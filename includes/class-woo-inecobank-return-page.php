<?php
/**
 * Inecobank Payment Return Page Manager
 *
 * Creates and manages the custom return page for Inecobank payments
 *
 * @package WooCommerce Inecobank Payment Gateway
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Woo_Inecobank_Return_Page
{

    /**
     * Page slug
     */
    const PAGE_SLUG = 'inecobank-payment-return';

    /**
     * Page ID option name
     */
    const PAGE_ID_OPTION = 'woo_inecobank_return_page_id';

    /**
     * Initialize
     */
    public static function init()
    {
        add_action('init', array(__CLASS__, 'register_page_template'));
        add_filter('template_include', array(__CLASS__, 'load_page_template'));
    }

    /**
     * Create the return page
     *
     * @return int|WP_Error Page ID or error
     */
    public static function create_page()
    {
        // Check if page already exists
        $existing_page_id = get_option(self::PAGE_ID_OPTION);

        if ($existing_page_id && get_post($existing_page_id)) {
            return $existing_page_id;
        }

        // Create new page
        $page_data = array(
            'post_title' => __('Payment Processing', 'woo-inecobank-payment-gateway'),
            'post_name' => self::PAGE_SLUG,
            'post_content' => '[inecobank_return_page]',
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_author' => 1,
            'comment_status' => 'closed',
            'ping_status' => 'closed',
        );

        $page_id = wp_insert_post($page_data);

        if (!is_wp_error($page_id)) {
            update_option(self::PAGE_ID_OPTION, $page_id);

            // Set page template
            update_post_meta($page_id, '_wp_page_template', 'default');

            return $page_id;
        }

        return $page_id;
    }

    /**
     * Get the return page URL
     *
     * @return string
     */
    public static function get_page_url()
    {
        $page_id = get_option(self::PAGE_ID_OPTION);

        if (!$page_id) {
            // Try to create it
            $page_id = self::create_page();
        }

        if ($page_id && !is_wp_error($page_id)) {
            return get_permalink($page_id);
        }

        // Fallback to WooCommerce API
        return WC()->api_request_url('woo_inecobank_gateway');
    }

    /**
     * Register custom page template
     */
    public static function register_page_template()
    {
        // Template will be loaded via template_include filter
    }

    /**
     * Load custom template for return page
     *
     * @param string $template Template path.
     * @return string
     */
    public static function load_page_template($template)
    {
        global $post;

        // Check if this is our return page
        if (is_page() && isset($post->ID)) {
            $page_id = get_option(self::PAGE_ID_OPTION);

            if ($post->ID == $page_id) {
                // Check for template in theme first
                $theme_template = locate_template(array(
                    'woocommerce/page-inecobank-return.php',
                    'page-inecobank-return.php',
                ));

                if ($theme_template) {
                    return $theme_template;
                }

                // Use plugin template
                $plugin_template = WOO_INECOBANK_PLUGIN_DIR . 'templates/page-inecobank-return.php';

                if (file_exists($plugin_template)) {
                    return $plugin_template;
                }
            }
        }

        return $template;
    }

    /**
     * Delete the return page
     */
    public static function delete_page()
    {
        $page_id = get_option(self::PAGE_ID_OPTION);

        if ($page_id) {
            wp_delete_post($page_id, true);
            delete_option(self::PAGE_ID_OPTION);
        }
    }

    /**
     * Shortcode for return page content
     * 
     * @return string
     */
    public static function return_page_shortcode()
    {
        ob_start();
        include WOO_INECOBANK_PLUGIN_DIR . 'templates/page-inecobank-return.php';
        return ob_get_clean();
    }
}

// Initialize
Woo_Inecobank_Return_Page::init();

// Register shortcode
add_shortcode('inecobank_return_page', array('Woo_Inecobank_Return_Page', 'return_page_shortcode'));
