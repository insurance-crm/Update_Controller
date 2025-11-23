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
        $controller = Update_Controller::get_instance();
        
        // Sites table
        $sites_table = $controller->get_sites_table();
        $sites_sql = "CREATE TABLE IF NOT EXISTS $sites_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            site_url varchar(255) NOT NULL,
            site_name varchar(255) NOT NULL,
            username varchar(100) NOT NULL,
            password text NOT NULL,
            status varchar(20) DEFAULT 'active',
            last_update datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY site_url (site_url)
        ) $charset_collate;";
        
        // Plugins table
        $plugins_table = $controller->get_plugins_table();
        $plugins_sql = "CREATE TABLE IF NOT EXISTS $plugins_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            site_id bigint(20) UNSIGNED NOT NULL,
            plugin_slug varchar(255) NOT NULL,
            plugin_name varchar(255) NOT NULL,
            update_source varchar(500) NOT NULL,
            source_type varchar(20) DEFAULT 'web',
            auto_update tinyint(1) DEFAULT 1,
            last_update datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY site_id (site_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sites_sql);
        dbDelta($plugins_sql);
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
}
