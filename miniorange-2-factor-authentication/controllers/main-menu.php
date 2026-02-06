<?php
/**
 * Description: This file is used to add subtabs in the menu.
 *
 * @package miniorange-2-factor-authentication/controllers.
 */

use TwoFA\Objects\Mo2f_TabDetails;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use TwoFA\Helper\MoWpnsUtility;
$mo2f_subtab                       = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ( current_user_can( 'manage_options' ) ? 'mo_2fa_two_fa' : 'mo_2fa_my_account' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended -- Reading GET parameter from the URL for checking the tab name, doesn't require nonce verification.
$mo2f_mo_2fa_with_network_security = MoWpnsUtility::get_mo2f_db_option( 'mo_wpns_2fa_with_network_security', 'site_option' );
$mo2f_tab_details                  = Mo2f_TabDetails::instance();
require $mo2f_dir_name . 'views' . DIRECTORY_SEPARATOR . 'main-menu.php';
