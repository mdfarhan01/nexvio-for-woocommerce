<?php
 if ( ! defined( 'ABSPATH' ) ) {
exit;
}
class Nexvio_Attribute_Types {
public static function custom() {
return array( 'color' => __( 'Colour', 'nexvio' ), 'image' => __( 'Image', 'nexvio' ), 'button' => __( 'Button', 'nexvio' ), 'radio' => __( 'Radio', 'nexvio' ), 'checkbox' => __( 'Checkbox', 'nexvio' ), );
}
public static function init() {
add_filter( 'product_attributes_type_selector', array( __CLASS__, 'types' ) );
add_action( 'woocommerce_product_option_terms', array( __CLASS__, 'product_terms' ), 10, 3 );
add_action( 'woocommerce_attribute_added', array( __CLASS__, 'ensure_saved' ), 20, 2 );
add_action( 'woocommerce_attribute_updated', array( __CLASS__, 'ensure_saved' ), 20, 2 );
}
public static function types( $types ) {
$types = is_array( $types ) ? $types : array();
if ( ! isset( $types['select'] ) ) {
$types = array( 'select' => __( 'Select', 'nexvio' ) ) + $types;
}
foreach ( self::custom() as $slug => $label ) {
if ( ! isset( $types[ $slug ] ) ) {
$types[ $slug ] = $label;
}
}
return $types;
}
public static function type_of( $taxonomy ) {
$taxonomy = (string) $taxonomy;
if ( 0 !== strpos( $taxonomy, 'pa_' ) || ! function_exists( 'wc_get_attribute_taxonomies' ) ) {
return '';
}
$name = substr( $taxonomy, 3 );
foreach ( (array) wc_get_attribute_taxonomies() as $tax ) {
if ( isset( $tax->attribute_name ) && $tax->attribute_name === $name ) {
return isset( $tax->attribute_type ) ? (string) $tax->attribute_type : 'select';
}
}
return '';
}
public static function ensure_saved( $id, $data ) {
if ( ! current_user_can( 'manage_product_terms' ) && ! current_user_can( 'manage_woocommerce' ) ) {
return;
}
// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Runs inside the WooCommerce attribute save, which verifies its own nonce; capability is checked above.
if ( ! isset( $_POST['attribute_type'] ) ) {
return;
}
// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Runs inside the WooCommerce attribute save, which verifies its own nonce; capability is checked above.
$type = sanitize_key( wp_unslash( $_POST['attribute_type'] ) );
if ( ! array_key_exists( $type, wc_get_attribute_types() ) ) {
return;
}
if ( is_array( $data ) && isset( $data['attribute_type'] ) && $type === $data['attribute_type'] ) {
return;
}
global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- WooCommerce attribute table has no API for this custom column; runs once when an attribute is saved.
$wpdb->update( $wpdb->prefix . 'woocommerce_attribute_taxonomies', array( 'attribute_type' => $type ), array( 'attribute_id' => (int) $id ), array( '%s' ), array( '%d' ) );
delete_transient( 'wc_attribute_taxonomies' );
if ( class_exists( 'WC_Cache_Helper' ) ) {
WC_Cache_Helper::invalidate_cache_group( 'woocommerce-attributes' );
}
}
public static function product_terms( $attribute_taxonomy, $i, $attribute = null ) {
if ( ! is_object( $attribute_taxonomy ) || ! isset( $attribute_taxonomy->attribute_type ) || ! array_key_exists( $attribute_taxonomy->attribute_type, self::custom() ) ) {
return;
}
$taxonomy = wc_attribute_taxonomy_name( $attribute_taxonomy->attribute_name );
$selected = ( $attribute && is_object( $attribute ) && method_exists( $attribute, 'get_options' ) ) ? array_map( 'intval', (array) $attribute->get_options() ) : array();
$terms = get_terms( array( 'taxonomy' => $taxonomy, 'orderby' => 'name', 'hide_empty' => false, ) ); ?>
		<select multiple="multiple" data-placeholder="<?php esc_attr_e( 'Select terms', 'nexvio' ); ?>" class="multiselect attribute_values wc-enhanced-select" name="attribute_values[<?php echo esc_attr( $i ); ?>][]">
			<?php
 if ( ! is_wp_error( $terms ) ) { foreach ( $terms as $term ) { echo '<option value="' . esc_attr( $term->term_id ) . '"' . selected( in_array( (int) $term->term_id, $selected, true ), true, false ) . '>' . esc_html( $term->name ) . '</option>'; } } ?>
		</select>
		<button class="button plus select_all_attributes"><?php esc_html_e( 'Select all', 'nexvio' ); ?></button>
		<button class="button minus select_no_attributes"><?php esc_html_e( 'Select none', 'nexvio' ); ?></button>
		<button class="button fr plus add_new_attribute"><?php esc_html_e( 'Add new', 'nexvio' ); ?></button>
		<?php
 } } 