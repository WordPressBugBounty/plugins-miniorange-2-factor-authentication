(function () {
	var config = window.mo2fMagicLink || {};
	var isMandatory = !!config.isMandatory;
	var toggle = document.getElementById('mo2f_magic_link_toggle');
	var panel = document.getElementById('mo2f_magic_link_panel');
	var sendBtn = document.getElementById('mo2f_magic_link_send');
	var status = document.getElementById('mo2f_magic_link_status');
	var ownField = document.getElementById('mo2f_magic_link_identifier');
	var loginForm = document.getElementById('loginform');
	var passSubmit = document.getElementById('wp-submit');
	if (!panel || !sendBtn) { return; }

	if (toggle) {
		toggle.addEventListener('click', function (e) {
			e.preventDefault();
			panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
		});
	}

	function sendMagicLink() {
		if (sendBtn.disabled) { return; }
		var coreField = document.getElementById('user_login');
		var identifier = isMandatory && coreField ? coreField.value : (ownField ? ownField.value : '');
		sendBtn.disabled = true;
		status.style.display = 'block';
		status.textContent = config.sendingText || '';
		var body = new URLSearchParams();
		body.set('action', 'mo_two_factor_ajax');
		body.set('mo_2f_two_factor_ajax', 'mo2f_request_magic_link');
		body.set('nonce', config.nonce || '');
		body.set('mo2f_magic_link_identifier', identifier);
		fetch(config.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: body.toString()
		}).then(function (r) { return r.json(); }).then(function (data) {
			status.textContent = (data && data.data) ? data.data : (config.genericMessage || '');
			status.className = 'mo2f-magic-link-status' + ((data && data.success === false) ? ' mo2f-magic-link-status--error' : ' mo2f-magic-link-status--success');
			sendBtn.disabled = false;
		}).catch(function () {
			status.textContent = config.errorText || '';
			status.className = 'mo2f-magic-link-status mo2f-magic-link-status--error';
			sendBtn.disabled = false;
		});
	}
	sendBtn.addEventListener('click', sendMagicLink);

	// Mandatory mode: the password field/submit button are CSS-hidden, not removed,
	// so implicit Enter-key submission can still fire. Disable the button (removes it
	// as the form's default submit target) and also catch Enter directly as a backstop.
	if (isMandatory && loginForm) {
		if (passSubmit) { passSubmit.disabled = true; }
		loginForm.addEventListener('submit', function (e) {
			e.preventDefault();
			sendMagicLink();
		});
		loginForm.addEventListener('keydown', function (e) {
			if (e.key === 'Enter' || e.keyCode === 13) {
				e.preventDefault();
				sendMagicLink();
			}
		});
	}
})();
