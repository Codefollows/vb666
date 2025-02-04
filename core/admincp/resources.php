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

// ######################## SET PHP ENVIRONMENT ###########################

// ##################### DEFINE IMPORTANT CONSTANTS #######################
define('CVS_REVISION', '$RCSfile$ - $Revision: 111006 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
global $phrasegroups, $specialtemplates, $vbphrase, $vbulletin;
$phrasegroups = array('cppermission');
$specialtemplates = array();

// ########################## REQUIRE BACK-END ############################
require_once(dirname(__FILE__) . '/global.php');

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!can_administer('canadminforums'))
{
	print_cp_no_permission();
}

$vbulletin->input->clean_array_gpc('r', array(
	'userid'      => vB_Cleaner::TYPE_INT,
	'usergroupid' => vB_Cleaner::TYPE_INT,
	'nodeid'     => vB_Cleaner::TYPE_INT,
));

// ############################# LOG ACTION ###############################
log_admin_action(iif($vbulletin->GPC['userid'], "user id = " . $vbulletin->GPC['userid'], iif($vbulletin->GPC['usergroupid'], "usergroup id = " . $vbulletin->GPC['usergroupid'], iif($vbulletin->GPC['nodeid'], "node id = " . $vbulletin->GPC['nodeid']))));

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

print_cp_header($vbphrase['view_permissions_gcppermission']);

$perm_phrase = array(
	'canview'                => $vbphrase['can_view_forum'],
	'canviewthreads'         => $vbphrase['can_view_threads'],
	'canviewothers'          => $vbphrase['can_view_others_threads'],
	'cansearch'              => $vbphrase['can_search_forum'],
	'canemail'               => $vbphrase['can_use_email_to_friend'],
	'canpostnew'             => $vbphrase['can_post_threads'],
	'canreply'          	 => $vbphrase['can_reply_to_threads'],
	'caneditpost'            => $vbphrase['can_edit_own_posts'],
	'candeletepost'          => $vbphrase['can_delete_own_posts'],
	'candeletethread'        => $vbphrase['can_delete_own_threads'],
	'canopenclose'           => $vbphrase['can_open_close_own_threads'],
	'canmove'                => $vbphrase['can_move_own_threads'],
	'cangetattachment'       => $vbphrase['can_view_attachments'],
	'cangetimgattachment'    => $vbphrase['can_view_image_attachments'],
	'canseethumbnails'       => $vbphrase['can_see_thumbnails'],
	'canpostattachment'      => $vbphrase['can_post_attachments'],
	'canpostpoll'            => $vbphrase['can_post_polls'],
	'canvote'                => $vbphrase['can_vote_on_polls'],
	'canthreadrate'	         => $vbphrase['can_rate_threads'],
	'canseedelnotice'        => $vbphrase['can_see_deletion_notices'],
	'cantagown'              => $vbphrase['can_tag_own_threads'],
	'cantagothers'           => $vbphrase['can_tag_others_threads'],
	'candeletetagown'        => $vbphrase['can_delete_tags_own_threads'],
	'canconfigchannel'       => $vbphrase['can_configure_channel'],
	'canusehtml'             => $vbphrase['can_use_html'],
	'canpublish'             => $vbphrase['can_publish'],
	'cancreateblog'          => $vbphrase['can_create_blog'],
	'canjoin'                => $vbphrase['can_join'],
	'canuploadchannelicon'	 => $vbphrase['can_upload_channel_icons'],
	'cananimatedchannelicon' => $vbphrase['can_use_animated_channel_icon'],
	'cancomment'        	 => $vbphrase['can_comment'],
	'caneditothers'          => $vbphrase['can_edit_others_content'],
	'candeleteothers'        => $vbphrase['can_delete_others_content'],
	'canattachmentcss'       => $vbphrase['can_css_attachments'],
);

//build a nice array with permission names
foreach ($vbulletin->bf_ugp_forumpermissions AS $key => $val)
{
	$bitfieldnames["$val"] = $perm_phrase["$key"];
}

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'index';
}

