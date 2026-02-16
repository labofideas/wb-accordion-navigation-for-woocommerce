=== WB Accordion Navigation for WooCommerce ===
Contributors: wbcomdesigns
Tags: woocommerce, accordion, categories, navigation, filter
Requires at least: 6.5
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Smart, customizable accordion navigation for WooCommerce categories, tags, attributes, and dynamic collections.

== Description ==

WB Accordion Navigation for WooCommerce helps shoppers find products faster with accordion-driven navigation.

Features:

* Product categories, tags, and attribute taxonomies.
* Dynamic collections (Best Sellers, On Sale, Top Rated, New Arrivals).
* Search within accordion.
* Custom default accordion title from settings.
* Style presets: Minimal, Bold, Glass.
* Live preview in admin settings.
* Import/export settings as JSON.
* Native Gutenberg block: `WB Accordion Navigation for WooCommerce`.
* Auto-expand current taxonomy path.
* Optional mobile off-canvas panel.
* Configurable taxonomy cache TTL from settings.
* Optional shop sidebar auto-injection.
* Optional WordPress menu section.
* Pro-ready extension scaffold via hooks and addon manager.

Shortcode:

`[wb_wc_accordion_navigation]`

Shortcode example with overrides:

`[wb_wc_accordion_navigation title="Shop Filters" taxonomies="product_cat,product_tag" collections="best_sellers,on_sale"]`

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate through the Plugins screen.
3. Go to WooCommerce > Accordion Navigation to configure settings.
4. Add the shortcode where needed or enable auto-injection.

== Developer Notes ==

Extension hooks:

* `wbwan_default_settings`
* `wbwan_available_taxonomies`
* `wbwan_available_collections`
* `wbwan_collection_query_args`
* `wbwan_render_sections`
* `wbwan_render_output`
* `wbwan_register_addons`
* `wbwan_enabled_addons`

Addon scaffold example:

`addons/example-featured-addon.php`

Playwright regression tests:

1. `cd wp-content/plugins/wb-accordion-navigation-for-woocommerce`
2. `npm install`
3. `E2E_BASE_URL=http://wbrbpw.local E2E_USER=wbwan_admin E2E_PASS='Passw0rd!234' npm run test:e2e`
4. `npm run package:zip`

== Changelog ==

= 1.0.0 =
* Initial release.
