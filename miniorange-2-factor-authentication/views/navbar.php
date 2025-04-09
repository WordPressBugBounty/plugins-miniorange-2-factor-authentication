<?php
/**
 * Navigation bar of the plugin dashboard
 *
 * @package miniorange-2-factor-authentication/views
 */

// Needed in both.
use TwoFA\Helper\MoWpnsMessages;
use TwoFA\Helper\MoWpnsUtility;
use TwoFA\Helper\MoWpnsConstants;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

global $mo2f_dir_name;
$security_features_nonce = wp_create_nonce( 'mo_2fa_security_features_nonce' );

	$user         = wp_get_current_user();
	$user_id      = wp_get_current_user()->ID;
	$onprem_admin = get_option( 'mo2f_onprem_admin' );
	$roles        = (array) $user->roles;
	$is_onprem    = MO2F_IS_ONPREM;
		$flag     = 0;
foreach ( $roles as $role_name ) {
	if ( 1 === get_option( 'mo2fa_' . $role_name ) ) {
		$flag = 1;
	}
}
if ( get_transient( 'ip_whitelisted' ) && current_user_can( 'administrator' ) ) {
	echo wp_kses_post( MoWpnsMessages::show_message( 'ADMIN_IP_WHITELISTED' ) );
}
if ( ! $safe ) {
	if ( MoWpnsUtility::get_mo2f_db_option( 'mo_wpns_2fa_with_network_security', 'site_option' ) && current_user_can( 'administrator' ) ) {
		echo wp_kses_post( MoWpnsMessages::show_message( 'WHITELIST_SELF' ) );
	}
}
if ( ( ! get_user_meta( $user_id, 'mo_backup_code_generated', true ) || ( 5 === $backup_codes_remaining && ! get_user_meta( $user_id, 'mo_backup_code_downloaded', true ) ) ) && ! empty( $mo2f_two_fa_method ) && ! get_user_meta( $user_id, 'donot_show_backup_code_notice', true ) ) {
	echo wp_kses_post( MoWpnsMessages::show_message( 'GET_BACKUP_CODES' ) );
}
?>
<?php
			echo '<div style="display:flex; flex-direction:column; margin-left:-20px;">
			<div class="wrap mo2f-header">';

				$date1           = '2022-01-10';
				$date_timestamp1 = strtotime( $date1 );

				$date2           = gmdate( 'Y-m-d' );
				$date_timestamp2 = strtotime( $date2 );

if ( $date_timestamp2 <= $date_timestamp1 && ( $user_id === $onprem_admin ) && ! get_site_option( 'mo2f_banner_never_show_again' ) ) {
	echo '<div class="mo2f_offer_main_div">

					

					<div class="mo2f_offer_first_section">
                        <p class="mo2f_offer_christmas">CHRISTMAS</p>
                        <h3 class= "mo2fa_hr_line"><span>&</span></h3>
                        <p class="mo2f_offer_cyber">NEW YEAR&nbsp;<spn style="color:white;">SALE</span></p>
                    </div>

					<div class="mo2f_offer_middle_section">
						<p class="mo2f_offer_get_upto"><span style="font-size: 30px;">GET UPTO <span style="color: white;font-size: larger; font-weight:bold">50%</span> OFF ON PREMIUM PLUGINS</p><br>
						<p class="mo2f_offer_valid">Offer valid for limited period only!</p>
					</div>

					<div id="mo2f_offer_last_section" class="mo2f_offer_last_section"><button class="mo2f_banner_never_show_again mo2f_close">CLOSE <span class=" mo2f_cross">X</span></button><a class="mo2f_offer_contact_us" href="' . esc_url( $request_offer_url ) . '">Contact Us</a></p></div>

					</div><br><br>';
}
				echo ' <div class="mo2f-admin-options"> <div class="mx-mo-3"> <img width="30" height="30" src="' . esc_url( $logo_url ) . '"></div>';

	echo '<span class="mo2f-plugin-name">Two-Factor Authentication</span>';
	echo '
		<div class="flex mo2f-text-xs-white">
    		<div id="mo2f_check_transactions" class="mo2f-transaction-show">
				<div id="mo2f_remaining_transactions_sms_email">
        			SMS: ' . esc_attr( $remaining_transaction['sms_transactions'] ) . '  |  Email: ' . esc_attr( $remaining_transaction['email_transactions'] ) . '   
				</div>      
        		<div class="relative mo2f-30x30">
            		<button id="mo2f_transaction_check" class="mo2f-refresh-btn mo2f-full-size">
						<svg width="18" height="18" viewBox="0 0 512 512">
							<path d="M320,146s24.36-12-64-12A160,160,0,1,0,416,294" class="mo2f-stroke"/>
							<polyline points="256 58 336 138 256 218" class="mo2f-stroke"/>
						</svg>
            		</button>
            		<span id="mo2f_transaction_loader" class="mo2f_transaction_loader mo2f-full-size hidden"></span>
        		</div>
    		</div>
    		<div> 
        		<a href=' . esc_url( MoWpnsConstants::RECHARGELINK ) . ' target="_blank" class="mo2f-button">' . esc_html__( 'Recharge', 'miniorange-2-factor-authentication' ) . '</a>
    		</div>
		</div>';
	echo '</div>';
if ( current_user_can( 'administrator' ) && get_site_option( 'mo_wpns_2fa_with_network_security' ) || get_site_option( 'mo2f_is_old_customer' ) ) {
			update_site_option( 'mo2f_is_old_customer', 1 );

			echo '	<form id="mo_wpns_2fa_with_network_security" method="post" action="">
								<div class="mo2f-security-toggle"> 

								
								<input type="hidden" name="mo_security_features_nonce" value="' . esc_html( $security_features_nonce ) . '"/>

									<input type="hidden" name="option" value="mo_wpns_2fa_with_network_security">
									<div>2FA + Website Security
									<span>
										<label class="mo_wpns_switch">
										<input type="checkbox" name="mo_wpns_2fa_with_network_security" ' . esc_html( $network_security_features ) . '  onchange="document.getElementById(\'mo_wpns_2fa_with_network_security\').submit();"> 
										<span class="mo_wpns_slider mo_wpns_round"></span>
										</label>
									</span>
									</div>
									
									
									</div>
									</form>';
}


					echo '</div></div>';
					echo '<div id = "wpns_nav_message"></div>';

