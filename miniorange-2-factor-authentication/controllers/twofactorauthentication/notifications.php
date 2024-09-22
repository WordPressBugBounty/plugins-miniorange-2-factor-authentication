<?php
/**
 * Calls notifications view.
 *
 * @package miniorange-2-factor-authentication/notifications/controllers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
global $mo_wpns_utility;
$notify_admin_unusual_activity = '1' === get_site_option( 'mo_wpns_enable_unusual_activity_email_to_user' ) ? 'checked' : '';
$notify_new_release            = '1' === get_site_option( 'mo2f_mail_notify_new_release' ) ? 'checked' : '';
require dirname( dirname( dirname( __FILE__ ) ) ) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'twofactorauthentication' . DIRECTORY_SEPARATOR . 'notifications.php';

