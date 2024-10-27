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
define('CVS_REVISION', '$RCSfile$ - $Revision: 116252 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
global $phrasegroups, $specialtemplates, $vbphrase, $tableadded, $vbulletin;
$phrasegroups = ['cpuser', 'forum', 'timezone', 'user', 'cprofilefield', 'subscription', 'banning', 'profilefield'];
$specialtemplates = [];

// ########################## REQUIRE BACK-END ############################
require_once(dirname(__FILE__) . '/global.php');
require_once(DIR . '/includes/adminfunctions_profilefield.php');
require_once(DIR . '/includes/adminfunctions_user.php');

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!can_administer('canadminusers'))
{
	print_cp_no_permission();
}

// ############################# LOG ACTION ###############################
$vbulletin->input->clean_array_gpc('r', array(
	'userid' => vB_Cleaner::TYPE_INT
));
log_admin_action(($vbulletin->GPC['userid'] != 0 ? 'user id = ' . $vbulletin->GPC['userid'] : ''));
// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

$vb5_config =& vB::getConfig();
$vboptions = vB::getDatastore()->getValue('options');

// #############################################################################
// put this before print_cp_header() so we can use an HTTP header
if ($_REQUEST['do'] == 'find')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'user'              => vB_Cleaner::TYPE_ARRAY,
		'profile'           => vB_Cleaner::TYPE_ARRAY,
		'display'           => vB_Cleaner::TYPE_ARRAY_BOOL,
		'orderby'           => vB_Cleaner::TYPE_STR,
		'limitstart'        => vB_Cleaner::TYPE_UINT,
		'limitnumber'       => vB_Cleaner::TYPE_UINT,
		'direction'         => vB_Cleaner::TYPE_STR,
		'serializedprofile' => vB_Cleaner::TYPE_STR,
		'serializeduser'    => vB_Cleaner::TYPE_STR,
		'serializeddisplay' => vB_Cleaner::TYPE_STR
	));

	if (!empty($vbulletin->GPC['serializeduser']))
	{
		$vbulletin->GPC['user']    = @unserialize(verify_client_string($vbulletin->GPC['serializeduser']));
		$vbulletin->GPC['profile'] = @unserialize(verify_client_string($vbulletin->GPC['serializedprofile']));
	}

	if (!empty($vbulletin->GPC['serializeddisplay']))
	{
		$vbulletin->GPC['display'] = @unserialize(verify_client_string($vbulletin->GPC['serializeddisplay']));
	}

	if (@array_sum($vbulletin->GPC['display']) == 0)
	{
		$vbulletin->GPC['display'] = [
			'username' => 1,
			'displayname' => !empty($vboptions['enabledisplayname']),
			'options' => 1,
			'email' => 1,
			'joindate' => 1,
			'lastactivity' => 1,
			'posts' => 1,
		];
	}

	//the find function will default to 25, but we need the limitnumber below
	//and we don't want the values to get out of sync
	if (empty($vbulletin->GPC['limitnumber']))
	{
		$vbulletin->GPC['limitnumber'] = 25;
	}

	//one base for human readable but the db is zero based
	//however if we aren't passed a limit start we want to
	//assume one, but since setting it one and then decrementing
	//doesn't make a lot of sense, we'll just leave it at 0
	if ($vbulletin->GPC['limitstart'])
	{
		$vbulletin->GPC['limitstart']--;
	}
	$users = vB_Api::instance('User')->find(
		$vbulletin->GPC['user'],
		$vbulletin->GPC['profile'],
		$vbulletin->GPC['orderby'],
		$vbulletin->GPC['direction'],
		$vbulletin->GPC['limitstart'],
		$vbulletin->GPC['limitnumber']
	);

	if (is_array($users) AND isset($users['errors']))
	{
		print_stop_message_array($users['errors']);
	}
	if (empty($users['users']) OR $users['count'] == 0)
	{
		// no users found!
		print_stop_message2('no_users_matched_your_query');
	}
	$countusers = $users['count'];
	if ($users['count'] == 1)
	{
		// show a user if there is just one found
		$user = current($users['users']);
		$args = array();
		$args['do'] = 'edit';
		$args['u'] = $user['userid'];
		// instant redirect
		exec_header_redirect(get_admincp_url('user', $args));
	}

	define('DONEFIND', true);
	$_REQUEST['do'] = 'find2';
}

// #############################################################################

print_cp_header($vbphrase['user_manager'], '', [
	get_admincp_script_tag('vbulletin_user.js'),
	get_admincp_script_tag('vbulletin_paginate.js'),
]);

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'modify';
}

// ###################### Start email password #######################
if ($_REQUEST['do'] == 'emailpassword')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'email'  => vB_Cleaner::TYPE_STR,
		'userid' => vB_Cleaner::TYPE_UINT,
	));

	print_form_header('admincp/user', 'do_emailpassword');
	construct_hidden_code('email', $vbulletin->GPC['email']);
	construct_hidden_code('url', "admincp/user.php?do=find&user[email]=" . urlencode($vbulletin->GPC['email']));
	construct_hidden_code('u', $vbulletin->GPC['userid']);
	print_table_header($vbphrase['email_password_reminder_to_user']);
	print_description_row(construct_phrase($vbphrase['click_the_button_to_send_password_reminder_to_x'], "<i>" . htmlspecialchars_uni($vbulletin->GPC['email']) . "</i>"));
	print_submit_row($vbphrase['send'], 0);
}

// ###################### Start Remove #######################
if ($_REQUEST['do'] == 'remove')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'userid' => vB_Cleaner::TYPE_INT
	));

	$extratext = $vbphrase['all_posts_will_be_set_to_guest'];

	// find out if the user has social groups
	$groups = vB_Api::instanceInternal('socialgroup')->getSGInfo(array('userid' => $vbulletin->GPC['userid']));

	if ($groups['totalcount'])
	{
		$extratext .= "<br /><br />" . construct_phrase($vbphrase['delete_user_transfer_social_groups'], $groups['totalcount']) . " <input type=\"checkbox\" name=\"transfer_groups\" value=\"1\" />";
	}

	print_delete_confirmation('user', $vbulletin->GPC['userid'], 'user', 'kill', 'user', '', $extratext);
	$pruneurl =  "admincp/nodetools.php?do=pruneuser&channelid=-1&u=" . $vbulletin->GPC['userid'];
	echo '<p align="center">' . construct_phrase($vbphrase['if_you_want_to_prune_user_posts_first'], htmlspecialchars($pruneurl)). '</p>';
}

// ###################### Start Kill #######################
if ($_POST['do'] == 'kill')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'userid' => vB_Cleaner::TYPE_INT,
		'transfer_groups' => vB_Cleaner::TYPE_BOOL
	));

	// check user is not set in the $undeletable users string
	if (is_unalterable_user($vbulletin->GPC['userid']))
	{
		print_stop_message2('user_is_protected_from_alteration_by_undeletableusers_var');
	}
	else
	{
		$info = vB_User::fetchUserinfo($vbulletin->GPC['userid']);
		if (!$info)
		{
			print_stop_message2('invalid_user_specified');
		}

		vB_Api::instanceInternal('user')->delete($vbulletin->GPC['userid'], $vbulletin->GPC['transfer_groups']);

		print_stop_message2('deleted_user_successfully', 'user', array('do'=>'modify'));
	}
}

