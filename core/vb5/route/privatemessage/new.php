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

class vB5_Route_PrivateMessage_New
{
	use vB_Trait_NoSerialize;

	protected $subtemplate = 'privatemessage_newpm';

	protected $userid = 0;

	public function __construct(&$routeInfo, &$matches, &$queryString = '')
	{
		$cleaner = vB::getCleaner();
		if (isset($matches['params']) AND !empty($matches['params']))
		{
			$paramString = (strpos($matches['params'], '/') === 0) ? substr($matches['params'], 1) : $matches['params'];
			list($this->userid) = explode('/', $paramString);
		}
		else if (isset($matches['userid']))
		{
			$this->userid = $matches['userid'];
		}
		$this->userid = $cleaner->clean($this->userid, vB_Cleaner::TYPE_INT);

		$routeInfo['arguments']['subtemplate'] = $this->subtemplate;

		$userid = vB::getCurrentSession()->get('userid');
		$pmquota = vB::getUserContext($userid)->getLimit('pmquota');
		$vboptions = vB::getDatastore($userid)->getValue('options');
		$canUsePmSystem = ($vboptions['enablepms'] AND $pmquota);
		if (!$canUsePmSystem)
		{
			throw new vB_Exception_NodePermission('privatemessage');
		}
	}

	public function getUrlParameters()
	{
		return "/{$this->userid}";
	}

	public function getParameters()
	{
		return array('userid' => $this->userid);
	}

	public function getBreadcrumbs()
	{
		$breadcrumbs = array(
			array(
				'phrase' => 'inbox',
				'url'	=> vB5_Route::buildUrl('privatemessage')
			),
			array(
				'phrase' => 'messages',
				'url' => ''
			),
			array(
				'phrase' => 'new_message',
				'url' => ''
			)
		);

		return $breadcrumbs;
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 110104 $
|| #######################################################################
\*=========================================================================*/
