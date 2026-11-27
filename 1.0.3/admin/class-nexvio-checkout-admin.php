<?php
 if ( ! defined( 'ABSPATH' ) ) {
exit;
}
class Nexvio_Checkout_Admin {
const PAGE = 'nexvio-checkout';
public static function init() {
add_action( 'wp_ajax_nexvio_cfe_save', array( __CLASS__, 'ajax_save' ) );
add_action( 'wp_ajax_nexvio_cfe_reset', array( __CLASS__, 'ajax_reset' ) );
}
private static function section() {
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only request state (filter, sort, page or preview); nothing is saved, so no nonce is needed.
$s = isset( $_GET['section'] ) ? sanitize_key( wp_unslash( $_GET['section'] ) ) : '';
return array_key_exists( $s, Nexvio_Checkout::sections() ) ? $s : 'billing';
}
private static function url( $section ) {
return add_query_arg( array( 'page' => self::PAGE, 'section' => $section ), admin_url( 'admin.php' ) );
}
public static function enqueue() {
wp_enqueue_style( 'nexvio-checkout-admin', NEXVIO_URL . 'admin/assets/checkout.css', array( 'nexvio-admin' ), Nexvio_Plugin::ver( 'admin/assets/checkout.css' ) );
wp_enqueue_script( 'nexvio-checkout-admin', NEXVIO_URL . 'admin/assets/checkout.js', array( 'jquery', 'jquery-ui-sortable' ), Nexvio_Plugin::ver( 'admin/assets/checkout.js' ), true );
wp_localize_script( 'nexvio-checkout-admin', 'NEXVIO_CFE', self::data() );
}
private static function data() {
$sections = array();
foreach ( Nexvio_Checkout::sections() as $key => $s ) {
$sections[ $key ] = array( 'label' => $s[0], 'prefix' => $s[2] );
}
$types = array();
foreach ( Nexvio_Checkout::types() as $k => $t ) {
$types[ $k ] = array( 'label' => $t[0], 'icon' => $t[1], 'choice' => $t[2] );
}
$widths = array();
foreach ( Nexvio_Checkout::widths() as $k => $w ) {
$widths[ $k ] = $w[0];
}
return array( 'ajax' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'nexvio_cfe' ), 'section' => self::section(), 'sections' => $sections, 'fields' => array( self::section() => Nexvio_Checkout::builder_fields( self::section() ) ), 'types' => $types, 'widths' => $widths, 'shippingUrl' => admin_url( 'admin.php?page=wc-settings&tab=shipping' ), 'validations' => Nexvio_Checkout::validations(), 'tags' => array( 'h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4', 'h5' => 'H5', 'h6' => 'H6', 'p' => 'P' ), 'i18n' => array( 'edit' => __( 'Edit', 'nexvio' ), 'delete' => __( 'Delete', 'nexvio' ), 'custom' => __( 'Custom', 'nexvio' ), 'required' => __( 'Required', 'nexvio' ), 'hiddenField' => __( 'Hidden', 'nexvio' ), 'addTitle' => __( 'Add a field', 'nexvio' ), 'editTitle' => __( 'Edit field', 'nexvio' ), 'save' => __( 'Save changes', 'nexvio' ), 'saved' => __( 'Saved.', 'nexvio' ), 'error' => __( 'Could not save. Please try again.', 'nexvio' ), 'done' => __( 'Done', 'nexvio' ), 'cancel' => __( 'Cancel', 'nexvio' ), 'confirmDelete' => __( 'Delete this field? Orders that already have a value keep it.', 'nexvio' ), 'confirmReset' => __( 'Reset this section to the WooCommerce default? Your changes and custom fields in it are removed.', 'nexvio' ), 'resetDone' => __( 'Section reset.', 'nexvio' ), 'empty' => __( 'No fields here yet. Click "Add a field".', 'nexvio' ), 'unsaved' => __( 'You have unsaved changes.', 'nexvio' ), 'nameTaken' => __( 'This name is already used in this section.', 'nexvio' ), 'nameNeeded' => __( 'Give the field a name (letters, numbers and _).', 'nexvio' ), 'labelNeeded' => __( 'Give the field a label.', 'nexvio' ), 'optionsNeeded' => __( 'Add at least one choice.', 'nexvio' ), 'newField' => __( 'New field', 'nexvio' ), 'fType' => __( 'Field type', 'nexvio' ), 'fName' => __( 'Name', 'nexvio' ), 'fNameHint' => __( 'Used to save the value in the order. Letters, numbers and _ only. It cannot be changed later.', 'nexvio' ), 'fLabel' => __( 'Label', 'nexvio' ), 'fLabelHint' => __( 'What the customer sees above the field.', 'nexvio' ), 'fPlaceholder' => __( 'Placeholder', 'nexvio' ), 'fDefault' => __( 'Default value', 'nexvio' ), 'fDesc' => __( 'Help text', 'nexvio' ), 'fDescHint' => __( 'Optional text shown under the field.', 'nexvio' ), 'fOptions' => __( 'Choices', 'nexvio' ), 'fOptionsHint' => __( 'One choice per line. Use value|Label to show a different text, for example s|Small.', 'nexvio' ), 'fWidth' => __( 'Width', 'nexvio' ), 'fClass' => __( 'Extra CSS class', 'nexvio' ), 'fClassHint' => __( 'Optional. Separate several classes with a space.', 'nexvio' ), 'fTag' => __( 'Heading size', 'nexvio' ), 'fRequired' => __( 'Required', 'nexvio' ), 'fRequiredHint' => __( 'The customer must fill this in to place the order.', 'nexvio' ), 'fEnabled' => __( 'Show on the checkout', 'nexvio' ), 'fChecked' => __( 'Ticked by default', 'nexvio' ), 'fValidate' => __( 'Check the value as', 'nexvio' ), 'fEmail' => __( 'Show in order emails', 'nexvio' ), 'fOrder' => __( 'Show on the order page', 'nexvio' ), 'fParagraph' => __( 'Text', 'nexvio' ), 'tabBasic' => __( 'Basics', 'nexvio' ), 'tabLook' => __( 'Look', 'nexvio' ), 'tabRules' => __( 'Rules', 'nexvio' ), 'standardNote' => __( 'This is a WooCommerce field. You can rename it, move it, make it required or hide it. Leave Label or Placeholder empty to use the WooCommerce text.', 'nexvio' ), 'defaultLabel' => __( 'WooCommerce text', 'nexvio' ), 'td_text' => __( 'Single line of text', 'nexvio' ), 'td_textarea' => __( 'Longer, multi-line message', 'nexvio' ), 'td_number' => __( 'Numbers only', 'nexvio' ), 'td_email' => __( 'Email address', 'nexvio' ), 'td_tel' => __( 'Phone number', 'nexvio' ), 'td_url' => __( 'Website link', 'nexvio' ), 'td_password' => __( 'Characters are masked', 'nexvio' ), 'td_select' => __( 'Pick one from a list', 'nexvio' ), 'td_multiselect' => __( 'Pick several from a list', 'nexvio' ), 'td_radio' => __( 'Pick one visible option', 'nexvio' ), 'td_checkbox' => __( 'A single yes / no tick', 'nexvio' ), 'td_checkboxgroup' => __( 'Tick several options', 'nexvio' ), 'td_date' => __( 'Date picker', 'nexvio' ), 'td_time' => __( 'Time picker', 'nexvio' ), 'td_heading' => __( 'A section title', 'nexvio' ), 'td_paragraph' => __( 'A plain text note', 'nexvio' ), 'td_hidden' => __( 'Saved with the order, not shown', 'nexvio' ), 'td_shippingzone' => __( 'Customer picks a shipping zone, with its price', 'nexvio' ), 'noteZone' => __( 'The choices are your WooCommerce shipping zones. A zone is listed when it has an active shipping method.', 'nexvio' ), 'zoneLink' => __( 'Manage shipping zones', 'nexvio' ), 'fCharge' => __( 'Use the zone price for shipping', 'nexvio' ), 'fChargeHint' => __( 'On: the shipping price of the zone the customer picks is used and added to the order total. Off: WooCommerce works out the shipping price from the address (country, state, city, ZIP).', 'nexvio' ), 'fShowPrice' => __( 'Show the price', 'nexvio' ), 'fShowPriceHint' => __( 'Shows the price of the zone next to its name on the checkout, for example "Dhaka - $5.00". It reads the cost of the zone\'s shipping methods.', 'nexvio' ), 'tabDelivery' => __( 'Delivery', 'nexvio' ), 'grpDelivery' => __( 'Delivery', 'nexvio' ), 'td_deliverydate' => __( 'Calendar with an earliest day, holidays and limits', 'nexvio' ), 'td_deliverytime' => __( 'Dropdown of time slots you set up', 'nexvio' ), 'dMin' => __( 'Earliest delivery', 'nexvio' ), 'dMinHint' => __( 'How many days after the order the first delivery date is. 0 means the same day.', 'nexvio' ), 'dSame' => __( 'Same day', 'nexvio' ), 'dDay' => __( 'day', 'nexvio' ), 'dDays' => __( 'days', 'nexvio' ), 'dMax' => __( 'Booking window', 'nexvio' ), 'dMaxHint' => __( 'How many days, counted from the earliest date, the customer can choose from.', 'nexvio' ), 'dSkip' => __( 'Count open days only', 'nexvio' ), 'dSkipHint' => __( 'Closed weekdays and holidays are not counted in the earliest-delivery days. With 2 days and Friday closed, an order on Wednesday is delivered from Saturday, not Friday.', 'nexvio' ), 'dClosed' => __( 'Closed weekdays', 'nexvio' ), 'dClosedHint' => __( 'No delivery on these days of the week.', 'nexvio' ), 'dCutoff' => __( 'Order cut-off time', 'nexvio' ), 'dCutoffHint' => __( 'Orders placed after this time count as placed the next day. Leave empty for no cut-off.', 'nexvio' ), 'dBlack' => __( 'Holidays and closed dates', 'nexvio' ), 'dBlackHint' => __( 'One per line, like 2026-12-24 or a range like 2026-12-24 to 2026-12-26.', 'nexvio' ), 'dCap' => __( 'Daily limit', 'nexvio' ), 'dCapHint' => __( 'The most orders that can pick the same day. 0 means no limit.', 'nexvio' ), 'tSched' => __( 'Time slots', 'nexvio' ), 'tSchedHint' => __( 'Choose the days, the time deliveries start and end, and how long one slot is. The slots are made for you.', 'nexvio' ), 'tDays' => __( 'Days', 'nexvio' ), 'tFrom' => __( 'From', 'nexvio' ), 'tTo' => __( 'To', 'nexvio' ), 'tLen' => __( 'Slot length', 'nexvio' ), 'tCap' => __( 'Limit per slot', 'nexvio' ), 'tMin' => __( 'min', 'nexvio' ), 'tAddSched' => __( 'Add a schedule', 'nexvio' ), 'tRemove' => __( 'Remove', 'nexvio' ), 'tSlot' => __( 'slot', 'nexvio' ), 'tSlots' => __( 'slots', 'nexvio' ), 'tNotice' => __( 'Minimum notice (hours)', 'nexvio' ), 'tNoticeHint' => __( 'Only for today: slots that start sooner than this are hidden. 0 means no notice.', 'nexvio' ), 'tFormat' => __( 'Time style', 'nexvio' ), 'tFormatHint' => __( 'How the times look to your customers.', 'nexvio' ), 't12' => __( '12-hour (2:00 PM)', 'nexvio' ), 't24' => __( '24-hour (14:00)', 'nexvio' ), 'tCapHint' => __( '0 = no limit', 'nexvio' ), 'schedNeeded' => __( 'Add at least one schedule with days and a start time before the end time.', 'nexvio' ), 'fltAll' => __( 'All', 'nexvio' ), 'fltVisible' => __( 'Visible', 'nexvio' ), 'grpText' => __( 'Text', 'nexvio' ), 'grpChoice' => __( 'Choices', 'nexvio' ), 'grpDate' => __( 'Date and time', 'nexvio' ), 'grpDisplay' => __( 'Display', 'nexvio' ), 'addAnother' => __( 'Add a new field', 'nexvio' ), 'colField' => __( 'Field', 'nexvio' ), 'colType' => __( 'Type', 'nexvio' ), 'colWidth' => __( 'Width', 'nexvio' ), 'colStatus' => __( 'Status', 'nexvio' ), 'noMatch' => __( 'No field matches your search.', 'nexvio' ), 'preview' => __( 'Live preview', 'nexvio' ), 'previewHint' => __( 'This is how the field looks on the checkout.', 'nexvio' ), 'subAdd' => __( 'Pick a type, then fill in the details.', 'nexvio' ), 'subStandard' => __( 'WooCommerce field', 'nexvio' ), 'hiddenNote' => __( 'A hidden field is not shown to the customer. Its value is still saved with the order.', 'nexvio' ), 'fTypePick' => __( 'Choose a field type', 'nexvio' ), 'fWidthHint' => __( 'How much of the row the field uses on the checkout.', 'nexvio' ), 'fValidateHint' => __( 'Optional. Block the order when the value does not match.', 'nexvio' ), 'fieldsCount' => __( 'fields', 'nexvio' ), 'fieldOne' => __( 'field', 'nexvio' ), 'hiddenCount' => __( 'hidden', 'nexvio' ), 'choice1' => __( 'Choice 1', 'nexvio' ), 'choice2' => __( 'Choice 2', 'nexvio' ), 'coreWarn' => __( 'Hiding or un-requiring this field can break shipping, tax or payment on some stores.', 'nexvio' ), ), );
}
public static function page() {
if ( ! current_user_can( 'manage_options' ) ) {
return;
}
$current = self::section();
Nexvio_Admin::open( self::PAGE, __( 'Checkout Field Editor', 'nexvio' ), __( 'Edit, reorder, hide and add fields on your checkout page.', 'nexvio' ) );
if ( defined( 'THWCFD_VERSION' ) || defined( 'THWCFE_VERSION' ) || class_exists( 'WC_Checkout_Field_Editor', false ) ) {
echo '<div class="nexvio-alert nexvio-cfe-alert"><span class="dashicons dashicons-warning" aria-hidden="true"></span>' . esc_html__( 'Another checkout field plugin is active. Use only one of them for the same fields, otherwise they change each other\'s work.', 'nexvio' ) . '</div>';
}
$co = function_exists( 'wc_get_page_id' ) ? (int) wc_get_page_id( 'checkout' ) : 0;
if ( $co > 0 && function_exists( 'has_block' ) && has_block( 'woocommerce/checkout', $co ) ) {
echo '<div class="nexvio-alert nexvio-cfe-alert"><span class="dashicons dashicons-info-outline" aria-hidden="true"></span>' . esc_html__( 'Your checkout page uses the Checkout block. These fields work on the classic checkout (the [woocommerce_checkout] shortcode or an Elementor checkout widget). Replace the block with the shortcode to use them.', 'nexvio' ) . '</div>';
} ?>
		<nav class="nexvio-cfe-tabs" aria-label="<?php esc_attr_e( 'Checkout sections', 'nexvio' ); ?>">
			<?php foreach ( Nexvio_Checkout::sections() as $key => $s ) : ?>
				<a class="<?php echo $key === $current ? 'is-active' : ''; ?>" href="<?php echo esc_url( self::url( $key ) ); ?>"><?php echo esc_html( $s[0] ); ?></a>
			<?php endforeach; ?>
		</nav>

		<section class="nexvio-glass nexvio-card-box nexvio-cfe" id="nexvio-cfe" data-section="<?php echo esc_attr( $current ); ?>">
			<div class="nexvio-box-head">
				<div class="nexvio-cfe__title">
					<h2><?php echo esc_html( Nexvio_Checkout::sections()[ $current ][0] ); ?> <?php esc_html_e( 'fields', 'nexvio' ); ?> <span class="nexvio-cfe-count" id="nexvio-cfe-count"></span></h2>
					<p><?php esc_html_e( 'Drag a field to move it. Use the switch to show or hide it.', 'nexvio' ); ?></p>
				</div>
				<div class="nexvio-cfe__tools">
					<label class="nexvio-cfe-search">
						<span class="dashicons dashicons-search" aria-hidden="true"></span>
						<input type="search" id="nexvio-cfe-search" placeholder="<?php esc_attr_e( 'Search fields', 'nexvio' ); ?>" aria-label="<?php esc_attr_e( 'Search fields', 'nexvio' ); ?>" autocomplete="off">
					</label>
					<button type="button" class="nexvio-btn nexvio-btn--ghost nexvio-btn--sm" id="nexvio-cfe-reset"><span class="dashicons dashicons-image-rotate" aria-hidden="true"></span><?php esc_html_e( 'Reset', 'nexvio' ); ?></button>
					<button type="button" class="nexvio-btn nexvio-btn--sm" id="nexvio-cfe-add"><span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span><?php esc_html_e( 'Add a field', 'nexvio' ); ?></button>
				</div>
			</div>
			<div class="nexvio-cfe-filters" id="nexvio-cfe-filters" role="tablist"></div>
			<div class="nexvio-cfe-table">
				<div class="nexvio-cfe-thead" aria-hidden="true">
					<span></span>
					<span><?php esc_html_e( 'Field', 'nexvio' ); ?></span>
					<span class="c-type"><?php esc_html_e( 'Type', 'nexvio' ); ?></span>
					<span class="c-width"><?php esc_html_e( 'Width', 'nexvio' ); ?></span>
					<span class="c-status"><?php esc_html_e( 'Status', 'nexvio' ); ?></span>
					<span class="c-show"><?php esc_html_e( 'Visible', 'nexvio' ); ?></span>
					<span></span>
				</div>
				<ul class="nexvio-cfe-list" id="nexvio-cfe-list"></ul>
				<button type="button" class="nexvio-cfe-addrow" id="nexvio-cfe-addrow"><span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span><?php esc_html_e( 'Add a new field', 'nexvio' ); ?></button>
			</div>
		</section>

		<div class="nexvio-glass nexvio-savebar">
			<span id="nexvio-cfe-state"><?php esc_html_e( 'Changes are saved when you click Save changes.', 'nexvio' ); ?></span>
			<button type="button" class="nexvio-btn" id="nexvio-cfe-save"><span class="dashicons dashicons-saved" aria-hidden="true"></span><?php esc_html_e( 'Save changes', 'nexvio' ); ?></button>
		</div>

		<div class="nexvio-cfe-modal" id="nexvio-cfe-modal" hidden>
			<div class="nexvio-cfe-modal__box" role="dialog" aria-modal="true" aria-labelledby="nexvio-cfe-modal-title">
				<header class="nexvio-cfe-modal__head">
					<span class="nexvio-cfe-modal__badge dashicons dashicons-edit" id="nexvio-cfe-modal-badge" aria-hidden="true"></span>
					<div class="nexvio-cfe-modal__titles">
						<h3 id="nexvio-cfe-modal-title"></h3>
						<p id="nexvio-cfe-modal-sub"></p>
					</div>
					<button type="button" class="nexvio-cfe-modal__x" data-close aria-label="<?php esc_attr_e( 'Close', 'nexvio' ); ?>">&times;</button>
				</header>
				<div class="nexvio-cfe-modal__main">
					<div class="nexvio-cfe-modal__form">
						<nav class="nexvio-cfe-mtabs" id="nexvio-cfe-modal-tabs" role="tablist"></nav>
						<div class="nexvio-cfe-modal__body" id="nexvio-cfe-modal-body"></div>
					</div>
				</div>
				<footer class="nexvio-cfe-modal__foot">
					<span class="nexvio-cfe-modal__err" id="nexvio-cfe-modal-err" role="alert"></span>
					<button type="button" class="nexvio-btn nexvio-btn--ghost" data-close><?php esc_html_e( 'Cancel', 'nexvio' ); ?></button>
					<button type="button" class="nexvio-btn" id="nexvio-cfe-modal-ok"><span class="dashicons dashicons-yes" aria-hidden="true"></span><?php esc_html_e( 'Done', 'nexvio' ); ?></button>
				</footer>
			</div>
		</div>
		<?php
 Nexvio_Admin::close(); } public static function enqueue_thankyou() { if ( current_user_can( 'manage_options' ) && false === get_option( Nexvio_Thankyou::OPTION, false ) ) { update_option( Nexvio_Thankyou::OPTION, Nexvio_Thankyou::settings(), false ); Nexvio_Thankyou::ensure_page(); } elseif ( current_user_can( 'manage_options' ) && '2' !== (string) get_option( 'nexvio_ty_setup', '' ) ) { if ( Nexvio_Thankyou::page_id() ) { Nexvio_Thankyou::setup_page( Nexvio_Thankyou::page_id(), (int) Nexvio_Thankyou::settings()['design'] ); } update_option( 'nexvio_ty_setup', '2', false ); } wp_enqueue_style( 'nexvio-thankyou-admin', NEXVIO_URL . 'admin/assets/thankyou.css', array( 'nexvio-admin' ), Nexvio_Plugin::ver( 'admin/assets/thankyou.css' ) ); wp_enqueue_script( 'nexvio-thankyou-admin', NEXVIO_URL . 'admin/assets/thankyou.js', array( 'jquery', 'wp-color-picker' ), Nexvio_Plugin::ver( 'admin/assets/thankyou.js' ), true ); $s = Nexvio_Thankyou::settings(); wp_localize_script( 'nexvio-thankyou-admin', 'NEXVIO_TY', array( 'ajax' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( Nexvio_Thankyou::NONCE ), 'preview' => Nexvio_Thankyou::preview_url( 0 ), 'page' => Nexvio_Thankyou::page_info( $s['page_id'] ), 'colors' => wp_list_pluck( Nexvio_Thankyou::designs(), 2 ), 'i18n' => array( 'saved' => __( 'Saved.', 'nexvio' ), 'saving' => __( 'Saving...', 'nexvio' ), 'unsaved' => __( 'You have unsaved changes.', 'nexvio' ), 'failed' => __( 'Something went wrong. Please try again.', 'nexvio' ), 'creating' => __( 'Creating...', 'nexvio' ), 'none' => __( 'No page yet. Choose a design below and a Thank you page is created for you.', 'nexvio' ), 'live' => __( 'Customers are sent to this page after they order.', 'nexvio' ), 'draft' => __( 'This page is not published, so it is not used yet.', 'nexvio' ), ), ) ); } public static function thankyou_page() { if ( ! current_user_can( 'manage_options' ) ) { return; } Nexvio_Admin::open( Nexvio_Admin::THANKYOU, __( 'Thank You Page', 'nexvio' ), __( 'Choose the page customers see after they order, and one of 10 designs.', 'nexvio' ) ); self::thankyou_panel(); Nexvio_Admin::close(); } private static function thankyou_panel() { $s = Nexvio_Thankyou::settings(); $designs = Nexvio_Thankyou::designs(); $pages = get_pages( array( 'number' => 300, 'sort_column' => 'post_title', 'post_status' => array( 'publish', 'draft', 'private' ) ) ); $toggles = array( 'show_steps' => __( 'Order progress timeline', 'nexvio' ), 'show_items' => __( 'Ordered products', 'nexvio' ), 'show_addr' => __( 'Billing and shipping address', 'nexvio' ), 'show_extras' => __( 'Checkout field answers (delivery date, time...)', 'nexvio' ), 'show_orders' => __( '"View my orders" button', 'nexvio' ), ); ?>
		<section class="nexvio-glass nexvio-card-box nxty-a" id="nexvio-ty" data-design="<?php echo esc_attr( $s['design'] ); ?>">
			<div class="nexvio-box-head">
				<div class="nexvio-cfe__title">
					<h2><?php esc_html_e( 'Thank you page', 'nexvio' ); ?> <span class="nxty-a__on"><?php esc_html_e( 'Always on', 'nexvio' ); ?></span></h2>
					<p><?php esc_html_e( 'Choose the page customers see after they order, and pick one of 10 designs.', 'nexvio' ); ?></p>
				</div>
			</div>

			<div class="nxty-a__page">
				<div class="nxty-a__pagehead">
					<span class="nxty-a__pageico dashicons dashicons-admin-page" aria-hidden="true"></span>
					<div>
						<strong><?php esc_html_e( 'Page', 'nexvio' ); ?></strong>
						<p id="nxty-pagestate"></p>
					</div>
				</div>
				<div class="nxty-a__pagectl">
					<select id="nxty-page" aria-label="<?php esc_attr_e( 'Thank you page', 'nexvio' ); ?>">
						<option value="0"><?php esc_html_e( '- No page yet -', 'nexvio' ); ?></option>
						<?php foreach ( $pages as $pg ) : ?>
							<option value="<?php echo esc_attr( $pg->ID ); ?>" <?php selected( (int) $s['page_id'], $pg->ID ); ?>><?php echo esc_html( $pg->post_title ? $pg->post_title : '#' . $pg->ID ); ?></option>
						<?php endforeach; ?>
					</select>
					<button type="button" class="nexvio-btn nexvio-btn--sm" id="nxty-create"><span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span><?php esc_html_e( 'Create page', 'nexvio' ); ?></button>
					<a class="nexvio-btn nexvio-btn--ghost nexvio-btn--sm" id="nxty-view" target="_blank" rel="noopener" hidden><?php esc_html_e( 'View', 'nexvio' ); ?></a>
					<a class="nexvio-btn nexvio-btn--ghost nexvio-btn--sm" id="nxty-edit" target="_blank" rel="noopener" hidden><?php esc_html_e( 'Edit', 'nexvio' ); ?></a>
				</div>
				<p class="nxty-a__hint"><?php esc_html_e( 'Choosing a design creates the page for you: an Elementor Full Width page without the theme title. Open it with Edit and you can style every part of the design in Elementor (group Thank you).', 'nexvio' ); ?></p>
			</div>

			<h3 class="nxty-a__h"><?php esc_html_e( 'Design', 'nexvio' ); ?></h3>
			<div class="nxty-a__grid" id="nxty-grid" role="radiogroup" aria-label="<?php esc_attr_e( 'Thank you page design', 'nexvio' ); ?>">
				<?php foreach ( $designs as $id => $d ) : ?>
					<div class="nxty-a__card <?php echo (int) $s['design'] === $id ? 'is-on' : ''; ?>" data-design="<?php echo esc_attr( $id ); ?>">
						<button type="button" class="nxty-a__pick" role="radio" aria-checked="<?php echo (int) $s['design'] === $id ? 'true' : 'false'; ?>" aria-label="<?php echo esc_attr( $d[0] ); ?>">
							<span class="nxty-a__img"><img src="<?php echo esc_url( NEXVIO_URL . 'assets/img/thankyou/design-' . $id . '.jpg' ); ?>" alt="" loading="lazy" width="550" height="412"></span>
							<span class="nxty-a__check dashicons dashicons-yes" aria-hidden="true"></span>
						</button>
						<div class="nxty-a__meta">
							<div><strong><?php echo esc_html( $id . '. ' . $d[0] ); ?></strong><span><?php echo esc_html( $d[1] ); ?></span></div>
							<button type="button" class="nxty-a__zoom" data-preview="<?php echo esc_attr( $id ); ?>"><span class="dashicons dashicons-visibility" aria-hidden="true"></span><?php esc_html_e( 'Preview', 'nexvio' ); ?></button>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<h3 class="nxty-a__h"><?php esc_html_e( 'Text and options', 'nexvio' ); ?></h3>
			<div class="nxty-a__form">
				<label><span><?php esc_html_e( 'Headline', 'nexvio' ); ?></span><input type="text" data-k="title" value="<?php echo esc_attr( $s['title'] ); ?>"></label>
				<label><span><?php esc_html_e( 'Button text', 'nexvio' ); ?></span><input type="text" data-k="btn_text" value="<?php echo esc_attr( $s['btn_text'] ); ?>"></label>
				<label class="is-wide"><span><?php esc_html_e( 'Message', 'nexvio' ); ?></span><textarea rows="2" data-k="message"><?php echo esc_textarea( $s['message'] ); ?></textarea></label>
				<p class="nxty-a__hint is-wide"><?php esc_html_e( 'You can use {name}, {order}, {email} and {total} in the headline and message.', 'nexvio' ); ?></p>
				<label><span><?php esc_html_e( 'Button link (empty = shop page)', 'nexvio' ); ?></span><input type="url" data-k="btn_url" placeholder="https://" value="<?php echo esc_attr( $s['btn_url'] ); ?>"></label>
				<div class="nxty-a__color"><span><?php esc_html_e( 'Main colour', 'nexvio' ); ?></span>
					<div class="nxty-a__colorbox">
						<div class="nxty-a__colorrow"><input type="text" id="nxty-accent" class="nxty-color" value="<?php echo esc_attr( $s['accent'] ? $s['accent'] : $designs[ (int) $s['design'] ][2] ); ?>" data-custom="<?php echo $s['accent'] ? '1' : '0'; ?>"><button type="button" class="nexvio-btn nexvio-btn--ghost nexvio-btn--sm" id="nxty-accent-reset"><?php esc_html_e( 'Design colour', 'nexvio' ); ?></button></div>
						<div class="nexvio-swatches" id="nxty-swatches" aria-label="<?php esc_attr_e( 'Ready-made colours', 'nexvio' ); ?>">
							<?php foreach ( array( '#16a34a' => __( 'Green', 'nexvio' ), '#b08d57' => __( 'Gold', 'nexvio' ), '#111111' => __( 'Black', 'nexvio' ), '#1e3a8a' => __( 'Navy', 'nexvio' ), '#9f1239' => __( 'Burgundy', 'nexvio' ), '#c2410c' => __( 'Terracotta', 'nexvio' ), '#d8b4a0' => __( 'Blush', 'nexvio' ), '#6d28d9' => __( 'Violet', 'nexvio' ) ) as $hex => $nm ) : ?>
								<button type="button" class="nexvio-swatch" data-colour="<?php echo esc_attr( $hex ); ?>" style="--c:<?php echo esc_attr( $hex ); ?>" title="<?php echo esc_attr( $nm ); ?>" aria-label="<?php echo esc_attr( $nm ); ?>"></button>
							<?php endforeach; ?>
						</div>
					</div>
				</div>
			</div>
			<div class="nxty-a__toggles">
				<?php foreach ( $toggles as $k => $label ) : ?>
					<label class="nxty-a__tg"><input type="checkbox" data-k="<?php echo esc_attr( $k ); ?>" <?php checked( ! empty( $s[ $k ] ) ); ?>><span class="nxty-a__sw" aria-hidden="true"></span><span><?php echo esc_html( $label ); ?></span></label>
				<?php endforeach; ?>
			</div>
		</section>

		<div class="nexvio-glass nexvio-savebar">
			<span id="nxty-state"><?php esc_html_e( 'Changes are saved when you click Save changes.', 'nexvio' ); ?></span>
			<button type="button" class="nexvio-btn" id="nxty-save"><span class="dashicons dashicons-saved" aria-hidden="true"></span><?php esc_html_e( 'Save changes', 'nexvio' ); ?></button>
		</div>

		<div class="nxty-a__modal" id="nxty-modal" hidden>
			<div class="nxty-a__mbox" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Design preview', 'nexvio' ); ?>">
				<header><strong id="nxty-mtitle"></strong><div><button type="button" class="nexvio-btn nexvio-btn--sm" id="nxty-muse"><?php esc_html_e( 'Use this design', 'nexvio' ); ?></button><button type="button" class="nxty-a__mx" data-close aria-label="<?php esc_attr_e( 'Close', 'nexvio' ); ?>">&times;</button></div></header>
				<iframe id="nxty-frame" title="<?php esc_attr_e( 'Design preview', 'nexvio' ); ?>"></iframe>
			</div>
		</div>
		<?php
 }
