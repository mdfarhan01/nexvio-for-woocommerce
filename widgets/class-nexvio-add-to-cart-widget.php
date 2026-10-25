<?php
/**
 * NEXVIO Add to Cart - Elementor widget for the single product page.
 *
 * Quantity stepper + "Ajouter au panier" button + wishlist heart (built-in),
 * in the Nexvio style. Simple products are added with AJAX
 * (WooCommerce wc-ajax endpoint, mini-cart fragments refreshed); other
 * product types use the native WooCommerce form, styled to match.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

class Nexvio_Add_To_Cart_Widget extends Widget_Base {

	use Nexvio_Card_Trait;

	public function get_name() {
		return 'nexvio_add_to_cart';
	}

	public function get_title() {
		return esc_html__( 'Add to Cart', 'nexvio' );
	}

	public function get_icon() {
		return 'eicon-product-add-to-cart';
	}

	public function get_categories() {
		return array( 'nexvio' );
	}

	public function get_keywords() {
		return array( 'nexvio', 'woocommerce', 'add to cart', 'cart', 'quantity', 'wishlist', 'single product' );
	}

	public function get_style_depends() {
		return array( 'nexvio', 'nexvio-add-to-cart' );
	}

	public function get_script_depends() {
		return array( 'nexvio', 'nexvio-add-to-cart' );
	}

	/* ------------------------------------------------------------------
	 * Controls
	 * ------------------------------------------------------------------ */

	protected function register_controls() {

		/* ============ PRODUCT ============ */
		$this->start_controls_section( 'section_product', array( 'label' => esc_html__( 'Product', 'nexvio' ) ) );
		$this->add_control( 'product_note', array(
			'type'            => Controls_Manager::RAW_HTML,
			'raw'             => esc_html__( 'Place this widget in the single product template. It uses the product being viewed.', 'nexvio' ),
			'content_classes' => 'elementor-descriptor',
		) );
		$this->add_control( 'product_id', array(
			'label'       => esc_html__( 'Fixed product ID (optional)', 'nexvio' ),
			'type'        => Controls_Manager::NUMBER,
			'min'         => 0,
			'default'     => '',
			'description' => esc_html__( 'Leave empty to use the current product. In the editor, empty = latest product.', 'nexvio' ),
		) );
		$this->end_controls_section();

		/* ============ ELEMENTS ============ */
		$this->start_controls_section( 'section_elements', array( 'label' => esc_html__( 'Elements', 'nexvio' ) ) );
		$this->switcher( 'show_qty', esc_html__( 'Quantity selector', 'nexvio' ) );
		$this->text( 'qty_label', esc_html__( 'Quantity label (screen readers)', 'nexvio' ), 'Quantity', array( 'condition' => array( 'show_qty' => 'yes' ) ) );

		$this->add_control( 'wish_mode', array(
			'label'     => esc_html__( 'Wishlist heart', 'nexvio' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'wishlist',
			'options'   => array(
				'wishlist' => esc_html__( 'Show (built-in wishlist)', 'nexvio' ),
				'none'     => esc_html__( 'Hide', 'nexvio' ),
			),
			'separator' => 'before',
		) );
		$this->text( 'wish_label', esc_html__( 'Heart label (screen readers)', 'nexvio' ), 'Add to wishlist', array( 'condition' => array( 'wish_mode!' => 'none' ) ) );
		$this->switcher( 'wish_toast', esc_html__( 'Show message after heart click', 'nexvio' ), 'yes', array( 'condition' => array( 'wish_mode!' => 'none' ) ) );
		$wc = array( 'wish_mode!' => 'none', 'wish_toast' => 'yes' );
		$this->text( 'wish_added', esc_html__( 'Added message', 'nexvio' ), 'Added to your wishlist', array( 'condition' => $wc ) );
		$this->text( 'wish_removed', esc_html__( 'Removed message', 'nexvio' ), 'Removed from your wishlist', array( 'condition' => $wc ) );
		$this->text( 'wish_view', esc_html__( '"View wishlist" link text', 'nexvio' ), 'View my wishlist', array( 'condition' => $wc ) );
		$this->text( 'wish_error', esc_html__( 'Error message', 'nexvio' ), 'Something went wrong, please try again.', array( 'condition' => $wc ) );
		$this->end_controls_section();

		/* ============ TEXTS & BEHAVIOUR ============ */
		$this->start_controls_section( 'section_texts', array( 'label' => esc_html__( 'Button texts and behaviour', 'nexvio' ) ) );
		$this->text( 'txt_add', esc_html__( 'Button', 'nexvio' ), 'Add to cart' );
		$this->text( 'txt_pre', esc_html__( 'Button (pre-order / backorder)', 'nexvio' ), 'Pre-order' );
		$this->text( 'pre_slug', esc_html__( 'Pre-order tag or category slug', 'nexvio' ), 'precommande' );
		$this->text( 'txt_out', esc_html__( 'Button (sold out)', 'nexvio' ), 'Sold out' );
		$this->text( 'txt_adding', esc_html__( 'While adding', 'nexvio' ), 'Adding…' );
		$this->text( 'txt_added', esc_html__( 'After adding', 'nexvio' ), 'Added to cart' );
		$this->text( 'txt_error', esc_html__( 'Error', 'nexvio' ), 'Could not add this product. Please try again.' );
		$this->switcher( 'show_cart_link', esc_html__( 'Show "view cart" link after adding', 'nexvio' ), 'yes', array( 'separator' => 'before' ) );
		$this->text( 'txt_cart', esc_html__( '"View cart" text', 'nexvio' ), 'View cart', array( 'condition' => array( 'show_cart_link' => 'yes' ) ) );
		$this->add_control( 'ajax', array(
			'label'        => esc_html__( 'Add to cart without reloading (AJAX)', 'nexvio' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
			'separator'    => 'before',
		) );
		$this->add_control( 'redirect', array(
			'label'   => esc_html__( 'After adding', 'nexvio' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'wc',
			'options' => array(
				'wc'   => esc_html__( 'Follow WooCommerce setting', 'nexvio' ),
				'stay' => esc_html__( 'Stay on the page', 'nexvio' ),
				'cart' => esc_html__( 'Go to the cart', 'nexvio' ),
			),
		) );
		$this->add_control( 'side_cart', array(
			'label'       => esc_html__( 'Open the side cart after adding', 'nexvio' ),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'global',
			'options'     => array(
				'global' => esc_html__( 'Dashboard setting', 'nexvio' ),
				'open'   => esc_html__( 'Always open it', 'nexvio' ),
				'no'     => esc_html__( 'Do not open it', 'nexvio' ),
			),
			'description' => esc_html__( 'Nexvio > Add to Cart. Used when "After adding" is "Stay on the page" or follows WooCommerce without redirect.', 'nexvio' ),
		) );
		$this->add_control( 'btn_icon', array(
			'label'       => esc_html__( 'Button icon', 'nexvio' ),
			'type'        => Controls_Manager::ICONS,
			'default'     => array( 'value' => '', 'library' => '' ),
			'skin'        => 'inline',
			'label_block' => false,
			'separator'   => 'before',
		) );
		$this->end_controls_section();

		/* ============ STYLE: LAYOUT ============ */
		$this->start_controls_section( 'style_layout', array( 'label' => esc_html__( 'Layout', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_responsive_control( 'height', array(
			'label'      => esc_html__( 'Height', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 32, 'max' => 90 ) ),
			'default'    => array( 'size' => 56, 'unit' => 'px' ),
			'selectors'  => array( '{{WRAPPER}} .nexvio-atc' => '--nexvio-atc-h: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'gap', array(
			'label'      => esc_html__( 'Space between', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
			'default'    => array( 'size' => 12, 'unit' => 'px' ),
			'selectors'  => array( '{{WRAPPER}} .nexvio-atc' => '--nexvio-atc-gap: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_control( 'full', array(
			'label'        => esc_html__( 'Button fills the width', 'nexvio' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		) );
		$this->add_responsive_control( 'max_w', array(
			'label'      => esc_html__( 'Max width', 'nexvio' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px', '%' ),
			'range'      => array( 'px' => array( 'min' => 200, 'max' => 1200 ), '%' => array( 'min' => 10, 'max' => 100 ) ),
			'default'    => array( 'size' => 100, 'unit' => '%' ),
			'selectors'  => array( '{{WRAPPER}} .nexvio-atc' => 'max-width: {{SIZE}}{{UNIT}};' ),
		) );
		$this->end_controls_section();

		/* ============ STYLE: QUANTITY ============ */
		$this->start_controls_section( 'style_qty', array( 'label' => esc_html__( 'Quantity', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE, 'condition' => array( 'show_qty' => 'yes' ) ) );
		$this->color_var( 'q_bg', esc_html__( 'Background', 'nexvio' ), '--nexvio-atc-qbg', '#1f252e' );
		$this->color_var( 'q_ibg', esc_html__( 'Number background', 'nexvio' ), '--nexvio-atc-ibg', '#181d24' );
		$this->color_var( 'q_border', esc_html__( 'Border', 'nexvio' ), '--nexvio-atc-qborder', '#313945' );
		$this->color_var( 'q_color', esc_html__( 'Number colour', 'nexvio' ), '--nexvio-atc-qcolor', '#e8edf4' );
		$this->color_var( 'q_step', esc_html__( '− / + colour', 'nexvio' ), '--nexvio-atc-step', '#e8edf4' );
		$this->color_var( 'q_step_h', esc_html__( '− / + hover background', 'nexvio' ), '--nexvio-atc-step-h', '#262d37' );
		$this->number_var( 'q_step_w', esc_html__( '− / + width (px)', 'nexvio' ), '--nexvio-atc-stepw', 44, 24, 80, 1, 'px' );
		$this->number_var( 'q_input_w', esc_html__( 'Number width (px)', 'nexvio' ), '--nexvio-atc-iw', 52, 30, 120, 1, 'px' );
		$this->number_var( 'q_radius', esc_html__( 'Corner radius (px)', 'nexvio' ), '--nexvio-atc-qradius', 4, 0, 40, 1, 'px' );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'q_typo', 'selector' => '{{WRAPPER}} .nexvio-atc__input' ) );
		$this->end_controls_section();

		/* ============ STYLE: BUTTON ============ */
		$this->start_controls_section( 'style_btn', array( 'label' => esc_html__( 'Button', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->color_var( 'b_bg', esc_html__( 'Background', 'nexvio' ), '--nexvio-atc-bg', '#3d8bff' );
		$this->color_var( 'b_bg_h', esc_html__( 'Background on hover', 'nexvio' ), '--nexvio-atc-bg-h', '#5a9dff' );
		$this->color_var( 'b_tc', esc_html__( 'Text', 'nexvio' ), '--nexvio-atc-tc', '#ffffff' );
		$this->color_var( 'b_tc_h', esc_html__( 'Text on hover', 'nexvio' ), '--nexvio-atc-tc-h', '#ffffff' );
		$this->color_var( 'b_added', esc_html__( 'Background after adding', 'nexvio' ), '--nexvio-atc-ok', '#2fae78' );
		$this->color_var( 'b_dis', esc_html__( 'Sold-out background', 'nexvio' ), '--nexvio-atc-dis-bg', '#424b58' );
		$this->color_var( 'b_dis_tc', esc_html__( 'Sold-out text', 'nexvio' ), '--nexvio-atc-dis-tc', '#aab3c0' );
		$this->number_var( 'b_cut', esc_html__( 'Cut corners (px, 0 = none)', 'nexvio' ), '--nexvio-atc-cut', 10, 0, 30, 1, 'px' );
		$this->number_var( 'b_radius', esc_html__( 'Corner radius (px)', 'nexvio' ), '--nexvio-atc-bradius', 0, 0, 40, 1, 'px' );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'b_typo', 'selector' => '{{WRAPPER}} .nexvio-atc__btn' ) );
		$this->end_controls_section();

		/* ============ STYLE: WISHLIST ============ */
		$this->start_controls_section( 'style_wish', array( 'label' => esc_html__( 'Wishlist heart', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE, 'condition' => array( 'wish_mode!' => 'none' ) ) );
		$this->color_var( 'w_bg', esc_html__( 'Background', 'nexvio' ), '--nexvio-atc-wbg', '#13171d' );
		$this->color_var( 'w_border', esc_html__( 'Border', 'nexvio' ), '--nexvio-atc-wborder', '#313945' );
		$this->color_var( 'w_border_h', esc_html__( 'Border on hover', 'nexvio' ), '--nexvio-atc-wborder-h', '#ff6b8a' );
		$this->color_var( 'w_icon', esc_html__( 'Icon', 'nexvio' ), '--nexvio-atc-wicon', '#e8edf4' );
		$this->color_var( 'w_on', esc_html__( 'Icon when in wishlist', 'nexvio' ), '--nexvio-atc-won', '#ff6b8a' );
		$this->number_var( 'w_w', esc_html__( 'Width (px)', 'nexvio' ), '--nexvio-atc-ww', 56, 32, 90, 1, 'px' );
		$this->number_var( 'w_radius', esc_html__( 'Corner radius (px)', 'nexvio' ), '--nexvio-atc-wradius', 4, 0, 45, 1, 'px' );
		$this->number_var( 'w_icon_s', esc_html__( 'Icon size (px)', 'nexvio' ), '--nexvio-atc-wis', 20, 12, 40, 1, 'px' );
		$this->end_controls_section();

		/* ============ STYLE: MESSAGE ============ */
		$this->start_controls_section( 'style_msg', array( 'label' => esc_html__( 'Message after adding', 'nexvio' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->color_var( 'm_color', esc_html__( 'Text', 'nexvio' ), '--nexvio-atc-mcolor', '#8f9aab' );
		$this->color_var( 'm_link', esc_html__( 'Link', 'nexvio' ), '--nexvio-atc-mlink', '#8cc8ff' );
		$this->color_var( 'm_err', esc_html__( 'Error', 'nexvio' ), '--nexvio-atc-merr', '#ff6371' );
		$this->end_controls_section();
	}

	/* ------------------------------------------------------------------
	 * Helpers
	 * ------------------------------------------------------------------ */

	private function current_product( $s ) {
		$id = ! empty( $s['product_id'] ) ? (int) $s['product_id'] : 0;
		if ( ! $id && is_singular( 'product' ) ) {
			$id = (int) get_queried_object_id();
		}
		if ( ! $id ) {
			global $product;
			if ( $product instanceof WC_Product ) {
				return $product;
			}
			$post_id = get_the_ID();
			if ( $post_id && 'product' === get_post_type( $post_id ) ) {
				$id = (int) $post_id;
			}
		}
		if ( ! $id ) {
			$is_edit = class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode();
			$preview = isset( $_GET['elementor-preview'] ) || isset( $_GET['elementor_library'] ); // phpcs:ignore WordPress.Security.NonceVerification
			if ( $is_edit || $preview || wp_doing_ajax() ) {
				$latest = get_posts( array( 'post_type' => 'product', 'post_status' => 'publish', 'numberposts' => 1, 'fields' => 'ids' ) );
				$id     = $latest ? (int) $latest[0] : 0;
			}
		}
		return $id ? wc_get_product( $id ) : null;
	}

	private function is_pre( $product, $s ) {
		$slug = trim( (string) $s['pre_slug'] );
		$id   = $product->get_id();
		if ( $slug && ( has_term( $slug, 'product_tag', $id ) || has_term( $slug, 'product_cat', $id ) ) ) {
			return true;
		}
		return $product->is_on_backorder();
	}

	/** "open" / "no" / "global": read by the side cart after adding. */
	private function side_pref( $s ) {
		return isset( $s['side_cart'] ) && in_array( $s['side_cart'], array( 'open', 'no' ), true ) ? $s['side_cart'] : 'global';
	}

	private function heart( $product, $s ) {
		if ( 'none' === $this->wish_mode( $s ) ) {
			return '';
		}
		return $this->heart_button( $product->get_id(), $s['wish_label'], 'nexvio-atc__wish' );
	}

	private function redirect_url( $s ) {
		if ( 'cart' === $s['redirect'] || ( 'wc' === $s['redirect'] && 'yes' === get_option( 'woocommerce_cart_redirect_after_add' ) ) ) {
			return wc_get_cart_url();
		}
		return '';
	}

	/* ------------------------------------------------------------------
	 * Output
	 * ------------------------------------------------------------------ */

	protected function render() {
		$s       = $this->get_settings_for_display();
		$product = $this->current_product( $s );
		$is_edit = class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode();

		if ( ! $product instanceof WC_Product ) {
			if ( $is_edit ) {
				echo '<div class="nexvio-ps nexvio-atc"><p class="nexvio-atc__msg">' . esc_html__( 'No product found. Use this widget in the single product template, or set a fixed product ID.', 'nexvio' ) . '</p></div>';
			}
			return;
		}

		$classes = array( 'nexvio-ps', 'nexvio-atc' );
		if ( 'yes' === $s['full'] ) {
			$classes[] = 'nexvio-atc--full';
		}
		$js = array( 'wish' => $this->wish_settings( $s ) );

		/* other product types: native WooCommerce form, styled to match */
		if ( ! $product->is_type( 'simple' ) ) {
			$classes[] = 'nexvio-atc--native';
			if ( function_exists( 'wc_setup_product_data' ) ) {
				wc_setup_product_data( $product->get_id() ); /* WooCommerce sets up its own product for its template */
			}
			echo '<div class="' . esc_attr( implode( ' ', $classes ) ) . '" data-settings="' . esc_attr( wp_json_encode( $js ) ) . '" data-nexvio-side="' . esc_attr( $this->side_pref( $s ) ) . '">';
			echo '<div class="nexvio-atc__native">';
			if ( function_exists( 'woocommerce_template_single_add_to_cart' ) ) {
				woocommerce_template_single_add_to_cart();
			}
			echo '</div>';
			echo wp_kses( $this->heart( $product, $s ), Nexvio_Plugin::allowed_html() );
			echo '</div>';
			return;
		}

		$id        = $product->get_id();
		$available = $product->is_purchasable() && $product->is_in_stock();
		$pre       = $available && $this->is_pre( $product, $s );
		$min       = max( 1, (int) $product->get_min_purchase_quantity() );
		$max       = (int) $product->get_max_purchase_quantity(); // -1 = no limit
		$single    = $product->is_sold_individually() || ( $max > 0 && $max <= 1 );
		$show_qty  = 'yes' === $s['show_qty'] && ! $single && $available;
		$label     = ! $available ? $s['txt_out'] : ( $pre ? $s['txt_pre'] : $s['txt_add'] );

		$texts = array(
			'adding' => (string) $s['txt_adding'],
			'added'  => (string) $s['txt_added'],
			'error'  => (string) $s['txt_error'],
			'cart'   => 'yes' === $s['show_cart_link'] ? (string) $s['txt_cart'] : '',
			'label'  => (string) $label,
		);
		?>
		<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-settings="<?php echo esc_attr( wp_json_encode( $js ) ); ?>" data-nexvio-side="<?php echo esc_attr( $this->side_pref( $s ) ); ?>">
			<?php $nexvio_form_action = apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core hook, called on purpose for compatibility. ?>
			<form class="nexvio-atc__form" action="<?php echo esc_url( $nexvio_form_action ); ?>" method="post" enctype="multipart/form-data"
				data-product_id="<?php echo esc_attr( $id ); ?>"
				data-ajax="<?php echo 'yes' === $s['ajax'] && $available ? '1' : '0'; ?>"
				data-ajax-url="<?php echo esc_url( WC_AJAX::get_endpoint( 'add_to_cart' ) ); ?>"
				data-cart-url="<?php echo esc_url( wc_get_cart_url() ); ?>"
				data-redirect="<?php echo esc_url( $this->redirect_url( $s ) ); ?>"
				data-texts="<?php echo esc_attr( wp_json_encode( $texts ) ); ?>">

				<?php if ( $show_qty ) : ?>
					<div class="nexvio-atc__qty">
						<button type="button" class="nexvio-atc__step" data-step="-1" aria-label="<?php esc_attr_e( 'Decrease quantity', 'nexvio' ); ?>">&minus;</button>
						<input type="number" class="nexvio-atc__input qty" name="quantity"
							value="<?php echo esc_attr( $min ); ?>"
							min="<?php echo esc_attr( $min ); ?>"
							<?php echo $max > 0 ? 'max="' . esc_attr( $max ) . '"' : ''; ?>
							step="1" inputmode="numeric" autocomplete="off"
							aria-label="<?php echo esc_attr( $s['qty_label'] ); ?>">
						<button type="button" class="nexvio-atc__step" data-step="1" aria-label="<?php esc_attr_e( 'Increase quantity', 'nexvio' ); ?>">+</button>
					</div>
				<?php else : ?>
					<input type="hidden" name="quantity" value="<?php echo esc_attr( $min ); ?>">
				<?php endif; ?>

				<?php if ( $available ) : ?>
					<button type="submit" name="add-to-cart" value="<?php echo esc_attr( $id ); ?>" class="nexvio-atc__btn">
						<?php if ( ! empty( $s['btn_icon']['value'] ) ) : ?>
							<span class="nexvio-atc__icon" aria-hidden="true"><?php \Elementor\Icons_Manager::render_icon( $s['btn_icon'], array( 'aria-hidden' => 'true' ) ); ?></span>
						<?php endif; ?>
						<span class="nexvio-atc__label"><?php echo esc_html( $label ); ?></span>
					</button>
				<?php else : ?>
					<button type="button" class="nexvio-atc__btn is-disabled" disabled aria-disabled="true">
						<span class="nexvio-atc__label"><?php echo esc_html( $label ); ?></span>
					</button>
				<?php endif; ?>

				<?php echo wp_kses( $this->heart( $product, $s ), Nexvio_Plugin::allowed_html() ); ?>
			</form>
			<p class="nexvio-atc__msg" aria-live="polite"></p>
		</div>
		<?php
	}
}
