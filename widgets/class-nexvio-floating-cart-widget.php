<?php
/**
 * Floating Cart - Elementor widget: a fixed cart button that opens the side cart.
 *
 * @package Nexvio_For_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Icons_Manager;

class Nexvio_Floating_Cart_Widget extends Widget_Base {

	use Nexvio_Card_Trait;

	public function get_name() {
		return 'nexvio_floating_cart';
	}

	public function get_title() {
		return esc_html__( 'Floating Cart', 'nexvio' );
	}

	public function get_icon() {
		return 'eicon-cart-solid';
	}

	public function get_categories() {
		return array( 'nexvio' );
	}

	public function get_keywords() {
		return array( 'nexvio', 'cart', 'floating', 'sticky', 'bag', 'side cart' );
	}

	public function get_style_depends() {
		return array( 'nexvio-side-cart' );
	}

	public function get_script_depends() {
		return array( 'nexvio-side-cart' );
	}

	private function c( $id, $label, $var, $default = '' ) {
		$this->add_control( $id, array(
			'label'     => $label,
			'type'      => Controls_Manager::COLOR,
			'default'   => $default,
			'selectors' => array( '{{WRAPPER}} .nexvio-fc' => $var . ': {{VALUE}};' ),
		) );
	}

	protected function register_controls() {
		$this->start_controls_section( 'section_fc', array( 'label' => esc_html__( 'Floating Cart', 'nexvio' ) ) );
		$this->switcher( 'enabled', esc_html__( 'Show the floating cart', 'nexvio' ) );
		$this->add_control( 'icon', array(
			'label'       => esc_html__( 'Icon', 'nexvio' ),
			'type'        => Controls_Manager::ICONS,
			'default'     => array( 'value' => '', 'library' => '' ),
			'skin'        => 'inline',
			'label_block' => false,
			'description' => esc_html__( 'Empty = shopping bag.', 'nexvio' ),
		) );
		$this->text( 'a11y', esc_html__( 'Screen reader text', 'nexvio' ), 'Open cart' );
		$this->switcher( 'show_count', esc_html__( 'Show item counter', 'nexvio' ), 'yes', array( 'separator' => 'before' ) );
		$this->switcher( 'hide_zero', esc_html__( 'Hide the counter when the cart is empty', 'nexvio' ), 'yes', array( 'condition' => array( 'show_count' => 'yes' ) ) );
		$this->add_control( 'count_side', array(
			'label'     => esc_html__( 'Counter position', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'right',
			'options'   => array( 'right' => esc_html__( 'Top right', 'nexvio' ), 'left' => esc_html__( 'Top left', 'nexvio' ) ),
			'condition' => array( 'show_count' => 'yes' ),
		) );
		$this->add_control( 'on_click', array(
			'label'   => esc_html__( 'On click', 'nexvio' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'global',
			'options' => array(
				'global' => esc_html__( 'Dashboard setting', 'nexvio' ),
				'open'   => esc_html__( 'Open the side cart', 'nexvio' ),
				'link'   => esc_html__( 'Go to the cart page', 'nexvio' ),
			),
		) );

		$this->add_control( 'h_pos', array( 'label' => esc_html__( 'Position', 'nexvio' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_control( 'side', array(
			'label'   => esc_html__( 'Side', 'nexvio' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'global',
			'options' => array( 'global' => esc_html__( 'Dashboard setting', 'nexvio' ), 'right' => esc_html__( 'Bottom right', 'nexvio' ), 'left' => esc_html__( 'Bottom left', 'nexvio' ) ),
		) );
		$this->add_responsive_control( 'off_x', array(
			'label'      => esc_html__( 'Distance from the side', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 200 ) ),
			'selectors'  => array( '{{WRAPPER}} .nexvio-fc' => '--wfc-x: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'off_y', array(
			'label'      => esc_html__( 'Distance from the bottom', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 300 ) ),
			'selectors'  => array( '{{WRAPPER}} .nexvio-fc' => '--wfc-y: {{SIZE}}{{UNIT}}; --wfc-y-m: {{SIZE}}{{UNIT}};' ),
		) );

		$this->add_control( 'h_dev', array( 'label' => esc_html__( 'Show on', 'nexvio' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_control( 'devices', array(
			'label'   => esc_html__( 'Devices', 'nexvio' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'global',
			'options' => array( 'global' => esc_html__( 'Dashboard setting', 'nexvio' ), 'custom' => esc_html__( 'Choose here', 'nexvio' ) ),
		) );
		$this->switcher( 'dev_desktop', esc_html__( 'Computers', 'nexvio' ), 'yes', array( 'condition' => array( 'devices' => 'custom' ) ) );
		$this->switcher( 'dev_tablet', esc_html__( 'Tablets', 'nexvio' ), 'yes', array( 'condition' => array( 'devices' => 'custom' ) ) );
		$this->switcher( 'dev_mobile', esc_html__( 'Phones', 'nexvio' ), 'yes', array( 'condition' => array( 'devices' => 'custom' ) ) );
		$this->end_controls_section();

		$this->start_controls_section( 'style_btn', array( 'label' => esc_html__( 'Button', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_responsive_control( 'size', array(
			'label'      => esc_html__( 'Button size', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 36, 'max' => 100 ) ),
			'selectors'  => array( '{{WRAPPER}} .nexvio-fc' => '--fc-size: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'icon_size', array(
			'label'      => esc_html__( 'Icon size', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 12, 'max' => 60 ) ),
			'selectors'  => array( '{{WRAPPER}} .nexvio-fc' => '--fc-icon: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_control( 'colour_note', array(
			'type'            => Controls_Manager::RAW_HTML,
			'raw'             => esc_html__( 'Colours follow Nexvio > Add to Cart > Look (Light or Dark + main colour), so every cart button matches.', 'nexvio' ),
			'content_classes' => 'elementor-descriptor',
		) );
		$this->end_controls_section();


	}

	protected function render() {
		$s = $this->get_settings_for_display();
		if ( 'yes' !== $s['enabled'] || ! Nexvio_Cart_Settings::on( 'floating_enabled' ) ) {
			return;
		}
		$icon = Nexvio_Side_Cart::icon( 'bag' );
		if ( ! empty( $s['icon']['value'] ) ) {
			ob_start();
			Icons_Manager::render_icon( $s['icon'], array( 'aria-hidden' => 'true' ) );
			$icon = ob_get_clean();
		}
		$opens = 'open' === $s['on_click'] || ( 'global' === $s['on_click'] && Nexvio_Cart_Settings::on( 'open_floating' ) );
		$args  = array(
			'icon_html'  => $icon,
			'label'      => $s['a11y'],
			'show_count' => 'yes' === $s['show_count'],
			'hide_zero'  => 'yes' === $s['hide_zero'],
			'opens'      => $opens && Nexvio_Cart_Settings::on( 'enabled' ),
			'classes'    => 'left' === $s['count_side'] ? 'nexvio-fc--count-left' : '',
		);
		if ( 'global' !== $s['side'] ) {
			$args['side'] = $s['side'];
		}
		if ( 'custom' === $s['devices'] ) {
			$args['devices'] = array(
				'desktop' => 'yes' === $s['dev_desktop'],
				'tablet'  => 'yes' === $s['dev_tablet'],
				'mobile'  => 'yes' === $s['dev_mobile'],
			);
		}
		Nexvio_Side_Cart::$floating_printed = true;
		echo wp_kses( Nexvio_Side_Cart::floating_html( $args ), Nexvio_Plugin::allowed_html() );
	}
}
