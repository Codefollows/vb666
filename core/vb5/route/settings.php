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

class vB5_Route_Settings extends vB5_Route
{
	const DEFAULT_PREFIX = 'settings';
	const REGEXP = '(/(?P<tab>profile|account|privacy|notifications|security|subscriptions))?';

	public function __construct($routeInfo, $matches, $queryString = '', $anchor = '')
	{
		if (empty($matches['tab']))
		{
			$matches['tab'] = 'profile';
		}

		parent::__construct($routeInfo, $matches, $queryString, $anchor);

		if (empty($this->arguments['userid']))
		{
			$userInfo = vB::getCurrentSession()->fetch_userinfo();
			$this->arguments['userid'] = $userInfo['userid'];
			$this->arguments['username'] = $userInfo['username'];
		}
		else if (empty($this->arguments['username']))
		{
			$userInfo = vB_User::fetchUserinfo($this->arguments['userid']);
			$this->arguments['username'] = $userInfo['username'];
		}

		$this->breadcrumbs = array(
			0 => array(
				'title' => $this->arguments['username'],
				'url' => vB5_Route::buildUrl('profile',
					array(
						'userid' => $this->arguments['userid'],
						'username' => vB_String::getUrlIdent($this->arguments['username'])
					)
				)
			),
			1 => array(
				'phrase' => 'user_settings',
				'url' => ''
			)
		);

	}

	public function getUrl()
	{
		// the regex contains the url
		$url = '/' . $this->prefix . '/' . $this->arguments['tab'];

		if (strtolower(vB_String::getCharset()) != 'utf-8')
		{
			$url = vB_String::encodeUtf8Url($url);
		}

		return $url;
	}

	public function getCanonicalRoute()
	{
		if (!isset($this->canonicalRoute))
		{
			$page = vB::getDbAssertor()->getRow('page', array('pageid' => $this->arguments['pageid']));
			$this->canonicalRoute = self::getRoute($page['routeid'], $this->arguments, $this->queryParameters);
		}

		return $this->canonicalRoute;
	}

	protected static function validInput(array &$data)
	{
		if (
			!isset($data['pageid']) OR !is_numeric($data['pageid']) OR
			!isset($data['prefix'])
		)
		{
			return false;
		}

		$data['regex'] = $data['prefix'] . self::REGEXP;
		$data['class'] = __CLASS__;
		$data['controller']	= 'page';
		$data['action']		= 'index';
		$data['arguments']	= serialize(array('pageid' => $data['pageid'], 'tab' =>'$tab'));

		return parent::validInput($data);
	}

	protected static function updateContentRoute($oldRouteInfo, $newRouteInfo)
	{
		$db = vB::getDbAssertor();
		$events = array();

		$updateIds = self::updateRedirects($db, $oldRouteInfo['routeid'], $newRouteInfo['routeid']);
		foreach($updateIds AS $routeid)
		{
			$events[] = "routeChg_$routeid";
		}

		vB_Cache::allCacheEvent($events);
	}

	public static function exportArguments($arguments)
	{
		self::pageIdtoGuid($arguments);
		return $arguments;
	}

	public static function importArguments($arguments)
	{
		self::pageGuidToId($arguments);
		return $arguments;
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 110104 $
|| #######################################################################
\*=========================================================================*/
