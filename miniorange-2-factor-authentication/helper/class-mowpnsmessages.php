<?php
/**
 * This file has all the notifications that are shown throughout the plugin.
 *
 * @package miniorange-2-factor-authentication/helper/
 */

namespace TwoFA\Helper;

use TwoFA\Traits\Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! class_exists( 'MoWpnsMessages' ) ) {
	/**
	 * This Class has all the notifications that are shown throughout the plugin.
	 */
	class MoWpnsMessages {

		use Instance;

		/**
		 * Constructor.
		 */
		public function __construct() {
			add_action( 'admin_notices', array( $this, 'mo2f_show_message_strip' ) );
			if ( ! self::$filter_hooked ) {
				add_filter( 'gettext', array( __CLASS__, 'maybe_translate_key' ), 10, 3 );
				self::$filter_hooked = true;
			}
		}

		const EMAIL_SAVED            = 'EMAIL_SAVED';
		const IP_ALREADY_WHITELISTED = 'IP_ALREADY_WHITELISTED';
		const IP_IN_WHITELISTED      = 'IP_IN_WHITELISTED';
		const IP_WHITELISTED         = 'IP_WHITELISTED';

		// Advanced security.
		const INVALID_IP_FORMAT = 'INVALID_IP_FORMAT';
		const DEACTIVATE_PLUGIN = 'DEACTIVATE_PLUGIN';

		// common messages.
		const GET_BACKUP_CODES       = 'GET_BACKUP_CODES';
		const REG_SUCCESS            = 'REG_SUCCESS';
		const ACCOUNT_EXISTS         = 'ACCOUNT_EXISTS';
		const ALREADY_ACCOUNT_EXISTS = 'ALREADY_ACCOUNT_EXISTS';

		const INVALID_OTP           = 'INVALID_OTP';
		const INVALID_PHONE         = 'INVALID_PHONE';
		const INVALID_INPUT         = 'INVALID_INPUT';
		const INVALID_CREDS         = 'INVALID_CREDS';
		const INVALID_USERNAME      = 'INVALID_USERNAME';
		const ALL_ENABLED           = 'ALL_ENABLED';
		const IP_BLOCK_RANGE_ADDED  = 'IP_BLOCK_RANGE_ADDED';
		const ALL_DISABLED          = 'ALL_DISABLED';
		const LOGIN_ENABLE          = 'LOGIN_ENABLE';
		const DELETE_FILE           = 'DELETE_FILE';
		const NOT_ADMIN             = 'NOT_ADMIN';
		const UNBLOCK_CONFIRMATION  = 'UNBLOCK_CONFIRMATION';
		const PHONE_NUMBER_MISMATCH = 'PHONE_NUMBER_MISMATCH';
		const CHAT_ID_MISMATCH      = 'CHAT_ID_MISMATCH';

		const WHITELIST_SELF       = 'WHITELIST_SELF';
		const ADMIN_IP_WHITELISTED = 'ADMIN_IP_WHITELISTED';

		const LOW_SMS_TRANSACTIONS   = 'LOW_SMS_TRANSACTIONS';
		const LOW_EMAIL_TRANSACTIONS = 'LOW_EMAIL_TRANSACTIONS';

		// Two FA Settings.
		const ENABLE        = 'ENABLE';
		const DISABLE       = 'DISABLE';
		const PLUGIN_LOG    = 'PLUGIN_LOG';
		const TWO_FA_PROMPT = 'TWO_FA_PROMPT';
		const TWO_FA        = 'TWO_FA';
		const MULTI_FA      = 'MULTI_FA';
		const INLINE_2FA    = 'INLINE_2FA';

		const FILE_NOT_EXISTS                            = 'FILE_NOT_EXISTS';
		const QUERY_SUBMISSION_ERROR                     = 'QUERY_SUBMISSION_ERROR';
		const FEEDBACK_APPRECIATION                      = 'FEEDBACK_APPRECIATION';
		const TEMPLATE_SAVED                             = 'TEMPLATE_SAVED';
		const NOTIFY_ON_UNUSUAL_ACTIVITY                 = 'NOTIFY_ON_UNUSUAL_ACTIVITY';
		const DONOT_NOTIFY_ON_UNUSUAL_ACTIVITY           = 'DONOT_NOTIFY_ON_UNUSUAL_ACTIVITY';
		const DONOT_NOTIFY_ON_IP_BLOCKED                 = 'DONOT_NOTIFY_ON_IP_BLOCKED';
		const NOTIFY_ON_IP_BLOCKED                       = 'NOTIFY_ON_IP_BLOCKED';
		const INVALID_EMAIL                              = 'INVALID_EMAIL';
		const ADV_BLOCK_DISABLE                          = 'ADV_BLOCK_DISABLE';
		const ADV_BLOCK_ENABLE                           = 'ADV_BLOCK_ENABLE';
		const PASS_LENGTH                                = 'PASS_LENGTH';
		const REQUIRED_FIELDS                            = 'REQUIRED_FIELDS';
		const INVALID_IP                                 = 'INVALID_IP';
		const PASS_MISMATCH                              = 'PASS_MISMATCH';
		const RESET_PASS                                 = 'RESET_PASS';
		const SUPPORT_FORM_VALUES                        = 'SUPPORT_FORM_VALUES';
		const EXPECTED_GRACE_PERIOD_VALUE                = 'EXPECTED_GRACE_PERIOD_VALUE';
		const EXPECTED_MAX_SESSIONS                      = 'EXPECTED_MAX_SESSIONS';
		const EXPECTED_MAX_SESSION_TIME                  = 'EXPECTED_MAX_SESSION_TIME';
		const SETTINGS_SAVED_SUCCESSFULLY                = 'SETTINGS_SAVED_SUCCESSFULLY';
		const EMPTY_LOGIN_FIELDS                         = 'EMPTY_LOGIN_FIELDS';
		const SELECT_ANY_AUTHENTICATION_METHOD           = 'SELECT_ANY_AUTHENTICATION_METHOD';
		const EXPECTED_RBA_EXPIRY                        = 'EXPECTED_RBA_EXPIRY';
		const EXPECTED_RBA_DEVICE_LIMIT                  = 'EXPECTED_RBA_DEVICE_LIMIT';
		const RBA_CANNOT_BE_ENABLED_ERROR                = 'RBA_CANNOT_BE_ENABLED_ERROR';
		const REMEMBER_IP_CANNOT_BE_ENABLED_ERROR        = 'REMEMBER_IP_CANNOT_BE_ENABLED_ERROR';
		const FILE_UPLOADED_SUCCESSFULLY                 = 'FILE_UPLOADED_SUCCESSFULLY';
		const INVALID_FILE_FORMAT                        = 'INVALID_FILE_FORMAT';
		const TOO_LARGE_FILE_SIZE                        = 'TOO_LARGE_FILE_SIZE';
		const INVALIDE_REDIRECTION_URL                   = 'INVALIDE_REDIRECTION_URL';
		const RESET_SETTINGS_SUCCESSFULLY                = 'RESET_SETTINGS_SUCCESSFULLY';
		const BACKUPCODE_VALIDATED                       = 'BACKUPCODE_VALIDATED';
		const INVALID_BACKUPCODE                         = 'INVALID_BACKUPCODE';
		const ENTER_BACKUP_CODES                         = 'ENTER_BACKUP_CODES';
		const EMAIL_TEMPLATE_SAVED                       = 'EMAIL_TEMPLATE_SAVED';
		const EMAIL_TEMPLATE_RESET                       = 'EMAIL_TEMPLATE_RESET';
		const SUPPORT_FORM_SENT                          = 'SUPPORT_FORM_SENT';
		const QUERY_SUBMITTED                            = 'QUERY_SUBMITTED';
		const SUPPORT_FORM_ERROR                         = 'SUPPORT_FORM_ERROR';
		const DUO_INVALID_REQ                            = 'DUO_INVALID_REQ';
		const VERIFY_CHAT_ID                             = 'VERIFY_CHAT_ID';
		const DUO_ACCOUNT_INACTIVE                       = 'DUO_ACCOUNT_INACTIVE';
		const DUO_USER_EXISTS                            = 'DUO_USER_EXISTS';
		const DUO_SERVER_NOT_RESPONDING                  = 'DUO_SERVER_NOT_RESPONDING';
		const INVALID_CREDENTIALS                        = 'INVALID_CREDENTIALS';
		const FIELD_MISSING                              = 'FIELD_MISSING';
		const UNIQUE_QUESTION                            = 'UNIQUE_QUESTION';
		const OTP_EXPIRED                                = 'OTP_EXPIRED';
		const SETUP_2FA                                  = 'SETUP_2FA';
		const INVALID_REQ                                = 'INVALID_REQ';
		const INVALID_ENTRY                              = 'INVALID_ENTRY';
		const INVALID_EMAIL_VER_REQ                      = 'INVALID_EMAIL_VER_REQ';
		const COMPLETED_TEST                             = 'COMPLETED_TEST';
		const SET_AS_2ND_FACTOR                          = 'SET_AS_2ND_FACTOR';
		const VALIDATE_DUO                               = 'VALIDATE_DUO';
		const ERROR_DURING_USER_REGISTRATION             = 'ERROR_DURING_USER_REGISTRATION';
		const ERROR_DURING_PROCESS_EMAIL                 = 'ERROR_DURING_PROCESS_EMAIL';
		const ERROR_DURING_PROCESS                       = 'ERROR_DURING_PROCESS';
		const ERROR_IN_SENDING_PN                        = 'ERROR_IN_SENDING_PN';
		const SCAN_QR_CODE                               = 'SCAN_QR_CODE';
		const GET_FREE_TRANSACTIONS                      = 'GET_FREE_TRANSACTIONS';
		const NEW_OTP_SENT                               = 'NEW_OTP_SENT';
		const OTP_SENT                                   = 'OTP_SENT';
		const ENTER_YOUR_EMAIL_PASSWORD                  = 'ENTER_YOUR_EMAIL_PASSWORD';
		const INTERNET_CONNECTIVITY_ERROR                = 'INTERNET_CONNECTIVITY_ERROR';
		const USED_ALL_BACKUP_CODES                      = 'USED_ALL_BACKUP_CODES';
		const BACKUP_CODES_SENT_SUCCESS                  = 'BACKUP_CODES_SENT_SUCCESS';
		const BACKUP_CODE_INVALID_REQUEST                = 'BACKUP_CODE_INVALID_REQUEST';
		const BACKUP_CODE_SENT_ERROR                     = 'BACKUP_CODE_SENT_ERROR';
		const BACKUP_CODE_DOMAIN_LIMIT_REACH             = 'BACKUP_CODE_DOMAIN_LIMIT_REACH';
		const BACKUP_CODE_LIMIT_REACH                    = 'BACKUP_CODE_LIMIT_REACH';
		const BACKUP_CODE_ALL_USED                       = 'BACKUP_CODE_ALL_USED';
		const BACKUP_CODE_INTERNET_ISSUE                 = 'BACKUP_CODE_INTERNET_ISSUE';
		const ANSWER_SECURITY_QUESTIONS                  = 'ANSWER_SECURITY_QUESTIONS';
		const ERROR_WHILE_SAVING_KBA                     = 'ERROR_WHILE_SAVING_KBA';
		const SETTINGS_SAVED                             = 'SETTINGS_SAVED';
		const ERROR_IN_SENDING_EMAIL                     = 'ERROR_IN_SENDING_EMAIL';
		const RESENT_OTP                                 = 'RESENT_OTP';
		const INVALID_ANSWERS                            = 'INVALID_ANSWERS';
		const ERROR_FETCHING_QUESTIONS                   = 'ERROR_FETCHING_QUESTIONS';
		const RESET_DUO_CONFIGURATON                     = 'RESET_DUO_CONFIGURATON';
		const TRANSIENT_ACTIVE                           = 'TRANSIENT_ACTIVE';
		const TEST_GAUTH_METHOD                          = 'TEST_GAUTH_METHOD';
		const ERROR_WHILE_VALIDATING_OTP                 = 'ERROR_WHILE_VALIDATING_OTP';
		const PUSH_NOTIFICATION_SENT                     = 'PUSH_NOTIFICATION_SENT';
		const ERROR_IN_SENDING_OTP_ONPREM                = 'ERROR_IN_SENDING_OTP_ONPREM';
		const ERROR_IN_SENDING_OTP                       = 'ERROR_IN_SENDING_OTP';
		const ENTER_OTP                                  = 'ENTER_OTP';
		const VERIFY_YOURSELF                            = 'VERIFY_YOURSELF';
		const SET_THE_2FA                                = 'SET_THE_2FA';
		const ENTER_VALUE                                = 'ENTER_VALUE';
		const REGISTER_WITH_MO                           = 'REGISTER_WITH_MO';
		const AUTHENTICATION_FAILED                      = 'AUTHENTICATION_FAILED';
		const ERROR_IN_SENDING_OTP_CAUSES                = 'ERROR_IN_SENDING_OTP_CAUSES';
		const ACCOUNT_CREATED                            = 'ACCOUNT_CREATED';
		const ACCEPT_LINK_TO_VERIFY_EMAIL                = 'ACCEPT_LINK_TO_VERIFY_EMAIL';
		const VERIFICATION_EMAIL_SENT                    = 'VERIFICATION_EMAIL_SENT';
		const SET_2FA                                    = 'SET_2FA';
		const TEST_AUTHY_2FA                             = 'TEST_AUTHY_2FA';
		const ONLY_DIGITS_ALLOWED                        = 'ONLY_DIGITS_ALLOWED';
		const ERROR_WHILE_VALIDATING_USER                = 'ERROR_WHILE_VALIDATING_USER';
		const SERVER_TIME_SYNC                           = 'SERVER_TIME_SYNC';
		const APP_TIME_SYNC                              = 'APP_TIME_SYNC';
		const ENTER_VALID_ENTRY                          = 'ENTER_VALID_ENTRY';
		const ERROR_WHILE_SAVING_SETTINGS                = 'ERROR_WHILE_SAVING_SETTINGS';
		const DISABLED_2FA                               = 'DISABLED_2FA';
		const DENIED_DUO_REQUEST                         = 'DENIED_DUO_REQUEST';
		const DENIED_REQUEST                             = 'DENIED_REQUEST';
		const REGISTRATION_SUCCESS                       = 'REGISTRATION_SUCCESS';
		const ACCOUNT_REMOVED                            = 'ACCOUNT_REMOVED';
		const LOGIN_WITH_2ND_FACTOR                      = 'LOGIN_WITH_2ND_FACTOR';
		const ERROR_CREATE_ACC_OTP                       = 'ERROR_CREATE_ACC_OTP';
		const CLICK_HERE                                 = 'CLICK_HERE';
		const PHONE_NOT_CONFIGURED                       = 'PHONE_NOT_CONFIGURED';
		const CONFIGURE_2FA                              = 'CONFIGURE_2FA';
		const ADD_MINIORANGE_ACCOUNT                     = 'ADD_MINIORANGE_ACCOUNT';
		const ACCOUNT_ALREADY_EXISTS                     = 'ACCOUNT_ALREADY_EXISTS';
		const INVALID_REQUEST                            = 'INVALID_REQUEST';
		const PASS_LENGTH_LIMIT                          = 'PASS_LENGTH_LIMIT';
		const SENT_OTP                                   = 'SENT_OTP';
		const SOMETHING_WENT_WRONG                       = 'SOMETHING_WENT_WRONG';
		const ENTER_SENT_OTP                             = 'ENTER_SENT_OTP';
		const USER_LIMIT_EXCEEDED                        = 'USER_LIMIT_EXCEEDED';
		const USER_PROFILE_SETUP_SMTP                    = 'USER_PROFILE_SETUP_SMTP';
		const SESSION_LIMIT_REACHED                      = 'SESSION_LIMIT_REACHED';
		const EMAIL_LABEL                                = 'EMAIL_LABEL';
		const LOGIN_WITH_TWO_FACTOR                      = 'LOGIN_WITH_TWO_FACTOR';
		const TWOFA_NOT_ENABLED                          = 'TWOFA_NOT_ENABLED';
		const TWOFA_NOT_CONFIGURED                       = 'TWOFA_NOT_CONFIGURED';
		const PASSWORDLESS_LOGIN_CANNOT_BE_ENABLED_ERROR = 'PASSWORDLESS_LOGIN_CANNOT_BE_ENABLED_ERROR';
		const GET_YOUR_PLAN_UPGRADED                     = 'GET_YOUR_PLAN_UPGRADED';
		const REMEMBER_IP_CONSENT_MESSAGE                = 'REMEMBER_IP_CONSENT_MESSAGE';
		const INVALID_SESSION                            = 'INVALID_SESSION';

		/**
		 * Cached translated messages.
		 *
		 * @var array|null
		 */
		private static $messages = null;

		/**
		 * Track if gettext filter is registered.
		 *
		 * @var bool
		 */
		private static $filter_hooked = false;

		/**
		 * Guard against recursive translation lookups.
		 *
		 * @var bool
		 */
		private static $is_translating = false;

		/**
		 * Retrieve translated messages mapped by key.
		 *
		 * @return array
		 */
		private static function get_messages() {
			if ( null === self::$messages ) {
				self::$is_translating = true;
				try {
					self::$messages = array(
						self::EMAIL_SAVED                 => __( 'Email ID saved successfully.', 'miniorange-2-factor-authentication' ),
						self::IP_ALREADY_WHITELISTED      => __( 'IP Address is already Whitelisted.', 'miniorange-2-factor-authentication' ),
						self::IP_IN_WHITELISTED           => __( 'IP Address is Whitelisted. Please remove it from the whitelisted list.', 'miniorange-2-factor-authentication' ),
						self::IP_WHITELISTED              => __( 'IP has been whitelisted successfully', 'miniorange-2-factor-authentication' ),
						self::INVALID_IP_FORMAT           => __( 'Please enter Valid IP Range.', 'miniorange-2-factor-authentication' ),
						self::DEACTIVATE_PLUGIN           => __( 'Plugin deactivated successfully', 'miniorange-2-factor-authentication' ),
						self::GET_BACKUP_CODES            => __( "<div class='mo2f-custom-notice notice notice-warning backupcodes-notice'><p><p class='notice-message'><b>Please download backup codes using the 'Get backup codes' button to avoid getting locked out. Backup codes will be emailed as well as downloaded.</b></p><button class='backup_codes_dismiss notice-button'><i>NEVER SHOW AGAIN</i></button></p></div>", 'miniorange-2-factor-authentication' ),
						self::REG_SUCCESS                 => __( 'Your account has been retrieved successfully.', 'miniorange-2-factor-authentication' ),
						self::ACCOUNT_EXISTS              => __( 'You already have an account with miniOrange. Please enter a valid password.', 'miniorange-2-factor-authentication' ),
						self::ALREADY_ACCOUNT_EXISTS      => __( 'You already have an account with miniOrange. Please click on "Already have an account?" to continue.', 'miniorange-2-factor-authentication' ),
						self::INVALID_OTP                 => __( 'Invalid one time passcode. Please enter a valid passcode.', 'miniorange-2-factor-authentication' ),
						self::INVALID_PHONE               => __( 'Please enter a valid phone number.', 'miniorange-2-factor-authentication' ),
						self::INVALID_INPUT               => __( 'Please enter a valid value in the OTP length field.', 'miniorange-2-factor-authentication' ),
						self::INVALID_CREDS               => __( 'Invalid username or password. Please try again.', 'miniorange-2-factor-authentication' ),
						self::INVALID_USERNAME            => __( 'Invalid username or email.', 'miniorange-2-factor-authentication' ),
						self::ALL_ENABLED                 => __( 'All Website security features are available.', 'miniorange-2-factor-authentication' ),
						self::IP_BLOCK_RANGE_ADDED        => __( 'Blocked IP range added successfully!', 'miniorange-2-factor-authentication' ),
						self::ALL_DISABLED                => __( 'All Website security features are disabled.', 'miniorange-2-factor-authentication' ),
						self::LOGIN_ENABLE                => __( 'Login security and spam protection features are available. Configure it in the Login and Spam tab.', 'miniorange-2-factor-authentication' ),
						self::DELETE_FILE                 => __( 'Someone has deleted the backup by going to directory please refreash the page', 'miniorange-2-factor-authentication' ),
						self::NOT_ADMIN                   => __( 'You are not a admin. Only admin can download', 'miniorange-2-factor-authentication' ),
						self::UNBLOCK_CONFIRMATION        => __( 'Are you sure you want to unblock this user?', 'miniorange-2-factor-authentication' ),
						self::PHONE_NUMBER_MISMATCH       => __( "The current phone number is different from the phone number on which OTP is sent. Please enter the phone number and click on 'Validate OTP' button again.", 'miniorange-2-factor-authentication' ),
						self::CHAT_ID_MISMATCH            => __( "The current Chat ID is different from the Chat ID on which OTP is sent. Please enter the Chat ID and click on 'Verify' button again.", 'miniorange-2-factor-authentication' ),
						self::WHITELIST_SELF              => __( "<div class='mo2f-custom-notice notice notice-warning whitelistself-notice MOWrn'><p><p class='notice-message'>It looks like you have not whitelisted your IP. Whitelist your IP as you can get blocked from your site.</p><button class='whitelist_self notice-button'><i>WhiteList</i></button></p></div>", 'miniorange-2-factor-authentication' ),
						self::ADMIN_IP_WHITELISTED        => __( "<div class='mo2f-custom-notice notice notice-warning MOWrn'>\n                                                       <p class='notice-message'>Your IP has been whitelisted. In the IP Blocking settings, you can remove your IP address from the whitelist if you want to do so.</p>\n                                                   </div>", 'miniorange-2-factor-authentication' ),
						self::LOW_SMS_TRANSACTIONS        => sprintf(
							/* translators: %1$s: plugin url, %2$s: recharge link */
							__(
								"<div class='mo2f-custom-notice notice notice-warning low_sms-notice MOWrn'><div class='flex gap-mo-3 w-full'><p class='notice-message'><img src='%1\$s/includes/images/miniorange_icon.png'>&nbsp;&nbsp;You have left very few SMS transaction. We advise you to recharge or change 2FA method before you have no SMS left.</p><a class='notice-button' href='%2\$s' target='_blank' >RECHARGE</a><a class='notice-button' href='admin.php?page=mo_2fa_my_account&subpage=setupyour2fa' id='setuptwofa_redirect' >SET UP ANOTHER 2FA</a><button class='sms_low_dismiss notice-button' ><i>DISMISS</i></button><button class='sms_low_dismiss_always notice-button'><i>NEVER SHOW AGAIN</i></button></div></div>",
								'miniorange-2-factor-authentication'
							),
							MO2F_PLUGIN_URL,
							MoWpnsConstants::RECHARGELINK
						),
						self::LOW_EMAIL_TRANSACTIONS      => sprintf(
							/* translators: %1$s: plugin url, %2$s: recharge link */
							__(
								"<div class='mo2f-custom-notice notice notice-warning low_email-notice MOWrn'><div class='flex gap-mo-3 w-full'><p class='notice-message'><img src='%1\$s/includes/images/miniorange_icon.png'>&nbsp;&nbsp;You have left very few Email transaction. We advise you to recharge or change 2FA method before you have no Email left.</p><a class='notice-button' href='%2\$s' target='_blank' >RECHARGE</a><a class='notice-button' href='admin.php?page=mo_2fa_my_account&subpage=setupyour2fa' id='setuptwofa_redirect' >SET UP ANOTHER 2FA</a><button class='email_low_dismiss notice-button' ><i>DISMISS</i></button><button class='email_low_dismiss_always notice-button'><i>NEVER SHOW AGAIN</i></button></div></div>",
								'miniorange-2-factor-authentication'
							),
							MO2F_PLUGIN_URL,
							MoWpnsConstants::RECHARGELINK
						),
						self::ENABLE                      => __( ' has been enabled', 'miniorange-2-factor-authentication' ),
						self::DISABLE                     => __( ' has been disabled', 'miniorange-2-factor-authentication' ),
						self::PLUGIN_LOG                  => __( 'Plugin log', 'miniorange-2-factor-authentication' ),
						self::TWO_FA_PROMPT               => __( '2FA prompt on WP login', 'miniorange-2-factor-authentication' ),
						self::TWO_FA                      => __( '2-factor authentication', 'miniorange-2-factor-authentication' ),
						self::MULTI_FA                    => __( 'Login with any configured method', 'miniorange-2-factor-authentication' ),
						self::INLINE_2FA                  => __( 'User enrollment for 2FA', 'miniorange-2-factor-authentication' ),
						self::FILE_NOT_EXISTS             => __( 'File does not exist.', 'miniorange-2-factor-authentication' ),
						self::QUERY_SUBMISSION_ERROR      => __( 'Error while submitting the query.', 'miniorange-2-factor-authentication' ),
						self::FEEDBACK_APPRECIATION       => __( 'Thank you for the feedback.', 'miniorange-2-factor-authentication' ),
						self::TEMPLATE_SAVED              => __( 'Email template saved.', 'miniorange-2-factor-authentication' ),
						self::NOTIFY_ON_UNUSUAL_ACTIVITY  => __( 'Email notification is enabled for user for unusual activities.', 'miniorange-2-factor-authentication' ),
						self::DONOT_NOTIFY_ON_UNUSUAL_ACTIVITY => __( 'Email notification is disabled for user for unusual activities.', 'miniorange-2-factor-authentication' ),
						self::DONOT_NOTIFY_ON_IP_BLOCKED  => __( 'Email notification is disabled for Admin.', 'miniorange-2-factor-authentication' ),
						self::NOTIFY_ON_IP_BLOCKED        => __( 'Email notification is enabled for Admin.', 'miniorange-2-factor-authentication' ),
						self::INVALID_EMAIL               => __( 'Please enter valid Email ID.', 'miniorange-2-factor-authentication' ),
						self::ADV_BLOCK_DISABLE           => __( 'Advanced blocking features are disabled.', 'miniorange-2-factor-authentication' ),
						self::ADV_BLOCK_ENABLE            => __( 'Advanced blocking features are available. Configure it in the Advanced blocking tab.', 'miniorange-2-factor-authentication' ),
						self::PASS_LENGTH                 => __( 'Please Choose a password with minimum length 6.', 'miniorange-2-factor-authentication' ),
						self::REQUIRED_FIELDS             => __( 'Please enter all the required fields.', 'miniorange-2-factor-authentication' ),
						self::INVALID_IP                  => __( 'The IP address you entered is not valid or the IP Range is not valid.', 'miniorange-2-factor-authentication' ),
						self::PASS_MISMATCH               => __( 'Password and Confirm Password do not match.', 'miniorange-2-factor-authentication' ),
						self::RESET_PASS                  => __( 'You password has been reset successfully and sent to your registered email. Please check your mailbox.', 'miniorange-2-factor-authentication' ),
						self::SUPPORT_FORM_VALUES         => __( 'Please submit your query along with email.', 'miniorange-2-factor-authentication' ),
						self::EXPECTED_GRACE_PERIOD_VALUE => __( 'Please enter grace period value greater than 0', 'miniorange-2-factor-authentication' ),
						self::EXPECTED_MAX_SESSIONS       => __( 'Please enter maximum sessions value greater than 0', 'miniorange-2-factor-authentication' ),
						self::EXPECTED_MAX_SESSION_TIME   => __( 'Please enter maximum sessions time greater than 0', 'miniorange-2-factor-authentication' ),
						self::SETTINGS_SAVED_SUCCESSFULLY => __( 'Settings saved successfully!', 'miniorange-2-factor-authentication' ),
						self::EMPTY_LOGIN_FIELDS          => __( 'One or more fields are empty.', 'miniorange-2-factor-authentication' ),
						self::SELECT_ANY_AUTHENTICATION_METHOD => __( 'Please select an authentication method before saving.', 'miniorange-2-factor-authentication' ),
						self::EXPECTED_RBA_EXPIRY         => __( 'Please enter remember device expiry value greater than 0', 'miniorange-2-factor-authentication' ),
						self::EXPECTED_RBA_DEVICE_LIMIT   => __( 'Please enter maximum remembered device limit greater than 0', 'miniorange-2-factor-authentication' ),
						self::RBA_CANNOT_BE_ENABLED_ERROR => __( 'Please disable the Passwordless Login and Remember IP features in order to enable this feature.', 'miniorange-2-factor-authentication' ),
						self::REMEMBER_IP_CANNOT_BE_ENABLED_ERROR => __( 'Please disable the Remember Device and Passwordless Login features in order to enable this feature.', 'miniorange-2-factor-authentication' ),
						self::FILE_UPLOADED_SUCCESSFULLY  => __( 'File uploaded successfully!', 'miniorange-2-factor-authentication' ),
						self::INVALID_FILE_FORMAT         => __( 'Invalid file format.', 'miniorange-2-factor-authentication' ),
						self::TOO_LARGE_FILE_SIZE         => __( 'File size is too large.', 'miniorange-2-factor-authentication' ),
						self::INVALIDE_REDIRECTION_URL    => __( 'Invalide redirection url. Please enter valid url.', 'miniorange-2-factor-authentication' ),
						self::RESET_SETTINGS_SUCCESSFULLY => __( 'Reset settings successfully!', 'miniorange-2-factor-authentication' ),
						self::BACKUPCODE_VALIDATED        => __( 'Backup code validated successfully.', 'miniorange-2-factor-authentication' ),
						self::INVALID_BACKUPCODE          => __( 'The code you provided is already used or incorrect.', 'miniorange-2-factor-authentication' ),
						self::ENTER_BACKUP_CODES          => __( 'Please enter the backup code.', 'miniorange-2-factor-authentication' ),
						self::EMAIL_TEMPLATE_SAVED        => __( 'Email template saved successfully!', 'miniorange-2-factor-authentication' ),
						self::EMAIL_TEMPLATE_RESET        => __( 'Email template reset successfully!', 'miniorange-2-factor-authentication' ),
						self::SUPPORT_FORM_SENT           => __( 'Thanks for getting in touch! We shall get back to you shortly.', 'miniorange-2-factor-authentication' ),
						self::QUERY_SUBMITTED             => __( 'Your query is already submitted.', 'miniorange-2-factor-authentication' ),
						self::SUPPORT_FORM_ERROR          => __( 'Your query could not be submitted. Please try again.', 'miniorange-2-factor-authentication' ),
						self::DUO_INVALID_REQ             => __( 'Invalid or missing parameters, or a user with this name already exists.', 'miniorange-2-factor-authentication' ),
						self::VERIFY_CHAT_ID              => __( 'An Error has occured while sending the OTP. Please verify your chat ID.', 'miniorange-2-factor-authentication' ),
						self::DUO_ACCOUNT_INACTIVE        => __( 'Your account is inactive from duo side, please contact to your administrator.', 'miniorange-2-factor-authentication' ),
						self::DUO_USER_EXISTS             => __( 'This user is already available on duo, please send push notification to setup the 2FA.', 'miniorange-2-factor-authentication' ),
						self::DUO_SERVER_NOT_RESPONDING   => __( 'Duo server is not responding right now, please try after some time.', 'miniorange-2-factor-authentication' ),
						self::INVALID_CREDENTIALS         => __( 'Not the valid credential, please enter valid keys.', 'miniorange-2-factor-authentication' ),
						self::FIELD_MISSING               => __( 'Some field is missing, please fill all required details.', 'miniorange-2-factor-authentication' ),
						self::UNIQUE_QUESTION             => __( 'The questions you select must be unique.', 'miniorange-2-factor-authentication' ),
						self::OTP_EXPIRED                 => __( 'OTP has been expired please initiate another transaction for verification.', 'miniorange-2-factor-authentication' ),
						self::SETUP_2FA                   => __( 'Please set up the second-factor by clicking on Configure button.', 'miniorange-2-factor-authentication' ),
						self::INVALID_REQ                 => __( 'Invalid request. Please try again.', 'miniorange-2-factor-authentication' ),
						self::INVALID_ENTRY               => __( 'All the fields are required. Please enter valid entries.', 'miniorange-2-factor-authentication' ),
						self::INVALID_EMAIL_VER_REQ       => __( 'Invalid request. Test case failed.', 'miniorange-2-factor-authentication' ),
						self::COMPLETED_TEST              => __( 'You have successfully completed the test.', 'miniorange-2-factor-authentication' ),
						self::SET_AS_2ND_FACTOR           => __( 'is set as your 2 factor authentication method.', 'miniorange-2-factor-authentication' ),
						self::VALIDATE_DUO                => __( 'Duo push notification validate successfully.', 'miniorange-2-factor-authentication' ),
						self::ERROR_DURING_USER_REGISTRATION => __( 'Error occurred while registering the user. Please try again.', 'miniorange-2-factor-authentication' ),
						self::ERROR_DURING_PROCESS_EMAIL  => __( 'An error occured while processing your request. Please check if your SMTP server is configured or check if email transactions are exhausted.', 'miniorange-2-factor-authentication' ),
						self::ERROR_DURING_PROCESS        => __( 'An error occured while processing your request. Please try again or contact site administrator.', 'miniorange-2-factor-authentication' ),
						self::ERROR_IN_SENDING_PN         => __( 'An error occured while sending push notification to your app. You can click on <b>Phone is Offline</b> button to enter soft token from app or <b>Forgot your phone</b> button to receive OTP to your registered email.', 'miniorange-2-factor-authentication' ),
						self::SCAN_QR_CODE                => __( 'Please scan the QR code.', 'miniorange-2-factor-authentication' ),
						self::GET_FREE_TRANSACTIONS       => __( 'You have reached your limit of free SMS transactions. In case you did not receive free transactions, please contact us at <a href="mailto:mfasupport@xecurify.com" target="blank">mfasupport@xecurify.com</a>.', 'miniorange-2-factor-authentication' ),
						self::NEW_OTP_SENT                => __( 'A new one-time passcode has been sent to ', 'miniorange-2-factor-authentication' ),
						self::OTP_SENT                    => __( 'One-time passcode has been sent to ', 'miniorange-2-factor-authentication' ),
						self::ENTER_YOUR_EMAIL_PASSWORD   => __( 'Please enter your registered email and password.', 'miniorange-2-factor-authentication' ),
						self::INTERNET_CONNECTIVITY_ERROR => __( 'Unable to generate backup codes. Please check your internet and try again.', 'miniorange-2-factor-authentication' ),
						self::USED_ALL_BACKUP_CODES       => __( 'You have used all of the backup codes.', 'miniorange-2-factor-authentication' ),
						self::BACKUP_CODES_SENT_SUCCESS   => __( 'An email containing the backup codes has been sent. Please click on <strong>Use Backup Codes</strong> to login using the backup codes.', 'miniorange-2-factor-authentication' ),
						self::BACKUP_CODE_INVALID_REQUEST => __( 'Invalid request. Please try again.', 'miniorange-2-factor-authentication' ),
						self::BACKUP_CODE_SENT_ERROR      => __( 'An error ocurred while sending the backup codes on the email. Please contact site administrator.', 'miniorange-2-factor-authentication' ),
						self::BACKUP_CODE_DOMAIN_LIMIT_REACH => __( 'Backup code generation limit has reached for this domain.', 'miniorange-2-factor-authentication' ),
						self::BACKUP_CODE_LIMIT_REACH     => __( 'Backup code generation limit has reached for this user.', 'miniorange-2-factor-authentication' ),
						self::BACKUP_CODE_ALL_USED        => __( 'You have already used all the backup codes for this user and domain.', 'miniorange-2-factor-authentication' ),
						self::BACKUP_CODE_INTERNET_ISSUE  => __( 'An error ocurred while sending backup codes. Please try after some time.', 'miniorange-2-factor-authentication' ),
						self::ANSWER_SECURITY_QUESTIONS   => __( 'Please answer the following security questions.', 'miniorange-2-factor-authentication' ),
						self::ERROR_WHILE_SAVING_KBA      => __( 'Error occured while saving your kba details. Please try again.', 'miniorange-2-factor-authentication' ),
						self::SETTINGS_SAVED              => __( 'Your settings are saved successfully.', 'miniorange-2-factor-authentication' ),
						self::ERROR_IN_SENDING_EMAIL      => __( 'There was an error in sending email. Please click on Resend OTP to try again.', 'miniorange-2-factor-authentication' ),
						self::RESENT_OTP                  => __( 'Another One Time Passcode has been sent', 'miniorange-2-factor-authentication' ),
						self::INVALID_ANSWERS             => __( 'Invalid Answers. Please try again.', 'miniorange-2-factor-authentication' ),
						self::ERROR_FETCHING_QUESTIONS    => __( 'There was an error fetching security questions. Please try again.', 'miniorange-2-factor-authentication' ),
						self::RESET_DUO_CONFIGURATON      => __( 'Your Duo configuration has been reset successfully.', 'miniorange-2-factor-authentication' ),
						self::TRANSIENT_ACTIVE            => __( 'Please try again after some time.', 'miniorange-2-factor-authentication' ),
						self::TEST_GAUTH_METHOD           => __( 'to test Google Authenticator method.', 'miniorange-2-factor-authentication' ),
						self::ERROR_WHILE_VALIDATING_OTP  => __( 'Error occurred while validating the OTP. Please try again.', 'miniorange-2-factor-authentication' ),
						self::PUSH_NOTIFICATION_SENT      => __( 'A Push notification has been sent to your miniOrange Authenticator App.', 'miniorange-2-factor-authentication' ),
						self::ERROR_IN_SENDING_OTP_ONPREM => __( 'There was an error in sending one-time passcode. Please check your SMTP Setup and remaining transactions.', 'miniorange-2-factor-authentication' ),
						self::ERROR_IN_SENDING_OTP        => __( 'There was an error in sending one-time passcode. Your transaction limit might have exceeded.', 'miniorange-2-factor-authentication' ),
						self::ENTER_OTP                   => __( 'Please enter below the code in order to ', 'miniorange-2-factor-authentication' ),
						self::VERIFY_YOURSELF             => __( 'verify yourself.', 'miniorange-2-factor-authentication' ),
						self::SET_THE_2FA                 => __( 'set the 2FA.', 'miniorange-2-factor-authentication' ),
						self::ENTER_VALUE                 => __( 'Please enter a value to test your authentication.', 'miniorange-2-factor-authentication' ),
						self::REGISTER_WITH_MO            => __( 'Invalid request. Please register with miniOrange before configuring your mobile.', 'miniorange-2-factor-authentication' ),
						self::AUTHENTICATION_FAILED       => __( 'Authentication failed. Please try again to test the configuration.', 'miniorange-2-factor-authentication' ),
						self::ERROR_IN_SENDING_OTP_CAUSES => __( 'Error occurred while validating the OTP. Please try again. Possible causes:', 'miniorange-2-factor-authentication' ),
						self::ACCOUNT_CREATED             => __( 'Your account has been created successfully.', 'miniorange-2-factor-authentication' ),
						self::ACCEPT_LINK_TO_VERIFY_EMAIL => __( 'Please click on accept link to verify your email.', 'miniorange-2-factor-authentication' ),
						self::VERIFICATION_EMAIL_SENT     => __( 'A verification email is sent to', 'miniorange-2-factor-authentication' ),
						self::SET_2FA                     => __( 'is set as your Two-Factor method.', 'miniorange-2-factor-authentication' ),
						self::TEST_AUTHY_2FA              => __( 'to test Authy 2-Factor Authentication method.', 'miniorange-2-factor-authentication' ),
						self::ONLY_DIGITS_ALLOWED         => __( 'Only digits are allowed. Please enter again.', 'miniorange-2-factor-authentication' ),
						self::ERROR_WHILE_VALIDATING_USER => __( 'Error occurred while validating the user. Please try again.', 'miniorange-2-factor-authentication' ),
						self::SERVER_TIME_SYNC            => __( 'Please make sure your System and device have the same time as the displayed Server time.', 'miniorange-2-factor-authentication' ),
						self::APP_TIME_SYNC               => __( 'Your App Time is not in sync.Go to settings and tap on tap on Sync Time now.', 'miniorange-2-factor-authentication' ),
						self::ENTER_VALID_ENTRY           => __( 'All the fields are required. Please enter valid entries.', 'miniorange-2-factor-authentication' ),
						self::ERROR_WHILE_SAVING_SETTINGS => __( 'Error occurred while saving the settings.Please try again.', 'miniorange-2-factor-authentication' ),
						self::DISABLED_2FA                => __( 'Two-Factor plugin has been disabled.', 'miniorange-2-factor-authentication' ),
						self::DENIED_DUO_REQUEST          => __( 'You have denied the request or you have not set duo push notification yet', 'miniorange-2-factor-authentication' ),
						self::DENIED_REQUEST              => __( 'You have denied the request.', 'miniorange-2-factor-authentication' ),
						self::REGISTRATION_SUCCESS        => __( 'You are registered successfully.', 'miniorange-2-factor-authentication' ),
						self::ACCOUNT_REMOVED             => __( 'Your account has been removed. Please contact your administrator.', 'miniorange-2-factor-authentication' ),
						self::LOGIN_WITH_2ND_FACTOR       => __( 'Please disable 2FA prompt on WP login page to enable Login with 2nd facor only.', 'miniorange-2-factor-authentication' ),
						self::ERROR_CREATE_ACC_OTP        => __( 'An error occured while creating your account. Please try again by sending OTP again.', 'miniorange-2-factor-authentication' ),
						self::CLICK_HERE                  => __( 'Click Here', 'miniorange-2-factor-authentication' ),
						self::PHONE_NOT_CONFIGURED        => __( 'Your phone number is not configured. Please configure it before selecting OTP Over SMS as your 2-factor method.', 'miniorange-2-factor-authentication' ),
						self::CONFIGURE_2FA               => __( 'to configure another 2 Factor authentication method.', 'miniorange-2-factor-authentication' ),
						self::ADD_MINIORANGE_ACCOUNT      => __( 'Please sign in using your miniOrange account in order to set the 2FA.', 'miniorange-2-factor-authentication' ),
						self::ACCOUNT_ALREADY_EXISTS      => __( 'You already have an account with miniOrange, please sign in.', 'miniorange-2-factor-authentication' ),
						self::INVALID_REQUEST             => __( 'Invalid request. Please register with miniOrange and configure 2-Factor to save your login settings.', 'miniorange-2-factor-authentication' ),
						self::PASS_LENGTH_LIMIT           => __( 'Password length between 6 - 15 characters. Only following symbols (!@#.$%^&*-_) should be present.', 'miniorange-2-factor-authentication' ),
						self::SENT_OTP                    => __( 'The OTP has been sent to', 'miniorange-2-factor-authentication' ),
						self::SOMETHING_WENT_WRONG        => __( 'Something went wrong', 'miniorange-2-factor-authentication' ),
						self::ENTER_SENT_OTP              => __( '. Please enter the OTP you received to Validate.', 'miniorange-2-factor-authentication' ),
						self::USER_LIMIT_EXCEEDED         => __( 'Your limit of 3 users has exceeded. Please upgrade to premium plans for more users.', 'miniorange-2-factor-authentication' ),
						self::USER_PROFILE_SETUP_SMTP     => __( 'Please setup SMTP on your site in order to set the 2FA for your users.', 'miniorange-2-factor-authentication' ),
						self::SESSION_LIMIT_REACHED       => __( 'You have reached the limit for maximum concurrent session allowed. Please logout from another session or wait for it to expire.', 'miniorange-2-factor-authentication' ),
						self::EMAIL_LABEL                 => __( 'Username or Email', 'miniorange-2-factor-authentication' ),
						self::LOGIN_WITH_TWO_FACTOR       => __( 'Login With 2nd Factor', 'miniorange-2-factor-authentication' ),
						self::TWOFA_NOT_ENABLED           => __( 'Two Factor is not enabled for you. Please login with username and password.', 'miniorange-2-factor-authentication' ),
						self::TWOFA_NOT_CONFIGURED        => __( 'Two Factor is not configured for you. Please login with password first to setup 2FA.', 'miniorange-2-factor-authentication' ),
						self::PASSWORDLESS_LOGIN_CANNOT_BE_ENABLED_ERROR => __( 'Please disable the Remember Device and Remember IP features first in order to enable this feature.', 'miniorange-2-factor-authentication' ),
						self::GET_YOUR_PLAN_UPGRADED      => __( 'Please upgrade you plan in order to use this feature.', 'miniorange-2-factor-authentication' ),
						self::REMEMBER_IP_CONSENT_MESSAGE => __( 'Do you want to remember this IP? Remembering this IP enables you to avoid the Two-Factor Authentication (2FA) the next time you login using this IP.', 'miniorange-2-factor-authentication' ),
						self::INVALID_SESSION             => __( 'Invalid session. Please try again.', 'miniorange-2-factor-authentication' ),
					);
				} finally {
					self::$is_translating = false;
				}
			}

			return self::$messages;
		}

		/**
		 * Return the translated message for the given key.
		 *
		 * @param string $key Message key.
		 * @return string
		 */
		public static function mo2f_get_message( $key ) {
			$messages = self::get_messages();
			return isset( $messages[ $key ] ) ? $messages[ $key ] : '';
		}

		/**
		 * Translate legacy message keys passed to WordPress gettext functions.
		 *
		 * @param string $translated Existing translated text.
		 * @param string $text       Text to translate.
		 * @param string $domain     Translation domain.
		 * @return string
		 */
		public static function maybe_translate_key( $translated, $text, $domain ) {
			if ( 'miniorange-2-factor-authentication' !== $domain ) {
				return $translated;
			}

			if ( self::$is_translating ) {
				return $translated;
			}

			$messages = self::get_messages();

			if ( isset( $messages[ $text ] ) ) {
				return $messages[ $text ];
			}

			return $translated;
		}

		/**
		 * Show nofications on admin dashboard depend on type i.e. sucess, error and notice.
		 *
		 * @param string $content - message to be shown on dashboard.
		 * @param string $type - type of message to be shown.
		 * @return void
		 */
		public function mo2f_show_message( $content, $type ) {
			set_site_transient( 'mo2f_show_notification' . wp_get_current_user()->ID, array( $type, $content ), 30 );
		}

		/**
		 * Shows success and error messages after saving the settings.
		 *
		 * @return void
		 */
		public function mo2f_show_message_strip() {
			$user_id             = wp_get_current_user()->ID;
			$is_settings_updated = get_site_transient( 'mo2f_show_notification' . $user_id );
			if ( $is_settings_updated ) {
				$content           = $is_settings_updated[1];
				$type              = $is_settings_updated[0];
				$notification_type = array(
					'CUSTOM_MESSAGE' => 'success',
					'NOTICE'         => 'error',
					'ERROR'          => 'error',
					'SUCCESS'        => 'success',
				);
				echo '<div class="mo2f_overlay_not_JQ_' . esc_attr( $notification_type[ $type ] ) . '" id = "pop_up_' . esc_attr( $notification_type[ $type ] ) . '" > <p class ="mo2f_popup_text_not_JQ"> ' . wp_kses_post( $content ) . '</p> </div>';
				echo '<script type="text/javascript">
        setTimeout(function() {
            var element = document.getElementById("pop_up_' . esc_js( $notification_type[ $type ] ) . '");
            if (element) {
                element.classList.toggle("overlay_not_JQ_' . esc_js( $notification_type[ $type ] ) . '");
                element.innerHTML = "";
            }
        }, 7000);
    </script>
	<style>
	/* This style tag is added because we cant load CSS files everywhere in the WordPress admin dashboard.*/
		.mo2f_overlay_not_JQ_success {
		width: 450px;
		height: min-content;
		position: fixed;
		float: right;
		z-index: 99999;
		top: 0;
		right: 0;
		margin-top: 7%;
		background-color: #bcffb4 !important ;
		/* overflow-x: hidden; */
		transition: 0.5s;
		border-left: 4px solid #46b450;
		}
		.mo2f_overlay_not_JQ_error {
		width: 450px;
		height: min-content;
		position: fixed;
		float: right;
		z-index: 99999;
		top: 0;
		right: 0;
		margin-top: 7%;
		background-color: bisque !important ;
		/* overflow-x: hidden; */
		transition: 0.5s;
		border-left: 4px solid red;
		}
		.mo2f_popup_text_not_JQ {
		color: black;
		margin-top: 2%;
		margin-left: 5%;
		font-weight: 600;
		font-size: 12px !important;
		}
	</style>';
			}
			delete_site_transient( 'mo2f_show_notification' . $user_id );
		}
	}
}
