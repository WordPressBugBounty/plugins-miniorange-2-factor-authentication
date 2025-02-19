<?php
/**
 * Description: This file is used to show the user details.
 *
 * @package miniorange-2-factor-authentication/controllers.
 */

use TwoFA\Helper\Mo2f_Common_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
$common_helper = new Mo2f_Common_Helper();
require_once $mo2f_dir_name . 'views' . DIRECTORY_SEPARATOR . 'reports' . DIRECTORY_SEPARATOR . 'users2fastatus.php';


