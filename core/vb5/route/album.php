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

class vB5_Route_Album extends vB5_Route
{
	protected $nodeid;

	protected $title;

	protected $controller = 'page';

	private $routeArgs = '';

	public function __construct($routeInfo, $matches, $queryString = '', $anchor = '')
	{
		//we need to pass this along in the canonical route function and there is no good way
		//to reconstruct it, so we'll store it here.
		$this->routeArgs = $routeInfo['arguments'];
		parent::__construct($routeInfo, $matches, $queryString, $anchor);

		if (empty($matches['nodeid']))
		{
			throw new vB_Exception_Router('invalid_request');
		}
		else
		{
			$routeInfo['nodeid'] =  $matches['nodeid'];
			$this->nodeid = $matches['nodeid'];
			$this->arguments['nodeid'] = $matches['nodeid'];
			$this->arguments['contentid'] = $matches['nodeid'];
		}

		if (!empty($matches['title']))
		{
			//It should start with a dash, which we can ignore
			$routeInfo['title'] = substr($matches['title'],1);
			$this->arguments['title'] = substr($matches['title'],1);
		}

		if (!empty($routeInfo['title']))
		{
			$this->title = $routeInfo['title'];
		}

		$this->setPageKey('nodeid');
		$this->setUserAction('viewing_album');
	}

	protected static function validInput(array &$data)
	{
		if (!parent::validInput($data) OR !isset($data['nodeid']) OR !is_numeric($data['nodeid']))
		{
			return false;
		}

		try
		{
			$node = vB_Library::instance('node')->getNodeBare($data['nodeid']);
		}
		catch(Throwable $e)
		{
			return false;
		}

		$data['title'] = $node['title'];
		return true;
	}

	public function getUrl()
	{
		$cache = vB_Cache::instance(vB_Cache::CACHE_FAST);
		$hashKey = 'vbRouteURLIndent_'. $this->arguments['nodeid'];
		$urlident = $cache->read($hashKey);
		if (empty($urlident))
		{
			$node = vB_Library::instance('node')->getNodeBare($this->arguments['nodeid']);
			$urlident = $node['urlident'];
			$cache->write($hashKey, $urlident);
		}
		elseif (is_array($urlident) AND !empty($urlident['urlident']))
		{
			$urlident = $urlident['urlident'];
		}
		$url = '/album/' . $this->arguments['nodeid'] . '-' . $urlident;

		if (strtolower(vB_String::getCharset()) != 'utf-8')
		{
			$url = vB_String::encodeUtf8Url($url);
		}

		return $url;
	}

	public function getCanonicalRoute($node = false)
	{
		if (!isset($this->canonicalRoute))
		{
			if (empty($this->title))
			{
				if (empty($node))
				{
					$node = vB_Library::instance('node')->getNodeBare($this->arguments['nodeid']);
				}

				if (empty($node) OR !empty($node['errors']))
				{
					return FALSE;
				}

				$this->title = vB_String::getChannelUrlIdent($node['title']);
			}

			$routeInfo = array(
				'routeid' => $this->routeId,
				'guid' => $this->routeGuid,
				'prefix' => $this->prefix,
				'regex' => $this->regex,
			 	'nodeid' => $this->nodeid,
				'title' => $this->title,
				'controller' => $this->controller,
				'pageid' => $this->arguments['contentid'],
				'action' => $this->action,
				'arguments' => $this->routeArgs,
			);
			$this->canonicalRoute = new vB5_Route_Album($routeInfo, array('nodeid' => $this->nodeid),
				http_build_query($this->queryParameters));
		}

		return $this->canonicalRoute;
	}

	protected function setBreadcrumbs()
	{
		$this->breadcrumbs = [];
		// If a page is set to home, we should not show breadcrumbs.
		if (!$this->ishomeroute)
		{
			$node = vB_Library::instance('node')->getNodeBare($this->arguments['nodeid']);
			if ($node['nodeid'] == $node['starter'])
			{
				$this->addParentNodeBreadcrumbs($node['parentid']);
			}
		}
	}

	/**
	 * Returns arguments to be exported
	 * @param array $arguments
	 * @return array
	 */
	public static function exportArguments($arguments)
	{
		self::pageIdtoGuid($arguments);
		return $arguments;
	}

	/**
	 * Returns an array with imported values for the route
	 * @param array $arguments
	 * @return array
	 */
	public static function importArguments($arguments)
	{
		self::pageGuidToId($arguments);
		return $arguments;
	}

}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 111805 $
|| #######################################################################
\*=========================================================================*/