private static function guard() {
check_ajax_referer( 'nexvio_cfe', 'nonce' );
if ( ! current_user_can( 'manage_options' ) ) {
wp_send_json_error( null, 403 );
}
$section = isset( $_POST['section'] ) ? sanitize_key( wp_unslash( $_POST['section'] ) ) : '';
if ( ! array_key_exists( $section, Nexvio_Checkout::sections() ) ) {
wp_send_json_error( null, 400 );
}
return $section;
}
public static function ajax_save() {
check_ajax_referer( 'nexvio_cfe', 'nonce' );
$section = self::guard();
// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON/array input that is unslashed here and fully sanitised right after decoding.
$raw = isset( $_POST['fields'] ) ? wp_unslash( $_POST['fields'] ) : '';
$rows = json_decode( (string) $raw, true );
if ( ! is_array( $rows ) || count( $rows ) > 200 ) {
wp_send_json_error( null, 400 );
}
$rows = wp_slash( $rows );
$all = Nexvio_Checkout::saved();
$all[ $section ] = Nexvio_Checkout::sanitize_section( $section, $rows );
update_option( Nexvio_Checkout::OPTION, $all, false );
wp_send_json_success( array( 'fields' => Nexvio_Checkout::builder_fields( $section ) ) );
}
public static function ajax_reset() {
$section = self::guard();
$all = Nexvio_Checkout::saved();
unset( $all[ $section ] );
update_option( Nexvio_Checkout::OPTION, $all, false );
wp_send_json_success( array( 'fields' => Nexvio_Checkout::builder_fields( $section ) ) );
}
} 