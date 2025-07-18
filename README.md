# WooCommerce Role & Permission Health Check

[![WordPress Plugin](https://img.shields.io/badge/WordPress-Plugin-blue.svg)](https://wordpress.org/)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-Compatible-purple.svg)](https://woocommerce.com/)
[![License](https://img.shields.io/badge/License-GPL%20v2-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

A comprehensive diagnostic and repair tool for WooCommerce permission issues, missing admin menus, and corrupted user roles.

## 🚨 Problem This Solves

Have you ever encountered these frustrating WooCommerce issues?

- ❌ **WooCommerce admin menu completely missing** from WordPress dashboard
- ❌ **"Sorry, you are not allowed to access this page"** errors for WooCommerce settings
- ❌ **Fatal errors when activating WooCommerce** plugin
- ❌ **Admin users losing WooCommerce access** after plugin/theme changes
- ❌ **Permission corruption** causing complete WooCommerce lockout

This plugin diagnoses and fixes these complex issues automatically.

## ✨ Features

### 🔍 Comprehensive Health Checks
- **WooCommerce Plugin Status** - Verifies plugin activation and core class availability
- **User Roles & Capabilities** - Checks for missing administrator role and essential permissions
- **Database Integrity** - Validates essential WordPress and WooCommerce tables
- **Admin Menu Detection** - Identifies missing WooCommerce admin menus
- **User Meta Corruption** - Detects corrupted user capability metadata
- **Plugin Conflict Detection** - Identifies known problematic plugins
- **Custom Code Interference** - Scans theme files for menu-blocking code
- **File Permission Validation** - Checks basic file system permissions

### 🛠️ Automated Repair Tools
- **Role Restoration** - Recreate missing administrator role
- **Capability Repair** - Fix corrupted user capabilities and permissions
- **Database Recovery** - Recreate missing WooCommerce tables
- **Permission Reset** - Reset user roles and permissions to defaults
- **Emergency Access** - Create emergency admin user for complete lockouts

### 🚑 Emergency Features
- **Nuclear Permission Repair** - Complete role system reset for extreme cases
- **Emergency Recovery URL** - Direct access via URL parameter when locked out
- **System Backup** - Automatic backup before applying major fixes
- **Detailed Logging** - Track all fixes and changes applied

## 📋 Requirements

- **WordPress:** 5.0 or higher
- **WooCommerce:** 5.0 or higher (for WooCommerce-specific features)
- **PHP:** 7.4 or higher
- **User Role:** Administrator access required

## 🚀 Installation

### Method 1: Manual Installation
1. Download the plugin files
2. Upload to `/wp-content/plugins/woocommerce-role-permission-health-check/`
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Navigate to **Tools > WC Role Health** to access the diagnostic tool

### Method 2: Upload via WordPress Admin
1. Download the plugin ZIP file
2. Go to **Plugins > Add New > Upload Plugin**
3. Choose the ZIP file and click **Install Now**
4. Activate the plugin

## 📖 Usage

<img width="2292" height="4344" alt="CleanShot 2025-07-18 at 15 54 19@2x" src="https://github.com/user-attachments/assets/d98f6940-879f-4529-89f5-a3acecda7187" />

### Basic Health Check
1. Go to **Tools > WC Role Health** in your WordPress admin
2. Click **"Run Health Check"** to scan for issues
3. Review the diagnostic results
4. Click **"Apply Fixes"** if issues are found

### Emergency Recovery
If you're completely locked out of WooCommerce:

1. Add `?wc_emergency_recovery=true` to any admin URL
2. The plugin will attempt automatic permission repair
3. Check **Tools > WC Role Health** after recovery

### Advanced Options
- **Nuclear Permission Repair** - Use for severe corruption cases
- **Emergency Admin Creation** - Creates new admin user if needed
- **System Information Export** - Generate detailed diagnostic report

## 🔧 What Gets Fixed

### Common Issues Resolved:
- ✅ Missing WooCommerce admin menu
- ✅ "Not authorized" errors for admin users
- ✅ Corrupted user capability metadata
- ✅ Missing administrator role
- ✅ Incomplete WooCommerce permissions
- ✅ Database table corruption
- ✅ Plugin conflict interference
- ✅ Theme code blocking admin access

### Real-World Success Stories:
This plugin was specifically designed to solve complex cases like:
- WooCommerce menu disappeared after plugin conflicts
- Admin users suddenly losing all WooCommerce access
- Fatal errors preventing WooCommerce reactivation
- Permission corruption after hosting migrations
- Access issues after developer handoffs

## ⚠️ Safety Features

- **Automatic Backups** - Creates backup before major changes
- **Safe Mode Operation** - Won't break existing functionality
- **Detailed Logging** - Tracks every change made
- **Rollback Capability** - Can restore previous state if needed
- **Non-Destructive Scans** - Diagnostic mode doesn't modify anything

## 🔒 Security Considerations

- Only users with `manage_options` capability can use this plugin
- All AJAX requests are nonce-protected
- Emergency features require additional verification
- Sensitive operations are logged for audit trails

## 🆘 Emergency Situations

### Complete WooCommerce Lockout:
1. Access: `yoursite.com/wp-admin/?wc_emergency_recovery=true`
2. Plugin will attempt automatic recovery
3. New emergency admin user may be created if needed

### Can't Access WordPress Admin:
1. Add plugin files via FTP
2. Use emergency recovery URL
3. Check hosting control panel for database access

`wordpress` `woocommerce` `permissions` `user-roles` `admin-menu` `debugging` `repair-tool` `plugin-conflicts` `emergency-recovery` `capability-management`

---

**Made with ❤️ for the WordPress community**

*This plugin was created to solve real-world WooCommerce permission nightmares that standard troubleshooting can't fix.*
