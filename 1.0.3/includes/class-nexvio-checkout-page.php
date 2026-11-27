<?php
/**
 * The WooCommerce checkout page, set up by default with the Elementor Checkout widget
 * (instead of the [woocommerce_checkout] shortcode or the Checkout block).
 *
 * Default design: Elementor Full Width page, page title hidden,
 * padding 80px 20px (desktop), 60px 20px (tablet), 40px 15px (mobile).
 *
 * It only touches a checkout page that has no Elementor design yet (or one that holds the shortcode).
 * A checkout page you already designed in Elementor is never changed.
 * The Elementor Checkout widget is part of Elementor Pro: until Elementor Pro is active
 * the page is left as it is, and it is set up as soon as Elementor Pro and WooCommerce are active.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Nexvio_Checkout_Page {

	const PENDING  = 'nexvio_checkout_page_pending';
	const TEMPLATE = 'elementor_header_footer'; /* Elementor "Full Width" */
	const WIDGET   = 'woocommerce-checkout-page'; /* the Elementor Pro Checkout widget */

	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'finish_pending' ) );
		add_filter( 'body_class', array( __CLASS__, 'body_class' ) );
		add_action( 'wp_head', array( __CLASS__, 'title_css' ), 99 );
		add_action( 'wp_head', array( __CLASS__, 'order_review_css' ), 100 );
	}

	/* Order review (checkout): the product name stays on one line with its details */
	public static function order_review_css() {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
			return;
		}
		echo '<style id="nexvio-checkout-order-review">.woocommerce-page.woocommerce-checkout form #order_review td.product-name,.woocommerce.woocommerce-checkout form #order_review td.product-name{font-weight:500;display:inline;flex-wrap:wrap}</style>' . "\n"; // phpcs:ignore
	}

	/* Runs when the plugin is activated */
	public static function on_activate() {
		if ( ! self::apply() ) {
			update_option( self::PENDING, 1 ); /* try again when Elementor Pro / WooCommerce are ready */
		}
	}

	public static function finish_pending() {
		if ( ! get_option( self::PENDING ) ) {
			return;
		}
		if ( self::apply() ) {
			delete_option( self::PENDING );
		}
	}

	private static function ready() {
		return defined( 'ELEMENTOR_VERSION' ) && defined( 'ELEMENTOR_PRO_VERSION' ) && class_exists( '\Elementor\Plugin' ) && class_exists( 'WooCommerce' );
	}

	private static function checkout_id() {
		$id = function_exists( 'wc_get_page_id' ) ? (int) wc_get_page_id( 'checkout' ) : (int) get_option( 'woocommerce_checkout_page_id', 0 );
		if ( $id > 0 && 'publish' === get_post_status( $id ) ) {
			return $id;
		}
		return 0;
	}

	private static function el_id() {
		return substr( md5( wp_generate_password( 12, false ) . microtime() ), 0, 7 );
	}

	private static function pad( $top, $right, $bottom, $left ) {
		return array( 'unit' => 'px', 'top' => (string) $top, 'right' => (string) $right, 'bottom' => (string) $bottom, 'left' => (string) $left, 'isLinked' => false );
	}

	private static function containers_on() {
		try {
			$exp = \Elementor\Plugin::$instance->experiments;
			return $exp && $exp->is_feature_active( 'container' );
		} catch ( \Throwable $e ) {
			return false;
		}
	}

	private static function widget() {
		return array( 'id' => self::el_id(), 'elType' => 'widget', 'widgetType' => self::WIDGET, 'settings' => array(), 'elements' => array() );
	}

	/* true when the page already has the checkout widget */
	private static function has_widget( $elements ) {
		foreach ( (array) $elements as $el ) {
			if ( ! is_array( $el ) ) {
				continue;
			}
			if ( isset( $el['widgetType'] ) && self::WIDGET === $el['widgetType'] ) {
				return true;
			}
			if ( ! empty( $el['elements'] ) && self::has_widget( $el['elements'] ) ) {
				return true;
			}
		}
		return false;
	}

	/* replaces a shortcode widget holding [woocommerce_checkout] with the checkout widget; returns true when it did */
	private static function swap_shortcode( &$elements ) {
		$done = false;
		foreach ( $elements as &$el ) {
			if ( ! is_array( $el ) ) {
				continue;
			}
			if ( isset( $el['widgetType'] ) && in_array( $el['widgetType'], array( 'shortcode', 'text-editor', 'html' ), true ) ) {
				$text = isset( $el['settings']['shortcode'] ) ? $el['settings']['shortcode'] : ( isset( $el['settings']['editor'] ) ? $el['settings']['editor'] : ( isset( $el['settings']['html'] ) ? $el['settings']['html'] : '' ) );
				if ( is_string( $text ) && false !== strpos( $text, 'woocommerce_checkout' ) ) {
					$el   = self::widget();
					$done = true;
					continue;
				}
			}
			if ( ! empty( $el['elements'] ) && self::swap_shortcode( $el['elements'] ) ) {
				$done = true;
			}
		}
		unset( $el );
		return $done;
	}

	private static function style_page( $page_id ) {
		$tpl = get_post_meta( $page_id, '_wp_page_template', true );
		if ( ! $tpl || 'default' === $tpl ) {
			update_post_meta( $page_id, '_wp_page_template', self::TEMPLATE );
		}
		$ps = get_post_meta( $page_id, '_elementor_page_settings', true );
		$ps = is_array( $ps ) ? $ps : array();
		if ( ! isset( $ps['hide_title'] ) ) {
			$ps['hide_title'] = 'yes';
			update_post_meta( $page_id, '_elementor_page_settings', $ps );
		}
		update_post_meta( $page_id, 'site-post-title', 'disabled' ); /* Astra and similar themes */
	}

	/* Returns true when the checkout page is done (or does not need anything), false when it has to wait */
	public static function apply() {
		if ( ! self::ready() ) {
			return false;
		}
		$page_id = self::checkout_id();
		if ( ! $page_id ) {
			return false;
		}
		$raw  = get_post_meta( $page_id, '_elementor_data', true );
		$data = is_string( $raw ) && '' !== $raw ? json_decode( $raw, true ) : ( is_array( $raw ) ? $raw : array() );
		$data = is_array( $data ) ? $data : array();

		if ( $data ) {
			if ( self::has_widget( $data ) ) {
				return true; /* already done */
			}
			if ( self::swap_shortcode( $data ) ) {
				self::save( $page_id, $data, false );
			}
			return true; /* the person designed this page: leave it */
		}

		/* no Elementor design yet: build the default one */
		if ( self::containers_on() ) {
			$data[] = array(
				'id'             => self::el_id(),
				'elType'         => 'container',
				'settings'       => array(
					'content_width'  => 'full',
					'flex_direction' => 'column',
					'padding'        => self::pad( 80, 20, 80, 20 ),
					'padding_tablet' => self::pad( 60, 20, 60, 20 ),
					'padding_mobile' => self::pad( 40, 15, 40, 15 ),
				),
				'elements'       => array( self::widget() ),
				'isInner'        => false,
			);
		} else {
			$data[] = array(
				'id'       => self::el_id(),
				'elType'   => 'section',
				'settings' => array(
					'layout'         => 'full_width',
					'padding'        => self::pad( 80, 20, 80, 20 ),
					'padding_tablet' => self::pad( 60, 20, 60, 20 ),
					'padding_mobile' => self::pad( 40, 15, 40, 15 ),
				),
				'elements' => array(
					array(
						'id'       => self::el_id(),
						'elType'   => 'column',
						'settings' => array( '_column_size' => 100, '_inline_size' => null ),
						'elements' => array( self::widget() ),
						'isInner'  => false,
					),
				),
				'isInner'  => false,
			);
		}
		self::save( $page_id, $data, true );
		return true;
	}

	private static function save( $page_id, $data, $restyle ) {
		if ( $restyle ) {
			self::style_page( $page_id );
			$post = get_post( $page_id );
			if ( $post && '' !== trim( (string) $post->post_content ) ) {
				update_post_meta( $page_id, '_nexvio_checkout_backup', $post->post_content ); /* the old shortcode / block, in case it is needed */
				wp_update_post( array( 'ID' => $page_id, 'post_content' => '' ) );
			}
		}
		update_post_meta( $page_id, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );
		update_post_meta( $page_id, '_elementor_edit_mode', 'builder' );
		update_post_meta( $page_id, '_elementor_template_type', 'wp-page' );
		if ( defined( 'ELEMENTOR_VERSION' ) ) {
			update_post_meta( $page_id, '_elementor_version', ELEMENTOR_VERSION );
		}
		delete_post_meta( $page_id, '_elementor_css' );
	}

	public static function body_class( $classes ) {
		if ( self::is_ours() ) {
			$classes[] = 'nexvio-checkout-page';
		}
		return $classes;
	}

	/* the page title is hidden by default, also on themes that ignore the Elementor "hide title" setting */
	private static function is_ours() {
		$id = self::checkout_id();
		if ( ! $id || ! is_singular( 'page' ) || (int) get_queried_object_id() !== $id ) {
			return false;
		}
		$ps = get_post_meta( $id, '_elementor_page_settings', true );
		return is_array( $ps ) && isset( $ps['hide_title'] ) && 'yes' === $ps['hide_title'];
	}

	public static function title_css() {
		if ( ! self::is_ours() ) {
			return;
		}
		echo '<style id="nexvio-checkout-page">body.nexvio-checkout-page :is(.entry-title,.page-title,.elementor-page-title,.page-header,.entry-header,.wp-block-post-title,.ast-archive-description,.page-header-title,.post-title,.ct-title-container,.hero-title){display:none!important}body.nexvio-checkout-page .elementor-widget-theme-post-title{display:none}</style>' . "\n"; // phpcs:ignore
	}
}
