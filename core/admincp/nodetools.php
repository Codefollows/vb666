<?php
/*======================================================================*\
|| #################################################################### ||
|| # vBulletin 6.0.6 - Licence Number LN05842122
|| # ---------------------------------------------------------------- # ||
|| # Copyright ©2000-2024 MH Sub I, LLC dba vBulletin. All Rights Reserved. ||
|| # This file may not be redistributed in whole or significant part. # ||
|| # ---------------- VBULLETIN IS NOT FREE SOFTWARE ---------------- # ||
|| #        www.vbulletin.com | www.vbulletin.com/license.html        # ||
|| #################################################################### ||
\*======================================================================*/

// ######################## SET PHP ENVIRONMENT ###########################

// ##################### DEFINE IMPORTANT CONSTANTS #######################
define('CVS_REVISION', '$RCSfile$ - $Revision: 116491 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
global $phrasegroups, $specialtemplates, $vbphrase, $vbulletin;

$phrasegroups = ['thread', 'threadmanage', 'prefix'];
$specialtemplates = [];

// ########################## REQUIRE BACK-END ############################
require_once(dirname(__FILE__) . '/global.php');
require_once(DIR . '/includes/functions_databuild.php');
require_once(DIR . '/includes/adminfunctions_prefix.php');

vB_Utility_Functions::setPhpTimeout(0);

// track which permissions are needed for which action to avoid too many duplicate checks
// in the action blocks.
$permissions = [
	'prune' => ['canadminthreads'],
	'move' => ['canadminthreads'],
	'close' => ['canadminthreads'],
	'open' => ['canadminthreads'],
	'approve' => ['canadminthreads'],
	'unapprove' => ['canadminthreads'],
	'feature' => ['canadminthreads'],
	'unfeature' => ['canadminthreads'],
	'pruneuser' => ['canadminthreads'],
	'prunepm' => ['canadminpm'],

	// These are used for both pms and regular nodes.  Need to check specific perms downstream but
	// should weed out users that don't have either here at the top.
	'donodes' => ['canadminthreads', 'canadminpm'],
	'donodessel' => ['canadminthreads', 'canadminpm'],
	'donodesall' => ['canadminthreads', 'canadminpm'],
	'donodesselfinish' => ['canadminthreads', 'canadminpm'],
];

// ######################## CHECK ADMIN PERMISSIONS #######################
// if we don't recognize the action, then just give a no permission error.
if (!isset($permissions[$_REQUEST['do']]) OR !vB::getUserContext()->hasAnyAdminPermission($permissions[$_REQUEST['do']]))
{
	print_cp_no_permission();
}

$vbulletin->input->clean_array_gpc('r', [
	'channelid' => vB_Cleaner::TYPE_INT,
	'pollid'  => vB_Cleaner::TYPE_INT,
]);

// ############################# LOG ACTION ###############################

$log = '';
if (!empty($vbulletin->GPC['channelid']))
{
	$log = "channel id = " . $vbulletin->GPC['channelid'];
}
else if (!empty($vbulletin->GPC['pollid']))
{
	$log = "poll id = " . $vbulletin->GPC['pollid'];
}
log_admin_action($log);

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################


// ###################### Start Prune #######################
if ($_REQUEST['do'] == 'prune')
{
	print_cp_header($vbphrase['topic_manager_admincp']);

	print_form_header2('admincp/nodetools', 'donodes');
	print_table_start2();

	print_table_header($vbphrase['prune_topics_manager']);
	print_description_row($vbphrase['pruning_many_threads_is_a_server_intensive_process']);

	construct_hidden_code('type', 'prune');
	print_node_filter_rows($vbphrase);
	print_table_default_footer($vbphrase['prune_topics']);

	print_form_header2('admincp/nodetools', 'pruneuser');
	print_table_start2();
	print_table_header($vbphrase['prune_by_username']);
	print_input_row($vbphrase['username'], 'username');
	print_move_prune_channel_chooser($vbphrase['channel'], 'channelid', $vbphrase['all_channels']);

	$buttons = [
		'topics' => $vbphrase['topics'],
		'posts' => $vbphrase['posts'],
		'either' => $vbphrase['either'],
	];
	print_radio_row($vbphrase['select'], 'topicsposts', $buttons, 'either', 'normal', false, true);

	print_yes_no_row($vbphrase['include_child_channels'], 'subforums');
	print_table_default_footer($vbphrase['prune']);
	print_cp_footer();
}


if ($_REQUEST['do'] == 'prunepm')
{
	$filter = [
		'channelid'=> vB_Api::instanceInternal('node')->fetchPMChannel(),
		'subforums' => 0,
		'prefixid' => -1,
	];
	print_standard_node_action_form($vbphrase, 'prunepm', $filter,  ['status'], 'pruning_many_pms_is_a_server_intensive_process');
}


if ($_REQUEST['do'] == 'move')
{
	print_cp_header($vbphrase['topic_manager_admincp']);

	print_form_header2('admincp/nodetools', 'donodes');
	print_table_start2();

	print_table_header($vbphrase['move_topics']);

	construct_hidden_code('type', 'move');
	print_move_prune_channel_chooser($vbphrase['destination_channel'], 'destchannelid', '');
	print_node_filter_rows($vbphrase);
	print_table_default_footer($vbphrase['move_topics']);
	print_cp_footer();
}

if ($_REQUEST['do'] == 'close')
{
	print_standard_node_action_form($vbphrase, 'close', ['isopen' => 1], [], '');
}

if ($_REQUEST['do'] == 'open')
{
	print_standard_node_action_form($vbphrase, 'open', ['isopen' => 0], [], '');
}

if ($_REQUEST['do'] == 'approve')
{
	//We use the "moderated" nomenclature, probably for historical reasons, in node tools
	//which is the opposite of "approved".  We should prehaps change that.
	print_standard_node_action_form($vbphrase, 'approve', ['moderated' => 1], [], '');
}

if ($_REQUEST['do'] == 'unapprove')
{
	//We use the "moderated" nomenclature, probably for historical reasons, in node tools
	//which is the opposite of "approved".  We should prehaps change that.
	print_standard_node_action_form($vbphrase, 'unapprove', ['moderated' => 0], [], '');
}

if ($_REQUEST['do'] == 'feature')
{
	print_standard_node_action_form($vbphrase, 'feature', ['featured' => 0], [], '');
}

if ($_REQUEST['do'] == 'unfeature')
{
	print_standard_node_action_form($vbphrase, 'unfeature', ['featured' => 1], [], '');
}

// ###################### Start Prune by user #######################
if ($_REQUEST['do'] == 'pruneuser')
{
	$vbulletin->input->clean_array_gpc('r', [
		'username' => vB_Cleaner::TYPE_NOHTML,
		'channelid' => vB_Cleaner::TYPE_INT,
		'subforums' => vB_Cleaner::TYPE_BOOL,
		'userid' => vB_Cleaner::TYPE_UINT,
		'topicsposts' => vB_Cleaner::TYPE_NOHTML,
	]);

	// we only ever submit this via post
	$vbulletin->input->clean_array_gpc('p', [
		'confirm'   => vB_Cleaner::TYPE_BOOL,
	]);

	print_cp_header($vbphrase['topic_manager_admincp']);
	$assertor = vB::getDbAssertor();
	$nodeApi = vB_Api::instance('node');

	if (empty($vbulletin->GPC['username']) AND !$vbulletin->GPC['userid'])
	{
		print_stop_message('invalid_user_specified');
	}
	else if (!$vbulletin->GPC['channelid'])
	{
		print_stop_message('invalid_channel_specified');
	}

	if ($vbulletin->GPC['channelid'] == -1)
	{
		$forumtitle = $vbphrase['all_forums'];
	}
	else
	{
		$channel = $nodeApi->getNode($vbulletin->GPC['channelid']);
		$forumtitle = $channel['title'] . ($vbulletin->GPC['subforums'] ? ' (' . $vbphrase['include_child_channels'] . ')' : '');
	}

	$conditions = [];
	if ($vbulletin->GPC['username'])
	{
		$conditions[] = ['field' => 'username', 'value' => $vbulletin->GPC['username'], 'operator' => vB_dB_Query::OPERATOR_INCLUDES];
	}
	else
	{
		$conditions['userid'] = $vbulletin->GPC['userid'];
	}

	$result = $assertor->select('user', $conditions, 'username', ['userid', 'username']);

	if (!$result->valid())
	{
		print_stop_message('invalid_user_specified');
	}
	else
	{
		echo '<p>' . construct_phrase($vbphrase['about_to_delete_posts_in_forum_x_by_users'], $forumtitle) . '</p>';

		$filter = [
			'channelid' => $vbulletin->GPC['channelid'],
			'subforums' =>  $vbulletin->GPC['subforums'],
		];

		foreach ($result AS $user)
		{
			$filter['userid'] = $user['userid'];

			$params = fetch_thread_move_prune_sql($assertor, $filter);
			$params['special']['topicsposts'] = $vbulletin->GPC['topicsposts'];
			$hiddenParams = sign_client_string(serialize($params));

			print_form_header2('admincp/nodetools', 'donodesall');
			print_table_start2();
			print_table_header(construct_phrase($vbphrase['prune_all_x_posts_automatically'], $user['username']), 2, 0);
			construct_hidden_code('type', 'prune');
			construct_hidden_code('criteria', $hiddenParams);
			print_table_default_footer(construct_phrase($vbphrase['prune_all_x_posts_automatically'], $user['username']));

			print_form_header2('admincp/nodetools', 'donodessel');
			print_table_start2();
			print_table_header(construct_phrase($vbphrase['prune_x_posts_selectively'], $user['username']), 2, 0);
			construct_hidden_code('type', 'prune');
			construct_hidden_code('criteria', $hiddenParams);
			print_table_default_footer(construct_phrase($vbphrase['prune_x_posts_selectively'], $user['username']));
		}
	}

	print_cp_footer();
}

// ###################### Start thread move/prune by options #######################
if ($_POST['do'] == 'donodes')
{
	$vbulletin->input->clean_array_gpc('p', [
		'type'        => vB_Cleaner::TYPE_NOHTML,
		'topic'      => vB_Cleaner::TYPE_ARRAY,
		'destchannelid' => vB_Cleaner::TYPE_INT,
	]);

	print_cp_header($vbphrase['topic_manager_admincp']);

	$topic = $vbulletin->GPC['topic'];
	$type = $vbulletin->GPC['type'];
	check_type_permission($type);

	$destchannelid = $vbulletin->GPC['destchannelid'];

	if ($topic['channelid'] == 0)
	{
		print_stop_message('please_complete_required_fields');
	}

	if ($type == 'move')
	{
		$channel = vB_Api::instance('content_channel')->getContent($destchannelid);
		print_stop_message_on_api_error($channel);

		$channel = $channel[$destchannelid];
		if ($channel['category'])
		{
			print_stop_message('destination_channel_cant_contain_topics');
		}
	}

	$assertor = vB::getDbAssertor();

	$params = fetch_thread_move_prune_sql($assertor, $vbulletin->GPC['topic']);
	$hiddenParams = sign_client_string(serialize($params));

	$count = $assertor->getRow('vBForum:getNodeToolsTopicsCount', $params);
	$count = $count['count'];

	if (!$count)
	{
		print_stop_message('no_topics_matched_your_query');
	}

	$typephrases = get_action_phrases($type);

	print_form_header2('admincp/nodetools', 'donodesall');
	print_table_start2();
	construct_hidden_code('type', $type);
	construct_hidden_code('criteria', $hiddenParams);

	print_table_header(construct_phrase($vbphrase['x_topic_matches_found'], $count));
	if ($type == 'move')
	{
		construct_hidden_code('destchannelid', $destchannelid);
	}

	print_table_default_footer($vbphrase[$typephrases['action_all_topics']]);

	print_form_header('admincp\nodetools', 'donodessel');
	construct_hidden_code('type', $type);
	construct_hidden_code('criteria', $hiddenParams);
	print_table_header(construct_phrase($vbphrase['x_topic_matches_found'], $count));
	if ($type == 'move')
	{
		construct_hidden_code('destchannelid', $destchannelid);
	}

	print_table_default_footer($vbphrase[$typephrases['action_topics_selectively']]);
	print_cp_footer();
}

// ###################### Start move/prune all matching #######################
if ($_POST['do'] == 'donodesall')
{
	require_once(DIR . '/includes/functions_log_error.php');

	$vbulletin->input->clean_array_gpc('p', [
		'type'        => vB_Cleaner::TYPE_NOHTML,
		'criteria'    => vB_Cleaner::TYPE_STR,
		'destchannelid' => vB_Cleaner::TYPE_INT,
	]);

	$type = $vbulletin->GPC['type'];
	check_type_permission($type);

	$assertor = vB::getDbAssertor();

	print_cp_header($vbphrase['topic_manager_admincp']);

	$params = unserialize(verify_client_string($vbulletin->GPC['criteria']));
	if ($params)
	{
		$nodeids = $assertor->getColumn('vBForum:getNodeToolsTopics', 'nodeid', $params);
		print_node_action($type, $vbphrase, $nodeids, $vbulletin->GPC['destchannelid']);
	}
	print_cp_footer();
}

// ###################### Start move/prune select #######################
if ($_POST['do'] == 'donodessel')
{
	$vbulletin->input->clean_array_gpc('p', [
		'type'        => vB_Cleaner::TYPE_NOHTML,
		'criteria'    => vB_Cleaner::TYPE_STR,
		'destchannelid' => vB_Cleaner::TYPE_INT,
	]);

	print_cp_header($vbphrase['topic_manager_admincp']);

	$type = $vbulletin->GPC['type'];
	check_type_permission($type);

	$assertor = vB::getDbAssertor();
	$nodeApi = vB_Api::instance('node');

	$nodes = [];

	$params = unserialize(verify_client_string($vbulletin->GPC['criteria']));
	if ($params)
	{
		$nodeids = $assertor->getColumn('vBForum:getNodeToolsTopics', 'nodeid', $params);

		$nodes = $nodeApi->getNodes($nodeids);
		print_stop_message_on_api_error($nodes);
	}

	$topicsOnly = true;
	$starterTitles = [];
	$needTitles = [];
	foreach ($nodes AS $node)
	{
		if ($node['starter'] == $node['nodeid'])
		{
			$starterTitles[$node['nodeid']] = $node['title'];
		}
		else
		{
			$topicsOnly = false;
			if (!isset($starterTitles[$node['starter']]))
			{
				$needTitles[] = $node['starter'];
			}
		}
	}

	//shouldn't happen, but let's check.  Weird things could happen if we are wrong.
	if (!$topicsOnly AND $type != 'prune')
	{
		print_stop_message(['action_only_topics', $type]);
	}

	$needTitles = array_unique($needTitles);

	$starters = $nodeApi->getNodes($needTitles);
	foreach ($starters AS $starter)
	{
		$starterTitles[$starter['nodeid']] = $starter['title'];
	}

	unset($staters);

	print_form_header('admincp/nodetools', 'donodesselfinish');
	construct_hidden_code('type', $type);
	construct_hidden_code('destchannelid', $vbulletin->GPC['destchannelid']);

	$typephrases = get_action_phrases($type);
	print_table_header($vbphrase[$typephrases[($topicsOnly ? 'action_topics_selectively' : 'action_nodes_selectively')]], 5);

	$cells = [
		'<input type="checkbox" name="allbox" title="' . $vbphrase['check_all'] . '" onclick="js_check_all(this.form);" checked="checked" />',
		$vbphrase['title'],
		$vbphrase['user'],
		$vbphrase['replies'],
		$vbphrase['last_post'],
	];

	$alignarray = array_fill_keys(array_keys($cells), 'left');
	$alignarray[3] = 'center';
	$alignarray[4] = 'right';

	print_cells_row2($cells, 'thead', $alignarray);

	$pmType = vB_Types::instance()->getContentTypeId('vBForum_PrivateMessage');

	foreach ($nodes AS $node)
	{
		$prefix = '';
		if ($node['prefixid'])
		{
			$prefix = '[' . vB_String::htmlSpecialCharsUni($vbphrase["prefix_$node[prefixid]_title_plain"]) . '] ';
		}


		if ($node['starter'] == $node['nodeid'])
		{
			$title = $node['title'];

			//we don't have a reliable way to route to PMs that the current user -- even an admin --
			//isn't a part of.  They just show up all wonky if we try.  So until we fix that, don't link
			//to PMs.
			$nodeUrl = '';
			if ($node['contenttypeid'] != $pmType)
			{
				$nodeUrl = vB5_Route::buildUrl($node['routeid'] . '|fullurl', $node);
			}
		}
		else
		{
			$title = construct_phrase($vbphrase['child_of_x'], $starterTitles[$node['starter']]) . ' (nodeid ' .  $node['nodeid'] . ')';
			$nodeUrl=	vB5_Route::buildUrl($node['routeid'] . '|fullurl',
				[
					'nodeid' => $node['starter'],
					'innerPost' => $node['nodeid'],
					'innerPostParent' => $node['parentid'],
				]
			);
		}

		$titleVal = $title;
		if ($nodeUrl)
		{
			$titleVal = '<a href="' . $nodeUrl. '" target="_blank">' . $title . '</a>';
		}

		$cells = [];
		$cells[] = "<input type=\"checkbox\" name=\"nodes[$node[nodeid]]\" tabindex=\"1\" checked=\"checked\" />";
		$cells[] = $prefix . $titleVal;

		if ($node['userid'])
		{
			$authorUrl = vB5_Route::buildUrl('profile|fullurl', $node);
			$cells[] = '<span class="smallfont"><a href="' . $authorUrl . '" target="_blank">' . $node['authorname'] . '</a></span>';
		}
		else
		{
			$cells[] = '<span class="smallfont">' . $node['authorname'] . '</span>';
		}

		$cells[] = "<span class=\"smallfont\">$node[textcount]</span>";
		$cells[] = '<span class="smallfont">' . vbdate($vbulletin->options['dateformat'] . ' ' . $vbulletin->options['timeformat'], $node['lastcontent']) . '</span>';

		print_cells_row2($cells, '', $alignarray);

	}

	print_table_default_footer($vbphrase['go'], 5);
	print_cp_footer();
}

// ###################### Start move/prune select - finish! #######################
if ($_POST['do'] == 'donodesselfinish')
{
	require_once(DIR . '/includes/functions_log_error.php');

	$vbulletin->input->clean_array_gpc('p', [
		'type'        => vB_Cleaner::TYPE_NOHTML,
		'nodes'      => vB_Cleaner::TYPE_ARRAY_BOOL,
		'destchannelid' => vB_Cleaner::TYPE_INT,
	]);

	$type = $vbulletin->GPC['type'];
	check_type_permission($type);

	print_cp_header($vbphrase['topic_manager_admincp']);

	$nodes = $vbulletin->GPC['nodes'];
	if (is_array($nodes) AND !empty($nodes))
	{
		$nodeids = array_keys($nodes);
		print_node_action($type, $vbphrase, $nodeids, $vbulletin->GPC['destchannelid']);
	}
	else
	{
		print_stop_message('please_select_at_least_one_node');
	}
	print_cp_footer();
}


/************ HELPER FUNCTIONS/END OF ACTION BLOCKS ******************/

//not all of our actions follow this template, but a lot of them will so let's not repeat ourselves.
function print_standard_node_action_form($vbphrase, $type, $force, $hide, $notice)
{
	$phrases = get_action_phrases($type);
	print_cp_header($vbphrase['topic_manager_admincp']);

	print_form_header2('admincp/nodetools', 'donodes');
	print_table_start2();

	print_table_header($vbphrase[$phrases['action_topics']]);
	if ($notice)
	{
		print_description_row($vbphrase[$notice]);
	}

	construct_hidden_code('type', $type);
	print_node_filter_rows($vbphrase, $force, $hide);
	print_table_default_footer($vbphrase[$phrases['action_topics']]);
	print_cp_footer();
}

/**
 *	@param $force -- in theory this will allow the caller to preset a filter row to a value and
 *		skip displaying the row.  This is intended for avoiding providing a nonsensical option
 *		for a specific filter (such as allowing searching for closed topics for the close action)
 *		In practice we've only implemented options for the filters the callers currently need.
 *		This is an internal function and it's kind of a pain to implement;
 *	@param $hide -- a list of filter sections to skip.
 */
function print_node_filter_rows($vbphrase, $force = [], $hide = [])
{
	$hide = array_flip($hide);
	if (!isset($hide['date']))
	{
		print_date_filters($vbphrase, $force);
	}

	if (!isset($hide['viewreply']))
	{
		print_viewreply_filters($vbphrase, $force);
	}

	if (!isset($hide['status']))
	{
		print_status_filters($vbphrase, $force);
	}

	if (!isset($hide['other']))
	{
		print_other_filters($vbphrase, $force);
	}

	foreach ($force AS $key => $value)
	{
		construct_hidden_code('topic[' . $key . ']', $value);
	}
}

function print_date_filters($vbphrase, $force)
{
	$nolimitdfn_0 = '<dfn>' . construct_phrase($vbphrase['note_leave_x_specify_no_limit'], '0') . '</dfn>';
	print_description_row($vbphrase['date_options'], 0, 2, 'thead', 'center');
	print_input_row($vbphrase['original_post_date_is_at_least_xx_days_ago'], 'topic[originaldaysolder]', 0, 1, 5);
	print_input_row($vbphrase['original_post_date_is_at_most_xx_days_ago'] . $nolimitdfn_0, 'topic[originaldaysnewer]', 0, 1, 5);
	print_input_row($vbphrase['last_post_date_is_at_least_xx_days_ago'], 'topic[lastdaysolder]', 0, 1, 5);
	print_input_row($vbphrase['last_post_date_is_at_most_xx_days_ago'] . $nolimitdfn_0, 'topic[lastdaysnewer]', 0, 1, 5);
}

function print_viewreply_filters($vbphrase, $force)
{
	$nolimitdfn_neg1 = '<dfn>' . construct_phrase($vbphrase['note_leave_x_specify_no_limit'], '-1') . '</dfn>';
	print_description_row($vbphrase['view_options'], 0, 2, 'thead', 'center');
	print_input_row($vbphrase['topic_has_at_least_xx_replies'], 'topic[repliesleast]', 0, 1, 5);
	print_input_row($vbphrase['topic_has_at_most_xx_replies'] . $nolimitdfn_neg1, 'topic[repliesmost]', -1, 1, 5);
	print_input_row($vbphrase['topic_has_at_least_xx_views'], 'topic[viewsleast]', 0, 1, 5);
	print_input_row($vbphrase['topic_has_at_most_xx_views'] . $nolimitdfn_neg1, 'topic[viewsmost]', -1, 1, 5);
}

function print_status_filters($vbphrase, $force)
{
	print_description_row($vbphrase['status_options'], 0, 2, 'thead', 'center');
	print_yes_no_other_row($vbphrase['topic_is_sticky'], 'topic[issticky]', $vbphrase['either'], 0);

	print_yes_no_other_row($vbphrase['topic_is_unpublished'], 'topic[unpublished]', $vbphrase['either'], -1);

	if (!isset($force['moderated']))
	{
		print_yes_no_other_row($vbphrase['topic_is_awaiting_moderation'], 'topic[moderated]', $vbphrase['either'], -1);
	}

	if (!isset($force['isopen']))
	{
		print_yes_no_other_row($vbphrase['topic_is_open'], 'topic[isopen]', $vbphrase['either'], -1);
	}

	if (!isset($force['featured']))
	{
		print_yes_no_other_row($vbphrase['topic_is_featured'] ?? 'featured', 'topic[featured]', $vbphrase['either'], -1);
	}

	print_yes_no_other_row($vbphrase['topic_is_redirect'], 'topic[isredirect]', $vbphrase['either'], 0);
}

function print_other_filters($vbphrase, $force)
{
	print_description_row($vbphrase['other_options'], 0, 2, 'thead', 'center');
	print_input_row($vbphrase['username'], 'topic[posteduser]');
	print_input_row($vbphrase['userid'] . '<dfn>' . $vbphrase['not_used_if_username'] . '</dfn>' , 'topic[userid]', '', 1, 5);
	print_input_row($vbphrase['title'], 'topic[titlecontains]');

	if (!isset($force['channelid']))
	{
		print_move_prune_channel_chooser($vbphrase['channel'], 'topic[channelid]', $vbphrase['all_channels']);
	}

	if (!isset($force['subforums']))
	{
		print_yes_no_row($vbphrase['include_child_channels'], 'topic[subforums]');
	}

	if (!isset($force['prefixid']))
	{
		if ($prefix_options = construct_prefix_options(0, '', true, true))
		{
			print_label_row($vbphrase['prefix'], '<select name="topic[prefixid]" class="bginput">' . $prefix_options . '</select>', '', 'top', 'prefixid');
		}
	}
}

//stripped down channel chooser that only has the options we need for move/prune and skips the special channels.
//print_channel_chooser already has to many impenetrable parameters to add another (though perhaps a version
//that allows passing the results of construct_channel_chooser_options might be generally useful)
function print_move_prune_channel_chooser($title, $name, $topname)
{
	$topchannels = vB_Api::instanceInternal('content_channel')->fetchTopLevelChannelIds();
	$channels = vB_Api::instanceInternal('search')->getChannels(false, ['exclude_subtrees' => $topchannels['special']]);
	$channels = reset($channels);
	$channels = $channels['channels'];

	$options = construct_channel_chooser_options($channels, '', $topname, null);
	print_select_row($title, $name, $options, -1, 0, 0, false);
}

// ###################### Start genmoveprunequery #######################
function fetch_thread_move_prune_sql($db, $topic)
{
	$conditions = [];
	$channelinfo = [];
	$special = [];

	$timenow = vB::getRequest()->getTimeNow();

	//probably not needed because we'll have a starter check by default.  But we don't want
	//channels here regardless.
	$type = vB_Types::instance()->getContentTypeId('vBForum_Channel');
	$conditions[] = ['field' => 'node.contenttypeid', 'value' => $type, 'operator' => vB_dB_Query::OPERATOR_NE];

	// original post
	if (isset($topic['originaldaysolder']) AND intval($topic['originaldaysolder']))
	{
		$timecut = $timenow - ($topic['originaldaysolder'] * 86400);
		$conditions[] = ['field' => 'node.created', 'value' => $timecut, 'operator' => vB_dB_Query::OPERATOR_LTE];
	}

	if (isset($topic['originaldaysnewer']) AND intval($topic['originaldaysnewer']))
	{
		$timecut = $timenow - ($topic['originaldaysnewer'] * 86400);
		$conditions[] = ['field' => 'node.created', 'value' => $timecut, 'operator' => vB_dB_Query::OPERATOR_GTE];
	}

	// last post
	if (isset($topic['lastdaysolder']) AND intval($topic['lastdaysolder']))
	{
		$timecut = $timenow - ($topic['lastdaysolder'] * 86400);
		$conditions[] = ['field' => 'node.lastcontent', 'value' => $timecut, 'operator' => vB_dB_Query::OPERATOR_LTE];
	}

	if (isset($topic['lastdaysnewer']) AND intval($topic['lastdaysnewer']))
	{
		$timecut = $timenow - ($topic['lastdaysnewer'] * 86400);
		$conditions[] = ['field' => 'node.lastcontent', 'value' => $timecut, 'operator' => vB_dB_Query::OPERATOR_GTE];
	}

	// replies
	if (isset($topic['repliesleast']) AND intval($topic['repliesleast']) > 0)
	{
		$conditions[] = ['field' => 'node.textcount', 'value' => intval($topic['repliesleast']), 'operator' => vB_dB_Query::OPERATOR_GTE];
	}

	if (isset($topic['repliesmost']) AND intval($topic['repliesmost']) > -1)
	{
		$conditions[] = ['field' => 'node.textcount', 'value' => intval($topic['repliesmost']), 'operator' => vB_dB_Query::OPERATOR_LTE];
	}

	// views
	if (isset($topic['viewsleast']) AND intval($topic['viewsleast']) > 0)
	{
		$conditions[] = ['field' => 'nodeview.count', 'value' => intval($topic['viewsleast']), 'operator' => vB_dB_Query::OPERATOR_GTE];
	}

	if (isset($topic['viewsmost']) AND intval($topic['viewsmost']) > -1)
	{
		$conditions[] = ['field' => 'nodeview.count', 'value' => intval($topic['viewsmost']), 'operator' => vB_dB_Query::OPERATOR_LTE];
	}

	// sticky
	if (isset($topic['issticky']) AND $topic['issticky'] != -1)
	{
		$conditions['node.sticky'] = $topic['issticky'];
	}

	if (isset($topic['unpublished']) AND $topic['unpublished'] != -1)
	{
		if ($topic['unpublished'])
		{
			//this can't be handled with standard conditions
			$special['unpublished'] = 'yes';
			$special['timenow'] = $timenow;
		}
		else
		{
			$special['unpublished'] = 'no';
			$special['timenow'] = $timenow;
		}
	}

	if (isset($topic['moderated']) AND $topic['moderated'] != -1)
	{
		$conditions['node.approved'] = !$topic['moderated'];
	}

	//status
	if (isset($topic['isopen']) AND $topic['isopen'] != -1)
	{
		$conditions['node.open'] = $topic['isopen'];
	}

	if (isset($topic['featured']) AND $topic['featured'] != -1)
	{
		$conditions['node.featured'] = $topic['featured'];
	}

	if (isset($topic['isredirect']) AND $topic['isredirect'] != -1)
	{
		$op = (($topic['isredirect'] == 1) ? vB_dB_Query::OPERATOR_EQ : vB_dB_Query::OPERATOR_NE);
		$type = vB_Types::instance()->getContentTypeId('vBForum_Redirect');

		$conditions[] = ['field' => 'node.contenttypeid', 'value' => $type, 'operator' => $op];
	}

	// posted by
	if (!empty($topic['posteduser']))
	{
		$user = $db->getRow('user', ['username' => vB_String::htmlSpecialCharsUni($topic['posteduser'])]);
		if (!$user)
		{
			print_stop_message('invalid_username_specified');
		}

		$conditions['node.userid'] = $user['userid'];
	}

	//specifically allow 0 as "guest user"
	else if (isset($topic['userid']) AND ($topic['userid'] != ''))
	{
		$conditions['node.userid'] = $topic['userid'];
	}

	// title contains
	if (!empty($topic['titlecontains']))
	{
		//we are still encoding the title in the DB so we need to do the same to the
		//string in order to get it to match.  This will likely prove fragile but not doing doesn't work.
		$contains = vB_String::htmlSpecialCharsUni($topic['titlecontains']);
		$conditions[] = ['field' => 'node.title', 'value' => $contains, 'operator' => vB_dB_Query::OPERATOR_INCLUDES];
	}

	// forum
	$topic['channelid'] = intval($topic['channelid']);

	if ($topic['channelid'] != -1)
	{
		$channelinfo['channelid'] = $topic['channelid'];
		$channelinfo['subforums'] = $topic['subforums'];

		//we need special handling for PMs.  This is a bit of a hack because
		//in theory this is true even if we had a channel other than the main
		//PM channel containg PMs.  But in practice either we're selecting it
		//explicitly or excluding it altogether and a general solution gets
		//unnecesarily complicated.
		$nodeApi = vB_Api::instanceInternal('node');
		$special['ispm'] = ($topic['channelid'] == $nodeApi->fetchPMChannel());
	}

	// prefixid
	if (isset($topic['prefixid']) AND $topic['prefixid'] != '')
	{
		$conditions['node.prefixid'] = ($topic['prefixid'] == '-1' ? '' : $topic['prefixid']);
	}

	$channelApi = vB_Api::instance('content_channel');
	$channels = $channelApi->fetchTopLevelChannelIds();
	print_stop_message_on_api_error($channels);

	$special['specialchannelid'] = $channels['special'];
	return ['conditions' => $conditions, 'channelinfo' => $channelinfo, 'special'=> $special];
}

//We have a bunch of similar phrases for the different node tool actions.  In some cases we want to share
//phrases.  Let's hide the logic for figuring that out here so that the common code can ... be common.
function get_action_phrases($type)
{
	//for now the prune UI will share basic phrases with the prune type don't add the special prune phrases, they don't apply.
	$basetype = ($type == 'prunepm' ? 'prune' : $type);

	//prune is the only action that allows non topics but it doesn't matter if we create what the phrase
	//name should be ... if we use it for non prune types a blank phrase name isn't better than a
	//phrase that doesn't exist.
	$basephrases = [
		'action_topics' => '%s_topics',
		'action_all_topics' => '%s_all_topics',
		'action_topics_selectively' => '%s_topics_selectively',
		'action_nodes_selectively' => '%s_nodes_selectively'
	];

	$phrases = [];
	foreach ($basephrases AS $key => $phrase)
	{
		$phrases[$key] = sprintf($phrase, $basetype);
	}

	//should rename the phrases here so they can match the above patter of injecting
	//the type name.
	$performingPhrases = [
		'prune' => 'deleting_topics',
		'move' => 'moving_topics',
		'close' => 'closing_topics',
		'open' => 'opening_topics',
		'approve' => 'approving_topics',
		'unapprove' => 'unapproving_topics',
		'feature' => 'featuring_topics',
		'unfeature' => 'unfeaturing_topics',
	];

	$phrases['performing_action'] = $performingPhrases[$basetype];

	//this phrase doesn't follow the pattern (and is different from the other prune phrases)
	if ($type == 'prunepm')
	{
		$phrases['action_topics'] = 'prune_pms';
	}

	return $phrases;
}

function print_node_action($type, $vbphrase, $nodeids, $destination)
{
	$phrases = get_action_phrases($type);
	$nodeApi = vB_Api::instance('node');
	echo '<p><b>' . $vbphrase[$phrases['performing_action']] . '</b>';

	if ($type == 'prune' OR $type == 'prunepm')
	{
		$result = $nodeApi->deleteNodes($nodeids, true);
	}
	else if ($type == 'move')
	{
		$result = $nodeApi->moveNodes($nodeids, $destination);
	}
	else if ($type == 'close')
	{
		$result = $nodeApi->closeNode($nodeids);
	}
	else if ($type == 'open')
	{
		$result = $nodeApi->openNode($nodeids);
	}
	else if ($type == 'approve')
	{
		$result = $nodeApi->approve($nodeids);
	}
	else if ($type == 'unapprove')
	{
		$result = $nodeApi->unapprove($nodeids);
	}
	else if ($type == 'feature')
	{
		$result = $nodeApi->setFeatured($nodeids);
	}
	else if ($type == 'unfeature')
	{
		$result = $nodeApi->setUnFeatured($nodeids);
	}

	echo ' ' . $vbphrase['done'] . '</p>';

	print_stop_message_on_api_error($result);
	print_stop_message('action_performed_successfully', get_admincp_url('nodetools', ['do' => $type]));
}

function check_type_permission(string $type) : void
{
	if ($type == 'prunepm')
	{
		$permission = 'canadminpm';
	}
	else
	{
		$permission = 'canadminthreads';
	}

	if (!vB::getUserContext()->hasAdminPermission($permission))
	{
		print_cp_no_permission();
	}
}


/*======================================================================*\
|| ####################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024 : $Revision: 116491 $
|| # $Date: 2024-06-25 12:58:27 -0700 (Tue, 25 Jun 2024) $
|| ####################################################################
\*======================================================================*/
?>
