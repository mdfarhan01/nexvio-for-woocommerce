<?php
/**
 * NEXVIO Products Archive - Elementor widget.
 *
 * The product archive widget with the Products Card design. It shows the products of
 * the WooCommerce shop / product archive page being viewed (category, tag, attribute,
 * search), follows the Nexvio Product Filter and other filter plugins, and uses the
 * archive's own pagination. Cards, controls and Style tab are the Products Card ones.
 *
 * @package Nexvio_For_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;

require_once __DIR__ . '/class-nexvio-products-card-widget.php';

class Nexvio_Products_Archive_Widget extends Nexvio_Products_Card_Widget {

	public function get_name() {
		return 'nexvio_products_archive';
	}

	public function get_title() {
		return esc_html__( 'Products Archive', 'nexvio' );
	}

	public function get_icon() {
		return 'eicon-archive-posts';
	}

	public function get_keywords() {
		return array( 'nexvio', 'woocommerce', 'products', 'archive', 'shop', 'filter', 'pagination', 'card' );
	}

	protected function register_controls() {
		/* Content tab */
		$this->register_pc_design_section();
		$this->register_gallery_section();

		/* The archive always uses the current query (hidden controls keep the shared card code working). */
		$this->start_controls_section( 'section_toolbar', array( 'label' => esc_html__( 'Result count and sorting', 'nexvio' ) ) );
		$this->add_control( 'source_mode', array( 'type' => Controls_Manager::HIDDEN, 'default' => 'current' ) );
		$this->add_control( 'preview_limit', array( 'type' => Controls_Manager::HIDDEN, 'default' => 12 ) );
		$this->switcher( 'show_count', esc_html__( 'Show result count', 'nexvio' ) );
		$this->switcher( 'show_sorting', esc_html__( 'Show sorting dropdown', 'nexvio' ) );
		$this->add_control( 'toolbar_note', array(
			'type'            => Controls_Manager::RAW_HTML,
			'raw'             => esc_html__( 'Use this widget in your shop / product archive template. It shows the products of the page being viewed, with WooCommerce sorting, products per page and the product filters. In the editor you see a preview of recent products. Result count and sorting appear only on the shop / archive page.', 'nexvio' ),
			'content_classes' => 'elementor-descriptor',
		) );
		$this->switcher( 'loop_hooks', esc_html__( 'Run WooCommerce loop hooks', 'nexvio' ), 'yes', array(
			'separator'   => 'before',
			'description' => esc_html__( 'Keep this on: filter plugins and themes use these hooks (active filters, notices, wrappers). Turn off only if something appears twice.', 'nexvio' ),
		) );
		$this->end_controls_section();

		$this->register_query_section( array( 'condition' => array( 'source_mode' => 'custom' ) ) );
		$this->register_card_sections();
		$this->register_grid_layout_section();
		$this->register_pagination_section();

		$this->remove_simple_card_controls();

		/* Style tab */
		$this->register_pc_layout_style_section();
		$this->register_pc_image_section();
		$this->register_gs_content_section();
		$this->register_pc_button_section();
		$this->register_gs_badges_section();
		$this->register_pagination_style_section();
		$this->register_empty_style_section();
	}

	/** Archive context: current query, with the toolbar settings the editor chose. */
	protected function grid_settings( $s ) {
		$own = $s;
		$s   = parent::grid_settings( $s );
		foreach ( array( 'show_count', 'show_sorting', 'loop_hooks' ) as $key ) {
			if ( isset( $own[ $key ] ) ) {
				$s[ $key ] = $own[ $key ];
			}
		}
		$s['source_mode']   = 'current';
		$s['preview_limit'] = 12;
		return $s;
	}

	/** An archive widget: the Product Filter finds it and filters its list. */
	protected function extra_root_classes() {
		return array();
	}

	/** The archive's own page number (WordPress pagination). */
	protected function page_param() {
		return 'product-page';
	}
}
