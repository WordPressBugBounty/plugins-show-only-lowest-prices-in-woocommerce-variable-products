<?php
/**
 * Uninstall script for Show only lowest prices in variable products for WooCommerce
 *
 * Removes all plugin data from the database on uninstall.
 *
 * @package AyudaWP_Lowest_Prices
 */

// Exit if not called from WordPress uninstall.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Remove plugin options and transients.
 */
function ayudawp_lowest_prices_uninstall() {
	delete_option( 'ayudawp_lowest_prices_options' );
	delete_transient( 'ayudawp_lowest_prices_activation_notice' );
	delete_transient( 'ayudawp_lowest_prices_migrate_prefix' );

	// Legacy transients from previous versions.
	delete_transient( 'ayudawp_update_default_text' );

	// Multisite cleanup.
	if ( is_multisite() ) {
		$sites = get_sites( array( 'number' => 0 ) );

		foreach ( $sites as $site ) {
			switch_to_blog( $site->blog_id );

			delete_option( 'ayudawp_lowest_prices_options' );
			delete_transient( 'ayudawp_lowest_prices_activation_notice' );
			delete_transient( 'ayudawp_lowest_prices_migrate_prefix' );
			delete_transient( 'ayudawp_update_default_text' );

			restore_current_blog();
		}
	}
}

ayudawp_lowest_prices_uninstall();