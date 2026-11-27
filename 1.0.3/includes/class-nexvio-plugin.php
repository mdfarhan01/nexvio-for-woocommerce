<?php
 if ( ! defined( 'ABSPATH' ) ) {
exit;
}
final class Nexvio_Plugin {
const CATEGORY = 'nexvio';
private static $instance = null;
public static function instance() {
if ( null === self::$instance ) {
self::$instance = new self();
}
return self::$instance;
}
public static function allowed_html() {
static $allowed = null;
if ( null !== $allowed ) {
return $allowed;
}
$allowed = wp_kses_allowed_html( 'post' );
$global = array( 'class' => true, 'id' => true, 'style' => true, 'title' => true, 'role' => true, 'hidden' => true, 'tabindex' => true, 'lang' => true, 'dir' => true, 'data-*' => true, );
foreach ( array( 'controls', 'current', 'describedby', 'disabled', 'expanded', 'haspopup', 'hidden', 'label', 'labelledby', 'live', 'modal', 'pressed', 'selected', 'checked', 'atomic', 'busy' ) as $aria ) {
$global[ 'aria-' . $aria ] = true;
}
$paint = array( 'fill' => true, 'fill-rule' => true, 'clip-rule' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'opacity' => true, 'transform' => true, );
$extra = array( 'svg' => array_merge( $paint, array( 'xmlns' => true, 'viewbox' => true, 'width' => true, 'height' => true, 'focusable' => true, 'preserveaspectratio' => true ) ), 'g' => $paint, 'path' => array_merge( $paint, array( 'd' => true ) ), 'circle' => array_merge( $paint, array( 'cx' => true, 'cy' => true, 'r' => true ) ), 'ellipse' => array_merge( $paint, array( 'cx' => true, 'cy' => true, 'rx' => true, 'ry' => true ) ), 'rect' => array_merge( $paint, array( 'x' => true, 'y' => true, 'width' => true, 'height' => true, 'rx' => true, 'ry' => true ) ), 'line' => array_merge( $paint, array( 'x1' => true, 'y1' => true, 'x2' => true, 'y2' => true ) ), 'polyline' => array_merge( $paint, array( 'points' => true ) ), 'polygon' => array_merge( $paint, array( 'points' => true ) ), 'defs' => array(), 'lineargradient' => array( 'x1' => true, 'y1' => true, 'x2' => true, 'y2' => true, 'gradientunits' => true, 'gradienttransform' => true ), 'stop' => array( 'offset' => true, 'stop-color' => true, 'stop-opacity' => true ), 'form' => array( 'action' => true, 'method' => true, 'novalidate' => true, 'autocomplete' => true, 'enctype' => true, 'role' => true ), 'input' => array( 'type' => true, 'name' => true, 'value' => true, 'checked' => true, 'disabled' => true, 'readonly' => true, 'required' => true, 'placeholder' => true, 'min' => true, 'max' => true, 'step' => true, 'maxlength' => true, 'pattern' => true, 'size' => true, 'inputmode' => true, 'autocomplete' => true, 'form' => true ), 'select' => array( 'name' => true, 'multiple' => true, 'disabled' => true, 'required' => true, 'autocomplete' => true ), 'option' => array( 'value' => true, 'selected' => true, 'disabled' => true ), 'optgroup' => array( 'label' => true, 'disabled' => true ), 'textarea' => array( 'name' => true, 'rows' => true, 'cols' => true, 'placeholder' => true, 'disabled' => true, 'readonly' => true, 'required' => true ), 'button' => array( 'type' => true, 'name' => true, 'value' => true, 'disabled' => true, 'form' => true ), 'label' => array( 'for' => true ), 'a' => array( 'href' => true, 'target' => true, 'rel' => true, 'download' => true ), 'img' => array( 'src' => true, 'srcset' => true, 'sizes' => true, 'alt' => true, 'width' => true, 'height' => true, 'loading' => true, 'decoding' => true, 'fetchpriority' => true ), 'details' => array( 'open' => true ), 'summary' => array(), 'nav' => array(), 'section' => array(), 'header' => array(), 'footer' => array(), 'aside' => array(), 'article' => array(), 'main' => array(), 'ul' => array(), 'ol' => array(), 'li' => array(), 'dl' => array(), 'dt' => array(), 'dd' => array(), 'div' => array(), 'span' => array(), 'p' => array(), 'i' => array(), 'del' => array(), 'ins' => array(), 'h2' => array(), 'h3' => array(), 'h4' => array(), );
foreach ( $extra as $tag => $attrs ) {
$allowed[ $tag ] = array_merge( isset( $allowed[ $tag ] ) && is_array( $allowed[ $tag ] ) ? $allowed[ $tag ] : array(), $global, $attrs );
}
return $allowed;
}
public static function safe_style_css( $props ) {
return array_merge( (array) $props, array( '--sw', '--sw-img', '--r', '--lo', '--hi', '--nexvio-rating', 'display', 'justify-content', 'object-fit', 'gap', 'align-items' ) );
}
public static function widgets() {
return array( 'products_card' => array( 'class' => 'Nexvio_Products_Card_Widget', 'file' => 'class-nexvio-products-card-widget.php', 'title' => __( 'Products Card', 'nexvio' ), 'desc' => __( 'A grid of simple product cards (image, title, description, price, button) with a short Style tab: box, image, card content, button and badges.', 'nexvio' ), 'icon' => 'dashicons-index-card', 'group' => __( 'Products', 'nexvio' ), ), 'products_grid' => array( 'class' => 'Nexvio_Products_Grid_Widget', 'file' => 'class-nexvio-products-grid-widget.php', 'title' => __( 'Products Card Layouts', 'nexvio' ), 'desc' => __( 'A grid of chosen products for any page (featured, on sale, categories…), with pagination and 11 designs.', 'nexvio' ), 'icon' => 'dashicons-screenoptions', 'group' => __( 'Products', 'nexvio' ), ), 'category_grid' => array( 'class' => 'Nexvio_Category_Grid_Widget', 'file' => 'class-nexvio-category-grid-widget.php', 'title' => __( 'Product Category Grid', 'nexvio' ), 'desc' => __( 'A responsive grid of product categories with image, name and product count. 3 designs, choose which categories, 3 main colours.', 'nexvio' ), 'icon' => 'dashicons-category', 'group' => __( 'Products', 'nexvio' ), ), 'category_slider' => array( 'class' => 'Nexvio_Category_Slider_Widget', 'file' => 'class-nexvio-category-slider-widget.php', 'title' => __( 'Product Category Slider', 'nexvio' ), 'desc' => __( 'The Product Category Grid as a slider: arrows, dots, swipe and mouse drag, autoplay and endless loop.', 'nexvio' ), 'icon' => 'dashicons-images-alt2', 'group' => __( 'Products', 'nexvio' ), ), 'category_grid_layouts' => array( 'class' => 'Nexvio_Category_Grid_Layouts_Widget', 'file' => 'class-nexvio-category-grid-layouts-widget.php', 'title' => __( 'Category Grid Layouts', 'nexvio' ), 'desc' => __( 'The Product Category Grid with 12 ready design layouts (cards, fade, round, pill, arch, frame, badge, bar, line, numbered…). Pick a picture, then change the 3 main colours.', 'nexvio' ), 'icon' => 'dashicons-screenoptions', 'group' => __( 'Products', 'nexvio' ), ), 'category_slider_layouts' => array( 'class' => 'Nexvio_Category_Slider_Layouts_Widget', 'file' => 'class-nexvio-category-slider-layouts-widget.php', 'title' => __( 'Category Slider Layouts', 'nexvio' ), 'desc' => __( 'The Product Category Slider with 12 ready design layouts. Arrows, dots, swipe, autoplay and endless loop.', 'nexvio' ), 'icon' => 'dashicons-images-alt2', 'group' => __( 'Products', 'nexvio' ), ), 'products_slider' => array( 'class' => 'Nexvio_Products_Slider_Widget', 'file' => 'class-nexvio-products-slider-widget.php', 'title' => __( 'Products Slider', 'nexvio' ), 'desc' => __( 'Product carousel with the Products Card design: arrows, swipe, mouse drag, autoplay and endless loop.', 'nexvio' ), 'icon' => 'dashicons-images-alt2', 'group' => __( 'Products', 'nexvio' ), ), 'product_slider' => array( 'class' => 'Nexvio_Product_Slider_Widget', 'file' => 'class-nexvio-product-slider-widget.php', 'title' => __( 'Products Slider Layouts', 'nexvio' ), 'desc' => __( 'Carousel of WooCommerce products with 12 slider card designs, endless loop and mouse drag.', 'nexvio' ), 'icon' => 'dashicons-images-alt2', 'group' => __( 'Products', 'nexvio' ), ), 'product_showcase' => array( 'class' => 'Nexvio_Product_Showcase_Widget', 'file' => 'class-nexvio-product-showcase-widget.php', 'title' => __( 'Product Showcase (Tabs)', 'nexvio' ), 'desc' => __( 'Big product view (image, title, price, stock countdown, payment options, Add to cart) with a product list that switches it like tabs. Fully responsive.', 'nexvio' ), 'icon' => 'dashicons-columns', 'group' => __( 'Products', 'nexvio' ), ), 'products_archive' => array( 'class' => 'Nexvio_Products_Archive_Widget', 'file' => 'class-nexvio-products-archive-widget.php', 'title' => __( 'Products Archive', 'nexvio' ), 'desc' => __( 'Shop / category page products with the Products Card design. Follows the filter and the archive pagination.', 'nexvio' ), 'icon' => 'dashicons-grid-view', 'group' => __( 'Shop', 'nexvio' ), ), 'product_grid' => array( 'class' => 'Nexvio_Product_Grid_Widget', 'file' => 'class-nexvio-product-grid-widget.php', 'title' => __( 'Products Archive Layouts', 'nexvio' ), 'desc' => __( 'Shop / category page grid with pagination, result count and sorting. Works with the filter. 11 designs.', 'nexvio' ), 'icon' => 'dashicons-grid-view', 'group' => __( 'Shop', 'nexvio' ), ), 'related_products' => array( 'class' => 'Nexvio_Related_Products_Widget', 'file' => 'class-nexvio-related-products-widget.php', 'title' => __( 'Related Products', 'nexvio' ), 'desc' => __( 'On the product page: related products with the Products Card design.', 'nexvio' ), 'icon' => 'dashicons-randomize', 'group' => __( 'Single product', 'nexvio' ), ), 'product_related' => array( 'class' => 'Nexvio_Product_Related_Widget', 'file' => 'class-nexvio-product-related-widget.php', 'title' => __( 'Related Products Layouts', 'nexvio' ), 'desc' => __( 'On the product page: other products of the same category, most popular first.', 'nexvio' ), 'icon' => 'dashicons-randomize', 'group' => __( 'Single product', 'nexvio' ), ), 'add_to_cart' => array( 'class' => 'Nexvio_Add_To_Cart_Widget', 'file' => 'class-nexvio-add-to-cart-widget.php', 'title' => __( 'Add to Cart', 'nexvio' ), 'desc' => __( 'Quantity selector, "Add to cart" button (AJAX) and wishlist heart.', 'nexvio' ), 'icon' => 'dashicons-cart', 'group' => __( 'Single product', 'nexvio' ), ), 'extra_options' => array( 'class' => 'Nexvio_Extra_Options_Widget', 'file' => 'class-nexvio-extra-options-widget.php', 'title' => __( 'Extra Options', 'nexvio' ), 'desc' => __( 'Shows the extra fields created in Nexvio > Extra Options (text, choices, swatches, upload…) on the product page, with live prices.', 'nexvio' ), 'icon' => 'dashicons-forms', 'group' => __( 'Single product', 'nexvio' ), ), 'product_faq' => array( 'class' => 'Nexvio_Product_Faq_Widget', 'file' => 'class-nexvio-product-faq-widget.php', 'title' => __( 'Product FAQ', 'nexvio' ), 'desc' => __( 'Questions and answers for the product, as an accordion. Questions come from the FAQ fields in Extra Options and from the FAQ tab of each product. Optional FAQ schema for search engines.', 'nexvio' ), 'icon' => 'dashicons-editor-help', 'group' => __( 'Single product', 'nexvio' ), ), 'product_tabs' => array( 'class' => 'Nexvio_Product_Tabs_Widget', 'file' => 'class-nexvio-product-tabs-widget.php', 'title' => __( 'Product Tabs', 'nexvio' ), 'desc' => __( 'Three tabs: Description, Reviews and Add Review. Rating summary with star filter, sort, load more, and a premium review form with a big star selector.', 'nexvio' ), 'icon' => 'dashicons-star-filled', 'group' => __( 'Single product', 'nexvio' ), ), 'product_variations' => array( 'class' => 'Nexvio_Product_Variations_Widget', 'file' => 'class-nexvio-product-variations-widget.php', 'title' => __( 'Product Price', 'nexvio' ), 'desc' => __( 'The price of the product (regular and sale price). For a variable product it also shows the options (Color, Size…) as dropdown, buttons, swatches or checkbox style, with the price of the chosen variation. Works together with the Add to Cart widget.', 'nexvio' ), 'icon' => 'dashicons-art', 'group' => __( 'Single product', 'nexvio' ), ), 'smart_purchase' => array( 'class' => 'Nexvio_Smart_Purchase_Widget', 'file' => 'class-nexvio-smart-purchase-widget.php', 'title' => __( 'Smart Purchase', 'nexvio' ), 'desc' => __( 'A products grid with the Products Card design. Every card has an Add to cart button and opens a product popup (full-width image with gallery thumbnails, variations, quantity).', 'nexvio' ), 'icon' => 'dashicons-cart', 'group' => __( 'Products', 'nexvio' ), ), 'product_images' => array( 'class' => 'Nexvio_Product_Images_Widget', 'file' => 'class-nexvio-product-images-widget.php', 'title' => __( 'Product Images', 'nexvio' ), 'desc' => __( 'Only the product image with its gallery below, above, left or right. Every image has the same size, is never cropped and loads in full resolution.', 'nexvio' ), 'icon' => 'dashicons-format-gallery', 'group' => __( 'Single product', 'nexvio' ), ), 'product_filter' => array( 'class' => 'Nexvio_Product_Filter_Widget', 'file' => 'class-nexvio-product-filter-widget.php', 'title' => __( 'Product Filter', 'nexvio' ), 'desc' => __( 'AJAX filter: search, price range, categories and any attribute, tag or stock filter.', 'nexvio' ), 'icon' => 'dashicons-filter', 'group' => __( 'Shop', 'nexvio' ), ), 'filter_drawer' => array( 'class' => 'Nexvio_Filter_Drawer_Widget', 'file' => 'class-nexvio-filter-drawer-widget.php', 'title' => __( 'Filter Popup (side panel)', 'nexvio' ), 'desc' => __( 'A Filters button that slides the filter in from the side. Apply closes it and shows the products.', 'nexvio' ), 'icon' => 'dashicons-filter', 'group' => __( 'Shop', 'nexvio' ), ), 'header_search' => array( 'class' => 'Nexvio_Header_Search_Widget', 'file' => 'class-nexvio-header-search-widget.php', 'title' => __( 'Header Search', 'nexvio' ), 'desc' => __( 'Search icon for the header. Opens a search bar that filters products by title.', 'nexvio' ), 'icon' => 'dashicons-search', 'group' => __( 'Header', 'nexvio' ), ), 'header_cart' => array( 'class' => 'Nexvio_Header_Cart_Widget', 'file' => 'class-nexvio-header-cart-widget.php', 'title' => __( 'Header Cart', 'nexvio' ), 'desc' => __( 'Cart icon with a live counter for the header. Opens the side cart.', 'nexvio' ), 'icon' => 'dashicons-cart', 'group' => __( 'Cart', 'nexvio' ), ), 'cart_panel' => array( 'class' => 'Nexvio_Cart_Panel_Widget', 'file' => 'class-nexvio-cart-panel-widget.php', 'title' => __( 'Cart Panel', 'nexvio' ), 'desc' => __( 'The full cart shown on a page: products, quantities, coupon and checkout. Fully styleable.', 'nexvio' ), 'icon' => 'dashicons-products', 'group' => __( 'Cart', 'nexvio' ), ), 'floating_cart' => array( 'class' => 'Nexvio_Floating_Cart_Widget', 'file' => 'class-nexvio-floating-cart-widget.php', 'title' => __( 'Floating Cart', 'nexvio' ), 'desc' => __( 'A cart button fixed in a bottom corner, with a live counter. Opens the side cart.', 'nexvio' ), 'icon' => 'dashicons-sticky', 'group' => __( 'Cart', 'nexvio' ), ), 'wishlist_icon' => array( 'class' => 'Nexvio_Wishlist_Icon_Widget', 'file' => 'class-nexvio-wishlist-icon-widget.php', 'title' => __( 'Wishlist Icon (header)', 'nexvio' ), 'desc' => __( 'Heart with a live counter, links to the wishlist page.', 'nexvio' ), 'icon' => 'dashicons-heart', 'group' => __( 'Header', 'nexvio' ), ), 'wishlist_button' => array( 'class' => 'Nexvio_Wishlist_Button_Widget', 'file' => 'class-nexvio-wishlist-button-widget.php', 'title' => __( 'Wishlist Button', 'nexvio' ), 'desc' => __( 'Add to wishlist button with full control: 7 designs, own icons, text, counter, effects and every colour per state.', 'nexvio' ), 'icon' => 'dashicons-heart', 'group' => __( 'Single product', 'nexvio' ), ), 'wishlist_card' => array( 'class' => 'Nexvio_Wishlist_Card_Widget', 'file' => 'class-nexvio-wishlist-card-widget.php', 'title' => __( 'Wishlist Card', 'nexvio' ), 'desc' => __( 'The visitor\'s wishlist products with the Products Card design. Hearts remove cards live.', 'nexvio' ), 'icon' => 'dashicons-star-filled', 'group' => __( 'Wishlist', 'nexvio' ), ), 'wishlist_grid' => array( 'class' => 'Nexvio_Wishlist_Grid_Widget', 'file' => 'class-nexvio-wishlist-grid-widget.php', 'title' => __( 'Wishlist Card Layouts', 'nexvio' ), 'desc' => __( 'The visitor\'s wishlist products, with the same card design. Hearts remove cards live.', 'nexvio' ), 'icon' => 'dashicons-star-filled', 'group' => __( 'Wishlist', 'nexvio' ), ), 'thankyou' => array( 'class' => 'Nexvio_Thankyou_Widget', 'file' => 'class-nexvio-thankyou-widget.php', 'title' => __( 'Thank You', 'nexvio' ), 'desc' => __( 'The order confirmation widgets of the Thank You Page (10 designs). The widget of the design you choose in Thank You Page is shown in Elementor.', 'nexvio' ), 'icon' => 'dashicons-yes-alt', 'group' => __( 'Thank you', 'nexvio' ), ), );
}
public static function ver( $relative ) {
$file = NEXVIO_PATH . ltrim( $relative, '/' );
if ( file_exists( $file ) ) {
return NEXVIO_VERSION . '.' . ( defined( 'NEXVIO_ASSET_REV' ) ? NEXVIO_ASSET_REV . '.' : '' ) . substr( md5_file( $file ), 0, 8 );
}
return NEXVIO_VERSION . ( defined( 'NEXVIO_ASSET_REV' ) ? '.' . NEXVIO_ASSET_REV : '' );
}
public static function disabled() {
$d = get_option( 'nexvio_disabled_widgets', array() );
return is_array( $d ) ? $d : array();
}
public static function features() {
return array( 'filters' => array( 'label' => __( 'Filters', 'nexvio' ), 'desc' => __( 'Product Filter and Filter Popup widgets, and the Filters settings page.', 'nexvio' ), 'icon' => 'dashicons-filter', 'page' => 'nexvio-filters', 'widgets' => array( 'product_filter', 'filter_drawer' ), ), 'add_to_cart' => array( 'label' => __( 'Add to Cart', 'nexvio' ), 'desc' => __( 'The side cart, the floating cart, the cart widgets (Header, Panel, Floating) and the Add to Cart settings page. Add to Cart, Product Price and Smart Purchase widgets stay available.', 'nexvio' ), 'icon' => 'dashicons-cart', 'page' => 'nexvio-cart', 'widgets' => array( 'header_cart', 'cart_panel', 'floating_cart' ), ), 'wishlist' => array( 'label' => __( 'Wishlist', 'nexvio' ), 'desc' => __( 'Wishlist widgets, the hearts on the product cards, and the Wishlist settings page.', 'nexvio' ), 'icon' => 'dashicons-heart', 'page' => 'nexvio-wishlist', 'widgets' => array( 'wishlist_icon', 'wishlist_button', 'wishlist_card', 'wishlist_grid' ), ), 'extra_options' => array( 'label' => __( 'Extra Options', 'nexvio' ), 'desc' => __( 'Extra product fields with prices (text, choices, swatches, upload), the Extra Options widget, and the Extra Options settings page.', 'nexvio' ), 'icon' => 'dashicons-forms', 'page' => 'nexvio-epo', 'widgets' => array( 'extra_options', 'product_faq' ), ), 'checkout_fields' => array( 'label' => __( 'Checkout Field Editor', 'nexvio' ), 'desc' => __( 'Edit, reorder, hide and add fields on the checkout page (billing, shipping, additional), and the Checkout Field Editor settings page.', 'nexvio' ), 'icon' => 'dashicons-feedback', 'page' => 'nexvio-checkout', 'widgets' => array(), ), 'thankyou' => array( 'label' => __( 'Thank You Page', 'nexvio' ), 'desc' => __( '10 designs for the page customers see after they order, a button to create the page, and its settings page. The widgets are in the Nexvio category in Elementor.', 'nexvio' ), 'icon' => 'dashicons-yes-alt', 'page' => 'nexvio-thankyou', 'widgets' => array( 'thankyou' ), ), );
}
public static function features_off() {
$d = get_option( 'nexvio_features_off', array( 'extra_options', 'checkout_fields' ) );
return is_array( $d ) ? $d : array();
}
public static function feature_enabled( $feature ) {
return ! in_array( $feature, self::features_off(), true );
}
public static function widget_feature( $key ) {
foreach ( self::features() as $f => $def ) {
if ( in_array( $key, $def['widgets'], true ) ) {
return $f;
}
}
return '';
}
public static function visible_widgets() {
$out = array();
foreach ( self::widgets() as $key => $w ) {
$f = self::widget_feature( $key );
if ( '' === $f || self::feature_enabled( $f ) ) {
$out[ $key ] = $w;
}
}
return $out;
}
public static function is_enabled( $key ) {
$f = self::widget_feature( $key );
if ( '' !== $f && ! self::feature_enabled( $f ) ) {
return false;
}
return ! in_array( $key, self::disabled(), true );
}
private static function migrate_103() {
if ( get_option( 'nexvio_defaults_103' ) ) {
return;
}
$off = get_option( 'nexvio_features_off', null );
if ( is_array( $off ) && ! in_array( 'checkout_fields', $off, true ) ) {
$off[] = 'checkout_fields';
update_option( 'nexvio_features_off', array_values( array_unique( $off ) ) );
}
update_option( 'nexvio_defaults_103', 1, false );
}
public function boot() {
require_once NEXVIO_PATH . 'includes/class-nexvio-wishlist.php';
require_once NEXVIO_PATH . 'includes/class-nexvio-reviews.php';
require_once NEXVIO_PATH . 'includes/class-nexvio-filter-query.php';
require_once NEXVIO_PATH . 'includes/class-nexvio-layouts.php';
require_once NEXVIO_PATH . 'includes/class-nexvio-attribute-types.php';
require_once NEXVIO_PATH . 'includes/class-nexvio-swatches.php';
require_once NEXVIO_PATH . 'includes/class-nexvio-texts.php';
require_once NEXVIO_PATH . 'includes/class-nexvio-filter-config.php';
require_once NEXVIO_PATH . 'includes/class-nexvio-cart-settings.php';
require_once NEXVIO_PATH . 'includes/class-nexvio-side-cart.php';
require_once NEXVIO_PATH . 'includes/class-nexvio-showcase-ajax.php';
require_once NEXVIO_PATH . 'includes/class-nexvio-smart-purchase-ajax.php';
require_once NEXVIO_PATH . 'includes/class-nexvio-epo-settings.php';
require_once NEXVIO_PATH . 'includes/class-nexvio-epo.php';
require_once NEXVIO_PATH . 'includes/class-nexvio-epo-render.php';
require_once NEXVIO_PATH . 'includes/class-nexvio-epo-faq.php';
require_once NEXVIO_PATH . 'includes/class-nexvio-epo-product.php';
require_once NEXVIO_PATH . 'includes/class-nexvio-delivery.php';
require_once NEXVIO_PATH . 'includes/class-nexvio-checkout.php';
require_once NEXVIO_PATH . 'includes/class-nexvio-thankyou.php';
require_once NEXVIO_PATH . 'admin/class-nexvio-admin.php';
require_once NEXVIO_PATH . 'admin/class-nexvio-epo-admin.php';
require_once NEXVIO_PATH . 'admin/class-nexvio-checkout-admin.php';
self::migrate_103();
Nexvio_Admin::init();
Nexvio_Epo_Admin::init();
Nexvio_Checkout_Admin::init();
$missing = $this->missing_requirements();
if ( $missing ) {
add_action( 'admin_notices', function () use ( $missing ) {
global $pagenow;
if ( 'plugins.php' !== $pagenow || ! current_user_can( 'activate_plugins' ) ) {
return;
}
echo '<div class="notice notice-warning"><p><strong>Nexvio</strong>: ' . esc_html( sprintf( /* translators: %s: name of the missing plugin(s). */ __( 'please install and activate %s.', 'nexvio' ), implode( ' + ', $missing ) ) ) . '</p></div>';
}
);
return;
}
if ( self::feature_enabled( 'extra_options' ) ) {
add_action( 'init', array( 'Nexvio_Epo', 'register_type' ) );
add_action( 'woocommerce_init', array( 'Nexvio_Epo', 'init' ) );
}
if ( self::feature_enabled( 'checkout_fields' ) ) {
Nexvio_Checkout::init();
}
if ( self::feature_enabled( 'thankyou' ) ) {
Nexvio_Thankyou::init();
}
Nexvio_Wishlist::init();
Nexvio_Reviews::init();
if ( self::feature_enabled( 'filters' ) ) {
Nexvio_Filter_Query::init();
}
Nexvio_Attribute_Types::init();
add_filter( 'safe_style_css', array( __CLASS__, 'safe_style_css' ) );
add_action( 'admin_init', array( 'Nexvio_Swatches', 'init_admin' ) );
add_action( 'woocommerce_init', array( 'Nexvio_Side_Cart', 'init' ) );
add_action( 'woocommerce_init', array( 'Nexvio_Showcase_Ajax', 'init' ) );
add_action( 'woocommerce_init', array( 'Nexvio_Smart_Purchase_Ajax', 'init' ) );
add_action( 'elementor/elements/categories_registered', array( $this, 'register_category' ) );
add_action( 'elementor/editor/before_enqueue_scripts', array( $this, 'reorder_category' ), 1 );
add_action( 'elementor/controls/register', array( $this, 'register_controls' ) );
add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
add_action( 'elementor/frontend/after_register_styles', array( $this, 'register_assets' ) );
add_action( 'elementor/frontend/after_register_scripts', array( $this, 'register_assets' ) );
add_action( 'elementor/preview/enqueue_styles', array( $this, 'register_assets' ) );
add_action( 'elementor/editor/after_enqueue_styles', array( $this, 'editor_styles' ) );
add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );
add_filter( 'loop_shop_per_page', array( $this, 'per_page' ), 99 );
add_action( 'wp', array( $this, 'maybe_replace_related' ) );
add_shortcode( 'nexvio_related_products', array( $this, 'related_shortcode' ) );
}
public function missing_requirements() {
$missing = array();
if ( ! did_action( 'elementor/loaded' ) ) {
$missing[] = 'Elementor';
}
if ( ! class_exists( 'WooCommerce' ) ) {
$missing[] = 'WooCommerce';
}
return $missing;
}
public function register_category( $manager ) {
$manager->add_category( self::CATEGORY, array( 'title' => __( 'Nexvio', 'nexvio' ), 'icon' => 'eicon-woocommerce', ) );
self::place_category( $manager );
}
/**
 * Elementor moves its own "Custom Widget" category right under Basic after plugins have registered theirs, so the order is fixed once more when the editor loads.
 */
