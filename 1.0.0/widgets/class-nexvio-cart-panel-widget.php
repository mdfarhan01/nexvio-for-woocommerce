<?php
/**
 * Cart Panel - Elementor widget: the whole cart shown on a page (same content and live
 * updates as the side cart), with full control over what is shown and how it looks.
 *
 * @package Nexvio_For_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

class Nexvio_Cart_Panel_Widget extends Widget_Base {

	use Nexvio_Card_Trait;

	public function get_name() {
		return 'nexvio_cart_panel';
	}

	public function get_title() {
		return esc_html__( 'Cart Panel', 'nexvio' );
	}

	public function get_icon() {
		return 'eicon-cart';
	}

	public function get_categories() {
		return array( 'nexvio' );
	}

	public function get_keywords() {
		return array( 'nexvio', 'cart', 'bag', 'basket', 'page', 'inline', 'mini cart', 'checkout' );
	}

	public function get_style_depends() {
		return array( 'nexvio-side-cart' );
	}

	public function get_script_depends() {
		return array( 'nexvio-side-cart' );
	}

	/* ------------------------------------------------------------------
	 * Small helpers
	 * ------------------------------------------------------------------ */

	private function sel( $part ) {
		return '{{WRAPPER}} .nexvio-icart' . ( $part ? ' ' . $part : '' );
	}

	/** Colour control that sets one or more CSS declarations on a selector. */
	private function col( $id, $label, $selector, $css, $extra = array() ) {
		$this->add_control( $id, array_merge( array(
			'label'     => $label,
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( $this->sel( $selector ) => $css ),
		), $extra ) );
	}

	private function typo( $name, $label, $selector ) {
		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => $name,
			'label'    => $label,
			'selector' => $this->sel( $selector ),
		) );
	}

	private function dims( $id, $label, $selector, $prop ) {
		$this->add_responsive_control( $id, array(
			'label'      => $label,
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em', '%' ),
			'selectors'  => array( $this->sel( $selector ) => $prop . ': {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );
	}

	private function slider( $id, $label, $selector, $css, $min, $max, $responsive = true, $unit = 'px' ) {
		$args = array(
			'label'      => $label,
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( $unit ),
			'range'      => array( $unit => array( 'min' => $min, 'max' => $max ) ),
			'selectors'  => array( $this->sel( $selector ) => $css ),
		);
		if ( $responsive ) {
			$this->add_responsive_control( $id, $args );
		} else {
			$this->add_control( $id, $args );
		}
	}

	/* ------------------------------------------------------------------
	 * Controls
	 * ------------------------------------------------------------------ */

	protected function register_controls() {
		/* ---------------- content ---------------- */
		$this->start_controls_section( 'section_content', array( 'label' => esc_html__( 'Cart', 'nexvio' ) ) );
		$this->add_control( 'note', array(
			'type'            => Controls_Manager::RAW_HTML,
			'raw'             => esc_html__( 'Shows the visitor\'s real cart, updating live. Texts (title, buttons, empty message) come from Nexvio > Add to Cart.', 'nexvio' ),
			'content_classes' => 'elementor-descriptor',
		) );
		$this->add_control( 'preset', array(
			'label'   => esc_html__( 'Style', 'nexvio' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'global',
			'options' => array(
				'global' => esc_html__( 'Dashboard setting', 'nexvio' ),
				'light'  => esc_html__( 'Light', 'nexvio' ),
				'dark'   => esc_html__( 'Dark', 'nexvio' ),
			),
		) );
		$this->add_control( 'h_show', array( 'label' => esc_html__( 'What to show', 'nexvio' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->switcher( 'show_head', esc_html__( 'Title and counter', 'nexvio' ) );
		$this->switcher( 'show_sub', esc_html__( 'Subtitle', 'nexvio' ), 'yes', array( 'condition' => array( 'show_head' => 'yes' ) ) );
		$this->switcher( 'show_thumb', esc_html__( 'Product images', 'nexvio' ) );
		$this->switcher( 'show_var', esc_html__( 'Product options (size, colour…)', 'nexvio' ) );
		$this->switcher( 'show_unit', esc_html__( 'Price of one item', 'nexvio' ) );
		$this->switcher( 'show_qty', esc_html__( 'Quantity buttons', 'nexvio' ) );
		$this->switcher( 'show_remove', esc_html__( 'Remove button', 'nexvio' ) );
		$this->switcher( 'show_coupon', esc_html__( 'Coupon field', 'nexvio' ), 'yes', array( 'description' => esc_html__( 'Needs the coupon field to be on in Nexvio > Add to Cart.', 'nexvio' ) ) );
		$this->switcher( 'show_checkout', esc_html__( 'Checkout button', 'nexvio' ) );
		$this->switcher( 'show_more', esc_html__( 'Shop more button', 'nexvio' ), 'yes', array( 'description' => esc_html__( 'Needs the "Shop more" button to be on in Nexvio > Add to Cart.', 'nexvio' ) ) );

		$this->add_control( 'h_layout', array( 'label' => esc_html__( 'Layout', 'nexvio' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_responsive_control( 'title_lines', array(
			'label'     => esc_html__( 'Product title length', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => '',
			'options'   => array(
				''  => esc_html__( 'Dashboard setting', 'nexvio' ),
				'1' => esc_html__( '1 line', 'nexvio' ),
				'2' => esc_html__( '2 lines', 'nexvio' ),
				'3' => esc_html__( '3 lines', 'nexvio' ),
			),
			'selectors' => array( $this->sel( '' ) => '--wsc-lines: {{VALUE}};' ),
		) );
		$this->slider( 'max_h', esc_html__( 'Product list: maximum height', 'nexvio' ), '.nexvio-sc__body', 'max-height: {{SIZE}}{{UNIT}}; overflow-y: auto;', 120, 1200 );
		$this->slider( 'max_w', esc_html__( 'Maximum width', 'nexvio' ), '', 'max-width: {{SIZE}}{{UNIT}};', 280, 1400 );
		$this->add_responsive_control( 'align', array(
			'label'              => esc_html__( 'Alignment', 'nexvio' ),
			'type'               => Controls_Manager::CHOOSE,
			'options'            => array(
				'left'   => array( 'title' => esc_html__( 'Left', 'nexvio' ), 'icon' => 'eicon-h-align-left' ),
				'center' => array( 'title' => esc_html__( 'Center', 'nexvio' ), 'icon' => 'eicon-h-align-center' ),
				'right'  => array( 'title' => esc_html__( 'Right', 'nexvio' ), 'icon' => 'eicon-h-align-right' ),
			),
			'selectors_dictionary' => array(
				'left'   => 'margin-left: 0; margin-right: auto;',
				'center' => 'margin-left: auto; margin-right: auto;',
				'right'  => 'margin-left: auto; margin-right: 0;',
			),
			'selectors'          => array( $this->sel( '' ) => '{{VALUE}}' ),
		) );
		$this->end_controls_section();

		/* ---------------- style: box ---------------- */
		$this->start_controls_section( 'style_box', array( 'label' => esc_html__( 'Box', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->col( 'box_bg', esc_html__( 'Background', 'nexvio' ), '', '--bg: {{VALUE}}; background: {{VALUE}};' );
		$this->col( 'accent', esc_html__( 'Accent colour', 'nexvio' ), '', '--mc: {{VALUE}};', array( 'description' => esc_html__( 'Counter, buttons and the sale price follow it. Empty = main colour from the dashboard.', 'nexvio' ) ) );
		$this->col( 'accent_text', esc_html__( 'Text on the accent colour', 'nexvio' ), '', '--mct: {{VALUE}};' );
		$this->col( 'line', esc_html__( 'Divider lines', 'nexvio' ), '', '--ln: {{VALUE}};' );
		$this->add_group_control( Group_Control_Border::get_type(), array( 'name' => 'box_border', 'selector' => $this->sel( '' ) ) );
		$this->add_responsive_control( 'box_radius', array(
			'label'      => esc_html__( 'Corner radius', 'nexvio' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', '%' ),
			'selectors'  => array( $this->sel( '' ) => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );
		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array( 'name' => 'box_shadow', 'selector' => $this->sel( '' ) ) );
		$this->end_controls_section();

		/* ---------------- style: header ---------------- */
		$this->start_controls_section( 'style_head', array( 'label' => esc_html__( 'Title', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE, 'condition' => array( 'show_head' => 'yes' ) ) );
		$this->typo( 'title_typo', esc_html__( 'Title typography', 'nexvio' ), '.nexvio-sc__title' );
		$this->col( 'title_c', esc_html__( 'Title colour', 'nexvio' ), '.nexvio-sc__title', 'color: {{VALUE}};' );
		$this->typo( 'sub_typo', esc_html__( 'Subtitle typography', 'nexvio' ), '.nexvio-sc__sub' );
		$this->col( 'sub_c', esc_html__( 'Subtitle colour', 'nexvio' ), '.nexvio-sc__sub', 'color: {{VALUE}};' );
		$this->col( 'badge_bg', esc_html__( 'Counter background', 'nexvio' ), '.nexvio-sc__badge', 'background: {{VALUE}};' );
		$this->col( 'badge_c', esc_html__( 'Counter number colour', 'nexvio' ), '.nexvio-sc__badge', 'color: {{VALUE}};' );
		$this->dims( 'head_pad', esc_html__( 'Padding', 'nexvio' ), '.nexvio-sc__head', 'padding' );
		$this->end_controls_section();

		/* ---------------- style: products ---------------- */
		$this->start_controls_section( 'style_items', array( 'label' => esc_html__( 'Products', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->slider( 'img_w', esc_html__( 'Image width', 'nexvio' ), '', '--wsc-img: {{SIZE}}{{UNIT}};', 40, 220 );
		$this->add_responsive_control( 'img_ratio', array(
			'label'     => esc_html__( 'Image shape', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => '',
			'options'   => array(
				''    => esc_html__( 'Landscape (default)', 'nexvio' ),
				'1/1' => esc_html__( 'Square', 'nexvio' ),
				'4/5' => esc_html__( 'Portrait', 'nexvio' ),
				'3/4' => esc_html__( 'Tall', 'nexvio' ),
			),
			'selectors' => array( $this->sel( '.nexvio-sc__thumb' ) => 'aspect-ratio: {{VALUE}};' ),
		) );
		$this->add_responsive_control( 'img_radius', array(
			'label'      => esc_html__( 'Image corner radius', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
			'selectors'  => array( $this->sel( '.nexvio-sc__thumb' ) => 'border-radius: {{SIZE}}{{UNIT}};' ),
		) );
		$this->slider( 'row_pad', esc_html__( 'Space above and below each product', 'nexvio' ), '.nexvio-sc__item', 'padding-top: {{SIZE}}{{UNIT}}; padding-bottom: {{SIZE}}{{UNIT}};', 0, 60 );
		$this->slider( 'row_gap', esc_html__( 'Space between image and details', 'nexvio' ), '.nexvio-sc__item', 'column-gap: {{SIZE}}{{UNIT}};', 0, 60 );
		$this->dims( 'body_pad', esc_html__( 'List padding', 'nexvio' ), '.nexvio-sc__body', 'padding' );

		$this->add_control( 'h_name', array( 'label' => esc_html__( 'Product name', 'nexvio' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->typo( 'name_typo', esc_html__( 'Typography', 'nexvio' ), '.nexvio-sc__name' );
		$this->col( 'name_c', esc_html__( 'Colour', 'nexvio' ), '.nexvio-sc__name', 'color: {{VALUE}};' );
		$this->col( 'name_ch', esc_html__( 'Colour on hover', 'nexvio' ), 'a.nexvio-sc__name:hover', 'color: {{VALUE}};' );

		$this->add_control( 'h_price', array( 'label' => esc_html__( 'Prices', 'nexvio' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->typo( 'line_typo', esc_html__( 'Line total typography', 'nexvio' ), '.nexvio-sc__line' );
		$this->col( 'line_c', esc_html__( 'Line total colour', 'nexvio' ), '.nexvio-sc__line', 'color: {{VALUE}};' );
		$this->typo( 'unit_typo', esc_html__( 'Price of one item: typography', 'nexvio' ), '.nexvio-sc__unit' );
		$this->col( 'reg_c', esc_html__( 'Regular price colour (struck through)', 'nexvio' ), '.nexvio-sc__unit del', 'color: {{VALUE}}; opacity: 1;' );
		$this->typo( 'reg_typo', esc_html__( 'Regular price typography', 'nexvio' ), '.nexvio-sc__unit del' );
		$this->col( 'sale_c', esc_html__( 'Sale price colour', 'nexvio' ), '.nexvio-sc__sale', 'color: {{VALUE}};' );
		$this->typo( 'sale_typo', esc_html__( 'Sale price typography', 'nexvio' ), '.nexvio-sc__sale' );

		$this->add_control( 'h_var', array( 'label' => esc_html__( 'Product options', 'nexvio' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before', 'condition' => array( 'show_var' => 'yes' ) ) );
		$this->typo( 'var_typo', esc_html__( 'Typography', 'nexvio' ), '.nexvio-sc__var' );
		$this->col( 'var_c', esc_html__( 'Colour', 'nexvio' ), '.nexvio-sc__meta', 'color: {{VALUE}};', array( 'condition' => array( 'show_var' => 'yes' ) ) );
		$this->end_controls_section();

		/* ---------------- style: quantity + remove ---------------- */
		$this->start_controls_section( 'style_qty', array( 'label' => esc_html__( 'Quantity and remove', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->col( 'qty_bg', esc_html__( 'Quantity background', 'nexvio' ), '.nexvio-sc__qty', 'background: {{VALUE}};' );
		$this->col( 'qty_bd', esc_html__( 'Quantity border', 'nexvio' ), '.nexvio-sc__qty', 'border-color: {{VALUE}};' );
		$this->col( 'qty_c', esc_html__( 'Quantity text colour', 'nexvio' ), '.nexvio-sc__qty button, {{WRAPPER}} .nexvio-icart .nexvio-sc__qty input', 'color: {{VALUE}};' );
		$this->col( 'qty_hbg', esc_html__( 'Quantity button on hover', 'nexvio' ), '.nexvio-sc__qty button:hover:not(:disabled)', 'background: {{VALUE}};' );
		$this->col( 'rm_c', esc_html__( 'Remove icon colour', 'nexvio' ), '.nexvio-sc__remove', 'color: {{VALUE}};' );
		$this->col( 'rm_ch', esc_html__( 'Remove icon on hover', 'nexvio' ), '.nexvio-sc__remove:hover', 'color: {{VALUE}};' );
		$this->end_controls_section();

		/* ---------------- style: totals ---------------- */
		$this->start_controls_section( 'style_totals', array( 'label' => esc_html__( 'Totals and coupon', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->dims( 'foot_pad', esc_html__( 'Padding', 'nexvio' ), '.nexvio-sc__foot', 'padding' );
		$this->typo( 'row_typo', esc_html__( 'Labels typography', 'nexvio' ), '.nexvio-sc__row dt' );
		$this->col( 'row_c', esc_html__( 'Labels colour', 'nexvio' ), '.nexvio-sc__row dt', 'color: {{VALUE}};' );
		$this->typo( 'val_typo', esc_html__( 'Values typography', 'nexvio' ), '.nexvio-sc__row dd' );
		$this->col( 'val_c', esc_html__( 'Values colour', 'nexvio' ), '.nexvio-sc__row dd', 'color: {{VALUE}};' );
		$this->add_control( 'h_total', array( 'label' => esc_html__( 'Total', 'nexvio' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->typo( 'tl_typo', esc_html__( 'Total label typography', 'nexvio' ), '.nexvio-sc__row--total dt' );
		$this->col( 'tl_c', esc_html__( 'Total label colour', 'nexvio' ), '.nexvio-sc__row--total dt', 'color: {{VALUE}};' );
		$this->typo( 'tv_typo', esc_html__( 'Total amount typography', 'nexvio' ), '.nexvio-sc__row--total dd' );
		$this->col( 'tv_c', esc_html__( 'Total amount colour', 'nexvio' ), '.nexvio-sc__row--total dd', 'color: {{VALUE}};' );
		$this->add_control( 'h_coupon', array( 'label' => esc_html__( 'Coupon', 'nexvio' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before', 'condition' => array( 'show_coupon' => 'yes' ) ) );
		$this->col( 'cp_c', esc_html__( 'Link colour', 'nexvio' ), '.nexvio-sc__coupon summary', 'color: {{VALUE}};', array( 'condition' => array( 'show_coupon' => 'yes' ) ) );
		$this->col( 'cp_bd', esc_html__( 'Field border', 'nexvio' ), '.nexvio-sc__coupon-form input', 'border-color: {{VALUE}};', array( 'condition' => array( 'show_coupon' => 'yes' ) ) );
		$this->col( 'cp_btn', esc_html__( 'Apply button', 'nexvio' ), '.nexvio-sc__coupon-form button', 'background: {{VALUE}}; border-color: {{VALUE}};', array( 'condition' => array( 'show_coupon' => 'yes' ) ) );
		$this->col( 'cp_btn_c', esc_html__( 'Apply button text', 'nexvio' ), '.nexvio-sc__coupon-form button', 'color: {{VALUE}};', array( 'condition' => array( 'show_coupon' => 'yes' ) ) );
		$this->end_controls_section();

		/* ---------------- style: buttons ---------------- */
		$this->start_controls_section( 'style_btn', array( 'label' => esc_html__( 'Buttons', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_control( 'h_checkout', array( 'label' => esc_html__( 'Checkout button', 'nexvio' ), 'type' => Controls_Manager::HEADING, 'condition' => array( 'show_checkout' => 'yes' ) ) );
		$this->typo( 'co_typo', esc_html__( 'Typography', 'nexvio' ), '.nexvio-sc__actions .nexvio-sc__btn--primary' );
		$this->col( 'co_c', esc_html__( 'Text colour', 'nexvio' ), '.nexvio-sc__actions .nexvio-sc__btn--primary', 'color: {{VALUE}};', array( 'condition' => array( 'show_checkout' => 'yes' ) ) );
		$this->col( 'co_bg', esc_html__( 'Background', 'nexvio' ), '.nexvio-sc__actions .nexvio-sc__btn--primary', 'background: {{VALUE}};', array( 'condition' => array( 'show_checkout' => 'yes' ) ) );
		$this->col( 'co_ch', esc_html__( 'Text colour on hover', 'nexvio' ), '.nexvio-sc__actions .nexvio-sc__btn--primary:hover', 'color: {{VALUE}};', array( 'condition' => array( 'show_checkout' => 'yes' ) ) );
		$this->col( 'co_bgh', esc_html__( 'Background on hover', 'nexvio' ), '.nexvio-sc__actions .nexvio-sc__btn--primary:hover', 'background: {{VALUE}};', array( 'condition' => array( 'show_checkout' => 'yes' ) ) );
		$this->add_control( 'h_more', array( 'label' => esc_html__( 'Shop more button', 'nexvio' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before', 'condition' => array( 'show_more' => 'yes' ) ) );
		$this->typo( 'mo_typo', esc_html__( 'Typography', 'nexvio' ), '.nexvio-sc__btn--ghost' );
		$this->col( 'mo_c', esc_html__( 'Text colour', 'nexvio' ), '.nexvio-sc__btn--ghost', 'color: {{VALUE}};', array( 'condition' => array( 'show_more' => 'yes' ) ) );
		$this->col( 'mo_bg', esc_html__( 'Background', 'nexvio' ), '.nexvio-sc__btn--ghost', 'background: {{VALUE}};', array( 'condition' => array( 'show_more' => 'yes' ) ) );
		$this->col( 'mo_bd', esc_html__( 'Border colour', 'nexvio' ), '.nexvio-sc__btn--ghost', 'border-color: {{VALUE}};', array( 'condition' => array( 'show_more' => 'yes' ) ) );
		$this->col( 'mo_ch', esc_html__( 'Text colour on hover', 'nexvio' ), '.nexvio-sc__btn--ghost:hover', 'color: {{VALUE}};', array( 'condition' => array( 'show_more' => 'yes' ) ) );
		$this->col( 'mo_bgh', esc_html__( 'Background on hover', 'nexvio' ), '.nexvio-sc__btn--ghost:hover', 'background: {{VALUE}};', array( 'condition' => array( 'show_more' => 'yes' ) ) );
		$this->col( 'mo_bdh', esc_html__( 'Border colour on hover', 'nexvio' ), '.nexvio-sc__btn--ghost:hover', 'border-color: {{VALUE}};', array( 'condition' => array( 'show_more' => 'yes' ) ) );
		$this->add_control( 'h_btn_all', array( 'label' => esc_html__( 'Both buttons', 'nexvio' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->slider( 'btn_h', esc_html__( 'Height', 'nexvio' ), '.nexvio-sc__actions .nexvio-sc__btn', 'min-height: {{SIZE}}{{UNIT}};', 36, 90 );
		$this->slider( 'btn_r', esc_html__( 'Corner radius', 'nexvio' ), '.nexvio-sc__actions .nexvio-sc__btn', 'border-radius: {{SIZE}}{{UNIT}};', 0, 60 );
		$this->slider( 'btn_gap', esc_html__( 'Space between the buttons', 'nexvio' ), '.nexvio-sc__actions', 'gap: {{SIZE}}{{UNIT}};', 0, 40 );
		$this->end_controls_section();

		/* ---------------- style: empty cart ---------------- */
		$this->start_controls_section( 'style_empty', array( 'label' => esc_html__( 'Empty cart', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->typo( 'em_title_typo', esc_html__( 'Title typography', 'nexvio' ), '.nexvio-sc__empty-title' );
		$this->col( 'em_title_c', esc_html__( 'Title colour', 'nexvio' ), '.nexvio-sc__empty-title', 'color: {{VALUE}};' );
		$this->typo( 'em_text_typo', esc_html__( 'Message typography', 'nexvio' ), '.nexvio-sc__empty-text' );
		$this->col( 'em_text_c', esc_html__( 'Message colour', 'nexvio' ), '.nexvio-sc__empty-text', 'color: {{VALUE}};' );
		$this->col( 'em_icon_c', esc_html__( 'Icon colour', 'nexvio' ), '.nexvio-sc__empty-icon', 'color: {{VALUE}};' );
		$this->col( 'em_icon_bg', esc_html__( 'Icon background', 'nexvio' ), '.nexvio-sc__empty-icon', 'background: {{VALUE}};' );
		$this->end_controls_section();
	}

	/* ------------------------------------------------------------------
	 * Output
	 * ------------------------------------------------------------------ */

	protected function render() {
		$s = $this->get_settings_for_display();

		$classes = array();
		foreach ( array(
			'show_head'     => 'head',
			'show_sub'      => 'sub',
			'show_thumb'    => 'thumb',
			'show_var'      => 'var',
			'show_unit'     => 'unit',
			'show_qty'      => 'qty',
			'show_remove'   => 'remove',
			'show_coupon'   => 'coupon',
			'show_checkout' => 'checkout',
			'show_more'     => 'more',
		) as $control => $slug ) {
			if ( 'yes' !== $s[ $control ] ) {
				$classes[] = 'nexvio-icart--no-' . $slug;
			}
		}

		$html = Nexvio_Side_Cart::inline_html( $s['preset'], implode( ' ', $classes ) );
		if ( '' === $html ) {
			echo '<p class="nexvio-icart-note">' . esc_html__( 'The cart is not available here (is WooCommerce active?).', 'nexvio' ) . '</p>';
			return;
		}
		echo wp_kses( $html, Nexvio_Plugin::allowed_html() );
	}
}
