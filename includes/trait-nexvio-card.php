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
		$this->end_controls_section();
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
			'label'     => esc_html__( 'Shadow', 'nexvio' ),
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

		$this->add_control( 'l_h_btn', array( 'label' => esc_html__( 'Button', 'nexvio' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$lc( 'l_accent', esc_html__( 'Button background', 'nexvio' ), '--nexvio-l-accent' );
		$lc( 'l_accent_text', esc_html__( 'Button text / icon', 'nexvio' ), '--nexvio-l-accent-text' );
		$ln( 'l_btn_radius', esc_html__( 'Button corner radius (px)', 'nexvio' ), '--nexvio-l-btn-radius', 0, 60 );

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

	protected function render_card( $product, $s, $rules, $wrap = 'slide' ) {
		$id    = $product->get_id();
		$link  = $product->get_permalink();
		$name  = $product->get_name();
		$style = '';

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
		$img    = $img_id
			? wp_get_attachment_image( $img_id, $size, false, array( 'class' => 'nexvio-ps__img', 'loading' => 'lazy', 'alt' => $name ) )
			: wc_placeholder_img( $size, array( 'class' => 'nexvio-ps__img' ) );

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
					$swatches_html .= '<button type="button" class="nexvio-ps__sw' . ( $it['color'] || $pic ? '' : ' is-unknown' ) . ( $light ? ' is-light' : '' ) . ( $pic ? ' is-image' : '' ) . '" style="--sw:' . esc_attr( $bg ) . ( $pic ? ';--sw-img:url(' . esc_url( $pic ) . ')' : '' ) . '"'
						. ' title="' . esc_attr( $it['name'] ) . '" aria-label="' . esc_attr( $it['name'] ) . '" aria-pressed="false"'
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
			if ( ! $product->is_purchasable() || ! $product->is_in_stock() ) {
				$button = '<span class="nexvio-ps__add is-disabled" aria-hidden="true">' . $this->svg( 'plus' ) . sprintf( $label_tpl, esc_html( $lbl_out ) ) . '</span>';
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
			<article class="nexvio-ps__card">
				<?php if ( $badges ) : ?>
					<div class="nexvio-ps__badges"><?php echo wp_kses( implode( '', $badges ), Nexvio_Plugin::allowed_html() ); ?></div>
				<?php endif; ?>
				<?php echo wp_kses( $wish, Nexvio_Plugin::allowed_html() ); ?>
				<a class="nexvio-ps__media" href="<?php echo esc_url( $link ); ?>" tabindex="-1" aria-hidden="true"<?php echo $style ? ' style="' . esc_attr( $style ) . '"' : ''; ?>>
					<?php echo wp_kses( $img, Nexvio_Plugin::allowed_html() ); ?>
				</a>
				<div class="nexvio-ps__body">
					<?php echo wp_kses( $swatches_html, Nexvio_Plugin::allowed_html() ); ?>
					<?php if ( $meta ) : ?>
						<span class="nexvio-ps__meta"><?php echo esc_html( implode( $s['meta_sep'], $meta ) ); ?></span>
					<?php endif; ?>
					<h3 class="nexvio-ps__name"><a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $name ); ?></a></h3>
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
}
