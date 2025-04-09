<?php
/**
 * This file is controller for controllers/whitelabelling/2facustomizations.php.
 *
 * @package miniorange-2-factor-authentication/controllers/whitelabelling
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$default_question_count = get_site_option( 'mo2f_default_kbaquestions_users', 2 );
$custom_question_count  = get_site_option( 'mo2f_custom_kbaquestions_users', 1 );
$saved_questions        = get_site_option( 'mo2f_custom_security_questions', array() );
/**
 * Including the file for frontend.
 */
require dirname( dirname( dirname( __FILE__ ) ) ) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'whitelabelling' . DIRECTORY_SEPARATOR . '2facustomizations.php';

