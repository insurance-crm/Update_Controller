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
            exit;
        }
        
        $plugin_id = isset($_POST['plugin_id']) ? intval($_POST['plugin_id']) : 0;
        
        if (empty($plugin_id)) {
            wp_send_json_error(array('message' => __('Invalid plugin ID', 'update-controller')));
            exit;
        }
        
        // Increase time limit for long-running updates
        $timeout = apply_filters('uc_update_timeout', 300); // 5 minutes default, filterable
        set_time_limit($timeout);
        
        // Log the update attempt
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf('Update Controller: Manual update requested for plugin ID: %d', absint($plugin_id)));
        }
        
        $result = self::update_plugin($plugin_id);
        
        // Log the result with error message for debugging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            if ($result['success']) {
                error_log('Update Controller: Manual update completed with status: success');
            } else {
                // Log error message without sensitive data
                $error_msg = isset($result['message']) ? $result['message'] : 'Unknown error';
                error_log('Update Controller: Manual update failed - ' . sanitize_text_field($error_msg));
            }
        }
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
        exit;
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
        // Include WordPress file functions
        if (!function_exists('download_url')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        
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
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Update Controller: Step 0 - Downloading plugin from ' . $plugin->update_source);
        }
        
        $plugin_file = self::download_plugin($plugin->update_source, $plugin->source_type);
        
        if (is_wp_error($plugin_file)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Update Controller: Step 0 FAILED - Download error: ' . $plugin_file->get_error_message());
            }
            return array(
                'success' => false,
                'message' => $plugin_file->get_error_message()
            );
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Update Controller: Step 0 SUCCESS - Plugin downloaded to ' . $plugin_file);
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
        
        // Handle Google Drive URLs
        if (strpos($source_url, 'drive.google.com') !== false) {
            $source_url = self::convert_google_drive_url($source_url);
            if (is_wp_error($source_url)) {
                return $source_url;
            }
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Update Controller: Downloading from URL: ' . $source_url);
        }
        
        // Use wp_remote_get with proper headers to avoid "Forbidden" errors
        // Some servers block requests without User-Agent or other headers
        $temp_file = self::download_file_with_headers($source_url);
        
        if (is_wp_error($temp_file)) {
            return $temp_file;
        }
        
        // Validate that we downloaded a ZIP file
        $file_type = wp_check_filetype($temp_file);
        $file_size = filesize($temp_file);
        
        // Check if file is actually a ZIP (not HTML or other format)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $temp_file);
        finfo_close($finfo);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Update Controller: Downloaded file mime type: ' . $mime_type . ', size: ' . $file_size . ' bytes');
        }
        
        // Validate it's a ZIP file
        if ($mime_type !== 'application/zip' && $file_type['ext'] !== 'zip') {
            @unlink($temp_file);
            
            // Check if it's an HTML file (common with Google Drive sharing links)
            if (strpos($mime_type, 'text/html') !== false || $file_size < 1000) {
                return new WP_Error(
                    'invalid_source',
                    __('Download failed: Source URL returned an HTML page instead of a ZIP file. If using Google Drive, make sure to use a direct download link. See documentation for proper URL formats.', 'update-controller')
                );
            }
            
            return new WP_Error(
                'invalid_file_type',
                sprintf(__('Download failed: Expected ZIP file but got %s. Please check the source URL.', 'update-controller'), $mime_type)
            );
        }
        
        return $temp_file;
    }
    
    /**
     * Download file with proper HTTP headers
     * 
     * Some servers block requests without proper User-Agent or Referer headers.
     * This method uses wp_remote_get with customized headers to avoid "Forbidden" errors.
     * 
     * Common causes of 403 Forbidden:
     * - Hotlink protection (Referer must match source domain)
     * - User-Agent filtering (non-browser requests blocked)
     * - Security plugins (Wordfence, Sucuri, etc.)
     * - Server mod_security rules
     * 
     * @param string $url URL to download
     * @return string|WP_Error Path to temporary file or error
     */
    private static function download_file_with_headers($url) {
        // Parse URL to get the source domain for Referer header
        $parsed_url = wp_parse_url($url);
        $source_domain = isset($parsed_url['scheme']) && isset($parsed_url['host']) 
            ? $parsed_url['scheme'] . '://' . $parsed_url['host'] 
            : '';
        
        // Prepare headers to mimic a real browser request
        // Using a common browser User-Agent to avoid being blocked by security plugins
        $headers = array(
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'Accept-Language' => 'en-US,en;q=0.9',
            'Accept-Encoding' => 'gzip, deflate, br',
            'Connection' => 'keep-alive',
            'Upgrade-Insecure-Requests' => '1',
            // Use source domain as Referer to bypass hotlink protection
            'Referer' => $source_domain ? $source_domain . '/' : home_url(),
            'Sec-Fetch-Dest' => 'document',
            'Sec-Fetch-Mode' => 'navigate',
            'Sec-Fetch-Site' => 'same-origin',
            'Sec-Fetch-User' => '?1',
        );
        
        // Allow filtering of download headers
        $headers = apply_filters('uc_download_headers', $headers, $url);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Update Controller: Downloading with browser-like headers from: ' . $url);
            error_log('Update Controller: Referer set to: ' . $headers['Referer']);
        }
        
        // Make the request
        $response = wp_remote_get($url, array(
            'headers' => $headers,
            'timeout' => apply_filters('uc_download_timeout', 300), // 5 minutes timeout
            'stream' => true,
            'filename' => wp_tempnam($url),
            'sslverify' => apply_filters('uc_download_sslverify', true),
            'redirection' => 5, // Follow up to 5 redirects
        ));
        
        if (is_wp_error($response)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Update Controller: Download request failed - ' . $response->get_error_message());
            }
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $temp_file = $response['filename'];
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Update Controller: Download response code: ' . $response_code);
        }
        
        // Handle non-200 response codes
        if ($response_code !== 200) {
            @unlink($temp_file);
            
            $error_message = '';
            switch ($response_code) {
                case 403:
                    $error_message = __('Forbidden - The server denied access to the file. Check if the file URL is publicly accessible or requires authentication.', 'update-controller');
                    break;
                case 404:
                    $error_message = __('Not Found - The file does not exist at the specified URL. Please check the URL is correct.', 'update-controller');
                    break;
                case 401:
                    $error_message = __('Unauthorized - The server requires authentication to access this file.', 'update-controller');
                    break;
                case 500:
                case 502:
                case 503:
                    $error_message = __('Server Error - The source server encountered an error. Please try again later.', 'update-controller');
                    break;
                default:
                    $error_message = sprintf(__('Download failed with HTTP status code %d', 'update-controller'), $response_code);
            }
            
            return new WP_Error('download_failed', $error_message);
        }
        
        // Verify the file was created
        if (!file_exists($temp_file) || filesize($temp_file) === 0) {
            @unlink($temp_file);
            return new WP_Error('download_failed', __('Downloaded file is empty or was not created', 'update-controller'));
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
     * Convert Google Drive sharing URL to direct download URL
     */
    private static function convert_google_drive_url($url) {
        // Google Drive sharing links look like:
        // https://drive.google.com/file/d/FILE_ID/view?usp=sharing
        // Direct download should be:
        // https://drive.google.com/uc?export=download&id=FILE_ID
        
        if (preg_match('#drive\.google\.com/file/d/([^/]+)#', $url, $matches)) {
            $file_id = $matches[1];
            return 'https://drive.google.com/uc?export=download&id=' . $file_id;
        }
        
        // If already a direct download link, return as is
        if (strpos($url, 'drive.google.com/uc?') !== false) {
            return $url;
        }
        
        // If we can't parse it, return an error
        return new WP_Error(
            'invalid_google_drive_url',
            __('Invalid Google Drive URL format. Please use: https://drive.google.com/file/d/FILE_ID/view or get a direct download link.', 'update-controller')
        );
    }
    
    /**
     * Install plugin on remote WordPress site
     */
    private static function install_remote_plugin($site, $plugin, $plugin_file) {
        // Decrypt password
        $password = UC_Encryption::decrypt($site->password);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Update Controller: Step 1 - Starting authentication to ' . $site->site_url);
        }
        
        // Authenticate with WordPress site
        $auth_result = self::authenticate_site($site->site_url, $site->username, $password);
        
        if (is_wp_error($auth_result)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Update Controller: Step 1 FAILED - Authentication error: ' . $auth_result->get_error_message());
            }
            return array(
                'success' => false,
                'message' => __('Authentication failed: ', 'update-controller') . $auth_result->get_error_message()
            );
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Update Controller: Step 1 SUCCESS - Authentication successful');
        }
        
        $cookie = $auth_result;
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Update Controller: Step 2 - Starting file upload');
        }
        
        // Upload plugin file
        $upload_result = self::upload_plugin_file($site->site_url, $plugin_file, $cookie);
        
        if (is_wp_error($upload_result)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Update Controller: Step 2 FAILED - Upload error: ' . $upload_result->get_error_message());
            }
            return array(
                'success' => false,
                'message' => __('Upload failed: ', 'update-controller') . $upload_result->get_error_message()
            );
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Update Controller: Step 2 SUCCESS - File uploaded');
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Update Controller: Step 3 - Deactivating plugin');
        }
        
        // Deactivate plugin before update
        self::toggle_plugin($site->site_url, $plugin->plugin_slug, 'deactivate', $cookie);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Update Controller: Step 4 - Installing plugin');
        }
        
        // Install/update plugin
        $install_result = self::install_plugin_from_upload($site->site_url, $upload_result, $cookie);
        
        if (is_wp_error($install_result)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Update Controller: Step 4 FAILED - Installation error: ' . $install_result->get_error_message());
            }
            return array(
                'success' => false,
                'message' => __('Installation failed: ', 'update-controller') . $install_result->get_error_message()
            );
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Update Controller: Step 4 SUCCESS - Plugin installed');
            error_log('Update Controller: Step 5 - Reactivating plugin');
        }
        
        // Reactivate plugin
        self::toggle_plugin($site->site_url, $plugin->plugin_slug, 'activate', $cookie);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Update Controller: All steps completed successfully');
        }
        
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
        
        // Log authentication attempt for debugging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Update Controller: Attempting authentication to ' . $site_url);
        }
        
        // Try to authenticate using WordPress REST API with Application Passwords
        $response = wp_remote_post($site_url . '/wp-json/wp/v2/users/me', array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($username . ':' . $password)
            ),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Update Controller: Authentication request error - ' . $response->get_error_message());
            }
            return $response;
        }
        
        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Update Controller: Authentication response code: ' . $code);
        }
        
        if ($code === 200) {
            // Return authentication credentials for further requests
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Update Controller: Authentication successful');
            }
            return array(
                'Authorization' => 'Basic ' . base64_encode($username . ':' . $password)
            );
        }
        
        // Handle specific error codes
        $error_message = '';
        switch ($code) {
            case 403:
                $error_message = __('Access forbidden (403). Please verify: 1) Companion plugin is installed and activated on target site, 2) Application Passwords are enabled in WordPress 5.6+, 3) Username is correct.', 'update-controller');
                break;
            case 401:
                $error_message = __('Invalid credentials (401). Please check the username and Application Password. Make sure to copy the password with all spaces.', 'update-controller');
                break;
            case 404:
                $error_message = __('REST API not found (404). Please ensure: 1) WordPress REST API is enabled, 2) Site URL is correct and includes /wp-json path.', 'update-controller');
                break;
            case 0:
                $error_message = __('Connection failed. Please check: 1) Site URL is accessible, 2) SSL certificate is valid, 3) Server can connect to target site.', 'update-controller');
                break;
            default:
                $error_message = sprintf(__('Authentication failed with HTTP code %d. Response: %s', 'update-controller'), $code, substr($body, 0, 200));
                break;
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Update Controller: Authentication failed - ' . $error_message);
        }
        
        return new WP_Error('auth_failed', $error_message);
    }
    
    /**
     * Upload plugin file to remote site
     * 
     * Uploads the plugin ZIP file directly to companion plugin endpoint,
     * bypassing media library permission issues.
     */
    private static function upload_plugin_file($site_url, $file_path, $auth_headers) {
        $site_url = rtrim($site_url, '/');
        
        // Read file content
        $file_content = file_get_contents($file_path);
        $file_name = basename($file_path);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Update Controller: Uploading plugin file to ' . $site_url . ' via companion plugin');
        }
        
        // Try new method first: Upload via companion plugin (bypasses media library)
        $response = wp_remote_post($site_url . '/wp-json/uc-companion/v1/upload-plugin', array(
            'headers' => array_merge($auth_headers, array(
                'Content-Type' => 'application/zip'
            )),
            'body' => $file_content,
            'timeout' => 60
        ));
        
        if (!is_wp_error($response)) {
            $code = wp_remote_retrieve_response_code($response);
            $body_text = wp_remote_retrieve_body($response);
            $body = json_decode($body_text, true);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Update Controller: Companion upload response code: ' . $code);
                if ($code !== 200) {
                    error_log('Update Controller: Companion upload failed - ' . substr($body_text, 0, 200));
                }
            }
            
            if ($code === 200 && isset($body['file_id'])) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Update Controller: Upload via companion successful, file ID: ' . $body['file_id']);
                }
                return array('id' => $body['file_id'], 'method' => 'companion');
            }
        } else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Update Controller: Companion upload request error - ' . $response->get_error_message());
            }
        }
        
        // Fallback to media library method
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Update Controller: Companion upload unavailable, falling back to media library');
        }
        
        $response = wp_remote_post($site_url . '/wp-json/wp/v2/media', array(
            'headers' => array_merge($auth_headers, array(
                'Content-Disposition' => 'attachment; filename=' . $file_name,
                'Content-Type' => 'application/zip'
            )),
            'body' => $file_content,
            'timeout' => 60
        ));
        
        if (is_wp_error($response)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Update Controller: Upload failed - ' . $response->get_error_message());
            }
            return $response;
        }
        
        $code = wp_remote_retrieve_response_code($response);
        $body_text = wp_remote_retrieve_body($response);
        $body = json_decode($body_text, true);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Update Controller: Media library upload response code: ' . $code);
            error_log('Update Controller: Upload response: ' . substr($body_text, 0, 500));
        }
        
        if ($code !== 201 && $code !== 200) {
            $error_msg = isset($body['message']) ? $body['message'] : 'HTTP ' . $code;
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Update Controller: Media library upload failed with code ' . $code . ': ' . $error_msg);
                if ($code === 403) {
                    error_log('Update Controller: 403 Forbidden - Check user has upload_files capability');
                }
            }
            return new WP_Error('upload_failed', __('Failed to upload plugin file: ', 'update-controller') . $error_msg);
        }
        
        if (isset($body['id'])) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Update Controller: Media library upload successful, media ID: ' . $body['id']);
            }
            return array('id' => $body['id'], 'method' => 'media');
        }
        
        return new WP_Error('upload_failed', __('Failed to upload plugin file - no file ID returned', 'update-controller'));
    }
    
    /**
     * Install plugin from uploaded file
     * 
     * Uses the Update Controller Companion plugin's REST API endpoint
     * to install/update the plugin on the remote site.
     */
    private static function install_plugin_from_upload($site_url, $upload_data, $auth_headers) {
        $site_url = rtrim($site_url, '/');
        
        if (!isset($upload_data['id'])) {
            return new WP_Error('invalid_upload', __('Invalid upload data', 'update-controller'));
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Update Controller: Installing plugin from media ID: ' . $upload_data['id']);
        }
        
        // Call the companion plugin's install endpoint
        $response = wp_remote_post($site_url . '/wp-json/uc-companion/v1/install-plugin', array(
            'headers' => $auth_headers,
            'body' => array(
                'file_id' => $upload_data['id']
            ),
            'timeout' => 120
        ));
        
        if (is_wp_error($response)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Update Controller: Install request failed - ' . $response->get_error_message());
            }
            return $response;
        }
        
        $code = wp_remote_retrieve_response_code($response);
        $body_text = wp_remote_retrieve_body($response);
        $body = json_decode($body_text, true);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Update Controller: Install response code: ' . $code);
            error_log('Update Controller: Install response: ' . substr($body_text, 0, 500));
        }
        
        if ($code !== 200) {
            $message = isset($body['message']) ? $body['message'] : __('Plugin installation failed with HTTP ', 'update-controller') . $code;
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Update Controller: Installation failed: ' . $message);
            }
            return new WP_Error('install_failed', $message);
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Update Controller: Plugin installed successfully');
        }
        
        return $body;
    }
    
    /**
     * Toggle plugin activation status
     * 
     * Uses the Update Controller Companion plugin's REST API endpoints
     * to activate or deactivate the plugin on the remote site.
     */
    private static function toggle_plugin($site_url, $plugin_slug, $action, $auth_headers) {
        $site_url = rtrim($site_url, '/');
        
        $endpoint = $action === 'activate' ? 'activate-plugin' : 'deactivate-plugin';
        
        $response = wp_remote_post($site_url . '/wp-json/uc-companion/v1/' . $endpoint, array(
            'headers' => $auth_headers,
            'body' => array(
                'plugin_slug' => $plugin_slug
            ),
            'timeout' => 60
        ));
        
        if (is_wp_error($response)) {
            // Log error but don't fail the update
            error_log('Update Controller: Plugin ' . $action . ' failed: ' . $response->get_error_message());
            return false;
        }
        
        $code = wp_remote_retrieve_response_code($response);
        
        return $code === 200;
    }
}
