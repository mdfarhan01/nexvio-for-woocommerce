<?php
/**
 * NEXVIO Wishlist Button - Elementor widget with full control.
 * A heart (or star, bookmark, flag, gift, own icon) button that adds / removes the product
 * from the built-in wishlist. For the single product page, a fixed product, or any template.
 * Works with the same AJAX as the hearts on the product cards (product-slider.js).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Icons_Manager;

class Nexvio_Wishlist_Button_Widget extends Widget_Base {

	use Nexvio_Card_Trait;

	public function get_name() {
		return 'nexvio_wishlist_button';
	}

	public function get_title() {
		return esc_html__( 'Wishlist Button', 'nexvio' );
	}

	public function get_icon() {
		return 'eicon-heart';
	}

	public function get_categories() {
		return array( 'nexvio' );
	}

	public function get_keywords() {
		return array( 'nexvio', 'wishlist', 'favorites', 'heart', 'button', 'save', 'like', 'single product' );
	}

	public function get_style_depends() {
		return array( 'nexvio-layout', 'nexvio', 'nexvio-wishlist-button' );
	}

	public function get_script_depends() {
		return array( 'nexvio', 'nexvio-wishlist-widgets' );
	}

	/* ------------------------------------------------------------------
	 * Lists
	 * ------------------------------------------------------------------ */

	/** Designs: key => array( title, description ). Pictures: assets/img/layouts/wb-{key}.svg */
	public static function designs() {
		return array(
			'circle'  => array( __( 'Circle', 'nexvio' ), __( 'Round icon button.', 'nexvio' ) ),
			'square'  => array( __( 'Square', 'nexvio' ), __( 'Rounded square icon button.', 'nexvio' ) ),
			'pill'    => array( __( 'Pill with text', 'nexvio' ), __( 'Icon and text in a rounded button.', 'nexvio' ) ),
			'outline' => array( __( 'Outline with text', 'nexvio' ), __( 'Border only, fills when the product is saved.', 'nexvio' ) ),
			'stack'   => array( __( 'Icon above text', 'nexvio' ), __( 'Big icon with the text under it.', 'nexvio' ) ),
			'link'    => array( __( 'Text link', 'nexvio' ), __( 'Small icon and an underlined text, no box.', 'nexvio' ) ),
			'count'   => array( __( 'With counter', 'nexvio' ), __( 'Pill with the number of saved products.', 'nexvio' ) ),
		);
	}

	private function layout_options() {
		$out = array();
		foreach ( self::designs() as $key => $l ) {
			$img         = 'assets/img/layouts/wb-' . $key . '.svg';
			$out[ $key ] = array(
				'title' => $l[0],
				'desc'  => $l[1],
				'image' => NEXVIO_URL . $img . '?v=' . Nexvio_Plugin::ver( $img ),
			);
		}
		return $out;
	}

	private static function icon_svg( $name ) {
		$a = 'viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"';
		$i = array(
			'heart'    => '<svg ' . $a . '><path d="M12 20s-7-4.4-9-9a4.8 4.8 0 0 1 9-3 4.8 4.8 0 0 1 9 3c-2 4.6-9 9-9 9z"/></svg>',
			'star'     => '<svg ' . $a . '><path d="m12 3.5 2.6 5.4 5.9.8-4.3 4.1 1 5.9L12 16.9 6.8 19.7l1-5.9L3.5 9.7l5.9-.8L12 3.5z"/></svg>',
			'bookmark' => '<svg ' . $a . '><path d="M6.5 3.5h11v17L12 16.5l-5.5 4v-17z"/></svg>',
			'flag'     => '<svg ' . $a . '><path d="M5.5 21V4m0 1h12l-2.5 4 2.5 4h-12"/></svg>',
			'gift'     => '<svg ' . $a . '><path d="M4 9h16v4H4zM5 13h14v7H5zM12 9v11M12 9c-2.5 0-4.5-1-4.5-2.7S10 3.5 12 9zm0 0c2.5 0 4.5-1 4.5-2.7S14 3.5 12 9z"/></svg>',
		);
		return isset( $i[ $name ] ) ? $i[ $name ] : $i['heart'];
	}

	/* ------------------------------------------------------------------
	 * Controls
	 * ------------------------------------------------------------------ */

	protected function register_controls() {

		/* ============ PRODUCT ============ */
		$this->start_controls_section( 'section_product', array( 'label' => esc_html__( 'Product', 'nexvio' ) ) );
		$this->add_control( 'product_note', array(
			'type'            => Controls_Manager::RAW_HTML,
			'raw'             => esc_html__( 'Place this widget in the single product template. It uses the product being viewed.', 'nexvio' ),
			'content_classes' => 'elementor-descriptor',
		) );
		$this->add_control( 'product_id', array(
			'label'       => esc_html__( 'Fixed product ID (optional)', 'nexvio' ),
			'type'        => Controls_Manager::NUMBER,
			'min'         => 0,
			'default'     => '',
			'description' => esc_html__( 'Leave empty to use the current product. In the editor, empty = latest product.', 'nexvio' ),
		) );
		$this->end_controls_section();

		/* ============ DESIGN ============ */
		$this->start_controls_section( 'section_design', array( 'label' => esc_html__( 'Design', 'nexvio' ) ) );
		$has_lc = class_exists( 'Nexvio_Layout_Control' );
		$this->add_control( 'design', array(
			'label'       => esc_html__( 'Button design', 'nexvio' ),
			'type'        => $has_lc ? Nexvio_Layout_Control::TYPE : Controls_Manager::SELECT,
			'default'     => 'circle',
			'options'     => $has_lc ? $this->layout_options() : array_map( function ( $d ) { return $d[0]; }, self::designs() ),
			'description' => esc_html__( 'Click a picture to choose the design. Every colour, size and corner can be changed in the Style tab.', 'nexvio' ),
		) );
		$this->add_responsive_control( 'align', array(
			'label'     => esc_html__( 'Alignment', 'nexvio' ),
			'type'      => Controls_Manager::CHOOSE,
			'options'   => array(
				'flex-start' => array( 'title' => esc_html__( 'Left', 'nexvio' ), 'icon' => 'eicon-text-align-left' ),
				'center'     => array( 'title' => esc_html__( 'Center', 'nexvio' ), 'icon' => 'eicon-text-align-center' ),
				'flex-end'   => array( 'title' => esc_html__( 'Right', 'nexvio' ), 'icon' => 'eicon-text-align-right' ),
			),
			'selectors' => array( '{{WRAPPER}} .nexvio-wb' => 'justify-content: {{VALUE}};' ),
		) );
		$this->switcher( 'full', esc_html__( 'Button fills the width', 'nexvio' ), '' );
		$this->end_controls_section();

		/* ============ ICON ============ */
		$this->start_controls_section( 'section_icon', array( 'label' => esc_html__( 'Icon', 'nexvio' ) ) );
		$this->add_control( 'icon_src', array(
			'label'   => esc_html__( 'Icon', 'nexvio' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'heart',
			'options' => array(
				'heart'    => esc_html__( 'Heart', 'nexvio' ),
				'star'     => esc_html__( 'Star', 'nexvio' ),
				'bookmark' => esc_html__( 'Bookmark', 'nexvio' ),
				'flag'     => esc_html__( 'Flag', 'nexvio' ),
				'gift'     => esc_html__( 'Gift', 'nexvio' ),
				'custom'   => esc_html__( 'My own icon', 'nexvio' ),
				'none'     => esc_html__( 'No icon (text only)', 'nexvio' ),
			),
		) );
		$this->add_control( 'icon_off', array(
			'label'       => esc_html__( 'Icon (not saved)', 'nexvio' ),
			'type'        => Controls_Manager::ICONS,
			'default'     => array( 'value' => '', 'library' => '' ),
			'skin'        => 'inline',
			'label_block' => false,
			'condition'   => array( 'icon_src' => 'custom' ),
		) );
		$this->add_control( 'icon_on', array(
			'label'       => esc_html__( 'Icon (saved)', 'nexvio' ),
			'type'        => Controls_Manager::ICONS,
			'default'     => array( 'value' => '', 'library' => '' ),
			'skin'        => 'inline',
			'label_block' => false,
			'description' => esc_html__( 'Empty = the same icon, filled with colour.', 'nexvio' ),
			'condition'   => array( 'icon_src' => 'custom' ),
		) );
		$this->add_control( 'fill_on', array(
			'label'        => esc_html__( 'Fill the icon when saved', 'nexvio' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
			'condition'    => array( 'icon_src!' => array( 'none', 'custom' ) ),
		) );
		$this->add_control( 'anim', array(
			'label'     => esc_html__( 'Effect when added', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'pop',
			'options'   => array(
				'none'  => esc_html__( 'None', 'nexvio' ),
				'pop'   => esc_html__( 'Pop', 'nexvio' ),
				'beat'  => esc_html__( 'Heartbeat', 'nexvio' ),
				'burst' => esc_html__( 'Ring burst', 'nexvio' ),
				'spin'  => esc_html__( 'Spin', 'nexvio' ),
			),
			'separator' => 'before',
		) );
		$this->end_controls_section();

		/* ============ TEXT & COUNT ============ */
		$this->start_controls_section( 'section_text', array( 'label' => esc_html__( 'Text and counter', 'nexvio' ) ) );
		$this->add_control( 'label_mode', array(
			'label'   => esc_html__( 'Text', 'nexvio' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'auto',
			'options' => array(
				'auto' => esc_html__( 'Follow the design', 'nexvio' ),
				'show' => esc_html__( 'Always show', 'nexvio' ),
				'hide' => esc_html__( 'Hide (icon only)', 'nexvio' ),
			),
		) );
		$this->text( 'label_off', esc_html__( 'Text (not saved)', 'nexvio' ), 'Add to wishlist', array( 'condition' => array( 'label_mode!' => 'hide' ) ) );
		$this->text( 'label_on', esc_html__( 'Text (saved)', 'nexvio' ), 'In your wishlist', array( 'condition' => array( 'label_mode!' => 'hide' ) ) );
		$this->add_control( 'label_pos', array(
			'label'     => esc_html__( 'Text position', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'auto',
			'options'   => array(
				'auto'   => esc_html__( 'Follow the design', 'nexvio' ),
				'right'  => esc_html__( 'Right of the icon', 'nexvio' ),
				'left'   => esc_html__( 'Left of the icon', 'nexvio' ),
				'bottom' => esc_html__( 'Under the icon', 'nexvio' ),
				'top'    => esc_html__( 'Above the icon', 'nexvio' ),
			),
			'condition' => array( 'label_mode!' => 'hide' ),
		) );
		$this->text( 'aria_label', esc_html__( 'Label (screen readers / tooltip)', 'nexvio' ), 'Add to wishlist' );
		$this->switcher( 'tooltip', esc_html__( 'Show a tooltip on hover', 'nexvio' ), '' );
		$this->add_control( 'show_count', array(
			'label'        => esc_html__( 'Show the counter', 'nexvio' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => '',
			'description'  => esc_html__( 'The number of products in the visitor\'s wishlist. Always on in the "With counter" design.', 'nexvio' ),
			'separator'    => 'before',
		) );
		$this->switcher( 'hide_zero', esc_html__( 'Hide the counter when empty', 'nexvio' ), 'yes' );
		$this->end_controls_section();

		/* ============ BEHAVIOUR & MESSAGES ============ */
		$this->start_controls_section( 'section_behaviour', array( 'label' => esc_html__( 'Behaviour and messages', 'nexvio' ) ) );
		$this->add_control( 'on_action', array(
			'label'   => esc_html__( 'Click when already saved', 'nexvio' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'toggle',
			'options' => array(
				'toggle' => esc_html__( 'Remove from the wishlist', 'nexvio' ),
				'page'   => esc_html__( 'Open the wishlist page', 'nexvio' ),
			),
		) );
		$this->switcher( 'wish_toast', esc_html__( 'Show message after click', 'nexvio' ) );
		$wc = array( 'wish_toast' => 'yes' );
		$this->text( 'wish_added', esc_html__( 'Added message', 'nexvio' ), 'Added to your wishlist', array( 'condition' => $wc ) );
		$this->text( 'wish_removed', esc_html__( 'Removed message', 'nexvio' ), 'Removed from your wishlist', array( 'condition' => $wc ) );
		$this->text( 'wish_view', esc_html__( '"View wishlist" link text', 'nexvio' ), 'View my wishlist', array( 'condition' => $wc ) );
		$this->text( 'wish_error', esc_html__( 'Error message', 'nexvio' ), 'Something went wrong, please try again.', array( 'condition' => $wc ) );
		$this->end_controls_section();

		/* ============ STYLE: BUTTON ============ */
		$this->start_controls_section( 'style_btn', array( 'label' => esc_html__( 'Button', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->wb_slider( 'width', esc_html__( 'Width', 'nexvio' ), '--nexvio-wb-w', array( 'px', '%' ), 20, 400 );
		$this->wb_slider( 'height', esc_html__( 'Height', 'nexvio' ), '--nexvio-wb-h', array( 'px' ), 20, 160 );
		$this->wb_slider( 'isize', esc_html__( 'Icon size', 'nexvio' ), '--nexvio-wb-is', array( 'px' ), 8, 80 );
		$this->wb_slider( 'gap', esc_html__( 'Space icon / text', 'nexvio' ), '--nexvio-wb-gap', array( 'px' ), 0, 40 );
		$this->add_responsive_control( 'pad', array(
			'label'      => esc_html__( 'Padding', 'nexvio' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em' ),
			'selectors'  => array( '{{WRAPPER}} .nexvio-wb' => '--nexvio-wb-pad: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'radius', array(
			'label'      => esc_html__( 'Corner radius', 'nexvio' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', '%' ),
			'selectors'  => array( '{{WRAPPER}} .nexvio-wb' => '--nexvio-wb-r: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );
		$this->wb_slider( 'bw', esc_html__( 'Border width', 'nexvio' ), '--nexvio-wb-bw', array( 'px' ), 0, 10 );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'typo', 'selector' => '{{WRAPPER}} .nexvio-wb__label' ) );

		$this->start_controls_tabs( 'tabs_state' );
		foreach ( array(
			'n' => array( esc_html__( 'Normal', 'nexvio' ), '', '' ),
			'h' => array( esc_html__( 'Hover', 'nexvio' ), '-h', ':hover' ),
			'a' => array( esc_html__( 'Saved', 'nexvio' ), '-a', '.is-on' ),
		) as $k => $st ) {
			$this->start_controls_tab( 'tab_' . $k, array( 'label' => $st[0] ) );
			$this->wb_color( 'c_' . $k, esc_html__( 'Icon colour', 'nexvio' ), '--nexvio-wb-c' . $st[1] );
			$this->wb_color( 't_' . $k, esc_html__( 'Text colour', 'nexvio' ), '--nexvio-wb-t' . $st[1] );
			$this->wb_color( 'bg_' . $k, esc_html__( 'Background', 'nexvio' ), '--nexvio-wb-bg' . $st[1] );
			$this->wb_color( 'bc_' . $k, esc_html__( 'Border colour', 'nexvio' ), '--nexvio-wb-bc' . $st[1] );
			$this->add_group_control( Group_Control_Box_Shadow::get_type(), array(
				'name'     => 'sh_' . $k,
				'selector' => '{{WRAPPER}} .nexvio-wb .nexvio-wb__btn' . $st[2],
			) );
			$this->end_controls_tab();
		}
		$this->end_controls_tabs();

		$this->add_control( 'speed', array(
			'label'     => esc_html__( 'Colour change speed (ms)', 'nexvio' ),
			'type'      => Controls_Manager::NUMBER,
			'min'       => 0,
			'max'       => 1500,
			'step'      => 10,
			'selectors' => array( '{{WRAPPER}} .nexvio-wb' => '--nexvio-wb-dur: {{VALUE}}ms;' ),
			'separator' => 'before',
		) );
		$this->end_controls_section();

		/* ============ STYLE: COUNTER ============ */
		$this->start_controls_section( 'style_count', array(
			'label'      => esc_html__( 'Counter', 'nexvio' ),
			'tab'        => Controls_Manager::TAB_STYLE,
		) );
		$this->wb_color( 'cn_bg', esc_html__( 'Background', 'nexvio' ), '--nexvio-wb-cbg' );
		$this->wb_color( 'cn_c', esc_html__( 'Text', 'nexvio' ), '--nexvio-wb-cc' );
		$this->wb_slider( 'cn_size', esc_html__( 'Size', 'nexvio' ), '--nexvio-wb-cs', array( 'px' ), 12, 40 );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'cn_typo', 'selector' => '{{WRAPPER}} .nexvio-wb__count' ) );
		$this->end_controls_section();

		/* ============ STYLE: TOOLTIP ============ */
		$this->start_controls_section( 'style_tip', array(
			'label'     => esc_html__( 'Tooltip', 'nexvio' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'tooltip' => 'yes' ),
		) );
		$this->wb_color( 'tip_bg', esc_html__( 'Background', 'nexvio' ), '--nexvio-wb-tipbg' );
		$this->wb_color( 'tip_c', esc_html__( 'Text', 'nexvio' ), '--nexvio-wb-tipc' );
		$this->end_controls_section();
	}

	private function wb_slider( $id, $label, $var, $units, $min, $max ) {
		$range = array();
		foreach ( $units as $u ) {
			$range[ $u ] = '%' === $u ? array( 'min' => 5, 'max' => 100 ) : array( 'min' => $min, 'max' => $max );
		}
		$this->add_responsive_control( $id, array(
			'label'      => $label,
			'type'       => Controls_Manager::SLIDER,
			'size_units' => $units,
			'range'      => $range,
			'selectors'  => array( '{{WRAPPER}} .nexvio-wb' => $var . ': {{SIZE}}{{UNIT}};' ),
		) );
	}

	private function wb_color( $id, $label, $var ) {
		$this->add_control( $id, array(
			'label'     => $label,
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .nexvio-wb' => $var . ': {{VALUE}};' ),
		) );
	}

	/* ------------------------------------------------------------------
	 * Helpers
	 * ------------------------------------------------------------------ */

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
		if ( ! $id ) {
			$is_edit = class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode();
			$preview = isset( $_GET['elementor-preview'] ) || isset( $_GET['elementor_library'] ); // phpcs:ignore WordPress.Security.NonceVerification
			if ( $is_edit || $preview || wp_doing_ajax() ) {
				$latest = get_posts( array( 'post_type' => 'product', 'post_status' => 'publish', 'numberposts' => 1, 'fields' => 'ids' ) );
				$id     = $latest ? (int) $latest[0] : 0;
			}
		}
		return $id && function_exists( 'wc_get_product' ) ? wc_get_product( $id ) : null;
	}

	private function icon_html( $s, $which ) {
		$src = isset( $s['icon_src'] ) ? $s['icon_src'] : 'heart';
		if ( 'none' === $src ) {
			return '';
		}
		if ( 'custom' === $src ) {
			$key = 'on' === $which ? 'icon_on' : 'icon_off';
			if ( empty( $s[ $key ]['value'] ) ) {
				return '';
			}
			ob_start();
			Icons_Manager::render_icon( $s[ $key ], array( 'aria-hidden' => 'true' ) );
			return ob_get_clean();
		}
		return self::icon_svg( $src );
	}

	/* ------------------------------------------------------------------
	 * Output
	 * ------------------------------------------------------------------ */

	protected function render() {
		$s       = $this->get_settings_for_display();
		$product = $this->current_product( $s );
		$is_edit = class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode();

		if ( ! Nexvio_Plugin::feature_enabled( 'wishlist' ) ) {
			if ( $is_edit ) {
				echo '<p class="nexvio-wb__note">' . esc_html__( 'The Wishlist feature is switched off in Nexvio > Dashboard.', 'nexvio' ) . '</p>';
			}
			return;
		}
		if ( ! $product instanceof WC_Product ) {
			if ( $is_edit ) {
				echo '<p class="nexvio-wb__note">' . esc_html__( 'No product found. Use this widget in the single product template, or set a fixed product ID.', 'nexvio' ) . '</p>';
			}
			return;
		}

		$id     = $product->get_id();
		$on     = Nexvio_Wishlist::has( $id );
		$design = isset( $s['design'] ) && isset( self::designs()[ $s['design'] ] ) ? $s['design'] : 'circle';
		$mode   = isset( $s['label_mode'] ) ? $s['label_mode'] : 'auto';
		$text_designs = array( 'pill', 'outline', 'stack', 'link', 'count' );
		$show_label   = 'show' === $mode || ( 'auto' === $mode && in_array( $design, $text_designs, true ) );
		$icon_off     = $this->icon_html( $s, 'off' );
		$icon_on      = 'custom' === $s['icon_src'] ? $this->icon_html( $s, 'on' ) : '';
		if ( '' === $icon_off && '' === $icon_on && 'none' !== $s['icon_src'] ) {
			$icon_off = self::icon_svg( 'heart' ); // "My own icon" chosen but empty
		}
		if ( '' === $icon_off && '' === $icon_on && ! $show_label ) {
			$show_label = true; // never an empty button
		}
		$show_count = 'yes' === $s['show_count'] || 'count' === $design;
		$anim       = in_array( $s['anim'], array( 'pop', 'beat', 'burst', 'spin' ), true ) ? $s['anim'] : 'none';
		$pos        = in_array( $s['label_pos'], array( 'left', 'right', 'top', 'bottom' ), true ) ? $s['label_pos'] : 'auto';
		$count      = Nexvio_Wishlist::count();
		$aria       = '' !== (string) $s['aria_label'] ? (string) $s['aria_label'] : (string) $s['label_off'];

		$classes = array( 'nexvio-ps', 'nexvio-wb', 'nexvio-wb--d-' . $design, 'nexvio-wb--anim-' . $anim );
		if ( 'auto' !== $pos ) {
			$classes[] = 'nexvio-wb--lp-' . $pos;
		}
		if ( 'yes' === $s['full'] ) {
			$classes[] = 'nexvio-wb--full';
		}
		if ( 'yes' === $s['fill_on'] && ! in_array( $s['icon_src'], array( 'custom', 'none' ), true ) ) {
			$classes[] = 'nexvio-wb--fill';
		}
		if ( 'custom' === $s['icon_src'] && '' !== $icon_on ) {
			$classes[] = 'nexvio-wb--two-icons';
		}

		$cfg = $this->wish_settings( $s );
		?>
		<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-settings="<?php echo esc_attr( wp_json_encode( array( 'wish' => $cfg ) ) ); ?>">
			<button type="button" class="nexvio-ps__wish nexvio-wb__btn<?php echo $on ? ' is-on' : ''; ?>" data-wish="1" data-id="<?php echo esc_attr( $id ); ?>"
				data-on-action="<?php echo esc_attr( 'page' === $s['on_action'] ? 'page' : 'toggle' ); ?>"
				aria-pressed="<?php echo $on ? 'true' : 'false'; ?>" aria-label="<?php echo esc_attr( $aria ); ?>"
				<?php echo 'yes' === $s['tooltip'] ? 'data-tip="' . esc_attr( $aria ) . '"' : ''; ?>>
				<?php if ( '' !== $icon_off || '' !== $icon_on ) : ?>
					<span class="nexvio-wb__icon" aria-hidden="true">
						<span class="nexvio-wb__i nexvio-wb__i--off"><?php echo wp_kses( '' !== $icon_off ? $icon_off : $icon_on, Nexvio_Plugin::allowed_html() ); ?></span>
						<?php if ( '' !== $icon_on ) : ?>
							<span class="nexvio-wb__i nexvio-wb__i--on"><?php echo wp_kses( $icon_on, Nexvio_Plugin::allowed_html() ); ?></span>
						<?php endif; ?>
					</span>
				<?php endif; ?>
				<?php if ( $show_label ) : ?>
					<span class="nexvio-wb__label">
						<span class="nexvio-wb__t nexvio-wb__t--off"><?php echo esc_html( $s['label_off'] ); ?></span>
						<span class="nexvio-wb__t nexvio-wb__t--on"><?php echo esc_html( '' !== (string) $s['label_on'] ? $s['label_on'] : $s['label_off'] ); ?></span>
					</span>
				<?php endif; ?>
				<?php if ( $show_count ) : ?>
					<span class="nexvio-wb__count nexvio-wishlist-count<?php echo ( 'yes' === $s['hide_zero'] && $count < 1 ) ? ' is-hidden' : ''; ?>" data-hide-zero="<?php echo 'yes' === $s['hide_zero'] ? '1' : '0'; ?>" aria-live="polite"><?php echo esc_html( $count ); ?></span>
				<?php endif; ?>
			</button>
		</div>
		<?php
	}
}
