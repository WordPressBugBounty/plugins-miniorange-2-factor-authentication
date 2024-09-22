<?php
/**
 * This file shows the plugin settings on frontend.
 *
 * @package miniorange-2-factor-authentication/views/whitelabelling/
 */

use TwoFA\Helper\MoWpnsUtility;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$enable_2fa                  = MoWpnsUtility::get_mo2f_db_option( 'mo2f_activate_plugin', 'get_option' );
$overlay_on_premium_features = MO2F_PREMIUM_PLAN ? '' : 'mo2f-premium-feature';
$crown                       = '<svg width="18" class="ml-mo-4 -mb-mo-0.5" height="18" viewBox="0 0 24 24" fill="none">
<g id="d4a43e0162b45f718f49244b403ea8f4">
    <g id="4ea4c3dca364b4cff4fba75ac98abb38">
        <g id="2413972edc07f152c2356073861cb269">
            <path id="2deabe5f8681ff270d3f37797985a977" d="M20.8007 20.5644H3.19925C2.94954 20.5644 2.73449 20.3887 2.68487 20.144L0.194867 7.94109C0.153118 7.73681 0.236091 7.52728 0.406503 7.40702C0.576651 7.28649 0.801941 7.27862 0.980492 7.38627L7.69847 11.4354L11.5297 3.72677C11.6177 3.54979 11.7978 3.43688 11.9955 3.43531C12.1817 3.43452 12.3749 3.54323 12.466 3.71889L16.4244 11.3598L23.0197 7.38654C23.1985 7.27888 23.4233 7.28702 23.5937 7.40728C23.7641 7.52754 23.8471 7.73707 23.8056 7.94136L21.3156 20.1443C21.2652 20.3887 21.0501 20.5644 20.8007 20.5644Z" fill="orange"></path>
        </g>
    </g>
</g>
</svg>';
?>
<div class="mo2f-settings-div <?php echo esc_attr( $overlay_on_premium_features ); ?>">

	<div class="mo2f-settings-head">
	<label class="mo2f_checkbox_container">
		<input type="checkbox" id="mo2f_select_methods" <?php checked( MoWpnsUtility::get_mo2f_db_option( 'mo2f_enable_custom_poweredby', 'get_option' ) === '1' ); ?> onclick="mo2f_showSettings(this)"/><span class="mo2f-settings-checkmark"></span>
	</label>
		<span><?php esc_html_e( 'Use your own branding logo on 2FA login Popup', 'miniorange-2-factor-authentication' ); ?></span>	<?php echo $crown;//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Only a SVG, doesn't require escaping. ?>
	</div>
	<br><br>
	<div class="flex ml-mo-20">
		<div class="mo2f-settings-div">
		&nbsp;&nbsp;<button id="mo2f_enable2FA_save_button" class="mo2f-save-settings-button">Upload Image</button>
			<div class="mo2f-progress-container">
				<div class="progress-bar" id="progress-bar">0%</div>
			</div>
			<br>
			<div class="justify-end <?php echo $enable_2fa ? 'flex' : 'hidden'; ?>">
					<div class=" <?php echo esc_attr( $overlay_on_premium_features ); ?>">
					<button id="mo2f_upload_logo_save_button"  class="mo2f-save-settings-button"><?php esc_html_e( 'Save', 'miniorange-2-factor-authentication' ); ?></button>
					<button id="mo2f_upload_logo_reset_button"  class="mo2f-save-settings-button"><?php esc_html_e( 'Reset', 'miniorange-2-factor-authentication' ); ?></button>
					</div>
			</div>
		</div ><div class="ml-mo-16">
		<div>	<span><b>Preview</b></span>	</div>
		<div class="mo2f-settings-div">
		<img class="mo2f-miniorange-logo" src="<?php echo esc_url( plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'includes/images/miniorange-new-logo.png' ); ?>" alt="<?php esc_attr_e( 'miniOrange 2-factor Logo', 'miniorange-2-factor-authentication' ); ?>" >
		</div>
		</div>
	</div>
</div>

