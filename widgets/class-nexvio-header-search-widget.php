<?php
/**
 * NEXVIO Header Search - Elementor widget.
 * Icon only; a click opens a search bar. Searching filters the shop / archive
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
		return array( 'nexvio-header-search' );
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
		$this->add_control( $id, array_merge( array(
			'label'     => $label,
			'type'      => Controls_Manager::NUMBER,
			'default'   => $default,
			'min'       => $min,
			'max'       => $max,
			'selectors' => array( '{{WRAPPER}} .nexvio-hs' => $var . ': {{VALUE}}' . $unit . ';' ),
		), $extra ) );
	}

	protected function register_controls() {

		$this->start_controls_section( 'section_search', array( 'label' => esc_html__( 'Search', 'nexvio' ) ) );
		$this->add_control( 'icon', array(
			'label'       => esc_html__( 'Icon', 'nexvio' ),
			'type'        => Controls_Manager::ICONS,
			'default'     => array( 'value' => '', 'library' => '' ),
			'skin'        => 'inline',
			'label_block' => false,
			'description' => esc_html__( 'Empty = default search icon.', 'nexvio' ),
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
		$this->add_control( 'layout', array(
			'label'     => esc_html__( 'Search bar opens as', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'dropdown',
			'options'   => array(
				'dropdown' => esc_html__( 'Dropdown under the icon', 'nexvio' ),
				'bar'      => esc_html__( 'Full-width bar at the top of the screen', 'nexvio' ),
			),
			'separator' => 'before',
		) );
		$this->add_control( 'side', array(
			'label'     => esc_html__( 'Dropdown opens to the', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'right',
			'options'   => array( 'right' => esc_html__( 'Left (aligned to the right of the icon)', 'nexvio' ), 'left' => esc_html__( 'Right (aligned to the left of the icon)', 'nexvio' ) ),
			'condition' => array( 'layout' => 'dropdown' ),
		) );
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
		$this->nv( 'box', esc_html__( 'Box size (px)', 'nexvio' ), '--nexvio-hs-box', 42, 20, 90 );
		$this->nv( 'isize', esc_html__( 'Icon size (px)', 'nexvio' ), '--nexvio-hs-icon', 22, 10, 60 );
		$this->nv( 'radius', esc_html__( 'Corner radius (px)', 'nexvio' ), '--nexvio-hs-radius', 6, 0, 45 );
		$this->cv( 'color', esc_html__( 'Icon colour', 'nexvio' ), '--nexvio-hs-color', '#e8edf4' );
		$this->cv( 'color_h', esc_html__( 'Icon colour on hover / open', 'nexvio' ), '--nexvio-hs-color-h', '#ffffff' );
		$this->cv( 'bg', esc_html__( 'Background', 'nexvio' ), '--nexvio-hs-bg', 'rgba(0,0,0,0)' );
		$this->cv( 'bg_h', esc_html__( 'Background on hover / open', 'nexvio' ), '--nexvio-hs-bg-h', '#262d37' );
		$this->end_controls_section();

		/* style: panel */
		$this->start_controls_section( 'style_panel', array( 'label' => esc_html__( 'Search bar', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->nv( 'p_w', esc_html__( 'Dropdown width (px)', 'nexvio' ), '--nexvio-hs-w', 380, 200, 900, 'px', array( 'condition' => array( 'layout' => 'dropdown' ) ) );
		$this->nv( 'p_bar_w', esc_html__( 'Bar content width (px)', 'nexvio' ), '--nexvio-hs-bar-w', 760, 300, 1600, 'px', array( 'condition' => array( 'layout' => 'bar' ) ) );
		$this->nv( 'p_offset', esc_html__( 'Space below the icon (px)', 'nexvio' ), '--nexvio-hs-offset', 10, 0, 60, 'px', array( 'condition' => array( 'layout' => 'dropdown' ) ) );
		$this->cv( 'p_bg', esc_html__( 'Background', 'nexvio' ), '--nexvio-hs-pbg', '#1f252e' );
		$this->cv( 'p_border', esc_html__( 'Border', 'nexvio' ), '--nexvio-hs-pborder', '#313945' );
		$this->nv( 'p_radius', esc_html__( 'Corner radius (px)', 'nexvio' ), '--nexvio-hs-pradius', 8, 0, 40 );
		$this->nv( 'p_pad', esc_html__( 'Padding (px)', 'nexvio' ), '--nexvio-hs-ppad', 10, 0, 40 );
		$this->cv( 'p_overlay', esc_html__( 'Screen overlay (bar mode)', 'nexvio' ), '--nexvio-hs-overlay', 'rgba(5,7,10,0.6)', array( 'condition' => array( 'layout' => 'bar' ) ) );
		$this->add_control( 'h_input', array( 'label' => esc_html__( 'Input', 'nexvio' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->nv( 'i_h', esc_html__( 'Height (px)', 'nexvio' ), '--nexvio-hs-ih', 46, 30, 80 );
		$this->cv( 'i_bg', esc_html__( 'Background', 'nexvio' ), '--nexvio-hs-ibg', '#181d24' );
		$this->cv( 'i_border', esc_html__( 'Border', 'nexvio' ), '--nexvio-hs-iborder', '#313945' );
		$this->cv( 'i_focus', esc_html__( 'Border when focused', 'nexvio' ), '--nexvio-hs-focus', '#3d8bff' );
		$this->cv( 'i_text', esc_html__( 'Text', 'nexvio' ), '--nexvio-hs-itext', '#e8edf4' );
		$this->cv( 'i_ph', esc_html__( 'Placeholder', 'nexvio' ), '--nexvio-hs-ph', '#8f9aab' );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'i_typo', 'selector' => '{{WRAPPER}} .nexvio-hs__input' ) );
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
		$cls   = 'nexvio-hs nexvio-hs--' . ( 'bar' === $s['layout'] ? 'bar' : 'dropdown' ) . ' nexvio-hs--' . ( 'left' === $s['side'] ? 'left' : 'right' );
		?>
		<div class="nexvio-hs-wrap">
			<div class="<?php echo esc_attr( $cls ); ?>">
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
