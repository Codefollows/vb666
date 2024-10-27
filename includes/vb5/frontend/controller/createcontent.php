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

class vB5_Frontend_Controller_CreateContent extends vB5_Frontend_Controller
{
	/**
	 * Returns input needed to create the different content types, common to all
	 * types.  This is incomplete and mostly deals with the items used by the
	 * CMS to create articles.
	 *
	 * This function is a stop-gap measure to avoid a bunch of code duplication
	 * in the different content type functions in this class.  This should be updated
	 * to include all of the values common to all types as a first step to refactoring
	 * the class.
	 *
	 * This class needs a rewrite to normalize how the different content types are created,
	 * updated, and handled, and to reduce code duplication.
	 *
	 * @return	array	Array of input items
	 */
	//this appear to only *mostly* involve the Article fields.  This always gets called regardless
	//of article/not article status so if the control exists it will get included in the data
	protected function getArticleInput()
	{
		//I'm about 97% convinced that the trim(strval(...)) idiom (here and elsewhere) is a leftover
		//from when we didn't check that the key existed and so the value could sometimes be null.
		//The post values that exist should always be strings.
		$input = [
			'urlident'               => trim(strval($_POST['urlident'] ?? '')),
			'htmltitle'              => trim(strval($_POST['htmltitle'] ?? '')),
			'description'            => trim(strval($_POST['description'] ?? '')),
			'public_preview'         => trim(intval($_POST['public_preview'] ?? 0)),
			// Fields for -- CMS static HTML type
			'disable_bbcode'         => boolval($_POST['disable_bbcode'] ?? false),
			'hide_title'             => boolval($_POST['hide_title'] ?? false),
			'hide_author'            => boolval($_POST['hide_author'] ?? false),
			'hide_publishdate'       => boolval($_POST['hide_publishdate'] ?? false),
			'display_fullincategory' => boolval($_POST['display_fullincategory'] ?? false),
			'display_pageviews'      => boolval($_POST['display_pageviews'] ?? false),
			'hide_comment_count'     => boolval($_POST['hide_comment_count'] ?? false),
		];

		//enable/disable article comments -- this is now used generally
		//do not set if not provide, use the API default values.  Otherwise things like the forums which aren't thinking about it
		//get set incorrectly.
		if (isset($_POST['allow_post']))
		{
			$input['allow_post'] = (bool)$_POST['allow_post'];
		}

		if (!empty($_POST['save_draft']))
		{
			$input['publish_now'] = false;
			$input['publishdate'] = 0;
		}
		else if (!empty($_POST['publish_now']))
		{
			$input['publish_now'] = (int)$_POST['publish_now'];
		}
		else
		{
			$input['publishdate'] = $this->getPublishDate();
		}

		//enable/disable blog comments. For blogs, this uses a checkbox which isn't sent when it's unchecked
		//so we use a hidden input flag to tell us to look for it.
		if (!empty($_POST['allow_post_checkbox']))
		{
			$input['allow_post'] = (bool) (isset($_POST['allow_post']) ? $_POST['allow_post'] : 0);
		}

		if (isset($_POST['displayorder']))
		{
			$input['displayorder'] = (int)$_POST['displayorder'];
		}


		return $input;
	}

	//Get the common fields for all types.  Need to sort out how to chunk different fields expected from
	//different places, particularly the CMS which has a ton of extra stuff.
	private function getCommonFields()
	{
		$input = [
			'title'        => trim(strval($_POST['title'] ?? '')),
			'text'         => trim(strval($_POST['text']) ?? ''),
			'nodeid'       => intval($_POST['nodeid'] ?? 0),
			'parentid'     => intval($_POST['parentid'] ?? 0),
			'channelid'    => intval($_POST['channelid'] ?? 0),
			'ret'          => trim(strval($_POST['ret'] ?? '')),
			'tags'         => $_POST['tags'] ?? '',
			//used in editing a post
			'reason'       => trim(strval($_POST['reason'] ?? '')),
			'iconid'       => intval($_POST['iconid'] ?? 0),
			'prefixid'     => trim(strval($_POST['prefixid'] ?? '')),
			'hvinput'      => $_POST['humanverify'] ?? '',
			//I'm not sure if this is ever actually set from anywhere.  The only reference to
			//nl2br in the templates is for the htmlstate field below.
			'nl2br'        => boolval($_POST['nl2br'] ?? false),
			'customfields' => $_POST['customfields'] ?? [],
		];


		//not sure why we don't default this like we do the other fields.
		if (isset($_POST['htmlstate']))
		{
			$input['htmlstate'] = trim(strval($_POST['htmlstate']));
		}

		return $input;
	}

	//we should be pulling this directly from the $_POST array instead of copying it
	//through the input (and limited input to just the fileds that aren't part of the
	//text) but that's another layer to the onion.
	private function getCommonNodeData($input, $user)
	{
		$nodeData = [
			'title'                  => $input['title'],
			'parentid'               => $input['parentid'],
			'prefixid'               => $input['prefixid'],
			'iconid'                 => $input['iconid'],
			'customfields'           => $input['customfields'],
			'rawtext'                => $input['text'],
		];

		//we have different fields for edit vs add
		if ($input['nodeid'])
		{
			$nodeData['reason'] = $input['reason'];

			//I'm not sure where this gets used.  For some reason we need this in the text
			//data for but it's an "option" for create.  We'll need to sort out how best
			//to handle that
			if ($input['nl2br'])
			{
				// not using ckeditor (on edit, 'nl2br' goes in the data array)
				$nodeData['nl2br'] = true;
			}
		}
		else
		{
			$nodeData['userid'] = $user['userid'];
			$nodeData['authorname'] = $user['username'];
			$nodeData['created'] = vB5_Request::get('timeNow');
			$nodeData['hvinput'] = $input['hvinput'];

			//we used to set the input and then not use it in the various type actions
			//so just read this from the post.  Not sure if there is a reason we can't just
			//default it to something instead of only setting it if it exists.
			if (!empty($_POST['setfor']))
			{
				$nodeData['setfor'] = intval($_POST['setfor']);
			}
		}

		// This is to carry the old getArticleInput() default behavior over
		// through the new getCommonInputs() method that partially replaces
		// the old usage.
		// Unknown atm why we don't default this like the other vars but
		// not wanting to change behaviors right now.
		if (isset($input['htmlstate']))
		{
			$nodeData['htmlstate'] = $input['htmlstate'];
		}

		return $nodeData;
	}

	/**
	 * Returns the correct publish date for this item, taking into account the
	 * Future publish and draft options. Returns boolean false when the publish
	 * date should not be set.
	 *
	 * @return	mixed	Publish date (which can be empty to save as draft) or false to not set publish date.
	 */
	protected function getPublishDate()
	{
		// for save draft and specify publish date, we always want to
		// set the publishdate, when updating and when creating new
		if (isset($_POST['save_draft']) AND $_POST['save_draft'] == 1)
		{
			// no publish date == draft (currently used for articles)
			return '';
		}
		else if (!empty($_POST['publish_now']))
		{
			return false;
		}
		else
		{
			// specify publish date (currently used for articles)
			if (
				!empty($_POST['publish_hour']) AND
				isset($_POST['publish_minute']) AND
				!empty($_POST['publish_month']) AND
				!empty($_POST['publish_day']) AND
				!empty($_POST['publish_year']) AND
				!empty($_POST['publish_ampm'])
			)
			{
				if ($_POST['publish_ampm'] == 'pm')
				{
					$_POST['publish_hour'] = $_POST['publish_hour'] + 12;
				}
				$dateInfo = [
					'hour' => $_POST['publish_hour'],
					'minute' => $_POST['publish_minute'],
					'month' =>  $_POST['publish_month'],
					'day' => $_POST['publish_day'],
					'year' => $_POST['publish_year']
				];
				$api = Api_InterfaceAbstract::instance();
				return  $api->callApi('user', 'vBMktime', [$dateInfo]);
			}
			else
			{
				// we don't have the correct fields to generate the publish date
				// save as draft
				return '';
			}
		}
	}

	public function actionEvent()
	{
		return $this->actionTextNodeInternal('content_event');
	}

	/**
	 * Returns event-specific data
	 *
	 * @return	array	Array of input items
	 */
	protected function getEventInput()
	{
		$ignoredst = boolval($_POST['ignoredst'] ?? true);

		$input = [
			'eventstartdate'   => (!empty($_POST['eventstartdate']) ? $this->userTimeStrToUnixtimestamp($_POST['eventstartdate'], $ignoredst) : 0),
			'eventenddate'     => (!empty($_POST['eventenddate']) ? $this->userTimeStrToUnixtimestamp($_POST['eventenddate'], $ignoredst) : 0),
			// if 'allday' is true, the library cancels 'eventenddate' above
			'allday'           => boolval($_POST['is_all_day'] ?? false),
			'location'         => trim(strval($_POST['location'] ?? '')),
			'maplocation'      => '',
			'ignoredst'        => $ignoredst,
			'eventhighlightid' => intval($_POST['eventhighlightid'] ?? 0),
		];

		if (!empty($_POST['showmap']))
		{
			$input['maplocation'] = $input['location'];
		}

		return $input;
	}

	public function actionHashtagAutocomplete()
	{
		//the real autoComplete functions have an offset param. However it's not used as far as I can tell
		//and I'm not sure how to implement it here without way more effort than its worth for something
		//we're not going to use.
		$params = [
			'searchStr' => $_POST['searchStr'] ?? '',
			'limitnumber' => $_POST['limitnumber'] ?? 15,
		];

		$api = Api_InterfaceAbstract::instance();
		$tags = $api->callApi('tags', 'getAutocomplete', $params, true);
		if (!empty($tags['errors']))
		{
			return $this->sendAsJson($tags);
		}

		$channels = $api->callApi('content_channel', 'getAutocomplete', $params, true);
		if (!empty($channels['errors']))
		{
			return $this->sendAsJson($channels);
		}

		$tags = $tags['suggestions'];
		//mark the tag vs channel ids
		foreach ($tags AS $index => $row)
		{
			$tags[$index]['id'] = 't' . $tags[$index]['id'];
			// We need to HTML escape on the serverside, because we don't want to escape the channel titles
			// which allow HTML.
			// It seems like tags are stored escaped, but also rendered escaped. It looks messy, but
			// trying to stay consistent with the current frontend.
			// We need both the title & htmllabel escaped. htmllabel will be used for the suggestion list,
			// while the title will be used for the inserted inline item.
			// If we switch tags to be displayed raw, we should remove the escaping here.
			$tags[$index]['title'] = vB5_String::htmlSpecialCharsUni($tags[$index]['title']);
			$tags[$index]['htmllabel'] = $tags[$index]['title'];
		}

		$channels = $channels['suggestions'];
		foreach ($channels AS $index => $row)
		{
			$channels[$index]['id'] = 'c' . $channels[$index]['id'];
			// We also need to specify htmllabel, so that the suggestionlist entry is unescaped (i.e. goes through
			// .html() instead of .text() in autocomplete('instance')._renderItem function)
			$channels[$index]['htmllabel'] = $channels[$index]['title'];

		}

		//combine the arrays and limit to the overall limit
		$suggestions = array_merge($channels, $tags);
		uasort($suggestions, function($a, $b) {return strcasecmp($a['title'], $b['title']);});
		return $this->sendAsJson(['suggestions' => array_slice($suggestions, 0, $params['limitnumber'])]);
	}

	//this handles a simple text node. It really should be actionText for clarity.
	public function index()
	{
		return $this->actionTextNodeInternal('content_text');
	}


	protected function actionTextNodeInternal($apiClass)
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		$apiClass = strtolower($apiClass);
		$api = Api_InterfaceAbstract::instance();

		$input = $this->getCommonFields();
		$input += [
			'subtype' => trim(strval($_POST['subtype'] ?? '')),
		];

		// get user info for the currently logged in user
		$user  = $api->callApi('user', 'fetchUserinfo', []);

		$textData = $this->getCommonNodeData($input, $user);

		if ($apiClass == 'content_event')
		{
			$textData += $this->getEventInput();
		}

