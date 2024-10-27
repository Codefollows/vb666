<?php if (!defined('VB_ENTRY')) die('Access denied.');
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

class vB5_Route_PrivateMessage_Index
{
	use vB_Trait_NoSerialize;

	protected $subtemplate = 'privatemessage_foldersummary';

	public function __construct(&$routeInfo, &$matches, &$queryString = '')
	{
		// just modify routeInfo, no internal settings
		$routeInfo['arguments']['subtemplate'] = $this->subtemplate;
	}

	public function getUrlParameters()
	{
		return '';
	}

	public function getParameters()
	{
		// TODO: remove the dummy variable, this was just a demo
		return array('dummyIndex' => "I'm a dummy value!");
	}

	public function getBreadcrumbs()
	{
		return array(
			array(
				'phrase' => 'inbox',
				'url'	=> ''
			)
		);
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 108518 $
|| #######################################################################
\*=========================================================================*/
