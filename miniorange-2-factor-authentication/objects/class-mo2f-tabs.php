<?php
/**Load Tabs
 *
 * @package miniorange-otp-verification/objects
 */

namespace TwoFA\Objects;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Mo2f_Tabs' ) ) {
	/**
	 * This class is used to define the base url of tabs of plugin
	 */
	final class Mo2f_Tabs {
		const TWO_FACTOR           = 'two_factor';
		const WHITE_LABELLING      = 'white_labelling';
		const ADVANCE_SETTINGS     = 'advance_settings';
		const UPGRADE              = 'upgrade';
		const MY_ACCOUNT           = 'my_account';
		const SETUPWIZARD_SETTINGS = 'setupwizard_settings';
		const TROUBLESHOOTING      = 'troubleshooting';
		const CONTACT_US           = 'contact_us';
		const IP_BLOCKING          = 'ip_blocking';
	}
}
