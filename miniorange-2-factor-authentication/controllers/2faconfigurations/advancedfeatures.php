<?php
/**
 * Description: This file is used to show the user details.
 *
 * @package miniorange-2-factor-authentication/controllers.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
$mo2f_configurations = array(
	'mo2f_remember_ip_feature'   => 0,
	'mo2f_give_rem_ip_choice'    => 1,
	'mo2f_enable_ip_list'        => 1,
	'mo2f_enable_ip_range'       => 0,
	'mo2f_2fa_whitelist_ip_list' => '',
	'mo2f_rem_ip_range'          => array( array() ),

);
$mo2f_configurations = (array) get_site_option( 'mo2f_remember_ip_configurations', $mo2f_configurations );
$mo2f_rem_ip_ranges  = isset( $mo2f_configurations['mo2f_rem_ip_range'] ) ? $mo2f_configurations['mo2f_rem_ip_range'] : array( array() );
$mo2f_enable_trusted_device = get_site_option( 'mo2f_enable_trusted_device' );
$mo2f_enable_captcha        = get_site_option( 'mo2f_enable_captcha' );
$mo2f_captcha_provider      = get_site_option( 'mo2f_captcha_provider', 'recaptcha' );
$mo2f_captcha_site_key      = get_site_option( 'mo2f_captcha_site_key' );
$mo2f_captcha_secret_key    = get_site_option( 'mo2f_captcha_secret_key' );
$mo2f_enable_xmlrpc         = get_site_option( 'mo2f_enable_xmlrpc' );
?>
<div class="mo2f-settings-div mo2f-section">
	<div class="mo2f-section-head">
		<h2><?php esc_html_e( 'Advanced', 'miniorange-2-factor-authentication' ); ?></h2>
		<p><?php esc_html_e( 'Reduce login friction with trusted devices and IPs, cap concurrent sessions, or drop the password entirely.', 'miniorange-2-factor-authentication' ); ?></p>
	</div>
	<div class="mo2f-advfeature-group">
	<?php require_once $mo2f_dir_name . 'views' . DIRECTORY_SEPARATOR . '2faconfigurations' . DIRECTORY_SEPARATOR . 'advancedfeatures' . DIRECTORY_SEPARATOR . 'hardening.php'; ?>
	</div>
	<div class="mo2f-advfeature-group">
	<?php require_once $mo2f_dir_name . 'views' . DIRECTORY_SEPARATOR . '2faconfigurations' . DIRECTORY_SEPARATOR . 'advancedfeatures' . DIRECTORY_SEPARATOR . 'rememberdevice.php'; ?>
	</div>
	<div class="mo2f-advfeature-group mo2f-advfeature-group--label-lg">
	<?php require_once $mo2f_dir_name . 'views' . DIRECTORY_SEPARATOR . '2faconfigurations' . DIRECTORY_SEPARATOR . 'advancedfeatures' . DIRECTORY_SEPARATOR . 'rememberip.php'; ?>
	</div>
	<div class="mo2f-advfeature-group mo2f-advfeature-group--label-sm">
	<?php require_once $mo2f_dir_name . 'views' . DIRECTORY_SEPARATOR . '2faconfigurations' . DIRECTORY_SEPARATOR . 'advancedfeatures' . DIRECTORY_SEPARATOR . 'sessionmanagement.php'; ?>
	</div>
	<div class="mo2f-advfeature-group mo2f-advfeature-group--label-md">
	<?php require_once $mo2f_dir_name . 'views' . DIRECTORY_SEPARATOR . '2faconfigurations' . DIRECTORY_SEPARATOR . 'advancedfeatures' . DIRECTORY_SEPARATOR . 'passwordlesslogin.php'; ?>
	</div>
</div>
<script>
jQuery("#mo_2fa_two_fa").addClass("side-nav-active");
</script>
<?php
	global $mo2f_main_dir;
	wp_enqueue_script( 'login-settings-script', $mo2f_main_dir . '/includes/js/login-settings.min.js', array(), MO2F_VERSION, false );
	wp_localize_script(
		'login-settings-script',
		'loginSettings',
		array(
			'nonce' => esc_js( wp_create_nonce( 'mo2f-login-settings-ajax-nonce' ) ),
		)
	);
?>
