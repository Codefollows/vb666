(($, vB, ajaxtools, dialogtools) =>
{
	function confirmToken()
	{
		let url = vB.getAjaxBaseurl() + '/googlelogin.auth/start-token-refresh',
			bc = new BroadcastChannel('googlelogin');

		dialogtools.popupWindow(url, 'googlelogin', 500, 700)

		return new Promise((resolve, reject) =>
		{
			$(bc).on('message', resolve);
		}).then(() =>
		{
			bc.close();
		});
	}

	function login($context)
	{
		return confirmToken().then(() =>
		{
			//We don't have a use case for trying to log a user in as a different user if the front end thinks they are logged in
			//so we assume if the page userid is not 0 then we are trying to restore the session for the user we think is logged in
			ajaxtools.ajax({
				call: '/googlelogin.auth/login',
				data: {
					url: location.href,
					userid: pageData.userid,
				},
				success: (result) =>
				{
					$context.trigger('vb-login', result);
				}
			});
		});
	}

	function setLinkButtons(showlink)
	{
		$('.js-googlelogin-show-on-unlink').toggleClass('h-hide', !showlink);
		$('.js-googlelogin-hide-on-unlink').toggleClass('h-hide', showlink);
	}

	$(() =>
	{
		//working with the existing markup for now
		$('.js-googlelogin-signinbutton').offon('click', (e) =>
		{
			e.preventDefault();

			let $element = $(e.currentTarget),
				action = $element.data('action');

			if(action == 'link')
			{
				confirmToken().then(() =>
				{
					ajaxtools.ajax({
						call: '/ajax/api/googlelogin.externallogin/linkCurrentUser',
						title_phrase: 'googlelogin_connect_to_google',
						success: () =>
						{
							setLinkButtons(false);
						}
					});
				});
			}
			else if (action == 'unlink')
			{
				ajaxtools.ajax({
					call: '/ajax/api/googlelogin.externallogin/unlinkCurrentUser',
					title_phrase: 'googlelogin_disconnect_from_google',
					success: () =>
					{
						dialogtools.alert('googlelogin_disconnect_from_google', 'googlelogin_disconnect_complete_revoke_access')
						setLinkButtons(true);
					}
				});
			}
			else if (action == 'login')
			{
				//we don't expose the code to close menu items so fake a close click
				//if we don't reload the page we're displaying an error popup and leaving the login menu open
				//just looks bad.
				$('#lnkLoginSignupMenu').trigger('click');
				login($(e.currentTarget));
			}
			else if (action == 'register')
			{
				confirmToken().then(() =>
				{
					location.reload();
				});
			}
		});

		// Clear preloaded google account data from registration form.
		$('.js-googlelogin-register-remove').offon('click', (evt) =>
		{
			evt.preventDefault();

			ajaxtools.ajax(
			{
				call: '/ajax/api/googlelogin.externallogin/forgetRegistrationData',
				success: function(result)
				{
					location.reload();
				},
			});
		});


		// Registration autofill
		// Wait a few milliseconds to avoid the weird input-wiping initialization that we saw with twitterlogin
		setTimeout(() =>
		{
			let fieldnotset = ($field) => ($field.length > 0 && $field.val() == 0);

			let $registerAutoFillData = $('.js-googlelogin-register-data');
			if ($registerAutoFillData.length > 0 && !$registerAutoFillData.data('register-init'))
			{
				let username = $registerAutoFillData.data('username'),
					email = $registerAutoFillData.data('email'),
					$username = $('#regDataUsername'),
					$email = $('#regDataEmail'),
					$confEmail = $('#regDataEmailConfirm');

				if (username && fieldnotset($username))
				{
					$username.val(username);
				}

				if (email && fieldnotset($email) && fieldnotset($confEmail))
				{
					$email.val(email);
					$confEmail.val(email);
				}

				//not sure what the purpose here is.
				$registerAutoFillData.data('register-init', true);
			}
		}, 412);
	});
})(jQuery, vBulletin, vBulletin.ajaxtools, vBulletin.dialogtools);
