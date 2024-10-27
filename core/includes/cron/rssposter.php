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

// ######################## SET PHP ENVIRONMENT ###########################
error_reporting(error_reporting() & ~E_NOTICE);

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

require_once(DIR . '/includes/class_rss_poster.php');
require_once(DIR . '/includes/functions_wysiwyg.php');

vB_Utilities::extendMemoryLimit();
vB_Utility_Functions::setPhpTimeout(0);

// #############################################################################
// slurp all enabled feeds from the database
$datastore = vB::getDatastore();
$assertor = vB::getDbAssertor();

$bf_misc_feedoptions = $datastore->getValue('bf_misc_feedoptions');
$feeds_result = $assertor->assertQuery('fetchFeeds', ['bf_misc_feedoptions_enabled' => $bf_misc_feedoptions['enabled']]);
foreach ($feeds_result as $feed)
{
	// only process feeds that are due to be run (lastrun + TTL earlier than now)
	if ($feed['lastrun'] < vB::getRequest()->getTimeNow() - $feed['ttl'])
	{
		// counter for maxresults
		$feed['counter'] = 0;

		// add to $feeds slurp array
		$feeds["$feed[rssfeedid]"] = $feed;
	}
}

// #############################################################################
// extract items from feeds

$vbphrase = vB_Api::instanceInternal('phrase')->fetch(['x_unable_to_open_url', 'x_xml_error_y_at_line_z', 'rss_feed_manager', 'thread', 'topic']);

