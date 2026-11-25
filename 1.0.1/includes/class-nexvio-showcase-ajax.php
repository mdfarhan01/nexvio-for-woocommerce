<?php
/**
 * Product Showcase: AJAX add to cart for variable products.
 *
 * The normal WooCommerce "add_to_cart" endpoint only handles simple products and a variation sent as product_id.
 * This endpoint receives the parent product, the variation id and the chosen attributes (also "any" attributes),
 * adds the variation to the cart and answers with the same data (fragments + cart hash) as WooCommerce does.
 *
 * @package Nexvio_For_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Nexvio_Showcase_Ajax {

	public static function init() {
		add_action( 'wc_ajax_nexvio_shw_add', array( __CLASS__, 'add' ) );
	}

	public static function add() {
		// phpcs:disable WordPress.Security.NonceVerification -- same as WooCommerce's own add_to_cart endpoint (public, no nonce).
		$product_id   = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		$variation_id = isset( $_POST['variation_id'] ) ? absint( $_POST['variation_id'] ) : 0;
		$quantity     = isset( $_POST['quantity'] ) ? wc_stock_amount( sanitize_text_field( wp_unslash( $_POST['quantity'] ) ) ) : 1;
		$quantity     = $quantity > 0 ? $quantity : 1;
		$product      = $product_id ? wc_get_product( $product_id ) : false;
		$product_url  = $product ? $product->get_permalink() : wc_get_page_permalink( 'shop' );

		if ( ! $product || ! $variation_id || 'publish' !== get_post_status( $product_id ) ) {
			wp_send_json( array( 'error' => true, 'product_url' => $product_url ) );
		}

		$variation = array();
		foreach ( $_POST as $key => $value ) {
			if ( 0 === strpos( (string) $key, 'attribute_' ) ) {
				$variation[ sanitize_title( wp_unslash( $key ) ) ] = is_array( $value ) ? '' : wc_clean( wp_unslash( $value ) );
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification

		$passed = apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity, $variation_id, $variation ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core hook.

		if ( $passed && false !== WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variation ) ) {
			do_action( 'woocommerce_ajax_added_to_cart', $product_id ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core hook.

			if ( 'yes' === get_option( 'woocommerce_cart_redirect_after_add' ) ) {
				wc_add_to_cart_message( array( $product_id => $quantity ), true );
			}

			ob_start();
			woocommerce_mini_cart();
			$mini_cart = ob_get_clean();

			wp_send_json( array(
				'fragments' => apply_filters( 'woocommerce_add_to_cart_fragments', array( // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core hook.
					'div.widget_shopping_cart_content' => '<div class="widget_shopping_cart_content">' . $mini_cart . '</div>',
				) ),
				'cart_hash' => WC()->cart->get_cart_hash(),
			) );
		}

		wp_send_json( array( 'error' => true, 'product_url' => $product_url ) );
	}
}
