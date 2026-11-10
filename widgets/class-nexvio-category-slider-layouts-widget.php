<?php
/**
 * NEXVIO Category Slider Layouts - the category slider with 12 design layouts.
 *
 * @package Nexvio_For_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-nexvio-category-slider-widget.php';
require_once dirname( __DIR__ ) . '/includes/trait-nexvio-category-layouts.php';

class Nexvio_Category_Slider_Layouts_Widget extends Nexvio_Category_Slider_Widget {

	use Nexvio_Category_Layouts_Trait;

	public function get_name() {
		return 'nexvio_category_slider_layouts';
	}

	public function get_title() {
		return esc_html__( 'Category Slider Layouts', 'nexvio' );
	}

	public function get_keywords() {
		return array( 'nexvio', 'woocommerce', 'category', 'categories', 'slider', 'carousel', 'layout', 'design' );
	}

	public function get_script_depends() {
		return array( 'nexvio-category-slider' );
	}
}
