# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Core Forms is a lightweight WordPress forms plugin (v2.0.19) that allows users to create forms using plain HTML with AJAX submission handling. All premium features are bundled in the core plugin. Forms are stored as a custom post type (`core-form`) with submissions saved to a custom database table (`wp_cf_submissions`).

## Key Commands

```bash
# No build step required - this is a WordPress plugin
# Development is done within a WordPress installation

# Generate translation file (requires WP-CLI)
wp i18n make-pot . languages/core-forms.pot --domain=core-forms

# Run plugin in local WordPress (symlink or copy to wp-content/plugins/)
```

## Architecture

### Namespace & Prefixes
- PHP namespace: `Core_Forms`
- Function prefix: `cf_` (e.g., `cf_get_form()`, `cf_get_settings()`)
- Database table: `wp_cf_submissions`
- Post type: `core-form`
- Shortcode: `[cf_form slug="..." id="..."]`
- Gutenberg block: `core-forms/form`
- AJAX action: `cf_form_submit`

### Core Classes (src/)
- **`Forms`** (`class-forms.php`) - Main plugin class. Registers post type, shortcode, block, REST API, assets, and handles AJAX form processing via `process()` method
- **`Form`** (`class-form.php`) - Form entity with properties: `ID`, `title`, `slug`, `markup`, `messages`, `settings`. The `get_html()` method renders the complete form with honeypot, nonce, and accessibility attributes
- **`Submission`** (`class-submission.php`) - Submission entity with `from_object()` factory method

### Admin Classes (src/admin/)
- **`Admin`** - Admin pages, form CRUD operations, settings management
- **`Recaptcha`** - Google reCAPTCHA v3 integration
- **`MathCaptcha`** - Simple math-based spam protection
- **`Akismet`** - Akismet spam filtering integration
- **`Table`** - WP_List_Table extension for submissions listing
- **`Migrations`** - Database schema migrations

### Actions (src/actions/)
- **`Email`** - Sends email notifications on form submission
- **`MailChimp`** - Subscribes to Mailchimp lists (requires MC4WP plugin)

### Premium Features (premium/)
Each feature is self-contained in its own directory:
- `data-exporter/` - CSV export of submissions
- `data-management/` - Advanced submission management
- `file-upload/` - File upload field support
- `webhooks/` - Send data to external APIs
- `notifications/` - Admin notification badges
- `submission-limit/` - Limit form submissions
- `require-user-logged-in/` - Restrict forms to logged-in users

### Views (views/)
Admin templates using WordPress admin UI patterns. Tab-based form editing: fields, messages, settings, actions, submissions.

### Key Functions (src/functions.php)
```php
cf_get_form($id_or_slug)           // Get Form by ID or slug
cf_get_form_by_slug($slug)         // Get Form by slug only
cf_get_forms($args)                // Get multiple forms
cf_get_form_submissions($form_id)  // Get submissions for a form
cf_get_settings()                  // Get global plugin settings
cf_replace_data_variables($string, $submission)  // Template variable replacement
cf_template($template)             // Process {{user.email}} style tags
```

### Data Flow
1. Form rendered via shortcode/block calls `Form::get_html()`
2. JavaScript (`assets/js/forms.js`) handles AJAX submission to `cf_form_submit` action
3. `Forms::process()` validates (honeypot, nonce, required fields, email format, custom filters)
4. Submission saved to `wp_cf_submissions` table if `save_submissions` enabled
5. `cf_form_success` action triggers configured actions (email, webhooks, etc.)
6. JSON response returned with message or redirect URL

### Key Filters & Actions (documented in docs.md)
- `cf_validate_form` - Custom validation logic
- `cf_form_success` - Post-submission actions
- `cf_submission_inserted` - After database insert
- `cf_form_html` / `cf_form_markup` - Modify form output
- `cf_process_form_action_{type}` - Custom action types

### Database
Custom table `wp_cf_submissions`:
- `id`, `form_id`, `data` (JSON), `ip_address`, `user_agent`, `referer_url`, `is_spam`, `submitted_at`

Migrations in `migrations/` directory are version-prefixed (e.g., `2.0.18-add-spam-column.php`).

## Development Notes

- Forms use plain HTML stored in `post_content` - no custom field builder
- Conditional fields via `data-show-if` / `data-hide-if` attributes
- Template variables: `{{user.email}}`, `{{post.ID}}`, `{{url_params.ref}}`
- Data variables in emails/redirects: `[field_name]`, `[CF_TIMESTAMP]`, `[all:label]`
- Spam detection returns success response to trick bots but stores as spam
- Capability: `edit_forms` (added to admin on activation)
