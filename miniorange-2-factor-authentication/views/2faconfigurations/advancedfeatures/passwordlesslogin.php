<?php
/**
 * Description: This file is used to show Passwordless login feature.
 *
 * @package miniorange-2-factor-authentication/views/2faconfigurations/advancedfeatures
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Helper\Mo2f_Common_Helper;

$mo2f_lv_needed = apply_filters( 'mo2f_is_lv_needed', false );
?>
<div class="mo2f-setting-row mo2f-enterprise-plan">
	<div class="mo2f-setting-main">
		<span class="mo2f-setting-title">
			<?php esc_html_e( 'Passwordless Login with 2FA', 'miniorange-2-factor-authentication' ); ?>
			<?php echo Mo2f_Common_Helper::mo2f_check_plan( 'enterprise', MoWpnsConstants::MO2F_PREMIUM_2PLAN_NAME ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Only a SVG, doesn't require escaping. ?>
		</span>
	</div>
	<div class="mo2f-setting-control">
		<b><?php esc_html_e( 'Select Login Options', 'miniorange-2-factor-authentication' ); ?></b>
		<div class="mo2f-settings-items" style="margin-top:10px;">
			<div class="mr-mo-4">
				<input type="radio" name="mo2f_login_option" id="mo2f_with_password" value="1" <?php checked( (int) get_site_option( 'mo2f_login_option', 1 ), 1 ); ?>>
				<?php esc_html_e( 'Username + Password + 2FA', 'miniorange-2-factor-authentication' ); ?>
				(<span class="text-mo-blue-txt"><?php esc_html_e( 'Recommended', 'miniorange-2-factor-authentication' ); ?></span>)
			</div>
		</div>
		<br>
		<div class="mo2f-settings-items">
			<div class="mr-mo-4">
				<input type="radio" name="mo2f_login_option" id="mo2f_without_password" value="0" <?php checked( (int) get_site_option( 'mo2f_login_option', 1 ), 0 ); ?>>
				<?php esc_html_e( 'Username + 2FA', 'miniorange-2-factor-authentication' ); ?>
				(<span class="text-mo-blue-txt"><?php esc_html_e( 'No password required', 'miniorange-2-factor-authentication' ); ?></span>) &nbsp;<a class="btn-link" data-toggle="collapse" id="mo2f-showpreview" href="#mo2f-preview1" aria-expanded="false"><?php esc_html_e( $mo2f_lv_needed ? 'Show Preview' : 'Hide Preview', 'miniorange-2-factor-authentication' ); //phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- The $text is a single string literal ?></a>
			</div>
		</div>
		<p class="mo2f-setting-desc"><?php esc_html_e( 'If you do not want to remember password anymore and login with 2nd factor, please check this option.', 'miniorange-2-factor-authentication' ); ?></p>
		<div id="mo2f-pwdless-login-content" style="margin-top:10px;">
			<label class="mo2f_checkbox_container">
				<input type="checkbox" id="mo2f_enable_passwordless_login" <?php checked( get_site_option( 'mo2f_show_loginwith_phone', $mo2f_lv_needed ? 0 : 1 ) ); ?>/>
				<span class="mo2f-settings-checkmark"></span>
				<span><?php esc_html_e( 'I want to hide default login form', 'miniorange-2-factor-authentication' ); ?></span>
			</label>
			<p class="mo2f-setting-desc"><?php esc_html_e( 'Checking this option will hide default login form and will only show the Login with 2-factor form. Click on \'Show Preview\' link to see the preview.', 'miniorange-2-factor-authentication' ); ?></p>
		</div>
		<div style="display:flex;flex-wrap:wrap;align-items:flex-start;gap:16px;">
			<div class="mo2f_collapse <?php echo ( esc_attr( $mo2f_lv_needed ) ? 'hidden' : '' ); ?>" id="mo2f-preview2">
				<img  class="h-mo-60" src="<?php echo esc_url( plugin_dir_url( dirname( dirname( __DIR__ ) ) ) . 'includes/images/passwordless_login.png' ); ?>" alt="<?php esc_attr_e( 'Passwordless login preview', 'miniorange-2-factor-authentication' ); ?>" >
			</div>
			<div class="mo2f_collapse <?php echo ( esc_attr( $mo2f_lv_needed ) ? 'hidden' : '' ); ?>" id="mo2f-preview1">
				<img class="h-mo-80" src="<?php echo esc_url( plugin_dir_url( dirname( dirname( __DIR__ ) ) ) . 'includes/images/password_login.png' ); ?>" alt="<?php esc_attr_e( 'Passwordless login preview', 'miniorange-2-factor-authentication' ); ?>" >
			</div>
		</div>
	</div>
</div>
<div class="justify-start mo2f-enterprise-plan" style="padding:0 18px 16px;">
	<button id="mo2f_passwordless_login_save_button"  class="mo2f-save-settings-button"><?php esc_html_e( 'Save Settings', 'miniorange-2-factor-authentication' ); ?></button>
</div>
