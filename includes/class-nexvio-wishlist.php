<?php
/**
 * Built-in wishlist. No other plugin needed.
 *
 * - Logged-in customers: saved in user meta (kept across devices).
 * - Visitors: saved in a cookie; merged into the account when they log in.
 *
 * @package Nexvio_For_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Nexvio_Wishlist {

	const META   = '_nexvio_wishlist';
	const COOKIE = 'nexvio_wishlist';
	const MAX    = 300;

	public static function init() {
		add_action( 'wp_ajax_nexvio_wishlist_toggle', array( __CLASS__, 'ajax_toggle' ) );
		add_action( 'wp_ajax_nopriv_nexvio_wishlist_toggle', array( __CLASS__, 'ajax_toggle' ) );
		add_action( 'wp_ajax_nexvio_wishlist_count', array( __CLASS__, 'ajax_count' ) );
		add_action( 'wp_ajax_nopriv_nexvio_wishlist_count', array( __CLASS__, 'ajax_count' ) );
		add_action( 'wp_login', array( __CLASS__, 'merge_on_login' ), 10, 2 );
		add_shortcode( 'nexvio_wishlist_count', array( __CLASS__, 'shortcode_count' ) );
	}

	/* ------------------------------------------------------------------
	 * Settings
	 * ------------------------------------------------------------------ */

	public static function guests_allowed() {
		return 'no' !== get_option( 'nexvio_wishlist_guests', 'yes' );
	}

	public static function cookie_days() {
		return max( 1, (int) get_option( 'nexvio_wishlist_days', 60 ) );
	}

	/** URL of the "My favorites" page. */
	public static function url() {
		$page = (int) get_option( 'nexvio_wishlist_page', 0 );
		if ( $page && 'publish' === get_post_status( $page ) ) {
			return get_permalink( $page );
		}
		return '';
	}

	/** Kept for compatibility with widgets written for the old YITH bridge. */
	public static function active() {
		return true;
	}

	/* ------------------------------------------------------------------
	 * Storage: array( product_id => timestamp )
	 * ------------------------------------------------------------------ */

	private static function read_cookie() {
		if ( empty( $_COOKIE[ self::COOKIE ] ) ) {
			return array();
		}
		$raw  = sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE ] ) );
		$list = array();
		foreach ( explode( ',', $raw ) as $pair ) {
			$parts = explode( ':', $pair );
			$id    = absint( $parts[0] );
			if ( $id ) {
				$list[ $id ] = isset( $parts[1] ) ? absint( $parts[1] ) : 0;
			}
		}
		return $list;
	}

	private static function write_cookie( $list ) {
		$pairs = array();
		foreach ( $list as $id => $time ) {
			$pairs[] = (int) $id . ':' . (int) $time;
		}
		$value   = implode( ',', $pairs );
		$expires = $value ? time() + self::cookie_days() * DAY_IN_SECONDS : time() - HOUR_IN_SECONDS;
		if ( ! headers_sent() ) {
			setcookie( self::COOKIE, $value, array(
				'expires'  => $expires,
				'path'     => COOKIEPATH ? COOKIEPATH : '/',
				'domain'   => COOKIE_DOMAIN,
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			) );
		}
		$_COOKIE[ self::COOKIE ] = $value;
	}

	public static function get_list() {
		if ( is_user_logged_in() ) {
			$list = get_user_meta( get_current_user_id(), self::META, true );
			return is_array( $list ) ? $list : array();
		}
		return self::guests_allowed() ? self::read_cookie() : array();
	}

	private static function save_list( $list ) {
		/* newest first, limited size */
		arsort( $list );
		$list = array_slice( $list, 0, self::MAX, true );
		if ( is_user_logged_in() ) {
			update_user_meta( get_current_user_id(), self::META, $list );
		} else {
			self::write_cookie( $list );
		}
	}

	/* ------------------------------------------------------------------
	 * Public API
	 * ------------------------------------------------------------------ */

	/** Product IDs, newest first. */
	public static function ids() {
		$list = self::get_list();
		arsort( $list );
		return array_map( 'intval', array_keys( $list ) );
	}

	public static function has( $product_id ) {
		$list = self::get_list();
		return isset( $list[ (int) $product_id ] );
	}

	public static function count() {
		return count( self::get_list() );
	}

	public static function add( $product_id ) {
		$list                       = self::get_list();
		$list[ (int) $product_id ] = time();
		self::save_list( $list );
	}

	public static function remove( $product_id ) {
		$list = self::get_list();
		unset( $list[ (int) $product_id ] );
		self::save_list( $list );
	}

	/** Visitor logs in: keep what they added before logging in. */
	public static function merge_on_login( $login, $user ) {
		$guest = self::read_cookie();
		if ( ! $guest || ! $user instanceof WP_User ) {
			return;
		}
		$saved = get_user_meta( $user->ID, self::META, true );
		$saved = is_array( $saved ) ? $saved : array();
		foreach ( $guest as $id => $time ) {
			if ( ! isset( $saved[ $id ] ) ) {
				$saved[ $id ] = $time;
			}
		}
		arsort( $saved );
		update_user_meta( $user->ID, self::META, array_slice( $saved, 0, self::MAX, true ) );
		self::write_cookie( array() );
	}

	/* ------------------------------------------------------------------
	 * AJAX
	 * ------------------------------------------------------------------ */

	/**
	 * Does the browser say this request was sent from another site?
	 *
	 * Uses Fetch Metadata (Sec-Fetch-Site, which page scripts cannot set) and, for browsers without it,
	 * the Origin header. A request with neither header is not a browser cross-site request, so it is allowed.
	 */
	private static function is_cross_site_request() {
		$fetch_site = isset( $_SERVER['HTTP_SEC_FETCH_SITE'] ) ? strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_SEC_FETCH_SITE'] ) ) ) : '';
		if ( '' !== $fetch_site ) {
			return 'cross-site' === $fetch_site;
		}

		$origin = isset( $_SERVER['HTTP_ORIGIN'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_ORIGIN'] ) ) : '';
		if ( '' === $origin ) {
			return false;
		}
		$host = wp_parse_url( $origin, PHP_URL_HOST );
		if ( ! $host ) {
			return true; /* "null" or malformed origin */
		}
		$own = array_filter( array( wp_parse_url( home_url(), PHP_URL_HOST ), wp_parse_url( site_url(), PHP_URL_HOST ) ) );
		return ! in_array( strtolower( $host ), array_map( 'strtolower', $own ), true );
	}

	public static function ajax_toggle() {
		nocache_headers();

		/* Logged-in users need a valid nonce. A guest has no secret of their own to tie a nonce to (every
		   guest would get the same one, and WooCommerce changes it when its session starts), so a nonce would
		   not stop a cross-site form. Guests are protected by refusing requests the browser says come from
		   another site, plus the SameSite=Lax cookie. */
		if ( is_user_logged_in() ) {
			if ( ! check_ajax_referer( 'nexvio_wishlist', 'nonce', false ) ) {
				wp_send_json_error( array( 'reload' => true ), 403 );
			}
		} elseif ( self::is_cross_site_request() ) {
			wp_send_json_error( array( 'message' => 'cross_site' ), 403 );
		}

		$product_id = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- logged-in: nonce checked above; guest: cross-site requests refused above.
		$product    = $product_id && function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : null;
		if ( ! $product || 'publish' !== $product->get_status() ) {
			wp_send_json_error( array( 'message' => 'invalid_product' ), 400 );
		}

		if ( ! is_user_logged_in() && ! self::guests_allowed() ) {
			$login = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : wp_login_url();
			wp_send_json_error( array( 'login' => $login ), 401 );
		}

		if ( self::has( $product_id ) ) {
			self::remove( $product_id );
			$in = false;
		} else {
			self::add( $product_id );
			$in = true;
		}

		if ( $in ) {
			do_action( 'nexvio_wishlist_added', $product_id );
		} else {
			do_action( 'nexvio_wishlist_removed', $product_id );
		}

		wp_send_json_success( array(
			'product_id' => $product_id,
			'in'         => $in,
			'count'      => self::count(),
			'url'        => self::url(),
		) );
	}

	public static function ajax_count() {
		nocache_headers();
		wp_send_json_success( array( 'count' => self::count() ) );
	}

	public static function shortcode_count() {
		return '<span class="nexvio-wishlist-count">' . (int) self::count() . '</span>';
	}
}
