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
	'nexvio_disabled_widgets', 'nexvio_wishlist_page', 'nexvio_wishlist_page_pending', 'nexvio_wishlist_guests', 'nexvio_wishlist_days',
	'nexvio_per_page', 'nexvio_do_activation_redirect', 'nexvio_auto_related', 'nexvio_related_title', 'nexvio_related_limit',
	'nexvio_related_orderby', 'nexvio_related_layout', 'nexvio_cart', 'nexvio_filters', 'nexvio_filter_style',
	'nexvio_epo_settings',
) as $nexvio_option ) {
	delete_option( $nexvio_option );
}
delete_transient( 'nexvio_price_bounds' );

/* Extra Options forms (the fields already saved in orders stay in the orders) */
foreach ( get_posts( array( 'post_type' => 'nexvio_epo', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids' ) ) as $nexvio_form_id ) {
	wp_delete_post( $nexvio_form_id, true );
}
