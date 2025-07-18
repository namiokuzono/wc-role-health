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
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WC_Role_Health_Checker {
    
    private $plugin_version = '1.0.0';
    private $issues_found = array();
    private $fixes_applied = array();
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_wc_role_health_check', array($this, 'ajax_health_check'));
        add_action('wp_ajax_wc_role_health_fix', array($this, 'ajax_health_fix'));
        
        // Add admin notices for critical issues
        add_action('admin_notices', array($this, 'show_admin_notices'));
    }
    
    /**
     * Add admin menu page
     */
    public function add_admin_menu() {
        add_management_page(
            'WooCommerce Role Health Check',
            'WC Role Health',
            'manage_options',
            'wc-role-health',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts($hook) {
        if ($hook !== 'tools_page_wc-role-health') {
            return;
        }
        
        wp_enqueue_script('wc-role-health', plugin_dir_url(__FILE__) . 'assets/admin.js', array('jquery'), $this->plugin_version, true);
        wp_enqueue_style('wc-role-health', plugin_dir_url(__FILE__) . 'assets/admin.css', array(), $this->plugin_version);
        
        wp_localize_script('wc-role-health', 'wcRoleHealth', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wc_role_health_nonce'),
            'strings' => array(
                'checking' => __('Checking...', 'wc-role-health'),
                'fixing' => __('Fixing...', 'wc-role-health'),
                'complete' => __('Complete!', 'wc-role-health'),
                'error' => __('Error occurred', 'wc-role-health')
            )
        ));
    }
    
    /**
     * Admin page content
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="wc-role-health-container">
                <div class="postbox">
                    <h2 class="hndle"><?php _e('WooCommerce Role & Permission Health Check', 'wc-role-health'); ?></h2>
                    <div class="inside">
                        <p><?php _e('This tool will diagnose and repair WooCommerce user role and permission issues that may cause missing admin menus or access problems.', 'wc-role-health'); ?></p>
                        
                        <div class="wc-health-actions">
                            <button type="button" class="button button-primary" id="run-health-check">
                                <?php _e('Run Health Check', 'wc-role-health'); ?>
                            </button>
                            <button type="button" class="button button-secondary" id="run-health-fix" disabled>
                                <?php _e('Apply Fixes', 'wc-role-health'); ?>
                            </button>
                        </div>
                        
                        <div id="health-check-results" class="wc-health-results" style="display: none;">
                            <h3><?php _e('Health Check Results', 'wc-role-health'); ?></h3>
                            <div id="results-content"></div>
                        </div>
                        
                        <div id="health-fix-results" class="wc-health-results" style="display: none;">
                            <h3><?php _e('Fix Results', 'wc-role-health'); ?></h3>
                            <div id="fix-results-content"></div>
                        </div>
                    </div>
                </div>
                
                <div class="postbox">
                    <h2 class="hndle"><?php _e('Current System Status', 'wc-role-health'); ?></h2>
                    <div class="inside">
                        <?php $this->display_system_status(); ?>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .wc-role-health-container {
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
            background: #e8f5e8;
            border-left: 4px solid #46b450;
        }
        .system-status-table {
            width: 100%;
            border-collapse: collapse;
        }
        .system-status-table th,
        .system-status-table td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        .system-status-table th {
            background: #f1f1f1;
            font-weight: bold;
        }
        .status-good { color: #46b450; }
        .status-warning { color: #ffb900; }
        .status-critical { color: #dc3232; }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('#run-health-check').on('click', function() {
                var button = $(this);
                var resultsDiv = $('#health-check-results');
                var contentDiv = $('#results-content');
                
                button.prop('disabled', true).text(wcRoleHealth.strings.checking);
                resultsDiv.show();
                contentDiv.html('<p>Running comprehensive health check...</p>');
                
                $.ajax({
                    url: wcRoleHealth.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wc_role_health_check',
                        nonce: wcRoleHealth.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            contentDiv.html(response.data.html);
                            if (response.data.has_issues) {
                                $('#run-health-fix').prop('disabled', false);
                            }
                        } else {
                            contentDiv.html('<p class="health-issue critical">Error: ' + response.data + '</p>');
                        }
                    },
                    error: function() {
                        contentDiv.html('<p class="health-issue critical">AJAX error occurred</p>');
                    },
                    complete: function() {
                        button.prop('disabled', false).text('Run Health Check');
                    }
                });
            });
            
            $('#run-health-fix').on('click', function() {
                var button = $(this);
                var resultsDiv = $('#health-fix-results');
                var contentDiv = $('#fix-results-content');
                
                if (!confirm('Are you sure you want to apply the fixes? This will modify user roles and capabilities.')) {
                    return;
                }
                
                button.prop('disabled', true).text(wcRoleHealth.strings.fixing);
                resultsDiv.show();
                contentDiv.html('<p>Applying fixes...</p>');
                
                $.ajax({
                    url: wcRoleHealth.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wc_role_health_fix',
                        nonce: wcRoleHealth.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            contentDiv.html(response.data.html);
                            // Re-run health check
                            setTimeout(function() {
                                $('#run-health-check').trigger('click');
                            }, 1000);
                        } else {
                            contentDiv.html('<p class="health-issue critical">Error: ' + response.data + '</p>');
                        }
                    },
                    error: function() {
                        contentDiv.html('<p class="health-issue critical">AJAX error occurred</p>');
                    },
                    complete: function() {
                        button.prop('disabled', false).text('Apply Fixes');
                    }
                });
            });
        });
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
        echo '<tr><th>Item</th><th>Status</th><th>Details</th></tr>';
        
        // Current user info
        echo '<tr>';
        echo '<td>Current User</td>';
        echo '<td><span class="status-good">✓</span></td>';
        echo '<td>ID: ' . $current_user->ID . ', Login: ' . $current_user->user_login . ', Roles: ' . implode(', ', $current_user->roles) . '</td>';
        echo '</tr>';
        
        // WooCommerce status
        echo '<tr>';
        echo '<td>WooCommerce Plugin</td>';
        if ($wc_active) {
            echo '<td><span class="status-good">✓ Active</span></td>';
            echo '<td>Version: ' . WC()->version . '</td>';
        } else {
            echo '<td><span class="status-critical">✗ Inactive</span></td>';
            echo '<td>WooCommerce is not active</td>';
        }
        echo '</tr>';
        
        // Administrator role
        echo '<tr>';
        echo '<td>Administrator Role</td>';
        if ($admin_role) {
            echo '<td><span class="status-good">✓ Exists</span></td>';
            echo '<td>Capabilities: ' . count($admin_role->capabilities) . '</td>';
        } else {
            echo '<td><span class="status-critical">✗ Missing</span></td>';
            echo '<td>Administrator role is missing</td>';
        }
        echo '</tr>';
        
        echo '</table>';
    }
    
    /**
     * AJAX handler for health check
     */
    public function ajax_health_check() {
        check_ajax_referer('wc_role_health_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        $results = $this->run_health_check();
        
        wp_send_json_success(array(
            'html' => $this->format_health_results($results),
            'has_issues' => !empty($this->issues_found)
        ));
    }
    
    /**
     * AJAX handler for applying fixes
     */
    public function ajax_health_fix() {
        check_ajax_referer('wc_role_health_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        $results = $this->apply_fixes();
        
        wp_send_json_success(array(
            'html' => $this->format_fix_results($results)
        ));
    }
    
    /**
     * Run comprehensive health check
     */
    private function run_health_check() {
        $this->issues_found = array();
        $results = array();
        
        // Check 1: WooCommerce plugin status
        $results['wc_plugin'] = $this->check_woocommerce_plugin();
        
        // Check 2: User roles and capabilities
        $results['user_roles'] = $this->check_user_roles();
        
        // Check 3: Database integrity
        $results['database'] = $this->check_database_integrity();
        
        // Check 4: WooCommerce capabilities
        $results['wc_capabilities'] = $this->check_woocommerce_capabilities();
        
        // Check 5: Admin menu hooks
        $results['admin_menus'] = $this->check_admin_menus();
        
        // Check 6: User meta corruption
        $results['user_meta'] = $this->check_user_meta();
        
        // Check 7: Option corruption
        $results['options'] = $this->check_options();
        
        // Check 8: File permissions
        $results['file_permissions'] = $this->check_file_permissions();
        
        return $results;
    }
    
    /**
     * Check WooCommerce plugin status
     */
    private function check_woocommerce_plugin() {
        $result = array('status' => 'good', 'message' => 'WooCommerce plugin is active and functioning');
        
        if (!class_exists('WooCommerce')) {
            $result['status'] = 'critical';
            $result['message'] = 'WooCommerce plugin is not active';
            $this->issues_found[] = 'wc_plugin_inactive';
            return $result;
        }
        
        // Check if WooCommerce core classes exist
        $core_classes = array(
            'WC_Admin_Menus',
            'WC_Admin_Settings',
            'Automattic\WooCommerce\Admin\Install'
        );
        
        foreach ($core_classes as $class) {
            if (!class_exists($class)) {
                $result['status'] = 'critical';
                $result['message'] = "WooCommerce core class missing: {$class}";
                $this->issues_found[] = 'wc_core_missing';
                break;
            }
        }
        
        return $result;
    }
    
    /**
     * Check user roles and capabilities
     */
    private function check_user_roles() {
        global $wp_roles;
        $result = array('status' => 'good', 'message' => 'User roles are properly configured');
        
        // Check if administrator role exists
        $admin_role = get_role('administrator');
        if (!$admin_role) {
            $result['status'] = 'critical';
            $result['message'] = 'Administrator role is missing';
            $this->issues_found[] = 'admin_role_missing';
            return $result;
        }
        
        // Check essential admin capabilities
        $essential_caps = array(
            'manage_options',
            'manage_woocommerce',
            'view_woocommerce_reports',
            'edit_shop_orders',
            'edit_products'
        );
        
        $missing_caps = array();
        foreach ($essential_caps as $cap) {
            if (!$admin_role->has_cap($cap)) {
                $missing_caps[] = $cap;
            }
        }
        
        if (!empty($missing_caps)) {
            $result['status'] = 'critical';
            $result['message'] = 'Administrator role missing capabilities: ' . implode(', ', $missing_caps);
            $this->issues_found[] = 'admin_caps_missing';
        }
        
        return $result;
    }
    
    /**
     * Check database integrity
     */
    private function check_database_integrity() {
        global $wpdb;
        $result = array('status' => 'good', 'message' => 'Database tables are intact');
        
        // Check essential WordPress tables
        $essential_tables = array(
            $wpdb->users,
            $wpdb->usermeta,
            $wpdb->options,
            $wpdb->posts,
            $wpdb->postmeta
        );
        
        foreach ($essential_tables as $table) {
            $exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
            if (!$exists) {
                $result['status'] = 'critical';
                $result['message'] = "Essential database table missing: {$table}";
                $this->issues_found[] = 'db_table_missing';
                return $result;
            }
        }
        
        // Check WooCommerce tables if WC is active
        if (class_exists('WooCommerce')) {
            $wc_tables = array(
                $wpdb->prefix . 'woocommerce_sessions',
                $wpdb->prefix . 'woocommerce_order_items',
                $wpdb->prefix . 'woocommerce_order_itemmeta'
            );
            
            foreach ($wc_tables as $table) {
                $exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
                if (!$exists) {
                    $result['status'] = 'warning';
                    $result['message'] = "WooCommerce table missing: {$table}";
                    $this->issues_found[] = 'wc_table_missing';
                    break;
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Check WooCommerce specific capabilities
     */
    private function check_woocommerce_capabilities() {
        $result = array('status' => 'good', 'message' => 'WooCommerce capabilities are properly set');
        
        if (!class_exists('WooCommerce')) {
            return $result;
        }
        
        $current_user = wp_get_current_user();
        $wc_capabilities = array(
            'manage_woocommerce',
            'view_woocommerce_reports',
            'edit_shop_orders',
            'edit_products',
            'manage_product_terms',
            'edit_shop_coupons'
        );
        
        $missing_caps = array();
        foreach ($wc_capabilities as $cap) {
            if (!current_user_can($cap)) {
                $missing_caps[] = $cap;
            }
        }
        
        if (!empty($missing_caps)) {
            $result['status'] = 'critical';
            $result['message'] = 'Current user missing WooCommerce capabilities: ' . implode(', ', $missing_caps);
            $this->issues_found[] = 'wc_user_caps_missing';
        }
        
        return $result;
    }
    
    /**
     * Check admin menu hooks
     */
    private function check_admin_menus() {
        global $menu, $submenu;
        $result = array('status' => 'good', 'message' => 'Admin menus are loading properly');
        
        if (!class_exists('WooCommerce')) {
            return $result;
        }
        
        // Check if WooCommerce menu exists
        $wc_menu_found = false;
        if (is_array($menu)) {
            foreach ($menu as $menu_item) {
                if (isset($menu_item[2]) && strpos($menu_item[2], 'woocommerce') !== false) {
                    $wc_menu_found = true;
                    break;
                }
            }
        }
        
        if (!$wc_menu_found) {
            $result['status'] = 'critical';
            $result['message'] = 'WooCommerce admin menu is not present';
            $this->issues_found[] = 'wc_menu_missing';
        }
        
        return $result;
    }
    
    /**
     * Check user meta corruption
     */
    private function check_user_meta() {
        global $wpdb;
        $result = array('status' => 'good', 'message' => 'User meta is intact');
        
        $current_user = wp_get_current_user();
        
        // Check for corrupted capabilities
        $user_caps = get_user_meta($current_user->ID, $wpdb->prefix . 'capabilities', true);
        if (!is_array($user_caps)) {
            $result['status'] = 'critical';
            $result['message'] = 'User capabilities meta is corrupted';
            $this->issues_found[] = 'user_caps_corrupted';
            return $result;
        }
        
        // Check for missing administrator capability
        if (!isset($user_caps['administrator'])) {
            $result['status'] = 'critical';
            $result['message'] = 'User is missing administrator capability';
            $this->issues_found[] = 'user_admin_missing';
        }
        
        return $result;
    }
    
    /**
     * Check options corruption
     */
    private function check_options() {
        $result = array('status' => 'good', 'message' => 'WordPress options are intact');
        
        // Check essential options
        $essential_options = array(
            'active_plugins',
            'stylesheet',
            'template'
        );
        
        foreach ($essential_options as $option) {
            $value = get_option($option);
            if ($value === false) {
                $result['status'] = 'warning';
                $result['message'] = "Essential option missing: {$option}";
                $this->issues_found[] = 'option_missing';
                break;
            }
        }
        
        return $result;
    }
    
    /**
     * Check file permissions
     */
    private function check_file_permissions() {
        $result = array('status' => 'good', 'message' => 'File permissions are correct');
        
        // Check if we can write to uploads directory
        $upload_dir = wp_upload_dir();
        if (!is_writable($upload_dir['basedir'])) {
            $result['status'] = 'warning';
            $result['message'] = 'Uploads directory is not writable';
            $this->issues_found[] = 'uploads_not_writable';
        }
        
        return $result;
    }
    
    /**
     * Apply fixes for found issues
     */
    private function apply_fixes() {
        $this->fixes_applied = array();
        $results = array();
        
        foreach ($this->issues_found as $issue) {
            switch ($issue) {
                case 'admin_role_missing':
                    $results[] = $this->fix_admin_role_missing();
                    break;
                case 'admin_caps_missing':
                    $results[] = $this->fix_admin_caps_missing();
                    break;
                case 'wc_user_caps_missing':
                    $results[] = $this->fix_wc_user_caps_missing();
                    break;
                case 'user_caps_corrupted':
                    $results[] = $this->fix_user_caps_corrupted();
                    break;
                case 'user_admin_missing':
                    $results[] = $this->fix_user_admin_missing();
                    break;
                case 'wc_table_missing':
                    $results[] = $this->fix_wc_table_missing();
                    break;
                default:
                    $results[] = array('status' => 'skipped', 'message' => "No fix available for issue: {$issue}");
            }
        }
        
        return $results;
    }
    
    /**
     * Fix missing administrator role
     */
    private function fix_admin_role_missing() {
        require_once(ABSPATH . 'wp-admin/includes/schema.php');
        populate_roles();
        
        $this->fixes_applied[] = 'admin_role_restored';
        return array('status' => 'fixed', 'message' => 'Administrator role has been restored');
    }
    
    /**
     * Fix missing administrator capabilities
     */
    private function fix_admin_caps_missing() {
        $admin_role = get_role('administrator');
        if (!$admin_role) {
            return array('status' => 'failed', 'message' => 'Administrator role does not exist');
        }
        
        // Add essential capabilities
        $essential_caps = array(
            'manage_options' => true,
            'manage_woocommerce' => true,
            'view_woocommerce_reports' => true,
            'edit_shop_orders' => true,
            'edit_products' => true,
            'manage_product_terms' => true,
            'edit_shop_coupons' => true
        );
        
        foreach ($essential_caps as $cap => $grant) {
            $admin_role->add_cap($cap, $grant);
        }
        
        $this->fixes_applied[] = 'admin_caps_restored';
        return array('status' => 'fixed', 'message' => 'Administrator capabilities have been restored');
    }
    
    /**
     * Fix missing WooCommerce user capabilities
     */
    private function fix_wc_user_caps_missing() {
        $current_user = wp_get_current_user();
        
        // Grant WooCommerce capabilities directly to user
        $wc_capabilities = array(
            'manage_woocommerce',
            'view_woocommerce_reports',
            'edit_shop_orders',
            'edit_products',
            'manage_product_terms',
            'edit_shop_coupons'
        );
        
        foreach ($wc_capabilities as $cap) {
            $current_user->add_cap($cap);
        }
        
        $this->fixes_applied[] = 'wc_user_caps_restored';
        return array('status' => 'fixed', 'message' => 'WooCommerce user capabilities have been restored');
    }
    
    /**
     * Fix corrupted user capabilities
     */
    private function fix_user_caps_corrupted() {
        global $wpdb;
        $current_user = wp_get_current_user();
        
        // Reset user capabilities
        $capabilities = array('administrator' => true);
        update_user_meta($current_user->ID, $wpdb->prefix . 'capabilities', $capabilities);
        
        $this->fixes_applied[] = 'user_caps_reset';
        return array('status' => 'fixed', 'message' => 'User capabilities have been reset');
    }
    
    /**
     * Fix missing administrator capability for user
     */
    private function fix_user_admin_missing() {
        $current_user = wp_get_current_user();
        $current_user->add_role('administrator');
        
        $this->fixes_applied[] = 'user_admin_restored';
        return array('status' => 'fixed', 'message' => 'Administrator role has been added to user');
    }
    
    /**
     * Fix missing WooCommerce tables
     */
    private function fix_wc_table_missing() {
        if (!class_exists('WooCommerce')) {
            return array('status' => 'failed', 'message' => 'WooCommerce is not active');
        }
        
        // Force WooCommerce to recreate tables
        if (class_exists('WC_Install')) {
            WC_Install::create_tables();
            $this->fixes_applied[] = 'wc_tables_created';
            return array('status' => 'fixed', 'message' => 'WooCommerce tables have been recreated');
        }
        
        return array('status' => 'failed', 'message' => 'Could not recreate WooCommerce tables');
    }
    
    /**
     * Format health check results for display
     */
    private function format_health_results($results) {
        $html = '';
        
        foreach ($results as $check => $result) {
            $class = 'health-issue ' . $result['status'];
            $icon = $result['status'] === 'good' ? '✓' : ($result['status'] === 'warning' ? '⚠' : '✗');
            
            $html .= "<div class='{$class}'>";
            $html .= "<strong>{$icon} " . ucwords(str_replace('_', ' ', $check)) . "</strong><br>";
            $html .= $result['message'];
            $html .= "</div>";
        }
        
        if (empty($this->issues_found)) {
            $html .= "<div class='health-issue good'><strong>✓ All checks passed!</strong><br>No issues found with your WooCommerce installation.</div>";
        } else {
            $html .= "<div class='health-issue warning'><strong>⚠ Issues found: " . count($this->issues_found) . "</strong><br>Click 'Apply Fixes' to attempt automatic repairs.</div>";
        }
        
        return $html;
    }
    
    /**
     * Format fix results for display
     */
    private function format_fix_results($results) {
        $html = '';
        
        foreach ($results as $result) {
            $class = 'health-issue ' . $result['status'];
            $icon = $result['status'] === 'fixed' ? '✓' : ($result['status'] === 'skipped' ? '⚠' : '✗');
            
            $html .= "<div class='{$class}'>";
            $html .= "<strong>{$icon} " . ucwords($result['status']) . "</strong><br>";
            $html .= $result['message'];
            $html .= "</div>";
        }
        
        if (!empty($this->fixes_applied)) {
            $html .= "<div class='health-issue good'><strong>✓ Fixes applied: " . count($this->fixes_applied) . "</strong><br>Please refresh your WordPress admin and check if the WooCommerce menu is now visible.</div>";
        }
        
        return $html;
    }
    
    /**
     * Show admin notices for critical issues
     */
    public function show_admin_notices() {
        // Only show on admin pages and if user can manage options
        if (!is_admin() || !current_user_can('manage_options')) {
            return;
        }
        
        // Quick check for critical issues
        $critical_issues = array();
        
        // Check if WooCommerce menu is missing (quick check)
        if (class_exists('WooCommerce')) {
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
            
            if (!$wc_menu_found) {
                $critical_issues[] = 'WooCommerce admin menu is missing';
            }
        }
        
        // Check if current user has essential capabilities
        if (!current_user_can('manage_woocommerce') && class_exists('WooCommerce')) {
            $critical_issues[] = 'Missing WooCommerce management capabilities';
        }
        
        if (!empty($critical_issues)) {
            echo '<div class="notice notice-error is-dismissible">';
            echo '<h3>WooCommerce Role & Permission Issues Detected</h3>';
            echo '<ul>';
            foreach ($critical_issues as $issue) {
                echo '<li>' . esc_html($issue) . '</li>';
            }
            echo '</ul>';
            echo '<p><a href="' . admin_url('tools.php?page=wc-role-health') . '" class="button button-primary">Run Health Check & Fix</a></p>';
            echo '</div>';
        }
    }
    
    /**
     * Export system information for debugging
     */
    public function export_system_info() {
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
    
    /**
     * Advanced permission repair - nuclear option
     */
    public function nuclear_permission_repair() {
        global $wpdb;
        
        if (!current_user_can('manage_options')) {
            return false;
        }
        
        // Backup current state
        $backup = array(
            'user_roles' => get_option($wpdb->prefix . 'user_roles'),
            'current_user_caps' => get_user_meta(get_current_user_id(), $wpdb->prefix . 'capabilities', true)
        );
        update_option('wc_role_health_backup', $backup);
        
        // Step 1: Completely reset WordPress roles
        delete_option($wpdb->prefix . 'user_roles');
        require_once(ABSPATH . 'wp-admin/includes/schema.php');
        populate_roles();
        
        // Step 2: Reset current user capabilities
        $current_user_id = get_current_user_id();
        update_user_meta($current_user_id, $wpdb->prefix . 'capabilities', array('administrator' => true));
        update_user_meta($current_user_id, $wpdb->prefix . 'user_level', 10);
        
        // Step 3: Reinitialize WooCommerce capabilities if active
        if (class_exists('WooCommerce') && function_exists('wc_get_core_capabilities')) {
            $admin_role = get_role('administrator');
            if ($admin_role) {
                $wc_caps = wc_get_core_capabilities();
                foreach ($wc_caps as $cap_group) {
                    foreach ($cap_group as $cap) {
                        $admin_role->add_cap($cap);
                    }
                }
            }
        }
        
        // Step 4: Force WordPress to refresh capabilities
        wp_cache_delete($current_user_id, 'users');
        wp_cache_delete($current_user_id, 'user_meta');
        
        return true;
    }
    
    /**
     * Check for plugin conflicts that might affect admin menus
     */
    private function check_plugin_conflicts() {
        $result = array('status' => 'good', 'message' => 'No conflicting plugins detected');
        
        $known_problematic_plugins = array(
            'user-role-editor/user-role-editor.php' => 'User Role Editor',
            'members/members.php' => 'Members',
            'capability-manager-enhanced/capsman-enhanced.php' => 'Capability Manager Enhanced',
            'admin-menu-editor/menu-editor.php' => 'Admin Menu Editor',
            'white-label-cms/white-label-cms.php' => 'White Label CMS'
        );
        
        $active_plugins = get_option('active_plugins', array());
        $problematic_active = array();
        
        foreach ($active_plugins as $plugin) {
            if (isset($known_problematic_plugins[$plugin])) {
                $problematic_active[] = $known_problematic_plugins[$plugin];
            }
        }
        
        if (!empty($problematic_active)) {
            $result['status'] = 'warning';
            $result['message'] = 'Potentially conflicting plugins active: ' . implode(', ', $problematic_active);
            $this->issues_found[] = 'plugin_conflicts';
        }
        
        return $result;
    }
    
    /**
     * Check for custom code that might interfere with admin menus
     */
    private function check_custom_code_interference() {
        $result = array('status' => 'good', 'message' => 'No obvious code interference detected');
        
        // Check active theme's functions.php for problematic code
        $theme_functions = get_template_directory() . '/functions.php';
        if (file_exists($theme_functions)) {
            $functions_content = file_get_contents($theme_functions);
            
            $problematic_patterns = array(
                'remove_menu_page',
                'remove_submenu_page',
                'current_user_can.*manage_woocommerce.*false',
                'unset.*woocommerce',
                'admin_menu.*remove'
            );
            
            foreach ($problematic_patterns as $pattern) {
                if (preg_match('/' . $pattern . '/i', $functions_content)) {
                    $result['status'] = 'warning';
                    $result['message'] = 'Theme functions.php contains code that might interfere with admin menus';
                    $this->issues_found[] = 'theme_interference';
                    break;
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Emergency access restoration
     */
    public function emergency_access_restoration() {
        if (!current_user_can('edit_plugins')) {
            return false;
        }
        
        // Create emergency admin user if current user is locked out
        $emergency_user = array(
            'user_login' => 'emergency_admin_' . time(),
            'user_pass' => wp_generate_password(12, false),
            'user_email' => get_option('admin_email'),
            'role' => 'administrator'
        );
        
        $user_id = wp_insert_user($emergency_user);
        
        if (!is_wp_error($user_id)) {
            // Grant all possible capabilities
            $user = new WP_User($user_id);
            $all_caps = array(
                'manage_options' => true,
                'manage_woocommerce' => true,
                'view_woocommerce_reports' => true,
                'edit_shop_orders' => true,
                'edit_products' => true,
                'manage_product_terms' => true,
                'edit_shop_coupons' => true,
                'read' => true,
                'upload_files' => true,
                'edit_posts' => true,
                'edit_others_posts' => true,
                'edit_published_posts' => true,
                'publish_posts' => true,
                'edit_pages' => true,
                'edit_others_pages' => true,
                'edit_published_pages' => true,
                'publish_pages' => true,
                'delete_posts' => true,
                'delete_others_posts' => true,
                'delete_published_posts' => true,
                'delete_pages' => true,
                'delete_others_pages' => true,
                'delete_published_pages' => true,
                'manage_categories' => true,
                'manage_links' => true,
                'moderate_comments' => true,
                'activate_plugins' => true,
                'edit_plugins' => true,
                'edit_themes' => true,
                'install_plugins' => true,
                'install_themes' => true,
                'update_plugins' => true,
                'update_themes' => true,
                'update_core' => true,
                'list_users' => true,
                'edit_users' => true,
                'create_users' => true,
                'delete_users' => true,
                'import' => true,
                'export' => true
            );
            
            foreach ($all_caps as $cap => $grant) {
                $user->add_cap($cap, $grant);
            }
            
            return array(
                'user_login' => $emergency_user['user_login'],
                'user_pass' => $emergency_user['user_pass'],
                'user_email' => $emergency_user['user_email']
            );
        }
        
        return false;
    }
}

// Initialize the plugin
if (is_admin()) {
    new WC_Role_Health_Checker();
}

/**
 * Activation hook
 */
register_activation_hook(__FILE__, function() {
    // Create a log table for tracking fixes
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wc_role_health_log';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        user_id bigint(20) NOT NULL,
        action varchar(255) NOT NULL,
        details text,
        PRIMARY KEY (id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
});

/**
 * Deactivation hook
 */
register_deactivation_hook(__FILE__, function() {
    // Clean up any temporary data
    delete_option('wc_role_health_backup');
});

/**
 * Helper function to log actions
 */
function wc_role_health_log($action, $details = '') {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wc_role_health_log';
    
    $wpdb->insert(
        $table_name,
        array(
            'user_id' => get_current_user_id(),
            'action' => $action,
            'details' => $details
        ),
        array('%d', '%s', '%s')
    );
}

// Add emergency recovery via URL parameter (use with caution)
if (isset($_GET['wc_emergency_recovery']) && $_GET['wc_emergency_recovery'] === 'true' && current_user_can('manage_options')) {
    add_action('init', function() {
        $checker = new WC_Role_Health_Checker();
        $result = $checker->nuclear_permission_repair();
        
        if ($result) {
            wp_redirect(admin_url('tools.php?page=wc-role-health&recovery=success'));
        } else {
            wp_redirect(admin_url('tools.php?page=wc-role-health&recovery=failed'));
        }
        exit;
    });
}

?>
