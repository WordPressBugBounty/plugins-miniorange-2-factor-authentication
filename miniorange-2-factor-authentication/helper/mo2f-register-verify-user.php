<?php
/**
 * Description: File contains functions to register, verify and save the information for customer account.
 *
 * @package miniorange-2-factor-authentication/twofactor/myaccount/helper.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use TwoFA\Helper\MocURL;
use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Helper\MoWpnsUtility;
use TwoFA\Helper\MoWpnsMessages;
use TwoFA\Onprem\Two_Factor_Setup_Onprem_Cloud;

/**
 * Description: Save all required fields on customer registration/retrieval complete.
 *
 * @param string $email Customer Email.
 * @param int    $id Customer Id.
 * @param string $api_key Customer apikey.
 * @param string $token Customer token key.
 * @param string $app_secret Customer appSecret.
 * @return void
 */
function mo2fa_save_success_customer_config( $email, $id, $api_key, $token, $app_secret ) {
	global $mo2fdb_queries, $mo2f_onprem_cloud_obj;

	$user = wp_get_current_user();
	update_option( 'mo2f_customerKey', $id );
	update_option( 'mo2f_api_key', $api_key );
	update_option( 'mo2f_customer_token', $token );
	update_option( 'mo2f_app_secret', $app_secret );
	update_option( 'mo_wpns_enable_log_requests', true );
	update_option( 'mo2f_miniorange_admin', $user->ID );
	update_site_option( 'mo_2factor_admin_registration_status', 'MO_2_FACTOR_CUSTOMER_REGISTERED_SUCCESS' );

	$mo2fdb_queries->mo2f_update_user_details(
		$user->ID,
		array(
			'mo2f_user_email'                   => $email,
			'user_registration_with_miniorange' => 'SUCCESS',
		)
	);
	$enduser  = new MocURL();
	$userinfo = json_decode( $enduser->mo2f_get_userinfo( $email ), true );

	$mo2f_second_factor = 'NONE';
	if ( json_last_error() === JSON_ERROR_NONE ) {
		if ( 'SUCCESS' === $userinfo['status'] ) {
			$mo2f_second_factor = mo2f_update_and_sync_user_two_factor( $user->ID, $userinfo );
		}
	}
	if ( 'NONE' !== $mo2f_second_factor ) {
		if ( in_array(
			$mo2f_second_factor,
			array(
				MoWpnsConstants::OUT_OF_BAND_EMAIL,
				MoWpnsConstants::AUTHY_AUTHENTICATOR,
				MoWpnsConstants::OTP_OVER_SMS,
				MoWpnsConstants::OTP_OVER_EMAIL,
			),
			true
		) ) {
			$enduser->mo2f_update_user_info( $email, 'NONE', null, '', true );
		}
	}

	delete_user_meta( $user->ID, 'register_account' );

	$mo2f_customer_selected_plan = get_option( 'mo2f_customer_selected_plan' );
	if ( ! empty( $mo2f_customer_selected_plan ) ) {
		delete_option( 'mo2f_customer_selected_plan' );

		if ( MoWpnsUtility::get_mo2f_db_option( 'mo2f_planname', 'site_option' ) === 'addon_plan' ) {
			?><script>window.location.href="admin.php?page=mo_2fa_addons";</script>
			<?php
		} else {
			?>
				<script>window.location.href="admin.php?page=mo_2fa_upgrade";</script>
				<?php
		}
	} elseif ( 'NONE' === $mo2f_second_factor ) {
		if ( get_user_meta( $user->ID, 'register_account_popup', true ) ) {
			update_user_meta( $user->ID, 'mo2f_configure_2FA', 1 );
		}
	}
	delete_user_meta( $user->ID, 'register_account_popup' );
	delete_option( 'mo_wpns_verify_customer' );
	delete_option( 'mo_wpns_registration_status' );
	delete_option( 'mo_wpns_password' );
}

/**
 * Description: Function to fetch current user
 *
 * @param string $email Email of the user.
 * @param string $password Password of the user.
 * @return void
 */
