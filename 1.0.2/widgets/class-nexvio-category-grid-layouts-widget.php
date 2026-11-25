<?php
/**
 * NEXVIO Category Grid Layouts - the category grid with 12 design layouts.
 *
 * @package Nexvio_For_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-nexvio-category-grid-widget.php';
require_once dirname( __DIR__ ) . '/includes/trait-nexvio-category-layouts.php';

class Nexvio_Category_Grid_Layouts_Widget extends Nexvio_Category_Grid_Widget {

	use Nexvio_Category_Layouts_Trait;

	public function get_name() {
		return 'nexvio_category_grid_layouts';
	}

	public function get_title() {
		return esc_html__( 'Category Grid Layouts', 'nexvio' );
	}

	public function get_keywords() {
		return array( 'nexvio', 'woocommerce', 'category', 'categories', 'grid', 'layout', 'design' );
	}
}
