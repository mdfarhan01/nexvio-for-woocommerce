<?php
 if ( ! defined( 'ABSPATH' ) ) { exit; } use Elementor\Widget_Base; use Elementor\Controls_Manager; use Elementor\Group_Control_Typography; class Nexvio_Extra_Options_Widget extends Widget_Base { public function get_name() { return 'nexvio_extra_options'; } public function get_title() { return esc_html__( 'Extra Options', 'nexvio' ); } public function get_icon() { return 'eicon-form-horizontal'; } public function get_categories() { return array( 'nexvio' ); } public function get_keywords() { return array( 'nexvio', 'woocommerce', 'extra', 'options', 'add-ons', 'addons', 'custom fields', 'product fields', 'engraving', 'upload', 'single product' ); } public function get_style_depends() { return array( 'nexvio-extra-options' ); } public function get_script_depends() { return array( 'nexvio-extra-options' ); } private function color( $id, $label, $selectors, $extra = array() ) { $this->add_control( $id, array_merge( array( 'label' => $label, 'type' => Controls_Manager::COLOR, 'selectors' => $selectors, ), $extra ) ); } private function typo( $id, $label, $sel, $sep = '' ) {
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => $id, 'label' => $label, 'selector' => $sel, 'separator' => $sep ) );
	}

	private function slider( $id, $label, $sel, $css, $min, $max ) {
		$this->add_responsive_control( $id, array(
			'label'      => $label,
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => $min, 'max' => $max ) ),
			'selectors'  => array( $sel => str_replace( '{V}', '{{SIZE}}{{UNIT}}', $css ) ),
		) );
	}

	/* one selector list for "{{WRAPPER}} .a, {{WRAPPER}} .b" */
	private function sel( $list, $suffix = '' ) {
		$out = array();
		foreach ( $list as $s ) {
			$out[] = '{{WRAPPER}} ' . $s . $suffix;
		}
		return implode( ', ', $out );
	}

	protected function register_controls() {
		$w = '{{WRAPPER}} .nexvio-epo';

		$this->start_controls_section( 'section_content', array( 'label' => esc_html__( 'Extra Options', 'nexvio' ) ) );
		$this->add_control( 'note', array( 'type' => Controls_Manager::RAW_HTML, 'raw' => esc_html__( 'The fields come from Nexvio > Extra Options. Put this widget in your single product template, above the Add to Cart widget.', 'nexvio' ), 'content_classes' => 'elementor-descriptor' ) );
		$this->add_control( 'title', array( 'label' => esc_html__( 'Heading', 'nexvio' ), 'type' => Controls_Manager::TEXT, 'default' => '', 'placeholder' => esc_html__( 'Customize your product', 'nexvio' ), 'label_block' => true ) );
		$this->add_control( 'descriptions', array( 'label' => esc_html__( 'Show field descriptions', 'nexvio' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ) );
		$this->add_control( 'totals', array( 'label' => esc_html__( 'Totals box', 'nexvio' ), 'type' => Controls_Manager::SELECT, 'default' => '', 'options' => array( '' => esc_html__( 'As in Nexvio > Extra Options', 'nexvio' ), 'yes' => esc_html__( 'Show', 'nexvio' ), 'no' => esc_html__( 'Hide', 'nexvio' ) ) ) );
		$this->end_controls_section();

		/* ONE global style: every change here is applied to all field types (text, dropdown, radio, checkbox, swatch ...) */
		$this->start_controls_section( 'section_style_text', array( 'label' => esc_html__( 'Text and colours (all fields)', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_control( 'global_note', array( 'type' => Controls_Manager::RAW_HTML, 'raw' => esc_html__( 'One style for all field types. A change here updates every field: text, dropdown, radio, checkbox, swatch, number, date and more.', 'nexvio' ), 'content_classes' => 'elementor-panel-alert elementor-panel-alert-info' ) );
		$this->typo( 'font_typo', esc_html__( 'Font (all text)', 'nexvio' ), $w );
		$this->color( 'text_color', esc_html__( 'Text colour', 'nexvio' ), array( $w => '--nxe-text: {{VALUE}};' ) );
		$this->color( 'muted_color', esc_html__( 'Secondary text colour', 'nexvio' ), array( $w => '--nxe-muted: {{VALUE}};' ) );
		$this->typo( 'title_typo', esc_html__( 'Heading typography', 'nexvio' ), $w . '__title', 'before' );
		$this->color( 'title_color', esc_html__( 'Heading colour', 'nexvio' ), array( $w . '__title' => 'color: {{VALUE}};' ) );
		$this->typo( 'label_typo', esc_html__( 'Label typography', 'nexvio' ), $w . '__label, ' . $w . '__heading', 'before' );
		$this->color( 'label_color', esc_html__( 'Label colour', 'nexvio' ), array( $w . '__label, ' . $w . '__heading' => 'color: {{VALUE}};' ) );
		$this->color( 'req_color', esc_html__( 'Required star colour', 'nexvio' ), array( $w . '__req' => 'color: {{VALUE}};' ) );
		$this->typo( 'desc_typo', esc_html__( 'Description typography', 'nexvio' ), $w . '__desc', 'before' );
		$this->color( 'desc_color', esc_html__( 'Description colour', 'nexvio' ), array( $w . '__desc' => 'color: {{VALUE}};' ) );
		$this->color( 'price_color', esc_html__( 'Price colour', 'nexvio' ), array( $w . '__cprice, ' . $w . '__fprice' => 'color: {{VALUE}};' ) );
		$this->end_controls_section();

		$this->start_controls_section( 'section_style_fields', array( 'label' => esc_html__( 'Fields (all types)', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$box = array( '.nexvio-epo__input', '.nexvio-epo__num', '.nexvio-epo__colorwrap', '.nexvio-epo__choices--buttons .nexvio-epo__choice' );
		$this->start_controls_tabs( 'field_tabs' );
		$this->start_controls_tab( 'field_tab_n', array( 'label' => esc_html__( 'Normal', 'nexvio' ) ) );
		$this->color( 'input_color', esc_html__( 'Field text colour', 'nexvio' ), array( $w => '--nxe-text: {{VALUE}};' ) );
		$this->color( 'input_bg', esc_html__( 'Field background', 'nexvio' ), array( $w => '--nxe-bg: {{VALUE}};' ) );
		$this->color( 'input_border', esc_html__( 'Border colour', 'nexvio' ), array( $w => '--nxe-line: {{VALUE}};' ) );
		$this->end_controls_tab();
		$this->start_controls_tab( 'field_tab_h', array( 'label' => esc_html__( 'Hover', 'nexvio' ) ) );
		$this->color( 'hover_border', esc_html__( 'Hover colour', 'nexvio' ), array( $this->sel( array_merge( $box, array( '.nexvio-epo__ubtn' ) ), ':hover' ) => 'border-color: {{VALUE}};', $this->sel( array( '.nexvio-epo__choice' ), ':hover .nexvio-epo__mark' ) => 'border-color: {{VALUE}};' ) );
		$this->end_controls_tab();
		$this->start_controls_tab( 'field_tab_a', array( 'label' => esc_html__( 'Active', 'nexvio' ) ) );
		$this->color( 'accent', esc_html__( 'Active colour (focus, selected, ticks, swatch ring, slider)', 'nexvio' ), array( $w => '--nxe-mc: {{VALUE}};' ) );
		$this->color( 'accent_text', esc_html__( 'Text on the active colour', 'nexvio' ), array( $w => '--nxe-mc-t: {{VALUE}};' ) );
		$this->end_controls_tab();
		$this->end_controls_tabs();
		$this->typo( 'input_typo', esc_html__( 'Field text typography', 'nexvio' ), $w . '__input, ' . $w . '__text, ' . $w . '__colorval, ' . $w . '__ubtn', 'before' );
		$this->slider( 'radius', esc_html__( 'Corner radius', 'nexvio' ), $w, '--nxe-r: {V};', 0, 40 );
		$this->slider( 'input_height', esc_html__( 'Field height', 'nexvio' ), $w . '__input, ' . $w . '__step', 'min-height: {V};', 30, 90 );
		$this->slider( 'mark_size', esc_html__( 'Radio and checkbox size', 'nexvio' ), $w . '__mark', 'width: {V}; height: {V};', 12, 40 );
		$this->slider( 'swatch_size', esc_html__( 'Swatch size', 'nexvio' ), $w . '__swface', 'width: {V}; height: {V};', 20, 100 );
		$this->end_controls_section();

		$this->start_controls_section( 'section_style_gap', array( 'label' => esc_html__( 'Spacing (all fields)', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->slider( 'gap', esc_html__( 'Space between fields', 'nexvio' ), $w, '--nxe-gap: {V};', 0, 60 );
		$this->slider( 'label_gap', esc_html__( 'Space under the label', 'nexvio' ), $w . '__label', 'margin-bottom: {V};', 0, 30 );
		$this->slider( 'choice_gap', esc_html__( 'Space between options', 'nexvio' ), $w . '__choices, ' . $w . '__swatches', 'gap: {V};', 0, 40 );
		$this->end_controls_section();

		$this->start_controls_section( 'section_style_totals', array( 'label' => esc_html__( 'Totals box', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->typo( 'totals_typo', esc_html__( 'Typography', 'nexvio' ), $w . '__totals' );
		$this->color( 'totals_bg', esc_html__( 'Background', 'nexvio' ), array( $w . '__totals' => 'background: {{VALUE}};' ) );
		$this->color( 'totals_color', esc_html__( 'Text colour', 'nexvio' ), array( $w . '__totals' => 'color: {{VALUE}};' ) );
		$this->color( 'totals_final', esc_html__( 'Final price colour', 'nexvio' ), array( $w . '__trow--final b' => 'color: {{VALUE}};' ) );
		$this->add_responsive_control( 'totals_pad', array( 'label' => esc_html__( 'Padding', 'nexvio' ), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => array( 'px', 'em' ), 'selectors' => array( $w . '__totals' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ) ) );
		$this->end_controls_section();
	}
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
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only request state (filter, sort, page or preview); nothing is saved, so no nonce is needed.
$preview = isset( $_GET['elementor-preview'] ) || isset( $_GET['elementor_library'] );
if ( $is_edit || $preview || wp_doing_ajax() ) {
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
$s = $this->get_settings_for_display();
$product = $this->current_product();
if ( ! $product ) {
$this->note( __( 'Extra Options: open a product to see its fields.', 'nexvio' ) );
return;
}
$html = Nexvio_Epo_Render::html( $product, array( 'title' => isset( $s['title'] ) ? $s['title'] : '', 'totals' => isset( $s['totals'] ) ? $s['totals'] : '', 'descriptions' => isset( $s['descriptions'] ) && 'yes' === $s['descriptions'] ? 'yes' : 'no', ) );
if ( '' === $html ) {
$this->note( __( 'No extra options apply to this product yet. Create a form in Nexvio > Extra Options.', 'nexvio' ) );
return;
}
echo wp_kses( $html, Nexvio_Epo_Render::allowed() );
}
} 