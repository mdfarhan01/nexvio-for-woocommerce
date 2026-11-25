<?php
/**
 * NEXVIO simple card design (the Products Card look) as a trait.
 *
 * The Products Card widget and the widgets that share its design (Products Archive,
 * Related Products, Wishlist Card) register the same Content "Layout design" section
 * and the same Style sections from here, so the design is defined in one place.
 *
 * @package Nexvio_For_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Css_Filter;
use Elementor\Group_Control_Background;

trait Nexvio_Simple_Card_Trait {

	/** Controls of the shared card sections that the simple design does not use. */
	protected function remove_simple_card_controls() {
		foreach ( array( 'section_colors', 'colors_note', 'color_rules', 'image_mode' ) as $id ) {
			$this->remove_control( $id );
		}
	}

	/**
	 * Settings the shared card code reads but these widgets have no control for:
	 * the single "simple" design and the defaults of the options that were left out.
	 */
	protected function simple_card_settings( $s ) {
		$s['design_layout'] = 'simple';
		$defaults           = array(
			'image_mode'  => 'contain',
			'pattern'     => '',
			'frame'       => '',
			'img_shadow'  => '',
			'hover_fx'    => 'none',
			'color_rules' => array(),
		);
		foreach ( $defaults as $key => $value ) {
			if ( ! isset( $s[ $key ] ) ) {
				$s[ $key ] = $value;
			}
		}
		return $s;
	}

	/* ------------------------------------------------------------------
	 * Content tab: Layout design
	 * ------------------------------------------------------------------ */

	protected function register_pc_design_section() {
		$this->start_controls_section( 'section_design', array( 'label' => esc_html__( 'Layout design', 'nexvio' ) ) );
		$this->add_control( 'design_layout', array(
			'type'    => Controls_Manager::HIDDEN,
			'default' => 'simple',
		) );
		$this->add_control( 'design_note', array(
			'type'            => Controls_Manager::RAW_HTML,
			'raw'             => esc_html__( 'Simple card: the image on top, then the title, description, price and button. The card box (background, border, padding, shadow) is in Style > Layout design. What the card shows (stock, rating, badges, swatches, heart) is in the sections below.', 'nexvio' ),
			'content_classes' => 'elementor-descriptor',
		) );
		$this->add_control( 'btn_style', array(
			'label'     => esc_html__( 'Add-to-cart button', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'auto',
			'options'   => array(
				'auto' => esc_html__( 'Design default (text button)', 'nexvio' ),
				'icon' => esc_html__( 'Icon only', 'nexvio' ),
				'text' => esc_html__( 'Text button', 'nexvio' ),
			),
			'separator' => 'before',
		) );
		$this->text( 'btn_text', esc_html__( 'Button text', 'nexvio' ), 'Add to cart', array( 'condition' => array( 'btn_style!' => 'icon' ) ) );
		$this->add_control( 'btn_action', array(
			'label'       => esc_html__( 'When the button is clicked', 'nexvio' ),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'default',
			'options'     => array(
				'default' => esc_html__( 'Design default (products with options open the product page)', 'nexvio' ),
				'cart'    => esc_html__( 'Add to cart (do not open the product page)', 'nexvio' ),
			),
			'description' => esc_html__( 'Add to cart: variable products are added right from the card, with their default option (or the first one in stock). Products that cannot be added without a choice still open the product page.', 'nexvio' ),
			'separator'   => 'before',
		) );
		$this->add_responsive_control( 'content_align', array(
			'label'        => esc_html__( 'Content alignment', 'nexvio' ),
			'type'         => Controls_Manager::CHOOSE,
			'options'      => array(
				'left'    => array( 'title' => esc_html__( 'Left', 'nexvio' ), 'icon' => 'eicon-text-align-left' ),
				'center'  => array( 'title' => esc_html__( 'Center', 'nexvio' ), 'icon' => 'eicon-text-align-center' ),
				'right'   => array( 'title' => esc_html__( 'Right', 'nexvio' ), 'icon' => 'eicon-text-align-right' ),
				'justify' => array( 'title' => esc_html__( 'Justified', 'nexvio' ), 'icon' => 'eicon-text-align-justify' ),
			),
			'prefix_class' => 'nexvio-ca%s-',
			'separator'    => 'before',
			'description'  => esc_html__( 'Aligns the text, rating, colour swatches, price and button of the card. Leave empty to keep the design.', 'nexvio' ),
		) );
		$this->end_controls_section();
	}

	/* ------------------------------------------------------------------
	 * Style 1. Layout design: card background + border, text padding, box shadow
	 * ------------------------------------------------------------------ */

	protected function register_pc_layout_style_section() {
		$card = '{{WRAPPER}} .nexvio-ps .nexvio-ps__card';

		$this->start_controls_section( 'style_layout_design', array(
			'label' => esc_html__( 'Layout design', 'nexvio' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'gs_card_bg', array(
			'label'     => esc_html__( 'Card background', 'nexvio' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '',
			'selectors' => array( '{{WRAPPER}} .nexvio-ps' => '--nexvio-ps-card-bg: {{VALUE}}; --nexvio-l-card: {{VALUE}};' ),
		) );

		$this->add_group_control( Group_Control_Border::get_type(), array(
			'name'      => 'gs_card_border',
			'label'     => esc_html__( 'Border (top, right, bottom, left)', 'nexvio' ),
			'selector'  => $card,
			'separator' => 'before',
		) );

		$this->gs_box( 'gs_body_pad', esc_html__( 'Text area padding (top, right, bottom, left)', 'nexvio' ), $card . ' .nexvio-ps__body', 'padding', array( 'px', '%', 'em' ) );

		$this->gs_heading( 'gs_h_shadow', esc_html__( 'Box Shadow', 'nexvio' ) );
		$this->start_controls_tabs( 'gs_shadow_tabs' );
		$this->start_controls_tab( 'gs_shadow_normal', array( 'label' => esc_html__( 'Normal', 'nexvio' ) ) );
		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array(
			'name'     => 'gs_card_shadow',
			'selector' => $card,
		) );
		$this->end_controls_tab();
		$this->start_controls_tab( 'gs_shadow_hover', array( 'label' => esc_html__( 'Hover', 'nexvio' ) ) );
		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array(
			'name'     => 'gs_card_shadow_h',
			'selector' => $card . ':hover',
		) );
		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	/* ------------------------------------------------------------------
	 * Style 2. Image: fit (Default / Cover / Contain), image controls, image area
	 * ------------------------------------------------------------------ */

	protected function register_pc_image_section() {
		$img   = '{{WRAPPER}} .nexvio-ps .nexvio-ps__media .nexvio-ps__img';
		$img_h = '{{WRAPPER}} .nexvio-ps .nexvio-ps__card:hover .nexvio-ps__media .nexvio-ps__img';
		$ps    = '{{WRAPPER}} .nexvio-ps';

		$this->start_controls_section( 'style_image', array(
			'label' => esc_html__( 'Image', 'nexvio' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'l_fit', array(
			'label'     => esc_html__( 'Image fit', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => '',
			'options'   => array(
				''        => esc_html__( 'Default (design)', 'nexvio' ),
				'cover'   => esc_html__( 'Cover (fill, crop)', 'nexvio' ),
				'contain' => esc_html__( 'Contain (whole image)', 'nexvio' ),
			),
			'selectors' => array(
				$ps  => '--nexvio-l-fit: {{VALUE}};',
				$img => 'object-fit: {{VALUE}};',
			),
		) );

		$this->add_responsive_control( 'img_align', array(
			'label'                => esc_html__( 'Alignment', 'nexvio' ),
			'type'                 => Controls_Manager::CHOOSE,
			'options'              => array(
				'left'   => array( 'title' => esc_html__( 'Left', 'nexvio' ), 'icon' => 'eicon-text-align-left' ),
				'center' => array( 'title' => esc_html__( 'Center', 'nexvio' ), 'icon' => 'eicon-text-align-center' ),
				'right'  => array( 'title' => esc_html__( 'Right', 'nexvio' ), 'icon' => 'eicon-text-align-right' ),
			),
			'selectors_dictionary' => array(
				'left'   => 'margin-left:0;margin-right:auto;',
				'center' => 'margin-left:auto;margin-right:auto;',
				'right'  => 'margin-left:auto;margin-right:0;',
			),
			'selectors'            => array( $img => '{{VALUE}}' ),
			'separator'            => 'before',
		) );
		$this->add_responsive_control( 'img_width', array(
			'label'      => esc_html__( 'Width', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( '%', 'px', 'vw' ),
			'range'      => array( '%' => array( 'min' => 1, 'max' => 100 ), 'px' => array( 'min' => 1, 'max' => 1000 ), 'vw' => array( 'min' => 1, 'max' => 100 ) ),
			'selectors'  => array( $img => 'width: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'img_maxw', array(
			'label'      => esc_html__( 'Max Width', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( '%', 'px', 'vw' ),
			'range'      => array( '%' => array( 'min' => 1, 'max' => 100 ), 'px' => array( 'min' => 1, 'max' => 1000 ), 'vw' => array( 'min' => 1, 'max' => 100 ) ),
			'selectors'  => array( $img => 'max-width: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'img_height', array(
			'label'      => esc_html__( 'Height', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px', '%', 'vh' ),
			'range'      => array( 'px' => array( 'min' => 1, 'max' => 1000 ), '%' => array( 'min' => 1, 'max' => 100 ), 'vh' => array( 'min' => 1, 'max' => 100 ) ),
			'selectors'  => array( $img => 'height: {{SIZE}}{{UNIT}};' ),
		) );

		$this->start_controls_tabs( 'img_fx_tabs' );
		$this->start_controls_tab( 'img_fx_normal', array( 'label' => esc_html__( 'Normal', 'nexvio' ) ) );
		$this->add_control( 'img_opacity', array(
			'label'     => esc_html__( 'Opacity', 'nexvio' ),
			'type'      => Controls_Manager::SLIDER,
			'range'     => array( 'px' => array( 'max' => 1, 'min' => 0.05, 'step' => 0.01 ) ),
			'selectors' => array( $ps => '--nexvio-img-op: {{SIZE}};' ),
		) );
		$this->add_group_control( Group_Control_Css_Filter::get_type(), array(
			'name'     => 'img_filters',
			'selector' => $img,
		) );
		$this->end_controls_tab();
		$this->start_controls_tab( 'img_fx_hover', array( 'label' => esc_html__( 'Hover', 'nexvio' ) ) );
		$this->add_control( 'img_opacity_h', array(
			'label'     => esc_html__( 'Opacity', 'nexvio' ),
			'type'      => Controls_Manager::SLIDER,
			'range'     => array( 'px' => array( 'max' => 1, 'min' => 0.05, 'step' => 0.01 ) ),
			'selectors' => array( $ps => '--nexvio-img-op-h: {{SIZE}};' ),
		) );
		$this->add_group_control( Group_Control_Css_Filter::get_type(), array(
			'name'     => 'img_filters_h',
			'selector' => $img_h,
		) );
		$this->add_control( 'img_td', array(
			'label'     => esc_html__( 'Transition Duration (s)', 'nexvio' ),
			'type'      => Controls_Manager::SLIDER,
			'range'     => array( 'px' => array( 'min' => 0, 'max' => 3, 'step' => 0.1 ) ),
			'selectors' => array( $ps . ' .nexvio-ps__media .nexvio-ps__img:not(.nexvio-ps__alt)' => 'transition-duration: {{SIZE}}s;' ),
		) );
		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_group_control( Group_Control_Border::get_type(), array(
			'name'      => 'img_border',
			'selector'  => $img,
			'separator' => 'before',
		) );
		$this->add_responsive_control( 'img_br', array(
			'label'      => esc_html__( 'Border Radius', 'nexvio' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', '%', 'em', 'rem' ),
			'selectors'  => array( $img => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );
		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array(
			'name'     => 'img_bshadow',
			'selector' => $img,
		) );
		$this->add_control( 'img_note', array(
			'type'            => Controls_Manager::RAW_HTML,
			'raw'             => esc_html__( 'The image area clips what goes outside it. To see a border, radius or shadow around the image itself, make the image smaller than its area with Width or Max Width.', 'nexvio' ),
			'content_classes' => 'elementor-descriptor',
		) );

		/* image area: part of the Image section */
		$this->gs_heading( 'gs_h_area', esc_html__( 'Image area', 'nexvio' ) );
		$this->add_control( 'l_ratio', array(
			'label'     => esc_html__( 'Shape', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => '',
			'options'   => array( '' => esc_html__( 'Design default', 'nexvio' ), '1/1' => esc_html__( 'Square', 'nexvio' ), '4/5' => '4:5', '3/4' => '3:4', '63/88' => esc_html__( 'Card (63:88)', 'nexvio' ), '4/3' => '4:3', '16/9' => '16:9' ),
			'selectors' => array( $ps => '--nexvio-l-ratio: {{VALUE}};' ),
		) );
		$this->add_control( 'l_img_pad', array(
			'label'     => esc_html__( 'Space around the image (%)', 'nexvio' ),
			'type'      => Controls_Manager::NUMBER,
			'default'   => '',
			'min'       => 0,
			'max'       => 30,
			'selectors' => array( $ps => '--nexvio-l-img-pad: {{VALUE}}%;' ),
		) );
		$this->add_control( 'l_img_radius', array(
			'label'     => esc_html__( 'Image area corner radius (px)', 'nexvio' ),
			'type'      => Controls_Manager::NUMBER,
			'default'   => '',
			'min'       => 0,
			'max'       => 60,
			'selectors' => array( $ps => '--nexvio-l-img-radius: {{VALUE}}px;' ),
		) );
		$this->add_control( 'l_media', array(
			'label'     => esc_html__( 'Image area background', 'nexvio' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '',
			'selectors' => array( $ps => '--nexvio-l-media: {{VALUE}};' ),
		) );

		$this->end_controls_section();
	}

	/* ------------------------------------------------------------------
	 * Style 4. Button (shown only when the add-to-cart button is on)
	 * ------------------------------------------------------------------ */

	protected function register_pc_button_section() {
		$btn  = '{{WRAPPER}} .nexvio-ps .nexvio-ps__add';
		$on   = $btn . ':not(.is-disabled)';
		$on_h = $on . ':hover';

		$this->start_controls_section( 'style_button', array(
			'label'     => esc_html__( 'Button', 'nexvio' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'show_button' => 'yes' ),
		) );

		$this->gs_typo( 'gs_btn_typo', esc_html__( 'Font (family, size, weight…)', 'nexvio' ), $btn . ' .nexvio-ps__add-label' );
		$this->add_control( 'gs_btn_typo_note', array(
			'type'            => Controls_Manager::RAW_HTML,
			'raw'             => esc_html__( 'The font applies to the button text. It shows when Layout design > Add-to-cart button is a text button.', 'nexvio' ),
			'content_classes' => 'elementor-descriptor',
		) );

		$this->start_controls_tabs( 'gs_btn_tabs' );

		$this->start_controls_tab( 'gs_btn_normal', array( 'label' => esc_html__( 'Normal', 'nexvio' ) ) );
		$this->add_group_control( Group_Control_Background::get_type(), array(
			'name'           => 'btn_bg',
			'label'          => esc_html__( 'Background (colour or gradient)', 'nexvio' ),
			'types'          => array( 'classic', 'gradient' ),
			'selector'       => $on,
			'fields_options' => array(
				'background' => array( 'label' => esc_html__( 'Background type', 'nexvio' ) ),
			),
		) );
		$this->gs_color( 'gs_btn_text', esc_html__( 'Text colour', 'nexvio' ), array( $on => 'color: {{VALUE}};' ) );
		$this->gs_color( 'gs_btn_icon', esc_html__( 'Icon colour', 'nexvio' ), array( $on . ' svg' => 'color: {{VALUE}};' ) );
		$this->end_controls_tab();

		$this->start_controls_tab( 'gs_btn_hover', array( 'label' => esc_html__( 'Hover', 'nexvio' ) ) );
		$this->add_group_control( Group_Control_Background::get_type(), array(
			'name'           => 'btn_bg_h',
			'label'          => esc_html__( 'Background (colour or gradient)', 'nexvio' ),
			'types'          => array( 'classic', 'gradient' ),
			'selector'       => $on_h,
			'fields_options' => array(
				'background' => array( 'label' => esc_html__( 'Background type', 'nexvio' ) ),
			),
		) );
		$this->gs_color( 'gs_btn_text_h', esc_html__( 'Text colour', 'nexvio' ), array( $on_h => 'color: {{VALUE}};' ) );
		$this->gs_color( 'gs_btn_icon_h', esc_html__( 'Icon colour', 'nexvio' ), array( $on_h . ' svg' => 'color: {{VALUE}};' ) );
		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->gs_box( 'gs_btn_pad', esc_html__( 'Padding (top, right, bottom, left)', 'nexvio' ), $btn, 'padding', array( 'px', 'em', '%' ) );
		$this->gs_space( 'gs_btn_space', esc_html__( 'Spacing (top / bottom)', 'nexvio' ), $btn );
		$this->gs_box( 'gs_btn_radius', esc_html__( 'Border radius (top, right, bottom, left)', 'nexvio' ), $btn, 'border-radius', array( 'px', '%' ), array(), 'clip-path: none;' );

		$this->end_controls_section();
	}
}
