# Troubleshooting Guide

This guide helps you resolve common issues with the WooCommerce Role Permission Health Check plugin.

## Common Issues

### 1. WooCommerce Menu Still Missing After Fix

**Symptoms:**
- Health check shows "fixed" but WooCommerce menu still not visible
- "Apply Fixes" completed successfully but no change

**Solutions:**

#### Check Browser Cache
```bash
# Clear browser cache and cookies
# Or use incognito/private browsing mode
```

#### Verify WordPress Cache
```php
// Add to wp-config.php temporarily
define('WP_CACHE', false);
```

#### Check for Plugin Conflicts
1. Deactivate all plugins except WooCommerce
2. Test if WooCommerce menu appears
3. Reactivate plugins one by one
4. Identify conflicting plugin

#### Manual Menu Check
```php
// Add to functions.php temporarily
add_action('admin_init', function() {
    global $menu;
    error_log('Admin menu: ' . print_r($menu, true));
});
```

### 2. "You are not allowed to access this page" Error

**Symptoms:**
- Cannot access WooCommerce settings
- Permission denied errors
- Admin user locked out

**Solutions:**

#### Emergency Recovery
1. Add `?wc_emergency_recovery=true&_wpnonce=YOUR_NONCE` to any admin URL
2. Or use the Nuclear Repair option in the plugin

#### Manual Capability Check
```php
// Add to functions.php temporarily
add_action('admin_init', function() {
    $user = wp_get_current_user();
    error_log('User capabilities: ' . print_r($user->allcaps, true));
});
```

#### Reset User Capabilities
```php
// Add to functions.php temporarily (use with caution)
add_action('init', function() {
    $user = wp_get_current_user();
    $user->add_role('administrator');
    $user->add_cap('manage_woocommerce');
    $user->add_cap('edit_others_shop_orders');
});
```

### 3. Plugin Won't Activate

**Symptoms:**
- Fatal error on activation
- "Plugin could not be activated" message
- White screen of death

**Solutions:**

#### Check PHP Version
```bash
# Check PHP version
php -v
# Should be 7.4 or higher
```

#### Check WordPress Version
```php
// Add to functions.php temporarily
add_action('admin_notices', function() {
    echo '<div class="notice notice-info">WordPress Version: ' . get_bloginfo('version') . '</div>';
});
```

