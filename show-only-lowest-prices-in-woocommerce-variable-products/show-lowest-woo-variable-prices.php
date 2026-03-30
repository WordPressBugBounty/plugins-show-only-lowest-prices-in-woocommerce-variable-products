<?php
/**
 * Plugin Name: Show only lowest prices in variable products for WooCommerce
 * Plugin URI: https://servicios.ayudawp.com
 * Description: Shows only the lowest price and sale in variable WooCommerce products with customizable prefix and advanced options.
 * Author: Fernando Tellado
 * Version: 2.1.0
 * Author URI: https://ayudawp.com
 * Text Domain: show-only-lowest-prices-in-woocommerce-variable-products
 * Requires Plugins: woocommerce
 * Requires at least: 5.0
 * Tested up to: 7.0
 * Requires PHP: 7.4
 * WC requires at least: 4.0
 * WC tested up to: 10.6
 * License: GPLv2+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'AYUDAWP_LOWEST_PRICES_VERSION', '2.1.0' );
define( 'AYUDAWP_LOWEST_PRICES_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'AYUDAWP_LOWEST_PRICES_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'AYUDAWP_LOWEST_PRICES_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main plugin class.
 */
class AyudaWP_Lowest_Prices {

	/**
	 * Singleton instance.
	 *
	 * @var AyudaWP_Lowest_Prices|null
	 */
	private static $instance = null;

	/**
	 * Plugin options (lazy loaded).
	 *
	 * @var array|null
	 */
	private $options = null;

	/**
	 * Get singleton instance.
	 *
	 * @return AyudaWP_Lowest_Prices
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		// HPOS compatibility must be declared early.
		add_action( 'before_woocommerce_init', array( $this, 'ayudawp_declare_hpos_compatibility' ) );

		add_action( 'init', array( $this, 'ayudawp_init' ) );
		register_activation_hook( __FILE__, array( $this, 'ayudawp_activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'ayudawp_deactivate' ) );
	}

	/**
	 * Initialize plugin on 'init' hook when translations are available.
	 */
	public function ayudawp_init() {
		// WooCommerce dependency check.
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', array( $this, 'ayudawp_woocommerce_missing_notice' ) );
			return;
		}

		// Migrate prefix text for users updating from 2.0.x.
		$this->ayudawp_maybe_migrate_prefix();

