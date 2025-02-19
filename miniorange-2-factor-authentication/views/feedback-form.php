<?php
/**
 * This File contains Feedback form UI.
 *
 * @package miniorange-2-factor-authentication/views
 */

// Needed in both.
use TwoFA\Handler\Twofa\MO2f_Utility;
use TwoFA\Helper\MoWpnsConstants;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( 'plugins.php' !== basename( isset( $_SERVER['PHP_SELF'] ) ? sanitize_text_field( wp_unslash( $_SERVER['PHP_SELF'] ) ) : '' ) ) {
	return;
}
global $mo2fdb_queries;
$mo2f_configured_2_f_a_method = $mo2fdb_queries->get_user_detail( 'mo2f_configured_2FA_method', get_current_user_id() );
$no_of_2fa_users              = $mo2fdb_queries->get_no_of_2fa_users();
$deactivate_reasons           = array(
	'Conflicts with other plugins',
	'Redirecting back to login page after Authentication',
);
$message                      = (
	'We are sad to see you go!  Help us to improve our plugin by giving your opinion.'
);
if ( strlen( $mo2f_configured_2_f_a_method ) ) {
	array_push( $deactivate_reasons, "Couldn't understand how to make it work" );
} elseif ( strpos( $mo2f_configured_2_f_a_method, MoWpnsConstants::GOOGLE_AUTHENTICATOR ) !== false ) {
	array_push( $deactivate_reasons, 'Unable to verify Google Authenticator' );
} elseif ( strpos( $mo2f_configured_2_f_a_method, MoWpnsConstants::OTP_OVER_SMS ) !== false || strpos( $mo2f_configured_2_f_a_method, MoWpnsConstants::OTP_OVER_EMAIL ) !== false ) {
	array_push( $deactivate_reasons, 'Exhausted Email or SMS transactions' );
}
if ( 'MO_2_FACTOR_CUSTOMER_REGISTERED_SUCCESS' !== get_site_option( 'mo_2factor_admin_registration_status' ) ) {
	array_push( $deactivate_reasons, 'Did not want to create an account' );
}
if ( get_site_option( 'mo2fa_visit' ) ) {
	array_push( $deactivate_reasons, 'Plans are expensive' );
}
if ( 3 === $no_of_2fa_users ) {
	array_push( $deactivate_reasons, 'User Limit' );
}
array_push( $deactivate_reasons, 'Other Reasons:' );
?>
</head>

