<?php
/**
 * This file shows the plugin settings on frontend.
 *
 * @package miniorange-2-factor-authentication/views/2faconfigurations/advancedfeatures
 */

use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Helper\Mo2f_Common_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="mo2f-setting-row mo2f-basic-plan">
	<div class="mo2f-setting-main">
		<span class="mo2f-setting-title">
			<?php esc_html_e( 'Custom Login URL', 'miniorange-2-factor-authentication' ); ?>
			<?php echo Mo2f_Common_Helper::mo2f_check_plan( 'basic', MoWpnsConstants::MO2F_PREMIUM_3PLAN_NAME ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Only a SVG, doesn't require escaping. ?>
		</span>
	</div>
	<div class="mo2f-setting-control">
		<label class="mo2f_checkbox_container">
			<input type="checkbox" id="mo2f_enable_custom_login_url" name="mo2f_enable_custom_login_url" <?php checked( get_site_option( 'mo2f_enable_custom_login_url' ) ); ?>/>
			<span class="mo2f-settings-checkmark"></span>
		</label>
		<p class="mo2f-setting-desc">
			<?php
				printf(
				/* Translators: %s: bold tags */
					esc_html( __( 'Change the \'%1$1sLogin Page URL%2$12s\'', 'miniorange-2-factor-authentication' ) ), //phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- The $text is a single string literal
					'<b>',
					'</b>',
				);
				?>
		</p>
	</div>
</div>
<div class="mo2f-sub-settings-div mo2f-sub-panel mo2f-basic-plan flex" id="mo2f_custom_login_url_content">
	<div class="ml-mo-24">
			<span><?php esc_html_e( 'Choose a custom URL for your login page:', 'miniorange-2-factor-authentication' ); ?></span><br>
			<span class="text-mo-tertiary-txt"><?php echo esc_url( home_url( '/' ) ); ?></span>
			<input type="text" class="mo2f-redirection-field" name="mo2f_custom_login_url_slug" placeholder="my-login" value="<?php echo esc_attr( get_site_option( 'mo2f_custom_login_url_slug' ) ); ?>"><br>
	</div>
	<br>
	<div class="text-mo-tertiary-txt ml-mo-24"><b><?php esc_html_e( 'Note:', 'miniorange-2-factor-authentication' ); ?></b> <?php esc_html_e( 'Requires Pretty Permalinks. The new URL is verified before being saved, so you are never locked out.', 'miniorange-2-factor-authentication' ); ?></div>
	<br>
	<div class="justify-start mo2f-basic-plan" style="padding:0 18px 16px;">
		<button id="mo2f_custom_login_url_save_button" class="mo2f-save-settings-button"><?php esc_html_e( 'Save Settings', 'miniorange-2-factor-authentication' ); ?></button>
	</div>
</div>
