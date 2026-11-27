<?php
 if ( ! defined( 'ABSPATH' ) ) {
exit;
}
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup is built by Nexvio_Thankyou, which escapes every dynamic value.
echo Nexvio_Thankyou::output( array( 'order' => $order ) ); 