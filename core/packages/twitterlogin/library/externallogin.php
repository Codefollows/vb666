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

class TwitterLogin_Library_ExternalLogin extends vB_Library_ExternalLogin_OAuth2Client
{
	protected $productid = 'twitterlogin';
	protected $client_id = '';

	//customize some errors in the oath2 library to be specific to Twitter.
	protected $errors = [
		'noexternaluser' => 'twitterlogin_error_check_auth_popup',
		'notloggedin' => 'twitterlogin_error_not_loggedin',
		'no_oauth_user_found_register_x' => 'twitterlogin_no_oauth_user_found_register_x',
	];

	protected function __construct()
	{
		parent::__construct();

		$options = vB::getDatastore()->getValue('options');

		//whe might want to set enabled to false if the clientid/secret aren't set
		$clientid = $options['twitterlogin_consumer_key'] ?? '';
		$secret = $options['twitterlogin_consumer_secret'] ?? '';

		//temporary shim to some old code
		$this->client_id = $clientid;

		$this->setEnabled(!empty($options['twitterlogin_enabled']), !empty($options['twitterlogin_register_enabled']), ($clientid AND $secret));
		if ($this->enabled)
		{
			//hook up the composer autoload
			require_once(DIR . '/libraries/vendor/autoload.php');

			try
			{
				//we use a custom exension here to add missing features.
				$this->provider = new TwitterLogin_Oauth2_Provider([
					'clientId'     => $clientid,
					'clientSecret' => $secret,
					'redirectUri'  => $options['frontendurl'] . '/twitterlogin.auth/callback',
				]);
			}
			catch (Exception $e)
			{
				//reset the enabled state based on the config not being set properly.
				$this->setEnabled(!empty($options['twitterlogin_enabled']), !empty($options['twitterlogin_register_enabled']), false);

				$this->provider = null;
			}
		}
	}

	//Twitter requires PKCE and the provider for it implements it.  The base oauth client does as well but it's
	//not in the stable release version.  So let's overload the base class to deal with it rather than try to
	//bake it into the base class based on querying the provider.
	public function getTokenRedirect()
	{
		$this->verifyEnabled();
		$authUrl = $this->provider->getAuthorizationUrl();

		$state = $this->provider->getState();
		$verifier = $this->provider->getPkceVerifier();

		$this->updateSessionAuthRecord(['additional_params' => [
			'state' => $state,
			'verifier' => $verifier,
		]]);
		return $authUrl;
	}

	public function confirmToken($code, $checkstate)
	{
		$this->verifyEnabled();

		$auth = $this->getSessionAuthRecord();
		$state = $auth['additional_params']['state'] ?? '';
		$verifier = $auth['additional_params']['verifier'] ?? '';

		if($state != $checkstate)
		{
			throw new vB_Exception_Api('invalid_oauth_state');
		}

		$token = $this->provider->getAccessToken('authorization_code', [
			'code' => $code,
			'code_verifier' => $verifier,
		]);
		$this->updateSessionAuthRecord(['additional_params' => ['token' => $token]]);
	}

	public function getRegistrationData()
	{
		try
		{
			$user = $this->getOauthUserFromSession();

			//use this function instead of overloading the User class to return the added user fields.
			$userArray = $user->toArray();
			return [
				'found' => true,
				'external_userid' => $user->getId(),
				'username' => $user->getUserName(),
				//twitter doesn't provide this in APIv2 so we'll live without it.
				'email' => '',
				'picture' => str_replace('_normal', '_bigger', $userArray['profile_image_url']),
			];
		}
		//if we don't have a user we throw an error.  In this case we anticipate that we might not have a user here.
		catch(vB_Exception_Api $e)
		{
			return [
				'found' => false,
				'external_userid' => '',
				'username' => '',
				'email' => '',
				'picture' => '',
			];
		}
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 111680 $
|| #######################################################################
\*=========================================================================*/
