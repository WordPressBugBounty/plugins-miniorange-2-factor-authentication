<?php
/**
 * This file contains the html UI for the miniOrange account details.
 *
 * @package miniorange-2-factor-authentication/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use TwoFA\Helper\Mo2f_Common_Helper;
use TwoFA\Helper\MoWpnsUtility;
if ( ! get_option( 'mo2f_customerKey' ) ) {
	$common_helper = new Mo2f_Common_Helper();
	$skeleton      = array(
		'##crossbutton##'    => '',
		'##miniorangelogo##' => '',
		'##pagetitle##'      => '<h3>' . __( 'Login/Register with miniOrange', 'miniorange-2-factor-authentication' ) . '</h3>',
	);
	$html          = $common_helper->mo2f_get_miniorange_user_registration_prompt( '', null, null, 'myaccount', $skeleton );
	echo '<div class="" id="mo2f_login_registration_div">' . $html . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already escaped the necessary in the definition.
} else {
	echo '
        <div class="mo2f-table-layout" id="mo2f_account_details" style="display:' . ( get_option( 'mo2f_customerKey' ) ? 'block' : 'none' ) . '" >
        <div>
            <div class="w-5/6">
                <h4>Thank You for registering with miniOrange.
                    <div style="float: right;">';

				echo '</div>
                </h4>
                <h3>Your Profile</h3>
                <h2 >
                 <a id="mo2f_transaction_check" class="mo2f-save-settings-button">Refresh Available Email & SMS Transactions</a>
               </h2>
                <table border="1" style="background-color:#FFFFFF; border:1px solid #CCCCCC; border-collapse: collapse; padding:0px 0px 0px 10px; margin:2px; width:100%">
                    <tr>
                        <td style="width:45%; padding: 10px;">Username/Email</td>
                        <td style="width:55%; padding: 10px;">' . esc_html( $email ) . '</td>
                    </tr>
                    <tr>
                        <td style="width:45%; padding: 10px;">Customer ID</td>
                        <td style="width:55%; padding: 10px;">' . esc_html( $key ) . '</td>
                    </tr>
                    <tr>
                        <td style="width:45%; padding: 10px;">API Key</td>
                        <td style="width:55%; padding: 10px;">' . esc_html( $api ) . '</td>
                    </tr>
                    <tr>
                        <td style="width:45%; padding: 10px;">Token Key</td>
                        <td style="width:55%; padding: 10px;">' . esc_html( $token ) . '</td>
                    </tr>
        
                    <tr>
                        <td style="width:45%; padding: 10px;">Remaining Email transactions</td>
                        <td style="width:55%; padding: 10px;">' . esc_html( $email_transactions ) . '</td>
                    </tr>
                    <tr>
                        <td style="width:45%; padding: 10px;">Remaining SMS transactions</td>
                        <td style="width:55%; padding: 10px;">' . esc_html( $sms_transactions ) . '</td>
                    </tr>
        
                </table>
                <br/>
                <div class="flex justify-center">';
			echo '
                <a id="mo_logout" class="mo2f-reset-settings-button" >Remove Account</a>
                </div>
            </div>
        </div>
        </div>
     ';

	?>
		<script type="text/javascript">
			jQuery("#mo_2fa_my_account").addClass("side-nav-active");
			var nonce = '<?php echo esc_js( wp_create_nonce( 'mo-two-factor-ajax-nonce' ) ); ?>';
			jQuery(document).ready(function()
			{
				jQuery("#mo_logout").click(function()
				{
					var data =  
					{
						'action': "mo_two_factor_ajax",
						'mo_2f_two_factor_ajax': "mo2f_remove_miniorange_account",
						'nonce'                   : nonce  
					};
					jQuery.post(ajaxurl, data, function(response) {
						success_msg(response.data);
						window.location.reload(true);
					});
				});
				jQuery("#mo2f_transaction_check").click(function()
				{
					var data =  
					{  
						'action'                  : 'mo_two_factor_ajax',
						'mo_2f_two_factor_ajax' : 'mo2f_check_transactions', 
						'nonce'                   : nonce  
					};
					jQuery.post(ajaxurl, data, function(response) {
						success_msg(response.data);
					});
				});
				function success_msg(msg) {
					jQuery("#wpns_nav_message").empty();
					jQuery("#wpns_nav_message").append(
						"<div id='notice_div' class='overlay_success' style='z-index:9999'><div class='popup_text'>" +
							msg +
							"</div></div>"
					);
					window.onload = nav_popup();
				}
				});
		</script>
		<?php
}
