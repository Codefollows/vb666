(($, vB, ajaxtools, dialogtools) =>
{
	function confirmToken()
	{
		let url = vB.getAjaxBaseurl() + '/twitterlogin.auth/start-token-refresh',
			bc = new BroadcastChannel('twitterlogin');

		dialogtools.popupWindow(url, 'twitterlogin', 500, 700)

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
				call: '/twitterlogin.auth/login',
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
		$('.js-twitterlogin-show-on-unlink').toggleClass('h-hide', !showlink);
		$('.js-twitterlogin-hide-on-unlink').toggleClass('h-hide', showlink);
	}

	$(() =>
	{
		let namespace = 'vbtwitterlogin';
		$('.js-twitterlogin-link-twitter').offon('click.' + namespace, (e) =>
		{
			e.preventDefault();
			confirmToken().then(() =>
			{
				ajaxtools.ajax({
					call: '/ajax/api/twitterlogin.externallogin/linkCurrentUser',
					title_phrase: 'twitterlogin_connect_to_twitter',
					success: () =>
					{
						setLinkButtons(false);
					}
				});
			});
		});

		$('.js-twitterlogin-unlink-twitter').offon('click.' + namespace, (e) =>
		{
			e.preventDefault();
			ajaxtools.ajax({
				call: '/ajax/api/twitterlogin.externallogin/unlinkCurrentUser',
				title_phrase: 'twitterlogin_disconnect_from_twitter',
				success: () =>
				{
					dialogtools.alert('twitterlogin_disconnect_from_twitter', 'twitterlogin_disconnect_complete')
					setLinkButtons(true);
				}
			});
		});

		$('.js-twitterlogin-signin-with-twitter').offon('click.' + namespace, (e) =>
		{
			e.preventDefault();
			//we don't expose the code to close menu items so fake a close click
			//if we don't reload the page we're displaying an error popup and leaving the login menu open
			//just looks bad.
			$('#lnkLoginSignupMenu').trigger('click');
			login($(e.currentTarget));
		});

		$('.js-twitterlogin-register-with-twitter').offon('click.' + namespace, (e) =>
		{
			e.preventDefault();
			{
				confirmToken().then(() =>
				{
					location.reload();
				});
			}
		});

		// Clear preloaded account data from registration form.
		$('.js-twitterlogin-register-remove').offon('click.' + namespace, (e) =>
		{
			e.preventDefault();

			ajaxtools.ajax(
			{
				call: '/ajax/api/twitterlogin.externallogin/forgetRegistrationData',
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

			let $registerAutoFillData = $('.js-twitterlogin-register-data');
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
