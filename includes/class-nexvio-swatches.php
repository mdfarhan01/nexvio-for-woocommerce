<?php
/**
 * Colour swatches for product cards.
 *
 * Finds the colour attribute of a product, the colour of every value and,
 * for variable products, the image of the matching variation.
 * Adds a "Swatch colour" field to product attribute terms.
 *
 * @package Nexvio_For_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Nexvio_Swatches {

	const META  = 'nexvio_swatch_color';
	const IMAGE = 'nexvio_swatch_image';

	/** Words that mark an attribute as a colour attribute. */
	private static $keywords = array( 'color', 'colour', 'couleur', 'farbe', 'colore', 'kleur', 'cor' );

	/** Colour names (English + French) => hex. */
	private static $names = array(
		'black' => '#111111', 'noir' => '#111111', 'white' => '#ffffff', 'blanc' => '#ffffff', 'ivory' => '#fffff0', 'ivoire' => '#fffff0',
		'cream' => '#f5ecd7', 'creme' => '#f5ecd7', 'off white' => '#f6f3ea', 'beige' => '#e8dcc4', 'sand' => '#d8c7a3', 'sable' => '#d8c7a3',
		'grey' => '#9ca3af', 'gray' => '#9ca3af', 'gris' => '#9ca3af', 'light grey' => '#d1d5db', 'light gray' => '#d1d5db', 'gris clair' => '#d1d5db',
		'dark grey' => '#4b5563', 'dark gray' => '#4b5563', 'gris fonce' => '#4b5563', 'charcoal' => '#36454f', 'anthracite' => '#383e42', 'silver' => '#c0c0c0', 'argent' => '#c0c0c0',
		'red' => '#dc2626', 'rouge' => '#dc2626', 'burgundy' => '#800020', 'bordeaux' => '#6d071a', 'maroon' => '#800000', 'wine' => '#722f37',
		'pink' => '#f4a6b8', 'rose' => '#f4a6b8', 'light pink' => '#ffc9d6', 'rose clair' => '#ffc9d6', 'hot pink' => '#ff4f9a', 'fuchsia' => '#d946ef', 'blush' => '#f3c6c1', 'coral' => '#ff7f50', 'corail' => '#ff7f50',
		'orange' => '#f97316', 'peach' => '#ffcba4', 'peche' => '#ffcba4', 'apricot' => '#fbceb1',
		'yellow' => '#facc15', 'jaune' => '#facc15', 'mustard' => '#e1ad01', 'moutarde' => '#e1ad01', 'gold' => '#d4af37', 'or' => '#d4af37', 'lemon' => '#fff44f',
		'green' => '#16a34a', 'vert' => '#16a34a', 'light green' => '#86efac', 'vert clair' => '#86efac', 'dark green' => '#14532d', 'vert fonce' => '#14532d',
		'olive' => '#708238', 'kaki' => '#8a8650', 'khaki' => '#c3b091', 'mint' => '#98ff98', 'menthe' => '#98ff98', 'emerald' => '#2e8b57', 'emeraude' => '#2e8b57', 'emerald green' => '#2e8b57', 'lime' => '#84cc16', 'sage' => '#9caf88',
		'blue' => '#2563eb', 'bleu' => '#2563eb', 'light blue' => '#add8e6', 'bleu clair' => '#add8e6', 'sky blue' => '#87ceeb', 'baby blue' => '#a7c7e7',
		'dark blue' => '#1e3a8a', 'bleu fonce' => '#1e3a8a', 'navy' => '#0b1f60', 'navy blue' => '#0b1f60', 'marine' => '#0b1f60', 'bleu marine' => '#0b1f60', 'royal blue' => '#4169e1',
		'denim' => '#4a6fa5', 'teal' => '#0f766e', 'turquoise' => '#40e0d0', 'cyan' => '#06b6d4', 'aqua' => '#00ffff',
		'purple' => '#7e22ce', 'violet' => '#7c3aed', 'lavender' => '#c4b5fd', 'lavande' => '#c4b5fd', 'lilac' => '#c8a2c8', 'lilas' => '#c8a2c8', 'plum' => '#8e4585', 'prune' => '#8e4585', 'mauve' => '#b784a7',
		'brown' => '#7c4a1e', 'marron' => '#7c4a1e', 'brun' => '#7c4a1e', 'chocolate' => '#5c3317', 'chocolat' => '#5c3317', 'camel' => '#c19a6b', 'tan' => '#d2b48c', 'cognac' => '#9a463d', 'caramel' => '#af6f09', 'taupe' => '#8b8589', 'coffee' => '#6f4e37',
		'multicolor' => 'linear-gradient(135deg,#ef4444,#f59e0b,#22c55e,#3b82f6,#a855f7)', 'multicolour' => 'linear-gradient(135deg,#ef4444,#f59e0b,#22c55e,#3b82f6,#a855f7)', 'multicolore' => 'linear-gradient(135deg,#ef4444,#f59e0b,#22c55e,#3b82f6,#a855f7)',
		'transparent' => 'repeating-conic-gradient(#e5e7eb 0 25%,#fff 0 50%) 50%/8px 8px', 'clear' => 'repeating-conic-gradient(#e5e7eb 0 25%,#fff 0 50%) 50%/8px 8px',
	);

	/* ------------------------------------------------------------------
	 * Admin: colour field on attribute terms
	 * ------------------------------------------------------------------ */

	public static function init_admin() {
		if ( ! function_exists( 'wc_get_attribute_taxonomy_names' ) ) {
			return;
		}
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'term_assets' ) );
		foreach ( wc_get_attribute_taxonomy_names() as $taxonomy ) {
			add_action( $taxonomy . '_add_form_fields', array( __CLASS__, 'field_add' ) );
			add_action( $taxonomy . '_edit_form_fields', array( __CLASS__, 'field_edit' ) );
			add_action( 'created_' . $taxonomy, array( __CLASS__, 'save' ) );
			add_action( 'edited_' . $taxonomy, array( __CLASS__, 'save' ) );
		}
	}

	/** Media picker for the "Swatch image" field on attribute term screens. */
	public static function term_assets() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || empty( $screen->taxonomy ) || 0 !== strpos( $screen->taxonomy, 'pa_' ) ) {
			return;
		}
		wp_enqueue_media();
		$js = <<<'NEXVIOJS'
