<?php
/**
 * This file contains functions related to login flow.
 *
 * @package miniorange-2-factor-authentication/database.
 */

namespace TwoFA\Database;

use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Traits\Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 *   Including  upgrade.php.
 */
require_once ABSPATH . 'wp-admin/includes/upgrade.php';

if ( ! class_exists( 'MoWpnsDB' ) ) {
	/**
	 * Class used to perform DB operation on security functions.
	 */
	class MoWpnsDB {

		use Instance;

		/**
		 * Transaction table name.
		 *
		 * @var string
		 */
		private $filescan;

		/**
		 * Constructor for class MoWpnsDB.
		 */
		public function __construct() {
			global $wpdb;
		}

		/**
		 * This function should run on activation of plugin.
		 *
		 * @return void
		 */
		public function mo_plugin_activate() {
			if ( ! get_site_option( 'mo_wpns_dbversion' ) || get_site_option( 'mo_wpns_dbversion' ) < MoWpnsConstants::DB_VERSION ) {
				update_site_option( 'mo_wpns_dbversion', MoWpnsConstants::DB_VERSION );
			} else {
				$current_db_version = get_site_option( 'mo_wpns_dbversion' );
				if ( $current_db_version < MoWpnsConstants::DB_VERSION ) {
					update_site_option( 'mo_wpns_dbversion', MoWpnsConstants::DB_VERSION );
				}
			}
		}

		/**
		 * Returns blocked IP count
		 *
		 * @param string $ip_address ip address.
		 * @return string
		 */
		public function mo2f_get_ip_blocked_count( $ip_address ) {
			$blocked_ips = get_site_option( 'mo2f_blocked_ips_data', array() );
			$count       = 0;
			foreach ( $blocked_ips as $blocked_ip ) {
				if ( $blocked_ip['ip_address'] === $ip_address ) {
					++$count;
				}
			}
			return $count;
		}


		/**
		 * Returns total manual blocked ips.
		 *
		 * @return object
		 */
		public function mo2f_get_total_manual_blocked_ips() {
			$blocked_ips = get_site_option( 'mo2f_blocked_ips_data', array() );
			return is_array( $blocked_ips ) ? count(
				array_filter(
					$blocked_ips,
					function ( $ip ) {
						return isset( $ip['reason'] ) && 'Blocked by Admin' === $ip['reason'];
					}
				)
			) : 0;
		}

		/**
		 * Clears login report.
		 *
		 * @return void
		 */
		public function mo_wpns_clear_login_report() {
			$option_key            = 'mo2f_network_transactions_data';
			$transactions          = get_site_option( $option_key, array() );
			$filtered_transactions = array_filter(
				$transactions,
				function ( $transaction ) {
					return ! in_array( $transaction['status'], array( 'success', 'pastfailed', 'failed' ) );
				}
			);
			update_site_option( $option_key, $filtered_transactions );
		}



		/**
		 * Returns if IP blocked
		 *
		 * @param string $entryid ip address.
		 * @return object
		 */
		public function mo2f_get_blocked_ip( $entryid ) {
			$blocked_ips = get_site_option( 'mo2f_blocked_ips_data', array() );
			foreach ( $blocked_ips as $ip_entry ) {
				if ( isset( $ip_entry['id'] ) && $ip_entry['id'] === $entryid ) {
					return $ip_entry['ip_address'];
				}
			}
			return null;
		}

		/**
		 * Get blocked ip list
		 *
		 * @return object
		 */
		public function mo2f_get_blocked_ip_list() {
			$blocked_ips = get_site_option( 'mo2f_blocked_ips_data', array() );
			$ip_list     = array();
			if ( is_array( $blocked_ips ) ) {
				foreach ( $blocked_ips as $ip_entry ) {
					$ip_object                    = new \stdClass();
					$ip_object->id                = $ip_entry['id'] ?? null;
					$ip_object->reason            = $ip_entry['reason'] ?? null;
					$ip_object->ip_address        = $ip_entry['ip_address'] ?? null;
					$ip_object->created_timestamp = $ip_entry['created_timestamp'] ?? null;
					$ip_list[]                    = $ip_object;
				}
			}
			return $ip_list;
		}

		/**
		 * Insert blocked IP
		 *
		 * @param string $ip_address ip.
		 * @param string $reason reason.
		 * @param string $blocked_for_time blocked duration.
		 * @return void
		 */
		public function mo2f_insert_blocked_ip( $ip_address, $reason, $blocked_for_time ) {
			$blocked_ips   = get_site_option( 'mo2f_blocked_ips_data', array() );
			$new_entry     = array(
				'id'                => count( $blocked_ips ) + 1,
				'ip_address'        => $ip_address,
				'reason'            => $reason,
				'blocked_for_time'  => $blocked_for_time,
				'created_timestamp' => current_time( 'timestamp' ),
			);
			$blocked_ips[] = $new_entry;
			update_site_option( 'mo2f_blocked_ips_data', $blocked_ips );
		}

		/**
		 * Delete blocked ips
		 *
		 * @param string $entryid ip address.
		 * @return void
		 */
		public function mo2f_delete_blocked_ip( $entryid ) {
			$blocked_ips = get_site_option( 'mo2f_blocked_ips_data', array() );
			$entryid     = (int) $entryid;
			foreach ( $blocked_ips as $key => $ip_entry ) {
				if ( isset( $ip_entry['id'] ) && $ip_entry['id'] === $entryid ) {
					unset( $blocked_ips[ $key ] );
					$updated = update_site_option( 'mo2f_blocked_ips_data', array_values( $blocked_ips ) );
					return;
				}
			}
		}

		/**
		 * Whiteliisted ip count.
		 *
		 * @param string $ip_address ip address.
		 * @return object
		 */
		public function mo2f_get_whitelisted_ip_count( $ip_address ) {
			$whitelist = get_site_option( 'mo2f_whitelisted_ips', array() );
			$count     = 0;
			foreach ( $whitelist as $entry ) {
				if ( isset( $entry['ip_address'] ) && $entry['ip_address'] === $ip_address ) {
					++$count;
				}
			}
			return $count;
		}

		/**
		 * Insert whitelisted ip.
		 *
		 * @param string $ip_address ip address.
		 * @return void
		 */
		public function mo2f_insert_whitelisted_ip( $ip_address ) {
			$whitelist   = get_site_option( 'mo2f_whitelisted_ips', array() );
			$new_entry   = array(
				'ip_address'        => $ip_address,
				'created_timestamp' => current_time( 'timestamp' ),
			);
			$whitelist[] = $new_entry;
			update_site_option( 'mo2f_whitelisted_ips', $whitelist );
		}

		/**
		 * Delete whitelisted ip
		 *
		 * @param string $entryid ip address.
		 * @return void
		 */
		public function mo2f_delete_whitelisted_ip( $entryid ) {
			$whitelist = get_site_option( 'mo2f_whitelisted_ips', array() );
			if ( isset( $whitelist[ $entryid ] ) ) {
				unset( $whitelist[ $entryid ] );
				$whitelist = array_values( $whitelist );
				update_site_option( 'mo2f_whitelisted_ips', $whitelist );
			}
		}

		/**
		 * Get whitelisted IP list
		 *
		 * @return string
		 */
		public function mo2f_get_whitelisted_ips_list() {
			$whitelist        = get_site_option( 'mo2f_whitelisted_ips', array() );
			$object_whitelist = array();
			foreach ( $whitelist as $index => $entry ) {
				$entry['id']        = $index;
				$object_whitelist[] = (object) $entry;
			}
			return $object_whitelist;
		}

		/**
		 * Insrt transaction.
		 *
		 * @param string $ip_address ip address.
		 * @param string $username username.
		 * @param string $type type.
		 * @param string $status status.
		 * @param string $url url.
		 * @return void
		 */
		public function mo2f_insert_transaction_audit( $ip_address, $username, $type, $status, $url = null ) {
			$option_key       = 'mo2f_network_transactions_data';
			$transaction_data = array(
				'ip_address'        => sanitize_text_field( $ip_address ),
				'username'          => sanitize_text_field( $username ),
				'type'              => sanitize_text_field( $type ),
				'status'            => sanitize_text_field( $status ),
				'url'               => is_null( $url ) ? '' : esc_url_raw( $url ),
				'created_timestamp' => strtotime( current_time( 'mysql' ) ),
			);
			$existing_data    = get_site_option( $option_key, array() );
			$existing_data[]  = $transaction_data;
			update_site_option( $option_key, $existing_data );
		}

		/**
		 * Get transaction list.
		 *
		 * @return object.
		 */
		public function mo2f_get_transaction_list() {
			$option_key   = 'mo2f_network_transactions_data';
			$transactions = get_site_option( $option_key, array() );
			usort(
				$transactions,
				function ( $a, $b ) {
					return $b['created_timestamp'] - $a['created_timestamp'];
				}
			);
			return $transactions;
		}

		/**
		 * Get login transaction limit.
		 *
		 * @return object
		 */
		public function mo2f_get_login_transaction_report() {
			$option_key         = 'mo2f_network_transactions_data';
			$transactions       = get_site_option( $option_key, array() );
			$login_transactions = array_filter(
				$transactions,
				function ( $transaction ) {
					return isset( $transaction['type'] ) && $transaction['type'] === 'User Login';
				}
			);
			usort(
				$login_transactions,
				function ( $a, $b ) {
					return $b['created_timestamp'] - $a['created_timestamp'];
				}
			);
			$login_transactions = json_decode( wp_json_encode( $login_transactions ) );
			return $login_transactions;
		}

		/**
		 * Update transaction report
		 *
		 * @param mixed $where where.
		 * @param mixed $update update.
		 * @return void
		 */
		public function mo2f_update_transaction_table( $where, $update ) {
			$option_key   = 'mo2f_network_transactions_data';
			$transactions = get_site_option( $option_key, array() );
			foreach ( $transactions as &$transaction ) {
				$match = true;
				foreach ( $where as $where_key => $where_value ) {
					if ( ! isset( $transaction [ $where_key ] ) || $transaction [ $where_key ] !== $where_value ) {
						$match = false;
						break;
					}
				}
				if ( $match ) {
					$transaction = array_merge( $transaction, $update );
				}
			}
			update_site_option( $option_key, $transactions );
		}

		/**
		 * Get count of attack blocked.
		 *
		 * @return string
		 */
		public function mo2f_get_count_of_attacks_blocked() {
			$option_key        = 'mo2f_network_transactions_data';
			$transactions      = get_site_option( $option_key, array() );
			$statuses_to_count = array( MoWpnsConstants::FAILED, MoWpnsConstants::PAST_FAILED );
			$count             = 0;
			foreach ( $transactions as $transaction ) {
				if ( in_array( $transaction['status'], $statuses_to_count, true ) ) {
					++$count;
				}
			}
			return $count;
		}

		/**
		 * Undocumented function
		 *
		 * @param string $ip_address ip address.
		 * @return string
		 */
		public function mo2f_get_failed_transaction_count( $ip_address ) {
			$option_key   = 'mo2f_network_transactions_data';
			$transactions = get_site_option( $option_key, array() );
			$count        = 0;
			foreach ( $transactions as $transaction ) {
				if ( isset( $transaction['ip_address'], $transaction['status'] ) &&
					$transaction['ip_address'] === $ip_address &&
					MoWpnsConstants::FAILED === $transaction['status'] ) {
					++$count;
				}
			}
			return $count;
		}

		/**
		 * Delete transactions
		 *
		 * @param string $ip_address ip address.
		 * @return void
		 */
		public function mo2f_delete_transaction( $ip_address ) {
			$option_key            = 'mo2f_network_transactions_data';
			$transactions          = get_site_option( $option_key, array() );
			$filtered_transactions = array_filter(
				$transactions,
				function ( $transaction ) use ( $ip_address ) {
					return ! (
						isset( $transaction['ip_address'], $transaction['status'] ) &&
						$transaction['ip_address'] === $ip_address &&
						MoWpnsConstants::FAILED === $transaction['status']
					);
				}
			);
			update_site_option( $option_key, $filtered_transactions );
		}

		/**
		 * Retrieve data from a specified table.
		 *
		 * @param string $table_name Table name (without prefix).
		 * @return array|null Retrieved data or null if empty.
		 */
		public function mo2f_get_old_table_data( $table_name ) {
			global $wpdb;
			$full_table_name = $wpdb->base_prefix . $table_name;
			$table_exists    = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $full_table_name ) );
			if ( $table_exists !== $full_table_name ) {
				return null;
			}
			$query    = $wpdb->prepare( 'SELECT * FROM %1s', $full_table_name );
			$old_data = $wpdb->get_results( $query, ARRAY_A );
			return ! empty( $old_data ) ? $old_data : null;
		}
	}
}
