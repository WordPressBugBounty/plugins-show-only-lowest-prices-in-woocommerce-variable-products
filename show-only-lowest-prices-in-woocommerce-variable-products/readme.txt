=== Show only lowest prices in variable products for WooCommerce ===
Contributors: fernandot, ayudawp
Tags: woocommerce, variations, variable products, price, lowest price
Requires at least: 5.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 2.2.0
License: GPLv2+
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Show only the lowest price instead of confusing price ranges in variable products, with your own price prefix and suffix text.

== Description ==

Transform your store's pricing display and boost conversions by showing only what matters most to your customers: the lowest available price.

Instead of showing confusing price ranges like "$10 - $50" that can overwhelm and confuse customers, this plugin displays clean, simple pricing that encourages purchases. You decide the price prefix ("From", "Starting at", or whatever you want), the suffix after the price, and whether the price shown is the lowest, the highest or both.

**Key Features:**
* **Pick what to show** - Lowest price ("From $40"), highest price ("Up to $60"), both with text ("From $40 up to $60") or both with a separator ("$40 – $60")
* **Your own price prefix** - Replace "From" with any text you want, with a separate prefix for the highest price so each one translates on its own
* **Your own price suffix** - Text after the amount, for units or recurrences like "/ month" or "per person"
* **Sales stay visible** - The regular price is kept crossed out next to the sale price, so discounts are not lost in shop and archive pages
* **Discount badge** - Optionally show the discount percentage next to the price
* **Out of stock aware** - Leave sold out variations out of the calculation, so the price you advertise is one your customers can actually buy
* **Where it applies** - Everywhere, shop and archives only, or product pages only
* **Smart prefix** - Hide it automatically when every variation costs the same
* **Yours to style and extend** - Custom CSS class for the price, plus a filter to add your own information next to it
* **No performance impact** - Lightweight and efficient, reusing the price data WooCommerce already caches
* **Translation ready** and fully compatible with WooCommerce and WordPress, HPOS included

**Perfect for:**
* Stores with complex variable products
* Fashion and clothing retailers
* Electronics stores with multiple variants
* Any shop wanting cleaner price displays

The plugin automatically detects your WooCommerce installation and starts working immediately. Access the settings through Marketing > Lowest Prices in your admin dashboard.

== Installation ==

1. Go to your WordPress Dashboard > Plugins > Add New
2. Search for 'Show only lowest prices in variable products'
3. Install and activate the plugin
4. Go to Marketing > Lowest Prices to customize settings
5. That's it! Your variable product prices are now clean and conversion-focused

**Manual installation:**
1. Download the plugin from the WordPress repository
2. Upload the plugin folder to `/wp-content/plugins/`
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Configure settings in Marketing > Lowest Prices

== Frequently Asked Questions ==

= Does this plugin work with all WooCommerce themes? =

Yes! The plugin uses WooCommerce's standard price hooks, so it works with any properly coded WooCommerce theme.

= Can I customize the "From" text? =

Absolutely! Go to Marketing > Lowest Prices in your admin dashboard to customize the prefix text, spacing, and display options.

= How do I add a price prefix or a price suffix? =

Both are plain text fields in Marketing > Lowest Prices. The prefix goes before the amount ("From $40", "Starting at $40") and the suffix goes after it ("$40 / month", "$40 per person"). There is a third field for the prefix of the highest price, used in the "Up to $60" and "From $40 up to $60" modes, so each text translates on its own. Leave any of them empty to show nothing.

There is also a checkbox to add a space between the prefix and the price, on by default. It exists because a text field cannot store a trailing space, so writing "From " would be saved as "From". Turn it off for symbol prefixes like "~" or for languages that do not separate words with spaces.

One caveat about the suffix: if what you want is a tax notice like "VAT included", use the WooCommerce price suffix in WooCommerce > Settings > Tax instead. It already does that, with its own tax placeholders, and using both would show two suffixes in a row.

= What happens if all variations have the same price? =

By default, the plugin won't show the "From" prefix when all variations have the same price. You can change this behavior in the settings.

= What if I don't want any prefix text? =

Simply clear the prefix text field and save. No prefix will be displayed before the price.

= Does this affect product pages only or shop pages too? =

By default it works on both shop pages and individual product pages - anywhere WooCommerce displays variable product prices. In Marketing > Lowest Prices you can restrict it to shop and archives only, or to product pages only. Related products and upsells shown inside a product page count as listings.

= What happens with products on sale? =

The regular price is kept crossed out next to the lowest price, the same way WooCommerce does it, so your customers can see there is a discount. You can turn this off in the settings if you would rather show the sale price on its own.

= Can I show "Up to" instead of "From"? =

Yes. In Marketing > Lowest Prices you can choose to show the highest price instead of the lowest, both prices with your own text ("From $40 up to $60"), or both with a separator ("$40 – $60"). Each text has its own field, so they translate independently.

