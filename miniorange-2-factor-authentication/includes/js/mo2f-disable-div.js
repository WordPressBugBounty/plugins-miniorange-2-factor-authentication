jQuery(document).ready(function () {
    jQuery('.mo2f-disable-div').find('a, input, button, select').each(function () {
		if (jQuery(this).is('a')) {
			jQuery(this).on('click', function (e) {
				e.preventDefault();
			}).css('pointer-events', 'none').css('color', 'gray');
		} else if (jQuery(this).is('button')) {
			jQuery(this).prop('disabled', true).css('pointer-events', 'none').css('background-color', '#cccccc');
		} else {
			jQuery(this).prop('disabled', true).css('pointer-events', 'none');
		}
	});
});