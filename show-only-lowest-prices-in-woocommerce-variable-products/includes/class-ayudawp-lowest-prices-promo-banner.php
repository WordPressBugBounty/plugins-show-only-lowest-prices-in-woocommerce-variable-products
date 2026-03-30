<?php
/**
 * AyudaWP Lowest Prices Promotional Banner
 *
 * Promotional banner for Show only lowest prices plugin.
 * Displays random plugin recommendations and services in the admin sidebar.
 *
 * @package AyudaWP_Lowest_Prices
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AyudaWP Lowest Prices Promo Banner class.
 */
class AyudaWP_Lowest_Prices_Promo_Banner {

	/**
	 * Current plugin slug to exclude from recommendations.
	 *
	 * @var string
	 */
	private $current_plugin_slug;

	/**
	 * CSS class prefix.
	 *
	 * @var string
	 */
	private $css_prefix;

	/**
	 * Constructor.
	 *
	 * @param string $current_plugin_slug Current plugin slug.
	 * @param string $css_prefix          CSS class prefix.
	 */
	public function __construct( $current_plugin_slug, $css_prefix ) {
		$this->current_plugin_slug = $current_plugin_slug;
		$this->css_prefix          = $css_prefix;
	}

	/**
	 * Get plugins catalog.
	 *
	 * @return array
	 */
	private function get_plugins_catalog() {
		return array(
			'vigilante'          => array(
				'icon'        => 'dashicons-shield',
				'title'       => __( 'Complete WordPress security', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'description' => __( 'All-in-one security plugin: firewall, login protection, security headers, 2FA, file integrity monitoring, and activity logging.', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'button'      => __( 'Install Vigilante', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
			),
			'gozer'              => array(
				'icon'        => 'dashicons-admin-network',
				'title'       => __( 'Restrict site access', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'description' => __( 'Force visitors to log in before accessing your site with extensive exception controls for pages, posts, and user roles.', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'button'      => __( 'Install Gozer', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
			),
			'vigia'              => array(
				'icon'        => 'dashicons-visibility',
				'title'       => __( 'Monitor AI crawler activity', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'description' => __( 'Track which AI bots visit your site, analyze their behavior, and take control with blocking rules and robots.txt management.', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'button'      => __( 'Install VigIA', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
			),
			'ai-share-summarize' => array(
				'icon'        => 'dashicons-share',
				'title'       => __( 'Boost your AI presence', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'description' => __( 'Add social sharing and AI summarize buttons. Help visitors share your content and let AIs learn from your site while getting backlinks.', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'button'      => __( 'Install AI Share & Summarize', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
			),
			'ai-content-signals' => array(
				'icon'        => 'dashicons-flag',
				'title'       => __( 'Control AI content usage', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'description' => __( 'Cloudflare-endorsed plugin to define how AI systems can use your content: for training, search results, or both.', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'button'      => __( 'Install AI Content Signals', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
			),
			'wpo-tweaks'         => array(
				'icon'        => 'dashicons-performance',
				'title'       => __( 'Speed up your WordPress', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'description' => __( 'Comprehensive performance optimizations: critical CSS, lazy loading, cache rules, and 30+ tweaks with zero configuration.', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'button'      => __( 'Install Zero Config Performance', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
			),
			'no-gutenberg'       => array(
				'icon'        => 'dashicons-edit-page',
				'title'       => __( 'Back to Classic Editor', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'description' => __( 'Completely remove Gutenberg, FSE styles, and block widgets. Restore the classic editing experience with better performance.', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'button'      => __( 'Install No Gutenberg', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
			),
			'anticache'          => array(
				'icon'        => 'dashicons-hammer',
				'title'       => __( 'Development toolkit', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'description' => __( 'Bypass all caching during development. Auto-detects cache plugins, enables debug mode, and includes maintenance screen.', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'button'      => __( 'Install Anti-Cache Kit', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
			),
			'auto-capitalize-names-ayudawp' => array(
				'icon'        => 'dashicons-editor-textcolor',
				'title'       => __( 'Fix customer names', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'description' => __( 'Auto-capitalize names and addresses in WordPress and WooCommerce. Keep invoices and reports professionally formatted.', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'button'      => __( 'Install Auto Capitalize', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
			),
			'easy-actions-scheduler-cleaner-ayudawp' => array(
				'icon'        => 'dashicons-database-remove',
				'title'       => __( 'Clean Action Scheduler', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'description' => __( 'Remove millions of completed, failed, and old actions from WooCommerce Action Scheduler. Reduce database size instantly.', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'button'      => __( 'Install Scheduler Cleaner', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
			),
			'native-sitemap-customizer' => array(
				'icon'        => 'dashicons-networking',
				'title'       => __( 'Customize your sitemap', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'description' => __( 'Control WordPress native sitemap: exclude post types, taxonomies, specific posts, and authors. No bloat, just options.', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'button'      => __( 'Install Sitemap Customizer', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
			),
			'post-visibility-control' => array(
				'icon'        => 'dashicons-hidden',
				'title'       => __( 'Control post visibility', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'description' => __( 'Hide posts from homepage, archives, feeds, or REST API while keeping them accessible via direct URL.', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'button'      => __( 'Install Post Visibility', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
			),
			'widget-visibility-control' => array(
				'icon'        => 'dashicons-welcome-widgets-menus',
				'title'       => __( 'Smart widget display', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'description' => __( 'Show or hide widgets based on pages, post types, categories, user roles, and more. Works with any theme.', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'button'      => __( 'Install Widget Visibility', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
			),
			'search-replace-text-blocks' => array(
				'icon'        => 'dashicons-search',
				'title'       => __( 'Search & replace in blocks', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'description' => __( 'Find and replace text across all your Gutenberg blocks. Bulk edit content without touching the database directly.', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'button'      => __( 'Install Search Replace Blocks', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
			),
			'seo-read-more-buttons-ayudawp' => array(
				'icon'        => 'dashicons-admin-links',
				'title'       => __( 'Better read more links', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'description' => __( 'Customize excerpt "read more" links with buttons, custom text, and nofollow option. Improve CTR and SEO.', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'button'      => __( 'Install SEO Read More', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
			),
			'multiple-sale-prices-scheduler' => array(
				'icon'        => 'dashicons-calendar-alt',
				'title'       => __( 'Schedule sale prices', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'description' => __( 'Set multiple future sale prices for WooCommerce products. Plan promotions in advance with start and end dates.', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'button'      => __( 'Install Sale Scheduler', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
			),
			'easy-store-management-ayudawp' => array(
				'icon'        => 'dashicons-store',
				'title'       => __( 'Simplify store management', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'description' => __( 'Clean up WordPress admin for Store Managers. Hide unnecessary menus, keep only orders, products, and customers, plus quick access shortcuts.', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'button'      => __( 'Install Easy Store', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
			),
			'lightbox-images-for-divi' => array(
				'icon'        => 'dashicons-format-gallery',
				'title'       => __( 'Lightbox for Divi', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'description' => __( 'Add native lightbox functionality to Divi theme images. No jQuery, fast loading, fully customizable.', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'button'      => __( 'Install Divi Lightbox', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
			),
			'scheduled-posts-showcase' => array(
				'icon'        => 'dashicons-clock',
				'title'       => __( 'Show visitors what is coming up next', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'description' => __( 'Display your scheduled and future posts on the frontend to gain and retain visits.', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'button'      => __( 'Install Scheduled Posts Showcase', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
			),
			'periscopio'         => array(
				'icon'        => 'dashicons-rss',
				'title'       => __( 'Custom Dashboard News', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'description' => __( 'Add your own custom feeds and links to the news and events dashboard widget and replace WordPress default one.', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'button'      => __( 'Install Periscopio', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
			),
		);
	}

	/**
	 * Get services catalog.
	 *
	 * @return array
	 */
	private function get_services_catalog() {
		return array(
			'maintenance' => array(
				'icon'        => 'dashicons-admin-tools',
				'title'       => __( 'Need help with your website?', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'description' => __( 'Professional WordPress maintenance: security monitoring, regular backups, performance optimization, and priority support.', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'button'      => __( 'Learn more', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'url'         => 'https://mantenimiento.ayudawp.com',
			),
			'consultancy' => array(
				'icon'        => 'dashicons-businessman',
				'title'       => __( 'WordPress consultancy', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'description' => __( 'One-on-one online sessions to solve your WordPress doubts, get expert advice, and make better decisions for your project.', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'button'      => __( 'Book a session', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'url'         => 'https://servicios.ayudawp.com/producto/consultoria-online-wordpress/',
			),
			'hacked'      => array(
				'icon'        => 'dashicons-sos',
				'title'       => __( 'Hacked website?', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'description' => __( 'Fast recovery service for compromised WordPress sites. We clean malware, fix vulnerabilities, and restore your site security.', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'button'      => __( 'Get help now', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'url'         => 'https://servicios.ayudawp.com/producto/wordpress-hackeado/',
			),
			'development' => array(
				'icon'        => 'dashicons-editor-code',
				'title'       => __( 'Custom development', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'description' => __( 'Need a custom plugin, theme modifications, or specific functionality? We build tailored WordPress solutions for your needs.', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'button'      => __( 'Request a quote', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'url'         => 'https://servicios.ayudawp.com/producto/desarrollo-wordpress/',
			),
			'hosting'     => array(
				'icon'        => 'dashicons-cloud-saved',
				'title'       => __( 'Hosting built for WordPress', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'description' => __( 'Google Cloud servers, automatic geo-located daily backups, and 24/7 expert support. Speed, security, and migration tools included.', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				'button'      => __( 'Learn more', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
				/* translators: SiteGround affiliate URL. Change this URL in translations to use a localized landing page. */
				'url'         => __( 'https://stgrnd.co/telladowpbox', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
			),
		);
	}

	/**
	 * Get random plugins excluding current.
	 *
	 * @param int $count Number of plugins to return.
	 * @return array
	 */
	private function get_random_plugins( $count = 2 ) {
		$plugins = $this->get_plugins_catalog();

		// Remove current plugin.
		unset( $plugins[ $this->current_plugin_slug ] );

		// Get random keys.
		$random_keys = array_rand( $plugins, min( $count, count( $plugins ) ) );

		if ( ! is_array( $random_keys ) ) {
			$random_keys = array( $random_keys );
		}

		$result = array();
		foreach ( $random_keys as $key ) {
			$result[ $key ] = $plugins[ $key ];
		}

		return $result;
	}

	/**
	 * Get random service.
	 *
	 * @return array
	 */
	private function get_random_service() {
		$services   = $this->get_services_catalog();
		$random_key = array_rand( $services );

		return $services[ $random_key ];
	}

	/**
	 * Render the promotional banner (vertical/sidebar layout).
	 */
	public function render() {
		$plugins = $this->get_random_plugins( 2 );
		$service = $this->get_random_service();
		$prefix  = $this->css_prefix;

		// Render plugin widgets.
		foreach ( $plugins as $slug => $plugin ) :
			?>
			<div class="<?php echo esc_attr( $prefix ); ?>-sidebar-widget <?php echo esc_attr( $prefix ); ?>-promo-widget">
				<span class="dashicons <?php echo esc_attr( $plugin['icon'] ); ?>"></span>
				<h3><?php echo esc_html( $plugin['title'] ); ?></h3>
				<p><?php echo esc_html( $plugin['description'] ); ?></p>
				<a href="<?php echo esc_url( admin_url( 'plugin-install.php?tab=plugin-information&plugin=' . $slug . '&TB_iframe=true&width=772&height=618' ) ); ?>" class="button thickbox">
					<?php echo esc_html( $plugin['button'] ); ?>
				</a>
			</div>
			<?php
		endforeach;

		// Render service widget.
		?>
		<div class="<?php echo esc_attr( $prefix ); ?>-sidebar-widget <?php echo esc_attr( $prefix ); ?>-promo-widget">
			<span class="dashicons <?php echo esc_attr( $service['icon'] ); ?>"></span>
			<h3><?php echo esc_html( $service['title'] ); ?></h3>
			<p><?php echo esc_html( $service['description'] ); ?></p>
			<a href="<?php echo esc_url( $service['url'] ); ?>" target="_blank" rel="noopener noreferrer" class="button button-primary">
				<?php echo esc_html( $service['button'] ); ?>
			</a>
		</div>
		<?php
	}
}