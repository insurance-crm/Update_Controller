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
        
        // Initialize filesystem - try direct method first for reliability
        global $wp_filesystem;
        
        // Force direct filesystem method for plugin installation
        add_filter('filesystem_method', array(__CLASS__, 'filter_filesystem_method'), 999);
        WP_Filesystem();
        remove_filter('filesystem_method', array(__CLASS__, 'filter_filesystem_method'), 999);
        
        // Verify filesystem is available
        if (!$wp_filesystem) {
            // Try without filter
            $creds = request_filesystem_credentials('', '', false, false, array());
            WP_Filesystem($creds);
        }
        
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
        if (empty($folders)) {
            // Clean up
            self::recursive_rmdir($temp_dir);
            if (get_transient('uc_plugin_file_' . $file_id)) {
                delete_transient('uc_plugin_file_' . $file_id);
                @unlink($file_path);
            }
            return new WP_Error('invalid_plugin', __('No plugin folder found in ZIP file', 'update-controller-companion'), array('status' => 500));
        }
        
        $plugin_folder = basename($folders[0]);
        $source_dir = $folders[0];
        
        // Find the main plugin file
        $php_files = glob($source_dir . '/*.php');
        foreach ($php_files as $php_file) {
            $plugin_data = get_plugin_data($php_file, false, false);
            if (!empty($plugin_data['Name'])) {
                $plugin_main_file = $plugin_folder . '/' . basename($php_file);
                break;
            }
        }
        
        // Check if plugin already exists (update) or new install
        $is_update = false;
        $plugin_dir = WP_PLUGIN_DIR . '/' . $plugin_folder;
        
        // Pre-check: Ensure plugins directory is writable
        if (!is_writable(WP_PLUGIN_DIR)) {
            // Clean up
            self::recursive_rmdir($temp_dir);
            if (get_transient('uc_plugin_file_' . $file_id)) {
                delete_transient('uc_plugin_file_' . $file_id);
                @unlink($file_path);
            }
            
            $perms = substr(sprintf('%o', fileperms(WP_PLUGIN_DIR)), -4);
            return new WP_Error('permission_denied', 
                sprintf(__('Plugin directory (%s) is not writable. Current permissions: %s. Required: 755 or 775. Please update directory permissions.', 'update-controller-companion'), 
                    WP_PLUGIN_DIR, $perms), 
                array('status' => 500));
        }
        
        if ($plugin_main_file && file_exists(WP_PLUGIN_DIR . '/' . $plugin_main_file)) {
            $is_update = true;
            
            // Remove old plugin directory
            if (file_exists($plugin_dir)) {
                $delete_success = false;
                
                if ($wp_filesystem) {
                    $delete_success = $wp_filesystem->delete($plugin_dir, true);
                }
                
                // Fallback to PHP if WP_Filesystem failed
                if (!$delete_success) {
                    $delete_success = self::recursive_rmdir($plugin_dir);
                }
                
                if (!$delete_success && file_exists($plugin_dir)) {
                    // Clean up temp files
                    self::recursive_rmdir($temp_dir);
                    if (get_transient('uc_plugin_file_' . $file_id)) {
                        delete_transient('uc_plugin_file_' . $file_id);
                        @unlink($file_path);
                    }
                    
                    return new WP_Error('delete_failed', 
                        sprintf(__('Failed to remove old plugin directory. Please check file permissions on %s', 'update-controller-companion'), $plugin_dir), 
                        array('status' => 500));
                }
            }
        }
        
        // Install the plugin - use copy instead of move for cross-device compatibility
        $install_result = false;
        $install_error = '';
        
        // Method 1: Try WP_Filesystem copy_dir (most reliable)
        if ($wp_filesystem) {
            $install_result = copy_dir($source_dir, $plugin_dir);
            if (is_wp_error($install_result)) {
                $install_error = $install_result->get_error_message();
                $install_result = false;
            } else {
                $install_result = true;
            }
        }
        
        // Method 2: Try PHP recursive copy
        if (!$install_result) {
            $install_result = self::recursive_copy($source_dir, $plugin_dir);
            if (!$install_result) {
                $install_error = 'PHP recursive copy failed';
            }
        }
        
        // Method 3: Try rename (works if on same filesystem)
        if (!$install_result) {
            $install_result = @rename($source_dir, $plugin_dir);
            if (!$install_result) {
                $install_error = 'Rename failed - possibly cross-device';
            }
        }
        
        // Clean up temp directory
        self::recursive_rmdir($temp_dir);
        
        // Clean up uploaded file
        if (get_transient('uc_plugin_file_' . $file_id)) {
            delete_transient('uc_plugin_file_' . $file_id);
            @unlink($file_path);
        } else {
            wp_delete_attachment($file_id, true);
        }
        
        if (!$install_result || !file_exists($plugin_dir)) {
            $error_msg = __('Failed to install plugin to plugins directory. ', 'update-controller-companion');
            
            // Add debugging info
            $error_msg .= sprintf(__('Target: %s. ', 'update-controller-companion'), $plugin_dir);
            
            if (!empty($install_error)) {
                $error_msg .= sprintf(__('Error: %s. ', 'update-controller-companion'), $install_error);
            }
            
            // Check directory permissions
            $perms = substr(sprintf('%o', fileperms(WP_PLUGIN_DIR)), -4);
            $owner = fileowner(WP_PLUGIN_DIR);
            if (function_exists('posix_getpwuid')) {
                $owner_info = posix_getpwuid($owner);
                if ($owner_info && isset($owner_info['name'])) {
                    $owner = $owner_info['name'];
                }
            }
            $error_msg .= sprintf(__('Plugin dir permissions: %s, owner: %s.', 'update-controller-companion'), $perms, $owner);
            
            return new WP_Error('install_failed', $error_msg, array('status' => 500));
        }
        
        // Verify plugin files exist
        if ($plugin_main_file && !file_exists(WP_PLUGIN_DIR . '/' . $plugin_main_file)) {
            return new WP_Error('install_failed', 
                sprintf(__('Plugin installed but main file not found: %s', 'update-controller-companion'), $plugin_main_file), 
                array('status' => 500));
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
     * Filter to force direct filesystem method
     */
    public static function filter_filesystem_method($method) {
        return 'direct';
    }
    
    /**
     * Recursively copy directory
     */
    private static function recursive_copy($source, $dest) {
        // Create destination directory with same permissions as plugins dir
        if (!file_exists($dest)) {
            $parent_perms = fileperms(WP_PLUGIN_DIR) & 0777;
            if (!@mkdir($dest, $parent_perms, true)) {
                return false;
            }
        }
        
        // Get directory contents using scandir (more efficient)
        $files = @scandir($source);
        if ($files === false) {
            return false;
        }
        
        $success = true;
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $src_path = $source . '/' . $file;
            $dest_path = $dest . '/' . $file;
            
            if (is_dir($src_path)) {
                if (!self::recursive_copy($src_path, $dest_path)) {
                    $success = false;
                }
            } else {
                if (!@copy($src_path, $dest_path)) {
                    $success = false;
                }
            }
        }
        
        return $success;
    }
    
    /**
     * Recursively delete directory
     */
    private static function recursive_rmdir($dir) {
        if (!file_exists($dir)) {
            return true;
        }
        
        if (!is_dir($dir)) {
            return @unlink($dir);
        }
        
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? self::recursive_rmdir($path) : @unlink($path);
        }
        
        return @rmdir($dir);
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
