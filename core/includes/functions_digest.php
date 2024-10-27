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

// ###################### Start dodigest #######################
function exec_digest($type = 2)
{
	// type = 2 : daily
	// type = 3 : weekly

	$lastdate = mktime(0, 0); // midnight today
	if ($type == 2)
	{
		// yesterday midnight
		$lastdate -= 24 * 60 * 60;
	}
	else
	{
		// last week midnight
		$lastdate -= 7 * 24 * 60 * 60;
	}

	$datastore = vB::getDatastore();
	$db = vB::getDbAssertor();
	$phraseApi = vB_Api::instanceInternal('phrase');

	$globalignore = $datastore->getOption('globalignore');
	if (trim($globalignore) != '')
	{
		$coventry = preg_split('#\s+#s', $globalignore, -1, PREG_SPLIT_NO_EMPTY);
	}
	else
	{
		$coventry = [];
	}

	require_once(DIR . '/includes/class_bbcode_alt.php');
	$vbulletin = vB::get_registry();
	$plaintext_parser = new vB_BbCodeParser_PlainText($vbulletin, fetch_tag_list());

	vB_Mail::vbmailStart();

	$bf_misc_useroptions = $datastore->getValue('bf_misc_useroptions');
	$bf_ugp_genericoptions = $datastore->getValue('bf_ugp_genericoptions');
	$bf_ugp_forumpermissions = $datastore->getValue('bf_ugp_forumpermissions');

	// get new threads (Topic Subscription)
	$threads = $db->getRows('getNewThreads', [
		'dstonoff' => $bf_misc_useroptions['dstonoff'],
		'dstauto' => $bf_misc_useroptions['dstauto'],
		'isnotbannedgroup' => $bf_ugp_genericoptions['isnotbannedgroup'],
		'lastdate' => intval($lastdate)
	]);

	// grab all forums / subforums for given subscription (Channel Subscription)
	$forums = $db->assertQuery('getNewForums', [
		'dstonoff' => $bf_misc_useroptions['dstonoff'],
		'dstauto' => $bf_misc_useroptions['dstauto'],
		'type' => intval($type),
		'lastdate' => intval($lastdate),
		'channelcontenttype' => vB_Api::instanceInternal('contenttype')->fetchContentTypeIdFromClass('Channel'),
		'isnotbannedgroup' => $bf_ugp_genericoptions['isnotbannedgroup']
	]);

	// we want to fetch all language records at once and using cache if possible
	// Let's just always set & fetch the default language. Chances of it being used are pretty high.
	$defaultLanguage = intval($datastore->getOption('languageid'));
	$languageIds = [$defaultLanguage];

	$useridsForPrefetch = [];
	// Let's see which languageids we wanna fetch
	foreach ($threads AS $thread)
	{
		$languageIds[] = $thread['languageid'];
		$useridsForPrefetch[] = $thread['userid'];
	}

	foreach ($forums AS $forum)
	{
		$languageIds[] = $forum['languageid'];
		$useridsForPrefetch[] = $forum['userid'];
	}

	/** @var vB_Library_Unsubscribe */
	$unsubLib = vB_Library::instance('unsubscribe');
	$unsubLib->prefetchUserHashes($useridsForPrefetch);
	//$unsubLib->prefetchUserMailOptions($useridsForPrefetch);

	// fetch languages
	$defaultDateformat = $datastore->getOption('dateformat');
	$defaultTimeformat = $datastore->getOption('timeformat');
	$languages = vB_Library::instance('language')->getLanguageCache($languageIds);

	//update the date formats if we don't have them
	foreach($languages AS $key => $langInfo)
	{
		if(!$langInfo['dateoverride'])
		{
			$languages[$key]['dateoverride'] = $defaultDateformat;
		}

		if(!$langInfo['timeoverride'])
		{
			$languages[$key]['timeoverride'] = $defaultTimeformat;
		}
	}

	$currentUserId = vB::getCurrentSession()->get('userid');
	$request = vB::getRequest();
	$string = vB::getString();
	$bbtitle = $datastore->getOption('bbtitle');
	$bbtitle_escaped = $string->htmlspecialchars($bbtitle);
	$settingsURL = vB5_Route::buildUrl('settings|fullurl', ['tab' => 'notifications']);

	try
	{
		// process threads -- note that "thread" is a hybrid record of the
		// subscription, the subscribed user, and the subscribed thread.  Some of the fields may not
		// be what you are expecting them to be.
		foreach ($threads AS $thread)
		{
			$postbits = '';

			// Make sure user have correct email notification settings.
			if ($thread['emailnotification'] != $type)
			{
				continue;
			}

			// Check mail opt-out. This may not actually be needed, as if they unsubscribed from all mails via the unsubscribe API, that should've
			// set 'emailnotification' to 0, which we check right above.
			// if ($unsubLib->isUserOptedOutOfEmail($thread['userid']))
			// {
			// 	continue;
			// }

			if ($thread['lastauthorid'] != $thread['userid'] AND in_array($thread['lastauthorid'], $coventry))
			{
				continue;
			}

			//privilege escalation.  This isn't something to do lightly, but as a backend script we really need
			//to generate the email with the permissions of the person we are sending it to
			$request->createSessionForUser($thread['userid']);

			$usercontext = vB::getUserContext($thread['userid']);
			if (
				!$usercontext->getChannelPermission('forumpermissions', 'canview', $thread['nodeid']) OR
				!$usercontext->getChannelPermission('forumpermissions', 'canviewthreads', $thread['nodeid']) OR
				($thread['authorid'] != $thread['userid'] AND !$usercontext->getChannelPermission('forumpermissions', 'canviewothers', $thread['nodeid']))
			)
			{
				continue;
			}

			$langInfo = $languages[$thread['languageid']] ?? $languages[$defaultLanguage];

			$userinfo = [
				'lang_locale'    => $langInfo['locale'],
				'dstonoff'       => $thread['dstonoff'],
				'dstauto'        => $thread['dstauto'],
				'timezoneoffset' => $thread['timezoneoffset'],
			];

			//this is the *subscribing* user, not a user associated with the thread.
			$thread['username'] = $thread['username'];
			$thread['newposts'] = 0;
			$thread['displayname_safe'] = $string->htmlspecialchars($thread['displayname']);

			//change some fields from the query to better display in the email.
			exec_digest_modify_thread($thread, $phraseApi, $langInfo, $userinfo);

			// Note: closure.depth = 1  on the where clause means getNewPosts only grabs replies, not comments.
			$posts = $db->getRows('getNewPosts', array('threadid' => intval($thread['nodeid']), 'lastdate' => intval($lastdate)));

			// compile
			$haveothers = false;
			foreach ($posts AS $post)
			{
				if ($post['userid'] != $thread['userid'] AND in_array($post['userid'], $coventry))
				{
					continue;
				}

				if ($post['userid'] != $thread['userid'])
				{
					$haveothers = true;
				}

				$thread['newposts']++;
				$post['htmltitle'] = $post['htmltitle'];
				$post['postdate'] = vbdate($langInfo['dateoverride'], $post['publishdate'], false, true, true, false, $userinfo);
				$post['posttime'] = vbdate($langInfo['timeoverride'], $post['publishdate'], false, true, true, false, $userinfo);
				$post['postusername'] = getEmailUserLabelForDigest($post['authorname'], $post['userid'], $thread['languageid']);

				$contentAPI = vB_Library_Content::getContentApi($post['contenttypeid']);
				$contents = $contentAPI->getContent($post['nodeid']);

				$plaintext_parser->set_parsing_language($thread['languageid']);
				$post['pagetext'] = $plaintext_parser->parse($contents[$post['nodeid']]['rawtext'], $thread['parentid']);
				// Note, digestpostbit phrase puts the pagetext around <pre> tags, so nl2br is skipped. This rawtext skips the standard getPostTextForEmail()
				// process because it's going through its own plaintext parser, and I don't want to change that behavior right now.
				// We may want to switch this over to a standardized email parser strategy if we parse other email post snippets in the future.
				// For that, leaving a couple of comments for turning up in greps in the future:
				// vB_Mail::getPostTextForEmail($contents[$post['nodeid']]['rawtext'], $contents[$post['nodeid']]['htmlstate'], vB::getString());
				// vB_Mail::getPreviewTextForEmail($contents[$post['nodeid']]['rawtext'], $contents[$post['nodeid']]['htmlstate'], vB::getString());

				$postlink = vB5_Route::buildUrl($post['routeid'] . '|bburl', ['nodeid' => $post['nodeid']]);
				// When we start using anchors, we need to also html escape links.
				//$postlink = $string->htmlspecialchars($postlink);

				$phrase = [
					'digestpostbit',
					$post['htmltitle'],
					$postlink,
					$post['postusername'],
					$post['postdate'],
					$post['posttime'],
					$post['pagetext'],
				];
				$phrases = $phraseApi->renderPhrasesNoShortcode(['postbit' => $phrase], $thread['languageid']);

				$postbits .= $phrases['phrases']['postbit'];
			}

			// Don't send an update if the subscriber is the only one who posted in the thread.
			if ($haveothers)
			{
				// make email
				// magic vars used by the phrase eval
				$threadlink = vB5_Route::buildUrl($thread['routeid'] . '|fullurl', ['nodeid' => $thread['nodeid']]);
				// When we start using anchors, we also need to escape links.
				//$threadlink = $string->htmlspecialchars($threadlink);


				$unsubscribelink = vB_Library::instance('notification')->generateUrlForUnsubscribe($thread['nodeid'], $thread['userid']);

				// note, $postbits gets its own parsing into an HTML version above, so we skip parsePostTextForEmail() for those post texts.
				$maildata = $phraseApi->fetchEmailPhrases(
					'digestthread',
					[
						$thread['displayname_safe'],
						$thread['prefix_plain'],
						$thread['htmltitle'],
						$thread['postusername'],
						$thread['newposts'],
						$thread['lastposter'],
						$threadlink,
						$postbits,
						$bbtitle_escaped,
						$unsubscribelink,
						$settingsURL,
					],
					[
						$thread['prefix_plain'],
						//subject needs to be unescaped as it's not rendered as html
						vB_String::unHtmlSpecialChars($thread['htmltitle']),
					],
					$thread['languageid']
				);
				$recipientData = [
					'userid' => $thread['userid'],
					'email' => $thread['email'],
					'languageid' => $thread['languageid'],
				];
				$mailContent = [
					'toemail' => $thread['email'],
					'subject' => $maildata['subject'],
					'message' => $maildata['message'],
				];

				vB_Mail::vbmailWithUnsubscribe($recipientData, $mailContent);

			}
		}

		unset($plaintext_parser);

		// process forums
		foreach ($forums as $forum)
		{
			// Check mail opt-out. This may not actually be needed, as if they unsubscribed from all mails via the unsubscribe API, that should've
			// set 'emailnotification' to 0, which we should be filtering out in the getNewForums query that generates $forums data.
			// if ($unsubLib->isUserOptedOutOfEmail($forum['userid']))
			// {
			// 	continue;
			// }

			$langInfo = $languages[$forum['languageid']] ?? $languages[$defaultLanguage];

			$userinfo = [
				'lang_locale'       => $langInfo['locale'],
				'dstonoff'          => $forum['dstonoff'],
				'dstauto'           => $forum['dstauto'],
				'timezoneoffset'    => $forum['timezoneoffset'],
			];

			$forum['displayname_safe'] = $string->htmlspecialchars($forum['displayname']);

			$newthreadbits = '';
			$newthreads = 0;
			$updatedthreadbits = '';
			$updatedthreads = 0;

			$threads = $db->assertQuery('fetchForumThreads', [
				'forumid' =>intval($forum['forumid']),
				'lastdate' => intval ($lastdate)
			]);

			//privilege escalation.  This isn't something to do lightly, but as a backend script we really need
			//to generate the email with the permissions of the person we are sending it to
			$request->createSessionForUser($forum['userid']);

			$usercontext = vB::getUserContext($forum['userid']);
			foreach ($threads AS $thread)
			{
				if ($thread['userid'] != $forum['userid'] AND in_array($thread['userid'], $coventry))
				{
					continue;
				}

				// allow those without canviewthreads to subscribe/receive forum updates as they contain not post content
				if (
					!$usercontext->getChannelPermission('forumpermissions', 'canview', $thread['nodeid']) OR
					($thread['userid'] != $forum['userid'] AND !$usercontext->getChannelPermission('forumpermissions', 'canviewothers', $thread['nodeid']))
				)
				{
					continue;
				}

				// getNewThreads & fetchForumThreads return columns with different meanings. Pave over that for exec_digest_modify_thread.
				$thread['authorid'] = $thread['userid'];
				$thread['languageid'] = $forum['languageid'];
				//change some fields from the query to better display in the email.
				exec_digest_modify_thread($thread, $phraseApi, $langInfo, $userinfo);

				$threadlink = vB5_Route::buildUrl($thread['routeid'] . '|fullurl', ['nodeid' => $thread['nodeid']]);
				// When we start using anchors, we also need to escape links.
				//$threadlink = $string->htmlspecialchars($threadlink);

				//this apparently used to be an email phrase, but it no longer is.  The subject phrase half doesn't
				//exist.  There is no point in using the email render function for this.
				$phrase = [
					'digestthreadbit_gemailbody',
					$thread['prefix_plain'],
					$thread['htmltitle'],
					$threadlink,
					$thread['forumhtmltitle'],
					$thread['postusername'],
					$thread['lastreplydate'],
					$thread['lastreplytime']
				];
				$phrases = $phraseApi->renderPhrasesNoShortcode(['threadbit' => $phrase], $forum['languageid']);

				if ($thread['dateline'] > $lastdate)
				{
					// new thread
					$newthreads++;
					$newthreadbits .= $phrases['phrases']['threadbit'];
				}
				else
				{
					$updatedthreads++;
					$updatedthreadbits .= $phrases['phrases']['threadbit'];
				}
			}

			if (!empty($newthreads) OR !empty($updatedthreadbits))
			{
				// make email
				$forumlink = vB5_Route::buildUrl($forum['routeid'] . '|fullurl', ['nodeid' => $forum['forumid']]);
				// When we start using anchors, we also need to escape links.
				//$forumlink = $string->htmlspecialchars($forumlink);

				$unsubscribelink = vB_Library::instance('notification')->generateUrlForUnsubscribe($forum['forumid'], $forum['userid']);

				$maildata = $phraseApi->fetchEmailPhrases(
					'digestforum',
					[
						$forum['displayname_safe'],
						$forum['title_clean'],
						$newthreads,
						$updatedthreads,
						$forumlink,
						$newthreadbits,
						$updatedthreadbits,
						$bbtitle_escaped,
						$unsubscribelink,
						$settingsURL,
					],
					// subjects should not be escaped as they're not rendered as html.
					[vB_String::unHtmlSpecialChars($forum['title_clean'])],
					$forum['languageid']
				);
				// I have no idea why this email is "sendnow" and the above digestthread is NOT...
				$recipientData = [
					'userid' => $forum['userid'],
					'email' => $forum['email'],
					'languageid' => $forum['languageid'],
				];
				$mailContent = [
					'toemail' => $forum['email'],
					'subject' => $maildata['subject'],
					'message' => $maildata['message'],
				];
				$vBMailOptions = [
					'sendnow' => true,
				];
				vB_Mail::vbmailWithUnsubscribe($recipientData, $mailContent, $vBMailOptions);
			}
		}
	}
	finally
	{
		//this may not be strictly necesary because the script more or less ends at this point (and the cron stuff
		//needs to run as any user anyway), but it's more than a little tacky to drop out the function without
		//resetting the privs.  Somebody might call it later without realizing they are doing something hideously
		//insecure.
		$request->createSessionForUser($currentUserId);
	}

	vB_Mail::vbmailEnd();
}


