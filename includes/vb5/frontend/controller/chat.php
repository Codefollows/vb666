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

class vB5_Frontend_Controller_Chat extends vB5_Frontend_Controller
{
	private $api;
	private $pmChannelId;

	function __construct()
	{
		parent::__construct();

		//the api init can redirect.  We need to make sure that happens before we echo anything
		$this->api = Api_InterfaceAbstract::instance();
		$this->pmChannelId = $this->api->callApi('node', 'fetchPMChannel', []);
	}

	private function check($pmthreadid)
	{
		$api = $this->api;

		// Must be logged into send messages.
		$currentUser = vB5_User::get('userid');
		if (empty($currentUser))
		{
			vB5_ApplicationAbstract::handleNoPermission(true);
		}

		/*
			canUsePMChat will check if user's loged in, chat is globally enabled, AND usergroup's allowed to use chat.
		 */
		$check = $api->callApi('pmchat', 'canUsePMChat', []);
		if (empty($check['canuse']))
		{
			return $this->showErrorPageLocal($check['reason']);
		}

		// If they requested a thread, can they view that thread?
		if (!empty($pmthreadid) AND $pmthreadid !== $this->pmChannelId)
		{
			$check = $api->callApi('pmchat', 'isMessageParticipant', [$pmthreadid]);
			if (!$check['result'])
			{
				return $this->showErrorPageLocal('Invalid message id.');
			}
		}
	}

	//this is incompatible with the base class error page function in ways that's going to cause us
	//problems as we tighten type hinting so rename it to it's own thing.  However we *should* be
	//using that function or at least the application base class function.  However
	//1) We pass raw strings instead of phrases -- we still pass this through the phrase interface
	//	which works because if we don't match a phraseid we return the string verbatim.  But it's dodgey.
	//2) We set noindex/nofollow values which the base error function doesn't. Perhaps it should.  But
	//	ultimately seems like it should be consistent.
	private function showErrorPageLocal($message)
	{
		$page = ['noindex' => true, 'nofollow' => true];
		$templater = new vB5_Template('error_page');
		$templater->registerGlobal('page', $page);
		$templater->register('error', ['message' => $message]);

		$output = vB5_ApplicationAbstract::getPreheader() . $templater->render();
		echo $output;
		exit; // don't show anything else.
	}

