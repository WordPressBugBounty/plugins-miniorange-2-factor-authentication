<?php
/**
 * This file includes the UI for 2fa methods options.
 *
 * @package miniorange-2-factor-authentication/controllers/ipblocking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mo2f_range_count = is_numeric( get_site_option( 'mo_wpns_iprange_count' ) ) && intval( get_site_option( 'mo_wpns_iprange_count' ) ) !== 0 ? intval( get_site_option( 'mo_wpns_iprange_count' ) ) : 1;
for ( $mo2f_i = 1; $mo2f_i <= $mo2f_range_count; $mo2f_i++ ) {
	$mo2f_ip_range = get_site_option( 'mo_wpns_iprange_range_' . $mo2f_i );
	if ( $mo2f_ip_range ) {
		$mo2f_a = explode( '-', $mo2f_ip_range );

		$mo2f_start[ $mo2f_i ] = $mo2f_a[0];
		$mo2f_end[ $mo2f_i ]   = $mo2f_a[1];
	}
}
if ( ! isset( $mo2f_start[1] ) ) {
	$mo2f_start[1] = '';
}
if ( ! isset( $mo2f_end[1] ) ) {
	$mo2f_end[1] = '';
}

require_once dirname( dirname( __DIR__ ) ) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'twofa' . DIRECTORY_SEPARATOR . 'link-tracer.php';
require dirname( dirname( __DIR__ ) ) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'ipblocking' . DIRECTORY_SEPARATOR . 'advancedblocking.php';