function mo2fa_get_current_customer( $email, $password ) {
	$customer     = new MocURL();
	$content      = $customer->get_customer_key( $email, $password );
	$customer_key = json_decode( $content, true );
	$show_message = new MoWpnsMessages();
	if ( json_last_error() === JSON_ERROR_NONE ) {
		if ( 'SUCCESS' === $customer_key['status'] ) {
			if ( isset( $customer_key['phone'] ) ) {
				update_option( 'mo_wpns_admin_phone', $customer_key['phone'] );
			}
			update_option( 'mo2f_email', $email );
			$id         = isset( $customer_key['id'] ) ? $customer_key['id'] : '';
			$api_key    = isset( $customer_key['apiKey'] ) ? $customer_key['apiKey'] : '';
			$token      = isset( $customer_key['token'] ) ? $customer_key['token'] : '';
			$app_secret = isset( $customer_key['appSecret'] ) ? $customer_key['appSecret'] : '';
			mo2fa_save_success_customer_config( $email, $id, $api_key, $token, $app_secret );
			update_site_option( base64_encode( 'totalUsersCloud' ), get_site_option( base64_encode( 'totalUsersCloud' ) ) + 1 ); //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- We need to obfuscate the option as it will be stored in database.
			$customer_t = new Two_Factor_Setup_Onprem_Cloud();
			$content    = json_decode( $customer_t->get_customer_transactions( get_option( 'mo2f_customerKey' ), get_option( 'mo2f_api_key' ), 'PREMIUM' ), true );
			if ( 'SUCCESS' === $content['status'] ) {
				update_site_option( 'mo2f_license_type', 'PREMIUM' );
			} else {
				update_site_option( 'mo2f_license_type', 'DEMO' );
				$content = json_decode( $customer_t->get_customer_transactions( get_option( 'mo2f_customerKey' ), get_option( 'mo2f_api_key' ), 'DEMO' ), true );
			}
			if ( isset( $content['smsRemaining'] ) ) {
				update_site_option( 'cmVtYWluaW5nT1RQVHJhbnNhY3Rpb25z', $content['smsRemaining'] );
			} elseif ( isset( $content['status'] ) && 'SUCCESS' === $content['status'] ) {
				update_site_option( 'cmVtYWluaW5nT1RQVHJhbnNhY3Rpb25z', 0 );
			}

			if ( isset( $content['emailRemaining'] ) ) {
				if ( MO2F_IS_ONPREM ) {
					if ( ! get_site_option( 'cmVtYWluaW5nT1RQ' ) ) {
						update_site_option( 'cmVtYWluaW5nT1RQ', 30 );
					}
				} else {
					update_site_option( 'cmVtYWluaW5nT1RQ', $content['emailRemaining'] );
				}
			}
			$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::REG_SUCCESS ), 'SUCCESS' );
			return;
		} else {
			update_option( 'mo_2factor_admin_registration_status', 'MO_2_FACTOR_VERIFY_CUSTOMER' );
			update_option( 'mo_wpns_verify_customer', 'true' );
			delete_option( 'mo_wpns_new_registration' );
			$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::ACCOUNT_EXISTS ), 'ERROR' );
			return;
		}
	} else {
		$mo2f_message = is_string( $content ) ? $content : '';
		$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( $mo2f_message ), 'ERROR' );
	}
}
/**
 * It will update and sync the two factor settings
 *
 * @param string $user_id It will carry the user id .
 * @param object $userinfo It will carry the user info .
 * @return string
 */
function mo2f_update_and_sync_user_two_factor( $user_id, $userinfo ) {
	global $mo2fdb_queries;
	$mo2f_second_factor = isset( $userinfo['authType'] ) && ! empty( $userinfo['authType'] ) ? $userinfo['authType'] : 'NONE';
	if ( MO2F_IS_ONPREM ) {
		$mo2f_second_factor = $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_configured_2FA_method', $user_id );
		$mo2f_second_factor = $mo2f_second_factor ? $mo2f_second_factor : 'NONE';
		return $mo2f_second_factor;
	}

	$mo2fdb_queries->mo2f_update_user_details( $user_id, array( 'mo2f_user_email' => $userinfo['email'] ) );
	if ( MoWpnsConstants::OUT_OF_BAND_EMAIL === $mo2f_second_factor ) {
		$mo2fdb_queries->mo2f_update_user_details( $user_id, array( 'mo2f_EmailVerification_config_status' => true ) );
	} elseif ( MoWpnsConstants::OTP_OVER_SMS === $mo2f_second_factor && ! MO2F_IS_ONPREM ) {
		$phone_num = isset( $userinfo['phone'] ) ? sanitize_text_field( $userinfo['phone'] ) : '';
		$mo2fdb_queries->mo2f_update_user_details( $user_id, array( 'mo2f_OTPOverSMS_config_status' => true ) );
		$_SESSION['user_phone'] = $phone_num;
	} elseif ( MoWpnsConstants::SECURITY_QUESTIONS === $mo2f_second_factor ) {
		$mo2fdb_queries->mo2f_update_user_details( $user_id, array( 'mo2f_SecurityQuestions_config_status' => true ) );
	} elseif ( MoWpnsConstants::GOOGLE_AUTHENTICATOR === $mo2f_second_factor ) {
		$app_type = get_user_meta( $user_id, 'mo2f_external_app_type', true );
		if ( MoWpnsConstants::AUTHY_AUTHENTICATOR === $app_type ) {
			$mo2fdb_queries->mo2f_update_user_details(
				$user_id,
				array(
					'mo2f_AuthyAuthenticator_config_status' => true,
				)
			);
		} else {
			$mo2fdb_queries->mo2f_update_user_details(
				$user_id,
				array(
					'mo2f_GoogleAuthenticator_config_status' => true,
				)
			);

			update_user_meta( $user_id, 'mo2f_external_app_type', MoWpnsConstants::GOOGLE_AUTHENTICATOR );
		}
	}

	return $mo2f_second_factor;
}
