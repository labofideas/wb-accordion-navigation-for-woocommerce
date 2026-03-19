<?php
/**
 * Uninstall routine.
 *
 * @package WBCOM\WBWAN
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'wbwan_settings' );
delete_option( 'wbwan_version' );
delete_option( 'wbwan_filter_analytics' );

// Clean up cached term transients.
global $wpdb;
$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		$wpdb->esc_like( '_transient_wbwan_terms_' ) . '%',
		$wpdb->esc_like( '_transient_timeout_wbwan_terms_' ) . '%'
	)
);
