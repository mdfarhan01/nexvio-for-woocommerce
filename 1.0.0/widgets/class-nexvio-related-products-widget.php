<?php
/**
 * NEXVIO Related Products (Products Card design) - Elementor widget.
 *
 * Same related-products query and behaviour as the Related Products Layouts widget,
 * shown with the Products Card design and its Style tab.
 *
 * @package Nexvio_For_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-nexvio-product-related-widget.php';
require_once NEXVIO_PATH . 'includes/trait-nexvio-simple-card.php';

class Nexvio_Related_Products_Widget extends Nexvio_Product_Related_Widget {

	use Nexvio_Simple_Card_Trait;

	public function get_name() {
		return 'nexvio_related_products';
	}

	public function get_title() {
		return esc_html__( 'Related Products', 'nexvio' );
	}

	public function get_keywords() {
		return array( 'nexvio', 'woocommerce', 'related', 'products', 'similar', 'category', 'card' );
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
		$this->register_related_section();
		$this->register_card_sections();
		$this->register_layout_section();
		$this->register_slider_section( array( 'condition' => array( 'layout' => 'slider' ) ) );

		$this->remove_simple_card_controls();

		/* Style tab */
		$this->register_pc_layout_style_section();
		$this->register_pc_image_section();
		$this->register_gs_content_section();
		$this->register_pc_button_section();
		$this->register_gs_badges_section();
		$this->register_header_style_section();
		$this->register_nav_style_section( array( 'condition' => array( 'layout' => 'slider' ) ) );

		/* related-specific defaults for shared controls */
		$this->update_control( 'title', array( 'default' => 'You may also like' ) );
		$this->update_control( 'subtitle', array( 'default' => 'More products from the same collection.' ) );
		$this->update_control( 'all_text', array( 'default' => 'View collection' ) );
		$this->update_control( 'all_link', array(
			'default'     => array( 'url' => '' ),
			'description' => esc_html__( 'Leave empty to link automatically to the category used for the related products.', 'nexvio' ),
		) );
	}
}
