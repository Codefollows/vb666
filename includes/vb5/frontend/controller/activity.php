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

class vB5_Frontend_Controller_Activity extends vB5_Frontend_Controller
{
	public static function mapFormFiltersToSearchOptions(&$filters)
	{
		$search = [];

		if (isset($filters['q']) AND trim($filters['q']) != '')
		{
			$search['keywords'] = $filters['q'];
		}
		else
		{
			// Not sure what this is used for, but this is why we need the $filter passed as reference.
			$filters['q'] = false;
		}

		if (!empty($filters['exclude_type']))
		{
			$search['exclude_type'] = $filters['exclude_type'];
		}

		if (!empty($filters['userid']))
		{
			$search['authorid'] = $filters['userid'];
		}
		else if (!empty($filters['filter_blogs']) AND $filters['filter_blogs'] == 'show_my')
		{
			$search['authorid'] = vB5_User::get('userid');
		}

		if (isset($filters['filter_prefix']))
		{
			if (!empty($filters['filter_prefix']))
			{
				if ($filters['filter_prefix'] == '-1')
				{
					$search['no_prefix'] = 1;
				}
				else if ($filters['filter_prefix'] == '-2')
				{
					$search['has_prefix'] = 1;
				}
				else
				{
					$search['prefix'] = $filters['filter_prefix'];
				}
			}
			else
			{
				// Any thread, regardless of prefix, don't set $search['prefix']
			}
		}


		if (isset($filters['nodeid']) AND intval($filters['nodeid']) > 0)
		{
			$search['channel'] = $filters['nodeid'];
		}

		switch ($filters['view'])
		{
			// "Photos" tab. Special handling.
			case 'media':
				// Photos tab handling does not use search options, see actionGet();
				// Only reason it passes through this function is to keep the $filter['q'] handling
				// above the same for regression risk.
				return $search;
				break;

			// topic view is used by channeldisplay.
			case 'topic':
				$search['view'] = vB_Api_Search::FILTER_VIEW_TOPIC;
				$search['exclude_sticky'] = true;
				$search['nolimit'] = !empty($filters['nolimit']);
				if (!empty($filters['depth']))
				{
					$search['depth'] = $filters['depth'];
				}
				$search['depth_exact'] = !empty($filters['depth_exact']);
				break;

			//Channel view is the same with Activity view except that Channel view's search scope is within
			//that channel only as specified by the channel nodeid in the 'channel' filter
			case 'channel':
				$search['include_sticky'] = true;
				// drop through to 'activity'

			case 'activity':
				//Per Product, if New Topics filter in activity stream is ON, display latest starters only.
				//if OFF, display latest starter, reply or comment per topic
				if (isset($filters['filter_new_topics']) AND $filters['filter_new_topics'] == '1')
				{
					$search['starter_only'] = true;
				}
				$search['view'] = vB_Api_Search::FILTER_VIEW_ACTIVITY;
				break;

			case 'stream':
				$search['view'] = vB_Api_Search::FILTER_VIEW_CONVERSATION_STREAM;
				$search['include_starter'] = true;
				// Stream view used to show starters at the top even though
				// it used created DESC sorting.
				// That was never intentional, and WFs show starters at the bottom.
				// ATM there's no "sorting" UI for thread/stream views, so
				// the expected sorting is default created DESC for stream view.
				// So we won't check & change the isstarter sorting here.
				$search['sort']['isstarter'] = 'asc';
				$search['sort']['created'] = 'desc';
				$search['depth'] = 2;
				break;

			case 'thread':
				$search['view'] = vB_Api_Search::FILTER_VIEW_CONVERSATION_THREAD;
				$search['include_sticky'] = true;
				$search['include_starter'] = true;
				// This is viewing a single thread, so show starters first.
				// ATM there's no "sorting" UI for thread/stream views, so
				// the expected sorting is default created ASC for thread view
				// So we won't check & change the isstarter sorting here.
				$search['sort']['isstarter'] = 'desc';
				$search['sort']['created'] = 'asc';
				/*
					TODO: The "include_sticky" above (which I'm not sure is even
					correct or necessary when we're looking at a single topic thread)
					causes some additional sorting, including a "closure.depth ASC"
					that makes isstarter sorting pointless.
					But I think it's "more correct" to have the isstarter sorting here,
					especially if it turns out the include_sticky isn't supposed to be
					there.
				 */
				$search['depth'] = 1;
				$search['nolimit'] = !empty($filters['nolimit']);
				if ($filters['q'])
				{
					$search['view'] = vB_Api_Search::FILTER_VIEW_CONVERSATION_THREAD_SEARCH;
				}
				$incrementNodeview = true;
				break;

			case 'article':
				/*
				Keep these defaults in sync with widget_cmschanneldisplay template's
				articleOptions array
				 */
				// 'channel' is set above.
				$search['starter_only'] = true;
				$search['nolimit'] = $filters['nolimit'] ?? true;
				// this indirectly comes from $widgetConfig['include_subcategory_content']
				$search['depth'] = $filters['depth'] ?? 0;
				// Note, we cannot use FILTER_VIEW_ACTIVITY for article streams as
				// that removes unpublished articles from the view. We can't remove
				// unpublished filtering from the search code without inadvertently
				// affecting forum stream view.
				// There is no "article" view implementation in search atm.

				// Default sorting is handled in the else block below.

				break;
		}

		if (!empty($filters[vB_Api_Node::FILTER_DEPTH]))
		{
			$search['depth'] = intval($filters[vB_Api_Node::FILTER_DEPTH]);
		}

		if (isset($filters['filter_sort']))
		{
			switch($filters['filter_sort'])
			{
				case vB_Api_Node::FILTER_SORTFEATURED:
					$search['featured'] = 1;
					break;
				case vB_Api_Node::FILTER_SORTPOPULAR:
					$search['sort']['votes'] = 'desc';
					break;
				case vB_Api_Node::FILTER_SORTOLDEST:
					if (isset($filters['view']) AND $filters['view'] == 'topic')
					{
						$search['sort']['lastcontent'] = 'asc';
					}
					else
					{
						$search['sort']['created'] = 'asc';
					}
					break;
				case vB_Api_Node::FILTER_SORTMOSTRECENT:
				default:
					if (empty($filters['filter_order']))
					{
						$filters['filter_order'] = 'desc';
					}

					if (isset($filters['view']) AND $filters['view'] == 'topic')
					{
						$search['sort'][$filters['filter_sort']] = $filters['filter_order'];
					}
					else
					{
						$search['sort']['created'] = 'desc';
					}
					break;
			}
		}
		else
		{
			switch ($filters['view'])
			{
				case 'thread':
					$search['sort']['created'] = 'asc';
					break;
				case 'topic':
				case 'activity':
					$search['sort']['lastcontent'] = 'desc';
					break;
				case 'article':
					$search['sort']['publishdate'] = 'DESC';
					break;
				default:
					break;
			}
		}


		if (isset($filters['checkSince']) AND is_numeric($filters['checkSince']))
		{
			$search['date']['from'] = $filters['checkSince'] + 1;
		}
		elseif (isset($filters['date']) OR isset($filters['filter_time']))
		{
			$date_filter = empty($filters['date']) ? $filters['filter_time'] : $filters['date'];
			switch($date_filter)
			{
				case 'time_today':
					$search['date']['from'] = 'lastDay';//vB_Api_Search::FILTER_LASTDAY;
				break;
				case 'time_lastweek':
					$search['date']['from'] = 'lastWeek';//vB_Api_Search::FILTER_LASTWEEK;
				break;
				case 'time_lastmonth':
					$search['date']['from'] = 'lastMonth';//vB_Api_Search::FILTER_LASTMONTH;
				break;
				case 'time_lastyear':
					$search['date']['from'] = 'lastYear';//vB_Api_Search::FILTER_LASTYEAR;
				break;
				case 'time_all':
				default:
					$search['date'] = 'all';
				break;
			}
		}

		if (isset($filters[vB_Api_Node::FILTER_SHOW]) AND strcasecmp($filters[vB_Api_Node::FILTER_SHOW], vB_Api_Node::FILTER_SHOWALL) != 0)
		{
			$search['type'] = $filters[vB_Api_Node::FILTER_SHOW];
		}

		if (isset($filters['filter_has_answer']))
		{
			// -1: any, 0: unanswered-only, 1: answered-only
			if (is_numeric($filters['filter_has_answer']) AND $filters['filter_has_answer'] >= 0)
			{
				$search['has_answer'] = ($filters['filter_has_answer'] > 0);
			}
		}

		if (!empty($filters['context']) AND $filters['context'] === "channeldisplay")
		{
			$search['context'] = $filters['context'];
		}

		return $search;
	}

