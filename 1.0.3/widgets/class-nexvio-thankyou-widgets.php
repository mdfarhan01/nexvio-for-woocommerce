<?php
 if ( ! defined( 'ABSPATH' ) ) {
exit;
}
use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Background;
abstract class Nexvio_Thankyou_Base_Widget extends Widget_Base {
abstract protected function design_id();
public function get_name() {
return Nexvio_Thankyou::widget_name( $this->design_id() );
}
public function get_title() {
$d = Nexvio_Thankyou::designs();
return sprintf( /* translators: %s: design name. */ esc_html__( 'Thank You: %s', 'nexvio' ), $d[ $this->design_id() ][0] );
}
public function get_icon() {
return 'eicon-check-circle';
}
public function get_categories() {
return array( 'nexvio' );
}
public function get_keywords() {
return array( 'nexvio', 'thank you', 'thankyou', 'order', 'confirmation', 'received', 'checkout' );
}
public function get_style_depends() {
return array( 'nexvio' );
}
public function show_in_panel() {
return (int) Nexvio_Thankyou::settings()['design'] === $this->design_id();
}
protected function register_controls() {
$d = $this->design_id();
$this->start_controls_section( 'section_text', array( 'label' => esc_html__( 'Text', 'nexvio' ) ) );
$this->add_control( 'note', array( 'type' => Controls_Manager::RAW_HTML, 'raw' => esc_html__( 'In the editor you see an example order. Visitors see their own order.', 'nexvio' ), 'content_classes' => 'elementor-descriptor', ) );
$this->add_control( 'title', array( 'label' => esc_html__( 'Headline', 'nexvio' ), 'type' => Controls_Manager::TEXT, 'placeholder' => esc_html__( 'Same as in the dashboard. You can use {name} and {order}.', 'nexvio' ), 'dynamic' => array( 'active' => true ), 'label_block' => true, ) );
$this->add_control( 'message', array( 'label' => esc_html__( 'Message', 'nexvio' ), 'type' => Controls_Manager::TEXTAREA, 'placeholder' => esc_html__( 'Same as in the dashboard. You can use {name}, {order}, {email}, {total}.', 'nexvio' ), ) );
$this->add_control( 'btn_text', array( 'label' => esc_html__( 'Button text', 'nexvio' ), 'type' => Controls_Manager::TEXT, ) );
$this->add_control( 'btn_url', array( 'label' => esc_html__( 'Button link', 'nexvio' ), 'type' => Controls_Manager::URL, ) );
$this->end_controls_section();
$this->start_controls_section( 'section_show', array( 'label' => esc_html__( 'Show / hide', 'nexvio' ) ) );
$toggles = array( 'show_icon' => esc_html__( 'Icon', 'nexvio' ), 'show_message' => esc_html__( 'Message', 'nexvio' ), 'show_meta' => esc_html__( 'Order number, date, email, payment', 'nexvio' ), 'show_steps' => esc_html__( 'Order progress timeline', 'nexvio' ), 'show_items' => esc_html__( 'Ordered products', 'nexvio' ), 'show_totals' => esc_html__( 'Totals', 'nexvio' ), 'show_addr' => esc_html__( 'Addresses', 'nexvio' ), 'show_extras' => esc_html__( 'Checkout field answers', 'nexvio' ), 'show_note' => esc_html__( 'Payment instructions', 'nexvio' ), 'show_orders' => esc_html__( '"View my orders" button', 'nexvio' ), );
foreach ( $toggles as $k => $label ) {
$this->add_control( $k, array( 'label' => $label, 'type' => Controls_Manager::SWITCHER, 'default' => 'yes', 'return_value' => 'yes', ) );
}
$this->end_controls_section();
$this->start_controls_section( 'section_labels', array( 'label' => esc_html__( 'Labels', 'nexvio' ) ) );
foreach ( Nexvio_Thankyou::labels() as $k => $default ) {
$this->add_control( 'lbl_' . $k, array( 'label' => $default, 'type' => Controls_Manager::TEXT, 'placeholder' => $default, 'label_block' => true, ) );
}
$this->end_controls_section();
$this->style_controls( $d );
}
private function sel( $css ) {
$out = array();
foreach ( explode( ',', $css ) as $one ) {
$out[] = '{{WRAPPER}} ' . trim( $one );
}
return implode( ', ', $out );
}
private function section( $id, $label ) {
$this->start_controls_section( $id, array( 'label' => $label, 'tab' => Controls_Manager::TAB_STYLE ) );
}
private function color( $id, $label, $css, $prop = 'color' ) {
$this->add_control( $id, array( 'label' => $label, 'type' => Controls_Manager::COLOR, 'selectors' => array( $this->sel( $css ) => $prop . ': {{VALUE}};' ), ) );
}
private function typo( $id, $css, $label = '' ) {
$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => $id, 'label' => $label ? $label : esc_html__( 'Typography', 'nexvio' ), 'selector' => $this->sel( $css ), ) );
}
private function box( $id, $css, $with_bg = true, $with_radius = true ) {
if ( $with_bg ) {
$this->add_group_control( Group_Control_Background::get_type(), array( 'name' => $id . '_bg', 'types' => array( 'classic', 'gradient' ), 'selector' => $this->sel( $css ), ) );
}
$this->add_group_control( Group_Control_Border::get_type(), array( 'name' => $id . '_border', 'selector' => $this->sel( $css ), ) );
if ( $with_radius ) {
$this->add_responsive_control( $id . '_radius', array( 'label' => esc_html__( 'Corner radius', 'nexvio' ), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => array( 'px', '%', 'em' ), 'selectors' => array( $this->sel( $css ) => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ), ) );
}
$this->add_group_control( Group_Control_Box_Shadow::get_type(), array( 'name' => $id . '_shadow', 'selector' => $this->sel( $css ), ) );
$this->add_responsive_control( $id . '_pad', array( 'label' => esc_html__( 'Padding', 'nexvio' ), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => array( 'px', '%', 'em' ), 'selectors' => array( $this->sel( $css ) => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ), ) );
}
private function gap( $id, $label, $css, $prop = 'gap', $max = 80 ) {
$this->add_responsive_control( $id, array( 'label' => $label, 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 0, 'max' => $max ) ), 'selectors' => array( $this->sel( $css ) => $prop . ': {{SIZE}}{{UNIT}};' ), ) );
}
private function style_controls( $d ) {
$align = array( 'left' => array( 'title' => esc_html__( 'Left', 'nexvio' ), 'icon' => 'eicon-text-align-left' ), 'center' => array( 'title' => esc_html__( 'Center', 'nexvio' ), 'icon' => 'eicon-text-align-center' ), 'right' => array( 'title' => esc_html__( 'Right', 'nexvio' ), 'icon' => 'eicon-text-align-right' ), );
$this->section( 'st_all', esc_html__( 'Whole design', 'nexvio' ) );
$this->add_control( 'accent', array( 'label' => esc_html__( 'Main colour', 'nexvio' ), 'type' => Controls_Manager::COLOR, ) );
$this->add_responsive_control( 'max_width', array( 'label' => esc_html__( 'Maximum width', 'nexvio' ), 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px', '%' ), 'range' => array( 'px' => array( 'min' => 280, 'max' => 1400 ), '%' => array( 'min' => 30, 'max' => 100 ) ), 'selectors' => array( '{{WRAPPER}} .nxty' => 'max-width: {{SIZE}}{{UNIT}};' ), ) );
$this->color( 'ink', esc_html__( 'Text colour', 'nexvio' ), '.nxty', '--nxty-ink' );
$this->color( 'mute', esc_html__( 'Soft text colour', 'nexvio' ), '.nxty', '--nxty-mute' );
$this->color( 'line', esc_html__( 'Line colour', 'nexvio' ), '.nxty', '--nxty-line' );
$this->typo( 'base_typo', '.nxty', esc_html__( 'Base typography', 'nexvio' ) );
$this->add_group_control( Group_Control_Background::get_type(), array( 'name' => 'wrap_bg', 'types' => array( 'classic', 'gradient' ), 'selector' => '{{WRAPPER}} .nxty', ) );
$this->add_responsive_control( 'wrap_pad', array( 'label' => esc_html__( 'Padding', 'nexvio' ), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => array( 'px', '%', 'em' ), 'selectors' => array( '{{WRAPPER}} .nxty' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ), ) );
$this->end_controls_section();
$box = array( 1 => '.nxty__card', 3 => '.nxty__card', 6 => '.nxty__card', 9 => '.nxty__card', 2 => '.nxty__paper', 4 => '.nxty__panel', 8 => '.nxty__sheet', 10 => '.nxty__main', );
if ( isset( $box[ $d ] ) ) {
$this->section( 'st_box', 2 === $d ? esc_html__( 'Receipt', 'nexvio' ) : esc_html__( 'Card', 'nexvio' ) );
$this->box( 'box', $box[ $d ] );
$this->end_controls_section();
}
if ( 6 === $d ) {
$this->section( 'st_banner', esc_html__( 'Banner', 'nexvio' ) );
$this->box( 'banner', '.nxty__banner' );
$this->end_controls_section();
}
if ( 10 === $d ) {
$this->section( 'st_side', esc_html__( 'Summary sidebar', 'nexvio' ) );
$this->box( 'side', '.nxty__summary' );
$this->color( 'side_ink', esc_html__( 'Text colour', 'nexvio' ), '.nxty__summary .nxty__meta dd, .nxty__summary .nxty__iname, .nxty__summary .nxty__iprice, .nxty__summary .nxty__totals dd' );
$this->color( 'side_mute', esc_html__( 'Soft text colour', 'nexvio' ), '.nxty__summary .nxty__h, .nxty__summary dt, .nxty__summary .nxty__iname small' );
$this->end_controls_section();
}
if ( 7 === $d ) {
$this->section( 'st_tiles', esc_html__( 'Tiles', 'nexvio' ) );
$this->gap( 'tiles_gap', esc_html__( 'Space between tiles', 'nexvio' ), '.nxty__tiles' );
$this->box( 'tile', '.nxty__tile' );
$this->add_control( 'tile_tot_h', array( 'label' => esc_html__( 'Total tile', 'nexvio' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
$this->color( 'tile_tot_bg', esc_html__( 'Background', 'nexvio' ), '.nxty__tile--tot', 'background-color' );
$this->color( 'tile_tot_ink', esc_html__( 'Text colour', 'nexvio' ), '.nxty__tile--tot dd, .nxty__tile--tot .is-total' );
$this->end_controls_section();
}
if ( 8 === $d ) {
$this->section( 'st_stamp', esc_html__( 'Receipt number', 'nexvio' ) );
$this->typo( 'stamp_typo', '.nxty__stamp b', esc_html__( 'Number typography', 'nexvio' ) );
$this->color( 'stamp_c', esc_html__( 'Number colour', 'nexvio' ), '.nxty__stamp b' );
$this->typo( 'stamp_d_typo', '.nxty__stamp span', esc_html__( 'Date typography', 'nexvio' ) );
$this->color( 'stamp_d_c', esc_html__( 'Date colour', 'nexvio' ), '.nxty__stamp span' );
$this->end_controls_section();
}
if ( 5 === $d || 8 === $d ) {
$this->section( 'st_kicker', esc_html__( 'Small heading', 'nexvio' ) );
$this->typo( 'kicker_typo', '.nxty__kicker' );
$this->color( 'kicker_c', esc_html__( 'Colour', 'nexvio' ), '.nxty__kicker' );
$this->end_controls_section();
}
if ( 9 === $d ) {
$this->section( 'st_confetti', esc_html__( 'Confetti', 'nexvio' ) );
$this->add_control( 'confetti_off', array( 'label' => esc_html__( 'Hide confetti', 'nexvio' ), 'type' => Controls_Manager::SWITCHER, 'selectors' => array( '{{WRAPPER}} .nxty__confetti' => 'display: none;' ), 'return_value' => 'yes', ) );
$this->end_controls_section();
}
$this->section( 'st_icon', esc_html__( 'Icon', 'nexvio' ) );
$this->add_responsive_control( 'icon_box', array( 'label' => esc_html__( 'Circle size', 'nexvio' ), 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 30, 'max' => 160 ) ), 'selectors' => array( '{{WRAPPER}} .nxty__tick' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ), ) );
$this->add_responsive_control( 'icon_size', array( 'label' => esc_html__( 'Icon size', 'nexvio' ), 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 10, 'max' => 90 ) ), 'selectors' => array( '{{WRAPPER}} .nxty__tick .nxty__ico' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ), ) );
$this->color( 'icon_c', esc_html__( 'Icon colour', 'nexvio' ), '.nxty__tick' );
$this->add_group_control( Group_Control_Background::get_type(), array( 'name' => 'icon_bg', 'types' => array( 'classic', 'gradient' ), 'selector' => '{{WRAPPER}} .nxty__tick', ) );
$this->add_group_control( Group_Control_Border::get_type(), array( 'name' => 'icon_border', 'selector' => '{{WRAPPER}} .nxty__tick' ) );
$this->add_group_control( Group_Control_Box_Shadow::get_type(), array( 'name' => 'icon_shadow', 'selector' => '{{WRAPPER}} .nxty__tick' ) );
$this->end_controls_section();
$this->section( 'st_title', esc_html__( 'Headline', 'nexvio' ) );
$this->add_responsive_control( 'hero_align', array( 'label' => esc_html__( 'Alignment', 'nexvio' ), 'type' => Controls_Manager::CHOOSE, 'options' => $align, 'selectors' => array( '{{WRAPPER}} .nxty__hero' => 'text-align: {{VALUE}}; justify-items: {{VALUE}};' ), ) );
$this->typo( 'title_typo', '.nxty__title' );
$this->color( 'title_c', esc_html__( 'Colour', 'nexvio' ), '.nxty__title' );
$this->add_control( 'title_clip_note', array( 'type' => Controls_Manager::RAW_HTML, 'raw' => esc_html__( 'Designs with a gradient headline: choose a colour and it replaces the gradient.', 'nexvio' ), 'content_classes' => 'elementor-descriptor', ) );
$this->add_responsive_control( 'title_mb', array( 'label' => esc_html__( 'Space under headline', 'nexvio' ), 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 0, 'max' => 60 ) ), 'selectors' => array( '{{WRAPPER}} .nxty__title' => 'margin-bottom: {{SIZE}}{{UNIT}};' ), ) );
$this->end_controls_section();
$this->section( 'st_msg', esc_html__( 'Message', 'nexvio' ) );
$this->typo( 'msg_typo', '.nxty__msg' );
$this->color( 'msg_c', esc_html__( 'Colour', 'nexvio' ), '.nxty__msg' );
$this->add_responsive_control( 'msg_w', array( 'label' => esc_html__( 'Maximum width', 'nexvio' ), 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px', '%' ), 'range' => array( 'px' => array( 'min' => 200, 'max' => 1000 ) ), 'selectors' => array( '{{WRAPPER}} .nxty__msg' => 'max-width: {{SIZE}}{{UNIT}};' ), ) );
$this->end_controls_section();
$this->section( 'st_meta', esc_html__( 'Order details tiles', 'nexvio' ) );
$this->gap( 'meta_gap', esc_html__( 'Space between tiles', 'nexvio' ), '.nxty__meta' );
$this->box( 'meta', '.nxty__meta > div' );
$this->add_control( 'meta_lbl_h', array( 'label' => esc_html__( 'Label', 'nexvio' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
$this->typo( 'meta_lbl_typo', '.nxty__meta dt' );
$this->color( 'meta_lbl_c', esc_html__( 'Colour', 'nexvio' ), '.nxty__meta dt' );
$this->add_control( 'meta_val_h', array( 'label' => esc_html__( 'Value', 'nexvio' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
$this->typo( 'meta_val_typo', '.nxty__meta dd' );
$this->color( 'meta_val_c', esc_html__( 'Colour', 'nexvio' ), '.nxty__meta dd' );
$this->add_control( 'meta_ico_h', array( 'label' => esc_html__( 'Tile icon', 'nexvio' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
$this->color( 'meta_ico_c', esc_html__( 'Icon colour', 'nexvio' ), '.nxty__mico' );
$this->color( 'meta_ico_bg', esc_html__( 'Icon background', 'nexvio' ), '.nxty__mico', 'background-color' );
$this->end_controls_section();
$this->section( 'st_h', esc_html__( 'Section headings', 'nexvio' ) );
$this->typo( 'h_typo', '.nxty__h, .nxty__addr h4, .nxty__extras h4' );
$this->color( 'h_c', esc_html__( 'Colour', 'nexvio' ), '.nxty__h, .nxty__addr h4, .nxty__extras h4' );
$this->end_controls_section();
$this->section( 'st_items', esc_html__( 'Products', 'nexvio' ) );
$this->add_responsive_control( 'thumb_size', array( 'label' => esc_html__( 'Picture size', 'nexvio' ), 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 24, 'max' => 140 ) ), 'selectors' => array( '{{WRAPPER}} .nxty__thumb' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};', '{{WRAPPER}} .nxty__items li' => 'grid-template-columns: {{SIZE}}{{UNIT}} 1fr auto;', ), ) );
$this->add_responsive_control( 'thumb_radius', array( 'label' => esc_html__( 'Picture corner radius', 'nexvio' ), 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px', '%' ), 'range' => array( 'px' => array( 'min' => 0, 'max' => 60 ), '%' => array( 'min' => 0, 'max' => 50 ) ), 'selectors' => array( '{{WRAPPER}} .nxty__thumb' => 'border-radius: {{SIZE}}{{UNIT}};' ), ) );
$this->gap( 'item_gap', esc_html__( 'Space between columns', 'nexvio' ), '.nxty__items li' );
$this->add_responsive_control( 'item_pad', array( 'label' => esc_html__( 'Row padding', 'nexvio' ), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => array( 'px', 'em' ), 'selectors' => array( '{{WRAPPER}} .nxty__items li' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ), ) );
$this->color( 'item_line', esc_html__( 'Row line colour', 'nexvio' ), '.nxty__items li', 'border-color' );
$this->color( 'thumb_bg', esc_html__( 'Picture background', 'nexvio' ), '.nxty__thumb', 'background-color' );
$this->add_control( 'iname_h', array( 'label' => esc_html__( 'Name', 'nexvio' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
$this->typo( 'iname_typo', '.nxty__iname' );
$this->color( 'iname_c', esc_html__( 'Colour', 'nexvio' ), '.nxty__iname' );
$this->add_control( 'iqty_h', array( 'label' => esc_html__( 'Quantity', 'nexvio' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
$this->typo( 'iqty_typo', '.nxty__iname small' );
$this->color( 'iqty_c', esc_html__( 'Colour', 'nexvio' ), '.nxty__iname small' );
$this->add_control( 'iprice_h', array( 'label' => esc_html__( 'Price', 'nexvio' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
$this->typo( 'iprice_typo', '.nxty__iprice' );
$this->color( 'iprice_c', esc_html__( 'Colour', 'nexvio' ), '.nxty__iprice' );
$this->end_controls_section();
$this->section( 'st_tot', esc_html__( 'Totals', 'nexvio' ) );
$this->gap( 'tot_gap', esc_html__( 'Space between rows', 'nexvio' ), '.nxty__totals' );
$this->typo( 'tot_lbl_typo', '.nxty__totals dt', esc_html__( 'Label typography', 'nexvio' ) );
$this->color( 'tot_lbl_c', esc_html__( 'Label colour', 'nexvio' ), '.nxty__totals > div' );
$this->typo( 'tot_val_typo', '.nxty__totals dd', esc_html__( 'Value typography', 'nexvio' ) );
$this->color( 'tot_val_c', esc_html__( 'Value colour', 'nexvio' ), '.nxty__totals dd' );
$this->add_control( 'tot_t_h', array( 'label' => esc_html__( 'Total row', 'nexvio' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
$this->typo( 'tot_t_typo', '.nxty__totals .is-total, .nxty__totals .is-total dd', esc_html__( 'Typography', 'nexvio' ) );
$this->color( 'tot_t_lbl_c', esc_html__( 'Label colour', 'nexvio' ), '.nxty__totals .is-total' );
$this->color( 'tot_t_val_c', esc_html__( 'Amount colour', 'nexvio' ), '.nxty__totals .is-total dd' );
$this->color( 'tot_t_line', esc_html__( 'Line colour', 'nexvio' ), '.nxty__totals .is-total', 'border-color' );
$this->end_controls_section();
$this->section( 'st_addr', esc_html__( 'Addresses', 'nexvio' ) );
$this->gap( 'addr_gap', esc_html__( 'Space between boxes', 'nexvio' ), '.nxty__addr' );
$this->box( 'addr', '.nxty__addr section' );
$this->typo( 'addr_typo', '.nxty__addr address', esc_html__( 'Text typography', 'nexvio' ) );
$this->color( 'addr_c', esc_html__( 'Text colour', 'nexvio' ), '.nxty__addr address' );
$this->end_controls_section();
$this->section( 'st_extras', esc_html__( 'Delivery and extra details', 'nexvio' ) );
$this->box( 'extras', '.nxty__extras' );
$this->typo( 'ex_lbl_typo', '.nxty__extras dt', esc_html__( 'Label typography', 'nexvio' ) );
$this->color( 'ex_lbl_c', esc_html__( 'Label colour', 'nexvio' ), '.nxty__extras dt' );
$this->typo( 'ex_val_typo', '.nxty__extras dd', esc_html__( 'Value typography', 'nexvio' ) );
$this->color( 'ex_val_c', esc_html__( 'Value colour', 'nexvio' ), '.nxty__extras dd' );
$this->end_controls_section();
$this->section( 'st_steps', esc_html__( 'Order progress timeline', 'nexvio' ) );
$this->add_responsive_control( 'dot_size', array( 'label' => esc_html__( 'Circle size', 'nexvio' ), 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 24, 'max' => 80 ) ), 'selectors' => array( '{{WRAPPER}} .nxty__dot' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};', '{{WRAPPER}} .nxty__steps li::before' => 'top: calc({{SIZE}}{{UNIT}} / 2 - 1px);' ), ) );
$this->typo( 'step_typo', '.nxty__steps li', esc_html__( 'Label typography', 'nexvio' ) );
$this->color( 'step_c', esc_html__( 'Label colour', 'nexvio' ), '.nxty__steps li' );
$this->color( 'step_on_c', esc_html__( 'Reached label colour', 'nexvio' ), '.nxty__steps .is-done, .nxty__steps .is-now' );
$this->color( 'step_dot_bg', esc_html__( 'Circle background', 'nexvio' ), '.nxty__dot', 'background-color' );
$this->color( 'step_dot_line', esc_html__( 'Circle border', 'nexvio' ), '.nxty__dot', 'border-color' );
$this->color( 'step_done_bg', esc_html__( 'Done circle colour', 'nexvio' ), '.nxty__steps .is-done .nxty__dot', 'background-color' );
$this->color( 'step_done_line', esc_html__( 'Done line colour', 'nexvio' ), '.nxty__steps li.is-done::before, .nxty__steps li.is-now::before', 'background-color' );
$this->end_controls_section();
$this->section( 'st_note', esc_html__( 'Payment instructions', 'nexvio' ) );
$this->box( 'note', '.nxty__note' );
$this->typo( 'note_typo', '.nxty__note' );
$this->color( 'note_c', esc_html__( 'Text colour', 'nexvio' ), '.nxty__note' );
$this->end_controls_section();
$this->section( 'st_btn', esc_html__( 'Buttons', 'nexvio' ) );
$this->add_responsive_control( 'act_align', array( 'label' => esc_html__( 'Alignment', 'nexvio' ), 'type' => Controls_Manager::CHOOSE, 'options' => $align, 'selectors_dictionary' => array( 'left' => 'flex-start', 'center' => 'center', 'right' => 'flex-end' ), 'selectors' => array( '{{WRAPPER}} .nxty__actions' => 'justify-content: {{VALUE}};' ), ) );
$this->gap( 'act_gap', esc_html__( 'Space between buttons', 'nexvio' ), '.nxty__actions' );
$this->typo( 'btn_typo', '.nxty__btn' );
$this->add_responsive_control( 'btn_pad', array( 'label' => esc_html__( 'Padding', 'nexvio' ), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => array( 'px', 'em' ), 'selectors' => array( '{{WRAPPER}} .nxty__btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ), ) );
$this->add_responsive_control( 'btn_radius', array( 'label' => esc_html__( 'Corner radius', 'nexvio' ), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => array( 'px', '%' ), 'selectors' => array( '{{WRAPPER}} .nxty__btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ), ) );
foreach ( array( 'main' => array( esc_html__( 'Main button', 'nexvio' ), '.nxty__btn:not(.nxty__btn--ghost)' ), 'ghost' => array( esc_html__( 'Second button', 'nexvio' ), '.nxty__btn--ghost' ) ) as $k => $b ) {
$this->add_control( 'btn_' . $k . '_h', array( 'label' => $b[0], 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
$this->start_controls_tabs( 'btn_' . $k . '_tabs' );
foreach ( array( 'n' => array( esc_html__( 'Normal', 'nexvio' ), '' ), 'h' => array( esc_html__( 'Hover', 'nexvio' ), ':hover' ) ) as $sk => $state ) {
$this->start_controls_tab( 'btn_' . $k . '_' . $sk, array( 'label' => $state[0] ) );
$css = $b[1] . $state[1];
$this->add_control( 'btn_' . $k . '_' . $sk . '_c', array( 'label' => esc_html__( 'Text colour', 'nexvio' ), 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} ' . $css => 'color: {{VALUE}} !important;' ), ) );
$this->add_control( 'btn_' . $k . '_' . $sk . '_bg', array( 'label' => esc_html__( 'Background', 'nexvio' ), 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} ' . $css => 'background: {{VALUE}};' ), ) );
$this->add_control( 'btn_' . $k . '_' . $sk . '_bd', array( 'label' => esc_html__( 'Border colour', 'nexvio' ), 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} ' . $css => 'border-color: {{VALUE}};' ), ) );
$this->end_controls_tab();
}
$this->end_controls_tabs();
}
$this->add_group_control( Group_Control_Box_Shadow::get_type(), array( 'name' => 'btn_shadow', 'selector' => '{{WRAPPER}} .nxty__btn:not(.nxty__btn--ghost)' ) );
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
foreach ( array( 'show_icon', 'show_message', 'show_meta', 'show_steps', 'show_items', 'show_totals', 'show_addr', 'show_extras', 'show_note', 'show_orders' ) as $k ) {
$override[ $k ] = isset( $st[ $k ] ) && 'yes' === $st[ $k ] ? 1 : 0;
}
$labels = array();
foreach ( array_keys( Nexvio_Thankyou::labels() ) as $k ) {
if ( ! empty( $st[ 'lbl_' . $k ] ) ) {
$labels[ $k ] = (string) $st[ 'lbl_' . $k ];
}
}
$override['labels'] = $labels;
$edit = false;
if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->editor ) ) {
$edit = \Elementor\Plugin::$instance->editor->is_edit_mode() || ( isset( \Elementor\Plugin::$instance->preview ) && \Elementor\Plugin::$instance->preview->is_preview_mode() );
}
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup is built by Nexvio_Thankyou, which escapes every dynamic value.
echo Nexvio_Thankyou::output( array( 'design' => $this->design_id(), 'accent' => ! empty( $st['accent'] ) ? sanitize_hex_color( $st['accent'] ) : '', 'override' => $override, 'demo' => $edit, ) );
}
}
class Nexvio_Thankyou_D1_Widget extends Nexvio_Thankyou_Base_Widget {
protected function design_id() {
return 1;
}
}
class Nexvio_Thankyou_D2_Widget extends Nexvio_Thankyou_Base_Widget {
protected function design_id() {
return 2;
}
}
class Nexvio_Thankyou_D3_Widget extends Nexvio_Thankyou_Base_Widget {
protected function design_id() {
return 3;
}
}
class Nexvio_Thankyou_D4_Widget extends Nexvio_Thankyou_Base_Widget {
protected function design_id() {
return 4;
}
}
class Nexvio_Thankyou_D5_Widget extends Nexvio_Thankyou_Base_Widget {
protected function design_id() {
return 5;
}
}
class Nexvio_Thankyou_D6_Widget extends Nexvio_Thankyou_Base_Widget {
protected function design_id() {
return 6;
}
}
class Nexvio_Thankyou_D7_Widget extends Nexvio_Thankyou_Base_Widget {
protected function design_id() {
return 7;
}
}
class Nexvio_Thankyou_D8_Widget extends Nexvio_Thankyou_Base_Widget {
protected function design_id() {
return 8;
}
}
class Nexvio_Thankyou_D9_Widget extends Nexvio_Thankyou_Base_Widget {
protected function design_id() {
return 9;
}
}
class Nexvio_Thankyou_D10_Widget extends Nexvio_Thankyou_Base_Widget {
protected function design_id() {
return 10;
}
} 