<body>
	<div id="mo_wpns_feedback_modal" class="mo_modal" style="width: 90%; margin-left: 18%; margin-top: 5%; text-align: center; display: none; overflow: hidden; position: fixed; top: 0; right: 0; bottom: 0; left: 0; z-index: 1050; -webkit-overflow-scrolling: touch; outline: 0;">
	<div class="mo2f_deactivation_popup_container" id="mo2f_otp_feedback_modal" style="font-family: Inter, sans-serif; position: fixed; top: 0; right: 0; bottom: 0; left: 0; display: flex; align-items: flex-start; justify-content: center; background-color: rgba(0, 0, 0, 0.5); padding: 3.5rem; z-index: 2;">
				<div id="mo2f_deactivation_popup_wrapper rounded-md" class="mo2f_deactivation_popup_wrapper" tabindex="-1" role="dialog" style="width: 528px; background-color: rgb(255, 255, 255); border-radius: 0.375rem;">

				<div style="border-bottom: 1px solid; border-color:#dadada;">
					<h3>
						<b>Your feedback</b>
						<span class="mo_wpns_close mr-mo-4 ml-mo-4" style="margin-right: 1rem; margin-left: 1rem; color: #aaaaaa; cursor: pointer; float: right; font-size: 28px; font-weight: bold;">&times;</span>
					</h3>
				</div>

			<form class="p-mo-6 flex flex-col gap-mo-6" name="f" method="post" action="" id="mo_wpns_feedback" style="padding: 1.5rem; flex-direction: column; display: flex;">
				<input type="hidden" id="mo_wpns_feedback_nonce" name="mo_wpns_feedback_nonce" value="<?php echo esc_attr( wp_create_nonce( 'mo-wpns-feedback-nonce' ) ); ?>"/>
				<input type="hidden" name="option" value="mo_wpns_feedback" />
				<div class="mo2f_deactivation_message" style="border: 1px solid; border-radius: 4px; border-color: rgb(203, 213, 225); background-color: rgb(248, 250, 252); padding: 1rem; color: rgb(15, 23, 42);"><?php echo esc_attr( $message ); ?></div>

				<div class="mo_feedback_text" style="text-align: left;">
					<span id="mo2f_link_id"></span>
					<div id="feedback_reasons" style="margin-top:20px;margin-bottom:20px;">
					<?php

					foreach ( $deactivate_reasons as $deactivate_reason ) {
						?>
						<div style="margin:8px;">
							<label for="<?php echo esc_attr( $deactivate_reason ); ?>">
								<input type="radio" name="mo_wpns_deactivate_plugin" value="<?php echo esc_attr( $deactivate_reason ); ?>" required>
								<?php echo esc_attr( $deactivate_reason ); ?>
								<?php if ( 'Conflicts with other plugins' === $deactivate_reason ) { ?>
									<div id="mo_wpns_other_plugins_installed">
										<?php
											MO2f_Utility::get_all_plugins_installed();
										?>
									</div>
								<?php } ?>

							</label>
						</div>
					<?php } ?>
								</div>
					<div class="mo-input-wrapper" style="position: relative; width: 100%;">   
						<textarea id="wpns_query_feedback" name="wpns_query_feedback" class="mo-textarea h-[100px]" style="resize: vertical; width:100%;" cols="52" rows="4" placeholder="Write your query here..."></textarea>
					</div> 

					<div class="mo2f_modal-footer">
						<div class="my-mo-3" style="margin-bottom: 1rem;">
							<input type="checkbox" name="mo2f_get_reply" value="1" checked />
							<label for="mo2f_get_reply">Send plugin Configuration</label>
							</input>
						</div>
						<div class="my-mo-3" style="margin-bottom: 1rem;">
							<input type="checkbox" name="mo2f_contact_back" id="mo2f_contact_back" value="1" checked />
							<label for="mo2f_contact_back"><?php echo esc_html__( 'Allow us to contact you on your registered email address', 'miniorange-2-factor-authentication' ); ?></label>
						</div>
						<input type="button" name="miniorange_feedback_skip" class="mo2f-reset-settings-button" style="float:left" value="Skip & Deactivate" onclick="document.getElementById('mo_wpns_feedback_form_close').submit();" />
						<input type="submit" name="miniorange_2fa_feedback_submit" class="mo2f-save-settings-button" style="float:right" value="Submit"/>
					</div>
				</div>
			</form>
			<form name="f" method="post" action="" id="mo_wpns_feedback_form_close">
				<input type="hidden" id="mo_wpns_feedback_nonce" name="mo_wpns_feedback_nonce" value="<?php echo esc_attr( wp_create_nonce( 'mo-wpns-feedback-nonce' ) ); ?>"/>
				<input type="hidden" name="option" value="mo_wpns_skip_feedback" />
			</form>


		</div>
	</div>

	</div>
	<script>
		jQuery('#mo_wpns_other_plugins_installed').hide();
		jQuery("#deactivate-miniorange-2-factor-authentication").click(function() {
			var mo_modal = document.getElementById('mo_wpns_feedback_modal');
			var span = document.getElementsByClassName("mo_wpns_close")[0];
			// When the user clicks the button, open the mo2f_modal
			mo_modal.style.display = "block";
			jQuery('input:radio[name="mo_wpns_deactivate_plugin"]').click(function() {
				var reason = jQuery(this).val();
				jQuery('#wpns_query_feedback').removeAttr('required');
				if (reason == "Did not want to create an account") {
					jQuery('#mo_wpns_other_plugins_installed').hide();
					jQuery('#wpns_query_feedback').attr("placeholder", "Write your query here.");
					jQuery('#mo2f_link_id').html('<p>We suggest you to create an account for only those methods which require miniOrange cloud for working purpose.</p>');
					jQuery('#mo2f_link_id').show();
				} else if (reason == "Upgrading to Premium") {
					jQuery('#mo_wpns_other_plugins_installed').hide();
					jQuery('#wpns_query_feedback').attr("placeholder", "Write your query here.");
					jQuery('#mo2f_link_id').html('<p>Thanks for upgrading. For setup instructions, please follow this guide' +
						', <a href="<?php echo esc_url( MoWpnsConstants::SETUPGUIDE ); ?>" target="_blank"><b>VIDEO GUIDE.</b></a></p>');
					jQuery('#mo2f_link_id').show();
				} else if (reason == "User Limit") {
					jQuery('#mo_wpns_other_plugins_installed').hide();
					jQuery('#wpns_query_feedback').attr("placeholder", "Write your query here.");
					jQuery('#mo2f_link_id').html('<p>You can download our <a href="https://wordpress.org/plugins/miniorange-login-security/" target="_blank"><b>Multi Factor Authentication</b></a> plugin to setup 2FA for unlimited admin users.</p>');
					jQuery('#mo2f_link_id').show();

				} else if (reason == "Exhausted Email or SMS") {
					jQuery('#mo_wpns_other_plugins_installed').hide();
					jQuery('#wpns_query_feedback').attr("placeholder", "Write your query here.");
					jQuery('#mo2f_link_id').html('<p>You can recharge your SMS and Email transactions using this link' +
						', <a href="<?php echo esc_url( MoWpnsConstants::RECHARGELINK ); ?>" target="_blank"><b>Recharge Link.</b></a> Otherwise, you can configure your own gateway <a href="<?php echo esc_url( MoWpnsConstants::CUSTOMSMSGATEWAY ); ?>" target="_blank"><b>here.</b></a></p>');
					jQuery('#mo2f_link_id').show();
				} else if (reason == "Conflicts with other plugins") {
					jQuery('#wpns_query_feedback').attr("placeholder", "Can you please mention the plugin name, and the issue?");
					jQuery('#mo_wpns_other_plugins_installed').show();
					jQuery('#mo2f_link_id').hide();
				} else if (reason == "Other Reasons:") {
					jQuery('#mo_wpns_other_plugins_installed').hide();
					jQuery('#wpns_query_feedback').attr("placeholder", "Can you let us know the reason for deactivation");
					jQuery('#wpns_query_feedback').prop('required', true);
					jQuery('#mo2f_link_id').hide();
				} else {
					jQuery('#mo_wpns_other_plugins_installed').hide();
					jQuery('#wpns_query_feedback').attr("placeholder", "Write your query here.");
					jQuery('#mo2f_link_id').hide();
				}
			});

			span.onclick = function() {
				mo_modal.style.display = "none";
			}

			// When the user clicks anywhere outside of the mo2f_modal, mo2f_close it
			window.onclick = function(event) {
				if (event.target == mo_modal) {
					mo_modal.style.display = "none";
				}
			}
			return false;

		});
	</script>
	<style>
		.mo-textarea{
			-webkit-appearance: none;
			outline: none !important;
			box-shadow: none !important;
			border: 2px solid;
			--tw-border-opacity: 1;
			border-color: rgb(226 232 240 / var(--tw-border-opacity));
			padding-left: 1rem;
			padding-right: 1rem;
			padding-top: 0.75rem;
			padding-bottom: 0.75rem;
			font-weight: 700;
			transition-duration: 200ms;
			border-radius: 0.375rem;
		}
		.mo-textarea:disabled{
			cursor: not-allowed;
			-webkit-user-select: none;
			-moz-user-select: none;
					user-select: none;
		}
		.mo-textarea:focus {
			outline: none !important;
			box-shadow: none !important;
			border: 2px solid black;
			--tw-border-opacity: 1;
			border-color: rgb(99 102 241 / var(--tw-border-opacity));
		}
		.mo-textarea {
			font-family: monospace, sans-serif;
			resize: none;
			font-weight: 400;
			width: 100%;
		}
		.mo2f_modal-footer div{
			margin-bottom: 0.5em;
		}
		.mo2f_modal-footer {
			margin-bottom: 1em;
		}
		.my-mo-3 {
			margin-top: 0.75rem;
			margin-bottom: 0.75rem;
		}
		.mo2f-save-settings-button {
			padding-left: 1rem;
			padding-right: 1rem;
			padding-top: 0.5rem;
			padding-bottom: 0.5rem;
			font-family: Poppins, sans-serif;
			--tw-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
			--tw-shadow-colored: 0 10px 15px -3px var(--tw-shadow-color), 0 4px 6px -4px var(--tw-shadow-color);
			box-shadow: var(--tw-ring-offset-shadow, 0 0 #0000), var(--tw-ring-shadow, 0 0 #0000), var(--tw-shadow);
			border-radius: 4px;
			border-style: none;
			--tw-bg-opacity: 1;
			background-color: rgb(99 102 241 / var(--tw-bg-opacity));
			font-size: 13px;
			--tw-text-opacity: 1;
			color: rgb(255 255 255 / var(--tw-text-opacity));
		}
		.mo2f-save-settings-button:hover {
			cursor: pointer;
			--tw-bg-opacity: 1;
			background-color: rgb(129 140 248 / var(--tw-bg-opacity));
		}
		.mo2f-reset-settings-button {
			border-radius: 4px;
			border-width: 1px;
			border-style: solid;
			--tw-border-opacity: 1;
			border-color: rgb(99 102 241 / var(--tw-border-opacity));
			--tw-bg-opacity: 1;
			background-color: rgb(255 255 255 / var(--tw-bg-opacity));
			padding-left: 1rem;
			padding-right: 1rem;
			padding-top: 0.5rem;
			padding-bottom: 0.5rem;
			font-family: Poppins, sans-serif;
			font-size: 13px;
			font-weight: 500;
			--tw-text-opacity: 1;
			color: rgb(99 102 241 / var(--tw-text-opacity));
		}
		.mo2f-reset-settings-button:hover {
			cursor: pointer;
			--tw-bg-opacity: 1;
			background-color: rgb(224 231 255 / var(--tw-bg-opacity));
		}
		.mo2f_plugin_select{
			padding:5px;
			margin-left:4%;
			font-size:13px;
			background-color: #a3e8c2;
		}
	</style>
