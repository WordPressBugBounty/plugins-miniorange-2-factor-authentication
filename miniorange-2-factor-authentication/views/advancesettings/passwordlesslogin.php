<?php
/**
 * Description: This file is used to show the user details.
 *
 * @package miniorange-2-factor-authentication/controllers.
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
<div class="mo2f-settings-div <?php echo esc_attr( $overlay_on_premium_features ); ?>" >
	<div class="mo2f-settings-head">
		<span><?php esc_html_e( 'Passwordless Login with 2FA', 'miniorange-2-factor-authentication' ); ?></span><?php echo $crown; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Only a SVG, doesn't require escaping. ?>
	</div>
	<br>
	<div class="ml-mo-16">
	<span><b><?php esc_html_e( 'Select Login Options', 'miniorange-2-factor-authentication' ); ?></b></span><br>
	</div>
	<br>
	<div class="mo2f-settings-items ml-mo-20">
		<div class="mr-mo-4">
			<input type="radio" name="mo2f_login_option" id="mo2f_block_users" value="1" <?php checked( MoWpnsUtility::get_mo2f_db_option( 'mo2f_login_option', 'get_option' ) === '1' ); ?>>
			<?php esc_html_e( 'Username + Password + 2FA', 'miniorange-2-factor-authentication' ); ?>
			(<span class="text-mo-blue-txt"><?php esc_html_e( 'Recommended', 'miniorange-2-factor-authentication' ); ?></span>)
		</div>
	</div>
	<br>
	<div class="text-mo-tertiary-txt ml-mo-29" > 
		<?php
				printf(
					/* Translators: %s: bold tags */
					esc_html( __( '%1$1sNote:%2$12s By default the 2nd factor is enabled after authentication. If you do not want to remeber password anymore and login with 2nd factor, please select the below option.', 'miniorange-2-factor-authentication' ) ), //phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- The $text is a single string literal
					'<b>',
					'</b>',
				);
				?>
	</div>
	<br>
	<div class="mo2f-settings-items ml-mo-20">
		<div class="mr-mo-4">
			<input type="radio" name="mo2f_login_option" id="mo2f_enforce_2fa" value="0" <?php checked( MoWpnsUtility::get_mo2f_db_option( 'mo2f_login_option', 'get_option' ) === '0' ); ?>>
			<?php esc_html_e( 'Username + 2FA', 'miniorange-2-factor-authentication' ); ?>
			(<span class="text-mo-blue-txt"><?php esc_html_e( 'No password required', 'miniorange-2-factor-authentication' ); ?></span>) &nbsp;<a class="btn-link" data-toggle="collapse" id="showpreview1" href="#preview1" aria-expanded="false"><?php esc_html_e( 'Hide preview', 'miniorange-2-factor-authentication' ); ?></a>
		</div>
	</div>
	<div class="mo2f_collapse ml-mo-80" id="preview1">
		<br>
		<img class="h-mo-80" src="<?php echo esc_url( plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'includes/images/password_login.png' ); ?>" alt="<?php esc_attr_e( 'Passwordless login preview', 'miniorange-2-factor-authentication' ); ?>" >
		<br>
	</div> 
	<br>
	<div class="ml-mo-30">
	<br>
	<label class="mo2f_checkbox_container">
		<input type="checkbox" id="mo2f_enable_remember_device" onclick="mo2f_showSettings(this)" <?php checked( MoWpnsUtility::get_mo2f_db_option( 'mo2f_show_loginwith_phone', 'get_option' ) === '1' ); ?>/>	<span><?php esc_html_e( 'I want to hide default login form', 'miniorange-2-factor-authentication' ); ?></span>
	</label>
		&nbsp;<a class="btn-link" data-toggle="collapse" id="showpreview2" href="#preview2" aria-expanded="false"><?php esc_html_e( 'Hide preview', 'miniorange-2-factor-authentication' ); ?></a>
		<br>
	</div>
	<br>
	<div class="text-mo-tertiary-txt ml-mo-38"> <?php esc_html_e( 'Note: Checking this option will hide default login form and will only show the Login with 2-factor form. Click on \'Show Preview\' link to see the preview.', 'miniorange-2-factor-authentication' ); ?></div>
	<div class="mo2f_collapse ml-mo-80" id="preview2">
	<br>	
		<img  class="h-mo-60" src="<?php echo esc_url( plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'includes/images/passwordless_login.png' ); ?>" alt="<?php esc_attr_e( 'Passwordless login preview', 'miniorange-2-factor-authentication' ); ?>" >
	</div> 
	<br>
	<br>
	<div class="justify-start <?php echo $enable_2fa ? 'flex' : 'hidden'; ?> ml-mo-16">
		<div class=" <?php echo esc_attr( $overlay_on_premium_features ); ?>">
		<button id="mo2f_passwordless_login_save_button"  class="mo2f-save-settings-button"><?php esc_html_e( 'Save Settings', 'miniorange-2-factor-authentication' ); ?></button>
		</div>
	</div>
</div>

<script>
	jQuery('#passwordlesslogin').addClass('mo2f-subtab-active');
	jQuery("#mo_2fa_advance_settings").addClass("side-nav-active");
	jQuery('#preview1').show();
	jQuery('#showpreview1').on('click', function() {
		if ( jQuery("#preview1").is(":visible") ) { 
			jQuery('#preview1').hide();
			jQuery('#showpreview1').html('Show preview');
		} else if ( jQuery("#preview1").is(":hidden") ) { 
			jQuery('#preview1').show();
			jQuery('#showpreview1').html('Hide preview');
		}
	});
	jQuery('#preview2').show();
		jQuery('#showpreview2').on('click', function() {
			if ( jQuery("#preview2").is(":visible") ) { 
				jQuery('#preview2').hide();
				jQuery('#showpreview2').html('Show preview');
			} else if ( jQuery("#preview2").is(":hidden") ) { 
				jQuery('#preview2').show();
				jQuery('#showpreview2').html('Hide preview');
			}
	});
</script>