	public function index()
	{
		$router = vB5_ApplicationAbstract::instance()->getRouter();
		$queryParameters = $router->getQueryParameters();
		/*
			- check if current user can use chat.
			- check if current user is a participant of $pm_threadid
		 */
		$this->check($queryParameters['messageid'] ?? 0);

		$api = $this->api;

		$top = '';
		// No caching for the chat pages.

		$preheader = vB5_ApplicationAbstract::getPreheader();
		$top .= $preheader;

		if (vB5_Request::get('useEarlyFlush'))
		{
			//we may want to create PHP sessions at some point but we don't know yet
			//and this is our last change to initalize it properly.  Creating the
			//session is likley less overhead than figuring out if we need to
			//and we'd like to expand the user of PHP sessions in the future.
			if (session_status() == PHP_SESSION_NONE)
			{
				session_start();
			}

			echo $preheader;
			flush();
		}

		$arguments = $router->getArguments();
		//$userAction = $router->getUserAction(); // No logging useraction/wol for chat pages.

		$pageKey = $router->getPageKey();
		$api->callApi('page', 'preload', [$pageKey]);

		/*
			Todo: pagination for chat pages???
		 */
		if (isset($arguments['pagenum']))
		{
			$arguments['pagenum'] = intval($arguments['pagenum']) > 0 ? intval($arguments['pagenum']) : 1;
		}
		$pageid = (int) (isset($arguments['pageid']) ? $arguments['pageid'] : (isset($arguments['contentid']) ? $arguments['contentid'] : 0));

		if ($pageid < 1)
		{
			// @todo This needs to output a user-friendly "page not found" page
			throw new Exception('Could not find page.');
		}

		$page = $api->callApi('page', 'fetchPageById', [$pageid, $arguments]);
		if (!$page)
		{
			// @todo This needs to output a user-friendly "page not found" page
			throw new Exception('Could not find page.');
		}

		$templateVars = $this->getTemplateVars($queryParameters);
		if (isset($templateVars['channelid']) AND isset($templateVars['nodeid']) AND $templateVars['channelid'] != $templateVars['nodeid'])
		{
			/*
				The bare_header & header templates have some rules about setting the title tag.
				If page.nodeid AND channelid are given, and they're different, it'll fetch the nodeid as "conversation" & use its starter's htmltitle for the
				title tag without escaping it, then append it with " - " and escaped bbtitle.
				Otherwise, if page.title is set it'll use the title but escape it, then append it with " - " and escaped bbtitle.
				If neither path is an option, it'll just use the escaped bbtitle by itself.
				PM starters are not channels, so any html entites in the titles are auto-escaped by the content API. This means that we do not want to double
				escape these entities, thus we need to go with the first option.
			 */
			$page['channelid'] = $templateVars['channelid'];
			$page['nodeid'] = $templateVars['nodeid'];
		}

		if (isset($templateVars['pm_title']))
		{
			// Use the autogenerated title. Which hopefully won't ever have HTML in it that needs to stay unescaped.
			$page['title'] = $templateVars['pm_title'];
		}

		$page['routeInfo'] = [
			'routeId' => $router->getRouteId(),
			'arguments'	=> $arguments,
			'queryParameters' => $queryParameters
		];
		$page['crumbs'] = $router->getBreadcrumbs();
		$page['headlinks'] = $router->getHeadLinks();
		$page['pageKey'] = $pageKey;

		// default value for pageSchema
		$page['pageSchema'] = 'WebPage';

		/*
		 *	VBV-12506
		 */
		$doNotReplaceWithQueryParams = [
			'titleprefix',
			'title',
			'pageid',
			'channelid',
			'nodeid',
			'pagetemplateid',
			'url',
			'pagenum',
			'tagCloudTitle',
		];
		foreach ($doNotReplaceWithQueryParams AS $key)
		{
			unset($queryParameters[$key]);
		}

		$arguments = array_merge($queryParameters, $arguments);
		foreach ($arguments AS $key => $value)
		{
			$page[$key] = $value;
		}

		$options = vB5_Template_Options::instance();
		$page['phrasedate'] = $options->get('miscoptions.phrasedate');
		$page['optionsdate'] = $options->get('miscoptions.optionsdate');

		$page['metadescription'] = 'vBulletin Chat'; // we shouldn't be allowing robots at all.

		// Non-persistent notices @todo - change this to use vB_Cookie
		$page['ignore_np_notices'] = vB5_ApplicationAbstract::getIgnoreNPNotices();

		$templateCache = vB5_Template_Cache::instance();
		$templater = new vB5_Template($page['screenlayouttemplate']);

		// noindex, nofollow
		$page['noindex'] = true;
		$page['nofollow'] = true;

		$templater->registerGlobal('page', $page);
		foreach ($templateVars AS $key => $value)
		{
			$templater->registerGlobal($key, $value);
		}

		$templater->registerGlobal('skipSitebuilder', 1);
		$page['noindex'] = $page['nofollow'] = true;

		$page = $this->outputPage($templater->render(), false);
		$fullPage = $top . $page;

		// these are the templates rendered for this page
		$loadedTemplates = vB5_Template::getRenderedTemplates();

		if (!vB5_Request::get('useEarlyFlush'))
		{
			echo $fullPage;
		}
		else
		{
			echo $page;
		}
	}