//should be considered private to this file
function exec_digest_modify_thread(&$thread, $phraseApi, $langInfo, $userinfo)
{
	$thread['lastreplydate'] = vbdate($langInfo['dateoverride'], $thread['lastcontent'], false, true, true, false, $userinfo);
	$thread['lastreplytime'] = vbdate($langInfo['timeoverride'], $thread['lastcontent'], false, true, true, false, $userinfo);

	$thread['postusername']  = getEmailUserLabelForDigest($thread['authorname'], $thread['authorid'], $thread['languageid']);
	$thread['lastposter']  = getEmailUserLabelForDigest($thread['lastcontentauthor'], $thread['lastauthorid'], $thread['languageid']);


	if ($thread['prefixid'])
	{
		//it would be possible to batch these to some extent.  Not sure if it's worth it.
		$phrases = $phraseApi->renderPhrasesNoShortcode(array('prefix' => array("prefix_$thread[prefixid]_title_plain")), $langInfo['languageid']);
		$thread['prefix_plain']= $phrases['phrases']['prefix'];
	}
	else
	{
		$thread['prefix_plain'] = '';
	}
}

function getEmailUserLabelForDigest($authorname, $authorid, $languageid)
{
	// if this came from node.authorname, there's a possibility the username is missing because it's a guest post.
	$phrase = vB_Api::instanceInternal('phrase')->fetch('guest', $languageid);
	$userLib = vB_Library::instance('user');
	$authorinfo = [
		// Note, displayname will now be escaped in getEmailUserLabel(). Avoid double escaping since authorname is already escaped.
		'displayname' => vB_String::unHtmlSpecialChars($authorname),
		'username' => empty($authorid) ? $phrase['guest'] : $userLib->fetchUserName($authorid),
	];

	return vB_User::getEmailUserLabel($authorinfo);
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 115430 $
|| #######################################################################
\*=========================================================================*/
