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
		<span><?php esc_html_e( 'Remember Device To Bypass 2FA', 'miniorange-2-factor-authentication' ); ?></span><?php echo $crown; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Only a SVG, doesn't require escaping. ?>
	</div>
	<br>
	<div class="ml-mo-16">
	<label class="mo2f_checkbox_container">
		<input type="checkbox" id="mo2f_select_methods" <?php checked( MoWpnsUtility::get_mo2f_db_option( 'mo2f_remember_device', 'get_option' ) === '1' ); ?> onclick="mo2f_showSettings(this)"/>
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
			esc_html( __( '%1$1sNote:%2$12s This can be enabled only when \'Username + 2FA\' login option is selected in the \'Passwordless 2FA\' settings under the \'Passwordless Login\' tab.', 'miniorange-2-factor-authentication' ) ), //phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- The $text is a single string literal
			'<b>',
			'</b>',
		);
		?>
		</div>
	<br>
	<div class="mo2f-settings-items ml-mo-20">
		<div class="mr-mo-4">
			<input type="radio" name="mo2f_enable_rba_types" id="mo2f_block_users" value="0" <?php checked( MoWpnsUtility::get_mo2f_db_option( 'mo2f_enable_rba_types', 'get_option' ) === '0' ); ?>>
			<?php
				printf(
					/* Translators: %s: bold tags */
					esc_html( __( 'Give users an option to enanble %1$1s\'Remember Device\'%2$2s', 'miniorange-2-factor-authentication' ) ), //phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- The $text is a single string literal
					'<b>',
					'</b>',
				);
				?>
		</div>
	</div>
	<br>
	<div class="mo2f-settings-items ml-mo-20">
		<div class="mr-mo-4">
			<input type="radio" name="mo2f_enable_rba_types" id="mo2f_enforce_2fa" value="1" <?php checked( MoWpnsUtility::get_mo2f_db_option( 'mo2f_enable_rba_types', 'get_option' ) === '1' ); ?>>
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
			<span><?php esc_html_e( 'Allow', 'miniorange-2-factor-authentication' ); ?></span>
			&nbsp;&nbsp;<input type="number" class="mo2f-settings-radio" name= "mo2fa_device_limit" value="<?php echo esc_attr( get_site_option( 'mo2f_device_limit', 1 ) ); ?>" min=0 max=336>
			&nbsp;&nbsp;<span><?php esc_html_e( 'devices for users to remember', 'miniorange-2-factor-authentication' ); ?></span>
	</div>
	<br>
	<div class="mo2f-settings-items ml-mo-20">
		<span><b><?php esc_html_e( 'Action on exceeding device limit', 'miniorange-2-factor-authentication' ); ?></b></span>
		&nbsp;&nbsp;&nbsp;&nbsp;<div class="mr-mo-4">
			<input type="radio" name="mo2f_rba_login_limit" id="mo2f_block_users" value="1" <?php checked( MoWpnsUtility::get_mo2f_db_option( 'mo2f_action_rba_limit_exceed', 'get_option' ) === '1' ); ?>>
			<?php esc_html_e( 'Ask for Two Factor', 'miniorange-2-factor-authentication' ); ?>
		</div>
			&nbsp;&nbsp;
		<div class="mr-mo-4">
			<input type="radio" name="mo2f_rba_login_limit" id="mo2f_enforce_2fa" value="0" <?php checked( MoWpnsUtility::get_mo2f_db_option( 'mo2f_action_rba_limit_exceed', 'get_option' ) === '0' ); ?>>
			<?php esc_html_e( 'Deny Access', 'miniorange-2-factor-authentication' ); ?>
		</div>
	</div>
	<br>
	<div class="text-mo-tertiary-txt ml-mo-16"> 
		<?php
				printf(
					/* Translators: %s: bold tags */
					esc_html( __( '%1$1sNote:%2$12s Checking this option will enable %3$3s\'Remember Device\'%4$4s.  In the login from the same device, user will bypass 2nd factor i.e user will be logged in through username + password only.', 'miniorange-2-factor-authentication' ) ), //phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- The $text is a single string literal
					'<b>',
					'</b>',
					'<b>',
					'</b>',
				);
				?>
	</div>
	<br>	
	<div class="justify-start <?php echo $enable_2fa ? 'flex' : 'hidden'; ?> ml-mo-16">
		<div class=" <?php echo esc_attr( $overlay_on_premium_features ); ?>">
		<button id="mo2f_rba_save_button"  class="mo2f-save-settings-button"><?php esc_html_e( 'Save Settings', 'miniorange-2-factor-authentication' ); ?></button>
		</div>
	</div>
</div>
<script>
	jQuery('#rememberdevice').addClass('mo2f-subtab-active');
	jQuery("#mo_2fa_advance_settings").addClass("side-nav-active");
</script>
