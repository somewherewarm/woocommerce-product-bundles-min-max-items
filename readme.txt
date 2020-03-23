=== Product Bundles - Min/Max Items for WooCommerce ===

Contributors: franticpsyx, SomewhereWarm
Tags: woocommerce, product, bundles, bundled, quantity, min, max, item, items, count, restrict, limit
Requires at least: 4.4
Tested up to: 5.3
Requires PHP: 5.6
Stable tag: 1.4.1
WC requires at least: 3.1
WC tested up to: 4.0
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Free mini-extension for WooCommerce Product Bundles that allows you to control the minimum or maximum quantity of bundled products that customers must choose in order to purchase a Product Bundle.


== Description ==

Want to use WooCommerce to sell personalized cases of wine? Boxes of cupcakes? T-shirts by the dozen?

This plugin adds [pick-and-mix functionality](https://docs.woocommerce.com/document/bundles/bundles-use-case-pick-and-mix/) to the official [WooCommerce Product Bundles](https://woocommerce.com/products/product-bundles/?aff=46147&cid=5972457) extension.

Use it to control the minimum or maximum quantity of products that customers must choose in order to purchase a Product Bundle.

**Important**: This plugin requires the official [WooCommerce Product Bundles](https://woocommerce.com/products/product-bundles/?aff=46147&cid=5972457) extension. Before installing this plugin, please ensure that you are running the latest versions of both **WooCommerce** and **WooCommerce Product Bundles**.


== Documentation ==

This plugin adds two new options under **Product Data > Bundled Products**:

* **Items required (≥)**; and
* **Items allowed (≤)**.

Once you have used these options to set a minimum or maximum quantity limit, customers must choose a quantity of products within the specified range to make a purchase.

Want to contribute? Please submit your issue reports and pull requests on [GitHub](https://github.com/somewherewarm/woocommerce-product-bundles-min-max-items).


== Installation ==

This plugin requires the official [WooCommerce Product Bundles](https://woocommerce.com/products/product-bundles/?aff=46147&cid=5972457) extension. Before installing this plugin, please ensure that you are running the latest versions of both **WooCommerce** and **WooCommerce Product Bundles**.


== Screenshots ==

1. A pick-and-mix Product Bundle.
2. Setting the minimum or maximum quantity of products that customers must choose in a Product Bundle.


== Changelog ==

= 1.4.0 =
* Important - Renamed plugin to comply with WordPress.org guidelines.

= 1.3.6 =
* Tweak - Declared support for WP 5.3 and WooCommerce 3.9.

= 1.3.5 =
* Tweak - Updated supported WP/WC versions.

= 1.3.4 =
* Tweak - Removed admin options wrapper div.

= 1.3.3 =
* Tweak - Declare WC 3.5 support.

= 1.3.2 =
* Tweak - Fixed an incorrect gettext string in validation messages.
* Tweak - Added WC 3.3 support.

= 1.3.1 =
* Tweak - Updated plugin headers.
* Tweak - Renamed 'Bundled Products' tab option labels.

= 1.3.0 =
* Fix - Cart validation.
* Tweak - Re-designed validation messages.
* Tweak - Updated validation message strings.

= 1.2.0 =
* Important - Product Bundles v5.5+ required.
* Fix - Product Bundles v5.5 compatibility.

= 1.1.1 =
* Fix - Add-to-cart validation failure when bundle quantity > 1.

= 1.1.0 =
* Fix - WooCommerce v3.0 support.
* Fix - Product Bundles v5.2 support.
* Important - Product Bundles v5.1 support dropped.

= 1.0.6 =
* Fix - Product Bundles v5.0 support.

= 1.0.5 =
* Fix - Load plugin textdomain on init.

= 1.0.4 =
* Fix - Composite Products v3.6 support.
* Fix - Product Bundles v4.14 support. Fix validation notices not displaying on first page load. Requires Product Bundles v4.14.3+.

= 1.0.3 =
* Fix - Composite Products support.

= 1.0.2 =
* Tweak - Bundles with min/max constraints require input: 'Add to cart' button text and behaviour changed.

= 1.0.1 =
* Fix - Accurate 'from:' price calculation based on the defined qty constraints.

= 1.0.0 =
* Initial Release.



== Upgrade Notice ==

= 1.4.0 =
Renamed plugin to comply with WordPress.org guidelines.
