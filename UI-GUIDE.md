# Update Controller - User Interface Guide

## Admin Menu Structure

```
WordPress Admin Menu
└── Update Controller (dashicons-update icon)
    ├── Sites (Manage WordPress Sites)
    └── Plugins (Manage Plugin Configurations)
```

## Sites Page

### Layout
- **Page Title**: "WordPress Sites"
- **Action Button**: "Add New Site" (top right)
- **Table Columns**:
  - Site Name
  - URL (clickable link)
  - Username
  - Status (Active/Inactive badge)
  - Last Update (date/time)
  - Actions (Edit, Delete buttons)

### Add/Edit Site Modal
- **Fields**:
  - Site Name (text input)
  - Site URL (URL input with placeholder: https://example.com)
  - Admin Username (text input)
  - Admin Password / Application Password (password input)
    - Note: "Leave empty when editing to keep current password"
- **Buttons**:
  - Save (primary button)
  - Cancel (secondary button)

### Features
- Click "Add New Site" to open add modal
- Click "Edit" to modify existing site
- Click "Delete" to remove site (with confirmation)
- Password is hidden and encrypted in database
- Sites table is responsive and sortable

## Plugins Page

### Layout
- **Page Title**: "Plugin Configurations"
- **Action Button**: "Add Plugin Configuration" (top right)
- **Table Columns**:
  - Site (site name)
  - Plugin Name
  - Plugin Slug (in code format)
  - Update Source (clickable link)
  - Source Type (badge: web/github)
  - Auto Update (Yes/No)
  - Last Update (date/time)
  - Actions (Update Now, Edit, Delete buttons)

### Add/Edit Plugin Modal
- **Fields**:
  - WordPress Site (dropdown of available sites)
  - Plugin Name (text input, e.g., "My Plugin")
  - Plugin Slug (text input, e.g., "my-plugin/my-plugin.php")
    - Help text: "Plugin directory/main file (e.g., akismet/akismet.php)"
  - Update Source URL (URL input, e.g., "https://example.com/plugin.zip")
    - Help text: "Direct download URL or GitHub repository URL"
  - Source Type (dropdown: Web URL, GitHub Repository)
  - Enable Automatic Updates (checkbox, checked by default)
- **Buttons**:
  - Save (primary button)
  - Cancel (secondary button)

### Update Progress Modal
- Shows during manual updates
- Displays:
  - "Updating plugin..." with loading spinner
  - Success message with green badge
  - Error message with red badge
- Auto-closes after successful update

### Features
- Click "Add Plugin Configuration" to open add modal
- Click "Update Now" to manually trigger update (shows progress modal)
- Click "Edit" to modify configuration
- Click "Delete" to remove configuration (with confirmation)
- Automatic updates run daily via WordPress cron
- Visual feedback with badges and status indicators

## Color Scheme

- **Primary Action**: WordPress blue (#0073aa)
- **Success**: Green (#d4edda background, #155724 text)
- **Error**: Red (#f8d7da background, #721c24 text)
- **Badges**: Light gray (#e9ecef background)
- **Status Active**: Green badge
- **Status Inactive**: Red badge

## Modal Behavior

- Modals appear centered on screen
- Background overlay dims the page
- Click X or Cancel to close
- Click outside modal to close
- Form validation before submission
- Success/error messages appear below page title
- Auto-dismiss after 3 seconds

## Responsive Design

- Tables are responsive and scroll horizontally on small screens
- Modals adapt to screen size (max-width: 90% on mobile)
- Buttons stack vertically on small screens
- All actions accessible via touch on mobile devices

## Accessibility

- Proper label associations for all inputs
- Required fields marked with asterisk
- Keyboard navigation supported
- Screen reader friendly
- Focus states visible
- Confirmation dialogs for destructive actions
