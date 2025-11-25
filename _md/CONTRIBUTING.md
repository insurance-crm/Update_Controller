# Contributing to Update Controller

Thank you for considering contributing to Update Controller! This document provides guidelines for contributing to the project.

## Code of Conduct

- Be respectful and inclusive
- Welcome newcomers and help them get started
- Focus on constructive feedback
- Respect different viewpoints and experiences

## How Can I Contribute?

### Reporting Bugs

Before creating a bug report:
- Check the documentation to ensure it's actually a bug
- Search existing issues to avoid duplicates
- Test with the latest version

When reporting a bug, include:
- WordPress version
- PHP version
- Plugin version
- Steps to reproduce
- Expected behavior
- Actual behavior
- Error messages (if any)
- Screenshots (if applicable)

### Suggesting Enhancements

Enhancement suggestions are welcome! Please include:
- Clear description of the feature
- Use cases and benefits
- Potential implementation approach
- Any drawbacks or concerns

### Pull Requests

1. Fork the repository
2. Create a new branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Test your changes thoroughly
5. Commit your changes (`git commit -m 'Add amazing feature'`)
6. Push to the branch (`git push origin feature/amazing-feature`)
7. Open a Pull Request

## Development Guidelines

### Coding Standards

Follow WordPress Coding Standards:
- [PHP Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/)
- [JavaScript Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/javascript/)
- [CSS Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/css/)

### Code Style

**PHP:**
```php
// Use WordPress naming conventions
function uc_example_function() {
    // Code here
}

// Class names use underscores
class UC_Example_Class {
    // Properties and methods
}

// Use proper indentation (4 spaces)
if ( condition ) {
    // Code here
}
```

**JavaScript:**
```javascript
// Use camelCase for variables and functions
var exampleVariable = 'value';

function exampleFunction() {
    // Code here
}

// Use consistent indentation
if (condition) {
    // Code here
}
```

**CSS:**
```css
/* Use hyphens for class names */
.uc-example-class {
    property: value;
}

/* Group related properties */
.uc-modal {
    /* Positioning */
    position: fixed;
    z-index: 100000;
    
    /* Box model */
    width: 100%;
    height: 100%;
    
    /* Visual */
    background-color: rgba(0,0,0,0.4);
}
```

### File Organization

```
update-controller/
├── update-controller.php     # Main plugin file
├── includes/                 # PHP classes
│   ├── class-uc-admin.php
│   ├── class-uc-database.php
│   ├── class-uc-encryption.php
│   └── class-uc-updater.php
├── templates/                # PHP templates
│   ├── sites-page.php
│   └── plugins-page.php
├── assets/                   # Static assets
│   ├── css/
│   │   └── admin.css
│   └── js/
│       └── admin.js
├── languages/                # Translation files (future)
└── uninstall.php            # Uninstall script
```

### Security Guidelines

1. **Always sanitize input:**
   ```php
   $value = sanitize_text_field($_POST['value']);
   $url = esc_url_raw($_POST['url']);
   ```

2. **Always escape output:**
   ```php
   echo esc_html($value);
   echo esc_attr($attribute);
   echo esc_url($url);
   ```

3. **Use nonces for forms:**
   ```php
   wp_nonce_field('uc_action', 'uc_nonce');
   wp_verify_nonce($_POST['uc_nonce'], 'uc_action');
   ```

4. **Check capabilities:**
   ```php
   if (!current_user_can('manage_options')) {
       wp_die(__('Insufficient permissions'));
   }
   ```

5. **Use prepared statements:**
   ```php
   $wpdb->prepare("SELECT * FROM table WHERE id = %d", $id);
   ```

### Testing

Before submitting a pull request:

1. **Test manually:**
   - Install the plugin on a test WordPress site
   - Test all modified functionality
   - Test on different browsers
   - Test with different PHP versions (7.2, 7.4, 8.0+)

2. **Check for errors:**
   - Enable WP_DEBUG
   - Check PHP error logs
   - Check browser console
   - Test with Query Monitor plugin

3. **Test security:**
   - Test with different user roles
   - Test without proper capabilities
   - Test AJAX endpoints with invalid nonces
   - Test input validation

4. **Test edge cases:**
   - Empty inputs
   - Very long inputs
   - Special characters
   - Invalid URLs
   - Non-existent sites

### Documentation

Update documentation when:
- Adding new features
- Changing existing functionality
- Fixing bugs that affect usage
- Adding new configuration options

Documentation files to update:
- README.md
- README-TR.md (if applicable)
- CHANGELOG.md
- EXAMPLES.md (if adding new examples)
- Code comments (PHPDoc)

### Commit Messages

Write clear, descriptive commit messages:

```
Add feature to support custom update intervals

- Add new database field for update interval
- Add UI for configuring intervals
- Update scheduler to use custom intervals
- Add tests for interval functionality
```

Format:
- First line: Brief summary (50 characters or less)
- Blank line
- Detailed description (wrap at 72 characters)
- List specific changes with bullet points

### Versioning

We use [Semantic Versioning](https://semver.org/):
- MAJOR version for incompatible API changes
- MINOR version for new functionality (backwards compatible)
- PATCH version for backwards compatible bug fixes

## Project Structure

### Main Components

1. **Database Layer** (`class-uc-database.php`)
   - Handles all database operations
   - CRUD operations for sites and plugins
   - Uses prepared statements

2. **Admin Interface** (`class-uc-admin.php`)
   - Admin menu and pages
   - AJAX handlers
   - Form rendering

3. **Updater** (`class-uc-updater.php`)
   - Update logic
   - File downloads
   - Remote site communication

4. **Encryption** (`class-uc-encryption.php`)
   - Password encryption/decryption
   - Uses AES-256-CBC

### Adding New Features

When adding a new feature:

1. **Plan the implementation:**
   - Define the feature scope
   - Identify affected components
   - Plan database changes (if any)

2. **Update the database schema:**
   - Add migration code in activation hook
   - Update `class-uc-database.php`

3. **Add backend logic:**
   - Add methods to appropriate classes
   - Follow existing patterns
   - Add error handling

4. **Update the UI:**
   - Add templates or modify existing ones
   - Add JavaScript functionality
   - Add CSS styling

5. **Add AJAX handlers:**
   - Add action hooks
   - Add handler methods
   - Add nonce verification

6. **Test thoroughly:**
   - Test all code paths
   - Test error conditions
   - Test security aspects

7. **Document:**
   - Add code comments
   - Update README
   - Update examples
   - Update changelog

## Getting Help

- Check the documentation
- Search existing issues
- Ask questions in issues (tag with "question")
- Contact maintainers

## License

By contributing, you agree that your contributions will be licensed under the GPL v2 or later license.

## Recognition

Contributors will be recognized in:
- CHANGELOG.md
- Release notes
- README.md (for significant contributions)

Thank you for contributing to Update Controller!
