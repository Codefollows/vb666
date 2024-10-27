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

class GoogleLogin_Api_ExternalLogin extends vB_Api_ExternalLogin_OAuth2Client
{
	protected $disableWhiteList = array(
		// required to show login button template
		'getState',
		'showExternalLoginButton',
		// called by google dialog closing to log user into forum via their google account
		'verifyAuthAndLogin',
	);

	public function __construct()
	{
		parent::__construct();
		$this->library = vB_Library::instance('googlelogin:ExternalLogin');
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 111680 $
|| #######################################################################
\*=========================================================================*/
