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
        
        add_submenu_page(
            'update-controller',
            __('Update Packages', 'update-controller'),
            __('Updates', 'update-controller'),
            'manage_options',
            'update-controller-updates',
            array(__CLASS__, 'render_updates_page')
        );
        
        add_submenu_page(
            'update-controller',
            __('Update Logs', 'update-controller'),
            __('Logs', 'update-controller'),
            'manage_options',
            'update-controller-logs',
            array(__CLASS__, 'render_logs_page')
        );
        
        add_submenu_page(
            'update-controller',
            __('Companion Check', 'update-controller'),
            __('Companion Check', 'update-controller'),
            'manage_options',
            'update-controller-companion',
            array(__CLASS__, 'render_companion_page')
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
        $updates = UC_Database::get_updates();
        
        include UPDATE_CONTROLLER_PLUGIN_DIR . 'templates/plugins-page.php';
    }
    
    /**
     * Render updates page
     */
    public static function render_updates_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'update-controller'));
        }
        
        // Ensure upload directory and .htaccess exist with correct permissions
        self::ensure_upload_directory();
        
        $updates = UC_Database::get_updates();
        
        include UPDATE_CONTROLLER_PLUGIN_DIR . 'templates/updates-page.php';
    }
    
    /**
     * Render logs page
     */
    public static function render_logs_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'update-controller'));
        }
        
        $logs = UC_Database::get_update_logs_with_details();
        $sites = UC_Database::get_sites();
        
        include UPDATE_CONTROLLER_PLUGIN_DIR . 'templates/logs-page.php';
    }
    
    /**
     * Render companion check page
     */
    public static function render_companion_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'update-controller'));
        }
        
        $sites = UC_Database::get_sites();
        
        // Get local companion plugin info
        $local_companion_file = UPDATE_CONTROLLER_PLUGIN_DIR . 'companion-plugin/update-controller-companion.php';
        $local_version = 'N/A';
        $local_size = 0;
        
        if (file_exists($local_companion_file)) {
            $local_content = file_get_contents($local_companion_file);
            $local_size = filesize($local_companion_file);
            preg_match('/Version:\s*([0-9.]+)/i', $local_content, $matches);
            $local_version = isset($matches[1]) ? $matches[1] : 'N/A';
        }
        
        include UPDATE_CONTROLLER_PLUGIN_DIR . 'templates/companion-page.php';
    }
    
    /**
     * Ensure upload directory exists with proper .htaccess
     */
    private static function ensure_upload_directory() {
        $upload_dir = wp_upload_dir();
        $uc_dir = $upload_dir['basedir'] . '/update-controller';
        
        // Create directory if it doesn't exist
        if (!file_exists($uc_dir)) {
            wp_mkdir_p($uc_dir);
        }
        
        // Create/update .htaccess to allow direct access
        $htaccess_content = "# Allow direct access to files in this directory\n";
        $htaccess_content .= "# Generated by Update Controller plugin\n\n";
        $htaccess_content .= "Options -Indexes\n\n";
        $htaccess_content .= "# Apache 2.4+\n";
        $htaccess_content .= "<IfModule mod_authz_core.c>\n";
        $htaccess_content .= "    Require all granted\n";
        $htaccess_content .= "</IfModule>\n\n";
        $htaccess_content .= "# Apache 2.2\n";
        $htaccess_content .= "<IfModule !mod_authz_core.c>\n";
        $htaccess_content .= "    Order allow,deny\n";
        $htaccess_content .= "    Allow from all\n";
        $htaccess_content .= "</IfModule>\n\n";
        $htaccess_content .= "# Allow ZIP files\n";
        $htaccess_content .= "<FilesMatch \"\\.zip$\">\n";
        $htaccess_content .= "    ForceType application/zip\n";
        $htaccess_content .= "</FilesMatch>\n";
        file_put_contents($uc_dir . '/.htaccess', $htaccess_content);
        
        // Add index.php to prevent directory listing
        if (!file_exists($uc_dir . '/index.php')) {
            file_put_contents($uc_dir . '/index.php', '<?php // Silence is golden');
        }
    }
    
    /**
     * AJAX: Add site
     */
    public static function ajax_add_site() {
        check_ajax_referer('uc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'update-controller')));
            exit;
        }
        
        $site_url = isset($_POST['site_url']) ? $_POST['site_url'] : '';
        $site_name = isset($_POST['site_name']) ? $_POST['site_name'] : '';
        $username = isset($_POST['username']) ? $_POST['username'] : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        
        if (empty($site_url) || empty($site_name) || empty($username) || empty($password)) {
            wp_send_json_error(array('message' => __('All fields are required', 'update-controller')));
            exit;
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
        exit;
    }
    
    /**
     * AJAX: Update site
     */
    public static function ajax_update_site() {
        check_ajax_referer('uc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'update-controller')));
            exit;
        }
        
        $site_id = isset($_POST['site_id']) ? intval($_POST['site_id']) : 0;
        $site_url = isset($_POST['site_url']) ? $_POST['site_url'] : '';
        $site_name = isset($_POST['site_name']) ? $_POST['site_name'] : '';
        $username = isset($_POST['username']) ? $_POST['username'] : '';
        $password = isset($_POST['password']) ? $_POST['password'] : null;
        
        if (empty($site_id) || empty($site_url) || empty($site_name) || empty($username)) {
            wp_send_json_error(array('message' => __('Required fields are missing', 'update-controller')));
            exit;
        }
        
        $result = UC_Database::update_site($site_id, $site_url, $site_name, $username, $password);
        
        if ($result !== false) {
            wp_send_json_success(array('message' => __('Site updated successfully', 'update-controller')));
        } else {
            wp_send_json_error(array('message' => __('Failed to update site', 'update-controller')));
        }
        exit;
    }
    
    /**
     * AJAX: Delete site
     */
    public static function ajax_delete_site() {
        check_ajax_referer('uc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'update-controller')));
            exit;
        }
        
        $site_id = isset($_POST['site_id']) ? intval($_POST['site_id']) : 0;
        
        if (empty($site_id)) {
            wp_send_json_error(array('message' => __('Invalid site ID', 'update-controller')));
            exit;
        }
        
        $result = UC_Database::delete_site($site_id);
        
        if ($result !== false) {
            wp_send_json_success(array('message' => __('Site deleted successfully', 'update-controller')));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete site', 'update-controller')));
        }
        exit;
    }
    
    /**
     * AJAX: Test connection to remote site
     */
    public static function ajax_test_connection() {
        check_ajax_referer('uc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'update-controller')));
            exit;
        }
        
        $site_id = isset($_POST['site_id']) ? intval($_POST['site_id']) : 0;
        
        if (empty($site_id)) {
            wp_send_json_error(array('message' => __('Invalid site ID', 'update-controller')));
            exit;
        }
        
        $site = UC_Database::get_site($site_id);
        
        if (!$site) {
            wp_send_json_error(array('message' => __('Site not found', 'update-controller')));
            exit;
        }
        
        $site_url = rtrim($site->site_url, '/');
        
        // Test 1: Check if companion plugin endpoint is accessible
        $test_url = $site_url . '/wp-json/uc-companion/v1/test';
        $test_response = wp_remote_get($test_url, array('timeout' => 10));
        
        if (is_wp_error($test_response)) {
            wp_send_json_error(array(
                'message' => sprintf(__('Connection failed: %s', 'update-controller'), $test_response->get_error_message()),
                'details' => array(
                    'test_url' => $test_url,
                    'error' => $test_response->get_error_message()
                )
            ));
            exit;
        }
        
        $test_code = wp_remote_retrieve_response_code($test_response);
        $test_body = wp_remote_retrieve_body($test_response);
        
        if ($test_code !== 200) {
            wp_send_json_error(array(
                'message' => sprintf(__('Companion plugin test failed (HTTP %d). Plugin may not be installed or activated.', 'update-controller'), $test_code),
                'details' => array(
                    'test_url' => $test_url,
                    'http_code' => $test_code,
                    'response' => substr($test_body, 0, 200)
                )
            ));
            exit;
        }
        
        // Test 2: Try authentication
        $password = UC_Encryption::decrypt($site->password);
        $auth_url = $site_url . '/wp-json/wp/v2/users/me';
        $auth_response = wp_remote_post($auth_url, array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($site->username . ':' . $password)
            ),
            'timeout' => 10
        ));
        
        if (is_wp_error($auth_response)) {
            wp_send_json_success(array(
                'message' => __('Companion plugin is active, but authentication failed.', 'update-controller'),
                'details' => array(
                    'companion_status' => 'OK',
                    'auth_status' => 'FAILED',
                    'auth_error' => $auth_response->get_error_message()
                )
            ));
            exit;
        }
        
        $auth_code = wp_remote_retrieve_response_code($auth_response);
        
        if ($auth_code !== 200) {
            $auth_message = '';
            switch ($auth_code) {
                case 401:
                    $auth_message = __('Invalid credentials. Check username and Application Password.', 'update-controller');
                    break;
                case 403:
                    $auth_message = __('Access forbidden. Check Application Passwords are enabled.', 'update-controller');
                    break;
                default:
                    $auth_message = sprintf(__('Authentication returned HTTP %d', 'update-controller'), $auth_code);
            }
            
            wp_send_json_error(array(
                'message' => __('Companion plugin is active, but authentication failed: ', 'update-controller') . $auth_message,
                'details' => array(
                    'companion_status' => 'OK',
                    'auth_status' => 'FAILED',
                    'auth_code' => $auth_code
                )
            ));
            exit;
        }
        
        // Both tests passed!
        $test_data = json_decode($test_body, true);
        $companion_needs_update = false;
        $local_version = '0.0.0';
        $remote_version = isset($test_data['version']) && !empty($test_data['version']) ? $test_data['version'] : 'unknown';
        
        // Check if companion plugin needs update
        $local_companion_file = UPDATE_CONTROLLER_PLUGIN_DIR . 'companion-plugin/update-controller-companion.php';
        if (file_exists($local_companion_file)) {
            $local_content = file_get_contents($local_companion_file);
            $local_size = filesize($local_companion_file);
            
            // Get version from local file
            preg_match('/Version:\s*([0-9.]+)/i', $local_content, $local_matches);
            $local_version = isset($local_matches[1]) ? $local_matches[1] : '0.0.0';
            
            $remote_size = isset($test_data['file_size']) ? intval($test_data['file_size']) : 0;
            
            // Compare versions and sizes - check if local (server) version is newer or different
            // local = Update Controller server, remote = target WordPress site
            // If remote version is unknown, it's an old companion that needs update
            if ($remote_version === 'unknown' || 
                version_compare($local_version, $remote_version, '>') || 
                ($local_size != $remote_size && version_compare($local_version, $remote_version, '='))) {
                $companion_needs_update = true;
            }
        }
        
        $message = __('✓ Connection successful! Companion plugin is active and authentication works.', 'update-controller');
        
        wp_send_json_success(array(
            'message' => $message,
            'details' => array(
                'companion_status' => 'OK',
                'auth_status' => 'OK',
                'companion_version' => $remote_version,
                'local_companion_version' => $local_version,
                'companion_needs_update' => $companion_needs_update,
                'wp_version' => isset($test_data['wp_version']) ? $test_data['wp_version'] : 'unknown',
                'site_url' => isset($test_data['site_url']) ? $test_data['site_url'] : $site_url
            )
        ));
        exit;
    }
    
    /**
     * AJAX: Update companion plugin on remote site
     */
    public static function ajax_update_companion() {
        check_ajax_referer('uc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'update-controller')));
            exit;
        }
        
        $site_id = isset($_POST['site_id']) ? intval($_POST['site_id']) : 0;
        
        if (empty($site_id)) {
            wp_send_json_error(array('message' => __('Invalid site ID', 'update-controller')));
            exit;
        }
        
        $site = UC_Database::get_site($site_id);
        
        if (!$site) {
            wp_send_json_error(array('message' => __('Site not found', 'update-controller')));
            exit;
        }
        
        $local_companion_file = UPDATE_CONTROLLER_PLUGIN_DIR . 'companion-plugin/update-controller-companion.php';
        if (!file_exists($local_companion_file)) {
            wp_send_json_error(array('message' => __('Local companion plugin file not found', 'update-controller')));
            exit;
        }
        
        $local_content = file_get_contents($local_companion_file);
        
        // Get local version
        preg_match('/Version:\s*([0-9.]+)/i', $local_content, $local_matches);
        $local_version = isset($local_matches[1]) ? $local_matches[1] : '0.0.0';
        
        // Get remote version first
        $site_url = rtrim($site->site_url, '/');
        $test_url = $site_url . '/wp-json/uc-companion/v1/test';
        $test_response = wp_remote_get($test_url, array('timeout' => 10));
        
        $remote_version = '0.0.0';
        if (!is_wp_error($test_response) && wp_remote_retrieve_response_code($test_response) === 200) {
            $test_data = json_decode(wp_remote_retrieve_body($test_response), true);
            $remote_version = isset($test_data['version']) ? $test_data['version'] : '0.0.0';
        }
        
        $password = UC_Encryption::decrypt($site->password);
        $update_result = self::update_remote_companion($site, $local_content, $password);
        
        if ($update_result['success']) {
            $update_message = sprintf(
                __('Companion plugin updated from v%s to v%s', 'update-controller'),
                $remote_version,
                $local_version
            );
            
            // Log the companion update
            UC_Database::add_update_log(
                $site_id,
                0, // No plugin_id for companion
                'Update Controller Companion',
                $remote_version,
                $local_version,
                'success',
                $update_message,
                ''
            );
            
            wp_send_json_success(array(
                'message' => $update_message,
                'old_version' => $remote_version,
                'new_version' => $local_version
            ));
        } else {
            wp_send_json_error(array('message' => $update_result['message']));
        }
        exit;
    }
    
    /**
     * Update companion plugin on remote site
     */
    private static function update_remote_companion($site, $file_content, $password) {
        $site_url = rtrim($site->site_url, '/');
        $auth_header = 'Basic ' . base64_encode($site->username . ':' . $password);
        
        // First try the direct update-companion endpoint (for v1.0.1+)
        $response = wp_remote_post($site_url . '/wp-json/uc-companion/v1/update-companion', array(
            'headers' => array(
                'Authorization' => $auth_header,
                'Content-Type' => 'text/plain'
            ),
            'body' => $file_content,
            'timeout' => 60
        ));
        
        if (!is_wp_error($response)) {
            $code = wp_remote_retrieve_response_code($response);
            $body = json_decode(wp_remote_retrieve_body($response), true);
            
            if ($code === 200 && isset($body['success']) && $body['success']) {
                return array('success' => true, 'message' => isset($body['message']) ? $body['message'] : '');
            }
            
            // If 404, the endpoint doesn't exist - try fallback method
            if ($code !== 404) {
                return array('success' => false, 'message' => isset($body['message']) ? $body['message'] : 'Update failed with HTTP ' . $code);
            }
        }
        
        // Fallback: Use upload-plugin + install-plugin for old companion versions (v1.0.0)
        // Create a ZIP file with the companion plugin
        $temp_zip = wp_tempnam('companion-update-') . '.zip';
        $zip = new ZipArchive();
        
        if ($zip->open($temp_zip, ZipArchive::CREATE) !== true) {
            return array('success' => false, 'message' => 'Failed to create temporary ZIP file');
        }
        
        // Add companion plugin to ZIP with proper directory structure
        $zip->addFromString('update-controller-companion/update-controller-companion.php', $file_content);
        $zip->close();
        
        $zip_content = file_get_contents($temp_zip);
        @unlink($temp_zip);
        
        if (empty($zip_content)) {
            return array('success' => false, 'message' => 'Failed to read ZIP file');
        }
        
        // Step 1: Upload the ZIP file
        $upload_response = wp_remote_post($site_url . '/wp-json/uc-companion/v1/upload-plugin', array(
            'headers' => array(
                'Authorization' => $auth_header,
                'Content-Type' => 'application/octet-stream',
                'Content-Disposition' => 'attachment; filename="update-controller-companion.zip"'
            ),
            'body' => $zip_content,
            'timeout' => 60
        ));
        
        if (is_wp_error($upload_response)) {
            return array('success' => false, 'message' => 'Upload failed: ' . $upload_response->get_error_message());
        }
        
        $upload_code = wp_remote_retrieve_response_code($upload_response);
        $upload_body = json_decode(wp_remote_retrieve_body($upload_response), true);
        
        // Old companion (v1.0.0) returns file_id, new companion might return file_path
        $file_id = isset($upload_body['file_id']) ? $upload_body['file_id'] : null;
        $file_path = isset($upload_body['file_path']) ? $upload_body['file_path'] : null;
        
        if ($upload_code !== 200 || (!$file_id && !$file_path)) {
            return array('success' => false, 'message' => 'Upload failed: ' . (isset($upload_body['message']) ? $upload_body['message'] : 'Unknown error'));
        }
        
        // Step 2: Install the uploaded plugin (this will overwrite the existing one)
        // Old companion uses file_id, new companion might use file_path
        $install_params = array('overwrite' => true);
        if ($file_id) {
            $install_params['file_id'] = $file_id;
        } else {
            $install_params['file_path'] = $file_path;
        }
        
        $install_response = wp_remote_post($site_url . '/wp-json/uc-companion/v1/install-plugin', array(
            'headers' => array(
                'Authorization' => $auth_header,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($install_params),
            'timeout' => 60
        ));
        
        if (is_wp_error($install_response)) {
            return array('success' => false, 'message' => 'Install failed: ' . $install_response->get_error_message());
        }
        
        $install_code = wp_remote_retrieve_response_code($install_response);
        $install_body = json_decode(wp_remote_retrieve_body($install_response), true);
        
        if ($install_code === 200 && isset($install_body['success']) && $install_body['success']) {
            return array('success' => true, 'message' => 'Companion plugin updated via install method');
        }
        
        return array('success' => false, 'message' => isset($install_body['message']) ? $install_body['message'] : 'Install failed with HTTP ' . $install_code);
    }
    
    /**
     * AJAX: Add plugin
     */
    public static function ajax_add_plugin() {
        check_ajax_referer('uc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'update-controller')));
            exit;
        }
        
        $site_id = isset($_POST['site_id']) ? intval($_POST['site_id']) : 0;
        $plugin_slug = isset($_POST['plugin_slug']) ? $_POST['plugin_slug'] : '';
        $plugin_name = isset($_POST['plugin_name']) ? $_POST['plugin_name'] : '';
        $update_source = isset($_POST['update_source']) ? $_POST['update_source'] : '';
        $source_type = isset($_POST['source_type']) ? $_POST['source_type'] : 'web';
        $auto_update = isset($_POST['auto_update']) ? intval($_POST['auto_update']) : 1;
        
        if (empty($site_id) || empty($plugin_slug) || empty($plugin_name) || empty($update_source)) {
            wp_send_json_error(array('message' => __('All fields are required', 'update-controller')));
            exit;
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
        exit;
    }
    
    /**
     * AJAX: Update plugin
     */
    public static function ajax_update_plugin() {
        check_ajax_referer('uc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'update-controller')));
            exit;
        }
        
        $plugin_id = isset($_POST['plugin_id']) ? intval($_POST['plugin_id']) : 0;
        $plugin_slug = isset($_POST['plugin_slug']) ? $_POST['plugin_slug'] : '';
        $plugin_name = isset($_POST['plugin_name']) ? $_POST['plugin_name'] : '';
        $update_source = isset($_POST['update_source']) ? $_POST['update_source'] : '';
        $source_type = isset($_POST['source_type']) ? $_POST['source_type'] : 'web';
        $auto_update = isset($_POST['auto_update']) ? intval($_POST['auto_update']) : 1;
        
        if (empty($plugin_id) || empty($plugin_slug) || empty($plugin_name) || empty($update_source)) {
            wp_send_json_error(array('message' => __('Required fields are missing', 'update-controller')));
            exit;
        }
        
        $result = UC_Database::update_plugin($plugin_id, $plugin_slug, $plugin_name, $update_source, $source_type, $auto_update);
        
        if ($result !== false) {
            wp_send_json_success(array('message' => __('Plugin updated successfully', 'update-controller')));
        } else {
            wp_send_json_error(array('message' => __('Failed to update plugin', 'update-controller')));
        }
        exit;
    }
    
    /**
     * AJAX: Delete plugin
     */
    public static function ajax_delete_plugin() {
        check_ajax_referer('uc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'update-controller')));
            exit;
        }
        
        $plugin_id = isset($_POST['plugin_id']) ? intval($_POST['plugin_id']) : 0;
        
        if (empty($plugin_id)) {
            wp_send_json_error(array('message' => __('Invalid plugin ID', 'update-controller')));
            exit;
        }
        
        $result = UC_Database::delete_plugin($plugin_id);
        
        if ($result !== false) {
            wp_send_json_success(array('message' => __('Plugin deleted successfully', 'update-controller')));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete plugin', 'update-controller')));
        }
        exit;
    }
    
    /**
     * AJAX: Add update package
     */
    public static function ajax_add_update_package() {
        check_ajax_referer('uc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'update-controller')));
            exit;
        }
        
        // Check if file was uploaded
        if (!isset($_FILES['update_file']) || $_FILES['update_file']['error'] !== UPLOAD_ERR_OK) {
            $error_message = isset($_FILES['update_file']) ? self::get_upload_error_message($_FILES['update_file']['error']) : __('No file uploaded', 'update-controller');
            wp_send_json_error(array('message' => $error_message));
            exit;
        }
        
        $file = $_FILES['update_file'];
        $package_name = isset($_POST['package_name']) ? sanitize_text_field($_POST['package_name']) : '';
        $version = isset($_POST['version']) ? sanitize_text_field($_POST['version']) : '';
        
        if (empty($package_name)) {
            wp_send_json_error(array('message' => __('Package name is required', 'update-controller')));
            exit;
        }
        
        // Check file type
        $file_type = wp_check_filetype($file['name']);
        if ($file_type['ext'] !== 'zip') {
            wp_send_json_error(array('message' => __('Only ZIP files are allowed', 'update-controller')));
            exit;
        }
        
        // Check file size (50MB max)
        $max_size = 50 * 1024 * 1024;
        if ($file['size'] > $max_size) {
            wp_send_json_error(array('message' => __('File size exceeds 50MB limit', 'update-controller')));
            exit;
        }
        
        // Ensure upload directory exists with proper permissions
        self::ensure_upload_directory();
        
        $upload_dir = wp_upload_dir();
        $uc_dir = $upload_dir['basedir'] . '/update-controller';
        
        // Generate unique filename
        $filename = sanitize_file_name($file['name']);
        $unique_filename = wp_unique_filename($uc_dir, $filename);
        $upload_path = $uc_dir . '/' . $unique_filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
            wp_send_json_error(array('message' => __('Failed to save uploaded file', 'update-controller')));
            exit;
        }
        
        // Make file publicly accessible
        chmod($upload_path, 0644);
        
        // Generate URL
        $upload_url = $upload_dir['baseurl'] . '/update-controller/' . $unique_filename;
        
        // Save to database
        $result = UC_Database::add_update($package_name, $unique_filename, $upload_path, $upload_url, $file['size'], $version);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Update package uploaded successfully', 'update-controller'),
                'update_id' => $result,
                'file_url' => $upload_url
            ));
        } else {
            // Clean up file if database insert failed
            @unlink($upload_path);
            wp_send_json_error(array('message' => __('Failed to save update package', 'update-controller')));
        }
        exit;
    }
    
    /**
     * AJAX: Delete update package
     */
    public static function ajax_delete_update_package() {
        check_ajax_referer('uc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'update-controller')));
            exit;
        }
        
        $update_id = isset($_POST['update_id']) ? intval($_POST['update_id']) : 0;
        
        if (empty($update_id)) {
            wp_send_json_error(array('message' => __('Invalid update ID', 'update-controller')));
            exit;
        }
        
        $result = UC_Database::delete_update($update_id);
        
        if ($result !== false) {
            wp_send_json_success(array('message' => __('Update package deleted successfully', 'update-controller')));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete update package', 'update-controller')));
        }
        exit;
    }
    
    /**
     * AJAX: Get update packages (for dropdown)
     */
    public static function ajax_get_update_packages() {
        check_ajax_referer('uc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'update-controller')));
            exit;
        }
        
        $updates = UC_Database::get_updates();
        $packages = array();
        
        foreach ($updates as $update) {
            $packages[] = array(
                'id' => $update->id,
                'name' => $update->package_name,
                'file_name' => $update->file_name,
                'file_url' => $update->file_url,
                'version' => $update->version,
                'size' => size_format($update->file_size)
            );
        }
        
        wp_send_json_success(array('packages' => $packages));
        exit;
    }
    
    /**
     * Get upload error message
     */
    private static function get_upload_error_message($error_code) {
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
                return __('The uploaded file exceeds the upload_max_filesize directive in php.ini', 'update-controller');
            case UPLOAD_ERR_FORM_SIZE:
                return __('The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form', 'update-controller');
            case UPLOAD_ERR_PARTIAL:
                return __('The uploaded file was only partially uploaded', 'update-controller');
            case UPLOAD_ERR_NO_FILE:
                return __('No file was uploaded', 'update-controller');
            case UPLOAD_ERR_NO_TMP_DIR:
                return __('Missing a temporary folder', 'update-controller');
            case UPLOAD_ERR_CANT_WRITE:
                return __('Failed to write file to disk', 'update-controller');
            case UPLOAD_ERR_EXTENSION:
                return __('A PHP extension stopped the file upload', 'update-controller');
            default:
                return __('Unknown upload error', 'update-controller');
        }
    }
    
    /**
     * AJAX: Secure backup file download
     */
    public static function ajax_download_backup() {
        $log_id = isset($_GET['log_id']) ? intval($_GET['log_id']) : 0;
        $nonce = isset($_GET['nonce']) ? sanitize_text_field($_GET['nonce']) : '';
        
        if (!wp_verify_nonce($nonce, 'uc_download_backup_' . $log_id)) {
            wp_die(__('Security check failed', 'update-controller'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to download this file', 'update-controller'));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'uc_update_logs';
        $log = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $log_id));
        
        if (!$log || empty($log->backup_file) || !file_exists($log->backup_file)) {
            wp_die(__('Backup file not found', 'update-controller'));
        }
        
        $file_path = $log->backup_file;
        $file_name = basename($file_path);
        
        // Verify the file is in our backups directory
        $upload_dir = wp_upload_dir();
        $allowed_path = $upload_dir['basedir'] . '/update-controller/backups/';
        if (strpos(realpath($file_path), realpath($allowed_path)) !== 0) {
            wp_die(__('Invalid file path', 'update-controller'));
        }
        
        // Serve the file
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $file_name . '"');
        header('Content-Length: ' . filesize($file_path));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        
        readfile($file_path);
        exit;
    }
    
    /**
     * AJAX: Delete backup file
     */
    public static function ajax_delete_backup() {
        $log_id = isset($_POST['log_id']) ? intval($_POST['log_id']) : 0;
        
        // Use per-log nonce for better security
        check_ajax_referer('uc_delete_backup_' . $log_id, 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'update-controller')));
            exit;
        }
        
        if (empty($log_id)) {
            wp_send_json_error(array('message' => __('Invalid log ID', 'update-controller')));
            exit;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'uc_update_logs';
        $log = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $log_id));
        
        if (!$log) {
            wp_send_json_error(array('message' => __('Log not found', 'update-controller')));
            exit;
        }
        
        if (!empty($log->backup_file) && file_exists($log->backup_file)) {
            // Verify the file is in our backups directory using strict path validation
            $upload_dir = wp_upload_dir();
            $allowed_path = realpath($upload_dir['basedir'] . '/update-controller/backups');
            
            // Get real path and ensure it's resolved (no symlinks, relative paths)
            $real_path = realpath($log->backup_file);
            
            // Security checks:
            // 1. Both paths must resolve to real paths
            // 2. Real path must start with allowed path
            // 3. File must have .zip extension
            // 4. Path must not contain null bytes or parent directory traversal
            if ($real_path === false || $allowed_path === false) {
                wp_send_json_error(array('message' => __('Invalid backup file path', 'update-controller')));
                exit;
            }
            
            if (strpos($real_path, $allowed_path . DIRECTORY_SEPARATOR) !== 0) {
                wp_send_json_error(array('message' => __('Backup file is outside allowed directory', 'update-controller')));
                exit;
            }
            
            $file_ext = strtolower(pathinfo($real_path, PATHINFO_EXTENSION));
            if ($file_ext !== 'zip') {
                wp_send_json_error(array('message' => __('Invalid backup file type', 'update-controller')));
                exit;
            }
            
            $deleted = unlink($real_path);
            
            if ($deleted) {
                // Update the log to remove backup_file reference
                $wpdb->update(
                    $table,
                    array('backup_file' => ''),
                    array('id' => $log_id),
                    array('%s'),
                    array('%d')
                );
                
                wp_send_json_success(array('message' => __('Backup deleted successfully', 'update-controller')));
            } else {
                wp_send_json_error(array('message' => __('Failed to delete backup file', 'update-controller')));
            }
        } else {
            wp_send_json_error(array('message' => __('Backup file not found', 'update-controller')));
        }
        exit;
    }
    
    /**
     * AJAX: Check companion plugin status on a site
     */
    public static function ajax_check_companion() {
        check_ajax_referer('uc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'update-controller')));
            exit;
        }
        
        $site_id = isset($_POST['site_id']) ? intval($_POST['site_id']) : 0;
        
        if (empty($site_id)) {
            wp_send_json_error(array('message' => __('Invalid site ID', 'update-controller')));
            exit;
        }
        
        $site = UC_Database::get_site($site_id);
        
        if (!$site) {
            wp_send_json_error(array('message' => __('Site not found', 'update-controller')));
            exit;
        }
        
        // Get local companion plugin info
        $local_companion_file = UPDATE_CONTROLLER_PLUGIN_DIR . 'companion-plugin/update-controller-companion.php';
        $local_version = 'N/A';
        $local_size = 0;
        
        if (file_exists($local_companion_file)) {
            $local_content = file_get_contents($local_companion_file);
            $local_size = filesize($local_companion_file);
            preg_match('/Version:\s*([0-9.]+)/i', $local_content, $matches);
            $local_version = isset($matches[1]) ? $matches[1] : 'N/A';
        }
        
        // Test connection to remote site
        $site_url = rtrim($site->site_url, '/');
        $test_url = $site_url . '/wp-json/uc-companion/v1/test';
        $test_response = wp_remote_get($test_url, array('timeout' => 15));
        
        if (is_wp_error($test_response)) {
            wp_send_json_error(array(
                'message' => sprintf(__('Connection failed: %s', 'update-controller'), $test_response->get_error_message()),
                'site_name' => $site->site_name,
                'local_version' => $local_version,
                'local_size' => $local_size,
                'remote_version' => 'N/A',
                'remote_size' => 0,
                'status' => 'error'
            ));
            exit;
        }
        
        $test_code = wp_remote_retrieve_response_code($test_response);
        $test_body = wp_remote_retrieve_body($test_response);
        
        if ($test_code !== 200) {
            wp_send_json_error(array(
                'message' => sprintf(__('Companion plugin not responding (HTTP %d)', 'update-controller'), $test_code),
                'site_name' => $site->site_name,
                'local_version' => $local_version,
                'local_size' => $local_size,
                'remote_version' => 'N/A',
                'remote_size' => 0,
                'status' => 'not_installed'
            ));
            exit;
        }
        
        $test_data = json_decode($test_body, true);
        
        $remote_version = isset($test_data['version']) && !empty($test_data['version']) ? $test_data['version'] : 'unknown';
        $remote_size = isset($test_data['file_size']) ? intval($test_data['file_size']) : 0;
        
        // Determine if update is needed
        $needs_update = false;
        $status = 'ok';
        
        if ($remote_version === 'unknown' || $remote_version === 'N/A') {
            $needs_update = true;
            $status = 'outdated';
        } elseif (version_compare($local_version, $remote_version, '>')) {
            $needs_update = true;
            $status = 'outdated';
        } elseif ($local_size != $remote_size && version_compare($local_version, $remote_version, '=')) {
            $needs_update = true;
            $status = 'size_mismatch';
        }
        
        wp_send_json_success(array(
            'site_name' => $site->site_name,
            'local_version' => $local_version,
            'local_size' => $local_size,
            'remote_version' => $remote_version,
            'remote_size' => $remote_size,
            'needs_update' => $needs_update,
            'status' => $status
        ));
        exit;
    }
}
