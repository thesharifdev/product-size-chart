# WordPress Plugin Development: Hooks, Actions & Filters

## Session Overview
**Topic:** Leveling Up WordPress Plugin Development: Hooks, Actions & Filters

---

## 1. Introduction to WordPress Hooks

### What are Hooks?
Hooks are WordPress's way of allowing plugins and themes to interact with the core functionality without modifying core files. They enable you to "hook into" WordPress at specific points during execution.

### Two Types of Hooks

**Actions** - Execute custom code at specific points
- Do something (add functionality)
- Example: Send an email when a post is published

**Filters** - Modify data before it's displayed or saved
- Change something (modify data)
- Example: Add text to the end of every post

---

## 2. Hook Basics

### Adding Action Hooks

**Syntax:**
```php
add_action( $hook_name, $callback_function, $priority, $accepted_args );
```

**Parameters:**
- `$hook_name` (string) - Name of the action hook
- `$callback_function` (callable) - Function to execute
- `$priority` (int) - Order of execution (default: 10, lower = earlier)
- `$accepted_args` (int) - Number of arguments the function accepts (default: 1)

**Example:**
```php
function my_custom_footer_text() {
    echo '<p>Copyright © 2026 My Website</p>';
}
add_action( 'wp_footer', 'my_custom_footer_text', 20, 0 );
```

### Adding Filter Hooks

**Syntax:**
```php
add_filter( $hook_name, $callback_function, $priority, $accepted_args );
```

**Example:**
```php
function modify_post_title( $title, $post_id ) {
    if ( is_single() ) {
        return '★ ' . $title;
    }
    return $title;
}
add_filter( 'the_title', 'modify_post_title', 10, 2 );
```

### Executing Custom Hooks

**do_action()** - Trigger custom action hooks
```php
// In your plugin file
do_action( 'my_plugin_before_save', $data );

// Other developers can hook into this
add_action( 'my_plugin_before_save', function( $data ) {
    // Custom code here
}, 10, 1 );
```

**apply_filters()** - Trigger custom filter hooks
```php
// In your plugin file
$output = apply_filters( 'my_plugin_output', $output, $post_id );

// Other developers can modify this
add_filter( 'my_plugin_output', function( $output, $post_id ) {
    return $output . ' - Modified!';
}, 10, 2 );
```

### Priority System

**How Priority Works:**
- Lower numbers execute earlier (1-9 = very early)
- Default priority is 10
- Higher numbers execute later (11-999 = later)

**Example:**
```php
add_action( 'init', 'first_function', 5 );   // Runs first
add_action( 'init', 'second_function', 10 );  // Runs second
add_action( 'init', 'third_function', 15 );   // Runs third
```

### Accepted Arguments

Specify how many parameters your callback receives:

```php
// Hook passes 3 arguments
add_filter( 'the_content', 'my_content_filter', 10, 1 ); // Only receives content

function my_content_filter( $content ) {
    return $content . '<p>End of content</p>';
}

// Multiple arguments example
add_action( 'save_post', 'my_save_handler', 10, 3 );

function my_save_handler( $post_id, $post, $update ) {
    // Access all three parameters
}
```

---

## 3. Removing Hooks

### remove_action()

Remove previously added action hooks:

```php
remove_action( $hook_name, $callback_function, $priority );
```

**Example:**
```php
// Remove WordPress default functionality
remove_action( 'wp_head', 'wp_generator' );

// Remove a specific plugin's action
remove_action( 'wp_footer', 'some_plugin_function', 15 );
```

**Important:** Must be called after the action was added, typically in `init` or later hooks.

### remove_filter()

Remove previously added filter hooks:

```php
remove_filter( $hook_name, $callback_function, $priority );
```

**Example:**
```php
// Remove default WordPress filters
remove_filter( 'the_content', 'wpautop' );

// Remove custom filter
remove_filter( 'the_title', 'another_plugin_title_filter', 10 );
```

### Removing Class-Based Hooks

When the callback is a class method:

```php
// For static methods
remove_action( 'init', array( 'ClassName', 'method_name' ), 10 );

// For instance methods (need the instance)
global $my_plugin_instance;
remove_action( 'init', array( $my_plugin_instance, 'method_name' ), 10 );
```

### Best Practices for Removal
```php
function remove_unwanted_hooks() {
    // Always check if function exists
    if ( function_exists( 'wp_generator' ) ) {
        remove_action( 'wp_head', 'wp_generator' );
    }
}
add_action( 'init', 'remove_unwanted_hooks' );
```

---

## 4. Enqueueing Scripts Properly

### register_script vs enqueue_script

**wp_register_script()** - Register a script for later use
```php
wp_register_script(
    'my-script-handle',           // Handle (unique ID)
    plugins_url( 'js/script.js', __FILE__ ),  // Source URL
    array( 'jquery' ),            // Dependencies
    '1.0.0',                      // Version
    true                          // Load in footer
);
```

**wp_enqueue_script()** - Actually load the script
```php
wp_enqueue_script( 'my-script-handle' );

// Or register and enqueue in one step
wp_enqueue_script(
    'my-script-handle',
    plugins_url( 'js/script.js', __FILE__ ),
    array( 'jquery' ),
    '1.0.0',
    true
);
```

### Screen-Specific Loading

Only load scripts where needed to improve performance:

