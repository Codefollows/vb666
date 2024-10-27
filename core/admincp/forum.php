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

// ##################### DEFINE IMPORTANT CONSTANTS #######################
define('CVS_REVISION', '$RCSfile$ - $Revision: 116240 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
global $phrasegroups, $specialtemplates, $vbphrase, $vbulletin, $imodcache;;
$phrasegroups = ['forum', 'cpuser', 'forumdisplay', 'prefix'];
$specialtemplates = [];

// ########################## REQUIRE BACK-END ############################
require_once(dirname(__FILE__) . '/global.php');
require_once(DIR . '/includes/adminfunctions_template.php');
require_once(DIR . '/includes/adminfunctions_prefix.php');

//It's not really clear why we disable the time limit in this file.
//None of the operations seem to be sufficiently long to warrant it.
vB_Utility_Functions::setPhpTimeout(0);

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!can_administer('canadminforums'))
{
	print_cp_no_permission();
}

// ############################# LOG ACTION ###############################


$vbulletin->input->clean_array_gpc('r', [
	'moderatorid' 	=> vB_Cleaner::TYPE_UINT,
	'nodeid'		=> vB_Cleaner::TYPE_UINT
]);

$message = '';
if($vbulletin->GPC['moderatorid'] != 0)
{
	$message = 'moderator id = ' . $vbulletin->GPC['moderatorid'];
}
else if ($vbulletin->GPC['nodeid'] != 0)
{
	$message = 'node id = ' . $vbulletin->GPC['nodeid'];
}

log_admin_action($message);
unset($message);

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

$extraheaders = '
	<script type="text/javascript" src="core/clientscript/vbulletin_channel.js?v=' . SIMPLE_VERSION . '"></script>
';

print_cp_header($vbphrase['channel_manager_gforum'], '', $extraheaders);

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'modify';
}

// Legacy Hook 'channeladmin_start' Removed //

