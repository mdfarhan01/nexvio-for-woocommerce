<?php
/**
 * The 12 design layouts of the Category Grid Layouts and Category Slider Layouts widgets.
 *
 * @package Nexvio_For_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Nexvio_Category_Layouts {

	/**
	 * key => title, short description, text (description / button allowed), fixed image shape, dark overlay.
	 */
	public static function all() {
		return array(
			'classic'    => array( __( 'Clean Card', 'nexvio' ), __( 'White card: image on top, name and product count below.', 'nexvio' ), true, false, false ),
			'overlay'    => array( __( 'Bottom Fade', 'nexvio' ), __( 'Full image, name on a soft dark fade at the bottom.', 'nexvio' ), false, false, true ),
			'circle'     => array( __( 'Round Image', 'nexvio' ), __( 'Round image with the name centred below.', 'nexvio' ), false, true, false ),
			'tile'       => array( __( 'Centre Tile', 'nexvio' ), __( 'Full image with the name in the middle on a colour veil.', 'nexvio' ), false, false, true ),
			'pill'       => array( __( 'Floating Pill', 'nexvio' ), __( 'Image with a rounded white label floating over the bottom.', 'nexvio' ), false, false, false ),
			'arch'       => array( __( 'Arch', 'nexvio' ), __( 'Arch-shaped image, centred name, no box.', 'nexvio' ), false, false, false ),
			'horizontal' => array( __( 'Side by Side', 'nexvio' ), __( 'Small image on the left, name and count on the right.', 'nexvio' ), true, true, false ),
			'frame'      => array( __( 'Photo Frame', 'nexvio' ), __( 'Image inside a padded frame with a soft shadow.', 'nexvio' ), true, false, false ),
			'badge'      => array( __( 'Count Badge', 'nexvio' ), __( 'Product count as a badge on the image, name below.', 'nexvio' ), true, false, false ),
			'bar'        => array( __( 'Name Bar', 'nexvio' ), __( 'Image with a solid colour bar: name, count and an arrow.', 'nexvio' ), false, false, false ),
			'line'       => array( __( 'Clean Line', 'nexvio' ), __( 'No box: image, then name and arrow over a thin line.', 'nexvio' ), true, false, false ),
			'editorial'  => array( __( 'Numbered', 'nexvio' ), __( 'Tall image with a big number, spaced capital name.', 'nexvio' ), false, false, false ),
		);
	}

	private static function keys_where( $index ) {
		$out = array();
		foreach ( self::all() as $key => $l ) {
			if ( $l[ $index ] ) {
				$out[] = $key;
			}
		}
		return $out;
	}

	public static function text_keys() {
		return self::keys_where( 2 );
	}

	public static function fixed_shape_keys() {
		return self::keys_where( 3 );
	}

	public static function overlay_keys() {
		return self::keys_where( 4 );
	}

	public static function exists( $key ) {
		return array_key_exists( (string) $key, self::all() );
	}

	public static function options_for_control() {
		$out = array();
		foreach ( self::all() as $key => $l ) {
			$img         = 'assets/img/layouts/cg-' . $key . '.svg';
			$out[ $key ] = array(
				'title' => $l[0],
				'desc'  => $l[1],
				'image' => NEXVIO_URL . $img . '?v=' . Nexvio_Plugin::ver( $img ),
			);
		}
		return $out;
	}

	public static function titles() {
		$out = array();
		foreach ( self::all() as $key => $l ) {
			$out[ $key ] = $l[0];
		}
		return $out;
	}
}