// ###################### Start edit #######################
if ($_REQUEST['do'] == 'edit' OR $_REQUEST['do'] == 'add')
{
	$OUTERTABLEWIDTH = '100%';
	$INNERTABLEWIDTH = '100%';

	$vbulletin->input->clean_array_gpc('r', [
		'userid' => vB_Cleaner::TYPE_UINT
	]);

	$edituserid = $vbulletin->GPC['userid'];

	$assertor = vB::getDbAssertor();
	$datastore = vB::getDatastore();
	$userLib = vB_Library::instance('user');

	if ($edituserid)
	{
		$userApi = vB_Api::instance('user');
		$user = $userApi->fetchProfileInfo($edituserid);
		print_stop_message_on_api_error($user);
		if (!$user)
		{
			print_stop_message2('invalid_user_specified');
		}

		// VBV-11898 modified vB_User::fetchUserinfo() such that if 'allowmembergroups' is set to No,
		// the user's membergroupids are set to an empty string. As such, let's check the option & grab
		// membergroupids from vB_Library_User->fetchUserGroups()
		$bf_ugp_genericoptions = $datastore->getValue('bf_ugp_genericoptions');
		$usergroupCache = $datastore->getValue('usergroupcache');
		if (!($usergroupCache[$user['usergroupid']]['genericoptions'] & $bf_ugp_genericoptions['allowmembergroups']))
		{
			$groups = $userLib->fetchUserGroups($user['userid']);
			$user['membergroupids'] = implode(',', $groups['secondary']);
		}

		$user = array_merge($user, convert_bits_to_array($user['options'], $vbulletin->bf_misc_useroptions));
		$user = array_merge($user, convert_bits_to_array($user['adminoptions'], $vbulletin->bf_misc_adminoptions));

		if ($user['coppauser'] == 1)
		{
			echo "<p align=\"center\"><b>$vbphrase[this_is_a_coppa_user_do_not_change_to_registered]</b></p>\n";
		}

		if ($user['usergroupid'] == 3)
		{
			print_form_header('admincp/user', 'emailcode', 0, 0);
			construct_hidden_code('email', $user['email']);
			construct_hidden_code('userid', $user['userid']);
			print_submit_row($vbphrase['email_activation_codes'], 0);
		}

		// make array for quick links menu
		$quicklinks = [
			"admincp/resources.php?do=viewuser&u=" . $user['userid'] => $vbphrase['view_forum_permissions_gcpuser'],
			"mailto:$user[email]"	=> $vbphrase['send_email_to_user']
		];

		if ($user['usergroupid'] == 3)
		{
			$url = 'admincp/user.php?do=emailcode&email=' . urlencode(unhtmlspecialchars($user['email'])) . '&userid=' . $user['userid'];
			$quicklinks[$url] = $vbphrase['email_activation_codes'];
		}

		require_once(DIR . '/includes/class_paid_subscription.php');
		$subobj = new vB_PaidSubscription();
		$subobj->cache_user_subscriptions();
		if (!empty($subobj->subscriptioncache))
		{
			$quicklinks["admincp/subscriptions.php?do=adjust&amp;userid=" . $user['userid']] = $vbphrase['add_paid_subscription'];
		}

		$url = "admincp/user.php?do=emailpassword&amp;u=" . $user['userid'] . "&amp;email=" . urlencode(unhtmlspecialchars($user['email']));
		$quicklinks[$url] = $vbphrase['email_password_reminder_to_user'];

		try
		{
			$url = vB5_Route::buildUrl('privatemessage|fullurl', ['action' => 'new', 'userid' => $user['userid']]);
			$quicklinks[$url] = $vbphrase['send_private_message_to_user'];
		}
		catch(vB_Exception_Api $e)
		{
			//if we can't generate the route, then simply skip showing this option
		}

		$url = htmlspecialchars('admincp/usertools.php?do=pmfolderstats&u=' . $user['userid']);
		$quicklinks[$url] = $vbphrase['private_message_statistics_gcpuser'];

		$url = htmlspecialchars('admincp/usertools.php?do=removepms&u=' . $user['userid']);
		$quicklinks[$url] = $vbphrase['delete_all_users_private_messages'];

		$url = htmlspecialchars('admincp/usertools.php?do=removesentpms&u=' . $user['userid']);
		$quicklinks[$url] = $vbphrase['delete_private_messages_sent_by_user'];

		$url = htmlspecialchars('admincp/usertools.php?do=removesentvms&u=' . $user['userid']);
		$quicklinks[$url] = $vbphrase['delete_visitor_messages_sent_by_user'];

		$url = htmlspecialchars('admincp/usertools.php?do=removesubs&u=' . $user['userid']);
		$quicklinks[$url] = $vbphrase['delete_subscriptions'];

		$url = htmlspecialchars('admincp/usertools.php?do=removetagassociations&u=' . $user['userid']);
		$quicklinks[$url] = $vbphrase['delete_tagassociations'];

		$url = htmlspecialchars('admincp/usertools.php?do=doips&u=' . $user['userid'] . '&hash=' . CP_SESSIONHASH);
		$quicklinks[$url] = $vbphrase['view_ip_addresses'];

		$url = vB5_Route::buildUrl('profile|fullurl', $user);
		$quicklinks[$url] = $vbphrase['view_profile'];

		$url = vB5_Route::buildUrl('search|fullurl', array(), array('searchJSON' => json_encode(array('authorid' => $user['userid']))));
		$quicklinks[$url] = $vbphrase['find_posts_by_user'];

		$timeNow = vB::getRequest()->getTimeNow();
		$url = 'admincp/admininfraction.php?do=dolist&amp;startstamp=1&amp;endstamp= ' . $timeNow .
			'&amp;infractionlevelid=-1&amp;u=' . $user['userid'];
		$quicklinks[$url] = $vbphrase['view_infractions_gcpuser'];

		$url = 'modcp/banning.php?do=banuser&amp;u=' . $user['userid'];
		$quicklinks[$url] = $vbphrase['ban_user_gcpuser'];

		$url = 'admincp/user.php?do=remove&u=' . $user['userid'];
		$quicklinks[$url] = $vbphrase['delete_user'];

		$url = 	'admincp/socialgroups.php?do=groupsby&u=' . $user['userid'];
		$quicklinks[$url] = $vbphrase['view_social_groups_created_by_user'];

		if (
			vB::getUserContext($user['userid'])->hasAdminPermission('cancontrolpanel') AND
			vB::getUserContext()->isSuperAdmin()
		)
		{
			$quicklinks["admincp/adminpermissions.php?do=edit&u=" . $user['userid']] = $vbphrase['edit_administrator_permissions'];
		}

		$result = $userApi->isMfaEnabled($user['userid']);
		if (isset($result['errors']))
		{
			print_stop_message_array($result['errors']);
		}

		if ($result['enabled'])
		{
			$url = 'admincp/usertools.php?do=resetmfa&u=' . $user['userid'];
			$quicklinks[$url] = $vbphrase['reset_mfa'];
		}

		// don't check 'canreferusers' because the target user could have had the
		// permission previously, etc. and could have referrals because of that
		$quicklinks['admincp/usertools.php?do=showreferrals&amp;referrerid=' . $user['userid']] = $vbphrase['view_referrals'];

		$userfield = $assertor->getRow('vBForum:userfield', array('userid' => $user['userid']));
	}
	else
	{
		$user = user_getdefaultuserdata($datastore);
		$userfield = '';
	}

	// get threaded mode options
	if ($user['threadedmode'] == 1 OR $user['threadedmode'] == 2)
	{
		$threaddisplaymode = $user['threadedmode'];
	}
	else
	{
		if ($user['postorder'] == 0)
		{
			$threaddisplaymode = 0;
		}
		else
		{
			$threaddisplaymode = 3;
		}
	}
	$user['threadedmode'] = $threaddisplaymode;

	// make array for daysprune menu
	$pruneoptions = [
		'0'   => '- ' . $vbphrase['use_forum_default'] . ' -',
		'1'   => $vbphrase['show_threads_from_last_day'],
		'2'   => construct_phrase($vbphrase['show_threads_from_last_x_days'], 2),
		'7'   => $vbphrase['show_threads_from_last_week'],
		'10'  => construct_phrase($vbphrase['show_threads_from_last_x_days'], 10),
		'14'  => construct_phrase($vbphrase['show_threads_from_last_x_weeks'], 2),
		'30'  => $vbphrase['show_threads_from_last_month'],
		'45'  => construct_phrase($vbphrase['show_threads_from_last_x_days'], 45),
		'60'  => construct_phrase($vbphrase['show_threads_from_last_x_months'], 2),
		'75'  => construct_phrase($vbphrase['show_threads_from_last_x_days'], 75),
		'100' => construct_phrase($vbphrase['show_threads_from_last_x_days'], 100),
		'365' => $vbphrase['show_threads_from_last_year'],
		'-1'  => $vbphrase['show_all_threads_guser']
	];
	if ($pruneoptions["$user[daysprune]"] == '')
	{
		$pruneoptions["$user[daysprune]"] = construct_phrase($vbphrase['show_threads_from_last_x_days'], $user['daysprune']);
	}

	// start main table
	print_form_header('admincp/user', 'update', 0, 0);
	?>
	<table cellpadding="0" cellspacing="0" border="0" width="<?php echo $OUTERTABLEWIDTH; ?>" align="center"><tr valign="top"><td>
	<table cellpadding="4" cellspacing="0" border="0" align="center" width="100%" class="tborder">
	<?php

	construct_hidden_code('userid', $edituserid);
	construct_hidden_code('ousergroupid', $user['usergroupid']);
	construct_hidden_code('odisplaygroupid', $user['displaygroupid']);

	$haschangehistory = false;

	if ($edituserid)
	{
		// QUICK LINKS SECTION
		print_table_header(construct_phrase($vbphrase['x_y_id_z'], $vbphrase['user'], $user['username'], $edituserid));

		print_label_row($vbphrase['quick_user_links'], construct_jump_control('uql', $edituserid,  $vbphrase['go'], $quicklinks));

		print_table_break('', $INNERTABLEWIDTH);

		require_once(DIR . '/includes/class_userchangelog.php');

		$userchangelog = new vB_UserChangeLog();

		// get the user change list
		$userchange_list = $userchangelog->sql_select_by_userid($edituserid);
		$haschangehistory = count($userchange_list) ? true : false;
	}

	// PROFILE SECTION
	unset($user['token']);
	unset($user['scheme']);
	construct_hidden_code('olduser', sign_client_string(serialize($user))); //For consistent Edits

	print_table_header($vbphrase['profile_guser'] . ($haschangehistory ? '<span class="smallfont">' .
		construct_link_code($vbphrase['view_change_history'], 'user.php?do=changehistory&amp;userid=' . $edituserid)  . '</span>' : ''));
	print_input_row($vbphrase['username'], 'user[username]', $user['username'], 0);
	print_input_row($vbphrase['displayname'], 'user[displayname]', vB_String::htmlSpecialCharsUni($user['displayname']), 0);
	print_input_row($vbphrase['password'], 'password', '', true, 35, 0, '', false, false, array(1, 1), array('autocomplete' => 'off'));
	print_input_row($vbphrase['email'], 'user[email]', $user['email']);

	$selectlist = ['0' => $vbphrase['use_forum_default']] + vB_Api::instanceInternal('language')->getLanguageTitles(false);
	print_select_row($vbphrase['language'] , 'user[languageid]', $selectlist, $user['languageid']);

	//if the title is user set, it's already html escaped in the user array and we don't want to escape it further.
	$userset = ($user['customtitle'] == 2);
	print_input_row($vbphrase['user_title_guser'], 'user[usertitle]', $user['usertitle'], !$userset);

	$selectlist = [
		0 => $vbphrase['no'],
		2 => $vbphrase['user_set'],
		1 => $vbphrase['admin_set_html_allowed']
	];
	print_select_row($vbphrase['custom_user_title'], 'user[customtitle]', $selectlist, $user['customtitle']);
	print_input_row($vbphrase['personal_home_page'], 'user[homepage]', $user['homepage'], 0);

	print_time_row($vbphrase['birthday_guser'], 'user[birthday]', $user['birthday'], 0, 1);

	$selectlist = [
		0 => $vbphrase['hide_age_and_dob'],
		1 => $vbphrase['display_age_guser'],
		3 => $vbphrase['display_day_and_month'],
		2 => $vbphrase['display_age_and_dob']
	];
	print_select_row($vbphrase['privacy_guser'], 'user[showbirthday]', $selectlist, $user['showbirthday']);
	print_textarea_row($vbphrase['signature'], 'user[signature]', $user['signature'], 8, 45);

	print_input_row($vbphrase['icq_uin'], 'user[icq]', $user['icq'], 0);
	print_input_row($vbphrase['yahoo_id'], 'user[yahoo]', $user['yahoo'], 0);
	print_input_row($vbphrase['skype_name'], 'user[skype]', $user['skype'], 0);

	print_yes_no_row($vbphrase['coppa_user'], 'options[coppauser]', $user['coppauser']);
	print_input_row($vbphrase['parent_email_address'], 'user[parentemail]', $user['parentemail'], 0);

	$referrername = '';
	if ($user['referrerid'])
	{
		$referrername = $assertor->getRow('user',	[
			'userid' => $user['referrerid'],
			vB_dB_Query::COLUMNS_KEY => ['username'],
		]);
		$referrername = $referrername['username'] ?? '';
	}

	print_input_row($vbphrase['referrer'], 'user[referrerid]', $referrername, 0);
	$viewReferralsLink = '<div class="smallfont" style="margin-top:-10px;">';
	if ($user['referrerid'] AND $referrername)
	{
		$viewReferralsLink .= '<a href="admincp/user.php?do=edit&amp;u=' . $user['referrerid'] . '">[' . $referrername . ']</a> ';
	}

	if ($user['userid'])
	{
		$viewReferralsLink .= '<a href="' . 'admincp/usertools.php?do=showreferrals&amp;referrerid=' . $user['userid'] . '">[' . $vbphrase['view_referrals'] . ']</a> ';
	}

	$viewReferralsLink .= '</div>';
	fetch_row_bgclass();
	print_label_row('', $viewReferralsLink);
	print_input_row($vbphrase['ip_address'], 'user[ipaddress]', $user['ipaddress']);
	print_input_row($vbphrase['post_count'], 'user[posts]', $user['posts'], 0, 7);
	print_table_break('', $INNERTABLEWIDTH);

	// USER IMAGE SECTION
	print_table_header($vbphrase['image_options']);

	$userApi = vB_Api::instance('user');
	$avatar = $userApi->fetchAvatar($user['userid']);
	print_stop_message_on_api_error($avatar);


	$avatarurl = $avatar['avatarpath'];
	if (empty($avatar['isfullurl']))
	{
		$avatarurl = 'core/' . $avatarurl;
	}

	print_label_row(
		$vbphrase['avatar_guser'],
		'<img style="max-height:200px" src="' . $avatarurl . '" alt="" align="top" /> &nbsp; ' .
			'<input type="submit" class="button" tabindex="1" name="modifyavatar" value="' . $vbphrase['change_avatar'] . '" />'
	);

	$image = '';
	if ($user['sigpicfiledataid'])
	{
		$sigpicurl = htmlspecialchars('filedata/fetch?filedataid=' . $user['sigpicfiledataid'] . '&sigpic=1');
		if ($user['sigpicwidth'] AND $user['sigpicheight'])
		{
			$sigpicurl .= "\" width=\"$user[sigpicwidth]\" height=\"$user[sigpicheight]";
		}
		$image = '<img src="' . $sigpicurl . '" alt="" align="top" />';
	}

	print_label_row(
		$vbphrase['signature_picture_guser'] . '<input type="image" src="images/clear.gif" alt="" />',
			$image  . ' &nbsp; ' .
			'<input type="submit" class="button" tabindex="1" name="modifysigpic" value="' . $vbphrase['change_signature_picture'] . '" />'
	);

	print_table_break('', $INNERTABLEWIDTH);


	// PROFILE FIELDS SECTION
	$forms = array(
		0 => $vbphrase['edit_your_details'],
		1 => "$vbphrase[options]: $vbphrase[log_in] / $vbphrase[privacy]",
		2 => "$vbphrase[options]: $vbphrase[messaging] / $vbphrase[notification]",
		3 => "$vbphrase[options]: $vbphrase[thread_viewing]",
		4 => "$vbphrase[options]: $vbphrase[date] / $vbphrase[time]",
		5 => "$vbphrase[options]: $vbphrase[other_gprofilefield]",
	);
	$currentform = -1;

	print_table_header($vbphrase['user_profile_fields']);

	$profilefields = $assertor->assertQuery('fetchProfileFields', []);
	foreach ($profilefields AS $profilefield)
	{
		if ($profilefield['form'] != $currentform)
		{
			print_description_row(construct_phrase($vbphrase['fields_from_form_x'], $forms["$profilefield[form]"]), false, 2, 'optiontitle');
			$currentform = $profilefield['form'];
		}
		print_profilefield_row('userfield', $profilefield, $userfield, false);
		construct_hidden_code('userfield[field' . $profilefield['profilefieldid'] . '_set]', 1);
	}

	// Legacy Hook 'useradmin_edit_column1' Removed //

	if ($vbulletin->options['cp_usereditcolumns'] == 2)
	{
		?>
		</table>
		</td><td>&nbsp;&nbsp;&nbsp;&nbsp;</td><td>
		<table cellpadding="4" cellspacing="0" border="0" align="center" width="100%" class="tborder">
		<?php
	}
	else
	{
		print_table_break('', $INNERTABLEWIDTH);
	}

	// USERGROUP SECTION
	print_table_header($vbphrase['usergroup_options_gcpuser']);
	print_chooser_row($vbphrase['primary_usergroup'], 'user[usergroupid]', 'usergroup', $user['usergroupid']);
	if (!empty($user['membergroupids']))
	{
		$usergroupids = explode(',',  $user['membergroupids']);
		$usergroupids[] = $user['usergroupid'];
		$displaygroupid = ($user['displaygroupid'] == 0 ? -1 : $user['displaygroupid']);
		print_chooser_row($vbphrase['display_usergroup'], 'user[displaygroupid]', 'usergroup', $displaygroupid, $vbphrase['default'], 0, ['usergroupid' => $usergroupids]);
	}
	print_membergroup_row($vbphrase['additional_usergroups'], 'user[membergroupids]', 0, $user['membergroupids']);
	print_table_break('', $INNERTABLEWIDTH);

	if ($user['userid'])
	{
		$banreason = $assertor->getRow('userban', ['userid' => $user['userid']]);
		if ($banreason)
		{
			print_table_header($vbphrase['banning'], 3);

			$row = [
				$vbphrase['ban_reason'],
				(!empty($banreason['reason']) ? $banreason['reason'] : $vbphrase['n_a']),
				construct_link_code($vbphrase['lift_ban'], "../" . $vb5_config['Misc']['modcpdir'] . "/banning.php?do=liftban&amp;userid=" . $user['userid'])
			];
			print_cells_row($row);
			print_table_break('', $INNERTABLEWIDTH);
		}
	}

	if (!empty($subobj->subscriptioncache))
	{
		$subscribed = [];
		// fetch all active subscriptions the user is subscribed too
		$subs = $assertor->assertQuery('fetchActiveSubscriptions', ['userid' => $user['userid']]);
		if ($subs AND $subs->valid())
		{
			print_table_header($vbphrase['paid_subscriptions']);
			//while ($sub = $vbulletin->db->fetch_array($subs))
			foreach ($subs AS $sub)
			{
				$desc = "<div style=\"float:" . vB_Template_Runtime::fetchStyleVar('right') . "\"><input type=\"submit\" class=\"button\" tabindex=\"1\" name=\"subscriptionlogid[$sub[subscriptionlogid]]\" value=\"" . $vbphrase['edit'] . "\" />&nbsp;</div>";

				$joindate = vbdate($vbulletin->options['dateformat'], $sub['regdate'], false);
				$enddate = vbdate($vbulletin->options['dateformat'], $sub['expirydate'], false);
				if ($sub['status'])
				{
					$title = '<strong>' . $vbphrase['sub' . $sub['subscriptionid'] . '_title'] . '</strong>';
					$desc .= '<strong>' . construct_phrase($vbphrase['x_to_y'], $joindate, $enddate) . '</strong>';
				}
				else
				{
					$title = $vbphrase['sub' . $sub['subscriptionid'] . '_title'];
					$desc .= construct_phrase($vbphrase['x_to_y'], $joindate, $enddate);
				}

				print_label_row($title, $desc);
			}
			print_table_break('',$INNERTABLEWIDTH);
		}
	}

	// REPUTATION SECTION
	$score = vB_Library::instance('reputation')->fetchReppower($user);

	print_table_header($vbphrase['reputation']);
	print_input_row($vbphrase['reputation_level_guser'], 'user[reputation]', $user['reputation']);
	print_label_row($vbphrase['current_reputation_power'], $score, '', 'top', 'reputationpower');
	print_table_break('', $INNERTABLEWIDTH);

	// INFRACTIONS section
	print_table_header($vbphrase['infractions'] . '<span class="smallfont">' . construct_link_code($vbphrase['view'], "admininfraction.php?do=dolist&amp;startstamp=1&amp;endstamp= " . TIMENOW . "&amp;infractionlevelid=-1&amp;u= " . $edituserid) . '</span>');
	print_input_row($vbphrase['warnings_gcpuser'], 'user[warnings]', $user['warnings'], true, 5);
	print_input_row($vbphrase['infractions'], 'user[infractions]', $user['infractions'], true, 5);
	print_input_row($vbphrase['infraction_points'], 'user[ipoints]', $user['ipoints'], true, 5);
	if (!empty($user['infractiongroupids']))
	{
		$infractiongroups = explode(',', $user['infractiongroupids']);
		$groups = array();
		foreach ($infractiongroups AS $groupid)
		{
			if (!empty($vbulletin->usergroupcache["$groupid"]['title']))
			{
				$groups[] = $vbulletin->usergroupcache["$groupid"]['title'];
			}
		}
		if (!empty($groups))
		{
			print_label_row($vbphrase['infraction_groups'], implode('<br />', $groups));
		}
		if (!empty($user['infractiongroupid']) AND $usertitle = $vbulletin->usergroupcache["$user[infractiongroupid]"]['usertitle'])
		{
			print_label_row($vbphrase['display_group'], 	$usertitle);
		}
	}
	print_table_break('',$INNERTABLEWIDTH);

	// BROWSING OPTIONS SECTION
	print_table_header($vbphrase['browsing_options']);
	print_yes_no_row($vbphrase['receive_admin_emails_guser'], 'options[adminemail]', $user['adminemail']);
	print_yes_no_row($vbphrase['invisible_mode_guser'], 'options[invisible]', $user['invisible']);
	print_yes_no_row($vbphrase['receive_private_messages_guser'], 'options[receivepm]', $user['receivepm']);
	print_yes_no_row($vbphrase['pm_from_contacts_only'], 'options[receivepmbuddies]', $user['receivepmbuddies']);
	print_yes_no_row($vbphrase['send_notification_email_when_a_private_message_is_received_guser'], 'options[emailonpm]', $user['emailonpm']);
	print_yes_no_row($vbphrase['save_pm_copy_default_no_link'], 'options[pmdefaultsavecopy]', $user['pmdefaultsavecopy']);
	print_yes_no_row($vbphrase['enable_visitor_messaging'], 'options[vm_enable]', $user['vm_enable']);
	print_yes_no_row($vbphrase['limit_vm_to_contacts_only'], 'options[vm_contactonly]', $user['vm_contactonly']);
	print_yes_no_row($vbphrase['display_signatures_gcpuser'], 'options[showsignatures]', $user['showsignatures']);
	print_yes_no_row($vbphrase['display_avatars_gcpuser'], 'options[showavatars]', $user['showavatars']);
	print_yes_no_row($vbphrase['display_images_gcpuser'], 'options[showimages]', $user['showimages']);
	print_yes_no_row($vbphrase['show_others_custom_profile_styles'], 'options[showusercss]', $user['showusercss']);
	print_yes_no_row($vbphrase['receieve_friend_request_notification'], 'options[receivefriendemailrequest]', $user['receivefriendemailrequest']);
	print_yes_no_row($vbphrase['send_birthday_email'], 'options[birthdayemail]', $user['birthdayemail']);

	print_yes_no_row($vbphrase['autosubscribe_when_posting'], 'user[autosubscribe]', $user['autosubscribe']);

	$selectlist = [
		0  => $vbphrase['usersetting_emailnotification_none'],
		1  => $vbphrase['usersetting_emailnotification_on'],
		2  => $vbphrase['usersetting_emailnotification_daily'],
		3  => $vbphrase['usersetting_emailnotification_weekly'],
	];
	print_radio_row($vbphrase['usersetting_emailnotification'], 'user[emailnotification]', $selectlist, $user['emailnotification'], 'smallfont');

	$selectlist = [
		0 => "$vbphrase[linear] - $vbphrase[oldest_first_guser]",
		3 => "$vbphrase[linear] - $vbphrase[newest_first_guser]",
		2 => $vbphrase['hybrid'],
		1 => $vbphrase['threaded'],
	];
	print_radio_row($vbphrase['thread_display_mode_guser'], 'user[threadedmode]', $selectlist, $user['threadedmode'], 'smallfont');

	$selectlist = [
		0 => $vbphrase['do_not_show_editor_toolbar'],
		1 => $vbphrase['show_standard_editor_toolbar_guser'],
		2 => $vbphrase['show_enhanced_editor_toolbar_guser'],
	];
	print_radio_row($vbphrase['message_editor_interface'], 'user[showvbcode]', $selectlist, $user['showvbcode'], 'smallfont');

	print_user_style_chooser_row($vbphrase['style'], 'user[styleid]', $user['styleid'], $vbphrase['use_forum_default']);
	print_table_break('', $INNERTABLEWIDTH);

	// MODERATOR NOTIFICATION OPTIONS SECTION
	$isModerator = (vB::getUserContext($user['userid'])->getUserLevel() >= vB_UserContext::USERLEVEL_MODERATOR);
	if ($isModerator)
	{
		$datastore = vB::getDatastore();
		$bf_misc_moderatornotificationoptions = $datastore->getValue('bf_misc_moderatornotificationoptions');
		$bf_misc_moderatoremailnotificationoptions = $datastore->getValue('bf_misc_moderatoremailnotificationoptions');

		print_table_header($vbphrase['moderator_notification_options']);

		print_yes_no_row($vbphrase['usersetting_moderatornotification_monitoredword'], 'moderatornotificationoptions[monitoredword]', boolval($user['moderatornotificationoptions'] & $bf_misc_moderatornotificationoptions['monitoredword']));
		print_yes_no_row($vbphrase['send_email_on_monitored_word'], 'moderatoremailnotificationoptions[monitoredword]', boolval($user['moderatoremailnotificationoptions'] & $bf_misc_moderatoremailnotificationoptions['monitoredword']));

		print_yes_no_row($vbphrase['usersetting_moderatornotification_reportedpost'], 'moderatornotificationoptions[reportedpost]', boolval($user['moderatornotificationoptions'] & $bf_misc_moderatornotificationoptions['reportedpost']));
		print_yes_no_row($vbphrase['send_email_on_reported_post'], 'moderatoremailnotificationoptions[reportedpost]', boolval($user['moderatoremailnotificationoptions'] & $bf_misc_moderatoremailnotificationoptions['reportedpost']));

		print_yes_no_row($vbphrase['usersetting_moderatornotification_unapprovedpost'], 'moderatornotificationoptions[unapprovedpost]', boolval($user['moderatornotificationoptions'] & $bf_misc_moderatornotificationoptions['unapprovedpost']));
		print_yes_no_row($vbphrase['send_email_on_unapproved_post'], 'moderatoremailnotificationoptions[unapprovedpost]', boolval($user['moderatoremailnotificationoptions'] & $bf_misc_moderatoremailnotificationoptions['unapprovedpost']));

		print_yes_no_row($vbphrase['usersetting_moderatornotification_spampost'], 'moderatornotificationoptions[spampost]', boolval($user['moderatornotificationoptions'] & $bf_misc_moderatornotificationoptions['spampost']));
		print_yes_no_row($vbphrase['send_email_on_spam_post'], 'moderatoremailnotificationoptions[spampost]', boolval($user['moderatoremailnotificationoptions'] & $bf_misc_moderatoremailnotificationoptions['spampost']));

		print_table_break('', $INNERTABLEWIDTH);
	}

	// ADMIN OVERRIDE OPTIONS SECTION
	print_table_header($vbphrase['admin_override_options']);
	foreach ($vbulletin->bf_misc_adminoptions AS $field => $value)
	{
		print_yes_no_row($vbphrase['keep_' . $field], 'adminoptions[' . $field . ']', $user["$field"]);
	}
	print_table_break('', $INNERTABLEWIDTH);

	// TIME FIELDS SECTION
	print_table_header($vbphrase['time_options']);
	print_select_row($vbphrase['timezone'], 'user[timezoneoffset]', fetch_timezones_array(), $user['timezoneoffset']);
	print_yes_no_row($vbphrase['automatically_detect_dst_settings'], 'options[dstauto]', $user['dstauto']);
	print_yes_no_row($vbphrase['dst_currently_in_effect'], 'options[dstonoff]', $user['dstonoff']);
	print_select_row($vbphrase['default_view_age'], 'user[daysprune]', $pruneoptions, $user['daysprune']);
	print_time_row($vbphrase['join_date'], 'user[joindate]', $user['joindate']);
	print_time_row($vbphrase['last_activity'], 'user[lastactivity]', $user['lastactivity']);
	print_time_row($vbphrase['last_post'], 'user[lastpost]', $user['lastpost']);
	print_table_break('', $INNERTABLEWIDTH);

	// EXTERNAL CONNECTIONS SECTION
	print_table_header($vbphrase['external_connections']);
	$externalConnections = array(
		array(
			'titlephrase' => 'facebook_connected',
			'connected' => !empty($user['fbuserid']),
			'helpname' => 'facebookconnect',
			'displayorder' => 10,
		),
	);

	vB::getHooks()->invoke('hookAdminCPUserExternalConnections', array(
		'userid' => $user['userid'],
		'externalConnections' => &$externalConnections,
	));

	// sort by displayorder
	usort($externalConnections, function ($a, $b) {
		return ($a['displayorder'] - $b['displayorder']);
	});

	foreach ($externalConnections AS $__row)
	{
		if (!isset($__row['helpname']))
		{
			$__row['helpname'] = NULL;
		}
		print_label_row(
			$vbphrase[$__row['titlephrase']],
			($__row['connected'] ? $vbphrase['yes'] : $vbphrase['no']),
			'', // class
			'top', // valign
			$__row['helpname'] // helpname
		);
	}

	// PRIVACY CONSENT SECTION
	if ($edituserid AND $_REQUEST['do'] != 'add')
	{
		print_table_break('', $INNERTABLEWIDTH);

		print_table_header($vbphrase['admincp_privacyconsent_label']);

		// Having the location visible somewhere is useful for debugging, since both 0 or 1 can mean "requires consent"
		$privacyConsentRequired = $userLib->checkPrivacyOption('enable_privacy_registered', $user['location']);
		$privacyConsentRequiredOutput = '<span title="location: ' . htmlentities($user['location']) . '">'
			. ($privacyConsentRequired ? $vbphrase['yes'] : $vbphrase['no'])
			. '</span>';

		print_label_row(
			$vbphrase['admincp_privacyconsent_required_label'],
			$privacyConsentRequiredOutput,
			'', // class
			'top', // valign
			'privacyconsent_required' // helpname
		);
		$privacyConsentOutput = "";
		switch($user['privacyconsent'])
		{
			case 1:
				$privacyConsentOutput = $vbphrase['admincp_privacyconsent_provided'];
				break;
			case -1:
				$privacyConsentOutput = $vbphrase['admincp_privacyconsent_withdrawn'];
				break;
			case 0:
			default:
				$privacyConsentOutput = $vbphrase['admincp_privacyconsent_unknown'];
				break;
		}
		print_label_row(
			$vbphrase['admincp_privacyconsent_status_label'],
			$privacyConsentOutput,
			'', // class
			'top', // valign
			'privacyconsent' // helpname
		);
		if ($user['privacyconsentupdated'] > 0)
		{
			$privacyConsentUpdatedOutput = vbdate($vboptions['dateformat'], $user['privacyconsentupdated'], false);
		}
		else
		{
			$privacyConsentUpdatedOutput = $vbphrase['never'];
		}
		print_label_row(
			$vbphrase['admincp_privacyconsentupdated_label'],
			$privacyConsentUpdatedOutput,
			'', // class
			'top', // valign
			'privacyconsentupdated' // helpname
		);
	}
	//print_table_break('', $INNERTABLEWIDTH);

	// Legacy Hook 'useradmin_edit_column2' Removed //

	?>
	</table>
	</td>
	</tr>
	<?php

	print_table_break('', $OUTERTABLEWIDTH);
	$tableadded = 1;
	print_submit_row($vbphrase['save']);
}


