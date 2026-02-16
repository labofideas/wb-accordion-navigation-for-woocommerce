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
