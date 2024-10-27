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

// TODO: replace this controller with page one
class vB5_Frontend_Controller_Search extends vB5_Frontend_Controller
{
	public function actionIndex()
	{
		//the api init can redirect.  We need to make sure that happens before we echo anything
		$api = Api_InterfaceAbstract::instance();

		$top = '';
		if (vB5_Request::get('cachePageForGuestTime') > 0 AND !vB5_User::get('userid'))
		{
			$fullPageKey = md5(serialize($_REQUEST));
			$fullPage = vB_Cache::instance()->read($fullPageKey);
			if (!empty($fullPage))
			{
				echo $fullPage;
				exit;
			}
		}

		$preheader = vB5_ApplicationAbstract::getPreheader();
		$top .= $preheader;

		if (vB5_Request::get('useEarlyFlush'))
		{
			//we may want to create PHP sessions at some point but we don't know yet
			//and this is our last change to initalize it properly.  Creating the
			//session is likley less overhead than figuring out if we need to
			//and we'd like to expand the user of PHP sessions in the future.
			if(session_status() == PHP_SESSION_NONE)
			{
				session_start();
			}

			echo $preheader;
			flush();
		}

		$router = vB5_ApplicationAbstract::instance()->getRouter();
		$arguments = $router->getArguments();
		$userAction = $router->getUserAction();
		if (!empty($userAction))
		{
			$api->callApi('wol', 'register', array($userAction['action'], $userAction['params']));
		}

		$serverData = array_merge($_GET, $_POST);
		if (empty($serverData['searchJSON']) AND empty($serverData['r']) AND empty($serverData['e']))
		{
			$adv_search = $api->callApi('route', 'getRouteByIdent', array('ident' => 'advanced_search'), true);
			$arguments = $adv_search['arguments'];
		}

		$pageid = (int) (isset($arguments['pageid']) ? $arguments['pageid'] : $arguments['contentid']);

		$page = $api->callApi('page', 'fetchPageById', array($pageid, $arguments));
		if (!$page)
		{
			// @todo This needs to output a user-friendly "page not found" page
			throw new Exception('Could not find page.');
		}

		$phrases = $api->callApi('phrase', 'fetch', array(array('advanced_search')));

		$page['title'] = $phrases['advanced_search'];
		$page['url'] = vB5_Route::buildUrl('advanced_search');
		$page['crumbs'] = $router->getBreadcrumbs();
		// avoid search page itself being indexed
		$page['noindex'] = 1;

		if(!empty($serverData['cookie']))
		{
			$page['searchJSON'] = '{"specific":['.$_COOKIE[$serverData['cookie']].']}';
		}

		if(!empty($serverData['searchJSON']))
		{
			$decoded = json_decode($serverData['searchJSON'],true);
			if (!empty($decoded))
			{
				$page['searchJSON'] = json_encode($decoded);
			}
		}
		elseif (!empty($serverData['r']))
		{
			$page['resultId'] = $serverData['r'];
			if(!empty($serverData['p']) && is_numeric($serverData['p'])){
				$page['currentPage'] = intval($serverData['p']);
			}
		}
		elseif (!empty($serverData['e']))
		{
			$path = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
			if (strlen($path) AND $path[0] == '/')
			{
				$path = substr($path, 1);
			}

			$route = $api->callApi('route', 'getRouteByIdent', array('ident' => 'advanced_search'), true);
			$page = $api->callApi('page', 'fetchPageById', array($route['arguments']['pageid']));
			$page['resultId'] = $serverData['e'];
		}

		$page['ignore_np_notices'] = vB5_ApplicationAbstract::getIgnoreNPNotices();

		$templater = new vB5_Template($page['screenlayouttemplate']);
		$templater->registerGlobal('page', $page);
		$page = $this->outputPage($templater->render(), false);
		$fullPage = $top . $page;

		if (vB5_Request::get('cachePageForGuestTime') > 0 AND !vB5_User::get('userid'))
		{
			vB_Cache::instance()->write($fullPageKey, $fullPage, vB5_Request::get('cachePageForGuestTime'));
		}

		if (!vB5_Request::get('useEarlyFlush'))
		{
			echo $fullPage;
		}
		else
		{
			echo $page;
		}
	}

	public function index()
	{
		return $this->actionIndex();
	}

