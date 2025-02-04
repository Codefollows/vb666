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

class vB5_Route_SGAdmin extends vB5_Route
{
	const DEFAULT_PREFIX = 'sgadmin';
	const REGEXP =   'sgadmin/(?P<nodeid>([0-9]+)*)(?P<title>(-[^!@\\#\\$%\\^&\\*\\(\\)\\+\\?/:;"\'\\\\,\\.<>= _]*)*)(/?)(?P<action>([a-z^/]*)*)';
	protected static $createActions = array('settings', 'permissions', 'contributors', 'sidebar', 'invite');
	protected static $adminActions = array('settings', 'permissions', 'contributors', 'owner', 'sidebar', 'members', 'subscribers', 'invite', 'events', 'stats', 'delete');
	// maps sgaction & adminAction to phrase used in the sgadmin_sidebar template
	protected static $breadCrumbPhrases = array(
		'admin' => 'admin',
		'create' => 'create_a_new_group',
		'settings' => 'general_settings',
		'permissions' => 'post_settings',
		'contributors' => 'manage_group_managers',
		//'sidebar' => 'organize_sidebar',
		'members' => 'manage_joined_members',
		'subscribers' => 'manage_subscribers',
		'invite' => 'invite_members',
		//'stats' => 'blog_statistics',
		'delete' => 'delete_sg',
	);
	protected $title;
	protected static $actionKey = 'sgaction';
	/**
	 * There is a silly and fairly serious limitation in php. A descendant cannot override a parent's
	 *	static value. Otherwise we could extend vB_Route_Blogadmin. Since the parent has 'blogaction',
	 *	and we need to make that "sgaction", we have to copy every method of the parent
	 *	even though we aren't changing the contents.
	 */

	/**
	 * constructor needs to check for valid data and set the arguments.
	 *
	 * @param mixed
	 * @param mixed
	 * @param string
	 */
	public function __construct($routeInfo, $matches, $queryString = '', $anchor = '')
	{
		parent::__construct($routeInfo, $matches, $queryString, $anchor);

		if (!empty($matches))
		{
			foreach ($matches AS $key => $match)
			{
				//if we were passed routeInfo, skip it.
				if ($key == 'nodeid')
				{
					$this->arguments['nodeid'] = $match;
					$routeInfo['arguments']['nodeid'] = $match;
				}
				else if ($key == self::$actionKey)
				{
					$action = explode('/', $match);
					$this->arguments[self::$actionKey] = $action[0];
					$routeInfo['arguments'][self::$actionKey] = $action[0];

					if (count($action) > 1)
					{
						$this->arguments['action2'] = $action[1];
						$routeInfo['arguments']['action2'] = $action[1];
					}
				}
				else if ($key == 'action2')
				{
					$this->arguments['action2'] = $match;
					$routeInfo['arguments']['action2'] = $match;
				}
			}
		}

		//update the action variables if they are blank
		if (empty($this->arguments[self::$actionKey]))
		{
			$this->arguments[self::$actionKey] = 'create';
			$routeInfo['arguments'][self::$actionKey] = 'create';
		}

		if (empty($this->arguments['action2']))
		{
			$this->arguments['action2'] = 'settings';
			$routeInfo['arguments']['action2'] = 'settings';
		}

		//check for valid input.
		if (! self::validInput($routeInfo['arguments']))
		{
			throw new vB_Exception_404('upload_invalid_url');
		}

	}

	/**
	 * Checks if route info is valid and performs any required sanitation
	 *
	 * @param array $data
	 * @return bool Returns TRUE iff data is valid
	 */
	protected static function validInput(array &$data)
	{
		//if we have nothing we set actions to create, settings
		//if we have a channelid and no action1 or 2 we set actions to create, settings.
		//if we have no channelid and anything but create, settings then we throw an exception
		// if no action is defined, use index
		if (empty($data[self::$actionKey]))
		{
			$data[self::$actionKey] = 'create';
		}

		if (empty($data['action2']))
		{
			$data['action2'] = 'settings';
		}

		if (!isset($data['guid']) OR empty($data['guid']))
		{
			$data['guid'] = vB_Xml_Export_Route::createGUID($data);
		}

		if ($data[self::$actionKey] == 'admin')
		{
			return (isset($data['nodeid']) AND in_array($data['action2'], self::$adminActions));
		}

		if ($data[self::$actionKey] == 'create')
		{
			return in_array($data['action2'], self::$createActions);
		}

		return false;
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

		$arguments['nodeid'] = 0;
		$arguments[self::$actionKey] = 'create';
		$arguments['action2'] = 'settings';

		return $arguments;
	}

