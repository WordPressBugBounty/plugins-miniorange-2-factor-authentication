<?php
/**
 * File contains super global variables.
 *
 * @package miniOrange-2-factor-authentication/database
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$mo2f_email_logo_path = dirname( __DIR__ ) . '/includes/images/xecurify-logo.png';
$mo2f_email_logo_src  = '';

if ( file_exists( $mo2f_email_logo_path ) ) {
	$mo2f_email_logo_contents = file_get_contents( $mo2f_email_logo_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Intentional read for inline email asset.
	if ( false !== $mo2f_email_logo_contents ) {
		$mo2f_email_logo_src = esc_attr( 'data:image/png;base64,' . base64_encode( $mo2f_email_logo_contents ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Encode local logo for inline email embedding.
	}
}

if ( '' === $mo2f_email_logo_src ) {
	$mo2f_email_logo_src = esc_url( plugin_dir_url( dirname( __DIR__ ) . '/miniorange_2_factor_settings.php' ) . 'includes/images/xecurify-logo.png' );
}

$mo2f_email_logo_img_tag = '<img src="' . $mo2f_email_logo_src . '" alt="Xecurify" style="color:#5fb336;text-decoration:none;display:block;width:auto;height:auto;max-height:35px">';

$GLOBALS['mo2f_enable_brute_force']                              = false;
$GLOBALS['mo2f_show_remaining_attempts']                         = false;
$GLOBALS['mo2f_mo_wpns_enable_ip_blocked_email_to_admin']        = false;
$GLOBALS['mo2f_activate_plugin']                                 = 0;
$GLOBALS['mo2f_login_option']                                    = 1;
$GLOBALS['mo2f_number_of_transactions']                          = 1;
$GLOBALS['mo2f_set_transactions']                                = 0;
$GLOBALS['mo2f_enable_forgotphone']                              = 0;
$GLOBALS['mo2f_enable_2fa_for_users']                            = 1;
$GLOBALS['mo2f_enable_xmlrpc']                                   = 0;
$GLOBALS['mo2f_custom_plugin_name']                              = 'miniOrange 2-Factor';
$GLOBALS['mo2f_show_sms_transaction_message']                    = 0;
$GLOBALS['mo2f_enforce_strong_passswords_for_accounts']          = 'all';
$GLOBALS['mo2f_mo_wpns_scan_initialize']                         = 1;
$GLOBALS['mo2f_mo_wpns_2fa_with_network_security']               = 0;
$GLOBALS['mo2f_mo_wpns_2fa_with_network_security_popup_visible'] = 1;
$GLOBALS['mo2f_two_factor_tour']                                 = -1;
$GLOBALS['mo2f_planname']                                        = '';
$GLOBALS['mo2f_cmVtYWluaW5nT1RQ']                                = 30;
$GLOBALS['mo2f_bGltaXRSZWFjaGVk']                                = 0;
$GLOBALS['mo2f_is_NC']                             = 1;
$GLOBALS['mo2f_is_NNC']                            = 1;
$GLOBALS['mo2f_enforce_strong_passswords']         = false;
$GLOBALS['mo2f_enable_debug_log']                  = 0;
$GLOBALS['mo2f_grace_period']                      = null;
$GLOBALS['mo2f_grace_period_type']                 = 'hours';
$GLOBALS['mo2f_enable_email_change']               = 0;
$GLOBALS['mo2f_remember_device']                   = '1';
$GLOBALS['mo2f_enable_login_popup_customization']  = '1';
$GLOBALS['mo2f_show_loginwith_phone']              = '1';
$GLOBALS['mo2f_action_rba_limit_exceed']           = '1';
$GLOBALS['mo2f_sesssion_restriction']              = '1';
$GLOBALS['mo2f_session_logout_time_enable']        = '1';
$GLOBALS['mo2f_login_option']                      = '0';
$GLOBALS['mo2f_email_ver_subject']                 = '2FA - Email Verification Via Link';
$GLOBALS['mo2f_email_subject']                     = '2FA - Email Verification Via OTP';
$GLOBALS['mo2f_2fa_reconfig_email_subject']        = '2FA - Account Recovery Link';
$GLOBALS['mo2f_2fa_backup_code_email_subject']     = '2FA - Backup Codes';
$GLOBALS['mo2f_2fa_new_ip_detected_email_subject'] = '2FA - User Logged In Using New IP';
$GLOBALS['mo2f_otp_over_email_template']           = '<table cellpadding="25" style="margin:0px auto">
<tbody>
<tr>
<td>
<table cellpadding="24" width="584px" style="margin:0 auto;max-width:584px;background-color:#f6f4f4;">
<tbody>
<tr>
<td>' . $mo2f_email_logo_img_tag . '</td>
</tr>
</tbody>
</table>
<table cellpadding="24" style="background:#fff;border:1px solid #a8adad;width:584px;border-top:none;color:#4d4b48;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:18px">
<tbody>
<tr>
<td>
<p style="margin-top:0;margin-bottom:20px">Dear Customer,</p>
<p style="margin-top:0;margin-bottom:10px">You initiated a transaction <b>WordPress 2 Factor Authentication Plugin</b>:</p>
<p style="margin-top:0;margin-bottom:10px">Your one time passcode is ##otp_token##.
<p style="margin-top:0;margin-bottom:15px">Thank you,<br>miniOrange Team</p>
<p style="margin-top:0;margin-bottom:0px;font-size:11px;color:red">Disclaimer: This email and any files transmitted with it are confidential and intended solely for the use of the individual or entity to whom they are addressed.</p>
</div></div></td>
</tr>
</tbody>
</table>
</td>
</tr>
</tbody>
</table>';
$GLOBALS['mo2f_reconfig_link_email_template']      = '
<table cellpadding="25" style="margin:0px auto">
<tbody>
<tr>
<td>
<table cellpadding="24" width="584px" style="margin:0 auto;max-width:584px;background-color:#f6f4f4;border:1px solid #a8adad">
<tbody>
<tr>
<td>' . $mo2f_email_logo_img_tag . '</td>
</tr>
</tbody>
</table>
<table cellpadding="24" style="background:#fff;border:1px solid #a8adad;width:584px;border-top:none;color:#4d4b48;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:18px">
<tbody>
<tr>
<td>
<input type="hidden" name="user_id" id="user_id" value="##user_id##">
<input type="hidden" name="email" id="email" value="##user_email##">
<p style="margin-top:0;margin-bottom:20px">Dear ' . '##user_name##,</p>
<p style="margin-top:0;margin-bottom:10px">Please click on the below link to recover your account:</p>
<p><a href="##url##" >Click to recover your account</a></p>
<p style="margin-top:0;margin-bottom:15px">Thank you,<br> miniOrange Team</p>
<p style="margin-top:0;margin-bottom:0px;font-size:11px;color:red">Disclaimer: This email and any files transmitted with it are confidential and intended solely for the use of the individual or entity to whom they are addressed.</p>
</div></div></td>
</tr>
</tbody>
</table>
</td>
</tr>
</tbody>
</table>';
$GLOBALS['mo2f_out_of_band_email_template']        = '<table cellpadding="25" style="margin:0px auto">
<tbody>
<tr>
<td>
<table cellpadding="24" width="584px" style="margin:0 auto;max-width:584px;background-color:#f6f4f4;border:1px solid #a8adad">
<tbody>
<tr>
<td>' . $mo2f_email_logo_img_tag . '</td>
</tr>
</tbody>
</table>
<table cellpadding="24" style="background:#fff;border:1px solid #a8adad;width:584px;border-top:none;color:#4d4b48;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:18px">
<tbody>
<tr>
<td>
<p style="margin-top:0;margin-bottom:20px">Dear Customer,</p>
<p style="margin-top:0;margin-bottom:10px">You initiated a transaction <b>WordPress 2 Factor Authentication Plugin</b>:</p>
<p style="margin-top:0;margin-bottom:10px">To accept, <a href="##url##userID=##user_id##&amp;accessToken=##accept_token##&amp;secondFactorAuthType=OUT+OF+BAND+EMAIL&amp;Txid=##txid##&amp;user=##email##" target="_blank">Accept Transaction</a></p>
<p style="margin-top:0;margin-bottom:10px">To deny, <a href="##url##userID=##user_id##&amp;accessToken=##denie_token##&amp;secondFactorAuthType=OUT+OF+BAND+EMAIL&amp;Txid=##txid##&amp;user=##email##" target="_blank">Deny Transaction</a></p><div><div class="adm"><div id="q_31" class="ajR h4" data-tooltip="Hide expanded content" aria-label="Hide expanded content" aria-expanded="true"><div class="ajT"></div></div></div><div class="im">
<p style="margin-top:0;margin-bottom:15px">Thank you,<br>miniOrange Team</p>
<p style="margin-top:0;margin-bottom:0px;font-size:11px;color:red">Disclaimer: This email and any files transmitted with it are confidential and intended solely for the use of the individual or entity to whom they are addressed.</p>
</div></div></td>
</tr>
</tbody>
</table>
</td>
</tr>
</tbody>
</table>';
$GLOBALS['mo2f_backup_code_email_template']        = '<table cellpadding="25" style="margin:0px auto">
<tbody>
<tr>
<td>
<table cellpadding="24" width="584px" style="margin:0 auto;max-width:584px;background-color:#f6f4f4;border:1px solid #a8adad">
<tbody>
<tr>
<td>' . $mo2f_email_logo_img_tag . '</td>
</tr>
</tbody>
</table>
<table cellpadding="24" style="background:#fff;border:1px solid #a8adad;width:584px;border-top:none;color:#4d4b48;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:18px">
<tbody>
<tr>
<td>
<p style="margin-top:0;margin-bottom:20px">Dear Customer,</p>
<p style="margin-top:0;margin-bottom:10px">You initiated a transaction from <b>WordPress 2 Factor Authentication Plugin</b>:</p>
<p style="margin-top:0;margin-bottom:10px">Your backup codes are:-
<table cellspacing="10">
	<tr><td> ##code1## </td><td> ##code2## </td><td> ##code3## </td><td> ##code4## </td><td> ##code5## </td>
</table></p>
<p style="margin-top:0;margin-bottom:10px">Please use this carefully as each code can only be used once. Please do not share these codes with anyone.</p>
<p style="margin-top:0;margin-bottom:10px">Also, we would highly recommend you to reconfigure your two-factor after logging in.</p>
<p style="margin-top:0;margin-bottom:15px">Thank you,<br>miniOrange Team</p>
<p style="margin-top:0;margin-bottom:0px;font-size:11px;color:red">Disclaimer: This email and any files transmitted with it are confidential and intended solely for the use of the individual or entity to whom they are addressed.</p>
</div></div></td>
</tr>
</tbody>
</table>
</td>
</tr>
</tbody>
</table>';
$GLOBALS['mo2f_new_ip_detected_email_template']    = '<table cellpadding="25" style="margin:0px auto">
<tbody>
<tr>
<td>
<table cellpadding="24" width="584px" style="margin:0 auto;max-width:584px;background-color:#f6f4f4;border:1px solid #a8adad">
<tbody>
<tr>
<td>' . $mo2f_email_logo_img_tag . '</td>
</tr>
</tbody>
</table>
<table cellpadding="24" style="background:#fff;border:1px solid #a8adad;width:584px;border-top:none;color:#4d4b48;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:18px">
<tbody>
<tr>
<td>
<p style="margin-top:0;margin-bottom:20px">Dear Customer,</p>
<p style="margin-top:0;margin-bottom:10px">Your account was logged in from new IP Address ##ipaddress## on website <b>' . get_bloginfo() . '.</b></p>
<p style="margin-top:0;margin-bottom:10px">Please <a href="mailto:info@xecurify.com">contact us</a> if you don\'t recognize this activity.</p>
<p style="margin-top:0;margin-bottom:15px">Thank you,<br>miniOrange Team</p>
<p style="margin-top:0;margin-bottom:0px;font-size:11px;color:red">Disclaimer: This email and any files transmitted with it are confidential and intended solely for the use of the individual or entity to whom they are addressed.</p>
</div></div></td>
</tr>
</tbody>
</table>
</td>
</tr>
</tbody>
</table>';
$GLOBALS['mo2f_default_kba_question_set']          = array(
	'What is your first company name?',
	'What was your childhood nickname?',
	'In what city did you meet your spouse/significant other?',
	'What is the name of your favorite childhood friend?',
	'What school did you attend for sixth grade?',
	'In what city or town was your first job?',
	'What is your favourite sport?',
	'Who is your favourite sports player?',
	'What is your grandmother\'s maiden name?',
	'What was your first vehicle\'s registration number?',
);
