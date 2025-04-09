<?php
/**
 * File contains functions to validate KBA, Google Authenticator code and to send and verify OTP over Email.
 *
 * @package miniOrange-2-factor-authentication/api
 */

namespace TwoFA\Onprem;

use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Helper\TwoFAMoSessions;
use TwoFA\Helper\MoWpnsUtility;
use TwoFA\Helper\MoWpnsMessages;
use TwoFA\Handler\Twofa\Google_Auth_Onpremise;
use TwoFA\Handler\Twofa\MO2f_Utility;
use TwoFA\Handler\Twofa\Miniorange_Authentication;

if ( ! class_exists( 'Mo2f_OnPremRedirect' ) ) {
	/**
	 * Class contains functions to validate several OnPremise methods like Google Authenticator and KBA and to send email to the users containing OTP.
	 */
	class Mo2f_OnPremRedirect {

		/**
		 * Function to redirect the login flow based on the authentication method.
		 *
		 * @param  string $auth_type    Authentication method of user.
		 * @param  int    $otp_token    Otp received by the user.
		 * @param  string $transaction_id    Transaction id.
		 * @param  object $current_user Contains information about the current user.
		 * @return array
		 */
		public function on_prem_validate_redirect( $auth_type, $otp_token, $transaction_id, $current_user = null ) {
			switch ( $auth_type ) {

				case MoWpnsConstants::GOOGLE_AUTHENTICATOR:
					$content = $this->mo2f_google_authenticator_onpremise( $otp_token, $current_user );
					return $content;
				case MoWpnsConstants::SECURITY_QUESTIONS:
					$content = $this->mo2f_kba_onpremise( $current_user );
					return $content;
				case MoWpnsConstants::OTP_OVER_EMAIL:
					return $this->mo2f_otp_email_verify( $otp_token, $transaction_id );

			}

		}

		/**
		 * Validates security questions.
		 *
		 * @param object $current_user Current user.
		 * @return array
		 */
		private function mo2f_kba_onpremise( $current_user ) {
			if ( ! check_ajax_referer( 'mo-two-factor-ajax-nonce', 'nonce', false ) ) {
				wp_send_json_error( 'class-mo2f-ajax' );
			}
			$user_id              = $current_user->ID;
			$kba_ans_1            = isset( $_POST['mo2f_answer_1'] ) ? sanitize_text_field( wp_unslash( $_POST['mo2f_answer_1'] ) ) : '';
			$kba_ans_2            = isset( $_POST['mo2f_answer_2'] ) ? sanitize_text_field( wp_unslash( $_POST['mo2f_answer_2'] ) ) : '';
			$questions_challenged = TwoFAMoSessions::get_session_var( 'mo_2_factor_kba_questions' );
			$all_ques_ans         = get_user_meta( $user_id, 'mo2f_kba_challenge' );
			$all_ques_ans         = $all_ques_ans[0];
			$ans_1                = $all_ques_ans[ $questions_challenged[0] ];
			$ans_2                = $all_ques_ans[ $questions_challenged[1] ];
			if ( ! strcmp( md5( strtolower( $kba_ans_1 ) ), $ans_1 ) && ! strcmp( md5( strtolower( $kba_ans_2 ) ), $ans_2 ) ) {
				$arr     = array(
					'status'  => 'SUCCESS',
					'message' => 'Successfully validated.',
				);
				$content = wp_json_encode( $arr );
				return $content;
			} else {
				$arr     = array(
					'status'  => 'FAILED',
					'message' => 'TEST FAILED.',
				);
				$content = wp_json_encode( $arr );
				return $content;
			}
		}
		/**
		 * Function to redirect login flow.
		 *
		 * @param  string $u_key User key.
		 * @param  string $auth_type   Authentication type of user.
		 * @param  string $currentuser Contains details of current user.
		 * @return array
		 */
		public function on_prem_send_redirect( $u_key, $auth_type, $currentuser ) {
			switch ( $auth_type ) {
				case MoWpnsConstants::OTP_OVER_EMAIL:
					$content = $this->on_prem_otp_over_email( $currentuser, $u_key );
					return $content;
				case MoWpnsConstants::SECURITY_QUESTIONS:
					$content = $this->on_prem_security_questions( $currentuser );
					return $content;

			}

		}

		/**
		 * Function to validate security questions.
		 *
		 * @param  object $user Contain details of current user.
		 * @return array
		 */
		private function on_prem_security_questions( $user ) {
			$question_answers    = get_user_meta( $user->ID, 'mo2f_kba_challenge' );
			$challenge_questions = array_keys( $question_answers[0] );
			$random_keys         = array_rand( $challenge_questions, 2 );
			$challenge_ques1     = array( 'question' => $challenge_questions[ $random_keys[0] ] );
			$challenge_ques2     = array( 'question' => $challenge_questions[ $random_keys[1] ] );
			$questions           = array( $challenge_ques1, $challenge_ques2 );
			update_user_meta( $user->ID, 'kba_questions_user', $questions );
			$response = wp_json_encode(
				array(
					'txId'      => wp_rand( 100, 10000000 ),
					'status'    => 'SUCCESS',
					'message'   => 'Please answer the following security questions.',
					'questions' => $questions,
				)
			);
			return $response;

		}
		/**
		 * Function to redirect login flow to verify code.
		 *
		 * @param  int    $otp_token    OTP token received by user.
		 * @param  object $current_user Details of current user.
		 * @return array
		 */
		private function mo2f_google_authenticator_onpremise( $otp_token, $current_user = null ) {
			include_once dirname( dirname( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'handler' . DIRECTORY_SEPARATOR . 'twofa' . DIRECTORY_SEPARATOR . 'class-google-auth-onpremise.php';
			$gauth_obj          = new Google_auth_onpremise();
			$session_id_encrypt = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : null; //phpcs:ignore WordPress.Security.NonceVerification.Missing -- Ignoring nonce verification warning as the flow is coming from multiple files.
			if ( is_user_logged_in() ) {
				$user    = wp_get_current_user();
				$user_id = $user->ID;
			} elseif ( isset( $current_user ) && ! empty( $current_user->ID ) ) {
				$user_id = $current_user->ID;
			} else {
				$user_id = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );
			}
			$secret  = $gauth_obj->mo_a_auth_get_secret( $user_id );
			$content = $gauth_obj->mo2f_verify_code( $secret, $otp_token );
			return $content;
		}
		/**
		 * Function to send otp.
		 *
		 * @param  object $current_user Details of current user.
		 * @param  string $useremail    Email id of user.
		 * @return array
		 */
		private function on_prem_otp_over_email( $current_user, $useremail ) {
			if ( ! $this->mo2f_check_if_email_transactions_exists() ) {
				return wp_json_encode(
					array(
						'status'  => 'ERROR',
						'message' => MoWpnsMessages::ERROR_IN_SENDING_OTP,
					)
				);
			};
			return $this->on_prem_send_otp_email( $current_user, $useremail );
		}

		/**
		 * Checks the email transactions.
		 *
		 * @return array
		 */
		public function mo2f_check_if_email_transactions_exists() {
			if ( ! apply_filters( 'mo2f_is_lv_needed', false ) && (int) get_site_option( 'cmVtYWluaW5nT1RQ', 0 ) <= 0 ) {
				return false;
			} else {
				return true;
			}

		}
		/**
		 * Function to send email to users.
		 *
		 * @param  object $current_user Details of the current user.
		 * @param  string $email        Email id of user.
		 * @return array
		 */
		private function on_prem_send_otp_email( $current_user, $email ) {
			global $image_path;
			$subject   = MoWpnsUtility::get_mo2f_db_option( 'mo2f_email_subject', 'site_option' );
			$headers   = array( 'Content-Type: text/html; charset=UTF-8' );
			$otp_token = '';
			for ( $i = 1;$i < 7;$i++ ) {
				$otp_token .= wp_rand( 0, 9 );
			}
			$transaction_id = MoWpnsUtility::rand();
			TwoFAMoSessions::add_session_var( 'mo2f_otp_email_code', $transaction_id . $otp_token ); // adding OTP token in session variable to store it in the otp verification on registration flow.
			TwoFAMoSessions::add_session_var( 'mo2f_otp_email_time', time() );
			TwoFAMoSessions::add_session_var( 'tempRegEmail', $email );
			$message = MoWpnsUtility::get_mo2f_db_option( 'mo2f_otp_over_email_template', 'site_option' );
			$message = str_replace( '##image_path##', $image_path, $message );
			$message = str_replace( '##otp_token##', $otp_token, $message );
			$result  = wp_mail( $email, $subject, $message, $headers );
			if ( $result ) {
				$cmvtywluaw5nt1rq = get_site_option( 'cmVtYWluaW5nT1RQ' );
				update_site_option( 'cmVtYWluaW5nT1RQ', $cmvtywluaw5nt1rq - 1 );
				if ( ! apply_filters( 'mo2f_is_lv_needed', false ) && 5 === (int) $cmvtywluaw5nt1rq ) {
					Miniorange_Authentication::mo2f_low_otp_alert( 'email' );
				}
				$arr = array(
					'status'  => 'SUCCESS',
					'message' => 'An OTP code has been sent to you on your email.',
					'txId'    => $transaction_id,
					'email'   => $email,
				);
			} else {
				$arr = array(
					'status'  => 'FAILED',
					'message' => 'TEST FAILED.',
				);
			}
			$content = wp_json_encode( $arr );
			return $content;

		}

		/**
		 * Function verifies otp received by user via email.
		 *
		 * @param  int    $otp_token    otp received by user.
		 * @param  string $transaction_id Transaction id.
		 * @return array
		 */
		private function mo2f_otp_email_verify( $otp_token, $transaction_id ) {
			global $mo2fdb_queries;
			if ( isset( $otp_token ) && ! empty( $otp_token ) ) {
				$valid_token   = TwoFAMoSessions::get_session_var( 'mo2f_otp_email_code' );
				$time          = TwoFAMoSessions::get_session_var( 'mo2f_otp_email_time' );
				$accepted_time = time() - 300;
				if ( $accepted_time > $time ) {
					$arr = array(
						'status'  => 'ERROR',
						'message' => 'The One time passcode has been expired. Please resend the code.',
					);
				} elseif ( strtolower( (string) ( $transaction_id . $otp_token ) ) === strtolower( (string) $valid_token ) ) {
					$arr = array(
						'status'  => 'SUCCESS',
						'message' => 'Successfully validated.',
					);
					TwoFAMoSessions::unset_session( 'mo2f_otp_email_code' );
					TwoFAMoSessions::unset_session( 'mo2f_otp_email_time' );
					TwoFAMoSessions::unset_session( 'tempRegEmail' );
				} else {
					$arr = array(
						'status'  => 'ERROR',
						'message' => MoWpnsMessages::INVALID_OTP,
					);
				}
				$content = wp_json_encode( $arr );
				return $content;

			}
		}

		/**
		 * Function to send email to the user for email verification method.
		 *
		 * @param object  $current_user Details of current user.
		 * @param string  $email Email.
		 * @param boolean $in_dashboard_flow Details of current user.
		 * @return array
		 */
		public function mo2f_pass2login_push_email_onpremise( $current_user, $email, $in_dashboard_flow = false ) {
			global $mo2fdb_queries;
			if ( empty( $email ) ) {
				$email = $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_user_email', $current_user->ID );
			}
			$subject     = MoWpnsUtility::get_mo2f_db_option( 'mo2f_email_ver_subject', 'site_option' );
			$headers     = array( 'Content-Type: text/html; charset=UTF-8' );
			$txid        = '';
			$otp_token   = '';
			$otp_token_d = '';
			for ( $i = 1;$i < 7;$i++ ) {
				$otp_token   .= wp_rand( 0, 9 );
				$txid        .= wp_rand( 100, 999 );
				$otp_token_d .= wp_rand( 0, 9 );
			}
			$otp_token_h   = hash( 'sha512', $otp_token );
			$otp_token_d_h = hash( 'sha512', $otp_token_d );
			TwoFAMoSessions::add_session_var( 'mo2f_transactionId', $txid );
			TwoFAMoSessions::add_session_var(
				$txid,
				array(
					'user_id'    => $current_user->ID,
					'user_email' => $email,
				)
			);
			$user_id = hash( 'sha512', $current_user->ID . $txid );
			update_site_option( $user_id, $otp_token_h );
			update_site_option( $txid, 3 );
			$user_idd = $user_id . 'D';
			update_site_option( $user_idd, $otp_token_d_h );
			$message                 = $this->getemailtemplate( $user_id, $otp_token_h, $otp_token_d_h, $txid, $email );
			$cm_vt_y_wlua_w5n_t1_r_q = MoWpnsUtility::get_mo2f_db_option( 'cmVtYWluaW5nT1RQ', 'site_option' );
			$result                  = wp_mail( $email, $subject, $message, $headers );
			$response                = array( 'txId' => $txid );
			if ( $result ) {
				if ( ! apply_filters( 'mo2f_is_lv_needed', false ) && (int) get_site_option( 'cmVtYWluaW5nT1RQ' ) === 5 ) {
					Miniorange_Authentication::mo2f_low_otp_alert( 'email' );
				}
				update_site_option( 'cmVtYWluaW5nT1RQ', $cm_vt_y_wlua_w5n_t1_r_q - 1 );
				$response['status']     = 'SUCCESS';
				$time                   = 'time' . $txid;
				$current_time_in_millis = round( microtime( true ) * 1000 );
				update_site_option( $time, $current_time_in_millis );
			} else {
				$response['status']  = 'ERROR';
				$response['message'] = MoWpnsMessages::ERROR_DURING_PROCESS_EMAIL;
			}
			return wp_json_encode( $response );
		}
		/**
		 * Function to fetch customize email template.
		 *
		 * @param  int    $user_id       Id of user.
		 * @param  string $otp_token_h   OTP token sent to email.
		 * @param  string $otp_token_d_h Variable sent to email.
		 * @param  string $txid          Transaction id to verify the email transaction.
		 * @param  string $email         Email id of user.
		 * @return string
		 */
		public function getemailtemplate( $user_id, $otp_token_h, $otp_token_d_h, $txid, $email ) {
			global $image_path;
			$url     = get_site_option( 'siteurl' ) . '/wp-login.php?';
			$message = MoWpnsUtility::get_mo2f_db_option( 'mo2f_out_of_band_email_template', 'site_option' );
			$message = str_replace( '##image_path##', $image_path, $message );
			$message = str_replace( '##user_id##', $user_id, $message );
			$message = str_replace( '##url##', $url, $message );
			$message = str_replace( '##accept_token##', $otp_token_h, $message );
			$message = str_replace( '##denie_token##', $otp_token_d_h, $message );
			$message = str_replace( '##txid##', $txid, $message );
			$message = str_replace( '##email##', $email, $message );
			return $message;
		}
	}
}
