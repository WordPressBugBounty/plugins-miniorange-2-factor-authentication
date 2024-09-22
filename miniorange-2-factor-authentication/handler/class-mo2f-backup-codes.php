<?php
/**
 * This file is part of reconfigure link feature.
 *
 * @package miniOrange-2-factor-authentication/handler
 */

namespace TwoFA\Onprem;

use TwoFA\Onprem\MO2f_Utility;
use TwoFA\Helper\Mo2f_Login_Popup;
use TwoFA\Helper\MoWpnsUtility;
use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Helper\MoWpnsMessages;
use TwoFA\Helper\Mo2f_Common_Helper;
use WP_REST_Request;
use TwoFA\Helper\MocURL;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Mo2f_Backup_Codes' ) ) {
	/**
	 * Class Mo2f_Backup_Codes
	 */
	class Mo2f_Backup_Codes {

		/**
		 * This will validate or Use the backcode
		 *
		 * @param string $post It will carry the post data .
		 * @return void
		 */
		public function mo2f_use_backup_codes( $post ) {
			$session_id_encrypt  = isset( $post['session_id'] ) ? sanitize_text_field( $post['session_id'] ) : null;
			$redirect_to         = isset( $post['redirect_to'] ) ? esc_url_raw( $post['redirect_to'] ) : null;
			$login_method        = isset( $post['login_method'] ) ? sanitize_text_field( $post['login_method'] ) : '';
			$mo2fa_login_message = __( 'Please provide your backup codes.', 'miniorange-2-factor-authentication' );
			$this->mo2f_call_backup_code_validation_form( $mo2fa_login_message, $redirect_to, $session_id_encrypt, $login_method );
		}


		/**
		 * Creates and send backup codes on email.
		 *
		 * @param array $post Post data.
		 * @return void
		 */
		public function mo2f_send_backup_codes( $post ) {
			global $mo2fdb_queries;
			$redirect_to        = isset( $post['redirect_to'] ) ? esc_url_raw( wp_unslash( $post['redirect_to'] ) ) : '';
			$session_id         = isset( $post['session_id'] ) ? sanitize_text_field( wp_unslash( $post['session_id'] ) ) : '';
			$mo2fa_login_status = isset( $post['login_status'] ) ? sanitize_text_field( wp_unslash( $post['login_status'] ) ) : '';
			$mo2fa_login_method = isset( $post['login_method'] ) ? sanitize_text_field( wp_unslash( $post['login_method'] ) ) : '';
			$user_id            = MO2f_Utility::mo2f_get_transient( $session_id, 'mo2f_current_user_id' );
			$mo2f_user_email    = $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $user_id );
			$currentuser        = get_user_by( 'id', $user_id );
			if ( empty( $mo2f_user_email ) ) {
				$mo2f_user_email = $currentuser->user_email;
			}
			$generate_backup_code = new MocURL();
			$codes                = $generate_backup_code->mo_2f_generate_backup_codes( $mo2f_user_email, site_url() );
			$common_helper        = new Mo2f_Common_Helper();
			$mo2f_message         = $common_helper->mo2f_check_backupcode_status( $codes, $user_id );
			if ( ! $mo2f_message ) {
				$codes        = explode( ' ', $codes );
				$mo2f_message = $common_helper->mo2f_send_backcodes_on_email( $codes, $mo2f_user_email, $user_id );
			}
			$this->mo2f_show_backup_code_sent_message( $mo2f_message, $mo2fa_login_status, $currentuser, $redirect_to, $session_id, $mo2fa_login_method );
		}

		/**
		 * This function will invoke for back up code validation
		 *
		 * @param string $post It will carry the post data .
		 * @return void
		 */
		public function mo2f_validate_backup_codes( $post ) {
			global $mo2fdb_queries;
			$session_id_encrypt = isset( $post['session_id'] ) ? sanitize_text_field( $post['session_id'] ) : null;
			$redirect_to        = isset( $post['redirect_to'] ) ? esc_url_raw( $post['redirect_to'] ) : null;
			$mo2f_backup_code   = isset( $post['mo2f_backup_code'] ) ? sanitize_text_field( $post['mo2f_backup_code'] ) : null;
			$twofa_method       = isset( $post['twofa_method'] ) ? sanitize_text_field( $post['twofa_method'] ) : '';
			$currentuser_id     = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );
			$mo2f_user_email    = $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $currentuser_id );
			$this->mo2f_handle_backupcode_validation( $mo2f_backup_code, $currentuser_id, $redirect_to, $session_id_encrypt, $mo2f_user_email, $twofa_method );
		}

		/**
		 * Shows backup code sent message.
		 *
		 * @param string $mo2f_message Message.
		 * @param string $mo2fa_login_status Login status.
		 * @param object $currentuser Current user.
		 * @param string $redirect_to Redirection url.
		 * @param string $session_id Session ID.
		 * @param string $mo2fa_login_method Twofa method.
		 * @return void
		 */
		public function mo2f_show_backup_code_sent_message( $mo2f_message, $mo2fa_login_status, $currentuser, $redirect_to, $session_id, $mo2fa_login_method ) {
			$common_helper  = new Mo2f_Common_Helper();
			$method_handler = $common_helper->mo2f_get_object( $mo2fa_login_method );
			$method_handler->mo2f_show_login_prompt( $mo2f_message, $mo2fa_login_status, $currentuser, $redirect_to, $session_id );
		}

		/**
		 * Shows error prompt at login.
		 *
		 * @param string $mo2fa_login_message Login message.
		 * @param object $current_user Current user.
		 * @param string $redirect_to Redirection url.
		 * @param string $session_id Session id.
		 * @param string $twofa_method Twofa method.
		 * @return void
		 */
		public function mo2f_show_error_prompt( $mo2fa_login_message, $current_user, $redirect_to, $session_id, $twofa_method ) {
			$login_popup        = new Mo2f_Login_Popup();
			$mo2fa_login_status = MoWpnsConstants::MO2F_ERROR_MESSAGE_PROMPT;
			$skeleton_values    = $login_popup->mo2f_twofa_login_prompt_skeleton_values( $mo2fa_login_message, $mo2fa_login_status, null, null, $current_user->ID, '' );
			$html               = $login_popup->mo2f_get_twofa_skeleton_html( $mo2fa_login_status, $mo2fa_login_message, '', '', $skeleton_values, $twofa_method, '' );
			$html              .= $login_popup->mo2f_get_validation_popup_script( '', $twofa_method, '', '' );
			exit;

		}

		/**
		 * Calls to backup code validation form.
		 *
		 * @param string $mo2fa_login_message Login message.
		 * @param string $redirect_to Redirection Url.
		 * @param string $session_id_encrypt Session ID.
		 * @param string $login_method Twofa method.
		 * @return void
		 */
		public function mo2f_call_backup_code_validation_form( $mo2fa_login_message, $redirect_to, $session_id_encrypt, $login_method ) {
			$login_popup   = new Mo2f_Login_Popup();
			$common_helper = new Mo2f_Common_Helper();
			$user_id       = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );
			$common_helper->mo2f_echo_js_css_files();
			$mo2fa_login_status = MoWpnsConstants::MO_2_FACTOR_USE_BACKUP_CODES;
			$skeleton_values    = $login_popup->mo2f_twofa_login_prompt_skeleton_values( $mo2fa_login_message, $mo2fa_login_status, null, null, $user_id, '' );
			$html               = $login_popup->mo2f_twofa_authentication_login_prompt( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, $session_id_encrypt, $skeleton_values, $login_method );
			$html              .= $common_helper->mo2f_get_hidden_forms_login( $redirect_to, $session_id_encrypt, $mo2fa_login_status, $mo2fa_login_message, $login_method, $user_id );
			$html              .= $common_helper->mo2f_get_hidden_script_login();
			$html              .= $this->mo2f_get_validation_hidden_form( $redirect_to, $session_id_encrypt );
			$html              .= $this->mo2f_get_login_script();
			echo $html;// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already escaped the necessary in the definition.
			exit;

		}

		/**
		 * Gets hidden for validation success.
		 *
		 * @param string $redirect_to Redirection url.
		 * @param string $session_id Session id.
		 * @return string
		 */
		public function mo2f_get_validation_hidden_form( $redirect_to, $session_id ) {
			$html = '<form name="mo2f_backup_code_validation_form" method="post" action="" id="mo2f_backup_code_validation_form" style="display:none;">
			<input type="hidden" name="redirect_to" value="' . esc_url( $redirect_to ) . '"/>
			<input type="hidden" name="session_id" value="' . esc_attr( $session_id ) . '"/>
			<input type="hidden" name="option" value="mo2f_backup_code_validation_success"/>
			<input type="hidden" name="miniorange_inline_save_2factor_method_nonce" value="' . esc_attr( wp_create_nonce( 'miniorange-2-factor-inline-save-2factor-method-nonce' ) ) . '"/>
		</form>';
			return $html;

		}



		/**
		 * Gets the script for login.
		 *
		 * @return string
		 */
		public function mo2f_get_login_script() {

			$script = '<script>			jQuery("#mo2f_validate").click(function() {
				var nonce = "' . wp_create_nonce( 'mo-two-factor-ajax-nonce' ) . '";
				var ajaxurl = "' . esc_js( admin_url( 'admin-ajax.php' ) ) . '";
				var data = {
					action: "mo_two_factor_ajax",
					mo_2f_two_factor_ajax: "mo2f_validate_backup_codes",
					twofa_method: jQuery("input[name=\'mo2f_login_method\']").val(),
					redirect_to: jQuery("input[name=\'redirect_to\']").val(),
					session_id: jQuery("input[name=\'session_id\']").val(),
					mo2f_backup_code: jQuery("input[name=\'mo2f_backup_code\']").val(),
					nonce: nonce,
				};
				jQuery.post(ajaxurl, data, function(response) {
					if (response.success) {
						jQuery("#mo2f_backup_code_validation_form").submit();
					} else {
						mo2f_show_message(response.data);
					}
				});
			});
		</script>';
			return $script;

		}

		/**
		 * Handles backup code validation.
		 *
		 * @param string $mo2f_backup_code Entered backup code.
		 * @param int    $currentuser_id User id.
		 * @param string $redirect_to Redirection url.
		 * @param string $session_id_encrypt Session ID.
		 * @param string $mo2f_user_email User email.
		 * @param string $twofa_method Twofa method.
		 * @return void
		 */
		public function mo2f_handle_backupcode_validation( $mo2f_backup_code, $currentuser_id, $redirect_to, $session_id_encrypt, $mo2f_user_email, $twofa_method ) {
			global $mo2fdb_queries;
			$backup_codes = get_user_meta( $currentuser_id, 'mo2f_backup_codes', true );
			if ( ! empty( $backup_codes ) ) {  // This will be used in premium plugin.
				$mo2f_backup_code = md5( $mo2f_backup_code );
				if ( in_array( $mo2f_backup_code, $backup_codes, true ) ) {
					foreach ( $backup_codes as $key => $value ) {
						if ( $value === $mo2f_backup_code ) {
							unset( $backup_codes[ $key ] );
							update_user_meta( $currentuser_id, 'mo2f_backup_codes', $backup_codes );
							$mo2fdb_queries->delete_user_details( $currentuser_id );
							wp_send_json_success();
						}
					}
				} else {
					wp_send_json_error( MoWpnsMessages::lang_translate( 'The code you provided is already used or incorrect.' ) );
				}
			} else {
				if ( isset( $mo2f_backup_code ) ) {
					$generate_backup_code = new MocURL();
					$data                 = $generate_backup_code->mo2f_validate_backup_codes( $mo2f_backup_code, $mo2f_user_email );
					if ( 'success' === $data ) {
						$mo2f_delete_details = new Miniorange_Authentication();
						$mo2f_delete_details->mo2f_delete_user( $currentuser_id );
						wp_send_json_success( MoWpnsMessages::lang_translate( 'Backup code valided successfully.' ) );
					} else {
						wp_send_json_error( MoWpnsMessages::lang_translate( 'The code you provided is already used or incorrect.' ) );
					}
				} else {
					wp_send_json_error( MoWpnsMessages::lang_translate( 'Please enter backup code.' ) );
				}
			}

		}

		/**
		 * Handles flow after successful backup code validation.
		 *
		 * @param array $post Post data.
		 * @return void
		 */
		public function mo2f_backup_code_validation_success( $post ) {
			global $mo2fdb_queries;
			$session_id_encrypt = isset( $post['session_id'] ) ? sanitize_text_field( wp_unslash( $post['session_id'] ) ) : '';
			$redirect_to        = isset( $post['redirect_to'] ) ? esc_url_raw( wp_unslash( $post['redirect_to'] ) ) : '';
			$user_id            = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );
			if ( ! get_site_option( 'mo2f_disable_inline_registration' ) ) {
					$mo2fdb_queries->insert_user( $user_id );
					$mo2fa_login_message = 'Please configure your 2FA again so that you can avoid being locked out.';
					$inline_popup        = new Mo2f_Inline_Popup();
					$mo2fdb_queries->update_user_details( $user_id, array( 'user_registration_with_miniorange' => 'SUCCESS' ) );
					$inline_popup->prompt_user_to_select_2factor_mthod_inline( $user_id, $mo2fa_login_message, $redirect_to, $session_id_encrypt );
					exit;
			} else {
				$pass2fa = new Mo2f_Main_Handler();
				$pass2fa->mo2fa_pass2login( $redirect_to, $session_id_encrypt );
			}
			exit;
		}

	}
	new Mo2f_Backup_Codes();
}
