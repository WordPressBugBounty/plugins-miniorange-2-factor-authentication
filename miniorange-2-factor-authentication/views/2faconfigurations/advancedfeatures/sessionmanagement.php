<?php
/**
 * This file shows the plugin settings on frontend.
 *
 * @package miniorange-2-factor-authentication/views/whitelabelling/
 */

use TwoFA\Helper\MoWpnsUtility;
use TwoFA\Helper\MoWpnsConstants;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="mo2f-settings-div mo2f-enterprise-plan">
<div class="mo2f-settings-head">
		<span><?php esc_html_e( 'Session Management', 'miniorange-2-factor-authentication' ); ?></span><?php echo MoWpnsConstants::PREMIUM_CROWN; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Only a SVG, doesn't require escaping. ?>
	</div>
	<br>
	<div class="ml-mo-16">
	<label class="mo2f_checkbox_container">
		<input type="checkbox" id="mo2f_sesssion_restriction" name="mo2f_sesssion_restriction" <?php checked( get_site_option( 'mo2f_sesssion_restriction' ) ); ?>/>
		<span>
			<?php
				printf(
				/* Translators: %s: bold tags */
					esc_html( __( 'Limit \'%1$1sSimultaneous Sessions%2$12s\'', 'miniorange-2-factor-authentication' ) ), //phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- The $text is a single string literal
					'<b>',
					'</b>',
				);
				?>
		</span>
	</label>
		<br><br>
	</div>
	<div id="mo2f_simultaneous_sessions_content">
	<div class="ml-mo-24">
			<span><?php esc_html_e( 'Enter the maximum simultaneous sessions allowed:', 'miniorange-2-factor-authentication' ); ?></span>
			<input type="number" class="mo2f-settings-number-field" name= "mo2fa_simultaneous_session_allowed" value="<?php echo esc_attr( get_site_option( 'mo2f_maximum_allowed_session', 1 ) ); ?>" min=0><br>
	</div>
	<br>
	<div class="ml-mo-24">
		<span><?php esc_html_e( 'What happens when user\'s session limit is reached?', 'miniorange-2-factor-authentication' ); ?></span>	
	</div>
	<br>
	<div class="mo2f-settings-items ml-mo-20">
		<div class="mr-mo-4">
			<input type="radio" name="mo2f_enable_simultaneous_session" value="allow_access" <?php checked( get_site_option( 'mo2f_session_allowed_type', 'allow_access' ) === 'allow_access' ); ?>>
			<?php esc_html_e( 'Allow Access', 'miniorange-2-factor-authentication' ); ?>
		</div>
			&nbsp;&nbsp;
		<div class="mr-mo-4">
			<input type="radio" name="mo2f_enable_simultaneous_session" value="deny_access" <?php checked( get_site_option( 'mo2f_session_allowed_type' ) === 'deny_access' ); ?>>
			<?php esc_html_e( 'Deny Access', 'miniorange-2-factor-authentication' ); ?>
		</div>
	</div>
	<br>
	<div class="text-mo-tertiary-txt ml-mo-24"><b><?php esc_html_e( 'Note:', 'miniorange-2-factor-authentication' ); ?></b> <?php esc_html_e( '\'Allow access\' will allow user to login but terminate all other active session when the limit reached. \'Deny access\' will not allow users to login when the limit is reached.', 'miniorange-2-factor-authentication' ); ?></div>
	</div>
	<br>


	<div class="ml-mo-16">
	<label class="mo2f_checkbox_container">
		<input type="checkbox" id="mo2f_session_logout_time_enable" name="mo2f_session_logout_time_enable" <?php checked( get_site_option( 'mo2f_session_logout_time_enable' ) === '1' ); ?>/>
	</label>
	<span>
	<?php
		printf(
					/* Translators: %s: bold tags */
			esc_html( __( 'Limit \'%1$1sSession Time%2$12s\'', 'miniorange-2-factor-authentication' ) ), //phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- The $text is a single string literal
			'<b>',
			'</b>',
		);
		?>
				</span>
			<br><br>
	</div>
	<div class="ml-mo-24" id="mo2f_session_expiry_time_content">
			<span>
			<?php
				printf(
					/* Translators: %s: bold tags */
					esc_html( __( 'Enter the number of %1$1shours%2$12s for which a session should be allowed:', 'miniorange-2-factor-authentication' ) ), //phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- The $text is a single string literal
					'<b>',
					'</b>',
				);
				?>
		</span>
			<input type="number" class="mo2f-settings-number-field" name="mo2f_number_of_timeout_hours" value="<?php echo esc_attr( get_site_option( 'mo2f_number_of_timeout_hours', 24 ) ); ?>" min=0 max=336><br>
	</div>
	<br>
	<div class="justify-start ml-mo-16">
		<div class="mo2f-enterprise-plan">
		<button id="mo2f_session_restriction_save_button"  class="mo2f-save-settings-button"><?php esc_html_e( 'Save Settings', 'miniorange-2-factor-authentication' ); ?></button>
		</div>
	</div>
</div>
