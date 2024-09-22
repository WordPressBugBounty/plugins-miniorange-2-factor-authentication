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
		<span><?php esc_html_e( 'Session Restriction', 'miniorange-2-factor-authentication' ); ?></span><?php echo $crown; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Only a SVG, doesn't require escaping. ?>
	</div>
	<br>
	<div class="ml-mo-16">
	<label class="mo2f_checkbox_container">
		<input type="checkbox" id="mo2f_sesssion_restriction" <?php checked( MoWpnsUtility::get_mo2f_db_option( 'mo2f_sesssion_restriction', 'get_option' ) === '1' ); ?> onclick="mo2f_showSettings(this)"/><span><?php esc_html_e( 'Limit \'Simultaneous Sessions\'', 'miniorange-2-factor-authentication' ); ?></span>
	</label>
		<br><br>
	</div>
	<div class="ml-mo-24">
			<span><?php esc_html_e( 'Enter the maximum simultaneous sessions allowed:', 'miniorange-2-factor-authentication' ); ?></span>
			<input type="number" class="mo2f-settings-radio" name= "mo2fa_simultaneous_session_allowed" value="<?php echo esc_attr( get_option( 'mo2f_maximum_allowed_session', 1 ) ); ?>" min=0 max=10><br>
	</div>
	<br>
	<div class="ml-mo-24">
		<span><?php esc_html_e( 'What happens when my session limit is reached?', 'miniorange-2-factor-authentication' ); ?></span>	
	</div>
	<br>
	<div class="mo2f-settings-items ml-mo-20">
		<div class="mr-mo-4">
			<input type="radio" name="mo2f_enable_simultaneous_session" id="mo2f_block_users" value="1" <?php checked( MoWpnsUtility::get_mo2f_db_option( 'mo2f_session_allowed_type', 'get_option' ) === '1' ); ?>>
			<?php esc_html_e( 'Allow Access', 'miniorange-2-factor-authentication' ); ?>
		</div>
			&nbsp;&nbsp;
		<div class="mr-mo-4">
			<input type="radio" name="mo2f_enable_simultaneous_session" id="mo2f_enforce_2fa" value="0" <?php checked( MoWpnsUtility::get_mo2f_db_option( 'mo2f_session_allowed_type', 'get_option' ) === '0' ); ?>>
			<?php esc_html_e( 'Deny Access', 'miniorange-2-factor-authentication' ); ?>
		</div>
	</div>
	<br>
	<div class="text-mo-tertiary-txt ml-mo-24"><b><?php esc_html_e( 'Note:', 'miniorange-2-factor-authentication' ); ?></b> <?php esc_html_e( '\'Allow access\' will allow user to login but terminate all other active session when the limit reached. Disable access will not all users to login when the limit is reached.', 'miniorange-2-factor-authentication' ); ?></div>
	<br>
	<div class="justify-start <?php echo $enable_2fa ? 'flex' : 'hidden'; ?> ml-mo-16">
		<div class=" <?php echo esc_attr( $overlay_on_premium_features ); ?>">
		<button id="mo2f_session_restriction_save_button"  class="mo2f-save-settings-button"><?php esc_html_e( 'Save Settings', 'miniorange-2-factor-authentication' ); ?></button>
		</div>
	</div>
</div>

<div class="mo2f-settings-div <?php echo esc_attr( $overlay_on_premium_features ); ?>">
	<div class="mo2f-settings-head">
		<span><?php esc_html_e( 'Limit Session Time', 'miniorange-2-factor-authentication' ); ?></span>	<?php echo $crown; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Only a SVG, doesn't require escaping. ?>
	</div>
	<br>
	<div class="ml-mo-16">
	<label class="mo2f_checkbox_container">
		<input type="checkbox" id="mo2f_session_logout_time_enable" name="mo2f_session_logout_time_enable" <?php checked( MoWpnsUtility::get_mo2f_db_option( 'mo2f_session_logout_time_enable', 'get_option' ) === '1' ); ?> onclick="mo2f_showSettings(this)"/>
	</label>
	<span><?php esc_html_e( 'Enable \'Session Time\' limit', 'miniorange-2-factor-authentication' ); ?></span>
			<br><br>
	</div>
	<div class="ml-mo-24">
			<span><?php esc_html_e( 'Enter the number of hours for which session should be allowed:', 'miniorange-2-factor-authentication' ); ?></span>
			<input type="number" class="mo2f-settings-radio" name= "mo2f_number_of_timeout_hours" value="<?php echo esc_attr( get_site_option( 'mo2f_number_of_timeout_hours', 24 ) ); ?>" min=0 max=336><br>
	</div>
	<br>
	<div class="text-mo-tertiary-txt ml-mo-16"><b><?php esc_html_e( 'Note:', 'miniorange-2-factor-authentication' ); ?></b> <?php esc_html_e( 'This will allow you to set a time limit on the user\'s session. After that time, the user would be logged out and will be required to login again. You can set the time limit after which users will get expired.', 'miniorange-2-factor-authentication' ); ?></div>
	<br>
	<div class="justify-start <?php echo $enable_2fa ? 'flex' : 'hidden'; ?> ml-mo-16">
		<div class=" <?php echo esc_attr( $overlay_on_premium_features ); ?>">
		<button id="mo2f_session_time_limit_save_button"  class="mo2f-save-settings-button"><?php esc_html_e( 'Save Settings', 'miniorange-2-factor-authentication' ); ?></button>
		</div>
	</div>
</div>
<script>
	jQuery('#sessionmanagement').addClass('mo2f-subtab-active');
	jQuery("#mo_2fa_advance_settings").addClass("side-nav-active");
</script>
