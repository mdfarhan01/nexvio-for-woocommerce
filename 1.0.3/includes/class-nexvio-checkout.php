<?php
 if ( ! defined( 'ABSPATH' ) ) {
exit;
}
class Nexvio_Checkout {
const OPTION = 'nexvio_checkout_fields';
private static $bypass = false;
public static function sections() {
return array( 'billing' => array( __( 'Billing', 'nexvio' ), 'billing', 'billing_' ), 'shipping' => array( __( 'Shipping', 'nexvio' ), 'shipping', 'shipping_' ), 'additional' => array( __( 'Additional', 'nexvio' ), 'order', 'nx_' ), );
}
public static function types() {
return array( 'text' => array( __( 'Text', 'nexvio' ), 'editor-textcolor', false ), 'textarea' => array( __( 'Text area', 'nexvio' ), 'text', false ), 'number' => array( __( 'Number', 'nexvio' ), 'calculator', false ), 'email' => array( __( 'Email', 'nexvio' ), 'email', false ), 'tel' => array( __( 'Phone', 'nexvio' ), 'phone', false ), 'url' => array( __( 'Link (URL)', 'nexvio' ), 'admin-links', false ), 'password' => array( __( 'Password', 'nexvio' ), 'lock', false ), 'date' => array( __( 'Date', 'nexvio' ), 'calendar-alt', false ), 'time' => array( __( 'Time', 'nexvio' ), 'clock', false ), 'select' => array( __( 'Dropdown', 'nexvio' ), 'arrow-down-alt2', true ), 'multiselect' => array( __( 'Multi select', 'nexvio' ), 'list-view', true ), 'radio' => array( __( 'Radio buttons', 'nexvio' ), 'marker', true ), 'checkbox' => array( __( 'Checkbox', 'nexvio' ), 'yes', false ), 'checkboxgroup' => array( __( 'Checkbox group', 'nexvio' ), 'yes-alt', true ), 'shippingzone' => array( __( 'Shipping zone', 'nexvio' ), 'location-alt', false ), 'deliverydate' => array( __( 'Delivery date', 'nexvio' ), 'calendar', false ), 'deliverytime' => array( __( 'Delivery time', 'nexvio' ), 'clock', false ), 'hidden' => array( __( 'Hidden', 'nexvio' ), 'hidden', false ), 'heading' => array( __( 'Heading', 'nexvio' ), 'heading', false ), 'paragraph' => array( __( 'Paragraph', 'nexvio' ), 'editor-paragraph', false ), );
}
private static function own_types() {
return array( 'multiselect', 'checkboxgroup', 'heading', 'paragraph', 'hidden', 'deliverydate', 'deliverytime' );
}
private static function display_types() {
return array( 'heading', 'paragraph' );
}
public static function widths() {
return array( 'wide' => array( __( 'Full width', 'nexvio' ), 'form-row-wide' ), 'first' => array( __( 'Half (left)', 'nexvio' ), 'form-row-first' ), 'last' => array( __( 'Half (right)', 'nexvio' ), 'form-row-last' ), );
}
public static function validations() {
return array( 'email' => __( 'Email', 'nexvio' ), 'phone' => __( 'Phone', 'nexvio' ), 'postcode' => __( 'Postcode', 'nexvio' ), );
}
public static function defaults() {
return array( 'name' => '', 'type' => 'text', 'label' => '', 'placeholder' => '', 'default' => '', 'description' => '', 'width' => 'wide', 'class' => '', 'required' => false, 'enabled' => true, 'validate' => array(), 'options' => '', 'checked' => false, 'heading_tag' => 'h3', 'show_price' => true, 'charge' => false, 'delivery' => array(), 'show_in_email' => true, 'show_in_order' => true, 'custom' => false, );
}
public static function saved( $section = '' ) {
$all = get_option( self::OPTION, array() );
$all = is_array( $all ) ? $all : array();
if ( '' === $section ) {
return $all;
}
return isset( $all[ $section ] ) && is_array( $all[ $section ] ) ? $all[ $section ] : array();
}
public static function sanitize_section( $section, $rows ) {
$def = self::defaults();
$types = self::types();
$widths = self::widths();
$tags = array( 'h2', 'h3', 'h4', 'h5', 'h6', 'p' );
$out = array();
$sec_def = self::sections();
$prefix = isset( $sec_def[ $section ] ) ? $sec_def[ $section ][2] : '';
if ( ! is_array( $rows ) ) {
return $out;
}
foreach ( $rows as $row ) {
if ( ! is_array( $row ) ) {
continue;
}
$custom = ! empty( $row['custom'] );
$name = isset( $row['name'] ) ? self::clean_name( $row['name'] ) : '';
if ( '' === $name || isset( $out[ $name ] ) ) {
continue;
}
$f = $def;
$f['name'] = $name;
$f['custom'] = $custom;
$type = isset( $row['type'] ) ? sanitize_key( $row['type'] ) : 'text';
$f['type'] = ! $custom ? '' : ( isset( $types[ $type ] ) ? $type : 'text' );
$f['label'] = isset( $row['label'] ) ? sanitize_text_field( wp_unslash( $row['label'] ) ) : '';
$f['placeholder'] = isset( $row['placeholder'] ) ? sanitize_text_field( wp_unslash( $row['placeholder'] ) ) : '';
$f['default'] = isset( $row['default'] ) ? sanitize_text_field( wp_unslash( $row['default'] ) ) : '';
$f['description'] = isset( $row['description'] ) ? wp_kses_post( wp_unslash( $row['description'] ) ) : '';
$w = isset( $row['width'] ) ? sanitize_key( $row['width'] ) : 'wide';
$f['width'] = isset( $widths[ $w ] ) ? $w : 'wide';
$f['class'] = isset( $row['class'] ) ? implode( ' ', array_filter( array_map( 'sanitize_html_class', preg_split( '/[\s,]+/', (string) $row['class'] ) ) ) ) : '';
$f['required'] = ! empty( $row['required'] );
$f['enabled'] = ! isset( $row['enabled'] ) || ! empty( $row['enabled'] );
$f['checked'] = ! empty( $row['checked'] );
$f['show_price'] = ! isset( $row['show_price'] ) || ! empty( $row['show_price'] );
$f['charge'] = ! empty( $row['charge'] );
$f['delivery'] = $custom && in_array( $f['type'], array( 'deliverydate', 'deliverytime' ), true ) ? Nexvio_Delivery::clean( $f['type'], isset( $row['delivery'] ) ? wp_unslash( $row['delivery'] ) : array() ) : array();
$f['validate'] = isset( $row['validate'] ) && is_array( $row['validate'] ) ? array_values( array_intersect( array_map( 'sanitize_key', $row['validate'] ), array_keys( self::validations() ) ) ) : array();
$f['options'] = isset( $row['options'] ) ? self::clean_options( $row['options'] ) : '';
$tag = isset( $row['heading_tag'] ) ? sanitize_key( $row['heading_tag'] ) : 'h3';
$f['heading_tag'] = in_array( $tag, $tags, true ) ? $tag : 'h3';
$f['show_in_email'] = ! isset( $row['show_in_email'] ) || ! empty( $row['show_in_email'] );
$f['show_in_order'] = ! isset( $row['show_in_order'] ) || ! empty( $row['show_in_order'] );
if ( $custom && in_array( $f['type'], self::display_types(), true ) ) {
$f['required'] = false;
}
if ( $custom && $prefix && 0 !== strpos( $name, $prefix ) ) {
$name = $prefix . $name;
if ( isset( $out[ $name ] ) ) {
continue;
}
$f['name'] = $name;
}
$out[ $name ] = $f;
}
return $out;
}
private static function clean_name( $name ) {
$name = strtolower( trim( (string) $name ) );
$name = preg_replace( '/[^a-z0-9_\-]/', '_', $name );
return trim( (string) $name, '_' ) === '' ? '' : substr( $name, 0, 60 );
}
private static function clean_options( $text ) {
$lines = preg_split( '/\r\n|\r|\n/', (string) wp_unslash( $text ) );
$out = array();
foreach ( $lines as $line ) {
$line = trim( sanitize_text_field( $line ) );
if ( '' !== $line ) {
$out[] = $line;
}
}
return implode( "\n", $out );
}
public static function parse_options( $text ) {
$out = array();
foreach ( preg_split( '/\r\n|\r|\n/', (string) $text ) as $line ) {
$line = trim( $line );
if ( '' === $line ) {
continue;
}
if ( false !== strpos( $line, '|' ) ) {
list( $v, $l ) = array_map( 'trim', explode( '|', $line, 2 ) );
}
else {
$v = $line;
$l = $line;
}
$out[ $v ] = '' === $l ? $v : $l;
}
return $out;
}
public static function core_fields( $section ) {
$def = self::sections();
if ( ! isset( $def[ $section ] ) || ! function_exists( 'WC' ) || ! WC()->countries ) {
return array();
}
self::$bypass = true;
$fields = array();
if ( 'additional' === $section ) {
// phpcs:ignore WordPress.WP.I18n.TextDomainMismatch -- Intentionally reuses WooCommerce's own translation so labels match the store language.
$fields = array( 'order_comments' => array( 'type' => 'textarea', 'label' => __( 'Order notes', 'woocommerce' ), 'placeholder' => __( 'Notes about your order, e.g. special notes for delivery.', 'woocommerce' ), 'class' => array( 'notes' ), 'required' => false, ), );
}
else {
$fields = WC()->countries->get_address_fields( WC()->countries->get_base_country(), $def[ $section ][2] );
}
self::$bypass = false;
$out = array();
foreach ( (array) $fields as $name => $f ) {
$out[ $name ] = self::from_core( $name, $f );
}
return $out;
}
private static function from_core( $name, $f ) {
$p = self::defaults();
$p['name'] = $name;
$p['type'] = isset( $f['type'] ) ? (string) $f['type'] : 'text';
$p['label'] = isset( $f['label'] ) ? (string) $f['label'] : $name;
$p['placeholder'] = isset( $f['placeholder'] ) ? (string) $f['placeholder'] : '';
$p['required'] = ! empty( $f['required'] );
$classes = isset( $f['class'] ) ? (array) $f['class'] : array();
$p['width'] = in_array( 'form-row-first', $classes, true ) ? 'first' : ( in_array( 'form-row-last', $classes, true ) ? 'last' : 'wide' );
$p['custom'] = false;
return $p;
}
public static function builder_fields( $section ) {
$core = self::core_fields( $section );
$saved = self::saved( $section );
$out = array();
foreach ( $saved as $name => $p ) {
$p = array_merge( self::defaults(), $p );
if ( ! $p['custom'] ) {
if ( ! isset( $core[ $name ] ) ) {
continue;
}
$p['type'] = $core[ $name ]['type'];
$p['_core'] = array( 'label' => $core[ $name ]['label'], 'placeholder' => $core[ $name ]['placeholder'], 'required' => $core[ $name ]['required'], );
}
$out[ $name ] = $p;
}
foreach ( $core as $name => $p ) {
if ( ! isset( $out[ $name ] ) ) {
$p['_core'] = array( 'label' => $p['label'], 'placeholder' => $p['placeholder'], 'required' => $p['required'] );
$out[ $name ] = $p;
}
}
return array_values( $out );
}
public static function init() {
add_filter( 'woocommerce_checkout_fields', array( __CLASS__, 'checkout_fields' ), 9990 );
add_filter( 'woocommerce_enable_order_notes_field', array( __CLASS__, 'order_notes_switch' ), 9990 );
foreach ( self::own_types() as $t ) {
add_filter( 'woocommerce_form_field_nexvio_' . $t, array( __CLASS__, 'render_' . $t ), 10, 4 );
}
add_action( 'woocommerce_after_checkout_validation', array( __CLASS__, 'validate' ), 10, 2 );
add_action( 'woocommerce_checkout_create_order', array( __CLASS__, 'save' ), 10, 2 );
add_action( 'woocommerce_admin_order_data_after_billing_address', array( __CLASS__, 'admin_billing' ) );
add_action( 'woocommerce_admin_order_data_after_shipping_address', array( __CLASS__, 'admin_shipping' ) );
add_action( 'woocommerce_admin_order_data_after_order_details', array( __CLASS__, 'admin_additional' ) );
add_action( 'woocommerce_order_details_after_customer_details', array( __CLASS__, 'customer_view' ) );
add_filter( 'woocommerce_email_order_meta_fields', array( __CLASS__, 'email_fields' ), 20, 3 );
add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ) );
add_filter( 'woocommerce_cart_shipping_packages', array( __CLASS__, 'zone_packages' ), 20 );
add_filter( 'woocommerce_package_rates', array( __CLASS__, 'zone_rates' ), 20, 2 );
}
private static function has_charging_zone() {
foreach ( self::active_custom() as $p ) {
if ( 'shippingzone' === $p['type'] && ! empty( $p['charge'] ) ) {
return true;
}
}
return false;
}
public static function assets() {
if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
return;
}
if ( self::has_charging_zone() ) {
wp_register_script( 'nexvio-checkout-zone', false, array( 'jquery' ), NEXVIO_VERSION, true );
wp_enqueue_script( 'nexvio-checkout-zone' );
wp_add_inline_script( 'nexvio-checkout-zone', 'jQuery(function($){$(document.body).on("change","select[data-nexvio-zone]",function(){$(document.body).trigger("update_checkout");});});' );
}
wp_enqueue_style( 'nexvio-checkout-fields', NEXVIO_URL . 'assets/css/checkout-fields.css', array(), Nexvio_Plugin::ver( 'assets/css/checkout-fields.css' ) );
foreach ( self::active_custom() as $p ) {
if ( in_array( $p['type'], array( 'deliverydate', 'deliverytime' ), true ) ) {
wp_enqueue_script( 'nexvio-checkout-delivery', NEXVIO_URL . 'assets/js/checkout-delivery.js', array(), Nexvio_Plugin::ver( 'assets/js/checkout-delivery.js' ), true );
break;
}
}
}
private static function width_class( $w ) {
$all = self::widths();
return isset( $all[ $w ] ) ? $all[ $w ][1] : 'form-row-wide';
}
public static function checkout_fields( $fields ) {
if ( self::$bypass || ! is_array( $fields ) ) {
return $fields;
}
foreach ( self::sections() as $key => $s ) {
$saved = self::saved( $key );
if ( ! $saved ) {
continue;
}
$fs = $s[1];
$base = isset( $fields[ $fs ] ) && is_array( $fields[ $fs ] ) ? $fields[ $fs ] : array();
$new = array();
$i = 0;
foreach ( $saved as $name => $p ) {
$p = array_merge( self::defaults(), $p );
++$i;
if ( empty( $p['custom'] ) ) {
if ( ! isset( $base[ $name ] ) ) {
continue;
}
if ( empty( $p['enabled'] ) ) {
unset( $base[ $name ] );
continue;
}
$f = $base[ $name ];
if ( '' !== $p['label'] ) {
$f['label'] = $p['label'];
}
if ( '' !== $p['placeholder'] ) {
$f['placeholder'] = $p['placeholder'];
}
$f['required'] = ! empty( $p['required'] );
$f['class'] = self::merge_classes( isset( $f['class'] ) ? (array) $f['class'] : array(), $p );
$f['priority'] = $i * 10;
$new[ $name ] = $f;
unset( $base[ $name ] );
}
elseif ( ! empty( $p['enabled'] ) ) {
$new[ $name ] = self::build_field( $p, $i * 10 );
}
}
foreach ( $base as $name => $f ) {
$f['priority'] = 1000 + ( isset( $f['priority'] ) ? (int) $f['priority'] : 0 );
$new[ $name ] = $f;
}
$fields[ $fs ] = $new;
}
return $fields;
}
private static function merge_classes( $classes, $p ) {
$classes = array_diff( $classes, array( 'form-row-wide', 'form-row-first', 'form-row-last' ) );
$classes[] = self::width_class( $p['width'] );
foreach ( array_filter( explode( ' ', (string) $p['class'] ) ) as $c ) {
$classes[] = $c;
}
$classes[] = 'nexvio-cf';
return array_values( array_unique( $classes ) );
}
private static function build_field( $p, $priority ) {
$type = $p['type'];
$f = array( 'type' => in_array( $type, self::own_types(), true ) ? 'nexvio_' . $type : $type, 'label' => $p['label'], 'placeholder' => $p['placeholder'], 'required' => ! empty( $p['required'] ), 'default' => $p['default'], 'class' => self::merge_classes( array(), $p ), 'priority' => $priority, 'validate' => (array) $p['validate'], 'nexvio' => array( 'name' => $p['name'], 'ftype' => $type, 'options' => self::parse_options( $p['options'] ), 'description' => $p['description'], 'tag' => $p['heading_tag'], 'checked' => ! empty( $p['checked'] ), 'props' => $p, ), );
if ( '' !== $p['description'] && ! in_array( $type, self::display_types(), true ) ) {
$f['description'] = $p['description'];
}
if ( in_array( $type, array( 'select', 'radio' ), true ) ) {
$f['options'] = self::parse_options( $p['options'] );
if ( 'select' === $type && '' !== $p['placeholder'] ) {
$f['options'] = array( '' => $p['placeholder'] ) + $f['options'];
}
}
if ( 'shippingzone' === $type ) {
$f['type'] = 'select';
$f['default'] = '';
$f['options'] = array( '' => '' !== $p['placeholder'] ? $p['placeholder'] : __( 'Select a shipping zone', 'nexvio' ) ) + self::zone_options( $p );
if ( ! empty( $p['charge'] ) ) {
$f['class'][] = 'update_totals_on_change';
$f['custom_attributes'] = array( 'data-nexvio-zone' => '1' );
}
}
if ( 'checkbox' === $type ) {
$f['default'] = ! empty( $p['checked'] ) ? 1 : 0;
}
if ( 'hidden' === $type ) {
$f['label'] = '';
$f['required'] = false;
}
if ( in_array( $type, self::display_types(), true ) ) {
$f['required'] = false;
}
return $f;
}
public static function order_notes_switch( $show ) {
$saved = self::saved( 'additional' );
if ( ! $saved ) {
return $show;
}
foreach ( $saved as $p ) {
if ( ! isset( $p['enabled'] ) || ! empty( $p['enabled'] ) ) {
return true;
}
}
return false;
}
private static function zone_price( $methods ) {
$costs = array();
foreach ( $methods as $m ) {
if ( 'free_shipping' === $m->id ) {
$costs[] = 0.0;
continue;
}
$cost = (string) $m->get_option( 'cost', '' );
if ( in_array( $m->id, array( 'flat_rate', 'local_pickup' ), true ) && '' === trim( $cost ) ) {
$costs[] = 0.0;
}
elseif ( preg_match( '/^\s*[0-9][0-9.,]*\s*$/', $cost ) ) {
$costs[] = (float) wc_format_decimal( $cost );
}
}
if ( ! $costs ) {
return '';
}
$fmt = function ( $n ) {
return $n > 0 ? html_entity_decode( wp_strip_all_tags( wc_price( $n ) ), ENT_QUOTES, 'UTF-8' ) : __( 'Free', 'nexvio' );
}
;
$min = min( $costs );
$max = max( $costs );
return $min === $max ? $fmt( $min ) : sprintf( /* translators: %s: lowest price. */ __( 'from %s', 'nexvio' ), $fmt( $min ) );
}
public static function zone_options( $p ) {
$out = array();
if ( ! class_exists( 'WC_Shipping_Zones' ) ) {
return $out;
}
$zones = array();
foreach ( WC_Shipping_Zones::get_zones() as $z ) {
$zones[] = new WC_Shipping_Zone( $z['zone_id'] );
}
$zones[] = new WC_Shipping_Zone( 0 );
$show = ! isset( $p['show_price'] ) || ! empty( $p['show_price'] );
foreach ( $zones as $zone ) {
$methods = $zone->get_shipping_methods( true );
if ( ! $methods ) {
continue;
}
$label = $zone->get_zone_name();
$price = $show ? self::zone_price( $methods ) : '';
$out[ (string) $zone->get_id() ] = '' !== $price ? $label . ' — ' . $price : $label;
}
return $out;
}
private static function chosen_zone() {
$names = array();
foreach ( self::active_custom() as $name => $p ) {
if ( 'shippingzone' === $p['type'] && ! empty( $p['charge'] ) ) {
$names[] = $name;
}
}
if ( ! $names ) {
return '';
}
$data = array();
// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Read while WooCommerce processes its own checkout update request, which verifies its own nonce.
if ( isset( $_POST['post_data'] ) && is_string( $_POST['post_data'] ) ) {
// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Read while WooCommerce processes its own checkout update request, which verifies its own nonce. WooCommerce checkout form data; only values that pass ctype_digit() are used.
parse_str( wp_unslash( $_POST['post_data'] ), $data );
}
foreach ( $names as $n ) {
// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Read while WooCommerce processes its own checkout update request, which verifies its own nonce. WooCommerce checkout form data; only values that pass ctype_digit() are used.
$v = isset( $data[ $n ] ) ? $data[ $n ] : ( isset( $_POST[ $n ] ) ? wp_unslash( $_POST[ $n ] ) : '' );
$v = is_scalar( $v ) ? trim( (string) $v ) : '';
if ( '' !== $v && ctype_digit( $v ) ) {
return $v;
}
}
return '';
}
public static function zone_packages( $packages ) {
if ( ! is_array( $packages ) ) {
return $packages;
}
$z = self::chosen_zone();
if ( function_exists( 'WC' ) && WC()->session ) {
$last = (string) WC()->session->get( 'nexvio_zone_last', '' );
if ( $last !== $z ) {
WC()->session->set( 'nexvio_zone_last', $z );
foreach ( array_keys( $packages ) as $k ) {
WC()->session->set( 'shipping_for_package_' . $k, null );
}
}
}
if ( '' === $z ) {
return $packages;
}
foreach ( $packages as $k => $pk ) {
$packages[ $k ]['nexvio_zone'] = $z;
}
return $packages;
}
public static function zone_rates( $rates, $package ) {
if ( ! isset( $package['nexvio_zone'] ) || '' === $package['nexvio_zone'] || ! class_exists( 'WC_Shipping_Zone' ) ) {
return $rates;
}
$zone = new WC_Shipping_Zone( (int) $package['nexvio_zone'] );
if ( (int) $package['nexvio_zone'] !== (int) $zone->get_id() ) {
return $rates;
}
$out = array();
foreach ( $zone->get_shipping_methods( true ) as $m ) {
$out = $out + $m->get_rates_for_package( $package );
}
return $out ? $out : $rates;
}
private static function row_open( $key, $args, $extra = '' ) {
$classes = isset( $args['class'] ) ? (array) $args['class'] : array();
$classes = array_merge( array( 'form-row', 'nexvio-cf', $extra ), $classes );
if ( ! empty( $args['required'] ) ) {
$classes[] = 'validate-required';
}
return '<p class="' . esc_attr( implode( ' ', array_filter( array_unique( $classes ) ) ) ) . '" id="' . esc_attr( $key ) . '_field" data-priority="' . esc_attr( isset( $args['priority'] ) ? $args['priority'] : '' ) . '">';
}
private static function label_html( $key, $args ) {
if ( empty( $args['label'] ) ) {
return '';
}
// phpcs:ignore WordPress.WP.I18n.TextDomainMismatch -- Intentionally reuses WooCommerce's own translation so labels match the store language.
$req = ! empty( $args['required'] ) ? ' <abbr class="required" title="' . esc_attr__( 'required', 'woocommerce' ) . '">*</abbr>' : ' <span class="optional">(' . esc_html__( 'optional', 'woocommerce' ) . ')</span>';
return '<label class="nexvio-cf__label">' . wp_kses_post( $args['label'] ) . $req . '</label>';
}
private static function desc_html( $args ) {
return ! empty( $args['description'] ) ? '<span class="description nexvio-cf__hint">' . wp_kses_post( $args['description'] ) . '</span>' : '';
}
private static function meta( $args ) {
return isset( $args['nexvio'] ) && is_array( $args['nexvio'] ) ? $args['nexvio'] : array();
}
public static function render_heading( $field, $key, $args, $value ) {
$m = self::meta( $args );
$tag = isset( $m['tag'] ) && in_array( $m['tag'], array( 'h2', 'h3', 'h4', 'h5', 'h6', 'p' ), true ) ? $m['tag'] : 'h3';
$out = '<div class="form-row form-row-wide nexvio-cf nexvio-cf--heading ' . esc_attr( implode( ' ', array_diff( (array) ( isset( $args['class'] ) ? $args['class'] : array() ), array( 'form-row-first', 'form-row-last' ) ) ) ) . '" id="' . esc_attr( $key ) . '_field" data-priority="' . esc_attr( isset( $args['priority'] ) ? $args['priority'] : '' ) . '">';
$out .= '<' . $tag . ' class="nexvio-cf__heading">' . esc_html( $args['label'] ) . '</' . $tag . '>';
if ( ! empty( $m['description'] ) ) {
$out .= '<p class="nexvio-cf__hint">' . wp_kses_post( $m['description'] ) . '</p>';
}
return $out . '</div>';
}
public static function render_hidden( $field, $key, $args, $value ) {
$v = '' === $value || null === $value ? ( isset( $args['default'] ) ? $args['default'] : '' ) : $value;
return '<input type="hidden" class="nexvio-cf nexvio-cf--hidden" name="' . esc_attr( $key ) . '" id="' . esc_attr( $key ) . '" value="' . esc_attr( is_array( $v ) ? '' : $v ) . '">';
}
public static function render_paragraph( $field, $key, $args, $value ) {
$m = self::meta( $args );
$out = '<div class="form-row form-row-wide nexvio-cf nexvio-cf--paragraph" id="' . esc_attr( $key ) . '_field" data-priority="' . esc_attr( isset( $args['priority'] ) ? $args['priority'] : '' ) . '">';
$txt = ! empty( $m['description'] ) ? $m['description'] : $args['label'];
return $out . '<div class="nexvio-cf__text">' . wp_kses_post( wpautop( (string) $txt ) ) . '</div></div>';
}
public static function render_multiselect( $field, $key, $args, $value ) {
$m = self::meta( $args );
$opts = isset( $m['options'] ) ? $m['options'] : array();
$checked = self::as_list( $value, isset( $args['default'] ) ? $args['default'] : '' );
$out = self::row_open( $key, $args, 'nexvio-cf--multiselect' ) . self::label_html( $key, $args );
$out .= '<span class="woocommerce-input-wrapper"><select multiple="multiple" name="' . esc_attr( $key ) . '[]" id="' . esc_attr( $key ) . '" class="select nexvio-cf__multi" data-placeholder="' . esc_attr( isset( $args['placeholder'] ) ? $args['placeholder'] : '' ) . '">';
foreach ( $opts as $v => $l ) {
$out .= '<option value="' . esc_attr( $v ) . '"' . selected( in_array( (string) $v, $checked, true ), true, false ) . '>' . esc_html( $l ) . '</option>';
}
return $out . '</select>' . self::desc_html( $args ) . '</span></p>';
}
public static function render_checkboxgroup( $field, $key, $args, $value ) {
$m = self::meta( $args );
$opts = isset( $m['options'] ) ? $m['options'] : array();
$checked = self::as_list( $value, isset( $args['default'] ) ? $args['default'] : '' );
$out = self::row_open( $key, $args, 'nexvio-cf--checkboxgroup' ) . self::label_html( $key, $args );
$out .= '<span class="woocommerce-input-wrapper nexvio-cf__choices">';
$i = 0;
foreach ( $opts as $v => $l ) {
++$i;
$id = $key . '_' . $i;
$out .= '<label class="nexvio-cf__choice" for="' . esc_attr( $id ) . '"><input type="checkbox" id="' . esc_attr( $id ) . '" name="' . esc_attr( $key ) . '[]" value="' . esc_attr( $v ) . '"' . checked( in_array( (string) $v, $checked, true ), true, false ) . '><span>' . esc_html( $l ) . '</span></label>';
}
return $out . self::desc_html( $args ) . '</span></p>';
}
private static function date_field_name() {
foreach ( self::active_custom() as $name => $p ) {
if ( 'deliverydate' === $p['type'] ) {
return $name;
}
}
return '';
}
public static function render_deliverydate( $field, $key, $args, $value ) {
$m = self::meta( $args );
$p = isset( $m['props'] ) ? $m['props'] : array();
$cfg = Nexvio_Delivery::date_config( $p, $key );
$val = is_string( $value ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ? $value : '';
$out = self::row_open( $key, $args, 'nexvio-cf--deliverydate' ) . self::label_html( $key, $args );
$out .= '<span class="woocommerce-input-wrapper"><span class="nxd" data-nxd="' . esc_attr( wp_json_encode( $cfg ) ) . '">';
$out .= '<input type="hidden" class="nxd__input" name="' . esc_attr( $key ) . '" id="' . esc_attr( $key ) . '" value="' . esc_attr( $val ) . '">';
$out .= '<button type="button" class="nxd__trigger" aria-haspopup="dialog" aria-expanded="false"><svg class="nxd__ico" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M7 3v3M17 3v3M4 9h16M6 5h12a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2z"/></svg><span class="nxd__value">' . esc_html( $cfg['placeholder'] ) . '</span><svg class="nxd__chev" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg></button>';
$out .= '<span class="nxd__pop" role="dialog" aria-label="' . esc_attr( $cfg['i18n']['pick'] ) . '" hidden></span>';
$out .= '<noscript><input type="date" name="' . esc_attr( $key ) . '" min="' . esc_attr( $cfg['min'] ) . '" max="' . esc_attr( $cfg['max'] ) . '" value="' . esc_attr( $val ) . '"></noscript>';
return $out . '</span>' . self::desc_html( $args ) . '</span></p>';
}
public static function render_deliverytime( $field, $key, $args, $value ) {
$m = self::meta( $args );
$p = isset( $m['props'] ) ? $m['props'] : array();
$cfg = Nexvio_Delivery::time_config( $p, $key, self::date_field_name() );
$out = self::row_open( $key, $args, 'nexvio-cf--deliverytime' ) . self::label_html( $key, $args );
$out .= '<span class="woocommerce-input-wrapper"><select name="' . esc_attr( $key ) . '" id="' . esc_attr( $key ) . '" class="select nxt" data-nxt="' . esc_attr( wp_json_encode( $cfg ) ) . '" data-value="' . esc_attr( is_string( $value ) ? $value : '' ) . '">';
$out .= '<option value="">' . esc_html( $cfg['placeholder'] ) . '</option>';
foreach ( $cfg['slots'] as $s ) {
$out .= '<option value="' . esc_attr( $s['k'] ) . '"' . selected( (string) $value, $s['k'], false ) . '>' . esc_html( $s['l'] ) . '</option>';
}
return $out . '</select>' . self::desc_html( $args ) . '</span></p>';
}
private static function as_list( $value, $default ) {
$v = '' === $value || null === $value ? $default : $value;
if ( is_array( $v ) ) {
return array_map( 'strval', $v );
}
return array_values( array_filter( array_map( 'trim', explode( ',', (string) $v ) ), 'strlen' ) );
}
private static function active_custom() {
$out = array();
foreach ( self::sections() as $key => $s ) {
foreach ( self::saved( $key ) as $name => $p ) {
$p = array_merge( self::defaults(), $p );
if ( ! empty( $p['custom'] ) && ! empty( $p['enabled'] ) && ! in_array( $p['type'], self::display_types(), true ) ) {
$p['section'] = $key;
$out[ $name ] = $p;
}
}
}
return $out;
}
public static function validate( $data, $errors ) {
foreach ( self::active_custom() as $name => $p ) {
if ( 'shipping' === $p['section'] && ( ! function_exists( 'WC' ) || ! WC()->cart || ! WC()->cart->needs_shipping_address() || empty( $data['ship_to_different_address'] ) ) ) {
continue;
}
if ( 'deliverydate' === $p['type'] ) {
$dv = isset( $data[ $name ] ) ? (string) $data[ $name ] : '';
if ( '' !== $dv && ! Nexvio_Delivery::date_ok( $p, $dv, $name ) ) {
$errors->add( $name . '_invalid', sprintf( /* translators: %s: field label. */ __( '%s: please choose an available date.', 'nexvio' ), '<strong>' . esc_html( $p['label'] ? $p['label'] : $name ) . '</strong>' ), array( 'id' => $name ) );
}
continue;
}
if ( 'deliverytime' === $p['type'] ) {
$tv = isset( $data[ $name ] ) ? (string) $data[ $name ] : '';
$dn = self::date_field_name();
$dd = '' !== $dn && isset( $data[ $dn ] ) ? (string) $data[ $dn ] : '';
if ( '' !== $tv && ! Nexvio_Delivery::slot_ok( $p, $tv, $dd, $dn, $name ) ) {
$errors->add( $name . '_invalid', sprintf( /* translators: %s: field label. */ __( '%s: please choose an available time.', 'nexvio' ), '<strong>' . esc_html( $p['label'] ? $p['label'] : $name ) . '</strong>' ), array( 'id' => $name ) );
}
continue;
}
if ( 'shippingzone' === $p['type'] ) {
$zv = isset( $data[ $name ] ) ? (string) $data[ $name ] : '';
if ( '' !== $zv ) {
$zones = self::zone_options( $p );
if ( ! isset( $zones[ $zv ] ) ) {
$errors->add( $name . '_invalid', sprintf( /* translators: %s: field label. */ __( '%s is not a valid choice.', 'nexvio' ), '<strong>' . esc_html( $p['label'] ? $p['label'] : $name ) . '</strong>' ), array( 'id' => $name ) );
}
}
continue;
}
if ( empty( $p['required'] ) || ! in_array( $p['type'], array( 'multiselect', 'checkboxgroup' ), true ) ) {
continue;
}
$v = isset( $data[ $name ] ) ? $data[ $name ] : '';
if ( ( is_array( $v ) && ! array_filter( $v, 'strlen' ) ) || '' === $v ) {
// phpcs:ignore WordPress.WP.I18n.TextDomainMismatch -- Intentionally reuses WooCommerce's own translation so labels match the store language.
$errors->add( $name . '_required', sprintf( /* translators: %s: field label. */ __( '%s is a required field.', 'woocommerce' ), '<strong>' . esc_html( $p['label'] ? $p['label'] : $name ) . '</strong>' ), array( 'id' => $name ) );
}
}
}
public static function save( $order, $data ) {
foreach ( self::active_custom() as $name => $p ) {
if ( ! isset( $data[ $name ] ) ) {
continue;
}
$v = $data[ $name ];
if ( is_array( $v ) ) {
$v = implode( ', ', array_filter( array_map( 'wc_clean', $v ), 'strlen' ) );
}
if ( '' === $v && 'checkbox' !== $p['type'] ) {
continue;
}
if ( 'shippingzone' === $p['type'] ) {
$zones = self::zone_options( $p );
$v = isset( $zones[ (string) $v ] ) ? $zones[ (string) $v ] : '';
if ( '' === $v ) {
continue;
}
}
$order->update_meta_data( $name, $v );
}
}
private static function order_values( $order, $section = '', $only = '' ) {
$out = array();
foreach ( self::active_custom_all() as $name => $p ) {
if ( '' !== $section && $p['section'] !== $section ) {
continue;
}
if ( '' !== $only && empty( $p[ $only ] ) ) {
continue;
}
$v = $order->get_meta( $name );
if ( '' === $v || null === $v ) {
continue;
}
if ( 'checkbox' === $p['type'] ) {
$v = $v ? __( 'Yes', 'nexvio' ) : __( 'No', 'nexvio' );
}
elseif ( in_array( $p['type'], array( 'select', 'radio', 'multiselect', 'checkboxgroup' ), true ) ) {
$opts = self::parse_options( $p['options'] );
$v = implode( ', ', array_map( function ( $x ) use ( $opts ) {
$x = trim( $x );
return isset( $opts[ $x ] ) ? $opts[ $x ] : $x;
}
, explode( ',', (string) $v ) ) );
}
elseif ( 'deliverydate' === $p['type'] ) {
$v = Nexvio_Delivery::date_label( (string) $v );
}
elseif ( 'deliverytime' === $p['type'] ) {
$v = Nexvio_Delivery::slot_label( $p, (string) $v );
}
elseif ( 'password' === $p['type'] ) {
$v = str_repeat( '•', 8 );
}
$out[ $name ] = array( 'label' => $p['label'] ? $p['label'] : $name, 'value' => $v );
}
return $out;
}
private static function active_custom_all() {
$out = array();
foreach ( self::sections() as $key => $s ) {
foreach ( self::saved( $key ) as $name => $p ) {
$p = array_merge( self::defaults(), $p );
if ( ! empty( $p['custom'] ) && ! in_array( $p['type'], self::display_types(), true ) ) {
$p['section'] = $key;
$out[ $name ] = $p;
}
}
}
return $out;
}
private static function admin_list( $order, $section ) {
$rows = self::order_values( $order, $section );
if ( ! $rows ) {
return;
}
echo '<div class="nexvio-cf-admin">';
foreach ( $rows as $r ) {
echo '<p><strong>' . esc_html( $r['label'] ) . ':</strong> ' . esc_html( $r['value'] ) . '</p>';
}
echo '</div>';
}
public static function admin_billing( $order ) {
self::admin_list( $order, 'billing' );
}
public static function admin_shipping( $order ) {
self::admin_list( $order, 'shipping' );
}
public static function admin_additional( $order ) {
self::admin_list( $order, 'additional' );
}
public static function order_rows( $order ) {
return self::order_values( $order );
}
public static function customer_view( $order ) {
$rows = self::order_values( $order, '', 'show_in_order' );
if ( ! $rows ) {
return;
}
echo '<section class="nexvio-cf-order"><h2 class="woocommerce-column__title">' . esc_html__( 'Additional information', 'nexvio' ) . '</h2><dl class="nexvio-cf-order__list">';
foreach ( $rows as $r ) {
echo '<dt>' . esc_html( $r['label'] ) . '</dt><dd>' . esc_html( $r['value'] ) . '</dd>';
}
echo '</dl></section>';
}
public static function email_fields( $fields, $sent_to_admin, $order ) {
if ( ! is_object( $order ) || ! method_exists( $order, 'get_meta' ) ) {
return $fields;
}
foreach ( self::order_values( $order, '', 'show_in_email' ) as $name => $r ) {
$fields[ 'nexvio_' . $name ] = array( 'label' => $r['label'], 'value' => $r['value'] );
}
return $fields;
}
} 