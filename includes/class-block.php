<?php
/**
 * Gutenberg block integration.
 *
 * @package WBCOM\WBWAN
 */

namespace WBCOM\WBWAN;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Block {
	/**
	 * Hook registration.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_block' ) );
	}

	/**
	 * Register block type and editor assets.
	 */
	public function register_block(): void {
		$script_path = WBWAN_PATH . 'assets/js/block.js';
		if ( ! file_exists( $script_path ) ) {
			return;
		}

		wp_register_script(
			'wbwan-block-editor',
			WBWAN_URL . 'assets/js/block.js',
			array( 'wp-blocks', 'wp-element', 'wp-i18n', 'wp-components', 'wp-block-editor' ),
			(string) filemtime( $script_path ),
			true
		);

		register_block_type(
			'wbcom/wc-accordion-navigation',
			array(
				'api_version'     => 2,
				'editor_script'   => 'wbwan-block-editor',
				'render_callback' => array( $this, 'render_block' ),
				'attributes'      => array(
					'title'       => array(
						'type'    => 'string',
						'default' => '',
					),
					'taxonomies'  => array(
						'type'    => 'string',
						'default' => '',
					),
					'collections' => array(
						'type'    => 'string',
						'default' => '',
					),
				),
			)
		);
	}

	/**
	 * Dynamic block render callback.
	 *
	 * @param array<string,mixed> $attributes Block attributes.
	 * @return string
	 */
	public function render_block( array $attributes ): string {
		if ( ! shortcode_exists( 'wb_wc_accordion_navigation' ) ) {
			return '';
		}

		$pairs = array();

		if ( ! empty( $attributes['title'] ) ) {
			$pairs[] = 'title="' . esc_attr( sanitize_text_field( (string) $attributes['title'] ) ) . '"';
		}

		if ( ! empty( $attributes['taxonomies'] ) ) {
			$pairs[] = 'taxonomies="' . esc_attr( sanitize_text_field( (string) $attributes['taxonomies'] ) ) . '"';
		}

		if ( ! empty( $attributes['collections'] ) ) {
			$pairs[] = 'collections="' . esc_attr( sanitize_text_field( (string) $attributes['collections'] ) ) . '"';
		}

		$shortcode = '[wb_wc_accordion_navigation' . ( ! empty( $pairs ) ? ' ' . implode( ' ', $pairs ) : '' ) . ']';
		return do_shortcode( $shortcode );
	}
}
