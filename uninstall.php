<?php
/**
 * Removes the plugin settings when the plugin is deleted.
 * Customers' wishlists (user meta) are kept on purpose.
 *
 * @package Nexvio_For_WooCommerce
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

foreach ( array(
	'nexvio_disabled_widgets', 'nexvio_wishlist_page', 'nexvio_wishlist_guests', 'nexvio_wishlist_days',
	'nexvio_per_page', 'nexvio_auto_related', 'nexvio_related_title', 'nexvio_related_limit',
	'nexvio_related_orderby', 'nexvio_related_layout', 'nexvio_cart', 'nexvio_filters', 'nexvio_filter_style',
) as $nexvio_option ) {
	delete_option( $nexvio_option );
}
delete_transient( 'nexvio_price_bounds' );
