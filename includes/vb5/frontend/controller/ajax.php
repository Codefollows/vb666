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

class vB5_Frontend_Controller_Ajax extends vB5_Frontend_Controller
{
	/*
	 *	Much of what this controller nominally handles is currently
	 *	handled by applicationlight internally.  We should rework
	 *	application light to handle routing/call controllers explicitly
	 *	but currently this controller needs work before that's possible
	 *	(in particular we need to make changes so that the actions handle
	 *	their own output rather than rely in the index method to do it
	 *	for them, especially since its no longer a one size fits all
	 *	issue).
	 *
	 *	(This includes the apidetach action not found here)
	 */


	// NOTE:
	// ajax/api/* is now handled by application light
	// ajax/render/* is now handled by application light


	/**
	 * Handles all calls to /ajax/* and routes them to the correct method in
	 * this controller, then sends the result as JSON.
	 *
	 * @param	string	Route
	 */
	public function index($route)
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		ob_start();
		$route = trim(strval($route), '/');
		$segments = explode('/', $route);

		// change method-name to actionMethodName
		$method = array_shift($segments);
		$method = preg_replace_callback('#-(.)#',
			function ($matches)
			{
				return strtoupper($matches[1]);
			}, strtolower($method)
		);
		$method = 'action' . ucfirst($method);

