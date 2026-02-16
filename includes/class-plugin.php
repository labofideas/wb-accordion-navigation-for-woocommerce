<?php
/**
 * Main plugin orchestrator.
 *
 * @package WBCOM\WBWAN
 */

namespace WBCOM\WBWAN;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once WBWAN_PATH . 'includes/class-settings.php';
require_once WBWAN_PATH . 'includes/class-frontend.php';
require_once WBWAN_PATH . 'includes/class-addon-manager.php';
require_once WBWAN_PATH . 'includes/class-block.php';

class Plugin {
	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;
	/**
	 * Addon manager.
	 *
	 * @var Addon_Manager
	 */
	private Addon_Manager $addon_manager;

	/**
	 * Bootstrap plugin.
	 *
	 * @return Plugin
	 */
	public static function init(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register hooks.
	 */
	private function __construct() {
		add_action( 'admin_notices', array( $this, 'maybe_show_wc_notice' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( WBWAN_FILE ), array( $this, 'plugin_action_links' ) );

		$this->addon_manager = new Addon_Manager();
		add_action( 'init', array( $this, 'boot_addons' ), 5 );

		new Settings();
		new Frontend();
		new Block();
	}

	/**
	 * Boot registered addons.
	 */
	public function boot_addons(): void {
		$this->addon_manager->boot();
	}

	/**
	 * Show notice when WooCommerce is missing.
	 */
	public function maybe_show_wc_notice(): void {
		if ( class_exists( 'WooCommerce' ) ) {
			return;
		}

		echo '<div class="notice notice-error"><p>' . esc_html__( 'WB Accordion Navigation for WooCommerce requires WooCommerce to be active.', 'wb-accordion-navigation-for-woocommerce' ) . '</p></div>';
	}

	/**
	 * Add action links on plugins page.
	 *
	 * @param array<int,string> $links Existing action links.
	 * @return array<int,string>
	 */
	public function plugin_action_links( array $links ): array {
		$settings_url = admin_url( 'admin.php?page=wbwan-settings' );
		array_unshift(
			$links,
			'<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Settings', 'wb-accordion-navigation-for-woocommerce' ) . '</a>'
		);

		return $links;
	}
}