		if ($input['nodeid'])
		{
			$this->validateEditUser($user);

			// when *editing* comments, it uses create-content/text (this function)
			// when *creating* comments, it uses ajax/post-comment (actionPostComment)
			if ($input['subtype'] == 'comment')
			{
				// NOTE: Keep this in sync with
				//       vB5_Frontend_Controller_Ajax:: actionPostComment
				//
				// htmlspecialchars and nl2br puts the text into the same state
				// it is when the text api receives input from ckeditor
				// specifically, newlines are passed as <br /> and any HTML tags
				// that are typed literally into the editor are passed as HTML-escaped
				// because non-escaped HTML that is sent is assumed to be formatting
				// generated by ckeditor and will be parsed & converted to bbcode.
				$textData['rawtext'] = nl2br($api->stringInstance()->htmlspecialchars($textData['rawtext'], ENT_NOQUOTES));
			}

			$result = $this->updateNode($apiClass, $input['nodeid'], $textData, $input['tags']);
			$this->sendAsJson($result);
		}
		else
		{
			$result = $this->createNewNode($apiClass, $textData, $input);
			$this->sendAsJson($result);
		}
	}

	private function validateEditUser($user)
	{
		if ($user['userid'] < 1)
		{
			$result = ['error' => 'logged_out_while_editing_post'];
			$this->sendAsJson($result);
			exit;
		}
	}

	private function updateNode($apilib, $nodeid, $data, $tags)
	{
		$api = Api_InterfaceAbstract::instance();

		$data += $this->getArticleInput();

		// We need to convert WYSIWYG html here and run the img check
		$options = [];
		if (isset($data['rawtext']))
		{
			$tmpText = $api->callApi('bbcode', 'convertWysiwygTextToBbcode', [$data['rawtext'], $options]);
			if (!empty($tmpText['errors']))
			{
				return $tmpText;
			}

			// Check Images
			if (($phrase = vB5_Frontend_Controller_Bbcode::verifyImgCheck($tmpText)) !== true)
			{
				return ['errors' => [$phrase]];
			}
		}

		// add attachment info so update() can do permission checking & add/remove attachments to this node.
		$this->addAttachments($data);

		//allow hooking into content updates.
		$altreturn = '';
		$api->invokeHook('hookFrontendContentBeforeUpdate', [
			'apilib' => $apilib,
			'nodeid' => $nodeid,
			'data' => &$data,
			'altreturn' => &$altreturn,
		]);

		if ($altreturn !== '')
		{
			return $altreturn;
		}

		//if we can't even load the load, we're not going to be able to edit it
		$node = $api->callApi($apilib, 'getFullContent', [$nodeid]);
		if (!empty($node['errors']))
		{
			return $node;
		}

		$node = current($node);
		$updateResult = $api->callApi($apilib, 'update', [$nodeid, $data]);

		// If the update failed, just return and don't edit tags, attachments etc.
		if (!empty($updateResult['errors']))
		{
			return $updateResult;
		}

		if (!empty($updateResult['warnings']))
		{
			$updateResult['warningReturnUrl'] = $this->getReturnUrl($node['channelid'], $node['parentid'], $node['nodeid']);
		}

		//update tags
		$tags = !empty($tags) ? explode(',', $tags) : [];
		$tagRet = $api->callApi('tags', 'updateUserTags', [$nodeid, $tags]);
		if (!empty($tagRet['errors']))
		{
			return $tagRet;
		}

		$api->invokeHook('hookFrontendContentAfterUpdate', [
			'apilib' => $apilib,
			'nodeid' => $nodeid,
			'updateResult' => $updateResult,
		]);

		return $updateResult;
	}

	/**
	 *	creates a new node based on the type
	 *
	 *	This handle the JSON output for both errors and success.
	 *
	 *	@param string $apilib -- the library to use to create the node
	 *	@param array $data -- the data needed by the api function for a particular type.  See the calling functions for
	 *		details.
	 *	@param array $input -- the input variables
	 *
	 *	@return boolean -- false means an error happened and the calling action should return immediately.  true means
	 *		success and the caller should continue.
	 */
	private function createNewNode($apilib, $data, $input, $alert = null)
	{
		//the input parameter could be better handled.  It's used this way because that's how it exisited in the
		//code before it was a parameter.

		$api = Api_InterfaceAbstract::instance();

		// sets publishdate
		$data += $this->getArticleInput();

		$options = [];
		$result = [];

		// We need to convert WYSIWYG html here and run the img check
		if (isset($data['rawtext']))
		{
			$tmpText = $api->callApi('bbcode', 'convertWysiwygTextToBbcode', [$data['rawtext'], $options]);
			if (!empty($tmpText['errors']))
			{
				return $tmpText;
			}

			if (($phrase = vB5_Frontend_Controller_Bbcode::verifyImgCheck($tmpText)) !== true)
			{
				return ['errors' => [$phrase]];
			}
		}

		if ($input['nl2br'])
		{
			// not using ckeditor (on add, 'nl2br' goes in the options array)
			$options['nl2br'] = true;
		}

		// add attachments
		$this->addAttachments($data);

		$altreturn = '';
		$api->invokeHook('hookFrontendContentBeforeAdd', [
			'iscomment' => false,
			'altreturn' => &$altreturn,
			'apilib' => $apilib,
			'data' => &$data,
			'options' => &$options,
		]);

		if ($altreturn !== '')
		{
			return $altreturn;
		}

		$nodeId = $api->callApi($apilib, 'add', [$data, $options]);

		$success = true;
		$output = [];

		if (!is_int($nodeId) OR $nodeId < 1)
		{
			$output = $nodeId;
			$success =  false;
		}

		$queryparams = [];
		if ($success)
		{
			if (!empty($input['tags']))
			{
				$tagRet = $api->callApi('tags', 'addTags', [$nodeId, $input['tags']]);
				/*
				Any tag errors should not interrupt the redirect-to-post logic.
				Convert to notices that will behandled via flashMessage.

				It seems like the tagAPI mostly returned rendered phrases in
				the errors[0] array rather than a phrase key or phrase array.

				encodeFlashMessage() should now be able to handle any of
				phrase key, phrase array, or rendered.
				 */
				if (!empty($tagRet['errors'][0]))
				{
					$flashMessage = $this->encodeFlashMessage($tagRet['errors'][0]);
					$queryparams = ['flashmsg' => $flashMessage];
				}
			}

			$node = $api->callApi('node', 'getNode', [$nodeId]);

			//we really don't expect to get errors loading the
			//node we just created, but if we do...
			if (!empty($node['errors']))
			{
				$output = $node;
				$success =  false;
			}

			if ($success)
			{
				if ($node)
				{
					if (empty($node['approved']))
					{
						$result['moderateNode'] = true;
					}
				}

				$url = $this->getReturnUrl(
					$input['channelid'],
					$input['parentid'],
					$nodeId,
					$queryparams
				);
				if ($url)
				{
					$result['retUrl'] = $url;
				}

				$result['nodeId'] = $nodeId;

				//hack for the gallery code, need to sort of the JS end so that we can
				//make all of the types act the same way.
				if ($alert)
				{
					$result['alert'] = $alert;
				}

				$output = $result;
			}
		}

		$api->invokeHook('hookFrontendContentAfterAdd', [
			'iscomment' => false,
			'success' => $success,
			'output' => $output,
			'nodeid' => $nodeId,
		]);

		return $output;
	}

	private function userTimeStrToUnixtimestamp($strTime, $ignoreDST)
	{
		/*
			Wrapper for user API function that takes a readable time string, adjusts it for user's TZ offset, & converts it to UTC timestamp
		 */
		$strTime = trim(strval($strTime));
		$api = Api_InterfaceAbstract::instance();
		$apiResult = $api->callApi('user', 'userTimeStrToUnixtimestamp', [$strTime, false, $ignoreDST]);

		return $apiResult['unixtimestamp'];
	}

	public function actionPoll()
	{
		// require a POST request for this action
		$this->verifyPostRequest();
		// We need this input for userTimeStrToUnixtimestamp() check.

		$api = Api_InterfaceAbstract::instance();

		$ignoreDst = boolval($_POST['ignoredst'] ?? true);
		$input = $this->getCommonFields();
		//for polls the parent is always the channel and not passed seperately.  Will need to
		//fix if we allow polls to be replies.
		$input['channelid'] = $input['parentid'];
		$input += [
			'polloptions'     => (array) $_POST['polloptions'],
			'timeout'         => (!empty($_POST['timeout']) ?	$this->userTimeStrToUnixtimestamp($_POST['timeout'], $ignoreDst) : 0),
			'multiple'        => boolval($_POST['multiple'] ?? false),
			'public'          => boolval($_POST['public'] ?? false),
		];

		// Poll Options
		$polloptions = [];
		foreach ($input['polloptions'] AS $k => $v)
		{
			if ($v)
			{
				if ($k == 'new')
				{
					foreach ($v AS $v2)
					{
						$v2 = trim(strval($v2));
						if ($v2 !== '')
						{
							$polloptions[]['title'] = $v2;
						}
					}
				}
				else
				{
					$polloptions[] = [
						'polloptionid' => intval($k),
						'title' => trim($v),
					];
				}
			}
		}

		// get user info for the currently logged in user
		$user = $api->callApi('user', 'fetchUserinfo', []);

		$pollData = $this->getCommonNodeData($input, $user);
		$pollData += [
			'options'         => $polloptions,
			'multiple'        => $input['multiple'],
			'public'          => $input['public'],
			'timeout'         => $input['timeout'],
		];

		if ($input['nodeid'])
		{
			$result = $this->updateNode('content_poll', $input['nodeid'], $pollData, $input['tags']);
			$this->sendAsJson($result);
		}
		else
		{
			$result = $this->createNewNode('content_poll', $pollData, $input);
			$this->sendAsJson($result);
		}
	}

	/**
	 * Creates a gallery
	 * This is called when creating a thread or reply using the "Photos" tab
	 * And when uploading photos at Profile => Media => Share Photos
	 */
	public function actionGallery()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		$apiClass = 'content_gallery';
		$api = Api_InterfaceAbstract::instance();

		$input = $this->getCommonFields();
		$input += [
			'viewperms' => (isset($_POST['viewperms']) ? intval($_POST['viewperms']) : null),
		];

		// get user info for the currently logged in user
		$user = $api->callApi('user', 'fetchUserinfo', []);
		$galleryData = $this->getCommonNodeData($input, $user);
		$galleryData += [
			// Currently used only for albums
			'viewperms'       			 => $input['viewperms'],
		];

		if ($input['nodeid'])
		{
			$this->validateEditUser($user);

				//needed for updateFromWeb, passed seperately for the proper update function.
			$galleryData['nodeid'] = $input['nodeid'];

			//should be handled by the Article fields function but leaving for now.
			//enable/disable article comments -- this is now used generally
			//do not set if not provide, use the API default values.  Otherwise things like the forums which aren't thinking about it
			//get set incorrectly.
			if (isset($_POST['allow_post']))
			{
				$input['allow_post'] = (bool)$_POST['allow_post'];
			}

			if (empty($_POST['filedataid']))
			{
				$_POST['filedataid'] = [];
			}

			// prepare filedataids array for updateFromWeb
			$filedataids = [];
			foreach ($_POST['filedataid'] AS $filedataid)
			{
				$filedataids[$filedataid] = [
					'title' => $_POST["title_$filedataid"] ?? '',
					'caption' => $_POST["caption_$filedataid"] ?? '',
					//not clear on why this default is 1 here and '' for add
					'displayorder' => $_POST["displayorder_$filedataid"] ?? 1,
				];
			}

			// add attachment information before saving.
			$this->addAttachments($galleryData);

			//if we can't even load the load, we're not going to be able to edit it
			$node = $api->callApi('content_gallery', 'getFullContent', [$galleryData['nodeid']]);
			if (!empty($node['errors']))
			{
				return $this->sendAsJson($node);
			}

			$node = current($node);

			//updateFromWeb doesn't use prefixid or iconid and probably should.  Really we need to kill it with fire and use
			//update like we do for every other content type.
			$updateResult = $api->callApi('content_gallery', 'updateFromWeb', [$galleryData['nodeid'], $galleryData, $filedataids]);

			// If the update failed, just return and don't edit tags, attachments etc.
			if (!empty($updateResult['errors']))
			{
				return $this->sendAsJson($updateResult);
			}

			if (!empty($updateResult['warnings']))
			{
				$updateResult['warningReturnUrl'] = $this->getReturnUrl($node['channelid'], $node['parentid'], $node['nodeid']);
			}

			//update tags
			$tags = !empty($input['tags']) ? explode(',', $input['tags']) : [];
			$tagRet = $api->callApi('tags', 'updateUserTags', [$input['nodeid'], $tags]);

			if (!empty($tagRet['errors']))
			{
				return $this->sendAsJson($tagRet);
			}

			return $this->sendAsJson($updateResult);
		}
		else
		{
			if (!empty($_POST['filedataid']))
			{
				// by circumstance, photos added to an album seem to use input name="filedataid"
				// while other attachments use input name="filedataids". So thankfully we can
				// distinguish between gallery photos & extraneous attachments. Whew.
				$galleryData['photos'] = [];
				foreach ($_POST['filedataid'] AS $filedataid)
				{
					$caption = $_POST["caption_$filedataid"] ?? '';
					$title = $_POST["title_$filedataid"] ?? '';
					//not clear on why this default is '' here and 1 for edit
					$displayorder = $_POST["displayorder_$filedataid"] ?? '';

					$galleryData['photos'][] = [
						'caption' => $caption,
						'title' => $title,
						'displayorder' => $displayorder,
						'filedataid' => $filedataid,
						'options' => [
							'isnewgallery' => true,
							'skipNotification' => true,
						],
					];
				}
			}

			$alert = null;
			if (!$api->callApi('user', 'hasPermissions', ['albumpermissions', 'picturefollowforummoderation']))
			{
				$alert = 'post_awaiting_moderation';
			}

			$result = $this->createNewNode($apiClass, $galleryData, $input, $alert);
			$this->sendAsJson($result);
		}
	}

	public function actionVideo()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		$api = Api_InterfaceAbstract::instance();

		$input = $this->getCommonFields();
		$input += [
			'url_title'       => trim(strval($_POST['url_title'] ?? '')),
			'url'             => trim(strval($_POST['url'] ?? '')),
			'url_meta'        => trim(strval($_POST['url_meta'] ?? '')),
			'videoitems'      => $_POST['videoitems'] ?? [],
		];

		$videoitems = [];
		foreach ($input['videoitems'] AS $k => $v)
		{
			if ($k == 'new')
			{
				foreach ($v AS $v2)
				{
					if ($v2)
					{
						$videoitems[]['url'] = $v2['url'];
					}
				}
			}
			else
			{
				$videoitems[] = [
					'videoitemid' => intval($k),
					'url' => $v['url'],
				];
			}
		}

		// get user info for the currently logged in user
		$user  = $api->callApi('user', 'fetchUserinfo', []);
		$videoData = $this->getCommonNodeData($input, $user);
		$videoData += [
			'url_title'       => $input['url_title'],
			'url'             => $input['url'],
			'meta'            => $input['url_meta'],
			'videoitems'      => $videoitems,
		];

		if ($input['nodeid'])
		{
			$result = $this->updateNode('content_video', $input['nodeid'], $videoData, $input['tags']);
			$this->sendAsJson($result);
		}
		else
		{
			$result = $this->createNewNode('content_video', $videoData, $input);
			$this->sendAsJson($result);
		}
	}

	public function actionLink()
	{
		if (isset($_POST['videoitems']))
		{
			return $this->actionVideo();
		}

		// require a POST request for this action
		$this->verifyPostRequest();

		$api = Api_InterfaceAbstract::instance();

		$input = $this->getCommonFields();
		$input += [
			'url_image'       => trim(strval($_POST['url_image'] ?? '')),
			'url_title'       => trim(strval($_POST['url_title'] ?? '')),
			'url'             => trim(strval($_POST['url'] ?? '')),
			'url_meta'        => trim(strval($_POST['url_meta'] ?? '')),
			'url_nopreview'   => intval($_POST['url_nopreview'] ?? 0),
		];

		// Upload images
		$filedataid = 0;
		if (!$input['url_nopreview'] AND $input['url_image'] AND $input['url_image'] !== 'current')
		{
			$ret = $api->callApi('content_attach', 'uploadUrl', [$input['url_image']]);

			if (empty($ret['errors']))
			{
				$filedataid = $ret['filedataid'];
			}
		}

		$user  = $api->callApi('user', 'fetchUserinfo', []);
		$linkData = $this->getCommonNodeData($input, $user);
		$linkData += [
			'url_title'       => $input['url_title'],
			'url'             => $input['url'],
			'meta'            => $input['url_meta'],
			'filedataid'      => $filedataid,
		];

		if (!$input['url_nopreview'] AND $input['url_image'] === 'current')
		{
			unset($linkData['filedataid']);
		}

		if ($input['nodeid'])
		{
			$result = $this->updateNode('content_link', $input['nodeid'], $linkData, $input['tags']);
			$this->sendAsJson($result);
		}
		else
		{
			$result = $this->createNewNode('content_link', $linkData, $input);
			$this->sendAsJson($result);
		}
	}

	/**
	 * Creates a private message.
	 */
	public function actionPrivateMessage()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		$api = Api_InterfaceAbstract::instance();

		if (!empty($_POST['autocompleteHelper']) AND empty($_POST['msgRecipients']))
		{
			$msgRecipients = $_POST['autocompleteHelper'];


			if (substr($msgRecipients, -1) == ';')
			{
				$msgRecipients = substr($msgRecipients, 0, -1);
			}
			$_POST['msgRecipients'] = $msgRecipients;
		}

		if (!empty($_POST['msgRecipients']) AND (substr($_POST['msgRecipients'], -3) == ' ; '))
		{
			$_POST['msgRecipients'] = substr($_POST['msgRecipients'], 0, -3);
		}

		$hvInput = isset($_POST['humanverify']) ? $_POST['humanverify'] : '';
		$_POST['hvinput'] =& $hvInput;

		$_POST['rawtext'] = $_POST['text'];
		unset($_POST['text']);

		$options = [];

		if (!empty($_POST['nl2br']))
		{
			// not using ckeditor (on add, 'nl2br' goes in the options array)
			$options['nl2br'] = true;
		}

		// add attachment info so update() can do permission checking & add/remove attachments to this node.
		$data = $_POST; // let's not try to edit magic globals directly.
		$this->addAttachments($data);

		$result = $api->callApi('content_privatemessage', 'add', [$data, $options]);

		$results = [];
		if (!empty($result['errors']))
		{
			$results = $result;
		}
		else
		{
			$results['nodeId'] = (int) $result;
		}

		return $this->sendAsJson($results);
	}

	/**
	 * Cleans and normalizes arbitrary pasted HTML to remove bits that vBulletin
	 * doesn't recognize or handle. Achieved by converting to BBCode, then converting
	 * back to WYSIWYG.
	 */
	public function actionCleanpastedhtml()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		$input = [
			'nodeid' => intval($_POST['nodeid'] ?? 0),
			'data' => strval($_POST['data'] ?? ''),
		];

		// first convert from WYSIWYG to BBcode
		$result = Api_InterfaceAbstract::instance()->callApi('editor', 'convertHtmlToBbcode', [$input['data']]);
		if (!empty($result['errors']))
		{
			return $this->sendAsJson($result);
		}

		$data = $result['data'];

		// now convert from BBcode to WYSIWYG
		$data = $this->convertBbcodeToWysiwyg($input['nodeid'], $data);

		return $this->sendAsJson(['data' => $data]);
	}

	/**
	 * Convert BBCode text to WYSIWYG text. Used when switching from source mode to
	 * wysiwyg mode.
	 */
	public function actionParseWysiwyg()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		$input = [
			'nodeid' => intval($_POST['nodeid'] ?? 0),
			'data' => strval($_POST['data'] ?? ''),
		];

		$data = $this->convertBbcodeToWysiwyg($input['nodeid'], $input['data']);

		return $this->sendAsJson(['data' => $data]);
	}

	/**
	 * Converts Bbcode text to Wysiwyg text.
	 *
	 * @param  int    Node ID (optional)
	 * @param  string Post text
	 * @return string Converted WYSIWYG post text
	 */
	private function convertBbcodeToWysiwyg($nodeid, $data)
	{
		$options = [];
		$attachments = [];

		// if this is an existing node, we need to fetch attachments so converting from source mode to
		// wysiwyg mode displays attachments. If attachments are not passed, they won't be set in the
		// vB5_Template_BbCode_Wysiwyg instance. See vB5_Template_BbCode's setAttachments() function.
		if ($nodeid)
		{
			$attachments = Api_InterfaceAbstract::instance()->callApi('node', 'getNodeAttachments', [$nodeid]);
		}

		// eventually goes through vB5_Template_BbCode_Wysiwyg's doParse()
		$data = vB5_Frontend_Controller_Bbcode::parseWysiwyg($data, $options, $attachments);

		/*
		 *	we might have some placeholders from bbcode parser. Replace them before we send it back.
		 *	I added this call because the parser was adding placeholders for the 'image_larger_version_x_y_z' phrase
		 *	in the image alt texts for images that didn't get titles set, and ckeditor was having a field day with the
		 *	placeholder, not to mention causing issues with wysiwyghtmlparser's parseUnmatchedTags() (the regex fails
		 *	to match image tags if any attribute before src has a > character).
		 *	While parseUnmatchedTags() will still have problems* if the alt text (or any attribute before src) contains
		 *	a >, getting rid of the placeholder at least prevents the problem from being caused by the parser itself.
		 *		* see VBV-12308
		 */
		$phraseCache = vB5_Template_Phrase::instance();
		$phraseCache->replacePlaceholders($data);

		return $data;
	}

	/**
	 * Creates the edit title form
	 *
	 * We load the form via AJAX to ensure that the title populated in the form is the current
	 * title, instead of pulling it from the DOM.
	 */
	public function actionLoadTitleEdit()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		$input = [
			'nodeid' => (isset($_POST['nodeid']) ? intval($_POST['nodeid']) : 0),
		];

		$results = [];

		if ($input['nodeid'] < 1)
		{
			$results['error'] = 'invalid_node';
			$this->sendAsJson($results);
			return;
		}

		$api = Api_InterfaceAbstract::instance();
		$node = $api->callApi('node', 'getNodeContent', [$input['nodeid'], false]);
		$node = $node[$input['nodeid']];

		if (!$node)
		{
			$results['error'] = 'invalid_node';
			$this->sendAsJson($results);
			return;
		}

		// render the template
		$results = vB5_Template::staticRenderAjax('contententry_titleedit', ['node' => $node]);

		$this->sendAsJson($results);
	}

	/**
	 * Saves the edited title
	 */
	public function actionSaveTitleEdit()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		$input = [
			'nodeid' => (isset($_POST['nodeid']) ? intval($_POST['nodeid']) : 0),
			'title'  => (isset($_POST['title']) ? strval($_POST['title']) : ''),
		];

		$api = Api_InterfaceAbstract::instance();

		$node = $api->callApi('node', 'getNodeContent', [$input['nodeid'], false]);
		$node = $node[$input['nodeid']];

		$apiName = 'Content_' . $node['contenttypeclass'];
		$updateResult = $api->callApi($apiName, 'update', [
			'nodeid' => $input['nodeid'],
			'data'   => [
				'title' => $input['title'],
				'parentid' => $node['parentid'],
			],
		]);

		$node = $api->callApi('node', 'getNodeContent', [$input['nodeid'], false]);
		$node = $node[$input['nodeid']];

		$results = [
			'title' => $node['title'],
		];

		if (!empty($updateResult['errors']))
		{
			$results['errors'] = $updateResult['errors'];
		}

		$this->sendAsJson($results);

	}

	public function actionLoadeditor()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		$input = [
			'nodeid' => intval($_POST['nodeid'] ?? 0),
			'type' => trim(strval($_POST['type'] ?? '')),
			'view' => trim($_POST['view'] ?? 'stream'),
		];

		$results = [];

		if (!$input['nodeid'])
		{
			$results['errors'] = ['error_loading_editor'];
			$this->sendAsJson($results);
			return;
		}

		$api = Api_InterfaceAbstract::instance();
		$user = $api->callApi('user', 'fetchUserinfo', []);
		$node = $api->callApi('node', 'getNodeContent', [$input['nodeid'], false]);

		if (isset($node['errors']))
		{
			$this->sendAsJson($node);
			return;
		}

		$node = $node[$input['nodeid']];

		if (!$node)
		{
			$results['errors'] = ['error_loading_editor'];
			$this->sendAsJson($results);
			return;
		}

		// the contententry template uses createpermissions, but content library's assembleContent seems to
		// set createpermissions for *REPLYING* to the node.
		// The createpermissions for editing should be handled differently, so let's re-assemble them
		// and set it for the node. (This assumes loadeditor is only called for editing)
		$createPermissions = $api->callApi('node', 'getCreatepermissionsForEdit', [$node]);
		$node['createpermissions'] = $createPermissions['createpermissions'];

		//See if we should show delete
		$node['canremove'] = 0;

		// if user can soft OR hard delete, we should show the delete button. The appropriate template
		// should handle *which* delete options to show.
		$canDelete = $api->callApi('node', 'getCanDeleteForEdit', [$node]);
		$node['canremove'] = $canDelete['candelete'];

		/* VM checks. I'm leaving these alone for now, but I'LL BE BACK
		 * We should update vB_Library_Content's getCanDelete() (which is downstream of node API's getCanDelete())
		 * to detect/handle the VM checks, and just remove the below code altogether.
		 */
		if (
			($node['starter'] > 0)
			AND
			($node['setfor'] > 0)
			AND
			(
				$api->callApi('user', 'hasPermissions', ['moderatorpermissions2', 'candeletevisitormessages'])
				OR
				$api->callApi('user', 'hasPermissions', ['moderatorpermissions2', 'canremovevisitormessages'])
			)
		)
		{
			// Make the editor show Delete button
			$node['canremove'] = 1;
		}
		else if (
			($node['starter'] > 0)
			AND
			($node['setfor'] > 0)
			AND
			($user['userid'] == $node['setfor'])
			AND
			$api->callApi('user', 'hasPermissions', ['visitormessagepermissions', 'can_delete_own_visitor_messages'])
		)
		{
			// Make the editor show Delete button
			$node['canremove'] = 1;
		}


		$types = ['Text', 'Gallery', 'Poll', 'Video', 'Link', 'Event'];
		if (in_array($node['contenttypeclass'], $types))
		{
			if ($input['type'] == 'comment' AND $node['contenttypeclass'] == 'Text')
			{
				$results = vB5_Template::staticRenderAjax('editor_contenttype_Text_comment', [
					'conversation' => $node,
					'showDelete' => $node['canremove'],
				]);
			}
			else
			{
				$templateData = [
					'nodeid'               => $node['nodeid'],
					'conversation'         => $node,
					'parentid'             => $node['parentid'],
					'showCancel'           => 1,
					'showDelete'           => $node['canremove'],
					'showPreview'          => 1,
					'showToggleEditor'     => 1,
					'showSmiley'           => 1,
					'showAttachment'       => 1,
					'showTags'             => ($node['nodeid'] == $node['starter'] AND $node['channeltype'] != 'vm'),
					'showTitle'            => ($node['nodeid'] == $node['starter'] AND $node['channeltype'] != 'vm'),
					'editPost'             => 1,
					'conversationType'     => $input['type'],
					'compactButtonSpacing' => 1,
					'initOnPageLoad'       => 1,
					'focusOnPageLoad'      => 1,
					'noJavascriptInclude'  => 1,
				];

				//for blog posts and articles, we need the channel info to determine if we need to display the blog / article options panel
				$channelInfo = $api->callApi('content_channel', 'fetchChannelById', [$node['channelid']]);
				$templateData['channelInfo'] = $channelInfo;

				foreach ($types AS $type)
				{
					$templateFlag = (($type == 'Gallery') ? 'Photo' : $type);
					$templateFlagValue = ($node['contenttypeclass'] == $type ? 1 : 0);
					$templateData['allowType' . $templateFlag] =  $templateFlagValue;
					if ($templateFlagValue == 1)
					{
						$templateData['defaultContentType'] = $node['contenttypeclass'];
					}
				}

				if ($node['contenttypeclass'] == 'Gallery')
				{
					if (!empty($node['photo']))
					{
						$templateData['maxid'] = max(array_keys($node['photo']));
					}
					else
					{
						$templateData['maxid'] = 0;
					}
					//for albums we enable the viewperms edit.
					if ($node['channeltype'] == 'album')
					{
						$templateData['showViewPerms'] = 1;
					}

				}

				//content types that has no Tags. Types used should be the same used in $input['type']
				$noTagsContentTypes = ['media', 'visitorMessage']; //add more types as needed
				if ($node['nodeid'] == $node['starter'])
				{
					if (!in_array($input['type'], $noTagsContentTypes)) //get tags of the starter (exclude types that don't use tags)
					{
						$tagList = $api->callApi('tags', 'getNodeTags', [$input['nodeid']]);
						if (!empty($tagList) AND !empty($tagList['tags']))
						{
							$templateData['tagList'] = $tagList;
						}
					}
				}
				if (in_array($input['type'], $noTagsContentTypes) OR $node['nodeid'] != $node['starter'])
				{
					$templateData['showTags'] = 0;
				}

				$results = vB5_Template::staticRenderAjax('contententry', $templateData);
			}
		}
		else
		{
			$results['errors'] = ['error_loading_editor'];
		}

		$this->sendAsJson($results);
		return;
	}

	private function getAutoParseLinks($input)
	{
		$api = Api_InterfaceAbstract::instance();
		$contentTypeClass = 'content_' . $input['conversationtype'];
		// getAutoparseLinks() needs htmlstate, disable_bbcode and parentid
		$data = $api->callApi($contentTypeClass, 'getAutoparseLinks', [$input]);
		return $data['autoparselinks'] ?? false;
	}

	public function actionLoadPreview()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		$input = [
			'parentid'         => (isset($_POST['parentid'])         ? intval($_POST['parentid']) : 0),
			'channelid'        => (isset($_POST['channelid'])        ? intval($_POST['channelid']) : 0),
			'pagedata'         => (isset($_POST['pagedata'])         ? ((array)$_POST['pagedata']) : []),
			'conversationtype' => (isset($_POST['conversationtype']) ? trim(strval($_POST['conversationtype'])) : ''),
			'posttags'         => (isset($_POST['posttags'])         ? trim(strval($_POST['posttags'])) : ''),
			'rawtext'          => (isset($_POST['rawtext'])          ? trim(strval($_POST['rawtext'])) : ''),
			'filedataid'       => (isset($_POST['filedataid'])       ? ((array)$_POST['filedataid']) : []),
			'link'             => (isset($_POST['link'])             ? ((array)$_POST['link']) : []),
			'poll'             => (isset($_POST['poll'])             ? ((array)$_POST['poll']) : []),
			'video'            => (isset($_POST['video'])            ? ((array)$_POST['video']) : []),
			'htmlstate'        => (isset($_POST['htmlstate'])        ? trim(strval($_POST['htmlstate'])) : ''),
			'disable_bbcode'   => (isset($_POST['disable_bbcode'])   ? intval($_POST['disable_bbcode']) : 0),
		];

		$nodeid = $_POST['nodeid'] ?? 0;

		$results = [];

		if ($input['parentid'] < 1)
		{
			$results['error'] = 'invalid_parentid';
			$this->sendAsJson($results);
			return;
		}

		if (!in_array($input['htmlstate'], ['off', 'on_nl2br', 'on'], true))
		{
			$input['htmlstate'] = 'off';
		}

		// when creating a new content item, channelid == parentid
		$input['channelid'] = ($input['channelid'] == 0) ? $input['parentid'] : $input['channelid'];

		$templateName = 'display_contenttype_conversationreply_';
		$templateName .= ucfirst($input['conversationtype']);

		$api = Api_InterfaceAbstract::instance();
		$channelBbcodes = $api->callApi('content_channel', 'getBbcodeOptions', [$input['channelid']]);

		// The $node['starter'] and $node['nodeid'] values are just there to differentiate starters and replies
		$node = [
			'rawtext' => '',
			'userid' => vB5_User::get('userid'),
			'authorname' => vB5_User::get('username'),
			'tags' => $input['posttags'],
			'taglist' => $input['posttags'],
			'approved' => true,
			'created' => time(),
			'avatar' => $api->callApi('user', 'fetchAvatar', ['userid' => vB5_User::get('userid')]),
			'parentid' => $input['parentid'],
			'starter' => ($input['channelid'] == $input['parentid']) ? 0 : $input['parentid'],
			'nodeid' => ($input['channelid'] == $input['parentid']) ? 0 : 1,
		];

		if ($input['conversationtype'] == 'gallery')
		{
			$node['photopreview'] = [];
			foreach ($input['filedataid'] AS $filedataid)
			{
				/*
				Known Issue/todo:
				We load the images via filedataid (see photo_gallery_preview template)
				which can cause breakages during preview in the edge case of current user
				not having cangetimgattachment and one or more images were added to this
				gallery by another user (an admin).
				Currently switching these to nodeids for existing photos is not straight
				forward (see gallery handling of vBulletin.contentEntryBox.handleButtonPreview
				in content_entry_box.js).
				 */
				$node['photopreview'][] = [
					'nodeid' => $filedataid,
					'htmltitle' => isset($_POST['title_' . $filedataid]) ? vB_String::htmlSpecialCharsUni($_POST['title_' . $filedataid]) : '',
				];

				//photo preview is up to 3 photos only
				if (count($node['photopreview']) == 3)
				{
					break;
				}
			}
			$node['photocount'] = count($input['filedataid']);
		}

		if ($input['conversationtype'] == 'link')
		{
			$node['url_title'] = !empty($input['link']['title']) ? $input['link']['title'] : '';
			$node['url'] = !empty($input['link']['url']) ? $input['link']['url'] : '';
			$node['meta'] = !empty($input['link']['meta']) ? $input['link']['meta'] : '';
			$node['previewImage'] = !empty($input['link']['url_image']) ? $input['link']['url_image'] : '';
			// 'current' is a special value to indicate that we should not change the thumbnail when editing a link.
			// Note, content_entry_box.js handles the "no preview thumbnail" checkbox by setting url_image to 0.
			if ($input['link']['url_image'] === 'current')
			{
				// If this is not an edit, we have no node to pull the current thumbnail from.
				$node['previewImage'] = '';
				if (!empty($nodeid))
				{
					$linkNode = $api->callApi('node', 'getNodeFullContent', ['nodeid' => $nodeid]);
					$linkNode = $linkNode[$nodeid] ?? null;
					if (!empty($linkNode['filedataid']))
					{
						$node['previewImage'] = "filedata/fetch?linkid=$nodeid&type=thumb&cachebusterid={$node['filedataid']}";
					}
				}
			}
		}

		if ($input['conversationtype'] == 'poll')
		{
			$node['multiple'] = !empty($input['poll']['mutliple']);
			$node['options'] = [];
			if (!empty($input['poll']['options']) and is_array($input['poll']['options']))
			{
				$optionIndex = 1;
				foreach ($input['poll']['options'] AS $option)
				{
					$node['options'][] = [
						'polloptionid' => $optionIndex,
						'title' => $option,
					];
					$optionIndex++;
				}
			}
			$node['permissions']['canviewthreads'] = 1; //TODO: Fix this!!
		}

		if ($input['conversationtype'] == 'video')
		{
			$node['url_title'] = !empty($input['video']['title']) ? $input['video']['title'] : '';
			$node['url'] = !empty($input['video']['url']) ? $input['video']['url'] : '';
			$node['meta'] = !empty($input['video']['meta']) ? $input['video']['meta'] : '';
			$node['items'] = !empty($input['video']['items']) ? $input['video']['items'] : '';
		}

		if ($input['conversationtype'] == 'event')
		{
			$node += $this->getEventInput();

			if ($node['allday'])
			{
				$apiResult = $api->callApi('content_event', 'getEndOfDayUnixtime',
					[
						'timestamp' => $node['eventstartdate'],
						'userid' => false,
						'hmsString' => "12:00:00 AM",
						'ignoreDST' => $node['ignoredst'],
					]
				);
				$node['eventstartdate'] = $apiResult['unixtime'];

				$apiResult = $api->callApi('content_event', 'getEndOfDayUnixtime',
					[
						'timestamp' => $node['eventenddate'],
						'userid' => false,
						'hmsString' => "11:59:59 PM",
						'ignoreDST' => $node['ignoredst'],
					]
				);
				$node['eventenddate'] = $apiResult['unixtime'];
			}
		}

		try
		{
			$results = vB5_Template::staticRenderAjax(
				$templateName,
				[
					'nodeid' => $node['nodeid'],
					'conversation' => $node,
					'currentConversation' => $node,
					'bbcodeOptions' => $channelBbcodes,
					'pagingInfo' => [],
					'postIndex' => 0,
					'reportActivity' => false,
					'showChannelInfo' => false,
					'showInlineMod' => false,
					'commentsPerPage' => 1,
					'view' => 'stream',
					'previewMode' => true,
				]
			);
		}
		catch (Exception $e)
		{
			if (vB5_Config::instance()->debug)
			{
				$results['error'] = 'error_rendering_preview_template ' . (string) $e;
			}
			else
			{
				$results['error'] = 'error_rendering_preview_template';
			}
			$this->sendAsJson($results);
			return;
		}

		$bbcodeoptions = [
			'allowhtml' => in_array($input['htmlstate'], ['on', 'on_nl2br'], true),
			'allowbbcode' => !$input['disable_bbcode'],
			'htmlstate' => $input['htmlstate'],
			'autoparselinks' => $this->getAutoParseLinks($input),
		];

		// Preview is weird. Rather than the template parse above rendering the text via the template code:
		// {vb:action parsedText, bbcode, parseNodeText, {vb:var conversation.nodeid}, 0, {vb:var page.contentpagenum}}
		// we do a separate parse here, then have the javascript inject it into place. This might partly be due to
		// the regular render stack requiring nodes to exist to pull data from....
		$parsedText = $this->parseBbCodeForPreview(fetch_censored_text($input['rawtext']), $bbcodeoptions);

		$results = array_merge($results, $parsedText);

		$this->sendAsJson($results);
	}

	public function actionLoadnode()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		$input = [
			'nodeid' => intval($_REQUEST['nodeid'] ?? 0),
			'view'   => trim(strval($_REQUEST['view'] ?? 'stream')),
			'page'   => ($_REQUEST['page'] ?? []),
			'index'  => floatval($_REQUEST['index'] ?? 0),
			'type'   => trim(strval($_REQUEST['type'] ?? '')),
		];

		$results = [];
		$results['css_links'] = [];

		if (!$input['nodeid'])
		{
			$results['error'] = 'error_loading_post';
			$this->sendAsJson($results);
			return;
		}

		$api = Api_InterfaceAbstract::instance();

		$node = $api->callApi('node', 'getNodeFullContent', [
			'nodeid' => $input['nodeid'],
			'contenttypeid' => false,
			'options' => ['showVM' => 1, 'withParent' => 1]
		]);
		$node = ($node[$input['nodeid']] ?? null);

		if (!$node)
		{
			$results['error'] = 'error_loading_post';
			$this->sendAsJson($results);
			return;
		}

		$currentNodeIsBlog = $node['channeltype'] == 'blog';
		$currentNodeIsArticle = $node['channeltype'] == 'article';

		if (!in_array($input['view'], ['stream', 'thread', 'activity-stream', 'full-activity-stream']))
		{
			$input['view'] = 'stream';
		}

		// add article views
		if ($currentNodeIsArticle)
		{
			// mergeNodeviewsForTopics expects an array of search results
			$tempNodes = [
				$node['nodeid'] => [
					'content' => [],
				],
			];
			$tempNodes = $api->callApi('node', 'mergeNodeviewsForTopics', [$tempNodes]);
			if (isset($tempNodes[$node['nodeid']]['content']['views']))
			{
				$node['views'] = $tempNodes[$node['nodeid']]['content']['views'];
			}
			unset($tempNodes);
		}

		//comment in Thread view
		// TODO Should $node['contenttypeclass'] == 'Text' be here?
		if (($input['view'] == 'thread' OR $currentNodeIsBlog OR $currentNodeIsArticle) AND $input['type'] == 'comment' AND $node['contenttypeclass'] == 'Text')
		{
			$templater = new vB5_Template('conversation_comment_item');
			$templater->register('conversation', $node);
			$templater->register('conversationIndex', floor($input['index']));
			if ($currentNodeIsBlog OR $currentNodeIsArticle)
			{
				$templater->register('commentIndex', $input['index']);
				$templater->register('parentNodeIsBlog', (bool)$currentNodeIsBlog);
				$templater->register('parentNodeIsArticle', (bool)$currentNodeIsArticle);

				$enableInlineMod = (
					!empty($node['moderatorperms']['canmoderateposts']) OR
					!empty($node['moderatorperms']['candeleteposts']) OR
					!empty($node['moderatorperms']['caneditposts']) OR
					!empty($node['moderatorperms']['canremoveposts'])
				);
				$templater->register('enableInlineMod', $enableInlineMod);
			}
			else if ($input['index'] - floor($input['index']) > 0)
			{
				$commentIndex = explode('.', strval($input['index']));
				$templater->register('commentIndex', $commentIndex[1]);
			}
			else
			{
				$templater->register('commentIndex', 1);
			}
		}
		else //reply or starter node or comment in Stream view
		{
			//Media tab Video Album
			if ($input['type'] == 'media' AND $node['contenttypeclass'] == 'Video')
			{
				$templater = new vB5_Template('profile_media_videoitem');
				$templater->register('conversation', $node);
				$templater->register('reportActivity', true);
				$results['template'] = $templater->render(true, true);
				$results['css_links'] = vB5_Template_Stylesheet::instance()->getAjaxCssLinks();

				$this->sendAsJson($results);
				return;
			}
			else
			{
				//designed to duplicate some logic in the widget_conversationdisplay template that updates a flag on the nodes used
				//by the conversation_footer template.  This really needs to be pushed back on the node API, but that's a riskier fix
				$starter = $api->callApi('node', 'getNodeFullContent', [$node['starter']]);
				if (!isset($starter['error']))
				{
					$node['can_use_multiquote'] = ($starter[$node['starter']]['canreply'] AND
						($starter[$node['starter']]['channeltype'] != 'blog'));
				}
				else
				{
					//explicitly handle the error case.  This is unlikely and throwing an error here would be bad.
					//so we'll ignore it and just return false as the safest behavior.
					$node['can_use_multiquote'] = false;
				}


				$template = 'display_contenttype_';
				if ($node['nodeid'] == $node['starter'])
				{
					$template .= ($input['view'] == 'thread') ? 'conversationstarter_threadview_' : 'conversationreply_';
				}
				else
				{
					$template .= ($input['view'] == 'thread') ? 'conversationreply_threadview_' : 'conversationreply_';
				}
			}

			$conversationRoute = $api->callApi('route', 'getChannelConversationRoute', [$node['channelid']]);
			$channelBbcodes = $api->callApi('content_channel', 'getBbcodeOptions', [$node['channelid']]);

			if (strpos($input['view'], 'stream') !== false)
			{
				$totalCount = $node['totalcount'];
			}
			else
			{
				$totalCount = $node['textcount'];
			}

			$arguments = [
				'nodeid'    => $node['nodeid'],
				'pagenum'   => $input['page']['pagenum'],
				'channelid' => $input['page']['channelid'],
				'pageid'    => $input['page']['pageid'],
			];

			$routeInfo = [
				'routeId'   => $conversationRoute,
				'arguments' => $arguments,
			];

			$pagingInfo = $api->callApi('page', 'getPagingInfo', [
				$input['page']['pagenum'],
				$totalCount,
				($input['page']['posts-perpage'] ?? null),
				$routeInfo,
				vB5_Template_Options::instance()->get('options.frontendurl')
			]);

			if (!isset($node['parsedSignature']))
			{
				if (isset($node['signature']))
				{
					$signatures = [$node['userid'] => $node['signature']];
					$parsed_signatures = Api_InterfaceAbstract::instance()->callApi('bbcode', 'parseSignatures', [
						array_keys($signatures),
						$signatures
					]);
					$node['parsedSignature'] = $parsed_signatures[$node['userid']];
				}
				else
				{
					$node['parsedSignature'] ='';
				}
			}

			// check if user can comment on this blog / article
			// same check as can be found in widget_conversationdisplay
			$userCanCommentOnThisBlog = false;
			$userCanCommentOnThisArticle = false;
			if ($currentNodeIsBlog)
			{
				$temp = $api->callApi('blog', 'userCanComment', [$node]);
				if ($temp AND empty($temp['errors']))
				{
					$userCanCommentOnThisBlog = array_shift($temp);
				}
				unset($temp);
			}
			else if ($currentNodeIsArticle)
			{
				$userCanCommentOnThisArticle = $node['canreply'];
			}

			$template .= $node['contenttypeclass'];

			$templater = new vB5_Template($template);
			$templater->register('nodeid', $node['nodeid']);
			$templater->register('currentNodeIsBlog', $currentNodeIsBlog);
			$templater->register('currentNodeIsArticle', $currentNodeIsArticle);
			$templater->register('userCanCommentOnThisBlog', $userCanCommentOnThisBlog);
			$templater->register('userCanCommentOnThisArticle', $userCanCommentOnThisArticle);
			$templater->register('conversation', $node);
			$templater->register('currentConversation', $node);
			$templater->register('bbcodeOptions', $channelBbcodes);
			$templater->register('pagingInfo', $pagingInfo);
			$templater->register('postIndex', $input['index']);
			$templater->register('reportActivity', strpos($input['view'], 'activity-stream') !== false);
			$templater->register('showChannelInfo', $input['view'] == 'full-activity-stream');
			if ($input['view'] == 'thread')
			{
				$templater->register('showInlineMod', true);
				$templater->register('commentsPerPage', $input['page']['comments-perpage']);
			}
			else if ($input['view'] == 'stream' AND !$node['isVisitorMessage']) // Visitor Message doesn't allow to be quoted. See VBV-5583.
			{
				$templater->register('view', 'conversation_detail');
			}
		}

		// send subscribed info for updating the UI
		if (!empty($node['starter']))
		{
			$topicSubscribed = $api->callApi('follow', 'isFollowingContent', ['contentId' => $node['starter']]);
		}
		else
		{
			$topicSubscribed = 0;
		}

		$results['template'] = $templater->render(true, true);
		$results['css_links'] = vB5_Template_Stylesheet::instance()->getAjaxCssLinks();
		$results['topic_subscribed'] = $topicSubscribed;

		if ($node['nodeid'] == $node['starter'])
		{
			$results['topic_url'] = $api->callApi('route', 'getUrl', [$node['routeid'], $node]);
		}

		// Add article meta. E.g. if an article is unpublished, or publishdate changes, we need to update that.
		if ($currentNodeIsArticle AND $node['nodeid'] == $node['starter'])
		{
			// Based on the template call in widget_pagetitle template
			$infoTemplateR = new vB5_Template('article_title_info');
			$infoTemplateR->register('conversation', $node);
			$infoTemplateR->register('reportActivity', 0);
			$infoTemplateR->register('displayCompact', 1);
			$infoTemplateR->register('hideTitle', 1);
			$infoTemplateR->register('isPageTitle', 1);
			$results['template_article_title_info'] = $infoTemplateR->render(true, true);
		}


		$this->sendAsJson($results);
		return;
	}


	public function actionLoadNewPosts()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		/*
			BEGIN >>> Clean Input <<<
		 */
		$input = [
			'parentid'			=> (isset($_POST['parentid'])		? intval($_POST['parentid']) : 0),	// form's parentid input. The topic starter.
			'channelid'			=> (isset($_POST['channelid'])		? intval($_POST['channelid']) : 0),
			'newreplyid'		=> (isset($_POST['newreplyid'])		? intval($_POST['newreplyid']) : 0),
			'lastloadtime'		=> (isset($_POST['lastloadtime'])		? intval($_POST['lastloadtime']) : 0),
			'lastpublishdate'	=> (isset($_POST['lastpublishdate'])		? intval($_POST['lastpublishdate']) : 0),
			'pageload_servertime'	=> (isset($_POST['pageload_servertime'])		? intval($_POST['pageload_servertime']) : 0),
			'view'				=> (isset($_POST['view'])		? trim($_POST['view']) : 'stream'),
			'currentpage'		=> (isset($_POST['currentpage'])		? intval($_POST['currentpage']) : 1),
			'pagetotal'			=> (isset($_POST['pagetotal'])		? intval($_POST['pagetotal']) : 0),
			'postcount'			=> (isset($_POST['postcount'])		? intval($_POST['postcount']) : 0),
			'postsperpage'		=> (isset($_POST['postsperpage'])		? intval($_POST['postsperpage']) : 0),
			'commentsperpage'	=> (isset($_POST['commentsperpage'])		? intval($_POST['commentsperpage']) : 0),
			'past_page_limit_aware' => (isset($_POST['past_page_limit_aware'])	? filter_var($_POST['past_page_limit_aware'], FILTER_VALIDATE_BOOLEAN) : false),
			'loadednodes'		=> [], // Individually cleaned below
		];
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
			}
			unset($unclean);
		}
		// END >>> Clean Input <<<



		$api = Api_InterfaceAbstract::instance();
		if (!empty($input['newreplyid']))
		{
			$usersNewReply = $api->callApi('node', 'getFullContentforNodes', [$input['newreplyid']]);
			$usersNewReply = (empty($usersNewReply) ? null : reset($usersNewReply));
		}
		else
		{
			$usersNewReply = null;
		}




		/*
			BEGIN >>> Redirect to new page <<<
			If we're trying to load a nodeid, and currentpage is < pagetotal, this indicates a scenario where
			a reply was posted on a page that's not the last page. vB4 behavior for this was to redirect browser
			to the page that the reply is on, so we should do the same.
		 */
		if (!empty($usersNewReply) AND $input['currentpage'] < $input['pagetotal'])
		{
			// redirect to loadnode
			$url = $api->callApi('route', 'getUrl',
				[
					'route' => $usersNewReply['routeid'],
					'data' => $usersNewReply,
					'extra' => ['p' => $usersNewReply['nodeid']]
				]
			);
			if (is_string($url))
			{
				$url = vB5_Template_Options::instance()->get('options.frontendurl') . $url;
				// TODO, return a template saying "redirecting... or something. The wait before reload is noticeable."
				return $this->sendAsJson(['redirect' => $url]);
			}
			else
			{
				// UNTESTED.
				// todo, send user to same topic, but with ?goto=newpost
				$url = $api->callApi('route', 'getUrl',
					[
						'route' => $usersNewReply['routeid'],
						'data' => ['nodeid' => $usersNewReply['starter']],
						'extra' => ['goto' => 'newpost']
					]
				);
				$url = vB5_Template_Options::instance()->get('options.frontendurl') . $url;
				return $this->sendAsJson(['redirect' => $url]);
			}
		}
		// END >>> Redirect to new page <<<



		/*
			BEGIN >>> Fetch new replies under topic <<<
		 */
		// based on widget_conversationdisplay search options
		$search_json = [
			'date' => ['from' => $input['lastpublishdate']],
			//'date' => ['from' => $input['pageload_servertime']],	// test
			'channel' => $input['parentid'],	// parentid may not be a channel, but this is how the widget gets the data displayed.
			//'filter_show' => ???,	// TODO: should we filter "new posts" by current filter?
		];
		if ($input['view'] == 'stream')
		{
			// UNTESTED &  UNSUPPORTED

			// based on vB5_Frontend_Controller_Activity::actionGet()
			$search_json['depth'] = 2;
			$search_json['view'] = 'conversation_stream';
			$search_json['sort']['created'] = 'DESC';
		}
		else
		{
			$input['view'] = 'thread';
			$search_json['view'] = 'thread';
			// thread
			$search_json['depth'] = 1;
			$search_json['view'] = 'conversation_thread';
			$search_json['sort']['created'] = 'ASC';
			$search_json['nolimit'] = 1; // TODO: remove this?
		}
		$search_json['ignore_protected'] = 1;
		if (!empty($input['loadednodes']))
		{
			$search_json['exclude'] = $input['loadednodes'];
		}

		$numAllowed = max($input['postsperpage'] - $input['postcount'], 0);
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
		$searchResult = Api_InterfaceAbstract::instance()->callApi('search', 'getInitialResults',  $functionParams);
		$newReplies = $searchResult['results'];

		// END >>> Fetch new replies under topic <<<

		/*
			BEGIN >>> Get next page URL <<<
		 */
		$routeid = false;
		$firstnode = reset($newReplies);
		if (isset($firstnode['routeid']))
		{
			$routeid = $firstnode['routeid'];
		}
		else
		{
			// UNTESTED
			$parentnode = $api->callApi('node', 'getNodeFullContent', ['nodeid' => $input['parentid'], 'contenttypeid' => false, 'options' => ['showVM' => 1, 'withParent' => 1]]);
			$parentnode = $parentnode[$input['parentid']];
			$routeid = $parentnode['routeid'];
		}
		$nextPageUrl = $api->callApi('route', 'getUrl',
			[
				'route' => $routeid,
				'data' => [
					'nodeid' => $input['parentid'],
					'pagenum' => $input['currentpage'] + 1,
				],
				'extra' => []
			]
		);
		$nextPageUrl = vB5_Template_Options::instance()->get('options.frontendurl') . $nextPageUrl;
		// END >>> Get next page URL <<<




		/*
			BEGIN >>> GENERATE TEMPLATE <<<
		 */
		$channelBbcodes = $api->callApi('content_channel', 'getBbcodeOptions', [$input['channelid']]);
		// Used for display_contenttype_threadview_header template, post index (ex. #123 link)
		$pagingInfo = [
			'currentpage' => $input['currentpage'],
			'perpage' => $input['postsperpage'],
		];
		// the template automatically calculates what the postIndex should be given the $postIndex *offset* (# of posts already on the page)
		$postIndex = $input['postcount'];
		$templateInfo = []; // This is handy for debugging. Can remove once this code is stabilized.
		$topHTML = '';
		$bottomHTML = '';
		$counter = 1;
		$newRepliesSinceTime = false;	// "New replies since ##:##"
		$moreUnreadReplies = false;		// "There are more unread replies after the current page. Please click here to..."
		$past_page_limit = false;


		// ** START ** set can_use_multiquote
		// adapted from code to set can_use_multiquote in actionLoadnode()
		// we may already have the starter in $newReplies
		$starterContent = false;
		$canUseMultiquote = false;
		foreach ($newReplies AS $k => $node)
		{
			if ($node['nodeid'] == $input['parentid'])
			{
				$starterContent = $newReplies[$k]['content'];
				break;
			}
		}
		if (!$starterContent)
		{
			$starterContent = $api->callApi('node', 'getNodeFullContent', [$input['parentid']]);
			if (!isset($starterContent['error']))
			{
				$starterContent = array_pop($starterContent);
			}
			else
			{
				$starterContent = false;
			}
		}
		//designed to duplicate some logic in the widget_conversationdisplay template that updates a flag on the nodes used
		//by the conversation_footer template.  This really needs to be pushed back on the node API, but that's a riskier fix
		if ($starterContent)
		{
			$canUseMultiquote = (
				$starterContent['canreply']
				AND $starterContent['channeltype'] != 'blog'
				AND $starterContent['channeltype'] != 'article'
			);
		}
		unset($starterContent);
		// ** END ** set can_use_multiquote


		foreach ($newReplies AS $node)
		{
			$node['content']['can_use_multiquote'] = $canUseMultiquote;

			if ($addOneStarterExcludeFix AND ($node['nodeid'] == $input['parentid']))
			{
				// This is the starter node that we couldn't exclude via search params,
				// so we have to filter it out via PHP here.
				continue;
			}
			if ($counter <= $numAllowed)
			{
				$templateInfo['reply'][$node['nodeid']] = true;
				$extra = [
					'pagingInfo' => $pagingInfo,
					'postIndex' => $postIndex++,
				];
				$topHTML .= $this->renderSinglePostTemplate($node, $input['view'], $channelBbcodes, $extra) . "\n";

				if ($input['newreplyid'] AND $node['nodeid'] == $input['newreplyid'])
				{
					// We don't want to accidentally duplicate the user's reply if it's included here.
					unset($usersNewReply);
				}
				else
				{
					// Only prepend the "New post(s) since {time}" if there are posts other than the user's post that triggered
					// this.
					$newRepliesSinceTime = true;
				}
				$counter++; // We only care about this while we're still within limit.
			}
			else  // Since we limit the search results by $numAllowed +1 or +2, we'll hit this at most twice.
			{
				// Let's not show a warning more than once.
				$past_page_limit = true;
				if (!empty($usersNewReply))
				{
					// If we've yet to render the user's new reply, there's a possibility that this node is
					// the user's. Only show the "there are more unread replies" message when there are new
					// posts OTHER than the user's new reply since the last time they checked ($input['lastpublishdate'])
					if ($usersNewReply['nodeid'] != $node['nodeid'])
					{
						$moreUnreadReplies = true;
					}
				}
				else
				{
					// If we're not also fetching the user's reply, or we already rendered it within $numAllowed (above),
					// this reply will always be on the 'second page'.
					$moreUnreadReplies = true;
				}
			}
		}


		if ($newRepliesSinceTime)
		{
			$templateInfo['new_replies_since_x'] = true;
			$topHTML = $this->renderPostNoticeTemplate('new_replies_since_x', ['timestamp' => $input['lastloadtime']])
						. "\n" . $topHTML;
		}
		if (!empty($topHTML))
		{
			$topHTML .= "\n"; // If we have any replies etc rendered, add newline for human eyes looking at the HTML
		}

		if (!empty($usersNewReply))
		{
			// TODO: Add something for stream view (reverse order)?
			if (empty($input['past_page_limit_aware']) AND $input['view'] == 'thread')
			{
				$templateInfo['replies_below_on_next_page'] = true;
				// Put up a warning saying below do not fit on the current page
				$bottomHTML = $this->renderPostNoticeTemplate('replies_below_on_next_page', ['nextpageurl' => $nextPageUrl]);
			}
			$templateInfo['user_own_reply'][$usersNewReply['nodeid']] = true;
			$extra = [
				'pagingInfo' => $pagingInfo,
				'postIndex' => $postIndex++,
			];

			$usersNewReply['content']['can_use_multiquote'] = $canUseMultiquote;

			$bottomHTML .= $this->renderSinglePostTemplate($usersNewReply, $input['view'], $channelBbcodes, $extra) . "\n";
		}

		if ($moreUnreadReplies)
		{
			$templateInfo['more_replies_after_current_page'] = true;
			$bottomHTML .= $this->renderPostNoticeTemplate('more_replies_after_current_page', ['nextpageurl' => $nextPageUrl]);
		}

		$template = $topHTML . $bottomHTML;
		if (empty($template))
		{
			$templateInfo['no_new_replies_at_x'] = true;
			$template = $this->renderPostNoticeTemplate('no_new_replies_at_x', ['timestamp' => vB5_Request::get('timeNow')]);
		}

		// END >>> GENERATE TEMPLATE <<<

		/*
			BEGIN	>>> Return results array <<<
		 */
		$results = [];
		$results['success'] = true;
		$results['past_page_limit'] = $past_page_limit;
		$results['timenow'] = vB5_Request::get('timeNow');
		$results['template'] = $template;
		$results['css_links'] = vB5_Template_Stylesheet::instance()->getAjaxCssLinks();
		// CLOSE CONNECTION BEFORE WE DO SOME RESPONSE-UNRELATED BACKEND WORK
		$this->sendAsJsonAndCloseConnection($results);

		// END	>>> Return results array <<<

		/*
			The reason I decided not to just do markread via AJAX + apidetach is that the timenow would be different, since
			the current session's time and the that apidetach/node/markread call would have a bit of lag. So it's more
			correct to do it here, and saves a request to do so.
			We should decouple the "close request" logic from applicationlight's handleAjaxApiDetached() into a separate
			function, and call it from here.
		 */
		// The library markRead() function handles the case when user is a guest. JS needs to handle the case when
		// it's cookie based threadmarking.
		$api->callApi('node', 'markRead', [$input['parentid']]);

		return;
	}

	protected function renderPostNoticeTemplate($phrase_name, $data = [])
	{
		/*
			Template display_threadview_post_notice only supports single phrase var atm.
			If we need to support variable phrase var, we either need a vb:var_array or
			use vb:raw on the phrase_var parameter and investigate whether allowing
			vb:raw there is safe, and html-escape any URLs used in html (nextpageurl).
		 */
		$template_name = 'display_threadview_post_notice';
		switch($phrase_name)
		{
			case 'new_replies_since_x':
				$phrase_var = vB5_Template_Runtime::time($data['timestamp']);
				break;
			case 'no_new_replies_at_x':
				$phrase_var = vB5_Template_Runtime::time($data['timestamp']);
				break;
			case 'replies_below_on_next_page':
				$phrase_var = $data['nextpageurl'];
				break;
			case 'more_replies_after_current_page':
				$phrase_var = $data['nextpageurl'];
				break;
			default:
				return;
				break;
		}

		$templater = new vB5_Template($template_name);
		$templater->register('phrase_name', $phrase_name);
		$templater->register('phrase_var', $phrase_var);

		return $templater->render(true, true);
	}

	protected function renderSinglePostTemplate($node, $view, $channelBbcodes, $additionalData = [])
	{
		if (empty($node))
		{
			return '';
		}
		/*
		TODO: add support for blogs & articles
		 */

		if ($view == 'stream')
		{
			$templatenamePrefix = 'display_contenttype_conversationreply_';
		}
		else
		{
			// thread
			$templatenamePrefix = 'display_contenttype_conversationreply_threadview_';
		}

		$template = $templatenamePrefix . $node['contenttypeclass'];

		$templater = new vB5_Template($template);
		$templater->register('nodeid', $node['nodeid']);
		$templater->register('conversation', $node['content']);
		$templater->register('currentConversation', $node);
		$templater->register('bbcodeOptions', $channelBbcodes);
		//$templater->register('hidePostIndex', true);	// TODO: figure post# bits out.
		if (isset($additionalData['pagingInfo']))
		{
			$templater->register('pagingInfo', $additionalData['pagingInfo']);
		}
		if (isset($additionalData['pagingInfo']))
		{
			$templater->register('postIndex', $additionalData['postIndex']);
		}
		$templater->register('reportActivity', ($view == 'stream'));
		$templater->register('showChannelInfo', false);
		if ($view == 'thread')
		{
			$templater->register('showInlineMod', true);
			//$templater->register('commentsPerPage', $additionalData['comments-perpage']); // TODO: comments
		}
		else if ($view == 'stream' AND !$node['isVisitorMessage']) // Visitor Message doesn't allow to be quoted. See VBV-5583.
		{
			$templater->register('view', 'conversation_detail');
		}

		return $templater->render(true, true);
	}



	/**
	 * This handles all saves of blog data.
	 */
	public function actionBlog()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		$fields = [
			'title',
			'description',
			'nodeid',
			'filedataid',
			'invite_usernames',
			'invite_userids',
			'viewperms',
			'commentperms',
			'moderate_comments',
			'approve_membership',
			'allow_post',
			'autoparselinks',
			'disablesmilies',
			'sidebarInfo',
		];

		// forum options map
		$channelOpts = ['allowsmilies' => 'disablesmilies', 'allowposting' => 'allow_post'];

		$input = [];
		foreach ($fields AS $field)
		{
			if (isset($_POST[$field]))
			{
				$input[$field] = $_POST[$field];
			}
		}

		// allowsmilies is general
		if (isset($_POST['next']) AND ($_POST['next'] == 'permissions'))
		{
			foreach (['autoparselinks', 'disablesmilies'] AS $field)
			{
				// channeloptions
				if ($idx = array_search($field, $channelOpts))
				{
					// some options means totally the oppositve than the bf when enable, tweak then
					if (isset($_POST[$field]))
					{
						$input['options'][$idx] = (in_array($field, ['disablesmilies']) ? 0 : 1);
					}
					else
					{
						$input['options'][$idx] = (in_array($field, ['disablesmilies']) ? 1 : 0);
					}
				}

				if (!isset($_POST[$field]))
				{
					$input[$field] = 0;
				}
			}
		}


		//If this is the "permission" step, we must pass the three checkboxes
		if (isset($_POST['next']) AND ($_POST['next'] == 'contributors'))
		{
			foreach (['moderate_comments', 'approve_membership', 'allow_post'] AS $field )
			{
				if ($idx = array_search($field, $channelOpts))
				{
					// some options means totally the oppositve than the bf when enable, tweak then
					if (isset($_POST[$field]))
					{
						$input['options'][$idx] = 1;
					}
					else
					{
						$input['options'][$idx] = 0;
					}
				}

				if (!isset($_POST[$field]))
				{
					$input[$field] = 0;
				}
			}
		}
		if (empty($input['options']))
		{
			$input['options'] = [];
		}
		// Other default options
		$input['options'] += [
			'allowbbcode' => 1,
			'allowimages' => 1,
		];
		$input['auto_subscribe_on_join'] = 1;
		$input['displayorder'] = 1;

		$api = Api_InterfaceAbstract::instance();

		//check if in quick create blog mode (in overlay and non-wizard type)
		$quickCreateBlog = (isset($_POST['wizard']) AND $_POST['wizard'] == '0') ? true : false;

		if (count($input) > 1)
		{
			$input['parentid'] = $api->callApi('blog', 'getBlogChannel');
			if (empty($input['nodeid']))
			{
				$nodeid = $api->callApi('blog', 'createBlog', [$input]);

				if (is_array($nodeid) AND array_key_exists('errors', $nodeid))
				{
					if ($quickCreateBlog)
					{
						$this->sendAsJson($nodeid);
						return;
					}
					else
					{
						$urlparams = ['blogaction' => 'create', 'action2' => 'settings'];
						$url = $api->callApi('route', 'getUrl', ['blogadmin|fullurl', $urlparams]);
						vB5_ApplicationAbstract::handleFormError($nodeid['errors'], $url);
					}
				}
			}
			else if (isset($input['invite_usernames']) AND $input['nodeid'])
			{
				// What we get are raw, unescaped usernames, but API expects escaped usernames.
				$inviteUnames = explode(',', $input['invite_usernames']);
				$inviteUnames = array_map('vB5_String::htmlSpecialCharsUni', $inviteUnames);
				$inviteIds = (isset($input['invite_userids'])) ? $input['invite_userids'] : [];
				$nodeid = $input['nodeid'];
				$api->callApi('user', 'inviteMembers', [$inviteIds, $inviteUnames, $nodeid, 'member_to']);
			}
			else if (isset($input['sidebarInfo']) AND $input['nodeid'])
			{
				$modules = explode(',', $input['sidebarInfo']);
				$nodeid = $input['nodeid'];
				foreach ($modules AS $key => $val)
				{
					$info = explode(':', $val);
					$modules[$key] = ['widgetinstanceid' => $info[0], 'hide' => ($info[1] == 'hide')];
				}
				$api->callApi('blog', 'saveBlogSidebarModules', [$input['nodeid'], $modules]);
			}
			else
			{

				foreach (['allow_post', 'moderate_comments', 'approve_membership', 'autoparselinks', 'disablesmilies'] AS $bitfield)
				{
					if (!empty($_POST[$bitfield]))
					{
						$input[$bitfield] = 1;
					}
				}

				$nodeid = $input['nodeid'];
				unset($input['nodeid']);
				$api->callApi('content_channel', 'update', [$nodeid, $input]);

				//if this is for the permission page we handle differently

			}
//			set_exception_handler(['vB5_ApplicationAbstract','handleException']);
//
//			if (!is_numeric($nodeid) AND !empty($nodeid['errors']))
//			{
//				throw new exception($nodeid['errors'][0][0]);
//			}
		}
		else if (isset($_POST['nodeid']))
		{
			$nodeid = $_POST['nodeid'];
			if (isset($_POST['next']) AND ($_POST['next'] == 'contributors'))
			{
				$updates = [];
				foreach (['allow_post', 'moderate_comments', 'approve_membership'] AS $bitfield)
				{

					if (empty($_POST[$bitfield]))
					{
						$updates[$bitfield] = 0;
					}
					else
					{
						$updates[$bitfield] = 1;
					}
				}
				$api->callApi('node', 'setNodeOptions', [$nodeid, $updates]);
				$updates = [];

				if (isset($_POST['viewperms']))
				{
					$updates['viewperms'] = $_POST['viewperms'];
				}

				if (isset($_POST['commentperms']))
				{
					$updates['commentperms'] = $_POST['commentperms'];
				}

				if (!empty($updates))
				{
					$results = $api->callApi('node', 'setNodePerms', [$nodeid, $updates]);
				}

			}
		}
		else
		{
			$nodeid = 0;
		}

		//If the user clicked Next we go to the permissions page. Otherwise we go to the node.
		if (isset($_POST['btnSubmit']))
		{
			if (isset($_POST['next']))
			{
				$action2 = $_POST['next'];
			}
			else
			{
				$action2 = 'permissions';
			}

			if (isset($_POST['blogaction']))
			{
				$blogaction = $_POST['blogaction'];
			}
			else
			{
				$blogaction = 'admin';
			}

			if (
				($action2 == 'permissions') AND
				!($api->callApi('user', 'hasPermissions', ['group' => 'forumpermissions2', 'permission' => 'canconfigchannel', 'nodeid' => $nodeid]))
			)
			{
				$action2 = 'contributors';
			}

			$urlparams = [
				'nodeid' => $nodeid,
				'blogaction' => $blogaction,
				'action2' => $action2,
			];

			// pass message to the next page
			$flashMessage = $this->encodeFlashMessage('changes_saved');
			$queryparams = ['flashmsg' => $flashMessage];

			$url = $api->callApi('route', 'getUrl', ['blogadmin|fullurl', $urlparams, $queryparams]);
		}
		else if ($quickCreateBlog)
		{
			$this->sendAsJson(['nodeid' => $nodeid]);
			return;
		}
		// btnSave OR btnPublish
		else
		{
			// when editing blog settings, redirect back to the settings when possible
			// but not when using the Publish button
			if (isset($_POST['btnSave']) AND !empty($_POST['blogaction']) AND !empty($_POST['current_channel_admin_page']))
			{
				$blogaction = preg_replace('#[^a-z]#siU', '', (string) $_POST['blogaction']);
				$action2 = preg_replace('#[^a-z]#siU', '', (string) $_POST['current_channel_admin_page']);

				$urlparams = [
					'nodeid' => $nodeid,
					'blogaction' => $blogaction,
					'action2' => $action2,
				];

				// pass message to the next page
				$flashMessage = $this->encodeFlashMessage('changes_saved');
				$queryparams = ['flashmsg' => $flashMessage];

				$url = $api->callApi('route', 'getUrl', ['blogadmin|fullurl', $urlparams, $queryparams]);
			}
			else
			{
				$node = $api->callApi('node', 'getNode', ['nodeid' => $nodeid]);
				$url = $api->callApi('route', 'getUrl', [
					$node['routeid'] . '|fullurl',
					['nodeid' => $nodeid, 'title' => $node['title'], 'urlident' => $node['urlident']],
				]);
			}
		}

		header('Location: ' . $url);
	}

	/**
	 * This added one or more channels.  It is intended to be called from the wizard.
	 */
	public function actionChannel()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		if (empty($_REQUEST['title']))
		{
			return ['error' => 'invalid_data'];
		}
		$api = Api_InterfaceAbstract::instance();
		//We don't need a parentid, because the channels are by default create at root.

		if (empty($_REQUEST['parentid']) OR !intval($_REQUEST['parentid']))
		{
			$rootChannels = $api->callApi('content_channel', 'fetchTopLevelChannelIds', []);
			$data['parentid'] = $rootChannels['forum'];
		}
		else
		{
			$data['parentid'] = $_REQUEST['parentid'];
		}

		$data['title'] = $_REQUEST['title'];
		if (!empty($_REQUEST['description']) AND is_string($_REQUEST['description']))
		{
			$data['description'] = $_REQUEST['description'];
		}
		$result =  $api->callApi('content_channel', 'add', [$data]);

		if (!empty($result['errors']))
		{
			return $result['errors'];
		}

		$canDelete = $api->callApi('user', 'hasPermissions', ['adminpermissions', 'canadminforums']);

		if (!$canDelete)
		{
			$canDelete = $api->callApi('user', 'hasPermissions', ['forumpermissions2', 'candeletechannel', $data['parentid']]);
		}
		$this->sendAsJson(['nodeid' => $result, 'candelete' => (int)$canDelete]);
	}

	/**
	 * This handles all saves of social group data.
	 */
	public function actionSocialgroup()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		$fields = [
			'title',
			'description',
			'nodeid',
			'filedataid',
			'invite_usernames',
			'parentid',
			'invite_userids',
			'group_type',
			'viewperms',
			'commentperms',
			'moderate_topics',
			'autoparselinks',
			'disablesmilies',
			'allow_post',
			'approve_subscription',
			'group_type',
		];
		// forum options map
		$channelOpts = ['allowsmilies' => 'disablesmilies', 'allowposting' => 'allow_post'];

		$input = [];
		foreach ($fields AS $field)
		{
			if (isset($_POST[$field]))
			{
				$input[$field] = $_POST[$field];
			}
		}

		//If this is the "permission" step, we must pass the four checkboxes
		if (isset($_POST['next']) AND ($_POST['next'] == 'contributors'))
		{
			foreach (['moderate_comments', 'autoparselinks', 'disablesmilies', 'allow_post', 'approve_subscription', 'moderate_topics'] AS $field)
			{
				// channeloptions
				if ($idx = array_search($field, $channelOpts))
				{
					// some options means totally the oppositve than the bf when enable, tweak then
					if (isset($_POST[$field]))
					{
						$input['options'][$idx] = (in_array($field, ['disablesmilies']) ? 0 : 1);
					}
					else
					{
						$input['options'][$idx] = (in_array($field, ['disablesmilies']) ? 1 : 0);
					}
				}

				// I have no idea what this is on about. Below seems to ignore the "flip" above, and above does not agree with the
				// (isset($_POST['nodeid'])) block way below. Perhaps the checkboxes displayed are different between a "create"
				// case and an "update" case??
				// Furthermore, sometimes these options go through socialgroup::createSocialGroup()/content_channel::update(),
				// while other times they go through node::setNodeOptions(). It's not very clear which these channel options
				// actually work through the socialgroup or channel APIs, but the frontend seems to more commonly set these options
				// via setNodeOptions() instead of channel updates, and that seems to provide more consistent results per reports
				// from mobile devs. I'm also not sure at the moment how the hypothetical `channel`.`options` overriding
				// `node`.`nodeoptions` works in the backend.

				if (!isset($_POST[$field]))
				{
					$input[$field] = 0;
				}
			}
		}

		// default input values
		$input['displayorder'] = 1;

		$api = Api_InterfaceAbstract::instance();
		if (count($input) > 1)
		{
			if (!isset($input['nodeid']) OR (intval($input['nodeid']) == 0))
			{
				$nodeid = $api->callApi('socialgroup', 'createSocialGroup', [$input]);
				if (is_array($nodeid) AND array_key_exists('errors', $nodeid))
				{
					$urlparams = ['sgaction' => 'create', 'action2' => 'settings'];
					$url = $api->callApi('route', 'getUrl', ['sgadmin|fullurl', $urlparams]);
					vB5_ApplicationAbstract::handleFormError($nodeid['errors'], $url);
				}
			}
			else if (isset($input['invite_usernames']) AND $input['nodeid'])
			{
				// What we get are raw, unescaped usernames, but API expects escaped usernames.
				$inviteUnames = explode(',', $input['invite_usernames']);
				$inviteUnames = array_map('vB5_String::htmlSpecialCharsUni', $inviteUnames);
				$inviteIds = (isset($input['invite_userids'])) ? $input['invite_userids'] : [];
				$nodeid = $input['nodeid'];
				$api->callApi('user', 'inviteMembers', [$inviteIds, $inviteUnames, $nodeid, 'sg_member_to']);
			}
			else
			{
				$nodeid = $input['nodeid'];
				unset($input['nodeid']);

				$update = $api->callApi('content_channel', 'update', [$nodeid, $input]);

				// set group type nodeoptions
				if (empty($update['errors']) AND isset($input['group_type']))
				{
					$bitfields = [];
					switch ($input['group_type'])
					{
						case 2:
							$bitfields['invite_only'] = 1;
							$bitfields['approve_membership'] = 0;
							break;
						case 1:
							$bitfields['invite_only'] = 0;
							$bitfields['approve_membership'] = 0;
							break;
						default:
							$bitfields['invite_only'] = 0;
							$bitfields['approve_membership'] = 1;
							break;
					}

					$api->callApi('node', 'setNodeOptions', [$nodeid, $bitfields]);
					$api->callApi('socialgroup', 'changeCategory', [$nodeid, $input['parentid']]);
				}
			}
		}
		else if (isset($_POST['nodeid']))
		{
			$nodeid = $_POST['nodeid'];
			if (isset($_POST['next']) AND ($_POST['next'] == 'contributors'))
			{
				$updates = [];
				foreach (['allow_post', 'moderate_comments', 'autoparselinks', 'disablesmilies', 'approve_subscription'] AS $bitfield)
				{
					if (empty($_POST[$bitfield]))
					{
						$updates[$bitfield] = 0;
					}
					else
					{
						$updates[$bitfield] = 1;
					}
				}
				$api->callApi('node', 'setNodeOptions', [$nodeid, $updates]);
				$updates = [];

				if (isset($_POST['viewperms']))
				{
					$updates['viewperms'] = $_POST['viewperms'];
				}

				if (isset($_POST['commentperms']))
				{
					$updates['commentperms'] = $_POST['commentperms'];
				}

				if (!empty($updates))
				{
					$results = $api->callApi('node', 'setNodePerms', [$nodeid, $updates]);
				}
			}
		}
		else
		{
			$nodeid = 0;
		}

		//If the user clicked Next we go to the permissions page. Otherwise we go to the node.
		if (isset($_POST['btnSubmit']))
		{
			if (isset($_POST['next']))
			{
				$action2 = $_POST['next'];
			}
			else
			{
				$action2 = 'permissions';
			}

			if (isset($_POST['sgaction']))
			{
				$sgaction = $_POST['sgaction'];
			}
			else
			{
				$sgaction = 'admin';
			}

			$urlparams = [
				'nodeid' => $nodeid,
				'sgaction' => $sgaction,
				'action2' => $action2,
			];

			// pass message to the next page
			$flashMessage = $this->encodeFlashMessage('changes_saved');
			$queryparams = ['flashmsg' => $flashMessage];

			$url = $api->callApi('route', 'getUrl', ['sgadmin|fullurl', $urlparams, $queryparams]);
		}
		// btnSave OR btnPublish
		else
		{
			// when editing group settings, redirect back to the settings when possible
			// but not when using the Publish button
			if (isset($_POST['btnSave']) AND !empty($_POST['sgaction']) AND !empty($_POST['current_channel_admin_page']))
			{
				$sgaction = preg_replace('#[^a-z]#siU', '', (string) $_POST['sgaction']);
				$action2 = preg_replace('#[^a-z]#siU', '', (string) $_POST['current_channel_admin_page']);

				$urlparams = [
					'nodeid' => $nodeid,
					'sgaction' => $sgaction,
					'action2' => $action2,
				];

				// pass message to the next page
				$flashMessage = $this->encodeFlashMessage('changes_saved');
				$queryparams = ['flashmsg' => $flashMessage];

				$url = $api->callApi('route', 'getUrl', ['sgadmin|fullurl', $urlparams, $queryparams]);
			}
			else
			{
				$node = $api->callApi('node', 'getNode', ['nodeid' => $nodeid]);
				$url = $api->callApi('route', 'getUrl', [
					$node['routeid'] . '|fullurl',
					['nodeid' => $nodeid, 'title' => $node['title'], 'urlident' => $node['urlident']],
				]);
			}
		}

		header('Location: ' . $url);
	}

	/**
	 * Returns an array of quotes
	 */
	public function actionFetchQuotes()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		$quotes = [];
		$nodeids = $_REQUEST['nodeid'] ?? [];

		if (!empty($nodeids))
		{
			$contenttypes = vB_Types::instance()->getContentTypes();
			$typelist = [];
			foreach ($contenttypes AS $key => $type)
			{
				$typelist[$type['id']] = $key;
			}

			$api = Api_InterfaceAbstract::instance();
			$contentTypes = ['vBForum_Text', 'vBForum_Gallery', 'vBForum_Poll', 'vBForum_Video', 'vBForum_Link', 'vBForum_Infraction', 'vBForum_Event'];

			foreach ($nodeids AS $nodeid)
			{
				$node = $api->callApi('node', 'getNode', [$nodeid]);
				$contentType = $typelist[$node['contenttypeid']];
				if (in_array($contentType, $contentTypes))
				{
					$quotes[$nodeid] = $api->callApi('content_' . strtolower(substr($contentType, 8)), 'getQuotes', [$nodeid]);
				}
			}
		}

		$this->sendAsJson($quotes);
	}

	/**
	 * This sets a return url when creating new content and sets if the created content
	 * is a visitor message
	 */
	protected function getReturnUrl($channelid, $parentid, $nodeid, $queryparams = [])
	{
		$api = Api_InterfaceAbstract::instance();
		$returnUrl = '';

		// ensure we have a channelid for the redirect
		if (!$channelid && $parentid)
		{
			try
			{
				$channel = $api->callApi('content_channel', 'fetchChannelById', [$parentid]);
				if ($channel && isset($channel['nodeid']) && $channel['nodeid'])
				{
					$channelid = $channel['nodeid'];
				}
			}
			catch (Exception $e){}
		}

		//Get the conversation detail page of the newly created post if we are creating a starter
		if ($channelid == $parentid)
		{
			if (isset($result['moderateNode']))
			{
				$nodeid = $parentid;
			}
			$node = $api->callApi('node', 'getNode', [$nodeid]);
			if ($node AND empty($node['errors']))
			{
				$url = $api->callApi('route', 'getUrl', ['route' => $node['routeid'], 'data' => $node, 'extra' => $queryparams]);
				if (is_string($url))
				{
					$returnUrl = vB5_Template_Options::instance()->get('options.frontendurl') . $url;
				}
				else
				{
					// if the user can't view the item they just created, return to the channel.
					$channel = $api->callApi('content_channel', 'fetchChannelById', [$channelid]);
					$url = $api->callApi('route', 'getUrl', ['route' => $channel['routeid'], 'data' => $channel, 'extra' => $queryparams]);
					if (is_string($url))
					{
						$returnUrl = vB5_Template_Options::instance()->get('options.frontendurl') . $url;
					}
				}
			}
		}

		return $returnUrl;
	}

	// handleAttachmentUploads() removed. Adding/removing attachments are now done inside vB_Library_Content_Text->add() & update()
	// using the 'attachments' & 'removeattachments' data generated from $_POST by addAttachments().

	// addAttachments() moved to parent so that other controllers that saves
	// post content (ex. upload which handles gallery edits) can have access to it.

	/**
	 * Returns a URL Ident corresponding to the text
	 *
	 * @param  string Text (usually a node title) to convert to a URL Ident
	 *
	 * @return string URL Ident
	 */
	public function actionGetUrlIdent()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		$text = strval($_POST['text'] ?? '');
		$return = ['urlident' => vB_String::getUrlIdent($text)];

		$this->sendAsJson($return);
	}

	public function actionSetExclusiveAnswer()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		$results = [];
		$nodeid = intval($_POST['nodeid'] ?? 0);
		if ($nodeid < 1)
		{
			$results['error'] = 'invalid_node';
			$this->sendAsJson($results);
			return;
		}
		$doset = !empty($_POST['set']);
		$filters = $_POST['searchFilters'] ?? [];

		$api = Api_InterfaceAbstract::instance();
		$apiResult = $api->callApi('node', 'setExclusiveAnswer', [$nodeid, $doset]);
		if (!empty($apiResult['errors']))
		{
			return $this->sendAsJson($apiResult);
		}

		$starter = $api->callApi('node', 'getNodeFullContent', [$apiResult['starter']]);
		if (!empty($starter['errors']))
		{
			return $this->sendAsJson($starter);
		}
		$starter = $starter[$apiResult['starter']];

		$searchOptions = vB5_Frontend_Controller_Activity::mapFormFiltersToSearchOptions($filters);
		$getAjaxCssLinks = true;
		$result = vB5_Frontend_Controller_Activity::getPinnedRepliesTemplateRender(
			$starter,
			$searchOptions,
			$getAjaxCssLinks
		);

		$result['answers'] = $apiResult['answers'];

		return $this->sendAsJson($result);
	}

	public function actionGetlinkdata()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		$input = [
			'url' => trim($_REQUEST['url']),
		];

		$api = Api_InterfaceAbstract::instance();

		$video = $api->callApi('content_video', 'getVideoFromUrl', [$input['url']]);
		$data = $api->callApi('content_link', 'parsePage', [$input['url']]);

		if ($video AND empty($video['errors']))
		{
			$result = vB5_Template::staticRenderAjax('video_edit', [
				'video' => $video,
				'existing' => 0,
				'editMode' => 1,
				'title' => $data['title'],
				'url' => $input['url'],
				'meta' => $data['meta'],
			]);
			$result['contenttype'] = 'video';
		}
		else
		{
			if ($data AND empty($data['errors']))
			{
				$result = vB5_Template::staticRenderAjax('link_edit', [
					'images' => $data['images'],
					'title' => $data['title'],
					'url' => $input['url'],
					'meta' => $data['meta'],
				]);
				$result['contenttype'] = 'link';
			}
			else
			{
				$result = [
					'error' => 'upload_invalid_url',
					'css_links' => [],
				];
			}
		}

		$this->sendAsJson($result);
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 115156 $
|| #######################################################################
\*=========================================================================*/
