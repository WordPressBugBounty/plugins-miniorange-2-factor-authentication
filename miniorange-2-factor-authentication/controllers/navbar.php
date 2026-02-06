<?php
/**
 * File updates network security options in the options table.
 *
 * @package miniorange-2-factor-authentication/controllers
 */

// Needed in both.
use TwoFA\Helper\MoWpnsUtility;
use TwoFA\Helper\MoWpnsHandler;
use TwoFA\Handler\Mo2fa_Security_Features;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$mo2f_nonce = isset( $_POST['mo_security_features_nonce'] ) ? sanitize_key( wp_unslash( $_POST['mo_security_features_nonce'] ) ) : '';
if ( ! wp_verify_nonce( $mo2f_nonce, 'mo_2fa_security_features_nonce' ) ) {
	$mo2f_error = new WP_Error();
	$mo2f_error->add( 'empty_username', '<strong>' . __( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . __( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );

} else {
	global $mo2f_mo_wpns_utility, $mo2f_dir_name;
	if ( current_user_can( 'manage_options' ) && isset( $_POST['option'] ) ) {
		switch ( sanitize_text_field( wp_unslash( $_POST['option'] ) ) ) {
			case 'mo_wpns_2fa_with_network_security':
				$mo2f_security_features = new Mo2fa_Security_Features();
				$mo2f_security_features->wpns_2fa_with_network_security( $_POST );
				break;
		}
	}
}
$mo2f_network_security_features = get_site_option( 'mo_wpns_2fa_with_network_security' ) ? 'checked' : '';
$mo2f_remaining_transaction     = $mo2f_mo_wpns_utility->mo2f_check_remaining_transactions();

if ( isset( $_GET['page'] ) ) {
	$mo2f_tab_count = get_site_option( 'mo2f_tab_count', 0 );
	switch ( sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) {

		case 'mo_2fa_advancedblocking':
			update_site_option( 'mo_2f_switch_adv_block', 1 );
			if ( $mo2f_tab_count < 5 && ! get_site_option( 'mo_2f_switch_adv_block' ) ) {
				update_site_option( 'mo2f_tab_count', get_site_option( 'mo2f_tab_count' ) + 1 );
			}
			break;


	}
}
	// Added for new design.
	$mo2f_request_offer_url = esc_url( add_query_arg( array( 'page' => 'mo_2fa_request_offer' ), ( isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '' ) ) );
	// dynamic.
	$mo2f_logo_url = plugin_dir_url( __DIR__ ) . 'includes/images/miniorange-new-logo.png';

	$mo2f_mo_plugin_handler      = new MoWpnsHandler();
	$mo2f_safe                   = $mo2f_mo_plugin_handler->mo2f_is_whitelisted( $mo2f_mo_wpns_utility->get_client_ip() );
	$mo2f_active_tab             = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
	$mo2f_user_id                = get_current_user_id();
	$mo2f_two_fa_method     = $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_configured_2FA_method', $mo2f_user_id );
	$mo2f_backup_codes_remaining = get_user_meta( $mo2f_user_id, 'mo2f_backup_codes', true );
if ( is_array( $mo2f_backup_codes_remaining ) ) {
	$mo2f_backup_codes_remaining = count( $mo2f_backup_codes_remaining );
} else {
	$mo2f_backup_codes_remaining = 0;
}
require $mo2f_dir_name . 'views' . DIRECTORY_SEPARATOR . 'navbar.php';
