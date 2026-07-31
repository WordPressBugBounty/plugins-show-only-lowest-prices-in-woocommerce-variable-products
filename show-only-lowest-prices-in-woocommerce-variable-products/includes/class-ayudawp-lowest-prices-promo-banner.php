<?php
/**
 * AyudaWP Lowest Prices Promotional Banner
 *
 * Promotional banner for Show only lowest prices plugin.
 * Displays random AyudaWP services in the admin sidebar.
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
	 * CSS class prefix.
	 *
	 * @var string
	 */
	private $css_prefix;

	/**
	 * Constructor.
	 *
	 * @param string $css_prefix CSS class prefix.
	 */
	public function __construct( $css_prefix ) {
		$this->css_prefix = $css_prefix;
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
				/* translators: Maintenance service URL. Change this URL in translations to use a localized landing page. */
				'url'         => __( 'https://mantenimiento.ayudawp.com/en/', 'show-only-lowest-prices-in-woocommerce-variable-products' ),
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
	 * Get random services.
	 *
	 * @param int $count Number of services to return.
	 * @return array
	 */
	private function get_random_services( $count = 3 ) {
		$services = $this->get_services_catalog();

		$random_keys = array_rand( $services, min( $count, count( $services ) ) );

		if ( ! is_array( $random_keys ) ) {
			$random_keys = array( $random_keys );
		}

		$result = array();
		foreach ( $random_keys as $key ) {
			$result[ $key ] = $services[ $key ];
		}

		return $result;
	}

	/**
	 * Render the promotional banner (vertical/sidebar layout).
	 */
	public function render() {
		$services = $this->get_random_services( 3 );
		$prefix   = $this->css_prefix;

		foreach ( $services as $service ) :
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
		endforeach;
	}
}
