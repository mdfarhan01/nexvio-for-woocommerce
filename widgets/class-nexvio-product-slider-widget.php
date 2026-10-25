<?php
/**
 * NEXVIO Product Slider - Elementor widget.
 *
 * WooCommerce product carousel in the Nexvio card style.
 * Query, slider behaviour, badges, texts and colours are all controlled
 * from the Elementor panel.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;
use Elementor\Group_Control_Typography;

class Nexvio_Product_Slider_Widget extends Widget_Base {

	use Nexvio_Card_Trait;

	public function get_name() {
		return 'nexvio_product_slider';
	}

	public function get_title() {
		return esc_html__( 'Product Slider', 'nexvio' );
	}

	public function get_icon() {
		return 'eicon-products';
	}

	public function get_categories() {
		return array( 'nexvio' );
	}

	public function get_keywords() {
		return array( 'nexvio', 'woocommerce', 'product', 'slider', 'carousel', 'singles', 'cards' );
	}

	public function get_style_depends() {
		return array( 'nexvio' );
	}

	public function get_script_depends() {
		return array( 'nexvio' );
	}

	/* ------------------------------------------------------------------
	 * Controls
	 * ------------------------------------------------------------------ */

	protected function register_controls() {
		$this->register_design_section();
		$this->register_header_section();
		$this->register_query_section();
		$this->register_card_sections();
		$this->register_slider_section();
		$this->register_layout_style_section();
		$this->register_header_style_section();
		$this->register_card_style_sections();
		$this->register_nav_style_section();
		$this->limit_classic_controls();
	}

	protected function register_header_section() {
		/* ============ HEADER ============ */
		$this->start_controls_section( 'section_header', array( 'label' => esc_html__( 'Header', 'nexvio' ) ) );
		$this->switcher( 'show_header', esc_html__( 'Show header', 'nexvio' ) );
		$this->text( 'title', esc_html__( 'Title', 'nexvio' ), 'Featured products', array( 'condition' => array( 'show_header' => 'yes' ), 'label_block' => true ) );
		$this->add_control( 'title_tag', array(
			'label'     => esc_html__( 'Title HTML tag', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'h2',
			'options'   => array( 'h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4', 'div' => 'div' ),
			'condition' => array( 'show_header' => 'yes' ),
		) );
		$this->add_control( 'subtitle', array(
			'label'     => esc_html__( 'Subtitle', 'nexvio' ),
			'type'      => Controls_Manager::TEXTAREA,
			'rows'      => 2,
			'default'   => 'Hand-picked products, carefully checked and shipped with care.',
			'condition' => array( 'show_header' => 'yes' ),
		) );
		$this->switcher( 'show_all', esc_html__( 'Show "view all" link', 'nexvio' ), 'yes', array( 'condition' => array( 'show_header' => 'yes' ) ) );
		$this->text( 'all_text', esc_html__( 'Link text', 'nexvio' ), 'View all', array( 'condition' => array( 'show_header' => 'yes', 'show_all' => 'yes' ) ) );
		$this->add_control( 'all_link', array(
			'label'     => esc_html__( 'Link URL', 'nexvio' ),
			'type'      => Controls_Manager::URL,
			'default'   => array( 'url' => '/shop/' ),
			'condition' => array( 'show_header' => 'yes', 'show_all' => 'yes' ),
		) );
		$this->end_controls_section();
	}

	protected function register_slider_section( $section_args = array() ) {
		/* ============ SLIDER ============ */
		$this->start_controls_section( 'section_slider', array_merge( array( 'label' => esc_html__( 'Slider', 'nexvio' ) ), $section_args ) );
		$this->add_responsive_control( 'spv', array(
			'label'          => esc_html__( 'Cards visible', 'nexvio' ),
			'type'           => Controls_Manager::NUMBER,
			'min'            => 1,
			'max'            => 8,
			'step'           => 1,
			'default'        => 5,
			'tablet_default' => 3,
			'mobile_default' => 1,
			'selectors'      => array( '{{WRAPPER}} .nexvio-ps' => '--nexvio-ps-spv: {{VALUE}};' ),
		) );
		$this->add_responsive_control( 'peek', array(
			'label'          => esc_html__( 'Peek of next card', 'nexvio' ),
			'type'           => Controls_Manager::NUMBER,
			'min'            => 0,
			'max'            => 0.9,
			'step'           => 0.05,
			'default'        => 0.15,
			'tablet_default' => 0.2,
			'mobile_default' => 0.35,
			'description'    => esc_html__( '0 = no partial card. 0.15 = 15% of the next card shows.', 'nexvio' ),
			'selectors'      => array( '{{WRAPPER}} .nexvio-ps' => '--nexvio-ps-peek: {{VALUE}};' ),
		) );
		$this->add_responsive_control( 'gap', array(
			'label'      => esc_html__( 'Space between cards', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
			'default'    => array( 'size' => 18, 'unit' => 'px' ),
			'mobile_default' => array( 'size' => 12, 'unit' => 'px' ),
			'selectors'  => array( '{{WRAPPER}} .nexvio-ps' => '--nexvio-ps-gap: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_control( 'scroll_mode', array(
			'label'       => esc_html__( 'Motion', 'nexvio' ),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'slide',
			'options'     => array(
				'slide'   => esc_html__( 'Slide (arrows, swipe, optional autoplay)', 'nexvio' ),
				'marquee' => esc_html__( 'Continuous infinite scroll', 'nexvio' ),
			),
			'separator'   => 'before',
			'description' => esc_html__( 'Continuous: products move at a steady speed in an endless loop, with no jump when it repeats.', 'nexvio' ),
		) );
		$this->add_responsive_control( 'marquee_speed', array(
			'label'          => esc_html__( 'Speed (pixels per second)', 'nexvio' ),
			'type'           => Controls_Manager::NUMBER,
			'min'            => 5,
			'max'            => 400,
			'step'           => 5,
			'default'        => 40,
			'tablet_default' => 35,
			'mobile_default' => 30,
			'condition'      => array( 'scroll_mode' => 'marquee' ),
		) );
		$this->add_control( 'marquee_dir', array(
			'label'     => esc_html__( 'Direction', 'nexvio' ),
			'type'      => Controls_Manager::CHOOSE,
			'default'   => 'left',
			'options'   => array(
				'left'  => array( 'title' => esc_html__( 'Right to left', 'nexvio' ), 'icon' => 'eicon-arrow-left' ),
				'right' => array( 'title' => esc_html__( 'Left to right', 'nexvio' ), 'icon' => 'eicon-arrow-right' ),
			),
			'toggle'    => false,
			'condition' => array( 'scroll_mode' => 'marquee' ),
		) );
		$this->switcher( 'marquee_pause', esc_html__( 'Pause on hover', 'nexvio' ), 'yes', array( 'condition' => array( 'scroll_mode' => 'marquee' ) ) );
		$this->switcher( 'arrows', esc_html__( 'Arrows', 'nexvio' ), 'yes', array( 'separator' => 'before' ) );
		$this->add_control( 'arrows_pos', array(
			'label'     => esc_html__( 'Arrow position', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'header',
			'options'   => array( 'header' => esc_html__( 'In the header (like the design)', 'nexvio' ), 'sides' => esc_html__( 'On the sides', 'nexvio' ) ),
			'condition' => array( 'arrows' => 'yes' ),
		) );
		$this->switcher( 'dots', esc_html__( 'Dots', 'nexvio' ), '', array( 'condition' => array( 'scroll_mode' => 'slide' ) ) );
		$this->add_control( 'scroll_by', array(
			'label'   => esc_html__( 'Arrows move', 'nexvio' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'one',
			'options' => array( 'one' => esc_html__( 'One card', 'nexvio' ), 'page' => esc_html__( 'A full page', 'nexvio' ) ),
			'separator' => 'before',
			'condition' => array( 'scroll_mode' => 'slide' ),
		) );
		$this->switcher( 'loop', esc_html__( 'Loop back to start', 'nexvio' ), 'yes', array( 'condition' => array( 'scroll_mode' => 'slide' ) ) );
		$this->switcher( 'drag', esc_html__( 'Drag with mouse', 'nexvio' ), 'yes' );
		$this->switcher( 'autoplay', esc_html__( 'Autoplay', 'nexvio' ), '', array( 'separator' => 'before', 'condition' => array( 'scroll_mode' => 'slide' ) ) );
		$this->add_control( 'autoplay_speed', array(
			'label'     => esc_html__( 'Autoplay delay (ms)', 'nexvio' ),
			'type'      => Controls_Manager::NUMBER,
			'min'       => 1000,
			'max'       => 20000,
			'step'      => 250,
			'default'   => 4000,
			'condition' => array( 'autoplay' => 'yes', 'scroll_mode' => 'slide' ),
		) );
		$this->switcher( 'pause_hover', esc_html__( 'Pause on hover', 'nexvio' ), 'yes', array( 'condition' => array( 'autoplay' => 'yes', 'scroll_mode' => 'slide' ) ) );
		$this->end_controls_section();
	}

	protected function register_header_style_section() {
		/* ============ STYLE: HEADER ============ */
		$this->start_controls_section( 'style_header', array( 'label' => esc_html__( 'Header', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE, 'condition' => array( 'show_header' => 'yes' ) ) );
		$this->add_control( 'title_color', array( 'label' => esc_html__( 'Title colour', 'nexvio' ), 'type' => Controls_Manager::COLOR, 'default' => '#e8edf4', 'selectors' => array( '{{WRAPPER}} .nexvio-ps__title' => 'color: {{VALUE}};' ) ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'title_typo', 'label' => esc_html__( 'Title typography', 'nexvio' ), 'selector' => '{{WRAPPER}} .nexvio-ps__title' ) );
		$this->add_control( 'sub_color', array( 'label' => esc_html__( 'Subtitle colour', 'nexvio' ), 'type' => Controls_Manager::COLOR, 'default' => '#8f9aab', 'selectors' => array( '{{WRAPPER}} .nexvio-ps__subtitle' => 'color: {{VALUE}};' ), 'separator' => 'before' ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'sub_typo', 'label' => esc_html__( 'Subtitle typography', 'nexvio' ), 'selector' => '{{WRAPPER}} .nexvio-ps__subtitle' ) );
		$this->add_control( 'all_color', array( 'label' => esc_html__( 'Link colour', 'nexvio' ), 'type' => Controls_Manager::COLOR, 'default' => '#8cc8ff', 'selectors' => array( '{{WRAPPER}} .nexvio-ps__all' => 'color: {{VALUE}};' ), 'separator' => 'before' ) );
		$this->add_control( 'all_hover', array( 'label' => esc_html__( 'Link hover colour', 'nexvio' ), 'type' => Controls_Manager::COLOR, 'default' => '#e8edf4', 'selectors' => array( '{{WRAPPER}} .nexvio-ps__all:hover' => 'color: {{VALUE}};' ) ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'all_typo', 'label' => esc_html__( 'Link typography', 'nexvio' ), 'selector' => '{{WRAPPER}} .nexvio-ps__all' ) );
		$this->add_responsive_control( 'head_space', array(
			'label'      => esc_html__( 'Space below header', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 100 ) ),
			'default'    => array( 'size' => 28, 'unit' => 'px' ),
			'selectors'  => array( '{{WRAPPER}} .nexvio-ps__head' => 'margin-bottom: {{SIZE}}{{UNIT}};' ),
			'separator'  => 'before',
		) );
		$this->end_controls_section();
	}

	protected function register_nav_style_section( $section_args = array() ) {
		/* ============ STYLE: NAVIGATION ============ */
		$this->start_controls_section( 'style_nav', array_merge( array( 'label' => esc_html__( 'Arrows and dots', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ), $section_args ) );
		$this->color_var( 'arrow_bg', esc_html__( 'Arrow background', 'nexvio' ), '--nexvio-ps-arrow-bg', '#1f252e' );
		$this->color_var( 'arrow_border', esc_html__( 'Arrow border', 'nexvio' ), '--nexvio-ps-arrow-border', '#313945' );
		$this->color_var( 'arrow_color', esc_html__( 'Arrow icon', 'nexvio' ), '--nexvio-ps-arrow-color', '#e8edf4' );
		$this->color_var( 'arrow_hover', esc_html__( 'Arrow border on hover', 'nexvio' ), '--nexvio-ps-arrow-hover', '#3d8bff' );
		$this->color_var( 'dot_c', esc_html__( 'Dot', 'nexvio' ), '--nexvio-ps-dot', '#424b58', array( 'dots' => 'yes' ) );
		$this->color_var( 'dot_a', esc_html__( 'Active dot', 'nexvio' ), '--nexvio-ps-dot-active', '#3d8bff', array( 'dots' => 'yes' ) );
		$this->end_controls_section();
	}

	/* Hooks for child widgets (Related products). */
	protected function prepare_settings( $s ) {
		return $s;
	}

	protected function context_cats( $s ) {
		return ( 'manual' !== $s['source'] && ! empty( $s['categories'] ) ) ? (array) $s['categories'] : array();
	}

	protected function empty_message() {
		return esc_html__( 'No products match these settings. Check the query section (categories, IDs, stock).', 'nexvio' );
	}


	protected function render() {
		$s        = $this->prepare_settings( $this->get_settings_for_display() );
		$products = $this->get_products( $s );
		$is_edit  = class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode();

		if ( empty( $products ) ) {
			if ( $is_edit ) {
				echo wp_kses( '<div class="nexvio-ps"><div class="nexvio-ps__empty">' . $this->empty_message() . '</div></div>', Nexvio_Plugin::allowed_html() );
			}
			return;
		}

		if ( wp_script_is( 'wc-add-to-cart', 'registered' ) ) {
			wp_enqueue_script( 'wc-add-to-cart' );
		}

		$classes   = array( 'nexvio-ps' );
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
		$arrows      = 'yes' === $s['arrows'];
		$arrows_side = $arrows && 'sides' === $s['arrows_pos'];
		if ( $arrows_side ) {
			$classes[] = 'nexvio-ps--arrows-sides';
		}
		$layout_classes = $this->layout_classes( $s );
		if ( in_array( 'nexvio-ps--l', $layout_classes, true ) ) {
			/* classic-only looks (gold frame, hexagon pattern, tilt) do not apply to the new designs */
			$classes = array_values( array_diff( $classes, array( 'nexvio-ps--contain', 'nexvio-ps--cover', 'nexvio-ps--no-pattern', 'nexvio-ps--frame', 'nexvio-ps--shadow', 'nexvio-ps--hover-lift', 'nexvio-ps--hover-zoom' ) ) );
		}
		$classes = array_merge( $classes, $layout_classes );
		$mode    = isset( $s['scroll_mode'] ) && 'marquee' === $s['scroll_mode'] ? 'marquee' : 'slide';
		if ( 'marquee' === $mode ) {
			$classes[] = 'nexvio-ps--marquee';
		}

		$js = array(
			'loop'          => 'yes' === $s['loop'],
			'dots'          => 'yes' === $s['dots'],
			'drag'          => 'yes' === $s['drag'],
			'autoplay'      => 'yes' === $s['autoplay'],
			'autoplaySpeed' => (int) $s['autoplay_speed'],
			'scrollBy'      => 'page' === $s['scroll_by'] ? 'page' : 'one',
			'dotLabel'      => 'Slide',
			'mode'          => $mode,
			'speed'         => array(
				'desktop' => isset( $s['marquee_speed'] ) ? (float) $s['marquee_speed'] : 40,
				'tablet'  => isset( $s['marquee_speed_tablet'] ) && '' !== $s['marquee_speed_tablet'] ? (float) $s['marquee_speed_tablet'] : 0,
				'mobile'  => isset( $s['marquee_speed_mobile'] ) && '' !== $s['marquee_speed_mobile'] ? (float) $s['marquee_speed_mobile'] : 0,
			),
			'direction'     => isset( $s['marquee_dir'] ) && 'right' === $s['marquee_dir'] ? 'right' : 'left',
			'pauseOnHover'  => 'marquee' === $mode ? ( isset( $s['marquee_pause'] ) && 'yes' === $s['marquee_pause'] ) : 'yes' === $s['pause_hover'],
			'wish'          => $this->wish_settings( $s ),
		);

		$s['_ctx_cats'] = $this->context_cats( $s );
		$rules = is_array( $s['color_rules'] ) ? $s['color_rules'] : array();
		$tag   = in_array( $s['title_tag'], array( 'h1', 'h2', 'h3', 'h4', 'div' ), true ) ? $s['title_tag'] : 'h2';
		$nav   = '';
		if ( $arrows ) {
			$nav = '<div class="nexvio-ps__nav">'
				. '<button type="button" class="nexvio-ps__arrow nexvio-ps__prev" aria-label="' . esc_attr__( 'Previous', 'nexvio' ) . '">' . $this->svg( 'left' ) . '</button>'
				. '<button type="button" class="nexvio-ps__arrow nexvio-ps__next" aria-label="' . esc_attr__( 'Next', 'nexvio' ) . '">' . $this->svg( 'right' ) . '</button>'
				. '</div>';
		}
		$show_all = 'yes' === $s['show_all'] && ! empty( $s['all_link']['url'] );
		if ( $show_all ) {
			$this->add_link_attributes( 'all_link', $s['all_link'] );
			$this->add_render_attribute( 'all_link', 'class', 'nexvio-ps__all' );
		}
		?>
		<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-settings="<?php echo esc_attr( wp_json_encode( $js ) ); ?>">
			<?php if ( 'yes' === $s['show_header'] ) : ?>
				<div class="nexvio-ps__head">
					<div class="nexvio-ps__titles">
						<?php if ( '' !== trim( (string) $s['title'] ) ) : ?>
							<<?php echo esc_html( $tag ); ?> class="nexvio-ps__title"><?php echo esc_html( $s['title'] ); ?></<?php echo esc_html( $tag ); ?>>
						<?php endif; ?>
						<?php if ( '' !== trim( (string) $s['subtitle'] ) ) : ?>
							<p class="nexvio-ps__subtitle"><?php echo esc_html( $s['subtitle'] ); ?></p>
						<?php endif; ?>
					</div>
					<?php if ( $show_all || ( $arrows && ! $arrows_side ) ) : ?>
						<div class="nexvio-ps__tools">
							<?php if ( $show_all ) : ?>
								<a <?php $this->print_render_attribute_string( 'all_link' ); ?>><?php echo esc_html( $s['all_text'] ); ?></a>
							<?php endif; ?>
							<?php
							if ( ! $arrows_side ) {
								echo wp_kses( $nav, Nexvio_Plugin::allowed_html() );
							}
							?>
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<div class="nexvio-ps__viewport">
				<?php
				if ( $arrows_side || ( $arrows && 'yes' !== $s['show_header'] ) ) {
					if ( 'yes' !== $s['show_header'] && ! $arrows_side ) {
						echo wp_kses( '<div class="nexvio-ps__head" style="justify-content:flex-end"><div class="nexvio-ps__tools">' . $nav . '</div></div>', Nexvio_Plugin::allowed_html() );
					} else {
						echo wp_kses( $nav, Nexvio_Plugin::allowed_html() );
					}
				}
				?>
				<div class="nexvio-ps__track" tabindex="0" role="region" aria-label="<?php echo esc_attr( $s['title'] ? $s['title'] : __( 'Products', 'nexvio' ) ); ?>">
					<?php
					foreach ( $products as $product ) {
						if ( $product instanceof WC_Product ) {
							$this->render_card( $product, $s, $rules );
						}
					}
					?>
				</div>
			</div>
			<?php if ( 'yes' === $s['dots'] ) : ?>
				<div class="nexvio-ps__dots"></div>
			<?php endif; ?>
		</div>
		<?php
	}
}
