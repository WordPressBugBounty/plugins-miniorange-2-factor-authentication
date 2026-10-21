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
			$this->transaction_table = $wpdb->base_prefix . 'mo2f_network_transactions';
			if ( is_multisite() ) {
				wp_cache_add_global_groups( array( 'mo2f_transactions' ) );
			}
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
				$sql = 'CREATE TABLE ' . $table_name . ' ( `id` bigint NOT NULL AUTO_INCREMENT, `ip_address` mediumtext NOT NULL ,  `username` mediumtext NOT NULL , `type` mediumtext NOT NULL , `url` mediumtext NOT NULL , `status` mediumtext NOT NULL , `created_timestamp` int, `actor_id` bigint UNSIGNED DEFAULT NULL, `method` varchar(50) DEFAULT NULL, `meta` text DEFAULT NULL, UNIQUE KEY id (id) );'; // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange -- Ignoring warning related to Schema change
				dbDelta( $sql );
			}

			// Existing installs already had the table above; add the newer audit columns to them here.
			$mo2f_new_columns = array(
				'actor_id' => 'bigint UNSIGNED DEFAULT NULL',
				'method'   => 'varchar(50) DEFAULT NULL',
				'meta'     => 'text DEFAULT NULL',
			);
			foreach ( $mo2f_new_columns as $mo2f_column => $mo2f_definition ) {
				if ( ! $this->mo2f_transaction_column_exists( $mo2f_column ) ) {
					$this->mo2f_add_transaction_column( $mo2f_column, $mo2f_definition );
				}
			}
		}

		/**
		 * Checks whether a column already exists on the transactions table.
		 *
		 * @param string $column_name Column to check.
		 * @return bool
		 */
		private function mo2f_transaction_column_exists( $column_name ) {
			global $wpdb;
			$table_name = esc_sql( $this->transaction_table );
			$found      = $wpdb->get_var( $wpdb->prepare( 'SHOW COLUMNS FROM ' . $table_name . ' LIKE %s', $column_name ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- Table name sanitized above; column name is prepared.
			return ! empty( $found );
		}

		/**
		 * Adds a single nullable column to the transactions table.
		 *
		 * @param string $column_name Column to add.
		 * @param string $definition  SQL type/definition for the column.
		 * @return void
		 */
		private function mo2f_add_transaction_column( $column_name, $definition ) {
			global $wpdb;
			$table_name = esc_sql( $this->transaction_table );
			$column     = esc_sql( $column_name );
			$wpdb->query( "ALTER TABLE {$table_name} ADD COLUMN `{$column}` {$definition}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- DDL cannot use placeholders; identifiers are escaped above and $definition is always one of the hardcoded literals in mo2f_generate_tables(), never user input.
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
			delete_site_option( 'mo2f_network_transactions_data' ); // This is to delete the data from options table.
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
				'created_timestamp' => wp_date( 'U' ),
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
				'created_timestamp' => wp_date( 'U' ),
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
		 * @param string   $url url.
		 * @param int|null $actor_id WP user ID who performed the action, when it differs from the subject (e.g. an admin resetting another user). Null for self-initiated events.
		 * @param string|null $method 2FA method involved, when relevant.
		 * @param string|null $meta Optional JSON-encoded extra detail.
		 * @return void
		 */
		public function mo2f_insert_transaction_audit( $ip_address, $username, $type, $status, $url = null, $actor_id = null, $method = null, $meta = null ) {
			global $wpdb;
			$data        = array(
				'ip_address'        => $ip_address,
				'username'          => $username,
				'type'              => $type,
				'status'            => $status,
				'created_timestamp' => wp_date( 'U' ),
				'actor_id'          => $actor_id,
				'method'            => $method,
				'meta'              => $meta,
			);
			$format      = array( '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s' );
			$data['url'] = is_null( $url ) ? '' : esc_url_raw( $url );
			$wpdb->insert( $this->transaction_table, $data, $format ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Insert uses $wpdb with prepared formats and cache is flushed below.
			wp_cache_delete( 'mo2f_transaction_list', 'mo2f_transactions' );
			wp_cache_delete( 'mo2f_login_transaction_report', 'mo2f_transactions' );
		}

		/**
		 * Get login transaction limit.
		 *
		 * @return object
		 */
		public function mo2f_get_login_transaction_report() {
			global $wpdb;

			$cache_key = 'mo2f_login_transaction_report';

			$cache_group = 'mo2f_transactions';

			$transactions = wp_cache_get( $cache_key, $cache_group );
			if ( false !== $transactions ) {
				return $transactions;
			}

			$table_name   = esc_sql( $this->transaction_table );
			$transactions = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Query requires direct database access and result is cached above.
				$wpdb->prepare(
					'SELECT ip_address, username, status, created_timestamp FROM ' . $table_name . ' WHERE type = %s ORDER BY id DESC LIMIT %d', // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- Table name sanitized above.
					'User Login',
					5000
				)
			);

			if ( null === $transactions ) {
				$transactions = array();
			}

			wp_cache_set( $cache_key, $transactions, $cache_group );

			return $transactions;
		}

		/**
		 * Server-side page of the login transaction report, for DataTables'
		 * `serverSide` mode -- returns one page of rows plus the total and
		 * filtered counts, instead of mo2f_get_login_transaction_report()'s
		 * every-row-at-once (capped at 5000, still rendered into the DOM in
		 * full so the browser can paginate client-side).
		 *
		 * @param int    $start        Row offset.
		 * @param int    $length       Page size.
		 * @param string $search       Search term (matched against ip_address, username, status).
		 * @param int    $order_column DataTables column index (0=ip_address, 1=username, 2=status, 3=created_timestamp).
		 * @param string $order_dir    'asc' or 'desc'.
		 * @return array{total:int, filtered:int, rows:array}
		 */
		public function mo2f_get_login_transaction_report_page( $start, $length, $search = '', $order_column = 3, $order_dir = 'desc' ) {
			global $wpdb;

			$table_name = esc_sql( $this->transaction_table );
			$order_map  = array(
				0 => 'ip_address',
				1 => 'username',
				2 => 'status',
				3 => 'created_timestamp',
			);
			$order_by   = isset( $order_map[ $order_column ] ) ? $order_map[ $order_column ] : 'created_timestamp';
			$order_dir  = 'asc' === strtolower( $order_dir ) ? 'ASC' : 'DESC';
			$start      = max( 0, (int) $start );
			$length     = max( 1, min( 100, (int) $length ) );
			$where      = 'WHERE type = %s';
			$query_args = array( 'User Login' );
			$search     = trim( (string) $search );

			if ( '' !== $search ) {
				$search_like  = '%' . $wpdb->esc_like( $search ) . '%';
				$where       .= ' AND (ip_address LIKE %s OR username LIKE %s OR status LIKE %s)';
				$query_args[] = $search_like;
				$query_args[] = $search_like;
				$query_args[] = $search_like;
			}

			$total = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Server-side report count.
				$wpdb->prepare(
					'SELECT COUNT(*) FROM ' . $table_name . ' WHERE type = %s', // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- Table name is escaped above.
					'User Login'
				)
			);

			$filtered = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Server-side report count; $where is assembled above from developer-controlled fragments only.
				$wpdb->prepare(
					'SELECT COUNT(*) FROM ' . $table_name . ' ' . $where, // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Table name and where clause are controlled above; $where's placeholder count always matches $query_args (its own %s aren't visible to the static scanner here).
					$query_args
				)
			);

			$query_args[] = $length;
			$query_args[] = $start;

			$transactions = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Server-side report page; $where is assembled above from developer-controlled fragments only.
				$wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- $query_args' element count always matches $where's placeholder count.
					'SELECT ip_address, username, status, created_timestamp FROM ' . $table_name . ' ' . $where . ' ORDER BY ' . $order_by . ' ' . $order_dir . ' LIMIT %d OFFSET %d', // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- Dynamic pieces are allow-listed above.
					$query_args
				)
			);

			return array(
				'total'    => $total,
				'filtered' => $filtered,
				'rows'     => is_array( $transactions ) ? $transactions : array(),
			);
		}

		/**
		 * Get a user's own recent login attempts.
		 *
		 * @param string $username The account's username.
		 * @param int    $limit    Max rows to return.
		 * @return array
		 */
		public function mo2f_get_user_login_history( $username, $limit = 10 ) {
			global $wpdb;

			$cache_key   = 'mo2f_user_login_history_' . md5( $username ) . '_' . (int) $limit;
			$cache_group = 'mo2f_transactions';

			$transactions = wp_cache_get( $cache_key, $cache_group );
			if ( false !== $transactions ) {
				return $transactions;
			}

			$table_name   = esc_sql( $this->transaction_table );
			$transactions = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Query requires direct database access and result is cached above.
				$wpdb->prepare(
					'SELECT ip_address, status, created_timestamp FROM ' . $table_name . ' WHERE type = %s AND username = %s ORDER BY id DESC LIMIT %d', // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- Table name sanitized above.
					MoWpnsConstants::LOGIN_TRANSACTION,
					$username,
					$limit
				)
			);

			if ( null === $transactions ) {
				$transactions = array();
			}

			wp_cache_set( $cache_key, $transactions, $cache_group, 60 );

			return $transactions;
		}

		/**
		 * Get compliance log report.
		 *
		 * @param int $days How many days back to include.
		 * @return array
		 */
		public function mo2f_get_compliance_log_report( $days = 30 ) {
			global $wpdb;

			$compliance_types = array(
				MoWpnsConstants::ADMIN_2FA_RESET,
				MoWpnsConstants::SELF_SERVICE_2FA_RESET,
			);

			$table_name   = esc_sql( $this->transaction_table );
			$placeholders = implode( ', ', array_fill( 0, count( $compliance_types ), '%s' ) );
			$since        = time() - ( DAY_IN_SECONDS * absint( $days ) );
			$query_args   = array_merge( $compliance_types, array( $since, 2000 ) );

			$results = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct query required; not cached because it is date-range filtered.
				$wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- $placeholders' %s count always matches count( $compliance_types ), so total placeholders match $query_args.
					'SELECT id, ip_address, username, type, status, created_timestamp, actor_id, method FROM ' . $table_name . ' WHERE type IN (' . $placeholders . ') AND created_timestamp >= %d ORDER BY id DESC LIMIT %d', // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- Table name sanitized above.
					$query_args
				)
			);

			return $results ? $results : array();
		}

		/**
		 * @param int $since Unix timestamp; count events at or after this time.
		 * @return array {
		 *     @type int $new_logins          Successful logins.
		 *     @type int $failed_attempts     Failed login attempts.
		 *     @type int $admin_resets        Admin-initiated 2FA resets.
		 *     @type int $self_service_resets Self-service 2FA resets.
		 * }
		 */
		public function mo2f_get_digest_summary( $since ) {
			global $wpdb;
			$table_name = esc_sql( $this->transaction_table );
			$results    = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct query required; not cached because it is date-range filtered.
				$wpdb->prepare(
					'SELECT type, status, COUNT(*) as event_count FROM ' . $table_name . ' WHERE created_timestamp >= %d GROUP BY type, status', // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- Table name is built from $wpdb->base_prefix plus a hardcoded suffix, never user input; esc_sql() is applied for consistency with the identical pattern used elsewhere in this file and to satisfy automated SQL-escaping scanners.
					$since
				)
			);
			$summary = array(
				'new_logins'          => 0,
				'failed_attempts'     => 0,
				'admin_resets'        => 0,
				'self_service_resets' => 0,
			);
			foreach ( (array) $results as $row ) {
				$count = (int) $row->event_count;
				if ( MoWpnsConstants::LOGIN_TRANSACTION === $row->type && MoWpnsConstants::SUCCESS === $row->status ) {
					$summary['new_logins'] += $count;
				} elseif ( MoWpnsConstants::LOGIN_TRANSACTION === $row->type && MoWpnsConstants::FAILED === $row->status ) {
					$summary['failed_attempts'] += $count;
				} elseif ( MoWpnsConstants::ADMIN_2FA_RESET === $row->type ) {
					$summary['admin_resets'] += $count;
				} elseif ( MoWpnsConstants::SELF_SERVICE_2FA_RESET === $row->type ) {
					$summary['self_service_resets'] += $count;
				}
			}
			return $summary;
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
					if ( ! isset( $transaction['type'] ) ) {
						return false;
					}

					return 'User Login' === $transaction['type'];
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

			if ( empty( $update ) || empty( $where ) ) {
				return;
			}

			$data        = array();
			$data_format = array();
			foreach ( $update as $key => $value ) {
				$column_name = sanitize_key( $key );
				if ( $column_name !== $key || ! is_scalar( $value ) ) {
					continue;
				}

				if ( in_array( $column_name, array( 'created_timestamp', 'id' ), true ) ) {
					$data[ $column_name ] = (int) $value;
					$data_format[]        = '%d';
				} else {
					$data[ $column_name ] = sanitize_text_field( wp_unslash( (string) $value ) );
					$data_format[]        = '%s';
				}
			}

			$where_data        = array();
			$where_data_format = array();
			foreach ( $where as $key => $value ) {
				$column_name = sanitize_key( $key );
				if ( $column_name !== $key || ! is_scalar( $value ) ) {
					continue;
				}

				if ( in_array( $column_name, array( 'created_timestamp', 'id' ), true ) ) {
					$where_data[ $column_name ] = (int) $value;
					$where_data_format[]        = '%d';
				} else {
					$where_data[ $column_name ] = sanitize_text_field( wp_unslash( (string) $value ) );
					$where_data_format[]        = '%s';
				}
			}

			if ( empty( $data ) || empty( $where_data ) ) {
				return;
			}

			$wpdb->update( $this->transaction_table, $data, $where_data, $data_format, $where_data_format ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Using $wpdb->update() ensures proper preparation.
		}

		/**
		 * Retrieve data from a specified table.
		 *
		 * @param string $table_name Table name (without prefix).
		 * @return array|null Retrieved data or null if empty.
		 */
		public function mo2f_get_old_table_data( $table_name ) {
			global $wpdb;

			$table_name = trim( (string) $table_name );
			if ( '' === $table_name || 1 !== preg_match( '/^[A-Za-z0-9_]+$/', $table_name ) ) {
				return null;
			}

			$full_table_name = $wpdb->base_prefix . $table_name;
			$table_exists    = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $full_table_name ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Schema checks require direct DB access.

			if ( $table_exists !== $full_table_name ) {
				return null;
			}

			$table_name_safe = esc_sql( $full_table_name );

			$cache_key   = 'mo2f_old_table_data_' . md5( $table_name_safe );
			$cache_group = 'mo2f_transactions';
			$cache_entry = wp_cache_get( $cache_key, $cache_group );

			if ( false !== $cache_entry ) {
				return isset( $cache_entry['data'] ) ? $cache_entry['data'] : null;
			}

			$old_data = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Direct DB read required for migration and is cached above.
				$wpdb->prepare(
					'SELECT * FROM ' . $table_name_safe . ' WHERE 1 = %d', // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- Table name sanitized above.
					1
				),
				ARRAY_A
			);
			$result   = ! empty( $old_data ) ? $old_data : null;

			wp_cache_set( $cache_key, array( 'data' => $result ), $cache_group );

			return $result;
		}
	}
}
