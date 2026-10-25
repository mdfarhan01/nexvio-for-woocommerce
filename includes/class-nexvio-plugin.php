<?php
/**
 * Nexvio - core.
 *
 * @package Nexvio_For_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Nexvio_Plugin {

	const CATEGORY = 'nexvio';

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/* ------------------------------------------------------------------
	 * Widgets registry
	 * ------------------------------------------------------------------ */

	/**
	 * All widgets of the plugin.
	 * key => class, file, title, description, dashicon, group
	 */
	public static function widgets() {
		return array(
			'product_slider' => array(
				'class' => 'Nexvio_Product_Slider_Widget',
				'file'  => 'class-nexvio-product-slider-widget.php',
				'title' => __( 'Product Slider', 'nexvio' ),
				'desc'  => __( 'Carousel of WooCommerce products with the card design, e.g. singles or new arrivals.', 'nexvio' ),
				'icon'  => 'dashicons-images-alt2',
				'group' => __( 'Products', 'nexvio' ),
			),
			'products_grid' => array(
				'class' => 'Nexvio_Products_Grid_Widget',
				'file'  => 'class-nexvio-products-grid-widget.php',
				'title' => __( 'Products Grid', 'nexvio' ),
				'desc'  => __( 'A grid of chosen products for any page (featured, on sale, categories…), with pagination and 11 designs.', 'nexvio' ),
				'icon'  => 'dashicons-screenoptions',
				'group' => __( 'Products', 'nexvio' ),
			),
			'product_grid' => array(
				'class' => 'Nexvio_Product_Grid_Widget',
				'file'  => 'class-nexvio-product-grid-widget.php',
				'title' => __( 'Products Archive', 'nexvio' ),
				'desc'  => __( 'Shop / category page grid with pagination, result count and sorting. Works with the filter. 11 designs.', 'nexvio' ),
				'icon'  => 'dashicons-grid-view',
				'group' => __( 'Shop', 'nexvio' ),
			),
			'product_related' => array(
				'class' => 'Nexvio_Product_Related_Widget',
				'file'  => 'class-nexvio-product-related-widget.php',
				'title' => __( 'Related Products', 'nexvio' ),
				'desc'  => __( 'On the product page: other products of the same category, most popular first.', 'nexvio' ),
				'icon'  => 'dashicons-randomize',
				'group' => __( 'Single product', 'nexvio' ),
			),
			'add_to_cart' => array(
				'class' => 'Nexvio_Add_To_Cart_Widget',
				'file'  => 'class-nexvio-add-to-cart-widget.php',
				'title' => __( 'Add to Cart', 'nexvio' ),
				'desc'  => __( 'Quantity selector, "Add to cart" button (AJAX) and wishlist heart.', 'nexvio' ),
				'icon'  => 'dashicons-cart',
				'group' => __( 'Single product', 'nexvio' ),
			),
			'product_filter' => array(
				'class' => 'Nexvio_Product_Filter_Widget',
				'file'  => 'class-nexvio-product-filter-widget.php',
				'title' => __( 'Product Filter', 'nexvio' ),
				'desc'  => __( 'AJAX filter: search, price range, categories and any attribute, tag or stock filter.', 'nexvio' ),
				'icon'  => 'dashicons-filter',
				'group' => __( 'Shop', 'nexvio' ),
			),
			'filter_drawer' => array(
				'class' => 'Nexvio_Filter_Drawer_Widget',
				'file'  => 'class-nexvio-filter-drawer-widget.php',
				'title' => __( 'Filter Popup (side panel)', 'nexvio' ),
				'desc'  => __( 'A Filters button that slides the filter in from the side. Apply closes it and shows the products.', 'nexvio' ),
				'icon'  => 'dashicons-filter',
				'group' => __( 'Shop', 'nexvio' ),
			),
			'header_search' => array(
				'class' => 'Nexvio_Header_Search_Widget',
				'file'  => 'class-nexvio-header-search-widget.php',
				'title' => __( 'Header Search', 'nexvio' ),
				'desc'  => __( 'Search icon for the header. Opens a search bar that filters products by title.', 'nexvio' ),
				'icon'  => 'dashicons-search',
				'group' => __( 'Header', 'nexvio' ),
			),
			'header_cart' => array(
				'class' => 'Nexvio_Header_Cart_Widget',
				'file'  => 'class-nexvio-header-cart-widget.php',
				'title' => __( 'Header Cart', 'nexvio' ),
				'desc'  => __( 'Cart icon with a live counter for the header. Opens the side cart.', 'nexvio' ),
				'icon'  => 'dashicons-cart',
				'group' => __( 'Cart', 'nexvio' ),
			),
			'floating_cart' => array(
				'class' => 'Nexvio_Floating_Cart_Widget',
				'file'  => 'class-nexvio-floating-cart-widget.php',
				'title' => __( 'Floating Cart', 'nexvio' ),
				'desc'  => __( 'A cart button fixed in a bottom corner, with a live counter. Opens the side cart.', 'nexvio' ),
				'icon'  => 'dashicons-sticky',
				'group' => __( 'Cart', 'nexvio' ),
			),
			'wishlist_icon' => array(
				'class' => 'Nexvio_Wishlist_Icon_Widget',
				'file'  => 'class-nexvio-wishlist-icon-widget.php',
				'title' => __( 'Wishlist Icon (header)', 'nexvio' ),
				'desc'  => __( 'Heart with a live counter, links to the wishlist page.', 'nexvio' ),
				'icon'  => 'dashicons-heart',
				'group' => __( 'Header', 'nexvio' ),
			),
			'wishlist_grid' => array(
				'class' => 'Nexvio_Wishlist_Grid_Widget',
				'file'  => 'class-nexvio-wishlist-grid-widget.php',
				'title' => __( 'Wishlist Grid (My favorites)', 'nexvio' ),
				'desc'  => __( 'The visitor\'s wishlist products, with the same card design. Hearts remove cards live.', 'nexvio' ),
				'icon'  => 'dashicons-star-filled',
				'group' => __( 'Wishlist', 'nexvio' ),
			),
		);
	}

	/**
	 * Asset version = plugin version + file time, so browsers and cache plugins
	 * (LiteSpeed, WP Rocket…) always load the newest file after an update.
	 */
	public static function ver( $relative ) {
		$file = NEXVIO_PATH . ltrim( $relative, '/' );
		return file_exists( $file ) ? NEXVIO_VERSION . '.' . filemtime( $file ) : NEXVIO_VERSION;
	}

	public static function disabled() {
		$d = get_option( 'nexvio_disabled_widgets', array() );
		return is_array( $d ) ? $d : array();
	}

	public static function is_enabled( $key ) {
		return ! in_array( $key, self::disabled(), true );
	}

	/* ------------------------------------------------------------------
	 * Output escaping
	 * ------------------------------------------------------------------ */

	/**
	 * HTML the plugin is allowed to print: the post-content set plus the inline SVG
	 * icons, form controls and data-* / aria-* attributes its own markup uses.
	 * Use it as the second argument of wp_kses() when printing built markup.
	 *
	 * @return array
	 */
	public static function allowed_html() {
		static $allowed = null;
		if ( null !== $allowed ) {
			return $allowed;
		}
		$global = array(
			'id'       => true,
			'class'    => true,
			'style'    => true,
			'title'    => true,
			'role'     => true,
			'hidden'   => true,
			'inert'    => true,
			'tabindex' => true,
			'lang'     => true,
			'dir'      => true,
			'data-*'   => true,
		);
		/* kses has no aria-* wildcard (only data-*), so each attribute the markup uses is listed. */
		foreach ( array( 'hidden', 'label', 'labelledby', 'describedby', 'controls', 'expanded', 'pressed', 'selected', 'checked', 'current', 'haspopup', 'modal', 'live', 'atomic', 'busy', 'disabled', 'invalid', 'required', 'owns', 'orientation', 'roledescription', 'valuenow', 'valuemin', 'valuemax', 'valuetext', 'multiline', 'readonly', 'sort', 'level', 'posinset', 'setsize', 'relevant', 'activedescendant', 'autocomplete' ) as $aria ) {
			$global[ 'aria-' . $aria ] = true;
		}
		$svg    = array_merge( $global, array(
			'xmlns'             => true,
			'viewbox'           => true,
			'width'             => true,
			'height'            => true,
			'fill'              => true,
			'stroke'            => true,
			'stroke-width'      => true,
			'stroke-linecap'    => true,
			'stroke-linejoin'   => true,
			'stroke-opacity'    => true,
			'fill-opacity'      => true,
			'fill-rule'         => true,
			'clip-rule'         => true,
			'focusable'         => true,
			'd'                 => true,
			'cx'                => true,
			'cy'                => true,
			'r'                 => true,
			'rx'                => true,
			'ry'                => true,
			'x'                 => true,
			'y'                 => true,
			'x1'                => true,
			'y1'                => true,
			'x2'                => true,
			'y2'                => true,
			'points'            => true,
			'transform'         => true,
			'offset'            => true,
			'stop-color'        => true,
			'stop-opacity'      => true,
			'opacity'           => true,
		) );
		$tags   = wp_kses_allowed_html( 'post' );
		foreach ( $tags as $tag => $attrs ) {
			$tags[ $tag ] = array_merge( is_array( $attrs ) ? $attrs : array(), $global );
		}
		$extra = array(
			'svg'            => $svg,
			'g'              => $svg,
			'path'           => $svg,
			'circle'         => $svg,
			'ellipse'        => $svg,
			'rect'           => $svg,
			'line'           => $svg,
			'polyline'       => $svg,
			'polygon'        => $svg,
			'defs'           => $svg,
			'lineargradient' => array_merge( $svg, array( 'gradientunits' => true, 'gradienttransform' => true ) ),
			'stop'           => $svg,
			'details'        => array_merge( $global, array( 'open' => true ) ),
			'summary'        => $global,
			'button'         => array_merge( $global, array( 'type' => true, 'name' => true, 'value' => true, 'disabled' => true ) ),
			'input'          => array_merge( $global, array(
				'type'        => true,
				'name'        => true,
				'value'       => true,
				'checked'     => true,
				'disabled'    => true,
				'readonly'    => true,
				'placeholder' => true,
				'min'         => true,
				'max'         => true,
				'step'        => true,
				'size'        => true,
				'maxlength'   => true,
				'inputmode'   => true,
				'pattern'     => true,
				'autocomplete' => true,
				'required'    => true,
			) ),
			'select'         => array_merge( $global, array( 'name' => true, 'disabled' => true, 'multiple' => true ) ),
			'option'         => array_merge( $global, array( 'value' => true, 'selected' => true, 'disabled' => true ) ),
			'label'          => array_merge( $global, array( 'for' => true ) ),
			'form'           => array_merge( $global, array( 'action' => true, 'method' => true, 'enctype' => true, 'novalidate' => true ) ),
			'a'              => array_merge( $global, array( 'href' => true, 'target' => true, 'rel' => true, 'download' => true ) ),
			'img'            => array_merge( $global, array(
				'src'      => true,
				'srcset'   => true,
				'sizes'    => true,
				'alt'      => true,
				'width'    => true,
				'height'   => true,
				'loading'  => true,
				'decoding' => true,
			) ),
			'del'            => $global,
			'ins'            => $global,
			'bdi'            => $global,
			'nav'            => $global,
			'aside'          => $global,
			'section'        => $global,
		);
		$allowed = array_merge( $tags, $extra );
		return $allowed;
	}

	/** Extra inline-CSS properties the plugin markup relies on (kses would drop them). */
	public static function safe_style_css( $props ) {
		return array_merge( (array) $props, array( 'justify-content', 'display', 'align-items', 'gap', 'flex-direction' ) );
	}

	/** Lets kses keep the plugin's own CSS custom properties (--sw, --nexvio-rating ...). */
	public static function allow_css_var( $allow, $css ) {
		if ( $allow ) {
			return $allow;
		}
		$css = trim( (string) $css );
		if ( preg_match( '/^--(?:nexvio-[a-z0-9-]+|sw|r|lo|hi|c)\s*:\s*(?!.*url)[#a-z0-9.,%()\s-]+$/i', $css ) ) {
			return true;
		}
		/* swatch picture: an http(s) image address only */
		if ( preg_match( '#^--sw-img\s*:\s*url\(\s*https?://[^\s\'"()<>\\\\]+\s*\)$#i', $css ) ) {
			return true;
		}
		return $allow;
	}

	/* ------------------------------------------------------------------
	 * Boot
	 * ------------------------------------------------------------------ */

	public function boot() {
		add_filter( 'safe_style_css', array( __CLASS__, 'safe_style_css' ) );
		add_filter( 'safecss_filter_attr_allow_css', array( __CLASS__, 'allow_css_var' ), 10, 2 );
		require_once NEXVIO_PATH . 'includes/class-nexvio-wishlist.php';
		require_once NEXVIO_PATH . 'includes/class-nexvio-filter-query.php';
		require_once NEXVIO_PATH . 'includes/class-nexvio-layouts.php';
		require_once NEXVIO_PATH . 'includes/class-nexvio-swatches.php';
		require_once NEXVIO_PATH . 'includes/class-nexvio-texts.php';
		require_once NEXVIO_PATH . 'includes/class-nexvio-filter-config.php';
		require_once NEXVIO_PATH . 'includes/class-nexvio-cart-settings.php';
		require_once NEXVIO_PATH . 'includes/class-nexvio-side-cart.php';
		require_once NEXVIO_PATH . 'admin/class-nexvio-admin.php';

		Nexvio_Admin::init();

		$missing = $this->missing_requirements();
		if ( $missing ) {
			add_action( 'admin_notices', function () use ( $missing ) {
				global $pagenow;
				if ( 'plugins.php' !== $pagenow || ! current_user_can( 'activate_plugins' ) ) {
					return;
				}
				echo '<div class="notice notice-warning"><p><strong>Nexvio</strong>: ' . esc_html( sprintf(
					/* translators: %s: plugin names */
					__( 'please install and activate %s.', 'nexvio' ),
					implode( ' + ', $missing )
				) ) . '</p></div>';
			} );
			return;
		}

		Nexvio_Wishlist::init();
		Nexvio_Filter_Query::init();
		add_action( 'admin_init', array( 'Nexvio_Swatches', 'init_admin' ) );
		add_action( 'woocommerce_init', array( 'Nexvio_Side_Cart', 'init' ) );

		/* Elementor */
		add_action( 'elementor/elements/categories_registered', array( $this, 'register_category' ) );
		add_action( 'elementor/controls/register', array( $this, 'register_controls' ) );
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
		add_action( 'elementor/frontend/after_register_styles', array( $this, 'register_assets' ) );
		add_action( 'elementor/frontend/after_register_scripts', array( $this, 'register_assets' ) );
		add_action( 'elementor/preview/enqueue_styles', array( $this, 'register_assets' ) );
		add_action( 'elementor/editor/after_enqueue_styles', array( $this, 'editor_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );

		/* WooCommerce */
		add_filter( 'loop_shop_per_page', array( $this, 'per_page' ), 99 );
		add_action( 'wp', array( $this, 'maybe_replace_related' ) );
		add_shortcode( 'nexvio_related_products', array( $this, 'related_shortcode' ) );
	}

	public function missing_requirements() {
		$missing = array();
		if ( ! did_action( 'elementor/loaded' ) ) {
			$missing[] = 'Elementor';
		}
		if ( ! class_exists( 'WooCommerce' ) ) {
			$missing[] = 'WooCommerce';
		}
		return $missing;
	}

	/* ------------------------------------------------------------------
	 * Elementor category, shown first in the panel
	 * ------------------------------------------------------------------ */

	public function register_category( $manager ) {
		$manager->add_category( self::CATEGORY, array(
			'title' => __( 'Nexvio', 'nexvio' ),
			'icon'  => 'eicon-woocommerce',
		) );

		/* place it right after the "Pro" category (or at the top if there is none) */
		try {
			$ref = new ReflectionObject( $manager );
			if ( $ref->hasProperty( 'categories' ) ) {
				$prop = $ref->getProperty( 'categories' );
				$prop->setAccessible( true );
				$cats = $prop->getValue( $manager );
				if ( is_array( $cats ) && isset( $cats[ self::CATEGORY ] ) ) {
					$ours = $cats[ self::CATEGORY ];
					unset( $cats[ self::CATEGORY ] );
					$after = apply_filters( 'nexvio_elementor_category_after', 'pro-elements' );
					$new   = array();
					$done  = false;
					if ( ! isset( $cats[ $after ] ) ) {
						$new[ self::CATEGORY ] = $ours;
						$done                  = true;
					}
					foreach ( $cats as $key => $cat ) {
						$new[ $key ] = $cat;
						if ( ! $done && $key === $after ) {
							$new[ self::CATEGORY ] = $ours;
							$done                  = true;
						}
					}
					$prop->setValue( $manager, $new );
				}
			}
		} catch ( Throwable $e ) {
			/* the category still exists, just not first */
			unset( $e );
		}
	}

	/** Visual "Design Layout" picker used by the Product Slider. */
	public function register_controls( $controls_manager ) {
		require_once NEXVIO_PATH . 'includes/class-nexvio-layout-control.php';
		$controls_manager->register( new Nexvio_Layout_Control() );
	}

	public function editor_styles() {
		wp_enqueue_style( 'nexvio-editor', NEXVIO_URL . 'assets/css/editor.css', array(), self::ver( 'assets/css/editor.css' ) );
		wp_add_inline_style( 'elementor-editor', '#elementor-panel-category-nexvio .elementor-panel-category-title{color:#3d8bff}#elementor-panel-category-nexvio .elementor-element .icon{color:#3d8bff}' );
	}

	/* ------------------------------------------------------------------
	 * Widgets
	 * ------------------------------------------------------------------ */

	public static function load_widget_classes() {
		require_once NEXVIO_PATH . 'includes/class-nexvio-layouts.php';
		require_once NEXVIO_PATH . 'includes/class-nexvio-swatches.php';
		require_once NEXVIO_PATH . 'includes/class-nexvio-texts.php';
		require_once NEXVIO_PATH . 'includes/class-nexvio-filter-config.php';
		require_once NEXVIO_PATH . 'includes/class-nexvio-cart-settings.php';
		require_once NEXVIO_PATH . 'includes/class-nexvio-side-cart.php';
		require_once NEXVIO_PATH . 'includes/trait-nexvio-card.php';
		foreach ( self::widgets() as $w ) {
			require_once NEXVIO_PATH . 'widgets/' . $w['file'];
		}
	}

	public function register_widgets( $widgets_manager ) {
		$this->register_assets();
		self::load_widget_classes();
		foreach ( self::widgets() as $key => $w ) {
			if ( self::is_enabled( $key ) && class_exists( $w['class'] ) ) {
				$widgets_manager->register( new $w['class']() );
			}
		}
	}

	/* ------------------------------------------------------------------
	 * Assets
	 * ------------------------------------------------------------------ */

	public function register_assets() {
		if ( wp_style_is( 'nexvio', 'registered' ) ) {
			return;
		}
		$styles = array(
			'nexvio'   => array( 'product-slider.css', array() ),
			'nexvio-add-to-cart'      => array( 'add-to-cart.css', array( 'nexvio' ) ),
			'nexvio-wishlist-widgets' => array( 'wishlist-widgets.css', array() ),
			'nexvio-product-filter'   => array( 'product-filter.css', array() ),
			'nexvio-filter-drawer'    => array( 'filter-drawer.css', array( 'nexvio-product-filter' ) ),
			'nexvio-header-search'    => array( 'header-search.css', array() ),
		);
		foreach ( $styles as $handle => $st ) {
			wp_register_style( $handle, NEXVIO_URL . 'assets/css/' . $st[0], $st[1], self::ver( 'assets/css/' . $st[0] ) );
		}
		if ( class_exists( 'Nexvio_Filter_Config' ) ) {
			wp_add_inline_style( 'nexvio-product-filter', Nexvio_Filter_Config::style_css() );
		}

		$scripts = array(
			'nexvio'   => array( 'product-slider.js', array() ),
			'nexvio-add-to-cart'      => array( 'add-to-cart.js', array() ),
			'nexvio-wishlist-widgets' => array( 'wishlist-widgets.js', array( 'nexvio' ) ),
			'nexvio-product-filter'   => array( 'product-filter.js', array() ),
			'nexvio-header-search'    => array( 'header-search.js', array() ),
			'nexvio-view-switch'      => array( 'view-switch.js', array() ),
			'nexvio-filter-drawer'    => array( 'filter-drawer.js', array() ),
		);
		foreach ( $scripts as $handle => $sc ) {
			wp_register_script( $handle, NEXVIO_URL . 'assets/js/' . $sc[0], $sc[1], self::ver( 'assets/js/' . $sc[0] ), true );
		}

		wp_localize_script( 'nexvio', 'NEXVIO', array(
			'ajax'     => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'nexvio_wishlist' ),
			'wishlist' => class_exists( 'Nexvio_Wishlist' ) ? Nexvio_Wishlist::url() : '',
		) );
	}

	/* ------------------------------------------------------------------
	 * WooCommerce
	 * ------------------------------------------------------------------ */

	public function per_page( $per_page ) {
		/* "Products per page" field of the Product Filter */
		$chosen = isset( $_GET['nexvio_pp'] ) ? absint( wp_unslash( $_GET['nexvio_pp'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only shop view setting.
		if ( $chosen >= 1 && $chosen <= 100 ) {
			return $chosen;
		}
		$value = (int) get_option( 'nexvio_per_page', 0 );
		return $value > 0 ? $value : $per_page;
	}

	public function maybe_replace_related() {
		if ( 'yes' !== get_option( 'nexvio_auto_related', 'no' ) || ! self::is_enabled( 'product_related' ) || ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}
		remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );
		add_action( 'woocommerce_after_single_product_summary', array( $this, 'print_related' ), 20 );
	}

	public function print_related() {
		echo wp_kses( $this->related_shortcode(), Nexvio_Plugin::allowed_html() );
	}

	/**
	 * [nexvio_related_products limit="8" layout="slider|grid" orderby="popularity|rating|rand|date|price"
	 *  related_by="specific|primary|top|all" fill_parent="yes|no" title="..." columns="4"]
	 */
	public function related_shortcode( $atts = array() ) {
		if ( ! class_exists( '\Elementor\Widget_Base' ) || ! function_exists( 'wc_get_product' ) ) {
			return '';
		}
		$atts = shortcode_atts( array(
			'limit'       => get_option( 'nexvio_related_limit', 8 ),
			'layout'      => get_option( 'nexvio_related_layout', 'slider' ),
			'orderby'     => get_option( 'nexvio_related_orderby', 'popularity' ),
			'related_by'  => 'specific',
			'fill_parent' => 'yes',
			'title'       => get_option( 'nexvio_related_title', 'You may also like' ),
			'subtitle'    => '',
			'columns'     => 4,
			'product_id'  => 0,
		), $atts, 'nexvio_related_products' );

		$this->register_assets();
		self::load_widget_classes();
		wp_enqueue_style( 'nexvio' );
		wp_enqueue_script( 'nexvio' );

		$settings = array(
			'limit'       => max( 1, (int) $atts['limit'] ),
			'layout'      => 'grid' === $atts['layout'] ? 'grid' : 'slider',
			'orderby'     => sanitize_key( $atts['orderby'] ),
			'related_by'  => sanitize_key( $atts['related_by'] ),
			'fill_parent' => 'no' === $atts['fill_parent'] ? '' : 'yes',
			'title'       => sanitize_text_field( $atts['title'] ),
			'subtitle'    => sanitize_text_field( $atts['subtitle'] ),
			'columns'     => max( 1, (int) $atts['columns'] ),
			'_product_id' => (int) $atts['product_id'],
		);
		$data = array(
			'id'         => 'nexviorel' . substr( md5( wp_json_encode( $settings ) ), 0, 6 ),
			'elType'     => 'widget',
			'widgetType' => 'nexvio_product_related',
			'settings'   => $settings,
		);

		$buffer = false;
		try {
			$widget = null;
			if ( isset( \Elementor\Plugin::$instance->elements_manager ) ) {
				$widget = \Elementor\Plugin::$instance->elements_manager->create_element_instance( $data );
			}
			if ( ! $widget ) {
				$type   = \Elementor\Plugin::$instance->widgets_manager->get_widget_types( 'nexvio_product_related' );
				$args   = $type && method_exists( $type, 'get_default_args' ) ? $type->get_default_args() : array();
				$widget = new Nexvio_Product_Related_Widget( $data, $args );
			}
			ob_start();
			$buffer = true;
			$widget->print_element();
			$html   = ob_get_clean();
			$buffer = false;
		} catch ( Throwable $e ) {
			if ( $buffer ) {
				ob_end_clean();
			}
			return '';
		}
		return false === strpos( $html, 'nexvio-ps__card' ) ? '' : '<div class="nexvio-related-auto">' . $html . '</div>';
	}
}
