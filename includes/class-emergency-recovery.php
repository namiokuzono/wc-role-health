<?php
/**
 * Emergency Recovery Class
 * 
 * Handles emergency recovery and nuclear repair options for critical situations.
 * 
 * @package WC_Role_Permission_Health_Check
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WC_RPHC_Emergency_Recovery {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_notices', array($this, 'show_emergency_notices'));
    }
    
    /**
     * Attempt emergency recovery
     */
    public function attempt_emergency_recovery() {
        if (!current_user_can('edit_plugins')) {
            wp_die(__('You do not have sufficient permissions for emergency recovery.', 'wc-role-permission-health-check'));
        }
        
        // Check if this is a legitimate emergency recovery request
        if (!isset($_GET['wc_emergency_recovery']) || $_GET['wc_emergency_recovery'] !== 'true') {
            return false;
        }
        
        // Verify nonce for security
        if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'wc_emergency_recovery')) {
            wp_die(__('Security check failed.', 'wc-role-permission-health-check'));
        }
        
        // Attempt nuclear repair
        $result = $this->nuclear_permission_repair();
        
        if ($result) {
            // Redirect with success message
            wp_redirect(admin_url('tools.php?page=wc-role-health&recovery=success'));
            exit;
        } else {
            // Redirect with failure message
            wp_redirect(admin_url('tools.php?page=wc-role-health&recovery=failed'));
            exit;
        }
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
            'current_user_caps' => get_user_meta(get_current_user_id(), $wpdb->prefix . 'capabilities', true),
            'timestamp' => current_time('mysql')
        );
        update_option('wc_rphc_backup_' . time(), $backup);
        
        // Step 1: Completely reset WordPress roles
        delete_option($wpdb->prefix . 'user_roles');
        require_once(ABSPATH . 'wp-admin/includes/schema.php');
        populate_roles();
        
        // Step 2: Reset current user capabilities
        $current_user_id = get_current_user_id();
        update_user_meta($current_user_id, $wpdb->prefix . 'capabilities', array('administrator' => true));
        update_user_meta($current_user_id, $wpdb->prefix . 'user_level', 10);
        
        // Step 3: Reinitialize WooCommerce capabilities if active
        if (class_exists('WooCommerce')) {
            $this->restore_woocommerce_capabilities();
        }
        
        // Step 4: Force WordPress to refresh capabilities
        wp_cache_delete($current_user_id, 'users');
        wp_cache_delete($current_user_id, 'user_meta');
        
        // Log the nuclear repair
        wc_rphc_log('Nuclear permission repair executed', array(
            'user_id' => $current_user_id,
            'backup_created' => true
        ));
        
        return true;
    }
    
    /**
     * Restore WooCommerce capabilities
     */
    private function restore_woocommerce_capabilities() {
        $admin_role = get_role('administrator');
        if (!$admin_role) {
            return false;
        }
        
        // Get WooCommerce core capabilities
        $wc_capabilities = $this->get_woocommerce_capabilities();
        
        foreach ($wc_capabilities as $cap) {
            $admin_role->add_cap($cap);
        }
        
        return true;
    }
    
    /**
     * Get WooCommerce capabilities
     */
    private function get_woocommerce_capabilities() {
        $capabilities = array();
        
        // Core WooCommerce capabilities
        $capabilities['core'] = array(
            'manage_woocommerce',
            'create_customers',
            'view_woocommerce_reports',
        );
        
        // Post type capabilities
        $capability_types = array('product', 'shop_order', 'shop_coupon');
        
        foreach ($capability_types as $capability_type) {
            $capabilities[$capability_type] = array(
                // Post type
                "edit_{$capability_type}",
                "read_{$capability_type}",
                "delete_{$capability_type}",
                "edit_{$capability_type}s",
                "edit_others_{$capability_type}s",
                "publish_{$capability_type}s",
                "read_private_{$capability_type}s",
                "delete_{$capability_type}s",
                "delete_private_{$capability_type}s",
                "delete_published_{$capability_type}s",
                "delete_others_{$capability_type}s",
                "edit_private_{$capability_type}s",
                "edit_published_{$capability_type}s",
                
                // Terms
                "manage_{$capability_type}_terms",
                "edit_{$capability_type}_terms",
                "delete_{$capability_type}_terms",
                "assign_{$capability_type}_terms",
            );
        }
        
        // Flatten the array
        $flat_capabilities = array();
        foreach ($capabilities as $cap_group) {
            if (is_array($cap_group)) {
                foreach ($cap_group as $cap) {
                    $flat_capabilities[] = $cap;
                }
            }
        }
        
        return $flat_capabilities;
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
            $all_caps = $this->get_all_possible_capabilities();
            
            foreach ($all_caps as $cap => $grant) {
                $user->add_cap($cap, $grant);
            }
            
            // Log the emergency user creation
            wc_rphc_log('Emergency admin user created', array(
                'user_id' => $user_id,
                'user_login' => $emergency_user['user_login']
            ));
            
            return array(
                'user_login' => $emergency_user['user_login'],
                'user_pass' => $emergency_user['user_pass'],
                'user_email' => $emergency_user['user_email']
            );
        }
        
        return false;
    }
    
    /**
     * Get all possible WordPress capabilities
     */
    private function get_all_possible_capabilities() {
        return array(
            'manage_options' => true,
            'manage_woocommerce' => true,
            'view_woocommerce_reports' => true,
            'edit_shop_orders' => true,
            'edit_products' => true,
            'manage_product_terms' => true,
            'edit_shop_coupons' => true,
            'edit_others_shop_orders' => true,
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
    }
    
    /**
     * Show emergency notices
     */
    public function show_emergency_notices() {
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
                $critical_issues[] = __('WooCommerce admin menu is missing', 'wc-role-permission-health-check');
            }
        }
        
        // Check if current user has essential capabilities
        if (!current_user_can('manage_woocommerce') && class_exists('WooCommerce')) {
            $critical_issues[] = __('Missing WooCommerce management capabilities', 'wc-role-permission-health-check');
        }
        
        if (!empty($critical_issues)) {
            echo '<div class="notice notice-error is-dismissible">';
            echo '<h3>' . __('WooCommerce Role & Permission Issues Detected', 'wc-role-permission-health-check') . '</h3>';
            echo '<ul>';
            foreach ($critical_issues as $issue) {
                echo '<li>' . esc_html($issue) . '</li>';
            }
            echo '</ul>';
            echo '<p><a href="' . admin_url('tools.php?page=wc-role-health') . '" class="button button-primary">' . __('Run Health Check & Fix', 'wc-role-permission-health-check') . '</a></p>';
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
     * Restore from backup
     */
    public function restore_from_backup($backup_id) {
        global $wpdb;
        
        $backup = get_option('wc_rphc_backup_' . $backup_id);
        if (!$backup) {
            return false;
        }
        
        // Restore user roles
        if (isset($backup['user_roles'])) {
            update_option($wpdb->prefix . 'user_roles', $backup['user_roles']);
        }
        
        // Restore current user capabilities
        if (isset($backup['current_user_caps'])) {
            update_user_meta(get_current_user_id(), $wpdb->prefix . 'capabilities', $backup['current_user_caps']);
        }
        
        // Clear caches
        wp_cache_delete(get_current_user_id(), 'users');
        wp_cache_delete(get_current_user_id(), 'user_meta');
        
        // Log the restoration
        wc_rphc_log('System restored from backup', array('backup_id' => $backup_id));
        
        return true;
    }
    
    /**
     * Get available backups
     */
    public function get_available_backups() {
        global $wpdb;
        
        $backups = array();
        $options = $wpdb->get_results("SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE 'wc_rphc_backup_%'");
        
        foreach ($options as $option) {
            $backup_data = maybe_unserialize($option->option_value);
            $backup_id = str_replace('wc_rphc_backup_', '', $option->option_name);
            
            $backups[$backup_id] = array(
                'timestamp' => $backup_data['timestamp'] ?? 'Unknown',
                'user_roles' => isset($backup_data['user_roles']),
                'user_caps' => isset($backup_data['current_user_caps'])
            );
        }
        
        return $backups;
    }
    
    /**
     * Clean up old backups
     */
    public function cleanup_old_backups($days_to_keep = 30) {
        global $wpdb;
        
        $cutoff_time = time() - ($days_to_keep * DAY_IN_SECONDS);
        $options = $wpdb->get_results("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'wc_rphc_backup_%'");
        
        $deleted_count = 0;
        foreach ($options as $option) {
            $backup_id = str_replace('wc_rphc_backup_', '', $option->option_name);
            if ($backup_id < $cutoff_time) {
                delete_option($option->option_name);
                $deleted_count++;
            }
        }
        
        return $deleted_count;
    }
} 