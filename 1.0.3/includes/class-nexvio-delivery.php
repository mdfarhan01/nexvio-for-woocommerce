<?php
 defined( 'ABSPATH' ) || exit;
class Nexvio_Delivery {
public static function defaults( $type ) {
if ( 'deliverytime' === $type ) {
return array( 'schedule' => array( array( 'days' => array( 1, 2, 3, 4, 5, 6 ), 'from' => '10:00', 'to' => '18:00', 'length' => 60, 'cap' => 0, ), ), 'notice' => 0, 'format' => '12', );
}
return array( 'min_days' => 1, 'max_days' => 30, 'skip_closed' => true, 'closed' => array(), 'cutoff' => '', 'blackout' => '', 'cap' => 0, );
}
private static function clean_time( $v ) {
if ( ! is_string( $v ) || ! preg_match( '/^\s*(\d{1,2}):(\d{1,2})\s*$/', $v, $m ) ) {
return '';
}
$h = (int) $m[1];
$i = (int) $m[2];
return ( $h > 23 || $i > 59 ) ? '' : sprintf( '%02d:%02d', $h, $i );
}
private static function days( $list ) {
$out = array();
foreach ( (array) $list as $d ) {
if ( is_numeric( $d ) && (int) $d >= 0 && (int) $d <= 6 ) {
$out[ (int) $d ] = (int) $d;
}
}
ksort( $out );
return array_values( $out );
}
public static function clean( $type, $in ) {
$def = self::defaults( $type );
$in = is_array( $in ) ? $in : array();
if ( 'deliverytime' === $type ) {
$sched = array();
$rows = isset( $in['schedule'] ) && is_array( $in['schedule'] ) ? $in['schedule'] : array();
foreach ( array_slice( $rows, 0, 14 ) as $r ) {
if ( ! is_array( $r ) ) {
continue;
}
$from = self::clean_time( isset( $r['from'] ) ? $r['from'] : '' );
$to = self::clean_time( isset( $r['to'] ) ? $r['to'] : '' );
$days = self::days( isset( $r['days'] ) ? $r['days'] : array() );
$length = isset( $r['length'] ) ? max( 5, min( 720, (int) $r['length'] ) ) : 60;
if ( '' === $from || '' === $to || ! $days || $to <= $from ) {
continue;
}
$sched[] = array( 'days' => $days, 'from' => $from, 'to' => $to, 'length' => $length, 'cap' => isset( $r['cap'] ) ? max( 0, min( 9999, (int) $r['cap'] ) ) : 0, );
}
return array( 'schedule' => $sched ? $sched : $def['schedule'], 'notice' => isset( $in['notice'] ) ? max( 0, min( 720, (int) $in['notice'] ) ) : 0, 'format' => isset( $in['format'] ) && '24' === (string) $in['format'] ? '24' : '12', );
}
$black = isset( $in['blackout'] ) ? (string) $in['blackout'] : '';
$black = implode( "\n", array_slice( array_filter( array_map( 'sanitize_text_field', preg_split( '/\r\n|\r|\n/', $black ) ), 'strlen' ), 0, 200 ) );
return array( 'min_days' => isset( $in['min_days'] ) ? max( 0, min( 365, (int) $in['min_days'] ) ) : $def['min_days'], 'max_days' => isset( $in['max_days'] ) ? max( 1, min( 365, (int) $in['max_days'] ) ) : $def['max_days'], 'skip_closed' => ! isset( $in['skip_closed'] ) || ! empty( $in['skip_closed'] ), 'closed' => self::days( isset( $in['closed'] ) ? $in['closed'] : array() ), 'cutoff' => self::clean_time( isset( $in['cutoff'] ) ? $in['cutoff'] : '' ), 'blackout' => $black, 'cap' => isset( $in['cap'] ) ? max( 0, min( 9999, (int) $in['cap'] ) ) : 0, );
}
private static function cfg( $p, $type ) {
$d = isset( $p['delivery'] ) && is_array( $p['delivery'] ) ? $p['delivery'] : array();
return self::clean( $type, $d );
}
private static function now() {
return new DateTimeImmutable( 'now', wp_timezone() );
}
private static function parse( $ymd ) {
if ( ! is_string( $ymd ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $ymd ) ) {
return null;
}
$d = DateTimeImmutable::createFromFormat( '!Y-m-d', $ymd, wp_timezone() );
return $d && $d->format( 'Y-m-d' ) === $ymd ? $d : null;
}
private static function ranges( $text ) {
$out = array();
foreach ( preg_split( '/\r\n|\r|\n/', (string) $text ) as $line ) {
if ( ! preg_match_all( '/\d{4}-\d{2}-\d{2}/', $line, $m ) || ! self::parse( $m[0][0] ) ) {
continue;
}
$a = $m[0][0];
$b = isset( $m[0][1] ) && self::parse( $m[0][1] ) ? $m[0][1] : $a;
$out[] = $a <= $b ? array( $a, $b ) : array( $b, $a );
}
return $out;
}
private static function day_closed( $d, $closed, $ranges ) {
if ( in_array( (int) $d->format( 'w' ), $closed, true ) ) {
return true;
}
$ymd = $d->format( 'Y-m-d' );
foreach ( $ranges as $r ) {
if ( $ymd >= $r[0] && $ymd <= $r[1] ) {
return true;
}
}
return false;
}
public static function window( $p ) {
$c = self::cfg( $p, 'deliverydate' );
$now = self::now();
$base = $now->setTime( 0, 0 );
$ranges = self::ranges( $c['blackout'] );
if ( '' !== $c['cutoff'] && $now->format( 'H:i' ) >= $c['cutoff'] ) {
$base = $base->modify( '+1 day' );
}
$min = $base;
$steps = (int) $c['min_days'];
if ( $c['skip_closed'] ) {
$guard = 0;
while ( $steps > 0 && $guard++ < 800 ) {
$min = $min->modify( '+1 day' );
if ( ! self::day_closed( $min, $c['closed'], $ranges ) ) {
--$steps;
}
}
}
else {
$min = $min->modify( '+' . $steps . ' day' );
}
$max = $min->modify( '+' . (int) $c['max_days'] . ' day' );
return array( 'min' => $min->format( 'Y-m-d' ), 'max' => $max->format( 'Y-m-d' ), 'closed' => $c['closed'], 'ranges' => $ranges, 'cap' => (int) $c['cap'], 'today' => $now->format( 'Y-m-d' ), 'now' => $now->format( 'Y-m-d H:i' ), );
}
public static function date_ok( $p, $ymd, $name = '' ) {
$d = self::parse( $ymd );
if ( ! $d ) {
return false;
}
$w = self::window( $p );
if ( $ymd < $w['min'] || $ymd > $w['max'] || self::day_closed( $d, $w['closed'], $w['ranges'] ) ) {
return false;
}
if ( $w['cap'] > 0 && '' !== $name ) {
$b = self::booked( $name, '', $ymd, $ymd );
if ( isset( $b['d'][ $ymd ] ) && $b['d'][ $ymd ] >= $w['cap'] ) {
return false;
}
}
return true;
}
public static function booked( $date_name, $time_name, $from, $to ) {
static $cache = array();
$key = $date_name . '|' . $time_name . '|' . $from . '|' . $to;
if ( isset( $cache[ $key ] ) ) {
return $cache[ $key ];
}
$out = array( 'd' => array(), 's' => array() );
if ( ! function_exists( 'wc_get_orders' ) || '' === $date_name ) {
return $out;
}
// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Needed to filter or sort WooCommerce data; no cheaper core API and the query is bounded.
$orders = wc_get_orders( array( 'limit' => 2000, 'status' => array( 'wc-pending', 'wc-processing', 'wc-on-hold', 'wc-completed' ), 'type' => 'shop_order', 'meta_query' => array( array( 'key' => $date_name, 'value' => array( $from, $to ), 'compare' => 'BETWEEN', 'type' => 'CHAR', ), ), ) );
foreach ( $orders as $o ) {
$d = (string) $o->get_meta( $date_name );
if ( '' === $d ) {
continue;
}
$out['d'][ $d ] = ( isset( $out['d'][ $d ] ) ? $out['d'][ $d ] : 0 ) + 1;
if ( '' !== $time_name ) {
$s = (string) $o->get_meta( $time_name );
if ( '' !== $s ) {
$out['s'][ $d . '|' . $s ] = ( isset( $out['s'][ $d . '|' . $s ] ) ? $out['s'][ $d . '|' . $s ] : 0 ) + 1;
}
}
}
$cache[ $key ] = $out;
return $out;
}
private static function mins( $hhmm ) {
return (int) substr( $hhmm, 0, 2 ) * 60 + (int) substr( $hhmm, 3, 2 );
}
private static function show_time( $mins, $format ) {
$h = (int) floor( $mins / 60 ) % 24;
$i = $mins % 60;
if ( '24' === $format ) {
return sprintf( '%02d:%02d', $h, $i );
}
$ap = $h >= 12 ? 'PM' : 'AM';
$h12 = $h % 12 ? $h % 12 : 12;
return sprintf( '%d:%02d %s', $h12, $i, $ap );
}
public static function slots( $p ) {
$c = self::cfg( $p, 'deliverytime' );
$slots = array();
foreach ( $c['schedule'] as $r ) {
$from = self::mins( $r['from'] );
$to = self::mins( $r['to'] );
$n = 0;
for ( $t = $from;
$t + $r['length'] <= $to && $n < 200;
$t += $r['length'], $n++ ) {
$e = $t + $r['length'];
$key = sprintf( '%02d%02d-%02d%02d', floor( $t / 60 ), $t % 60, floor( $e / 60 ), $e % 60 );
if ( isset( $slots[ $key ] ) ) {
$slots[ $key ]['days'] = self::days( array_merge( $slots[ $key ]['days'], $r['days'] ) );
$slots[ $key ]['cap'] = ( 0 === $slots[ $key ]['cap'] || 0 === (int) $r['cap'] ) ? 0 : max( $slots[ $key ]['cap'], (int) $r['cap'] );
continue;
}
$slots[ $key ] = array( 'key' => $key, 'label' => self::show_time( $t, $c['format'] ) . ' – ' . self::show_time( $e, $c['format'] ), 'from' => $t, 'days' => $r['days'], 'cap' => (int) $r['cap'], );
}
}
uasort( $slots, function ( $a, $b ) {
return $a['from'] - $b['from'];
}
);
return $slots;
}
public static function slot_label( $p, $key ) {
$slots = self::slots( $p );
return isset( $slots[ $key ] ) ? $slots[ $key ]['label'] : $key;
}
public static function date_label( $ymd ) {
$d = self::parse( $ymd );
return $d ? date_i18n( get_option( 'date_format' ), $d->getTimestamp() + 12 * HOUR_IN_SECONDS ) : $ymd;
}
public static function slot_ok( $p, $key, $ymd = '', $date_name = '', $time_name = '' ) {
$slots = self::slots( $p );
if ( ! isset( $slots[ $key ] ) ) {
return false;
}
$s = $slots[ $key ];
if ( '' === $ymd ) {
return true;
}
$d = self::parse( $ymd );
if ( ! $d || ! in_array( (int) $d->format( 'w' ), $s['days'], true ) ) {
return false;
}
$c = self::cfg( $p, 'deliverytime' );
$now = self::now();
if ( $ymd === $now->format( 'Y-m-d' ) && $s['from'] < (int) $now->format( 'G' ) * 60 + (int) $now->format( 'i' ) + $c['notice'] * 60 ) {
return false;
}
if ( $s['cap'] > 0 && '' !== $time_name ) {
$b = self::booked( $date_name, $time_name, $ymd, $ymd );
if ( isset( $b['s'][ $ymd . '|' . $key ] ) && $b['s'][ $ymd . '|' . $key ] >= $s['cap'] ) {
return false;
}
}
return true;
}
public static function date_config( $p, $name ) {
$w = self::window( $p );
$full = array();
if ( $w['cap'] > 0 ) {
$b = self::booked( $name, '', $w['min'], $w['max'] );
foreach ( $b['d'] as $d => $n ) {
if ( $n >= $w['cap'] ) {
$full[] = $d;
}
}
}
return array( 'min' => $w['min'], 'max' => $w['max'], 'closed' => $w['closed'], 'ranges' => $w['ranges'], 'full' => $full, 'today' => $w['today'], 'weekStart' => (int) get_option( 'start_of_week', 1 ), 'placeholder' => isset( $p['placeholder'] ) && '' !== $p['placeholder'] ? $p['placeholder'] : __( 'Select a date', 'nexvio' ), 'i18n' => array( 'prev' => __( 'Previous month', 'nexvio' ), 'next' => __( 'Next month', 'nexvio' ), 'earliest' => __( 'Earliest available date', 'nexvio' ), 'none' => __( 'No dates are available.', 'nexvio' ), 'full' => __( 'Fully booked', 'nexvio' ), 'closed' => __( 'Not available', 'nexvio' ), 'pick' => __( 'Choose a delivery date', 'nexvio' ), ), );
}
public static function time_config( $p, $name, $date_name ) {
$c = self::cfg( $p, 'deliverytime' );
$slots = array();
$full = array();
$caps = false;
foreach ( self::slots( $p ) as $s ) {
$slots[] = array( 'k' => $s['key'], 'l' => $s['label'], 'f' => $s['from'], 'd' => $s['days'], 'c' => $s['cap'] );
$caps = $caps || $s['cap'] > 0;
}
if ( $caps && '' !== $date_name ) {
$now = self::now();
$b = self::booked( $date_name, $name, $now->format( 'Y-m-d' ), $now->modify( '+400 day' )->format( 'Y-m-d' ) );
foreach ( $b['s'] as $k => $n ) {
list( $d, $slot ) = explode( '|', $k, 2 );
foreach ( $slots as $s ) {
if ( $s['k'] === $slot && $s['c'] > 0 && $n >= $s['c'] ) {
$full[ $d ][] = $slot;
}
}
}
}
$now = self::now();
return array( 'dateField' => $date_name, 'slots' => $slots, 'full' => (object) $full, 'notice' => (int) $c['notice'], 'today' => $now->format( 'Y-m-d' ), 'nowMin' => (int) $now->format( 'G' ) * 60 + (int) $now->format( 'i' ), 'placeholder' => isset( $p['placeholder'] ) && '' !== $p['placeholder'] ? $p['placeholder'] : __( 'Select a time', 'nexvio' ), 'i18n' => array( 'firstDate' => __( 'Choose a delivery date first', 'nexvio' ), 'noSlots' => __( 'No time slots on this day', 'nexvio' ), 'full' => __( 'Full', 'nexvio' ), ), );
}
} 