/**
 * Admin behaviour for the AyudaWP Lowest Prices settings page.
 *
 * Two things happen here: a confirmation before restoring the default settings,
 * since doing so discards every customization without a way back, and the live
 * preview of the price format, which mirrors what the plugin prints on the front
 * end for three cases at once.
 *
 * @package AyudaWP_Lowest_Prices
 */

( function () {
	'use strict';

	var OPTION = 'ayudawp_lowest_prices_options';

	/*
	 * Each preview line is one situation the options behave differently in:
	 * different prices, the shown variation on sale, and every variation at the
	 * same price, which is what the "show prefix when all prices are the same"
	 * setting is about.
	 */
	var CASES = {
		varied: { same: false, sale: false },
		sale: { same: false, sale: true },
		same: { same: true, sale: false }
	};

	function field( key ) {
		return document.querySelector( '[name="' + OPTION + '[' + key + ']"]' );
	}

	function value( key ) {
		var el = field( key );

		return el ? el.value : '';
	}

	function enabled( key ) {
		var el = field( key );

		return !! ( el && el.checked );
	}

	// Price amounts come from wc_price() on the server, so they are markup.
	function priceNode( html ) {
		var span = document.createElement( 'span' );

		span.innerHTML = html;

		return span;
	}

	// Anything the shop owner types is inserted as text, never as markup.
	function textNode( className, text ) {
		var span = document.createElement( 'span' );

		span.className = className;
		span.textContent = text;

		return span;
	}

	function space( text ) {
		return document.createTextNode( text );
	}

	function withPrefix( nodes, prefix, gap, samePrice ) {
		if ( '' === prefix ) {
			return nodes;
		}

		// With a single price the prefix is only shown when explicitly asked for.
		if ( samePrice && ! enabled( 'show_prefix_same_price' ) ) {
			return nodes;
		}

		return [ textNode( 'ayudawp-prefix', prefix ), space( gap ) ].concat( nodes );
	}

	function build( sample, data ) {
		var mode = value( 'price_mode' );
		var gap = enabled( 'add_space_after_prefix' ) ? ' ' : '';
		var onSale = sample.sale && enabled( 'show_sale_strikethrough' );
		var badge = sample.sale && enabled( 'show_discount_badge' );
		var min = onSale ? data.minSale : data.min;
		var max = sample.same ? min : ( onSale ? data.maxSale : data.max );
		var suffix = value( 'suffix_text' );
		var separator;
		var maxPrefix;
		var nodes;

		// A range needs two different prices to make sense.
		if ( sample.same && ( 'range' === mode || 'range_short' === mode ) ) {
			mode = 'lowest';
		}

		switch ( mode ) {
			case 'highest':
				nodes = [ priceNode( max ) ];

				if ( badge ) {
					nodes.push( space( ' ' ), textNode( 'ayudawp-discount-badge', data.badge ) );
				}

				nodes = withPrefix( nodes, value( 'max_prefix_text' ), gap, sample.same );
				break;

			case 'range':
				maxPrefix = value( 'max_prefix_text' );
				nodes = [ priceNode( data.min ), space( ' ' ) ];

				if ( '' !== maxPrefix ) {
					nodes.push( textNode( 'ayudawp-max-prefix', maxPrefix ), space( ' ' ) );
				}

				nodes.push( priceNode( data.max ) );
				nodes = withPrefix( nodes, value( 'prefix_text' ), gap, false );
				break;

			case 'range_short':
				separator = '' !== value( 'range_separator' ) ? value( 'range_separator' ) : '–';
				nodes = [
					priceNode( data.min ),
					space( ' ' ),
					textNode( 'ayudawp-range-separator', separator ),
					space( ' ' ),
					priceNode( data.max )
				];
				break;

			default:
				nodes = [ priceNode( min ) ];

				if ( badge ) {
					nodes.push( space( ' ' ), textNode( 'ayudawp-discount-badge', data.badge ) );
				}

				nodes = withPrefix( nodes, value( 'prefix_text' ), gap, sample.same );
				break;
		}

		if ( '' !== suffix ) {
			nodes.push( space( ' ' ), textNode( 'ayudawp-suffix', suffix ) );
		}

		return nodes;
	}

	function refresh( data ) {
		var targets = document.querySelectorAll( '.ayudawp-lp-preview-price' );
		var i;

		for ( i = 0; i < targets.length; i++ ) {
			( function ( target ) {
				var sample = CASES[ target.getAttribute( 'data-case' ) ];
				var nodes;
				var n;

				if ( ! sample ) {
					return;
				}

				nodes = build( sample, data );
				target.textContent = '';

				for ( n = 0; n < nodes.length; n++ ) {
					target.appendChild( nodes[ n ] );
				}
			}( targets[ i ] ) );
		}
	}

	function initPreview() {
		var box = document.getElementById( 'ayudawp-lp-preview' );
		var data = ayudawpLowestPrices.preview;
		var form;

		if ( ! box || ! data || ! data.min ) {
			return;
		}

		form = box.closest( 'form' );

		if ( ! form ) {
			return;
		}

		function update() {
			refresh( data );
		}

		form.addEventListener( 'input', update );
		form.addEventListener( 'change', update );

		update();
		box.hidden = false;
	}

	function initReset() {
		var button = document.getElementById( 'ayudawp-lp-reset' );

		if ( ! button ) {
			return;
		}

		button.addEventListener( 'click', function ( event ) {
			if ( ! window.confirm( ayudawpLowestPrices.confirmReset ) ) {
				event.preventDefault();
			}
		} );
	}

	function init() {
		if ( 'undefined' === typeof ayudawpLowestPrices ) {
			return;
		}

		initReset();
		initPreview();
	}

	// The script is enqueued in the footer, so the DOM is usually ready already.
	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
