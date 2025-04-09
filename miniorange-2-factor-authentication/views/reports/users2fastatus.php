<?php
/**
 * Description: This file is used to show the user details.
 *
 * @package miniorange-2-factor-authentication/views/reports
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

echo '<div>
		<div class="mo2f-settings-div">';


echo ' <h2><b> Users\' 2FA Status </b></h2>
<div id="toggle" class="mo2f_2fa_status_toggle">
	<div id="mo2f_registered_users_btn" class="mo2f_toggle_button mo2f-active">Registered Users</div>
	<div id="mo2f_unregistered_users_btn" class="mo2f_toggle_button mo2f-active">Unregistered Users</div>
</div>
        <hr>';
		$common_helper->mo2f_show_registered_user_details();
		$common_helper->mo2f_show_unregistered_user_details();

	echo ' </div>
    </div>
<script>
        jQuery("#users2fastatus").addClass("mo2f-subtab-active");
        jQuery("#mo_2fa_reports").addClass("side-nav-active");
</script>';





