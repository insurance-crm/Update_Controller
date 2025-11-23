# Update Controller

WordPress plugin for managing automatic updates of plugins across multiple WordPress sites from specified web or GitHub repository sources.

## Features

- Manage multiple WordPress sites from a single dashboard
- Configure automatic plugin updates from custom sources
- Support for web URLs and GitHub repositories
- Secure credential storage with encryption
- Manual and automatic update scheduling
- User-friendly admin interface

## Installation

1. Upload the `update-controller` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to 'Update Controller' in the admin menu

## Usage

### Adding WordPress Sites

1. Go to **Update Controller > Sites**
2. Click **Add New Site**
3. Fill in the following information:
   - **Site Name**: A friendly name for the site
   - **Site URL**: The full URL of the WordPress site (e.g., https://example.com)
   - **Admin Username**: WordPress admin username
   - **Admin Password**: WordPress admin password or Application Password

**Note**: For better security, use WordPress Application Passwords instead of the main admin password.

### Configuring Plugin Updates

1. Go to **Update Controller > Plugins**
2. Click **Add Plugin Configuration**
3. Fill in the following information:
   - **WordPress Site**: Select the target site
   - **Plugin Name**: Friendly name for the plugin
   - **Plugin Slug**: Plugin directory/main file (e.g., `akismet/akismet.php`)
   - **Update Source URL**: Direct download URL or GitHub repository URL
   - **Source Type**: Select 'Web URL' or 'GitHub Repository'
   - **Enable Automatic Updates**: Check to enable scheduled updates

### Running Updates

**Manual Updates:**
- Go to **Update Controller > Plugins**
- Click **Update Now** next to any plugin configuration

**Automatic Updates:**
- Enabled by default for plugins with "Enable Automatic Updates" checked
- Runs daily via WordPress cron
- Can be customized by modifying the plugin code

## Source URL Examples

### Web URL
```
https://example.com/downloads/my-plugin.zip
https://cdn.example.com/plugins/latest/plugin-name.zip
```

### GitHub Repository
```
https://github.com/username/repository
https://github.com/username/repository/archive/refs/heads/main.zip
https://github.com/username/repository/releases/download/v1.0.0/plugin.zip
```

## Security

- Admin credentials are encrypted using AES-256-CBC
- All AJAX requests are protected with WordPress nonces
- Requires `manage_options` capability for all operations
- Credentials are never displayed in the UI after being saved

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher
- OpenSSL PHP extension for encryption

## Technical Details

### Database Tables

**uc_sites:**
- Stores WordPress site information and credentials

**uc_plugins:**
- Stores plugin update configurations

### Update Process

1. Download plugin from specified source
2. Authenticate with remote WordPress site
3. Upload plugin file to remote site
4. Deactivate current plugin version
5. Install updated plugin
6. Reactivate plugin

## Important Notes

### Application Passwords

For WordPress 5.6+, it's recommended to use Application Passwords:

1. Go to **Users > Profile** on the target WordPress site
2. Scroll to **Application Passwords** section
3. Create a new application password
4. Use this password in Update Controller instead of the main admin password

### Remote Site Requirements

The remote WordPress site must:
- Have REST API enabled (default in WordPress)
- Accept authentication via Application Passwords or basic auth
- Have proper file permissions for plugin installation

### Customization

To customize the update schedule, modify the cron schedule in `update-controller.php`:

```php
// Change 'daily' to 'hourly', 'twicedaily', or a custom interval
wp_schedule_event(time(), 'daily', 'uc_scheduled_update');
```

## Support

For issues and feature requests, please visit:
https://github.com/insurance-crm/Update_Controller

## License

GPL v2 or later

## Changelog

### 1.0.0
- Initial release
- Site management
- Plugin update configuration
- Manual and automatic updates
- GitHub and web URL support
- Credential encryption