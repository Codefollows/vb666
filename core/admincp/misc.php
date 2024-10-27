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
define('CVS_REVISION', '$RCSfile$ - $Revision: 116407 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
global $phrasegroups, $specialtemplates, $vbphrase, $vbulletin;
$phrasegroups = ['cphome', 'maintenance', 'user', 'infraction', 'search'];
if (isset($_POST['do']) AND $_POST['do'] == 'rebuildstyles')
{
	$phrasegroups[] = 'style';
}
$specialtemplates = ['ranks'];


// ########################## REQUIRE BACK-END ############################
require_once(dirname(__FILE__) . '/global.php');
require_once(DIR . '/includes/functions_databuild.php');

require_once (DIR . '/vb/vb.php');
vB::init();

ignore_user_abort(true);
vB_Utility_Functions::setPhpTimeout(0);

// ######################## CHECK ADMIN PERMISSIONS #######################
$maintainAll = vB::getUserContext()->hasAdminPermission('canuseallmaintenance');
if (!can_administer('canadminmaintain') AND !vB::getUserContext()->hasAdminPermission('canadmintemplates')
	AND !$maintainAll)
{
	print_cp_no_permission();
}

// ############################# LOG ACTION ###############################
log_admin_action();

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'chooser';
}

$vbulletin->input->clean_array_gpc('r', [
	'perpage' => vB_Cleaner::TYPE_UINT,
	'startat' => vB_Cleaner::TYPE_UINT
]);

$datastore = vB::getDatastore();
$vboptions = $datastore->getValue('options');
$assertor = vB::getDbAssertor();

// ###################### Start clear cache ########################
if ($_REQUEST['do'] == 'clear_cache')
{
	print_cp_header($vbphrase['clear_system_cache']);
	vB_Cache::resetCache();
	$datastore->resetCache();

	vB::getHooks()->invoke('hookAdminClearedCache', []);
	print_cp_message($vbphrase['cache_cleared']);
}
else
{
	print_cp_header($vbphrase['maintenance']);
}

// ###################### Rebuild all style info #######################
if ($_POST['do'] == 'rebuildstyles')
{
	if (!vB::getUserContext()->hasAdminPermission('canadmintemplates') AND !vB::getUserContext()->hasAdminPermission('canadminstyles'))
	{
		print_cp_no_permission();
	}
	require_once(DIR . '/includes/adminfunctions_template.php');

	$vbulletin->input->clean_array_gpc('p', [
		'install'  => vB_Cleaner::TYPE_BOOL
	]);

	build_all_styles(false, $vbulletin->GPC['install'], 'admincp/misc.php?do=chooser#style');

	print_stop_message2('updated_styles_successfully');
}

// ###################### Start emptying the index #######################
if ($_REQUEST['do'] == 'emptyindex')
{
	if (!$maintainAll)
	{
		print_cp_no_permission();
	}

	print_form_header('admincp/misc', 'doemptyindex');
	print_table_header($vbphrase['confirm_deletion_gcpglobal']);
	print_description_row($vbphrase['are_you_sure_empty_index']);
	print_submit_row($vbphrase['yes'], '', 2, $vbphrase['no']);
}

// ###################### Start emptying the index #######################
if ($_POST['do'] == 'doemptyindex')
{
	if (!$maintainAll)
	{
		print_cp_no_permission();
	}

	$result = vB_Library::instance('search')->emptyIndex();
	print_stop_message_on_api_error($result);

	vB_Cache::resetCache();
	$datastore->resetCache();

	print_stop_message2('emptied_search_index_successfully', 'misc', ['do' => 'chooser']);
}

// ###################### Start rebuild the whole index #######################
if ($_REQUEST['do'] == 'rebuildindex')
{
	if (!$maintainAll)
	{
		print_cp_no_permission();

	}
	print_form_header('admincp/misc', 'dorebuildindex');
	print_table_header($vbphrase['confirm_deletion_gcpglobal']);
	print_description_row($vbphrase['are_you_sure_rebuild_index']);
	print_submit_row($vbphrase['yes'], '', 2, $vbphrase['no']);
}

// ###################### Start rebuilding the index #######################
if ($_POST['do'] == 'dorebuildindex')
{
	if (!$maintainAll)
	{
		print_cp_no_permission();

	}

	$result = vB_Library::instance('search')->reIndexAll(true);
	print_stop_message_on_api_error($result);

	vB_Cache::resetCache();
	$datastore->resetCache();

	if ($result)
	{
		$message = 'rebuilt_search_index_successfully';
	}
	else
	{
		$message = 'rebuilt_search_index_not_implemented';
	}

	print_stop_message2($message, 'admincp/misc', ['do' => 'chooser']);
}

// ###################### Start build search index #######################
if ($_REQUEST['do'] == 'doindextypes')
{
	if (!$maintainAll)
	{
		print_cp_no_permission();
	}

	//this can be memory intensive.  Try to avoid hitting the limit.
	vB_Utilities::extendMemoryLimit();

	$vbulletin->input->clean_array_gpc('r', [
		'autoredirect' => vB_Cleaner::TYPE_BOOL,
		'indextypes'   => vB_Cleaner::TYPE_NOHTML,
		'initialnodeid'   => vB_Cleaner::TYPE_UINT,
		'previousnodeid'   => vB_Cleaner::TYPE_UINT,
	]);

	$starttime = microtime(true);

	//Init Search & get the enabled types to be re-indexed
	$perpage = empty($vbulletin->GPC['perpage']) ? 250 : $vbulletin->GPC['perpage'];

	//we either have
	//1) previousnodeid (from a previous run) which we should exclude from this run
	//2) initialnodeid (which we should start at and include)
	//3) neither, start at 0
	//4) both (shouldn't happen) use the initial value
	$previousNodeId = 0;
	if ($vbulletin->GPC['previousnodeid'])
	{
		$previousNodeId = $vbulletin->GPC['previousnodeid'];
	}

	if ($vbulletin->GPC['initialnodeid'])
	{
		$previousNodeId = $vbulletin->GPC['initialnodeid'] - 1;
	}

	$channelid = $vbulletin->GPC['indextypes'];
	$indextype = '';
	if ($channelid)
	{
		$channelTypes = vB_Channel::getChannelTypes();
		$indextype = $vbphrase[$channelTypes[$channelid]['label']];
	}

	echo '<p>' . construct_phrase($vbphrase['search_index_from_node'], $perpage, $indextype, $previousNodeId) . '</p>';
	vbflush();

	$previousNodeId = vB_Library::instance('search')->indexRangeFromNode($previousNodeId, $perpage, $channelid);

	$pagetime = vb_number_format(microtime(true) - $starttime, 2);

	echo '<p>' .
		construct_phrase($vbphrase['processing_time_x'], $pagetime) . '<br />' .
	'</p>';
	vbflush();

	// There is more to do of that type
	if ($previousNodeId)
	{
		$args = [
			'do' => 'doindextypes',
			'pp' => $perpage,
			'autoredirect' => $vbulletin->GPC['autoredirect'],
			'indextypes' => $vbulletin->GPC['indextypes'],
			'previousnodeid' => $previousNodeId,
		];

		$url = get_admincp_url('misc', $args);
		if ($vbulletin->GPC['autoredirect'] == 1)
		{
			print_cp_redirect($url, 2);
		}
		else
		{
			echo '<p><a href=' . $url . '">' . $vbphrase['click_here_to_continue_processing'] . '</a></p>';
		}
	}
	else
	{
		vB_Cache::resetCache();
		$datastore->resetCache();

		print_stop_message2('rebuilt_search_index_successfully', 'misc', ['do' => 'chooser']);
	}
}

