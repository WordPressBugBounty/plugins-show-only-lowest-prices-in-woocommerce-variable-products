=== Show only lowest prices in variable products for WooCommerce ===
Contributors: fernandot, ayudawp
Tags: woocommerce, variations, variable products, price, lowest price
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 2.0.3
License: GPLv2+
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Clean up your variable product prices by showing only the lowest price instead of confusing price ranges. Now with customizable settings!

== Description ==

Transform your store's pricing display and boost conversions by showing only what matters most to your customers: the lowest available price.

Instead of showing confusing price ranges like "$10 - $50" that can overwhelm and confuse customers, this plugin displays clean, simple pricing that encourages purchases.

**New from version 2.x:**
* **Settings page** - Customize the prefix text, spacing, and display options
* **Smart prefix control** - Choose whether to show "From" when all variations have the same price
* **Custom CSS classes** - Add your own styling with custom CSS classes
* **Better performance** - Optimized code following WordPress standards
* **Enhanced security** - Improved data sanitization and validation
* **HPOS compatibility** - Full support for WooCommerce High-Performance Order Storage

**Key Features:**
* Shows only the lowest price from all product variations
* Customizable prefix text (default: "From")
* Option to hide prefix when all variations have the same price
* Custom CSS class support for advanced styling
* No performance impact - lightweight and efficient
* Translation ready
* Full WooCommerce and WordPress compatibility

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

Absolutely! Go to WooCommerce > Lowest Prices in your admin dashboard to customize the prefix text, spacing, and display options.

= What happens if all variations have the same price? =

By default, the plugin won't show the "From" prefix when all variations have the same price. You can change this behavior in the settings.

= Does this affect product pages only or shop pages too? =

The plugin works on both shop pages and individual product pages - anywhere WooCommerce displays variable product prices.

= Is this plugin translation ready? =

Yes! The plugin includes a .pot file for translations and follows WordPress internationalization standards.

= Does it work with WooCommerce HPOS? =

Yes! The lugin includes full compatibility with WooCommerce High-Performance Order Storage.

== Screenshots ==

1. WooCommerce variable product before plugin activation showing confusing price range
2. Clean, simple pricing after plugin activation
3. Settings page with customization options integrated into WooCommerce's Marketing admin menu

== Changelog ==

= 2.0.3 =
* Improved: Admin menu moved to WooCommerce's Marketing > Lowest Prices for better organization

= 2.0.2 =
* Fixed: Prefix text now properly uses translations when available
* Fixed: Settings form no longer auto-activates checkboxes when saving
* Improved: Better handling of default options on plugin activation
* Improved: More reliable translation loading process
* Removed: Deprecated load_plugin_textdomain() function
* Removed: Domain Path header (no longer needed for WordPress.org plugins)

= 2.0.1 =
* Solved load translations too early notice

= 2.0 =
* **Major update with new features and improvements**
* Added comprehensive settings page with customization options
* New: Customizable prefix text
* New: Option to control prefix display when all prices are the same
* New: Custom CSS class support for advanced styling
* New: Option to hide prefix with CSS while maintaining structure
* Improved: Better code organization following WordPress standards
* Improved: Enhanced security with proper data sanitization
* Improved: Better performance and reduced memory usage
* Improved: Updated branding and admin interface
* Updated: PHP 7.4+ requirement for better security and performance
* Updated: WordPress 5.0+ requirement
* Updated: WooCommerce 4.0+ requirement
* Fixed: Deprecated function warnings in latest PHP versions
* Tested up to WordPress 6.8
* Tested up to WooCommerce 10.0.4

= 1.0.7 =
* Tested up to WordPress 6.7.1
* Tested up to WooCommerce 9.5.1

= 1.0.6 =
* Tested up to WordPress 6.6.1
* Tested up to WooCommerce 9.2.3

= 1.0.5 =
* Tested up to WordPress 6.4

= 1.0.4 =
* HPOS compatibility

= 1.0.3 =
* Prefix "From" not showed if all variations have the same price
* Tested up to WooCommerce 7.5.1
* Tested up to WordPress 6.2

= 1.0.2 =
* Tested up to WooCommerce 7.0.0
* Tested up to WordPress 6.1

= 1.0.1 =
* Solved issue with translations

= 1.0 =
* Code updated to latest WooCommerce functions
* Added the suffix after the min price if it's enabled as text
* Tested up to WooCommerce 6.9.1
* Tested up to WordPress 6.0.2

= 0.9.9 =
* Initial release

== Upgrade Notice ==

= 2.0.3 =
Settings moved to WooCommerce's Marketing menu for better organization 

= 2.0.2 =
Bug fix release! Fixes prefix text translation issues and settings form behavior. Recommended update for all users.

= 2.0 =
Major update! New settings page with customization options, improved performance, and enhanced security. Backup your site before upgrading. Settings will be automatically migrated from the previous version.