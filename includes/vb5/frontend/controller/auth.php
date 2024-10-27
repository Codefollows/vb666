<?php
/*========================================================================*\
|| ###################################################################### ||
|| # vBulletin 6.0.6 - Licence Number LN05842122
|| # ------------------------------------------------------------------ # ||
|| # Copyright 2000-2024 MH Sub I, LLC dba vBulletin. All Rights Reserved.  # ||
|| # This file may not be redistributed in whole or significant part.   # ||
|| # ----------------- VBULLETIN IS NOT FREE SOFTWARE ----------------- # ||
|| # http://www.vbulletin.com | http://www.vbulletin.com/license.html   # ||
|| ###################################################################### ||
\*========================================================================*/

class vB5_Frontend_Controller_Auth extends vB5_Frontend_Controller
{
	public function actionLoginDialog()
	{
		$api = Api_InterfaceAbstract::instance();

		$userid = intval($_POST['userid'] ?? 0);
		$logintype = $_POST['logintype'] ?? '';

		$options = vB5_Template_Options::instance();
		$loginwith = $options->get('options.logintype');

		$userInfo = $api->callApi('user', 'fetchUserinfo', ['userid' => $userid], true);

		$loginPhrase = 'email';
		if ($loginwith = 1)
		{
			$loginPhrase = 'username';
		}
		else if ($loginwith = 2)
		{
			$loginPhrase = 'username_or_email';
		}

		$username = ($userInfo ? $userInfo['username'] : '');

		$data = [
			'logintype' => $logintype,
			'userid' => $userInfo['userid'],
			'username' => $username,
		 	'loginPhrase' => $loginPhrase,
		];

		$response = vB5_Template::staticRenderAjax('login', $data);
		$this->sendAsJson($response);
	}

	public function actionAjaxLogin()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		$api = Api_InterfaceAbstract::instance();

		//if we have a userid we use that instead of username/email to identify
		//the user.  Used to log somebody back in after their session expires
		$userid = intval($_POST['userid'] ?? 0);
		$logintype = $_POST['logintype'] ?? '';
		$authcode =  $_POST['mfa_authcode'] ?? '';
		$privacyconsent =  $_POST['privacyconsent'] ?? 0;

		//mfa auth will be ignored if it isn't required (and will probably be blank anyway)
		if ($userid)
		{
			$loginInfo = $api->callApi('user', 'loginSpecificUser', [
				$userid,
				['password' => $_POST['password']],
				['mfa_authcode' => $authcode],
				$logintype,
			]);
		}
		else
		{
			$loginInfo = $api->callApi('user', 'login2', [
				$_POST['username'],
				['password' => $_POST['password']],
				['mfa_authcode' => $authcode],
				$logintype,
			]);
		}

		if (!empty($loginInfo['errors']))
		{
			$this->sendAsJson($loginInfo);
			return false;
		}

		//only do this if the request specifies it.  It is impertive that we only flag consent
		//when we display the privacy message.  We need to make sure that the caller doesn't do
		//this by accident (we don't currently display the message, for instance, on the popup
		//dialog that uses this function -- since that requires a recent login to get to).
		if ($privacyconsent)
		{
			$response = $this->setPrivacyConsent($api, $loginInfo['userid']);
			if ($response !== true)
			{
				$this->sendAsJson($response);
				return false;
			}
		}
		//even if we don't want to set the consent flag, we should clear the deletion when the user logs in
		else
		{
			$response = $api->callApi('user', 'clearFlagForDelete');
			if (isset($response['errors']))
			{
				return $response;
			}
		}

		vB5_Cookie::setSessionCookies($loginInfo, $logintype, !empty($_POST['rememberme']));
		$newUserInfo = $api->callApi('user', 'fetchUserinfo', ['nocache' => true], true);
		$response = ['success' => true];
		$response['newtoken'] = $newUserInfo['securitytoken'];
		$this->sendAsJson($response);