	public function actionGet()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		$filters = isset($_POST['filters']) ? $_POST['filters'] : [];

		if (empty($filters['view']))
		{
			$result = ['error' => 'invalid_request'];
			$this->sendAsJson($result);
			return;
		}

		$search = [];
		$stickySearchOptions = ['sticky_only' => 1];
		$stickynodes = [];
		$incrementNodeview = false; // call node API's incrementNodeview if we're viewing a thread

		$search = self::mapFormFiltersToSearchOptions($filters);

		if ($filters['view'] == 'media')
		{
			// "Photos" tab. Special handling.
			return $this->processMediaView($filters);
		}

		// TODO: Should this be part of mapFormFiltersToSearchOptions() ?
		$search['ignore_protected'] = 1;

		if (!empty($search['context']) AND $search['context'] === "channeldisplay")
		{
			$nodes = Api_InterfaceAbstract::instance()->callApi('search', 'getChannelTopics', [
				$search,
				empty($filters['per-page']) ? false : $filters['per-page'],
				empty($filters['pagenum']) ? false : $filters['pagenum']
			]);

		}
		else
		{
			$nodes = Api_InterfaceAbstract::instance()->callApi('search', 'getInitialResults', [
				$search,
				empty($filters['per-page']) ? false : $filters['per-page'],
				empty($filters['pagenum']) ? false : $filters['pagenum'],
				true
			]);
		}



