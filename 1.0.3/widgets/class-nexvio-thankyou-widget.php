<?php
 if ( ! defined( 'ABSPATH' ) ) {
exit;
}
use Elementor\Widget_Base;
use Elementor\Controls_Manager;
class Nexvio_Thankyou_Widget extends Widget_Base {
public function get_name() {
return 'nexvio_thankyou';
}
public function get_title() {
return esc_html__( 'Thank You', 'nexvio' );
}
public function get_icon() {
return 'eicon-check-circle';
}
public function get_categories() {
return array( 'nexvio' );
}
public function show_in_panel() {
return false;
}
public function get_keywords() {
return array( 'nexvio', 'thank you', 'thankyou', 'order', 'confirmation', 'received', 'checkout' );
}
public function get_style_depends() {
return array( 'nexvio' );
}
protected function register_controls() {
$this->start_controls_section( 'section_design', array( 'label' => esc_html__( 'Design', 'nexvio' ) ) );
$opts = array( '0' => esc_html__( 'The design chosen in Checkout Fields > Thank you', 'nexvio' ) );
foreach ( Nexvio_Thankyou::designs() as $id => $d ) {
$opts[ (string) $id ] = $id . '. ' . $d[0];
}
$this->add_control( 'design', array( 'label' => esc_html__( 'Design', 'nexvio' ), 'type' => Controls_Manager::SELECT, 'default' => '0', 'options' => $opts, ) );
$this->add_control( 'accent', array( 'label' => esc_html__( 'Main colour', 'nexvio' ), 'type' => Controls_Manager::COLOR, ) );
$this->add_control( 'note', array( 'type' => Controls_Manager::RAW_HTML, 'raw' => esc_html__( 'In the editor you see an example order. Visitors see their own order.', 'nexvio' ), 'content_classes' => 'elementor-descriptor', ) );
$this->end_controls_section();
$this->start_controls_section( 'section_text', array( 'label' => esc_html__( 'Text', 'nexvio' ) ) );
$this->add_control( 'title', array( 'label' => esc_html__( 'Headline', 'nexvio' ), 'type' => Controls_Manager::TEXT, 'placeholder' => esc_html__( 'Same as in the dashboard. You can use {name} and {order}.', 'nexvio' ), 'dynamic' => array( 'active' => true ), ) );
$this->add_control( 'message', array( 'label' => esc_html__( 'Message', 'nexvio' ), 'type' => Controls_Manager::TEXTAREA, 'placeholder' => esc_html__( 'Same as in the dashboard. You can use {name}, {order}, {email}, {total}.', 'nexvio' ), ) );
$this->add_control( 'btn_text', array( 'label' => esc_html__( 'Button text', 'nexvio' ), 'type' => Controls_Manager::TEXT, ) );
$this->add_control( 'btn_url', array( 'label' => esc_html__( 'Button link', 'nexvio' ), 'type' => Controls_Manager::URL, ) );
$this->end_controls_section();
$this->start_controls_section( 'section_show', array( 'label' => esc_html__( 'Show', 'nexvio' ) ) );
$toggles = array( 'show_steps' => esc_html__( 'Order progress timeline', 'nexvio' ), 'show_items' => esc_html__( 'Ordered products', 'nexvio' ), 'show_addr' => esc_html__( 'Addresses', 'nexvio' ), 'show_extras' => esc_html__( 'Checkout field answers', 'nexvio' ), 'show_orders' => esc_html__( '"View my orders" button', 'nexvio' ), );
foreach ( $toggles as $k => $label ) {
$this->add_control( $k, array( 'label' => $label, 'type' => Controls_Manager::SWITCHER, 'default' => 'yes', 'return_value' => 'yes', ) );
}
$this->end_controls_section();
}
protected function render() {
$st = $this->get_settings_for_display();
$override = array();
foreach ( array( 'title', 'message', 'btn_text' ) as $k ) {
if ( ! empty( $st[ $k ] ) ) {
$override[ $k ] = $st[ $k ];
}
}
if ( ! empty( $st['btn_url']['url'] ) ) {
$override['btn_url'] = esc_url_raw( $st['btn_url']['url'] );
}
foreach ( array( 'show_steps', 'show_items', 'show_addr', 'show_extras', 'show_orders' ) as $k ) {
$override[ $k ] = isset( $st[ $k ] ) && 'yes' === $st[ $k ] ? 1 : 0;
}
$edit = false;
if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->editor ) ) {
$edit = \Elementor\Plugin::$instance->editor->is_edit_mode() || ( isset( \Elementor\Plugin::$instance->preview ) && \Elementor\Plugin::$instance->preview->is_preview_mode() );
}
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup is built by Nexvio_Thankyou, which escapes every dynamic value.
echo Nexvio_Thankyou::output( array( 'design' => (int) $st['design'], 'accent' => ! empty( $st['accent'] ) ? sanitize_hex_color( $st['accent'] ) : '', 'override' => $override, 'demo' => $edit, ) );
}
} 