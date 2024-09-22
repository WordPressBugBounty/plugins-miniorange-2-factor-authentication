<?php
/**
 * This file shows the plugin settings on frontend.
 *
 * @package miniorange-2-factor-authentication/views/whitelabelling
 */

use TwoFA\Helper\MoWpnsMessages;
use TwoFA\Helper\MoWpnsUtility;

$overlay_on_premium_features = MO2F_PREMIUM_PLAN ? '' : 'mo2f-premium-feature';
$crown                       = MO2F_PREMIUM_PLAN ? '' : '<svg width="18" class="ml-mo-4 -mb-mo-0.5" height="18" viewBox="0 0 24 24" fill="none">
<g id="d4a43e0162b45f718f49244b403ea8f4">
	<g id="4ea4c3dca364b4cff4fba75ac98abb38">
		<g id="2413972edc07f152c2356073861cb269">
			<path id="2deabe5f8681ff270d3f37797985a977" d="M20.8007 20.5644H3.19925C2.94954 20.5644 2.73449 20.3887 2.68487 20.144L0.194867 7.94109C0.153118 7.73681 0.236091 7.52728 0.406503 7.40702C0.576651 7.28649 0.801941 7.27862 0.980492 7.38627L7.69847 11.4354L11.5297 3.72677C11.6177 3.54979 11.7978 3.43688 11.9955 3.43531C12.1817 3.43452 12.3749 3.54323 12.466 3.71889L16.4244 11.3598L23.0197 7.38654C23.1985 7.27888 23.4233 7.28702 23.5937 7.40728C23.7641 7.52754 23.8471 7.73707 23.8056 7.94136L21.3156 20.1443C21.2652 20.3887 21.0501 20.5644 20.8007 20.5644Z" fill="orange"></path>
		</g>
	</g>
</g>
</svg>';
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
		</span>
		<div class="justify-start" id="mo2f_google_appname_save"><div class="mo2f_google_appname_save_button"><button id="mo2f_google_appname_save_button" class="mo2f-save-settings-button"><?php esc_html_e( 'Save App Name', 'miniorange-2-factor-authentication' ); ?></button></div></div>
	</div>
</div>

<div class="mo2f-settings-div">
	<div class="mo2f-settings-head -ml-mo-9">
		<span><?php esc_html_e( 'OTP Over SMS', 'miniorange-2-factor-authentication' ); ?></span>
	</div>
	<div class="mo2f-sub-settings-div  <?php echo esc_attr( $overlay_on_premium_features ); ?>">
	<div class="mb-mo-4"><?php esc_html_e( 'Change SMS Template: ', 'miniorange-2-factor-authentication' ); ?><span><a href="https://login.xecurify.com/moas/admin/customer/showsmstemplate">Click Here</a></span><?php echo $crown; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Only a SVG, doesn't require escaping. ?></div>
	<div><?php esc_html_e( 'Configure Custom SMS Gateway: ', 'miniorange-2-factor-authentication' ); ?><sapn><a href="https://login.xecurify.com/moas/admin/customer/smsconfig">Click Here</a></span><?php echo $crown; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Only a SVG, doesn't require escaping. ?></div>
	</div>
</div>

