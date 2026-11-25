<?php
/**
 * Filter Popup - Elementor widget.
 *
 * A "Filters" button; a click slides the Product Filter in from the side.
 * The fields are the ones of Nexvio > Filters. Choices are applied
 * when the visitor presses "Apply", which also closes the panel.
 *
 * @package Nexvio_For_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-nexvio-product-filter-widget.php';

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;

class Nexvio_Filter_Drawer_Widget extends Nexvio_Product_Filter_Widget {

	public function get_name() {
		return 'nexvio_filter_drawer';
	}

	public function get_title() {
		return esc_html__( 'Filter Popup (side panel)', 'nexvio' );
	}

	public function get_icon() {
		return 'eicon-sidebar';
	}

	public function get_keywords() {
		return array( 'nexvio', 'filter', 'popup', 'drawer', 'off canvas', 'side panel', 'mobile filter' );
	}

	public function get_style_depends() {
		return array( 'nexvio-product-filter', 'nexvio-filter-drawer' );
	}

	public function get_script_depends() {
		return array( 'nexvio-product-filter', 'nexvio-filter-drawer' );
	}

	protected function register_controls() {
		/* ============ BUTTON + PANEL ============ */
		$this->start_controls_section( 'section_fd', array( 'label' => esc_html__( 'Filter button & panel', 'nexvio' ) ) );
		$this->add_control( 'fd_note', array(
			'type'            => Controls_Manager::RAW_HTML,
			'raw'             => esc_html__( 'Visitors see a button. A click opens the filter in a side panel; "Apply" closes it and shows the matching products. The fields come from Nexvio > Filters.', 'nexvio' ),
			'content_classes' => 'elementor-descriptor',
		) );
		$this->text( 'fd_label', esc_html__( 'Button text', 'nexvio' ), 'Filters' );
		$this->switcher( 'fd_show_label', esc_html__( 'Show the text', 'nexvio' ) );
		$this->add_control( 'fd_icon', array(
			'label'       => esc_html__( 'Icon', 'nexvio' ),
			'type'        => Controls_Manager::ICONS,
			'default'     => array( 'value' => '', 'library' => '' ),
			'skin'        => 'inline',
			'label_block' => false,
			'description' => esc_html__( 'Empty = filter icon.', 'nexvio' ),
		) );
		$this->switcher( 'fd_badge', esc_html__( 'Show the number of active filters', 'nexvio' ) );
		$this->add_responsive_control( 'fd_align', array(
			'label'     => esc_html__( 'Button alignment', 'nexvio' ),
			'type'      => Controls_Manager::CHOOSE,
			'options'   => array(
				'flex-start' => array( 'title' => esc_html__( 'Left', 'nexvio' ), 'icon' => 'eicon-text-align-left' ),
				'center'     => array( 'title' => esc_html__( 'Center', 'nexvio' ), 'icon' => 'eicon-text-align-center' ),
				'flex-end'   => array( 'title' => esc_html__( 'Right', 'nexvio' ), 'icon' => 'eicon-text-align-right' ),
			),
			'selectors' => array( '{{WRAPPER}} .nexvio-fd-wrap' => 'justify-content: {{VALUE}};' ),
		) );
		$this->add_control( 'fd_side', array(
			'label'     => esc_html__( 'Panel opens from', 'nexvio' ),
			'type'      => Controls_Manager::CHOOSE,
			'default'   => 'left',
			'toggle'    => false,
			'options'   => array(
				'left'  => array( 'title' => esc_html__( 'Left', 'nexvio' ), 'icon' => 'eicon-h-align-left' ),
				'right' => array( 'title' => esc_html__( 'Right', 'nexvio' ), 'icon' => 'eicon-h-align-right' ),
			),
			'separator' => 'before',
		) );
		$this->text( 'fd_title', esc_html__( 'Panel title', 'nexvio' ), 'Filters' );
		$this->text( 'fd_apply', esc_html__( 'Apply button', 'nexvio' ), 'Apply' );
		$this->switcher( 'fd_show_reset', esc_html__( 'Reset button', 'nexvio' ) );
		$this->text( 'fd_reset', esc_html__( 'Reset button text', 'nexvio' ), 'Reset', array( 'condition' => array( 'fd_show_reset' => 'yes' ) ) );
		$this->switcher( 'fd_overlay_close', esc_html__( 'Close by clicking outside', 'nexvio' ) );
		$this->end_controls_section();

		/* the whole Product Filter (fields, behaviour, styles) */
		parent::register_controls();

		/* Apply is always used here; the panel has its own title and buttons */
		foreach ( array( 'apply_mode', 'apply_text', 'show_reset', 'reset_text', 'show_title', 'title' ) as $id ) {
			$this->remove_control( $id );
		}

		/* ============ STYLE: BUTTON ============ */
		$this->start_controls_section( 'style_fd_button', array( 'label' => esc_html__( 'Filter button', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$fd = function ( $id, $label, $var ) {
			$this->add_control( $id, array(
				'label'     => $label,
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => array( '{{WRAPPER}} .nexvio-fd__open' => $var . ': {{VALUE}};' ),
			) );
		};
		$this->start_controls_tabs( 'fd_tabs' );
		$this->start_controls_tab( 'fd_tab_n', array( 'label' => esc_html__( 'Normal', 'nexvio' ) ) );
		$fd( 'fd_bg', esc_html__( 'Background', 'nexvio' ), '--fd-bg' );
		$fd( 'fd_color', esc_html__( 'Text and icon', 'nexvio' ), '--fd-color' );
		$fd( 'fd_border', esc_html__( 'Border', 'nexvio' ), '--fd-border' );
		$this->end_controls_tab();
		$this->start_controls_tab( 'fd_tab_h', array( 'label' => esc_html__( 'Hover', 'nexvio' ) ) );
		$fd( 'fd_bg_h', esc_html__( 'Background', 'nexvio' ), '--fd-bg-h' );
		$fd( 'fd_color_h', esc_html__( 'Text and icon', 'nexvio' ), '--fd-color-h' );
		$fd( 'fd_border_h', esc_html__( 'Border', 'nexvio' ), '--fd-border-h' );
		$this->end_controls_tab();
		$this->end_controls_tabs();
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'fd_typo', 'selector' => '{{WRAPPER}} .nexvio-fd__open', 'separator' => 'before' ) );
		$this->add_responsive_control( 'fd_height', array(
			'label'      => esc_html__( 'Height', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 30, 'max' => 80 ) ),
			'selectors'  => array( '{{WRAPPER}} .nexvio-fd__open' => '--fd-h: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'fd_radius', array(
			'label'      => esc_html__( 'Corner radius', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
			'selectors'  => array( '{{WRAPPER}} .nexvio-fd__open' => '--fd-radius: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'fd_icon_size', array(
			'label'      => esc_html__( 'Icon size', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 10, 'max' => 40 ) ),
			'selectors'  => array( '{{WRAPPER}} .nexvio-fd__open' => '--fd-icon: {{SIZE}}{{UNIT}};' ),
		) );
		$fd( 'fd_badge_bg', esc_html__( 'Number background', 'nexvio' ), '--fd-badge-bg' );
		$fd( 'fd_badge_color', esc_html__( 'Number text', 'nexvio' ), '--fd-badge' );
		$this->end_controls_section();

		/* ============ STYLE: PANEL ============ */
		$this->start_controls_section( 'style_fd_panel', array( 'label' => esc_html__( 'Side panel', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_responsive_control( 'fd_width', array(
			'label'      => esc_html__( 'Width', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px', 'vw' ),
			'range'      => array( 'px' => array( 'min' => 260, 'max' => 640 ), 'vw' => array( 'min' => 50, 'max' => 100 ) ),
			'selectors'  => array( '{{WRAPPER}} .nexvio-fd' => '--fd-w: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_control( 'fd_overlay', array(
			'label'     => esc_html__( 'Overlay colour', 'nexvio' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '',
			'selectors' => array( '{{WRAPPER}} .nexvio-fd' => '--fd-overlay: {{VALUE}};' ),
		) );
		$this->add_control( 'fd_speed', array(
			'label'     => esc_html__( 'Slide speed (ms)', 'nexvio' ),
			'type'      => Controls_Manager::NUMBER,
			'min'       => 0,
			'max'       => 1500,
			'step'      => 50,
			'default'   => 420,
			'selectors' => array( '{{WRAPPER}} .nexvio-fd' => '--fd-dur: {{VALUE}}ms;' ),
		) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'fd_title_typo', 'label' => esc_html__( 'Title typography', 'nexvio' ), 'selector' => '{{WRAPPER}} .nexvio-fd__title' ) );
		$this->end_controls_section();
	}

	private function filter_icon() {
		return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true"><path d="M4 7h10M18 7h2M4 17h4M12 17h8"/><circle cx="16" cy="7" r="2"/><circle cx="10" cy="17" r="2"/></svg>';
	}

	protected function render() {
		$raw = $this->get_settings_for_display();
		$s   = $this->base_settings( $raw );
		$fd  = array_merge( array(
			'fd_label'         => 'Filters',
			'fd_show_label'    => 'yes',
			'fd_icon'          => array( 'value' => '' ),
			'fd_badge'         => 'yes',
			'fd_side'          => 'left',
			'fd_title'         => 'Filters',
			'fd_apply'         => 'Apply',
			'fd_show_reset'    => 'yes',
			'fd_reset'         => 'Reset',
			'fd_overlay_close' => 'yes',
		), array_filter( (array) $raw, function ( $v ) {
			return null !== $v;
		} ) );

		/* the panel always waits for "Apply" */
		$s['apply_mode'] = 'button';
		$s['apply_text'] = $fd['fd_apply'];
		$s['show_reset'] = $fd['fd_show_reset'];
		$s['reset_text'] = $fd['fd_reset'];
		$s['show_title'] = '';

		$id    = 'nexvio-fd-' . $this->get_id();
		$title = '' !== trim( (string) $fd['fd_title'] ) ? $fd['fd_title'] : $fd['fd_label'];
		$close = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>';
		$head  = '<div class="nexvio-fd__head"><h2 class="nexvio-fd__title" id="' . esc_attr( $id ) . '-title">' . esc_html( $title ) . '</h2>'
			. '<button type="button" class="nexvio-fd__close" data-nexvio-fd-close aria-label="' . esc_attr__( 'Close the filters', 'nexvio' ) . '">' . $close . '</button></div>';
		?>
		<div class="nexvio-fd-wrap">
			<button type="button" class="nexvio-fd__open" data-nexvio-fd-open aria-controls="<?php echo esc_attr( $id ); ?>" aria-expanded="false" aria-label="<?php echo esc_attr( $fd['fd_label'] ); ?>">
				<span class="nexvio-fd__icon" aria-hidden="true">
					<?php
					if ( ! empty( $fd['fd_icon']['value'] ) ) {
						Icons_Manager::render_icon( $fd['fd_icon'], array( 'aria-hidden' => 'true' ) );
					} else {
						echo wp_kses( $this->filter_icon(), Nexvio_Plugin::allowed_html() );
					}
					?>
				</span>
				<?php if ( 'yes' === $fd['fd_show_label'] ) : ?>
					<span class="nexvio-fd__label"><?php echo esc_html( $fd['fd_label'] ); ?></span>
				<?php endif; ?>
				<?php if ( 'yes' === $fd['fd_badge'] ) : ?>
					<span class="nexvio-fd__badge" hidden>0</span>
				<?php endif; ?>
			</button>
		</div>
		<div class="nexvio-fd nexvio-fd--<?php echo 'right' === $fd['fd_side'] ? 'right' : 'left'; ?>" id="<?php echo esc_attr( $id ); ?>" aria-hidden="true" inert data-overlay-close="<?php echo 'yes' === $fd['fd_overlay_close'] ? '1' : '0'; ?>">
			<div class="nexvio-fd__overlay" data-nexvio-fd-overlay></div>
			<div class="nexvio-fd__panel" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr( $id ); ?>-title" tabindex="-1">
				<?php $this->render_filter( $s, 'nexvio-pf--drawer', $head ); ?>
			</div>
		</div>
		<?php
	}
}
