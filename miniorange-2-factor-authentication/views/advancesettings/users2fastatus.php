<?php
/**
 * Description: This file is used to show the user details.
 *
 * @package miniorange-2-factor-authentication/views/advancedsettings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
require_once dirname( dirname( dirname( __FILE__ ) ) ) . DIRECTORY_SEPARATOR . 'helper' . DIRECTORY_SEPARATOR . '2fastatus.php';

echo '<div>
		<div class="mo2f-settings-div">';


echo ' <h2><b> Users 2FA Status </b></h2>
        <hr>';

echo ' <table  id="mo2f_user_details" class="display" cellspacing="0" width="100%">
      <thead > 
         <tr>
                 <th>Username</th>
                 <th>Registered 2FA Email</th>
                 <th>Role</th>
                 <th>Method selected</th>
                 <th>Reset 2-Factor</th>
                 
                 
                 
         </tr>
         
         
      </thead>
      
     <tbody > ';


		mo2f_show_user_details();


echo '   </tbody>
     </table>
     </div>
     </div>
     
     <script>
        jQuery("#users2fastatus").addClass("mo2f-subtab-active");
        jQuery("#mo_2fa_advance_settings").addClass("side-nav-active");
	jQuery(document).ready(function() {
		$("#mo2f_user_details").DataTable({
			"order": [[ 0, "desc" ]]
		});
		
	} );

      

</script>';





