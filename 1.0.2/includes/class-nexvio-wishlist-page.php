<?php
/**
 * The "My Wishlist" page.
 *
 * - Created by itself when the plugin is activated (and adopted when a page called "wishlist" already exists).
 * - Uses the Elementor Full Width template, so the theme header and footer stay but the content is not squeezed.
 * - The page title is hidden: the wishlist card has its own heading.
 * - The Wishlist Card widget is placed on it with Elementor.
 * The same look is applied when a page is chosen in Nexvio > Wishlist.
 *
 * @package Nexvio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Nexvio_Wishlist_Page {

	const OPTION  = 'nexvio_wishlist_page';
	const PENDING = 'nexvio_wishlist_page_pending';
	const TEMPLATE = 'elementor_header_footer'; /* Elementor "Full Width" */

	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'finish_pending' ) );
		add_filter( 'body_class', array( __CLASS__, 'body_class' ) );
		add_filter( 'the_title', array( __CLASS__, 'blank_title' ), 10, 2 );
		add_action( 'wp_head', array( __CLASS__, 'title_css' ), 99 );
	}

	/* ------------------------------------------------------------------
	 * Activation
	 * ------------------------------------------------------------------ */

	/** Called when the plugin is activated: make sure a wishlist page exists. */
	public static function on_activate() {
		$current = (int) get_option( self::OPTION, 0 );
		if ( $current && 'publish' === get_post_status( $current ) ) {
			self::ensure_widget( $current );
			return;
		}
		$existing = get_page_by_path( 'wishlist', OBJECT, 'page' );
		if ( $existing && 'publish' === $existing->post_status ) {
			update_option( self::OPTION, (int) $existing->ID );
			self::ensure_widget( (int) $existing->ID );
			return;
		}
		self::create();
	}

	/** Create the page and put the widget on it. Returns the page id (0 on failure). */
	public static function create() {
		$id = wp_insert_post( array(
			'post_title'  => __( 'My Wishlist', 'nexvio' ),
			'post_name'   => 'wishlist',
			'post_type'   => 'page',
			'post_status' => 'publish',
		) );
		if ( ! $id || is_wp_error( $id ) ) {
			return 0;
		}
		update_option( self::OPTION, (int) $id );
		self::ensure_widget( (int) $id );
		return (int) $id;
	}

	/** Elementor was not ready at activation: finish the page once it is. */
	public static function finish_pending() {
		$id = (int) get_option( self::PENDING, 0 );
		if ( ! $id || ! self::elementor_ready() ) {
			return;
		}
		delete_option( self::PENDING );
		self::ensure_widget( $id );
	}

	/* ------------------------------------------------------------------
	 * The page itself
	 * ------------------------------------------------------------------ */

	private static function elementor_ready() {
		return class_exists( '\Elementor\Plugin' );
	}

	private static function el_id() {
		return substr( md5( wp_generate_password( 12, false ) . microtime() ), 0, 7 );
	}

	private static function has_widget( $elements ) {
		foreach ( (array) $elements as $el ) {
			if ( ! is_array( $el ) ) {
				continue;
			}
			if ( isset( $el['widgetType'] ) && in_array( $el['widgetType'], array( 'nexvio_wishlist_card', 'nexvio_wishlist_grid' ), true ) ) {
				return true;
			}
			if ( ! empty( $el['elements'] ) && self::has_widget( $el['elements'] ) ) {
				return true;
			}
		}
		return false;
	}

	private static function containers_on() {
		if ( ! self::elementor_ready() ) {
			return false;
		}
		try {
			$exp = \Elementor\Plugin::$instance->experiments;
			return $exp && $exp->is_feature_active( 'container' );
		} catch ( \Throwable $e ) {
			return false;
		}
	}

	/** Full width template + page title hidden (only set when the page has not been given its own choice). */
	public static function style_page( $page_id ) {
		$page_id = (int) $page_id;
		$tpl     = get_post_meta( $page_id, '_wp_page_template', true );
		if ( ! $tpl || 'default' === $tpl ) {
			update_post_meta( $page_id, '_wp_page_template', self::TEMPLATE );
		}
		$ps = get_post_meta( $page_id, '_elementor_page_settings', true );
		$ps = is_array( $ps ) ? $ps : array();
		if ( ! isset( $ps['hide_title'] ) ) {
			$ps['hide_title'] = 'yes';
			update_post_meta( $page_id, '_elementor_page_settings', $ps );
		}
	}

	/** Put the Wishlist Card widget on the page (once), full width, no page title. */
	public static function ensure_widget( $page_id ) {
		$page_id = (int) $page_id;
		$post    = $page_id ? get_post( $page_id ) : null;
		if ( ! $post || 'page' !== $post->post_type ) {
			return;
		}
		self::style_page( $page_id );

		if ( ! self::elementor_ready() ) {
			update_option( self::PENDING, $page_id, false ); /* finished when Elementor is there */
			return;
		}

		$raw  = get_post_meta( $page_id, '_elementor_data', true );
		$data = is_string( $raw ) && '' !== $raw ? json_decode( $raw, true ) : ( is_array( $raw ) ? $raw : array() );
		$data = is_array( $data ) ? $data : array();
		if ( self::has_widget( $data ) ) {
			return;
		}

		$pad = function ( $t, $r, $b, $l ) {
			return array( 'unit' => 'px', 'top' => (string) $t, 'right' => (string) $r, 'bottom' => (string) $b, 'left' => (string) $l, 'isLinked' => false );
		};

		$widgets = array();
		/* text of a page that was not built with Elementor stays above the wishlist */
		if ( ! $data && '' !== trim( wp_strip_all_tags( (string) $post->post_content ) ) ) {
			$widgets[] = array( 'id' => self::el_id(), 'elType' => 'widget', 'widgetType' => 'text-editor', 'settings' => array( 'editor' => (string) $post->post_content ), 'elements' => array() );
		}
		$widgets[] = array( 'id' => self::el_id(), 'elType' => 'widget', 'widgetType' => 'nexvio_wishlist_card', 'settings' => array(), 'elements' => array() );

		if ( self::containers_on() ) {
			$data[] = array(
				'id'       => self::el_id(),
				'elType'   => 'container',
				'settings' => array(
					'content_width'  => 'full',
					'flex_direction' => 'column',
					'padding'        => $pad( 40, 20, 60, 20 ),
					'padding_mobile' => $pad( 24, 16, 40, 16 ),
				),
				'elements' => $widgets,
				'isInner'  => false,
			);
		} else {
			$cols = array();
			$cols[] = array(
				'id'       => self::el_id(),
				'elType'   => 'column',
				'settings' => array( '_column_size' => 100, '_inline_size' => null ),
				'elements' => $widgets,
				'isInner'  => false,
			);
			$data[] = array(
				'id'       => self::el_id(),
				'elType'   => 'section',
				'settings' => array(
					'layout'         => 'full_width',
					'padding'        => $pad( 40, 20, 60, 20 ),
					'padding_mobile' => $pad( 24, 16, 40, 16 ),
				),
				'elements' => $cols,
				'isInner'  => false,
			);
		}

		update_post_meta( $page_id, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );
		update_post_meta( $page_id, '_elementor_edit_mode', 'builder' );
		update_post_meta( $page_id, '_elementor_template_type', 'wp-page' );
		if ( defined( 'ELEMENTOR_VERSION' ) ) {
			update_post_meta( $page_id, '_elementor_version', ELEMENTOR_VERSION );
		}
		delete_post_meta( $page_id, '_elementor_css' );
		delete_option( self::PENDING );
	}

	/* ------------------------------------------------------------------
	 * Hide the title on the wishlist page (any theme)
	 * ------------------------------------------------------------------ */

	private static function is_wishlist_page() {
		$id = (int) get_option( self::OPTION, 0 );
		return $id && is_singular( 'page' ) && (int) get_queried_object_id() === $id;
	}

	public static function body_class( $classes ) {
		if ( self::is_wishlist_page() ) {
			$classes[] = 'nexvio-wishlist-page';
		}
		return $classes;
	}

	/** The page title in the main loop is left empty; menus and the browser tab keep the real title. */
	public static function blank_title( $title, $post_id = 0 ) {
		if ( ! is_admin() && in_the_loop() && is_main_query() && self::is_wishlist_page() && (int) $post_id === (int) get_queried_object_id() ) {
			return '';
		}
		return $title;
	}

	public static function title_css() {
		if ( ! self::is_wishlist_page() ) {
			return;
		}
		echo '<style id="nexvio-wishlist-page">body.nexvio-wishlist-page :is(.entry-title,.page-title,.elementor-page-title,.page-header,.entry-header,.wp-block-post-title,.ast-archive-description,.page-header-title,.post-title,.ct-title-container,.hero-title){display:none!important}body.nexvio-wishlist-page .elementor-widget-theme-post-title{display:none}</style>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static CSS.
	}
}
