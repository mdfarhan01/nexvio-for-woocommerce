<?php
/**
 * Header Cart - Elementor widget: cart icon with a live counter that opens the side cart.
 *
 * @package Nexvio_For_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;

class Nexvio_Header_Cart_Widget extends Widget_Base {

	use Nexvio_Card_Trait;

	public function get_name() {
		return 'nexvio_header_cart';
	}

	public function get_title() {
		return esc_html__( 'Header Cart', 'nexvio' );
	}

	public function get_icon() {
		return 'eicon-cart-medium';
	}

	public function get_categories() {
		return array( 'nexvio' );
	}

	public function get_keywords() {
		return array( 'nexvio', 'cart', 'bag', 'header', 'mini cart', 'side cart', 'counter' );
	}

	public function get_style_depends() {
		return array( 'nexvio-side-cart' );
	}

	public function get_script_depends() {
		return array( 'nexvio-side-cart' );
	}

	private function v( $id, $label, $var, $default, $min, $max, $unit = 'px', $responsive = false ) {
		$args = array(
			'label'     => $label,
			'type'      => Controls_Manager::NUMBER,
			'default'   => $default,
			'min'       => $min,
			'max'       => $max,
			'selectors' => array( '{{WRAPPER}} .nexvio-hc' => $var . ': {{VALUE}}' . $unit . ';' ),
		);
		if ( $responsive ) {
			$this->add_responsive_control( $id, $args );
		} else {
			$this->add_control( $id, $args );
		}
	}

	private function c( $id, $label, $var, $default ) {
		$this->add_control( $id, array(
			'label'     => $label,
			'type'      => Controls_Manager::COLOR,
			'default'   => $default,
			'selectors' => array( '{{WRAPPER}} .nexvio-hc' => $var . ': {{VALUE}};' ),
		) );
	}

	protected function register_controls() {
		$this->start_controls_section( 'section_cart', array( 'label' => esc_html__( 'Header Cart', 'nexvio' ) ) );
		$this->add_control( 'icon', array(
			'label'       => esc_html__( 'Icon', 'nexvio' ),
			'type'        => Controls_Manager::ICONS,
			'default'     => array( 'value' => '', 'library' => '' ),
			'skin'        => 'inline',
			'label_block' => false,
			'description' => esc_html__( 'Empty = shopping bag.', 'nexvio' ),
		) );
		$this->add_control( 'icon_builtin', array(
			'label'     => esc_html__( 'Built-in icon', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'bag',
			'options'   => array( 'bag' => esc_html__( 'Shopping bag', 'nexvio' ), 'cart' => esc_html__( 'Cart', 'nexvio' ) ),
			'condition' => array( 'icon[value]' => '' ),
		) );
		$this->switcher( 'show_label', esc_html__( 'Show a label', 'nexvio' ), '' );
		$this->text( 'label', esc_html__( 'Label', 'nexvio' ), 'Cart', array( 'condition' => array( 'show_label' => 'yes' ) ) );
		$this->text( 'a11y', esc_html__( 'Screen reader text', 'nexvio' ), 'Open cart' );
		$this->switcher( 'show_count', esc_html__( 'Show item counter', 'nexvio' ), 'yes', array( 'separator' => 'before' ) );
		$this->switcher( 'hide_zero', esc_html__( 'Hide the counter when the cart is empty', 'nexvio' ), '', array( 'condition' => array( 'show_count' => 'yes' ) ) );
		$this->add_control( 'count_mode', array(
			'label'     => esc_html__( 'Counter counts', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'global',
			'options'   => array(
				'global'   => esc_html__( 'Dashboard setting', 'nexvio' ),
				'quantity' => esc_html__( 'All items (quantities added up)', 'nexvio' ),
				'lines'    => esc_html__( 'Different products', 'nexvio' ),
			),
			'condition' => array( 'show_count' => 'yes' ),
		) );
		$this->add_control( 'on_click', array(
			'label'     => esc_html__( 'On click', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'global',
			'options'   => array(
				'global' => esc_html__( 'Dashboard setting', 'nexvio' ),
				'open'   => esc_html__( 'Open the side cart', 'nexvio' ),
				'link'   => esc_html__( 'Go to the cart page', 'nexvio' ),
			),
			'separator' => 'before',
		) );
		$this->add_responsive_control( 'align', array(
			'label'     => esc_html__( 'Alignment', 'nexvio' ),
			'type'      => Controls_Manager::CHOOSE,
			'options'   => array(
				'flex-start' => array( 'title' => esc_html__( 'Left', 'nexvio' ), 'icon' => 'eicon-text-align-left' ),
				'center'     => array( 'title' => esc_html__( 'Center', 'nexvio' ), 'icon' => 'eicon-text-align-center' ),
				'flex-end'   => array( 'title' => esc_html__( 'Right', 'nexvio' ), 'icon' => 'eicon-text-align-right' ),
			),
			'selectors' => array( '{{WRAPPER}} .nexvio-hc-wrap' => 'justify-content: {{VALUE}};' ),
		) );
		$this->end_controls_section();

		$this->start_controls_section( 'style_icon', array( 'label' => esc_html__( 'Icon', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->v( 'size', esc_html__( 'Icon size (px)', 'nexvio' ), '--hc-size', 24, 12, 64, 'px', true );
		$this->c( 'color', esc_html__( 'Colour', 'nexvio' ), '--hc-color', '' );
		$this->c( 'color_h', esc_html__( 'Colour on hover', 'nexvio' ), '--hc-color-h', '' );
		$this->c( 'bg', esc_html__( 'Background', 'nexvio' ), '--hc-bg', '' );
		$this->c( 'bg_h', esc_html__( 'Background on hover', 'nexvio' ), '--hc-bg-h', '' );
		$this->v( 'pad', esc_html__( 'Padding (px)', 'nexvio' ), '--hc-pad', 9, 0, 30 );
		$this->v( 'radius', esc_html__( 'Corner radius (px)', 'nexvio' ), '--hc-radius', 10, 0, 60 );
		$this->add_control( 'gap', array(
			'label'     => esc_html__( 'Space between icon and label (px)', 'nexvio' ),
			'type'      => Controls_Manager::NUMBER,
			'min'       => 0,
			'max'       => 40,
			'default'   => 8,
			'selectors' => array( '{{WRAPPER}} .nexvio-hc' => 'gap: {{VALUE}}px;' ),
			'condition' => array( 'show_label' => 'yes' ),
		) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'label_typo', 'label' => esc_html__( 'Label typography', 'nexvio' ), 'selector' => '{{WRAPPER}} .nexvio-hc__label', 'condition' => array( 'show_label' => 'yes' ) ) );
		$this->end_controls_section();

		$this->start_controls_section( 'style_count', array( 'label' => esc_html__( 'Counter', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE, 'condition' => array( 'show_count' => 'yes' ) ) );
		$this->c( 'c_bg', esc_html__( 'Background', 'nexvio' ), '--hc-c-bg', '' );
		$this->c( 'c_color', esc_html__( 'Number colour', 'nexvio' ), '--hc-c-color', '' );
		$this->v( 'c_size', esc_html__( 'Size (px)', 'nexvio' ), '--hc-c-size', 18, 12, 40, 'px', true );
		$this->v( 'c_top', esc_html__( 'Top offset (px)', 'nexvio' ), '--hc-c-top', -2, -20, 20 );
		$this->v( 'c_right', esc_html__( 'Right offset (px)', 'nexvio' ), '--hc-c-right', -2, -20, 20 );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'c_typo', 'label' => esc_html__( 'Number typography', 'nexvio' ), 'selector' => '{{WRAPPER}} .nexvio-hc__count' ) );
		$this->end_controls_section();
	}

	protected function render() {
		if ( ! Nexvio_Cart_Settings::on( 'header_enabled' ) ) {
			return;
		}
		$s     = $this->get_settings_for_display();
		$mode  = in_array( $s['count_mode'], array( 'quantity', 'lines' ), true ) ? $s['count_mode'] : '';
		$opens = 'open' === $s['on_click'] || ( 'global' === $s['on_click'] && Nexvio_Cart_Settings::on( 'open_header' ) );
		$opens = $opens && Nexvio_Cart_Settings::on( 'enabled' );
		$count = Nexvio_Side_Cart::count( $mode );
		$hide  = 'yes' === $s['hide_zero'];
		?>
		<div class="nexvio-hc-wrap">
			<a class="nexvio-hc" href="<?php echo esc_url( Nexvio_Cart_Settings::cart_url() ); ?>"<?php echo $opens ? ' data-nexvio-cart-open' : ''; ?> aria-label="<?php echo esc_attr( $s['a11y'] ); ?>">
				<span class="nexvio-hc__icon" aria-hidden="true">
					<?php
					if ( ! empty( $s['icon']['value'] ) ) {
						Icons_Manager::render_icon( $s['icon'], array( 'aria-hidden' => 'true' ) );
					} else {
						echo wp_kses( Nexvio_Side_Cart::icon( 'cart' === $s['icon_builtin'] ? 'cart' : 'bag' ), Nexvio_Plugin::allowed_html() );
					}
					?>
					<?php if ( 'yes' === $s['show_count'] ) : ?>
						<span class="nexvio-hc__count nexvio-cart-count<?php echo $hide && $count < 1 ? ' is-zero-hidden' : ''; ?>" data-hide-zero="<?php echo $hide ? '1' : '0'; ?>"<?php echo $mode ? ' data-count-mode="' . esc_attr( $mode ) . '"' : ''; ?>><?php echo (int) $count; ?></span>
					<?php endif; ?>
				</span>
				<?php if ( 'yes' === $s['show_label'] ) : ?>
					<span class="nexvio-hc__label"><?php echo esc_html( $s['label'] ); ?></span>
				<?php endif; ?>
			</a>
		</div>
		<?php
	}
}
