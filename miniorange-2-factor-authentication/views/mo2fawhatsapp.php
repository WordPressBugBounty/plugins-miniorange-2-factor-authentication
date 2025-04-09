<?php
/**
 * Pricing page of the plugin.
 *
 * @package miniorange-2-factor-authentication/views
 */

use TwoFA\Helper\MoWpnsConstants;
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

echo '<div id="whatsappTable" class="mo2f-settings-div mo2f-whatsapp-div">
                <input type="hidden" name="nonce" value="' . esc_attr( $manual_report_clear_nonce ) . '">
                <input type="hidden" name="option" value="mo2f_enable_whatsapp_otp" />
		    	<div class="mo2f-whatsapp-header">
					<p class="mo-heading flex-1">' . esc_html( __( 'WhatsApp Configuration Settings', 'miniorange-2-factor-authentication' ) ) . '</p>';
					echo MoWpnsConstants::PREMIUM_CROWN; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Only a SVG, doesn't require escaping.
echo '			</div>
				<div class="m-mo-4 mo2f-basic-plan">
					<div style="padding-left:10px;">
						
						<div class="wp_business_details w-[75%]" id="whatsapp_businesss_options">
							<div class="flex gap-mo-3 py-mo-2">
								<div class="mo-input-wrapper">
									<label class="mo-input-label">' . esc_html( __( 'Phone Number Id', 'miniorange-2-factor-authentication' ) ) . '</label>
									<input class=" mo-input py-mo-2 w-full" placeholder="Enter the Phone Number Id" value="' . esc_attr( $phone_number_id ) . '" type="text" name="mo_whatsapp_phone_number_id" id="mo_whatsapp_phone_number_id" >
								</div>
								<div class="mo-input-wrapper">
									<label class="mo-input-label">' . esc_html( __( 'Template Name', 'miniorange-2-factor-authentication' ) ) . '</label>
									<input class=" mo-input pt-mo-2 w-full" placeholder="Enter the Template Name" value="' . esc_attr( $template_name ) . '" type="text" name="mo_whatsapp_template_name" id="mo_whatsapp_template_name" >
								</div>
							</div>
							<div class="flex gap-mo-3 py-mo-2" >
								<div class="mo-input-wrapper">
									<label class="mo-input-label">' . esc_html( __( 'Template Language', 'miniorange-2-factor-authentication' ) ) . '</label>
									<input class=" mo-input w-full" placeholder="Enter the Template Language" value="' . esc_attr( $language ) . '" type="text" name="mo_whatsapp_template_language" id="mo_whatsapp_template_language" >
								</div>
								<div class="mo-input-wrapper">
									<label class="mo-input-label">' . esc_html( __( 'OTP Length', 'miniorange-2-factor-authentication' ) ) . '</label>
									<input class=" mo-input pt-mo-2 w-full" placeholder="Enter the OTP Length" value="' . esc_attr( $mo_otp_length ) . '" type="text" name="mo_whatsapp_otp_length" id="mo_whatsapp_otp_length" >
								</div>
							</div>
                            <div class="py-mo-2">
                                <div class="mo-input-wrapper">
                                    <label class="mo-input-label">' . esc_html( __( 'Enter Access Token', 'miniorange-2-factor-authentication' ) ) . '</label>
                                    <textarea name="mo_whatsapp_access_token" rows="3" class="mo-textarea" id="mo_whatsapp_access_token" >' . esc_html( $access_token ) . '</textarea>
                                </div>
                            </div>
						</div>
						<button 
							type="submit" 
							name="save" 
							id="mo2f_save_whatsapp_otp_settings" 
							class="mo2f-save-settings-button" >
							' . esc_html( __( 'Save Settings', 'miniorange-2-factor-authentication' ) ) . '
						</button>
					</div>
				</div>
		</div>';
echo '<script type="text/javascript">
		jQuery("#mo_2fa_whatsapp").addClass("side-nav-active");
	</script>';