public function reorder_category() {
if ( ! class_exists( '\\Elementor\\Plugin' ) || empty( \Elementor\Plugin::$instance->elements_manager ) ) {
return;
}
$manager = \Elementor\Plugin::$instance->elements_manager;
$manager->get_categories();
self::place_category( $manager );
}
private static function place_category( $manager ) {
try {
$ref = new ReflectionObject( $manager );
if ( ! $ref->hasProperty( 'categories' ) ) {
return;
}
$prop = $ref->getProperty( 'categories' );
$prop->setAccessible( true );
$cats = $prop->getValue( $manager );
if ( ! is_array( $cats ) || ! isset( $cats[ self::CATEGORY ] ) ) {
return;
}
$ours = array( self::CATEGORY => $cats[ self::CATEGORY ] );
unset( $cats[ self::CATEGORY ] );
$after = apply_filters( 'nexvio_elementor_category_after', 'basic' );
$new = array();
$done = false;
if ( ! isset( $cats[ $after ] ) ) {
$new = $ours;
$done = true;
}
foreach ( $cats as $key => $cat ) {
$new[ $key ] = $cat;
if ( ! $done && $key === $after ) {
$new = array_merge( $new, $ours );
$done = true;
}
}
$prop->setValue( $manager, $new );
} catch ( Throwable $e ) {
unset( $e );
}
}
public function register_controls( $controls_manager ) {
require_once NEXVIO_PATH . 'includes/class-nexvio-layout-control.php';
$controls_manager->register( new Nexvio_Layout_Control() );
}
public function editor_styles() {
wp_enqueue_style( 'nexvio-editor', NEXVIO_URL . 'assets/css/editor.css', array(), self::ver( 'assets/css/editor.css' ) );
wp_add_inline_style( 'elementor-editor', '#elementor-panel-category-nexvio .elementor-panel-category-title{color:#3d8bff}#elementor-panel-category-nexvio .elementor-element .icon{color:#3d8bff}' );
}
public static function load_widget_classes() {
require_once NEXVIO_PATH . 'includes/class-nexvio-layouts.php';
require_once NEXVIO_PATH . 'includes/class-nexvio-reviews.php';
require_once NEXVIO_PATH . 'includes/class-nexvio-attribute-types.php';
require_once NEXVIO_PATH . 'includes/class-nexvio-swatches.php';
require_once NEXVIO_PATH . 'includes/class-nexvio-texts.php';
require_once NEXVIO_PATH . 'includes/class-nexvio-filter-config.php';
require_once NEXVIO_PATH . 'includes/class-nexvio-cart-settings.php';
require_once NEXVIO_PATH . 'includes/class-nexvio-side-cart.php';
require_once NEXVIO_PATH . 'includes/class-nexvio-smart-purchase-ajax.php';
require_once NEXVIO_PATH . 'includes/class-nexvio-epo-settings.php';
require_once NEXVIO_PATH . 'includes/class-nexvio-epo.php';
require_once NEXVIO_PATH . 'includes/class-nexvio-epo-render.php';
require_once NEXVIO_PATH . 'includes/class-nexvio-epo-faq.php';
require_once NEXVIO_PATH . 'includes/class-nexvio-epo-product.php';
require_once NEXVIO_PATH . 'includes/class-nexvio-thankyou.php';
require_once NEXVIO_PATH . 'includes/trait-nexvio-card.php';
foreach ( self::widgets() as $w ) {
require_once NEXVIO_PATH . 'widgets/' . $w['file'];
}
require_once NEXVIO_PATH . 'widgets/class-nexvio-thankyou-widget.php';
require_once NEXVIO_PATH . 'widgets/class-nexvio-thankyou-widgets.php';
}
public static function thankyou_widget_classes() {
$out = array( 'Nexvio_Thankyou_Widget' );
for ( $i = 1;
$i <= 10;
$i++ ) {
$out[] = 'Nexvio_Thankyou_D' . $i . '_Widget';
}
return $out;
}
public function register_widgets( $widgets_manager ) {
$this->register_assets();
self::load_widget_classes();
foreach ( self::widgets() as $key => $w ) {
if ( self::is_enabled( $key ) && class_exists( $w['class'] ) ) {
$widgets_manager->register( new $w['class']() );
}
}
if ( self::is_enabled( 'thankyou' ) ) {
foreach ( self::thankyou_widget_classes() as $class ) {
if ( 'Nexvio_Thankyou_Widget' !== $class && class_exists( $class ) ) {
$widgets_manager->register( new $class() );
}
}
}
}
public function register_assets() {
if ( wp_style_is( 'nexvio', 'registered' ) ) {
return;
}
$styles = array( 'nexvio-layout' => array( 'widget-layout.css', array() ), 'nexvio' => array( 'product-slider.css', array( 'nexvio-layout' ) ), 'nexvio-add-to-cart' => array( 'add-to-cart.css', array( 'nexvio' ) ), 'nexvio-wishlist-widgets' => array( 'wishlist-widgets.css', array() ), 'nexvio-wishlist-button' => array( 'wishlist-button.css', array( 'nexvio' ) ), 'nexvio-product-filter' => array( 'product-filter.css', array() ), 'nexvio-filter-drawer' => array( 'filter-drawer.css', array( 'nexvio-product-filter' ) ), 'nexvio-header-search' => array( 'header-search.css', array() ), 'nexvio-product-showcase' => array( 'product-showcase.css', array( 'nexvio-add-to-cart' ) ), 'nexvio-smart-purchase' => array( 'smart-purchase.css', array( 'nexvio' ) ), 'nexvio-product-images' => array( 'product-images.css', array() ), 'nexvio-product-variations' => array( 'product-variations.css', array() ), 'nexvio-product-tabs' => array( 'product-tabs.css', array() ), 'nexvio-category-grid' => array( 'category-grid.css', array() ), 'nexvio-extra-options' => array( 'extra-options.css', array() ), 'nexvio-product-faq' => array( 'product-faq.css', array() ), 'nexvio-thankyou' => array( 'thankyou.css', array() ), );
foreach ( $styles as $handle => $st ) {
wp_register_style( $handle, NEXVIO_URL . 'assets/css/' . $st[0], $st[1], self::ver( 'assets/css/' . $st[0] ) );
}
if ( class_exists( 'Nexvio_Filter_Config' ) ) {
wp_add_inline_style( 'nexvio-product-filter', Nexvio_Filter_Config::style_css() );
}
$scripts = array( 'nexvio' => array( 'product-slider.js', array() ), 'nexvio-add-to-cart' => array( 'add-to-cart.js', array() ), 'nexvio-wishlist-widgets' => array( 'wishlist-widgets.js', array( 'nexvio' ) ), 'nexvio-product-filter' => array( 'product-filter.js', array() ), 'nexvio-header-search' => array( 'header-search.js', array() ), 'nexvio-view-switch' => array( 'view-switch.js', array() ), 'nexvio-filter-drawer' => array( 'filter-drawer.js', array() ), 'nexvio-product-showcase' => array( 'product-showcase.js', array() ), 'nexvio-smart-purchase' => array( 'smart-purchase.js', array() ), 'nexvio-product-images' => array( 'product-images.js', array() ), 'nexvio-product-variations' => array( 'product-variations.js', array() ), 'nexvio-product-tabs' => array( 'product-tabs.js', array() ), 'nexvio-category-slider' => array( 'category-slider.js', array() ), 'nexvio-extra-options' => array( 'extra-options.js', array() ), );
foreach ( $scripts as $handle => $sc ) {
wp_register_script( $handle, NEXVIO_URL . 'assets/js/' . $sc[0], $sc[1], self::ver( 'assets/js/' . $sc[0] ), true );
}
if ( ! has_action( 'wp_footer', array( __CLASS__, 'localize_script' ) ) ) {
add_action( 'wp_footer', array( __CLASS__, 'localize_script' ), 5 );
}
}
public static function localize_script() {
$used = false;
foreach ( (array) wp_scripts()->queue as $handle ) {
if ( 0 === strpos( (string) $handle, 'nexvio' ) ) {
$used = true;
break;
}
}
if ( ! $used ) {
return;
}
wp_localize_script( 'nexvio', 'NEXVIO', array( 'ajax' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'nexvio_wishlist' ), 'wishlist' => class_exists( 'Nexvio_Wishlist' ) ? Nexvio_Wishlist::url() : '', ) );
}
public function per_page( $per_page ) {
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only request state (filter, sort, page or preview); nothing is saved, so no nonce is needed.
$chosen = isset( $_GET['nexvio_pp'] ) ? absint( wp_unslash( $_GET['nexvio_pp'] ) ) : 0;
if ( $chosen >= 1 && $chosen <= 100 ) {
return $chosen;
}
$value = (int) get_option( 'nexvio_per_page', 0 );
return $value > 0 ? $value : $per_page;
}
public function maybe_replace_related() {
if ( 'yes' !== get_option( 'nexvio_auto_related', 'no' ) || ! self::is_enabled( 'product_related' ) || ! function_exists( 'is_product' ) || ! is_product() ) {
return;
}
remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );
add_action( 'woocommerce_after_single_product_summary', array( $this, 'print_related' ), 20 );
}
public function print_related() {
echo wp_kses( $this->related_shortcode(), Nexvio_Plugin::allowed_html() );
}
public function related_shortcode( $atts = array() ) {
if ( ! class_exists( '\Elementor\Widget_Base' ) || ! function_exists( 'wc_get_product' ) ) {
return '';
}
$atts = shortcode_atts( array( 'limit' => get_option( 'nexvio_related_limit', 8 ), 'layout' => get_option( 'nexvio_related_layout', 'slider' ), 'orderby' => get_option( 'nexvio_related_orderby', 'popularity' ), 'related_by' => 'specific', 'fill_parent' => 'yes', 'title' => get_option( 'nexvio_related_title', 'You may also like' ), 'subtitle' => '', 'columns' => 4, 'product_id' => 0, ), $atts, 'nexvio_related_products' );
$this->register_assets();
self::load_widget_classes();
wp_enqueue_style( 'nexvio' );
wp_enqueue_script( 'nexvio' );
$settings = array( 'limit' => max( 1, (int) $atts['limit'] ), 'layout' => 'grid' === $atts['layout'] ? 'grid' : 'slider', 'orderby' => sanitize_key( $atts['orderby'] ), 'related_by' => sanitize_key( $atts['related_by'] ), 'fill_parent' => 'no' === $atts['fill_parent'] ? '' : 'yes', 'title' => sanitize_text_field( $atts['title'] ), 'subtitle' => sanitize_text_field( $atts['subtitle'] ), 'columns' => max( 1, (int) $atts['columns'] ), '_product_id' => (int) $atts['product_id'], );
$data = array( 'id' => 'nexviorel' . substr( md5( wp_json_encode( $settings ) ), 0, 6 ), 'elType' => 'widget', 'widgetType' => 'nexvio_product_related', 'settings' => $settings, );
$buffer = false;
try {
$widget = null;
if ( isset( \Elementor\Plugin::$instance->elements_manager ) ) {
$widget = \Elementor\Plugin::$instance->elements_manager->create_element_instance( $data );
}
if ( ! $widget ) {
$type = \Elementor\Plugin::$instance->widgets_manager->get_widget_types( 'nexvio_product_related' );
$args = $type && method_exists( $type, 'get_default_args' ) ? $type->get_default_args() : array();
$widget = new Nexvio_Product_Related_Widget( $data, $args );
}
ob_start();
$buffer = true;
$widget->print_element();
$html = ob_get_clean();
$buffer = false;
}
catch ( Throwable $e ) {
if ( $buffer ) {
ob_end_clean();
}
return '';
}
return false === strpos( $html, 'nexvio-ps__card' ) ? '' : '<div class="nexvio-related-auto">' . $html . '</div>';
}
} 