<?php
/**
 * Plugin Name: Show only lowest prices in variable products for WooCommerce
 * Plugin URI: https://servicios.ayudawp.com
 * Description: Shows only the lowest price and sale in variable WooCommerce products with customizable prefix and advanced options.
 * Author: Fernando Tellado
 * Version: 2.3.0
 * Author URI: https://ayudawp.com
 * Text Domain: show-only-lowest-prices-in-woocommerce-variable-products
 * Requires Plugins: woocommerce
 * Requires at least: 5.0
 * Tested up to: 7.1
 * Requires PHP: 7.4
 * WC requires at least: 4.0
 * WC tested up to: 11.0
 * License: GPLv2+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'AYUDAWP_LOWEST_PRICES_VERSION', '2.3.0' );
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

		// One-time cleanup for users updating from an earlier version.
		$this->ayudawp_maybe_upgrade();

		// Admin hooks.
		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'ayudawp_add_admin_menu' ) );
			add_action( 'admin_init', array( $this, 'ayudawp_admin_init' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'ayudawp_enqueue_admin_assets' ) );
			add_action( 'admin_notices', array( $this, 'ayudawp_activation_notice' ) );
			add_filter( 'plugin_action_links_' . AYUDAWP_LOWEST_PRICES_PLUGIN_BASENAME, array( $this, 'ayudawp_plugin_action_links' ) );
		}

		// WooCommerce price filter.
		add_filter( 'woocommerce_variable_price_html', array( $this, 'ayudawp_custom_variable_price_range' ), 10, 2 );

		// Leave out-of-stock variations out of the price calculation.
		$options = $this->ayudawp_get_options();
		if ( ! empty( $options['exclude_out_of_stock'] ) ) {
			add_filter( 'woocommerce_variation_prices_price', array( $this, 'ayudawp_skip_out_of_stock_price' ), 10, 2 );
			add_filter( 'woocommerce_get_variation_prices_hash', array( $this, 'ayudawp_variation_prices_hash' ) );
		}
	}

	/**
	 * One-time cleanup, run once after the plugin version changes.
	 *
	 * - Drops the prefixes that only hold a copy of a bundled default (2.2.1).
	 * - Removes the deprecated 'hide_prefix_css' option (2.1.0).
	 *
	 * Gated on a stored version instead of a transient, so it runs once per
	 * update rather than once a day, and a prefix the shop actually typed is
	 * never looked at again after the update that cleaned up.
	 */
	private function ayudawp_maybe_upgrade() {
		if ( get_option( 'ayudawp_lowest_prices_version' ) === AYUDAWP_LOWEST_PRICES_VERSION ) {
			return;
		}

		$options  = get_option( 'ayudawp_lowest_prices_options', array() );
		$modified = false;

		if ( is_array( $options ) ) {

			foreach ( $this->ayudawp_find_stored_defaults( $options ) as $key ) {
				unset( $options[ $key ] );
				$modified = true;
			}

			// Legacy setting no longer used since 2.1.0.
			if ( isset( $options['hide_prefix_css'] ) ) {
				unset( $options['hide_prefix_css'] );
				$modified = true;
			}
		}

		if ( $modified ) {
			update_option( 'ayudawp_lowest_prices_options', $options );

			// Drop the lazy loaded copy so the rest of this request sees the change.
			$this->options = null;

			if ( function_exists( 'wc_delete_product_transients' ) ) {
				wc_delete_product_transients();
			}
		}

		// Gate of the old migration routine, replaced by the version option.
		delete_transient( 'ayudawp_lowest_prices_migrate_prefix' );

		update_option( 'ayudawp_lowest_prices_version', AYUDAWP_LOWEST_PRICES_VERSION );
	}

	/**
	 * Find stored prefixes that are just a copy of a bundled default.
	 *
	 * Until 2.2.1 the prefixes were stored verbatim in three different ways:
	 * activation wrote the English literals, a migration routine replaced them
	 * with the translation of the active locale, and the settings fields came
	 * pre-filled so the first "Save changes" stored whatever the admin was
	 * seeing. From then on the shop printed the stored value as is, so the
	 * prefix stopped going through translation and stayed in that one language
	 * for every visitor, which on a multilingual shop is plainly wrong.
	 *
	 * Removing the key restores the fallback: the option merge puts the bundled
	 * string back at read time, and it is resolved in the language of each
	 * visitor. A prefix the shop actually typed never matches any of the
	 * defaults and is left untouched, and so is an empty one, which is the way
	 * to ask for no prefix at all.
	 *
	 * Every locale installed on the site is checked, plus the original English,
	 * which covers the language the admin could have been using when saving.
	 *
	 * @param array $options Stored options.
	 * @return array List of option keys that only hold a bundled default.
	 */
	private function ayudawp_find_stored_defaults( $options ) {
		$found   = array();
		$locales = array_unique( array_merge( array( 'en_US', get_locale() ), get_available_languages() ) );

		foreach ( $locales as $locale ) {

			$switched = switch_to_locale( $locale );
			$defaults = $this->ayudawp_translatable_defaults();

			if ( $switched ) {
				restore_previous_locale();
			}

			foreach ( $defaults as $key => $default ) {
				if ( isset( $options[ $key ] ) && $options[ $key ] === $default ) {
					$found[ $key ] = $key;
				}
			}
		}

		return $found;
	}

	/**
	 * The settings whose default is a translatable string.
	 *
	 * These are printed to the customer, so their default has to stay a regular
	 * translatable string and follow the language of each visitor. They are only
	 * stored as an option when the shop writes its own wording, which is what
	 * ayudawp_sanitize_options() and ayudawp_maybe_upgrade() take care of.
	 *
	 * @return array Option key => bundled default text.
	 */
	private function ayudawp_translatable_defaults() {
		return array(
			'prefix_text'     => __( 'From', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
			'max_prefix_text' => __( 'Up to', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
		);
	}

	/**
	 * Get options with lazy loading and translated defaults.
	 *
	 * @return array
	 */
	private function ayudawp_get_options() {
		if ( null === $this->options ) {
			$saved_options = get_option( 'ayudawp_lowest_prices_options', array() );

			$this->options = wp_parse_args( $saved_options, $this->ayudawp_get_default_options() );
		}
		return $this->options;
	}

	/**
	 * Default options, with the prefixes in the language of the current request.
	 *
	 * The two prefixes are resolved here, at read time, and are never stored:
	 * that is what keeps them following the language of each visitor instead of
	 * freezing in the one the admin happened to be using. See
	 * ayudawp_translatable_defaults().
	 *
	 * @return array
	 */
	private function ayudawp_get_default_options() {
		return array_merge(
			array(
				'show_prefix_same_price'  => false,
				'add_space_after_prefix'  => true,
				'custom_css_class'        => 'ayudawp-lowest-price',
				'price_mode'              => 'lowest',
				'range_separator'         => '–',
				'suffix_text'             => '',
				'show_sale_strikethrough' => true,
				'show_discount_badge'     => false,
				'exclude_out_of_stock'    => false,
				'apply_scope'             => 'everywhere',
			),
			$this->ayudawp_translatable_defaults()
		);
	}

	/**
	 * Allowed values for the select settings.
	 *
	 * @return array Setting key => list of valid values, first one being the default.
	 */
	private function ayudawp_get_allowed_values() {
		return array(
			'price_mode'  => array( 'lowest', 'highest', 'range', 'range_short' ),
			'apply_scope' => array( 'everywhere', 'listings_only', 'single_only' ),
		);
	}

	/**
	 * Plugin activation — set default options.
	 */
	public function ayudawp_activate() {
		if ( false === get_option( 'ayudawp_lowest_prices_options', false ) ) {
			// Both prefixes are left out on purpose: they are resolved at read
			// time from the language pack, so storing them here would freeze
			// them in one language. Translations are not even loaded during
			// activation, which is what made the old code store English.
			add_option( 'ayudawp_lowest_prices_options', array(
				'show_prefix_same_price'  => false,
				'add_space_after_prefix'  => true,
				'custom_css_class'        => 'ayudawp-lowest-price',
				'price_mode'              => 'lowest',
				'range_separator'         => '–',
				'suffix_text'             => '',
				'show_sale_strikethrough' => true,
				'show_discount_badge'     => false,
				'exclude_out_of_stock'    => false,
				'apply_scope'             => 'everywhere',
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
		$options = $this->ayudawp_get_options();

		if ( ! $this->ayudawp_should_apply( $product, $options ) ) {
			return $price_html;
		}

		$prices = $product->get_variation_prices( true );

		// No priced variations: let WooCommerce handle it.
		if ( empty( $prices['price'] ) ) {
			return $price_html;
		}

		/*
		 * Each array in $prices is sorted on its own, so the first regular price
		 * does not necessarily belong to the cheapest variation. Both prices must
		 * be read through the same variation ID.
		 */
		$min_id  = array_key_first( $prices['price'] );
		$max_id  = array_key_last( $prices['price'] );
		$min     = $prices['price'][ $min_id ];
		$max     = $prices['price'][ $max_id ];
		$min_reg = isset( $prices['regular_price'][ $min_id ] ) ? $prices['regular_price'][ $min_id ] : '';
		$max_reg = isset( $prices['regular_price'][ $max_id ] ) ? $prices['regular_price'][ $max_id ] : '';

		$same_price = ( $min === $max );
		$mode       = $options['price_mode'];

		// A range needs two different prices to make sense.
		if ( $same_price && ( 'range' === $mode || 'range_short' === $mode ) ) {
			$mode = 'lowest';
		}

		$space = $options['add_space_after_prefix'] ? ' ' : '';

		switch ( $mode ) {
			case 'highest':
				$body   = $this->ayudawp_format_price( $max, $max_reg, $options );
				$body  .= $this->ayudawp_discount_badge( $max, $max_reg, $options );
				$body   = $this->ayudawp_add_prefix( $body, $options['max_prefix_text'], $space, $same_price, $options );
				$amount = $max;
				break;

			case 'range':
				$body = wc_price( $min );

				if ( '' !== $options['max_prefix_text'] ) {
					$body .= ' <span class="ayudawp-max-prefix">' . esc_html( $options['max_prefix_text'] ) . '</span> ' . wc_price( $max );
				} else {
					$body .= ' ' . wc_price( $max );
				}

				$body   = $this->ayudawp_add_prefix( $body, $options['prefix_text'], $space, false, $options );
				$amount = $min;
				break;

			case 'range_short':
				$separator = '' !== $options['range_separator'] ? $options['range_separator'] : '–';
				$body      = wc_price( $min ) . ' <span class="ayudawp-range-separator" aria-hidden="true">' . esc_html( $separator ) . '</span> ' . wc_price( $max );
				$amount    = $min;
				break;

			case 'lowest':
			default:
				$body   = $this->ayudawp_format_price( $min, $min_reg, $options );
				$body  .= $this->ayudawp_discount_badge( $min, $min_reg, $options );
				$body   = $this->ayudawp_add_prefix( $body, $options['prefix_text'], $space, $same_price, $options );
				$amount = $min;
				break;
		}

		$body .= $product->get_price_suffix( $amount );

		if ( '' !== $options['suffix_text'] ) {
			$body .= ' <span class="ayudawp-suffix">' . esc_html( $options['suffix_text'] ) . '</span>';
		}

		$css_class = ! empty( $options['custom_css_class'] ) ? $options['custom_css_class'] : 'ayudawp-lowest-price';
		$html      = '<span class="' . esc_attr( $css_class ) . '">' . $body . '</span>';

		/**
		 * Filters the final price HTML built by this plugin.
		 *
		 * Useful to append extra information next to the price, such as the lowest
		 * price of the last 30 days tracked by a price history plugin.
		 *
		 * @since 2.2.0
		 *
		 * @param string     $html    The price HTML about to be returned.
		 * @param WC_Product $product The variable product object.
		 * @param array      $options The plugin options in use.
		 */
		return apply_filters( 'ayudawp_lowest_price_html', $html, $product, $options );
	}

	/**
	 * Prepend the prefix text to the price body.
	 *
	 * @param string $body       Price HTML.
	 * @param string $prefix     Prefix text.
	 * @param string $space      Space between prefix and price.
	 * @param bool   $same_price Whether every variation shares the same price.
	 * @param array  $options    Plugin options.
	 * @return string
	 */
	private function ayudawp_add_prefix( $body, $prefix, $space, $same_price, $options ) {
		if ( '' === $prefix ) {
			return $body;
		}

		// With a single price the prefix is only shown when explicitly asked for.
		if ( $same_price && empty( $options['show_prefix_same_price'] ) ) {
			return $body;
		}

		return '<span class="ayudawp-prefix">' . esc_html( $prefix ) . '</span>' . $space . $body;
	}

	/**
	 * Format a price, keeping the struck through regular price when on sale.
	 *
	 * @param string|float $price   Active price.
	 * @param string|float $regular Regular price of the very same variation.
	 * @param array        $options Plugin options.
	 * @return string
	 */
	private function ayudawp_format_price( $price, $regular, $options ) {
		if ( ! empty( $options['show_sale_strikethrough'] ) && '' !== $regular && (float) $regular > (float) $price ) {
			// WooCommerce builds the accessible <del>/<ins> markup for us.
			return wc_format_sale_price( $regular, $price );
		}

		return wc_price( $price );
	}

	/**
	 * Build the discount percentage badge.
	 *
	 * @param string|float $price   Active price.
	 * @param string|float $regular Regular price of the very same variation.
	 * @param array        $options Plugin options.
	 * @return string Empty string when there is no discount to show.
	 */
	private function ayudawp_discount_badge( $price, $regular, $options ) {
		if ( empty( $options['show_discount_badge'] ) || '' === $regular || (float) $regular <= 0 ) {
			return '';
		}

		$percentage = (int) round( ( ( (float) $regular - (float) $price ) / (float) $regular ) * 100 );

		if ( $percentage <= 0 ) {
			return '';
		}

		return ' <span class="ayudawp-discount-badge">' . esc_html(
			sprintf(
				/* translators: %s: discount percentage, without the percent sign. */
				__( '-%s%%', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				$percentage
			)
		) . '</span>';
	}

	/**
	 * Whether the price should be modified in the current context.
	 *
	 * Product pages also list products (related, upsells) and blocks can render a
	 * product collection anywhere, so the check is whether this very product is
	 * the one being queried, not merely whether we are on a product page.
	 *
	 * @param WC_Product $product Product object.
	 * @param array      $options Plugin options.
	 * @return bool
	 */
	private function ayudawp_should_apply( $product, $options ) {
		if ( 'everywhere' === $options['apply_scope'] ) {
			return true;
		}

		$is_single_product = is_singular( 'product' ) && get_queried_object_id() === $product->get_id();

		return 'single_only' === $options['apply_scope'] ? $is_single_product : ! $is_single_product;
	}

	/**
	 * Drop out-of-stock variations from the price calculation.
	 *
	 * Returning an empty price makes WooCommerce skip the variation entirely.
	 *
	 * @param string|float $price     Variation price.
	 * @param WC_Product   $variation Variation object.
	 * @return string|float Empty string for out-of-stock variations.
	 */
	public function ayudawp_skip_out_of_stock_price( $price, $variation ) {
		return $variation->is_in_stock() ? $price : '';
	}

	/**
	 * Tell WooCommerce that the filtered prices need their own cache entry.
	 *
	 * Without this the cached transient built without the exclusion would be
	 * served back and the setting would seem to do nothing.
	 *
	 * @param array $hash Factors used to build the variation prices cache key.
	 * @return array
	 */
	public function ayudawp_variation_prices_hash( $hash ) {
		$hash['ayudawp_exclude_out_of_stock'] = true;

		return $hash;
	}

	/**
	 * Show a one-time dismissible notice after plugin activation.
	 *
	 * The notice is displayed once and the transient is deleted immediately,
	 * so it will not appear again on subsequent page loads.
	 */
	public function ayudawp_activation_notice() {
		// Only for users who can actually reach the settings page it links to.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

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

		// Price display section.
		add_settings_section(
			'ayudawp_lowest_prices_display_section',
			__( 'Price Display', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
			array( $this, 'ayudawp_display_section_callback' ),
			'ayudawp_lowest_prices_page'
		);

		$display_fields = array(
			'price_mode'              => __( 'What to show', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
			'max_prefix_text'         => __( 'Highest Price Prefix', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
			'range_separator'         => __( 'Range separator', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
			'suffix_text'             => __( 'Price Suffix', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
			'show_sale_strikethrough' => __( 'Keep the crossed out price on sale', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
			'show_discount_badge'     => __( 'Show discount percentage', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
			'exclude_out_of_stock'    => __( 'Ignore out of stock variations', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
			'apply_scope'             => __( 'Where to apply', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
		);

		foreach ( $display_fields as $field => $label ) {
			add_settings_field(
				$field,
				$label,
				array( $this, 'ayudawp_' . $field . '_callback' ),
				'ayudawp_lowest_prices_page',
				'ayudawp_lowest_prices_display_section'
			);
		}
	}

	/**
	 * Sanitize options before saving.
	 *
	 * @param array $input Raw input from the settings form.
	 * @return array Sanitized options.
	 */
	public function ayudawp_sanitize_options( $input ) {
		// The "Restore defaults" button travels inside the option array, so it
		// arrives here already past the nonce and capability checks of options.php.
		if ( isset( $input['reset_defaults'] ) ) {
			add_settings_error(
				'ayudawp_lowest_prices_options',
				'ayudawp_defaults_restored',
				__( 'Settings restored to their default values.', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'updated'
			);

			if ( function_exists( 'wc_delete_product_transients' ) ) {
				wc_delete_product_transients();
			}

			// Drop the lazy loaded copy so the rest of this request sees the new values.
			$this->options = null;

			$defaults = $this->ayudawp_get_default_options();

			// The prefixes go back to being resolved at read time, so they are
			// not written to the database along with the rest.
			foreach ( array_keys( $this->ayudawp_translatable_defaults() ) as $key ) {
				unset( $defaults[ $key ] );
			}

			return $defaults;
		}

		$sanitized = array();

		// Allow empty prefix (user wants no prefix text).
		$sanitized['prefix_text']            = isset( $input['prefix_text'] ) ? sanitize_text_field( $input['prefix_text'] ) : '';
		$sanitized['custom_css_class']       = isset( $input['custom_css_class'] ) ? sanitize_html_class( $input['custom_css_class'] ) : '';
		$sanitized['show_prefix_same_price'] = isset( $input['show_prefix_same_price'] );
		$sanitized['add_space_after_prefix'] = isset( $input['add_space_after_prefix'] );

		$sanitized['max_prefix_text'] = isset( $input['max_prefix_text'] ) ? sanitize_text_field( $input['max_prefix_text'] ) : '';
		$sanitized['range_separator'] = isset( $input['range_separator'] ) ? sanitize_text_field( $input['range_separator'] ) : '';
		$sanitized['suffix_text']     = isset( $input['suffix_text'] ) ? sanitize_text_field( $input['suffix_text'] ) : '';

		$sanitized['show_sale_strikethrough'] = isset( $input['show_sale_strikethrough'] );
		$sanitized['show_discount_badge']     = isset( $input['show_discount_badge'] );
		$sanitized['exclude_out_of_stock']    = isset( $input['exclude_out_of_stock'] );

		// Selects are restricted to their allowed values, falling back to the default.
		foreach ( $this->ayudawp_get_allowed_values() as $key => $allowed ) {
			$value            = isset( $input[ $key ] ) ? sanitize_text_field( $input[ $key ] ) : '';
			$sanitized[ $key ] = in_array( $value, $allowed, true ) ? $value : $allowed[0];
		}

		/*
		 * A prefix identical to the bundled default is not stored at all, so it
		 * keeps being resolved from the language pack on every request and each
		 * visitor of a multilingual shop reads it in their own language. An
		 * empty prefix is a different thing and is stored: it means no prefix.
		 */
		foreach ( $this->ayudawp_find_stored_defaults( $sanitized ) as $key ) {
			unset( $sanitized[ $key ] );
		}

		// The out-of-stock filters are registered on init, so the cached prices
		// built under the previous setting have to go.
		if ( function_exists( 'wc_delete_product_transients' ) ) {
			wc_delete_product_transients();
		}

		return $sanitized;
	}

	/**
	 * Section description callback.
	 */
	public function ayudawp_section_callback() {
		echo '<p>' . esc_html__( 'Configure how the lowest prices are displayed in your WooCommerce variable products.', 'show-only-lowest-prices-in-woocommerce-variable-products' ) . '</p>';
	}

	/**
	 * Price display section description callback.
	 */
	public function ayudawp_display_section_callback() {
		echo '<p>' . esc_html__( 'Choose which price to show, how sales are displayed and where these changes apply.', 'show-only-lowest-prices-in-woocommerce-variable-products' ) . '</p>';
	}

	/**
	 * Prefix text field callback.
	 */
	public function ayudawp_prefix_text_callback() {
		$options = $this->ayudawp_get_options();

		// Always set: an unsaved prefix comes from the defaults, already in the
		// language of this request. Saving it back unchanged stores nothing.
		$value = $options['prefix_text'];

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
		echo '<p class="description">';
		echo esc_html__( 'Add a space between the prefix and the price.', 'show-only-lowest-prices-in-woocommerce-variable-products' );
		echo ' ';
		// Second sentence added in 2.2.0. Kept separate so the sentence above
		// keeps the translations it already has since 2.0.
		echo esc_html__( 'Turn it off for symbol prefixes like ~ or for languages that do not separate words with spaces. The text field cannot store a trailing space, so this checkbox is the way to control it.', 'show-only-lowest-prices-in-woocommerce-variable-products' );
		echo '</p>';
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
	 * Render a text input for one of the plugin options.
	 *
	 * @param string $key         Option key.
	 * @param string $placeholder Placeholder shown when the field is left empty.
	 * @param string $description Field description.
	 */
	private function ayudawp_render_text_field( $key, $placeholder, $description ) {
		$options = $this->ayudawp_get_options();
		$value   = isset( $options[ $key ] ) ? $options[ $key ] : '';

		echo '<input type="text" name="ayudawp_lowest_prices_options[' . esc_attr( $key ) . ']" value="' . esc_attr( $value ) . '" placeholder="' . esc_attr( $placeholder ) . '" class="regular-text" />';
		echo '<p class="description">' . esc_html( $description ) . '</p>';
	}

	/**
	 * Render a checkbox for one of the plugin options.
	 *
	 * @param string $key         Option key.
	 * @param string $description Field description.
	 */
	private function ayudawp_render_checkbox_field( $key, $description ) {
		$options = $this->ayudawp_get_options();
		$checked = ! empty( $options[ $key ] );

		echo '<input type="checkbox" name="ayudawp_lowest_prices_options[' . esc_attr( $key ) . ']" value="1" ' . checked( 1, $checked, false ) . ' />';
		echo '<p class="description">' . esc_html( $description ) . '</p>';
	}

	/**
	 * Render a select for one of the plugin options.
	 *
	 * @param string $key         Option key.
	 * @param array  $choices     Value => label pairs.
	 * @param string $description Field description.
	 */
	private function ayudawp_render_select_field( $key, $choices, $description ) {
		$options = $this->ayudawp_get_options();
		$value   = isset( $options[ $key ] ) ? $options[ $key ] : '';

		echo '<select name="ayudawp_lowest_prices_options[' . esc_attr( $key ) . ']">';
		foreach ( $choices as $choice => $label ) {
			echo '<option value="' . esc_attr( $choice ) . '" ' . selected( $value, $choice, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select>';
		echo '<p class="description">' . esc_html( $description ) . '</p>';
	}

	/**
	 * Price mode field callback.
	 */
	public function ayudawp_price_mode_callback() {
		$this->ayudawp_render_select_field(
			'price_mode',
			array(
				'lowest'      => __( 'Lowest price only — From 40', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'highest'     => __( 'Highest price only — Up to 60', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'range'       => __( 'Both, with text — From 40 up to 60', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'range_short' => __( 'Both, with a separator — 40 – 60', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
			),
			__( 'Which price replaces the default WooCommerce range. Products whose variations all cost the same always show that single price.', 'show-only-lowest-prices-in-woocommerce-variable-products' )
		);
	}

	/**
	 * Highest price text field callback.
	 */
	public function ayudawp_max_prefix_text_callback() {
		$this->ayudawp_render_text_field(
			'max_prefix_text',
			__( 'Up to', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
			__( 'Used before the highest price, and between both prices in the "From 40 up to 60" mode. Leave empty to show no text.', 'show-only-lowest-prices-in-woocommerce-variable-products' )
		);
	}

	/**
	 * Range separator field callback.
	 */
	public function ayudawp_range_separator_callback() {
		$this->ayudawp_render_text_field(
			'range_separator',
			'–',
			__( 'Character shown between both prices in the "40 – 60" mode.', 'show-only-lowest-prices-in-woocommerce-variable-products' )
		);
	}

	/**
	 * Suffix text field callback.
	 */
	public function ayudawp_suffix_text_callback() {
		$this->ayudawp_render_text_field(
			'suffix_text',
			__( '/ month', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
			__( 'Text shown after the price, for a unit or a recurrence, such as "/ month", "per person" or "per night". Leave empty to show none. For tax notices like "VAT included" use the WooCommerce price suffix in WooCommerce > Settings > Tax, which already handles them.', 'show-only-lowest-prices-in-woocommerce-variable-products' )
		);
	}

	/**
	 * Sale strikethrough checkbox callback.
	 */
	public function ayudawp_show_sale_strikethrough_callback() {
		$this->ayudawp_render_checkbox_field(
			'show_sale_strikethrough',
			__( 'When the shown variation is on sale, keep its regular price crossed out next to the sale price, the way WooCommerce does.', 'show-only-lowest-prices-in-woocommerce-variable-products' )
		);
	}

	/**
	 * Discount badge checkbox callback.
	 */
	public function ayudawp_show_discount_badge_callback() {
		$this->ayudawp_render_checkbox_field(
			'show_discount_badge',
			__( 'Add the discount percentage after the price. Style it with the .ayudawp-discount-badge CSS class.', 'show-only-lowest-prices-in-woocommerce-variable-products' )
		);
	}

	/**
	 * Out of stock exclusion checkbox callback.
	 */
	public function ayudawp_exclude_out_of_stock_callback() {
		$this->ayudawp_render_checkbox_field(
			'exclude_out_of_stock',
			__( 'Leave out of stock variations out of the calculation, so the shown price is one your customers can actually buy.', 'show-only-lowest-prices-in-woocommerce-variable-products' )
		);
	}

	/**
	 * Apply scope field callback.
	 */
	public function ayudawp_apply_scope_callback() {
		$this->ayudawp_render_select_field(
			'apply_scope',
			array(
				'everywhere'    => __( 'Everywhere', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'listings_only' => __( 'Shop and archives only', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'single_only'   => __( 'Product pages only', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
			),
			__( 'Related products, upsells and product blocks count as listings, even inside a product page.', 'show-only-lowest-prices-in-woocommerce-variable-products' )
		);
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

		wp_enqueue_script(
			'ayudawp-lowest-prices-admin',
			AYUDAWP_LOWEST_PRICES_PLUGIN_URL . 'assets/js/admin.js',
			array(),
			AYUDAWP_LOWEST_PRICES_VERSION,
			true
		);

		wp_localize_script(
			'ayudawp-lowest-prices-admin',
			'ayudawpLowestPrices',
			array(
				'confirmReset' => __( 'This will restore every setting to its default value. Your prefix, suffix, price mode and any other customization will be lost. Do you want to continue?', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'preview'      => $this->ayudawp_get_preview_samples(),
			)
		);
	}

	/**
	 * Sample prices for the live preview, formatted with the store currency.
	 *
	 * The preview is assembled in JavaScript, so only the price amounts are built
	 * here: WooCommerce is the one that knows the currency, the decimal separator
	 * and the accessible markup of a sale price.
	 *
	 * @return array Price HTML snippets keyed by role, plus the discount badge text.
	 */
	private function ayudawp_get_preview_samples() {
		if ( ! function_exists( 'wc_price' ) ) {
			return array();
		}

		return array(
			'min'     => wc_price( 40 ),
			'max'     => wc_price( 60 ),
			'minSale' => wc_format_sale_price( 50, 40 ),
			'maxSale' => wc_format_sale_price( 75, 60 ),
			'badge'   => sprintf(
				/* translators: %s: discount percentage, without the percent sign. */
				__( '-%s%%', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				20
			),
		);
	}

	/**
	 * Live preview of the price format, filled in by the admin script.
	 *
	 * Hidden until JavaScript takes over, so nobody is left looking at an empty
	 * box, and never saved: it only shows what the current options would print.
	 * It sits at the top of the form, and sticks there on wide screens, so it is
	 * still visible while the options further down the page are being changed.
	 */
	private function ayudawp_render_preview() {
		if ( ! function_exists( 'wc_price' ) ) {
			return;
		}

		$cases = array(
			'varied' => __( 'Variations at different prices', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
			'sale'   => __( 'The variation shown is on sale', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
			'same'   => __( 'All variations at the same price', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
		);
		?>
		<div class="ayudawp-lp-preview" id="ayudawp-lp-preview" hidden>
			<p class="ayudawp-lp-preview-title"><?php esc_html_e( 'Live preview', 'show-only-lowest-prices-in-woocommerce-variable-products' ); ?></p>
			<ul class="ayudawp-lp-preview-list">
				<?php foreach ( $cases as $case => $label ) : ?>
					<li>
						<span class="ayudawp-lp-preview-case"><?php echo esc_html( $label ); ?></span>
						<span class="ayudawp-lp-preview-price" data-case="<?php echo esc_attr( $case ); ?>"></span>
					</li>
				<?php endforeach; ?>
			</ul>
			<p class="description">
				<?php esc_html_e( 'Sample amounts in your store currency, updated as you change the options on this page. Nothing is saved until you press Save Settings, and taxes are left out because WooCommerce adds those on its own.', 'show-only-lowest-prices-in-woocommerce-variable-products' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Name of the active price history plugin, if any.
	 *
	 * These plugins track the lowest price of the last 30 days required by the EU
	 * Omnibus Directive, which this plugin does not do. Detection is done against
	 * the active plugins list to avoid guessing class or constant names.
	 *
	 * @return string Plugin name, or an empty string when none is active.
	 */
	private function ayudawp_get_price_history_plugin() {
		$known = array(
			'omnibus'               => 'Omnibus — show the lowest price',
			'wc-price-history'      => 'WC Price History',
			'product-price-history' => 'Product Price History for WooCommerce',
			'fleekcode-omnibus'     => 'FleekCode – Omnibus Price Tracker',
		);

		$active = (array) get_option( 'active_plugins', array() );

		if ( is_multisite() ) {
			$active = array_merge( $active, array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) ) );
		}

		foreach ( $active as $plugin_file ) {
			$folder = dirname( $plugin_file );

			if ( isset( $known[ $folder ] ) ) {
				return $known[ $folder ];
			}
		}

		return '';
	}

	/**
	 * Notice explaining how this plugin coexists with a price history plugin.
	 */
	private function ayudawp_render_price_history_notice() {
		$plugin_name = $this->ayudawp_get_price_history_plugin();

		if ( '' === $plugin_name ) {
			return;
		}
		?>
		<div class="notice notice-info inline ayudawp-lp-notice">
			<p>
				<?php
				printf(
					/* translators: %s: name of the detected price history plugin. */
					esc_html__( '%s is active on this site. Both plugins work together: this one decides which price replaces the WooCommerce range, and the other one adds the lowest price of the last 30 days required by the EU Omnibus Directive.', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
					esc_html( $plugin_name )
				);
				?>
			</p>
			<p>
				<?php esc_html_e( 'If the two messages end up in the wrong order, use the ayudawp_lowest_price_html filter to place them exactly where you want.', 'show-only-lowest-prices-in-woocommerce-variable-products' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Render the admin settings page.
	 */
	public function ayudawp_admin_page() {
		// Load promo banner class.
		if ( ! class_exists( 'AyudaWP_Lowest_Prices_Promo_Banner' ) ) {
			require_once AYUDAWP_LOWEST_PRICES_PLUGIN_PATH . 'includes/class-ayudawp-lowest-prices-promo-banner.php';
		}

		$promo_banner = new AyudaWP_Lowest_Prices_Promo_Banner( 'ayudawp-lp' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Show only lowest prices in variable products', 'show-only-lowest-prices-in-woocommerce-variable-products' ); ?></h1>
			<p><?php esc_html_e( 'Clean up your WooCommerce variable product prices and boost your sales.', 'show-only-lowest-prices-in-woocommerce-variable-products' ); ?></p>

			<?php $this->ayudawp_render_price_history_notice(); ?>

			<div class="ayudawp-content">
				<div class="ayudawp-main">
					<form method="post" action="options.php">
						<?php
						settings_fields( 'ayudawp_lowest_prices_group' );
						$this->ayudawp_render_preview();
						do_settings_sections( 'ayudawp_lowest_prices_page' );
						?>
						<p class="submit">
							<?php submit_button( __( 'Save Settings', 'show-only-lowest-prices-in-woocommerce-variable-products' ), 'primary', 'submit', false ); ?>
							<input type="submit" id="ayudawp-lp-reset" name="ayudawp_lowest_prices_options[reset_defaults]" class="button button-secondary" value="<?php esc_attr_e( 'Restore defaults', 'show-only-lowest-prices-in-woocommerce-variable-products' ); ?>" />
						</p>
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