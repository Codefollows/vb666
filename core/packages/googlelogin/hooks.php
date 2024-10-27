<?php
class googlelogin_Hooks
{
	public static function hookShowExternalLoginButton($params)
	{
		$api = vB_Api::instance('googlelogin:ExternalLogin');
		$state = $api->getState();
		$params['buttons']['googlelogin'] = $state['enabled'];
	}

	public static function hookShowExternalRegistrationBlock($params)
	{
		$api = vB_Api::instance('googlelogin:ExternalLogin');
		$state = $api->getState();
		$params['blocks']['googlelogin'] = $state['register_enabled'];
	}

	public static function hookUserAfterSave($params)
	{
		if ($params['newuser'] AND $params['userid'])
		{
			try
			{
				$lib = vB_Library::instance('googlelogin:ExternalLogin');
				if($lib->getEnabled()['register_enabled'])
				{
					$lib->linkUserWithApp($params['userid']);
				}
			}
			catch(Exception $e)
			{
				//Not sure what we want to do here.  The new user is already created so kicking it
				//back will break the flow (which will assume an error means registration failed).
				//Deleting the user to retry isn't great either.
				//
				//Also if we don't have a google user then will get an exception because the
				//oath user isn't found -- but that's probably because the user isn't connected to
				//google and doesn't want to link accounts.
			}
		}
	}

	public static function hookAdminCPUserExternalConnections($params)
	{
		$ext_userid = '';
		if (!empty($params['userid']))
		{
			$lib = vB_Library::instance('googlelogin:ExternalLogin');
			$auth = $lib->getUserAuthRecord(null, null, $params['userid']);
			if (!empty($auth['external_userid']))
			{
				$ext_userid = $auth['external_userid'];
			}
		}

		$params['externalConnections'][] = [
			'titlephrase' => 'googlelogin_google',
			'connected' => boolval($ext_userid),
			'helpname' => null,
			'displayorder' => 30,
		];
	}

	public static function hookTemplateGroupPhrase($params)
	{
		$params['groups']['googlelogin'] = 'group_googlelogin';
	}

	public static function hookSetRouteWhitelist($params)
	{
		/*
			Login callback
		 */
		$params['whitelistRoute'][] = 'googlelogin/auth';
	}

	public static function hookGetRoutingControllerActionWhitelist($params)
	{
		/*
			Login callback
		 */
		$params['whitelist']['googlelogin.auth'] = [
			'callback',
			'start-token-refresh'
		];
	}

}