function user_getdefaultuserdata($datastore)
{
	//the regoptions map to user options in some cases but are a seperate bitfield
	//the acutal bit values and names do not necesarily line up
	$bfregoptions = $datastore->getValue('bf_misc_regoptions');
	$defaultregoptions = $datastore->getOption('defaultregoptions');

	$optarray = convert_bits_to_array($defaultregoptions, $bfregoptions);

	//some calculated fields based on multiple regoptions.
	$regoption = [];
	if ($optarray['emailnotification_none'])
	{
		$regoption['emailnotification'] = 0;
	}
	else if ($optarray['emailnotification_on'])
	{
		$regoption['emailnotification'] = 1;
	}
	else if ($optarray['emailnotification_daily'])
	{
		$regoption['emailnotification'] = 2;
	}
	else // weekly
	{
		$regoption['emailnotification'] = 3;
	}

	if ($optarray['vbcode_none'])
	{
		$regoption['showvbcode'] = 0;
	}
	else if ($optarray['vbcode_standard'])
	{
		$regoption['showvbcode'] = 1;
	}
	else
	{
		$regoption['showvbcode'] = 2;
	}

	if ($optarray['thread_linear_oldest'])
	{
		$regoption['threadedmode'] = 0;
		$regoption['postorder'] = 0;
	}
	else if ($optarray['thread_linear_newest'])
	{
		$regoption['threadedmode'] = 0;
		$regoption['postorder'] = 1;
	}
	else if ($optarray['thread_threaded'])
	{
		$regoption['threadedmode'] = 1;
		$regoption['postorder'] = 0;
	}
	else if ($optarray['thread_hybrid'])
	{
		$regoption['threadedmode'] = 2;
		$regoption['postorder'] = 0;
	}
	else
	{
		$regoption['threadedmode'] = 0;
		$regoption['postorder'] = 0;
	}

	$user = [
		//hard coded defaults
		'userid'                    => 0,
		'usergroupid'               => 2,
		'membergroupids'            => '',
		'daysprune'                 => -1,
		'joindate'                  => TIMENOW,
		'lastactivity'              => TIMENOW,
		'lastpost'                  => 0,
		'username'                  => '',
		'displayname'               => '',
		'email'                     => '',
		'languageid'                => 0,
		'usertitle'                 => '',
		'customtitle'               => 0,
		'homepage'                  => '',
		'birthday'                  => '',
		'showbirthday'              => 0,
		'signature'                 => '',
		'icq'                       => '',
		'yahoo'                     => '',
		'skype'                     => '',
		'parentemail'               => '',
		'referrerid'                => 0,
		'ipaddress'                 => '',
		'posts'                     => '',
		'avatarid'                  => 0,
		'warnings'                  => 0,
		'infractions'               => 0,
		'ipoints'                   => 0,
		'styleid'                   => 0,
		'sigpicfiledataid'          => 0,

		//psudeo fields based on the presence/absense child table records.
		'hascustomavatar'           => 0,

		//bitfields that get expanded into the user array
		//options bitfield
		'coppauser'                 => 0,
		'dstauto'                   => 1,
		'receivefriendemailrequest' => 1,
		'receivepmbuddies'          => 0,
		'showusercss'               => 1,
		'dstonoff'                  => 0,

		//adminoptions bitfield
		'adminavatar'               => 0,

		//we mostly only care about this when editing a user but we
		//stick it in a hidden field and I don't know what the consequences of
		//not having the hidded filds are.
		'displaygroupid'            => null,

		//direct options
		'reputation'                => $datastore->getOption('reputationdefault'),
		'timezoneoffset'            => $datastore->getOption('timeoffset'),

		//we don't necesarily want all of the regotions here and the field names don't 100% match
		//so copy them over explicitly.
		'autosubscribe'             => $optarray['autosubscribe'],
		'invisible'                 => $optarray['invisiblemode'],
		'adminemail'                => $optarray['adminemail'],
		'receivepm'                 => $optarray['enablepm'],
		'emailonpm'                 => $optarray['emailonpm'],
		'pmdefaultsavecopy'         => $optarray['pmdefaultsavecopy'],
		'vm_enable'                 => $optarray['vm_enable'],
		'vm_contactonly'            => $optarray['vm_contactonly'],
		'showsignatures'            => $optarray['signature'],
		'showavatars'               => $optarray['avatar'],
		'showimages'                => $optarray['image'],
		'birthdayemail'             => $optarray['birthdayemail'],

		//calculated options.
		'emailnotification'         => $regoption['emailnotification'],
		'postorder'                 => $regoption['postorder'],
		'threadedmode'              => $regoption['threadedmode'],
		'showvbcode'                => $regoption['showvbcode'],

		// I'm not sure why these default values are needed in both adminCP & the user datamanager.
		// See vB_DataManager_User::set_registration_defaults() where this is On by default if not set.
		// I'm going to leave this bit out of this file to improve maintainability, but leave this comment for tracking.
		// 'enable_pmchat'             => 1,
	];

	return $user;
}