	public function actionLoadNewMessages()
	{
		/*
			Copied mostly from createcontent's actionLoadNewNodes()
		 */

		// require a POST request for this action
		$this->verifyPostRequest();

		/*
			BEGIN >>> Clean Input <<<
		 */
		$input = [
			'parentid'              => intval($_POST['parentid'] ?? 0),	// form's parentid input. The topic starter.
			'newreplyid'            => intval($_POST['newreplyid'] ?? 0),
			'lastpublishdate'       => intval($_POST['lastpublishdate'] ?? 0),
			'lastnodeid'            => intval($_POST['lastnodeid'] ?? 0),

			'lastloadtime'          => intval($_POST['lastloadtime'] ?? 0),
			'pageload_servertime'   => intval($_POST['pageload_servertime'] ?? 0),

			'currentpage'           => intval($_POST['currentpage'] ?? 0),
			'pagetotal'             => intval($_POST['pagetotal'] ?? 0),
			'postcount'             => intval($_POST['postcount'] ?? 0),
			'postsperpage'          => intval($_POST['postsperpage'] ?? 10000),
			'past_page_limit_aware' => filter_var($_POST['past_page_limit_aware'] ?? false, FILTER_VALIDATE_BOOLEAN),
			'loadednodes'           => [], // Individually cleaned below
		];

		if (empty($input['parentid']))
		{
			if (!empty($input['newreplyid']))
			{
				$input['parentid'] = $input['newreplyid'];
			}
		}
		/*
			- check if current user can use chat.
			- check if current user is a participant of $pm_threadid
			- fetch new messages..
		 */
		$this->check($input['parentid']);

		if ($input['parentid'] == $this->pmChannelId)
		{
			$results = [];
			$results['success'] = false;
			$results['timenow'] = vB5_Request::get('timeNow');
			$this->sendAsJsonAndCloseConnection($results);
			return;
		}

		$addOneStarterExcludeFix = 0;
		// loadednodes - nodeids that are already on the page
		if (isset($_POST['loadednodes']))
		{
			$unclean['loadednodes'] = (array) $_POST['loadednodes'];
			foreach ($unclean['loadednodes'] AS $nodeid)
			{
				$nodeid = intval($nodeid);
				/*
					Currently, the "exclude" JSON results in a join like
					... LEFT JOIN closure AS exclude_closure ON ... exclude_closure.parent IN ({exclude list})
					... WHERE exclude_closure.child IS NULL ...
					which means that if we pass in the starter nodeid in the list, it'll exclude the entire thread,
					resulting in 0 results. A bit annoying, but this is the "workaround".
				*/
				if ($nodeid !== $input['parentid'])
				{
					$input['loadednodes'][$nodeid]  = $nodeid;
				}
				else
				{
					$addOneStarterExcludeFix = 1;
				}
				// hacky work around to make sure first node is skipped when posting too quickly. It *may* need to require parentid, which is why it's separate from just loadednodes.
				// Only time we don't skip the parentid is if this is the very first time it's called when starting a PM thread, in which case loadednodes would be empty.
				$skipNodes[$nodeid] = true;
			}
			unset($unclean);
		}

		// If we perform a search, we cache that for several seconds/minutes. Depending on
		// the timing/luck, this can get unusable (e.g. 1 min between message fetches even when
		// they're sending messages every few seconds)
		$starter = $this->api->callApi('node', 'getNode', ['nodeid' => $input['parentid']]);
		if (!isset($starter['errors']))
		{
			if ($starter['lastcontent'] <= $input['lastpublishdate'] AND
				$starter['lastcontentid'] == $input['lastnodeid']
			)
			{
				$results = [];
				$results['success'] = true;
				$results['timenow'] = vB5_Request::get('timeNow');
				$results['moreinfo'] = ['no new messages'];
				$this->sendAsJsonAndCloseConnection($results);
				return;
			}
		}


		// based on widget_conversationdisplay search options
		$search_json = [
			'date' => ['from' => $input['lastpublishdate'], ],
			//'date' => ['from' => $input['pageload_servertime']],	// test
			'channel' => $input['parentid'],	// parentid may not be a channel, but this is how the widget gets the data displayed.
			//'filter_show' => ???,	// TODO: should we filter "new posts" by current filter?
		];
		$search_json['view'] = 'thread';
		// thread
		$search_json['depth'] = 1;
		$search_json['view'] = 'conversation_thread';
		$search_json['sort']['created'] = 'ASC';
		$search_json['nolimit'] = 1; // TODO: remove this?
		$search_json['ignore_protected'] = 0; // Explicitly need to set this for Private Messages.
		if (!empty($input['loadednodes']))
		{
			$search_json['exclude'] = $input['loadednodes'];
		}
		$search_json = json_encode($search_json);

		$numAllowed = max($input['postsperpage'] - $input['postcount'], 0);
		// todo: remove detritus. Need to look into what usersNewReply used to be for.
		if (!empty($usersNewReply))
		{
			// Grab 2 extra *just* in case the one immediately after $numAllowed is the new reply
			$perpage = $numAllowed + 2 + $addOneStarterExcludeFix;
		}
		else
		{
			$perpage = $numAllowed + 1 + $addOneStarterExcludeFix;
		}

		$functionParams = [
			$search_json,
			$perpage,
			1, 	 //pagenum
		];
		$searchResult = $this->api->callApi('search', 'getInitialResults',  $functionParams);
		$newReplies = $searchResult['results'];
		$returnedNodeids = [];
		$html = '';
		foreach ($newReplies AS $node)
		{
			if (isset($skipNodes[$node['nodeid']]))
			{
				//	Edge case. if you *start* a new post and keep posting quickly, you'll get the first node.
				continue;
			}
			$returnedNodeids[$node['nodeid']] = $node['nodeid'];
			$html .= $this->renderSinglePostTemplate($node);
		}

		//	BEGIN	>>> Return results array <<<
		$results = [];
		$results['success'] = true;
		$results['timenow'] = vB5_Request::get('timeNow');
		$results['html'] = $html;
		$results['nodeids'] = $returnedNodeids;

		// CLOSE CONNECTION BEFORE WE DO SOME RESPONSE-UNRELATED BACKEND WORK
		$this->sendAsJsonAndCloseConnection($results);

		// END	>>> Return results array <<<

		$this->api->callApi('content_privatemessage', 'setRead', [$returnedNodeids, 1]);
		return;
	}


