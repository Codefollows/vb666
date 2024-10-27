<?php

class TwitterLogin_Controller_Auth extends vB5_Frontend_Controller
{
	public function actionStartTokenRefresh()
	{
		$api = Api_InterfaceAbstract::instance();
		$result = $api->callApi('twitterlogin:ExternalLogin', 'getTokenRedirect', []);
		if(!empty($result['errors']))
		{
			return $this->showErrorPageBare($result['errors']);
			$this->sendAsJson($result);
			return true;
		}

		header('Location: ' . $result['url']);
	}

	public function actionCallback()
	{
		$api = Api_InterfaceAbstract::instance();
		$code = $_GET['code'] ?? '';
		$state = $_GET['state'] ?? '';

		$result = $api->callApi('twitterlogin:ExternalLogin', 'confirmToken', [$code, $state]);

		if(isset($result['errors']))
		{
			return $this->showErrorPageBare($result['errors']);
		}

		//Ping the opener.
		//Due to issues with firefox not allowing us to close the window the html got too
		//complex to hardcode here.  Make into a template but don't use the full page framework.
		$ping = new vB5_Template('twitterlogin_confirmresponse');
		echo $ping->render();
	}

	public function actionLogin()
	{
		$url = $_REQUEST['url'] ?? '';
		$userid = $_REQUEST['userid'] ?? null;

		$api = Api_InterfaceAbstract::instance();
		$result = $api->callApi('twitterlogin:ExternalLogin', 'verifyAuthAndLogin', [$url, $userid]);
		if ($result['success'])
		{
			vB5_Cookie::setSessionCookies($result['login'], 'external', true);
			$newUserInfo = $api->callApi('user', 'fetchUserinfo', ['nocache' => true], true);
			$result['newtoken'] = $newUserInfo['securitytoken'];
		}
		$this->sendAsJson($result);
	}


}