// ###################### Start add #######################
if ($_REQUEST['do'] == 'add' OR $_REQUEST['do'] == 'edit')
{
	$vbulletin->input->clean_array_gpc('r', [
		'nodeid'			=> vB_Cleaner::TYPE_UINT,
		'parentid'			=> vB_Cleaner::TYPE_UINT
	]);

	$channelApi = vB_Api::instanceInternal('Content_Channel');
	$rootChannelid = $channelApi->fetchChannelIdByGUID(vB_Channel::MAIN_CHANNEL);
	$forumChannelid = $channelApi->fetchChannelIdByGUID(vB_Channel::MAIN_FORUM);

	if ($_REQUEST['do'] == 'add')
	{
		if ($vbulletin->GPC['parentid'] == $rootChannelid)
		{
			print_stop_message2('cant_add_channel_to_root');
		}
		// Set Defaults;
		$channel = [
			'nodeid' => 0,
			'title' => '',
			'description' => '',
			'displayorder' => 1,
			'parentid' => $vbulletin->GPC['parentid'],
			'styleid' => '',
			'styleoverride' => 0,
			'cancontainthreads' => 1,
			'options' => [
				'allowbbcode' => 1,
				'allowsmilies' => 1,
				'styleoverride' => 0,
				'moderatepublish' => 0,
			],
			'category' => 0,
			'filedataid' => null,
			'topicexpiretype' => 'none',
			'topicexpireseconds' => 0,
		];

		print_form_header('admincp/forum', 'update', true);
		print_table_header($vbphrase['add_new_forum_gforum']);
	}
	else
	{
		$channel = vB_Library::instance('Content_Channel')->getContent($vbulletin->GPC['nodeid']);
		if (is_array($channel))
		{
			$channel = array_pop($channel);
		}
		if (empty($channel))
		{
			print_stop_message2('invalid_channel_specified');
		}

		print_form_header('admincp/forum', 'update', true);
		print_table_header(construct_phrase($vbphrase['x_y_id_z'], $vbphrase['channel'], vB_String::htmlSpecialCharsUni($channel['title']), $channel['nodeid']));
		construct_hidden_code('nodeid', $vbulletin->GPC['nodeid']);
	}

	$channel['title'] = str_replace('&amp;', '&', $channel['title']);
	$channel['description'] = str_replace('&amp;', '&', $channel['description'] ?? '');

	print_input_row($vbphrase['title'], 'channel[title]', $channel['title']);
	print_textarea_row($vbphrase['description_gcpglobal'], 'channel[description]', $channel['description']);

	// icon
	// This check may not be accurate as the parent may not be accurate/known (e.g. adding a new channel)
	$iconChannelId = $channel['parentid'];
	if (empty($iconChannelId))
	{
		$iconChannelId = $forumChannelid;
	}

	if (!empty($channel['nodeid']))
	{
		// If this is a channel in itself, use its own channel permissions
		$iconChannelId = $channel['nodeid'];
	}

	$userContext = vB::getUserContext();
	$canUploadChannelIcon = $userContext->getChannelPermission('forumpermissions', 'canuploadchannelicon', $iconChannelId);
	if ($canUploadChannelIcon)
	{
		print_channel_icon_upload_row(
			$vbphrase['channel_icon_admincp_label'],
			'channel_icon',
			null,
			$channel['filedataid'],
			'channel[filedataid]'
		);
	}
	else if (!empty($channel['filedataid']))
	{
		print_channel_icon_preview_row($vbphrase['channel_icon_admincp_label'], $channel['filedataid']);
	}

	print_input_row("$vbphrase[display_order]<dfn>$vbphrase[zero_equals_no_display]</dfn>", 'channel[displayorder]', $channel['displayorder']);
	if ($vbulletin->GPC['nodeid'] != -1)
	{
		$topLevelChannels = vB_Api::instance('content_channel')->fetchTopLevelChannelIds();
		if (!isset($channel['guid']) OR ($channel['guid'] != vB_Channel::MAIN_CHANNEL AND !in_array($channel['nodeid'], $topLevelChannels)))
		{
			$showParents = true;
			if(!empty($channel['nodeid']))
			{
				// Do not allow editing any "Special" sub-channel's ancestry, because doing so will break things badly.
				// ATM this is only needed to prevent moving the "Albums" channel.
				$specialDescendant = vB::getDbAssertor()->getRow('vBForum:closure',	[
					'parent' => $topLevelChannels['special'],
					'child' => $channel['nodeid'],
				]);

				$showParents = empty($specialDescendant);
			}
			if ($showParents)
			{
				print_channel_chooser($vbphrase['parent_forum'], 'channel[parentid]', $channel['parentid'], false, false, false, null, true);
			}
		}
	}
	else
	{
		construct_hidden_code('parentid', 0);
	}
	print_table_header($vbphrase['style_options']);

	if ($channel['styleid'] == 0)
	{
		$channel['styleid'] = -1; // to get the "use default style" option selected
	}
	print_style_chooser_row('channel[styleid]', $channel['styleid'], $vbphrase['use_default_style_gforum'], $vbphrase['custom_forum_style'], 1);
	print_yes_no_row($vbphrase['override_style_choice'], 'channel[options][styleoverride]', $channel['options']['styleoverride']);

	$mainChannel = vB_Library::instance('content_channel')->getMainChannel();
	if ($mainChannel['nodeid'] != $channel['nodeid'])
	{
		print_table_header($vbphrase['posting_options']);
		// For the main channel, changing "cancontainthreads" to Yes can severely break the site.
		// Disable that option for nodeid=1.
		// Edit: On second though, decided to hide the whole "posting options" block for nodeid=1 as
		// these options seem irrelevant for a category (no threads) channel, but leaving this block
		// in case we change our mind and want to be more selective.
		$extraattributes = [];
		if ($mainChannel['nodeid'] == $channel['nodeid'])
		{
			$extraattributes['title'] = $vbphrase['cannot_modify_system_channel'];
			$extraattributes['disabled'] = null;
		}

		print_yes_no_row($vbphrase['act_as_forum'], 'channel[options][cancontainthreads]', ($channel['category'] ? 0 : 1), $extraattributes);
		print_yes_no_row($vbphrase['allow_bbcode'], 'channel[options][allowbbcode]', $channel['options']['allowbbcode']);
		print_yes_no_row($vbphrase['allow_smilies'], 'channel[options][allowsmilies]', $channel['options']['allowsmilies']);
		print_yes_no_row($vbphrase['moderatepublish'], 'channel[options][moderatepublish]', $channel['options']['moderatepublish']);

		$topicexpiretypeoptions = [
			'none' => $vbphrase['topicexpire_type_none'],
			'soft' => $vbphrase['topicexpire_type_soft'],
			'hard' => $vbphrase['topicexpire_type_hard'],
		];
		print_radio_row($vbphrase['topicexpire_type_label'], 'channel[topicexpiretype]', $topicexpiretypeoptions, $channel['topicexpiretype']);
		print_input_row($vbphrase['topicexpire_time_label'], 'channel[topicexpiredays]', ($channel['topicexpireseconds'] / 86400));
	}

	print_submit_row($vbphrase['save']);
}

