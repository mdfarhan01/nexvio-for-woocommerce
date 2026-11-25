<?php
/**
 * NEXVIO Product Variations - Elementor widget for the single product page.
 *
 * Only the options of a variable product (Color, Size…): the type of every option group
 * can be chosen (dropdown, buttons, colour / image swatches, checkbox style), with a clear
 * link and the stock line. It does not add anything to the cart: the Add to Cart widget
 * (placed anywhere on the same page) receives the chosen variation through the browser event
 * "nexvio:variation" and adds exactly that variation.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

class Nexvio_Product_Variations_Widget extends Widget_Base {

	public function get_name() {
		return 'nexvio_product_variations';
	}

	public function get_title() {
		return esc_html__( 'Product Variations', 'nexvio' );
	}

	public function get_icon() {
		return 'eicon-product-variations';
	}

	public function get_categories() {
		return array( 'nexvio' );
	}

	public function get_keywords() {
		return array( 'nexvio', 'woocommerce', 'variation', 'variations', 'options', 'color', 'size', 'swatch', 'attribute', 'single product' );
	}

	public function get_style_depends() {
		return array( 'nexvio-layout', 'nexvio-product-variations' );
	}

	public function get_script_depends() {
		return array( 'nexvio-product-variations' );
	}

	/** Never cached: the options depend on the product being viewed. */
	protected function is_dynamic_content(): bool {
		return true;
	}

	/* ------------------------------------------------------------------
	 * Helpers for the controls
	 * ------------------------------------------------------------------ */

	private function types( $with_auto = false ) {
		$t = array(
			'dropdown' => esc_html__( 'Dropdown', 'nexvio' ),
			'buttons'  => esc_html__( 'Buttons', 'nexvio' ),
			'swatches' => esc_html__( 'Colour / image swatches (text buttons when there is no colour)', 'nexvio' ),
			'checkbox' => esc_html__( 'Checkbox style (tick box + name)', 'nexvio' ),
		);
		if ( $with_auto ) {
			$t['auto'] = esc_html__( 'Automatic (swatches for colours and images, buttons for the rest)', 'nexvio' );
		}
		return $t;
	}

	/** Control id part of an attribute: "pa_color" or "color" -> "color". */
	private static function attr_key( $name ) {
		$name = preg_replace( '/^pa_/', '', strtolower( (string) $name ) );
		return str_replace( '-', '_', sanitize_title( $name ) );
	}

	/** Colour control that sets one CSS variable on the widget. */
	private function vcol( $id, $label, $var, $condition = array() ) {
		$args = array(
			'label'     => $label,
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .nexvio-var' => $var . ': {{VALUE}};' ),
		);
		if ( $condition ) {
			$args['condition'] = $condition;
		}
		$this->add_control( $id, $args );
	}

	/** Slider control that sets one CSS variable on the widget. */
	private function vsld( $id, $label, $var, $min, $max, $responsive = false, $units = array( 'px' ), $condition = array() ) {
		$args = array(
			'label'      => $label,
			'type'       => Controls_Manager::SLIDER,
			'size_units' => $units,
			'range'      => array(
				'px' => array( 'min' => $min, 'max' => $max ),
				'%'  => array( 'min' => 10, 'max' => 100 ),
			),
			'selectors'  => array( '{{WRAPPER}} .nexvio-var' => $var . ': {{SIZE}}{{UNIT}};' ),
		);
		if ( $condition ) {
			$args['condition'] = $condition;
		}
		if ( $responsive ) {
			$this->add_responsive_control( $id, $args );
		} else {
			$this->add_control( $id, $args );
		}
	}

	private function vheading( $id, $label ) {
		$this->add_control( $id, array(
			'label'     => $label,
			'type'      => Controls_Manager::HEADING,
			'separator' => 'before',
		) );
	}

	private function vtext( $id, $label, $default, $condition = array() ) {
		$args = array(
			'label'   => $label,
			'type'    => Controls_Manager::TEXT,
			'default' => $default,
		);
		if ( $condition ) {
			$args['condition'] = $condition;
		}
		$this->add_control( $id, $args );
	}

	private function vswitch( $id, $label, $default = 'yes', $extra = array() ) {
		$this->add_control( $id, array_merge( array(
			'label'        => $label,
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => $default,
		), $extra ) );
	}

	/* ------------------------------------------------------------------
	 * Controls
	 * ------------------------------------------------------------------ */

	protected function register_controls() {

		/* ============ PRODUCT ============ */
		$this->start_controls_section( 'section_product', array( 'label' => esc_html__( 'Product', 'nexvio' ) ) );
		$this->add_control( 'product_note', array(
			'type'            => Controls_Manager::RAW_HTML,
			'raw'             => esc_html__( 'Place this widget in the single product template, together with the Add to Cart widget. The customer picks the options here, the Add to Cart widget adds the chosen variation.', 'nexvio' ),
			'content_classes' => 'elementor-descriptor',
		) );
		$this->add_control( 'product_id', array(
			'label'       => esc_html__( 'Fixed product ID (optional)', 'nexvio' ),
			'type'        => Controls_Manager::NUMBER,
			'min'         => 0,
			'default'     => '',
			'description' => esc_html__( 'Leave empty to use the current product. In the editor, empty = latest variable product. The Add to Cart widget must use the same product.', 'nexvio' ),
		) );
		$this->end_controls_section();

		/* ============ OPTIONS ============ */
		$this->start_controls_section( 'section_options', array( 'label' => esc_html__( 'Options', 'nexvio' ) ) );
		$this->add_control( 'var_type', array(
			'label'       => esc_html__( 'Selector type (all attributes)', 'nexvio' ),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'auto',
			'options'     => $this->types( true ),
			'description' => esc_html__( 'Used for every attribute that has no type of its own below.', 'nexvio' ),
		) );

		/* one type control for every attribute of the shop (Products > Attributes) */
		$attr_types = array( 'default' => esc_html__( 'Same as above', 'nexvio' ) ) + $this->types( true );
		$tax_list   = function_exists( 'wc_get_attribute_taxonomies' ) ? wc_get_attribute_taxonomies() : array();
		if ( $tax_list ) {
			$this->add_control( 'h_attr_types', array(
				'label'     => esc_html__( 'Type of each attribute', 'nexvio' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			) );
			foreach ( $tax_list as $tax ) {
				$this->add_control( 'attr_type_' . self::attr_key( $tax->attribute_name ), array(
					'label'   => $tax->attribute_label ? $tax->attribute_label : $tax->attribute_name,
					'type'    => Controls_Manager::SELECT,
					'default' => 'default',
					'options' => $attr_types,
				) );
			}
		}

		$rep = new \Elementor\Repeater();
		$rep->add_control( 'attr', array(
			'label'       => esc_html__( 'Attribute name', 'nexvio' ),
			'type'        => Controls_Manager::TEXT,
			'default'     => '',
			'placeholder' => 'Material',
			'description' => esc_html__( 'The name of a custom attribute typed on one product (not in Products > Attributes).', 'nexvio' ),
		) );
		$rep->add_control( 'type', array(
			'label'   => esc_html__( 'Type', 'nexvio' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'buttons',
			'options' => $this->types( true ),
		) );
		$this->add_control( 'var_overrides', array(
			'label'       => esc_html__( 'Custom attributes of a single product', 'nexvio' ),
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $rep->get_controls(),
			'title_field' => '{{{ attr }}} → {{{ type }}}',
			'default'     => array(),
			'separator'   => 'before',
		) );

		$this->add_control( 'var_preselect', array(
			'label'     => esc_html__( 'Selected when the page opens', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'default',
			'options'   => array(
				'default' => esc_html__( 'The product\'s default choice (nothing if none is set)', 'nexvio' ),
				'first'   => esc_html__( 'First available variation', 'nexvio' ),
				'none'    => esc_html__( 'Nothing: the customer must choose', 'nexvio' ),
			),
			'separator' => 'before',
		) );
		$this->vswitch( 'var_label_show', esc_html__( 'Show the attribute name (Color, Size…)', 'nexvio' ) );
		$this->add_control( 'var_label_pos', array(
			'label'     => esc_html__( 'Name position', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'above',
			'options'   => array(
				'above'  => esc_html__( 'Above the options', 'nexvio' ),
				'inline' => esc_html__( 'Beside the options', 'nexvio' ),
			),
			'condition' => array( 'var_label_show' => 'yes' ),
		) );
		$this->vtext( 'txt_choose', esc_html__( 'Dropdown first line', 'nexvio' ), 'Choose an option', array( 'var_type' => array( 'dropdown', 'auto' ) ) );
		$this->add_control( 'var_unavail', array(
			'label'   => esc_html__( 'Options that are not available', 'nexvio' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'dim',
			'options' => array(
				'dim'  => esc_html__( 'Dim and cross out', 'nexvio' ),
				'hide' => esc_html__( 'Hide them', 'nexvio' ),
			),
		) );
		$this->vswitch( 'img_swap', esc_html__( 'Change the product image when an option is chosen', 'nexvio' ), 'yes', array(
			'description' => esc_html__( 'Works with the Product Images widget. Uses the image set on the variation (Products → Variations → image).', 'nexvio' ),
		) );
		$this->end_controls_section();

		/* ============ EXTRA INFO ============ */
		$this->start_controls_section( 'section_extra', array( 'label' => esc_html__( 'Clear link, stock and price', 'nexvio' ) ) );
		$this->vswitch( 'show_clear', esc_html__( 'Clear link', 'nexvio' ) );
		$this->vtext( 'txt_clear', esc_html__( 'Clear text', 'nexvio' ), 'Clear', array( 'show_clear' => 'yes' ) );
		$this->vswitch( 'show_avail', esc_html__( 'Stock line (Availability)', 'nexvio' ), 'yes', array( 'separator' => 'before' ) );
		$ac = array( 'show_avail' => 'yes' );
		$this->vtext( 'txt_avail', esc_html__( 'Availability label', 'nexvio' ), 'Availability:', $ac );
		$this->vtext( 'txt_in', esc_html__( 'In stock text (when WooCommerce gives none)', 'nexvio' ), 'In stock', $ac );
		$this->vtext( 'txt_out', esc_html__( 'Out of stock text', 'nexvio' ), 'Out of stock', $ac );
		$this->vtext( 'txt_na', esc_html__( 'Combination not available', 'nexvio' ), 'This combination is not available', $ac );
		$this->vswitch( 'show_price', esc_html__( 'Price of the chosen variation', 'nexvio' ), 'yes', array( 'separator' => 'before', 'description' => esc_html__( 'Shown below the options and changes with the chosen variation.', 'nexvio' ) ) );
		$this->add_control( 'price_before', array(
			'label'     => esc_html__( 'Price before every option is chosen', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'range',
			'options'   => array(
				'range' => esc_html__( 'Show the price (range of the matching variations)', 'nexvio' ),
				'hide'  => esc_html__( 'Hide until a variation is chosen', 'nexvio' ),
			),
			'condition' => array( 'show_price' => 'yes' ),
		) );
		$this->end_controls_section();

		/* ============ STYLE: GENERAL ============ */
		$this->start_controls_section( 'style_general', array( 'label' => esc_html__( 'General', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->vcol( 'label_c', esc_html__( 'Name colour', 'nexvio' ), '--nv-label-c', array( 'var_label_show' => 'yes' ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'      => 'label_typo',
			'label'     => esc_html__( 'Name typography', 'nexvio' ),
			'selector'  => '{{WRAPPER}} .nexvio-var__label',
			'condition' => array( 'var_label_show' => 'yes' ),
		) );
		$this->vsld( 'label_w', esc_html__( 'Name minimum width (beside the options)', 'nexvio' ), '--nv-labelw', 0, 300, true, array( 'px' ), array( 'var_label_show' => 'yes', 'var_label_pos' => 'inline' ) );
		$this->vsld( 'label_gap', esc_html__( 'Space between name and options', 'nexvio' ), '--nv-labelgap', 0, 60, true, array( 'px' ), array( 'var_label_show' => 'yes' ) );
		$this->vsld( 'attr_gap', esc_html__( 'Space between attributes', 'nexvio' ), '--nv-gap', 0, 60, true );
		$this->vsld( 'opt_gap', esc_html__( 'Space between options', 'nexvio' ), '--nv-optgap', 0, 40, false );
		$this->add_responsive_control( 'opts_align', array(
			'label'     => esc_html__( 'Options alignment', 'nexvio' ),
			'type'      => Controls_Manager::CHOOSE,
			'options'   => array(
				'flex-start' => array( 'title' => esc_html__( 'Left', 'nexvio' ), 'icon' => 'eicon-text-align-left' ),
				'center'     => array( 'title' => esc_html__( 'Center', 'nexvio' ), 'icon' => 'eicon-text-align-center' ),
				'flex-end'   => array( 'title' => esc_html__( 'Right', 'nexvio' ), 'icon' => 'eicon-text-align-right' ),
			),
			'selectors' => array( '{{WRAPPER}} .nexvio-var__opts' => 'justify-content:{{VALUE}};' ),
		) );
		$this->vcol( 'missing_c', esc_html__( 'Name colour when nothing is chosen (after clicking Add to cart)', 'nexvio' ), '--nv-missing' );
		$this->add_control( 'unav_op', array(
			'label'     => esc_html__( 'Opacity of unavailable options', 'nexvio' ),
			'type'      => Controls_Manager::SLIDER,
			'range'     => array( 'px' => array( 'min' => 0.1, 'max' => 1, 'step' => 0.05 ) ),
			'default'   => array( 'size' => 0.35 ),
			'selectors' => array( '{{WRAPPER}} .nexvio-var' => '--nv-unav:{{SIZE}};' ),
			'condition' => array( 'var_unavail' => 'dim' ),
		) );
		$this->end_controls_section();

		/* ============ STYLE: DROPDOWN ============ */
		$this->start_controls_section( 'style_dd', array( 'label' => esc_html__( 'Dropdown', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->vcol( 'dd_bg', esc_html__( 'Background', 'nexvio' ), '--nv-ddbg' );
		$this->vcol( 'dd_c', esc_html__( 'Text colour', 'nexvio' ), '--nv-ddc' );
		$this->vcol( 'dd_bc', esc_html__( 'Border colour', 'nexvio' ), '--nv-ddbc' );
		$this->vsld( 'dd_w', esc_html__( 'Width', 'nexvio' ), '--nv-ddw', 100, 800, true, array( 'px', '%' ) );
		$this->vsld( 'dd_h', esc_html__( 'Height', 'nexvio' ), '--nv-ddh', 28, 90, true );
		$this->vsld( 'dd_r', esc_html__( 'Corner radius', 'nexvio' ), '--nv-ddr', 0, 100, false );
		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'dd_typo',
			'selector' => '{{WRAPPER}} .nexvio-var__select',
		) );
		$this->end_controls_section();

		/* ============ STYLE: BUTTONS ============ */
		$this->start_controls_section( 'style_btn', array( 'label' => esc_html__( 'Buttons and swatches', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'opt_typo',
			'label'    => esc_html__( 'Button typography', 'nexvio' ),
			'selector' => '{{WRAPPER}} .nexvio-var__opt--btn, {{WRAPPER}} .nexvio-var__opt--check',
		) );
		$this->vsld( 'btn_h', esc_html__( 'Button minimum height', 'nexvio' ), '--nv-bh', 20, 90, true );
		$this->add_responsive_control( 'btn_pad', array(
			'label'      => esc_html__( 'Button padding', 'nexvio' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em' ),
			'selectors'  => array( '{{WRAPPER}} .nexvio-var' => '--nv-pad:{{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );
		$this->vsld( 'btn_bw', esc_html__( 'Border width', 'nexvio' ), '--nv-bw', 0, 8, false );
		$this->vsld( 'btn_r', esc_html__( 'Corner radius', 'nexvio' ), '--nv-br', 0, 100, false );
		$this->start_controls_tabs( 'btn_tabs' );
		$this->start_controls_tab( 'btn_n', array( 'label' => esc_html__( 'Normal', 'nexvio' ) ) );
		$this->vcol( 'btn_bg', esc_html__( 'Background', 'nexvio' ), '--nv-bg' );
		$this->vcol( 'btn_c', esc_html__( 'Text', 'nexvio' ), '--nv-c' );
		$this->vcol( 'btn_bc', esc_html__( 'Border', 'nexvio' ), '--nv-bc' );
		$this->end_controls_tab();
		$this->start_controls_tab( 'btn_hv', array( 'label' => esc_html__( 'Hover', 'nexvio' ) ) );
		$this->vcol( 'btn_bg_h', esc_html__( 'Background', 'nexvio' ), '--nv-bg-h' );
		$this->vcol( 'btn_c_h', esc_html__( 'Text', 'nexvio' ), '--nv-c-h' );
		$this->vcol( 'btn_bc_h', esc_html__( 'Border', 'nexvio' ), '--nv-bc-h' );
		$this->end_controls_tab();
		$this->start_controls_tab( 'btn_a', array( 'label' => esc_html__( 'Selected', 'nexvio' ) ) );
		$this->vcol( 'btn_bg_a', esc_html__( 'Background', 'nexvio' ), '--nv-bg-a' );
		$this->vcol( 'btn_c_a', esc_html__( 'Text', 'nexvio' ), '--nv-c-a' );
		$this->vcol( 'btn_bc_a', esc_html__( 'Border', 'nexvio' ), '--nv-bc-a' );
		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->vheading( 'h_sw', esc_html__( 'Colour / image swatches', 'nexvio' ) );
		$this->vsld( 'sw_size', esc_html__( 'Swatch size', 'nexvio' ), '--nv-swsize', 16, 100, true );
		$this->vsld( 'sw_r', esc_html__( 'Swatch corner radius', 'nexvio' ), '--nv-swr', 0, 999, false );
		$this->vcol( 'sw_bc', esc_html__( 'Swatch border colour', 'nexvio' ), '--nv-swbc' );
		$this->vcol( 'sw_ring', esc_html__( 'Selected ring colour', 'nexvio' ), '--nv-swring' );

		$this->vheading( 'h_cb', esc_html__( 'Checkbox style', 'nexvio' ) );
		$this->vsld( 'cb_size', esc_html__( 'Box size', 'nexvio' ), '--nv-cbs', 12, 40, false );
		$this->vsld( 'cb_r', esc_html__( 'Box corner radius', 'nexvio' ), '--nv-cbr', 0, 20, false );
		$this->vcol( 'cb_bc', esc_html__( 'Box border colour', 'nexvio' ), '--nv-cbc' );
		$this->vcol( 'cb_on', esc_html__( 'Box colour when ticked', 'nexvio' ), '--nv-cba' );
		$this->vcol( 'cb_tick', esc_html__( 'Tick colour', 'nexvio' ), '--nv-cbt' );
		$this->end_controls_section();

		/* ============ STYLE: CLEAR / STOCK / PRICE ============ */
		$this->start_controls_section( 'style_extra', array( 'label' => esc_html__( 'Clear link, stock and price', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->vheading( 'h_clear', esc_html__( 'Clear link', 'nexvio' ) );
		$this->vcol( 'clear_c', esc_html__( 'Colour', 'nexvio' ), '--nv-clear' );
		$this->vcol( 'clear_c_h', esc_html__( 'Colour on hover', 'nexvio' ), '--nv-clear-h' );
		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'clear_typo',
			'selector' => '{{WRAPPER}} .nexvio-var__clear',
		) );
		$this->add_responsive_control( 'clear_align', array(
			'label'     => esc_html__( 'Alignment', 'nexvio' ),
			'type'      => Controls_Manager::CHOOSE,
			'options'   => array(
				'flex-start' => array( 'title' => esc_html__( 'Left', 'nexvio' ), 'icon' => 'eicon-text-align-left' ),
				'center'     => array( 'title' => esc_html__( 'Center', 'nexvio' ), 'icon' => 'eicon-text-align-center' ),
				'flex-end'   => array( 'title' => esc_html__( 'Right', 'nexvio' ), 'icon' => 'eicon-text-align-right' ),
			),
			'selectors' => array( '{{WRAPPER}} .nexvio-var__tools' => 'justify-content:{{VALUE}};' ),
		) );

		$this->vheading( 'h_avail', esc_html__( 'Stock line', 'nexvio' ) );
		$this->vcol( 'avail_lc', esc_html__( 'Label colour', 'nexvio' ), '--nv-avail-l' );
		$this->vcol( 'avail_in', esc_html__( 'In stock colour', 'nexvio' ), '--nv-avail-in' );
		$this->vcol( 'avail_out', esc_html__( 'Out of stock colour', 'nexvio' ), '--nv-avail-out' );
		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'avail_typo',
			'selector' => '{{WRAPPER}} .nexvio-var__avail',
		) );
		$this->vsld( 'avail_gap', esc_html__( 'Space above the stock line', 'nexvio' ), '--nv-avail-gap', 0, 60, true );
		$this->add_control( 'avail_line', array(
			'label'        => esc_html__( 'Divider line above the stock line', 'nexvio' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => '',
		) );
		$this->vcol( 'avail_line_c', esc_html__( 'Divider colour', 'nexvio' ), '--nv-line', array( 'avail_line' => 'yes' ) );

		$this->vheading( 'h_price', esc_html__( 'Price', 'nexvio' ) );
		$this->vcol( 'price_c', esc_html__( 'Colour', 'nexvio' ), '--nv-price' );
		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'price_typo',
			'selector' => '{{WRAPPER}} .nexvio-var__price',
		) );
		$this->end_controls_section();
	}

	/* ------------------------------------------------------------------
	 * Data
	 * ------------------------------------------------------------------ */

	private function current_product( $s ) {
		$id = ! empty( $s['product_id'] ) ? (int) $s['product_id'] : 0;
		if ( ! $id && is_singular( 'product' ) ) {
			$id = (int) get_queried_object_id();
		}
		if ( ! $id ) {
			global $product;
			if ( $product instanceof WC_Product ) {
				return $product;
			}
			$post_id = get_the_ID();
			if ( $post_id && 'product' === get_post_type( $post_id ) ) {
				$id = (int) $post_id;
			}
		}
		if ( ! $id ) {
			$is_edit = class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode();
			$preview = isset( $_GET['elementor-preview'] ) || isset( $_GET['elementor_library'] ); // phpcs:ignore WordPress.Security.NonceVerification
			if ( $is_edit || $preview || wp_doing_ajax() ) {
				/* the latest variable product, so the editor shows real options */
				$ids = wc_get_products( array( 'type' => 'variable', 'status' => 'publish', 'limit' => 1, 'return' => 'ids' ) );
				$id  = $ids ? (int) $ids[0] : 0;
			}
		}
		return $id ? wc_get_product( $id ) : null;
	}

	/** Price markup of one variation (sale price aware). */
	private function variation_price_html( $v ) {
		$price   = isset( $v['display_price'] ) ? (float) $v['display_price'] : 0;
		$regular = isset( $v['display_regular_price'] ) ? (float) $v['display_regular_price'] : $price;
		if ( $regular > $price ) {
			return '<del aria-hidden="true">' . wc_price( $regular ) . '</del> <ins>' . wc_price( $price ) . '</ins>';
		}
		return wc_price( $price );
	}

	/** The type of one attribute: its own override, else the widget type ("auto" is decided with the values). */
	private function type_for( $s, $name, $label ) {
		$valid = array( 'dropdown', 'buttons', 'swatches', 'checkbox', 'auto' );
		$type  = in_array( $s['var_type'], $valid, true ) ? $s['var_type'] : 'dropdown';
		$own   = 'attr_type_' . self::attr_key( $name );
		if ( isset( $s[ $own ] ) && in_array( $s[ $own ], $valid, true ) ) {
			return $s[ $own ];
		}
		$keys  = array( strtolower( (string) $name ), strtolower( sanitize_title( $name ) ), strtolower( (string) $label ), strtolower( sanitize_title( $label ) ) );
		if ( ! empty( $s['var_overrides'] ) && is_array( $s['var_overrides'] ) ) {
			foreach ( $s['var_overrides'] as $row ) {
				$want = isset( $row['attr'] ) ? strtolower( trim( (string) $row['attr'] ) ) : '';
				if ( '' === $want ) {
					continue;
				}
				if ( in_array( $want, $keys, true ) || in_array( sanitize_title( $want ), $keys, true ) ) {
					$t = isset( $row['type'] ) ? $row['type'] : '';
					if ( in_array( $t, $valid, true ) ) {
						$type = $t;
					}
				}
			}
		}
		return $type;
	}

	/* ------------------------------------------------------------------
	 * Output
	 * ------------------------------------------------------------------ */

	protected function render() {
		$s       = $this->get_settings_for_display();
		$product = $this->current_product( $s );
		$is_edit = class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode();

		if ( ! $product instanceof WC_Product ) {
			if ( $is_edit ) {
				echo '<div class="nexvio-var nexvio-var--note"><p>' . esc_html__( 'No product found. Use this widget in the single product template, or set a fixed product ID.', 'nexvio' ) . '</p></div>';
			}
			return;
		}
		if ( ! $product->is_type( 'variable' ) ) {
			if ( $is_edit ) {
				echo '<div class="nexvio-var nexvio-var--note"><p>' . esc_html__( 'This product has no variations. The widget only shows for variable products.', 'nexvio' ) . '</p></div>';
			}
			return;
		}

		$attributes = $product->get_variation_attributes();
		$available  = $product->get_available_variations();
		if ( ! $attributes || ! $available || ! is_array( $available ) ) {
			if ( $is_edit ) {
				echo '<div class="nexvio-var nexvio-var--note"><p>' . esc_html__( 'This product has no available variations.', 'nexvio' ) . '</p></div>';
			}
			return;
		}

		$pid        = $product->get_id();
		$parent_img = (int) $product->get_image_id();

		/* compact data for the browser: id, attribute values, image, price, stock, text, quantity limits */
		$data        = array();
		$first_attrs = array();
		foreach ( $available as $v ) {
			$img    = null;
			$img_id = ! empty( $v['image_id'] ) ? (int) $v['image_id'] : 0;
			if ( $img_id && $img_id !== $parent_img && ! empty( $v['image']['src'] ) ) {
				$img = array(
					'src'      => (string) $v['image']['src'],
					'full_src' => ! empty( $v['image']['full_src'] ) ? (string) $v['image']['full_src'] : (string) $v['image']['src'],
					'srcset'   => ! empty( $v['image']['srcset'] ) ? (string) $v['image']['srcset'] : '',
					'sizes'    => ! empty( $v['image']['sizes'] ) ? (string) $v['image']['sizes'] : '',
					'alt'      => ! empty( $v['image']['alt'] ) ? (string) $v['image']['alt'] : '',
				);
			}
			$in_stock = ! empty( $v['is_in_stock'] ) && ! empty( $v['is_purchasable'] );
			$attrs    = isset( $v['attributes'] ) && is_array( $v['attributes'] ) ? $v['attributes'] : array();
			if ( $in_stock && ! $first_attrs ) {
				$first_attrs = $attrs;
			}
			$stock_text = isset( $v['availability_html'] ) ? trim( wp_strip_all_tags( (string) $v['availability_html'] ) ) : '';
			if ( '' === $stock_text ) {
				$stock_text = $in_stock ? (string) $s['txt_in'] : (string) $s['txt_out'];
			}
			$data[] = array(
				'id'    => (int) $v['variation_id'],
				'attrs' => $attrs,
				'img'   => $img,
				'price' => $this->variation_price_html( $v ),
				'stock' => $in_stock ? 1 : 0,
				'avail' => $stock_text,
				'min'   => isset( $v['min_qty'] ) && is_numeric( $v['min_qty'] ) ? (int) $v['min_qty'] : 1,
				'max'   => isset( $v['max_qty'] ) && is_numeric( $v['max_qty'] ) ? (int) $v['max_qty'] : 0,
			);
		}

		$defaults = $product->get_default_attributes();
		$show_lab = 'yes' === $s['var_label_show'];
		$groups   = '';
		$uid      = 'nxv-' . $this->get_id() . '-' . $pid;

		foreach ( $attributes as $name => $options ) {
			$slug  = sanitize_title( $name );
			$key   = 'attribute_' . $slug;
			$label = wc_attribute_label( $name, $product );

			/* the values of this attribute: [ value, label, term id ] */
			$opts = array();
			if ( taxonomy_exists( $name ) ) {
				$terms = wc_get_product_terms( $pid, $name, array( 'fields' => 'all' ) );
				foreach ( (array) $terms as $t ) {
					if ( in_array( $t->slug, (array) $options, true ) ) {
						$opts[] = array( 'value' => (string) $t->slug, 'label' => (string) $t->name, 'term' => (int) $t->term_id );
					}
				}
			} else {
				foreach ( (array) $options as $o ) {
					$opts[] = array( 'value' => (string) $o, 'label' => (string) $o, 'term' => 0 );
				}
			}
			if ( ! $opts ) {
				continue;
			}

			/* colour / image of every value (swatches) */
			foreach ( $opts as $n => $o ) {
				$opts[ $n ]['color'] = Nexvio_Swatches::color_for( $o['label'], $o['term'] );
				$opts[ $n ]['pic']   = $o['term'] ? Nexvio_Swatches::image_for( $o['term'] ) : '';
			}

			$type = $this->type_for( $s, $name, $label );
			if ( 'auto' === $type ) {
				/* the type chosen in Products > Attributes decides first */
				$set_type = class_exists( 'Nexvio_Attribute_Types' ) ? Nexvio_Attribute_Types::type_of( $name ) : '';
				$by_type  = array( 'color' => 'swatches', 'image' => 'swatches', 'button' => 'buttons', 'radio' => 'checkbox', 'checkbox' => 'checkbox' );
				if ( isset( $by_type[ $set_type ] ) ) {
					$type = $by_type[ $set_type ];
				}
			}
			if ( 'auto' === $type ) {
				$has_sw = false;
				foreach ( $opts as $o ) {
					if ( $o['color'] || $o['pic'] ) {
						$has_sw = true;
					}
				}
				$type = $has_sw ? 'swatches' : 'buttons';
			}

			/* value chosen when the page opens */
			$sel = '';
			if ( 'none' !== $s['var_preselect'] ) {
				if ( 'first' === $s['var_preselect'] && isset( $first_attrs[ $key ] ) ) {
					$sel = (string) $first_attrs[ $key ];
				} elseif ( 'default' === $s['var_preselect'] && isset( $defaults[ $slug ] ) ) {
					$sel = (string) $defaults[ $slug ];
				}
			}
			$found = '';
			if ( '' !== $sel ) {
				foreach ( $opts as $o ) {
					if ( 0 === strcasecmp( $o['value'], $sel ) ) {
						$found = $o['value'];
					}
				}
			}
			$sel = $found;
			$gid = $uid . '-' . $slug;
			$out = '';

			if ( 'dropdown' === $type ) {
				$out .= '<select class="nexvio-var__select" id="' . esc_attr( $gid ) . '" aria-label="' . esc_attr( $label ) . '">';
				$out .= '<option value="">' . esc_html( $s['txt_choose'] ) . '</option>';
				foreach ( $opts as $o ) {
					$out .= '<option value="' . esc_attr( $o['value'] ) . '"' . ( $sel === $o['value'] ? ' selected' : '' ) . '>' . esc_html( $o['label'] ) . '</option>';
				}
				$out .= '</select>';
			} else {
				foreach ( $opts as $o ) {
					$on = ( '' !== $sel && $sel === $o['value'] );
					if ( 'checkbox' === $type ) {
						$out .= '<label class="nexvio-var__opt nexvio-var__opt--check' . ( $on ? ' is-selected' : '' ) . '" data-value="' . esc_attr( $o['value'] ) . '">'
							. '<input type="radio" name="' . esc_attr( $gid . '-r' ) . '" value="' . esc_attr( $o['value'] ) . '"' . ( $on ? ' checked' : '' ) . '>'
							. '<span class="nexvio-var__box" aria-hidden="true"></span><span class="nexvio-var__opt-text">' . esc_html( $o['label'] ) . '</span></label>';
						continue;
					}
					if ( 'swatches' === $type && ( $o['color'] || $o['pic'] ) ) {
						$out .= '<button type="button" class="nexvio-var__opt nexvio-var__opt--sw' . ( $o['pic'] ? ' is-image' : '' ) . ( $on ? ' is-selected' : '' ) . '" data-value="' . esc_attr( $o['value'] ) . '" title="' . esc_attr( $o['label'] ) . '" aria-label="' . esc_attr( $o['label'] ) . '" aria-pressed="' . ( $on ? 'true' : 'false' ) . '" style="--sw:' . esc_attr( $o['color'] ? $o['color'] : '#999999' ) . ( $o['pic'] ? ';--sw-img:url(' . esc_url( $o['pic'] ) . ')' : '' ) . '"></button>';
						continue;
					}
					$out .= '<button type="button" class="nexvio-var__opt nexvio-var__opt--btn' . ( $on ? ' is-selected' : '' ) . '" data-value="' . esc_attr( $o['value'] ) . '" aria-pressed="' . ( $on ? 'true' : 'false' ) . '">' . esc_html( $o['label'] ) . '</button>';
				}
			}

			$lab = '';
			if ( $show_lab ) {
				$lab = 'dropdown' === $type
					? '<label class="nexvio-var__label" for="' . esc_attr( $gid ) . '">' . esc_html( $label ) . '</label>'
					: '<span class="nexvio-var__label">' . esc_html( $label ) . '</span>';
			}

			$groups .= '<div class="nexvio-var__group nexvio-var__group--' . esc_attr( $type ) . '" role="group" data-attr="' . esc_attr( $key ) . '" data-value="' . esc_attr( $sel ) . '"' . ( $show_lab ? '' : ' aria-label="' . esc_attr( $label ) . '"' ) . '>'
				. $lab
				. '<div class="nexvio-var__opts">' . $out . '</div>'
				. '</div>';
		}

		if ( '' === $groups ) {
			return;
		}

		$cfg = array(
			'product' => $pid,
			'img'     => 'yes' === $s['img_swap'],
			'priceAll' => ( 'yes' === $s['show_price'] && 'hide' !== $s['price_before'] ) ? (string) $product->get_price_html() : '',
			'texts'   => array(
				'na' => (string) $s['txt_na'],
			),
		);

		$classes = array( 'nexvio-var' );
		$classes[] = 'inline' === $s['var_label_pos'] ? 'nexvio-var--label-inline' : 'nexvio-var--label-above';
		if ( 'yes' === $s['avail_line'] ) {
			$classes[] = 'nexvio-var--line';
		}
		?>
		<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
			data-product="<?php echo esc_attr( $pid ); ?>"
			data-unav="<?php echo 'hide' === $s['var_unavail'] ? 'hide' : 'dim'; ?>"
			data-config="<?php echo esc_attr( wp_json_encode( $cfg ) ); ?>"
			data-variations="<?php echo esc_attr( wp_json_encode( $data ) ); ?>">
			<div class="nexvio-var__attrs"><?php echo $groups; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- every part is escaped above. ?></div>

			<?php if ( 'yes' === $s['show_clear'] ) : ?>
				<div class="nexvio-var__tools">
					<button type="button" class="nexvio-var__clear" hidden><?php echo esc_html( $s['txt_clear'] ); ?></button>
				</div>
			<?php endif; ?>

			<?php if ( 'yes' === $s['show_price'] ) : ?>
				<div class="nexvio-var__price" hidden></div>
			<?php endif; ?>

			<?php if ( 'yes' === $s['show_avail'] ) : ?>
				<div class="nexvio-var__avail" hidden>
					<span class="nexvio-var__avail-label"><?php echo esc_html( $s['txt_avail'] ); ?></span>
					<span class="nexvio-var__avail-val"></span>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}
}
