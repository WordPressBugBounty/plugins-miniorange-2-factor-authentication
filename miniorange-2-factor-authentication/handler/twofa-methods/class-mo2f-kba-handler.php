<?php
/**
 * This file is contains functions related to KBA method.
 *
 * @package miniOrange-2-factor-authentication/handler
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use TwoFA\Onprem\MO2f_Cloud_Onprem_Interface;
use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Onprem\Mo2f_Inline_Popup;
use TwoFA\Onprem\MO2f_Utility;
use TwoFA\Helper\Mo2f_Login_Popup;
use TwoFA\Helper\Mo2f_Common_Helper;
use TwoFA\Onprem\Mo2f_Main_Handler;
use TwoFA\Helper\TwoFAMoSessions;
if ( ! class_exists( 'Mo2f_KBA_Handler' ) ) {
	/**
	 * Class Mo2f_KBA_Handler
	 */
	class Mo2f_KBA_Handler {

		/**
		 * Current Method.
		 *
		 * @var string
		 */
		private $mo2f_current_method;

		/**
		 * KBA Questions.
		 *
		 * @var string
		 */
		private $kba_login_questions;

		/**
		 * Class Mo2f_KBA_Handler constructor
		 */
		public function __construct() {
			$this->mo2f_current_method = MoWpnsConstants::SECURITY_QUESTIONS;
		}



		/**
		 * Process Inline data for SMS.
		 *
		 * @param string $session_id Sessiong ID.
		 * @param string $redirect_to Redirection url.
		 * @param object $current_user_id Current user ID.
		 * @param string $mo2fa_login_message Login message.
		 * @return void
		 */
		public function mo2f_prompt_2fa_setup_inline( $session_id, $redirect_to, $current_user_id, $mo2fa_login_message ) {
			global $mo2f_onprem_cloud_obj;
			$inline_helper = new Mo2f_Inline_Popup();
			$current_user  = get_userdata( $current_user_id );
			$content       = $mo2f_onprem_cloud_obj->mo2f_set_user_two_fa( $current_user, $this->mo2f_current_method );
			$common_helper = new Mo2f_Common_Helper();
			$common_helper->mo2f_inline_css_and_js();
			$html        = '<div class="mo2f_modal" tabindex="-1" role="dialog">
			<div class="mo2f-modal-backdrop"></div>
			<div class="mo_customer_validation-modal-dialog mo_customer_validation-modal-md">';
			$prev_screen = $common_helper->mo2f_get_previous_screen_for_inline( $current_user->ID );
			$html       .= $common_helper->prompt_user_for_kba_setup( $current_user_id, $mo2fa_login_message, $redirect_to, $session_id, $prev_screen );
			$html       .= '</div></div>';
			$html       .= $inline_helper->mo2f_get_inline_hidden_forms( $redirect_to, $session_id, $current_user->ID );
			$html       .= $this->mo2f_get_script( $current_user_id, 'inline' );
			echo $html;// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already escaped the necessary in the definition.
			exit;
		}

		/**
		 * Gets inline script.
		 *
		 * @param int    $user_id User id.
		 * @param string $twofa_flow Twofa flow.
		 * @return string
		 */
		public function mo2f_get_script( $user_id, $twofa_flow ) {
			$common_helper    = new Mo2f_Common_Helper();
			$call_to_function = array( $common_helper, 'mo2f_get_validate_success_response_' . $twofa_flow . '_script' );
			$script           = '<script>
			jQuery(document).ready(function($){
				jQuery(function(){	
				jQuery("a[href=\'#mo2f_login_form\']").click(function() {
					jQuery("#mo2f_backto_mo_loginform").submit();
				});
				jQuery("a[href=\'#mo2f_inline_form\']").click(function() {
					jQuery("#mo2f_backto_inline_registration").submit();
				});
				jQuery(\'#mo2f_next_step3\').css(\'display\',\'none\');
				var ajaxurl = "' . esc_js( admin_url( 'admin-ajax.php' ) ) . '";
				var userId = "' . esc_js( $user_id ) . '";
				jQuery("#mo2f_save_kba").click(function() {
					var nonce = "' . esc_js( wp_create_nonce( 'mo-two-factor-ajax-nonce' ) ) . '";
                    ' . $this->mo2f_get_jquery_data() . '
					jQuery.post(ajaxurl, data, function(response) {
						if (response.success) {
							jQuery("#mo2f_inline_otp_validated_form").submit();
							' . call_user_func( $call_to_function ) . '
						} else if (!response.success) {
							mo2f_show_message(response.data);
						} else {
							mo2f_show_message("Unknown error occurred. Please try again.");
						}
					})
				});
			});
		});';
			$script          .= '</script>';
			return $script;
		}

		/**
		 * Gets jquery data.
		 *
		 * @return string
		 */
		public function mo2f_get_jquery_data() {
			$data = '			var data = {
				action: "mo_two_factor_ajax",
				mo_2f_two_factor_ajax: "mo2f_set_kba",
				mo2f_kbaquestion_1: jQuery("#mo2f_kbaquestion_1").val(),
				mo2f_kbaquestion_2: jQuery("#mo2f_kbaquestion_2").val(),
				mo2f_kbaquestion_3: jQuery("#mo2f_kbaquestion_3").val(),
				mo2f_kba_ans1: jQuery("#mo2f_kba_ans1").val(),
				mo2f_kba_ans2: jQuery("#mo2f_kba_ans2").val(),
				mo2f_kba_ans3: jQuery("#mo2f_kba_ans3").val(),
				redirect_to: jQuery("input[name=\'redirect_to\']").val(),
				session_id: jQuery("input[name=\'session_id\']").val(),
				user_id: userId,
				nonce: nonce,
			};';
			return $data;
		}

		/**
		 * Show KBA configuration prompt on dashboard.
		 *
		 * @return mixed
		 */
		public function mo2f_prompt_2fa_setup_dashboard() {
			global $mo2fdb_queries;
			$current_user = wp_get_current_user();
			$mo2fdb_queries->insert_user( $current_user->ID );
			$common_helper = new Mo2f_Common_Helper();
			$html          = $common_helper->prompt_user_for_kba_setup( $current_user->ID, '', '', '', 'dashboard' );
			$html         .= $common_helper->mo2f_get_dashboard_hidden_forms();
			$html         .= $this->mo2f_get_script( $current_user->ID, 'dashboard' );
			wp_send_json_success( $html );
		}

		/**
		 * Show SMS Testing prompt on dashboard.
		 *
		 * @return mixed
		 */
		public function mo2f_prompt_2fa_test_dashboard() {
			global $mo2f_onprem_cloud_obj;
			$current_user        = wp_get_current_user();
			$mo2fa_login_message = 'Please answer the following questions:';
			$mo2fa_login_status  = MoWpnsConstants::MO_2_FACTOR_CHALLENGE_KBA_AUTHENTICATION;
			$kba_questions       = $mo2f_onprem_cloud_obj->mo2f_pass2login_kba_verification( $current_user, $this->mo2f_current_method, '', '' );
			$login_popup         = new Mo2f_Login_Popup();
			$common_helper       = new Mo2f_Common_Helper();
			$skeleton_values     = $login_popup->mo2f_twofa_login_prompt_skeleton_values( $mo2fa_login_message, $mo2fa_login_status, $kba_questions[0], $kba_questions[1], $current_user->ID, 'test_2fa', '' );
			$html                = $login_popup->mo2f_get_twofa_skeleton_html( $mo2fa_login_status, $mo2fa_login_message, '', '', $skeleton_values, $this->mo2f_current_method, 'test_2fa' );
			$html               .= $login_popup->mo2f_get_validation_popup_script( 'test_2fa', $this->mo2f_current_method, '', '' );
			$html               .= $common_helper->mo2f_get_test_script();
			wp_send_json_success( $html );
		}

		/**
		 * Calls to validate kba in inline.
		 *
		 * @param array $post Post value.
		 * @return void
		 */
		public function mo2f_set_kba( $post ) {
			$session_id_encrypt = isset( $post['session_id'] ) ? sanitize_text_field( wp_unslash( $post['session_id'] ) ) : null;
			$redirect_to        = isset( $post['redirect_to'] ) ? esc_url_raw( wp_unslash( $post['redirect_to'] ) ) : null;
			$user_id            = isset( $post['user_id'] ) ? sanitize_text_field( wp_unslash( $post['user_id'] ) ) : null;
			$current_user       = get_user_by( 'id', $user_id );
			$kba_ques_ans       = $this->mo2f_get_ques_ans( $post );
			$kba_questions      = $this->mo2f_validate_questions( $kba_ques_ans, $session_id_encrypt, $redirect_to, $user_id );
			$kba_answers        = $this->mo2f_validate_answers( $kba_ques_ans, $session_id_encrypt, $redirect_to, $user_id );
			$question_answer    = $this->mo2f_encode_question_answer( $kba_questions, $kba_answers );
			update_user_meta( $current_user->ID, 'mo2f_kba_challenge', $question_answer );
			$this->mo2f_update_user_details( $post, $current_user->ID, $current_user->user_email );
			update_user_meta( $current_user->ID, 'mo2f_2FA_method_to_configure', $this->mo2f_current_method );
			wp_send_json_success();
		}

		/**
		 * Gets questions and answers at inline.
		 *
		 * @param array $post Post data.
		 * @return array
		 */
		public function mo2f_get_ques_ans( $post ) {
			$kba_ques_ans = array(
				'kba_q1' => 'mo2f_kbaquestion_1',
				'kba_a1' => 'mo2f_kba_ans1',
				'kba_q2' => 'mo2f_kbaquestion_2',
				'kba_a2' => 'mo2f_kba_ans2',
				'kba_q3' => 'mo2f_kbaquestion_3',
				'kba_a3' => 'mo2f_kba_ans3',
			);
			foreach ( $kba_ques_ans as $key => $value ) {
				$kba_ques_ans[ $key ] = isset( $post[ $value ] ) ? sanitize_text_field( wp_unslash( $post[ $value ] ) ) : '';
			}
			return $kba_ques_ans;
		}

		/**
		 * Validates questions.
		 *
		 * @param array  $kba_ques_ans Questions-Answeres array.
		 * @param string $session_id_encrypt Session id.
		 * @param string $redirect_to Redirection url.
		 * @param int    $user_id User id.
		 * @return array
		 */
		public function mo2f_validate_questions( $kba_ques_ans, $session_id_encrypt, $redirect_to, $user_id ) {
			$temp_array    = array( $kba_ques_ans['kba_q1'], $kba_ques_ans['kba_q2'], $kba_ques_ans['kba_q3'] );
			$kba_questions = array();
			foreach ( $temp_array as $question ) {
				if ( MO2f_Utility::mo2f_check_empty_or_null( $question ) ) {
					$mo2fa_login_message = __( 'All the fields are required. Please enter valid entries.', 'miniorange-2-factor-authentication' );
					wp_send_json_error( $mo2fa_login_message );
				} else {
					$ques = sanitize_text_field( $question );
					$ques = addcslashes( stripslashes( $ques ), '"\\' );
					array_push( $kba_questions, $ques );
				}
			}
			if ( ! ( array_unique( $kba_questions ) === $kba_questions ) ) {
				$mo2fa_login_message = __( 'The questions you select must be unique.', 'miniorange-2-factor-authentication' );
				wp_send_json_error( $mo2fa_login_message );
			}
			return $kba_questions;
		}

		/**
		 * Validates answers.
		 *
		 * @param array  $kba_ques_ans Questions-Answers array.
		 * @param string $session_id_encrypt Session ID.
		 * @param string $redirect_to Rediretion url.
		 * @param int    $user_id User id.
		 * @return array
		 */
		public function mo2f_validate_answers( $kba_ques_ans, $session_id_encrypt, $redirect_to, $user_id ) {
			$temp_array_ans = array( $kba_ques_ans['kba_a1'], $kba_ques_ans['kba_a2'], $kba_ques_ans['kba_a3'] );
			$kba_answers    = array();
			foreach ( $temp_array_ans as $answer ) {
				if ( MO2f_Utility::mo2f_check_empty_or_null( $answer ) ) {
					$mo2fa_login_message = __( 'All the fields are required. Please enter valid entries.', 'miniorange-2-factor-authentication' );
					wp_send_json_error( $mo2fa_login_message );
				} else {
					$ques   = sanitize_text_field( $answer );
					$answer = strtolower( $answer );
					array_push( $kba_answers, $answer );
				}
			}
			return $kba_answers;
		}

		/**
		 * Encodes questions and answers.
		 *
		 * @param array $kba_questions Questions.
		 * @param array $kba_answers Answers.
		 * @return array
		 */
		public function mo2f_encode_question_answer( $kba_questions, $kba_answers ) {
			$size         = count( $kba_questions );
			$kba_q_a_list = array();
			for ( $c = 0; $c < $size; $c++ ) {
				array_push( $kba_q_a_list, $kba_questions[ $c ] );
				array_push( $kba_q_a_list, $kba_answers[ $c ] );
			}
			$kba_q1          = $kba_q_a_list[0];
			$kba_a1          = md5( $kba_q_a_list[1] );
			$kba_q2          = $kba_q_a_list[2];
			$kba_a2          = md5( $kba_q_a_list[3] );
			$kba_q3          = $kba_q_a_list[4];
			$kba_a3          = md5( $kba_q_a_list[5] );
			$question_answer = array(
				$kba_q1 => $kba_a1,
				$kba_q2 => $kba_a2,
				$kba_q3 => $kba_a3,
			);
			return $question_answer;

		}
		/**
		 * Update Kba details.
		 *
		 * @param array   $post $_POST data.
		 * @param integer $user_id user id.
		 * @param string  $email user email.
		 * @return mixed
		 */
		public function mo2f_update_user_details( $post, $user_id, $email ) {
			global $mo2f_onprem_cloud_obj;
			$kba_ques_ans    = $this->mo2f_get_kba_details( $post );
			$kba_reg_reponse = json_decode( $mo2f_onprem_cloud_obj->mo2f_register_kba_details( $email, $kba_ques_ans['kba_q1'], $kba_ques_ans['kba_a1'], $kba_ques_ans['kba_q2'], $kba_ques_ans['kba_a2'], $kba_ques_ans['kba_q3'], $kba_ques_ans['kba_a3'], $user_id ), true );
			if ( json_last_error() === JSON_ERROR_NONE ) {
				if ( 'SUCCESS' === $kba_reg_reponse['status'] ) {
					delete_user_meta( $user_id, 'mo2f_user_profile_set' );
					$response = json_decode( $mo2f_onprem_cloud_obj->mo2f_update_user_info( $user_id, true, MoWpnsConstants::SECURITY_QUESTIONS, MoWpnsConstants::SUCCESS_RESPONSE, MoWpnsConstants::MO_2_FACTOR_PLUGIN_SETTINGS, true, $email ), true );
				}
			}
			return $response;
		}

		/**
		 * Gets kba details
		 *
		 * @param mixed $post Post data.
		 * @return array
		 */
		public function mo2f_get_kba_details( $post ) {
			$kba_ques_ans = array(
				'kba_q1' => 'mo2f_kbaquestion_1',
				'kba_a1' => 'mo2f_kba_ans1',
				'kba_q2' => 'mo2f_kbaquestion_2',
				'kba_a2' => 'mo2f_kba_ans2',
				'kba_q3' => 'mo2f_kbaquestion_3',
				'kba_a3' => 'mo2f_kba_ans3',
			);
			foreach ( $kba_ques_ans as $key => $value ) {

				$kba_ques_ans[ $key ] = isset( $post[ $value ] ) ? sanitize_text_field( wp_unslash( $post[ $value ] ) ) : '';
			}
			foreach ( $kba_ques_ans as $key => $value ) {

				$kba_ques_ans[ $key ] = addcslashes( stripslashes( $value ), '"\\' );
			}
			return $kba_ques_ans;
		}

		/**
		 * Process login data for KBA.
		 *
		 * @param object $currentuser current user.
		 * @param string $session_id_encrypt Session ID.
		 * @param object $redirect_to Redirection url.
		 * @return void
		 */
		public function mo2f_prompt_2fa_login( $currentuser, $session_id_encrypt, $redirect_to ) {
			global $mo2f_onprem_cloud_obj;
			$mo2fa_login_message = 'Please answer the following questions:';
			$mo2fa_login_status  = MoWpnsConstants::MO_2_FACTOR_CHALLENGE_KBA_AUTHENTICATION;
			$kba_questions       = $mo2f_onprem_cloud_obj->mo2f_pass2login_kba_verification( $currentuser, $this->mo2f_current_method, $redirect_to, $session_id_encrypt );
			$this->mo2f_show_login_prompt( $mo2fa_login_message, $mo2fa_login_status, $currentuser, $redirect_to, $session_id_encrypt, $kba_questions );
			exit;
		}

		/**
		 * Show login popup for Telegram.
		 *
		 * @param string $mo2fa_login_message Login message.
		 * @param string $mo2fa_login_status Login status.
		 * @param object $current_user Current user.
		 * @param string $redirect_to Redirection url.
		 * @param string $session_id_encrypt Session ID.
		 * @param array  $kba_questions KBA questions.
		 * @return void
		 */
		public function mo2f_show_login_prompt( $mo2fa_login_message, $mo2fa_login_status, $current_user, $redirect_to, $session_id_encrypt, $kba_questions = null ) {
			$login_popup = new Mo2f_Login_Popup();
			if ( is_null( $kba_questions ) ) {
				$kba_questions = TwoFAMoSessions::get_session_var( 'mo_2_factor_kba_questions' );
			}
			$login_popup->mo2f_show_login_prompt_for_otp_based_methods( $mo2fa_login_message, $mo2fa_login_status, $current_user, $redirect_to, $session_id_encrypt, $this->mo2f_current_method, $kba_questions );
			exit;
		}

		/**
		 * Validate KBA at login.
		 *
		 * @param string $mo2f_login_transaction_id Login transaction id.
		 * @param string $kba_ques_ans OTP token.
		 * @param object $current_user Current user.
		 * @return mixed
		 */

		/**
		 * Validate otp at login.
		 *
		 * @param string $otp_token OTP token.
		 * @param string $redirect_to Redirection url.
		 * @param string $session_id_encrypt Session id.
		 * @return mixed
		 */
		public function mo2f_login_validate( $otp_token, $redirect_to, $session_id_encrypt ) {
			global $mo2f_onprem_cloud_obj;
			if ( ! check_ajax_referer( 'mo-two-factor-ajax-nonce', 'nonce', false ) ) {
				wp_send_json_error( 'class-mo2f-ajax' );
			}
			$user_id = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );
			if ( ! $user_id && is_user_logged_in() ) {
				$user    = wp_get_current_user();
				$user_id = $user->ID;
			}
			$current_user    = get_user_by( 'id', $user_id );
			$kba_ques_ans    = array();
			$kba_questions   = TwoFAMoSessions::get_session_var( 'mo_2_factor_kba_questions' );
			$kba_ques_ans[0] = $kba_questions[0];
			$kba_ques_ans[1] = isset( $_POST['mo2f_answer_1'] ) ? sanitize_text_field( wp_unslash( $_POST['mo2f_answer_1'] ) ) : '';
			$kba_ques_ans[2] = $kba_questions[1];
			$kba_ques_ans[3] = isset( $_POST['mo2f_answer_2'] ) ? sanitize_text_field( wp_unslash( $_POST['mo2f_answer_2'] ) ) : '';
			$content         = json_decode( $mo2f_onprem_cloud_obj->validate_otp_token( $this->mo2f_current_method, $current_user->user_email, '', $kba_ques_ans, $current_user ), true );
			if ( 0 === strcasecmp( $content['status'], 'SUCCESS' ) ) {
				wp_send_json_success( 'VALIDATED_SUCCESS' );
			} else {
				wp_send_json_error( 'INVALID_ANSWERS' );
			}
		}

	}
	new Mo2f_KBA_Handler();
}
