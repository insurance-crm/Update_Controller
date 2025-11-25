# Installation Guide

Complete step-by-step guide for installing and configuring Update Controller.

## Prerequisites

Before installation, ensure you have:

- [ ] WordPress 5.0 or higher
- [ ] PHP 7.2 or higher
- [ ] OpenSSL PHP extension enabled
- [ ] Admin access to all WordPress sites (controller and targets)
- [ ] HTTPS configured on all sites (recommended)

## Part 1: Install Main Plugin (Controller Site)

This is the WordPress site from which you'll manage all updates.

### Step 1: Download and Upload

1. Download or clone this repository
2. Upload the `update-controller` folder to `/wp-content/plugins/` on your controller site
3. Or upload as a ZIP file through WordPress admin (Plugins > Add New > Upload)

### Step 2: Activate

1. Go to **Plugins** in WordPress admin
2. Find "Update Controller" in the list
3. Click **Activate**

### Step 3: Verify Installation

1. Check for "Update Controller" in the WordPress admin menu
2. You should see a new menu item with an update icon
3. Click it to verify the Sites and Plugins pages load correctly

## Part 2: Install Companion Plugin (Target Sites)

The companion plugin must be installed on **each WordPress site** you want to update remotely.

### Step 1: Prepare Companion Plugin

1. Navigate to the `companion-plugin` folder in the Update Controller repository
2. Copy the entire folder or just the `update-controller-companion.php` file

### Step 2: Upload to Target Sites

For each target WordPress site:

1. Create a folder: `/wp-content/plugins/update-controller-companion/`
2. Upload `update-controller-companion.php` to this folder
3. Or create a ZIP file and upload through WordPress admin

### Step 3: Activate on Target Sites

For each target WordPress site:

1. Go to **Plugins** in WordPress admin
2. Find "Update Controller Companion" in the list
3. Click **Activate**

### Step 4: Verify REST API Endpoints

For each target site, verify the endpoints are accessible:

1. Visit: `https://your-target-site.com/wp-json/uc-companion/v1/`
2. You should see a list of available routes (or a 404 is fine, means REST API works)
3. If you get a blank page or server error, troubleshoot REST API issues

## Part 3: Configure Application Passwords

WordPress 5.6+ includes Application Passwords for secure API authentication.

### For Each Target Site:

