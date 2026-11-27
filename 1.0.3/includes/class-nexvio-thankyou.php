<?php
 if ( ! defined( 'ABSPATH' ) ) {
exit;
}
class Nexvio_Thankyou {
const OPTION = 'nexvio_ty';
const NONCE = 'nexvio_ty';
public static function init() {
add_action( 'wp_ajax_nexvio_ty_save', array( __CLASS__, 'ajax_save' ) );
add_action( 'wp_ajax_nexvio_ty_create', array( __CLASS__, 'ajax_create' ) );
add_action( 'wp_ajax_nexvio_ty_preview', array( __CLASS__, 'ajax_preview' ) );
add_shortcode( 'nexvio_thankyou', array( __CLASS__, 'shortcode' ) );
add_filter( 'woocommerce_get_checkout_order_received_url', array( __CLASS__, 'received_url' ), 20, 2 );
add_action( 'template_redirect', array( __CLASS__, 'maybe_redirect' ), 5 );
add_action( 'wp_enqueue_scripts', array( __CLASS__, 'maybe_enqueue' ), 20 );
add_filter( 'wc_get_template', array( __CLASS__, 'template' ), 20, 2 );
}
public static function designs() {
return array( 1 => array( __( 'Classic Card', 'nexvio' ), __( 'A clean centred card with a green tick and a tidy summary.', 'nexvio' ), '#16a34a' ), 2 => array( __( 'Split Receipt', 'nexvio' ), __( 'Message on the left, a paper receipt on the right.', 'nexvio' ), '#4f46e5' ), 3 => array( __( 'Order Timeline', 'nexvio' ), __( 'A progress timeline that shows where the order is.', 'nexvio' ), '#0ea5e9' ), 4 => array( __( 'Dark Luxe', 'nexvio' ), __( 'Dark and elegant with gold details. Feels premium.', 'nexvio' ), '#d4af37' ), 5 => array( __( 'Minimal Editorial', 'nexvio' ), __( 'Lots of white space, a serif headline, thin lines.', 'nexvio' ), '#111827' ), 6 => array( __( 'Gradient Hero', 'nexvio' ), __( 'A bold gradient banner with the summary card on top of it.', 'nexvio' ), '#7c3aed' ), 7 => array( __( 'Dashboard Tiles', 'nexvio' ), __( 'Everything in neat rounded tiles.', 'nexvio' ), '#f97316' ), 8 => array( __( 'Invoice Paper', 'nexvio' ), __( 'Looks like a printed invoice, ready to keep.', 'nexvio' ), '#0f766e' ), 9 => array( __( 'Celebration', 'nexvio' ), __( 'Colourful confetti for a happy moment.', 'nexvio' ), '#ec4899' ), 10 => array( __( 'Sidebar Summary', 'nexvio' ), __( 'Details on the left, a sticky order summary on the right.', 'nexvio' ), '#2563eb' ), );
}
public static function defaults() {
return array( 'design' => 1, 'page_id' => 0, 'title' => __( 'Thank you, {name}!', 'nexvio' ), 'message' => __( 'Your order #{order} is confirmed. We sent a receipt to {email}.', 'nexvio' ), 'btn_text' => __( 'Continue shopping', 'nexvio' ), 'btn_url' => '', 'accent' => '', 'show_steps' => 1, 'show_items' => 1, 'show_addr' => 1, 'show_extras' => 1, 'show_orders' => 1, 'show_icon' => 1, 'show_message' => 1, 'show_meta' => 1, 'show_totals' => 1, 'show_note' => 1, );
}
public static function labels() {
return array( 'order_number' => __( 'Order number', 'nexvio' ), 'date' => __( 'Date', 'nexvio' ), 'email' => __( 'Email', 'nexvio' ), 'payment' => __( 'Payment', 'nexvio' ), 'summary' => __( 'Order summary', 'nexvio' ), 'subtotal' => __( 'Subtotal', 'nexvio' ), 'shipping' => __( 'Shipping', 'nexvio' ), 'discount' => __( 'Discount', 'nexvio' ), 'tax' => __( 'Tax', 'nexvio' ), 'total' => __( 'Total', 'nexvio' ), 'billing' => __( 'Billing address', 'nexvio' ), 'shipping_adr' => __( 'Shipping address', 'nexvio' ), 'extras' => __( 'Delivery and extra details', 'nexvio' ), 'step1' => __( 'Order placed', 'nexvio' ), 'step2' => __( 'Processing', 'nexvio' ), 'step3' => __( 'On the way', 'nexvio' ), 'step4' => __( 'Delivered', 'nexvio' ), 'orders_btn' => __( 'View my orders', 'nexvio' ), 'kicker' => __( 'Order confirmed', 'nexvio' ), 'receipt' => __( 'Order receipt', 'nexvio' ), );
}
private static $labels_now = null;
private static function L( $key ) {
$all = self::labels();
$now = is_array( self::$labels_now ) ? self::$labels_now : array();
return isset( $now[ $key ] ) && '' !== trim( (string) $now[ $key ] ) ? (string) $now[ $key ] : ( isset( $all[ $key ] ) ? $all[ $key ] : '' );
}
public static function settings() {
$s = get_option( self::OPTION, array() );
$s = wp_parse_args( is_array( $s ) ? $s : array(), self::defaults() );
if ( ! isset( self::designs()[ (int) $s['design'] ] ) ) {
$s['design'] = 1;
}
return $s;
}
public static function clean( $in ) {
$d = self::defaults();
$out = array();
$id = isset( $in['design'] ) ? (int) $in['design'] : 1;
$out['design'] = isset( self::designs()[ $id ] ) ? $id : 1;
$out['page_id'] = isset( $in['page_id'] ) ? absint( $in['page_id'] ) : 0;
if ( $out['page_id'] && 'page' !== get_post_type( $out['page_id'] ) ) {
$out['page_id'] = 0;
}
foreach ( array( 'title', 'btn_text' ) as $k ) {
$out[ $k ] = isset( $in[ $k ] ) ? sanitize_text_field( wp_unslash( $in[ $k ] ) ) : $d[ $k ];
}
$out['message'] = isset( $in['message'] ) ? sanitize_textarea_field( wp_unslash( $in['message'] ) ) : $d['message'];
$out['btn_url'] = isset( $in['btn_url'] ) ? esc_url_raw( wp_unslash( $in['btn_url'] ) ) : '';
$acc = isset( $in['accent'] ) ? sanitize_hex_color( wp_unslash( $in['accent'] ) ) : '';
$out['accent'] = $acc ? $acc : '';
foreach ( array( 'show_steps', 'show_items', 'show_addr', 'show_extras', 'show_orders' ) as $k ) {
$out[ $k ] = ! empty( $in[ $k ] ) && '0' !== $in[ $k ] && 'false' !== $in[ $k ] ? 1 : 0;
}
return $out;
}
public static function page_id() {
$id = (int) self::settings()['page_id'];
return ( $id && 'publish' === get_post_status( $id ) ) ? $id : 0;
}
private static function page_url( $order_id, $key ) {
$id = self::page_id();
if ( ! $id ) {
return '';
}
return add_query_arg( array( 'nxo' => (int) $order_id, 'key' => rawurlencode( $key ) ), get_permalink( $id ) );
}
public static function received_url( $url, $order ) {
if ( is_object( $order ) && method_exists( $order, 'get_id' ) ) {
$new = self::page_url( $order->get_id(), $order->get_order_key() );
if ( $new ) {
return $new;
}
}
return $url;
}
public static function maybe_redirect() {
if ( is_admin() || ! function_exists( 'is_wc_endpoint_url' ) || ! is_wc_endpoint_url( 'order-received' ) ) {
return;
}
$page = self::page_id();
if ( ! $page || (int) get_queried_object_id() === $page || ( function_exists( 'wc_get_page_id' ) && (int) wc_get_page_id( 'checkout' ) === $page ) ) {
return;
}
$order_id = absint( get_query_var( 'order-received' ) );
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only request state (filter, sort, page or preview); nothing is saved, so no nonce is needed.
$key = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';
if ( ! $order_id || '' === $key ) {
return;
}
$to = self::page_url( $order_id, $key );
if ( $to ) {
wp_safe_redirect( $to );
exit;
}
}
public static function maybe_enqueue() {
$page = self::page_id();
if ( ( $page && is_page( $page ) ) || ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-received' ) ) ) {
self::style();
}
if ( $page && is_page( $page ) ) {
$sel = 'body.page-id-' . (int) $page;
wp_add_inline_style( 'nexvio-thankyou', $sel . ' .entry-header,' . $sel . ' .page-header,' . $sel . ' .ast-single-post-order .entry-title{display:none!important}' );
}
}
public static function template( $template, $name ) {
if ( 'checkout/thankyou.php' !== $name ) {
return $template;
}
$order = self::current_order();
if ( ! $order || $order->has_status( 'failed' ) ) {
return $template;
}
return NEXVIO_PATH . 'templates/thankyou.php';
}
public static function style() {
wp_enqueue_style( 'nexvio-thankyou', NEXVIO_URL . 'assets/css/thankyou.css', array(), Nexvio_Plugin::ver( 'assets/css/thankyou.css' ) );
}
private static function money( $amount, $order = null ) {
if ( function_exists( 'wc_price' ) ) {
return wc_price( $amount, $order ? array( 'currency' => $order->get_currency() ) : array() );
}
return '$' . number_format( (float) $amount, 2 );
}
public static function demo() {
return array( 'demo' => true, 'id' => 1024, 'number' => '1024', 'date' => date_i18n( get_option( 'date_format', 'F j, Y' ) ), 'name' => __( 'Alex', 'nexvio' ), 'email' => 'alex@example.com', 'total' => self::money( 148.5 ), 'subtotal' => self::money( 139 ), 'shipping' => self::money( 9.5 ), 'ship_via' => __( 'Standard delivery', 'nexvio' ), 'discount' => '', 'tax' => '', 'payment' => __( 'Credit card', 'nexvio' ), 'status' => 'processing', 'items' => array( array( 'name' => __( 'Wireless headphones', 'nexvio' ), 'qty' => 1, 'total' => self::money( 99 ), 'img' => '' ), array( 'name' => __( 'Leather travel case', 'nexvio' ), 'qty' => 2, 'total' => self::money( 40 ), 'img' => '' ), ), 'billing' => 'Alex Morgan<br/>12 Lake Road<br/>Dhaka 1212<br/>Bangladesh', 'shipping_addr' => 'Alex Morgan<br/>12 Lake Road<br/>Dhaka 1212<br/>Bangladesh', 'extras' => array( array( 'label' => __( 'Delivery date', 'nexvio' ), 'value' => date_i18n( 'D, j M Y', time() + 3 * DAY_IN_SECONDS ) ), array( 'label' => __( 'Delivery time', 'nexvio' ), 'value' => '10:00 - 12:00' ), ), 'pay_note' => '', );
}
public static function from_order( $order ) {
$items = array();
foreach ( $order->get_items() as $item ) {
$img = '';
$product = is_callable( array( $item, 'get_product' ) ) ? $item->get_product() : null;
if ( $product ) {
$img = (string) wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' );
}
$items[] = array( 'name' => $item->get_name(), 'qty' => (int) $item->get_quantity(), 'total' => self::money( $order->get_line_total( $item, true ), $order ), 'img' => $img, );
}
$extras = array();
if ( method_exists( 'Nexvio_Checkout', 'order_rows' ) ) {
foreach ( Nexvio_Checkout::order_rows( $order ) as $r ) {
$extras[] = array( 'label' => $r['label'], 'value' => $r['value'] );
}
}
$ship = (float) $order->get_shipping_total();
return array( 'demo' => false, 'id' => $order->get_id(), 'number' => $order->get_order_number(), 'date' => $order->get_date_created() ? wc_format_datetime( $order->get_date_created() ) : '', 'name' => $order->get_billing_first_name() ? $order->get_billing_first_name() : __( 'there', 'nexvio' ), 'email' => $order->get_billing_email(), 'total' => $order->get_formatted_order_total(), 'subtotal' => self::money( $order->get_subtotal(), $order ), 'shipping' => $ship > 0 ? self::money( $ship, $order ) : ( $order->get_shipping_method() ? __( 'Free', 'nexvio' ) : '' ), 'ship_via' => $order->get_shipping_method(), 'discount' => (float) $order->get_discount_total() > 0 ? '-' . self::money( $order->get_discount_total(), $order ) : '', 'tax' => (float) $order->get_total_tax() > 0 ? self::money( $order->get_total_tax(), $order ) : '', 'payment' => $order->get_payment_method_title(), 'status' => $order->get_status(), 'items' => $items, 'billing' => $order->get_formatted_billing_address(), 'shipping_addr' => $order->get_formatted_shipping_address(), 'extras' => $extras, 'pay_note' => '', );
}
public static function current_order() {
if ( ! function_exists( 'wc_get_order' ) ) {
return null;
}
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only request state (filter, sort, page or preview); nothing is saved, so no nonce is needed.
$id = isset( $_GET['nxo'] ) ? absint( $_GET['nxo'] ) : absint( get_query_var( 'order-received' ) );
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only request state (filter, sort, page or preview); nothing is saved, so no nonce is needed.
$key = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';
if ( ! $id || '' === $key ) {
return null;
}
$order = wc_get_order( $id );
if ( ! $order || ! hash_equals( (string) $order->get_order_key(), $key ) ) {
return null;
}
return $order;
}
public static function shortcode( $atts = array() ) {
$atts = shortcode_atts( array( 'design' => '' ), $atts, 'nexvio_thankyou' );
return self::output( array( 'design' => (int) $atts['design'] ) );
}
public static function output( $args = array() ) {
self::style();
$s = self::settings();
$design = ! empty( $args['design'] ) && isset( self::designs()[ (int) $args['design'] ] ) ? (int) $args['design'] : (int) $s['design'];
if ( ! empty( $args['override'] ) && is_array( $args['override'] ) ) {
$s = array_merge( $s, $args['override'] );
}
self::$labels_now = isset( $s['labels'] ) && is_array( $s['labels'] ) ? $s['labels'] : array();
if ( ! empty( $args['accent'] ) ) {
$s['accent'] = $args['accent'];
}
$order = ! empty( $args['order'] ) ? $args['order'] : self::current_order();
$extra = '';
if ( $order ) {
$data = self::from_order( $order );
if ( ! $order->has_status( 'failed' ) && function_exists( 'WC' ) && WC()->cart ) {
WC()->cart->empty_cart();
if ( WC()->session ) {
WC()->session->set( 'order_awaiting_payment', false );
}
}
ob_start();
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WordPress core hook, not a Nexvio hook.
do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() );
$data['pay_note'] = trim( (string) ob_get_clean() );
$had = has_action( 'woocommerce_thankyou', 'woocommerce_order_details_table' );
if ( false !== $had ) {
remove_action( 'woocommerce_thankyou', 'woocommerce_order_details_table', $had );
}
ob_start();
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WordPress core hook, not a Nexvio hook.
do_action( 'woocommerce_thankyou', $order->get_id() );
$extra = trim( (string) ob_get_clean() );
if ( false !== $had ) {
add_action( 'woocommerce_thankyou', 'woocommerce_order_details_table', $had );
}
}
elseif ( ! empty( $args['demo'] ) || current_user_can( 'manage_options' ) ) {
$data = self::demo();
}
else {
$url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
return '<div class="nxty nxty--empty"><p>' . esc_html__( 'We could not find an order here.', 'nexvio' ) . '</p><a class="nxty__btn" href="' . esc_url( $url ) . '">' . esc_html__( 'Continue shopping', 'nexvio' ) . '</a></div>';
}
$html = self::render( $design, $data, $s );
if ( ! empty( $data['demo'] ) && empty( $args['demo'] ) ) {
$html = '<p class="nxty__demo-note">' . esc_html__( 'Example order. Only site admins see this; customers see their own order.', 'nexvio' ) . '</p>' . $html;
}
return $html . $extra;
}
private static function svg( $name ) {
$p = array( 'check' => '<path d="m5 12.5 4.5 4.5L19 7.5"/>', 'box' => '<path d="M3 7.5 12 3l9 4.5v9L12 21l-9-4.5z"/><path d="m3 7.5 9 4.5 9-4.5M12 12v9"/>', 'truck' => '<path d="M2 6h11v10H2zM13 10h4l3 3v3h-7"/><circle cx="6.5" cy="17.5" r="1.8"/><circle cx="16.5" cy="17.5" r="1.8"/>', 'home' => '<path d="m3 11 9-7 9 7M5 10v10h14V10"/>', 'cog' => '<circle cx="12" cy="12" r="3"/><path d="M12 3v3M12 18v3M3 12h3M18 12h3M5.6 5.6l2.1 2.1M16.3 16.3l2.1 2.1M18.4 5.6l-2.1 2.1M7.7 16.3l-2.1 2.1"/>', 'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>', 'card' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h4"/>', 'cal' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>', 'hash' => '<path d="M5 9h14M5 15h14M10 4 8 20M16 4l-2 16"/>', 'arrow' => '<path d="M5 12h14m-6-6 6 6-6 6"/>', 'gift' => '<rect x="3" y="9" width="18" height="12" rx="1"/><path d="M12 9v12M3 13h18M12 9c-3 0-5-1-5-3s3-3 5 0c2-3 5-2 5 0s-2 3-5 3z"/>', );
return '<svg class="nxty__ico" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p[ $name ] . '</svg>';
}
private static function fill( $text, $d ) {
return strtr( $text, array( '{name}' => $d['name'], '{order}' => $d['number'], '{email}' => $d['email'], '{total}' => wp_strip_all_tags( $d['total'] ), ) );
}
private static function b_hero( $d, $s, $icon = 'check' ) {
$ico = ! isset( $s['show_icon'] ) || ! empty( $s['show_icon'] ) ? '<span class="nxty__tick">' . self::svg( $icon ) . '</span>' : '';
$msg = ! isset( $s['show_message'] ) || ! empty( $s['show_message'] ) ? '<p class="nxty__msg">' . esc_html( self::fill( $s['message'], $d ) ) . '</p>' : '';
return '<header class="nxty__hero">' . $ico . '<h2 class="nxty__title">' . esc_html( self::fill( $s['title'], $d ) ) . '</h2>' . $msg . '</header>';
}
private static function meta_rows( $d ) {
$rows = array( array( 'hash', self::L( 'order_number' ), '#' . $d['number'] ), array( 'cal', self::L( 'date' ), $d['date'] ), array( 'mail', self::L( 'email' ), $d['email'] ), array( 'card', self::L( 'payment' ), $d['payment'] ), );
return array_filter( $rows, function ( $r ) {
return '' !== trim( (string) $r[2] ) && '#' !== $r[2];
}
);
}
private static $show_meta = true;
private static $show_totals = true;
private static $show_note = true;
private static function b_meta( $d, $icons = false ) {
if ( ! self::$show_meta ) {
return '';
}
$h = '<dl class="nxty__meta">';
foreach ( self::meta_rows( $d ) as $r ) {
$h .= '<div>' . ( $icons ? '<span class="nxty__mico">' . self::svg( $r[0] ) . '</span>' : '' ) . '<dt>' . esc_html( $r[1] ) . '</dt><dd>' . esc_html( $r[2] ) . '</dd></div>';
}
return $h . '</dl>';
}
private static function b_items( $d, $s ) {
if ( empty( $s['show_items'] ) || empty( $d['items'] ) ) {
return '';
}
$h = '<ul class="nxty__items">';
foreach ( $d['items'] as $i ) {
$img = $i['img'] ? '<img src="' . esc_url( $i['img'] ) . '" alt="" loading="lazy">' : '<span class="nxty__noimg">' . self::svg( 'box' ) . '</span>';
$h .= '<li><span class="nxty__thumb">' . $img . '</span><span class="nxty__iname">' . esc_html( $i['name'] ) . '<small>&times; ' . (int) $i['qty'] . '</small></span><b class="nxty__iprice">' . wp_kses_post( $i['total'] ) . '</b></li>';
}
return $h . '</ul>';
}
private static function b_totals( $d ) {
if ( ! self::$show_totals ) {
return '';
}
$rows = array( array( self::L( 'subtotal' ), $d['subtotal'], '' ), array( $d['ship_via'] ? self::L( 'shipping' ) . ' (' . $d['ship_via'] . ')' : self::L( 'shipping' ), $d['shipping'], '' ), array( self::L( 'discount' ), $d['discount'], '' ), array( self::L( 'tax' ), $d['tax'], '' ), array( self::L( 'total' ), $d['total'], 'is-total' ), );
$h = '<dl class="nxty__totals">';
foreach ( $rows as $r ) {
if ( '' === trim( (string) $r[1] ) ) {
continue;
}
$h .= '<div class="' . esc_attr( $r[2] ) . '"><dt>' . esc_html( $r[0] ) . '</dt><dd>' . wp_kses_post( $r[1] ) . '</dd></div>';
}
return $h . '</dl>';
}
private static function b_addr( $d, $s ) {
if ( empty( $s['show_addr'] ) || ( ! $d['billing'] && ! $d['shipping_addr'] ) ) {
return '';
}
$h = '<div class="nxty__addr">';
if ( $d['billing'] ) {
$h .= '<section><h4>' . esc_html( self::L( 'billing' ) ) . '</h4><address>' . wp_kses_post( $d['billing'] ) . '</address></section>';
}
if ( $d['shipping_addr'] ) {
$h .= '<section><h4>' . esc_html( self::L( 'shipping_adr' ) ) . '</h4><address>' . wp_kses_post( $d['shipping_addr'] ) . '</address></section>';
}
return $h . '</div>';
}
private static function b_extras( $d, $s ) {
if ( empty( $s['show_extras'] ) || empty( $d['extras'] ) ) {
return '';
}
$h = '<section class="nxty__extras"><h4>' . esc_html( self::L( 'extras' ) ) . '</h4><dl>';
foreach ( $d['extras'] as $r ) {
$h .= '<div><dt>' . esc_html( $r['label'] ) . '</dt><dd>' . esc_html( $r['value'] ) . '</dd></div>';
}
return $h . '</dl></section>';
}
private static function b_steps( $d, $s ) {
if ( empty( $s['show_steps'] ) ) {
return '';
}
$now = array( 'completed' => 3, 'processing' => 1, 'on-hold' => 1, 'pending' => 0, 'refunded' => 0, 'cancelled' => 0, 'failed' => 0 );
$at = isset( $now[ $d['status'] ] ) ? $now[ $d['status'] ] : 1;
$st = array( array( 'check', self::L( 'step1' ) ), array( 'cog', self::L( 'step2' ) ), array( 'truck', self::L( 'step3' ) ), array( 'home', self::L( 'step4' ) ), );
$h = '<ol class="nxty__steps">';
foreach ( $st as $i => $x ) {
$cls = $i < $at ? 'is-done' : ( $i === $at ? 'is-now' : '' );
$h .= '<li class="' . $cls . '"><span class="nxty__dot">' . self::svg( $x[0] ) . '</span><span class="nxty__slabel">' . esc_html( $x[1] ) . '</span></li>';
}
return $h . '</ol>';
}
private static function b_note( $d ) {
return $d['pay_note'] && self::$show_note ? '<section class="nxty__note">' . wp_kses_post( $d['pay_note'] ) . '</section>' : '';
}
private static function b_actions( $d, $s ) {
$url = $s['btn_url'];
if ( '' === $url ) {
$url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
}
$h = '<div class="nxty__actions"><a class="nxty__btn" href="' . esc_url( $url ) . '">' . esc_html( $s['btn_text'] ) . self::svg( 'arrow' ) . '</a>';
if ( ! empty( $s['show_orders'] ) && function_exists( 'wc_get_account_endpoint_url' ) && is_user_logged_in() ) {
$h .= '<a class="nxty__btn nxty__btn--ghost" href="' . esc_url( wc_get_account_endpoint_url( 'orders' ) ) . '">' . esc_html( self::L( 'orders_btn' ) ) . '</a>';
}
elseif ( ! empty( $d['demo'] ) && ! empty( $s['show_orders'] ) ) {
$h .= '<a class="nxty__btn nxty__btn--ghost" href="#">' . esc_html( self::L( 'orders_btn' ) ) . '</a>';
}
return $h . '</div>';
}
private static function h( $t ) {
return '<h3 class="nxty__h">' . esc_html( $t ) . '</h3>';
}
public static function render( $design, $d, $s ) {
$design = isset( self::designs()[ $design ] ) ? (int) $design : 1;
$sum = self::L( 'summary' );
self::$show_meta = ! isset( $s['show_meta'] ) || ! empty( $s['show_meta'] );
self::$show_totals = ! isset( $s['show_totals'] ) || ! empty( $s['show_totals'] );
self::$show_note = ! isset( $s['show_note'] ) || ! empty( $s['show_note'] );
$style = $s['accent'] ? ' style="--nxty-a:' . esc_attr( $s['accent'] ) . '"' : '';
$hero = self::b_hero( $d, $s );
$items = self::b_items( $d, $s );
$tot = self::b_totals( $d );
$addr = self::b_addr( $d, $s );
$ext = self::b_extras( $d, $s );
$steps = self::b_steps( $d, $s );
$note = self::b_note( $d );
$act = self::b_actions( $d, $s );
$meta = self::b_meta( $d );
$H = '';
switch ( $design ) {
case 2: $H = '<div class="nxty__split"><div class="nxty__left">' . $hero . $meta . $ext . $note . $act . '</div><aside class="nxty__paper">' . self::h( $sum ) . $items . $tot . $addr . '</aside></div>';
break;
case 3: $H = '<div class="nxty__card">' . $hero . $steps . self::b_meta( $d, true ) . '<div class="nxty__cols"><div>' . self::h( $sum ) . $items . '</div><div>' . $tot . $ext . '</div></div>' . $addr . $note . $act . '</div>';
break;
case 4: $H = $hero . $meta . '<div class="nxty__panel">' . self::h( $sum ) . $items . $tot . '</div>' . $ext . $addr . $note . $act;
break;
case 5: $H = '<span class="nxty__kicker">' . esc_html( self::L( 'kicker' ) ) . ' &middot; #' . esc_html( $d['number'] ) . '</span>' . $hero . $meta . $items . $tot . $ext . $addr . $note . $act;
break;
case 6: $H = '<div class="nxty__banner">' . $hero . '</div><div class="nxty__card">' . self::b_meta( $d, true ) . $steps . self::h( $sum ) . $items . $tot . $ext . $addr . $note . $act . '</div>';
break;
case 7: $H = $hero . '<div class="nxty__tiles">' . '<div class="nxty__tile nxty__tile--meta">' . self::b_meta( $d, true ) . '</div>' . '<div class="nxty__tile nxty__tile--items">' . self::h( $sum ) . $items . '</div>' . '<div class="nxty__tile nxty__tile--tot">' . $tot . '</div>' . ( $ext ? '<div class="nxty__tile">' . $ext . '</div>' : '' ) . ( $addr ? '<div class="nxty__tile nxty__tile--addr">' . $addr . '</div>' : '' ) . ( $steps ? '<div class="nxty__tile nxty__tile--steps">' . $steps . '</div>' : '' ) . '</div>' . $note . $act;
break;
case 8: $H = '<div class="nxty__sheet"><div class="nxty__sheethead"><div><span class="nxty__kicker">' . esc_html( self::L( 'receipt' ) ) . '</span><h2 class="nxty__title">' . esc_html( self::fill( $s['title'], $d ) ) . '</h2><p class="nxty__msg">' . esc_html( self::fill( $s['message'], $d ) ) . '</p></div><div class="nxty__stamp"><b>#' . esc_html( $d['number'] ) . '</b><span>' . esc_html( $d['date'] ) . '</span></div></div>' . $addr . $items . $tot . $ext . $note . '<p class="nxty__foot">' . esc_html( $d['payment'] ) . '</p></div>' . $act;
break;
case 9: $conf = '';
for ( $i = 1;
$i <= 18;
$i++ ) {
$conf .= '<i class="c' . $i . '"></i>';
}
$H = '<div class="nxty__confetti" aria-hidden="true">' . $conf . '</div>' . self::b_hero( $d, $s, 'gift' ) . $meta . '<div class="nxty__card">' . self::h( $sum ) . $items . $tot . '</div>' . $ext . $addr . $note . $act;
break;
case 10: $H = '<div class="nxty__side"><div class="nxty__main">' . $hero . $steps . $ext . $addr . $note . $act . '</div><aside class="nxty__summary">' . self::h( $sum ) . $meta . $items . $tot . '</aside></div>';
break;
default: $H = '<div class="nxty__card">' . $hero . $meta . self::h( $sum ) . $items . $tot . $ext . $addr . $note . $act . '</div>';
}
return '<div class="nxty nxty--d' . $design . '"' . $style . '>' . $H . '</div>';
}
private static function guard() {
check_ajax_referer( self::NONCE, 'nonce' );
if ( ! current_user_can( 'manage_options' ) ) {
wp_send_json_error( array( 'message' => __( 'You are not allowed to do this.', 'nexvio' ) ), 403 );
}
}
public static function page_info( $id ) {
$id = (int) $id;
if ( ! $id || ! get_post( $id ) ) {
return null;
}
return array( 'id' => $id, 'title' => get_the_title( $id ), 'status' => get_post_status( $id ), 'view' => get_permalink( $id ), 'edit' => get_edit_post_link( $id, 'raw' ), );
}
public static function ajax_save() {
check_ajax_referer( self::NONCE, 'nonce' );
self::guard();
// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON/array input that is unslashed here and fully sanitised right after decoding.
$in = isset( $_POST['s'] ) && is_array( $_POST['s'] ) ? wp_unslash( $_POST['s'] ) : array();
$in = wp_slash( $in );
$s = self::clean( $in );
update_option( self::OPTION, $s, false );
if ( ! self::page_id() ) {
self::ensure_page();
}
if ( self::page_id() ) {
self::setup_page( self::page_id(), (int) self::settings()['design'] );
}
wp_send_json_success( array( 'settings' => self::settings(), 'page' => self::page_info( self::settings()['page_id'] ) ) );
}
public static function ensure_page() {
$found = get_posts( array( 'post_type' => 'page', 'post_status' => 'publish', 's' => '[nexvio_thankyou', 'posts_per_page' => 1, 'fields' => 'ids', ) );
$id = $found ? (int) $found[0] : 0;
if ( ! $id ) {
$new = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => __( 'Thank you', 'nexvio' ), 'post_name' => 'thank-you', 'post_content' => '[nexvio_thankyou]', ), true );
$id = is_wp_error( $new ) ? 0 : (int) $new;
}
if ( $id ) {
$s = self::settings();
$s['page_id'] = $id;
update_option( self::OPTION, $s, false );
self::setup_page( $id, (int) $s['design'] );
}
return $id;
}
public static function widget_name( $design ) {
return 'nexvio_thankyou_d' . (int) $design;
}
private static function elementor_on() {
return did_action( 'elementor/loaded' ) && class_exists( '\\Elementor\\Plugin' ) && isset( \Elementor\Plugin::$instance );
}
private static function new_id() {
return substr( md5( uniqid( '', true ) ), 0, 7 );
}
private static function pad( $v ) {
return array( 'unit' => 'px', 'top' => (string) $v[0], 'right' => (string) $v[1], 'bottom' => (string) $v[0], 'left' => (string) $v[1], 'isLinked' => false );
}
private static function swap_widgets( &$els, $to, &$changed ) {
$found = 0;
foreach ( $els as &$e ) {
if ( isset( $e['widgetType'] ) && 0 === strpos( (string) $e['widgetType'], 'nexvio_thankyou' ) ) {
++$found;
if ( $e['widgetType'] !== $to ) {
$e['widgetType'] = $to;
++$changed;
}
}
if ( ! empty( $e['elements'] ) && is_array( $e['elements'] ) ) {
$found += self::swap_widgets( $e['elements'], $to, $changed );
}
}
return $found;
}
private static function fresh_data( $design ) {
$widget = array( 'id' => self::new_id(), 'elType' => 'widget', 'widgetType' => self::widget_name( $design ), 'settings' => array(), 'elements' => array(), );
$settings = array( 'padding' => self::pad( array( 80, 20 ) ), 'padding_tablet' => self::pad( array( 60, 20 ) ), 'padding_mobile' => self::pad( array( 40, 20 ) ), 'css_classes' => 'nexvio-ty-wrap', );
$container = false;
try {
$container = isset( \Elementor\Plugin::$instance->experiments ) && \Elementor\Plugin::$instance->experiments->is_feature_active( 'container' );
}
catch ( \Throwable $e ) {
$container = false;
}
if ( $container ) {
$settings['content_width'] = 'full';
return array( array( 'id' => self::new_id(), 'elType' => 'container', 'isInner' => false, 'settings' => $settings, 'elements' => array( $widget ) ) );
}
$settings['layout'] = 'full_width';
return array( array( 'id' => self::new_id(), 'elType' => 'section', 'isInner' => false, 'settings' => $settings, 'elements' => array( array( 'id' => self::new_id(), 'elType' => 'column', 'isInner' => false, 'settings' => array( '_column_size' => 100, '_inline_size' => null ), 'elements' => array( $widget ) ) ), ) );
}
public static function setup_page( $id, $design = 0 ) {
$id = (int) $id;
if ( ! $id || 'page' !== get_post_type( $id ) ) {
return false;
}
$design = $design && isset( self::designs()[ (int) $design ] ) ? (int) $design : (int) self::settings()['design'];
update_post_meta( $id, 'site-post-title', 'disabled' );
if ( ! self::elementor_on() ) {
return true;
}
$tpl = (string) get_post_meta( $id, '_wp_page_template', true );
if ( ! in_array( $tpl, array( 'elementor_header_footer', 'elementor_canvas' ), true ) ) {
update_post_meta( $id, '_wp_page_template', 'elementor_header_footer' );
}
$raw = get_post_meta( $id, '_elementor_data', true );
$data = is_string( $raw ) && '' !== $raw ? json_decode( $raw, true ) : ( is_array( $raw ) ? $raw : array() );
$data = is_array( $data ) ? $data : array();
$changed = 0;
$found = $data ? self::swap_widgets( $data, self::widget_name( $design ), $changed ) : 0;
if ( $found ) {
if ( ! $changed ) {
return true;
}
}
else {
if ( $data ) {
update_post_meta( $id, '_nexvio_ty_backup', wp_slash( wp_json_encode( $data ) ) );
}
$data = self::fresh_data( $design );
}
update_post_meta( $id, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );
update_post_meta( $id, '_elementor_edit_mode', 'builder' );
update_post_meta( $id, '_elementor_template_type', 'wp-page' );
if ( defined( 'ELEMENTOR_VERSION' ) ) {
update_post_meta( $id, '_elementor_version', ELEMENTOR_VERSION );
}
delete_post_meta( $id, '_elementor_css' );
try {
if ( isset( \Elementor\Plugin::$instance->files_manager ) ) {
\Elementor\Plugin::$instance->files_manager->clear_cache();
}
}
catch ( \Throwable $e ) {
unset( $e );
}
return true;
}
public static function ajax_create() {
self::guard();
$id = self::ensure_page();
if ( ! $id ) {
wp_send_json_error( array( 'message' => __( 'The page could not be created.', 'nexvio' ) ) );
}
wp_send_json_success( array( 'page' => self::page_info( $id ) ) );
}
public static function preview_url( $design ) {
return add_query_arg( array( 'action' => 'nexvio_ty_preview', 'design' => (int) $design, 'nonce' => wp_create_nonce( self::NONCE ) ), admin_url( 'admin-ajax.php' ) );
}
public static function ajax_preview() {
if ( ! current_user_can( 'manage_options' ) || ! wp_verify_nonce( isset( $_GET['nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['nonce'] ) ) : '', self::NONCE ) ) {
wp_die( esc_html__( 'You are not allowed to do this.', 'nexvio' ), '', array( 'response' => 403 ) );
}
$design = isset( $_GET['design'] ) ? absint( $_GET['design'] ) : 1;
$s = self::settings();
if ( isset( $_GET['accent'] ) && sanitize_hex_color( wp_unslash( $_GET['accent'] ) ) ) {
$s['accent'] = sanitize_hex_color( wp_unslash( $_GET['accent'] ) );
}
nocache_headers();
header( 'Content-Type: text/html; charset=utf-8' );
echo '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
if ( ! wp_style_is( 'nexvio-thankyou', 'registered' ) ) {
wp_register_style( 'nexvio-thankyou', NEXVIO_URL . 'assets/css/thankyou.css', array(), Nexvio_Plugin::ver( 'assets/css/thankyou.css' ) );
}
wp_print_styles( 'nexvio-thankyou' );
echo '<style>body{margin:0;padding:32px 20px;background:#f3f4f8;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif}</style></head><body>';
self::$labels_now = array();
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup is built by Nexvio_Thankyou, which escapes every dynamic value.
echo self::render( $design, self::demo(), $s );
echo '</body></html>';
exit;
}
} 