// ###################### Start update #######################
if ($_POST['do'] == 'update')
{
	$vbulletin->input->clean_array_gpc('p', [
		'nodeid'         => vB_Cleaner::TYPE_UINT,
		'channel'           => vB_Cleaner::TYPE_ARRAY,
	]);

	// Keeping this separate from 'channel' array for isolated cleaning & handling
	$vbulletin->input->clean_array_gpc('f', [
		'channel_icon' => vB_Cleaner::TYPE_FILE
	]);
	scanVbulletinGPCFile('channel_icon');

	$channelAPI = vB_Api::instance('Content_Channel');
	$data = [];
	$data = $vbulletin->GPC['channel'];
	// Convert days back to seconds. The reason we store seconds is in case we want to allow for more precise expiries in the future.
	// Probably not, since the cron that will take care of closing the topics won't be more accurate than a day.
	$data['topicexpireseconds'] = $data['topicexpiredays'] * 86400;
	unset ($data['topicexpiredays']);

	// Try uploading the channel icon first. If there was an error with the upload
	// (which may cause tmp_name to be empty), pass that along and let the API
	// functions map the error code to an error phrase. However ignore the error
	// if it's a NO_FILE error (that just means no icon was uploaded)
	$hasUpload = (
		!empty($vbulletin->GPC['channel_icon']['tmp_name']) OR
		!empty($vbulletin->GPC['channel_icon']['error']) AND
		$vbulletin->GPC['channel_icon']['error'] !== UPLOAD_ERR_NO_FILE
	);
	if ($hasUpload)
	{
		// If this channel already exists (i.e. this is an update), use itself for
		// channel permission lookups, otherwise try its specified parent channel
		if (!empty($vbulletin->GPC['nodeid']))
		{
			$iconChannelId = $vbulletin->GPC['nodeid'];
		}
		else if (!empty($data['parentid']))
		{
			$iconChannelId = intval($data['parentid']);
		}

		if (!empty($iconChannelId))
		{
			$vbulletin->GPC['channel_icon']['parentid'] = $iconChannelId;
			$vbulletin->GPC['channel_icon']['uploadFrom'] = 'channelicon';

			$attachAPI = vB_Api::instance('content_attach');
			$result = $attachAPI->upload($vbulletin->GPC['channel_icon']);

			if (!empty($result['errors']))
			{
				print_stop_message2($result['errors'][0]);
			}
			if (!empty($result['filedataid']))
			{
				$data['filedataid'] = $result['filedataid'];
			}
		}
	}

	if (!empty($vbulletin->GPC['nodeid']))
	{
		$channelid = $vbulletin->GPC['nodeid'];

		$response = $channelAPI->switchForumCategory(((int)$data['options']['cancontainthreads']) ? 0 : 1, $channelid);
		if (!empty($response['errors']))
		{
			print_stop_message_array($response['errors']);
		}

		$prior = vB::getDbAssertor()->getRow('vBForum:node', ['nodeid' => $channelid]);
		$response = $channelAPI->update($channelid, $data);
		if (!empty($response['errors']))
		{
			print_stop_message_array($response['errors']);
		}

		if (isset($data['parentid']) AND ($prior['parentid'] != $data['parentid']))
		{
			$response = vB_Api::instance('node')->moveNodes($channelid, $data['parentid']);
			if (!empty($response['errors']))
			{
				print_stop_message_array($response['errors']);
			}
		}
	}
	else
	{
		$data['category'] = ((int)$data['options']['cancontainthreads']) ? 0 : 1;
		// Allow IMG BB Code
		$data['options']['allowimages'] = 1;
		// Allow HTML (but control it with channel permissions instead)
		$data['options']['allowhtml'] = 1;

		// article channels require different routes & pagetemplates to be set.
		if (isset($data['parentid']))
		{
			$parentid = $data['parentid'];
			$parentFullContent = vB_Library::instance('node')->getNodeFullContent($parentid);
			$parentFullContent = $parentFullContent[$parentid];
			if(!empty($parentFullContent['channeltype']) AND ($parentFullContent['channeltype'] == 'article'))
			{
				$data['templates']['vB5_Route_Channel'] = $channelPgTemplateId;
				$data['templates']['vB5_Route_Article'] = $channelConvTemplateid;
				$data['childroute'] = 'vB5_Route_Article';
				unset($data['category']);
			}
		}

		$channelid = $channelAPI->add($data);
		if (!empty($channelid['errors']))
		{
			print_stop_message_array($channelid['errors']);
		}
	}

	$vbulletin->GPC['nodeid'] = $channelid;

	print_stop_message2(['saved_channel_x_successfully',  $vbulletin->GPC['channel']['title']], 'forum', [
		'do'=>'modify',
		'n'=> $vbulletin->GPC['nodeid'] . "#channel" . $vbulletin->GPC['nodeid']
	]);
}
// ###################### Start Remove #######################

