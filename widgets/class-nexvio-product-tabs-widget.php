<?php
/**
 * NEXVIO Product Tabs - Elementor widget for the single product page.
 *
 * Three tabs: Description, Reviews and Add Review.
 *  - Reviews: rating summary with a clickable 5-to-1 star filter, sort, review cards,
 *    "read more" on long reviews and a "load more" button (AJAX), so hundreds of
 *    reviews stay easy to browse.
 *  - Add Review: premium form with a big, touch friendly star selector.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

class Nexvio_Product_Tabs_Widget extends Widget_Base {

	public function get_name() {
		return 'nexvio_product_tabs';
	}

	public function get_title() {
		return esc_html__( 'Product Tabs', 'nexvio' );
	}

	public function get_icon() {
		return 'eicon-product-tabs';
	}

	public function get_categories() {
		return array( 'nexvio' );
	}

	public function get_keywords() {
		return array( 'nexvio', 'woocommerce', 'tabs', 'description', 'reviews', 'review', 'rating', 'single product' );
	}

	public function get_style_depends() {
		return array( 'nexvio-layout', 'nexvio-product-tabs' );
	}

	public function get_script_depends() {
		return array( 'nexvio-product-tabs' );
	}

	/* ------------------------------------------------------------------
	 * Controls
	 * ------------------------------------------------------------------ */

	private function sw( $id, $label, $default = 'yes', $extra = array() ) {
		$this->add_control( $id, array_merge( array(
			'label'        => $label,
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => $default,
		), $extra ) );
	}

	private function tx( $id, $label, $default, $extra = array() ) {
		$this->add_control( $id, array_merge( array(
			'label'   => $label,
			'type'    => Controls_Manager::TEXT,
			'default' => $default,
		), $extra ) );
	}

	private function var_color( $id, $label, $var, $default = '' ) {
		$this->add_control( $id, array(
			'label'     => $label,
			'type'      => Controls_Manager::COLOR,
			'default'   => $default,
			'selectors' => array( '{{WRAPPER}} .nxt' => $var . ': {{VALUE}};' ),
		) );
	}

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

		/* ============ TABS ============ */
		$this->start_controls_section( 'section_tabs', array( 'label' => esc_html__( 'Tabs', 'nexvio' ) ) );
		$this->tx( 'lbl_desc', esc_html__( 'Tab 1 label', 'nexvio' ), 'Description' );
		$this->tx( 'lbl_rev', esc_html__( 'Tab 2 label', 'nexvio' ), 'Reviews' );
		$this->sw( 'show_ship', esc_html__( 'Shipping & Returns tab', 'nexvio' ) );
		$this->tx( 'lbl_ship', esc_html__( 'Tab 3 label', 'nexvio' ), 'Shipping & Returns', array( 'condition' => array( 'show_ship' => 'yes' ) ) );
		$this->add_control( 'ship_content', array(
			'label'     => esc_html__( 'Shipping & Returns text', 'nexvio' ),
			'type'      => Controls_Manager::WYSIWYG,
			'default'   => '<p>Free standard shipping on orders over $50. Orders ship within 1-2 business days.</p><p>Not happy with your purchase? Return it within 30 days for a full refund. Items must be unused and in the original packaging.</p>',
			'condition' => array( 'show_ship' => 'yes' ),
		) );
		$this->tx( 'lbl_add', esc_html__( 'Add review tab label (only when the form is not a popup)', 'nexvio' ), 'Add Review' );
		$this->sw( 'show_count', esc_html__( 'Show the number of reviews on the Reviews tab', 'nexvio' ) );
		$this->sw( 'tab_icons', esc_html__( 'Icons in the tabs', 'nexvio' ) );
		$this->add_control( 'tab_design', array(
			'label'   => esc_html__( 'Tabs design', 'nexvio' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'folder',
			'options' => array(
				'folder'    => esc_html__( 'Folder tabs (equal width, dark active tab)', 'nexvio' ),
				'underline' => esc_html__( 'Underline', 'nexvio' ),
				'pills'     => esc_html__( 'Pills (rounded)', 'nexvio' ),
				'boxed'     => esc_html__( 'Segmented (boxed)', 'nexvio' ),
			),
		) );
		$this->add_control( 'default_tab', array(
			'label'   => esc_html__( 'Open first', 'nexvio' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'desc',
			'options' => array(
				'desc' => esc_html__( 'Description', 'nexvio' ),
				'rev'  => esc_html__( 'Reviews', 'nexvio' ),
				'ship' => esc_html__( 'Shipping & Returns', 'nexvio' ),
				'add'  => esc_html__( 'Add Review', 'nexvio' ),
			),
		) );
		$this->add_control( 'tabs_align', array(
			'label'     => esc_html__( 'Tabs alignment', 'nexvio' ),
			'type'      => Controls_Manager::CHOOSE,
			'options'   => array(
				'flex-start' => array( 'title' => esc_html__( 'Left', 'nexvio' ), 'icon' => 'eicon-text-align-left' ),
				'center'     => array( 'title' => esc_html__( 'Center', 'nexvio' ), 'icon' => 'eicon-text-align-center' ),
				'flex-end'   => array( 'title' => esc_html__( 'Right', 'nexvio' ), 'icon' => 'eicon-text-align-right' ),
			),
			'default'   => 'center',
			'selectors' => array( '{{WRAPPER}} .nxt__tabs' => 'justify-content: {{VALUE}};' ),
		) );
		$this->end_controls_section();

		/* ============ DESCRIPTION ============ */
		$this->start_controls_section( 'section_description', array( 'label' => esc_html__( 'Description', 'nexvio' ) ) );
		$this->add_control( 'desc_align', array(
			'label'     => esc_html__( 'Text alignment', 'nexvio' ),
			'type'      => Controls_Manager::CHOOSE,
			'options'   => array(
				'left'   => array( 'title' => esc_html__( 'Left', 'nexvio' ), 'icon' => 'eicon-text-align-left' ),
				'center' => array( 'title' => esc_html__( 'Center', 'nexvio' ), 'icon' => 'eicon-text-align-center' ),
				'right'  => array( 'title' => esc_html__( 'Right', 'nexvio' ), 'icon' => 'eicon-text-align-right' ),
			),
			'default'   => 'left',
			'toggle'    => false,
			'selectors' => array( '{{WRAPPER}} .nxt__desc' => 'text-align: {{VALUE}};' ),
		) );
		$this->add_responsive_control( 'desc_width', array(
			'label'      => esc_html__( 'Max width', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px', '%' ),
			'range'      => array( 'px' => array( 'min' => 320, 'max' => 1600 ), '%' => array( 'min' => 30, 'max' => 100 ) ),
			'default'    => array( 'size' => 100, 'unit' => '%' ),
			'selectors'  => array( '{{WRAPPER}} .nxt__desc' => 'max-width: {{SIZE}}{{UNIT}};' ),
		) );
		$this->end_controls_section();

		/* ============ REVIEWS ============ */
		$this->start_controls_section( 'section_reviews', array( 'label' => esc_html__( 'Reviews list', 'nexvio' ) ) );
		$this->add_control( 'per_page', array(
			'label'       => esc_html__( 'Reviews shown first', 'nexvio' ),
			'type'        => Controls_Manager::NUMBER,
			'min'         => 1,
			'max'         => 50,
			'default'     => 5,
			'description' => esc_html__( 'The "Load more" button shows this many more each time.', 'nexvio' ),
		) );
		$this->add_control( 'rev_design', array(
			'label'   => esc_html__( 'Review design', 'nexvio' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'cards',
			'options' => array(
				'cards' => esc_html__( 'Cards', 'nexvio' ),
				'lines' => esc_html__( 'Simple lines', 'nexvio' ),
			),
		) );
		$this->add_control( 'rev_cols', array(
			'label'     => esc_html__( 'Review columns (desktop)', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => '1',
			'options'   => array( '1' => '1', '2' => '2' ),
			'condition' => array( 'rev_design' => 'cards' ),
		) );
		$this->sw( 'show_dist', esc_html__( 'Star bars (click a bar to filter)', 'nexvio' ) );
		$this->sw( 'show_sort', esc_html__( 'Sort menu', 'nexvio' ) );
		$this->sw( 'show_verified', esc_html__( '"Verified purchase" badge', 'nexvio' ) );
		$this->tx( 'txt_verified', esc_html__( 'Badge text', 'nexvio' ), 'Verified Buyer', array( 'condition' => array( 'show_verified' => 'yes' ) ) );
		$this->sw( 'show_helpful', esc_html__( '"Helpful" button on each review', 'nexvio' ) );
		$this->sw( 'allow_edit', esc_html__( 'Reviewers can edit their own review (after it is approved)', 'nexvio' ) );
		$this->tx( 'txt_edit', esc_html__( '"Edit" button', 'nexvio' ), 'Edit', array( 'condition' => array( 'allow_edit' => 'yes' ) ) );
		$this->sw( 'allow_reply', esc_html__( 'Visitors can reply to a review', 'nexvio' ) );
		$this->tx( 'txt_reply', esc_html__( '"Reply" button', 'nexvio' ), 'Reply', array( 'condition' => array( 'allow_reply' => 'yes' ) ) );
		$this->sw( 'sticky_pin', esc_html__( 'Pinned review card stays at the top while scrolling', 'nexvio' ), 'yes', array(
			'description' => esc_html__( 'The whole pinned card (review, replies and buttons) sticks on tablet and desktop, single column. If a sticky header covers it, raise the distance below.', 'nexvio' ),
		) );
		$this->add_responsive_control( 'sticky_top', array(
			'label'      => esc_html__( 'Sticky distance from the top', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 240 ) ),
			'default'    => array( 'size' => 16, 'unit' => 'px' ),
			'condition'  => array( 'sticky_pin' => 'yes' ),
			'selectors'  => array( '{{WRAPPER}} .nxt' => '--nxt-sticky-top: {{SIZE}}{{UNIT}};' ),
		) );
		$this->tx( 'txt_write', esc_html__( '"Write a review" button', 'nexvio' ), 'Write a Review', array( 'separator' => 'before' ) );
		$this->tx( 'txt_sort', esc_html__( 'Sort label', 'nexvio' ), 'Sort by' );
		$this->tx( 'txt_more', esc_html__( '"Load more" button', 'nexvio' ), 'Load more reviews' );
		$this->tx( 'txt_nomatch', esc_html__( 'No review for the chosen stars', 'nexvio' ), 'No reviews with this rating yet.' );
		$this->tx( 'txt_empty_title', esc_html__( 'No reviews: title', 'nexvio' ), 'No reviews yet' );
		$this->tx( 'txt_empty_text', esc_html__( 'No reviews: text', 'nexvio' ), 'Be the first to share your experience with this product.' );
		$this->end_controls_section();

		/* ============ FORM ============ */
		$this->start_controls_section( 'section_form', array( 'label' => esc_html__( 'Add review form', 'nexvio' ) ) );
		$this->add_control( 'form_mode', array(
			'label'       => esc_html__( 'Show the form', 'nexvio' ),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'popup',
			'options'     => array(
				'popup' => esc_html__( 'In a popup (opens from the tab and the "Write a review" buttons)', 'nexvio' ),
				'tab'   => esc_html__( 'Inside a tab', 'nexvio' ),
			),
		) );
		$this->tx( 'f_title', esc_html__( 'Title', 'nexvio' ), 'Share your experience' );
		$this->tx( 'f_intro', esc_html__( 'Intro text', 'nexvio' ), 'Your honest opinion helps other customers choose with confidence.' );
		$this->sw( 'f_side', esc_html__( 'Show the product panel next to the form', 'nexvio' ) );
		$this->tx( 'f_rating', esc_html__( 'Rating label', 'nexvio' ), 'Your rating', array( 'separator' => 'before' ) );
		$this->tx( 'f_rating_words', esc_html__( 'Star names (comma separated, 1 to 5)', 'nexvio' ), 'Poor, Fair, Good, Very good, Excellent' );
		$this->tx( 'f_review', esc_html__( 'Review label', 'nexvio' ), 'Your review' );
		$this->tx( 'f_review_ph', esc_html__( 'Review placeholder', 'nexvio' ), 'What did you like or dislike? How is the quality, the fit, the finish?' );
		$this->tx( 'f_name', esc_html__( 'Name label', 'nexvio' ), 'Name' );
		$this->tx( 'f_email', esc_html__( 'Email label', 'nexvio' ), 'Email' );
		$this->tx( 'f_consent', esc_html__( 'Save my details text', 'nexvio' ), 'Save my name and email for my next review.' );
		$this->tx( 'f_submit', esc_html__( 'Submit button', 'nexvio' ), 'Submit review' );
		$this->tx( 'f_sending', esc_html__( 'Button while sending', 'nexvio' ), 'Sending…' );
		$this->tx( 'f_view', esc_html__( '"See reviews" button (after sending)', 'nexvio' ), 'See all reviews' );
		$this->end_controls_section();

		/* ============ STYLE: COLOURS (main) ============ */
		$this->start_controls_section( 'style_colors', array( 'label' => esc_html__( 'Colours (main)', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_control( 'colors_note', array(
			'type'            => Controls_Manager::RAW_HTML,
			'raw'             => esc_html__( 'Change these 3 colours and the whole widget follows: tabs, reviews, rating summary, buttons, Add review popup and Read more popup. The other style sections are only for fine tuning.', 'nexvio' ),
			'content_classes' => 'elementor-descriptor',
		) );
		$this->var_color( 'c_bg', esc_html__( 'Background colour', 'nexvio' ), '--nxt-bg', '#ffffff' );
		$this->var_color( 'c_head', esc_html__( 'Heading colour (headings, tabs, buttons)', 'nexvio' ), '--nxt-head', '#111827' );
		$this->var_color( 'c_body', esc_html__( 'Text colour', 'nexvio' ), '--nxt-body', '#4b5563' );
		$this->var_color( 'c_star', esc_html__( 'Star colour (optional)', 'nexvio' ), '--nxt-star', '#f5a623' );
		$this->add_control( 'radius', array(
			'label'     => esc_html__( 'Cards corner radius', 'nexvio' ),
			'type'      => Controls_Manager::SLIDER,
			'range'     => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
			'selectors' => array( '{{WRAPPER}} .nxt' => '--nxt-r: {{SIZE}}px;' ),
		) );
		$this->end_controls_section();

		/* ============ STYLE: WIDGET CARD ============ */
		$this->start_controls_section( 'style_card', array( 'label' => esc_html__( 'Whole card (background)', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_group_control( Group_Control_Background::get_type(), array(
			'name'     => 'card_bg',
			'types'    => array( 'classic', 'gradient' ),
			'selector' => '{{WRAPPER}} .nxt',
		) );
		$this->add_group_control( Group_Control_Border::get_type(), array(
			'name'     => 'card_border',
			'selector' => '{{WRAPPER}} .nxt',
		) );
		$this->add_responsive_control( 'card_radius', array(
			'label'      => esc_html__( 'Corner radius', 'nexvio' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', '%' ),
			'selectors'  => array( '{{WRAPPER}} .nxt' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );
		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array(
			'name'     => 'card_shadow',
			'selector' => '{{WRAPPER}} .nxt',
		) );
		$this->add_responsive_control( 'card_pad', array(
			'label'      => esc_html__( 'Padding', 'nexvio' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em', '%' ),
			'default'    => array( 'top' => '20', 'right' => '30', 'bottom' => '30', 'left' => '30', 'unit' => 'px', 'isLinked' => false ),
			'selectors'  => array( '{{WRAPPER}} .nxt' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );
		$this->end_controls_section();

		/* ============ STYLE: TABS ============ */
		$this->start_controls_section( 'style_tabs', array( 'label' => esc_html__( 'Tabs', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'typo_tab',
			'selector' => '{{WRAPPER}} .nxt__tab',
		) );
		$this->add_control( 'h_tab_active', array( 'label' => esc_html__( 'Active tab', 'nexvio' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_group_control( Group_Control_Background::get_type(), array(
			'name'     => 'tab_active_bg',
			'label'    => esc_html__( 'Active tab background / line', 'nexvio' ),
			'types'    => array( 'classic', 'gradient' ),
			'selector' => '{{WRAPPER}} .nxt .nxt__ink',
		) );
		$this->var_color( 'tab_active_color', esc_html__( 'Active tab text', 'nexvio' ), '--nxt-tab-ac' );
		$this->add_control( 'h_tab_normal', array( 'label' => esc_html__( 'Normal tab', 'nexvio' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->var_color( 'tab_color', esc_html__( 'Text', 'nexvio' ), '--nxt-tab-c' );
		$this->var_color( 'tab_hover_color', esc_html__( 'Text on hover', 'nexvio' ), '--nxt-tab-hc' );
		$this->var_color( 'tab_hover_bg', esc_html__( 'Background on hover', 'nexvio' ), '--nxt-tab-hbg' );
		$this->add_control( 'h_tab_bar', array( 'label' => esc_html__( 'Tabs bar', 'nexvio' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_group_control( Group_Control_Background::get_type(), array(
			'name'     => 'tabs_bar_bg',
			'label'    => esc_html__( 'Bar background', 'nexvio' ),
			'types'    => array( 'classic', 'gradient' ),
			'selector' => '{{WRAPPER}} .nxt__bar',
		) );
		$this->var_color( 'tabs_line', esc_html__( 'Bar line (underline design)', 'nexvio' ), '--nxt-line' );
		$this->add_responsive_control( 'tab_radius', array(
			'label'     => esc_html__( 'Tab corner radius', 'nexvio' ),
			'type'      => Controls_Manager::SLIDER,
			'range'     => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
			'selectors' => array( '{{WRAPPER}} .nxt' => '--nxt-tab-r: {{SIZE}}px;' ),
		) );
		$this->add_responsive_control( 'tab_pad', array(
			'label'      => esc_html__( 'Tab padding', 'nexvio' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em' ),
			'selectors'  => array( '{{WRAPPER}} .nxt__tab' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'tab_gap', array(
			'label'     => esc_html__( 'Space between tabs', 'nexvio' ),
			'type'      => Controls_Manager::SLIDER,
			'range'     => array( 'px' => array( 'min' => 0, 'max' => 80 ) ),
			'selectors' => array( '{{WRAPPER}} .nxt__bar' => 'gap: {{SIZE}}px;' ),
		) );
		$this->end_controls_section();

		/* ============ STYLE: DESCRIPTION ============ */
		$this->start_controls_section( 'style_desc', array( 'label' => esc_html__( 'Description', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'typo_body',
			'selector' => '{{WRAPPER}} .nxt__desc',
		) );
		$this->add_control( 'desc_color', array(
			'label'     => esc_html__( 'Text colour', 'nexvio' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .nxt__desc' => 'color: {{VALUE}};' ),
		) );
		$this->add_control( 'desc_head_color', array(
			'label'     => esc_html__( 'Headings colour', 'nexvio' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .nxt__desc h1, {{WRAPPER}} .nxt__desc h2, {{WRAPPER}} .nxt__desc h3, {{WRAPPER}} .nxt__desc h4' => 'color: {{VALUE}};' ),
		) );
		$this->add_responsive_control( 'panel_pad', array(
			'label'      => esc_html__( 'Panel padding', 'nexvio' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em', '%' ),
			'selectors'  => array( '{{WRAPPER}} .nxt__panel' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );
		$this->end_controls_section();

		/* ============ STYLE: REVIEWS ============ */
		$this->start_controls_section( 'style_reviews', array( 'label' => esc_html__( 'Reviews', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_control( 'h_sum', array( 'label' => esc_html__( 'Rating summary card', 'nexvio' ), 'type' => Controls_Manager::HEADING ) );
		$this->add_group_control( Group_Control_Background::get_type(), array(
			'name'     => 'sum_bg',
			'types'    => array( 'classic', 'gradient' ),
			'selector' => '{{WRAPPER}} .nxt-sum',
		) );
		$this->add_group_control( Group_Control_Border::get_type(), array(
			'name'     => 'sum_border',
			'selector' => '{{WRAPPER}} .nxt-sum',
		) );
		$this->add_control( 'sum_num_color', array(
			'label'     => esc_html__( 'Big number colour', 'nexvio' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .nxt-sum__num' => 'color: {{VALUE}};' ),
		) );
		$this->add_responsive_control( 'card_inner_pad', array(
			'label'      => esc_html__( 'Padding (summary and review cards)', 'nexvio' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em' ),
			'selectors'  => array( '{{WRAPPER}} .nxt-sum, {{WRAPPER}} .nxt-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );
		$this->add_control( 'h_item', array( 'label' => esc_html__( 'Review card', 'nexvio' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_group_control( Group_Control_Background::get_type(), array(
			'name'     => 'item_bg',
			'types'    => array( 'classic', 'gradient' ),
			'selector' => '{{WRAPPER}} .nxt--cards .nxt-item',
		) );
		$this->add_group_control( Group_Control_Border::get_type(), array(
			'name'     => 'item_border',
			'selector' => '{{WRAPPER}} .nxt--cards .nxt-item',
		) );
		$this->add_responsive_control( 'item_radius', array(
			'label'      => esc_html__( 'Corner radius', 'nexvio' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', '%' ),
			'selectors'  => array( '{{WRAPPER}} .nxt--cards .nxt-item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );
		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array(
			'name'     => 'item_shadow',
			'selector' => '{{WRAPPER}} .nxt--cards .nxt-item',
		) );
		$this->add_responsive_control( 'rev_gap', array(
			'label'      => esc_html__( 'Space between reviews', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 80 ) ),
			'default'    => array( 'size' => 20, 'unit' => 'px' ),
			'selectors'  => array( '{{WRAPPER}} .nxt' => '--nxt-rgap: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'rev_lines', array(
			'label'       => esc_html__( 'Review text lines before "Read more"', 'nexvio' ),
			'type'        => Controls_Manager::SLIDER,
			'size_units'  => array( 'px' ),
			'range'       => array( 'px' => array( 'min' => 1, 'max' => 10, 'step' => 1 ) ),
			'default'     => array( 'size' => 3, 'unit' => 'px' ),
			'description' => esc_html__( 'Longer reviews are cut after this many lines. "Read more" opens the whole review in a popup.', 'nexvio' ),
			'selectors'   => array( '{{WRAPPER}} .nxt' => '--nxt-lines: {{SIZE}};' ),
		) );
		$this->add_control( 'more_color', array(
			'label'     => esc_html__( '"Read more" colour', 'nexvio' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .nxt-more' => 'color: {{VALUE}};' ),
		) );
		$this->add_responsive_control( 'item_pad', array(
			'label'      => esc_html__( 'Review card padding', 'nexvio' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em' ),
			'selectors'  => array( '{{WRAPPER}} .nxt--cards .nxt-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'avatar_size', array(
			'label'      => esc_html__( 'Avatar size', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 28, 'max' => 100 ) ),
			'selectors'  => array( '{{WRAPPER}} .nxt' => '--nxt-avatar: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_control( 'h_txt', array( 'label' => esc_html__( 'Text', 'nexvio' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'typo_name',
			'label'    => esc_html__( 'Reviewer name', 'nexvio' ),
			'selector' => '{{WRAPPER}} .nxt-item__name',
		) );
		$this->add_control( 'name_color', array(
			'label'     => esc_html__( 'Name colour', 'nexvio' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .nxt-item__name' => 'color: {{VALUE}};' ),
		) );
		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'typo_review',
			'label'    => esc_html__( 'Review text', 'nexvio' ),
			'selector' => '{{WRAPPER}} .nxt-item__text',
		) );
		$this->add_control( 'review_color', array(
			'label'     => esc_html__( 'Review text colour', 'nexvio' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .nxt-item__text' => 'color: {{VALUE}};' ),
		) );
		$this->add_control( 'avatar_bg', array(
			'label'     => esc_html__( 'Avatar background', 'nexvio' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .nxt-avatar' => 'background: {{VALUE}};' ),
		) );
		$this->add_control( 'avatar_color', array(
			'label'     => esc_html__( 'Avatar letters', 'nexvio' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .nxt-avatar' => 'color: {{VALUE}};' ),
		) );
		$this->end_controls_section();

		/* ============ STYLE: FORM & POPUP ============ */
		$this->start_controls_section( 'style_form', array( 'label' => esc_html__( 'Review form & popup', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_control( 'h_pop', array( 'label' => esc_html__( 'Popup', 'nexvio' ), 'type' => Controls_Manager::HEADING ) );
		$this->add_control( 'pop_overlay', array(
			'label'     => esc_html__( 'Dark overlay colour', 'nexvio' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .nxt-modal__ov' => 'background: {{VALUE}};' ),
		) );
		$this->add_control( 'pop_bg', array(
			'label'     => esc_html__( 'Popup background', 'nexvio' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .nxt-modal__dlg' => 'background: {{VALUE}};' ),
		) );
		$this->add_control( 'h_fm', array( 'label' => esc_html__( 'Form', 'nexvio' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_group_control( Group_Control_Background::get_type(), array(
			'name'     => 'form_bg',
			'types'    => array( 'classic', 'gradient' ),
			'selector' => '{{WRAPPER}} .nxt-form__main',
		) );
		$this->add_group_control( Group_Control_Background::get_type(), array(
			'name'     => 'side_bg',
			'label'    => esc_html__( 'Product panel background', 'nexvio' ),
			'types'    => array( 'classic', 'gradient' ),
			'selector' => '{{WRAPPER}} .nxt-form__side',
		) );
		$this->add_control( 'side_color', array(
			'label'     => esc_html__( 'Product panel text', 'nexvio' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .nxt-form__side, {{WRAPPER}} .nxt-form__tips' => 'color: {{VALUE}};', '{{WRAPPER}} .nxt-form__product' => 'color: {{VALUE}};' ),
		) );
		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'typo_ftitle',
			'label'    => esc_html__( 'Form title', 'nexvio' ),
			'selector' => '{{WRAPPER}} .nxt-form__title',
		) );
		$this->add_control( 'ftitle_color', array(
			'label'     => esc_html__( 'Form title colour', 'nexvio' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .nxt-form__title' => 'color: {{VALUE}};' ),
		) );
		$this->add_control( 'input_bg', array(
			'label'     => esc_html__( 'Field background', 'nexvio' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .nxt-field input[type="text"], {{WRAPPER}} .nxt-field input[type="email"], {{WRAPPER}} .nxt-field textarea, {{WRAPPER}} .nxt-rate__row' => 'background: {{VALUE}};' ),
		) );
		$this->add_control( 'input_border', array(
			'label'     => esc_html__( 'Field border', 'nexvio' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .nxt-field input[type="text"], {{WRAPPER}} .nxt-field input[type="email"], {{WRAPPER}} .nxt-field textarea, {{WRAPPER}} .nxt-rate__row' => 'border-color: {{VALUE}};' ),
		) );
		$this->add_control( 'h_btn', array( 'label' => esc_html__( 'Main buttons (Submit, Write a review)', 'nexvio' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'typo_btn',
			'selector' => '{{WRAPPER}} .nxt-btn',
		) );
		$this->start_controls_tabs( 'btn_tabs' );
		$this->start_controls_tab( 'btn_normal', array( 'label' => esc_html__( 'Normal', 'nexvio' ) ) );
		$this->add_control( 'btn_bg', array(
			'label'     => esc_html__( 'Background', 'nexvio' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .nxt-btn--gold, {{WRAPPER}} .nxt-btn--dark' => 'background: {{VALUE}};' ),
		) );
		$this->add_control( 'btn_color', array(
			'label'     => esc_html__( 'Text', 'nexvio' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .nxt-btn--gold, {{WRAPPER}} .nxt-btn--dark' => 'color: {{VALUE}};' ),
		) );
		$this->end_controls_tab();
		$this->start_controls_tab( 'btn_hover', array( 'label' => esc_html__( 'Hover', 'nexvio' ) ) );
		$this->add_control( 'btn_hbg', array(
			'label'     => esc_html__( 'Background', 'nexvio' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .nxt-btn--gold:hover, {{WRAPPER}} .nxt-btn--dark:hover' => 'background: {{VALUE}};' ),
		) );
		$this->add_control( 'btn_hcolor', array(
			'label'     => esc_html__( 'Text', 'nexvio' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .nxt-btn--gold:hover, {{WRAPPER}} .nxt-btn--dark:hover' => 'color: {{VALUE}};' ),
		) );
		$this->end_controls_tab();
		$this->end_controls_tabs();
		$this->add_control( 'btn_radius', array(
			'label'     => esc_html__( 'Button corner radius', 'nexvio' ),
			'type'      => Controls_Manager::SLIDER,
			'range'     => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
			'selectors' => array( '{{WRAPPER}} .nxt-btn' => 'border-radius: {{SIZE}}px;' ),
		) );
		$this->end_controls_section();
	}

	/* ------------------------------------------------------------------
	 * Render
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
				$latest = get_posts( array( 'post_type' => 'product', 'post_status' => 'publish', 'numberposts' => 1, 'fields' => 'ids' ) );
				$id     = $latest ? (int) $latest[0] : 0;
			}
		}
		return $id ? wc_get_product( $id ) : null;
	}

	/** Never cache: reviews and the logged-in visitor change the output. */
	protected function is_dynamic_content(): bool {
		return true;
	}

	private function svg_star() {
		return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 2.2 2.9 6.1 6.6.9-4.8 4.6 1.2 6.6L12 17.2l-5.9 3.2 1.2-6.6L2.5 9.2l6.6-.9z"/></svg>';
	}

	protected function render() {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return;
		}
		$s       = $this->get_settings_for_display();
		$product = $this->current_product( $s );
		if ( ! $product ) {
			if ( current_user_can( 'edit_posts' ) ) {
				echo '<p class="nxt-note">' . esc_html__( 'Product Tabs: no product found. Place this widget in a single product template.', 'nexvio' ) . '</p>';
			}
			return;
		}

		$pid   = $product->get_id();
		$uid   = 'nxt-' . $this->get_id();
		$stats = Nexvio_Reviews::stats( $pid );
		$tab   = in_array( $s['default_tab'], array( 'desc', 'rev', 'ship', 'add' ), true ) ? $s['default_tab'] : 'desc';
		$ship  = 'yes' === $s['show_ship'] && '' !== trim( wp_strip_all_tags( (string) $s['ship_content'] ) );
		if ( 'ship' === $tab && ! $ship ) {
			$tab = 'desc';
		}

		$t = array(
			'write'       => $s['txt_write'],
			'sort'        => $s['txt_sort'],
			'load_more'   => $s['txt_more'],
			'no_match'    => $s['txt_nomatch'],
			'empty_title' => $s['txt_empty_title'],
			'empty_text'  => $s['txt_empty_text'],
		);
		$panel_opts = array(
			'per_page'  => (int) $s['per_page'],
			'show_dist' => $s['show_dist'],
			'show_sort' => $s['show_sort'],
			'verified'  => 'yes' === $s['show_verified'] ? $s['txt_verified'] : '',
			'helpful'   => 'yes' === $s['show_helpful'] ? 'yes' : 'no',
			'edit'      => 'yes' === $s['allow_edit'] ? 'yes' : 'no',
			'edit_label' => '' !== trim( (string) $s['txt_edit'] ) ? $s['txt_edit'] : __( 'Edit', 'nexvio' ),
			'reply'     => 'yes' === $s['allow_reply'] ? 'yes' : 'no',
			'reply_label' => '' !== trim( (string) $s['txt_reply'] ) ? $s['txt_reply'] : __( 'Reply', 'nexvio' ),
			't'         => $t,
		);

		$popup = 'popup' === $s['form_mode'];
		if ( $popup && 'add' === $tab ) {
			$tab = 'desc';
		}
		$design = in_array( $s['tab_design'], array( 'folder', 'underline', 'pills', 'boxed' ), true ) ? $s['tab_design'] : 'folder';
		$revd   = 'lines' === $s['rev_design'] ? 'lines' : 'cards';
		$cols   = '2' === (string) $s['rev_cols'] && 'cards' === $revd ? '2' : '1';

		$tabs = array(
			'desc' => $s['lbl_desc'],
			'rev'  => $s['lbl_rev'],
		);
		if ( $ship ) {
			$tabs['ship'] = $s['lbl_ship'];
		}
		if ( ! $popup ) {
			$tabs['add'] = $s['lbl_add'];
		}
		$icons = array(
			'desc' => '<svg class="nxt__ico" viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 8 4.2v9.6L12 21l-8-4.2V7.2L12 3Zm0 9 8-4.8M12 12v9M12 12 4 7.2" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>',
			'rev'  => '<svg class="nxt__ico" viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 2.7 5.6 6.1.8-4.5 4.3 1.1 6.1L12 16.8 6.6 19.8l1.1-6.1L3.2 9.4l6.1-.8L12 3Z" fill="currentColor"/></svg>',
			'ship' => '<svg class="nxt__ico" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="M9.6 9.4a2.5 2.5 0 1 1 3.6 2.2c-.8.4-1.2.9-1.2 1.7M12 16.6h.01" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
			'add'  => '<svg class="nxt__ico" viewBox="0 0 24 24" aria-hidden="true"><path d="m4 20 1-4L16.5 4.5a2.1 2.1 0 0 1 3 3L8 19l-4 1Z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>',
		);
		$cfg = array(
			'ajax'     => admin_url( 'admin-ajax.php' ),
			'product'  => $pid,
			'verified' => $panel_opts['verified'],
			'sending'  => $s['f_sending'],
			'popup'    => $popup,
			'edit'     => $panel_opts['edit'],
			'editLbl'  => $panel_opts['edit_label'],
			'reply'    => $panel_opts['reply'],
			'replyLbl' => $panel_opts['reply_label'],
			'helpful'  => $panel_opts['helpful'],
			'loggedIn' => is_user_logged_in(),
			'rt'       => array(
				'placeholder' => __( 'Write your reply…', 'nexvio' ),
				'name'        => __( 'Your name', 'nexvio' ),
				'email'       => __( 'Your email (not published)', 'nexvio' ),
				'send'        => __( 'Send reply', 'nexvio' ),
				'sending'     => __( 'Sending…', 'nexvio' ),
				'cancel'      => __( 'Cancel', 'nexvio' ),
				'save'        => __( 'Save changes', 'nexvio' ),
				'saving'      => __( 'Saving…', 'nexvio' ),
				'rate'        => __( 'Your rating', 'nexvio' ),
			),
			'words'    => array_map( 'trim', explode( ',', (string) $s['f_rating_words'] ) ),
			'tpl'      => array(
				/* translators: 1: shown reviews, 2: total reviews */
				'showing'  => __( 'Showing %1$s of %2$s reviews', 'nexvio' ),
				/* translators: %s: number of stars */
				'filter'   => __( '%s-star reviews', 'nexvio' ),
				'clear'    => __( 'Clear filter', 'nexvio' ),
				'rate'     => __( 'Please choose a star rating.', 'nexvio' ),
				'error'    => __( 'Something went wrong, please try again.', 'nexvio' ),
				'required' => __( 'Please complete the highlighted fields.', 'nexvio' ),
			),
		);
		$dalign = in_array( $s['desc_align'], array( 'left', 'center', 'right' ), true ) ? $s['desc_align'] : 'left';
		$root_class = 'nxt nxt--da-' . $dalign . ' nxt--' . $design . ' nxt--' . $revd . ' nxt--cols' . $cols . ( $popup ? ' is-popup' : '' ) . ( 'yes' === $s['tab_icons'] ? ' has-icons' : '' ) . ( 'yes' === $s['sticky_pin'] ? ' nxt--sticky' : '' );
		?>
		<div class="<?php echo esc_attr( $root_class ); ?>" id="<?php echo esc_attr( $uid ); ?>" data-nxt="<?php echo esc_attr( wp_json_encode( $cfg ) ); ?>" data-start="<?php echo esc_attr( $tab ); ?>">
			<div class="nxt__tabs">
				<div class="nxt__bar" role="tablist" aria-label="<?php esc_attr_e( 'Product information', 'nexvio' ); ?>">
					<?php foreach ( $tabs as $key => $label ) : ?>
						<?php if ( 'add' === $key && $popup ) : ?>
							<button type="button" class="nxt__tab nxt__tab--action" data-nxt-goto="add" aria-haspopup="dialog">
								<svg viewBox="0 0 20 20" aria-hidden="true"><path d="M10 4v12M4 10h12" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
								<span><?php echo esc_html( $label ); ?></span>
							</button>
							<?php continue; ?>
						<?php endif; ?>
						<button type="button" class="nxt__tab" role="tab" id="<?php echo esc_attr( $uid . '-t-' . $key ); ?>" aria-controls="<?php echo esc_attr( $uid . '-p-' . $key ); ?>" aria-selected="<?php echo $key === $tab ? 'true' : 'false'; ?>" tabindex="<?php echo $key === $tab ? '0' : '-1'; ?>" data-tab="<?php echo esc_attr( $key ); ?>">
							<?php echo $icons[ $key ]; // phpcs:ignore WordPress.Security.EscapeOutput -- static markup. ?>
							<span><?php echo esc_html( $label ); ?></span>
							<?php if ( 'rev' === $key && 'yes' === $s['show_count'] ) : ?>
								<em class="nxt__count"><?php echo esc_html( number_format_i18n( $stats['total'] ) ); ?></em>
							<?php endif; ?>
						</button>
					<?php endforeach; ?>
					<i class="nxt__ink" aria-hidden="true"></i>
				</div>
			</div>

			<div class="nxt__panel" role="tabpanel" id="<?php echo esc_attr( $uid . '-p-desc' ); ?>" aria-labelledby="<?php echo esc_attr( $uid . '-t-desc' ); ?>" data-panel="desc" <?php echo 'desc' === $tab ? '' : 'hidden'; ?>>
				<?php $this->render_description( $product ); ?>
			</div>

			<div class="nxt__panel" role="tabpanel" id="<?php echo esc_attr( $uid . '-p-rev' ); ?>" aria-labelledby="<?php echo esc_attr( $uid . '-t-rev' ); ?>" data-panel="rev" <?php echo 'rev' === $tab ? '' : 'hidden'; ?>>
				<?php echo Nexvio_Reviews::render_panel( $product, $panel_opts ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside. ?>
			</div>

			<?php if ( $ship ) : ?>
				<div class="nxt__panel" role="tabpanel" id="<?php echo esc_attr( $uid . '-p-ship' ); ?>" aria-labelledby="<?php echo esc_attr( $uid . '-t-ship' ); ?>" data-panel="ship" <?php echo 'ship' === $tab ? '' : 'hidden'; ?>>
					<div class="nxt__desc nxt__ship"><?php echo wp_kses_post( wpautop( (string) $s['ship_content'] ) ); ?></div>
				</div>
			<?php endif; ?>

			<?php if ( $popup ) : ?>
				<div class="nxt-modal" data-modal hidden>
					<div class="nxt-modal__ov" data-nxt-close></div>
					<div class="nxt-modal__dlg" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr( $s['f_title'] ); ?>" tabindex="-1">
						<button type="button" class="nxt-modal__x" data-nxt-close aria-label="<?php esc_attr_e( 'Close', 'nexvio' ); ?>"><svg viewBox="0 0 20 20" aria-hidden="true"><path d="m5 5 10 10M15 5 5 15" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></button>
						<?php $this->render_form( $product, $s, $uid ); ?>
					</div>
				</div>
			<?php else : ?>
				<div class="nxt__panel" role="tabpanel" id="<?php echo esc_attr( $uid . '-p-add' ); ?>" aria-labelledby="<?php echo esc_attr( $uid . '-t-add' ); ?>" data-panel="add" <?php echo 'add' === $tab ? '' : 'hidden'; ?>>
					<?php $this->render_form( $product, $s, $uid ); ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	private function render_description( $product ) {
		$content = $product->get_description();
		if ( '' === trim( wp_strip_all_tags( $content ) ) ) {
			$content = $product->get_short_description();
		}
		echo '<div class="nxt__desc">';
		if ( '' === trim( wp_strip_all_tags( $content ) ) ) {
			echo '<p class="nxt-muted">' . esc_html__( 'No description has been added for this product yet.', 'nexvio' ) . '</p>';
		} else {
			echo wp_kses_post( apply_filters( 'the_content', $content ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core WordPress filter.
		}
		echo '</div>';
	}

	private function render_form( $product, $s, $uid ) {
		$pid  = $product->get_id();
		$why  = Nexvio_Reviews::cannot_review( $product );
		$user = wp_get_current_user();
		$logged = $user->ID > 0;
		$required = ! function_exists( 'wc_review_ratings_required' ) || wc_review_ratings_required();
		$consent  = ! $logged && has_action( 'set_comment_cookies' ) && get_option( 'show_comments_cookies_opt_in' );
		$thumb    = $product->get_image_id() ? wp_get_attachment_image( $product->get_image_id(), 'woocommerce_thumbnail', false, array( 'class' => 'nxt-form__img', 'loading' => 'lazy' ) ) : '';
		$words    = array_map( 'trim', explode( ',', (string) $s['f_rating_words'] ) );
		$side     = 'yes' === $s['f_side'];
		$stats    = Nexvio_Reviews::stats( $pid );
		?>
		<div class="nxt-form <?php echo $side ? 'has-side' : ''; ?>">
			<?php if ( $side ) : ?>
				<aside class="nxt-form__side">
					<?php if ( $thumb ) : ?>
						<div class="nxt-form__thumb"><?php echo $thumb; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
					<?php endif; ?>
					<h4 class="nxt-form__product"><?php echo esc_html( $product->get_name() ); ?></h4>
					<?php if ( $stats['rated'] ) : ?>
						<div class="nxt-form__avg">
							<?php echo Nexvio_Reviews::stars( $stats['average'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<span>
								<?php
								echo esc_html( number_format_i18n( $stats['average'], 1 ) ) . ' · ';
								/* translators: %s: number of reviews */
								echo esc_html( sprintf( _n( '%s review', '%s reviews', $stats['total'], 'nexvio' ), number_format_i18n( $stats['total'] ) ) );
								?>
							</span>
						</div>
					<?php endif; ?>
					<ul class="nxt-form__tips">
						<li><?php esc_html_e( 'Describe quality, fit and finish', 'nexvio' ); ?></li>
						<li><?php esc_html_e( 'Tell how you use it every day', 'nexvio' ); ?></li>
						<li><?php esc_html_e( 'Keep it honest and respectful', 'nexvio' ); ?></li>
					</ul>
				</aside>
			<?php endif; ?>

			<div class="nxt-form__main">
				<h3 class="nxt-form__title"><?php echo esc_html( $s['f_title'] ); ?></h3>
				<?php if ( '' !== $s['f_intro'] ) : ?>
					<p class="nxt-form__intro"><?php echo esc_html( $s['f_intro'] ); ?></p>
				<?php endif; ?>

				<?php if ( '' !== $why ) : ?>
					<div class="nxt-lock">
						<p><?php echo esc_html( $why ); ?></p>
						<?php if ( ! $logged ) : ?>
							<a class="nxt-btn nxt-btn--dark" href="<?php echo esc_url( wp_login_url( get_permalink( $pid ) ) ); ?>"><?php esc_html_e( 'Log in', 'nexvio' ); ?></a>
						<?php endif; ?>
					</div>
				<?php else : ?>
					<form class="nxt-f" method="post" novalidate data-required="<?php echo $required ? '1' : '0'; ?>">
						<input type="hidden" name="action" value="nexvio_review_submit">
						<input type="hidden" name="comment_post_ID" value="<?php echo esc_attr( $pid ); ?>">
						<div class="nxt-hp" aria-hidden="true"><label>Website <input type="text" name="nexvio_hp" tabindex="-1" autocomplete="off"></label></div>

						<fieldset class="nxt-field nxt-rate" data-field="rating">
							<legend><?php echo esc_html( $s['f_rating'] ); ?><?php echo $required ? ' <b aria-hidden="true">*</b>' : ''; ?></legend>
							<div class="nxt-rate__row">
								<div class="nxt-stars-pick" role="radiogroup">
									<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
										<label class="nxt-pick" data-v="<?php echo esc_attr( $i ); ?>" title="<?php echo esc_attr( isset( $words[ $i - 1 ] ) ? $words[ $i - 1 ] : $i ); ?>">
											<input type="radio" name="rating" value="<?php echo esc_attr( $i ); ?>">
											<?php echo $this->svg_star(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
											<span class="screen-reader-text"><?php echo esc_html( sprintf( /* translators: %d: stars */ _n( '%d star', '%d stars', $i, 'nexvio' ), $i ) ); ?></span>
										</label>
									<?php endfor; ?>
								</div>
								<span class="nxt-rate__word" aria-live="polite" data-hint="<?php esc_attr_e( 'Tap a star to rate', 'nexvio' ); ?>"></span>
							</div>
							<p class="nxt-err" role="alert"></p>
						</fieldset>

						<div class="nxt-field" data-field="comment">
							<label for="<?php echo esc_attr( $uid ); ?>-c"><?php echo esc_html( $s['f_review'] ); ?> <b aria-hidden="true">*</b></label>
							<textarea id="<?php echo esc_attr( $uid ); ?>-c" name="comment" rows="5" maxlength="2000" required placeholder="<?php echo esc_attr( $s['f_review_ph'] ); ?>"></textarea>
							<div class="nxt-field__foot"><p class="nxt-err" role="alert"></p><span class="nxt-count"><span data-count>0</span> / 2000</span></div>
						</div>

						<?php if ( $logged ) : ?>
							<p class="nxt-as">
								<?php
								/* translators: %s: user display name */
								echo wp_kses( sprintf( __( 'Reviewing as %s', 'nexvio' ), '<strong>' . esc_html( $user->display_name ) . '</strong>' ), array( 'strong' => array() ) );
								?>
							</p>
						<?php else : ?>
							<div class="nxt-grid2">
								<div class="nxt-field" data-field="author">
									<label for="<?php echo esc_attr( $uid ); ?>-a"><?php echo esc_html( $s['f_name'] ); ?> <b aria-hidden="true">*</b></label>
									<input id="<?php echo esc_attr( $uid ); ?>-a" type="text" name="author" autocomplete="name" maxlength="245" required>
									<p class="nxt-err" role="alert"></p>
								</div>
								<div class="nxt-field" data-field="email">
									<label for="<?php echo esc_attr( $uid ); ?>-e"><?php echo esc_html( $s['f_email'] ); ?> <b aria-hidden="true">*</b></label>
									<input id="<?php echo esc_attr( $uid ); ?>-e" type="email" name="email" autocomplete="email" maxlength="100" required>
									<p class="nxt-err" role="alert"></p>
								</div>
							</div>
							<?php if ( $consent ) : ?>
								<label class="nxt-check">
									<input type="checkbox" name="wp-comment-cookies-consent" value="yes">
									<span class="nxt-check__box" aria-hidden="true"></span>
									<span><?php echo esc_html( $s['f_consent'] ); ?></span>
								</label>
							<?php endif; ?>
						<?php endif; ?>

						<p class="nxt-msg" role="alert" hidden></p>
						<button type="submit" class="nxt-btn nxt-btn--gold nxt-btn--lg" data-label="<?php echo esc_attr( $s['f_submit'] ); ?>">
							<span class="nxt-btn__t"><?php echo esc_html( $s['f_submit'] ); ?></span>
							<i class="nxt-spin" aria-hidden="true"></i>
						</button>
						<p class="nxt-secure"><svg viewBox="0 0 20 20" aria-hidden="true"><path d="M10 1.8 3.5 4.4v4.8c0 4 2.7 7.4 6.5 8.9 3.8-1.5 6.5-4.9 6.5-8.9V4.4z" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg><?php esc_html_e( 'Your email is never published.', 'nexvio' ); ?></p>
					</form>

					<div class="nxt-done" hidden>
						<span class="nxt-done__ring" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m5 12.5 4.2 4.2L19 7" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
						<h3><?php esc_html_e( 'Thank you!', 'nexvio' ); ?></h3>
						<p class="nxt-done__text"></p>
						<button type="button" class="nxt-btn nxt-btn--dark" data-nxt-goto="rev"><?php echo esc_html( $s['f_view'] ); ?></button>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}
