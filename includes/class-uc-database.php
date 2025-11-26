<?php
/**
 * Database management class
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class UC_Database {
    
    /**
     * Create database tables
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Define table names directly
        $sites_table = $wpdb->prefix . 'uc_sites';
        $plugins_table = $wpdb->prefix . 'uc_plugins';
        
        // Sites table
        $sites_sql = "CREATE TABLE $sites_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            site_url varchar(255) NOT NULL,
            site_name varchar(255) NOT NULL,
            username varchar(100) NOT NULL,
            password text NOT NULL,
            status varchar(20) DEFAULT 'active',
            last_update datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY site_url (site_url)
        ) $charset_collate;";
        
        // Plugins table
        $plugins_sql = "CREATE TABLE $plugins_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            site_id bigint(20) UNSIGNED NOT NULL,
            plugin_slug varchar(255) NOT NULL,
            plugin_name varchar(255) NOT NULL,
            update_source varchar(500) NOT NULL,
            source_type varchar(20) DEFAULT 'web',
            auto_update tinyint(1) DEFAULT 1,
            last_update datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY site_id (site_id)
        ) $charset_collate;";
        
        // Updates (packages) table
        $updates_table = $wpdb->prefix . 'uc_updates';
        $updates_sql = "CREATE TABLE $updates_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            package_name varchar(255) NOT NULL,
            file_name varchar(255) NOT NULL,
            file_path varchar(500) NOT NULL,
            file_url varchar(500) NOT NULL,
            file_size bigint(20) DEFAULT 0,
            version varchar(50) DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        // Update logs table
        $logs_table = $wpdb->prefix . 'uc_update_logs';
        $logs_sql = "CREATE TABLE $logs_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            site_id bigint(20) UNSIGNED NOT NULL,
            plugin_id bigint(20) UNSIGNED NOT NULL,
            plugin_name varchar(255) NOT NULL,
            from_version varchar(50) DEFAULT '',
            to_version varchar(50) DEFAULT '',
            status varchar(20) DEFAULT 'success',
            message text,
            backup_file varchar(500) DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY site_id (site_id),
            KEY plugin_id (plugin_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $result_sites = dbDelta($sites_sql);
        $result_plugins = dbDelta($plugins_sql);
        $result_updates = dbDelta($updates_sql);
        $result_logs = dbDelta($logs_sql);
        
        // Log results for debugging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Update Controller: Sites table creation result - ' . print_r($result_sites, true));
            error_log('Update Controller: Plugins table creation result - ' . print_r($result_plugins, true));
            error_log('Update Controller: Updates table creation result - ' . print_r($result_updates, true));
            error_log('Update Controller: Logs table creation result - ' . print_r($result_logs, true));
        }
        
        // Verify tables were created
        $sites_exists = $wpdb->get_var("SHOW TABLES LIKE '$sites_table'") == $sites_table;
        $plugins_exists = $wpdb->get_var("SHOW TABLES LIKE '$plugins_table'") == $plugins_table;
        $updates_exists = $wpdb->get_var("SHOW TABLES LIKE '$updates_table'") == $updates_table;
        $logs_exists = $wpdb->get_var("SHOW TABLES LIKE '$logs_table'") == $logs_table;
        
        if (!$sites_exists || !$plugins_exists || !$updates_exists || !$logs_exists) {
            error_log('Update Controller: Table creation failed. Sites exists: ' . ($sites_exists ? 'yes' : 'no') . ', Plugins exists: ' . ($plugins_exists ? 'yes' : 'no') . ', Updates exists: ' . ($updates_exists ? 'yes' : 'no') . ', Logs exists: ' . ($logs_exists ? 'yes' : 'no'));
        }
    }
    
    /**
     * Get all sites
     */
    public static function get_sites() {
        global $wpdb;
        $controller = Update_Controller::get_instance();
        $table = $controller->get_sites_table();
        
        return $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC");
    }
    
    /**
     * Get site by ID
     */
    public static function get_site($site_id) {
        global $wpdb;
        $controller = Update_Controller::get_instance();
        $table = $controller->get_sites_table();
        
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $site_id));
    }
    
    /**
     * Add new site
     */
    public static function add_site($site_url, $site_name, $username, $password) {
        global $wpdb;
        $controller = Update_Controller::get_instance();
        $table = $controller->get_sites_table();
        
        // Encrypt password
        $encrypted_password = UC_Encryption::encrypt($password);
        
        $result = $wpdb->insert(
            $table,
            array(
                'site_url' => esc_url_raw($site_url),
                'site_name' => sanitize_text_field($site_name),
                'username' => sanitize_text_field($username),
                'password' => $encrypted_password,
                'status' => 'active'
            ),
            array('%s', '%s', '%s', '%s', '%s')
        );
        
        return $result !== false ? $wpdb->insert_id : false;
    }
    
    /**
     * Update site
     */
    public static function update_site($site_id, $site_url, $site_name, $username, $password = null) {
        global $wpdb;
        $controller = Update_Controller::get_instance();
        $table = $controller->get_sites_table();
        
        $data = array(
            'site_url' => esc_url_raw($site_url),
            'site_name' => sanitize_text_field($site_name),
            'username' => sanitize_text_field($username)
        );
        
        $format = array('%s', '%s', '%s');
        
        // Only update password if provided
        if (!empty($password)) {
            $data['password'] = UC_Encryption::encrypt($password);
            $format[] = '%s';
        }
        
        return $wpdb->update(
            $table,
            $data,
            array('id' => $site_id),
            $format,
            array('%d')
        );
    }
    
    /**
     * Delete site
     */
    public static function delete_site($site_id) {
        global $wpdb;
        $controller = Update_Controller::get_instance();
        
        // Delete associated plugins first
        $plugins_table = $controller->get_plugins_table();
        $wpdb->delete($plugins_table, array('site_id' => $site_id), array('%d'));
        
        // Delete site
        $sites_table = $controller->get_sites_table();
        return $wpdb->delete($sites_table, array('id' => $site_id), array('%d'));
    }
    
    /**
     * Get all plugins
     */
    public static function get_plugins($site_id = null) {
        global $wpdb;
        $controller = Update_Controller::get_instance();
        $table = $controller->get_plugins_table();
        
        if ($site_id) {
            return $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE site_id = %d ORDER BY created_at DESC", $site_id));
        }
        
        return $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC");
    }
    
    /**
     * Get plugin by ID
     */
    public static function get_plugin($plugin_id) {
        global $wpdb;
        $controller = Update_Controller::get_instance();
        $table = $controller->get_plugins_table();
        
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $plugin_id));
    }
    
    /**
     * Add new plugin
     */
    public static function add_plugin($site_id, $plugin_slug, $plugin_name, $update_source, $source_type = 'web', $auto_update = 1) {
        global $wpdb;
        $controller = Update_Controller::get_instance();
        $table = $controller->get_plugins_table();
        
        $result = $wpdb->insert(
            $table,
            array(
                'site_id' => intval($site_id),
                'plugin_slug' => sanitize_text_field($plugin_slug),
                'plugin_name' => sanitize_text_field($plugin_name),
                'update_source' => esc_url_raw($update_source),
                'source_type' => sanitize_text_field($source_type),
                'auto_update' => intval($auto_update)
            ),
            array('%d', '%s', '%s', '%s', '%s', '%d')
        );
        
        return $result !== false ? $wpdb->insert_id : false;
    }
    
    /**
     * Update plugin
     */
    public static function update_plugin($plugin_id, $plugin_slug, $plugin_name, $update_source, $source_type = 'web', $auto_update = 1) {
        global $wpdb;
        $controller = Update_Controller::get_instance();
        $table = $controller->get_plugins_table();
        
        return $wpdb->update(
            $table,
            array(
                'plugin_slug' => sanitize_text_field($plugin_slug),
                'plugin_name' => sanitize_text_field($plugin_name),
                'update_source' => esc_url_raw($update_source),
                'source_type' => sanitize_text_field($source_type),
                'auto_update' => intval($auto_update)
            ),
            array('id' => $plugin_id),
            array('%s', '%s', '%s', '%s', '%d'),
            array('%d')
        );
    }
    
    /**
     * Delete plugin
     */
    public static function delete_plugin($plugin_id) {
        global $wpdb;
        $controller = Update_Controller::get_instance();
        $table = $controller->get_plugins_table();
        
        return $wpdb->delete($table, array('id' => $plugin_id), array('%d'));
    }
    
    /**
     * Update last update time for site
     */
    public static function update_site_last_update($site_id) {
        global $wpdb;
        $controller = Update_Controller::get_instance();
        $table = $controller->get_sites_table();
        
        return $wpdb->update(
            $table,
            array('last_update' => current_time('mysql')),
            array('id' => $site_id),
            array('%s'),
            array('%d')
        );
    }
    
    /**
     * Update last update time for plugin
     */
    public static function update_plugin_last_update($plugin_id) {
        global $wpdb;
        $controller = Update_Controller::get_instance();
        $table = $controller->get_plugins_table();
        
        return $wpdb->update(
            $table,
            array('last_update' => current_time('mysql')),
            array('id' => $plugin_id),
            array('%s'),
            array('%d')
        );
    }
    
    /**
     * Get all update packages
     */
    public static function get_updates() {
        global $wpdb;
        $controller = Update_Controller::get_instance();
        $table = $controller->get_updates_table();
        
        return $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC");
    }
    
    /**
     * Get update package by ID
     */
    public static function get_update($update_id) {
        global $wpdb;
        $controller = Update_Controller::get_instance();
        $table = $controller->get_updates_table();
        
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $update_id));
    }
    
    /**
     * Add new update package
     */
    public static function add_update($package_name, $file_name, $file_path, $file_url, $file_size = 0, $version = '') {
        global $wpdb;
        $controller = Update_Controller::get_instance();
        $table = $controller->get_updates_table();
        
        $result = $wpdb->insert(
            $table,
            array(
                'package_name' => sanitize_text_field($package_name),
                'file_name' => sanitize_file_name($file_name),
                'file_path' => sanitize_text_field($file_path),
                'file_url' => esc_url_raw($file_url),
                'file_size' => intval($file_size),
                'version' => sanitize_text_field($version)
            ),
            array('%s', '%s', '%s', '%s', '%d', '%s')
        );
        
        return $result !== false ? $wpdb->insert_id : false;
    }
    
    /**
     * Update existing update package
     */
    public static function update_update($update_id, $package_name, $version = '') {
        global $wpdb;
        $controller = Update_Controller::get_instance();
        $table = $controller->get_updates_table();
        
        return $wpdb->update(
            $table,
            array(
                'package_name' => sanitize_text_field($package_name),
                'version' => sanitize_text_field($version)
            ),
            array('id' => $update_id),
            array('%s', '%s'),
            array('%d')
        );
    }
    
    /**
     * Delete update package
     */
    public static function delete_update($update_id) {
        global $wpdb;
        $controller = Update_Controller::get_instance();
        $table = $controller->get_updates_table();
        
        // Get file path to delete
        $update = self::get_update($update_id);
        if ($update && !empty($update->file_path) && file_exists($update->file_path)) {
            @unlink($update->file_path);
        }
        
        return $wpdb->delete($table, array('id' => $update_id), array('%d'));
    }
    
    /**
     * Add update log entry
     */
    public static function add_update_log($site_id, $plugin_id, $plugin_name, $from_version, $to_version, $status = 'success', $message = '', $backup_file = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'uc_update_logs';
        
        $result = $wpdb->insert(
            $table,
            array(
                'site_id' => intval($site_id),
                'plugin_id' => intval($plugin_id),
                'plugin_name' => sanitize_text_field($plugin_name),
                'from_version' => sanitize_text_field($from_version),
                'to_version' => sanitize_text_field($to_version),
                'status' => sanitize_text_field($status),
                'message' => sanitize_text_field($message),
                'backup_file' => sanitize_text_field($backup_file)
            ),
            array('%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        return $result !== false ? $wpdb->insert_id : false;
    }
    
    /**
     * Get all update logs
     */
    public static function get_update_logs($site_id = null, $limit = 100) {
        global $wpdb;
        $table = $wpdb->prefix . 'uc_update_logs';
        
        if ($site_id) {
            return $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table WHERE site_id = %d ORDER BY created_at DESC LIMIT %d",
                $site_id,
                $limit
            ));
        }
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table ORDER BY created_at DESC LIMIT %d",
            $limit
        ));
    }
    
    /**
     * Get update logs with site and plugin names
     */
    public static function get_update_logs_with_details($limit = 100) {
        global $wpdb;
        $logs_table = $wpdb->prefix . 'uc_update_logs';
        $sites_table = $wpdb->prefix . 'uc_sites';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT l.*, s.site_name 
             FROM $logs_table l 
             LEFT JOIN $sites_table s ON l.site_id = s.id 
             ORDER BY l.created_at DESC 
             LIMIT %d",
            $limit
        ));
    }
    
    /**
     * Get backups for a site
     */
    public static function get_site_backups($site_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'uc_update_logs';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE site_id = %d AND backup_file != '' ORDER BY created_at DESC",
            $site_id
        ));
    }
    
    /**
     * Delete update log
     */
    public static function delete_update_log($log_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'uc_update_logs';
        
        // Get backup file path to delete
        $log = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $log_id));
        if ($log && !empty($log->backup_file) && file_exists($log->backup_file)) {
            $deleted = unlink($log->backup_file);
            if (!$deleted && defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Update Controller: Failed to delete backup file: ' . $log->backup_file);
            }
        }
        
        return $wpdb->delete($table, array('id' => $log_id), array('%d'));
    }
    
    /**
     * Update site status
     */
    public static function update_site_status($site_id, $status) {
        global $wpdb;
        $controller = Update_Controller::get_instance();
        $table = $controller->get_sites_table();
        
        return $wpdb->update(
            $table,
            array('status' => sanitize_text_field($status)),
            array('id' => $site_id),
            array('%s'),
            array('%d')
        );
    }
    
    /**
     * Clear backup file from log entry
     */
    public static function clear_log_backup($log_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'uc_update_logs';
        
        return $wpdb->update(
            $table,
            array('backup_file' => ''),
            array('id' => $log_id),
            array('%s'),
            array('%d')
        );
    }
}