$log_items = '';
if (!empty($feeds))
{
	// array of items to be potentially inserted into the database
	$items = [];

	// array to store rss item logs sql
	$rsslog_insert_sql = [];

	// array to store list of inserted items
	$cronlog_items = [];

	$feedcount = 0;
	$itemstemp = [];
	foreach (array_keys($feeds) AS $rssfeedid)
	{
		$feed =& $feeds["$rssfeedid"];

		$feed['xml'] = new vB_RSS_Poster();
		$feed['xml']->fetch_xml($feed['url']);
		if (empty($feed['xml']->xml_string))
		{
			if (defined('IN_CONTROL_PANEL'))
			{
				echo construct_phrase($vbphrase['x_unable_to_open_url'], $feed['title']);
			}
			continue;
		}
		else if ($feed['xml']->parse_xml() === false)
		{
			if (defined('IN_CONTROL_PANEL'))
			{
				echo construct_phrase($vbphrase['x_xml_error_y_at_line_z'], $feed['title'], ($feed['xml']->feedtype == 'unknown' ? 'Unknown Feed Type' : $feed['xml']->xml_object->error_string()), $feed['xml']->xml_object->error_line());
			}
			continue;
		}

		// prepare search terms if there are any
		$feed['searchterms'] = [];
		if ($feed['searchwords'] !== '')
		{
			$feed['searchwords'] = preg_quote($feed['searchwords'], '#');
			$matches = [];

			// find quoted terms or single words
			if (preg_match_all('#(?:"(?P<phrase>.*?)"|(?P<word>[^ \r\n\t]+))#', $feed['searchwords'], $matches, PREG_SET_ORDER))
			{
				foreach ($matches AS $match)
				{
					$searchword = empty($match['phrase']) ? $match['word'] : $match['phrase'];

					// Ensure empty quotes were not used
					if (!($searchword))
					{
						continue;
					}

					// exact word match required
					if (substr($searchword, 0, 2) == '\\{' AND substr($searchword, -2, 2) == '\\}')
					{
						// don't match words nested in other words - the patterns here match targets that are not surrounded by ascii alphanums below 0128 \x7F
						$feed['searchterms']["$searchword"] = '#(?<=[\x00-\x40\x5b-\x60\x7b-\x7f]|^)' . substr($searchword, 2, -2) . '(?=[\x00-\x40\x5b-\x60\x7b-\x7f]|$)#si';
					}
					// string fragment match required
					else
					{
						$feed['searchterms']["$searchword"] = "#$searchword#si";
					}
				}
			}
		}

		foreach ($feed['xml']->fetch_items() AS $item)
		{
			// attach the rssfeedid to each item
			$item['rssfeedid'] = $rssfeedid;

			if (!empty($item['summary']))
			{
				// ATOM
				$description = get_item_value($item['summary']);
			}
			elseif (!empty($item['content:encoded']))
			{
				$description = get_item_value($item['content:encoded']);
			}
			elseif (!empty($item['content']))
			{
				$description = get_item_value($item['content']);
			}
			else
			{
				$description = get_item_value($item['description']);
			}

			// backward compatability to RSS
			if (!isset($item['description']))
			{
				$item['description'] = $description;
			}
			if (!isset($item['guid']) AND isset($item['id']))
			{
				$item['guid'] =& $item['id'];
			}
			if (!isset($item['pubDate']))
			{
				if (isset($item['published']))
				{
					$item['pubDate'] =& $item['published'];
				}
				else if (isset($item['updated']))
				{
					$item['pubDate'] =& $item['updated'];
				}
			}

			switch($feed['xml']->feedtype)
			{
				case 'atom':
				{
					// attach a content hash to each item
					$itemtitle = (!empty($item['title']['value']) ? $item['title']['value'] : $item['title']);
					$item['contenthash'] = md5($itemtitle . $description . $item['link']['href']);
					unset($itemtitle);
					break;
				}
				case 'rss':
				default:
				{
					// attach a content hash to each item
					$item['contenthash'] = md5($item['title'] . $description . $item['link']);
				}
			}

			// generate unique id for each item
			if (is_array($item['guid']) AND !empty($item['guid']['value']))
			{
				$uniquehash = md5($item['guid']['value']);
			}
			else if (!is_array($item['guid']) AND !empty($item['guid']))
			{
				$uniquehash = md5($item['guid']);
			}
			else
			{
				$uniquehash = $item['contenthash'];
			}

			// check to see if there are search words defined for this feed
			if (!empty($feed['searchterms']))
			{
				$matched = false;

				foreach ($feed['searchterms'] AS $searchword => $searchterm)
				{
					// (search title only                     ) OR (search description if option is set..)
					if (preg_match($searchterm, $item['title']) OR ($feed['rssoptions'] & $bf_misc_feedoptions['searchboth'] AND preg_match($searchterm, $description)))
					{
						$matched = true;

						if (!($feed['rssoptions'] & $bf_misc_feedoptions['matchall']))
						{
							break;
						}
					}
					else if ($feed['rssoptions'] & $bf_misc_feedoptions['matchall'])
					{
						$matched = false;
						break;
					}
				}

				// add matched item to the potential insert array
				if ($matched AND ($feed['maxresults'] == 0 OR $feed['counter'] < $feed['maxresults']))
				{
					$feed['counter']++;
					$items["$uniquehash"] = $item;
					$itemstemp["$uniquehash"] = $uniquehash;
				}
			}
			// no search terms, insert item regardless
			else
			{
				// add item to the potential insert array
				if ($feed['maxresults'] == 0 OR $feed['counter'] < $feed['maxresults'])
				{
					$feed['counter']++;
					$items["$uniquehash"] = $item;
					$itemstemp["$uniquehash"] = $uniquehash;
				}
			}

			if (++$feedcount % 10 == 0 AND !empty($itemstemp))
			{
				$rsslogs_result = $assertor->assertQuery('vBForum:rsslog', ['uniquehash' => $itemstemp]);
				foreach ($rsslogs_result as $rsslog)
				{
					// remove any items which have this unique id from the list of potential inserts.
					unset($items["$rsslog[uniquehash]"]);
				}
				$itemstemp = [];
			}

		}
	}

	if (!empty($itemstemp))
	{
		// query rss log table to find items that are already inserted
		$rsslogs_result = $assertor->assertQuery('vBForum:rsslog', ['uniquehash' => $itemstemp]);

		foreach ($rsslogs_result as $rsslog)
		{
			// remove any items with this unique id from the list of potential inserts
			unset($items["$rsslog[uniquehash]"]);
		}
	}

	if (!empty($items))
	{
		$datastore->setOption('postminchars', 1, false);
		$error_type = (defined('IN_CONTROL_PANEL') ? vB_DataManager_Constants::ERRTYPE_CP : vB_DataManager_Constants::ERRTYPE_SILENT);
		$rss_logs_inserted = false;

		if (defined('IN_CONTROL_PANEL'))
		{
			echo "<ol>";
		}

		try
		{
			$currentUserId = vB::getCurrentSession()->get('userid');
			$request = vB::getRequest();

			//privilege escalation.  This isn't something to do lightly, but we want to create nodes as a particular
			//user and the library still checks a bunch of permissions based on the current user.
			$request->createSessionForUser($feed['userid']);


			// process the remaining list of items to be inserted
			foreach ($items AS $uniquehash => $item)
			{
				$feed =& $feeds["$item[rssfeedid]"];
				$feed['rssoptions'] = intval($feed['rssoptions']);

				$convertHtmlToBbcode = false;
				$nl2br = false;
				if ($feed['rssoptions'] & $bf_misc_feedoptions['html2bbcode'])
				{
					$feed['rssoptions'] = $feed['rssoptions'] & ~$bf_misc_feedoptions['allowhtml'];
					$convertHtmlToBbcode = true;
					$nl2br = true;
				}

				$bbcodeApi = vB_Api::instanceInternal('bbcode');
				switch ($feed['itemtype'])
				{
					case 'announcement':
					{
						// do nothing, announcement have been removed.
						break;
					}

					// insert item as thread
					case 'topic':
					default:
					{
						$pagetext = $feed['xml']->parse_template($feed['bodytemplate'], $item);
						$itemtitle = strip_bbcode(convert_wysiwyg_html_to_bbcode($feed['xml']->parse_template($feed['titletemplate'], $item)));
						if (empty($itemtitle))
						{
							$itemtitle = vB_Phrase::fetchSinglePhrase('rssposter_post_from_x', [$feed['title']]);
						}

						$itemAddResult = vB_Library::instance('content_text')->add(
							[
								'userid'=> $feed['userid'],
								'sticky'=> ($feed['rssoptions'] & $bf_misc_feedoptions['stickthread'] ? 1 : 0),
								'parentid' => $feed['nodeid'],
								'title' => $itemtitle,
								'rawtext' => $pagetext,
								'approved' => ($feed['rssoptions'] & $bf_misc_feedoptions['moderatethread'] ? 0 : 1),
								'showapproved' => ($feed['rssoptions'] & $bf_misc_feedoptions['moderatethread'] ? 0 : 1),
								'iconid' => (!empty($feed['iconid']) ? $feed['iconid'] : 0)
							],
							[
								'autoparselinks' => 1,
								'nl2br' => $nl2br,
								'skipDupCheck' => 1,
								'skipFloodCheck' => true,
							],
							$convertHtmlToBbcode
						);

						$itemid = !empty($itemAddResult['nodeid']) ? $itemAddResult['nodeid'] : false;

						$threadactiontime = (($feed['topicactiondelay'] > 0) ? (vB::getRequest()->getTimeNow() + $feed['topicactiondelay']  * 3600) : 0);

						if ($itemid)
						{
							$itemtype = 'topic';
							$itemlink = vB_Api::instanceInternal('route')->getAbsoluteNodeUrl($itemid);
							if (defined('IN_CONTROL_PANEL'))
							{
								echo "<li><a href=\"$itemlink\" target=\"feed\">$itemtitle</a></li>";
							}

							$rsslog_insert_sql[] = [
								'rssfeedid' => $item['rssfeedid'],
								'itemid' => $itemid,
								'itemtype' => $itemtype,
								'uniquehash' => $assertor->escape_string($uniquehash),
								'contenthash' => $assertor->escape_string($item['contenthash']),
								'dateline' => vB::getRequest()->getTimeNow(),
								'topicactiontime' => $threadactiontime
							];
							$cronlog_items[$item['rssfeedid']][] = "\t<li>$vbphrase[$itemtype] <a href=\"$itemlink\" target=\"logview\"><em>$itemtitle</em></a></li>";
						}
						break;
					}
				}

				if (!empty($rsslog_insert_sql))
				{
					// insert logs
					$assertor->assertQuery('replaceValues', ['table' => 'rsslog', 'values' => $rsslog_insert_sql]);
					$rsslog_insert_sql = [];
					$rss_logs_inserted = true;
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

		if (defined('IN_CONTROL_PANEL'))
		{
			echo "</ol>";
		}

		if ($rss_logs_inserted)
		{
			// build cron log
			$log_items = '<ul class="smallfont">';
			foreach ($cronlog_items AS $rssfeedid => $items)
			{
				$log_items .= "<li><strong>" . $feeds[$rssfeedid]['title'] . "</strong><ul class=\"smallfont\">\r\n";
				foreach ($items AS $item)
				{
					$log_items .= $item;
				}
				$log_items .= "</ul></li>\r\n";
			}
			$log_items .= '</ul>';
		}

		if (!empty($feeds))
		{
			// update lastrun time for feeds
			$assertor->update('vBForum:rssfeed', ['lastrun' => vB::getRequest()->getTimeNow()], ['rssfeedid' => array_keys($feeds)]);
		}
	}
}

// #############################################################################
// check for threads that need time-delay actions
$threads_result = $assertor->assertQuery('fetchRSSFeeds', ['TIMENOW' => vB::getRequest()->getTimeNow()]);
$threads = [];
foreach ($threads_result as $thread)
{
	if ($thread['options'] & $bf_misc_feedoptions['unstickthread'])
	{
		vB_Api::instanceInternal('node')->setSticky([$thread['nodeid']], false);
	}

	if ($thread['options'] & $bf_misc_feedoptions['closethread'])
	{
		vB_Api::instanceInternal('node')->closeNode($thread['nodeid']);
	}

	$threads[] = $thread['itemid'];
}

// don't work with those items again
if (!empty($threads))
{
	$assertor->update('vBForum:rsslog', ['topicactioncomplete' => 1], ['itemid' => $threads, 'itemtype' => 'topic']);
}

// #############################################################################
// all done

if (defined('IN_CONTROL_PANEL'))
{
	echo '<p><a href="admincp/rssposter.php">' . $vbphrase['rss_feed_manager'] . '</a></p>';
}

if ($log_items)
{
	log_cron_action($log_items, $nextitem, 1);
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 114274 $
|| #######################################################################
\*=========================================================================*/