// ###################### Start index ########################
if ($_REQUEST['do'] == 'index')
{
	print_form_header('admincp/resources', 'view');
	print_table_header($vbphrase['view_forum_permissions_gcppermission']);
	print_channel_chooser($vbphrase['channel'], 'nodeid', 0, "($vbphrase[channel])");
	print_chooser_row($vbphrase['usergroup'], 'usergroupid', 'usergroup', '', "($vbphrase[usergroup])");
	print_label_row(
		$vbphrase['channel_permissions_gcppermission'],
		'<label for="cb_checkall"><input type="checkbox" id="cb_checkall" name="allbox" onclick="js_check_all(this.form)" />' . $vbphrase['check_all'] . '</label>',
		'thead'
	);
	foreach ($vbulletin->bf_ugp_forumpermissions AS $field => $value)
	{
		print_checkbox_row($perm_phrase["$field"], "checkperm[$value]", false, $value);
	}
	print_submit_row($vbphrase['find']);

}

// ###################### Start viewing resources for forums or usergroups ########################
if ($_REQUEST['do'] == 'view')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'checkperm' => vB_Cleaner::TYPE_ARRAY_INT,
	));
	if ($vbulletin->GPC['nodeid'] <= 0 AND $vbulletin->GPC['usergroupid'] <= 0)
	{
		print_stop_message2('you_must_pick_a_usergroup_or_channel_to_check_permissions');
	}
	if (empty($vbulletin->GPC['checkperm']))
	{
		$vbulletin->GPC['checkperm'][] = 1;
	}

	$assertor = vB::getDbAssertor();

	$params = array();
	if ($vbulletin->GPC['usergroupid'] > 0)
	{
		$params['usergroupid'] = $vbulletin->GPC['usergroupid'];
	}

	if ($vbulletin->GPC['nodeid'] > 0)
	{
		$params['nodeid'] = $vbulletin->GPC['nodeid'];
	}

	$result = $assertor->assertQuery('fetchPermsOrdered', $params);
	$fpermscache = array();
	$titlecache = array();
	foreach ($result as $permission)
	{
		$fpermscache["$permission[nodeid]"]["$permission[groupid]"] = intval($permission['forumpermissions']);
		$titlecache["$permission[nodeid]"] = $permission['htmltitle'];
	}

	$params = array(
			vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
	);
	if ($vbulletin->GPC['usergroupid'] > 0)
	{
		$params[vB_dB_Query::CONDITIONS_KEY] = array(
				array('field'=>'usergroupid',	'value'=>$vbulletin->GPC['usergroupid'], 'operator' => vB_dB_Query::OPERATOR_EQ)
		);
	}
	$result = $assertor->assertQuery('usergroup', $params);
	$usergrouptitlecache = array();
	foreach ($result as $usergroup)
	{
		$usergrouptitlecache["$usergroup[usergroupid]"] = $usergroup['title'];
		$vbulletin->usergroupcache["$usergroup[usergroupid]"] = $usergroup;
	}

	foreach($fpermscache AS $snodeid => $fpermissions)
	{
		if ($vbulletin->GPC['usergroupid'] <= 0)
		{
			foreach ($vbulletin->usergroupcache AS $pusergroupid => $usergroup)
			{
				$perms["$snodeid"]["$pusergroupid"] = 0;
				if (isset($fpermissions["$pusergroupid"]))
				{
					$perms["$snodeid"]["$pusergroupid"] |= $fpermissions["$pusergroupid"];
				}
				else
				{
					$perms["$snodeid"]["$pusergroupid"] |= $vbulletin->usergroupcache["$pusergroupid"]['forumpermissions'];
				}
			}
		}
		else
		{
			$perms["$snodeid"]["{$vbulletin->GPC['usergroupid']}"] = 0;
			if (isset($fpermissions["{$vbulletin->GPC['usergroupid']}"]))
			{
				$perms["$snodeid"]["{$vbulletin->GPC['usergroupid']}"] |= $fpermissions["{$vbulletin->GPC['usergroupid']}"];
			}
			else
			{
				$perms["$snodeid"]["{$vbulletin->GPC['usergroupid']}"] |= $vbulletin->usergroupcache["{$vbulletin->GPC['usergroupid']}"]['forumpermissions'];
			}
		}
	}
	//we now have a nice $perms array with the nodeid as the index, lets look at the users original request
	//did they want all nodes for a usergroup or all perms for a node or just a specific one
	print_form_header('admincp/', '');
	if ($vbulletin->GPC['nodeid'] <= 0)
	{
		print_table_header($usergrouptitlecache["{$vbulletin->GPC['usergroupid']}"] . " <span class=\"normal\">(usergroupid: " . $vbulletin->GPC['usergroupid'] . ")</span>");
		foreach ($perms AS $snodeid => $usergroup)
		{
			print_table_header($titlecache["$snodeid"] . " <span class=\"normal\">(nodeid: $snodeid)</span>");
			foreach ($vbulletin->GPC['checkperm'] AS $key => $val)
			{
				if (bitwise($usergroup["{$vbulletin->GPC['usergroupid']}"], $val))
				{
					print_label_row($bitfieldnames["$val"], '<b>' . $vbphrase['yes'] . '</b>');
				}
				else
				{
					print_label_row($bitfieldnames["$val"], '<b>' . $vbphrase['no'] . '</b>');
				}
			}
		}
	}
	else if ($vbulletin->GPC['usergroupid'] <= 0)
	{
		ksort($perms["{$vbulletin->GPC['nodeid']}"], SORT_NUMERIC);
		print_table_header($titlecache["{$vbulletin->GPC['nodeid']}"] . " <span class=\"normal\">(nodeid: " . $vbulletin->GPC['nodeid'] . ")</span>");
		//nodeid was set so show permissions for all usergroups on that node
		foreach ($perms["{$vbulletin->GPC['nodeid']}"] AS $_usergroupid => $usergroup)
		{
			print_table_header($usergrouptitlecache["$_usergroupid"] . " <span class=\"normal\">(usergroupid: $_usergroupid)</span>");
			foreach ($vbulletin->GPC['checkperm'] AS $key => $val)
			{
				if (bitwise($usergroup, $val))
				{
					print_label_row($bitfieldnames["$val"], '<b>' . $vbphrase['yes'] . '</b>');
				}
				else
				{
					print_label_row($bitfieldnames["$val"], '<b>' . $vbphrase['no'] . '</b>');
				}
			}
		}
	}
	else
	{
		print_table_header($usergrouptitlecache["{$vbulletin->GPC['usergroupid']}"] . ' / ' . $titlecache["{$vbulletin->GPC['nodeid']}"]);
		foreach ($vbulletin->GPC['checkperm'] AS $key => $val)
		{
			if (bitwise($perms["{$vbulletin->GPC['nodeid']}"]["{$vbulletin->GPC['usergroupid']}"], $val))
			{
				print_label_row($bitfieldnames["$val"], '<b>' . $vbphrase['yes'] . '</b>');
			}
			else
			{
				print_label_row($bitfieldnames["$val"], '<b>' . $vbphrase['no'] . '</b>');
			}
		}
	}
	print_table_footer();
}

