<?php
 if ( ! defined( 'ABSPATH' ) ) {
exit;
}
class Nexvio_Epo_Admin {
const PAGE = 'nexvio-epo';
public static function init() {
add_action( 'admin_post_nexvio_epo_save_form', array( __CLASS__, 'save_form' ) );
add_action( 'admin_post_nexvio_epo_save_settings', array( __CLASS__, 'save_settings' ) );
add_action( 'admin_post_nexvio_epo_toggle', array( __CLASS__, 'toggle_form' ) );
add_action( 'admin_post_nexvio_epo_duplicate', array( __CLASS__, 'duplicate_form' ) );
add_action( 'admin_post_nexvio_epo_delete', array( __CLASS__, 'delete_form' ) );
add_action( 'wp_ajax_nexvio_epo_preview', array( __CLASS__, 'ajax_preview' ) );
add_action( 'wp_ajax_nexvio_epo_search_products', array( __CLASS__, 'ajax_search_products' ) );
}
private static function url( $args = array() ) {
return add_query_arg( array_merge( array( 'page' => self::PAGE ), $args ), admin_url( 'admin.php' ) );
}
private static function guard( $nonce ) {
if ( ! current_user_can( 'manage_options' ) ) {
wp_die( esc_html__( 'Not allowed.', 'nexvio' ) );
}
check_admin_referer( $nonce );
}
private static function tab() {
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only request state (filter, sort, page or preview); nothing is saved, so no nonce is needed.
$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only request state (filter, sort, page or preview); nothing is saved, so no nonce is needed.
if ( isset( $_GET['form'] ) ) {
return 'edit';
}
return 'settings' === $tab ? 'settings' : 'forms';
}
public static function enqueue() {
wp_enqueue_style( 'nexvio-epo-admin', NEXVIO_URL . 'admin/assets/epo.css', array( 'nexvio-admin' ), Nexvio_Plugin::ver( 'admin/assets/epo.css' ) );
if ( 'edit' !== self::tab() ) {
return;
}
wp_enqueue_media();
wp_enqueue_style( 'nexvio-epo-preview', NEXVIO_URL . 'assets/css/extra-options.css', array(), Nexvio_Plugin::ver( 'assets/css/extra-options.css' ) );
wp_enqueue_style( 'nexvio-epo-faq-preview', NEXVIO_URL . 'assets/css/product-faq.css', array(), Nexvio_Plugin::ver( 'assets/css/product-faq.css' ) );
wp_enqueue_script( 'nexvio-epo-front', NEXVIO_URL . 'assets/js/extra-options.js', array(), Nexvio_Plugin::ver( 'assets/js/extra-options.js' ), true );
wp_enqueue_script( 'nexvio-epo-admin', NEXVIO_URL . 'admin/assets/epo.js', array( 'jquery', 'nexvio-epo-front' ), Nexvio_Plugin::ver( 'admin/assets/epo.js' ), true );
wp_localize_script( 'nexvio-epo-admin', 'NEXVIO_EPO', self::builder_data() );
}
public static function builder_data( $own_fields = null ) {
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only request state (filter, sort, page or preview); nothing is saved, so no nonce is needed.
$id = isset( $_GET['form'] ) ? absint( wp_unslash( $_GET['form'] ) ) : 0;
$form = $id ? Nexvio_Epo::get_form( $id ) : null;
$types = array();
foreach ( Nexvio_Epo::types() as $k => $t ) {
$types[ $k ] = array( 'label' => $t[0], 'icon' => $t[1], 'desc' => $t[2], 'choice' => in_array( $k, Nexvio_Epo::choice_types(), true ), 'layout' => in_array( $k, Nexvio_Epo::layout_types(), true ), 'prices' => Nexvio_Epo::price_types( $k ), );
}
$symbol = function_exists( 'get_woocommerce_currency_symbol' ) ? html_entity_decode( get_woocommerce_currency_symbol() ) : '€';
$fields = is_array( $own_fields ) ? $own_fields : ( $form ? $form['fields'] : array() );
foreach ( $fields as $k => $fl ) {
if ( isset( $fl['type'] ) && 'faq' === $fl['type'] ) {
$titles = array();
foreach ( isset( $fl['products'] ) ? (array) $fl['products'] : array() as $pid ) {
$t = get_the_title( (int) $pid );
$titles[ (int) $pid ] = $t ? $t : '#' . (int) $pid;
}
$fields[ $k ]['ptitles'] = (object) $titles;
}
}
return array( 'fields' => $fields, 'types' => $types, 'ops' => Nexvio_Epo::logic_ops(), 'currency' => $symbol, 'ajax' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'nexvio_epo_admin' ), 'i18n' => array( 'palette' => __( 'Choose a field type', 'nexvio' ), 'empty' => __( 'No fields yet. Click "Add a field" to start.', 'nexvio' ), 'up' => __( 'Move up', 'nexvio' ), 'down' => __( 'Move down', 'nexvio' ), 'remove' => __( 'Remove', 'nexvio' ), 'copy' => __( 'Duplicate', 'nexvio' ), 'more' => __( 'Options', 'nexvio' ), 'label' => __( 'Field name', 'nexvio' ), 'desc' => __( 'Description under the name', 'nexvio' ), 'placeholder' => __( 'Placeholder text', 'nexvio' ), 'default' => __( 'Starting value', 'nexvio' ), 'required' => __( 'Required: the customer must fill it in', 'nexvio' ), 'hideLabel' => __( 'Hide the field name', 'nexvio' ), 'width' => __( 'Width', 'nexvio' ), 'full' => __( 'Full width', 'nexvio' ), 'half' => __( 'Half', 'nexvio' ), 'third' => __( 'One third', 'nexvio' ), 'maxlength' => __( 'Longest text (0 = no limit)', 'nexvio' ), 'min' => __( 'Smallest value', 'nexvio' ), 'max' => __( 'Largest value', 'nexvio' ), 'step' => __( 'Step', 'nexvio' ), 'price' => __( 'Price', 'nexvio' ), 'priceNone' => __( 'Free', 'nexvio' ), 'priceFixed' => __( 'Fixed amount', 'nexvio' ), 'pricePct' => __( '% of the product price', 'nexvio' ), 'priceChar' => __( 'Amount per character', 'nexvio' ), 'priceValue' => __( 'Amount per unit of the number', 'nexvio' ), 'choices' => __( 'Choices', 'nexvio' ), 'choiceName' => __( 'Choice name', 'nexvio' ), 'addChoice' => __( 'Add a choice', 'nexvio' ), 'bulk' => __( 'Add many', 'nexvio' ), 'bulkHelp' => __( 'One choice per line. Put the price after a bar, e.g. Large | 5 (use 10% for a percentage).', 'nexvio' ), 'bulkAdd' => __( 'Add these choices', 'nexvio' ), 'chosen' => __( 'Selected at the start', 'nexvio' ), 'colour' => __( 'Colour', 'nexvio' ), 'image' => __( 'Image', 'nexvio' ), 'pickImage' => __( 'Select image', 'nexvio' ), 'display' => __( 'Display', 'nexvio' ), 'list' => __( 'List', 'nexvio' ), 'inline' => __( 'In a row', 'nexvio' ), 'buttons' => __( 'Buttons', 'nexvio' ), 'swMode' => __( 'Swatches are', 'nexvio' ), 'swColour' => __( 'Colour circles', 'nexvio' ), 'swImage' => __( 'Pictures', 'nexvio' ), 'swMulti' => __( 'The customer can choose several', 'nexvio' ), 'tag' => __( 'Heading size', 'nexvio' ), 'logic' => __( 'Conditional logic', 'nexvio' ), 'logicOn' => __( 'Show or hide this field with conditions', 'nexvio' ), 'show' => __( 'Show', 'nexvio' ), 'hide' => __( 'Hide', 'nexvio' ), 'thisWhen' => __( 'this field when', 'nexvio' ), 'all' => __( 'all', 'nexvio' ), 'any' => __( 'any', 'nexvio' ), 'ofRules' => __( 'of these rules match:', 'nexvio' ), 'addRule' => __( 'Add a rule', 'nexvio' ), 'pickField' => __( '— field —', 'nexvio' ), 'value' => __( 'value', 'nexvio' ), 'conditional' => __( 'Conditional', 'nexvio' ), 'searchPh' => __( 'Search products by name…', 'nexvio' ), 'noResult' => __( 'No product found.', 'nexvio' ), 'chooseOpt' => __( 'Choose an option', 'nexvio' ), 'uploadBtn' => __( 'Choose a file', 'nexvio' ), 'sample' => __( 'Sample', 'nexvio' ), 'chars' => __( 'characters', 'nexvio' ), 'daySource' => __( 'Day of the week (today)', 'nexvio' ), 'defaultHint' => __( 'Filled in when the page opens. The customer can change it.', 'nexvio' ), 'faqA' => __( 'Answer', 'nexvio' ), 'faqAdd' => __( 'Add a question', 'nexvio' ), 'faqDefaultTitle' => __( 'Frequently asked questions', 'nexvio' ), 'faqHint' => __( 'Write the questions customers ask most. On each product you can choose which of these to show, and add questions for that product only.', 'nexvio' ), 'faqInfo' => __( 'Add the "Product FAQ" widget to your product page to display this FAQ. On a product, open the FAQ tab to choose questions or add your own.', 'nexvio' ), 'faqShowOn' => __( 'Show this FAQ on', 'nexvio' ), 'faqKind' => __( 'What is this list?', 'nexvio' ), 'faqKindFaq' => __( 'FAQ (questions)', 'nexvio' ), 'faqKindFeat' => __( 'Features', 'nexvio' ), 'faqKindInfo' => __( 'Info list', 'nexvio' ), 'faqLook' => __( 'Design', 'nexvio' ), 'faqBadge' => __( 'Badge', 'nexvio' ), 'faqBadgeAuto' => __( 'Automatic', 'nexvio' ), 'faqBadgeQa' => __( 'Q and A', 'nexvio' ), 'faqBadgeNum' => __( 'Numbers', 'nexvio' ), 'faqBadgeCheck' => __( 'Check marks', 'nexvio' ), 'faqBadgeNone' => __( 'None', 'nexvio' ), 'faqAll' => __( 'Always open', 'nexvio' ), 'faqItemTitle' => __( 'Title', 'nexvio' ), 'faqItemText' => __( 'Text', 'nexvio' ), 'faqAddItem' => __( 'Add an item', 'nexvio' ), 'lkCards' => __( 'Cards', 'nexvio' ), 'lkLines' => __( 'Lines', 'nexvio' ), 'lkFilled' => __( 'Filled', 'nexvio' ), 'lkAccent' => __( 'Accent bar', 'nexvio' ), 'lkPills' => __( 'Pills', 'nexvio' ), 'lkDark' => __( 'Dark', 'nexvio' ), 'lkNumbered' => __( 'Numbered', 'nexvio' ), 'lkSoft' => __( 'Soft shadow', 'nexvio' ), 'lkOutline' => __( 'Outline', 'nexvio' ), 'lkTiles' => __( 'Tiles', 'nexvio' ), 'faqShowOnHint' => __( 'Pick the products that get these questions. Other products will not show this FAQ.', 'nexvio' ), 'faqOnProducts' => __( 'Chosen products', 'nexvio' ), 'faqOnForm' => __( 'Same products as the form', 'nexvio' ), 'faqSearch' => __( 'Search products by name…', 'nexvio' ), 'faqNoProducts' => __( 'No product chosen yet. This FAQ will not show on any product until you add one.', 'nexvio' ), 'faqFormNote' => __( 'This FAQ shows on every product the form applies to (see "Where it appears" above).', 'nexvio' ), 'productsWord' => __( 'products', 'nexvio' ), 'faqMany' => __( 'Several can be open', 'nexvio' ), 'faqOne' => __( 'One at a time', 'nexvio' ), 'faqOpenFirst' => __( 'Open the first question', 'nexvio' ), 'faqOpenFirstHint' => __( 'The first answer is already visible when the page loads.', 'nexvio' ), 'faqQ' => __( 'Question', 'nexvio' ), 'faqStyle' => __( 'Opening style', 'nexvio' ), 'faqStyleHint' => __( 'One at a time closes the other answers when one opens.', 'nexvio' ), 'faqTitle' => __( 'FAQ title', 'nexvio' ), 'faqTitleHint' => __( 'Shown above these questions when the widget shows group titles.', 'nexvio' ), 'faqTitlePh' => __( 'e.g. Shipping questions', 'nexvio' ), 'fri' => __( 'Fri', 'nexvio' ), 'groupFields' => __( 'Other fields in this form', 'nexvio' ), 'groupShop' => __( 'The product and the customer', 'nexvio' ), 'listPh' => __( 'a, b, c (separate with commas)', 'nexvio' ), 'loggedIn' => __( 'logged in', 'nexvio' ), 'loggedOut' => __( 'logged out', 'nexvio' ), 'mon' => __( 'Mon', 'nexvio' ), 'noValue' => __( 'No value needed', 'nexvio' ), 'off' => __( 'Off', 'nexvio' ), 'on' => __( 'On', 'nexvio' ), 'opCountGte' => __( 'number picked is at least', 'nexvio' ), 'opCountLte' => __( 'number picked is at most', 'nexvio' ), 'opLenGt' => __( 'text length is more than', 'nexvio' ), 'opLenLt' => __( 'text length is less than', 'nexvio' ), 'priceHeading' => __( 'Extra cost', 'nexvio' ), 'priceIntro' => __( 'Choose if filling in this field adds money to the price.', 'nexvio' ), 'priceSource' => __( 'Product price', 'nexvio' ), 'questionsWord' => __( 'questions', 'nexvio' ), 'quickField' => __( 'Another field has a value', 'nexvio' ), 'quickQty' => __( 'Quantity is 10 or more', 'nexvio' ), 'quickTitle' => __( 'Start with an example, then change it:', 'nexvio' ), 'quickUser' => __( 'Customer is logged in', 'nexvio' ), 'quickWeekend' => __( 'It is the weekend', 'nexvio' ), 'sat' => __( 'Sat', 'nexvio' ), 'secField' => __( 'The field', 'nexvio' ), 'secRules' => __( 'Rules and look', 'nexvio' ), 'stepHint' => __( 'How much the number moves with each click.', 'nexvio' ), 'sumAnd' => __( 'and', 'nexvio' ), 'sumHide' => __( 'This field is hidden when', 'nexvio' ), 'sumOr' => __( 'or', 'nexvio' ), 'sumShow' => __( 'This field is shown when', 'nexvio' ), 'sun' => __( 'Sun', 'nexvio' ), 'swMultiHint' => __( 'Switch on to let the customer pick more than one.', 'nexvio' ), 'tabQuestions' => __( 'Questions', 'nexvio' ), 'thu' => __( 'Thu', 'nexvio' ), 'tue' => __( 'Tue', 'nexvio' ), 'userSource' => __( 'Customer', 'nexvio' ), 'wed' => __( 'Wed', 'nexvio' ), 'tabBasic' => __( 'Basics', 'nexvio' ), 'tabChoices' => __( 'Choices', 'nexvio' ), 'tabPrice' => __( 'Price', 'nexvio' ), 'tabLogic' => __( 'Conditions', 'nexvio' ), 'extraCost' => __( 'Extra cost', 'nexvio' ), 'chosenShort' => __( 'Preselected', 'nexvio' ), 'regularPrice' => __( 'Regular price', 'nexvio' ), 'salePrice' => __( 'Sale price', 'nexvio' ), 'saleNone' => __( 'Optional', 'nexvio' ), 'saleWarn' => __( 'The sale price must be lower than the regular price, otherwise it is ignored.', 'nexvio' ), 'opChosen' => __( 'has a choice picked', 'nexvio' ), 'opNotChosen' => __( 'has nothing picked', 'nexvio' ), 'opHasFile' => __( 'has a file', 'nexvio' ), 'opNoFile' => __( 'has no file', 'nexvio' ), 'logicHint' => __( 'Example: show "Engraving text" only when "Engraving" is Yes. You can also use the quantity, the price, the customer or the day of the week.', 'nexvio' ), 'noRules' => __( 'No rules yet. Add one below.', 'nexvio' ), 'when' => __( 'When', 'nexvio' ), 'or' => __( 'or', 'nexvio' ), 'and' => __( 'and', 'nexvio' ), 'qtySource' => __( 'Quantity (number of items)', 'nexvio' ), 'pickChoice' => __( '— pick a choice —', 'nexvio' ), 'choicesHint' => __( 'The customer picks from these. Add an extra cost to a choice if it costs more. Tick the round button to preselect one.', 'nexvio' ), 'priceHow' => __( 'How is this field priced?', 'nexvio' ), 'priceNoneHint' => __( 'No extra cost.', 'nexvio' ), 'priceFixedHint' => __( 'A fixed amount is added when the customer fills this in.', 'nexvio' ), 'pricePctHint' => __( 'A percentage of the product price is added.', 'nexvio' ), 'priceCharHint' => __( 'Multiplied by the number of characters typed (spaces not counted).', 'nexvio' ), 'priceValueHint' => __( 'Multiplied by the number the customer enters.', 'nexvio' ), 'pricePctAmt' => __( 'Percentage', 'nexvio' ), 'priceAmt' => __( 'Amount', 'nexvio' ), 'dividerHint' => __( 'A thin line to separate parts of the form. It has no settings.', 'nexvio' ), 'headingText' => __( 'Heading text', 'nexvio' ), 'labelHint' => __( 'What the customer sees above the field.', 'nexvio' ), 'descHint' => __( 'Optional help text shown under the name.', 'nexvio' ), 'big' => __( 'big', 'nexvio' ), 'medium' => __( 'medium', 'nexvio' ), 'small' => __( 'small', 'nexvio' ), 'widthHint' => __( 'Half and third let fields sit side by side.', 'nexvio' ), 'placeholderHint' => __( 'Grey hint shown inside an empty field.', 'nexvio' ), 'maxHint' => __( 'Stops the customer typing more.', 'nexvio' ), 'requiredShort' => __( 'Required', 'nexvio' ), 'requiredHint' => __( 'The customer must fill this in before adding to cart.', 'nexvio' ), 'hideLabelHint' => __( 'Hides the name on the shop page.', 'nexvio' ), 'requiredBadge' => __( 'Required', 'nexvio' ), 'choicesWord' => __( 'choices', 'nexvio' ), ), );
}
public static function page() {
if ( ! current_user_can( 'manage_options' ) ) {
return;
}
switch ( self::tab() ) {
case 'edit': self::page_edit();
break;
case 'settings': self::page_settings();
break;
default: self::page_forms();
}
}
private static function tabs( $current ) { ?>
		<nav class="nexvio-epo-tabs" aria-label="<?php esc_attr_e( 'Extra Options sections', 'nexvio' ); ?>">
			<a class="<?php echo 'forms' === $current || 'edit' === $current ? 'is-active' : ''; ?>" href="<?php echo esc_url( self::url() ); ?>"><span class="dashicons dashicons-forms" aria-hidden="true"></span><?php esc_html_e( 'Forms', 'nexvio' ); ?></a>
			<a class="<?php echo 'settings' === $current ? 'is-active' : ''; ?>" href="<?php echo esc_url( self::url( array( 'tab' => 'settings' ) ) ); ?>"><span class="dashicons dashicons-admin-generic" aria-hidden="true"></span><?php esc_html_e( 'Settings', 'nexvio' ); ?></a>
		</nav>
		<?php
 }