```php
function my_plugin_enqueue_scripts( $hook ) {
    // Only load on plugin's admin page
    if ( 'toplevel_page_my-plugin' !== $hook ) {
        return;
    }
    
    wp_enqueue_script(
        'my-admin-script',
        plugins_url( 'js/admin.js', __FILE__ ),
        array( 'jquery' ),
        '1.0.0',
        true
    );
}
add_action( 'admin_enqueue_scripts', 'my_plugin_enqueue_scripts' );
```

### Conditional Loading Examples

**Load only on specific post types:**
```php
function enqueue_for_custom_post_type() {
    global $post_type;
    
    if ( 'product' === $post_type ) {
        wp_enqueue_script( 'product-script', plugins_url( 'js/product.js', __FILE__ ) );
    }
}
add_action( 'admin_enqueue_scripts', 'enqueue_for_custom_post_type' );
```

**Load only on frontend single posts:**
```php
function frontend_scripts() {
    if ( is_single() ) {
        wp_enqueue_script(
            'single-post-script',
            plugins_url( 'js/single.js', __FILE__ ),
            array( 'jquery' ),
            '1.0.0',
            true
        );
    }
}
add_action( 'wp_enqueue_scripts', 'frontend_scripts' );
```

### Styles Work the Same Way

```php
wp_enqueue_style(
    'my-plugin-style',
    plugins_url( 'css/style.css', __FILE__ ),
    array(),
    '1.0.0',
    'all'  // Media type
);
```

---

## 5. Inline Scripts

### What are Inline Scripts?

Inline scripts let you add JavaScript directly to the page, typically used for:
- Passing PHP variables to JavaScript
- Adding small configuration snippets
- Dynamic JavaScript based on PHP logic

### wp_add_inline_script()

Add inline JavaScript to an enqueued script:

```php
function my_plugin_inline_script() {
    // First, enqueue the main script
    wp_enqueue_script(
        'my-main-script',
        plugins_url( 'js/main.js', __FILE__ ),
        array( 'jquery' ),
        '1.0.0',
        true
    );
    
    // Add inline script after the main script
    $inline_script = "
        jQuery(document).ready(function($) {
            console.log('Plugin initialized');
        });
    ";
    
    wp_add_inline_script( 'my-main-script', $inline_script );
}
add_action( 'wp_enqueue_scripts', 'my_plugin_inline_script' );
```

### Passing PHP Data to JavaScript

**Using wp_localize_script()** (recommended method):

```php
function pass_data_to_js() {
    wp_enqueue_script(
        'my-ajax-script',
        plugins_url( 'js/ajax.js', __FILE__ ),
        array( 'jquery' ),
        '1.0.0',
        true
    );
    
    // Pass data to JavaScript
    wp_localize_script(
        'my-ajax-script',
        'myPluginData',  // JavaScript object name
        array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'my_plugin_nonce' ),
            'postId'  => get_the_ID(),
            'userId'  => get_current_user_id(),
            'strings' => array(
                'loading' => __( 'Loading...', 'my-plugin' ),
                'error'   => __( 'An error occurred', 'my-plugin' )
            )
        )
    );
}
add_action( 'wp_enqueue_scripts', 'pass_data_to_js' );
```

**In your JavaScript file (ajax.js):**
```javascript
jQuery(document).ready(function($) {
    // Access the localized data
    console.log(myPluginData.ajaxUrl);
    console.log(myPluginData.postId);
    
    $.ajax({
        url: myPluginData.ajaxUrl,
        type: 'POST',
        data: {
            action: 'my_plugin_action',
            nonce: myPluginData.nonce,
            post_id: myPluginData.postId
        },
        success: function(response) {
            alert(myPluginData.strings.loading);
        }
    });
});
```

### Position: Before vs After

```php
// Add inline script BEFORE the enqueued script
wp_add_inline_script( 'my-script', $inline_js, 'before' );

// Add inline script AFTER the enqueued script (default)
wp_add_inline_script( 'my-script', $inline_js, 'after' );
```

### Inline Styles

Works similarly for CSS:

```php
function my_inline_styles() {
    wp_enqueue_style( 'my-style', plugins_url( 'css/style.css', __FILE__ ) );
    
    $custom_css = "
        .my-plugin-wrapper {
            background-color: " . get_option( 'my_plugin_bg_color', '#ffffff' ) . ";
            padding: 20px;
        }
    ";
    
    wp_add_inline_style( 'my-style', $custom_css );
}
add_action( 'wp_enqueue_scripts', 'my_inline_styles' );
```

---

## 6. Real-World Example: Complete Plugin: Auto Cart Recovery


---

## 7. Best Practices

### Hook Naming Conventions
- Use unique prefixes: `my_plugin_action_name`
- Be descriptive: `my_plugin_before_save_post`
- Use lowercase with underscores


## 8. Resources & Further Learning

### Official Documentation
- [WordPress Plugin Handbook - Hooks](https://developer.wordpress.org/plugins/hooks/)
- [Action Reference](https://codex.wordpress.org/Plugin_API/Action_Reference)
- [Filter Reference](https://codex.wordpress.org/Plugin_API/Filter_Reference)

### Useful Tools
- Query Monitor plugin - Debug hooks and queries
- Debug Bar plugin - See all hooks on a page
- WordPress Code Reference - developer.wordpress.org

### Common Hooks to Know
- `init` - Initialize plugin
- `wp_enqueue_scripts` - Frontend scripts
- `admin_enqueue_scripts` - Admin scripts
- `save_post` - When post is saved
- `the_content` - Filter post content
- `wp_footer` - Add to footer
- `admin_menu` - Add admin pages

---
