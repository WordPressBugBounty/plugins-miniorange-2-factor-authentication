<?php
/**
 * File contains 2fa database queries.
 *
 * @package miniOrange-2-factor-authentication/database
 */

namespace TwoFA\Database;

use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Traits\Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Upgrade.php included.
 */
require_once ABSPATH . 'wp-admin/includes/upgrade.php';

if ( ! class_exists( 'Mo2FDB' ) ) {

	/**
	 * Class Mo2fDB
	 */
	class Mo2fDB {

		use Instance;

		/**
		 * User details table variable.
		 *
		 * @var string
		 */
		private $user_details_table;

		/**
		 * User login information table variable.
		 *
		 * @var string
		 */
		private $user_login_info_table;

		/**
		 * Class Mo2fDB constructor
		 */

		/**
		 * Class Mo2fDB constructor.
		 */
		public function __construct() {
			global $wpdb;
			$this->user_details_table    = $wpdb->base_prefix . 'mo2f_user_details';
			$this->user_login_info_table = $wpdb->base_prefix . 'mo2f_user_login_info';
		}

		/**
		 * Updates the database version in the options table.
		 *
		 * @return void
		 */
		public function mo_plugin_activate() {
			if ( ! get_site_option( 'mo2f_dbversion' ) ) {
				update_site_option( 'mo2f_dbversion', MoWpnsConstants::DB_VERSION );
				$this->generate_tables();
			} else {
				$current_db_version = get_site_option( 'mo2f_dbversion' );
				if ( $current_db_version < MoWpnsConstants::DB_VERSION ) {

					update_site_option( 'mo2f_dbversion', MoWpnsConstants::DB_VERSION );
					$this->generate_tables();
				}
				// update the tables based on DB_VERSION.
			}
		}

		/**
		 * Creates the tables and adds columns if not exist in the database.
		 *
		 * @return void
		 */
		public function generate_tables() {
			global $wpdb;
			add_site_option( 'cmVtYWluaW5nT1RQ', 30 );
			add_site_option( 'bGltaXRSZWFjaGVk', 0 );
			$table_name = $this->user_login_info_table;
			if ( $wpdb->get_var( $wpdb->prepare( 'show tables like %s', array( $table_name ) ) ) !== $table_name ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- DB Direct Query is necessary here.
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange -- Schema change is neccessary here.
				$sql = 'CREATE TABLE IF NOT EXISTS ' . $table_name . ' (
			`session_id` mediumtext NOT NULL, 
			 `mo2f_login_message` mediumtext NOT NULL , 
			 `mo2f_current_user_id` tinyint NOT NULL , 
			 `mo2f_1stfactor_status` mediumtext NOT NULL , 
			 `mo_2factor_login_status` mediumtext NOT NULL , 
			 `mo2f_transactionId` mediumtext NOT NULL , 
			 `mo_2_factor_kba_questions` longtext NOT NULL , 
			 `mo2f_rba_status` longtext NOT NULL , 
			 `secret_ga` mediumtext NOT NULL,
			 `ga_qrCode` mediumtext NOT NULL,
			 `ts_created` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			  PRIMARY KEY (`session_id`(100)));';

				dbDelta( $sql );
			}

			$check_if_column_exists = $this->check_if_column_exists( 'user_login_info_table', 'mo_2factor_login_status' );

			if ( ! $check_if_column_exists ) {
				$query = "ALTER TABLE `$table_name` ADD COLUMN `mo_2factor_login_status` mediumtext NOT NULL";
				$this->execute_add_column( $query );

			}
			$check_if_column_exists = $this->check_if_column_exists( 'user_login_info_table', 'secret_ga' );

			if ( ! $check_if_column_exists ) {
				$query = "ALTER TABLE `$table_name` ADD COLUMN `secret_ga` mediumtext NOT NULL";
				$this->execute_add_column( $query );

			}
			$check_if_column_exists = $this->check_if_column_exists( 'user_login_info_table', 'ga_qrCode' );

			if ( ! $check_if_column_exists ) {
				$query = "ALTER TABLE `$table_name` ADD COLUMN `ga_qrCode` mediumtext NOT NULL";
				$this->execute_add_column( $query );

			}
		}

		/**
		 * Fetches all users RBA details.
		 *
		 * @return mixed
		 */
		public function mo2f_get_all_users_rba_details() {
			global $wpdb;
			$rba_datails        = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %1s WHERE meta_key = %s;', array( $wpdb->base_prefix . 'usermeta', 'mo2f_rba_device_details' ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- DB Direct Query is necessary here.
			$all_users_rba_data = array();
			foreach ( $rba_datails as $rba_detail ) {
				foreach ( maybe_unserialize( $rba_detail->meta_value ) as $value ) {
					array_push( $all_users_rba_data, $value );
				}
			}
			return $all_users_rba_data;
		}

		/**
		 * Return RBA data from tables.
		 *
		 * @return mixed
		 */
		public function mo2f_get_all_device_details() {
			global $wpdb;
			$does_table_exist = $wpdb->query( $wpdb->prepare( 'SHOW TABLES LIKE %s;', array( $wpdb->base_prefix . 'mo2f_user_device_details' ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- DB Direct Query is necessary here.
			if ( $does_table_exist ) {
				$value = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %1s', array( $wpdb->base_prefix . 'mo2f_user_device_details' ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- DB Direct Query is necessary here.
				return $value;
			}
			return false;
		}

		/**
		 * Gets the email ID of current user from database.
		 *
		 * @param integer $id current user id.
		 * @return string
		 */
		public function get_current_user_email( $id ) {
			global $wpdb;

			return $wpdb->get_var( $wpdb->prepare( 'select user_email from wp_users	where ID=%d', array( $id ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- DB Direct Query is necessary here.
		}

		/**
		 * Creates user login details table and adds column if not exist in database.
		 *
		 * @return void
		 */
		public function database_table_issue() {
			global $wpdb;
			$table_name = $this->user_login_info_table;
			if ( $wpdb->get_var( $wpdb->prepare( 'show tables like %s', array( $table_name ) ) ) !== $table_name ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- DB Direct Query is necessary here.
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange -- Schema change is neccessary here.
				$sql = 'CREATE TABLE IF NOT EXISTS ' . $table_name . ' (
			`session_id` mediumtext NOT NULL, 
			 `mo2f_login_message` mediumtext NOT NULL , 
			 `mo2f_current_user_id` tinyint NOT NULL , 
			 `mo2f_1stfactor_status` mediumtext NOT NULL , 
			 `mo_2factor_login_status` mediumtext NOT NULL , 
			 `mo2f_transactionId` mediumtext NOT NULL , 
			 `mo_2_factor_kba_questions` longtext NOT NULL , 
			 `mo2f_rba_status` longtext NOT NULL , 
			 `ts_created` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			  PRIMARY KEY (`session_id`(100)));';
				dbDelta( $sql );
			}

			$check_if_column_exists = $this->check_if_column_exists( 'user_login_info_table', 'mo_2factor_login_status' );

			if ( ! $check_if_column_exists ) {
				$query = "ALTER TABLE `$table_name` ADD COLUMN `mo_2factor_login_status` mediumtext NOT NULL";
				$this->execute_add_column( $query );

			}
		}


		/**
		 * Fetch user details of given user id from database.
		 *
		 * @param string  $column_name Name of the column from which the details get fetched.
		 * @param integer $user_id Id of the users whose details need to be fetched.
		 * @return string
		 */
		public function mo2f_get_user_detail( $column_name, $user_id ) {
			$mo2f_user_2fa_data = get_user_meta( $user_id, 'mo2f_user_2fa_data', true );
			if ( ! empty( $mo2f_user_2fa_data ) && isset( $mo2f_user_2fa_data[ $column_name ] ) ) {
				return $mo2f_user_2fa_data[ $column_name ];
			}
			return '';
		}

		/**
		 * Gets the count of number of users who uses specific method.
		 *
		 * @param string $method 2FA method setup by the user.
		 * @return integer
		 */
		public function mo2f_get_specific_method_users_count( $method ) {
			global $wpdb;
			$users_meta = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT user_id, meta_value
					FROM {$wpdb->usermeta}
					WHERE meta_key = %s",
					MoWpnsConstants::USER_DEATIL_META_KEY
				)
			);
			$count      = 0;
			if ( ! empty( $users_meta ) ) {
				foreach ( $users_meta as $user_meta ) {
					$meta_value = maybe_unserialize( $user_meta->meta_value );
					if ( is_array( $meta_value ) && isset( $meta_value['mo2f_configured_2FA_method'] ) ) {
						$configured_method = $meta_value['mo2f_configured_2FA_method'];
						if ( $configured_method === $method ) {
							++$count;
						}
					}
				}
			}
			return $count;
		}

		/**
		 * Deletes the user details for corresponding user id from user details table.
		 *
		 * @param integer $user_id User ID whose details will be deleted.
		 * @return void
		 */
		public function mo2f_delete_user_details( $user_id ) {
			delete_user_meta( $user_id, MoWpnsConstants::USER_DEATIL_META_KEY );
		}

		/**
		 * Gets the number of 2-factor authentication users.
		 *
		 * @return integer
		 */
		public function mo2f_get_no_of_2fa_users() {
			global $wpdb;
			$count = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(DISTINCT user_id) FROM {$wpdb->usermeta} WHERE meta_key = %s",
					MoWpnsConstants::USER_DEATIL_META_KEY
				)
			);
			return $count;
		}

		/**
		 * Gets all the 2-factor authentication methods configured.
		 *
		 * @return array
		 */
		public function get_all_user_2fa_methods() {
			global $wpdb;
			$all_methods = array();
			$users_meta  = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT user_id, meta_value FROM {$wpdb->usermeta} WHERE meta_key = %s",
					MoWpnsConstants::USER_DEATIL_META_KEY
				)
			);
			if ( ! empty( $users_meta ) ) {
				foreach ( $users_meta as $user_meta ) {
					$meta_value = maybe_unserialize( $user_meta->meta_value );
					if ( is_array( $meta_value ) && isset( $meta_value['mo2f_configured_2FA_method'] ) ) {
						array_push( $all_methods, $meta_value['mo2f_configured_2FA_method'] );
					}
				}
			}
			return $all_methods;
		}


		/**
		 * Fetch the user details corresponding to given user id from user details table.
		 *
		 * @param integer $user_id User ID whose details need to be fetched.
		 * @return integer
		 */
		public function mo2f_check_if_user_exists( $user_id ) {
			$meta_value = get_user_meta( $user_id, 'mo2f_user_2fa_data', true );
			return ! empty( $meta_value );
		}

		/**
		 * Check if given column exist in the given table.
		 *
		 * @param string $table_type Name of the table where the given column will be checked.
		 * @param string $column_name Name of the column which will be checked in the given table.
		 * @return bool
		 */
		public function check_if_column_exists( $table_type, $column_name ) {
			$table = $this->user_login_info_table;
			global $wpdb;

			$value = $wpdb->query( $wpdb->prepare( 'SHOW COLUMNS FROM %1s LIKE %s;', array( $table, $column_name ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- DB Direct Query is necessary here.
			return $value;
		}

		/**
		 * Updates user details for corresponding user id in user details table.
		 *
		 * @param integer $user_id User ID whose details need to be updated.
		 * @param array   $update The details which need to be updated for given user id.
		 * @return void
		 */
		public function mo2f_update_user_details( $user_id, $update ) {
			$mo2f_user_2fa_data = get_user_meta( $user_id, 'mo2f_user_2fa_data', true );
			if ( ! is_array( $mo2f_user_2fa_data ) ) {
				$mo2f_user_2fa_data = array();
			}
			foreach ( $update as $key => $value ) {
					$mo2f_user_2fa_data[ $key ] = $value;
			}
			update_user_meta( $user_id, 'mo2f_user_2fa_data', $mo2f_user_2fa_data );
		}

		/**
		 * Inserts session Id in user login information table and delete the details if created time stamps is less than current added time.
		 *
		 * @param string $session_id The session id which need to be stored.
		 * @return void
		 */
		public function insert_user_login_session( $session_id ) {
			global $wpdb;

			$wpdb->query( $wpdb->prepare( 'INSERT INTO %1s (session_id) VALUES(%d) ON DUPLICATE KEY UPDATE session_id= %s;', array( $this->user_login_info_table, $session_id, $session_id ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- DB Direct Query is necessary here.

			$wpdb->query( $wpdb->prepare( 'DELETE FROM %1s WHERE ts_created < DATE_ADD(NOW(),INTERVAL - 2 MINUTE);', array( $this->user_login_info_table ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- DB Direct Query is necessary here.
		}

		/**
		 * Deletes user meta when admin removes miniOrange account from plugin.
		 *
		 * @return void
		 */
		public function mo2f_delete_cloud_meta_on_account_remove() {
			global $wpdb;
			$tablename = $wpdb->base_prefix . 'usermeta';
			$prefix    = 'mo2f_%';
			$wpdb->query( $wpdb->prepare( 'DELETE FROM %1s  WHERE `meta_key` LIKE %s', $tablename, $prefix ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- DB Direct Query is necessary here.
		}
		/**
		 * Inserts values in session_id and ts_created columns from user login information table.
		 *
		 * @param string $session_id Session Id corresponding which the details need to be saved.
		 * @param array  $user_values Array of column name and it's values.
		 * @return void
		 */
		public function save_user_login_details( $session_id, $user_values ) {
			global $wpdb;
			$count = count( $user_values );
			$sql   = 'UPDATE ' . $this->user_login_info_table . ' SET ';
			$i     = 1;
			foreach ( $user_values as $key => $value ) {
				if ( 'session_id' === $key || 'ts_created' === $key ) {
					$sql .= $wpdb->prepare( ' %1s=%d', array( $key, $value ) ); // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- We can not have table name in quotes.
				} else {
					$sql .= $wpdb->prepare( ' %1s=%s', array( $key, $value ) ); // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- We can not have table name in quotes.
				}
				if ( $i < $count ) {
					$sql .= ' , ';
				}
				$i++;
			}

			$wpdb->query( $sql .= $wpdb->prepare( ' WHERE session_id=%s;', array( $session_id ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Ignoring complex placeholder warning and DB Direct Query is necessary here.
		}

		/**
		 * Executes the given query.
		 *
		 * @param string $query The query which needs to be executed.
		 * @return void
		 */
		public function execute_add_column( $query ) {
			global $wpdb;

			$wpdb->query( $query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Ignoring complex placeholder warning and DB Direct Query is necessary here.
		}

		/**
		 * Fetch details corresponding to given session ID from given column of user login information table.
		 *
		 * @param string $column_name Name of the column from which the details need to be fetched.
		 * @param string $session_id Session Id corresponding which the details need to be fetched.
		 * @return string
		 */
		public function get_user_login_details( $column_name, $session_id ) {
			global $wpdb;

			$user_column_detail = $wpdb->get_results( $wpdb->prepare( 'SELECT %1s FROM %1s WHERE session_id = %s;', array( $column_name, $this->user_login_info_table, $session_id ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- DB Direct Query is necessary here.
			$value              = empty( $user_column_detail ) ? '' : get_object_vars( $user_column_detail[0] );
			return empty( $value ) ? '' : $value[ $column_name ];
		}

		/**
		 * Gets the configured 2-fator authentication methods corresponding to given user id from user details table.
		 *
		 * @param interger $user_id User id whose details need to be fetched.
		 * @return array
		 */
		public function mo2f_get_user_configured_methods( $user_id ) {
			$user_meta           = get_user_meta( $user_id, MoWpnsConstants::USER_DEATIL_META_KEY, true );
			$user_methods_detail = ! empty( $user_meta ) ? maybe_unserialize( $user_meta ) : array();
			if ( ! empty( $user_methods_detail ) && is_array( $user_methods_detail ) ) {
				if ( ! isset( $user_methods_detail[0] ) ) {
					return array( (object) $user_methods_detail );
				}
				return array_map(
					function ( $item ) {
						return (object) $item;
					},
					$user_methods_detail
				);
			}
			return array();
		}

		/**
		 * Delete details corresponding to given session id from user login information table.
		 *
		 * @param string $session_id Session id corresponding which the details need to be deleted.
		 * @return void
		 */
		public function delete_user_login_sessions( $session_id ) {
			global $wpdb;

			$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- DB Direct Query is necessary here.
				$wpdb->prepare( 'DELETE FROM %1s  WHERE session_id=%s;', array( $this->user_login_info_table, $session_id ) ) // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- We can not have table name in quotes.
			);
		}

		/**
		 * Checks if the user limit is exceeded.
		 *
		 * @param integer $user_id User Id to check if the corresponding user already configured the 2fa method.
		 * @return bool
		 */
		public function check_alluser_limit_exceeded( $user_id ) {
			global $wpdb;
			$total_users_configured  = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(DISTINCT user_id) FROM {$wpdb->usermeta} WHERE meta_key = %s",
					MoWpnsConstants::USER_DEATIL_META_KEY
				)
			);
			$user_meta_data          = get_user_meta( $user_id, MoWpnsConstants::USER_DEATIL_META_KEY, true );
			$user_already_configured = ! empty( $user_meta_data );

			if ( $total_users_configured < 3 || $user_already_configured ) {
				return false;
			} else {
				return true;
			}
		}

		/**
		 * Fetches user id from reset token.
		 *
		 * @param string $reset_token Reset token.
		 * @return string
		 */
		public function mo2f_get_userid_from_reset_token( $reset_token ) {
			global $wpdb;

			$user_details = $wpdb->get_results( $wpdb->prepare( 'SELECT user_id  FROM %1s WHERE meta_key = %s and meta_value = %s;', array( $wpdb->base_prefix . 'usermeta', 'mo2f_reset_token', $reset_token ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- DB Direct Query is necessary here.
			return $user_details[0]->user_id;
		}

		/**
		 * Get all the user ids and updates the user details in user details table.
		 *
		 * @return void
		 */
		public function get_all_onprem_userids() {
			global $wpdb;

			$value = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- DB Direct Query is necessary here.
				'SELECT * FROM ' . $wpdb->base_prefix . "usermeta 
                 WHERE meta_key = 'currentMethod'"
			);

			foreach ( $value as $row ) {

				if ( isset( $row->user_id ) ) {

					$this->mo2f_update_user_details(
						$row->user_id,
						array(
							'mo2f_GoogleAuthenticator_config_status' => get_user_meta( $row->user_id, MoWpnsConstants::GOOGLE_AUTHENTICATOR, true ),
							'mo2f_SecurityQuestions_config_status' => get_user_meta( $row->user_id, MoWpnsConstants::GOOGLE_AUTHENTICATOR, true ),
							'mo2f_EmailVerification_config_status' => get_user_meta( $row->user_id, MoWpnsConstants::OUT_OF_BAND_EMAIL, true ),
							'mo2f_AuthyAuthenticator_config_status' => 0,
							'mo2f_user_email'            => get_user_meta( $row->user_id, 'email', true ),
							'mo2f_user_phone'            => '',
							'user_registration_with_miniorange' => '',
							'mobile_registration_status' => '',
							'mo2f_configured_2FA_method' => get_user_meta( $row->user_id, 'currentMethod', true ),
							'mo_2factor_user_registration_status' => 'MO_2_FACTOR_PLUGIN_SETTINGS',
						)
					);
				}
			}
		}
		/**
		 * Drops a specified table from the WordPress database.
		 *
		 * @param string $table_name The name of the table to be dropped (without prefix).
		 * @return void
		 */
		public function mo2f_drop_table( $table_name ) {
			global $wpdb;
			$table_name = esc_sql( $wpdb->base_prefix . $table_name );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- DB Direct Query is necessary here.
			$wpdb->query( "DROP TABLE IF EXISTS `{$table_name}`" );
		}
	}
}