	private function renderSinglePostTemplate($node)
	{
		if (empty($node))
		{
			return '';
		}
		$template = 'pmchat_chatwindow__post_template';
		$templater = new vB5_Template($template);
		if (empty($node['username']) AND !empty($node['userid']))
		{
			$user = $this->api->callApi('user', 'getNamecardInfo', [$node['userid']]);
			// emulating getTemplateVars(). username is used for the tooltip for avatars,
			// but is not provided by default. node.authorname is the escaped displayname,
			// which isn't always the same as the username if enabledisplayname is on.
			$node['username'] = $user['info']['username'];
		}
		// emulating PM lib's getMessageTree()
		$node['senderAvatar'] = $node['content']['avatar'];
		$templater->register('message', $node);
		return $templater->render(true, true);
	}

	private function getTemplateVars($queryparams)
	{
		$api = $this->api;
		$templateVars = [
			'pm_action' => 'new',
			'participants' => [],
			'entry_show_title' => 0,
			'privateMessageAction' => 'reply',
			'can_add_participants' => false,
		];

		$phrases = [];

		/*
			Create new PM about some content
		 */
		if (!empty($queryparams['toUserid']) AND is_numeric($queryparams['toUserid']))
		{
			$templateVars['to_user']['userid'] = intval($queryparams['toUserid']);
			$user = $api->callApi('user', 'getNamecardInfo', [$templateVars['to_user']['userid']]);
			$user = $user['info'] ?? [];

			if (!empty($user['username']))
			{
				// pmchat is vb:vared as prefillTitle in contententry_title. Since usernames are already escaped,
				// this can cause double encoding. For similar reasons, we decode node titles below.
				$phrases['pm_title'] = ['chat_with_x', vB5_String::unHtmlSpecialChars($user['username'])];
				$templateVars['to_user']['username'] = $user['username']; // required for .msgRecipients autocomplete.
			}

			$templateVars['participants'][$user['userid']] = [
				'userid' => $user['userid'],
				'username' => $user['username'],
				'displayname' => $user['displayname'],
				'avatar' => $user['avatar'],
			];
			$templateVars['can_add_participants'] = true;
		}
		else
		{
			unset($queryparams['toUserid']);
		}

		if (!empty($queryparams['aboutNodeid']) AND is_numeric($queryparams['aboutNodeid']))
		{
			/*
				WARNING: An autogenerated title means we could leak titles of nodes that the recipient(s) cannot see,
				which is a permission violation.
				Should we check view perms for recipients? What should we do if one of the recipients shouldn't be able to
				see the node title?
			 */
			$node = $api->callApi('node', 'getNode', [$queryparams['aboutNodeid']]);
			$starter = $api->callApi('node', 'getNode', [$node['starter']]);
			if (empty($node['errors']))
			{
				/*
					This overrides above set title.
					Note, we use html_entity_decode here to avoid double escaping the html entities in the node titles.
					Only channels are currently allowed to have HTML unescaped in titles, so we can assume here that all entities are in the escaped form.
					At the moment pm_title is used in 2 places :
						1) this is set to page.title in this controller's index() function, which is escaped via vb:var & used in the <header><title> tag in bare_header template.
						2) escaped via vb:var & used as a data attribute to a helper div.js-pmchat__data in the pmchat_widget template. This data is then passed in as the title of
							the starter message into the PM API.
				 */
				$phrases['pm_title'] = ['chat_about_x', html_entity_decode($starter['title'], ENT_QUOTES)];

				$extra = ['p' => $node['nodeid']];
				$anchor = 'post' . $node['nodeid'];
				$nodeUrl = vB5_Route::buildUrl($starter['routeid'] . '|fullurl', $node, $extra, $anchor);
				$phrases['pm_textprefill'] = ['about_x', '<a target="_blank" href="' . $nodeUrl . '">' . $starter['title'] .'</a>'];
			}
		}


		/*
			Continue chatting, not create new PM.
		 */
		if (!empty($queryparams['messageid']) AND is_numeric($queryparams['messageid']))
		{
			$pmThread = $api->callApi('node', 'getNode', [$queryparams['messageid']]);
			$messageTree = $api->callApi('content_privatemessage', 'getMessage', [$queryparams['messageid']]);

			$currentUser = vB5_User::get('userid');
			$userids = array_column($messageTree['message']['recipients'], 'userid', 'userid');

			// in case URL query params had extra stuff and we had a toUserid, IGNORE IT & reset participants.
			$templateVars['participants'] = [];
			$users = $this->api->callApi('user', 'getNamecardInfoBulk', [$userids]);
			foreach ($users['infos'] AS $userData)
			{
				if ($userData['userid'] != $currentUser)
				{
					$templateVars['participants'][$userData['userid']] = [
						'userid' => $userData['userid'],
						'username' => $userData['username'],
						'displayname' => $userData['displayname'],
						'avatar' => $userData['avatar'],
					];
				}
			}

			if (empty($pmThread['errors']))
			{
				// todo: make sure user is part of this PM thread. It *should* be done via the api, but we should double check.
				$templateVars['pm_messageid'] = intval($queryparams['messageid']);
				$templateVars['pm_action'] = 'load_message'; // anything not == 'new'
				$templateVars['pm_title'] = $pmThread['title'];

				// see below about page titles.
				$templateVars['nodeid'] = $pmThread['starter'];

				/*
					Let's also unset some unnecessary stuff in case the query params were fubar and stuff was set above.
				 */
				unset($templateVars['to_user']);
				unset($phrases['pm_title']);
				unset($phrases['pm_textprefill']);

				// For some bizarre reason, getMessage() sets "starter" to true/false instead of a nodeid like every other node array structure
				// in other libs...
				// Let's just fetch the starter node separately rather than go through messages and try to find the starter (which I'm not even
				// sure is guaranteed in that list) that way.
				$starter = $this->api->callApi('node', 'getNode', [$pmThread['starter']]);
				if (!empty($starter['userid']) AND $starter['userid'] == $currentUser)
				{
					$templateVars['can_add_participants'] = true;
				}

			}
			// todo else error handling.
		}
		else
		{
			unset($queryparams['messageid']);
		}

		/*
			Create new PM, let user set title & participants
		 */
		if (empty($queryparams['toUserid']) AND empty($queryparams['messageid']))
		{
			$templateVars = [
				'pm_action' => 'new',
				'participants' => [],
				'entry_show_title' => 1,
				'privateMessageAction' => 'new',
				'can_add_participants' => true,
			];
		}
		else if (!empty($queryparams['toUserid']) AND empty($queryparams['aboutNodeid']) AND empty($queryparams['messageid']))
		{
			/*
				Create a new PM to a specified user, but not a completely prefilled/locked-in message.
			 */
			// set this false to disallow editing participants & title. Editing *just* the title requires 'contententry' template refactor that we've been trying to avoid
			// due to added complexity & bug risk.
			$allowTitleAndParticipantsEdit = true;
			if ($allowTitleAndParticipantsEdit)
			{
				$templateVars['pm_action'] = 'new';
				$templateVars['participants'] = [];
				$templateVars['entry_show_title'] = 1;
				$templateVars['privateMessageAction'] = 'new';
				$templateVars['can_add_participants'] = true;
			}
		}

		/*
			Always set channelid for purposes of setting page titles.
			At the moment, content API's cleanInput() escapes html entities in titles (except for override in channel content API).
			To get around double escaping html entities in our pm titles, we need to set the channel id & the conversation nodeid if available.
			Nodeid is set above when messageid is valid.
		 */
		$templateVars['channelid'] = $this->pmChannelId;


		// +1 for the current user, who's not listed in participants but displayed at the end by templates
		$templateVars['participants_count'] = count($templateVars['participants']) + 1;

		//batch phrase lookup
		$phrases = $api->callApi('phrase', 'renderPhrases', [$phrases]);
		if (!isset($phrases['errors']))
		{
			foreach ($phrases['phrases'] AS $key => $value)
			{
				$templateVars[$key] = $value;
			}
		}

		return $templateVars;
	}


