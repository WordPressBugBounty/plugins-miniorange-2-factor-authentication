<?php
/**
 * This file shows the plugin settings on frontend.
 *
 * @package miniorange-2-factor-authentication/views/whitelabelling
 */

use TwoFA\Helper\MoWpnsMessages;
use TwoFA\Helper\MoWpnsUtility;
use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Helper\Mo2f_Common_Helper;
?>
<div class="mo2f-settings-div">
	<div class="mo2f-settings-head -ml-mo-9">
	<?php $gauth_name = get_option( 'mo2f_google_appname' ) ? get_option( 'mo2f_google_appname' ) : DEFAULT_GOOGLE_APPNAME; ?>
		<span><?php esc_html_e( 'Google Authenticator', 'miniorange-2-factor-authentication' ); ?></span>
	</div>
	<div class="mo2f-sub-settings-div">
		<span><?php esc_html_e( 'Change App name in authenticator app:', 'miniorange-2-factor-authentication' ); ?></span>
		<span>
			<input type="text" class="m-mo-4" id= "mo2f_change_app_name" name="mo2f_google_auth_appname" placeholder="Enter the app name" value="<?php echo esc_attr( $gauth_name ); ?>"  />
			<input type="hidden" id="mo2f_nonce" name="mo2f_nonce" value="<?php echo esc_attr( wp_create_nonce( 'mo2f-white-labelling-ajax-nonce' ) ); ?>" />
		</span>
	</div>
	<div class="justify-start" id="mo2f_google_appname_save"><div class="mo2f_google_appname_save_button"><button id="mo2f_google_appname_save_button" class="mo2f-save-settings-button"><?php esc_html_e( 'Save App Name', 'miniorange-2-factor-authentication' ); ?></button></div></div>
</div>

<div class="mo2f-settings-div">
	<div class="mo2f-settings-head -ml-mo-9">
		<span><?php esc_html_e( 'OTP Over SMS', 'miniorange-2-factor-authentication' ); ?></span>
		<?php echo Mo2f_Common_Helper::mo2f_check_plan( 'basic', MoWpnsConstants::MO2F_PREMIUM_3PLAN_NAME ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Only a SVG, doesn't require escaping. ?>
	</div>
	<div class="mo2f-sub-settings-div mo2f-basic-plan">
	<div class="mb-mo-4"><?php esc_html_e( 'Change SMS Template: ', 'miniorange-2-factor-authentication' ); ?><span><a href="https://login.xecurify.com/moas/admin/customer/showsmstemplate" target="_blank"><?php esc_html_e( 'Click Here', 'miniorange-2-factor-authentication' ); ?></a></span>
	</div><div><?php esc_html_e( 'Configure Custom SMS Gateway: ', 'miniorange-2-factor-authentication' ); ?><sapn><a href="https://login.xecurify.com/moas/admin/customer/smsconfig" target="_blank"><?php esc_html_e( 'Click Here', 'miniorange-2-factor-authentication' ); ?></a></span>
</div>
</div>
</div>

<div class="mo2f-settings-div">
	<div class="mo2f-settings-head -ml-mo-9">
		<span><?php esc_html_e( 'Customize Security Questions (KBA)', 'miniorange-2-factor-authentication' ); ?>
		<?php echo Mo2f_Common_Helper::mo2f_check_plan( 'enterprise', MoWpnsConstants::MO2F_PREMIUM_2PLAN_NAME ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Only a SVG, doesn't require escaping. ?>
	</div>
	<form name="f" id="mo2f_custom_security_questions_settings">
	<div class="mo2f-sub-settings-div mo2f-enterprise-plan">
		<p><?php esc_html_e( 'You can customize the list of Security Questions and specify how many custom questions users can add during setup.', 'miniorange-2-factor-authentication' ); ?></p>
		<b style="display: inline-block; margin-top: 0;"><?php esc_html_e( 'Security Questions (KBA) List:', 'miniorange-2-factor-authentication' ); ?></b>
		<div class="mo2f_collapse" id="customSecurityQuestions" style="margin-left: 2%;">
			<input type="hidden" name="mo2f_whitelabelling_nonce" id="mo2f_whitelabelling_nonce" value="<?php echo esc_attr( wp_create_nonce( 'mo2f-white-labelling-ajax-nonce' ) ); ?>"/><br />
			<table class="mo2f_kba_table">
				<?php
					$questions_to_display = ! empty( $saved_questions ) ? $saved_questions : array_slice( $GLOBALS['mo2f_default_kba_question_set'], 0, 10 );
				foreach ( $questions_to_display as $index => $question_value ) {
					$question_number = $index + 1;
					?>
					<tr class="mo2f_kba_body">
						<td style="width: 40px;"><?php echo 'Q' . esc_html( $question_number ) . ':'; ?></td>
						<td>
							<input class="w-2/3" type="text" name="mo2f_kbaquestion_custom_admin[]" 
							id="mo2f_kbaquestion_custom_admin_<?php echo esc_attr( $question_number ); ?>" 
							value="<?php echo esc_attr( $question_value ); ?>" 
							placeholder="<?php esc_attr_e( 'Enter your custom question here', 'miniorange-2-factor-authentication' ); ?>" 
							autocomplete="off" />
							<button type="button" class="mo2f_add_question">+</button>
							<button type="button" class="mo2f_remove_question">-</button>
						</td>
					</tr>
					<?php
				}
				?>
			</table>
				<br />
				<div class="m-mo-4">
				<table >
				<tr >
				<td>
					<span><?php esc_html_e( 'No. of default questions to be selected from the above list during security question configuration : ', 'miniorange-2-factor-authentication' ); ?></span>
				</td>
				<td>
					<input type="number" name="mo2f_default_kbaquestions_users" id="mo2f_default_kbaquestions_users" value="<?php echo esc_attr( $default_question_count ); ?>" min="1" max="5" />
				</td>
				</tr>
				<tr>
				<td>
					<span><?php esc_html_e( 'No. of custom questions user can add during security question configuration : ', 'miniorange-2-factor-authentication' ); ?></span>
				</td>
				<td>
					<input type="number" name="mo2f_custom_kbaquestions_users" id="mo2f_custom_kbaquestions_users" value="<?php echo esc_attr( $custom_question_count ); ?>" min="0" max="5" />
				</td>
				</tr>
			</table>
		</div>
	</div>
	</div>
	<div class="justify-start">
		<div class="mo2f-enterprise-plan">
			<button id="mo2f-save-custom-kba-settings"  class="mo2f-save-settings-button"><?php esc_html_e( 'Save Settings', 'miniorange-2-factor-authentication' ); ?></button>
			<input type="button" id="mo2f_custom_security_questions_reset_button"  class="mo2f-reset-settings-button" value ="<?php esc_attr_e( 'Reset Settings', 'miniorange-2-factor-authentication' ); ?>"></input>
		</div>
	</div>
	</form>
	<form  id="mo2f_login_popup_reset_button_form_second" method="post" action="">
		<input type="hidden" name="mo2f_whitelabelling_nonce" value="<?php echo esc_attr( wp_create_nonce( 'mo2f-whitelabelling-nonce' ) ); ?>"/>
		<input type="hidden" name="option" value="mo2f_reset_custom_security_questions_settings">
	</form>
</div>

<script>
	jQuery('#2facustomizations').addClass('mo2f-subtab-active');
	jQuery("#mo_2fa_white_labelling").addClass("side-nav-active");
</script>