if ($_REQUEST['do'] == 'remove')
{
	$vbulletin->input->clean_array_gpc('r', ['nodeid' => vB_Cleaner::TYPE_UINT]);

	print_delete_confirmation('vBForum:node', $vbulletin->GPC['nodeid'], 'forum', 'kill', 'channel', 0, $vbphrase['are_you_sure_you_want_to_delete_this_channel'], 'htmltitle', 'nodeid');}

// ###################### Start Kill #######################

if ($_POST['do'] == 'kill')
{
	$vbulletin->input->clean_array_gpc('p', [
		'nodeid' => vB_Cleaner::TYPE_UINT
	]);

	vB_Api::instanceInternal('content_channel')->delete($vbulletin->GPC['nodeid']);

	print_stop_message2('deleted_channel_successfully', 'forum');
}

// ###################### Start do order #######################
if ($_POST['do'] == 'doorder')
{
	$vbulletin->input->clean_array_gpc('p', ['order' => vB_Cleaner::TYPE_ARRAY]);

	if (is_array($vbulletin->GPC['order']))
	{
		$channels = vB_Api::instanceInternal('search')->getChannels(true);
		foreach ($channels as $channel)
		{
			if (!isset($vbulletin->GPC['order']["$channel[nodeid]"]))
			{
				continue;
			}

			$displayorder = intval($vbulletin->GPC['order'][$channel['nodeid']]);

			if ($channel['displayorder'] != $displayorder)
			{
				vB_Api::instanceInternal('content_channel')->update($channel['nodeid'], ['displayorder' => $displayorder]);
			}
		}
	}

	print_stop_message2('saved_display_order_successfully', 'forum', ['do'=>'modify']);
}

function get_channel_actions($vbphrase, $modcount = null)
{
	$channeloptions = [
		''        => $vbphrase['choose_action'],
		'edit'    => $vbphrase['edit_forum'],
		'view'    => $vbphrase['view_forum'],
		'remove'  => $vbphrase['delete_forum'],
		'add'     => $vbphrase['add_child_forum'],
		'addmod'  => $vbphrase['add_moderator_gforum'],
		'listmod' => $vbphrase['list_moderators'],
		'perms'   => $vbphrase['view_permissions_gforum'],
	];

	if(is_int($modcount))
	{
		if ($modcount > 0)
		{
			$channeloptions['listmod'] = $vbphrase['list_moderators'] . " ($modcount)";
		}
		else
		{
			unset($channeloptions['listmod']);
		}
	}

	return $channeloptions;
}

