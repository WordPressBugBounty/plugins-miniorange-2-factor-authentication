<?php //phpcs:ignore WordPress.Files.FileName.InvalidClassFileName, WPShield_Standard.Security.DisallowBrandAndImproperPluginName.ImproperPluginName, WordPress.Files.FileName.NotHyphenatedLowercase -- Cannot change the main settings filename
/**
 * Main plugin settings file for miniOrange 2-factor Authentication.
 *
 * @package miniOrange 2FA
 */

/**
 * Plugin Name: miniOrange 2 Factor Authentication
 * Plugin URI: https://miniorange.com
 * Description: This TFA plugin provides various two-factor authentication methods as an additional layer of security after the default WordPress login. We Support Google/Authy/LastPass/Microsoft Authenticator, QR Code, Push Notification, Soft Token and Security Questions(KBA) for 3 User in the free version of the plugin.
 * Version: 6.1.3
 * Author: miniOrange
 * Author URI: https://miniorange.com
 * Text Domain: miniorange-2-factor-authentication
 * License: Expat
 * License URI: https://plugins.miniorange.com/mit-license
 */

namespace TwoFA;

use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Helper\MoWpnsUtility;
use TwoFA\Helper\MoWpnsMessages;
use TwoFA\Handler\Twofa\MO2f_Utility;
use TwoFA\Handler\Twofa\Miniorange_Authentication;
use TwoFA\Views\Mo2f_Setup_Wizard;
use TwoFA\Helper\Mo2f_MenuItems;
use TwoFA\Handler\Twofa\Mo2fCustomRegFormShortcode;
use TwoFA\Handler\Mo2f_2fa_Settings_Handler;
use TwoFA\Traits\Instance;
use TwoFA\Handler\RegistrationHandler;
use TwoFA\Mo2f_Classloader;
use TwoFA\Helper\Miniorange_Security_Notification;
use TwoFA\Database\Mo2fDB;
use TwoFA\Database\MoWpnsDB;
use TwoFA\Mo2fInit;
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'mo2f-db-options.php';
require dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'new-release-email.php';
require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'traits' . DIRECTORY_SEPARATOR . 'class-instance.php';

define( 'MO_HOST_NAME', 'https://login.xecurify.com' );
define( 'MO2F_VERSION', '6.1.3' );
define( 'MO2F_PLUGIN_URL', ( plugin_dir_url( __FILE__ ) ) );
define( 'MO2F_TEST_MODE', false );
define( 'MO2F_IS_ONPREM', get_option( 'is_onprem', 1 ) );
define( 'MO2F_PREMIUM_PLAN', false );
define( 'DEFAULT_GOOGLE_APPNAME', preg_replace( '#^https?://#i', '', home_url() ) );

global $main_dir, $image_path;
$main_dir   = plugin_dir_url( __FILE__ );
$image_path = plugin_dir_url( __FILE__ );

