<?php
/**
 * Display Troubleshooting tab.
 *
 * @package miniorange-2-factor-authentication/views
 */

use TwoFA\Helper\MoWpnsConstants;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

	echo '<div class="mo_wpns_divided_layout">
	        <div class="mo_wpns_setting_layout">
        <h3 class="mo2f_faq_head">
        	' . esc_html__( 'Frequenty Asked Questions', 'miniorange-2-factor-authentication' ) . '
        </h3><br><hr>
			<table class="mo_wpns_help">
						<tbody><tr>
							<td class="mo_wpns_help_cell">
								<div id="mo_wpns_issue_in_scanning_QR" class="mo_wpns_title_panel">
									<div class="mo_wpns_help_title">' . esc_html__( 'I am facing an issue while scanning the QR code. What could be the reason?', 'miniorange-2-factor-authentication' ) . '</div>
								</div>
								<div id="mo_wpns_issue_in_scanning_QR_solution" class="mo_wpns_help_desc hidden">
								   <ol>
								   <li>' . esc_html__( 'Make sure that the QR code you are scanning and the app you are using to scan it are compatible.', 'miniorange-2-factor-authentication' ) . '</li>
								<li>' . esc_html__( 'If you are configuring the Google Authenticator method, you will need the Google Authenticator App.', 'miniorange-2-factor-authentication' ) . '</li>
								   <li>' . sprintf(
									__( 'If you are having issues setting up the <b>Authenticator app</b>, please refer to <a href="%1$s" target="_blank">this</a> setup guide.', 'miniorange-2-factor-authentication' ),
									'https://plugins.miniorange.com/setup-two-factor-authentication-using-authenticator-apps'
								) . '</li>
								   </ol>
								</div>
							</td>
						</tr>
						<tr>
							<td class="mo_wpns_help_cell"> 
								<div id="mo_wpns_help_particular_use_role" class="mo_wpns_title_panel">
									<div class="mo_wpns_help_title">How can I enable 2-factor authentication for specific user roles in WordPress?</div>
								</div>
								<div id="mo_wpns_help_particular_use_role_solution" class="mo_wpns_help_desc hidden">
		                            <ol>
									<li>' . sprintf(
									__( 'Click on the %1$sQuick setup%2$s tab present under the %3$s2FA Configuration%4$s tab.', 'miniorange-2-factor-authentication' ),
									'<b>',
									'</b>',
									'<b>',
									'</b>'
								) . '</li>
								<li>' . sprintf(
									__( 'Select the checkbox to %1$sEnable 2FA%2$s Settings.', 'miniorange-2-factor-authentication' ),
									'<b>',
									'</b>'
								) . '</li>
								   <li>' . sprintf(
									__( 'Select the user roles for 2FA and click the %1$sSave Settings%2$s button.', 'miniorange-2-factor-authentication' ),
									'<b>',
									'</b>'
								) . '</li>
								   </ol>
								</div>
							</td>
						</tr>
						<tr>
							<td class="mo_wpns_help_cell">
								<div id="mo_wpns_help_enforce_MFA" class="mo_wpns_title_panel">
									<div class="mo_wpns_help_title">' . esc_html__( 'How can I enforce my users to configure a two-factor authentication method?', 'miniorange-2-factor-authentication' ) . '</div>
								</div>
								<div id="mo_wpns_help_enforce_MFA_solution" class="mo_wpns_help_desc hidden">
	                               <ol>
								   <li>' . sprintf(
										__( 'Click on the %1$sQuick setup%2$s tab present under the %3$s2FA Configuration%4$s tab.', 'miniorange-2-factor-authentication' ),
										'<b>',
										'</b>',
										'<b>',
										'</b>'
									) . '</li>
								   <li>' . sprintf(
										__( 'Select the user role for which you want to enforce 2FA and click on the %1$sSave Settings%2$s button.', 'miniorange-2-factor-authentication' ),
										'<b>',
										'</b>'
									) . '</li>
								   <li>' . sprintf(
										__( 'Go to the %1$sSettings%2$s tab and keep the %3$s"Prevent End Users from configuring their 2FA method at Login"%4$s setting unchecked.', 'miniorange-2-factor-authentication' ),
										'<b>',
										'</b>',
										'<b>',
										'</b>'
									) . '</li>
								   </ol>
								</div>
							</td>
						</tr>
						<tr>
							<td class="mo_wpns_help_cell">
								<div id="mo_wpns_help_reset_MFA" class="mo_wpns_title_panel">
									<div class="mo_wpns_help_title">' . esc_html__( 'How can I reset two-factor authentication for my users?', 'miniorange-2-factor-authentication' ) . '</div>
								</div>
								<div id="mo_wpns_help_reset_MFA_solution" class="mo_wpns_help_desc hidden">
								   <ol>
								   <li>' . sprintf(
										__( 'Click on the %1$sUsers 2FA Status%2$s tab present under the %3$sReports%4$s tab.', 'miniorange-2-factor-authentication' ),
										'<b>',
										'</b>',
										'<b>',
										'</b>'
									) . '</li>
								   <li>' . sprintf(
										__( 'Click the %1$sReset 2FA%2$s button next to the user whose two-factor authentication you want to reset.', 'miniorange-2-factor-authentication' ),
										'<b>',
										'</b>'
									) . '</li>
								   <li>' . esc_html__( 'Now, when the user logs in to their account again, they will be able to reconfigure two-factor authentication.', 'miniorange-2-factor-authentication' ) . '</li>
								   </ol>
								</div>
							</td>
						</tr>
						<tr>
							<td class="mo_wpns_help_cell">
								<div id="mo_wpns_help_get_back_to_account" class="mo_wpns_title_panel">
									<div class="mo_wpns_help_title">' . esc_html__( 'How do I gain access to my website if I get locked out?', 'miniorange-2-factor-authentication' ) . '</div>
								</div>
								<div id="mo_wpns_help_get_back_to_account_solution" class="mo_wpns_help_desc hidden">
									' . sprintf(
										__( 'Please use the following link to gain access to your site. %1$s.', 'miniorange-2-factor-authentication' ),
										'<b><a href="https://faq.miniorange.com/knowledgebase/how-to-gain-access-to-my-website-if-i-get-locked-out/" target="_blank">' . __( 'click here', 'miniorange-2-factor-authentication' ) . '</a></b>'
									) . '
								</div>
							</td>
						</tr>
						<tr>
							<td class="mo_wpns_help_cell">
								<div id="mo_wpns_help_multisite" class="mo_wpns_title_panel">
									<div class="mo_wpns_help_title">' . esc_html__( 'Does the miniOrange 2FA plugin support multisite network?', 'miniorange-2-factor-authentication' ) . '</div>
								</div>
								<div id="mo_wpns_help_multisite_solution" class="mo_wpns_help_desc hidden">
									' . esc_html__( 'Yes, the plugin supports 2FA on WordPress multisite network.', 'miniorange-2-factor-authentication' ) . '
								</div>
							</td>
						</tr>
						<tr>
							<td class="mo_wpns_help_cell">
								<div id="mo_wpns_help_forgot_password" class="mo_wpns_title_panel">
									<div class="mo_wpns_help_title">' . esc_html__( 'I forgot the password of my miniOrange account. How can I reset it?', 'miniorange-2-factor-authentication' ) . '</div>
								</div>
								<div id="mo_wpns_help_forgot_password_solution" class="mo_wpns_help_desc hidden">
									' . sprintf(
										__( 'To reset your miniOrange account password, %1$s%2$s%3$s and use your registered email address to complete the process.', 'miniorange-2-factor-authentication' ),
										'<b><a href="' . esc_url( MoWpnsConstants::PORTAL_LINK ) . 'forgotpassword" target="_blank">',
										__( 'click here', 'miniorange-2-factor-authentication' ),
										'</a></b>'
									) . '
								</div>
							</td>
						</tr>
						<tr>
							<td class="mo_wpns_help_cell">
								<div id="mo_wpns_help_MFA_propmted" class="mo_wpns_title_panel">
									<div class="mo_wpns_help_title">' . esc_html__( 'My Users are not being prompted for 2-factor authentication during login. Why?', 'miniorange-2-factor-authentication' ) . '</div>
								</div>
								<div id="mo_wpns_help_MFA_propmted_solution" class="mo_wpns_help_desc hidden">
									' . esc_html__( 'If you are on Free plan, you can configure the 2FA upto only 3 users. Otherwise, please check the following settings.', 'miniorange-2-factor-authentication' ) . '
                                   <ol>
								   <li>' . sprintf(
										__( 'Click on the %1$sQuick setup%2$s tab present under the %3$s2FA Configuration%4$s tab.', 'miniorange-2-factor-authentication' ),
										'<b>',
										'</b>',
										'<b>',
										'</b>'
									) . '</li>
									<li>' . esc_html__( 'Make sure you have enabled the 2FA setting and selected the correct user roles for 2FA.', 'miniorange-2-factor-authentication' ) . '</li>
								    <li>' . sprintf(
										__( 'Go to the %1$sSettings%2$s tab and keep the %3$s"Prevent End Users from configuring their 2FA method at Login"%4$s setting unchecked.', 'miniorange-2-factor-authentication' ),
										'<b>',
										'</b>',
										'<b>',
										'</b>'
									) . '</li>
								   </ol>

								</div>
							</td>
						</tr><tr>
							<td class="mo_wpns_help_cell">
								<div id="mo_wpns_help_redirect_back" class="mo_wpns_title_panel">
									<div class="mo_wpns_help_title">' . esc_html__( 'When I enter my authentication code and click on Validate, I got redirected back to the login page, Why is this happening?', 'miniorange-2-factor-authentication' ) . '</div>
								</div>
								<div id="mo_wpns_help_redirect_back_solution" class="mo_wpns_help_desc hidden">
									' . sprintf(
										__( 'It might be the case that the plugin is not able to write the PHP session info (into the %1$s/var/lib/php/sessions directory%2$s) because of permission issues, and hence it is failing at the authentication step. Please update to the latest plugin version (%3$sFree- 6.0.8/Premium- 18.0%4$s) which has the bug fix for this.', 'miniorange-2-factor-authentication' ),
										'<b>',
										'</b>',
										'<b>',
										'</b>'
									) . '
								</div>
							</td>
						</tr>
						<tr>
							<td class="mo_wpns_help_cell">
								<div id="mo_wpns_help_curl_title" class="mo_wpns_title_panel">
									<div class="mo_wpns_help_title">' . esc_html__( 'How to enable PHP cURL extension?', 'miniorange-2-factor-authentication' ) . '</div>
								</div>
								<div id="mo_wpns_help_curl_desc" class="mo_wpns_help_desc hidden">
								   <ol>
								   <li>' . sprintf(
										__( 'Open %1$sphp.ini%2$s file located under the PHP installation folder.', 'miniorange-2-factor-authentication' ),
										'<b>',
										'</b>'
									) . '</li>
								   <li>' . sprintf(
										__( 'Search for %1$sextension=curl.dll%2$s.', 'miniorange-2-factor-authentication' ),
										'<b>',
										'</b>'
									) . '</li>
								   <li>' . sprintf(
										__( 'Uncomment it by removing the semi-colon (%1$s;%2$s) in front of it. Restart the Apache Server.', 'miniorange-2-factor-authentication' ),
										'<b>',
										'</b>'
									) . '</li>
								   </ol>
								</div>
							</td>
						</tr>
						<tr>
							<td class="mo_wpns_help_cell">
								<div id="mo_wpns_help_translate" class="mo_wpns_title_panel">
									<div class="mo_wpns_help_title">How do I translate the plugin in a language of my choice?</div>
								</div>
								<div id="mo_wpns_help_translate_solution" class="mo_wpns_help_desc hidden">
								Please follow the below steps:
								<ol>
								<li>' . sprintf(
										__( 'Download & open %1$sPOEDIT%2$s software.', 'miniorange-2-factor-authentication' ),
										'<b>',
										'</b>'
									) . '</li>
								<li>' . sprintf(
										__( 'Click on %1$sCreate New%2$s Translation.', 'miniorange-2-factor-authentication' ),
										'<b>',
										'</b>'
									) . '</li>
								<li>' . sprintf(
										__( 'In the plugin, you have a %1$slang%2$s folder. You can find the %3$s.pot%4$s file in it. Import the file.', 'miniorange-2-factor-authentication' ),
										'<b>',
										'</b>',
										'<b>',
										'</b>'
									) . '</li>
								<li>' . esc_html__( 'It will ask you for the translation language. Select the country that you want to translate.', 'miniorange-2-factor-authentication' ) . '</li>
								<li>' . esc_html__( 'In the top navigation bar, Click on Update ', 'miniorange-2-factor-authentication' ) . '<i class="fa fa-fw fa-arrow-right"></i> ' . esc_html__( 'Save. Do the translations and Save them.', 'miniorange-2-factor-authentication' ) . '</li>
								<li>' . esc_html__( 'Select the WordPress site language to match the one you selected in the software.', 'miniorange-2-factor-authentication' ) . '</li>
								</ol>
													
								</div>
							</td>
						</tr>
						<tr>
							<td class="mo_wpns_help_cell">
								<div id="mo_wpns_help_refund_title" class="mo_wpns_title_panel">
									<div class="mo_wpns_help_title">' . esc_html__( 'What is your refund policy & end user license agreement?', 'miniorange-2-factor-authentication' ) . '</div>
								</div>
								<div id="mo_wpns_help_refund_desc" class="mo_wpns_help_desc hidden">
						            <li>' . sprintf(
										__( '%1$s%2$s%3$s to read our refund policy.', 'miniorange-2-factor-authentication' ),
										'<b><a href="https://plugins.miniorange.com/end-user-license-agreement/#v5-software-warranty-refund-policy" target="_blank">',
										__( 'click here', 'miniorange-2-factor-authentication' ),
										'</a></b>'
									) . '</li>
									<li>' . sprintf(
										__( '%1$s%2$s%3$s to read our end user license agreement.', 'miniorange-2-factor-authentication' ),
										'<b><a href="https://plugins.miniorange.com/end-user-license-agreement" target="_blank">',
										__( 'click here', 'miniorange-2-factor-authentication' ),
										'</a></b>'
									) . '</li>
								</div>
							</td>
						</tr>
					</tbody></table>
					<h4>' . sprintf(
										__( 'If you have any other queries, Contact us at %1$s%2$s%3$s.', 'miniorange-2-factor-authentication' ),
										'<a href="mailto:mfasupport@xecurify.com" target="_blank">',
										'mfasupport@xecurify.com',
										'</a>'
									) . '</h4>
		    </div>
		</div>		
		<script>
		jQuery("#mo_2fa_troubleshooting").addClass("side-nav-active");
		</script>';
