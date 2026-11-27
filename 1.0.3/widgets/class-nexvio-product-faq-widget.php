<?php
 if ( ! defined( 'ABSPATH' ) ) {
exit;
}
use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Box_Shadow;
class Nexvio_Product_Faq_Widget extends Widget_Base {
public function get_name() {
return 'nexvio_product_faq';
}
public function get_title() {
return esc_html__( 'Product FAQ', 'nexvio' );
}
public function get_icon() {
return 'eicon-help-o';
}
public function get_categories() {
return array( 'nexvio' );
}
public function get_keywords() {
return array( 'nexvio', 'faq', 'questions', 'answers', 'accordion', 'product', 'single product', 'help' );
}
public function get_style_depends() {
return array( 'nexvio-product-faq' );
}
private function sel( $parts ) {
return '{{WRAPPER}} ' . $parts;
}
public static function designs() {
return array( 'cards' => array( __( 'Cards', 'nexvio' ), __( 'A tinted question bar and the answer on its own background.', 'nexvio' ) ), 'lines' => array( __( 'Lines', 'nexvio' ), __( 'Clean and light, only thin lines between items.', 'nexvio' ) ), 'filled' => array( __( 'Filled', 'nexvio' ), __( 'Soft coloured blocks.', 'nexvio' ) ), 'accent' => array( __( 'Accent bar', 'nexvio' ), __( 'A coloured edge on the left of every item.', 'nexvio' ) ), 'pills' => array( __( 'Pills', 'nexvio' ), __( 'Rounded question bars with a round badge.', 'nexvio' ) ), 'dark' => array( __( 'Dark bars', 'nexvio' ), __( 'Strong dark question bars, light answers.', 'nexvio' ) ), 'numbered' => array( __( 'Numbered', 'nexvio' ), __( 'Big 01, 02, 03 numbers in front of each item.', 'nexvio' ) ), 'soft' => array( __( 'Soft shadow', 'nexvio' ), __( 'White floating cards with a soft shadow.', 'nexvio' ) ), 'outline' => array( __( 'Outline', 'nexvio' ), __( 'Coloured borders, no fill.', 'nexvio' ) ), 'tiles' => array( __( 'Tiles', 'nexvio' ), __( 'A grid of feature tiles. Great for product features.', 'nexvio' ) ), );
}
private function layout_options() {
$out = array();
foreach ( self::designs() as $key => $l ) {
$img = 'assets/img/layouts/faq-' . $key . '.svg';
$out[ $key ] = array( 'title' => $l[0], 'desc' => $l[1], 'image' => NEXVIO_URL . $img . '?v=' . Nexvio_Plugin::ver( $img ), );
}
return $out;
}
private function colour( $id, $label, $var, $extra = array() ) {
$this->add_control( $id, array_merge( array( 'label' => $label, 'type' => Controls_Manager::COLOR, 'selectors' => array( $this->sel( '.nexvio-faq' ) => $var . ': {{VALUE}};' ), ), $extra ) );
}
protected function register_controls() {
$this->start_controls_section( 'section_content', array( 'label' => esc_html__( 'Content', 'nexvio' ) ) );
$this->add_control( 'note', array( 'type' => Controls_Manager::RAW_HTML, 'raw' => esc_html__( 'The list comes from the "FAQ / Features" fields in Nexvio > Extra Options and from the FAQ tab on each product.', 'nexvio' ), 'content_classes' => 'elementor-descriptor', ) );
$this->add_control( 'title', array( 'label' => esc_html__( 'Heading', 'nexvio' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'Frequently asked questions', 'nexvio' ), 'description' => esc_html__( 'Leave empty for no heading.', 'nexvio' ), 'label_block' => true, ) );
$this->add_control( 'title_tag', array( 'label' => esc_html__( 'Heading tag', 'nexvio' ), 'type' => Controls_Manager::SELECT, 'default' => 'h3', 'options' => array( 'h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4', 'div' => 'div' ), ) );
$this->add_control( 'group_titles', array( 'label' => esc_html__( 'Show each list title', 'nexvio' ), 'description' => esc_html__( 'The title you gave to each FAQ / Features field.', 'nexvio' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => '', ) );
$this->add_control( 'schema', array( 'label' => esc_html__( 'FAQ schema for search engines', 'nexvio' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes', ) );
$this->end_controls_section();
$this->start_controls_section( 'section_design', array( 'label' => esc_html__( 'Design', 'nexvio' ) ) );
$has_lc = class_exists( 'Nexvio_Layout_Control' );
$this->add_control( 'layout', array( 'label' => esc_html__( 'Design', 'nexvio' ), 'type' => $has_lc ? Nexvio_Layout_Control::TYPE : Controls_Manager::SELECT, 'default' => 'cards', 'options' => $has_lc ? $this->layout_options() : array_map( function ( $d ) {
return $d[0];
}
, self::designs() ), 'description' => esc_html__( 'Click a picture to choose the design. Colours, spacing and text can be changed in the Style tab.', 'nexvio' ), ) );
$this->add_control( 'badge', array( 'label' => esc_html__( 'Badge', 'nexvio' ), 'type' => Controls_Manager::SELECT, 'default' => 'auto', 'options' => array( 'auto' => esc_html__( 'Automatic (set in the field)', 'nexvio' ), 'qa' => esc_html__( 'Q and A letters', 'nexvio' ), 'number' => esc_html__( 'Numbers 1, 2, 3', 'nexvio' ), 'check' => esc_html__( 'Check marks', 'nexvio' ), 'none' => esc_html__( 'No badge', 'nexvio' ), ), ) );
$this->add_control( 'opening', array( 'label' => esc_html__( 'How items open', 'nexvio' ), 'type' => Controls_Manager::SELECT, 'default' => '', 'options' => array( '' => esc_html__( 'As set in the field', 'nexvio' ), 'accordion' => esc_html__( 'One at a time', 'nexvio' ), 'multi' => esc_html__( 'Several at once', 'nexvio' ), 'static' => esc_html__( 'Always open (no toggle)', 'nexvio' ), ), ) );
$this->add_responsive_control( 'columns', array( 'label' => esc_html__( 'Columns', 'nexvio' ), 'type' => Controls_Manager::SELECT, 'default' => '', 'options' => array( '' => esc_html__( 'Automatic', 'nexvio' ), '1' => '1', '2' => '2', '3' => '3', '4' => '4' ), 'selectors' => array( $this->sel( '.nexvio-faq' ) => '--nxf-cols: {{VALUE}};' ), ) );
$this->add_control( 'icon', array( 'label' => esc_html__( 'Open / close icon', 'nexvio' ), 'type' => Controls_Manager::SELECT, 'default' => 'plus', 'options' => array( 'plus' => esc_html__( 'Plus / minus', 'nexvio' ), 'chevron' => esc_html__( 'Arrow', 'nexvio' ) ), ) );
$this->add_control( 'icon_side', array( 'label' => esc_html__( 'Icon position', 'nexvio' ), 'type' => Controls_Manager::SELECT, 'default' => 'right', 'options' => array( 'right' => esc_html__( 'Right', 'nexvio' ), 'left' => esc_html__( 'Left', 'nexvio' ) ), ) );
$this->end_controls_section();
$this->start_controls_section( 'style_colours', array( 'label' => esc_html__( 'Colours', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
$this->colour( 'accent', esc_html__( 'Main colour', 'nexvio' ), '--nxf-accent' );
$this->colour( 'q_bg', esc_html__( 'Question background', 'nexvio' ), '--nxf-q-bg' );
$this->colour( 'q_open_bg', esc_html__( 'Question background (open)', 'nexvio' ), '--nxf-q-open' );
$this->add_control( 'c_q', array( 'label' => esc_html__( 'Question text', 'nexvio' ), 'type' => Controls_Manager::COLOR, 'selectors' => array( $this->sel( '.nexvio-faq__q' ) => 'color: {{VALUE}};' ) ) );
$this->colour( 'a_bg', esc_html__( 'Answer background', 'nexvio' ), '--nxf-a-bg' );
$this->add_control( 'c_a', array( 'label' => esc_html__( 'Answer text', 'nexvio' ), 'type' => Controls_Manager::COLOR, 'selectors' => array( $this->sel( '.nexvio-faq__a' ) => 'color: {{VALUE}};' ) ) );
$this->colour( 'item_bg', esc_html__( 'Card background', 'nexvio' ), '--nxf-bg' );
$this->colour( 'item_open_bg', esc_html__( 'Fill colour', 'nexvio' ), '--nxf-open', array( 'condition' => array( 'layout' => 'filled' ) ) );
$this->colour( 'line', esc_html__( 'Border colour', 'nexvio' ), '--nxf-line' );
$this->colour( 'badge_bg', esc_html__( 'Badge background', 'nexvio' ), '--nxf-badge-bg', array( 'condition' => array( 'badge!' => 'none' ) ) );
$this->colour( 'badge_c', esc_html__( 'Badge text', 'nexvio' ), '--nxf-badge-c', array( 'condition' => array( 'badge!' => 'none' ) ) );
$this->add_control( 'c_title', array( 'label' => esc_html__( 'Heading colour', 'nexvio' ), 'type' => Controls_Manager::COLOR, 'separator' => 'before', 'selectors' => array( $this->sel( '.nexvio-faq__heading, ' ) . '{{WRAPPER}} .nexvio-faq__title' => 'color: {{VALUE}};' ) ) );
$this->end_controls_section();
$this->start_controls_section( 'style_text', array( 'label' => esc_html__( 'Typography', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 't_title', 'label' => esc_html__( 'Heading', 'nexvio' ), 'selector' => $this->sel( '.nexvio-faq__heading, ' ) . '{{WRAPPER}} .nexvio-faq__title' ) );
$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 't_q', 'label' => esc_html__( 'Question', 'nexvio' ), 'selector' => $this->sel( '.nexvio-faq__q' ) ) );
$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 't_a', 'label' => esc_html__( 'Answer', 'nexvio' ), 'selector' => $this->sel( '.nexvio-faq__a' ) ) );
$this->end_controls_section();
$this->start_controls_section( 'style_box', array( 'label' => esc_html__( 'Spacing and shape', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
$this->add_responsive_control( 'gap', array( 'label' => esc_html__( 'Space between items', 'nexvio' ), 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 0, 'max' => 40 ) ), 'selectors' => array( $this->sel( '.nexvio-faq' ) => '--nxf-gap: {{SIZE}}{{UNIT}};' ), ) );
$this->add_responsive_control( 'radius', array( 'label' => esc_html__( 'Corner radius', 'nexvio' ), 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 0, 'max' => 40 ) ), 'selectors' => array( $this->sel( '.nexvio-faq' ) => '--nxf-r: {{SIZE}}{{UNIT}};' ), ) );
$this->add_responsive_control( 'pad', array( 'label' => esc_html__( 'Inner space', 'nexvio' ), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => array( 'px', 'em' ), 'selectors' => array( $this->sel( '.nexvio-faq__q' ) => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};', $this->sel( '.nexvio-faq__a' ) => 'padding-left: {{LEFT}}{{UNIT}}; padding-right: {{RIGHT}}{{UNIT}};', ), ) );
$this->add_group_control( Group_Control_Box_Shadow::get_type(), array( 'name' => 'shadow', 'selector' => $this->sel( '.nexvio-faq__item' ) ) );
$this->end_controls_section();
}
private function current_product_id() {
$id = 0;
if ( function_exists( 'is_singular' ) && is_singular( 'product' ) ) {
$id = (int) get_queried_object_id();
}
if ( ! $id ) {
global $product;
if ( $product instanceof WC_Product ) {
return $product->get_id();
}
$post_id = get_the_ID();
if ( $post_id && 'product' === get_post_type( $post_id ) ) {
$id = (int) $post_id;
}
}
return $id;
}
private function is_editor() {
return class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode();
}
protected function render() {
$s = $this->get_settings_for_display();
$pid = $this->current_product_id();
$blocks = $pid ? Nexvio_Epo_Faq::blocks( $pid ) : array();
$sample = false;
if ( ! $blocks && $this->is_editor() ) {
$sample = true;
$blocks = array( array( 'title' => esc_html__( 'Shipping', 'nexvio' ), 'kind' => 'faq', 'style' => 'accordion', 'open_first' => true, 'own' => false, 'items' => array( array( 'id' => 's1', 'q' => esc_html__( 'How long does delivery take?', 'nexvio' ), 'a' => esc_html__( 'Orders ship within 1-2 business days. Delivery usually takes 3-5 days.', 'nexvio' ) ), array( 'id' => 's2', 'q' => esc_html__( 'Can I return the product?', 'nexvio' ), 'a' => esc_html__( 'Yes. You can return it within 30 days in its original packaging.', 'nexvio' ) ), array( 'id' => 's3', 'q' => esc_html__( 'Is it a gift?', 'nexvio' ), 'a' => esc_html__( 'Choose gift wrap in the extra options and we add a card.', 'nexvio' ) ), ), ) );
}
if ( ! $blocks ) {
return;
}
$classes = 'nexvio-faq nexvio-faq--' . ( in_array( $s['layout'], Nexvio_Epo_Faq::looks(), true ) ? $s['layout'] : 'cards' ) . ' nexvio-faq--icon-' . ( 'chevron' === $s['icon'] ? 'chevron' : 'plus' ) . ' nexvio-faq--side-' . ( 'left' === $s['icon_side'] ? 'left' : 'right' );
$tag = in_array( $s['title_tag'], array( 'h2', 'h3', 'h4', 'div' ), true ) ? $s['title_tag'] : 'h3';
$many = 'yes' === $s['group_titles'];
echo '<div class="' . esc_attr( $classes ) . '">';
if ( '' !== trim( (string) $s['title'] ) ) {
echo '<' . tag_escape( $tag ) . ' class="nexvio-faq__heading">' . esc_html( $s['title'] ) . '</' . tag_escape( $tag ) . '>';
}
foreach ( $blocks as $i => $b ) {
if ( ! $many ) {
$b['title'] = '';
}
if ( ! empty( $s['opening'] ) && in_array( $s['opening'], array( 'accordion', 'multi', 'static' ), true ) ) {
$b['style'] = $s['opening'];
}
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup is built by Nexvio_Epo_Faq, which escapes every dynamic value.
echo Nexvio_Epo_Faq::block_html( $b, $this->get_id() . $i, 'h4', isset( $s['badge'] ) ? $s['badge'] : 'auto' );
}
echo '</div>';
if ( ! $sample && 'yes' === $s['schema'] ) {
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup is built by Nexvio_Epo_Faq, which escapes every dynamic value.
echo Nexvio_Epo_Faq::schema( $blocks );
}
}
} 