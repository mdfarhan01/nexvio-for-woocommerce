<?php
/**
 * Plugin Name:       Nexvio for WooCommerce
 * Description:       Elementor widgets for WooCommerce: product slider, grid, AJAX filter, side cart and a built-in wishlist.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce, elementor
 * WC requires at least: 6.0
 * Author:            Md. Farhan Sadik
 * Text Domain:       nexvio
 * Domain Path:       /languages
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package Nexvio_For_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* a second copy of the plugin is active: stop here instead of crashing the site */
if ( defined( 'NEXVIO_VERSION' ) || class_exists( 'Nexvio_Plugin', false ) ) {
	return;
}

define( 'NEXVIO_VERSION', '1.0.0' );
define( 'NEXVIO_FILE', __FILE__ );
define( 'NEXVIO_PATH', plugin_dir_path( __FILE__ ) );
define( 'NEXVIO_URL', plugin_dir_url( __FILE__ ) );

/* WooCommerce HPOS compatibility (the plugin does not touch orders) */
add_action( 'before_woocommerce_init', function () {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );

add_action( 'plugins_loaded', function () {
	require_once NEXVIO_PATH . 'includes/class-nexvio-plugin.php';
	Nexvio_Plugin::instance()->boot();
}, 20 );

register_activation_hook( __FILE__, function () {
	add_option( 'nexvio_disabled_widgets', array() );
	add_option( 'nexvio_wishlist_guests', 'yes' );
	add_option( 'nexvio_wishlist_days', 60 );
	add_option( 'nexvio_auto_related', 'no' );
	update_option( 'nexvio_do_activation_redirect', 1, false ); /* open the dashboard once */
} );