private static function notices() {
$msgs = array( 'updated' => __( 'Saved.', 'nexvio' ), 'deleted' => __( 'Form deleted.', 'nexvio' ), 'duplicated' => __( 'Form duplicated.', 'nexvio' ), );
foreach ( $msgs as $k => $m ) {
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only request state (filter, sort, page or preview); nothing is saved, so no nonce is needed.
if ( isset( $_GET[ $k ] ) ) {
echo '<div class="nexvio-alert nexvio-alert--ok"><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>' . esc_html( $m ) . '</div>';
}
}
}
private static function applies_to( $rules ) {
$names = function ( $ids, $tax ) {
$out = array();
foreach ( $ids as $id ) {
$t = get_term( $id, $tax );
if ( $t && ! is_wp_error( $t ) ) {
$out[] = $t->name;
}
}
return $out;
}
;
switch ( $rules['apply'] ) {
case 'categories': $n = $names( $rules['cats'], 'product_cat' );
return $n ? __( 'Categories:', 'nexvio' ) . ' ' . implode( ', ', array_slice( $n, 0, 4 ) ) . ( count( $n ) > 4 ? '…' : '' ) : __( 'No category chosen', 'nexvio' );
case 'tags': $n = $names( $rules['tags'], 'product_tag' );
return $n ? __( 'Tags:', 'nexvio' ) . ' ' . implode( ', ', array_slice( $n, 0, 4 ) ) . ( count( $n ) > 4 ? '…' : '' ) : __( 'No tag chosen', 'nexvio' );
case 'products': return $rules['products'] ? sprintf( /* translators: %d: number of products. */ _n( '%d product', '%d products', count( $rules['products'] ), 'nexvio' ), count( $rules['products'] ) ) : __( 'No product chosen', 'nexvio' );
}
return __( 'All products', 'nexvio' );
}
private static function page_forms() {
$forms = Nexvio_Epo::all_forms();
Nexvio_Admin::open( self::PAGE, __( 'Extra Options', 'nexvio' ), __( 'Add your own fields to the product page: text, choices, colours, files… with prices, in any combination.', 'nexvio' ) );
self::tabs( 'forms' );
self::notices(); ?>
		<ol class="nexvio-howto">
			<li><b>1</b><span><?php esc_html_e( 'Create a form and add fields', 'nexvio' ); ?></span></li>
			<li><b>2</b><span><?php esc_html_e( 'Choose the products it belongs to', 'nexvio' ); ?></span></li>
			<li><b>3</b><span><?php esc_html_e( 'Add the "Extra Options" widget to your product template', 'nexvio' ); ?></span></li>
		</ol>
		<section class="nexvio-glass nexvio-set">
			<header class="nexvio-set__head">
				<span class="nexvio-set__icon dashicons dashicons-forms" aria-hidden="true"></span>
				<div>
					<h2><?php esc_html_e( 'Option forms', 'nexvio' ); ?></h2>
					<p><?php esc_html_e( 'A form is a group of fields. Every form shows on the products it is assigned to; a product can have several forms.', 'nexvio' ); ?></p>
				</div>
				<a class="nexvio-btn nexvio-btn--sm nexvio-epo-head-btn" href="<?php echo esc_url( self::url( array( 'form' => 'new' ) ) ); ?>"><span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span><?php esc_html_e( 'Add a form', 'nexvio' ); ?></a>
			</header>
			<?php if ( ! $forms ) : ?>
				<div class="nexvio-epo-empty">
					<span class="dashicons dashicons-forms" aria-hidden="true"></span>
					<p><?php esc_html_e( 'No form yet. Create your first one, for example "Engraving and gift wrap".', 'nexvio' ); ?></p>
					<a class="nexvio-btn" href="<?php echo esc_url( self::url( array( 'form' => 'new' ) ) ); ?>"><?php esc_html_e( 'Create a form', 'nexvio' ); ?></a>
				</div>
			<?php else : ?>
				<div class="nexvio-epo-table" role="table">
					<div class="nexvio-epo-table__row nexvio-epo-table__row--head" role="row">
						<span role="columnheader"><?php esc_html_e( 'Form', 'nexvio' ); ?></span>
						<span role="columnheader"><?php esc_html_e( 'Applies to', 'nexvio' ); ?></span>
						<span role="columnheader"><?php esc_html_e( 'Fields', 'nexvio' ); ?></span>
						<span role="columnheader"><?php esc_html_e( 'Status', 'nexvio' ); ?></span>
						<span role="columnheader"><?php esc_html_e( 'Actions', 'nexvio' ); ?></span>
					</div>
					<?php foreach ( $forms as $form ) : $live = 'publish' === $form['status']; ?>
						<div class="nexvio-epo-table__row" role="row">
							<span role="cell"><a class="nexvio-epo-table__name" href="<?php echo esc_url( self::url( array( 'form' => $form['id'] ) ) ); ?>"><?php echo esc_html( $form['title'] ? $form['title'] : __( '(no title)', 'nexvio' ) ); ?></a></span>
							<span role="cell"><?php echo esc_html( self::applies_to( $form['rules'] ) ); ?></span>
							<span role="cell"><?php echo esc_html( count( array_filter( $form['fields'], function ( $f ) { return ! in_array( $f['type'], Nexvio_Epo::layout_types(), true ); } ) ) ); ?></span>
							<span role="cell">
								<a class="nexvio-tag <?php echo $live ? 'nexvio-tag--ok' : 'nexvio-tag--warn'; ?>" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=nexvio_epo_toggle&form=' . $form['id'] ), 'nexvio_epo_toggle' ) ); ?>" title="<?php esc_attr_e( 'Click to switch', 'nexvio' ); ?>"><?php echo $live ? esc_html__( 'Active', 'nexvio' ) : esc_html__( 'Draft', 'nexvio' ); ?></a>
							</span>
							<span role="cell" class="nexvio-epo-table__actions">
								<a class="nexvio-btn nexvio-btn--ghost nexvio-btn--sm" href="<?php echo esc_url( self::url( array( 'form' => $form['id'] ) ) ); ?>"><?php esc_html_e( 'Edit', 'nexvio' ); ?></a>
								<a class="nexvio-btn nexvio-btn--ghost nexvio-btn--sm" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=nexvio_epo_duplicate&form=' . $form['id'] ), 'nexvio_epo_duplicate' ) ); ?>"><?php esc_html_e( 'Duplicate', 'nexvio' ); ?></a>
								<a class="nexvio-btn nexvio-btn--ghost nexvio-btn--sm nexvio-epo-del" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=nexvio_epo_delete&form=' . $form['id'] ), 'nexvio_epo_delete' ) ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Delete this form? Its fields disappear from the product pages.', 'nexvio' ) ); ?>');"><?php esc_html_e( 'Delete', 'nexvio' ); ?></a>
							</span>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</section>
		<p class="nexvio-note"><?php esc_html_e( 'Elementor: add the "Extra Options" widget (Nexvio > Single product) to your product template. Without Elementor templates, turn on "Show automatically on product pages" in the Settings tab.', 'nexvio' ); ?></p>
		<?php
 Nexvio_Admin::close();
}
private static function term_checks( $taxonomy, $name, $selected ) {
$terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false, 'number' => 300, 'orderby' => 'name' ) );
if ( is_wp_error( $terms ) || ! $terms ) {
echo '<p class="description">' . esc_html__( 'Nothing to choose yet.', 'nexvio' ) . '</p>';
return;
}
echo '<div class="nexvio-checklist nexvio-checklist--chips">';
foreach ( $terms as $t ) {
echo '<label><input type="checkbox" name="' . esc_attr( $name ) . '[]" value="' . (int) $t->term_id . '"' . checked( in_array( (int) $t->term_id, $selected, true ), true, false ) . '> ' . esc_html( $t->name ) . '</label>';
}
echo '</div>';
}
private static function product_picker( $name, $ids ) {
echo '<div class="nexvio-epo-pick" data-name="' . esc_attr( $name ) . '">';
echo '<ul class="nexvio-epo-chips">';
foreach ( $ids as $pid ) {
$title = get_the_title( $pid );
echo '<li data-id="' . (int) $pid . '"><span>' . esc_html( $title ? $title : '#' . $pid ) . '</span><button type="button" aria-label="' . esc_attr__( 'Remove', 'nexvio' ) . '">×</button><input type="hidden" name="' . esc_attr( $name ) . '[]" value="' . (int) $pid . '"></li>';
}
echo '</ul>';
echo '<input type="search" class="nexvio-epo-pick__search" placeholder="' . esc_attr__( 'Search products by name…', 'nexvio' ) . '" autocomplete="off">';
echo '<ul class="nexvio-epo-pick__results" hidden></ul>';
echo '</div>';
}
private static function page_edit() {
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only request state (filter, sort, page or preview); nothing is saved, so no nonce is needed.
$raw = isset( $_GET['form'] ) ? sanitize_text_field( wp_unslash( $_GET['form'] ) ) : 'new';
$id = absint( $raw );
$form = $id ? Nexvio_Epo::get_form( $id ) : null;
if ( $id && ! $form ) {
wp_safe_redirect( self::url() );
exit;
}
$rules = $form ? $form['rules'] : Nexvio_Epo::sanitize_rules( array() );
$title = $form ? $form['title'] : '';
$live = ! $form || 'publish' === $form['status'];
Nexvio_Admin::open( self::PAGE, $id ? __( 'Edit form', 'nexvio' ) : __( 'New form', 'nexvio' ), __( 'Add the fields, set their prices, then choose where the form appears.', 'nexvio' ) );
self::tabs( 'edit' );
self::notices(); ?>
		<div class="nexvio-cart-layout nexvio-epo-layout">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="nexvio-settings" id="nexvio-epo-form">
				<input type="hidden" name="action" value="nexvio_epo_save_form">
				<input type="hidden" name="form_id" value="<?php echo esc_attr( $id ); ?>">
				<input type="hidden" name="nexvio_epo_json" id="nexvio-epo-json" value="">
				<?php wp_nonce_field( 'nexvio_epo_save_form' ); ?>

				<section class="nexvio-glass nexvio-set">
					<header class="nexvio-set__head">
						<span class="nexvio-set__icon dashicons dashicons-edit" aria-hidden="true"></span>
						<div><h2><?php esc_html_e( 'Form', 'nexvio' ); ?></h2><p><?php esc_html_e( 'The name is only for you. Customers do not see it.', 'nexvio' ); ?></p></div>
					</header>
					<div class="nexvio-row">
						<div class="nexvio-row__label"><label for="nexvio-epo-title"><?php esc_html_e( 'Form name', 'nexvio' ); ?></label></div>
						<div class="nexvio-row__control"><input type="text" id="nexvio-epo-title" name="nexvio_epo_title" value="<?php echo esc_attr( $title ); ?>" placeholder="<?php esc_attr_e( 'e.g. Engraving and gift wrap', 'nexvio' ); ?>" required></div>
					</div>
					<div class="nexvio-row">
						<div class="nexvio-row__label"><strong><?php esc_html_e( 'Active', 'nexvio' ); ?></strong><p><?php esc_html_e( 'A draft form is not shown on the shop.', 'nexvio' ); ?></p></div>
						<div class="nexvio-row__control"><label class="nexvio-toggle"><input type="checkbox" name="nexvio_epo_live" value="yes"<?php checked( $live ); ?>> <?php esc_html_e( 'Show this form on the shop', 'nexvio' ); ?></label></div>
					</div>
					<div class="nexvio-row">
						<div class="nexvio-row__label"><label for="nexvio-epo-prio"><?php esc_html_e( 'Order', 'nexvio' ); ?></label><p><?php esc_html_e( 'When a product has several forms, the lowest number comes first.', 'nexvio' ); ?></p></div>
						<div class="nexvio-row__control"><span class="nexvio-input-unit"><input type="number" id="nexvio-epo-prio" name="nexvio_epo_prio" min="0" max="999" value="<?php echo esc_attr( $form ? $form['priority'] : 10 ); ?>"></span></div>
					</div>
				</section>

				<section class="nexvio-glass nexvio-set" id="nexvio-epo-rules">
					<header class="nexvio-set__head">
						<span class="nexvio-set__icon dashicons dashicons-tag" aria-hidden="true"></span>
						<div><h2><?php esc_html_e( 'Where it appears', 'nexvio' ); ?></h2><p><?php esc_html_e( 'The fields show on the single product page of these products.', 'nexvio' ); ?></p></div>
					</header>
					<div class="nexvio-row nexvio-row--stack">
						<div class="nexvio-row__label"><strong><?php esc_html_e( 'Products', 'nexvio' ); ?></strong></div>
						<div class="nexvio-row__control">
							<div class="nexvio-epo-apply" role="radiogroup">
								<?php
 $apply_opts = array( 'all' => array( __( 'All products', 'nexvio' ), __( 'Every product in the shop', 'nexvio' ), 'dashicons-store' ), 'categories' => array( __( 'Categories', 'nexvio' ), __( 'Products in the categories you tick', 'nexvio' ), 'dashicons-category' ), 'tags' => array( __( 'Tags', 'nexvio' ), __( 'Products with the tags you tick', 'nexvio' ), 'dashicons-tag' ), 'products' => array( __( 'Chosen products', 'nexvio' ), __( 'Pick single products by name', 'nexvio' ), 'dashicons-cart' ), ); foreach ( $apply_opts as $k => $o ) : ?>
									<label class="nexvio-epo-apply__opt"><input type="radio" name="nexvio_epo_rules[apply]" value="<?php echo esc_attr( $k ); ?>"<?php checked( $rules['apply'], $k ); ?>><span class="nexvio-epo-apply__card"><i class="dashicons <?php echo esc_attr( $o[2] ); ?>" aria-hidden="true"></i><b><?php echo esc_html( $o[0] ); ?></b><small><?php echo esc_html( $o[1] ); ?></small></span></label>
								<?php endforeach; ?>
							</div>
							<div class="nexvio-epo-panel" data-apply="categories"><?php self::term_checks( 'product_cat', 'nexvio_epo_rules[cats]', $rules['cats'] ); ?></div>
							<div class="nexvio-epo-panel" data-apply="tags"><?php self::term_checks( 'product_tag', 'nexvio_epo_rules[tags]', $rules['tags'] ); ?></div>
							<div class="nexvio-epo-panel" data-apply="products"><?php self::product_picker( 'nexvio_epo_rules[products]', $rules['products'] ); ?></div>
						</div>
					</div>
					<details class="nexvio-epo-excl"<?php echo $rules['excl_cats'] || $rules['excl_products'] ? ' open' : ''; ?>>
						<summary><span class="dashicons dashicons-dismiss" aria-hidden="true"></span><?php esc_html_e( 'Leave some products out', 'nexvio' ); ?><small><?php esc_html_e( 'optional', 'nexvio' ); ?></small></summary>
						<div class="nexvio-epo-excl__body">
							<div class="nexvio-epo-excl__col">
								<strong><?php esc_html_e( 'Not in these categories', 'nexvio' ); ?></strong>
								<?php self::term_checks( 'product_cat', 'nexvio_epo_rules[excl_cats]', $rules['excl_cats'] ); ?>
							</div>
							<div class="nexvio-epo-excl__col">
								<strong><?php esc_html_e( 'Not on these products', 'nexvio' ); ?></strong>
								<?php self::product_picker( 'nexvio_epo_rules[excl_products]', $rules['excl_products'] ); ?>
							</div>
						</div>
					</details>
				</section>

				<section class="nexvio-glass nexvio-set">
					<header class="nexvio-set__head">
						<span class="nexvio-set__icon dashicons dashicons-forms" aria-hidden="true"></span>
						<div><h2><?php esc_html_e( 'Fields', 'nexvio' ); ?></h2><p><?php esc_html_e( 'They appear in this order. Drag a field or use the arrows to move it.', 'nexvio' ); ?></p></div>
					</header>
					<ul class="nexvio-fb__list" id="nexvio-epo-list" aria-live="polite"></ul>
					<div class="nexvio-fb__add">
						<button type="button" class="nexvio-btn" id="nexvio-epo-add" aria-expanded="false" aria-controls="nexvio-epo-palette"><span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span><?php esc_html_e( 'Add a field', 'nexvio' ); ?></button>
					</div>
					<div class="nexvio-fb__palette" id="nexvio-epo-palette" hidden></div>
				</section>

				<div class="nexvio-glass nexvio-savebar">
					<span><a href="<?php echo esc_url( self::url() ); ?>">← <?php esc_html_e( 'All forms', 'nexvio' ); ?></a></span>
					<button type="submit" class="nexvio-btn"><span class="dashicons dashicons-saved" aria-hidden="true"></span><?php esc_html_e( 'Save form', 'nexvio' ); ?></button>
				</div>
			</form>

			<aside class="nexvio-preview nexvio-epo-pv" aria-label="<?php esc_attr_e( 'Live preview', 'nexvio' ); ?>">
				<div class="nexvio-preview__label"><span class="dashicons dashicons-visibility" aria-hidden="true"></span><?php esc_html_e( 'Live preview', 'nexvio' ); ?></div>
				<div class="nexvio-epo-pv__stage" id="nexvio-epo-stage"><div id="nexvio-epo-preview"></div></div>
				<p class="nexvio-preview__note"><?php esc_html_e( 'Try it like a customer: pick options and watch the price and the conditional fields react. Nothing is saved.', 'nexvio' ); ?></p>
			</aside>
		</div>
		<?php
 Nexvio_Admin::close(); } private static function page_settings() { Nexvio_Admin::open( self::PAGE, __( 'Extra Options', 'nexvio' ), __( 'Behaviour, look and texts of the extra fields on the product page.', 'nexvio' ) ); self::tabs( 'settings' ); self::notices(); ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="nexvio-settings" id="nexvio-epo-settings">
			<input type="hidden" name="action" value="nexvio_epo_save_settings">
			<?php wp_nonce_field( 'nexvio_epo_save_settings' ); ?>
			<?php foreach ( Nexvio_Epo_Settings::schema() as $group_key => $group ) : ?>
				<section class="nexvio-glass nexvio-set" id="nexvio-epo-set-<?php echo esc_attr( $group_key ); ?>">
					<header class="nexvio-set__head">
						<span class="nexvio-set__icon dashicons <?php echo esc_attr( $group[1] ); ?>" aria-hidden="true"></span>
						<div>
							<h2><?php echo esc_html( $group[0] ); ?></h2>
							<?php if ( $group[2] ) : ?><p><?php echo esc_html( $group[2] ); ?></p><?php endif; ?>
						</div>
					</header>
					<?php
 foreach ( $group[3] as $key => $f ) { $value = 'text' === $f[0] ? Nexvio_Epo_Settings::text( $key ) : Nexvio_Epo_Settings::get( $key ); Nexvio_Admin::cart_field( $key, $f, $value, 'nexvio_epo_set' ); } ?>
				</section>
			<?php endforeach; ?>
			<div class="nexvio-glass nexvio-savebar">
				<span><?php esc_html_e( 'Prices of the options use the same tax setting as your product prices.', 'nexvio' ); ?></span>
				<button type="submit" class="nexvio-btn"><span class="dashicons dashicons-saved" aria-hidden="true"></span><?php esc_html_e( 'Save settings', 'nexvio' ); ?></button>
			</div>
		</form>
		<?php
 Nexvio_Admin::close();
}
public static function save_form() {
if ( ! current_user_can( 'manage_options' ) ) {
wp_die( esc_html__( 'Not allowed.', 'nexvio' ) );
}
check_admin_referer( 'nexvio_epo_save_form' );
$id = isset( $_POST['form_id'] ) ? absint( wp_unslash( $_POST['form_id'] ) ) : 0;
$title = isset( $_POST['nexvio_epo_title'] ) ? sanitize_text_field( wp_unslash( $_POST['nexvio_epo_title'] ) ) : '';
$title = '' !== $title ? $title : __( 'Untitled form', 'nexvio' );
// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON/array input that is unslashed here and fully sanitised right after decoding.
$json = isset( $_POST['nexvio_epo_json'] ) ? wp_unslash( $_POST['nexvio_epo_json'] ) : '[]';
$fields = json_decode( is_string( $json ) ? $json : '[]', true );
$rules = isset( $_POST['nexvio_epo_rules'] ) && is_array( $_POST['nexvio_epo_rules'] ) ? map_deep( wp_unslash( $_POST['nexvio_epo_rules'] ), 'sanitize_text_field' ) : array();
$status = ! empty( $_POST['nexvio_epo_live'] ) ? 'publish' : 'draft';
$prio = isset( $_POST['nexvio_epo_prio'] ) ? max( 0, min( 999, absint( wp_unslash( $_POST['nexvio_epo_prio'] ) ) ) ) : 10;
if ( $id && ! Nexvio_Epo::get_form( $id ) ) {
$id = 0;
}
$saved = Nexvio_Epo::save_form( $id, $title, $status, is_array( $fields ) ? $fields : array(), $rules, $prio );
wp_safe_redirect( $saved ? self::url( array( 'form' => $saved, 'updated' => 1 ) ) : self::url() );
exit;
}
public static function save_settings() {
if ( ! current_user_can( 'manage_options' ) ) {
wp_die( esc_html__( 'Not allowed.', 'nexvio' ) );
}
check_admin_referer( 'nexvio_epo_save_settings' );
$input = isset( $_POST['nexvio_epo_set'] ) && is_array( $_POST['nexvio_epo_set'] ) ? map_deep( wp_unslash( $_POST['nexvio_epo_set'] ), 'sanitize_text_field' ) : array();
update_option( Nexvio_Epo_Settings::OPTION, Nexvio_Epo_Settings::sanitize( $input ) );
Nexvio_Epo_Settings::flush();
wp_safe_redirect( self::url( array( 'tab' => 'settings', 'updated' => 1 ) ) );
exit;
}
private static function form_from_request() {
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only request state (filter, sort, page or preview); nothing is saved, so no nonce is needed.
$id = isset( $_GET['form'] ) ? absint( wp_unslash( $_GET['form'] ) ) : 0;
return $id ? Nexvio_Epo::get_form( $id ) : null;
}
public static function toggle_form() {
self::guard( 'nexvio_epo_toggle' );
$form = self::form_from_request();
if ( $form ) {
wp_update_post( array( 'ID' => $form['id'], 'post_status' => 'publish' === $form['status'] ? 'draft' : 'publish' ) );
Nexvio_Epo::flush();
}
wp_safe_redirect( self::url( array( 'updated' => 1 ) ) );
exit;
}
public static function duplicate_form() {
self::guard( 'nexvio_epo_duplicate' );
$form = self::form_from_request();
if ( $form ) {
$fields = array_map( function ( $f ) {
$f['id'] = '';
return $f;
}
, $form['fields'] );
$new = Nexvio_Epo::save_form( 0, sprintf( /* translators: %s: title of the original form. */ __( '%s (copy)', 'nexvio' ), $form['title'] ), 'draft', $fields, $form['rules'], $form['priority'] );
wp_safe_redirect( $new ? self::url( array( 'form' => $new, 'duplicated' => 1 ) ) : self::url() );
exit;
}
wp_safe_redirect( self::url() );
exit;
}
public static function delete_form() {
self::guard( 'nexvio_epo_delete' );
$form = self::form_from_request();
if ( $form ) {
wp_delete_post( $form['id'], true );
Nexvio_Epo::flush();
}
wp_safe_redirect( self::url( array( 'deleted' => 1 ) ) );
exit;
}
public static function ajax_search_products() {
check_ajax_referer( 'nexvio_epo_admin', 'nonce' );
if ( ! current_user_can( 'manage_options' ) ) {
wp_send_json_error( null, 403 );
}
$term = isset( $_GET['term'] ) ? sanitize_text_field( wp_unslash( $_GET['term'] ) ) : '';
$out = array();
if ( strlen( $term ) >= 2 ) {
$ids = get_posts( array( 'post_type' => 'product', 'post_status' => array( 'publish', 'draft', 'private' ), 's' => $term, 'posts_per_page' => 12, 'fields' => 'ids', 'no_found_rows' => true ) );
foreach ( $ids as $pid ) {
$out[] = array( 'id' => (int) $pid, 'title' => get_the_title( $pid ) );
}
}
wp_send_json_success( $out );
}
public static function ajax_preview() {
check_ajax_referer( 'nexvio_epo_admin', 'nonce' );
if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'edit_products' ) ) {
wp_send_json_error( null, 403 );
}
// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON/array input that is unslashed here and fully sanitised right after decoding.
$raw = isset( $_POST['fields'] ) ? wp_unslash( $_POST['fields'] ) : '[]';
$data = json_decode( (string) $raw, true );
$fields = Nexvio_Epo::sanitize_fields( is_array( $data ) ? $data : array() );
$base = isset( $_POST['base'] ) ? floatval( wp_unslash( $_POST['base'] ) ) : 0;
$html = $fields ? Nexvio_Epo_Render::preview( $fields, $base ) : '';
wp_send_json_success( array( 'html' => wp_kses( $html, Nexvio_Epo_Render::allowed() ) ) );
}
} 