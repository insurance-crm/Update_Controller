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
        
        // Upload plugin file endpoint (bypasses media library)
        register_rest_route('uc-companion/v1', '/upload-plugin', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'upload_plugin'),
            'permission_callback' => array(__CLASS__, 'check_permission')
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
     * Upload plugin file directly (bypasses media library upload permissions)
     */
    public static function upload_plugin($request) {
        $file_data = $request->get_body();
        
        if (empty($file_data)) {
            return new WP_Error('missing_file', __('Missing file data', 'update-controller-companion'), array('status' => 400));
        }
        
        // Create temp file
        $temp_file = wp_tempnam('plugin-upload-');
        
        // Write the uploaded data to temp file
        $bytes_written = file_put_contents($temp_file, $file_data);
        
        if ($bytes_written === false) {
            return new WP_Error('write_failed', __('Failed to write uploaded file', 'update-controller-companion'), array('status' => 500));
        }
        
        // Store file path in transient for later use
        $file_id = md5($temp_file . time());
        set_transient('uc_plugin_file_' . $file_id, $temp_file, HOUR_IN_SECONDS);
        
        return array(
            'success' => true,
            'file_id' => $file_id,
            'file_size' => $bytes_written,
            'message' => __('Plugin file uploaded successfully', 'update-controller-companion')
        );
    }
    
    /**
     * Install or update a plugin
     */
    public static function install_plugin($request) {
        $file_id = $request->get_param('file_id');
        
        if (empty($file_id)) {
            return new WP_Error('missing_param', __('Missing file_id parameter', 'update-controller-companion'), array('status' => 400));
        }
        
        // Try to get file path from transient (new method)
        $file_path = get_transient('uc_plugin_file_' . $file_id);
        
        // If not found, try old method (media library)
        if (!$file_path) {
            $file_path = get_attached_file($file_id);
        }
        
        if (!$file_path || !file_exists($file_path)) {
            return new WP_Error('file_not_found', __('Uploaded file not found or expired', 'update-controller-companion'), array('status' => 404));
        }
        
        // Include required WordPress files
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        require_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');
        require_once(ABSPATH . 'wp-admin/includes/plugin-install.php');
        
        // Initialize filesystem
        WP_Filesystem();
        global $wp_filesystem;
        
        // Unzip to temporary directory to check plugin info
        $temp_dir = get_temp_dir() . 'uc-plugin-' . uniqid();
        $unzip_result = unzip_file($file_path, $temp_dir);
        
        if (is_wp_error($unzip_result)) {
            // Clean up
            if (get_transient('uc_plugin_file_' . $file_id)) {
                delete_transient('uc_plugin_file_' . $file_id);
                @unlink($file_path);
            }
            return new WP_Error('unzip_failed', $unzip_result->get_error_message(), array('status' => 500));
        }
        
        // Get plugin folder name and main file
        $plugin_folder = '';
        $plugin_main_file = '';
        
        $folders = glob($temp_dir . '/*', GLOB_ONLYDIR);
        if (!empty($folders)) {
            $plugin_folder = basename($folders[0]);
            
            // Find the main plugin file
            $php_files = glob($folders[0] . '/*.php');
            foreach ($php_files as $php_file) {
                $plugin_data = get_plugin_data($php_file, false, false);
                if (!empty($plugin_data['Name'])) {
                    $plugin_main_file = $plugin_folder . '/' . basename($php_file);
                    break;
                }
            }
        }
        
        // Check if plugin already exists (update) or new install
        $is_update = false;
        $plugin_dir = WP_PLUGIN_DIR . '/' . $plugin_folder;
        
        if ($plugin_main_file && file_exists(WP_PLUGIN_DIR . '/' . $plugin_main_file)) {
            $is_update = true;
            
            // Remove old plugin directory
            if (file_exists($plugin_dir)) {
                $wp_filesystem->delete($plugin_dir, true);
            }
        }
        
        // Move the plugin to the plugins directory
        $move_result = $wp_filesystem->move($folders[0], $plugin_dir, true);
        
        // Clean up temp directory
        $wp_filesystem->delete($temp_dir, true);
        
        // Clean up uploaded file
        if (get_transient('uc_plugin_file_' . $file_id)) {
            delete_transient('uc_plugin_file_' . $file_id);
            @unlink($file_path);
        } else {
            wp_delete_attachment($file_id, true);
        }
        
        if (!$move_result) {
            return new WP_Error('install_failed', __('Failed to move plugin to plugins directory', 'update-controller-companion'), array('status' => 500));
        }
        
        return array(
            'success' => true,
            'message' => $is_update ? __('Plugin updated successfully', 'update-controller-companion') : __('Plugin installed successfully', 'update-controller-companion'),
            'plugin_file' => $plugin_main_file,
            'is_update' => $is_update,
            'plugin_folder' => $plugin_folder
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
