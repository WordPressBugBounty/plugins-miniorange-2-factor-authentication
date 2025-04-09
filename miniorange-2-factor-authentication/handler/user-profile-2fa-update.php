<?php
/**
 * User profile 2fa update file.
 *
 * @package miniOrange-2-factor-authentication/handler
 */

use TwoFA\Helper\MocURL;
use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Handler\Twofa\MO2f_Utility;
use TwoFA\Handler\Twofa\MO2f_Cloud_Onprem_Interface;
use TwoFA\Handler\Twofa\Miniorange_Authentication;
use TwoFA\Handler\Twofa\Miniorange_Password_2Factor_Login;
use TwoFA\Handler\TwofaMethods\Mo2f_KBA_Handler;
use TwoFA\Helper\MoWpnsMessages;
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
global $mo2fdb_queries;
$is_userprofile_enabled = isset( $_POST['mo2f_enable_userprofile_2fa'] ) ? sanitize_key( wp_unslash( $_POST['mo2f_enable_userprofile_2fa'] ) ) : false;
if ( ! $is_userprofile_enabled ) {
	return;
}

$nonce = isset( $_POST['mo2f-update-mobile-nonce'] ) ? sanitize_key( wp_unslash( $_POST['mo2f-update-mobile-nonce'] ) ) : '';

