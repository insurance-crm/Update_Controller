# Database Table Creation - Verification Guide

## How to Verify Tables Are Created Correctly

### Step 1: Enable WordPress Debug Mode

Edit `wp-config.php` and add/update these lines:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Step 2: Deactivate and Reactivate Plugin

1. Go to **Plugins** in WordPress admin
2. Find "Update Controller"
3. Click **Deactivate**
4. Click **Activate**

### Step 3: Check Debug Log

Open `wp-content/debug.log` and look for:

```
Update Controller: Sites table creation result - Array
(
    [wp_uc_sites] => Created table wp_uc_sites
)
Update Controller: Plugins table creation result - Array
(
    [wp_uc_plugins] => Created table wp_uc_plugins
)
```

Note: `wp_` will be your actual table prefix (could be `crmu_`, `wpdb_`, etc.)

### Step 4: Verify Tables in Database

#### Option 1: Using phpMyAdmin
1. Open phpMyAdmin
2. Select your WordPress database
3. Look for tables named `{prefix}uc_sites` and `{prefix}uc_plugins`

#### Option 2: Using MySQL Command Line
```sql
USE your_database_name;
SHOW TABLES LIKE '%uc_sites';
SHOW TABLES LIKE '%uc_plugins';
```

#### Option 3: Using WordPress Plugin
Install "WP-CLI" or "Query Monitor" plugin to inspect database.

### Step 5: Test Plugin Pages

1. Go to **Update Controller > Sites**
   - Should load without database errors
   - Should show "No sites found" message

2. Go to **Update Controller > Plugins**
   - Should load without database errors
   - Should show "No plugin configurations found" message

## Expected Table Structure

### Sites Table (`{prefix}uc_sites`)

```sql
DESC wp_uc_sites;
```

Expected columns:
- id (bigint, PRIMARY KEY, AUTO_INCREMENT)
- site_url (varchar 255, UNIQUE)
- site_name (varchar 255)
- username (varchar 100)
- password (text)
- status (varchar 20, default 'active')
- last_update (datetime, nullable)
- created_at (datetime, default CURRENT_TIMESTAMP)

### Plugins Table (`{prefix}uc_plugins`)

```sql
DESC wp_uc_plugins;
```

Expected columns:
- id (bigint, PRIMARY KEY, AUTO_INCREMENT)
- site_id (bigint)
- plugin_slug (varchar 255)
- plugin_name (varchar 255)
- update_source (varchar 500)
- source_type (varchar 20, default 'web')
- auto_update (tinyint 1, default 1)
- last_update (datetime, nullable)
- created_at (datetime, default CURRENT_TIMESTAMP)

## Troubleshooting

### If Tables Still Don't Exist

#### 1. Check Activation Hook is Firing

Add temporary code to `update-controller.php` activate method:

```php
public static function activate() {
    error_log('UPDATE CONTROLLER: Activation hook fired!');
    UC_Database::create_tables();
    // ... rest of code
}
```

Deactivate/reactivate and check debug.log for the message.

#### 2. Check Database Permissions

Ensure your WordPress database user has CREATE TABLE permission:

```sql
SHOW GRANTS FOR 'your_db_user'@'localhost';
```

Should include: `CREATE` permission.

#### 3. Manually Create Tables

If automatic creation fails, create tables manually:

```sql
-- Replace 'wp_' with your actual prefix
CREATE TABLE `wp_uc_sites` (
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `site_url` varchar(255) NOT NULL,
    `site_name` varchar(255) NOT NULL,
    `username` varchar(100) NOT NULL,
    `password` text NOT NULL,
    `status` varchar(20) DEFAULT 'active',
    `last_update` datetime DEFAULT NULL,
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `site_url` (`site_url`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `wp_uc_plugins` (
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `site_id` bigint(20) UNSIGNED NOT NULL,
    `plugin_slug` varchar(255) NOT NULL,
    `plugin_name` varchar(255) NOT NULL,
    `update_source` varchar(500) NOT NULL,
    `source_type` varchar(20) DEFAULT 'web',
    `auto_update` tinyint(1) DEFAULT 1,
    `last_update` datetime DEFAULT NULL,
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `site_id` (`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 4. Check File Includes

Verify `class-uc-database.php` is being loaded:

```php
// In update-controller.php activate method
public static function activate() {
    if (!class_exists('UC_Database')) {
        error_log('UPDATE CONTROLLER: UC_Database class not found!');
        return;
    }
    UC_Database::create_tables();
    // ...
}
```

#### 5. Check WordPress Version

Ensure WordPress 5.0+ is installed:
```php
global $wp_version;
echo $wp_version;
```

`dbDelta()` may behave differently in older versions.

## Common Errors and Solutions

| Error | Cause | Solution |
|-------|-------|----------|
| Class 'UC_Database' not found | Files not loaded | Check file paths and includes |
| dbDelta not found | upgrade.php not loaded | Check ABSPATH constant |
| Permission denied | DB user lacks CREATE | Grant CREATE permission |
| Syntax error in SQL | dbDelta formatting | Check spacing (2 spaces after PRIMARY KEY) |
| Tables not appearing | Wrong database | Verify database name in wp-config.php |

## Verification Checklist

- [ ] WP_DEBUG enabled in wp-config.php
- [ ] Plugin deactivated
- [ ] Plugin activated
- [ ] Debug log checked for table creation messages
- [ ] Tables exist in database (verified via phpMyAdmin/SQL)
- [ ] Sites page loads without errors
- [ ] Plugins page loads without errors
- [ ] Can add a test site (optional)
- [ ] Can add a test plugin config (optional)

## Success Indicators

✅ Debug log shows table creation messages
✅ Tables visible in phpMyAdmin/database
✅ Admin pages load without "Table doesn't exist" errors
✅ Can interact with Sites and Plugins pages

## Still Having Issues?

If tables still aren't created after following all steps:

1. Provide the debug.log contents
2. Provide WordPress version
3. Provide PHP version
4. Provide MySQL version
5. Provide database user permissions (SHOW GRANTS output)
6. Provide any error messages from PHP error log

This information will help diagnose the specific issue.