// ###################### Start do update #######################
if ($_POST['do'] == 'update')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'userid'                            => vB_Cleaner::TYPE_UINT,
		'password'                          => vB_Cleaner::TYPE_STR,
		'user'                              => vB_Cleaner::TYPE_ARRAY,
		'options'                           => vB_Cleaner::TYPE_ARRAY_BOOL,
		'moderatornotificationoptions'      => vB_Cleaner::TYPE_ARRAY_BOOL,
		'moderatoremailnotificationoptions' => vB_Cleaner::TYPE_ARRAY_BOOL,
		'adminoptions'                      => vB_Cleaner::TYPE_ARRAY_BOOL,
		'userfield'                         => vB_Cleaner::TYPE_ARRAY,
		'modifyavatar'                      => vB_Cleaner::TYPE_NOCLEAN,
		'modifysigpic'                      => vB_Cleaner::TYPE_NOCLEAN,
		'subscriptionlogid'                 => vB_Cleaner::TYPE_ARRAY_KEYS_INT,
	));

	if (!isset($vbulletin->GPC['user']['membergroupids']))
	{
		$vbulletin->GPC['user']['membergroupids'] = array();
	}

	// Slight QoL behavior for creating/editing users via adminCP. Just allow skipping
	// setting a display name for new users, or deleting the display name and saving for
	// existing users, as a shortcut for "set it to username". This shortcut is documented
	// in the new adminhelp for display name.
	if (isset($vbulletin->GPC['user']['displayname']) AND trim($vbulletin->GPC['user']['displayname']) == '')
	{
		// Note that both should be raw unescaped at this point, so we don't have to
		// put the username through vB_String::unhtmlspecialcharsuni() here.
		$vbulletin->GPC['user']['displayname'] = $vbulletin->GPC['user']['username'];
	}

	$assertor = vB::getDbAssertor();
	$userApi = vB_Api::instance('user');
	$userid = $userApi->save(
		$vbulletin->GPC['userid'],
		$vbulletin->GPC['password'],
		$vbulletin->GPC['user'],
		$vbulletin->GPC['options'],
		$vbulletin->GPC['adminoptions'],
		$vbulletin->GPC['userfield'],
		[], // notification options (@TODO: Support this here)
		[], // hv input
		[], // extra
		$vbulletin->GPC['moderatornotificationoptions'],
		$vbulletin->GPC['moderatoremailnotificationoptions']
	);

	if (is_array($userid) AND isset($userid['errors']))
	{
		foreach ($userid['errors'] AS $key => $error)
		{
			if (is_array($error))
			{
				$error = $error[0];
			}

			//sub in the admincp error for the user facing error.
			if ($error == 'emailtaken')
			{
				//we shouldn't be here if we didn't try to set/change the email.
				//but let's avoid errors anyway sincet the result is a bad link and
				//not a garbled error message
				$url = get_redirect_url('admincp/user.php', [
					'do' => 'find',
					'user[email]' => $vbulletin->GPC['user']['email'] ?? '',
				]);

				$userid['errors'][$key] = ['emailtaken_search_here', $url];
			}
		}

		print_stop_message_array($userid['errors']);
	}

	// #############################################################################
	// now do the redirect
	$file = '';
	$args = array();
	if ($vbulletin->GPC['modifyavatar'])
	{
		$file = 'usertools';
		$args = array(
			'do' => 'avatar',
			'u' => $userid
		);
	}
	else if ($vbulletin->GPC['modifysigpic'])
	{
		$file = 'usertools';
		$args = array(
			'do' => 'sigpic',
			'u' => $userid
		);
	}
	else if ($vbulletin->GPC['subscriptionlogid'])
	{
		$file = 'subscriptions';
		$args = array(
			'do' => 'adjust',
			'subscriptionlogid' => array_pop($vbulletin->GPC['subscriptionlogid'])
		);
	}
	else
	{
		$handled = false;
		// Legacy Hook 'useradmin_update_choose' Removed //

		if (!$handled)
		{
			$file = 'user';
			$args = array(
				'do' => 'modify',
				'u' => $userid
			);
		}
	}

	$user = $userApi->fetchUserinfo($userid);
	if (is_array($user) AND isset($user['errors']))
	{
		print_stop_message2($userid['errors'][0]);
	}

	//don't grab the cached context from the vB object.  It may have changed.
	$context = new vB_UserContext($userid, $assertor, vB::getDatastore(), vB::getConfig());
	$args['insertedadmin'] = $context->isAdministrator();
	print_stop_message2(array('saved_user_x_successfully',  $user['username']), $file, $args);
}

