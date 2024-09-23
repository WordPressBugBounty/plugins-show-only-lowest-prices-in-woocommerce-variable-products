<?php
/*
 Plugin Name: WooCommerce - Show only lowest prices in variable products
 Plugin URI: https://servicios.ayudawp.com/
 Description: Shows only the lowest price and sale in variable WooCommerce products.
 Author: Fernando Tellado
 Version: 1.0.6
 Author URI: https://ayudawp.com
 Text Domain: show-only-lowest-prices-in-woocommerce-variable-products
 Domain Path: /languages
 Requires Plugins: woocommerce
 Requires at least: 4.0
 Tested up to: 6.6.1
 WC requires at least: 3.0
 WC tested up to: 9.2.3
 License: GPLv2+
*/
/* This is for security */
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
/* This is for translations */
function show_only_lowest_prices_in_woocommerce_variable_products_load_plugin_textdomain() {
    load_plugin_textdomain( 'show-only-lowest-prices-in-woocommerce-variable-products', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'show_only_lowest_prices_in_woocommerce_variable_products_load_plugin_textdomain' );
/* Declare HPOS compatibility */
add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );
/* The code */
add_filter( 'woocommerce_variable_sale_price_html', 'custom_variable_price_range', 10, 2 );
add_filter( 'woocommerce_variable_price_html', 'custom_variable_price_range', 10, 2 );
function custom_variable_price_range( $price_html, $product ) {

    $prefix     = __('From', 'show-only-lowest-prices-in-woocommerce-variable-products');
    $min_price  = $product->get_variation_price( 'min', true );
    $suffix = $product->get_price_suffix( $price = '', $qty = 1 );

    /* If all variations have same price not display prefix - No mostrar prefijo si todas las variaciones tienen el mismo precio */
    $max_price  = $product->get_variation_price( 'max', true );
    if( $min_price == $max_price ) 
        return wc_price( $min_price ) . $suffix;

    return $prefix . ' ' . wc_price( $min_price ) . $suffix;
}