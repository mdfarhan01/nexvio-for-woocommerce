<?php
/**
 * Sends the install status of the plugin (website link + Active / Deactive) to the
 * developer's Google Sheet. It has no settings page and no option anywhere: it only
 * runs when the plugin is activated or deactivated.
 *
 * @package Nexvio_For_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Nexvio_Install_Tracker {

	/** Paste the Google Apps Script web app URL here (see google-apps-script/Code.gs). */
	const ENDPOINT = '';

	/** Must be the same as TOKEN in the Apps Script. */
	const TOKEN = 'nxv_2e384c87d2e55bb64f178bdb';

	public static function activated() {
		self::send( 'Active' );
	}

	public static function deactivated() {
		self::send( 'Deactive' );
	}

	private static function send( $status ) {
		if ( '' === self::ENDPOINT ) {
			return;
		}
		/* fire and forget: never slows down or breaks activation */
		wp_remote_post( self::ENDPOINT, array(
			'timeout'  => 8,
			'blocking' => false,
			'body'     => array(
				'token'  => self::TOKEN,
				'site'   => home_url( '/' ),
				'status' => $status,
			),
		) );
	}
}
