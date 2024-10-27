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

function build_bbcode_video($checktable = false)
{
	$db = vB::getDbAssertor();

	if ($checktable)
	{
		try
		{
			$db->assertQuery('bbcode_video', []);
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/*
		NOTE: having the 2nd param true for the loadProductXmlListParsed() call currently means you could potentially
		*override* (rather than add to) the default vbulletin's bbcode_video list by having a file named
		'bbcode_video_vbulletin.xml' (because the 'vbulletin' would be used as the key, overriding the default vbulletin
		data that also uses 'vbulletin' as the key).
		This may be a good thing or a bad thing. For now let's go with "add" instead of "override"
		$xmlData = vB_Library::instance('product')->loadProductXmlListParsed('bbcode_video', true);

		NOTE2: The "tagoption" attribute is used as the key (it's unique in DB and required by how bbcode parsing works), so
		providers from different packages sharing this key will resolve conflicts by a "priority" element.
		If this element is not provided for a provider node in the XML file, it'll default to "0". See VBV-9692
		We still do not want to accidentally override packages until we do the filtering via priority, so continue passing false for 2nd param.
	 */
	$xmlData = vB_Library::instance('product')->loadProductXmlListParsed('bbcode_video', false); // 2nd param is optional, default false, but explicitly specified here intentionally.

	$insert = [];
	$priority = [];
	$failed = [];
	foreach ($xmlData AS $data)
	{
		if (is_array($data['provider']))
		{
			$provider = $data['provider'];
			if (isset($provider['tagoption']) OR isset($provider['title']) OR isset($provider['url']))
			{
				/*
					It seems that if the XML file contains only 1 provider tag, we don't get a nested array for $data['provider'].
					Force consistency.
				 */
				$provider = [$provider];
			}

			foreach ($provider AS $provider)
			{
				$doInsert = false;
				$tagoption = $provider['tagoption'];
				$items = [];
				$items['tagoption'] = $tagoption;
				$items['provider'] = $provider['title'];
				$items['url'] = $provider['url'];
				$items['regex_url'] = $provider['regex_url'];
				$items['regex_scrape'] = $provider['regex_scrape'];
				$items['embed'] = $provider['embed'];

				// default to 0 if this element's not set.
				if (!isset($provider['priority']))
				{
					$provider['priority'] = 0;
				}

				if (isset($priority[$tagoption]))
				{

					if ($priority[$tagoption] < $provider['priority'])
					{
						$doInsert = true;
						$failed[] = $insert[$tagoption]; // save the overwritten one in failed array.
					}
				}
				else
				{
					$doInsert = true;
				}


				// bbcode_video table currently has tagoption as a unique key.
				if ($doInsert)
				{
					$priority[$tagoption] = $provider['priority'];
					$insert[$tagoption] = $items;
				}
				else
				{
					// todo: report these back to caller.
					$failed[] = $items;
				}
			}
		}
	}


	if (!empty($insert))
	{
		// TODO: wrap below 2 in a transaction if possible (need to change truncate to DELETE instead as I think truncate forces a commit)
		// in an attempt to avoid a case where a bad addon causes default bbcode_video to go away and we cannot recover.
		$db->assertQuery('truncateTable', ['table' => 'bbcode_video']);
		$insertResult = $db->assertQuery('bbcode_video', [
			vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_MULTIPLEINSERT,
			vB_dB_Query::FIELDS_KEY => ['tagoption', 'provider', 'url', 'regex_url', 'regex_scrape', 'embed'],
			vB_dB_Query::VALUES_KEY => $insert
		]);
	}

	$firsttag = '<vb:if condition="$provider == \'%1$s\'">';
	$secondtag = '<vb:elseif condition="$provider == \'%1$s\'" />';

	$template = [];
	$bbcodes = $db->assertQuery('bbcode_video', [],	['field' => ['priority'], 'direction' => [vB_dB_Query::SORT_ASC]]);
	foreach ($bbcodes as $bbcode)
	{
		if (empty($template))
		{
			$template[] = sprintf($firsttag, $bbcode['tagoption']);
		}
		else
		{
			$template[] = sprintf($secondtag, $bbcode['tagoption']);
		}
		$template[] = $bbcode['embed'];
	}
	$template[] = "</vb:if>";

	$final = implode("\r\n", $template);

	$exists = $db->getRow('template', [
		'title' => 'bbcode_video',
		'product' => ['', 'vbulletin'],
		'styleid' => -1,
	]);

	if ($exists)
	{
		try
		{
			vB_Api::instanceInternal('template')->update($exists['templateid'], 'bbcode_video', $final, 'vbulletin', false, false, '');
		}
		catch (Exception $e)
		{
			return false;
		}
	}
	else
	{
		vB_Api::instanceInternal('template')->insert(-1, 'bbcode_video', $final, 'vbulletin');
	}
	return true;
}

// ###################### Start build_userlist #######################
// This forces the cache for X list to be rebuilt, only generally needed for modifications.
function build_userlist($userid, $lists = array())
{
	$userid = intval($userid);
	if ($userid == 0)
	{
		return false;
	}

	if (empty($lists))
	{
		$userlists = vB::getDbAssertor()->assertQuery('vBForum:fetchuserlists', array(
			vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_STORED,
			'userid' => $userid,
		));

		foreach ($userlists as $userlist)
		{
			$lists["$userlist[type]"][] = $userlist['userid'];
		}
	}

	$userdata = new vB_Datamanager_User(vB_DataManager_Constants::ERRTYPE_STANDARD);
	$existing = array('userid' => $userid);
	$userdata->set_existing($existing);

	foreach ($lists AS $listtype => $values)
	{
		$key = $listtype . 'list';
		if (isset($userdata->validfields["$key"]))
		{
			$userdata->set($key, implode(',', $values));
		}
	}

	/* Now to set the ones that weren't set. */
	foreach ($userdata->list_types AS $listtype)
	{
		$key = $listtype . 'list';
		if ($userdata->is_field_set($key))
		{
			$userdata->set($key, '');
		}
	}

	$userdata->save();

	return true;
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 116465 $
|| #######################################################################
\*=========================================================================*/
