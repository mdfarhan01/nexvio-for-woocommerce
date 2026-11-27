<?php
 if ( ! defined( 'ABSPATH' ) ) {
exit;
}
class Nexvio_Epo_Settings {
const OPTION = 'nexvio_epo_settings';
private static $cache = null;
public static function upload_types() {
return array( 'jpg' => 'JPG / JPEG', 'png' => 'PNG', 'gif' => 'GIF', 'webp' => 'WebP', 'pdf' => 'PDF', 'doc' => 'DOC / DOCX', 'txt' => 'TXT', 'zip' => 'ZIP', );
}
public static function schema() {
return array( 'general' => array( __( 'Behaviour', 'nexvio' ), 'dashicons-admin-generic', __( 'Where the extra fields appear on the product page and what happens in shop lists.', 'nexvio' ), array( 'auto_display' => array( 'toggle', __( 'Show automatically on product pages', 'nexvio' ), 'no', array( 'help' => __( 'Puts the fields above the Add to cart button without Elementor. Leave off when you use the "Extra Options" widget in your Elementor product template.', 'nexvio' ) ) ), 'position' => array( 'select', __( 'Automatic position', 'nexvio' ), 'before_button', array( 'options' => array( 'before_button' => __( 'Above the Add to cart button', 'nexvio' ), 'after_button' => __( 'Below the Add to cart button', 'nexvio' ), 'before_form' => __( 'Above the whole cart form', 'nexvio' ) ) ) ), 'loop_button' => array( 'toggle', __( '"Select options" button in shop lists', 'nexvio' ), 'yes', array( 'help' => __( 'Products that have required fields cannot be added from a list, so the button opens the product page instead.', 'nexvio' ) ) ), 'show_prices' => array( 'toggle', __( 'Show the price next to each choice', 'nexvio' ), 'yes', array( 'help' => __( 'For example "Large (+€5.00)".', 'nexvio' ) ) ), 'cart_prices' => array( 'toggle', __( 'Show option prices in the cart', 'nexvio' ), 'yes' ), 'req_mark' => array( 'toggle', __( 'Mark required fields with *', 'nexvio' ), 'yes' ), ), ), 'totals' => array( __( 'Totals box', 'nexvio' ), 'dashicons-calculator', __( 'A box under the fields that adds the options to the product price while the customer chooses.', 'nexvio' ), array( 'show_totals' => array( 'toggle', __( 'Show the totals box', 'nexvio' ), 'yes' ), 'totals_price' => array( 'toggle', __( 'Show the product price in it', 'nexvio' ), 'yes' ), 'totals_hide' => array( 'toggle', __( 'Hide it when the options cost nothing', 'nexvio' ), 'no' ), ), ), 'look' => array( __( 'Look', 'nexvio' ), 'dashicons-art', __( 'Choose Light or Dark, then pick your main colour. A colour set in the Elementor widget replaces it for that widget only.', 'nexvio' ), array( 'preset' => array( 'style', __( 'Style', 'nexvio' ), 'light', array( 'options' => array( 'light' => __( 'Light', 'nexvio' ), 'dark' => __( 'Dark', 'nexvio' ) ) ) ), 'main_color' => array( 'color', __( 'Main colour', 'nexvio' ), '#3d8bff', array( 'help' => __( 'Selected choices, focus rings, sliders and the totals box. Text on it turns black or white automatically so it stays readable.', 'nexvio' ) ) ), 'radius' => array( 'select', __( 'Corners', 'nexvio' ), '8', array( 'options' => array( '0' => __( 'Square', 'nexvio' ), '8' => __( 'Soft', 'nexvio' ), '14' => __( 'Round', 'nexvio' ) ) ) ), 'gap' => array( 'number', __( 'Space between fields', 'nexvio' ), 18, array( 'min' => 4, 'max' => 60, 'unit' => 'px' ) ), ), ), 'upload' => array( __( 'File upload', 'nexvio' ), 'dashicons-upload', __( 'For the "File upload" field: what customers may send.', 'nexvio' ), array( 'upload_max' => array( 'number', __( 'Largest file', 'nexvio' ), 5, array( 'min' => 1, 'max' => 64, 'unit' => 'MB' ) ), 'upload_types' => array( 'checks', __( 'Allowed file types', 'nexvio' ), array( 'jpg', 'png', 'gif', 'webp', 'pdf' ), array( 'options' => self::upload_types() ) ), ), ), 'texts' => array( __( 'Texts', 'nexvio' ), 'dashicons-editor-textcolor', __( 'The words customers see. They are translated with your site; type your own to replace them.', 'nexvio' ), array( 't_choose' => array( 'text', __( 'Dropdown placeholder', 'nexvio' ), __( 'Choose an option', 'nexvio' ) ), 't_required' => array( 'text', __( 'Required field message', 'nexvio' ), /* translators: %s: field name. */ __( 'Please fill in: %s', 'nexvio' ), array( 'help' => /* translators: %s: placeholder that is replaced by the field name. */ __( '%s is replaced by the field name.', 'nexvio' ), ), ), 't_upload' => array( 'text', __( 'Upload button', 'nexvio' ), __( 'Choose a file', 'nexvio' ) ), 't_uploading' => array( 'text', __( 'While uploading', 'nexvio' ), __( 'Uploading…', 'nexvio' ) ), 't_upl_error' => array( 'text', __( 'Upload error', 'nexvio' ), __( 'This file could not be uploaded.', 'nexvio' ) ), 't_options' => array( 'text', __( 'Totals: options', 'nexvio' ), __( 'Options total', 'nexvio' ) ), 't_product' => array( 'text', __( 'Totals: product', 'nexvio' ), __( 'Product price', 'nexvio' ) ), 't_final' => array( 'text', __( 'Totals: final price', 'nexvio' ), __( 'Final total', 'nexvio' ) ), 't_pending' => array( 'text', __( 'Totals: options not chosen', 'nexvio' ), __( 'Choose the product options to see the price.', 'nexvio' ) ), 't_select' => array( 'text', __( 'Shop list button', 'nexvio' ), __( 'Select options', 'nexvio' ) ), ), ), );
}
public static function defaults() {
$out = array();
foreach ( self::schema() as $group ) {
foreach ( $group[3] as $key => $f ) {
$out[ $key ] = $f[2];
}
}
return $out;
}
public static function all() {
if ( null === self::$cache ) {
$saved = get_option( self::OPTION, array() );
self::$cache = array_merge( self::defaults(), is_array( $saved ) ? $saved : array() );
}
return self::$cache;
}
public static function get( $key ) {
$all = self::all();
return isset( $all[ $key ] ) ? $all[ $key ] : null;
}
public static function on( $key ) {
return 'yes' === self::get( $key );
}
public static function text( $key ) {
$value = trim( (string) self::get( $key ) );
if ( '' !== $value ) {
return $value;
}
$defaults = self::defaults();
return isset( $defaults[ $key ] ) ? (string) $defaults[ $key ] : '';
}
public static function flush() {
self::$cache = null;
}
public static function sanitize( $input ) {
$input = is_array( $input ) ? $input : array();
$out = array();
foreach ( self::schema() as $group ) {
foreach ( $group[3] as $key => $f ) {
$type = $f[0];
$default = $f[2];
$extra = isset( $f[3] ) ? $f[3] : array();
$value = isset( $input[ $key ] ) ? $input[ $key ] : null;
switch ( $type ) {
case 'toggle': $out[ $key ] = null !== $value && 'yes' === $value ? 'yes' : 'no';
break;
case 'select': case 'style': $out[ $key ] = is_string( $value ) && isset( $extra['options'][ $value ] ) ? $value : $default;
break;
case 'color': $c = is_string( $value ) ? sanitize_hex_color( trim( $value ) ) : '';
$out[ $key ] = $c ? $c : $default;
break;
case 'number': $n = null === $value || '' === $value ? $default : (int) $value;
$out[ $key ] = max( isset( $extra['min'] ) ? $extra['min'] : 0, min( isset( $extra['max'] ) ? $extra['max'] : 9999, $n ) );
break;
case 'checks': $list = is_array( $value ) ? array_map( 'sanitize_key', $value ) : array();
$out[ $key ] = array_values( array_intersect( $list, array_keys( $extra['options'] ) ) );
break;
default: $text = is_string( $value ) ? sanitize_text_field( $value ) : '';
$out[ $key ] = $text === (string) $default ? '' : $text;
}
}
}
return $out;
}
public static function contrast( $hex ) {
return class_exists( 'Nexvio_Cart_Settings' ) ? Nexvio_Cart_Settings::contrast( $hex ) : '#ffffff';
}
} 