	public function actionLoadHeaderData()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		$headerCounts = $this->api->callApi('content_privatemessage', 'getHeaderCounts', []);

		if (!empty($headerCounts['errors']))
		{
			return $this->sendAsJson($headerCounts);
		}

		$perpage = vB5_Template_Options::instance()->get('options.pmchat_dropdown_max');
		if (empty($perpage))
		{
			$perpage = 6; // default to 6
		}
		// todo: do we need to hardcode a max here or in option validation for sake of performance?

		$data = [
			'sortDir' => "DESC",
			'folderid' => $headerCounts['folderid_messages'],
			'pageNum' => 1,
			'perpage' => $perpage,
		];
		$messages = $this->api->callApi('content_privatemessage', 'listMessages', [$data]);

		/*
			BEGIN	>>> Return results array <<<
		 */
		$results = [];
		$results['success'] = true;
		$results['timenow'] = vB5_Request::get('timeNow');
		$results['headerCounts'] = $headerCounts;
		$results['messages'] = $messages;
		//$results['css_links'] = vB5_Template_Stylesheet::instance()->getAjaxCssLinks();
		// CLOSE CONNECTION BEFORE WE DO SOME RESPONSE-UNRELATED BACKEND WORK
		$this->sendAsJsonAndCloseConnection($results);

