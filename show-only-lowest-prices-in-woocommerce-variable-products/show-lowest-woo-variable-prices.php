<?php
/**
 * Plugin Name: WooCommerce - Show only lowest prices in variable products
 * Plugin URI: https://servicios.ayudawp.com
 * Description: Shows only the lowest price and sale in variable WooCommerce products with customizable prefix and advanced options.
 * Author: Fernando Tellado
 * Version: 2.0
 * Author URI: https://ayudawp.com
 * Text Domain: show-only-lowest-prices-in-woocommerce-variable-products
 * Domain Path: /languages
 * Requires Plugins: woocommerce
 * Requires at least: 5.0
 * Tested up to: 6.8
 * Requires PHP: 7.4
 * WC requires at least: 4.0
 * WC tested up to: 10.0.4
 * License: GPLv2+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'AYUDAWP_LOWEST_PRICES_VERSION', '2.0' );
define( 'AYUDAWP_LOWEST_PRICES_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'AYUDAWP_LOWEST_PRICES_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'AYUDAWP_LOWEST_PRICES_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main plugin class
 */
class AyudaWP_Lowest_Prices {

    /**
     * Instance of this class
     */
    private static $instance = null;

    /**
     * Plugin options
     */
    private $options;

    /**
     * Get instance
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action( 'plugins_loaded', array( $this, 'ayudawp_init' ) );
        register_activation_hook( __FILE__, array( $this, 'ayudawp_activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'ayudawp_deactivate' ) );
    }

    /**
     * Initialize plugin
     */
    public function ayudawp_init() {
        // Check if WooCommerce is active
        if ( ! class_exists( 'WooCommerce' ) ) {
            add_action( 'admin_notices', array( $this, 'ayudawp_woocommerce_missing_notice' ) );
            return;
        }

        // Text domain is automatically loaded by WordPress.org for hosted plugins

        // Declare HPOS compatibility
        add_action( 'before_woocommerce_init', array( $this, 'ayudawp_declare_hpos_compatibility' ) );

        // Load options
        $this->options = get_option( 'ayudawp_lowest_prices_options', $this->ayudawp_get_default_options() );

        // Initialize admin if in admin area
        if ( is_admin() ) {
            add_action( 'admin_menu', array( $this, 'ayudawp_add_admin_menu' ) );
            add_action( 'admin_init', array( $this, 'ayudawp_admin_init' ) );
            add_filter( 'plugin_action_links_' . AYUDAWP_LOWEST_PRICES_PLUGIN_BASENAME, array( $this, 'ayudawp_plugin_action_links' ) );
        }

        // Hook into WooCommerce price filters
        add_filter( 'woocommerce_variable_sale_price_html', array( $this, 'ayudawp_custom_variable_price_range' ), 10, 2 );
        add_filter( 'woocommerce_variable_price_html', array( $this, 'ayudawp_custom_variable_price_range' ), 10, 2 );

        // Add custom CSS if needed
        add_action( 'wp_head', array( $this, 'ayudawp_add_custom_css' ) );
    }

    /**
     * Get default options
     */
    private function ayudawp_get_default_options() {
        return array(
            'prefix_text' => __( 'From', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
            'show_prefix_same_price' => false,
            'add_space_after_prefix' => true,
            'custom_css_class' => 'ayudawp-lowest-price',
            'hide_prefix_css' => false
        );
    }

    /**
     * Plugin activation
     */
    public function ayudawp_activate() {
        // Set default options on activation
        add_option( 'ayudawp_lowest_prices_options', $this->ayudawp_get_default_options() );
        
        // Set a transient to show activation notice
        set_transient( 'ayudawp_lowest_prices_activation_notice', true, 30 );
    }

    /**
     * Plugin deactivation
     */
    public function ayudawp_deactivate() {
        // Clean up transients
        delete_transient( 'ayudawp_lowest_prices_activation_notice' );
    }

    /**
     * Declare HPOS compatibility
     */
    public function ayudawp_declare_hpos_compatibility() {
        if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
        }
    }

