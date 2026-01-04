# WordPress Development Instructions

This document tells LLMs and developers how to code for WordPress projects under my supervision. Follow these guidelines. They exist because I've learned the hard way what works and what causes headaches six months down the line.

---

## About the Developer

**Gaurav Tiwari** - WordPress developer, educator, and entrepreneur with 16+ years of hands-on experience.

- **Agency**: Gatilab (marketing agency)
- **Primary Platform**: gauravtiwari.org (1,750+ published articles)
- **WordPress Credentials**: Core contributor, plugin developer (7,500+ active users across plugins)
- **Technical Stack**: Hetzner Cloud, CloudPanel, GeneratePress, FlyingPress, Redis object caching
- **Frameworks**: Custom md.css utility framework, ACF Pro, Tangible Loops & Logic
- **Location**: Kushinagar, India

I care deeply about performance, clean code, and solutions that don't break when WordPress updates. If you're writing code for my projects, you're writing code that needs to work reliably for years, not just pass a quick demo.

---

## Core Philosophy

1. **Performance is non-negotiable.** Every database query, every HTTP request, every enqueued asset matters.
2. **WordPress standards exist for good reasons.** Follow them unless you have a damn good reason not to.
3. **Future-proof over clever.** I'd rather have boring, maintainable code than something impressive that nobody can debug.
4. **Security by default.** Sanitize inputs. Escape outputs. Always.

---

## PHP Coding Standards

Follow the [WordPress PHP Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/) with these specifics:

### Naming Conventions

Use the product/plugin name as the prefix. For example, if building "Link Central", use `lc_` or `linkcentral_`. If building "Speed Optimizer", use `so_` or `speedopt_`.

```php
// Functions: lowercase with underscores, prefixed with product name
function {product}_get_custom_field( $field_name, $post_id = null ) {
    // Example: linkcentral_get_custom_field() or speedopt_get_custom_field()
}

// Classes: capitalized words, prefixed with product name
class {Product}_Custom_Post_Types {
    // Example: LinkCentral_Custom_Post_Types or SpeedOpt_Custom_Post_Types
}

// Constants: uppercase with underscores, prefixed with product name
define( '{PRODUCT}_PLUGIN_VERSION', '1.0.0' );
// Example: LINKCENTRAL_PLUGIN_VERSION or SPEEDOPT_PLUGIN_VERSION

// Variables: lowercase with underscores
$post_meta_value = get_post_meta( $post_id, '_{product}_custom_field', true );
// Example: _linkcentral_custom_field or _speedopt_custom_field
```

### Prefix Everything

Use `{product}_` for functions, `{Product}_` for classes, `_{product}_` for meta keys. This prevents conflicts with other plugins and themes.

**Choosing a prefix:**
- Use 2-4 letter abbreviation for short names (lc, so, wpo)
- Use full lowercase name for clarity when it's short (jenga, spark)
- Be consistent across the entire codebase

**Bad:**
```php
function get_settings() { } // Will conflict with something
```

**Good:**
```php
function linkcentral_get_settings() { } // Clear ownership
function lc_get_settings() { } // Also acceptable if consistent
```

### Spacing and Formatting

```php
// Space after control structure keywords
if ( $condition ) {
    // Space inside parentheses
}

// Space around operators
$total = $price + $tax;

// Arrays: space after opening and before closing brackets
$args = array(
    'post_type'      => 'post',
    'posts_per_page' => 10,
    'orderby'        => 'date',
);

// Or short array syntax (I prefer this for new code)
$args = [
    'post_type'      => 'post',
    'posts_per_page' => 10,
    'orderby'        => 'date',
];
```

**Note:** All examples below use `{product}` as a placeholder. Replace with your actual product prefix (e.g., `linkcentral`, `speedopt`, `jenga`).

### Yoda Conditions

WordPress uses Yoda conditions. I know they look weird. Use them anyway.

```php
// Correct (Yoda)
if ( true === $value ) {
    // ...
}

// Incorrect
if ( $value === true ) {
    // ...
}
```

### Type Declarations (PHP 7.4+)

