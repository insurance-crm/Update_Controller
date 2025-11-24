# Quick Reference Card

Essential commands and information for Update Controller.

## Plugin Files

```
update-controller/
├── update-controller.php          # Main plugin
├── includes/                      # Core classes
│   ├── class-uc-admin.php        # Admin interface
│   ├── class-uc-database.php     # Database operations
│   ├── class-uc-encryption.php   # Password encryption
│   └── class-uc-updater.php      # Update logic
├── templates/                     # UI templates
├── assets/                        # CSS/JS files
└── companion-plugin/             # For target sites
    └── update-controller-companion.php
```

## Installation Checklist

### Controller Site
- [ ] Upload main plugin to `/wp-content/plugins/`
- [ ] Activate plugin
- [ ] Verify menu appears

### Target Sites (Each)
- [ ] Upload companion plugin
- [ ] Activate companion plugin
- [ ] Create Application Password
- [ ] Test REST API endpoint

## Quick Setup

```bash
# 1. Controller Site
Upload update-controller/ → /wp-content/plugins/
Activate → Plugins → Update Controller

# 2. Target Sites
Upload companion-plugin/ → /wp-content/plugins/
Activate → Plugins → Update Controller Companion

# 3. Configure
Controller → Add Site → Fill Form
Controller → Add Plugin → Configure Update
Controller → Test → Click "Update Now"
```

## Admin URLs

```
Controller Site:
/wp-admin/admin.php?page=update-controller        # Sites page
/wp-admin/admin.php?page=update-controller-plugins # Plugins page

Target Site:
/wp-json/uc-companion/v1/                          # API endpoints
```

## Database Tables

```sql
-- Sites table
wp_uc_sites
  - id, site_url, site_name, username
  - password (encrypted), status, last_update

-- Plugins table  
wp_uc_plugins
  - id, site_id, plugin_slug, plugin_name
  - update_source, source_type, auto_update
```

## Common Plugin Slugs

```
akismet/akismet.php
jetpack/jetpack.php
wordpress-seo/wp-seo.php
contact-form-7/wp-contact-form-7.php
woocommerce/woocommerce.php
[directory-name]/[main-file].php
```

## Update Source Examples

```
# Web URL
https://example.com/plugins/my-plugin.zip
https://cdn.example.com/downloads/plugin-v1.0.0.zip

# GitHub Repository
https://github.com/user/repo
https://github.com/user/repo/archive/refs/heads/main.zip
https://github.com/user/repo/releases/download/v1.0.0/plugin.zip
```

## REST API Endpoints

### Companion Plugin Endpoints

```http
# Install/Update Plugin
POST /wp-json/uc-companion/v1/install-plugin
Body: { "file_id": 123 }

# Activate Plugin
POST /wp-json/uc-companion/v1/activate-plugin
Body: { "plugin_slug": "plugin-name/plugin-name.php" }

# Deactivate Plugin
POST /wp-json/uc-companion/v1/deactivate-plugin
Body: { "plugin_slug": "plugin-name/plugin-name.php" }
```

## AJAX Actions

### Controller Plugin AJAX

```javascript
// Sites
wp_ajax_uc_add_site
wp_ajax_uc_update_site
wp_ajax_uc_delete_site

// Plugins
wp_ajax_uc_add_plugin
wp_ajax_uc_update_plugin
wp_ajax_uc_delete_plugin

// Updates
wp_ajax_uc_run_update
```

## WordPress Hooks

```php
// Activation
register_activation_hook()
  → UC_Database::create_tables()
  → wp_schedule_event()

// Deactivation  
register_deactivation_hook()
  → wp_unschedule_event()

// Scheduled Updates
add_action('uc_scheduled_update', ...)
  → Runs daily (default)
```

## Configuration

### Application Password Setup

```
Target Site:
1. Users → Profile
2. Scroll to "Application Passwords"
3. Enter name: "Update Controller"
4. Click "Add New Application Password"
5. Copy: xxxx xxxx xxxx xxxx xxxx xxxx
6. Use in controller (keep spaces!)
```

