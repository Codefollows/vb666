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

class vB5_Frontend_Application extends vB5_ApplicationAbstract
{
	public static function init($rootDir)
	{
		parent::init($rootDir);
		set_exception_handler(['vB5_ApplicationAbstract', 'handleException']);

		self::$instance->router = new vB5_Frontend_Routing();
		self::$instance->router->setRoutes();
		$styleid = vB5_Template_Stylevar::instance()->getPreferredStyleId();

		if ($styleid)
		{
			vB::getCurrentSession()->set('styleid', $styleid);
		}

		self::$instance->convertInputArrayCharset();

		self::setHeaders();
		return self::$instance;
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 111814 $
|| #######################################################################
\*=========================================================================*/
