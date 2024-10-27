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

class vB5_Route_PrivateMessage_List extends vB5_Route_PrivateMessage_Index
{
	protected $pagenum = 1;
	protected $folderid = 0;
	protected $subtemplate = 'privatemessage_listfolder';
	protected $overrideDisable = false;

	public function __construct(&$routeInfo, &$matches, &$queryString = '')
	{
		if (isset($matches['params']) AND !empty($matches['params']))
		{
			$paramString = (strpos($matches['params'], '/') === 0) ? substr($matches['params'], 1) : $matches['params'];
			$params = explode('/', $paramString);
			if (count($params) >= 2 )
			{
				$this->pagenum = $params[1];
				$this->folderid = $params[0];
			}
			else if (!empty($params))
			{
				$this->pagenum = $params[1];
			}
		}
		if (!empty($matches['pagenum']) AND intval($matches['pagenum']))
		{
			$this->pagenum = $matches['pagenum'];
		}

		if (!empty($matches['folderid']) AND intval($matches['folderid']))
		{
			$this->folderid = $matches['folderid'];
		}
		$routeInfo['arguments']['subtemplate'] = $this->subtemplate;

		$userid = vB::getCurrentSession()->get('userid');
		$pmquota = vB::getUserContext($userid)->getLimit('pmquota');
		$vboptions = vB::getDatastore($userid)->getValue('options');
		$canUsePmSystem = ($vboptions['enablepms'] AND $pmquota);
		if (!$canUsePmSystem AND !$this->overrideDisable)
		{
			throw new vB_Exception_NodePermission('privatemessage');
		}
	}

	public function getUrlParameters()
	{
		return "/{$this->folderid}/{$this->pagenum}";
	}

	public function getParameters()
	{
		return array('pagenum' => $this->pagenum, 'folderid' => $this->folderid);
	}

	public function getBreadcrumbs()
	{
		$breadcrumbs = array(
			array(
				'phrase' => 'inbox',
				'url'	=> vB5_Route::buildUrl('privatemessage')
			)
		);

		try
		{
			$folder = vB_Api::instanceInternal('content_privatemessage')->getFolderInfoFromId($this->folderid);
			if (isset($folder[$this->folderid]) AND $folder[$this->folderid]['iscustom'])
			{
				$breadcrumbs[] = array('title' => $folder[$this->folderid]['title'], 'url' => '');
			}
			else if (isset($folder[$this->folderid]) AND !$folder[$this->folderid]['iscustom'])
			{
				$breadcrumbs[] = array('phrase' => $folder[$this->folderid]['title'], 'url' => '');
			}
		}
		catch (vB_Exception_Api $e)
		{
			// something went wrong... don't display that crumb
		}

		return $breadcrumbs;
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 110104 $
|| #######################################################################
\*=========================================================================*/
