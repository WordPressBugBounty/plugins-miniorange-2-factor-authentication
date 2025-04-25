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
		private $transaction_table;

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
			$this->transaction_table   = $wpdb->base_prefix . 'mo2f_network_transactions';
		}

		/**
		 * This function should run on activation of plugin.
		 *
		 * @return void
		 */
		public function mo_plugin_activate() {
			if ( ! get_site_option( 'mo_wpns_dbversion' ) || get_site_option( 'mo_wpns_dbversion' ) < MoWpnsConstants::DB_VERSION ) {
				update_site_option( 'mo_wpns_dbversion', MoWpnsConstants::DB_VERSION );
				$this->mo2f_generate_tables();
			} else {
				$current_db_version = get_site_option( 'mo_wpns_dbversion' );
				if ( $current_db_version < MoWpnsConstants::DB_VERSION ) {
					update_site_option( 'mo_wpns_dbversion', MoWpnsConstants::DB_VERSION );
				}
			}
		}

		/**
		 * This function generates tables.
		 *
		 * @return void
		 */
		public function mo2f_generate_tables() {
			global $wpdb;

			$table_name = $this->transaction_table;
			if ( $wpdb->get_var( $wpdb->prepare( 'show tables like %s', array( $table_name ) ) ) !== $table_name ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery -- Ignoring warning related to Schema change
				$sql = 'CREATE TABLE ' . $table_name . ' ( `id` bigint NOT NULL AUTO_INCREMENT, `ip_address` mediumtext NOT NULL ,  `username` mediumtext NOT NULL , `type` mediumtext NOT NULL , `url` mediumtext NOT NULL , `status` mediumtext NOT NULL , `created_timestamp` int, UNIQUE KEY id (id) );'; // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange -- Ignoring warning related to Schema change
				dbDelta( $sql );
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
			global $wpdb;
			$wpdb->query( 'DELETE FROM ' . $wpdb->base_prefix . "mo2f_network_transactions WHERE Status='success' or Status= 'pastfailed' or Status='failed' " ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, Direct database call without caching detected -- DB Direct Query is necessary here.
		    delete_site_option('mo2f_network_transactions_data'); // This is to delete the data from options table.
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
			global $wpdb;
			$data        = array(
				'ip_address'        => $ip_address,
				'username'          => $username,
				'type'              => $type,
				'status'            => $status,
				'created_timestamp' => current_time( 'timestamp' ), // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested -- Ignoring warning related to timestamp use
			);
			$format      = array( '%s', '%s', '%s', '%s', '%d' );
			$data['url'] = is_null( $url ) ? '' : $url;
			$url         = esc_url_raw( $url );
			$wpdb->insert( $this->transaction_table, $data, $format ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery , WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- Ignoring the warnings related to DB caching, Dirct DB access, and complex placeholder
		}

		/**
		 * Get transaction list.
		 *
		 * @return object.
		 */
		public function mo2f_get_transaction_list() {
			global $wpdb;
			return $wpdb->get_results( $wpdb->prepare( 'SELECT ip_address, username, type, status, created_timestamp FROM %1s order by id desc limit 5000', array( $this->transaction_table ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery , WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- Ignoring the warnings related to DB caching, Dirct DB access, and complex placeholder
		}

		/**
		 * Get login transaction limit.
		 *
		 * @return object
		 */
		public function mo2f_get_login_transaction_report() {
			global $wpdb;
			return $wpdb->get_results( $wpdb->prepare( "SELECT ip_address, username, status, created_timestamp FROM %1s WHERE type='User Login' order by id desc limit 5000", array( $this->transaction_table ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery , WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- Ignoring the warnings related to DB caching, Dirct DB access, and complex placeholder
		}

		
		/**
		 * Get login old transaction.
		 *
		 * @return object
		 */
		public function mo2f_get_old_login_transaction_report() {
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
			global $wpdb;

			$sql = 'UPDATE ' . $this->transaction_table . ' SET ';
			$i   = 0;
			foreach ( $update as $key => $value ) {
				if ( 0 !== $i % 2 ) {
					$sql .= ' , ';
				}
				if ( 'created_timestamp' === $key || 'id' === $key ) {
					$sql .= $wpdb->prepare( '%1s = %d', array( $key, $value ) ); // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- Ignoring complex placeholder warning as it is used for table name
				} else {
					$sql .= $wpdb->prepare( '%1s = %s', array( $key, $value ) ); // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- Ignoring complex placeholder warning as it is used for table name
				}
				$i++;
			}
			$sql .= ' WHERE ';
			$i    = 0;
			foreach ( $where as $key => $value ) {
				if ( 0 !== $i % 2 ) {
					$sql .= ' AND ';
				}
				if ( 'created_timestamp' === $key || 'id' === $key ) {
					$sql .= $wpdb->prepare( ' %1s = %d ', array( $key, $value ) ); // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- Ignoring complex placeholder warning as it is used for table name
				} else {
					$sql .= $wpdb->prepare( ' %1s = %s ', array( $key, $value ) );  // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- Ignoring complex placeholder warning as it is used for table name
				}
				$i++;
			}

			$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery , WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Ignoring warnings as prepare() is used in above statement
		}

		/**
		 * Get count of attack blocked.
		 *
		 * @return string
		 */
		public function mo2f_get_count_of_attacks_blocked() {
			global $wpdb;
			return $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %1s WHERE status = %s OR status = %s', array( $this->transaction_table, MoWpnsConstants::FAILED, MoWpnsConstants::PAST_FAILED ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery , WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- Ignoring the warnings related to DB caching, Dirct DB access, and complex placeholder
		}

		/**
		 * Undocumented function
		 *
		 * @param string $ip_address ip address.
		 * @return string
		 */
		public function mo2f_get_failed_transaction_count( $ip_address ) {
			global $wpdb;
			return $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %1s WHERE ip_address = %s AND status = %s', array( $this->transaction_table, $ip_address, MoWpnsConstants::FAILED ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery , WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- Ignoring the warnings related to DB caching, Dirct DB access, and complex placeholder
		}

		/**
		 * Delete transactions
		 *
		 * @param string $ip_address ip address.
		 * @return void
		 */
		public function mo2f_delete_transaction( $ip_address ) {
			global $wpdb;
			$wpdb->query( $wpdb->prepare( 'DELETE FROM %1s WHERE ip_address = %s AND status= %s ', array( $this->transaction_table, $ip_address, MoWpnsConstants::FAILED ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery , WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- Ignoring the warnings related to DB caching, Dirct DB access, and complex placeholder
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
			$table_exists    = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $full_table_name ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- DB Direct Query is necessary here.
			if ( $table_exists !== $full_table_name ) {
				return null;
			}
			$query    = $wpdb->prepare( 'SELECT * FROM %1s', $full_table_name );
			$old_data = $wpdb->get_results( $query, ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- DB Direct Query is necessary here.
			return ! empty( $old_data ) ? $old_data : null;
		}
	}
}
