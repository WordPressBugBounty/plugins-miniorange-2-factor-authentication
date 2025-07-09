<?php
/**
 * Description: This file is used to show the user details.
 *
 * @package miniorange-2-factor-authentication/views/reports
 */

use TwoFA\Helper\Mo2f_Common_Helper;
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

echo '<div>
		<div class="mo2f-settings-div">';


echo ' <h2><b> Users\' 2FA Status </b></h2>
<div id="toggle" class="mo2f_2fa_status_toggle">
	<div id="mo2f_registered_users_btn" class="mo2f_toggle_button mo2f-active">Registered Users</div>
	<div id="mo2f_unregistered_users_btn" class="mo2f_toggle_button mo2f-active">Unregistered Users</div>
</div>';


if ( ! apply_filters( 'mo2f_is_lv_needed', false ) ) {
	echo '<div class="text-mo-tertiary-txt ml-mo-8">';
	printf(
		/* Translators: %1$s: <b>, %2$s: </b>, %3$s: <a>, %4$s: </a> */
		esc_html__( '%1$sNote:%2$s In the free version of the plugin, you can set up 2FA for only up to 3 users. If you wish to set it for more users, please upgrade to a %3$sPremium plan%4$s.', 'miniorange-2-factor-authentication' ),
		'<b>',
		'</b>',
		'<a class="mo2f_report_no_underline" target="_blank" href="https://plugins.miniorange.com/2-factor-authentication-for-wordpress-wp-2fa#pricing">',
		'</a>'
	);
	echo '</div>';
	echo '<hr>';
}

$common_helper->mo2f_show_registered_user_details();
$common_helper->mo2f_show_unregistered_user_details();

	echo ' </div>
    </div>
<script>
        jQuery("#users2fastatus").addClass("mo2f-subtab-active");
        jQuery("#mo_2fa_reports").addClass("side-nav-active");
</script>';
