<?php
/**
 * Plugin Name: WooCommerce Role & Permission Health Check
 * Description: Diagnose and repair WooCommerce user role and permission issues including missing admin menus and access problems.
 * Version: 1.0.0
 * Author: Woo Nami
 * License: GPL v2 or later
 * Requires at least: 5.0
 * Tested up to: 6.4
 * WC requires at least: 5.0
 * WC tested up to: 9.0
 * Text Domain: wc-role-permission-health-check
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WC_RPHC_VERSION', '1.0.0');
define('WC_RPHC_PLUGIN_FILE', __FILE__);
define('WC_RPHC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WC_RPHC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WC_RPHC_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main plugin class
 */
class WC_Role_Permission_Health_Check {
    
    /**
     * Plugin instance
     */
    private static $instance = null;
    
    /**
     * Health checker instance
     */
    public $health_checker;
    
    /**
     * Emergency recovery instance
     */
    public $emergency_recovery;
    
    /**
     * Admin interface instance
     */
    public $admin_interface;
    
    /**
     * Get plugin instance
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
        $this->init_hooks();
        $this->load_dependencies();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Load required files
        require_once WC_RPHC_PLUGIN_DIR . 'includes/class-health-checker.php';
        require_once WC_RPHC_PLUGIN_DIR . 'includes/class-emergency-recovery.php';
        require_once WC_RPHC_PLUGIN_DIR . 'includes/class-admin-interface.php';
        
        // Initialize components
        $this->health_checker = new WC_RPHC_Health_Checker();
        $this->emergency_recovery = new WC_RPHC_Emergency_Recovery();
        $this->admin_interface = new WC_RPHC_Admin_Interface();
    }
    
    /**
     * Load text domain for translations
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'wc-role-permission-health-check',
            false,
            dirname(WC_RPHC_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Run initial health check on activation
        if (class_exists('WC_RPHC_Health_Checker')) {
            $health_checker = new WC_RPHC_Health_Checker();
            $health_checker->run_initial_check();
        }
        
        // Log activation
        wc_rphc_log('Plugin activated', array('version' => WC_RPHC_VERSION));
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clean up any scheduled events
        wp_clear_scheduled_hook('wc_rphc_health_check_cron');
        
        // Log deactivation
        wc_rphc_log('Plugin deactivated');
    }
}

/**
 * Initialize the plugin
 */
function wc_rphc_init() {
    return WC_Role_Permission_Health_Check::get_instance();
}

// Start the plugin
add_action('plugins_loaded', 'wc_rphc_init');

/**
 * Global logging function
 */
function wc_rphc_log($action, $details = '') {
    if (!function_exists('wc_get_logger')) {
        return;
    }
    
    $logger = wc_get_logger();
    $context = array('source' => 'wc-role-permission-health-check');
    
    $message = $action;
    if (!empty($details)) {
        if (is_array($details)) {
            $message .= ' - ' . json_encode($details);
        } else {
            $message .= ' - ' . $details;
        }
    }
    
    $logger->info($message, $context);
}

/**
 * Emergency recovery access
 */
function wc_rphc_emergency_recovery() {
    if (isset($_GET['wc_emergency_recovery']) && $_GET['wc_emergency_recovery'] === 'true') {
        if (class_exists('WC_RPHC_Emergency_Recovery')) {
            $recovery = new WC_RPHC_Emergency_Recovery();
            $recovery->attempt_emergency_recovery();
        }
    }
}
add_action('init', 'wc_rphc_emergency_recovery');
