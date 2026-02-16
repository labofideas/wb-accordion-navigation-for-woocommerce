<?php
/**
 * Example addon scaffold for WBWAN.
 *
 * Copy this file into a custom plugin/mu-plugin and adjust as needed.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wbwan_register_addons',
	static function ( \WBCOM\WBWAN\Addon_Manager $manager ): void {
		$manager->register(
			'featured-collection',
			static function (): void {
				add_filter(
					'wbwan_available_collections',
					static function ( array $collections ): array {
						$collections['featured'] = __( 'Featured', 'wb-accordion-navigation-for-woocommerce' );
						return $collections;
					}
				);

				add_filter(
					'wbwan_collection_query_args',
					static function ( array $args, string $collection ): array {
						if ( 'featured' !== $collection ) {
							return $args;
						}

						return array(
							// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Example addon shows taxonomy-based featured collection.
							'tax_query' => array(
								array(
									'taxonomy' => 'product_visibility',
									'field'    => 'name',
									'terms'    => array( 'featured' ),
								),
							),
						);
					},
					10,
					2
				);
			}
		);
	}
);

/**
 * Enable only this addon in this example.
 */
add_filter(
	'wbwan_enabled_addons',
	static function ( array $enabled ): array {
		$enabled[] = 'featured-collection';
		return array_unique( array_map( 'sanitize_key', $enabled ) );
	}
);