Use type hints where possible. We're not stuck in PHP 5.6 anymore.

```php
function {product}_calculate_discount( float $price, int $percentage ): float {
    return $price * ( 1 - $percentage / 100 );
}
```

---

## JavaScript Coding Standards

Follow [WordPress JavaScript Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/javascript/).

### Modern JavaScript

Write ES6+ but compile to ES5 for broad compatibility when needed.

```javascript
// Use const and let, never var
const settings = window.{product}Settings || {};
let isLoading = false;

// Arrow functions for callbacks
document.querySelectorAll( '.{product}-button' ).forEach( ( button ) => {
    button.addEventListener( 'click', handleClick );
} );

// Template literals
const message = `Hello, ${ userName }. You have ${ count } notifications.`;
```

### jQuery Usage

Avoid jQuery in new code unless you're building on existing jQuery-dependent code. Vanilla JS is faster and doesn't add a dependency.

**If you must use jQuery:**
```javascript
( function( $ ) {
    'use strict';
    
    $( document ).ready( function() {
        // Your code here
    } );
} )( jQuery );
```

### Enqueueing Scripts

Always use `wp_enqueue_script()`. Never dump scripts directly in templates.

```php
function {product}_enqueue_scripts() {
    wp_enqueue_script(
        '{product}-main',
        {PRODUCT}_PLUGIN_URL . 'assets/js/main.js',
        [], // Dependencies
        {PRODUCT}_PLUGIN_VERSION,
        true // Load in footer
    );
    
    // Pass data to JS
    wp_localize_script( '{product}-main', '{product}Data', [
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( '{product}_ajax_nonce' ),
    ] );
}
add_action( 'wp_enqueue_scripts', '{product}_enqueue_scripts' );
```

---

## CSS Coding Standards

Follow [WordPress CSS Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/css/).

### Class Naming

Use BEM-style naming or a clear prefix system.

```css
/* Block */
.{product}-card { }

/* Element */
.{product}-card__title { }
.{product}-card__content { }

/* Modifier */
.{product}-card--featured { }
.{product}-card--compact { }
```

### Specificity

Keep specificity low. Avoid IDs in CSS. Avoid `!important` unless overriding third-party styles.

```css
/* Good: low specificity */
.{product}-button {
    background: #0073aa;
}

/* Bad: unnecessarily high specificity */
#main-content div.wrapper .{product}-button {
    background: #0073aa;
}
```

### CSS Custom Properties

Use CSS variables for colors, spacing, and typography.

```css
:root {
    --{product}-color-primary: #0073aa;
    --{product}-color-secondary: #23282d;
    --{product}-spacing-sm: 0.5rem;
    --{product}-spacing-md: 1rem;
    --{product}-spacing-lg: 2rem;
}

.{product}-button {
    background: var( --{product}-color-primary );
    padding: var( --{product}-spacing-sm ) var( --{product}-spacing-md );
}
```

### My md.css Framework

If you're working on my projects, you'll likely encounter my md.css utility framework. It's a lightweight utility CSS system. Check the project's existing styles before adding new CSS. Don't duplicate utilities that already exist.

---

## Database Operations

### Use WordPress APIs

Never write raw SQL unless absolutely necessary. When you must, use `$wpdb` with prepared statements.

```php
// Good: using WP APIs
$posts = get_posts( [
    'post_type'   => 'product',
    'meta_key'    => '_price',
    'meta_value'  => 100,
    'meta_compare' => '>=',
] );

// When raw SQL is necessary
global $wpdb;
$results = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s",
        'product',
        'publish'
    )
);
```

### Caching Database Queries

Use transients for expensive queries. But don't over-cache. Set reasonable expiration times.

```php
function {product}_get_featured_products() {
    $cache_key = '{product}_featured_products';
    $products  = get_transient( $cache_key );
    
    if ( false === $products ) {
        $products = get_posts( [
            'post_type'      => 'product',
            'posts_per_page' => 10,
            'meta_key'       => '_featured',
            'meta_value'     => 'yes',
        ] );
        
        set_transient( $cache_key, $products, HOUR_IN_SECONDS );
    }
    
    return $products;
}
```