jQuery(function($){
	var frame;
	$(document).on('click','.nexvio-swimg-pick',function(e){
		e.preventDefault();
		var w=$(this).closest('.nexvio-swimg');
		frame=wp.media({title:w.data('title'),multiple:false,library:{type:'image'}});
		frame.on('select',function(){
			var a=frame.state().get('selection').first().toJSON();
			var url=a.sizes&&a.sizes.thumbnail?a.sizes.thumbnail.url:a.url;
			w.find('input').val(a.id);
			w.find('.nexvio-swimg-preview').empty().append($('<img alt="">').attr('src',url).css({width:48,height:48,borderRadius:'50%',objectFit:'cover'}));
			w.find('.nexvio-swimg-remove').show();
		});
		frame.open();
	});
	$(document).on('click','.nexvio-swimg-remove',function(e){
		e.preventDefault();
		var w=$(this).closest('.nexvio-swimg');
		w.find('input').val('');w.find('.nexvio-swimg-preview').empty();$(this).hide();
	});
	$(document).ajaxComplete(function(ev,x,o){
		if(o&&o.data&&String(o.data).indexOf('action=add-tag')>-1){$('.nexvio-swimg input').val('');$('.nexvio-swimg-preview').empty();$('.nexvio-swimg-remove').hide();}
	});
});
NEXVIOJS;
		wp_add_inline_script( 'jquery-core', $js );
	}

	private static function image_field( $value ) {
		$url = $value ? wp_get_attachment_image_url( (int) $value, 'thumbnail' ) : '';
		echo '<div class="nexvio-swimg" data-title="' . esc_attr__( 'Swatch image', 'nexvio' ) . '" style="display:flex;align-items:center;gap:10px">';
		echo '<span class="nexvio-swimg-preview">' . ( $url ? '<img src="' . esc_url( $url ) . '" alt="" style="width:48px;height:48px;border-radius:50%;object-fit:cover">' : '' ) . '</span>';
		echo '<input type="hidden" name="nexvio_swatch_image" value="' . esc_attr( $value ? (int) $value : '' ) . '">';
		echo '<button type="button" class="button nexvio-swimg-pick">' . esc_html__( 'Choose image', 'nexvio' ) . '</button>';
		echo '<button type="button" class="button-link nexvio-swimg-remove"' . ( $url ? '' : ' style="display:none"' ) . '>' . esc_html__( 'Remove', 'nexvio' ) . '</button></div>';
	}

	public static function field_add() {
		wp_nonce_field( 'nexvio_swatch', 'nexvio_swatch_nonce' );
		?>
		<div class="form-field">
			<label for="nexvio-swatch"><?php esc_html_e( 'Swatch colour', 'nexvio' ); ?></label>
			<input type="color" id="nexvio-swatch" name="nexvio_swatch_color" value="#cccccc" style="width:64px;height:34px;padding:2px">
			<label style="display:inline-block;margin-left:8px;font-weight:400"><input type="checkbox" name="nexvio_swatch_none" value="1" checked> <?php esc_html_e( 'No colour (use the name)', 'nexvio' ); ?></label>
			<p class="description"><?php esc_html_e( 'Used by Nexvio colour swatches on product cards and in filters.', 'nexvio' ); ?></p>
		</div>
		<div class="form-field">
			<label><?php esc_html_e( 'Swatch image', 'nexvio' ); ?></label>
			<?php self::image_field( 0 ); ?>
			<p class="description"><?php esc_html_e( 'Optional: a small picture (pattern, fabric, print). Shown instead of the colour.', 'nexvio' ); ?></p>
		</div>
		<?php
	}

	public static function field_edit( $term ) {
		$value = get_term_meta( $term->term_id, self::META, true );
		?>
		<tr class="form-field">
			<th scope="row"><label for="nexvio-swatch"><?php esc_html_e( 'Swatch colour', 'nexvio' ); ?></label></th>
			<td>
				<?php wp_nonce_field( 'nexvio_swatch', 'nexvio_swatch_nonce' ); ?>
				<input type="color" id="nexvio-swatch" name="nexvio_swatch_color" value="<?php echo esc_attr( $value ? $value : '#cccccc' ); ?>" style="width:64px;height:34px;padding:2px">
				<label style="margin-left:8px"><input type="checkbox" name="nexvio_swatch_none" value="1"<?php checked( '' === (string) $value ); ?>> <?php esc_html_e( 'No colour (use the name)', 'nexvio' ); ?></label>
				<p class="description"><?php esc_html_e( 'Used by Nexvio colour swatches on product cards and in filters.', 'nexvio' ); ?></p>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row"><?php esc_html_e( 'Swatch image', 'nexvio' ); ?></th>
			<td>
				<?php self::image_field( (int) get_term_meta( $term->term_id, self::IMAGE, true ) ); ?>
				<p class="description"><?php esc_html_e( 'Optional: a small picture (pattern, fabric, print). Shown instead of the colour.', 'nexvio' ); ?></p>
			</td>
		</tr>
		<?php
	}

	public static function save( $term_id ) {
		if ( ! isset( $_POST['nexvio_swatch_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['nexvio_swatch_nonce'] ) ), 'nexvio_swatch' ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_product_terms' ) && ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		$image = isset( $_POST['nexvio_swatch_image'] ) ? absint( wp_unslash( $_POST['nexvio_swatch_image'] ) ) : 0;
		if ( $image ) {
			update_term_meta( $term_id, self::IMAGE, $image );
		} else {
			delete_term_meta( $term_id, self::IMAGE );
		}
		$none  = ! empty( $_POST['nexvio_swatch_none'] );
		$color = isset( $_POST['nexvio_swatch_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['nexvio_swatch_color'] ) ) : '';
		if ( $none || ! $color ) {
			delete_term_meta( $term_id, self::META );
		} else {
			update_term_meta( $term_id, self::META, $color );
		}
	}

	/* ------------------------------------------------------------------
	 * Colours
	 * ------------------------------------------------------------------ */

	private static function normalize( $text ) {
		$t = strtolower( remove_accents( (string) $text ) );
		return trim( preg_replace( '/[\s_\-]+/', ' ', $t ) );
	}

	/** Swatch picture of a term: this plugin's field, or the fields other swatch plugins commonly use. */
	public static function image_for( $term_id, $size = 'thumbnail' ) {
		if ( ! $term_id ) {
			return '';
		}
		foreach ( array( self::IMAGE, 'product_attribute_image', 'swatch_image', 'image', 'thumbnail_id' ) as $key ) {
			$v = get_term_meta( (int) $term_id, $key, true );
			if ( is_numeric( $v ) && (int) $v > 0 ) {
				$url = wp_get_attachment_image_url( (int) $v, $size );
				if ( $url ) {
					return $url;
				}
			}
		}
		return '';
	}

	/** CSS background for a value: term colour, known swatch fields, hex in the name, or colour names. */
	public static function color_for( $name, $term_id = 0 ) {
		if ( $term_id ) {
			foreach ( array( self::META, 'color', 'product_attribute_color', 'swatch_color', 'pa_color', 'term_color' ) as $key ) {
				$v = get_term_meta( $term_id, $key, true );
				if ( is_string( $v ) && preg_match( '/^#?[0-9a-f]{3}(?:[0-9a-f]{3})?$/i', trim( $v ) ) ) {
					return '#' . ltrim( trim( $v ), '#' );
				}
			}
		}
		if ( preg_match( '/#?\b([0-9a-f]{6}|[0-9a-f]{3})\b/i', (string) $name, $m ) && preg_match( '/[0-9]/', $m[1] ) ) {
			return '#' . $m[1];
		}
		$n = self::normalize( $name );
		if ( isset( self::$names[ $n ] ) ) {
			return self::$names[ $n ];
		}
		/* "Dark Navy Blue", "Bleu marine foncé" → try the last / first two words, then single words */
		$words = explode( ' ', $n );
		$tries = array();
		if ( count( $words ) >= 2 ) {
			$tries[] = implode( ' ', array_slice( $words, -2 ) );
			$tries[] = implode( ' ', array_slice( $words, 0, 2 ) );
		}
		$tries = array_merge( $tries, array_reverse( $words ) );
		foreach ( $tries as $t ) {
			if ( isset( self::$names[ $t ] ) ) {
				return self::$names[ $t ];
			}
		}
		return '';
	}

	/* ------------------------------------------------------------------
	 * Product data
	 * ------------------------------------------------------------------ */

	/** The product's colour attribute: [ key, WC_Product_Attribute ] or null. */
	private static function find_attribute( $product, $slug ) {
		$attributes = $product->get_attributes();
		if ( ! $attributes ) {
			return null;
		}
		$slug = sanitize_title( (string) $slug );
		foreach ( $attributes as $key => $attr ) {
			if ( ! $attr instanceof WC_Product_Attribute || ! $attr->get_visible() && ! $attr->get_variation() ) {
				continue;
			}
			$name = $attr->get_name();
			if ( $slug ) {
				if ( in_array( $slug, array( sanitize_title( $name ), 'pa_' . $slug, sanitize_title( $key ) ), true ) || 'pa_' . $slug === $name ) {
					return array( $key, $attr );
				}
				continue;
			}
			$hay = self::normalize( $attr->is_taxonomy() ? wc_attribute_label( $name ) . ' ' . $name : $name );
			foreach ( self::$keywords as $word ) {
				if ( preg_match( '/\b' . $word . 's?\b/', $hay ) ) {
					return array( $key, $attr );
				}
			}
		}
		return null;
	}

	/**
	 * Swatches of a product.
	 * Returns [ 'label' => attribute label, 'items' => [ [name, color, img, srcset, link], ... ] ].
	 */
	public static function data( $product, $args ) {
		$found = self::find_attribute( $product, isset( $args['attr'] ) ? $args['attr'] : '' );
		if ( ! $found ) {
			return null;
		}
		list( $key, $attr ) = $found;
		$is_tax   = $attr->is_taxonomy();
		$taxonomy = $is_tax ? $attr->get_name() : '';
		$var_key  = 'attribute_' . ( $is_tax ? $taxonomy : sanitize_title( $attr->get_name() ) );
		$size     = ! empty( $args['size'] ) ? $args['size'] : 'woocommerce_thumbnail';
		$link     = $product->get_permalink();

		/* values in their admin order */
		$values = array();
		if ( $is_tax ) {
			$terms = wc_get_product_terms( $product->get_id(), $taxonomy, array( 'fields' => 'all' ) );
			foreach ( (array) $terms as $t ) {
				$values[ $t->slug ] = array( 'name' => $t->name, 'term_id' => (int) $t->term_id );
			}
		} else {
			foreach ( $attr->get_options() as $opt ) {
				$values[ (string) $opt ] = array( 'name' => (string) $opt, 'term_id' => 0 );
			}
		}
		if ( ! $values ) {
			return null;
		}

		/* variation image for each colour */
		$images = array();
		if ( $product->is_type( 'variable' ) && $attr->get_variation() ) {
			foreach ( $product->get_children() as $child_id ) {
				$variation = wc_get_product( $child_id );
				if ( ! $variation || ! $variation->is_purchasable() || 'publish' !== get_post_status( $child_id ) ) {
					continue;
				}
				$va  = $variation->get_attributes();
				$val = isset( $va[ $key ] ) ? (string) $va[ $key ] : '';
				if ( '' === $val ) {
					continue;
				}
				if ( ! $is_tax ) {
					/* custom attributes store the option text */
					foreach ( array_keys( $values ) as $opt ) {
						if ( sanitize_title( $opt ) === sanitize_title( $val ) ) {
							$val = $opt;
						}
					}
				}
				$img_id = $variation->get_image_id();
				if ( $img_id && ! isset( $images[ $val ] ) ) {
					$images[ $val ] = $img_id; /* first variation of this colour that has an image */
				}
			}
		} elseif ( empty( $args['simple'] ) ) {
			return null;
		}

		$items = array();
		foreach ( $values as $value => $v ) {
			$img_id  = isset( $images[ $value ] ) ? $images[ $value ] : 0;
			$items[] = array(
				'name'   => $v['name'],
				'color'  => self::color_for( $v['name'], $v['term_id'] ),
				'swatch' => self::image_for( $v['term_id'] ),
				'img'    => $img_id ? (string) wp_get_attachment_image_url( $img_id, $size ) : '',
				'srcset' => $img_id ? (string) wp_get_attachment_image_srcset( $img_id, $size ) : '',
				'link'   => $product->is_type( 'variable' ) ? add_query_arg( $var_key, $is_tax ? $value : rawurlencode( $value ), $link ) : '',
			);
		}

		return array(
			'label' => $is_tax ? wc_attribute_label( $taxonomy ) : $attr->get_name(),
			'items' => $items,
		);
	}
}
