# Frequently Asked Questions (FAQ)

## General Questions

### What is Update Controller?

Update Controller is a WordPress plugin that allows you to manage plugin updates across multiple WordPress sites from a single dashboard. You can specify custom update sources (web URLs or GitHub repositories) and automate the update process.

### Why do I need this plugin?

This plugin is useful when:
- You manage multiple WordPress sites
- You have custom or premium plugins not in the WordPress.org repository
- You want to update plugins from GitHub or custom sources
- You need centralized update management
- You want to automate plugin updates from custom sources

### Is this a replacement for the WordPress.org plugin repository?

No. This plugin is designed for custom, premium, or private plugins that aren't available on WordPress.org. For plugins in the WordPress.org repository, use the built-in WordPress update mechanism.

## Installation & Setup

### Do I need to install plugins on multiple sites?

Yes. You need:
1. **Main Plugin** (Update Controller) - Installed on your controller site
2. **Companion Plugin** - Installed on each target site you want to manage

### Can I use this on WordPress Multisite?

The plugin hasn't been specifically tested for WordPress Multisite. It should work on individual sites within a network, but multisite-specific features aren't implemented.

### What are Application Passwords?

Application Passwords are WordPress feature (5.6+) that allow you to create separate passwords for API access without exposing your main admin password. They can be revoked individually.

### Can I use regular passwords instead of Application Passwords?

While technically possible with Basic Auth, it's **strongly discouraged** for security reasons. Application Passwords are more secure and can be revoked without changing your main password.

### What if my WordPress version doesn't support Application Passwords?

WordPress 5.6+ has built-in support. For earlier versions:
- Upgrade to WordPress 5.6+ (recommended)
- Install the "Application Passwords" feature plugin
- Use basic authentication (not recommended)

## Configuration

### How do I find the plugin slug?

The plugin slug is `directory-name/main-file.php`. For example:
- Akismet: `akismet/akismet.php`
- Contact Form 7: `contact-form-7/wp-contact-form-7.php`

To find it:
1. Go to the site's `wp-content/plugins/` directory
2. Find the plugin directory name
3. Inside, find the main PHP file (usually same name as directory)
4. Combine: `directory/file.php`

### Can I update plugins from private GitHub repositories?

Not directly. The current implementation only supports public repositories. For private repositories, you would need to:
- Generate a GitHub personal access token
- Modify the download code to include authentication
- Or host the files on a web server instead

### Can I update multiple plugins from the same source?

Yes, but each plugin needs its own configuration. You can point multiple plugin configurations to the same update source if they're bundled together.

### Can I use different update sources for the same plugin on different sites?

Yes! Each plugin configuration is site-specific. You can configure:
- Site A: Plugin X from Source 1
- Site B: Plugin X from Source 2

## Updates

### How often do automatic updates run?

By default, once per day via WordPress cron. You can customize this in the plugin code.

### When exactly do automatic updates run?

WordPress cron doesn't run at exact times. It runs when someone visits the site. If you need precise timing, set up a real cron job to trigger WordPress cron.

### Can I disable automatic updates for specific plugins?

Yes. When configuring or editing a plugin, uncheck "Enable Automatic Updates". You can still update manually using the "Update Now" button.

### What happens if an update fails?

The plugin will log the error and continue with other updates. You'll see error messages in the admin interface. The site won't be left in a broken state - the old version remains active.

### Can I rollback after an update?

Currently, there's no built-in rollback feature. Best practices:
- Always backup before updates
- Test on staging sites first
- Keep previous versions of plugins available

## Security

### Is it safe to store admin credentials?

Credentials are encrypted using AES-256-CBC with your WordPress AUTH_KEY. However:
- Use Application Passwords, not main passwords
- Ensure AUTH_KEY is properly configured
- Limit access to administrator accounts
- Keep the controller site secure

### What if someone gains access to the controller site?

They could potentially:
- Trigger updates on managed sites
- View (but not decrypt) stored credentials
- Add new sites or plugins

Protect your controller site with:
- Strong passwords
- Two-factor authentication
- Regular security updates
- Access logging
- Firewall rules

### Can I use this over HTTP?

Technically yes, but **strongly discouraged**. Always use HTTPS to protect credentials and data in transit.

### How are passwords encrypted?

Passwords are encrypted using:
- Algorithm: AES-256-CBC
- Key: WordPress AUTH_KEY
- IV: Random per encryption
- Result: Base64-encoded

## Troubleshooting

### Updates show as successful but nothing changes

This usually means:
- Companion plugin isn't installed on target site
- Plugin installation is failing silently
- Permissions issue on target site
- Plugin slug is incorrect

Check companion plugin is activated and check PHP error logs on target site.

### "Authentication failed" errors

