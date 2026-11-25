<?php
/**
 * Side cart (Quick Cart): one panel per page, shared by the Header Cart,
 * the Floating Cart and every add-to-cart button. Uses the native
 * WooCommerce cart, session, calculations, validation and coupons.
 *
 * @package Nexvio_For_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Nexvio_Side_Cart {

	/** A Floating Cart widget was printed on this page (the global one is then skipped). */
	public static $floating_printed = false;

	public static function init() {
		/* read-only count: also used by the Header Cart when the side cart is off */
		add_action( 'wc_ajax_nexvio_cart_get', array( __CLASS__, 'ajax_get' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ), 6 );
		/* also used by the Cart Panel widget, which works with the side cart switched off */
		foreach ( array( 'qty', 'remove', 'coupon', 'coupon_remove', 'add' ) as $action ) {
			add_action( 'wc_ajax_nexvio_cart_' . $action, array( __CLASS__, 'ajax_' . $action ) );
		}

		if ( ! Nexvio_Cart_Settings::on( 'enabled' ) ) {
			return;
		}
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ), 20 );
		add_action( 'wp_footer', array( __CLASS__, 'footer' ), 20 );
	}

	/* ------------------------------------------------------------------
	 * Where the side cart is active
	 * ------------------------------------------------------------------ */

	public static function active() {
		if ( ! Nexvio_Cart_Settings::on( 'enabled' ) || is_admin() || ! function_exists( 'WC' ) || ! WC()->cart ) {
			return false;
		}
		if ( function_exists( 'is_checkout' ) && is_checkout() ) {
			return false;
		}
		$exclude = (array) Nexvio_Cart_Settings::get( 'exclude_pages' );
		if ( $exclude && is_singular() && in_array( (int) get_queried_object_id(), array_map( 'intval', $exclude ), true ) ) {
			return false;
		}
		return true;
	}

	/** Number shown on counters. */
	public static function count( $mode = '' ) {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return 0;
		}
		$mode = $mode ? $mode : Nexvio_Cart_Settings::get( 'count_mode' );
		return 'lines' === $mode ? count( WC()->cart->get_cart() ) : (int) WC()->cart->get_cart_contents_count();
	}

	/* ------------------------------------------------------------------
	 * Assets
	 * ------------------------------------------------------------------ */

	public static function register_assets() {
		if ( wp_script_is( 'nexvio-side-cart', 'registered' ) ) {
			return;
		}
		wp_register_style( 'nexvio-side-cart', NEXVIO_URL . 'assets/css/side-cart.css', array(), Nexvio_Plugin::ver( 'assets/css/side-cart.css' ) );
		wp_register_script( 'nexvio-side-cart', NEXVIO_URL . 'assets/js/side-cart.js', array(), Nexvio_Plugin::ver( 'assets/js/side-cart.js' ), true );
		$types = Nexvio_Cart_Settings::get( 'open_types' );
		wp_localize_script( 'nexvio-side-cart', 'NEXVIO_CART', array(
			'enabled'      => self::active(),
			'ajax'         => class_exists( 'WC_AJAX' ) ? WC_AJAX::get_endpoint( '%%endpoint%%' ) : '',
			'nonce'        => wp_create_nonce( 'nexvio_cart' ),
			'autoOpen'     => Nexvio_Cart_Settings::on( 'auto_open' ),
			'openTypes'    => is_array( $types ) ? $types : array(),
			'ajaxSingle'   => Nexvio_Cart_Settings::on( 'ajax_single' ),
			'closeOverlay' => Nexvio_Cart_Settings::on( 'close_overlay' ),
			'closeEsc'     => Nexvio_Cart_Settings::on( 'close_esc' ),
			'countMode'    => Nexvio_Cart_Settings::get( 'count_mode' ),
			'cartUrl'      => Nexvio_Cart_Settings::cart_url(),
			'i18n'         => array(
				'error'   => Nexvio_Texts::get( 'error' ),
				'options' => Nexvio_Texts::get( 'options' ),
				'coupon'  => Nexvio_Texts::get( 'coupon_empty' ),
			),
		) );
		wp_add_inline_style( 'nexvio-side-cart', self::css_vars() );
	}

	public static function enqueue() {
		if ( self::active() ) {
			wp_enqueue_style( 'nexvio-side-cart' );
			wp_enqueue_script( 'nexvio-side-cart' );
		}
	}

	/** Settings → CSS variables: one main colour (+ readable text on it) and the panel width. */
	private static function css_vars() {
		$main = sanitize_hex_color( (string) Nexvio_Cart_Settings::get( 'main_color' ) );
		$main = $main ? $main : '#b08d57';
		$sizes = Nexvio_Cart_Settings::fl_sizes();
		$size  = isset( $sizes[ Nexvio_Cart_Settings::get( 'fl_size' ) ] ) ? $sizes[ Nexvio_Cart_Settings::get( 'fl_size' ) ] : $sizes['medium'];
		$imgs  = array( 'small' => array( 76, 72 ), 'medium' => array( 96, 84 ), 'large' => array( 116, 100 ) );
		$img   = isset( $imgs[ Nexvio_Cart_Settings::get( 'img_size' ) ] ) ? $imgs[ Nexvio_Cart_Settings::get( 'img_size' ) ] : $imgs['medium'];
		$lines = max( 1, min( 3, (int) Nexvio_Cart_Settings::get( 'title_lines' ) ) );
		$vars = array(
			'--wsc-img:' . (int) $img[0] . 'px',
			'--wsc-lines:' . (int) $lines,
			'--wfc-x:' . (int) Nexvio_Cart_Settings::get( 'fl_x' ) . 'px',
			'--wfc-y:' . (int) Nexvio_Cart_Settings::get( 'fl_y' ) . 'px',
			'--wfc-y-m:' . (int) Nexvio_Cart_Settings::get( 'fl_y_m' ) . 'px',
			'--fc-size:' . (int) $size[0] . 'px',
			'--fc-icon:' . (int) round( $size[0] * 0.38 ) . 'px',
			'--wsc-mc:' . $main,
			'--wsc-mc-text:' . Nexvio_Cart_Settings::contrast( $main ),
			'--wsc-w:' . (int) Nexvio_Cart_Settings::get( 'width' ) . 'px',
			'--wsc-wm:' . (int) Nexvio_Cart_Settings::get( 'width_m' ) . '%',
		);
		$css = '#nexvio-side-cart,.nexvio-icart,.nexvio-fc,.nexvio-hc{' . implode( ';', $vars ) . '}';
		$css .= '@media (max-width:767px){#nexvio-side-cart,.nexvio-icart{--wsc-img:' . (int) $img[1] . 'px}}';
		$fonts = self::font_vars();
		if ( $fonts ) {
			$css .= '#nexvio-side-cart,.nexvio-icart{' . implode( ';', $fonts ) . '}';
		}
		/* phones: a slightly smaller button */
		$css .= '@media (max-width:767px){.nexvio-fc{--fc-size:' . (int) $size[1] . 'px;--fc-icon:' . (int) round( $size[1] * 0.38 ) . 'px}}';
		return $css;
	}

	/** A font list typed in the dashboard -> a safe CSS font-family value ('' when empty). */
	private static function font_stack( $raw ) {
		$generic = array( 'serif', 'sans-serif', 'monospace', 'cursive', 'system-ui' );
		$names   = array();
		foreach ( explode( ',', (string) $raw ) as $name ) {
			$name = trim( $name );
			if ( '' === $name ) {
				continue;
			}
			$names[] = in_array( strtolower( $name ), $generic, true ) ? strtolower( $name ) : '"' . $name . '"';
		}
		return implode( ',', $names );
	}

	/** Font settings -> CSS variables (empty when both fonts are "Default"). */
	public static function font_vars() {
		$out    = array();
		$global = array( 'primary', 'secondary', 'text', 'accent' );
		$rows   = array(
			'heading' => array( '--wsc-hf', '"Cormorant Garamond"', ',Georgia,serif' ),
			'body'    => array( '--wsc-bf', 'inherit', ',sans-serif' ),
		);
		foreach ( $rows as $which => $cfg ) {
			$choice = (string) Nexvio_Cart_Settings::get( 'font_' . $which );
			if ( in_array( $choice, $global, true ) ) {
				/* the Elementor global font; if the site has none, the default font is used */
				$out[] = $cfg[0] . ':var(--e-global-typography-' . $choice . '-font-family,' . $cfg[1] . ')' . ( 'heading' === $which ? $cfg[2] : '' );
			} elseif ( 'custom' === $choice ) {
				$stack = self::font_stack( Nexvio_Cart_Settings::get( 'font_' . $which . '_custom' ) );
				if ( '' !== $stack ) {
					/* already ends with serif / sans-serif…: no extra fallback needed */
					$tail  = preg_match( '/(?:^|,)(?:serif|sans-serif|monospace|cursive|system-ui)$/', $stack ) ? '' : $cfg[2];
					$out[] = $cfg[0] . ':' . $stack . $tail;
				}
			}
		}
		return $out;
	}

	public static function preset() {
		return 'dark' === Nexvio_Cart_Settings::get( 'preset' ) ? 'dark' : 'light';
	}

	/* ------------------------------------------------------------------
	 * Footer: the one panel (+ the global floating cart)
	 * ------------------------------------------------------------------ */

	public static function footer() {
		if ( ! self::active() ) {
			return;
		}
		$cls = 'nexvio-sc nexvio-sc--' . self::preset();
		if ( in_array( Nexvio_Texts::lang(), array( 'bn', 'ar', 'hi', 'ja', 'zh', 'ko', 'th', 'fa', 'he', 'ur', 'ta', 'te', 'mr', 'gu', 'pa', 'ne', 'si' ), true ) ) {
			$cls .= ' nexvio-sc--script'; /* no spaced capitals for these alphabets */
		}
		echo '<div id="nexvio-side-cart" class="' . esc_attr( $cls ) . '" aria-hidden="true" inert>';
		echo '<div class="nexvio-sc__overlay" data-nexvio-cart-overlay></div>';
		echo '<aside class="nexvio-sc__panel" role="dialog" aria-modal="true" aria-labelledby="nexvio-sc-title" tabindex="-1">';
		echo '<div class="nexvio-sc__inner">';
		echo wp_kses( self::render_inner(), Nexvio_Plugin::allowed_html() );
		echo '</div><div class="nexvio-sc__loading" aria-hidden="true"><span></span></div></aside></div>';

		if ( Nexvio_Cart_Settings::on( 'floating_enabled' ) && Nexvio_Cart_Settings::on( 'floating_global' ) && ! self::$floating_printed ) {
			echo wp_kses( self::floating_html( array( 'global' => true ) ), Nexvio_Plugin::allowed_html() );
		}
	}

	/** The cart on a page (Cart Panel widget): the same content as the side cart, always visible. */
	public static function inline_html( $preset = '', $classes = '' ) {
		if ( ! function_exists( 'WC' ) ) {
			return '';
		}
		if ( ! WC()->cart && function_exists( 'wc_load_cart' ) ) {
			wc_load_cart();
		}
		if ( ! WC()->cart ) {
			return '';
		}
		$preset = in_array( $preset, array( 'light', 'dark' ), true ) ? $preset : self::preset();
		$cls    = 'nexvio-sc nexvio-sc--' . $preset . ' nexvio-icart' . ( $classes ? ' ' . $classes : '' );
		if ( in_array( Nexvio_Texts::lang(), array( 'bn', 'ar', 'hi', 'ja', 'zh', 'ko', 'th', 'fa', 'he', 'ur', 'ta', 'te', 'mr', 'gu', 'pa', 'ne', 'si' ), true ) ) {
			$cls .= ' nexvio-sc--script';
		}
		return '<div class="' . esc_attr( $cls ) . '" data-nexvio-inline><div class="nexvio-sc__inner">' . self::render_inner() . '</div><div class="nexvio-sc__loading" aria-hidden="true"><span></span></div></div>';
	}

	/* ------------------------------------------------------------------
	 * Buttons used by the widgets
	 * ------------------------------------------------------------------ */

	public static function icon( $name ) {
		$icons = array(
			'bag'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 7h12l1 13H5L6 7z"/><path d="M9 7V6a3 3 0 0 1 6 0v1"/></svg>',
			'cart'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 4h2l2.4 11h10.2L20 7H6.3"/><circle cx="9.5" cy="19" r="1.3"/><circle cx="17" cy="19" r="1.3"/></svg>',
			'heart' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" aria-hidden="true"><path d="M12 20s-7-4.4-9-9a4.8 4.8 0 0 1 9-3 4.8 4.8 0 0 1 9 3c-2 4.6-9 9-9 9z"/></svg>',
			'close' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>',
			'trash' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg>',
			'basket' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 10h18l-1.8 9H4.8L3 10z"/><path d="M8 10l3-6M16 10l-3-6"/></svg>',
			'tote'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="8" width="16" height="12" rx="2"/><path d="M8 8V7a4 4 0 0 1 8 0v1"/></svg>',
			'handbag' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="8" width="18" height="12" rx="3"/><path d="M8 8V6.5A2.5 2.5 0 0 1 10.5 4h3A2.5 2.5 0 0 1 16 6.5V8"/><path d="M3 13h18"/></svg>',
			'trolley' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 4h3l2.2 11h11l2-8H6"/><path d="M9 10h9"/><circle cx="9" cy="19.5" r="1.4"/><circle cx="17" cy="19.5" r="1.4"/></svg>',
			'package' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l8 4v10l-8 4-8-4V7l8-4z"/><path d="M4 7l8 4 8-4M12 11v10"/></svg>',
			'gift'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="9" width="18" height="4" rx="1"/><path d="M5 13v7h14v-7M12 9v11"/><path d="M12 9S9 8.5 8.2 6.8A2 2 0 0 1 12 6v3zM12 9s3-.5 3.8-2.2A2 2 0 0 0 12 6v3z"/></svg>',
			'store' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 10l1.5-5.5h15L21 10"/><path d="M3 10a3 3 0 0 0 6 0 3 3 0 0 0 6 0 3 3 0 0 0 6 0"/><path d="M5 12.5V20h14v-7.5"/></svg>',
			'tag'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" aria-hidden="true"><path d="M3 12V4h8l10 10-8 8L3 12z"/><circle cx="7.5" cy="8.5" r="1.3"/></svg>',
		);
		return isset( $icons[ $name ] ) ? $icons[ $name ] : $icons['bag'];
	}

	/** The icon chosen in Nexvio > Add to Cart (a built-in one or a custom image). */
	public static function floating_icon() {
		$choice = (string) Nexvio_Cart_Settings::get( 'fl_icon' );
		$url    = (string) Nexvio_Cart_Settings::get( 'fl_icon_url' );
		if ( 'custom' === $choice && '' !== $url ) {
			return '<img src="' . esc_url( $url ) . '" alt="" loading="lazy" decoding="async">';
		}
		return self::icon( 'custom' === $choice ? 'bag' : $choice );
	}

	/**
	 * Floating cart button.
	 * $a: global, icon_html, label, show_count, hide_zero, side, opens, classes, devices.
	 */
	public static function floating_html( $a = array() ) {
		$a = array_merge( array(
			'global'     => false,
			'icon_html'  => self::floating_icon(),
			'label'      => Nexvio_Texts::get( 'open_cart' ),
			'show_count' => true,
			'hide_zero'  => true,
			'side'       => Nexvio_Cart_Settings::get( 'fl_side' ),
			'opens'      => Nexvio_Cart_Settings::on( 'open_floating' ) && self::active(),
			'classes'    => '',
			'devices'    => array(
				'desktop' => in_array( 'desktop', (array) Nexvio_Cart_Settings::get( 'fl_devices' ), true ),
				'tablet'  => in_array( 'tablet', (array) Nexvio_Cart_Settings::get( 'fl_devices' ), true ),
				'mobile'  => in_array( 'mobile', (array) Nexvio_Cart_Settings::get( 'fl_devices' ), true ),
			),
		), $a );
		if ( Nexvio_Cart_Settings::on( 'fl_hide_pages' ) && ( ( function_exists( 'is_cart' ) && is_cart() ) || ( function_exists( 'is_checkout' ) && is_checkout() ) ) ) {
			return '';
		}
		$count = self::count();
		$cls   = 'nexvio-fc nexvio-fc--' . self::preset() . ' nexvio-fc--' . ( 'left' === $a['side'] ? 'left' : 'right' ) . ( $a['global'] ? ' nexvio-fc--global' : '' ) . ( Nexvio_Cart_Settings::on( 'fl_pulse' ) ? ' nexvio-fc--pulse' : '' ) . ( $a['classes'] ? ' ' . $a['classes'] : '' ) . ( $count < 1 ? ' is-empty' : '' );
		foreach ( $a['devices'] as $device => $show ) {
			if ( ! $show ) {
				$cls .= ' nexvio-hide-' . $device;
			}
		}
		$attrs = $a['opens'] ? ' data-nexvio-cart-open' : '';
		$html  = '<a href="' . esc_url( Nexvio_Cart_Settings::cart_url() ) . '" class="' . esc_attr( $cls ) . '"' . $attrs . ' aria-label="' . esc_attr( $a['label'] ) . '">';
		$html .= '<span class="nexvio-fc__ring" aria-hidden="true"></span><span class="nexvio-fc__icon" aria-hidden="true">' . $a['icon_html'] . '</span>';
		if ( $a['show_count'] ) {
			$html .= '<span class="nexvio-fc__count nexvio-cart-count' . ( $a['hide_zero'] && $count < 1 ? ' is-zero-hidden' : '' ) . '" data-hide-zero="' . ( $a['hide_zero'] ? '1' : '0' ) . '">' . (int) $count . '</span>';
		}
		$html .= '</a>';
		return $html;
	}

	/* ------------------------------------------------------------------
	 * Panel content
	 * ------------------------------------------------------------------ */

	private static function opt( $key ) {
		return Nexvio_Cart_Settings::get( $key );
	}

	public static function render_inner() {
		$cart  = WC()->cart;
		$items = array();
		foreach ( $cart->get_cart() as $key => $item ) {
			$product = apply_filters( 'woocommerce_cart_item_product', $item['data'], $item, $key ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core hook.
			if ( ! $product || ! $product->exists() || $item['quantity'] < 1 || ! apply_filters( 'woocommerce_widget_cart_item_visible', true, $item, $key ) ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core hook.
				continue;
			}
			$items[ $key ] = array( $item, $product );
		}
		$count    = self::count();
		$subtitle = Nexvio_Cart_Settings::text( 'subtitle' );

		ob_start();
		?>
		<header class="nexvio-sc__head">
			<div class="nexvio-sc__heading">
				<h2 id="nexvio-sc-title" class="nexvio-sc__title"><?php echo esc_html( Nexvio_Cart_Settings::text( 'title' ) ); ?> <span class="nexvio-sc__badge nexvio-cart-count" data-hide-zero="0"><?php echo (int) $count; ?></span></h2>
				<?php if ( '' !== $subtitle && $items ) : ?>
					<p class="nexvio-sc__sub"><?php echo esc_html( $subtitle ); ?></p>
				<?php endif; ?>
			</div>
			<button type="button" class="nexvio-sc__close" data-nexvio-cart-close aria-label="<?php echo esc_attr( Nexvio_Texts::get( 'close' ) ); ?>"><?php echo wp_kses( self::icon( 'close' ), Nexvio_Plugin::allowed_html() ); ?></button>
		</header>
		<div class="nexvio-sc__msgs" role="status" aria-live="polite"></div>
		<?php
		if ( ! $items ) {
			self::render_empty();
		} else {
			echo '<div class="nexvio-sc__body"><ul class="nexvio-sc__items">';
			foreach ( $items as $key => $pair ) {
				self::render_item( $key, $pair[0], $pair[1] );
			}
			echo '</ul></div>';
			self::render_footer();
		}
		return ob_get_clean();
	}

	private static function render_empty() {
		?>
		<div class="nexvio-sc__empty">
			<div class="nexvio-sc__empty-icon"><?php echo wp_kses( self::icon( 'bag' ), Nexvio_Plugin::allowed_html() ); ?></div>
			<h3 class="nexvio-sc__empty-title"><?php echo esc_html( Nexvio_Cart_Settings::text( 'empty_title' ) ); ?></h3>
			<p class="nexvio-sc__empty-text"><?php echo esc_html( Nexvio_Cart_Settings::text( 'empty_text' ) ); ?></p>
			<a class="nexvio-sc__btn nexvio-sc__btn--primary" href="<?php echo esc_url( Nexvio_Cart_Settings::shop_url() ); ?>"><?php echo esc_html( Nexvio_Cart_Settings::text( 'continue_label' ) ); ?></a>
		</div>
		<?php
	}

	private static function render_item( $key, $item, $product ) {
		$qty       = (int) $item['quantity'];
		$link      = $product->is_visible() ? $product->get_permalink( $item ) : '';
		$name      = apply_filters( 'woocommerce_cart_item_name', $product->get_name(), $item, $key ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core hook.
		$thumb     = apply_filters( 'woocommerce_cart_item_thumbnail', $product->get_image( 'woocommerce_thumbnail' ), $item, $key ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core hook.
		$unit      = apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $product ), $item, $key ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core hook.
		$line      = apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $product, $qty ), $item, $key ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core hook.
		$max       = $product->is_sold_individually() ? 1 : (int) $product->get_max_purchase_quantity();
		$regular   = '';
		if ( $product->is_on_sale() && '' !== (string) $product->get_regular_price() ) {
			$regular = wc_price( wc_get_price_to_display( $product, array( 'price' => $product->get_regular_price() ) ) );
		}
		$full_name = wp_strip_all_tags( $name );
		$remove_label = Nexvio_Texts::get( 'remove' ) . ': ' . wp_strip_all_tags( $name );
		$meta = 'yes' === self::opt( 'show_variation' ) ? trim( wp_strip_all_tags( str_replace( array( '</dd>', '</dt>' ), array( ' · ', ' ' ), wc_get_formatted_cart_item_data( $item ) ) ) ) : '';
		$meta = trim( preg_replace( '/\s+/', ' ', $meta ), " ·\t\n" );
		?>
		<li class="nexvio-sc__item" data-key="<?php echo esc_attr( $key ); ?>">
			<?php if ( $link ) : ?>
				<a class="nexvio-sc__thumb" href="<?php echo esc_url( $link ); ?>" tabindex="-1" aria-hidden="true"><?php echo wp_kses_post( $thumb ); ?></a>
			<?php else : ?>
				<span class="nexvio-sc__thumb" aria-hidden="true"><?php echo wp_kses_post( $thumb ); ?></span>
			<?php endif; ?>
			<div class="nexvio-sc__info">
				<div class="nexvio-sc__top">
					<?php if ( $link ) : ?>
						<a class="nexvio-sc__name" href="<?php echo esc_url( $link ); ?>" title="<?php echo esc_attr( $full_name ); ?>"><?php echo wp_kses_post( $name ); ?></a>
					<?php else : ?>
						<span class="nexvio-sc__name" title="<?php echo esc_attr( $full_name ); ?>"><?php echo wp_kses_post( $name ); ?></span>
					<?php endif; ?>
					<span class="nexvio-sc__line"><?php echo wp_kses_post( $line ); ?></span>
				</div>
				<div class="nexvio-sc__meta">
					<?php if ( '' !== $meta ) : ?><span class="nexvio-sc__var"><?php echo esc_html( $meta ); ?></span><?php endif; ?>
					<span class="nexvio-sc__unit"><?php echo $regular ? '<del>' . wp_kses_post( $regular ) . '</del> <ins class="nexvio-sc__sale">' . wp_kses_post( $unit ) . '</ins>' : wp_kses_post( $unit ); ?></span>
				</div>
				<div class="nexvio-sc__bottom">
					<?php if ( 1 !== $max ) : ?>
						<div class="nexvio-sc__qty" role="group" aria-label="<?php echo esc_attr( Nexvio_Texts::get( 'qty' ) ); ?>">
							<button type="button" data-nexvio-qty="-1" aria-label="−"<?php disabled( $qty <= 1 ); ?>>−</button>
							<input type="number" inputmode="numeric" min="1"<?php echo $max > 0 ? ' max="' . (int) $max . '"' : ''; ?> value="<?php echo (int) $qty; ?>" aria-label="<?php echo esc_attr( Nexvio_Texts::get( 'qty' ) ); ?>">
							<button type="button" data-nexvio-qty="1" aria-label="+"<?php disabled( $max > 0 && $qty >= $max ); ?>>+</button>
						</div>
					<?php else : ?>
						<span class="nexvio-sc__qty-text">× <?php echo (int) $qty; ?></span>
					<?php endif; ?>
					<button type="button" class="nexvio-sc__remove" data-nexvio-remove aria-label="<?php echo esc_attr( $remove_label ); ?>" title="<?php echo esc_attr( Nexvio_Texts::get( 'remove' ) ); ?>"><?php echo wp_kses( self::icon( 'trash' ), Nexvio_Plugin::allowed_html() ); ?><span><?php echo esc_html( Nexvio_Texts::get( 'remove' ) ); ?></span></button>
				</div>
			</div>
		</li>
		<?php
	}

	private static function render_footer() {
		$cart     = WC()->cart;
		$coupons  = $cart->get_coupons();
		$shipping  = $cart->needs_shipping();
		/* side cart shows product prices only: fees (e.g. Delivery fee) are added at checkout */
		$fee_sum   = (float) $cart->get_fee_total() + (float) $cart->get_fee_tax();
		$total_now = max( 0, (float) $cart->get_total( 'edit' ) - $fee_sum );
		$coupon_id = wp_unique_id( 'nexvio-sc-coupon-' );
		?>
		<footer class="nexvio-sc__foot">
			<?php if ( 'yes' === self::opt( 'coupon' ) && function_exists( 'wc_coupons_enabled' ) && wc_coupons_enabled() ) : ?>
				<details class="nexvio-sc__coupon">
					<summary><?php echo wp_kses( self::icon( 'tag' ), Nexvio_Plugin::allowed_html() ); ?><span><?php echo esc_html( Nexvio_Texts::get( 'coupon_link' ) ); ?></span></summary>
					<form class="nexvio-sc__coupon-form" data-nexvio-coupon>
						<label class="screen-reader-text" for="<?php echo esc_attr( $coupon_id ); ?>"><?php echo esc_html( Nexvio_Texts::get( 'coupon_placeholder' ) ); ?></label>
						<input type="text" id="<?php echo esc_attr( $coupon_id ); ?>" name="coupon_code" autocomplete="off" placeholder="<?php echo esc_attr( Nexvio_Texts::get( 'coupon_placeholder' ) ); ?>">
						<button type="submit"><?php echo esc_html( Nexvio_Texts::get( 'apply' ) ); ?></button>
					</form>
				</details>
			<?php endif; ?>

			<dl class="nexvio-sc__totals">
				<?php if ( $coupons || $shipping ) : ?>
					<div class="nexvio-sc__row"><dt><?php echo esc_html( Nexvio_Texts::get( 'subtotal' ) ); ?></dt><dd><?php echo wp_kses_post( $cart->get_cart_subtotal() ); ?></dd></div>
				<?php endif; ?>
				<?php foreach ( $coupons as $code => $coupon ) : ?>
					<div class="nexvio-sc__row nexvio-sc__row--coupon">
						<dt><?php echo esc_html( wc_cart_totals_coupon_label( $coupon, false ) ); ?> <button type="button" class="nexvio-sc__coupon-remove" data-nexvio-coupon-remove="<?php echo esc_attr( $code ); ?>"><?php echo esc_html( Nexvio_Texts::get( 'remove_coupon' ) ); ?></button></dt>
						<dd>−<?php echo wp_kses_post( wc_price( $cart->get_coupon_discount_amount( $code, $cart->display_cart_ex_tax ) ) ); ?></dd>
					</div>
				<?php endforeach; ?>
				<?php if ( $shipping ) : ?>
					<div class="nexvio-sc__row"><dt><?php echo esc_html( Nexvio_Texts::get( 'shipping' ) ); ?></dt><dd><?php echo $cart->show_shipping() && $cart->get_customer()->has_calculated_shipping() ? wp_kses_post( $cart->get_cart_shipping_total() ) : esc_html( Nexvio_Texts::get( 'calc' ) ); ?></dd></div>
				<?php endif; ?>
				<div class="nexvio-sc__row nexvio-sc__row--total"><dt><?php echo esc_html( Nexvio_Texts::get( 'total' ) ); ?></dt><dd><?php echo wp_kses_post( wc_price( $total_now ) ); ?></dd></div>
			</dl>

			<div class="nexvio-sc__actions">
				<a class="nexvio-sc__btn nexvio-sc__btn--primary" href="<?php echo esc_url( Nexvio_Cart_Settings::checkout_url() ); ?>"><span><?php echo esc_html( Nexvio_Cart_Settings::text( 'checkout_label' ) ); ?></span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
				<?php if ( 'yes' === self::opt( 'shop_more' ) ) : ?>
					<a class="nexvio-sc__btn nexvio-sc__btn--ghost" href="<?php echo esc_url( Nexvio_Cart_Settings::shop_url() ); ?>" data-nexvio-shop-more><span><?php echo esc_html( Nexvio_Cart_Settings::text( 'shop_label' ) ); ?></span></a>
				<?php endif; ?>
			</div>
		</footer>
		<?php
	}

	/* ------------------------------------------------------------------
	 * AJAX (WooCommerce wc-ajax endpoints)
	 * ------------------------------------------------------------------ */

	/** Errors / messages WooCommerce collected during this request (and clear them). */
	private static function notices() {
		$out = array();
		if ( function_exists( 'wc_get_notices' ) ) {
			foreach ( array( 'error', 'notice', 'success' ) as $type ) {
				foreach ( (array) wc_get_notices( $type ) as $n ) {
					$text = is_array( $n ) ? $n['notice'] : $n;
					$out[] = array( 'type' => $type, 'text' => trim( wp_strip_all_tags( html_entity_decode( (string) $text ) ) ) );
				}
			}
			wc_clear_notices();
		}
		return $out;
	}

	private static function state( $messages = array(), $extra = array() ) {
		$cart      = WC()->cart;
		$fragments = array();
		if ( function_exists( 'woocommerce_mini_cart' ) ) {
			ob_start();
			woocommerce_mini_cart();
			$fragments['div.widget_shopping_cart_content'] = '<div class="widget_shopping_cart_content">' . ob_get_clean() . '</div>';
		}
		return array_merge( array(
			'html'      => self::render_inner(),
			'count'     => self::count(),
			'qty'       => (int) $cart->get_cart_contents_count(),
			'lines'     => count( $cart->get_cart() ),
			'empty'     => $cart->is_empty(),
			'nonce'     => wp_create_nonce( 'nexvio_cart' ),
			'messages'  => $messages,
			'fragments' => apply_filters( 'woocommerce_add_to_cart_fragments', $fragments ), // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core hook, keeps theme mini carts in sync.
			'cart_hash' => $cart->get_cart_hash(),
		), $extra );
	}

	private static function ready() {
		nocache_headers();
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			wp_send_json_error( array( 'message' => 'no_cart' ), 500 );
		}
	}

	/** Invalid nonce: answer with a fresh one so the browser can retry once (guest sessions change nonces). */
	private static function nonce_failed() {
		wp_send_json_error( array( 'nonce' => wp_create_nonce( 'nexvio_cart' ), 'retry' => true ), 403 );
	}

	public static function ajax_get() {
		self::ready();
		wp_send_json_success( self::state() );
	}

	public static function ajax_qty() {
		self::ready();
		if ( ! check_ajax_referer( 'nexvio_cart', 'nonce', false ) ) {
			self::nonce_failed();
		}
		$key  = isset( $_POST['key'] ) ? sanitize_text_field( wp_unslash( $_POST['key'] ) ) : '';
		$qty  = isset( $_POST['qty'] ) ? (int) sanitize_text_field( wp_unslash( $_POST['qty'] ) ) : -1;
		$item = WC()->cart->get_cart_item( $key );
		if ( ! $item || $qty < 0 ) {
			wp_send_json_error( self::state( array( array( 'type' => 'error', 'text' => __( 'This product is no longer in your cart.', 'nexvio' ) ) ) ), 400 );
		}
		$messages = array();
		if ( $qty > 0 ) {
			$product = $item['data'];
			$max     = $product->is_sold_individually() ? 1 : (int) $product->get_max_purchase_quantity();
			if ( $max > 0 && $qty > $max ) {
				$qty = $max;
				/* translators: %d: maximum quantity */
				$messages[] = array( 'type' => 'notice', 'text' => sprintf( __( 'Only %d available.', 'nexvio' ), $max ) );
			}
			$passed = apply_filters( 'woocommerce_update_cart_validation', true, $key, $item, $qty ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core hook.
			if ( $passed ) {
				WC()->cart->set_quantity( $key, $qty, true );
			}
		} else {
			WC()->cart->remove_cart_item( $key );
		}
		WC()->cart->calculate_totals();
		wp_send_json_success( self::state( array_merge( $messages, self::notices() ) ) );
	}

	public static function ajax_remove() {
		self::ready();
		if ( ! check_ajax_referer( 'nexvio_cart', 'nonce', false ) ) {
			self::nonce_failed();
		}
		$key = isset( $_POST['key'] ) ? sanitize_text_field( wp_unslash( $_POST['key'] ) ) : '';
		if ( WC()->cart->get_cart_item( $key ) ) {
			WC()->cart->remove_cart_item( $key );
			WC()->cart->calculate_totals();
		}
		wp_send_json_success( self::state( self::notices() ) );
	}

	public static function ajax_coupon() {
		self::ready();
		if ( ! check_ajax_referer( 'nexvio_cart', 'nonce', false ) ) {
			self::nonce_failed();
		}
		$code = isset( $_POST['code'] ) ? wc_format_coupon_code( sanitize_text_field( wp_unslash( $_POST['code'] ) ) ) : '';
		if ( '' === $code ) {
			wp_send_json_error( self::state( array( array( 'type' => 'error', 'text' => Nexvio_Texts::get( 'coupon_empty' ) ) ) ), 400 );
		}
		$ok = WC()->cart->apply_coupon( $code );
		WC()->cart->calculate_totals();
		$messages = self::notices();
		if ( $ok ) {
			wp_send_json_success( self::state( $messages, array( 'coupon_ok' => true ) ) );
		}
		wp_send_json_error( self::state( $messages ? $messages : array( array( 'type' => 'error', 'text' => __( 'This coupon is not valid.', 'nexvio' ) ) ) ), 400 );
	}

	public static function ajax_coupon_remove() {
		self::ready();
		if ( ! check_ajax_referer( 'nexvio_cart', 'nonce', false ) ) {
			self::nonce_failed();
		}
		$code = isset( $_POST['code'] ) ? wc_format_coupon_code( sanitize_text_field( wp_unslash( $_POST['code'] ) ) ) : '';
		if ( $code ) {
			WC()->cart->remove_coupon( $code );
			WC()->cart->calculate_totals();
		}
		wp_send_json_success( self::state( self::notices() ) );
	}

	/** Add from a product page form (simple and variable products, with options). */
	public static function ajax_add() {
		self::ready();
		if ( ! check_ajax_referer( 'nexvio_cart', 'nonce', false ) ) {
			self::nonce_failed();
		}
		$product_id = isset( $_POST['nexvio_product_id'] ) ? absint( wp_unslash( $_POST['nexvio_product_id'] ) ) : 0;
		$product    = $product_id ? wc_get_product( $product_id ) : null;
		if ( ! $product || ! in_array( $product->get_type(), array( 'simple', 'variable' ), true ) ) {
			wp_send_json_error( array( 'fallback' => true ), 400 );
		}
		$qty          = isset( $_POST['quantity'] ) ? wc_stock_amount( absint( wp_unslash( $_POST['quantity'] ) ) ) : 1;
		$qty          = $qty > 0 ? $qty : 1;
		$variation_id = isset( $_POST['variation_id'] ) ? absint( wp_unslash( $_POST['variation_id'] ) ) : 0;
		$variations   = array();
		if ( $product->is_type( 'variable' ) ) {
			if ( ! $variation_id ) {
				wp_send_json_error( self::state( array( array( 'type' => 'error', 'text' => Nexvio_Texts::get( 'options' ) ) ) ), 400 );
			}
			foreach ( $_POST as $k => $v ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce checked above.
				if ( is_string( $k ) && 0 === strpos( $k, 'attribute_' ) && is_scalar( $v ) ) {
					$variations[ sanitize_title( wp_unslash( $k ) ) ] = wc_clean( wp_unslash( $v ) );
				}
			}
		}
		$passed = apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $qty, $variation_id, $variations ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core hook.
		$added  = $passed ? WC()->cart->add_to_cart( $product_id, $qty, $variation_id, $variations ) : false;
		$msgs   = self::notices();
		if ( false === $added ) {
			wp_send_json_error( self::state( $msgs ? $msgs : array( array( 'type' => 'error', 'text' => __( 'This product could not be added to your cart.', 'nexvio' ) ) ) ), 400 );
		}
		do_action( 'woocommerce_ajax_added_to_cart', $product_id ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core hook.
		wp_send_json_success( self::state( array(), array( 'type' => $product->get_type() ) ) );
	}
}
