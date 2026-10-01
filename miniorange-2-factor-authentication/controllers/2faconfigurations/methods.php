<?php
/**
 * This file is controller for twofactor/quicksetup/views/2faconfigurations/methods.php.
 *
 * @package miniorange-2-factor-authentication/controllers/2faconfigurations
 */

use TwoFA\Helper\MoWpnsConstants;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $mo2f_onprem_cloud_obj, $mo2f_dir_name;
$mo2f_two_factor_methods_details = $mo2f_onprem_cloud_obj->mo2f_plan_methods();
$mo2f_methods_on_dashboard       = array_keys( $mo2f_two_factor_methods_details );
$mo2f_method_names               = MoWpnsConstants::$mo2f_cap_to_small;
$mo2f_method_icons                = MoWpnsConstants::$mo2f_method_icons;
$mo2f_method_hints                = MoWpnsConstants::$mo2f_method_hints;
$mo2f_show_cloud_login_notice     = empty( get_site_option( 'mo2f_customerKey' ) )
	|| 'MO_2_FACTOR_CUSTOMER_REGISTERED_SUCCESS' !== get_site_option( 'mo_2factor_admin_registration_status' );

require $mo2f_dir_name . 'views' . DIRECTORY_SEPARATOR . '2faconfigurations' . DIRECTORY_SEPARATOR . 'methods.php';
