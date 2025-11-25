# Update Controller - Configuration Examples

## Example 1: Update from Direct Web URL

**Scenario**: Update a custom plugin from a direct download URL

**Configuration:**
- Site Name: My WordPress Site
- Site URL: https://mysite.com
- Admin Username: admin
- Admin Password: [Your Application Password]

**Plugin Configuration:**
- Plugin Name: My Custom Plugin
- Plugin Slug: my-custom-plugin/my-custom-plugin.php
- Update Source URL: https://cdn.example.com/plugins/my-custom-plugin-latest.zip
- Source Type: Web URL
- Auto Update: Enabled

## Example 2: Update from GitHub Repository

**Scenario**: Update a plugin hosted on GitHub (main branch)

**Configuration:**
- Site Name: Development Site
- Site URL: https://dev.example.com
- Admin Username: devadmin
- Admin Password: [Your Application Password]

**Plugin Configuration:**
- Plugin Name: GitHub Custom Plugin
- Plugin Slug: github-plugin/github-plugin.php
- Update Source URL: https://github.com/mycompany/custom-plugin
- Source Type: GitHub Repository
- Auto Update: Enabled

**Note**: The plugin will automatically convert this to:
`https://github.com/mycompany/custom-plugin/archive/refs/heads/main.zip`

## Example 3: Update from GitHub Release

**Scenario**: Update from a specific GitHub release

**Configuration:**
- Site Name: Production Site
- Site URL: https://example.com
- Admin Username: admin
- Admin Password: [Your Application Password]

**Plugin Configuration:**
- Plugin Name: Stable Plugin
- Plugin Slug: stable-plugin/stable-plugin.php
- Update Source URL: https://github.com/mycompany/stable-plugin/releases/download/v1.0.0/stable-plugin.zip
- Source Type: GitHub Repository
- Auto Update: Disabled (manual updates only)

## Example 4: Multiple Sites, Same Plugin

**Scenario**: Update the same plugin across multiple sites

**Site 1:**
- Site Name: Main Website
- Site URL: https://main.example.com
- Admin Username: admin
- Admin Password: [Your Application Password]

**Site 2:**
- Site Name: Blog Website
- Site URL: https://blog.example.com
- Admin Username: blogadmin
- Admin Password: [Your Application Password]

**Plugin Configuration for Site 1:**
- WordPress Site: Main Website
- Plugin Name: Shared Plugin
- Plugin Slug: shared-plugin/shared-plugin.php
- Update Source URL: https://github.com/mycompany/shared-plugin
- Source Type: GitHub Repository
- Auto Update: Enabled

**Plugin Configuration for Site 2:**
- WordPress Site: Blog Website
- Plugin Name: Shared Plugin
- Plugin Slug: shared-plugin/shared-plugin.php
- Update Source URL: https://github.com/mycompany/shared-plugin
- Source Type: GitHub Repository
- Auto Update: Enabled

## Example 5: Custom CDN Source

**Scenario**: Update from a custom CDN with versioned URLs

**Configuration:**
- Site Name: Customer Site
- Site URL: https://customer.example.com
- Admin Username: customer_admin
- Admin Password: [Your Application Password]

**Plugin Configuration:**
- Plugin Name: Premium Plugin
- Plugin Slug: premium-plugin/premium-plugin.php
- Update Source URL: https://cdn.myplugins.com/releases/premium-plugin/latest.zip
- Source Type: Web URL
- Auto Update: Enabled

## Finding the Plugin Slug

The plugin slug is the directory name and main file of the plugin. You can find it by:

1. Go to the WordPress site's `wp-content/plugins/` directory
2. Look for the plugin directory name
3. Inside that directory, find the main PHP file (usually the same name)
4. Combine them: `directory-name/main-file.php`

**Examples:**
- Akismet: `akismet/akismet.php`
- Yoast SEO: `wordpress-seo/wp-seo.php`
- WooCommerce: `woocommerce/woocommerce.php`
- Custom Plugin: `my-custom-plugin/my-custom-plugin.php`

## Setting Up Application Passwords

For WordPress 5.6+ (recommended for security):

1. Log in to the target WordPress site
2. Go to **Users > Profile**
3. Scroll down to **Application Passwords** section
4. Enter a name (e.g., "Update Controller")
5. Click **Add New Application Password**
6. Copy the generated password
7. Use this password in Update Controller (keep spaces, they're part of the password)

**Important**: Application Passwords are different from regular passwords and can be revoked individually without changing your main password.

## Troubleshooting

### Authentication Failed
- Verify the Site URL is correct (with or without trailing slash)
- Check that the username is correct
- For WordPress 5.6+, ensure you're using an Application Password
- Verify the remote site's REST API is enabled

### Update Failed
- Check that the Update Source URL is accessible
- Verify the plugin slug is correct
- Ensure the remote site has write permissions for the plugins directory
- Check PHP error logs on both the controller and remote site

### Plugin Not Activating
- The plugin slug may be incorrect
- The uploaded plugin may not match the expected directory structure
- Check the remote site's error logs for activation errors
