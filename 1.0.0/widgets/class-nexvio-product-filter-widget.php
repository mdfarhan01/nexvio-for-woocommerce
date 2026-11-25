<?php
/**
 * NEXVIO Product Filter - Elementor widget.
 *
 * Search by title, price range, category (radio, with counts and "see more"),
 * plus any number of extra filters (attributes, tags, any product taxonomy,
 * stock, on sale, featured). Works with AJAX (no page reload) by reloading the
 * product grid of the page, and keeps the URL shareable.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;
use Elementor\Group_Control_Typography;

class Nexvio_Product_Filter_Widget extends Widget_Base {

	use Nexvio_Card_Trait;

	public function get_name() {
		return 'nexvio_product_filter';
	}

	public function get_title() {
		return esc_html__( 'Product Filter', 'nexvio' );
	}

	public function get_icon() {
		return 'eicon-filter';
	}

	public function get_categories() {
		return array( 'nexvio' );
	}

	public function get_keywords() {
		return array( 'nexvio', 'filter', 'filtre', 'price', 'category', 'search', 'ajax', 'woocommerce' );
	}

	public function get_style_depends() {
		return array( 'nexvio-product-filter' );
	}

	public function get_script_depends() {
		return array( 'nexvio-product-filter' );
	}

	/* ------------------------------------------------------------------
	 * Control helpers (CSS variables on .nexvio-pf)
	 * ------------------------------------------------------------------ */

	/** Colours start empty: Nexvio > Filters (Style + main colour) applies until a colour is set here. */
	private function cv( $id, $label, $var, $default, $extra = array() ) {
		unset( $default );
		$this->add_control( $id, array_merge( array(
			'label'     => $label,
			'type'      => Controls_Manager::COLOR,
			'default'   => '',
			'selectors' => array( '{{WRAPPER}} .nexvio-pf' => $var . ': {{VALUE}};' ),
		), $extra ) );
	}

	private function nv( $id, $label, $var, $default, $min, $max, $step = 1, $unit = 'px', $extra = array() ) {
		$this->add_control( $id, array_merge( array(
			'label'     => $label,
			'type'      => Controls_Manager::NUMBER,
			'default'   => $default,
			'min'       => $min,
			'max'       => $max,
			'step'      => $step,
			'selectors' => array( '{{WRAPPER}} .nexvio-pf' => $var . ': {{VALUE}}' . $unit . ';' ),
		), $extra ) );
	}

	private function attribute_options() {
		$out = array();
		if ( function_exists( 'wc_get_attribute_taxonomies' ) ) {
			foreach ( wc_get_attribute_taxonomies() as $a ) {
				$out[ 'pa_' . $a->attribute_name ] = $a->attribute_label;
			}
		}
		return $out ? $out : array( '' => esc_html__( 'No attributes yet (Products > Attributes)', 'nexvio' ) );
	}

	/* ------------------------------------------------------------------
	 * Controls
	 * ------------------------------------------------------------------ */

	protected function register_controls() {

		/* ============ GENERAL ============ */
		$this->start_controls_section( 'section_fields', array( 'label' => esc_html__( 'Fields', 'nexvio' ) ) );
		$this->add_control( 'fields_source', array(
			'label'   => esc_html__( 'Filter fields', 'nexvio' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'global',
			'options' => array(
				'global' => esc_html__( 'From Nexvio > Filters', 'nexvio' ),
				'widget' => esc_html__( 'Set here, for this widget only', 'nexvio' ),
			),
		) );
		$this->add_control( 'fields_note', array(
			'type'            => Controls_Manager::RAW_HTML,
			'raw'             => '<a href="' . esc_url( admin_url( 'admin.php?page=nexvio-filters' ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'Choose the fields on the Filters page', 'nexvio' ) . ' &rarr;</a> ' . esc_html__( 'Search, price, category, colour, size and any other attribute. Colours and styles stay here in the Style tab.', 'nexvio' ),
			'content_classes' => 'elementor-descriptor',
			'condition'       => array( 'fields_source' => 'global' ),
		) );
		$this->end_controls_section();

		$this->start_controls_section( 'section_general', array( 'label' => esc_html__( 'General', 'nexvio' ) ) );
		$this->switcher( 'show_title', esc_html__( 'Show filter title', 'nexvio' ), '' );
		$this->text( 'title', esc_html__( 'Title', 'nexvio' ), 'Filters', array( 'condition' => array( 'show_title' => 'yes' ) ) );
		$this->add_control( 'ajax', array(
			'label'        => esc_html__( 'AJAX (no page reload)', 'nexvio' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
			'separator'    => 'before',
		) );
		$this->add_control( 'apply_mode', array(
			'label'   => esc_html__( 'Apply filters', 'nexvio' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'instant',
			'options' => array(
				'instant' => esc_html__( 'Instantly on every change', 'nexvio' ),
				'button'  => esc_html__( 'With an "Apply" button', 'nexvio' ),
			),
		) );
		$this->text( 'apply_text', esc_html__( '"Apply" button text', 'nexvio' ), 'Apply', array( 'condition' => array( 'apply_mode' => 'button' ) ) );
		$this->switcher( 'show_reset', esc_html__( 'Reset button', 'nexvio' ) );
		$this->text( 'reset_text', esc_html__( 'Reset button text', 'nexvio' ), 'Reset', array( 'condition' => array( 'show_reset' => 'yes' ) ) );
		$this->switcher( 'collapsible', esc_html__( 'Groups can be collapsed', 'nexvio' ), 'yes', array( 'separator' => 'before' ) );
		$this->add_control( 'selector', array(
			'label'       => esc_html__( 'Products container (CSS selector)', 'nexvio' ),
			'type'        => Controls_Manager::TEXT,
			'default'     => '.nexvio-pg:not(.nexvio-pg--grid)',
			'separator'   => 'before',
			'description' => esc_html__( 'The default finds the Products Archive widget. For another grid, use its container selector (e.g. .elementor-widget-wc-archive-products). If not found, the page is loaded normally.', 'nexvio' ),
		) );
		$this->switcher( 'scroll_top', esc_html__( 'Scroll to the products after filtering', 'nexvio' ) );
		$this->end_controls_section();

		/* ============ SEARCH ============ */
		$this->start_controls_section( 'section_search', array( 'label' => esc_html__( 'Search', 'nexvio' ) , 'condition' => array( 'fields_source' => 'widget' )) );
		$this->switcher( 'show_search', esc_html__( 'Show search', 'nexvio' ) );
		$this->text( 'search_title', esc_html__( 'Group title', 'nexvio' ), 'Search', array( 'condition' => array( 'show_search' => 'yes' ) ) );
		$this->text( 'search_ph', esc_html__( 'Placeholder', 'nexvio' ), 'Search products…', array( 'condition' => array( 'show_search' => 'yes' ) ) );
		$this->add_control( 'search_min', array(
			'label'     => esc_html__( 'Search from (characters)', 'nexvio' ),
			'type'      => Controls_Manager::NUMBER,
			'min'       => 1,
			'max'       => 5,
			'default'   => 2,
			'condition' => array( 'show_search' => 'yes' ),
		) );
		$this->add_control( 'search_delay', array(
			'label'     => esc_html__( 'Wait after typing (ms)', 'nexvio' ),
			'type'      => Controls_Manager::NUMBER,
			'min'       => 100,
			'max'       => 2000,
			'step'      => 50,
			'default'   => 450,
			'condition' => array( 'show_search' => 'yes' ),
		) );
		$this->switcher( 'search_open', esc_html__( 'Open by default', 'nexvio' ), 'yes', array( 'condition' => array( 'show_search' => 'yes', 'collapsible' => 'yes' ) ) );
		$this->end_controls_section();

		/* ============ PRICE ============ */
		$this->start_controls_section( 'section_price', array( 'label' => esc_html__( 'Price range', 'nexvio' ) , 'condition' => array( 'fields_source' => 'widget' )) );
		$this->switcher( 'show_price', esc_html__( 'Show price range', 'nexvio' ) );
		$this->text( 'price_title', esc_html__( 'Group title', 'nexvio' ), 'Price', array( 'condition' => array( 'show_price' => 'yes' ) ) );
		$this->add_control( 'price_bounds', array(
			'label'     => esc_html__( 'Lowest / highest value', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'auto',
			'options'   => array(
				'auto'   => esc_html__( 'Automatic (cheapest / most expensive product)', 'nexvio' ),
				'manual' => esc_html__( 'Manual', 'nexvio' ),
			),
			'condition' => array( 'show_price' => 'yes' ),
		) );
		$this->add_control( 'price_min', array( 'label' => esc_html__( 'Lowest', 'nexvio' ), 'type' => Controls_Manager::NUMBER, 'default' => 0, 'condition' => array( 'show_price' => 'yes', 'price_bounds' => 'manual' ) ) );
		$this->add_control( 'price_max', array( 'label' => esc_html__( 'Highest', 'nexvio' ), 'type' => Controls_Manager::NUMBER, 'default' => 500, 'condition' => array( 'show_price' => 'yes', 'price_bounds' => 'manual' ) ) );
		$this->add_control( 'price_step', array( 'label' => esc_html__( 'Step', 'nexvio' ), 'type' => Controls_Manager::NUMBER, 'min' => 0.01, 'default' => 1, 'condition' => array( 'show_price' => 'yes' ) ) );
		$this->switcher( 'price_inputs', esc_html__( 'Show min / max boxes', 'nexvio' ), 'yes', array( 'condition' => array( 'show_price' => 'yes' ) ) );
		$this->switcher( 'price_slider', esc_html__( 'Show slider', 'nexvio' ), 'yes', array( 'condition' => array( 'show_price' => 'yes' ) ) );
		$this->text( 'price_min_label', esc_html__( 'Min label', 'nexvio' ), 'Min', array( 'condition' => array( 'show_price' => 'yes', 'price_inputs' => 'yes' ) ) );
		$this->text( 'price_max_label', esc_html__( 'Max label', 'nexvio' ), 'Max', array( 'condition' => array( 'show_price' => 'yes', 'price_inputs' => 'yes' ) ) );
		$this->switcher( 'price_open', esc_html__( 'Open by default', 'nexvio' ), 'yes', array( 'condition' => array( 'show_price' => 'yes', 'collapsible' => 'yes' ) ) );
		$this->end_controls_section();

		/* ============ CATEGORY ============ */
		$this->start_controls_section( 'section_cat', array( 'label' => esc_html__( 'Category', 'nexvio' ) , 'condition' => array( 'fields_source' => 'widget' )) );
		$this->switcher( 'show_cat', esc_html__( 'Show categories', 'nexvio' ) );
		$this->text( 'cat_title', esc_html__( 'Group title', 'nexvio' ), 'Category', array( 'condition' => array( 'show_cat' => 'yes' ) ) );
		$this->switcher( 'cat_all', esc_html__( '"All" option', 'nexvio' ), 'yes', array( 'condition' => array( 'show_cat' => 'yes' ) ) );
		$this->text( 'cat_all_text', esc_html__( '"All" text', 'nexvio' ), 'All categories', array( 'condition' => array( 'show_cat' => 'yes', 'cat_all' => 'yes' ) ) );
		$this->switcher( 'cat_counts', esc_html__( 'Show number of products', 'nexvio' ), 'yes', array( 'condition' => array( 'show_cat' => 'yes' ) ) );
		$this->switcher( 'cat_hide_empty', esc_html__( 'Hide empty categories', 'nexvio' ), 'yes', array( 'condition' => array( 'show_cat' => 'yes' ) ) );
		$this->add_control( 'cat_levels', array(
			'label'     => esc_html__( 'Sub-categories', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'all',
			'options'   => array(
				'all' => esc_html__( 'Show all, indented', 'nexvio' ),
				'top' => esc_html__( 'Top-level only', 'nexvio' ),
			),
			'condition' => array( 'show_cat' => 'yes' ),
		) );
		$this->add_control( 'cat_orderby', array(
			'label'     => esc_html__( 'Order', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'name',
			'options'   => array(
				'name'       => esc_html__( 'Name', 'nexvio' ),
				'count'      => esc_html__( 'Number of products', 'nexvio' ),
				'menu_order' => esc_html__( 'Custom order (drag & drop in Products > Categories)', 'nexvio' ),
			),
			'condition' => array( 'show_cat' => 'yes' ),
		) );
		$this->add_control( 'cat_exclude', array(
			'label'       => esc_html__( 'Hide these categories', 'nexvio' ),
			'type'        => Controls_Manager::SELECT2,
			'multiple'    => true,
			'options'     => $this->term_options( 'product_cat' ),
			'label_block' => true,
			'condition'   => array( 'show_cat' => 'yes' ),
		) );
		$this->add_control( 'cat_limit', array(
			'label'       => esc_html__( 'Visible before "See more"', 'nexvio' ),
			'type'        => Controls_Manager::NUMBER,
			'min'         => 1,
			'max'         => 100,
			'default'     => 10,
			'separator'   => 'before',
			'condition'   => array( 'show_cat' => 'yes' ),
		) );
		$this->add_control( 'cat_display', array(
			'label'     => esc_html__( 'Look', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'radio',
			'options'   => array(
				'radio'    => esc_html__( 'List (one category)', 'nexvio' ),
				'checkbox' => esc_html__( 'Checkboxes (several)', 'nexvio' ),
				'select'   => esc_html__( 'Dropdown', 'nexvio' ),
				'chips'    => esc_html__( 'Buttons (several)', 'nexvio' ),
			),
			'condition' => array( 'show_cat' => 'yes' ),
		) );
		$this->switcher( 'cat_collapse', esc_html__( 'Sub-categories hidden until + is clicked', 'nexvio' ), 'yes', array( 'condition' => array( 'show_cat' => 'yes', 'cat_levels' => 'all' ) ) );
		$this->text( 'more_text', esc_html__( '"See more" text ({n} = hidden)', 'nexvio' ), 'Show more ({n})', array( 'condition' => array( 'show_cat' => 'yes' ) ) );
		$this->text( 'less_text', esc_html__( '"See less" text', 'nexvio' ), 'Show less', array( 'condition' => array( 'show_cat' => 'yes' ) ) );
		$this->add_control( 'list_height', array(
			'label'       => esc_html__( 'Height of the open list (px, then it scrolls)', 'nexvio' ),
			'type'        => Controls_Manager::NUMBER,
			'min'         => 120,
			'max'         => 1200,
			'default'     => 320,
			'selectors'   => array( '{{WRAPPER}} .nexvio-pf' => '--nexvio-pf-list-h: {{VALUE}}px;' ),
			'condition'   => array( 'show_cat' => 'yes' ),
		) );
		$this->switcher( 'cat_open', esc_html__( 'Open by default', 'nexvio' ), 'yes', array( 'condition' => array( 'show_cat' => 'yes', 'collapsible' => 'yes' ) ) );
		$this->end_controls_section();

		/* ============ EXTRA FILTERS ============ */
		$this->start_controls_section( 'section_extra', array( 'label' => esc_html__( 'More filters', 'nexvio' ) , 'condition' => array( 'fields_source' => 'widget' )) );
		$this->add_control( 'extra_note', array(
			'type'            => Controls_Manager::RAW_HTML,
			'raw'             => esc_html__( 'Add any filter: product attributes (language, condition, set…), tags, any product taxonomy, stock, on sale, featured.', 'nexvio' ),
			'content_classes' => 'elementor-descriptor',
		) );
		$r = new Repeater();
		$r->add_control( 'f_title', array( 'label' => esc_html__( 'Title', 'nexvio' ), 'type' => Controls_Manager::TEXT, 'default' => 'Language' ) );
		$r->add_control( 'f_source', array(
			'label'   => esc_html__( 'Filter by', 'nexvio' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'attribute',
			'options' => array(
				'attribute' => esc_html__( 'Product attribute', 'nexvio' ),
				'tag'       => esc_html__( 'Product tag', 'nexvio' ),
				'taxonomy'  => esc_html__( 'Other taxonomy (slug)', 'nexvio' ),
				'stock'     => esc_html__( 'Stock status', 'nexvio' ),
				'sale'      => esc_html__( 'On sale (on / off)', 'nexvio' ),
				'featured'  => esc_html__( 'Featured (on / off)', 'nexvio' ),
				'rating'    => esc_html__( 'Rating (4 stars & up…)', 'nexvio' ),
				'sort'      => esc_html__( 'Sort by', 'nexvio' ),
				'perpage'   => esc_html__( 'Products per page', 'nexvio' ),
			),
		) );
		$r->add_control( 'f_attr', array(
			'label'     => esc_html__( 'Attribute', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'options'   => $this->attribute_options(),
			'default'   => '',
			'condition' => array( 'f_source' => 'attribute' ),
		) );
		$r->add_control( 'f_tax', array(
			'label'       => esc_html__( 'Taxonomy slug', 'nexvio' ),
			'type'        => Controls_Manager::TEXT,
			'placeholder' => 'product_brand',
			'condition'   => array( 'f_source' => 'taxonomy' ),
		) );
		$r->add_control( 'f_display', array(
			'label'     => esc_html__( 'Display', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'checkbox',
			'options'   => array(
				'checkbox' => esc_html__( 'Checkboxes (several)', 'nexvio' ),
				'radio'    => esc_html__( 'Radio (one)', 'nexvio' ),
				'chips'    => esc_html__( 'Buttons / chips (several)', 'nexvio' ),
				'select'   => esc_html__( 'Dropdown (one)', 'nexvio' ),
				'color'    => esc_html__( 'Colour circles', 'nexvio' ),
				'image'    => esc_html__( 'Image circles', 'nexvio' ),
				'size'     => esc_html__( 'Size buttons', 'nexvio' ),
				'auto'     => esc_html__( 'Automatic (colours / sizes)', 'nexvio' ),
			),
			'condition' => array( 'f_source' => array( 'attribute', 'tag', 'taxonomy', 'stock' ) ),
		) );
		$r->add_control( 'f_logic', array(
			'label'     => esc_html__( 'Products must match', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'any',
			'options'   => array( 'any' => esc_html__( 'Any selected value', 'nexvio' ), 'all' => esc_html__( 'All selected values', 'nexvio' ) ),
			'condition' => array( 'f_source' => array( 'attribute', 'tag', 'taxonomy' ), 'f_display' => array( 'checkbox', 'chips' ) ),
		) );
		$r->add_control( 'f_all_text', array(
			'label'     => esc_html__( '"All" text', 'nexvio' ),
			'type'      => Controls_Manager::TEXT,
			'default'   => 'All',
			'condition' => array( 'f_source' => array( 'attribute', 'tag', 'taxonomy', 'stock' ), 'f_display' => array( 'radio', 'select' ) ),
		) );
		$r->add_control( 'f_toggle_text', array(
			'label'     => esc_html__( 'Checkbox text', 'nexvio' ),
			'type'      => Controls_Manager::TEXT,
			'default'   => 'On sale',
			'condition' => array( 'f_source' => array( 'sale', 'featured' ) ),
		) );
		$r->add_control( 'f_in', array( 'label' => esc_html__( '"In stock" text', 'nexvio' ), 'type' => Controls_Manager::TEXT, 'default' => 'In stock', 'condition' => array( 'f_source' => 'stock' ) ) );
		$r->add_control( 'f_back', array( 'label' => esc_html__( '"Pre-order" text', 'nexvio' ), 'type' => Controls_Manager::TEXT, 'default' => 'Pre-order', 'condition' => array( 'f_source' => 'stock' ) ) );
		$r->add_control( 'f_out', array( 'label' => esc_html__( '"Sold out" text (empty = hide)', 'nexvio' ), 'type' => Controls_Manager::TEXT, 'default' => '', 'condition' => array( 'f_source' => 'stock' ) ) );
		$r->add_control( 'f_counts', array(
			'label'        => esc_html__( 'Show number of products', 'nexvio' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
			'condition'    => array( 'f_source' => array( 'attribute', 'tag', 'taxonomy' ) ),
		) );
		$r->add_control( 'f_hide_empty', array(
			'label'        => esc_html__( 'Hide values without products', 'nexvio' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
			'condition'    => array( 'f_source' => array( 'attribute', 'tag', 'taxonomy' ) ),
		) );
		$r->add_control( 'f_limit', array(
			'label'     => esc_html__( 'Visible before "See more"', 'nexvio' ),
			'type'      => Controls_Manager::NUMBER,
			'min'       => 1,
			'max'       => 100,
			'default'   => 10,
			'condition' => array( 'f_source' => array( 'attribute', 'tag', 'taxonomy' ), 'f_display!' => 'select' ),
		) );
		$r->add_control( 'f_open', array(
			'label'        => esc_html__( 'Open by default', 'nexvio' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		) );
		$this->add_control( 'extra_filters', array(
			'label'       => esc_html__( 'Filters', 'nexvio' ),
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $r->get_controls(),
			'title_field' => '{{{ f_title }}}',
			'default'     => array(),
		) );
		$this->end_controls_section();

		/* ============ STYLE: PANEL ============ */
		$this->start_controls_section( 'style_panel', array( 'label' => esc_html__( 'Panel', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_control( 'style_note', array(
			'type'            => Controls_Manager::RAW_HTML,
			'raw'             => esc_html__( 'Light / Dark and the main colour come from Nexvio > Filters. A colour set here replaces it for this widget only.', 'nexvio' ),
			'content_classes' => 'elementor-descriptor',
		) );
		$this->cv( 'p_bg', esc_html__( 'Background', 'nexvio' ), '--nexvio-pf-bg', '#1f252e' );
		$this->cv( 'p_border', esc_html__( 'Border', 'nexvio' ), '--nexvio-pf-border', '#313945' );
		$this->nv( 'p_radius', esc_html__( 'Corner radius (px)', 'nexvio' ), '--nexvio-pf-radius', 8, 0, 40 );
		$this->nv( 'p_pad', esc_html__( 'Padding (px)', 'nexvio' ), '--nexvio-pf-pad', 22, 0, 60 );
		$this->nv( 'p_gap', esc_html__( 'Space between groups (px)', 'nexvio' ), '--nexvio-pf-gap', 22, 0, 60 );
		$this->cv( 'p_divider', esc_html__( 'Divider between groups', 'nexvio' ), '--nexvio-pf-divider', '#313945' );
		$this->cv( 'p_title_c', esc_html__( 'Filter title colour', 'nexvio' ), '--nexvio-pf-title', '#e8edf4', array( 'separator' => 'before' ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'p_title_typo', 'label' => esc_html__( 'Filter title typography', 'nexvio' ), 'selector' => '{{WRAPPER}} .nexvio-pf__title' ) );
		$this->cv( 'g_title_c', esc_html__( 'Group title colour', 'nexvio' ), '--nexvio-pf-gtitle', '#c7d0dc', array( 'separator' => 'before' ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'g_title_typo', 'label' => esc_html__( 'Group title typography', 'nexvio' ), 'selector' => '{{WRAPPER}} .nexvio-pf__gtitle' ) );
		$this->end_controls_section();

		/* ============ STYLE: FIELDS ============ */
		$this->start_controls_section( 'style_fields', array( 'label' => esc_html__( 'Search and price boxes', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->cv( 'i_bg', esc_html__( 'Background', 'nexvio' ), '--nexvio-pf-ibg', '#181d24' );
		$this->cv( 'i_border', esc_html__( 'Border', 'nexvio' ), '--nexvio-pf-iborder', '#313945' );
		$this->cv( 'i_focus', esc_html__( 'Border when focused', 'nexvio' ), '--nexvio-pf-focus', '#3d8bff' );
		$this->cv( 'i_text', esc_html__( 'Text', 'nexvio' ), '--nexvio-pf-itext', '#e8edf4' );
		$this->cv( 'i_ph', esc_html__( 'Placeholder / labels', 'nexvio' ), '--nexvio-pf-muted', '#8f9aab' );
		$this->nv( 'i_h', esc_html__( 'Height (px)', 'nexvio' ), '--nexvio-pf-ih', 44, 30, 70 );
		$this->nv( 'i_radius', esc_html__( 'Corner radius (px)', 'nexvio' ), '--nexvio-pf-iradius', 4, 0, 30 );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'i_typo', 'selector' => '{{WRAPPER}} .nexvio-pf input[type=search], {{WRAPPER}} .nexvio-pf input[type=number], {{WRAPPER}} .nexvio-pf select' ) );
		$this->add_control( 'h_slider', array( 'label' => esc_html__( 'Price slider', 'nexvio' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->cv( 's_track', esc_html__( 'Track', 'nexvio' ), '--nexvio-pf-track', '#313945' );
		$this->cv( 's_fill', esc_html__( 'Selected range', 'nexvio' ), '--nexvio-pf-fill', '#3d8bff' );
		$this->cv( 's_handle', esc_html__( 'Handles', 'nexvio' ), '--nexvio-pf-handle', '#ffffff' );
		$this->cv( 's_handle_b', esc_html__( 'Handle border', 'nexvio' ), '--nexvio-pf-handle-b', '#3d8bff' );
		$this->nv( 's_handle_s', esc_html__( 'Handle size (px)', 'nexvio' ), '--nexvio-pf-handle-s', 18, 10, 32 );
		$this->end_controls_section();

		/* ============ STYLE: OPTIONS ============ */
		$this->start_controls_section( 'style_options', array( 'label' => esc_html__( 'Category and filter options', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->cv( 'o_text', esc_html__( 'Text', 'nexvio' ), '--nexvio-pf-otext', '#c7d0dc' );
		$this->cv( 'o_text_h', esc_html__( 'Text on hover / selected', 'nexvio' ), '--nexvio-pf-otext-a', '#ffffff' );
		$this->cv( 'o_count', esc_html__( 'Count', 'nexvio' ), '--nexvio-pf-count', '#8f9aab' );
		$this->cv( 'o_count_bg', esc_html__( 'Count background', 'nexvio' ), '--nexvio-pf-count-bg', '#262d37' );
		$this->cv( 'o_box', esc_html__( 'Radio / checkbox border', 'nexvio' ), '--nexvio-pf-box', '#56606e' );
		$this->cv( 'o_check', esc_html__( 'Radio / checkbox selected', 'nexvio' ), '--nexvio-pf-check', '#3d8bff' );
		$this->nv( 'o_gap', esc_html__( 'Space between options (px)', 'nexvio' ), '--nexvio-pf-ogap', 4, 0, 20 );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'o_typo', 'selector' => '{{WRAPPER}} .nexvio-pf__name' ) );
		$this->add_control( 'h_chips', array( 'label' => esc_html__( 'Chips', 'nexvio' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->cv( 'c_bg', esc_html__( 'Background', 'nexvio' ), '--nexvio-pf-chip-bg', '#181d24' );
		$this->cv( 'c_bg_a', esc_html__( 'Selected background', 'nexvio' ), '--nexvio-pf-chip-bg-a', 'rgba(61,139,255,0.18)' );
		$this->cv( 'c_border_a', esc_html__( 'Selected border', 'nexvio' ), '--nexvio-pf-chip-border-a', '#3d8bff' );
		$this->add_control( 'h_more', array( 'label' => esc_html__( '"See more"', 'nexvio' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->cv( 'm_color', esc_html__( 'Colour', 'nexvio' ), '--nexvio-pf-more', '#8cc8ff' );
		$this->end_controls_section();

		/* ============ STYLE: ATTRIBUTES (one block per product attribute: colour, size, image…) ============ */
		$attrs = class_exists( 'Nexvio_Filter_Config' ) ? Nexvio_Filter_Config::attributes() : array();
		/* attributes added on Nexvio > Filters get their block automatically; with "Set here" fields every attribute has one */
		$used = array();
		if ( class_exists( 'Nexvio_Filter_Config' ) ) {
			foreach ( Nexvio_Filter_Config::fields() as $fld ) {
				if ( isset( $fld['type'], $fld['taxonomy'] ) && 'attribute' === $fld['type'] ) {
					$used[] = $fld['taxonomy'];
				}
			}
		}
		if ( $attrs ) {
			$this->start_controls_section( 'style_attributes', array( 'label' => esc_html__( 'Attribute circles and buttons', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
			$this->add_control( 'attr_note', array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Every attribute you add to the filter (colour, size, image…) gets its own block here with size and radius. Add an attribute on Nexvio > Filters and it appears here. Circles are for colour / image attributes, buttons for size and other attributes shown as buttons.', 'nexvio' ),
				'content_classes' => 'elementor-descriptor',
			) );
			$first = true;
			foreach ( $attrs as $tax => $info ) {
				$key  = sanitize_key( $tax );
				$cond = in_array( $tax, $used, true ) ? array() : array( 'fields_source' => 'widget' );
				$grp  = '{{WRAPPER}} .nexvio-pf .nexvio-pf__group[data-param="nexvio_tax_' . $tax . '"]';
				$this->add_control( 'attr_h_' . $key, array(
					'label'     => $info[0],
					'type'      => Controls_Manager::HEADING,
					'condition' => $cond,
					'separator' => $first ? 'default' : 'before',
				) );
				$first = false;
				$this->add_responsive_control( 'attr_dot_' . $key, array(
					'label'      => esc_html__( 'Circle size', 'nexvio' ),
					'type'       => Controls_Manager::SLIDER,
					'size_units' => array( 'px' ),
					'range'      => array( 'px' => array( 'min' => 12, 'max' => 90 ) ),
					'condition'  => $cond,
					'selectors'  => array( $grp . ' .nexvio-pf__dot' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ),
				) );
				$this->add_responsive_control( 'attr_dot_r_' . $key, array(
					'label'      => esc_html__( 'Circle radius', 'nexvio' ),
					'type'       => Controls_Manager::DIMENSIONS,
					'size_units' => array( 'px', '%' ),
					'condition'  => $cond,
					'selectors'  => array( $grp . ' .nexvio-pf__dot' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
				) );
				$this->add_responsive_control( 'attr_btn_w_' . $key, array(
					'label'      => esc_html__( 'Button min width', 'nexvio' ),
					'type'       => Controls_Manager::SLIDER,
					'size_units' => array( 'px' ),
					'range'      => array( 'px' => array( 'min' => 20, 'max' => 200 ) ),
					'condition'  => $cond,
					'selectors'  => array( $grp . ' .nexvio-pf__list--chips .nexvio-pf__opt, ' . $grp . ' .nexvio-pf__list--sizes .nexvio-pf__opt' => 'min-width: {{SIZE}}{{UNIT}};' ),
				) );
				$this->add_responsive_control( 'attr_btn_h_' . $key, array(
					'label'      => esc_html__( 'Button height', 'nexvio' ),
					'type'       => Controls_Manager::SLIDER,
					'size_units' => array( 'px' ),
					'range'      => array( 'px' => array( 'min' => 20, 'max' => 100 ) ),
					'condition'  => $cond,
					'selectors'  => array( $grp . ' .nexvio-pf__list--chips .nexvio-pf__opt, ' . $grp . ' .nexvio-pf__list--sizes .nexvio-pf__opt' => 'min-height: {{SIZE}}{{UNIT}}; box-sizing: border-box;' ),
				) );
				$this->add_responsive_control( 'attr_btn_r_' . $key, array(
					'label'      => esc_html__( 'Button radius', 'nexvio' ),
					'type'       => Controls_Manager::DIMENSIONS,
					'size_units' => array( 'px', '%' ),
					'condition'  => $cond,
					'selectors'  => array( $grp . ' .nexvio-pf__list--chips .nexvio-pf__opt, ' . $grp . ' .nexvio-pf__list--sizes .nexvio-pf__opt' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
				) );
			}
			$this->end_controls_section();
		}

		/* ============ STYLE: BUTTONS ============ */
		$this->start_controls_section( 'style_buttons', array( 'label' => esc_html__( 'Buttons', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_control( 'h_apply', array( 'label' => esc_html__( 'Apply button', 'nexvio' ), 'type' => Controls_Manager::HEADING ) );
		$this->start_controls_tabs( 'tabs_apply' );
		$this->start_controls_tab( 'tab_apply_n', array( 'label' => esc_html__( 'Normal', 'nexvio' ) ) );
		$this->cv( 'b_bg', esc_html__( 'Background', 'nexvio' ), '--nexvio-pf-btn', '' );
		$this->cv( 'b_text', esc_html__( 'Text', 'nexvio' ), '--nexvio-pf-btn-t', '' );
		$this->end_controls_tab();
		$this->start_controls_tab( 'tab_apply_h', array( 'label' => esc_html__( 'Hover', 'nexvio' ) ) );
		$this->cv( 'b_bg_h', esc_html__( 'Background', 'nexvio' ), '--nexvio-pf-btn-h', '' );
		$this->cv( 'b_text_h', esc_html__( 'Text', 'nexvio' ), '--nexvio-pf-btn-t-h', '' );
		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_control( 'h_reset', array( 'label' => esc_html__( 'Reset button', 'nexvio' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->start_controls_tabs( 'tabs_reset' );
		$this->start_controls_tab( 'tab_reset_n', array( 'label' => esc_html__( 'Normal', 'nexvio' ) ) );
		$this->cv( 'r_text', esc_html__( 'Text', 'nexvio' ), '--nexvio-pf-reset', '' );
		$this->cv( 'r_bg', esc_html__( 'Background', 'nexvio' ), '--nexvio-pf-reset-bg', '' );
		$this->cv( 'r_border', esc_html__( 'Border', 'nexvio' ), '--nexvio-pf-reset-b', '' );
		$this->end_controls_tab();
		$this->start_controls_tab( 'tab_reset_h', array( 'label' => esc_html__( 'Hover', 'nexvio' ) ) );
		$this->cv( 'r_text_h', esc_html__( 'Text', 'nexvio' ), '--nexvio-pf-reset-h', '' );
		$this->cv( 'r_bg_h', esc_html__( 'Background', 'nexvio' ), '--nexvio-pf-reset-bg-h', '' );
		$this->cv( 'r_border_h', esc_html__( 'Border', 'nexvio' ), '--nexvio-pf-reset-b-h', '' );
		$this->end_controls_tab();
		$this->end_controls_tabs();
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'b_typo', 'selector' => '{{WRAPPER}} .nexvio-pf__actions button', 'separator' => 'before' ) );
		$this->add_responsive_control( 'b_radius', array(
			'label'      => esc_html__( 'Corner radius', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 30 ) ),
			'selectors'  => array( '{{WRAPPER}} .nexvio-pf .nexvio-pf__actions button' => 'border-radius: {{SIZE}}{{UNIT}} !important; clip-path: none;' ),
		) );
		$this->end_controls_section();
	}

	/* ------------------------------------------------------------------
	 * Data helpers
	 * ------------------------------------------------------------------ */

	private function shop_url() {
		$url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : '';
		return $url ? $url : home_url( '/' );
	}

	private function current_cat_id() {
		if ( function_exists( 'is_product_category' ) && is_product_category() ) {
			$o = get_queried_object();
			return $o && isset( $o->term_id ) ? (int) $o->term_id : 0;
		}
		return 0;
	}

	/** Terms ordered parent-first, with a depth. */
	private function tree( $terms, $parent = 0, $depth = 0, &$out = array(), $top_only = false ) {
		foreach ( $terms as $t ) {
			if ( (int) $t->parent === (int) $parent ) {
				$t->nexvio_depth = $depth;
				$out[]        = $t;
				if ( ! $top_only ) {
					$this->tree( $terms, $t->term_id, $depth + 1, $out );
				}
			}
		}
		return $out;
	}

	private function cat_terms( $s ) {
		$orderby = in_array( $s['cat_orderby'], array( 'name', 'count', 'menu_order' ), true ) ? $s['cat_orderby'] : 'name';
		$args    = array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => 'yes' === $s['cat_hide_empty'],
			'pad_counts' => true,
			'orderby'    => $orderby,
			'order'      => 'count' === $orderby ? 'DESC' : 'ASC',
		);
		if ( 'menu_order' === $orderby ) {
			$args['orderby']  = 'meta_value_num';
			$args['meta_key'] = 'order'; // phpcs:ignore WordPress.DB.SlowDBQuery
		}
		$terms = get_terms( $args );
		if ( is_wp_error( $terms ) ) {
			return array();
		}
		$exclude = ! empty( $s['cat_exclude'] ) ? (array) $s['cat_exclude'] : array();
		$terms   = array_values( array_filter( $terms, function ( $t ) use ( $exclude ) {
			return ! in_array( $t->slug, $exclude, true );
		} ) );

		/* re-attach orphans (parent hidden) to the top level */
		$ids = wp_list_pluck( $terms, 'term_id' );
		foreach ( $terms as $t ) {
			if ( $t->parent && ! in_array( (int) $t->parent, array_map( 'intval', $ids ), true ) ) {
				$t->parent = 0;
			}
		}
		$out = array();
		return $this->tree( $terms, 0, 0, $out, 'top' === $s['cat_levels'] );
	}

	private function group_open( $s, $key ) {
		return 'yes' !== $s['collapsible'] || ! isset( $s[ $key ] ) || 'yes' === $s[ $key ];
	}

	private function chevron() {
		return '<svg class="nexvio-pf__chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>';
	}

	private function group_open_tag( $s, $type, $title, $open, $extra_attr = '' ) {
		$id = 'nexvio-pf-' . $this->get_id() . '-' . sanitize_html_class( $type );
		echo wp_kses( '<section class="nexvio-pf__group nexvio-pf__group--' . esc_attr( $type ) . ( $open ? ' is-open' : '' ) . '" ' . $extra_attr . '>', Nexvio_Plugin::allowed_html() );
		if ( '' !== trim( (string) $title ) ) {
			if ( 'yes' === $s['collapsible'] ) {
				echo wp_kses( '<h4 class="nexvio-pf__gtitle"><button type="button" class="nexvio-pf__toggle" aria-expanded="' . ( $open ? 'true' : 'false' ) . '" aria-controls="' . esc_attr( $id ) . '">' . esc_html( $title ) . $this->chevron() . '</button></h4>', Nexvio_Plugin::allowed_html() );
			} else {
				echo '<h4 class="nexvio-pf__gtitle">' . esc_html( $title ) . '</h4>';
			}
		}
		echo '<div class="nexvio-pf__gbody" id="' . esc_attr( $id ) . '"' . ( $open ? '' : ' hidden' ) . '>';
	}

	private function group_close() {
		echo '</div></section>';
	}

	/** Option list (radio / checkbox / chips) with "see more". */
	private function option_list( $items, $type, $name, $limit, $show_counts, $more, $less, $all_text = '' ) {
		$limit  = max( 1, (int) $limit );
		$count  = count( $items );
		$hidden = max( 0, $count - $limit );
		$input  = 'radio' === $type ? 'radio' : 'checkbox';
		$is_ch  = in_array( $type, array( 'chips', 'size' ), true );
		$is_sw  = in_array( $type, array( 'color', 'image' ), true );
		if ( $is_sw ) {
			$this->swatch_list( $items, $name, $limit, $show_counts, $more, $less );
			return;
		}

		echo '<ul class="nexvio-pf__list' . ( $is_ch ? ' nexvio-pf__list--chips' : '' ) . ( 'size' === $type ? ' nexvio-pf__list--sizes' : '' ) . '" data-limit="' . esc_attr( $limit ) . '">';
		if ( 'radio' === $type && '' !== $all_text ) {
			$none = true;
			foreach ( $items as $it ) {
				if ( ! empty( $it['checked'] ) ) {
					$none = false;
				}
			}
			echo '<li class="nexvio-pf__item"><label class="nexvio-pf__opt"><input type="radio" name="' . esc_attr( $name ) . '" value=""' . ( $none ? ' checked' : '' ) . '><span class="nexvio-pf__mark" aria-hidden="true"></span><span class="nexvio-pf__name">' . esc_html( $all_text ) . '</span></label></li>';
		}
		foreach ( $items as $i => $it ) {
			$cls = 'nexvio-pf__item' . ( $i >= $limit ? ' is-extra' : '' );
			if ( ! empty( $it['depth'] ) ) {
				$cls .= ' depth-' . min( 4, (int) $it['depth'] );
			}
			$attrs = '';
			if ( ! empty( $it['url'] ) ) {
				$attrs .= ' data-url="' . esc_url( $it['url'] ) . '"';
			}
			echo '<li class="' . esc_attr( $cls ) . '"><label class="nexvio-pf__opt' . ( $is_ch ? ' nexvio-pf__chip' : '' ) . '">';
			echo wp_kses( '<input type="' . esc_attr( $input ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $it['value'] ) . '"' . ( ! empty( $it['checked'] ) ? ' checked' : '' ) . $attrs . '>', Nexvio_Plugin::allowed_html() );
			if ( ! $is_ch ) {
				echo '<span class="nexvio-pf__mark" aria-hidden="true"></span>';
			}
			echo '<span class="nexvio-pf__name">' . esc_html( $it['label'] ) . '</span>';
			if ( $show_counts && isset( $it['count'] ) ) {
				echo '<span class="nexvio-pf__count">' . esc_html( $it['count'] ) . '</span>';
			}
			echo '</label></li>';
		}
		echo '</ul>';
		if ( $hidden > 0 ) {
			echo '<button type="button" class="nexvio-pf__more" aria-expanded="false" data-more="' . esc_attr( str_replace( '{n}', (string) $hidden, $more ) ) . '" data-less="' . esc_attr( $less ) . '">' . esc_html( str_replace( '{n}', (string) $hidden, $more ) ) . '</button>';
		}
	}

	/** Colour circles (several can be chosen). */
	private function swatch_list( $items, $name, $limit, $show_counts, $more, $less ) {
		$limit  = max( 1, (int) $limit );
		$hidden = max( 0, count( $items ) - $limit );
		echo '<ul class="nexvio-pf__list nexvio-pf__list--swatches" data-limit="' . esc_attr( $limit ) . '">';
		foreach ( $items as $i => $it ) {
			$color = ! empty( $it['color'] ) ? $it['color'] : '';
			$light = in_array( strtolower( $color ), array( '#fff', '#ffffff', '#fffff0', '#f6f3ea', '#f5ecd7' ), true );
			$tip   = $it['label'] . ( $show_counts && isset( $it['count'] ) ? ' (' . (int) $it['count'] . ')' : '' );
			echo '<li class="nexvio-pf__item' . ( $i >= $limit ? ' is-extra' : '' ) . '"><label class="nexvio-pf__swatch' . ( $light ? ' is-light' : '' ) . ( $color || ! empty( $it['image'] ) ? '' : ' is-unknown' ) . '" title="' . esc_attr( $tip ) . '">';
			echo '<input type="checkbox" name="' . esc_attr( $name ) . '" value="' . esc_attr( $it['value'] ) . '"' . ( ! empty( $it['checked'] ) ? ' checked' : '' ) . '>';
			$pic = ! empty( $it['image'] ) ? $it['image'] : '';
			echo '<span class="nexvio-pf__dot' . ( $pic ? ' is-image' : '' ) . '" style="--sw:' . esc_attr( $color ? $color : '#d1d5db' ) . ( $pic ? ';--sw-img:url(' . esc_url( $pic ) . ')' : '' ) . '" aria-hidden="true">' . ( $color || $pic ? '' : esc_html( function_exists( 'mb_substr' ) ? mb_substr( $it['label'], 0, 1 ) : substr( $it['label'], 0, 1 ) ) ) . '</span>';
			echo '<span class="nexvio-pf__swatch-name">' . esc_html( $it['label'] ) . '</span></label></li>';
		}
		echo '</ul>';
		if ( $hidden > 0 ) {
			echo '<button type="button" class="nexvio-pf__more" aria-expanded="false" data-more="' . esc_attr( str_replace( '{n}', (string) $hidden, $more ) ) . '" data-less="' . esc_attr( $less ) . '">' . esc_html( str_replace( '{n}', (string) $hidden, $more ) ) . '</button>';
		}
	}

	/* ------------------------------------------------------------------
	 * Output
	 * ------------------------------------------------------------------ */

	/** Defaults for every setting used below (hidden controls are not in the settings). */
	protected function base_settings( $s ) {
		$d = array(
			'show_title' => '', 'title' => '', 'ajax' => 'yes', 'apply_mode' => 'instant', 'apply_text' => 'Apply', 'show_reset' => 'yes', 'reset_text' => 'Reset',
			'collapsible' => 'yes', 'selector' => '.nexvio-pg:not(.nexvio-pg--grid)', 'scroll_top' => 'yes',
			'show_search' => '', 'search_title' => 'Search', 'search_ph' => 'Search products…', 'search_min' => 2, 'search_delay' => 450, 'search_open' => 'yes',
			'show_price' => '', 'price_title' => 'Price', 'price_bounds' => 'auto', 'price_min' => 0, 'price_max' => 500, 'price_step' => 1, 'price_inputs' => 'yes', 'price_slider' => 'yes',
			'price_min_label' => 'Min', 'price_max_label' => 'Max', 'price_open' => 'yes',
			'show_cat' => '', 'cat_title' => 'Category', 'cat_all' => 'yes', 'cat_all_text' => 'All categories', 'cat_counts' => 'yes', 'cat_hide_empty' => 'yes', 'cat_levels' => 'all',
			'cat_orderby' => 'name', 'cat_exclude' => array(), 'cat_limit' => 10, 'cat_collapse' => 'yes', 'cat_open' => 'yes', 'cat_display' => 'radio',
			'more_text' => 'Show more ({n})', 'less_text' => 'Show less', 'extra_filters' => array(), 'fields_source' => 'global',
		);
		foreach ( (array) $s as $k => $v ) {
			if ( null !== $v ) {
				$d[ $k ] = $v;
			}
		}
		return $d;
	}

	/** The filter fields in display order: [ kind, settings, extra field ]. */
	private function groups( $s ) {
		$groups = array();
		if ( 'widget' === $s['fields_source'] || ! class_exists( 'Nexvio_Filter_Config' ) ) {
			foreach ( array( 'search', 'price', 'cat' ) as $k ) {
				if ( 'yes' === $s[ 'show_' . $k ] ) {
					$groups[] = array( 'cat' === $k ? 'category' : $k, $s, null );
				}
			}
			foreach ( (array) $s['extra_filters'] as $f ) {
				$groups[] = array( 'extra', $s, $f );
			}
			return $groups;
		}
		$yn = function ( $b ) {
			return $b ? 'yes' : '';
		};
		foreach ( Nexvio_Filter_Config::fields() as $f ) {
			$label = isset( $f['label'] ) ? $f['label'] : '';
			$open  = $yn( ! empty( $f['open'] ) );
			switch ( $f['type'] ) {
				case 'search':
					$groups[] = array( 'search', array_merge( $s, array( 'search_title' => $label, 'search_ph' => ! empty( $f['placeholder'] ) ? $f['placeholder'] : __( 'Search products…', 'nexvio' ), 'search_open' => $open ) ), null );
					break;
				case 'price':
					$disp     = isset( $f['display'] ) ? $f['display'] : 'slider_boxes';
					$groups[] = array( 'price', array_merge( $s, array( 'price_title' => $label, 'price_bounds' => 'auto', 'price_slider' => $yn( 'boxes' !== $disp ), 'price_inputs' => $yn( 'slider' !== $disp ), 'price_open' => $open ) ), null );
					break;
				case 'category':
					$groups[] = array( 'category', array_merge( $s, array( 'cat_title' => $label, 'cat_counts' => $yn( ! empty( $f['counts'] ) ), 'cat_hide_empty' => $yn( ! empty( $f['hide_empty'] ) ), 'cat_collapse' => $yn( ! empty( $f['collapse'] ) ), 'cat_all' => $yn( ! empty( $f['all'] ) ), 'cat_all_text' => __( 'All categories', 'nexvio' ), 'cat_limit' => isset( $f['limit'] ) ? (int) $f['limit'] : 10, 'cat_levels' => 'all', 'cat_open' => $open, 'cat_display' => isset( $f['display'] ) ? $f['display'] : 'radio' ) ), null );
					break;
				default:
					$groups[] = array(
						'extra',
						$s,
						array(
							'f_title'       => $label,
							'f_source'      => $f['type'],
							'f_attr'        => isset( $f['taxonomy'] ) ? $f['taxonomy'] : '',
							'f_display'     => isset( $f['display'] ) ? $f['display'] : 'checkbox',
							'f_logic'       => isset( $f['logic'] ) ? $f['logic'] : 'any',
							'f_counts'      => $yn( ! empty( $f['counts'] ) ),
							'f_hide_empty'  => $yn( ! isset( $f['hide_empty'] ) || ! empty( $f['hide_empty'] ) ),
							'f_limit'       => isset( $f['limit'] ) ? (int) $f['limit'] : 10,
							'f_open'        => $open,
							'f_all_text'    => __( 'All', 'nexvio' ),
							'f_toggle_text' => ! empty( $f['text'] ) ? $f['text'] : ( 'sale' === $f['type'] ? __( 'On sale', 'nexvio' ) : __( 'Featured', 'nexvio' ) ),
							'f_in'          => __( 'In stock', 'nexvio' ),
							'f_back'        => __( 'Pre-order', 'nexvio' ),
							'f_out'         => ! empty( $f['show_out'] ) ? __( 'Sold out', 'nexvio' ) : '',
							'f_values'      => isset( $f['values'] ) ? $f['values'] : '12,24,48',
						),
					);
			}
		}
		return $groups;
	}

	protected function render() {
		$this->render_filter( $this->base_settings( $this->get_settings_for_display() ) );
	}

	/** The filter itself; the Filter Popup widget calls it with its own settings. */
	protected function render_filter( $s, $extra_class = '', $head_html = '' ) {
		$groups  = $this->groups( $s );
		$shop    = $this->shop_url();
		$cur_cat = $this->current_cat_id();
		$base    = $shop;
		if ( $cur_cat ) {
			$link = get_term_link( $cur_cat, 'product_cat' );
			$base = is_wp_error( $link ) ? $shop : $link;
		}

		/* price */
		$ps = null;
		foreach ( $groups as $g ) {
			if ( 'price' === $g[0] ) {
				$ps = $g[1];
			}
		}
		$bounds = array( 'min' => 0, 'max' => 100 );
		if ( $ps ) {
			if ( 'manual' === $ps['price_bounds'] ) {
				$bounds = array( 'min' => (float) $ps['price_min'], 'max' => max( (float) $ps['price_min'] + 1, (float) $ps['price_max'] ) );
			} else {
				$bounds = Nexvio_Filter_Query::price_bounds();
			}
		}
		$price = array(
			'bounds' => $bounds,
			'min'    => '' !== Nexvio_Filter_Query::get( 'min_price' ) ? max( $bounds['min'], (float) Nexvio_Filter_Query::get( 'min_price' ) ) : $bounds['min'],
			'max'    => '' !== Nexvio_Filter_Query::get( 'max_price' ) ? min( $bounds['max'], (float) Nexvio_Filter_Query::get( 'max_price' ) ) : $bounds['max'],
			'step'   => max( 0.01, (float) ( $ps ? $ps['price_step'] : 1 ) ),
			'cur'    => function_exists( 'get_woocommerce_currency_symbol' ) ? html_entity_decode( get_woocommerce_currency_symbol() ) : '€',
			'pos'    => get_option( 'woocommerce_currency_pos', 'right_space' ),
		);

		$config = array(
			'ajax'     => 'yes' === $s['ajax'],
			'instant'  => 'button' !== $s['apply_mode'],
			'selector' => trim( (string) $s['selector'] ) ? trim( (string) $s['selector'] ) : '.nexvio-pg:not(.nexvio-pg--grid)',
			'scroll'   => 'yes' === $s['scroll_top'],
			'delay'    => max( 100, (int) $s['search_delay'] ),
			'minChars' => max( 1, (int) $s['search_min'] ),
			'shop'     => $shop,
			'base'     => $base,
			'bounds'   => $bounds,
		);
		?>
		<?php $nexvio_style = class_exists( 'Nexvio_Filter_Config' ) ? Nexvio_Filter_Config::style() : array( 'preset' => 'light' ); ?>
		<div class="nexvio-pf nexvio-pf--<?php echo esc_attr( $nexvio_style['preset'] ); ?><?php echo 'yes' === $s['collapsible'] ? ' nexvio-pf--collapsible' : ''; ?><?php echo $extra_class ? ' ' . esc_attr( $extra_class ) : ''; ?>" data-config="<?php echo esc_attr( wp_json_encode( $config ) ); ?>">
			<?php if ( $head_html ) : ?>
				<?php echo wp_kses( $head_html, Nexvio_Plugin::allowed_html() ); ?>
			<?php elseif ( 'yes' === $s['show_title'] && '' !== trim( (string) $s['title'] ) ) : ?>
				<div class="nexvio-pf__title"><?php echo esc_html( $s['title'] ); ?></div>
			<?php endif; ?>
			<form class="nexvio-pf__form" action="<?php echo esc_url( $base ); ?>" method="get" role="search">
				<?php
				foreach ( $groups as $g ) {
					if ( 'search' === $g[0] ) {
						$this->render_search( $g[1] );
					} elseif ( 'price' === $g[0] ) {
						$this->render_price( $g[1], $price );
					} elseif ( 'category' === $g[0] ) {
						$this->render_category( $g[1], $shop, $cur_cat );
					} else {
						$this->render_extra( $g[2], $g[1] );
					}
				}

				/* ---------- buttons ---------- */
				$show_apply = 'button' === $s['apply_mode'];
				if ( $show_apply || 'yes' === $s['show_reset'] ) :
					?>
					<div class="nexvio-pf__actions">
						<?php if ( $show_apply ) : ?>
							<button type="submit" class="nexvio-pf__apply"><?php echo esc_html( $s['apply_text'] ); ?></button>
						<?php endif; ?>
						<?php if ( 'yes' === $s['show_reset'] ) : ?>
							<button type="button" class="nexvio-pf__reset"><?php echo esc_html( $s['reset_text'] ); ?></button>
						<?php endif; ?>
					</div>
					<?php
				endif;
				?>
			</form>
		</div>
		<?php
	}


	private function render_search( $s ) {
		$this->group_open_tag( $s, 'search', $s['search_title'], $this->group_open( $s, 'search_open' ) );
		?>
		<div class="nexvio-pf__search">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
			<input type="search" name="nexvio_q" value="<?php echo esc_attr( Nexvio_Filter_Query::search_term() ); ?>" placeholder="<?php echo esc_attr( $s['search_ph'] ); ?>" autocomplete="off" aria-label="<?php echo esc_attr( $s['search_title'] ? $s['search_title'] : $s['search_ph'] ); ?>">
			<button type="button" class="nexvio-pf__clear" aria-label="<?php esc_attr_e( 'Clear search', 'nexvio' ); ?>"<?php echo '' === Nexvio_Filter_Query::search_term() ? ' hidden' : ''; ?>>&times;</button>
		</div>
		<?php
		$this->group_close();
	}

	private function render_price( $s, $p ) {
		$b    = $p['bounds'];
		$span = $b['max'] - $b['min'];
		$lo   = $span > 0 ? ( $p['min'] - $b['min'] ) / $span * 100 : 0;
		$hi   = $span > 0 ? ( $p['max'] - $b['min'] ) / $span * 100 : 100;
		$this->group_open_tag( $s, 'price', $s['price_title'], $this->group_open( $s, 'price_open' ), 'data-currency="' . esc_attr( $p['cur'] ) . '" data-pos="' . esc_attr( $p['pos'] ) . '"' );
		if ( 'yes' === $s['price_slider'] ) :
			?>
			<div class="nexvio-pf__range" style="--lo:<?php echo esc_attr( round( $lo, 3 ) ); ?>%;--hi:<?php echo esc_attr( round( $hi, 3 ) ); ?>%">
				<div class="nexvio-pf__track"><div class="nexvio-pf__fill"></div></div>
				<input type="range" class="nexvio-pf__lo" min="<?php echo esc_attr( $b['min'] ); ?>" max="<?php echo esc_attr( $b['max'] ); ?>" step="<?php echo esc_attr( $p['step'] ); ?>" value="<?php echo esc_attr( $p['min'] ); ?>" aria-label="<?php echo esc_attr( $s['price_min_label'] ); ?>">
				<input type="range" class="nexvio-pf__hi" min="<?php echo esc_attr( $b['min'] ); ?>" max="<?php echo esc_attr( $b['max'] ); ?>" step="<?php echo esc_attr( $p['step'] ); ?>" value="<?php echo esc_attr( $p['max'] ); ?>" aria-label="<?php echo esc_attr( $s['price_max_label'] ); ?>">
			</div>
			<?php
		endif;
		if ( 'yes' === $s['price_inputs'] ) :
			$left = in_array( $p['pos'], array( 'left', 'left_space' ), true );
			$sym  = '<i>' . esc_html( $p['cur'] ) . '</i>';
			?>
			<div class="nexvio-pf__prices">
				<?php foreach ( array( 'min_price' => array( $s['price_min_label'], $p['min'] ), 'max_price' => array( $s['price_max_label'], $p['max'] ) ) as $name => $box ) : ?>
					<?php if ( 'max_price' === $name ) : ?><span class="nexvio-pf__dash" aria-hidden="true">–</span><?php endif; ?>
					<label class="nexvio-pf__pbox">
						<span class="nexvio-pf__plabel"><?php echo esc_html( $box[0] ); ?></span>
						<span class="nexvio-pf__pfield"><?php echo wp_kses( $left ? $sym : '', Nexvio_Plugin::allowed_html() ); ?><input type="number" name="<?php echo esc_attr( $name ); ?>" inputmode="decimal" min="<?php echo esc_attr( $b['min'] ); ?>" max="<?php echo esc_attr( $b['max'] ); ?>" step="<?php echo esc_attr( $p['step'] ); ?>" value="<?php echo esc_attr( $box[1] ); ?>"><?php echo wp_kses( $left ? '' : $sym, Nexvio_Plugin::allowed_html() ); ?></span>
					</label>
				<?php endforeach; ?>
			</div>
			<?php
		endif;
		$this->group_close();
	}

	private function render_category( $s, $shop, $cur_cat ) {
		$terms   = $this->cat_terms( $s );
		$display = in_array( $s['cat_display'], array( 'radio', 'checkbox', 'select', 'chips' ), true ) ? $s['cat_display'] : 'radio';

		/* several categories: filter by category on the current page (nexvio_tax_product_cat) */
		if ( 'checkbox' === $display || 'chips' === $display ) {
			$selected = Nexvio_Filter_Query::slugs( 'nexvio_tax_product_cat' );
			$this->group_open_tag( $s, 'category', $s['cat_title'], $this->group_open( $s, 'cat_open' ), 'data-param="nexvio_tax_product_cat" data-kind="multi" data-logic="any"' );
			if ( 'checkbox' === $display && 'all' === $s['cat_levels'] ) {
				$this->category_tree( $terms, $cur_cat, $s, 'checkbox', $selected );
			} else {
				$items = array();
				foreach ( $terms as $t ) {
					$items[] = array( 'value' => $t->slug, 'label' => $t->name, 'count' => (int) $t->count, 'checked' => in_array( $t->slug, $selected, true ) );
				}
				$this->option_list( $items, $display, 'nexvio_tax_product_cat', $s['cat_limit'], 'yes' === $s['cat_counts'], $s['more_text'], $s['less_text'] );
			}
			$this->group_close();
			return;
		}

		/* dropdown: one category, opens its page */
		if ( 'select' === $display ) {
			$this->group_open_tag( $s, 'category', $s['cat_title'], $this->group_open( $s, 'cat_open' ), 'data-param="category"' );
			echo '<div class="nexvio-pf__select"><select name="nexvio_cat" aria-label="' . esc_attr( $s['cat_title'] ) . '">';
			echo '<option value="" data-url="' . esc_url( $shop ) . '">' . esc_html( $s['cat_all_text'] ) . '</option>';
			foreach ( $terms as $t ) {
				$link  = get_term_link( $t );
				$depth = isset( $t->nexvio_depth ) ? (int) $t->nexvio_depth : 0;
				$label = str_repeat( '— ', $depth ) . $t->name . ( 'yes' === $s['cat_counts'] ? ' (' . (int) $t->count . ')' : '' );
				echo '<option value="' . esc_attr( $t->slug ) . '" data-url="' . esc_url( is_wp_error( $link ) ? '' : $link ) . '"' . selected( (int) $t->term_id, (int) $cur_cat, false ) . '>' . esc_html( $label ) . '</option>';
			}
			echo '</select></div>';
			$this->group_close();
			return;
		}

		$this->group_open_tag( $s, 'category', $s['cat_title'], $this->group_open( $s, 'cat_open' ), 'data-param="category"' );
		if ( 'yes' === $s['cat_all'] ) {
			echo '<ul class="nexvio-pf__list nexvio-pf__list--all"><li class="nexvio-pf__item"><label class="nexvio-pf__opt"><input type="radio" name="nexvio_cat" value="" data-url="' . esc_url( $shop ) . '"' . ( $cur_cat ? '' : ' checked' ) . '><span class="nexvio-pf__mark" aria-hidden="true"></span><span class="nexvio-pf__name">' . esc_html( $s['cat_all_text'] ) . '</span></label></li></ul>';
		}
		if ( 'all' === $s['cat_levels'] ) {
			$this->category_tree( $terms, $cur_cat, $s );
		} else {
			$items = array();
			foreach ( $terms as $t ) {
				$link    = get_term_link( $t );
				$items[] = array( 'value' => $t->slug, 'label' => $t->name, 'count' => (int) $t->count, 'url' => is_wp_error( $link ) ? '' : $link, 'checked' => (int) $t->term_id === $cur_cat );
			}
			$this->option_list( $items, 'radio', 'nexvio_cat', $s['cat_limit'], 'yes' === $s['cat_counts'], $s['more_text'], $s['less_text'] );
		}
		$this->group_close();
	}

	/**
	 * Categories with their sub-categories nested and indented.
	 * Collapsed: sub-categories stay hidden until "+" is clicked (the branch of the current category is open).
	 */
	private function category_tree( $terms, $cur_cat, $s, $input = 'radio', $selected = array() ) {
		$children = array();
		$ids      = array();
		foreach ( $terms as $t ) {
			$ids[ (int) $t->term_id ] = true;
		}
		foreach ( $terms as $t ) {
			$parent                = isset( $ids[ (int) $t->parent ] ) ? (int) $t->parent : 0;
			$children[ $parent ][] = $t;
		}
		$open_ids = array();
		if ( $cur_cat ) {
			$open_ids   = array_map( 'intval', get_ancestors( $cur_cat, 'product_cat', 'taxonomy' ) );
			$open_ids[] = (int) $cur_cat;
		}
		$collapse = 'yes' === $s['cat_collapse'];
		$counts   = 'yes' === $s['cat_counts'];
		$limit    = max( 1, (int) $s['cat_limit'] );
		$top      = isset( $children[0] ) ? $children[0] : array();
		$hidden   = max( 0, count( $top ) - $limit );

		if ( 'checkbox' === $input ) {
			foreach ( $terms as $t ) {
				if ( in_array( $t->slug, $selected, true ) ) {
					$open_ids = array_merge( $open_ids, array_map( 'intval', get_ancestors( (int) $t->term_id, 'product_cat', 'taxonomy' ) ) );
				}
			}
		}
		$walk = function ( $parent, $depth ) use ( &$walk, $children, $cur_cat, $open_ids, $collapse, $counts, $limit, $input, $selected ) {
			$out = '';
			foreach ( $children[ $parent ] as $i => $t ) {
				$link  = get_term_link( $t );
				$kids  = ! empty( $children[ (int) $t->term_id ] );
				$open  = ! $collapse || in_array( (int) $t->term_id, $open_ids, true );
				$sub   = 'nexvio-pf-sub-' . (int) $t->term_id;
				$cls   = 'nexvio-pf__item' . ( 0 === $depth && $i >= $limit ? ' is-extra' : '' ) . ( $kids ? ' has-children' : '' );
				$out  .= '<li class="' . esc_attr( $cls ) . '"><div class="nexvio-pf__row">';
				if ( 'checkbox' === $input ) {
					$out .= '<label class="nexvio-pf__opt"><input type="checkbox" name="nexvio_tax_product_cat" value="' . esc_attr( $t->slug ) . '"' . ( in_array( $t->slug, $selected, true ) ? ' checked' : '' ) . '>';
				} else {
					$out .= '<label class="nexvio-pf__opt"><input type="radio" name="nexvio_cat" value="' . esc_attr( $t->slug ) . '" data-url="' . esc_url( is_wp_error( $link ) ? '' : $link ) . '"' . ( (int) $t->term_id === $cur_cat ? ' checked' : '' ) . '>';
				}
				$out  .= '<span class="nexvio-pf__mark" aria-hidden="true"></span><span class="nexvio-pf__name">' . esc_html( $t->name ) . '</span>';
				if ( $counts ) {
					$out .= '<span class="nexvio-pf__count">' . (int) $t->count . '</span>';
				}
				$out .= '</label>';
				if ( $kids && $collapse ) {
					/* translators: %s: category name */
					$out .= '<button type="button" class="nexvio-pf__sub-toggle" aria-expanded="' . ( $open ? 'true' : 'false' ) . '" aria-controls="' . esc_attr( $sub ) . '" aria-label="' . esc_attr( sprintf( __( 'Show the sub-categories of %s', 'nexvio' ), $t->name ) ) . '"><span aria-hidden="true"></span></button>';
				}
				$out .= '</div>';
				if ( $kids ) {
					$out .= '<ul class="nexvio-pf__sublist" id="' . esc_attr( $sub ) . '"' . ( $open ? '' : ' hidden' ) . '>' . $walk( (int) $t->term_id, $depth + 1 ) . '</ul>';
				}
				$out .= '</li>';
			}
			return $out;
		};

		if ( ! $top ) {
			return;
		}
		echo wp_kses( '<ul class="nexvio-pf__list nexvio-pf__list--tree" data-limit="' . esc_attr( $limit ) . '">' . $walk( 0, 0 ) . '</ul>', Nexvio_Plugin::allowed_html() );
		if ( $hidden > 0 ) {
			echo '<button type="button" class="nexvio-pf__more" aria-expanded="false" data-more="' . esc_attr( str_replace( '{n}', (string) $hidden, $s['more_text'] ) ) . '" data-less="' . esc_attr( $s['less_text'] ) . '">' . esc_html( str_replace( '{n}', (string) $hidden, $s['more_text'] ) ) . '</button>';
		}
	}

	private function render_extra( $f, $s ) {
		$source  = isset( $f['f_source'] ) ? $f['f_source'] : 'attribute';
		$title   = isset( $f['f_title'] ) ? $f['f_title'] : '';
		$open    = 'yes' !== $s['collapsible'] || ! isset( $f['f_open'] ) || 'yes' === $f['f_open'];
		$display = isset( $f['f_display'] ) ? $f['f_display'] : 'checkbox';

		/* on / off filters */
		if ( 'sale' === $source || 'featured' === $source ) {
			$param = 'sale' === $source ? 'nexvio_sale' : 'nexvio_featured';
			$on    = '1' === Nexvio_Filter_Query::get( $param );
			$this->group_open_tag( $s, $param, $title, $open, 'data-param="' . esc_attr( $param ) . '" data-kind="toggle"' );
			echo '<ul class="nexvio-pf__list"><li class="nexvio-pf__item"><label class="nexvio-pf__opt"><input type="checkbox" name="' . esc_attr( $param ) . '" value="1"' . ( $on ? ' checked' : '' ) . '><span class="nexvio-pf__mark" aria-hidden="true"></span><span class="nexvio-pf__name">' . esc_html( $f['f_toggle_text'] ) . '</span></label></li></ul>';
			$this->group_close();
			return;
		}

		/* shop: sort by */
		if ( 'sort' === $source ) {
			// phpcs:disable WordPress.WP.I18n.TextDomainMismatch -- WooCommerce's own sorting labels, already translated.
			$options = apply_filters(
				'woocommerce_catalog_orderby', // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core hook, keeps custom sort options.
				array(
					'menu_order' => __( 'Default sorting', 'woocommerce' ),
					'popularity' => __( 'Sort by popularity', 'woocommerce' ),
					'rating'     => __( 'Sort by average rating', 'woocommerce' ),
					'date'       => __( 'Sort by latest', 'woocommerce' ),
					'price'      => __( 'Sort by price: low to high', 'woocommerce' ),
					'price-desc' => __( 'Sort by price: high to low', 'woocommerce' ),
				)
			);
			// phpcs:enable WordPress.WP.I18n.TextDomainMismatch
			$cur   = Nexvio_Filter_Query::get( 'orderby' );
			$items = array();
			foreach ( (array) $options as $val => $lab ) {
				$items[] = array( 'value' => 'menu_order' === $val ? '' : $val, 'label' => $lab, 'checked' => ( 'menu_order' === $val && '' === $cur ) || $val === $cur );
			}
			$this->render_choice( $s, 'orderby', $title, $open, $display, $items );
			return;
		}

		/* shop: products per page */
		if ( 'perpage' === $source ) {
			$cur   = (int) Nexvio_Filter_Query::get( 'nexvio_pp' );
			$items = array( array( 'value' => '', 'label' => isset( $f['f_all_text'] ) ? $f['f_all_text'] : __( 'All', 'nexvio' ), 'checked' => ! $cur ) );
			foreach ( array_filter( array_map( 'intval', explode( ',', isset( $f['f_values'] ) ? $f['f_values'] : '12,24,48' ) ) ) as $n ) {
				$items[] = array( 'value' => (string) $n, 'label' => (string) $n, 'checked' => $cur === $n );
			}
			$items[0]['label'] = __( 'Default', 'nexvio' );
			$this->render_choice( $s, 'nexvio_pp', $title, $open, $display, $items );
			return;
		}

		/* rating: 4 stars & up… */
		if ( 'rating' === $source ) {
			$param = 'nexvio_rating';
			$cur   = (int) Nexvio_Filter_Query::get( $param );
			$this->group_open_tag( $s, $param, $title, $open, 'data-param="' . esc_attr( $param ) . '" data-kind="single"' );
			echo '<ul class="nexvio-pf__list nexvio-pf__list--rating">';
			echo '<li class="nexvio-pf__item"><label class="nexvio-pf__opt"><input type="radio" name="' . esc_attr( $param ) . '" value=""' . ( $cur ? '' : ' checked' ) . '><span class="nexvio-pf__mark" aria-hidden="true"></span><span class="nexvio-pf__name">' . esc_html( isset( $f['f_all_text'] ) ? $f['f_all_text'] : __( 'All', 'nexvio' ) ) . '</span></label></li>';
			foreach ( array( 4, 3, 2, 1 ) as $n ) {
				/* translators: %d: number of stars */
				$label = sprintf( __( '%d stars & up', 'nexvio' ), $n );
				echo '<li class="nexvio-pf__item"><label class="nexvio-pf__opt"><input type="radio" name="' . esc_attr( $param ) . '" value="' . (int) $n . '"' . checked( $cur, $n, false ) . '><span class="nexvio-pf__mark" aria-hidden="true"></span><span class="nexvio-pf__stars" style="--r:' . (int) ( $n * 20 ) . '%" aria-hidden="true"></span><span class="nexvio-pf__name">' . esc_html( $label ) . '</span></label></li>';
			}
			echo '</ul>';
			$this->group_close();
			return;
		}

		/* stock */
		if ( 'stock' === $source ) {
			$param    = 'nexvio_stock';
			$selected = Nexvio_Filter_Query::slugs( $param );
			$items    = array();
			foreach ( array( 'instock' => $f['f_in'], 'onbackorder' => $f['f_back'], 'outofstock' => $f['f_out'] ) as $val => $label ) {
				if ( '' !== trim( (string) $label ) ) {
					$items[] = array( 'value' => $val, 'label' => $label, 'checked' => in_array( $val, $selected, true ) );
				}
			}
			$this->render_values( $f, $s, $param, $title, $open, $display, $items, false, 'any' );
			return;
		}

		/* taxonomies */
		if ( 'attribute' === $source ) {
			$taxonomy = isset( $f['f_attr'] ) ? $f['f_attr'] : '';
		} elseif ( 'tag' === $source ) {
			$taxonomy = 'product_tag';
		} else {
			$taxonomy = isset( $f['f_tax'] ) ? sanitize_key( $f['f_tax'] ) : '';
		}
		if ( ! $taxonomy || ! taxonomy_exists( $taxonomy ) ) {
			if ( class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				/* translators: %s: filter title */
				echo '<p class="nexvio-pf__note">' . esc_html( sprintf( __( 'Filter "%s": choose an existing attribute / taxonomy.', 'nexvio' ), $title ) ) . '</p>';
			}
			return;
		}
		$orderby = 'name';
		if ( 0 === strpos( $taxonomy, 'pa_' ) && function_exists( 'wc_attribute_orderby' ) ) {
			$orderby = wc_attribute_orderby( $taxonomy ); /* the order chosen in Products > Attributes */
		}
		$terms = get_terms( array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => ! isset( $f['f_hide_empty'] ) || 'yes' === $f['f_hide_empty'],
			'orderby'    => in_array( $orderby, array( 'menu_order', 'name', 'name_num', 'id' ), true ) ? $orderby : 'name',
		) );
		if ( 'auto' === $display ) {
			/* pictures on the values -> image circles; colour attribute -> colour circles; size -> buttons */
			$display = '';
			if ( class_exists( 'Nexvio_Swatches' ) && ! is_wp_error( $terms ) ) {
				foreach ( (array) $terms as $t ) {
					if ( Nexvio_Swatches::image_for( (int) $t->term_id ) ) {
						$display = 'image';
						break;
					}
				}
			}
			if ( ! $display ) {
				$label_text = function_exists( 'wc_attribute_label' ) ? wc_attribute_label( $taxonomy ) : $taxonomy;
				$guess      = class_exists( 'Nexvio_Filter_Config' ) ? Nexvio_Filter_Config::guess( $taxonomy . ' ' . $label_text ) : '';
				$display    = $guess ? $guess : 'checkbox';
			}
		}
		if ( is_wp_error( $terms ) || ! $terms ) {
			return;
		}
		$param    = 'nexvio_tax_' . $taxonomy;
		$selected = Nexvio_Filter_Query::slugs( $param );
		$items    = array();
		foreach ( $terms as $t ) {
			$items[] = array(
				'value'   => $t->slug,
				'label'   => $t->name,
				'count'   => (int) $t->count,
				'checked' => in_array( $t->slug, $selected, true ),
				'color'   => in_array( $display, array( 'color', 'image' ), true ) && class_exists( 'Nexvio_Swatches' ) ? Nexvio_Swatches::color_for( $t->name, (int) $t->term_id ) : '',
				'image'   => 'image' === $display && class_exists( 'Nexvio_Swatches' ) ? Nexvio_Swatches::image_for( (int) $t->term_id ) : '',
			);
		}
		if ( 'size' === $display && 'name' === $orderby ) {
			usort( $items, array( __CLASS__, 'size_cmp' ) ); /* XS, S, M, L, XL… instead of alphabetical */
		}
		$logic = isset( $f['f_logic'] ) && 'all' === $f['f_logic'] ? 'all' : 'any';
		$this->render_values( $f, $s, $param, $title, $open, $display, $items, ! isset( $f['f_counts'] ) || 'yes' === $f['f_counts'], $logic );
	}

	/** Natural size order. */
	public static function size_cmp( $a, $b ) {
		$order = array( 'xxxs', '3xs', 'xxs', '2xs', 'xs', 's', 'sm', 'm', 'md', 'l', 'lg', 'xl', 'xxl', '2xl', 'xxxl', '3xl', '4xl', '5xl' );
		$ka    = array_search( strtolower( trim( $a['label'] ) ), $order, true );
		$kb    = array_search( strtolower( trim( $b['label'] ) ), $order, true );
		if ( false !== $ka && false !== $kb ) {
			return $ka - $kb;
		}
		if ( is_numeric( $a['label'] ) && is_numeric( $b['label'] ) ) {
			return (float) $a['label'] <=> (float) $b['label'];
		}
		if ( false !== $ka || false !== $kb ) {
			return false !== $ka ? -1 : 1;
		}
		return strnatcasecmp( $a['label'], $b['label'] );
	}

	/** One choice (dropdown, radio or buttons) for shop settings such as sort by / per page. */
	private function render_choice( $s, $param, $title, $open, $display, $items ) {
		$this->group_open_tag( $s, $param, $title, $open, 'data-param="' . esc_attr( $param ) . '" data-kind="single"' );
		if ( 'select' === $display ) {
			echo '<div class="nexvio-pf__select"><select name="' . esc_attr( $param ) . '" aria-label="' . esc_attr( $title ) . '">';
			foreach ( $items as $it ) {
				echo '<option value="' . esc_attr( $it['value'] ) . '"' . ( $it['checked'] ? ' selected' : '' ) . '>' . esc_html( $it['label'] ) . '</option>';
			}
			echo '</select></div>';
		} else {
			$chips = 'chips' === $display;
			echo '<ul class="nexvio-pf__list' . ( $chips ? ' nexvio-pf__list--chips nexvio-pf__list--single' : '' ) . '">';
			foreach ( $items as $it ) {
				echo '<li class="nexvio-pf__item"><label class="nexvio-pf__opt' . ( $chips ? ' nexvio-pf__chip' : '' ) . '"><input type="radio" name="' . esc_attr( $param ) . '" value="' . esc_attr( $it['value'] ) . '"' . ( $it['checked'] ? ' checked' : '' ) . '>'
					. ( $chips ? '' : '<span class="nexvio-pf__mark" aria-hidden="true"></span>' ) . '<span class="nexvio-pf__name">' . esc_html( $it['label'] ) . '</span></label></li>';
			}
			echo '</ul>';
		}
		$this->group_close();
	}

	private function render_values( $f, $s, $param, $title, $open, $display, $items, $counts, $logic ) {
		$kind = in_array( $display, array( 'radio', 'select' ), true ) ? 'single' : 'multi';
		$this->group_open_tag( $s, $param, $title, $open, 'data-param="' . esc_attr( $param ) . '" data-kind="' . esc_attr( $kind ) . '" data-logic="' . esc_attr( $logic ) . '"' );
		$all_text = isset( $f['f_all_text'] ) ? $f['f_all_text'] : 'All';
		if ( 'select' === $display ) {
			echo '<div class="nexvio-pf__select"><select name="' . esc_attr( $param ) . '" aria-label="' . esc_attr( $title ) . '"><option value="">' . esc_html( $all_text ) . '</option>';
			foreach ( $items as $it ) {
				$label = $it['label'] . ( $counts && isset( $it['count'] ) ? ' (' . $it['count'] . ')' : '' );
				echo '<option value="' . esc_attr( $it['value'] ) . '"' . ( ! empty( $it['checked'] ) ? ' selected' : '' ) . '>' . esc_html( $label ) . '</option>';
			}
			echo '</select></div>';
		} else {
			$limit = isset( $f['f_limit'] ) ? (int) $f['f_limit'] : 10;
			$this->option_list( $items, $display, $param, $limit, $counts, $s['more_text'], $s['less_text'], 'radio' === $display ? $all_text : '' );
		}
		$this->group_close();
	}
}
