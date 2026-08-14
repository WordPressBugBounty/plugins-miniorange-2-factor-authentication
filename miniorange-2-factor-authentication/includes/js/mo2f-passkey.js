/**
 * Client-side WebAuthn (passkey) ceremony logic for the miniOrange 2FA plugin.
 *
 * Expects a global `mo2fPasskeyConfig` object to be printed inline before this
 * file runs, with shape: { ajaxUrl, nonce, sessionId, redirectTo }.
 */
( function( window, document ) {
	'use strict';

	function mo2fPasskeySupported() {
		return !! ( window.PublicKeyCredential && navigator.credentials && navigator.credentials.create && navigator.credentials.get );
	}

	function mo2fBase64UrlToArrayBuffer( base64url ) {
		if ( ! base64url ) {
			return new ArrayBuffer( 0 );
		}
		var base64 = base64url.replace( /-/g, '+' ).replace( /_/g, '/' );
		var padded = base64 + '='.repeat( ( 4 - ( base64.length % 4 ) ) % 4 );
		var binary = window.atob( padded );
		var buffer = new ArrayBuffer( binary.length );
		var bytes = new Uint8Array( buffer );
		for ( var i = 0; i < binary.length; i++ ) {
			bytes[ i ] = binary.charCodeAt( i );
		}
		return buffer;
	}

	function mo2fArrayBufferToBase64Url( buffer ) {
		var bytes = new Uint8Array( buffer );
		var binary = '';
		for ( var i = 0; i < bytes.byteLength; i++ ) {
			binary += String.fromCharCode( bytes[ i ] );
		}
		var base64 = window.btoa( binary );
		return base64.replace( /\+/g, '-' ).replace( /\//g, '_' ).replace( /=+$/, '' );
	}

	function mo2fPasskeyShowMessage( message ) {
		if ( typeof window.mo2f_show_message === 'function' ) {
			window.mo2f_show_message( message );
		} else {
			window.alert( message ); // eslint-disable-line no-alert
		}
	}

	/**
	 * After a successful registration verification, hand off to the plugin's existing
	 * generic "configuration complete" mechanism for the current flow - the same hidden
	 * forms every other 2FA method (Google Authenticator, Security Questions) submits.
	 */
	function mo2fPasskeyCompleteSetup() {
		var config = window.mo2fPasskeyConfig || {};
		if ( 'inline' === config.flow && document.getElementById( 'mo2f_inline_otp_validated_form' ) ) {
			jQuery( '#mo2f_inline_otp_validated_form' ).submit();
		} else if ( 'dashboard' === config.flow && document.getElementById( 'mo2f_2factor_test_prompt_cross' ) ) {
			jQuery( '#mo2f_2factor_test_prompt_cross' ).submit();
		} else {
			mo2fPasskeyShowMessage( 'Passkey added successfully.' );
		}
	}

	function mo2fPasskeyFriendlyError( error ) {
		if ( error && 'NotAllowedError' === error.name ) {
			return 'The request was cancelled or timed out. Please try again.';
		}
		if ( error && 'InvalidStateError' === error.name ) {
			return 'This authenticator is already registered.';
		}
		return 'Something went wrong with the passkey request. Please try again.';
	}

	/**
	 * Reports a client-side failure back to the server debug log (the same log file
	 * every server-side Passkey step already writes to), so a ceremony that fails
	 * entirely client-side - e.g. navigator.credentials.get() rejecting during a
	 * cross-device/QR hybrid-transport handshake, before any verification request
	 * ever reaches the server - is still diagnosable from that one log, without
	 * requiring access to this browser's own devtools console (which may not be
	 * convenient to check, especially mid cross-device debugging). Best-effort and
	 * intentionally fire-and-forget: never chains further error handling onto this
	 * itself, so a failure to log never masks or replaces the real user-facing error.
	 */
	function mo2fPasskeyLogClientError( context, error ) {
		var config = window.mo2fPasskeyConfig || {};
		try {
			jQuery.post( config.ajaxUrl, {
				action: 'mo_two_factor_ajax',
				mo_2f_two_factor_ajax: 'mo2f_passkey_log_client_error',
				nonce: config.nonce,
				flow: config.flow,
				context: context,
				mo2f_session_id: config.sessionId,
				error_name: error && error.name ? error.name : ( typeof error ),
				error_message: error && error.message ? error.message : String( error ),
			} );
		} catch ( loggingError ) {
			window.console && console.error( 'mo2f-passkey: failed to report client error to server', loggingError ); // eslint-disable-line no-console
		}
	}

	/**
	 * Logs the real error to the console (so it is diagnosable) and shows the user a
	 * friendly message - used to wrap synchronous logic that isn't already covered by
	 * a WebAuthn promise .catch(), so a bug there fails loudly instead of silently.
	 */
	function mo2fPasskeyHandleUnexpectedError( context, error ) {
		window.console && console.error( 'mo2f-passkey: ' + context, error ); // eslint-disable-line no-console
		mo2fPasskeyLogClientError( context, error );
		mo2fPasskeyShowMessage( 'Something went wrong (' + context + '). Please try again or check the browser console for details.' );
	}

	function mo2fPasskeyAjax( data ) {
		var config = window.mo2fPasskeyConfig || {};
		data = data || {};
		data.action = 'mo_two_factor_ajax';
		data.nonce = config.nonce;
		var request = jQuery.post( config.ajaxUrl, data );
		request.fail( function( jqXHR, textStatus, errorThrown ) {
			window.console && console.error( 'mo2f-passkey: AJAX request failed', textStatus, errorThrown ); // eslint-disable-line no-console
			mo2fPasskeyLogClientError( 'ajax:' + ( data.mo_2f_two_factor_ajax || '(unknown)' ), { name: textStatus, message: errorThrown ? String( errorThrown ) : jqXHR.status + ' ' + jqXHR.statusText } );
			mo2fPasskeyShowMessage( 'A network error occurred while contacting the server. Please try again.' );
		} );
		return request;
	}

	/**
	 * Decode a CredentialCreationOptions/CredentialRequestOptions JSON payload
	 * (as produced server-side, with base64url strings for binary fields) into
	 * the ArrayBuffer-based shape the WebAuthn API expects.
	 */
	function mo2fDecodePublicKeyOptions( publicKey, isCreate ) {
		publicKey.challenge = mo2fBase64UrlToArrayBuffer( publicKey.challenge );
		if ( isCreate && publicKey.user && publicKey.user.id ) {
			publicKey.user.id = mo2fBase64UrlToArrayBuffer( publicKey.user.id );
		}
		var listKey = isCreate ? 'excludeCredentials' : 'allowCredentials';
		if ( Array.isArray( publicKey[ listKey ] ) ) {
			publicKey[ listKey ] = publicKey[ listKey ].map( function( entry ) {
				entry.id = mo2fBase64UrlToArrayBuffer( entry.id );
				return entry;
			} );
		}
		return publicKey;
	}

	function mo2fPasskeyStartRegistration() {
		var config = window.mo2fPasskeyConfig || {};
		if ( ! mo2fPasskeySupported() ) {
			mo2fPasskeyShowMessage( 'Your browser does not support passkeys. Please try a modern browser on a device with a fingerprint reader, face recognition, or a security key.' );
			return;
		}
		mo2fPasskeyAjax( {
			mo_2f_two_factor_ajax: 'mo2f_passkey_get_registration_options',
			mo2f_session_id: config.sessionId,
		} ).done( function( response ) {
			try {
				if ( ! response.success ) {
					mo2fPasskeyShowMessage( response.data || 'Could not start passkey registration.' );
					return;
				}
				var publicKey = mo2fDecodePublicKeyOptions( response.data.publicKey, true );
			} catch ( error ) {
				mo2fPasskeyHandleUnexpectedError( 'preparing registration request', error );
				return;
			}
			navigator.credentials.create( { publicKey: publicKey } ).then( function( credential ) {
				try {
					var payload = {
						id: credential.id,
						rawId: mo2fArrayBufferToBase64Url( credential.rawId ),
						type: credential.type,
						response: {
							clientDataJSON: mo2fArrayBufferToBase64Url( credential.response.clientDataJSON ),
							attestationObject: mo2fArrayBufferToBase64Url( credential.response.attestationObject ),
						},
					};
				} catch ( error ) {
					mo2fPasskeyHandleUnexpectedError( 'encoding the new passkey', error );
					return;
				}
				mo2fPasskeyAjax( {
					mo_2f_two_factor_ajax: 'mo2f_validate_otp_for_configuration',
					otp_token: JSON.stringify( payload ),
					mo2f_otp_based_method: 'PASSKEY',
					mo2f_session_id: config.sessionId,
					redirect_to: config.redirectTo,
				} ).done( function( verifyResponse ) {
					if ( verifyResponse.success ) {
						mo2fPasskeyCompleteSetup();
					} else {
						mo2fPasskeyShowMessage( verifyResponse.data || 'Could not verify the new passkey.' );
					}
				} );
			} ).catch( function( error ) {
				window.console && console.error( 'mo2f-passkey: navigator.credentials.create() rejected', error ); // eslint-disable-line no-console
				mo2fPasskeyLogClientError( 'navigator.credentials.create', error );
				mo2fPasskeyShowMessage( mo2fPasskeyFriendlyError( error ) );
			} );
		} );
	}

	function mo2fPasskeyStartAuthentication() {
		var config = window.mo2fPasskeyConfig || {};
		if ( ! mo2fPasskeySupported() ) {
			mo2fPasskeyShowMessage( 'Your browser does not support passkeys. Please use another sign-in method.' );
			return;
		}
		mo2fPasskeyAjax( {
			mo_2f_two_factor_ajax: 'mo2f_passkey_get_authentication_options',
			session_id: config.sessionId,
		} ).done( function( response ) {
			try {
				if ( ! response.success ) {
					mo2fPasskeyShowMessage( response.data || 'Could not start passkey sign-in.' );
					return;
				}
				var publicKey = mo2fDecodePublicKeyOptions( response.data.publicKey, false );
			} catch ( error ) {
				mo2fPasskeyHandleUnexpectedError( 'preparing sign-in request', error );
				return;
			}
			navigator.credentials.get( { publicKey: publicKey } ).then( function( assertion ) {
				try {
					var payload = {
						id: assertion.id,
						rawId: mo2fArrayBufferToBase64Url( assertion.rawId ),
						type: assertion.type,
						response: {
							clientDataJSON: mo2fArrayBufferToBase64Url( assertion.response.clientDataJSON ),
							authenticatorData: mo2fArrayBufferToBase64Url( assertion.response.authenticatorData ),
							signature: mo2fArrayBufferToBase64Url( assertion.response.signature ),
						},
					};
				} catch ( error ) {
					mo2fPasskeyHandleUnexpectedError( 'encoding the sign-in response', error );
					return;
				}
				var softtokenField = document.getElementById( 'mo2fa_softtoken' );
				if ( softtokenField ) {
					softtokenField.value = JSON.stringify( payload );
				} else {
					mo2fPasskeyHandleUnexpectedError( 'locating the login form', new Error( '#mo2fa_softtoken not found' ) );
					return;
				}
				var validateButton = document.getElementById( 'mo2f_validate' );
				if ( validateButton ) {
					validateButton.click();
				} else {
					mo2fPasskeyHandleUnexpectedError( 'submitting the login form', new Error( '#mo2f_validate not found' ) );
				}
			} ).catch( function( error ) {
				window.console && console.error( 'mo2f-passkey: navigator.credentials.get() rejected', error ); // eslint-disable-line no-console
				mo2fPasskeyLogClientError( 'navigator.credentials.get', error );
				mo2fPasskeyShowMessage( mo2fPasskeyFriendlyError( error ) );
			} );
		} );
	}

	function mo2fPasskeyDeleteCredential( credentialId, buttonEl ) {
		var config = window.mo2fPasskeyConfig || {};
		if ( ! window.confirm( 'Remove this passkey? You will no longer be able to sign in with it.' ) ) { // eslint-disable-line no-alert
			return;
		}
		mo2fPasskeyAjax( {
			mo_2f_two_factor_ajax: 'mo2f_passkey_delete_credential',
			credential_id: credentialId,
			mo2f_session_id: config.sessionId,
		} ).done( function( response ) {
			if ( response.success ) {
				var row = buttonEl ? buttonEl.closest( '.mo2f_passkey_list_item' ) : null;
				if ( row ) {
					var list = row.parentNode;
					row.parentNode.removeChild( row );
					if ( list && ! list.querySelector( '.mo2f_passkey_list_item' ) ) {
						list.parentNode.removeChild( list );
					}
				}
			} else {
				mo2fPasskeyShowMessage( response.data || 'Could not remove this passkey.' );
			}
		} );
	}

	window.mo2fPasskeySupported = mo2fPasskeySupported;
	window.mo2fPasskeyStartRegistration = mo2fPasskeyStartRegistration;
	window.mo2fPasskeyStartAuthentication = mo2fPasskeyStartAuthentication;
	window.mo2fPasskeyDeleteCredential = mo2fPasskeyDeleteCredential;
}( window, document ) );
