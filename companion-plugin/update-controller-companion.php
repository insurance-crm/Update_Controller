<?php
/**
 * Plugin Name: Update Controller Companion
 * Plugin URI: https://github.com/insurance-crm/Update_Controller
 * Description: Companion plugin for Update Controller - allows remote plugin updates via REST API.
 * Version: 1.0.0
 * Author: Insurance CRM
 * Author URI: https://github.com/insurance-crm
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: update-controller-companion
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Update Controller Companion Class
 */
class UC_Companion {
    
    /**
     * Initialize the companion plugin
     */
    public static function init() {
        add_action('rest_api_init', array(__CLASS__, 'register_routes'));
    }
    
    /**
     * Register REST API routes
     */
    public static function register_routes() {
        // Test connection endpoint (no auth required)
        register_rest_route('uc-companion/v1', '/test', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'test_connection'),
            'permission_callback' => '__return_true'
        ));
        
        // Install/Update plugin endpoint
        register_rest_route('uc-companion/v1', '/install-plugin', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'install_plugin'),
            'permission_callback' => array(__CLASS__, 'check_permission')
        ));
        
        // Activate plugin endpoint
        register_rest_route('uc-companion/v1', '/activate-plugin', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'activate_plugin'),
            'permission_callback' => array(__CLASS__, 'check_permission')
        ));
        
        // Deactivate plugin endpoint
        register_rest_route('uc-companion/v1', '/deactivate-plugin', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'deactivate_plugin'),
            'permission_callback' => array(__CLASS__, 'check_permission')
        ));
    }
    
    /**
     * Test connection endpoint
     */
    public static function test_connection() {
        return array(
            'success' => true,
            'message' => 'Update Controller Companion is active and ready',
            'version' => '1.0.0',
            'wp_version' => get_bloginfo('version'),
            'site_url' => get_site_url()
        );
    }
    
    /**
     * Check if user has permission
     */
    public static function check_permission() {
        return current_user_can('activate_plugins');
    }
    
    /**
     * Install or update a plugin
     */
    public static function install_plugin($request) {
        $file_id = $request->get_param('file_id');
        
        if (empty($file_id)) {
            return new WP_Error('missing_param', __('Missing file_id parameter', 'update-controller-companion'), array('status' => 400));
        }
        
        // Get the uploaded file from media library
        $file_path = get_attached_file($file_id);
        
        if (!$file_path || !file_exists($file_path)) {
            return new WP_Error('file_not_found', __('Uploaded file not found', 'update-controller-companion'), array('status' => 404));
        }
        
        // Include required WordPress files
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        require_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');
        require_once(ABSPATH . 'wp-admin/includes/plugin-install.php');
        
        // Create upgrader instance
        $upgrader = new Plugin_Upgrader(new WP_Ajax_Upgrader_Skin());
        
        // Install/upgrade the plugin
        $result = $upgrader->install($file_path, array('overwrite_package' => true));
        
        // Delete the temporary file from media library
        wp_delete_attachment($file_id, true);
        
        if (is_wp_error($result)) {
            return new WP_Error('install_failed', $result->get_error_message(), array('status' => 500));
        }
        
        if ($result === false) {
            return new WP_Error('install_failed', __('Plugin installation failed', 'update-controller-companion'), array('status' => 500));
        }
        
        return array(
            'success' => true,
            'message' => __('Plugin installed successfully', 'update-controller-companion'),
            'plugin_file' => $upgrader->plugin_info()
        );
    }
    
    /**
     * Activate a plugin
     */
    public static function activate_plugin($request) {
        $plugin_slug = $request->get_param('plugin_slug');
        
        if (empty($plugin_slug)) {
            return new WP_Error('missing_param', __('Missing plugin_slug parameter', 'update-controller-companion'), array('status' => 400));
        }
        
        // Include required WordPress files
        require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        
        // Activate the plugin
        $result = activate_plugin($plugin_slug);
        
        if (is_wp_error($result)) {
            return new WP_Error('activation_failed', $result->get_error_message(), array('status' => 500));
        }
        
        return array(
            'success' => true,
            'message' => __('Plugin activated successfully', 'update-controller-companion')
        );
    }
    
    /**
     * Deactivate a plugin
     */
    public static function deactivate_plugin($request) {
        $plugin_slug = $request->get_param('plugin_slug');
        
        if (empty($plugin_slug)) {
            return new WP_Error('missing_param', __('Missing plugin_slug parameter', 'update-controller-companion'), array('status' => 400));
        }
        
        // Include required WordPress files
        require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        
        // Deactivate the plugin
        deactivate_plugins($plugin_slug);
        
        return array(
            'success' => true,
            'message' => __('Plugin deactivated successfully', 'update-controller-companion')
        );
    }
}

// Initialize the companion plugin
add_action('plugins_loaded', array('UC_Companion', 'init'));
