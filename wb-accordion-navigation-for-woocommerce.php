<?php
/**
 * Plugin Name: WB Accordion Navigation for WooCommerce
 * Plugin URI:  https://wbcomdesigns.com/
 * Description: Add smart, customizable accordion navigation for WooCommerce categories, tags, attributes, and collections.
 * Version:     1.0.0
 * Author:      Wbcom Designs
 * Author URI:  https://wbcomdesigns.com/
 * Text Domain: wb-accordion-navigation-for-woocommerce
 * License:     GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 6.5
 * Requires PHP: 8.0
 * WC requires at least: 8.0
 * WC tested up to: 10.5.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WBWAN_FILE' ) ) {
	define( 'WBWAN_FILE', __FILE__ );
}

if ( ! defined( 'WBWAN_PATH' ) ) {
	define( 'WBWAN_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'WBWAN_URL' ) ) {
	define( 'WBWAN_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'WBWAN_VERSION' ) ) {
	define( 'WBWAN_VERSION', '1.0.0' );
}

require_once WBWAN_PATH . 'includes/class-plugin.php';

add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( '\\Automattic\\WooCommerce\\Utilities\\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);

add_action(
	'plugins_loaded',
	static function () {
		\WBCOM\WBWAN\Plugin::init();
	}
);

register_activation_hook(
	__FILE__,
	static function () {
		if ( ! class_exists( 'WooCommerce' ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die( esc_html__( 'WB Accordion Navigation for WooCommerce requires WooCommerce to be active.', 'wb-accordion-navigation-for-woocommerce' ) );
		}

		$defaults = \WBCOM\WBWAN\Settings::defaults();
		$existing = get_option( 'wbwan_settings', array() );
		update_option( 'wbwan_settings', wp_parse_args( $existing, $defaults ) );
		update_option( 'wbwan_version', WBWAN_VERSION );
	}
);