// ###################### Start modify #######################
if ($_REQUEST['do'] == 'modify')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'userid'        => vB_Cleaner::TYPE_INT,
		'insertedadmin' => vB_Cleaner::TYPE_INT
	));

	if ($vbulletin->GPC['userid'])
	{
		$userinfo = vB_User::fetchUserinfo($vbulletin->GPC['userid']);
		if (!$userinfo)
		{
			print_stop_message2('invalid_user_specified');
		}
		print_form_header('admincp/user', 'edit', 0, 1, 'reviewform');
		print_table_header($userinfo['username'], 2, 0, '', 'center', 0);
		construct_hidden_code('userid', $vbulletin->GPC['userid']);

		$description = construct_link_code($vbphrase['view_profile'], "user.php?do=edit&amp;u=" . $vbulletin->GPC['userid']);
		if ($vbulletin->GPC['insertedadmin'])
		{
			$description .= '<br />' . construct_link_code(
				'<span style="color:red;"><strong>' . $vbphrase['update_or_add_administration_permissions'] .	'</strong></span>',
				'adminpermissions.php?do=edit&amp;u=' . $vbulletin->GPC['userid']
			);
		}

		print_description_row($description);
		print_table_footer();
	}

	print_form_header('admincp/', '');
	print_table_header($vbphrase['quick_search']);
	$links = [
		"admincp/user.php?do=find" => $vbphrase['show_all_users'],
		"admincp/user.php?do=find&orderby=posts&direction=DESC&limitnumber=30" => $vbphrase['list_top_posters'],
		"admincp/user.php?do=find&user[lastactivityafter]=" . (TIMENOW - 86400) . "&orderby=lastactivity&direction=DESC" => $vbphrase['list_visitors_in_the_last_24_hours'],
		"admincp/user.php?do=find&orderby=joindate&direction=DESC&limitnumber=30" => $vbphrase['list_new_registrations'],
		"admincp/user.php?do=moderate" => $vbphrase['list_users_awaiting_moderation'],
		"admincp/user.php?do=find&user[coppauser]=1" => $vbphrase['show_all_coppa_users'],
		"admincp/user.php?do=findduplicateemails" => $vbphrase['look_for_duplicate_emails'],
		"modcp/banning.php?do=modify&sortby=bandate&sortdir=DESC" => $vbphrase['list_recently_banned_users'],
	];
	print_link_list_row($links);
	print_table_footer();

	print_form_header('admincp/user', 'find');
	print_table_header($vbphrase['advanced_search']);
	print_description_row($vbphrase['if_you_leave_a_field_blank_it_will_be_ignored']);
	print_description_row('', 0, 2, 'thead');
	print_user_search_rows();
	print_table_break();

	//might be worth moving the display chunk to the be in a function like print_user_search_rows
	//mostly for symetry but it also get stuff out of global space.
	print_table_header($vbphrase['display_options']);
	print_yes_no_row($vbphrase['display_username'], 'display[username]', 1);
	// We may want to revisit this, but QoL to just hide displaynames in search result columns by default when enabledisplayname is off.
	// Note that they can manually turn it on/off in either case, and they will always be able to search by & edit displaynames regardless
	// of the enabledisplayname setting.
	print_yes_no_row($vbphrase['display_displayname'], 'display[displayname]', !empty($vboptions['enabledisplayname']));
	print_yes_no_row($vbphrase['display_options'], 'display[options]', 1);
	print_yes_no_row($vbphrase['display_usergroup'], 'display[usergroup]', 0);
	print_yes_no_row($vbphrase['display_email_gcpuser'], 'display[email]', 1);
	print_yes_no_row($vbphrase['display_parent_email_address'], 'display[parentemail]', 0);
	print_yes_no_row($vbphrase['display_coppa_user'],'display[coppauser]', 0);
	print_yes_no_row($vbphrase['display_home_page'], 'display[homepage]', 0);
	print_yes_no_row($vbphrase['display_icq_uin'], 'display[icq]', 0);
	print_yes_no_row($vbphrase['display_yahoo_id'], 'display[yahoo]', 0);
	print_yes_no_row($vbphrase['display_skype_name'], 'display[skype]', 0);
	print_yes_no_row($vbphrase['display_signature'], 'display[signature]', 0);
	print_yes_no_row($vbphrase['display_user_title'], 'display[usertitle]', 0);
	print_yes_no_row($vbphrase['display_join_date'], 'display[joindate]', 1);
	print_yes_no_row($vbphrase['display_last_activity'], 'display[lastactivity]', 1);
	print_yes_no_row($vbphrase['display_last_post'], 'display[lastpost]', 0);
	print_yes_no_row($vbphrase['display_post_count'], 'display[posts]', 1);
	print_yes_no_row($vbphrase['display_reputation_gcpuser'], 'display[reputation]', 0);
	print_yes_no_row($vbphrase['display_warnings'], 'display[warnings]', 0);
	print_yes_no_row($vbphrase['display_infractions_gcpuser'], 'display[infractions]', 0);
	print_yes_no_row($vbphrase['display_infraction_points'], 'display[ipoints]', 0);
	print_yes_no_row($vbphrase['display_ip_address'], 'display[ipaddress]', 0);
	print_yes_no_row($vbphrase['display_birthday'], 'display[birthday]', 0);
	print_yes_no_row($vbphrase['display_eustatus'], 'display[privacyrequired]', 0);
	print_yes_no_row($vbphrase['display_privacyconsent'], 'display[privacyconsent]', 0);
	print_yes_no_row($vbphrase['display_privacyconsentupdated'], 'display[privacyconsentupdated]', 0);
	print_middle_submit_row($vbphrase['find']);

	print_table_header($vbphrase['user_profile_field_options']);
	$profilefields = vB::getDbAssertor()->assertQuery('fetchProfileFields');
	foreach ($profilefields AS $profilefield)
	{
		$key = 'field' . $profilefield['profilefieldid'];
		$titlekey = $key . '_title';
		$__title = construct_phrase($vbphrase['display_x'], htmlspecialchars_uni($vbphrase[$titlekey] ?? $titlekey));
		print_yes_no_row($__title, "display[$key]", 0);
	}

	print_middle_submit_row($vbphrase['find']);
	print_table_break();

	print_table_header($vbphrase['sorting_options']);
	print_label_row($vbphrase['order_by_gcpglobal'], '
		<select name="orderby" tabindex="1" class="bginput">
			<option value="username" selected="selected">' . 	$vbphrase['username'] . '</option>
			<option value="email">' . $vbphrase['email'] . '</option>
			<option value="joindate">' . $vbphrase['join_date'] . '</option>
			<option value="lastactivity">' . $vbphrase['last_activity'] . '</option>
			<option value="lastpost">' . $vbphrase['last_post'] . '</option>
			<option value="posts">' . $vbphrase['post_count'] . '</option>
			<option value="birthday_search">' . $vbphrase['birthday_guser'] . '</option>
			<option value="reputation">' . $vbphrase['reputation'] . '</option>
			<option value="warnings">' . $vbphrase['warnings_gcpuser'] . '</option>
			<option value="infractions">' . $vbphrase['infractions'] . '</option>
			<option value="ipoints">' . $vbphrase['infraction_points'] . '</option>
		</select>
		<select name="direction" tabindex="1" class="bginput">
			<option value="">' . $vbphrase['ascending'] . '</option>
			<option value="DESC">' . $vbphrase['descending'] . '</option>
		</select>
	', '', 'top', 'orderby');
	print_input_row($vbphrase['starting_at_result'], 'limitstart', 1);
	print_input_row($vbphrase['maximum_results'], 'limitnumber', 50);

	print_submit_row($vbphrase['find'], $vbphrase['reset'], 2, '', '<input type="submit" class="button" value="' . $vbphrase['exact_match'] . '" tabindex="1" name="user[exact]" />');
}

