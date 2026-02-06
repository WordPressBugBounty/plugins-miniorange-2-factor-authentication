<?php
/**
 * This includes files according to the switch case.
 *
 * @package miniOrange-2-factor-authentication/controllers
 */

// Needed in both.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use TwoFA\Objects\Mo2f_TabDetails;

global $mo2f_mo_wpns_utility, $mo2f_dir_name;

$mo2f_controller = $mo2f_dir_name . 'controllers' . DIRECTORY_SEPARATOR;

require_once $mo2f_controller . 'navbar.php';

$mo2f_tab_details = Mo2f_TabDetails::instance();
require_once $mo2f_controller . 'main-menu.php';
$mo2f_current_page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; //phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading GET parameter from the URL for checking the tab name, doesn't require nonce verification.
?><div id="mo2f_2fa_popup_dashboard" class="modal" hidden></div>
<?php
require_once $mo2f_controller . DIRECTORY_SEPARATOR . 'two-factor-page.php';
if ( current_user_can( 'manage_options' ) ) {
	require $mo2f_controller . 'contactus.php';
}