if ( ! class_exists( 'Miniorange_TwoFactor' ) ) {
	/**
	 * Includes all the hooks and actions in the main plugin file.
	 */
	class Miniorange_TwoFactor {

		use Instance;

		/**
		 * Constructor.
		 */
		public function __construct() {
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'mo2f_add_plugin_action_link' ), 10, 1 );
			register_deactivation_hook( __FILE__, array( $this, 'mo_wpns_deactivate' ) );
			register_activation_hook( __FILE__, array( $this, 'mo_wpns_activate' ) );
			add_action( 'admin_menu', array( $this, 'mo_wpns_widget_menu' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'mo_wpns_settings_style' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'mo_wpns_settings_script' ) );
			add_action( 'admin_init', array( $this, 'miniorange_reset_save_settings' ) );
			add_action( 'admin_init', array( $this, 'mo2f_mail_send' ) );
			add_filter( 'manage_users_columns', array( $this, 'mo2f_mapped_email_column' ) );
			add_action( 'manage_users_custom_column', array( $this, 'mo2f_mapped_email_column_content' ), 10, 3 );
			add_action( 'admin_notices', array( $this, 'mo2f_notices' ) );
			$actions = add_filter( 'user_row_actions', array( $this, 'miniorange_reset_users' ), 10, 2 );
			add_action( 'admin_footer', array( $this, 'feedback_request' ) );
			add_action( 'plugins_loaded', array( $this, 'mo2fa_load_textdomain' ) );
			if ( ! defined( 'DISALLOW_FILE_EDIT' ) && get_option( 'mo2f_disable_file_editing' ) ) {
				define( 'DISALLOW_FILE_EDIT', true );
			}
			$notify = new Miniorange_Security_Notification();
			add_action( 'wp_dashboard_setup', array( $notify, 'my_custom_dashboard_widgets' ) );
			if ( ! apply_filters( 'mo2f_is_lv_needed', false ) ) {
				add_action( 'plugins_loaded', array( $this, 'mo2f_add_wizard_actions' ), 1 );
			}
			$custom_short    = new Mo2fCustomRegFormShortcode();
			$reg_handler_obj = new RegistrationHandler();
			add_action( 'admin_init', array( $reg_handler_obj, 'mo2f_wp_verification' ) );
			add_action( 'admin_init', array( $custom_short, 'mo_enqueue_shortcode' ) );
			add_action( 'elementor/init', array( $this, 'mo2fa_login_elementor_note' ) );
			add_shortcode( 'mo2f_enable_register', array( $reg_handler_obj, 'mo2f_wp_verification' ) );
			add_action( 'user_profile_update_errors', array( $this, 'mo2f_user_profile_errors' ), 10, 3 );
			add_action( 'admin_init', array( $this, 'mo2f_migrate_whitelisted_ips_table' ) );
			add_action( 'admin_init', array( $this, 'mo2f_migrate_network_blocked_ips_table' ) );
			add_action( 'admin_init', array( $this, 'mo2f_migrate_user_details' ) );
			add_action( 'admin_init', array( $this, 'mo2f_drop_wpns_attack_logs_and_network_email_sent_audit' ) );
		}

		/**
		 * Shows error messages in the user profile
		 *
		 * @param object  $errors error object.
		 * @param boolean $update boolean variable update.
		 * @param object  $userdata user data object.
		 * @return void
		 */
		public function mo2f_user_profile_errors( $errors, $update, $userdata ) {
			global $mo2fdb_queries;
			$nonce = isset( $_POST['mo2f_enable_user_profile_2fa_nonce'] ) ? sanitize_key( wp_unslash( $_POST['mo2f_enable_user_profile_2fa_nonce'] ) ) : null;
			if ( wp_verify_nonce( $nonce, 'mo-two-factor-ajax-nonce' ) ) {
				$is_userprofile_enabled = isset( $_POST['mo2f_enable_userprofile_2fa'] ) ? sanitize_key( wp_unslash( $_POST['mo2f_enable_userprofile_2fa'] ) ) : false;
				if ( $is_userprofile_enabled ) {
					$twofa_configuration_status = $mo2fdb_queries->mo2f_get_user_detail( 'mo_2factor_user_registration_status', $userdata->ID );
					if ( MoWpnsConstants::MO_2_FACTOR_PLUGIN_SETTINGS === $twofa_configuration_status ) {
						$existing_twofa_method = $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_configured_2FA_method', $userdata->ID );
						$selected_method       = isset( $_POST['method'] ) ? sanitize_text_field( wp_unslash( $_POST['method'] ) ) : '';
						if ( $existing_twofa_method === $selected_method ) {
							return;
						}
					}
					$error_message = get_user_meta( $userdata->ID, 'mo2f_userprofile_error_message', true );
					delete_user_meta( $userdata->ID, 'mo2f_userprofile_error_message' );
					if ( $error_message ) {
						$errors->add( 'user_profile_error', $error_message );
					}
				}
			}
		}

		/**
		 * Includes scripts and localize parameters for elementor login form.
		 *
		 * @return void
		 */
		public function mo2fa_login_elementor_note() {
			global $main_dir;

			if ( ! is_user_logged_in() ) {
				wp_enqueue_script( 'jquery' );
				wp_enqueue_script( 'mo2fa_elementor_script', $main_dir . 'includes/js/mo2fa_elementor.min.js', array(), MO2F_VERSION, false );

				wp_localize_script(
					'mo2fa_elementor_script',
					'my_ajax_object',
					array(
						'ajax_url'          => get_site_url() . '/login/',
						'nonce'             => wp_create_nonce( 'miniorange-2-factor-login-nonce' ),
						'mo2f_login_option' => MoWpnsUtility::get_mo2f_db_option( 'mo2f_login_option', 'get_option' ),
						'mo2f_enable_login_with_2nd_factor' => get_option( 'mo2f_enable_login_with_2nd_factor' ),
					)
				);
			}
		}
		/**
		 * As on plugins.php page not in the plugin.
		 *
		 * @return void
		 */
		public function feedback_request() {
			if ( isset( $_SERVER['PHP_SELF'] ) && 'plugins.php' !== basename( esc_url_raw( wp_unslash( $_SERVER['PHP_SELF'] ) ) ) ) {
				return;
			}
			global $mo2f_dir_name;

			$email = get_site_option( 'mo2f_email' );
			if ( empty( $email ) ) {
				$user  = wp_get_current_user();
				$email = $user->user_email;
			}

			wp_enqueue_style( 'wp-pointer' );
			wp_enqueue_script( 'wp-pointer' );
			wp_enqueue_script( 'utils' );

			include $mo2f_dir_name . 'views' . DIRECTORY_SEPARATOR . 'feedback-form.php';
		}
		/**
		 * Function tells where to look for translations.
		 */
		public function mo2fa_load_textdomain() {
			load_plugin_textdomain( 'miniorange-2-factor-authentication', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
		}
		/**
		 * Including setup wiard actions.
		 *
		 * @return void
		 */
		public function mo2f_add_wizard_actions() {
			global $mo2f_dir_name;
			if ( file_exists( $mo2f_dir_name . 'views' . DIRECTORY_SEPARATOR . 'class-mo2f-setup-wizard.php' ) ) {
				$object = new Mo2f_Setup_Wizard();
				if ( function_exists( 'wp_get_current_user' ) && current_user_can( 'administrator' ) ) {
					add_action( 'admin_init', array( $object, 'mo2f_setup_page' ), 11 );
				}
			}
		}
		/**
		 * Add notices to admin dashboard.
		 *
		 * @return void
		 */
		public function mo2f_notices() {
			$one_day      = 60 * 60 * 24;
			$dismiss_time = get_site_option( 'notice_dismiss_time' );

			$dismiss_time = ( time() - $dismiss_time ) / $one_day;
			$dismiss_time = (int) $dismiss_time;

			// setting variables for low SMS/email notification.
			global $mo2fdb_queries;
			$user_object                  = wp_get_current_user();
			$mo2f_configured_2_f_a_method = $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_configured_2FA_method', $user_object->ID );
			$one_day                      = 60 * 60 * 24;
			$day_sms                      = ( time() - get_site_option( 'mo2f_wpns_sms_dismiss' ) ) / $one_day;
			$day_sms                      = floor( $day_sms );
			$day_email                    = ( time() - get_site_option( 'mo2f_wpns_email_dismiss' ) ) / $one_day;
			$day_email                    = floor( $day_email );

			$count = $mo2fdb_queries->mo2f_get_specific_method_users_count( MoWpnsConstants::OTP_OVER_SMS );
			if ( ! apply_filters( 'mo2f_is_lv_needed', false ) && MoWpnsConstants::OTP_OVER_EMAIL === $mo2f_configured_2_f_a_method && ( $day_email >= 1 ) && ! get_site_option( 'mo2f_wpns_donot_show_low_email_notice' ) && ( get_site_option( 'cmVtYWluaW5nT1RQ' ) <= 5 ) ) {
				echo wp_kses_post( MoWpnsMessages::show_message( 'LOW_EMAIL_TRANSACTIONS' ) );
			}
			if ( ! get_site_option( 'mo2f_wpns_donot_show_low_sms_notice' ) && ( get_site_option( 'cmVtYWluaW5nT1RQVHJhbnNhY3Rpb25z' ) <= 4 ) && ( $day_sms >= 1 ) && 0 !== $count ) {
				echo wp_kses_post( MoWpnsMessages::show_message( 'LOW_SMS_TRANSACTIONS' ) );
			}
		}

		/**
		 * This function hooks into the admin_menu WordPress hook to generate
		 * WordPress menu items. You define all the options and links you want
		 * to show to the admin in the WordPress sidebar.
		 */
		public function mo_wpns_widget_menu() {
			Mo2f_MenuItems::instance();
		}

		/**
		 * Settings options and calling required functions after register activation hook.
		 *
		 * @return void
		 */
		public function mo_wpns_activate() {
			global $wpns_db_queries, $mo2fdb_queries;
			$userid = wp_get_current_user()->ID;
			$wpns_db_queries->mo_plugin_activate();
			$mo2fdb_queries->mo_plugin_activate();
			add_option( 'mo2f_is_NC', 1 );
			add_option( 'mo2f_is_NNC', 1 );
			add_action( 'mo2f_auth_show_success_message', array( $this, 'mo2f_auth_show_success_message' ), 10, 1 );
			add_action( 'mo2f_auth_show_error_message', array( $this, 'mo2f_auth_show_error_message' ), 10, 1 );
			add_option( 'mo2f_onprem_admin', $userid );
			add_option( 'mo2f_handle_migration_status', 1 );
			add_option( 'mo2f_enable_backup_methods', true );
			add_option( 'mo2f_enabled_backup_methods', array( 'mo2f_reconfig_link_show', 'mo2f_back_up_codes' ) );
			add_option( 'mo_wpns_last_scan_time', time() );
			update_site_option( 'mo2f_mail_notify_new_release', 'on' );
			add_site_option( 'mo2f_mail_notify', 'on' );
			update_site_option( 'mo2f_redirect_url_for_users', 'redirect_all' );
			update_site_option( 'mo2f_graceperiod_action', 'enforce_2fa' );
			update_site_option( 'mo2f_disable_inline_registration', null );
			update_site_option( 'mo2f-enforcement-policy', 'mo2f-certain-roles-only' );
			update_site_option( 'mo2f_mail_notify_new_release', 1 );
			update_site_option( 'admin_email_address', wp_get_current_user()->user_email );
			if ( ! get_site_option( 'mo2f_activated_time' ) ) {
				add_site_option( 'mo2f_activated_time', time() );
			}
			$no_of2fa_users = $mo2fdb_queries->mo2f_get_no_of_2fa_users();
			if ( ! $no_of2fa_users ) {
				update_site_option( 'mo2f_plugin_redirect', true );
			}
			if ( is_multisite() ) {
				add_site_option( 'mo2fa_superadmin', 1 );
			}
			MO2f_Utility::mo2f_debug_file( 'Plugin activated' );
		}

		/**
		 * Settings options and calling required functions after register dectivation hook.
		 *
		 * @return void
		 */
		public function mo_wpns_deactivate() {
			if ( ! MO2F_IS_ONPREM ) {
				delete_site_option( 'mo2f_customerKey' );
				delete_site_option( 'mo2f_api_key' );
				delete_site_option( 'mo2f_customer_token' );
			}
			delete_site_option( 'mo2f_wizard_selected_method' );
			delete_site_option( 'mo2f_wizard_skipped' );
			$two_fa_settings = new Miniorange_Authentication();
			$two_fa_settings->mo2f_auth_deactivate();
		}
		/**
		 * Including css files on 2fa dashboard.
		 *
		 * @param int $hook - Hook suffix for the current admin page.
		 * @return void
		 */
		public function mo_wpns_settings_style( $hook ) {
			global $mo2f_dir_name;
			if ( strpos( $hook, 'page_mo_2fa' ) ) {
				wp_enqueue_style( 'mo_wpns_main_style', plugins_url( 'includes/css/mo2f-main.min.css', __FILE__ ), array(), MO2F_VERSION );
				wp_enqueue_style( 'mo_2fa_admin_settings_jquery_style', plugins_url( 'includes/css/jquery.ui.min.css', __FILE__ ), array(), MO2F_VERSION );
				wp_enqueue_style( 'mo_2fa_admin_settings_phone_style', plugins_url( 'includes/css/phone.min.css', __FILE__ ), array(), MO2F_VERSION );
				wp_enqueue_style( 'mo_wpns_admin_settings_style', plugins_url( 'includes/css/style_settings.min.css', __FILE__ ), array(), MO2F_VERSION );
				wp_enqueue_style( 'mo_wpns_admin_settings_phone_style', plugins_url( 'includes/css/phone.min.css', __FILE__ ), array(), MO2F_VERSION );
				wp_enqueue_style( 'mo_wpns_admin_settings_datatable_style', plugins_url( 'includes/css/jquery.dataTables.min.css', __FILE__ ), array(), MO2F_VERSION );
				wp_enqueue_style( 'mo_wpns_button_settings_style', plugins_url( 'includes/css/button_styles.min.css', __FILE__ ), array(), MO2F_VERSION );
				wp_enqueue_style( 'mo_wpns_popup_settings_style', plugins_url( 'includes/css/popup.min.css', __FILE__ ), array(), MO2F_VERSION );
				wp_enqueue_style( 'mo_2f_twofa_settings_style', plugins_url( 'includes/css/twofa_style_settings.min.css', __FILE__ ), array(), MO2F_VERSION );
				if ( file_exists( $mo2f_dir_name . 'includes' . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'basic-plan.min.css' ) ) {
					wp_enqueue_style( 'mo_2f_basic_plan_style', plugins_url( 'includes/css/basic-plan.min.css', __FILE__ ), array(), MO2F_VERSION );
				}
				if ( file_exists( $mo2f_dir_name . 'includes' . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'enterprise-plan.min.css' ) ) {
					wp_enqueue_style( 'mo_2f_enterprise_plan_style', plugins_url( 'includes/css/enterprise-plan.min.css', __FILE__ ), array(), MO2F_VERSION );
				}
				if ( file_exists( $mo2f_dir_name . 'includes' . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'all-inclusive-plan.min.css' ) ) {
					wp_enqueue_style( 'mo_2f_all_inclusive_plan_style', plugins_url( 'includes/css/all-inclusive-plan.min.css', __FILE__ ), array(), MO2F_VERSION );
				}
			}
		}
		/**
		 * Including javascript files on 2fa dashboard.
		 *
		 * @param int $hook - Hook suffix for the current admin page.
		 * @return void
		 */
		public function mo_wpns_settings_script( $hook ) {
			global $mo2f_dir_name;
			if ( strpos( $hook, 'page_mo_2fa' ) ) {
				wp_enqueue_script( 'mo_wpns_admin_settings_script', plugins_url( 'includes/js/settings_page.min.js', __FILE__ ), array( 'jquery' ), MO2F_VERSION, false );
				wp_localize_script(
					'mo_wpns_admin_settings_script',
					'settings_page_object',
					array(
						'nonce'               => wp_create_nonce( 'mo2f_settings_nonce' ),
						'contactus_nonce'     => wp_create_nonce( 'mo-two-factor-ajax-nonce' ),
						'whitelabeling_nonce' => wp_create_nonce( 'mo2f-white-labelling-ajax-nonce' ),
					)
				);
				wp_enqueue_script( 'mo_wpns_hide_warnings_script', plugins_url( 'includes/js/hide.min.js', __FILE__ ), array( 'jquery' ), MO2F_VERSION, false );
				wp_enqueue_script( 'mo_wpns_admin_settings_phone_script', plugins_url( 'includes/js/phone.min.js', __FILE__ ), array(), MO2F_VERSION, false );
				wp_enqueue_script( 'mo2f-script-handle', plugins_url( 'includes/js/mo2f-color-picker.js', __FILE__ ), array( 'wp-color-picker' ), MO2F_VERSION, true );
				wp_enqueue_script( 'mo_wpns_admin_datatable_script', plugins_url( 'includes/js/jquery.dataTables.min.js', __FILE__ ), array( 'jquery' ), MO2F_VERSION, false );
				wp_enqueue_script( 'mo_wpns_min_qrcode_script', plugins_url( '/includes/jquery-qrcode/jquery-qrcode.min.js', __FILE__ ), array(), MO2F_VERSION, false );
				wp_enqueue_script( 'jquery-ui-core' );
				wp_enqueue_script( 'jquery-ui-autocomplete' );
				wp_enqueue_script( 'mo_2fa_select2_script', plugins_url( '/includes/js/select2.min.js', __FILE__ ), array(), MO2F_VERSION, false );
				if ( file_exists( $mo2f_dir_name . 'includes' . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'basic-plan.min.js' ) ) {
					wp_enqueue_script( 'mo_2fa_basic_plan_script', plugins_url( '/includes/js/basic-plan.min.js', __FILE__ ), array(), MO2F_VERSION, false );
				}
				if ( file_exists( $mo2f_dir_name . 'includes' . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'enterprise-plan.min.js' ) ) {
					wp_enqueue_script( 'mo_2fa_enterprise_plan_script', plugins_url( '/includes/js/enterprise-plan.min.js', __FILE__ ), array(), MO2F_VERSION, false );
				}
				if ( file_exists( $mo2f_dir_name . 'includes' . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'all-inclusive-plan.min.js' ) ) {
					wp_enqueue_script( 'mo_2fa_all_inclusive_plan_script', plugins_url( '/includes/js/all-inclusive-plan.min.js', __FILE__ ), array(), MO2F_VERSION, false );
				}
			}
		}

		/**
		 * Handle reset users functionality from user's profile section.
		 *
		 * @param string[] $actions - An array of action links to be displayed.
		 * @param object   $user_object - object for the currently listed user.
		 * @return string[]
		 */
		public function miniorange_reset_users( $actions, $user_object ) {
			global $mo2fdb_queries;
			$mo2f_configured_2_f_a_method = $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_configured_2FA_method', $user_object->ID );
			if ( current_user_can( 'edit_users', $user_object->ID ) && $mo2f_configured_2_f_a_method ) {
				if ( get_current_user_id() !== $user_object->ID ) {
					$actions['miniorange_reset_users'] = "<a class='miniorange_reset_users' href='" . wp_nonce_url( "users.php?page=reset&action=reset_edit&amp;user_id=$user_object->ID", 'reset_edit', 'mo2f_reset-2fa' ) . "'>" . __( 'Reset 2 Factor', 'miniorange-2-factor-authentication' ) . '</a>';
				}
			} elseif ( miniorange_check_if_2fa_enabled_for_roles( $user_object->roles ) ) {
				$edit_link                         = esc_url(
					add_query_arg(
						'wp_http_referer',
						rawurlencode( isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '' ),
						get_edit_user_link( $user_object->ID )
					)
				);
				$actions['miniorange_reset_users'] = '<a href="' . $edit_link . '">' . __( 'Set 2 Factor', 'miniorange-2-factor-authentication' ) . '</a>';
			}
			return $actions;
		}
		/**
		 * Add UTM links for plugin actions on plugins.php.
		 *
		 * @param string[] $links - UTM links.
		 * @return string[]
		 */
		public function mo2f_add_plugin_action_link( $links ) {
			$is_lv_needed = apply_filters( 'mo2f_is_lv_needed', false );
			if ( ! $is_lv_needed ) {
				$custom['pro'] = sprintf(
					'<a href="%1$s" aria-label="%2$s" target="_blank" rel="noopener noreferrer" 
					style="color: #EF8354; font-weight: 700;" 
					onmouseover="this.style.color=\'#F5AD8F\';" 
					onmouseout="this.style.color=\'#EF8354\';"
					>%3$s</a>',
					esc_url(
						add_query_arg(
							array(
								'utm_content'  => 'pricing',
								'utm_campaign' => 'mo2f',
								'utm_medium'   => 'wp',
								'utm_source'   => 'wpf_plugin',
							),
							MoWpnsConstants::MO2F_PLUGINS_PAGE_URL . '/2-factor-authentication-for-wordpress-wp-2fa#pricing'
						)
					),
					esc_attr( 'Upgrade to Premium' ),
					esc_html( 'Upgrade to Premium' )
				);
			}

			$custom['docs'] = sprintf(
				'<a href="%1$s" target="_blank" aria-label="%2$s" rel="noopener noreferrer">%3$s</a>',
				esc_url(
					add_query_arg(
						array(
							'utm_content'  => 'docs',
							'utm_campaign' => 'mo2f',
							'utm_medium'   => 'wp',
							'utm_source'   => 'wpf_plugin',
						),
						MoWpnsConstants::MO2F_PLUGINS_PAGE_URL . '/wordpress-two-factor-authentication-setup-guides'
					)
				),
				esc_attr( 'miniorange.com documentation page' ),
				esc_html( 'Docs' )
			);

			return array_merge( $custom, (array) $links );
		}

		/**
		 * Add column in user profile section of WordPress.
		 *
		 * @param string[] $columns - The column header labels keyed by column ID.
		 * @return string[]
		 */
		public function mo2f_mapped_email_column( $columns ) {
			$columns['current_method'] = '2FA Method';
			return $columns;
		}
		/**
		 * Users page to reset 2FA for specific user
		 *
		 * @return void
		 */
		public function mo2f_reset_2fa_for_users_by_admin() {
			$nonce = wp_create_nonce( 'ResetTwoFnonce' );
			if ( ! isset( $_GET['mo2f_reset-2fa'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['mo2f_reset-2fa'] ) ), 'reset_edit' ) ) {
				wp_send_json( 'ERROR' );
			}
			if ( isset( $_GET['action'] ) && sanitize_text_field( wp_unslash( $_GET['action'] ) ) === 'reset_edit' ) {
				$user_id   = isset( $_GET['user_id'] ) ? sanitize_text_field( wp_unslash( $_GET['user_id'] ) ) : '';
				$user_info = get_userdata( $user_id );
				if ( is_numeric( $user_id ) && $user_info ) {
					?>
				<div class="wrap">
					<form method="post" name="reset2fa" id="reset2fa" action="<?php echo esc_url( 'users.php' ); ?>">
						<h1>Reset 2nd Factor</h1>

						<p>You have specified this user for reset:</p>

						<ul>
							<li>ID #<?php echo esc_html( $user_info->ID ); ?>: <?php echo esc_html( $user_info->user_login ); ?></li>
						</ul>
						<input type="hidden" name="userid" value="<?php echo esc_attr( $user_id ); ?>">
						<input type="hidden" name="miniorange_reset_2fa_option" value="mo_reset_2fa">
						<input type="hidden" name="nonce" value="<?php echo esc_attr( $nonce ); ?>">
						<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Confirm Reset"></p>
					</form>
				</div>

					<?php
				} else {
					?>
				<h2> Invalid User Id </h2>
					<?php
				}
			}
		}
		/**
		 * Function to save settings on 2FA reset.
		 *
		 * @return void
		 */
		public function miniorange_reset_save_settings() {
			$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'ResetTwoFnonce' ) ) {
				return;
			}
			if ( isset( $_POST['miniorange_reset_2fa_option'] ) && sanitize_text_field( wp_unslash( $_POST['miniorange_reset_2fa_option'] ) ) === 'mo_reset_2fa' ) {
				$user_id = isset( $_POST['userid'] ) && ! empty( $_POST['userid'] ) ? sanitize_text_field( wp_unslash( $_POST['userid'] ) ) : '';
				if ( ! empty( $user_id ) ) {
					$user_object  = wp_get_current_user();
					$capabilities = $user_object->allcaps;

					if ( current_user_can( 'edit_users' ) || ( isset( $capabilities['edit_users'] ) && $capabilities['edit_users'] ) ) {

						global $mo2fdb_queries;
						delete_user_meta( $user_id, 'mo2f_kba_challenge' );
						delete_user_meta( $user_id, 'mo2f_2FA_method_to_configure' );
						delete_user_meta( $user_id, MoWpnsConstants::SECURITY_QUESTIONS );
						delete_user_meta( $user_id, 'mo2f_chat_id' );
						delete_user_meta( $user_id, 'mo2f_whatsapp_num' );
						delete_user_meta( $user_id, 'mo2f_whatsapp_id' );
						delete_user_meta( $user_id, 'mo2f_configure_2FA' );
						$mo2fdb_queries->mo2f_delete_user_details( $user_id );
						delete_user_meta( $user_id, 'mo2f_2FA_method_to_test' );
						delete_site_option( 'mo2f_user_login_status_' . $user_id );
						delete_site_option( 'mo2f_grace_period_status_' . $user_id );
						delete_user_meta( $user_id, 'mo2f_user_profile_set' );
						delete_user_meta( $user_id, 'mo2f_grace_period_start_time' );
						delete_user_meta( $user_id, 'mo_backup_code_generated' );
						delete_user_meta( $user_id, 'mo2f_rba_device_details' );
					}
				}
			}
		}
		/**
		 * Get mapped user profile column
		 *
		 * @param string $value Row value to be shown.
		 * @param string $column_name Column name.
		 * @param  int    $user_id User ID of the details to be shown.
		 * @return string
		 */
		public function mo2f_mapped_email_column_content( $value, $column_name, $user_id ) {
			global $mo2fdb_queries;
			$current_method = $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_configured_2FA_method', $user_id );
			if ( ! $current_method ) {
				$check_if_skipped = $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_2factor_enable_2fa_byusers', $user_id );
				if ( ! (int) $check_if_skipped ) {
					$current_method = 'Two-Factor skipped by user';
				} else {
					$current_method = 'Not Registered for 2FA';
				}
			}
			if ( 'current_method' === $column_name ) {
				return $current_method;
			}
			return $value;
		}

		/**
		 * Check whether email should be sent after plugin update.
		 *
		 * @return void
		 */
		public function mo2f_mail_send() {
			if ( get_site_option( 'mo2f_mail_notify_new_release' ) === 'on' ) {
				if ( ! get_site_option( 'mo2f_feature_vers' ) ) {
					$this->mo2f_email_send();
				} else {
					$current_versions = get_site_option( 'mo2f_feature_vers' );

					if ( $current_versions < MoWpnsConstants::DB_FEATURE_MAIL ) {
						$this->mo2f_email_send();
					}
				}
			}
		}
		/**
		 * Function contains Email template to send to users after updating the plugin.
		 *
		 * @return void
		 */
		public function mo2f_email_send() {
			$subject  = 'miniOrange 2FA V' . MO2F_VERSION . ' | What\'s New?';
			$messages = mail_tem();
			$headers  = array( 'Content-Type: text/html; charset=UTF-8' );
			$email    = get_site_option( 'admin_email' );

			update_site_option( 'mo2f_feature_vers', MoWpnsConstants::DB_FEATURE_MAIL );
			if ( empty( $email ) ) {
				$user  = wp_get_current_user();
				$email = $user->user_email;
			}
			if ( is_email( $email ) ) {
				wp_mail( $email, $subject, $messages, $headers );
			}
		}

		/**
		 * Migrate whitelisted IPs from the custom `mo2f_whitelist_ips_table` to a WordPress site option.
		 *
		 * This function retrieves all the whitelisted IP data from the `mo2f_whitelist_ips_table`,
		 * updates the `mo2f_whitelisted_ips_data` site option with the valid IP data,
		 * deletes the old table, and sets an option to indicate that the migration is complete.
		 *
		 * @global wpdb $wpdb WordPress database abstraction object.
		 * @return void
		 */
		public function mo2f_migrate_whitelisted_ips_table() {
			global $wpdb;
			global $wpns_db_queries;
			global $mo2fdb_queries;
			if ( get_site_option( 'mo2f_whitelisted_ips_migrated' ) ) {
				return;
			}
			$old_network_whitelisted_ips_data = $wpns_db_queries->mo2f_get_old_table_data( 'mo2f_network_whitelisted_ips' );
			if ( ! empty( $old_network_whitelisted_ips_data ) ) {
				$whitelisted_ips = array();
				foreach ( $old_network_whitelisted_ips_data as $row ) {
					$ip_data           = array_filter(
						array(
							'id'                => $row['id'],
							'ip_address'        => $row['ip_address'],
							'created_timestamp' => $row['created_timestamp'],
							'plugin_path'       => $row['plugin_path'] ?? null,
						),
						function ( $value ) {
							return null !== $value;
						}
					);
					$whitelisted_ips[] = $ip_data;
				}
				update_site_option( 'mo2f_whitelisted_ips', $whitelisted_ips );
			}
			$mo2fdb_queries->mo2f_drop_table( 'mo2f_network_whitelisted_ips' );
			update_site_option( 'mo2f_whitelisted_ips_migrated', true );
		}

		/**
		 * Migrate blocked IPs from the custom `mo2f_network_blocked_ips` table to a WordPress site option.
		 *
		 * This function retrieves all the blocked IP data from the `mo2f_network_blocked_ips` table,
		 * filters the entries, and updates the `mo2f_blocked_ips_data` site option with the valid IP data.
		 * After the migration, it deletes the old table and sets an option to indicate that the migration is complete.
		 *
		 * @global wpdb $wpdb WordPress database abstraction object.
		 * @return void
		 */
		public function mo2f_migrate_network_blocked_ips_table() {
			global $wpns_db_queries;
			global $mo2fdb_queries;
			if ( get_site_option( 'mo2f_blocked_ips_migrated' ) ) {
				return;
			}
			$old_network_blocked_ips_data = $wpns_db_queries->mo2f_get_old_table_data( 'mo2f_network_blocked_ips' );
			if ( ! empty( $old_network_blocked_ips_data ) ) {
				$blocked_ips = array();
				foreach ( $old_network_blocked_ips_data as $row ) {
					$ip_data       = array_filter(
						array(
							'id'                => $row['id'],
							'ip_address'        => $row['ip_address'],
							'reason'            => $row['reason'],
							'blocked_for_time'  => $row['blocked_for_time'],
							'created_timestamp' => $row['created_timestamp'],
						),
						function ( $value ) {
							return null !== $value;
						}
					);
					$blocked_ips[] = $ip_data;
				}
				update_site_option( 'mo2f_blocked_ips_data', $blocked_ips );
			}
			$mo2fdb_queries->mo2f_drop_table( 'mo2f_network_blocked_ips' );
			update_site_option( 'mo2f_blocked_ips_migrated', true );
		}

		/**
		 * Migrate user details from the custom `mo2f_user_details` table to the WordPress `usermeta` table.
		 *
		 * @global wpdb $wpdb WordPress database abstraction object.
		 * @return void
		 */
		public function mo2f_migrate_user_details() {
			global $wpdb;
			global $mo2fdb_queries;
			global $wpns_db_queries;
			if ( get_site_option( 'mo2f_user_details_migrated' ) ) {
				return;
			}

			$old_user_details_data = $wpns_db_queries->mo2f_get_old_table_data( 'mo2f_user_details' );

			if ( ! empty( $old_user_details_data ) ) {
				foreach ( $old_user_details_data as $row ) {
					$user_id               = $row['user_id'];
					$configured_2fa_method = ! empty( $row['mo2f_configured_2FA_method'] ) ? strtoupper( $row['mo2f_configured_2FA_method'] ) : '';
					$configured_2fa_map    = array(
						'SECURITY QUESTIONS' => 'KBA',
						'EMAIL VERIFICATION' => 'OUT OF BAND EMAIL',
						'OTP OVER SMS'       => 'SMS',
						'OTP OVER EMAIL'     => 'EMAIL',
					);
					if ( isset( $configured_2fa_map [ $configured_2fa_method ] ) ) {
						$configured_2fa_method = $configured_2fa_map [ $configured_2fa_method ];
					}
					$user_data = array_filter(
						array(
							'mo2f_OTPOverSMS_config_status'                     => $row['mo2f_OTPOverSMS_config_status'] ?? null,
							'mo2f_miniOrangePushNotification_config_status'     => $row['mo2f_miniOrangePushNotification_config_status'] ?? null,
							'mo2f_miniOrangeQRCodeAuthentication_config_status' => $row['mo2f_miniOrangeQRCodeAuthentication_config_status'] ?? null,
							'mo2f_miniOrangeSoftToken_config_status'            => $row['mo2f_miniOrangeSoftToken_config_status'] ?? null,
							'mo2f_AuthyAuthenticator_config_status'             => $row['mo2f_AuthyAuthenticator_config_status'] ?? null,
							'mo2f_EmailVerification_config_status'              => $row['mo2f_EmailVerification_config_status'] ?? null,
							'mo2f_SecurityQuestions_config_status'              => $row['mo2f_SecurityQuestions_config_status'] ?? null,
							'mo2f_GoogleAuthenticator_config_status'            => $row['mo2f_GoogleAuthenticator_config_status'] ?? null,
							'mo2f_OTPOverEmail_config_status'                   => $row['mo2f_OTPOverEmail_config_status'] ?? null,
							'mo2f_OTPOverTelegram_config_status'                => $row['mo2f_OTPOverTelegram_config_status'] ?? null,
							'mo2f_OTPOverWhatsapp_config_status'                => $row['mo2f_OTPOverWhatsapp_config_status'] ?? null,
							'mo2f_DuoAuthenticator_config_status'               => $row['mo2f_DuoAuthenticator_config_status'] ?? null,
							'mobile_registration_status'                        => $row['mobile_registration_status'] ?? null,
							'mo2f_2factor_enable_2fa_byusers'                   => $row['mo2f_2factor_enable_2fa_byusers'] ?? null,
							'mo2f_configured_2FA_method'                        => $configured_2fa_method,
							'mo2f_user_phone'                                   => $row['mo2f_user_phone'] ?? null,
							'mo2f_user_whatsapp'                                => $row['mo2f_user_whatsapp'] ?? null,
							'mo2f_user_email'                                   => $row['mo2f_user_email'] ?? null,
							'user_registration_with_miniorange'                 => $row['user_registration_with_miniorange'] ?? null,
							'mo_2factor_user_registration_status'               => $row['mo_2factor_user_registration_status'] ?? null,
						),
						function ( $value ) {
							return null !== $value;
						}
					);
					update_user_meta( $user_id, 'mo2f_user_2fa_data', $user_data );        }
			}
			$mo2fdb_queries->mo2f_drop_table( 'mo2f_user_details' );
			update_site_option( 'mo2f_user_details_migrated', true );
		}
		/**
		 * Drop table wpns_attack_logs and mo2f_network_email_sent_audit.
		 *
		 * @return void
		 */
		public function mo2f_drop_wpns_attack_logs_and_network_email_sent_audit() {
			global $mo2fdb_queries;
			if ( get_site_option( 'mo2f_wpns_attack_logs_and_mo2f_network_email_sent_audit_dropped' ) ) {
				return;
			}
			$mo2fdb_queries->mo2f_drop_table( 'wpns_attack_logs' );
			$mo2fdb_queries->mo2f_drop_table( 'mo2f_network_email_sent_audit' );
			update_site_option( 'mo2f_wpns_attack_logs_and_mo2f_network_email_sent_audit_dropped', true );
		}
	}
}
require_once 'class-mo2f-classloader.php';
$class_loader = new Mo2f_Classloader( 'TwoFA', realpath( __DIR__ . DIRECTORY_SEPARATOR . '..' ) );
$class_loader->mo2f_autoload();
new Miniorange_TwoFactor();
Mo2fInit::instance();

?>
