<?php
/**
 * This file contains the ajax request handler.
 *
 * @package miniorange-2-factor-authentication/twofactor/loginsettings/handler
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use TwoFA\Helper\MoWpnsHandler;
use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Helper\MoWpnsMessages;

if ( ! class_exists( 'Mo2f_IP_Blocking_Handler' ) ) {

	/**
	 * Class Mo2f_IP_Blocking_Handler
	 */
	class Mo2f_IP_Blocking_Handler {

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

			if ( ! check_ajax_referer( 'mo2f-ip-black-list-ajax-nonce', 'nonce', false ) ) {
				wp_send_json_error( 'class-wpns-ajax' );
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
				case 'wpns_ip_lookup':
					$this->wpns_ip_lookup();
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
				$is_whitelisted = $mo_wpns_config->is_whitelisted( $ip_address );
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
						$mo_wpns_handler = new MoWpnsHandler();
						$blockedips      = $mo_wpns_handler->get_blocked_ips();
						$whitelisted_ips = $mo_wpns_handler->get_whitelisted_ips();
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
				if ( $mo_wpns_config->is_whitelisted( $ip_address ) ) {
					echo( 'IP_ALREADY_WHITELISTED' );
					exit;
				} else {
					$mo_wpns_config->whitelist_ip( $ip );
					$mo_wpns_handler = new MoWpnsHandler();
					$whitelisted_ips = $mo_wpns_handler->get_whitelisted_ips();

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
		 * @return void
		 */
		public function wpns_ip_lookup() {

			if ( ! check_ajax_referer( 'LoginSecurityNonce', 'nonce', false ) ) {
				wp_send_json_error( 'class-wpns-ajax' );

			} else {
				$ip = isset( $_POST['IP'] ) ? sanitize_text_field( wp_unslash( $_POST['IP'] ) ) : '';
				if ( ! preg_match( '/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\z/', $ip ) ) {
					wp_send_json_error( 'INVALID_IP_FORMAT' );

				} elseif ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					wp_send_json_error( 'INVALID_IP' );

				}
				$result = wp_remote_get( 'http://www.geoplugin.net/json.gp?ip=' . $ip );

				if ( ! is_wp_error( $result ) ) {
					$result = json_decode( wp_remote_retrieve_body( $result ), true );
				}

				try {
					$timeoffset = timezone_offset_get( new DateTimeZone( $result['geoplugin_timezone'] ), new DateTime( 'now' ) );
					$timeoffset = $timeoffset / 3600;

				} catch ( Exception $e ) {
					$result['geoplugin_timezone'] = '';
					$timeoffset                   = '';
				}
				$ip_look_up_template = MoWpnsConstants::IP_LOOKUP_TEMPLATE;
				if ( $result['geoplugin_request'] === $ip ) {
					$ip_parameters = array(
						'status'           => 'geoplugin_status',
						'ip'               => 'geoplugin_request',
						'region'           => 'geoplugin_region',
						'country'          => 'geoplugin_countryName',
						'city'             => 'geoplugin_city',
						'continent'        => 'geoplugin_continentName',
						'latitude'         => 'geoplugin_latitude',
						'longitude'        => 'geoplugin_longitude',
						'timezone'         => 'geoplugin_timezone',
						'curreny_code'     => 'geoplugin_currencyCode',
						'curreny_symbol'   => 'geoplugin_currencySymbol',
						'per_dollar_value' => 'geoplugin_currencyConverter',
						'offset'           => $timeoffset,
					);

					foreach ( $ip_parameters as $parameter => $value ) {
						$ip_look_up_template = str_replace( '{{' . $parameter . '}}', $result[ $value ], $ip_look_up_template );
					}
					$result['ipDetails'] = $ip_look_up_template;
				} else {
					$result['ipDetails']['status'] = 'ERROR';
				}
				wp_send_json( $result );
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
				$entryid        = sanitize_text_field( $entry_id );
				$mo_wpns_config = new MoWpnsHandler();
				$mo_wpns_config->unblock_ip_entry( $entryid );
				?>
				<table id="blockedips_table1" class="display">
				<thead><tr><th>IP Address&emsp;&emsp;</th><th>Reason&emsp;&emsp;</th><th>Blocked Until&emsp;&emsp;</th><th>Blocked Date&emsp;&emsp;</th><th>Action&emsp;&emsp;</th></tr></thead>
				<tbody>
				<?php
				$mo_wpns_handler = new MoWpnsHandler();
				$blockedips      = $mo_wpns_handler->get_blocked_ips();
				$whitelisted_ips = $mo_wpns_handler->get_whitelisted_ips();
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
				$entryid        = isset( $entry_id ) ? sanitize_text_field( $entry_id ) : '';
				$mo_wpns_config = new MoWpnsHandler();
				$mo_wpns_config->remove_whitelist_entry( $entryid );
				$mo_wpns_handler = new MoWpnsHandler();
				$whitelisted_ips = $mo_wpns_handler->get_whitelisted_ips();

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
						update_option( 'mo_wpns_iprange_range_' . $added_mappings_ranges, $range );

					} else {
						$flag = 1;
						$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::INVALID_IP ), 'ERROR' );
						return;
					}
				}
			}

			if ( 0 === $added_mappings_ranges ) {
				update_option( 'mo_wpns_iprange_range_1', '' );
			}
			update_option( 'mo_wpns_iprange_count', $added_mappings_ranges );
			if ( 0 === $flag ) {
				$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::IP_BLOCK_RANGE_ADDED ), 'SUCCESS' );
			}
		}

	}
	new Mo2f_IP_Blocking_Handler();
}