<div class="mo2f-settings-div <?php echo esc_attr( $overlay_on_premium_features ); ?>">

	<div class="mo2f-settings-head">
	<label class="mo2f_checkbox_container">
		<input type="checkbox" id="mo2f_enable_custom_popup" <?php checked( MoWpnsUtility::get_mo2f_db_option( 'mo2f_enable_login_popup_customization', 'get_option' ) === '1' ); ?> onclick="mo2f_showSettings(this)"/><span class="mo2f-settings-checkmark"></span>
	</label>
		<span><?php esc_html_e( 'Use custom 2FA login Popup', 'miniorange-2-factor-authentication' ); ?></span>	<?php echo $crown;//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Only a SVG, doesn't require escaping. ?>
	</div>

		<div class="ml-mo-20">
		<form name="f"  id="custom_css_form" method="post" action="">
			<input type="hidden" name="option" value="mo2f_login_popup_settings" />
			<input type="hidden" name="mo2f_whitelabelling_nonce" value="<?php echo esc_attr( wp_create_nonce( 'mo2f-whitelabelling-nonce' ) ); ?>"/>
				<table class="my-mo-3 w-3/4">
					<?php
					$mo2f_login_popup = array(
						'Background Color:'               => '	',
						'Popup Background Color:'         => 'mo2f_custom_popup_bg_color',
						'Button  Color:'                  => 'mo2f_custom_button_color',
						'Popup Message Text Color:'       => 'mo2f_custom_notif_text_color',
						'Popup Message Background Color:' => 'mo2f_custom_notif_bg_color',
						'OTP Token BackgroundColor:'      => 'mo2f_custom_otp_bg_color',
						'OTP Token Text Color:'           => 'mo2f_custom_otp_text_color',
						'Header TextColor:'               => 'mo2f_custom_header_text_color',
						'Middle Text Color:'              => 'mo2f_custom_middle_text_color',
						'Footer Text Color:'              => 'mo2f_custom_footer_text_color',

					);
					foreach ( $mo2f_login_popup as $color => $value ) {
						?>
					<tr>
					<td><b><?php esc_html_e( $color, 'miniorange-2-factor-authentication' );  //phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- The $text is a single string literal ?> </b></td>
					<td><input type="text" id="<?php esc_attr( $value ); ?>" name="<?php esc_attr( $value ); ?>"
					value="<?php echo esc_attr( get_site_option( $value ) ); ?>" class="my-color-field" /> </td>
					</tr>
						<?php

					}
					?>
				<tr>
					<td><b><?php esc_html_e( 'Popup Background Image URL:', 'miniorange-2-factor-authentication' ); ?></b></td> &nbsp;
					<td> <input type="text" class="mo2f_table_textbox" style="width:93% !important;float:left;" name="mo2f_background_image" placeholder="<?php esc_html_e( 'Enter the url of the background image', 'miniorange-2-factor-authentication' ); ?>" 
					id="mo2f_background_image"  value="<?php echo esc_attr( get_site_option( 'mo2f_background_image' ) ); ?>"  /></td>
				</tr>
				</table>
				<br>
				<div class="text-mo-tertiary-txt"><b><?php esc_html_e( 'Note:', 'miniorange-2-factor-authentication' ); ?></b> <?php esc_html_e( 'Popup Background Image will be updated only if Popup Background Color is clear or not selected.', 'miniorange-2-factor-authentication' ); ?></div>
				</br>	
				<div class="justify-start <?php echo $enable_2fa ? 'flex' : 'hidden'; ?>">
					<div class=" <?php echo esc_attr( $overlay_on_premium_features ); ?>">
					<button id="mo2f_login_popup_save_button"  class="mo2f-save-settings-button"><?php esc_html_e( 'Save Settings', 'miniorange-2-factor-authentication' ); ?></button>
					<button id="mo2f_login_popup_reset_button"  class="mo2f-save-settings-button"><?php esc_html_e( 'Reset Settings', 'miniorange-2-factor-authentication' ); ?></button>
					</div>
				</div>
		</form>
	</div>
</div>
<script>
	jQuery('#loginpopup').addClass('mo2f-subtab-active');
	jQuery("#mo_2fa_white_labelling").addClass("side-nav-active");
</script>
