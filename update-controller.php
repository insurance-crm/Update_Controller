<?php
/**
 * Plugin Name: Update Controller
 * Plugin URI: https://github.com/insurance-crm/Update_Controller
 * Description: Manages automatic updates for plugins across multiple WordPress sites from specified web or GitHub repository sources.
 * Version: 1.0.0
 * Author: Insurance CRM
 * Author URI: https://github.com/insurance-crm
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: update-controller
 * Domain Path: /languages
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('UPDATE_CONTROLLER_VERSION', '1.0.0');
define('UPDATE_CONTROLLER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('UPDATE_CONTROLLER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('UPDATE_CONTROLLER_PLUGIN_FILE', __FILE__);

/**
 * Main Update Controller Class
 */
class Update_Controller {
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Database table names
     */
    private $sites_table;
    private $plugins_table;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        global $wpdb;
        $this->sites_table = $wpdb->prefix . 'uc_sites';
        $this->plugins_table = $wpdb->prefix . 'uc_plugins';
        
        // Include required files
        $this->include_files();
        
        // Initialize hooks
        $this->init_hooks();
    }
    
    /**
     * Include required files
     */
    private function include_files() {
        require_once UPDATE_CONTROLLER_PLUGIN_DIR . 'includes/class-uc-database.php';
        require_once UPDATE_CONTROLLER_PLUGIN_DIR . 'includes/class-uc-admin.php';
        require_once UPDATE_CONTROLLER_PLUGIN_DIR . 'includes/class-uc-updater.php';
        require_once UPDATE_CONTROLLER_PLUGIN_DIR . 'includes/class-uc-encryption.php';
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Activation and deactivation hooks
        register_activation_hook(UPDATE_CONTROLLER_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(UPDATE_CONTROLLER_PLUGIN_FILE, array($this, 'deactivate'));
        
        // Admin menu
        add_action('admin_menu', array('UC_Admin', 'add_menu'));
        
        // Admin scripts and styles
        add_action('admin_enqueue_scripts', array('UC_Admin', 'enqueue_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_uc_add_site', array('UC_Admin', 'ajax_add_site'));
        add_action('wp_ajax_uc_update_site', array('UC_Admin', 'ajax_update_site'));
        add_action('wp_ajax_uc_delete_site', array('UC_Admin', 'ajax_delete_site'));
        add_action('wp_ajax_uc_add_plugin', array('UC_Admin', 'ajax_add_plugin'));
        add_action('wp_ajax_uc_update_plugin', array('UC_Admin', 'ajax_update_plugin'));
        add_action('wp_ajax_uc_delete_plugin', array('UC_Admin', 'ajax_delete_plugin'));
        add_action('wp_ajax_uc_run_update', array('UC_Updater', 'ajax_run_update'));
        
        // Scheduled update hook
        add_action('uc_scheduled_update', array('UC_Updater', 'run_scheduled_update'));
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        UC_Database::create_tables();
        
        // Schedule automatic updates (daily)
        if (!wp_next_scheduled('uc_scheduled_update')) {
            wp_schedule_event(time(), 'daily', 'uc_scheduled_update');
        }
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled events
        $timestamp = wp_next_scheduled('uc_scheduled_update');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'uc_scheduled_update');
        }
    }
    
    /**
     * Get sites table name
     */
    public function get_sites_table() {
        return $this->sites_table;
    }
    
    /**
     * Get plugins table name
     */
    public function get_plugins_table() {
        return $this->plugins_table;
    }
}

/**
 * Initialize the plugin
 */
function update_controller_init() {
    return Update_Controller::get_instance();
}

// Initialize plugin
add_action('plugins_loaded', 'update_controller_init');