if ( ! wp_verify_nonce( $nonce, 'mo2f-update-mobile-nonce' ) || ! current_user_can( 'manage_options' ) ) {
	$mo2f_error = new WP_Error();
	$mo2f_error->add( 'empty_username', '<strong>' . __( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . __( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );
	return $mo2f_error;
} else {
	$method = isset( $_POST['method'] ) ? sanitize_text_field( wp_unslash( $_POST['method'] ) ) : null ;
	if ( is_null( $method ) || $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_configured_2FA_method', $user_id ) === $method  ) {
		return;
	}
	$email   = $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_user_email', $user_id );
	$email   = sanitize_email( $email );
	$enduser = new MocURL();
	if ( isset( $_POST['verify_phone'] ) ) {
		$phone = strlen( $_POST['verify_phone'] > 4 ) ? sanitize_text_field( wp_unslash( $_POST['verify_phone'] ) ) : null;
	} else {
		$phone = null;
	}
	$pass2flogin = new Miniorange_Password_2Factor_Login();
	$currentuser = get_user_by( 'id', $user_id );
	if ( ! MO2F_IS_ONPREM ) {
		$mocurl   = new MocURL();
		$content  = json_decode( $mocurl->mo_create_user( $currentuser, $email ), true );
		$response = json_decode( $mocurl->mo2f_update_user_info( $email, $method, $phone, null, null ), true );
		if ( 'SUCCESS' !== $response['status'] ) {
			return;
		}
	}
	$userid    = get_current_user_id();
	$tfastatus = ( $userid === $user_id ) ? MoWpnsConstants::MO_2_FACTOR_PLUGIN_SETTINGS : MoWpnsConstants::MO_2_FACTOR_INITIALIZE_TWO_FACTOR;
	switch ( $method ) {
		case MoWpnsConstants::GOOGLE_AUTHENTICATOR:
			if ( $userid === $user_id ) {
				if ( isset( $_POST['mo2f_configuration_status'] ) && sanitize_text_field( wp_unslash( $_POST['mo2f_configuration_status'] ) ) === 'SUCCESS' ) {
					$content = array( 'status' => 'SUCCESS' );
				} else{
					$content = array( 'status' => 'ERROR' );
				}
			} else {
				$content = mo2f_send_twofa_setup_link_on_email( $email, $user_id, $method );
			}
			if ( 'SUCCESS' === $content['status'] ) {
				$pass2flogin->mo2fa_update_user_details( $user_id, true, $method, MoWpnsConstants::SUCCESS_RESPONSE, $tfastatus, 1, $email );
				if ( ! MO2F_IS_ONPREM ) {
					update_user_meta( $user_id, 'mo2f_external_app_type', MoWpnsConstants::GOOGLE_AUTHENTICATOR );
				}
			} else {
				update_user_meta( $user_id, 'mo2f_userprofile_error_message', MoWpnsMessages::lang_translate( MoWpnsMessages::USER_PROFILE_SETUP_SMTP ) );
			}
			break;
		case MoWpnsConstants::OTP_OVER_SMS:
			if ( ! get_site_option( 'mo2f_customerKey' ) ) {
				update_user_meta( $user_id, 'mo2f_userprofile_error_message', MoWpnsMessages::lang_translate( 'Please register with miniOrange to set the OTP Over SMS method.' ) );
				return;
			}
			if ( $userid === $user_id ) {
				$pass2flogin->mo2fa_update_user_details( $user_id, true, $method, MoWpnsConstants::SUCCESS_RESPONSE, MoWpnsConstants::MO_2_FACTOR_PLUGIN_SETTINGS, 1, $email );
			} else {
				$content = mo2f_send_twofa_setup_link_on_email( $email, $user_id, $method );
				if ( 'SUCCESS' === $content['status'] ) {
					$pass2flogin->mo2fa_update_user_details( $user_id, true, $method, MoWpnsConstants::SUCCESS_RESPONSE, MoWpnsConstants::MO_2_FACTOR_PLUGIN_SETTINGS, 1, $email );
				} else {
					update_user_meta( $user_id, 'mo2f_userprofile_error_message', MoWpnsMessages::lang_translate( MoWpnsMessages::USER_PROFILE_SETUP_SMTP ) );
				}
			}
			break;
		case MoWpnsConstants::OTP_OVER_WHATSAPP:
			if ( ! get_site_option( 'mo2f_customerKey' ) ) {
				update_user_meta( $user_id, 'mo2f_userprofile_error_message', MoWpnsMessages::lang_translate( 'Please register with miniOrange to set the OTP Over WhatsApp method.' ) );
				return;
			}
			if ( $userid === $user_id ) {
				$pass2flogin->mo2fa_update_user_details( $user_id, true, $method, MoWpnsConstants::SUCCESS_RESPONSE, MoWpnsConstants::MO_2_FACTOR_PLUGIN_SETTINGS, 1, $email );
			} else {
				$content = mo2f_send_twofa_setup_link_on_email( $email, $user_id, $method );
				if ( 'SUCCESS' === $content['status'] ) {
					$pass2flogin->mo2fa_update_user_details( $user_id, true, $method, MoWpnsConstants::SUCCESS_RESPONSE, MoWpnsConstants::MO_2_FACTOR_PLUGIN_SETTINGS, 1, $email );
				} else {
					update_user_meta( $user_id, 'mo2f_userprofile_error_message', MoWpnsMessages::lang_translate( MoWpnsMessages::USER_PROFILE_SETUP_SMTP ) );
				}
			}
			break;
		case MoWpnsConstants::SECURITY_QUESTIONS:
			if ( $userid === $user_id ) {
				$obj              = new Miniorange_Authentication();
				$kba_ques_ans_obj = new Mo2f_KBA_Handler();
				$kba_ques_ans     = $kba_ques_ans_obj->mo2f_get_ques_ans( $_POST );
				$show_message     = new MoWpnsMessages();
				foreach ( $kba_ques_ans as $key => $value ) {
					if ( MO2f_Utility::mo2f_check_empty_or_null( $value ) ) {
						$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::INVALID_ENTRY ), 'ERROR' );
						return;
					}
				}
				$questions        = array_keys( $kba_ques_ans );
				$unique_questions = array_unique( array_map( 'strtolower', $kba_ques_ans ) );
				if ( count( $questions ) !== count( $unique_questions ) ) {
					$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::UNIQUE_QUESTION ), 'ERROR' );
					return;
				}
				$kba_registration = new MO2f_Cloud_Onprem_Interface();
				$content          = json_decode( $kba_registration->mo2f_register_kba_details( $email, $kba_ques_ans, $user_id ), true );
			} else {
				$content = mo2f_send_twofa_setup_link_on_email( $email, $user_id, $method );
			}
			if ( json_last_error() === JSON_ERROR_NONE ) {
				if ( 'SUCCESS' === $content['status'] ) {
					$pass2flogin->mo2fa_update_user_details( $user_id, true, $method, MoWpnsConstants::SUCCESS_RESPONSE, $tfastatus, 1, $email );
				} else {
					update_user_meta( $user_id, 'mo2f_userprofile_error_message', MoWpnsMessages::lang_translate( MoWpnsMessages::USER_PROFILE_SETUP_SMTP ) );
				}
			}
			break;
		case MoWpnsConstants::OTP_OVER_EMAIL:
			$content = mo2f_send_twofa_setup_link_on_email( $email, $user_id, $method );
			if ( 'SUCCESS' === $content['status'] ) {
				$pass2flogin->mo2fa_update_user_details( $user_id, true, $method, MoWpnsConstants::SUCCESS_RESPONSE, MoWpnsConstants::MO_2_FACTOR_PLUGIN_SETTINGS, 1, $email );
				delete_user_meta( $user_id, 'mo2f_configure_2FA' );
				delete_user_meta( $user_id, 'test_2FA' );
			} else {
				update_user_meta( $user_id, 'mo2f_userprofile_error_message', MoWpnsMessages::lang_translate( MoWpnsMessages::USER_PROFILE_SETUP_SMTP ) );
			}
			break;
		case MoWpnsConstants::OUT_OF_BAND_EMAIL:
			$content = mo2f_send_twofa_setup_link_on_email( $email, $user_id, $method );
			if ( 'SUCCESS' === $content['status'] ) {
				$pass2flogin->mo2fa_update_user_details( $user_id, true, $method, MoWpnsConstants::SUCCESS_RESPONSE, MoWpnsConstants::MO_2_FACTOR_PLUGIN_SETTINGS, 1, $email );
			} else {
				update_user_meta( $user_id, 'mo2f_userprofile_error_message', MoWpnsMessages::lang_translate( MoWpnsMessages::USER_PROFILE_SETUP_SMTP ) );
			}
			break;
	}
}

