<?php
/**
 * NEXVIO Wishlist Card (Products Card design) - Elementor widget.
 *
 * Same wishlist data, live removal and behaviour as the Wishlist Card Layouts widget,
 * shown with the Products Card design and its Style tab.
 *
 * @package Nexvio_For_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-nexvio-wishlist-grid-widget.php';
require_once NEXVIO_PATH . 'includes/trait-nexvio-simple-card.php';

class Nexvio_Wishlist_Card_Widget extends Nexvio_Wishlist_Grid_Widget {

	use Nexvio_Simple_Card_Trait;

	public function get_name() {
		return 'nexvio_wishlist_card';
	}

	public function get_title() {
		return esc_html__( 'Wishlist Card', 'nexvio' );
	}

	public function get_keywords() {
		return array( 'nexvio', 'wishlist', 'favorites', 'card', 'products' );
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
		$this->register_wl_title_section();
		$this->register_wl_products_section();
		$this->register_wl_empty_section();
		$this->register_card_sections();
		$this->register_wl_layout_section();

		$this->remove_simple_card_controls();

		/* Style tab */
		$this->register_pc_layout_style_section();
		$this->register_pc_image_section();
		$this->register_gs_content_section();
		$this->register_pc_button_section();
		$this->register_gs_badges_section();
		$this->register_wl_title_style_section();
		$this->register_wl_empty_style_section();
	}
}
