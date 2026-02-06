<?php
/**
 * This file is contains functions related to KBA method.
 *
 * @package miniOrange-2-factor-authentication/handler
 */

namespace TwoFA\Handler\TwofaMethods;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use TwoFA\Handler\Twofa\MO2f_Cloud_Onprem_Interface;
use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Helper\Mo2f_Inline_Popup;
use TwoFA\Handler\Twofa\MO2f_Utility;
use TwoFA\Helper\Mo2f_Login_Popup;
use TwoFA\Helper\Mo2f_Common_Helper;
use TwoFA\Handler\Mo2f_Main_Handler;
use TwoFA\Helper\TwoFAMoSessions;
use TwoFA\Traits\Instance;

if ( ! class_exists( 'Mo2f_KBA_Handler' ) ) {
	/**
	 * Class Mo2f_KBA_Handler
	 */
	class Mo2f_KBA_Handler {

		use Instance;

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
				jQuery("#mo2f_save_kba").click(function() {
					' . $common_helper->mo2f_show_loader() . '
					var nonce = "' . esc_js( wp_create_nonce( 'mo-two-factor-ajax-nonce' ) ) . '";
                    ' . $this->mo2f_get_jquery_data() . '
					jQuery.post(ajaxurl, data, function(response) {
					    ' . $common_helper->mo2f_hide_loader() . '
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
			$default_question_count = get_site_option( 'mo2f_default_kbaquestions_users', 2 );
			$custom_question_count  = get_site_option( 'mo2f_custom_kbaquestions_users', 1 );
			$total_questions        = $default_question_count + $custom_question_count;
			$data                   = 'var data = {
				action: "mo_two_factor_ajax",
				mo_2f_two_factor_ajax: "mo2f_set_kba",
				nonce: nonce,
			};';
			for ( $i = 1; $i <= $total_questions; $i++ ) {
				$data .= '
				data["mo2f_kbaquestion_' . $i . '"] = jQuery("#mo2f_kbaquestion_' . $i . '").val();
				data["mo2f_kba_ans' . $i . '"] = jQuery("#mo2f_kba_ans' . $i . '").val();';
			}
			$data .= '
			data["redirect_to"] = jQuery("input[name=\'redirect_to\']").val();
			data["session_id"] = jQuery("input[name=\'session_id\']").val();';

			return $data;
		}

		/**
		 * Show KBA configuration prompt on dashboard.
		 *
		 * @param string $session_id_encrypt Session id.
		 * @return mixed
		 */
		public function mo2f_prompt_2fa_setup_dashboard( $session_id_encrypt ) {
			global $mo2fdb_queries;
			$current_user  = wp_get_current_user();
			$common_helper = new Mo2f_Common_Helper();
			$html          = $common_helper->prompt_user_for_kba_setup( $current_user->ID, '', '', '', 'dashboard' );
			$html         .= $common_helper->mo2f_get_dashboard_hidden_forms();
			$html         .= $this->mo2f_get_script( $current_user->ID, 'dashboard' );
			wp_send_json_success( $html );
		}

		/**
		 * Show SMS Testing prompt on dashboard.
		 *
		 * @param string $session_id_encrypt Session id.
		 * @return mixed
		 */
		public function mo2f_prompt_2fa_test_dashboard( $session_id_encrypt ) {
			global $mo2f_onprem_cloud_obj;
			$current_user        = wp_get_current_user();
			$mo2fa_login_message = 'Please answer the following questions:';
			$mo2fa_login_status  = MoWpnsConstants::MO_2_FACTOR_CHALLENGE_KBA_AUTHENTICATION;
			$kba_questions       = $mo2f_onprem_cloud_obj->mo2f_pass2login_kba_verification( $current_user, $this->mo2f_current_method, '', $session_id_encrypt );
			$login_popup         = new Mo2f_Login_Popup();
			$common_helper       = new Mo2f_Common_Helper();
			$skeleton_values     = $login_popup->mo2f_twofa_login_prompt_skeleton_values( $mo2fa_login_message, $mo2fa_login_status, $kba_questions[0], $kba_questions[1], $current_user->ID, 'test_2fa', '', $session_id_encrypt );
			$html                = $login_popup->mo2f_get_twofa_skeleton_html( $mo2fa_login_status, $mo2fa_login_message, '', $session_id_encrypt, $skeleton_values, $this->mo2f_current_method, 'test_2fa' );
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
			global $mo2fdb_queries;
			$session_id_encrypt = isset( $post['session_id'] ) ? sanitize_text_field( wp_unslash( $post['session_id'] ) ) : null;
			if ( empty( $session_id_encrypt ) && ! is_user_logged_in() ) {
				wp_send_json_error( __( 'Oops! There was a problem completing the setup. Please refresh the page and try again.', 'miniorange-2-factor-authentication' ) );
			}
			$redirect_to   = isset( $post['redirect_to'] ) ? esc_url_raw( wp_unslash( $post['redirect_to'] ) ) : null;
			$common_helper = new Mo2f_Common_Helper();
			$user_id       = $common_helper->mo2f_get_current_user_id( $session_id_encrypt );
			$current_user  = empty( $user_id ) ? wp_get_current_user() : get_user_by( 'id', $user_id );
			if ( empty( $current_user ) ) {
				wp_send_json_error( __( 'Something went wrong. Please try again.', 'miniorange-2-factor-authentication' ) );
			}
			$kba_ques_ans    = $this->mo2f_get_ques_ans( $post );
			$kba_questions   = $this->mo2f_validate_questions( $kba_ques_ans, $session_id_encrypt, $redirect_to, $user_id );
			$kba_answers     = $this->mo2f_validate_answers( $kba_ques_ans, $session_id_encrypt, $redirect_to, $user_id );
			$question_answer = $this->mo2f_encode_question_answer( $kba_questions, $kba_answers );
			update_user_meta( $current_user->ID, 'mo2f_kba_challenge', $question_answer );
			if ( get_transient( $session_id_encrypt . 'mo2f_is_kba_backup_configured' . $user_id ) ) {
				update_user_meta( $user_id, 'mo2f_backup_method_set', 1 );
				$mo2fdb_queries->mo2f_update_user_details( $user_id, array( 'mo2f_SecurityQuestions_config_status' => true ) );
			} else {
				$this->mo2f_update_user_details( $post, $current_user->ID, $current_user->user_email );
			}
			$common_helper = new Mo2f_Common_Helper();
			$common_helper->mo2f_update_current_user_status( $session_id_encrypt );
			wp_send_json_success();
		}

		/**
		 * Gets questions and answers at inline.
		 *
		 * @param array $post Post data.
		 * @return array
		 */
		public function mo2f_get_ques_ans( $post ) {
			$default_question_count = get_site_option( 'mo2f_default_kbaquestions_users', 2 );
			$custom_question_count  = get_site_option( 'mo2f_custom_kbaquestions_users', 1 );
			$total_questions        = $default_question_count + $custom_question_count;
			$kba_ques_ans           = array();
			for ( $i = 1; $i <= $total_questions; $i++ ) {
				$kba_ques_ans[ 'kba_q' . $i ] = isset( $post[ 'mo2f_kbaquestion_' . $i ] ) ? sanitize_text_field( wp_unslash( $post[ 'mo2f_kbaquestion_' . $i ] ) ) : '';
				$kba_ques_ans[ 'kba_a' . $i ] = isset( $post[ 'mo2f_kba_ans' . $i ] ) ? sanitize_text_field( wp_unslash( $post[ 'mo2f_kba_ans' . $i ] ) ) : '';
			}
			return $kba_ques_ans;
		}

		/**
		 * Validates uniqueness of items (questions or answers) by normalizing them.
		 *
		 * @param array  $items Array of items to validate.
		 * @param string $error_message Error message to display if duplicates found.
		 * @return void
		 */
		private function mo2f_validate_uniqueness( $items, $error_message ) {
			$normalized_items = array();
			foreach ( $items as $item ) {
				$normalized_items[] = strtolower( preg_replace( '/\s+/', '', $item ) );
			}
			if ( count( $items ) !== count( array_unique( $normalized_items ) ) ) {
				wp_send_json_error( $error_message );
			}
		}

		/**
		 * Common validation logic for KBA items (questions or answers).
		 *
		 * @param array  $kba_ques_ans The question-answer array from post data.
		 * @param string $key_prefix The key prefix to filter by ('kba_q' or 'kba_a').
		 * @return array Sanitized array of items.
		 */
		private function mo2f_validate_kba_items( $kba_ques_ans, $key_prefix ) {
			$items = array();
			foreach ( $kba_ques_ans as $key => $item ) {
				if ( strpos( $key, $key_prefix ) === 0 ) {
					if ( MO2f_Utility::mo2f_check_empty_or_null( $item ) ) {
						$mo2fa_login_message = __( 'All the fields are required. Please enter valid entries.', 'miniorange-2-factor-authentication' );
						wp_send_json_error( $mo2fa_login_message );
					} else {
						$sanitized_item = sanitize_text_field( $item );
						array_push( $items, $sanitized_item );
					}
				}
			}
			return $items;
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
			$kba_questions = $this->mo2f_validate_kba_items( $kba_ques_ans, 'kba_q' );
			// Apply question-specific processing.
			foreach ( $kba_questions as &$question ) {
				$question = addcslashes( stripslashes( $question ), '"\\' );
			}
			$this->mo2f_validate_uniqueness( $kba_questions, __( 'The questions you select must be unique.', 'miniorange-2-factor-authentication' ) );
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
			$kba_answers = $this->mo2f_validate_kba_items( $kba_ques_ans, 'kba_a' );
			// Apply answer-specific processing.
			foreach ( $kba_answers as &$answer ) {
				$answer = strtolower( $answer );
			}
			$this->mo2f_validate_uniqueness( $kba_answers, __( 'The answers you select must be unique.', 'miniorange-2-factor-authentication' ) );
			return $kba_answers;
		}

		/**
		 * Encodes questions and answers using secure password hashing.
		 *
		 * @param array $kba_questions Questions.
		 * @param array $kba_answers Answers.
		 * @return array
		 */
		public function mo2f_encode_question_answer( $kba_questions, $kba_answers ) {
			$size         = count( $kba_questions );
			$kba_q_a_list = array();
			for ( $c = 0; $c < $size; $c++ ) {
				$question                  = $kba_questions[ $c ];
				$answer                    = wp_hash_password( strtolower( $kba_answers[ $c ] ) );
				$kba_q_a_list[ $question ] = $answer;
			}
			return $kba_q_a_list;
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
			$kba_ques_ans    = $this->mo2f_get_ques_ans( $post );
			$kba_reg_reponse = json_decode( $mo2f_onprem_cloud_obj->mo2f_register_kba_details( $email, $kba_ques_ans, $user_id ), true );
			if ( json_last_error() === JSON_ERROR_NONE ) {
				if ( 'SUCCESS' === $kba_reg_reponse['status'] ) {
					delete_user_meta( $user_id, 'mo2f_user_profile_set' );
					$response = json_decode( $mo2f_onprem_cloud_obj->mo2f_update_user_info( $user_id, true, MoWpnsConstants::SECURITY_QUESTIONS, MoWpnsConstants::SUCCESS_RESPONSE, MoWpnsConstants::MO_2_FACTOR_PLUGIN_SETTINGS, true, $email ), true );
				}
			}
			return $response;
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
				$kba_questions = get_transient( $session_id_encrypt . 'mo_2_factor_kba_questions' );
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
			$common_helper = new Mo2f_Common_Helper();
			$user_id       = $common_helper->mo2f_get_current_user_id( $session_id_encrypt );
			if ( ! $user_id && is_user_logged_in() ) {
				$user    = wp_get_current_user();
				$user_id = $user->ID;
			}
			$current_user    = get_user_by( 'id', $user_id );
			$kba_ques_ans    = array();
			$kba_questions   = get_transient( $session_id_encrypt . 'mo_2_factor_kba_questions' );
			$kba_ques_ans[0] = $kba_questions[0];
			$kba_ques_ans[1] = isset( $_POST['mo2f_answer_1'] ) ? sanitize_text_field( wp_unslash( $_POST['mo2f_answer_1'] ) ) : '';
			$kba_ques_ans[2] = $kba_questions[1];
			$kba_ques_ans[3] = isset( $_POST['mo2f_answer_2'] ) ? sanitize_text_field( wp_unslash( $_POST['mo2f_answer_2'] ) ) : '';
			$content         = json_decode( $mo2f_onprem_cloud_obj->validate_otp_token( $this->mo2f_current_method, $current_user->user_email, '', $kba_ques_ans, $current_user, $session_id_encrypt ), true );
			if ( 0 === strcasecmp( $content['status'], 'SUCCESS' ) ) {
				$common_helper = new Mo2f_Common_Helper();
				$common_helper->mo2f_update_current_user_status( $session_id_encrypt );
				wp_send_json_success( 'VALIDATED_SUCCESS' );
			} else {
				wp_send_json_error( 'INVALID_ANSWERS' );
			}
		}
	}
}