		return true;
	}


	/**
	 *	Logs a user in via an exernal login provider
	 *
	 *	Currently only facebook is supported.
	 *
	 *	Expects the a post with:
	 *	* provider -- currently ignored, should be passed as "facebook" for future compatibility
	 *	* auth -- Facebook auth token for FB user to connect to (provide by FB JS SDK)
	 *
	 * 	outputs the result of the the loginExternal API call as JSON
	 *	@return boolean
	 */
	public function actionLoginExternal()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		$userid = isset($_POST['userid']) ? intval($_POST['userid']) : null;

		$result = [];
		$api = Api_InterfaceAbstract::instance();
		$response = $api->callApi('user', 'loginExternal',
			[
				$_REQUEST['provider'],
				['token' => $_REQUEST['auth']],
				$userid
			]
		);

		if (isset($response['errors']))
		{
			$this->sendAsJson($response);
			return false;
		}

		vB5_Cookie::setSessionCookies($response['login'], 'external', true);

		$response = $this->setPrivacyConsent($api, $response['userid']);
		if ($response !== true)
		{
			$this->sendAsJson($response);
			return false;
		}

		$this->sendAsJson(['response' => $response]);

		return true;
	}

	private function setPrivacyConsent($api, $userid)
	{
		$response = $api->callApi('user', 'setCurrentUserPrivacyConsent');
		if (isset($response['errors']))
		{
			return $response;
		}

		// We may or may not have cleared the delete flag above (it uses the same consent flag)
		// Make sure.
		$response = $api->callApi('user', 'clearFlagForDelete');
		if (isset($response['errors']))
		{
			return $response;
		}

		return true;
	}

	/**
	 * 	Logs a user in via a vb login and connects them to a facebook account
	 *
	 *	Expects post fields for login (only one of the three password fields is strictly required --
	 *	Typically either the password (plain text) or the md5 pair are passed but not both):
	 *	* password
	 *	* vb_login_md5password
	 *	* vb_login_md5password_utf
	 *	* username
	 *	* auth -- Facebook auth token for FB user to connect to (provide by FB JS SDK)
	 *
	 *	If the connection fails then login tokens will not be set and the user will not be logged in even
	 *	if the login portion succeeds.
	 *
	 *	Will output a JSON object with either a standard error message or {'redirect' : $homepageurl}
	 *	@return boolean
	 */
	public function actionLoginAndAssociate()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		$result = [];
		$api = Api_InterfaceAbstract::instance();

		//we might not get all of these
		$password = isset($_POST['password']) ? $_POST['password'] : '';
		$vb_login_md5password = isset($_POST['vb_login_md5password']) ? $_POST['vb_login_md5password'] : '';
		$vb_login_md5password_utf = isset($_POST['vb_login_md5password_utf']) ? $_POST['vb_login_md5password_utf'] : '';

		//login
		$loginInfo = $api->callApi('user', 'login', [$_POST['username'], $password, $vb_login_md5password, $vb_login_md5password_utf, '']);
		if ($this->handleErrorsForAjax($result, $loginInfo))
		{
			$this->sendAsJson($result);
			return false;
		}

		$api = Api_InterfaceAbstract::instance();
		$response = $api->callApi('facebook', 'connectCurrentUser', ['token' => $_POST['auth']]);

		if ($this->handleErrorsForAjax($result, $response))
		{
			$this->sendAsJson($result);
			return false;
		}

		//don't set the auth cookies until after we have connected the user
		vB5_Cookie::setSessionCookies($loginInfo, '', !empty($_POST['rememberme']));

		$homeurl = $api->callApi('route', 'getUrl', ['home', [], []]);
		$this->sendAsJson(['redirect' => $homeurl]);
		return true;
	}

	public function actionLogout()
	{
		// We currently allow logging out via a GET request, however the logout function
		// requires that the hash value passed matches the current security token to avoid CSRF

		$api = Api_InterfaceAbstract::instance();
		$response = $api->callApi('user', 'logout', [$_REQUEST['logouthash']]);
		if (isset($response['errors']))
		{
			self::showErrorPage($response['errors']);
			exit;
		}

		//delete all cookies with cookiePrefix
		vB5_Cookie::deleteAll();

		//Set the session cookie for the newly created guest session so we don't just create another
		//one when we redirect to the home page.
		vB5_Cookie::setSessionCookies($response, '', false);

		header('Location: ' . vB5_Template_Options::instance()->get('options.frontendurl'));
		exit;
	}

	/**
	 * Forgot password form action
	 * Reset url = /auth/lostpw/?action=pwreset&userid=<n>&activationid=<xxxxx>
	 */
	public function actionLostpw()
	{
		/*
			This controller handles 2 actions,
			1) email reset link when guest submits the lost password form (from the {forumroot}/lostpw page, see
				route guid="vbulletin-4ecbdacd6a6f13.66635712" & associated page, pagetemplate & widget nodes in
				the xml files in core/install/vbulletin-*.xml)
			2) accept & handle the GET reset request when user clicks on the link from (1) with ?action=pwreset
			For (1), it should be a POST, but (2) is a GET.
		*/
		if (!isset($_REQUEST['action']) OR $_REQUEST['action'] != 'pwreset')
		{
			$this->verifyPostRequest();
		}

		$input = [
			// Send request
			'email' => (isset($_POST['email']) ? trim(strval($_POST['email'])) : ''),
			'hvinput' => isset($_POST['humanverify']) ? (array)$_POST['humanverify'] : [],

			// Reset Request
			'action' => (isset($_REQUEST['action']) ? trim($_REQUEST['action']) : ''),
			'userid' => (isset($_REQUEST['userid']) ? trim(strval($_REQUEST['userid'])) : ''),
			'activationid' => (isset($_REQUEST['activationid']) ? trim($_REQUEST['activationid']) : ''),
		];

		$api = Api_InterfaceAbstract::instance();

		if ($input['action'] == 'pwreset')
		{
			/*
			redirect to reset password.
			 */
			$url = vB5_Template_Options::instance()->get('options.frontendurl') . '/reset-password?userid=' . $input['userid'] . '&activationid=' . $input['activationid'];
			if (headers_sent())
			{
				echo '<script type="text/javascript">window.location = "' . $url . '";</script>';
			}
			else
			{
				header('Location: ' . $url);
			}
			exit;
		}
		else
		{
			$response = $api->callApi('user', 'emailPassword', ['userid' => 0, 'email' => $input['email'], 'hvinput' => $input['hvinput']]);
			$this->sendAsJson($response);
		}
	}

	public function actionResetPassword()
	{
		$this->verifyPostRequest();

		$api = Api_InterfaceAbstract::instance();

		/*
			Make sure user is not logged in.
		 */
		$currentuser = vB5_User::instance();
		if (!empty($currentuser['userid']))
		{
			/*
				Note, this does not consume the activationid.
			 */
			$phrasesToFetch = [
				'password_reset',
				'changing_password_but_currently_logged_in_msg',
			];
			$phrases = $api->callApi('phrase', 'fetch', [$phrasesToFetch]);
			$phrases['changing_password_but_currently_logged_in_msg'] = vsprintf($phrases['changing_password_but_currently_logged_in_msg'], [$currentuser['username'], $currentuser['logouthash']]);
			vB5_ApplicationAbstract::showMsgPage($phrases['password_reset'], $phrases['changing_password_but_currently_logged_in_msg']);
			return;
		}

		$userid = (isset($_REQUEST['userid']) ? trim($_REQUEST['userid']) : '');
		$activationid = (isset($_REQUEST['activationid']) ? trim($_REQUEST['activationid']) : '');
		$newpassword = (isset($_REQUEST['new-password']) ? trim($_REQUEST['new-password']) : '');

		/*
			user api / login lib will throw exceptions for us for bad passwords etc.
		 */
		$response = $api->callApi('user', 'setNewPassword', ['userid' => $userid, 'activationid' => $activationid, 'newpassword' => $newpassword]);

		if (isset($response['errors']))
		{
			$phraseController = vB5_Template_Phrase::instance();
			$phraseController->register(['error']);

			//call message first so that we pull both phrases at the same time
			$phraseArg = $response['errors'][0];
			if (is_array($phraseArg))
			{
				// The login library (ATM the only place that returns an array instead of string) can return an ['passwordhistory', $checkOptions['passwordhistorylength']]
				$message = call_user_func_array([$phraseController, 'getPhrase'], array_values($phraseArg));
			}
			else
			{
				$message = $phraseController->getPhrase($phraseArg);
			}
			$title = $phraseController->getPhrase('error');
		}
		else
		{
			$title = $response['password_reset'];
			$message = $response['setnewpw_message'];
		}

		vB5_ApplicationAbstract::showMsgPage($title, $message);
	}
}
/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 114817 $
|| #######################################################################
\*=========================================================================*/
