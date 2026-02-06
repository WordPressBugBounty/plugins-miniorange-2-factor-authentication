<?php
/**
 * This file includes the UI for 2fa methods options.
 *
 * @package miniorange-2-factor-authentication/ipblocking/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use TwoFA\Helper\MoWpnsConstants;
echo '<div class="mo2f-settings-div"><div class="mo2f-settings-head">Verify License  [ <span class="mo2f-view-license-key"><a href=' . esc_url( MoWpnsConstants::PORTAL_LINK . 'viewlicense' ) . ' target="_blank">' . esc_html__( 'Click here to view your license key', 'miniorange-2-factor-authentication' ) . '</a></span> ]</div>
    <form name="f" method="post" action="">
		<div class="ml-mo-16 mo2f_license_key_input_lable_wrapper"><b class="mo2f-w-400"><font color="#FF0000">*</font>' . esc_html__( 'Enter your license key to activate the plugin:', 'miniorange-2-factor-authentication' ) . '</b>
			<div class=" mo-input-wrapper group">
				<label  class="mo-input-label">' . esc_html__( 'License key', 'miniorange-2-factor-authentication' ) . '</label>
				<input class="mo2f-input-fields-placeholder mo2f_license_key_input" required type="text" name="mo2fa_licence_key" placeholder="' . esc_html__( 'Enter your license key to activate the license ', 'miniorange-2-factor-authentication' ) . '"/>
			</div>
        </div>
        <div class="mo2f_transfer_license_key">
			<div class="text-mo-tertiary-txt ml-mo-22">';
			printf(
				/* Translators: %s: bold tags */
				esc_html( __( '%1$1sNote:%2$2s Please make sure that the license key you enter is not being used on any other instance.', 'miniorange-2-factor-authentication' ) ), //phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- The $text is a single string literal
				'<b>',
				'</b>',
			);
			echo '</div>
           <br>
			</div>
        <div class="ml-mo-16"><b><font color="#FF0000">*</font>Please check this to confirm that you have read the below details: </b>&nbsp;&nbsp;
            <label class="mo2f_checkbox_container">
                <input type="checkbox" id="license_conditions" name="license_conditions"/>
            </label>		
		</div>
        <div class="ml-mo-20">  <ol>
            <li>' . esc_html__( 'License key you have entered here is associated with this site instance. In future, if you want to reinstall the plugin on your site for any reason, you should deactivate and delete the plugin from the WordPress dashboard ( not from the FTP/SFTP/cPanel ) so that you can reuse the same license key.', 'miniorange-2-factor-authentication' ) . '</li><br>
            <li>';
			printf(
				/* Translators: %s: bold tags */
				esc_html( __( '%1$1sThis is not a developer\'s license.:%2$2s Making any kind of change to the plugin\'s code will delete all your configurations and render the plugin unusable.', 'miniorange-2-factor-authentication' ) ), //phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- The $text is a single string literal
				'<b>',
				'</b>',
			);
			echo '
               </ol>
               <br>
               </div> 
               <div class="ml-mo-16">          
               <input type="button" class="mo2f-reset-settings-button" id="mo2f_go_back_account" value="Back" onclick="location.reload();"/>
                   <input type="button" id="mo2f_check_license" value="Activate License" class="mo2f-save-settings-button"/>
		</div>

       </form>
<form style="display:none;" id="mo2f_view_licensekey_form" action="' . esc_attr( MO_HOST_NAME ) . '/moas/login"
target="_blank" method="post">
<input type="email" name="username" value="' . esc_attr( $mo2f_email ) . '" /> 
<input type="text" name="redirectUrl" value="' . esc_attr( MO_HOST_NAME ) . '/moas/viewlicensekeys" />
<input type="text" name="requestOrigin" value="wp_security_two_factor_all_inclusive_plan"  />
</form>

</div>
<script type="text/javascript">
 jQuery("#mo_2fa_my_account").addClass("side-nav-active");
</script>';
