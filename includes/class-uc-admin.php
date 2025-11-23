<?php
/**
 * Admin interface class
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class UC_Admin {
    
    /**
     * Add admin menu
     */
    public static function add_menu() {
        add_menu_page(
            __('Update Controller', 'update-controller'),
            __('Update Controller', 'update-controller'),
            'manage_options',
            'update-controller',
            array(__CLASS__, 'render_sites_page'),
            'dashicons-update',
            30
        );
        
        add_submenu_page(
            'update-controller',
            __('WordPress Sites', 'update-controller'),
            __('Sites', 'update-controller'),
            'manage_options',
            'update-controller',
            array(__CLASS__, 'render_sites_page')
        );
        
        add_submenu_page(
            'update-controller',
            __('Plugin Configurations', 'update-controller'),
            __('Plugins', 'update-controller'),
            'manage_options',
            'update-controller-plugins',
            array(__CLASS__, 'render_plugins_page')
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public static function enqueue_scripts($hook) {
        if (strpos($hook, 'update-controller') === false) {
            return;
        }
        
        wp_enqueue_style(
            'update-controller-admin',
            UPDATE_CONTROLLER_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            UPDATE_CONTROLLER_VERSION
        );
        
        wp_enqueue_script(
            'update-controller-admin',
            UPDATE_CONTROLLER_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            UPDATE_CONTROLLER_VERSION,
            true
        );
        
        wp_localize_script('update-controller-admin', 'ucAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('uc_admin_nonce'),
            'strings' => array(
                'confirmDelete' => __('Are you sure you want to delete this item?', 'update-controller'),
                'updateSuccess' => __('Update completed successfully!', 'update-controller'),
                'updateError' => __('Update failed. Please check the logs.', 'update-controller')
            )
        ));
    }
    
    /**
     * Render sites page
     */
    public static function render_sites_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'update-controller'));
        }
        
        $sites = UC_Database::get_sites();
        
        include UPDATE_CONTROLLER_PLUGIN_DIR . 'templates/sites-page.php';
    }
    
    /**
     * Render plugins page
     */
    public static function render_plugins_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'update-controller'));
        }
        
        $sites = UC_Database::get_sites();
        $plugins = UC_Database::get_plugins();
        
        include UPDATE_CONTROLLER_PLUGIN_DIR . 'templates/plugins-page.php';
    }
    
    /**
     * AJAX: Add site
     */
    public static function ajax_add_site() {
        check_ajax_referer('uc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'update-controller')));
        }
        
        $site_url = isset($_POST['site_url']) ? $_POST['site_url'] : '';
        $site_name = isset($_POST['site_name']) ? $_POST['site_name'] : '';
        $username = isset($_POST['username']) ? $_POST['username'] : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        
        if (empty($site_url) || empty($site_name) || empty($username) || empty($password)) {
            wp_send_json_error(array('message' => __('All fields are required', 'update-controller')));
        }
        
        $result = UC_Database::add_site($site_url, $site_name, $username, $password);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Site added successfully', 'update-controller'),
                'site_id' => $result
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to add site', 'update-controller')));
        }
    }
    
    /**
     * AJAX: Update site
     */
    public static function ajax_update_site() {
        check_ajax_referer('uc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'update-controller')));
        }
        
        $site_id = isset($_POST['site_id']) ? intval($_POST['site_id']) : 0;
        $site_url = isset($_POST['site_url']) ? $_POST['site_url'] : '';
        $site_name = isset($_POST['site_name']) ? $_POST['site_name'] : '';
        $username = isset($_POST['username']) ? $_POST['username'] : '';
        $password = isset($_POST['password']) ? $_POST['password'] : null;
        
        if (empty($site_id) || empty($site_url) || empty($site_name) || empty($username)) {
            wp_send_json_error(array('message' => __('Required fields are missing', 'update-controller')));
        }
        
        $result = UC_Database::update_site($site_id, $site_url, $site_name, $username, $password);
        
        if ($result !== false) {
            wp_send_json_success(array('message' => __('Site updated successfully', 'update-controller')));
        } else {
            wp_send_json_error(array('message' => __('Failed to update site', 'update-controller')));
        }
    }
    
    /**
     * AJAX: Delete site
     */
    public static function ajax_delete_site() {
        check_ajax_referer('uc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'update-controller')));
        }
        
        $site_id = isset($_POST['site_id']) ? intval($_POST['site_id']) : 0;
        
        if (empty($site_id)) {
            wp_send_json_error(array('message' => __('Invalid site ID', 'update-controller')));
        }
        
        $result = UC_Database::delete_site($site_id);
        
        if ($result !== false) {
            wp_send_json_success(array('message' => __('Site deleted successfully', 'update-controller')));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete site', 'update-controller')));
        }
    }
    
    /**
     * AJAX: Add plugin
     */
    public static function ajax_add_plugin() {
        check_ajax_referer('uc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'update-controller')));
        }
        
        $site_id = isset($_POST['site_id']) ? intval($_POST['site_id']) : 0;
        $plugin_slug = isset($_POST['plugin_slug']) ? $_POST['plugin_slug'] : '';
        $plugin_name = isset($_POST['plugin_name']) ? $_POST['plugin_name'] : '';
        $update_source = isset($_POST['update_source']) ? $_POST['update_source'] : '';
        $source_type = isset($_POST['source_type']) ? $_POST['source_type'] : 'web';
        $auto_update = isset($_POST['auto_update']) ? intval($_POST['auto_update']) : 1;
        
        if (empty($site_id) || empty($plugin_slug) || empty($plugin_name) || empty($update_source)) {
            wp_send_json_error(array('message' => __('All fields are required', 'update-controller')));
        }
        
        $result = UC_Database::add_plugin($site_id, $plugin_slug, $plugin_name, $update_source, $source_type, $auto_update);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Plugin added successfully', 'update-controller'),
                'plugin_id' => $result
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to add plugin', 'update-controller')));
        }
    }
    
    /**
     * AJAX: Update plugin
     */
    public static function ajax_update_plugin() {
        check_ajax_referer('uc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'update-controller')));
        }
        
        $plugin_id = isset($_POST['plugin_id']) ? intval($_POST['plugin_id']) : 0;
        $plugin_slug = isset($_POST['plugin_slug']) ? $_POST['plugin_slug'] : '';
        $plugin_name = isset($_POST['plugin_name']) ? $_POST['plugin_name'] : '';
        $update_source = isset($_POST['update_source']) ? $_POST['update_source'] : '';
        $source_type = isset($_POST['source_type']) ? $_POST['source_type'] : 'web';
        $auto_update = isset($_POST['auto_update']) ? intval($_POST['auto_update']) : 1;
        
        if (empty($plugin_id) || empty($plugin_slug) || empty($plugin_name) || empty($update_source)) {
            wp_send_json_error(array('message' => __('Required fields are missing', 'update-controller')));
        }
        
        $result = UC_Database::update_plugin($plugin_id, $plugin_slug, $plugin_name, $update_source, $source_type, $auto_update);
        
        if ($result !== false) {
            wp_send_json_success(array('message' => __('Plugin updated successfully', 'update-controller')));
        } else {
            wp_send_json_error(array('message' => __('Failed to update plugin', 'update-controller')));
        }
    }
    
    /**
     * AJAX: Delete plugin
     */
    public static function ajax_delete_plugin() {
        check_ajax_referer('uc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'update-controller')));
        }
        
        $plugin_id = isset($_POST['plugin_id']) ? intval($_POST['plugin_id']) : 0;
        
        if (empty($plugin_id)) {
            wp_send_json_error(array('message' => __('Invalid plugin ID', 'update-controller')));
        }
        
        $result = UC_Database::delete_plugin($plugin_id);
        
        if ($result !== false) {
            wp_send_json_success(array('message' => __('Plugin deleted successfully', 'update-controller')));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete plugin', 'update-controller')));
        }
    }
}
