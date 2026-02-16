<?php
/**
 * Addon registration scaffold.
 *
 * @package WBCOM\WBWAN
 */

namespace WBCOM\WBWAN;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Addon_Manager {
	/**
	 * Addon callbacks by id.
	 *
	 * @var array<string,callable>
	 */
	private array $addons = array();

	/**
	 * Prevent double boot.
	 *
	 * @var bool
	 */
	private bool $booted = false;

	/**
	 * Register a callback for an addon id.
	 *
	 * @param string   $addon_id Addon id.
	 * @param callable $callback Boot callback.
	 */
	public function register( string $addon_id, callable $callback ): void {
		$addon_id = sanitize_key( $addon_id );
		if ( '' === $addon_id ) {
			return;
		}

		$this->addons[ $addon_id ] = $callback;
	}

	/**
	 * Boot enabled addons.
	 */
	public function boot(): void {
		if ( $this->booted ) {
			return;
		}
		$this->booted = true;

		/**
		 * Register addon boot callbacks.
		 *
		 * Example:
		 * add_action(
		 *   'wbwan_register_addons',
		 *   static function( \WBCOM\WBWAN\Addon_Manager $manager ): void {
		 *     $manager->register( 'my-addon', static function(): void {
		 *       // Add filters/actions.
		 *     } );
		 *   }
		 * );
		 */
		do_action( 'wbwan_register_addons', $this );

		$enabled = apply_filters( 'wbwan_enabled_addons', array_keys( $this->addons ) );
		if ( ! is_array( $enabled ) ) {
			$enabled = array();
		}

		foreach ( $enabled as $addon_id ) {
			$key = sanitize_key( (string) $addon_id );
			if ( isset( $this->addons[ $key ] ) ) {
				call_user_func( $this->addons[ $key ] );
			}
		}
	}
}
