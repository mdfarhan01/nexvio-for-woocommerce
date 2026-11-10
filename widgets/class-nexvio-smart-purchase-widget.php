<?php
/**
 * NEXVIO Smart Purchase - Elementor widget.
 *
 * A button that opens a product popup (or shows the same panel inline):
 *  - left: the main product image at 100% width with the gallery thumbnails inside the image, at the bottom;
 *  - right: title, price, short description, rating, SKU, stock, variations, quantity and the
 *    Add to cart button.
 * Works with simple and variable products. Variation images replace the main image.
 *
 * Uses the helpers of the Products Card family (Nexvio_Card_Trait): colour / number / typography controls,
 * wishlist-free, same "nexvio-ps" base class and the same Style tab conventions.
 *
 * @package Nexvio_For_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

require_once __DIR__ . '/class-nexvio-products-card-widget.php';

class Nexvio_Smart_Purchase_Widget extends Nexvio_Products_Card_Widget {

	/** The editor preview shows the popup of the first card only. */
	private $sp_inline_used = false;

	public function get_name() {
		return 'nexvio_smart_purchase';
	}

	public function get_title() {
		return esc_html__( 'Smart Purchase', 'nexvio' );
	}

	public function get_icon() {
		return 'eicon-product-add-to-cart';
	}

	public function get_categories() {
		return array( 'nexvio' );
	}

	public function get_keywords() {
		return array( 'nexvio', 'woocommerce', 'products', 'grid', 'card', 'popup', 'quick view', 'add to cart', 'variation', 'gallery' );
	}

	public function get_style_depends() {
		return array_values( array_unique( array_merge( (array) parent::get_style_depends(), array( 'nexvio-smart-purchase' ) ) ) );
	}

	public function get_script_depends() {
		return array_values( array_unique( array_merge( (array) parent::get_script_depends(), array( 'nexvio-smart-purchase' ) ) ) );
	}

	/* ------------------------------------------------------------------
	 * Controls
	 * ------------------------------------------------------------------ */

	/** Style helper: a CSS property on a selector inside the widget. */
	private function sel( $class ) {
		return '{{WRAPPER}} ' . $class;
	}

	private function icon_control( $id, $label, $condition = array() ) {
		$args = array(
			'label'       => $label,
			'type'        => Controls_Manager::ICONS,
			'default'     => array( 'value' => '', 'library' => '' ),
			'skin'        => 'inline',
			'label_block' => false,
		);
		if ( $condition ) {
			$args['condition'] = $condition;
		}
		$this->add_control( $id, $args );
	}

	private function icon_pos_control( $id, $condition = array() ) {
		$args = array(
			'label'   => esc_html__( 'Icon position', 'nexvio' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'before',
			'options' => array(
				'before' => esc_html__( 'Before text', 'nexvio' ),
				'after'  => esc_html__( 'After text', 'nexvio' ),
			),
		);
		if ( $condition ) {
			$args['condition'] = $condition;
		}
		$this->add_control( $id, $args );
	}

	protected function register_controls() {

		/* Products Card: layout design, hover gallery, product query, card elements, grid, pagination */
		$this->register_pc_design_section();
		$this->register_gallery_section();
		$this->register_query_section();
		$this->register_card_sections();
		$this->register_grid_layout_section();
		$this->register_pagination_section();
		$this->remove_simple_card_controls();
		/* the single add-to-cart icon of Products Card is replaced by the 3 purchase buttons */
		foreach ( array( 'show_button', 'btn_label', 'btn_label_options' ) as $old ) {
			$this->remove_control( $old );
		}
		$this->update_control( 'limit', array(
			'label'       => esc_html__( 'Products per page', 'nexvio' ),
			'description' => esc_html__( 'With pagination on, the next products are on the following pages.', 'nexvio' ),
		) );

		/* ============ CARD BUTTONS ============ */
		$this->start_controls_section( 'section_cardbtns', array( 'label' => esc_html__( 'Card purchase buttons', 'nexvio' ) ) );
		$this->switcher( 'card_btns', esc_html__( 'Show the Add to cart button on every card', 'nexvio' ), 'yes', array( 'description' => esc_html__( 'Shows the Add to cart button. It can be switched on or off in its own section.', 'nexvio' ) ) );
		$this->add_control( 'card_action', array(
			'label'       => esc_html__( 'When a card button is clicked', 'nexvio' ),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'auto',
			'options'     => array(
				'auto'  => esc_html__( 'Do it now (simple product) / open the popup to choose options (variable product)', 'nexvio' ),
				'popup' => esc_html__( 'Always open the popup first', 'nexvio' ),
			),
			'description' => esc_html__( 'With the popup disabled, variable products go to the product page.', 'nexvio' ),
			'condition'   => array( 'card_btns' => 'yes' ),
		) );
		$this->end_controls_section();

		/* ============ POPUP ============ */
		$this->start_controls_section( 'section_popup', array( 'label' => esc_html__( 'Popup settings', 'nexvio' ) ) );
		$this->switcher( 'popup', esc_html__( 'Enable the popup', 'nexvio' ), 'yes', array( 'description' => esc_html__( 'On: the card button opens the product popup. Off: no popup, the card button goes to the product page.', 'nexvio' ) ) );
		$pc = array( 'popup' => 'yes' );
		$this->switcher( 'editor_preview', esc_html__( 'Show the popup of the first product in the editor (for styling)', 'nexvio' ), 'no', array( 'condition' => $pc ) );
		$this->add_control( 'popup_position', array(
			'label'     => esc_html__( 'Popup position', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'center',
			'options'   => array(
				'center' => esc_html__( 'Center', 'nexvio' ),
				'top'    => esc_html__( 'Top', 'nexvio' ),
				'bottom' => esc_html__( 'Bottom', 'nexvio' ),
			),
			'condition' => $pc,
			'separator' => 'before',
		) );
		$this->switcher( 'sp_overlay', esc_html__( 'Overlay', 'nexvio' ), 'yes', array( 'condition' => $pc ) );
		$this->switcher( 'close_btn', esc_html__( 'Close button', 'nexvio' ), 'yes', array( 'condition' => $pc ) );
		$this->switcher( 'close_overlay', esc_html__( 'Close on overlay click', 'nexvio' ), 'yes', array( 'condition' => $pc ) );
		$this->switcher( 'close_esc', esc_html__( 'Close on ESC key', 'nexvio' ), 'yes', array( 'condition' => $pc ) );
		$this->switcher( 'lock_scroll', esc_html__( 'Lock the page scroll while open', 'nexvio' ), 'yes', array( 'condition' => $pc ) );
		$this->text( 'close_label', esc_html__( 'Close button label (screen readers)', 'nexvio' ), 'Close', array( 'condition' => $pc ) );

		$anim = array(
			'fade-scale' => esc_html__( 'Fade + scale', 'nexvio' ),
			'slide-up'   => esc_html__( 'Slide up', 'nexvio' ),
			'slide-down' => esc_html__( 'Slide down', 'nexvio' ),
			'zoom'       => esc_html__( 'Zoom', 'nexvio' ),
			'fade'       => esc_html__( 'Fade', 'nexvio' ),
			'none'       => esc_html__( 'None', 'nexvio' ),
		);
		$this->add_control( 'anim_in', array(
			'label'     => esc_html__( 'Entrance animation', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'fade-scale',
			'options'   => $anim,
			'condition' => $pc,
			'separator' => 'before',
		) );
		$this->add_control( 'anim_out', array(
			'label'     => esc_html__( 'Exit animation', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'fade-scale',
			'options'   => $anim,
			'condition' => $pc,
		) );
		$this->add_control( 'anim_dur', array(
			'label'     => esc_html__( 'Animation duration (ms)', 'nexvio' ),
			'type'      => Controls_Manager::NUMBER,
			'default'   => 320,
			'min'       => 0,
			'max'       => 2000,
			'step'      => 20,
			'condition' => $pc,
		) );
		$this->add_control( 'anim_ease', array(
			'label'     => esc_html__( 'Animation easing', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'cubic-bezier(.2,.8,.2,1)',
			'options'   => array(
				'cubic-bezier(.2,.8,.2,1)' => esc_html__( 'Smooth (ease out)', 'nexvio' ),
				'ease'                     => esc_html__( 'Ease', 'nexvio' ),
				'ease-in-out'              => esc_html__( 'Ease in-out', 'nexvio' ),
				'linear'                   => esc_html__( 'Linear', 'nexvio' ),
				'cubic-bezier(.34,1.56,.64,1)' => esc_html__( 'Bounce', 'nexvio' ),
			),
			'condition' => $pc,
		) );
		$this->end_controls_section();

		/* ============ GALLERY ============ */
		$this->start_controls_section( 'section_gallery', array( 'label' => esc_html__( 'Product gallery', 'nexvio' ) ) );
		$this->switcher( 'show_thumbs', esc_html__( 'Gallery thumbnails', 'nexvio' ) );
		$this->add_control( 'thumbs_pos', array(
			'label'     => esc_html__( 'Thumbnails position', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'overlay',
			'options'   => array(
				'overlay' => esc_html__( 'Inside the image, at the bottom', 'nexvio' ),
				'below'   => esc_html__( 'Below the image', 'nexvio' ),
			),
			'condition' => array( 'show_thumbs' => 'yes' ),
		) );
		$this->switcher( 'show_nav', esc_html__( 'Previous / next arrows', 'nexvio' ) );
		$this->add_responsive_control( 'img_ratio', array(
			'label'          => esc_html__( 'Image box ratio', 'nexvio' ),
			'type'           => Controls_Manager::SELECT,
			'default'        => 'auto',
			'mobile_default' => '1/1',
			'options'        => array(
				'auto' => esc_html__( 'Original', 'nexvio' ),
				'1/1'  => '1 : 1',
				'4/5'  => '4 : 5',
				'3/4'  => '3 : 4',
				'4/3'  => '4 : 3',
				'16/9' => '16 : 9',
			),
			'description'    => esc_html__( 'Original: the popup follows the size of the product image, so there is no empty space around it. The other ratios use a fixed box.', 'nexvio' ),
			'selectors'      => array( $this->sel( '.nexvio-sp__img' ) => 'aspect-ratio: {{VALUE}};' ),
			'separator'      => 'before',
		) );
		$this->add_control( 'img_fit', array(
			'label'     => esc_html__( 'Image fit', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'cover',
			'options'   => array(
				'contain' => esc_html__( 'Whole image visible (no crop)', 'nexvio' ),
				'cover'   => esc_html__( 'Fill the box (crops)', 'nexvio' ),
			),
			'selectors' => array( $this->sel( '.nexvio-sp__img' ) . ', ' . $this->sel( '.nexvio-sp__thumb img' ) => 'object-fit: {{VALUE}};' ),
		) );
		$this->end_controls_section();

		/* ============ INFORMATION ============ */
		$this->start_controls_section( 'section_info', array( 'label' => esc_html__( 'Product information', 'nexvio' ) ) );
		$this->switcher( 'pp_title', esc_html__( 'Title', 'nexvio' ) );
		$this->switcher( 'pp_price', esc_html__( 'Price', 'nexvio' ) );
		$this->switcher( 'pp_desc', esc_html__( 'Short description', 'nexvio' ) );
		$this->switcher( 'pp_rating', esc_html__( 'Rating', 'nexvio' ), 'yes' );
		$this->switcher( 'pp_sku', esc_html__( 'SKU', 'nexvio' ), 'no' );
		$this->switcher( 'pp_stock', esc_html__( 'Stock status', 'nexvio' ) );
		$this->add_control( 'title_tag', array(
			'label'   => esc_html__( 'Title tag', 'nexvio' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'h2',
			'options' => array( 'h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4', 'div' => 'div', 'p' => 'p' ),
		) );
		$this->end_controls_section();

		/* ============ VARIATIONS ============ */
		$this->start_controls_section( 'section_vars', array( 'label' => esc_html__( 'Variations', 'nexvio' ) ) );
		$this->switcher( 'show_vars_labels', esc_html__( 'Attribute labels', 'nexvio' ) );
		$this->switcher( 'color_swatches', esc_html__( 'Colour attributes as colour dots', 'nexvio' ) );
		$this->text( 'txt_pick', esc_html__( 'Message: options missing', 'nexvio' ), 'Please choose all the product options first.' );
		$this->text( 'txt_unavailable', esc_html__( 'Message: combination not available', 'nexvio' ), 'Sorry, this combination is not available.' );
		$this->text( 'txt_clear', esc_html__( '"Clear" link text', 'nexvio' ), 'Clear' );
		$this->end_controls_section();

		/* ============ QUANTITY ============ */
		$this->start_controls_section( 'section_qty', array( 'label' => esc_html__( 'Quantity', 'nexvio' ) ) );
		$this->switcher( 'show_qty', esc_html__( 'Quantity selector', 'nexvio' ) );
		$qc = array( 'show_qty' => 'yes' );
		$this->text( 'qty_label', esc_html__( 'Quantity label', 'nexvio' ), 'Quantity', array( 'condition' => $qc ) );
		$this->add_control( 'qty_min', array( 'label' => esc_html__( 'Minimum quantity', 'nexvio' ), 'type' => Controls_Manager::NUMBER, 'default' => '', 'min' => 1, 'description' => esc_html__( 'Empty = the WooCommerce minimum.', 'nexvio' ), 'condition' => $qc ) );
		$this->add_control( 'qty_max', array( 'label' => esc_html__( 'Maximum quantity', 'nexvio' ), 'type' => Controls_Manager::NUMBER, 'default' => '', 'min' => 1, 'description' => esc_html__( 'Empty = the WooCommerce maximum / stock. A lower value here is respected.', 'nexvio' ), 'condition' => $qc ) );
		$this->add_control( 'qty_step', array( 'label' => esc_html__( 'Quantity step', 'nexvio' ), 'type' => Controls_Manager::NUMBER, 'default' => 1, 'min' => 1, 'condition' => $qc ) );
		$this->end_controls_section();

		/* ============ ADD TO CART ============ */
		$this->start_controls_section( 'section_atc', array( 'label' => esc_html__( 'Add to cart button', 'nexvio' ) ) );
		$this->switcher( 'atc_on', esc_html__( 'Show the Add to cart button', 'nexvio' ) );
		$ac = array( 'atc_on' => 'yes' );
		$this->text( 'atc_text', esc_html__( 'Button text', 'nexvio' ), 'Add to cart', array( 'condition' => $ac ) );
		$this->text( 'atc_adding', esc_html__( 'While adding', 'nexvio' ), 'Adding…', array( 'condition' => $ac ) );
		$this->text( 'atc_added', esc_html__( 'After adding', 'nexvio' ), 'Added to cart', array( 'condition' => $ac ) );
		$this->text( 'atc_view', esc_html__( '"View cart" link text (empty = hide)', 'nexvio' ), 'View cart', array( 'condition' => $ac ) );
		$this->text( 'txt_error', esc_html__( 'Error message', 'nexvio' ), 'Could not add this product. Please try again.', array( 'condition' => $ac ) );
		$this->switcher( 'atc_close', esc_html__( 'Close the popup after adding', 'nexvio' ), 'no', array( 'condition' => $ac ) );
		$this->add_control( 'side_cart', array(
			'label'     => esc_html__( 'Open the side cart after adding', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'global',
			'options'   => array(
				'global' => esc_html__( 'Dashboard setting', 'nexvio' ),
				'open'   => esc_html__( 'Always open it', 'nexvio' ),
				'no'     => esc_html__( 'Do not open it', 'nexvio' ),
			),
			'condition' => $ac,
		) );
		$this->icon_control( 'atc_icon', esc_html__( 'Icon (empty = cart icon)', 'nexvio' ), $ac );
		$this->icon_pos_control( 'atc_icon_pos', $ac );
		$this->end_controls_section();

		/* ============ BUTTON DISPLAY ============ */
		$this->start_controls_section( 'section_btn_display', array( 'label' => esc_html__( 'Purchase buttons layout', 'nexvio' ) ) );
		$this->add_control( 'btn_display', array(
			'label'   => esc_html__( 'Display', 'nexvio' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'both',
			'options' => array(
				'both' => esc_html__( 'Icon + text', 'nexvio' ),
				'text' => esc_html__( 'Text only', 'nexvio' ),
				'icon' => esc_html__( 'Icon only', 'nexvio' ),
			),
		) );
		$this->add_control( 'btn_layout', array(
			'label'   => esc_html__( 'Buttons are', 'nexvio' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'stack',
			'options' => array(
				'stack' => esc_html__( 'Stacked (one per row)', 'nexvio' ),
				'row'   => esc_html__( 'In a row', 'nexvio' ),
			),
		) );
		$this->end_controls_section();

		/* ============ LAYOUT ============ */
		$this->start_controls_section( 'section_layout', array( 'label' => esc_html__( 'Layout settings', 'nexvio' ) ) );
		$this->add_responsive_control( 'col_media', array(
			'label'      => esc_html__( 'Image column width (%)', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( '%' ),
			'range'      => array( '%' => array( 'min' => 30, 'max' => 70 ) ),
			'default'    => array( 'size' => 52, 'unit' => '%' ),
			'selectors'  => array( $this->sel( '.nexvio-sp__grid' ) => '--nexvio-sp-media: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'sp_col_gap', array(
			'label'      => esc_html__( 'Space between the columns', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 80 ) ),
			'default'    => array( 'size' => 0, 'unit' => 'px' ),
			'selectors'  => array( $this->sel( '.nexvio-sp__grid' ) => 'column-gap: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'sp_content_align', array(
			'label'     => esc_html__( 'Content alignment', 'nexvio' ),
			'type'      => Controls_Manager::CHOOSE,
			'options'   => array(
				'left'   => array( 'title' => esc_html__( 'Left', 'nexvio' ), 'icon' => 'eicon-text-align-left' ),
				'center' => array( 'title' => esc_html__( 'Center', 'nexvio' ), 'icon' => 'eicon-text-align-center' ),
				'right'  => array( 'title' => esc_html__( 'Right', 'nexvio' ), 'icon' => 'eicon-text-align-right' ),
			),
			'selectors' => array( $this->sel( '.nexvio-sp__info' ) => 'text-align: {{VALUE}};' ),
		) );
		$this->end_controls_section();

		$this->register_style_tab();
	}

	/* ------------------------------------------------------------------
	 * Style tab
	 * ------------------------------------------------------------------ */

	private function style_section( $id, $label, $condition = array() ) {
		$args = array( 'label' => $label, 'tab' => Controls_Manager::TAB_STYLE );
		if ( $condition ) {
			$args['condition'] = $condition;
		}
		$this->start_controls_section( $id, $args );
	}

	private function register_style_tab() {

		/* Products Card style sections */
		$this->register_pc_layout_style_section();
		$this->register_pc_image_section();
		$this->register_gs_content_section();
		$this->register_gs_badges_section();
		$this->register_pagination_style_section();
		$this->register_empty_style_section();

		/* ---- Popup ---- */
		$this->style_section( 'st_popup', esc_html__( 'Popup', 'nexvio' ) );
		$this->add_responsive_control( 'pop_w', array(
			'label'      => esc_html__( 'Popup width', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px', '%', 'vw' ),
			'range'      => array( 'px' => array( 'min' => 320, 'max' => 1600 ), '%' => array( 'min' => 30, 'max' => 100 ), 'vw' => array( 'min' => 30, 'max' => 100 ) ),
			'default'    => array( 'size' => 1000, 'unit' => 'px' ),
			'selectors'  => array( $this->sel( '.nexvio-sp__dialog' ) => 'width: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'pop_h', array(
			'label'      => esc_html__( 'Popup maximum height', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'vh', 'px' ),
			'range'      => array( 'vh' => array( 'min' => 30, 'max' => 100 ), 'px' => array( 'min' => 300, 'max' => 1400 ) ),
			'default'    => array( 'size' => 90, 'unit' => 'vh' ),
			'selectors'  => array( $this->sel( '.nexvio-sp__dialog' ) => 'max-height: {{SIZE}}{{UNIT}}; --nexvio-sp-maxh: {{SIZE}}{{UNIT}};' ),
		) );
		$this->gs_color( 'pop_bg', esc_html__( 'Background', 'nexvio' ), array( $this->sel( '.nexvio-sp__dialog' ) => 'background-color: {{VALUE}};' ), array(), array( 'default' => '#ffffff' ) );
		$this->gs_color( 'pop_overlay', esc_html__( 'Overlay background', 'nexvio' ), array( $this->sel( '.nexvio-sp__overlay' ) => 'background-color: {{VALUE}};' ), array( 'sp_overlay' => 'yes' ), array( 'default' => 'rgba(10,14,20,.6)' ) );
		$this->gs_box( 'pop_pad', esc_html__( 'Info column padding', 'nexvio' ), $this->sel( '.nexvio-sp__info' ), 'padding' );
		$this->add_group_control( Group_Control_Border::get_type(), array( 'name' => 'pop_border', 'selector' => $this->sel( '.nexvio-sp__dialog' ) ) );
		$this->gs_box( 'pop_radius', esc_html__( 'Corner radius', 'nexvio' ), $this->sel( '.nexvio-sp__dialog' ), 'border-radius' );
		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array( 'name' => 'pop_shadow', 'selector' => $this->sel( '.nexvio-sp__dialog' ) ) );
		$this->gs_heading( 'pop_close_h', esc_html__( 'Close button', 'nexvio' ), array( 'close_btn' => 'yes' ) );
		$this->gs_color( 'close_color', esc_html__( 'Icon', 'nexvio' ), array( $this->sel( '.nexvio-sp__close' ) => 'color: {{VALUE}};' ), array( 'close_btn' => 'yes' ) );
		$this->gs_color( 'close_bg', esc_html__( 'Background', 'nexvio' ), array( $this->sel( '.nexvio-sp__close' ) => 'background-color: {{VALUE}};' ), array( 'close_btn' => 'yes' ), array( 'default' => 'rgba(255,255,255,.92)' ) );
		$this->end_controls_section();

		/* ---- Gallery ---- */
		$this->style_section( 'st_gallery', esc_html__( 'Gallery', 'nexvio' ) );
		$this->gs_color( 'media_bg', esc_html__( 'Image area background', 'nexvio' ), array( $this->sel( '.nexvio-sp__stage' ) => 'background-color: {{VALUE}};' ), array(), array( 'default' => '#f3f4f6' ) );
		$this->add_group_control( Group_Control_Border::get_type(), array( 'name' => 'sp_img_border', 'selector' => $this->sel( '.nexvio-sp__stage' ) ) );
		$this->gs_box( 'sp_img_radius', esc_html__( 'Main image radius', 'nexvio' ), $this->sel( '.nexvio-sp__stage' ), 'border-radius' );
		$this->gs_heading( 'thumb_h', esc_html__( 'Thumbnails', 'nexvio' ), array( 'show_thumbs' => 'yes' ) );
		$this->add_responsive_control( 'thumb_w', array(
			'label'      => esc_html__( 'Thumbnail width', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 20, 'max' => 160 ) ),
			'default'    => array( 'size' => 30, 'unit' => 'px' ),
			'selectors'  => array( $this->sel( '.nexvio-sp__thumb' ) => 'width: {{SIZE}}{{UNIT}};' ),
			'condition'  => array( 'show_thumbs' => 'yes' ),
		) );
		$this->add_responsive_control( 'thumb_hh', array(
			'label'      => esc_html__( 'Thumbnail height', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 20, 'max' => 160 ) ),
			'default'    => array( 'size' => 30, 'unit' => 'px' ),
			'selectors'  => array( $this->sel( '.nexvio-sp__thumb' ) => 'height: {{SIZE}}{{UNIT}};' ),
			'condition'  => array( 'show_thumbs' => 'yes' ),
		) );
		$this->add_responsive_control( 'thumb_gap', array(
			'label'      => esc_html__( 'Thumbnail gap', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
			'default'    => array( 'size' => 6, 'unit' => 'px' ),
			'selectors'  => array( $this->sel( '.nexvio-sp__thumbs' ) => 'gap: {{SIZE}}{{UNIT}};' ),
			'condition'  => array( 'show_thumbs' => 'yes' ),
		) );
		$this->add_responsive_control( 'thumb_pad', array(
			'label'      => esc_html__( 'Thumbnail bar padding', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
			'default'    => array( 'size' => 10, 'unit' => 'px' ),
			'selectors'  => array( $this->sel( '.nexvio-sp__thumbs' ) => 'padding: {{SIZE}}{{UNIT}};' ),
			'condition'  => array( 'show_thumbs' => 'yes' ),
		) );
		$this->gs_color( 'thumb_bar', esc_html__( 'Thumbnail bar background', 'nexvio' ), array( $this->sel( '.nexvio-sp__thumbs' ) => 'background: {{VALUE}};' ), array( 'show_thumbs' => 'yes' ), array( 'description' => esc_html__( 'Default is a soft dark fade behind the thumbnails.', 'nexvio' ) ) );
		$this->gs_box( 'thumb_radius', esc_html__( 'Thumbnail radius', 'nexvio' ), $this->sel( '.nexvio-sp__thumb' ), 'border-radius', array( 'px', '%' ), array( 'show_thumbs' => 'yes' ) );
		$this->gs_color( 'thumb_border', esc_html__( 'Thumbnail border', 'nexvio' ), array( $this->sel( '.nexvio-sp__thumb' ) => 'border-color: {{VALUE}};' ), array( 'show_thumbs' => 'yes' ), array( 'default' => 'rgba(255,255,255,.7)' ) );
		$this->gs_color( 'thumb_active', esc_html__( 'Active thumbnail border', 'nexvio' ), array( $this->sel( '.nexvio-sp__thumb.is-active' ) => 'border-color: {{VALUE}};' ), array( 'show_thumbs' => 'yes' ), array( 'default' => '#111827' ) );
		$this->gs_heading( 'nav_h', esc_html__( 'Arrows', 'nexvio' ), array( 'show_nav' => 'yes' ) );
		$this->gs_color( 'nav_color', esc_html__( 'Arrow colour', 'nexvio' ), array( $this->sel( '.nexvio-sp__nav' ) => 'color: {{VALUE}};' ), array( 'show_nav' => 'yes' ) );
		$this->gs_color( 'nav_bg', esc_html__( 'Arrow background', 'nexvio' ), array( $this->sel( '.nexvio-sp__nav' ) => 'background-color: {{VALUE}};' ), array( 'show_nav' => 'yes' ) );
		$this->end_controls_section();

		/* ---- Product information ---- */
		$this->style_section( 'st_info', esc_html__( 'Product information', 'nexvio' ) );
		$this->gs_typo( 'ttl_typo', esc_html__( 'Title typography', 'nexvio' ), $this->sel( '.nexvio-sp__title' ), array( 'pp_title' => 'yes' ) );
		$this->gs_color( 'ttl_color', esc_html__( 'Title colour', 'nexvio' ), array( $this->sel( '.nexvio-sp__title' ) => 'color: {{VALUE}};' ), array( 'pp_title' => 'yes' ) );
		$this->gs_typo( 'prc_typo', esc_html__( 'Price typography', 'nexvio' ), $this->sel( '.nexvio-sp__price' ), array( 'pp_price' => 'yes' ) );
		$this->gs_color( 'prc_color', esc_html__( 'Price colour', 'nexvio' ), array( $this->sel( '.nexvio-sp__price' ) => 'color: {{VALUE}};' ), array( 'pp_price' => 'yes' ) );
		$this->gs_color( 'prc_sale', esc_html__( 'Sale price colour', 'nexvio' ), array( $this->sel( '.nexvio-sp__price ins' ) => 'color: {{VALUE}};' ), array( 'pp_price' => 'yes' ) );
		$this->gs_color( 'prc_old', esc_html__( 'Old price colour', 'nexvio' ), array( $this->sel( '.nexvio-sp__price del' ) => 'color: {{VALUE}};' ), array( 'pp_price' => 'yes' ) );
		$this->gs_typo( 'dsc_typo', esc_html__( 'Description typography', 'nexvio' ), $this->sel( '.nexvio-sp__desc' ), array( 'pp_desc' => 'yes' ) );
		$this->gs_color( 'dsc_color', esc_html__( 'Description colour', 'nexvio' ), array( $this->sel( '.nexvio-sp__desc' ) => 'color: {{VALUE}};' ), array( 'pp_desc' => 'yes' ) );
		$this->gs_color( 'sp_star_color', esc_html__( 'Star colour', 'nexvio' ), array( $this->sel( '.nexvio-sp__rating .star-rating span' ) => 'color: {{VALUE}};' ), array( 'pp_rating' => 'yes' ) );
		$this->gs_typo( 'stk_typo', esc_html__( 'SKU / stock typography', 'nexvio' ), $this->sel( '.nexvio-sp__meta' ) );
		$this->gs_color( 'stk_in', esc_html__( 'In stock colour', 'nexvio' ), array( $this->sel( '.nexvio-sp__stock.is-in' ) => 'color: {{VALUE}};' ), array( 'pp_stock' => 'yes' ), array( 'default' => '#15803d' ) );
		$this->gs_color( 'stk_out', esc_html__( 'Out of stock colour', 'nexvio' ), array( $this->sel( '.nexvio-sp__stock.is-out' ) => 'color: {{VALUE}};' ), array( 'pp_stock' => 'yes' ), array( 'default' => '#b91c1c' ) );
		$this->end_controls_section();

		/* ---- Variations ---- */
		$this->style_section( 'st_vars', esc_html__( 'Variations', 'nexvio' ) );
		$this->gs_typo( 'vl_typo', esc_html__( 'Label typography', 'nexvio' ), $this->sel( '.nexvio-sp__varlabel' ) );
		$this->gs_color( 'vl_color', esc_html__( 'Label colour', 'nexvio' ), array( $this->sel( '.nexvio-sp__varlabel' ) => 'color: {{VALUE}};' ) );
		$this->gs_typo( 'vo_typo', esc_html__( 'Option typography', 'nexvio' ), $this->sel( '.nexvio-sp__opt' ) );
		$this->gs_color( 'vo_bg', esc_html__( 'Option background', 'nexvio' ), array( $this->sel( '.nexvio-sp__opt' ) => 'background-color: {{VALUE}};' ), array(), array( 'default' => '#ffffff' ) );
		$this->gs_color( 'vo_color', esc_html__( 'Option text', 'nexvio' ), array( $this->sel( '.nexvio-sp__opt' ) => 'color: {{VALUE}};' ), array(), array( 'default' => '#111827' ) );
		$this->gs_color( 'vo_border', esc_html__( 'Option border', 'nexvio' ), array( $this->sel( '.nexvio-sp__opt' ) => 'border-color: {{VALUE}};' ), array(), array( 'default' => '#d1d5db' ) );
		$this->gs_color( 'vs_bg', esc_html__( 'Selected background', 'nexvio' ), array( $this->sel( '.nexvio-sp__opt.is-selected:not(.nexvio-sp__opt--color)' ) => 'background-color: {{VALUE}};' ), array(), array( 'default' => '#111827' ) );
		$this->gs_color( 'sp_vs_color', esc_html__( 'Selected text', 'nexvio' ), array( $this->sel( '.nexvio-sp__opt.is-selected:not(.nexvio-sp__opt--color)' ) => 'color: {{VALUE}};' ), array(), array( 'default' => '#ffffff' ) );
		$this->gs_color( 'vs_border', esc_html__( 'Selected border', 'nexvio' ), array( $this->sel( '.nexvio-sp__opt.is-selected' ) => 'border-color: {{VALUE}};' ), array(), array( 'default' => '#111827' ) );
		$this->gs_color( 'vd_color', esc_html__( 'Disabled option text', 'nexvio' ), array( $this->sel( '.nexvio-sp__opt.is-disabled' ) => 'color: {{VALUE}};' ), array(), array( 'default' => '#9ca3af' ) );
		$this->gs_box( 'vo_radius', esc_html__( 'Option radius', 'nexvio' ), $this->sel( '.nexvio-sp__opt' ), 'border-radius', array( 'px', '%' ) );
		$this->add_responsive_control( 'vo_gap', array(
			'label'      => esc_html__( 'Space between options', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 30 ) ),
			'default'    => array( 'size' => 8, 'unit' => 'px' ),
			'selectors'  => array( $this->sel( '.nexvio-sp__opts' ) => 'gap: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'vo_space', array(
			'label'      => esc_html__( 'Space between attributes', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
			'default'    => array( 'size' => 16, 'unit' => 'px' ),
			'selectors'  => array( $this->sel( '.nexvio-sp__var' ) => 'margin-bottom: {{SIZE}}{{UNIT}};' ),
		) );
		$this->end_controls_section();

		/* ---- Quantity ---- */
		$this->style_section( 'st_qty', esc_html__( 'Quantity', 'nexvio' ), array( 'show_qty' => 'yes' ) );
		$this->add_responsive_control( 'q_w', array(
			'label'      => esc_html__( 'Width', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 90, 'max' => 260 ) ),
			'default'    => array( 'size' => 140, 'unit' => 'px' ),
			'selectors'  => array( $this->sel( '.nexvio-sp__qty' ) => 'width: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'q_h', array(
			'label'      => esc_html__( 'Height', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 32, 'max' => 80 ) ),
			'default'    => array( 'size' => 46, 'unit' => 'px' ),
			'selectors'  => array( $this->sel( '.nexvio-sp__qty' ) => 'height: {{SIZE}}{{UNIT}};' ),
		) );
		$this->gs_typo( 'q_typo', esc_html__( 'Input typography', 'nexvio' ), $this->sel( '.nexvio-sp__qty-input' ) );
		$this->gs_color( 'q_color', esc_html__( 'Number colour', 'nexvio' ), array( $this->sel( '.nexvio-sp__qty-input' ) => 'color: {{VALUE}};' ) );
		$this->gs_color( 'q_bg', esc_html__( 'Background', 'nexvio' ), array( $this->sel( '.nexvio-sp__qty' ) => 'background-color: {{VALUE}};' ), array(), array( 'default' => '#ffffff' ) );
		$this->gs_color( 'q_btn_bg', esc_html__( '− / + background', 'nexvio' ), array( $this->sel( '.nexvio-sp__step' ) => 'background-color: {{VALUE}};' ), array(), array( 'default' => '#f3f4f6' ) );
		$this->gs_color( 'q_btn_color', esc_html__( '− / + colour', 'nexvio' ), array( $this->sel( '.nexvio-sp__step' ) => 'color: {{VALUE}};' ), array(), array( 'default' => '#111827' ) );
		$this->add_group_control( Group_Control_Border::get_type(), array( 'name' => 'q_border', 'selector' => $this->sel( '.nexvio-sp__qty' ) ) );
		$this->gs_box( 'q_radius', esc_html__( 'Corner radius', 'nexvio' ), $this->sel( '.nexvio-sp__qty' ), 'border-radius', array( 'px' ), array(), 'overflow:hidden;' );
		$this->end_controls_section();

		/* ---- Purchase buttons ---- */
		$this->style_section( 'st_btn', esc_html__( 'Purchase buttons', 'nexvio' ) );
		$b = $this->sel( '.nexvio-sp__btn' );
		$this->add_responsive_control( 'btn_w', array(
			'label'      => esc_html__( 'Button width', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px', '%' ),
			'range'      => array( 'px' => array( 'min' => 60, 'max' => 600 ), '%' => array( 'min' => 10, 'max' => 100 ) ),
			'selectors'  => array( $b => 'width: {{SIZE}}{{UNIT}}; flex: 0 0 auto;' ),
		) );
		$this->add_responsive_control( 'btn_h', array(
			'label'      => esc_html__( 'Button height', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 32, 'max' => 90 ) ),
			'default'    => array( 'size' => 50, 'unit' => 'px' ),
			'selectors'  => array( $b => 'min-height: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'btn_align', array(
			'label'     => esc_html__( 'Alignment', 'nexvio' ),
			'type'      => Controls_Manager::CHOOSE,
			'options'   => array(
				'flex-start' => array( 'title' => esc_html__( 'Left', 'nexvio' ), 'icon' => 'eicon-text-align-left' ),
				'center'     => array( 'title' => esc_html__( 'Center', 'nexvio' ), 'icon' => 'eicon-text-align-center' ),
				'flex-end'   => array( 'title' => esc_html__( 'Right', 'nexvio' ), 'icon' => 'eicon-text-align-right' ),
			),
			'selectors' => array( $this->sel( '.nexvio-sp__btns' ) => 'justify-content: {{VALUE}}; align-items: {{VALUE}};' ),
		) );
		$this->add_responsive_control( 'btn_gap', array(
			'label'      => esc_html__( 'Space between buttons', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
			'default'    => array( 'size' => 10, 'unit' => 'px' ),
			'selectors'  => array( $this->sel( '.nexvio-sp__btns' ) => 'gap: {{SIZE}}{{UNIT}};' ),
		) );
		$this->gs_typo( 'btn_typo', esc_html__( 'Typography', 'nexvio' ), $b );
		$this->add_responsive_control( 'btn_pad', array(
			'label'      => esc_html__( 'Padding', 'nexvio' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em' ),
			'selectors'  => array( $b => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );
		$this->gs_box( 'btn_margin', esc_html__( 'Margin', 'nexvio' ), $b, 'margin', array( 'px' ) );
		$this->add_group_control( Group_Control_Border::get_type(), array( 'name' => 'btn_border', 'selector' => $b ) );
		$this->gs_box( 'btn_radius', esc_html__( 'Corner radius', 'nexvio' ), $b, 'border-radius' );
		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array( 'name' => 'btn_shadow', 'selector' => $b ) );

		$this->gs_heading( 'btn_colors_h', esc_html__( 'Colours (normal)', 'nexvio' ) );
		$this->gs_color( 'btn_color', esc_html__( 'Text (all buttons)', 'nexvio' ), array( $b => 'color: {{VALUE}};' ), array(), array( 'default' => '#ffffff' ) );
		$this->gs_color( 'bg_atc', esc_html__( 'Add to cart background', 'nexvio' ), array( $this->sel( '.nexvio-sp__btn--atc' ) => 'background-color: {{VALUE}};' ), array( 'atc_on' => 'yes' ), array( 'default' => '#111827' ) );
		$this->gs_heading( 'btn_hover_h', esc_html__( 'Hover', 'nexvio' ) );
		$this->gs_color( 'btn_color_h', esc_html__( 'Text on hover', 'nexvio' ), array( $this->sel( '.nexvio-sp__btn:hover:not(:disabled)' ) => 'color: {{VALUE}};' ) );
		$this->gs_color( 'sp_btn_bg_h', esc_html__( 'Background on hover (all buttons)', 'nexvio' ), array( $this->sel( '.nexvio-sp__btn:hover:not(:disabled)' ) => 'background-color: {{VALUE}};' ) );
		$this->gs_color( 'btn_border_h', esc_html__( 'Border on hover', 'nexvio' ), array( $this->sel( '.nexvio-sp__btn:hover:not(:disabled)' ) => 'border-color: {{VALUE}};' ) );
		$this->add_control( 'btn_hover_anim', array(
			'label'        => esc_html__( 'Hover animation', 'nexvio' ),
			'type'         => Controls_Manager::SELECT,
			'default'      => 'lift',
			'options'      => array(
				'none'  => esc_html__( 'None', 'nexvio' ),
				'lift'  => esc_html__( 'Lift', 'nexvio' ),
				'grow'  => esc_html__( 'Grow', 'nexvio' ),
				'shine' => esc_html__( 'Brighten', 'nexvio' ),
			),
		) );
		$this->gs_heading( 'btn_icon_h', esc_html__( 'Icon', 'nexvio' ) );
		$this->add_responsive_control( 'btn_icon_size', array(
			'label'      => esc_html__( 'Icon size', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 10, 'max' => 48 ) ),
			'default'    => array( 'size' => 20, 'unit' => 'px' ),
			'selectors'  => array( $this->sel( '.nexvio-sp__icon' ) => 'font-size: {{SIZE}}{{UNIT}};', $this->sel( '.nexvio-sp__icon svg' ) => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ),
		) );
		$this->gs_color( 'btn_icon_color', esc_html__( 'Icon colour', 'nexvio' ), array( $this->sel( '.nexvio-sp__icon' ) => 'color: {{VALUE}}; fill: {{VALUE}};', $this->sel( '.nexvio-sp__icon svg' ) => 'fill: {{VALUE}};' ) );
		$this->add_responsive_control( 'btn_icon_gap', array(
			'label'      => esc_html__( 'Icon spacing', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 30 ) ),
			'default'    => array( 'size' => 10, 'unit' => 'px' ),
			'selectors'  => array( $b => 'gap: {{SIZE}}{{UNIT}};' ),
		) );
		$this->gs_heading( 'msg_h', esc_html__( 'Messages', 'nexvio' ) );
		$this->gs_color( 'msg_color', esc_html__( 'Message colour', 'nexvio' ), array( $this->sel( '.nexvio-sp__msg' ) => 'color: {{VALUE}};' ), array(), array( 'default' => '#374151' ) );
		$this->gs_color( 'msg_err', esc_html__( 'Error colour', 'nexvio' ), array( $this->sel( '.nexvio-sp__msg.is-error' ) => 'color: {{VALUE}};' ), array(), array( 'default' => '#b91c1c' ) );
		$this->end_controls_section();
	}

	/* ------------------------------------------------------------------
	 * Helpers
	 * ------------------------------------------------------------------ */

	private function icon_svg( $name ) {
		$icons = array(
			'wa'    => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12.04 2a9.9 9.9 0 0 0-8.5 14.9L2 22l5.25-1.38A9.9 9.9 0 1 0 12.04 2zm0 1.8a8.1 8.1 0 1 1-4.3 14.96l-.3-.19-3.12.82.83-3.04-.2-.31A8.1 8.1 0 0 1 12.04 3.8zM8.6 7.4c-.2 0-.5.07-.75.35-.26.28-1 .97-1 2.37s1.03 2.75 1.17 2.94c.15.2 2 3.2 4.95 4.36 2.44.96 2.94.77 3.47.72.53-.05 1.7-.7 1.94-1.37.24-.68.24-1.26.17-1.38-.07-.12-.27-.2-.56-.34-.3-.15-1.7-.84-1.97-.94-.26-.1-.46-.15-.65.15-.2.3-.75.94-.92 1.13-.17.2-.34.22-.63.07-.3-.15-1.22-.45-2.32-1.43-.86-.76-1.44-1.7-1.6-1.99-.17-.3-.02-.45.13-.6.13-.13.3-.34.44-.51.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.65-1.6-.9-2.18-.23-.56-.47-.48-.65-.49h-.55z"/></svg>',
			'cart'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/><path d="M2 3h3l2.4 12.2a1.6 1.6 0 0 0 1.6 1.3h8.7a1.6 1.6 0 0 0 1.6-1.2L21 7H6"/></svg>',
			'bolt'  => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M13 2 4 14h6l-1 8 9-12h-6z"/></svg>',
			'close' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6 6 18"/></svg>',
			'left'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m15 5-7 7 7 7"/></svg>',
			'right' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m9 5 7 7-7 7"/></svg>',
		);
		return isset( $icons[ $name ] ) ? $icons[ $name ] : '';
	}

	/** Icon markup: the chosen Elementor icon, or the built-in one. */
	private function icon_html( $icon, $default ) {
		if ( ! empty( $icon['value'] ) ) {
			ob_start();
			\Elementor\Icons_Manager::render_icon( $icon, array( 'aria-hidden' => 'true' ) );
			return '<span class="nexvio-sp__icon" aria-hidden="true">' . ob_get_clean() . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput -- Elementor escapes the icon.
		}
		return '<span class="nexvio-sp__icon" aria-hidden="true">' . $this->icon_svg( $default ) . '</span>';
	}

	/** The gallery images: featured image first, then the product gallery. */
	private function gallery_images( $product ) {
		$ids = array();
		if ( $product->get_image_id() ) {
			$ids[] = (int) $product->get_image_id();
		}
		foreach ( (array) $product->get_gallery_image_ids() as $gid ) {
			$gid = (int) $gid;
			if ( $gid && ! in_array( $gid, $ids, true ) ) {
				$ids[] = $gid;
			}
		}
		$out = array();
		foreach ( $ids as $id ) {
			$src = wp_get_attachment_image_src( $id, 'large' );
			if ( ! $src ) {
				continue;
			}
			$thumb = wp_get_attachment_image_src( $id, 'woocommerce_gallery_thumbnail' );
			$out[] = array(
				'id'     => $id,
				'src'    => $src[0],
				'w'      => (int) $src[1],
				'h'      => (int) $src[2],
				'srcset' => (string) wp_get_attachment_image_srcset( $id, 'large' ),
				'sizes'  => (string) wp_get_attachment_image_sizes( $id, 'large' ),
				'thumb'  => $thumb ? $thumb[0] : $src[0],
				'alt'    => trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) ),
			);
		}
		if ( ! $out ) {
			$ph    = wc_placeholder_img_src( 'large' );
			$out[] = array( 'id' => 0, 'src' => $ph, 'w' => 600, 'h' => 600, 'srcset' => '', 'sizes' => '', 'thumb' => $ph, 'alt' => '' );
		}
		return $out;
	}

	private function is_color_attribute( $label ) {
		return (bool) preg_match( '/colou?r|couleur|farbe|colore|kleur|রং|রঙ/iu', (string) $label );
	}

	/** Attribute rows for the variation picker. */
	private function variation_attributes( $product, $s ) {
		$rows = array();
		foreach ( (array) $product->get_variation_attributes() as $name => $options ) {
			$taxonomy = taxonomy_exists( $name );
			$label    = wc_attribute_label( $name, $product );
			$is_color = 'yes' === $s['color_swatches'] && ( $this->is_color_attribute( $label . ' ' . $name ) || ( $taxonomy && class_exists( 'Nexvio_Attribute_Types' ) && 'color' === Nexvio_Attribute_Types::type_of( $name ) ) );
			$list     = array();
			if ( $taxonomy ) {
				$terms = wc_get_product_terms( $product->get_id(), $name, array( 'fields' => 'all' ) );
				foreach ( $terms as $term ) {
					if ( in_array( $term->slug, $options, true ) ) {
						$list[] = array(
							'value' => $term->slug,
							'label' => $term->name,
							'color' => $is_color && class_exists( 'Nexvio_Swatches' ) ? Nexvio_Swatches::color_for( $term->name, (int) $term->term_id ) : '',
						);
					}
				}
			} else {
				foreach ( $options as $opt ) {
					$list[] = array(
						'value' => (string) $opt,
						'label' => (string) $opt,
						'color' => $is_color && class_exists( 'Nexvio_Swatches' ) ? Nexvio_Swatches::color_for( (string) $opt, 0 ) : '',
					);
				}
			}
			$rows[] = array(
				'name'    => 'attribute_' . sanitize_title( $name ),
				'label'   => $label,
				'options' => $list,
			);
		}
		return $rows;
	}

	private function button( $key, $cls, $act, $s, $default_icon ) {
		$text = (string) $s[ $key . '_text' ];
		$pos  = isset( $s[ $key . '_icon_pos' ] ) && 'after' === $s[ $key . '_icon_pos' ] ? ' nexvio-sp__btn--after' : '';
		return '<button type="button" class="nexvio-sp__btn nexvio-sp__btn--' . esc_attr( $cls ) . esc_attr( $pos ) . '" data-act="' . esc_attr( $act ) . '">'
			. $this->icon_html( $s[ $key . '_icon' ], $default_icon ) // phpcs:ignore WordPress.Security.EscapeOutput -- built in icon_html().
			. '<span class="nexvio-sp__label">' . esc_html( $text ) . '</span></button>';
	}

	/* ------------------------------------------------------------------
	 * Output
	 * ------------------------------------------------------------------ */

	/** Settings of the grid: the Products Card ones, without the single add-to-cart icon. */
	protected function grid_settings( $s ) {
		$s                = parent::grid_settings( $s );
		$s['show_button'] = '';
		return $s;
	}

	/** The Products Card, then the purchase buttons and the popup of the product. */
	protected function render_card( $product, $s, $rules, $wrap = 'slide' ) {
		ob_start();
		parent::render_card( $product, $s, $rules, $wrap );
		$html  = ob_get_clean();
		$block = $this->purchase_block( $product, $s );
		$end   = strripos( $html, '</article>' );
		if ( '' === $block || false === $end ) {
			echo $html; // phpcs:ignore WordPress.Security.EscapeOutput -- the card was escaped by the shared card code.
			return;
		}
		$body_end = strripos( substr( $html, 0, $end ), '</div>' );
		if ( false === $body_end ) {
			echo $html; // phpcs:ignore WordPress.Security.EscapeOutput -- the card was escaped by the shared card code.
			return;
		}
		echo substr( $html, 0, $body_end ) . $block . substr( $html, $body_end ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above and in purchase_block().
	}

	/** Card buttons + popup markup of one product. */
	private function purchase_block( $product, $s ) {
		$is_edit = class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode();
		ob_start();
		$id          = $product->get_id();
		$variable    = $product->is_type( 'variable' );
		$purchasable = $product->is_purchasable();
		$in_stock    = $product->is_in_stock();
		$popup       = 'yes' === $s['popup'];
		$inline      = $popup && $is_edit && 'yes' === $s['editor_preview'] && ! $this->sp_inline_used;
		if ( $inline ) {
			$this->sp_inline_used = true;
		}
		$images      = $this->gallery_images( $product );
		$many        = count( $images ) > 1;

		/* variations */
		$var_data = array();
		$var_rows = array();
		if ( $variable ) {
			$var_rows = $this->variation_attributes( $product, $s );
			if ( count( $product->get_children() ) <= 200 ) {
				$var_data = $product->get_available_variations();
			}
		}

		/* quantity */
		$min  = max( 1, (int) $product->get_min_purchase_quantity() );
		$max  = (int) $product->get_max_purchase_quantity(); // -1 = no limit
		if ( '' !== (string) $s['qty_min'] && (int) $s['qty_min'] > $min ) {
			$min = (int) $s['qty_min'];
		}
		if ( '' !== (string) $s['qty_max'] && (int) $s['qty_max'] >= $min && ( $max < 0 || (int) $s['qty_max'] < $max ) ) {
			$max = (int) $s['qty_max'];
		}
		$step     = max( 1, (int) $s['qty_step'] );
		$single   = $product->is_sold_individually() || ( $max > 0 && $max <= $min );
		$show_qty = 'yes' === $s['show_qty'] && ! $single && $purchasable && $in_stock;
		$can_buy  = $purchasable && ( $in_stock || $variable );

		/* classes */
		$classes = array( 'nexvio-sp', 'nexvio-sp--item' );
		$classes[] = $inline ? 'nexvio-sp--inline' : 'nexvio-sp--popup';
		$mclasses  = array(
			'nexvio-sp__modal',
			$inline ? 'nexvio-sp__modal--inline' : 'nexvio-sp__modal--popup',
			'nexvio-sp__modal--pos-' . sanitize_html_class( $s['popup_position'] ),
			'nexvio-sp--dm-' . sanitize_html_class( $s['btn_display'] ),
			'nexvio-sp--btns-' . sanitize_html_class( $s['btn_layout'] ),
			'nexvio-sp--thumbs-' . ( 'below' === $s['thumbs_pos'] ? 'below' : 'overlay' ),
		);
		$mclasses[] = 'nexvio-sp--hover-' . sanitize_html_class( $s['btn_hover_anim'] );

		$cfg = array(
			'popup'        => ! $inline,
			'closeOverlay' => 'yes' === $s['close_overlay'],
			'closeEsc'     => 'yes' === $s['close_esc'],
			'lock'         => 'yes' === $s['lock_scroll'],
			'animIn'       => (string) $s['anim_in'],
			'animOut'      => (string) $s['anim_out'],
			'dur'          => max( 0, (int) $s['anim_dur'] ),
			'ease'         => (string) $s['anim_ease'],
			'closeAfter'   => 'yes' === $s['atc_close'],
			'selector'     => '',
			'ajax'         => array(
				'add' => WC_AJAX::get_endpoint( 'nexvio_sp_add' ),
			),
			'nonce'        => Nexvio_Smart_Purchase_Ajax::nonce(),
			'cartUrl'      => wc_get_cart_url(),
			'cardAction'   => 'popup' === $s['card_action'] ? 'popup' : 'auto',
			'productUrl'   => $product->get_permalink(),
			'product'      => array(
				'id'    => $id,
				'name'  => wp_strip_all_tags( $product->get_name() ),
				'url'   => $product->get_permalink(),
				'price' => wp_strip_all_tags( html_entity_decode( $product->get_price_html(), ENT_QUOTES, 'UTF-8' ) ),
				'type'  => $variable ? 'variable' : 'simple',
				'min'   => $min,
				'max'   => $max,
				'step'  => $step,
			),
			'texts'        => array(
				'pick'        => (string) $s['txt_pick'],
				'unavailable' => (string) $s['txt_unavailable'],
				'adding'      => (string) $s['atc_adding'],
				'added'       => (string) $s['atc_added'],
				'viewCart'    => (string) $s['atc_view'],
				'error'       => (string) $s['txt_error'],
				'none'        => esc_html__( 'None', 'nexvio' ),
			),
		);
		?>
		<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-sp="<?php echo esc_attr( wp_json_encode( $cfg ) ); ?>" data-nexvio-side="<?php echo esc_attr( 'open' === $s['side_cart'] || 'no' === $s['side_cart'] ? $s['side_cart'] : 'global' ); ?>">

			<?php if ( 'yes' === $s['card_btns'] ) : ?>
				<div class="nexvio-sp__btns nexvio-sp__cbtns nexvio-sp--dm-<?php echo esc_attr( sanitize_html_class( $s['btn_display'] ) ); ?> nexvio-sp--btns-<?php echo esc_attr( sanitize_html_class( $s['btn_layout'] ) ); ?> nexvio-sp--hover-<?php echo esc_attr( sanitize_html_class( $s['btn_hover_anim'] ) ); ?>">
					<?php
					if ( $can_buy ) {
						if ( 'yes' === $s['atc_on'] ) {
							echo $this->button( 'atc', 'atc', 'add', $s, 'cart' ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in button().
						}
					}
					?>
				</div>
				<p class="nexvio-sp__msg nexvio-sp__cmsg" role="status" aria-live="polite"></p>
			<?php endif; ?>

			<div class="<?php echo esc_attr( implode( ' ', $mclasses ) ); ?>" data-nexvio-side="<?php echo esc_attr( 'open' === $s['side_cart'] || 'no' === $s['side_cart'] ? $s['side_cart'] : 'global' ); ?>" data-anim-in="<?php echo esc_attr( $s['anim_in'] ); ?>" data-anim-out="<?php echo esc_attr( $s['anim_out'] ); ?>" style="--nexvio-sp-dur:<?php echo esc_attr( max( 0, (int) $s['anim_dur'] ) ); ?>ms;--nexvio-sp-ease:<?php echo esc_attr( $s['anim_ease'] ); ?>"<?php echo $inline ? '' : ' hidden'; ?>>
				<?php if ( ! $inline && 'yes' === $s['sp_overlay'] ) : ?>
					<div class="nexvio-sp__overlay" data-sp-close="1"></div>
				<?php elseif ( ! $inline ) : ?>
					<div class="nexvio-sp__overlay nexvio-sp__overlay--none" data-sp-close="1"></div>
				<?php endif; ?>

				<div class="nexvio-sp__dialog" <?php echo $inline ? '' : 'role="dialog" aria-modal="true" tabindex="-1"'; ?> aria-labelledby="nexvio-sp-title-<?php echo esc_attr( $this->get_id() . '-' . $id ); ?>">
					<?php if ( ! $inline && 'yes' === $s['close_btn'] ) : ?>
						<button type="button" class="nexvio-sp__close" data-sp-close="1" aria-label="<?php echo esc_attr( $s['close_label'] ); ?>"><?php echo $this->icon_svg( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?></button>
					<?php endif; ?>

					<div class="nexvio-sp__scroll">
						<div class="nexvio-sp__grid">

							<!-- LEFT: main image, thumbnails inside the image -->
							<div class="nexvio-sp__media">
								<div class="nexvio-sp__stage">
									<div class="nexvio-sp__main">
										<img class="nexvio-sp__img" src="<?php echo esc_url( $images[0]['src'] ); ?>"<?php echo $images[0]['srcset'] ? ' srcset="' . esc_attr( $images[0]['srcset'] ) . '" sizes="' . esc_attr( $images[0]['sizes'] ) . '"' : ''; ?> alt="<?php echo esc_attr( $images[0]['alt'] ? $images[0]['alt'] : $product->get_name() ); ?>" data-default-src="<?php echo esc_url( $images[0]['src'] ); ?>" data-default-srcset="<?php echo esc_attr( $images[0]['srcset'] ); ?>" data-default-sizes="<?php echo esc_attr( $images[0]['sizes'] ); ?>"<?php echo $images[0]['w'] && $images[0]['h'] ? ' width="' . esc_attr( $images[0]['w'] ) . '" height="' . esc_attr( $images[0]['h'] ) . '"' : ''; ?> draggable="false">
									</div>

									<?php if ( $many && 'yes' === $s['show_nav'] ) : ?>
										<button type="button" class="nexvio-sp__nav nexvio-sp__nav--prev" data-sp-nav="-1" aria-label="<?php esc_attr_e( 'Previous image', 'nexvio' ); ?>"><?php echo $this->icon_svg( 'left' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?></button>
										<button type="button" class="nexvio-sp__nav nexvio-sp__nav--next" data-sp-nav="1" aria-label="<?php esc_attr_e( 'Next image', 'nexvio' ); ?>"><?php echo $this->icon_svg( 'right' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?></button>
									<?php endif; ?>

									<?php if ( $many ) : ?>
										<div class="nexvio-sp__thumbs<?php echo 'yes' === $s['show_thumbs'] ? '' : ' nexvio-sp__thumbs--hidden'; ?>" role="group" aria-label="<?php esc_attr_e( 'Product images', 'nexvio' ); ?>">
											<?php foreach ( $images as $i => $img ) : ?>
												<button type="button" class="nexvio-sp__thumb<?php echo 0 === $i ? ' is-active' : ''; ?>" data-sp-thumb="<?php echo esc_attr( $i ); ?>" data-id="<?php echo esc_attr( $img['id'] ); ?>" data-src="<?php echo esc_url( $img['src'] ); ?>" data-srcset="<?php echo esc_attr( $img['srcset'] ); ?>" data-sizes="<?php echo esc_attr( $img['sizes'] ); ?>" data-alt="<?php echo esc_attr( $img['alt'] ); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %d: image number */ __( 'Show image %d', 'nexvio' ), $i + 1 ) ); ?>" aria-pressed="<?php echo 0 === $i ? 'true' : 'false'; ?>">
													<img src="<?php echo esc_url( $img['thumb'] ); ?>" alt="" loading="lazy" draggable="false">
												</button>
											<?php endforeach; ?>
										</div>
									<?php endif; ?>
								</div>
							</div>

							<!-- RIGHT: product information and purchase -->
							<div class="nexvio-sp__info">
								<?php if ( 'yes' === $s['pp_title'] ) : ?>
									<?php
									$tag = in_array( $s['title_tag'], array( 'h2', 'h3', 'h4', 'div', 'p' ), true ) ? $s['title_tag'] : 'h2';
									printf( '<%1$s class="nexvio-sp__title" id="nexvio-sp-title-%2$s">%3$s</%1$s>', esc_attr( $tag ), esc_attr( $this->get_id() . '-' . $id ), esc_html( $product->get_name() ) );
									?>
								<?php endif; ?>

								<?php if ( 'yes' === $s['pp_rating'] && wc_review_ratings_enabled() && $product->get_average_rating() > 0 ) : ?>
									<div class="nexvio-sp__rating">
										<?php echo wp_kses_post( wc_get_rating_html( $product->get_average_rating(), $product->get_rating_count() ) ); ?>
										<span class="nexvio-sp__rating-count">(<?php echo esc_html( $product->get_review_count() ); ?>)</span>
									</div>
								<?php endif; ?>

								<?php if ( 'yes' === $s['pp_price'] ) : ?>
									<div class="nexvio-sp__price" data-default="<?php echo esc_attr( $product->get_price_html() ); ?>"><?php echo wp_kses_post( $product->get_price_html() ); ?></div>
								<?php endif; ?>

								<?php if ( 'yes' === $s['pp_desc'] && $product->get_short_description() ) : ?>
									<div class="nexvio-sp__desc"><?php echo wp_kses_post( apply_filters( 'woocommerce_short_description', $product->get_short_description() ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core hook. ?></div>
								<?php endif; ?>

								<?php if ( ( 'yes' === $s['pp_sku'] && $product->get_sku() ) || 'yes' === $s['pp_stock'] ) : ?>
									<div class="nexvio-sp__meta">
										<?php if ( 'yes' === $s['pp_sku'] && $product->get_sku() ) : ?>
											<span class="nexvio-sp__sku"><?php esc_html_e( 'SKU:', 'nexvio' ); ?> <span data-default="<?php echo esc_attr( $product->get_sku() ); ?>"><?php echo esc_html( $product->get_sku() ); ?></span></span>
										<?php endif; ?>
										<?php
										if ( 'yes' === $s['pp_stock'] ) :
											$av = $product->get_availability();
											if ( ! empty( $av['availability'] ) ) :
												?>
												<span class="nexvio-sp__stock <?php echo $in_stock ? 'is-in' : 'is-out'; ?>" data-default="<?php echo esc_attr( $av['availability'] ); ?>" data-default-class="<?php echo $in_stock ? 'is-in' : 'is-out'; ?>"><?php echo esc_html( $av['availability'] ); ?></span>
											<?php endif; ?>
										<?php endif; ?>
									</div>
								<?php endif; ?>

								<form class="nexvio-sp__form" method="post" novalidate data-product_id="<?php echo esc_attr( $id ); ?>"<?php echo $variable ? ' data-variations="' . esc_attr( wp_json_encode( $var_data ) ) . '"' : ''; ?>>
									<?php if ( $variable ) : ?>
										<div class="nexvio-sp__vars">
											<?php foreach ( $var_rows as $row ) : ?>
												<div class="nexvio-sp__var" data-name="<?php echo esc_attr( $row['name'] ); ?>" data-value="">
													<?php if ( 'yes' === $s['show_vars_labels'] ) : ?>
														<div class="nexvio-sp__varhead">
															<span class="nexvio-sp__varlabel" id="nexvio-sp-l-<?php echo esc_attr( $this->get_id() . '-' . $row['name'] ); ?>"><?php echo esc_html( $row['label'] ); ?></span>
															<span class="nexvio-sp__varvalue"></span>
														</div>
													<?php endif; ?>
													<div class="nexvio-sp__opts" role="radiogroup" aria-label="<?php echo esc_attr( $row['label'] ); ?>">
														<?php foreach ( $row['options'] as $opt ) : ?>
															<?php $is_color = ! empty( $opt['color'] ); ?>
															<button type="button" role="radio" aria-checked="false" class="nexvio-sp__opt<?php echo $is_color ? ' nexvio-sp__opt--color' : ''; ?>" data-value="<?php echo esc_attr( $opt['value'] ); ?>" data-label="<?php echo esc_attr( $opt['label'] ); ?>"<?php echo $is_color ? ' style="--nexvio-sp-swatch:' . esc_attr( $opt['color'] ) . '" title="' . esc_attr( $opt['label'] ) . '"' : ''; ?>>
																<?php if ( $is_color ) : ?>
																	<span class="nexvio-sp__swatch" aria-hidden="true"></span><span class="screen-reader-text nexvio-sp__sr"><?php echo esc_html( $opt['label'] ); ?></span>
																<?php else : ?>
																	<?php echo esc_html( $opt['label'] ); ?>
																<?php endif; ?>
															</button>
														<?php endforeach; ?>
													</div>
												</div>
											<?php endforeach; ?>
											<button type="button" class="nexvio-sp__clear" hidden><?php echo esc_html( $s['txt_clear'] ); ?></button>
										</div>
										<div class="nexvio-sp__varprice" hidden></div>
									<?php endif; ?>

									<?php if ( $show_qty ) : ?>
										<div class="nexvio-sp__qtywrap">
											<span class="nexvio-sp__qtylabel"><?php echo esc_html( $s['qty_label'] ); ?></span>
											<div class="nexvio-sp__qty">
												<button type="button" class="nexvio-sp__step" data-step="-1" aria-label="<?php esc_attr_e( 'Decrease quantity', 'nexvio' ); ?>">&minus;</button>
												<input type="number" class="nexvio-sp__qty-input" name="quantity" value="<?php echo esc_attr( $min ); ?>" min="<?php echo esc_attr( $min ); ?>"<?php echo $max > 0 ? ' max="' . esc_attr( $max ) . '"' : ''; ?> step="<?php echo esc_attr( $step ); ?>" inputmode="numeric" autocomplete="off" aria-label="<?php echo esc_attr( $s['qty_label'] ); ?>">
												<button type="button" class="nexvio-sp__step" data-step="1" aria-label="<?php esc_attr_e( 'Increase quantity', 'nexvio' ); ?>">+</button>
											</div>
										</div>
									<?php else : ?>
										<input type="hidden" name="quantity" value="<?php echo esc_attr( $min ); ?>">
									<?php endif; ?>

									<?php if ( $variable ) : ?>
										<input type="hidden" name="variation_id" value="0">
									<?php endif; ?>

									<div class="nexvio-sp__btns">
										<?php
										if ( $can_buy ) {
											if ( 'yes' === $s['atc_on'] ) {
												echo $this->button( 'atc', 'atc', 'add', $s, 'cart' ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in button().
											}
										}
										?>
									</div>
									<p class="nexvio-sp__msg" role="status" aria-live="polite"><?php echo $can_buy ? '' : esc_html__( 'This product is not available for purchase right now.', 'nexvio' ); ?></p>
								</form>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