= Is this plugin translation ready? =

Yes! The plugin follows WordPress internationalization standards and translations are loaded automatically from WordPress.org.

= Does it work with WooCommerce HPOS? =

Yes! The plugin includes full compatibility with WooCommerce High-Performance Order Storage.

= Does this plugin implement the EU Omnibus Directive (lowest price in the last 30 days)? =

No. This plugin only changes how variable product prices are displayed. It is a presentational feature: it does not track price history and it does not display the prior price (the lowest price applied during the 30 days before a price reduction) required by Directive (EU) 2019/2161, known as the Omnibus Directive. Despite the similar name of some compliance plugins, this one serves a different purpose.

If your store needs to comply with those rules, use a plugin built for it, such as Omnibus - show the lowest price, WC Price History or FleekCode - Omnibus Price Tracker. They work alongside this plugin without conflict: this one decides which price replaces the WooCommerce range, and the other one adds the lowest price of the last 30 days. When one of them is active you will see a notice in the settings page confirming it.

Two things help here. Products on sale keep their regular price crossed out, so a price reduction stays visible instead of showing only the reduced price. And the `ayudawp_lowest_price_html` filter lets you place the price history message exactly where you want it:

`add_filter( 'ayudawp_lowest_price_html', function ( $html, $product ) {
    return $html . '<span class="my-prior-price">' . my_prior_price( $product ) . '</span>';
}, 10, 2 );`

= Can I add my own information next to the price? =

Yes, with the `ayudawp_lowest_price_html` filter. It receives the final price HTML, the product object and the plugin options.

== Screenshots ==

1. WooCommerce variable product before plugin activation showing confusing price range
2. Clean, simple pricing after plugin activation
3. Settings page with customization options integrated into WooCommerce's Marketing admin menu

== Changelog ==

= 2.2.0 =
* New: Choose what replaces the WooCommerce price range: lowest price, highest price, both with text ("From 40 up to 60") or both with a separator ("40 – 60")
* New: Customizable price prefix for the highest price, with its own field just like the "From" prefix
* New: Price suffix after the amount, for units or recurrences like "/ month" or "per person"
* New: Optional discount percentage badge, styled with the .ayudawp-discount-badge CSS class
* New: Option to leave out of stock variations out of the price calculation, so the shown price is one customers can actually buy
* New: Choose where the change applies: everywhere, shop and archives only, or product pages only
* New: ayudawp_lowest_price_html filter to append extra information to the price, such as the lowest price of the last 30 days tracked by an Omnibus Directive plugin
* New: Notice in the settings page when a price history plugin is active, explaining how both plugins work together
* New: Restore defaults button in the settings page, with a confirmation prompt, to put every option back to its recommended value
* Improved: Settings page split into two sections, General and Price Display
* Improved: Promotional sidebar now shows AyudaWP services
* Improved: The "Add space after prefix" setting now explains when you would want it off, and why the space cannot be typed into the prefix field itself
* Fix: The crossed out regular price is no longer lost on products whose variations are all on sale at the same price. WooCommerce showed it and the plugin was replacing it with the sale price alone
* Fix: Products on sale now show their regular price crossed out next to the lowest price, so the discount is visible in shop and archive pages too
* Fix: Removed a filter on woocommerce_variable_sale_price_html, a hook that no longer exists in WooCommerce
* Tested up to WooCommerce 10.9

For older changelog entries, please check the [changelog.txt](https://plugins.svn.wordpress.org/show-only-lowest-prices-in-woocommerce-variable-products/trunk/changelog.txt) file

== Upgrade Notice ==

= 2.2.0 =
Sale prices now keep their crossed out regular price, which previous versions dropped. New options: show the highest price or both, discount badge, suffix text, ignore out of stock variations and choose where it applies.

== Support ==

Need private support or custom development?

Do you need one-on-one help, priority troubleshooting, or a custom feature, integration, or tweak built specifically for your site? I offer private support and custom development. Just [contact me](mailto:show-only-lowest-prices-in-woocommerce-variable-products@ayudawp.com) and tell me what you need.

Need help or have suggestions?

* [Official website](https://servicios.ayudawp.com)
* [WordPress support forum](https://wordpress.org/support/plugin/show-only-lowest-prices-in-woocommerce-variable-products/)
* [YouTube channel](https://www.youtube.com/AyudaWordPressES)
* [Documentation and tutorials](https://ayudawp.com)

Love the plugin? Please leave us a [5-star review](https://wordpress.org/support/plugin/show-only-lowest-prices-in-woocommerce-variable-products/reviews/#new-post) and help spread the word!

== About AyudaWP.com ==

We are specialists in WordPress security, SEO, AI and performance optimization plugins. We create tools that solve real problems for WordPress site owners while maintaining the highest coding standards and accessibility requirements.