		// Admin hooks.
		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'ayudawp_add_admin_menu' ) );
			add_action( 'admin_init', array( $this, 'ayudawp_admin_init' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'ayudawp_enqueue_admin_assets' ) );
			add_action( 'admin_notices', array( $this, 'ayudawp_activation_notice' ) );
			add_filter( 'plugin_action_links_' . AYUDAWP_LOWEST_PRICES_PLUGIN_BASENAME, array( $this, 'ayudawp_plugin_action_links' ) );
		}

		// WooCommerce price filters.
		add_filter( 'woocommerce_variable_sale_price_html', array( $this, 'ayudawp_custom_variable_price_range' ), 10, 2 );
		add_filter( 'woocommerce_variable_price_html', array( $this, 'ayudawp_custom_variable_price_range' ), 10, 2 );
	}

	/**
	 * Migrate settings from 2.0.x to 2.1.0.
	 *
	 * - Translates stored 'From' prefix if a translation exists.
	 * - Removes the deprecated 'hide_prefix_css' option.
	 */
	private function ayudawp_maybe_migrate_prefix() {
		if ( get_transient( 'ayudawp_lowest_prices_migrate_prefix' ) ) {
			return; // Already migrated.
		}

		$options  = get_option( 'ayudawp_lowest_prices_options', array() );
		$modified = false;

		// Translate the default prefix if it was stored as English 'From'.
		if ( isset( $options['prefix_text'] ) && 'From' === $options['prefix_text'] ) {
			$translated = __( 'From', 'show-only-lowest-prices-in-woocommerce-variable-products' );

			if ( 'From' !== $translated ) {
				$options['prefix_text'] = $translated;
				$modified               = true;
			}
		}

		// Remove legacy setting no longer used in 2.1.0.
		if ( isset( $options['hide_prefix_css'] ) ) {
			unset( $options['hide_prefix_css'] );
			$modified = true;
		}

		if ( $modified ) {
			update_option( 'ayudawp_lowest_prices_options', $options );
		}

		// Prevent running on every page load.
		set_transient( 'ayudawp_lowest_prices_migrate_prefix', true, DAY_IN_SECONDS );
	}

	/**
	 * Get options with lazy loading and translated defaults.
	 *
	 * @return array
	 */
	private function ayudawp_get_options() {
		if ( null === $this->options ) {
			$saved_options = get_option( 'ayudawp_lowest_prices_options', array() );

			$default_options = array(
				'prefix_text'            => __( 'From', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'show_prefix_same_price' => false,
				'add_space_after_prefix' => true,
				'custom_css_class'       => 'ayudawp-lowest-price',
			);

			$this->options = wp_parse_args( $saved_options, $default_options );
		}
		return $this->options;
	}

	/**
	 * Plugin activation — set default options.
	 */
	public function ayudawp_activate() {
		if ( false === get_option( 'ayudawp_lowest_prices_options', false ) ) {
			// Translations may not be loaded during activation, so we store
			// the English default. The migration routine will update it on
			// first init if a translation exists for the active locale.
			add_option( 'ayudawp_lowest_prices_options', array(
				'prefix_text'            => 'From',
				'show_prefix_same_price' => false,
				'add_space_after_prefix' => true,
				'custom_css_class'       => 'ayudawp-lowest-price',
			) );
		}

		set_transient( 'ayudawp_lowest_prices_activation_notice', true );
	}

	/**
	 * Plugin deactivation — clean up transients.
	 */
	public function ayudawp_deactivate() {
		delete_transient( 'ayudawp_lowest_prices_activation_notice' );
		delete_transient( 'ayudawp_lowest_prices_migrate_prefix' );
	}

	/**
	 * Declare WooCommerce HPOS compatibility.
	 */
	public function ayudawp_declare_hpos_compatibility() {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}

	/**
	 * Admin notice when WooCommerce is not active.
	 */
	public function ayudawp_woocommerce_missing_notice() {
		?>
		<div class="notice notice-error">
			<p><?php esc_html_e( 'Show only lowest prices in variable products requires WooCommerce to be installed and active.', 'show-only-lowest-prices-in-woocommerce-variable-products' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Modify the variable product price range display.
	 *
	 * @param string     $price_html Default price HTML.
	 * @param WC_Product $product    Product object.
	 * @return string Modified price HTML.
	 */
	public function ayudawp_custom_variable_price_range( $price_html, $product ) {
		$min_price = $product->get_variation_price( 'min', true );
		$max_price = $product->get_variation_price( 'max', true );
		$suffix    = $product->get_price_suffix();

		$options = $this->ayudawp_get_options();

		// Prefix text: use stored value, empty string means no prefix.
		$prefix = $options['prefix_text'];

		// Space between prefix and price.
		$space = $options['add_space_after_prefix'] ? ' ' : '';

		// CSS class for the price wrapper.
		$css_class = ! empty( $options['custom_css_class'] ) ? $options['custom_css_class'] : 'ayudawp-lowest-price';

		// All variations have the same price.
		if ( $min_price === $max_price ) {
			if ( $options['show_prefix_same_price'] && '' !== $prefix ) {
				return '<span class="' . esc_attr( $css_class ) . '"><span class="ayudawp-prefix">' . esc_html( $prefix ) . '</span>' . $space . wc_price( $min_price ) . $suffix . '</span>';
			}
			return '<span class="' . esc_attr( $css_class ) . '">' . wc_price( $min_price ) . $suffix . '</span>';
		}

		// Different prices — show prefix if not empty.
		if ( '' !== $prefix ) {
			return '<span class="' . esc_attr( $css_class ) . '"><span class="ayudawp-prefix">' . esc_html( $prefix ) . '</span>' . $space . wc_price( $min_price ) . $suffix . '</span>';
		}

		return '<span class="' . esc_attr( $css_class ) . '">' . wc_price( $min_price ) . $suffix . '</span>';
	}

	/**
	 * Show a one-time dismissible notice after plugin activation.
	 *
	 * The notice is displayed once and the transient is deleted immediately,
	 * so it will not appear again on subsequent page loads.
	 */
	public function ayudawp_activation_notice() {
		if ( ! get_transient( 'ayudawp_lowest_prices_activation_notice' ) ) {
			return;
		}

		// Delete immediately so it only shows once.
		delete_transient( 'ayudawp_lowest_prices_activation_notice' );

		$settings_url = admin_url( 'admin.php?page=ayudawp-lowest-prices' );
		?>
		<div class="notice notice-success is-dismissible">
			<p>
				<?php
				printf(
					/* translators: %1$s: opening link tag, %2$s: closing link tag */
					esc_html__( 'Show only lowest prices is active! You can customize the prefix text, display options and more in the %1$ssettings page%2$s.', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
					'<a href="' . esc_url( $settings_url ) . '">',
					'</a>'
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Admin page hook suffix for asset enqueue.
	 *
	 * @var string
	 */
	private $admin_page_hook = '';

	/**
	 * Register admin submenu under WooCommerce Marketing.
	 */
	public function ayudawp_add_admin_menu() {
		$this->admin_page_hook = add_submenu_page(
			'woocommerce-marketing',
			__( 'Lowest Prices Settings', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
			__( 'Lowest Prices', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
			'manage_woocommerce',
			'ayudawp-lowest-prices',
			array( $this, 'ayudawp_admin_page' )
		);
	}

	/**
	 * Register settings, sections and fields.
	 */
	public function ayudawp_admin_init() {
		register_setting(
			'ayudawp_lowest_prices_group',
			'ayudawp_lowest_prices_options',
			array( $this, 'ayudawp_sanitize_options' )
		);

		add_settings_section(
			'ayudawp_lowest_prices_section',
			__( 'General Settings', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
			array( $this, 'ayudawp_section_callback' ),
			'ayudawp_lowest_prices_page'
		);

		// Prefix text.
		add_settings_field(
			'prefix_text',
			__( 'Prefix Text', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
			array( $this, 'ayudawp_prefix_text_callback' ),
			'ayudawp_lowest_prices_page',
			'ayudawp_lowest_prices_section'
		);

		// Show prefix when all prices match.
		add_settings_field(
			'show_prefix_same_price',
			__( 'Show prefix when all prices are the same', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
			array( $this, 'ayudawp_show_prefix_same_price_callback' ),
			'ayudawp_lowest_prices_page',
			'ayudawp_lowest_prices_section'
		);

		// Space after prefix.
		add_settings_field(
			'add_space_after_prefix',
			__( 'Add space after prefix', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
			array( $this, 'ayudawp_add_space_after_prefix_callback' ),
			'ayudawp_lowest_prices_page',
			'ayudawp_lowest_prices_section'
		);

		// Custom CSS class.
		add_settings_field(
			'custom_css_class',
			__( 'Custom CSS Class', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
			array( $this, 'ayudawp_custom_css_class_callback' ),
			'ayudawp_lowest_prices_page',
			'ayudawp_lowest_prices_section'
		);
	}

	/**
	 * Sanitize options before saving.
	 *
	 * @param array $input Raw input from the settings form.
	 * @return array Sanitized options.
	 */
	public function ayudawp_sanitize_options( $input ) {
		$sanitized = array();

		// Allow empty prefix (user wants no prefix text).
		$sanitized['prefix_text']            = isset( $input['prefix_text'] ) ? sanitize_text_field( $input['prefix_text'] ) : '';
		$sanitized['custom_css_class']       = isset( $input['custom_css_class'] ) ? sanitize_html_class( $input['custom_css_class'] ) : '';
		$sanitized['show_prefix_same_price'] = isset( $input['show_prefix_same_price'] );
		$sanitized['add_space_after_prefix'] = isset( $input['add_space_after_prefix'] );

		return $sanitized;
	}

	/**
	 * Section description callback.
	 */
	public function ayudawp_section_callback() {
		echo '<p>' . esc_html__( 'Configure how the lowest prices are displayed in your WooCommerce variable products.', 'show-only-lowest-prices-in-woocommerce-variable-products' ) . '</p>';
	}

	/**
	 * Prefix text field callback.
	 */
	public function ayudawp_prefix_text_callback() {
		$options = $this->ayudawp_get_options();
		$value   = isset( $options['prefix_text'] ) ? $options['prefix_text'] : __( 'From', 'show-only-lowest-prices-in-woocommerce-variable-products' );

		echo '<input type="text" name="ayudawp_lowest_prices_options[prefix_text]" value="' . esc_attr( $value ) . '" placeholder="' . esc_attr__( 'From', 'show-only-lowest-prices-in-woocommerce-variable-products' ) . '" class="regular-text" />';
		echo '<p class="description">' . esc_html__( 'Text to show before the lowest price. Leave empty to show no prefix.', 'show-only-lowest-prices-in-woocommerce-variable-products' ) . '</p>';
	}

	/**
	 * Show prefix with same price checkbox callback.
	 */
	public function ayudawp_show_prefix_same_price_callback() {
		$options = $this->ayudawp_get_options();
		$checked = ! empty( $options['show_prefix_same_price'] );

		echo '<input type="checkbox" name="ayudawp_lowest_prices_options[show_prefix_same_price]" value="1" ' . checked( 1, $checked, false ) . ' />';
		echo '<p class="description">' . esc_html__( 'Show the prefix even when all variations have the same price.', 'show-only-lowest-prices-in-woocommerce-variable-products' ) . '</p>';
	}

	/**
	 * Space after prefix checkbox callback.
	 */
	public function ayudawp_add_space_after_prefix_callback() {
		$options = $this->ayudawp_get_options();
		$checked = ! empty( $options['add_space_after_prefix'] );

		echo '<input type="checkbox" name="ayudawp_lowest_prices_options[add_space_after_prefix]" value="1" ' . checked( 1, $checked, false ) . ' />';
		echo '<p class="description">' . esc_html__( 'Add a space between the prefix and the price.', 'show-only-lowest-prices-in-woocommerce-variable-products' ) . '</p>';
	}

	/**
	 * Custom CSS class field callback.
	 */
	public function ayudawp_custom_css_class_callback() {
		$options = $this->ayudawp_get_options();
		$value   = isset( $options['custom_css_class'] ) ? $options['custom_css_class'] : 'ayudawp-lowest-price';

		echo '<input type="text" name="ayudawp_lowest_prices_options[custom_css_class]" value="' . esc_attr( $value ) . '" placeholder="ayudawp-lowest-price" class="regular-text" />';
		echo '<p class="description">' . esc_html__( 'CSS class applied to the price wrapper. We recommend keeping the default class and only changing it if the price text does not display correctly with your theme.', 'show-only-lowest-prices-in-woocommerce-variable-products' ) . '</p>';
	}

	/**
	 * Enqueue admin styles and Thickbox on the plugin settings page.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 */
	public function ayudawp_enqueue_admin_assets( $hook_suffix ) {
		if ( $hook_suffix !== $this->admin_page_hook ) {
			return;
		}

		wp_enqueue_style(
			'ayudawp-lowest-prices-admin',
			AYUDAWP_LOWEST_PRICES_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			AYUDAWP_LOWEST_PRICES_VERSION
		);

		// Thickbox for plugin install modals in promo banner.
		add_thickbox();
	}

	/**
	 * Render the admin settings page.
	 */
	public function ayudawp_admin_page() {
		// Load promo banner class.
		if ( ! class_exists( 'AyudaWP_Lowest_Prices_Promo_Banner' ) ) {
			require_once AYUDAWP_LOWEST_PRICES_PLUGIN_PATH . 'includes/class-ayudawp-lowest-prices-promo-banner.php';
		}

		$promo_banner = new AyudaWP_Lowest_Prices_Promo_Banner(
			'show-only-lowest-prices-in-woocommerce-variable-products',
			'ayudawp-lp'
		);
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Show only lowest prices in variable products', 'show-only-lowest-prices-in-woocommerce-variable-products' ); ?></h1>
			<p><?php esc_html_e( 'Clean up your WooCommerce variable product prices and boost your sales.', 'show-only-lowest-prices-in-woocommerce-variable-products' ); ?></p>

			<div class="ayudawp-content">
				<div class="ayudawp-main">
					<form method="post" action="options.php">
						<?php
						settings_fields( 'ayudawp_lowest_prices_group' );
						do_settings_sections( 'ayudawp_lowest_prices_page' );
						submit_button( __( 'Save Settings', 'show-only-lowest-prices-in-woocommerce-variable-products' ) );
						?>
					</form>
				</div>

				<div class="ayudawp-sidebar">
					<?php $promo_banner->render(); ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Add Settings link to plugins list.
	 *
	 * @param array $links Existing plugin action links.
	 * @return array Modified links.
	 */
	public function ayudawp_plugin_action_links( $links ) {
		$settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=ayudawp-lowest-prices' ) ) . '">' . esc_html__( 'Settings', 'show-only-lowest-prices-in-woocommerce-variable-products' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}
}

// Boot.
AyudaWP_Lowest_Prices::get_instance();