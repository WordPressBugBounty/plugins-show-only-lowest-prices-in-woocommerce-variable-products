<?php
/**
 * Uninstall script for Show only lowest prices in variable products for WooCommerce
 *
 * @package AyudaWP_Lowest_Prices
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

/**
 * Remove plugin options and clean up database
 */
function ayudawp_lowest_prices_uninstall() {
    // Remove plugin options
    delete_option( 'ayudawp_lowest_prices_options' );
    
    // Remove any transients
    delete_transient( 'ayudawp_lowest_prices_activation_notice' );
    
    // For multisite installations
    if ( is_multisite() ) {
        // Get all sites in the network
        $sites = get_sites( array( 'number' => 0 ) );
        
        foreach ( $sites as $site ) {
            switch_to_blog( $site->blog_id );
            
            // Remove options for each site
            delete_option( 'ayudawp_lowest_prices_options' );
            delete_transient( 'ayudawp_lowest_prices_activation_notice' );
            
            restore_current_blog();
        }
    }
}

// Execute cleanup
ayudawp_lowest_prices_uninstall();