		if (method_exists($this, $method))
		{
			$returnValue = call_user_func_array([$this, $method], $segments);
		}
		else
		{
			exit('Invalid AJAX method called');
		}
		$errors = trim(ob_get_clean());
		if (!empty($errors))
		{
			if (!is_array($returnValue))
			{
				$returnValue = [$returnValue];
				$returnValue['wasNotArray'] = 1;
			}

			if (empty($returnValue['errors']))
			{
				$returnValue['errors'] = [];
			}
			array_push($returnValue['errors'], $errors);
		}
		$this->sendAsJson($returnValue);
	}

	/**
	 * Ajax calls to /ajax/call/[controller]/[method] allow calling a
	 * presentation controller
	 *
	 * @param	string	API controller
	 * @param	string	API method
	 *
	 * @param	mixed	The return value of the API call
	 */
	public function actionCall($controller, $method)
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		if (!empty($controller))
		{
			$args = array_merge($_GET, $_POST);
			$class = 'vB5_Frontend_Controller_' . ucfirst($controller);

			// TODO: This is a temporary fix for VBV-4731. Only 'action' methods can be called from ajax/call
			if (strpos($method, 'action') !== 0)
			{
				$method = 'action' . $method;
			}

			if (!class_exists($class) || !method_exists($class, $method))
			{
				return null;
			}
			else
			{
				$object = new $class;
			}

			$reflection = new ReflectionMethod($object, $method);

			if ($reflection->isConstructor() || $reflection->isDestructor() || $reflection->isStatic() )
			{
				return null;
			}

			$php_args = [];
			foreach ($reflection->getParameters() as $param)
			{
				if (isset($args[$param->getName()]))
				{
					$php_args[] = &$args[$param->getName()];
				}
				else
				{
					if ($param->isDefaultValueAvailable())
					{
						$php_args[] = $param->getDefaultValue();
					}
					else
					{
						throw new Exception('Required argument missing: ' . htmlspecialchars($param->getName()));
						return null;
					}
				}
			}

			return $reflection->invokeArgs($object, $php_args);
		}
		return null;
	}

	/**
	 * Renders a widget or screen layout admin template in the presentation layer and
	 * returns it as JSON
	 * Ajax calls should go to /ajax/admin-template/widget or /ajax/admin-template/screen-layout
	 *
	 * @param	string	The type of template requested (widget or screen-layout)
	 */
	public function actionAdminTemplate($type)
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		if ($type == 'widget')
		{
			$pagetemplateid = isset($_REQUEST['pagetemplateid']) ? intval($_REQUEST['pagetemplateid']) : 0;

			if (isset($_REQUEST['widgets']) AND is_array($_REQUEST['widgets']))
			{
				// requesting multiple widget admin templates
				$requestedWidgets = [];
				$requestedWidgetIds = [];
				$requestedWidgetInstanceIds = [];
				foreach ($_REQUEST['widgets'] AS $widget)
				{
					$widgetId = isset($widget['widgetid']) ? intval($widget['widgetid']) : 0;
					$widgetInstanceId = isset($widget['widgetinstanceid']) ? intval($widget['widgetinstanceid']) : 0;

					if ($widgetId < 1)
					{
						continue;
					}

					$requestedWidgets[] = [
						'widgetid' => $widgetId,
						'widgetinstanceid' => $widgetInstanceId,
					];
					$requestedWidgetIds[] = $widgetId;
					$requestedWidgetInstanceIds[] = $widgetInstanceId;
				}

				$requestedWidgetIds = array_unique($requestedWidgetIds);
				$requestedWidgetInstanceIds = array_unique($requestedWidgetInstanceIds);

				if (!empty($requestedWidgetIds))
				{
					$widgets = Api_InterfaceAbstract::instance()->callApi('widget', 'fetchWidgets', ['widgetids' => $requestedWidgetIds]);
				}
				else
				{
					$widgets = [];
				}

				if (!empty($requestedWidgetInstanceIds))
				{
					$widgetInstances = Api_InterfaceAbstract::instance()->callApi('widget', 'fetchWidgetInstances', ['widgetinstanceids' => $requestedWidgetInstanceIds]);
				}
				else
				{
					$widgetInstances = [];
				}

				$widgetsOut = [];
				foreach ($requestedWidgets AS $requestedWidget)
				{
					if (!isset($widgets[$requestedWidget['widgetid']]))
					{
						continue;
					}

					$widget = $widgets[$requestedWidget['widgetid']];

					// we may want to pull the whole widget instance and send it to the template if needed
					$widget['widgetinstanceid'] = $requestedWidget['widgetinstanceid'];

					$templateName = empty($widget['admintemplate']) ? 'widget_admin_default' : $widget['admintemplate'];
					$templater = new vB5_Template($templateName);
					$templater->register('widget', $widget);

					if (isset($widgetInstances[$widget['widgetinstanceid']]) AND is_array($widgetInstances[$widget['widgetinstanceid']]))
					{
						$widgetInstance = $widgetInstances[$widget['widgetinstanceid']];
						$displaySection = $widgetInstance['displaysection'] >= 0 ? $widgetInstance['displaysection'] : 0;
						$displayOrder = $widgetInstance['displayorder'] >= 0 ? $widgetInstance['displayorder'] : 0;
					}
					else
					{
						$displaySection = $displayOrder = 0;
					}

					$widgetsOut[] = [
						'widgetid'         => $widget['widgetid'],
						'widgetinstanceid' => $widget['widgetinstanceid'],
						'displaysection'   => $displaySection,
						'displayorder'     => $displayOrder,
						'pagetemplateid'   => $pagetemplateid,
						'template'         => $templater->render(),
					];
				}

				$output = [
					'widgets'        => $widgetsOut,
					'pagetemplateid' => $pagetemplateid,
				];
			}
			else
			{
				// requesting one widget admin template
				$widgetid = isset($_REQUEST['widgetid']) ? intval($_REQUEST['widgetid']) : 0;
				$widgetinstanceid = isset($_REQUEST['widgetinstanceid']) ? intval($_REQUEST['widgetinstanceid']) : 0;

				$widget = Api_InterfaceAbstract::instance()->callApi('widget', 'fetchWidget', ['widgetid' => $widgetid]);

				// we may want to pull the whole widget instance and send it to the template if needed
				$widget['widgetinstanceid'] = $widgetinstanceid;

				$templateName = empty($widget['admintemplate']) ? 'widget_admin_default' : $widget['admintemplate'];
				$templater = new vB5_Template($templateName);
				$templater->register('widget', $widget);

				$output = [
					'widgetid'         => $widgetid,
					'widgetinstanceid' => $widgetinstanceid,
					'pagetemplateid'   => $pagetemplateid,
					'template'         => $templater->render(),
				];
			}

			return $output;
		}
		else if ($type == 'screen-layout')
		{
			// @todo implement this
		}
	}

	/**
	 * Returns the widget admin template
	 *
	 * Ajax calls should go to /ajax/fetch-widget-template
	 *
	 * @param	string	The type of template requested (widget or screen-layout)
	 */
	public function actionFetchWidgetTemplate()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		$api = Api_InterfaceAbstract::instance();

		$widgetId = intval($_POST['widgetid']);

		$widget = $api->callApi('widget', 'fetchWidget', [$widgetId]);

		$templateName = empty($widget['admintemplate']) ? 'widget_admin_default' : $widget['admintemplate'];
		$templater = new vB5_Template($templateName);
		$templater->register('widget', $widget);

		try
		{
			$template = $templater->render();
		}
		catch (Throwable $e)
		{
			$template = $this->exceptionToErrorArray($e);
		}

		return $template;
	}

	/**
	 * Returns an array of widget objects which include some of the widget information available
	 * via the widget-fetchWidgets API call *and* the rendered admin template to display the
	 * widget on the page canvas when editing a page template. The widget admin template
	 * is rendered here (client side)
	 *
	 * Ajax calls should go to /ajax/fetch-widget-admin-template-list
	 *
	 * @param	string	The type of template requested (widget or screen-layout)
	 */
	public function actionFetchWidgetAdminTemplateList()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		$api = Api_InterfaceAbstract::instance();

		if (isset($_POST['widgetids']) AND is_array($_POST['widgetids']))
		{
			$widgetids = array_map('intval', $_POST['widgetids']);
			$widgetids = array_unique($widgetids);
		}
		else
		{
			$widgetids = []; // retrieve all widgets
		}

		// second param is removeNonPlaceableWidgets = true
		// this removes the System and Abstract widgets
		// (System widgets only removed when not in debug mode)
		$widgets = $api->callApi('widget', 'fetchWidgets', [$widgetids, true]);

		// adding array_values here because the api call returns the widgets indexed
		// by the widgetid, and this function (actionFetchWidgetAdminTemplateList) was
		// previously returning the widgets with an incrementing numeric index
		// this may not be necessary, but for the moment I want to avoid any potential
		// problems that may arise from changing the return value
		return array_values($widgets);
	}

	/**
	 * Replace securitytoken
	 *
	 */
	public function actionReplaceSecurityToken()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		$userinfo = vB_User::fetchUserinfo();

		return ['newtoken' => $userinfo['securitytoken']];
	}

	/**
	 * Returns the sitebuilder template markup required for using sitebuilder
	 *
	 * @param	int	The page id
	 */
	public function actionActivateSitebuilder()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		$api = Api_InterfaceAbstract::instance();
		$canuse = $api->callApi('user', 'hasPermissions', ['adminpermissions', 'canusesitebuilder']);
		if (isset($canuse['errors']))
		{
			return $canuse;
		}

		if (!$canuse)
		{
			return ['errors' => [['inlinemodauth_required']]];
		}

		$sb = [];
		$pageId = isset($_REQUEST['pageid']) ? intval($_REQUEST['pageid']) : 0;
		if ($pageId > 0)
		{
			//should change this to take the route data regardless of what it is to
			//avoid further breakage for other information we may store with a route.
			$arguments = [
				'pageid'	=>	$pageId,
				'nodeid' 	=>	isset($_REQUEST['nodeid']) ? intval($_REQUEST['nodeid']) : 0,
				'userid' 	=>	isset($_REQUEST['userid']) ? intval($_REQUEST['userid']) : '',
			];

			$page = $api->callApi('page', 'fetchPageById', [$pageId, $arguments]);

			$loadMenu = !empty($_REQUEST['loadMenu']);

			if ($page)
			{
				$router = vB5_ApplicationAbstract::instance()->getRouter();
				$page['routeInfo'] = [
					'routeId' => $router->getRouteId(),
					'arguments'	=> $arguments
				];

				//we should explicitly pass and clean the arguments from the request array
				//this previously blindly copied the query string over to the page array whic
				//is bad mojo (especially if things don't get properly cleaned).
				foreach ($arguments AS $key => $value)
				{
					$page[$key] = $value;
				}

				$templates = [
					'css' => '',
					'menu' => '',
					'main' => '',
					'extra' => '',
				];

				if ($loadMenu)
				{
					$templates['css'] = vB5_Template::staticRenderAjax('stylesheet_block', [
						'cssFiles' => ['sitebuilder-after.css'],
					]);

					$templates['menu'] = vB5_Template::staticRenderAjax('admin_sitebuilder_menu');
				}

				$templates['main'] = vB5_Template::staticRenderAjax('admin_sitebuilder', [
					'page' => $page,
				]);


				// output
				$sb['templates'] = [];
				$sb['css_links'] = [];
				foreach ($templates AS $key => $value)
				{
					if (!empty($value))
					{
						$sb['templates'][$key] = $value['template'];
						$sb['css_links'] = array_merge($sb['css_links'], $value['css_links']);
					}
				}
			}
		}

		return $sb;
	}

	/**
	 * Posts a comment to a conversation reply.
	 *
	 */
	public function actionPostComment()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		$results = [];
		$input = [
			'text' => trim(strval($_POST['text'] ?? '')),
			'parentid' => intval($_POST['parentid'] ?? 0),
			'postindex' => intval($_POST['postindex'] ?? 1),
			'view'	=> trim(strval($_POST['view'] ?? 'thread')),
			'redirecturl' => intval($_POST['redirecturl'] ?? 0),
			'isblogcomment' => intval($_POST['isblogcomment'] ?? 0),
			'isarticlecomment' => (isset($_POST['isarticlecomment']) ? intval($_POST['isarticlecomment']) : 0),
			'hvinput' => (isset($_POST['humanverify']) ? $_POST['humanverify'] : ''),
		];

		if ($input['parentid'] > 0)
		{
			$api = Api_InterfaceAbstract::instance();
			$user  = $api->callApi('user', 'fetchUserinfo', []);
			$textData = [
				'parentid' => $input['parentid'],
				// when *editing* comments, it uses create-content/text
				// when *creating* comments, it uses ajax/post-comment
				// NOTE: Keep this in sync with
				//       vB5_Frontend_Controller_CreateContent:: index()
				//
				// htmlspecialchars and nl2br puts the text into the same state
				// it is when the text api receives input from ckeditor
				// specifically, newlines are passed as <br /> and any HTML tags
				// that are typed literally into the editor are passed as HTML-escaped
				// because non-escaped HTML that is sent is assumed to be formatting
				// generated by ckeditor and will be parsed & converted to bbcode.
				'rawtext' => nl2br($api->stringInstance()->htmlspecialchars($input['text'], ENT_NOQUOTES)),
				'userid' => $user['userid'],
				'authorname' => $user['username'],
				'created' => time(),
				'hvinput' => $input['hvinput'],
				'publishdate' => $api->callApi('content_text', 'getTimeNow', [])
			];

			$options = ['skipUpdateLastContent' => 1];
			$altreturn = '';
			$api->invokeHook('hookFrontendContentBeforeAdd', [
				'iscomment' => true,
				'altreturn' => &$altreturn,
				'apilib' => 'content_text',
				'data' => &$textData,
				'options' => &$options,
			]);

			if ($altreturn !== '')
			{
				return $altreturn;
			}

			$nodeId = $api->callApi('content_text', 'add', [$textData, $options]);

			if (is_int($nodeId) AND $nodeId > 0)
			{
				$node = $api->callApi('node', 'getNodeContent', [$nodeId]);
				if ($node AND !isset($node['errors']))
				{
					$node = $node[$nodeId];

					if ($input['redirecturl'])
					{
						//send redirecturl to the client to indicate that it must redirect to the starter detail page after posting a comment to a reply
						$starterNode = $api->callApi('node', 'getNode', [$node['starter']]);
						$results['redirecturl'] = vB5_Template_Options::instance()->get('options.frontendurl') .
							vB5_Route::buildUrl($starterNode['routeid'], $starterNode, ['view' => 'stream', 'p' => $nodeId]) . '#post' . $nodeId;
					}
					else
					{
						//get parent node
						$parentNode = $api->callApi('node', 'getNodeContent', [$input['parentid']]);
						if (!empty($parentNode))
						{
							$parentNode = $parentNode[$input['parentid']];
						}

						/*
							Keep these params in sync with display_Comments template.
							The page & perpage values may not match up with the dynamic
							values set in the template, but those *shouldn't* matter
							for the totalCounts.
						*/
						$params = [
							'parentid' => $input['parentid'],
							'page' => 1,
							'perpage' => 25,
							'depth' => 1,
							'contenttypeid' => null,
							'options' => ['sort' => ['created' => 'DESC'], 'nolimit' => 1],
						];
						$callNamed = true;
						$count = $api->callApi('node', 'listNodeFullContentCount', $params, $callNamed);
						// Preserving old "no zero" behavior
						$totalComments = max($count['totalCount'], 1);

						// send subscribed info for updating the UI
						if (!empty($parentNode['starter']))
						{
							$topicSubscribed = $api->callApi('follow', 'isFollowingContent', ['contentId' => $parentNode['starter']]);
						}
						else
						{
							$topicSubscribed = 0;
						}

						$templater = new vB5_Template('conversation_comment_item');
						$templater->register('conversation', $node);
						$templater->register('commentIndex', $totalComments);
						$templater->register('conversationIndex', $input['postindex']);
						$templater->register('parentNodeIsBlog', $input['isblogcomment']);
						$templater->register('parentNodeIsArticle', $input['isarticlecomment']);

						$enableInlineMod = (
							!empty($parentNode['moderatorperms']['canmoderateposts']) OR
							!empty($parentNode['moderatorperms']['candeleteposts']) OR
							!empty($parentNode['moderatorperms']['caneditposts']) OR
							!empty($parentNode['moderatorperms']['canremoveposts'])
						);
						$templater->register('enableInlineMod', $enableInlineMod);

						$results['template'] = $templater->render();
						$results['totalcomments'] = $totalComments;
						$results['nodeId'] = $nodeId;
						$results['topic_subscribed'] = $topicSubscribed;
					}
				}
				else
				{
					$results['errors'] = $node['errors'];
				}
			}
			else
			{
				$results['errors'] = $nodeId['errors'];
			}

			$api->invokeHook('hookFrontendContentAfterAdd', [
				'iscomment' => true,
				'success' => empty($results['errors']),
				'output' => $results,
				'nodeid' => $nodeId,
			]);
		}
		else
		{
			//this isn't proper, but I'm not sure it can happen without serious abuse.
			//should really rely on the API function to handle, but not sure it does
			//adequately.  Not worth creating a new phrase over but this at least gets
			//it into the proper return format.
			$results['errors'] = ['error_x', 'Cannot post comment.'];
		}

		return $results;
	}

	/**
	 * Fetches comments of a conversation reply.
	 *
	 */
	public function actionFetchComments()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		$results = [];
		$input = [
			'parentid'			=> (isset($_POST['parentid']) ? intval($_POST['parentid']) : 0),
			'page'				=> (isset($_POST['page']) ? intval($_POST['page']) : 0),
			'postindex'			=> (isset($_POST['postindex']) ? intval($_POST['postindex']) : 1),
			'isblogcomment' 	=> (isset($_POST['isblogcomment']) ? intval($_POST['isblogcomment']) : 0),
			'isarticlecomment' 	=> (isset($_POST['isarticlecomment']) ? intval($_POST['isarticlecomment']) : 0),
			'widgetInstanceId'	=> (isset($_POST['widgetInstanceId']) ? intval($_POST['widgetInstanceId']) : 0),
		];
		if ($input['page'] == 0)
		{
			$is_default = true;
			$input['page'] = 1;
		}
		if ($input['parentid'] > 0)
		{
			$params = [
				'parentid'			=> $input['parentid'],
				'page'				=> $input['page'],
				'perpage'			=> 25, // default to 25
				'depth'				=> 1,
				'contenttypeid'		=> null,
				'options'			=> ['sort' => ['created' => 'ASC']]
			];

			$api = Api_InterfaceAbstract::instance();

			// get comment perpage setting from widget config
			$widgetConfig = $api->callApi('widget', 'fetchConfig', [$input['widgetInstanceId']]);
			$params['perpage'] = $commentsPerPage = !empty($widgetConfig['commentsPerPage']) ? $widgetConfig['commentsPerPage'] : 25;
			$initialCommentsPerPage = isset($widgetConfig['initialCommentsPerPage']) ? $widgetConfig['initialCommentsPerPage'] : 3;
			//get parent node's total comment count
			$parentNode = $api->callApi('node', 'getNodeContent', [$input['parentid']]);
			$totalComments = 1;
			if ($parentNode)
			{
				$parentNode = $parentNode[$input['parentid']];
				$totalComments = $parentNode['textcount'];
			}
			$totalPages = ceil($parentNode['textcount'] / $commentsPerPage);
			// flip the pages, first page will have the oldest comments
			$params['page'] = $totalPages - $input['page'] + 1;
			if (!empty($is_default) AND $params['page'] == $totalPages AND ($rem =  $parentNode['textcount'] % $commentsPerPage) > 0 AND $rem <= $initialCommentsPerPage)
			{
				$params['page'] --;
			}
			$nodes = $api->callApi('node', 'listNodeContent', $params);
			if ($nodes)
			{

				$results['totalcomments'] = $totalComments;
				$results['page'] = $totalPages - $params['page'] + 1;
				$commentIndex = (($params['page'] - 1) * $params['perpage']) + 1;
				if ($commentIndex < 1)
				{
					$commentIndex = 1;
				}

				$enableInlineMod = (
					!empty($parentNode['moderatorperms']['canmoderateposts']) OR
					!empty($parentNode['moderatorperms']['candeleteposts']) OR
					!empty($parentNode['moderatorperms']['caneditposts']) OR
					!empty($parentNode['moderatorperms']['canremoveposts'])
				);

				$results['templates'] = [];
				$templater = new vB5_Template('conversation_comment_item');

				$pagingInfo = [
					'currentpage'	=> $params['page'],
					'perpage'		=> $params['perpage']
				];
				foreach ($nodes as $node)
				{
					// Keep this in sync with display_Comments template.
					$hookdata_comment = [
						'context'          => 'comments',
						'channeltype'      => $node['content']['channeltype'],
						'contenttype'      => strtolower($node['contenttypeclass']),
						'showopen'         => $node['showopen'],
						'showpublished'    => $node['showpublished'],
						'showapproved'     => $node['showapproved'],
						'commentId'        => $commentIndex,
						'pagingInfo'       => $pagingInfo,
						'conversation'     => $node['content'],
					];

					$templater->register('conversation', $node['content']);
					$templater->register('commentIndex', $commentIndex);
					$templater->register('conversationIndex', $input['postindex']);
					$templater->register('parentNodeIsBlog', $input['isblogcomment']);
					$templater->register('parentNodeIsArticle', $input['isarticlecomment']);
					$templater->register('enableInlineMod', $enableInlineMod);
					$templater->register('hookdata_comment', $hookdata_comment);
					$results['templates'][$node['nodeid']] = $templater->render();
					++$commentIndex;
				}
			}
			else
			{
				$results['error'] = 'Error fetching comments.';
			}
		}
		else
		{
			$results['error'] = 'Cannot fetch comments.';
		}

		return $results;
	}

	public function actionFetchHiddenModules()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		$api = Api_InterfaceAbstract::instance();

		$result = [];
		if (!empty($_POST['modules']))
		{
			$widgets = $api->callApi('widget', 'fetchWidgetInstanceTemplates', [$_POST['modules']]);

			if ($widgets)
			{
				// register the templates, so we use bulk fetch
				$templateCache = vB5_Template_Cache::instance();
				foreach ($widgets AS $widget)
				{
					$templateCache->register($widget['template'], []);
				}

				// now render them
				foreach ($widgets AS $widget)
				{
					$result[] = [
						'widgetinstanceid' => $widget['widgetinstanceid'],
						'template' => vB5_Template::staticRender($widget['template'], [
							'widgetid' => $widget['widgetid'],
							'widgetinstanceid' => $widget['widgetinstanceid'],
							'isWidget' => 1,
						])
					];
				}
			}
		}

		return $result;
	}

	/**
	 * Fetch node's preview
	 *
	 */
	public function actionFetchNodePreview()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		$preview = '';
		// nodeid can come in via query string, see qtip.js's initializeTopicPreviews() function
		$nodeid = isset($_REQUEST['nodeid']) ? intval($_REQUEST['nodeid']) : [];

		if (!empty($nodeid))
		{
			if (!vb::getUserContext()->getChannelPermission('forumpermissions', 'canviewthreads', $nodeid))
			{
				return '';
			}

			$contenttypes = vB_Types::instance()->getContentTypes();
			$typelist = [];
			foreach ($contenttypes as $key => $type)
			{
				$typelist[$type['id']] = $key;
			}

			$api = Api_InterfaceAbstract::instance();
			$contentTypes = ['vBForum_Text', 'vBForum_Gallery', 'vBForum_Poll', 'vBForum_Video', 'vBForum_Link', 'vBForum_Event'];

			$nodes = $api->callApi('node', 'getNodeContent', [$nodeid]);
			$node = $nodes[$nodeid];
			$contentType = $typelist[$node['contenttypeid']];

			if (in_array($contentType, $contentTypes))
			{
				$preview = vB5_Template_NodeText::instance()->fetchOneNodePreview($nodeid, $api);
			}
		}

		return $preview;
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 115568 $
|| #######################################################################
\*=========================================================================*/