Common causes:
- Wrong username or password
- Application Password not copied correctly (missing spaces)
- User doesn't have administrator privileges
- REST API is blocked
- Authentication not enabled on target site

### "Plugin not found" errors

Common causes:
- Plugin slug is incorrect
- Plugin isn't installed on target site
- Typo in plugin directory or file name

### REST API returning 404

Common causes:
- Companion plugin not activated
- REST API disabled
- Permalink issues
- .htaccess blocking requests

Fix:
1. Verify companion plugin is active
2. Go to Settings > Permalinks > Save Changes
3. Check .htaccess file
4. Test: visit `/wp-json/` on target site

### Downloads failing from GitHub

Common causes:
- Repository doesn't exist or is private
- Incorrect URL format
- Rate limiting (if making many requests)
- Network issues

Try:
- Verify repository URL in browser
- Use direct release URLs instead of repository URLs
- Check GitHub status

## Performance

### Does this slow down my sites?

The companion plugin is very lightweight and only activates during update requests. The controller plugin only impacts the controller site, not target sites.

### How many sites can I manage?

There's no hard limit. Performance depends on:
- Server resources
- Number of plugins per site
- Frequency of updates
- Network speed

### Can I update multiple sites simultaneously?

Currently, updates run sequentially. Parallel updates could be added in future versions.

## Compatibility

### Which WordPress versions are supported?

- Controller site: WordPress 5.0+
- Target sites: WordPress 5.0+
- Recommended: WordPress 5.6+ for Application Passwords

### Which PHP versions are supported?

PHP 7.2 or higher. PHP 8.0+ is supported.

### Does this work with any hosting provider?

Yes, as long as:
- PHP requirements are met
- REST API is accessible
- File system is writable for plugins
- WordPress is properly configured

### Can I use this with managed WordPress hosting?

Most managed hosts will work, but some may:
- Block REST API access
- Restrict file system writes
- Have custom plugin update mechanisms

Check with your host if you encounter issues.

## Development

### Can I contribute to the project?

Yes! See [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

### Can I use this in production?

The plugin is functional but consider it beta. Recommendations:
- Test thoroughly in staging
- Maintain backups
- Monitor updates closely
- Report any issues

### Are there any planned features?

See [CHANGELOG.md](CHANGELOG.md) for planned features including:
- Email notifications
- Backup integration
- Rollback functionality
- Bulk operations
- Enhanced logging

### Can I modify the plugin?

Yes! It's GPL v2 licensed. You can:
- Modify for your needs
- Create forks
- Submit improvements
- Share modifications (must also be GPL)

## Best Practices

### Should I test updates before deploying?

Absolutely! Best practice workflow:
1. Test on local/development site
2. Test on staging site
3. Backup production site
4. Deploy to production
5. Verify functionality

### How often should I run updates?

Balance security with stability:
- Critical security updates: ASAP
- Regular updates: Weekly or monthly
- Major updates: After testing

### Should I enable auto-updates for all plugins?

No. Recommendations:
- Enable for well-tested, stable plugins
- Disable for critical plugins
- Disable for beta/development plugins
- Always test major updates manually first

### How can I monitor update status?

Currently:
- Check "Last Update" column in admin
- Check PHP error logs
- Test sites after updates

Future versions may include:
- Email notifications
- Update logs/history
- Dashboard widgets

## Getting Help

### Where can I get support?

1. Read the documentation (README, INSTALLATION, EXAMPLES)
2. Check this FAQ
3. Search GitHub issues
4. Create a new issue with details

### How do I report a bug?

Create a GitHub issue with:
- WordPress version
- PHP version
- Plugin version
- Steps to reproduce
- Expected vs actual behavior
- Error messages
- Relevant logs

### How do I request a feature?

Create a GitHub issue tagged "enhancement" with:
- Clear description
- Use case
- Benefits
- Potential implementation ideas

### Is commercial support available?

Currently, this is a community project. Commercial support may be available in the future.

## Miscellaneous

### Can I use this for theme updates?

Not currently. The plugin is designed for plugins only. Theme support could be added in future versions.

### Does this work with WooCommerce?

Yes, you can update WooCommerce and its extensions (if you have the update files).

### Can I update WordPress core?

No. This plugin only handles plugin updates. Use WordPress's built-in core update mechanism.

### Can I schedule updates for specific times?

Not directly. You can customize the cron schedule, but WordPress cron isn't precise. For exact timing, use system cron to trigger WordPress cron.

### What languages are supported?

Currently:
- English (primary)
- Turkish (documentation)

The plugin is translation-ready for future localizations.

---

## Still Have Questions?

If your question isn't answered here:
1. Check the other documentation files
2. Search GitHub issues
3. Create a new issue

We're here to help!
