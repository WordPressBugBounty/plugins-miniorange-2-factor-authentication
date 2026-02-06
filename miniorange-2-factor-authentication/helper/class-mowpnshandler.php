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

use TwoFA\Traits\Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'MoWpnsHandler' ) ) {

	/**
	 * This library is miniOrange Authentication Service.
	 * Contains Request Calls to Customer service.
	 **/
	class MoWpnsHandler {

		use Instance;

		/**
		 * It is for check the ip is block
		 *
		 * @param string $ip_address .
		 * @return boolean .
		 */
		public function mo_wpns_is_ip_blocked( $ip_address ) {
			global $mo2f_wpns_db_queries;
			if ( empty( $ip_address ) ) {
				return false;
			}

			$user_count = $mo2f_wpns_db_queries->mo2f_get_ip_blocked_count( $ip_address );

			if ( $user_count ) {
				$user_count = intval( $user_count );
			}
			if ( $user_count > 0 ) {
				return true;
			}

			return false;
		}
		/**
		 * Blocking the Ip addresses
		 *
		 * @param string $ip_address .
		 * @param string $reason .
		 * @param string $permenently .
		 * @return void
		 */
		public function mo_wpns_block_ip( $ip_address, $reason, $permenently ) {
			global $mo2f_wpns_db_queries;
			if ( empty( $ip_address ) ) {
				return;
			}
			if ( $this->mo_wpns_is_ip_blocked( $ip_address ) ) {
				return;
			}
			$blocked_for_time = null;
			if ( ! $permenently && get_site_option( 'mo2f_time_of_blocking_type' ) ) {
				$blocking_type        = get_site_option( 'mo2f_time_of_blocking_type' );
				$time_of_blocking_val = 3;
				if ( get_site_option( 'mo2f_time_of_blocking_val' ) ) {
					$time_of_blocking_val = get_site_option( 'mo2f_time_of_blocking_val' );
				}
				if ( 'months' === $blocking_type ) {
					$blocked_for_time = wp_date( 'U' ) + $time_of_blocking_val * 30 * 24 * 60 * 60;
				} elseif ( 'days' === $blocking_type ) {
					$blocked_for_time = wp_date( 'U' ) + $time_of_blocking_val * 24 * 60 * 60;
				} elseif ( 'hours' === $blocking_type ) {
					$blocked_for_time = wp_date( 'U' ) + $time_of_blocking_val * 60 * 60;
				}
			}

			$mo2f_wpns_db_queries->mo2f_insert_blocked_ip( $ip_address, $reason, $blocked_for_time );
			// send notification.
			global $mo2f_mo_wpns_utility;
			if ( MoWpnsUtility::get_mo2f_db_option( 'mo_wpns_enable_ip_blocked_email_to_admin', 'site_option' ) ) {
				$mo2f_mo_wpns_utility->sendIpBlockedNotification( $ip_address, MoWpnsConstants::LOGIN_ATTEMPTS_EXCEEDED );
			}
		}

		/**
		 * The function is to check the whitelisted ip
		 *
		 * @param string $ip_address .
		 * @return boolean
		 */
		public function mo2f_is_whitelisted( $ip_address ) {
			global $mo2f_wpns_db_queries;
			$count = $mo2f_wpns_db_queries->mo2f_get_whitelisted_ip_count( $ip_address );

			if ( empty( $ip_address ) ) {
				return false;
			}
			if ( $count ) {
				$count = intval( $count );
			}

			if ( $count > 0 ) {
				return true;
			}
			return false;
		}
		/**
		 * White listing the ips
		 *
		 * @param string $ip_address .
		 * @return void
		 */
		public function mo2f_whitelist_ip( $ip_address ) {
			global $mo2f_wpns_db_queries;

			if ( empty( $ip_address ) ) {
				return;
			}
			if ( $this->mo2f_is_whitelisted( $ip_address ) ) {
				return;
			}

			$mo2f_wpns_db_queries->mo2f_insert_whitelisted_ip( $ip_address );
		}

		/**
		 * Move failed transaction on table
		 *
		 * @param string $ip_address .
		 * @return void
		 */
		public function move_failed_transactions_to_past_failed( $ip_address ) {
			global $mo2f_wpns_db_queries;
			$mo2f_wpns_db_queries->mo2f_update_transaction_table(
				array(
					'status'     => MoWpnsConstants::FAILED,
					'ip_address' => $ip_address,
				),
				array( 'status' => MoWpnsConstants::PAST_FAILED )
			);
		}
		/**
		 * It will check the ip is block or not
		 *
		 * @param string $user_ip .
		 * @return boolean
		 */
		public function is_ip_blocked_in_anyway( $user_ip ) {
			$is_blocked = false;
			if ( $this->mo_wpns_is_ip_blocked( $user_ip ) ) {
				$is_blocked = true;
			} elseif ( $this->is_ip_range_blocked( $user_ip ) ) {
				$is_blocked = true;
			}

			return $is_blocked;
		}
		/**
		 * It will help to block the range of ip
		 *
		 * @param string $user_ip .
		 * @return boolean
		 */
		public function is_ip_range_blocked( $user_ip ) {
			if ( empty( $user_ip ) ) {
				return false;
			}
			$range_count = 0;
			if ( is_numeric( get_site_option( 'mo_wpns_iprange_count' ) ) ) {
				$range_count = intval( get_site_option( 'mo_wpns_iprange_count' ) );
			}
			for ( $i = 1; $i <= $range_count; $i++ ) {
				$blockedrange = get_site_option( 'mo_wpns_iprange_range_' . $i );
				$rangearray   = explode( '-', $blockedrange );
				if ( 2 === count( $rangearray ) ) {
					$lowip  = ip2long( trim( $rangearray[0] ) );
					$highip = ip2long( trim( $rangearray[1] ) );
					if ( ip2long( $user_ip ) >= $lowip && ip2long( $user_ip ) <= $highip ) {
						$mo_wpns_config = new MoWpnsHandler();
						$mo_wpns_config->mo_wpns_block_ip( $user_ip, MoWpnsConstants::IP_RANGE_BLOCKING, true );
						return true;
					}
				}
			}
			return false;
		}

		/**
		 * Lockedoutlink
		 *
		 * @return string
		 */
		public function locked_out_link() {
			if ( MO2F_IS_ONPREM ) {
				return MoWpnsConstants::ONPREMISELOCKEDOUT;
			} else {
				return MoWpnsConstants::CLOUDLOCKOUT;
			}
		}
	}
}
