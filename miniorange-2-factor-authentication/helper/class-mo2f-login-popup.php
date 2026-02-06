<?php
/**
 * This file is contains functions related to KBA method.
 *
 * @package miniOrange-2-factor-authentication/handler
 */

namespace TwoFA\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use TwoFA\Handler\Twofa\MO2f_Utility;
use TwoFA\Helper\MoWpnsHandler;
use TwoFA\Helper\MoWpnsUtility;
use TwoFA\Helper\Mo2f_Inline_Popup;
use TwoFA\Traits\Instance;

if ( ! class_exists( 'Mo2f_Login_Popup' ) ) {
	/**
	 * Class Mo2f_Login_Popup
	 */
	class Mo2f_Login_Popup {

		use Instance;

		/**
		 * Gets skeleton values according to the 2fa method.
		 *
		 * @param string $login_message Login message.
		 * @param string $login_status Login status.
		 * @param array  $kba_question1 KBA question 1.
		 * @param array  $kba_question2 KBA question 2.
		 * @param int    $user_id User Id.
		 * @param string $validation_flow Validation flow.
		 * @param string $login_title Login title.
		 * @param string $session_id_encrypt Session id encrypt.
		 * @return array
		 */
		public function mo2f_twofa_login_prompt_skeleton_values( $login_message, $login_status, $kba_question1, $kba_question2, $user_id, $validation_flow, $login_title = '', $session_id_encrypt = '' ) {
			$prompt_title = $this->mo2f_login_prompt_title( $login_title, $login_status );
			if ( ! get_transient( $session_id_encrypt . 'mo2f_attempts_before_redirect' ) ) {
				set_transient( $session_id_encrypt . 'mo2f_attempts_before_redirect', 3 );
			}
			$attempts            = get_transient( $session_id_encrypt . 'mo2f_attempts_before_redirect' );
			$backup_methods      = (array) get_site_option( 'mo2f_enabled_backup_methods' );
			$custom_logo_enabled = get_site_option( 'mo2f_custom_logo', 'miniOrange2.png' );
			$skeleton_blocks     = $this->mo2f_skeleton_block( $prompt_title, $login_message, $login_status, $kba_question1, $kba_question2, $user_id, $validation_flow, $attempts, $custom_logo_enabled, $backup_methods );
			$common_helper       = new Mo2f_Common_Helper();
			$configure_methods   = $common_helper->mo2fa_return_methods_value( $user_id );
			if ( $common_helper->mo2f_is_2fa_set( $user_id ) ) {
				if ( empty( get_user_meta( $user_id, 'mo_backup_code_generated', true ) ) ) {
					$skeleton_blocks['use_backup_codes'] = '';
				} else {
					$skeleton_blocks['send_backup_codes'] = '';
				}
				if ( in_array( $login_status, array( MoWpnsConstants::MO_2_FACTOR_RECONFIGURATION_LINK_SENT, MoWpnsConstants::MO_2_FACTOR_USE_BACKUP_CODES ), true ) ) {
					$skeleton_blocks['backtologin'] = 'mo2f_validation_screen';
				} elseif ( $common_helper->mo2f_check_mfa_details( $configure_methods ) ) {
					$skeleton_blocks['backtologin'] = 'mo2f_mfa_form';
				} else {
					$skeleton_blocks['backtologin'] = 'mo2f_login_form';
				}
			} else {
				$skeleton_blocks['use_backup_codes']   = '';
				$skeleton_blocks['send_backup_codes']  = '';
				$skeleton_blocks['send_reconfig_link'] = '';
			}
			$default_login_status_block = array(
				'##mo2f_title##'        => $skeleton_blocks['login_prompt_title'],
				'##login_message##'     => $skeleton_blocks['login_prompt_message'],
				'##attemptleft##'       => '',
				'##enterotp##'          => '',
				'##enterbackupcode##'   => '',
				'##enteranswers##'      => '',
				'##resendotp##'         => '',
				'##emailloader##'       => '',
				'##backtologin##'       => $skeleton_blocks['backtologin'],
				'##validatebutton##'    => '',
				'##rbaconsent##'        => '',
				'##remipconsent##'      => '',
				'##usebackupcodes##'    => '',
				'##sendbackupcodes##'   => '',
				'##sendreconfiglink##'  => '',
				'##customlogo##'        => $skeleton_blocks['custom_logo'],
				'##backupmethod##'      => '',
				'##confirmationblock##' => '',
			);

			$login_status_blocks = $this->mo2f_fetch_login_status_block_skeleton( $skeleton_blocks, $login_status );
			return array_merge( $default_login_status_block, $login_status_blocks ?? array() );
		}

		/**
		 * Returns the appropriate login prompt title based on the user's 2FA login status.
		 *
		 * @param string $login_title   Custom login title used in specific scenarios (e.g., reconfiguration link sent).
		 * @param string $login_status  The current login status or 2FA flow identifier.
		 *
		 * @return string The login prompt title corresponding to the login status.
		 */
		public function mo2f_login_prompt_title( $login_title, $login_status ) {
			$prompt_title = array(
				MoWpnsConstants::MO_2_FACTOR_RECONFIGURATION_LINK_SENT => $login_title,
				MoWpnsConstants::MO_2_FACTOR_CHALLENGE_KBA_AUTHENTICATION => 'Validate Security Questions',
				MoWpnsConstants::MO2F_ERROR_MESSAGE_PROMPT => 'Something Went Wrong!',
				MoWpnsConstants::MO_2_FACTOR_USE_BACKUP_CODES => 'Validate Backup Code',
				MoWpnsConstants::MO2F_USER_BLOCKED_PROMPT  => 'Access Denied!',
				MoWpnsConstants::MO2F_RBA_GET_USER_CONSENT => 'Remember Device',
				MoWpnsConstants::MO_2_FACTOR_CHALLENGE_OOB_EMAIL => 'Verify Link',
				MoWpnsConstants::MO2F_REMEMBER_IP_GET_USER_CONSENT => 'Remember IP',
				MoWpnsConstants::MO_2_FACTOR_SHOW_CONFIRMATION_BLOCK => 'Confirmation Popup',

			);
			return $prompt_title[ $login_status ] ?? 'Validate OTP';
		}

		/**
		 * Constructs an array of HTML content blocks used for rendering the 2FA login prompt UI.
		 *
		 * @param string $prompt_title  Login prompt title.
		 * @param string $login_message Login message.
		 * @param string $login_status Login status.
		 * @param array  $kba_question1 KBA question 1.
		 * @param array  $kba_question2 KBA question 2.
		 * @param int    $user_id User Id.
		 * @param string $validation_flow Validation flow.
		 * @param int    $attempts Remaining login attempts.
		 * @param string $custom_logo_enabled custom logo.
		 * @param array  $backup_methods enabled backup method.
		 * @return array
		 */
		public function mo2f_skeleton_block( $prompt_title, $login_message, $login_status, $kba_question1, $kba_question2, $user_id, $validation_flow, $attempts, $custom_logo_enabled, $backup_methods ) {
			$common_helper   = new Mo2f_Common_Helper();
			$skeleton_blocks = array(
				'login_prompt_title'     => esc_html__( $prompt_title, 'miniorange-2-factor-authentication' ), // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- This is a string literal.
				'login_prompt_message'   => $login_message . '<br>',
				'attempt_left'           => 'test_2fa' === $validation_flow ? '' : '<br><span class="mo2f_middle_text"><b>Attempts left</b>:&nbsp;</span><span class="mo2f_middle_text" id="mo2f_attempt_span">' . esc_html( $attempts ) . '</span><br><br>',
				'enter_otp_sms'          => '<br><div class="mo2fa_text-align-center mo2f-otp-catchy-box">
													<input type="text" name="mo2fa_softtoken"
													placeholder="' . esc_attr__( 'Enter code', 'miniorange-2-factor-authentication' ) . '"
													id="mo2fa_softtoken" required="true" class="mo2f_otp_token mo2f_kba_input_fields" autofocus="true"
													pattern="[0-9]{4,8}" />
												</div><br>',
				'enter_otp'              => '<br><div class="mo2fa_text-align-center">
													<div style= "width:100%; margin: 0 auto;">
														<div  class="mo2f-otp-catchy-box items-center justify-center">
															<div class="mo2f-digit-group mo2f-otp-catchy-box" data-group-name="digits" data-autosubmit="false" autocomplete="off">
																' . $this->mo_catchy_field() . '
															</div>
														</div>
													</div>
													<br>
                                    			</div>
											<br>
											',
				'enter_answers'          => '<p class="mo2f_kba_alignments"> ' .
											esc_html( $kba_question1 ) . '
                                            <br>
                                            <br><input class="mo2f_otp_token mo2f_kba_input_fields" type="password" name="mo2f_answer_1" id="mo2f_answer_1" placeholder="' . esc_attr__( 'Enter Answer 1', 'miniorange-2-factor-authentication' ) . '"
                                                required="true" autofocus="true"
                                                pattern="(?=\S)[A-Za-z0-9_@.$#&amp;+\-\s]{1,100}"
                                                title="Only alphanumeric letters with special characters(_@.$#&amp;+-) are allowed."
                                                autocomplete="off"><br> <br>' . esc_html( $kba_question2 ) . '<br>
                                            <br><input class="mo2f_otp_token mo2f_kba_input_fields" type="password" name="mo2f_answer_2" id="mo2f_answer_2" placeholder="' . esc_attr__( 'Enter Answer 2', 'miniorange-2-factor-authentication' ) . '"
                                                required="true" pattern="(?=\S)[A-Za-z0-9_@.$#&amp;+\-\s]{1,100}"
                                                title="Only alphanumeric letters with special characters(_@.$#&amp;+-) are allowed."
                                                autocomplete="off">
                                    </p>',
				'resend_otp'             => '<span style="color:#1F618D;"></span><span><a href="#resend" style="color:#a7a7a8 ;text-decoration:none;">' . esc_html__( 'Resend OTP', 'miniorange-2-factor-authentication' ) . '</a></span>&nbsp;<br><br>',
				'validate_button_catchy' => '<input type="button" name="mo2f_catchy_validate" id="mo2f_catchy_validate" class="mo2f-save-settings-button mo2f_width_30" value="' . esc_attr__( 'Validate', 'miniorange-2-factor-authentication' ) . '"/><input type="button" name="mo2f-save-settings-button" id="mo2f_validate" class="hidden" />',
				'validate_button'        => ' <input type="button" name="mo2f-save-settings-button" id="mo2f_validate" class="mo2f-save-settings-button mo2f_width_30" value="' . esc_attr__( 'Validate', 'miniorange-2-factor-authentication' ) . '"/>',
				'backtologin'            => MoWpnsConstants::MO2F_USER_BLOCKED_PROMPT === $login_status ? 'mo2f_login_form' : 'mo2f_inline_form',
				'email_loader'           => '	<div id="showPushImage"><br>
				<div class="mo2fa_text-align-center">We are waiting for your approval...</div>
				                                <div class="mo2fa_text-align-center">
					                               <img src="' . esc_url( plugins_url( 'includes/images/email-loader.gif', __DIR__ ) ) . '"/>
											</div>',
				'use_backup_codes'       => 'test_2fa' === $validation_flow || ! in_array( 'mo2f_back_up_codes', $backup_methods, true ) ? '' : '<div> <a href="#mo2f_backup_option" class="mo2f_text_decoration">
                                     <p class="mo2f_footer_text">' . esc_html__( 'Use Backup Codes', 'miniorange-2-factor-authentication' ) . ' ' . MoWpnsConstants::MO2F_SVG_ARROW_ICON . '</p>
                                     </a>
                                    </div>',
				'send_backup_codes'      => 'test_2fa' === $validation_flow || ! in_array( 'mo2f_back_up_codes', $backup_methods, true ) || ! get_site_option( 'mo2f_enable_backup_methods' ) ? '' : '<div> <a href="#mo2f_backup_generate" class="mo2f_text_decoration">
                                         <p class="mo2f_footer_text">' . esc_html__( 'Send backup codes on email', 'miniorange-2-factor-authentication' ) . ' ' . MoWpnsConstants::MO2F_SVG_ARROW_ICON . '</p>
                                         </a>
                                    </div>',
				'backup_code_input'      => '<div id="mo2f_kba_content">
									<p style="font-size:15px;">
										<input class="mo2f-textbox" type="text" name="mo2f_backup_code" id="mo2f_backup_code" required="true" autofocus="true"  title="' . esc_attr__( 'Only alphanumeric letters with special characters(_@.$#&amp;+-) are allowed.', 'miniorange-2-factor-authentication' ) . '" autocomplete="off" ><br/>
									</p>
								</div>',
				'send_reconfig_link'     => 'test_2fa' === $validation_flow || ! in_array( 'mo2f_reconfig_link_show', $backup_methods, true ) || ! get_site_option( 'mo2f_enable_backup_methods' ) ? '' : '<div> <a href="#mo2f_send_reconfig_link" class="mo2f_text_decoration">
									<p class="mo2f_footer_text">' . esc_html__( 'Locked out? Click to recover your account using email verification', 'miniorange-2-factor-authentication' ) . ' ' . MoWpnsConstants::MO2F_SVG_ARROW_ICON . '</p>
									</a>
							   </div>',
				'kba_backup_method_link' => 'test_2fa' === $validation_flow || ! in_array( 'backup_kba', $backup_methods, true ) || ! get_site_option( 'mo2f_enable_backup_methods' ) || ! get_user_meta( $user_id, 'mo2f_backup_method_set', true ) || ! $common_helper->mo2f_is_2fa_set( $user_id ) ? '' : '<div> <a href="#kba_backup_method_link" class="mo2f_text_decoration">
							   <p class="mo2f_footer_text">' . esc_html__( 'Login with alternate 2FA method', 'miniorange-2-factor-authentication' ) . ' ' . MoWpnsConstants::MO2F_SVG_ARROW_ICON . '</p>
							   </a>
						</div>',
				'custom_logo'            => '<div class="mo2f-powerby-logo"><img
                                     alt="logo"  src="' . esc_url( plugins_url( 'includes/images/' . $custom_logo_enabled, __DIR__ ) ) . '"/></a></div>',
				'rba_consent'            => '<br><input type="button" name="miniorange_trust_device_yes" id="miniorange_trust_device_yes" class="mo2f-save-settings-button mr-mo-per-5 mo2f_width_30"  value="' . esc_attr__( 'Yes', 'miniorange-2-factor-authentication' ) . '"/>
						                    <input type="button" name="miniorange_trust_device_no" id="miniorange_trust_device_no" class="mo2f-reset-settings-button mo2f_width_30" value="' . esc_attr__( 'No', 'miniorange-2-factor-authentication' ) . '"/>',
				'rem_ip_consent'         => '<br><input type="button" name="mo2f_remember_ip_yes" id="mo2f_remember_ip_yes" class="mo2f-save-settings-button mr-mo-per-5 mo2f_width_30" value="' . esc_attr__( 'Yes', 'miniorange-2-factor-authentication' ) . '"/>
											<input type="button" name="mo2f_remember_ip_no" id="mo2f_remember_ip_no" class="mo2f-reset-settings-button mo2f_width_30" value="' . esc_attr__( 'No', 'miniorange-2-factor-authentication' ) . '"/>',
				'confirmation_consent'   => '<br><input type="button" name="mo2f_confirm_yes" id="mo2f_confirm_yes" class="mo2f-save-settings-button" style="margin-right:5%;" value="' . esc_attr__( 'Yes', 'miniorange-2-factor-authentication' ) . '"/>
						                    <input type="button" name="mo2f_confirm_no" id="mo2f_confirm_no" class="mo2f-reset-settings-button" value="' . esc_attr__( 'No', 'miniorange-2-factor-authentication' ) . '"/>
											<br><br>',

			);
			return $skeleton_blocks;
		}

		/**
		 * Retrieves the specific 2FA UI block structure based on the current login status.
		 *
		 * @param array  $skeleton_blocks An associative array of reusable HTML components for the 2FA UI.
		 * @param string $login_status Login status.
		 *
		 * @return array
		 */
		public function mo2f_fetch_login_status_block_skeleton( $skeleton_blocks, $login_status ) {
			$login_status_blocks = array(
				MoWpnsConstants::MO_2_FACTOR_CHALLENGE_OTP_OVER_EMAIL => array(
					'##attemptleft##'      => $skeleton_blocks['attempt_left'],
					'##enterotp##'         => $skeleton_blocks['enter_otp'],
					'##resendotp##'        => $skeleton_blocks['resend_otp'],
					'##validatebutton##'   => $skeleton_blocks['validate_button_catchy'],
					'##usebackupcodes##'   => $skeleton_blocks['use_backup_codes'],
					'##sendbackupcodes##'  => $skeleton_blocks['send_backup_codes'],
					'##sendreconfiglink##' => $skeleton_blocks['send_reconfig_link'],
					'##backupmethod##'     => $skeleton_blocks['kba_backup_method_link'],
				),
				MoWpnsConstants::MO_2_FACTOR_CHALLENGE_OOB_EMAIL => array(
					'##emailloader##'      => $skeleton_blocks['email_loader'],
					'##usebackupcodes##'   => $skeleton_blocks['use_backup_codes'],
					'##sendbackupcodes##'  => $skeleton_blocks['send_backup_codes'],
					'##sendreconfiglink##' => $skeleton_blocks['send_reconfig_link'],
					'##backupmethod##'     => $skeleton_blocks['kba_backup_method_link'],
				),
				MoWpnsConstants::MO_2_FACTOR_CHALLENGE_OTP_OVER_TELEGRAM => array(
					'##attemptleft##'      => $skeleton_blocks['attempt_left'],
					'##enterotp##'         => $skeleton_blocks['enter_otp'],
					'##resendotp##'        => $skeleton_blocks['resend_otp'],
					'##validatebutton##'   => $skeleton_blocks['validate_button_catchy'],
					'##usebackupcodes##'   => $skeleton_blocks['use_backup_codes'],
					'##sendbackupcodes##'  => $skeleton_blocks['send_backup_codes'],
					'##sendreconfiglink##' => $skeleton_blocks['send_reconfig_link'],
					'##backupmethod##'     => $skeleton_blocks['kba_backup_method_link'],
				),
				MoWpnsConstants::MO_2_FACTOR_CHALLENGE_GOOGLE_AUTHENTICATION => array(
					'##attemptleft##'      => $skeleton_blocks['attempt_left'],
					'##enterotp##'         => $skeleton_blocks['enter_otp'],
					'##validatebutton##'   => $skeleton_blocks['validate_button_catchy'],
					'##usebackupcodes##'   => $skeleton_blocks['use_backup_codes'],
					'##sendbackupcodes##'  => $skeleton_blocks['send_backup_codes'],
					'##sendreconfiglink##' => $skeleton_blocks['send_reconfig_link'],
					'##backupmethod##'     => $skeleton_blocks['kba_backup_method_link'],
				),
				MoWpnsConstants::MO_2_FACTOR_CHALLENGE_OTP_OVER_SMS => array(
					'##attemptleft##'      => $skeleton_blocks['attempt_left'],
					'##enterotp##'         => $skeleton_blocks['enter_otp_sms'],
					'##resendotp##'        => $skeleton_blocks['resend_otp'],
					'##validatebutton##'   => $skeleton_blocks['validate_button'],
					'##usebackupcodes##'   => $skeleton_blocks['use_backup_codes'],
					'##sendbackupcodes##'  => $skeleton_blocks['send_backup_codes'],
					'##sendreconfiglink##' => $skeleton_blocks['send_reconfig_link'],
					'##backupmethod##'     => $skeleton_blocks['kba_backup_method_link'],
				),
				MoWpnsConstants::MO_2_FACTOR_CHALLENGE_KBA_AUTHENTICATION => array(
					'##enteranswers##'     => $skeleton_blocks['enter_answers'],
					'##validatebutton##'   => $skeleton_blocks['validate_button'],
					'##usebackupcodes##'   => $skeleton_blocks['use_backup_codes'],
					'##sendbackupcodes##'  => $skeleton_blocks['send_backup_codes'],
					'##sendreconfiglink##' => $skeleton_blocks['send_reconfig_link'],
				),
				MoWpnsConstants::MO2F_ERROR_MESSAGE_PROMPT => array(
					'##usebackupcodes##'   => $skeleton_blocks['use_backup_codes'],
					'##sendbackupcodes##'  => $skeleton_blocks['send_backup_codes'],
					'##sendreconfiglink##' => $skeleton_blocks['send_reconfig_link'],
					'##backupmethod##'     => $skeleton_blocks['kba_backup_method_link'],
				),
				MoWpnsConstants::MO2F_USER_BLOCKED_PROMPT  => array(),
				MoWpnsConstants::MO_2_FACTOR_RECONFIGURATION_LINK_SENT => array(
					'##usebackupcodes##'  => $skeleton_blocks['use_backup_codes'],
					'##sendbackupcodes##' => $skeleton_blocks['send_backup_codes'],
				),
				MoWpnsConstants::MO_2_FACTOR_USE_BACKUP_CODES => array(
					'##enterbackupcode##' => $skeleton_blocks['backup_code_input'],
					'##validatebutton##'  => $skeleton_blocks['validate_button'],
					'##backupmethod##'    => $skeleton_blocks['kba_backup_method_link'],
				),
				MoWpnsConstants::MO2F_RBA_GET_USER_CONSENT => array(
					'##rbaconsent##' => $skeleton_blocks['rba_consent'],
				),
				MoWpnsConstants::MO2F_REMEMBER_IP_GET_USER_CONSENT => array(
					'##remipconsent##' => $skeleton_blocks['rem_ip_consent'],
				),
				MoWpnsConstants::MO_2_FACTOR_SHOW_CONFIRMATION_BLOCK => array(
					'##backtologin##'       => '',
					'##confirmationblock##' => $skeleton_blocks['confirmation_consent'],
				),
			);

			return $login_status_blocks[ $login_status ];
		}

		/**
		 * This function is used to generate input field in catchy popup template.
		 *
		 * @return mixed|string
		 */
		public function mo_catchy_field() {
			$input      = '<input type="text" id="mo2f-digit-1" class="mo2f-otp-catchy" maxlength="1" placeholder=" " data-next="mo2f-digit-2" />';
			$prev_field = 5;
			for ( $i = 2;$i <= 5;$i++ ) {
				$next  = $i + 1;
				$prev  = $i - 1;
				$input = $input . '<input type="text" id="mo2f-digit-' . $i . '" class="mo2f-otp-catchy" maxlength="1" placeholder=" "  data-next="mo2f-digit-' . $next . '"  data-previous="mo2f-digit-' . $prev . '" />';

			}
			$input = $input . '<input type="text" id="mo2f-digit-6" class="mo2f-otp-catchy" maxlength="1" placeholder=" " data-previous="mo2f-digit-' . $prev_field . '" />';
			$input = $input . '<input type="text" autocomplete="one-time-code" name="mo2fa_softtoken"
									placeholder="' . esc_attr__( 'Enter code', 'miniorange-2-factor-authentication' ) . '"
									id="mo2fa_softtoken" required="true" class="mo2f_otp_token hidden" autofocus="true" />';
			return $input;
		}

		/**
		 * Shows two factor authentication login prompt.
		 *
		 * @param string $login_status Login status.
		 * @param string $login_message Login message.
		 * @param string $redirect_to Redirection url.
		 * @param string $session_id_encrypt Session Id.
		 * @param array  $skeleton_values Skeleton values.
		 * @param string $twofa_method Twofa method.
		 * @param string $twofa_flow Twofa flow.
		 */
		public function mo2f_twofa_authentication_login_prompt( $login_status, $login_message, $redirect_to, $session_id_encrypt, $skeleton_values, $twofa_method, $twofa_flow = 'login_2fa' ) {
			echo '
			<html>
			<head>
			<meta http-equiv="X-UA-Compatible" content="IE=edge">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			';
			$common_helper = new Mo2f_Common_Helper();
			$common_helper->mo2f_echo_js_css_files();
			echo '
			</head>
			<body>
			<div class="mo2f_modal" tabindex="-1" role="dialog">
			<div class="mo2f-modal-backdrop"></div> <div class="mo_customer_validation-modal-dialog mo_customer_validation-modal-md">';
			$html  = $this->mo2f_get_twofa_skeleton_html( $login_status, $login_message, $redirect_to, $session_id_encrypt, $skeleton_values, $twofa_method, $twofa_flow );
			$html .= '</div></div></body></html>';
			$html .= $this->mo2f_get_validation_popup_script( $twofa_flow, $twofa_method, $redirect_to, $session_id_encrypt );
			return $html;
		}

		/**
		 * Gets 2fa validation popup script.
		 *
		 * @param string $twofa_flow Twofa flow.
		 * @param string $twofa_method Twofa method.
		 * @param string $redirect_to Redirection url.
		 * @param string $session_id_encrypt Session id.
		 * @return mixed
		 */
		public function mo2f_get_validation_popup_script( $twofa_flow, $twofa_method, $redirect_to, $session_id_encrypt ) {
			$common_helper = new Mo2f_Common_Helper();
			if ( 'login_2fa' === $twofa_flow ) {
				$resend_script = 'prompt_2fa_popup_login( twofa_method );';
			} else {
				$resend_script = 'prompt_2fa_popup_dashboard( twofa_method, "test" );';
			}
			$html  = '<script>
			var twofa_method = "' . esc_attr( $twofa_method ) . '";
			jQuery("a[href=\'#resend\']").click(function() {
				' . $resend_script . '
			});
			function mologinback(){
				jQuery("#mo2f_backto_mo_loginform").submit();
				jQuery("#mo2f_2fa_popup_dashboard").fadeOut();
				closeVerification = true;
			}';
			$html .= 'jQuery("input[name=mo2fa_softtoken]").keypress(function(e) {
				if (e.which === 13) {
					e.preventDefault();
					jQuery("#mo2f_validate").click();
					jQuery("input[name=otp_token]").focus();
				}

			});';
			$html .= "function prompt_2fa_popup_login(methodName) {
			'" . esc_js( $common_helper->mo2f_show_loader() ) . " '
				var data = {
					'action'                    : 'mo_two_factor_ajax',
					'mo_2f_two_factor_ajax'     : 'mo2f_resend_otp_login',
					'nonce'                     : '" . esc_js( wp_create_nonce( 'mo-two-factor-ajax-nonce' ) ) . "',
					'auth_method'               : methodName,
					'redirect_to'               : '" . esc_js( $redirect_to ) . "',
					'session_id'                : '" . esc_js( $session_id_encrypt ) . "',
				};
				var ajaxurl = '" . esc_js( admin_url( 'admin-ajax.php' ) ) . "';
				jQuery.ajax({
					url: ajaxurl,
					method: 'POST',
					data: data,
					dataType: 'json',
					success: function(response) {
					'" . esc_js( $common_helper->mo2f_hide_loader() ) . "'
						if (response.success) {
							mo2f_show_message(response.data);
						} else {
							mo2f_show_message(response.data);
						}
					},
					error: function (o, e, n) {
					},
				});
			}";
			$html .= 'jQuery(document).ready(function(jQuery) {
						jQuery(".mo2f-digit-group input").on("keyup", function (e) {
							const input = jQuery(this);
							const parent = input.parent();
							const inputVal = input.val();

							const ignoredKeys = [9, 16, 17, 18, 91];
							const arrowKeys = [37, 38, 39, 40];

							if (ignoredKeys.includes(e.keyCode) || arrowKeys.includes(e.keyCode)) {
								return;
							}

							if (e.keyCode === 8) {
								const prevId = input.data("previous");
								const prev = parent.find("input#" + prevId);
								if (prev.length) {
									prev.focus();
								}
								return;
							}

							if (inputVal.length === 1) {
								const nextId = input.data("next");
								const next = parent.find("input#" + nextId);
								if (next.length) {
									next.focus();
								}
							}
						});

						jQuery(".mo2f-digit-group input.mo2f-otp-catchy").on("paste", function(e) {
							e.preventDefault();
							const pasteData = e.originalEvent.clipboardData.getData("text").trim();
							const inputs = jQuery(".mo2f-digit-group input.mo2f-otp-catchy");

							if (pasteData.length === inputs.length) {
								for (let i = 0; i < inputs.length; i++) {
									inputs.eq(i).val(pasteData[i]);
								}
								jQuery("#mo2f_catchy_validate").click();
							} else {
								mo2f_show_message("Please paste the full 6-digit OTP.");
							}
						});


						var submitButton = jQuery("#mo2f_catchy_validate");
						if (submitButton.length) {
							submitButton.on("click", function(e) {
								var fieldstring = "";
								for (var i = 1; i <= 6; i++) {
									fieldstring += jQuery("#mo2f-digit-" + i).val();
								}
								jQuery("#mo2fa_softtoken").val(fieldstring);
								jQuery("#mo2f_validate").click();
							});

							jQuery("#mo2f-digit-6").on("keydown", function(e) {
								if (e.key === "Enter") {
									e.preventDefault();
									jQuery("#mo2f_catchy_validate").click();
								}
							});

						}
					});';
			$html .= "function mo2f_show_message(response) {
				var html = '<div id=\"mo2f-otpMessage\"><p class=\"mo2fa_display_message_frontend\">' + response + '</p></div>';
				jQuery('#mo2f-otpMessage').remove();
				jQuery('#mo2f-otpMessagehide').after(html);
			}</script>";
			return $html;
		}

		/**
		 * Shows two factor authentication skeleton values.
		 *
		 * @param string $login_status Login status.
		 * @param string $login_message Login message.
		 * @param string $redirect_to Redirection url.
		 * @param string $session_id_encrypt Session Id.
		 * @param array  $skeleton_values Skeleton values.
		 * @param string $twofa_method Twofa method.
		 * @param string $twofa_flow Twofa flow.
		 */
		public function mo2f_get_twofa_skeleton_html( $login_status, $login_message, $redirect_to, $session_id_encrypt, $skeleton_values, $twofa_method, $twofa_flow ) {
			$html                          = '<div id="mo2f_2fa_popup_dashboard_loader" class="modal" hidden></div>';
			$html                         .= '<div class="mo2f_setup_popup_dashboard">';
			$html                         .= '<div class="login mo_customer_validation-modal-content">
			<div class="mo2f_modal-header">
			<h4 class="mo2f_modal-title"><button type="button" class="mo2f_close" data-dismiss="modal" aria-label="Close" title="' . esc_attr__( 'Back to login', 'miniorange-2-factor-authentication' ) . '" onclick="mologinback();"><span aria-hidden="true">&times;</span></button>';
			$html                         .= esc_html( $skeleton_values['##mo2f_title##'] );
			$html                         .= '</h4>
			</div>
					<div class="mo2f_modal-body center">';
					$html                 .= '	<div id="mo2f-otpMessagehide" class="hidden">
					<p class="mo2fa_display_message_frontend" style="text-align: left !important; ">' . wp_kses( $login_message, array( 'b' => array() ) ) . '</p>
				</div>';
				$html                     .= '<div id="mo2f-otpMessage">
							<p class="mo2fa_display_message_frontend">';
						$html             .= wp_kses(
							$skeleton_values['##login_message##'],
							array(
								'b'      => array(),
								'br'     => array(),
								'a'      => array(
									'href'   => array(),
									'target' => array(),
								),
								'strong' => array(),
							)
						);
						$html             .= '
							
							</p>
						</div>';
						$html             .= wp_kses(
							$skeleton_values['##attemptleft##'],
							array(
								'b'    => array(),
								'br'   => array(),
								'span' => array(
									'style' => array(),
									'id'    => array(),
									'class' => array(),
								),
							)
						);
						$html             .= wp_kses(
							$skeleton_values['##emailloader##'],
							array(
								'div' => array(
									'id'    => array(),
									'class' => array(),
								),
								'img' => array(
									'src' => array(),
								),
								'br'  => array(),
							)
						);
						$html             .= '
						 <div id="showOTP">
								<div class="mo2f-login-container">
									<form name="f" id="mo2f_submitotp_loginform" method="post" class="' . esc_attr( ( 'MO2F_ERROR_MESSAGE_PROMPT' !== $login_status && 'MO_2_FACTOR_RECONFIGURATION_LINK_SENT' !== $login_status && 'MO2F_USER_BLOCKED_PROMPT' !== $login_status && 'MO_2_FACTOR_CHALLENGE_OOB_EMAIL' !== $login_status ) ? 'mo2f_login_form_border' : '' ) . '"> ';
									$html .= wp_kses(
										$skeleton_values['##enterotp##'],
										array(
											'div'   => array(
												'class' => array(),
											),
											'input' => array(
												'type'     => array(),
												'name'     => array(),
												'style'    => array(),
												'placeholder' => array(),
												'id'       => array(),
												'required' => array(),
												'class'    => array(),
												'autofocus' => array(),
												'pattern'  => array(),
												'title'    => array(),
												'maxlength' => array(),
												'data-previous' => array(),
												'data-next' => array(),
											),
											'br'    => array(),

										)
									);
									$html .= wp_kses(
										$skeleton_values['##enterbackupcode##'],
										array(
											'div'   => array(),
											'p'     => array(
												'style' => array(),
											),
											'input' => array(
												'type'     => array(),
												'name'     => array(),
												'style'    => array(),
												'placeholder' => array(),
												'id'       => array(),
												'required' => array(),
												'class'    => array(),
												'autofocus' => array(),
												'pattern'  => array(),
												'title'    => array(),
												'autocomplete' => array(),

											),
											'br'    => array(),

										)
									);
									$html .= wp_kses(
										$skeleton_values['##enteranswers##'],
										array(
											'p'     => array(
												'style' => array(),
												'class' => array(),
											),
											'br'    => array(),
											'input' => array(
												'type'     => array(),
												'name'     => array(),
												'style'    => array(),
												'placeholder' => array(),
												'id'       => array(),
												'required' => array(),
												'class'    => array(),
												'autofocus' => array(),
												'pattern'  => array(),
												'title'    => array(),
												'autocomplete' => array(),

											),

										)
									);
									$html .= wp_kses(
										$skeleton_values['##resendotp##'],
										array(
											'span' => array(
												'style' => array(),
											),
											'br'   => array(),
											'a'    => array(
												'href'  => array(),
												'style' => array(),

											),
											'u'    => array(),

										)
									);
									$html .= wp_kses(
										$skeleton_values['##validatebutton##'],
										array(
											'br'    => array(),
											'input' => array(
												'type'  => array(),
												'name'  => array(),
												'value' => array(),
												'id'    => array(),
												'class' => array(),
											),

										)
									);
									$html .= wp_kses(
										$skeleton_values['##rbaconsent##'],
										array(
											'br'    => array(),
											'input' => array(
												'type'  => array(),
												'name'  => array(),
												'value' => array(),
												'id'    => array(),
												'class' => array(),
												'style' => array(),
											),

										)
									);
									$html .= wp_kses(
										$skeleton_values['##remipconsent##'],
										array(
											'br'    => array(),
											'input' => array(
												'type'  => array(),
												'name'  => array(),
												'value' => array(),
												'id'    => array(),
												'class' => array(),
												'style' => array(),
											),

										)
									);
									$html .= wp_kses(
										$skeleton_values['##confirmationblock##'],
										array(
											'br'    => array(),
											'input' => array(
												'type'  => array(),
												'name'  => array(),
												'value' => array(),
												'id'    => array(),
												'class' => array(),
												'style' => array(),
											),

										)
									);

									$html        .= '
									<input type="hidden" name="request_origin_method" value="' . esc_attr( $login_status ) . '"/>
                                    <input type="hidden" name="mo2f_login_method" value="' . esc_attr( $twofa_method ) . '"/>
									<input type="hidden" name="miniorange_inline_save_2factor_method_nonce" value="' . esc_attr( wp_create_nonce( 'miniorange-2-factor-inline-save-2factor-method-nonce' ) ) . '"/>
                                    <input type="hidden" name="option" value="mo2f_validate_user_for_login">
									<input type="hidden" name="redirect_to" value="' . esc_url( $redirect_to ) . '"/>
									<input type="hidden" name="session_id" value="' . esc_attr( $session_id_encrypt ) . '"/>
									</form>';
									$allowed_html = array(
										'a'    => array(
											'href'  => array(),
											'style' => array(),
											'class' => array(),
										),
										'p'    => array(
											'style' => array(),
											'class' => array(),
										),
										'svg'  => array(
											'class'       => array(),
											'xmlns'       => array(),
											'width'       => array(),
											'height'      => array(),
											'viewbox'     => array(),
											'fill'        => array(),
											'aria-hidden' => array(),
											'focusable'   => array(),
										),
										'path' => array(
											'd' => array(),
										),
									);
									$placeholders = array(
										'##sendbackupcodes##',
										'##usebackupcodes##',
										'##sendreconfiglink##',
										'##backupmethod##',
									);
									foreach ( $placeholders as $placeholder ) {
										if ( isset( $skeleton_values[ $placeholder ] ) ) {
											$html .= wp_kses( $skeleton_values[ $placeholder ], $allowed_html );
										}
									}

									$html .= '
                                      

								</div>
                                
						 </div> ';
									if ( 'login_2fa' === $twofa_flow ) {
										$common_helper = new Mo2f_Common_Helper();
										$html         .= $common_helper->mo2f_go_back_link_form( $skeleton_values['##backtologin##'] );
										$resend_script = 'prompt_2fa_popup_login( twofa_method );';
									} else {
										$resend_script = 'prompt_2fa_popup_dashboard( twofa_method, "test" );';
									}

									$html .= wp_kses(
										$skeleton_values['##customlogo##'],
										array(

											'div' => array(
												'class' => array(),

											),
											'a'   => array(
												'target' => array(),
												'href'   => array(),

											),
											'img' => array(
												'alt' => array(),
												'src' => array(),

											),

										)
									);

					$html .= '
                    </div>


				</div>
               
			</div>';
			return $html;
		}

		/**
		 * It will help to display the email verification
		 *
		 * @param array $popup_args Popup args.
		 * @return void
		 */
		public function mo2f_display_email_verification( $popup_args ) {
			echo "<div style='" . esc_attr( $popup_args['branding_img'] ) . " height: 790px;'>
				<div style='height: 710px; display: flex; align-items: center; justify-content: center;'>
					<div style='background-color: " . esc_attr( $popup_args['bg_color'] ) . "; border-radius: 5px; padding: 2%; width: 850px; height: 350px; box-shadow: 0 5px 15px rgba(0,0,0,.5); align-self: center; margin: 180px auto;'>
						<img alt='logo' style='margin-left: 400px; margin-top: 10px;' src='" . esc_url( ( $popup_args['logo_url'] ) ) . "'>
						<div><hr></div>
						<div style='text-align: center;'>
							<h1 style='color:" . esc_attr( $popup_args['color'] ) . "; text-align: center; font-size: 50px;'>" . esc_attr( $popup_args['head'] ) . "</h1>
							<h2 style='text-align: center; margin-top: 20px;'>" . esc_html( $popup_args['body'] ) . '</h2>
						</div>
					</div>
				</div>
			</div>';
		}

		/**
		 * Prompts mfa form for users.
		 *
		 * @param array  $configure_array_method array of methods.
		 * @param string $session_id_encrypt encrypted session id.
		 * @param string $redirect_to redirect to url.
		 * @return void
		 */
		public function mo2fa_prompt_mfa_form_for_user( $configure_array_method, $session_id_encrypt, $redirect_to ) {
			?>
	<html>
			<head>
				<meta charset="utf-8"/>
				<meta http-equiv="X-UA-Compatible" content="IE=edge">
				<meta name="viewport" content="width=device-width, initial-scale=1">
				<?php
				$common_helper = new Mo2f_Common_Helper();
				$common_helper->mo2f_inline_css_and_js();
				?>
			</head>
			<body>
				<div class="mo2f_modal1" tabindex="-1" role="dialog" id="myModal51">
					<div class="mo2f-modal-backdrop"></div>
					<div class="mo_customer_validation-modal-dialog mo_customer_validation-modal-md">
						<div class="login mo_customer_validation-modal-content">
							<div class="mo2f_modal-header">
								<h3 class="mo2f_modal-title"><button type="button" class="mo2f_close" data-dismiss="modal" aria-label="Close" title="<?php esc_attr_e( 'Back to login', 'miniorange-2-factor-authentication' ); ?>" onclick="mologinback();"><span aria-hidden="true">&times;</span></button>

								<?php esc_html_e( 'Select 2 Factor method for authentication', 'miniorange-2-factor-authentication' ); ?></h3>
							</div>
							<div class="mo2f_modal-body">
									<?php
									foreach ( $configure_array_method as $key => $value ) {
										echo '<span  >
                                    		<label>
                                    			<input type="radio"  name="mo2f_selected_mfactor_method" class ="mo2f-styled-radio_conf" value="' . esc_html( $value ) . '"/>';
												echo '<span class="mo2f-styled-radio-text_conf">';
												echo esc_html( MoWpnsConstants::mo2f_convert_method_name( $value, 'cap_to_small' ) );
											echo ' </span> </label>
                                			<br>
                                			<br>
                                		</span>';

									}
									$common_helper = new Mo2f_Common_Helper();
									echo wp_kses(
										$common_helper->mo2f_customize_logo(),
										array(
											'div' => array(
												'style' => array(),
											),
											'img' => array(
												'alt' => array(),
												'src' => array(),
											),
										)
									);
									?>
							</div>
						</div>
					</div>
				</div>
			<?php
			echo wp_kses(
				$common_helper->mo2f_backto_login_form(),
				array(
					'form' => array(
						'name'   => array(),
						'id'     => array(),
						'method' => array(),
						'action' => array(),
						'class'  => array(),
					),
				)
			);
			?>
				<form name="f" method="post" action="" id="mo2f_select_mfa_methods_form" style="display:none;">
					<input type="hidden" name="mo2f_selected_mfactor_method" />
					<input type="hidden" name="miniorange_inline_save_2factor_method_nonce" value="<?php echo esc_attr( wp_create_nonce( 'miniorange-2-factor-inline-save-2factor-method-nonce' ) ); ?>" />
					<input type="hidden" name="option" value="miniorange_mfactor_method" />
					<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_to ); ?>"/>
					<input type="hidden" name="session_id" value="<?php echo esc_attr( $session_id_encrypt ); ?>"/>
				</form>
			<script>
				function mologinback(){
					jQuery('#mo2f_backto_mo_loginform').submit();
				}
				jQuery('input:radio[name=mo2f_selected_mfactor_method]').click(function() {
					var selectedMethod = jQuery(this).val();
					document.getElementById("mo2f_select_mfa_methods_form").elements[0].value = selectedMethod;
					jQuery('#mo2f_select_mfa_methods_form').submit();
				});				
			</script>
			</body>
		</html>
				<?php
		}

		/**
		 * Show login popup for email.
		 *
		 * @param string $mo2fa_login_message Login message.
		 * @param string $mo2fa_login_status Login status.
		 * @param object $current_user Current user.
		 * @param string $redirect_to Redirection url.
		 * @param string $session_id_encrypt Session ID.
		 * @param string $twofa_method Twofa Method.
		 * @param array  $kba_questions KBA questions.
		 * @return void
		 */
		public function mo2f_show_login_prompt_for_otp_based_methods( $mo2fa_login_message, $mo2fa_login_status, $current_user, $redirect_to, $session_id_encrypt, $twofa_method, $kba_questions = null ) {
			$common_helper   = new Mo2f_Common_Helper();
			$skeleton_values = $this->mo2f_twofa_login_prompt_skeleton_values( $mo2fa_login_message, $mo2fa_login_status, isset( $kba_questions[0] ) ? $kba_questions[0] : null, isset( $kba_questions[1] ) ? $kba_questions[1] : null, $current_user->ID, 'login_2fa', '', $session_id_encrypt );
			$html            = $this->mo2f_twofa_authentication_login_prompt( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, $session_id_encrypt, $skeleton_values, $twofa_method );
			$html           .= $common_helper->mo2f_get_hidden_forms_login( $redirect_to, $session_id_encrypt, $mo2fa_login_status, $mo2fa_login_message, $twofa_method, $current_user->ID );
			$html           .= $common_helper->mo2f_get_login_script( $twofa_method );
			$html           .= $common_helper->mo2f_get_hidden_script_login();
			echo $html;// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already escaped the necessary in the definition.
			exit;
		}
	}
}
