<?php
/**
 * NEXVIO Wishlist Icon - Elementor widget for the header.
 * Heart icon + live count badge, links to the wishlist ("My favorites") page.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;

class Nexvio_Wishlist_Icon_Widget extends Widget_Base {

	use Nexvio_Card_Trait;

	public function get_name() {
		return 'nexvio_wishlist_icon';
	}

	public function get_title() {
		return esc_html__( 'Wishlist Icon (header)', 'nexvio' );
	}

	public function get_icon() {
		return 'eicon-heart-o';
	}

	public function get_categories() {
		return array( 'nexvio' );
	}

	public function get_keywords() {
		return array( 'nexvio', 'wishlist', 'favorites', 'heart', 'header', 'count' );
	}

	public function get_style_depends() {
		return array( 'nexvio-wishlist-widgets' );
	}

	public function get_script_depends() {
		return array( 'nexvio', 'nexvio-wishlist-widgets' );
	}

	protected function register_controls() {

		$this->start_controls_section( 'section_icon', array( 'label' => esc_html__( 'Wishlist icon', 'nexvio' ) ) );
		$this->add_control( 'link_type', array(
			'label'   => esc_html__( 'Link to', 'nexvio' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'page',
			'options' => array(
				'page'   => esc_html__( 'Wishlist page (Nexvio > Wishlist)', 'nexvio' ),
				'custom' => esc_html__( 'Custom link', 'nexvio' ),
			),
		) );
		$this->add_control( 'link', array(
			'label'     => esc_html__( 'Link', 'nexvio' ),
			'type'      => Controls_Manager::URL,
			'default'   => array( 'url' => '/wishlist/' ),
			'condition' => array( 'link_type' => 'custom' ),
		) );
		$this->add_control( 'icon', array(
			'label'       => esc_html__( 'Icon', 'nexvio' ),
			'type'        => Controls_Manager::ICONS,
			'default'     => array( 'value' => '', 'library' => '' ),
			'skin'        => 'inline',
			'label_block' => false,
			'description' => esc_html__( 'Empty = default heart icon.', 'nexvio' ),
		) );
		$this->text( 'label', esc_html__( 'Label (screen readers / tooltip)', 'nexvio' ), 'My wishlist' );
		$this->switcher( 'show_count', esc_html__( 'Show count', 'nexvio' ), 'yes', array( 'separator' => 'before' ) );
		$this->switcher( 'hide_zero', esc_html__( 'Hide count when empty', 'nexvio' ), 'yes', array( 'condition' => array( 'show_count' => 'yes' ) ) );
		$this->switcher( 'refresh', esc_html__( 'Refresh count on page load', 'nexvio' ), 'yes', array(
			'condition'   => array( 'show_count' => 'yes' ),
			'description' => esc_html__( 'Keeps the number right even when pages are cached.', 'nexvio' ),
		) );
		$this->add_responsive_control( 'align', array(
			'label'     => esc_html__( 'Alignment', 'nexvio' ),
			'type'      => Controls_Manager::CHOOSE,
			'options'   => array(
				'flex-start' => array( 'title' => esc_html__( 'Left', 'nexvio' ), 'icon' => 'eicon-text-align-left' ),
				'center'     => array( 'title' => esc_html__( 'Center', 'nexvio' ), 'icon' => 'eicon-text-align-center' ),
				'flex-end'   => array( 'title' => esc_html__( 'Right', 'nexvio' ), 'icon' => 'eicon-text-align-right' ),
			),
			'selectors' => array( '{{WRAPPER}} .nexvio-wli-wrap' => 'justify-content: {{VALUE}};' ),
			'separator' => 'before',
		) );
		$this->end_controls_section();

		/* style: icon */
		$this->start_controls_section( 'style_icon', array( 'label' => esc_html__( 'Icon', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->wli_var( 'box', esc_html__( 'Box size (px)', 'nexvio' ), '--nexvio-wli-box', 42, 20, 90 );
		$this->wli_var( 'isize', esc_html__( 'Icon size (px)', 'nexvio' ), '--nexvio-wli-icon', 22, 10, 60 );
		$this->wli_var( 'radius', esc_html__( 'Corner radius (px)', 'nexvio' ), '--nexvio-wli-radius', 6, 0, 45 );
		$this->wli_color( 'color', esc_html__( 'Icon colour', 'nexvio' ), '--nexvio-wli-color', '#e8edf4' );
		$this->wli_color( 'color_h', esc_html__( 'Icon colour on hover', 'nexvio' ), '--nexvio-wli-color-h', '#ffffff' );
		$this->wli_color( 'bg', esc_html__( 'Background', 'nexvio' ), '--nexvio-wli-bg', 'rgba(0,0,0,0)' );
		$this->wli_color( 'bg_h', esc_html__( 'Background on hover', 'nexvio' ), '--nexvio-wli-bg-h', '#262d37' );
		$this->wli_color( 'color_on', esc_html__( 'Icon colour when list is not empty', 'nexvio' ), '--nexvio-wli-color-on', '' );
		$this->end_controls_section();

		/* style: count */
		$this->start_controls_section( 'style_count', array( 'label' => esc_html__( 'Count badge', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE, 'condition' => array( 'show_count' => 'yes' ) ) );
		$this->wli_color( 'c_bg', esc_html__( 'Background', 'nexvio' ), '--nexvio-wli-cbg', '#3d8bff' );
		$this->wli_color( 'c_color', esc_html__( 'Text', 'nexvio' ), '--nexvio-wli-ccolor', '#ffffff' );
		$this->wli_color( 'c_border', esc_html__( 'Ring colour (around the badge)', 'nexvio' ), '--nexvio-wli-cring', '#13171d' );
		$this->wli_var( 'c_size', esc_html__( 'Badge size (px)', 'nexvio' ), '--nexvio-wli-csize', 18, 12, 40 );
		$this->wli_var( 'c_top', esc_html__( 'Top offset (px)', 'nexvio' ), '--nexvio-wli-ctop', 3, -20, 30 );
		$this->wli_var( 'c_right', esc_html__( 'Right offset (px)', 'nexvio' ), '--nexvio-wli-cright', 2, -20, 30 );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'c_typo', 'selector' => '{{WRAPPER}} .nexvio-wli__count' ) );
		$this->end_controls_section();
	}

	private function wli_var( $id, $label, $var, $default, $min, $max ) {
		$this->add_control( $id, array(
			'label'     => $label,
			'type'      => Controls_Manager::NUMBER,
			'default'   => $default,
			'min'       => $min,
			'max'       => $max,
			'selectors' => array( '{{WRAPPER}} .nexvio-wli' => $var . ': {{VALUE}}px;' ),
		) );
	}

	private function wli_color( $id, $label, $var, $default ) {
		$this->add_control( $id, array(
			'label'     => $label,
			'type'      => Controls_Manager::COLOR,
			'default'   => $default,
			'selectors' => array( '{{WRAPPER}} .nexvio-wli' => $var . ': {{VALUE}};' ),
		) );
	}

	protected function render() {
		$s      = $this->get_settings_for_display();
		$count = Nexvio_Wishlist::count();

		if ( 'custom' === $s['link_type'] && ! empty( $s['link']['url'] ) ) {
			$this->add_link_attributes( 'link', $s['link'] );
		} else {
			$url = Nexvio_Wishlist::url();
			$this->add_render_attribute( 'link', 'href', $url ? $url : '#' );
		}

		$classes = array( 'nexvio-wli' );
		if ( $count > 0 ) {
			$classes[] = 'has-items';
		}
		$this->add_render_attribute( 'link', array(
			'class'           => $classes,
			'aria-label'      => $s['label'],
			'title'           => $s['label'],
			'data-hide-zero'  => 'yes' === $s['hide_zero'] ? '1' : '0',
			'data-refresh'    => 'yes' === $s['refresh'] ? '1' : '0',
		) );

		$show = 'yes' === $s['show_count'];
		$hide = $show && 'yes' === $s['hide_zero'] && $count < 1;
		?>
		<div class="nexvio-wli-wrap">
			<a <?php $this->print_render_attribute_string( 'link' ); ?>>
				<span class="nexvio-wli__icon" aria-hidden="true">
					<?php
					if ( ! empty( $s['icon']['value'] ) ) {
						Icons_Manager::render_icon( $s['icon'], array( 'aria-hidden' => 'true' ) );
					} else {
						echo '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"><path d="M12 20s-7-4.4-9-9a4.8 4.8 0 0 1 9-3 4.8 4.8 0 0 1 9 3c-2 4.6-9 9-9 9z"/></svg>';
					}
					?>
				</span>
				<?php if ( $show ) : ?>
					<span class="nexvio-wli__count nexvio-wishlist-count<?php echo $hide ? ' is-hidden' : ''; ?>" aria-live="polite"><?php echo esc_html( $count ); ?></span>
				<?php endif; ?>
			</a>
		</div>
		<?php
	}
}
