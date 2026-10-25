<?php
/**
 * NEXVIO Wishlist Grid - Elementor widget for the "My favorites" page.
 * Shows the visitor's wishlist products (built-in wishlist) with the same card design.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

class Nexvio_Wishlist_Grid_Widget extends Widget_Base {

	use Nexvio_Card_Trait;

	public function get_name() {
		return 'nexvio_wishlist_grid';
	}

	public function get_title() {
		return esc_html__( 'Wishlist Grid (My favorites)', 'nexvio' );
	}

	public function get_icon() {
		return 'eicon-heart';
	}

	public function get_categories() {
		return array( 'nexvio' );
	}

	public function get_keywords() {
		return array( 'nexvio', 'wishlist', 'favorites', 'grid' );
	}

	public function get_style_depends() {
		return array( 'nexvio', 'nexvio-wishlist-widgets' );
	}

	public function get_script_depends() {
		return array( 'nexvio', 'nexvio-wishlist-widgets' );
	}

	protected function register_controls() {
		$this->register_design_section();

		/* ============ TITLE ============ */
		$this->start_controls_section( 'section_title', array( 'label' => esc_html__( 'Title', 'nexvio' ) ) );
		$this->switcher( 'show_title', esc_html__( 'Show title', 'nexvio' ) );
		$this->text( 'title', esc_html__( 'Title', 'nexvio' ), 'My wishlist', array( 'condition' => array( 'show_title' => 'yes' ), 'label_block' => true ) );
		$this->add_control( 'title_tag', array(
			'label'     => esc_html__( 'HTML tag', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'h1',
			'options'   => array( 'h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3', 'div' => 'div' ),
			'condition' => array( 'show_title' => 'yes' ),
		) );
		$this->switcher( 'title_bar', esc_html__( 'Line under the title', 'nexvio' ), 'yes', array( 'condition' => array( 'show_title' => 'yes' ) ) );
		$this->switcher( 'show_count', esc_html__( 'Show number of products', 'nexvio' ), '', array( 'condition' => array( 'show_title' => 'yes' ) ) );
		$this->text( 'count_text', esc_html__( 'Count text ({n} = number)', 'nexvio' ), '{n} product(s)', array( 'condition' => array( 'show_title' => 'yes', 'show_count' => 'yes' ) ) );
		$this->end_controls_section();

		/* ============ PRODUCTS ============ */
		$this->start_controls_section( 'section_products', array( 'label' => esc_html__( 'Products', 'nexvio' ) ) );
		$this->add_control( 'products_note', array(
			'type'            => Controls_Manager::RAW_HTML,
			'raw'             => esc_html__( 'Shows the products in the visitor\'s wishlist, newest first. Clicking a heart removes the card right away. In the editor, recent products are shown as a preview.', 'nexvio' ),
			'content_classes' => 'elementor-descriptor',
		) );
		$this->add_control( 'limit', array(
			'label'       => esc_html__( 'Maximum products (0 = all)', 'nexvio' ),
			'type'        => Controls_Manager::NUMBER,
			'min'         => 0,
			'max'         => 200,
			'default'     => 0,
		) );
		$this->switcher( 'hide_out', esc_html__( 'Hide sold-out products', 'nexvio' ), '' );
		$this->switcher( 'live_remove', esc_html__( 'Remove the card when the heart is clicked', 'nexvio' ) );
		$this->add_control( 'preview_count', array(
			'label'     => esc_html__( 'Editor preview: number of products', 'nexvio' ),
			'type'      => Controls_Manager::NUMBER,
			'min'       => 0,
			'max'       => 20,
			'default'   => 1,
			'separator' => 'before',
		) );
		$this->end_controls_section();

		/* ============ EMPTY ============ */
		$this->start_controls_section( 'section_empty', array( 'label' => esc_html__( 'Empty wishlist', 'nexvio' ) ) );
		$this->add_control( 'empty_text', array(
			'label'   => esc_html__( 'Message', 'nexvio' ),
			'type'    => Controls_Manager::TEXTAREA,
			'rows'    => 2,
			'default' => 'Your wishlist is empty. Tap the heart on a product to save it here.',
		) );
		$this->switcher( 'empty_btn', esc_html__( 'Show button', 'nexvio' ) );
		$this->text( 'empty_btn_text', esc_html__( 'Button text', 'nexvio' ), 'Browse the shop', array( 'condition' => array( 'empty_btn' => 'yes' ) ) );
		$this->add_control( 'empty_btn_link', array(
			'label'       => esc_html__( 'Button link', 'nexvio' ),
			'type'        => Controls_Manager::URL,
			'default'     => array( 'url' => '' ),
			'description' => esc_html__( 'Empty = WooCommerce shop page.', 'nexvio' ),
			'condition'   => array( 'empty_btn' => 'yes' ),
		) );
		$this->end_controls_section();

		$this->register_card_sections();

		/* "New" badge: keep but off (same as other widgets) */

		/* ============ LAYOUT ============ */
		$this->start_controls_section( 'section_layout', array( 'label' => esc_html__( 'Grid layout', 'nexvio' ) ) );
		$this->add_responsive_control( 'columns', array(
			'label'          => esc_html__( 'Columns', 'nexvio' ),
			'type'           => Controls_Manager::NUMBER,
			'min'            => 1,
			'max'            => 8,
			'default'        => 5,
			'tablet_default' => 3,
			'mobile_default' => 2,
			'selectors'      => array( '{{WRAPPER}} .nexvio-pg' => '--nexvio-pg-cols: {{VALUE}};' ),
		) );
		$this->add_responsive_control( 'col_gap', array(
			'label'          => esc_html__( 'Column gap', 'nexvio' ),
			'type'           => Controls_Manager::SLIDER,
			'size_units'     => array( 'px' ),
			'range'          => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
			'default'        => array( 'size' => 18, 'unit' => 'px' ),
			'mobile_default' => array( 'size' => 10, 'unit' => 'px' ),
			'selectors'      => array( '{{WRAPPER}} .nexvio-pg' => '--nexvio-pg-col-gap: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'row_gap', array(
			'label'          => esc_html__( 'Row gap', 'nexvio' ),
			'type'           => Controls_Manager::SLIDER,
			'size_units'     => array( 'px' ),
			'range'          => array( 'px' => array( 'min' => 0, 'max' => 80 ) ),
			'default'        => array( 'size' => 22, 'unit' => 'px' ),
			'mobile_default' => array( 'size' => 12, 'unit' => 'px' ),
			'selectors'      => array( '{{WRAPPER}} .nexvio-pg' => '--nexvio-pg-row-gap: {{SIZE}}{{UNIT}};' ),
		) );
		$this->end_controls_section();

		/* ============ STYLE: TITLE ============ */
		$this->start_controls_section( 'style_title', array( 'label' => esc_html__( 'Title', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE, 'condition' => array( 'show_title' => 'yes' ) ) );
		$this->add_control( 't_color', array( 'label' => esc_html__( 'Colour', 'nexvio' ), 'type' => Controls_Manager::COLOR, 'default' => '#e8edf4', 'selectors' => array( '{{WRAPPER}} .nexvio-wlg__title' => 'color: {{VALUE}};' ) ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 't_typo', 'selector' => '{{WRAPPER}} .nexvio-wlg__title' ) );
		$this->add_control( 't_count_color', array( 'label' => esc_html__( 'Count colour', 'nexvio' ), 'type' => Controls_Manager::COLOR, 'default' => '#8f9aab', 'selectors' => array( '{{WRAPPER}} .nexvio-wlg__count' => 'color: {{VALUE}};' ) ) );
		$this->add_control( 't_line', array( 'label' => esc_html__( 'Line colour', 'nexvio' ), 'type' => Controls_Manager::COLOR, 'default' => '#313945', 'selectors' => array( '{{WRAPPER}} .nexvio-wlg__head.has-line' => 'border-bottom-color: {{VALUE}};' ) ) );
		$this->add_responsive_control( 't_pad', array(
			'label'      => esc_html__( 'Title area padding', 'nexvio' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px' ),
			'default'    => array( 'top' => 46, 'right' => 0, 'bottom' => 34, 'left' => 0, 'unit' => 'px', 'isLinked' => false ),
			'selectors'  => array( '{{WRAPPER}} .nexvio-wlg__head' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 't_space', array(
			'label'      => esc_html__( 'Space below title area', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 120 ) ),
			'default'    => array( 'size' => 36, 'unit' => 'px' ),
			'selectors'  => array( '{{WRAPPER}} .nexvio-wlg__head' => 'margin-bottom: {{SIZE}}{{UNIT}};' ),
		) );
		$this->end_controls_section();

		$this->register_layout_style_section();
		$this->register_card_style_sections();

		/* ============ STYLE: EMPTY ============ */
		$this->start_controls_section( 'style_empty', array( 'label' => esc_html__( 'Empty wishlist', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_control( 'e_color', array( 'label' => esc_html__( 'Text', 'nexvio' ), 'type' => Controls_Manager::COLOR, 'default' => '#8f9aab', 'selectors' => array( '{{WRAPPER}} .nexvio-wlg__empty' => 'color: {{VALUE}};' ) ) );
		$this->add_control( 'e_border', array( 'label' => esc_html__( 'Border', 'nexvio' ), 'type' => Controls_Manager::COLOR, 'default' => '#424b58', 'selectors' => array( '{{WRAPPER}} .nexvio-wlg__empty' => 'border-color: {{VALUE}};' ) ) );
		$this->add_control( 'e_btn_bg', array( 'label' => esc_html__( 'Button background', 'nexvio' ), 'type' => Controls_Manager::COLOR, 'default' => '#3d8bff', 'selectors' => array( '{{WRAPPER}} .nexvio-wlg__btn' => 'background: {{VALUE}};' ) ) );
		$this->add_control( 'e_btn_bg_h', array( 'label' => esc_html__( 'Button background on hover', 'nexvio' ), 'type' => Controls_Manager::COLOR, 'default' => '#5a9dff', 'selectors' => array( '{{WRAPPER}} .nexvio-wlg__btn:hover' => 'background: {{VALUE}};' ) ) );
		$this->add_control( 'e_btn_c', array( 'label' => esc_html__( 'Button text', 'nexvio' ), 'type' => Controls_Manager::COLOR, 'default' => '#ffffff', 'selectors' => array( '{{WRAPPER}} .nexvio-wlg__btn, {{WRAPPER}} .nexvio-wlg__btn:hover' => 'color: {{VALUE}};' ) ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'e_typo', 'selector' => '{{WRAPPER}} .nexvio-wlg__empty p' ) );
		$this->end_controls_section();
		$this->limit_classic_controls();
	}

	private function is_edit() {
		return class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode();
	}

	protected function render() {
		$s       = $this->get_settings_for_display();
		$is_edit = $this->is_edit();

		$ids = Nexvio_Wishlist::ids();
		if ( ! $ids && $is_edit && (int) $s['preview_count'] > 0 ) {
			$ids = get_posts( array( 'post_type' => 'product', 'post_status' => 'publish', 'numberposts' => (int) $s['preview_count'], 'fields' => 'ids' ) );
		}

		$products = array();
		foreach ( $ids as $id ) {
			$p = wc_get_product( $id );
			if ( ! $p || 'publish' !== $p->get_status() ) {
				continue;
			}
			if ( 'yes' === $s['hide_out'] && ! $p->is_in_stock() ) {
				continue;
			}
			$products[] = $p;
			if ( (int) $s['limit'] > 0 && count( $products ) >= (int) $s['limit'] ) {
				break;
			}
		}

		if ( wp_script_is( 'wc-add-to-cart', 'registered' ) ) {
			wp_enqueue_script( 'wc-add-to-cart' );
		}

		$classes   = array( 'nexvio-ps', 'nexvio-pg', 'nexvio-wlg' );
		$classes[] = 'cover' === $s['image_mode'] ? 'nexvio-ps--cover' : 'nexvio-ps--contain';
		if ( 'yes' !== $s['pattern'] ) {
			$classes[] = 'nexvio-ps--no-pattern';
		}
		if ( 'yes' === $s['frame'] ) {
			$classes[] = 'nexvio-ps--frame';
		}
		if ( 'yes' === $s['img_shadow'] ) {
			$classes[] = 'nexvio-ps--shadow';
		}
		if ( in_array( $s['hover_fx'], array( 'lift', 'zoom' ), true ) ) {
			$classes[] = 'nexvio-ps--hover-' . $s['hover_fx'];
		}
		if ( 'yes' === $s['live_remove'] ) {
			$classes[] = 'nexvio-wlg--live';
		}

		$layout_classes = $this->layout_classes( $s );
		if ( in_array( 'nexvio-ps--l', $layout_classes, true ) ) {
			/* classic-only looks (gold frame, hexagon pattern, tilt) do not apply to the new designs */
			$classes = array_values( array_diff( $classes, array( 'nexvio-ps--contain', 'nexvio-ps--cover', 'nexvio-ps--no-pattern', 'nexvio-ps--frame', 'nexvio-ps--shadow', 'nexvio-ps--hover-lift', 'nexvio-ps--hover-zoom' ) ) );
		}
		$classes = array_merge( $classes, $layout_classes );

		$rules = is_array( $s['color_rules'] ) ? $s['color_rules'] : array();
		$js    = array( 'wish' => $this->wish_settings( $s ) );
		$tag   = in_array( $s['title_tag'], array( 'h1', 'h2', 'h3', 'div' ), true ) ? $s['title_tag'] : 'h1';
		$cols  = max( 1, (int) $s['columns'] );

		$btn_url = ! empty( $s['empty_btn_link']['url'] ) ? $s['empty_btn_link']['url'] : ( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ) );
		$count_t = (string) $s['count_text'];
		?>
		<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-settings="<?php echo esc_attr( wp_json_encode( $js ) ); ?>" data-count-text="<?php echo esc_attr( $count_t ); ?>">
			<?php if ( 'yes' === $s['show_title'] ) : ?>
				<div class="nexvio-wlg__head<?php echo 'yes' === $s['title_bar'] ? ' has-line' : ''; ?>">
					<<?php echo esc_html( $tag ); ?> class="nexvio-wlg__title"><?php echo esc_html( $s['title'] ); ?></<?php echo esc_html( $tag ); ?>>
					<?php if ( 'yes' === $s['show_count'] ) : ?>
						<span class="nexvio-wlg__count"><?php echo esc_html( str_replace( '{n}', (string) count( $products ), $count_t ) ); ?></span>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php if ( $is_edit && $products && ! Nexvio_Wishlist::ids() ) : ?>
				<p class="nexvio-pg__preview-note"><?php esc_html_e( 'Editor preview. Visitors see the products in their own wishlist.', 'nexvio' ); ?></p>
			<?php endif; ?>

			<ul class="products nexvio-pg__grid columns-<?php echo esc_attr( $cols ); ?>"<?php echo $products ? '' : ' hidden'; ?>>
				<?php
				foreach ( $products as $product ) {
					$this->render_card( $product, $s, $rules, 'li' );
				}
				?>
			</ul>

			<div class="nexvio-wlg__empty"<?php echo $products ? ' hidden' : ''; ?>>
				<p><?php echo esc_html( $s['empty_text'] ); ?></p>
				<?php if ( 'yes' === $s['empty_btn'] ) : ?>
					<a class="nexvio-wlg__btn" href="<?php echo esc_url( $btn_url ); ?>"><?php echo esc_html( $s['empty_btn_text'] ); ?></a>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}
