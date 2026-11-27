<?php
/**
 * The "Extra Options" tab in Product data (product edit screen).
 * Every field type of the Extra Options builder can be added to this one product,
 * one by one, with the same builder and live preview as Nexvio > Extra Options.
 * The fields are saved on the product (meta _nexvio_epo_own) and shown together with the forms
 * assigned to the product.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Nexvio_Epo_Product {

	public static function init() {
		if ( ! is_admin() ) {
			return;
		}
		add_filter( 'woocommerce_product_data_tabs', array( __CLASS__, 'tab' ) );
		add_action( 'woocommerce_product_data_panels', array( __CLASS__, 'panel' ) );
		add_action( 'woocommerce_process_product_meta', array( __CLASS__, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
	}

	public static function tab( $tabs ) {
		$tabs['nexvio_epo'] = array(
			'label'    => __( 'Extra Options', 'nexvio' ),
			'target'   => 'nexvio_epo_data',
			'class'    => array(),
			'priority' => 71,
		);
		return $tabs;
	}

	private static function is_product_screen( $hook ) {
		return in_array( $hook, array( 'post.php', 'post-new.php' ), true ) && 'product' === get_post_type();
	}

	public static function assets( $hook ) {
		if ( ! self::is_product_screen( $hook ) ) {
			return;
		}
		global $post;
		$pid = $post ? (int) $post->ID : 0;
		wp_enqueue_media();
		wp_enqueue_style( 'nexvio-admin', NEXVIO_URL . 'admin/assets/admin.css', array( 'dashicons', 'wp-color-picker' ), Nexvio_Plugin::ver( 'admin/assets/admin.css' ) );
		wp_enqueue_style( 'nexvio-epo-admin', NEXVIO_URL . 'admin/assets/epo.css', array( 'nexvio-admin' ), Nexvio_Plugin::ver( 'admin/assets/epo.css' ) );
		wp_enqueue_style( 'nexvio-epo-preview', NEXVIO_URL . 'assets/css/extra-options.css', array(), Nexvio_Plugin::ver( 'assets/css/extra-options.css' ) );
		wp_enqueue_style( 'nexvio-epo-faq-preview', NEXVIO_URL . 'assets/css/product-faq.css', array(), Nexvio_Plugin::ver( 'assets/css/product-faq.css' ) );
		wp_enqueue_style( 'nexvio-epo-product', NEXVIO_URL . 'admin/assets/epo-product.css', array( 'nexvio-epo-admin' ), Nexvio_Plugin::ver( 'admin/assets/epo-product.css' ) );
		wp_enqueue_script( 'nexvio-epo-front', NEXVIO_URL . 'assets/js/extra-options.js', array(), Nexvio_Plugin::ver( 'assets/js/extra-options.js' ), true );
		wp_enqueue_script( 'nexvio-epo-admin', NEXVIO_URL . 'admin/assets/epo.js', array( 'jquery', 'nexvio-epo-front' ), Nexvio_Plugin::ver( 'admin/assets/epo.js' ), true );
		wp_localize_script( 'nexvio-epo-admin', 'NEXVIO_EPO', Nexvio_Epo_Admin::builder_data( Nexvio_Epo::own_fields( $pid ) ) );
		wp_enqueue_script( 'nexvio-epo-product', NEXVIO_URL . 'admin/assets/epo-product.js', array( 'jquery', 'nexvio-epo-admin' ), Nexvio_Plugin::ver( 'admin/assets/epo-product.js' ), true );
	}

	public static function panel() {
		global $post;
		$pid   = $post ? (int) $post->ID : 0;
		$forms = $pid ? Nexvio_Epo::forms_for( $pid ) : array();
		wp_nonce_field( 'nexvio_epo_own_save', 'nexvio_epo_own_nonce' ); ?>
		<div id="nexvio_epo_data" class="panel woocommerce_options_panel nexvio-pt">
			<div id="nexvio-app" class="nexvio-app nexvio-pt__app">
				<?php if ( $forms ) : ?>
					<div class="nexvio-pt__forms">
						<span><?php esc_html_e( 'Forms that already apply to this product:', 'nexvio' ); ?></span>
						<?php foreach ( $forms as $form ) : ?>
							<a class="nexvio-pt__chip" href="<?php echo esc_url( admin_url( 'admin.php?page=nexvio-epo&tab=edit&form=' . (int) $form['id'] ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $form['title'] ? $form['title'] : '#' . $form['id'] ); ?></a>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
				<div class="nexvio-pt__grid">
					<div id="nexvio-epo-form" class="nexvio-settings nexvio-pt__form">
						<input type="hidden" name="nexvio_epo_own_json" id="nexvio-epo-json" value="">
						<section class="nexvio-glass nexvio-set">
							<header class="nexvio-set__head">
								<span class="nexvio-set__icon dashicons dashicons-forms" aria-hidden="true"></span>
								<div><h2><?php esc_html_e( 'Fields of this product', 'nexvio' ); ?></h2><p><?php esc_html_e( 'They appear in this order. Drag a field or use the arrows to move it.', 'nexvio' ); ?></p></div>
							</header>
							<ul class="nexvio-fb__list" id="nexvio-epo-list" aria-live="polite"></ul>
							<div class="nexvio-fb__add">
								<button type="button" class="nexvio-btn" id="nexvio-epo-add" aria-expanded="false" aria-controls="nexvio-epo-palette"><span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span><?php esc_html_e( 'Add a field', 'nexvio' ); ?></button>
							</div>
							<div class="nexvio-fb__palette" id="nexvio-epo-palette" hidden></div>
						</section>
						<p class="nexvio-pt__note"><?php esc_html_e( 'Click Update (or Publish) to save the fields.', 'nexvio' ); ?></p>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	public static function save( $post_id ) {
		if ( ! isset( $_POST['nexvio_epo_own_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nexvio_epo_own_nonce'] ) ), 'nexvio_epo_own_save' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) || ! isset( $_POST['nexvio_epo_own_json'] ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON input that is unslashed here and fully sanitised right after decoding.
		$raw = wp_unslash( $_POST['nexvio_epo_own_json'] );
		if ( ! is_string( $raw ) || '' === trim( $raw ) ) {
			return; /* the builder did not load: keep what is saved */
		}
		$list = json_decode( $raw, true );
		if ( ! is_array( $list ) ) {
			return;
		}
		$fields = Nexvio_Epo::sanitize_fields( $list );
		if ( $fields ) {
			update_post_meta( $post_id, Nexvio_Epo::META_OWN, wp_slash( wp_json_encode( $fields ) ) );
		} else {
			delete_post_meta( $post_id, Nexvio_Epo::META_OWN );
		}
	}
}
