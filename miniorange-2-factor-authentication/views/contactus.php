<?php
/**
 * Support form of the plugin.
 *
 * @package miniorange-2-factor-authentication/views
 */

use TwoFA\Helper\MoWpnsConstants;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

global $mo2f_image_path;
echo '
<div class="w-[296px] mo2f-contact-us-container" style="transition-duration: 300ms;"> 
    <div id="mo_contact_us" class="flex relative justify-end" style="gap: 1rem;">
        <input id="contact-us-toggle" type="checkbox" class="peer sr-only" style="display:none;" />

        <span onClick="mo2FASupportOnClick(\'\')" class="mo_contact_us_box" style="border-radius: 0.125rem; background-color:#1d4ed8;">
            <span class="mo-heading leading-normal" style="font-size:14px; color: rgb(255 255 255);">Hello there! Need Help?<br>Drop us an Email</span>
        </span>

        <span onClick="mo2FASupportOnClick(\'\')">
            <svg width="60" height="60" viewBox="0 0 102 103" fill="none" class="cursor-pointer">
              <g id="d4c51d1a6d24c668e01e2eb6a39325d7">
                <rect width="102" height="103" rx="51" fill="url(#b69bc691e4b17a460c917ded85c3988c)"></rect>
                <g id="0df790d6c3b93208dd73e487cf02eedc">
                  <path id="e161bdf1e94ee39e424acc659f19e97c" fill-rule="evenodd" clip-rule="evenodd" d="M32 51.2336C32 37.5574 36.7619 33 51.0476 33C65.3333 33 70.0952 37.5574 70.0952 51.2336C70.0952 64.9078 65.3333 69.4672 51.0476 69.4672C36.7619 69.4672 32 64.9078 32 51.2336Z" stroke="white" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"></path>
                  <path id="c79e8f13aac8a6b146b9542a01c31ddc" d="M69.0957 44.2959C69.0957 44.2959 56.6508 55.7959 51.5957 55.7959C46.5406 55.7959 34.0957 44.2959 34.0957 44.2959" stroke="white" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"></path>
                </g>
              </g>
              <defs>
                <linearGradient id="b69bc691e4b17a460c917ded85c3988c" x1="0" y1="0" x2="102" y2="103" gradientUnits="userSpaceOnUse">
                  <stop stop-color="#2563eb"></stop>
                  <stop offset="1" stop-color="#1d4ed8"></stop>
                </linearGradient>
              </defs>
            </svg>
        </span>
        <div class="mo2fa_contactus_popup_container" style="display:none;">
        </div>
        <div id="mo-contact-form" class="mo_contactus_popup_wrapper rounded-md hidden animate-fade-in-up">
            <div class="mo-header">
                <h5 class="mo-heading flex" style="flex:1 1 0%;">Contact us</h5>
                    <label class="mo-icon-button" onclick="mo_2fa_contactus_goback()">
                      <svg width="10" height="10" viewBox="0 0 10 10" fill="none">
                        <g id="a8e87dce2cfc3c0d3b0cee61b2290011">
                          <path id="4988f6043ba0a8c6d0d29ca41557a1d8" d="M8.99033 1.00293L1.00366 8.9896" stroke="black" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                          <path id="7c0fb53a248addedc5d06bb436da0b4d" d="M9 9L1 1" stroke="black" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                        </g>
                      </svg>
                    </label>
            </div>
            <form name="f" method="post" action="" value="mo_wpns_send_query" class="flex flex-col gap-mo-3 p-mo-6 mo2f-scrollable-div">
            <input type="hidden" name="option" value="mo_wpns_send_query"/>
            <input type="hidden" name="nonce" value="' . esc_attr( $mo2f_support_form_nonce ) . '">';

			echo '
            
                <div class="mo-input-wrapper">   
                    <label class="mo-input-label">Email</label>
                        <input type="email" class="mo-input w-full" id="mo2f_query_email" name="mo2f_query_email" value="' . esc_attr( $mo2f_email ) . '"
                                placeholder="' . esc_attr( ( 'Enter your Email' ) ) . '" required />
                </div>  

                <div class="mo-input-wrapper">            
                    <label class="mo-input-label">Phone</label>
                        <input type="text" class="mo-input w-full" id="mo2f_query_phone" name="mo2f_query_phone" value="' . esc_attr( $mo2f_phone ) . '"
                            placeholder="' . esc_attr( ( 'Enter your Phone' ) ) . '" /> 
                </div>         
                            
                <div class="mo-input-wrapper">   
                    <textarea id="mo2f_query" name="mo2f_query" class="mo-textarea h-[100px]" style="resize: vertical; width:100%" cols="52" rows="4" placeholder="Write your query here..."></textarea>
                </div> 

                <div id="mo_2fa_plugin_configuration" class="mo-input-wrapper">
                    <input type="checkbox" id="mo2f_send_configuration" name="mo2f_send_configuration" value="1" checked />
                                Send plugin Configuration
                    <br/>
                </div>

                <input type="button" name="send_query" id="mo2f_send_query" value="Submit" class="button button-primary button-large" />
                <a href="https://wordpress.org/support/plugin/miniorange-2-factor-authentication/" target="_blank" class="button mo2f-button secondary">Raise a Support Ticket on WordPress</a>

            </form> 
          </div>  
    </div>
</div>    
';
