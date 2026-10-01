<?php
/** The miniOrange enables user to log in through mobile authentication as an additional layer of security over password.
 * Copyright (C) 2015  miniOrange
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 * @package        miniorange-2-factor-authentication/helper
 */

namespace TwoFA\Helper;

use TwoFA\Helper\MoWpnsUtility;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Miniorange_Security_Notification' ) ) {
	/**
	 * This library is miniOrange Authentication Service.
	 * Contains Request Calls to Customer service.
	 **/
	class Miniorange_Security_Notification {
		/**
		 * It will help to show dashboard
		 *
		 * @return void
		 */
		public function my_custom_dashboard_widgets() {

			wp_add_dashboard_widget( 'custom_help_widget', 'MiniOrange Website Security', array( $this, 'custom_dashboard_help' ) );
			wp_add_dashboard_widget( 'mo2f_2fa_adoption_widget', __( '2FA Adoption', 'miniorange-2-factor-authentication' ), array( $this, 'mo2f_2fa_adoption_dashboard_widget' ) );
		}

		/**
		 * Renders the "2FA Adoption" dashboard widget.
		 *
		 * @return void
		 */
		public function mo2f_2fa_adoption_dashboard_widget() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}
			global $mo2fdb_queries;
			$total_users_data = count_users();
			$total            = isset( $total_users_data['total_users'] ) ? (int) $total_users_data['total_users'] : 0;
			$configured       = $mo2fdb_queries->mo2f_get_configured_2fa_users_count();
			$not_configured   = max( 0, $total - $configured );
			$percent        = $total > 0 ? round( ( $configured / $total ) * 100 ) : 0;
			?>
			<div style="text-align:center;padding:10px 0 16px;">
				<span style="font-size:42px;font-weight:600;color:<?php echo esc_attr( $percent >= 80 ? '#00a32a' : ( $percent >= 40 ? '#dba617' : '#d63638' ) ); ?>;"><?php echo esc_html( $percent ); ?>%</span>
				<p style="margin:4px 0 0;color:#646970;"><?php echo esc_html( sprintf(
					/* Translators: 1: number of users with 2FA configured, 2: total number of users. */
					__( '%1$d of %2$d users have 2FA configured', 'miniorange-2-factor-authentication' ),
					$configured,
					$total
				) ); ?></p>
			</div>
			<?php if ( $not_configured > 0 ) : ?>
				<p>
					<?php echo esc_html( sprintf(
						/* Translators: %d: number of users without 2FA configured. */
						_n( '%d user still needs to set up 2FA.', '%d users still need to set up 2FA.', $not_configured, 'miniorange-2-factor-authentication' ),
						$not_configured
					) ); ?>
					<a href="<?php echo esc_url( admin_url( 'users.php' ) ); ?>"><?php esc_html_e( 'View users', 'miniorange-2-factor-authentication' ); ?></a>
				</p>
			<?php else : ?>
				<p><?php esc_html_e( 'Every user on this site has 2FA configured.', 'miniorange-2-factor-authentication' ); ?></p>
			<?php endif; ?>
			<?php
		}
		/**
		 * It will help to custom dashboard
		 *
		 * @return void
		 */
		public function custom_dashboard_help() {

			if ( current_user_can( 'manage_options' ) ) {
				// Inline CSS used intentionally as external files can't be loaded in the admin dashboard.
				echo "<html>
                       
              <div style='width:100%;background-color:#555f5f;padding-top:10px;''>
              <div style='font-size:25px;color:white;text-align:center'>
            <strong style='font-weight:300;''>" . esc_html__( 'Remaining Transactions ', 'miniorange-2-factor-authentication' ) . "<span style='color:orange;'>[OTPs]</strong>
      
                </div>
                <hr>
       
                ";

				$email_transactions = apply_filters( 'mo2f_is_lv_needed', false ) ? 'Unlimited' : get_site_option( 'cmVtYWluaW5nT1RQ', 0 );
				$sms_transactions   = get_site_option( 'cmVtYWluaW5nT1RQVHJhbnNhY3Rpb25z', 0 );
				echo '<table style="solid #CCCCCC; border-collapse: collapse; padding:0px 0px 0px 10px; margin:2px; width:99%">
          <tr>  
                        <td style="font-size:18px;color:#ffffff;padding: 10px;"><strong style="font-weight:300;">' . esc_html__( 'Remaining SMS transactions ', 'miniorange-2-factor-authentication' ) . '</strong></td>
                        <td style="text-align:center;font-size:36px;color:#ffffff;font-weight:400" ><strong>' . esc_html( isset( $sms_transactions ) ? $sms_transactions : '' ) . '</strong></td>
                                                              
                    </tr>
                    <tr>
                        <td style="font-size:18px;color:#ffffff;padding: 10px;"><strong style="font-weight:300;">' . esc_html__( 'Remaining Email transactions ', 'miniorange-2-factor-authentication' ) . '</strong></td>
                        <td style="text-align:center;font-size:36px;color:#ffffff;font-weight:400" ><strong>' . esc_html( isset( $email_transactions ) ? $email_transactions : '' ) . '</strong></td>
                                      
                        
                    </tr>

                </table><br>';

				echo '</div>

          ';
			}
		}
	}
}