### Invalidate Caches Properly

When data changes, delete the relevant transients.

```php
function {product}_clear_product_cache( $post_id ) {
    if ( 'product' === get_post_type( $post_id ) ) {
        delete_transient( '{product}_featured_products' );
    }
}
add_action( 'save_post', '{product}_clear_product_cache' );
```

---

## Security Requirements

### Input Sanitization

Sanitize all input. Every time. No exceptions.

```php
// Text fields
$title = sanitize_text_field( $_POST['title'] );

// Textareas (preserves line breaks)
$content = sanitize_textarea_field( $_POST['content'] );

// Email
$email = sanitize_email( $_POST['email'] );

// URLs
$url = esc_url_raw( $_POST['url'] );

// Arrays
$ids = array_map( 'absint', $_POST['ids'] );

// HTML (when you need to allow some tags)
$html = wp_kses_post( $_POST['html_content'] );
```

### Output Escaping

Escape all output. Match the escaping function to the context.

```php
// In HTML context
echo esc_html( $user_input );

// In HTML attributes
echo '<input value="' . esc_attr( $value ) . '">';

// In URLs
echo '<a href="' . esc_url( $link ) . '">';

// In JavaScript
echo '<script>var data = ' . wp_json_encode( $data ) . ';</script>';

// Translated strings with HTML
echo wp_kses_post( __( 'Click <strong>here</strong> to continue.', 'gt-plugin' ) );
```

### Nonce Verification

Always verify nonces for form submissions and AJAX requests.

```php
// Creating a nonce
wp_nonce_field( '{product}_save_settings', '{product}_nonce' );

// Verifying a nonce
if ( ! isset( $_POST['{product}_nonce'] ) || ! wp_verify_nonce( $_POST['{product}_nonce'], '{product}_save_settings' ) ) {
    wp_die( 'Security check failed' );
}

// For AJAX
check_ajax_referer( '{product}_ajax_nonce', 'nonce' );
```

### Capability Checks

Verify user capabilities before performing actions.

```php
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( 'Unauthorized access' );
}
```

---

## Performance Guidelines

### Minimize Database Queries

Use `WP_Query` arguments efficiently. Don't query what you don't need.

```php
// Good: only get what you need
$args = [
    'post_type'              => 'post',
    'posts_per_page'         => 10,
    'no_found_rows'          => true, // Skip pagination count if not needed
    'update_post_meta_cache' => false, // Skip meta cache if not using meta
    'update_post_term_cache' => false, // Skip term cache if not using terms
    'fields'                 => 'ids', // Only get IDs if that's all you need
];
```

### Lazy Load Everything Possible

Don't load resources until they're needed.

```php
// Conditional asset loading
function {product}_enqueue_conditional_scripts() {
    if ( is_singular( 'product' ) ) {
        wp_enqueue_script( '{product}-product-gallery' );
    }
}
add_action( 'wp_enqueue_scripts', '{product}_enqueue_conditional_scripts' );
```

### Avoid Loading in wp_head

Heavy operations in `wp_head` block rendering. Move them to `wp_footer` when possible.

### Batch Operations

When processing multiple items, batch them.

```php
// Bad: individual queries in a loop
foreach ( $post_ids as $id ) {
    $meta = get_post_meta( $id, '_custom_field', true );
}

// Good: prime the cache first
update_meta_cache( 'post', $post_ids );
foreach ( $post_ids as $id ) {
    $meta = get_post_meta( $id, '_custom_field', true );
}
```

---

## Plugin Development Structure

```
{product}-plugin/
├── {product}-plugin.php        # Main plugin file
├── uninstall.php               # Cleanup on uninstall
├── readme.txt                  # WordPress.org readme
├── includes/
│   ├── class-{product}.php     # Main plugin class
│   ├── class-{product}-admin.php    # Admin functionality
│   ├── class-{product}-public.php   # Frontend functionality
│   └── functions.php           # Helper functions
├── admin/
│   ├── css/
│   ├── js/
│   └── views/                  # Admin templates
├── public/
│   ├── css/
│   ├── js/
│   └── views/                  # Frontend templates
├── languages/
│   └── {product}-plugin.pot    # Translation template
└── assets/
    └── images/
```