	public static function importArguments($arguments)
	{
		self::pageGuidToId($arguments);

		$arguments['nodeid'] ??= 0;
		$arguments[self::$actionKey] ??= 'create';
		$arguments['action2'] ??= 'settings';

		return $arguments;
	}

	/**
	 * Returns the canonical url
	 */
	public function getCanonicalUrl()
	{
		$url = $this->prefix;

		if (!empty($this->arguments['nodeid']))
		{

			if (empty($this->title))
			{
				$node = vB_Library::instance('node')->getNodeBare($this->arguments['nodeid']);
				$this->title = vB_String::getChannelUrlIdent($node['title']);
			}
			$url .= '/' . $this->arguments['nodeid'] . '-' . $this->title;
		}

		$url .= '/' . $this->arguments[self::$actionKey] . '/' . $this->arguments['action2'];

		if (strtolower(vB_String::getCharset()) != 'utf-8')
		{
			$url = vB_String::encodeUtf8Url($url);
		}

		return $url;
	}
	//Returns the Url
	public function getUrl()
	{
		return $this->getCanonicalUrl();
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

	/**
	 * Build URLs using a single instance for the class. It does not check permissions
	 * @param string $className
	 * @param array $URLInfoList
	 *				- route
	 *				- data
	 *				- extra
	 *				- anchor
	 *				- options
	 * @return array
	 */
	protected static function bulkFetchUrls($className, $URLInfoList)
	{
		$results = array();

		$cache = vB_Cache::instance(vB_Cache::CACHE_FAST);

		foreach($URLInfoList AS $hash => $info)
		{
			try
			{
				// we need different instances, since we need to instantiate different action classes
				$route = new $className($info['routeInfo'], $info['data'], http_build_query($info['extra']), $info['anchor']);

				$options = explode('|', $info['route']);
				$routeId = $options[0];

				$fullURL = $route->getFullUrl($options);
				$cache->write($info['innerHash'], $fullURL, 1440, array('routeChg_' . $routeId));
			}
			catch (Exception $e)
			{
				$fullURL = '';
			}

			$results[$hash] = $fullURL;
		}

		return $results;
	}

	/*
	 * Sets the breadcrumbs for the route
	 * The parent implementation requires a channelid.
	 * For blogadmin, we don't have a channelid but we do have a nodeid.
	 */
	protected function setBreadcrumbs()
	{
		// Skipping home check, surely no one would intentionally set the sgadmin page as a homeroute,
		// and I don't think we want to actually support/test that.
		$this->breadcrumbs = [];
		if (isset($this->arguments['nodeid']) && $this->arguments['nodeid'])
		{
			$this->addParentNodeBreadcrumbs($this->arguments['nodeid']);

			// add the emtpy sgaction (create or admin) bread crumb...
			if (isset(vB5_Route_SGAdmin::$breadCrumbPhrases[$this->arguments['sgaction']]))
			{
				$this->breadcrumbs[] = array(
						'phrase' => vB5_Route_SGAdmin::$breadCrumbPhrases[$this->arguments['sgaction']],
						'url' =>  '',
				);
			}

			// ...then the admin action crumb if the action's phrase is defined in the $breadCrumbPhrases static array
			if (isset(vB5_Route_SGAdmin::$breadCrumbPhrases[$this->arguments['action2']]))
			{
				$this->breadcrumbs[] = array(
						'phrase' => vB5_Route_SGAdmin::$breadCrumbPhrases[$this->arguments['action2']],
						'url' =>  $this->getUrl(),
				);
			}
		}
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 111805 $
|| #######################################################################
\*=========================================================================*/
