<?php
/**
 * Product card design layouts for the Product Slider.
 *
 * @package Nexvio_For_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Nexvio_Layouts {

	/**
	 * key => title, text button by default?, short description.
	 */
	public static function all() {
		return array(
			'classic'   => array( __( 'Classic Vault', 'nexvio' ), false, __( 'The original dark card with hexagon background.', 'nexvio' ) ),
			'minimal'   => array( __( 'Minimal Light', 'nexvio' ), false, __( 'Clean white card, soft image frame.', 'nexvio' ) ),
			'overlay'   => array( __( 'Image Overlay', 'nexvio' ), false, __( 'Full image with text on a dark gradient.', 'nexvio' ) ),
			'horizontal'=> array( __( 'Horizontal', 'nexvio' ), false, __( 'Image on the left, details on the right.', 'nexvio' ) ),
			'glass'     => array( __( 'Glass', 'nexvio' ), false, __( 'Frosted glass card for colourful sections.', 'nexvio' ) ),
			'neon'      => array( __( 'Neon Outline', 'nexvio' ), false, __( 'Dark card with a glowing outline.', 'nexvio' ) ),
			'elevated'  => array( __( 'Elevated Shop', 'nexvio' ), true, __( 'Rounded card, soft shadow, full-width button.', 'nexvio' ) ),
			'compact'   => array( __( 'Compact Tiles', 'nexvio' ), false, __( 'Small tiles to show many products at once.', 'nexvio' ) ),
			'editorial' => array( __( 'Editorial', 'nexvio' ), true, __( 'Tall image, magazine style text, no box.', 'nexvio' ) ),
			'pricetag'  => array( __( 'Price Tag', 'nexvio' ), true, __( 'Price badge on the image, centred details.', 'nexvio' ) ),
			'spotlight' => array( __( 'Spotlight', 'nexvio' ), true, __( 'Dark showcase with a glowing round stage.', 'nexvio' ) ),
			'boutique'  => array( __( 'Boutique', 'nexvio' ), false, __( 'Soft image tile, colour swatches, outlined price and rating.', 'nexvio' ) ),
			/* 4th value true: not offered in the design picker; used by the Products Card widget only */
			'simple'    => array( __( 'Simple', 'nexvio' ), true, __( 'Plain card: image on top, title, description, price and a full-width button.', 'nexvio' ), true ),
		);
	}

	public static function options_for_control() {
		$out = array();
		foreach ( self::all() as $key => $l ) {
			if ( ! empty( $l[3] ) ) {
				continue;
			}
			$out[ $key ] = array(
				'title' => $l[0],
				'desc'  => $l[2],
				'image' => NEXVIO_URL . 'assets/img/layouts/' . $key . '.svg?v=' . Nexvio_Plugin::ver( 'assets/img/layouts/' . $key . '.svg' ),
			);
		}
		return $out;
	}

	/** key => title (plain select fallback). */
	public static function titles() {
		$out = array();
		foreach ( self::all() as $key => $l ) {
			if ( ! empty( $l[3] ) ) {
				continue;
			}
			$out[ $key ] = $l[0];
		}
		return $out;
	}

	public static function exists( $key ) {
		return array_key_exists( (string) $key, self::all() );
	}

	public static function text_button_default( $key ) {
		$all = self::all();
		return isset( $all[ $key ] ) ? (bool) $all[ $key ][1] : false;
	}
}