### Main Plugin File Header

```php
<?php
/**
 * Plugin Name:       {Product} Plugin Name
 * Plugin URI:        https://gauravtiwari.org/plugins/{product}-plugin
 * Description:       A brief description of what this plugin does.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Gaurav Tiwari
 * Author URI:        https://gauravtiwari.org
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       {product}-plugin
 * Domain Path:       /languages
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin constants
define( '{PRODUCT}_PLUGIN_VERSION', '1.0.0' );
define( '{PRODUCT}_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( '{PRODUCT}_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
```

### Activation and Deactivation Hooks

```php
register_activation_hook( __FILE__, '{product}_plugin_activate' );
function {product}_plugin_activate() {
    // Create database tables if needed
    // Set default options
    // Flush rewrite rules if registering CPTs
    flush_rewrite_rules();
}

register_deactivation_hook( __FILE__, '{product}_plugin_deactivate' );
function {product}_plugin_deactivate() {
    // Clean up scheduled events
    wp_clear_scheduled_hook( '{product}_daily_cron' );
    // Flush rewrite rules
    flush_rewrite_rules();
}
```

### Uninstall.php

Always include an uninstall.php that cleans up after your plugin.

```php
<?php
// If uninstall not called from WordPress, exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Delete options
delete_option( '{product}_plugin_settings' );

// Delete post meta
delete_post_meta_by_key( '_{product}_custom_field' );

// Delete transients
delete_transient( '{product}_cached_data' );

// Drop custom tables if any
global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}{product}_custom_table" );
```

---

## Theme Development Structure

```
{product}-theme/
├── style.css                  # Theme header and main styles
├── functions.php              # Theme setup and functions
├── index.php                  # Fallback template
├── front-page.php             # Homepage template
├── single.php                 # Single post template
├── page.php                   # Page template
├── archive.php                # Archive template
├── search.php                 # Search results
├── 404.php                    # 404 template
├── header.php                 # Header template
├── footer.php                 # Footer template
├── sidebar.php                # Sidebar template
├── inc/
│   ├── theme-setup.php        # Theme support and menus
│   ├── enqueue.php            # Scripts and styles
│   ├── customizer.php         # Customizer options
│   └── template-tags.php      # Custom template tags
├── template-parts/
│   ├── content.php
│   ├── content-page.php
│   └── content-none.php
├── assets/
│   ├── css/
│   ├── js/
│   ├── images/
│   └── fonts/
└── languages/
```

### Theme Setup

```php
function {product}_theme_setup() {
    // Translation support
    load_theme_textdomain( '{product}-theme', get_template_directory() . '/languages' );
    
    // Theme supports
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'html5', [
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ] );
    add_theme_support( 'customize-selective-refresh-widgets' );
    add_theme_support( 'editor-styles' );
    add_theme_support( 'responsive-embeds' );
    
    // Register menus
    register_nav_menus( [
        'primary' => __( 'Primary Menu', '{product}-theme' ),
        'footer'  => __( 'Footer Menu', '{product}-theme' ),
    ] );
    
    // Image sizes
    add_image_size( '{product}-featured', 1200, 630, true );
    add_image_size( '{product}-thumbnail', 400, 300, true );
}
add_action( 'after_setup_theme', '{product}_theme_setup' );
```

---

## Block Editor (Gutenberg) Development

### Register Custom Blocks

```php
function {product}_register_blocks() {
    register_block_type( {PRODUCT}_PLUGIN_PATH . 'blocks/custom-block' );
}
add_action( 'init', '{product}_register_blocks' );
```

### Block.json Structure

