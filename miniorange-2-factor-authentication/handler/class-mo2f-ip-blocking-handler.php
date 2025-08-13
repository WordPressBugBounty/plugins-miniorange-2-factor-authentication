<?php
/**
 * This file contains the ajax request handler.
 *
 * @package miniorange-2-factor-authentication/twofactor/loginsettings/handler
 */

namespace TwoFA\Handler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use TwoFA\Helper\MoWpnsHandler;
use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Helper\MoWpnsMessages;
use TwoFA\Traits\Instance;
use Exception;
use DateTime;
use DateTimeZone;

if ( ! class_exists( 'Mo2f_IP_Blocking_Handler' ) ) {

	/**
	 * Class Mo2f_IP_Blocking_Handler
	 */
	class Mo2f_IP_Blocking_Handler {

		use Instance;

		/**
		 * Mo2f_IP_Blocking_Handler class custructor.
		 */
		public function __construct() {
			add_action( 'admin_init', array( $this, 'mo_2f_two_factor' ) );
			add_action( 'admin_init', array( $this, 'mo2f_handle_advanced_blocking' ) );

		}
		/**
		 * Function for handling ajax requests.
		 *
		 * @return void
		 */
		public function mo_2f_two_factor() {
			add_action( 'wp_ajax_mo2f_ip_black_list_ajax', array( $this, 'mo2f_ip_black_list_ajax' ) );
		}

		/**
		 * Handle advanced blocking
		 *
		 * @return void
		 */
		public function mo2f_handle_advanced_blocking() {
			if ( current_user_can( 'manage_options' ) && isset( $_POST['option'] ) && isset( $_POST['mo2f_security_features_nonce'] ) ) {
				if ( ! wp_verify_nonce( ( sanitize_key( $_POST['mo2f_security_features_nonce'] ) ), 'mo2f_security_nonce' ) ) {
					$show_message = new MoWpnsMessages();
					$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::SOMETHING_WENT_WRONG ), 'ERROR' );
				} else {
					switch ( sanitize_text_field( wp_unslash( $_POST['option'] ) ) ) {
						case 'mo_wpns_block_ip_range':
							$this->wpns_handle_range_blocking( $_POST );
							break;
					}
				}
			}
		}

		/**
		 * Calls the function according to the switch case.
		 *
		 * @return void
		 */
		public function mo2f_ip_black_list_ajax() {

			if ( ! check_ajax_referer( 'mo2f-ip-black-list-ajax-nonce', 'nonce', false ) || ! current_user_can( 'manage_options' ) ) {
				wp_send_json( 'ajax-error' );
			}
			$GLOBALS['mo2f_is_ajax_request'] = true;
			$option                          = isset( $_POST['option'] ) ? sanitize_text_field( wp_unslash( $_POST['option'] ) ) : '';
			switch ( $option ) {
				case 'mo_wpns_manual_block_ip':
					$this->wpns_handle_manual_block_ip( $_POST );
					break;
				case 'mo_wpns_whitelist_ip':
					$this->wpns_handle_whitelist_ip( $_POST );
					break;
				case 'mo_wpns_ip_lookup':
					$this->mo_wpns_ip_lookup( $_POST );
					break;
				case 'mo_wpns_unblock_ip':
					$this->wpns_handle_unblock_ip( $_POST );
					break;
				case 'mo_wpns_remove_whitelist':
					$this->wpns_handle_remove_whitelist( $_POST );
					break;
			}
		}

		/**
		 * Handles manual ip blocking and whitelisting.
		 *
		 * @param string $post Post data.
		 * @return void
		 */
		public function wpns_handle_manual_block_ip( $post ) {
			global $mo_wpns_utility;
			$ip = isset( $post['IP'] ) ? sanitize_text_field( wp_unslash( $post['IP'] ) ) : '';
			if ( $mo_wpns_utility->check_empty_or_null( $ip ) ) {
				echo( 'empty IP' );
				exit;
			}
			if ( ! preg_match( '/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\z/', $ip ) ) {
				echo( 'INVALID_IP_FORMAT' );
				exit;
			} else {

				$ip_address     = filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : 'INVALID_IP_FORMAT';
				$mo_wpns_config = new MoWpnsHandler();
				$is_whitelisted = $mo_wpns_config->mo2f_is_whitelisted( $ip_address );
				if ( ! $is_whitelisted ) {
					if ( $mo_wpns_config->mo_wpns_is_ip_blocked( $ip_address ) ) {
						echo( 'already blocked' );
						exit;
					} else {
						$mo_wpns_config->mo_wpns_block_ip( $ip_address, MoWpnsConstants::BLOCKED_BY_ADMIN, true );
						?>
							<table id="blockedips_table1" class="display">
						<thead><tr><th>IP Address&emsp;&emsp;</th><th>Reason&emsp;&emsp;</th><th>Blocked Until&emsp;&emsp;</th><th>Blocked Date&emsp;&emsp;</th><th>Action&emsp;&emsp;</th></tr></thead>
						<tbody>
						<?php
						global $wpns_db_queries;
						$blockedips      = $wpns_db_queries->mo2f_get_blocked_ip_list();
						$whitelisted_ips = $wpns_db_queries->mo2f_get_whitelisted_ips_list();
						global $mo2f_dir_name;
						foreach ( $blockedips as $blockedip ) {
							echo "<tr class='mo_wpns_not_bold'><td>" . esc_html( $blockedip->ip_address ) . '</td><td>' . esc_html( $blockedip->reason ) . '</td><td>';
							if ( empty( $blockedip->blocked_for_time ) ) {
								echo '<span class=redtext>Permanently</span>';
							} else {
								echo esc_html( gmdate( 'M j, Y, g:i:s a', $blockedip->blocked_for_time ) );
							}
							echo '</td><td>' . esc_html( gmdate( 'M j, Y, g:i:s a', $blockedip->created_timestamp ) ) . "</td><td><a  onclick=unblockip('" . esc_js( $blockedip->id ) . "')>Unblock IP</a></td></tr>";
						}
						?>
							</tbody>
							</table>
							<script type="text/javascript">
								jQuery("#blockedips_table1").DataTable({
								"order": [[ 3, "desc" ]]
								});
							</script>
						<?php
						exit;
					}
				} else {
					echo( 'IP_IN_WHITELISTED' );
					exit;
				}
			}
		}

		/**
		 * Handles the whitelisting ips.
		 *
		 * @param string $post Post data.
		 * @return void
		 */
		public function wpns_handle_whitelist_ip( $post ) {
			global $mo_wpns_utility;
			$ip = isset( $post['IP'] ) ? sanitize_text_field( wp_unslash( $post['IP'] ) ) : '';
			if ( $mo_wpns_utility->check_empty_or_null( $ip ) ) {
				echo( 'EMPTY IP' );
				exit;
			}
			if ( ! preg_match( '/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\z/', $ip ) ) {
				echo( 'INVALID_IP' );
				exit;
			} else {
				$ip_address     = ( filter_var( $ip, FILTER_VALIDATE_IP ) ) ? $ip : 'INVALID_IP';
				$mo_wpns_config = new MoWpnsHandler();
				if ( $mo_wpns_config->mo2f_is_whitelisted( $ip_address ) ) {
					echo( 'IP_ALREADY_WHITELISTED' );
					exit;
				} else {
					$mo_wpns_config->mo2f_whitelist_ip( $ip );
					global $wpns_db_queries;
					$whitelisted_ips = $wpns_db_queries->mo2f_get_whitelisted_ips_list();

					?>
				<table id="whitelistedips_table1" class="display">
				<thead><tr><th >IP Address</th><th >Whitelisted Date</th><th >Remove from Whitelist</th></tr></thead>
				<tbody>
					<?php
					foreach ( $whitelisted_ips as $whitelisted_ip ) {
						echo "<tr class='mo_wpns_not_bold'><td>" . esc_html( $whitelisted_ip->ip_address ) . '</td><td>' . esc_html( gmdate( 'M j, Y, g:i:s a', $whitelisted_ip->created_timestamp ) ) . "</td><td><a  onclick=removefromwhitelist('" . esc_js( $whitelisted_ip->id ) . "')>Remove</a></td></tr>";
					}

					?>
				</tbody>
				</table>
			<script type="text/javascript">
				jQuery("#whitelistedips_table1").DataTable({
				"order": [[ 1, "desc" ]]
				});
			</script>

					<?php
					exit;
				}
			}
		}

		/**
		 * Creates ip look up template.
		 *
		 * @param string $post Post data.
		 * @return void
		 */
		public function mo_wpns_ip_lookup( $post ) {
			$ip = isset( $post['IP'] ) ? sanitize_text_field( wp_unslash( $post['IP'] ) ) : '';
			if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) === false ) {
				wp_send_json( 'INVALID_IP' );
			}
			$result = wp_remote_get( 'https://ipinfo.io/' . $ip . '/json' );
			if ( ! is_wp_error( $result ) ) {
				$result = json_decode( wp_remote_retrieve_body( $result ), true );
			}
		
			if ( json_last_error() === JSON_ERROR_NONE ) {
				// Validate JSON response before processing.
				if ( ! is_array( $result ) || ! isset( $result['ip'] ) ) {
					wp_send_json( 'INVALID_RESPONSE' );
				}

				$hostname = gethostbyaddr( $result['ip'] );
				try {
					$timeoffset = timezone_offset_get( new DateTimeZone( $result['timezone'] ), new DateTime( 'now' ) );
					$timeoffset = $timeoffset / 3600;
				} catch ( Exception $e ) {
					$result['timezone'] = '';
					$timeoffset         = '';
				}

				$ip_look_up_template = MoWpnsConstants::IP_LOOKUP_TEMPLATE;
				if ( $result['ip'] === $ip ) {
					// Sanitize all data before template replacement.
					$sanitized_data = $this->mo2f_build_ip_lookup_sanitized_data( $result, $hostname, $timeoffset );

					// Replace placeholders with sanitized data.
					foreach ( $sanitized_data as $placeholder => $value ) {
						$ip_look_up_template = str_replace( $placeholder, $value, $ip_look_up_template );
					}

					// Apply additional HTML sanitization to the entire template.
					$allowed_html        = $this->mo2f_get_ip_lookup_allowed_html();
					$result['status']    = 'SUCCESS';
					$result['ipDetails'] = wp_kses( $ip_look_up_template, $allowed_html );
				} else {
					$result['ipDetails'] = array( 'status' => 'ERROR' );
				}
				wp_send_json( $result );
			} else {
				wp_send_json( 'INVALID_RESPONSE' );
			}
		}

		/**
		 * Handles the unblock ip.
		 *
		 * @param string $post Post data.
		 * @return void
		 */
		public function wpns_handle_unblock_ip( $post ) {
			global $mo_wpns_utility;
			$entry_id = isset( $post['id'] ) ? sanitize_text_field( wp_unslash( $post['id'] ) ) : '';
			if ( $mo_wpns_utility->check_empty_or_null( $entry_id ) ) {
				echo( 'UNKNOWN_ERROR' );
				exit;
			} else {
				$entryid = sanitize_text_field( $entry_id );
				global $wpns_db_queries;
				$wpns_db_queries->mo2f_delete_blocked_ip( $entryid );
				?>
				<table id="blockedips_table1" class="display">
				<thead><tr><th>IP Address&emsp;&emsp;</th><th>Reason&emsp;&emsp;</th><th>Blocked Until&emsp;&emsp;</th><th>Blocked Date&emsp;&emsp;</th><th>Action&emsp;&emsp;</th></tr></thead>
				<tbody>
				<?php
				global $wpns_db_queries;
				$blockedips      = $wpns_db_queries->mo2f_get_blocked_ip_list();
				$whitelisted_ips = $wpns_db_queries->mo2f_get_whitelisted_ips_list();
				foreach ( $blockedips as $blockedip ) {
					echo "<tr class='mo_wpns_not_bold'><td>" . esc_html( $blockedip->ip_address ) . '</td><td>' . esc_html( $blockedip->reason ) . '</td><td>';
					if ( empty( $blockedip->blocked_for_time ) ) {
						echo '<span class=redtext>Permanently</span>';
					} else {
						echo esc_html( gmdate( 'M j, Y, g:i:s a', $blockedip->blocked_for_time ) );
					}
					echo '</td><td>' . esc_html( gmdate( 'M j, Y, g:i:s a', $blockedip->created_timestamp ) ) . "</td><td><a onclick=unblockip('" . esc_js( $blockedip->id ) . "')>Unblock IP</a></td></tr>";
				}
				?>
					</tbody>
					</table>
					<script type="text/javascript">
						jQuery("#blockedips_table1").DataTable({
						"order": [[ 3, "desc" ]]
						});
					</script>
				<?php

				exit;
			}
		}

		/**
		 * Remove the whitelisted ips.
		 *
		 * @param string $post Post data.
		 * @return void
		 */
		public function wpns_handle_remove_whitelist( $post ) {
			global $mo_wpns_utility;
			$entry_id = isset( $post['id'] ) ? sanitize_text_field( wp_unslash( $post['id'] ) ) : '';
			if ( $mo_wpns_utility->check_empty_or_null( $entry_id ) ) {
				echo( 'UNKNOWN_ERROR' );
				exit;
			} else {
				$entryid = isset( $entry_id ) ? sanitize_text_field( $entry_id ) : '';
				global $wpns_db_queries;
				$wpns_db_queries->mo2f_delete_whitelisted_ip( $entryid );
				$whitelisted_ips = $wpns_db_queries->mo2f_get_whitelisted_ips_list();

				?>
				<table id="whitelistedips_table1" class="display">
				<thead><tr><th >IP Address</th><th >Whitelisted Date</th><th >Remove from Whitelist</th></tr></thead>
				<tbody>
				<?php
				foreach ( $whitelisted_ips as $whitelisted_ip ) {
					echo "<tr class='mo_wpns_not_bold'><td>" . esc_html( $whitelisted_ip->ip_address ) . '</td><td>' . esc_html( gmdate( 'M j, Y, g:i:s a', $whitelisted_ip->created_timestamp ) ) . "</td><td><a onclick=removefromwhitelist('" . esc_js( $whitelisted_ip->id ) . "')>Remove</a></td></tr>";
				}

				?>
				</tbody>
				</table>
			<script type="text/javascript">
				jQuery("#whitelistedips_table1").DataTable({
				"order": [[ 1, "desc" ]]
				});
			</script>

				<?php
				exit;
			}
		}
		/**
		 * Description: Function to save range of ips.
		 *
		 * @param array $posted_value It contains the start and end of range of ips.
		 * @return void
		 */
		public function wpns_handle_range_blocking( $posted_value ) {
			$flag                  = 0;
			$max_allowed_ranges    = 100;
			$added_mappings_ranges = 0;
			$show_message          = new MoWpnsMessages();
			for ( $i = 1;$i <= $max_allowed_ranges;$i++ ) {
				if ( isset( $posted_value[ 'start_' . $i ] ) && isset( $posted_value[ 'end_' . $i ] ) && ! empty( $posted_value[ 'start_' . $i ] ) && ! empty( $posted_value[ 'end_' . $i ] ) ) {

					$posted_value[ 'start_' . $i ] = sanitize_text_field( $posted_value[ 'start_' . $i ] );
					$posted_value[ 'end_' . $i ]   = sanitize_text_field( $posted_value[ 'end_' . $i ] );

					if ( filter_var( $posted_value[ 'start_' . $i ], FILTER_VALIDATE_IP ) && filter_var( $posted_value[ 'end_' . $i ], FILTER_VALIDATE_IP ) && ( ip2long( $posted_value[ 'end_' . $i ] ) > ip2long( $posted_value[ 'start_' . $i ] ) ) ) {
						$range  = '';
						$range  = sanitize_text_field( $posted_value[ 'start_' . $i ] );
						$range .= '-';
						$range .= sanitize_text_field( $posted_value[ 'end_' . $i ] );
						$added_mappings_ranges++;
						update_site_option( 'mo_wpns_iprange_range_' . $added_mappings_ranges, $range );

					} else {
						$flag = 1;
						$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::INVALID_IP ), 'ERROR' );
						return;
					}
				}
			}

			if ( 0 === $added_mappings_ranges ) {
				update_site_option( 'mo_wpns_iprange_range_1', '' );
			}
			update_site_option( 'mo_wpns_iprange_count', $added_mappings_ranges );
			if ( 0 === $flag ) {
				$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::IP_BLOCK_RANGE_ADDED ), 'SUCCESS' );
			}
		}

		/**
		 * Build sanitized placeholder data for IP lookup template replacement.
		 *
		 * @param array  $lookup_result Lookup result from API.
		 * @param string $hostname      Resolved hostname.
		 * @param string $timeoffset    Time offset hours.
		 * @return array
		 */
		private function mo2f_build_ip_lookup_sanitized_data( $lookup_result, $hostname, $timeoffset ) {
			$location_parts = explode( ',', $lookup_result['loc'] ?? '' );
			$latitude       = isset( $location_parts[0] ) ? $location_parts[0] : '';
			$longitude      = isset( $location_parts[1] ) ? $location_parts[1] : '';
			return array(
				'{{status}}'    => esc_html( 'Success' ),
				'{{ip}}'        => esc_html( isset( $lookup_result['ip'] ) ? $lookup_result['ip'] : '' ),
				'{{region}}'    => esc_html( isset( $lookup_result['region'] ) ? $lookup_result['region'] : '' ),
				'{{country}}'   => esc_html( isset( $lookup_result['country'] ) ? $lookup_result['country'] : '' ),
				'{{city}}'      => esc_html( isset( $lookup_result['city'] ) ? $lookup_result['city'] : '' ),
				'{{latitude}}'  => esc_html( $latitude ),
				'{{longitude}}' => esc_html( $longitude ),
				'{{timezone}}'  => esc_html( isset( $lookup_result['timezone'] ) ? $lookup_result['timezone'] : '' ),
				'{{hostname}}'  => esc_html( isset( $lookup_result['hostname'] ) ? $lookup_result['hostname'] : ( isset( $hostname ) ? $hostname : '' ) ),
				'{{offset}}'    => esc_html( isset( $timeoffset ) ? $timeoffset : '' ),
			);
		}

		/**
		 * Allowed HTML tags and attributes for rendering the IP lookup template.
		 *
		 * @return array
		 */
		private function mo2f_get_ip_lookup_allowed_html() {
			return array(
				'span'  => array( 'class' => array() ),
				'table' => array( 'class' => array() ),
				'tr'    => array(),
				'td'    => array( 'class' => array() ),
				'hr'    => array(),
			);
		}

	}
	new Mo2f_IP_Blocking_Handler();
}
