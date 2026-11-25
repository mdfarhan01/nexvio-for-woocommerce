<?php
/**
 * Side cart / add to cart: global settings (Nexvio > Add to Cart).
 *
 * @package Nexvio_For_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Nexvio_Cart_Settings {

	const OPTION = 'nexvio_cart';

	private static $cache = null;

	/**
	 * Schema: group => [ title, icon, help, fields => [ key => [ type, label, default, extra ] ] ].
	 * Types: toggle, select, color, number, text, page, pages, checks.
	 */
	public static function schema() {
		return array(
			'general'  => array(
				__( 'Behaviour', 'nexvio' ),
				'dashicons-cart',
				__( 'With the side cart off, WooCommerce works exactly as before.', 'nexvio' ),
				array(
					'enabled'         => array( 'toggle', __( 'Side cart', 'nexvio' ), 'yes', array( 'help' => __( 'A cart panel that slides in from the right.', 'nexvio' ) ) ),
					'auto_open'       => array( 'toggle', __( 'Open after adding a product', 'nexvio' ), 'yes' ),
					'ajax_single'     => array( 'toggle', __( 'Add without reloading on product pages', 'nexvio' ), 'yes', array( 'help' => __( 'Simple and variable products, with their options.', 'nexvio' ) ) ),
					'count_mode'      => array( 'select', __( 'Cart counter shows', 'nexvio' ), 'quantity', array( 'options' => array( 'quantity' => __( 'All items (quantities added up)', 'nexvio' ), 'lines' => __( 'Different products', 'nexvio' ) ) ) ),
					'coupon'          => array( 'toggle', __( 'Coupon field', 'nexvio' ), 'yes' ),
					'shop_more'       => array( 'toggle', __( '"Shop more" button', 'nexvio' ), 'yes', array( 'help' => __( 'A second button under checkout that takes visitors back to the shop.', 'nexvio' ) ) ),
				),
			),
			'look'     => array(
				__( 'Look', 'nexvio' ),
				'dashicons-art',
				__( 'Two steps: choose Light or Dark, then pick your main colour. The preview shows the result straight away.', 'nexvio' ),
				array(
					'preset'     => array( 'style', __( 'Style', 'nexvio' ), 'light', array( 'options' => array( 'light' => __( 'Light', 'nexvio' ), 'dark' => __( 'Dark', 'nexvio' ) ) ) ),
					'main_color' => array( 'color', __( 'Main colour', 'nexvio' ), '#b08d57', array( 'help' => __( 'Used for the checkout button, the counters and the floating cart. Text on it turns black or white automatically so it stays readable.', 'nexvio' ) ) ),
					'font_heading'        => array( 'select', __( 'Title font', 'nexvio' ), 'default', array( 'options' => self::font_options(), 'help' => __( 'Cart title, empty-cart title and the Total label. "Website" fonts are the global fonts of your Elementor site.', 'nexvio' ) ) ),
					'font_heading_custom' => array( 'font', __( 'Custom title font', 'nexvio' ), '', array( 'show_if' => 'font_heading=custom', 'help' => __( 'Font name as used on your site, for example Playfair Display. The font must already be loaded by your theme or Elementor.', 'nexvio' ) ) ),
					'font_body'           => array( 'select', __( 'Text font', 'nexvio' ), 'default', array( 'options' => self::font_options(), 'help' => __( 'Product names, prices, buttons and all other text. Default follows your theme text font.', 'nexvio' ) ) ),
					'font_body_custom'    => array( 'font', __( 'Custom text font', 'nexvio' ), '', array( 'show_if' => 'font_body=custom' ) ),
					'img_size'            => array( 'select', __( 'Product image size', 'nexvio' ), 'medium', array( 'options' => array( 'small' => __( 'Small', 'nexvio' ), 'medium' => __( 'Medium', 'nexvio' ), 'large' => __( 'Large', 'nexvio' ) ) ) ),
					'title_lines'         => array( 'select', __( 'Product title length', 'nexvio' ), '2', array( 'options' => array( '1' => __( '1 line', 'nexvio' ), '2' => __( '2 lines', 'nexvio' ), '3' => __( '3 lines', 'nexvio' ) ), 'help' => __( 'Longer titles are cut with "…" so every product row keeps the same tidy layout. Customers can still read the full title by hovering it.', 'nexvio' ) ) ),
				),
			),
			'floating' => array(
				__( 'Floating cart', 'nexvio' ),
				'dashicons-sticky',
				__( 'A round cart button in a bottom corner. Its colours follow the style and main colour above.', 'nexvio' ),
				array(
					'floating_global' => array( 'toggle', __( 'Show on every page', 'nexvio' ), 'yes', array( 'help' => __( 'No widget needed. Pages with a Floating Cart widget use the widget instead.', 'nexvio' ) ) ),
					'fl_icon'         => array( 'icons', __( 'Icon', 'nexvio' ), 'basket', array( 'options' => self::icon_options() ) ),
					'fl_icon_url'     => array( 'media', __( 'Your custom icon', 'nexvio' ), '', array( 'show_if' => 'fl_icon=custom', 'help' => __( 'Select your own icon from the Media Library. A square PNG, WebP or SVG works best.', 'nexvio' ) ) ),
					'fl_pulse'        => array( 'toggle', __( 'Pulse animation', 'nexvio' ), 'yes', array( 'help' => __( 'A soft ripple around the button, like a video play button, to draw the eye.', 'nexvio' ) ) ),
					'fl_size'         => array( 'select', __( 'Button size', 'nexvio' ), 'medium', array( 'options' => array( 'small' => __( 'Small', 'nexvio' ), 'medium' => __( 'Medium', 'nexvio' ), 'large' => __( 'Large', 'nexvio' ), 'xlarge' => __( 'Extra large', 'nexvio' ) ) ) ),
					'fl_side'         => array( 'select', __( 'Position', 'nexvio' ), 'right', array( 'options' => array( 'right' => __( 'Bottom right', 'nexvio' ), 'left' => __( 'Bottom left', 'nexvio' ) ) ) ),
					'fl_x'            => array( 'number', __( 'Distance from the side', 'nexvio' ), 24, array( 'min' => 0, 'max' => 300, 'unit' => 'px' ) ),
					'fl_y'            => array( 'number', __( 'Distance from the bottom', 'nexvio' ), 24, array( 'min' => 0, 'max' => 400, 'unit' => 'px' ) ),
					'fl_y_m'          => array( 'number', __( 'Distance from the bottom on phones', 'nexvio' ), 24, array( 'min' => 0, 'max' => 400, 'unit' => 'px', 'help' => __( 'Raise it if a chat or WhatsApp button sits in the same corner.', 'nexvio' ) ) ),
					'fl_devices'      => array( 'checks', __( 'Show on', 'nexvio' ), array( 'desktop', 'tablet', 'mobile' ), array( 'options' => array( 'desktop' => __( 'Computers', 'nexvio' ), 'tablet' => __( 'Tablets', 'nexvio' ), 'mobile' => __( 'Phones', 'nexvio' ) ) ) ),
					'fl_hide_pages'   => array( 'toggle', __( 'Hide on the cart and checkout pages', 'nexvio' ), 'yes' ),
				),
			),
			'texts'    => array(
				__( 'Texts', 'nexvio' ),
				'dashicons-translation',
				__( 'Leave a field empty to use the automatic text: it is translated into your site\'s language (also on multilingual sites). Type your own text only if you want to replace it.', 'nexvio' ),
				array(
					'title'          => array( 'text', __( 'Title', 'nexvio' ), '', array( 'auto' => 'title' ) ),
					'subtitle'       => array( 'text', __( 'Subtitle', 'nexvio' ), '', array( 'auto' => 'subtitle' ) ),
					'checkout_label' => array( 'text', __( 'Checkout button', 'nexvio' ), '', array( 'auto' => 'checkout' ) ),
					'shop_label'     => array( 'text', __( 'Shop more button', 'nexvio' ), '', array( 'auto' => 'shop_more' ) ),
					'empty_title'    => array( 'text', __( 'Empty cart title', 'nexvio' ), '', array( 'auto' => 'empty_title' ) ),
					'empty_text'     => array( 'text', __( 'Empty cart message', 'nexvio' ), '', array( 'auto' => 'empty_text' ) ),
					'continue_label' => array( 'text', __( 'Continue shopping button', 'nexvio' ), '', array( 'auto' => 'continue' ) ),
				),
			),
		);
	}

	/** Floating cart icons: key => label. */
	public static function icon_options() {
		return array(
			'basket' => __( 'Basket', 'nexvio' ),
			'bag'    => __( 'Shopping bag', 'nexvio' ),
			'cart'   => __( 'Cart', 'nexvio' ),
			'tote'   => __( 'Tote bag', 'nexvio' ),
			'handbag' => __( 'Handbag', 'nexvio' ),
			'trolley' => __( 'Trolley', 'nexvio' ),
			'package' => __( 'Package', 'nexvio' ),
			'gift'   => __( 'Gift', 'nexvio' ),
			'store'  => __( 'Shop', 'nexvio' ),
			'custom' => __( 'Custom icon', 'nexvio' ),
		);
	}

	/** Cart fonts: key => label. */
	public static function font_options() {
		return array(
			'default'   => __( 'Default', 'nexvio' ),
			'primary'   => __( 'Website primary font', 'nexvio' ),
			'secondary' => __( 'Website secondary font', 'nexvio' ),
			'text'      => __( 'Website text font', 'nexvio' ),
			'accent'    => __( 'Website accent font', 'nexvio' ),
			'custom'    => __( 'Custom', 'nexvio' ),
		);
	}

	/** Button size => pixels (desktop, phones). */
	public static function fl_sizes() {
		return array(
			'small'  => array( 46, 42 ),
			'medium' => array( 58, 54 ),
			'large'  => array( 68, 62 ),
			'xlarge' => array( 78, 70 ),
		);
	}

	/** Settings that are no longer on the page keep fixed values (older code paths still read them). */
	private static function fixed() {
		return array(
			'open_types'       => array( 'simple', 'variable' ),
			'header_enabled'   => 'yes',
			'floating_enabled' => 'yes',
			'open_header'      => 'yes',
			'open_floating'    => 'yes',
			'close_overlay'    => 'yes',
			'close_esc'        => 'yes',
			'shop_page'        => 0,
			'cart_page'        => 0,
			'checkout_page'    => 0,
			'exclude_pages'    => array(),
			'width'            => 420,
			'width_m'          => 100,
			'show_thumb'       => 'yes',
			'show_variation'   => 'yes',
			'show_unit'        => 'yes',
			'show_qty'         => 'yes',
			'show_line'        => 'yes',
			'show_subtotal'    => 'yes',
			'show_discount'    => 'yes',
			'show_shipping'    => 'yes',
			'show_total'       => 'yes',
			'checkout'         => 'yes',
		);
	}

	/** key => default */
	public static function defaults() {
		$out = array();
		foreach ( self::schema() as $group ) {
			foreach ( $group[3] as $key => $f ) {
				$out[ $key ] = $f[2];
			}
		}
		return $out;
	}

	/** English texts of version 1.6 that were saved as values: treat them as "automatic" so they get translated. */
	private static $legacy_texts = array(
		'title'          => 'Your Shopping Bag',
		'subtitle'       => 'Review your selected items',
		'checkout_label' => 'Proceed to Checkout',
		'empty_title'    => 'Your shopping bag is empty',
		'empty_text'     => 'Discover our latest pieces and find something you love.',
		'continue_label' => 'Continue Shopping',
	);

	public static function all() {
		if ( null === self::$cache ) {
			$saved = get_option( self::OPTION, array() );
			$saved = is_array( $saved ) ? $saved : array();

			/* 1.6 -> 1.7: one main colour instead of many, Light / Dark only */
			if ( empty( $saved['main_color'] ) ) {
				foreach ( array( 'primary', 'accent' ) as $old ) {
					if ( ! empty( $saved[ $old ] ) && sanitize_hex_color( $saved[ $old ] ) ) {
						$saved['main_color'] = $saved[ $old ];
						break;
					}
				}
			}
			if ( isset( $saved['preset'] ) && 'luxury' === $saved['preset'] ) {
				$saved['preset'] = 'light';
			}
			if ( isset( $saved['fl_desktop'] ) && ! isset( $saved['fl_devices'] ) ) {
				$saved['fl_devices'] = array();
				foreach ( array( 'desktop', 'tablet', 'mobile' ) as $d ) {
					if ( 'no' !== $saved[ 'fl_' . $d ] ) {
						$saved['fl_devices'][] = $d;
					}
				}
			}
			foreach ( self::$legacy_texts as $key => $old ) {
				if ( isset( $saved[ $key ] ) && $old === $saved[ $key ] ) {
					$saved[ $key ] = '';
				}
			}

			self::$cache = array_merge( self::defaults(), $saved, self::fixed() );
		}
		return self::$cache;
	}

	/** A text setting, or the automatic translated text when the field is empty. */
	public static function text( $key ) {
		$value = trim( (string) self::get( $key ) );
		if ( '' !== $value ) {
			return $value;
		}
		foreach ( self::schema() as $group ) {
			if ( isset( $group[3][ $key ][3]['auto'] ) ) {
				return Nexvio_Texts::get( $group[3][ $key ][3]['auto'] );
			}
		}
		return '';
	}

	/** Black or white, whichever reads better on the colour. */
	public static function contrast( $hex ) {
		$hex = ltrim( (string) $hex, '#' );
		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		if ( 6 !== strlen( $hex ) ) {
			return '#ffffff';
		}
		list( $r, $g, $b ) = array_map( 'hexdec', str_split( $hex, 2 ) );
		$l = ( 0.299 * $r + 0.587 * $g + 0.114 * $b ) / 255;
		return $l > 0.6 ? '#111111' : '#ffffff';
	}

	public static function get( $key ) {
		$all = self::all();
		return isset( $all[ $key ] ) ? $all[ $key ] : null;
	}

	public static function on( $key ) {
		return 'yes' === self::get( $key );
	}

	public static function flush() {
		self::$cache = null;
	}

	/** Clean a submitted form against the schema. */
	public static function sanitize( $input ) {
		$out = array();
		foreach ( self::schema() as $group ) {
			foreach ( $group[3] as $key => $f ) {
				list( $type, , $default ) = $f;
				$extra = isset( $f[3] ) ? $f[3] : array();
				$raw   = isset( $input[ $key ] ) ? $input[ $key ] : null;
				switch ( $type ) {
					case 'toggle':
						$out[ $key ] = ! empty( $raw ) ? 'yes' : 'no';
						break;
					case 'select':
					case 'icons':
					case 'style':
						$out[ $key ] = is_string( $raw ) && isset( $extra['options'][ $raw ] ) ? $raw : $default;
						break;
					case 'color':
						$c           = is_string( $raw ) ? sanitize_hex_color( trim( $raw ) ) : '';
						$out[ $key ] = $c ? $c : $default;
						break;
					case 'number':
						if ( null === $raw || '' === trim( (string) $raw ) ) {
							$out[ $key ] = '' === $default ? '' : $default;
						} else {
							$n           = (int) $raw;
							$out[ $key ] = max( isset( $extra['min'] ) ? $extra['min'] : 0, min( isset( $extra['max'] ) ? $extra['max'] : 9999, $n ) );
						}
						break;
					case 'url':
					case 'media':
						$out[ $key ] = is_string( $raw ) ? esc_url_raw( trim( $raw ) ) : '';
						break;
					case 'font':
						$out[ $key ] = is_string( $raw ) ? trim( preg_replace( '/[^\p{L}\p{N} ,_\-]/u', '', $raw ) ) : '';
						break;
					case 'page':
						$out[ $key ] = absint( $raw );
						break;
					case 'pages':
						$out[ $key ] = is_array( $raw ) ? array_values( array_filter( array_map( 'absint', $raw ) ) ) : array();
						break;
					case 'checks':
						$allowed     = array_keys( $extra['options'] );
						$out[ $key ] = is_array( $raw ) ? array_values( array_intersect( array_map( 'sanitize_key', $raw ), $allowed ) ) : array();
						break;
					default:
						$out[ $key ] = is_string( $raw ) ? sanitize_text_field( $raw ) : (string) $default;
				}
			}
		}
		return $out;
	}

	/* ------------------------------------------------------------------
	 * URLs
	 * ------------------------------------------------------------------ */

	private static function page_url( $key, $wc_page ) {
		$id = (int) self::get( $key );
		if ( $id && 'publish' === get_post_status( $id ) ) {
			return get_permalink( $id );
		}
		return function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( $wc_page ) : home_url( '/' );
	}

	public static function shop_url() {
		return self::page_url( 'shop_page', 'shop' );
	}

	public static function cart_url() {
		$id = (int) self::get( 'cart_page' );
		return $id ? self::page_url( 'cart_page', 'cart' ) : ( function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/' ) );
	}

	public static function checkout_url() {
		$id = (int) self::get( 'checkout_page' );
		return $id ? self::page_url( 'checkout_page', 'checkout' ) : ( function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/' ) );
	}
}
