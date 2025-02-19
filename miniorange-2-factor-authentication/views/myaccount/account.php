<?php
/**
 * Shows account details of the user.
 *
 * @package miniorange-2-factor-authentication/views/myaccount/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
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
echo '<script type="text/javascript">
     jQuery("#mo_2fa_my_account").addClass("side-nav-active");
     jQuery("#mo2f-myaccount-details").addClass("side-nav-active");
     jQuery("#mo2f-myaccount-submenu").css("display", "block");
 </script>';

