<?php
/**
 * NEXVIO Product Category Grid - Elementor widget.
 *
 * A responsive grid of WooCommerce product categories (image, name, product count).
 *  - Choose which categories: all, top level only, children of one category, or hand-picked.
 *  - 3 designs: Card, Image overlay and Circle.
 *  - 3 main colours (background, heading, text) drive the whole widget.
 *
 * @package Nexvio_For_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

class Nexvio_Category_Grid_Widget extends Widget_Base {

	public function get_name() {
		return 'nexvio_category_grid';
	}

	public function get_title() {
		return esc_html__( 'Product Category Grid', 'nexvio' );
	}

	public function get_icon() {
		return 'eicon-product-categories';
	}

	public function get_categories() {
		return array( 'nexvio' );
	}

	public function get_keywords() {
		return array( 'nexvio', 'woocommerce', 'category', 'categories', 'product category', 'grid', 'shop' );
	}

	public function get_style_depends() {
		return array( 'nexvio-layout', 'nexvio-category-grid' );
	}

	protected function is_dynamic_content(): bool {
		return true;
	}

	private function var_color( $id, $label, $var, $default = '' ) {
		$this->add_control( $id, array(
			'label'     => $label,
			'type'      => Controls_Manager::COLOR,
			'default'   => $default,
			'selectors' => array( '{{WRAPPER}} .nxcg' => $var . ': {{VALUE}};' ),
		) );
	}

	/** id => name list of product categories (editor only). */
	private function category_options( $parent = null ) {
		$args = array( 'taxonomy' => 'product_cat', 'hide_empty' => false, 'orderby' => 'name' );
		if ( null !== $parent ) {
			$args['parent'] = $parent;
		}
		$terms = taxonomy_exists( 'product_cat' ) ? get_terms( $args ) : array();
		$out   = array();
		if ( is_array( $terms ) ) {
			foreach ( $terms as $t ) {
				$out[ $t->term_id ] = $t->name;
			}
		}
		return $out;
	}

	/* ---- design hooks (the "Layouts" widgets override these) ---- */

	protected function add_design_control() {
		$this->add_control( 'design', array(
			'label'   => esc_html__( 'Design', 'nexvio' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'card',
			'options' => array(
				'card'    => esc_html__( 'Card (image, then name)', 'nexvio' ),
				'overlay' => esc_html__( 'Image with name on top', 'nexvio' ),
				'circle'  => esc_html__( 'Round image', 'nexvio' ),
			),
		) );
	}

	/** Designs that can show the description and a button. */
	protected function text_designs() {
		return array( 'card' );
	}

	/** Designs whose image shape is fixed (no "Image shape" control). */
	protected function no_ratio_designs() {
		return array( 'circle' );
	}

	/** Designs with a dark overlay on the image. */
	protected function overlay_designs() {
		return array( 'overlay' );
	}

	/** Add the number and arrow elements to every card? */
	protected function rich_cards() {
		return false;
	}

	protected function root_class( $s ) {
		return 'nxcg nxcg--' . $this->design( $s ) . ( 'yes' === $s['hover_zoom'] ? ' has-zoom' : '' );
	}

	protected function register_controls() {

		/* ============ CONTENT: CATEGORIES ============ */
		$this->start_controls_section( 'section_cats', array( 'label' => esc_html__( 'Categories', 'nexvio' ) ) );
		$all = $this->category_options();
		$this->add_control( 'source', array(
			'label'   => esc_html__( 'Show', 'nexvio' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'top',
			'options' => array(
				'top'      => esc_html__( 'Main categories (no parent)', 'nexvio' ),
				'all'      => esc_html__( 'All categories', 'nexvio' ),
				'children' => esc_html__( 'Sub-categories of one category', 'nexvio' ),
				'pick'     => esc_html__( 'Hand-picked categories', 'nexvio' ),
			),
		) );
		$this->add_control( 'parent', array(
			'label'     => esc_html__( 'Parent category', 'nexvio' ),
			'type'      => Controls_Manager::SELECT2,
			'options'   => $all,
			'default'   => '',
			'condition' => array( 'source' => 'children' ),
		) );
		$this->add_control( 'picked', array(
			'label'       => esc_html__( 'Categories', 'nexvio' ),
			'type'        => Controls_Manager::SELECT2,
			'options'     => $all,
			'multiple'    => true,
			'label_block' => true,
			'description' => esc_html__( 'They are shown in the order you pick them.', 'nexvio' ),
			'condition'   => array( 'source' => 'pick' ),
		) );
		$this->add_control( 'exclude', array(
			'label'       => esc_html__( 'Hide these categories', 'nexvio' ),
			'type'        => Controls_Manager::SELECT2,
			'options'     => $all,
			'multiple'    => true,
			'label_block' => true,
			'condition'   => array( 'source!' => 'pick' ),
		) );
		$this->add_control( 'hide_empty', array(
			'label'        => esc_html__( 'Hide categories without products', 'nexvio' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		) );
		$this->add_control( 'hide_uncat', array(
			'label'        => esc_html__( 'Hide "Uncategorized"', 'nexvio' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => '',
			'condition'    => array( 'source!' => 'pick' ),
		) );
		$this->add_control( 'orderby', array(
			'label'     => esc_html__( 'Order by', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'menu_order',
			'options'   => array(
				'menu_order' => esc_html__( 'Shop order (drag and drop in Products > Categories)', 'nexvio' ),
				'name'       => esc_html__( 'Name', 'nexvio' ),
				'count'      => esc_html__( 'Number of products', 'nexvio' ),
				'id'         => esc_html__( 'Newest first', 'nexvio' ),
			),
			'condition' => array( 'source!' => 'pick' ),
		) );
		$this->add_control( 'order', array(
			'label'     => esc_html__( 'Direction', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'ASC',
			'options'   => array( 'ASC' => esc_html__( 'Ascending', 'nexvio' ), 'DESC' => esc_html__( 'Descending', 'nexvio' ) ),
			'condition' => array( 'source!' => 'pick', 'orderby!' => 'menu_order' ),
		) );
		$this->add_control( 'limit', array(
			'label'       => esc_html__( 'Maximum categories (0 = all)', 'nexvio' ),
			'type'        => Controls_Manager::NUMBER,
			'min'         => 0,
			'max'         => 100,
			'default'     => 0,
		) );
		$this->end_controls_section();

		/* ============ CONTENT: LAYOUT ============ */
		$this->start_controls_section( 'section_layout', array( 'label' => esc_html__( 'Layout', 'nexvio' ) ) );
		$this->add_design_control();
		$this->add_responsive_control( 'columns', array(
			'label'           => esc_html__( 'Columns', 'nexvio' ),
			'type'            => Controls_Manager::SELECT,
			'default'         => '4',
			'tablet_default'  => '3',
			'mobile_default'  => '2',
			'options'         => array( '1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6' ),
			'selectors'       => array( '{{WRAPPER}} .nxcg' => '--nxcg-cols: {{VALUE}};' ),
		) );
		$this->add_responsive_control( 'gap', array(
			'label'      => esc_html__( 'Space between', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 80 ) ),
			'default'    => array( 'size' => 24, 'unit' => 'px' ),
			'tablet_default' => array( 'size' => 18, 'unit' => 'px' ),
			'mobile_default' => array( 'size' => 12, 'unit' => 'px' ),
			'selectors'  => array( '{{WRAPPER}} .nxcg' => '--nxcg-gap: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_control( 'ratio', array(
			'label'     => esc_html__( 'Image shape', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => '1/1',
			'options'   => array(
				'1/1'  => esc_html__( 'Square', 'nexvio' ),
				'4/5'  => esc_html__( 'Portrait 4:5', 'nexvio' ),
				'3/4'  => esc_html__( 'Portrait 3:4', 'nexvio' ),
				'4/3'  => esc_html__( 'Landscape 4:3', 'nexvio' ),
				'16/9' => esc_html__( 'Wide 16:9', 'nexvio' ),
			),
			'selectors' => array( '{{WRAPPER}} .nxcg' => '--nxcg-ratio: {{VALUE}};' ),
			'condition' => array( 'design!' => $this->no_ratio_designs() ),
		) );
		$this->add_control( 'align', array(
			'label'     => esc_html__( 'Text alignment', 'nexvio' ),
			'type'      => Controls_Manager::CHOOSE,
			'options'   => array(
				'left'   => array( 'title' => esc_html__( 'Left', 'nexvio' ), 'icon' => 'eicon-text-align-left' ),
				'center' => array( 'title' => esc_html__( 'Center', 'nexvio' ), 'icon' => 'eicon-text-align-center' ),
				'right'  => array( 'title' => esc_html__( 'Right', 'nexvio' ), 'icon' => 'eicon-text-align-right' ),
			),
			'default'   => 'center',
			'toggle'    => false,
			'selectors' => array( '{{WRAPPER}} .nxcg' => '--nxcg-align: {{VALUE}};' ),
		) );
		$this->add_control( 'show_count', array(
			'label'        => esc_html__( 'Show the number of products', 'nexvio' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
			'separator'    => 'before',
		) );
		$this->add_control( 'count_text', array(
			'label'       => esc_html__( 'Count text', 'nexvio' ),
			'type'        => Controls_Manager::TEXT,
			'default'     => '{count} products',
			'description' => esc_html__( 'Use {count} for the number.', 'nexvio' ),
			'condition'   => array( 'show_count' => 'yes' ),
		) );
		$this->add_control( 'show_desc', array(
			'label'        => esc_html__( 'Show the category description', 'nexvio' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => '',
			'condition'    => array( 'design' => $this->text_designs() ),
		) );
		$this->add_control( 'show_btn', array(
			'label'        => esc_html__( 'Show a button', 'nexvio' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => '',
			'condition'    => array( 'design' => $this->text_designs() ),
		) );
		$this->add_control( 'btn_text', array(
			'label'     => esc_html__( 'Button text', 'nexvio' ),
			'type'      => Controls_Manager::TEXT,
			'default'   => 'Shop now',
			'condition' => array( 'design' => $this->text_designs(), 'show_btn' => 'yes' ),
		) );
		$this->add_control( 'empty_text', array(
			'label'     => esc_html__( 'Text when there is no category', 'nexvio' ),
			'type'      => Controls_Manager::TEXT,
			'default'   => 'No categories found.',
			'separator' => 'before',
		) );
		$this->end_controls_section();

		/* ============ STYLE: COLOURS ============ */
		$this->start_controls_section( 'style_colors', array( 'label' => esc_html__( 'Colours (main)', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_control( 'colors_note', array(
			'type'            => Controls_Manager::RAW_HTML,
			'raw'             => esc_html__( 'Change these 3 colours and the whole grid follows. The other style sections are only for fine tuning.', 'nexvio' ),
			'content_classes' => 'elementor-descriptor',
		) );
		$this->var_color( 'c_bg', esc_html__( 'Background colour', 'nexvio' ), '--nxcg-bg', '#ffffff' );
		$this->var_color( 'c_head', esc_html__( 'Heading colour (names, button)', 'nexvio' ), '--nxcg-head', '#111827' );
		$this->var_color( 'c_body', esc_html__( 'Text colour', 'nexvio' ), '--nxcg-body', '#4b5563' );
		$this->end_controls_section();

		/* ============ STYLE: CARD ============ */
		$this->start_controls_section( 'style_card', array( 'label' => esc_html__( 'Card', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_group_control( Group_Control_Border::get_type(), array(
			'name'     => 'card_border',
			'selector' => '{{WRAPPER}} .nxcg__card',
		) );
		$this->add_responsive_control( 'card_radius', array(
			'label'      => esc_html__( 'Corner radius', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
			'default'    => array( 'size' => 14, 'unit' => 'px' ),
			'selectors'  => array( '{{WRAPPER}} .nxcg' => '--nxcg-r: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array(
			'name'     => 'card_shadow',
			'selector' => '{{WRAPPER}} .nxcg__card',
		) );
		$this->add_responsive_control( 'card_pad', array(
			'label'      => esc_html__( 'Text padding', 'nexvio' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em' ),
			'selectors'  => array( '{{WRAPPER}} .nxcg__body' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );
		$this->add_control( 'hover_zoom', array(
			'label'        => esc_html__( 'Image zoom on hover', 'nexvio' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		) );
		$this->end_controls_section();

		/* ============ STYLE: TEXT ============ */
		$this->start_controls_section( 'style_text', array( 'label' => esc_html__( 'Text', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'typo_name',
			'label'    => esc_html__( 'Category name', 'nexvio' ),
			'selector' => '{{WRAPPER}} .nxcg__name',
		) );
		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'typo_count',
			'label'    => esc_html__( 'Product count / description', 'nexvio' ),
			'selector' => '{{WRAPPER}} .nxcg__count, {{WRAPPER}} .nxcg__desc',
		) );
		$this->add_control( 'overlay_color', array(
			'label'     => esc_html__( 'Overlay darkness (image design)', 'nexvio' ),
			'type'      => Controls_Manager::SLIDER,
			'range'     => array( 'px' => array( 'min' => 0, 'max' => 90 ) ),
			'default'   => array( 'size' => 55, 'unit' => 'px' ),
			'selectors' => array( '{{WRAPPER}} .nxcg' => '--nxcg-shade: {{SIZE}}%;' ),
			'condition' => array( 'design' => $this->overlay_designs() ),
		) );
		$this->end_controls_section();
	}

	/* ------------------------------------------------------------------
	 * Render
	 * ------------------------------------------------------------------ */

	protected function get_categories_list( $s ) {
		if ( ! taxonomy_exists( 'product_cat' ) ) {
			return array();
		}
		$args = array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => 'yes' === $s['hide_empty'],
		);
		$limit = max( 0, (int) $s['limit'] );

		if ( 'pick' === $s['source'] ) {
			$ids = array_filter( array_map( 'absint', (array) $s['picked'] ) );
			if ( ! $ids ) {
				return array();
			}
			$args['include']    = $ids;
			$args['orderby']    = 'include';
			$args['hide_empty'] = false;
		} else {
			$exclude   = array_filter( array_map( 'absint', (array) $s['exclude'] ) );
			if ( 'yes' === $s['hide_uncat'] ) {
				$exclude[] = (int) get_option( 'default_product_cat' ); // "Uncategorized".
			}
			$args['exclude'] = array_values( array_unique( array_filter( $exclude ) ) ); // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- this is a term query (get_terms), not a post query.
			if ( 'top' === $s['source'] ) {
				$args['parent'] = 0;
			} elseif ( 'children' === $s['source'] ) {
				$args['parent'] = absint( $s['parent'] );
				if ( ! $args['parent'] ) {
					return array();
				}
			}
			$orderby = in_array( $s['orderby'], array( 'menu_order', 'name', 'count', 'id' ), true ) ? $s['orderby'] : 'menu_order';
			if ( 'menu_order' === $orderby ) {
				$args['menu_order'] = 'ASC';
				$args['orderby']    = 'menu_order';
				$args['order']      = 'ASC';
			} else {
				$args['orderby'] = $orderby;
				$args['order']   = 'DESC' === $s['order'] ? 'DESC' : 'ASC';
			}
		}
		if ( $limit ) {
			$args['number'] = $limit;
		}
		$terms = get_terms( $args );
		return is_wp_error( $terms ) ? array() : $terms;
	}

	protected function render() {
		$s     = $this->get_settings_for_display();
		$terms = $this->get_categories_list( $s );
		if ( ! $terms ) {
			$this->render_empty( $s );
			return;
		}
		echo '<div class="' . esc_attr( $this->root_class( $s ) ) . '">';
		$this->render_cards( $terms, $s );
		echo '</div>';
	}

	protected function render_empty( $s ) {
		$text = '' !== trim( (string) $s['empty_text'] ) ? $s['empty_text'] : __( 'No categories found.', 'nexvio' );
		echo '<p class="nxcg-empty">' . esc_html( $text ) . '</p>';
	}

	protected function design( $s ) {
		return in_array( $s['design'], array( 'card', 'overlay', 'circle' ), true ) ? $s['design'] : 'card';
	}

	/** The category cards (shared by the grid and the slider). */
	protected function render_cards( $terms, $s, $item_class = '' ) {
		$design = $this->design( $s );
		$text   = in_array( $design, $this->text_designs(), true );
		$desc   = $text && 'yes' === $s['show_desc'];
		$btn    = $text && 'yes' === $s['show_btn'];
		$rich   = $this->rich_cards();
		$n      = 0;
		foreach ( $terms as $term ) {
			++$n;
			$link = get_term_link( $term );
			if ( is_wp_error( $link ) ) {
				continue;
			}
			$thumb = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
			$img   = $thumb ? wp_get_attachment_image( $thumb, 'woocommerce_single', false, array( 'class' => 'nxcg__img', 'loading' => 'lazy', 'alt' => $term->name ) ) : '';
			if ( ! $img ) {
				$ph  = function_exists( 'wc_placeholder_img_src' ) ? wc_placeholder_img_src( 'woocommerce_single' ) : '';
				$img = $ph ? '<img class="nxcg__img" src="' . esc_url( $ph ) . '" alt="' . esc_attr( $term->name ) . '" loading="lazy">' : '';
			}
			?>
			<a class="nxcg__card<?php echo $item_class ? ' ' . esc_attr( $item_class ) : ''; ?>" href="<?php echo esc_url( $link ); ?>" draggable="false">
				<span class="nxcg__media"><?php echo $img; // phpcs:ignore WordPress.Security.EscapeOutput -- core image markup. ?></span>
				<?php if ( $rich ) : ?>
					<span class="nxcg__num" aria-hidden="true"><?php echo esc_html( sprintf( '%02d', $n ) ); ?></span>
				<?php endif; ?>
				<span class="nxcg__body">
					<span class="nxcg__name"><?php echo esc_html( $term->name ); ?></span>
					<?php if ( 'yes' === $s['show_count'] ) : ?>
						<span class="nxcg__count"><?php echo esc_html( str_replace( '{count}', number_format_i18n( (int) $term->count ), (string) $s['count_text'] ) ); ?></span>
					<?php endif; ?>
					<?php if ( $desc && '' !== trim( wp_strip_all_tags( $term->description ) ) ) : ?>
						<span class="nxcg__desc"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $term->description ), 18 ) ); ?></span>
					<?php endif; ?>
					<?php if ( $btn && '' !== trim( (string) $s['btn_text'] ) ) : ?>
						<span class="nxcg__btn"><?php echo esc_html( $s['btn_text'] ); ?></span>
					<?php endif; ?>
					<?php if ( $rich ) : ?>
						<span class="nxcg__arrow" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5 12h14m-6-6 6 6-6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
					<?php endif; ?>
				</span>
			</a>
			<?php
		}
	}
}