		if ($incrementNodeview)
		{
			Api_InterfaceAbstract::instance()->callApi('node', 'incrementNodeview', [$filters['nodeid']]);
		}

		if (!empty($nodes) AND !empty($nodes['errors']))
		{
			$result = ['error' => $nodes['errors'][0][0]];
			$this->sendAsJson($result);
			return;
		}

		//the same selected search filters except 'exclude_sticky' should also be applied when fetching sticky topics
		if (($filters['view'] == 'topic') AND (empty($filters['pagenum']) OR $filters['pagenum'] == 1 OR vB::getDatastore()->getOption('showstickies')))
		{
			$stickySearchOptions = array_merge($search, $stickySearchOptions);
			unset($stickySearchOptions['exclude_sticky']);
			// todo... is it ok if all view = 'topic' go through getChannelTopics ?

			if (!empty($filters['context']) AND $filters['context'] === "channeldisplay")
			{
				$stickynodes = Api_InterfaceAbstract::instance()->callApi('search', 'getChannelTopics', [
					$stickySearchOptions,
					0, // perpage, 0 means "infinite" (999 in reality)
					1, // page
					true // skipCount
				]);
			}
			else
			{
				$stickynodes = Api_InterfaceAbstract::instance()->callApi('search', 'getInitialResults', [$stickySearchOptions]);
			}
		}

		if (empty($filters['maxpages']))
		{
			$filters['maxpages'] = 0;
		}

		switch($filters['view'])
		{
			case 'activity':
				$result = $this->processActivityStream($nodes, true, $filters['maxpages']);
				break;

			case 'thread':
			case 'stream':
				$result = $this->processConversationDetail($nodes, $filters, $filters['maxpages'], $search);
				break;

			case 'topic':
				$result = $this->processTopics($nodes, $stickynodes, $filters['maxpages']);
				break;

			case 'article':
				$articleDisplayColumns = !empty($_POST['article_display_columns']) ? strval($_POST['article_display_columns']) : '';
				$result = $this->processArticles($nodes, $filters['maxpages'], $articleDisplayColumns);
				break;

			case 'channel':
			default:
				$result = $this->processActivityStream($nodes, false, $filters['maxpages']);
				break;
		}

		if (!$result['lastDate'])
		{
			$result['lastDate'] = time();
		}

