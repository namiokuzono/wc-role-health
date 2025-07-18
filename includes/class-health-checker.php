<?php
/**
 * Health Checker Class
 * 
 * Handles all diagnostic and repair functionality for WooCommerce role and permission issues.
 * 
 * @package WC_Role_Permission_Health_Check
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WC_RPHC_Health_Checker {
    
    /**
     * Issues found during health check
     */
    private $issues_found = array();
    
    /**
     * Fixes applied during repair
     */
    private $fixes_applied = array();
    
    /**
     * Required WooCommerce capabilities for administrator role
     */
    private $required_caps = array(
        'manage_woocommerce',
        'edit_others_shop_orders',
        'view_woocommerce_reports',
        'edit_products',
        'edit_shop_orders',
        'edit_shop_coupons',
        'manage_product_terms',
        'manage_shop_order_terms',
        'manage_shop_coupon_terms'
    );
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_wc_rphc_health_check', array($this, 'ajax_health_check'));
        add_action('wp_ajax_wc_rphc_health_fix', array($this, 'ajax_health_fix'));
        add_action('wc_rphc_health_check_cron', array($this, 'cron_health_check'));
    }
    
    /**
     * Run comprehensive health check
     */
    public function run_health_check() {
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
        
        // Check 9: Plugin conflicts
        $results['plugin_conflicts'] = $this->check_plugin_conflicts();
        
        // Check 10: Custom code interference
        $results['custom_code'] = $this->check_custom_code_interference();
        
        return $results;
    }
    
    /**
     * Check WooCommerce plugin status
     */
    private function check_woocommerce_plugin() {
        $result = array('status' => 'good', 'message' => __('WooCommerce plugin is active and functioning', 'wc-role-permission-health-check'));
        
        if (!class_exists('WooCommerce')) {
            $result['status'] = 'critical';
            $result['message'] = __('WooCommerce plugin is not active', 'wc-role-permission-health-check');
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
                $result['message'] = sprintf(__('WooCommerce core class missing: %s', 'wc-role-permission-health-check'), $class);
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
        $result = array('status' => 'good', 'message' => __('User roles are properly configured', 'wc-role-permission-health-check'));
        
        // Check if administrator role exists
        $admin_role = get_role('administrator');
        if (!$admin_role) {
            $result['status'] = 'critical';
            $result['message'] = __('Administrator role is missing', 'wc-role-permission-health-check');
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
            $result['message'] = sprintf(__('Administrator role missing capabilities: %s', 'wc-role-permission-health-check'), implode(', ', $missing_caps));
            $this->issues_found[] = 'admin_caps_missing';
        }
        
        return $result;
    }
    
    /**
     * Check database integrity
     */
    private function check_database_integrity() {
        global $wpdb;
        $result = array('status' => 'good', 'message' => __('Database tables are intact', 'wc-role-permission-health-check'));
        
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
                $result['message'] = sprintf(__('Essential database table missing: %s', 'wc-role-permission-health-check'), $table);
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
                    $result['message'] = sprintf(__('WooCommerce table missing: %s', 'wc-role-permission-health-check'), $table);
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
        $result = array('status' => 'good', 'message' => __('WooCommerce capabilities are properly set', 'wc-role-permission-health-check'));
        
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
            $result['message'] = sprintf(__('Current user missing WooCommerce capabilities: %s', 'wc-role-permission-health-check'), implode(', ', $missing_caps));
            $this->issues_found[] = 'wc_user_caps_missing';
        }
        
        return $result;
    }
    
    /**
     * Check admin menu hooks
     */
    private function check_admin_menus() {
        global $menu, $submenu;
        $result = array('status' => 'good', 'message' => __('Admin menus are loading properly', 'wc-role-permission-health-check'));
        
        if (!class_exists('WooCommerce')) {
            return $result;
        }
        
        // Check if WooCommerce menu exists using multiple methods
        $wc_menu_found = false;
        
        // Method 1: Check global $menu array
        if (is_array($menu)) {
            foreach ($menu as $menu_item) {
                if (isset($menu_item[2]) && strpos($menu_item[2], 'woocommerce') !== false) {
                    $wc_menu_found = true;
                    break;
                }
            }
        }
        
        // Method 2: Check if WooCommerce menu function exists and user has capability
        if (!$wc_menu_found && current_user_can('edit_others_shop_orders')) {
            // Try to force WooCommerce to register its menu
            if (class_exists('WC_Admin_Menus')) {
                // Check if the menu registration hook has been fired
                if (did_action('admin_menu')) {
                    // Menu should be registered by now, let's check again
                    if (is_array($menu)) {
                        foreach ($menu as $menu_item) {
                            if (isset($menu_item[2]) && strpos($menu_item[2], 'woocommerce') !== false) {
                                $wc_menu_found = true;
                                break;
                            }
                        }
                    }
                }
            }
        }
        
        // Method 3: Check if WooCommerce admin class exists and is properly loaded
        if (!$wc_menu_found && class_exists('WooCommerce')) {
            // This might be a timing issue, so we'll mark it as a warning instead of critical
            $result['status'] = 'warning';
            $result['message'] = __('WooCommerce admin menu may not be visible (timing issue or admin class not loaded)', 'wc-role-permission-health-check');
            $this->issues_found[] = 'wc_menu_missing';
        }
        
        return $result;
    }
    
    /**
     * Check user meta corruption
     */
    private function check_user_meta() {
        global $wpdb;
        $result = array('status' => 'good', 'message' => __('User meta is intact', 'wc-role-permission-health-check'));
        
        $current_user = wp_get_current_user();
        
        // Check for corrupted capabilities
        $user_caps = get_user_meta($current_user->ID, $wpdb->prefix . 'capabilities', true);
        if (!is_array($user_caps)) {
            $result['status'] = 'critical';
            $result['message'] = __('User capabilities meta is corrupted', 'wc-role-permission-health-check');
            $this->issues_found[] = 'user_caps_corrupted';
            return $result;
        }
        
        // Check for missing administrator capability
        if (!isset($user_caps['administrator'])) {
            $result['status'] = 'critical';
            $result['message'] = __('User is missing administrator capability', 'wc-role-permission-health-check');
            $this->issues_found[] = 'user_admin_missing';
        }
        
        return $result;
    }
    
    /**
     * Check options corruption
     */
    private function check_options() {
        $result = array('status' => 'good', 'message' => __('WordPress options are intact', 'wc-role-permission-health-check'));
        
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
                $result['message'] = sprintf(__('Essential option missing: %s', 'wc-role-permission-health-check'), $option);
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
        $result = array('status' => 'good', 'message' => __('File permissions are correct', 'wc-role-permission-health-check'));
        
        // Check if we can write to uploads directory
        $upload_dir = wp_upload_dir();
        if (!is_writable($upload_dir['basedir'])) {
            $result['status'] = 'warning';
            $result['message'] = __('Uploads directory is not writable', 'wc-role-permission-health-check');
            $this->issues_found[] = 'uploads_not_writable';
        }
        
        return $result;
    }
    
    /**
     * Check for plugin conflicts
     */
    private function check_plugin_conflicts() {
        $result = array('status' => 'good', 'message' => __('No conflicting plugins detected', 'wc-role-permission-health-check'));
        
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
            $result['message'] = sprintf(__('Potentially conflicting plugins active: %s', 'wc-role-permission-health-check'), implode(', ', $problematic_active));
            $this->issues_found[] = 'plugin_conflicts';
        }
        
        return $result;
    }
    
    /**
     * Check for custom code interference
     */
    private function check_custom_code_interference() {
        $result = array('status' => 'good', 'message' => __('No obvious code interference detected', 'wc-role-permission-health-check'));
        
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
                    $result['message'] = __('Theme functions.php contains code that might interfere with admin menus', 'wc-role-permission-health-check');
                    $this->issues_found[] = 'theme_interference';
                    break;
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Apply fixes for found issues
     */
    public function apply_fixes() {
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
                case 'wc_menu_missing':
                    $results[] = $this->fix_wc_menu_missing();
                    break;
                default:
                    $results[] = array('status' => 'skipped', 'message' => sprintf(__('No fix available for issue: %s', 'wc-role-permission-health-check'), $issue));
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
        return array('status' => 'fixed', 'message' => __('Administrator role has been restored', 'wc-role-permission-health-check'));
    }
    
    /**
     * Fix missing administrator capabilities
     */
    private function fix_admin_caps_missing() {
        $admin_role = get_role('administrator');
        if (!$admin_role) {
            return array('status' => 'failed', 'message' => __('Administrator role does not exist', 'wc-role-permission-health-check'));
        }
        
        // Add essential capabilities
        $essential_caps = array(
            'manage_options' => true,
            'manage_woocommerce' => true,
            'view_woocommerce_reports' => true,
            'edit_shop_orders' => true,
            'edit_products' => true,
            'manage_product_terms' => true,
            'edit_shop_coupons' => true,
            'edit_others_shop_orders' => true
        );
        
        foreach ($essential_caps as $cap => $grant) {
            $admin_role->add_cap($cap, $grant);
        }
        
        $this->fixes_applied[] = 'admin_caps_restored';
        return array('status' => 'fixed', 'message' => __('Administrator capabilities have been restored', 'wc-role-permission-health-check'));
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
            'edit_shop_coupons',
            'edit_others_shop_orders'
        );
        
        foreach ($wc_capabilities as $cap) {
            $current_user->add_cap($cap);
        }
        
        $this->fixes_applied[] = 'wc_user_caps_restored';
        return array('status' => 'fixed', 'message' => __('WooCommerce user capabilities have been restored', 'wc-role-permission-health-check'));
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
        return array('status' => 'fixed', 'message' => __('User capabilities have been reset', 'wc-role-permission-health-check'));
    }
    
    /**
     * Fix missing administrator capability for user
     */
    private function fix_user_admin_missing() {
        $current_user = wp_get_current_user();
        $current_user->add_role('administrator');
        
        $this->fixes_applied[] = 'user_admin_restored';
        return array('status' => 'fixed', 'message' => __('Administrator role has been added to user', 'wc-role-permission-health-check'));
    }
    
    /**
     * Fix missing WooCommerce tables
     */
    private function fix_wc_table_missing() {
        if (!class_exists('WooCommerce')) {
            return array('status' => 'failed', 'message' => __('WooCommerce is not active', 'wc-role-permission-health-check'));
        }
        
        // Force WooCommerce to recreate tables
        if (class_exists('WC_Install')) {
            WC_Install::create_tables();
            $this->fixes_applied[] = 'wc_tables_created';
            return array('status' => 'fixed', 'message' => __('WooCommerce tables have been recreated', 'wc-role-permission-health-check'));
        }
        
        return array('status' => 'failed', 'message' => __('Could not recreate WooCommerce tables', 'wc-role-permission-health-check'));
    }
    
    /**
     * Fix missing WooCommerce admin menu
     */
    private function fix_wc_menu_missing() {
        if (!class_exists('WooCommerce')) {
            return array('status' => 'failed', 'message' => __('WooCommerce is not active', 'wc-role-permission-health-check'));
        }
        
        // Ensure user has the required capability
        if (!current_user_can('edit_others_shop_orders')) {
            $current_user = wp_get_current_user();
            $current_user->add_cap('edit_others_shop_orders');
            $this->fixes_applied[] = 'wc_menu_cap_added';
        }
        
        // Try to force WooCommerce to register its admin menu
        if (class_exists('WC_Admin_Menus')) {
            // Clear any cached menu data
            delete_transient('wc_admin_menu_cache');
            
            // Force WooCommerce to re-register its admin menu
            do_action('admin_menu');
            
            $this->fixes_applied[] = 'wc_menu_forced_registration';
            return array('status' => 'fixed', 'message' => __('WooCommerce admin menu registration has been forced. Please refresh the page.', 'wc-role-permission-health-check'));
        }
        
        return array('status' => 'warning', 'message' => __('WooCommerce admin menu fix attempted. Please refresh the page to see if the menu appears.', 'wc-role-permission-health-check'));
    }
    
    /**
     * Format health check results for display
     */
    public function format_health_results($results) {
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
            $html .= "<div class='health-issue good'><strong>✓ " . __('All checks passed!', 'wc-role-permission-health-check') . "</strong><br>" . __('No issues found with your WooCommerce installation.', 'wc-role-permission-health-check') . "</div>";
        } else {
            $html .= "<div class='health-issue warning'><strong>⚠ " . sprintf(__('Issues found: %d', 'wc-role-permission-health-check'), count($this->issues_found)) . "</strong><br>" . __('Click "Apply Fixes" to attempt automatic repairs.', 'wc-role-permission-health-check') . "</div>";
        }
        
        return $html;
    }
    
    /**
     * Format fix results for display
     */
    public function format_fix_results($results) {
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
            $html .= "<div class='health-issue good'><strong>✓ " . sprintf(__('Fixes applied: %d', 'wc-role-permission-health-check'), count($this->fixes_applied)) . "</strong><br>" . __('Please refresh your WordPress admin and check if the WooCommerce menu is now visible.', 'wc-role-permission-health-check') . "</div>";
        }
        
        return $html;
    }
    
    /**
     * AJAX handler for health check
     */
    public function ajax_health_check() {
        check_ajax_referer('wc_rphc_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'wc-role-permission-health-check'));
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
        check_ajax_referer('wc_rphc_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'wc-role-permission-health-check'));
        }
        
        $results = $this->apply_fixes();
        
        wp_send_json_success(array(
            'html' => $this->format_fix_results($results)
        ));
    }
    
    /**
     * Run initial health check on plugin activation
     */
    public function run_initial_check() {
        $results = $this->run_health_check();
        
        if (!empty($this->issues_found)) {
            wc_rphc_log('Initial health check found issues', array('issues' => $this->issues_found));
        }
        
        return $results;
    }
    
    /**
     * Cron job health check
     */
    public function cron_health_check() {
        $results = $this->run_health_check();
        
        if (!empty($this->issues_found)) {
            // Send notification email
            $this->send_notification_email($results);
            
            // Auto-fix if configured
            if (get_option('wc_rphc_auto_fix', false)) {
                $this->apply_fixes();
            }
        }
    }
    
    /**
     * Send notification email
     */
    private function send_notification_email($results) {
        $admin_email = get_option('admin_email');
        $subject = __('WooCommerce Role Health Issue Detected', 'wc-role-permission-health-check');
        $message = __('Missing WooCommerce capabilities detected:', 'wc-role-permission-health-check') . "\n\n";
        $message .= __('Missing capabilities: ', 'wc-role-permission-health-check') . implode(', ', $this->issues_found) . "\n\n";
        $message .= __('Please check the WC Role Health Checker in your WordPress admin.', 'wc-role-permission-health-check') . "\n";
        $message .= __('Site: ', 'wc-role-permission-health-check') . get_site_url();
        
        wp_mail($admin_email, $subject, $message);
    }
    
    /**
     * Get issues found
     */
    public function get_issues_found() {
        return $this->issues_found;
    }
    
    /**
     * Get fixes applied
     */
    public function get_fixes_applied() {
        return $this->fixes_applied;
    }
} 