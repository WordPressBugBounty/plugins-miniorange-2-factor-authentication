jQuery( document ).ready(
	function($){
	jQuery( '.my-color-field' ).wpColorPicker();
	jQuery('.wp-color-result-text').css('color', 'black');
	}
);
jQuery( document ).ready(
	function($) {
		jQuery( ".wp-color-picker" ).wpColorPicker(
			'option',
			'change',
			function(event, ui) {
				var color = ui.color.toString();
				jQuery( "#test123" ).css( 'background-color', color );
			}
		);
	}
);
