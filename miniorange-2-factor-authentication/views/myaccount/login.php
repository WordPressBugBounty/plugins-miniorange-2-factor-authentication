<?php
/**
 * This file includes the UI for login/registration form.
 *
 * @package miniorange-2-factor-authentication/ipblocking/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo '<div class="mo2f-settings-div">';
echo '<div class="" id="mo2f_login_registration_div">' . $html . '</div></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already escaped the necessary in the definition.