		$this->sendAsJson($result);
	}

	private function processMediaView($filters)
	{
		if (empty($filters['nodeid']))
		{
			$this->sendAsJson(['error' => 'invalid_request']);
			return;
		}
		$nodeid = intval($filters['nodeid']);
		$filter_time = $filters['filter_time'] ?? 'time_all';
		$filter_show = $filters['filter_show'] ?? 'show_all';
		$perpage = intval($filters['per-page'] ?? 7);
		$pagenum = intval($filters['pagenum'] ?? 1);
		/*
			Special sorting for the conversation display's photos tab. Based on the channel's
			photos tab & the "Posted Photos" pseudo album, the default sorting is by the photo's
			publishdate DESC, nodeid ASC (not sure why this ordering). So it's a activity-stream
			-esque sorting.
			Per demo feedback, we want this to reflect the default Posts tab (thread view).
			Per the widget_conversationdisplay template, the thread view's sorting order is
				{vb:set sortOption.isstarter, 'DESC'}
				{vb:set sortOption.created, 'ASC'}
			but we want this sorting to be applied to the photo or attachment's PARENT node
			that's actually the "content" node.
		 */
		$sort = 'thread';

		/*
			Duplicating action from widget_conversationdisplay
			{vb:set mediaFilters.nodeid, -2}
			{vb:set mediaFilters.page, {vb:raw page.pagenum}}
			{vb:set mediaFilters.perpage, {vb:raw resultsPerPage}}
			{vb:set mediaFilters.userid, 0}
			{vb:set mediaFilters.channelid, {vb:raw nodeid}}
			{vb:set mediaFilters.dateFilter, {vb:raw filter_time}}
			{vb:data albums, profile, getAlbum, {vb:raw mediaFilters}}
			{vb:data albumDisplay, profile, getAlbumDisplayConditions, {vb:raw mediaFilters}}
			{vb:set pagingInfo, {vb:raw albums.-2.pagenav}}
			{vb:set totalCount, {vb:raw pagingInfo.totalcount}}
			...
			{vb:template conversation_media,
				nodeid={vb:raw nodeid},
				filter_time={vb:raw filter_time},
				pageno={vb:raw page.pagenum},
				perpage={vb:raw resultsPerPage},
				nodes={vb:raw albums},
				display={vb:raw albumDisplay}
			}
		 */
		$mediaFilters = [
			'nodeid' => -2,
			'page' => $pagenum,
			'perpage' => $perpage,
			'userid' => 0,
			'channelid' => $nodeid,
			'dateFilter' => $filter_time,
			'showFilter' => $filter_show,
			'sort' => $sort,
		];
		$api = Api_InterfaceAbstract::instance();
		$albums = $api->callApi('profile', 'getAlbum', [$mediaFilters]);
		$pagingInfo = $albums[-2]['pagenav'] ?? ['totalcount' => 0];
		$albumDisplay = $api->callApi('profile', 'getAlbumDisplayConditions', [$mediaFilters]);
		// Keep this in sync with the {vb:template conversation_media } call in
		// widget_conversationdisplay template
		$templateData = [
			'channelid'   => $nodeid,
			'filter_time' => $filter_time,
			'page'        => $pagenum,
			'perpage'     => $perpage,
			'nodes'       => $albums,
			'display'     => $albumDisplay,
			'sort'        => $sort,
		];

		// defaults taken from processActivityStream(), not sure which are required.
		$result = [
			'total'				=> 0,
			'total_with_sticky'	=> 0,
			'lastDate'			=> 0,
			'template'	=>		'',
			'pageinfo'	=>		[
				'pagenumber'	=> 1,
				'totalpages'	=> 1,
			],
			'css_links' => []
		];


		$templater = new vB5_Template('conversation_media');
		foreach ($templateData AS $__key => $__data)
		{
			$templater->register($__key, $__data);
		}

		$result['template'] .= "\n" . $templater->render(true, true) . "\n";
		$result['total'] = $pagingInfo['totalcount']; // required for conversation_filter to show this.
		/*
		pagingInfo: Array
		(
			[previous] => 1
			[next] => 0
			[totalpages] => 2
			[currentpage] => 2
			[totalcount] => 20
		)

		 */
		$result['pageinfo']['pagenumber'] = $pagingInfo['currentpage'] ?? 1;
		$result['pageinfo']['totalpages'] = $pagingInfo['totalpages'] ?? 1;

		/*
		$result['total']++;
		$result['lastDate'] = max($result['lastDate'], $node['publishdate']);

		$result['pageinfo']['pagenumber'] = $nodes['pagenumber'];
		$result['pageinfo']['totalpages'] = (!empty($maxpages) AND $maxpages < $nodes['totalpages']) ? $maxpages : $nodes['totalpages'];
		$result['pageinfo']['resultId'] = isset($nodes['resultId']) ? $nodes['resultId'] : null;
		$result['total_with_sticky'] = $result['total'];
		$result['nodes'] = $nodes['results'];
		*/
		$result['css_links'] = vB5_Template_Stylesheet::instance()->getAjaxCssLinks();
		// request JS for lastrow resizing fixes in case rows/columns changed.
		$result['fixFlexGrid'] = true;

		return $this->sendAsJson($result);
	}

	protected function processActivityStream($nodes, $showChannelInfo, $maxpages = 0)
	{
		$result = [
			'total' => 0,
			'total_with_sticky'	=> 0,
			'lastDate' => 0,
			'template' => '',
			'pageinfo' => [
				'pagenumber' => 1,
				'totalpages' => 1,
			],
			'css_links' => [],
		];

		if (!isset($nodes['errors']) AND !empty($nodes['results']))
		{
			$api = Api_InterfaceAbstract::instance();
			$nodes['results'] = $api->callApi('node', 'mergeNodeviewsForTopics', [$nodes['results']]);

			foreach ($nodes['results'] AS $node)
			{
				if (empty($node['content']))
				{
					$conversation = $node;
				}
				else
				{
					$conversation = $node['content'];
				}

				$templateName = 'display_contenttype_conversationreply_' . $conversation['contenttypeclass'];
				$templater = new vB5_Template($templateName);
				$templater->register('conversation', $conversation);
				$templater->register('reportActivity', true);
				$templater->register('showChannelInfo', $showChannelInfo);
				$templater->register('currentNodeIsBlog', (bool)($conversation['channeltype'] == 'blog'));
				$templater->register('currentNodeIsArticle', (bool)($conversation['channeltype'] == 'article'));

				$result['template'] .= "\n" . $templater->render(true, true) . "\n";
				$result['total']++;
				$result['lastDate'] = max($result['lastDate'], $node['publishdate']);
			}
			$result['pageinfo']['pagenumber'] = $nodes['pagenumber'];
			$result['pageinfo']['totalpages'] = (!empty($maxpages) AND $maxpages < $nodes['totalpages']) ? $maxpages : $nodes['totalpages'];
			$result['pageinfo']['resultId'] = isset($nodes['resultId']) ? $nodes['resultId'] : null;
		}
		$result['total_with_sticky'] = $result['total'];
		$result['nodes'] = $nodes['results'];
		$result['css_links'] = vB5_Template_Stylesheet::instance()->getAjaxCssLinks();

		return $result;
	}

	/**
	 * Processes article results to prepare the rendered templates for returning.
	 *
	 * @param	array	Node search result
	 * @param	int	Max pages of results
	 * @param	string	Article display columns setting
	 *
	 * @return	array	Array of rendered results for display
	 */
	protected function processArticles($nodes, $maxpages = 0, $articleDisplayColumns = '')
	{
		$result = [
			'total'             => 0,
			'total_with_sticky' => 0,
			'lastDate'          => 0,
			'template'          => '',
			'pageinfo'          => [
				'pagenumber' => 1,
				'totalpages' => 1,
			],
			'css_links' => [],
		];

		if (!isset($nodes['errors']) AND !empty($nodes['results']))
		{
			$api = Api_InterfaceAbstract::instance();

			// add article views
			$nodes['results'] = $api->callApi('node', 'mergeNodeviewsForTopics', [$nodes['results']]);

			foreach ($nodes['results'] AS $node)
			{

				$result['total']++;
				$result['lastDate'] = max($result['lastDate'], $node['publishdate']);
			}

			// Render the list together
			$templater = new vB5_Template('widget_cmschanneldisplay_list');
			$templater->register('nodes', $nodes['results']);
			$templater->register('displayColumns', $articleDisplayColumns);
			$templater->register('reportActivity', true);
			$templater->register('showChannelInfo', false);
			$templater->register('currentNodeIsBlog', false);
			$templater->register('currentNodeIsArticle', true);

			$result['template'] = $templater->render(true, true);

			$result['pageinfo']['pagenumber'] = $nodes['pagenumber'];
			$result['pageinfo']['totalpages'] = (!empty($maxpages) AND $maxpages < $nodes['totalpages']) ? $maxpages : $nodes['totalpages'];
			$result['pageinfo']['resultId'] = isset($nodes['resultId']) ? $nodes['resultId'] : null;
		}

		$result['total_with_sticky'] = $result['total'];
		$result['nodes'] = $nodes['results'];
		$result['css_links'] = vB5_Template_Stylesheet::instance()->getAjaxCssLinks();

		return $result;
	}

	private function processConversationDetail($nodes, $filters, $maxpages, $searchOptions)
	{
		$view = $filters['view'];
		$result = [
			'total' => 0,
			'lastDate' => 0,
			'template' => '',
			'pageinfo' => [
				'pagenumber' => 1,
				'totalpages' => 1,
			],
			'css_links' => []
		];
		$api = Api_InterfaceAbstract::instance();
		if (!isset($nodes['errors']) AND !empty($nodes['results']))
		{
			$showInlineMod = ($view == 'thread');
			if ($view == 'thread')
			{
				$showInlineMod =  true;
				$templateSuffix = 'starter';
				$pagingInfo = [
					'currentpage'	=> $filters['pagenum'],
					'perpage'		=> $filters['per-page']
				];
			}
			else
			{
				$showInlineMod = false;
				$templateSuffix = 'reply';
				$pagingInfo = [];
			}
			$baseTemplateName = 'display_contenttype_' . ($view == 'stream' ? 'conversation%s_' : 'conversation%s_threadview_');
			$postIndex = 0;
			$signatures = [];
			foreach ($nodes['results'] AS $node)
			{
				$signatures[$node['userid']] = $node['content']['signature'];
			}

			$parsed_signatures = $api->callApi('bbcode', 'parseSignatures', [array_keys($signatures), $signatures]);
			foreach ($nodes['results'] AS $node)
			{
				$updateIndex = true;
				$templateName = $baseTemplateName;

				if (!empty($parsed_signatures[$node['userid']]) AND !empty($node['content']['canSign']))
				{
					$node['content']['parsedSignature'] = $parsed_signatures[$node['userid']];
				}

				if ($node['content']['starter'] == $node['content']['nodeid'])
				{
					$templateName = sprintf($templateName, $templateSuffix);
					$templateName .= $node['content']['contenttypeclass'];
					$conversation = $node['content'];
					$postIndex = 1;
					$updateIndex = false;

					$conversation['can_use_multiquote'] = $this->canUseMultiquote($node['content']['starter']);
				}
				elseif ($view == 'thread' AND $node['content']['parentid'] != $node['content']['starter'])
				{
					//we don't need comments for thread view
					continue;
				}
				else
				{
					$templateName = sprintf($templateName, 'reply');
					$templateName .= $node['content']['contenttypeclass'];
					$conversation = $node['content'];

					$conversation['can_use_multiquote'] = $this->canUseMultiquote($node['content']['starter']);
				}
				$convType = ($node['nodeid'] == $node['starter']) ? 'starter' : 'reply';
				// Keep this in sync with widget_conversationdisplay template.
				// Note, currently the activity stream for topic pages go through this method as well, so the
				// hook data is also provided for pagination of both thread & stream view for topic pages.
				$hookdata_post = [
					'context'          => $filters['hookcontext'],
					'view'             => $view,
					'channeltype'      => $conversation['channeltype'],
					'contenttype'      => strtolower($conversation['contenttypeclass']),
					'postIndex'        => $postIndex,
					'showopen'         => $conversation['showopen'],
					'showpublished'    => $conversation['showpublished'],
					'showapproved'     => $conversation['showapproved'],
					'conversationtype' => $convType,
					'commentId'        => 0,
					'isPinnedAnswer'   => 0,
					'pagingInfo' => [
						// $pagingInfo is only populated for thread view above for some reason.
						'currentpage'  => $filters['pagenum'],
						'perpage'      => $filters['per-page']
					],
					'conversation'     => $conversation,
				];

				$templater = new vB5_Template($templateName);
				$templater->register('nodeid', $conversation['nodeid']);
				$templater->register('conversation', $conversation);
				$templater->register('reportActivity', false);
				$templater->register('showInlineMod', $showInlineMod);
				$templater->register('pagingInfo', $pagingInfo);
				$templater->register('view', $view);
				$templater->register('hookdata_post', $hookdata_post);
				if ($conversation['unpublishdate'])
				{
					$templater->register('hidePostIndex', true);
					$templater->register('postIndex', null);
				}
				else
				{
					$templater->register('postIndex', $postIndex);
					if ($updateIndex)
					{
						$postIndex++;
					}
				}

				$result['template'] .= "\n" . $templater->render(true, true) . "\n";
				// Add answers under starter when in thread view.
				// keep this in sync with widget_conversationdisplay template
				if ($view == 'thread' AND $node['starter'] == $node['nodeid'])
				{
					$check = self::getPinnedRepliesTemplateRender($node, $searchOptions);
					if (!empty($check['template']))
					{
						$result['template'] .= $check['template'];
					}
				}
				$result['css_links'] = vB5_Template_Stylesheet::instance()->getAjaxCssLinks();
				$result['total']++;
				$result['lastDate'] = max($result['lastDate'], $node['publishdate']);
			}
			$result['pageinfo']['pagenumber'] = $nodes['pagenumber'];
			$result['pageinfo']['totalpages'] = (!empty($maxpages) AND $maxpages < $nodes['totalpages']) ? $maxpages : $nodes['totalpages'];
			$result['pageinfo']['resultId'] = isset($nodes['resultId']) ? $nodes['resultId'] : null;
		}
		$result['total_with_sticky'] = $result['total'];
		return $result;
	}

	public static function getPinnedRepliesTemplateRender($starter, $searchOptions, $getAjaxCssLinks = false)
	{
		$rendered = '';
		$api = Api_InterfaceAbstract::instance();
		$pinnedReplies = $api->callApi('node', 'getPinnedRepliesFullContent', [$starter, $searchOptions]);
		foreach ($pinnedReplies['nodes'] AS $pinnedReply)
		{
			// keep this in sync with widget_conversationdisplay template
			$templateName = 'display_contenttype_conversationreply_threadview_' . $pinnedReply['content']['contenttypeclass'];
			// skipping multiquote logic for pins... if we need this fix the template too.
			//$pinnedReply['content']['can_use_multiquote'] = $this->canUseMultiquote($pinnedReply['content']['starter']);

			// Set up template passthrough controls
			$templatehints = [
				'threadviewHeaderControls' => [
					'showAnswerLabel' => 1,
					'nopostanchor' => 1,
					'showgotopostlink' => 1,
					'additionalClasses' => ' pinned-answer ',
				],
				'threadviewFooterControls' => [
					'footerControlsOverride' => [
						'showEditCtrl' => 0,
						'showCommentCtrl' => 0,
					],
				]
			];
			$pinnedReply['content']['templatehints'] = $templatehints;


			$templater = new vB5_Template($templateName);
			$templater->register('nodeid', $pinnedReply['nodeid']);
			$templater->register('conversation', $pinnedReply['content']);
			$templater->register('reportActivity', false);
			// showInlineMod is set to true for 'thread' view in processConversationDetail(),
			// but we may want to just hide inline mod for pinned replies in the future...
			$templater->register('showInlineMod', true);
			// pagingInfo in these templates seem to be used for
			// some postindex / commentindex logic, which we are not using.
			$templater->register('pagingInfo', null);
			$templater->register('view', 'thread');
			$templater->register('hidePostIndex', true);
			$templater->register('hideCommentPostIndex', true);
			$templater->register('postIndex', null);
			$pinnedReplyRender = $templater->render(true, true);
			$rendered .= "\n" . $pinnedReplyRender . "\n";
		}

		$css_links = '';
		if ($getAjaxCssLinks)
		{
			$css_links = vB5_Template_Stylesheet::instance()->getAjaxCssLinks();
		}

		return [
			'template' => $rendered,
			'css_links' => $css_links,
		];
	}

	//designed to duplicated some logic in the widget_conversationdisplay template that updates a flag on the nodes used
	//by the conversation_footer template.  This really needs to be pushed back on the node API, but that's a riskier fix
	protected function canUseMultiquote($starterid)
	{
		static $cache = [];

		if (!isset($cache[$starterid]))
		{
			$api = Api_InterfaceAbstract::instance();
			$starter = $api->callApi('node', 'getNodeFullContent', [$starterid]);

			if (!isset($starter['error']))
			{
				$cache[$starterid] = ($starter[$starterid]['canreply'] AND ($starter[$starterid]['channeltype'] != 'blog'));
			}
			else
			{
				//explicitly handle the error case.  This is unlikely and throwing an error here would be bad.
				//so we'll ignore it and just return false as the safest behavior.
				$cache[$starterid] = false;
			}
		}

		return $cache[$starterid];
	}

	protected function processTopics($nodes, $stickynodes, $maxpages = 0)
	{
		$result = [
			'total' => 0,
			'total_with_sticky' => 0,
			'lastDate' => 0,
			'template' => '',
			'pageinfo' => [
				'pagenumber'	=> 1,
				'totalpages'	=> 1,
			],
			'css_links' => [],
		];

		$templater = new vB5_Template('display_Topics');
		$canmoderate  = false;
		if (!isset($nodes['errors']) AND !empty($nodes['results']))
		{
			foreach ($nodes['results'] AS $key => $node)
			{
				//only include the starter
				if ($node['content']['contenttypeclass'] == 'Channel' OR $node['content']['starter'] != $node['content']['nodeid'])
				{
					unset($nodes['results'][$key]);
				}
				else
				{
					$result['lastDate'] = max($result['lastDate'], $node['content']['publishdate']);
				}
				if (!empty($node['content']['permissions']['canmoderate']) AND !$canmoderate)
				{
					$canmoderate = 1;
					$templater->register('canmoderate', $canmoderate);
				}
			}

			$templater->register('topics', $nodes['results']);

			$result['total_with_sticky'] = $result['total'] = count($nodes['results']);
			$result['pageinfo']['pagenumber'] = $nodes['pagenumber'];
			$result['pageinfo']['totalpages'] = (!empty($maxpages) AND $maxpages < $nodes['totalpages']) ? $maxpages : $nodes['totalpages'];
			$result['pageinfo']['resultId'] = isset($nodes['resultId']) ? $nodes['resultId'] : null;
		}
		elseif (isset($nodes['errors']))
		{
			$templater->register('topics', $nodes);
		}

		if (!isset($stickynodes['errors']) AND !empty($stickynodes['results']))
		{
			$result['total_with_sticky'] = $result['total'] + count ($stickynodes['results']);
			$sticky_templater = new vB5_Template('display_Topics');
			$sticky_templater->register('topics', $stickynodes['results']);
			$sticky_templater->register('topic_list_class', 'sticky-list');

			if (!$canmoderate AND empty($nodes['results']))
			{
				//It is safe to assume that if user has canmoderate permission for the first topic node in a forum,
				//he/she has the same permission for all the nodes.
				$firstTopic = reset($stickynodes['results']);
				$canmoderate = $firstTopic['content']['permissions']['canmoderate'];
			}
			$sticky_templater->register('canmoderate', $canmoderate);

			$result['template'] .= "\n" . $sticky_templater->render() . "\n";
			$templater->register('no_header', 1);
		}

		if (!empty($nodes['results']) OR empty($stickynodes['results']))
		{
			$result['template'] .= "\n" . $templater->render(true, true) . "\n";
			$result['css_links'] = vB5_Template_Stylesheet::instance()->getAjaxCssLinks();
		}
		return $result;
	}


	/**
	 * This gets nodeText for a single node.
	 */
	public function actionfetchtext()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		if (empty($_REQUEST['nodeid']) OR !intval($_REQUEST['nodeid']))
		{
			return '';
		}
		$api = Api_InterfaceAbstract::instance();
		$nodeHandler = vB5_Template_NodeText::instance();
		$nodeText = $nodeHandler->fetchOneNodeText(intval($_REQUEST['nodeid']), $api);

		//make sure that we don't get any weird null results.
		if (empty($nodeText))
		{
			$nodeText = '';
		}

		$this->sendAsJson(['nodeText' => $nodeText]);
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 115567 $
|| #######################################################################
\*=========================================================================*/