// ###################### Start viewing resources for specific user ########################
if ($_REQUEST['do'] == 'viewuser')
{
	$userinfo = fetch_userinfo($vbulletin->GPC['userid']);
	$usercontext = vB::getUserContext($vbulletin->GPC['userid']);
	if (!$userinfo /*OR !$usercontext*/)
	{
		print_stop_message2('invalid_user_specified');
	}

	print_form_header('admincp/', '');
	print_table_header($userinfo['username'] . " <span class=\"normal\">(userid: $userinfo[userid])</span>");
	$channels = vB_Api::instanceInternal('search')->getChannels(true);
	foreach ($channels AS $nodeid => $channel)
	{
		print_table_header($channel['htmltitle'] . " <span class=\"normal\">(nodeid: $nodeid)</span>");
		foreach ($vbulletin->bf_ugp_forumpermissions AS $key => $val)
		{
			$yes = $usercontext->getChannelPermission('forumpermissions', $key, $nodeid);
			print_label_row($bitfieldnames[$val], '<b>' . $vbphrase[($yes ? 'yes' : 'no')] . '</b>');
		}
	}

	print_table_footer();
}

function bitwise($value, $bitfield)
{
	//old comment says to not return true/false.  Don't think there is a current reason.
	return (intval($value) & $bitfield ? 1 : 0);
}

print_cp_footer();

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 111006 $
|| #######################################################################
\*=========================================================================*/