function print_channel_rows($vbphrase, $imodcache, $cp_collapse_forums, $channels, $expanded_parents)
{
	foreach ($channels AS $nodeid => $channel)
	{
		$expandthis = ($expanded_parents === true OR in_array($nodeid, $expanded_parents));

		$modlist = $imodcache[$nodeid] ?? [];

		$modcount = sizeof($modlist);
		$mainoptions = get_channel_actions($vbphrase, $modcount);
		$cells = [];

		//Channel column
		$collapseprefix = '';
		if ($cp_collapse_forums)
		{
			if ($expandthis OR empty($channel['channels']))
			{
				$collapseprefix = '[-] ';
			}
			else
			{
				$collapseprefix = construct_link_code2('+', get_admincp_url('forum', ['do' => 'modify', 'expandid' => $nodeid]));
			}
		}

		$spacer = str_repeat('- - ', $channel['depth']);
		$url = get_admincp_url('forum', ['do' => 'edit', 'n' => $nodeid]);
		$cells[] = $collapseprefix . $spacer . '<a id="forum' . $nodeid . '" href="' . $url . '">' . $channel['htmltitle'] . '</a> (' . $vbphrase['node_id'] . ': ' . $nodeid . ')';

		//Controls column
		$attributes = ['class' => 'js-channeljump-select', 'data-channel' => $nodeid, 'data-collapse' => $cp_collapse_forums];
		$select  = construct_select('n' . $nodeid, $mainoptions, '', $attributes);

		$cells[] = "\n\t" . $select . "\n\t";

		//Display Order colunn
		$attributes = [
			'value' => $channel['displayorder'],
			'class' => 'bginput display-order',
			'title' => $vbphrase['edit_display_order'],
		];
		$cells[] = construct_input('text', 'order[' . $nodeid . ']', $attributes);

		//Last Update
		//unfortantely the dateformat options gets updated in the legacy registry is ways
		//that skip the datastore so use the legacy.  This follows the front end formatting logic which
		//is less than perfect.
		$cells[] = vbdate($GLOBALS['vbulletin']->options['dateformat'], $channel['lastcontent']) . ', ' .
			vbdate($GLOBALS['vbulletin']->options['timeformat'], $channel['lastcontent']);


		//Moderators Column
		$mods = [
			'' => $vbphrase['moderators'] . ' (' . $modcount . ')',
		];
		foreach ($modlist AS $moderator)
		{
			// deferred until needed/requested.
			//$displayname = getAdminCPUsernameAndDisplayname($moderator['username'], $moderator['displayname'], ['nospan']);
			$displayname = $moderator['username'];
			$mods[$moderator['moderatorid']] = '&nbsp;&nbsp;&nbsp;&nbsp;' . $displayname;
		}

		if($modcount > 0)
		{
			$mods['show'] = $vbphrase['list_moderators'];
		}

		$mods['add'] = $vbphrase['add_moderator_gforum'];

		$select  = construct_select('m' . $nodeid, $mods, '', ['class' => 'js-modjump-select', 'data-channel' => $nodeid]);
		$cells[] = "\n\t" . $select . "\n\t";
		print_cells_row2($cells);

		if (!empty($channel['channels']) AND (!$cp_collapse_forums OR $expandthis))
		{
			print_channel_rows($vbphrase, $imodcache, $cp_collapse_forums, $channel['channels'], $expanded_parents);
		}
	}
}