```json
{
    "$schema": "https://schemas.wp.org/trunk/block.json",
    "apiVersion": 3,
    "name": "{product}/custom-block",
    "version": "1.0.0",
    "title": "{Product} Custom Block",
    "category": "widgets",
    "icon": "admin-generic",
    "description": "A custom block for specific functionality.",
    "supports": {
        "html": false,
        "align": ["wide", "full"]
    },
    "textdomain": "{product}-plugin",
    "editorScript": "file:./index.js",
    "editorStyle": "file:./index.css",
    "style": "file:./style-index.css",
    "attributes": {
        "content": {
            "type": "string",
            "default": ""
        }
    }
}
```

### Custom Block Styles

```php
function {product}_register_block_styles() {
    register_block_style( 'core/button', [
        'name'  => '{product}-gradient',
        'label' => __( 'Gradient', '{product}-theme' ),
    ] );
    
    register_block_style( 'core/group', [
        'name'  => '{product}-card',
        'label' => __( 'Card', '{product}-theme' ),
    ] );
}
add_action( 'init', '{product}_register_block_styles' );
```

---

## AJAX Handling

### Frontend AJAX

```php
// Register AJAX handlers
add_action( 'wp_ajax_{product}_load_more', '{product}_ajax_load_more' );
add_action( 'wp_ajax_nopriv_{product}_load_more', '{product}_ajax_load_more' ); // For non-logged-in users

function {product}_ajax_load_more() {
    // Verify nonce
    check_ajax_referer( '{product}_ajax_nonce', 'nonce' );
    
    // Get and sanitize parameters
    $page = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
    
    // Your logic here
    $posts = get_posts( [
        'posts_per_page' => 10,
        'paged'          => $page,
    ] );
    
    if ( empty( $posts ) ) {
        wp_send_json_error( [ 'message' => 'No more posts' ] );
    }
    
    // Return success response
    wp_send_json_success( [
        'posts' => $posts,
        'html'  => {product}_render_posts( $posts ),
    ] );
}
```

### JavaScript Side

```javascript
const loadMore = async ( page ) => {
    const response = await fetch( {product}Data.ajaxUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams( {
            action: '{product}_load_more',
            nonce: {product}Data.nonce,
            page: page,
        } ),
    } );
    
    const data = await response.json();
    
    if ( data.success ) {
        // Handle success
        document.querySelector( '.posts-container' ).insertAdjacentHTML( 'beforeend', data.data.html );
    } else {
        // Handle error
        console.log( data.data.message );
    }
};
```

---

## REST API

### Registering Custom Endpoints

```php
function {product}_register_rest_routes() {
    register_rest_route( '{product}/v1', '/products', [
        'methods'             => 'GET',
        'callback'            => '{product}_rest_get_products',
        'permission_callback' => '__return_true', // Public endpoint
        'args'                => [
            'per_page' => [
                'default'           => 10,
                'sanitize_callback' => 'absint',
            ],
        ],
    ] );
    
    register_rest_route( '{product}/v1', '/products', [
        'methods'             => 'POST',
        'callback'            => '{product}_rest_create_product',
        'permission_callback' => function() {
            return current_user_can( 'edit_posts' );
        },
    ] );
}
add_action( 'rest_api_init', '{product}_register_rest_routes' );

function {product}_rest_get_products( WP_REST_Request $request ) {
    $per_page = $request->get_param( 'per_page' );
    
    $products = get_posts( [
        'post_type'      => 'product',
        'posts_per_page' => $per_page,
    ] );
    
    return rest_ensure_response( $products );
}
```

---

## Internationalization (i18n)

### Translatable Strings

```php
// Simple string
__( 'Hello World', '{product}-plugin' );

// Echo directly
esc_html_e( 'Settings saved.', '{product}-plugin' );

// With placeholders
sprintf(
    /* translators: %s: user display name */
    __( 'Welcome back, %s!', '{product}-plugin' ),
    esc_html( $user->display_name )
);

// Plural forms
sprintf(
    /* translators: %d: number of items */
    _n(
        '%d item found.',
        '%d items found.',
        $count,
        '{product}-plugin'
    ),
    $count
);

// With context
_x( 'Post', 'noun', '{product}-plugin' ); // A blog post
_x( 'Post', 'verb', '{product}-plugin' ); // To post something
```

### Generate POT File