// ###################### Start find #######################
if ($_REQUEST['do'] == 'find2' AND defined('DONEFIND'))
{
	$userLib = vB_Library::instance('user');

	// carries on from do == find at top of script
	$display = $vbulletin->GPC['display'];
	$limitfinish = $vbulletin->GPC['limitstart'] + $vbulletin->GPC['limitnumber'];

	//these are array of ['phrasename', 'displaytype', 'columnoverride']
	//phrasename -- phrase system id to show the for the column header
	//displaytype -- switch to determine how to do the formatting
	//columnoverride -- the user array column name for the value.  If not
	//	provided then will use the field name (key for this array)
	$fieldmap = [
		'username' => ['username', 'userlink', ''],
		'displayname' => ['displayname', 'displayname_link', ''],
		'usergroup' => ['usergroup', 'usergroup', 'usergroupid'],
		'email' => ['email', 'email', ''],
		'parentemail' => ['parent_email_address', 'email', ''],
		'coppauser' => ['coppa_user', 'yesno', ''],
		'homepage' => ['personal_home_page', 'url', ''],
		'icq' => ['icq_uin', 'string', ''],
		'yahoo' => ['yahoo_id', 'string', ''],
		'skype' => ['skype_name', 'string', ''],
		'signature' => ['signature', 'signature', ''],
		'usertitle' => ['user_title_guser', 'string', ''],
		'joindate' => ['join_date', 'date', ''],
		'lastactivity' => ['last_activity', 'date', ''],
		'lastpost' => ['last_post', 'date', ''],
		'posts' => ['post_count', 'number', ''],
		'reputation' => ['reputation', 'number', ''],
		'warnings' => ['warnings_gcpuser', 'number', ''],
		'infractions' => ['infractions', 'number', ''],
		'ipoints' => ['infraction_points', 'number', ''],
		'ipaddress' => ['ip_address', 'string', ''],
		'birthday' => ['birthday_guser', 'string', 'birthday_search'],
		'privacyrequired' => ['admincp_privacyconsent_required_label', 'privacyrequired', 'location'],
		'privacyconsent' => ['admincp_privacyconsent_status_label', 'privacyconsent', ''],
		'privacyconsentupdated' => ['admincp_privacyconsentupdated_label', 'date', ''],
	];

	// display the column headings
	$header = [];
	foreach ($fieldmap AS $field => $fieldinfo)
	{
		if (!empty($display[$field]))
		{
			$header[] = $vbphrase[$fieldinfo[0]];
		}
	}

	$profilefields = vB::getDbAssertor()->assertQuery('fetchProfileFields');
	foreach ($profilefields AS $profilefield)
	{
		$key = 'field' . $profilefield['profilefieldid'];
		if (!empty($display[$key]))
		{
			$titlekey = $key . '_title';
			$header[] = htmlspecialchars_uni($vbphrase[$titlekey] ?? $titlekey);
		}
	}

	if (!empty($display['options']))
	{
		$header[] = $vbphrase['options'];
	}

	// get number of cells for use in 'colspan=' attributes
	$colspan = sizeof($header);
	print_form_header('admincp/user', 'find');
	print_table_header(
		construct_phrase(
			$vbphrase['showing_users_x_to_y_of_z'],
			intval($vbulletin->GPC['limitstart']) + 1,
			min($countusers, $limitfinish),
			$countusers
		),
		$colspan
	);
	print_cells_row2($header, 'thead');

	$groupcache = vB_Api::instanceInternal('usergroup')->fetchUsergroupList();
	$groupcache = array_column($groupcache, 'title', 'usergroupid');

	$privacyConsentCache = [
		1 => $vbphrase['admincp_privacyconsent_provided'],
		-1 => $vbphrase['admincp_privacyconsent_withdrawn'],
		0 => $vbphrase['admincp_privacyconsent_unknown'],
	];

	// now display the results
	foreach ($users['users'] AS $user)
	{
		$cell = [];
		foreach ($fieldmap AS $field => $fieldinfo)
		{
			if (!empty($display[$field]))
			{
				//this gets a trifle convoluted because so may of the types are one-off custom forms.
				//however we don't wnat to stick with the field by field approach that this is replacing because
				//a) The master list aids in reordering columns without having to make sure that the header/body
				//	order stays in sync
				//b) Some columns share logic and we'd like to avoid repeating it.
				//c) We want to skip the repeated if (display) then show column logic because with so many columns
				//	it clutters the code.  The alternative to break this mess up further would be to set a format
				//	function (perhaps anonymous for one offs) in the master list and just call it here.
				//	that might be overkill.

				//default to the field name as the user column but a couple of field don't match.
				$usercolumn = ($fieldinfo[2] ? $fieldinfo[2] : $field);
				$fieldval = $user[$usercolumn];

				switch ($fieldinfo[1])
				{
					case 'userlink':
						$url = 'admincp/user.php?do=edit&u=' . $user['userid'];
						$cell[] = '<a href="' . htmlspecialchars($url) . '"><b>' . $fieldval . '</b></a>&nbsp;';
						break;
					// same as above, but displaynames are not HTML safe.
					case 'displayname_link':
						$url = 'admincp/user.php?do=edit&u=' . $user['userid'];
						$cell[] = '<a href="' . htmlspecialchars($url) . '"><b>' . vB_String::htmlSpecialCharsUni($fieldval) . '</b></a>&nbsp;';
						break;

					case 'usergroup':
						$cell[] = $groupcache[$fieldval];
						break;

					case 'email':
						$cell[] = '<a href="mailto:' . $fieldval . '">' . $fieldval . '</a>';
						break;

					case 'yesno':
						$cell[] = ($user[$field] ? $vbphrase['yes'] : $vbphrase['no']);
						break;

					case 'url':
						$url = htmlspecialchars($fieldval);
						if ($url)
						{
							$cell[] = '<a href="' . $url . '" target="_blank">' . $url . '</a>';
						}
						else
						{
							$cell[] = '';
						}
						break;

					//this might be more genrically a multiline
					case 'signature':
						$cell[] = nl2br(htmlspecialchars_uni($fieldval));
						break;

					case 'date':
						if ($fieldval)
						{
							$cell[] = '<span class="smallfont">' . vbdate($vbulletin->options['dateformat'], $fieldval) . '</span>';
						}
						else
						{
							$cell[] = '<i>' . $vbphrase['never'] . '</i>';
						}
						break;

					case 'number':
						$cell[] = vb_number_format($fieldval);
						break;

					case 'ipaddress':
						if ($fieldval)
						{
							$host = @gethostbyaddr($fieldval);
							$cell[] = $fieldval . ' (' . $host . ')';
						}
						else
						{
							$cell[] = '&nbsp;';
						}
						break;

					case 'privacyrequired':
						$required = $userLib->checkPrivacyOption('enable_privacy_registered', $fieldval);
						$required	=  ($required ? $vbphrase['yes'] : $vbphrase['no']);
						$cell[] = '<span title="location: ' . htmlentities($fieldval) . '">'. $required . '</span>';
						break;

					case 'privacyconsent':
						$cell[] = $privacyConsentCache[$fieldval] ?? $privacyConsentCache[0];
						break;

					case 'string':
					default:
						$cell[] = $fieldval;
						break;
				}
			}
		}

		foreach ($profilefields AS $profilefield)
		{
			$profilefieldname = 'field' . $profilefield['profilefieldid'];
			if (!empty($display[$profilefieldname]))
			{
				if ($profilefield['type'] == 'checkbox' OR $profilefield['type'] == 'select_multiple')
				{
					$output = '';
					$data = unserialize($profilefield['data']);
					foreach ($data AS $index => $value)
					{
						if (intval($user[$profilefieldname]) & pow(2, $index))
						{
							if (!empty($output))
							{
								$output .= '<b>,</b> ';
							}
							$output .= $value;
						}
					}
					$cell[] = $output;
				}
				else
				{
					$cell[] = $user[$profilefieldname];
				}
			}
		}

		if (!empty(['options']))
		{
			$options = [];
			$options['edit'] = $vbphrase['view'] . " / " . $vbphrase['edit_user'];

			if (!empty($user['email']))
			{
			 	$options[unhtmlspecialchars($user['email'])] = $vbphrase['send_password_to_user'];
			}

			$options['kill'] = $vbphrase['delete_user'];
			$cell[] = construct_jump_control('ufind', $user['userid'],  $vbphrase['go'], $options);
		}
		print_cells_row2($cell);
	}

	construct_hidden_code('serializeduser', sign_client_string(serialize($vbulletin->GPC['user'])));
	construct_hidden_code('serializedprofile', sign_client_string(serialize($vbulletin->GPC['profile'])));
	construct_hidden_code('serializeddisplay', sign_client_string(serialize($vbulletin->GPC['display'])));
	construct_hidden_code('limitnumber', $vbulletin->GPC['limitnumber']);
	construct_hidden_code('orderby', $vbulletin->GPC['orderby']);
	construct_hidden_code('direction', $vbulletin->GPC['direction']);

	if ($vbulletin->GPC['limitstart'] == 0 AND $countusers > $vbulletin->GPC['limitnumber'])
	{
		construct_hidden_code('limitstart', $vbulletin->GPC['limitstart'] + $vbulletin->GPC['limitnumber'] + 1);
		print_submit_row($vbphrase['next_page'], 0, $colspan);
	}
	else if ($limitfinish < $countusers)
	{
		//note this is one based indexing which the next page will automatically adjust to 0 based internally
		construct_hidden_code('limitstart', $vbulletin->GPC['limitstart'] + $vbulletin->GPC['limitnumber'] + 1);
		print_submit_row($vbphrase['next_page'], 0, $colspan, $vbphrase['prev_page'], '', true);
	}
	else if ($vbulletin->GPC['limitstart'] > 0 AND $limitfinish >= $countusers)
	{
		print_submit_row($vbphrase['first_page'], 0, $colspan, $vbphrase['prev_page'], '', true);
	}
	else
	{
		print_table_footer();
	}
}

// ###################### Start moderate + coppa #######################
if ($_REQUEST['do'] == 'moderate')
{
	$vbulletin->input->clean_array_gpc('r', [
		'orderby'           => vB_Cleaner::TYPE_STR,
		'direction'         => vB_Cleaner::TYPE_STR,
		'page'              => vB_Cleaner::TYPE_UINT,
		'perpage'           => vB_Cleaner::TYPE_UINT,
	]);

	$orderby = $vbulletin->GPC['orderby'] ? $vbulletin->GPC['orderby'] : 'username';
	$direction = $vbulletin->GPC['direction'] ? $vbulletin->GPC['direction'] : 'ASC';
	$page =   ($vbulletin->GPC['page'] > 1) ? $vbulletin->GPC['page'] : 1;
	$perpage = $vbulletin->GPC['perpage'] ? $vbulletin->GPC['perpage'] : 25;
	$limitstart = $perpage * ($page - 1);
	$users = vB_Api::instanceInternal('user')->find(
		['usergroupid' => 4],
		[],
		$orderby,
		$direction,
		$limitstart,
		$perpage
	);
	if (empty($users['users']))
	{
		print_stop_message2('no_matches_found_gerror');
	}
	?>
	<script type="text/javascript">
	function js_check_radio(value)
	{
		// cpform may be a collection instead of a form if there are multiple submit forms on the page
		// with the same name.
		var forms = document.cpform;
		if (!(forms instanceof HTMLCollection))
		{
			forms = [forms];
		}
		for (let thisForm of forms)
		{
			for (var i = 0; i < thisForm.elements.length; i++)
			{
				var e = thisForm.elements[i];
				if (e.type == 'radio' && e.name.substring(0, 8) == 'validate')
				{
					e.checked = (e.value == value);
				}
			}
		}
	}
	</script>
	<?php
	print_form_header('admincp/user', 'domoderate');

	$headercells = array(
		$vbphrase['username'],
		$vbphrase['email'],
		$vbphrase['ip_address'],
		$vbphrase['join_date'],
		'<input type="button" class="button" value="' . $vbphrase['accept_all'] . '" onclick="js_check_radio(1)" /> ' .
		'<input type="button" class="button" value="' . $vbphrase['delete_all_gcpuser'] . '" onclick="js_check_radio(-1)" /> ' .
		'<input type="button" class="button" value="' . $vbphrase['ignore_all'] . '" onclick="js_check_radio(0)" />',
	);

	print_table_header($vbphrase['users_awaiting_moderation_gcpuser'], count($headercells));
	print_cells_row($headercells, 0, 'thead', -4);

	foreach ($users['users'] as $user)
	{
		$cells = array(
			"<a href=\"admincp/user.php?do=edit&amp;u=$user[userid]\" target=\"_user\"><b>$user[username]</b></a>",
			"<a href=\"mailto:$user[email]\">$user[email]</a>",
			"<a href=\"admincp/usertools.php?do=doips&amp;depth=2&amp;ipaddress=$user[ipaddress]&amp;hash=" . CP_SESSIONHASH . "\" target=\"_user\">$user[ipaddress]</a>",
			vbdate($vbulletin->options['dateformat'], $user['joindate']),
			"
				<label for=\"v_$user[userid]\"><input type=\"radio\" name=\"validate[$user[userid]]\" value=\"1\" id=\"v_$user[userid]\" tabindex=\"1\" />$vbphrase[accept]</label>
				<label for=\"d_$user[userid]\"><input type=\"radio\" name=\"validate[$user[userid]]\" value=\"-1\" id=\"d_$user[userid]\" tabindex=\"1\" />$vbphrase[delete]</label>
				<label for=\"i_$user[userid]\"><input type=\"radio\" name=\"validate[$user[userid]]\" value=\"0\" id=\"i_$user[userid]\" tabindex=\"1\" checked=\"checked\" />$vbphrase[ignore]</label>
			",
		);
		print_cells_row($cells, 0, '', -4);
	}
	$colspan = count($cells);

	// what is this? todo: remove?
	//$phraseAux = vB_Api::instanceInternal('phrase')->fetch(['validated']);
	//$template = $phraseAux['moderation_validated_gemailbody'];

	print_table_break();
	print_table_header($vbphrase['email_options']);
	print_yes_no_row($vbphrase['send_email_to_accepted_users'], 'send_validated', 1);
	print_yes_no_row($vbphrase['send_email_to_deleted_users'], 'send_deleted', 1);
	print_description_row($vbphrase['email_will_be_sent_in_user_specified_language']);

	print_table_break();
	print_submit_row($vbphrase['continue']);

	// Pagination
	$params = [
		'page' => $page,
		'perpage' => $perpage,
		'orderby' => $orderby,
		'direction' => $direction,
	];
	print_pagination_form('admincp/user', 'moderate', $params, $users['count']);
}


// ###################### Start do moderate and coppa #######################
if ($_POST['do'] == 'domoderate')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'send_validated' => vB_Cleaner::TYPE_INT,
		'send_deleted'	  => vB_Cleaner::TYPE_INT,
		'validate'       => vB_Cleaner::TYPE_ARRAY_INT,
	));

	if (empty($vbulletin->GPC['validate']))
	{
		print_stop_message2('please_complete_required_fields');
	}
	else
	{
		if ($vboptions['welcomepm'])
		{
			if ($fromuser = vB_User::fetchUserinfo($vboptions['welcomepm']))
			{
				cache_permissions($fromuser, false);
			}
		}

		$users = vB::getDbAssertor()->assertQuery('user', array(
			'userid' => array_keys($vbulletin->GPC['validate'])
		));

		$phraseApi =  vB_Api::instance('phrase');
		$string = vB::getString();
		$bbtitle_escaped = $string->htmlspecialchars($vboptions['bbtitle']);

		//only pull the route information once and only if needed
		$route = null;
		foreach ($users AS $user)
		{
			$status = $vbulletin->GPC['validate'][$user['userid']];
			$username = $user['username'];
			// I think the username will be useful here, because this is about account moderation & deletion, and particularly in the
			// latter case, they may not be able to easily pull up their user info.
			// Note that the "approved" email does include their username in the email, but not the "deleted".
			$userLabel = vB_User::getEmailUserLabel($user);

			$chosenlanguage = ($user['languageid'] < 1 ? intval($vboptions['languageid']) : intval($user['languageid']));

			if ($status == 1)
			{
				// validated
				// init user data manager
				$displaygroupid = ($user['displaygroupid'] > 0 AND $user['displaygroupid'] != $user['usergroupid']) ? $user['displaygroupid'] : 2;

				$userdata = new vB_DataManager_User($vbulletin, vB_DataManager_Constants::ERRTYPE_CP);
				$userdata->set_existing($user);
				$userdata->set('usergroupid', 2);
				$userdata->set_usertitle(
					$user['customtitle'] ? $user['usertitle'] : '',
					false,
					$vbulletin->usergroupcache["$displaygroupid"],
					($vbulletin->usergroupcache['2']['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canusecustomtitle']) ? true : false,
					false
				);
				//user save handles perm cache events and changes to user stats
				$userdata->save();


				if ($vbulletin->GPC['send_validated'])
				{
					if (!$route)
					{
						// When we start using anchors, we also need to escape these.
						$route = vB5_Route::buildUrl('home|fullurl');
						$settings = vB5_Route::buildUrl('settings|fullurl');
					}

					$mail = $phraseApi->fetchEmailPhrases(
						'moderation_validated',
						[
							$route,
							$username,
							$bbtitle_escaped,
							$settings,
							$userLabel,
						],
						[$vboptions['bbtitle']],
						$chosenlanguage
					);

					if (is_array($mail) AND isset($mail['errors']))
					{
						print_stop_message_array($users['errors']);
					}

					vB_Mail::vbmail2($user['email'], $mail['subject'], $mail['message'], true);
				}

				if ($vboptions['welcomepm'] AND $fromuser AND !$user['posts'])
				{
					// create the DM to do error checking and insert the new PM
					$userdata = new vB_DataManager_User(vB_DataManager_Constants::ERRTYPE_STANDARD);
					$userdata->set_existing($user);
					$userdata->send_welcomepm($fromuser, $user['userid']);
				}

				vB::getHooks()->invoke('hookUserModerationApproved', array(
					'userid' => $user['userid']
				));
			}
			else if ($status == -1)
			{
				// deleted
				if ($vbulletin->GPC['send_deleted'])
				{
					$mail = $phraseApi->fetchEmailPhrases(
						'moderation_deleted',
						[
							$userLabel,
							$bbtitle_escaped,
						],
						[$vboptions['bbtitle']],
						$chosenlanguage
					);

					if (is_array($mail) AND isset($mail['errors']))
					{
						print_stop_message_array($users['errors']);
					}

					vB_Mail::vbmail2($user['email'], $mail['subject'], $mail['message'], true);
				}

				$userdm = new vB_DataManager_User($vbulletin, vB_DataManager_Constants::ERRTYPE_SILENT);
				$userdm->set_existing($user);
				$userdm->delete();
				unset($userdm);
			} // else, do nothing
		}

		//if there are no more users go back to the user page
		$do = 'moderate';
		$users = vB_Api::instanceInternal('user')->find(array('usergroupid' => 4), array(), 'username', 'ASC');
		if (empty($users))
		{
			$do = 'modify';
		}

		print_stop_message2('user_accounts_validated', 'user', array('do' => $do));
	}
}