// ###################### Start modify #######################
if ($_REQUEST['do'] == 'modify')
{
	$vbulletin->input->clean_array_gpc('r', [
		'nodeid' 	=> vB_Cleaner::TYPE_UINT,
		'expandid'	=> vB_Cleaner::TYPE_INT,
	]);

	if (!$vbulletin->GPC['expandid'])
	{
		$vbulletin->GPC['expandid'] = -1;
	}

	register_js_phrase([
		'please_select_forum',
	]);

	$channeloptions = get_channel_actions($vbphrase);
	$cp_collapse_forums = vB::getDatastore()->getOption('cp_collapse_forums');
	if ($cp_collapse_forums != 2)
	{
		$headers = [
			$vbphrase['channel'],
			$vbphrase['controls'],
			$vbphrase['display_order'],
			$vbphrase['last_updated'],
			$vbphrase['moderators']
		];
		$headercount = count($headers);

		print_form_header2('admincp/forum', 'doorder');
		print_table_start2();

		print_table_header($vbphrase['channel_manager_gforum'], $headercount);
		print_description_row($vbphrase['if_you_change_display_order'], 0, $headercount);
		fetch_row_bgclass(); // restart alternating row classes

		//this is primarily for print_channel_rows below but it sets a magic global $imodcache so
		//changing it's position could affect things unexpectedly.
		$modcache = cache_moderators();

		if($vbulletin->GPC['expandid'] == -2)
		{
			$expanded_parents = true;
		}
		else
		{
			$expanded_parents = [];
			if (!empty($vbulletin->GPC['expandid']))
			{
				$expanded_parents = vB::getDbAssertor()->getColumn('vBForum:closure', 'parent', ['child' => $vbulletin->GPC['expandid']], 'depth');
			}
		}

		print_cells_row2($headers, 'thead');

		// Hide protected channels. A bug in search API was already hiding them, and per VBV-14764 we'll continue to hide them unless
		// customer feedback overwhelmingly indicate they want to be able to edit the special/protected channels.
		$channels = vB_Api::instanceInternal('search')->getChannels(false, ['include_protected' => 0]);
		print_channel_rows($vbphrase, $modcache, $cp_collapse_forums, $channels, $expanded_parents);

		$buttons = [
			['submit', $vbphrase['save_display_order']],
			construct_link_button($vbphrase['add_new_forum_gforum'] , get_admincp_url('forum', ['do' => 'add'])),
		];
		print_table_button_footer($buttons, $headercount);

		if ($cp_collapse_forums)
		{
			echo '<p class="smallfont" align="center">' . construct_link_code2($vbphrase['expand_all'], get_admincp_url('forum', ['do' => 'modify', 'expandid' => -2])) . '</p>';
		}
	}
	else
	{
		$headers = [
			$vbphrase['channel'],
			$vbphrase['controls'],
		];
		$headercount = count($headers);

		print_form_header2('admincp/forum', '');
		print_table_start2();

		print_table_header($vbphrase['channel_manager_gforum'], $headercount);

		print_cells_row2([$vbphrase['channel'], $vbphrase['controls']], 'thead');
		$cells = [];

		//the construct_select isn't compatible with construct_channel_chooser, should figure out how to bring this together.
		$attributes = construct_control_attributes('nodeid', ['id' => 'sel_foruid']);
		$cells[] = '<select ' . $attributes . '>' . construct_channel_chooser($vbulletin->GPC['nodeid'], true) . '</select>';

		$attributes = ['class' => 'js-channeljump-select', 'data-collapse' => $vbulletin->options['cp_collapse_forums']];
		$select  = construct_select('controls', $channeloptions, '', $attributes);

		$cells[] = "\n\t" . $select . "\n\t";

		print_cells_row2($cells);

		$buttons = [
			construct_link_button($vbphrase['add_new_forum_gforum'] , get_admincp_url('forum', ['do' => 'add'])),
		];
		print_table_button_footer($buttons, $headercount);
	}
}

// ###################### Start update #######################
if ($_REQUEST['do'] == 'view')
{
	$vbulletin->input->clean_array_gpc('r', [
		'nodeid' => vB_Cleaner::TYPE_UINT,
	]);
	$channel = vB_Api::instanceInternal('node')->getNode($vbulletin->GPC['nodeid']);
	if (empty($channel))
	{
		print_stop_message2('invalid_channel_specified');
	}

	$path = vB_Api::instanceInternal('route')->getUrl($channel['routeid'], [], []);
	print_cp_redirect($path);
}

print_cp_footer();

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 116240 $
|| #######################################################################
\*=========================================================================*/
