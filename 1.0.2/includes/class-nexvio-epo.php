<?php
/**
 * Extra Options - core: option forms, assignment to products, validation, pricing,
 * cart, order, uploads.
 *
 * A "form" is a set of extra fields (text, dropdown, swatches, file upload…) that is
 * assigned to all products, to categories, tags or to chosen products. The fields show on
 * the single product page (Extra Options widget or automatic), their prices are added to
 * the product price, and the choices travel through the cart into the order.
 *
 * @package Nexvio_For_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Nexvio_Epo {

	const CPT        = 'nexvio_epo';
	const META_FIELDS = '_nexvio_epo_fields';
	const META_RULES  = '_nexvio_epo_rules';
	const META_PRIO   = '_nexvio_epo_priority';

	/** Hard limits for what a customer can send (the limits an admin sets on a field come on top). */
	const MAX_TEXT   = 5000;
	const MAX_NUMBER = 1000000000;

	private static $forms      = null;
	private static $field_memo = array();
	private static $base       = array();

	/* ------------------------------------------------------------------
	 * Boot
	 * ------------------------------------------------------------------ */

	public static function register_type() {
		register_post_type( self::CPT, array(
			'labels'              => array( 'name' => __( 'Extra option forms', 'nexvio' ), 'singular_name' => __( 'Extra option form', 'nexvio' ) ),
			'public'              => false,
			'show_ui'             => false,
			'show_in_menu'        => false,
			'show_in_rest'        => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'rewrite'             => false,
			'supports'            => array( 'title' ),
			'capability_type'     => 'post',
		) );
	}

	public static function init() {
		/* validation, cart, order */
		add_filter( 'woocommerce_add_to_cart_validation', array( __CLASS__, 'validate_add' ), 20, 5 );
		add_filter( 'woocommerce_add_cart_item_data', array( __CLASS__, 'cart_item_data' ), 20, 4 );
		add_action( 'woocommerce_before_calculate_totals', array( __CLASS__, 'apply_prices' ), 20 );
		add_filter( 'woocommerce_get_item_data', array( __CLASS__, 'item_data' ), 20, 2 );
		add_action( 'woocommerce_checkout_create_order_line_item', array( __CLASS__, 'order_item' ), 20, 4 );

		/* shop lists: products with required fields open the product page */
		add_filter( 'woocommerce_product_add_to_cart_url', array( __CLASS__, 'loop_url' ), 20, 2 );
		add_filter( 'woocommerce_product_add_to_cart_text', array( __CLASS__, 'loop_text' ), 20, 2 );
		add_filter( 'woocommerce_product_supports', array( __CLASS__, 'loop_supports' ), 20, 3 );

		/* automatic display on the product page */
		add_action( 'wp', array( __CLASS__, 'auto_display' ) );

		/* uploads */
		add_action( 'wp_ajax_nexvio_epo_upload', array( __CLASS__, 'ajax_upload' ) );
		add_action( 'wp_ajax_nopriv_nexvio_epo_upload', array( __CLASS__, 'ajax_upload' ) );

		/* product screen */
		Nexvio_Epo_Faq::init();
		if ( is_admin() ) {
			add_action( 'add_meta_boxes_product', array( __CLASS__, 'add_box' ) );
			add_action( 'save_post_product', array( __CLASS__, 'save_box' ), 10, 2 );
		}
	}

	/* ------------------------------------------------------------------
	 * Field types
	 * ------------------------------------------------------------------ */

	/** key => label, icon, description */
	public static function types() {
		return array(
			'text'     => array( __( 'Text', 'nexvio' ), 'dashicons-editor-textcolor', __( 'One line of text, e.g. a name to print.', 'nexvio' ) ),
			'textarea' => array( __( 'Text area', 'nexvio' ), 'dashicons-text-page', __( 'Several lines of text, e.g. a gift message.', 'nexvio' ) ),
			'number'   => array( __( 'Number', 'nexvio' ), 'dashicons-calculator', __( 'A number, with a smallest and largest value.', 'nexvio' ) ),
			'select'   => array( __( 'Dropdown', 'nexvio' ), 'dashicons-arrow-down-alt2', __( 'Choose one value from a list.', 'nexvio' ) ),
			'radio'    => array( __( 'Radio buttons', 'nexvio' ), 'dashicons-marker', __( 'Choose one value, all visible.', 'nexvio' ) ),
			'checkbox' => array( __( 'Checkboxes', 'nexvio' ), 'dashicons-yes-alt', __( 'Choose any number of values.', 'nexvio' ) ),
			'swatch'   => array( __( 'Colour / image swatches', 'nexvio' ), 'dashicons-art', __( 'Choices shown as colour circles or pictures.', 'nexvio' ) ),
			'color'    => array( __( 'Colour picker', 'nexvio' ), 'dashicons-admin-appearance', __( 'The customer picks any colour.', 'nexvio' ) ),
			'date'     => array( __( 'Date', 'nexvio' ), 'dashicons-calendar-alt', __( 'A date picker.', 'nexvio' ) ),
			'time'     => array( __( 'Time', 'nexvio' ), 'dashicons-clock', __( 'A time picker.', 'nexvio' ) ),
			'range'    => array( __( 'Slider', 'nexvio' ), 'dashicons-leftright', __( 'A number chosen with a slider.', 'nexvio' ) ),
			'upload'   => array( __( 'File upload', 'nexvio' ), 'dashicons-upload', __( 'The customer sends a file (logo, photo, design).', 'nexvio' ) ),
			'header'   => array( __( 'Heading', 'nexvio' ), 'dashicons-heading', __( 'A title with an optional text, to group fields.', 'nexvio' ) ),
			'faq'      => array( __( 'FAQ / Features', 'nexvio' ), 'dashicons-editor-help', __( 'A list of questions and answers, or product features (a title and a text). Choose the products it shows on.', 'nexvio' ) ),
			'divider'  => array( __( 'Divider', 'nexvio' ), 'dashicons-minus', __( 'A line between fields.', 'nexvio' ) ),
		);
	}

	public static function choice_types() {
		return array( 'select', 'radio', 'checkbox', 'swatch' );
	}

	public static function layout_types() {
		return array( 'header', 'divider', 'faq' );
	}

	/** Price types a field type may use. */
	public static function price_types( $type ) {
		if ( in_array( $type, self::layout_types(), true ) || in_array( $type, self::choice_types(), true ) ) {
			return array( 'none' );
		}
		$base = array( 'none', 'fixed', 'percent' );
		if ( 'text' === $type || 'textarea' === $type ) {
			$base[] = 'char';
		}
		if ( 'number' === $type || 'range' === $type ) {
			$base[] = 'value';
		}
		return $base;
	}

	/* ------------------------------------------------------------------
	 * Sanitizing (admin input and stored data)
	 * ------------------------------------------------------------------ */

	private static function num( $v, $default = 0.0 ) {
		if ( is_string( $v ) ) {
			$v = str_replace( ',', '.', trim( $v ) );
		}
		return is_numeric( $v ) ? (float) $v : $default;
	}

	private static function bool( $v ) {
		return true === $v || 1 === $v || '1' === $v || 'yes' === $v || 'true' === $v;
	}

	private static function clean_id( $id, array &$used, $prefix ) {
		$id = strtolower( preg_replace( '/[^a-zA-Z0-9_]/', '', (string) $id ) );
		$id = substr( $id, 0, 24 );
		if ( '' === $id || isset( $used[ $id ] ) ) {
			do {
				$id = $prefix . strtolower( wp_generate_password( 7, false, false ) );
			} while ( isset( $used[ $id ] ) );
		}
		$used[ $id ] = true;
		return $id;
	}

	/** Clean the list of fields sent by the builder. */
	public static function sanitize_fields( $list ) {
		$types = self::types();
		$out   = array();
		$used  = array();
		foreach ( array_slice( is_array( $list ) ? $list : array(), 0, 100 ) as $f ) {
			if ( ! is_array( $f ) || empty( $f['type'] ) || ! isset( $types[ $f['type'] ] ) ) {
				continue;
			}
			$type  = $f['type'];
			$clean = array(
				'id'          => self::clean_id( isset( $f['id'] ) ? $f['id'] : '', $used, 'f' ),
				'type'        => $type,
				'label'       => isset( $f['label'] ) ? sanitize_text_field( $f['label'] ) : '',
				'description' => isset( $f['description'] ) ? sanitize_text_field( $f['description'] ) : '',
			);
			if ( 'divider' === $type ) {
				$clean['label'] = '';
			}
			if ( 'header' === $type ) {
				$clean['tag'] = isset( $f['tag'] ) && in_array( $f['tag'], array( 'h2', 'h3', 'h4' ), true ) ? $f['tag'] : 'h3';
			}
			if ( 'faq' === $type ) {
				$clean['style']      = isset( $f['style'] ) && in_array( $f['style'], array( 'accordion', 'multi', 'static' ), true ) ? $f['style'] : 'accordion';
				$clean['kind']       = isset( $f['kind'] ) && in_array( $f['kind'], array( 'faq', 'features', 'info' ), true ) ? $f['kind'] : 'faq';
				$clean['look']       = isset( $f['look'] ) && in_array( $f['look'], Nexvio_Epo_Faq::looks(), true ) ? $f['look'] : 'cards';
				$clean['badge']      = isset( $f['badge'] ) && in_array( $f['badge'], array( 'auto', 'qa', 'number', 'check', 'none' ), true ) ? $f['badge'] : 'auto';
				$clean['open_first'] = self::bool( isset( $f['open_first'] ) ? $f['open_first'] : false );
				$clean['items']      = self::sanitize_faq_items( isset( $f['items'] ) ? $f['items'] : array() );
				/* which products show this FAQ: the ones chosen here, or (older fields) every product the form applies to */
				$clean['apply']      = isset( $f['apply'] ) && in_array( $f['apply'], array( 'products', 'form' ), true ) ? $f['apply'] : 'form';
				$clean['products']   = array_slice( array_values( array_unique( array_filter( array_map( 'absint', isset( $f['products'] ) && is_array( $f['products'] ) ? $f['products'] : array() ) ) ) ), 0, 500 );
			}
			if ( ! in_array( $type, self::layout_types(), true ) ) {
				$clean['required']   = self::bool( isset( $f['required'] ) ? $f['required'] : false );
				$clean['hide_label'] = self::bool( isset( $f['hide_label'] ) ? $f['hide_label'] : false );
				$clean['width']      = isset( $f['width'] ) && in_array( $f['width'], array( 'full', 'half', 'third' ), true ) ? $f['width'] : 'full';
				$clean['placeholder'] = isset( $f['placeholder'] ) ? sanitize_text_field( $f['placeholder'] ) : '';
				$clean['default']    = isset( $f['default'] ) && is_scalar( $f['default'] ) ? sanitize_text_field( (string) $f['default'] ) : '';

				$ptypes             = self::price_types( $type );
				$clean['price_type'] = isset( $f['price_type'] ) && in_array( $f['price_type'], $ptypes, true ) ? $f['price_type'] : 'none';
				$clean['price']      = max( -1000000, min( 1000000, self::num( isset( $f['price'] ) ? $f['price'] : 0 ) ) );

				if ( in_array( $type, array( 'text', 'textarea' ), true ) ) {
					$clean['maxlength'] = isset( $f['maxlength'] ) ? max( 0, min( 5000, (int) $f['maxlength'] ) ) : 0;
				}
				if ( in_array( $type, array( 'number', 'range' ), true ) ) {
					$clean['min']  = isset( $f['min'] ) && '' !== $f['min'] ? self::num( $f['min'] ) : ( 'range' === $type ? 0 : '' );
					$clean['max']  = isset( $f['max'] ) && '' !== $f['max'] ? self::num( $f['max'] ) : ( 'range' === $type ? 100 : '' );
					$clean['step'] = isset( $f['step'] ) && self::num( $f['step'] ) > 0 ? self::num( $f['step'] ) : 1;
				}
				if ( in_array( $type, array( 'radio', 'checkbox' ), true ) ) {
					$clean['display'] = isset( $f['display'] ) && in_array( $f['display'], array( 'list', 'inline', 'buttons' ), true ) ? $f['display'] : 'list';
				}
				if ( 'swatch' === $type ) {
					$clean['swatch_mode'] = isset( $f['swatch_mode'] ) && 'image' === $f['swatch_mode'] ? 'image' : 'color';
					$clean['multiple']    = self::bool( isset( $f['multiple'] ) ? $f['multiple'] : false );
				}
				if ( in_array( $type, self::choice_types(), true ) ) {
					$clean['choices'] = self::sanitize_choices( isset( $f['choices'] ) ? $f['choices'] : array(), $type, ! empty( $clean['multiple'] ) || 'checkbox' === $type );
				}
			}
			$clean['logic'] = self::sanitize_logic( isset( $f['logic'] ) ? $f['logic'] : array() );
			$out[]          = $clean;
		}
		return $out;
	}

	/** Questions and answers of a FAQ field (also used for the FAQ typed on a product). */
	public static function sanitize_faq_items( $list ) {
		$out  = array();
		$used = array();
		foreach ( array_slice( is_array( $list ) ? $list : array(), 0, 100 ) as $it ) {
			if ( ! is_array( $it ) ) {
				continue;
			}
			$q = isset( $it['q'] ) ? sanitize_text_field( $it['q'] ) : '';
			$a = isset( $it['a'] ) && is_scalar( $it['a'] ) ? wp_kses_post( (string) $it['a'] ) : '';
			if ( '' === $q && '' === trim( wp_strip_all_tags( $a ) ) ) {
				continue;
			}
			$out[] = array( 'id' => self::clean_id( isset( $it['id'] ) ? $it['id'] : '', $used, 'q' ), 'q' => $q, 'a' => $a );
		}
		return $out;
	}

	/** A choice has a sale price that is really lower than its regular price. */
	public static function has_sale( $c ) {
		return isset( $c['sale'] ) && '' !== $c['sale'] && null !== $c['sale'] && (float) $c['sale'] < (float) $c['price'];
	}

	/** The price a customer pays for a choice: the sale price when there is one, else the regular price. */
	public static function choice_price( $c ) {
		return self::has_sale( $c ) ? (float) $c['sale'] : (float) $c['price'];
	}

	private static function sanitize_choices( $list, $type, $multi ) {
		$out  = array();
		$used = array();
		$has_default = false;
		foreach ( array_slice( is_array( $list ) ? $list : array(), 0, 100 ) as $c ) {
			if ( ! is_array( $c ) ) {
				continue;
			}
			$label = isset( $c['label'] ) ? sanitize_text_field( $c['label'] ) : '';
			if ( '' === $label ) {
				continue;
			}
			$color   = isset( $c['color'] ) ? sanitize_hex_color( (string) $c['color'] ) : '';
			$default = self::bool( isset( $c['default'] ) ? $c['default'] : false );
			if ( $default && ! $multi ) {
				$default     = ! $has_default;
				$has_default = true;
			}
			$out[] = array(
				'id'         => self::clean_id( isset( $c['id'] ) ? $c['id'] : '', $used, 'c' ),
				'label'      => $label,
				'price'      => max( -1000000, min( 1000000, self::num( isset( $c['price'] ) ? $c['price'] : 0 ) ) ),
				'price_type' => isset( $c['price_type'] ) && 'percent' === $c['price_type'] ? 'percent' : 'fixed',
				'sale'       => isset( $c['sale'] ) && '' !== trim( (string) $c['sale'] ) ? max( -1000000, min( 1000000, self::num( $c['sale'] ) ) ) : '',
				'image'      => isset( $c['image'] ) ? esc_url_raw( (string) $c['image'] ) : '',
				'color'      => $color ? $color : '',
				'default'    => $default,
			);
		}
		return $out;
	}

	/** Every comparison a rule can use. The builder shows the ones that fit the type of the field. */
	public static function logic_ops() {
		return array(
			'is'          => __( 'is', 'nexvio' ),
			'isnot'       => __( 'is not', 'nexvio' ),
			'contains'    => __( 'contains', 'nexvio' ),
			'notcontains' => __( 'does not contain', 'nexvio' ),
			'starts'      => __( 'starts with', 'nexvio' ),
			'ends'        => __( 'ends with', 'nexvio' ),
			'eq'          => __( 'equals', 'nexvio' ),
			'neq'         => __( 'does not equal', 'nexvio' ),
			'gt'          => __( 'is greater than', 'nexvio' ),
			'gte'         => __( 'is at least', 'nexvio' ),
			'lt'          => __( 'is less than', 'nexvio' ),
			'lte'         => __( 'is at most', 'nexvio' ),
			'in'          => __( 'is any of', 'nexvio' ),
			'notin'       => __( 'is none of', 'nexvio' ),
			'lengt'       => __( 'is longer than', 'nexvio' ),
			'lenlt'       => __( 'is shorter than', 'nexvio' ),
			'countgte'    => __( 'has at least this many picked', 'nexvio' ),
			'countlte'    => __( 'has at most this many picked', 'nexvio' ),
			'before'      => __( 'is before', 'nexvio' ),
			'after'       => __( 'is after', 'nexvio' ),
			'empty'       => __( 'is empty', 'nexvio' ),
			'notempty'    => __( 'is filled in', 'nexvio' ),
		);
	}

	private static function sanitize_logic( $l ) {
		$l     = is_array( $l ) ? $l : array();
		$rules = array();
		foreach ( array_slice( isset( $l['rules'] ) && is_array( $l['rules'] ) ? $l['rules'] : array(), 0, 10 ) as $r ) {
			if ( ! is_array( $r ) || empty( $r['field'] ) ) {
				continue;
			}
			$op      = isset( $r['op'] ) && isset( self::logic_ops()[ $r['op'] ] ) ? $r['op'] : 'is';
			$rules[] = array(
				'field' => strtolower( preg_replace( '/[^a-zA-Z0-9_]/', '', (string) $r['field'] ) ),
				'op'    => $op,
				'value' => isset( $r['value'] ) && is_scalar( $r['value'] ) ? sanitize_text_field( (string) $r['value'] ) : '',
			);
		}
		return array(
			'enabled' => self::bool( isset( $l['enabled'] ) ? $l['enabled'] : false ) && ! empty( $rules ),
			'action'  => isset( $l['action'] ) && 'hide' === $l['action'] ? 'hide' : 'show',
			'match'   => isset( $l['match'] ) && 'any' === $l['match'] ? 'any' : 'all',
			'rules'   => $rules,
		);
	}

	public static function sanitize_rules( $r ) {
		$r     = is_array( $r ) ? $r : array();
		$apply = isset( $r['apply'] ) && in_array( $r['apply'], array( 'all', 'categories', 'products', 'tags' ), true ) ? $r['apply'] : 'all';
		$ids   = function ( $key ) use ( $r ) {
			return array_values( array_unique( array_filter( array_map( 'absint', isset( $r[ $key ] ) && is_array( $r[ $key ] ) ? $r[ $key ] : array() ) ) ) );
		};
		return array(
			'apply'        => $apply,
			'cats'         => $ids( 'cats' ),
			'tags'         => $ids( 'tags' ),
			'products'     => $ids( 'products' ),
			'excl_cats'    => $ids( 'excl_cats' ),
			'excl_products' => $ids( 'excl_products' ),
		);
	}

	/* ------------------------------------------------------------------
	 * Forms (storage)
	 * ------------------------------------------------------------------ */

	public static function flush() {
		self::$forms      = null;
		self::$field_memo = array();
	}

	private static function read_form( $post ) {
		$fields = json_decode( (string) get_post_meta( $post->ID, self::META_FIELDS, true ), true );
		$rules  = json_decode( (string) get_post_meta( $post->ID, self::META_RULES, true ), true );
		return array(
			'id'       => (int) $post->ID,
			'title'    => $post->post_title,
			'status'   => $post->post_status,
			'priority' => (int) get_post_meta( $post->ID, self::META_PRIO, true ),
			'fields'   => is_array( $fields ) ? $fields : array(),
			'rules'    => self::sanitize_rules( $rules ),
		);
	}

	/** Every form (published, draft), for the admin list. */
	public static function all_forms() {
		$posts = get_posts( array(
			'post_type'      => self::CPT,
			'post_status'    => array( 'publish', 'draft' ),
			'posts_per_page' => 200,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'no_found_rows'  => true,
		) );
		return array_map( array( __CLASS__, 'read_form' ), $posts );
	}

	/** Published forms, lowest priority number first. */
	public static function published_forms() {
		if ( null === self::$forms ) {
			$posts = get_posts( array(
				'post_type'      => self::CPT,
				'post_status'    => 'publish',
				'posts_per_page' => 200,
				'orderby'        => 'date',
				'order'          => 'ASC',
				'no_found_rows'  => true,
			) );
			$forms = array_map( array( __CLASS__, 'read_form' ), $posts );
			usort( $forms, function ( $a, $b ) {
				return $a['priority'] === $b['priority'] ? $a['id'] - $b['id'] : $a['priority'] - $b['priority'];
			} );
			self::$forms = $forms;
		}
		return self::$forms;
	}

	public static function get_form( $id ) {
		$post = get_post( (int) $id );
		return $post && self::CPT === $post->post_type ? self::read_form( $post ) : null;
	}

	/** Create or update a form. Returns the form id. */
	public static function save_form( $id, $title, $status, $fields, $rules, $priority ) {
		$args = array(
			'post_type'   => self::CPT,
			'post_title'  => $title,
			'post_status' => 'draft' === $status ? 'draft' : 'publish',
		);
		if ( $id ) {
			$args['ID'] = (int) $id;
			$id         = wp_update_post( wp_slash( $args ), true );
		} else {
			$id = wp_insert_post( wp_slash( $args ), true );
		}
		if ( is_wp_error( $id ) || ! $id ) {
			return 0;
		}
		update_post_meta( $id, self::META_FIELDS, wp_slash( wp_json_encode( self::sanitize_fields( $fields ) ) ) );
		update_post_meta( $id, self::META_RULES, wp_slash( wp_json_encode( self::sanitize_rules( $rules ) ) ) );
		update_post_meta( $id, self::META_PRIO, (int) $priority );
		self::flush();
		return (int) $id;
	}

	/* ------------------------------------------------------------------
	 * Which fields belong to a product
	 * ------------------------------------------------------------------ */

	private static function term_ids_with_parents( $product_id, $taxonomy ) {
		$ids = wp_get_post_terms( $product_id, $taxonomy, array( 'fields' => 'ids' ) );
		if ( is_wp_error( $ids ) ) {
			return array();
		}
		$all = array();
		foreach ( $ids as $tid ) {
			$all[] = (int) $tid;
			if ( is_taxonomy_hierarchical( $taxonomy ) ) {
				foreach ( get_ancestors( $tid, $taxonomy, 'taxonomy' ) as $a ) {
					$all[] = (int) $a;
				}
			}
		}
		return array_values( array_unique( $all ) );
	}

	private static function rules_match( $rules, $product_id, $cats, $tags ) {
		if ( in_array( $product_id, $rules['excl_products'], true ) ) {
			return false;
		}
		if ( $rules['excl_cats'] && array_intersect( $rules['excl_cats'], $cats ) ) {
			return false;
		}
		switch ( $rules['apply'] ) {
			case 'categories':
				return (bool) array_intersect( $rules['cats'], $cats );
			case 'tags':
				return (bool) array_intersect( $rules['tags'], $tags );
			case 'products':
				return in_array( $product_id, $rules['products'], true );
		}
		return true;
	}

	/** The parent product id of a variation. */
	private static function parent_id( $product_id ) {
		$product_id = (int) $product_id;
		if ( 'product_variation' === get_post_type( $product_id ) ) {
			return (int) wp_get_post_parent_id( $product_id );
		}
		return $product_id;
	}

	/** Forms that apply to a product (product screen choices included). */
	public static function forms_for( $product_id ) {
		$product_id = self::parent_id( $product_id );
		if ( ! $product_id ) {
			return array();
		}
		$disabled = 'yes' === get_post_meta( $product_id, '_nexvio_epo_disable', true );
		$extra    = array_map( 'absint', (array) get_post_meta( $product_id, '_nexvio_epo_forms', true ) );
		$cats     = null;
		$tags     = null;
		$out      = array();
		foreach ( self::published_forms() as $form ) {
			if ( ! $form['fields'] ) {
				continue;
			}
			$pick = in_array( $form['id'], $extra, true );
			if ( ! $pick && ! $disabled ) {
				if ( null === $cats ) {
					$cats = self::term_ids_with_parents( $product_id, 'product_cat' );
					$tags = self::term_ids_with_parents( $product_id, 'product_tag' );
				}
				$pick = self::rules_match( $form['rules'], $product_id, $cats, $tags );
			}
			if ( $pick ) {
				$out[] = $form;
			}
		}
		return apply_filters( 'nexvio_epo_forms_for_product', $out, $product_id );
	}

	/** All fields of a product, in order. */
	public static function fields_for( $product_id ) {
		$product_id = self::parent_id( $product_id );
		if ( isset( self::$field_memo[ $product_id ] ) ) {
			return self::$field_memo[ $product_id ];
		}
		$fields = array();
		$ids    = array();
		foreach ( self::forms_for( $product_id ) as $form ) {
			foreach ( $form['fields'] as $f ) {
				if ( ! is_array( $f ) || empty( $f['id'] ) || isset( $ids[ $f['id'] ] ) ) {
					continue;
				}
				$ids[ $f['id'] ] = true;
				$fields[]        = $f;
			}
		}
		self::$field_memo[ $product_id ] = $fields;
		return $fields;
	}

	/** Does the product have a field the customer must fill in? */
	public static function has_required( $product_id ) {
		foreach ( self::fields_for( $product_id ) as $f ) {
			if ( ! empty( $f['required'] ) ) {
				return true;
			}
		}
		return false;
	}

	/* ------------------------------------------------------------------
	 * Conditional logic (same rules as extra-options.js)
	 * ------------------------------------------------------------------ */

	/**
	 * Is a field visible, given the values of the other fields?
	 *
	 * @param array $field   The field.
	 * @param array $map     id => field.
	 * @param array $values  id => array of values (choice ids or text), of visible fields only.
	 * @param array $state   Memo / cycle guard: id => true|false|null.
	 */
	public static function is_visible( $field, $map, $values, &$state = array() ) {
		$logic = isset( $field['logic'] ) ? $field['logic'] : array();
		if ( empty( $logic['enabled'] ) || empty( $logic['rules'] ) ) {
			return true;
		}
		$results = array();
		foreach ( $logic['rules'] as $r ) {
			$src = $r['field'];
			$val = array();
			if ( 0 === strpos( $src, '__' ) ) {
				$val = isset( $values[ $src ] ) ? (array) $values[ $src ] : array();
			} elseif ( isset( $map[ $src ] ) && isset( $values[ $src ] ) ) {
				if ( array_key_exists( $src, $state ) && null === $state[ $src ] ) {
					$visible = false; /* a loop: treat as hidden */
				} elseif ( array_key_exists( $src, $state ) ) {
					$visible = $state[ $src ];
				} else {
					$state[ $src ] = null;
					$visible       = self::is_visible( $map[ $src ], $map, $values, $state );
					$state[ $src ] = $visible;
				}
				$val = $visible ? (array) $values[ $src ] : array();
			}
			$results[] = self::compare( array_map( 'strval', $val ), $r['op'], (string) $r['value'] );
		}
		$hit = 'any' === $logic['match'] ? in_array( true, $results, true ) : ! in_array( false, $results, true );
		return 'hide' === $logic['action'] ? ! $hit : $hit;
	}

	/** Values that are not fields: quantity, price, who is shopping and the day. Same on the page (JS) and here. */
	public static function context_values( $product_id, $quantity = 1, $variation_id = 0, $base = null ) {
		if ( null === $base ) {
			$p    = $variation_id ? wc_get_product( $variation_id ) : wc_get_product( $product_id );
			$base = $p instanceof WC_Product && ! $p->is_type( 'variable' ) ? (float) wc_get_price_to_display( $p ) : null;
		}
		return array(
			'__qty'   => array( (string) max( 1, (int) $quantity ) ),
			'__price' => null === $base ? array() : array( (string) round( $base, 6 ) ),
			'__user'  => array( is_user_logged_in() ? 'in' : 'out' ),
			'__day'   => array( (string) (int) current_time( 'w' ) ),
		);
	}

	/** One rule: the values of a field against the value of the rule (same as extra-options.js). */
	private static function compare( $val, $op, $rule ) {
		$first = $val ? $val[0] : '';
		$has   = (bool) $val;
		switch ( $op ) {
			case 'is':
				return in_array( $rule, $val, true );
			case 'isnot':
				return ! in_array( $rule, $val, true );
			case 'contains':
			case 'notcontains':
				$hit = false;
				foreach ( $val as $v ) {
					if ( '' !== $rule && false !== stripos( $v, $rule ) ) {
						$hit = true;
					}
				}
				return 'contains' === $op ? $hit : ! $hit;
			case 'starts':
				return $has && '' !== $rule && 0 === stripos( $first, $rule );
			case 'ends':
				return $has && '' !== $rule && strlen( $first ) >= strlen( $rule ) && 0 === strcasecmp( substr( $first, -strlen( $rule ) ), $rule );
			case 'eq':
				return $has && is_numeric( $rule ) && (float) $first === (float) $rule;
			case 'neq':
				return ! ( $has && is_numeric( $rule ) && (float) $first === (float) $rule );
			case 'gt':
				return $has && is_numeric( $rule ) && (float) $first > (float) $rule;
			case 'gte':
				return $has && is_numeric( $rule ) && (float) $first >= (float) $rule;
			case 'lt':
				return $has && is_numeric( $rule ) && (float) $first < (float) $rule;
			case 'lte':
				return $has && is_numeric( $rule ) && (float) $first <= (float) $rule;
			case 'in':
			case 'notin':
				$list = array_filter( array_map( 'trim', explode( ',', strtolower( $rule ) ) ), 'strlen' );
				$hit  = false;
				foreach ( $val as $v ) {
					if ( in_array( strtolower( $v ), $list, true ) ) {
						$hit = true;
					}
				}
				return 'in' === $op ? $hit : ! $hit;
			case 'lengt':
				return is_numeric( $rule ) && mb_strlen( $first ) > (float) $rule;
			case 'lenlt':
				return is_numeric( $rule ) && mb_strlen( $first ) < (float) $rule;
			case 'countgte':
				return is_numeric( $rule ) && count( $val ) >= (float) $rule;
			case 'countlte':
				return is_numeric( $rule ) && count( $val ) <= (float) $rule;
			case 'before':
				return $has && '' !== $rule && strcmp( $first, $rule ) < 0;
			case 'after':
				return $has && '' !== $rule && strcmp( $first, $rule ) > 0;
			case 'empty':
				return ! $has;
			case 'notempty':
				return $has;
		}
		return false;
	}

	/** The values a field has before the customer does anything (its defaults). */
	public static function default_values( $fields ) {
		$out = array();
		foreach ( $fields as $f ) {
			if ( in_array( $f['type'], self::layout_types(), true ) ) {
				continue;
			}
			$vals = array();
			if ( in_array( $f['type'], self::choice_types(), true ) ) {
				foreach ( isset( $f['choices'] ) ? $f['choices'] : array() as $c ) {
					if ( ! empty( $c['default'] ) ) {
						$vals[] = $c['id'];
					}
				}
			} elseif ( isset( $f['default'] ) && '' !== $f['default'] ) {
				$vals[] = (string) $f['default'];
			}
			$out[ $f['id'] ] = $vals;
		}
		return $out;
	}

	/* ------------------------------------------------------------------
	 * Reading the posted values
	 * ------------------------------------------------------------------ */

	private static function posted() {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- WooCommerce add to cart requests carry no nonce.
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- every value is cleaned per field type in normalise().
		$raw = isset( $_POST['nexvio_epo'] ) && is_array( $_POST['nexvio_epo'] ) ? wp_unslash( $_POST['nexvio_epo'] ) : array();
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		return $raw;
	}

	/** Turn one posted value into an array of clean values; null = not valid. */
	private static function normalise( $f, $raw ) {
		$type = $f['type'];
		if ( in_array( $type, self::choice_types(), true ) ) {
			$ids   = array();
			$valid = array();
			foreach ( isset( $f['choices'] ) ? $f['choices'] : array() as $c ) {
				$valid[ $c['id'] ] = true;
			}
			foreach ( (array) $raw as $v ) {
				$v = is_scalar( $v ) ? (string) $v : '';
				if ( '' !== $v && isset( $valid[ $v ] ) && ! in_array( $v, $ids, true ) ) {
					$ids[] = $v;
				}
			}
			$multi = 'checkbox' === $type || ( 'swatch' === $type && ! empty( $f['multiple'] ) );
			return $multi ? $ids : array_slice( $ids, 0, 1 );
		}
		$raw = is_array( $raw ) ? ( $raw ? reset( $raw ) : '' ) : $raw;
		$raw = is_scalar( $raw ) ? trim( (string) $raw ) : '';
		if ( '' === $raw ) {
			return array();
		}
		switch ( $type ) {
			case 'text':
				$v = sanitize_text_field( mb_substr( $raw, 0, self::MAX_TEXT ) );
				break;
			case 'textarea':
				$v = sanitize_textarea_field( mb_substr( $raw, 0, self::MAX_TEXT ) );
				break;
			case 'number':
			case 'range':
				if ( ! is_numeric( $raw ) ) {
					return null;
				}
				$n = (float) $raw;
				/* never trust a number the browser sent: no INF / NAN and nothing absurdly large */
				if ( ! is_finite( $n ) || abs( $n ) > self::MAX_NUMBER ) {
					return null;
				}
				if ( isset( $f['min'] ) && '' !== $f['min'] && $n < (float) $f['min'] ) {
					return null;
				}
				if ( isset( $f['max'] ) && '' !== $f['max'] && $n > (float) $f['max'] ) {
					return null;
				}
				if ( isset( $f['price_type'] ) && 'value' === $f['price_type'] ) {
					/* the number sets the price: it can never be negative and it must sit on the step the field offers */
					$lo   = isset( $f['min'] ) && '' !== $f['min'] ? (float) $f['min'] : 0.0;
					$step = isset( $f['step'] ) && (float) $f['step'] > 0 ? (float) $f['step'] : 1.0;
					if ( $n < 0 ) {
						return null;
					}
					$steps = ( $n - $lo ) / $step;
					if ( abs( $steps - round( $steps ) ) > 1e-6 ) {
						return null;
					}
				}
				return array( (string) ( 0 + $n ) );
			case 'color':
				$v = sanitize_hex_color( $raw );
				return $v ? array( $v ) : null;
			case 'date':
				if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $raw, $m ) || ! checkdate( (int) $m[2], (int) $m[3], (int) $m[1] ) ) {
					return null;
				}
				return array( $raw );
			case 'time':
				return preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', $raw ) ? array( $raw ) : null;
			case 'upload':
				$list = array();
				foreach ( array_slice( array_filter( explode( ',', $raw ) ), 0, 1 ) as $token ) {
					if ( self::verify_token( $token ) ) {
						$list[] = $token;
					} else {
						return null;
					}
				}
				return $list;
			default:
				return array();
		}
		if ( ! empty( $f['maxlength'] ) ) {
			$v = mb_substr( $v, 0, (int) $f['maxlength'] );
		}
		return '' === $v ? array() : array( $v );
	}

	/**
	 * Validate and price what was posted for a product.
	 *
	 * @return array { lines: [], fixed: float, percent: float, errors: string[] }
	 */
	public static function collect( $product_id, $posted, $quantity = 1, $variation_id = 0 ) {
		$fields = self::fields_for( $product_id );
		$result = array( 'lines' => array(), 'fixed' => 0.0, 'percent' => 0.0, 'errors' => array() );
		if ( ! $fields ) {
			return $result;
		}
		$map    = array();
		$values = array();
		$bad    = array();
		foreach ( $fields as $f ) {
			$map[ $f['id'] ] = $f;
			if ( in_array( $f['type'], self::layout_types(), true ) ) {
				continue;
			}
			$raw = isset( $posted[ $f['id'] ] ) ? $posted[ $f['id'] ] : null;
			$val = null === $raw ? array() : self::normalise( $f, $raw );
			if ( null === $val ) {
				$bad[ $f['id'] ] = true;
				$val             = array();
			}
			$values[ $f['id'] ] = $val;
		}
		$values            = array_merge( $values, self::context_values( $product_id, $quantity, $variation_id ) );
		$state = array();
		foreach ( $fields as $f ) {
			if ( in_array( $f['type'], self::layout_types(), true ) ) {
				continue;
			}
			if ( ! self::is_visible( $f, $map, $values, $state ) ) {
				continue;
			}
			$id    = $f['id'];
			$name  = '' !== $f['label'] ? $f['label'] : self::types()[ $f['type'] ][0];
			$val   = $values[ $id ];
			if ( isset( $bad[ $id ] ) ) {
				/* translators: %s: field name */
				$result['errors'][] = sprintf( __( 'The value of "%s" is not valid.', 'nexvio' ), $name );
				continue;
			}
			if ( ! $val ) {
				if ( ! empty( $f['required'] ) ) {
					$result['errors'][] = sprintf( Nexvio_Epo_Settings::text( 't_required' ), $name );
				}
				continue;
			}
			$line = self::line( $f, $val, $name );
			$result['lines'][] = $line;
			$result['fixed']   += $line['fixed'];
			$result['percent'] += $line['percent'];
		}
		return $result;
	}

	/** One cart / order line for a filled field. */
	private static function line( $f, $val, $name ) {
		$fixed   = 0.0;
		$percent = 0.0;
		$labels  = array();
		$type    = $f['type'];
		$html    = '';
		if ( in_array( $type, self::choice_types(), true ) ) {
			$by = array();
			foreach ( $f['choices'] as $c ) {
				$by[ $c['id'] ] = $c;
			}
			foreach ( $val as $cid ) {
				$c        = $by[ $cid ];
				$labels[] = $c['label'];
				if ( 'percent' === $c['price_type'] ) {
					$percent += self::choice_price( $c );
				} else {
					$fixed += self::choice_price( $c );
				}
			}
			$text = implode( ', ', $labels );
		} else {
			$v    = $val[0];
			$text = $v;
			$p    = isset( $f['price'] ) ? (float) $f['price'] : 0.0;
			switch ( isset( $f['price_type'] ) ? $f['price_type'] : 'none' ) {
				case 'fixed':
					$fixed = $p;
					break;
				case 'percent':
					$percent = $p;
					break;
				case 'char':
					$fixed = $p * mb_strlen( preg_replace( '/\s+/u', '', $v ) );
					break;
				case 'value':
					$fixed = $p * (float) $v;
					break;
			}
			if ( 'upload' === $type ) {
				$file = self::token_file( $v );
				$text = $file ? $file['name'] : $v;
				$html = $file ? '<a href="' . esc_url( $file['url'] ) . '" target="_blank" rel="noopener">' . esc_html( $file['name'] ) . '</a>' : '';
			}
		}
		return array(
			'id'      => $f['id'],
			'type'    => $type,
			'label'   => $name,
			'text'    => $text,
			'html'    => $html,
			'fixed'   => round( $fixed, 6 ),
			'percent' => round( $percent, 6 ),
			'url'     => 'upload' === $type && isset( $file ) && $file ? $file['url'] : '',
		);
	}

	/* ------------------------------------------------------------------
	 * WooCommerce: validation, cart, totals, order
	 * ------------------------------------------------------------------ */

	public static function validate_add( $passed, $product_id = 0, $quantity = 1, $variation_id = 0, $variations = null ) {
		if ( ! $passed || ! $product_id || ! self::fields_for( $product_id ) ) {
			return $passed;
		}
		$res = self::collect( $product_id, self::posted(), $quantity, $variation_id );
		if ( $res['errors'] ) {
			foreach ( $res['errors'] as $msg ) {
				wc_add_notice( esc_html( $msg ), 'error' );
			}
			return false;
		}
		return $passed;
	}

	public static function cart_item_data( $data, $product_id, $variation_id = 0, $quantity = 1 ) {
		if ( ! self::fields_for( $product_id ) ) {
			return $data;
		}
		$res = self::collect( $product_id, self::posted(), $quantity, $variation_id );
		if ( $res['lines'] ) {
			$data['nexvio_epo'] = array(
				'lines'   => $res['lines'],
				'fixed'   => $res['fixed'],
				'percent' => $res['percent'],
			);
		}
		return $data;
	}

	/** Add the options to the price of each cart line that has them. */
	public static function apply_prices( $cart ) {
		if ( ( is_admin() && ! wp_doing_ajax() ) || ! $cart || ! is_object( $cart ) ) {
			return;
		}
		foreach ( $cart->get_cart() as $key => $item ) {
			if ( empty( $item['nexvio_epo'] ) || empty( $item['data'] ) || ! is_object( $item['data'] ) ) {
				continue;
			}
			if ( ! isset( self::$base[ $key ] ) ) {
				self::$base[ $key ] = (float) $item['data']->get_price( 'edit' );
			}
			$base    = self::$base[ $key ];
			$fixed   = isset( $item['nexvio_epo']['fixed'] ) ? (float) $item['nexvio_epo']['fixed'] : 0.0;
			$percent = isset( $item['nexvio_epo']['percent'] ) ? (float) $item['nexvio_epo']['percent'] : 0.0;
			if ( ! is_finite( $fixed ) || ! is_finite( $percent ) ) {
				continue;
			}
			$extra = $fixed + $base * $percent / 100;
			$item['data']->set_price( round( max( 0, $base + $extra ), wc_get_rounding_precision() ) );
		}
	}

	/** Option price text for one line, e.g. "+€5.00". */
	private static function price_text( $line, $base ) {
		$amount = (float) $line['fixed'];
		if ( (float) $line['percent'] && null !== $base ) {
			$amount += $base * (float) $line['percent'] / 100;
		}
		if ( ! $amount && (float) $line['percent'] && null === $base ) {
			return ( $line['percent'] > 0 ? '+' : '' ) . wc_format_decimal( $line['percent'], 2, true ) . '%';
		}
		if ( ! $amount ) {
			return '';
		}
		return ( $amount > 0 ? '+' : '−' ) . html_entity_decode( wp_strip_all_tags( wc_price( abs( $amount ) ) ), ENT_QUOTES, 'UTF-8' );
	}

	public static function item_data( $data, $cart_item ) {
		if ( empty( $cart_item['nexvio_epo']['lines'] ) ) {
			return $data;
		}
		$base = isset( $cart_item['key'] ) && isset( self::$base[ $cart_item['key'] ] ) ? self::$base[ $cart_item['key'] ] : null;
		foreach ( $cart_item['nexvio_epo']['lines'] as $line ) {
			$price = Nexvio_Epo_Settings::on( 'cart_prices' ) ? self::price_text( $line, $base ) : '';
			$value = $line['text'] . ( '' !== $price ? ' (' . $price . ')' : '' );
			$entry = array( 'key' => $line['label'], 'value' => $value );
			if ( ! empty( $line['html'] ) ) {
				$entry['display'] = $line['html'] . ( '' !== $price ? ' (' . esc_html( $price ) . ')' : '' );
			}
			$data[] = $entry;
		}
		return $data;
	}

	public static function order_item( $item, $cart_item_key, $values, $order ) {
		if ( empty( $values['nexvio_epo']['lines'] ) ) {
			return;
		}
		$base = isset( self::$base[ $cart_item_key ] ) ? self::$base[ $cart_item_key ] : null;
		foreach ( $values['nexvio_epo']['lines'] as $line ) {
			$price = self::price_text( $line, $base );
			$text  = $line['text'] . ( 'upload' === $line['type'] && ! empty( $line['url'] ) ? ' - ' . $line['url'] : '' ) . ( '' !== $price ? ' (' . $price . ')' : '' );
			$item->add_meta_data( $line['label'], $text );
		}
	}

	/* ------------------------------------------------------------------
	 * Shop lists
	 * ------------------------------------------------------------------ */

	private static function loop_needs_page( $product ) {
		return Nexvio_Epo_Settings::on( 'loop_button' ) && $product instanceof WC_Product && $product->is_type( 'simple' ) && self::has_required( $product->get_id() );
	}

	public static function loop_url( $url, $product ) {
		return self::loop_needs_page( $product ) ? get_permalink( $product->get_id() ) : $url;
	}

	public static function loop_text( $text, $product ) {
		return self::loop_needs_page( $product ) ? Nexvio_Epo_Settings::text( 't_select' ) : $text;
	}

	public static function loop_supports( $supports, $feature, $product ) {
		return 'ajax_add_to_cart' === $feature && self::loop_needs_page( $product ) ? false : $supports;
	}

	/* ------------------------------------------------------------------
	 * Automatic display
	 * ------------------------------------------------------------------ */

	public static function auto_display() {
		if ( ! Nexvio_Epo_Settings::on( 'auto_display' ) || ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}
		$hooks = array(
			'before_button' => 'woocommerce_before_add_to_cart_button',
			'after_button'  => 'woocommerce_after_add_to_cart_button',
			'before_form'   => 'woocommerce_before_add_to_cart_form',
		);
		$pos = Nexvio_Epo_Settings::get( 'position' );
		add_action( isset( $hooks[ $pos ] ) ? $hooks[ $pos ] : $hooks['before_button'], array( __CLASS__, 'print_auto' ), 5 );
	}

	public static function print_auto() {
		global $product;
		if ( ! $product instanceof WC_Product ) {
			return;
		}
		static $done = array();
		if ( isset( $done[ $product->get_id() ] ) ) {
			return;
		}
		$done[ $product->get_id() ] = true;
		echo wp_kses( Nexvio_Epo_Render::html( $product, array( 'auto' => true ) ), Nexvio_Epo_Render::allowed() );
	}

	/* ------------------------------------------------------------------
	 * File uploads
	 * ------------------------------------------------------------------ */

	private static function upload_mimes() {
		$all = array(
			'jpg'  => array( 'jpg|jpeg|jpe' => 'image/jpeg' ),
			'png'  => array( 'png' => 'image/png' ),
			'gif'  => array( 'gif' => 'image/gif' ),
			'webp' => array( 'webp' => 'image/webp' ),
			'pdf'  => array( 'pdf' => 'application/pdf' ),
			'doc'  => array( 'doc' => 'application/msword', 'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' ),
			'txt'  => array( 'txt' => 'text/plain' ),
			'zip'  => array( 'zip' => 'application/zip' ),
		);
		$mimes = array();
		foreach ( (array) Nexvio_Epo_Settings::get( 'upload_types' ) as $t ) {
			if ( isset( $all[ $t ] ) ) {
				$mimes = array_merge( $mimes, $all[ $t ] );
			}
		}
		return $mimes;
	}

	/** At most 20 uploads per visitor (by address) in 10 minutes. */
	private static function upload_limited() {
		$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$key = 'nexvio_epo_up_' . md5( $ip );
		$n   = (int) get_transient( $key );
		if ( $n >= 20 ) {
			return true;
		}
		set_transient( $key, $n + 1, 10 * MINUTE_IN_SECONDS );
		return false;
	}

	/** The upload folder never lists its files and never runs a script, whatever a server allows. */
	private static function protect_upload_dir() {
		$up   = wp_upload_dir();
		$root = $up['basedir'] . '/nexvio-epo';
		if ( ! is_dir( $root ) ) {
			return;
		}
		if ( ! file_exists( $root . '/index.php' ) ) {
			file_put_contents( $root . '/index.php', "<?php\n// Silence is golden.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- two tiny protection files in our own upload folder.
		}
		if ( ! file_exists( $root . '/.htaccess' ) ) {
			$rules  = "# Nexvio: customer files are never executed.\n";
			$rules .= "<FilesMatch \"\\.(php|php[0-9]|phtml|phar|pl|py|cgi|sh|shtml|htm|html|svg)$\">\n";
			$rules .= "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n";
			$rules .= "<IfModule !mod_authz_core.c>\nOrder allow,deny\nDeny from all\n</IfModule>\n";
			$rules .= "</FilesMatch>\n";
			file_put_contents( $root . '/.htaccess', $rules ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- see above.
		}
	}

	public static function upload_dir_filter( $dirs ) {
		$dirs['subdir'] = '/nexvio-epo' . $dirs['subdir'];
		$dirs['path']   = $dirs['basedir'] . $dirs['subdir'];
		$dirs['url']    = $dirs['baseurl'] . $dirs['subdir'];
		return $dirs;
	}

	private static function token_hash( $rel ) {
		return substr( wp_hash( 'nexvio_epo|' . $rel ), 0, 24 );
	}

	/** Absolute file + url + name for a token, or null when the token is not ours. */
	private static function token_file( $token ) {
		$token = (string) $token;
		$pos   = strrpos( $token, '|' );
		if ( false === $pos ) {
			return null;
		}
		$rel  = substr( $token, 0, $pos );
		$hash = substr( $token, $pos + 1 );
		if ( 0 !== strpos( $rel, 'nexvio-epo/' ) || false !== strpos( $rel, '..' ) || ! hash_equals( self::token_hash( $rel ), $hash ) ) {
			return null;
		}
		$up   = wp_upload_dir();
		$path = $up['basedir'] . '/' . $rel;
		if ( ! is_file( $path ) ) {
			return null;
		}
		$real = realpath( $path );
		$root = realpath( $up['basedir'] . '/nexvio-epo' );
		if ( ! $real || ! $root || 0 !== strpos( wp_normalize_path( $real ), trailingslashit( wp_normalize_path( $root ) ) ) ) {
			return null;
		}
		$name = preg_replace( '/^[a-f0-9]{6,16}-/', '', basename( $rel ) );
		return array( 'path' => $path, 'url' => $up['baseurl'] . '/' . $rel, 'name' => $name );
	}

	private static function verify_token( $token ) {
		return (bool) self::token_file( $token );
	}

	public static function ajax_upload() {
		if ( ! check_ajax_referer( 'nexvio_epo', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Please reload the page and try again.', 'nexvio' ) ), 403 );
		}
		/* a visitor may send only a few files in a short time (a guest nonce is the same for every guest) */
		if ( self::upload_limited() ) {
			wp_send_json_error( array( 'message' => __( 'Too many uploads. Please wait a few minutes and try again.', 'nexvio' ) ), 429 );
		}
		$product_id = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;
		$field_id   = isset( $_POST['field'] ) ? sanitize_key( wp_unslash( $_POST['field'] ) ) : '';
		$ok         = false;
		if ( ! $product_id || 'product' !== get_post_type( $product_id ) || ( 'publish' !== get_post_status( $product_id ) && ! current_user_can( 'edit_post', $product_id ) ) ) {
			wp_send_json_error( array( 'message' => Nexvio_Epo_Settings::text( 't_upl_error' ) ), 400 );
		}
		foreach ( self::fields_for( $product_id ) as $f ) {
			if ( $f['id'] === $field_id && 'upload' === $f['type'] ) {
				$ok = true;
			}
		}
		if ( ! $ok || empty( $_FILES['file'] ) || ! is_array( $_FILES['file'] ) ) {
			wp_send_json_error( array( 'message' => Nexvio_Epo_Settings::text( 't_upl_error' ) ), 400 );
		}
		$max = max( 1, (int) Nexvio_Epo_Settings::get( 'upload_max' ) ) * MB_IN_BYTES;
		// Each part of the upload is cleaned here; wp_handle_upload() then checks the file against the allowed types.
		$file = array(
			'name'     => isset( $_FILES['file']['name'] ) && is_string( $_FILES['file']['name'] ) ? sanitize_file_name( wp_unslash( $_FILES['file']['name'] ) ) : '',
			'type'     => isset( $_FILES['file']['type'] ) && is_string( $_FILES['file']['type'] ) ? sanitize_mime_type( wp_unslash( $_FILES['file']['type'] ) ) : '',
			/* PHP's own temporary path: WordPress does not slash $_FILES, so it is not unslashed here (a Windows path would break) */
			'tmp_name' => isset( $_FILES['file']['tmp_name'] ) && is_string( $_FILES['file']['tmp_name'] ) ? sanitize_text_field( $_FILES['file']['tmp_name'] ) : '',
			'error'    => isset( $_FILES['file']['error'] ) ? absint( $_FILES['file']['error'] ) : 0,
			'size'     => isset( $_FILES['file']['size'] ) ? absint( $_FILES['file']['size'] ) : 0,
		);
		if ( '' === $file['name'] || '' === $file['tmp_name'] ) {
			wp_send_json_error( array( 'message' => Nexvio_Epo_Settings::text( 't_upl_error' ) ), 400 );
		}
		if ( ! empty( $file['error'] ) || (int) $file['size'] > $max || (int) $file['size'] <= 0 ) {
			wp_send_json_error( array( 'message' => sprintf(
				/* translators: %d: size in MB */
				__( 'The file is too large (largest: %d MB).', 'nexvio' ),
				(int) Nexvio_Epo_Settings::get( 'upload_max' )
			) ), 400 );
		}
		$mimes = self::upload_mimes();
		if ( ! $mimes ) {
			wp_send_json_error( array( 'message' => Nexvio_Epo_Settings::text( 't_upl_error' ) ), 400 );
		}
		require_once ABSPATH . 'wp-admin/includes/file.php';
		/* a long random prefix: the address of a customer's file cannot be guessed */
		$file['name'] = bin2hex( random_bytes( 8 ) ) . '-' . sanitize_file_name( $file['name'] );
		add_filter( 'upload_dir', array( __CLASS__, 'upload_dir_filter' ) );
		$moved = wp_handle_upload( $file, array( 'test_form' => false, 'mimes' => $mimes ) );
		remove_filter( 'upload_dir', array( __CLASS__, 'upload_dir_filter' ) );
		self::protect_upload_dir();
		if ( ! is_array( $moved ) || ! empty( $moved['error'] ) || empty( $moved['file'] ) ) {
			wp_send_json_error( array( 'message' => ! empty( $moved['error'] ) ? wp_strip_all_tags( $moved['error'] ) : Nexvio_Epo_Settings::text( 't_upl_error' ) ), 400 );
		}
		$up  = wp_upload_dir();
		$rel = ltrim( str_replace( wp_normalize_path( $up['basedir'] ), '', wp_normalize_path( $moved['file'] ) ), '/' );
		wp_send_json_success( array(
			'token' => $rel . '|' . self::token_hash( $rel ),
			'name'  => preg_replace( '/^[a-f0-9]{6,16}-/', '', basename( $rel ) ),
		) );
	}

	/* ------------------------------------------------------------------
	 * Product screen: choose forms for one product
	 * ------------------------------------------------------------------ */

	public static function add_box() {
		add_meta_box( 'nexvio_epo_box', __( 'Nexvio Extra Options', 'nexvio' ), array( __CLASS__, 'box' ), 'product', 'side', 'default' );
	}

	public static function box( $post ) {
		$forms    = self::all_forms();
		$disabled = 'yes' === get_post_meta( $post->ID, '_nexvio_epo_disable', true );
		$chosen   = array_map( 'absint', (array) get_post_meta( $post->ID, '_nexvio_epo_forms', true ) );
		wp_nonce_field( 'nexvio_epo_box', 'nexvio_epo_box_nonce' );
		?>
		<p><label><input type="checkbox" name="nexvio_epo_disable" value="yes"<?php checked( $disabled ); ?>> <?php esc_html_e( 'Do not use the forms that are assigned to categories or all products', 'nexvio' ); ?></label></p>
		<?php if ( $forms ) : ?>
			<p><strong><?php esc_html_e( 'Always add these forms', 'nexvio' ); ?></strong></p>
			<?php foreach ( $forms as $form ) : ?>
				<p style="margin:2px 0"><label><input type="checkbox" name="nexvio_epo_forms[]" value="<?php echo esc_attr( $form['id'] ); ?>"<?php checked( in_array( $form['id'], $chosen, true ) ); ?>> <?php echo esc_html( $form['title'] ? $form['title'] : '#' . $form['id'] ); ?><?php echo 'draft' === $form['status'] ? ' <em>(' . esc_html__( 'draft', 'nexvio' ) . ')</em>' : ''; ?></label></p>
			<?php endforeach; ?>
		<?php endif; ?>
		<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=nexvio-epo' ) ); ?>"><?php esc_html_e( 'Manage extra option forms', 'nexvio' ); ?></a></p>
		<?php
	}

	public static function save_box( $post_id, $post ) {
		if ( ! isset( $_POST['nexvio_epo_box_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nexvio_epo_box_nonce'] ) ), 'nexvio_epo_box' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		update_post_meta( $post_id, '_nexvio_epo_disable', ! empty( $_POST['nexvio_epo_disable'] ) ? 'yes' : 'no' );
		$ids = isset( $_POST['nexvio_epo_forms'] ) && is_array( $_POST['nexvio_epo_forms'] ) ? array_values( array_filter( array_map( 'absint', wp_unslash( $_POST['nexvio_epo_forms'] ) ) ) ) : array();
		update_post_meta( $post_id, '_nexvio_epo_forms', $ids );
	}

	/* ------------------------------------------------------------------
	 * Data for the front-end script
	 * ------------------------------------------------------------------ */

	public static function currency_config() {
		return array(
			'symbol' => html_entity_decode( get_woocommerce_currency_symbol() ),
			'pos'    => get_option( 'woocommerce_currency_pos', 'left' ),
			'dec'    => wc_get_price_decimals(),
			'dsep'   => wc_get_price_decimal_separator(),
			'tsep'   => wc_get_price_thousand_separator(),
		);
	}
}