<div class="mo2f-settings-div">
	<div class="mo2f-settings-head -ml-mo-9">
		<span><?php esc_html_e( 'Customize Security Questions (KBA)', 'miniorange-2-factor-authentication' ); ?><?php echo $crown; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Only a SVG, doesn't require escaping. ?></span>
	</div>
	<div class="mo2f-sub-settings-div <?php echo esc_attr( $overlay_on_premium_features ); ?>">
		<p><?php esc_html_e( 'You can customize the questions list shown in the Security Questions. You can also choose how many custom questions your endusers can add while setting up Security Questions.', 'miniorange-2-factor-authentication' ); ?></p>
		<p><b><a data-toggle="mo2f_collapse" aria-expanded="false" id ="previewsecurityquestion" href="#customSecurityQuestions"><?php esc_html_e( 'Click Here', 'miniorange-2-factor-authentication' ); ?></a> <?php esc_html_e( 'to customize Security Questions.', 'miniorange-2-factor-authentication' ); ?></b></p>

		<div class="mo2f_collapse" id="customSecurityQuestions" style="margin-left: 2%;">
			<form name="f"  id="custom_security_questions" method="post" action="">
				<a data-toggle="mo2f_collapse" aria-expanded="false" id="hintsforquestions" href="#addAdminQuestions"><b><?php esc_html_e( 'Hints for choosing questions:', 'miniorange-2-factor-authentication' ); ?></b></a>
				<div class="mo2f_collapse" id="addAdminQuestions">
					<ol>
						<li><?php esc_html_e( 'What is your first company name?', 'miniorange-2-factor-authentication' ); ?></li>
						<li><?php esc_html_e( 'What was your childhood nickname?', 'miniorange-2-factor-authentication' ); ?></li>
						<li><?php esc_html_e( 'In what city did you meet your spouse/significant other?', 'miniorange-2-factor-authentication' ); ?></li>
						<li><?php esc_html_e( 'What is the name of your favorite childhood friend?', 'miniorange-2-factor-authentication' ); ?></li>
						<li><?php esc_html_e( 'What school did you attend for sixth grade?', 'miniorange-2-factor-authentication' ); ?></li>
						<li><?php esc_html_e( 'In what city or town was your first job?', 'miniorange-2-factor-authentication' ); ?></li>
						<li><?php esc_html_e( 'What is your favourite sport?', 'miniorange-2-factor-authentication' ); ?></li>
						<li><?php esc_html_e( 'Who is your favourite sports player?', 'miniorange-2-factor-authentication' ); ?></li>
						<li><?php esc_html_e( 'What is your grandmother\'s maiden name?', 'miniorange-2-factor-authentication' ); ?></li>
						<li><?php esc_html_e( 'What was your first vehicle\'s registration number?', 'miniorange-2-factor-authentication' ); ?></li>
					</ol>
				</div><br/><br/>
				<b><?php esc_html_e( 'Add Questions in the Security Questions (KBA) List: (Alteast 10)', 'miniorange-2-factor-authentication' ); ?></b><br /><br />
				<table class="mo2f_kba_table">
					<?php for ( $qc = 0; $qc <= 5; $qc++ ) { ?>
					<tr class="mo2f_kba_body">
						<td>Q<?php echo esc_html( $qc + 1 ); ?>:</td>
						<td>
							<input class="w-2/3" type="text" name="mo2f_kbaquestion_custom_admin[]" id="mo2f_kbaquestion_custom_admin_<?php echo esc_attr( $qc + 1 ); ?>" pattern="(?=\S)[A-Za-zãõâêîôûÁÀÉÈÍÌÓÒÚÙáàéèíìóòúù 0-9\/_?@'.$#&+\-*\s]{1,100}" value="" placeholder="<?php esc_attr_e( 'Enter your custom question here', 'miniorange-2-factor-authentication' ); ?>" autocomplete="off" />
						</td>
					</tr>
					<?php } ?>
				</table>
				<div class="m-mo-4">
						<b><?php esc_html_e( 'Security Questions for users: ', 'miniorange-2-factor-authentication' ); ?></b><br /><br />
						<span><?php esc_html_e( 'Default Questions to choose from above list: ', 'miniorange-2-factor-authentication' ); ?><input style="border: 1px solid #ddd;border-radius: 4px;width:40px;" type="text" name="mo2f_default_kbaquestions_users" id="mo2f_default_kbaquestions_users" value="" pattern="[0-9]{1}" autocomplete="off" /> <b><=5</b></span><br />
						<?php esc_html_e( 'Custom Questions added by users: ', 'miniorange-2-factor-authentication' ); ?><input style="border: 1px solid #ddd;border-radius: 4px;width:40px;" type="text" name="mo2f_custom_kbaquestions_users" id="mo2f_custom_kbaquestions_users" value="" pattern="[0-9]{1}" autocomplete="off" /> <b><=5</b>
				</div>
			</form>
		</div>
	</div>
	<div class="justify-start" id="mo2f_google_appname_save">
			<div class=" <?php echo esc_attr( $overlay_on_premium_features ); ?>">
				<button class="mo2f-save-settings-button"><?php esc_html_e( 'Save Settings', 'miniorange-2-factor-authentication' ); ?></button>
			</div>
	</div>
</div>
<script>
	jQuery('#2facustomizations').addClass('mo2f-subtab-active');
	jQuery("#mo_2fa_white_labelling").addClass("side-nav-active");
	var nonce = "<?php echo esc_js( wp_create_nonce( 'mo2f-white-labelling-ajax-nonce' ) ); ?>";
	jQuery('#mo2f_google_appname_save_button').click(function () {
		jQuery(this).prop('disabled', true);
		var saveButtonId = jQuery(this).attr('id');
		var data = {
			'action': 'mo2f_white_labelling_ajax',
			'option': 'mo2f_google_app_name',
			'nonce': nonce,
			'mo2f_google_auth_appname': jQuery("#mo2f_change_app_name").val(),
		};
		jQuery.post(ajaxurl, data, function (response) {
			if (response.success) {
				success_msg("App name saved successfully!");
			} else {
				error_msg("Error while saving the app name!");
			}
		});
	});
</script>