### AUTH_KEY Configuration

```php
// wp-config.php
define('AUTH_KEY', 'unique-key-here');

// Generate at:
// https://api.wordpress.org/secret-key/1.1/salt/
```

## Troubleshooting Commands

### Check REST API
```bash
curl https://target-site.com/wp-json/
curl https://target-site.com/wp-json/uc-companion/v1/
```

### Test Authentication
```bash
curl -u username:password \
  https://target-site.com/wp-json/wp/v2/users/me
```

### Check Cron Events
```
Install: WP Crontrol plugin
Navigate: Tools → Cron Events
Find: uc_scheduled_update
```

### Check File Permissions
```bash
# Target site plugins directory
ls -la /path/to/wp-content/plugins/
# Should be writable by web server
```

## Error Messages

### Common Errors

| Error | Cause | Solution |
|-------|-------|----------|
| Authentication failed | Wrong credentials | Check username/password |
| Plugin not found | Wrong slug | Verify plugin slug |
| REST API 404 | Companion not active | Activate companion plugin |
| Install failed | Permission issue | Check file permissions |
| Upload failed | Large file/timeout | Increase upload limits |

## Security Best Practices

```
✓ Use HTTPS everywhere
✓ Use Application Passwords
✓ Set unique AUTH_KEY
✓ Keep WordPress updated
✓ Backup before updates
✓ Limit admin access
✗ Don't use HTTP
✗ Don't use main passwords
✗ Don't skip backups
```

## Performance Tips

```
# Update Frequency
Daily (default)    → wp_schedule_event(time(), 'daily', ...)
Hourly            → wp_schedule_event(time(), 'hourly', ...)
Twice daily       → wp_schedule_event(time(), 'twicedaily', ...)

# Timeout Settings
Upload: 60s  (wp_remote_post timeout)
Install: 120s (companion plugin processing)
```

## File Size Limits

```php
// Increase if needed in php.ini
upload_max_filesize = 64M
post_max_size = 64M
max_execution_time = 300
memory_limit = 256M
```

## Support Resources

```
📖 README.md          → Overview & features
📖 INSTALLATION.md    → Step-by-step setup
📖 EXAMPLES.md        → Configuration examples
📖 FAQ.md             → Common questions
📖 SECURITY.md        → Security policy
📖 CONTRIBUTING.md    → Development guide
📖 UI-GUIDE.md        → Interface reference
```

## Key Capabilities Required

```php
// Controller Site
'manage_options'     → Admin operations

// Target Sites  
'activate_plugins'   → Install/activate plugins
```

## Version Information

```
Plugin Version: 1.0.0
WordPress Min: 5.0
PHP Min: 7.2
Required: OpenSSL extension
Recommended: WordPress 5.6+ (Application Passwords)
```

## Default Settings

```php
Encryption: AES-256-CBC
Schedule: Daily
Auto-update: Enabled (per plugin)
Timeout: 60s (upload), 120s (install)
```

## Useful Commands

### Find Plugin Slug
```bash
# On target site
cd /path/to/wp-content/plugins
ls -la
# Look for: directory-name/main-file.php
```

### Clear WordPress Cache
```bash
# If updates don't show
wp cache flush
# Or use plugin: WP Super Cache, W3 Total Cache
```

### Test ZIP File
```bash
# Verify plugin ZIP
unzip -l plugin.zip
# Should show: plugin-name/ directory structure
```

## Notes

- **Spaces matter** in Application Passwords!
- **Trailing slashes** in URLs can cause issues
- **Plugin slugs** are case-sensitive
- **GitHub** URLs are auto-converted to ZIP downloads
- **Daily cron** may not run at exact times
- **Backups** are not included - use separate plugin

---

## Need Help?

Check: FAQ.md → INSTALLATION.md → GitHub Issues

Quick Test:
1. Add one site
2. Add one plugin config
3. Click "Update Now"
4. Watch for success/error

Happy Updating! 🚀
