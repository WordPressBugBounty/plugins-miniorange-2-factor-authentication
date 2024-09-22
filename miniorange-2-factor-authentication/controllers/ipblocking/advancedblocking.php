<?php
/**
 * This file includes the UI for 2fa methods options.
 *
 * @package miniorange-2-factor-authentication/controllers/ipblocking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$range_count = is_numeric( get_option( 'mo_wpns_iprange_count' ) ) && intval( get_option( 'mo_wpns_iprange_count' ) ) !== 0 ? intval( get_option( 'mo_wpns_iprange_count' ) ) : 1;
for ( $i = 1; $i <= $range_count; $i++ ) {
	$ip_range = get_option( 'mo_wpns_iprange_range_' . $i );
	if ( $ip_range ) {
		$a = explode( '-', $ip_range );

		$start[ $i ] = $a[0];
		$end[ $i ]   = $a[1];
	}
}
if ( ! isset( $start[1] ) ) {
	$start[1] = '';
}
if ( ! isset( $end[1] ) ) {
	$end[1] = '';
}

require_once dirname( dirname( dirname( __FILE__ ) ) ) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'twofa' . DIRECTORY_SEPARATOR . 'link-tracer.php';
require dirname( dirname( dirname( __FILE__ ) ) ) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'ipblocking' . DIRECTORY_SEPARATOR . 'advancedblocking.php';