if (
	$_REQUEST['do'] == 'dopruneusers' OR
	$_REQUEST['do'] == 'pruneusers' OR
	$_REQUEST['do'] == 'prune'
)
{
	/*
	These are the expected inputs & their clean types for "pruneusers" search submit.
	This is used to help the pagination form (without ajax) to work properly.
	This set of data is also passed back and forth between "pruneusers" & "dopruneusers"
	handlers in order to allow the latter to return to the first page of the same search
	result after processing the selected list of users.
	 */
	$expectedInputsForPruneUsers = [
		'usergroupid' => vB_Cleaner::TYPE_INT,
		'includesecondary' => vB_Cleaner::TYPE_INT,
		'daysprune'   => vB_Cleaner::TYPE_INT,
		'minposts'    => vB_Cleaner::TYPE_INT,
		'joindate'    => vB_Cleaner::TYPE_ARRAY_UINT,
		'order'       => vB_Cleaner::TYPE_STR,
		'page'        => vB_Cleaner::TYPE_UINT,
		'perpage'     => vB_Cleaner::TYPE_UINT,
	];
}
else
{
	$expectedInputsForPruneUsers = [];
}

// ############################# do prune/move users (step 1) #########################
if ($_POST['do'] == 'dopruneusers')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'users'             => vB_Cleaner::TYPE_ARRAY_INT,
		'users_csv'         => vB_Cleaner::TYPE_STR,
		'dowhat'            => vB_Cleaner::TYPE_STR,
		'movegroup'         => vB_Cleaner::TYPE_INT,
		'return_to_pruneusers_params' => vB_Cleaner::TYPE_STR,
	));

	if (!empty($vbulletin->GPC['return_to_pruneusers_params']))
	{
		$data = json_decode($vbulletin->GPC['return_to_pruneusers_params'], true);
		$cleaner = vB::getCleaner();
		$vbulletin->GPC['return_to_pruneusers_params'] = $cleaner->cleanArray($data, $expectedInputsForPruneUsers);
	}
	else
	{
		$vbulletin->GPC['return_to_pruneusers_params'] = array();
	}

	if (empty($vbulletin->GPC['dowhat']))
	{
		print_stop_message2('user_prune_missing_action');
	}

	$userids = array();
	if (!empty($vbulletin->GPC['users_csv']))
	{
		$userids = explode(',', $vbulletin->GPC['users_csv']);
		$userids = array_map('intval', $userids);

	}
	else if (!empty($vbulletin->GPC['users']))
	{
		foreach ($vbulletin->GPC['users'] AS $key => $userid)
		{
			if ($val == 1 AND $userid != $vbulletin->userinfo['userid'])
			{
				$userids[] = $userid;
			}
		}
	}

	$userids = array_unique($userids);

	if (!empty($userids))
	{
		try
		{
			vB_Api::instanceInternal('user')->prune($userids, $vbulletin->GPC['dowhat'], $vbulletin->GPC['movegroup']);

			$args = $vbulletin->GPC['return_to_pruneusers_params'];
			// always go back to page 1.
			$args['page'] = 1;
			$args['do'] = 'pruneusers';
			if ($vbulletin->GPC['dowhat'] == 'delete')
			{
				echo '<p>' . $vbphrase['deleting_users'] . '</p>';
				print_stop_message2('updated_threads_posts_successfully','user', $args);
			}
			if ($vbulletin->GPC['dowhat'] == 'move')
			{
				echo $vbphrase['okay'] . '</p><p><b>' . $vbphrase['moved_users_successfully'] . '</b></p>';
				print_cp_redirect2('user', $args, 1, 'admincp');

			}

		}
		catch (vB_Exception_Api $e)
		{
			$errors = $e->get_errors();
			if (!empty($errors))
			{
				$error = array_shift($errors);
				print_stop_message2($error);
			}
			print_stop_message2('error');
		}

		$vbulletin->input->clean_array_gpc('r', array(
			'usergroupid' => vB_Cleaner::TYPE_INT,
			'daysprune'   => vB_Cleaner::TYPE_INT,
			'minposts'    => vB_Cleaner::TYPE_INT,
			'joindate'    => vB_Cleaner::TYPE_STR,
			'order'       => vB_Cleaner::TYPE_STR
		));


		print_stop_message2('invalid_action_specified_gcpglobal', 'user', array(
			'do' => 'pruneusers',
			'usergroupid' => $vbulletin->GPC['usergroupid'],
			'daysprune'   => $vbulletin->GPC['daysprune'],
			'minposts'    => $vbulletin->GPC['minposts'],
			'joindate'    => $vbulletin->GPC['joindate'],
			'order'       => $vbulletin->GPC['order'],
			'page'        => $vbulletin->GPC['page'] ?? 1,
			'perpage'     => $vbulletin->GPC['perpage'] ?? 20,
		));
	}
	else
	{
		print_stop_message2('please_complete_required_fields');
	}

}

// ############################# start list users for pruning #########################
if ($_REQUEST['do'] == 'pruneusers')
{
	$vbulletin->input->clean_array_gpc('r', $expectedInputsForPruneUsers);

	if (empty($vbulletin->GPC['page']))
	{
		$vbulletin->GPC['page'] = 1;
	}

	if (empty($vbulletin->GPC['perpage']))
	{
		// leave below commented out to support INFINITY view
		//$vbulletin->GPC['perpage'] = 20;
	}

	if (!empty($vbulletin->GPC['order']))
	{
		$userApi = vB_Api::instance('user');
		$result = $userApi->fetchPruneUsers(
 			$vbulletin->GPC['usergroupid'],
 			$vbulletin->GPC['includesecondary'],
			$vbulletin->GPC['daysprune'],
			$vbulletin->GPC['minposts'],
			$vbulletin->GPC['joindate'],
			$vbulletin->GPC['order'],
			$vbulletin->GPC['page'],
			$vbulletin->GPC['perpage']
		);

		if (is_array($result) AND isset($result['errors']))
		{
			print_stop_message_array($result['errors']);
		}
		else
		{
			// We can do this as of PHP 7.1 https://wiki.php.net/rfc/short_list_syntax
			// TODO Remove above comment and put it into ticket comment.
			['users' => $usersPrune, 'count' => $totalCount] = $result;
		}


		// Parameters to go back to the search page.
		if ($vbulletin->GPC['joindate']['month'] AND $vbulletin->GPC['joindate']['year'])
		{
			$joindateunix = mktime(0, 0, 0, $vbulletin->GPC['joindate']['month'], $vbulletin->GPC['joindate']['day'], $vbulletin->GPC['joindate']['year']);
		}
		else
		{
			$joindateunix = null;
		}
		$prunePageParams = array(
			'do' => 'prune',
			'usergroupid' => $vbulletin->GPC['usergroupid'],
			'daysprune' => $vbulletin->GPC['daysprune'],
			'joindateunix' => $joindateunix,
			'minposts' => $vbulletin->GPC['minposts'],
			'includesecondary' => $vbulletin->GPC['includesecondary'],
			// todo: order by
			'order' => $vbulletin->GPC['order'],
		);


		if ($totalCount)
		{
			register_js_phrase('you_may_not_delete_move_this_user');

			$groups = vB::getDbAssertor()->assertQuery('usergroup',
				[
					vB_dB_Query::CONDITIONS_KEY => [
						['field' => 'usergroupid','value' => [1,3,4,5,6], 'operator'=> vB_dB_Query::OPERATOR_NE],
					]
				],
				['field' => 'title', 'direction' => vB_dB_Query::SORT_ASC]
			);
			$groupslist = '';
			foreach ($groups AS $group)
			{
				$groupslist .= "\t<option value=\"$group[usergroupid]\">$group[title]</option>\n";
			}

			/*
			Pagination
			 */
			$leftControlHtml = get_goto_button('user', $prunePageParams, 'admincp', 'back_to_prune_users');
			$perpage = $vbulletin->GPC['perpage'];
			$curpage = max($vbulletin->GPC['page'], 1);
			if (!$perpage)
			{
				// show all
				// The math for start actually works out for either case but splitting it up
				// for readability.
				$start = 1;
				$end = $totalCount;
			}
			else
			{
				$start = ($curpage - 1) * $perpage + 1;
				// Don't overcount for last page.
				$end = min($curpage * $perpage, $totalCount);
			}
			$showingUsersXYZ = construct_phrase($vbphrase['showing_users_x_to_y_of_z'], $start, $end, $totalCount);

			$params = array_intersect_key($vbulletin->GPC, $expectedInputsForPruneUsers);
			$doSticky = true;
			print_pagination_form('admincp/user', 'pruneusers', $params, $totalCount, $leftControlHtml, $doSticky);

			print_form_header('admincp/user', 'dopruneusers');
			echo '<div class="hide js-serialize-form-data" data-source="users"></div>';

			construct_hidden_codes_from_params($params);
			construct_hidden_code('return_to_pruneusers_params', json_encode($params));

			print_table_header($showingUsersXYZ, 7);
			print_cells_row(array(
				'Userid',
				$vbphrase['username'],
				$vbphrase['email'],
				$vbphrase['post_count'],
				$vbphrase['last_activity'],
				$vbphrase['join_date'],
				'<input type="checkbox" name="allbox" onclick="js_check_all(this.form)" title="' . $vbphrase['check_all'] . '" checked="checked" />'
			), 1);

			foreach ($usersPrune as $user)
			{
				$cell = [];
				$cell[] = $user['userid'];
				$cell[] = "<a href=\"admincp/user.php?do=edit&u=$user[userid]\" target=\"_blank\">$user[username]</a><br /><span class=\"smallfont\">$user[title]" . ($user['moderatorid'] ? ", " . $vbphrase['moderator'] : "" ) . "</span>";
				$cell[] = "<a href=\"mailto:$user[email]\">$user[email]</a>";
				$cell[] = vb_number_format($user['posts']);
				$cell[] = vbdate($vbulletin->options['dateformat'], $user['lastactivity']);
				$cell[] = vbdate($vbulletin->options['dateformat'], $user['joindate']);
				if ($user['userid'] == $vbulletin->userinfo['userid'] OR $user['usergroupid'] == 6 OR $user['usergroupid'] == 5 OR $user['moderatorid'] OR is_unalterable_user($user['userid']))
				{
					$cell[] = '<input type="button" class="button js-prune-no-permission" value=" ! " />';
				}
				else
				{
					$cell[] = "<input type=\"checkbox\" name=\"users[]\" value=\"$user[userid]\" checked=\"checked\" tabindex=\"1\" />";
				}
				print_cells_row($cell);
			}

			print_description_row('<center><span class="smallfont">
				<b>' . $vbphrase['action'] . ':
				<label for="dw_delete"><input type="radio" name="dowhat" value="delete" id="dw_delete" tabindex="1" />' . $vbphrase['delete'] . '</label>
				<label for="dw_move"><input type="radio" name="dowhat" value="move" id="dw_move" tabindex="1" />' . $vbphrase['move_gcpglobal'] . '</label>
				<select name="movegroup" tabindex="1" class="bginput">' . $groupslist . '</select></b>
				</span></center>', 0, 7);
			print_submit_row($vbphrase['go'], $vbphrase['check_all'], 7);

			// TODO: Style this differently? Or do a list of page-links instead?
			if (!$doSticky)
			{
				print_pagination_form('admincp/user', 'pruneusers', $params, $totalCount, $leftControlHtml);
			}

			echo '<p>' . $vbphrase['this_action_is_not_reversible'] . '</p>';
		}
		else
		{
			print_stop_message2('no_users_matched_your_query','user', $prunePageParams);
		}
	}
	else
	{
		print_stop_message2('please_complete_required_fields');
	}
}


// ############################# start prune users #########################
if ($_REQUEST['do'] == 'prune')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'usergroupid'      => vB_Cleaner::TYPE_UINT,
		'daysprune'        => vB_Cleaner::TYPE_INT,
		'joindateunix'     => vB_Cleaner::TYPE_INT,
		'minposts'         => vB_Cleaner::TYPE_INT,
		'includesecondary' => vB_Cleaner::TYPE_BOOL,
		'order'            => vB_Cleaner::TYPE_STR,
	));

	print_form_header('admincp/user', 'pruneusers');
	print_table_header($vbphrase['user_moving_pruning_system']);
	print_description_row('<blockquote>' . $vbphrase['this_system_allows_you_to_mass_move_delete_users'] . '</blockquote>');

	$filter = [vB_dB_Query::CONDITIONS_KEY => [['field' => 'systemgroupid','value' => [1], 'operator'=> vB_dB_Query::OPERATOR_NE]]];
	print_chooser_row($vbphrase['usergroup'], 'usergroupid', 'usergroup', $vbulletin->GPC['usergroupid'], $vbphrase['all_usergroups'], 0, $filter);
	print_checkbox_row($vbphrase['include_secondary_groups'], 'includesecondary', $vbulletin->GPC['includesecondary']);
	print_input_row($vbphrase['has_not_logged_on_for_xx_days'], 'daysprune', ($vbulletin->GPC['daysprune'] ? $vbulletin->GPC['daysprune'] : 365));
	print_time_row($vbphrase['join_date_is_before'], 'joindate', $vbulletin->GPC['joindateunix'], false, false, 'middle');
	print_input_row($vbphrase['posts_is_less_than'], 'minposts', ($vbulletin->GPC['minposts'] ? $vbulletin->GPC['minposts'] : '0'));
	$orderFields = [
		'username'     => 'username',
		'email'        => 'email',
		'usergroup'    => 'usergroup',
		'posts'        => 'post_count',
		'lastactivity' => 'last_activity',
		'joindate'     => 'join_date',
	];
	$orderOptions = "";
	foreach ($orderFields AS $__key => $__phrase)
	{
		$__selected = $__key == $vbulletin->GPC['order'] ? ' selected ' : '';
		$orderOptions .= "\n<option value=\"{$__key}\"{$__selected}>{$vbphrase[$__phrase]}</option>";
	}
	print_label_row(
		$vbphrase['order_by_gcpglobal'],
		'<select name="order" tabindex="1" class="bginput">' . $orderOptions . '</select>',
		'',
		'top',
		'order'
	);
	construct_hidden_code('perpage', 100);
	print_submit_row($vbphrase['find']);
}

