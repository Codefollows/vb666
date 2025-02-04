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

class vB5_Route_Page extends vB5_Route
{
	protected $page = null;

	protected function getPage()
	{
		if ($this->page === NULL AND isset($this->arguments['pageid']))
		{
			$cache = vB_Cache::instance(vB_Cache::CACHE_FAST);
			$hashkey = 'vbPage_' . $this->arguments['pageid'];
			$this->page = $cache->read($hashkey);

			if (empty($this->page))
			{
				$this->page = vB::getDbAssertor()->getRow('page', ['pageid' => intval($this->arguments['pageid'])]);

				// use phrased page title & meta desc for breadcrumb
				$guidforphrase = vB_Library::instance('phrase')->cleanGuidForPhrase($this->page['guid']);
				$phrases = vB_Api::instanceInternal('phrase')->fetch(
					[
						'page_' . $guidforphrase . '_title',
						'page_' . $guidforphrase . '_metadesc',
					]
				);

				if (!empty($phrases['page_' . $guidforphrase . '_title']))
				{
					$this->page['title'] = $phrases['page_' . $guidforphrase . '_title'];
				}

				if (!empty($phrases['page_' . $guidforphrase . '_metadesc']))
				{
					$this->page['metadescription'] = $phrases['page_' . $guidforphrase . '_metadesc'];
				}

				$cache->write($hashkey, $this->page, 86400, 'pageChg_' . $this->arguments['pageid']);
			}

		}

		return $this->page;
	}

	public function __construct($routeInfo, $matches, $queryString = '', $anchor = '')
	{
		parent::__construct($routeInfo, $matches, $queryString, $anchor);
		if (isset($this->arguments['pageid']) AND !empty($this->arguments['pageid']))
		{
			$this->setPageKey('pageid');

			$page = $this->getPage();
			if ($page)
			{
				switch($page['guid'])
				{
					case vB_Page::PAGE_SOCIALGROUP:
						$this->checkStyle(vB_Channel::DEFAULT_SOCIALGROUP_PARENT);
						break;
					case vB_Page::PAGE_BLOG:
						$this->setUserAction('viewing_blog_home');
						$this->checkStyle(vB_Channel::DEFAULT_BLOG_PARENT);
						break;
					case vB_Page::PAGE_ONLINE:
					case vB_Page::PAGE_MEMBERLIST:
						$this->setUserAction('viewing_whos_online');
						break;
					case vB_Page::PAGE_SEARCH:
					case vB_Page::PAGE_SEARCHRESULT:
						$this->setUserAction('searching_forums');
						break;
					case vB_Page::PAGE_HOME:
					default:
						// TODO: should pages have a link by default?
						$this->setUserAction('viewing_x', $page['title'], $this->getFullUrl('fullurl'));
				}
			}
		}

		// RSS for channel pages (blog, group). Assuming that if a channelid argument is set, it's a channel
		// and the RSS xml exporter can create one for this. This should also set head links
		if (isset($this->arguments['channelid']))
		{
			$channel = vB_Library::instance('Content_Channel')->getBareContent($this->arguments['channelid']);
			if (is_array($channel))
			{
				$channel = array_pop($channel);
			}

			if ($channel['rss_enabled'])
			{
				$this->arguments['rss_enabled'] = $channel['rss_enabled'];
				$this->arguments['rss_route'] = $channel['rss_route'];
				$this->arguments['rss_title'] = $channel['title'];
				// because conversation routes also add their parent channel's rss info into the arguments,
				// this flag helps us tell channels apart from conversations when we're adding the RSS icon next to the page title
				$this->arguments['rss_show_icon_on_pagetitle'] = true;
			}
		}
	}

	protected function checkStyle($channelguid)
	{
		$channel = vB_Api::instanceInternal('content_channel')->fetchChannelByGUID($channelguid);
		if (!empty($channel['styleid']))
		{
			$forumOptions = vB::getDatastore()->getValue('bf_misc_forumoptions');
			if ($forumOptions['styleoverride'] & $channel['options'])
			{
				// the channel must force the style
				$this->arguments['forceStyleId'] = $channel['styleid'];
			}
			else
			{
				// the channel suggests to use this style
				$this->arguments['routeStyleId'] = $channel['styleid'];
			}
		}
	}

	protected function setBreadcrumbs()
	{
		// If a page is set to home, we should not show any breadcrumbs.
		if ($this->ishomeroute)
		{
			$this->breadcrumbs = [];
		}
		else
		{
			// TODO: A custom pages' hierarchy support for breadcrumbs has been requested.
			// Here, we would also pull a hierachical list of parent pages' and build up
			// the breadcrumbs for such a case.
			$page = $this->getPage();

			if ($page)
			{
				$this->breadcrumbs = [
					0 => [
						'title' => $page['title'],
						'url' => '',
					]
				];
			}
			else
			{
				parent::setBreadcrumbs();
			}
		}
	}