    /**
     * WooCommerce missing notice
     */
    public function ayudawp_woocommerce_missing_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php esc_html_e( 'WooCommerce - Show only lowest prices requires WooCommerce to be installed and active.', 'show-only-lowest-prices-in-woocommerce-variable-products' ); ?></p>
        </div>
        <?php
    }

    /**
     * Custom variable price range function
     */
    public function ayudawp_custom_variable_price_range( $price_html, $product ) {
        $min_price = $product->get_variation_price( 'min', true );
        $max_price = $product->get_variation_price( 'max', true );
        $suffix = $product->get_price_suffix();

        // Get custom prefix text
        $prefix = ! empty( $this->options['prefix_text'] ) ? $this->options['prefix_text'] : __( 'From', 'show-only-lowest-prices-in-woocommerce-variable-products' );
        
        // Add space after prefix if enabled
        $space = $this->options['add_space_after_prefix'] ? ' ' : '';
        
        // Get custom CSS class
        $css_class = ! empty( $this->options['custom_css_class'] ) ? $this->options['custom_css_class'] : 'ayudawp-lowest-price';

        // If all variations have same price, decide whether to show prefix or not
        if ( $min_price === $max_price ) {
            if ( $this->options['show_prefix_same_price'] ) {
                return '<span class="' . esc_attr( $css_class ) . '"><span class="ayudawp-prefix">' . esc_html( $prefix ) . '</span>' . $space . wc_price( $min_price ) . $suffix . '</span>';
            } else {
                return '<span class="' . esc_attr( $css_class ) . '">' . wc_price( $min_price ) . $suffix . '</span>';
            }
        }

        // Different prices, always show prefix
        return '<span class="' . esc_attr( $css_class ) . '"><span class="ayudawp-prefix">' . esc_html( $prefix ) . '</span>' . $space . wc_price( $min_price ) . $suffix . '</span>';
    }

    /**
     * Add custom CSS to hide prefix if option is enabled
     */
    public function ayudawp_add_custom_css() {
        if ( $this->options['hide_prefix_css'] ) {
            echo '<style type="text/css">.ayudawp-prefix { display: none !important; }</style>' . "\n";
        }
    }

    /**
     * Add admin menu
     */
    public function ayudawp_add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            __( 'Lowest Prices Settings', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
            __( 'Lowest Prices', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
            'manage_woocommerce',
            'ayudawp-lowest-prices',
            array( $this, 'ayudawp_admin_page' )
        );
    }

    /**
     * Initialize admin settings
     */
    public function ayudawp_admin_init() {
        register_setting( 'ayudawp_lowest_prices_group', 'ayudawp_lowest_prices_options', array( $this, 'ayudawp_sanitize_options' ) );

        add_settings_section(
            'ayudawp_lowest_prices_section',
            __( 'General Settings', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
            array( $this, 'ayudawp_section_callback' ),
            'ayudawp_lowest_prices_page'
        );

        // Prefix text field
        add_settings_field(
            'prefix_text',
            __( 'Prefix Text', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
            array( $this, 'ayudawp_prefix_text_callback' ),
            'ayudawp_lowest_prices_page',
            'ayudawp_lowest_prices_section'
        );

        // Show prefix when same price checkbox
        add_settings_field(
            'show_prefix_same_price',
            __( 'Show prefix when all prices are the same', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
            array( $this, 'ayudawp_show_prefix_same_price_callback' ),
            'ayudawp_lowest_prices_page',
            'ayudawp_lowest_prices_section'
        );

        // Add space after prefix checkbox
        add_settings_field(
            'add_space_after_prefix',
            __( 'Add space after prefix', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
            array( $this, 'ayudawp_add_space_after_prefix_callback' ),
            'ayudawp_lowest_prices_page',
            'ayudawp_lowest_prices_section'
        );

        // Custom CSS class field
        add_settings_field(
            'custom_css_class',
            __( 'Custom CSS Class', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
            array( $this, 'ayudawp_custom_css_class_callback' ),
            'ayudawp_lowest_prices_page',
            'ayudawp_lowest_prices_section'
        );

        // Hide prefix with CSS checkbox
        add_settings_field(
            'hide_prefix_css',
            __( 'Hide prefix with CSS', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
            array( $this, 'ayudawp_hide_prefix_css_callback' ),
            'ayudawp_lowest_prices_page',
            'ayudawp_lowest_prices_section'
        );
    }

    /**
     * Sanitize options
     */
    public function ayudawp_sanitize_options( $input ) {
        $sanitized = array();
        
        $sanitized['prefix_text'] = sanitize_text_field( $input['prefix_text'] );
        $sanitized['show_prefix_same_price'] = isset( $input['show_prefix_same_price'] ) ? true : false;
        $sanitized['add_space_after_prefix'] = isset( $input['add_space_after_prefix'] ) ? true : false;
        $sanitized['custom_css_class'] = sanitize_html_class( $input['custom_css_class'] );
        $sanitized['hide_prefix_css'] = isset( $input['hide_prefix_css'] ) ? true : false;

        return $sanitized;
    }

    /**
     * Section callback
     */
    public function ayudawp_section_callback() {
        echo '<p>' . esc_html__( 'Configure how the lowest prices are displayed in your WooCommerce variable products.', 'show-only-lowest-prices-in-woocommerce-variable-products' ) . '</p>';
    }

    /**
     * Prefix text callback
     */
    public function ayudawp_prefix_text_callback() {
        $value = isset( $this->options['prefix_text'] ) ? $this->options['prefix_text'] : 'From';
        echo '<input type="text" name="ayudawp_lowest_prices_options[prefix_text]" value="' . esc_attr( $value ) . '" class="regular-text" />';
        echo '<p class="description">' . esc_html__( 'Text to show before the lowest price. Leave empty to show no prefix.', 'show-only-lowest-prices-in-woocommerce-variable-products' ) . '</p>';
    }

    /**
     * Show prefix same price callback
     */
    public function ayudawp_show_prefix_same_price_callback() {
        $checked = isset( $this->options['show_prefix_same_price'] ) && $this->options['show_prefix_same_price'];
        echo '<input type="checkbox" name="ayudawp_lowest_prices_options[show_prefix_same_price]" value="1" ' . checked( 1, $checked, false ) . ' />';
        echo '<p class="description">' . esc_html__( 'Show the prefix even when all variations have the same price.', 'show-only-lowest-prices-in-woocommerce-variable-products' ) . '</p>';
    }

    /**
     * Add space after prefix callback
     */
    public function ayudawp_add_space_after_prefix_callback() {
        $checked = isset( $this->options['add_space_after_prefix'] ) && $this->options['add_space_after_prefix'];
        echo '<input type="checkbox" name="ayudawp_lowest_prices_options[add_space_after_prefix]" value="1" ' . checked( 1, $checked, false ) . ' />';
        echo '<p class="description">' . esc_html__( 'Add a space between the prefix and the price.', 'show-only-lowest-prices-in-woocommerce-variable-products' ) . '</p>';
    }

    /**
     * Custom CSS class callback
     */
    public function ayudawp_custom_css_class_callback() {
        $value = isset( $this->options['custom_css_class'] ) ? $this->options['custom_css_class'] : 'ayudawp-lowest-price';
        echo '<input type="text" name="ayudawp_lowest_prices_options[custom_css_class]" value="' . esc_attr( $value ) . '" class="regular-text" />';
        echo '<p class="description">' . esc_html__( 'CSS class to add to the price wrapper for custom styling.', 'show-only-lowest-prices-in-woocommerce-variable-products' ) . '</p>';
    }

    /**
     * Hide prefix CSS callback
     */
    public function ayudawp_hide_prefix_css_callback() {
        $checked = isset( $this->options['hide_prefix_css'] ) && $this->options['hide_prefix_css'];
        echo '<input type="checkbox" name="ayudawp_lowest_prices_options[hide_prefix_css]" value="1" ' . checked( 1, $checked, false ) . ' />';
        echo '<p class="description">' . esc_html__( 'Hide the prefix using CSS (useful for maintaining structure while hiding visually).', 'show-only-lowest-prices-in-woocommerce-variable-products' ) . '</p>';
    }

    /**
     * Admin page
     */
    public function ayudawp_admin_page() {
        ?>
        <div class="wrap ayudawp-admin-page">
            <div class="ayudawp-header">
                <div class="ayudawp-header-content">
                    <div class="ayudawp-title-section">
                        <h1><?php esc_html_e( 'Show only lowest prices in variable products', 'show-only-lowest-prices-in-woocommerce-variable-products' ); ?></h1>
                        <p class="ayudawp-subtitle"><?php esc_html_e( 'Clean up your WooCommerce variable product prices and boost your sales', 'show-only-lowest-prices-in-woocommerce-variable-products' ); ?></p>
                    </div>
                </div>
            </div>

            <div class="ayudawp-content">
                <div class="ayudawp-main">
                    <form method="post" action="options.php">
                        <?php
                        settings_fields( 'ayudawp_lowest_prices_group' );
                        do_settings_sections( 'ayudawp_lowest_prices_page' );
                        submit_button( __( 'Save Settings', 'show-only-lowest-prices-in-woocommerce-variable-products' ), 'primary', 'submit', true, array( 'class' => 'ayudawp-button-primary' ) );
                        ?>
                    </form>
                </div>

                <div class="ayudawp-sidebar">
                    <div class="ayudawp-sidebar-box">
                        <h3><?php esc_html_e( 'Need help?', 'show-only-lowest-prices-in-woocommerce-variable-products' ); ?></h3>
                        <p><?php esc_html_e( 'Visit our website for more WordPress and WooCommerce solutions.', 'show-only-lowest-prices-in-woocommerce-variable-products' ); ?></p>
                        <a href="https://servicios.ayudawp.com" target="_blank" class="ayudawp-button-secondary"><?php esc_html_e( 'Visit AyudaWP', 'show-only-lowest-prices-in-woocommerce-variable-products' ); ?></a>
                    </div>

                    <div class="ayudawp-sidebar-box">
                        <h3><?php esc_html_e( 'Plugin Info', 'show-only-lowest-prices-in-woocommerce-variable-products' ); ?></h3>
                        <p><strong><?php esc_html_e( 'Version:', 'show-only-lowest-prices-in-woocommerce-variable-products' ); ?></strong> <?php echo esc_html( AYUDAWP_LOWEST_PRICES_VERSION ); ?></p>
                        <p><strong><?php esc_html_e( 'WordPress:', 'show-only-lowest-prices-in-woocommerce-variable-products' ); ?></strong> <?php echo esc_html( get_bloginfo( 'version' ) ); ?></p>
                        <p><strong><?php esc_html_e( 'WooCommerce:', 'show-only-lowest-prices-in-woocommerce-variable-products' ); ?></strong> <?php echo esc_html( WC()->version ); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <style>
        .ayudawp-admin-page {
            background: #f1f1f1;
            margin: 20px 0 0 -20px;
            padding: 0;
        }
        .ayudawp-header {
            background: #fff;
            color: #333;
            padding: 30px;
            margin: 0 0 30px 0;
            border-bottom: 1px solid #ddd;
        }
        .ayudawp-header-content {
            max-width: 1200px;
            margin: 0 auto;
        }
        .ayudawp-title-section h1 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 2.2em;
            font-weight: 400;
        }
        .ayudawp-subtitle {
            margin: 0;
            color: #666;
            font-size: 1.1em;
        }
        .ayudawp-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 30px;
            display: flex;
            gap: 30px;
        }
        .ayudawp-main {
            flex: 2;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .ayudawp-sidebar {
            flex: 1;
        }
        .ayudawp-sidebar-box {
            background: white;
            padding: 25px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .ayudawp-sidebar-box h3 {
            margin-top: 0;
            color: #333;
            font-size: 1.2em;
        }
        .ayudawp-button-primary {
            background: #0073aa !important;
            border-color: #0073aa !important;
            text-shadow: none !important;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2) !important;
        }
        .ayudawp-button-secondary {
            display: inline-block;
            background: #f8f9fa;
            color: #0073aa;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            border: 2px solid #0073aa;
            transition: all 0.3s ease;
        }
        .ayudawp-button-secondary:hover {
            background: #0073aa;
            color: white;
        }
        .form-table th {
            color: #333;
            font-weight: 600;
        }
        </style>
        <?php
    }

    /**
     * Add plugin action links
     */
    public function ayudawp_plugin_action_links( $links ) {
        $settings_link = '<a href="' . admin_url( 'admin.php?page=ayudawp-lowest-prices' ) . '">' . __( 'Settings', 'show-only-lowest-prices-in-woocommerce-variable-products' ) . '</a>';
        array_unshift( $links, $settings_link );
        return $links;
    }
}

// Initialize the plugin
AyudaWP_Lowest_Prices::get_instance();