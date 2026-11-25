<?php
/**
 * NEXVIO Related Products - Elementor widget.
 *
 * Same card design as the Product Slider. On a single product page it
 * shows other products from the same category, as a slider or a grid.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;

class Nexvio_Product_Related_Widget extends Nexvio_Product_Slider_Widget {

	/** Cached result for the current render. */
	private $related = null;

	public function get_name() {
		return 'nexvio_product_related';
	}

	public function get_title() {
		return esc_html__( 'Related Products Layouts', 'nexvio' );
	}

	/** Gallery hover controls (see the card trait). */
	protected function gallery_ui() {
		return 'full';
	}


	public function get_icon() {
		return 'eicon-product-related';
	}

	public function get_keywords() {
		return array( 'nexvio', 'woocommerce', 'related', 'products', 'similar', 'category', 'slider', 'grid' );
	}

	/* ------------------------------------------------------------------
	 * Controls
	 * ------------------------------------------------------------------ */

	protected function register_controls() {
		$this->register_design_section();
		$this->register_header_section();
		$this->register_related_section();
		$this->register_card_sections();
		$this->register_layout_section();
		$this->register_slider_section( array( 'condition' => array( 'layout' => 'slider' ) ) );
		$this->register_gs_card_styles();
		$this->register_header_style_section();
		$this->register_nav_style_section( array( 'condition' => array( 'layout' => 'slider' ) ) );
		$this->limit_classic_controls();

		/* related-specific defaults for shared controls */
		$this->update_control( 'title', array( 'default' => 'You may also like' ) );
		$this->update_control( 'subtitle', array( 'default' => 'More products from the same collection.' ) );
		$this->update_control( 'all_text', array( 'default' => 'View collection' ) );
		$this->update_control( 'all_link', array(
			'default'     => array( 'url' => '' ),
			'description' => esc_html__( 'Leave empty to link automatically to the category used for the related products.', 'nexvio' ),
		) );
	}

	protected function register_related_section() {
		$this->start_controls_section( 'section_related', array( 'label' => esc_html__( 'Related products', 'nexvio' ) ) );

		$this->add_control( 'related_note', array(
			'type'            => Controls_Manager::RAW_HTML,
			'raw'             => esc_html__( 'Place this widget on the single product page (Elementor Pro: Theme Builder > Single Product). It shows other products from the same category as the product being viewed.', 'nexvio' ),
			'content_classes' => 'elementor-descriptor',
		) );

		$this->add_control( 'related_by', array(
			'label'   => esc_html__( 'Use which category', 'nexvio' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'specific',
			'options' => array(
				'specific' => esc_html__( 'Most specific category (e.g. a sub-category)', 'nexvio' ),
				'primary'  => esc_html__( 'Primary category (Yoast / Rank Math), else most specific', 'nexvio' ),
				'top'      => esc_html__( 'Top-level category (e.g. Singles)', 'nexvio' ),
				'all'      => esc_html__( 'All categories of the product', 'nexvio' ),
			),
		) );

		$this->switcher( 'fill_parent', esc_html__( 'Not enough products? Fill from the parent category', 'nexvio' ), 'yes', array(
			'condition' => array( 'related_by!' => array( 'top', 'all' ) ),
		) );

		$this->add_control( 'exclude_cats', array(
			'label'       => esc_html__( 'Never use these categories', 'nexvio' ),
			'type'        => Controls_Manager::SELECT2,
			'multiple'    => true,
			'options'     => $this->term_options( 'product_cat' ),
			'label_block' => true,
			'description' => esc_html__( 'For example a broad category like "Sale" or "New arrivals".', 'nexvio' ),
		) );

		$this->add_control( 'limit', array(
			'label'     => esc_html__( 'Number of products', 'nexvio' ),
			'type'      => Controls_Manager::NUMBER,
			'min'       => 1,
			'max'       => 40,
			'default'   => 8,
			'separator' => 'before',
		) );

		$this->add_control( 'orderby', array(
			'label'   => esc_html__( 'Order by', 'nexvio' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'popularity',
			'options' => array(
				'popularity' => esc_html__( 'Most popular (sales)', 'nexvio' ),
				'rating'     => esc_html__( 'Best rated', 'nexvio' ),
				'rand'       => esc_html__( 'Random', 'nexvio' ),
				'date'       => esc_html__( 'Newest', 'nexvio' ),
				'price'      => esc_html__( 'Price', 'nexvio' ),
				'title'      => esc_html__( 'Name', 'nexvio' ),
				'menu_order' => esc_html__( 'Menu order', 'nexvio' ),
			),
		) );

		$this->add_control( 'order', array(
			'label'     => esc_html__( 'Order', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'DESC',
			'options'   => array( 'DESC' => esc_html__( 'Descending', 'nexvio' ), 'ASC' => esc_html__( 'Ascending', 'nexvio' ) ),
			'condition' => array( 'orderby!' => array( 'rand', 'popularity', 'rating' ) ),
		) );

		$this->switcher( 'hide_out', esc_html__( 'Hide sold-out products', 'nexvio' ), 'yes' );

		$this->add_control( 'preview_id', array(
			'label'       => esc_html__( 'Editor preview: product ID', 'nexvio' ),
			'type'        => Controls_Manager::NUMBER,
			'min'         => 0,
			'default'     => '',
			'separator'   => 'before',
			'description' => esc_html__( 'Only used in the Elementor editor when no product is being viewed. Empty = latest product.', 'nexvio' ),
		) );

		$this->add_control( 'empty_hide', array(
			'label'        => esc_html__( 'No related products: hide the widget', 'nexvio' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		) );

		$this->end_controls_section();
	}

	protected function register_layout_section() {
		$this->start_controls_section( 'section_layout', array( 'label' => esc_html__( 'Layout', 'nexvio' ) ) );
		$this->add_control( 'layout', array(
			'label'   => esc_html__( 'Layout', 'nexvio' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'slider',
			'options' => array(
				'slider' => esc_html__( 'Slider (like the main product carousel)', 'nexvio' ),
				'grid'   => esc_html__( 'Grid', 'nexvio' ),
			),
		) );
		$this->add_responsive_control( 'columns', array(
			'label'          => esc_html__( 'Columns', 'nexvio' ),
			'type'           => Controls_Manager::NUMBER,
			'min'            => 1,
			'max'            => 8,
			'default'        => 4,
			'tablet_default' => 3,
			'mobile_default' => 2,
			'selectors'      => array( '{{WRAPPER}} .nexvio-pg' => '--nexvio-pg-cols: {{VALUE}};' ),
			'condition'      => array( 'layout' => 'grid' ),
		) );
		$this->add_responsive_control( 'col_gap', array(
			'label'          => esc_html__( 'Column gap', 'nexvio' ),
			'type'           => Controls_Manager::SLIDER,
			'size_units'     => array( 'px' ),
			'range'          => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
			'default'        => array( 'size' => 18, 'unit' => 'px' ),
			'mobile_default' => array( 'size' => 10, 'unit' => 'px' ),
			'selectors'      => array( '{{WRAPPER}} .nexvio-pg' => '--nexvio-pg-col-gap: {{SIZE}}{{UNIT}};' ),
			'condition'      => array( 'layout' => 'grid' ),
		) );
		$this->add_responsive_control( 'row_gap', array(
			'label'          => esc_html__( 'Row gap', 'nexvio' ),
			'type'           => Controls_Manager::SLIDER,
			'size_units'     => array( 'px' ),
			'range'          => array( 'px' => array( 'min' => 0, 'max' => 80 ) ),
			'default'        => array( 'size' => 22, 'unit' => 'px' ),
			'mobile_default' => array( 'size' => 12, 'unit' => 'px' ),
			'selectors'      => array( '{{WRAPPER}} .nexvio-pg' => '--nexvio-pg-row-gap: {{SIZE}}{{UNIT}};' ),
			'condition'      => array( 'layout' => 'grid' ),
		) );
		$this->end_controls_section();
	}

	/* ------------------------------------------------------------------
	 * Related query
	 * ------------------------------------------------------------------ */

	/**
	 * ID of the product being viewed. Works on the WooCommerce single product page,
	 * in Elementor Pro single product templates, in the product loop and in the editor.
	 */
	private function current_product_id( $s ) {
		if ( ! empty( $s['_product_id'] ) ) {
			return (int) $s['_product_id'];
		}
		if ( is_singular( 'product' ) ) {
			return (int) get_queried_object_id();
		}
		global $product;
		if ( $product instanceof WC_Product ) {
			return (int) $product->get_id();
		}
		$post_id = get_the_ID();
		if ( $post_id && 'product' === get_post_type( $post_id ) ) {
			return (int) $post_id;
		}
		$is_edit = class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode();
		$preview = isset( $_GET['elementor-preview'] ) || isset( $_GET['elementor_library'] ); // phpcs:ignore WordPress.Security.NonceVerification
		if ( $is_edit || $preview || wp_doing_ajax() ) {
			if ( ! empty( $s['preview_id'] ) ) {
				return (int) $s['preview_id'];
			}
			$latest = get_posts( array( 'post_type' => 'product', 'post_status' => 'publish', 'numberposts' => 1, 'fields' => 'ids' ) );
			return $latest ? (int) $latest[0] : 0;
		}
		return 0;
	}

	private function depth( $term ) {
		return count( get_ancestors( $term->term_id, 'product_cat', 'taxonomy' ) );
	}

	/**
	 * Category terms to use, most relevant first.
	 */
	private function related_terms( $product_id, $s ) {
		$terms = get_the_terms( $product_id, 'product_cat' );
		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return array();
		}
		$default = (int) get_option( 'default_product_cat', 0 );
		$exclude = ! empty( $s['exclude_cats'] ) ? array_filter( (array) $s['exclude_cats'] ) : array();

		/* drop the categories the user excluded */
		$terms = array_values( array_filter( $terms, function ( $t ) use ( $exclude ) {
			return ! in_array( $t->slug, $exclude, true );
		} ) );
		if ( ! $terms ) {
			return array();
		}

		/*
		 * Prefer real categories over "Uncategorized", but if "Uncategorized" is the
		 * product's only category, use it (products in it are still related).
		 */
		$real = array_values( array_filter( $terms, function ( $t ) use ( $default ) {
			return (int) $t->term_id !== $default;
		} ) );
		if ( $real ) {
			$terms = $real;
		}

		$by = isset( $s['related_by'] ) ? $s['related_by'] : 'specific';

		if ( 'all' === $by ) {
			return $terms;
		}

		if ( 'primary' === $by ) {
			foreach ( array( '_yoast_wpseo_primary_product_cat', 'rank_math_primary_product_cat' ) as $key ) {
				$primary = (int) get_post_meta( $product_id, $key, true );
				foreach ( $terms as $t ) {
					if ( $primary && (int) $t->term_id === $primary ) {
						return array( $t );
					}
				}
			}
			$by = 'specific';
		}

		$self = $this;
		usort( $terms, function ( $a, $b ) use ( $self ) {
			return $self->term_depth( $b ) - $self->term_depth( $a );
		} );

		if ( 'top' === $by ) {
			$t   = end( $terms );
			$anc = get_ancestors( $t->term_id, 'product_cat', 'taxonomy' );
			if ( $anc ) {
				$root = get_term( end( $anc ), 'product_cat' );
				if ( $root && ! is_wp_error( $root ) && ! in_array( $root->slug, $exclude, true ) ) {
					return array( $root );
				}
			}
			return array( $t );
		}

		return array( $terms[0] );
	}

	public function term_depth( $term ) {
		return $this->depth( $term );
	}

	/**
	 * Product IDs in the given categories (sub-categories included), using WP_Query
	 * with term IDs, like WooCommerce's own related products.
	 */
	private function query_related( $term_ids, $exclude_ids, $limit, $s ) {
		if ( ! $term_ids || $limit < 1 ) {
			return array();
		}

		$tax_query = array(
			'relation' => 'AND',
			array(
				'taxonomy'         => 'product_cat',
				'field'            => 'term_id',
				'terms'            => array_map( 'intval', $term_ids ),
				'include_children' => true,
				'operator'         => 'IN',
			),
		);

		/* hidden from catalog, and sold out if asked (same as WooCommerce) */
		$hide = array();
		if ( function_exists( 'wc_get_product_visibility_term_ids' ) ) {
			$vis = wc_get_product_visibility_term_ids();
			if ( ! empty( $vis['exclude-from-catalog'] ) ) {
				$hide[] = (int) $vis['exclude-from-catalog'];
			}
			if ( 'yes' === $s['hide_out'] && ! empty( $vis['outofstock'] ) ) {
				$hide[] = (int) $vis['outofstock'];
			}
		}
		if ( $hide ) {
			$tax_query[] = array(
				'taxonomy' => 'product_visibility',
				'field'    => 'term_taxonomy_id',
				'terms'    => $hide,
				'operator' => 'NOT IN',
			);
		}

		$args = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'posts_per_page'      => (int) $limit + count( $exclude_ids ),
			'tax_query'           => $tax_query, // phpcs:ignore WordPress.DB.SlowDBQuery
			'fields'              => 'ids',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'nexvio_related'         => true,
		);

		$order = 'ASC' === $s['order'] ? 'ASC' : 'DESC';
		$meta  = array(
			'popularity' => 'total_sales',
			'price'      => '_price',
			'rating'     => '_wc_average_rating',
		);
		$orderby = isset( $s['orderby'] ) ? $s['orderby'] : 'popularity';

		if ( isset( $meta[ $orderby ] ) ) {
			/* products without the meta are kept (sorted last) */
			$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery
				'relation'  => 'OR',
				'nexvio_sort'  => array( 'key' => $meta[ $orderby ], 'compare' => 'EXISTS', 'type' => 'NUMERIC' ),
				'nexvio_empty' => array( 'key' => $meta[ $orderby ], 'compare' => 'NOT EXISTS' ),
			);
			$dir             = 'popularity' === $orderby || 'rating' === $orderby ? 'DESC' : $order;
			$args['orderby'] = array( 'nexvio_sort' => $dir, 'date' => 'DESC' );
		} elseif ( 'rand' === $orderby ) {
			$args['orderby'] = 'rand';
		} else {
			$args['orderby'] = $orderby;
			$args['order']   = 'title' === $orderby || 'menu_order' === $orderby ? ( 'DESC' === $order && 'title' === $orderby ? 'DESC' : 'ASC' ) : $order;
		}

		$args = apply_filters( 'nexvio_related_query_args', $args, $s );
		$q   = new WP_Query( $args );
		$ids = array_diff( array_map( 'intval', (array) $q->posts ), array_map( 'intval', $exclude_ids ) );
		return array_slice( array_values( $ids ), 0, (int) $limit );
	}

	private function compute( $s ) {
		$result = array( 'products' => array(), 'slugs' => array(), 'names' => array(), 'link' => '', 'product_id' => 0 );
		$pid    = $this->current_product_id( $s );
		if ( ! $pid ) {
			return $result;
		}
		$result['product_id'] = $pid;
		$limit = max( 1, (int) $s['limit'] );
		$terms = $this->related_terms( $pid, $s );
		if ( ! $terms ) {
			return $result;
		}

		$ids = $this->query_related( wp_list_pluck( $terms, 'term_id' ), array( $pid ), $limit, $s );

		/* fill from the parent category (still category based) */
		if ( count( $ids ) < $limit && 'yes' === $s['fill_parent'] && in_array( $s['related_by'], array( 'specific', 'primary' ), true ) ) {
			$parent_id = (int) $terms[0]->parent;
			$exclude   = ! empty( $s['exclude_cats'] ) ? (array) $s['exclude_cats'] : array();
			while ( $parent_id && count( $ids ) < $limit ) {
				$parent = get_term( $parent_id, 'product_cat' );
				if ( ! $parent || is_wp_error( $parent ) || in_array( $parent->slug, $exclude, true ) ) {
					break;
				}
				$more      = $this->query_related( array( $parent->term_id ), array_merge( array( $pid ), $ids ), $limit - count( $ids ), $s );
				$ids       = array_merge( $ids, $more );
				$parent_id = (int) $parent->parent;
			}
		}

		$products = array();
		foreach ( $ids as $id ) {
			$p = wc_get_product( $id );
			if ( $p && $p->is_visible() ) {
				$products[] = $p;
			}
		}

		$result['products'] = $products;
		$result['slugs']    = wp_list_pluck( $terms, 'slug' );
		$result['names']    = wp_list_pluck( $terms, 'name' );
		$link               = get_term_link( $terms[0] );
		$result['link']     = is_wp_error( $link ) ? '' : $link;
		return $result;
	}

	/* ------------------------------------------------------------------
	 * Hooks used by the parent slider render
	 * ------------------------------------------------------------------ */

	protected function prepare_settings( $s ) {
		if ( null === $this->related ) {
			$this->related = $this->compute( $s );
		}
		if ( empty( $s['all_link']['url'] ) && $this->related['link'] ) {
			$s['all_link'] = array( 'url' => $this->related['link'], 'is_external' => '', 'nofollow' => '' );
		}
		$s['source']     = 'related';
		$s['categories'] = $this->related['slugs'];
		return $s;
	}

	protected function get_products( $s ) {
		return $this->related ? $this->related['products'] : array();
	}

	protected function context_cats( $s ) {
		return $this->related ? $this->related['slugs'] : array();
	}

	/** Small info line shown only in the editor. */
	private function editor_info() {
		if ( ! ( class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode() ) || ! $this->related ) {
			return '';
		}
		$r = $this->related;
		if ( ! $r['product_id'] ) {
			return '';
		}
		$txt = sprintf(
			/* translators: 1: product name, 2: categories, 3: number */
			esc_html__( 'Preview for "%1$s" · category: %2$s · %3$d related products', 'nexvio' ),
			esc_html( get_the_title( $r['product_id'] ) ),
			esc_html( $r['names'] ? implode( ', ', $r['names'] ) : '—' ),
			count( $r['products'] )
		);
		return '<p class="nexvio-pg__preview-note">' . $txt . '</p>';
	}

	protected function empty_message() {
		if ( $this->related && ! $this->related['product_id'] ) {
			return esc_html__( 'No product found to preview. Set "Editor preview: product ID" or open this template on a product.', 'nexvio' );
		}
		$names = $this->related && $this->related['names'] ? implode( ', ', $this->related['names'] ) : '—';
		/* translators: %s: category name(s) */
		return esc_html( sprintf( __( 'No other product found in the category "%s". The widget is hidden on the live site. Turn on "Fill from the parent category" or add more products to this category.', 'nexvio' ), $names ) );
	}

	/* ------------------------------------------------------------------
	 * Output
	 * ------------------------------------------------------------------ */

	protected function render() {
		$this->related = null;
		$s             = $this->get_settings_for_display();

		if ( 'grid' !== $s['layout'] ) {
			ob_start();
			parent::render();
			$html = ob_get_clean();
			echo wp_kses( $this->editor_info() . $html, Nexvio_Plugin::allowed_html() );
			return;
		}

		$s        = $this->prepare_settings( $s );
		$products = $this->get_products( $s );
		$is_edit  = class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode();

		if ( empty( $products ) ) {
			if ( $is_edit || 'yes' !== $s['empty_hide'] ) {
				echo wp_kses( '<div class="nexvio-ps"><div class="nexvio-ps__empty">' . $this->empty_message() . '</div></div>', Nexvio_Plugin::allowed_html() );
			}
			return;
		}

		if ( wp_script_is( 'wc-add-to-cart', 'registered' ) ) {
			wp_enqueue_script( 'wc-add-to-cart' );
		}

		$classes   = array( 'nexvio-ps', 'nexvio-pg', 'nexvio-pr' );
		$classes[] = 'cover' === $s['image_mode'] ? 'nexvio-ps--cover' : 'nexvio-ps--contain';
		if ( 'yes' !== $s['pattern'] ) {
			$classes[] = 'nexvio-ps--no-pattern';
		}
		if ( 'yes' === $s['frame'] ) {
			$classes[] = 'nexvio-ps--frame';
		}
		if ( 'yes' === $s['img_shadow'] ) {
			$classes[] = 'nexvio-ps--shadow';
		}
		if ( in_array( $s['hover_fx'], array( 'lift', 'zoom' ), true ) ) {
			$classes[] = 'nexvio-ps--hover-' . $s['hover_fx'];
		}

		$layout_classes = $this->layout_classes( $s );
		if ( in_array( 'nexvio-ps--l', $layout_classes, true ) ) {
			/* classic-only looks (gold frame, hexagon pattern, tilt) do not apply to the new designs */
			$classes = array_values( array_diff( $classes, array( 'nexvio-ps--contain', 'nexvio-ps--cover', 'nexvio-ps--no-pattern', 'nexvio-ps--frame', 'nexvio-ps--shadow', 'nexvio-ps--hover-lift', 'nexvio-ps--hover-zoom' ) ) );
		}
		$classes = array_merge( $classes, $layout_classes );

		$s['_ctx_cats'] = $this->context_cats( $s );
		$rules          = is_array( $s['color_rules'] ) ? $s['color_rules'] : array();
		$tag            = in_array( $s['title_tag'], array( 'h1', 'h2', 'h3', 'h4', 'div' ), true ) ? $s['title_tag'] : 'h2';
		$show_all       = 'yes' === $s['show_all'] && ! empty( $s['all_link']['url'] );
		if ( $show_all ) {
			$this->add_link_attributes( 'all_link', $s['all_link'] );
			$this->add_render_attribute( 'all_link', 'class', 'nexvio-ps__all' );
		}
		$cols = max( 1, (int) $s['columns'] );
		$js   = array( 'wish' => $this->wish_settings( $s ) );
		echo wp_kses( $this->editor_info(), Nexvio_Plugin::allowed_html() );
		?>
		<section class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-settings="<?php echo esc_attr( wp_json_encode( $js ) ); ?>">
			<?php if ( 'yes' === $s['show_header'] ) : ?>
				<div class="nexvio-ps__head">
					<div class="nexvio-ps__titles">
						<?php if ( '' !== trim( (string) $s['title'] ) ) : ?>
							<<?php echo esc_html( $tag ); ?> class="nexvio-ps__title"><?php echo esc_html( $s['title'] ); ?></<?php echo esc_html( $tag ); ?>>
						<?php endif; ?>
						<?php if ( '' !== trim( (string) $s['subtitle'] ) ) : ?>
							<p class="nexvio-ps__subtitle"><?php echo esc_html( $s['subtitle'] ); ?></p>
						<?php endif; ?>
					</div>
					<?php if ( $show_all ) : ?>
						<div class="nexvio-ps__tools"><a <?php $this->print_render_attribute_string( 'all_link' ); ?>><?php echo esc_html( $s['all_text'] ); ?></a></div>
					<?php endif; ?>
				</div>
			<?php endif; ?>
			<ul class="products nexvio-pg__grid columns-<?php echo esc_attr( $cols ); ?>">
				<?php
				foreach ( $products as $product ) {
					if ( $product instanceof WC_Product ) {
						$this->render_card( $product, $s, $rules, 'li' );
					}
				}
				?>
			</ul>
		</section>
		<?php
	}
}
