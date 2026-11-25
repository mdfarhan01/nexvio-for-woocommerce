<?php
/**
 * NEXVIO Product Showcase - Elementor widget.
 *
 * One big product view (image, title, short description, price, stock countdown,
 * payment options, quantity + Add to cart) with a clickable list of products
 * underneath. Clicking a product in the list swaps the whole view instantly.
 *
 * Every product is rendered once (hidden panels), so switching needs no reload
 * and no extra request. Add to cart uses the same AJAX as the Add to Cart widget.
 *
 * @package Nexvio_For_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;
use Elementor\Group_Control_Typography;

class Nexvio_Product_Showcase_Widget extends Widget_Base {

	use Nexvio_Card_Trait;

	public function get_name() {
		return 'nexvio_product_showcase';
	}

	public function get_title() {
		return esc_html__( 'Product Showcase (Tabs)', 'nexvio' );
	}

	public function get_icon() {
		return 'eicon-tabs';
	}

	public function get_categories() {
		return array( 'nexvio' );
	}

	public function get_keywords() {
		return array( 'nexvio', 'woocommerce', 'product', 'showcase', 'tabs', 'switcher', 'list', 'payment', 'tabby', 'tamara', 'stock' );
	}

	public function get_style_depends() {
		return array( 'nexvio-add-to-cart', 'nexvio-product-showcase' );
	}

	public function get_script_depends() {
		$deps = array( 'nexvio-add-to-cart', 'nexvio-product-showcase' );
		$tabby = self::tabby_script_handle();
		if ( $tabby ) {
			array_unshift( $deps, $tabby );
		}
		return $deps;
	}

	/* ------------------------------------------------------------------
	 * Tabby / Tamara plugin bridge
	 * ------------------------------------------------------------------ */

	/** Is the Tabby Checkout plugin (with its promo widget) available? */
	public static function tabby_active() {
		return class_exists( 'WC_Tabby_Promo' ) && class_exists( 'WC_Tabby_Config' ) && method_exists( 'WC_Tabby_Promo', 'get_tabby_promo_domain' );
	}

	/** Is the Tamara Checkout plugin available and its gateway on? */
	public static function tamara_active() {
		if ( ! class_exists( '\\Tamara\\Wp\\Plugin\\TamaraCheckout' ) ) {
			return false;
		}
		$tc = \Tamara\Wp\Plugin\TamaraCheckout::getInstance();
		return $tc && method_exists( $tc, 'isTamaraGatewayEnabled' ) && $tc->isTamaraGatewayEnabled();
	}

	/**
	 * Handle of the script that draws the Tabby promo card. The Tabby plugin only loads it on product / cart pages,
	 * so the showcase registers the same file for every page where the widget is used.
	 */
	public static function tabby_script_handle() {
		if ( ! self::tabby_active() || 'no' === get_option( 'tabby_promo' ) ) {
			return '';
		}
		if ( wp_script_is( 'tabby-checkout-promo', 'registered' ) ) {
			return 'tabby-checkout-promo';
		}
		if ( ! wp_script_is( 'nexvio-tabby-promo', 'registered' ) ) {
			wp_register_script( 'nexvio-tabby-promo', 'https://' . WC_Tabby_Promo::get_tabby_promo_domain() . '/tabby-promo.js', array(), null, true ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- external script, version is handled by Tabby.
		}
		return 'nexvio-tabby-promo';
	}

	/* ------------------------------------------------------------------
	 * Small control helpers (selectors are relative to the widget wrapper)
	 * ------------------------------------------------------------------ */

	/** Colour control writing one CSS property. */
	private function col( $id, $label, $selector, $prop, $default = '', $condition = array() ) {
		$args = array(
			'label'     => $label,
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} ' . $selector => $prop . ':{{VALUE}};' ),
		);
		if ( '' !== $default ) {
			$args['default'] = $default;
		}
		if ( $condition ) {
			$args['condition'] = $condition;
		}
		$this->add_control( $id, $args );
	}

	/** Slider control (responsive by default) writing one CSS declaration with {{SIZE}}{{UNIT}}. */
	private function sld( $id, $label, $selector, $decl, $default, $min, $max, $responsive = true, $units = array( 'px' ), $condition = array() ) {
		$args = array(
			'label'      => $label,
			'type'       => Controls_Manager::SLIDER,
			'size_units' => $units,
			'range'      => array(
				'px' => array( 'min' => $min, 'max' => $max ),
				'%'  => array( 'min' => 0, 'max' => 100 ),
				'em' => array( 'min' => 0, 'max' => 10, 'step' => 0.1 ),
			),
			'selectors'  => array( '{{WRAPPER}} ' . $selector => $decl . ':{{SIZE}}{{UNIT}};' ),
		);
		if ( null !== $default ) {
			$args['default'] = array( 'size' => $default, 'unit' => $units[0] );
		}
		if ( $condition ) {
			$args['condition'] = $condition;
		}
		if ( $responsive ) {
			$this->add_responsive_control( $id, $args );
		} else {
			$this->add_control( $id, $args );
		}
	}

	/** Four-sided spacing control. */
	private function dim( $id, $label, $selector, $prop = 'padding', $condition = array() ) {
		$args = array(
			'label'      => $label,
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em', '%' ),
			'selectors'  => array( '{{WRAPPER}} ' . $selector => $prop . ':{{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		);
		if ( $condition ) {
			$args['condition'] = $condition;
		}
		$this->add_responsive_control( $id, $args );
	}

	/** Radius control (one value, px). */
	private function rad( $id, $label, $selector, $default, $condition = array() ) {
		$this->sld( $id, $label, $selector, 'border-radius', $default, 0, 100, false, array( 'px' ), $condition );
	}

	private function typo( $id, $selector, $label = '' ) {
		$args = array( 'name' => $id, 'selector' => '{{WRAPPER}} ' . $selector );
		if ( $label ) {
			$args['label'] = $label;
		}
		$this->add_group_control( Group_Control_Typography::get_type(), $args );
	}

	private function heading( $id, $label ) {
		$this->add_control( $id, array(
			'label'     => $label,
			'type'      => Controls_Manager::HEADING,
			'separator' => 'before',
		) );
	}

	private function image_size_options() {
		$options = array();
		foreach ( get_intermediate_image_sizes() as $size ) {
			$options[ $size ] = ucwords( str_replace( array( '_', '-' ), ' ', $size ) );
		}
		$options['full'] = esc_html__( 'Full', 'nexvio' );
		return $options;
	}

	/* ------------------------------------------------------------------
	 * Controls
	 * ------------------------------------------------------------------ */

	protected function register_controls() {

		/* ============ PRODUCTS (query) ============ */
		$this->register_query_section();
		$this->update_control( 'limit', array(
			'default'     => 8,
			'description' => esc_html__( 'Every product is rendered on the page (hidden until it is clicked), so keep this reasonable.', 'nexvio' ),
		) );

		/* ============ SINGLE PRODUCT PAGE ============ */
		$this->start_controls_section( 'section_single', array( 'label' => esc_html__( 'Single product page', 'nexvio' ) ) );
		$this->switcher( 'use_current', esc_html__( 'Show the current product first', 'nexvio' ), 'yes' );
		$this->add_control( 'single_note', array(
			'type'            => Controls_Manager::RAW_HTML,
			'raw'             => esc_html__( 'On a product page the product being viewed is shown first, with its full info. The list below holds the other products, and clicking one switches the whole view. On other pages only the products from "Products (query)" are used.', 'nexvio' ),
			'content_classes' => 'elementor-descriptor',
			'condition'       => array( 'use_current' => 'yes' ),
		) );
		$this->add_control( 'list_from', array(
			'label'       => esc_html__( 'Other products in the list', 'nexvio' ),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'same_cat',
			'options'     => array(
				'same_cat' => esc_html__( 'Same category as the current product', 'nexvio' ),
				'query'    => esc_html__( 'Products chosen in "Products (query)"', 'nexvio' ),
			),
			'description' => esc_html__( 'Number of products, order and sold-out options come from "Products (query)".', 'nexvio' ),
			'condition'   => array( 'use_current' => 'yes' ),
		) );
		$this->add_control( 'product_id', array(
			'label'       => esc_html__( 'Fixed product ID (optional)', 'nexvio' ),
			'type'        => Controls_Manager::NUMBER,
			'min'         => 0,
			'default'     => '',
			'description' => esc_html__( 'Leave empty to use the product being viewed. In the Elementor editor, empty = latest product.', 'nexvio' ),
			'condition'   => array( 'use_current' => 'yes' ),
		) );
		$this->end_controls_section();

		/* ============ LAYOUT (responsive) ============ */
		$this->start_controls_section( 'section_layout', array( 'label' => esc_html__( 'Layout (per device)', 'nexvio' ) ) );

		$this->add_responsive_control( 'stage_layout', array(
			'label'                => esc_html__( 'Product view', 'nexvio' ),
			'type'                 => Controls_Manager::SELECT,
			'default'              => 'side',
			'tablet_default'       => 'stack',
			'mobile_default'       => 'stack',
			'options'              => array(
				'side'      => esc_html__( '2 columns: image left, info right', 'nexvio' ),
				'side_rev'  => esc_html__( '2 columns: info left, image right', 'nexvio' ),
				'stack'     => esc_html__( '1 column: image on top, info below', 'nexvio' ),
				'stack_rev' => esc_html__( '1 column: info on top, image below', 'nexvio' ),
			),
			'selectors_dictionary' => array(
				'side'      => '--shw-st-cols:minmax(0,var(--shw-img-w)) minmax(0,1fr);--shw-img-o:1;--shw-info-o:2;',
				'side_rev'  => '--shw-st-cols:minmax(0,1fr) minmax(0,var(--shw-img-w));--shw-img-o:2;--shw-info-o:1;',
				'stack'     => '--shw-st-cols:minmax(0,1fr);--shw-img-o:1;--shw-info-o:2;',
				'stack_rev' => '--shw-st-cols:minmax(0,1fr);--shw-img-o:2;--shw-info-o:1;',
			),
			'selectors'            => array( '{{WRAPPER}} .nexvio-shw' => '{{VALUE}}' ),
			'description'          => esc_html__( 'Use the desktop / tablet / mobile icons at the top of the panel to set each device.', 'nexvio' ),
		) );

		$this->add_responsive_control( 'buy_layout', array(
			'label'                => esc_html__( 'Price and payment options', 'nexvio' ),
			'type'                 => Controls_Manager::SELECT,
			'default'              => 'beside',
			'tablet_default'       => 'beside',
			'mobile_default'       => 'stacked',
			'options'              => array(
				'beside'  => esc_html__( 'Price on the left, payments + button on the right', 'nexvio' ),
				'stacked' => esc_html__( 'Stacked: price, payments, button', 'nexvio' ),
			),
			'selectors_dictionary' => array(
				'beside'  => '--shw-buy-cols:var(--shw-price-w,170px) minmax(0,1fr);',
				'stacked' => '--shw-buy-cols:minmax(0,1fr);',
			),
			'selectors'            => array( '{{WRAPPER}} .nexvio-shw' => '{{VALUE}}' ),
		) );

		$this->add_responsive_control( 'list_cols', array(
			'label'                => esc_html__( 'Product list columns', 'nexvio' ),
			'type'                 => Controls_Manager::SELECT,
			'default'              => 'auto',
			'tablet_default'       => 'auto',
			'mobile_default'       => '2',
			'options'              => array(
				'auto'   => esc_html__( 'Auto (as many as fit, natural width)', 'nexvio' ),
				'scroll' => esc_html__( 'One row, swipe / scroll sideways', 'nexvio' ),
				'1'      => '1',
				'2'      => '2',
				'3'      => '3',
				'4'      => '4',
				'5'      => '5',
				'6'      => '6',
			),
			'selectors_dictionary' => array(
				'auto'   => '--shw-list-d:flex;--shw-list-cols:none;--shw-list-wrap:wrap;--shw-list-ov:visible;--shw-item-fs:1;',
				'scroll' => '--shw-list-d:flex;--shw-list-cols:none;--shw-list-wrap:nowrap;--shw-list-ov:auto;--shw-item-fs:0;',
				'1'      => '--shw-list-d:grid;--shw-list-cols:repeat(1,minmax(0,1fr));--shw-list-wrap:wrap;--shw-list-ov:visible;--shw-item-fs:1;',
				'2'      => '--shw-list-d:grid;--shw-list-cols:repeat(2,minmax(0,1fr));--shw-list-wrap:wrap;--shw-list-ov:visible;--shw-item-fs:1;',
				'3'      => '--shw-list-d:grid;--shw-list-cols:repeat(3,minmax(0,1fr));--shw-list-wrap:wrap;--shw-list-ov:visible;--shw-item-fs:1;',
				'4'      => '--shw-list-d:grid;--shw-list-cols:repeat(4,minmax(0,1fr));--shw-list-wrap:wrap;--shw-list-ov:visible;--shw-item-fs:1;',
				'5'      => '--shw-list-d:grid;--shw-list-cols:repeat(5,minmax(0,1fr));--shw-list-wrap:wrap;--shw-list-ov:visible;--shw-item-fs:1;',
				'6'      => '--shw-list-d:grid;--shw-list-cols:repeat(6,minmax(0,1fr));--shw-list-wrap:wrap;--shw-list-ov:visible;--shw-item-fs:1;',
			),
			'selectors'            => array( '{{WRAPPER}} .nexvio-shw' => '{{VALUE}}' ),
			'separator'            => 'before',
		) );

		$this->add_control( 'list_pos', array(
			'label'   => esc_html__( 'Product list position', 'nexvio' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'below',
			'options' => array(
				'below' => esc_html__( 'Below the product view', 'nexvio' ),
				'above' => esc_html__( 'Above the product view', 'nexvio' ),
			),
		) );
		$this->add_control( 'trigger', array(
			'label'   => esc_html__( 'Switch product on', 'nexvio' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'click',
			'options' => array(
				'click' => esc_html__( 'Click / tap', 'nexvio' ),
				'hover' => esc_html__( 'Hover (desktop) and tap', 'nexvio' ),
			),
		) );
		$this->add_control( 'default_index', array(
			'label'       => esc_html__( 'Product shown first (position in the list)', 'nexvio' ),
			'type'        => Controls_Manager::NUMBER,
			'min'         => 1,
			'max'         => 60,
			'default'     => 1,
		) );
		$this->add_control( 'anim', array(
			'label'   => esc_html__( 'Change animation', 'nexvio' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'fade',
			'options' => array(
				'none'  => esc_html__( 'None', 'nexvio' ),
				'fade'  => esc_html__( 'Fade', 'nexvio' ),
				'slide' => esc_html__( 'Fade and slide up', 'nexvio' ),
			),
		) );
		$this->end_controls_section();

		/* ============ ELEMENTS ============ */
		$this->start_controls_section( 'section_elements', array( 'label' => esc_html__( 'Elements', 'nexvio' ) ) );
		$this->add_control( 'img_size', array(
			'label'   => esc_html__( 'Image size', 'nexvio' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'large',
			'options' => $this->image_size_options(),
		) );
		$this->switcher( 'link_image', esc_html__( 'Image links to the product page', 'nexvio' ), '' );

		$this->heading( 'h_title', esc_html__( 'Title', 'nexvio' ) );
		$this->switcher( 'show_title', esc_html__( 'Show title', 'nexvio' ) );
		$this->add_control( 'title_tag', array(
			'label'     => esc_html__( 'Title HTML tag', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'h2',
			'options'   => array( 'h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4', 'h5' => 'H5', 'h6' => 'H6', 'div' => 'div', 'p' => 'p' ),
			'condition' => array( 'show_title' => 'yes' ),
		) );
		$this->switcher( 'link_title', esc_html__( 'Title links to the product page', 'nexvio' ), '', array( 'condition' => array( 'show_title' => 'yes' ) ) );

		$this->heading( 'h_desc', esc_html__( 'Description', 'nexvio' ) );
		$this->switcher( 'show_desc', esc_html__( 'Show description', 'nexvio' ) );
		$this->add_control( 'desc_source', array(
			'label'     => esc_html__( 'Text from', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'short',
			'options'   => array(
				'short' => esc_html__( 'Short description', 'nexvio' ),
				'full'  => esc_html__( 'Full description', 'nexvio' ),
			),
			'condition' => array( 'show_desc' => 'yes' ),
		) );
		$this->switcher( 'desc_fallback', esc_html__( 'If the short description is empty, use the full one', 'nexvio' ), 'yes', array( 'condition' => array( 'show_desc' => 'yes', 'desc_source' => 'short' ) ) );
		$this->add_control( 'desc_words', array(
			'label'       => esc_html__( 'Limit to words (0 = show all)', 'nexvio' ),
			'type'        => Controls_Manager::NUMBER,
			'min'         => 0,
			'max'         => 300,
			'default'     => 0,
			'description' => esc_html__( 'With a limit the text is plain (no bold, links or lists).', 'nexvio' ),
			'condition'   => array( 'show_desc' => 'yes' ),
		) );

		$this->heading( 'h_price', esc_html__( 'Price', 'nexvio' ) );
		$this->switcher( 'show_price', esc_html__( 'Show price', 'nexvio' ) );

		$this->heading( 'h_stock', esc_html__( 'Stock countdown', 'nexvio' ) );
		$this->switcher( 'show_stock', esc_html__( 'Show stock', 'nexvio' ) );
		$sc = array( 'show_stock' => 'yes' );
		$this->add_control( 'stock_below', array(
			'label'       => esc_html__( 'Show the quantity left only at or below', 'nexvio' ),
			'type'        => Controls_Manager::NUMBER,
			'min'         => 0,
			'max'         => 9999,
			'default'     => 0,
			'description' => esc_html__( '0 = always show the quantity left (when the product manages stock). Above the limit the plain "In stock" text is shown.', 'nexvio' ),
			'condition'   => $sc,
		) );
		$this->add_control( 'stock_low', array(
			'label'       => esc_html__( 'Warning colour at or below', 'nexvio' ),
			'type'        => Controls_Manager::NUMBER,
			'min'         => 0,
			'max'         => 9999,
			'default'     => 10,
			'condition'   => $sc,
		) );
		$this->switcher( 'stock_bar', esc_html__( 'Show countdown bar', 'nexvio' ), 'yes', array( 'condition' => $sc ) );
		$this->add_control( 'stock_bar_max', array(
			'label'       => esc_html__( 'Bar is full at (quantity)', 'nexvio' ),
			'type'        => Controls_Manager::NUMBER,
			'min'         => 1,
			'max'         => 99999,
			'default'     => 50,
			'description' => esc_html__( 'Example: 50 means 25 left fills half of the bar.', 'nexvio' ),
			'condition'   => array( 'show_stock' => 'yes', 'stock_bar' => 'yes' ),
		) );
		$this->text( 'txt_in', esc_html__( 'In stock text', 'nexvio' ), 'In stock', array( 'condition' => $sc ) );
		$this->text( 'txt_count', esc_html__( 'Quantity left text ({n} = quantity)', 'nexvio' ), 'Only {n} left in stock', array( 'condition' => $sc ) );
		$this->text( 'txt_out', esc_html__( 'Sold out text', 'nexvio' ), 'Sold out', array( 'condition' => $sc ) );
		$this->text( 'txt_back', esc_html__( 'Backorder text', 'nexvio' ), 'Available on backorder', array( 'condition' => $sc ) );

		$this->heading( 'h_atc', esc_html__( 'Add to cart', 'nexvio' ) );
		$this->switcher( 'show_atc', esc_html__( 'Show Add to cart button', 'nexvio' ) );
		$ac = array( 'show_atc' => 'yes' );
		$this->switcher( 'show_qty', esc_html__( 'Quantity box', 'nexvio' ), 'yes', array( 'condition' => $ac ) );
		$this->add_control( 'qty_style', array(
			'label'     => esc_html__( 'Quantity style', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'input',
			'options'   => array(
				'input'   => esc_html__( 'Simple number box', 'nexvio' ),
				'stepper' => esc_html__( 'With − / + buttons', 'nexvio' ),
			),
			'condition' => array( 'show_atc' => 'yes', 'show_qty' => 'yes' ),
		) );
		$this->add_control( 'btn_icon', array(
			'label'       => esc_html__( 'Button icon', 'nexvio' ),
			'type'        => Controls_Manager::ICONS,
			'default'     => array( 'value' => '', 'library' => '' ),
			'skin'        => 'inline',
			'label_block' => false,
			'condition'   => $ac,
		) );
		$this->text( 'txt_add', esc_html__( 'Button text', 'nexvio' ), 'Add to cart', array( 'condition' => $ac ) );
		$this->text( 'txt_select', esc_html__( 'Button text (variable products)', 'nexvio' ), 'Select options', array( 'condition' => $ac, 'description' => esc_html__( 'Variable and other non-simple products open their product page.', 'nexvio' ) ) );
		$this->text( 'txt_btn_out', esc_html__( 'Button text (sold out)', 'nexvio' ), 'Sold out', array( 'condition' => $ac ) );
		$this->text( 'txt_adding', esc_html__( 'While adding', 'nexvio' ), 'Adding…', array( 'condition' => $ac ) );
		$this->text( 'txt_added', esc_html__( 'After adding', 'nexvio' ), 'Added to cart', array( 'condition' => $ac ) );
		$this->text( 'txt_error', esc_html__( 'Error', 'nexvio' ), 'Could not add this product. Please try again.', array( 'condition' => $ac ) );
		$this->switcher( 'show_cart_link', esc_html__( 'Show "view cart" link after adding', 'nexvio' ), 'yes', array( 'condition' => $ac ) );
		$this->text( 'txt_cart', esc_html__( '"View cart" text', 'nexvio' ), 'View cart', array( 'condition' => array( 'show_atc' => 'yes', 'show_cart_link' => 'yes' ) ) );
		$this->switcher( 'ajax', esc_html__( 'Add without reloading (AJAX)', 'nexvio' ), 'yes', array( 'condition' => $ac ) );
		$this->add_control( 'redirect', array(
			'label'     => esc_html__( 'After adding', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'wc',
			'options'   => array(
				'wc'   => esc_html__( 'Follow WooCommerce setting', 'nexvio' ),
				'stay' => esc_html__( 'Stay on the page', 'nexvio' ),
				'cart' => esc_html__( 'Go to the cart', 'nexvio' ),
			),
			'condition' => $ac,
		) );
		$this->add_control( 'side_cart', array(
			'label'       => esc_html__( 'Open the side cart after adding', 'nexvio' ),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'global',
			'options'     => array(
				'global' => esc_html__( 'Dashboard setting', 'nexvio' ),
				'open'   => esc_html__( 'Always open it', 'nexvio' ),
				'no'     => esc_html__( 'Do not open it', 'nexvio' ),
			),
			'condition'   => $ac,
		) );
		$this->end_controls_section();

		/* ============ VARIATIONS ============ */
		$this->start_controls_section( 'section_vars', array( 'label' => esc_html__( 'Variations (colour, size…)', 'nexvio' ) ) );
		$this->switcher( 'show_vars', esc_html__( 'Show variations', 'nexvio' ), 'yes', array(
			'description' => esc_html__( 'For variable products: the customer picks colour, size… here and Add to cart adds that exact variation. Off = variable products only show the "Select options" link.', 'nexvio' ),
		) );
		$vc = array( 'show_vars' => 'yes' );
		$this->add_control( 'var_type', array(
			'label'     => esc_html__( 'Selector type', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'dropdown',
			'options'   => array(
				'dropdown' => esc_html__( 'Dropdown', 'nexvio' ),
				'buttons'  => esc_html__( 'Buttons', 'nexvio' ),
				'swatches' => esc_html__( 'Colour / image swatches (text buttons when there is no colour)', 'nexvio' ),
				'checkbox' => esc_html__( 'Checkbox style (tick box + name)', 'nexvio' ),
			),
			'condition' => $vc,
		) );
		$this->add_control( 'var_pos', array(
			'label'     => esc_html__( 'Position', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'side',
			'options'   => array(
				'side'  => esc_html__( 'Above Add to cart, under the payment boxes', 'nexvio' ),
				'price' => esc_html__( 'Right under the price (price column)', 'nexvio' ),
			),
			'condition' => $vc,
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
			'condition' => $vc,
		) );
		$this->switcher( 'var_label_show', esc_html__( 'Show the attribute name (Color, Size…)', 'nexvio' ), 'yes', array( 'condition' => $vc ) );
		$this->add_responsive_control( 'var_label_pos', array(
			'label'                => esc_html__( 'Name position', 'nexvio' ),
			'type'                 => Controls_Manager::SELECT,
			'default'              => 'inline',
			'options'              => array(
				'inline' => esc_html__( 'Beside the options', 'nexvio' ),
				'above'  => esc_html__( 'Above the options', 'nexvio' ),
			),
			'selectors_dictionary' => array(
				'inline' => '--shw-v-dir:row;--shw-v-align:center;',
				'above'  => '--shw-v-dir:column;--shw-v-align:stretch;',
			),
			'selectors'            => array( '{{WRAPPER}} .nexvio-shw__vars' => '{{VALUE}}' ),
			'condition'            => array( 'show_vars' => 'yes', 'var_label_show' => 'yes' ),
		) );
		$this->switcher( 'var_img_swap', esc_html__( 'Change the product image when a variation is chosen', 'nexvio' ), 'yes', array(
			'description' => esc_html__( 'Uses the image set on the variation (Products → Variations → image). A colour whose variations share one image shows it even before the size is chosen.', 'nexvio' ),
			'condition'   => $vc,
		) );
		$this->switcher( 'var_price_swap', esc_html__( 'Change the price when a variation is chosen', 'nexvio' ), 'yes', array( 'condition' => $vc ) );
		$this->add_control( 'var_unavail', array(
			'label'     => esc_html__( 'Options that are not available', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'dim',
			'options'   => array(
				'dim'  => esc_html__( 'Dim and cross out', 'nexvio' ),
				'hide' => esc_html__( 'Hide', 'nexvio' ),
			),
			'condition' => $vc,
		) );
		$this->text( 'txt_choose', esc_html__( 'Dropdown placeholder', 'nexvio' ), 'Choose an option', array( 'condition' => array( 'show_vars' => 'yes', 'var_type' => 'dropdown' ) ) );
		$this->text( 'txt_pick', esc_html__( 'Message when nothing is chosen', 'nexvio' ), 'Please choose a product option first.', array( 'condition' => $vc ) );
		$this->text( 'txt_unavail', esc_html__( 'Button text (combination not available)', 'nexvio' ), 'Unavailable', array( 'condition' => $vc ) );
		$this->end_controls_section();

		/* ============ PAYMENT OPTIONS ============ */
		$this->start_controls_section( 'section_pay', array( 'label' => esc_html__( 'Payment options (Tabby, Tamara…)', 'nexvio' ) ) );
		$this->switcher( 'show_pay', esc_html__( 'Show payment options', 'nexvio' ) );
		$this->add_control( 'pay_source', array(
			'label'     => esc_html__( 'Payment options come from', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'plugins',
			'options'   => array(
				'plugins' => esc_html__( 'Tabby and Tamara plugins (automatic, same as the product page)', 'nexvio' ),
				'manual'  => esc_html__( 'Manual boxes (I type the text myself)', 'nexvio' ),
			),
			'condition' => array( 'show_pay' => 'yes' ),
		) );
		$this->add_control( 'pay_plugins_note', array(
			'type'            => Controls_Manager::RAW_HTML,
			'raw'             => esc_html__( 'The boxes are drawn by the Tabby and Tamara plugins for each product price, with their own text, language, limits and Learn more pop-up. Change the text in the Tabby / Tamara settings (WooCommerce → Settings → Payments), not here.', 'nexvio' ),
			'content_classes' => 'elementor-descriptor',
			'condition'       => array( 'show_pay' => 'yes', 'pay_source' => 'plugins' ),
		) );
		$this->switcher( 'pay_tabby', esc_html__( 'Show Tabby', 'nexvio' ), 'yes', array( 'condition' => array( 'show_pay' => 'yes', 'pay_source' => 'plugins' ) ) );
		$this->switcher( 'pay_tamara', esc_html__( 'Show Tamara', 'nexvio' ), 'yes', array( 'condition' => array( 'show_pay' => 'yes', 'pay_source' => 'plugins' ) ) );
		$this->add_control( 'pay_order', array(
			'label'     => esc_html__( 'Order', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'tabby_first',
			'options'   => array(
				'tabby_first'  => esc_html__( 'Tabby, then Tamara', 'nexvio' ),
				'tamara_first' => esc_html__( 'Tamara, then Tabby', 'nexvio' ),
			),
			'condition' => array( 'show_pay' => 'yes', 'pay_source' => 'plugins' ),
		) );

		$rep = new Repeater();
		$rep->add_control( 'pay_on', array(
			'label'        => esc_html__( 'Enabled', 'nexvio' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		) );
		$rep->add_control( 'pay_text', array(
			'label'       => esc_html__( 'Text', 'nexvio' ),
			'type'        => Controls_Manager::TEXTAREA,
			'rows'        => 3,
			'default'     => 'As low as {part}/month or {parts} interest-free payments.',
			'description' => esc_html__( '{price} = product price, {part} = price ÷ number of payments, {parts} = number of payments. <b>, <strong>, <em>, <br> are allowed.', 'nexvio' ),
		) );
		$rep->add_control( 'pay_parts', array(
			'label'   => esc_html__( 'Number of payments', 'nexvio' ),
			'type'    => Controls_Manager::NUMBER,
			'min'     => 1,
			'max'     => 60,
			'default' => 4,
		) );
		$rep->add_control( 'pay_link_text', array(
			'label'   => esc_html__( 'Link text', 'nexvio' ),
			'type'    => Controls_Manager::TEXT,
			'default' => 'Learn more',
		) );
		$rep->add_control( 'pay_link', array(
			'label'       => esc_html__( 'Link', 'nexvio' ),
			'type'        => Controls_Manager::URL,
			'placeholder' => 'https://',
			'default'     => array( 'url' => '', 'is_external' => true, 'nofollow' => true ),
		) );
		$rep->add_control( 'pay_logo', array(
			'label'     => esc_html__( 'Logo image (optional)', 'nexvio' ),
			'type'      => Controls_Manager::MEDIA,
			'default'   => array( 'url' => '' ),
			'separator' => 'before',
		) );
		$rep->add_control( 'pay_badge', array(
			'label'       => esc_html__( 'Badge text (used when there is no logo image)', 'nexvio' ),
			'type'        => Controls_Manager::TEXT,
			'default'     => 'tabby',
			'condition'   => array( 'pay_logo[url]' => '' ),
		) );
		$rep->add_control( 'pay_badge_bg', array(
			'label'     => esc_html__( 'Badge background', 'nexvio' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#3bffb1',
			'selectors' => array( '{{WRAPPER}} {{CURRENT_ITEM}} .nexvio-shw__pay-badge' => 'background:{{VALUE}};' ),
			'condition' => array( 'pay_logo[url]' => '' ),
		) );
		$rep->add_control( 'pay_badge_color', array(
			'label'     => esc_html__( 'Badge text colour', 'nexvio' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#111111',
			'selectors' => array( '{{WRAPPER}} {{CURRENT_ITEM}} .nexvio-shw__pay-badge' => 'color:{{VALUE}};' ),
			'condition' => array( 'pay_logo[url]' => '' ),
		) );
		$rep->add_control( 'pay_min', array(
			'label'       => esc_html__( 'Show only if the price is at least', 'nexvio' ),
			'type'        => Controls_Manager::NUMBER,
			'min'         => 0,
			'step'        => 0.01,
			'default'     => '',
			'separator'   => 'before',
			'description' => esc_html__( 'Leave empty for no minimum.', 'nexvio' ),
		) );
		$rep->add_control( 'pay_max', array(
			'label'       => esc_html__( 'Show only if the price is at most', 'nexvio' ),
			'type'        => Controls_Manager::NUMBER,
			'min'         => 0,
			'step'        => 0.01,
			'default'     => '',
			'description' => esc_html__( 'Leave empty for no maximum.', 'nexvio' ),
		) );
		$rep->add_control( 'pay_bg', array(
			'label'     => esc_html__( 'Box background (this option only)', 'nexvio' ),
			'type'      => Controls_Manager::COLOR,
			'separator' => 'before',
			'selectors' => array( '{{WRAPPER}} {{CURRENT_ITEM}}.nexvio-shw__pay' => 'background:{{VALUE}};' ),
		) );
		$rep->add_control( 'pay_color', array(
			'label'     => esc_html__( 'Box text colour (this option only)', 'nexvio' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} {{CURRENT_ITEM}}.nexvio-shw__pay' => 'color:{{VALUE}};' ),
		) );

		$this->add_control( 'pay_items', array(
			'label'       => esc_html__( 'Options', 'nexvio' ),
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $rep->get_controls(),
			'default'     => array(
				array(
					'pay_on'          => 'yes',
					'pay_text'        => 'As low as {part}/month or {parts} interest-free payments.',
					'pay_parts'       => 4,
					'pay_link_text'   => 'Learn more',
					'pay_badge'       => 'tabby',
					'pay_badge_bg'    => '#3bffb1',
					'pay_badge_color' => '#111111',
				),
				array(
					'pay_on'          => 'yes',
					'pay_text'        => '{part}/Month for up to {parts} months, no late fees!',
					'pay_parts'       => 4,
					'pay_link_text'   => 'Learn more',
					'pay_badge'       => 'tamara',
					'pay_badge_bg'    => '#f7c6d9',
					'pay_badge_color' => '#111111',
				),
			),
			'title_field' => '{{{ pay_badge || pay_text }}}',
			'condition'   => array( 'show_pay' => 'yes', 'pay_source' => 'manual' ),
		) );
		$this->add_control( 'pay_logo_pos', array(
			'label'     => esc_html__( 'Logo / badge position', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'row',
			'options'   => array(
				'row'         => esc_html__( 'Text first, logo on the right', 'nexvio' ),
				'row-reverse' => esc_html__( 'Logo first, text on the right', 'nexvio' ),
				'column'      => esc_html__( 'Text first, logo below', 'nexvio' ),
			),
			'selectors' => array( '{{WRAPPER}} .nexvio-shw__pay' => 'flex-direction:{{VALUE}};' ),
			'condition' => array( 'show_pay' => 'yes', 'pay_source' => 'manual' ),
		) );
		$this->end_controls_section();

		/* ============ STYLE: PRODUCT VIEW ============ */
		$this->start_controls_section( 'style_stage', array( 'label' => esc_html__( 'Product view', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->sld( 'img_w', esc_html__( 'Image column width (2-column layouts)', 'nexvio' ), '.nexvio-shw', '--shw-img-w', 40, 15, 80, true, array( '%' ) );
		$this->sld( 'stage_gap', esc_html__( 'Space between image and info', 'nexvio' ), '.nexvio-shw', '--shw-gap', 48, 0, 160 );
		$this->sld( 'list_gap_v', esc_html__( 'Space between product view and list', 'nexvio' ), '.nexvio-shw', '--shw-block-gap', 36, 0, 160 );
		$this->sld( 'info_gap', esc_html__( 'Space between info elements', 'nexvio' ), '.nexvio-shw', '--shw-info-gap', 20, 0, 80 );
		$this->sld( 'price_col_w', esc_html__( 'Price column width (price beside payments)', 'nexvio' ), '.nexvio-shw', '--shw-price-w', 170, 80, 400 );
		$this->add_responsive_control( 'info_valign', array(
			'label'     => esc_html__( 'Vertical alignment', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'start',
			'options'   => array(
				'start'  => esc_html__( 'Top', 'nexvio' ),
				'center' => esc_html__( 'Middle', 'nexvio' ),
				'end'    => esc_html__( 'Bottom', 'nexvio' ),
			),
			'selectors' => array( '{{WRAPPER}} .nexvio-shw__panel' => 'align-items:{{VALUE}};' ),
		) );
		$this->col( 'stage_bg', esc_html__( 'Background', 'nexvio' ), '.nexvio-shw__panel', 'background-color' );
		$this->dim( 'stage_pad', esc_html__( 'Padding', 'nexvio' ), '.nexvio-shw__panel' );
		$this->rad( 'stage_radius', esc_html__( 'Corner radius', 'nexvio' ), '.nexvio-shw__panel', 0 );
		$this->end_controls_section();

		/* ============ STYLE: IMAGE ============ */
		$this->start_controls_section( 'style_image', array( 'label' => esc_html__( 'Image', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_responsive_control( 'img_ratio', array(
			'label'     => esc_html__( 'Shape', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => '1 / 1',
			'options'   => array(
				'1 / 1'  => esc_html__( 'Square', 'nexvio' ),
				'4 / 3'  => '4 : 3',
				'3 / 4'  => '3 : 4',
				'16 / 9' => '16 : 9',
				'auto'   => esc_html__( 'Original ratio', 'nexvio' ),
			),
			'selectors' => array( '{{WRAPPER}} .nexvio-shw' => '--shw-ratio:{{VALUE}};' ),
		) );
		$this->add_control( 'img_fit', array(
			'label'     => esc_html__( 'Fit', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'contain',
			'options'   => array(
				'contain' => esc_html__( 'Whole image visible', 'nexvio' ),
				'cover'   => esc_html__( 'Fill the frame (crop)', 'nexvio' ),
			),
			'selectors' => array( '{{WRAPPER}} .nexvio-shw__media img' => 'object-fit:{{VALUE}};' ),
		) );
		$this->col( 'img_bg', esc_html__( 'Background', 'nexvio' ), '.nexvio-shw__media', 'background-color', '#ffffff' );
		$this->dim( 'img_pad', esc_html__( 'Padding', 'nexvio' ), '.nexvio-shw__media' );
		$this->rad( 'img_radius', esc_html__( 'Corner radius', 'nexvio' ), '.nexvio-shw__media', 16 );
		$this->sld( 'img_border_w', esc_html__( 'Border width', 'nexvio' ), '.nexvio-shw__media', 'border-width', 0, 0, 12, false );
		$this->col( 'img_border_c', esc_html__( 'Border colour', 'nexvio' ), '.nexvio-shw__media', 'border-color', '#2a2a2a' );
		$this->sld( 'img_maxw', esc_html__( 'Max image width', 'nexvio' ), '.nexvio-shw__media', 'max-width', null, 100, 1200, true, array( 'px', '%' ) );
		$this->end_controls_section();

		/* ============ STYLE: TITLE ============ */
		$this->start_controls_section( 'style_title', array( 'label' => esc_html__( 'Title', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE, 'condition' => array( 'show_title' => 'yes' ) ) );
		$this->col( 'title_c', esc_html__( 'Colour', 'nexvio' ), '.nexvio-shw__title, {{WRAPPER}} .nexvio-shw__title a', 'color' );
		$this->col( 'title_c_h', esc_html__( 'Colour on hover (link)', 'nexvio' ), '.nexvio-shw__title a:hover', 'color', '', array( 'link_title' => 'yes' ) );
		$this->typo( 'title_typo', '.nexvio-shw__title' );
		$this->add_responsive_control( 'title_align', array(
			'label'     => esc_html__( 'Alignment', 'nexvio' ),
			'type'      => Controls_Manager::CHOOSE,
			'options'   => array(
				'left'   => array( 'title' => esc_html__( 'Left', 'nexvio' ), 'icon' => 'eicon-text-align-left' ),
				'center' => array( 'title' => esc_html__( 'Center', 'nexvio' ), 'icon' => 'eicon-text-align-center' ),
				'right'  => array( 'title' => esc_html__( 'Right', 'nexvio' ), 'icon' => 'eicon-text-align-right' ),
			),
			'selectors' => array( '{{WRAPPER}} .nexvio-shw__info' => 'text-align:{{VALUE}};' ),
		) );
		$this->end_controls_section();

		/* ============ STYLE: DESCRIPTION ============ */
		$this->start_controls_section( 'style_desc', array( 'label' => esc_html__( 'Description', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE, 'condition' => array( 'show_desc' => 'yes' ) ) );
		$this->col( 'desc_c', esc_html__( 'Colour', 'nexvio' ), '.nexvio-shw__desc', 'color' );
		$this->col( 'desc_link', esc_html__( 'Link colour', 'nexvio' ), '.nexvio-shw__desc a', 'color' );
		$this->typo( 'desc_typo', '.nexvio-shw__desc, {{WRAPPER}} .nexvio-shw__desc p' );
		$this->end_controls_section();

		/* ============ STYLE: PRICE ============ */
		$this->start_controls_section( 'style_price', array( 'label' => esc_html__( 'Price', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE, 'condition' => array( 'show_price' => 'yes' ) ) );
		$this->col( 'price_c', esc_html__( 'Colour', 'nexvio' ), '.nexvio-shw__price, {{WRAPPER}} .nexvio-shw__price .amount', 'color' );
		$this->col( 'price_old_c', esc_html__( 'Old price (sale) colour', 'nexvio' ), '.nexvio-shw__price del, {{WRAPPER}} .nexvio-shw__price del .amount', 'color', '#8f9aab' );
		$this->col( 'price_new_c', esc_html__( 'Sale price colour', 'nexvio' ), '.nexvio-shw__price ins, {{WRAPPER}} .nexvio-shw__price ins .amount', 'color' );
		$this->typo( 'price_typo', '.nexvio-shw__price, {{WRAPPER}} .nexvio-shw__price .amount' );
		$this->end_controls_section();

		/* ============ STYLE: STOCK ============ */
		$this->start_controls_section( 'style_stock', array( 'label' => esc_html__( 'Stock countdown', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE, 'condition' => array( 'show_stock' => 'yes' ) ) );
		$this->col( 'stock_ok', esc_html__( 'In stock colour', 'nexvio' ), '.nexvio-shw', '--shw-ok', '#3ccf91' );
		$this->col( 'stock_low_c', esc_html__( 'Low stock / backorder colour', 'nexvio' ), '.nexvio-shw', '--shw-low', '#e8b949' );
		$this->col( 'stock_out_c', esc_html__( 'Sold out colour', 'nexvio' ), '.nexvio-shw', '--shw-out', '#ff6371' );
		$this->typo( 'stock_typo', '.nexvio-shw__stock-text' );
		$this->col( 'bar_track', esc_html__( 'Bar background', 'nexvio' ), '.nexvio-shw__bar', 'background-color', '', array( 'stock_bar' => 'yes' ) );
		$this->sld( 'bar_h', esc_html__( 'Bar height', 'nexvio' ), '.nexvio-shw__bar', 'height', 6, 2, 24, false, array( 'px' ), array( 'stock_bar' => 'yes' ) );
		$this->sld( 'bar_w', esc_html__( 'Bar max width', 'nexvio' ), '.nexvio-shw__stock', 'max-width', null, 60, 600, true, array( 'px', '%' ), array( 'stock_bar' => 'yes' ) );
		$this->add_control( 'bar_pulse', array(
			'label'        => esc_html__( 'Pulse the bar when stock is low', 'nexvio' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
			'condition'    => array( 'stock_bar' => 'yes' ),
		) );
		$this->end_controls_section();

		/* ============ STYLE: PAYMENT OPTIONS ============ */
		$this->start_controls_section( 'style_pay', array( 'label' => esc_html__( 'Payment options', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE, 'condition' => array( 'show_pay' => 'yes' ) ) );
		$this->add_control( 'pay_style_note', array(
			'type'            => Controls_Manager::RAW_HTML,
			'raw'             => esc_html__( 'With "Tabby and Tamara plugins" the cards are drawn by the plugins, so only "Space between boxes" and "Max width of the payment column" apply. The other settings are for manual boxes.', 'nexvio' ),
			'content_classes' => 'elementor-descriptor',
		) );
		$this->col( 'pay_bg_all', esc_html__( 'Box background', 'nexvio' ), '.nexvio-shw__pay', 'background-color', '#ffffff' );
		$this->col( 'pay_c_all', esc_html__( 'Text colour', 'nexvio' ), '.nexvio-shw__pay', 'color', '#1d2330' );
		$this->col( 'pay_link_c', esc_html__( 'Link colour', 'nexvio' ), '.nexvio-shw__pay-link', 'color', '#3d6fd6' );
		$this->col( 'pay_border_c', esc_html__( 'Border colour', 'nexvio' ), '.nexvio-shw__pay', 'border-color', '#e3e6ec' );
		$this->sld( 'pay_border_w', esc_html__( 'Border width', 'nexvio' ), '.nexvio-shw__pay', 'border-width', 1, 0, 8, false );
		$this->rad( 'pay_radius', esc_html__( 'Corner radius', 'nexvio' ), '.nexvio-shw__pay', 8 );
		$this->dim( 'pay_pad', esc_html__( 'Padding', 'nexvio' ), '.nexvio-shw__pay' );
		$this->sld( 'pay_gap', esc_html__( 'Space between boxes', 'nexvio' ), '.nexvio-shw__pays', 'gap', 14, 0, 60 );
		$this->sld( 'pay_inner_gap', esc_html__( 'Space between text and logo', 'nexvio' ), '.nexvio-shw__pay', 'gap', 12, 0, 60, false );
		$this->sld( 'pay_logo_h', esc_html__( 'Logo height', 'nexvio' ), '.nexvio-shw__pay-logo', 'height', 22, 10, 80, false );
		$this->sld( 'pay_maxw', esc_html__( 'Max width of the payment column', 'nexvio' ), '.nexvio-shw__side', 'max-width', null, 200, 900, true, array( 'px', '%' ) );
		$this->typo( 'pay_typo', '.nexvio-shw__pay-text', esc_html__( 'Text typography', 'nexvio' ) );
		$this->typo( 'pay_badge_typo', '.nexvio-shw__pay-badge', esc_html__( 'Badge typography', 'nexvio' ) );
		$this->end_controls_section();

		/* ============ STYLE: VARIATIONS ============ */
		$this->start_controls_section( 'style_vars', array( 'label' => esc_html__( 'Variations', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE, 'condition' => array( 'show_vars' => 'yes' ) ) );
		$vs = '.nexvio-shw__vars';
		$this->heading( 'h_v_general', esc_html__( 'General', 'nexvio' ) );
		$this->col( 'var_label_c', esc_html__( 'Name colour', 'nexvio' ), '.nexvio-shw__var-label', 'color', '', array( 'var_label_show' => 'yes' ) );
		$this->typo( 'var_label_typo', '.nexvio-shw__var-label', esc_html__( 'Name typography', 'nexvio' ) );
		$this->sld( 'var_label_w', esc_html__( 'Name minimum width', 'nexvio' ), $vs, '--shw-v-labelw', 0, 0, 300, true, array( 'px' ), array( 'var_label_show' => 'yes' ) );
		$this->sld( 'var_label_gap', esc_html__( 'Space between name and options', 'nexvio' ), $vs, '--shw-v-labelgap', 12, 0, 60, true, array( 'px' ), array( 'var_label_show' => 'yes' ) );
		$this->sld( 'var_gap', esc_html__( 'Space between attributes', 'nexvio' ), $vs, '--shw-v-gap', 14, 0, 60 );
		$this->sld( 'var_opt_gap', esc_html__( 'Space between options', 'nexvio' ), $vs, '--shw-v-optgap', 8, 0, 40 );
		$this->add_responsive_control( 'var_align', array(
			'label'     => esc_html__( 'Options alignment', 'nexvio' ),
			'type'      => Controls_Manager::CHOOSE,
			'options'   => array(
				'flex-start' => array( 'title' => esc_html__( 'Left', 'nexvio' ), 'icon' => 'eicon-text-align-left' ),
				'center'     => array( 'title' => esc_html__( 'Center', 'nexvio' ), 'icon' => 'eicon-text-align-center' ),
				'flex-end'   => array( 'title' => esc_html__( 'Right', 'nexvio' ), 'icon' => 'eicon-text-align-right' ),
			),
			'selectors' => array( '{{WRAPPER}} .nexvio-shw__var-opts' => 'justify-content:{{VALUE}};' ),
		) );
		$this->col( 'var_missing_c', esc_html__( 'Colour of the name when nothing is chosen (after clicking Add to cart)', 'nexvio' ), $vs, '--shw-v-missing', '' );
		$this->add_control( 'var_unav_op', array(
			'label'      => esc_html__( 'Opacity of unavailable options', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'range'      => array( 'px' => array( 'min' => 0.1, 'max' => 1, 'step' => 0.05 ) ),
			'default'    => array( 'size' => 0.35 ),
			'selectors'  => array( '{{WRAPPER}} .nexvio-shw__vars' => '--shw-v-unav:{{SIZE}};' ),
			'condition'  => array( 'var_unavail' => 'dim' ),
		) );

		/* dropdown */
		$dd = array( 'var_type' => 'dropdown' );
		$this->heading( 'h_v_dd', esc_html__( 'Dropdown', 'nexvio' ) );
		$this->col( 'var_dd_bg', esc_html__( 'Background', 'nexvio' ), $vs, '--shw-v-ddbg', '', $dd );
		$this->col( 'var_dd_c', esc_html__( 'Text colour', 'nexvio' ), $vs, '--shw-v-ddc', '', $dd );
		$this->col( 'var_dd_bc', esc_html__( 'Border colour', 'nexvio' ), $vs, '--shw-v-ddbc', '', $dd );
		$this->sld( 'var_dd_w', esc_html__( 'Width', 'nexvio' ), $vs, '--shw-v-ddw', null, 100, 600, true, array( 'px', '%' ), $dd );
		$this->sld( 'var_dd_h', esc_html__( 'Height', 'nexvio' ), $vs, '--shw-v-ddh', 40, 28, 90, true, array( 'px' ), $dd );
		$this->sld( 'var_dd_r', esc_html__( 'Corner radius', 'nexvio' ), $vs, '--shw-v-ddr', 8, 0, 100, false, array( 'px' ), $dd );

		/* buttons + checkbox */
		$bt = array( 'var_type' => array( 'buttons', 'swatches', 'checkbox' ) );
		$bb = array( 'var_type' => array( 'buttons', 'swatches' ) );
		$this->heading( 'h_v_btn', esc_html__( 'Options', 'nexvio' ) );
		$this->typo( 'var_opt_typo', '.nexvio-shw__opt, {{WRAPPER}} .nexvio-shw__select', esc_html__( 'Typography', 'nexvio' ) );
		$this->sld( 'var_btn_h', esc_html__( 'Button minimum height', 'nexvio' ), $vs, '--shw-v-bh', 38, 20, 90, true, array( 'px' ), $bb );
		$this->add_responsive_control( 'var_btn_pad', array(
			'label'      => esc_html__( 'Button padding', 'nexvio' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em' ),
			'selectors'  => array( '{{WRAPPER}} .nexvio-shw__vars' => '--shw-v-pad:{{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			'condition'  => $bb,
		) );
		$this->sld( 'var_btn_bw', esc_html__( 'Border width', 'nexvio' ), $vs, '--shw-v-bw', 1, 0, 8, false, array( 'px' ), $bb );
		$this->sld( 'var_btn_r', esc_html__( 'Corner radius', 'nexvio' ), $vs, '--shw-v-br', 8, 0, 100, false, array( 'px' ), $bb );
		$this->start_controls_tabs( 'var_btn_tabs', array( 'condition' => $bb ) );
		$this->start_controls_tab( 'var_btn_n', array( 'label' => esc_html__( 'Normal', 'nexvio' ) ) );
		$this->col( 'var_bg', esc_html__( 'Background', 'nexvio' ), $vs, '--shw-v-bg', '', $bb );
		$this->col( 'var_c', esc_html__( 'Text', 'nexvio' ), $vs, '--shw-v-c', '', $bb );
		$this->col( 'var_bc', esc_html__( 'Border', 'nexvio' ), $vs, '--shw-v-bc', '', $bb );
		$this->end_controls_tab();
		$this->start_controls_tab( 'var_btn_h', array( 'label' => esc_html__( 'Hover', 'nexvio' ) ) );
		$this->col( 'var_bg_h', esc_html__( 'Background', 'nexvio' ), $vs, '--shw-v-bg-h', '', $bb );
		$this->col( 'var_c_h', esc_html__( 'Text', 'nexvio' ), $vs, '--shw-v-c-h', '', $bb );
		$this->col( 'var_bc_h', esc_html__( 'Border', 'nexvio' ), $vs, '--shw-v-bc-h', '', $bb );
		$this->end_controls_tab();
		$this->start_controls_tab( 'var_btn_a', array( 'label' => esc_html__( 'Selected', 'nexvio' ) ) );
		$this->col( 'var_bg_a', esc_html__( 'Background', 'nexvio' ), $vs, '--shw-v-bg-a', '', $bb );
		$this->col( 'var_c_a', esc_html__( 'Text', 'nexvio' ), $vs, '--shw-v-c-a', '', $bb );
		$this->col( 'var_bc_a', esc_html__( 'Border', 'nexvio' ), $vs, '--shw-v-bc-a', '', $bb );
		$this->end_controls_tab();
		$this->end_controls_tabs();

		/* swatches */
		$sw = array( 'var_type' => 'swatches' );
		$this->heading( 'h_v_sw', esc_html__( 'Swatches', 'nexvio' ) );
		$this->sld( 'var_sw_size', esc_html__( 'Swatch size', 'nexvio' ), $vs, '--shw-v-swsize', 32, 16, 100, true, array( 'px' ), $sw );
		$this->sld( 'var_sw_r', esc_html__( 'Swatch corner radius', 'nexvio' ), $vs, '--shw-v-swr', 999, 0, 999, false, array( 'px' ), $sw );
		$this->col( 'var_sw_bc', esc_html__( 'Swatch border colour', 'nexvio' ), $vs, '--shw-v-swbc', '', $sw );
		$this->col( 'var_sw_ring', esc_html__( 'Selected ring colour', 'nexvio' ), $vs, '--shw-v-swring', '', $sw );

		/* checkbox */
		$cb = array( 'var_type' => 'checkbox' );
		$this->heading( 'h_v_cb', esc_html__( 'Checkbox', 'nexvio' ) );
		$this->sld( 'var_cb_size', esc_html__( 'Box size', 'nexvio' ), $vs, '--shw-v-cbs', 18, 12, 40, false, array( 'px' ), $cb );
		$this->sld( 'var_cb_r', esc_html__( 'Box corner radius', 'nexvio' ), $vs, '--shw-v-cbr', 4, 0, 20, false, array( 'px' ), $cb );
		$this->col( 'var_cb_bc', esc_html__( 'Box border colour', 'nexvio' ), $vs, '--shw-v-cbc', '', $cb );
		$this->col( 'var_cb_on', esc_html__( 'Box colour when ticked', 'nexvio' ), $vs, '--shw-v-cba', '', $cb );
		$this->col( 'var_cb_tick', esc_html__( 'Tick colour', 'nexvio' ), $vs, '--shw-v-cbt', '', $cb );
		$this->end_controls_section();

		/* ============ STYLE: QUANTITY ============ */
		$this->start_controls_section( 'style_qty', array( 'label' => esc_html__( 'Quantity box', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE, 'condition' => array( 'show_atc' => 'yes', 'show_qty' => 'yes' ) ) );
		$this->col( 'q_bg', esc_html__( 'Background', 'nexvio' ), '.nexvio-shw__atc', '--nexvio-atc-qbg', 'transparent' );
		$this->col( 'q_ibg', esc_html__( 'Number background', 'nexvio' ), '.nexvio-shw__atc', '--nexvio-atc-ibg', 'transparent' );
		$this->col( 'q_border', esc_html__( 'Border', 'nexvio' ), '.nexvio-shw__atc', '--nexvio-atc-qborder' );
		$this->col( 'q_color', esc_html__( 'Number colour', 'nexvio' ), '.nexvio-shw__atc', '--nexvio-atc-qcolor' );
		$this->col( 'q_step', esc_html__( '− / + colour', 'nexvio' ), '.nexvio-shw__atc', '--nexvio-atc-step', '', array( 'qty_style' => 'stepper' ) );
		$this->col( 'q_step_h', esc_html__( '− / + hover background', 'nexvio' ), '.nexvio-shw__atc', '--nexvio-atc-step-h', '', array( 'qty_style' => 'stepper' ) );
		$this->sld( 'q_w', esc_html__( 'Number width', 'nexvio' ), '.nexvio-shw__atc', '--nexvio-atc-iw', 38, 24, 140, false );
		$this->sld( 'q_step_w', esc_html__( '− / + width', 'nexvio' ), '.nexvio-shw__atc', '--nexvio-atc-stepw', 36, 24, 80, false, array( 'px' ), array( 'qty_style' => 'stepper' ) );
		$this->sld( 'q_radius', esc_html__( 'Corner radius', 'nexvio' ), '.nexvio-shw__atc', '--nexvio-atc-qradius', 2, 0, 40, false );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'q_typo', 'selector' => '{{WRAPPER}} .nexvio-shw__atc .nexvio-atc__input' ) );
		$this->end_controls_section();

		/* ============ STYLE: BUTTON ============ */
		$this->start_controls_section( 'style_btn', array( 'label' => esc_html__( 'Add to cart button', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE, 'condition' => array( 'show_atc' => 'yes' ) ) );
		$this->sld( 'b_h', esc_html__( 'Height (button and quantity)', 'nexvio' ), '.nexvio-shw__atc', '--nexvio-atc-h', 40, 28, 90 );
		$this->sld( 'b_gap', esc_html__( 'Space between quantity and button', 'nexvio' ), '.nexvio-shw__atc', '--nexvio-atc-gap', 12, 0, 40 );
		$this->add_control( 'b_full', array(
			'label'        => esc_html__( 'Button fills the width', 'nexvio' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => '',
		) );
		$this->col( 'b_bg', esc_html__( 'Background', 'nexvio' ), '.nexvio-shw__atc', '--nexvio-atc-bg', '#0a6df0' );
		$this->col( 'b_bg_h', esc_html__( 'Background on hover', 'nexvio' ), '.nexvio-shw__atc', '--nexvio-atc-bg-h', '#2d86ff' );
		$this->col( 'b_tc', esc_html__( 'Text', 'nexvio' ), '.nexvio-shw__atc', '--nexvio-atc-tc', '#ffffff' );
		$this->col( 'b_tc_h', esc_html__( 'Text on hover', 'nexvio' ), '.nexvio-shw__atc', '--nexvio-atc-tc-h', '#ffffff' );
		$this->col( 'b_added', esc_html__( 'Background after adding', 'nexvio' ), '.nexvio-shw__atc', '--nexvio-atc-ok', '#2fae78' );
		$this->col( 'b_dis', esc_html__( 'Sold-out background', 'nexvio' ), '.nexvio-shw__atc', '--nexvio-atc-dis-bg', '#424b58' );
		$this->col( 'b_dis_tc', esc_html__( 'Sold-out text', 'nexvio' ), '.nexvio-shw__atc', '--nexvio-atc-dis-tc', '#aab3c0' );
		$this->col( 'b_icon_c', esc_html__( 'Icon colour', 'nexvio' ), '.nexvio-shw__atc .nexvio-atc__icon, {{WRAPPER}} .nexvio-shw__atc .nexvio-atc__icon svg', 'color' );
		$this->sld( 'b_radius', esc_html__( 'Corner radius', 'nexvio' ), '.nexvio-shw__atc', '--nexvio-atc-bradius', 999, 0, 999, false );
		$this->dim( 'b_pad', esc_html__( 'Padding', 'nexvio' ), '.nexvio-shw__atc .nexvio-atc__btn' );
		$this->typo( 'b_typo', '.nexvio-shw__atc .nexvio-atc__btn' );
		$this->col( 'b_msg', esc_html__( 'Message text colour', 'nexvio' ), '.nexvio-shw__atc', '--nexvio-atc-mcolor', '#8f9aab' );
		$this->col( 'b_msg_link', esc_html__( 'Message link colour', 'nexvio' ), '.nexvio-shw__atc', '--nexvio-atc-mlink', '#8cc8ff' );
		$this->end_controls_section();

		/* ============ STYLE: PRODUCT LIST ============ */
		$this->start_controls_section( 'style_list', array( 'label' => esc_html__( 'Product list', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->sld( 'list_gap', esc_html__( 'Space between items', 'nexvio' ), '.nexvio-shw', '--shw-list-gap', 14, 0, 60 );
		$this->add_responsive_control( 'list_align', array(
			'label'     => esc_html__( 'Alignment (Auto columns)', 'nexvio' ),
			'type'      => Controls_Manager::CHOOSE,
			'options'   => array(
				'flex-start' => array( 'title' => esc_html__( 'Left', 'nexvio' ), 'icon' => 'eicon-text-align-left' ),
				'center'     => array( 'title' => esc_html__( 'Center', 'nexvio' ), 'icon' => 'eicon-text-align-center' ),
				'flex-end'   => array( 'title' => esc_html__( 'Right', 'nexvio' ), 'icon' => 'eicon-text-align-right' ),
			),
			'selectors' => array( '{{WRAPPER}} .nexvio-shw__list' => 'justify-content:{{VALUE}};' ),
		) );
		$this->add_responsive_control( 'item_align', array(
			'label'     => esc_html__( 'Text alignment inside items', 'nexvio' ),
			'type'      => Controls_Manager::CHOOSE,
			'options'   => array(
				'flex-start' => array( 'title' => esc_html__( 'Left', 'nexvio' ), 'icon' => 'eicon-text-align-left' ),
				'center'     => array( 'title' => esc_html__( 'Center', 'nexvio' ), 'icon' => 'eicon-text-align-center' ),
				'flex-end'   => array( 'title' => esc_html__( 'Right', 'nexvio' ), 'icon' => 'eicon-text-align-right' ),
			),
			'default'   => 'center',
			'selectors' => array( '{{WRAPPER}} .nexvio-shw__item' => 'justify-content:{{VALUE}};' ),
		) );
		$this->switcher( 'item_thumb', esc_html__( 'Small image in each item', 'nexvio' ), '' );
		$this->sld( 'item_thumb_s', esc_html__( 'Small image size', 'nexvio' ), '.nexvio-shw__item-img', 'width', 32, 16, 120, false, array( 'px' ), array( 'item_thumb' => 'yes' ) );
		$this->rad( 'item_thumb_r', esc_html__( 'Small image radius', 'nexvio' ), '.nexvio-shw__item-img', 6, array( 'item_thumb' => 'yes' ) );
		$this->sld( 'item_minh', esc_html__( 'Item min height', 'nexvio' ), '.nexvio-shw__item', 'min-height', 48, 24, 160 );
		$this->dim( 'item_pad', esc_html__( 'Item padding', 'nexvio' ), '.nexvio-shw__item' );
		$this->rad( 'item_radius', esc_html__( 'Corner radius', 'nexvio' ), '.nexvio-shw__item', 10 );
		$this->sld( 'item_bw', esc_html__( 'Border width', 'nexvio' ), '.nexvio-shw__item', 'border-width', 1, 0, 8, false );
		$this->typo( 'item_typo', '.nexvio-shw__item' );

		$this->start_controls_tabs( 'item_tabs' );

		$this->start_controls_tab( 'item_tab_n', array( 'label' => esc_html__( 'Normal', 'nexvio' ) ) );
		$this->col( 'item_bg', esc_html__( 'Background', 'nexvio' ), '.nexvio-shw__item', 'background-color', '#050505' );
		$this->col( 'item_c', esc_html__( 'Text', 'nexvio' ), '.nexvio-shw__item', 'color', '#ffffff' );
		$this->col( 'item_bc', esc_html__( 'Border', 'nexvio' ), '.nexvio-shw__item', 'border-color', '#262626' );
		$this->end_controls_tab();

		$this->start_controls_tab( 'item_tab_h', array( 'label' => esc_html__( 'Hover', 'nexvio' ) ) );
		$this->col( 'item_bg_h', esc_html__( 'Background', 'nexvio' ), '.nexvio-shw__item:hover:not(.is-active)', 'background-color', '#111111' );
		$this->col( 'item_c_h', esc_html__( 'Text', 'nexvio' ), '.nexvio-shw__item:hover:not(.is-active)', 'color', '#ffffff' );
		$this->col( 'item_bc_h', esc_html__( 'Border', 'nexvio' ), '.nexvio-shw__item:hover:not(.is-active)', 'border-color', '#555555' );
		$this->end_controls_tab();

		$this->start_controls_tab( 'item_tab_a', array( 'label' => esc_html__( 'Selected', 'nexvio' ) ) );
		$this->col( 'item_bg_a', esc_html__( 'Background', 'nexvio' ), '.nexvio-shw__item.is-active', 'background-color', '#050505' );
		$this->col( 'item_c_a', esc_html__( 'Text', 'nexvio' ), '.nexvio-shw__item.is-active', 'color', '#ffffff' );
		$this->col( 'item_bc_a', esc_html__( 'Border', 'nexvio' ), '.nexvio-shw__item.is-active', 'border-color', '#ffffff' );
		$this->end_controls_tab();

		$this->end_controls_tabs();
		$this->end_controls_section();
	}

	/* ------------------------------------------------------------------
	 * Helpers
	 * ------------------------------------------------------------------ */

	private function is_edit() {
		return class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode();
	}

	private function redirect_url( $s ) {
		if ( 'cart' === $s['redirect'] || ( 'wc' === $s['redirect'] && 'yes' === get_option( 'woocommerce_cart_redirect_after_add' ) ) ) {
			return wc_get_cart_url();
		}
		return '';
	}

	private function side_pref( $s ) {
		return isset( $s['side_cart'] ) && in_array( $s['side_cart'], array( 'open', 'no' ), true ) ? $s['side_cart'] : 'global';
	}

	/**
	 * Elementor saves the whole rendered page into post_content. For a product built with Elementor the "full
	 * description" is therefore the entire page (hero, sections, even this showcase again), not a product text.
	 */
	private function is_builder_content( $product, $text ) {
		if ( 'builder' === get_post_meta( $product->get_id(), '_elementor_edit_mode', true ) ) {
			return true;
		}
		return false !== strpos( $text, 'data-elementor-type' ) || false !== strpos( $text, 'class="elementor' ) || false !== strpos( $text, 'elementor-element' );
	}

	/** Remove any copy of this widget that ended up inside a saved description. */
	private function strip_nested_showcase( $html ) {
		if ( false === strpos( $html, 'nexvio-' ) || ! class_exists( 'DOMDocument' ) ) {
			return $html;
		}
		$prev = libxml_use_internal_errors( true );
		$doc  = new DOMDocument();
		$doc->loadHTML( '<?xml encoding="utf-8" ?><div id="nxshw-clean">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		$xp = new DOMXPath( $doc );
		foreach ( $xp->query( '//*[contains(@class,"nexvio-shw") or contains(@class,"nexvio-ps") or contains(@class,"nexvio-atc")]' ) as $node ) {
			if ( $node->parentNode ) {
				$node->parentNode->removeChild( $node );
			}
		}
		$root = $doc->getElementById( 'nxshw-clean' );
		$out  = '';
		if ( $root ) {
			foreach ( $root->childNodes as $child ) {
				$out .= $doc->saveHTML( $child );
			}
		}
		libxml_clear_errors();
		libxml_use_internal_errors( $prev );
		return '' === $out ? $html : $out;
	}

	private function description_html( $product, $s ) {
		$text = 'full' === $s['desc_source'] ? $product->get_description() : $product->get_short_description();
		if ( 'short' === $s['desc_source'] && 'yes' === $s['desc_fallback'] && '' === trim( wp_strip_all_tags( $text ) ) ) {
			$text = $product->get_description();
		}
		$text = (string) $text;
		if ( $text === $product->get_description() && '' !== $text && $this->is_builder_content( $product, $text ) ) {
			$text = (string) $product->get_short_description();
		}
		$text = strip_shortcodes( $this->strip_nested_showcase( $text ) );
		if ( '' === trim( wp_strip_all_tags( $text ) ) ) {
			return '';
		}
		$words = max( 0, (int) $s['desc_words'] );
		if ( $words > 0 ) {
			return '<p>' . esc_html( wp_trim_words( wp_strip_all_tags( $text ), $words ) ) . '</p>';
		}
		return wp_kses_post( wpautop( $text ) );
	}

	private function stock_html( $product, $s ) {
		if ( $product->is_on_backorder() ) {
			return '<div class="nexvio-shw__stock is-pre"><span class="nexvio-shw__stock-text">' . esc_html( $s['txt_back'] ) . '</span></div>';
		}
		if ( ! $product->is_in_stock() ) {
			return '<div class="nexvio-shw__stock is-out"><span class="nexvio-shw__stock-text">' . esc_html( $s['txt_out'] ) . '</span></div>';
		}

		$qty   = $product->managing_stock() ? (int) $product->get_stock_quantity() : null;
		$below = max( 0, (int) $s['stock_below'] );
		$low   = max( 0, (int) $s['stock_low'] );

		if ( null !== $qty && $qty > 0 && ( 0 === $below || $qty <= $below ) ) {
			$is_low = $low > 0 && $qty <= $low;
			$text   = str_replace( '{n}', (string) $qty, (string) $s['txt_count'] );
			$bar    = '';
			if ( 'yes' === $s['stock_bar'] ) {
				$max = max( 1, (int) $s['stock_bar_max'] );
				$pct = max( 2, min( 100, round( $qty / $max * 100 ) ) );
				$bar = '<span class="nexvio-shw__bar"><span class="nexvio-shw__bar-fill" style="width:' . esc_attr( $pct ) . '%"></span></span>';
			}
			return '<div class="nexvio-shw__stock is-count' . ( $is_low ? ' is-low' : '' ) . '"><span class="nexvio-shw__stock-text">' . esc_html( $text ) . '</span>' . $bar . '</div>';
		}

		return '<div class="nexvio-shw__stock"><span class="nexvio-shw__stock-text">' . esc_html( $s['txt_in'] ) . '</span></div>';
	}

	/** Tabby box: an empty holder, tabby-promo.js draws the card (same data as on the product page). */
	private function tabby_box( $product, $holder_id ) {
		if ( ! self::tabby_active() || 'no' === get_option( 'tabby_promo' ) ) {
			return '';
		}
		if ( ! WC_Tabby_Config::isAvailableForCurrency() || WC_Tabby_Config::isDisabledForSKU( $product->get_sku() ) ) {
			return '';
		}
		$price = (float) wc_get_price_to_display( $product );
		$max   = (int) WC_Tabby_Promo::getMaxPrice();
		$min   = (int) WC_Tabby_Promo::getMinTotal();
		if ( $price <= 0 || ( $max > 0 && $price > $max ) || ( $min > 0 && $price < $min ) ) {
			return '';
		}
		return '<div id="' . esc_attr( $holder_id ) . '" class="nexvio-shw__ext nexvio-shw__ext--tabby" data-tabby-price="' . esc_attr( number_format( $price, 2, '.', '' ) ) . '" data-tabby-currency="' . esc_attr( WC_Tabby_Promo::getCurrency() ) . '"></div>';
	}

	/** Tamara box: the plugin's own [tamara_show_popup] output (a <tamara-widget> with the product price). */
	private function tamara_box( $product ) {
		if ( ! self::tamara_active() || (float) wc_get_price_to_display( $product ) <= 0 ) {
			return '';
		}
		/* the shortcode reads the global product (price, excluded products and categories) */
		$prev = isset( $GLOBALS['product'] ) ? $GLOBALS['product'] : null;
		$GLOBALS['product'] = $product; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- WooCommerce's own global, restored right below.
		$html = do_shortcode( '[tamara_show_popup]' );
		$GLOBALS['product'] = $prev; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- WooCommerce's own global, restored.
		$html = is_string( $html ) ? trim( $html ) : '';
		return '' === $html ? '' : '<div class="nexvio-shw__ext nexvio-shw__ext--tamara">' . $html . '</div>';
	}

	private function plugin_payments_html( $product, $s, $holder_id ) {
		$boxes = array();
		if ( 'yes' === $s['pay_tabby'] ) {
			$boxes['tabby'] = $this->tabby_box( $product, $holder_id );
		}
		if ( 'yes' === $s['pay_tamara'] ) {
			$boxes['tamara'] = $this->tamara_box( $product );
		}
		if ( 'tamara_first' === $s['pay_order'] ) {
			$boxes = array_reverse( $boxes, true );
		}
		$out = implode( '', array_filter( $boxes ) );
		if ( '' === $out && $this->is_edit() ) {
			return '<div class="nexvio-shw__pays"><p class="nexvio-shw__empty">' . esc_html__( 'No Tabby / Tamara box here: the plugin is not active, switched off, or this price is outside its limits.', 'nexvio' ) . '</p></div>';
		}
		return '' === $out ? '' : '<div class="nexvio-shw__pays">' . $out . '</div>';
	}

	private function payments_html( $product, $s, $holder_id = '' ) {
		if ( 'yes' !== $s['show_pay'] ) {
			return '';
		}
		if ( 'manual' !== $s['pay_source'] ) {
			return $this->plugin_payments_html( $product, $s, $holder_id );
		}
		if ( empty( $s['pay_items'] ) || ! is_array( $s['pay_items'] ) ) {
			return '';
		}
		$price = (float) wc_get_price_to_display( $product );
		if ( $price <= 0 ) {
			return '';
		}
		$allowed = array(
			'b'      => array(),
			'strong' => array(),
			'em'     => array(),
			'i'      => array(),
			'br'     => array(),
			's'      => array(),
			'span'   => array( 'class' => array() ),
		);
		$out = '';
		foreach ( $s['pay_items'] as $item ) {
			if ( empty( $item['pay_on'] ) || 'yes' !== $item['pay_on'] ) {
				continue;
			}
			if ( isset( $item['pay_min'] ) && '' !== $item['pay_min'] && $price < (float) $item['pay_min'] ) {
				continue;
			}
			if ( isset( $item['pay_max'] ) && '' !== $item['pay_max'] && $price > (float) $item['pay_max'] ) {
				continue;
			}
			$parts = max( 1, (int) ( isset( $item['pay_parts'] ) ? $item['pay_parts'] : 4 ) );
			$text  = wp_kses( (string) $item['pay_text'], $allowed );
			$text  = str_replace(
				array( '{price}', '{part}', '{parts}' ),
				array( wc_price( $price ), wc_price( $price / $parts ), (string) $parts ),
				$text
			);

			$link = '';
			if ( ! empty( $item['pay_link_text'] ) ) {
				$url = isset( $item['pay_link']['url'] ) ? (string) $item['pay_link']['url'] : '';
				if ( '' !== $url ) {
					$rel   = array();
					$blank = ! empty( $item['pay_link']['is_external'] );
					if ( $blank ) {
						$rel[] = 'noopener';
					}
					if ( ! empty( $item['pay_link']['nofollow'] ) ) {
						$rel[] = 'nofollow';
					}
					$link = ' <a class="nexvio-shw__pay-link" href="' . esc_url( $url ) . '"' . ( $blank ? ' target="_blank"' : '' ) . ( $rel ? ' rel="' . esc_attr( implode( ' ', $rel ) ) . '"' : '' ) . '>' . esc_html( $item['pay_link_text'] ) . '</a>';
				} else {
					$link = ' <span class="nexvio-shw__pay-link">' . esc_html( $item['pay_link_text'] ) . '</span>';
				}
			}

			$logo = '';
			if ( ! empty( $item['pay_logo']['url'] ) ) {
				$logo = '<img class="nexvio-shw__pay-logo" src="' . esc_url( $item['pay_logo']['url'] ) . '" alt="' . esc_attr( isset( $item['pay_badge'] ) ? $item['pay_badge'] : '' ) . '" loading="lazy">';
			} elseif ( ! empty( $item['pay_badge'] ) ) {
				$logo = '<span class="nexvio-shw__pay-badge">' . esc_html( $item['pay_badge'] ) . '</span>';
			}

			$out .= '<div class="nexvio-shw__pay elementor-repeater-item-' . esc_attr( $item['_id'] ) . '"><div class="nexvio-shw__pay-text">' . $text . $link . '</div>' . $logo . '</div>';
		}
		return '' === $out ? '' : '<div class="nexvio-shw__pays">' . $out . '</div>';
	}

	/* ------------------------------------------------------------------
	 * Variations (colour, size…)
	 * ------------------------------------------------------------------ */

	/** Price markup of one variation (sale price aware). */
	private function variation_price_html( $v ) {
		$price   = isset( $v['display_price'] ) ? (float) $v['display_price'] : 0;
		$regular = isset( $v['display_regular_price'] ) ? (float) $v['display_regular_price'] : $price;
		if ( $regular > $price ) {
			return '<del aria-hidden="true">' . wc_price( $regular ) . '</del> <ins>' . wc_price( $price ) . '</ins>';
		}
		return wc_price( $price );
	}

	/**
	 * The variation selector of one variable product (empty string when it is off or not possible).
	 * Every attribute gets one group; the chosen values are kept in hidden inputs linked to the Add to cart form,
	 * so the form also works without JavaScript.
	 */
	private function variations_block( $product, $s, $form_id, $size ) {
		if ( 'yes' !== $s['show_vars'] || ! $product->is_type( 'variable' ) ) {
			return '';
		}
		$attributes = $product->get_variation_attributes();
		$available  = $product->get_available_variations();
		if ( ! $attributes || ! $available || ! is_array( $available ) ) {
			return '';
		}

		$type = in_array( $s['var_type'], array( 'dropdown', 'buttons', 'swatches', 'checkbox' ), true ) ? $s['var_type'] : 'dropdown';

		/* compact data for the browser: id, attribute values, image, price, stock, quantity limits */
		$data        = array();
		$first_attrs = array();
		foreach ( $available as $v ) {
			$img_id = ! empty( $v['image_id'] ) ? (int) $v['image_id'] : 0;
			$img    = null;
			if ( $img_id ) {
				$src = wp_get_attachment_image_url( $img_id, $size );
				if ( $src ) {
					$img = array(
						'src'    => $src,
						'srcset' => (string) wp_get_attachment_image_srcset( $img_id, $size ),
						'sizes'  => (string) wp_get_attachment_image_sizes( $img_id, $size ),
					);
				}
			}
			$in_stock = ! empty( $v['is_in_stock'] ) && ! empty( $v['is_purchasable'] );
			$attrs    = isset( $v['attributes'] ) && is_array( $v['attributes'] ) ? $v['attributes'] : array();
			if ( $in_stock && ! $first_attrs ) {
				$first_attrs = $attrs;
			}
			$data[] = array(
				'id'    => (int) $v['variation_id'],
				'attrs' => $attrs,
				'img'   => $img,
				'price' => $this->variation_price_html( $v ),
				'stock' => $in_stock ? 1 : 0,
				'min'   => isset( $v['min_qty'] ) && is_numeric( $v['min_qty'] ) ? (int) $v['min_qty'] : 1,
				'max'   => isset( $v['max_qty'] ) && is_numeric( $v['max_qty'] ) ? (int) $v['max_qty'] : 0,
			);
		}

		$defaults = $product->get_default_attributes();
		$show_lab = 'yes' === $s['var_label_show'];
		$groups   = '';

		foreach ( $attributes as $name => $options ) {
			$slug = sanitize_title( $name );
			$key  = 'attribute_' . $slug;

			/* the values of this attribute: [ value, label, term id ] */
			$opts = array();
			if ( taxonomy_exists( $name ) ) {
				$terms = wc_get_product_terms( $product->get_id(), $name, array( 'fields' => 'all' ) );
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
			$sel   = $found;
			$gid   = $form_id . '-' . $slug;
			$label = wc_attribute_label( $name, $product );
			$out   = '';

			if ( 'dropdown' === $type ) {
				$out .= '<select class="nexvio-shw__select" id="' . esc_attr( $gid ) . '" aria-label="' . esc_attr( $label ) . '">';
				$out .= '<option value="">' . esc_html( $s['txt_choose'] ) . '</option>';
				foreach ( $opts as $o ) {
					$out .= '<option value="' . esc_attr( $o['value'] ) . '"' . ( $sel === $o['value'] ? ' selected' : '' ) . '>' . esc_html( $o['label'] ) . '</option>';
				}
				$out .= '</select>';
			} else {
				foreach ( $opts as $n => $o ) {
					$on = ( '' !== $sel && $sel === $o['value'] );
					if ( 'checkbox' === $type ) {
						$out .= '<label class="nexvio-shw__opt nexvio-shw__opt--check' . ( $on ? ' is-selected' : '' ) . '" data-value="' . esc_attr( $o['value'] ) . '">'
							. '<input type="radio" name="' . esc_attr( $gid . '-r' ) . '" value="' . esc_attr( $o['value'] ) . '"' . ( $on ? ' checked' : '' ) . '>'
							. '<span class="nexvio-shw__box" aria-hidden="true"></span><span class="nexvio-shw__opt-text">' . esc_html( $o['label'] ) . '</span></label>';
						continue;
					}
					if ( 'swatches' === $type ) {
						$color = Nexvio_Swatches::color_for( $o['label'], $o['term'] );
						$pic   = $o['term'] ? Nexvio_Swatches::image_for( $o['term'] ) : '';
						if ( $color || $pic ) {
							$out .= '<button type="button" class="nexvio-shw__opt nexvio-shw__opt--sw' . ( $pic ? ' is-image' : '' ) . ( $on ? ' is-selected' : '' ) . '" data-value="' . esc_attr( $o['value'] ) . '" title="' . esc_attr( $o['label'] ) . '" aria-label="' . esc_attr( $o['label'] ) . '" aria-pressed="' . ( $on ? 'true' : 'false' ) . '" style="--sw:' . esc_attr( $color ? $color : '#999999' ) . ( $pic ? ';--sw-img:url(' . esc_url( $pic ) . ')' : '' ) . '"></button>';
							continue;
						}
					}
					$out .= '<button type="button" class="nexvio-shw__opt nexvio-shw__opt--btn' . ( $on ? ' is-selected' : '' ) . '" data-value="' . esc_attr( $o['value'] ) . '" aria-pressed="' . ( $on ? 'true' : 'false' ) . '">' . esc_html( $o['label'] ) . '</button>';
				}
			}

			$lab = '';
			if ( $show_lab ) {
				$lab = 'dropdown' === $type
					? '<label class="nexvio-shw__var-label" for="' . esc_attr( $gid ) . '">' . esc_html( $label ) . '</label>'
					: '<span class="nexvio-shw__var-label">' . esc_html( $label ) . '</span>';
			}

			$groups .= '<div class="nexvio-shw__var" role="group" data-attr="' . esc_attr( $key ) . '" data-value="' . esc_attr( $sel ) . '"' . ( $show_lab ? '' : ' aria-label="' . esc_attr( $label ) . '"' ) . '>'
				. $lab
				. '<div class="nexvio-shw__var-opts">' . $out . '</div>'
				. '<input type="hidden" class="nexvio-shw__var-input" name="' . esc_attr( $key ) . '" value="' . esc_attr( $sel ) . '" form="' . esc_attr( $form_id ) . '">'
				. '</div>';
		}

		if ( '' === $groups ) {
			return '';
		}

		return '<div class="nexvio-shw__vars nexvio-shw__vars--' . esc_attr( $type ) . '" data-form="' . esc_attr( $form_id ) . '"'
			. ' data-img="' . ( 'yes' === $s['var_img_swap'] ? '1' : '0' ) . '"'
			. ' data-price="' . ( 'yes' === $s['var_price_swap'] ? '1' : '0' ) . '"'
			. ' data-unav="' . ( 'hide' === $s['var_unavail'] ? 'hide' : 'dim' ) . '"'
			. ' data-variations="' . esc_attr( wp_json_encode( $data ) ) . '">' . $groups . '</div>';
	}

	private function render_buy( $product, $s, $form_id = '', $has_vars = false ) {
		$id        = $product->get_id();
		$available = $product->is_purchasable() && $product->is_in_stock();
		$variable  = $has_vars && $product->is_type( 'variable' );

		/* other types (and variable products without the variation selector): go to the product page */
		if ( ! $product->is_type( 'simple' ) && ! $variable ) {
			echo '<div class="nexvio-atc nexvio-shw__atc"><div class="nexvio-atc__form">';
			if ( $product->is_in_stock() ) {
				echo '<a class="nexvio-atc__btn" href="' . esc_url( $product->get_permalink() ) . '"><span class="nexvio-atc__label">' . esc_html( $s['txt_select'] ) . '</span></a>';
			} else {
				echo '<button type="button" class="nexvio-atc__btn is-disabled" disabled aria-disabled="true"><span class="nexvio-atc__label">' . esc_html( $s['txt_btn_out'] ) . '</span></button>';
			}
			echo '</div></div>';
			return;
		}

		$min      = max( 1, (int) $product->get_min_purchase_quantity() );
		$max      = (int) $product->get_max_purchase_quantity();
		$single   = $product->is_sold_individually() || ( $max > 0 && $max <= 1 );
		$show_qty = 'yes' === $s['show_qty'] && ! $single && $available;
		$label    = $available ? $s['txt_add'] : $s['txt_btn_out'];
		$texts    = array(
			'adding' => (string) $s['txt_adding'],
			'added'  => (string) $s['txt_added'],
			'error'  => (string) $s['txt_error'],
			'cart'   => 'yes' === $s['show_cart_link'] ? (string) $s['txt_cart'] : '',
			'label'  => (string) $label,
			'pick'   => isset( $s['txt_pick'] ) ? (string) $s['txt_pick'] : '',
			'unavail' => isset( $s['txt_unavail'] ) ? (string) $s['txt_unavail'] : '',
			'out'    => (string) $s['txt_btn_out'],
		);
		$stepper = 'stepper' === $s['qty_style'];
		?>
		<div class="nexvio-atc nexvio-shw__atc" data-nexvio-side="<?php echo esc_attr( $this->side_pref( $s ) ); ?>">
			<?php $nexvio_form_action = apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core hook, called on purpose for compatibility. ?>
			<form class="nexvio-atc__form"<?php echo $form_id ? ' id="' . esc_attr( $form_id ) . '"' : ''; ?> action="<?php echo esc_url( $nexvio_form_action ); ?>" method="post" enctype="multipart/form-data"
				data-product_id="<?php echo esc_attr( $id ); ?>"
				<?php echo $variable ? 'data-variable="1" data-avail="' . ( $available ? '1' : '0' ) . '"' : ''; ?>
				data-ajax="<?php echo 'yes' === $s['ajax'] && $available ? '1' : '0'; ?>"
				data-ajax-url="<?php echo esc_url( WC_AJAX::get_endpoint( $variable ? 'nexvio_shw_add' : 'add_to_cart' ) ); ?>"
				data-cart-url="<?php echo esc_url( wc_get_cart_url() ); ?>"
				data-redirect="<?php echo esc_url( $this->redirect_url( $s ) ); ?>"
				data-texts="<?php echo esc_attr( wp_json_encode( $texts ) ); ?>">

				<?php if ( $variable ) : ?>
					<input type="hidden" name="variation_id" value="0">
				<?php endif; ?>

				<?php if ( $show_qty && $stepper ) : ?>
					<div class="nexvio-atc__qty">
						<button type="button" class="nexvio-atc__step" data-step="-1" aria-label="<?php esc_attr_e( 'Decrease quantity', 'nexvio' ); ?>">&minus;</button>
						<input type="number" class="nexvio-atc__input qty" name="quantity" value="<?php echo esc_attr( $min ); ?>" min="<?php echo esc_attr( $min ); ?>" <?php echo $max > 0 ? 'max="' . esc_attr( $max ) . '"' : ''; ?> step="1" inputmode="numeric" autocomplete="off" aria-label="<?php esc_attr_e( 'Quantity', 'nexvio' ); ?>">
						<button type="button" class="nexvio-atc__step" data-step="1" aria-label="<?php esc_attr_e( 'Increase quantity', 'nexvio' ); ?>">+</button>
					</div>
				<?php elseif ( $show_qty ) : ?>
					<input type="number" class="nexvio-atc__input nexvio-shw__qtyin qty" name="quantity" value="<?php echo esc_attr( $min ); ?>" min="<?php echo esc_attr( $min ); ?>" <?php echo $max > 0 ? 'max="' . esc_attr( $max ) . '"' : ''; ?> step="1" inputmode="numeric" autocomplete="off" aria-label="<?php esc_attr_e( 'Quantity', 'nexvio' ); ?>">
				<?php else : ?>
					<input type="hidden" name="quantity" value="<?php echo esc_attr( $min ); ?>">
				<?php endif; ?>

				<?php if ( $available ) : ?>
					<button type="submit" name="add-to-cart" value="<?php echo esc_attr( $id ); ?>" class="nexvio-atc__btn">
						<?php if ( ! empty( $s['btn_icon']['value'] ) ) : ?>
							<span class="nexvio-atc__icon" aria-hidden="true"><?php \Elementor\Icons_Manager::render_icon( $s['btn_icon'], array( 'aria-hidden' => 'true' ) ); ?></span>
						<?php endif; ?>
						<span class="nexvio-atc__label"><?php echo esc_html( $label ); ?></span>
					</button>
				<?php else : ?>
					<button type="button" class="nexvio-atc__btn is-disabled" disabled aria-disabled="true">
						<span class="nexvio-atc__label"><?php echo esc_html( $label ); ?></span>
					</button>
				<?php endif; ?>
			</form>
			<p class="nexvio-atc__msg" aria-live="polite"></p>
		</div>
		<?php
	}

	/** The product being viewed (single product page), or a fixed one. */
	private function current_product( $s ) {
		$id = ! empty( $s['product_id'] ) ? (int) $s['product_id'] : 0;
		if ( ! $id && function_exists( 'is_singular' ) && is_singular( 'product' ) ) {
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
		if ( ! $id && ( $this->is_edit() || isset( $_GET['elementor-preview'] ) || isset( $_GET['elementor_library'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$latest = get_posts( array( 'post_type' => 'product', 'post_status' => 'publish', 'numberposts' => 1, 'fields' => 'ids' ) );
			$id     = $latest ? (int) $latest[0] : 0;
		}
		return $id ? wc_get_product( $id ) : null;
	}

	/** Current product first (on a product page), then the list products. */
	private function showcase_products( $s ) {
		$current = 'yes' === $s['use_current'] ? $this->current_product( $s ) : null;

		if ( $current instanceof WC_Product && 'same_cat' === $s['list_from'] ) {
			$slugs = wp_get_post_terms( $current->get_id(), 'product_cat', array( 'fields' => 'slugs' ) );
			if ( ! is_wp_error( $slugs ) && $slugs ) {
				$s['categories'] = $slugs;
				$s['tags']       = array();
				if ( 'manual' === $s['source'] ) {
					$s['source'] = 'recent';
				}
			}
		}

		$list = $this->get_products( $s );
		if ( ! $current instanceof WC_Product ) {
			return $list;
		}

		$others = array();
		foreach ( $list as $p ) {
			if ( $p instanceof WC_Product && $p->get_id() !== $current->get_id() ) {
				$others[] = $p;
			}
		}
		array_unshift( $others, $current );
		return $others;
	}

	/* ------------------------------------------------------------------
	 * Output
	 * ------------------------------------------------------------------ */

	protected function render() {
		$s        = $this->get_settings_for_display();
		$products = $this->showcase_products( $s );

		if ( ! $products ) {
			if ( $this->is_edit() ) {
				echo '<div class="nexvio-ps nexvio-shw"><p class="nexvio-shw__empty">' . esc_html__( 'No products found. Check the "Products (query)" settings.', 'nexvio' ) . '</p></div>';
			}
			return;
		}

		$uid    = 'nxshw-' . $this->get_id();
		$total  = count( $products );
		$active = min( $total, max( 1, (int) $s['default_index'] ) ) - 1;
		$size   = ! empty( $s['img_size'] ) ? $s['img_size'] : 'large';

		$classes = array( 'nexvio-ps', 'nexvio-shw', 'nexvio-shw--list-' . ( 'above' === $s['list_pos'] ? 'above' : 'below' ), 'nexvio-shw--anim-' . sanitize_key( $s['anim'] ) );
		if ( 'yes' === $s['bar_pulse'] ) {
			$classes[] = 'nexvio-shw--pulse';
		}
		if ( 'yes' === $s['item_thumb'] ) {
			$classes[] = 'nexvio-shw--thumbs';
		}
		if ( 'yes' === $s['b_full'] ) {
			$classes[] = 'nexvio-shw--btnfull';
		}

		$tabby_attr = '';
		if ( 'yes' === $s['show_pay'] && 'manual' !== $s['pay_source'] && 'yes' === $s['pay_tabby'] && self::tabby_script_handle() ) {
			$tabby_attr = wp_json_encode( WC_Tabby_Promo::getConfig() );
		}

		$tag = in_array( $s['title_tag'], array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'p' ), true ) ? $s['title_tag'] : 'h2';
		?>
		<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-trigger="<?php echo esc_attr( 'hover' === $s['trigger'] ? 'hover' : 'click' ); ?>"<?php echo $tabby_attr ? ' data-tabby="' . esc_attr( $tabby_attr ) . '"' : ''; ?>>

			<div class="nexvio-shw__stage">
				<?php
				foreach ( $products as $i => $product ) {
					if ( ! $product instanceof WC_Product ) {
						continue;
					}
					$is_active = ( $i === $active );
					$link      = $product->get_permalink();
					$img_id    = $product->get_image_id();
					if ( $img_id ) {
						$img = wp_get_attachment_image( $img_id, $size, false, array(
							'alt'      => $product->get_name(),
							'loading'  => $is_active ? 'eager' : 'lazy',
							'decoding' => 'async',
						) );
					} else {
						$img = wc_placeholder_img( $size );
					}
					$price_html = 'yes' === $s['show_price'] ? $product->get_price_html() : '';
					$desc       = 'yes' === $s['show_desc'] ? $this->description_html( $product, $s ) : '';
					$stock      = 'yes' === $s['show_stock'] ? $this->stock_html( $product, $s ) : '';
					$pays       = $this->payments_html( $product, $s, $uid . '-tabby' . $i );
					$form_id    = $uid . '-f' . $i;
					$var_html   = $this->variations_block( $product, $s, $form_id, $size );
					$var_price  = $var_html && 'price' === $s['var_pos'] ? $var_html : '';
					$var_side   = $var_html && 'price' !== $s['var_pos'] ? $var_html : '';
					?>
					<div class="nexvio-shw__panel<?php echo $is_active ? ' is-active' : ''; ?>" id="<?php echo esc_attr( $uid . '-p' . $i ); ?>" role="tabpanel" aria-labelledby="<?php echo esc_attr( $uid . '-t' . $i ); ?>" data-product="<?php echo esc_attr( $product->get_id() ); ?>"<?php echo $is_active ? '' : ' hidden'; ?>>

						<div class="nexvio-shw__media">
							<?php if ( 'yes' === $s['link_image'] ) : ?>
								<a href="<?php echo esc_url( $link ); ?>" class="nexvio-shw__media-link" aria-label="<?php echo esc_attr( $product->get_name() ); ?>"><?php echo wp_kses( $img, Nexvio_Plugin::allowed_html() ); ?></a>
							<?php else : ?>
								<?php echo wp_kses( $img, Nexvio_Plugin::allowed_html() ); ?>
							<?php endif; ?>
						</div>

						<div class="nexvio-shw__info">
							<?php if ( 'yes' === $s['show_title'] ) : ?>
								<<?php echo esc_attr( $tag ); ?> class="nexvio-shw__title">
									<?php if ( 'yes' === $s['link_title'] ) : ?>
										<a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $product->get_name() ); ?></a>
									<?php else : ?>
										<?php echo esc_html( $product->get_name() ); ?>
									<?php endif; ?>
								</<?php echo esc_attr( $tag ); ?>>
							<?php endif; ?>

							<?php if ( $desc ) : ?>
								<div class="nexvio-shw__desc"><?php echo wp_kses( $desc, Nexvio_Plugin::allowed_html() ); ?></div>
							<?php endif; ?>

							<?php if ( $price_html || $stock || $pays || $var_html || 'yes' === $s['show_atc'] ) : ?>
								<div class="nexvio-shw__buy">
									<?php if ( $price_html || $stock || $var_price ) : ?>
										<div class="nexvio-shw__pricebox">
											<?php if ( $price_html ) : ?>
												<div class="nexvio-shw__price"><?php echo wp_kses( $price_html, Nexvio_Plugin::allowed_html() ); ?></div>
											<?php endif; ?>
											<?php echo wp_kses( $stock, Nexvio_Plugin::allowed_html() ); ?>
											<?php echo wp_kses( $var_price, Nexvio_Plugin::allowed_html() ); ?>
										</div>
									<?php endif; ?>
									<?php if ( $pays || $var_side || 'yes' === $s['show_atc'] ) : ?>
										<div class="nexvio-shw__side">
											<?php echo wp_kses( $pays, Nexvio_Plugin::allowed_html() ); ?>
											<?php echo wp_kses( $var_side, Nexvio_Plugin::allowed_html() ); ?>
											<?php
											if ( 'yes' === $s['show_atc'] ) {
												$this->render_buy( $product, $s, $form_id, '' !== $var_html );
											}
											?>
										</div>
									<?php endif; ?>
								</div>
							<?php endif; ?>
						</div>
					</div>
					<?php
				}
				?>
			</div>

			<div class="nexvio-shw__list" role="tablist" aria-label="<?php esc_attr_e( 'Products', 'nexvio' ); ?>">
				<?php
				foreach ( $products as $i => $product ) {
					if ( ! $product instanceof WC_Product ) {
						continue;
					}
					$is_active = ( $i === $active );
					?>
					<button type="button" role="tab" class="nexvio-shw__item<?php echo $is_active ? ' is-active' : ''; ?>" id="<?php echo esc_attr( $uid . '-t' . $i ); ?>" aria-controls="<?php echo esc_attr( $uid . '-p' . $i ); ?>" aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>" tabindex="<?php echo $is_active ? '0' : '-1'; ?>" data-index="<?php echo esc_attr( $i ); ?>">
						<?php if ( 'yes' === $s['item_thumb'] ) : ?>
							<span class="nexvio-shw__item-img" aria-hidden="true">
								<?php
								$thumb_id = $product->get_image_id();
								echo wp_kses( $thumb_id ? wp_get_attachment_image( $thumb_id, 'thumbnail', false, array( 'alt' => '', 'loading' => 'lazy' ) ) : wc_placeholder_img( 'thumbnail' ), Nexvio_Plugin::allowed_html() );
								?>
							</span>
						<?php endif; ?>
						<span class="nexvio-shw__item-label"><?php echo esc_html( $product->get_name() ); ?></span>
					</button>
					<?php
				}
				?>
			</div>
		</div>
		<?php
	}
}
