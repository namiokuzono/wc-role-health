# Installation Guide

This guide will help you install the WooCommerce Role Permission Health Check plugin on your WordPress site.

## Requirements

Before installing the plugin, ensure your system meets these requirements:

- **WordPress:** 5.0 or higher
- **WooCommerce:** 5.0 or higher (for WooCommerce-specific features)
- **PHP:** 7.4 or higher
- **User Role:** Administrator access required

## Installation Methods

### Method 1: Manual Installation (Recommended)

1. **Download the Plugin**
   - Download the plugin files from the repository
   - Extract the ZIP file to your local computer

2. **Upload to WordPress**
   - Connect to your WordPress site via FTP/SFTP
   - Navigate to `/wp-content/plugins/`
   - Upload the `woocommerce-role-permission-health-check` folder

3. **Activate the Plugin**
   - Log in to your WordPress admin dashboard
   - Go to **Plugins > Installed Plugins**
   - Find "WooCommerce Role Permission Health Check"
   - Click **Activate**

### Method 2: WordPress Admin Upload

1. **Download the Plugin**
   - Download the plugin ZIP file from the repository

2. **Upload via WordPress Admin**
   - Log in to your WordPress admin dashboard
   - Go to **Plugins > Add New > Upload Plugin**
   - Click **Choose File** and select the plugin ZIP file
   - Click **Install Now**
   - Click **Activate Plugin**

### Method 3: Git Installation (Advanced)

If you have Git access to your server:

```bash
cd /path/to/wordpress/wp-content/plugins/
git clone https://github.com/yourusername/woocommerce-role-permission-health-check.git
cd woocommerce-role-permission-health-check
composer install  # If using Composer dependencies
```

## Post-Installation Setup

### 1. Verify Installation

After activation, you should see:

- A new menu item: **Tools > WC Role Health**
- No error messages in the WordPress admin
- The plugin listed in **Plugins > Installed Plugins**

### 2. Run Initial Health Check

1. Navigate to **Tools > WC Role Health**
2. Click **Run Health Check**
3. Review the results
4. If issues are found, click **Apply Fixes**

### 3. Configure Settings (Optional)

The plugin works out of the box, but you can configure:

- **Auto-fix:** Enable automatic repair of detected issues
- **Email notifications:** Get notified when issues are detected
- **Backup retention:** How long to keep system backups

## Troubleshooting Installation

### Common Issues

#### Plugin Won't Activate

**Symptoms:** Error message when trying to activate the plugin

**Solutions:**
- Check PHP version (requires 7.4+)
- Verify WordPress version (requires 5.0+)
- Check file permissions (should be 644 for files, 755 for directories)
- Review WordPress debug log for specific errors

#### "Plugin file does not exist" Error

**Symptoms:** WordPress can't find the main plugin file

**Solutions:**
- Verify the plugin folder name is exactly `woocommerce-role-permission-health-check`
- Check that `woocommerce-role-permission-health-check.php` exists in the root of the plugin folder
- Ensure all files were uploaded completely

#### Permission Denied Errors

**Symptoms:** Cannot upload or access plugin files

**Solutions:**
- Check server file permissions
- Contact your hosting provider
- Use FTP/SFTP instead of file manager

### Server Requirements

#### Minimum Server Configuration

```ini
memory_limit = 256M
max_execution_time = 300
upload_max_filesize = 64M
post_max_size = 64M
```

#### Recommended Server Configuration

```ini
memory_limit = 512M
max_execution_time = 600
upload_max_filesize = 128M
post_max_size = 128M
```

### File Permissions

Set the following permissions:

```bash
# Plugin directory
chmod 755 wp-content/plugins/woocommerce-role-permission-health-check/

# Plugin files
chmod 644 wp-content/plugins/woocommerce-role-permission-health-check/*.php
chmod 644 wp-content/plugins/woocommerce-role-permission-health-check/assets/*
chmod 644 wp-content/plugins/woocommerce-role-permission-health-check/includes/*.php

# Assets directory
chmod 755 wp-content/plugins/woocommerce-role-permission-health-check/assets/
chmod 755 wp-content/plugins/woocommerce-role-permission-health-check/includes/
```

## Security Considerations

### Before Installation

1. **Backup Your Site**
   - Database backup
   - File system backup
   - WooCommerce settings export

2. **Test on Staging**
   - Install on a staging environment first
   - Test all functionality
   - Verify no conflicts with existing plugins

### After Installation

1. **Verify Permissions**
   - Only administrators should have access
   - Check that non-admin users cannot access the tool

2. **Monitor Logs**
   - Check WordPress debug log
   - Monitor WooCommerce logs
   - Review server error logs

## Uninstallation

### Safe Uninstallation

1. **Export Settings** (if any)
2. **Run Final Health Check**
3. **Deactivate Plugin**
4. **Delete Plugin Files**

### Complete Removal

To completely remove the plugin and all its data:

1. Deactivate the plugin
2. Delete the plugin folder
3. Remove any database options (optional):
   ```sql
   DELETE FROM wp_options WHERE option_name LIKE 'wc_rphc_%';
   ```

## Support

If you encounter issues during installation:

1. **Check the Troubleshooting Guide**
2. **Review WordPress Debug Log**
3. **Contact Support** with:
   - WordPress version
   - WooCommerce version
   - PHP version
   - Error messages
   - Server environment details

## Next Steps

After successful installation:

1. Read the [Usage Guide](../README.md#usage)
2. Review the [Troubleshooting Guide](TROUBLESHOOTING.md)
3. Check the [Contributing Guidelines](CONTRIBUTING.md)
4. Join the [Community Discussions](https://github.com/yourusername/woocommerce-role-permission-health-check/discussions) 