1. Log in as an administrator
2. Go to **Users > Your Profile**
3. Scroll down to **Application Passwords** section
4. Enter a name: "Update Controller" or similar
5. Click **Add New Application Password**
6. **Copy the generated password immediately** (you won't see it again)
7. Keep the password secure - you'll need it in the controller

**Important Notes:**
- Application passwords look like: `xxxx xxxx xxxx xxxx xxxx xxxx`
- Keep the spaces - they're part of the password
- Each site should have its own application password
- You can revoke application passwords anytime without changing your main password

### If Application Passwords Are Not Available:

If you're using WordPress 5.5 or earlier:

1. Consider upgrading to WordPress 5.6+
2. Or enable basic authentication (less secure)
3. Or use a plugin like "Application Passwords" for backport support

## Part 4: Configure Update Controller

Now set up the controller to manage your target sites.

### Step 1: Add Your First Site

1. On the controller site, go to **Update Controller > Sites**
2. Click **Add New Site**
3. Fill in the form:
   - **Site Name**: A friendly name (e.g., "My Blog")
   - **Site URL**: Full URL (e.g., https://example.com)
   - **Admin Username**: Your admin username on the target site
   - **Admin Password**: The Application Password you generated
4. Click **Save**

### Step 2: Verify Site Connection

After adding a site:

1. Check that it appears in the sites list
2. Status should show "active"
3. If you see errors, verify:
   - URL is correct (with https://)
   - Username is correct
   - Application Password is correct (including spaces)
   - Companion plugin is activated on target site

### Step 3: Add Plugin Configuration

1. Go to **Update Controller > Plugins**
2. Click **Add Plugin Configuration**
3. Fill in the form:
   - **WordPress Site**: Select the site you just added
   - **Plugin Name**: Friendly name (e.g., "My Custom Plugin")
   - **Plugin Slug**: Directory/file (e.g., "my-plugin/my-plugin.php")
   - **Update Source URL**: Where to get updates
   - **Source Type**: Web URL or GitHub Repository
   - **Auto Update**: Check to enable automatic updates
4. Click **Save**

### Step 4: Test Manual Update

1. Go to **Update Controller > Plugins**
2. Find your plugin configuration
3. Click **Update Now**
4. Watch the progress modal
5. If successful, you'll see a success message
6. If it fails, check the error message for details

## Part 5: Configure Automatic Updates

Automatic updates run daily via WordPress cron.

### Default Schedule

By default, automatic updates run:
- **Frequency**: Daily
- **Time**: Depends on when WordPress cron runs
- **Plugins**: Only those with "Auto Update" enabled

### Verify Cron is Working

1. Install "WP Crontrol" plugin on the controller site (optional)
2. Go to **Tools > Cron Events**
3. Look for `uc_scheduled_update` event
4. It should be scheduled to run daily

### Customize Schedule (Advanced)

To change the update frequency:

1. Edit `update-controller.php`
2. Find the line: `wp_schedule_event(time(), 'daily', 'uc_scheduled_update');`
3. Change 'daily' to:
   - 'hourly' - Updates every hour
   - 'twicedaily' - Updates twice per day
   - Custom interval (requires additional code)

## Troubleshooting

### Connection Issues

**Error: "Authentication failed"**
- Verify username and password are correct
- Check that Application Password includes spaces
- Ensure user has administrator privileges
- Verify companion plugin is activated

**Error: "Site not found" or 404**
- Check the site URL is correct
- Ensure site is accessible from controller
- Verify HTTPS vs HTTP
- Check for trailing slashes

### Update Issues

**Error: "Plugin installation failed"**
- Verify companion plugin is activated on target
- Check file permissions on target site
- Ensure the ZIP file is a valid plugin
- Check PHP error logs on target site

**Error: "Update source not accessible"**
- Verify the update URL is correct and accessible
- Check for authentication requirements
- Test the URL in a browser
- Verify GitHub repository is public

### REST API Issues

**Error: "REST API not available"**
- Verify REST API is enabled
- Check .htaccess for rewrites
- Test: `https://site.com/wp-json/`
- Flush permalinks: Settings > Permalinks > Save

**Error: "Insufficient permissions"**
- Ensure user has 'activate_plugins' capability
- Check companion plugin is properly installed
- Verify authentication headers are correct

### Security Issues

**Warning: "AUTH_KEY not configured"**
- Set AUTH_KEY in wp-config.php
- Don't use the default value
- Generate unique keys at: https://api.wordpress.org/secret-key/1.1/salt/

**Error: "Nonce verification failed"**
- Clear browser cache
- Try logging out and back in
- Check for clock sync issues between server and client

## Best Practices

### Security

1. **Always use HTTPS** on all sites
2. **Use Application Passwords** instead of main passwords
3. **Keep WordPress updated** on all sites
4. **Set unique AUTH_KEY** in wp-config.php
5. **Regular security audits** of all sites

### Backups

1. **Backup before updates** (use a backup plugin)
2. **Test on staging** before production
3. **Keep multiple backup versions**
4. **Verify restore procedures** regularly

### Monitoring

1. **Check update logs** regularly
2. **Monitor for failures** and investigate
3. **Test sites** after automatic updates
4. **Keep documentation** of all configured sites

### Organization

1. **Use descriptive names** for sites and plugins
2. **Group related sites** together
3. **Document update sources** for each plugin
4. **Track plugin versions** and changes

## Advanced Configuration

### Custom Update Intervals

Create custom cron intervals:

```php
// Add to functions.php on controller site
add_filter('cron_schedules', 'uc_custom_cron_schedule');
function uc_custom_cron_schedule($schedules) {
    $schedules['weekly'] = array(
        'interval' => 604800,
        'display'  => __('Once Weekly')
    );
    return $schedules;
}
```

Then use 'weekly' in the schedule.

### Multiple Update Sources

You can configure the same plugin with different sources for different sites:

1. Add plugin config for Site A with Source URL 1
2. Add plugin config for Site B with Source URL 2
3. Each site gets updates from its configured source

### Selective Auto-Updates

Control which plugins update automatically:

1. Enable auto-update for stable plugins
2. Disable auto-update for beta/development plugins
3. Manually test before enabling auto-update

## Support

If you encounter issues:

1. Check the documentation
2. Review error messages carefully
3. Check PHP error logs
4. Search existing GitHub issues
5. Create a new issue with details

## Next Steps

After successful installation:

1. [ ] Add all target sites
2. [ ] Configure plugins to update
3. [ ] Test manual updates
4. [ ] Enable automatic updates
5. [ ] Set up monitoring
6. [ ] Configure backups
7. [ ] Document your setup

Congratulations! You've successfully installed Update Controller.
