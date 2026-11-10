<?php
/**
 * Shared by the Category Grid Layouts and Category Slider Layouts widgets:
 * the 12-design picker and the extra card markup the designs need.
 *
 * @package Nexvio_For_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-nexvio-category-layouts.php';

trait Nexvio_Category_Layouts_Trait {

	protected function add_design_control() {
		$has_lc = class_exists( 'Nexvio_Layout_Control' );
		$this->add_control( 'design', array(
			'label'       => esc_html__( 'Layout', 'nexvio' ),
			'type'        => $has_lc ? Nexvio_Layout_Control::TYPE : \Elementor\Controls_Manager::SELECT,
			'default'     => 'classic',
			'options'     => $has_lc ? Nexvio_Category_Layouts::options_for_control() : Nexvio_Category_Layouts::titles(),
			'description' => esc_html__( 'Click a picture to choose the design. The colours below work with every layout.', 'nexvio' ),
		) );
	}

	protected function text_designs() {
		return Nexvio_Category_Layouts::text_keys();
	}

	protected function no_ratio_designs() {
		return Nexvio_Category_Layouts::fixed_shape_keys();
	}

	protected function overlay_designs() {
		return Nexvio_Category_Layouts::overlay_keys();
	}

	protected function rich_cards() {
		return true;
	}

	protected function design( $s ) {
		return Nexvio_Category_Layouts::exists( $s['design'] ) ? $s['design'] : 'classic';
	}

	protected function root_class( $s ) {
		return 'nxcg nxl nxl--' . $this->design( $s ) . ( 'yes' === $s['hover_zoom'] ? ' has-zoom' : '' );
	}
}
