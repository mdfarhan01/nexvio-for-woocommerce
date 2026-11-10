<?php
/**
 * Reviews engine for the Product Tabs widget.
 *
 * - builds the reviews panel (summary, star filter, sort, list, "load more")
 * - AJAX: load / filter / sort reviews, submit a review
 *
 * Reviews are normal WooCommerce reviews (comments of a product with a
 * "rating" comment meta), so moderation, e-mails, "verified owner" and the
 * average rating keep working exactly like in WooCommerce.
 *
 * @package Nexvio_For_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Nexvio_Reviews {

	const TYPES = array( 'review', 'comment' );

	public static function init() {
		add_action( 'wp_ajax_nexvio_reviews_load', array( __CLASS__, 'ajax_load' ) );
		add_action( 'wp_ajax_nopriv_nexvio_reviews_load', array( __CLASS__, 'ajax_load' ) );
		add_action( 'wp_ajax_nexvio_review_submit', array( __CLASS__, 'ajax_submit' ) );
		add_action( 'wp_ajax_nopriv_nexvio_review_submit', array( __CLASS__, 'ajax_submit' ) );
		add_action( 'wp_ajax_nexvio_review_helpful', array( __CLASS__, 'ajax_helpful' ) );
		add_action( 'wp_ajax_nopriv_nexvio_review_helpful', array( __CLASS__, 'ajax_helpful' ) );
		add_action( 'wp_ajax_nexvio_review_pin', array( __CLASS__, 'ajax_pin' ) );
		add_action( 'wp_ajax_nexvio_review_reply', array( __CLASS__, 'ajax_reply' ) );
		add_action( 'wp_ajax_nexvio_review_edit', array( __CLASS__, 'ajax_edit' ) );
		add_action( 'wp_ajax_nopriv_nexvio_review_edit', array( __CLASS__, 'ajax_edit' ) );
		add_action( 'wp_ajax_nopriv_nexvio_review_reply', array( __CLASS__, 'ajax_reply' ) );
	}

	/* ------------------------------------------------------------------
	 * Data
	 * ------------------------------------------------------------------ */

	/** Approved top-level reviews of a product: total, rated count, average and 5..1 distribution. */
	public static function stats( $product_id ) {
		global $wpdb;
		$product_id = (int) $product_id;

		$total = (int) get_comments( array(
			'post_id'      => $product_id,
			'status'       => 'approve',
			'parent'       => 0,
			'type__in'     => self::TYPES,
			'count'        => true,
			'update_comment_meta_cache' => false,
		) );

		$dist = array( 5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0 );
		// phpcs:disable WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT m.meta_value AS rating, COUNT(*) AS c
			 FROM {$wpdb->comments} c
			 INNER JOIN {$wpdb->commentmeta} m ON m.comment_id = c.comment_ID AND m.meta_key = 'rating'
			 WHERE c.comment_post_ID = %d AND c.comment_approved = '1' AND c.comment_parent = 0
			   AND c.comment_type IN ( 'review', 'comment', '' ) AND m.meta_value > 0
			 GROUP BY m.meta_value",
			$product_id
		) );
		// phpcs:enable
		$sum   = 0;
		$rated = 0;
		foreach ( (array) $rows as $r ) {
			$n = (int) round( (float) $r->rating );
			if ( $n >= 1 && $n <= 5 ) {
				$dist[ $n ] += (int) $r->c;
				$sum        += $n * (int) $r->c;
				$rated      += (int) $r->c;
			}
		}

		return array(
			'total'   => $total,
			'rated'   => $rated,
			'average' => $rated ? $sum / $rated : 0,
			'dist'    => $dist,
		);
	}

	/** @return array{0:WP_Comment[],1:int} reviews of the page and the total for this filter. Pinned reviews come first. */
	public static function query( $product_id, $page, $per_page, $sort, $rating ) {
		$args = array(
			'post_id'  => (int) $product_id,
			'status'   => 'approve',
			'parent'   => 0,
			'type__in' => self::TYPES,
		);
		$meta = array();
		if ( $rating >= 1 && $rating <= 5 ) {
			$meta[] = array( 'key' => 'rating', 'value' => $rating, 'compare' => '=', 'type' => 'NUMERIC' );
		}
		$count_args = $args;
		if ( $meta ) {
			$count_args['meta_query'] = $meta; // phpcs:ignore WordPress.DB.SlowDBQuery
		}
		$total = (int) get_comments( array_merge( $count_args, array( 'count' => true ) ) );

		/* pinned reviews first (few of them: sorted here) */
		$pin_args               = $args;
		$pin_args['meta_query'] = array_merge( array( 'relation' => 'AND' ), $meta, array( array( 'key' => 'nexvio_pinned', 'value' => '1', 'compare' => '=' ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
		$pin_args['orderby']    = 'comment_date_gmt';
		$pin_args['order']      = 'DESC';
		$pinned                 = get_comments( $pin_args );
		switch ( $sort ) {
			case 'oldest':
				usort( $pinned, function ( $a, $b ) { return strcmp( $a->comment_date_gmt, $b->comment_date_gmt ); } );
				break;
			case 'highest':
				usort( $pinned, function ( $a, $b ) { return (float) get_comment_meta( $b->comment_ID, 'rating', true ) <=> (float) get_comment_meta( $a->comment_ID, 'rating', true ); } );
				break;
			case 'lowest':
				usort( $pinned, function ( $a, $b ) { return (float) get_comment_meta( $a->comment_ID, 'rating', true ) <=> (float) get_comment_meta( $b->comment_ID, 'rating', true ); } );
				break;
		}
		$pcount = count( $pinned );
		$start  = max( 0, ( $page - 1 ) * $per_page );
		$out    = array_slice( $pinned, $start, $per_page );
		$need   = $per_page - count( $out );

		if ( $need > 0 ) {
			$rest = $args;
			if ( $meta ) {
				$rest['meta_query'] = $meta; // phpcs:ignore WordPress.DB.SlowDBQuery
			}
			if ( $pcount ) {
				$rest['comment__not_in'] = wp_list_pluck( $pinned, 'comment_ID' );
			}
			switch ( $sort ) {
				case 'oldest':
					$rest['orderby'] = 'comment_date_gmt';
					$rest['order']   = 'ASC';
					break;
				case 'highest':
				case 'lowest':
					$rest['meta_key'] = 'rating'; // phpcs:ignore WordPress.DB.SlowDBQuery
					$rest['orderby']  = 'meta_value_num';
					$rest['order']    = 'highest' === $sort ? 'DESC' : 'ASC';
					break;
				default:
					$rest['orderby'] = 'comment_date_gmt';
					$rest['order']   = 'DESC';
			}
			$rest['number'] = $need;
			$rest['offset'] = max( 0, $start - $pcount );
			$out            = array_merge( $out, get_comments( $rest ) );
		}

		return array( $out, $total );
	}

	/* ------------------------------------------------------------------
	 * Markup helpers
	 * ------------------------------------------------------------------ */

	public static function stars( $value, $class = '' ) {
		$value = max( 0, min( 5, (float) $value ) );
		$pct   = round( $value / 5 * 100, 1 );
		/* translators: %s: rating, e.g. 4.5 */
		$label = sprintf( __( 'Rated %s out of 5', 'nexvio' ), number_format_i18n( $value, 1 ) );
		return '<span class="nxt-stars ' . esc_attr( $class ) . '" role="img" aria-label="' . esc_attr( $label ) . '"><i style="width:' . esc_attr( $pct ) . '%"></i></span>';
	}

	private static function initials( $name ) {
		$name  = trim( wp_strip_all_tags( $name ) );
		$parts = preg_split( '/\s+/u', $name );
		$out   = '';
		foreach ( array_slice( array_filter( (array) $parts ), 0, 2 ) as $p ) {
			$out .= function_exists( 'mb_substr' ) ? mb_strtoupper( mb_substr( $p, 0, 1 ) ) : strtoupper( substr( $p, 0, 1 ) );
		}
		return '' !== $out ? $out : '?';
	}

	/** "2 weeks ago" for a review. */
	private static function time_ago( $ts ) {
		if ( ! $ts || $ts > time() ) {
			return date_i18n( get_option( 'date_format' ), $ts ? $ts : time() );
		}
		/* translators: %s: time since the review, e.g. "2 weeks" */
		return sprintf( __( '%s ago', 'nexvio' ), human_time_diff( $ts, time() ) );
	}

	/** One reply under a review. */
	public static function render_reply( $r ) {
		$rts   = strtotime( $r->comment_date_gmt . ' GMT' );
		$rname = '' !== trim( $r->comment_author ) ? $r->comment_author : __( 'Customer', 'nexvio' );
		$rtext = apply_filters( 'comment_text', $r->comment_content, $r, array() ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core WordPress filter.
		$isstf = $r->user_id && user_can( (int) $r->user_id, 'moderate_comments' );
		ob_start();
		?>
		<div class="nxt-reply<?php echo $isstf ? ' is-store' : ''; ?>">
			<div class="nxt-reply__head">
				<strong class="nxt-reply__name"><?php echo esc_html( $rname ); ?></strong>
				<?php if ( $isstf ) : ?><span class="nxt-reply__tag"><?php esc_html_e( 'Store reply', 'nexvio' ); ?></span><?php endif; ?>
				<time class="nxt-reply__date" datetime="<?php echo esc_attr( gmdate( 'c', $rts ) ); ?>"><?php echo esc_html( self::time_ago( $rts ) ); ?></time>
			</div>
			<div class="nxt-reply__text"><?php echo wp_kses_post( $rtext ); ?></div>
		</div>
		<?php
		return ob_get_clean();
	}

	/** One review card. */
	public static function render_item( $comment, $opts = array() ) {
		$id      = (int) $comment->comment_ID;
		$rating  = (float) get_comment_meta( $id, 'rating', true );
		$name    = '' !== trim( $comment->comment_author ) ? $comment->comment_author : __( 'Anonymous', 'nexvio' );
		$ts      = strtotime( $comment->comment_date_gmt . ' GMT' );
		$verif   = '';
		$show_v  = isset( $opts['verified'] ) ? (string) $opts['verified'] : __( 'Verified purchase', 'nexvio' );
		if ( '' !== $show_v && function_exists( 'wc_review_is_from_verified_owner' ) && wc_review_is_from_verified_owner( $id ) ) {
			$verif = '<span class="nxt-badge"><svg viewBox="0 0 20 20" aria-hidden="true"><circle cx="10" cy="10" r="9" fill="currentColor"/><path d="m6 10.2 2.7 2.7L14 7.6" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>' . esc_html( $show_v ) . '</span>';
		}
		$helpful = ! isset( $opts['helpful'] ) || 'no' !== $opts['helpful'];
		$count   = max( 0, (int) get_comment_meta( $id, 'nexvio_helpful', true ) );
		$photo   = get_avatar_url( $comment, array( 'size' => 144, 'default' => '404' ) ); /* false when avatars are off */
		$text    = apply_filters( 'comment_text', $comment->comment_content, $comment, array() ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core WordPress filter.
		$pinned  = '1' === (string) get_comment_meta( $id, 'nexvio_pinned', true );
		$can_pin = current_user_can( 'moderate_comments' );
		$replies = isset( $opts['_replies'][ $id ] ) ? (array) $opts['_replies'][ $id ] : array();
		$rcount  = count( $replies );
		$can_rep = ( ! isset( $opts['reply'] ) || 'no' !== $opts['reply'] ) && comments_open( (int) $comment->comment_post_ID ) && ! ( get_option( 'comment_registration' ) && ! is_user_logged_in() );
		$owner   = is_user_logged_in() && (int) $comment->user_id === get_current_user_id();
		$can_edit = $owner && ( ! isset( $opts['edit'] ) || 'no' !== $opts['edit'] );
		$edited  = (int) get_comment_meta( $id, 'nexvio_edited', true );
		$rep_lbl = ! empty( $opts['reply_label'] ) ? (string) $opts['reply_label'] : __( 'Reply', 'nexvio' );

		ob_start();
		?>
		<li class="nxt-item<?php echo $pinned ? ' is-pinned' : ''; ?>" id="nxt-review-<?php echo esc_attr( $id ); ?>" data-rating="<?php echo esc_attr( (int) round( $rating ) ); ?>">
			<span class="nxt-avatar" aria-hidden="true">
				<span class="nxt-avatar__i"><?php echo esc_html( self::initials( $name ) ); ?></span>
				<?php if ( $photo ) : ?>
					<img class="nxt-avatar__img" src="<?php echo esc_url( $photo ); ?>" alt="" width="144" height="144" loading="lazy" decoding="async">
				<?php endif; ?>
			</span>
			<div class="nxt-item__main">
				<?php if ( $can_pin ) : ?>
					<button type="button" class="nxt-pin<?php echo $pinned ? ' is-on' : ''; ?>" data-id="<?php echo esc_attr( $id ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'nexvio_pin' ) ); ?>" data-pinned="<?php echo $pinned ? '1' : '0'; ?>" aria-pressed="<?php echo $pinned ? 'true' : 'false'; ?>" title="<?php echo esc_attr( $pinned ? __( 'Unpin this review', 'nexvio' ) : __( 'Pin this review to the top', 'nexvio' ) ); ?>">
						<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 3h6l-1 6 3 3v2h-4v6l-1 1-1-1v-6H7v-2l3-3-1-6Z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
						<span class="screen-reader-text"><?php esc_html_e( 'Pin', 'nexvio' ); ?></span>
					</button>
				<?php endif; ?>
				<div class="nxt-item__top">
					<div class="nxt-item__who">
						<strong class="nxt-item__name"><?php echo esc_html( $name ); ?></strong>
						<?php echo $verif; // phpcs:ignore WordPress.Security.EscapeOutput -- built above with escaped parts. ?>
					</div>
					<?php if ( $rating > 0 ) : ?>
						<div class="nxt-item__rating"><?php echo self::stars( $rating ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
					<?php endif; ?>
					<?php if ( $pinned ) : ?>
						<span class="nxt-pinned"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 3h6l-1 6 3 3v2h-4v6l-1 1-1-1v-6H7v-2l3-3-1-6Z" fill="currentColor"/></svg><?php esc_html_e( 'Pinned', 'nexvio' ); ?></span>
					<?php endif; ?>
				</div>
				<div class="nxt-item__body">
					<div class="nxt-item__text"><?php echo wp_kses_post( $text ); ?></div>
					<button type="button" class="nxt-more" hidden><?php esc_html_e( 'Read more', 'nexvio' ); ?></button>
				</div>
				<?php if ( $rcount ) : ?>
					<div class="nxt-replies" hidden>
						<?php
						foreach ( $replies as $r ) {
							echo self::render_reply( $r ); // phpcs:ignore WordPress.Security.EscapeOutput -- built with escaped parts.
						}
						?>
					</div>
				<?php endif; ?>
				<div class="nxt-item__foot">
					<time class="nxt-item__date" datetime="<?php echo esc_attr( gmdate( 'c', $ts ) ); ?>" title="<?php echo esc_attr( date_i18n( get_option( 'date_format' ), $ts ) ); ?>"><?php echo esc_html( self::time_ago( $ts ) ); ?></time>
					<?php if ( $edited ) : ?><span class="nxt-item__edited"><?php esc_html_e( 'edited', 'nexvio' ); ?></span><?php endif; ?>
					<div class="nxt-item__acts">
						<?php if ( $can_edit ) : ?>
							<button type="button" class="nxt-edit" data-id="<?php echo esc_attr( $id ); ?>" aria-expanded="false">
								<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m4 20 1-4L16.5 4.5a2 2 0 0 1 3 3L8 19l-4 1Z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
								<span><?php echo esc_html( ! empty( $opts['edit_label'] ) ? (string) $opts['edit_label'] : __( 'Edit', 'nexvio' ) ); ?></span>
							</button>
						<?php endif; ?>
						<?php if ( $can_rep ) : ?>
							<button type="button" class="nxt-reply-btn" data-id="<?php echo esc_attr( $id ); ?>" aria-expanded="false">
								<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 8V4l-7 7 7 7v-4c5 0 8 1.5 11 6-1-6-4-11-11-12Z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
								<span><?php echo esc_html( $rep_lbl ); ?></span>
							</button>
						<?php endif; ?>
						<?php if ( $rcount ) : ?>
							<button type="button" class="nxt-rep" aria-expanded="false">
								<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v11H9l-5 4V5Z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
								<span><?php
								/* translators: %s: number of replies */
								echo esc_html( sprintf( _n( '%s reply', '%s replies', $rcount, 'nexvio' ), number_format_i18n( $rcount ) ) );
								?></span>
							</button>
						<?php endif; ?>
						<?php if ( $helpful ) : ?>
							<button type="button" class="nxt-help" data-id="<?php echo esc_attr( $id ); ?>" data-n="<?php echo esc_attr( $count ); ?>" aria-pressed="false">
								<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 10v10H4a1 1 0 0 1-1-1v-8a1 1 0 0 1 1-1h3Zm0 0 4-7a2 2 0 0 1 2 2v3h5.2a2 2 0 0 1 2 2.4l-1.4 7A2 2 0 0 1 16.8 20H7" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
								<span class="nxt-help__t"><?php esc_html_e( 'Helpful', 'nexvio' ); ?></span>
								<span class="nxt-help__c">(<span class="nxt-help__n"><?php echo esc_html( number_format_i18n( $count ) ); ?></span>)</span>
							</button>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</li>
		<?php
		return ob_get_clean();
	}

	public static function render_items( $comments, $opts = array() ) {
		$html = '';
		$ids  = array_map( 'intval', wp_list_pluck( (array) $comments, 'comment_ID' ) );
		if ( $ids ) {
			$opts['_replies'] = array();
			$kids             = get_comments( array(
				'parent__in' => $ids,
				'status'     => 'approve',
				'orderby'    => 'comment_date_gmt',
				'order'      => 'ASC',
			) );
			foreach ( (array) $kids as $k ) {
				$opts['_replies'][ (int) $k->comment_parent ][] = $k;
			}
		}
		foreach ( (array) $comments as $c ) {
			$html .= self::render_item( $c, $opts );
		}
		return $html;
	}

	/**
	 * The whole "Reviews" tab content.
	 *
	 * @param WC_Product $product
	 * @param array      $o per_page, show_dist, show_sort, verified, write_label, t (texts)
	 */
	public static function render_panel( $product, $o ) {
		$pid   = $product->get_id();
		$stats = self::stats( $pid );
		$per   = max( 1, min( 50, (int) $o['per_page'] ) );
		list( $comments, $total ) = self::query( $pid, 1, $per, 'newest', 0 );
		$t = $o['t'];

		ob_start();
		if ( ! $stats['total'] ) :
			?>
			<div class="nxt-empty">
				<span class="nxt-empty__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m12 3 2.6 5.6 6.1.7-4.5 4.2 1.2 6L12 16.5 6.6 19.5l1.2-6L3.3 9.3l6.1-.7z" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/></svg></span>
				<h3><?php echo esc_html( $t['empty_title'] ); ?></h3>
				<p><?php echo esc_html( $t['empty_text'] ); ?></p>
				<button type="button" class="nxt-btn nxt-btn--dark" data-nxt-goto="add"><?php echo esc_html( $t['write'] ); ?></button>
			</div>
			<?php
			return ob_get_clean();
		endif;

		$avg = $stats['average'];
		?>
		<div class="nxt-rev" data-per="<?php echo esc_attr( $per ); ?>" data-total="<?php echo esc_attr( $total ); ?>">
			<aside class="nxt-sum">
				<div class="nxt-sum__top">
					<span class="nxt-sum__num"><?php echo esc_html( number_format_i18n( $avg, 1 ) ); ?></span>
					<?php echo self::stars( $avg, 'nxt-stars--lg' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
				<p class="nxt-sum__count">
					<?php
					/* translators: %s: number of reviews */
					echo esc_html( sprintf( _n( 'Based on %s review', 'Based on %s reviews', $stats['total'], 'nexvio' ), number_format_i18n( $stats['total'] ) ) );
					?>
				</p>
				<?php if ( 'yes' === $o['show_dist'] && $stats['rated'] ) : ?>
					<ul class="nxt-dist" aria-label="<?php esc_attr_e( 'Filter reviews by rating', 'nexvio' ); ?>">
						<?php
						foreach ( $stats['dist'] as $star => $n ) :
							$pct = $stats['rated'] ? round( $n / $stats['rated'] * 100 ) : 0;
							?>
							<li>
								<button type="button" class="nxt-dist__row" data-rating="<?php echo esc_attr( $star ); ?>" aria-pressed="false" <?php disabled( 0, $n ); ?>>
									<span class="nxt-dist__star"><?php /* translators: %d: number of stars */ echo esc_html( sprintf( __( '%d Star', 'nexvio' ), $star ) ); ?></span>
									<span class="nxt-dist__bar"><i style="width:<?php echo esc_attr( $pct ); ?>%"></i></span>
									<span class="nxt-dist__n"><?php echo esc_html( number_format_i18n( $pct ) ); ?>%</span>
								</button>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</aside>

			<div class="nxt-feed">
				<div class="nxt-bar">
					<button type="button" class="nxt-btn nxt-btn--write" data-nxt-goto="add" aria-haspopup="dialog">
						<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m4 20 1-4L16.5 4.5a2.1 2.1 0 0 1 3 3L8 19l-4 1Zm9-13 4 4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
						<span><?php echo esc_html( $t['write'] ); ?></span>
					</button>
					<p class="nxt-bar__status" aria-live="polite"></p>
					<?php if ( 'yes' === $o['show_sort'] ) : ?>
						<label class="nxt-sort">
							<span><?php echo esc_html( $t['sort'] ); ?></span>
							<select data-nxt-sort>
								<option value="newest"><?php esc_html_e( 'Newest first', 'nexvio' ); ?></option>
								<option value="oldest"><?php esc_html_e( 'Oldest first', 'nexvio' ); ?></option>
								<option value="highest"><?php esc_html_e( 'Highest rated', 'nexvio' ); ?></option>
								<option value="lowest"><?php esc_html_e( 'Lowest rated', 'nexvio' ); ?></option>
							</select>
						</label>
					<?php endif; ?>
				</div>
				<button type="button" class="nxt-chip" data-nxt-clear hidden></button>
				<ol class="nxt-list"><?php echo self::render_items( $comments, $o ); // phpcs:ignore WordPress.Security.EscapeOutput ?></ol>
				<p class="nxt-nomatch" hidden><?php echo esc_html( $t['no_match'] ); ?></p>
				<div class="nxt-foot">
					<button type="button" class="nxt-btn nxt-btn--ghost" data-nxt-more <?php echo $total > $per ? '' : 'hidden'; ?>><?php echo esc_html( $t['load_more'] ); ?></button>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/* ------------------------------------------------------------------
	 * AJAX
	 * ------------------------------------------------------------------ */

	private static function opts_from_request() {
		$verified = isset( $_POST['verified'] ) ? sanitize_text_field( wp_unslash( $_POST['verified'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$helpful  = isset( $_POST['helpful'] ) && 'no' === sanitize_key( wp_unslash( $_POST['helpful'] ) ) ? 'no' : 'yes'; // phpcs:ignore WordPress.Security.NonceVerification
		$reply     = isset( $_POST['reply'] ) && 'no' === sanitize_key( wp_unslash( $_POST['reply'] ) ) ? 'no' : 'yes'; // phpcs:ignore WordPress.Security.NonceVerification
		$edit      = isset( $_POST['edit'] ) && 'no' === sanitize_key( wp_unslash( $_POST['edit'] ) ) ? 'no' : 'yes'; // phpcs:ignore WordPress.Security.NonceVerification
		$edit_lbl  = isset( $_POST['edit_label'] ) ? sanitize_text_field( wp_unslash( $_POST['edit_label'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$reply_lbl = isset( $_POST['reply_label'] ) ? sanitize_text_field( wp_unslash( $_POST['reply_label'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		return array(
			'verified'    => mb_substr( $verified, 0, 60 ),
			'helpful'     => $helpful,
			'reply'       => $reply,
			'edit'        => $edit,
			'edit_label'  => mb_substr( $edit_lbl, 0, 40 ),
			'reply_label' => mb_substr( $reply_lbl, 0, 40 ),
		);
	}

	public static function ajax_load() {
		// phpcs:disable WordPress.Security.NonceVerification -- public, read-only list of approved reviews.
		$pid     = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		$page    = isset( $_POST['page'] ) ? max( 1, absint( $_POST['page'] ) ) : 1;
		$per     = isset( $_POST['per'] ) ? max( 1, min( 50, absint( $_POST['per'] ) ) ) : 5;
		$sort    = isset( $_POST['sort'] ) ? sanitize_key( wp_unslash( $_POST['sort'] ) ) : 'newest';
		$rating  = isset( $_POST['rating'] ) ? absint( $_POST['rating'] ) : 0;
		// phpcs:enable
		if ( ! $pid || 'product' !== get_post_type( $pid ) || 'publish' !== get_post_status( $pid ) ) {
			wp_send_json_error( array( 'message' => __( 'Product not found.', 'nexvio' ) ), 404 );
		}
		list( $comments, $total ) = self::query( $pid, $page, $per, $sort, $rating );
		$shown = min( $total, $page * $per );
		wp_send_json_success( array(
			'html'     => self::render_items( $comments, self::opts_from_request() ),
			'total'    => $total,
			'shown'    => $shown,
			'has_more' => $shown < $total,
		) );
	}

	/** Key of the current visitor for "Helpful" votes: the user, or the hashed address of a guest. */
	private static function voter_key() {
		$uid = get_current_user_id();
		if ( $uid ) {
			return wp_hash( 'nxt-u' . $uid );
		}
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		return wp_hash( 'nxt-ip' . $ip );
	}

	/** "Helpful" vote on a review: one vote per visitor, can be taken back. */
	public static function ajax_helpful() {
		// phpcs:disable WordPress.Security.NonceVerification -- public and idempotent: one vote per visitor, same as a like button.
		$id   = isset( $_POST['comment_id'] ) ? absint( $_POST['comment_id'] ) : 0;
		$vote = ! empty( $_POST['vote'] ) && '0' !== sanitize_text_field( wp_unslash( $_POST['vote'] ) );
		// phpcs:enable
		$comment = $id ? get_comment( $id ) : null;
		if ( ! $comment || '1' !== (string) $comment->comment_approved || ! in_array( $comment->comment_type, self::TYPES, true ) || 'product' !== get_post_type( (int) $comment->comment_post_ID ) ) {
			wp_send_json_error( array( 'message' => __( 'Review not found.', 'nexvio' ) ), 404 );
		}
		$by = get_comment_meta( $id, '_nexvio_helpful_by', true );
		$by = is_array( $by ) ? $by : array();
		$me = self::voter_key();
		if ( $vote && ! in_array( $me, $by, true ) ) {
			$by[] = $me;
		} elseif ( ! $vote ) {
			$by = array_values( array_diff( $by, array( $me ) ) );
		}
		$by = array_slice( $by, -2000 );
		update_comment_meta( $id, '_nexvio_helpful_by', $by );
		update_comment_meta( $id, 'nexvio_helpful', count( $by ) );
		wp_send_json_success( array(
			'count' => count( $by ),
			'voted' => in_array( $me, $by, true ),
		) );
	}

	/** A visitor (or the store) replies to a review. Normal WordPress moderation and spam checks apply. */
	public static function ajax_reply() {
		// phpcs:disable WordPress.Security.NonceVerification -- same as a WordPress comment form: public, protected by honeypot, flood and duplicate checks.
		$pid    = isset( $_POST['comment_post_ID'] ) ? absint( $_POST['comment_post_ID'] ) : 0;
		$parent = isset( $_POST['comment_parent'] ) ? absint( $_POST['comment_parent'] ) : 0;
		$pc     = $parent ? get_comment( $parent ) : null;
		if ( ! $pc || (int) $pc->comment_post_ID !== $pid || '1' !== (string) $pc->comment_approved || (int) $pc->comment_parent || ! in_array( $pc->comment_type, self::TYPES, true ) || 'product' !== get_post_type( $pid ) ) {
			wp_send_json_error( array( 'message' => __( 'Review not found.', 'nexvio' ) ), 404 );
		}

		/* spam bots fill the hidden field: pretend it worked */
		if ( ! empty( $_POST['nexvio_hp'] ) ) {
			wp_send_json_success( array( 'approved' => false, 'message' => __( 'Thank you!', 'nexvio' ) ) );
		}
		if ( ! comments_open( $pid ) ) {
			wp_send_json_error( array( 'message' => __( 'Replies are closed for this product.', 'nexvio' ) ), 403 );
		}
		if ( get_option( 'comment_registration' ) && ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'You must be logged in to reply.', 'nexvio' ) ), 403 );
		}

		$data                   = wp_unslash( $_POST );
		$data['comment_parent'] = $parent;
		unset( $data['rating'] );
		$comment = wp_handle_comment_submission( $data );
		// phpcs:enable
		if ( is_wp_error( $comment ) ) {
			$field = 'comment';
			$code  = $comment->get_error_code();
			if ( false !== strpos( $code, 'author' ) ) {
				$field = 'author';
			} elseif ( false !== strpos( $code, 'email' ) ) {
				$field = 'email';
			}
			$err = $comment->get_error_data();
			wp_send_json_error( array(
				'message' => wp_strip_all_tags( $comment->get_error_message() ),
				'field'   => $field,
			), is_numeric( $err ) ? (int) $err : 400 );
		}

		$approved = '1' === (string) $comment->comment_approved;
		$out      = array(
			'approved' => $approved,
			'message'  => $approved
				? __( 'Thank you! Your reply is now published.', 'nexvio' )
				: __( 'Thank you! Your reply has been received and will appear once it is approved.', 'nexvio' ),
		);
		if ( $approved ) {
			$out['item'] = self::render_items( array( get_comment( $parent ) ), self::opts_from_request() );
		}
		wp_send_json_success( $out );
	}

	/** True when the current visitor wrote this review (logged in, or the private key kept in the browser). */
	private static function owns( $comment, $key ) {
		if ( is_user_logged_in() && (int) $comment->user_id === get_current_user_id() ) {
			return true;
		}
		$hash = (string) get_comment_meta( (int) $comment->comment_ID, 'nexvio_edit_key', true );
		return '' !== $hash && '' !== (string) $key && hash_equals( $hash, wp_hash( (string) $key ) );
	}

	/** The author edits their own review after it was approved. */
	public static function ajax_edit() {
		// phpcs:disable WordPress.Security.NonceVerification -- ownership is checked below (logged-in author or private key).
		$id      = isset( $_POST['comment_id'] ) ? absint( $_POST['comment_id'] ) : 0;
		$key     = isset( $_POST['ekey'] ) ? sanitize_text_field( wp_unslash( $_POST['ekey'] ) ) : '';
		$content = isset( $_POST['comment'] ) ? trim( sanitize_textarea_field( wp_unslash( $_POST['comment'] ) ) ) : '';
		$rating  = isset( $_POST['rating'] ) ? absint( $_POST['rating'] ) : 0;
		// phpcs:enable
		$comment = $id ? get_comment( $id ) : null;
		if ( ! $comment || (int) $comment->comment_parent || '1' !== (string) $comment->comment_approved || ! in_array( $comment->comment_type, self::TYPES, true ) || 'product' !== get_post_type( (int) $comment->comment_post_ID ) ) {
			wp_send_json_error( array( 'message' => __( 'Review not found.', 'nexvio' ) ), 404 );
		}
		if ( ! self::owns( $comment, $key ) ) {
			wp_send_json_error( array( 'message' => __( 'You can only edit your own review.', 'nexvio' ) ), 403 );
		}
		if ( '' === $content ) {
			wp_send_json_error( array( 'message' => __( 'Please write your review.', 'nexvio' ), 'field' => 'comment' ), 422 );
		}
		$content = mb_substr( $content, 0, 5000 );
		$had     = (float) get_comment_meta( $id, 'rating', true ) > 0;
		if ( $had && ( $rating < 1 || $rating > 5 ) ) {
			wp_send_json_error( array( 'message' => __( 'Please select a rating.', 'nexvio' ), 'field' => 'rating' ), 422 );
		}
		$disallowed = function_exists( 'wp_check_comment_disallowed_list' )
			? wp_check_comment_disallowed_list( $comment->comment_author, $comment->comment_author_email, $comment->comment_author_url, $content, $comment->comment_author_IP, $comment->comment_agent )
			: false;
		if ( $disallowed ) {
			wp_send_json_error( array( 'message' => __( 'Your review could not be saved.', 'nexvio' ), 'field' => 'comment' ), 422 );
		}

		$old_rating = (int) round( (float) get_comment_meta( $id, 'rating', true ) );
		$res        = wp_update_comment( wp_slash( array(
			'comment_ID'      => $id,
			'comment_content' => $content,
		) ), true );
		if ( is_wp_error( $res ) ) {
			wp_send_json_error( array( 'message' => wp_strip_all_tags( $res->get_error_message() ) ), 400 );
		}
		if ( $had ) {
			update_comment_meta( $id, 'rating', $rating );
		}
		update_comment_meta( $id, 'nexvio_edited', time() );
		if ( class_exists( 'WC_Comments' ) && method_exists( 'WC_Comments', 'clear_transients' ) ) {
			WC_Comments::clear_transients( (int) $comment->comment_post_ID );
		}

		wp_send_json_success( array(
			'message'        => __( 'Your review was updated.', 'nexvio' ),
			'rating_changed' => $had && $old_rating !== $rating,
			'item'           => self::render_items( array( get_comment( $id ) ), self::opts_from_request() ),
		) );
	}

	/** Pin / unpin a review (shop managers and admins only). */
	public static function ajax_pin() {
		check_ajax_referer( 'nexvio_pin', 'nonce' );
		if ( ! current_user_can( 'moderate_comments' ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to do this.', 'nexvio' ) ), 403 );
		}
		$id      = isset( $_POST['comment_id'] ) ? absint( $_POST['comment_id'] ) : 0;
		$pin     = ! empty( $_POST['pin'] ) && '0' !== sanitize_text_field( wp_unslash( $_POST['pin'] ) );
		$comment = $id ? get_comment( $id ) : null;
		if ( ! $comment || (int) $comment->comment_parent || ! in_array( $comment->comment_type, self::TYPES, true ) || 'product' !== get_post_type( (int) $comment->comment_post_ID ) ) {
			wp_send_json_error( array( 'message' => __( 'Review not found.', 'nexvio' ) ), 404 );
		}
		if ( $pin ) {
			update_comment_meta( $id, 'nexvio_pinned', '1' );
		} else {
			delete_comment_meta( $id, 'nexvio_pinned' );
		}
		wp_send_json_success( array( 'pinned' => $pin ) );
	}

	/** Why the visitor cannot review this product ('' = can). */
	public static function cannot_review( $product ) {
		if ( ! comments_open( $product->get_id() ) ) {
			return __( 'Reviews are closed for this product.', 'nexvio' );
		}
		if ( get_option( 'comment_registration' ) && ! is_user_logged_in() ) {
			return __( 'You must be logged in to post a review.', 'nexvio' );
		}
		if ( 'yes' === get_option( 'woocommerce_review_rating_verification_required' ) && function_exists( 'wc_customer_bought_product' ) ) {
			$user  = wp_get_current_user();
			$email = $user->ID ? $user->user_email : '';
			if ( ! $user->ID || ! wc_customer_bought_product( $email, $user->ID, $product->get_id() ) ) {
				return __( 'Only logged in customers who have purchased this product may leave a review.', 'nexvio' );
			}
		}
		return '';
	}

	public static function ajax_submit() {
		// phpcs:disable WordPress.Security.NonceVerification -- same as a WordPress comment form: public, protected by honeypot, flood and duplicate checks.
		$pid = isset( $_POST['comment_post_ID'] ) ? absint( $_POST['comment_post_ID'] ) : 0;
		$product = $pid && 'product' === get_post_type( $pid ) ? wc_get_product( $pid ) : null;
		if ( ! $product ) {
			wp_send_json_error( array( 'message' => __( 'Product not found.', 'nexvio' ) ), 404 );
		}

		/* spam bots fill the hidden field: pretend it worked */
		if ( ! empty( $_POST['nexvio_hp'] ) ) {
			wp_send_json_success( array( 'approved' => false, 'message' => __( 'Thank you!', 'nexvio' ) ) );
		}

		$why = self::cannot_review( $product );
		if ( '' !== $why ) {
			wp_send_json_error( array( 'message' => $why ), 403 );
		}

		$rating   = isset( $_POST['rating'] ) ? absint( $_POST['rating'] ) : 0;
		$required = ! function_exists( 'wc_review_ratings_required' ) || wc_review_ratings_required();
		if ( ( $required && ( $rating < 1 || $rating > 5 ) ) || ( ! $required && $rating && ( $rating < 1 || $rating > 5 ) ) ) {
			wp_send_json_error( array( 'message' => __( 'Please select a rating.', 'nexvio' ), 'field' => 'rating' ), 422 );
		}

		$_POST['comment_parent'] = 0;
		$comment = wp_handle_comment_submission( wp_unslash( $_POST ) );
		// phpcs:enable
		if ( is_wp_error( $comment ) ) {
			$field = 'comment';
			$code  = $comment->get_error_code();
			if ( false !== strpos( $code, 'author' ) ) {
				$field = 'author';
			} elseif ( false !== strpos( $code, 'email' ) ) {
				$field = 'email';
			}
			$data = $comment->get_error_data();
			wp_send_json_error( array(
				'message' => wp_strip_all_tags( $comment->get_error_message() ),
				'field'   => $field,
			), is_numeric( $data ) ? (int) $data : 400 );
		}

		$approved = '1' === (string) $comment->comment_approved;
		$out      = array(
			'approved' => $approved,
			'message'  => $approved
				? __( 'Thank you! Your review is now published.', 'nexvio' )
				: __( 'Thank you! Your review has been received and will appear once it is approved.', 'nexvio' ),
			'total'    => (int) self::stats( $pid )['total'],
		);
		if ( ! is_user_logged_in() ) {
			/* a private key kept in the visitor's browser: it lets the same visitor edit this review once it is approved */
			$key = wp_generate_password( 24, false, false );
			update_comment_meta( (int) $comment->comment_ID, 'nexvio_edit_key', wp_hash( $key ) );
			$out['id']   = (int) $comment->comment_ID;
			$out['ekey'] = $key;
		}
		wp_send_json_success( $out );
	}
}
