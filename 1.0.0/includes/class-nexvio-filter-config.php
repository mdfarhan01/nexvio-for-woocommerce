<?php
/**
 * Filter fields chosen on Nexvio > Filters.
 * Used by every Product Filter widget whose "Fields" come from the Filters page.
 *
 * @package Nexvio_For_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Nexvio_Filter_Config {

	const OPTION = 'nexvio_filters';

	/** Field types: label, icon, one per filter?, looks (display => label). */
	public static function types() {
		$values = array(
			'checkbox' => __( 'Checkboxes (several)', 'nexvio' ),
			'chips'    => __( 'Buttons (several)', 'nexvio' ),
			'radio'    => __( 'Radio (one choice)', 'nexvio' ),
			'select'   => __( 'Dropdown (one choice)', 'nexvio' ),
		);
		return array(
			'search'    => array( __( 'Search', 'nexvio' ), 'dashicons-search', true, array(), __( 'Search by product name while typing.', 'nexvio' ) ),
			'price'     => array( __( 'Price', 'nexvio' ), 'dashicons-money-alt', true, array( 'slider_boxes' => __( 'Slider + min / max boxes', 'nexvio' ), 'slider' => __( 'Slider only', 'nexvio' ), 'boxes' => __( 'Min / max boxes only', 'nexvio' ) ), __( 'A price range from the cheapest to the most expensive product.', 'nexvio' ) ),
			'category'  => array( __( 'Category', 'nexvio' ), 'dashicons-category', true, array( 'radio' => __( 'List (one category)', 'nexvio' ), 'checkbox' => __( 'Checkboxes (several)', 'nexvio' ), 'select' => __( 'Dropdown', 'nexvio' ), 'chips' => __( 'Buttons (several)', 'nexvio' ) ), __( 'Categories, sub-categories open with +.', 'nexvio' ) ),
			'attribute' => array( __( 'Attribute', 'nexvio' ), 'dashicons-tag', false, array( 'auto' => __( 'Automatic (images, colours or sizes)', 'nexvio' ), 'color' => __( 'Colour circles', 'nexvio' ), 'image' => __( 'Image circles', 'nexvio' ), 'size' => __( 'Size buttons', 'nexvio' ) ) + $values, __( 'Colour, size, material… any product attribute.', 'nexvio' ) ),
			'tag'       => array( __( 'Tags', 'nexvio' ), 'dashicons-tagcloud', true, $values, __( 'Product tags.', 'nexvio' ) ),
			'stock'     => array( __( 'Availability', 'nexvio' ), 'dashicons-archive', true, array( 'checkbox' => $values['checkbox'], 'chips' => $values['chips'], 'radio' => $values['radio'] ), __( 'In stock, pre-order.', 'nexvio' ) ),
			'sale'      => array( __( 'On sale', 'nexvio' ), 'dashicons-megaphone', true, array(), __( 'Only products on sale.', 'nexvio' ) ),
			'featured'  => array( __( 'Featured', 'nexvio' ), 'dashicons-star-filled', true, array(), __( 'Only featured products.', 'nexvio' ) ),
			'rating'    => array( __( 'Rating', 'nexvio' ), 'dashicons-star-half', true, array(), __( '4 stars & up, 3 stars & up…', 'nexvio' ) ),
			'sort'      => array( __( 'Sort by', 'nexvio' ), 'dashicons-sort', true, array( 'select' => $values['select'], 'radio' => $values['radio'], 'chips' => __( 'Buttons (one choice)', 'nexvio' ) ), __( 'Shop order: popularity, newest, price…', 'nexvio' ) ),
			'perpage'   => array( __( 'Products per page', 'nexvio' ), 'dashicons-grid-view', true, array( 'chips' => __( 'Buttons (one choice)', 'nexvio' ), 'select' => $values['select'], 'radio' => $values['radio'] ), __( 'Show 12, 24, 48 products…', 'nexvio' ) ),
		);
	}

	/** Same look as before 1.8: Search, Price, Category. */
	public static function defaults() {
		return array(
			array( 'type' => 'search', 'label' => __( 'Search', 'nexvio' ), 'open' => true, 'placeholder' => '' ),
			array( 'type' => 'price', 'label' => __( 'Price', 'nexvio' ), 'open' => true, 'display' => 'slider_boxes' ),
			array( 'type' => 'category', 'label' => __( 'Category', 'nexvio' ), 'open' => true, 'display' => 'radio', 'counts' => true, 'hide_empty' => true, 'collapse' => true, 'all' => true, 'limit' => 10 ),
		);
	}

	const STYLE_OPTION = 'nexvio_filter_style';

	/** Light / Dark + main colour of every Product Filter (a widget's own Style colours still win). */
	public static function style() {
		$saved = get_option( self::STYLE_OPTION, array() );
		$saved = is_array( $saved ) ? $saved : array();
		$main  = isset( $saved['main_color'] ) ? sanitize_hex_color( $saved['main_color'] ) : '';
		return array(
			'preset'     => isset( $saved['preset'] ) && 'dark' === $saved['preset'] ? 'dark' : 'light',
			'main_color' => $main ? $main : '#3d8bff',
		);
	}

	public static function sanitize_style( $in ) {
		$in   = is_array( $in ) ? $in : array();
		$main = isset( $in['main_color'] ) ? sanitize_hex_color( trim( (string) $in['main_color'] ) ) : '';
		return array(
			'preset'     => isset( $in['preset'] ) && 'dark' === $in['preset'] ? 'dark' : 'light',
			'main_color' => $main ? $main : '#3d8bff',
		);
	}

	/** CSS for the main colour (accent of checkboxes, slider, buttons, chips). */
	public static function style_css() {
		$st   = self::style();
		$m    = $st['main_color'];
		$text = class_exists( 'Nexvio_Cart_Settings' ) ? Nexvio_Cart_Settings::contrast( $m ) : '#ffffff';
		return '.nexvio-pf{--nexvio-pf-check:' . $m . ';--nexvio-pf-fill:' . $m . ';--nexvio-pf-focus:' . $m . ';--nexvio-pf-handle-b:' . $m . ';--nexvio-pf-chip-border-a:' . $m
			. ';--nexvio-pf-chip-bg-a:color-mix(in srgb,' . $m . ' 16%,transparent);--nexvio-pf-btn:' . $m . ';--nexvio-pf-btn-h:color-mix(in srgb,' . $m . ' 85%,#fff);--nexvio-pf-btn-t:' . $text . '}';
	}

	public static function fields() {
		$saved = get_option( self::OPTION, null );
		return is_array( $saved ) ? $saved : self::defaults();
	}

	/** Product attributes: taxonomy => [ label, guessed look ]. */
	public static function attributes() {
		$out = array();
		if ( function_exists( 'wc_get_attribute_taxonomies' ) ) {
			foreach ( wc_get_attribute_taxonomies() as $a ) {
				$out[ 'pa_' . $a->attribute_name ] = array( $a->attribute_label, self::guess( $a->attribute_name . ' ' . $a->attribute_label ) );
			}
		}
		return $out;
	}

	/** "color" for colour attributes, "size" for sizes, "" otherwise. */
	public static function guess( $text ) {
		$t = strtolower( remove_accents( (string) $text ) );
		if ( preg_match( '/\b(colou?rs?|couleurs?|farben?|colori?|kleur(en)?|cor(es)?)\b/', $t ) ) {
			return 'color';
		}
		if ( preg_match( '/\b(sizes?|tailles?|grosse|groesse|tallas?|taglie|taglia|maat|maten|tamanhos?|pointures?|sz)\b/', $t ) || false !== strpos( $t, 'größe' ) ) {
			return 'size';
		}
		return '';
	}

	/** Clean the list sent by the Filters page. */
	public static function sanitize( $list ) {
		$types = self::types();
		$attrs = self::attributes();
		$out   = array();
		$seen  = array();
		foreach ( (array) $list as $f ) {
			if ( ! is_array( $f ) || empty( $f['type'] ) || ! isset( $types[ $f['type'] ] ) ) {
				continue;
			}
			$type = $f['type'];
			$key  = 'attribute' === $type ? 'attribute:' . ( isset( $f['taxonomy'] ) ? $f['taxonomy'] : '' ) : $type;
			if ( isset( $seen[ $key ] ) ) {
				continue; /* each field once */
			}
			if ( 'attribute' === $type && ( empty( $f['taxonomy'] ) || ! isset( $attrs[ $f['taxonomy'] ] ) ) ) {
				continue;
			}
			$seen[ $key ] = true;
			$looks        = $types[ $type ][3];
			$clean        = array(
				'type'  => $type,
				'label' => isset( $f['label'] ) ? sanitize_text_field( $f['label'] ) : '',
				'open'  => ! empty( $f['open'] ),
			);
			if ( $looks ) {
				$clean['display'] = isset( $f['display'] ) && isset( $looks[ $f['display'] ] ) ? $f['display'] : array_key_first( $looks );
			}
			switch ( $type ) {
				case 'search':
					$clean['placeholder'] = isset( $f['placeholder'] ) ? sanitize_text_field( $f['placeholder'] ) : '';
					break;
				case 'category':
					$clean['counts']     = ! empty( $f['counts'] );
					$clean['hide_empty'] = ! empty( $f['hide_empty'] );
					$clean['collapse']   = ! empty( $f['collapse'] );
					$clean['all']        = ! empty( $f['all'] );
					$clean['limit']      = max( 1, min( 100, isset( $f['limit'] ) ? (int) $f['limit'] : 10 ) );
					break;
				case 'attribute':
				case 'tag':
					if ( 'attribute' === $type ) {
						$clean['taxonomy'] = $f['taxonomy'];
					}
					$clean['counts']     = ! empty( $f['counts'] );
					$clean['hide_empty'] = ! empty( $f['hide_empty'] );
					$clean['logic']      = isset( $f['logic'] ) && 'all' === $f['logic'] ? 'all' : 'any';
					$clean['limit']      = max( 1, min( 100, isset( $f['limit'] ) ? (int) $f['limit'] : 10 ) );
					break;
				case 'stock':
					$clean['show_out'] = ! empty( $f['show_out'] );
					break;
				case 'perpage':
					$nums = array();
					foreach ( preg_split( '/[^0-9]+/', isset( $f['values'] ) ? (string) $f['values'] : '' ) as $n ) {
						$n = (int) $n;
						if ( $n >= 1 && $n <= 100 && ! in_array( $n, $nums, true ) ) {
							$nums[] = $n;
						}
					}
					sort( $nums );
					$clean['values'] = $nums ? implode( ',', array_slice( $nums, 0, 8 ) ) : '12,24,48';
					break;
				case 'sale':
				case 'featured':
					$clean['text'] = isset( $f['text'] ) ? sanitize_text_field( $f['text'] ) : '';
					break;
			}
			$out[] = $clean;
		}
		return $out;
	}
}
