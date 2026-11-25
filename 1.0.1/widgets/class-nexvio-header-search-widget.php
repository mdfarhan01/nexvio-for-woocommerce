<?php
/**
 * NEXVIO Header Search - Elementor widget.
 * Icon only; a click opens the search (dropdown, top bar, popup, full screen, side panel or inline; one layout per device). Searching filters the shop / archive
 * products by title (nexvio_q), with AJAX when the page has an Nexvio Product Filter.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;

class Nexvio_Header_Search_Widget extends Widget_Base {

	use Nexvio_Card_Trait;

	public function get_name() {
		return 'nexvio_header_search';
	}

	public function get_title() {
		return esc_html__( 'Header Search', 'nexvio' );
	}

	public function get_icon() {
		return 'eicon-search';
	}

	public function get_categories() {
		return array( 'nexvio' );
	}

	public function get_keywords() {
		return array( 'nexvio', 'search', 'recherche', 'header', 'product', 'woocommerce' );
	}

	public function get_style_depends() {
		return array( 'nexvio-layout', 'nexvio-header-search' );
	}

	public function get_script_depends() {
		return array( 'nexvio-header-search', 'nexvio-product-filter' );
	}

	private function cv( $id, $label, $var, $default, $extra = array() ) {
		$this->add_control( $id, array_merge( array(
			'label'     => $label,
			'type'      => Controls_Manager::COLOR,
			'default'   => $default,
			'selectors' => array( '{{WRAPPER}} .nexvio-hs' => $var . ': {{VALUE}};' ),
		), $extra ) );
	}

	private function nv( $id, $label, $var, $default, $min, $max, $unit = 'px', $extra = array() ) {
		$responsive = ! empty( $extra['_r'] );
		unset( $extra['_r'] );
		$args = array_merge( array(
			'label'     => $label,
			'type'      => Controls_Manager::NUMBER,
			'default'   => $default,
			'min'       => $min,
			'max'       => $max,
			'selectors' => array( '{{WRAPPER}} .nexvio-hs' => $var . ': {{VALUE}}' . $unit . ';' ),
		), $extra );
		if ( $responsive ) {
			$this->add_responsive_control( $id, $args );
		} else {
			$this->add_control( $id, $args );
		}
	}

	/** The search layouts: key => title, short description. */
	private static function layouts() {
		return array(
			'dropdown'   => array( __( 'Dropdown', 'nexvio' ), __( 'Small panel right under the icon.', 'nexvio' ) ),
			'bar'        => array( __( 'Top Bar', 'nexvio' ), __( 'Full-width bar at the top of the screen.', 'nexvio' ) ),
			'modal'      => array( __( 'Centered Popup', 'nexvio' ), __( 'Search box in the middle of a dimmed screen.', 'nexvio' ) ),
			'fullscreen' => array( __( 'Full Screen', 'nexvio' ), __( 'Big search field over the whole screen.', 'nexvio' ) ),
			'slide'      => array( __( 'Side Panel', 'nexvio' ), __( 'Panel that slides in from the screen edge.', 'nexvio' ) ),
			'inline'     => array( __( 'Inline Expand', 'nexvio' ), __( 'The icon turns into a search field in place.', 'nexvio' ) ),
		);
	}

	private function layout_options() {
		$out = array();
		foreach ( self::layouts() as $key => $l ) {
			$img = 'assets/img/layouts/hs-' . $key . '.svg';
			$out[ $key ] = array(
				'title' => $l[0],
				'desc'  => $l[1],
				'image' => NEXVIO_URL . $img . '?v=' . Nexvio_Plugin::ver( $img ),
			);
		}
		return $out;
	}

	private function valid_layout( $v, $fallback ) {
		return ( is_string( $v ) && isset( self::layouts()[ $v ] ) ) ? $v : $fallback;
	}

	protected function register_controls() {

		$this->start_controls_section( 'section_search', array( 'label' => esc_html__( 'Search', 'nexvio' ) ) );
		$has_lc = class_exists( 'Nexvio_Layout_Control' );
		$this->add_responsive_control( 'layout', array(
			'label'       => esc_html__( 'Search layout', 'nexvio' ),
			'type'        => $has_lc ? Nexvio_Layout_Control::TYPE : Controls_Manager::SELECT,
			'default'     => 'dropdown',
			'options'     => $has_lc ? $this->layout_options() : array_map( function ( $l ) { return $l[0]; }, self::layouts() ),
			'description' => esc_html__( 'Click a picture to choose how the search opens. Use the device icon next to the label to pick another layout on tablet / mobile.', 'nexvio' ),
		) );
		$this->add_control( 'side', array(
			'label'       => esc_html__( 'Panel side', 'nexvio' ),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'right',
			'options'     => array( 'right' => esc_html__( 'Right', 'nexvio' ), 'left' => esc_html__( 'Left', 'nexvio' ) ),
			'description' => esc_html__( 'Dropdown / Inline Expand: the icon edge the panel lines up with. Side Panel: the screen edge it slides in from. On small screens the dropdown fits the screen automatically.', 'nexvio' ),
		) );
		$this->add_control( 'icon', array(
			'label'       => esc_html__( 'Icon', 'nexvio' ),
			'type'        => Controls_Manager::ICONS,
			'default'     => array( 'value' => '', 'library' => '' ),
			'skin'        => 'inline',
			'label_block' => false,
			'description' => esc_html__( 'Empty = default search icon.', 'nexvio' ),
			'separator'   => 'before',
		) );
		$this->text( 'label', esc_html__( 'Icon label (screen readers)', 'nexvio' ), 'Search' );
		$this->text( 'placeholder', esc_html__( 'Placeholder', 'nexvio' ), 'Search products…' );
		$this->add_control( 'mode', array(
			'label'   => esc_html__( 'Search in', 'nexvio' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'title',
			'options' => array(
				'title' => esc_html__( 'Product titles (filters the shop page)', 'nexvio' ),
				'wc'    => esc_html__( 'WooCommerce search (title + description)', 'nexvio' ),
			),
		) );
		$this->switcher( 'in_category', esc_html__( 'On a category page, search inside that category', 'nexvio' ), 'yes', array( 'condition' => array( 'mode' => 'title' ) ) );
		$this->switcher( 'show_button', esc_html__( 'Show search button in the bar', 'nexvio' ) );
		$this->text( 'button_text', esc_html__( 'Button text (empty = icon)', 'nexvio' ), '', array( 'condition' => array( 'show_button' => 'yes' ) ) );
		$this->add_responsive_control( 'align', array(
			'label'     => esc_html__( 'Icon alignment', 'nexvio' ),
			'type'      => Controls_Manager::CHOOSE,
			'options'   => array(
				'flex-start' => array( 'title' => esc_html__( 'Left', 'nexvio' ), 'icon' => 'eicon-text-align-left' ),
				'center'     => array( 'title' => esc_html__( 'Center', 'nexvio' ), 'icon' => 'eicon-text-align-center' ),
				'flex-end'   => array( 'title' => esc_html__( 'Right', 'nexvio' ), 'icon' => 'eicon-text-align-right' ),
			),
			'selectors' => array( '{{WRAPPER}} .nexvio-hs-wrap' => 'justify-content: {{VALUE}};' ),
			'separator' => 'before',
		) );
		$this->end_controls_section();

		/* style: icon */
		$this->start_controls_section( 'style_icon', array( 'label' => esc_html__( 'Icon', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->nv( 'box', esc_html__( 'Box size (px)', 'nexvio' ), '--nexvio-hs-box', 42, 20, 90, 'px', array( '_r' => true ) );
		$this->nv( 'isize', esc_html__( 'Icon size (px)', 'nexvio' ), '--nexvio-hs-icon', 22, 10, 60, 'px', array( '_r' => true ) );
		$this->nv( 'radius', esc_html__( 'Corner radius (px)', 'nexvio' ), '--nexvio-hs-radius', 6, 0, 45 );
		$this->cv( 'color', esc_html__( 'Icon colour', 'nexvio' ), '--nexvio-hs-color', '#e8edf4' );
		$this->cv( 'color_h', esc_html__( 'Icon colour on hover / open', 'nexvio' ), '--nexvio-hs-color-h', '#ffffff' );
		$this->cv( 'bg', esc_html__( 'Background', 'nexvio' ), '--nexvio-hs-bg', 'rgba(0,0,0,0)' );
		$this->cv( 'bg_h', esc_html__( 'Background on hover / open', 'nexvio' ), '--nexvio-hs-bg-h', '#262d37' );
		$this->end_controls_section();

		/* style: panel */
		$this->start_controls_section( 'style_panel', array( 'label' => esc_html__( 'Search bar', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->nv( 'p_w', esc_html__( 'Panel width (px) - dropdown / inline / side panel', 'nexvio' ), '--nexvio-hs-w', 380, 200, 900, 'px', array( '_r' => true ) );
		$this->nv( 'p_bar_w', esc_html__( 'Content width (px) - bar / popup / full screen', 'nexvio' ), '--nexvio-hs-bar-w', 760, 300, 1600, 'px', array( '_r' => true ) );
		$this->nv( 'p_offset', esc_html__( 'Space below the icon (px)', 'nexvio' ), '--nexvio-hs-offset', 10, 0, 60, 'px', array( '_r' => true ) );
		$this->cv( 'p_bg', esc_html__( 'Background', 'nexvio' ), '--nexvio-hs-pbg', '#1f252e' );
		$this->cv( 'p_border', esc_html__( 'Border', 'nexvio' ), '--nexvio-hs-pborder', '#313945' );
		$this->nv( 'p_radius', esc_html__( 'Corner radius (px)', 'nexvio' ), '--nexvio-hs-pradius', 8, 0, 40 );
		$this->nv( 'p_pad', esc_html__( 'Padding (px)', 'nexvio' ), '--nexvio-hs-ppad', 10, 0, 40, 'px', array( '_r' => true ) );
		$this->cv( 'p_overlay', esc_html__( 'Screen overlay (bar / popup / side panel)', 'nexvio' ), '--nexvio-hs-overlay', 'rgba(5,7,10,0.6)' );
		$this->cv( 'p_fsbg', esc_html__( 'Full screen background', 'nexvio' ), '--nexvio-hs-fsbg', 'rgba(10,13,18,0.96)' );
		$this->add_control( 'h_input', array( 'label' => esc_html__( 'Input', 'nexvio' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->nv( 'i_h', esc_html__( 'Height (px)', 'nexvio' ), '--nexvio-hs-ih', 46, 30, 80, 'px', array( '_r' => true ) );
		$this->cv( 'i_bg', esc_html__( 'Background', 'nexvio' ), '--nexvio-hs-ibg', '#181d24' );
		$this->cv( 'i_border', esc_html__( 'Border', 'nexvio' ), '--nexvio-hs-iborder', '#313945' );
		$this->cv( 'i_focus', esc_html__( 'Border when focused', 'nexvio' ), '--nexvio-hs-focus', '#3d8bff' );
		$this->cv( 'i_text', esc_html__( 'Text', 'nexvio' ), '--nexvio-hs-itext', '#e8edf4' );
		$this->cv( 'i_ph', esc_html__( 'Placeholder', 'nexvio' ), '--nexvio-hs-ph', '#8f9aab' );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'i_typo', 'selector' => '{{WRAPPER}} .nexvio-hs .nexvio-hs__input' ) );
		$this->add_control( 'h_btn', array( 'label' => esc_html__( 'Search button', 'nexvio' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before', 'condition' => array( 'show_button' => 'yes' ) ) );
		$this->cv( 'b_bg', esc_html__( 'Background', 'nexvio' ), '--nexvio-hs-btn', '#3d8bff', array( 'condition' => array( 'show_button' => 'yes' ) ) );
		$this->cv( 'b_bg_h', esc_html__( 'Background on hover', 'nexvio' ), '--nexvio-hs-btn-h', '#5a9dff', array( 'condition' => array( 'show_button' => 'yes' ) ) );
		$this->cv( 'b_c', esc_html__( 'Text / icon', 'nexvio' ), '--nexvio-hs-btn-t', '#ffffff', array( 'condition' => array( 'show_button' => 'yes' ) ) );
		$this->end_controls_section();
	}

	private function svg_search() {
		return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>';
	}

	protected function render() {
		$s    = $this->get_settings_for_display();
		$shop = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
		$mode = 'wc' === $s['mode'] ? 'wc' : 'title';

		$action = $shop;
		if ( 'title' === $mode && 'yes' === $s['in_category'] && function_exists( 'is_product_category' ) && is_product_category() ) {
			$link   = get_term_link( get_queried_object() );
			$action = is_wp_error( $link ) ? $shop : $link;
		}
		if ( 'wc' === $mode ) {
			$action = home_url( '/' );
		}

		$value = 'wc' === $mode ? get_search_query() : Nexvio_Filter_Query::search_term();
		$uid   = 'nexvio-hs-' . $this->get_id();
		$l_d   = $this->valid_layout( isset( $s['layout'] ) ? $s['layout'] : '', 'dropdown' );
		$l_t   = $this->valid_layout( isset( $s['layout_tablet'] ) ? $s['layout_tablet'] : '', $l_d );
		$l_m   = $this->valid_layout( isset( $s['layout_mobile'] ) ? $s['layout_mobile'] : '', $l_t );
		$cls   = 'nexvio-hs nexvio-hs--' . $l_d . ' nexvio-hs--' . ( 'left' === $s['side'] ? 'left' : 'right' );
		?>
		<div class="nexvio-hs-wrap">
			<div class="<?php echo esc_attr( $cls ); ?>" data-layout="<?php echo esc_attr( $l_d ); ?>" data-layout-tablet="<?php echo esc_attr( $l_t ); ?>" data-layout-mobile="<?php echo esc_attr( $l_m ); ?>">
				<button type="button" class="nexvio-hs__toggle" aria-expanded="false" aria-controls="<?php echo esc_attr( $uid ); ?>" aria-label="<?php echo esc_attr( $s['label'] ); ?>" title="<?php echo esc_attr( $s['label'] ); ?>">
					<span class="nexvio-hs__icon" aria-hidden="true">
						<?php
						if ( ! empty( $s['icon']['value'] ) ) {
							Icons_Manager::render_icon( $s['icon'], array( 'aria-hidden' => 'true' ) );
						} else {
							echo wp_kses( $this->svg_search(), Nexvio_Plugin::allowed_html() );
						}
						?>
					</span>
				</button>
				<div class="nexvio-hs__panel" id="<?php echo esc_attr( $uid ); ?>" hidden>
					<form class="nexvio-hs__form" role="search" method="get" action="<?php echo esc_url( $action ); ?>" data-mode="<?php echo esc_attr( $mode ); ?>">
						<label class="screen-reader-text nexvio-hs__sr" for="<?php echo esc_attr( $uid ); ?>-q"><?php echo esc_html( $s['label'] ); ?></label>
						<span class="nexvio-hs__field">
							<?php echo wp_kses( $this->svg_search(), Nexvio_Plugin::allowed_html() ); ?>
							<input type="search" id="<?php echo esc_attr( $uid ); ?>-q" class="nexvio-hs__input" name="<?php echo 'wc' === $mode ? 's' : 'nexvio_q'; ?>" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php echo esc_attr( $s['placeholder'] ); ?>" autocomplete="off">
						</span>
						<?php if ( 'wc' === $mode ) : ?>
							<input type="hidden" name="post_type" value="product">
						<?php endif; ?>
						<?php if ( 'yes' === $s['show_button'] ) : ?>
							<button type="submit" class="nexvio-hs__submit" aria-label="<?php echo esc_attr( $s['label'] ); ?>">
								<?php echo wp_kses( '' !== trim( (string) $s['button_text'] ) ? esc_html( $s['button_text'] ) : $this->svg_search(), Nexvio_Plugin::allowed_html() ); ?>
							</button>
						<?php endif; ?>
						<button type="button" class="nexvio-hs__close" aria-label="<?php esc_attr_e( 'Close', 'nexvio' ); ?>">&times;</button>
					</form>
				</div>
			</div>
		</div>
		<?php
	}
}
