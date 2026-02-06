<?php
/**
 * This file shows the plugin settings on frontend.
 *
 * @package miniorange-2-factor-authentication/views/whitelabelling/
 */

use TwoFA\Helper\MoWpnsUtility;
use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Helper\Mo2f_Common_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="mo2f-settings-div mo2f-enterprise-plan">

<div class="mo2f-settings-head">
	<span><?php esc_html_e( 'Use your own branding logo on 2FA login Popup', 'miniorange-2-factor-authentication' ); ?></span>	
					<?php echo Mo2f_Common_Helper::mo2f_check_plan( 'enterprise', MoWpnsConstants::MO2F_PREMIUM_2PLAN_NAME ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Only a SVG, doesn't require escaping. ?>

</div>
<br>
<div class="text-mo-tertiary-txt ml-mo-16"> 
	<?php
		printf(
			/* Translators: %s: bold tags */
			esc_html( __( '%1$1sNote:%2$12s To ensure optimal appearance, please upload a logo with dimensions around 33x33 pixels.', 'miniorange-2-factor-authentication' ) ), //phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- The $text is a single string literal
			'<b>',
			'</b>',
		);
		?>
	</div>
<br><br>
<form name="mo2f_custom_logo_form_form" method="post" id="mo2f_custom_logo_form" action="" enctype="multipart/form-data">
	<input type="hidden" name="option" value="mo2f_add_custom_logo"/>
	<input type="hidden" name="mo2f_whitelabelling_nonce" value="<?php echo esc_attr( wp_create_nonce( 'mo2f-whitelabelling-nonce' ) ); ?>"/>
	<div class="flex ml-mo-20">
		<div class="mo2f-settings-div">
		<input style="margin:2%" type="file" name="imgFile" accept="image/*">
			<br><br>
			<div class="justify-end">
				<div class="mo2f-enterprise-plan">
					<button type="submit" id="mo2f_upload_custom_logo" class="mo2f-save-settings-button"><?php esc_html_e( 'Upload Logo', 'miniorange-2-factor-authentication' ); ?></button>
					<input type="button" id="mo2f_upload_logo_reset_button"  class="mo2f-reset-settings-button" value="<?php esc_attr_e( 'Reset', 'miniorange-2-factor-authentication' ); ?>"></input>
				</div>
			</div>
		</div ><div class="ml-mo-16">
		<div>	<span><b>Preview</b></span>	</div>
		<div class="mo2f-settings-div">
		<img class="mo2f-miniorange-logo" src="<?php echo esc_url( plugin_dir_url( dirname( __DIR__ ) ) . 'includes/images/' . get_site_option( 'mo2f_custom_logo', 'miniOrange2.png' ) ); ?>" alt="<?php esc_attr_e( 'miniOrange 2-factor Logo', 'miniorange-2-factor-authentication' ); ?>" >
		</div>
		</div>
	</div>
	<br>
</form>
<form name="mo2f_upload_logo_reset_button_form" id="mo2f_upload_logo_reset_button_form" method="post" action="">
		<input type="hidden" name="mo2f_whitelabelling_nonce" value="<?php echo esc_attr( wp_create_nonce( 'mo2f-whitelabelling-nonce' ) ); ?>"/>
		<input type="hidden" name="option" value="mo2f_reset_custom_logo">
</form>
</div>

<div class="mo2f-settings-div mo2f-enterprise-plan">

	<div class="mo2f-settings-head">
		<span><?php esc_html_e( 'Use custom 2FA login Popup', 'miniorange-2-factor-authentication' ); ?></span>	
		<?php echo Mo2f_Common_Helper::mo2f_check_plan( 'enterprise', MoWpnsConstants::MO2F_PREMIUM_2PLAN_NAME ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Only a SVG, doesn't require escaping. ?>
	</div>

		<div class="ml-mo-20">
		<form name="mo2f_login_popup_settings"  id="mo2f_login_popup_settings" method="post" action="">
			<input type="hidden" name="option" value="mo2f_login_popup_settings" />
			<input type="hidden" name="mo2f_whitelabelling_nonce" value="<?php echo esc_attr( wp_create_nonce( 'mo2f-whitelabelling-nonce' ) ); ?>"/>
				<table class="my-mo-3 w-3/4">
					<?php
					foreach ( $mo2f_login_popup as $mo2f_color => $mo2f_value ) {
						?>
					<tr>
					<td><b><?php esc_html_e( $mo2f_color, 'miniorange-2-factor-authentication' );  //phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- The $text is a single string literal ?> </b></td>
					<td><input type="text" id="<?php echo esc_attr( $mo2f_value ); ?>" name="<?php echo esc_attr( $mo2f_value ); ?>"
					value="<?php echo esc_attr( isset( $mo2f_custom_popup_css[ $mo2f_value ] ) ? $mo2f_custom_popup_css[ $mo2f_value ] : '' ); ?>" class="my-color-field" /> </td>
					</tr>
						<?php

					}
					?>
				<tr>
					<td><b><?php esc_html_e( 'Popup Background Image URL:', 'miniorange-2-factor-authentication' ); ?></b></td> &nbsp;
					<td> <input type="text" class="mo2f_table_textbox" style="width:93% !important;float:left;" name="mo2f_background_image" placeholder="<?php esc_html_e( 'Enter the url of the background image', 'miniorange-2-factor-authentication' ); ?>" 
					id="mo2f_background_image"  value="<?php echo esc_attr( isset( $mo2f_custom_popup_css['mo2f_background_image'] ) ? $mo2f_custom_popup_css['mo2f_background_image'] : '' ); ?>"  /></td>
				</tr>
				</table>
				<br>
				<div class="text-mo-tertiary-txt"><b><?php esc_html_e( 'Note:', 'miniorange-2-factor-authentication' ); ?></b> <?php esc_html_e( 'Popup Background Image will be updated only if Popup Background Color is clear or not selected.', 'miniorange-2-factor-authentication' ); ?></div>
				</br>	
				<div class="justify-start">
					<div class="mo2f-enterprise-plan">
					<button type="submit" id="mo2f_login_popup_save_button" class="mo2f-save-settings-button"><?php esc_html_e( 'Save Settings', 'miniorange-2-factor-authentication' ); ?></button>
					<input type="button" id="mo2f_login_popup_reset_button"  class="mo2f-reset-settings-button" value ="<?php esc_attr_e( 'Reset Settings', 'miniorange-2-factor-authentication' ); ?>"></input>
					</div>
				</div>
		</form>
		<form name="mo2f_login_popup_reset_button_form" id="mo2f_login_popup_reset_button_form" method="post" action="">
			<input type="hidden" name="mo2f_whitelabelling_nonce" value="<?php echo esc_attr( wp_create_nonce( 'mo2f-whitelabelling-nonce' ) ); ?>"/>
			<input type="hidden" name="option" value="mo2f_reset_login_popup_settings">
		</form>
	</div>
</div>
<div class="mo2f-settings-div mo2f-enterprise-plan">
	<div class="mo2f-settings-head">
		<span><?php esc_html_e( 'Customize Email Verification via Link Response Template', 'miniorange-2-factor-authentication' ); ?></span>	
		<?php echo Mo2f_Common_Helper::mo2f_check_plan( 'enterprise', MoWpnsConstants::MO2F_PREMIUM_2PLAN_NAME ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Only a SVG, doesn't require escaping. ?>

	</div>

	<div class="ml-mo-20">
		<form  id="mo2f_custom_email_verification_response_settings" method="post" action="" >
			<input type="hidden" name="option" value="mo2f_custom_email_verification_response_settings" />
			<input type="hidden" id="mo2f_white_labelling_nonce" name="mo2f_whitelabelling_nonce" value="<?php echo esc_attr( wp_create_nonce( 'mo2f-whitelabelling-nonce' ) ); ?>"/>
			<?php
			$mo2f_emails_popup_setting = array(
				__( 'Accept Message Text Color', 'miniorange-2-factor-authentication' ) => array( 'mo2f_accept_text_color', $mo2f_accept_text_color ),
				__( 'Deny Message Text Color', 'miniorange-2-factor-authentication' )   => array( 'mo2f_deny_text_color', $mo2f_deny_text_color ),
				__( 'Background Color', 'miniorange-2-factor-authentication' )          => array( 'mo2f_accept_deny_bg_color', $mo2f_bg_color ),
			);
			?>
			<table class="my-mo-3 w-3/4">
			<?php foreach ( $mo2f_emails_popup_setting as $mo2f_label => $mo2f_field ) : ?>
				<tr>
					<td><b><?php echo esc_html( $mo2f_label . ':' ); ?></b></td>
					<td>
					<input type="text" id="<?php echo esc_attr( $mo2f_field[0] ); ?>" name="<?php echo esc_attr( $mo2f_field[0] ); ?>" value="<?php echo esc_attr( $mo2f_field[1] ); ?>" class="my-color-field" />
					</td>
				</tr>
			<?php endforeach; ?>
				<tr>
					<td><b><?php esc_html_e( 'Popup Background Image URL:', 'miniorange-2-factor-authentication' ); ?></b></td>
					<td>
						<input type="text" class="mo2f_table_textbox" style="width:93% !important;float:left;" name="mo2f_custom_accept_deny_img" placeholder="<?php esc_html_e( 'Enter the url of the background image', 'miniorange-2-factor-authentication' ); ?>" id="mo2f_custom_accept_deny_img" value="<?php echo esc_attr( $mo2f_custom_img_url ); ?>" />
					</td>
				</tr>
			</table>
				<br>	
				<div class="justify-start">
					<div class="mo2f-enterprise-plan">
					<button type="submit" id="mo2f_email_verification_response_save_button" class="mo2f-save-settings-button"><?php esc_html_e( 'Save Settings', 'miniorange-2-factor-authentication' ); ?></button>
					<input type="button" id="mo2f_email_verification_response_reset_button"  class="mo2f-reset-settings-button" value ="<?php esc_attr_e( 'Reset Settings', 'miniorange-2-factor-authentication' ); ?>"></input>
					</div>
				</div>
		</form>
		<form id="mo2f_login_popup_reset_button_form2" method="post" action="">
			<input type="hidden" name="mo2f_whitelabelling_nonce" value="<?php echo esc_attr( wp_create_nonce( 'mo2f-whitelabelling-nonce' ) ); ?>"/>
			<input type="hidden" name="option" value="mo2f_reset_save_custom_email_verification_response_settings">
		</form>
	</div>
</div>
<script>
	jQuery('#loginpopup').addClass('mo2f-subtab-active');
	jQuery("#mo_2fa_white_labelling").addClass("side-nav-active");
</script>