	public function getUrl()
	{
		$url = '';
		if ($this->prefix)
		{
			$url = '/' . $this->prefix;
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
		if (!isset($this->canonicalRoute) AND !empty($this->arguments['pageid']))
		{
			$page = $this->getPage();
			$data = [];

			if (isset($this->arguments['pagenum']) AND is_numeric($this->arguments['pagenum']) AND $this->arguments['pagenum'] > 1)
			{
				$data['pagenum'] = $this->arguments['pagenum'];
			}

			$this->canonicalRoute = self::getRoute($page['routeid'], $data, $this->queryParameters);
		}

		return $this->canonicalRoute;
	}

	protected static function validInput(array &$data)
	{
		if (
			!isset($data['contentid']) OR
			!is_numeric($data['contentid']) OR
			!isset($data['prefix'])
		)
		{
			return false;
		}

		//some pages have there own regex that needs to be preserved.
		if (!isset($data['regex']))
		{
			$data['regex'] = $data['prefix'];
		}

		$data['class'] = __CLASS__;

		/************************************************************************************************************/
		/*
		 * TODO: This class is being used for search routes since rev 58051 (fix for VBV-176) which doesn't
		 * seem correct since they use a different controller and action.
		 *
		 * This is a temporal fix to prevent overwriting the controller and action, but eventually we'll need
		 * to use a different route class. I prefer the route class to always set the controller and action.
		 */
		if (!isset($data['controller']))
		{
			$data['controller']	= 'page';
		}
		if (!isset($data['action']))
		{
			$data['action']	= 'index';
		}
		/************************************************************************************************************/

		//in this case the contentid is the pageid
		$arguments = [];
		if (!empty($data['arguments']))
		{
			$arguments = vb_unserialize($data['arguments']);
		}
		$arguments['pageid'] = $data['contentid'];
		$data['arguments']	= serialize($arguments);

		return parent::validInput($data);
	}

	protected static function updateContentRoute($oldRouteInfo, $newRouteInfo)
	{
		$db = vB::getDbAssertor();
		$events = [];

		$updateIds = self::updateRedirects($db, $oldRouteInfo['routeid'], $newRouteInfo['routeid']);
		foreach($updateIds AS $routeid)
		{
			$events[] = "routeChg_$routeid";
		}

		//if we have an associated channel, update the route on the node record
		//it should always be the oldRoute, but we'll double check and not change it
		//if it isn't.
		$args = vb_unserialize($newRouteInfo['arguments']);
		if (!empty($args['channelid']))
		{
			$db->update(
				'vBForum:node',
				['routeid' => $newRouteInfo['routeid']],
				['nodeid' => $args['channelid'], 'routeid' => $oldRouteInfo['routeid']]
			);

			$events[] = 'nodeChg_' . $args['channelid'];
		}

		vB_Cache::allCacheEvent($events);
	}

	public static function exportArguments($arguments)
	{
		if (!empty($arguments['channelid']))
		{
			self::channelIdtoGuid($arguments);
		}

		self::pageIdtoGuid($arguments);
		return $arguments;
	}

	public static function importArguments($arguments)
	{
		// Some pages may have a channel associated (e.g. Groups, Blogs)
		if (!empty($arguments['channelGuid']))
		{
			self::channelGuidToId($arguments);
		}
		self::pageGuidToId($arguments);
		return $arguments;
	}

	public static function importContentId($arguments)
	{
		return $arguments['pageid'];
	}

	public function setHeadLinks()
	{
		$this->headlinks = [];

		if (vB::getDatastore()->getOption('externalrss'))
		{
			// adding headlink
			$routedata = vB_Api::instance('external')->buildExternalRoute(vB_Api_External::TYPE_RSS2);
			$bbtitle = vB::getDatastore()->getOption('bbtitle');
			$this->headlinks[] = ['rel' => 'alternate', 'title' => $bbtitle, 'type' => 'application/rss+xml', 'href' => $routedata['route'], 'rsslink' => 1];

			// specific channel's rss link iff it's set (which it will be if this is a channel like blog or social group)
			if (!empty($this->arguments['rss_enabled']))
			{
				$this->headlinks[] = [
					'rel' => 'alternate',
					'title' => $bbtitle . ' -- ' . $this->arguments['rss_title'],
					'type' => 'application/rss+xml',
					'href' => $this->arguments['rss_route'],
					'rsslink' => 1,
				];
			}
		}
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 114039 $
|| #######################################################################
\*=========================================================================*/
