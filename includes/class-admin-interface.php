<?php
/**
 * Admin Interface Class
 * 
 * Handles WordPress admin interface, menu, scripts, and page rendering.
 * 
 * @package WC_Role_Permission_Health_Check
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WC_RPHC_Admin_Interface {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_wc_rphc_export_system_info', array($this, 'ajax_export_system_info'));
        add_action('wp_ajax_wc_rphc_nuclear_repair', array($this, 'ajax_nuclear_repair'));
    }
    
    /**
     * Add admin menu page
     */
    public function add_admin_menu() {
        add_management_page(
            __('WooCommerce Role Health Check', 'wc-role-permission-health-check'),
            __('WC Role Health', 'wc-role-permission-health-check'),
            'manage_options',
            'wc-role-health',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts($hook) {
        // Debug: Log the hook name to help identify the correct hook
        error_log('WCRPHC Debug: Current hook: ' . $hook);
        
        // Check if we're on our plugin page - be more flexible with the hook check
        if (strpos($hook, 'wc-role-health') === false && $hook !== 'tools_page_wc-role-health') {
            error_log('WCRPHC Debug: Hook mismatch, not loading scripts. Expected: tools_page_wc-role-health, Got: ' . $hook);
            return;
        }
        
        error_log('WCRPHC Debug: Loading scripts for hook: ' . $hook);
        
        // Check if files actually exist
        $js_file = WC_RPHC_PLUGIN_URL . 'assets/admin.js';
        $css_file = WC_RPHC_PLUGIN_URL . 'assets/admin.css';
        
        error_log('WCRPHC Debug: JS File: ' . $js_file);
        error_log('WCRPHC Debug: CSS File: ' . $css_file);
        
        wp_enqueue_script(
            'wc-rphc-admin', 
            $js_file,
            array('jquery'), 
            WC_RPHC_VERSION, 
            true
        );
        
        wp_enqueue_style(
            'wc-rphc-admin', 
            $css_file,
            array(), 
            WC_RPHC_VERSION
        );
        
        wp_localize_script('wc-rphc-admin', 'wcRphc', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wc_rphc_nonce'),
            'strings' => array(
                'checking' => __('Checking...', 'wc-role-permission-health-check'),
                'fixing' => __('Fixing...', 'wc-role-permission-health-check'),
                'complete' => __('Complete!', 'wc-role-permission-health-check'),
                'error' => __('Error occurred', 'wc-role-permission-health-check'),
                'confirm_nuclear' => __('Are you sure you want to perform a nuclear repair? This will reset all user roles and capabilities. A backup will be created first.', 'wc-role-permission-health-check'),
                'confirm_fixes' => __('Are you sure you want to apply the fixes? This will modify user roles and capabilities.', 'wc-role-permission-health-check')
            )
        ));
        
        error_log('WCRPHC Debug: Scripts enqueued successfully');
    }
    
    /**
     * Admin page content
     */
    public function admin_page() {
        // Check for recovery messages
        $recovery_message = '';
        if (isset($_GET['recovery'])) {
            if ($_GET['recovery'] === 'success') {
                $recovery_message = '<div class="notice notice-success is-dismissible"><p>' . __('Emergency recovery completed successfully!', 'wc-role-permission-health-check') . '</p></div>';
            } elseif ($_GET['recovery'] === 'failed') {
                $recovery_message = '<div class="notice notice-error is-dismissible"><p>' . __('Emergency recovery failed. Please try again or contact support.', 'wc-role-permission-health-check') . '</p></div>';
            }
        }
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <?php echo $recovery_message; ?>
            
            <div class="wc-rphc-container">
                <!-- Main Health Check Section -->
                <div class="postbox">
                    <h2 class="hndle"><?php _e('WooCommerce Role & Permission Health Check', 'wc-role-permission-health-check'); ?></h2>
                    <div class="inside">
                        <p><?php _e('This tool will diagnose and repair WooCommerce user role and permission issues that may cause missing admin menus or access problems.', 'wc-role-permission-health-check'); ?></p>
                        
                        <div class="wc-health-actions">
                            <button type="button" class="button button-primary" id="run-health-check">
                                <?php _e('Run Health Check', 'wc-role-permission-health-check'); ?>
                            </button>
                            <button type="button" class="button button-secondary" id="run-health-fix" disabled>
                                <?php _e('Apply Fixes', 'wc-role-permission-health-check'); ?>
                            </button>
                            <button type="button" class="button button-secondary" id="nuclear-repair">
                                <?php _e('Nuclear Repair', 'wc-role-permission-health-check'); ?>
                            </button>
                        </div>
                        
                        <div id="health-check-results" class="wc-health-results" style="display: none;">
                            <h3><?php _e('Health Check Results', 'wc-role-permission-health-check'); ?></h3>
                            <div id="results-content"></div>
                        </div>
                        
                        <div id="health-fix-results" class="wc-health-results" style="display: none;">
                            <h3><?php _e('Fix Results', 'wc-role-permission-health-check'); ?></h3>
                            <div id="fix-results-content"></div>
                        </div>
                    </div>
                </div>
                
                <!-- System Status Section -->
                <div class="postbox">
                    <h2 class="hndle"><?php _e('Current System Status', 'wc-role-permission-health-check'); ?></h2>
                    <div class="inside">
                        <?php $this->display_system_status(); ?>
                    </div>
                </div>
                
                <!-- Emergency Recovery Section -->
                <div class="postbox">
                    <h2 class="hndle"><?php _e('Emergency Recovery', 'wc-role-permission-health-check'); ?></h2>
                    <div class="inside">
                        <p><?php _e('If you are completely locked out of WooCommerce, use these emergency recovery options:', 'wc-role-permission-health-check'); ?></p>
                        
                        <div class="emergency-options">
                            <h4><?php _e('Emergency Recovery URL', 'wc-role-permission-health-check'); ?></h4>
                            <p><?php _e('Add this parameter to any admin URL to trigger emergency recovery:', 'wc-role-permission-health-check'); ?></p>
                            <code><?php echo esc_url(admin_url('?wc_emergency_recovery=true&_wpnonce=' . wp_create_nonce('wc_emergency_recovery'))); ?></code>
                            
                            <h4><?php _e('Emergency Admin User', 'wc-role-permission-health-check'); ?></h4>
                            <p><?php _e('Create a new emergency administrator user:', 'wc-role-permission-health-check'); ?></p>
                            <button type="button" class="button button-secondary" id="create-emergency-user">
                                <?php _e('Create Emergency User', 'wc-role-permission-health-check'); ?>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- System Information Section -->
                <div class="postbox">
                    <h2 class="hndle"><?php _e('System Information', 'wc-role-permission-health-check'); ?></h2>
                    <div class="inside">
                        <p><?php _e('Export detailed system information for debugging:', 'wc-role-permission-health-check'); ?></p>
                        <button type="button" class="button button-secondary" id="export-system-info">
                            <?php _e('Export System Info', 'wc-role-permission-health-check'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .wc-rphc-container {
            max-width: 1200px;
        }
        .wc-health-actions {
            margin: 20px 0;
        }
        .wc-health-actions button {
            margin-right: 10px;
        }
        .wc-health-results {
            margin-top: 20px;
            padding: 15px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .health-issue {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .health-issue.critical {
            background: #fee;
            border-left: 4px solid #dc3232;
        }
        .health-issue.warning {
            background: #fff8e1;
            border-left: 4px solid #ffb900;
        }
        .health-issue.good {
            background: #f3f0ff;
            border-left: 4px solid #8b7dd3;
            color: #222;
        }
        .system-status-table {
            width: 100%;
            border-collapse: collapse;
        }
        .system-status-table th,
        .system-status-table td {
            padding: 12px;
            border: 1px solid #e0d8ff;
            text-align: left;
            color: #2c2c2c !important;
            font-weight: 500;
        }
        .system-status-table th {
            background: #f8f6ff;
            font-weight: bold;
            border-bottom: 2px solid #e0d8ff;
            color: #2c2c2c !important;
        }
        .system-status-table tr:nth-child(even) {
            background: #f8f6ff;
        }
        .system-status-table tr:nth-child(odd) {
            background: #ffffff;
        }
        .system-status-table tr:nth-child(even) td {
            color: #2c2c2c !important;
        }
        .system-status-table tr:nth-child(odd) td {
            color: #2c2c2c !important;
        }
        /* Force proper contrast for all table content */
        .system-status-table td,
        .system-status-table th {
            text-shadow: 0 0 1px rgba(255, 255, 255, 0.8);
        }
        /* Ensure alternating rows are clearly visible */
        .system-status-table tr:nth-child(even) {
            background: #f8f6ff !important;
        }
        .system-status-table tr:nth-child(odd) {
            background: #ffffff !important;
        }
        .status-good { color: #2c2c2c !important; font-weight: 600; }
        .status-warning { color: #d97706 !important; font-weight: 600; }
        .status-critical { color: #dc2626 !important; font-weight: 600; }
        .emergency-options {
            margin-top: 15px;
        }
        .emergency-options h4 {
            margin-top: 20px;
            margin-bottom: 10px;
        }
        .emergency-options code {
            display: block;
            padding: 12px;
            background: #ffffff;
            border: 2px solid #e0d8ff;
            border-radius: 6px;
            margin: 10px 0;
            word-break: break-all;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            color: #2c2c2c;
            box-shadow: 0 2px 4px rgba(139, 125, 211, 0.1);
        }
        </style>
        
        <!-- Debug JavaScript Loading -->
        <script>
        console.log('WCRPHC: Inline script loaded');
        console.log('WCRPHC: jQuery available:', typeof jQuery !== 'undefined');
        console.log('WCRPHC: wcRphc object:', typeof wcRphc !== 'undefined' ? wcRphc : 'undefined');
        
        // Simple test to see if our external JS loads
        jQuery(document).ready(function() {
            console.log('WCRPHC: Document ready in inline script');
            
            // Test if our buttons exist
            console.log('WCRPHC: Run health check button:', jQuery('#run-health-check').length);
            console.log('WCRPHC: Apply fixes button:', jQuery('#run-health-fix').length);
            
            // Check if external JS loaded by looking for WCRPHC object
            if (typeof window.WCRPHC === 'undefined') {
                console.log('WCRPHC: External JS not loaded, using fallback handlers');
                
                // Fallback handlers only if external JS fails
                jQuery('#run-health-check').on('click', function(e) {
                    console.log('WCRPHC: Health check button clicked (fallback handler)');
                    alert('External JavaScript not loading. Please check file paths and permissions.');
                });
                
                jQuery('#run-health-fix').on('click', function(e) {
                    console.log('WCRPHC: Apply fixes button clicked (fallback handler)');
                    alert('External JavaScript not loading. Please check file paths and permissions.');
                });
            } else {
                console.log('WCRPHC: External JS loaded successfully');
            }
            
            // Add a simple test handler for Apply Fixes that will work regardless
            jQuery('#run-health-fix').off('click').on('click', function(e) {
                e.preventDefault();
                console.log('WCRPHC: Apply fixes button clicked (test handler)');
                
                var button = jQuery(this);
                var resultsDiv = jQuery('#health-fix-results');
                var contentDiv = jQuery('#fix-results-content');
                
                button.prop('disabled', true).text('Testing...');
                resultsDiv.show();
                contentDiv.html('<p>Testing Apply Fixes functionality...</p>');
                
                // Test AJAX call
                jQuery.ajax({
                    url: wcRphc.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wc_rphc_health_fix',
                        nonce: wcRphc.nonce
                    },
                    success: function(response) {
                        console.log('WCRPHC: AJAX response:', response);
                        if (response.success) {
                            contentDiv.html(response.data.html);
                        } else {
                            contentDiv.html('<p class="health-issue critical">Error: ' + response.data + '</p>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('WCRPHC: AJAX error:', xhr, status, error);
                        contentDiv.html('<p class="health-issue critical">AJAX error occurred: ' + error + '</p>');
                    },
                    complete: function() {
                        button.prop('disabled', false).text('Apply Fixes');
                    }
                });
            });
        });
        </script>
        
        <!-- External JavaScript will handle all button interactions -->
        <script>
        console.log('WCRPHC: Admin page loaded, external JS should handle interactions');
        </script>
        <?php
    }
    
    /**
     * Display current system status
     */
    private function display_system_status() {
        $current_user = wp_get_current_user();
        $wc_active = class_exists('WooCommerce');
        $admin_role = get_role('administrator');
        
        echo '<table class="system-status-table">';
        echo '<tr><th>' . __('Item', 'wc-role-permission-health-check') . '</th><th>' . __('Status', 'wc-role-permission-health-check') . '</th><th>' . __('Details', 'wc-role-permission-health-check') . '</th></tr>';
        
        // Current user info
        echo '<tr class="status-row-even">';
        echo '<td style="color: #2c2c2c !important; background: #f8f6ff !important;">' . __('Current User', 'wc-role-permission-health-check') . '</td>';
        echo '<td style="color: #2c2c2c !important; background: #f8f6ff !important;"><span class="status-good">✓</span></td>';
        echo '<td style="color: #2c2c2c !important; background: #f8f6ff !important;">ID: ' . $current_user->ID . ', Login: ' . $current_user->user_login . ', Roles: ' . implode(', ', $current_user->roles) . '</td>';
        echo '</tr>';
        
        // WooCommerce status
        echo '<tr class="status-row-odd">';
        echo '<td style="color: #2c2c2c !important; background: #ffffff !important;">' . __('WooCommerce Plugin', 'wc-role-permission-health-check') . '</td>';
        if ($wc_active) {
            echo '<td style="color: #2c2c2c !important; background: #ffffff !important;"><span class="status-good">✓ ' . __('Active', 'wc-role-permission-health-check') . '</span></td>';
            echo '<td style="color: #2c2c2c !important; background: #ffffff !important;">' . sprintf(__('Version: %s', 'wc-role-permission-health-check'), WC()->version) . '</td>';
        } else {
            echo '<td style="color: #2c2c2c !important; background: #ffffff !important;"><span class="status-critical">✗ ' . __('Inactive', 'wc-role-permission-health-check') . '</span></td>';
            echo '<td style="color: #2c2c2c !important; background: #ffffff !important;">' . __('WooCommerce is not active', 'wc-role-permission-health-check') . '</td>';
        }
        echo '</tr>';
        
        // Administrator role
        echo '<tr class="status-row-even">';
        echo '<td style="color: #2c2c2c !important; background: #f8f6ff !important;">' . __('Administrator Role', 'wc-role-permission-health-check') . '</td>';
        if ($admin_role) {
            echo '<td style="color: #2c2c2c !important; background: #f8f6ff !important;"><span class="status-good">✓ ' . __('Exists', 'wc-role-permission-health-check') . '</span></td>';
            echo '<td style="color: #2c2c2c !important; background: #f8f6ff !important;">' . sprintf(__('Capabilities: %d', 'wc-role-permission-health-check'), count($admin_role->capabilities)) . '</td>';
        } else {
            echo '<td style="color: #2c2c2c !important; background: #f8f6ff !important;"><span class="status-critical">✗ ' . __('Missing', 'wc-role-permission-health-check') . '</span></td>';
            echo '<td style="color: #2c2c2c !important; background: #f8f6ff !important;">' . __('Administrator role is missing', 'wc-role-permission-health-check') . '</td>';
        }
        echo '</tr>';
        
        // WooCommerce menu status
        echo '<tr class="status-row-odd">';
        echo '<td style="color: #2c2c2c !important; background: #ffffff !important;">' . __('WooCommerce Menu', 'wc-role-permission-health-check') . '</td>';
        if ($wc_active) {
            global $menu;
            $wc_menu_found = false;
            if (is_array($menu)) {
                foreach ($menu as $menu_item) {
                    if (isset($menu_item[2]) && strpos($menu_item[2], 'woocommerce') !== false) {
                        $wc_menu_found = true;
                        break;
                    }
                }
            }
            
            if ($wc_menu_found) {
                echo '<td style="color: #2c2c2c !important; background: #ffffff !important;"><span class="status-good">✓ ' . __('Visible', 'wc-role-permission-health-check') . '</span></td>';
                echo '<td style="color: #2c2c2c !important; background: #ffffff !important;">' . __('WooCommerce menu is present in admin', 'wc-role-permission-health-check') . '</td>';
            } else {
                echo '<td style="color: #2c2c2c !important; background: #ffffff !important;"><span class="status-critical">✗ ' . __('Missing', 'wc-role-permission-health-check') . '</span></td>';
                echo '<td style="color: #2c2c2c !important; background: #ffffff !important;">' . __('WooCommerce menu is not visible', 'wc-role-permission-health-check') . '</td>';
            }
        } else {
            echo '<td style="color: #2c2c2c !important; background: #ffffff !important;"><span class="status-warning">⚠ ' . __('N/A', 'wc-role-permission-health-check') . '</span></td>';
            echo '<td style="color: #2c2c2c !important; background: #ffffff !important;">' . __('WooCommerce not active', 'wc-role-permission-health-check') . '</td>';
        }
        echo '</tr>';
        
        echo '</table>';
    }
    
    /**
     * AJAX handler for exporting system info
     */
    public function ajax_export_system_info() {
        check_ajax_referer('wc_rphc_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'wc-role-permission-health-check'));
        }
        
        $system_info = $this->get_system_info();
        
        wp_send_json_success($system_info);
    }
    
    /**
     * AJAX handler for nuclear repair
     */
    public function ajax_nuclear_repair() {
        check_ajax_referer('wc_rphc_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'wc-role-permission-health-check'));
        }
        
        // Get the emergency recovery instance
        $emergency_recovery = new WC_RPHC_Emergency_Recovery();
        $result = $emergency_recovery->nuclear_permission_repair();
        
        if ($result) {
            wp_send_json_success(__('Nuclear repair completed successfully.', 'wc-role-permission-health-check'));
        } else {
            wp_send_json_error(__('Nuclear repair failed.', 'wc-role-permission-health-check'));
        }
    }
    
    /**
     * Get system information
     */
    private function get_system_info() {
        global $wpdb, $wp_version;
        
        $info = array();
        $info['timestamp'] = current_time('mysql');
        $info['site_url'] = site_url();
        $info['wp_version'] = $wp_version;
        $info['php_version'] = PHP_VERSION;
        
        // WordPress info
        $info['wp_debug'] = defined('WP_DEBUG') ? WP_DEBUG : false;
        $info['wp_debug_log'] = defined('WP_DEBUG_LOG') ? WP_DEBUG_LOG : false;
        
        // User info
        $current_user = wp_get_current_user();
        $info['current_user'] = array(
            'ID' => $current_user->ID,
            'login' => $current_user->user_login,
            'email' => $current_user->user_email,
            'roles' => $current_user->roles,
            'capabilities' => array_keys($current_user->allcaps, true)
        );
        
        // WooCommerce info
        if (class_exists('WooCommerce')) {
            $info['woocommerce'] = array(
                'version' => WC()->version,
                'database_version' => get_option('woocommerce_db_version'),
                'active' => true
            );
        } else {
            $info['woocommerce'] = array('active' => false);
        }
        
        // Active plugins
        $info['active_plugins'] = get_option('active_plugins', array());
        
        // Theme info
        $theme = wp_get_theme();
        $info['theme'] = array(
            'name' => $theme->get('Name'),
            'version' => $theme->get('Version'),
            'template' => get_template(),
            'stylesheet' => get_stylesheet()
        );
        
        // Database info
        $info['database'] = array(
            'version' => $wpdb->db_version(),
            'charset' => $wpdb->charset,
            'collate' => $wpdb->collate
        );
        
        // Role info
        global $wp_roles;
        $info['roles'] = array();
        foreach ($wp_roles->roles as $role_name => $role_info) {
            $info['roles'][$role_name] = array(
                'name' => $role_info['name'],
                'capabilities' => count($role_info['capabilities'])
            );
        }
        
        return $info;
    }
} 