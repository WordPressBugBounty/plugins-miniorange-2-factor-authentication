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
?>
<div class="mo2f-settings-div mo2f-enterprise-plan">
	<div class="mo2f-settings-head">
		<span><?php esc_html_e( 'Remember Device to Bypass 2FA', 'miniorange-2-factor-authentication' ); ?></span><?php echo MoWpnsConstants::PREMIUM_CROWN; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Only a SVG, doesn't require escaping. ?>
		<span class="mo2f-rba-link">[<a  href="<?php echo esc_url( admin_url( 'admin.php?page=mo_2fa_reports&subpage=remembereddevices' ) ); ?>" target="_blank"><?php esc_html_e( 'View Remembered Devices', 'miniorange-2-factor-authentication' ); ?></a>]</span>
	</div>
	<br>
	<div class="ml-mo-16">
	<label class="mo2f_checkbox_container">
		<input type="checkbox" id="mo2f_check_rba" <?php checked( get_site_option( 'mo2f_remember_device' ) === '1' ); ?>/>
	</label>

			<span>
			<?php
				printf(
					/* Translators: %s: bold tags */
					esc_html( __( 'Enable %1$1s\'Remember Device\'%2$2s Option', 'miniorange-2-factor-authentication' ) ), //phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- The $text is a single string literal
					'<b>',
					'</b>',
				);
				?>
				</span><br><br>

	</div>
	<div class="text-mo-tertiary-txt ml-mo-22"> 
		<?php
				printf(
					/* Translators: %s: bold tags */
					esc_html( __( '%1$1sNote:%2$12s Checking this option will enable %3$3s\'Remember Device\'%4$4s. When login from the same device which user has allowed to remember, user will bypass 2nd factor i.e user will be able to login through \'username\' + \'password\' only.', 'miniorange-2-factor-authentication' ) ), //phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- The $text is a single string literal
					'<b>',
					'</b>',
					'<b>',
					'</b>',
				);
				?>
	</div>
	<br>
	<div id="mo2f-rba-content">
	<div class="mo2f-settings-items ml-mo-20">
		<div class="mr-mo-4">
			<input type="radio" name="mo2f_enable_rba_types" id="mo2f_block_users" value="1" <?php checked( get_site_option( 'mo2f_enable_rba_types', '1' ) ); ?>>
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
			<input type="radio" name="mo2f_enable_rba_types" id="mo2f_enforce_2fa" value="0" <?php checked( get_site_option( 'mo2f_enable_rba_types' ) === '0' ); ?>>
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
			<input type="radio" name="mo2f_rba_login_limit" id="mo2f_block_users" value="1" <?php checked( get_site_option( 'mo2f_action_rba_limit_exceed', '1' ) === '1' ); ?>>
			<?php esc_html_e( 'Ask for Two Factor', 'miniorange-2-factor-authentication' ); ?>
		</div>
			&nbsp;&nbsp;
		<div class="mr-mo-4">
			<input type="radio" name="mo2f_rba_login_limit" id="mo2f_enforce_2fa" value="0" <?php checked( get_site_option( 'mo2f_action_rba_limit_exceed', '0' ) === '0' ); ?>>
			<?php esc_html_e( 'Deny Access', 'miniorange-2-factor-authentication' ); ?>
		</div>
	</div>
	</div>
	<br>
	<div class="justify-start ml-mo-16">
		<div class="mo2f-enterprise-plan">
		<button id="mo2f_rba_save_button"  class="mo2f-save-settings-button"><?php esc_html_e( 'Save Settings', 'miniorange-2-factor-authentication' ); ?></button>
		</div>
	</div>
</div>
<script>
	jQuery('#advancedfeatures').addClass('mo2f-subtab-active');
	jQuery("#mo_2fa_two_fa").addClass("side-nav-active");
</script>
