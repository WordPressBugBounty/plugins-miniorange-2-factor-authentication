<?php
/**
 * This file is controller for twofactor/quicksetup/views/2faconfigurations/notifications.php.
 *
 * @package miniorange-2-factor-authentication/controllers/2faconfigurations
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
global $mo2f_dir_name;
$mo2f_enable_debug_log           = get_site_option( 'mo2f_enable_debug_log' );
$mo2f_enable_2fa_change_alerts   = get_site_option( 'mo2f_enable_2fa_change_alerts', 1 );
$mo2f_enable_2fa_reminder_emails = get_site_option( 'mo2f_enable_2fa_reminder_emails' );
$mo2f_enable_digest_email        = get_site_option( 'mo2f_enable_digest_email' );
$mo2f_digest_email_frequency     = get_site_option( 'mo2f_digest_email_frequency', 'weekly' );
$mo2f_digest_email_recipients    = get_site_option( 'mo2f_digest_email_recipients', '' );

require $mo2f_dir_name . 'views' . DIRECTORY_SEPARATOR . '2faconfigurations' . DIRECTORY_SEPARATOR . 'notifications.php';
