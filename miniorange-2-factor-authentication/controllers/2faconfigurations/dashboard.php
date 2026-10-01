<?php
/**
 * This file is controller for twofactor/quicksetup/views/2faconfigurations/dashboard.php.
 *
 * @package miniorange-2-factor-authentication/controllers/2faconfigurations
 */

use TwoFA\Helper\Mo2f_Common_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wp_roles, $mo2fdb_queries, $mo2f_wpns_db_queries, $mo2f_dir_name;

$mo2f_lv_needed = apply_filters( 'mo2f_is_lv_needed', false );

// Which roles are required to have 2FA, and how many users are in each.
$mo2f_required_roles = array();
foreach ( $wp_roles->role_names as $mo2f_role_id => $mo2f_role_name ) {
	if ( get_site_option( 'mo2fa_' . $mo2f_role_id ) ) {
		$mo2f_required_roles[] = $mo2f_role_id;
	}
}
$mo2f_role_user_counts   = count_users();
$mo2f_required_users_total = 0;
foreach ( $mo2f_required_roles as $mo2f_role_id ) {
	if ( isset( $mo2f_role_user_counts['avail_roles'][ $mo2f_role_id ] ) ) {
		$mo2f_required_users_total += (int) $mo2f_role_user_counts['avail_roles'][ $mo2f_role_id ];
	}
}

$mo2f_configured_users_total = $mo2fdb_queries->mo2f_get_configured_2fa_users_count();
$mo2f_configured_in_required = min( $mo2f_configured_users_total, $mo2f_required_users_total );
$mo2f_pending_users_total    = max( 0, $mo2f_required_users_total - $mo2f_configured_in_required );
$mo2f_adoption_percent       = $mo2f_required_users_total > 0
	? (int) round( ( $mo2f_configured_in_required / $mo2f_required_users_total ) * 100 )
	: 0;

$mo2f_enable_2fa         = get_site_option( 'mo2f_activate_plugin' );
$mo2f_methods_selected   = (array) get_site_option( 'mo2f_auth_methods_for_users', array() );
$mo2f_change_alerts_on   = get_site_option( 'mo2f_enable_2fa_change_alerts', 1 );
$mo2f_reminder_emails_on = get_site_option( 'mo2f_enable_2fa_reminder_emails' );
$mo2f_account_connected  = ! empty( get_site_option( 'mo2f_customerKey' ) );

$mo2f_recent_activity = array();
if ( isset( $mo2f_wpns_db_queries ) ) {
	$mo2f_recent_activity = array_slice( (array) $mo2f_wpns_db_queries->mo2f_get_login_transaction_report(), 0, 6 );
}

require $mo2f_dir_name . 'views' . DIRECTORY_SEPARATOR . '2faconfigurations' . DIRECTORY_SEPARATOR . 'dashboard.php';
