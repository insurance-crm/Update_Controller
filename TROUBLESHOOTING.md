# Troubleshooting Database Issues

## Common Database Problems and Solutions

### Tables Not Created on Activation

**Symptom:**
```
WordPress database error: [Table 'database_name.prefix_uc_sites' doesn't exist]
SELECT * FROM prefix_uc_sites ORDER BY created_at DESC
```

**Cause:**
The `dbDelta()` function is very particular about SQL syntax. It doesn't support `IF NOT EXISTS` clause.

**Solution:**
The plugin now uses proper `dbDelta()`-compatible SQL:
```php
CREATE TABLE table_name (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    ...
    PRIMARY KEY  (id)  // Note: Two spaces after PRIMARY KEY
) $charset_collate;
```

**How to Fix:**
1. Deactivate the plugin
2. Reactivate the plugin (this will run the activation hook again)
3. Tables will be created automatically

### Manual Table Creation

If automatic creation fails, you can create tables manually:

```sql
-- Sites table
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

-- Plugins table
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

**Note:** Replace `wp_` with your WordPress database prefix (check `wp-config.php` for `$table_prefix`).

### Checking Table Prefix

To find your WordPress table prefix:

1. Open `wp-config.php`
2. Find the line: `$table_prefix = 'wp_';`
3. Your tables will be named: `{prefix}uc_sites` and `{prefix}uc_plugins`

Example:
- If prefix is `wp_`: Tables are `wp_uc_sites` and `wp_uc_plugins`
- If prefix is `crmu_`: Tables are `crmu_uc_sites` and `crmu_uc_plugins`

### Verifying Tables Exist

```sql
-- Check if tables exist
SHOW TABLES LIKE '%uc_sites';
SHOW TABLES LIKE '%uc_plugins';

-- View table structure
DESCRIBE wp_uc_sites;
DESCRIBE wp_uc_plugins;

-- Count records
SELECT COUNT(*) FROM wp_uc_sites;
SELECT COUNT(*) FROM wp_uc_plugins;
```

### Reinstalling Plugin Tables

To completely reinstall the plugin tables:

1. **Backup data first** (if you have important data):
   ```sql
   -- Backup sites
   CREATE TABLE wp_uc_sites_backup AS SELECT * FROM wp_uc_sites;
   
   -- Backup plugins
   CREATE TABLE wp_uc_plugins_backup AS SELECT * FROM wp_uc_plugins;
   ```

2. **Drop tables:**
   ```sql
   DROP TABLE IF EXISTS wp_uc_plugins;
   DROP TABLE IF EXISTS wp_uc_sites;
   ```

3. **Reactivate plugin:**
   - Go to Plugins menu
   - Deactivate "Update Controller"
   - Activate "Update Controller"
   - Tables will be recreated

4. **Restore data** (if backed up):
   ```sql
   INSERT INTO wp_uc_sites SELECT * FROM wp_uc_sites_backup;
   INSERT INTO wp_uc_plugins SELECT * FROM wp_uc_plugins_backup;
   ```

### dbDelta() Best Practices

The WordPress `dbDelta()` function requires very specific SQL formatting:

✅ **Correct:**
```php
CREATE TABLE table_name (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    PRIMARY KEY  (id)  // Two spaces after PRIMARY KEY
) $charset_collate;
```

❌ **Incorrect:**
```php
CREATE TABLE IF NOT EXISTS table_name (  // Don't use IF NOT EXISTS
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    PRIMARY KEY(id)  // Missing space after PRIMARY KEY
) $charset_collate;
```

**Key Rules:**
- Don't use `IF NOT EXISTS`
- Put two spaces between `PRIMARY KEY` and the definition
- Use exact spacing and formatting
- Don't use MySQL-specific features

### Checking Plugin Activation Logs

If tables still don't create:

1. Enable WordPress debugging in `wp-config.php`:
   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   define('WP_DEBUG_DISPLAY', false);
   ```

2. Check debug log at `wp-content/debug.log`

3. Look for database errors during activation

### Database Permissions

Ensure your WordPress database user has proper permissions:

```sql
-- Check user permissions
SHOW GRANTS FOR 'your_db_user'@'localhost';

-- Should include at least:
-- CREATE, ALTER, DROP, SELECT, INSERT, UPDATE, DELETE
```

### Common Errors and Solutions

| Error | Cause | Solution |
|-------|-------|----------|
| Table doesn't exist | Not created on activation | Deactivate/reactivate plugin |
| Access denied | Insufficient permissions | Check database user permissions |
| Syntax error | dbDelta formatting | Use proper spacing and no IF NOT EXISTS |
| Character set issues | Collation mismatch | Use utf8mb4_unicode_ci |

### Getting Help

If you continue to have database issues:

1. Check the debug log for specific errors
2. Verify database user permissions
3. Try manual table creation (see above)
4. Check WordPress version (5.0+ required)
5. Report issue with debug log details

### Prevention

To avoid database issues:

- Keep WordPress updated
- Don't modify plugin files
- Use proper database backups
- Test on staging environment first
- Check PHP and MySQL versions meet requirements
