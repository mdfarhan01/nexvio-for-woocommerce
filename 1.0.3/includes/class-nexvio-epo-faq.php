<?php
 if ( ! defined( 'ABSPATH' ) ) { exit; } class Nexvio_Epo_Faq { const META_OFF = '_nexvio_faq_off'; const META_OWN = '_nexvio_faq_own'; public static function init() { if ( ! is_admin() ) { return; } add_filter( 'woocommerce_product_data_tabs', array( __CLASS__, 'tab' ) ); add_action( 'woocommerce_product_data_panels', array( __CLASS__, 'panel' ) ); add_action( 'woocommerce_process_product_meta', array( __CLASS__, 'save' ) ); add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) ); } public static function looks() { return array( 'cards', 'lines', 'filled', 'accent', 'pills', 'dark', 'numbered', 'soft', 'outline', 'tiles' ); } public static function applies( $field, $product_id ) { if ( isset( $field['apply'] ) && 'products' === $field['apply'] ) { return in_array( (int) $product_id, array_map( 'intval', (array) ( isset( $field['products'] ) ? $field['products'] : array() ) ), true ); } return true; } public static function blocks( $product_id ) { $product_id = (int) $product_id; if ( ! $product_id ) { return array(); } $off = array_map( 'strval', (array) get_post_meta( $product_id, self::META_OFF, true ) ); $blocks = array(); $style = 'accordion'; foreach ( Nexvio_Epo::fields_for( $product_id ) as $f ) { if ( 'faq' !== $f['type'] || empty( $f['items'] ) || ! self::applies( $f, $product_id ) ) { continue; } $items = array(); foreach ( $f['items'] as $it ) { if ( ! in_array( (string) $f['id'] . ':' . $it['id'], $off, true ) ) { $items[] = $it; } } if ( $items ) { $style = $f['style']; $blocks[] = array( 'title' => $f['label'], 'style' => $f['style'], 'kind' => isset( $f['kind'] ) ? $f['kind'] : 'faq', 'badge' => isset( $f['badge'] ) ? $f['badge'] : 'auto', 'look' => isset( $f['look'] ) ? $f['look'] : 'cards', 'open_first' => ! empty( $f['open_first'] ), 'items' => $items, 'own' => false ); } } $own = Nexvio_Epo::sanitize_faq_items( get_post_meta( $product_id, self::META_OWN, true ) ); if ( $own ) { $blocks[] = array( 'title' => '', 'style' => $style, 'open_first' => false, 'items' => $own, 'own' => true ); } return $blocks; } public static function block_html( $block, $uid = '', $title_tag = 'h3', $badge_override = '' ) { if ( empty( $block['items'] ) ) { return ''; } $uid = $uid ? $uid : 'b' . wp_rand( 1000, 99999 ); $style = isset( $block['style'] ) && in_array( $block['style'], array( 'multi', 'static' ), true ) ? $block['style'] : 'accordion'; $kind = isset( $block['kind'] ) ? $block['kind'] : 'faq'; $badge = $badge_override && 'auto' !== $badge_override ? $badge_override : ( isset( $block['badge'] ) ? $block['badge'] : 'auto' ); if ( 'auto' === $badge ) { $badge = 'features' === $kind ? 'check' : ( 'info' === $kind ? 'none' : 'qa' ); } if ( ! in_array( $badge, array( 'qa', 'number', 'check', 'none' ), true ) ) { $badge = 'qa'; } $tag = in_array( $title_tag, array( 'h2', 'h3', 'h4', 'div', 'p' ), true ) ? $title_tag : 'h3'; $out = '<section class="nexvio-faq__block">'; if ( ! empty( $block['title'] ) ) { $out .= '<' . $tag . ' class="nexvio-faq__title">' . esc_html( $block['title'] ) . '</' . $tag . '>'; } $out .= '<div class="nexvio-faq__list">'; foreach ( array_values( $block['items'] ) as $i => $it ) { $mark = ''; if ( 'qa' === $badge ) { $mark = 'Q'; } elseif ( 'number' === $badge ) { $mark = (string) ( $i + 1 ); } elseif ( 'check' === $badge ) { $mark = '&#10003;'; } $bq = '' === $mark ? '' : '<span class="nexvio-faq__badge" aria-hidden="true">' . $mark . '</span>'; $ba = 'qa' === $badge ? '<span class="nexvio-faq__badge nexvio-faq__badge--a" aria-hidden="true">A</span>' : ''; $qt = '<span class="nexvio-faq__qt">' . esc_html( $it['q'] ) . '</span>'; $has_a = '' !== trim( wp_strip_all_tags( (string) $it['a'] ) ); $an = ! $has_a ? '' : '<div class="nexvio-faq__a">' . $ba . '<div class="nexvio-faq__atext">' . wp_kses_post( wpautop( $it['a'] ) ) . '</div></div>'; if ( 'static' === $style ) { $out .= '<div class="nexvio-faq__item nexvio-faq__item--static"><div class="nexvio-faq__q">' . $bq . $qt . '</div>' . $an . '</div>'; continue; } $open = ! empty( $block['open_first'] ) && 0 === $i; $out .= '<details class="nexvio-faq__item"' . ( 'accordion' === $style ? ' name="nxf-' . esc_attr( $uid ) . '"' : '' ) . ( $open ? ' open' : '' ) . '>'; $out .= '<summary class="nexvio-faq__q">' . $bq . $qt . '<span class="nexvio-faq__ico" aria-hidden="true"></span></summary>' . $an . '</details>'; } return $out . '</div></section>'; } public static function schema( $blocks ) { $main = array(); foreach ( $blocks as $b ) { foreach ( $b['items'] as $it ) { if ( '' === $it['q'] ) { continue; } $main[] = array( '@type' => 'Question', 'name' => $it['q'], 'acceptedAnswer' => array( '@type' => 'Answer', 'text' => wp_kses_post( $it['a'] ) ), ); } } if ( ! $main ) { return ''; } return '<script type="application/ld+json">' . wp_json_encode( array( '@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $main ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG ) . '</script>'; } public static function tab( $tabs ) { $tabs['nexvio_faq'] = array( 'label' => __( 'FAQ', 'nexvio' ), 'target' => 'nexvio_faq_data', 'class' => array(), 'priority' => 75, ); return $tabs; } public static function assets( $hook ) { if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) || 'product' !== get_post_type() ) { return; } wp_enqueue_style( 'nexvio-faq-admin', NEXVIO_URL . 'admin/assets/faq-product.css', array(), Nexvio_Plugin::ver( 'admin/assets/faq-product.css' ) ); wp_enqueue_script( 'nexvio-faq-admin', NEXVIO_URL . 'admin/assets/faq-product.js', array( 'jquery' ), Nexvio_Plugin::ver( 'admin/assets/faq-product.js' ), true ); } private static function own_row( $i, $it ) { ?>
		<div class="nexvio-faqp__row">
			<div class="nexvio-faqp__bar">
				<span class="nexvio-faqp__n"></span>
				<span class="nexvio-faqp__tools">
					<button type="button" class="button-link" data-act="up" title="<?php esc_attr_e( 'Move up', 'nexvio' ); ?>"><span class="dashicons dashicons-arrow-up-alt2"></span></button>
					<button type="button" class="button-link" data-act="down" title="<?php esc_attr_e( 'Move down', 'nexvio' ); ?>"><span class="dashicons dashicons-arrow-down-alt2"></span></button>
					<button type="button" class="button-link" data-act="del" title="<?php esc_attr_e( 'Remove', 'nexvio' ); ?>"><span class="dashicons dashicons-trash"></span></button>
				</span>
			</div>
			<input type="text" class="nexvio-faqp__q" name="nexvio_faq_own[<?php echo esc_attr( $i ); ?>][q]" value="<?php echo esc_attr( $it['q'] ); ?>" placeholder="<?php esc_attr_e( 'Question, e.g. Is it dishwasher safe?', 'nexvio' ); ?>">
			<textarea class="nexvio-faqp__a" rows="3" name="nexvio_faq_own[<?php echo esc_attr( $i ); ?>][a]" placeholder="<?php esc_attr_e( 'Answer', 'nexvio' ); ?>"><?php echo esc_textarea( $it['a'] ); ?></textarea>
		</div>
		<?php
 } public static function panel() { global $post; $pid = $post ? (int) $post->ID : 0; $off = array_map( 'strval', (array) get_post_meta( $pid, self::META_OFF, true ) ); $own = Nexvio_Epo::sanitize_faq_items( get_post_meta( $pid, self::META_OWN, true ) ); $sets = array(); foreach ( Nexvio_Epo::forms_for( $pid ) as $form ) { foreach ( $form['fields'] as $f ) { if ( 'faq' === $f['type'] && ! empty( $f['items'] ) && self::applies( $f, $pid ) ) { $sets[] = array( 'form' => $form, 'field' => $f ); } } } wp_nonce_field( 'nexvio_faq_save', 'nexvio_faq_nonce' ); ?>
		<div id="nexvio_faq_data" class="panel woocommerce_options_panel nexvio-faqp">
			<div class="nexvio-faqp__intro">
				<strong><?php esc_html_e( 'Product FAQ', 'nexvio' ); ?></strong>
				<p><?php esc_html_e( 'These questions show in the Product FAQ widget on the product page. Pick the ready-made questions you want here, and add questions that only belong to this product.', 'nexvio' ); ?></p>
			</div>

			<div class="nexvio-faqp__sec">
				<h4><?php esc_html_e( 'Ready-made questions', 'nexvio' ); ?></h4>
				<?php if ( ! $sets ) : ?>
					<p class="nexvio-faqp__empty"><?php esc_html_e( 'No ready-made FAQ applies to this product yet.', 'nexvio' ); ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=nexvio-epo&tab=edit&form=new' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Create one: add a FAQ field in a form', 'nexvio' ); ?></a>
					</p>
				<?php endif; ?>
				<?php foreach ( $sets as $set ) : ?>
					<?php $f = $set['field']; ?>
					<fieldset class="nexvio-faqp__set">
						<legend><?php echo esc_html( ( '' !== $f['label'] ? $f['label'] : __( 'FAQ', 'nexvio' ) ) . ' · ' . ( $set['form']['title'] ? $set['form']['title'] : '#' . $set['form']['id'] ) ); ?></legend>
						<?php foreach ( $f['items'] as $it ) : ?>
							<?php $key = $f['id'] . ':' . $it['id']; ?>
							<label class="nexvio-faqp__pick">
								<input type="hidden" name="nexvio_faq_seen[]" value="<?php echo esc_attr( $key ); ?>">
								<input type="checkbox" name="nexvio_faq_show[]" value="<?php echo esc_attr( $key ); ?>"<?php checked( ! in_array( $key, $off, true ) ); ?>>
								<span><?php echo esc_html( '' !== $it['q'] ? $it['q'] : wp_trim_words( wp_strip_all_tags( $it['a'] ), 10 ) ); ?></span>
							</label>
						<?php endforeach; ?>
					</fieldset>
				<?php endforeach; ?>
			</div>

			<div class="nexvio-faqp__sec">
				<h4><?php esc_html_e( 'Questions for this product only', 'nexvio' ); ?></h4>
				<div class="nexvio-faqp__rows" id="nexvio-faqp-rows">
					<?php foreach ( array_values( $own ) as $i => $it ) { self::own_row( $i, $it ); } ?>
				</div>
				<script type="text/html" id="nexvio-faqp-tpl"><?php self::own_row( '__i__', array( 'q' => '', 'a' => '' ) ); ?></script>
				<p><button type="button" class="button" id="nexvio-faqp-add"><span class="dashicons dashicons-plus-alt2" style="margin-top:3px"></span> <?php esc_html_e( 'Add a question', 'nexvio' ); ?></button></p>
			</div>
		</div>
		<?php
 }
public static function save( $post_id ) {
if ( ! isset( $_POST['nexvio_faq_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nexvio_faq_nonce'] ) ), 'nexvio_faq_save' ) ) {
return;
}
if ( ! current_user_can( 'edit_post', $post_id ) ) {
return;
}
$seen = isset( $_POST['nexvio_faq_seen'] ) && is_array( $_POST['nexvio_faq_seen'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['nexvio_faq_seen'] ) ) : array();
$show = isset( $_POST['nexvio_faq_show'] ) && is_array( $_POST['nexvio_faq_show'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['nexvio_faq_show'] ) ) : array();
$off = array_values( array_diff( $seen, $show ) );
$prev = array_map( 'strval', (array) get_post_meta( $post_id, self::META_OFF, true ) );
$off = array_values( array_unique( array_merge( $off, array_diff( $prev, $seen ) ) ) );
update_post_meta( $post_id, self::META_OFF, $off );
// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON/array input that is unslashed here and fully sanitised right after decoding.
$own = isset( $_POST['nexvio_faq_own'] ) && is_array( $_POST['nexvio_faq_own'] ) ? wp_unslash( $_POST['nexvio_faq_own'] ) : array();
update_post_meta( $post_id, self::META_OWN, Nexvio_Epo::sanitize_faq_items( array_values( $own ) ) );
}
} 