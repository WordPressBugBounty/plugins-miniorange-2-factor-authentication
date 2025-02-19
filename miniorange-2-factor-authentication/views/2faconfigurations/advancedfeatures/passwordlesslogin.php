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
?>
<div class="mo2f-settings-div mo2f-enterprise-plan">
	<div class="mo2f-settings-head">
		<span><?php esc_html_e( 'Passwordless Login with 2FA', 'miniorange-2-factor-authentication' ); ?></span><?php echo MoWpnsConstants::PREMIUM_CROWN; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Only a SVG, doesn't require escaping. ?>
	</div>
	<br>
	<div class="ml-mo-16">
	<span><b><?php esc_html_e( 'Select Login Options', 'miniorange-2-factor-authentication' ); ?></b></span><br>
	</div>
	<br>
	<div class="mo2f-settings-items ml-mo-20">
		<div class="mr-mo-4">
			<input type="radio" name="mo2f_login_option" id="mo2f_with_password" value="1" <?php checked( get_site_option( 'mo2f_login_option', 1 ) ); ?>>
			<?php esc_html_e( 'Username + Password + 2FA', 'miniorange-2-factor-authentication' ); ?>
			(<span class="text-mo-blue-txt"><?php esc_html_e( 'Recommended', 'miniorange-2-factor-authentication' ); ?></span>)
		</div>
	</div>
	<br>
	<br>
	<div class="mo2f-settings-items ml-mo-20">
		<div class="mr-mo-4">
			<input type="radio" name="mo2f_login_option" id="mo2f_without_password" value="0" <?php checked( get_site_option( 'mo2f_login_option' ) === '0' ); ?>>
			<?php esc_html_e( 'Username + 2FA', 'miniorange-2-factor-authentication' ); ?>
			(<span class="text-mo-blue-txt"><?php esc_html_e( 'No password required', 'miniorange-2-factor-authentication' ); ?></span>) &nbsp;<a class="btn-link" data-toggle="collapse" id="mo2f-showpreview" href="#mo2f-preview1" aria-expanded="false"><?php esc_html_e( $lv_needed ? 'Show Preview' : 'Hide Preview', 'miniorange-2-factor-authentication' ); //phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- The $text is a single string literal ?></a>
		</div>
	</div>
	<br>
	<div class="text-mo-tertiary-txt ml-mo-29" > 
		<?php
				printf(
					/* Translators: %s: bold tags */
					esc_html( __( '%1$1sNote:%2$12s If you do not want to remember password anymore and login with 2nd factor, please check this option.', 'miniorange-2-factor-authentication' ) ), //phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- The $text is a single string literal
					'<b>',
					'</b>',
				);
				?>
	</div>
	<div id="mo2f-pwdless-login-content">
	<div class="ml-mo-30">
	<br>
	<label class="mo2f_checkbox_container">
		<input type="checkbox" id="mo2f_enable_passwordless_login" <?php checked( get_site_option( 'mo2f_show_loginwith_phone', $lv_needed ? 0 : 1 ) ); ?>/>	<span><?php esc_html_e( 'I want to hide default login form', 'miniorange-2-factor-authentication' ); ?></span>
	</label>
		<br>
	</div>
	<br>
	<div class="text-mo-tertiary-txt ml-mo-38"> <?php esc_html_e( 'Note: Checking this option will hide default login form and will only show the Login with 2-factor form. Click on \'Show Preview\' link to see the preview.', 'miniorange-2-factor-authentication' ); ?></div>
	</div> 
	<div class="mo2f_collapse ml-mo-80 <?php echo ( esc_attr( $lv_needed ) ? 'hidden' : '' ); ?>" id="mo2f-preview2">
	<br>	
		<img  class="h-mo-60" src="<?php echo esc_url( plugin_dir_url( dirname( dirname( dirname( __FILE__ ) ) ) ) . 'includes/images/passwordless_login.png' ); ?>" alt="<?php esc_attr_e( 'Passwordless login preview', 'miniorange-2-factor-authentication' ); ?>" >
	</div>
	<div class="mo2f_collapse ml-mo-80 <?php echo ( esc_attr( $lv_needed ) ? 'hidden' : '' ); ?>" id="mo2f-preview1">
		<br>
		<img class="h-mo-80" src="<?php echo esc_url( plugin_dir_url( dirname( dirname( dirname( __FILE__ ) ) ) ) . 'includes/images/password_login.png' ); ?>" alt="<?php esc_attr_e( 'Passwordless login preview', 'miniorange-2-factor-authentication' ); ?>" >
		<br>
	</div> 
	<br><br>
	<div class="justify-start ml-mo-16">
		<div class="mo2f-enterprise-plan">
		<button id="mo2f_passwordless_login_save_button"  class="mo2f-save-settings-button"><?php esc_html_e( 'Save Settings', 'miniorange-2-factor-authentication' ); ?></button>
		</div>
	</div>
</div>
