<?php
/**
 * Applies the NEXVIO filter / search parameters to the main WooCommerce product query
 * (shop page, category / tag / attribute archives).
 *
 * URL parameters:
 *   nexvio_q=text                    product title contains "text"
 *   min_price / max_price         handled natively by WooCommerce
 *   nexvio_tax_{taxonomy}=a,b        products in term slugs a or b (attributes pa_*, product_tag, any product taxonomy)
 *   nexvio_tax_{taxonomy}_all=1      products in ALL the given terms instead of any
 *   nexvio_stock=instock,onbackorder stock status
 *   nexvio_sale=1                    on sale only
 *   nexvio_featured=1                featured only
 *   nexvio_rating=4                  average rating of 4 stars or more
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Nexvio_Filter_Query {

	public static function init() {
		add_action( 'woocommerce_product_query', array( __CLASS__, 'apply' ), 20 );
		add_filter( 'posts_where', array( __CLASS__, 'title_where' ), 10, 2 );
		add_filter( 'query_vars', array( __CLASS__, 'query_vars' ) );
		add_action( 'woocommerce_update_product', array( __CLASS__, 'flush_bounds' ) );
		add_action( 'woocommerce_new_product', array( __CLASS__, 'flush_bounds' ) );
	}

	public static function query_vars( $vars ) {
		$vars[] = 'nexvio_q';
		return $vars;
	}

	/** Read a GET parameter as a clean string. */
	public static function get( $key ) {
		if ( ! isset( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return '';
		}
		if ( is_array( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return '';
		}
		return trim( sanitize_text_field( wp_unslash( $_GET[ $key ] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
	}

	/** Search text (nexvio_q), max 100 characters. */
	public static function search_term() {
		$t = self::get( 'nexvio_q' );
		return function_exists( 'mb_substr' ) ? mb_substr( $t, 0, 100 ) : substr( $t, 0, 100 );
	}

	/** List of slugs from a comma separated parameter. */
	public static function slugs( $key ) {
		$raw = self::get( $key );
		if ( '' === $raw ) {
			return array();
		}
		return array_values( array_filter( array_map( 'sanitize_title', explode( ',', $raw ) ) ) );
	}

	public static function apply( $q ) {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}

		/* title search */
		$term = self::search_term();
		if ( '' !== $term ) {
			$q->set( 'nexvio_title_search', $term );
		}

		$tax_query  = $q->get( 'tax_query' );
		$tax_query  = is_array( $tax_query ) ? $tax_query : array();
		$meta_query = $q->get( 'meta_query' );
		$meta_query = is_array( $meta_query ) ? $meta_query : array();
		$changed    = false;

		/* taxonomies: nexvio_tax_{taxonomy} */
		foreach ( array_keys( $_GET ) as $key ) { // phpcs:ignore WordPress.Security.NonceVerification
			$key = (string) $key;
			if ( 0 !== strpos( $key, 'nexvio_tax_' ) || '_all' === substr( $key, -4 ) ) {
				continue;
			}
			$taxonomy = sanitize_key( substr( $key, 8 ) );
			if ( ! taxonomy_exists( $taxonomy ) || ! is_object_in_taxonomy( 'product', $taxonomy ) ) {
				continue;
			}
			$terms = self::slugs( $key );
			if ( ! $terms ) {
				continue;
			}
			$tax_query[] = array(
				'taxonomy'         => $taxonomy,
				'field'            => 'slug',
				'terms'            => $terms,
				'operator'         => '1' === self::get( $key . '_all' ) ? 'AND' : 'IN',
				'include_children' => true,
			);
			$changed = true;
		}

		/* featured */
		if ( '1' === self::get( 'nexvio_featured' ) ) {
			$tax_query[] = array(
				'taxonomy' => 'product_visibility',
				'field'    => 'name',
				'terms'    => array( 'featured' ),
				'operator' => 'IN',
			);
			$changed = true;
		}

		/* stock status */
		$stock = array_intersect( self::slugs( 'nexvio_stock' ), array( 'instock', 'outofstock', 'onbackorder' ) );
		if ( $stock ) {
			$meta_query[] = array(
				'key'     => '_stock_status',
				'value'   => array_values( $stock ),
				'compare' => 'IN',
			);
			$q->set( 'meta_query', $meta_query );
		}

		/* rating: average of at least N stars */
		$rating = (int) self::get( 'nexvio_rating' );
		if ( $rating >= 1 && $rating <= 5 ) {
			$meta_query   = $q->get( 'meta_query' );
			$meta_query   = is_array( $meta_query ) ? $meta_query : array();
			$meta_query[] = array(
				'key'     => '_wc_average_rating',
				'value'   => $rating,
				'compare' => '>=',
				'type'    => 'DECIMAL(3,2)',
			);
			$q->set( 'meta_query', $meta_query );
		}

		/* on sale */
		if ( '1' === self::get( 'nexvio_sale' ) && function_exists( 'wc_get_product_ids_on_sale' ) ) {
			$sale    = array_map( 'intval', wc_get_product_ids_on_sale() );
			$current = (array) $q->get( 'post__in' );
			$current = array_filter( array_map( 'intval', $current ) );
			$ids     = $current ? array_values( array_intersect( $current, $sale ) ) : $sale;
			$q->set( 'post__in', $ids ? $ids : array( 0 ) );
		}

		if ( $changed ) {
			if ( ! isset( $tax_query['relation'] ) ) {
				$tax_query['relation'] = 'AND';
			}
			$q->set( 'tax_query', $tax_query );
		}
	}

	/** Title-only search, added to the main product query. */
	public static function title_where( $where, $query ) {
		$term = $query->get( 'nexvio_title_search' );
		if ( ! $term ) {
			return $where;
		}
		global $wpdb;
		$words = preg_split( '/\s+/', $term, -1, PREG_SPLIT_NO_EMPTY );
		foreach ( array_slice( $words, 0, 8 ) as $w ) {
			$where .= $wpdb->prepare( " AND {$wpdb->posts}.post_title LIKE %s", '%' . $wpdb->esc_like( $w ) . '%' );
		}
		return $where;
	}

	/** Lowest / highest product price in the shop (WooCommerce lookup table). */
	public static function price_bounds() {
		$cached = get_transient( 'nexvio_price_bounds' );
		if ( is_array( $cached ) ) {
			return $cached;
		}
		global $wpdb;
		$row = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			"SELECT MIN( l.min_price ) AS mn, MAX( l.max_price ) AS mx
			 FROM {$wpdb->wc_product_meta_lookup} l
			 INNER JOIN {$wpdb->posts} p ON p.ID = l.product_id
			 WHERE p.post_type = 'product' AND p.post_status = 'publish'"
		);
		$bounds = array(
			'min' => $row && null !== $row->mn ? (float) floor( $row->mn ) : 0,
			'max' => $row && null !== $row->mx ? (float) ceil( $row->mx ) : 100,
		);
		if ( $bounds['max'] <= $bounds['min'] ) {
			$bounds['max'] = $bounds['min'] + 1;
		}
		set_transient( 'nexvio_price_bounds', $bounds, HOUR_IN_SECONDS );
		return $bounds;
	}

	public static function flush_bounds() {
		delete_transient( 'nexvio_price_bounds' );
	}
}
