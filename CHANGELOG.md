# Changelog

All notable changes to Update Controller will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-11-23

### Added
- Initial release of Update Controller
- WordPress site management interface
  - Add, edit, and delete WordPress sites
  - Secure credential storage with AES-256-CBC encryption
  - Support for Application Passwords
- Plugin update configuration system
  - Configure plugins to update from custom sources
  - Support for web URLs and GitHub repositories
  - Plugin slug specification for accurate targeting
- Manual update functionality
  - "Update Now" button for immediate updates
  - Real-time progress feedback
  - Success/error notifications
- Automatic update scheduling
  - Daily WordPress cron-based updates
  - Per-plugin auto-update toggle
  - Update history tracking
- Admin interface
  - Clean, intuitive dashboard
  - Modal-based forms for adding/editing
  - Responsive design for mobile devices
  - AJAX-powered operations
- Security features
  - WordPress nonces for AJAX requests
  - Capability checks (manage_options)
  - Encrypted password storage
  - Secure authentication with remote sites
- Documentation
  - Comprehensive README in English
  - Turkish README (README-TR.md)
  - Usage examples (EXAMPLES.md)
  - UI guide (UI-GUIDE.md)
- Database schema
  - Sites table for WordPress site information
  - Plugins table for update configurations
  - Automatic table creation on activation
  - Clean uninstall with data removal
- Update mechanisms
  - Download from web URLs
  - Download from GitHub repositories
  - Automatic GitHub URL conversion
  - REST API integration for remote updates

### Technical Details
- Minimum WordPress version: 5.0
- Minimum PHP version: 7.2
- Required PHP extensions: OpenSSL
- Database tables: wp_uc_sites, wp_uc_plugins
- Scheduled event: uc_scheduled_update (daily)

### Files
- update-controller.php - Main plugin file
- includes/class-uc-database.php - Database operations
- includes/class-uc-admin.php - Admin interface
- includes/class-uc-updater.php - Update functionality
- includes/class-uc-encryption.php - Encryption utilities
- templates/sites-page.php - Sites management page
- templates/plugins-page.php - Plugins management page
- assets/css/admin.css - Admin styles
- assets/js/admin.js - Admin JavaScript
- uninstall.php - Cleanup on uninstall

## [Unreleased]

### Planned Features
- Email notifications for update results
- Update logs and history viewer
- Bulk update operations
- Update scheduling options (hourly, weekly, custom)
- Backup before update option
- Rollback functionality
- Multiple GitHub branch support
- Private repository authentication
- Update conflict detection
- Plugin dependency checking
- Multi-site (WordPress Network) support
- Import/export configurations
- REST API for external integrations
- Webhook support for update triggers

### Known Issues
- Remote plugin installation requires REST API access
- Large plugin files may timeout on slow connections
- GitHub rate limiting may affect frequent updates
- Password field doesn't support special characters in some cases

### Future Improvements
- Add retry mechanism for failed updates
- Implement update queue system
- Add detailed logging system
- Support for theme updates
- Dashboard widget with update status
- Integration with popular backup plugins
- Support for custom authentication methods
- Performance optimization for large site lists
