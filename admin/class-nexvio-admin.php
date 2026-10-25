<?php
/**
 * Nexvio - admin app (dashboard, widgets, settings, documentation).
 *
 * @package Nexvio_For_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Nexvio_Admin {

	const SLUG     = 'nexvio';
	const WIDGETS  = 'nexvio-widgets';
	const SETTINGS = 'nexvio-settings';
	const DOCS     = 'nexvio-docs';
	const CART     = 'nexvio-cart';
	const FILTERS  = 'nexvio-filters';
	const WISHLIST = 'nexvio-wishlist';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'wp_ajax_nexvio_toggle_widget', array( __CLASS__, 'ajax_toggle' ) );
		add_action( 'wp_ajax_nexvio_bulk_widgets', array( __CLASS__, 'ajax_bulk' ) );
		add_action( 'admin_post_nexvio_save_settings', array( __CLASS__, 'save_settings' ) );
		add_action( 'admin_post_nexvio_save_cart_settings', array( __CLASS__, 'save_cart_settings' ) );
		add_action( 'admin_post_nexvio_save_filters', array( __CLASS__, 'save_filters' ) );
		add_action( 'admin_post_nexvio_create_wishlist_page', array( __CLASS__, 'create_wishlist_page' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( NEXVIO_FILE ), array( __CLASS__, 'action_links' ) );
		add_filter( 'admin_body_class', array( __CLASS__, 'body_class' ) );
	}

	private static function pages() {
		return array(
			self::SLUG     => array( __( 'Dashboard', 'nexvio' ), 'dashicons-dashboard', 'page_dashboard' ),
			self::WIDGETS  => array( __( 'Widgets', 'nexvio' ), 'dashicons-screenoptions', 'page_widgets' ),
			self::FILTERS  => array( __( 'Filters', 'nexvio' ), 'dashicons-filter', 'page_filters' ),
			self::CART     => array( __( 'Add to Cart', 'nexvio' ), 'dashicons-cart', 'page_cart' ),
			self::WISHLIST => array( __( 'Wishlist', 'nexvio' ), 'dashicons-heart', 'page_wishlist' ),
			self::SETTINGS => array( __( 'Settings', 'nexvio' ), 'dashicons-admin-generic', 'page_settings' ),
			self::DOCS     => array( __( 'Documentation', 'nexvio' ), 'dashicons-book-alt', 'page_docs' ),
		);
	}

	private static function menu_icon() {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="black" d="M7 6V5a3 3 0 0 1 6 0v1h2.2a1 1 0 0 1 1 .9l.8 10A1 1 0 0 1 16 18H4a1 1 0 0 1-1-1.1l.8-10a1 1 0 0 1 1-.9H7zm1.6 0h2.8V5a1.4 1.4 0 0 0-2.8 0v1zM7.5 10v4h5v-4h-5zm0 1.6h5v.8h-5v-.8z"/></svg>';
		return 'data:image/svg+xml;base64,' . base64_encode( $svg ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
	}

	public static function menu() {
		/* 55.4: just above the WooCommerce menu */
		add_menu_page(
			__( 'Nexvio', 'nexvio' ),
			__( 'Nexvio', 'nexvio' ),
			'manage_options',
			self::SLUG,
			array( __CLASS__, 'page_dashboard' ),
			self::menu_icon(),
			'55.4'
		);
		foreach ( self::pages() as $slug => $p ) {
			add_submenu_page( self::SLUG, $p[0], $p[0], 'manage_options', $slug, array( __CLASS__, $p[2] ) );
		}
	}

	private static function is_ours() {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		return array_key_exists( $page, self::pages() ) ? $page : '';
	}

	public static function body_class( $classes ) {
		return self::is_ours() ? $classes . ' nexvio-screen' : $classes;
	}

	public static function action_links( $links ) {
		array_unshift(
			$links,
			'<a href="' . esc_url( admin_url( 'admin.php?page=' . self::WIDGETS ) ) . '">' . esc_html__( 'Widgets', 'nexvio' ) . '</a>',
			'<a href="' . esc_url( admin_url( 'admin.php?page=' . self::DOCS ) ) . '">' . esc_html__( 'Docs', 'nexvio' ) . '</a>'
		);
		return $links;
	}

	public static function assets() {
		if ( ! self::is_ours() ) {
			return;
		}
		wp_enqueue_style( 'wp-color-picker' );
		if ( self::FILTERS === self::is_ours() ) {
			wp_enqueue_style( 'nexvio-filter-preview', NEXVIO_URL . 'assets/css/product-filter.css', array(), Nexvio_Plugin::ver( 'assets/css/product-filter.css' ) );
			wp_enqueue_script( 'nexvio-filters', NEXVIO_URL . 'admin/assets/filters.js', array( 'jquery', 'wp-color-picker' ), Nexvio_Plugin::ver( 'admin/assets/filters.js' ), true );
			wp_localize_script( 'nexvio-filters', 'NEXVIO_FILTERS', self::filters_data() );
		}
		if ( self::CART === self::is_ours() ) {
			wp_enqueue_style( 'nexvio-side-cart-preview', NEXVIO_URL . 'assets/css/side-cart.css', array(), Nexvio_Plugin::ver( 'assets/css/side-cart.css' ) );
		}
		wp_enqueue_style( 'nexvio-admin', NEXVIO_URL . 'admin/assets/admin.css', array( 'dashicons', 'wp-color-picker' ), Nexvio_Plugin::ver( 'admin/assets/admin.css' ) );
		wp_enqueue_script( 'nexvio-admin', NEXVIO_URL . 'admin/assets/admin.js', array( 'jquery', 'wp-color-picker' ), Nexvio_Plugin::ver( 'admin/assets/admin.js' ), true );
		wp_localize_script( 'nexvio-admin', 'NEXVIO_ADMIN', array(
			'ajax'  => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'nexvio_admin' ),
			'total' => count( Nexvio_Plugin::widgets() ),
			'i18n'  => array(
				'active'   => __( 'Active', 'nexvio' ),
				'inactive' => __( 'Inactive', 'nexvio' ),
				'saved'    => __( 'Saved', 'nexvio' ),
				'error'    => __( 'Could not save. Please reload the page and try again.', 'nexvio' ),
				'enabled'  => __( 'enabled', 'nexvio' ),
				'disabled' => __( 'disabled', 'nexvio' ),
				/* translators: 1: number of enabled widgets, 2: total number of widgets */
				'of'       => __( '%1$s of %2$s enabled', 'nexvio' ),
			),
		) );
	}

	/* ------------------------------------------------------------------
	 * App layout
	 * ------------------------------------------------------------------ */

	private static function open( $current, $title, $subtitle = '' ) {
		$missing = Nexvio_Plugin::instance()->missing_requirements();
		?>
		<div id="nexvio-app" class="nexvio-app">
			<aside class="nexvio-side">
				<a class="nexvio-brand" href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::SLUG ) ); ?>">
					<img src="<?php echo esc_url( NEXVIO_URL . 'admin/assets/img/logo-animated.gif?v=' . Nexvio_Plugin::ver( 'admin/assets/img/logo-animated.gif' ) ); ?>" width="46" height="46" alt="">
					<span><strong><?php esc_html_e( 'Nexvio', 'nexvio' ); ?></strong><em><?php esc_html_e( 'for WooCommerce', 'nexvio' ); ?></em></span>
				</a>
				<nav class="nexvio-nav" aria-label="<?php esc_attr_e( 'Plugin sections', 'nexvio' ); ?>">
					<?php foreach ( self::pages() as $slug => $p ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $slug ) ); ?>" class="<?php echo $slug === $current ? 'is-active' : ''; ?>"<?php echo $slug === $current ? ' aria-current="page"' : ''; ?>>
							<span class="dashicons <?php echo esc_attr( $p[1] ); ?>" aria-hidden="true"></span><?php echo esc_html( $p[0] ); ?>
						</a>
					<?php endforeach; ?>
				</nav>
				<div class="nexvio-side__foot">
					<div class="nexvio-plan">
						<span class="nexvio-plan__chip"><?php esc_html_e( 'Version', 'nexvio' ); ?> <?php echo esc_html( NEXVIO_VERSION ); ?></span>
						<p><?php esc_html_e( 'Built by Md Farhan Sadik. No extra plugins needed: wishlist, filter and search are built in.', 'nexvio' ); ?></p>
					</div>
					<a class="nexvio-side__link" href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::DOCS ) ); ?>"><span class="dashicons dashicons-sos" aria-hidden="true"></span><?php esc_html_e( 'Get help', 'nexvio' ); ?></a>
				</div>
			</aside>

			<main class="nexvio-main">
				<header class="nexvio-topbar">
					<div>
						<h1><?php echo esc_html( $title ); ?></h1>
						<?php if ( $subtitle ) : ?><p><?php echo esc_html( $subtitle ); ?></p><?php endif; ?>
					</div>
					<div class="nexvio-status">
						<?php foreach ( array( 'Elementor', 'WooCommerce' ) as $dep ) : $ok = ! in_array( $dep, $missing, true ); ?>
							<span class="nexvio-status__pill <?php echo $ok ? 'is-ok' : 'is-bad'; ?>"><i aria-hidden="true"></i><?php echo esc_html( $dep . ' ' . ( $ok ? __( 'connected', 'nexvio' ) : __( 'missing', 'nexvio' ) ) ); ?></span>
						<?php endforeach; ?>
					</div>
				</header>
				<div class="nexvio-content">
					<div class="nexvio-notices"><hr class="wp-header-end"></div>
		<?php
	}

	private static function close() {
		?>
				</div>
			</main>
			<div class="nexvio-toast" role="status" aria-live="polite"></div>
		</div>
		<?php
	}

	private static function counts() {
		$keys = array_keys( Nexvio_Plugin::widgets() );
		$off  = count( array_intersect( $keys, Nexvio_Plugin::disabled() ) );
		return array( 'all' => count( $keys ), 'active' => count( $keys ) - $off, 'inactive' => $off );
	}

	/* ------------------------------------------------------------------
	 * Dashboard (overview)
	 * ------------------------------------------------------------------ */

	public static function page_dashboard() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$c        = self::counts();
		$wl_page  = (int) get_option( 'nexvio_wishlist_page', 0 );
		$wl_ok    = $wl_page && 'publish' === get_post_status( $wl_page );
		$missing  = Nexvio_Plugin::instance()->missing_requirements();
		$steps    = array(
			array( empty( $missing ), __( 'Elementor and WooCommerce are active', 'nexvio' ), admin_url( 'plugins.php' ), __( 'Plugins', 'nexvio' ) ),
			array( $c['active'] > 0, __( 'Choose the widgets you need', 'nexvio' ), admin_url( 'admin.php?page=' . self::WIDGETS ), __( 'Widgets', 'nexvio' ) ),
			array( $wl_ok, __( 'Set the wishlist page', 'nexvio' ), admin_url( 'admin.php?page=' . self::WISHLIST ), __( 'Wishlist', 'nexvio' ) ),
			array( null, __( 'Add the widgets to your Elementor templates', 'nexvio' ), admin_url( 'admin.php?page=' . self::DOCS . '#setup' ), __( 'Guide', 'nexvio' ) ),
		);
		self::open( self::SLUG, __( 'Dashboard', 'nexvio' ), __( 'Everything you need to build your WooCommerce shop with Elementor.', 'nexvio' ) );
		?>
		<section class="nexvio-hero nexvio-hero--welcome">
			<div class="nexvio-hero__text">
				<h2><?php esc_html_e( 'Welcome to Nexvio', 'nexvio' ); ?></h2>
				<p><?php esc_html_e( 'Product sliders, an AJAX shop filter, header search, related products, add to cart and a built-in wishlist. All in one plugin, all editable in Elementor.', 'nexvio' ); ?></p>
				<div class="nexvio-hero__actions">
					<a class="nexvio-btn nexvio-btn--light" href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::WIDGETS ) ); ?>"><?php esc_html_e( 'Manage widgets', 'nexvio' ); ?></a>
					<a class="nexvio-btn nexvio-btn--glass" href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::DOCS ) ); ?>"><?php esc_html_e( 'Read the documentation', 'nexvio' ); ?></a>
				</div>
			</div>
			<img class="nexvio-hero__logo" src="<?php echo esc_url( NEXVIO_URL . 'admin/assets/img/logo-animated.gif?v=' . Nexvio_Plugin::ver( 'admin/assets/img/logo-animated.gif' ) ); ?>" width="96" height="96" alt="">
		</section>

		<div class="nexvio-stats-grid">
			<div class="nexvio-glass nexvio-kpi"><span class="dashicons dashicons-screenoptions" aria-hidden="true"></span><b><?php echo esc_html( $c['active'] . ' / ' . $c['all'] ); ?></b><small><?php esc_html_e( 'Widgets enabled', 'nexvio' ); ?></small></div>
			<div class="nexvio-glass nexvio-kpi"><span class="dashicons dashicons-heart" aria-hidden="true"></span><b><?php echo $wl_ok ? esc_html__( 'Ready', 'nexvio' ) : esc_html__( 'Not set', 'nexvio' ); ?></b><small><?php esc_html_e( 'Wishlist page', 'nexvio' ); ?></small></div>
			<div class="nexvio-glass nexvio-kpi"><span class="dashicons dashicons-randomize" aria-hidden="true"></span><b><?php echo 'yes' === get_option( 'nexvio_auto_related', 'no' ) ? esc_html__( 'Automatic', 'nexvio' ) : esc_html__( 'Widget', 'nexvio' ); ?></b><small><?php esc_html_e( 'Related products', 'nexvio' ); ?></small></div>
			<div class="nexvio-glass nexvio-kpi"><span class="dashicons dashicons-tag" aria-hidden="true"></span><b><?php echo esc_html( NEXVIO_VERSION ); ?></b><small><?php esc_html_e( 'Plugin version', 'nexvio' ); ?></small></div>
		</div>

		<section class="nexvio-glass nexvio-card-box">
			<div class="nexvio-box-head"><div><h2><?php esc_html_e( 'Quick setup', 'nexvio' ); ?></h2><p><?php esc_html_e( 'Four steps and your shop is ready.', 'nexvio' ); ?></p></div></div>
			<ol class="nexvio-steps">
				<?php foreach ( $steps as $i => $st ) : ?>
					<li class="<?php echo true === $st[0] ? 'is-done' : ''; ?>">
						<span class="nexvio-steps__n"><?php echo true === $st[0] ? '<span class="dashicons dashicons-yes" aria-hidden="true"></span>' : esc_html( $i + 1 ); ?></span>
						<span class="nexvio-steps__t"><?php echo esc_html( $st[1] ); ?></span>
						<a class="nexvio-btn nexvio-btn--ghost nexvio-btn--sm" href="<?php echo esc_url( $st[2] ); ?>"><?php echo esc_html( $st[3] ); ?></a>
					</li>
				<?php endforeach; ?>
			</ol>
		</section>
		<?php
		self::close();
	}

	/* ------------------------------------------------------------------
	 * Widgets
	 * ------------------------------------------------------------------ */

	public static function page_widgets() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$widgets  = Nexvio_Plugin::widgets();
		$disabled = Nexvio_Plugin::disabled();
		$c        = self::counts();
		self::open( self::WIDGETS, __( 'Widgets', 'nexvio' ), __( 'Turn on the widgets you need. Disabled widgets are not loaded in Elementor.', 'nexvio' ) );
		?>
		<section class="nexvio-hero nexvio-hero--search">
			<div class="nexvio-hero__inner">
				<h2><?php esc_html_e( 'Find a widget', 'nexvio' ); ?></h2>
				<p><?php esc_html_e( 'Search by name or feature, then filter by status.', 'nexvio' ); ?></p>
				<div class="nexvio-search">
					<span class="dashicons dashicons-search" aria-hidden="true"></span>
					<label class="screen-reader-text" for="nexvio-search"><?php esc_html_e( 'Search widgets', 'nexvio' ); ?></label>
					<input type="search" id="nexvio-search" placeholder="<?php esc_attr_e( 'Search by name, e.g. slider, wishlist, filter…', 'nexvio' ); ?>" autocomplete="off">
					<kbd>/</kbd>
				</div>
				<div class="nexvio-stats" role="group" aria-label="<?php esc_attr_e( 'Show', 'nexvio' ); ?>">
					<button type="button" class="nexvio-stat is-active" data-filter="all" aria-pressed="true"><span><?php esc_html_e( 'All', 'nexvio' ); ?></span><b data-count="all"><?php echo esc_html( $c['all'] ); ?></b></button>
					<button type="button" class="nexvio-stat nexvio-stat--on" data-filter="active" aria-pressed="false"><i aria-hidden="true"></i><span><?php esc_html_e( 'Active', 'nexvio' ); ?></span><b data-count="active"><?php echo esc_html( $c['active'] ); ?></b></button>
					<button type="button" class="nexvio-stat nexvio-stat--off" data-filter="inactive" aria-pressed="false"><i aria-hidden="true"></i><span><?php esc_html_e( 'Inactive', 'nexvio' ); ?></span><b data-count="inactive"><?php echo esc_html( $c['inactive'] ); ?></b></button>
				</div>
			</div>
		</section>

		<section class="nexvio-glass nexvio-card-box">
			<div class="nexvio-box-head">
				<div>
					<h2><?php esc_html_e( 'Elements', 'nexvio' ); ?></h2>
					<p><?php esc_html_e( 'Toggle the widgets you need. They appear in Elementor under "Nexvio".', 'nexvio' ); ?></p>
				</div>
				<div class="nexvio-box-head__side">
					<span class="nexvio-pill" id="nexvio-enabled">
						<?php
						/* translators: 1: number of enabled widgets, 2: total number of widgets */
						echo esc_html( sprintf( __( '%1$s of %2$s enabled', 'nexvio' ), $c['active'], $c['all'] ) );
						?>
					</span>
					<button type="button" class="nexvio-btn nexvio-btn--ghost nexvio-btn--sm" data-bulk="enable"><?php esc_html_e( 'Enable all', 'nexvio' ); ?></button>
					<button type="button" class="nexvio-btn nexvio-btn--ghost nexvio-btn--sm" data-bulk="disable"><?php esc_html_e( 'Disable all', 'nexvio' ); ?></button>
				</div>
			</div>

			<div class="nexvio-grid" id="nexvio-grid">
				<?php
				foreach ( $widgets as $key => $w ) :
					$on     = ! in_array( $key, $disabled, true );
					$search = strtolower( $w['title'] . ' ' . $w['desc'] . ' ' . $w['group'] );
					?>
					<article class="nexvio-card<?php echo $on ? '' : ' is-off'; ?>" data-key="<?php echo esc_attr( $key ); ?>" data-status="<?php echo $on ? 'active' : 'inactive'; ?>" data-search="<?php echo esc_attr( $search ); ?>" title="<?php echo esc_attr( $w['desc'] ); ?>">
						<span class="nexvio-card__icon dashicons <?php echo esc_attr( $w['icon'] ); ?>" aria-hidden="true"></span>
						<div class="nexvio-card__body">
							<h3 class="nexvio-card__title"><?php echo esc_html( $w['title'] ); ?></h3>
							<p class="nexvio-card__desc"><?php echo esc_html( $w['desc'] ); ?></p>
						</div>
						<label class="nexvio-switch">
							<input type="checkbox" data-key="<?php echo esc_attr( $key ); ?>"<?php checked( $on ); ?>>
							<span class="nexvio-switch__track" aria-hidden="true"></span>
							<span class="screen-reader-text">
								<?php
								/* translators: %s: widget name */
								echo esc_html( sprintf( __( 'Enable %s', 'nexvio' ), $w['title'] ) );
								?>
							</span>
						</label>
					</article>
				<?php endforeach; ?>
			</div>
			<p class="nexvio-empty" hidden><?php esc_html_e( 'No widget matches your search.', 'nexvio' ); ?></p>
		</section>
		<p class="nexvio-note"><?php esc_html_e( 'Pages that already use a disabled widget show nothing in its place until you enable it again.', 'nexvio' ); ?></p>
		<?php
		self::close();
	}


	/* ------------------------------------------------------------------
	 * Settings
	 * ------------------------------------------------------------------ */

	public static function page_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$page    = (int) get_option( 'nexvio_wishlist_page', 0 );
		$page_ok = $page && 'publish' === get_post_status( $page );
		$orderby = get_option( 'nexvio_related_orderby', 'popularity' );
		$layout  = get_option( 'nexvio_related_layout', 'slider' );
		self::open( self::SETTINGS, __( 'Settings', 'nexvio' ), __( 'Shop and related products options.', 'nexvio' ) );
		if ( isset( $_GET['updated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			echo '<div class="nexvio-alert nexvio-alert--ok"><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>' . esc_html__( 'Settings saved.', 'nexvio' ) . '</div>';
		}
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="nexvio-settings">
			<input type="hidden" name="action" value="nexvio_save_settings">
			<input type="hidden" name="nexvio_section" value="general">
			<?php wp_nonce_field( 'nexvio_save_settings' ); ?>

			<!-- Shop -->
			<section class="nexvio-glass nexvio-set">
				<header class="nexvio-set__head">
					<span class="nexvio-set__icon dashicons dashicons-store" aria-hidden="true"></span>
					<div>
						<h2><?php esc_html_e( 'Shop', 'nexvio' ); ?></h2>
						<p><?php esc_html_e( 'Options for the shop page and the category archives.', 'nexvio' ); ?></p>
					</div>
				</header>
				<div class="nexvio-row">
					<div class="nexvio-row__label">
						<label for="nexvio_per_page"><?php esc_html_e( 'Products per page', 'nexvio' ); ?></label>
						<p><?php esc_html_e( '0 keeps the value of your theme / WooCommerce.', 'nexvio' ); ?></p>
					</div>
					<div class="nexvio-row__control">
						<span class="nexvio-input-unit"><input type="number" min="0" max="200" id="nexvio_per_page" name="nexvio_per_page" value="<?php echo esc_attr( (int) get_option( 'nexvio_per_page', 0 ) ); ?>"><span><?php esc_html_e( 'products', 'nexvio' ); ?></span></span>
					</div>
				</div>
			</section>

			<!-- Related -->
			<section class="nexvio-glass nexvio-set">
				<header class="nexvio-set__head">
					<span class="nexvio-set__icon dashicons dashicons-randomize" aria-hidden="true"></span>
					<div>
						<h2><?php esc_html_e( 'Related products', 'nexvio' ); ?></h2>
						<p><?php esc_html_e( 'Products from the same category, shown on the product page.', 'nexvio' ); ?></p>
					</div>
				</header>
				<div class="nexvio-row">
					<div class="nexvio-row__label">
						<strong><?php esc_html_e( 'Show automatically', 'nexvio' ); ?></strong>
						<p><?php esc_html_e( 'Replaces the WooCommerce related products. Leave off if you use the "Related Products" widget in an Elementor template.', 'nexvio' ); ?></p>
					</div>
					<div class="nexvio-row__control">
						<label class="nexvio-toggle"><input type="checkbox" name="nexvio_auto_related" value="yes"<?php checked( 'yes', get_option( 'nexvio_auto_related', 'no' ) ); ?>> <?php esc_html_e( 'On product pages', 'nexvio' ); ?></label>
					</div>
				</div>
				<div class="nexvio-row">
					<div class="nexvio-row__label"><label for="nexvio_related_title"><?php esc_html_e( 'Title', 'nexvio' ); ?></label></div>
					<div class="nexvio-row__control"><input type="text" id="nexvio_related_title" name="nexvio_related_title" value="<?php echo esc_attr( get_option( 'nexvio_related_title', 'You may also like' ) ); ?>"></div>
				</div>
				<div class="nexvio-row">
					<div class="nexvio-row__label"><label for="nexvio_related_limit"><?php esc_html_e( 'Number of products', 'nexvio' ); ?></label></div>
					<div class="nexvio-row__control"><span class="nexvio-input-unit"><input type="number" min="1" max="40" id="nexvio_related_limit" name="nexvio_related_limit" value="<?php echo esc_attr( (int) get_option( 'nexvio_related_limit', 8 ) ); ?>"><span><?php esc_html_e( 'products', 'nexvio' ); ?></span></span></div>
				</div>
				<div class="nexvio-row">
					<div class="nexvio-row__label"><label for="nexvio_related_orderby"><?php esc_html_e( 'Order', 'nexvio' ); ?></label></div>
					<div class="nexvio-row__control">
						<select id="nexvio_related_orderby" name="nexvio_related_orderby">
							<?php foreach ( array( 'popularity' => __( 'Most popular (sales)', 'nexvio' ), 'rating' => __( 'Best rated', 'nexvio' ), 'rand' => __( 'Random', 'nexvio' ), 'date' => __( 'Newest', 'nexvio' ) ) as $k => $l ) : ?>
								<option value="<?php echo esc_attr( $k ); ?>"<?php selected( $orderby, $k ); ?>><?php echo esc_html( $l ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
				<div class="nexvio-row">
					<div class="nexvio-row__label"><label for="nexvio_related_layout"><?php esc_html_e( 'Layout', 'nexvio' ); ?></label></div>
					<div class="nexvio-row__control">
						<select id="nexvio_related_layout" name="nexvio_related_layout">
							<option value="slider"<?php selected( $layout, 'slider' ); ?>><?php esc_html_e( 'Slider', 'nexvio' ); ?></option>
							<option value="grid"<?php selected( $layout, 'grid' ); ?>><?php esc_html_e( 'Grid', 'nexvio' ); ?></option>
						</select>
					</div>
				</div>
			</section>

			<!-- Shortcodes -->
			<section class="nexvio-glass nexvio-set">
				<header class="nexvio-set__head">
					<span class="nexvio-set__icon dashicons dashicons-editor-code" aria-hidden="true"></span>
					<div>
						<h2><?php esc_html_e( 'Shortcodes', 'nexvio' ); ?></h2>
						<p><?php esc_html_e( 'Use them anywhere shortcodes are allowed.', 'nexvio' ); ?></p>
					</div>
				</header>
				<div class="nexvio-shortcodes">
					<div class="nexvio-shortcode"><code>[nexvio_related_products limit="8" layout="slider"]</code><span><?php esc_html_e( 'Related products on a product page', 'nexvio' ); ?></span></div>
					<div class="nexvio-shortcode"><code>[nexvio_wishlist_count]</code><span><?php esc_html_e( 'Live number of products in the wishlist', 'nexvio' ); ?></span></div>
				</div>
			</section>

			<div class="nexvio-glass nexvio-savebar">
				<span><?php esc_html_e( 'Changes apply to the whole site.', 'nexvio' ); ?></span>
				<button type="submit" class="nexvio-btn"><span class="dashicons dashicons-saved" aria-hidden="true"></span><?php esc_html_e( 'Save settings', 'nexvio' ); ?></button>
			</div>
		</form>
		<?php
		self::close();
	}


	/* ------------------------------------------------------------------
	 * Wishlist
	 * ------------------------------------------------------------------ */

	public static function page_wishlist() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$page    = (int) get_option( 'nexvio_wishlist_page', 0 );
		$page_ok = $page && 'publish' === get_post_status( $page );
		self::open( self::WISHLIST, __( 'Wishlist', 'nexvio' ), __( 'The built-in wishlist: no other plugin needed.', 'nexvio' ) );
		if ( isset( $_GET['updated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			echo '<div class="nexvio-alert nexvio-alert--ok"><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>' . esc_html__( 'Settings saved.', 'nexvio' ) . '</div>';
		}
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="nexvio-settings">
			<input type="hidden" name="action" value="nexvio_save_settings">
			<input type="hidden" name="nexvio_section" value="wishlist">
			<?php wp_nonce_field( 'nexvio_save_settings' ); ?>
			<!-- Wishlist -->
			<section class="nexvio-glass nexvio-set">
				<header class="nexvio-set__head">
					<span class="nexvio-set__icon dashicons dashicons-heart" aria-hidden="true"></span>
					<div>
						<h2><?php esc_html_e( 'Wishlist', 'nexvio' ); ?></h2>
						<p><?php esc_html_e( 'Built-in wishlist, no other plugin needed. Customers keep their list on every device; visitors keep it in their browser and get it back when they log in.', 'nexvio' ); ?></p>
					</div>
				</header>
				<div class="nexvio-row">
					<div class="nexvio-row__label">
						<label for="nexvio_wishlist_page"><?php esc_html_e( 'Wishlist page', 'nexvio' ); ?></label>
						<p><?php esc_html_e( 'Add the "Wishlist Grid" widget to this page with Elementor. The header heart and the "View my wishlist" link open it.', 'nexvio' ); ?></p>
					</div>
					<div class="nexvio-row__control">
						<?php
						$nexvio_dropdown = wp_dropdown_pages( array(
							'name'              => 'nexvio_wishlist_page',
							'id'                => 'nexvio_wishlist_page',
							'selected'          => absint( $page ),
							'show_option_none'  => esc_html__( '— Select a page —', 'nexvio' ),
							'option_none_value' => 0,
							'echo'              => 0,
						) );
						echo wp_kses( (string) $nexvio_dropdown, array(
							'select' => array( 'name' => true, 'id' => true, 'class' => true ),
							'option' => array( 'value' => true, 'selected' => true, 'class' => true ),
						) );
						?>
						<?php if ( $page_ok ) : ?>
							<a class="nexvio-btn nexvio-btn--ghost nexvio-btn--sm" href="<?php echo esc_url( get_permalink( $page ) ); ?>" target="_blank" rel="noopener"><span class="dashicons dashicons-external" aria-hidden="true"></span><?php esc_html_e( 'View', 'nexvio' ); ?></a>
							<span class="nexvio-tag nexvio-tag--ok"><?php esc_html_e( 'Ready', 'nexvio' ); ?></span>
						<?php else : ?>
							<a class="nexvio-btn nexvio-btn--sm" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=nexvio_create_wishlist_page' ), 'nexvio_create_wishlist_page' ) ); ?>"><span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span><?php esc_html_e( 'Create the page', 'nexvio' ); ?></a>
							<span class="nexvio-tag nexvio-tag--warn"><?php esc_html_e( 'Not set', 'nexvio' ); ?></span>
						<?php endif; ?>
					</div>
				</div>
				<div class="nexvio-row">
					<div class="nexvio-row__label">
						<strong><?php esc_html_e( 'Visitors without an account', 'nexvio' ); ?></strong>
						<p><?php esc_html_e( 'When off, visitors who click a heart are sent to the login page.', 'nexvio' ); ?></p>
					</div>
					<div class="nexvio-row__control">
						<label class="nexvio-toggle"><input type="checkbox" name="nexvio_wishlist_guests" value="yes"<?php checked( 'no' !== get_option( 'nexvio_wishlist_guests', 'yes' ) ); ?>> <?php esc_html_e( 'Visitors can use the wishlist', 'nexvio' ); ?></label>
					</div>
				</div>
				<div class="nexvio-row">
					<div class="nexvio-row__label">
						<label for="nexvio_wishlist_days"><?php esc_html_e( 'Keep a visitor\'s wishlist for', 'nexvio' ); ?></label>
						<p><?php esc_html_e( 'How long a visitor\'s list stays in their browser.', 'nexvio' ); ?></p>
					</div>
					<div class="nexvio-row__control">
						<span class="nexvio-input-unit"><input type="number" min="1" max="730" id="nexvio_wishlist_days" name="nexvio_wishlist_days" value="<?php echo esc_attr( (int) get_option( 'nexvio_wishlist_days', 60 ) ); ?>"><span><?php esc_html_e( 'days', 'nexvio' ); ?></span></span>
					</div>
				</div>
			</section>

			<div class="nexvio-glass nexvio-savebar">
				<span><?php esc_html_e( 'Widgets: Wishlist Icon (header) and Wishlist Grid (wishlist page).', 'nexvio' ); ?></span>
				<button type="submit" class="nexvio-btn"><span class="dashicons dashicons-saved" aria-hidden="true"></span><?php esc_html_e( 'Save settings', 'nexvio' ); ?></button>
			</div>
		</form>
		<?php
		self::close();
	}


	/* ------------------------------------------------------------------
	 * Filters (fields of the Product Filter widget)
	 * ------------------------------------------------------------------ */

	/** Everything the builder needs: fields, types, attributes with sample values, sample categories. */
	public static function filters_data() {
		$types = array();
		foreach ( Nexvio_Filter_Config::types() as $k => $t ) {
			$types[ $k ] = array( 'label' => $t[0], 'icon' => $t[1], 'single' => $t[2], 'looks' => $t[3], 'desc' => $t[4] );
		}
		$attrs = array();
		foreach ( Nexvio_Filter_Config::attributes() as $tax => $a ) {
			$terms = taxonomy_exists( $tax ) ? get_terms( array( 'taxonomy' => $tax, 'hide_empty' => false, 'number' => 8 ) ) : array();
			$vals  = array();
			foreach ( is_wp_error( $terms ) ? array() : (array) $terms as $t ) {
				$vals[] = array(
					'name'  => $t->name,
					'color' => class_exists( 'Nexvio_Swatches' ) ? Nexvio_Swatches::color_for( $t->name, (int) $t->term_id ) : '',
					'image' => class_exists( 'Nexvio_Swatches' ) ? Nexvio_Swatches::image_for( (int) $t->term_id ) : '',
					'count' => (int) $t->count,
				);
			}
			$attrs[] = array( 'taxonomy' => $tax, 'label' => $a[0], 'guess' => $a[1], 'terms' => $vals );
		}
		$cats  = array();
		$terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false, 'parent' => 0, 'number' => 6 ) );
		foreach ( is_wp_error( $terms ) ? array() : (array) $terms as $t ) {
			$kids = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false, 'parent' => (int) $t->term_id, 'number' => 4 ) );
			$cats[] = array( 'name' => $t->name, 'count' => (int) $t->count, 'children' => is_wp_error( $kids ) ? array() : array_map( function ( $k ) {
				return array( 'name' => $k->name, 'count' => (int) $k->count );
			}, (array) $kids ) );
		}
		$tags = get_terms( array( 'taxonomy' => 'product_tag', 'hide_empty' => false, 'number' => 6 ) );
		return array(
			'fields'     => Nexvio_Filter_Config::fields(),
			'types'      => $types,
			'attributes' => $attrs,
			'categories' => $cats,
			'tags'       => is_wp_error( $tags ) ? array() : wp_list_pluck( (array) $tags, 'name' ),
			'currency'   => function_exists( 'get_woocommerce_currency_symbol' ) ? html_entity_decode( get_woocommerce_currency_symbol() ) : '€',
			'attrUrl'    => admin_url( 'edit.php?post_type=product&page=product_attributes' ),
			'i18n'       => array(
				'look'       => __( 'How it looks', 'nexvio' ),
				'name'       => __( 'Name shown above the field', 'nexvio' ),
				'up'         => __( 'Move up', 'nexvio' ),
				'down'       => __( 'Move down', 'nexvio' ),
				'remove'     => __( 'Remove', 'nexvio' ),
				'more'       => __( 'Options', 'nexvio' ),
				'open'       => __( 'Open when the page loads', 'nexvio' ),
				'counts'     => __( 'Show the number of products', 'nexvio' ),
				'hideEmpty'  => __( 'Hide values without products', 'nexvio' ),
				'collapse'   => __( 'Sub-categories hidden until + is clicked', 'nexvio' ),
				'all'        => __( 'Show "All categories"', 'nexvio' ),
				'limit'      => __( '"Show more" after', 'nexvio' ),
				'placeholder'=> __( 'Text inside the search box', 'nexvio' ),
				'text'       => __( 'Checkbox text', 'nexvio' ),
				'logic'      => __( 'With several values chosen, show products that have', 'nexvio' ),
				'any'        => __( 'any of them', 'nexvio' ),
				'every'      => __( 'all of them', 'nexvio' ),
				'showOut'    => __( 'Also offer "Sold out"', 'nexvio' ),
				'added'      => __( 'Already added', 'nexvio' ),
				'attrs'      => __( 'Attributes', 'nexvio' ),
				'other'      => __( 'Other fields', 'nexvio' ),
				'noAttrs'    => __( 'No product attributes yet. Create Colour, Size… in Products > Attributes.', 'nexvio' ),
				'empty'      => __( 'No fields yet. Click "Add a filter field" to start.', 'nexvio' ),
				'colour'     => __( 'Colour', 'nexvio' ),
				'size'       => __( 'Size', 'nexvio' ),
				'searchPh'   => __( 'Search products…', 'nexvio' ),
				'allCats'    => __( 'All categories', 'nexvio' ),
				'inStock'    => __( 'In stock', 'nexvio' ),
				'preorder'   => __( 'Pre-order', 'nexvio' ),
				'soldOut'    => __( 'Sold out', 'nexvio' ),
				'onSale'     => __( 'On sale', 'nexvio' ),
				'featured'   => __( 'Featured', 'nexvio' ),
				/* translators: %d: number of stars */
				'starsUp'    => __( '%d stars & up', 'nexvio' ),
				'items'      => __( 'items', 'nexvio' ),
				'perValues'  => __( 'Numbers to offer (comma separated)', 'nexvio' ),
				'defaultTxt' => __( 'Default', 'nexvio' ),
				'sorts'      => array( __( 'Default sorting', 'nexvio' ), __( 'Popularity', 'nexvio' ), __( 'Average rating', 'nexvio' ), __( 'Newest', 'nexvio' ), __( 'Price: low to high', 'nexvio' ), __( 'Price: high to low', 'nexvio' ) ),
				'image'      => __( 'Image', 'nexvio' ),
				'noAttrsHelp'=> __( 'Only global attributes can be filtered: create them in Products > Attributes (Colour, Size…), add their values there, then choose them on your products.', 'nexvio' ),
				'newAttr'    => __( 'Create an attribute', 'nexvio' ),
			),
		);
	}

	public static function page_filters() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		self::open( self::FILTERS, __( 'Filters', 'nexvio' ), __( 'Choose the fields of the Product Filter widget: what to filter by, how each field looks and in which order.', 'nexvio' ) );
		if ( isset( $_GET['updated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			echo '<div class="nexvio-alert nexvio-alert--ok"><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>' . esc_html__( 'Filters saved. Every Product Filter widget now uses them.', 'nexvio' ) . '</div>';
		}
		?>
		<div class="nexvio-cart-layout">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="nexvio-settings" id="nexvio-fb-form">
				<input type="hidden" name="action" value="nexvio_save_filters">
				<input type="hidden" name="nexvio_filters_json" id="nexvio-fb-json" value="">
				<?php wp_nonce_field( 'nexvio_save_filters' ); ?>
				<ol class="nexvio-howto">
					<li><b>1</b><span><?php esc_html_e( 'Add a filter field', 'nexvio' ); ?></span></li>
					<li><b>2</b><span><?php esc_html_e( 'Choose how it looks', 'nexvio' ); ?></span></li>
					<li><b>3</b><span><?php esc_html_e( 'Drag or use the arrows to order, then Save', 'nexvio' ); ?></span></li>
				</ol>
				<?php $fst = Nexvio_Filter_Config::style(); ?>
				<section class="nexvio-glass nexvio-set" id="nexvio-fb-look">
					<header class="nexvio-set__head">
						<span class="nexvio-set__icon dashicons dashicons-art" aria-hidden="true"></span>
						<div>
							<h2><?php esc_html_e( 'Look', 'nexvio' ); ?></h2>
							<p><?php esc_html_e( 'Choose Light or Dark, then pick the main colour (checkboxes, slider, selected buttons, Apply button). Every Product Filter uses it; a colour set in a widget\'s Style tab replaces it for that widget only.', 'nexvio' ); ?></p>
						</div>
					</header>
					<?php
					self::cart_field( 'preset', array( 'style', __( 'Style', 'nexvio' ), 'dark', array( 'options' => array( 'light' => __( 'Light', 'nexvio' ), 'dark' => __( 'Dark', 'nexvio' ) ) ) ), $fst['preset'], 'nexvio_filter_style' );
					self::cart_field( 'main_color', array( 'color', __( 'Main colour', 'nexvio' ), '#3d8bff', array( 'help' => __( 'Text on it turns black or white automatically so it stays readable.', 'nexvio' ) ) ), $fst['main_color'], 'nexvio_filter_style' );
					?>
				</section>
				<section class="nexvio-glass nexvio-set">
					<header class="nexvio-set__head">
						<span class="nexvio-set__icon dashicons dashicons-filter" aria-hidden="true"></span>
						<div>
							<h2><?php esc_html_e( 'Filter fields', 'nexvio' ); ?></h2>
							<p><?php esc_html_e( 'They appear in every Product Filter widget, in this order. Colours and sizes of the filter itself are set in Elementor (widget > Style).', 'nexvio' ); ?></p>
						</div>
					</header>
					<ul class="nexvio-fb__list" id="nexvio-fb-list" aria-live="polite"></ul>
					<div class="nexvio-fb__add">
						<button type="button" class="nexvio-btn" id="nexvio-fb-add" aria-expanded="false" aria-controls="nexvio-fb-palette"><span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span><?php esc_html_e( 'Add a filter field', 'nexvio' ); ?></button>
					</div>
					<div class="nexvio-fb__palette" id="nexvio-fb-palette" hidden></div>
				</section>
				<div class="nexvio-glass nexvio-savebar">
					<span><?php esc_html_e( 'Tip: colour attributes show as colour circles, sizes as buttons. Set the colour of each value in Products > Attributes > Configure terms.', 'nexvio' ); ?></span>
					<button type="submit" class="nexvio-btn"><span class="dashicons dashicons-saved" aria-hidden="true"></span><?php esc_html_e( 'Save filters', 'nexvio' ); ?></button>
				</div>
			</form>
			<aside class="nexvio-preview" aria-label="<?php esc_attr_e( 'Live preview', 'nexvio' ); ?>">
				<div class="nexvio-preview__label"><span class="dashicons dashicons-visibility" aria-hidden="true"></span><?php esc_html_e( 'Live preview', 'nexvio' ); ?></div>
				<div class="nexvio-pf nexvio-pf--<?php echo esc_attr( Nexvio_Filter_Config::style()['preset'] ); ?> nexvio-pf--collapsible nexvio-fb-preview" id="nexvio-fb-preview"></div>
				<p class="nexvio-preview__note"><?php esc_html_e( 'Your attributes and categories, shown with the default filter style.', 'nexvio' ); ?></p>
			</aside>
		</div>
		<?php
		self::close();
	}

	/** sanitize_text_field() for strings only, so numbers and booleans from JSON keep their type. */
	public static function clean_scalar( $value ) {
		return is_string( $value ) ? sanitize_textarea_field( $value ) : $value;
	}

	public static function save_filters() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'nexvio' ) );
		}
		check_admin_referer( 'nexvio_save_filters' );
		$raw  = isset( $_POST['nexvio_filters_json'] ) && is_string( $_POST['nexvio_filters_json'] ) ? sanitize_textarea_field( wp_unslash( $_POST['nexvio_filters_json'] ) ) : '[]';
		$list = json_decode( $raw, true );
		$list = is_array( $list ) ? map_deep( $list, array( __CLASS__, 'clean_scalar' ) ) : array();
		update_option( Nexvio_Filter_Config::OPTION, Nexvio_Filter_Config::sanitize( $list ) );
		$style = isset( $_POST['nexvio_filter_style'] ) && is_array( $_POST['nexvio_filter_style'] ) ? map_deep( wp_unslash( $_POST['nexvio_filter_style'] ), 'sanitize_text_field' ) : array();
		update_option( Nexvio_Filter_Config::STYLE_OPTION, Nexvio_Filter_Config::sanitize_style( $style ) );
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::FILTERS . '&updated=1' ) );
		exit;
	}

	/* ------------------------------------------------------------------
	 * Add to Cart (side cart, floating cart) with a live preview
	 * ------------------------------------------------------------------ */

	public static function page_cart() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$values = Nexvio_Cart_Settings::all();
		$main   = sanitize_hex_color( (string) $values['main_color'] );
		$main   = $main ? $main : '#b08d57';
		$style  = 'dark' === $values['preset'] ? 'dark' : 'light';
		self::open( self::CART, __( 'Add to Cart', 'nexvio' ), __( 'Side cart, floating cart and header cart: behaviour, look and texts.', 'nexvio' ) );
		if ( isset( $_GET['updated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			echo '<div class="nexvio-alert nexvio-alert--ok"><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>' . esc_html__( 'Settings saved.', 'nexvio' ) . '</div>';
		}
		?>
		<div class="nexvio-cart-layout">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="nexvio-settings" id="nexvio-cart-form">
				<input type="hidden" name="action" value="nexvio_save_cart_settings">
				<?php wp_nonce_field( 'nexvio_save_cart_settings' ); ?>
				<ol class="nexvio-howto">
					<li><b>1</b><span><?php esc_html_e( 'Choose Light or Dark', 'nexvio' ); ?></span></li>
					<li><b>2</b><span><?php esc_html_e( 'Pick your main colour', 'nexvio' ); ?></span></li>
					<li><b>3</b><span><?php esc_html_e( 'Check the preview, then Save', 'nexvio' ); ?></span></li>
				</ol>
				<?php foreach ( array( 'look', 'general', 'floating', 'texts' ) as $group_key ) : $group = Nexvio_Cart_Settings::schema()[ $group_key ]; ?>
					<section class="nexvio-glass nexvio-set" id="nexvio-cart-<?php echo esc_attr( $group_key ); ?>">
						<header class="nexvio-set__head">
							<span class="nexvio-set__icon dashicons <?php echo esc_attr( $group[1] ); ?>" aria-hidden="true"></span>
							<div>
								<h2><?php echo esc_html( $group[0] ); ?></h2>
								<?php if ( $group[2] ) : ?><p><?php echo esc_html( $group[2] ); ?></p><?php endif; ?>
							</div>
						</header>
						<?php
						foreach ( $group[3] as $key => $f ) {
							self::cart_field( $key, $f, $values[ $key ] );
						}
						?>
					</section>
				<?php endforeach; ?>
				<div class="nexvio-glass nexvio-savebar">
					<span><?php esc_html_e( 'Elementor widgets can still change their own size and position.', 'nexvio' ); ?></span>
					<button type="submit" class="nexvio-btn"><span class="dashicons dashicons-saved" aria-hidden="true"></span><?php esc_html_e( 'Save settings', 'nexvio' ); ?></button>
				</div>
			</form>

			<aside class="nexvio-preview" aria-label="<?php esc_attr_e( 'Live preview', 'nexvio' ); ?>">
				<div class="nexvio-preview__label"><span class="dashicons dashicons-visibility" aria-hidden="true"></span><?php esc_html_e( 'Live preview', 'nexvio' ); ?></div>
				<?php self::cart_preview( $style, $main ); ?>
				<p class="nexvio-preview__note"><?php esc_html_e( 'Sample products. Your shop shows the real cart.', 'nexvio' ); ?></p>
			</aside>
		</div>
		<?php
		self::close();
	}

	/** Static copy of the side cart, styled by the same CSS as the shop. */
	private static function cart_preview( $style, $main ) {
		$img = function ( $c1, $c2 ) {
			return 'data:image/svg+xml;base64,' . base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" width="80" height="100"><defs><linearGradient id="g" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="' . $c1 . '"/><stop offset="1" stop-color="' . $c2 . '"/></linearGradient></defs><rect width="80" height="100" fill="url(#g)"/><path d="M28 30l8-6h8l8 6 6 10-6 3v30H28V43l-6-3z" fill="#fff" opacity=".85"/></svg>' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- inline sample image.
		};
		$t   = function ( $key ) {
			return Nexvio_Cart_Settings::text( $key );
		};
		?>
		<div class="nexvio-sc nexvio-sc--<?php echo esc_attr( $style ); ?> nexvio-sc--preview" id="nexvio-preview-cart" style="--wsc-mc:<?php echo esc_attr( $main ); ?>;--wsc-mc-text:<?php echo esc_attr( Nexvio_Cart_Settings::contrast( $main ) ); ?>">
			<div class="nexvio-sc__panel">
				<div class="nexvio-sc__inner">
					<header class="nexvio-sc__head">
						<div class="nexvio-sc__heading">
							<h2 class="nexvio-sc__title"><span data-preview="title"><?php echo esc_html( $t( 'title' ) ); ?></span> <span class="nexvio-sc__badge">3</span></h2>
							<p class="nexvio-sc__sub" data-preview="subtitle"><?php echo esc_html( $t( 'subtitle' ) ); ?></p>
						</div>
						<span class="nexvio-sc__close" aria-hidden="true"><?php echo wp_kses( Nexvio_Side_Cart::icon( 'close' ), Nexvio_Plugin::allowed_html() ); ?></span>
					</header>
					<div class="nexvio-sc__body">
						<ul class="nexvio-sc__items">
							<?php foreach ( array( array( 'Silk Midi Dress', 'Emerald · S', '€150.00', '€120.00', '€120.00', 1, '#2e8b57', '#14532d' ), array( 'Linen Shirt', 'Navy · M', '', '€65.00', '€130.00', 2, '#4a6fa5', '#1e3a8a' ) ) as $it ) : ?>
								<li class="nexvio-sc__item">
									<span class="nexvio-sc__thumb"><img src="<?php echo esc_attr( $img( $it[6], $it[7] ) ); ?>" alt=""></span>
									<div class="nexvio-sc__info">
										<div class="nexvio-sc__top"><span class="nexvio-sc__name"><?php echo esc_html( $it[0] ); ?></span><span class="nexvio-sc__line"><?php echo esc_html( $it[4] ); ?></span></div>
										<div class="nexvio-sc__meta"><span class="nexvio-sc__var"><?php echo esc_html( $it[1] ); ?></span><span class="nexvio-sc__unit"><?php echo $it[2] ? '<del>' . esc_html( $it[2] ) . '</del> ' : ''; ?><?php echo esc_html( $it[3] ); ?></span></div>
										<div class="nexvio-sc__bottom">
											<span class="nexvio-sc__qty"><button type="button" tabindex="-1">−</button><input type="number" value="<?php echo (int) $it[5]; ?>" tabindex="-1" readonly aria-hidden="true"><button type="button" tabindex="-1">+</button></span>
											<span class="nexvio-sc__remove"><?php echo wp_kses( Nexvio_Side_Cart::icon( 'trash' ), Nexvio_Plugin::allowed_html() ); ?><span><?php echo esc_html( Nexvio_Texts::get( 'remove' ) ); ?></span></span>
										</div>
									</div>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
					<footer class="nexvio-sc__foot">
						<div class="nexvio-sc__coupon" data-preview-show="coupon"><span class="nexvio-sc__coupon-fake"><?php echo wp_kses( Nexvio_Side_Cart::icon( 'tag' ), Nexvio_Plugin::allowed_html() ); ?><?php echo esc_html( Nexvio_Texts::get( 'coupon_link' ) ); ?></span></div>
						<dl class="nexvio-sc__totals">
							<div class="nexvio-sc__row"><dt><?php echo esc_html( Nexvio_Texts::get( 'subtotal' ) ); ?></dt><dd>€250.00</dd></div>
							<div class="nexvio-sc__row nexvio-sc__row--total"><dt><?php echo esc_html( Nexvio_Texts::get( 'total' ) ); ?></dt><dd>€250.00</dd></div>
						</dl>
						<div class="nexvio-sc__actions">
							<span class="nexvio-sc__btn nexvio-sc__btn--primary"><span data-preview="checkout_label"><?php echo esc_html( $t( 'checkout_label' ) ); ?></span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
							<span class="nexvio-sc__link" data-preview="view_label" data-preview-show="view_cart"><?php echo esc_html( $t( 'view_label' ) ); ?></span>
						</div>
					</footer>
				</div>
			</div>
			<span class="nexvio-fc nexvio-fc--<?php echo esc_attr( $style ); ?> nexvio-fc--preview" id="nexvio-preview-fc" style="--wsc-mc:<?php echo esc_attr( $main ); ?>;--wsc-mc-text:<?php echo esc_attr( Nexvio_Cart_Settings::contrast( $main ) ); ?>" aria-hidden="true">
				<span class="nexvio-fc__ring"></span><span class="nexvio-fc__icon"><?php echo wp_kses( Nexvio_Side_Cart::icon( 'bag' ), Nexvio_Plugin::allowed_html() ); ?></span><span class="nexvio-fc__count">3</span>
			</span>
		</div>
		<?php
	}

	private static function cart_field( $key, $f, $value, $prefix = 'nexvio_cart' ) {
		list( $type, $label ) = $f;
		$extra = isset( $f[3] ) ? $f[3] : array();
		$id    = str_replace( '_', '-', $prefix ) . '-' . $key;
		$name  = $prefix . '[' . $key . ']';
		echo '<div class="nexvio-row"><div class="nexvio-row__label">';
		echo 'toggle' === $type || 'checks' === $type || 'pages' === $type ? '<strong>' . esc_html( $label ) . '</strong>' : '<label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label>';
		if ( ! empty( $extra['help'] ) ) {
			echo '<p>' . esc_html( $extra['help'] ) . '</p>';
		}
		echo '</div><div class="nexvio-row__control">';
		switch ( $type ) {
			case 'toggle':
				echo '<label class="nexvio-toggle"><input type="checkbox" name="' . esc_attr( $name ) . '" value="yes"' . checked( 'yes', $value, false ) . ' data-preview-toggle="' . esc_attr( $key ) . '"> <span class="screen-reader-text">' . esc_html( $label ) . '</span></label>';
				break;
			case 'select':
				echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '">';
				foreach ( $extra['options'] as $k => $l ) {
					echo '<option value="' . esc_attr( $k ) . '"' . selected( $value, $k, false ) . '>' . esc_html( $l ) . '</option>';
				}
				echo '</select>';
				break;
			case 'style':
				echo '<div class="nexvio-styles" role="radiogroup" aria-label="' . esc_attr( $label ) . '">';
				foreach ( $extra['options'] as $k => $l ) {
					echo '<label class="nexvio-style nexvio-style--' . esc_attr( $k ) . '"><input type="radio" name="' . esc_attr( $name ) . '" value="' . esc_attr( $k ) . '"' . checked( $value, $k, false ) . '>'
						. '<span class="nexvio-style__card"><span class="nexvio-style__mini" aria-hidden="true"><i></i><i></i><i></i><b></b></span><span class="nexvio-style__name">' . esc_html( $l ) . '</span></span></label>';
				}
				echo '</div>';
				break;
			case 'color':
				echo '<div class="nexvio-colour">';
				echo '<input type="text" id="' . esc_attr( $id ) . '" class="nexvio-color" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" data-default-color="' . esc_attr( $f[2] ) . '">';
				echo '<div class="nexvio-swatches" aria-label="' . esc_attr__( 'Ready-made colours', 'nexvio' ) . '">';
				foreach ( array( '#b08d57' => __( 'Gold', 'nexvio' ), '#111111' => __( 'Black', 'nexvio' ), '#0f5132' => __( 'Emerald', 'nexvio' ), '#1e3a8a' => __( 'Navy', 'nexvio' ), '#9f1239' => __( 'Burgundy', 'nexvio' ), '#c2410c' => __( 'Terracotta', 'nexvio' ), '#d8b4a0' => __( 'Blush', 'nexvio' ), '#6d28d9' => __( 'Violet', 'nexvio' ) ) as $hex => $nm ) {
					echo '<button type="button" class="nexvio-swatch" data-colour="' . esc_attr( $hex ) . '" style="--c:' . esc_attr( $hex ) . '" title="' . esc_attr( $nm ) . '" aria-label="' . esc_attr( $nm ) . '"></button>';
				}
				echo '</div></div>';
				break;
			case 'number':
				echo '<span class="nexvio-input-unit"><input type="number" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" min="' . esc_attr( isset( $extra['min'] ) ? $extra['min'] : 0 ) . '" max="' . esc_attr( isset( $extra['max'] ) ? $extra['max'] : 9999 ) . '"><span>' . esc_html( isset( $extra['unit'] ) ? $extra['unit'] : '' ) . '</span></span>';
				break;
			case 'page':
				$dd = wp_dropdown_pages( array(
					'name'              => esc_attr( $name ),
					'id'                => esc_attr( $id ),
					'selected'          => absint( $value ),
					'show_option_none'  => esc_html__( 'WooCommerce default', 'nexvio' ),
					'option_none_value' => 0,
					'echo'              => 0,
				) );
				echo wp_kses( (string) $dd, array( 'select' => array( 'name' => true, 'id' => true, 'class' => true ), 'option' => array( 'value' => true, 'selected' => true, 'class' => true ) ) );
				break;
			case 'pages':
				$pages = get_pages( array( 'sort_column' => 'post_title' ) );
				echo '<div class="nexvio-checklist">';
				foreach ( (array) $pages as $pg ) {
					echo '<label><input type="checkbox" name="' . esc_attr( $name ) . '[]" value="' . (int) $pg->ID . '"' . checked( in_array( (int) $pg->ID, array_map( 'intval', (array) $value ), true ), true, false ) . '> ' . esc_html( $pg->post_title ? $pg->post_title : '#' . $pg->ID ) . '</label>';
				}
				echo '</div>';
				break;
			case 'checks':
				foreach ( $extra['options'] as $k => $l ) {
					echo '<label class="nexvio-check"><input type="checkbox" name="' . esc_attr( $name ) . '[]" value="' . esc_attr( $k ) . '"' . checked( in_array( $k, (array) $value, true ), true, false ) . '> ' . esc_html( $l ) . '</label>';
				}
				break;
			default:
				$auto = isset( $extra['auto'] ) ? Nexvio_Texts::get( $extra['auto'] ) : '';
				echo '<input type="text" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '"' . ( $auto ? ' placeholder="' . esc_attr( $auto ) . '" data-auto="' . esc_attr( $auto ) . '"' : '' ) . ' data-preview-input="' . esc_attr( $key ) . '">';
				if ( $auto ) {
					echo '<span class="nexvio-auto-tag">' . esc_html__( 'Automatic when empty', 'nexvio' ) . '</span>';
				}
		}
		echo '</div></div>';
	}

	public static function save_cart_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'nexvio' ) );
		}
		check_admin_referer( 'nexvio_save_cart_settings' );
		$input = isset( $_POST['nexvio_cart'] ) && is_array( $_POST['nexvio_cart'] ) ? map_deep( wp_unslash( $_POST['nexvio_cart'] ), 'sanitize_text_field' ) : array();
		update_option( Nexvio_Cart_Settings::OPTION, Nexvio_Cart_Settings::sanitize( $input ) );
		Nexvio_Cart_Settings::flush();
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::CART . '&updated=1' ) );
		exit;
	}

	/* ------------------------------------------------------------------
	 * Documentation
	 * ------------------------------------------------------------------ */

	private static function doc_sections() {
		$w = admin_url( 'admin.php?page=' . self::WIDGETS );
		$s = admin_url( 'admin.php?page=' . self::SETTINGS );
		return array(
			'setup' => array( 'dashicons-flag', 'Getting started', '
				<p>Nexvio adds WooCommerce widgets to Elementor. They are listed in the Elementor panel under <strong>Nexvio</strong>, right after the Pro category.</p>
				<ol>
					<li><strong>Requirements:</strong> WordPress 5.8+, PHP 7.4+, WooCommerce and Elementor (the free version is enough; Elementor Pro is needed only for Theme Builder templates).</li>
					<li><strong>Choose your widgets</strong> in <a href="' . esc_url( $w ) . '">Widgets</a>. Disabled widgets are removed from the Elementor panel.</li>
					<li><strong>Set the wishlist page</strong> in <a href="' . esc_url( admin_url( 'admin.php?page=' . self::WISHLIST ) ) . '">Wishlist</a> (click "Create the page"), then edit it with Elementor and add <em>Wishlist Grid</em>.</li>
					<li><strong>Build your templates:</strong>
						<ul>
							<li>Header: <em>Header Search</em> + <em>Wishlist Icon</em></li>
							<li>Products archive (shop and categories): <em>Product Filter</em> in a sidebar + <em>Product Grid</em> with source "Current query"</li>
							<li>Single product: <em>Add to Cart</em> + <em>Related Products</em></li>
							<li>Home page: <em>Product Slider</em></li>
						</ul>
					</li>
				</ol>' ),
			'slider' => array( 'dashicons-images-alt2', 'Product Slider', '
				<p>A carousel of products with the card design (badges, wishlist heart, stock, price, add to cart).</p>
				<ul>
					<li><strong>Products:</strong> latest, featured, on sale, best sellers or chosen IDs; filter by categories and tags.</li>
					<li><strong>Slider:</strong> cards visible per device, peek of the next card, arrows (header or sides), dots, loop, mouse drag, autoplay.</li>
					<li><strong>Badges:</strong> language (product attribute), category, new, sale (percentage), pre-order (tag <code>precommande</code> or backorder).</li>
					<li><strong>Background by category:</strong> give each category its own colours.</li>
				</ul>
				<p><strong>Design Layout</strong> (Content tab, first section): pick one of 12 card designs from preview images: Classic Vault, Minimal Light, Image Overlay, Horizontal, Glass, Neon Outline, Elevated Shop, Compact Tiles, Editorial, Price Tag, Spotlight and Boutique. The same picker is in Products Grid, Products Archive, Related Products and Wishlist Grid. The colours, image shape and effects of the chosen design are in <em>Style &gt; Layout design</em>; only the controls that apply to that design are shown. You can also choose an icon or text add-to-cart button and show star ratings.</p>
				<p><strong>Motion</strong> (Slider section): <em>Slide</em> works with arrows, swipe and optional autoplay. <em>Continuous infinite scroll</em> moves the products at a steady speed in an endless loop with no visible restart; set the speed per device, the direction and pause on hover. Arrows and drag / swipe still work in this mode.</p>
				<p><strong>Tips:</strong> the Horizontal design looks best with 2 or 3 visible cards, Compact Tiles with 5 or 6. Glass is made for colourful or image backgrounds.</p>' ),
			'grid' => array( 'dashicons-grid-view', 'Products Archive', '
				<p>The product grid for the shop page and the category / tag / attribute archives, with pagination, result count and sorting.</p>
				<ul>
					<li>Use the source <strong>Current query</strong> in your archive template: the grid shows the products of the page being viewed and works with the Product Filter.</li>
					<li>Products per page: <a href="' . esc_url( $s ) . '">Settings</a> &gt; Shop.</li>
					<li><strong>Design Layout</strong> (Content tab): the same 12 designs as the Product Slider, with their own settings in <em>Style &gt; Layout design</em>.</li>
					<li><strong>View Mode</strong> (Content tab): <em>Grid View</em> (cards in rows and columns, columns per device in <em>Grid layout</em>) or <em>List View</em> (image and details side by side). List view has its own settings: image width and shape, price and button in a right column or below, vertical and text alignment, short description, stacking on mobile, and its own spacing, colours and typography in <em>Style &gt; List view</em>.</li>
					<li>Turn on <em>Let shoppers switch Grid / List</em> to show two buttons above the products; the choice can be remembered and stays when the filter reloads the products.</li>
				</ul>' ),
			'products-grid' => array( 'dashicons-screenoptions', 'Products Grid', '
				<p>A separate widget for a grid of <strong>chosen</strong> products on any page: latest, featured, on sale, best sellers, categories, tags or product IDs.</p>
				<ul>
					<li>Its own <strong>Design Layout</strong>, columns, gaps and pagination, independent from the Products Archive.</li>
					<li>Each Products Grid uses its own page number in the URL, so several grids on one page paginate separately.</li>
					<li>"Products per page" is in the Products (query) section.</li>
				</ul>' ),
			'filter' => array( 'dashicons-filter', 'Product Filter', '
				<p>An AJAX filter: only the product area reloads, the URL updates (shareable, back button works).</p>
				<ul>
					<li><strong>Choose the fields</strong> on <em>Nexvio &gt; Filters</em>: click <em>Add a filter field</em>, pick a field (search, price, category, any attribute such as colour or size, tags, availability, on sale, featured, rating), choose how it looks, put the fields in order with the arrows or by dragging, then <em>Save filters</em>. The preview shows the result with your own attributes and categories.</li>
					<li><strong>Look:</strong> on the same page choose Light or Dark and the main colour. A colour set in a widget\'s Style tab replaces it for that widget.</li>
					<li><strong>Attributes:</strong> colours show as colour circles, pictures as image circles, sizes as buttons in size order (XS, S, M, L, XL). Set them in <em>Products &gt; Attributes &gt; Configure terms</em>: <em>Swatch colour</em> or <em>Swatch image</em>. Only global attributes (Products &gt; Attributes) can be filtered.</li>
					<li><strong>Category</strong> can be a list (one category), checkboxes (several), a dropdown or buttons.</li>
					<li><strong>Shop fields:</strong> <em>Sort by</em> (popularity, rating, newest, price) and <em>Products per page</em> (e.g. 12, 24, 48).</li>
					<li><strong>Categories:</strong> sub-categories are indented under their category and open with the + button. On a category page its branch opens automatically.</li>
					<li>Colours, sizes and spacing of the filter itself are in Elementor (widget &gt; Style). To give one widget its own fields, set <em>Fields</em> to "Set here, for this widget only".</li>
					<li>If you use another products widget, set its container in <em>General &gt; Products container</em>.</li>
				</ul>' ),
			'filter-popup' => array( 'dashicons-filter', 'Filter Popup (side panel)', '
				<p>For shops that don\'t want the filter always visible: visitors see a <strong>Filters</strong> button; a click slides the filter in from the left (or right).</p>
				<ul>
					<li>The fields and the Look are the same as <em>Nexvio &gt; Filters</em>.</li>
					<li>Choices are not applied straight away: <strong>Apply</strong> closes the panel and shows the matching products (without reloading the page). The button shows how many filters are active.</li>
					<li>Elementor: button text and icon, side, panel title, Apply / Reset texts, width, slide speed, overlay colour, button colours (normal and hover).</li>
					<li>Put it above the products (e.g. next to the result count). The products area is found the same way as for the Product Filter.</li>
				</ul>' ),
			'search' => array( 'dashicons-search', 'Header Search', '
				<p>A search icon for the header. A click opens a search bar (dropdown or full-width). The search filters the shop by product title, and on a category page it searches inside that category. When the page has a Product Filter, results update without reloading. Press <kbd>Esc</kbd> to close.</p>' ),
			'related' => array( 'dashicons-randomize', 'Related Products', '
				<p>On the product page, shows other products of the <strong>same category</strong>, most popular first (or best rated, random, newest).</p>
				<ul>
					<li>Category used: most specific (default), primary (Yoast / Rank Math), top-level or all.</li>
					<li>"Fill from the parent category" completes the list when the category has few products.</li>
					<li>No Elementor Pro? Turn on <em>automatic related products</em> in <a href="' . esc_url( $s ) . '">Settings</a>, or use the shortcode.</li>
				</ul>' ),
			'cart' => array( 'dashicons-cart', 'Add to Cart', '
				<p>Quantity selector, "Add to cart" button and wishlist heart for the single product page. Simple products are added with AJAX and the mini-cart updates. Stock, minimum / maximum quantity and "sold individually" are respected. Variable and grouped products use the WooCommerce form, styled to match.</p>' ),
			'side-cart' => array( 'dashicons-cart', 'Side cart, Header Cart and Floating Cart', '
				<p>A luxury cart panel that slides in from the right. It uses the real WooCommerce cart: prices, variations, stock limits, coupons, shipping and totals are all calculated by WooCommerce.</p>
				<ul>
					<li><strong>Settings:</strong> <em>Nexvio &gt; Add to Cart</em>. Choose Light or Dark, pick one main colour (or a ready-made one) and check the live preview. Texts are automatic in your site\'s language; type your own only to replace them.</li>
					<li><strong>Adding products:</strong> product cards, the Add to Cart widget and product pages (simple and variable products, with their options) add without reloading and open the panel. Double clicks are ignored while a product is being added.</li>
					<li><strong>In the panel:</strong> change quantities, remove products, apply or remove a coupon, then <em>Proceed to Checkout</em> or <em>View Cart</em>. An empty cart shows a <em>Continue Shopping</em> button that goes to your shop page.</li>
					<li><strong>Header Cart</strong> widget: icon with a live counter for the header. <strong>Floating Cart</strong> widget: a button fixed in a bottom corner. Both open the same panel. You can also show a floating cart on every page from the settings, without a widget.</li>
					<li>With the side cart turned off, WooCommerce adds to cart exactly as before, and the Header Cart links to the cart page.</li>
				</ul>' ),
			'wishlist' => array( 'dashicons-heart', 'Wishlist', '
				<p>The wishlist is built in: no other plugin is needed.</p>
				<ul>
					<li><strong>Customers</strong> (logged in): the list is saved in their account, on every device.</li>
					<li><strong>Visitors:</strong> the list is saved in their browser and added to their account when they log in. You can require an account in Settings.</li>
					<li><strong>Wishlist Icon</strong> shows a live counter; <strong>Wishlist Grid</strong> shows the saved products and removes a card when its heart is clicked.</li>
					<li>Exclude the wishlist page from page caching: every visitor has a different list.</li>
				</ul>' ),
			'desc-rating' => array( 'dashicons-star-half', 'Short description & rating', '
				<p>In every product widget (Slider, Products Grid, Products Archive, Related Products, Wishlist Grid): <em>Content &gt; Description &amp; rating</em>. Each widget has its own settings.</p>
				<ul>
					<li><strong>Show short description:</strong> the product\'s WooCommerce short description, cut to a number of words (0 = all). Products without one show nothing; optionally use the long description instead. <em>Style &gt; Short description</em>: colour, typography, line height, alignment, spacing, maximum lines, and "Same height on every card" to keep prices and buttons lined up.</li>
					<li><strong>Show rating:</strong> the WooCommerce star rating with the review count (87) or (87 reviews), and optionally the average (4.5). Products without reviews show nothing, or the text "No reviews yet" if you choose; they never get stars. <em>Style &gt; Rating</em>: star colour and size, alignment, spacing, count colour and typography.</li>
				</ul>' ),
			'swatches' => array( 'dashicons-art', 'Colour swatches', '
				<p>Every product widget (Slider, Grid, Archive, Related Products, Wishlist Grid) can show a dot per colour under the image: <em>Content &gt; Colour swatches &gt; Show colour swatches</em>.</p>
				<ul>
					<li>The colour attribute is found automatically (named Color, Colour, Couleur…), or enter its slug, e.g. <code>pa_color</code>.</li>
					<li>On <strong>variable products</strong>, choosing a colour shows that variation\'s image on the card (on click, or on hover). The card then links to the product with that colour already selected.</li>
					<li>Dot colours: set them in <em>Products &gt; Attributes &gt; (your colour attribute) &gt; Configure terms &gt; Swatch colour</em>. Without it, the colour is read from the name (English and French names, or a hex code like #FFB07C).</li>
					<li>Size, gap, shape, alignment and colours: <em>Style &gt; Colour swatches</em>.</li>
				</ul>' ),
			'shortcodes' => array( 'dashicons-shortcode', 'Shortcodes', '
				<table class="nexvio-table">
					<tr><td><code>[nexvio_related_products limit="8" layout="slider" orderby="popularity"]</code></td><td>Related products on a product page. Options: layout slider|grid, orderby popularity|rating|rand|date|price, related_by specific|primary|top|all, fill_parent yes|no, title, columns.</td></tr>
					<tr><td><code>[nexvio_wishlist_count]</code></td><td>Live number of products in the wishlist.</td></tr>
				</table>' ),
			'developers' => array( 'dashicons-editor-code', 'Developers', '
				<p><strong>Filter URL parameters:</strong> <code>nexvio_q</code>, <code>min_price</code>, <code>max_price</code>, <code>nexvio_tax_{taxonomy}=a,b</code> (+ <code>nexvio_tax_{taxonomy}_all=1</code>), <code>nexvio_stock</code>, <code>nexvio_sale=1</code>, <code>nexvio_featured=1</code>.</p>
				<p><strong>PHP filters:</strong> <code>nexvio_product_query_args</code>, <code>nexvio_grid_pagination_args</code>, <code>nexvio_related_query_args</code>, <code>nexvio_elementor_category_after</code>.</p>
				<p><strong>PHP actions:</strong> <code>nexvio_wishlist_added</code>, <code>nexvio_wishlist_removed</code>.</p>
				<p><strong>JavaScript events</strong> (jQuery, on body): <code>nexvio_wishlist_changed</code>, <code>nexvio_filter_updated</code>.</p>' ),
			'faq' => array( 'dashicons-editor-help', 'FAQ', '
				<p><strong>The widgets are not in Elementor.</strong> Check that they are enabled in Widgets and that Elementor and WooCommerce are active, then reload the editor.</p>
				<p><strong>The filter reloads the whole page.</strong> The products container was not found: use the Product Grid widget with "Current query", or set your grid\'s selector in the filter settings.</p>
				<p><strong>The wishlist counter shows an old number.</strong> A page cache served an old page. The counter refreshes itself after load; also exclude the wishlist page from the cache.</p>' ),
		);
	}

	public static function page_docs() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$sections = self::doc_sections();
		self::open( self::DOCS, __( 'Documentation', 'nexvio' ), __( 'How to set up and use every widget.', 'nexvio' ) );
		?>
		<div class="nexvio-docs">
			<nav class="nexvio-glass nexvio-docs__toc" aria-label="<?php esc_attr_e( 'Contents', 'nexvio' ); ?>">
				<strong><?php esc_html_e( 'Contents', 'nexvio' ); ?></strong>
				<?php foreach ( $sections as $id => $sec ) : ?>
					<a href="#<?php echo esc_attr( $id ); ?>"><span class="dashicons <?php echo esc_attr( $sec[0] ); ?>" aria-hidden="true"></span><?php echo esc_html( $sec[1] ); ?></a>
				<?php endforeach; ?>
			</nav>
			<div class="nexvio-docs__body">
				<?php foreach ( $sections as $id => $sec ) : ?>
					<section class="nexvio-glass nexvio-card-box nexvio-doc" id="<?php echo esc_attr( $id ); ?>">
						<h2><span class="dashicons <?php echo esc_attr( $sec[0] ); ?>" aria-hidden="true"></span><?php echo esc_html( $sec[1] ); ?></h2>
						<?php echo wp_kses_post( $sec[2] ); ?>
					</section>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		self::close();
	}


	public static function ajax_toggle() {
		check_ajax_referer( 'nexvio_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( null, 403 );
		}
		$key     = isset( $_POST['key'] ) ? sanitize_key( wp_unslash( $_POST['key'] ) ) : '';
		$enabled = isset( $_POST['enabled'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['enabled'] ) );
		if ( ! array_key_exists( $key, Nexvio_Plugin::widgets() ) ) {
			wp_send_json_error( null, 400 );
		}
		$disabled = array_diff( Nexvio_Plugin::disabled(), array( $key ) );
		if ( ! $enabled ) {
			$disabled[] = $key;
		}
		update_option( 'nexvio_disabled_widgets', array_values( array_unique( $disabled ) ) );
		wp_send_json_success( array( 'key' => $key, 'enabled' => $enabled, 'counts' => self::counts() ) );
	}

	public static function ajax_bulk() {
		check_ajax_referer( 'nexvio_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( null, 403 );
		}
		$mode = isset( $_POST['mode'] ) ? sanitize_key( wp_unslash( $_POST['mode'] ) ) : '';
		update_option( 'nexvio_disabled_widgets', 'disable' === $mode ? array_keys( Nexvio_Plugin::widgets() ) : array() );
		wp_send_json_success( array( 'enabled' => 'disable' !== $mode, 'counts' => self::counts() ) );
	}

	public static function save_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'nexvio' ) );
		}
		check_admin_referer( 'nexvio_save_settings' );
		$section = isset( $_POST['nexvio_section'] ) && 'wishlist' === sanitize_key( wp_unslash( $_POST['nexvio_section'] ) ) ? 'wishlist' : 'general';

		if ( 'wishlist' === $section ) {
			$days = isset( $_POST['nexvio_wishlist_days'] ) ? absint( wp_unslash( $_POST['nexvio_wishlist_days'] ) ) : 60;
			update_option( 'nexvio_wishlist_page', isset( $_POST['nexvio_wishlist_page'] ) ? absint( wp_unslash( $_POST['nexvio_wishlist_page'] ) ) : 0 );
			update_option( 'nexvio_wishlist_guests', ! empty( $_POST['nexvio_wishlist_guests'] ) ? 'yes' : 'no' );
			update_option( 'nexvio_wishlist_days', max( 1, min( 730, $days ) ) );
			wp_safe_redirect( admin_url( 'admin.php?page=' . self::WISHLIST . '&updated=1' ) );
			exit;
		}

		$per_page = isset( $_POST['nexvio_per_page'] ) ? absint( wp_unslash( $_POST['nexvio_per_page'] ) ) : 0;
		$limit    = isset( $_POST['nexvio_related_limit'] ) ? absint( wp_unslash( $_POST['nexvio_related_limit'] ) ) : 8;
		$orderby  = isset( $_POST['nexvio_related_orderby'] ) ? sanitize_key( wp_unslash( $_POST['nexvio_related_orderby'] ) ) : 'popularity';
		$layout   = isset( $_POST['nexvio_related_layout'] ) ? sanitize_key( wp_unslash( $_POST['nexvio_related_layout'] ) ) : 'slider';
		update_option( 'nexvio_per_page', min( 200, $per_page ) );
		update_option( 'nexvio_auto_related', ! empty( $_POST['nexvio_auto_related'] ) ? 'yes' : 'no' );
		update_option( 'nexvio_related_title', isset( $_POST['nexvio_related_title'] ) ? sanitize_text_field( wp_unslash( $_POST['nexvio_related_title'] ) ) : '' );
		update_option( 'nexvio_related_limit', max( 1, min( 40, $limit ) ) );
		update_option( 'nexvio_related_orderby', in_array( $orderby, array( 'popularity', 'rating', 'rand', 'date' ), true ) ? $orderby : 'popularity' );
		update_option( 'nexvio_related_layout', 'grid' === $layout ? 'grid' : 'slider' );

		wp_safe_redirect( admin_url( 'admin.php?page=' . self::SETTINGS . '&updated=1' ) );
		exit;
	}

	public static function create_wishlist_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'nexvio' ) );
		}
		check_admin_referer( 'nexvio_create_wishlist_page' );
		$id = wp_insert_post( array(
			'post_title'  => 'My wishlist',
			'post_name'   => 'wishlist',
			'post_type'   => 'page',
			'post_status' => 'publish',
		) );
		if ( $id && ! is_wp_error( $id ) ) {
			update_option( 'nexvio_wishlist_page', (int) $id );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::WISHLIST . '&updated=1' ) );
		exit;
	}
}
