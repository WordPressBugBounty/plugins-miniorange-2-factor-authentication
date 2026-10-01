<?php
/**
 * This file is controller for twofactor/quicksetup/views/2faconfigurations/accessrules.php.
 *
 * @package miniorange-2-factor-authentication/controllers/2faconfigurations
 */

use TwoFA\Helper\MoWpnsUtility;
use TwoFA\Helper\MoWpnsMessages;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $wp_roles, $mo2f_dir_name;
if ( is_multisite() ) {
	$mo2f_first_role      = array( 'superadmin' => 'Superadmin' );
	$wp_roles->role_names = array_merge( $mo2f_first_role, $wp_roles->role_names );
}
$mo2f_free_plan_seats_full = MoWpnsMessages::mo2f_is_free_plan_seat_limit_reached();

require $mo2f_dir_name . 'views' . DIRECTORY_SEPARATOR . '2faconfigurations' . DIRECTORY_SEPARATOR . 'accessrules.php';
