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

class vB5_Route_Subscription extends vB5_Route
{
	const REGEXP = '(?P<userid>[0-9]+)(?P<username>(-[^\?]*)*)/(?P<tab>subscriptions|subscribers|groups)(?:/page(?P<pagenum>[0-9]+))?';

	public function __construct($routeInfo, $matches, $queryString = '', $anchor = '')
	{
		parent::__construct($routeInfo, $matches, $queryString, $anchor);
		$this->arguments['userid'] = intval($this->arguments['userid']);
		$userInfo = vB_Api::instanceInternal('user')->fetchProfileInfo($this->arguments['userid']);
		if (empty($this->arguments['userid']))
		{
			$this->arguments['userid'] = $userInfo['userid'];
			$this->arguments['username'] = $userInfo['username'];
		}
		else if (empty($this->arguments['username']))
		{
			$this->arguments['username'] = $userInfo['username'];
		}

		if ($this->arguments['tab'] == 'subscriptions' AND !$userInfo['showSubscriptions'])
		{
			throw new vB_Exception_NodePermission('subscriptions');
		}
		else if ($this->arguments['tab'] == 'subscribers' AND !$userInfo['showSubscribers'])
		{
			throw new vB_Exception_NodePermission('subscribers');
		}
	}

	protected function setBreadCrumbs()
	{
		// I don't think anyone would set a "member/{userid}/subscription" as a home page and
		// not sure if we want to support that. Skipping homeroute check

		$profileurl = vB5_Route::buildUrl('profile',
			array(
				'userid' => $this->arguments['userid'],
				'username' => $this->arguments['username'],
			)
		);

		$this->breadcrumbs = [
			0 => [
				'title' => $this->arguments['username'],
				'url' => $profileurl
			],
			1 => [
				'phrase' => $this->arguments['tab'],
				'url' => ''
			],
		];
	}

	public function getUrl()
	{
		if (empty($this->arguments['username']))
		{
			$userInfo = vB_Api::instanceInternal('user')->fetchProfileInfo($this->arguments['userid']);
			$this->arguments['username'] = $userInfo['username'];
		}

		// the regex contains the url
		$url = '/' . $this->prefix . '/' . $this->arguments['userid'] . '-' . vB_String::getUrlIdent($this->arguments['username']) . '/' . $this->arguments['tab'];

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
		if (!isset($this->canonicalRoute))
		{
			$page = vB::getDbAssertor()->getRow('page', array('pageid'=>$this->arguments['pageid']));
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

		$data['regex'] = $data['prefix'] . '/' . self::REGEXP;
		$data['class'] = __CLASS__;
		$data['controller']	= 'page';
		$data['action']		= 'index';

		$arguments = vb_unserialize($data['arguments']);
		if (!$arguments)
		{
			return false;
		}

		$arguments['pageid'] = $data['pageid'];
		$data['arguments']	= serialize($arguments);

		$result = parent::validInput($data);
		return $result;
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

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 111805 $
|| ####################################################################
\*======================================================================*/
