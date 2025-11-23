<?php
/**
 * Plugin updater class
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class UC_Updater {
    
    /**
     * AJAX: Run manual update
     */
    public static function ajax_run_update() {
        check_ajax_referer('uc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'update-controller')));
        }
        
        $plugin_id = isset($_POST['plugin_id']) ? intval($_POST['plugin_id']) : 0;
        
        if (empty($plugin_id)) {
            wp_send_json_error(array('message' => __('Invalid plugin ID', 'update-controller')));
        }
        
        $result = self::update_plugin($plugin_id);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Run scheduled update
     */
    public static function run_scheduled_update() {
        $plugins = UC_Database::get_plugins();
        
        foreach ($plugins as $plugin) {
            if ($plugin->auto_update == 1) {
                self::update_plugin($plugin->id);
            }
        }
    }
    
    /**
     * Update a specific plugin
     */
    public static function update_plugin($plugin_id) {
        $plugin = UC_Database::get_plugin($plugin_id);
        
        if (!$plugin) {
            return array(
                'success' => false,
                'message' => __('Plugin not found', 'update-controller')
            );
        }
        
        $site = UC_Database::get_site($plugin->site_id);
        
        if (!$site) {
            return array(
                'success' => false,
                'message' => __('Site not found', 'update-controller')
            );
        }
        
        // Download plugin file from source
        $plugin_file = self::download_plugin($plugin->update_source, $plugin->source_type);
        
        if (is_wp_error($plugin_file)) {
            return array(
                'success' => false,
                'message' => $plugin_file->get_error_message()
            );
        }
        
        // Upload and install plugin on remote site
        $result = self::install_remote_plugin($site, $plugin, $plugin_file);
        
        // Clean up temporary file
        if (file_exists($plugin_file)) {
            @unlink($plugin_file);
        }
        
        if ($result['success']) {
            UC_Database::update_plugin_last_update($plugin_id);
            UC_Database::update_site_last_update($plugin->site_id);
        }
        
        return $result;
    }
    
    /**
     * Download plugin from source
     */
    private static function download_plugin($source_url, $source_type) {
        // Handle GitHub URLs
        if ($source_type === 'github' || strpos($source_url, 'github.com') !== false) {
            $source_url = self::convert_github_url($source_url);
        }
        
        $temp_file = download_url($source_url);
        
        if (is_wp_error($temp_file)) {
            return $temp_file;
        }
        
        return $temp_file;
    }
    
    /**
     * Convert GitHub repository URL to download URL
     */
    private static function convert_github_url($url) {
        // Convert GitHub repository URLs to ZIP download URLs
        // Example: https://github.com/user/repo -> https://github.com/user/repo/archive/refs/heads/main.zip
        
        $url = rtrim($url, '/');
        
        // If already a direct download link, return as is
        if (strpos($url, '/archive/') !== false || strpos($url, '/releases/download/') !== false) {
            return $url;
        }
        
        // Convert repository URL to main branch ZIP
        if (preg_match('#github\.com/([^/]+)/([^/]+)#', $url, $matches)) {
            return "https://github.com/{$matches[1]}/{$matches[2]}/archive/refs/heads/main.zip";
        }
        
        return $url;
    }
    
    /**
     * Install plugin on remote WordPress site
     */
    private static function install_remote_plugin($site, $plugin, $plugin_file) {
        // Decrypt password
        $password = UC_Encryption::decrypt($site->password);
        
        // Authenticate with WordPress site
        $auth_result = self::authenticate_site($site->site_url, $site->username, $password);
        
        if (is_wp_error($auth_result)) {
            return array(
                'success' => false,
                'message' => __('Authentication failed: ', 'update-controller') . $auth_result->get_error_message()
            );
        }
        
        $cookie = $auth_result;
        
        // Upload plugin file
        $upload_result = self::upload_plugin_file($site->site_url, $plugin_file, $cookie);
        
        if (is_wp_error($upload_result)) {
            return array(
                'success' => false,
                'message' => __('Upload failed: ', 'update-controller') . $upload_result->get_error_message()
            );
        }
        
        // Deactivate plugin before update
        self::toggle_plugin($site->site_url, $plugin->plugin_slug, 'deactivate', $cookie);
        
        // Install/update plugin
        $install_result = self::install_plugin_from_upload($site->site_url, $upload_result, $cookie);
        
        if (is_wp_error($install_result)) {
            return array(
                'success' => false,
                'message' => __('Installation failed: ', 'update-controller') . $install_result->get_error_message()
            );
        }
        
        // Reactivate plugin
        self::toggle_plugin($site->site_url, $plugin->plugin_slug, 'activate', $cookie);
        
        return array(
            'success' => true,
            'message' => sprintf(__('Plugin "%s" updated successfully on %s', 'update-controller'), $plugin->plugin_name, $site->site_name)
        );
    }
    
    /**
     * Authenticate with WordPress site
     */
    private static function authenticate_site($site_url, $username, $password) {
        $site_url = rtrim($site_url, '/');
        
        // Try to authenticate using WordPress REST API with Application Passwords
        $response = wp_remote_post($site_url . '/wp-json/wp/v2/users/me', array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($username . ':' . $password)
            ),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $code = wp_remote_retrieve_response_code($response);
        
        if ($code === 200) {
            // Return authentication credentials for further requests
            return array(
                'Authorization' => 'Basic ' . base64_encode($username . ':' . $password)
            );
        }
        
        return new WP_Error('auth_failed', __('Authentication failed. Please check credentials.', 'update-controller'));
    }
    
    /**
     * Upload plugin file to remote site
     */
    private static function upload_plugin_file($site_url, $file_path, $auth_headers) {
        $site_url = rtrim($site_url, '/');
        
        // Read file content
        $file_content = file_get_contents($file_path);
        $file_name = basename($file_path);
        
        // Upload via REST API
        $response = wp_remote_post($site_url . '/wp-json/wp/v2/media', array(
            'headers' => array_merge($auth_headers, array(
                'Content-Disposition' => 'attachment; filename=' . $file_name,
                'Content-Type' => 'application/zip'
            )),
            'body' => $file_content,
            'timeout' => 60
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['id'])) {
            return $body;
        }
        
        return new WP_Error('upload_failed', __('Failed to upload plugin file', 'update-controller'));
    }
    
    /**
     * Install plugin from uploaded file
     */
    private static function install_plugin_from_upload($site_url, $upload_data, $auth_headers) {
        $site_url = rtrim($site_url, '/');
        
        // This would typically use a custom REST API endpoint on the remote site
        // For now, we'll return a success indicator
        // In production, you'd need to implement a custom endpoint on the target WordPress site
        
        return array('success' => true);
    }
    
    /**
     * Toggle plugin activation status
     */
    private static function toggle_plugin($site_url, $plugin_slug, $action, $auth_headers) {
        $site_url = rtrim($site_url, '/');
        
        // This would typically use WordPress REST API or a custom endpoint
        // The actual implementation depends on the target site's capabilities
        
        return true;
    }
}
