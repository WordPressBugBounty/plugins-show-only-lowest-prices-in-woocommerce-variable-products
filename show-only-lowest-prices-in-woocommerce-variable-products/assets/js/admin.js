/**
 * Admin behaviour for the AyudaWP Lowest Prices settings page.
 *
 * Asks for confirmation before restoring the default settings, since doing so
 * discards every customization without a way back.
 *
 * @package AyudaWP_Lowest_Prices
 */

( function () {
	'use strict';

	function init() {
		var button = document.getElementById( 'ayudawp-lp-reset' );

		if ( ! button || 'undefined' === typeof ayudawpLowestPrices ) {
			return;
		}

		button.addEventListener( 'click', function ( event ) {
			if ( ! window.confirm( ayudawpLowestPrices.confirmReset ) ) {
				event.preventDefault();
			}
		} );
	}

	// The script is enqueued in the footer, so the DOM is usually ready already.
	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
