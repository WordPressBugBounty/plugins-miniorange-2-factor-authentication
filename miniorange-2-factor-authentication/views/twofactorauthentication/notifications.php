<?php
/**
 * Contains Emails templates regarding IP alerts
 *
 * @package miniorange-2-factor-authentication/notifications/views
 */

// Needed in both.

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

echo '<div class="mo2f-settings-div">';

echo '		
			<br>
			<form id="mo2f-new-release-notification-form" method="post" action="">
				<div class="mo2f-settings-head">
				<label class="mo2f_checkbox_container">
				<input type="checkbox"  name="S_mail" id="S_mail" ' . esc_html( $notify_new_release ) . ' onChange="mo2f_enable_release_notification()"><span class="mo2f-settings-checkmark"></span></label>
			
			<span>';
			esc_html_e( 'Enable the email notifications to notify you of the new realeases of the plugin', 'miniorange-2-factor-authentication' );
			echo '</span>
		</div><br>';
		echo '		
			<div class="mo2f_email_notifications_head ml-mo-16 ' . ( $notify_new_release ? 'flex' : 'hidden' ) . '" id="mo2f_notification_email">
              Enter your E-mail :&nbsp;&nbsp;<input type= "email" name="admin_email_address" placeholder="miniorange@gmail.com"  value="' . esc_attr( get_option( 'admin_email_address', wp_get_current_user()->user_email ) ) . '">
              &nbsp;&nbsp;<input type="button" name="submit" value="Save" id="mo2f_save_new_release_notification" class="mo2f-save-settings-button"/>
             </div></form> <br>';
		echo '<div class="mo2f-settings-head">
			<form id="mo2f-ip-block-notification-form" method="post" action="">
				<label class="mo2f_checkbox_container"><input type="checkbox" name="Smail" id="Smail" ' . esc_html( $notify_admin_unusual_activity ) . '  onChange="mo2f_enable_new_ip_notification()"><span class="mo2f-settings-checkmark"></span></label>
			</form>
			<span>';
			esc_html_e( 'Enable the email notifications to notify the users of any login with a new IP', 'miniorange-2-factor-authentication' );
			echo '</span>&nbsp;&nbsp;(<a  href="' . esc_url( admin_url( 'admin.php?page=mo_2fa_white_labelling&subpage=emailtemplates#mo2f_2fa_new_ip_detected_email_subject' ) ) . '"  style="cursor:pointer" id="">Customize Email Template</a>)
		</div>
		
	</div>
	<script>
		jQuery(document).ready(function(){
			jQuery("#notifications").addClass("mo2f-subtab-active");
			jQuery("#mo_2fa_two_fa").addClass("side-nav-active");
			jQuery("#custom_user_template_expand").click(function() {
				jQuery("#custom_user_template_form").slideToggle();
			});
		});
		var nonce = "' . esc_js( wp_create_nonce( 'mo2f-login-settings-ajax-nonce' ) ) . '";
	    function mo2f_enable_release_notification(){
			var element = document.getElementById("S_mail");
			if (jQuery(element).is(":checked")) {
				jQuery("#mo2f_notification_email").css("display", "flex");
			} else{
			 	jQuery("#mo2f_notification_email").css("display", "none");
			    jQuery("#mo2f_save_new_release_notification").click();
			}
        }
		function mo2f_enable_new_ip_notification(){
        	var enablednotification = jQuery("#Smail").is(":checked");
			var data = {
				"action": "mo2f_login_settings_ajax",
				"option": "waf_settings_IP_mail_form",
				"nonce": nonce,
				"is_notification_enabled": enablednotification,
			};
			jQuery.post(ajaxurl, data, function (response) {
				if (response["success"]) {
					success_msg(response.data);
				} else {
					error_msg(response.data);
				}
			});
        }
		jQuery("#mo2f_save_new_release_notification").click(function () {
			var enablednotification = jQuery("#S_mail").is(":checked");
			var data = {
				"action": "mo2f_login_settings_ajax",
				"option": "mo2f_new_release_nofify",
				"nonce": nonce,
				"is_notification_enabled": enablednotification,
				"mo2f_email": jQuery("input[name=\"admin_email_address\"]").val(),
			};
			jQuery.post(ajaxurl, data, function (response) {
				if (response["success"]) {
					success_msg(response.data);
				} else {
					error_msg(response.data);
				}
			});
		});
	</script>';
