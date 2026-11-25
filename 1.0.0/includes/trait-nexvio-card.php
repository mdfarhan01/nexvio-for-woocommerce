<?php
/**
 * Shared card controls and rendering for the Nexvio widgets.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Repeater;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Css_Filter;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Text_Shadow;

trait Nexvio_Card_Trait {

	/* ------------------------------------------------------------------
	 * Helpers
	 * ------------------------------------------------------------------ */

	protected function term_options( $taxonomy ) {
		$options = array();
		$terms   = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) );
		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$options[ $term->slug ] = $term->name;
			}
		}
		return $options;
	}

	protected function color_var( $id, $label, $var, $default, $condition = array() ) {
		$args = array(
			'label'     => $label,
			'type'      => Controls_Manager::COLOR,
			'default'   => $default,
			'selectors' => array( '{{WRAPPER}} .nexvio-ps' => $var . ': {{VALUE}};' ),
		);
		if ( $condition ) {
			$args['condition'] = $condition;
		}
		$this->add_control( $id, $args );
	}

	protected function number_var( $id, $label, $var, $default, $min, $max, $step, $suffix = '', $condition = array() ) {
		$args = array(
			'label'     => $label,
			'type'      => Controls_Manager::NUMBER,
			'default'   => $default,
			'min'       => $min,
			'max'       => $max,
			'step'      => $step,
			'selectors' => array( '{{WRAPPER}} .nexvio-ps' => $var . ': {{VALUE}}' . $suffix . ';' ),
		);
		if ( $condition ) {
			$args['condition'] = $condition;
		}
		$this->add_control( $id, $args );
	}

	protected function switcher( $id, $label, $default = 'yes', $extra = array() ) {
		$this->add_control( $id, array_merge( array(
			'label'        => $label,
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => $default,
		), $extra ) );
	}

	protected function text( $id, $label, $default, $extra = array() ) {
		$this->add_control( $id, array_merge( array(
			'label'   => $label,
			'type'    => Controls_Manager::TEXT,
			'default' => $default,
		), $extra ) );
	}


	/**
	 * Never let Elementor's element cache store these widgets: their output depends on
	 * the product / archive being viewed, stock and the visitor's wishlist.
	 */
	protected function is_dynamic_content(): bool {
		return true;
	}

	/* ------------------------------------------------------------------
	 * Design layout (shared by Product Slider, Products Grid and Products Archive)
	 * ------------------------------------------------------------------ */

	protected function register_design_section() {
		$this->start_controls_section( 'section_design', array( 'label' => esc_html__( 'Design Layout', 'nexvio' ) ) );
		$this->add_control( 'design_layout', array(
			'label'       => esc_html__( 'Design Layout', 'nexvio' ),
			'type'        => class_exists( 'Nexvio_Layout_Control' ) ? Nexvio_Layout_Control::TYPE : Controls_Manager::SELECT,
			'default'     => 'classic',
			'options'     => class_exists( 'Nexvio_Layout_Control' ) ? Nexvio_Layouts::options_for_control() : Nexvio_Layouts::titles(),
			'description' => esc_html__( 'Choose how each product card looks. Colours and shapes for the chosen design are in Style > Layout design.', 'nexvio' ),
		) );
		$this->add_control( 'btn_style', array(
			'label'     => esc_html__( 'Add-to-cart button', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'auto',
			'options'   => array(
				'auto' => esc_html__( 'Design default', 'nexvio' ),
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
		$this->switcher( 'h_stack_mobile', esc_html__( 'Horizontal: image on top on mobile', 'nexvio' ), 'yes', array(
			'separator'   => 'before',
			'description' => esc_html__( 'On phones the image goes above and the details go below it. Turn off to keep image and details side by side.', 'nexvio' ),
			'condition'   => array( 'design_layout' => 'horizontal' ),
		) );
		$this->end_controls_section();

		$this->register_gallery_section();
	}

	/**
	 * Which gallery hover controls a widget shows: '' (the original set), 'simple'
	 * (on/off + animation settings) or 'full' (simple + the several-images options).
	 */
	protected function gallery_ui() {
		return '';
	}

	/** Second image shown when the pointer is over the card. */
	protected function register_gallery_section() {
		$ui = $this->gallery_ui();
		$this->start_controls_section( 'section_hover_gallery', array( 'label' => esc_html__( 'Hover gallery image', 'nexvio' ) ) );
		$this->switcher( 'gal_on', '' !== $ui ? esc_html__( 'Enable Gallery Image on Hover', 'nexvio' ) : esc_html__( 'Show gallery image on hover', 'nexvio' ), '', array(
			'description' => esc_html__( 'When the pointer is over the card, the product\'s gallery image replaces the main image. Products without gallery images keep their main image. Desktop (mouse) only.', 'nexvio' ),
		) );
		$on = array( 'gal_on' => 'yes' );
		if ( '' === $ui || 'full' === $ui ) {
		$this->add_control( 'gal_mode', array(
			'label'     => esc_html__( 'Images', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'first',
			'options'   => array(
				'first' => esc_html__( 'First gallery image', 'nexvio' ),
				'cycle' => esc_html__( 'Go through the gallery images while hovering', 'nexvio' ),
			),
			'condition' => $on,
		) );
		$this->add_control( 'gal_count', array(
			'label'     => esc_html__( 'Number of gallery images', 'nexvio' ),
			'type'      => Controls_Manager::NUMBER,
			'min'       => 1,
			'max'       => 5,
			'default'   => 3,
			'condition' => array( 'gal_on' => 'yes', 'gal_mode' => 'cycle' ),
		) );
		$this->add_control( 'gal_interval', array(
			'label'     => esc_html__( 'Time per image (ms)', 'nexvio' ),
			'type'      => Controls_Manager::NUMBER,
			'min'       => 400,
			'max'       => 6000,
			'step'      => 100,
			'default'   => 1200,
			'selectors' => array( '{{WRAPPER}} .nexvio-ps' => '--nexvio-gal-interval: {{VALUE}};' ),
			'condition' => array( 'gal_on' => 'yes', 'gal_mode' => 'cycle' ),
		) );
		}
		if ( '' !== $ui ) {
			$this->register_gallery_animation_controls( $ui );
			$this->end_controls_section();
			return;
		}
		$this->add_control( 'gal_effect', array(
			'label'     => esc_html__( 'Change effect', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'fade',
			'options'   => array(
				'fade'        => esc_html__( 'Fade', 'nexvio' ),
				'zoom-in'     => esc_html__( 'Zoom in', 'nexvio' ),
				'zoom-out'    => esc_html__( 'Zoom out', 'nexvio' ),
				'slide-left'  => esc_html__( 'Slide in from the left', 'nexvio' ),
				'slide-right' => esc_html__( 'Slide in from the right', 'nexvio' ),
				'slide-up'    => esc_html__( 'Slide in from the bottom', 'nexvio' ),
				'slide-down'  => esc_html__( 'Slide in from the top', 'nexvio' ),
			),
			'condition' => $on,
		) );
		$this->add_control( 'gal_duration', array(
			'label'      => esc_html__( 'Effect speed (ms)', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 100, 'max' => 2000, 'step' => 50 ) ),
			'default'    => array( 'size' => 500, 'unit' => 'px' ),
			'selectors'  => array( '{{WRAPPER}} .nexvio-ps' => '--nexvio-gal-dur: {{SIZE}}ms;' ),
			'condition'  => $on,
		) );
		$this->add_control( 'gal_ease', array(
			'label'     => esc_html__( 'Motion', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'cubic-bezier(.4,0,.2,1)',
			'options'   => array(
				'cubic-bezier(.4,0,.2,1)'  => esc_html__( 'Smooth', 'nexvio' ),
				'cubic-bezier(.22,1,.36,1)' => esc_html__( 'Soft landing', 'nexvio' ),
				'ease-in-out'              => esc_html__( 'Ease in and out', 'nexvio' ),
				'ease-out'                 => esc_html__( 'Ease out', 'nexvio' ),
				'linear'                   => esc_html__( 'Linear', 'nexvio' ),
			),
			'selectors' => array( '{{WRAPPER}} .nexvio-ps' => '--nexvio-gal-ease: {{VALUE}};' ),
			'condition' => $on,
		) );
		$this->end_controls_section();
	}

	/** Animation settings of the gallery hover (Products Card and the widgets that share its settings). */
	protected function register_gallery_animation_controls( $ui ) {
		$on   = array( 'gal_on' => 'yes' );
		$full = 'full' === $ui;
		$this->switcher( 'gal_anim', esc_html__( 'Enable Hover Animation', 'nexvio' ), 'yes', array( 'condition' => $on ) );
		$an = array( 'gal_on' => 'yes', 'gal_anim' => 'yes' );
		$effects = array(
			'fade'     => esc_html__( 'Fade', 'nexvio' ),
			'zoom-in'  => esc_html__( 'Zoom In', 'nexvio' ),
			'zoom-out' => esc_html__( 'Zoom Out', 'nexvio' ),
			'slide'    => esc_html__( 'Slide', 'nexvio' ),
		);
		if ( $full ) {
			/* the directions saved by earlier versions stay selectable */
			$effects['slide-left']  = esc_html__( 'Slide in from the left', 'nexvio' );
			$effects['slide-right'] = esc_html__( 'Slide in from the right', 'nexvio' );
			$effects['slide-up']    = esc_html__( 'Slide in from the bottom', 'nexvio' );
			$effects['slide-down']  = esc_html__( 'Slide in from the top', 'nexvio' );
		}
		$effects['none'] = esc_html__( 'None', 'nexvio' );
		$this->add_control( 'gal_effect', array(
			'label'     => esc_html__( 'Animation Effect', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'fade',
			'options'   => $effects,
			'condition' => $an,
		) );
		$this->add_control( 'gal_duration', array(
			'label'      => esc_html__( 'Animation Duration (ms)', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 100, 'max' => 2000, 'step' => 50 ) ),
			'default'    => array( 'size' => 500, 'unit' => 'px' ),
			'selectors'  => array( '{{WRAPPER}} .nexvio-ps' => '--nexvio-gal-dur: {{SIZE}}ms;' ),
			'condition'  => $an,
		) );
		$ease = array(
			'ease'        => esc_html__( 'Ease', 'nexvio' ),
			'ease-in'     => esc_html__( 'Ease In', 'nexvio' ),
			'ease-out'    => esc_html__( 'Ease Out', 'nexvio' ),
			'ease-in-out' => esc_html__( 'Ease In Out', 'nexvio' ),
		);
		if ( $full ) {
			$ease = array_merge( array(
				'cubic-bezier(.4,0,.2,1)'   => esc_html__( 'Smooth', 'nexvio' ),
				'cubic-bezier(.22,1,.36,1)' => esc_html__( 'Soft landing', 'nexvio' ),
			), $ease, array( 'linear' => esc_html__( 'Linear', 'nexvio' ) ) );
		}
		$this->add_control( 'gal_ease', array(
			'label'     => esc_html__( 'Animation Easing', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => $full ? 'cubic-bezier(.4,0,.2,1)' : 'ease',
			'options'   => $ease,
			'selectors' => array( '{{WRAPPER}} .nexvio-ps' => '--nexvio-gal-ease: {{VALUE}};' ),
			'condition' => $an,
		) );
	}

	/** Style controls for the new designs (hidden for Classic Vault). */
	protected function register_layout_style_section() {
		$this->start_controls_section( 'style_layout_design', array(
			'label'     => esc_html__( 'Layout design', 'nexvio' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'design_layout!' => 'classic' ),
		) );
		$this->add_control( 'l_note', array(
			'type'            => Controls_Manager::RAW_HTML,
			'raw'             => esc_html__( 'Leave a field empty to keep the colours of the chosen design.', 'nexvio' ),
			'content_classes' => 'elementor-descriptor',
		) );
		$lc = function ( $id, $label, $var, $cond = array() ) {
			$args = array(
				'label'     => $label,
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => array( '{{WRAPPER}} .nexvio-ps' => $var . ': {{VALUE}};' ),
			);
			if ( $cond ) {
				$args['condition'] = $cond;
			}
			$this->add_control( $id, $args );
		};
		$ln = function ( $id, $label, $var, $min, $max, $unit = 'px', $cond = array() ) {
			$args = array(
				'label'     => $label,
				'type'      => Controls_Manager::NUMBER,
				'default'   => '',
				'min'       => $min,
				'max'       => $max,
				'selectors' => array( '{{WRAPPER}} .nexvio-ps' => $var . ': {{VALUE}}' . $unit . ';' ),
			);
			if ( $cond ) {
				$args['condition'] = $cond;
			}
			$this->add_control( $id, $args );
		};

		$this->add_control( 'l_h_card', array( 'label' => esc_html__( 'Card', 'nexvio' ), 'type' => Controls_Manager::HEADING ) );
		$lc( 'l_card', esc_html__( 'Background', 'nexvio' ), '--nexvio-l-card' );
		$lc( 'l_border', esc_html__( 'Border colour', 'nexvio' ), '--nexvio-l-border' );
		$ln( 'l_bw', esc_html__( 'Border width (px)', 'nexvio' ), '--nexvio-l-bw', 0, 10 );
		$ln( 'l_radius', esc_html__( 'Corner radius (px)', 'nexvio' ), '--nexvio-l-radius', 0, 60 );
		$this->add_control( 'l_shadow', array(
			'label'       => esc_html__( 'Shadow preset', 'nexvio' ),
			'description' => esc_html__( 'For your own shadow (colour, offset, blur, spread) use Style > Card > Box Shadow.', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => '',
			'options'   => array(
				''       => esc_html__( 'Design default', 'nexvio' ),
				'none'   => esc_html__( 'None', 'nexvio' ),
				'soft'   => esc_html__( 'Soft', 'nexvio' ),
				'strong' => esc_html__( 'Strong', 'nexvio' ),
			),
			'selectors_dictionary' => array(
				'none'   => 'none',
				'soft'   => '0 6px 18px rgba(16,24,40,.08)',
				'strong' => '0 22px 44px -14px rgba(16,24,40,.32)',
			),
			'selectors' => array( '{{WRAPPER}} .nexvio-ps' => '--nexvio-l-shadow: {{VALUE}};' ),
		) );

		$this->add_control( 'l_h_img', array( 'label' => esc_html__( 'Image', 'nexvio' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_control( 'l_ratio', array(
			'label'     => esc_html__( 'Image shape', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => '',
			'options'   => array( '' => esc_html__( 'Design default', 'nexvio' ), '1/1' => esc_html__( 'Square', 'nexvio' ), '4/5' => '4:5', '3/4' => '3:4', '63/88' => esc_html__( 'Card (63:88)', 'nexvio' ), '4/3' => '4:3', '16/9' => '16:9' ),
			'selectors' => array( '{{WRAPPER}} .nexvio-ps' => '--nexvio-l-ratio: {{VALUE}};' ),
			'condition' => array( 'design_layout!' => 'horizontal' ),
		) );
		$this->add_control( 'l_fit', array(
			'label'     => esc_html__( 'Image fit', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => '',
			'options'   => array( '' => esc_html__( 'Design default', 'nexvio' ), 'cover' => esc_html__( 'Fill (crop)', 'nexvio' ), 'contain' => esc_html__( 'Fit (whole image)', 'nexvio' ) ),
			'selectors' => array( '{{WRAPPER}} .nexvio-ps' => '--nexvio-l-fit: {{VALUE}};' ),
		) );
		$this->add_responsive_control( 'l_media_w', array(
			'label'      => esc_html__( 'Image width', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( '%' ),
			'range'      => array( '%' => array( 'min' => 25, 'max' => 60 ) ),
			'selectors'  => array( '{{WRAPPER}} .nexvio-ps' => '--nexvio-l-media-w: {{SIZE}}%;' ),
			'condition'  => array( 'design_layout' => 'horizontal' ),
		) );
		$ln( 'l_img_pad', esc_html__( 'Space around the image (%)', 'nexvio' ), '--nexvio-l-img-pad', 0, 30, '%' );
		$ln( 'l_img_radius', esc_html__( 'Image corner radius (px)', 'nexvio' ), '--nexvio-l-img-radius', 0, 60 );
		$lc( 'l_media', esc_html__( 'Image background', 'nexvio' ), '--nexvio-l-media' );

		$this->add_control( 'l_h_text', array( 'label' => esc_html__( 'Text and price', 'nexvio' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$lc( 'l_title', esc_html__( 'Product name', 'nexvio' ), '--nexvio-l-title' );
		$lc( 'l_text', esc_html__( 'Small text (set, stock)', 'nexvio' ), '--nexvio-l-text' );
		$lc( 'l_price', esc_html__( 'Price', 'nexvio' ), '--nexvio-l-price' );
		$lc( 'l_old', esc_html__( 'Old price (on sale)', 'nexvio' ), '--nexvio-l-old' );

		$this->add_control( 'l_h_special', array( 'label' => esc_html__( 'Design effect', 'nexvio' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before', 'condition' => array( 'design_layout' => array( 'overlay', 'glass', 'neon', 'pricetag', 'spotlight' ) ) ) );
		$lc( 'l_overlay', esc_html__( 'Overlay colour', 'nexvio' ), '--nexvio-l-overlay', array( 'design_layout' => 'overlay' ) );
		$ln( 'l_blur', esc_html__( 'Glass blur (px)', 'nexvio' ), '--nexvio-l-blur', 0, 40, 'px', array( 'design_layout' => 'glass' ) );
		$lc( 'l_neon', esc_html__( 'Neon colour', 'nexvio' ), '--nexvio-l-neon', array( 'design_layout' => 'neon' ) );
		$lc( 'l_tag', esc_html__( 'Price tag background', 'nexvio' ), '--nexvio-l-tag', array( 'design_layout' => 'pricetag' ) );
		$lc( 'l_tag_text', esc_html__( 'Price tag text', 'nexvio' ), '--nexvio-l-tag-text', array( 'design_layout' => 'pricetag' ) );
		$lc( 'l_spot', esc_html__( 'Spotlight glow', 'nexvio' ), '--nexvio-l-spot', array( 'design_layout' => 'spotlight' ) );
		$this->end_controls_section();
	}

	/** Controls that only make sense for the Classic Vault design. */
	protected function limit_classic_controls() {
		$ids = array( 'image_mode', 'card_bg', 'card_border', 'card_border_h', 'card_radius', 'ratio', 'c1', 'c2', 'base', 'pattern', 'pattern_op', 'h_img', 'img_w', 'img_radius', 'frame', 'frame_c', 'frame_w', 'img_shadow', 'meta_color', 'name_color', 'name_hover', 'price_color', 'btn_bg', 'btn_bg_h', 'btn_icon', 'btn_cut' );
		foreach ( $ids as $id ) {
			$control = $this->get_controls( $id );
			if ( ! $control ) {
				continue;
			}
			$condition                  = isset( $control['condition'] ) && is_array( $control['condition'] ) ? $control['condition'] : array();
			$condition['design_layout'] = 'classic';
			$this->update_control( $id, array( 'condition' => $condition ) );
		}
	}

	/** Root classes for the chosen design. */
	protected function layout_classes( $s ) {
		$layout = isset( $s['design_layout'] ) && Nexvio_Layouts::exists( $s['design_layout'] ) ? $s['design_layout'] : 'classic';
		$out    = array( 'nexvio-ps--layout-' . $layout );
		if ( 'classic' !== $layout ) {
			$out[] = 'nexvio-ps--l';
		}
		if ( isset( $s['show_desc'] ) && 'yes' === $s['show_desc'] ) {
			$out[] = 'nexvio-ps--show-desc';
			if ( ! isset( $s['desc_even'] ) || 'yes' === $s['desc_even'] ) {
				$out[] = 'nexvio-ps--desc-even';
			}
		}
		if ( isset( $s['rating_count_style'] ) && 'words' === $s['rating_count_style'] ) {
			$out[] = 'nexvio-ps--rating-words';
		}
		if ( isset( $s['rating_number'] ) && 'yes' === $s['rating_number'] ) {
			$out[] = 'nexvio-ps--rating-number';
		}
		if ( 'horizontal' === $layout ) {
			if ( ! isset( $s['h_stack_mobile'] ) || 'yes' === $s['h_stack_mobile'] ) {
				$out[] = 'nexvio-ps--h-stack';
			}
			foreach ( array( 'img_gap', 'img_gap_tablet', 'img_gap_mobile' ) as $key ) {
				if ( isset( $s[ $key ]['size'] ) && '' !== (string) $s[ $key ]['size'] ) {
					$out[] = 'nexvio-ps--img-gap';
					break;
				}
			}
		}
		if ( isset( $s['gal_on'] ) && 'yes' === $s['gal_on'] ) {
			$fx    = isset( $s['gal_effect'] ) ? $s['gal_effect'] : 'fade';
			if ( isset( $s['gal_anim'] ) && 'yes' !== $s['gal_anim'] ) {
				$fx = 'none'; /* hover animation off: the images swap without a transition */
			}
			$out[] = 'nexvio-ps--gal';
			$out[] = 'nexvio-ps--gal-fx-' . ( in_array( $fx, array( 'fade', 'zoom-in', 'zoom-out', 'slide', 'slide-left', 'slide-right', 'slide-up', 'slide-down', 'none' ), true ) ? $fx : 'fade' );
			if ( isset( $s['gal_mode'] ) && 'cycle' === $s['gal_mode'] ) {
				$out[] = 'nexvio-ps--gal-cycle';
			}
		}
		/* a hover background chosen in Style > Button is shown as picked (no brightness tint) */
		$hbg = isset( $s['btn_bg_h_background'] ) ? $s['btn_bg_h_background'] : '';
		if ( 'gradient' === $hbg || ( 'classic' === $hbg && ! empty( $s['btn_bg_h_color'] ) ) ) {
			$out[] = 'nexvio-ps--btn-hbg';
		}
		$style = isset( $s['btn_style'] ) ? $s['btn_style'] : 'auto';
		if ( 'text' === $style || ( 'auto' === $style && Nexvio_Layouts::text_button_default( $layout ) ) ) {
			$out[] = 'nexvio-ps--btn-text';
		}
		return $out;
	}

	/* ------------------------------------------------------------------
	 * Shared control sections
	 * ------------------------------------------------------------------ */

	protected function register_query_section( $section_args = array() ) {
		$cats = $this->term_options( 'product_cat' );
		$tags = $this->term_options( 'product_tag' );

		/* ============ QUERY ============ */
		$this->start_controls_section( 'section_query', array_merge( array( 'label' => esc_html__( 'Products (query)', 'nexvio' ) ), $section_args ) );
		$this->add_control( 'source', array(
			'label'   => esc_html__( 'Show', 'nexvio' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'recent',
			'options' => array(
				'recent'   => esc_html__( 'Latest products', 'nexvio' ),
				'featured' => esc_html__( 'Featured products', 'nexvio' ),
				'sale'     => esc_html__( 'On sale', 'nexvio' ),
				'best'     => esc_html__( 'Best sellers', 'nexvio' ),
				'manual'   => esc_html__( 'Choose products by ID', 'nexvio' ),
			),
		) );
		$this->add_control( 'manual_ids', array(
			'label'       => esc_html__( 'Product IDs', 'nexvio' ),
			'type'        => Controls_Manager::TEXT,
			'placeholder' => '12, 45, 78',
			'description' => esc_html__( 'Comma-separated. Shown in this order.', 'nexvio' ),
			'condition'   => array( 'source' => 'manual' ),
			'label_block' => true,
		) );
		$this->add_control( 'categories', array(
			'label'       => esc_html__( 'Categories', 'nexvio' ),
			'type'        => Controls_Manager::SELECT2,
			'multiple'    => true,
			'options'     => $cats,
			'label_block' => true,
			'description' => esc_html__( 'For singles, choose your "Singles" category. Leave empty for all.', 'nexvio' ),
			'condition'   => array( 'source!' => 'manual' ),
		) );
		$this->add_control( 'tags', array(
			'label'       => esc_html__( 'Tags', 'nexvio' ),
			'type'        => Controls_Manager::SELECT2,
			'multiple'    => true,
			'options'     => $tags,
			'label_block' => true,
			'condition'   => array( 'source!' => 'manual' ),
		) );
		$this->add_control( 'limit', array(
			'label'   => esc_html__( 'Number of products', 'nexvio' ),
			'type'    => Controls_Manager::NUMBER,
			'min'     => 1,
			'max'     => 60,
			'default' => 12,
		) );
		$this->add_control( 'orderby', array(
			'label'     => esc_html__( 'Order by', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'date',
			'options'   => array(
				'date'       => esc_html__( 'Date', 'nexvio' ),
				'title'      => esc_html__( 'Name', 'nexvio' ),
				'price'      => esc_html__( 'Price', 'nexvio' ),
				'popularity' => esc_html__( 'Sales', 'nexvio' ),
				'rating'     => esc_html__( 'Rating', 'nexvio' ),
				'menu_order' => esc_html__( 'Menu order', 'nexvio' ),
				'rand'       => esc_html__( 'Random', 'nexvio' ),
			),
			'condition' => array( 'source!' => array( 'manual', 'best' ) ),
		) );
		$this->add_control( 'order', array(
			'label'     => esc_html__( 'Order', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'DESC',
			'options'   => array( 'DESC' => esc_html__( 'Descending', 'nexvio' ), 'ASC' => esc_html__( 'Ascending', 'nexvio' ) ),
			'condition' => array( 'source!' => array( 'manual', 'best' ) ),
		) );
		$this->switcher( 'hide_out', esc_html__( 'Hide sold-out products', 'nexvio' ), '' );
		$this->add_control( 'exclude_ids', array(
			'label'       => esc_html__( 'Exclude product IDs', 'nexvio' ),
			'type'        => Controls_Manager::TEXT,
			'placeholder' => '12, 45',
			'label_block' => true,
		) );
		$this->end_controls_section();

	}

	protected function register_card_sections() {
		$cats = $this->term_options( 'product_cat' );

		/* ============ CARD CONTENT ============ */
		$this->start_controls_section( 'section_card', array( 'label' => esc_html__( 'Card content', 'nexvio' ) ) );
		$sizes = array();
		foreach ( get_intermediate_image_sizes() as $size ) {
			$sizes[ $size ] = $size;
		}
		$sizes['full'] = 'full';
		$this->add_control( 'image_size', array(
			'label'   => esc_html__( 'Image size', 'nexvio' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'woocommerce_thumbnail',
			'options' => $sizes,
		) );
		$this->add_control( 'image_mode', array(
			'label'       => esc_html__( 'Image display', 'nexvio' ),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'contain',
			'options'     => array(
				'contain' => esc_html__( 'Card on background (like the design)', 'nexvio' ),
				'cover'   => esc_html__( 'Fill the whole area', 'nexvio' ),
			),
		) );

		$this->add_control( 'h_meta', array( 'label' => esc_html__( 'Line above the name', 'nexvio' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->switcher( 'show_meta', esc_html__( 'Show set / condition line', 'nexvio' ) );
		$this->add_control( 'set_source', array(
			'label'     => esc_html__( 'Set name comes from', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'attribute',
			'options'   => array(
				'attribute' => esc_html__( 'Product attribute', 'nexvio' ),
				'category'  => esc_html__( 'First category', 'nexvio' ),
				'none'      => esc_html__( 'Hide set', 'nexvio' ),
			),
			'condition' => array( 'show_meta' => 'yes' ),
		) );
		$this->text( 'set_attr', esc_html__( 'Set attribute slug', 'nexvio' ), 'pa_extension', array(
			'condition'   => array( 'show_meta' => 'yes', 'set_source' => 'attribute' ),
			'description' => esc_html__( 'Products > Attributes. Global attributes start with pa_.', 'nexvio' ),
		) );
		$this->switcher( 'show_cond', esc_html__( 'Show condition', 'nexvio' ), 'yes', array( 'condition' => array( 'show_meta' => 'yes' ) ) );
		$this->text( 'cond_attr', esc_html__( 'Condition attribute slug', 'nexvio' ), 'pa_etat', array( 'condition' => array( 'show_meta' => 'yes', 'show_cond' => 'yes' ) ) );
		$this->text( 'meta_sep', esc_html__( 'Separator', 'nexvio' ), ' · ', array( 'condition' => array( 'show_meta' => 'yes' ) ) );

		$this->add_control( 'h_stock', array( 'label' => esc_html__( 'Stock text', 'nexvio' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->switcher( 'show_stock', esc_html__( 'Show stock', 'nexvio' ) );
		$this->add_control( 'low_threshold', array(
			'label'     => esc_html__( 'Show "low stock" at or below', 'nexvio' ),
			'type'      => Controls_Manager::NUMBER,
			'min'       => 0,
			'max'       => 100,
			'default'   => 3,
			'condition' => array( 'show_stock' => 'yes' ),
		) );
		$this->text( 'txt_in', esc_html__( 'In stock', 'nexvio' ), 'In stock', array( 'condition' => array( 'show_stock' => 'yes' ) ) );
		$this->text( 'txt_low', esc_html__( 'Low stock ({n} = quantity)', 'nexvio' ), 'Only {n} left', array( 'condition' => array( 'show_stock' => 'yes' ) ) );
		$this->text( 'txt_out', esc_html__( 'Sold out', 'nexvio' ), 'Sold out', array( 'condition' => array( 'show_stock' => 'yes' ) ) );
		$this->text( 'txt_back', esc_html__( 'Pre-order / backorder', 'nexvio' ), 'Pre-order', array( 'condition' => array( 'show_stock' => 'yes' ) ) );

		$this->add_control( 'h_buy', array( 'label' => esc_html__( 'Price and button', 'nexvio' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->switcher( 'show_price', esc_html__( 'Show price', 'nexvio' ) );
		$this->switcher( 'show_button', esc_html__( 'Show add-to-cart button', 'nexvio' ) );
		$this->text( 'btn_label', esc_html__( 'Button label (screen readers)', 'nexvio' ), 'Add to cart', array( 'condition' => array( 'show_button' => 'yes' ) ) );
		$this->text( 'btn_label_options', esc_html__( 'Label for products with options', 'nexvio' ), 'Select options', array( 'condition' => array( 'show_button' => 'yes' ) ) );
		$this->end_controls_section();

		/* ============ BADGES ============ */
		/* ============ DESCRIPTION & RATING ============ */
		$this->start_controls_section( 'section_desc_rating', array( 'label' => esc_html__( 'Description & rating', 'nexvio' ) ) );
		$this->switcher( 'show_desc', esc_html__( 'Show short description', 'nexvio' ), '', array(
			'description' => esc_html__( 'The product\'s WooCommerce short description. Products without one show nothing (no empty space).', 'nexvio' ),
		) );
		$this->add_control( 'desc_words', array(
			'label'       => esc_html__( 'Length (words)', 'nexvio' ),
			'type'        => Controls_Manager::NUMBER,
			'min'         => 0,
			'max'         => 100,
			'default'     => 15,
			'description' => esc_html__( '0 = the whole short description.', 'nexvio' ),
			'condition'   => array( 'show_desc' => 'yes' ),
		) );
		$this->switcher( 'desc_fallback', esc_html__( 'No short description: use the long description', 'nexvio' ), '', array( 'condition' => array( 'show_desc' => 'yes' ) ) );

		$this->switcher( 'show_rating', esc_html__( 'Show rating', 'nexvio' ), '', array( 'separator' => 'before' ) );
		$this->switcher( 'rating_count', esc_html__( 'Show review count', 'nexvio' ), 'yes', array( 'condition' => array( 'show_rating' => 'yes' ) ) );
		$this->add_control( 'rating_count_style', array(
			'label'     => esc_html__( 'Review count style', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'number',
			'options'   => array(
				'number' => '(87)',
				'words'  => esc_html__( '(87 reviews)', 'nexvio' ),
			),
			'condition' => array( 'show_rating' => 'yes', 'rating_count' => 'yes' ),
		) );
		$this->text( 'rating_word', esc_html__( 'Word after the count', 'nexvio' ), 'reviews', array( 'condition' => array( 'show_rating' => 'yes', 'rating_count' => 'yes' ) ) );
		$this->switcher( 'rating_number', esc_html__( 'Show the average (4.5)', 'nexvio' ), '', array( 'condition' => array( 'show_rating' => 'yes' ) ) );
		$this->switcher( 'rating_empty', esc_html__( 'Products without reviews: show "No reviews yet"', 'nexvio' ), '', array(
			'condition'   => array( 'show_rating' => 'yes' ),
			'description' => esc_html__( 'Off: nothing is shown for them. No fake or zero stars are ever displayed.', 'nexvio' ),
		) );
		$this->text( 'rating_none_text', esc_html__( 'Text', 'nexvio' ), 'No reviews yet', array( 'condition' => array( 'show_rating' => 'yes', 'rating_empty' => 'yes' ) ) );
		$this->end_controls_section();

		/* ============ COLOUR SWATCHES ============ */
		$this->start_controls_section( 'section_swatches', array( 'label' => esc_html__( 'Colour swatches', 'nexvio' ) ) );
		$this->switcher( 'show_swatches', esc_html__( 'Show colour swatches', 'nexvio' ), '', array(
			'description' => esc_html__( 'Products with a colour attribute show a dot per colour. On variable products, choosing a colour shows that variation\'s image on the card.', 'nexvio' ),
		) );
		$sw = array( 'show_swatches' => 'yes' );
		$this->text( 'swatch_attr', esc_html__( 'Colour attribute slug', 'nexvio' ), '', array(
			'condition'   => $sw,
			'placeholder' => 'pa_color',
			'description' => esc_html__( 'Empty = found automatically (an attribute named Color, Colour, Couleur…).', 'nexvio' ),
		) );
		$this->add_control( 'swatch_action', array(
			'label'     => esc_html__( 'Change the image on', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'click',
			'options'   => array(
				'click' => esc_html__( 'Click / tap', 'nexvio' ),
				'hover' => esc_html__( 'Hover (and tap on phones)', 'nexvio' ),
			),
			'condition' => $sw,
		) );
		$this->add_control( 'swatch_max', array(
			'label'       => esc_html__( 'Maximum swatches', 'nexvio' ),
			'type'        => Controls_Manager::NUMBER,
			'min'         => 1,
			'max'         => 20,
			'default'     => 5,
			'description' => esc_html__( 'More colours show as "+3", linking to the product.', 'nexvio' ),
			'condition'   => $sw,
		) );
		$this->switcher( 'swatch_link', esc_html__( 'Open the product with the chosen colour', 'nexvio' ), 'yes', array( 'condition' => $sw ) );
		$this->switcher( 'swatch_simple', esc_html__( 'Also show on products without variations', 'nexvio' ), 'yes', array( 'condition' => $sw ) );
		$this->end_controls_section();

		$this->start_controls_section( 'section_badges', array( 'label' => esc_html__( 'Badges', 'nexvio' ) ) );
		$this->switcher( 'show_lang', esc_html__( 'Language badge', 'nexvio' ) );
		$this->text( 'lang_attr', esc_html__( 'Language attribute slug', 'nexvio' ), 'pa_langue', array(
			'condition'   => array( 'show_lang' => 'yes' ),
			'description' => esc_html__( 'Use short values like FR, EN, JP for each term.', 'nexvio' ),
		) );
		$this->switcher( 'show_cat', esc_html__( 'Category badge', 'nexvio' ), 'yes', array( 'separator' => 'before' ) );
		$this->add_control( 'cat_note', array(
			'type'            => Controls_Manager::RAW_HTML,
			'raw'             => esc_html__( 'Shows one category per product. If the widget is filtered by categories (or the page is a category archive), that category is shown. Otherwise: the primary category (Yoast / Rank Math), then the rule below.', 'nexvio' ),
			'content_classes' => 'elementor-descriptor',
			'condition'       => array( 'show_cat' => 'yes' ),
		) );
		$this->add_control( 'cat_pick', array(
			'label'     => esc_html__( 'When a product has several categories', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'specific',
			'options'   => array(
				'specific' => esc_html__( 'Most specific (sub-category)', 'nexvio' ),
				'top'      => esc_html__( 'Top-level category', 'nexvio' ),
				'first'    => esc_html__( 'First in alphabetical order', 'nexvio' ),
			),
			'condition' => array( 'show_cat' => 'yes' ),
		) );
		$this->add_control( 'cat_max', array(
			'label'       => esc_html__( 'Max characters (0 = no limit)', 'nexvio' ),
			'type'        => Controls_Manager::NUMBER,
			'min'         => 0,
			'max'         => 60,
			'default'     => 0,
			'condition'   => array( 'show_cat' => 'yes' ),
		) );
		$this->switcher( 'show_new', esc_html__( '"New" badge', 'nexvio' ), '', array( 'separator' => 'before' ) );
		$this->add_control( 'new_days', array(
			'label'     => esc_html__( 'Product counts as new for (days)', 'nexvio' ),
			'type'      => Controls_Manager::NUMBER,
			'min'       => 1,
			'max'       => 365,
			'default'   => 30,
			'condition' => array( 'show_new' => 'yes' ),
		) );
		$this->text( 'new_text', esc_html__( 'Text', 'nexvio' ), 'New', array( 'condition' => array( 'show_new' => 'yes' ) ) );
		$this->switcher( 'show_sale', esc_html__( 'Sale badge', 'nexvio' ), 'yes', array( 'separator' => 'before' ) );
		$this->switcher( 'sale_percent', esc_html__( 'Show percentage', 'nexvio' ), 'yes', array( 'condition' => array( 'show_sale' => 'yes' ) ) );
		$this->text( 'sale_text', esc_html__( 'Text (when no percentage)', 'nexvio' ), 'Sale', array( 'condition' => array( 'show_sale' => 'yes' ) ) );
		$this->switcher( 'show_pre', esc_html__( 'Pre-order badge', 'nexvio' ), 'yes', array( 'separator' => 'before' ) );
		$this->text( 'pre_slug', esc_html__( 'Pre-order tag or category slug', 'nexvio' ), 'precommande', array( 'condition' => array( 'show_pre' => 'yes' ) ) );
		$this->switcher( 'pre_backorder', esc_html__( 'Also show on backorder products', 'nexvio' ), 'yes', array( 'condition' => array( 'show_pre' => 'yes' ) ) );
		$this->text( 'pre_text', esc_html__( 'Text', 'nexvio' ), 'Pre-order', array( 'condition' => array( 'show_pre' => 'yes' ) ) );
		$this->end_controls_section();

		/* ============ WISHLIST ============ */
		$this->start_controls_section( 'section_wish', array( 'label' => esc_html__( 'Wishlist heart', 'nexvio' ) ) );
		$this->add_control( 'wish_mode', array(
			'label'   => esc_html__( 'Heart button', 'nexvio' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'wishlist',
			'options' => array(
				'wishlist' => esc_html__( 'Show (built-in wishlist)', 'nexvio' ),
				'none'     => esc_html__( 'Hide', 'nexvio' ),
			),
		) );
		$this->add_control( 'wish_note', array(
			'type'            => Controls_Manager::RAW_HTML,
			'raw'             => esc_html__( 'Built-in wishlist: no other plugin needed. Adds / removes with AJAX, works for visitors and logged-in customers. Wishlist page: Nexvio > Wishlist.', 'nexvio' ),
			'content_classes' => 'elementor-descriptor',
			'condition'       => array( 'wish_mode!' => 'none' ),
		) );
		$this->text( 'wish_label', esc_html__( 'Label (screen readers)', 'nexvio' ), 'Add to wishlist', array( 'condition' => array( 'wish_mode!' => 'none' ) ) );
		$this->switcher( 'wish_toast', esc_html__( 'Show message after click', 'nexvio' ), 'yes', array( 'condition' => array( 'wish_mode!' => 'none' ), 'separator' => 'before' ) );
		$cond = array( 'wish_mode!' => 'none', 'wish_toast' => 'yes' );
		$this->text( 'wish_added', esc_html__( 'Added message', 'nexvio' ), 'Added to your wishlist', array( 'condition' => $cond ) );
		$this->text( 'wish_removed', esc_html__( 'Removed message', 'nexvio' ), 'Removed from your wishlist', array( 'condition' => $cond ) );
		$this->text( 'wish_view', esc_html__( '"View wishlist" link text', 'nexvio' ), 'View my wishlist', array( 'condition' => $cond ) );
		$this->text( 'wish_error', esc_html__( 'Error message', 'nexvio' ), 'Something went wrong, please try again.', array( 'condition' => $cond ) );
		$this->end_controls_section();

		/* ============ COLOURS BY CATEGORY ============ */
		$this->start_controls_section( 'section_colors', array( 'label' => esc_html__( 'Background by category', 'nexvio' ) ) );
		$this->add_control( 'colors_note', array(
			'type'            => Controls_Manager::RAW_HTML,
			'raw'             => esc_html__( 'Give each set its own background colours (e.g. Mega Evolution = blue/purple). Products without a rule use the colours in Style > Image area.', 'nexvio' ),
			'content_classes' => 'elementor-descriptor',
		) );
		$rep = new Repeater();
		$rep->add_control( 'cat', array(
			'label'   => esc_html__( 'Category', 'nexvio' ),
			'type'    => Controls_Manager::SELECT,
			'options' => array( '' => '—' ) + $cats,
			'default' => '',
		) );
		$rep->add_control( 'c1', array( 'label' => esc_html__( 'Colour 1 (glow)', 'nexvio' ), 'type' => Controls_Manager::COLOR, 'default' => '#4d8dff' ) );
		$rep->add_control( 'c2', array( 'label' => esc_html__( 'Colour 2', 'nexvio' ), 'type' => Controls_Manager::COLOR, 'default' => '#5b2fb0' ) );
		$this->add_control( 'color_rules', array(
			'label'       => esc_html__( 'Rules', 'nexvio' ),
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $rep->get_controls(),
			'title_field' => '{{{ cat || "—" }}}',
			'default'     => array(),
		) );
		$this->end_controls_section();

	}

	protected function register_card_style_sections() {
		/* ============ STYLE: CARD ============ */
		$this->start_controls_section( 'style_card', array( 'label' => esc_html__( 'Card', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->color_var( 'card_bg', esc_html__( 'Background', 'nexvio' ), '--nexvio-ps-card-bg', '#1f252e' );
		$this->color_var( 'card_border', esc_html__( 'Border', 'nexvio' ), '--nexvio-ps-card-border', '#313945' );
		$this->color_var( 'card_border_h', esc_html__( 'Border on hover', 'nexvio' ), '--nexvio-ps-card-border-hover', '#424b58' );
		$this->number_var( 'card_radius', esc_html__( 'Corner radius (px)', 'nexvio' ), '--nexvio-ps-card-radius', 6, 0, 40, 1, 'px' );
		$this->add_responsive_control( 'body_pad', array(
			'label'      => esc_html__( 'Text area padding', 'nexvio' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px' ),
			'default'    => array( 'top' => 14, 'right' => 14, 'bottom' => 16, 'left' => 14, 'unit' => 'px', 'isLinked' => false ),
			'selectors'  => array( '{{WRAPPER}} .nexvio-ps__body' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		/* full box-shadow control (colour, horizontal, vertical, blur, spread, inset) */
		$this->add_control( 'h_card_shadow', array( 'label' => esc_html__( 'Box Shadow', 'nexvio' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->start_controls_tabs( 'card_shadow_tabs' );
		$this->start_controls_tab( 'card_shadow_normal', array( 'label' => esc_html__( 'Normal', 'nexvio' ) ) );
		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array(
			'name'     => 'card_bshadow',
			'selector' => '{{WRAPPER}} .nexvio-ps .nexvio-ps__card',
		) );
		$this->end_controls_tab();
		$this->start_controls_tab( 'card_shadow_hover', array( 'label' => esc_html__( 'Hover', 'nexvio' ) ) );
		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array(
			'name'     => 'card_bshadow_h',
			'selector' => '{{WRAPPER}} .nexvio-ps .nexvio-ps__card:hover',
		) );
		$this->end_controls_tab();
		$this->end_controls_tabs();
		$this->end_controls_section();

		/* ============ STYLE: IMAGE (same controls as the Elementor Image widget) ============ */
		$img   = '{{WRAPPER}} .nexvio-ps .nexvio-ps__media .nexvio-ps__img';
		$img_h = '{{WRAPPER}} .nexvio-ps .nexvio-ps__card:hover .nexvio-ps__media .nexvio-ps__img';
		$this->start_controls_section( 'style_image', array( 'label' => esc_html__( 'Image', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
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
		) );
		$this->add_responsive_control( 'img_width', array(
			'label'          => esc_html__( 'Width', 'nexvio' ),
			'type'           => Controls_Manager::SLIDER,
			'size_units'     => array( '%', 'px', 'vw' ),
			'range'          => array( '%' => array( 'min' => 1, 'max' => 100 ), 'px' => array( 'min' => 1, 'max' => 1000 ), 'vw' => array( 'min' => 1, 'max' => 100 ) ),
			'selectors'      => array( $img => 'width: {{SIZE}}{{UNIT}};' ),
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
			'selectors' => array( '{{WRAPPER}} .nexvio-ps' => '--nexvio-img-op: {{SIZE}};' ),
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
			'selectors' => array( '{{WRAPPER}} .nexvio-ps' => '--nexvio-img-op-h: {{SIZE}};' ),
		) );
		$this->add_group_control( Group_Control_Css_Filter::get_type(), array(
			'name'     => 'img_filters_h',
			'selector' => $img_h,
		) );
		$this->add_control( 'img_td', array(
			'label'     => esc_html__( 'Transition Duration (s)', 'nexvio' ),
			'type'      => Controls_Manager::SLIDER,
			'range'     => array( 'px' => array( 'min' => 0, 'max' => 3, 'step' => 0.1 ) ),
			'selectors' => array( '{{WRAPPER}} .nexvio-ps .nexvio-ps__media .nexvio-ps__img:not(.nexvio-ps__alt)' => 'transition-duration: {{SIZE}}s;' ),
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
		$this->end_controls_section();

		/* ============ STYLE: IMAGE AREA ============ */
		$this->start_controls_section( 'style_media', array( 'label' => esc_html__( 'Image area', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_control( 'ratio', array(
			'label'     => esc_html__( 'Shape', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => '1/1',
			'options'   => array( '1/1' => esc_html__( 'Square', 'nexvio' ), '4/5' => '4:5', '3/4' => '3:4', '63/88' => esc_html__( 'Card (63:88)', 'nexvio' ), '4/3' => '4:3' ),
			'selectors' => array( '{{WRAPPER}} .nexvio-ps' => '--nexvio-ps-ratio: {{VALUE}};' ),
		) );
		$this->color_var( 'c1', esc_html__( 'Glow colour', 'nexvio' ), '--nexvio-ps-c1', '#4d8dff' );
		$this->color_var( 'c2', esc_html__( 'Gradient colour', 'nexvio' ), '--nexvio-ps-c2', '#5b2fb0' );
		$this->color_var( 'base', esc_html__( 'Base colour', 'nexvio' ), '--nexvio-ps-base', '#141920' );
		$this->switcher( 'pattern', esc_html__( 'Hexagon pattern', 'nexvio' ), 'yes', array( 'separator' => 'before' ) );
		$this->number_var( 'pattern_op', esc_html__( 'Pattern strength (0 to 3)', 'nexvio' ), '--nexvio-ps-pattern-op', 1, 0, 3, 0.1, '', array( 'pattern' => 'yes' ) );
		$this->add_control( 'h_img', array( 'label' => esc_html__( 'Product image', 'nexvio' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before', 'condition' => array( 'image_mode' => 'contain' ) ) );
		$this->add_responsive_control( 'img_w', array(
			'label'      => esc_html__( 'Image width', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( '%' ),
			'range'      => array( '%' => array( 'min' => 20, 'max' => 100 ) ),
			'default'    => array( 'size' => 52, 'unit' => '%' ),
			'selectors'  => array( '{{WRAPPER}} .nexvio-ps' => '--nexvio-ps-img-w: {{SIZE}}%;' ),
			'condition'  => array( 'image_mode' => 'contain' ),
		) );
		$this->number_var( 'img_radius', esc_html__( 'Image corner radius (px)', 'nexvio' ), '--nexvio-ps-img-radius', 8, 0, 40, 1, 'px', array( 'image_mode' => 'contain' ) );
		$this->switcher( 'frame', esc_html__( 'Gold frame', 'nexvio' ), 'yes', array( 'condition' => array( 'image_mode' => 'contain' ) ) );
		$this->color_var( 'frame_c', esc_html__( 'Frame colour', 'nexvio' ), '--nexvio-ps-frame-c', 'rgba(255,236,170,0.75)', array( 'image_mode' => 'contain', 'frame' => 'yes' ) );
		$this->number_var( 'frame_w', esc_html__( 'Frame thickness (px)', 'nexvio' ), '--nexvio-ps-frame-w', 4, 0, 20, 1, 'px', array( 'image_mode' => 'contain', 'frame' => 'yes' ) );
		$this->switcher( 'img_shadow', esc_html__( 'Image shadow', 'nexvio' ), 'yes', array( 'condition' => array( 'image_mode' => 'contain' ) ) );
		$this->add_control( 'hover_fx', array(
			'label'     => esc_html__( 'Hover effect', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'lift',
			'options'   => array( 'lift' => esc_html__( 'Lift and tilt', 'nexvio' ), 'zoom' => esc_html__( 'Zoom', 'nexvio' ), 'none' => esc_html__( 'None', 'nexvio' ) ),
			'separator' => 'before',
		) );
		$this->end_controls_section();

		/* ============ STYLE: TEXT ============ */
		$this->start_controls_section( 'style_text', array( 'label' => esc_html__( 'Card text', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_control( 'meta_color', array( 'label' => esc_html__( 'Set / condition colour', 'nexvio' ), 'type' => Controls_Manager::COLOR, 'default' => '#8f9aab', 'selectors' => array( '{{WRAPPER}} .nexvio-ps__meta' => 'color: {{VALUE}};' ) ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'meta_typo', 'label' => esc_html__( 'Set / condition typography', 'nexvio' ), 'selector' => '{{WRAPPER}} .nexvio-ps__meta' ) );
		$this->add_control( 'name_color', array( 'label' => esc_html__( 'Name colour', 'nexvio' ), 'type' => Controls_Manager::COLOR, 'default' => '#e8edf4', 'selectors' => array( '{{WRAPPER}} .nexvio-ps__name' => 'color: {{VALUE}};' ), 'separator' => 'before' ) );
		$this->add_control( 'name_hover', array( 'label' => esc_html__( 'Name hover colour', 'nexvio' ), 'type' => Controls_Manager::COLOR, 'default' => '#5a9dff', 'selectors' => array( '{{WRAPPER}} .nexvio-ps__name a:hover' => 'color: {{VALUE}};' ) ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'name_typo', 'label' => esc_html__( 'Name typography', 'nexvio' ), 'selector' => '{{WRAPPER}} .nexvio-ps__name' ) );
		$this->add_control( 'h_stock_style', array( 'label' => esc_html__( 'Stock', 'nexvio' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->color_var( 'ok_c', esc_html__( 'In stock', 'nexvio' ), '--nexvio-ps-ok', '#3ccf91' );
		$this->color_var( 'low_c', esc_html__( 'Low stock / pre-order', 'nexvio' ), '--nexvio-ps-low', '#e8b949' );
		$this->color_var( 'out_c', esc_html__( 'Sold out', 'nexvio' ), '--nexvio-ps-out', '#ff6371' );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'stock_typo', 'label' => esc_html__( 'Stock typography', 'nexvio' ), 'selector' => '{{WRAPPER}} .nexvio-ps__stock' ) );
		$this->add_control( 'price_color', array( 'label' => esc_html__( 'Price colour', 'nexvio' ), 'type' => Controls_Manager::COLOR, 'default' => '#e8edf4', 'selectors' => array( '{{WRAPPER}} .nexvio-ps__price' => 'color: {{VALUE}};' ), 'separator' => 'before' ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'price_typo', 'label' => esc_html__( 'Price typography', 'nexvio' ), 'selector' => '{{WRAPPER}} .nexvio-ps__price' ) );
		$this->end_controls_section();

		/* ============ STYLE: SPACING ============ */
		$this->start_controls_section( 'style_spacing', array( 'label' => esc_html__( 'Spacing', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_control( 'h_sp_text', array( 'label' => esc_html__( 'Title and text', 'nexvio' ), 'type' => Controls_Manager::HEADING ) );
		$this->add_responsive_control( 'title_gap', array(
			'label'       => esc_html__( 'Space between title and text', 'nexvio' ),
			'description' => esc_html__( 'Space under the product name, before the description, rating, stock or price.', 'nexvio' ),
			'type'        => Controls_Manager::SLIDER,
			'size_units'  => array( 'px' ),
			'range'       => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
			'selectors'   => array( '{{WRAPPER}} .nexvio-ps .nexvio-ps__name' => 'margin-bottom: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'text_gap', array(
			'label'       => esc_html__( 'Space between all text lines', 'nexvio' ),
			'description' => esc_html__( 'Space between set line, name, rating, description, stock and price row.', 'nexvio' ),
			'type'        => Controls_Manager::SLIDER,
			'size_units'  => array( 'px' ),
			'range'       => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
			'selectors'   => array( '{{WRAPPER}} .nexvio-ps .nexvio-ps__card .nexvio-ps__body' => 'gap: {{SIZE}}{{UNIT}}; row-gap: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'price_gap', array(
			'label'      => esc_html__( 'Space above price and button', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
			'selectors'  => array( '{{WRAPPER}} .nexvio-ps .nexvio-ps__card .nexvio-ps__row' => 'padding-top: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_control( 'h_sp_img', array(
			'label'      => esc_html__( 'Image and details', 'nexvio' ),
			'type'       => Controls_Manager::HEADING,
			'separator'  => 'before',
			'condition'  => array( 'design_layout' => 'horizontal' ),
		) );
		$this->add_responsive_control( 'img_gap', array(
			'label'       => esc_html__( 'Space between image and details', 'nexvio' ),
			'description' => esc_html__( 'Exact distance from the image to the text. Works with the Horizontal design (when the image is beside the details).', 'nexvio' ),
			'type'        => Controls_Manager::SLIDER,
			'size_units'  => array( 'px' ),
			'range'       => array( 'px' => array( 'min' => 0, 'max' => 80 ) ),
			'selectors'   => array( '{{WRAPPER}} .nexvio-ps.nexvio-ps--layout-horizontal .nexvio-ps__card' => 'column-gap: {{SIZE}}{{UNIT}};' ),
			'condition'   => array( 'design_layout' => 'horizontal' ),
		) );
		$this->end_controls_section();

		/* ============ STYLE: BADGES ============ */
		/* ============ STYLE: SHORT DESCRIPTION ============ */
		$this->start_controls_section( 'style_desc', array(
			'label'      => esc_html__( 'Short description', 'nexvio' ),
			'tab'        => Controls_Manager::TAB_STYLE,
			'conditions' => array(
				'relation' => 'or',
				'terms'    => array(
					array( 'name' => 'show_desc', 'operator' => '==', 'value' => 'yes' ),
					array( 'name' => 'list_excerpt', 'operator' => '==', 'value' => 'yes' ),
				),
			),
		) );
		$this->add_control( 'desc_color', array( 'label' => esc_html__( 'Colour', 'nexvio' ), 'type' => Controls_Manager::COLOR, 'default' => '', 'selectors' => array( '{{WRAPPER}} .nexvio-ps .nexvio-ps__excerpt' => 'color: {{VALUE}};' ) ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'desc_typo', 'label' => esc_html__( 'Typography (size, line height…)', 'nexvio' ), 'selector' => '{{WRAPPER}} .nexvio-ps .nexvio-ps__excerpt' ) );
		$this->add_responsive_control( 'desc_align', array(
			'label'     => esc_html__( 'Alignment', 'nexvio' ),
			'type'      => Controls_Manager::CHOOSE,
			'options'   => array(
				'left'    => array( 'title' => esc_html__( 'Left', 'nexvio' ), 'icon' => 'eicon-text-align-left' ),
				'center'  => array( 'title' => esc_html__( 'Center', 'nexvio' ), 'icon' => 'eicon-text-align-center' ),
				'right'   => array( 'title' => esc_html__( 'Right', 'nexvio' ), 'icon' => 'eicon-text-align-right' ),
				'justify' => array( 'title' => esc_html__( 'Justify', 'nexvio' ), 'icon' => 'eicon-text-align-justify' ),
			),
			'selectors' => array( '{{WRAPPER}} .nexvio-ps .nexvio-ps__excerpt' => 'text-align: {{VALUE}};' ),
		) );
		$this->add_responsive_control( 'desc_space', array(
			'label'      => esc_html__( 'Space above / below', 'nexvio' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px' ),
			'allowed_dimensions' => 'vertical',
			'selectors'  => array( '{{WRAPPER}} .nexvio-ps .nexvio-ps__excerpt' => 'margin-top: {{TOP}}{{UNIT}}; margin-bottom: {{BOTTOM}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'desc_lines', array(
			'label'       => esc_html__( 'Maximum lines', 'nexvio' ),
			'type'        => Controls_Manager::NUMBER,
			'min'         => 0,
			'max'         => 10,
			'default'     => 3,
			'description' => esc_html__( 'Longer text ends with "…". 0 = no limit.', 'nexvio' ),
			'selectors'   => array( '{{WRAPPER}} .nexvio-ps' => '--nexvio-desc-lines: {{VALUE}};' ),
		) );
		$this->switcher( 'desc_even', esc_html__( 'Same height on every card', 'nexvio' ), 'yes', array(
			'description' => esc_html__( 'Keeps names, prices and buttons lined up across a row, even when some descriptions are shorter.', 'nexvio' ),
		) );
		$this->end_controls_section();

		/* ============ STYLE: RATING ============ */
		$this->start_controls_section( 'style_rating', array( 'label' => esc_html__( 'Rating', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE, 'condition' => array( 'show_rating' => 'yes' ) ) );
		$this->add_control( 'star_color', array( 'label' => esc_html__( 'Star colour', 'nexvio' ), 'type' => Controls_Manager::COLOR, 'default' => '', 'selectors' => array( '{{WRAPPER}} .nexvio-ps' => '--nexvio-l-star: {{VALUE}};' ) ) );
		$this->add_control( 'star_empty', array( 'label' => esc_html__( 'Empty star colour', 'nexvio' ), 'type' => Controls_Manager::COLOR, 'default' => '', 'selectors' => array( '{{WRAPPER}} .nexvio-ps' => '--nexvio-star-empty: {{VALUE}};' ) ) );
		$this->add_responsive_control( 'star_size', array(
			'label'      => esc_html__( 'Star size', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 8, 'max' => 32 ) ),
			'selectors'  => array( '{{WRAPPER}} .nexvio-ps' => '--nexvio-star-size: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'rating_align', array(
			'label'     => esc_html__( 'Alignment', 'nexvio' ),
			'type'      => Controls_Manager::CHOOSE,
			'options'   => array(
				'flex-start' => array( 'title' => esc_html__( 'Left', 'nexvio' ), 'icon' => 'eicon-text-align-left' ),
				'center'     => array( 'title' => esc_html__( 'Center', 'nexvio' ), 'icon' => 'eicon-text-align-center' ),
				'flex-end'   => array( 'title' => esc_html__( 'Right', 'nexvio' ), 'icon' => 'eicon-text-align-right' ),
			),
			'selectors' => array( '{{WRAPPER}} .nexvio-ps .nexvio-ps__card .nexvio-ps__rating' => 'justify-content: {{VALUE}};' ),
		) );
		$this->add_responsive_control( 'rating_gap', array(
			'label'      => esc_html__( 'Space between stars and count', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 20 ) ),
			'selectors'  => array( '{{WRAPPER}} .nexvio-ps .nexvio-ps__card .nexvio-ps__rating' => 'gap: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'rating_space', array(
			'label'      => esc_html__( 'Space above / below', 'nexvio' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px' ),
			'allowed_dimensions' => 'vertical',
			'selectors'  => array( '{{WRAPPER}} .nexvio-ps .nexvio-ps__card .nexvio-ps__rating' => 'margin-top: {{TOP}}{{UNIT}}; margin-bottom: {{BOTTOM}}{{UNIT}};' ),
		) );
		$this->add_control( 'rating_text_color', array( 'label' => esc_html__( 'Count / average colour', 'nexvio' ), 'type' => Controls_Manager::COLOR, 'default' => '', 'separator' => 'before', 'selectors' => array( '{{WRAPPER}} .nexvio-ps .nexvio-ps__card .nexvio-ps__rating' => 'color: {{VALUE}};' ) ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'rating_typo', 'label' => esc_html__( 'Count / average typography', 'nexvio' ), 'selector' => '{{WRAPPER}} .nexvio-ps .nexvio-ps__card .nexvio-ps__rating' ) );
		$this->end_controls_section();

		$this->start_controls_section( 'style_swatches', array( 'label' => esc_html__( 'Colour swatches', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE, 'condition' => array( 'show_swatches' => 'yes' ) ) );
		$this->add_responsive_control( 'sw_size', array(
			'label'      => esc_html__( 'Size', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 10, 'max' => 40 ) ),
			'default'    => array( 'size' => 16, 'unit' => 'px' ),
			'selectors'  => array( '{{WRAPPER}} .nexvio-ps' => '--nexvio-sw-size: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'sw_gap', array(
			'label'      => esc_html__( 'Space between', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 20 ) ),
			'default'    => array( 'size' => 6, 'unit' => 'px' ),
			'selectors'  => array( '{{WRAPPER}} .nexvio-ps' => '--nexvio-sw-gap: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_control( 'sw_shape', array(
			'label'                => esc_html__( 'Shape', 'nexvio' ),
			'type'                 => Controls_Manager::SELECT,
			'default'              => 'circle',
			'options'              => array( 'circle' => esc_html__( 'Circle', 'nexvio' ), 'rounded' => esc_html__( 'Rounded square', 'nexvio' ), 'square' => esc_html__( 'Square', 'nexvio' ) ),
			'selectors_dictionary' => array( 'circle' => '50%', 'rounded' => '4px', 'square' => '0' ),
			'selectors'            => array( '{{WRAPPER}} .nexvio-ps' => '--nexvio-sw-radius: {{VALUE}};' ),
		) );
		$this->add_responsive_control( 'sw_align', array(
			'label'     => esc_html__( 'Alignment', 'nexvio' ),
			'type'      => Controls_Manager::CHOOSE,
			'options'   => array(
				'flex-start' => array( 'title' => esc_html__( 'Left', 'nexvio' ), 'icon' => 'eicon-text-align-left' ),
				'center'     => array( 'title' => esc_html__( 'Center', 'nexvio' ), 'icon' => 'eicon-text-align-center' ),
				'flex-end'   => array( 'title' => esc_html__( 'Right', 'nexvio' ), 'icon' => 'eicon-text-align-right' ),
			),
			'selectors' => array( '{{WRAPPER}} .nexvio-ps' => '--nexvio-sw-justify: {{VALUE}};' ),
		) );
		$this->add_control( 'sw_border', array( 'label' => esc_html__( 'Border', 'nexvio' ), 'type' => Controls_Manager::COLOR, 'default' => '', 'selectors' => array( '{{WRAPPER}} .nexvio-ps' => '--nexvio-sw-border: {{VALUE}};' ) ) );
		$this->add_control( 'sw_ring', array( 'label' => esc_html__( 'Selected ring', 'nexvio' ), 'type' => Controls_Manager::COLOR, 'default' => '', 'selectors' => array( '{{WRAPPER}} .nexvio-ps' => '--nexvio-sw-ring: {{VALUE}};' ) ) );
		$this->add_control( 'sw_more', array( 'label' => esc_html__( '"+3" text', 'nexvio' ), 'type' => Controls_Manager::COLOR, 'default' => '', 'selectors' => array( '{{WRAPPER}} .nexvio-ps' => '--nexvio-sw-more: {{VALUE}};' ) ) );
		$this->end_controls_section();

		$this->start_controls_section( 'style_badges', array( 'label' => esc_html__( 'Badges', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_control( 'h_lang', array( 'label' => esc_html__( 'Language', 'nexvio' ), 'type' => Controls_Manager::HEADING ) );
		$this->color_var( 'lang_bg', esc_html__( 'Background', 'nexvio' ), '--nexvio-ps-lang-bg', 'rgba(10,13,17,0.75)' );
		$this->color_var( 'lang_border', esc_html__( 'Border', 'nexvio' ), '--nexvio-ps-lang-border', '#3d8bff' );
		$this->color_var( 'lang_text', esc_html__( 'Text', 'nexvio' ), '--nexvio-ps-lang-text', '#8cc8ff' );
		$this->add_control( 'h_cat', array( 'label' => esc_html__( 'Category', 'nexvio' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->color_var( 'cat_bg', esc_html__( 'Background', 'nexvio' ), '--nexvio-ps-cat-bg', '#3d8bff' );
		$this->color_var( 'cat_tc', esc_html__( 'Text', 'nexvio' ), '--nexvio-ps-cat-text', '#ffffff' );
		$this->color_var( 'cat_border', esc_html__( 'Border', 'nexvio' ), '--nexvio-ps-cat-border', 'rgba(0,0,0,0)' );
		$this->add_control( 'h_new', array( 'label' => esc_html__( 'New', 'nexvio' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->color_var( 'new_bg', esc_html__( 'Background', 'nexvio' ), '--nexvio-ps-new-bg', '#3d8bff' );
		$this->color_var( 'new_tc', esc_html__( 'Text', 'nexvio' ), '--nexvio-ps-new-text', '#ffffff' );
		$this->add_control( 'h_sale', array( 'label' => esc_html__( 'Sale', 'nexvio' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->color_var( 'sale_bg', esc_html__( 'Background', 'nexvio' ), '--nexvio-ps-sale-bg', '#ff6371' );
		$this->color_var( 'sale_tc', esc_html__( 'Text', 'nexvio' ), '--nexvio-ps-sale-text', '#ffffff' );
		$this->add_control( 'h_pre', array( 'label' => esc_html__( 'Pre-order', 'nexvio' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->color_var( 'pre_bg', esc_html__( 'Background', 'nexvio' ), '--nexvio-ps-pre-bg', '#e8b949' );
		$this->color_var( 'pre_tc', esc_html__( 'Text', 'nexvio' ), '--nexvio-ps-pre-text', '#1d1606' );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'badge_typo', 'label' => esc_html__( 'Badge typography', 'nexvio' ), 'selector' => '{{WRAPPER}} .nexvio-ps__badge', 'separator' => 'before' ) );
		$this->add_control( 'h_heart', array( 'label' => esc_html__( 'Wishlist heart', 'nexvio' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->color_var( 'heart_bg', esc_html__( 'Background', 'nexvio' ), '--nexvio-ps-heart-bg', 'rgba(10,13,17,0.6)' );
		$this->color_var( 'heart_c', esc_html__( 'Icon', 'nexvio' ), '--nexvio-ps-heart', '#cfd7e2' );
		$this->color_var( 'heart_on', esc_html__( 'Icon hover / active', 'nexvio' ), '--nexvio-ps-heart-on', '#ff6b8a' );
		$this->end_controls_section();

		/* ============ STYLE: BUTTON ============ */
		$this->start_controls_section( 'style_button', array( 'label' => esc_html__( 'Add-to-cart button', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE, 'condition' => array( 'show_button' => 'yes' ) ) );
		/* New designs (everything except Classic Vault): colours of the button and its icon */
		$new = array( 'design_layout!' => 'classic' );
		$this->add_control( 'l_accent', array(
			'label'     => esc_html__( 'Button background', 'nexvio' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '',
			'selectors' => array( '{{WRAPPER}} .nexvio-ps' => '--nexvio-l-accent: {{VALUE}};' ),
			'condition' => $new,
		) );
		$this->add_control( 'l_accent_text', array(
			'label'     => esc_html__( 'Icon / text colour', 'nexvio' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '',
			'selectors' => array( '{{WRAPPER}} .nexvio-ps' => '--nexvio-l-accent-text: {{VALUE}};' ),
			'condition' => $new,
		) );
		$this->add_control( 'l_accent_h', array(
			'label'     => esc_html__( 'Button background on hover', 'nexvio' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '',
			'selectors' => array( '{{WRAPPER}} .nexvio-ps.nexvio-ps--l .nexvio-ps__add:hover, {{WRAPPER}} .nexvio-ps.nexvio-ps--l .nexvio-ps__add:focus-visible' => 'background: {{VALUE}}; filter: none;' ),
			'condition' => $new,
		) );
		$this->add_control( 'l_accent_text_h', array(
			'label'     => esc_html__( 'Icon / text colour on hover', 'nexvio' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '',
			'selectors' => array( '{{WRAPPER}} .nexvio-ps.nexvio-ps--l .nexvio-ps__add:hover, {{WRAPPER}} .nexvio-ps.nexvio-ps--l .nexvio-ps__add:focus-visible' => 'color: {{VALUE}};' ),
			'condition' => $new,
		) );
		$this->add_control( 'l_btn_radius', array(
			'label'     => esc_html__( 'Corner radius (px)', 'nexvio' ),
			'type'      => Controls_Manager::NUMBER,
			'default'   => '',
			'min'       => 0,
			'max'       => 60,
			'selectors' => array( '{{WRAPPER}} .nexvio-ps' => '--nexvio-l-btn-radius: {{VALUE}}px;' ),
			'condition' => $new,
		) );
		$this->color_var( 'btn_bg', esc_html__( 'Background', 'nexvio' ), '--nexvio-ps-accent', '#3d8bff' );
		$this->color_var( 'btn_bg_h', esc_html__( 'Background on hover', 'nexvio' ), '--nexvio-ps-accent-hover', '#5a9dff' );
		$this->color_var( 'btn_icon', esc_html__( 'Icon colour', 'nexvio' ), '--nexvio-ps-btn-icon', '#ffffff' );
		$this->number_var( 'btn_size', esc_html__( 'Size (px)', 'nexvio' ), '--nexvio-ps-btn-size', 40, 24, 80, 1, 'px' );
		$this->number_var( 'btn_cut', esc_html__( 'Cut corner (px, 0 = square)', 'nexvio' ), '--nexvio-ps-btn-cut', 8, 0, 30, 1, 'px' );
		$this->end_controls_section();

	}


	/* ------------------------------------------------------------------
	 * Query
	 * ------------------------------------------------------------------ */

	protected function ids_from_text( $text ) {
		$ids = array_map( 'absint', preg_split( '/[\s,]+/', (string) $text, -1, PREG_SPLIT_NO_EMPTY ) );
		return array_values( array_filter( $ids ) );
	}

	protected function build_query_args( $s ) {
		$args = array(
			'status'     => 'publish',
			'limit'      => max( 1, (int) $s['limit'] ),
			'visibility' => 'catalog',
			'return'     => 'objects',
		);
		$source = $s['source'];

		if ( 'manual' === $source ) {
			$ids = $this->ids_from_text( $s['manual_ids'] );
			if ( ! $ids ) {
				return null;
			}
			$args['include'] = $ids;
			$args['orderby'] = 'post__in';
			$args['limit']   = count( $ids );
		} else {
			if ( ! empty( $s['categories'] ) ) {
				$args['category'] = (array) $s['categories'];
			}
			if ( ! empty( $s['tags'] ) ) {
				$args['tag'] = (array) $s['tags'];
			}
			if ( 'featured' === $source ) {
				$args['featured'] = true;
			}
			if ( 'sale' === $source ) {
				$sale            = wc_get_product_ids_on_sale();
				$args['include'] = $sale ? $sale : array( 0 );
			}

			$orderby = 'best' === $source ? 'popularity' : $s['orderby'];
			$order   = 'best' === $source ? 'DESC' : $s['order'];
			switch ( $orderby ) {
				case 'price':
					$args['orderby']  = 'meta_value_num';
					$args['meta_key'] = '_price'; // phpcs:ignore WordPress.DB.SlowDBQuery
					break;
				case 'popularity':
					$args['orderby']  = 'meta_value_num';
					$args['meta_key'] = 'total_sales'; // phpcs:ignore WordPress.DB.SlowDBQuery
					break;
				case 'rating':
					$args['orderby']  = 'meta_value_num';
					$args['meta_key'] = '_wc_average_rating'; // phpcs:ignore WordPress.DB.SlowDBQuery
					break;
				default:
					$args['orderby'] = $orderby;
			}
			$args['order'] = 'ASC' === $order ? 'ASC' : 'DESC';
		}

		if ( 'yes' === $s['hide_out'] ) {
			$args['stock_status'] = array( 'instock', 'onbackorder' );
		}
		$exclude = $this->ids_from_text( $s['exclude_ids'] );
		if ( $exclude ) {
			/* A short list typed by the site owner in the widget; needed for correct pagination. */
			$args['exclude'] = $exclude; // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
		}

		return apply_filters( 'nexvio_product_query_args', $args, $s );
	}

	protected function get_products( $s ) {
		if ( ! function_exists( 'wc_get_products' ) ) {
			return array();
		}
		$args = $this->build_query_args( $s );
		return null === $args ? array() : wc_get_products( $args );
	}

	/**
	 * Paginated custom query. Returns object with products, total, max_num_pages.
	 */
	/** Does this widget's list follow the Nexvio Product Filter? (Products Grid shows chosen products and does not.) */
	protected function follows_filter() {
		return method_exists( $this, 'extra_root_classes' ) && class_exists( 'Nexvio_Filter_Query' ) && ! in_array( 'nexvio-pg--grid', $this->extra_root_classes(), true ) && Nexvio_Filter_Query::active();
	}

	protected function get_products_paged( $s, $per_page, $page ) {
		$empty = (object) array( 'products' => array(), 'total' => 0, 'max_num_pages' => 0 );
		if ( ! function_exists( 'wc_get_products' ) ) {
			return $empty;
		}
		$args = $this->build_query_args( $s );
		if ( null === $args ) {
			return $empty;
		}
		$args['limit']    = max( 1, (int) $per_page );
		$args['page']     = max( 1, (int) $page );
		$args['paginate'] = true;
		if ( $this->follows_filter() ) {
			$args['nexvio_filter'] = true; /* colour / size / price… chosen in the Product Filter (see Nexvio_Filter_Query::wc_query) */
		}
		return wc_get_products( $args );
	}

	/* ------------------------------------------------------------------
	 * Wishlist (built-in)
	 * ------------------------------------------------------------------ */

	/** 'none' or 'wishlist'. Old values (yith, local, custom) count as 'wishlist'. */
	protected function wish_mode( $s ) {
		$mode = isset( $s['wish_mode'] ) ? $s['wish_mode'] : 'wishlist';
		return 'none' === $mode ? 'none' : 'wishlist';
	}

	/** Heart button markup, shared by all widgets. */
	protected function heart_button( $product_id, $label, $extra_class = '' ) {
		$on = Nexvio_Wishlist::has( $product_id );
		return '<button type="button" class="nexvio-ps__wish' . ( $extra_class ? ' ' . esc_attr( $extra_class ) : '' ) . ( $on ? ' is-on' : '' ) . '" data-wish="1" data-id="' . esc_attr( $product_id ) . '" aria-pressed="' . ( $on ? 'true' : 'false' ) . '" aria-label="' . esc_attr( $label ) . '">' . $this->svg( 'heart' ) . '</button>';
	}

	protected function wish_settings( $s ) {
		return array(
			'toast'   => 'yes' === $s['wish_toast'],
			'added'   => (string) $s['wish_added'],
			'removed' => (string) $s['wish_removed'],
			'view'    => (string) $s['wish_view'],
			'error'   => (string) $s['wish_error'],
		);
	}

	/* ------------------------------------------------------------------
	 * Output
	 * ------------------------------------------------------------------ */

	/**
	 * One category name for the card badge.
	 * 1. a category the widget / archive is filtered by (product in it or in a sub-category of it)
	 * 2. primary category from Yoast SEO or Rank Math
	 * 3. most specific / top-level / first category
	 */
	protected function card_category( $product, $s ) {
		$id    = $product->get_id();
		$terms = get_the_terms( $id, 'product_cat' );
		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return '';
		}
		$default = (int) get_option( 'default_product_cat', 0 );
		$ctx     = isset( $s['_ctx_cats'] ) ? (array) $s['_ctx_cats'] : array();

		foreach ( $ctx as $slug ) {
			$ct = get_term_by( 'slug', $slug, 'product_cat' );
			if ( ! $ct ) {
				continue;
			}
			foreach ( $terms as $t ) {
				if ( (int) $t->term_id === (int) $ct->term_id || term_is_ancestor_of( $ct, $t, 'product_cat' ) ) {
					return $ct->name;
				}
			}
		}

		foreach ( array( '_yoast_wpseo_primary_product_cat', 'rank_math_primary_product_cat' ) as $key ) {
			$primary = (int) get_post_meta( $id, $key, true );
			if ( $primary ) {
				foreach ( $terms as $t ) {
					if ( (int) $t->term_id === $primary ) {
						return $t->name;
					}
				}
			}
		}

		$list = array();
		foreach ( $terms as $t ) {
			if ( (int) $t->term_id !== $default ) {
				$list[] = $t;
			}
		}
		if ( ! $list ) {
			$list = $terms;
		}
		$pick = isset( $s['cat_pick'] ) ? $s['cat_pick'] : 'specific';
		if ( 'first' === $pick ) {
			usort( $list, function ( $a, $b ) { return strcasecmp( $a->name, $b->name ); } );
			return $list[0]->name;
		}
		$best = null;
		$best_depth = null;
		foreach ( $list as $t ) {
			$depth = count( get_ancestors( $t->term_id, 'product_cat', 'taxonomy' ) );
			if ( null === $best || ( 'top' === $pick ? $depth < $best_depth : $depth > $best_depth ) ) {
				$best       = $t;
				$best_depth = $depth;
			}
		}
		if ( 'top' === $pick && $best_depth > 0 ) {
			$anc  = get_ancestors( $best->term_id, 'product_cat', 'taxonomy' );
			$root = get_term( end( $anc ), 'product_cat' );
			if ( $root && ! is_wp_error( $root ) ) {
				return $root->name;
			}
		}
		return $best->name;
	}

	protected function first_category_name( $id ) {
		$terms = get_the_terms( $id, 'product_cat' );
		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return '';
		}
		$default = (int) get_option( 'default_product_cat', 0 );
		foreach ( $terms as $t ) {
			if ( (int) $t->term_id !== $default ) {
				return $t->name;
			}
		}
		return $terms[0]->name;
	}

	protected function is_preorder( $product, $s ) {
		$id   = $product->get_id();
		$slug = trim( (string) $s['pre_slug'] );
		if ( $slug && ( has_term( $slug, 'product_tag', $id ) || has_term( $slug, 'product_cat', $id ) ) ) {
			return true;
		}
		return 'yes' === $s['pre_backorder'] && $product->is_on_backorder();
	}

	protected function svg( $name ) {
		$icons = array(
			'heart' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 20s-7-4.4-9-9a4.8 4.8 0 0 1 9-3 4.8 4.8 0 0 1 9 3c-2 4.6-9 9-9 9z"/></svg>',
			'plus'  => '<svg class="nexvio-ps__plus" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>',
			'ok'    => '<svg class="nexvio-ps__ok" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg>',
			'arrow' => '<svg class="nexvio-ps__plus" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="m9 5 7 7-7 7"/></svg>',
			'left'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m15 5-7 7 7 7"/></svg>',
			'right' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 5 7 7-7 7"/></svg>',
		);
		return isset( $icons[ $name ] ) ? $icons[ $name ] : '';
	}

	/**
	 * "Add to cart" without opening the product page: the variation to add for a variable product.
	 * The default selection if there is one, otherwise the first variation in stock. Only variations
	 * with every option fixed qualify (an "any" option would need a choice from the shopper).
	 * Returns a WC_Product_Variation or null.
	 */
	protected function quick_variation( $product ) {
		static $cache = array();
		$pid = $product->get_id();
		if ( array_key_exists( $pid, $cache ) ) {
			return $cache[ $pid ];
		}
		$cache[ $pid ] = null;
		$defaults      = (array) $product->get_default_attributes();
		$first         = null;
		foreach ( $product->get_children() as $vid ) {
			$v = wc_get_product( $vid );
			if ( ! $v || ! $v->is_type( 'variation' ) || ! $v->variation_is_visible() || ! $v->is_purchasable() || ! $v->is_in_stock() ) {
				continue;
			}
			$attrs = (array) $v->get_variation_attributes( false );
			if ( ! $attrs || in_array( '', array_map( 'strval', $attrs ), true ) ) {
				continue;
			}
			if ( null === $first ) {
				$first = $v;
			}
			if ( ! $defaults ) {
				break;
			}
			$match = true;
			foreach ( $defaults as $key => $val ) {
				if ( '' !== (string) $val && ( ! isset( $attrs[ $key ] ) || (string) $attrs[ $key ] !== (string) $val ) ) {
					$match = false;
					break;
				}
			}
			if ( $match ) {
				$first = $v;
				break;
			}
		}
		$cache[ $pid ] = $first;
		return $first;
	}

	protected function render_card( $product, $s, $rules, $wrap = 'slide' ) {
		$id    = $product->get_id();
		$link  = $product->get_permalink();
		$name  = $product->get_name();
		$style = '';

		/* an attribute filter is on (colour, size, image…): show the chosen value on the card */
		$pick      = class_exists( 'Nexvio_Swatches' ) ? Nexvio_Swatches::filter_pick( $product ) : null;
		$card_link = ( $pick && $pick['query'] ) ? add_query_arg( $pick['query'], $link ) : $link;

		foreach ( $rules as $rule ) {
			if ( ! empty( $rule['cat'] ) && has_term( $rule['cat'], 'product_cat', $id ) ) {
				$style = '--c1:' . $rule['c1'] . ';--c2:' . $rule['c2'] . ';';
				break;
			}
		}

		/* badges */
		$badges = array();
		if ( 'yes' === $s['show_lang'] && $s['lang_attr'] ) {
			$langs = array_filter( array_map( 'trim', explode( ',', (string) $product->get_attribute( $s['lang_attr'] ) ) ) );
			foreach ( $langs as $l ) {
				$badges[] = '<span class="nexvio-ps__badge nexvio-ps__badge--lang">' . esc_html( $l ) . '</span>';
			}
		}
		if ( isset( $s['show_cat'] ) && 'yes' === $s['show_cat'] ) {
			$cat_name = $this->card_category( $product, $s );
			$max      = isset( $s['cat_max'] ) ? (int) $s['cat_max'] : 0;
			if ( $cat_name && $max > 0 && function_exists( 'mb_strlen' ) && mb_strlen( $cat_name ) > $max ) {
				$cat_name = rtrim( mb_substr( $cat_name, 0, $max ) ) . '…';
			}
			if ( $cat_name ) {
				$badges[] = '<span class="nexvio-ps__badge nexvio-ps__badge--cat">' . esc_html( $cat_name ) . '</span>';
			}
		}
		$preorder = 'yes' === $s['show_pre'] && $this->is_preorder( $product, $s );
		if ( $preorder ) {
			$badges[] = '<span class="nexvio-ps__badge nexvio-ps__badge--pre">' . esc_html( $s['pre_text'] ) . '</span>';
		}
		if ( 'yes' === $s['show_sale'] && $product->is_on_sale() ) {
			$label = $s['sale_text'];
			if ( 'yes' === $s['sale_percent'] ) {
				$reg  = (float) $product->get_regular_price();
				$sale = (float) $product->get_sale_price();
				if ( $reg > 0 && $sale > 0 && $sale < $reg ) {
					$label = '-' . round( ( $reg - $sale ) / $reg * 100 ) . ' %';
				}
			}
			$badges[] = '<span class="nexvio-ps__badge nexvio-ps__badge--sale">' . esc_html( $label ) . '</span>';
		}
		if ( ! $preorder && 'yes' === $s['show_new'] ) {
			$created = $product->get_date_created();
			$days    = max( 1, (int) $s['new_days'] );
			if ( $created && ( time() - $created->getTimestamp() ) < $days * DAY_IN_SECONDS ) {
				$badges[] = '<span class="nexvio-ps__badge nexvio-ps__badge--new">' . esc_html( $s['new_text'] ) . '</span>';
			}
		}

		/* meta line */
		$meta = array();
		if ( 'yes' === $s['show_meta'] ) {
			if ( 'attribute' === $s['set_source'] && $s['set_attr'] ) {
				$meta[] = $product->get_attribute( $s['set_attr'] );
			} elseif ( 'category' === $s['set_source'] ) {
				$meta[] = $this->first_category_name( $id );
			}
			if ( 'yes' === $s['show_cond'] && $s['cond_attr'] ) {
				$meta[] = $product->get_attribute( $s['cond_attr'] );
			}
			$meta = array_filter( array_map( 'trim', $meta ) );
		}

		/* stock */
		$stock_html = '';
		if ( 'yes' === $s['show_stock'] ) {
			$qty = $product->managing_stock() ? $product->get_stock_quantity() : null;
			if ( $preorder || $product->is_on_backorder() ) {
				$stock_html = '<span class="nexvio-ps__stock is-pre">' . esc_html( $s['txt_back'] ) . '</span>';
			} elseif ( ! $product->is_in_stock() ) {
				$stock_html = '<span class="nexvio-ps__stock is-out">' . esc_html( $s['txt_out'] ) . '</span>';
			} elseif ( null !== $qty && $qty <= (int) $s['low_threshold'] ) {
				$stock_html = '<span class="nexvio-ps__stock is-low">' . esc_html( str_replace( '{n}', (string) $qty, $s['txt_low'] ) ) . '</span>';
			} else {
				$stock_html = '<span class="nexvio-ps__stock">' . esc_html( $s['txt_in'] ) . '</span>';
			}
		}

		/* image */
		$size   = $s['image_size'] ? $s['image_size'] : 'woocommerce_thumbnail';
		$img_id = $product->get_image_id();
		$show_id = ( $pick && ! empty( $pick['image_id'] ) ) ? (int) $pick['image_id'] : (int) $img_id;
		$img_att = array( 'class' => 'nexvio-ps__img', 'loading' => 'lazy', 'alt' => $name );
		if ( $show_id && $show_id !== (int) $img_id && $img_id ) {
			/* the swatches can go back to the main image */
			$img_att['data-orig-src']    = (string) wp_get_attachment_image_url( $img_id, $size );
			$img_att['data-orig-srcset'] = (string) wp_get_attachment_image_srcset( $img_id, $size );
		}
		$img    = $show_id
			? wp_get_attachment_image( $show_id, $size, false, $img_att )
			: wc_placeholder_img( $size, array( 'class' => 'nexvio-ps__img' ) );

		/* hover gallery: the product's gallery images, shown over the main image while hovering */
		$gal_html = '';
		if ( isset( $s['gal_on'] ) && 'yes' === $s['gal_on'] ) {
			$cycle = isset( $s['gal_mode'] ) && 'cycle' === $s['gal_mode'];
			$max   = $cycle ? min( 5, max( 1, isset( $s['gal_count'] ) ? (int) $s['gal_count'] : 3 ) ) : 1;
			$n     = 0;
			foreach ( (array) $product->get_gallery_image_ids() as $gid ) {
				$gid = absint( $gid );
				if ( ! $gid || (int) $gid === (int) $img_id ) {
					continue;
				}
				$pic = wp_get_attachment_image( $gid, $size, false, array(
					'class'       => 'nexvio-ps__img nexvio-ps__alt',
					'loading'     => 'lazy',
					'decoding'    => 'async',
					'alt'         => '',
					'aria-hidden' => 'true',
					'data-n'      => (string) ( $n + 1 ),
				) );
				if ( $pic ) {
					$gal_html .= $pic;
					$n++;
				}
				if ( $n >= $max ) {
					break;
				}
			}
		}

		/* wishlist */
		$wish = 'none' !== $this->wish_mode( $s ) ? $this->heart_button( $id, $s['wish_label'] ) : '';

		/* rating: the real WooCommerce average and count; products without reviews never get stars */
		$rating_html = '';
		if ( isset( $s['show_rating'] ) && 'yes' === $s['show_rating'] ) {
			$avg   = (float) $product->get_average_rating();
			$count = (int) $product->get_review_count();
			if ( $count < 1 ) {
				$count = (int) $product->get_rating_count();
			}
			if ( $avg > 0 && $count > 0 ) {
				/* translators: %s: average rating, e.g. 4.5 */
				$sr         = sprintf( __( 'Rated %s out of 5', 'nexvio' ), number_format_i18n( $avg, 1 ) );
				$word       = isset( $s['rating_word'] ) && '' !== trim( (string) $s['rating_word'] ) ? $s['rating_word'] : __( 'reviews', 'nexvio' );
				$show_count = ! isset( $s['rating_count'] ) || 'yes' === $s['rating_count'];
				$rating_html = '<div class="nexvio-ps__rating" style="--nexvio-rating:' . esc_attr( round( $avg / 5 * 100, 1 ) ) . '%">'
					. '<span class="nexvio-ps__stars" aria-hidden="true"></span>'
					. '<span class="nexvio-ps__rnum" aria-hidden="true">' . esc_html( number_format_i18n( $avg, 1 ) ) . '</span>'
					. '<span class="screen-reader-text nexvio-ps__sr">' . esc_html( $sr ) . '</span>'
					. ( $show_count ? '<span class="nexvio-ps__rcount">(' . esc_html( number_format_i18n( $count ) ) . '<span class="nexvio-ps__rword"> ' . esc_html( $word ) . '</span>)</span>' : '' )
					. '</div>';
			} elseif ( isset( $s['rating_empty'] ) && 'yes' === $s['rating_empty'] ) {
				$none        = isset( $s['rating_none_text'] ) && '' !== trim( (string) $s['rating_none_text'] ) ? $s['rating_none_text'] : __( 'No reviews yet', 'nexvio' );
				$rating_html = '<div class="nexvio-ps__rating is-none"><span class="nexvio-ps__rnone">' . esc_html( $none ) . '</span></div>';
			}
		}

		/* colour swatches */
		$swatches_html = '';
		if ( isset( $s['show_swatches'] ) && 'yes' === $s['show_swatches'] && class_exists( 'Nexvio_Swatches' ) ) {
			$sw = Nexvio_Swatches::data( $product, array(
				'attr'   => isset( $s['swatch_attr'] ) ? $s['swatch_attr'] : '',
				'size'   => isset( $s['image_size'] ) ? $s['image_size'] : 'woocommerce_thumbnail',
				'simple' => ! isset( $s['swatch_simple'] ) || 'yes' === $s['swatch_simple'],
			) );
			if ( $sw && $sw['items'] ) {
				$max   = isset( $s['swatch_max'] ) ? max( 1, (int) $s['swatch_max'] ) : 5;
				$shown = array_slice( $sw['items'], 0, $max );
				$extra = count( $sw['items'] ) - count( $shown );
				$use_link = ! isset( $s['swatch_link'] ) || 'yes' === $s['swatch_link'];
				$swatches_html = '<div class="nexvio-ps__swatches" role="group" aria-label="' . esc_attr( $sw['label'] ) . '" data-action="' . esc_attr( isset( $s['swatch_action'] ) && 'hover' === $s['swatch_action'] ? 'hover' : 'click' ) . '">';
				foreach ( $shown as $it ) {
					$bg     = $it['color'] ? $it['color'] : '#d1d5db';
					$light  = in_array( strtolower( $bg ), array( '#fff', '#ffffff', '#fffff0', '#f6f3ea', '#f5ecd7' ), true );
					$pic            = ! empty( $it['swatch'] ) ? $it['swatch'] : '';
					$chosen = $pick && ! empty( $sw['taxonomy'] ) && isset( $pick['picks'][ $sw['taxonomy'] ] ) && ! empty( $it['slug'] ) && $pick['picks'][ $sw['taxonomy'] ] === $it['slug'];
					$swatches_html .= '<button type="button" class="nexvio-ps__sw' . ( $it['color'] || $pic ? '' : ' is-unknown' ) . ( $light ? ' is-light' : '' ) . ( $pic ? ' is-image' : '' ) . '" style="--sw:' . esc_attr( $bg ) . ( $pic ? ';--sw-img:url(' . esc_url( $pic ) . ')' : '' ) . '"'
						. ' title="' . esc_attr( $it['name'] ) . '" aria-label="' . esc_attr( $it['name'] ) . '" aria-pressed="' . ( $chosen ? 'true' : 'false' ) . '"'
						. ( $it['img'] ? ' data-img="' . esc_url( $it['img'] ) . '" data-srcset="' . esc_attr( $it['srcset'] ) . '"' : '' )
						. ( $use_link && $it['link'] ? ' data-link="' . esc_url( $it['link'] ) . '"' : '' )
						. '>' . ( $it['color'] || $pic ? '' : '<span>' . esc_html( function_exists( 'mb_substr' ) ? mb_substr( $it['name'], 0, 1 ) : substr( $it['name'], 0, 1 ) ) . '</span>' ) . '</button>';
				}
				if ( $extra > 0 ) {
					$swatches_html .= '<a class="nexvio-ps__sw-more" href="' . esc_url( $link ) . '">+' . (int) $extra . '</a>';
				}
				$swatches_html .= '</div>';
			}
		}

		/* short description: the product's WooCommerce short description */
		$excerpt   = '';
		$want_desc = isset( $s['show_desc'] ) && 'yes' === $s['show_desc'];
		$want_list = isset( $s['list_excerpt'], $s['view_mode'] ) && 'yes' === $s['list_excerpt']; /* Products Archive list view (1.3) */
		if ( $want_desc || $want_list ) {
			$raw = (string) $product->get_short_description();
			$fallback = $want_desc ? ( isset( $s['desc_fallback'] ) && 'yes' === $s['desc_fallback'] ) : true;
			if ( '' === trim( wp_strip_all_tags( $raw ) ) && $fallback ) {
				$raw = (string) $product->get_description();
			}
			$text = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( strip_shortcodes( $raw ) ) ) );
			if ( '' !== $text ) {
				$words   = $want_desc ? ( isset( $s['desc_words'] ) ? (int) $s['desc_words'] : 15 ) : ( isset( $s['list_words'] ) ? max( 5, (int) $s['list_words'] ) : 24 );
				$excerpt = $words > 0 ? wp_trim_words( $text, $words, '…' ) : $text;
			}
		}

		/* button (the text label is shown only by designs with a text button) */
		$button    = '';
		$lbl_add   = isset( $s['btn_text'] ) && '' !== trim( (string) $s['btn_text'] ) ? $s['btn_text'] : $s['btn_label'];
		$lbl_opt   = $s['btn_label_options'];
		$lbl_out   = isset( $s['txt_out'] ) ? $s['txt_out'] : __( 'Sold out', 'nexvio' );
		$label_tpl = '<span class="nexvio-ps__add-label">%s</span>';
		if ( 'yes' === $s['show_button'] ) {
			$quick = ( isset( $s['btn_action'] ) && 'cart' === $s['btn_action'] && $product->is_type( 'variable' ) && $product->is_purchasable() && $product->is_in_stock() ) ? $this->quick_variation( $product ) : null;
			if ( ! $product->is_purchasable() || ! $product->is_in_stock() ) {
				$button = '<span class="nexvio-ps__add is-disabled" aria-hidden="true">' . $this->svg( 'plus' ) . sprintf( $label_tpl, esc_html( $lbl_out ) ) . '</span>';
			} elseif ( $quick ) {
				/* WooCommerce's own AJAX add to cart takes a variation ID as the product ID */
				$button = sprintf(
					'<a href="%s" data-quantity="1" data-product_id="%d" data-product_sku="%s" class="nexvio-ps__add add_to_cart_button ajax_add_to_cart product_type_variable" rel="nofollow" aria-label="%s">%s%s%s</a>',
					esc_url( $quick->add_to_cart_url() ),
					(int) $quick->get_id(),
					esc_attr( $product->get_sku() ),
					esc_attr( $s['btn_label'] . ' : ' . $name ),
					$this->svg( 'plus' ),
					$this->svg( 'ok' ),
					sprintf( $label_tpl, esc_html( $lbl_add ) )
				);
			} elseif ( $product->is_type( 'simple' ) && $product->supports( 'ajax_add_to_cart' ) ) {
				$button = sprintf(
					'<a href="%s" data-quantity="1" data-product_id="%d" data-product_sku="%s" class="nexvio-ps__add add_to_cart_button ajax_add_to_cart product_type_simple" rel="nofollow" aria-label="%s">%s%s%s</a>',
					esc_url( $product->add_to_cart_url() ),
					(int) $id,
					esc_attr( $product->get_sku() ),
					esc_attr( $s['btn_label'] . ' : ' . $name ),
					$this->svg( 'plus' ),
					$this->svg( 'ok' ),
					sprintf( $label_tpl, esc_html( $lbl_add ) )
				);
			} else {
				$button = sprintf(
					'<a href="%s" class="nexvio-ps__add" aria-label="%s">%s%s</a>',
					esc_url( $product->add_to_cart_url() ),
					esc_attr( $lbl_opt . ' : ' . $name ),
					$this->svg( 'arrow' ),
					sprintf( $label_tpl, esc_html( $lbl_opt ) )
				);
			}
		}
		?>
		<?php if ( 'li' === $wrap ) : ?>
		<li class="<?php echo esc_attr( implode( ' ', wc_get_product_class( 'nexvio-pg__item', $product ) ) ); ?>">
		<?php else : ?>
		<div class="nexvio-ps__slide">
		<?php endif; ?>
			<article class="nexvio-ps__card<?php echo $gal_html ? ' nexvio-ps__card--gal' : ''; ?><?php echo ( $pick && ! empty( $pick['image_id'] ) ) ? ' is-sw' : ''; ?>">
				<?php if ( $badges ) : ?>
					<div class="nexvio-ps__badges"><?php echo wp_kses( implode( '', $badges ), Nexvio_Plugin::allowed_html() ); ?></div>
				<?php endif; ?>
				<?php echo wp_kses( $wish, Nexvio_Plugin::allowed_html() ); ?>
				<a class="nexvio-ps__media" href="<?php echo esc_url( $card_link ); ?>"<?php echo $card_link !== $link ? ' data-orig-href="' . esc_url( $link ) . '"' : ''; ?> tabindex="-1" aria-hidden="true"<?php echo $style ? ' style="' . esc_attr( $style ) . '"' : ''; ?>>
					<?php echo wp_kses( $img, Nexvio_Plugin::allowed_html() ); ?>
					<?php echo wp_kses( $gal_html, Nexvio_Plugin::allowed_html() ); ?>
				</a>
				<div class="nexvio-ps__body">
					<?php echo wp_kses( $swatches_html, Nexvio_Plugin::allowed_html() ); ?>
					<?php if ( $meta ) : ?>
						<span class="nexvio-ps__meta"><?php echo esc_html( implode( $s['meta_sep'], $meta ) ); ?></span>
					<?php endif; ?>
					<h3 class="nexvio-ps__name"><a href="<?php echo esc_url( $card_link ); ?>"<?php echo $card_link !== $link ? ' data-orig-href="' . esc_url( $link ) . '"' : ''; ?>><?php echo esc_html( $name ); ?></a></h3>
					<?php echo wp_kses( $rating_html, Nexvio_Plugin::allowed_html() ); ?>
					<?php if ( '' !== $excerpt ) : ?>
						<p class="nexvio-ps__excerpt"><?php echo esc_html( $excerpt ); ?></p>
					<?php elseif ( $want_desc && ( ! isset( $s['desc_even'] ) || 'yes' === $s['desc_even'] ) ) : ?>
						<p class="nexvio-ps__excerpt is-empty" aria-hidden="true"></p>
					<?php endif; ?>
					<?php echo wp_kses( $stock_html, Nexvio_Plugin::allowed_html() ); ?>
					<?php if ( 'yes' === $s['show_price'] || $button ) : ?>
						<div class="nexvio-ps__row">
							<?php if ( 'yes' === $s['show_price'] ) : ?>
								<span class="nexvio-ps__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
							<?php endif; ?>
							<?php echo wp_kses( $button, Nexvio_Plugin::allowed_html() ); ?>
						</div>
					<?php endif; ?>
				</div>
			</article>
		<?php echo 'li' === $wrap ? '</li>' : '</div>'; ?>
		<?php
	}

	/* ------------------------------------------------------------------
	 * Small helpers for the Style tab
	 * ------------------------------------------------------------------ */

	/** Colour control that writes real CSS (works for every design). */
	protected function gs_color( $id, $label, $selectors, $condition = array(), $extra = array() ) {
		$args = array_merge( array(
			'label'     => $label,
			'type'      => Controls_Manager::COLOR,
			'default'   => '',
			'selectors' => $selectors,
		), $extra );
		if ( $condition ) {
			$args['condition'] = $condition;
		}
		$this->add_control( $id, $args );
	}

	protected function gs_heading( $id, $label, $condition = array(), $separator = 'before' ) {
		$args = array(
			'label' => $label,
			'type'  => Controls_Manager::HEADING,
		);
		if ( $separator ) {
			$args['separator'] = $separator;
		}
		if ( $condition ) {
			$args['condition'] = $condition;
		}
		$this->add_control( $id, $args );
	}

	protected function gs_typo( $id, $label, $selector, $condition = array() ) {
		$args = array( 'name' => $id, 'label' => $label, 'selector' => $selector );
		if ( $condition ) {
			$args['condition'] = $condition;
		}
		$this->add_group_control( Group_Control_Typography::get_type(), $args );
	}

	/** Space above / below (margin top and bottom). */
	protected function gs_space( $id, $label, $selector, $condition = array() ) {
		$args = array(
			'label'              => $label,
			'type'               => Controls_Manager::DIMENSIONS,
			'size_units'         => array( 'px' ),
			'allowed_dimensions' => 'vertical',
			'selectors'          => array( $selector => 'margin-top: {{TOP}}{{UNIT}}; margin-bottom: {{BOTTOM}}{{UNIT}};' ),
		);
		if ( $condition ) {
			$args['condition'] = $condition;
		}
		$this->add_responsive_control( $id, $args );
	}

	/** Four-side box control (top, right, bottom, left). */
	protected function gs_box( $id, $label, $selector, $property, $units = array( 'px', '%', 'em' ), $condition = array(), $extra_css = '' ) {
		$args = array(
			'label'      => $label,
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => $units,
			'selectors'  => array( $selector => $property . ': {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' . $extra_css ),
		);
		if ( $condition ) {
			$args['condition'] = $condition;
		}
		$this->add_responsive_control( $id, $args );
	}

	/* ------------------------------------------------------------------
	 * 1. Layout design: card background, border, radius, text padding, shadow
	 * ------------------------------------------------------------------ */

	protected function register_gs_layout_section() {
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
		$this->gs_color( 'gs_card_border_h', esc_html__( 'Border colour on hover', 'nexvio' ), array( $card . ':hover' => 'border-color: {{VALUE}};' ) );
		$this->gs_box( 'gs_card_radius', esc_html__( 'Corner radius', 'nexvio' ), $card, 'border-radius', array( 'px', '%' ) );

		$this->gs_box( 'gs_body_pad', esc_html__( 'Text area padding', 'nexvio' ), '{{WRAPPER}} .nexvio-ps .nexvio-ps__card .nexvio-ps__body', 'padding', array( 'px', '%', 'em' ) );

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
	 * 2. Image (image controls + image area + fit)
	 * ------------------------------------------------------------------ */

	protected function register_gs_image_section() {
		$img   = '{{WRAPPER}} .nexvio-ps .nexvio-ps__media .nexvio-ps__img';
		$img_h = '{{WRAPPER}} .nexvio-ps .nexvio-ps__card:hover .nexvio-ps__media .nexvio-ps__img';

		$this->start_controls_section( 'style_image', array(
			'label' => esc_html__( 'Image', 'nexvio' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		/* --- fit: default / cover / contain --- */
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
				'{{WRAPPER}} .nexvio-ps' => '--nexvio-l-fit: {{VALUE}};',
				$img                     => 'object-fit: {{VALUE}};',
			),
		) );

		/* --- same controls as the Elementor Image widget --- */
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
			'selectors' => array( '{{WRAPPER}} .nexvio-ps' => '--nexvio-img-op: {{SIZE}};' ),
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
			'selectors' => array( '{{WRAPPER}} .nexvio-ps' => '--nexvio-img-op-h: {{SIZE}};' ),
		) );
		$this->add_group_control( Group_Control_Css_Filter::get_type(), array(
			'name'     => 'img_filters_h',
			'selector' => $img_h,
		) );
		$this->add_control( 'img_td', array(
			'label'     => esc_html__( 'Transition Duration (s)', 'nexvio' ),
			'type'      => Controls_Manager::SLIDER,
			'range'     => array( 'px' => array( 'min' => 0, 'max' => 3, 'step' => 0.1 ) ),
			'selectors' => array( '{{WRAPPER}} .nexvio-ps .nexvio-ps__media .nexvio-ps__img:not(.nexvio-ps__alt)' => 'transition-duration: {{SIZE}}s;' ),
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

		/* --- image area (was a separate section; now part of Image) --- */
		$this->gs_heading( 'gs_h_area', esc_html__( 'Image area', 'nexvio' ) );

		/* classic design (limit_classic_controls adds the "classic" condition) */
		$this->add_control( 'ratio', array(
			'label'     => esc_html__( 'Shape', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => '1/1',
			'options'   => array( '1/1' => esc_html__( 'Square', 'nexvio' ), '4/5' => '4:5', '3/4' => '3:4', '63/88' => esc_html__( 'Card (63:88)', 'nexvio' ), '4/3' => '4:3' ),
			'selectors' => array( '{{WRAPPER}} .nexvio-ps' => '--nexvio-ps-ratio: {{VALUE}};' ),
		) );
		/* every other design */
		$this->add_control( 'l_ratio', array(
			'label'     => esc_html__( 'Shape', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => '',
			'options'   => array( '' => esc_html__( 'Design default', 'nexvio' ), '1/1' => esc_html__( 'Square', 'nexvio' ), '4/5' => '4:5', '3/4' => '3:4', '63/88' => esc_html__( 'Card (63:88)', 'nexvio' ), '4/3' => '4:3', '16/9' => '16:9' ),
			'selectors' => array( '{{WRAPPER}} .nexvio-ps' => '--nexvio-l-ratio: {{VALUE}};' ),
			'condition' => array( 'design_layout!' => array( 'classic', 'horizontal' ) ),
		) );
		$this->add_responsive_control( 'l_media_w', array(
			'label'      => esc_html__( 'Image width (beside the details)', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( '%' ),
			'range'      => array( '%' => array( 'min' => 25, 'max' => 60 ) ),
			'selectors'  => array( '{{WRAPPER}} .nexvio-ps' => '--nexvio-l-media-w: {{SIZE}}%;' ),
			'condition'  => array( 'design_layout' => 'horizontal' ),
		) );
		$this->add_responsive_control( 'img_gap', array(
			'label'       => esc_html__( 'Space between image and details', 'nexvio' ),
			'description' => esc_html__( 'Exact distance from the image to the text. Works with the Horizontal design (when the image is beside the details).', 'nexvio' ),
			'type'        => Controls_Manager::SLIDER,
			'size_units'  => array( 'px' ),
			'range'       => array( 'px' => array( 'min' => 0, 'max' => 80 ) ),
			'selectors'   => array( '{{WRAPPER}} .nexvio-ps.nexvio-ps--layout-horizontal .nexvio-ps__card' => 'column-gap: {{SIZE}}{{UNIT}};' ),
			'condition'   => array( 'design_layout' => 'horizontal' ),
		) );
		$this->add_control( 'l_img_pad', array(
			'label'     => esc_html__( 'Space around the image (%)', 'nexvio' ),
			'type'      => Controls_Manager::NUMBER,
			'default'   => '',
			'min'       => 0,
			'max'       => 30,
			'selectors' => array( '{{WRAPPER}} .nexvio-ps' => '--nexvio-l-img-pad: {{VALUE}}%;' ),
			'condition' => array( 'design_layout!' => 'classic' ),
		) );
		$this->add_control( 'l_img_radius', array(
			'label'     => esc_html__( 'Image area corner radius (px)', 'nexvio' ),
			'type'      => Controls_Manager::NUMBER,
			'default'   => '',
			'min'       => 0,
			'max'       => 60,
			'selectors' => array( '{{WRAPPER}} .nexvio-ps' => '--nexvio-l-img-radius: {{VALUE}}px;' ),
			'condition' => array( 'design_layout!' => 'classic' ),
		) );
		$this->add_control( 'l_media', array(
			'label'     => esc_html__( 'Image area background', 'nexvio' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '',
			'selectors' => array( '{{WRAPPER}} .nexvio-ps' => '--nexvio-l-media: {{VALUE}};' ),
			'condition' => array( 'design_layout!' => 'classic' ),
		) );

		/* classic design: glow, gradient, pattern, framed product image */
		$this->color_var( 'c1', esc_html__( 'Glow colour', 'nexvio' ), '--nexvio-ps-c1', '#4d8dff' );
		$this->color_var( 'c2', esc_html__( 'Gradient colour', 'nexvio' ), '--nexvio-ps-c2', '#5b2fb0' );
		$this->color_var( 'base', esc_html__( 'Base colour', 'nexvio' ), '--nexvio-ps-base', '#141920' );
		$this->switcher( 'pattern', esc_html__( 'Hexagon pattern', 'nexvio' ), 'yes', array( 'separator' => 'before' ) );
		$this->number_var( 'pattern_op', esc_html__( 'Pattern strength (0 to 3)', 'nexvio' ), '--nexvio-ps-pattern-op', 1, 0, 3, 0.1, '', array( 'pattern' => 'yes' ) );
		$this->add_control( 'h_img', array( 'label' => esc_html__( 'Product image', 'nexvio' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before', 'condition' => array( 'image_mode' => 'contain' ) ) );
		$this->add_responsive_control( 'img_w', array(
			'label'      => esc_html__( 'Image width', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( '%' ),
			'range'      => array( '%' => array( 'min' => 20, 'max' => 100 ) ),
			'default'    => array( 'size' => 52, 'unit' => '%' ),
			'selectors'  => array( '{{WRAPPER}} .nexvio-ps' => '--nexvio-ps-img-w: {{SIZE}}%;' ),
			'condition'  => array( 'image_mode' => 'contain' ),
		) );
		$this->number_var( 'img_radius', esc_html__( 'Image corner radius (px)', 'nexvio' ), '--nexvio-ps-img-radius', 8, 0, 40, 1, 'px', array( 'image_mode' => 'contain' ) );
		$this->switcher( 'frame', esc_html__( 'Gold frame', 'nexvio' ), 'yes', array( 'condition' => array( 'image_mode' => 'contain' ) ) );
		$this->color_var( 'frame_c', esc_html__( 'Frame colour', 'nexvio' ), '--nexvio-ps-frame-c', 'rgba(255,236,170,0.75)', array( 'image_mode' => 'contain', 'frame' => 'yes' ) );
		$this->number_var( 'frame_w', esc_html__( 'Frame thickness (px)', 'nexvio' ), '--nexvio-ps-frame-w', 4, 0, 20, 1, 'px', array( 'image_mode' => 'contain', 'frame' => 'yes' ) );
		$this->switcher( 'img_shadow', esc_html__( 'Image shadow', 'nexvio' ), 'yes', array( 'condition' => array( 'image_mode' => 'contain' ) ) );
		$this->add_control( 'hover_fx', array(
			'label'     => esc_html__( 'Hover effect', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'lift',
			'options'   => array( 'lift' => esc_html__( 'Lift and tilt', 'nexvio' ), 'zoom' => esc_html__( 'Zoom', 'nexvio' ), 'none' => esc_html__( 'None', 'nexvio' ) ),
			'separator' => 'before',
			'condition' => array( 'design_layout' => 'classic' ),
		) );

		$this->end_controls_section();
	}

	/* ------------------------------------------------------------------
	 * 3. Card Content: everything the card shows about a product
	 * ------------------------------------------------------------------ */

	protected function register_gs_content_section() {
		$ps = '{{WRAPPER}} .nexvio-ps';

		$this->start_controls_section( 'style_card_content', array(
			'label' => esc_html__( 'Card Content', 'nexvio' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_responsive_control( 'text_gap', array(
			'label'       => esc_html__( 'Space between all text lines', 'nexvio' ),
			'description' => esc_html__( 'Space between set line, name, rating, description, stock and price row.', 'nexvio' ),
			'type'        => Controls_Manager::SLIDER,
			'size_units'  => array( 'px' ),
			'range'       => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
			'selectors'   => array( $ps . ' .nexvio-ps__card .nexvio-ps__body' => 'gap: {{SIZE}}{{UNIT}}; row-gap: {{SIZE}}{{UNIT}};' ),
		) );

		/* ---- Title ---- */
		$name = $ps . ' .nexvio-ps__card .nexvio-ps__name';
		$this->gs_heading( 'gs_h_title', esc_html__( 'Title', 'nexvio' ) );
		$this->gs_typo( 'name_typo', esc_html__( 'Title typography', 'nexvio' ), $name );
		$this->gs_color( 'gs_title_color', esc_html__( 'Title colour', 'nexvio' ), array( $name => 'color: {{VALUE}};' ) );
		$this->gs_color( 'gs_title_color_h', esc_html__( 'Title colour on hover', 'nexvio' ), array( $name . ' a:hover' => 'color: {{VALUE}};' ) );
		$this->add_group_control( Group_Control_Text_Shadow::get_type(), array(
			'name'     => 'gs_title_shadow',
			'selector' => $name,
		) );
		$this->gs_space( 'gs_title_space', esc_html__( 'Title spacing (top / bottom)', 'nexvio' ), $name );

		/* ---- Set / condition line ---- */
		$meta = $ps . ' .nexvio-ps__card .nexvio-ps__meta';
		$cm   = array( 'show_meta' => 'yes' );
		$this->gs_heading( 'gs_h_meta', esc_html__( 'Set / condition line', 'nexvio' ), $cm );
		$this->gs_typo( 'meta_typo', esc_html__( 'Typography', 'nexvio' ), $meta, $cm );
		$this->gs_color( 'gs_meta_color', esc_html__( 'Colour', 'nexvio' ), array( $meta => 'color: {{VALUE}};' ), $cm );
		$this->gs_space( 'gs_meta_space', esc_html__( 'Spacing (top / bottom)', 'nexvio' ), $meta, $cm );

		/* ---- Description ---- */
		$exc = $ps . ' .nexvio-ps__card .nexvio-ps__excerpt';
		$cd  = $this->get_controls( 'list_excerpt' ) ? array() : array( 'show_desc' => 'yes' );
		$this->gs_heading( 'gs_h_desc', esc_html__( 'Description', 'nexvio' ), $cd );
		$this->gs_typo( 'desc_typo', esc_html__( 'Description typography', 'nexvio' ), $exc, $cd );
		$this->gs_color( 'desc_color', esc_html__( 'Description colour', 'nexvio' ), array( $exc => 'color: {{VALUE}};' ), $cd );
		$this->add_responsive_control( 'desc_align', array(
			'label'     => esc_html__( 'Alignment', 'nexvio' ),
			'type'      => Controls_Manager::CHOOSE,
			'options'   => array(
				'left'    => array( 'title' => esc_html__( 'Left', 'nexvio' ), 'icon' => 'eicon-text-align-left' ),
				'center'  => array( 'title' => esc_html__( 'Center', 'nexvio' ), 'icon' => 'eicon-text-align-center' ),
				'right'   => array( 'title' => esc_html__( 'Right', 'nexvio' ), 'icon' => 'eicon-text-align-right' ),
				'justify' => array( 'title' => esc_html__( 'Justify', 'nexvio' ), 'icon' => 'eicon-text-align-justify' ),
			),
			'selectors' => array( $exc => 'text-align: {{VALUE}};' ),
			'condition' => $cd,
		) );
		$this->gs_space( 'desc_space', esc_html__( 'Description spacing (top / bottom)', 'nexvio' ), $exc, $cd );
		$this->add_responsive_control( 'desc_lines', array(
			'label'       => esc_html__( 'Maximum lines', 'nexvio' ),
			'type'        => Controls_Manager::NUMBER,
			'min'         => 0,
			'max'         => 10,
			'default'     => 3,
			'description' => esc_html__( 'Longer text ends with "…". 0 = no limit.', 'nexvio' ),
			'selectors'   => array( $ps => '--nexvio-desc-lines: {{VALUE}};' ),
			'condition'   => $cd,
		) );
		$this->switcher( 'desc_even', esc_html__( 'Same height on every card', 'nexvio' ), 'yes', array_merge( array(
			'description' => esc_html__( 'Keeps names, prices and buttons lined up across a row, even when some descriptions are shorter.', 'nexvio' ),
		), $cd ? array( 'condition' => $cd ) : array() ) );

		/* ---- Price ---- */
		$price = $ps . ' .nexvio-ps__card .nexvio-ps__price';
		$this->gs_heading( 'gs_h_price', esc_html__( 'Price', 'nexvio' ) );
		$this->gs_typo( 'price_typo', esc_html__( 'Price typography', 'nexvio' ), $price );
		$this->gs_color( 'gs_price_color', esc_html__( 'Price colour', 'nexvio' ), array( $price . ', ' . $price . ' ins' => 'color: {{VALUE}};' ) );
		$this->gs_color( 'gs_old_color', esc_html__( 'Old price colour (on sale)', 'nexvio' ), array( $price . ' del, ' . $price . ' del .amount' => 'color: {{VALUE}};' ) );
		$this->gs_space( 'gs_price_space', esc_html__( 'Price spacing (top / bottom)', 'nexvio' ), $price );

		/* ---- Stock ---- */
		$stock = $ps . ' .nexvio-ps__card .nexvio-ps__stock';
		$cs    = array( 'show_stock' => 'yes' );
		$this->gs_heading( 'gs_h_stock', esc_html__( 'Stock', 'nexvio' ), $cs );
		$this->gs_typo( 'stock_typo', esc_html__( 'Stock typography', 'nexvio' ), $stock, $cs );
		$this->gs_color( 'ok_c', esc_html__( 'In stock', 'nexvio' ), array( $ps => '--nexvio-ps-ok: {{VALUE}};' ), $cs );
		$this->gs_color( 'low_c', esc_html__( 'Low stock / pre-order', 'nexvio' ), array( $ps => '--nexvio-ps-low: {{VALUE}};' ), $cs );
		$this->gs_color( 'out_c', esc_html__( 'Sold out', 'nexvio' ), array( $ps => '--nexvio-ps-out: {{VALUE}};' ), $cs );
		$this->gs_space( 'gs_stock_space', esc_html__( 'Stock spacing (top / bottom)', 'nexvio' ), $stock, $cs );

		/* ---- Rating ---- */
		$rate = $ps . ' .nexvio-ps__card .nexvio-ps__rating';
		$cr   = array( 'show_rating' => 'yes' );
		$this->gs_heading( 'gs_h_rating', esc_html__( 'Rating', 'nexvio' ), $cr );
		$this->add_control( 'rating_pos', array(
			'label'                => esc_html__( 'Position', 'nexvio' ),
			'type'                 => Controls_Manager::SELECT,
			'default'              => '',
			'options'              => array(
				''      => esc_html__( 'Below the title (default)', 'nexvio' ),
				'above' => esc_html__( 'Above the title', 'nexvio' ),
				'image' => esc_html__( 'On the image', 'nexvio' ),
			),
			'selectors_dictionary' => array(
				'above' => 'order: -1;',
				'image' => 'position: absolute; z-index: 3; margin: 0; width: auto;',
			),
			'selectors'            => array( $rate => '{{VALUE}}' ),
			'condition'            => $cr,
		) );
		$ci = array( 'show_rating' => 'yes', 'rating_pos' => 'image' );
		$this->add_control( 'rating_side', array(
			'label'                => esc_html__( 'Side of the image', 'nexvio' ),
			'type'                 => Controls_Manager::CHOOSE,
			'default'              => 'left',
			'options'              => array(
				'left'  => array( 'title' => esc_html__( 'Left', 'nexvio' ), 'icon' => 'eicon-h-align-left' ),
				'right' => array( 'title' => esc_html__( 'Right', 'nexvio' ), 'icon' => 'eicon-h-align-right' ),
			),
			'selectors_dictionary' => array(
				'left'  => 'left: var(--nexvio-r-x, 10px); right: auto;',
				'right' => 'right: var(--nexvio-r-x, 10px); left: auto;',
			),
			'selectors'            => array( $rate => '{{VALUE}}' ),
			'condition'            => $ci,
		) );
		$this->add_responsive_control( 'rating_x', array(
			'label'      => esc_html__( 'Distance from the side', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 120 ) ),
			'selectors'  => array( $rate => '--nexvio-r-x: {{SIZE}}{{UNIT}};' ),
			'condition'  => $ci,
		) );
		$this->add_responsive_control( 'rating_y', array(
			'label'       => esc_html__( 'Distance from the top', 'nexvio' ),
			'description' => esc_html__( 'Badges are at the top, so the default leaves room for them.', 'nexvio' ),
			'type'        => Controls_Manager::SLIDER,
			'size_units'  => array( 'px' ),
			'range'       => array( 'px' => array( 'min' => 0, 'max' => 300 ) ),
			'default'     => array( 'size' => 44, 'unit' => 'px' ),
			'selectors'   => array( $rate => 'top: {{SIZE}}{{UNIT}};' ),
			'condition'   => $ci,
		) );
		$this->gs_color( 'rating_bg', esc_html__( 'Background', 'nexvio' ), array( $rate => 'background-color: {{VALUE}};' ), $ci );
		$this->gs_box( 'rating_pad', esc_html__( 'Padding', 'nexvio' ), $rate, 'padding', array( 'px', 'em' ), $ci );
		$this->gs_box( 'rating_rad', esc_html__( 'Border radius', 'nexvio' ), $rate, 'border-radius', array( 'px', '%' ), $ci );
		$this->gs_color( 'star_color', esc_html__( 'Star colour', 'nexvio' ), array( $ps => '--nexvio-l-star: {{VALUE}};' ), $cr );
		$this->gs_color( 'star_empty', esc_html__( 'Empty star colour', 'nexvio' ), array( $ps => '--nexvio-star-empty: {{VALUE}};' ), $cr );
		$this->add_responsive_control( 'star_size', array(
			'label'      => esc_html__( 'Star size', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 8, 'max' => 32 ) ),
			'selectors'  => array( $ps => '--nexvio-star-size: {{SIZE}}{{UNIT}};' ),
			'condition'  => $cr,
		) );
		$this->add_responsive_control( 'rating_align', array(
			'label'     => esc_html__( 'Alignment', 'nexvio' ),
			'type'      => Controls_Manager::CHOOSE,
			'options'   => array(
				'flex-start' => array( 'title' => esc_html__( 'Left', 'nexvio' ), 'icon' => 'eicon-text-align-left' ),
				'center'     => array( 'title' => esc_html__( 'Center', 'nexvio' ), 'icon' => 'eicon-text-align-center' ),
				'flex-end'   => array( 'title' => esc_html__( 'Right', 'nexvio' ), 'icon' => 'eicon-text-align-right' ),
			),
			'selectors' => array( $rate => 'justify-content: {{VALUE}};' ),
			'condition' => $cr,
		) );
		$this->add_responsive_control( 'rating_gap', array(
			'label'      => esc_html__( 'Space between stars and count', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 20 ) ),
			'selectors'  => array( $rate => 'gap: {{SIZE}}{{UNIT}};' ),
			'condition'  => $cr,
		) );
		$this->gs_space( 'rating_space', esc_html__( 'Rating spacing (top / bottom)', 'nexvio' ), $rate, $cr );
		$this->gs_color( 'rating_text_color', esc_html__( 'Count / average colour', 'nexvio' ), array( $rate => 'color: {{VALUE}};' ), $cr );
		$this->gs_typo( 'rating_typo', esc_html__( 'Count / average typography', 'nexvio' ), $rate, $cr );

		/* ---- Colour swatches ---- */
		$csw = array( 'show_swatches' => 'yes' );
		$this->gs_heading( 'gs_h_sw', esc_html__( 'Colour swatches', 'nexvio' ), $csw );
		$this->add_responsive_control( 'sw_size', array(
			'label'      => esc_html__( 'Size', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 10, 'max' => 40 ) ),
			'default'    => array( 'size' => 16, 'unit' => 'px' ),
			'selectors'  => array( $ps => '--nexvio-sw-size: {{SIZE}}{{UNIT}};' ),
			'condition'  => $csw,
		) );
		$this->add_responsive_control( 'sw_gap', array(
			'label'      => esc_html__( 'Space between', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 20 ) ),
			'default'    => array( 'size' => 6, 'unit' => 'px' ),
			'selectors'  => array( $ps => '--nexvio-sw-gap: {{SIZE}}{{UNIT}};' ),
			'condition'  => $csw,
		) );
		$this->add_control( 'sw_shape', array(
			'label'                => esc_html__( 'Shape', 'nexvio' ),
			'type'                 => Controls_Manager::SELECT,
			'default'              => 'circle',
			'options'              => array( 'circle' => esc_html__( 'Circle', 'nexvio' ), 'rounded' => esc_html__( 'Rounded square', 'nexvio' ), 'square' => esc_html__( 'Square', 'nexvio' ) ),
			'selectors_dictionary' => array( 'circle' => '50%', 'rounded' => '4px', 'square' => '0' ),
			'selectors'            => array( $ps => '--nexvio-sw-radius: {{VALUE}};' ),
			'condition'            => $csw,
		) );
		$this->add_responsive_control( 'sw_align', array(
			'label'     => esc_html__( 'Alignment', 'nexvio' ),
			'type'      => Controls_Manager::CHOOSE,
			'options'   => array(
				'flex-start' => array( 'title' => esc_html__( 'Left', 'nexvio' ), 'icon' => 'eicon-text-align-left' ),
				'center'     => array( 'title' => esc_html__( 'Center', 'nexvio' ), 'icon' => 'eicon-text-align-center' ),
				'flex-end'   => array( 'title' => esc_html__( 'Right', 'nexvio' ), 'icon' => 'eicon-text-align-right' ),
			),
			'selectors' => array( $ps => '--nexvio-sw-justify: {{VALUE}};' ),
			'condition' => $csw,
		) );
		$this->gs_color( 'sw_border', esc_html__( 'Border', 'nexvio' ), array( $ps => '--nexvio-sw-border: {{VALUE}};' ), $csw );
		$this->gs_color( 'sw_ring', esc_html__( 'Selected ring', 'nexvio' ), array( $ps => '--nexvio-sw-ring: {{VALUE}};' ), $csw );
		$this->gs_color( 'sw_more', esc_html__( '"+3" text', 'nexvio' ), array( $ps => '--nexvio-sw-more: {{VALUE}};' ), $csw );

		$this->end_controls_section();
	}

	/* ------------------------------------------------------------------
	 * 4. Button (shown only when the add-to-cart button is on)
	 * ------------------------------------------------------------------ */

	protected function register_gs_button_section() {
		$btn   = '{{WRAPPER}} .nexvio-ps .nexvio-ps__add';
		$on    = $btn . ':not(.is-disabled)';
		$on_h  = $on . ':hover';

		$this->start_controls_section( 'style_button', array(
			'label'     => esc_html__( 'Button', 'nexvio' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'show_button' => 'yes' ),
		) );

		$this->gs_typo( 'gs_btn_typo', esc_html__( 'Font (family, size, weight…)', 'nexvio' ), $btn . ' .nexvio-ps__add-label' );
		$this->add_control( 'gs_btn_typo_note', array(
			'type'            => Controls_Manager::RAW_HTML,
			'raw'             => esc_html__( 'Font applies to the button text. Turn on the text button in Design Layout > Add-to-cart button.', 'nexvio' ),
			'content_classes' => 'elementor-descriptor',
		) );

		$this->start_controls_tabs( 'gs_btn_tabs' );

		/* normal */
		$this->start_controls_tab( 'gs_btn_normal', array( 'label' => esc_html__( 'Normal', 'nexvio' ) ) );
		$this->add_group_control( Group_Control_Background::get_type(), array(
			'name'           => 'btn_bg',
			'label'          => esc_html__( 'Background', 'nexvio' ),
			'types'          => array( 'classic', 'gradient' ),
			'selector'       => $on,
			'fields_options' => array(
				'background' => array( 'label' => esc_html__( 'Background type', 'nexvio' ) ),
			),
		) );
		$this->gs_color( 'gs_btn_text', esc_html__( 'Text colour', 'nexvio' ), array( $on => 'color: {{VALUE}};' ) );
		$this->gs_color( 'gs_btn_icon', esc_html__( 'Icon colour', 'nexvio' ), array( $on . ' svg' => 'color: {{VALUE}};' ) );
		$this->end_controls_tab();

		/* hover */
		$this->start_controls_tab( 'gs_btn_hover', array( 'label' => esc_html__( 'Hover', 'nexvio' ) ) );
		$this->add_group_control( Group_Control_Background::get_type(), array(
			'name'           => 'btn_bg_h',
			'label'          => esc_html__( 'Background', 'nexvio' ),
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

		$this->gs_box( 'gs_btn_pad', esc_html__( 'Padding', 'nexvio' ), $btn, 'padding', array( 'px', 'em', '%' ), array(), '' );
		$this->gs_space( 'gs_btn_space', esc_html__( 'Spacing (top / bottom)', 'nexvio' ), $btn );
		$this->gs_box( 'gs_btn_radius', esc_html__( 'Border radius', 'nexvio' ), $btn, 'border-radius', array( 'px', '%' ), array(), 'clip-path: none;' );

		$this->number_var( 'btn_size', esc_html__( 'Button size (px)', 'nexvio' ), '--nexvio-ps-btn-size', '', 24, 80, 1, 'px' );
		$this->number_var( 'btn_cut', esc_html__( 'Cut corner (px, 0 = square)', 'nexvio' ), '--nexvio-ps-btn-cut', '', 0, 30, 1, 'px' );

		$this->end_controls_section();
	}

	/* ------------------------------------------------------------------
	 * 5. Badges: colours + full text control, padding, margin, radius
	 * ------------------------------------------------------------------ */

	protected function register_gs_badges_section() {
		$ps = '{{WRAPPER}} .nexvio-ps';

		$this->start_controls_section( 'style_badges', array(
			'label' => esc_html__( 'Badges', 'nexvio' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->gs_heading( 'h_badge_all', esc_html__( 'All badges', 'nexvio' ), array(), '' );
		$this->gs_typo( 'badge_typo', esc_html__( 'Text (font family, size, weight…)', 'nexvio' ), $ps . ' .nexvio-ps__badge' );

		/* [ key, title, class, colour ids ] */
		$badges = array(
			array( 'lang', esc_html__( 'Language', 'nexvio' ), 'lang', array( 'bg' => 'lang_bg', 'text' => 'lang_text', 'border' => 'lang_border' ), array( 'show_lang' => 'yes' ) ),
			array( 'cat', esc_html__( 'Category', 'nexvio' ), 'cat', array( 'bg' => 'cat_bg', 'text' => 'cat_tc', 'border' => 'cat_border' ), array( 'show_cat' => 'yes' ) ),
			array( 'new', esc_html__( 'New', 'nexvio' ), 'new', array( 'bg' => 'new_bg', 'text' => 'new_tc' ), array( 'show_new' => 'yes' ) ),
			array( 'sale', esc_html__( 'Sale', 'nexvio' ), 'sale', array( 'bg' => 'sale_bg', 'text' => 'sale_tc' ), array( 'show_sale' => 'yes' ) ),
			array( 'pre', esc_html__( 'Pre-order', 'nexvio' ), 'pre', array( 'bg' => 'pre_bg', 'text' => 'pre_tc' ), array( 'show_pre' => 'yes' ) ),
		);

		foreach ( $badges as $b ) {
			list( $key, $title, $cls, $ids, $cond ) = $b;
			$sel = $ps . ' .nexvio-ps__badge.nexvio-ps__badge--' . $cls;

			$this->gs_heading( 'h_' . $key, $title, $cond );
			$this->gs_color( $ids['bg'], esc_html__( 'Background', 'nexvio' ), array( $sel => 'background-color: {{VALUE}};' ), $cond );
			$this->gs_color( $ids['text'], esc_html__( 'Text colour', 'nexvio' ), array( $sel => 'color: {{VALUE}};' ), $cond );
			if ( isset( $ids['border'] ) ) {
				$this->gs_color( $ids['border'], esc_html__( 'Border colour', 'nexvio' ), array( $sel => 'border-color: {{VALUE}};' ), $cond );
			}
			$this->gs_typo( $key . '_typo', esc_html__( 'Text (font family, size, weight…)', 'nexvio' ), $sel, $cond );
			$this->gs_box( $key . '_pad', esc_html__( 'Padding', 'nexvio' ), $sel, 'padding', array( 'px', 'em', '%' ), $cond );
			$this->gs_box( $key . '_mar', esc_html__( 'Margin', 'nexvio' ), $sel, 'margin', array( 'px', 'em', '%' ), $cond );
			$this->gs_box( $key . '_rad', esc_html__( 'Border radius', 'nexvio' ), $sel, 'border-radius', array( 'px', '%' ), $cond );
		}

		$this->end_controls_section();
	}

	/* ------------------------------------------------------------------
	 * 6. Wishlist heart: full control
	 * ------------------------------------------------------------------ */

	protected function register_gs_heart_section() {
		$w  = '{{WRAPPER}} .nexvio-ps .nexvio-ps__card .nexvio-ps__wish';
		$on = array( 'wish_mode!' => 'none' );

		$this->start_controls_section( 'style_heart', array(
			'label'     => esc_html__( 'Wishlist heart', 'nexvio' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => $on,
		) );

		/* position */
		$this->add_control( 'heart_side', array(
			'label'                => esc_html__( 'Position (side)', 'nexvio' ),
			'type'                 => Controls_Manager::CHOOSE,
			'default'              => 'right',
			'options'              => array(
				'left'  => array( 'title' => esc_html__( 'Left', 'nexvio' ), 'icon' => 'eicon-h-align-left' ),
				'right' => array( 'title' => esc_html__( 'Right', 'nexvio' ), 'icon' => 'eicon-h-align-right' ),
			),
			'selectors_dictionary' => array(
				'left'  => 'left: var(--nexvio-w-x, 6px); right: auto;',
				'right' => 'right: var(--nexvio-w-x, 6px); left: auto;',
			),
			'selectors'            => array( $w => '{{VALUE}}' ),
		) );
		$this->add_responsive_control( 'heart_x', array(
			'label'      => esc_html__( 'Distance from the side', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 80 ) ),
			'selectors'  => array( $w => '--nexvio-w-x: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'heart_y', array(
			'label'      => esc_html__( 'Distance from the top', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 300 ) ),
			'selectors'  => array( $w => 'top: {{SIZE}}{{UNIT}};' ),
		) );

		/* size */
		$this->add_responsive_control( 'heart_size', array(
			'label'      => esc_html__( 'Button size', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 16, 'max' => 80 ) ),
			'selectors'  => array( $w => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ),
			'separator'  => 'before',
		) );
		$this->add_responsive_control( 'heart_icon', array(
			'label'      => esc_html__( 'Icon size', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 8, 'max' => 48 ) ),
			'selectors'  => array( $w . ' svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ),
		) );

		/* colours */
		$this->start_controls_tabs( 'heart_tabs' );
		$states = array(
			'n' => array( esc_html__( 'Normal', 'nexvio' ), $w, '' ),
			'h' => array( esc_html__( 'Hover', 'nexvio' ), $w . ':hover', '_h' ),
			'a' => array( esc_html__( 'In wishlist', 'nexvio' ), $w . '.is-on', '_a' ),
		);
		foreach ( $states as $k => $st ) {
			$this->start_controls_tab( 'heart_tab_' . $k, array( 'label' => $st[0] ) );
			$this->gs_color( 'heart_bg' . $st[2], esc_html__( 'Background', 'nexvio' ), array( $st[1] => 'background-color: {{VALUE}};' ) );
			$this->gs_color( 'heart_c' . $st[2], esc_html__( 'Icon colour', 'nexvio' ), array( $st[1] => 'color: {{VALUE}};' ) );
			$this->gs_color( 'heart_bc' . $st[2], esc_html__( 'Border colour', 'nexvio' ), array( $st[1] => 'border-color: {{VALUE}};' ) );
			$this->end_controls_tab();
		}
		$this->end_controls_tabs();

		/* border, radius, shadow */
		$this->add_group_control( Group_Control_Border::get_type(), array(
			'name'      => 'heart_border',
			'selector'  => $w,
			'separator' => 'before',
		) );
		$this->gs_box( 'heart_radius', esc_html__( 'Border radius', 'nexvio' ), $w, 'border-radius', array( 'px', '%' ) );
		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array(
			'name'     => 'heart_shadow',
			'selector' => $w,
		) );

		$this->end_controls_section();
	}

	/** All card style sections, in order: Layout design, Image, Card Content, Button, Badges. */
	protected function register_gs_card_styles() {
		$this->register_gs_layout_section();
		$this->register_gs_image_section();
		$this->register_gs_content_section();
		$this->register_gs_button_section();
		$this->register_gs_badges_section();
		$this->register_gs_heart_section();
	}

}
