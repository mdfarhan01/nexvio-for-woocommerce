<?php
/**
 * NEXVIO Products Slider (Products Card design) - Elementor widget.
 *
 * Works like the Products Slider Layouts widget (arrows, swipe, mouse drag, autoplay,
 * continuous scroll, endless loop) and shows the products with the Products Card
 * design and its Style tab.
 *
 * @package Nexvio_For_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-nexvio-product-slider-widget.php';
require_once NEXVIO_PATH . 'includes/trait-nexvio-simple-card.php';

class Nexvio_Products_Slider_Widget extends Nexvio_Product_Slider_Widget {

	use Nexvio_Simple_Card_Trait;

	public function get_name() {
		return 'nexvio_products_slider';
	}

	public function get_title() {
		return esc_html__( 'Products Slider', 'nexvio' );
	}

	public function get_keywords() {
		return array( 'nexvio', 'woocommerce', 'products', 'slider', 'carousel', 'card' );
	}

	protected function design_layout_group() {
		return '';
	}

	protected function gallery_ui() {
		return 'simple';
	}

	public function get_settings_for_display( $setting_key = null ) {
		$s = parent::get_settings_for_display( $setting_key );
		return null === $setting_key ? $this->simple_card_settings( $s ) : $s;
	}

	protected function register_controls() {
		/* Content tab */
		$this->register_pc_design_section();
		$this->register_gallery_section();
		$this->register_header_section();
		$this->register_query_section();
		$this->register_card_sections();
		$this->register_slider_section();

		$this->remove_simple_card_controls();

		/* Style tab */
		$this->register_pc_layout_style_section();
		$this->register_pc_image_section();
		$this->register_gs_content_section();
		$this->register_pc_button_section();
		$this->register_gs_badges_section();
		$this->register_header_style_section();
		$this->register_nav_style_section();
	}
}