Use WP-CLI:
```bash
wp i18n make-pot . languages/{product}-plugin.pot --domain={product}-plugin
```

---

## Testing

### PHPUnit for WordPress

Set up WordPress test suite. Test all custom functionality.

```php
class {Product}_Plugin_Test extends WP_UnitTestCase {
    
    public function test_custom_function_returns_expected_value() {
        $result = {product}_custom_function( 'input' );
        $this->assertEquals( 'expected output', $result );
    }
    
    public function test_post_meta_is_saved() {
        $post_id = $this->factory->post->create();
        {product}_save_custom_meta( $post_id, 'test_value' );
        
        $meta = get_post_meta( $post_id, '_{product}_custom_meta', true );
        $this->assertEquals( 'test_value', $meta );
    }
}
```

### Manual Testing Checklist

Before any release:
- [ ] Test on fresh WordPress install
- [ ] Test with default themes (Twenty Twenty-Four, etc.)
- [ ] Test with popular plugins (WooCommerce, Yoast, etc.)
- [ ] Test with PHP 7.4, 8.0, 8.1, 8.2
- [ ] Test activation, deactivation, uninstall
- [ ] Test with debug mode enabled (`WP_DEBUG = true`)
- [ ] Check Query Monitor for performance issues
- [ ] Validate HTML output (W3C validator)
- [ ] Test responsive layouts
- [ ] Test keyboard navigation and screen readers

---

## Tools I Use

These are my standard tools. Use them if they're available in the project:

- **Hosting**: Hetzner Cloud with CloudPanel
- **Theme**: GeneratePress (for client sites)
- **Page Builder**: GenerateBlocks (when needed)
- **Caching**: FlyingPress
- **Object Cache**: Redis
- **CDN**: Cloudflare or Bunny CDN
- **Forms**: Fluent Forms
- **ACF Pro**: For custom fields
- **Tangible Loops & Logic**: For dynamic content templating
- **Query Monitor**: For debugging (development only)

---

## Code Review Checklist

Before submitting code:

1. **Security**
   - [ ] All inputs sanitized
   - [ ] All outputs escaped
   - [ ] Nonces verified for forms/AJAX
   - [ ] Capability checks in place

2. **Performance**
   - [ ] No unnecessary database queries
   - [ ] Expensive queries cached
   - [ ] Assets conditionally loaded
   - [ ] No blocking operations in wp_head

3. **Standards**
   - [ ] Follows WordPress coding standards
   - [ ] Functions/classes properly prefixed
   - [ ] Translatable strings wrapped correctly
   - [ ] DocBlocks on functions and classes

4. **Quality**
   - [ ] No PHP errors or warnings
   - [ ] No JavaScript console errors
   - [ ] Works with WP_DEBUG enabled
   - [ ] Tested in multiple browsers

---

## Common Mistakes to Avoid

1. **Don't query inside loops.** Prime caches or restructure your code.

2. **Don't use `query_posts()`.** Ever. Use `WP_Query` or `get_posts()`.

3. **Don't modify `$wp_query` directly.** Use `pre_get_posts` hook.

4. **Don't hardcode URLs.** Use `home_url()`, `admin_url()`, `plugins_url()`.

5. **Don't assume timezone.** Use `wp_date()` instead of `date()`.

6. **Don't echo unescaped data.** Even if you think it's safe.

7. **Don't use `extract()`.** It makes code unreadable and error-prone.

8. **Don't rely on global variables.** Pass data explicitly.

9. **Don't skip text domains.** Every string should be translatable.

10. **Don't forget to flush rewrite rules.** After registering CPTs or taxonomies.

---

## Questions?

If something isn't covered here, check:
1. [WordPress Developer Resources](https://developer.wordpress.org/)
2. [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
3. [Plugin Handbook](https://developer.wordpress.org/plugins/)
4. [Theme Handbook](https://developer.wordpress.org/themes/)

Or ask me directly. I'd rather answer questions upfront than fix problems later.

---

*Last updated: January 2026*
*Maintained by: Gaurav Tiwari (gauravtiwari.org)*