// ###################### Start update post counts ################
if ($_REQUEST['do'] == 'updateposts')
{
	if (!vB::getUserContext()->hasAdminPermission('canadminmaintain'))
	{
		print_cp_no_permission();
	}

	if (empty($vbulletin->GPC['perpage']))
	{
		$vbulletin->GPC['perpage'] = 1000;
	}

	// NOTICE: How we determine if a post counts in user post count here needs to
	// match the criteria used in vB_Library_Content::countInUserPostCount()
	// If you update in one place, please update in the other

	$topChannels = vB_Api::instance('content_channel')->fetchTopLevelChannelIds();
	print_stop_message_on_api_error($topChannels);

	$checkChannels = [
		$topChannels['forum'],
		$topChannels['blog'],
		$topChannels['groups'],
	];
	$channelContentType = vB_Types::instance()->getContentTypeID('vBForum_Channel');

	echo '<p>' . $vbphrase['updating_post_counts'] . '</p>';

	$gotforums = '';
	foreach ($checkChannels as $checkChannel)
	{
		$forums = $vbulletin->db->query_read("
			SELECT node.nodeid
			FROM " . TABLE_PREFIX . "node AS node
			INNER JOIN " . TABLE_PREFIX . "closure AS cl ON cl.parent = $checkChannel AND cl.child = node.nodeid
			WHERE node.contenttypeid = $channelContentType
			AND node.nodeid <> $checkChannel
		");
		while ($forum = $vbulletin->db->fetch_array($forums))
		{
			$gotforums .= ',' . $forum['nodeid'];
		}
	}

	$userresult = $assertor->assertQuery('user',
		[
			vB_dB_Query::CONDITIONS_KEY => [['field' => 'userid', 'value' => $vbulletin->GPC['startat'], 'operator' =>  vB_dB_Query::OPERATOR_GTE]],
			vB_dB_Query::PARAM_LIMIT => $vbulletin->GPC['perpage'],
		],
		'userid'
	);

	$finishat = $vbulletin->GPC['startat'];

	foreach ($userresult AS $user)
	{
		$starterCount = $vbulletin->db->query_first("
			SELECT COUNT(*) AS count
			FROM " . TABLE_PREFIX . "node AS thread
			WHERE thread.userid = " . $user['userid'] . "
			AND thread.parentid IN (0$gotforums)
			AND thread.starter = thread.nodeid
			AND thread.publishdate IS NOT NULL
			AND thread.approved = 1
			AND thread.showpublished = 1
			AND thread.contenttypeid <> " . intval($channelContentType) . "
		");

		$replyCount = $vbulletin->db->query_first("
			SELECT COUNT(*) AS count
			FROM " . TABLE_PREFIX . "node AS post
			INNER JOIN " . TABLE_PREFIX . "node AS thread ON (thread.nodeid = post.parentid)
			WHERE post.userid = " . $user['userid'] . "
			AND thread.parentid IN (0$gotforums)
			AND thread.publishdate IS NOT NULL
			AND thread.approved = 1
			AND post.starter = thread.nodeid
			AND post.publishdate IS NOT NULL
			AND post.approved = 1
			AND post.showpublished = 1
			AND post.contenttypeid <> " . intval($channelContentType) . "
		");

		$totalPosts = (int) $starterCount['count'] + $replyCount['count'];

		$userdm = new vB_DataManager_User($vbulletin, vB_DataManager_Constants::ERRTYPE_CP);
		$userdm->set_existing($user);
		$userdm->set('posts', $totalPosts);
		$userdm->set('startedtopics', intval($starterCount['count']));
		$userdm->set_ladder_usertitle($totalPosts);
		$userdm->save();
		unset($userdm);

		echo construct_phrase($vbphrase['processing_x'], $user['userid']) . "<br />\n";
		vbflush();

		$finishat = $user['userid'];
	}

	$finishat++;

	if (check_for_more_users($assertor, $finishat))
	{
		$args = [
			'do' => 'updateposts',
			'startat' => $finishat,
			'pp' => $vbulletin->GPC['perpage'],
		];
		print_cp_redirect(get_admincp_url('misc', $args), 2);
	}
	else
	{
		vB_Cache::resetCache();
		$datastore->resetCache();

		print_stop_message2('updated_post_counts_successfully', 'admincp/misc');
	}
}

// ###################### Invalidate Passwords ################
if ($_REQUEST['do'] == 'invalidatepasswords')
{
	$userContext = vB::getUserContext();
	$vb5_config = vB::getConfig();

	//we only want to allow this is debug mode.  It's not exactly a no permission error but
	//that's close enough.  (You should be able to get here in normal operation if you aren't
	//in debug mode anyway)
	if (
		!$vb5_config['Misc']['debug'] OR
		!($maintainAll AND $userContext->hasAdminPermission('canadminusers'))
	)
	{
		print_cp_no_permission();
	}

	$perpage = $vbulletin->GPC['perpage'];
	if (!$perpage)
	{
		$perpage = 50000;
	}

	$startat = $vbulletin->GPC['startat'];
	$finishat = $startat + $perpage;

	echo construct_phrase($vbphrase['processing_x_to_y'], $startat, $finishat) . "<br />\n";

	$currentUserId = vB::getCurrentSession()->get('userid');


	$result = $assertor->update('user',
		['scheme' => 'invalid', 'token' => ''],
		[
			['field' => 'userid', 'value' => $startat, vB_dB_Query::OPERATOR_KEY => vB_dB_Query::OPERATOR_GTE],
			['field' => 'userid', 'value' => $finishat, vB_dB_Query::OPERATOR_KEY => vB_dB_Query::OPERATOR_LT],
			['field' => 'userid', 'value' => $currentUserId, vB_dB_Query::OPERATOR_KEY => vB_dB_Query::OPERATOR_NE],
		]
	);

	if (check_for_more_users($assertor, $finishat))
	{
		$args = [
			'do' => 'invalidatepasswords',
			'startat' => $finishat,
			'pp' => $perpage,
		];
		print_cp_redirect(get_admincp_url('misc', $args), 2);
	}
	else
	{
		vB_Cache::resetCache();
		$datastore->resetCache();
		print_stop_message2('invalidated_passwords_successfully', 'admincp/misc');
	}
}

// ###################### Invalidate Passwords ################
if ($_REQUEST['do'] == 'invalidateemailunsub')
{
	$userContext = vB::getUserContext();
	if (!($maintainAll AND $userContext->hasAdminPermission('canadminusers')))
	{
		print_cp_no_permission();
	}

	$vbulletin->input->clean_array_gpc('r', [
		'username' => vB_Cleaner::TYPE_STR,
		'process_all' => vB_Cleaner::TYPE_BOOL,
	]);

	/** @var vB_Library_Unsubscribe */
	$unsubLib = vB_Library::instance('unsubscribe');

	if (empty($vbulletin->GPC['username']) AND !empty($vbulletin->GPC['process_all']))
	{
		$unsubLib->resetAllHashes();
		print_stop_message2('invalidated_unsubhashes_successfully', 'admincp/misc');
	}
	else
	{
		$user = $assertor->getRow('user', ['username' => $vbulletin->GPC['username']]);
		if (!empty($user['userid']))
		{
			$unsubLib->resetSingleHash($user['userid']);
			print_stop_message2(['invalidated_unsubhashes_for_x', $vbulletin->GPC['username']], 'admincp/misc');
		}
		else
		{
			print_stop_message2('no_users_matched_your_query');
		}
	}
}

// ###################### Start update post counts ################
if ($_REQUEST['do'] == 'updatepmtotals')
{
	if (!vB::getUserContext()->hasAdminPermission('canadminmaintain') OR !vB::getUserContext()->hasAdminPermission('canadminusers'))
	{
		print_cp_no_permission();
	}

	if (empty($vbulletin->GPC['perpage']))
	{
		$vbulletin->GPC['perpage'] = 1000;
	}

	//add one to the limit so we can check for a new page without a second query.
	$users = $assertor->assertQuery('user',
		[
			vB_dB_Query::CONDITIONS_KEY => [
    		['field' => 'userid', 'value' => $vbulletin->GPC['startat'], 'operator' =>  vB_dB_Query::OPERATOR_GTE],
			],
			vB_dB_Query::PARAM_LIMIT => $vbulletin->GPC['perpage'] + 1,
			vB_dB_Query::COLUMNS_KEY => ['userid']
		],
		'userid'
	);

	$userids = [];
	foreach ($users AS $user)
	{
		$userids[] = $user['userid'];
	}

	//if we have an additional userid remove from the list and save to be the
	//next start at.
	$checkmore = false;
	if (count($userids) > $vbulletin->GPC['perpage'])
	{
		$finishat = array_pop($userids);
		$checkmore = true;
	}

	echo construct_phrase($vbphrase['processing_x_to_y'], $userids[0], $userids[count($userids)-1]) . "<br />\n";

	$result = vB_Api::instance('content_privatemessage')->buildPmTotals($userids);
	print_stop_message_on_api_error($result);

	if ($checkmore)
	{
		$args = [
			'do' => 'updatepmtotals',
			'startat' => $finishat,
			'pp' => $vbulletin->GPC['perpage'],
		];
		print_cp_redirect(get_admincp_url('misc', $args), 2);

	}
	else
	{
		print_stop_message2('updated_pm_counts_successfully', 'admincp/misc');
	}
}


// ###################### Start update user #######################
if ($_REQUEST['do'] == 'updateuser')
{
	$vbulletin->input->clean_array_gpc('r', [
		'maxid' => vB_Cleaner::TYPE_UINT,
	]);

	if (!vB::getUserContext()->hasAdminPermission('canadminmaintain'))
	{
		print_cp_no_permission();

	}

	if (empty($vbulletin->GPC['perpage']))
	{
		$vbulletin->GPC['perpage'] = 1000;
	}

	echo '<p>' . $vbphrase['updating_user_info'] . '</p>';

	// Needed to fix any totallikes errors
	/** @var vB_Library_Reactions */
	$lib = vB_Library::instance('reactions');
	$enabledvotetypeids = $lib->fetchOnlyEnabledReactionsVotetypeid();
	// Handle totallikes counts... current spec is that similar to reputables, specific reactions will be marked as countable.
	$countableTypes = $lib->getUserRepCountableTypes();
	$countableAndEnabled = [];
	foreach ($enabledvotetypeids AS $__id)
	{
		$__countable = $countableTypes[$__id] ?? false;
		if ($__countable)
		{
			$countableAndEnabled[] = $__id;
		}
	}
	// make sure query doesn't break when all the reactions are disabled or set to not-countable
	if (empty($countableAndEnabled))
	{
		$countableAndEnabled = [0];
	}

	$maxid = $vbulletin->GPC['maxid'];
	if (empty($maxid))
	{
		$maxid = $assertor->getRow('user', [
			vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SUMMARY,
			vB_dB_Query::COLUMNS_KEY => ['MAX(userid)'],
		]);
		$maxid = $maxid['max'];
	}
	$startuserid = $vbulletin->GPC['startat'];
	// We grab the end id instead of using limits to mitigate cases where there are large gaps
	// in the userid sequence (e.g. bunch of culled userids)
	$enduserid = $assertor->getRow('vBAdminCP:getNextEndUserid', [
		'startid' => $startuserid,
		'limit' => $vbulletin->GPC['perpage'],
	]);
	$enduserid = $enduserid['userid'] ?? ($maxid + 1);

	$users = $assertor->assertQuery('getUsersWithRankAndTotalLikes',	[
		'startid' => $startuserid,
		'endid' => $enduserid,
		'enabledvotetypeids' => $countableAndEnabled,
	]);

	$infractionLibrary = vB_Library::instance('Content_Infraction');
	foreach ($users AS $user)
	{
		$userdm = new vB_DataManager_User($vbulletin, vB_DataManager_Constants::ERRTYPE_CP);
		$userdm->set_existing($user);

		// VB6-506 :
		// Verify displaygroupid and change it if, for some reason, user is no
		// longer a member of that group.
		if ($user['displaygroupid'] > 0)
		{
			// Make copies as verify_displaygroupid() will change the value, rather than
			// return anything useful to tell us that something's wrong.
			// We may want to move the check out of the user DM...
			$correctedDisplaygroupid = $user['displaygroupid'];
			$userdm->verify_displaygroupid($correctedDisplaygroupid);
			if ($user['displaygroupid'] != $correctedDisplaygroupid)
			{
				// Displaygroupid of 0 means, effectively, use the primary
				// usergroupid, and is the default. Note that a
				// `user`.`displaygrouid` of 0 will frequently be overriden by
				// the primary usergroupid as a side effect when making group
				// changes. See vB_DataManager_User::pre_save() for more
				// details.

				// We could probably just do verify_displaygroupdid($user['displaygroupid']),
				// but I prefer to explicitly set it here for better readability
				// as well as in case verify_displaygroupid() changes later. We
				// want to update $user so that the user title can also be
				// updated properly below if the displaygroup changes.
				$user['displaygroupid'] = 0;
				$userdm->set('displaygroupid', 0);
			}
		}

		$displaygroupid = ($user['displaygroupid'] == 0 ? $user['usergroupid'] : $user['displaygroupid']);

		// This call seems to be for the (user.permissions.genericpermissions & canusecustomtitle) check below...
		cache_permissions($user, false);

		$userdm->set_usertitle(
			($user['customtitle'] ? $user['usertitle'] : ''),
			false,
			$vbulletin->usergroupcache[$displaygroupid],
			($user['customtitle'] == 1 OR $user['permissions']['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canusecustomtitle']) ? true : false,
			($user['customtitle'] == 1) ? true : false
		);

		$lastpost = $assertor->getRow('vBForum:getLastPostDate', ['userid' => $user['userid']]);
		if ($lastpost)
		{
			$dateline = intval($lastpost['dateline']);
		}
		else
		{
			$dateline = 0;
		}

		$infractioninfo = $infractionLibrary->fetchInfractionGroups($user['ipoints'], $user['usergroupid']);

		$userdm->set('infractiongroupids', $infractioninfo['infractiongroupids']);
		$userdm->set('infractiongroupid', $infractioninfo['infractiongroupid']);

		// 'posts' & 'totallikes' will activate the rank update
		$userdm->set('posts', $user['posts']);
		$userdm->set('lastpost', $dateline);
		// totallikesnew may be null depending on what's enabled / found. Those should mean "0" in terms of the tally.
		$userdm->set('totallikes', intval($user['totallikesnew']));
		$userdm->save();
		unset($userdm);

		echo construct_phrase($vbphrase['processing_x'], $user['userid']) . "<br />\n";
		vbflush();
	}

	if (check_for_more_users($assertor, $enduserid))
	{
		$args = [
			'do' => 'updateuser',
			'startat' => $enduserid,
			'limit' => $vbulletin->GPC['perpage'],
			'maxid' => $maxid,
			'pp' => $vbulletin->GPC['perpage'],
		];
		print_cp_redirect(get_admincp_url('misc', $args), 2);
	}
	else
	{
		vB_Cache::resetCache();
		$datastore->resetCache();

		print_stop_message2('updated_user_titles_successfully', 'admincp/misc');
	}
}

// ###################### Start Promote Usergroups #######################
if ($_REQUEST['do'] == 'promoteuser')
{
	$vbulletin->input->clean_array_gpc('r', [
		'maxid' => vB_Cleaner::TYPE_UINT,
	]);

	if (!vB::getUserContext()->hasAdminPermission('canadminmaintain'))
	{
		print_cp_no_permission();

	}

	if (empty($vbulletin->GPC['perpage']))
	{
		$vbulletin->GPC['perpage'] = 1000;
	}

	echo '<p>' . $vbphrase['updating_user_info'] . '</p>';

	// Needed to fix any totallikes errors
	/** @var vB_Library_Reactions */
	$lib = vB_Library::instance('reactions');
	$enabledvotetypeids = $lib->fetchOnlyEnabledReactionsVotetypeid();
	// Handle totallikes counts... current spec is that similar to reputables, specific reactions will be marked as countable.
	$countableTypes = $lib->getUserRepCountableTypes();
	$countableAndEnabled = [];
	foreach ($enabledvotetypeids AS $__id)
	{
		$__countable = $countableTypes[$__id] ?? false;
		if ($__countable)
		{
			$countableAndEnabled[] = $__id;
		}
	}
	// make sure query doesn't break when all the reactions are disabled or set to not-countable
	if (empty($countableAndEnabled))
	{
		$countableAndEnabled = [0];
	}

	$maxid = $vbulletin->GPC['maxid'];
	if (empty($maxid))
	{
		$maxid = $assertor->getRow('user', [
			vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SUMMARY,
			vB_dB_Query::COLUMNS_KEY => ['MAX(userid)'],
		]);
		$maxid = $maxid['max'];
	}
	$startuserid = $vbulletin->GPC['startat'];
	// We grab the end id instead of using limits to mitigate cases where there are large gaps
	// in the userid sequence (e.g. bunch of culled userids)
	$enduserid = $assertor->getRow('vBAdminCP:getNextEndUserid', [
		'startid' => $startuserid,
		'limit' => $vbulletin->GPC['perpage'],
	]);
	$enduserid = $enduserid['userid'] ?? ($maxid + 1);

	echo construct_phrase($vbphrase['processing_x_to_y'], $startuserid, $enduserid) . "<br />\n";
	vbflush();

	/** @var vB_Library_Usergroup */
	$usergroupLib = vB_Library::instance('usergroup');
	$usergroupLib->promoteUsersByBatch($startuserid, $enduserid);


	if (check_for_more_users($assertor, $enduserid))
	{
		$args = [
			'do' => 'promoteuser',
			'startat' => $enduserid,
			'limit' => $vbulletin->GPC['perpage'],
			'maxid' => $maxid,
			'pp' => $vbulletin->GPC['perpage'],
		];
		print_cp_redirect(get_admincp_url('misc', $args), 2);
	}
	else
	{
		// This is used by the cron to figure out the lastactivity lookback cutoff.
		$timenow = vB::getRequest()->getTimeNow();
		$datastore  = vB::getDatastore();
		$miscoptions = $datastore->getValue('miscoptions');
		$miscoptions['promotions_lastrun'] = $timenow;
		$datastore->build('miscoptions', serialize($miscoptions), 1);

		print_stop_message2('users_promoted_successfully', 'admincp/misc');
	}
}

// ###################### Start update usernames #######################
if ($_REQUEST['do'] == 'updateusernames')
{
	if (!vB::getUserContext()->hasAdminPermission('canadminmaintain'))
	{
		print_cp_no_permission();
	}

	if (empty($vbulletin->GPC['perpage']))
	{
		$vbulletin->GPC['perpage'] = 1000;
	}

	echo '<p>' . $vbphrase['updating_usernames'] . '</p>';

	$userresult = $assertor->assertQuery('user',
		[
			vB_dB_Query::CONDITIONS_KEY => [['field' => 'userid', 'value' => $vbulletin->GPC['startat'], 'operator' =>  vB_dB_Query::OPERATOR_GTE]],
			vB_dB_Query::PARAM_LIMIT => $vbulletin->GPC['perpage'],
		],
		'userid'
	);

	$userlib = vB_Library::instance('user');

	$finishat = $vbulletin->GPC['startat'];
	foreach ($userresult AS $user)
	{
		$userman = new vB_DataManager_User($vbulletin, vB_DataManager_Constants::ERRTYPE_SILENT);
		$userman->set_existing($user);
		$userman->update_username($user['userid'], $user['username'], $user['displayname']);
		$userlib->updateLatestUser($user['userid'], 'update');
		unset($userman);

		echo construct_phrase($vbphrase['processing_x'], $user['userid']) . "<br />\n";
		vbflush();

		$finishat = $user['userid'];
	}

	$finishat++; // move past the last processed user

	if (check_for_more_users($assertor, $finishat))
	{
		$args = [
			'do' => 'updateusernames',
			'startat' => $finishat,
			'pp' => $vbulletin->GPC['perpage'],
		];
		print_cp_redirect(get_admincp_url('misc', $args), 2);
	}
	else
	{
		vB_Cache::resetCache();
		$datastore->resetCache();

		print_stop_message2('updated_usernames_successfully', 'admincp/misc');
	}
}

// ###################### Start reset displaynnames #######################
if ($_REQUEST['do'] == 'resetdisplaynames')
{
	if (!vB::getUserContext()->hasAdminPermission('canadminmaintain'))
	{
		print_cp_no_permission();
	}

	if (empty($vbulletin->GPC['perpage']))
	{
		$vbulletin->GPC['perpage'] = 1000;
	}

	echo '<p>' . $vbphrase['updating_displaynames'] . '</p>';

	$userresult = $assertor->assertQuery('user',
		[
			vB_dB_Query::CONDITIONS_KEY => [['field' => 'userid', 'value' => $vbulletin->GPC['startat'], 'operator' =>  vB_dB_Query::OPERATOR_GTE]],
			vB_dB_Query::PARAM_LIMIT => $vbulletin->GPC['perpage'],
		],
		'userid'
	);

	//We don't use this for this action. For the update usernames we call updateLatestUser.  Not sure if this is an oversight
	//$userlib = vB_Library::instance('user');

	$finishat = $vbulletin->GPC['startat'];
	foreach ($userresult AS $user)
	{
		$newDisplayname = vB_String::unHtmlSpecialChars($user['username']);
		if ($newDisplayname != $user['displayname'])
		{
			// This tool is near identical to updateusernames but it saves each user's displaynames as well via the dm.
			// Doing it this way also invokes update_usernames() if the name changed.
			$userman = new vB_DataManager_User($vbulletin, vB_DataManager_Constants::ERRTYPE_SILENT);
			$userman->set_existing($user);
			$userman->set('displayname', $newDisplayname);
			$userman->save();
			unset($userman);
		}

		echo construct_phrase($vbphrase['processing_x'], $user['userid']) . "<br />\n";
		vbflush();

		$finishat = $user['userid'];
	}

	$finishat++; // move past the last processed user

	if (check_for_more_users($assertor, $finishat))
	{
		$args = [
			'do' => 'resetdisplaynames',
			'startat' => $finishat,
			'pp' => $vbulletin->GPC['perpage'],
		];
		print_cp_redirect(get_admincp_url('misc', $args), 2);
	}
	else
	{
		vB_Cache::resetCache();
		$datastore->resetCache();

		print_stop_message2('updated_displaynames_successfully', 'admincp/misc');
	}
}

// ###################### Start update forum #######################
if ($_REQUEST['do'] == 'updateforum')
{
	if (!vB::getUserContext()->hasAdminPermission('canadminmaintain'))
	{
		print_cp_no_permission();

	}

	if (empty($vbulletin->GPC['startat']))
	{
		$vbulletin->GPC['startat'] = 0;
	}
	$processed = 0;
	$vbulletin->input->clean_gpc('r', 'processed', vB_Cleaner::TYPE_UINT);

	if ($vbulletin->GPC_exists['processed'])
	{
		$processed = $vbulletin->GPC['processed'];
	}

	$channelTypeid = vB_Types::instance()->getContentTypeID('vBForum_Channel');
	$maxChannel = $assertor->getRow('vBAdmincp:getMaxChannel', []);
	$maxChannel = $maxChannel['maxid'];
	echo '<p>' . $vbphrase['updating_forums'] . '</p>';
	echo '<p>' . $vbphrase['forum_update_runs_multiple'] . '</p>';

	if ($vbulletin->GPC['startat'] > $maxChannel)
	{
		if ($processed == 0)
		{
			vB_Cache::resetCache();
			$datastore->resetCache();

			print_stop_message2('updated_forum_successfully', 'admincp/misc');
		}
		else
		{
			$args = [
				'do' => 'updateforum',
				'startat' => 0,
				'processed' => 0,
				'perpage' => $vbulletin->GPC['perpage'],
			];
			print_cp_redirect(get_admincp_url('misc', $args), 2);
		}
	}
	else
	{
		$end = $vbulletin->GPC['startat'] + $vbulletin->GPC['perpage'] - 1;
		echo '<p>' . construct_phrase($vbphrase['x_to_y_of_z'],  $vbulletin->GPC['startat'], $end, $maxChannel) . '</p>';

		$nodeids = $assertor->getColumn('vBAdmincp:getNextChannels', 'nodeid', ['startat' => $vbulletin->GPC['startat'], 'blocksize' => $vbulletin->GPC['perpage']]);

		if (empty($nodeids))
		{
			if ($processed == 0)
			{
				//not sure what's going on here.
				die('no more nodes ' . $vbulletin->GPC['startat'] . ', ' . $vbulletin->GPC['perpage']);
				print_stop_message2('updated_forum_successfully', 'admincp/misc');
			}
			else
			{
				$args = [
					'do' => 'updateforum', 'startat' => 0,
					'processed' => 0,
					'perpage' => $vbulletin->GPC['perpage']
				];
				print_cp_redirect(get_admincp_url('misc', $args), 2);
			}
		}
		else
		{
			$assertor->assertQuery('vBAdmincp:updateChannelCounts', [
				'nodeids' => $nodeids,
				'channelTypeid' => $channelTypeid,
			]);
			$count = $assertor->getRow('vBAdmincp:rows_affected', []);

			if (!empty($count) AND empty($count['errors']) AND !empty($count['qty']))
			{
				$processed += $count['qty'];
			}

			$assertor->assertQuery('vBAdmincp:updateChannelLast',	['nodeids' => $nodeids]);
			$startat = max($nodeids) + 1;

			$args = [
				'do' => 'updateforum',
				'processed' => $processed,
				'startat' => $startat,
				'perpage' => $vbulletin->GPC['perpage'],
			];
			print_cp_redirect(get_admincp_url('misc', $args), 2);
		}
	}
}

// ###################### Start update threads #######################
if ($_REQUEST['do'] == 'updatethread')
{
	if (!vB::getUserContext()->hasAdminPermission('canadminmaintain'))
	{
		print_cp_no_permission();

	}

	$perpage = vB_Utility_Functions::getPositiveIntParam($vbulletin->GPC, 'perpage', 2000, 1);

	echo '<p>' . $vbphrase['updating_threads'] . '</p>';

	$maxstarter = $assertor->getRow('vBAdmincp:getMaxStarter', []);
	$maxstarter = $maxstarter['maxstarter'];

	$types = vB_Types::instance();
	$excludeTypes = [
		$types->getContentTypeID('vBForum_Channel'),
		$types->getContentTypeID('vBForum_Photo'),
		$types->getContentTypeID('vBForum_Attach'),
	];

	$end =  min($vbulletin->GPC['startat'] + $perpage - 1, $maxstarter);
	echo '<p>' . construct_phrase($vbphrase['x_to_y_of_z'],  $vbulletin->GPC['startat'], $end, $maxstarter) . '</p>';

	//run the queries to fix the threads in range.
	$assertor->assertQuery('vBAdmincp:updateThreadCounts', [
		'start' => $vbulletin->GPC['startat'],
		'end' => $end,
		'nonTextTypes' => $excludeTypes
	]);

	$assertor->assertQuery('vBAdmincp:updateThreadLast', [
		'start' => $vbulletin->GPC['startat'],
		'end' => $end,
		'nonTextTypes' => $excludeTypes
	]);

	/** @var vB_Library_Reactions */
	$reactionsLib = vB_Library::instance('reactions');
	$enabledvotetypeids = $reactionsLib->fetchOnlyEnabledReactionsVotetypeid();
	if (empty($enabledvotetypeids))
	{
		$enabledvotetypeids = [0];
	}
	// Note, addition of the enabledvotetypeids makes the inner COUNT query go from USING INDEX to USING WHERE, USING INDEX, so
	// slightly worse performance, but timing-wise does not seem too bad, generally < 0.05s per 2000 nodes (granted, fairly small
	// local database).
	$assertor->assertQuery('vBAdmincp:bulkUpdateNodeVotes', [
		'start' => $vbulletin->GPC['startat'],
		'end' => $end,
		'enabledvotetypeids' => $enabledvotetypeids,
	]);

	$result = $assertor->assertQuery('vBAdmincp:getThreadsForUpdate', [
		'start' => $vbulletin->GPC['startat'],
		'end' => $end,
		'nonTextTypes' => $excludeTypes
	]);

	foreach ($result AS $row)
	{
		$data = [];

		//Only update the fields if we have a reason.  Most records won't change here and we
		//want to make sure that we don't unnecesary updates.
		$ident = vB_String::getUrlIdent($row['title']);
		if ($ident != $row['urlident'])
		{
			$data['urlident'] = $ident;
		}

		//We may have a custom description and figuring that out is more or less impossible.
		//But the description should never be blank, so if it is we should fix that.
		//It user can update the description for a particular node by editing and saving it.
		//If we need a more explicit batch process to allow the admin to reset descriptions
		//we'll need to figure out what the flow looks like so they understand the implications.
		if (!$row['descriptionlength'])
		{
			$contentlib = vB_Library_Content::getContentLib($row['contenttypeid']);
			if (method_exists($contentlib, 'getNodeDescription'))
			{
				$text = $contentlib->getNodeDescription($row, false);
				if ($text)
				{
					$data['description'] = $text;
				}
			}
		}

		if ($data)
		{
			$assertor->update('vBForum:node', $data, ['nodeid' => $row['nodeid']]);
		}
	}

	//generate the next batch information of things (or quit).
	$startat = $assertor->getRow('vBAdmincp:getNextStarter', ['startat' => $end]);
	$next = $startat['next'];
	if (!$next OR ($next >= $maxstarter))
	{
		vB_Cache::resetCache();
		$datastore->resetCache();
		print_stop_message2('updated_threads_successfully', 'admincp/misc');
	}
	else
	{
		$args = [
			'do' => 'updatethread',
			'startat' => $next,
			'pp' => $perpage,
		];
		print_cp_redirect(get_admincp_url('misc', $args), 2);
	}
}

// ################## Start rebuilding user reputation ######################
if ($_POST['do'] == 'rebuildreputation')
{
	// Note, we do not account for current reaction status here. I.e. if a user has
	// a reputation record from the past when a reaction was enabled & reputable,
	// they keep that reputation score.

	if (!vB::getUserContext()->hasAdminPermission('canadminmaintain'))
	{
		print_cp_no_permission();

	}
	$vbulletin->input->clean_array_gpc('p', [
		'reputation_base' => vB_Cleaner::TYPE_INT,
	]);

	$users = $vbulletin->db->query_read("
		SELECT reputation.userid, SUM(reputation.reputation) AS totalrep
		FROM " . TABLE_PREFIX . "reputation AS reputation
		GROUP BY reputation.userid
	");

	$userrep = [];
	while ($user = $vbulletin->db->fetch_array($users))
	{
		$user['totalrep'] += $vbulletin->GPC['reputation_base'];
		$userrep["$user[totalrep]"] .= ",$user[userid]";
	}

	$usercasesql = '';
	if (!empty($userrep))
	{
		foreach ($userrep AS $reputation => $ids)
		{
			$usercasesql .= " WHEN userid IN (0$ids) THEN $reputation";
		}
	}

	if ($usercasesql)
	{
		$vbulletin->db->query_write("
			UPDATE " . TABLE_PREFIX . "user
			SET reputation =
				CASE
					$usercasesql
					ELSE " . $vbulletin->GPC['reputation_base'] . "
				END
		");
	}
	else // there is no reputation
	{
		$vbulletin->db->query_write("
			UPDATE " . TABLE_PREFIX . "user
			SET reputation = " . $vbulletin->GPC['reputation_base'] . "
		");
	}

	vB_Cache::resetCache();
	$datastore->resetCache();

	print_stop_message2('rebuilt_user_reputation_successfully', 'admincp/misc');

}

// ################## Start rebuilding avatar thumbnails ################
if ($_REQUEST['do'] == 'rebuildavatars')
{
	if (!$maintainAll AND !vB::getUserContext()->hasAdminPermission('canadminmaintain'))
	{
		print_cp_no_permission();
	}

	$vbulletin->input->clean_array_gpc('r', [
		'autoredirect' => vB_Cleaner::TYPE_BOOL,
	]);

	vB_Utilities::extendMemoryLimit();

	if ($vboptions['usefileavatar'])
	{
		// avatarpath option is relative to the core directory.
		$avatardir = DIR . DIRECTORY_SEPARATOR . $vboptions['avatarpath'];
		$thumbsdir = $avatardir . '/thumbs';
		if (!file_exists($avatardir))
		{
			if (!mkdir($avatardir))
			{
				print_stop_message2(['custom_avatarpath_missing', $avatardir]);
			}
		}

		if (!is_writable($avatardir))
		{
			print_stop_message2(['custom_avatarpath_not_writable', $avatardir]);
		}

		if (!file_exists($thumbsdir))
		{
			if (!mkdir($thumbsdir))
			{
				print_stop_message2(['custom_thumbpath_missing', $thumbsdir]);
			}
		}

		if (!is_writable($thumbsdir))
		{
			print_stop_message2(['custom_thumbpath_not_writable', $thumbsdir]);
		}
	}

	if (empty($vbulletin->GPC['perpage']))
	{
		$vbulletin->GPC['perpage'] = 20;
	}

	if (!$vbulletin->GPC['startat'])
	{
		$firstattach = $assertor->getRow('vBAdmincp:getMinCustomavatarUserid');
		$vbulletin->GPC['startat'] = intval($firstattach['min']);
	}

	$baseUrl = 'admincp/misc.php?';
	$query = [
		'do' => 'rebuildavatars',
		'startat' => $vbulletin->GPC['startat'],
		'pp' => $vbulletin->GPC['perpage'],
		'autoredirect' => $vbulletin->GPC['autoredirect'],
	];
	$url = htmlspecialchars($baseUrl . http_build_query($query));
	echo '<p>' . construct_phrase($vbphrase['building_avatar_thumbnails'], $url) . '</p>';

	$params = [
		'startat' => $vbulletin->GPC['startat'],
		'perpage' => $vbulletin->GPC['perpage'],
	];
	$avatars = $assertor->assertQuery('vBAdmincp:getCustomAvatarDataForRebuild', $params);

	$finishat = $vbulletin->GPC['startat'];
	$userpic = vB_DataManager_Userpic::fetch_library($vbulletin, vB_DataManager_Constants::ERRTYPE_CP, 'userpic_avatar');

	foreach ($avatars AS $avatar)
	{
		echo construct_phrase($vbphrase['processing_x'], "$vbphrase[avatar] : $avatar[userid] (" . file_extension($avatar['filename']) . ') ');

		if ($vboptions['usefileavatar'])
		{
			// Re-generate the thumb based on current fullsized avatar.
			// userpic will be vB_DataManager_Userpic_Filesystem if usefileavatar
			// Based on vB_DataManager_Userpic_Filesystem::post_save_each()
			//Error handling added to avoid loop break and continue processing the rest of the avatar files.
			try
			{
				['full' => $fullSizedFilename, 'thumb' => $thumbfilename] = $userpic->getAvatarFilenames($avatar['userid'], $avatar['avatarrevision'], $avatar['extension']);
				$thumbnail = $userpic->fetch_thumbnail($fullSizedFilename, true);
				$writeSuccess = false;
				require_once(DIR . '/includes/functions_file.php');
				if ($thumbnail['filedata'] AND vbmkdir(dirname($thumbfilename)) AND $filenum = @fopen($thumbfilename, 'wb'))
				{
					@fwrite($filenum, $thumbnail['filedata']);
					@fclose($filenum);
					$writeSuccess = true;
				}
				else
				{
					print_description_row($vbphrase['error_processing_file']);
				}

				if ($writeSuccess AND $thumbnail['height'] AND $thumbnail['width'])
				{
					$assertor->update('vBForum:customavatar',
						[
						'width_thumb' => $thumbnail['width'],
						'height_thumb' => $thumbnail['height'],
						],
						['userid' => $avatar['userid']]
					);
				}
			}
			catch (\Exception $e)
			{
				print_description_row($vbphrase['error_processing_file']);
			}

		}
		else if (!empty($avatar['filedata']))
		{
			// Avatar in DB, regenerate thumb in DB
			$dataman = new vB_DataManager_Userpic_Avatar(vB_DataManager_Constants::ERRTYPE_STANDARD);
			$dataman->set_existing($avatar);
			$dataman->save();
			unset($dataman);
		}

		echo '<br />';
		vbflush();

		$finishat = ($avatar['userid'] > $finishat ? $avatar['userid'] : $finishat);
	}

	$finishat++;

	if ($checkmore = $vbulletin->db->query_first("SELECT userid FROM " . TABLE_PREFIX . "customavatar WHERE userid >= $finishat LIMIT 1"))
	{
		$query['startat'] = $finishat;
		if ($vbulletin->GPC['autoredirect'] == 1)
		{
			print_cp_redirect(get_redirect_url('admincp/misc.php', $query), 2);
		}

		$url = htmlspecialchars($baseUrl . http_build_query($query));
		echo '<p><a href="' . $url . '">' . $vbphrase['click_here_to_continue_processing'] . "</a></p>";
	}
	else
	{
		vB_Cache::resetCache();
		$datastore->resetCache();

		print_stop_message2('rebuilt_avatar_thumbnails_successfully', 'admincp/misc');
	}
}

// ###################### Start remove dupe #######################
if ($_REQUEST['do'] == 'removedupe')
{
	if (!$maintainAll)
	{
		print_cp_no_permission();

	}

	if (empty($vbulletin->GPC['perpage']))
	{
		$vbulletin->GPC['perpage'] = 500;
	}

	echo '<p>' . $vbphrase['removing_duplicate_threads'] . '</p>';

	$channelContentType = vB_Types::instance()->getContentTypeID('vBForum_Channel');

	$topLevelChannels = vB_Api::instance('content_channel')->fetchTopLevelChannelIds();
	print_stop_message_on_api_error($topLevelChannels);

	$specialChannelNodeId  = (int) $topLevelChannels['special'];

	if ($specialChannelNodeId < 1)
	{
		print_stop_message2('invalid_special_channel');
	}

	$threads = $vbulletin->db->query_read("
		SELECT nodeid, title, parentid, authorname, publishdate
		FROM " . TABLE_PREFIX . "node
		WHERE nodeid >= " . $vbulletin->GPC['startat'] . "
			AND contenttypeid != " . $channelContentType . "
		ORDER BY nodeid
		LIMIT " . $vbulletin->GPC['perpage']
	);

	$finishat = $vbulletin->GPC['startat'];
	$nodeApi = vB_Api::instance('node');
	$deletedNodeIds = [];

	while ($thread = $vbulletin->db->fetch_array($threads))
	{
		$finishat = ($thread['nodeid'] > $finishat ? $thread['nodeid'] : $finishat);
		// Skip any threads we have already deleted
		if (in_array($thread['nodeid'], $deletedNodeIds))
		{
			echo construct_phrase($vbphrase['skipping_x'], $thread['nodeid']) . "<br />\n";
			continue;
		}

		// Skip anything in the 'special' channel
		$node = $nodeApi->getNode($thread['nodeid'], true);
		if (isset($node['errors']))
		{
			// Invalid node, we can safely skip it
			$errorPhrase = $node['errors'][0][0] ?? '';
			echo construct_phrase($vbphrase['skipping_x'], $thread['nodeid']) . ' ' . (isset($vbphrase[$errorPhrase]) ? $vbphrase[$errorPhrase] : $errorPhrase) . "<br />\n";
			continue;
		}
		else if (in_array($specialChannelNodeId, $node['parents']))
		{
			echo construct_phrase($vbphrase['skipping_x'], $thread['nodeid']) . "<br />\n";
			continue;
		}

		// Skip anything whose parent is not a channel (this means it's not a thread, it's a reply, comment, etc.)
		$parentinfo = $vbulletin->db->query_first("
			SELECT nodeid, parentid, contenttypeid
			FROM " . TABLE_PREFIX . "node
			WHERE nodeid = " . intval($thread['parentid']) . "
		");
		if ($parentinfo['contenttypeid'] != $channelContentType)
		{
			echo construct_phrase($vbphrase['skipping_x'], $thread['nodeid']) . "<br />\n";
			continue;
		}

		echo construct_phrase($vbphrase['processing_x'], $thread['nodeid'] . ' "' . htmlspecialchars($thread['title']) . '"') . "<br />\n";
		vbflush();

		$deletethreads = $vbulletin->db->query_read("
			SELECT *
			FROM " . TABLE_PREFIX . "node
			WHERE title = '" . $vbulletin->db->escape_string($thread['title']) . "' AND
				parentid = $thread[parentid] AND
				authorname = '" . $vbulletin->db->escape_string($thread['authorname']) . "' AND
				publishdate = $thread[publishdate] AND
				nodeid > $thread[nodeid] AND
				contenttypeid != " . $channelContentType . "
		");
		while ($deletethread = $vbulletin->db->fetch_array($deletethreads))
		{
			$result = vB_Api::instance('node')->deleteNodes($deletethread['nodeid']);
			print_stop_message_on_api_error($result);

			$deletedNodeIds[] = $deletethread['nodeid'];
			echo "&nbsp;&nbsp;&nbsp; ".construct_phrase($vbphrase['delete_x'], $deletethread['nodeid'] . ' "' . htmlspecialchars($deletethread['title']) . '"') . "<br />";
			vbflush();
		}

	}

	$finishat++;

	if ($checkmore = $vbulletin->db->query_first("SELECT nodeid FROM " . TABLE_PREFIX . "node WHERE nodeid >= $finishat LIMIT 1"))
	{
		$args = [];
		$args['do'] = 'removedupe';
		$args['startat'] = $finishat;
		$args['pp'] = $vbulletin->GPC['perpage'];
		print_cp_redirect2('admincp/misc.php', $args, 2, '');
	}
	else
	{
		vB_Cache::resetCache();
		$datastore->resetCache();

		print_stop_message2('deleted_duplicate_threads_successfully', 'admincp/misc');
	}
}

// ###################### Start find lost users #######################
if ($_POST['do'] == 'lostusers')
{
	if (!vB::getUserContext()->hasAdminPermission('canadminmaintain'))
	{
		print_cp_no_permission();

	}

	$users = $vbulletin->db->query_read("
		SELECT user.userid
		FROM " . TABLE_PREFIX . "user AS user
		LEFT JOIN " . TABLE_PREFIX . "userfield AS userfield USING(userid)
		WHERE userfield.userid IS NULL
	");

	$userids = [];
	while ($user = $vbulletin->db->fetch_array($users))
	{
		$userids[] = $user['userid'];
	}

	if (!empty($userids))
	{
		/*insert query*/
		$vbulletin->db->query_write("INSERT INTO " . TABLE_PREFIX . "userfield (userid) VALUES (" . implode('),(', $userids) . ")");
	}

	$users = $vbulletin->db->query_read("
		SELECT user.userid
		FROM " . TABLE_PREFIX . "user AS user
		LEFT JOIN " . TABLE_PREFIX . "usertextfield AS usertextfield USING(userid)
		WHERE usertextfield.userid IS NULL
	");

	$userids = [];
	while ($user = $vbulletin->db->fetch_array($users))
	{
		$userids[] = $user['userid'];
	}

	if (!empty($userids))
	{
		/*insert query*/
		$vbulletin->db->query_write("INSERT INTO " . TABLE_PREFIX . "usertextfield (userid) VALUES (" . implode('),(', $userids) . ")");
	}

	vB_Cache::resetCache();
	$datastore->resetCache();

	print_stop_message2('user_records_repaired', 'admincp/misc');
}

// ###################### Start build statistics #######################
if ($_REQUEST['do'] == 'buildstats')
{
	if (!vB::getUserContext()->hasAdminPermission('canadminmaintainall'))
	{
		print_cp_no_permission();

	}

	$timestamp =& $vbulletin->GPC['startat'];
	$vbulletin->GPC['perpage'] = 10 * 86400;

	if (empty($timestamp))
	{
		// this is the first page of a stat rebuild
		// so let's clear out the old stats
		$vbulletin->db->query_write("TRUNCATE TABLE " . TABLE_PREFIX . "stats");

		// and select a suitable start time
		$timestamp = $vbulletin->db->query_first("SELECT MIN(joindate) AS start FROM " . TABLE_PREFIX . "user WHERE joindate > 0");
		if ($timestamp['start'] == 0 OR $timestamp['start'] < 915166800)
		{ // no value found or its before 1999 lets just make it the year 2000
			$timestamp['start'] = 946684800;
		}
		$month = date('n', $timestamp['start']);
		$day = date('j', $timestamp['start']);
		$year = date('Y', $timestamp['start']);

		$timestamp = mktime(0, 0, 0, $month, $day, $year);
	}

	if ($timestamp + $vbulletin->GPC['perpage'] >= TIMENOW)
	{
		$endstamp = TIMENOW;
	}
	else
	{
		$endstamp = $timestamp + $vbulletin->GPC['perpage'];
	}

	$topChannels = vB_Api::instance('content_channel')->fetchTopLevelChannelIds();
	print_stop_message_on_api_error($topChannels);

	$forumChannel = $topChannels['forum'];
	$channelContentType = vB_Types::instance()->getContentTypeID('vBForum_Channel');

	while ($timestamp <= $endstamp)
	{
		// new users
		$newusers = $vbulletin->db->query_first('SELECT COUNT(userid) AS total FROM ' . TABLE_PREFIX . 'user WHERE joindate >= ' . $timestamp . ' AND joindate < ' . ($timestamp + 86400));

		// new threads
		$newthreads = $vbulletin->db->query_first('SELECT COUNT(nodeid) AS total FROM ' . TABLE_PREFIX . 'node AS node INNER JOIN ' . TABLE_PREFIX . 'closure AS cl ON cl.parent = ' . $forumChannel . ' WHERE node.nodeid = node.starter AND cl.child = node.nodeid AND node.publishdate >= ' . $timestamp . ' AND node.publishdate < ' . ($timestamp + 86400));

		// new posts
		$newposts = $vbulletin->db->query_first('SELECT COUNT(nodeid) AS total FROM ' . TABLE_PREFIX . 'node AS node INNER JOIN ' . TABLE_PREFIX . 'closure as cl ON cl.parent = ' . $forumChannel . ' WHERE node.nodeid != node.starter AND cl.child = node.nodeid AND node.contenttypeid != ' . $channelContentType . ' AND node.publishdate >= ' . $timestamp . ' AND node.publishdate < ' . ($timestamp + 86400));

		// active users
		$activeusers = $vbulletin->db->query_first('SELECT COUNT(userid) AS total FROM ' . TABLE_PREFIX . 'user WHERE lastactivity >= ' . $timestamp . ' AND lastactivity < ' . ($timestamp + 86400));

		$inserts[] = "($timestamp, $newusers[total], $newthreads[total], $newposts[total], $activeusers[total])";

		echo $vbphrase['done'] . " $timestamp <br />\n";
		vbflush();

		$timestamp += 3600 * 24;

	}

	if (!empty($inserts))
	{
		/*insert query*/
		$vbulletin->db->query_write("
			REPLACE INTO " . TABLE_PREFIX . "stats
				(dateline, nuser, nthread, npost, ausers)
			VALUES
				" . implode(',', $inserts) . "
		");

		$args = [];
		$args['do'] = 'buildstats';
		$args['startat'] = $timestamp;
		print_cp_redirect(get_redirect_url('misc.php', $args, 'admincp'), 2);
	}
	else
	{
		vB_Cache::resetCache();
		$datastore->resetCache();

		print_stop_message2('rebuilt_statistics_successfully', 'admincp/misc');
	}
}

// ###################### Start remove dupe threads #######################
if ($_REQUEST['do'] == 'removeorphanthreads')
{
	if (!$maintainAll)
	{
		print_cp_no_permission();

	}

	if (empty($vbulletin->GPC['perpage']))
	{
		$vbulletin->GPC['perpage'] = 50;
	}

	$result = fetch_adminutil_text('orphanthread');

	if ($result == 'done')
	{
		build_adminutil_text('orphanthread');

		vB_Cache::resetCache();
		$datastore->resetCache();

		print_stop_message2('deleted_orphan_threads_successfully_gmaintenance', 'admincp/misc');
	}
	else if ($result != '')
	{
		$threadarray = unserialize($result);
	}
	else
	{
		$excludeTypes = array_keys(vB_Types::instance()->getContentTypeClasses([
			'vBForum_Channel',
			'vBForum_Photo',
			'vBForum_Attach',
			'vBForum_PrivateMessage',
		]));

		$channelContentType = vB_Types::instance()->getContentTypeID('vBForum_Channel');

		$threadarray = [];

		// Fetch IDS
		$threads = $vbulletin->db->query_read("
			SELECT thread.nodeid, thread.contenttypeid
			FROM " . TABLE_PREFIX . "node AS thread
			LEFT JOIN " . TABLE_PREFIX . "node AS forum ON forum.nodeid = thread.parentid AND forum.contenttypeid = $channelContentType
			WHERE forum.nodeid IS NULL
			AND thread.contenttypeid NOT IN (" . implode(',', $excludeTypes) . ")
			AND thread.starter = thread.nodeid
		");
		while ($thread = $vbulletin->db->fetch_array($threads))
		{
			$threadarray[$thread['nodeid']] = $thread['contenttypeid'];
		}
	}

	echo '<p>' . $vbphrase['removing_orphan_threads'] . '</p>';

	$count = 0;
	foreach ($threadarray AS $nodeid => $contenttypeid)
	{
		echo construct_phrase($vbphrase['processing_x'], $nodeid)."<br />\n";

		$contentLib = vB_Library_Content::getContentLib($contenttypeid);

		//if we cannot delete the type, we won't try.
		if (!$contentLib->getCannotDelete())
		{
			//This may prove fragile if the node data isn't right.  But it's better than
			//what used to be here.
			$contentLib->delete($nodeid);
		}
		vbflush();

		unset($threadarray[$nodeid]);
		$count++;
		if ($count >= $vbulletin->GPC['perpage'])
		{
			break;
		}
	}

	if (empty($threadarray))
	{
		build_adminutil_text('orphanthread', 'done');
	}
	else
	{
		build_adminutil_text('orphanthread', serialize($threadarray));
	}

	$args = [];
	$args['do'] = 'removeorphanthreads';
	$args['pp'] = $vbulletin->GPC['perpage'];
	print_cp_redirect2('admincp/misc.php', $args, 2, '');
}

// ###################### Start remove posts #######################
if ($_REQUEST['do'] == 'removeorphanposts')
{
	if (!$maintainAll)
	{
		print_cp_no_permission();

	}

	if (empty($vbulletin->GPC['perpage']))
	{
		$vbulletin->GPC['perpage'] = 50;
	}

	$finishat = $vbulletin->GPC['startat'] + $vbulletin->GPC['perpage'];

	$topChannelIds = vB_Api::instance('Content_Channel')->fetchTopLevelChannelIds();
	print_stop_message_on_api_error($topChannelIds);

	$excludeTypes = array_keys(vB_Types::instance()->getContentTypeClasses([
		'vBForum_Channel',
		'vBForum_Photo',
		'vBForum_Attach',
		'vBForum_PrivateMessage',
	]));

	$posts = $vbulletin->db->query_read("
		SELECT post.nodeid
		FROM " . TABLE_PREFIX . "node AS post
		INNER JOIN " . TABLE_PREFIX . "closure AS cl ON cl.parent = " . $topChannelIds['forum'] . " AND cl.child = post.nodeid
		LEFT JOIN " . TABLE_PREFIX . "node AS thread ON post.parentid = thread.nodeid AND thread.nodeid = thread.starter
		WHERE thread.nodeid IS NULL
		AND post.nodeid != post.starter
		AND post.parentid = post.starter
		AND post.contenttypeid NOT IN (" . implode(',', $excludeTypes) . ")
		LIMIT " . $vbulletin->GPC['startat'] . ", " . $vbulletin->GPC['perpage'] . "
	");

	$gotsome = false;
	while ($post = $vbulletin->db->fetch_array($posts))
	{
		$result = vB_Api::instance('node')->deleteNodes($post['nodeid']);
		print_stop_message_on_api_error($result);

		echo construct_phrase($vbphrase['processing_x'], $post['postid'])."<br />\n";
		vbflush();
		$gotsome = true;
	}

	if ($gotsome)
	{
		$args = [];
		$args['do'] = 'removeorphanposts';
		$args['startat'] = $finishat;
		$args['pp'] = $vbulletin->GPC['perpage'];
		print_cp_redirect2('admincp/misc.php', $args, 2, '');
	}
	else
	{
		vB_Cache::resetCache();
		$datastore->resetCache();

		print_stop_message2('deleted_orphan_posts_successfully', 'admincp/misc');
	}
}

// ###################### Start remove orphaned stylevars #######################
if ($_REQUEST['do'] == 'removeorphanstylevars')
{
	vB_Library::instance('style')->deleteOrphanStylevars(true);

	echo '<br><br>'; // Just some spacing ...

	// No redirection, so list stays visible on the screen
	print_stop_message2('deleted_orphan_stylevars_successfully');
}

// ###################### Anonymous Survey Code #######################
if ($_REQUEST['do'] == 'survey')
{
	if (!$maintainAll)
	{
		print_cp_no_permission();

	}

	// first we'd like extra phrase groups from the cphome
	// fetch_phrase_group('cphome');

	/*
	All the functions are prefixed with @ to supress errors, this allows us to get feedback from hosts which have almost everything
	useful disabled
	*/

	// What operating system is the webserver running
	$os = @php_uname('s');

	// Using 32bit or 64bit
	$architecture = @php_uname('m');//php_uname('r') . ' ' . php_uname('v') . ' ' . //;

	// Webserver Signature
	$web_server = $_SERVER['SERVER_SOFTWARE'];

	// PHP Web Server Interface
	$sapi_name = @php_sapi_name();

	// If Apache is used, what sort of modules, mod_security?
	if (function_exists('apache_get_modules'))
	{
		$apache_modules = @apache_get_modules();
	}
	else
	{
		$apache_modules = null;
	}

	// Check to see if a recent version is being used
	$php = PHP_VERSION;

	// Check for common PHP Extensions
	$php_extensions = @get_loaded_extensions();

	// Various configuration options regarding PHP
	$php_open_basedir = ((($bd = @ini_get('open_basedir')) AND $bd != '/') ? $vbphrase['on'] : $vbphrase['off']);
	$php_memory_limit = ((function_exists('memory_get_usage') AND ($limit = @ini_get('memory_limit'))) ? htmlspecialchars($limit) : $vbphrase['off']);

	// what version of MySQL
	$mysql = $vbulletin->db->query_first("SELECT VERSION() AS version");
	$mysql = $mysql['version'];

	// Post count
	$posts = $vbulletin->db->query_first("SELECT COUNT(*) AS total FROM " . TABLE_PREFIX . "node");
	$posts = $posts['total'];

	// User Count
	$users = $vbulletin->db->query_first("SELECT COUNT(*) AS total FROM " . TABLE_PREFIX . "user");
	$users = $users['total'];

	// Forum Count
	$forums = 'N/A';

	// Usergroup Count
	$usergroups = $vbulletin->db->query_first("SELECT COUNT(*) AS total FROM " . TABLE_PREFIX . "usergroup");
	$usergroups = $usergroups['total'];

	// First Forum Post
	$firstpost = $vbulletin->db->query_first("SELECT MIN(publishdate) AS firstpost FROM " . TABLE_PREFIX . "node");
	$firstpost = $firstpost['firstpost'];

	// Last upgrade performed
	$lastupgrade = $vbulletin->db->query_first("SELECT MAX(dateline) AS lastdate FROM " . TABLE_PREFIX . "upgradelog");
	$lastupgrade = $lastupgrade['lastdate'];

	// percentage of users not using linear mode
	$nonlinear = 'N/A';

	// character sets in use within all languages
	$charsets_result = $vbulletin->db->query_read("SELECT DISTINCT charset AS charset FROM " . TABLE_PREFIX . "language");
	$charsets = [];
	while ($charset = $vbulletin->db->fetch_array($charsets_result))
	{
		$charset_name = trim(htmlspecialchars($charset['charset']));
		if ($charset_name != '')
		{
			$charsets["$charset_name"] = $charset_name;
		}
	}
	$vbulletin->db->free_result($charsets_result);

	?>
	<style type="text/css">
	.infotable td { font-size: smaller; }
	.infotable tr { vertical-align: top; }
	.hcell { font-weight: bold; white-space: nowrap; width: 200px; }
	</style>
	<form action="https://www.vbulletin.com/survey.p<?php echo ''; ?>hp" method="post">
	<?php

	$apache_modules_html = '';
	if (is_array($apache_modules))
	{
		$apache_modules = array_map('htmlspecialchars', $apache_modules);

		foreach ($apache_modules AS $apache_module)
		{
			$apache_modules_html .= "<input type=\"hidden\" name=\"apache_module[]\" value=\"$apache_module\" />";
		}
	}

	$php_extensions_html = '';
	if (is_array($php_extensions))
	{
		$php_extensions = array_map('htmlspecialchars', $php_extensions);

		foreach ($php_extensions AS $php_extension)
		{
			$php_extensions_html .= "<input type=\"hidden\" name=\"php_extension[]\" value=\"$php_extension\" />";
		}
	}

	$charsets_html = '';
	if (is_array($charsets))
	{
		$charsets = array_map('htmlspecialchars', $charsets);

		foreach ($charsets AS $charset)
		{
			$charsets_html .= "<input type=\"hidden\" name=\"charset[]\" value=\"$charset\" />";
		}
	}

	print_table_start();
	print_table_header($vbphrase['anon_server_survey']);
	print_description_row($vbphrase['anon_server_survey_desc']);
	print_table_header('<img src="images/clear.gif" width="1" height="1" alt="" />');
	print_description_row("
		<table cellpadding=\"0\" cellspacing=\"6\" border=\"0\" class=\"infotable\">
		<tr><td class=\"hcell\">$vbphrase[vbulletin_version_gmaintenance]</td><td>" . $vboptions['templateversion'] . "</td></tr>
		<tr><td class=\"hcell\">$vbphrase[server_type]</td><td>$os</td></tr>
		<tr><td class=\"hcell\">$vbphrase[system_architecture]</td><td>$architecture</td></tr>
		<tr><td class=\"hcell\">$vbphrase[mysql_version]</td><td>$mysql</td></tr>
		<tr><td class=\"hcell\">$vbphrase[web_server]</td><td>$web_server</td></tr>
		<tr><td class=\"hcell\">SAPI</td><td>$sapi_name</td></tr>" . (is_array($apache_modules) ? "
		<tr><td class=\"hcell\">$vbphrase[apache_modules]</td><td>" . implode(', ', $apache_modules) . "</td></tr>" : '') . "
		<tr><td class=\"hcell\">PHP</td><td>$php</td></tr>
		<tr><td class=\"hcell\">$vbphrase[php_extensions]</td><td>" . implode(', ', $php_extensions) . "</td></tr>
		<tr><td class=\"hcell\">$vbphrase[php_memory_limit]</td><td>$php_memory_limit</td></tr>
		<tr><td class=\"hcell\">$vbphrase[php_openbase_dir]</td><td>$php_open_basedir</td></tr>
		<tr><td class=\"hcell\">$vbphrase[character_sets_usage]</td><td>" . implode(', ', $charsets) . "</td></tr>
		</table>");

	print_table_header($vbphrase['optional_info']);

	print_description_row("
		<table cellpadding=\"0\" cellspacing=\"6\" border=\"0\" class=\"infotable\">
		<tr><td class=\"hcell\">$vbphrase[total_posts_gmaintenance]</td><td>
			<label for=\"cb_posts\"><input type=\"checkbox\" name=\"posts\" id=\"cb_posts\" value=\"$posts\" checked=\"checked\" />" . vb_number_format(floatval($posts)) . "</label></td></tr>
		<tr><td class=\"hcell\">$vbphrase[total_users]</td><td>
			<label for=\"cb_users\"><input type=\"checkbox\" name=\"users\" id=\"cb_users\" value=\"$users\" checked=\"checked\" />" . vb_number_format(floatval($users)) . "</label></td></tr>
		<tr><td class=\"hcell\">$vbphrase[threaded_mode_usage]</td><td>
			<label for=\"cb_nonlinear\"><input type=\"checkbox\" name=\"nonlinear\" id=\"cb_nonlinear\" value=\"$nonlinear\" checked=\"checked\" />" . vb_number_format(floatval($nonlinear)) . "%</label></td></tr>
		<tr><td class=\"hcell\">$vbphrase[total_forums]</td><td>
			<label for=\"cb_forums\"><input type=\"checkbox\" name=\"forums\" id=\"cb_forums\" value=\"$forums\" checked=\"checked\" />" . $forums . "</label></td></tr>
		<tr><td class=\"hcell\">$vbphrase[total_usergroups]</td><td>
			<label for=\"cb_usergroups\"><input type=\"checkbox\" name=\"usergroups\" id=\"cb_usergroups\" value=\"$usergroups\" checked=\"checked\" />" . vb_number_format(floatval($usergroups)) . "</label></td></tr>
		" . ($firstpost > 0 ? "<tr><td class=\"hcell\">$vbphrase[first_post_date]</td><td>
			<label for=\"cb_firstpost\"><input type=\"checkbox\" name=\"firstpost\" id=\"cb_firstpost\" value=\"$firstpost\" checked=\"checked\" />" . vbdate($vboptions['dateformat'], $firstpost) . "</label></td></tr>" : '') .
		 	($lastupgrade > 0 ? "<tr><td class=\"hcell\">$vbphrase[last_upgrade_date]</td><td>
			<label for=\"cb_lastupgrade\"><input type=\"checkbox\" name=\"lastupgrade\" id=\"cb_lastupgrade\" value=\"$lastupgrade\" checked=\"checked\" />" . vbdate($vboptions['dateformat'], $lastupgrade) . "</label></td></tr>" : '') . "
		</table>
		<input type=\"hidden\" name=\"vbversion\" value=\"" . SIMPLE_VERSION . "\" />
		<input type=\"hidden\" name=\"os\" value=\"$os\" />
		<input type=\"hidden\" name=\"architecture\" value=\"$architecture\" />
		<input type=\"hidden\" name=\"mysql\" value=\"$mysql\" />
		<input type=\"hidden\" name=\"web_server\" value=\"$web_server\" />
		<input type=\"hidden\" name=\"sapi_name\" value=\"$sapi_name\" />
			$apache_modules_html
		<input type=\"hidden\" name=\"php\" value=\"$php\" />
			$php_extensions_html
		<input type=\"hidden\" name=\"php_memory_limit\" value=\"$php_memory_limit\" />
		<input type=\"hidden\" name=\"php_open_basedir\" value=\"$php_open_basedir\" />
			$charsets_html
	");
	print_submit_row($vbphrase['send_info'], '');
	print_table_footer();
}

// ###################### Start user choices #######################
if ($_REQUEST['do'] == 'chooser')
{
	if (!$maintainAll AND !vB::getUserContext()->hasAdminPermission('canadminmaintain'))
	{
		print_cp_no_permission();
	}

	$vb5_config = vB::getConfig();

	print_form_header('admincp/misc', 'updateuser');
	print_table_header($vbphrase['update_user_titles'], 2, 0);
	print_input_row($vbphrase['number_of_users_to_process_per_cycle_gmaintenance'], 'perpage', 1000);
	print_submit_row($vbphrase['update_user_titles']);

	print_form_header('admincp/misc', 'promoteuser');
	print_table_header($vbphrase['usergroup_promotions'], 2, 0);
	print_description_row($vbphrase['usergroup_promotions_warning']);
	print_input_row($vbphrase['number_of_users_to_process_per_cycle_gmaintenance'], 'perpage', 1000);
	print_submit_row($vbphrase['promote_users']);

	print_form_header('admincp/misc', 'updatethread');
	print_table_header($vbphrase['rebuild_thread_information'], 2, 0);
	print_input_row($vbphrase['number_of_threads_to_process_per_cycle'], 'perpage', 2000);
	print_submit_row($vbphrase['rebuild_thread_information']);

	print_form_header('admincp/misc', 'updateforum');
	print_table_header($vbphrase['rebuild_forum_information'], 2, 0);
	print_input_row($vbphrase['number_of_forums_to_process_per_cycle'], 'perpage', 100);
	print_submit_row($vbphrase['rebuild_forum_information']);

	print_form_header('admincp/misc', 'lostusers');
	print_table_header($vbphrase['fix_broken_user_profiles']);
	print_description_row($vbphrase['finds_users_without_complete_entries']);
	print_submit_row($vbphrase['fix_broken_user_profiles'],NULL);

	if ($maintainAll)
	{
		print_form_header('admincp/misc', 'doindextypes');
		print_table_header($vbphrase['rebuild_search_index'], 2, 0);
		print_description_row($vbphrase['note_reindexing_empty_indexes_x']);
		//don't use array_merge, it will (incorrectly) assume that the keys are index values
		//instead of meaningful numeric keys and renumber them.
		$channelTypes = vB_Channel::getChannelTypes();
		$types = array ( 0 => $vbphrase['all']);
		foreach ($channelTypes as $nodeId => $type)
		{
			$types[$nodeId] = $vbphrase[$type['label']];
		}

		print_select_row($vbphrase['search_content_type_to_index'], 'indextypes', $types);
		print_input_row($vbphrase['search_items_batch'], 'perpage', 250);
		print_input_row($vbphrase['search_start_item_id'], 'initialnodeid', 0);
		print_yes_no_row($vbphrase['include_automatic_javascript_redirect'], 'autoredirect', 1);
		print_description_row($vbphrase['note_server_intensive']);
		print_submit_row($vbphrase['rebuild_search_index']);
	}

	if ($maintainAll)
	{
		print_form_header('admincp/misc', 'buildstats');
		print_table_header($vbphrase['rebuild_statistics'], 2, 0);
		print_description_row($vbphrase['rebuild_statistics_warning']);
		print_submit_row($vbphrase['rebuild_statistics'],NULL);

		print_form_header('admincp/misc', 'removedupe');
		print_table_header($vbphrase['delete_duplicate_threads'], 2, 0);
		print_description_row($vbphrase['note_duplicate_threads_have_same']);
		print_input_row($vbphrase['number_of_threads_to_process_per_cycle'], 'perpage', 500);
		print_submit_row($vbphrase['delete_duplicate_threads']);


/*
	//this was removed for VBV-16739.  This feature has been requested to be restored
	//in VBV-13558 at which point this will be needed again.
		print_form_header('admincp/misc', 'rebuildadminavatars');
		print_table_header($vbphrase['rebuild_avatar_thumbnails'], 2, 0);
		//print_description_row($vbphrase['function_rebuilds_avatars']);
		print_input_row($vbphrase['number_of_avatars_to_process_per_cycle'], 'perpage', 25);
		print_yes_no_row($vbphrase['include_automatic_javascript_redirect'], 'autoredirect', 1);
		print_submit_row($vbphrase['rebuild_avatar_thumbnails']);
 */
	}

	print_form_header('admincp/misc', 'rebuildavatars');
	print_table_header($vbphrase['rebuild_custom_avatar_thumbnails'], 2, 0);
	//print_description_row($vbphrase['function_rebuilds_avatars']);
	print_input_row($vbphrase['number_of_avatars_to_process_per_cycle'], 'perpage', 25);
	print_yes_no_row($vbphrase['include_automatic_javascript_redirect'], 'autoredirect', 1);
	print_submit_row($vbphrase['rebuild_custom_avatar_thumbnails']);

	print_form_header('admincp/misc', 'rebuildreputation');
	print_table_header($vbphrase['rebuild_user_reputation'], 2, 0);
	print_description_row($vbphrase['function_rebuilds_reputation']);
	print_input_row($vbphrase['reputation_base'], 'reputation_base', $vboptions['reputationdefault']);
	print_submit_row($vbphrase['rebuild_user_reputation']);

	print_form_header('admincp/misc', 'updateusernames');
	print_table_header($vbphrase['update_usernames']);
	print_input_row($vbphrase['number_of_users_to_process_per_cycle_gmaintenance'], 'perpage', 1000);
	print_submit_row($vbphrase['update_usernames']);

	print_form_header('admincp/misc', 'resetdisplaynames');
	print_table_header($vbphrase['reset_displaynames']);
	print_description_row($vbphrase['function_resets_displaynames']);
	print_input_row($vbphrase['number_of_users_to_process_per_cycle_gmaintenance'], 'perpage', 1000);
	print_submit_row($vbphrase['reset_displaynames']);

	print_form_header('admincp/misc', 'updateposts');
	print_table_header($vbphrase['update_post_counts'], 2, 0);
	print_description_row($vbphrase['recalculate_users_post_counts_warning']);
	print_input_row($vbphrase['number_of_users_to_process_per_cycle_gmaintenance'], 'perpage', 1000);
	print_submit_row($vbphrase['update_post_counts']);

	if (vB::getUserContext()->hasAdminPermission('canadminusers'))
	{
		print_form_header('admincp/misc', 'updatepmtotals');
		print_table_header($vbphrase['update_pm_counts'], 2, 0);
		print_description_row($vbphrase['recalculate_users_pm_counts']);
		print_input_row($vbphrase['number_of_users_to_process_per_cycle_gmaintenance'], 'perpage', 1000);
		print_submit_row($vbphrase['update_pm_counts']);
	}

	if (vB::getUserContext()->hasAdminPermission('canadmintemplates') OR
		vB::getUserContext()->hasAdminPermission('canadminstyles'))
	{
		print_form_header('admincp/misc', 'rebuildstyles');
		print_table_header($vbphrase['rebuild_styles'], 2, 0, 'style');
		print_description_row($vbphrase['function_allows_rebuild_all_style_info']);
		print_yes_no_row($vbphrase['check_styles_no_parent'], 'install', 1);
		print_submit_row($vbphrase['rebuild_styles'], 0);
	}

	if ($maintainAll)
	{
		build_adminutil_text('orphanthread');
		print_form_header('admincp/misc', 'removeorphanthreads');
		print_table_header($vbphrase['remove_orphan_threads']);
		print_description_row($vbphrase['function_removes_orphan_threads']);
		print_input_row($vbphrase['number_of_threads_to_process_per_cycle'], 'perpage', 50);
		print_submit_row($vbphrase['remove_orphan_threads']);

		print_form_header('admincp/misc', 'removeorphanposts');
		print_table_header($vbphrase['remove_orphan_posts']);
		print_description_row($vbphrase['function_removes_orphan_posts']);
		print_input_row($vbphrase['number_of_posts_to_process_per_cycle'], 'perpage', 50);
		print_submit_row($vbphrase['remove_orphan_posts']);

		print_form_header('admincp/misc', 'removeorphanstylevars');
		print_table_header($vbphrase['remove_orphan_stylevars']);
		print_description_row($vbphrase['function_removes_orphan_stylevars']);
		print_submit_row($vbphrase['remove_orphan_stylevars'], 0);

		//we don't want this laying around unless the safeties are *off*
		if ($vb5_config['Misc']['debug'])
		{
			print_form_header('admincp/misc', 'invalidatepasswords');
			print_table_header($vbphrase['invalidate_passwords'], 2, 0);
			print_description_row($vbphrase['function_invalidates_passwords']);
			print_input_row($vbphrase['number_of_users_to_process_per_cycle_gmaintenance'], 'perpage', 50000);
			print_submit_row($vbphrase['invalidate_passwords']);
		}

		print_form_header('admincp/misc', 'invalidateemailunsub');
		print_table_header($vbphrase['invalidate_unsubhash']);
		print_description_row($vbphrase['function_resets_unsubhash']);
		print_input_row($vbphrase['username'], 'username');
		print_yes_no_row($vbphrase['process_all_users'], 'process_all', 0);
		print_submit_row($vbphrase['invalidate_unsubhash']);
	}
}


print_cp_footer();

//should be considered private to this file
function check_for_more_users($assertor, $finishat)
{
	$checkmore = $assertor->getRow('user', [
		vB_dB_Query::CONDITIONS_KEY => [
		['field' => 'userid', 'value' => $finishat, 'operator' =>  vB_dB_Query::OPERATOR_GTE],
		],
		vB_dB_Query::COLUMNS_KEY => ['userid']
	]);

	return boolval($checkmore);
}
/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 116407 $
|| #######################################################################
\*=========================================================================*/