		// END	>>> Return results array <<<

		// Do any read-marking ETC here. ATM none is marked read as just fetching the header list should not mark any PM threads as read.
		return;
	}

	public function actionLoadParticipants()
	{
		/*
			TODO refactor createcontent/privatemessage so that it can
			return data instead of echoing it out, so we can add additional
			data before the ajax return & not require a 2nd request from browser.
		 */
		// require a POST request for this action
		$this->verifyPostRequest();

		/*
			BEGIN >>> Clean Input <<<
		 */
		$input = [
			'nodeid' => intval($_POST['nodeid'] ?? 0),	// form's parentid input. The topic starter.
		];

		$results = [];
		$results['success'] = true;
		$results['timenow'] = vB5_Request::get('timeNow');
		$results['participants_html'] = '';
		$results['phrase'] = '';
		if (empty($input['nodeid']))
		{
			$results['errors'] = "missing_nodeid";
			return $this->sendAsJson($results);
		}

		$messageTree = $this->api->callApi('content_privatemessage', 'getMessage', [$input['nodeid']]);
		if (!empty($messageTree['errors']))
		{
			$results['errors'] = $messageTree['errors'];
			return $this->sendAsJson($results);
		}

		if (empty($messageTree['message']['recipients']))
		{
			return $this->sendAsJson($results);
		}

		$results['title'] = $messageTree['message']['title'];

		$currentUser = vB5_User::get('userid');
		$userids = array_column($messageTree['message']['recipients'], 'userid', 'userid');

		$users = $this->api->callApi('user', 'getNamecardInfoBulk', [$userids]);
		$count = 1; // start at 1 for the current user.
		foreach ($users['infos'] AS $userData)
		{
			if ($userData['userid'] != $currentUser)
			{
				$participant = [
					'userid' => $userData['userid'],
					'username' => $userData['username'],
					'displayname' => $userData['displayname'],
					'avatar' => $userData['avatar'],
				];
				$results['participants_html'] .= $this->renderSingleParticipantTemplate($participant);
				$count++;
			}
		}

		/*
			For reducing JS complexity (is this a pre-filled new chat or a completely new chat?),
			add *every* participant including the current user at the end, and completely reload the inner HTML
		 */
		$currentUserInstance = vB5_User::instance();
		$avatar = $this->api->callApi('user', 'fetchAvatar', [$currentUserInstance['userid'], 'avatar']);
		$currentUserData = [
			'userid' => $currentUserInstance['userid'],
			'username' => $currentUserInstance['username'],
			'displayname' => $currentUserInstance['displayname'],
			'avatar' => $avatar,
		];
		$results['participants_html'] .= $this->renderSingleParticipantTemplate($currentUserData);

		$phrases['x_participants'] = ['x_participants', $count];
		$apiresult = $this->api->callApi('phrase', 'renderPhrases', [$phrases]);
		if (!isset($apiresult['errors']) AND !empty($apiresult['phrases']['x_participants']))
		{
			$results['phrase'] = $apiresult['phrases']['x_participants'];
		}

		return $this->sendAsJson($results);
	}

	private function renderSingleParticipantTemplate($participant)
	{
		//This really shouldn't happen
		if (!$participant['username'] OR !$participant['userid'] OR !$participant['avatar'])
		{
			return "";
		}
		$template = 'pmchat_chatwindow__participant_block';
		$templater = new vB5_Template($template);
		$templater->register('participant', $participant);
		$result = $templater->render(true, true);
		return $result;
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 114640 $
|| #######################################################################
\*=========================================================================*/
