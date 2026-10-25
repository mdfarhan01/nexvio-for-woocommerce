<?php
/**
 * NEXVIO Product Grid - Elementor widget.
 *
 * Same card design as the Product Slider, laid out as a grid with
 * pagination. In "Current query" mode it renders the main WooCommerce
 * archive query, so product filter plugins (which filter that query)
 * work with it. It outputs the standard WooCommerce markup hooks
 * (ul.products, li.product, .woocommerce-pagination, result count,
 * ordering) that AJAX filter plugins look for.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

class Nexvio_Product_Grid_Widget extends Widget_Base {

	use Nexvio_Card_Trait;

	public function get_name() {
		return 'nexvio_product_grid';
	}

	public function get_title() {
		return esc_html__( 'Products Archive', 'nexvio' );
	}

	public function get_icon() {
		return 'eicon-gallery-grid';
	}

	public function get_categories() {
		return array( 'nexvio' );
	}

	public function get_keywords() {
		return array( 'nexvio', 'woocommerce', 'product', 'grid', 'archive', 'shop', 'filter', 'pagination' );
	}

	public function get_style_depends() {
		return array( 'nexvio' );
	}

	public function get_script_depends() {
		return array( 'nexvio', 'nexvio-view-switch' );
	}

	/* ------------------------------------------------------------------
	 * Controls
	 * ------------------------------------------------------------------ */

	protected function register_controls() {
		$this->register_design_section();
		$this->register_view_section();
		$this->register_source_section();
		$this->register_query_section( array( 'condition' => array( 'source_mode' => 'custom' ) ) );
		$this->register_toolbar_section();
		$this->register_card_sections();
		$this->register_grid_layout_section( array( 'conditions' => self::view_conditions( 'grid' ) ) );
		$this->register_list_section();
		$this->register_pagination_section();
		$this->register_layout_style_section();
		$this->register_list_style_section();
		$this->register_card_style_sections();
		$this->register_toolbar_style_section();
		$this->register_pagination_style_section();
		$this->register_empty_style_section();
		$this->limit_classic_controls();
	}

	protected function register_source_section() {
		/* ============ SOURCE ============ */
		$this->start_controls_section( 'section_source', array( 'label' => esc_html__( 'Products source', 'nexvio' ) ) );
		$this->add_control( 'source_mode', array(
			'label'   => esc_html__( 'Products to show', 'nexvio' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'current',
			'options' => array(
				'current' => esc_html__( 'Current query (shop / category archive)', 'nexvio' ),
				'custom'  => esc_html__( 'Custom query', 'nexvio' ),
			),
		) );
		$this->add_control( 'source_note', array(
			'type'            => Controls_Manager::RAW_HTML,
			'raw'             => esc_html__( 'Use "Current query" in your shop / product archive template. It shows the products of the page being viewed (shop, category, tag, attribute, search), with WooCommerce sorting, products per page and any product filter plugin. In the editor you see a preview of recent products.', 'nexvio' ),
			'content_classes' => 'elementor-descriptor',
			'condition'       => array( 'source_mode' => 'current' ),
		) );
		$this->add_control( 'custom_note', array(
			'type'            => Controls_Manager::RAW_HTML,
			'raw'             => esc_html__( 'Custom query: set the products in the "Products (query)" section. "Number of products" becomes products per page. Filter plugins only work with "Current query". Tip: for a grid of chosen products on a normal page, use the separate "Products Grid" widget.', 'nexvio' ),
			'content_classes' => 'elementor-descriptor',
			'condition'       => array( 'source_mode' => 'custom' ),
		) );
		$this->add_control( 'preview_limit', array(
			'label'     => esc_html__( 'Editor preview: number of products', 'nexvio' ),
			'type'      => Controls_Manager::NUMBER,
			'min'       => 1,
			'max'       => 48,
			'default'   => 12,
			'condition' => array( 'source_mode' => 'current' ),
		) );
		$this->end_controls_section();

	}

	protected function register_toolbar_section() {
		/* ============ TOOLBAR ============ */
		$this->start_controls_section( 'section_toolbar', array( 'label' => esc_html__( 'Result count and sorting', 'nexvio' ) ) );
		$this->switcher( 'show_count', esc_html__( 'Show result count', 'nexvio' ) );
		$this->switcher( 'show_sorting', esc_html__( 'Show sorting dropdown', 'nexvio' ) );
		$this->add_control( 'toolbar_note', array(
			'type'            => Controls_Manager::RAW_HTML,
			'raw'             => esc_html__( 'Result count and sorting appear only with "Current query" on a shop / archive page.', 'nexvio' ),
			'content_classes' => 'elementor-descriptor',
		) );
		$this->switcher( 'loop_hooks', esc_html__( 'Run WooCommerce loop hooks', 'nexvio' ), 'yes', array(
			'separator'   => 'before',
			'description' => esc_html__( 'Keep this on: filter plugins and themes use these hooks (active filters, notices, wrappers). Turn off only if something appears twice.', 'nexvio' ),
		) );
		$this->end_controls_section();

	}

	/** Section shows when that view is the default, or when visitors can switch. */
	protected static function view_conditions( $view ) {
		return array(
			'relation' => 'or',
			'terms'    => array(
				array( 'name' => 'view_mode', 'operator' => '==', 'value' => $view ),
				array( 'name' => 'view_switch', 'operator' => '==', 'value' => 'yes' ),
			),
		);
	}

	/* ============ VIEW MODE (Products Archive) ============ */
	protected function register_view_section() {
		$this->start_controls_section( 'section_view', array( 'label' => esc_html__( 'View Mode', 'nexvio' ) ) );
		$this->add_control( 'view_mode', array(
			'label'       => esc_html__( 'View', 'nexvio' ),
			'type'        => Controls_Manager::CHOOSE,
			'default'     => 'grid',
			'toggle'      => false,
			'label_block' => false,
			'options'     => array(
				'grid' => array( 'title' => esc_html__( 'Grid View', 'nexvio' ), 'icon' => 'eicon-gallery-grid' ),
				'list' => array( 'title' => esc_html__( 'List View', 'nexvio' ), 'icon' => 'eicon-post-list' ),
			),
			'description' => esc_html__( 'Grid: cards in rows and columns. List: image and details side by side.', 'nexvio' ),
		) );
		$this->switcher( 'view_switch', esc_html__( 'Let shoppers switch Grid / List', 'nexvio' ), '', array(
			'separator'   => 'before',
			'description' => esc_html__( 'Adds two buttons above the products. The view above is the starting view.', 'nexvio' ),
		) );
		$this->switcher( 'view_remember', esc_html__( 'Remember the shopper\'s choice', 'nexvio' ), 'yes', array( 'condition' => array( 'view_switch' => 'yes' ) ) );
		$this->text( 'view_grid_label', esc_html__( 'Grid button label (screen readers)', 'nexvio' ), 'Grid view', array( 'condition' => array( 'view_switch' => 'yes' ) ) );
		$this->text( 'view_list_label', esc_html__( 'List button label (screen readers)', 'nexvio' ), 'List view', array( 'condition' => array( 'view_switch' => 'yes' ) ) );
		$this->end_controls_section();
	}

	/* ============ LIST VIEW (Products Archive) ============ */
	protected function register_list_section() {
		$this->start_controls_section( 'section_list', array( 'label' => esc_html__( 'List view', 'nexvio' ), 'conditions' => self::view_conditions( 'list' ) ) );
		$this->add_responsive_control( 'list_img_w', array(
			'label'          => esc_html__( 'Image width', 'nexvio' ),
			'type'           => Controls_Manager::SLIDER,
			'size_units'     => array( '%', 'px' ),
			'range'          => array( '%' => array( 'min' => 15, 'max' => 60 ), 'px' => array( 'min' => 100, 'max' => 600 ) ),
			'default'        => array( 'size' => 30, 'unit' => '%' ),
			'tablet_default' => array( 'size' => 36, 'unit' => '%' ),
			'mobile_default' => array( 'size' => 40, 'unit' => '%' ),
			'selectors'      => array( '{{WRAPPER}} .nexvio-pg' => '--nexvio-pg-list-img: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_control( 'list_ratio', array(
			'label'     => esc_html__( 'Image shape', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => '',
			'options'   => array( '' => esc_html__( 'Full card height', 'nexvio' ), '1/1' => esc_html__( 'Square', 'nexvio' ), '4/3' => '4:3', '3/4' => '3:4', '16/9' => '16:9' ),
			'selectors' => array( '{{WRAPPER}} .nexvio-pg' => '--nexvio-pg-list-ratio: {{VALUE}}; --nexvio-pg-list-media-align: center;' ),
		) );
		$this->add_responsive_control( 'list_min_h', array(
			'label'      => esc_html__( 'Minimum height', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 80, 'max' => 500 ) ),
			'default'    => array( 'size' => 190, 'unit' => 'px' ),
			'selectors'  => array( '{{WRAPPER}} .nexvio-pg' => '--nexvio-pg-list-min-h: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_control( 'list_actions', array(
			'label'     => esc_html__( 'Price and button', 'nexvio' ),
			'type'      => Controls_Manager::CHOOSE,
			'default'   => 'right',
			'toggle'    => false,
			'options'   => array(
				'right' => array( 'title' => esc_html__( 'In a column on the right', 'nexvio' ), 'icon' => 'eicon-h-align-right' ),
				'below' => array( 'title' => esc_html__( 'Below the details', 'nexvio' ), 'icon' => 'eicon-v-align-bottom' ),
			),
			'separator' => 'before',
		) );
		$this->add_responsive_control( 'list_valign', array(
			'label'     => esc_html__( 'Vertical alignment', 'nexvio' ),
			'type'      => Controls_Manager::CHOOSE,
			'default'   => 'center',
			'options'   => array(
				'flex-start' => array( 'title' => esc_html__( 'Top', 'nexvio' ), 'icon' => 'eicon-v-align-top' ),
				'center'     => array( 'title' => esc_html__( 'Middle', 'nexvio' ), 'icon' => 'eicon-v-align-middle' ),
				'flex-end'   => array( 'title' => esc_html__( 'Bottom', 'nexvio' ), 'icon' => 'eicon-v-align-bottom' ),
			),
			'selectors' => array( '{{WRAPPER}} .nexvio-pg' => '--nexvio-pg-list-valign: {{VALUE}};' ),
		) );
		$this->add_responsive_control( 'list_talign', array(
			'label'     => esc_html__( 'Text alignment', 'nexvio' ),
			'type'      => Controls_Manager::CHOOSE,
			'default'   => 'left',
			'options'   => array(
				'left'   => array( 'title' => esc_html__( 'Left', 'nexvio' ), 'icon' => 'eicon-text-align-left' ),
				'center' => array( 'title' => esc_html__( 'Center', 'nexvio' ), 'icon' => 'eicon-text-align-center' ),
				'right'  => array( 'title' => esc_html__( 'Right', 'nexvio' ), 'icon' => 'eicon-text-align-right' ),
			),
			'selectors_dictionary' => array(
				'left'   => '--nexvio-pg-list-talign:left;--nexvio-pg-list-justify:flex-start;',
				'center' => '--nexvio-pg-list-talign:center;--nexvio-pg-list-justify:center;',
				'right'  => '--nexvio-pg-list-talign:right;--nexvio-pg-list-justify:flex-end;',
			),
			'selectors' => array( '{{WRAPPER}} .nexvio-pg' => '{{VALUE}}' ),
		) );
		$this->switcher( 'list_excerpt', esc_html__( 'Short description in list view', 'nexvio' ), 'yes', array( 'separator' => 'before', 'description' => esc_html__( 'Shown only in List View. To show it in both views, use Description & rating > Show short description.', 'nexvio' ) ) );
		$this->add_control( 'list_words', array(
			'label'     => esc_html__( 'Description length (words)', 'nexvio' ),
			'type'      => Controls_Manager::NUMBER,
			'min'       => 5,
			'max'       => 80,
			'default'   => 24,
			'condition' => array( 'list_excerpt' => 'yes' ),
		) );
		$this->switcher( 'list_stack', esc_html__( 'Stack image above details on mobile', 'nexvio' ), 'yes', array( 'separator' => 'before' ) );
		$this->end_controls_section();
	}

	/* ============ STYLE: LIST VIEW ============ */
	protected function register_list_style_section() {
		$this->start_controls_section( 'style_list', array( 'label' => esc_html__( 'List view', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE, 'conditions' => self::view_conditions( 'list' ) ) );
		$this->add_responsive_control( 'list_gap', array(
			'label'      => esc_html__( 'Space between products', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 80 ) ),
			'default'    => array( 'size' => 16, 'unit' => 'px' ),
			'selectors'  => array( '{{WRAPPER}} .nexvio-pg' => '--nexvio-pg-list-gap: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'list_inner_gap', array(
			'label'      => esc_html__( 'Space between image and details', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
			'default'    => array( 'size' => 8, 'unit' => 'px' ),
			'selectors'  => array( '{{WRAPPER}} .nexvio-pg' => '--nexvio-pg-list-inner-gap: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'list_pad', array(
			'label'      => esc_html__( 'Details padding', 'nexvio' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px' ),
			'selectors'  => array( '{{WRAPPER}} .nexvio-pg.nexvio-pg--list .nexvio-ps__body' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );
		$this->add_control( 'list_h_card', array( 'label' => esc_html__( 'Card', 'nexvio' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_control( 'list_card_bg', array( 'label' => esc_html__( 'Background', 'nexvio' ), 'type' => Controls_Manager::COLOR, 'default' => '', 'selectors' => array( '{{WRAPPER}} .nexvio-pg.nexvio-pg--list .nexvio-ps__card' => 'background: {{VALUE}};' ) ) );
		$this->add_control( 'list_card_border', array( 'label' => esc_html__( 'Border colour', 'nexvio' ), 'type' => Controls_Manager::COLOR, 'default' => '', 'selectors' => array( '{{WRAPPER}} .nexvio-pg.nexvio-pg--list .nexvio-ps__card' => 'border-color: {{VALUE}};' ) ) );
		$this->add_control( 'list_card_radius', array( 'label' => esc_html__( 'Corner radius (px)', 'nexvio' ), 'type' => Controls_Manager::NUMBER, 'min' => 0, 'max' => 60, 'default' => '', 'selectors' => array( '{{WRAPPER}} .nexvio-pg.nexvio-pg--list .nexvio-ps__card' => 'border-radius: {{VALUE}}px;' ) ) );
		$this->add_control( 'list_h_text', array( 'label' => esc_html__( 'Text', 'nexvio' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_control( 'list_name_color', array( 'label' => esc_html__( 'Name colour', 'nexvio' ), 'type' => Controls_Manager::COLOR, 'default' => '', 'selectors' => array( '{{WRAPPER}} .nexvio-pg.nexvio-pg--list .nexvio-ps__name' => 'color: {{VALUE}};' ) ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'list_name_typo', 'label' => esc_html__( 'Name typography', 'nexvio' ), 'selector' => '{{WRAPPER}} .nexvio-pg.nexvio-pg--list .nexvio-ps__name' ) );
		$this->add_control( 'list_excerpt_color', array( 'label' => esc_html__( 'Description colour', 'nexvio' ), 'type' => Controls_Manager::COLOR, 'default' => '', 'selectors' => array( '{{WRAPPER}} .nexvio-pg.nexvio-pg--list .nexvio-ps__excerpt' => 'color: {{VALUE}};' ), 'condition' => array( 'list_excerpt' => 'yes' ) ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'list_excerpt_typo', 'label' => esc_html__( 'Description typography', 'nexvio' ), 'selector' => '{{WRAPPER}} .nexvio-pg.nexvio-pg--list .nexvio-ps__excerpt', 'condition' => array( 'list_excerpt' => 'yes' ) ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'list_price_typo', 'label' => esc_html__( 'Price typography', 'nexvio' ), 'selector' => '{{WRAPPER}} .nexvio-pg.nexvio-pg--list .nexvio-ps__price' ) );
		$this->add_control( 'list_h_switch', array( 'label' => esc_html__( 'Grid / List buttons', 'nexvio' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before', 'condition' => array( 'view_switch' => 'yes' ) ) );
		$this->add_control( 'vs_color', array( 'label' => esc_html__( 'Icon colour', 'nexvio' ), 'type' => Controls_Manager::COLOR, 'default' => '', 'selectors' => array( '{{WRAPPER}} .nexvio-pg__view button' => 'color: {{VALUE}};' ), 'condition' => array( 'view_switch' => 'yes' ) ) );
		$this->add_control( 'vs_active', array( 'label' => esc_html__( 'Active background', 'nexvio' ), 'type' => Controls_Manager::COLOR, 'default' => '', 'selectors' => array( '{{WRAPPER}} .nexvio-pg__view button[aria-pressed="true"]' => 'background: {{VALUE}}; border-color: {{VALUE}};' ), 'condition' => array( 'view_switch' => 'yes' ) ) );
		$this->end_controls_section();
	}

	protected function register_grid_layout_section( $section_args = array() ) {
		/* ============ LAYOUT ============ */
		$this->start_controls_section( 'section_layout', array_merge( array( 'label' => esc_html__( 'Grid layout', 'nexvio' ) ), $section_args ) );
		$this->add_responsive_control( 'columns', array(
			'label'          => esc_html__( 'Columns', 'nexvio' ),
			'type'           => Controls_Manager::NUMBER,
			'min'            => 1,
			'max'            => 8,
			'default'        => 4,
			'tablet_default' => 3,
			'mobile_default' => 2,
			'selectors'      => array( '{{WRAPPER}} .nexvio-pg' => '--nexvio-pg-cols: {{VALUE}};' ),
		) );
		$this->add_responsive_control( 'col_gap', array(
			'label'          => esc_html__( 'Column gap', 'nexvio' ),
			'type'           => Controls_Manager::SLIDER,
			'size_units'     => array( 'px' ),
			'range'          => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
			'default'        => array( 'size' => 18, 'unit' => 'px' ),
			'mobile_default' => array( 'size' => 10, 'unit' => 'px' ),
			'selectors'      => array( '{{WRAPPER}} .nexvio-pg' => '--nexvio-pg-col-gap: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'row_gap', array(
			'label'          => esc_html__( 'Row gap', 'nexvio' ),
			'type'           => Controls_Manager::SLIDER,
			'size_units'     => array( 'px' ),
			'range'          => array( 'px' => array( 'min' => 0, 'max' => 80 ) ),
			'default'        => array( 'size' => 22, 'unit' => 'px' ),
			'mobile_default' => array( 'size' => 12, 'unit' => 'px' ),
			'selectors'      => array( '{{WRAPPER}} .nexvio-pg' => '--nexvio-pg-row-gap: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'grid_talign', array(
			'label'     => esc_html__( 'Text alignment', 'nexvio' ),
			'type'      => Controls_Manager::CHOOSE,
			'options'   => array(
				'left'   => array( 'title' => esc_html__( 'Left', 'nexvio' ), 'icon' => 'eicon-text-align-left' ),
				'center' => array( 'title' => esc_html__( 'Center', 'nexvio' ), 'icon' => 'eicon-text-align-center' ),
				'right'  => array( 'title' => esc_html__( 'Right', 'nexvio' ), 'icon' => 'eicon-text-align-right' ),
			),
			'selectors' => array( '{{WRAPPER}} .nexvio-pg:not(.nexvio-pg--list) .nexvio-ps__body' => 'text-align: {{VALUE}};' ),
		) );
		$this->end_controls_section();

	}

	protected function register_pagination_section() {
		/* ============ PAGINATION ============ */
		$this->start_controls_section( 'section_pagination', array( 'label' => esc_html__( 'Pagination', 'nexvio' ) ) );
		$this->switcher( 'show_pagination', esc_html__( 'Show pagination', 'nexvio' ) );
		$this->add_control( 'pag_type', array(
			'label'     => esc_html__( 'Type', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'numbers',
			'options'   => array(
				'numbers'  => esc_html__( 'Numbers + previous / next', 'nexvio' ),
				'only_num' => esc_html__( 'Numbers only', 'nexvio' ),
				'prevnext' => esc_html__( 'Previous / next only', 'nexvio' ),
			),
			'condition' => array( 'show_pagination' => 'yes' ),
		) );
		$this->text( 'prev_text', esc_html__( 'Previous text', 'nexvio' ), '‹', array( 'condition' => array( 'show_pagination' => 'yes', 'pag_type!' => 'only_num' ) ) );
		$this->text( 'next_text', esc_html__( 'Next text', 'nexvio' ), '›', array( 'condition' => array( 'show_pagination' => 'yes', 'pag_type!' => 'only_num' ) ) );
		$this->add_control( 'mid_size', array(
			'label'     => esc_html__( 'Page numbers around the current page', 'nexvio' ),
			'type'      => Controls_Manager::NUMBER,
			'min'       => 0,
			'max'       => 5,
			'default'   => 2,
			'condition' => array( 'show_pagination' => 'yes', 'pag_type!' => 'prevnext' ),
		) );
		$this->add_control( 'end_size', array(
			'label'     => esc_html__( 'Page numbers at the start and end', 'nexvio' ),
			'type'      => Controls_Manager::NUMBER,
			'min'       => 1,
			'max'       => 5,
			'default'   => 1,
			'condition' => array( 'show_pagination' => 'yes', 'pag_type!' => 'prevnext' ),
		) );
		$this->add_control( 'h_empty', array( 'label' => esc_html__( 'No products found', 'nexvio' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_control( 'empty_text', array(
			'label'   => esc_html__( 'Message', 'nexvio' ),
			'type'    => Controls_Manager::TEXTAREA,
			'rows'    => 2,
			'default' => 'No products match your selection. Remove a filter or try another search.',
		) );
		$this->end_controls_section();

	}

	protected function register_toolbar_style_section() {
		/* ============ STYLE: TOOLBAR ============ */
		$this->start_controls_section( 'style_toolbar', array( 'label' => esc_html__( 'Result count and sorting', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_control( 'count_color', array( 'label' => esc_html__( 'Result count colour', 'nexvio' ), 'type' => Controls_Manager::COLOR, 'default' => '#8f9aab', 'selectors' => array( '{{WRAPPER}} .nexvio-pg .woocommerce-result-count' => 'color: {{VALUE}};' ) ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'count_typo', 'selector' => '{{WRAPPER}} .nexvio-pg .woocommerce-result-count' ) );
		$this->color_var( 'select_bg', esc_html__( 'Dropdown background', 'nexvio' ), '--nexvio-pg-select-bg', '#181d24' );
		$this->color_var( 'select_border', esc_html__( 'Dropdown border', 'nexvio' ), '--nexvio-pg-select-border', '#313945' );
		$this->color_var( 'select_color', esc_html__( 'Dropdown text', 'nexvio' ), '--nexvio-pg-select-color', '#e8edf4' );
		$this->add_responsive_control( 'toolbar_space', array(
			'label'      => esc_html__( 'Space below', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 80 ) ),
			'default'    => array( 'size' => 22, 'unit' => 'px' ),
			'selectors'  => array( '{{WRAPPER}} .nexvio-pg__toolbar' => 'margin-bottom: {{SIZE}}{{UNIT}};' ),
		) );
		$this->end_controls_section();

	}

	protected function register_pagination_style_section() {
		/* ============ STYLE: PAGINATION ============ */
		$this->start_controls_section( 'style_pagination', array( 'label' => esc_html__( 'Pagination', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE, 'condition' => array( 'show_pagination' => 'yes' ) ) );
		$this->add_responsive_control( 'pag_align', array(
			'label'   => esc_html__( 'Alignment', 'nexvio' ),
			'type'    => Controls_Manager::CHOOSE,
			'options' => array(
				'flex-start' => array( 'title' => esc_html__( 'Left', 'nexvio' ), 'icon' => 'eicon-text-align-left' ),
				'center'     => array( 'title' => esc_html__( 'Center', 'nexvio' ), 'icon' => 'eicon-text-align-center' ),
				'flex-end'   => array( 'title' => esc_html__( 'Right', 'nexvio' ), 'icon' => 'eicon-text-align-right' ),
			),
			'default'   => 'center',
			'selectors' => array( '{{WRAPPER}} .nexvio-pg' => '--nexvio-pg-p-align: {{VALUE}};' ),
		) );
		$this->add_responsive_control( 'pag_space', array(
			'label'      => esc_html__( 'Space above', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 120 ) ),
			'default'    => array( 'size' => 40, 'unit' => 'px' ),
			'selectors'  => array( '{{WRAPPER}} .nexvio-pg' => '--nexvio-pg-p-space: {{SIZE}}{{UNIT}};' ),
		) );
		$this->number_var( 'pag_size', esc_html__( 'Button size (px)', 'nexvio' ), '--nexvio-pg-p-size', 42, 24, 80, 1, 'px' );
		$this->number_var( 'pag_gap', esc_html__( 'Space between (px)', 'nexvio' ), '--nexvio-pg-p-gap', 8, 0, 30, 1, 'px' );
		$this->number_var( 'pag_radius', esc_html__( 'Corner radius (px)', 'nexvio' ), '--nexvio-pg-p-radius', 4, 0, 40, 1, 'px' );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'pag_typo', 'selector' => '{{WRAPPER}} .nexvio-pg .woocommerce-pagination .page-numbers li .page-numbers' ) );
		$this->add_control( 'h_pag_n', array( 'label' => esc_html__( 'Normal', 'nexvio' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->color_var( 'pag_bg', esc_html__( 'Background', 'nexvio' ), '--nexvio-pg-p-bg', '#1f252e' );
		$this->color_var( 'pag_color', esc_html__( 'Text', 'nexvio' ), '--nexvio-pg-p-color', '#e8edf4' );
		$this->color_var( 'pag_border', esc_html__( 'Border', 'nexvio' ), '--nexvio-pg-p-border', '#313945' );
		$this->add_control( 'h_pag_h', array( 'label' => esc_html__( 'Hover', 'nexvio' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->color_var( 'pag_bg_h', esc_html__( 'Background', 'nexvio' ), '--nexvio-pg-p-bg-h', '#262d37' );
		$this->color_var( 'pag_color_h', esc_html__( 'Text', 'nexvio' ), '--nexvio-pg-p-color-h', '#ffffff' );
		$this->color_var( 'pag_border_h', esc_html__( 'Border', 'nexvio' ), '--nexvio-pg-p-border-h', '#3d8bff' );
		$this->add_control( 'h_pag_a', array( 'label' => esc_html__( 'Current page', 'nexvio' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->color_var( 'pag_bg_a', esc_html__( 'Background', 'nexvio' ), '--nexvio-pg-p-bg-a', '#3d8bff' );
		$this->color_var( 'pag_color_a', esc_html__( 'Text', 'nexvio' ), '--nexvio-pg-p-color-a', '#ffffff' );
		$this->color_var( 'pag_border_a', esc_html__( 'Border', 'nexvio' ), '--nexvio-pg-p-border-a', '#3d8bff' );
		$this->end_controls_section();

	}

	protected function register_empty_style_section() {
		/* ============ STYLE: EMPTY ============ */
		$this->start_controls_section( 'style_empty', array( 'label' => esc_html__( 'No products message', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_control( 'empty_color', array( 'label' => esc_html__( 'Text colour', 'nexvio' ), 'type' => Controls_Manager::COLOR, 'default' => '#8f9aab', 'selectors' => array( '{{WRAPPER}} .nexvio-pg__empty' => 'color: {{VALUE}};' ) ) );
		$this->add_control( 'empty_border', array( 'label' => esc_html__( 'Border colour', 'nexvio' ), 'type' => Controls_Manager::COLOR, 'default' => '#424b58', 'selectors' => array( '{{WRAPPER}} .nexvio-pg__empty' => 'border-color: {{VALUE}};' ) ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'empty_typo', 'selector' => '{{WRAPPER}} .nexvio-pg__empty' ) );
		$this->end_controls_section();
	}

	/* ------------------------------------------------------------------
	 * Query helpers
	 * ------------------------------------------------------------------ */

	/** Settings hook: the Products Grid widget forces its own mode here. */
	protected function grid_settings( $s ) {
		return $s;
	}

	/** Grid / List buttons for shoppers. */
	private function render_view_switch( $s, $view ) {
		$grid_icon = '<svg viewBox="0 0 20 20" aria-hidden="true"><rect x="2" y="2" width="7" height="7" rx="1.5"/><rect x="11" y="2" width="7" height="7" rx="1.5"/><rect x="2" y="11" width="7" height="7" rx="1.5"/><rect x="11" y="11" width="7" height="7" rx="1.5"/></svg>';
		$list_icon = '<svg viewBox="0 0 20 20" aria-hidden="true"><rect x="2" y="3" width="5" height="5" rx="1.2"/><rect x="9" y="4" width="9" height="3" rx="1.2"/><rect x="2" y="12" width="5" height="5" rx="1.2"/><rect x="9" y="13" width="9" height="3" rx="1.2"/></svg>';
		echo '<div class="nexvio-pg__view" role="group" aria-label="' . esc_attr__( 'View', 'nexvio' ) . '">';
		echo wp_kses( '<button type="button" data-view="grid" aria-pressed="' . ( 'grid' === $view ? 'true' : 'false' ) . '" aria-label="' . esc_attr( $s['view_grid_label'] ) . '" title="' . esc_attr( $s['view_grid_label'] ) . '">' . $grid_icon . '</button>', Nexvio_Plugin::allowed_html() );
		echo wp_kses( '<button type="button" data-view="list" aria-pressed="' . ( 'list' === $view ? 'true' : 'false' ) . '" aria-label="' . esc_attr( $s['view_list_label'] ) . '" title="' . esc_attr( $s['view_list_label'] ) . '">' . $list_icon . '</button>', Nexvio_Plugin::allowed_html() );
		echo '</div>';
	}

	/** Extra classes on the wrapper (the Products Grid marks itself so the filter leaves it alone). */
	protected function extra_root_classes() {
		return array();
	}

	/** URL parameter used for the page number of a custom query. */
	protected function page_param() {
		return 'product-page';
	}

	private function is_product_archive() {
		if ( ! function_exists( 'is_shop' ) ) {
			return false;
		}
		if ( is_shop() || is_product_taxonomy() || is_post_type_archive( 'product' ) ) {
			return true;
		}
		if ( is_search() ) {
			$pt = get_query_var( 'post_type' );
			return 'product' === $pt || ( is_array( $pt ) && in_array( 'product', $pt, true ) );
		}
		return false;
	}

	private function is_edit_mode() {
		return class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode();
	}

	/* ------------------------------------------------------------------
	 * Pagination
	 * ------------------------------------------------------------------ */

	private function page_url( $n, $is_main ) {
		if ( $is_main ) {
			return remove_query_arg( 'add-to-cart', get_pagenum_link( $n, false ) );
		}
		$param = $this->page_param();
		return $n > 1 ? add_query_arg( $param, $n ) : remove_query_arg( $param );
	}

	private function render_pagination( $s, $current, $total, $is_main ) {
		if ( 'yes' !== $s['show_pagination'] || $total < 2 ) {
			return;
		}
		$label = esc_attr__( 'Product pagination', 'nexvio' );
		$prev  = esc_html( $s['prev_text'] );
		$next  = esc_html( $s['next_text'] );

		if ( 'prevnext' === $s['pag_type'] ) {
			echo wp_kses( '<nav class="woocommerce-pagination nexvio-pg__pagination" aria-label="' . $label . '"><ul class="page-numbers">', Nexvio_Plugin::allowed_html() );
			if ( $current > 1 ) {
				echo wp_kses( '<li><a class="prev page-numbers" href="' . esc_url( $this->page_url( $current - 1, $is_main ) ) . '">' . $prev . '</a></li>', Nexvio_Plugin::allowed_html() );
			}
			echo '<li><span class="page-numbers nexvio-pg__of">' . esc_html( $current . ' / ' . $total ) . '</span></li>';
			if ( $current < $total ) {
				echo wp_kses( '<li><a class="next page-numbers" href="' . esc_url( $this->page_url( $current + 1, $is_main ) ) . '">' . $next . '</a></li>', Nexvio_Plugin::allowed_html() );
			}
			echo '</ul></nav>';
			return;
		}

		if ( $is_main ) {
			$base   = esc_url_raw( str_replace( 999999999, '%#%', remove_query_arg( 'add-to-cart', get_pagenum_link( 999999999, false ) ) ) );
			$format = '';
		} else {
			$base   = esc_url_raw( add_query_arg( $this->page_param(), '%#%', remove_query_arg( $this->page_param() ) ) );
			$format = '';
		}

		$links = paginate_links( apply_filters( 'nexvio_grid_pagination_args', array(
			'base'      => $base,
			'format'    => $format,
			'add_args'  => false,
			'current'   => max( 1, $current ),
			'total'     => $total,
			'prev_next' => 'only_num' !== $s['pag_type'],
			'prev_text' => $prev,
			'next_text' => $next,
			'type'      => 'list',
			'end_size'  => max( 1, (int) $s['end_size'] ),
			'mid_size'  => max( 0, (int) $s['mid_size'] ),
		), $s ) );

		if ( $links ) {
			echo wp_kses( '<nav class="woocommerce-pagination nexvio-pg__pagination" aria-label="' . $label . '">' . $links . '</nav>', Nexvio_Plugin::allowed_html() );
		}
	}

	/* ------------------------------------------------------------------
	 * Output
	 * ------------------------------------------------------------------ */

	protected function render() {
		$s       = $this->grid_settings( $this->get_settings_for_display() );
		$is_main = 'current' === $s['source_mode'] && $this->is_product_archive();
		$preview = 'current' === $s['source_mode'] && ! $is_main;

		if ( $is_main ) {
			global $wp_query;
			$products = array_values( array_filter( array_map( 'wc_get_product', (array) $wp_query->posts ) ) );
			$total    = (int) $wp_query->found_posts;
			$pages    = (int) $wp_query->max_num_pages;
			$per_page = (int) $wp_query->get( 'posts_per_page' );
			$current  = max( 1, (int) get_query_var( 'paged' ) );
		} else {
			$param   = $this->page_param();
			$current = isset( $_GET[ $param ] ) ? max( 1, absint( wp_unslash( $_GET[ $param ] ) ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification
			if ( $preview ) {
				$preview_s = array_merge( $s, array( 'source' => 'recent', 'categories' => array(), 'tags' => array(), 'orderby' => 'date', 'order' => 'DESC', 'hide_out' => '', 'exclude_ids' => '' ) );
				$result    = $this->get_products_paged( $preview_s, max( 1, (int) $s['preview_limit'] ), 1 );
			} else {
				$result = $this->get_products_paged( $s, max( 1, (int) $s['limit'] ), $current );
			}
			$products = (array) $result->products;
			$total    = (int) $result->total;
			$pages    = $preview ? 1 : (int) $result->max_num_pages;
			$per_page = $preview ? (int) $s['preview_limit'] : (int) $s['limit'];
		}

		if ( wp_script_is( 'wc-add-to-cart', 'registered' ) ) {
			wp_enqueue_script( 'wc-add-to-cart' );
		}

		$hooks = 'yes' === $s['loop_hooks'] && $is_main;

		if ( function_exists( 'wc_setup_loop' ) ) {
			wc_setup_loop( array(
				'name'         => 'nexvio_product_grid',
				'is_paginated' => true,
				'total'        => $total,
				'total_pages'  => max( 1, $pages ),
				'per_page'     => max( 1, $per_page ),
				'current_page' => $current,
				'columns'      => max( 1, (int) $s['columns'] ),
			) );
		}

		$classes   = array( 'nexvio-ps', 'nexvio-pg' );
		$classes[] = 'cover' === $s['image_mode'] ? 'nexvio-ps--cover' : 'nexvio-ps--contain';
		if ( 'yes' !== $s['pattern'] ) {
			$classes[] = 'nexvio-ps--no-pattern';
		}
		if ( 'yes' === $s['frame'] ) {
			$classes[] = 'nexvio-ps--frame';
		}
		if ( 'yes' === $s['img_shadow'] ) {
			$classes[] = 'nexvio-ps--shadow';
		}
		if ( in_array( $s['hover_fx'], array( 'lift', 'zoom' ), true ) ) {
			$classes[] = 'nexvio-ps--hover-' . $s['hover_fx'];
		}
		if ( $is_main ) {
			$qo             = get_queried_object();
			$s['_ctx_cats'] = ( function_exists( 'is_product_category' ) && is_product_category() && $qo && isset( $qo->slug ) ) ? array( $qo->slug ) : array();
		} else {
			$s['_ctx_cats'] = ( ! $preview && 'manual' !== $s['source'] && ! empty( $s['categories'] ) ) ? (array) $s['categories'] : array();
		}
		$layout_classes = $this->layout_classes( $s );
		if ( in_array( 'nexvio-ps--l', $layout_classes, true ) ) {
			/* classic-only looks (gold frame, hexagon pattern, tilt) do not apply to the new designs */
			$classes = array_values( array_diff( $classes, array( 'nexvio-ps--contain', 'nexvio-ps--cover', 'nexvio-ps--no-pattern', 'nexvio-ps--frame', 'nexvio-ps--shadow', 'nexvio-ps--hover-lift', 'nexvio-ps--hover-zoom' ) ) );
		}
		$classes = array_merge( $classes, $layout_classes, $this->extra_root_classes() );

		/* view mode (Products Archive only; the Products Grid widget has no view setting) */
		$view        = isset( $s['view_mode'] ) && 'list' === $s['view_mode'] ? 'list' : 'grid';
		$view_switch = isset( $s['view_switch'] ) && 'yes' === $s['view_switch'];
		if ( 'list' === $view ) {
			$classes[] = 'nexvio-pg--list';
		}
		if ( isset( $s['view_mode'] ) ) {
			$classes[] = 'nexvio-pg--actions-' . ( isset( $s['list_actions'] ) && 'below' === $s['list_actions'] ? 'below' : 'right' );
			if ( isset( $s['list_stack'] ) && 'yes' === $s['list_stack'] ) {
				$classes[] = 'nexvio-pg--stack';
			}
		}

		$rules = is_array( $s['color_rules'] ) ? $s['color_rules'] : array();
		$js    = array( 'wish' => $this->wish_settings( $s ) );
		?>
		<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-settings="<?php echo esc_attr( wp_json_encode( $js ) ); ?>" data-view="<?php echo esc_attr( $view ); ?>"<?php echo $view_switch ? ' data-view-switch="1" data-view-remember="' . ( isset( $s['view_remember'] ) && 'yes' === $s['view_remember'] ? '1' : '0' ) . '"' : ''; ?>>
			<?php
			if ( $preview && $this->is_edit_mode() ) {
				echo '<p class="nexvio-pg__preview-note">' . esc_html__( 'Editor preview: recent products. On the live shop / archive page this grid shows the products of the current page, with filters and pagination.', 'nexvio' ) . '</p>';
			}

			/* toolbar + "before loop" hooks (notices, filter plugins) */
			if ( $hooks ) {
				$removed_count = remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
				$removed_order = remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
				do_action( 'woocommerce_before_shop_loop' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core hook, called on purpose for compatibility.
				if ( $removed_count ) {
					add_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
				}
				if ( $removed_order ) {
					add_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
				}
			}

			$show_count = 'yes' === $s['show_count'] && $is_main && $products;
			$show_sort  = 'yes' === $s['show_sorting'] && $is_main && $products;
			if ( $show_count || $show_sort || ( $view_switch && $products ) ) {
				echo '<div class="nexvio-pg__toolbar">';
				if ( $show_count && function_exists( 'woocommerce_result_count' ) ) {
					woocommerce_result_count();
				} else {
					echo '<span></span>';
				}
				echo '<div class="nexvio-pg__tools">';
				if ( $view_switch && $products ) {
					$this->render_view_switch( $s, $view );
				}
				if ( $show_sort && function_exists( 'woocommerce_catalog_ordering' ) ) {
					woocommerce_catalog_ordering();
				}
				echo '</div></div>';
			}

			$cols  = max( 1, (int) $s['columns'] );
			$open  = '<ul class="products nexvio-pg__grid columns-' . $cols . '">';
			$close = '</ul>';
			if ( $hooks ) {
				$open  = apply_filters( 'woocommerce_product_loop_start', $open ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core hook, called on purpose for compatibility.
				$close = apply_filters( 'woocommerce_product_loop_end', $close ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core hook, called on purpose for compatibility.
			}

			if ( $products ) {
				echo wp_kses( $open, Nexvio_Plugin::allowed_html() );
				foreach ( $products as $product ) {
					if ( $product instanceof WC_Product ) {
						$this->render_card( $product, $s, $rules, 'li' );
					}
				}
				echo wp_kses( $close, Nexvio_Plugin::allowed_html() );

				$this->render_pagination( $s, $current, $pages, $is_main );

				if ( $hooks ) {
					$removed_pag = remove_action( 'woocommerce_after_shop_loop', 'woocommerce_pagination', 10 );
					do_action( 'woocommerce_after_shop_loop' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core hook, called on purpose for compatibility.
					if ( $removed_pag ) {
						add_action( 'woocommerce_after_shop_loop', 'woocommerce_pagination', 10 );
					}
				}
			} else {
				/* empty list kept so AJAX filter plugins still find the container */
				echo '<ul class="products nexvio-pg__grid columns-' . esc_attr( $cols ) . '"></ul>';
				echo '<div class="woocommerce-no-products-found"><p class="woocommerce-info nexvio-pg__empty">' . esc_html( $s['empty_text'] ) . '</p></div>';
			}
			?>
		</div>
		<?php
		if ( function_exists( 'wc_reset_loop' ) ) {
			wc_reset_loop();
		}
	}
}
