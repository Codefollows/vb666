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

use League\OAuth2\Client\Provider\Google;

class GoogleLogin_Library_ExternalLogin extends vB_Library_ExternalLogin_OAuth2Client
{
	protected $productid = 'googlelogin';
	protected $client_id = '';

	//customize some errors in the oath2 library to be specific to Google.
	protected $errors = [
		'noexternaluser' => 'googlelogin_error_check_auth_popup',
		'notloggedin' => 'googlelogin_error_not_loggedin',
		'no_oauth_user_found_register_x' => 'googlelogin_no_oauth_user_found_register_x',
	];

	protected function __construct()
	{
		parent::__construct();

		$options = vB::getDatastore()->getValue('options');

		//whe might want to set enabled to false if the clientid/secret aren't set
		$clientid = $options['googlelogin_client_id'] ?? '';
		$secret = $options['googlelogin_client_secret'] ?? '';

		//temporary shim to some old code
		$this->client_id = $clientid;


		$this->setEnabled(!empty($options['googlelogin_enabled']), !empty($options['googlelogin_register_enabled']), ($clientid AND $secret));
		if ($this->enabled)
		{
			//hook up the composer autoload
			require_once(DIR . '/libraries/vendor/autoload.php');

			try
			{
				//setting the redirect url is a little dubious but there isn't a good way to pass stuff to library constructors
				//and we can't set this after the object is constructed.
				$this->provider = new Google([
					'clientId'     => $clientid,
					'clientSecret' => $secret,
					'redirectUri'  => $options['frontendurl'] . '/googlelogin.auth/callback',
				]);
			}
			catch (Exception $e)
			{
				//reset the enabled state based on the config not being set properly.
				$this->setEnabled(!empty($options['googlelogin_enabled']), !empty($options['googlelogin_register_enabled']), false);

				$this->provider = null;
			}
		}
	}

	public function getRegistrationData()
	{
		try
		{
			$user = $this->getOauthUserFromSession();
			return [
				'found' => true,
				'external_userid' => $user->getId(),
				'username' => $user->getName(),
				'email' => $user->getEmail(),
				'picture' => $user->getAvatar(),
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
|| # CVS: $RCSfile$ - $Revision: 111485 $
|| #######################################################################
\*=========================================================================*/
