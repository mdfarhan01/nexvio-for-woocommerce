<?php
/**
 * Product card design layouts (shared designs and the Products Slider Layouts designs).
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
			/* 5th value 'slider': the designs of the Products Slider Layouts widget (not offered in the other widgets) */
			'sl-showcase' => array( __( 'Showcase', 'nexvio' ), false, __( 'Tall portrait image on a soft card, price and round button below.', 'nexvio' ), false, 'slider' ),
			'sl-poster' => array( __( 'Poster', 'nexvio' ), false, __( 'Full tall image with the details on a dark fade at the bottom.', 'nexvio' ), false, 'slider' ),
			'sl-pill' => array( __( 'Pill Cards', 'nexvio' ), true, __( 'Very round pastel cards with a full-width pill button.', 'nexvio' ), false, 'slider' ),
			'sl-landscape' => array( __( 'Landscape', 'nexvio' ), false, __( 'Wide image on top, details with the price and button side by side.', 'nexvio' ), false, 'slider' ),
			'sl-midnight' => array( __( 'Midnight Gold', 'nexvio' ), false, __( 'Dark card with gold accents.', 'nexvio' ), false, 'slider' ),
			'sl-frame' => array( __( 'Photo Frame', 'nexvio' ), false, __( 'White frame around the image, like a printed photo.', 'nexvio' ), false, 'slider' ),
			'sl-aurora' => array( __( 'Aurora', 'nexvio' ), true, __( 'Violet to blue gradient card with white text.', 'nexvio' ), false, 'slider' ),
			'sl-line' => array( __( 'Clean Line', 'nexvio' ), false, __( 'No box: square image and a thin line, centred text.', 'nexvio' ), false, 'slider' ),
			'sl-arch' => array( __( 'Arch', 'nexvio' ), false, __( 'Arch-shaped image, centred details, soft colours.', 'nexvio' ), false, 'slider' ),
			'sl-circle' => array( __( 'Circle Stage', 'nexvio' ), true, __( 'Round image on a colour stage, centred details.', 'nexvio' ), false, 'slider' ),
			'sl-accent' => array( __( 'Accent Bar', 'nexvio' ), false, __( 'White card with a bold colour bar on the left.', 'nexvio' ), false, 'slider' ),
			'sl-sand' => array( __( 'Sand Studio', 'nexvio' ), true, __( 'Warm sand card with a serif title and a rounded button.', 'nexvio' ), false, 'slider' ),
			/* 4th value true: not offered in the design picker; used by the Products Card widget only */
			'simple'    => array( __( 'Simple', 'nexvio' ), true, __( 'Plain card: image on top, title, description, price and a full-width button.', 'nexvio' ), true ),
		);
	}

	/** Is this design offered in the picker of the given group ('' = the shared designs, 'slider' = the slider designs)? */
	private static function offered( $l, $group ) {
		if ( ! empty( $l[3] ) ) {
			return false;
		}
		$own = isset( $l[4] ) ? $l[4] : '';
		return $own === $group;
	}

	public static function options_for_control( $group = '' ) {
		$out = array();
		foreach ( self::all() as $key => $l ) {
			if ( ! self::offered( $l, $group ) ) {
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
	public static function titles( $group = '' ) {
		$out = array();
		foreach ( self::all() as $key => $l ) {
			if ( ! self::offered( $l, $group ) ) {
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
