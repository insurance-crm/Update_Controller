# Update Controller Companion Plugin

This is a companion plugin that must be installed on **target WordPress sites** to enable remote plugin updates from Update Controller.

## What is this?

The Update Controller main plugin runs on a central WordPress site and manages updates for plugins across multiple WordPress sites. This companion plugin must be installed on each **target site** that you want to update remotely.

## Installation

1. Copy this `companion-plugin` folder to the target WordPress site
2. Rename it to `update-controller-companion` (optional)
3. Upload to `/wp-content/plugins/` directory
4. Activate the plugin through the 'Plugins' menu

## What does it do?

This companion plugin adds REST API endpoints that allow the main Update Controller to:

- Upload plugin ZIP files
- Install/update plugins
- Activate plugins
- Deactivate plugins

## REST API Endpoints

### Install/Update Plugin
```
POST /wp-json/uc-companion/v1/install-plugin
```

**Parameters:**
- `file_id` (required): Media attachment ID of the uploaded plugin ZIP file

**Response:**
```json
{
  "success": true,
  "message": "Plugin installed successfully",
  "plugin_file": "plugin-name/plugin-name.php"
}
```

### Activate Plugin
```
POST /wp-json/uc-companion/v1/activate-plugin
```

**Parameters:**
- `plugin_slug` (required): Plugin directory/file (e.g., "akismet/akismet.php")

**Response:**
```json
{
  "success": true,
  "message": "Plugin activated successfully"
}
```

### Deactivate Plugin
```
POST /wp-json/uc-companion/v1/deactivate-plugin
```

**Parameters:**
- `plugin_slug` (required): Plugin directory/file (e.g., "akismet/akismet.php")

**Response:**
```json
{
  "success": true,
  "message": "Plugin deactivated successfully"
}
```

## Security

- All endpoints require `activate_plugins` capability (administrator access)
- Authentication is handled via WordPress Application Passwords or Basic Auth
- Only administrators can install, activate, or deactivate plugins

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher
- REST API enabled (default in WordPress)
- Application Passwords or Basic Auth configured

## Setup with Update Controller

1. **Install this companion plugin** on all target WordPress sites you want to manage
2. **Enable Application Passwords** on target sites (WordPress 5.6+):
   - Go to Users > Profile
   - Scroll to "Application Passwords"
   - Create a new application password
   - Copy the generated password
3. **Add the site in Update Controller**:
   - Use the WordPress site URL
   - Use your admin username
   - Use the Application Password (not your regular password)
4. **Configure plugin updates** in Update Controller

## Troubleshooting

### Endpoints not found (404)
- Ensure the companion plugin is activated
- Check that REST API is enabled (`/wp-json/` should be accessible)
- Flush permalinks (Settings > Permalinks > Save Changes)

### Authentication failed
- Verify Application Passwords are enabled
- Check that the username and password are correct
- Ensure the user has administrator privileges

### Plugin installation failed
- Check file permissions on the plugins directory
- Verify the ZIP file is a valid WordPress plugin
- Check PHP error logs for details

### Plugin won't activate/deactivate
- Ensure the plugin slug is correct
- Check that the plugin is installed
- Review plugin activation requirements

## License

GPL v2 or later

## Support

This is part of the Update Controller plugin. For support, visit:
https://github.com/insurance-crm/Update_Controller
