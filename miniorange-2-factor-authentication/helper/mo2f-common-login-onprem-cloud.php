<?php
/**
 * This file contains functions related to login flow.
 *
 * @package miniorange-2-factor-authentication/controllers/twofa
 */

use TwoFA\Handler\Twofa\MO2f_Utility;
use TwoFA\Helper\MocURL;
use TwoFA\Helper\MoWpnsUtility;
use TwoFA\Helper\MoWpnsHandler;
use TwoFA\Handler\Twofa\MO2f_Cloud_Onprem_Interface;
use TwoFA\Handler\Twofa\Miniorange_Password_2Factor_Login;
use TwoFA\Helper\MoWpnsConstants;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Function checks if 2fa enabled for given user roles (used in shortcode addon)
 *
 * @param array $current_roles array containing roles of user.
 * @return boolean
 */
function mo2f_miniorange_check_if_2fa_enabled_for_roles( $current_roles ) {
	if ( empty( $current_roles ) ) {
		return 0;
	}

	foreach ( $current_roles as $value ) {
		if ( get_option( 'mo2fa_' . $value ) ) {
			return 1;
		}
	}

	return 0;
}

/**
 * This function prompts forgot phone form.
 *
 * @param string $login_status login status of user.
 * @param string $login_message message used to show success/failed login actions.
 * @param string $redirect_to redirect url.
 * @param string $session_id_encrypt encrypted session id.
 * @return void
 */
function mo2f_get_forgotphone_form( $login_status, $login_message, $redirect_to, $session_id_encrypt ) {
	$mo2f_forgotphone_enabled     = MoWpnsUtility::get_mo2f_db_option( 'mo2f_enable_forgotphone', 'get_option' );
	$mo2f_email_as_backup_enabled = get_option( 'mo2f_enable_forgotphone_email' );
	$mo2f_kba_as_backup_enabled   = get_option( 'mo2f_enable_forgotphone_kba' );
	?>
	<html>
	<head>
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<?php
		echo_js_css_files();
		?>
	</head>
	<body>
	<div class="mo2f_modal" tabindex="-1" role="dialog">
		<div class="mo2f-modal-backdrop"></div>
		<div class="mo_customer_validation-modal-dialog mo_customer_validation-modal-md">
			<div class="login mo_customer_validation-modal-content">
				<div class="mo2f_modal-header">
					<h4 class="mo2f_modal-title">
						<button type="button" class="mo2f_close" data-dismiss="modal" aria-label="Close"
								title="<?php esc_attr_e( 'Back to login', 'miniorange-2-factor-authentication' ); ?>"
								onclick="mologinback();"><span aria-hidden="true">&times;</span></button>
						<?php esc_html_e( 'How would you like to authenticate yourself?', 'miniorange-2-factor-authentication' ); ?>
					</h4>
				</div>
				<div class="mo2f_modal-body">
					<?php
					if ( $mo2f_forgotphone_enabled ) {
						if ( isset( $login_message ) && ! empty( $login_message ) ) {
							?>
							<div id="mo2f-otpMessage" class="mo2fa_display_message_frontend">
								<p class="mo2fa_display_message_frontend"><?php echo wp_kses( $login_message, array( 'b' => array() ) ); ?></p>
							</div>
						<?php } ?>
						<p class="mo2f_backup_options"><?php esc_html_e( 'Please choose the options from below:', 'miniorange-2-factor-authentication' ); ?></p>
						<div class="mo2f_backup_options_div">
							<?php if ( $mo2f_email_as_backup_enabled ) { ?>
								<input type="radio" name="mo2f_selected_forgotphone_option"
									value="One Time Passcode over Email"
									checked="checked"/><?php esc_html_e( 'Send a one time passcode to my registered email', 'miniorange-2-factor-authentication' ); ?>
								<br><br>
								<?php
							}
							if ( $mo2f_kba_as_backup_enabled ) {
								?>
								<input type="radio" name="mo2f_selected_forgotphone_option"
									value="'<?php echo esc_js( MoWpnsConstants::SECURITY_QUESTIONS ); ?>'"/><?php esc_html_e( 'Answer your Security Questions (KBA)', 'miniorange-2-factor-authentication' ); ?>
							<?php } ?>
							<br><br>
							<input type="button" name="miniorange_validate_otp" value="<?php esc_attr_e( 'Continue', 'miniorange-2-factor-authentication' ); ?>" class="miniorange_validate_otp"
								onclick="mo2fselectforgotphoneoption();"/>
						</div>
						<?php
						mo2f_customize_logo();
					}
					?>
				</div>
			</div>
		</div>
	</div>
	<form name="f" id="mo2f_backto_mo_loginform" method="post" action="<?php echo esc_url( wp_login_url() ); ?>"
		class="mo2f_display_none_forms">
		<input type="hidden" name="miniorange_mobile_validation_failed_nonce"
			value="<?php echo esc_attr( wp_create_nonce( 'miniorange-2-factor-mobile-validation-failed-nonce' ) ); ?>"/>
		<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_to ); ?>"/>
		<input type="hidden" name="session_id" value="<?php echo esc_attr( $session_id_encrypt ); ?>"/>
	</form>
	<form name="f" id="mo2f_challenge_forgotphone_form" method="post" class="mo2f_display_none_forms">
		<input type="hidden" name="mo2f_configured_2FA_method"/>
		<input type="hidden" name="miniorange_challenge_forgotphone_nonce"
			value="<?php echo esc_attr( wp_create_nonce( 'miniorange-2-factor-challenge-forgotphone-nonce' ) ); ?>"/>
		<input type="hidden" name="option" value="miniorange_challenge_forgotphone">
		<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_to ); ?>"/>
		<input type="hidden" name="session_id" value="<?php echo esc_attr( $session_id_encrypt ); ?>"/>
	</form>

	<script>
		function mologinback() {
			jQuery('#mo2f_backto_mo_loginform').submit();
		}

		function mo2fselectforgotphoneoption() {
			var option = jQuery('input[name=mo2f_selected_forgotphone_option]:checked').val();
			document.getElementById("mo2f_challenge_forgotphone_form").elements[0].value = option;
			jQuery('#mo2f_challenge_forgotphone_form').submit();
		}
	</script>
	</body>
	</html>
	<?php
}

/**
 * This function prints customized logo.
 *
 * @return string
 */
function mo2f_customize_logo() {
	$html = '<div style="float:right;"><img
					alt="logo"
					src="' . esc_url( plugins_url( 'includes/images/miniOrange2.png', __DIR__ ) ) . '"/></div>';
					return $html;
}