#### Enable Debug Mode
```php
// Add to wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

#### Check File Permissions
```bash
# Set correct permissions
chmod 644 wp-content/plugins/woocommerce-role-permission-health-check/*.php
chmod 755 wp-content/plugins/woocommerce-role-permission-health-check/
```

### 4. AJAX Errors

**Symptoms:**
- "AJAX error occurred" messages
- Health check fails to complete
- Fixes not applying

**Solutions:**

#### Check Nonce
```php
// Verify nonce is being generated
add_action('admin_footer', function() {
    echo '<script>console.log("Nonce: " + wcRphc.nonce);</script>';
});
```

#### Check AJAX URL
```php
// Verify AJAX URL
add_action('admin_footer', function() {
    echo '<script>console.log("AJAX URL: " + wcRphc.ajax_url);</script>';
});
```

#### Disable Other AJAX Handlers
1. Deactivate other plugins temporarily
2. Test if AJAX works
3. Identify conflicting plugin

### 5. Database Connection Issues

**Symptoms:**
- "Database table missing" errors
- Cannot connect to database
- SQL errors in logs

**Solutions:**

#### Check Database Connection
```php
// Add to functions.php temporarily
add_action('admin_notices', function() {
    global $wpdb;
    if ($wpdb->last_error) {
        echo '<div class="notice notice-error">DB Error: ' . $wpdb->last_error . '</div>';
    }
});
```

#### Repair Database Tables
```php
// Add to functions.php temporarily
add_action('init', function() {
    global $wpdb;
    $wpdb->query("REPAIR TABLE {$wpdb->options}");
    $wpdb->query("REPAIR TABLE {$wpdb->usermeta}");
});
```

### 6. Memory Limit Exceeded

**Symptoms:**
- "Allowed memory size exhausted" errors
- Plugin times out during health check
- White screen during operations

**Solutions:**

#### Increase Memory Limit
```php
// Add to wp-config.php
define('WP_MEMORY_LIMIT', '512M');
```

#### Increase PHP Limits
```ini
; Add to php.ini or .htaccess
memory_limit = 512M
max_execution_time = 300
max_input_vars = 3000
```

### 7. Plugin Conflicts

**Symptoms:**
- Health check shows false positives
- Fixes don't work properly
- Unexpected behavior

**Common Conflicting Plugins:**
- User Role Editor
- Members
- Capability Manager Enhanced
- Admin Menu Editor
- White Label CMS

**Solutions:**

#### Test in Isolation
1. Deactivate all plugins except WooCommerce
2. Test the health checker
3. Reactivate plugins one by one
4. Document conflicts

#### Check for Custom Code
```php
// Search for problematic code in functions.php
// Look for:
// - remove_menu_page
// - remove_submenu_page
// - current_user_can modifications
// - admin_menu hooks
```

## Advanced Troubleshooting

### Debug Mode

Enable comprehensive debugging:

```php
// Add to wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
define('SAVEQUERIES', true);

// Add to functions.php
add_action('shutdown', function() {
    if (defined('SAVEQUERIES') && SAVEQUERIES) {
        global $wpdb;
        error_log('Queries: ' . print_r($wpdb->queries, true));
    }
});
```

### Database Inspection

Check database integrity:

```sql
-- Check user roles
SELECT * FROM wp_options WHERE option_name = 'wp_user_roles';

-- Check user capabilities
SELECT user_id, meta_key, meta_value 
FROM wp_usermeta 
WHERE meta_key LIKE '%capabilities%';

-- Check WooCommerce tables
SHOW TABLES LIKE 'wp_woocommerce%';
```

### File System Check

Verify plugin files:

```bash
# Check file structure
ls -la wp-content/plugins/woocommerce-role-permission-health-check/

# Check file permissions
find wp-content/plugins/woocommerce-role-permission-health-check/ -type f -exec ls -la {} \;

# Check for syntax errors
php -l wp-content/plugins/woocommerce-role-permission-health-check/woocommerce-role-permission-health-check.php
```

## Emergency Recovery

### Complete Lockout

If you're completely locked out:

1. **Via FTP/SFTP:**
   ```php
   // Add to functions.php
   add_action('init', function() {
       $user = get_user_by('login', 'your_admin_username');
       if ($user) {
           $user->add_role('administrator');
           $user->add_cap('manage_woocommerce');
       }
   });
   ```

2. **Via Database:**
   ```sql
   -- Reset user capabilities
   UPDATE wp_usermeta 
   SET meta_value = 'a:1:{s:13:"administrator";b:1;}' 
   WHERE user_id = YOUR_USER_ID AND meta_key = 'wp_capabilities';
   ```

3. **Via Emergency Recovery URL:**
   ```
   https://yoursite.com/wp-admin/?wc_emergency_recovery=true&_wpnonce=YOUR_NONCE
   ```

### Nuclear Repair

Use the Nuclear Repair option in the plugin:

1. Go to **Tools > WC Role Health**
2. Click **Nuclear Repair**
3. Confirm the action
4. Wait for completion
5. Refresh the page

## Getting Help

### Before Contacting Support

1. **Gather Information:**
   - WordPress version
   - WooCommerce version
   - PHP version
   - Plugin version
   - Error messages
   - Debug log entries

2. **Test Steps:**
   - Disable all plugins
   - Switch to default theme
   - Test on staging site
   - Document exact steps to reproduce

3. **Check Resources:**
   - WordPress.org support forums
   - WooCommerce documentation
   - Plugin GitHub issues

### Contact Information

- **GitHub Issues:** [Report a Bug](https://github.com/yourusername/woocommerce-role-permission-health-check/issues)
- **Support Forum:** [WordPress.org](https://wordpress.org/support/)
- **Documentation:** [Plugin Wiki](https://github.com/yourusername/woocommerce-role-permission-health-check/wiki)

### Providing Debug Information

When reporting issues, include:

```php
// System information
echo 'WordPress: ' . get_bloginfo('version') . "\n";
echo 'WooCommerce: ' . WC()->version . "\n";
echo 'PHP: ' . PHP_VERSION . "\n";
echo 'Plugin: ' . WC_RPHC_VERSION . "\n";

// User information
$user = wp_get_current_user();
echo 'User: ' . $user->user_login . "\n";
echo 'Roles: ' . implode(', ', $user->roles) . "\n";

// Error log
if (file_exists(WP_CONTENT_DIR . '/debug.log')) {
    echo 'Recent errors: ' . file_get_contents(WP_CONTENT_DIR . '/debug.log');
}
``` 