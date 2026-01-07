# Core Forms - Developer Documentation

## Hooks & Filters Reference

This document lists all available hooks, filters, and actions in Core Forms with usage examples.

---

## Table of Contents

1. [Form Rendering](#form-rendering)
2. [Form Processing](#form-processing)
3. [Form Validation](#form-validation)
4. [Submissions](#submissions)
5. [Settings & Messages](#settings--messages)
6. [Spam Protection](#spam-protection)
7. [Actions](#actions)
8. [Admin](#admin)

---

## Form Rendering

### `cf_form_element_class_attr`

Filter the CSS classes added to the `<form>` element.

```php
/**
 * @param string $form_classes_attr Current class attribute value
 * @param \Core_Forms\Form $form The form object
 * @return string Modified class attribute
 */
add_filter( 'cf_form_element_class_attr', function( $form_classes_attr, $form ) {
    // Add custom class to all forms
    return $form_classes_attr . ' my-custom-class';
}, 10, 2 );
```

### `cf_form_element_action_attr`

Filter the action attribute for the form element.

```php
/**
 * @param string|null $form_action Current action URL (null uses AJAX)
 * @param \Core_Forms\Form $form The form object
 * @return string|null Modified action URL
 */
add_filter( 'cf_form_element_action_attr', function( $form_action, $form ) {
    // Submit to a custom endpoint instead of AJAX
    return 'https://example.com/custom-endpoint';
}, 10, 2 );
```

### `cf_form_element_data_attributes`

Filter the data attributes added to the form element.

```php
/**
 * @param array $attributes Current data attributes
 * @param \Core_Forms\Form $form The form object
 * @return array Modified attributes
 */
add_filter( 'cf_form_element_data_attributes', function( $attributes, $form ) {
    $attributes['my-custom-attr'] = 'custom-value';
    return $attributes;
}, 10, 2 );
```

### `cf_form_markup`

Filter the form markup (HTML content inside the form tags).

```php
/**
 * @param string $markup Current form markup
 * @param \Core_Forms\Form $form The form object
 * @return string Modified markup
 */
add_filter( 'cf_form_markup', function( $markup, $form ) {
    // Add a custom field to all forms
    $custom_field = '<input type="hidden" name="custom_field" value="custom_value" />';
    return $markup . $custom_field;
}, 10, 2 );
```

### `cf_form_html`

Filter the complete HTML output of a form (including form tags and comments).

```php
/**
 * @param string $html Complete form HTML
 * @param \Core_Forms\Form $form The form object
 * @return string Modified HTML
 */
add_filter( 'cf_form_html', function( $html, $form ) {
    // Wrap form in a custom container
    return '<div class="my-form-wrapper">' . $html . '</div>';
}, 10, 2 );
```

### `cf_shortcode_form_identifier`

Filter the form ID or slug before retrieving the form in shortcode.

```php
/**
 * @param string|int $form_id_or_slug Current identifier
 * @param array $attributes Shortcode attributes
 * @return string|int Modified identifier
 */
add_filter( 'cf_shortcode_form_identifier', function( $form_id_or_slug, $attributes ) {
    // Conditionally change which form is displayed
    if ( is_user_logged_in() ) {
        return 'logged-in-form';
    }
    return $form_id_or_slug;
}, 10, 2 );
```

---

## Form Processing

### `cf_ignored_field_names`

Add field names that should be ignored during form processing.

```php
/**
 * @param array $ignored_fields Current ignored field names
 * @return array Modified ignored fields
 */
add_filter( 'cf_ignored_field_names', function( $ignored_fields ) {
    $ignored_fields[] = 'my_custom_field';
    return $ignored_fields;
} );
```

### `cf_process_form`

Action fired during form processing, before validation.

```php
/**
 * @param \Core_Forms\Form $form The form object
 * @param \Core_Forms\Submission $submission The submission object
 * @param array $data Submitted form data
 * @param string $error_code Current error code (empty if no errors yet)
 */
add_action( 'cf_process_form', function( $form, $submission, $data, $error_code ) {
    // Log all form submissions
    error_log( sprintf( 'Form %d submitted with data: %s', $form->ID, print_r( $data, true ) ) );
}, 10, 4 );
```

### `cf_form_success`

Action fired when a form is successfully submitted (after validation passes).

```php
/**
 * @param \Core_Forms\Submission $submission The submission object
 * @param \Core_Forms\Form $form The form object
 */
add_action( 'cf_form_success', function( $submission, $form ) {
    // Custom action on successful submission
    // e.g., add user to a mailing list, create a custom post, etc.
}, 10, 2 );
```

### `cf_form_response`

Action fired when sending response to user (fires even on errors).

```php
/**
 * @param \Core_Forms\Form $form The form object
 * @param \Core_Forms\Submission $submission The submission object
 * @param string $error_code Error code (empty if successful)
 */
add_action( 'cf_form_response', function( $form, $submission, $error_code ) {
    // Track all form responses (success and errors)
}, 10, 3 );
```

---

## Form Validation

### `cf_validate_form`

Filter to add custom validation to forms.

```php
/**
 * @param string $error_code Current error code (empty if no errors)
 * @param \Core_Forms\Form $form The form object
 * @param array $data Submitted form data
 * @return string Error code (empty for no error, or message key)
 */
add_filter( 'cf_validate_form', function( $error_code, $form, $data ) {
    // Don't validate if there's already an error
    if ( ! empty( $error_code ) ) {
        return $error_code;
    }

    // Custom validation: check age
    if ( isset( $data['age'] ) && (int) $data['age'] < 18 ) {
        return 'age_too_young'; // This key should exist in form messages
    }

    return $error_code;
}, 10, 3 );
```

### Form-specific validation

You can target specific forms using the form ID or slug:

```php
// Validate only form with ID 123
add_filter( 'cf_validate_form', function( $error_code, $form, $data ) {
    if ( $form->ID !== 123 ) {
        return $error_code;
    }

    // Your validation logic here

    return $error_code;
}, 10, 3 );
```

---

## Submissions

### `cf_submission_inserted`

Action fired after a submission is saved to the database.

```php
/**
 * @param \Core_Forms\Submission $submission The submission object (with ID set)
 * @param \Core_Forms\Form $form The form object
 */
add_action( 'cf_submission_inserted', function( $submission, $form ) {
    // Send data to external API
    wp_remote_post( 'https://api.example.com/submissions', array(
        'body' => json_encode( array(
            'form_id' => $form->ID,
            'data' => $submission->data,
            'submitted_at' => $submission->submitted_at,
        ) ),
        'headers' => array( 'Content-Type' => 'application/json' ),
    ) );
}, 10, 2 );
```

---

## Settings & Messages

### `cf_form_default_settings`

Filter default settings for forms.

```php
/**
 * @param array $default_settings Current default settings
 * @return array Modified default settings
 */
add_filter( 'cf_form_default_settings', function( $default_settings ) {
    // Change default save_submissions to false
    $default_settings['save_submissions'] = 0;

    // Add custom default setting
    $default_settings['my_custom_setting'] = 'default_value';

    return $default_settings;
} );
```

### `cf_form_default_messages`

Filter default messages for forms.

```php
/**
 * @param array $default_messages Current default messages
 * @return array Modified default messages
 */
add_filter( 'cf_form_default_messages', function( $default_messages ) {
    // Customize default messages
    $default_messages['success'] = 'Thanks! We got your message.';

    // Add custom message
    $default_messages['age_too_young'] = 'You must be at least 18 years old.';

    return $default_messages;
} );
```

### `cf_form_message_{code}`

Filter specific message by code.

```php
/**
 * @param string $message Current message text
 * @param \Core_Forms\Form $form The form object
 * @return string Modified message
 */
add_filter( 'cf_form_message_success', function( $message, $form ) {
    // Customize success message for specific form
    if ( $form->slug === 'contact' ) {
        return 'Thanks for contacting us! We will reply within 24 hours.';
    }
    return $message;
}, 10, 2 );
```

### `cf_form_redirect_url`

Filter the redirect URL after successful submission.

```php
/**
 * @param string $redirect_url Current redirect URL
 * @param \Core_Forms\Form $form The form object
 * @param array $data Submitted form data
 * @return string Modified redirect URL
 */
add_filter( 'cf_form_redirect_url', function( $redirect_url, $form, $data ) {
    // Redirect based on form data
    if ( isset( $data['product'] ) && $data['product'] === 'premium' ) {
        return 'https://example.com/premium-thank-you';
    }
    return $redirect_url;
}, 10, 3 );
```

---

## Spam Protection

### `cf_recaptcha_min_score`

Filter the minimum reCAPTCHA score required (default: 0.5).

```php
/**
 * @param float $min_score Minimum score (0.0 to 1.0)
 * @return float Modified minimum score
 */
add_filter( 'cf_recaptcha_min_score', function( $min_score ) {
    // Require higher score for better spam protection
    return 0.7;
} );
```

### `cf_akismet_data`

Filter the data sent to Akismet for spam checking.

```php
/**
 * @param array $akismet_data Data sent to Akismet
 * @param \Core_Forms\Form $form The form object
 * @param array $data Submitted form data
 * @return array Modified Akismet data
 */
add_filter( 'cf_akismet_data', function( $akismet_data, $form, $data ) {
    // Add custom field to Akismet check
    if ( isset( $data['phone'] ) ) {
        $akismet_data['comment_content'] .= "\nPhone: " . $data['phone'];
    }
    return $akismet_data;
}, 10, 3 );
```

---

## Actions

### `cf_process_form_action_{type}`

Process a specific form action type.

```php
/**
 * @param array $action_settings Action configuration
 * @param \Core_Forms\Submission $submission The submission object
 * @param \Core_Forms\Form $form The form object
 */
// Example: Email action
add_action( 'cf_process_form_action_email', function( $action_settings, $submission, $form ) {
    // Custom email processing
}, 10, 3 );

// Example: Webhook action
add_action( 'cf_process_form_action_webhook', function( $action_settings, $submission, $form ) {
    // Custom webhook processing
}, 10, 3 );
```

### Creating Custom Actions

You can create your own form actions:

```php
// Register your action
add_filter( 'cf_form_actions', function( $actions ) {
    $actions['my_custom_action'] = array(
        'label' => 'My Custom Action',
        'description' => 'Does something custom',
    );
    return $actions;
} );

// Process your action
add_action( 'cf_process_form_action_my_custom_action', function( $action_settings, $submission, $form ) {
    // Your custom logic here
    // Access settings via $action_settings
    // Access form data via $submission->data
}, 10, 3 );
```

---

## Admin

### `cf_admin_output_form_tab_{tab}`

Output content for a specific admin form tab.

```php
/**
 * @param \Core_Forms\Form $form The form object
 */
add_action( 'cf_admin_output_form_tab_fields', function( $form ) {
    // Add content to the Fields tab
    echo '<p>Custom content in Fields tab</p>';
} );
```

### `cf_admin_output_form_settings`

Output additional settings in the Settings tab.

```php
/**
 * @param \Core_Forms\Form $form The form object
 */
add_action( 'cf_admin_output_form_settings', function( $form ) {
    ?>
    <tr valign="top">
        <th scope="row"><label for="my_setting">My Custom Setting</label></th>
        <td>
            <input type="text" id="my_setting" name="form[settings][my_setting]"
                   value="<?php echo esc_attr( $form->settings['my_setting'] ?? '' ); ?>" />
        </td>
    </tr>
    <?php
} );
```

### `cf_admin_output_form_messages`

Output additional message fields in the Messages tab.

```php
/**
 * @param \Core_Forms\Form $form The form object
 */
add_action( 'cf_admin_output_form_messages', function( $form ) {
    $custom_message = $form->messages['custom_error'] ?? 'Custom error occurred';
    ?>
    <tr valign="top">
        <th scope="row"><label for="custom_error">Custom Error</label></th>
        <td>
            <input type="text" class="widefat" id="custom_error"
                   name="form[messages][custom_error]"
                   value="<?php echo esc_attr( $custom_message ); ?>" />
        </td>
    </tr>
    <?php
} );
```

---

## Template Variables

Core Forms provides template variables that can be used in form markup, email templates, and redirect URLs:

- `[CF_TIMESTAMP]` - Current Unix timestamp
- `[CF_USER_AGENT]` - User's browser user agent
- `[CF_USER_IP]` - User's IP address
- `[CF_REFERER]` - Page the form was submitted from
- `[CF_FORM_ID]` - The form ID
- `[CF_FORM_TITLE]` - The form title
- `[CF_SUBMISSION_ID]` - The submission ID (only after save)
- `[all:label]` - All form fields with labels
- `[all]` - All form fields without labels
- `{field_name}` - Specific field value

### Using in Redirect URL

```php
// In form settings, set redirect_url to:
https://example.com/thank-you?submission={CF_SUBMISSION_ID}&email={email}
```

### Using in Email Template

```html
<p>Form submitted at: [CF_TIMESTAMP]</p>
<p>User IP: [CF_USER_IP]</p>
<p>Name: {name}</p>
<p>Email: {email}</p>
<p>Message: {message}</p>

<h3>All Fields:</h3>
[all:label]
```

---

## JavaScript Events

Core Forms triggers custom JavaScript events you can listen to:

```javascript
// Form submitted (before AJAX)
document.addEventListener('cf-submit', function(e) {
    const form = e.target;
    console.log('Form submitted:', form);
});

// Form submission sent to server
document.addEventListener('cf-submitted', function(e) {
    const form = e.target;
    console.log('Submission sent:', form);
});

// Form validation error or successful response
document.addEventListener('cf-error', function(e) {
    const form = e.target;
    console.log('Form error:', form);
});

// Successful form submission
document.addEventListener('cf-success', function(e) {
    const form = e.target;
    console.log('Form success:', form);
});

// Message displayed to user
document.addEventListener('cf-message', function(e) {
    const form = e.target;
    console.log('Message shown:', form);
});

// Form fields refreshed (conditional logic)
document.addEventListener('cf-refresh', function(e) {
    console.log('Form fields refreshed');
});
```

### Programmatic Form Submission

```javascript
// Submit a form programmatically
if (window.core_forms) {
    const form = document.querySelector('.cf-form');
    window.core_forms.submit(form);
}
```

---

## How Forms Work

Forms in Core Forms are built with **plain HTML**. The form markup is stored in the `post_content` field of the `core-form` post type.

### Basic Form Structure

```html
<p>
    <label>Your Name:</label>
    <input type="text" name="name" required />
</p>

<p>
    <label>Your Email:</label>
    <input type="email" name="email" required />
</p>

<p>
    <label>Message:</label>
    <textarea name="message" rows="5"></textarea>
</p>

<p>
    <input type="submit" value="Send Message" />
</p>
```

### Field Types Supported

All standard HTML5 form fields work:

- `<input type="text">` - Text input
- `<input type="email">` - Email input (auto-validated)
- `<input type="tel">` - Telephone input
- `<input type="number">` - Number input
- `<input type="date">` - Date picker
- `<input type="checkbox">` - Single checkbox or multiple
- `<input type="radio">` - Radio buttons
- `<textarea>` - Multi-line text
- `<select>` - Dropdown / Select box
- `<input type="hidden">` - Hidden fields
- `<button type="submit">` or `<input type="submit">` - Submit button

### Required Fields

Mark fields as required using the HTML5 `required` attribute:

```html
<input type="text" name="name" required />
```

Or specify in the Settings tab which fields are required.

### Conditional Fields

Show/hide fields based on other field values using `data-show-if` or `data-hide-if`:

```html
<!-- Show this field only if 'subscribe' checkbox is checked -->
<p data-show-if="subscribe:1">
    <label>Email:</label>
    <input type="email" name="email" />
</p>

<!-- Hide this field if product is "basic" -->
<p data-hide-if="product:basic">
    <label>Premium Features:</label>
    <input type="text" name="premium_features" />
</p>
```

Syntax: `data-show-if="field_name:value"` or `data-show-if="field_name:value1|value2"`

---

## Common Use Cases

### Example 1: Log all submissions to a custom table

```php
add_action( 'cf_submission_inserted', function( $submission, $form ) {
    global $wpdb;

    $wpdb->insert(
        $wpdb->prefix . 'my_custom_log',
        array(
            'form_id' => $form->ID,
            'email' => $submission->data['email'] ?? '',
            'logged_at' => current_time( 'mysql' ),
        )
    );
}, 10, 2 );
```

### Example 2: Block submissions from specific email domains

```php
add_filter( 'cf_validate_form', function( $error_code, $form, $data ) {
    if ( ! empty( $error_code ) ) {
        return $error_code;
    }

    if ( isset( $data['email'] ) ) {
        $blocked_domains = array( 'tempmail.com', 'throwaway.email' );
        $email_domain = substr( strrchr( $data['email'], '@' ), 1 );

        if ( in_array( $email_domain, $blocked_domains ) ) {
            return 'blocked_email_domain';
        }
    }

    return $error_code;
}, 10, 3 );

// Add the error message
add_filter( 'cf_form_default_messages', function( $messages ) {
    $messages['blocked_email_domain'] = 'Sorry, that email domain is not allowed.';
    return $messages;
} );
```

### Example 3: Create a WordPress user from form submission

```php
add_action( 'cf_form_success', function( $submission, $form ) {
    // Only for the registration form
    if ( $form->slug !== 'registration' ) {
        return;
    }

    $data = $submission->data;

    // Create user
    $user_id = wp_create_user(
        $data['username'],
        $data['password'],
        $data['email']
    );

    if ( ! is_wp_error( $user_id ) ) {
        // Update user meta
        wp_update_user( array(
            'ID' => $user_id,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
        ) );
    }
}, 10, 2 );
```

### Example 4: Add Google Analytics tracking

```php
add_action( 'cf_form_success', function( $submission, $form ) {
    ?>
    <script>
    if (typeof gtag !== 'undefined') {
        gtag('event', 'form_submission', {
            'event_category': 'Forms',
            'event_label': '<?php echo esc_js( $form->title ); ?>',
            'value': <?php echo $form->ID; ?>
        });
    }
    </script>
    <?php
}, 10, 2 );
```

---

## Support

For more information, visit [Core Forms Documentation](https://gauravtiwari.org/plugins/core-forms/)
