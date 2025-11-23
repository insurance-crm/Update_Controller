<?php
/**
 * Uninstall script
 * Fired when the plugin is uninstalled
 */

// Exit if accessed directly or not in uninstall
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Define table names
$sites_table = $wpdb->prefix . 'uc_sites';
$plugins_table = $wpdb->prefix . 'uc_plugins';

// Drop tables
$wpdb->query("DROP TABLE IF EXISTS $plugins_table");
$wpdb->query("DROP TABLE IF EXISTS $sites_table");

// Clear scheduled events
wp_clear_scheduled_hook('uc_scheduled_update');

// Delete options (if any were added in the future)
delete_option('update_controller_version');
