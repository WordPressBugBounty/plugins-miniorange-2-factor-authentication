<?php
/**
 * This file contains functions related to the PASSKEY (WebAuthn/FIDO2) method.
 *
 * @package miniOrange-2-factor-authentication/handler
 */

namespace TwoFA\Handler\TwofaMethods;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Helper\MoWpnsMessages;
use TwoFA\Handler\Twofa\MO2f_Utility;
use TwoFA\Helper\Mo2f_Inline_Popup;
use TwoFA\Helper\Mo2f_Login_Popup;
use TwoFA\Helper\Mo2f_Common_Helper;
use TwoFA\Traits\Instance;

if ( PHP_VERSION_ID >= MoWpnsConstants::MO2F_PASSKEY_MIN_PHP_VERSION_ID && file_exists( dirname( dirname( __DIR__ ) ) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'webauthn' . DIRECTORY_SEPARATOR . 'WebAuthn.php' ) ) {
	require_once dirname( dirname( __DIR__ ) ) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'webauthn' . DIRECTORY_SEPARATOR . 'WebAuthn.php';
}

if ( ! class_exists( 'Mo2f_PASSKEY_Handler' ) ) {
	/**
	 * Class Mo2f_PASSKEY_Handler
	 */
	class Mo2f_PASSKEY_Handler {

		use Instance;

		/**
		 * Current Method.
		 *
		 * @var string
		 */
		private $mo2f_current_method;

		/**
		 * Usermeta key under which the array of registered passkey credentials is stored.
		 *
		 * @var string
		 */
		const CREDENTIALS_META_KEY = 'mo2f_passkey_credentials';

		/**
		 * Class Mo2f_PASSKEY_Handler constructor
		 */
		public function __construct() {
			$this->mo2f_current_method = MoWpnsConstants::PASSKEY;
		}

		/**
		 * Whether the vendored WebAuthn library is present. Passkey is hidden/disabled
		 * gracefully (rather than fataling) if a site admin hasn't dropped it in yet.
		 *
		 * @return bool
		 */
		public function mo2f_is_available() {
			return class_exists( '\lbuchs\WebAuthn\WebAuthn' );
		}

		/**
		 * Explains why Passkey is unavailable, distinguishing the PHP-too-old
		 * case (a hard, permanent constraint for that site) from the
		 * library-not-vendored-yet case (a transient admin setup step), so
		 * the user sees an accurate, actionable message instead of one
		 * generic guess.
		 *
		 * @return string
		 */
		private function mo2f_get_unavailable_reason() {
			if ( PHP_VERSION_ID < MoWpnsConstants::MO2F_PASSKEY_MIN_PHP_VERSION_ID ) {
				return __( 'Passkeys require PHP 7.4 or higher. This site is running an older PHP version, so this feature is unavailable here.', 'miniorange-2-factor-authentication' );
			}
			return __( 'Passkey support is being set up for this site. Please check back shortly.', 'miniorange-2-factor-authentication' );
		}

		/**
		 * Builds a configured WebAuthn server instance for this site.
		 *
		 * @return \lbuchs\WebAuthn\WebAuthn
		 */
		private function mo2f_get_webauthn() {
			// Fourth arg forces base64url encoding of binary fields (challenge, credential
			// ids, etc.) in the library's JSON output - it defaults to false (a different,
			// non-base64url wire format), which would silently break the client-side JS.
			return new \lbuchs\WebAuthn\WebAuthn( get_bloginfo( 'name' ), $this->mo2f_get_relying_party_id(), null, true );
		}

		/**
		 * The WebAuthn Relying Party ID - the site's host, without scheme or port.
		 *
		 * @return string
		 */
		private function mo2f_get_relying_party_id() {
			return wp_parse_url( site_url(), PHP_URL_HOST );
		}

		/**
		 * Whether WebAuthn can work on this site: HTTPS, or localhost/127.0.0.1 during
		 * development (browsers treat those as secure contexts even over plain HTTP).
		 *
		 * @return bool
		 */
		private function mo2f_is_secure_context() {
			if ( is_ssl() ) {
				return true;
			}
			$host = $this->mo2f_get_relying_party_id();
			return in_array( $host, array( 'localhost', '127.0.0.1', '::1' ), true );
		}

		/**
		 * Base64url-decodes a string into a raw binary string.
		 *
		 * @param string $data Base64url-encoded string.
		 * @return string
		 */
		private static function mo2f_base64url_decode( $data ) {
			$padded = str_pad( (string) $data, strlen( (string) $data ) % 4 === 0 ? strlen( (string) $data ) : strlen( (string) $data ) + ( 4 - strlen( (string) $data ) % 4 ), '=' );
			return base64_decode( strtr( $padded, '-_', '+/' ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- WebAuthn wire format requires raw base64url decoding.
		}

		/**
		 * Base64url-encodes a raw binary string.
		 *
		 * @param string $data Raw binary string.
		 * @return string
		 */
		private static function mo2f_base64url_encode( $data ) {
			return rtrim( strtr( base64_encode( (string) $data ), '+/', '-_' ), '=' );
		}

		/**
		 * Gets a user's registered passkey credential records.
		 *
		 * @param int $user_id User ID.
		 * @return array
		 */
		private function mo2f_get_user_credentials( $user_id ) {
			$credentials = get_user_meta( $user_id, self::CREDENTIALS_META_KEY, true );
			return is_array( $credentials ) ? $credentials : array();
		}

		/**
		 * Saves a user's registered passkey credential records.
		 *
		 * @param int   $user_id User ID.
		 * @param array $credentials Credential records.
		 * @return void
		 */
		private function mo2f_save_user_credentials( $user_id, $credentials ) {
			update_user_meta( $user_id, self::CREDENTIALS_META_KEY, $credentials );
		}

		/**
		 * Resolves the user a Passkey AJAX request is acting on - either the logged-in
		 * dashboard user, or (during a login challenge / inline setup) the user tied to
		 * the password-validated session transient.
		 *
		 * @param string $session_id Session id.
		 * @return int|null
		 */
		private function mo2f_resolve_user_id( $session_id ) {
			if ( is_user_logged_in() ) {
				return get_current_user_id();
			}
			$common_helper = new Mo2f_Common_Helper();
			return $common_helper->mo2f_get_current_user_id( $session_id );
		}

		/**
		 * Show Passkey configuration prompt inline (during forced first-login setup).
		 *
		 * @param string $session_id Session ID.
		 * @param string $redirect_to Redirection url.
		 * @param int    $current_user_id Current user ID.
		 * @param string $mo2fa_login_message Login message.
		 * @return void
		 */
		public function mo2f_prompt_2fa_setup_inline( $session_id, $redirect_to, $current_user_id, $mo2fa_login_message ) {
			$common_helper = new Mo2f_Common_Helper();
			$inline_helper = new Mo2f_Inline_Popup();
			$common_helper->mo2f_inline_css_and_js();
			$html  = '<html><head><meta charset="utf-8"/>
			<meta http-equiv="X-UA-Compatible" content="IE=edge">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			</head><body>';
			$html .= '<div class="mo2f_modal" tabindex="-1" role="dialog">
			<div class="mo2f-modal-backdrop"></div>
			<div class="mo_customer_validation-modal-dialog mo_customer_validation-modal-md">
			<div class="login mo_customer_validation-modal-content">
			<div class="mo2f_modal-body">' . $this->mo2f_get_credential_management_html( $current_user_id ) . '</div>
			</div></div></div>';
			$html .= $inline_helper->mo2f_get_inline_hidden_forms( $redirect_to, $session_id, $current_user_id );
			$html .= $this->mo2f_get_script( 'inline', $session_id, $redirect_to );
			echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already escaped the necessary in the definition.
			exit;
		}

		/**
		 * Show Passkey configuration prompt on the My Account dashboard.
		 *
		 * @param string $session_id_encrypt Session id.
		 * @return void
		 */
		public function mo2f_prompt_2fa_setup_dashboard( $session_id_encrypt ) {
			$current_user  = wp_get_current_user();
			$common_helper = new Mo2f_Common_Helper();
			// Mirrors Mo2f_Common_Helper::mo2f_google_authenticator_popup_common_html()'s
			// dashboard-flow wrapper exactly: the outer #mo2f_2fa_popup_dashboard container
			// this gets injected into is just a bare `.modal` div (see
			// controllers/main-controller.php) with no built-in centering/backdrop of its
			// own - every method's returned HTML must supply that structure itself, or (as
			// happened before this fix) the content renders unstyled, wherever `.modal`'s
			// bare CSS happens to place it.
			$html  = '<div class="mo2f_modal" tabindex="-1" role="dialog" id="mo2f_passkey_modal">
			<div class="mo2f_modal-dialog mo2f_modal-lg">
			<div class="login mo_customer_validation-modal-content">
			<h4 class="mo2f_modal-title"><button type="button" class="mo2f_close" data-dismiss="modal" aria-label="Close" title="' . esc_attr__( 'Close', 'miniorange-2-factor-authentication' ) . '" onclick="mologinback();"><span aria-hidden="true">&times;</span></button>'
				. esc_html__( 'Configure Passkey', 'miniorange-2-factor-authentication' ) . '</h4>
			<hr>';
			$html .= $this->mo2f_get_credential_management_html( $current_user->ID );
			$html .= '</div></div></div>';
			$html .= $common_helper->mo2f_get_dashboard_hidden_forms();
			$html .= $this->mo2f_get_script( 'dashboard', $session_id_encrypt, '' );
			wp_send_json_success( $html );
		}

		/**
		 * Show Passkey test prompt on the dashboard "Test" screen.
		 *
		 * @param string $session_id_encrypt Session id.
		 * @return void
		 */
		public function mo2f_prompt_2fa_test_dashboard( $session_id_encrypt ) {
			$current_user        = wp_get_current_user();
			$mo2fa_login_message = __( 'Use your fingerprint, face, or security key to sign in.', 'miniorange-2-factor-authentication' );
			$mo2fa_login_status  = MoWpnsConstants::MO_2_FACTOR_CHALLENGE_PASSKEY_AUTHENTICATION;
			$login_popup         = new Mo2f_Login_Popup();
			$common_helper       = new Mo2f_Common_Helper();
			$skeleton_values     = $login_popup->mo2f_twofa_login_prompt_skeleton_values( $mo2fa_login_message, $mo2fa_login_status, null, null, $current_user->ID, 'test_2fa', '', $session_id_encrypt );
			$html                = $login_popup->mo2f_get_twofa_skeleton_html( $mo2fa_login_status, $mo2fa_login_message, '', $session_id_encrypt, $skeleton_values, $this->mo2f_current_method, 'test_2fa' );
			$html               .= $this->mo2f_get_script( 'test_2fa', $session_id_encrypt, '' );
			$html               .= $common_helper->mo2f_get_test_script();
			wp_send_json_success( $html );
		}

		/**
		 * Renders the credential list + "Add a Passkey" UI shown in the dashboard/inline
		 * configuration modal.
		 *
		 * @param int $user_id User id.
		 * @return string
		 */
		private function mo2f_get_credential_management_html( $user_id ) {
			if ( ! $this->mo2f_is_available() ) {
				return '<h3>' . esc_html__( 'Passkeys', 'miniorange-2-factor-authentication' ) . '</h3><p>'
					. esc_html( $this->mo2f_get_unavailable_reason() ) . '</p>';
			}
			$html = '<h3>' . esc_html__( 'Passkeys', 'miniorange-2-factor-authentication' ) . '</h3>';
			if ( ! $this->mo2f_is_secure_context() ) {
				$html .= '<p class="mo2f_error_message">' . esc_html__( 'Passkeys require this site to be served over HTTPS (localhost is exempt during development). Please contact your site administrator.', 'miniorange-2-factor-authentication' ) . '</p>';
			}
			$credentials = $this->mo2f_get_user_credentials( $user_id );
			if ( empty( $credentials ) ) {
				$html .= '<p>' . esc_html__( 'No passkeys registered yet. Add one using your fingerprint, face, or a security key.', 'miniorange-2-factor-authentication' ) . '</p>';
			} else {
				$html .= '<ul class="mo2f_passkey_list" style="list-style:none;padding:0;margin:0 0 16px;">';
				foreach ( $credentials as $credential ) {
					$nickname = ! empty( $credential['nickname'] ) ? $credential['nickname'] : __( 'Passkey', 'miniorange-2-factor-authentication' );
					$html    .= '<li class="mo2f_passkey_list_item" style="display:flex;align-items:center;justify-content:space-between;gap:16px;padding:10px 0;border-bottom:1px solid #e2e2e2;">'
						. '<span>' . esc_html( $nickname ) . '</span>'
						. '<button type="button" class="mo2f-reset-settings-button" style="flex-shrink:0;" onclick="mo2fPasskeyDeleteCredential(\'' . esc_js( $credential['credential_id'] ) . '\', this)">'
						. esc_html__( 'Remove', 'miniorange-2-factor-authentication' ) . '</button>'
						. '</li>';
				}
				$html .= '</ul>';
			}
			$html .= '<button type="button" id="mo2f_passkey_add_btn" class="mo2f-save-settings-button" style="margin-top:8px;" onclick="mo2fPasskeyStartRegistration()">'
				. esc_html__( 'Add a Passkey', 'miniorange-2-factor-authentication' ) . '</button>';
			return $html;
		}

		/**
		 * Prints the mo2f-passkey.js config + enqueues the script for the given flow.
		 *
		 * @param string $twofa_flow Twofa flow ('inline'|'dashboard'|'test_2fa'|'login_2fa').
		 * @param string $session_id Session id.
		 * @param string $redirect_to Redirection to.
		 * @return string
		 */
		public function mo2f_get_script( $twofa_flow, $session_id, $redirect_to ) {
			$passkey_js_path = dirname( __DIR__, 2 ) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'mo2f-passkey.js';
			// Use the file's own mtime (not MO2F_VERSION) as the cache-busting query arg -
			// this file is actively iterated on and MO2F_VERSION only changes on a plugin
			// release, so browsers/proxies would otherwise keep serving a stale copy.
			$passkey_js_ver = file_exists( $passkey_js_path ) ? filemtime( $passkey_js_path ) : MO2F_VERSION;
			// No 'jquery' dependency here on purpose: jQuery is already guaranteed to be
			// printed, in the correct <head> position, by mo2f_echo_js_css_files() (login/
			// test flows) or mo2f_inline_css_and_js() (inline/dashboard flows) before this
			// runs. Declaring it as a dependency would make wp_print_scripts() below print
			// jQuery's tag immediately into THIS buffer instead - which for the login flow
			// runs before mo2f_echo_js_css_files(), stranding jQuery's tag at the bottom of
			// the page (after Bootstrap and everything else that needs it in <head>).
			wp_register_script( 'mo2f_passkey_js', plugins_url( '/includes/js/mo2f-passkey.js', dirname( __DIR__ ) ), array(), $passkey_js_ver, false );
			ob_start();
			wp_print_scripts( 'mo2f_passkey_js' );
			$script_tag = ob_get_clean();
			// The login_2fa/test_2fa flows already get a `mologinback()` global defined by
			// Mo2f_Login_Popup::mo2f_get_validation_popup_script() (called earlier in that
			// page's build). The dashboard modal's close button uses the same onclick by
			// convention (matching every other method), but nothing else defines it there -
			// so it must be (re)defined here, identical to
			// Mo2f_Common_Helper::mo2f_google_authenticator_popup_common_html()'s own copy.
			$mologinback_def = 'dashboard' === $twofa_flow
				? 'function mologinback() { jQuery("#mo2f_backto_mo_loginform").submit(); jQuery("#mo2f_2fa_popup_dashboard").fadeOut(); }'
				: '';
			$config = '<script>
			var mo2fPasskeyConfig = {
				ajaxUrl: "' . esc_js( admin_url( 'admin-ajax.php' ) ) . '",
				nonce: "' . esc_js( wp_create_nonce( 'mo-two-factor-ajax-nonce' ) ) . '",
				sessionId: "' . esc_js( $session_id ) . '",
				redirectTo: "' . esc_js( $redirect_to ) . '",
				flow: "' . esc_js( $twofa_flow ) . '",
			};
			' . $mologinback_def . '
			jQuery(document).ready(function($) {
				$("#mo2f_passkey_authenticate_btn").click(function() {
					mo2fPasskeyStartAuthentication();
				});
				if (typeof mo2fPasskeySupported === "function" && ! mo2fPasskeySupported()) {
					$("#mo2f_passkey_authenticate_btn").prop("disabled", true);
				}
			});
			</script>';
			return $script_tag . $config;
		}

		/**
		 * AJAX: builds WebAuthn registration ("create") options for the current user
		 * and stashes the challenge for the follow-up verification step.
		 *
		 * @param array $post Post data.
		 * @return void
		 */
		public function mo2f_get_registration_options( $post ) {
			if ( ! $this->mo2f_is_available() ) {
				wp_send_json_error( $this->mo2f_get_unavailable_reason() );
				return;
			}
			$session_id = isset( $post['mo2f_session_id'] ) ? sanitize_text_field( wp_unslash( $post['mo2f_session_id'] ) ) : '';
			$user_id    = $this->mo2f_resolve_user_id( $session_id );
			$user       = $user_id ? get_user_by( 'id', $user_id ) : null;
			MO2f_Utility::mo2f_debug_file( 'Passkey get_registration_options - is_user_logged_in=' . ( is_user_logged_in() ? '1' : '0' ) . ' session_id=' . ( $session_id ? substr( $session_id, 0, 12 ) . '...' : '(empty)' ) . ' resolved_user_id=' . ( $user_id ? $user_id : '(none)' ) . ' post_keys=' . implode( ',', array_keys( (array) $post ) ) );
			if ( ! $user ) {
				wp_send_json_error( __( 'Unauthorized.', 'miniorange-2-factor-authentication' ) );
				return;
			}
			$existing_credentials = $this->mo2f_get_user_credentials( $user->ID );
			$exclude_ids          = array_map(
				function ( $credential ) {
					return \lbuchs\WebAuthn\Binary\ByteBuffer::fromBase64Url( $credential['credential_id'] );
				},
				$existing_credentials
			);
			try {
				$webauthn    = $this->mo2f_get_webauthn();
				$create_args = $webauthn->getCreateArgs( (string) $user->ID, $user->user_login, $user->display_name, 60, false, false, null, $exclude_ids );
			} catch ( \Throwable $e ) {
				MO2f_Utility::mo2f_debug_file(
					'Passkey registration options error - User_Id-' . $user->ID
					. ' ExceptionClass=' . get_class( $e ) . ' Code=' . $e->getCode() . ' Error-' . $e->getMessage()
				);
				wp_send_json_error( __( 'Could not start passkey registration. Please try again.', 'miniorange-2-factor-authentication' ) );
				return;
			}
			$options = json_decode( wp_json_encode( $create_args ), true );
			if ( empty( $options['publicKey']['challenge'] ) ) {
				wp_send_json_error( __( 'Could not start passkey registration. Please try again.', 'miniorange-2-factor-authentication' ) );
				return;
			}
			MO2f_Utility::mo2f_debug_file(
				'Passkey get_registration_options - options built OK for User_Id-' . $user->ID
				. ' rpId=' . $this->mo2f_get_relying_party_id() . ' excluded_credentials=' . count( $exclude_ids )
			);
			set_transient( $session_id . 'mo2f_passkey_reg_challenge', $options['publicKey']['challenge'], 300 );
			wp_send_json_success( $options );
		}

		/**
		 * Validates a Passkey registration/attestation response submitted during setup.
		 *
		 * @param string $otp_token Serialized WebAuthn attestation response (JSON).
		 * @param string $session_id_encrypt Session id.
		 * @param array  $user_details User details.
		 * @param string $prev_input Prev input.
		 * @param array  $post Post data.
		 * @return void
		 */
		public function mo2f_validate_otp( $otp_token, $session_id_encrypt, $user_details, $prev_input, $post ) {
			global $mo2f_onprem_cloud_obj;
			if ( ! $this->mo2f_is_available() ) {
				wp_send_json_error( $this->mo2f_get_unavailable_reason() );
				return;
			}
			$user_id = isset( $user_details['user_id'] ) ? $user_details['user_id'] : null;
			$user    = $user_id ? get_user_by( 'id', $user_id ) : null;
			MO2f_Utility::mo2f_debug_file( 'Passkey validate_otp (registration verify) - user_details_user_id=' . ( $user_id ? $user_id : '(none)' ) . ' login_status=' . ( isset( $user_details['login_status'] ) ? $user_details['login_status'] : '(none)' ) );
			if ( ! $user ) {
				MO2f_Utility::mo2f_debug_file( 'Passkey validate_otp - no user resolved from user_details, rejecting.' );
				wp_send_json_error( MoWpnsMessages::mo2f_get_message( MoWpnsMessages::ERROR_WHILE_VALIDATING_OTP ) );
				return;
			}
			$credential = json_decode( $otp_token, true );
			if ( empty( $credential['response']['clientDataJSON'] ) || empty( $credential['response']['attestationObject'] ) ) {
				MO2f_Utility::mo2f_debug_file( 'Passkey validate_otp - malformed attestation payload for User_Id-' . $user->ID . ' json_last_error=' . json_last_error_msg() );
				wp_send_json_error( __( 'Invalid passkey response.', 'miniorange-2-factor-authentication' ) );
				return;
			}
			$challenge_b64url = get_transient( $session_id_encrypt . 'mo2f_passkey_reg_challenge' );
			if ( empty( $challenge_b64url ) ) {
				MO2f_Utility::mo2f_debug_file( 'Passkey validate_otp - no stored challenge transient found (expired or session_id mismatch) for User_Id-' . $user->ID );
				wp_send_json_error( __( 'This passkey registration request has expired. Please try again.', 'miniorange-2-factor-authentication' ) );
				return;
			}
			$client_data     = self::mo2f_base64url_decode( $credential['response']['clientDataJSON'] );
			$client_data_obj = json_decode( $client_data, true );
			MO2f_Utility::mo2f_debug_file(
				'Passkey validate_otp - clientData type=' . ( isset( $client_data_obj['type'] ) ? $client_data_obj['type'] : '(missing)' )
				. ' origin=' . ( isset( $client_data_obj['origin'] ) ? $client_data_obj['origin'] : '(missing)' )
				. ' expected_rpId=' . $this->mo2f_get_relying_party_id()
				. ' transports=' . wp_json_encode( isset( $credential['response']['transports'] ) ? $credential['response']['transports'] : array() )
			);
			try {
				$webauthn        = $this->mo2f_get_webauthn();
				$attestation_obj = self::mo2f_base64url_decode( $credential['response']['attestationObject'] );
				$challenge       = \lbuchs\WebAuthn\Binary\ByteBuffer::fromBase64Url( $challenge_b64url );
				$result          = $webauthn->processCreate( $client_data, $attestation_obj, $challenge );
			} catch ( \Throwable $e ) {
				MO2f_Utility::mo2f_debug_file(
					'Passkey registration verification failed - User_Id-' . $user->ID
					. ' ExceptionClass=' . get_class( $e ) . ' Code=' . $e->getCode() . ' Error-' . $e->getMessage()
				);
				wp_send_json_error( __( 'Could not verify this passkey. Please try again.', 'miniorange-2-factor-authentication' ) );
				return;
			}
			delete_transient( $session_id_encrypt . 'mo2f_passkey_reg_challenge' );
			$credentials   = $this->mo2f_get_user_credentials( $user->ID );
			$credentials[] = array(
				'credential_id' => self::mo2f_base64url_encode( $result->credentialId ), // Raw binary string per AuthenticatorData::getCredentialId().
				'public_key'    => $result->credentialPublicKey, // Already a PEM string per AuthenticatorData::getPublicKeyPem().
				'sign_count'    => isset( $result->signatureCounter ) ? (int) $result->signatureCounter : 0,
				'transports'    => isset( $credential['response']['transports'] ) && is_array( $credential['response']['transports'] ) ? $credential['response']['transports'] : array(),
				/* translators: %s: date the passkey was added. */
				'nickname'      => sprintf( __( 'Passkey added on %s', 'miniorange-2-factor-authentication' ), gmdate( 'M j, Y' ) ),
				'created_at'    => time(),
				'last_used_at'  => null,
			);
			$this->mo2f_save_user_credentials( $user->ID, $credentials );
			$email = $user->user_email;
			$mo2f_onprem_cloud_obj->mo2f_update_user_info( $user->ID, true, MoWpnsConstants::PASSKEY, MoWpnsConstants::SUCCESS_RESPONSE, MoWpnsConstants::MO_2_FACTOR_PLUGIN_SETTINGS, true, $email, null );
			MO2f_Utility::mo2f_debug_file( 'Passkey validate_otp - SUCCESS, credential registered for User_Id-' . $user->ID . ' total_credentials=' . count( $credentials ) );
			$common_helper = new Mo2f_Common_Helper();
			$common_helper->mo2f_update_current_user_status( $session_id_encrypt );
			wp_send_json_success( __( 'Passkey has been configured successfully.', 'miniorange-2-factor-authentication' ) );
		}

		/**
		 * AJAX: builds WebAuthn authentication ("get") options for the user tied to the
		 * current login challenge / dashboard test session.
		 *
		 * @param array $post Post data.
		 * @return void
		 */
		public function mo2f_get_authentication_options( $post ) {
			if ( ! $this->mo2f_is_available() ) {
				wp_send_json_error( $this->mo2f_get_unavailable_reason() );
				return;
			}
			$session_id = isset( $post['session_id'] ) ? sanitize_text_field( wp_unslash( $post['session_id'] ) ) : '';
			$user_id    = $this->mo2f_resolve_user_id( $session_id );
			MO2f_Utility::mo2f_debug_file( 'Passkey get_authentication_options - is_user_logged_in=' . ( is_user_logged_in() ? '1' : '0' ) . ' session_id=' . ( $session_id ? substr( $session_id, 0, 12 ) . '...' : '(empty)' ) . ' resolved_user_id=' . ( $user_id ? $user_id : '(none)' ) . ' post_keys=' . implode( ',', array_keys( (array) $post ) ) );
			if ( ! $user_id ) {
				wp_send_json_error( __( 'Unauthorized.', 'miniorange-2-factor-authentication' ) );
				return;
			}
			$credentials = $this->mo2f_get_user_credentials( $user_id );
			if ( empty( $credentials ) ) {
				MO2f_Utility::mo2f_debug_file( 'Passkey get_authentication_options - no credentials found for user_id=' . $user_id );
				wp_send_json_error( __( 'No passkeys are registered for this account.', 'miniorange-2-factor-authentication' ) );
				return;
			}
			$credential_ids = array_map(
				function ( $credential ) {
					return \lbuchs\WebAuthn\Binary\ByteBuffer::fromBase64Url( $credential['credential_id'] );
				},
				$credentials
			);
			try {
				$webauthn = $this->mo2f_get_webauthn();
				$get_args = $webauthn->getGetArgs( $credential_ids );
			} catch ( \Throwable $e ) {
				MO2f_Utility::mo2f_debug_file(
					'Passkey authentication options error - User_Id-' . $user_id
					. ' ExceptionClass=' . get_class( $e ) . ' Code=' . $e->getCode() . ' Error-' . $e->getMessage()
				);
				wp_send_json_error( __( 'Could not start passkey sign-in. Please try again.', 'miniorange-2-factor-authentication' ) );
				return;
			}
			$options = json_decode( wp_json_encode( $get_args ), true );
			if ( empty( $options['publicKey']['challenge'] ) ) {
				wp_send_json_error( __( 'Could not start passkey sign-in. Please try again.', 'miniorange-2-factor-authentication' ) );
				return;
			}
			MO2f_Utility::mo2f_debug_file(
				'Passkey get_authentication_options - options built OK for user_id=' . $user_id
				. ' rpId=' . $this->mo2f_get_relying_party_id() . ' offered_credentials=' . count( $credential_ids )
			);
			set_transient( $session_id . 'mo2f_passkey_auth_challenge', $options['publicKey']['challenge'], 120 );
			wp_send_json_success( $options );
		}

		/**
		 * Shows the Passkey login challenge.
		 *
		 * @param object $currentuser Current user.
		 * @param string $session_id_encrypt Session ID.
		 * @param string $redirect_to Redirection url.
		 * @return void
		 */
		public function mo2f_prompt_2fa_login( $currentuser, $session_id_encrypt, $redirect_to ) {
			$mo2fa_login_message = __( 'Use your fingerprint, face, or security key to sign in.', 'miniorange-2-factor-authentication' );
			$mo2fa_login_status  = MoWpnsConstants::MO_2_FACTOR_CHALLENGE_PASSKEY_AUTHENTICATION;
			$this->mo2f_show_login_prompt( $mo2fa_login_message, $mo2fa_login_status, $currentuser, $redirect_to, $session_id_encrypt );
		}

		/**
		 * Generic login-prompt entry point expected by callers outside this handler
		 * (e.g. Mo2f_Backup_Codes::mo2f_show_backup_code_sent_message(), which resolves
		 * whatever method the user has configured via mo2f_get_object() and calls this
		 * same method name on it - mirrors every other method handler's own
		 * mo2f_show_login_prompt()).
		 *
		 * @param string $mo2fa_login_message Login message.
		 * @param string $mo2fa_login_status Login status.
		 * @param object $currentuser Current user.
		 * @param string $redirect_to Redirection url.
		 * @param string $session_id Session ID.
		 * @return void
		 */
		public function mo2f_show_login_prompt( $mo2fa_login_message, $mo2fa_login_status, $currentuser, $redirect_to, $session_id ) {
			$login_popup  = new Mo2f_Login_Popup();
			$extra_script = $this->mo2f_get_script( 'login_2fa', $session_id, $redirect_to );
			$login_popup->mo2f_show_login_prompt_for_otp_based_methods( $mo2fa_login_message, $mo2fa_login_status, $currentuser, $redirect_to, $session_id, $this->mo2f_current_method, null, $extra_script );
			exit;
		}

		/**
		 * Validates a Passkey authentication/assertion response submitted during login.
		 *
		 * @param string $otp_token Serialized WebAuthn assertion response (JSON).
		 * @param string $redirect_to Redirection url.
		 * @param string $session_id_encrypt Session ID.
		 * @return void
		 */
		public function mo2f_login_validate( $otp_token, $redirect_to, $session_id_encrypt ) {
			global $mo2f_mo_wpns_utility;
			if ( ! $this->mo2f_is_available() ) {
				wp_send_json_error( $this->mo2f_get_unavailable_reason() );
				return;
			}
			$common_helper = new Mo2f_Common_Helper();
			$user_id       = $common_helper->mo2f_get_current_user_id( $session_id_encrypt );
			if ( ! $user_id && is_user_logged_in() ) {
				$user_id = get_current_user_id();
			}
			$user = $user_id ? get_user_by( 'id', $user_id ) : null;
			MO2f_Utility::mo2f_debug_file( 'Passkey login_validate - is_user_logged_in=' . ( is_user_logged_in() ? '1' : '0' ) . ' resolved_user_id=' . ( $user_id ? $user_id : '(none)' ) . ' otp_token_length=' . strlen( (string) $otp_token ) );
			if ( ! $user ) {
				MO2f_Utility::mo2f_debug_file( 'Passkey login_validate - no user resolved from session, rejecting.' );
				$mo2f_mo_wpns_utility->mo2f_handle_attempt_validation( 'INVALID_OTP', $session_id_encrypt );
				return;
			}
			$assertion = json_decode( $otp_token, true );
			if ( empty( $assertion['id'] ) || empty( $assertion['response']['clientDataJSON'] ) || empty( $assertion['response']['authenticatorData'] ) || empty( $assertion['response']['signature'] ) ) {
				MO2f_Utility::mo2f_debug_file( 'Passkey login_validate - malformed assertion payload for User_Id-' . $user->ID . ' json_last_error=' . json_last_error_msg() );
				$mo2f_mo_wpns_utility->mo2f_handle_attempt_validation( 'INVALID_OTP', $session_id_encrypt );
				return;
			}
			$credentials     = $this->mo2f_get_user_credentials( $user->ID );
			$credential_key  = null;
			$matched         = null;
			foreach ( $credentials as $key => $credential ) {
				if ( hash_equals( $credential['credential_id'], $assertion['id'] ) ) {
					$credential_key = $key;
					$matched        = $credential;
					break;
				}
			}
			if ( null === $matched ) {
				MO2f_Utility::mo2f_debug_file( 'Passkey login_validate - assertion id did not match any stored credential for User_Id-' . $user->ID . ' registered_count=' . count( $credentials ) );
				$mo2f_mo_wpns_utility->mo2f_handle_attempt_validation( 'INVALID_OTP', $session_id_encrypt );
				return;
			}
			$challenge_b64url = get_transient( $session_id_encrypt . 'mo2f_passkey_auth_challenge' );
			if ( empty( $challenge_b64url ) ) {
				MO2f_Utility::mo2f_debug_file( 'Passkey login_validate - no stored challenge transient found (expired or session_id mismatch) for User_Id-' . $user->ID );
				wp_send_json_error( __( 'This sign-in request has expired. Please try again.', 'miniorange-2-factor-authentication' ) );
				return;
			}
			$client_data     = self::mo2f_base64url_decode( $assertion['response']['clientDataJSON'] );
			$client_data_obj = json_decode( $client_data, true );
			MO2f_Utility::mo2f_debug_file(
				'Passkey login_validate - clientData type=' . ( isset( $client_data_obj['type'] ) ? $client_data_obj['type'] : '(missing)' )
				. ' origin=' . ( isset( $client_data_obj['origin'] ) ? $client_data_obj['origin'] : '(missing)' )
				. ' expected_rpId=' . $this->mo2f_get_relying_party_id()
				. ' credential_transports=' . wp_json_encode( isset( $matched['transports'] ) ? $matched['transports'] : array() )
				. ' stored_sign_count=' . $matched['sign_count']
			);
			try {
				$webauthn        = $this->mo2f_get_webauthn();
				$authenticator_d = self::mo2f_base64url_decode( $assertion['response']['authenticatorData'] );
				$signature       = self::mo2f_base64url_decode( $assertion['response']['signature'] );
				$challenge       = \lbuchs\WebAuthn\Binary\ByteBuffer::fromBase64Url( $challenge_b64url );
				// processGet() returns true (or throws) - it does not return the new counter;
				// the updated value must be read back via getSignatureCounter() afterwards.
				$webauthn->processGet( $client_data, $authenticator_d, $signature, $matched['public_key'], $challenge, $matched['sign_count'] );
				$new_sign_count = $webauthn->getSignatureCounter();
			} catch ( \Throwable $e ) {
				MO2f_Utility::mo2f_debug_file(
					'Passkey login verification failed - User_Id-' . $user->ID
					. ' ExceptionClass=' . get_class( $e ) . ' Code=' . $e->getCode() . ' Error-' . $e->getMessage()
				);
				$mo2f_mo_wpns_utility->mo2f_handle_attempt_validation( 'INVALID_OTP', $session_id_encrypt );
				return;
			}
			delete_transient( $session_id_encrypt . 'mo2f_passkey_auth_challenge' );
			$credentials[ $credential_key ]['sign_count']   = null !== $new_sign_count ? $new_sign_count : $matched['sign_count'];
			$credentials[ $credential_key ]['last_used_at'] = time();
			$this->mo2f_save_user_credentials( $user->ID, $credentials );
			MO2f_Utility::mo2f_debug_file( 'Passkey login_validate - SUCCESS for User_Id-' . $user->ID . ' new_sign_count=' . ( null !== $new_sign_count ? $new_sign_count : '(unchanged)' ) );
			$common_helper->mo2f_update_current_user_status( $session_id_encrypt );
			wp_send_json_success( 'VALIDATED_SUCCESS' );
		}

		/**
		 * AJAX: removes one of the current user's registered passkeys.
		 *
		 * @param array $post Post data.
		 * @return void
		 */
		public function mo2f_delete_credential( $post ) {
			global $mo2fdb_queries;
			$session_id = isset( $post['mo2f_session_id'] ) ? sanitize_text_field( wp_unslash( $post['mo2f_session_id'] ) ) : '';
			$user_id    = $this->mo2f_resolve_user_id( $session_id );
			MO2f_Utility::mo2f_debug_file( 'Passkey delete_credential - is_user_logged_in=' . ( is_user_logged_in() ? '1' : '0' ) . ' session_id=' . ( $session_id ? substr( $session_id, 0, 12 ) . '...' : '(empty)' ) . ' resolved_user_id=' . ( $user_id ? $user_id : '(none)' ) . ' post_keys=' . implode( ',', array_keys( (array) $post ) ) );
			if ( ! $user_id ) {
				wp_send_json_error( __( 'Unauthorized.', 'miniorange-2-factor-authentication' ) );
				return;
			}
			$credential_id = isset( $post['credential_id'] ) ? sanitize_text_field( wp_unslash( $post['credential_id'] ) ) : '';
			$credentials   = $this->mo2f_get_user_credentials( $user_id );
			$remaining     = array_values(
				array_filter(
					$credentials,
					function ( $credential ) use ( $credential_id ) {
						return $credential['credential_id'] !== $credential_id;
					}
				)
			);
			if ( count( $remaining ) === count( $credentials ) ) {
				wp_send_json_error( __( 'Passkey not found.', 'miniorange-2-factor-authentication' ) );
				return;
			}
			$is_active_method = MoWpnsConstants::PASSKEY === $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_configured_2FA_method', $user_id );
			if ( $is_active_method && empty( $remaining ) ) {
				wp_send_json_error( __( 'You cannot remove your only passkey while Passkey is your active two-factor method. Configure another method first.', 'miniorange-2-factor-authentication' ) );
				return;
			}
			$this->mo2f_save_user_credentials( $user_id, $remaining );
			wp_send_json_success();
		}

		/**
		 * AJAX: records a client-side WebAuthn failure (e.g. navigator.credentials
		 * rejecting during a cross-device/QR hybrid-transport ceremony before any
		 * verification request ever reaches the server). Logged into the same debug
		 * log used everywhere else in this handler, so both sides of a failed
		 * ceremony are diagnosable from a single file. Never requires authentication
		 * beyond the standard AJAX nonce check (already enforced by the dispatcher
		 * this is called from), since it only ever logs, never mutates state.
		 *
		 * @param array $post Post data.
		 * @return void
		 */
		public function mo2f_log_client_error( $post ) {
			$flow          = isset( $post['flow'] ) ? sanitize_text_field( wp_unslash( $post['flow'] ) ) : '(unknown)';
			$context       = isset( $post['context'] ) ? sanitize_text_field( wp_unslash( $post['context'] ) ) : '(unknown)';
			$error_name    = isset( $post['error_name'] ) ? sanitize_text_field( wp_unslash( $post['error_name'] ) ) : '(unknown)';
			$error_message = isset( $post['error_message'] ) ? sanitize_textarea_field( wp_unslash( $post['error_message'] ) ) : '(none)';
			$session_id    = isset( $post['mo2f_session_id'] ) ? sanitize_text_field( wp_unslash( $post['mo2f_session_id'] ) ) : '';
			$user_id       = $this->mo2f_resolve_user_id( $session_id );
			$user_agent    = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '(unknown)';
			MO2f_Utility::mo2f_debug_file(
				'Passkey CLIENT-SIDE error - flow=' . $flow . ' context=' . $context . ' user_id=' . ( $user_id ? $user_id : '(none)' )
				. ' error_name=' . $error_name . ' error_message=' . $error_message . ' user_agent=' . $user_agent
			);
			wp_send_json_success();
		}
	}
}
