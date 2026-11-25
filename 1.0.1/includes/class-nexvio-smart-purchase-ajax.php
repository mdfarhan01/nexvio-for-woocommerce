<?php
/**
 * Smart Purchase: AJAX "Add to cart" and "Buy now" for simple and variable products.
 *
 * - wc_ajax nexvio_sp_add : adds the product (or the chosen variation) and answers with the cart fragments.
 * - wc_ajax nexvio_sp_buy : validates and adds the product, then answers with the checkout URL.
 *   Mode "replace": the cart holds only this product. The old cart is kept until the new product
 *   was added; if adding fails, the old cart is restored. Mode "add": the product joins the cart.
 *
 * Every request is checked with a nonce, the product, variation and quantity are validated on the server.
 *
 * @package Nexvio_For_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Nexvio_Smart_Purchase_Ajax {

	const NONCE = 'nexvio_sp';

	public static function init() {
		add_action( 'wc_ajax_nexvio_sp_add', array( __CLASS__, 'add' ) );
		add_action( 'wc_ajax_nexvio_sp_buy', array( __CLASS__, 'buy' ) );
	}

	public static function nonce() {
		return wp_create_nonce( self::NONCE );
	}

	private static function fail( $message ) {
		wp_send_json( array( 'error' => true, 'message' => wp_strip_all_tags( (string) $message ) ) );
	}

	/** Notices WooCommerce collected during the request, as one plain text line. */
	private static function notice_text( $fallback ) {
		$text = '';
		if ( function_exists( 'wc_get_notices' ) ) {
			$errors = wc_get_notices( 'error' );
			if ( $errors ) {
				$first = reset( $errors );
				$text  = wp_strip_all_tags( is_array( $first ) && isset( $first['notice'] ) ? $first['notice'] : (string) $first );
			}
			wc_clear_notices();
		}
		return '' !== $text ? $text : $fallback;
	}

	/**
	 * Read and validate the request. Returns array( product_id, variation_id, quantity, variation ) or sends an error.
	 */
	private static function read_request() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), self::NONCE ) ) {
			self::fail( __( 'Your session expired. Please reload the page and try again.', 'nexvio' ) );
		}
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			self::fail( __( 'The cart is not available.', 'nexvio' ) );
		}

		$product_id   = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		$variation_id = isset( $_POST['variation_id'] ) ? absint( $_POST['variation_id'] ) : 0;
		$product      = $product_id ? wc_get_product( $product_id ) : false;

		if ( ! $product || 'publish' !== get_post_status( $product_id ) || ! $product->is_purchasable() ) {
			self::fail( __( 'This product cannot be purchased.', 'nexvio' ) );
		}

		$quantity = isset( $_POST['quantity'] ) ? wc_stock_amount( sanitize_text_field( wp_unslash( $_POST['quantity'] ) ) ) : 1;
		$min      = max( 1, (int) $product->get_min_purchase_quantity() );
		$max      = (int) $product->get_max_purchase_quantity();
		if ( $quantity < $min ) {
			$quantity = $min;
		}
		if ( $max > 0 && $quantity > $max ) {
			self::fail( sprintf( /* translators: %d: maximum quantity */ __( 'You can buy at most %d of this product.', 'nexvio' ), $max ) );
		}

		$variation = array();
		if ( $product->is_type( 'variable' ) ) {
			$vp = $variation_id ? wc_get_product( $variation_id ) : false;
			if ( ! $vp || ! $vp->is_type( 'variation' ) || (int) $vp->get_parent_id() !== $product_id ) {
				self::fail( __( 'Please choose all the product options first.', 'nexvio' ) );
			}
			if ( ! $vp->is_purchasable() || ! $vp->is_in_stock() ) {
				self::fail( __( 'This option is not available.', 'nexvio' ) );
			}
			/* only the attributes of this product are accepted */
			$allowed = array_keys( (array) $product->get_variation_attributes() );
			foreach ( $allowed as $name ) {
				$key = 'attribute_' . sanitize_title( $name );
				$value = isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nonce verified in read_request(); value sanitised right here.
				if ( '' !== $value ) {
					$variation[ $key ] = $value;
				}
			}
		} elseif ( $variation_id ) {
			$variation_id = 0;
		}

		return array( $product_id, $variation_id, $quantity, $variation );
	}

	private static function fragments() {
		ob_start();
		woocommerce_mini_cart();
		$mini_cart = ob_get_clean();
		return array(
			'fragments' => apply_filters( 'woocommerce_add_to_cart_fragments', array( // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core hook.
				'div.widget_shopping_cart_content' => '<div class="widget_shopping_cart_content">' . $mini_cart . '</div>',
			) ),
			'cart_hash' => WC()->cart->get_cart_hash(),
		);
	}

	public static function add() {
		list( $product_id, $variation_id, $quantity, $variation ) = self::read_request();

		$passed = apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity, $variation_id, $variation ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core hook.
		if ( ! $passed ) {
			self::fail( self::notice_text( __( 'Could not add this product.', 'nexvio' ) ) );
		}
		if ( false === WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variation ) ) {
			self::fail( self::notice_text( __( 'Could not add this product.', 'nexvio' ) ) );
		}
		wc_clear_notices();
		do_action( 'woocommerce_ajax_added_to_cart', $product_id ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core hook.

		wp_send_json( array_merge( array( 'success' => true ), self::fragments() ) );
	}

	public static function buy() {
		list( $product_id, $variation_id, $quantity, $variation ) = self::read_request();

		$passed = apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity, $variation_id, $variation ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core hook.
		if ( ! $passed ) {
			self::fail( self::notice_text( __( 'Could not add this product.', 'nexvio' ) ) );
		}

		$replace = isset( $_POST['mode'] ) && 'add' !== sanitize_key( wp_unslash( $_POST['mode'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified in read_request().
		$backup  = null;
		if ( $replace && ! WC()->cart->is_empty() ) {
			/* keep the old cart until the new product is in */
			$backup = WC()->cart->get_cart_contents();
			WC()->cart->empty_cart( false );
		}

		$key = WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variation );

		if ( false === $key ) {
			$message = self::notice_text( __( 'Could not add this product.', 'nexvio' ) );
			if ( null !== $backup ) {
				WC()->cart->set_cart_contents( $backup );
				WC()->cart->calculate_totals();
				WC()->cart->set_session();
			}
			self::fail( $message );
		}
		wc_clear_notices();

		wp_send_json( array_merge( array(
			'success'  => true,
			'redirect' => wc_get_checkout_url(),
		), self::fragments() ) );
	}
}
