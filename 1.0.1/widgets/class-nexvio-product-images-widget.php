<?php
/**
 * NEXVIO Product Images - Elementor widget for the single product page.
 *
 * Only the product image and its gallery (nothing else):
 *  - the main image, with previous / next arrows;
 *  - the gallery thumbnails: left, right, above or below the image (own position on mobile);
 *  - every image is shown in the same box, never cropped (object-fit: contain), in full resolution;
 *  - variation images replace the main image (variable products).
 *
 * @package Nexvio_For_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

class Nexvio_Product_Images_Widget extends Widget_Base {

	public function get_name() {
		return 'nexvio_product_images';
	}

	public function get_title() {
		return esc_html__( 'Product Images', 'nexvio' );
	}

	public function get_icon() {
		return 'eicon-product-images';
	}

	public function get_categories() {
		return array( 'nexvio' );
	}

	public function get_keywords() {
		return array( 'nexvio', 'woocommerce', 'product', 'image', 'images', 'gallery', 'thumbnails', 'single product' );
	}

	public function get_style_depends() {
		return array( 'nexvio-layout', 'nexvio-product-images' );
	}

	public function get_script_depends() {
		return array( 'nexvio-product-images' );
	}

	private function sel( $class ) {
		return '{{WRAPPER}} ' . $class;
	}

	/* ------------------------------------------------------------------
	 * Controls
	 * ------------------------------------------------------------------ */

	protected function register_controls() {

		/* ============ CONTENT ============ */
		$this->start_controls_section( 'section_source', array( 'label' => esc_html__( 'Product', 'nexvio' ) ) );
		$this->add_control( 'product_id', array(
			'label'       => esc_html__( 'Product ID (empty = the product of the page)', 'nexvio' ),
			'type'        => Controls_Manager::NUMBER,
			'default'     => '',
			'min'         => 1,
			'description' => esc_html__( 'Leave empty in the single product template. Set an ID only to always show one fixed product.', 'nexvio' ),
		) );
		$this->end_controls_section();

		$this->start_controls_section( 'section_gallery', array( 'label' => esc_html__( 'Gallery', 'nexvio' ) ) );
		$this->add_control( 'show_thumbs', array(
			'label'        => esc_html__( 'Thumbnails inside the image', 'nexvio' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
			'description'  => esc_html__( 'The small gallery on the image, at the bottom. It always stays there.', 'nexvio' ),
		) );
		$this->add_responsive_control( 'thumb_count', array(
			'label'       => esc_html__( 'How many thumbnails to show inside the image', 'nexvio' ),
			'type'        => Controls_Manager::NUMBER,
			'min'         => 0,
			'max'         => 30,
			'step'        => 1,
			'default'     => '',
			'placeholder' => esc_html__( 'All', 'nexvio' ),
			'description' => esc_html__( 'Empty or 0 = all the images. The other images stay reachable with the arrows, the swipe and the gallery row. Tablet and phone use the desktop number until you set their own.', 'nexvio' ),
			'condition'   => array( 'show_thumbs' => 'yes' ),
		) );
		$this->add_control( 'show_below', array(
			'label'        => esc_html__( 'Gallery images next to the main image', 'nexvio' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
			'description'  => esc_html__( 'A row with all the gallery images, outside the main image. Choose where it goes below.', 'nexvio' ),
			'separator'    => 'before',
		) );
		$positions = array(
			'bottom' => esc_html__( 'Bottom (below the image)', 'nexvio' ),
			'top'    => esc_html__( 'Top (above the image)', 'nexvio' ),
			'left'   => esc_html__( 'Left', 'nexvio' ),
			'right'  => esc_html__( 'Right', 'nexvio' ),
		);
		$this->add_control( 'below_pos', array(
			'label'     => esc_html__( 'Gallery images position', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'bottom',
			'options'   => $positions,
			'condition' => array( 'show_below' => 'yes' ),
		) );
		$this->add_control( 'below_pos_mobile', array(
			'label'       => esc_html__( 'Gallery images position on phones', 'nexvio' ),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'bottom',
			'options'     => $positions,
			'description' => esc_html__( 'Used on screens up to 767px wide.', 'nexvio' ),
			'condition'   => array( 'show_below' => 'yes' ),
		) );
		$this->add_control( 'show_nav', array(
			'label'        => esc_html__( 'Previous / next arrows', 'nexvio' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
			'separator'    => 'before',
		) );
		$this->add_responsive_control( 'img_ratio', array(
			'label'     => esc_html__( 'Image box ratio', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => '1/1',
			'options'   => array(
				'1/1'  => '1 : 1',
				'4/5'  => '4 : 5',
				'3/4'  => '3 : 4',
				'4/3'  => '4 : 3',
				'3/2'  => '3 : 2',
				'16/9' => '16 : 9',
			),
			'selectors' => array( $this->sel( '.nexvio-pi__stage' ) => 'aspect-ratio: {{VALUE}};' ),
			'separator' => 'before',
		) );
		$this->add_control( 'img_fit', array(
			'label'       => esc_html__( 'Image fit', 'nexvio' ),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'contain',
			'options'     => array(
				'contain' => esc_html__( 'Whole image visible (no crop)', 'nexvio' ),
				'cover'   => esc_html__( 'Fill the box (crops)', 'nexvio' ),
			),
			'description' => esc_html__( 'All images use the same box. "No crop" keeps the whole picture visible.', 'nexvio' ),
			'selectors'   => array( $this->sel( '.nexvio-pi__img, ' ) . $this->sel( '.nexvio-pi__thumb img' ) . ', ' . $this->sel( '.nexvio-pi__bthumb img' ) => 'object-fit: {{VALUE}};' ),
		) );
		$this->end_controls_section();

		/* ============ STYLE: WIDTH ============ */
		$this->start_controls_section( 'st_size', array( 'label' => esc_html__( 'Width', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_responsive_control( 'pi_width', array(
			'label'          => esc_html__( 'Widget width', 'nexvio' ),
			'type'           => Controls_Manager::SLIDER,
			'size_units'     => array( '%', 'px' ),
			'range'          => array( '%' => array( 'min' => 20, 'max' => 100 ), 'px' => array( 'min' => 200, 'max' => 1400 ) ),
			'default'        => array( 'size' => 100, 'unit' => '%' ),
			'tablet_default' => array( 'size' => 100, 'unit' => '%' ),
			'mobile_default' => array( 'size' => 100, 'unit' => '%' ),
			'selectors'      => array( $this->sel( '.nexvio-pi' ) => 'width: {{SIZE}}{{UNIT}}; max-width: 100%;' ),
		) );
		$this->end_controls_section();

		/* ============ STYLE: IMAGE ============ */
		$this->start_controls_section( 'st_image', array( 'label' => esc_html__( 'Main image', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_control( 'stage_bg', array(
			'label'     => esc_html__( 'Background', 'nexvio' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#f3f4f6',
			'selectors' => array( $this->sel( '.nexvio-pi__stage' ) => 'background-color: {{VALUE}};' ),
		) );
		$this->add_responsive_control( 'stage_radius', array(
			'label'      => esc_html__( 'Corner radius', 'nexvio' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', '%' ),
			'selectors'  => array( $this->sel( '.nexvio-pi__stage' ) => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );
		$this->add_group_control( Group_Control_Border::get_type(), array( 'name' => 'stage_border', 'selector' => $this->sel( '.nexvio-pi__stage' ) ) );
		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array( 'name' => 'stage_shadow', 'selector' => $this->sel( '.nexvio-pi__stage' ) ) );
		$this->end_controls_section();

		/* ============ STYLE: THUMBNAILS ============ */
		$this->start_controls_section( 'st_thumbs', array(
			'label'     => esc_html__( 'Thumbnails inside the image', 'nexvio' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'show_thumbs' => 'yes' ),
		) );
		$this->add_responsive_control( 'thumb_size', array(
			'label'      => esc_html__( 'Thumbnail size (max 48px)', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 24, 'max' => 180 ) ),
			'default'    => array( 'size' => 64, 'unit' => 'px' ),
			'selectors'  => array( $this->sel( '.nexvio-pi' ) => '--nexvio-pi-thumb: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'thumb_gap', array(
			'label'      => esc_html__( 'Gap', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
			'default'    => array( 'size' => 10, 'unit' => 'px' ),
			'selectors'  => array( $this->sel( '.nexvio-pi' ) => '--nexvio-pi-gap: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'thumb_radius', array(
			'label'      => esc_html__( 'Corner radius', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
			'default'    => array( 'size' => 8, 'unit' => 'px' ),
			'selectors'  => array( $this->sel( '.nexvio-pi__thumb' ) => 'border-radius: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_control( 'thumb_bg', array(
			'label'     => esc_html__( 'Background', 'nexvio' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '',
			'selectors' => array( $this->sel( '.nexvio-pi__thumb' ) => 'background-color: {{VALUE}};' ),
		) );
		$this->add_control( 'thumb_border', array(
			'label'     => esc_html__( 'Border colour', 'nexvio' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '',
			'selectors' => array( $this->sel( '.nexvio-pi' ) => '--nexvio-pi-tb: {{VALUE}};' ),
		) );
		$this->add_control( 'thumb_border_hover', array(
			'label'     => esc_html__( 'Border colour on hover', 'nexvio' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '',
			'selectors' => array( $this->sel( '.nexvio-pi' ) => '--nexvio-pi-tbh: {{VALUE}};' ),
		) );
		$this->add_control( 'thumb_border_active', array(
			'label'     => esc_html__( 'Active border colour', 'nexvio' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '',
			'selectors' => array( $this->sel( '.nexvio-pi' ) => '--nexvio-pi-tba: {{VALUE}};' ),
		) );
		$this->add_control( 'thumb_dim', array(
			'label'       => esc_html__( 'Dim the inactive thumbnails', 'nexvio' ),
			'type'        => Controls_Manager::SLIDER,
			'range'       => array( 'px' => array( 'min' => 0.3, 'max' => 1, 'step' => 0.05 ) ),
			'default'     => array( 'size' => 0.7 ),
			'selectors'   => array( $this->sel( '.nexvio-pi__thumb:not(.is-active)' ) => 'opacity: {{SIZE}};' ),
		) );
		$this->end_controls_section();

		/* ============ STYLE: GALLERY BELOW ============ */
		$this->start_controls_section( 'st_below', array(
			'label'     => esc_html__( 'Gallery images (outside the image)', 'nexvio' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'show_below' => 'yes' ),
		) );
		$this->add_responsive_control( 'below_size', array(
			'label'      => esc_html__( 'Image size', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 40, 'max' => 240 ) ),
			'default'    => array( 'size' => 84, 'unit' => 'px' ),
			'selectors'  => array( $this->sel( '.nexvio-pi' ) => '--nexvio-pi-below: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'below_gap', array(
			'label'      => esc_html__( 'Gap', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
			'default'    => array( 'size' => 10, 'unit' => 'px' ),
			'selectors'  => array( $this->sel( '.nexvio-pi' ) => '--nexvio-pi-below-gap: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'below_margin', array(
			'label'      => esc_html__( 'Space between the image and the gallery (top / bottom)', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
			'default'    => array( 'size' => 12, 'unit' => 'px' ),
			'selectors'  => array( $this->sel( '.nexvio-pi' ) => '--nexvio-pi-wrap-gap: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'below_side_gap', array(
			'label'       => esc_html__( 'Space between the image and the gallery (left / right)', 'nexvio' ),
			'type'        => Controls_Manager::SLIDER,
			'size_units'  => array( 'px' ),
			'range'       => array( 'px' => array( 'min' => 0, 'max' => 120 ) ),
			'description' => esc_html__( 'Used when the gallery is on the left or on the right of the image (also on phones). Empty = same as the space above.', 'nexvio' ),
			'selectors'   => array( $this->sel( '.nexvio-pi' ) => '--nexvio-pi-side-gap: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'below_radius', array(
			'label'      => esc_html__( 'Corner radius', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
			'selectors'  => array( $this->sel( '.nexvio-pi' ) => '--nexvio-pi-bbr: {{SIZE}}{{UNIT}};' ),
			'separator'  => 'before',
		) );
		$this->add_responsive_control( 'below_bw', array(
			'label'      => esc_html__( 'Border width', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 8 ) ),
			'selectors'  => array( $this->sel( '.nexvio-pi' ) => '--nexvio-pi-bbw: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_control( 'below_bg', array(
			'label'     => esc_html__( 'Background', 'nexvio' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( $this->sel( '.nexvio-pi' ) => '--nexvio-pi-bbg: {{VALUE}};' ),
		) );
		$this->add_control( 'below_border', array(
			'label'     => esc_html__( 'Border colour', 'nexvio' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( $this->sel( '.nexvio-pi' ) => '--nexvio-pi-bb: {{VALUE}};' ),
		) );
		$this->add_control( 'below_border_hover', array(
			'label'     => esc_html__( 'Border colour on hover', 'nexvio' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( $this->sel( '.nexvio-pi' ) => '--nexvio-pi-bbh: {{VALUE}};' ),
		) );
		$this->add_control( 'below_border_active', array(
			'label'     => esc_html__( 'Active border colour', 'nexvio' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( $this->sel( '.nexvio-pi' ) => '--nexvio-pi-bba: {{VALUE}};' ),
		) );
		$this->add_control( 'below_dim', array(
			'label'     => esc_html__( 'Dim the inactive images', 'nexvio' ),
			'type'      => Controls_Manager::SLIDER,
			'range'     => array( 'px' => array( 'min' => 0.3, 'max' => 1, 'step' => 0.05 ) ),
			'selectors' => array( $this->sel( '.nexvio-pi__bthumb:not(.is-active):not(:hover)' ) => 'opacity: {{SIZE}};' ),
		) );
		$this->end_controls_section();

		/* ============ STYLE: ARROWS ============ */
		$this->start_controls_section( 'st_nav', array(
			'label'     => esc_html__( 'Arrows', 'nexvio' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'show_nav' => 'yes' ),
		) );
		$this->add_control( 'nav_color', array(
			'label'     => esc_html__( 'Icon colour', 'nexvio' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#111827',
			'selectors' => array( $this->sel( '.nexvio-pi__nav' ) => 'color: {{VALUE}};' ),
		) );
		$this->add_control( 'nav_bg', array(
			'label'     => esc_html__( 'Background', 'nexvio' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => 'rgba(255,255,255,.9)',
			'selectors' => array( $this->sel( '.nexvio-pi__nav' ) => 'background-color: {{VALUE}};' ),
		) );
		$this->add_responsive_control( 'nav_size', array(
			'label'      => esc_html__( 'Size', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 24, 'max' => 64 ) ),
			'default'    => array( 'size' => 38, 'unit' => 'px' ),
			'selectors'  => array( $this->sel( '.nexvio-pi__nav' ) => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ),
		) );
		$this->end_controls_section();
	}

	/* ------------------------------------------------------------------
	 * Helpers
	 * ------------------------------------------------------------------ */

	private function current_product( $s ) {
		$id = ! empty( $s['product_id'] ) ? (int) $s['product_id'] : 0;
		if ( ! $id && is_singular( 'product' ) ) {
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
				$latest = get_posts( array( 'post_type' => 'product', 'post_status' => 'publish', 'numberposts' => 1, 'fields' => 'ids' ) );
				$id     = $latest ? (int) $latest[0] : 0;
			}
		}
		return $id ? wc_get_product( $id ) : null;
	}

	/** Featured image first, then the product gallery; full resolution. */
	private function gallery_images( $product ) {
		$ids = array();
		if ( $product->get_image_id() ) {
			$ids[] = (int) $product->get_image_id();
		}
		foreach ( (array) $product->get_gallery_image_ids() as $gid ) {
			$gid = (int) $gid;
			if ( $gid && ! in_array( $gid, $ids, true ) ) {
				$ids[] = $gid;
			}
		}
		$out = array();
		foreach ( $ids as $id ) {
			$src = wp_get_attachment_image_src( $id, 'full' );
			if ( ! $src ) {
				continue;
			}
			$thumb = wp_get_attachment_image_src( $id, 'woocommerce_thumbnail' );
			$out[] = array(
				'id'     => $id,
				'src'    => $src[0],
				'w'      => (int) $src[1],
				'h'      => (int) $src[2],
				'srcset' => (string) wp_get_attachment_image_srcset( $id, 'full' ),
				'sizes'  => (string) wp_get_attachment_image_sizes( $id, 'full' ),
				'thumb'  => $thumb ? $thumb[0] : $src[0],
				'alt'    => trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) ),
			);
		}
		if ( ! $out ) {
			$ph    = wc_placeholder_img_src( 'full' );
			$out[] = array( 'id' => 0, 'src' => $ph, 'w' => 800, 'h' => 800, 'srcset' => '', 'sizes' => '', 'thumb' => $ph, 'alt' => '' );
		}
		return $out;
	}

	private function arrow( $dir ) {
		$d = 'prev' === $dir ? 'm15 5-7 7 7 7' : 'm9 5 7 7-7 7';
		return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="' . $d . '"/></svg>';
	}

	/* ------------------------------------------------------------------
	 * Output
	 * ------------------------------------------------------------------ */

	protected function render() {
		$s       = $this->get_settings_for_display();
		$product = $this->current_product( $s );
		$is_edit = class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode();

		if ( ! $product instanceof WC_Product ) {
			if ( $is_edit ) {
				echo '<div class="nexvio-pi nexvio-pi--empty"><p>' . esc_html__( 'No product found. Use this widget in the single product template, or set a fixed product ID.', 'nexvio' ) . '</p></div>';
			}
			return;
		}

		$images = $this->gallery_images( $product );
		$many   = count( $images ) > 1;
		$thumbs = 'yes' === $s['show_thumbs'] && $many;
		$below  = 'yes' === $s['show_below'] && $many;
		$valid  = array( 'bottom', 'top', 'left', 'right' );
		$pos    = in_array( $s['below_pos'], $valid, true ) ? $s['below_pos'] : 'bottom';
		$mpos   = in_array( $s['below_pos_mobile'], $valid, true ) ? $s['below_pos_mobile'] : 'bottom';
		$first  = $images[0];

		/* how many thumbnails inside the image: desktop, tablet (falls back to desktop), phone (falls back to tablet) */
		$cnt_d = isset( $s['thumb_count'] ) ? max( 0, (int) $s['thumb_count'] ) : 0;
		$cnt_t = ( isset( $s['thumb_count_tablet'] ) && '' !== $s['thumb_count_tablet'] ) ? max( 0, (int) $s['thumb_count_tablet'] ) : $cnt_d;
		$cnt_m = ( isset( $s['thumb_count_mobile'] ) && '' !== $s['thumb_count_mobile'] ) ? max( 0, (int) $s['thumb_count_mobile'] ) : $cnt_t;

		$cfg = array(
			'variations' => $product->is_type( 'variable' ),
			'product'    => $product->get_id(),
		);
		?>
		<div class="nexvio-pi nexvio-pi--pos-<?php echo esc_attr( $pos ); ?> nexvio-pi--mpos-<?php echo esc_attr( $mpos ); ?>" data-pi="<?php echo esc_attr( wp_json_encode( $cfg ) ); ?>">
			<div class="nexvio-pi__wrap">

				<div class="nexvio-pi__stage">
					<img class="nexvio-pi__img" src="<?php echo esc_url( $first['src'] ); ?>"<?php echo $first['srcset'] ? ' srcset="' . esc_attr( $first['srcset'] ) . '" sizes="' . esc_attr( $first['sizes'] ) . '"' : ''; ?> width="<?php echo esc_attr( $first['w'] ); ?>" height="<?php echo esc_attr( $first['h'] ); ?>" alt="<?php echo esc_attr( $first['alt'] ? $first['alt'] : $product->get_name() ); ?>" decoding="async" draggable="false">
					<?php if ( $many && 'yes' === $s['show_nav'] ) : ?>
						<button type="button" class="nexvio-pi__nav nexvio-pi__nav--prev" data-pi-nav="-1" aria-label="<?php esc_attr_e( 'Previous image', 'nexvio' ); ?>"><?php echo $this->arrow( 'prev' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?></button>
						<button type="button" class="nexvio-pi__nav nexvio-pi__nav--next" data-pi-nav="1" aria-label="<?php esc_attr_e( 'Next image', 'nexvio' ); ?>"><?php echo $this->arrow( 'next' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?></button>
					<?php endif; ?>

					<?php if ( $thumbs ) : ?>
						<div class="nexvio-pi__thumbs-wrap">
							<div class="nexvio-pi__thumbs" role="group" aria-label="<?php esc_attr_e( 'Product images', 'nexvio' ); ?>">
								<?php foreach ( $images as $i => $img ) : ?>
									<button type="button" class="nexvio-pi__thumb<?php echo 0 === $i ? ' is-active' : ''; echo ( $cnt_d && $i >= $cnt_d ) ? ' hide-d' : ''; echo ( $cnt_t && $i >= $cnt_t ) ? ' hide-t' : ''; echo ( $cnt_m && $i >= $cnt_m ) ? ' hide-m' : ''; ?>" data-pi-thumb="<?php echo esc_attr( $i ); ?>" data-src="<?php echo esc_url( $img['src'] ); ?>" data-srcset="<?php echo esc_attr( $img['srcset'] ); ?>" data-sizes="<?php echo esc_attr( $img['sizes'] ); ?>" data-alt="<?php echo esc_attr( $img['alt'] ? $img['alt'] : $product->get_name() ); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %d: image number */ __( 'Show image %d', 'nexvio' ), $i + 1 ) ); ?>"<?php echo 0 === $i ? ' aria-current="true"' : ''; ?>>
										<img src="<?php echo esc_url( $img['thumb'] ); ?>" alt="" loading="lazy" draggable="false">
									</button>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endif; ?>
				</div>

				<?php if ( $below ) : ?>
					<div class="nexvio-pi__below">
						<div class="nexvio-pi__below-list" role="group" aria-label="<?php esc_attr_e( 'Gallery', 'nexvio' ); ?>">
							<?php foreach ( $images as $i => $img ) : ?>
								<button type="button" class="nexvio-pi__bthumb<?php echo 0 === $i ? ' is-active' : ''; ?>" data-pi-thumb="<?php echo esc_attr( $i ); ?>" data-src="<?php echo esc_url( $img['src'] ); ?>" data-srcset="<?php echo esc_attr( $img['srcset'] ); ?>" data-sizes="<?php echo esc_attr( $img['sizes'] ); ?>" data-alt="<?php echo esc_attr( $img['alt'] ? $img['alt'] : $product->get_name() ); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %d: image number */ __( 'Show image %d', 'nexvio' ), $i + 1 ) ); ?>"<?php echo 0 === $i ? ' aria-current="true"' : ''; ?>>
									<img src="<?php echo esc_url( $img['src'] ); ?>"<?php echo $img['srcset'] ? ' srcset="' . esc_attr( $img['srcset'] ) . '" sizes="200px"' : ''; ?> alt="" loading="lazy" decoding="async" draggable="false">
								</button>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>

			</div>
		</div>
		<?php
	}
}
