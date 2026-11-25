<?php
/**
 * NEXVIO Product Category Slider - Elementor widget.
 *
 * The same categories, cards, designs and colours as the Product Category Grid,
 * shown in a slider: arrows, dots, swipe / mouse drag, autoplay and endless loop.
 *
 * @package Nexvio_For_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-nexvio-category-grid-widget.php';

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

class Nexvio_Category_Slider_Widget extends Nexvio_Category_Grid_Widget {

	public function get_name() {
		return 'nexvio_category_slider';
	}

	public function get_title() {
		return esc_html__( 'Product Category Slider', 'nexvio' );
	}

	public function get_icon() {
		return 'eicon-slider-push';
	}

	public function get_keywords() {
		return array( 'nexvio', 'woocommerce', 'category', 'categories', 'product category', 'slider', 'carousel' );
	}

	public function get_style_depends() {
		return array( 'nexvio-layout', 'nexvio-category-grid' );
	}

	public function get_script_depends() {
		return array( 'nexvio-category-slider' );
	}

	protected function register_controls() {
		parent::register_controls();

		/* The columns control of the grid = cards visible at once. */
		$this->update_responsive_control( 'columns', array( 'label' => esc_html__( 'Cards visible', 'nexvio' ) ) );

		/* ============ CONTENT: SLIDER ============ */
		$this->start_controls_section( 'section_slider', array( 'label' => esc_html__( 'Slider', 'nexvio' ) ) );
		$this->add_control( 'arrows', array(
			'label'        => esc_html__( 'Arrows', 'nexvio' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		) );
		$this->add_control( 'dots', array(
			'label'        => esc_html__( 'Dots', 'nexvio' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		) );
		$this->add_control( 'loop', array(
			'label'        => esc_html__( 'Endless loop (after the last card, go back to the first)', 'nexvio' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		) );
		$this->add_control( 'autoplay', array(
			'label'        => esc_html__( 'Autoplay', 'nexvio' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => '',
		) );
		$this->add_control( 'delay', array(
			'label'     => esc_html__( 'Time between slides (seconds)', 'nexvio' ),
			'type'      => Controls_Manager::NUMBER,
			'min'       => 1,
			'max'       => 20,
			'step'      => 0.5,
			'default'   => 3,
			'condition' => array( 'autoplay' => 'yes' ),
		) );
		$this->add_control( 'pause_hover', array(
			'label'        => esc_html__( 'Pause when the mouse is over', 'nexvio' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
			'condition'    => array( 'autoplay' => 'yes' ),
		) );
		$this->add_control( 'scroll_by', array(
			'label'   => esc_html__( 'Arrow moves', 'nexvio' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'one',
			'options' => array(
				'one'  => esc_html__( 'One card', 'nexvio' ),
				'page' => esc_html__( 'A full page of cards', 'nexvio' ),
			),
		) );
		$this->end_controls_section();

		/* ============ STYLE: ARROWS & DOTS ============ */
		$this->start_controls_section( 'style_nav', array( 'label' => esc_html__( 'Arrows & dots', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_control( 'nav_note', array(
			'type'            => Controls_Manager::RAW_HTML,
			'raw'             => esc_html__( 'By default arrows and dots use the Heading and Background colours.', 'nexvio' ),
			'content_classes' => 'elementor-descriptor',
		) );
		$this->add_control( 'arrow_bg', array(
			'label'     => esc_html__( 'Arrow background', 'nexvio' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .nxcg-sl' => '--nxcg-arrow-bg: {{VALUE}};' ),
		) );
		$this->add_control( 'arrow_color', array(
			'label'     => esc_html__( 'Arrow icon', 'nexvio' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .nxcg-sl' => '--nxcg-arrow-c: {{VALUE}};' ),
		) );
		$this->add_control( 'arrow_size', array(
			'label'     => esc_html__( 'Arrow size', 'nexvio' ),
			'type'      => Controls_Manager::SLIDER,
			'range'     => array( 'px' => array( 'min' => 28, 'max' => 70 ) ),
			'default'   => array( 'size' => 44, 'unit' => 'px' ),
			'selectors' => array( '{{WRAPPER}} .nxcg-sl' => '--nxcg-arrow-s: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_control( 'dot_color', array(
			'label'     => esc_html__( 'Active dot', 'nexvio' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .nxcg-sl' => '--nxcg-dot: {{VALUE}};' ),
		) );
		$this->end_controls_section();
	}

	protected function render() {
		$s     = $this->get_settings_for_display();
		$terms = $this->get_categories_list( $s );
		if ( ! $terms ) {
			$this->render_empty( $s );
			return;
		}
		$cfg = array(
			'loop'     => 'yes' === $s['loop'],
			'autoplay' => 'yes' === $s['autoplay'],
			'delay'    => max( 1, (float) $s['delay'] ) * 1000,
			'pause'    => 'yes' === $s['pause_hover'],
			'page'     => 'page' === $s['scroll_by'],
		);
		$class = $this->root_class( $s ) . ' nxcg-sl';
		?>
		<div class="<?php echo esc_attr( $class ); ?>" data-nxcg="<?php echo esc_attr( wp_json_encode( $cfg ) ); ?>">
			<div class="nxcg-sl__view">
				<div class="nxcg-sl__track" tabindex="0" aria-label="<?php esc_attr_e( 'Product categories', 'nexvio' ); ?>">
					<?php $this->render_cards( $terms, $s, 'nxcg-sl__slide' ); ?>
				</div>
				<?php if ( 'yes' === $s['arrows'] ) : ?>
					<button type="button" class="nxcg-sl__arrow nxcg-sl__prev" aria-label="<?php esc_attr_e( 'Previous', 'nexvio' ); ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m14.5 5.5-6.5 6.5 6.5 6.5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
					<button type="button" class="nxcg-sl__arrow nxcg-sl__next" aria-label="<?php esc_attr_e( 'Next', 'nexvio' ); ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9.5 5.5 6.5 6.5-6.5 6.5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
				<?php endif; ?>
			</div>
			<?php if ( 'yes' === $s['dots'] ) : ?>
				<div class="nxcg-sl__dots" role="tablist" aria-label="<?php esc_attr_e( 'Slides', 'nexvio' ); ?>"></div>
			<?php endif; ?>
		</div>
		<?php
	}
}