// ############################### send password email#############################
if ($_POST['do'] == 'do_emailpassword')
{
	$userid = vB::getCleaner()->clean($_REQUEST['userid'], vB_Cleaner::TYPE_UINT);
	$email = vB::getCleaner()->clean($_REQUEST['email'], vB_Cleaner::TYPE_STR);

	$userLib = vB_Library::instance('user');
	try
	{
		$userLib->sendPasswordEmail($userid, $email);
	}
	catch(vB_Exception_Api $e)
	{
		print_stop_message_array($e->get_errors());
	}

	echo $vbphrase['okay'] . '</p><p><b>' . vB_Phrase::fetchSinglePhrase('emails_sent_successfully') . '</b></p>';
	$args = array('do' => 'find');
	print_cp_redirect2('user', $args, true, 'admincp');
}

// ############################### process request activation email #############################
if ($_REQUEST['do'] == 'emailcode')
{
	$userid = vB::getCleaner()->clean($_REQUEST['userid'], vB_Cleaner::TYPE_UINT);

	$userLib = vB_Library::instance('user');
	try
	{
		//note that we skip sending a user back to "moderated" after activation even if
		//we normally would.  This is consistant with previous behavior going back a long
		//time, but it's not entirely clear if this is the correct behavior.
		$userLib->sendActivateEmail($userid, false);
	}
	catch(vB_Exception_Api $e)
	{
		print_stop_message_array($e->get_errors());
	}

	echo $vbphrase['okay'] . '</p><p><b>' . vB_Phrase::fetchSinglePhrase('emails_sent_successfully') . '</b></p>';
	$args = array('do' => 'find');
	print_cp_redirect2('user', $args, true, 'admincp');
}

// ############################# user change history #########################
if ($_REQUEST['do'] == 'changehistory')
{
	require_once(DIR . '/includes/class_userchangelog.php');

	$vbulletin->input->clean_array_gpc('r', [
		'userid' => vB_Cleaner::TYPE_UINT
	]);

	if ($vbulletin->GPC['userid'])
	{
		$datastore = vB::getDatastore();
		$userchangelog = new vB_UserChangeLog();

		// get the user change list
		$userchange_list = $userchangelog->sql_select_by_userid($vbulletin->GPC['userid']);

		if (!$userchange_list)
		{
			print_stop_message2('invalid_user_specified');
		}

		if ($userchange_list)
		{
			//start the printing
			print_table_start2();
			//not sure if we still need this
			print_column_style_code(['width: 30%;', 'width: 35%;', 'width: 35%;']);

			//the user info is repeated in each section and we don't have it otherwise.
			//rather than play tricks in the loop to print it once, handle it seperately.
			$headerinfo = reset($userchange_list);
			$url = construct_link_code2($headerinfo['username'], 'admincp/user.php?do=edit&userid=' . $headerinfo['userid'], false, '', 'smallfont');
			print_table_header($vbphrase['view_change_history'] . ' ' . $url, 3);

			$vbOptions = $datastore->getValue('options');
			$usergroups = vB_Api::instanceInternal('usergroup')->fetchUsergroupList();
			$usergroups = array_column($usergroups, 'title', 'usergroupid');

			// fetch the rows
			$change_uniq = '';
			foreach ($userchange_list AS $userchange)
			{
				// new change block, print a block header (empty line + header line)
				if ($change_uniq != $userchange['change_uniq'])
				{
					$text = [];
					$ipaddress = '';
					if ($userchange['ipaddress'])
					{
						$ipaddress = htmlspecialchars_uni($userchange['ipaddress']);
						$ipaddress = ' <span title="' . $vbphrase['ip_address'] . ': ' . $ipaddress . '">(' . $ipaddress . ')</span>';
					}
					$formattedTime = vbdate($vbOptions['timeformat'], $userchange['change_time']);
					$formattedDate = vbdate($vbOptions['dateformat'], $userchange['change_time']);

					$text[] = '<span title="' . $formattedTime . '">' . $formattedDate . ';</span> ' . $userchange['admin_username'] . $ipaddress;
					$text[] = $vbphrase['old_value'];
					$text[] = $vbphrase['new_value'];

					print_cells_row2($text, 'thead', 'vbleft');

					$change_uniq = $userchange['change_uniq'];
				}

				// get/find some names, depend on the field and the content
				switch ($userchange['fieldname'])
				{
					// get usergroup names from the cache
					case 'usergroupid':
					case 'membergroupids':
					{
						foreach (['oldvalue', 'newvalue'] AS $fname)
						{
							$titles = [];
							if ($userchange[$fname])
							{
								$ids = explode(',', $userchange[$fname]);
								foreach ($ids AS $id)
								{
									if (isset($usergroups[$id]))
									{
										$titles[] = $usergroups[$id];
									}
								}
							}
							$userchange[$fname] = ($titles ? implode('<br/>', $titles) . '<br/>' : '-');
						}

						break;
					}
				}

				// sometimes we need translate the fieldname to show the phrases (database field and phrase have different name)
				$fieldnametrans = [
					'usergroupid' => 'primary_usergroup',
					'membergroupids' => 'additional_usergroups',
				];

				if (isset($fieldnametrans[$userchange['fieldname']]))
				{
					$userchange['fieldname'] = $fieldnametrans[$userchange['fieldname']];
				}

				// print the change
				$text = [];
				$text[] = $vbphrase[$userchange['fieldname']];
				$text[] = $userchange['oldvalue'];
				$text[] = $userchange['newvalue'];
				print_cells_row2($text, '', 'vbleft');
			}
			print_table_footer();
		}
		else
		{
			print_stop_message2('no_userchange_history');
		}
	}
}


// #############################################################################
// find duplicate email users
if ($_REQUEST['do'] == 'findduplicateemails')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'limitstart'        => vB_Cleaner::TYPE_UINT,
		'limitnumber'       => vB_Cleaner::TYPE_UINT,
		//'direction'         => vB_Cleaner::TYPE_STR,
	));

	$limitstart = $vbulletin->GPC['limitstart'];
	$limitnumber = $vbulletin->GPC['limitnumber'];
	if (empty($limitnumber))
	{
		$limitnumber = 25;
	}
	$limitfinish = $limitstart + $limitnumber;

	$assertor = vB::getDbAssertor();
	$emails = $assertor->getRows('vBAdminCP:checkDuplicateEmails');
	$total_count = count($emails);

	$emails = array_slice($emails, $limitstart, $limitnumber);
	$current_count = count($emails);

	/*
		the limits won't help us with speeding up the query at all, so might as well do the limiting in PHP.
		The result set should be small on most sites.
	 */

	if ($total_count == 0)
	{
		// no users found!
		print_stop_message2('no_users_matched_your_query');
		print_cp_footer();
		return;
	}

	$header = array(
		$vbphrase['email'],
		$vbphrase['count'],
		$vbphrase['options'],
	);
	$colspan = sizeof($header);

	print_form_header('admincp/user', 'findduplicateemails');
	print_table_header(
		construct_phrase(
			$vbphrase['showing_emails_x_to_y_of_z'],
			$limitstart + 1,
			($limitfinish > $total_count) ? $total_count : $limitfinish,
			$total_count
		),
		$colspan
	);
	print_cells_row($header, 1);

	foreach ($emails AS $row)
	{
		$cell = array();
		$mailto_href = htmlspecialchars("mailto:" . rawurlencode($row['email']));
		$search_href = htmlspecialchars("admincp/user.php?do=find&user[exact_email]=1&user[email]=" . urlencode($row['email']));
		$cell[] = "<a href=\"$mailto_href\"><b>" . htmlspecialchars($row['email']) . "</b></a>";
		$cell[] = "<a href=\"$search_href\" title=\"" . $vbphrase['find_users'] . "\"><b>" .vb_number_format($row['count']) . "</b></a>";
		$cell[] = "<input type=\"button\" class=\"button\" tabindex=\"1\" value=\"" .
				$vbphrase['find_users'] . "\" onclick=\"vBRedirect('$search_href');\">";
		print_cells_row($cell);
	}


	construct_hidden_code('limitnumber', $limitnumber);
	if ($limitstart == 0 AND $total_count > $limitnumber)
	{
		construct_hidden_code('limitstart', $limitfinish);
		print_submit_row($vbphrase['next_page'], 0, $colspan);
	}
	else if ($limitfinish < $total_count)
	{
		construct_hidden_code('limitstart', $limitfinish);
		print_submit_row($vbphrase['next_page'], 0, $colspan, $vbphrase['prev_page'], '', true);
	}
	else if ($limitstart > 0 AND $limitfinish >= $total_count)
	{
		print_submit_row($vbphrase['first_page'], 0, $colspan, $vbphrase['prev_page'], '', true);
	}
	else
	{
		print_table_footer();
	}
}

print_cp_footer();

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 116252 $
|| #######################################################################
\*=========================================================================*/
