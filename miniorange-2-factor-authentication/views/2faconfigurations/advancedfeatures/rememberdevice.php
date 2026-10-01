<?php
/**
 * Description: Shows remember device settings UI.
 *
 * @package miniorange-2-factor-authentication/views/advancedsettings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use TwoFA\Helper\MoWpnsUtility;
use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Helper\Mo2f_Common_Helper;
?>
<div class="mo2f-setting-row">
	<div class="mo2f-setting-main">
		<span class="mo2f-setting-title"><?php esc_html_e( 'Remember Device to Bypass 2FA', 'miniorange-2-factor-authentication' ); ?></span>
	</div>
	<div class="mo2f-setting-control">
		<label class="mo2f_checkbox_container"><input type="checkbox" id="mo2f_enable_trusted_device" onclick="mo2f_showSettings(this)" <?php checked( $mo2f_enable_trusted_device ); ?>/><span class="mo2f-settings-checkmark"></span></label>
		<p class="mo2f-setting-desc">
			<?php esc_html_e( 'After a user completes 2FA successfully, their browser is remembered for 7 days and they will not be asked for a second factor again on it until then. Every device is trusted the same way automatically, with no per-user limit.', 'miniorange-2-factor-authentication' ); ?>
			&nbsp;<a href="<?php echo esc_url( admin_url( 'admin.php?page=mo_2fa_reports&subpage=remembereddevices' ) ); ?>" target="_blank"><?php esc_html_e( 'View Remembered Devices', 'miniorange-2-factor-authentication' ); ?></a>
		</p>
	</div>
</div>
<div class="mo2f-sub-settings-div mo2f-sub-panel mo2f-enterprise-plan flex" id="mo2f_enable_trusted_device_settings">
	<div class="mo2f-setting-title" style="margin-bottom:8px;">
		<?php esc_html_e( 'Enterprise: per-login consent, configurable expiry & device limits', 'miniorange-2-factor-authentication' ); ?>
		<?php echo Mo2f_Common_Helper::mo2f_check_plan( 'enterprise', MoWpnsConstants::MO2F_PREMIUM_2PLAN_NAME ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Only a SVG, doesn't require escaping. ?>
	</div>
	<div id="mo2f-rba-content">
		<div class="mo2f-settings-items ml-mo-20">
			<div class="mr-mo-4">
				<input type="radio" name="mo2f_enable_rba_types" id="mo2f_block_users" value="1" <?php checked( (int) get_site_option( 'mo2f_enable_rba_types', 1 ) ); ?>>
				<?php
					printf(
						/* Translators: %s: bold tags */
						esc_html( __( 'Give users an option to enable %1$1s\'Remember Device\'%2$2s', 'miniorange-2-factor-authentication' ) ), //phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- The $text is a single string literal
						'<b>',
						'</b>',
					);
					?>
			</div>
		</div>
		<br>
		<div class="mo2f-settings-items ml-mo-20">
			<div class="mr-mo-4">
				<input type="radio" name="mo2f_enable_rba_types" id="mo2f_enforce_2fa" value="0" <?php checked( ! (int) get_site_option( 'mo2f_enable_rba_types' ) ); ?>>
				<?php
					printf(
						/* Translators: %s: bold tags */
						esc_html( __( 'Silently enable %1$1s\'Remember Device\'%2$2s', 'miniorange-2-factor-authentication' ) ), //phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- The $text is a single string literal
						'<b>',
						'</b>',
					);
					?>
			</div>
		</div>
		<br>
		<br>
		<div class="mo2f-settings-items ml-mo-20">
				<span><?php esc_html_e( 'Remember device for', 'miniorange-2-factor-authentication' ); ?></span>
				&nbsp;&nbsp;<input type="number" class="mo2f-settings-radio" name= "mo2fa_device_expiry" value="<?php echo esc_attr( get_site_option( 'mo2f_device_expiry', 1 ) ); ?>" min=0 max=336>
				&nbsp;&nbsp;<span><?php esc_html_e( 'days', 'miniorange-2-factor-authentication' ); ?></span>
		</div>
		<br>
		<div class="mo2f-settings-items ml-mo-20">
				<span><?php esc_html_e( 'Allow maximum', 'miniorange-2-factor-authentication' ); ?></span>
				&nbsp;&nbsp;<input type="number" class="mo2f-settings-radio" name= "mo2fa_device_limit" value="<?php echo esc_attr( get_site_option( 'mo2f_device_limit', 1 ) ); ?>" min=0 max=336>
				&nbsp;&nbsp;<span><?php esc_html_e( 'devices per user to remember', 'miniorange-2-factor-authentication' ); ?></span>
		</div>
		<br>
		<div class="mo2f-settings-items ml-mo-20">
			<span><b><?php esc_html_e( 'Action on exceeding device limit', 'miniorange-2-factor-authentication' ); ?></b></span>
			&nbsp;&nbsp;&nbsp;&nbsp;<div class="mr-mo-4">
				<input type="radio" name="mo2f_rba_login_limit" id="mo2f_block_users_limit_exceed" value="1" <?php checked( (int) get_site_option( 'mo2f_action_rba_limit_exceed', 1 ) ); ?>>
				<?php esc_html_e( 'Ask for Two Factor', 'miniorange-2-factor-authentication' ); ?>
			</div>
				&nbsp;&nbsp;
			<div class="mr-mo-4">
				<input type="radio" name="mo2f_rba_login_limit" id="mo2f_enforce_2fa_limit_exceed" value="0" <?php checked( ! (int) get_site_option( 'mo2f_action_rba_limit_exceed', 0 ) ); ?>>
				<?php esc_html_e( 'Deny Access', 'miniorange-2-factor-authentication' ); ?>
			</div>
		</div>
	</div>
	<div class="justify-start" style="margin-top:14px;">
		<div class="mo2f-enterprise-plan">
		<button id="mo2f_rba_save_button"  class="mo2f-save-settings-button"><?php esc_html_e( 'Save Settings', 'miniorange-2-factor-authentication' ); ?></button>
		</div>
	</div>
</div>
