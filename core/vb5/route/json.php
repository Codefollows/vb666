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

class vB5_Route_Json extends vB5_Route
{
	private $controllerActionAllowlist = [
		'manifest' => 1,
	];
	// Very simplistic route. Only exists to let us build the proper URL back from the deduped prefix if any.
	public function __construct($routeInfo, $matches, $queryString = '', $anchor = '')
	{
		// if no action is defined, use index
		if (!isset($matches['action']) || empty($matches['action']))
		{
			$matches['action'] = 'index';
		}
		// Add action to arguments (required for rebuilding the URL for this action)
		$routeInfo['arguments']['action'] = $matches['action'];
		// Prefix "call{Action}" for controller method, but restrict which ones are possible for now.
		if (isset($this->controllerActionAllowlist[strtolower($matches['action'])]))
		{
			$routeInfo['action'] = 'action' . ucfirst($matches['action']);
		}
		else
		{
			$routeInfo['action'] = 'manifest';
		}

		parent::__construct($routeInfo, $matches, $queryString, $anchor);

		// add action parameters to route arguments
		// $actionParameters = $this->getParameters();
		// $this->arguments = empty($this->arguments) ? $actionParameters : array_merge($this->arguments, $actionParameters);
	}

	public function getUrl()
	{
		// Mostly copied from vB5_Route_Page::getUrl(), but we set the /manifest/{action} slug
		$url = "/{$this->prefix}/" . $this->arguments['action'];

		if (!empty($this->arguments['subaction']) AND $this->arguments['subaction'] !== '$subaction')
		{
			$url .= '/' . $this->arguments['subaction'];
		}

		if (isset($this->arguments['pagenum']) AND is_numeric($this->arguments['pagenum']) AND $this->arguments['pagenum'] > 1)
		{
			$url .= '/page' . intval($this->arguments['pagenum']);
		}

		if (strtolower(vB_String::getCharset()) != 'utf-8')
		{
			$url = vB_String::encodeUtf8Url($url);
		}

		return $url;
	}

	public function getCanonicalRoute()
	{
		return $this;
	}

	protected function setBreadcrumbs()
	{
		// json pages aren't meant for rendered pages, so we don't care about breadcrumbs.
		$this->breadcrumbs = [];
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 113693 $
|| #######################################################################
\*=========================================================================*/