/**
 * Sends the 2fa method reconfiguration link on user's email id.
 *
 * @param string  $email User's email id.
 * @param integer $user_id User id of the user.
 * @param string  $tfa_method Name of the 2fa method.
 * @return mixed
 */
function mo2f_send_twofa_setup_link_on_email( $email, $user_id, $tfa_method ) {
	global $mo2fdb_queries, $image_path;
	$method_description    = array(
		MoWpnsConstants::GOOGLE_AUTHENTICATOR => 'configure the 2nd factor',
		MoWpnsConstants::SECURITY_QUESTIONS   => 'configure the 2nd factor',
		MoWpnsConstants::OTP_OVER_SMS         => 'Login to the site',
		MoWpnsConstants::OTP_OVER_WHATSAPP    => 'Login to the site',
		MoWpnsConstants::OTP_OVER_EMAIL       => 'Login to the site',
		MoWpnsConstants::OUT_OF_BAND_EMAIL    => 'Login to the site',
	);
	$method                = strval( $tfa_method );
	$reconfiguraion_method = hash( 'sha512', $method );
	update_site_option( $reconfiguraion_method, $method );
	$txid = bin2hex( openssl_random_pseudo_bytes( 32 ) );
	update_site_option( $txid, get_current_user_id() );
	update_user_meta( $user_id, 'mo2f_transactionId', $txid );
	$subject = '2FA-Configuration';
	$headers = array( 'Content-Type: text/html; charset=UTF-8' );
	$path    = plugins_url( DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'qr_over_email.php', dirname( __FILE__ ) ) . '?email=' . $email . '&amp;user_id=' . $user_id;
	$url     = get_site_option( 'siteurl' ) . '/wp-login.php?';
	$path    = $url . '&amp;reconfigureMethod=' . $reconfiguraion_method . '&amp;transactionId=' . $txid;
	$message = '
    <table>
    <tbody>
    <tr>
    <td>
    <table cellpadding="24" width="584px" style="margin:0 auto;max-width:584px;background-color:#f6f4f4;border:1px solid #a8adad">
    <tbody>
    <tr>
    <td><img src="' . $image_path . 'includes/images/xecurify-logo.png" alt="Xecurify" style="color:#5fb336;text-decoration:none;display:block;width:auto;height:auto;max-height:35px" class="CToWUd"></td>
    </tr>
    </tbody>
    </table>
    <table cellpadding="24" style="background:#fff;border:1px solid #a8adad;width:584px;border-top:none;color:#4d4b48;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:18px">
    <tbody>
    <tr>
    <td>
    <input type="hidden" name="user_id" id="user_id" value="' . esc_attr( $user_id ) . '">
    <input type="hidden" name="email" id="email" value="' . esc_attr( $email ) . '">
    <p style="margin-top:0;margin-bottom:20px">Dear ' . get_user_by( 'id', $user_id )->user_login . ',</p>
    <p style="margin-top:0;margin-bottom:10px">Your 2FA method (' . esc_attr( MoWpnsConstants::mo2f_convert_method_name( $tfa_method, 'cap_to_small' ) ) . ') has been set by site admin.</p>
    <p><a href="' . esc_url( $path ) . '" > Click to ' . $method_description[ $tfa_method ] . '</a></p>
    <p style="margin-top:0;margin-bottom:15px">Thank you,<br>miniOrange Team</p>
    <p style="margin-top:0;margin-bottom:0px;font-size:11px">Disclaimer: This email and any files transmitted with it are confidential and intended solely for the use of the individual or entity to whom they are addressed.</p>
    </div></div></td>
    </tr>
    </tbody>
    </table>
    </td>
    </tr>
    </tbody>
    </table>';
	$result  = wp_mail( $email, $subject, $message, $headers );
	if ( $result ) {
		$arr = array(
			'status'  => 'SUCCESS',
			'message' => 'Successfully validated.',
			'txid'    => '',
		);
		update_user_meta( $user_id, 'mo2f_user_profile_set', true );
		$mo2fdb_queries->mo2f_update_user_details(
			$user_id,
			array(
				'mo2f_AuthyAuthenticator_config_status'  => false,
				'mo2f_EmailVerification_config_status'   => false,
				'mo2f_SecurityQuestions_config_status'   => false,
				'mo2f_GoogleAuthenticator_config_status' => false,
				'mo2f_OTPOverTelegram_config_status'     => false,
				'mo2f_OTPOverSMS_config_status'          => false,
				'mo2f_OTPOverEmail_config_status'        => false,
			)
		);

	} else {
		$arr = array(
			'status'  => 'FAILED',
			'message' => 'TEST FAILED.',
		);
	}
	return $arr;
}

