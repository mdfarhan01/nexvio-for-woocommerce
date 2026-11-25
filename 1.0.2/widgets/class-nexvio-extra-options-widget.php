<?php
/**
 * NEXVIO Extra Options - Elementor widget for the single product page.
 *
 * Shows every extra field that was created in Nexvio > Extra Options for the product
 * (forms assigned to all products, its categories, tags or to the product itself),
 * with live price totals. The choices travel with the Add to Cart widget and the
 * WooCommerce form into the cart and the order.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

class Nexvio_Extra_Options_Widget extends Widget_Base {

	public function get_name() {
		return 'nexvio_extra_options';
	}

	public function get_title() {
		return esc_html__( 'Extra Options', 'nexvio' );
	}

	public function get_icon() {
		return 'eicon-form-horizontal';
	}

	public function get_categories() {
		return array( 'nexvio' );
	}

	public function get_keywords() {
		return array( 'nexvio', 'woocommerce', 'extra', 'options', 'add-ons', 'addons', 'custom fields', 'product fields', 'engraving', 'upload', 'single product' );
	}

	public function get_style_depends() {
		return array( 'nexvio-extra-options' );
	}

	public function get_script_depends() {
		return array( 'nexvio-extra-options' );
	}

	/* ------------------------------------------------------------------
	 * Controls
	 * ------------------------------------------------------------------ */

	private function color( $id, $label, $selectors, $extra = array() ) {
		$this->add_control( $id, array_merge( array(
			'label'     => $label,
			'type'      => Controls_Manager::COLOR,
			'selectors' => $selectors,
		), $extra ) );
	}

	protected function register_controls() {
		/* ============ CONTENT ============ */
		$this->start_controls_section( 'section_content', array( 'label' => esc_html__( 'Extra Options', 'nexvio' ) ) );
		$this->add_control( 'note', array(
			'type'            => Controls_Manager::RAW_HTML,
			'raw'             => esc_html__( 'The fields come from Nexvio > Extra Options. Put this widget in your single product template, above the Add to Cart widget.', 'nexvio' ),
			'content_classes' => 'elementor-descriptor',
		) );
		$this->add_control( 'title', array(
			'label'       => esc_html__( 'Heading', 'nexvio' ),
			'type'        => Controls_Manager::TEXT,
			'default'     => '',
			'placeholder' => esc_html__( 'Customize your product', 'nexvio' ),
			'label_block' => true,
		) );
		$this->add_control( 'descriptions', array(
			'label'        => esc_html__( 'Show field descriptions', 'nexvio' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		) );
		$this->add_control( 'totals', array(
			'label'   => esc_html__( 'Totals box', 'nexvio' ),
			'type'    => Controls_Manager::SELECT,
			'default' => '',
			'options' => array(
				''    => esc_html__( 'As in Nexvio > Extra Options', 'nexvio' ),
				'yes' => esc_html__( 'Show', 'nexvio' ),
				'no'  => esc_html__( 'Hide', 'nexvio' ),
			),
		) );
		$this->end_controls_section();

		/* ============ STYLE: GENERAL ============ */
		$this->start_controls_section( 'section_style_general', array( 'label' => esc_html__( 'General', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->color( 'accent', esc_html__( 'Accent colour', 'nexvio' ), array( '{{WRAPPER}} .nexvio-epo' => '--nxe-mc: {{VALUE}};' ) );
		$this->color( 'accent_text', esc_html__( 'Text on the accent colour', 'nexvio' ), array( '{{WRAPPER}} .nexvio-epo' => '--nxe-mc-t: {{VALUE}};' ) );
		$this->add_responsive_control( 'gap', array(
			'label'      => esc_html__( 'Space between fields', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
			'selectors'  => array( '{{WRAPPER}} .nexvio-epo' => '--nxe-gap: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_control( 'radius', array(
			'label'      => esc_html__( 'Corner radius', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
			'selectors'  => array( '{{WRAPPER}} .nexvio-epo' => '--nxe-r: {{SIZE}}{{UNIT}};' ),
		) );
		$this->color( 'text_color', esc_html__( 'Text colour', 'nexvio' ), array( '{{WRAPPER}} .nexvio-epo' => '--nxe-text: {{VALUE}};' ) );
		$this->color( 'muted_color', esc_html__( 'Secondary text colour', 'nexvio' ), array( '{{WRAPPER}} .nexvio-epo' => '--nxe-muted: {{VALUE}};' ) );
		$this->end_controls_section();

		/* ============ STYLE: HEADING + LABELS ============ */
		$this->start_controls_section( 'section_style_text', array( 'label' => esc_html__( 'Heading and labels', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'title_typo',
			'label'    => esc_html__( 'Heading typography', 'nexvio' ),
			'selector' => '{{WRAPPER}} .nexvio-epo__title',
		) );
		$this->color( 'title_color', esc_html__( 'Heading colour', 'nexvio' ), array( '{{WRAPPER}} .nexvio-epo__title' => 'color: {{VALUE}};' ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'      => 'label_typo',
			'label'     => esc_html__( 'Label typography', 'nexvio' ),
			'selector'  => '{{WRAPPER}} .nexvio-epo__label',
			'separator' => 'before',
		) );
		$this->color( 'label_color', esc_html__( 'Label colour', 'nexvio' ), array( '{{WRAPPER}} .nexvio-epo__label' => 'color: {{VALUE}};' ) );
		$this->add_responsive_control( 'label_gap', array(
			'label'      => esc_html__( 'Space under the label', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 30 ) ),
			'selectors'  => array( '{{WRAPPER}} .nexvio-epo__label' => 'margin-bottom: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'      => 'desc_typo',
			'label'     => esc_html__( 'Description typography', 'nexvio' ),
			'selector'  => '{{WRAPPER}} .nexvio-epo__desc',
			'separator' => 'before',
		) );
		$this->end_controls_section();

		/* ============ STYLE: INPUTS ============ */
		$this->start_controls_section( 'section_style_inputs', array( 'label' => esc_html__( 'Fields', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'input_typo',
			'selector' => '{{WRAPPER}} .nexvio-epo__input',
		) );
		$this->color( 'input_color', esc_html__( 'Text colour', 'nexvio' ), array( '{{WRAPPER}} .nexvio-epo__input' => 'color: {{VALUE}};' ) );
		$this->color( 'input_bg', esc_html__( 'Background', 'nexvio' ), array( '{{WRAPPER}} .nexvio-epo' => '--nxe-bg: {{VALUE}};' ) );
		$this->color( 'input_border', esc_html__( 'Border colour', 'nexvio' ), array( '{{WRAPPER}} .nexvio-epo' => '--nxe-line: {{VALUE}};' ) );
		$this->add_responsive_control( 'input_height', array(
			'label'      => esc_html__( 'Height', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 30, 'max' => 90 ) ),
			'selectors'  => array( '{{WRAPPER}} .nexvio-epo__input' => 'min-height: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'swatch_size', array(
			'label'      => esc_html__( 'Swatch size', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 20, 'max' => 100 ) ),
			'selectors'  => array( '{{WRAPPER}} .nexvio-epo__swface' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ),
		) );
		$this->end_controls_section();

		/* ============ STYLE: TOTALS ============ */
		$this->start_controls_section( 'section_style_totals', array( 'label' => esc_html__( 'Totals box', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'totals_typo',
			'selector' => '{{WRAPPER}} .nexvio-epo__totals',
		) );
		$this->color( 'totals_bg', esc_html__( 'Background', 'nexvio' ), array( '{{WRAPPER}} .nexvio-epo__totals' => 'background: {{VALUE}};' ) );
		$this->color( 'totals_color', esc_html__( 'Text colour', 'nexvio' ), array( '{{WRAPPER}} .nexvio-epo__totals' => 'color: {{VALUE}};' ) );
		$this->color( 'totals_final', esc_html__( 'Final price colour', 'nexvio' ), array( '{{WRAPPER}} .nexvio-epo__trow--final b' => 'color: {{VALUE}};' ) );
		$this->add_responsive_control( 'totals_pad', array(
			'label'      => esc_html__( 'Padding', 'nexvio' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em' ),
			'selectors'  => array( '{{WRAPPER}} .nexvio-epo__totals' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );
		$this->end_controls_section();

		$this->register_field_controls();
	}

	/** Every field of every published form, by id. */
	private function all_fields() {
		$out = array();
		if ( ! class_exists( 'Nexvio_Epo' ) ) {
			return $out;
		}
		foreach ( Nexvio_Epo::published_forms() as $form ) {
			foreach ( (array) $form['fields'] as $f ) {
				if ( is_array( $f ) && ! empty( $f['id'] ) && ! empty( $f['type'] ) && ! isset( $out[ $f['id'] ] ) ) {
					$out[ $f['id'] ] = $f;
				}
			}
		}
		return $out;
	}

	/**
	 * One small box of controls for every field you created. Add a field in Nexvio > Extra Options
	 * and its box appears here; remove it and the box goes away. Each box only has what that
	 * kind of field can use.
	 */
	private function register_field_controls() {
		$fields = $this->all_fields();
		$types  = Nexvio_Epo::types();
		$choice = Nexvio_Epo::choice_types();

		$this->start_controls_section( 'section_fields_intro', array( 'label' => esc_html__( 'Each field', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_control( 'fields_note', array(
			'type'            => Controls_Manager::RAW_HTML,
			'raw'             => $fields
				? esc_html__( 'Every field you create in Nexvio > Extra Options has its own box below, with only the controls that field needs. Leave a control empty to use the general style.', 'nexvio' )
				: esc_html__( 'No fields yet. Create a form in Nexvio > Extra Options and add fields: each one gets its own style controls here.', 'nexvio' ),
			'content_classes' => 'elementor-descriptor',
		) );
		$this->end_controls_section();

		foreach ( $fields as $id => $f ) {
			$key   = 'fx_' . preg_replace( '/[^a-z0-9_]/', '', strtolower( $id ) );
			$type  = $f['type'];
			$tname = isset( $types[ $type ] ) ? $types[ $type ][0] : $type;
			$label = isset( $f['label'] ) && '' !== trim( (string) $f['label'] ) ? $f['label'] : $tname;
			$sel   = '{{WRAPPER}} .nexvio-epo__field[data-field="' . esc_attr( $id ) . '"]';

			$this->start_controls_section( $key, array(
				/* translators: 1: field name, 2: field type */
				'label' => sprintf( esc_html__( '%1$s (%2$s)', 'nexvio' ), wp_strip_all_tags( $label ), $tname ),
				'tab'   => Controls_Manager::TAB_STYLE,
			) );

			if ( 'divider' === $type ) {
				$this->color( $key . '_line', esc_html__( 'Line colour', 'nexvio' ), array( $sel . ' hr' => 'border-color: {{VALUE}}; background-color: {{VALUE}};' ) );
			} elseif ( 'header' === $type ) {
				$hs = $sel . ' h2, ' . $sel . ' h3, ' . $sel . ' h4';
				$this->color( $key . '_color', esc_html__( 'Text colour', 'nexvio' ), array( $hs => 'color: {{VALUE}};' ) );
				$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => $key . '_typo', 'selector' => $hs ) );
			} elseif ( 'faq' === $type ) {
				$fq = $sel . ' .nexvio-faq';
				$this->color( $key . '_accent', esc_html__( 'Main colour', 'nexvio' ), array( $fq => '--nxf-accent: {{VALUE}};' ) );
				$this->color( $key . '_qbg', esc_html__( 'Question background', 'nexvio' ), array( $fq => '--nxf-q-bg: {{VALUE}}; --nxf-q-open: {{VALUE}};' ) );
				$this->color( $key . '_qc', esc_html__( 'Question text', 'nexvio' ), array( $sel . ' .nexvio-faq__q' => 'color: {{VALUE}};' ) );
				$this->color( $key . '_abg', esc_html__( 'Answer background', 'nexvio' ), array( $fq => '--nxf-a-bg: {{VALUE}};' ) );
				$this->color( $key . '_ac', esc_html__( 'Answer text', 'nexvio' ), array( $sel . ' .nexvio-faq__a' => 'color: {{VALUE}};' ) );
			} else {
				$this->color( $key . '_label', esc_html__( 'Label colour', 'nexvio' ), array( $sel . ' .nexvio-epo__label' => 'color: {{VALUE}};' ) );
				$this->color( $key . '_text', esc_html__( 'Text colour', 'nexvio' ), array( $sel => '--nxe-text: {{VALUE}};' ) );
				$this->color( $key . '_bg', esc_html__( 'Background', 'nexvio' ), array( $sel => '--nxe-bg: {{VALUE}};' ) );
				$this->color( $key . '_line', esc_html__( 'Border colour', 'nexvio' ), array( $sel => '--nxe-line: {{VALUE}};' ) );
				if ( in_array( $type, $choice, true ) || in_array( $type, array( 'color', 'range', 'number' ), true ) ) {
					$this->color( $key . '_accent', esc_html__( 'Highlight colour', 'nexvio' ), array( $sel => '--nxe-mc: {{VALUE}};' ) );
				}
				$this->add_control( $key . '_radius', array(
					'label'      => esc_html__( 'Corner radius', 'nexvio' ),
					'type'       => Controls_Manager::SLIDER,
					'size_units' => array( 'px' ),
					'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
					'selectors'  => array( $sel => '--nxe-r: {{SIZE}}{{UNIT}};' ),
				) );
			}
			$this->end_controls_section();
		}
	}

	/* ------------------------------------------------------------------
	 * Product
	 * ------------------------------------------------------------------ */

	private function current_product() {
		$id = 0;
		if ( function_exists( 'is_singular' ) && is_singular( 'product' ) ) {
			$id = (int) get_queried_object_id();
		}
		if ( ! $id ) {
			global $product;
			if ( $product instanceof WC_Product ) {
				return $product;
			}
			$post_id = get_the_ID();
			if ( $post_id && 'product' === get_post_type( $post_id ) ) {
				$id = (int) $post_id;
			}
		}
		if ( ! $id ) {
			$is_edit = class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode();
			$preview = isset( $_GET['elementor-preview'] ) || isset( $_GET['elementor_library'] ); // phpcs:ignore WordPress.Security.NonceVerification
			if ( $is_edit || $preview || wp_doing_ajax() ) {
				/* in a template: preview with the latest product that has extra options */
				$latest = get_posts( array( 'post_type' => 'product', 'post_status' => 'publish', 'numberposts' => 20, 'fields' => 'ids' ) );
				foreach ( $latest as $pid ) {
					if ( Nexvio_Epo::fields_for( $pid ) ) {
						$id = (int) $pid;
						break;
					}
				}
				if ( ! $id && $latest ) {
					$id = (int) $latest[0];
				}
			}
		}
		return $id && function_exists( 'wc_get_product' ) ? wc_get_product( $id ) : null;
	}

	private function note( $text ) {
		$is_edit = class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode();
		if ( $is_edit ) {
			echo '<div class="nexvio-epo-note">' . esc_html( $text ) . ' <a href="' . esc_url( admin_url( 'admin.php?page=nexvio-epo' ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'Open Extra Options', 'nexvio' ) . '</a></div>';
		}
	}

	protected function render() {
		$s       = $this->get_settings_for_display();
		$product = $this->current_product();
		if ( ! $product ) {
			$this->note( __( 'Extra Options: open a product to see its fields.', 'nexvio' ) );
			return;
		}
		$html = Nexvio_Epo_Render::html( $product, array(
			'title'        => isset( $s['title'] ) ? $s['title'] : '',
			'totals'       => isset( $s['totals'] ) ? $s['totals'] : '',
			'descriptions' => isset( $s['descriptions'] ) && 'yes' === $s['descriptions'] ? 'yes' : 'no',
		) );
		if ( '' === $html ) {
			$this->note( __( 'No extra options apply to this product yet. Create a form in Nexvio > Extra Options.', 'nexvio' ) );
			return;
		}
		echo wp_kses( $html, Nexvio_Epo_Render::allowed() );
	}
}
