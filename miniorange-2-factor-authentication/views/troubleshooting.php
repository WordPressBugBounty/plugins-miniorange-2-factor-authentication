<?php
/**
 * Display Troubleshooting tab.
 *
 * @package miniorange-2-factor-authentication/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

	echo '<div class="mo_wpns_divided_layout">
	        <div class="mo_wpns_setting_layout">
        <h3>
        	Frequenty Asked Questions
        </h3><br><hr>
			<table class="mo_wpns_help">
						<tbody><tr>
							<td class="mo_wpns_help_cell">
								<div id="mo_wpns_issue_in_scanning_QR" class="mo_wpns_title_panel">
									<div class="mo_wpns_help_title">I am facing issue in scanning QR code, what is the reason behind the error?</div>
								</div>
								<div id="mo_wpns_issue_in_scanning_QR_solution" class="mo_wpns_help_desc hidden">
								   <ol><li>Make sure that the bar-code you are scanning and the bar-code scanning app that you are using is suitable.</li>
								   <li>If you are configuring the Google Authenticator method, you will need the Google Authenticator App.</li>
								</div>
							</td>
						</tr>
						<tr>
							<td class="mo_wpns_help_cell">
								<div id="mo_wpns_help_particular_use_role" class="mo_wpns_title_panel">
									<div class="mo_wpns_help_title">I want to enable 2-factor for particular user roles in WordPress, how do I do that?</div>
								</div>
								<div id="mo_wpns_help_particular_use_role_solution" class="mo_wpns_help_desc hidden">
		                            <ol><li>Click on Login Settings tab present under the Two Factor Authentication tab.</li>
								   <li>Go to Enable 2FA Settings.</li>
								   <li>Select the user roles for 2FA and click on Save Settings button</li></ol>
							</td>
						</tr>
						<tr>
							<td class="mo_wpns_help_cell">
								<div id="mo_wpns_help_enforce_MFA" class="mo_wpns_title_panel">
									<div class="mo_wpns_help_title">I want to Enforce my users to configure the Two-factor authentication method how I do that?</div>
								</div>
								<div id="mo_wpns_help_enforce_MFA_solution" class="mo_wpns_help_desc hidden">
	                               <ol><li>Click on <b>Login Settings</b> tab present under the Two Factor Authentication tab.</li>
								   <li>Keep the <b>Prevent Prevent 2FA Configuration on Login </b>Settings unchecked</li>
								</div>
							</td>
						</tr>
						<tr>
							<td class="mo_wpns_help_cell">
								<div id="mo_wpns_help_reset_MFA" class="mo_wpns_title_panel">
									<div class="mo_wpns_help_title">I want to reset Two Factor for my users, how I do that?</div>
								</div>
								<div id="mo_wpns_help_reset_MFA_solution" class="mo_wpns_help_desc hidden">
								   <ol><li>Click on the <b>Advanced Features</b> tab.</li>
								   <li>Click on Users 2FA Status tab</li>
								   <li>Click on Reset 2FA button whose 2FA you want to reset.</li><ol>
								</div>
							</td>
						</tr>
						<tr>
							<td class="mo_wpns_help_cell">
								<div id="mo_wpns_help_get_back_to_account" class="mo_wpns_title_panel">
									<div class="mo_wpns_help_title">How do I gain access to my website if I get locked out?</div>
								</div>
								<div id="mo_wpns_help_get_back_to_account_solution" class="mo_wpns_help_desc hidden">
									Please use the following link to gain access to your site. <a href="https://faq.miniorange.com/knowledgebase/how-to-gain-access-to-my-website-if-i-get-locked-out/" tager="_blank">click here</a>. 
								</div>
							</td>
						</tr>
						<tr>
							<td class="mo_wpns_help_cell">
								<div id="mo_wpns_help_multisite" class="mo_wpns_title_panel">
									<div class="mo_wpns_help_title">Does the miniOrange 2FA plugin support multi-site network?</div>
								</div>
								<div id="mo_wpns_help_multisite_solution" class="mo_wpns_help_desc hidden">
									Yes. This plugin does support the 2FA on multisite network.
								</div>
							</td>
						</tr>
						<tr>
							<td class="mo_wpns_help_cell">
								<div id="mo_wpns_help_forgot_password" class="mo_wpns_title_panel">
									<div class="mo_wpns_help_title">I forgot the password of my miniOrange account. How can I reset it?</div>
								</div>
								<div id="mo_wpns_help_forgot_password_solution" class="mo_wpns_help_desc hidden">
									To reset the password of your miniOrange account, please <a href="' . esc_url( MO_HOST_NAME ) . '/moas/idp/resetpassword" target="blank">click here</a> and reset your password using the email address registered with miniOrange.

								
								</div>
							</td>
						</tr>
						<tr>
							<td class="mo_wpns_help_cell">
								<div id="mo_wpns_help_MFA_propmted" class="mo_wpns_title_panel">
									<div class="mo_wpns_help_title">My Users are not being prompted for 2-factor during login. Why?</div>
								</div>
								<div id="mo_wpns_help_MFA_propmted_solution" class="mo_wpns_help_desc hidden">
									If you are on Free plan, you can configure the 2FA upto only 3 users. Otherwise, please check the following settings.
                                   <ol><li>Click on <b>Login Settings</b> tab.</li>
								   <li>Make sure that your have checked the <b>Enable 2FA</b> settings and the user roles for 2FA</li>
								   <li>Scroll down the page and confirm if you have kept the Prevent 2FA Configuration On Login settings unchecked.</li><ol>

								</div>
							</td>
						</tr><tr>
							<td class="mo_wpns_help_cell">
								<div id="mo_wpns_help_redirect_back" class="mo_wpns_title_panel">
									<div class="mo_wpns_help_title">When I enter my authentication code and click on Validate, I got redirected back to the login page, Why is this happening?</div>
								</div>
								<div id="mo_wpns_help_redirect_back_solution" class="mo_wpns_help_desc hidden">
									It might be the case that the plugin is not able to write the PHP session info (into the /var/lib/php/sessions directory ) because of permission issues, and hence it is failing at the authentication step.                   Please update to the latest plugin version(Free- 6.0.4/Premium- 18.0) which has the bug fix for this.								
								</div>
							</td>
						</tr>
						<tr>
							<td class="mo_wpns_help_cell">
								<div id="mo_wpns_help_curl_title" class="mo_wpns_title_panel">
									<div class="mo_wpns_help_title">How to enable PHP cURL extension?</div>
								</div>
								<div id="mo_wpns_help_curl_desc" class="mo_wpns_help_desc hidden">
								   <ol><li>Open php.ini file located under the PHP installation folder.</li>
								   <li>Search for extension=curl.dll.</li>
								   <li>Uncomment it by removing the semi-colon(;) in front of it. Restart the Apache Server.</li>
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
								<ol><li>Download & open POEDIT software.</li>
								<li>Click on Create New Translation.</li>
								<li>In the plugin, you have a lang folder. You can find the .pot file in it. Import the file.</li>
								<li>It will ask you for the translation language. Select it as Germany.</li>
								<li>In the top navigation bar, Click on Update <i class="fa fa-fw fa-arrow-right"></i> Save.
								Do the translations and Save them.</li>
								<li>Select the WordPress site language as the same you selected in the software. ( Germany )</li></ol>
													
								</div>
							</td>
						</tr>
						<tr>
							<td class="mo_wpns_help_cell">
								<div id="mo_wpns_help_refund_title" class="mo_wpns_title_panel">
									<div class="mo_wpns_help_title">What is your refund policy & end user license agreement?</div>
								</div>
								<div id="mo_wpns_help_refund_desc" class="mo_wpns_help_desc hidden">
						            <li><a href="https://plugins.miniorange.com/end-user-license-agreement/#v5-software-warranty-refund-policy" target="_blank">click here</a> to read our refund policy.</li>
									<li><a href="https://plugins.miniorange.com/end-user-license-agreement" target="_blank">click here</a> to read our end user license agreement.</li>
								</div>
							</td>
						</tr>
					</tbody></table>
					<h4>If you have any other queries, Contact us at <a href="mailto:mfasupport@xecurify.com" target="blank">mfasupport@xecurify.com</a></h4>
		    </div>
		</div>
		<script>
		jQuery("#mo_2fa_troubleshooting").addClass("side-nav-active");
		</script>';
