<?php
/**
 * NEXVIO Products Grid - Elementor widget.
 *
 * A grid of chosen products (latest, featured, on sale, best sellers,
 * categories, tags or IDs) for any page, with its own pagination and the
 * same design layouts as the Product Slider. Separate from the Products
 * Archive widget, which shows the products of the shop / category page.
 *
 * @package Nexvio_For_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-nexvio-product-grid-widget.php';

class Nexvio_Products_Grid_Widget extends Nexvio_Product_Grid_Widget {

	public function get_name() {
		return 'nexvio_products_grid';
	}

	public function get_title() {
		return esc_html__( 'Products Grid', 'nexvio' );
	}

	public function get_icon() {
		return 'eicon-products';
	}

	public function get_keywords() {
		return array( 'nexvio', 'woocommerce', 'products', 'grid', 'featured', 'sale', 'best sellers', 'layout' );
	}

	protected function register_controls() {
		$this->register_design_section();
		$this->register_query_section();
		$this->register_card_sections();
		$this->register_grid_layout_section();
		$this->register_pagination_section();
		$this->register_layout_style_section();
		$this->register_card_style_sections();
		$this->register_pagination_style_section();
		$this->register_empty_style_section();
		$this->limit_classic_controls();

		$this->update_control( 'limit', array(
			'label'       => esc_html__( 'Products per page', 'nexvio' ),
			'description' => esc_html__( 'With pagination on, the next products are on the following pages.', 'nexvio' ),
		) );
	}

	/** Always a custom query; no archive-only features. */
	protected function grid_settings( $s ) {
		$s['source_mode']   = 'custom';
		$s['loop_hooks']    = '';
		$s['show_count']    = '';
		$s['show_sorting']  = '';
		$s['preview_limit'] = isset( $s['limit'] ) ? (int) $s['limit'] : 12;
		return $s;
	}

	/** Not a target of the AJAX Product Filter (it shows chosen products, not the archive). */
	protected function extra_root_classes() {
		return array( 'nexvio-pg--grid' );
	}

	/** Own page parameter per widget: several grids on one page paginate independently. */
	protected function page_param() {
		return 'nexvio-pg-' . sanitize_key( $this->get_id() );
	}
}
