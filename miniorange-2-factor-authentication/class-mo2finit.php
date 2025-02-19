<?php
/**
 * Load Main File Mo2fInit
 *
 * @package miniorange-2-facator-authentication
 */

namespace TwoFA;

use TwoFA\Handler\Mo2f_2fa_Settings_Handler;
use TwoFA\Handler\AjaxHandler;
use TwoFA\Handler\FeedbackHandler;
use TwoFA\Handler\Handle_Migration;
use TwoFA\Handler\Mo2f_Admin_Action_Handler;
use TwoFA\Handler\Mo2f_Advance_Settings_Handler;
use TwoFA\Handler\Mo2f_All_Inclusive_Premium_Settings;
use TwoFA\Handler\Mo2f_Basic_Premium_Settings;
use TwoFA\Handler\Mo2f_Enterprise_Premium_Settings;
use TwoFA\Handler\Mo2f_IP_Blocking_Handler;
use TwoFA\Handler\Mo2f_Logger;
use TwoFA\Handler\Mo2f_VLS;
use TwoFA\Handler\Mo2f_Main_Handler;
use TwoFA\Handler\Mo2f_Whitelabelling;
use TwoFA\Handler\Twofa\Miniorange_Authentication;
use TwoFA\Helper\Mo2f_Setupwizard;
use TwoFA\Helper\MocURL;
use TwoFA\Helper\MoWpnsHandler;
use TwoFA\Helper\Mo2f_Premium_Common_Helper;
use TwoFA\Cloud\Two_Factor_Setup;
use TwoFA\Database\Mo2fDB;
use TwoFA\Database\MoWpnsDB;
use TwoFA\Traits\Instance;
use TwoFA\Handler\LoginHandler;
use TwoFA\Controllers\Twofa\Mo_2f_Ajax;
use TwoFA\Handler\Mo2f_Reconfigure_Link;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Mo2fInit' ) ) {

	/**
	 * Class Mo2fInit
	 */
	final class Mo2fInit {

		use Instance;

		/**
		 * Mo2fInit classs constructor
		 */
		private function __construct() {
			$this->mo2f_initialize_clouds();
			$this->mo2f_initialize_handlers();
			$this->mo2f_initialize_helpers();
			$this->mo2f_initialize_globals();
			$this->mo2f_initialize_controllers();
		}

		/**
		 * Initialize Handlers
		 */
		private function mo2f_initialize_handlers() {
			global $mo2f_dir_name;
			Mo2f_2fa_Settings_Handler::instance();
			AjaxHandler::instance();
			FeedbackHandler::instance();
			Handle_Migration::instance();
			Mo2f_Admin_Action_Handler::instance();
			Mo2f_Advance_Settings_Handler::instance();
			LoginHandler::instance();
			if ( file_exists( $mo2f_dir_name . 'handler' . DIRECTORY_SEPARATOR . 'class-mo2f-all-inclusive-premium-settings.php' ) ) {
				Mo2f_All_Inclusive_Premium_Settings::instance();
			}
			if ( file_exists( $mo2f_dir_name . 'handler' . DIRECTORY_SEPARATOR . 'class-mo2f-enterprise-premium-settings.php' ) ) {
				Mo2f_Enterprise_Premium_Settings::instance();
			}
			if ( file_exists( $mo2f_dir_name . 'handler' . DIRECTORY_SEPARATOR . 'class-mo2f-basic-premium-settings.php' ) ) {
				Mo2f_Basic_Premium_Settings::instance();
			}
			if ( file_exists( $mo2f_dir_name . 'handler' . DIRECTORY_SEPARATOR . 'class-mo2f-vls.php' ) ) {
				Mo2f_VLS::instance();
			}
			Mo2f_IP_Blocking_Handler::instance();
			Mo2f_Logger::instance();
			Mo2f_Whitelabelling::instance();
			Miniorange_Authentication::instance();
			MocURL::instance();
			MoWpnsHandler::instance();
			Mo2f_Main_Handler::instance();
			Mo2f_Reconfigure_Link::instance();
		}

		/**
		 * Initialize Helpers
		 */
		private function mo2f_initialize_helpers() {
			global $mo2f_dir_name;
			Mo2f_Setupwizard::instance();
			if ( file_exists( $mo2f_dir_name . 'helper' . DIRECTORY_SEPARATOR . 'class-mo2f-premium-common-helper.php' ) ) {
				Mo2f_Premium_Common_Helper::instance();
			}
		}

		/**
		 * Initialize Clouds
		 */
		private function mo2f_initialize_clouds() {
			Two_Factor_Setup::instance();
		}

		/**
		 * Initialize Clouds
		 */
		private function mo2f_initialize_controllers() {
			Mo_2f_Ajax::instance();
		}

		/**
		 * Initialize globals
		 */
		private function mo2f_initialize_globals() {
			global $mo2fdb_queries,$wpns_db_queries;
			$mo2fdb_queries  = Mo2fDB::instance();
			$wpns_db_queries = MoWpnsDB::instance();
		}

	}
}
