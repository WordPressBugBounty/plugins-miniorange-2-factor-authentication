<?php
/**
 * This file is part of reconfigure link feature.
 *
 * @package miniOrange-2-factor-authentication/handler
 */

namespace TwoFA\Handler;

use TwoFA\Handler\Twofa\MO2f_Utility;
use TwoFA\Helper\Mo2f_Login_Popup;
use TwoFA\Helper\MoWpnsUtility;
use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Helper\Mo2f_Common_Helper;
use TwoFA\Traits\Instance;
use WP_REST_Request;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Mo2f_Reconfigure_Link' ) ) {
	/**
	 * Class Mo2f_Reconfigure_Link
	 */
	class Mo2f_Reconfigure_Link {

		use Instance;

		/**
		 * Class Mo2f_Reconfigure_Link constructor
		 */
		public function __construct() {
			add_action( 'rest_api_init', array( $this, 'mo2f_add_custom_users_api' ) );
			add_filter( 'login_message', array( $this, 'mo2f_reconfiguration_success_message' ) );
		}


		/**
		 * Shows inline selected method.
		 *
		 * @param array $post Post data.
		 * @return void
		 */
		public function mo2f_send_reconfig_link( $post ) {
			$session_id_encrypt = isset( $post['session_id'] ) ? sanitize_text_field( $post['session_id'] ) : null;
			$redirect_to        = isset( $post['redirect_to'] ) ? esc_url_raw( $post['redirect_to'] ) : null;
			$twofa_method       = isset( $post['mo2f_login_method'] ) ? sanitize_text_field( wp_unslash( $post['mo2f_login_method'] ) ) : null;
			$user_id            = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );
			$currentuser        = get_user_by( 'id', $user_id );
			$email_delivered    = $this->mo2f_send_reconfig_link_on_email( $currentuser, $currentuser->user_email );
			$mo2f_title         = $email_delivered ? 'Account Recovery Email Sent' : 'Account Recovery Email Counldn\'t Send';
			$mo2f_message       = $this->mo2f_get_message( $email_delivered, $user_id );
			$mo2fa_login_status = MoWpnsConstants::MO_2_FACTOR_RECONFIGURATION_LINK_SENT;
			$this->mo2f_show_login_prompt( $mo2f_message, $mo2fa_login_status, $currentuser, $redirect_to, $session_id_encrypt, $mo2f_title, $twofa_method );
		}

		/**
		 * Gets the message.
		 *
		 * @param bool $email_delivered Email delivery status.
		 * @param int  $user_id User id.
		 * @return string
		 */
		public function mo2f_get_message( $email_delivered, $user_id ) {
			if ( $email_delivered ) {
				return 'An email containing the link to recover your account by resettings your Two-Factor Authentication (2FA) details has been sent to your inbox. Please click on that link for the same.';
			} else {
				return 'Apologies, we\'re encountering an issue while attempting to send the reconfiguration email. Kindly verify your network ' . ( user_can( $user_id, 'administrator' ) ? 'or SMTP connection settings.' : 'and try again.' );
			}
		}

		/**
		 * Show login popup for email.
		 *
		 * @param string $mo2fa_login_message Login message.
		 * @param string $mo2fa_login_status Login status.
		 * @param object $current_user Current user.
		 * @param string $redirect_to Redirection url.
		 * @param string $session_id Session ID.
		 * @param string $mo2f_title Login title.
		 * @param string $twofa_method Twofa method.
		 * @return void
		 */
		public function mo2f_show_login_prompt( $mo2fa_login_message, $mo2fa_login_status, $current_user, $redirect_to, $session_id, $mo2f_title, $twofa_method ) {
			$login_popup     = new Mo2f_Login_Popup();
			$common_helper   = new Mo2f_Common_Helper();
			$skeleton_values = $login_popup->mo2f_twofa_login_prompt_skeleton_values( $mo2fa_login_message, $mo2fa_login_status, null, null, $current_user->ID, 'login_2fa', $mo2f_title );
			$html            = $login_popup->mo2f_twofa_authentication_login_prompt( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, $session_id, $skeleton_values, $twofa_method );
			$html           .= $common_helper->mo2f_get_hidden_forms_login( $redirect_to, $session_id, $mo2fa_login_status, $mo2fa_login_message, $twofa_method, $current_user->ID );
			$html           .= $common_helper->mo2f_get_hidden_script_login();
			echo $html;// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already escaped the necessary in the definition.
			exit;
		}
		/**
		 * Sends 2fa-reconfiguration link email.
		 *
		 * @param object $current_user Current user.
		 * @param string $email User's email address.
		 * @return bool
		 */
		public function mo2f_send_reconfig_link_on_email( $current_user, $email ) {
			$reset_token = $this->mo2f_generate_2fa_reconfiguration_token( $current_user );
			$subject     = MoWpnsUtility::get_mo2f_db_option( 'mo2f_2fa_reconfig_email_subject', 'site_option' );
			$headers     = array( 'Content-Type: text/html; charset=UTF-8' );
			$message     = $this->mo2f_2fa_reconfiguration_email_template( $reset_token, $current_user, $email );
			$result      = wp_mail( $email, $subject, $message, $headers );
			return $result;
		}

		/**
		 * Generates 2fa reconfiguration token.
		 *
		 * @param object $current_user Currnet user.
		 * @return string
		 */
		public function mo2f_generate_2fa_reconfiguration_token( $current_user ) {
			$username           = base64_encode( $current_user->user_login ); //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Obfuscation is neccessory here.
			$user_id            = $current_user->ID;
			$current_time_stamp = base64_encode( strtotime( current_datetime()->format( 'h:ia M d Y' ) ) ); //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Obfuscation is neccessory here.
			$txid               = bin2hex( openssl_random_pseudo_bytes( 32 ) );
			$reset_token        = $current_time_stamp . $username . $txid;
			update_user_meta( $user_id, 'mo2f_reset_token', $reset_token );
			return $reset_token;
		}

		/**
		 * Forms the 2fa-reconfiguration email template.
		 *
		 * @param string $reset_token Reset token.
		 * @param object $current_user Current user.
		 * @param string $email Email address.
		 * @return string
		 */
		public function mo2f_2fa_reconfiguration_email_template( $reset_token, $current_user, $email ) {
			global $image_path;
			$user_id = $current_user->ID;
			$image   = wp_upload_dir();
			$img_url = $image_path . 'includes/images/' . get_site_option( 'mo2f_custom_logo', 'miniOrange2.png' );
			$url     = get_site_option( 'siteurl' );
			$url    .= '/wp-json/miniorange/mo_2fa_two_fa/resetuser2fa=' . $reset_token . '/message=resetsuccess';
			$message = MoWpnsUtility::get_mo2f_db_option( 'mo2f_reconfig_link_email_template', 'site_option' );
			$message = str_replace( '##image_path##', $img_url, $message );
			$message = str_replace( '##user_id##', $user_id, $message );
			$message = str_replace( '##user_email##', $email, $message );
			$message = str_replace( '##user_name##', $current_user->user_login, $message );
			$message = str_replace( '##url##', $url, $message );
			return $message;
		}


		/**
		 * Registers custom API.
		 *
		 * @return void
		 */
		public function mo2f_add_custom_users_api() {
			register_rest_route(
				'miniorange/mo_2fa_two_fa',
				'/resetuser2fa=(?P<resetuser2fa>[A-Za-z0-9=+/]+)/message=(?P<message>[A-Za-z]+)',
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'mo2f_allow_users_2fa_reconfiguration' ),
					'permission_callback' => '__return_true',
				),
			);

		}


		/**
		 * Allow users to reconfigure their 2fa.
		 *
		 * @param WP_REST_Request $request Request data.
		 * @return void
		 */
		public function mo2f_allow_users_2fa_reconfiguration( WP_REST_Request $request ) {
			global $mo2fdb_queries;
			$reset_token   = $request['resetuser2fa'];
			$reset_success = $request['message'];
			$user_id       = $mo2fdb_queries->mo2f_get_userid_from_reset_token( $reset_token );
			if ( isset( $user_id ) ) {
				$current_user = get_user_by( 'id', $user_id );
				$username     = base64_encode( $current_user->user_login ); //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Obfuscation is neccessory here.
				if ( strpos( $reset_token, $username ) ) {
					$data          = explode( $username, $reset_token );
					$previous_time = base64_decode( $data[0] ); //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Unobfuscation is neccessory here.
					$current_time  = strtotime( current_datetime()->format( 'h:ia M d Y' ) );
					delete_user_meta( $user_id, 'mo2f_reset_token' );
					if ( $current_time < $previous_time + 24 * 60 * 60 ) {
						$this->mo2f_reset_users_2fa_for_reconfiguration( $user_id );
						wp_safe_redirect( get_site_url() . '/wp-login.php?reset_message=' . $reset_success );
						exit;
					}
				}
			}
			wp_safe_redirect( get_site_url() . '/wp-login.php?' );
			exit;
		}

		/**
		 * Resets user's 2FA.
		 *
		 * @param integer $user_id User id.
		 * @return void
		 */
		public function mo2f_reset_users_2fa_for_reconfiguration( $user_id ) {
			global $mo2fdb_queries;
			delete_user_meta( $user_id, 'mo2f_kba_challenge' );
			delete_user_meta( $user_id, 'mo2f_2FA_method_to_configure' );
			delete_user_meta( $user_id, 'Security Questions' );
			delete_user_meta( $user_id, 'mo2f_chat_id' );
			$mo2fdb_queries->delete_user_details( $user_id );
			delete_user_meta( $user_id, 'mo2f_2FA_method_to_test' );
		}

		/**
		 * Reconfiguration success message.
		 *
		 * @return string
		 */
		public function mo2f_reconfiguration_success_message() {

			if ( isset( $_GET['reset_message'] ) && 'resetsuccess' === sanitize_text_field( wp_unslash( $_GET['reset_message'] ) ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verification is not required here as user is logged out.
				$message = 'You have successfully reset your 2FA. Please login and reconfigure the 2FA method for yourself.';
				return "<div> <p class='message'>" . $message . '</p></div>';
			}

		}


	}new Mo2f_Reconfigure_Link();
}
