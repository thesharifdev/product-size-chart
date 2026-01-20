<?php
/**
 * Plugin Name: Product Size Chart
 * Plugin URI: https://example.com
 * Description: Add size chart functionality to WooCommerce products with custom button and image upload
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * Text Domain: product-size-chart
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * Requires Plugins:  woocommerce
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WooCommerce_Size_Chart {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {

        // Initialize plugin
        add_action('plugins_loaded', array($this, 'init'));
    }
    
    public function init() {

        do_action('auto_cart_recovery_before_init');

        if (!class_exists('WooCommerce')) {
            return;
        }
        
        // Add custom tab to product data
        add_filter('woocommerce_product_data_tabs', array($this, 'add_size_chart_tab'));
        
        // Add fields to the size chart tab
        add_action('woocommerce_product_data_panels', array($this, 'add_size_chart_fields'));
        
        // Save the custom fields
        add_action('woocommerce_process_product_meta', array($this, 'save_size_chart_fields'));
        
        // Display size chart button on single product page
        add_action('woocommerce_after_add_to_cart_button', array($this, 'display_size_chart_button'));
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // AJAX handler for size chart modal
        add_action('wp_ajax_get_size_chart', array($this, 'ajax_get_size_chart'));
        add_action('wp_ajax_nopriv_get_size_chart', array($this, 'ajax_get_size_chart'));

        do_action('auto_cart_recovery_after_loaded');
    }
    
    public function add_size_chart_tab($tabs) {
        $tabs['size_chart'] = array(
            'label'    => __('Size Chart', 'product-size-chart'),
            'target'   => 'size_chart_options',
            'class'    => array('show_if_simple', 'show_if_variable'),
            'priority' => 65,
        );
        return $tabs;
    }
    
    public function add_size_chart_fields() {
        global $post;
        ?>
        <div id="size_chart_options" class="panel woocommerce_options_panel">
            <div class="options_group">
                <?php
                // Enable/Disable Size Chart
                woocommerce_wp_checkbox(array(
                    'id'          => '_enable_size_chart',
                    'label'       => __('Enable Size Chart', 'product-size-chart'),
                    'description' => __('Check this box to enable size chart for this product', 'product-size-chart'),
                    'desc_tip'    => true,
                ));
                
                // Button Text
                woocommerce_wp_text_input(array(
                    'id'          => '_size_chart_button_text',
                    'label'       => __('Size Chart Button Text', 'product-size-chart'),
                    'placeholder' => __('Size Chart', 'product-size-chart'),
                    'description' => __('Enter the text for the size chart button', 'product-size-chart'),
                    'desc_tip'    => true,
                    'value'       => get_post_meta($post->ID, '_size_chart_button_text', true) ?: 'Size Chart',
                ));
                ?>
            </div>
            
            <div class="options_group">
                <p class="form-field">
                    <label><?php _e('Size Chart Image', 'product-size-chart'); ?></label>
                    <span class="description"><?php _e('Upload an image for the size chart', 'product-size-chart'); ?></span>
                </p>
                
                <p class="form-field">
                    <input type="hidden" id="_size_chart_image_id" name="_size_chart_image_id" value="<?php echo esc_attr(get_post_meta($post->ID, '_size_chart_image_id', true)); ?>" />
                    <button type="button" class="button upload_size_chart_button"><?php _e('Upload Image', 'product-size-chart'); ?></button>
                    <button type="button" class="button remove_size_chart_button" style="<?php echo get_post_meta($post->ID, '_size_chart_image_id', true) ? '' : 'display:none;'; ?>"><?php _e('Remove Image', 'product-size-chart'); ?></button>
                </p>
                
                <p class="form-field">
                    <div id="size_chart_image_preview" style="margin-top: 10px;">
                        <?php
                        $image_id = get_post_meta($post->ID, '_size_chart_image_id', true);
                        if ($image_id) {
                            echo wp_get_attachment_image($image_id, 'thumbnail');
                        }
                        ?>
                    </div>
                </p>
            </div>
        </div>
        <?php
    }
    
    public function save_size_chart_fields($post_id) {
        // Enable/Disable
        $enable_size_chart = isset($_POST['_enable_size_chart']) ? 'yes' : 'no';
        update_post_meta($post_id, '_enable_size_chart', $enable_size_chart);
        
        // Button Text
        if (isset($_POST['_size_chart_button_text'])) {
            update_post_meta($post_id, '_size_chart_button_text', sanitize_text_field($_POST['_size_chart_button_text']));
        }
        
        // Image ID
        if (isset($_POST['_size_chart_image_id'])) {
            update_post_meta($post_id, '_size_chart_image_id', absint($_POST['_size_chart_image_id']));
        }
    }
    
    public function display_size_chart_button() {
        global $product;
        
        $enable_size_chart = get_post_meta($product->get_id(), '_enable_size_chart', true);
        
        if ($enable_size_chart === 'yes') {
            $button_text = get_post_meta($product->get_id(), '_size_chart_button_text', true);
            $button_text = $button_text ? $button_text : __('Size Chart', 'product-size-chart');
            
            $image_id = get_post_meta($product->get_id(), '_size_chart_image_id', true);
            
            if ($image_id) {
                ?>
                <button type="button" class="button size-chart-button" data-product-id="<?php echo esc_attr($product->get_id()); ?>">
                    <?php echo esc_html($button_text); ?>
                </button>
                <?php
            }
        }
    }
    
    public function enqueue_frontend_scripts() {
        if (is_product()) {
            wp_enqueue_style('woo-size-chart', plugin_dir_url(__FILE__) . 'assets/css/style.css', array(), '1.0.0');
            wp_enqueue_script('woo-size-chart', plugin_dir_url(__FILE__) . 'assets/js/size-chart.js', array('jquery'), '1.0.0', true);
            
            wp_localize_script('woo-size-chart', 'sizeChartData', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('size_chart_nonce'),
            ));
        }
    }
    
    public function enqueue_admin_scripts($hook) {
        if ('post.php' === $hook || 'post-new.php' === $hook) {
            global $post_type;
            if ('product' === $post_type) {
                wp_enqueue_media();
                wp_enqueue_script('woo-size-chart-admin', plugin_dir_url(__FILE__) . 'assets/js/main.js', array('jquery'), '1.0.0', true);
            }
        }
    }
    
    public function ajax_get_size_chart() {
        check_ajax_referer('size_chart_nonce', 'nonce');
        
        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        
        if (!$product_id) {
            wp_send_json_error(array('message' => __('Invalid product ID', 'product-size-chart')));
        }
        
        $image_id = get_post_meta($product_id, '_size_chart_image_id', true);
        
        if ($image_id) {
            $image_url = wp_get_attachment_image_url($image_id, 'full');
            wp_send_json_success(array('image_url' => $image_url));
        } else {
            wp_send_json_error(array('message' => __('No size chart image found', 'product-size-chart')));
        }
    }
}

// Initialize the plugin
WooCommerce_Size_Chart::get_instance();
