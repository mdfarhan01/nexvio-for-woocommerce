<?php
/**
 * NEXVIO Products Card - Elementor widget.
 *
 * A grid of simple product cards: image on top, then title, description,
 * price and a button. It has the same product query, columns and
 * pagination as the Products Grid, but one plain design and a short Style tab.
 *
 * Style tab (7 sections):
 * 1. Layout design: card background, border (top, right, bottom, left), text area padding, box shadow.
 * 2. Image: Default / Cover / Contain fit, the image controls and the image area.
 * 3. Card Content: title, description, price (and the other product info) with colour, typography and spacing.
 * 4. Button (when the button is on): font, normal and gradient background, text and icon colour, padding, spacing, radius.
 * 5. Badges: text (font), padding, margin, radius for every badge.
 * 6. Pagination.
 * 7. No products message.
 *
 * @package Nexvio_For_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Css_Filter;
use Elementor\Group_Control_Background;

require_once __DIR__ . '/class-nexvio-products-grid-widget.php';
require_once NEXVIO_PATH . 'includes/trait-nexvio-simple-card.php';

class Nexvio_Products_Card_Widget extends Nexvio_Products_Grid_Widget {

	use Nexvio_Simple_Card_Trait;

	/** Gallery hover with the animation settings (see the card trait). */
	protected function gallery_ui() {
		return 'simple';
	}

	public function get_name() {
		return 'nexvio_products_card';
	}

	public function get_title() {
		return esc_html__( 'Products Card', 'nexvio' );
	}

	public function get_icon() {
		return 'eicon-posts-grid';
	}

	public function get_keywords() {
		return array( 'nexvio', 'woocommerce', 'products', 'product card', 'card', 'simple', 'grid' );
	}

	/* ------------------------------------------------------------------
	 * Controls
	 * ------------------------------------------------------------------ */

	protected function register_controls() {
		/* Content tab */
		$this->register_pc_design_section();
		$this->register_gallery_section();
		$this->register_query_section();
		$this->register_card_sections();
		$this->register_grid_layout_section();
		$this->register_pagination_section();

		/* This widget has one design and no classic-only options. */
		$this->remove_simple_card_controls();

		/* Style tab */
		$this->register_pc_layout_style_section();
		$this->register_pc_image_section();
		$this->register_gs_content_section();
		$this->register_pc_button_section();
		$this->register_gs_badges_section();
		$this->register_pagination_style_section();
		$this->register_empty_style_section();

		$this->update_control( 'limit', array(
			'label'       => esc_html__( 'Products per page', 'nexvio' ),
			'description' => esc_html__( 'With pagination on, the next products are on the following pages.', 'nexvio' ),
		) );
	}

	/** The single "simple" design and the defaults of the options that were left out. */
	protected function grid_settings( $s ) {
		return $this->simple_card_settings( parent::grid_settings( $s ) );
	}
}
