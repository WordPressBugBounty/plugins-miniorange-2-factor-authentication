<?php
/**
 * Description: This file is used to show the user details.
 *
 * @package miniorange-2-factor-authentication/controllers.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once $mo2f_dir_name . 'views' . DIRECTORY_SEPARATOR . '2faconfigurations' . DIRECTORY_SEPARATOR . 'advancedfeatures' . DIRECTORY_SEPARATOR . 'rememberdevice.php';
require_once $mo2f_dir_name . 'views' . DIRECTORY_SEPARATOR . '2faconfigurations' . DIRECTORY_SEPARATOR . 'advancedfeatures' . DIRECTORY_SEPARATOR . 'sessionmanagement.php';
require_once $mo2f_dir_name . 'views' . DIRECTORY_SEPARATOR . '2faconfigurations' . DIRECTORY_SEPARATOR . 'advancedfeatures' . DIRECTORY_SEPARATOR . 'passwordlesslogin.php';
