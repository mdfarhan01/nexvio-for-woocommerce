<?php
/**
 * Extra Options - markup of the fields on the single product page.
 * Used by the Elementor widget and by the automatic display.
 *
 * @package Nexvio_For_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Nexvio_Epo_Render {

	/** CSS custom properties from the global settings (the widget may override them). */
	public static function css_vars() {
		$main = sanitize_hex_color( (string) Nexvio_Epo_Settings::get( 'main_color' ) );
		$main = $main ? $main : '#3d8bff';
		return '--nxe-mc:' . $main . ';--nxe-mc-t:' . Nexvio_Epo_Settings::contrast( $main ) . ';--nxe-r:' . (int) Nexvio_Epo_Settings::get( 'radius' ) . 'px;--nxe-gap:' . (int) Nexvio_Epo_Settings::get( 'gap' ) . 'px';
	}

	/** Allowed HTML for the fields: the plugin's list plus the few tags / attributes only this block uses. */
	public static function allowed() {
		static $allowed = null;
		if ( null !== $allowed ) {
			return $allowed;
		}
		$allowed = Nexvio_Plugin::allowed_html();
		$global  = array( 'class' => true, 'id' => true, 'style' => true, 'title' => true, 'role' => true, 'hidden' => true, 'data-*' => true, 'aria-required' => true, 'aria-label' => true, 'aria-hidden' => true );
		$allowed['output']   = array_merge( $global, array( 'for' => true ) );
		$allowed['hr']       = $global;
		$allowed['option']   = array( 'value' => true, 'selected' => true, 'data-*' => true );
		foreach ( array( 'details' => array( 'open' => true, 'name' => true ), 'summary' => array(), 'section' => array(), 'h2' => array(), 'h3' => array(), 'h4' => array(), 'ul' => array(), 'ol' => array(), 'li' => array(), 'span' => array(), 'div' => array(), 'p' => array(), 'a' => array( 'href' => true, 'target' => true, 'rel' => true ), 'strong' => array(), 'em' => array(), 'br' => array(), 'img' => array( 'src' => true, 'alt' => true, 'width' => true, 'height' => true, 'loading' => true ), 'blockquote' => array() ) as $t => $extra ) {
			$allowed[ $t ] = array_merge( isset( $allowed[ $t ] ) ? $allowed[ $t ] : array(), $global, $extra );
		}
		$allowed['textarea'] = array_merge( isset( $allowed['textarea'] ) ? $allowed['textarea'] : array(), $global, array( 'maxlength' => true, 'name' => true, 'rows' => true, 'placeholder' => true, 'required' => true ) );
		foreach ( array( 'input', 'select', 'button', 'label' ) as $tag ) {
			$allowed[ $tag ] = array_merge( isset( $allowed[ $tag ] ) ? $allowed[ $tag ] : array(), array( 'aria-required' => true, 'aria-label' => true ) );
		}
		return $allowed;
	}

	public static function enqueue() {
		if ( function_exists( 'wp_enqueue_style' ) ) {
			wp_enqueue_style( 'nexvio-extra-options' );
			wp_enqueue_script( 'nexvio-extra-options' );
		}
	}

	/** The label a choice shows: what the customer pays, plus the regular price when it is on sale. */
	private static function choice_labels( $c, $show ) {
		if ( ! $show ) {
			return array( '', '' );
		}
		$sale = Nexvio_Epo::has_sale( $c );
		$now  = self::price_label( Nexvio_Epo::choice_price( $c ), $c['price_type'] );
		if ( $sale && '' === $now ) {
			$now = __( 'Free', 'nexvio' );
		}
		return array( $now, $sale ? self::price_label( $c['price'], $c['price_type'] ) : '' );
	}

	private static function cprice_html( $now, $old ) {
		if ( '' === $now && '' === $old ) {
			return '';
		}
		return '<span class="nexvio-epo__cprice">' . ( '' !== $old ? '<del class="nexvio-epo__cold">' . esc_html( $old ) . '</del> ' : '' ) . esc_html( $now ) . '</span>';
	}

	/** A price next to a choice, e.g. "+€5.00" or "+10%". */
	private static function price_label( $price, $ptype ) {
		$price = (float) $price;
		if ( ! $price ) {
			return '';
		}
		if ( 'percent' === $ptype ) {
			return ( $price > 0 ? '+' : '−' ) . rtrim( rtrim( number_format( abs( $price ), 2, '.', '' ), '0' ), '.' ) . '%';
		}
		return ( $price > 0 ? '+' : '−' ) . wp_strip_all_tags( wc_price( abs( $price ) ) );
	}

	/**
	 * The whole block for a product.
	 *
	 * @param WC_Product $product Product (a variation is replaced by its parent).
	 * @param array      $args    title, totals ('' = global setting, yes, no), auto (bool), editor (bool).
	 * @return string HTML ('' when no field applies).
	 */
	public static function html( $product, $args = array() ) {
		if ( ! $product instanceof WC_Product ) {
			return '';
		}
		$args = array_merge( array( 'title' => '', 'totals' => '', 'auto' => false, 'editor' => false, 'descriptions' => 'yes' ), $args );
		if ( $product->is_type( 'variation' ) ) {
			$parent = wc_get_product( $product->get_parent_id() );
			$product = $parent ? $parent : $product;
		}
		$fields = Nexvio_Epo::fields_for( $product->get_id() );
		if ( ! $fields ) {
			return '';
		}
		self::enqueue();
		return self::build( $fields, $product, $args );
	}

	/** The form as the admin preview shows it: the same markup, a sample price, nothing is saved. */
	public static function preview( $fields, $base ) {
		return self::build( $fields, null, array( 'base' => (float) $base, 'preview' => true, 'totals' => 'yes' ) );
	}

	private static function build( $fields, $product, $args ) {
		$args     = array_merge( array( 'title' => '', 'totals' => '', 'auto' => false, 'editor' => false, 'descriptions' => 'yes', 'preview' => false ), $args );
		$preview  = ! empty( $args['preview'] );
		$fields   = $preview ? $fields : array_values( array_filter( $fields, function ( $f ) { return 'faq' !== $f['type']; } ) );
		if ( ! $fields ) {
			return '';
		}
		$map      = array();
		foreach ( $fields as $f ) {
			$map[ $f['id'] ] = $f;
		}
		$defaults = Nexvio_Epo::default_values( $fields );
		$defaults = array_merge( $defaults, Nexvio_Epo::context_values( $product ? $product->get_id() : 0, 1, 0, $preview ? (float) $args['base'] : null ) );
		$state    = array();
		$preset   = 'dark' === Nexvio_Epo_Settings::get( 'preset' ) ? 'dark' : 'light';
		$variable = $product && $product->is_type( 'variable' );
		$base     = $preview ? (float) $args['base'] : ( $variable ? 0 : (float) wc_get_price_to_display( $product ) );
		$totals   = '' === $args['totals'] ? Nexvio_Epo_Settings::on( 'show_totals' ) : 'yes' === $args['totals'];

		$var_prices = array();
		if ( $variable && ! $preview ) {
			foreach ( $product->get_available_variations() as $v ) {
				if ( isset( $v['variation_id'] ) && isset( $v['display_price'] ) ) {
					$var_prices[ (int) $v['variation_id'] ] = (float) $v['display_price'];
				}
			}
		}

		$config = array(
			'varPrices' => $var_prices,
			'currency' => Nexvio_Epo::currency_config(),
			'ajax'     => admin_url( 'admin-ajax.php' ),
			'nonce'    => $preview ? '' : wp_create_nonce( 'nexvio_epo' ),
			'i18n'     => array(
				'required'  => Nexvio_Epo_Settings::text( 't_required' ),
				'uploading' => Nexvio_Epo_Settings::text( 't_uploading' ),
				'uploadErr' => Nexvio_Epo_Settings::text( 't_upl_error' ),
				'pending'   => Nexvio_Epo_Settings::text( 't_pending' ),
			),
			'hideZero' => Nexvio_Epo_Settings::on( 'totals_hide' ),
			'user'     => is_user_logged_in() ? 'in' : 'out',
			'day'      => (string) (int) current_time( 'w' ),
		);

		ob_start();
		?>
		<div class="nexvio-epo nexvio-epo--<?php echo esc_attr( $preset ); ?><?php echo $args['auto'] ? ' nexvio-epo--auto' : ''; ?>" data-nexvio-epo<?php echo $preview ? ' data-preview' : ''; ?> data-product="<?php echo esc_attr( $product ? $product->get_id() : 0 ); ?>" data-base="<?php echo esc_attr( $base ); ?>" data-variable="<?php echo $variable ? '1' : '0'; ?>" data-config="<?php echo esc_attr( wp_json_encode( $config ) ); ?>" style="<?php echo esc_attr( self::css_vars() ); ?>">
			<?php if ( '' !== trim( (string) $args['title'] ) ) : ?>
				<h3 class="nexvio-epo__title"><?php echo esc_html( $args['title'] ); ?></h3>
			<?php endif; ?>
			<div class="nexvio-epo__fields">
				<?php foreach ( $fields as $f ) : ?>
					<?php
					$visible = Nexvio_Epo::is_visible( $f, $map, $defaults, $state );
					self::field( $f, $visible, 'yes' === $args['descriptions'] );
					?>
				<?php endforeach; ?>
			</div>
			<?php if ( $totals ) : ?>
				<div class="nexvio-epo__totals" data-nxe-totals hidden>
					<?php if ( Nexvio_Epo_Settings::on( 'totals_price' ) ) : ?>
						<div class="nexvio-epo__trow" data-nxe-row="product"><span><?php echo esc_html( Nexvio_Epo_Settings::text( 't_product' ) ); ?></span><b data-nxe-product></b></div>
					<?php endif; ?>
					<div class="nexvio-epo__trow" data-nxe-row="options"><span><?php echo esc_html( Nexvio_Epo_Settings::text( 't_options' ) ); ?></span><b data-nxe-options></b></div>
					<div class="nexvio-epo__trow nexvio-epo__trow--final" data-nxe-row="final"><span><?php echo esc_html( Nexvio_Epo_Settings::text( 't_final' ) ); ?></span><b data-nxe-final></b></div>
					<p class="nexvio-epo__pending" data-nxe-pending hidden><?php echo esc_html( Nexvio_Epo_Settings::text( 't_pending' ) ); ?></p>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	private static function field( $f, $visible, $show_desc ) {
		$id   = $f['id'];
		$type = $f['type'];
		$name = 'nexvio_epo[' . $id . ']';
		$dom  = 'nxe-' . $id;

		if ( 'faq' === $type ) {
			echo '<div class="nexvio-epo__field nexvio-epo__field--faq" data-field="' . esc_attr( $id ) . '" data-type="faq" data-logic="' . esc_attr( wp_json_encode( $f['logic'] ) ) . '"' . ( $visible ? '' : ' hidden' ) . '><div class="nexvio-faq nexvio-faq--' . esc_attr( isset( $f['look'] ) ? $f['look'] : 'cards' ) . ' nexvio-faq--icon-plus nexvio-faq--side-right">' . Nexvio_Epo_Faq::block_html( array( 'title' => $f['label'], 'style' => $f['style'], 'kind' => isset( $f['kind'] ) ? $f['kind'] : 'faq', 'badge' => isset( $f['badge'] ) ? $f['badge'] : 'auto', 'open_first' => ! empty( $f['open_first'] ), 'items' => $f['items'] ), 'f' . $id ) . '</div></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
			return;
		}
		if ( 'divider' === $type ) {
			echo '<div class="nexvio-epo__field nexvio-epo__field--divider" data-field="' . esc_attr( $id ) . '" data-type="divider" data-logic="' . esc_attr( wp_json_encode( $f['logic'] ) ) . '"' . ( $visible ? '' : ' hidden' ) . '><hr></div>';
			return;
		}
		if ( 'header' === $type ) {
			$tag = isset( $f['tag'] ) && in_array( $f['tag'], array( 'h2', 'h3', 'h4' ), true ) ? $f['tag'] : 'h3';
			echo '<div class="nexvio-epo__field nexvio-epo__field--header" data-field="' . esc_attr( $id ) . '" data-type="header" data-logic="' . esc_attr( wp_json_encode( $f['logic'] ) ) . '"' . ( $visible ? '' : ' hidden' ) . '>';
			if ( '' !== $f['label'] ) {
				echo '<' . tag_escape( $tag ) . ' class="nexvio-epo__heading">' . esc_html( $f['label'] ) . '</' . tag_escape( $tag ) . '>';
			}
			if ( $show_desc && '' !== $f['description'] ) {
				echo '<p class="nexvio-epo__desc">' . esc_html( $f['description'] ) . '</p>';
			}
			echo '</div>';
			return;
		}

		$label = '' !== $f['label'] ? $f['label'] : Nexvio_Epo::types()[ $type ][0];
		$ptype = isset( $f['price_type'] ) ? $f['price_type'] : 'none';
		$price = isset( $f['price'] ) ? (float) $f['price'] : 0.0;
		$width = isset( $f['width'] ) ? $f['width'] : 'full';
		?>
		<div class="nexvio-epo__field nexvio-epo__field--<?php echo esc_attr( $type ); ?> is-<?php echo esc_attr( $width ); ?>" data-field="<?php echo esc_attr( $id ); ?>" data-type="<?php echo esc_attr( $type ); ?>" data-label="<?php echo esc_attr( $label ); ?>" data-required="<?php echo ! empty( $f['required'] ) ? '1' : '0'; ?>" data-ptype="<?php echo esc_attr( $ptype ); ?>" data-price="<?php echo esc_attr( $price ); ?>" data-logic="<?php echo esc_attr( wp_json_encode( $f['logic'] ) ); ?>"<?php echo $visible ? '' : ' hidden'; ?>>
			<?php if ( empty( $f['hide_label'] ) ) : ?>
				<label class="nexvio-epo__label" for="<?php echo esc_attr( $dom ); ?>"><?php echo esc_html( $label ); ?><?php if ( ! empty( $f['required'] ) && Nexvio_Epo_Settings::on( 'req_mark' ) ) : ?> <span class="nexvio-epo__req" aria-hidden="true">*</span><?php endif; ?><?php
				if ( $price && in_array( $ptype, array( 'fixed', 'percent', 'char', 'value' ), true ) && Nexvio_Epo_Settings::on( 'show_prices' ) ) {
					$suffix = 'char' === $ptype ? ' / ' . esc_html__( 'character', 'nexvio' ) : ( 'value' === $ptype ? ' / ' . esc_html__( 'unit', 'nexvio' ) : '' );
					echo ' <span class="nexvio-epo__fprice">' . esc_html( self::price_label( $price, 'percent' === $ptype ? 'percent' : 'fixed' ) . $suffix ) . '</span>';
				}
				?></label>
			<?php endif; ?>
			<?php if ( $show_desc && '' !== $f['description'] ) : ?>
				<p class="nexvio-epo__desc"><?php echo esc_html( $f['description'] ); ?></p>
			<?php endif; ?>
			<?php self::control( $f, $name, $dom ); ?>
			<div class="nexvio-epo__error" role="alert" hidden></div>
		</div>
		<?php
	}

	private static function choice_attrs( $c ) {
		return ' data-price="' . esc_attr( Nexvio_Epo::choice_price( $c ) ) . '" data-ptype="' . esc_attr( $c['price_type'] ) . '"';
	}

	private static function control( $f, $name, $dom ) {
		$type = $f['type'];
		$req  = ! empty( $f['required'] ) ? ' data-nxe-required="1"' : '';
		$ph   = isset( $f['placeholder'] ) ? $f['placeholder'] : '';
		$def  = isset( $f['default'] ) ? $f['default'] : '';
		$show = Nexvio_Epo_Settings::on( 'show_prices' );

		switch ( $type ) {
			case 'text':
				echo '<input type="text" class="nexvio-epo__input" id="' . esc_attr( $dom ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $def ) . '" placeholder="' . esc_attr( $ph ) . '"' . ( ! empty( $f['maxlength'] ) ? ' maxlength="' . (int) $f['maxlength'] . '"' : '' ) . $req . ' data-nxe-input>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
				break;
			case 'textarea':
				echo '<textarea class="nexvio-epo__input" id="' . esc_attr( $dom ) . '" name="' . esc_attr( $name ) . '" rows="3" placeholder="' . esc_attr( $ph ) . '"' . ( ! empty( $f['maxlength'] ) ? ' maxlength="' . (int) $f['maxlength'] . '"' : '' ) . $req . ' data-nxe-input>' . esc_textarea( $def ) . '</textarea>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
				break;
			case 'number':
				echo '<div class="nexvio-epo__num" data-nxe-num><button type="button" class="nexvio-epo__step" data-nxe-step="-1" aria-label="' . esc_attr__( 'Decrease', 'nexvio' ) . '"><span aria-hidden="true">&minus;</span></button><input type="number" class="nexvio-epo__input" id="' . esc_attr( $dom ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $def ) . '" placeholder="' . esc_attr( $ph ) . '"'
					. ( isset( $f['min'] ) && '' !== $f['min'] ? ' min="' . esc_attr( $f['min'] ) . '"' : '' )
					. ( isset( $f['max'] ) && '' !== $f['max'] ? ' max="' . esc_attr( $f['max'] ) . '"' : '' )
					. ' step="' . esc_attr( isset( $f['step'] ) ? $f['step'] : 1 ) . '"' . $req . ' data-nxe-input><button type="button" class="nexvio-epo__step" data-nxe-step="1" aria-label="' . esc_attr__( 'Increase', 'nexvio' ) . '"><span aria-hidden="true">+</span></button></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
				break;
			case 'range':
				$min = isset( $f['min'] ) && '' !== $f['min'] ? (float) $f['min'] : 0;
				$max = isset( $f['max'] ) && '' !== $f['max'] ? (float) $f['max'] : 100;
				$val = '' !== $def && is_numeric( $def ) ? (float) $def : $min;
				echo '<div class="nexvio-epo__range"><input type="range" id="' . esc_attr( $dom ) . '" name="' . esc_attr( $name ) . '" min="' . esc_attr( $min ) . '" max="' . esc_attr( $max ) . '" step="' . esc_attr( isset( $f['step'] ) ? $f['step'] : 1 ) . '" value="' . esc_attr( $val ) . '" data-nxe-input data-nxe-range><output class="nexvio-epo__out" data-nxe-out>' . esc_html( $val ) . '</output></div>';
				break;
			case 'color':
				$hex = $def && sanitize_hex_color( $def ) ? $def : '';
				$cph = __( 'Pick a colour', 'nexvio' );
				echo '<div class="nexvio-epo__colorwrap' . ( $hex ? '' : ' is-empty' ) . '" data-nxe-colorwrap>'
					. '<input type="color" class="nexvio-epo__color" id="' . esc_attr( $dom ) . '" value="' . esc_attr( $hex ? $hex : '#000000' ) . '" data-nxe-color>'
					. '<label class="nexvio-epo__colorval" for="' . esc_attr( $dom ) . '" data-nxe-colorval data-ph="' . esc_attr( $cph ) . '">' . esc_html( $hex ? strtoupper( $hex ) : $cph ) . '</label>'
					. '<button type="button" class="nexvio-epo__colorclear" data-nxe-colorclear aria-label="' . esc_attr__( 'Clear colour', 'nexvio' ) . '"' . ( $hex ? '' : ' hidden' ) . '>&times;</button>'
					. '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( $hex ) . '" data-nxe-input></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
				break;
			case 'date':
				echo '<div class="nexvio-epo__dt nexvio-epo__dt--date"><input type="date" class="nexvio-epo__input" id="' . esc_attr( $dom ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $def ) . '"' . $req . ' data-nxe-input></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
				break;
			case 'time':
				echo '<div class="nexvio-epo__dt nexvio-epo__dt--time"><input type="time" class="nexvio-epo__input" id="' . esc_attr( $dom ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $def ) . '"' . $req . ' data-nxe-input></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
				break;
			case 'select':
				echo '<select class="nexvio-epo__input" id="' . esc_attr( $dom ) . '" name="' . esc_attr( $name ) . '"' . $req . ' data-nxe-input>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
				echo '<option value="">' . esc_html( '' !== $ph ? $ph : Nexvio_Epo_Settings::text( 't_choose' ) ) . '</option>';
				foreach ( $f['choices'] as $c ) {
					list( $pl, $old ) = self::choice_labels( $c, $show );
					echo '<option value="' . esc_attr( $c['id'] ) . '" data-label="' . esc_attr( $c['label'] ) . '" data-plabel="' . esc_attr( $pl ) . '" data-oldlabel="' . esc_attr( $old ) . '"' . self::choice_attrs( $c ) . selected( ! empty( $c['default'] ), true, false ) . '>' . esc_html( $c['label'] . ( '' !== $pl ? ' (' . $pl . ')' : '' ) ) . '</option>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
				}
				echo '</select>';
				break;
			case 'radio':
			case 'checkbox':
				$display = isset( $f['display'] ) ? $f['display'] : 'list';
				$input   = 'radio' === $type ? 'radio' : 'checkbox';
				echo '<div class="nexvio-epo__choices nexvio-epo__choices--' . esc_attr( $display ) . '" role="' . ( 'radio' === $type ? 'radiogroup' : 'group' ) . '" id="' . esc_attr( $dom ) . '">';
				foreach ( $f['choices'] as $i => $c ) {
					list( $pl, $old ) = self::choice_labels( $c, $show );
					echo '<label class="nexvio-epo__choice"><input type="' . esc_attr( $input ) . '" name="' . esc_attr( $name . ( 'checkbox' === $type ? '[]' : '' ) ) . '" value="' . esc_attr( $c['id'] ) . '"' . self::choice_attrs( $c ) . checked( ! empty( $c['default'] ), true, false ) . ' data-nxe-input><span class="nexvio-epo__mark" aria-hidden="true"></span><span class="nexvio-epo__text">' . esc_html( $c['label'] ) . '</span>' . self::cprice_html( $pl, $old ) . '</label>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
				}
				echo '</div>';
				break;
			case 'swatch':
				$multi = ! empty( $f['multiple'] );
				$mode  = isset( $f['swatch_mode'] ) ? $f['swatch_mode'] : 'color';
				echo '<div class="nexvio-epo__swatches" role="' . ( $multi ? 'group' : 'radiogroup' ) . '" id="' . esc_attr( $dom ) . '">';
				foreach ( $f['choices'] as $c ) {
					list( $pl, $old ) = self::choice_labels( $c, $show );
					$style = 'image' === $mode && $c['image'] ? 'background-image:url("' . str_replace( array( '"', "'", '(', ')', '\\', ';', ' ' ), array( '%22', '%27', '%28', '%29', '%5C', '%3B', '%20' ), esc_url_raw( $c['image'] ) ) . '")' : ( $c['color'] ? 'background-color:' . $c['color'] : '' );
					echo '<label class="nexvio-epo__sw' . ( 'image' === $mode ? ' is-image' : '' ) . '" title="' . esc_attr( $c['label'] . ( '' !== $pl ? ' (' . $pl . ')' : '' ) ) . '"><input type="' . ( $multi ? 'checkbox' : 'radio' ) . '" name="' . esc_attr( $name . ( $multi ? '[]' : '' ) ) . '" value="' . esc_attr( $c['id'] ) . '"' . self::choice_attrs( $c ) . checked( ! empty( $c['default'] ), true, false ) . ' data-nxe-input><span class="nexvio-epo__swface" style="' . esc_attr( $style ) . '"></span><span class="nexvio-epo__swname">' . esc_html( $c['label'] ) . '</span>' . self::cprice_html( $pl, $old ) . '</label>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
				}
				echo '</div>';
				break;
			case 'upload':
				$types = array_map( 'strtoupper', (array) Nexvio_Epo_Settings::get( 'upload_types' ) );
				echo '<div class="nexvio-epo__upload" data-nxe-upload>';
				echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="" data-nxe-input data-nxe-token>';
				echo '<label class="nexvio-epo__ubtn"><input type="file" id="' . esc_attr( $dom ) . '" data-nxe-file' . ( $req ? ' aria-required="true"' : '' ) . '><span>' . esc_html( Nexvio_Epo_Settings::text( 't_upload' ) ) . '</span></label>';
				echo '<span class="nexvio-epo__ufile" data-nxe-fname></span>';
				echo '<button type="button" class="nexvio-epo__uclear" data-nxe-clear hidden aria-label="' . esc_attr__( 'Remove file', 'nexvio' ) . '">×</button>';
				echo '<p class="nexvio-epo__uhint">' . esc_html( implode( ', ', $types ) ) . ' · ' . esc_html( (int) Nexvio_Epo_Settings::get( 'upload_max' ) ) . ' MB</p>';
				echo '</div>';
				break;
		}
	}
}
