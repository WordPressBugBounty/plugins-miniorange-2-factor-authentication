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
        <div class="mo2f-table-layout ' . ( get_option( 'mo2f_customerKey' ) ? '' : 'hidden' ) . '" id="mo2f_account_details">
    <div>
        <h3>' . esc_html__( 'Your Profile', 'miniorange-2-factor-authentication' ) . '</h3>
        <table>
            <tr>
                <td class="mo2f-myaccount-column1">' . esc_html__( 'Username/Email', 'miniorange-2-factor-authentication' ) . '</td>
                <td class="mo2f-myaccount-column2">' . esc_html( $mo2f_email ) . '</td>
            </tr>
            <tr>
                <td>' . esc_html__( 'Customer ID', 'miniorange-2-factor-authentication' ) . '</td>
                <td>' . esc_html( $mo2f_key ) . '</td>
            </tr>
            <tr>
                <td>' . esc_html__( 'API Key', 'miniorange-2-factor-authentication' ) . '</td>
                <td>' . esc_html( $mo2f_api ) . '</td>
            </tr>
            <tr>
                <td>' . esc_html__( 'Token Key', 'miniorange-2-factor-authentication' ) . '</td>
                <td>' . esc_html( $mo2f_token ) . '</td>
            </tr>
        </table>
        <div class="mo2f-flex-table">
            <a id="mo_logout" class="mo2f-remove-account-button">' . esc_html__( 'Remove Account', 'miniorange-2-factor-authentication' ) . '</a>
        </div>
    </div>
</div>';
echo '<script type="text/javascript">
     jQuery("#mo_2fa_my_account").addClass("side-nav-active");
     jQuery("#mo2f-myaccount-details").addClass("side-nav-active");
     jQuery("#mo2f-myaccount-submenu").css("display", "block");
 </script>';