	public function actionResult()
	{
		//the api init can redirect.  We need to make sure that happens before we echo anything
		$api = Api_InterfaceAbstract::instance();

		$top = '';
		if (vB5_Request::get('cachePageForGuestTime') > 0 AND !vB5_User::get('userid'))
		{
			$fullPageKey = md5(serialize($_REQUEST));
			$fullPage = vB_Cache::instance()->read($fullPageKey);
			if (!empty($fullPage))
			{
				echo $fullPage;
				exit;
			}
		}

		$preheader = vB5_ApplicationAbstract::getPreheader();
		$top .= $preheader;

		if (vB5_Request::get('useEarlyFlush'))
		{
			//we may want to create PHP sessions at some point but we don't know yet
			//and this is our last change to initalize it properly.  Creating the
			//session is likley less overhead than figuring out if we need to
			//and we'd like to expand the user of PHP sessions in the future.
			if(session_status() == PHP_SESSION_NONE)
			{
				session_start();
			}

			echo $preheader;
			flush();
		}

		$serverData = array_merge($_GET, $_POST);
		$router = vB5_ApplicationAbstract::instance()->getRouter();
		$arguments = $router->getArguments();
		$userAction = $router->getUserAction();

		if (!empty($userAction))
		{
			$api->callApi('wol', 'register', array($userAction['action'], $userAction['params']));
		}

		// if Human verification is required, and we don't have 'q' set in serverData (means the user is using
		// the quick search box), we redirect user to advanced search page with HV
		$requirehv = $api->callApi('hv', 'fetchRequireHvcheck', array('search'));
		if (!empty($serverData['AdvSearch']) OR ($requirehv AND isset($serverData['q'])))
		{
			$adv_search = $api->callApi('route', 'getRouteByIdent', array('ident' => 'advanced_search'), true);
			$arguments = $adv_search['arguments'];
		}
		elseif ($requirehv)
		{
			// Advanced search form submitted
			if (empty($serverData['humanverify']))
			{
				$serverData['humanverify'] = array();
			}
			$return = $api->callApi('hv', 'verifyToken', array($serverData['humanverify'], 'search'));

			if ($return !== true)
			{
				$adv_search = $api->callApi('route', 'getRouteByIdent', array('ident' => 'advanced_search'), true);
				$arguments = $adv_search['arguments'];
				$error = $return['errors'][0][0];
			}
		}

		$pageid = (int) (isset($arguments['pageid']) ? $arguments['pageid'] : $arguments['contentid']);

		$page = $api->callApi('page', 'fetchPageById', array($pageid, $arguments));
		if (!$page)
		{
			echo 'Could not find page.';
			exit;
		}

		$phrases = $api->callApi('phrase', 'fetch', array(array('advanced_search', 'search_results')));

		$page['crumbs'] = array(
			0 => array(
				'title' => $phrases['advanced_search'],
				'url' => vB5_Template_Runtime::buildUrl('advanced_search', array(), array(), array('noBaseUrl' => true))
			),
			1 => array(
				'title' => $phrases['search_results'],
				'url' => ''
			)
		);
		// avoid search page itself being indexed
		$page['noindex'] = 1;

		if(!empty($serverData['cookie']))
		{
			$specific = $_COOKIE[$serverData['cookie']] ?? '';
			$serverData['searchJSON'] = '{"specific":[' . $specific . ']}';
		}

		/*
		 The "has q but no searchJSON" block is hit by responsive width search forms.
		 Responsive width search box/form that's part of the hamburger menu lacks the
		 #searchForm ID which global.js expects to convert the 'q' into searchJson.keywords.
		 I don't know if this was intentional or just an oversight, so going with a minimal
		 change to remove the default sort by title for keyword searches that's been left
		 from very old code.
		 If it turns out that people use sort by titles more, we need to make that the default
		 for BOTH blocks not just this block, otherwise we have inconsistent behavior between
		 desktop & mobile.
		 */
		if (!empty($serverData['q']) AND empty($serverData['searchJSON']))
		{
			$serverData['q'] = str_replace(array('"', '\\'), '', $serverData['q']);
			// Note that the searchJSON block below applies filter_var() on keywords.
			$serverData['searchJSON'] = [
				'keywords' => $serverData['q'],
			];
			unset($serverData['q']);

			// Allegedly this is for VBV-2826 -- PM Search Box returns results from forum
			// I guess the PM search box used to add "type":"vBForum_PrivateMessage" to the
			// post data. When I tested the message center recently (on full width & responsive size)
			// it was using the full searchJSON rather than just 'q' & 'type', so I think this is
			// outdated code, but will leave it until we have time to clean this up.
			// TODO: Check if this is still used anywhere and remove it.
			$searchType = '';
			if (!empty($serverData['type']))
			{
				$serverData['type'] = str_replace(['"', '\\'], '', $serverData['type']);
				$serverData['type'] = strip_tags($serverData['searchJSON']['keywords']);
				//$searchType = ',"type":"' . $serverData['type'] . '"';
				$serverData['searchJSON']['type'] = $serverData['type'];
				unset($serverData['type']);
			}

			// Send this through the standardized searchJSON handling below for more consistent behavior...
		}

		if(!empty($serverData['searchJSON']))
		{
			if (is_string($serverData['searchJSON']))
			{
				if(preg_match('/[^\x00-\x7F]/', $serverData['searchJSON']))
				{
					$serverData['searchJSON'] = vB5_String::toUtf8($serverData['searchJSON'], vB5_String::getTempCharset());
				}
				$serverData['searchJSON'] = json_decode($serverData['searchJSON'], true);
			}
			if (!empty($serverData['searchJSON']))
			{
				if (!empty($serverData['searchJSON']['keywords']))
				{
					$serverData['searchJSON']['keywords'] = str_replace(array('"', '\\'), '', $serverData['searchJSON']['keywords']);
					$serverData['searchJSON']['keywords'] = strip_tags($serverData['searchJSON']['keywords']);
				}
				$serverData['searchJSON'] = json_encode($serverData['searchJSON']);
			}
			else
			{
				$serverData['searchJSON'] = '';
			}
			$page['searchJSON'] = $serverData['searchJSON'];
			$extra = array('searchJSON' => !empty($serverData['searchJSON'])?$serverData['searchJSON']:'{}');
			if (!empty($serverData['AdvSearch']))
			{
				$extra['AdvSearch'] = 1;
			}
			$page['url'] = str_replace('&amp;', '&', vB5_Route::buildUrl('search', array(),$extra));
			//$page['searchJSONStructure'] = json_decode($page['searchJSON'],true);
			$page['crumbs'][0]['url'] = vB5_Template_Runtime::buildUrl('advanced_search', array(),array('searchJSON' => $page['searchJSON']), array('noBaseUrl' => true));
		}
		elseif (!empty($serverData['r']))
		{
			unset($page['crumbs'][0]);
			$page['url'] = str_replace('&amp;', '&', vB5_Route::buildUrl('search', array(),array('r' => $serverData['r'])));
			$page['resultId'] = $serverData['r'];
			if(!empty($serverData['p']) && is_numeric($serverData['p'])){
				$page['currentPage'] = intval($serverData['p']);
			}
			$page['crumbs'][0]['url'] = vB5_Template_Runtime::buildUrl('advanced_search', array(),array('r' => $serverData['r']), array('noBaseUrl' => true));
		}
		else
		{
			return $this->actionIndex();
		}

		$page['ignore_np_notices'] = vB5_ApplicationAbstract::getIgnoreNPNotices();

		if (!empty($error))
		{
			$page['error'] = $error;
		}

		$templater = new vB5_Template($page['screenlayouttemplate']);
		$templater->registerGlobal('page', $page);
		$page = $this->outputPage($templater->render(), false);
		$fullPage = $top . $page;

		if (vB5_Request::get('cachePageForGuestTime') > 0 AND !vB5_User::get('userid'))
		{
			vB_Cache::instance()->write($fullPageKey, $fullPage, vB5_Request::get('cachePageForGuestTime'));
		}

		if (!vB5_Request::get('useEarlyFlush'))
		{
			echo $fullPage;
		}
		else
		{
			echo $page;
		}
	}

	public function results()
	{
		return $this->actionResult();
	}

	public function actionFetchTagCloud()
	{
		$taglevels = 5;
		$limit = 20;
		$type = 'search';
		$serverData = array_merge($_GET, $_POST);
		$type = empty($serverData['type']) ? 'search' : $serverData['type'];
		$taglevels = empty($serverData['taglevels']) ? 5 : $serverData['taglevels'];
		$limit = empty($serverData['limit']) ? 20 : $serverData['limit'];

		$tags = vB_Api::instanceInternal('Tags')->fetchTagsForCloud($taglevels, $limit, $type);
		$templater = new vB5_Template('tag_cloud');
		$templater->register('tags', $tags);
		$templater->register('noformat', $serverData['noformat']);
		$this->sendAsJson($templater->render());
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 107953 $
|| #######################################################################
\*=========================================================================*/
