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

function get_area_data($area)
{
	if ($area == 'AdminCP')
	{
		$data = array(
			'userscript' => 'usertools.php',
			'base' => 'admincp',
			'useraction' => 'edit',
		);
	}
	else
	{
		$data = array(
			'userscript' => 'user.php',
			'base' => 'modcp',
			'useraction' => 'viewuser',
		);
	}
	return $data;
}


// ###################### Start doipaddress #######################
function construct_ip_usage_table($ipaddress, $prevuserid, $depth = 1)
{
	return construct_ip_table_internal($ipaddress, $prevuserid, $depth, 'postipusers');
}

// ###################### Start construct_ip_register_table #######################
function construct_ip_register_table($ipaddress, $prevuserid, $depth = 1)
{
	return construct_ip_table_internal($ipaddress, $prevuserid, $depth, 'regipusers');
}

function construct_ip_table_internal($ipaddress, $prevuserid, $depth, $key)
{
	global $vbphrase;

	$depth--;

	//we play some games to handle the ModCP vs Admincp versions.
	$data = get_area_data(VB_AREA);
	$userscript = $data['userscript'];
	$base = $data['base'];
	$useraction = $data['useraction'];

	$users = vB_Api::instanceInternal('user')->searchUsersByIP($ipaddress, $depth);

	//this isn't right but let's go with it for now.  We probably need to fix searchUsersByIp
	$users = current($users[$key]);
	if ($users)
	{
		$retdata = '';
		foreach ($users AS $user)
		{
			$viewuserurl = htmlspecialchars($base . '/user.php?do=' . $useraction . '&u=' . $user['userid']);
			$resolveaddressurl = htmlspecialchars("$base/$userscript?do=gethost&ip=$user[ipaddress]");
			$usersearchurl = htmlspecialchars(vB5_Route::buildUrl('search|fullurl', array(), array('searchJSON' => json_encode(array('authorid' => $user['userid'])))));
			$otheripurl =  htmlspecialchars("$base/$userscript?do=doips&u=$user[userid]&hash=" . CP_SESSIONHASH);

			$retdata .= '<li>' .
				construct_link_code('<b>' . $user['username']. '</b>', $viewuserurl, false, '', false, false) . '&nbsp; ' .
				construct_link_code($user['ipaddress'], $resolveaddressurl, false, $vbphrase['resolve_address'], false, false) . '&nbsp; ' .
				construct_link_code($vbphrase['find_posts_by_user'], $usersearchurl, true, '', false, false) .
				construct_link_code($vbphrase['view_other_ip_addresses_for_this_user'], $otheripurl, false, '', false, false) .
			"</li>\n";

			if ($depth > 0)
			{
				$retdata .= construct_user_ip_table($user['userid'], $user['ipaddress'], $depth);
			}
		}
	}

	if (empty($retdata))
	{
		return '';
	}
	else
	{
		return '<ul>' . $retdata . '</ul>';
	}
}

// ###################### Start douseridip #######################
function construct_user_ip_table($userid, $previpaddress, $depth = 2)
{
	global $vbphrase;

	//we play some games to handle the ModCP vs Admincp versions.
	$data = get_area_data(VB_AREA);
	$userscript = $data['userscript'];
	$base = $data['base'];

	$depth --;
	$ips = vB_Api::instanceInternal('user')->searchIP($userid, $depth);

	$ips = current($ips['postips']);
	if($ips)
	{
		$retdata = '';
		foreach ($ips AS $ip)
		{
			$ipurl = htmlspecialchars("$base/$userscript?do=gethost&ip=$ip[ipaddress]");
			$moreusersurl = htmlspecialchars("$base/$userscript?do=doips&ipaddress=$ip[ipaddress]&hash=" . CP_SESSIONHASH);

			$retdata .= '<li>' .
				construct_link_code($ip['ipaddress'], $ipurl, false, $vbphrase['resolve_address'], false, false) . '&nbsp; ' .
				construct_link_code($vbphrase['find_more_users_with_this_ip_address'], $moreusersurl, false, '', false, false) .
			"</li>\n";

			if ($depth > 0)
			{
				$retdata .= construct_ip_usage_table($ip['ipaddress'], $userid, $depth);
			}
		}
	}

	if (empty($retdata))
	{
		return '';
	}
	else
	{
		return '<ul>' . $retdata . '</ul>';
	}
}

// ###################### Start finduserhtml #######################
function print_user_search_rows($email = false)
{
	global $vbphrase;

	$controls = construct_input('text', 'user[username]', ['size' => 35]) .
		construct_input('submit', 'user[exact]]', ['class' => 'button', 'value' => $vbphrase['exact_match']]);
	print_label_row($vbphrase['username'], $controls, '', 'top', 'user[username]');

	if ($email)
	{
		$extra = [
			'class' => 'bginput js-checkbox-master',
			'data-child' => 'js-checkbox-all-child',
		];
		print_checkbox_row($vbphrase['all_usergroups'], 'usergroup_all', 0, -1, $vbphrase['all_usergroups'], $extra);

		$extra = [
			'class' => 'bginput js-checkbox-master',
			'data-child' => 'js-checkbox-canview-child',
		];
		print_checkbox_row($vbphrase['can_view_forum'], 'usergroup_canview', 0, -1, $vbphrase['can_view_forum'], $extra);

		$groups = vB_Library::instance('usergroup')->getGroupsWithPerm('forumpermissions', 'canview');
		$extraCallback = function($group) use ($groups) {
			if (in_array($group['usergroupid'], $groups))
			{
				return [
					'class' => 'bginput js-checkbox-all-child js-checkbox-canview-child',
				];
			}
			else
			{
				return [
					'class' => 'bginput js-checkbox-all-child',
				];
			}
		};
		print_membergroup_row($vbphrase['primary_usergroup'], 'user[usergroupid]', 2, [], $extraCallback);

		print_membergroup_row($vbphrase['additional_usergroups'], 'user[membergroup]', 2);
		print_yes_no_row($vbphrase['include_users_that_have_declined_email'], 'user[adminemail]', 0);

		$actionlabel =  $vbphrase['submit'];
	}
	else
	{
		print_chooser_row($vbphrase['primary_usergroup'], 'user[usergroupid]', 'usergroup', -1, '-- ' . $vbphrase['all_usergroups'] . ' --');
		print_membergroup_row($vbphrase['additional_usergroups'], 'user[membergroup]', 2);

		$actionlabel =  $vbphrase['find'];
	}

	print_middle_submit_row($actionlabel);
	print_input_row($vbphrase['email'], 'user[email]');
	print_input_row($vbphrase['parent_email_address'], 'user[parentemail]');
	print_yes_no_other_row($vbphrase['coppa_user'], 'user[coppauser]', $vbphrase['either'], -1);
	print_input_row($vbphrase['home_page_guser'], 'user[homepage]');
	print_yes_no_other_row($vbphrase['facebook_connected'], 'user[facebook]', $vbphrase['either'], -1);
	print_input_row($vbphrase['icq_uin'], 'user[icq]');
	print_input_row($vbphrase['yahoo_id'], 'user[yahoo]');
	print_input_row($vbphrase['skype_name'], 'user[skype]');
	print_input_row($vbphrase['signature'], 'user[signature]');
	print_input_row($vbphrase['user_title_guser'], 'user[usertitle]');
	print_input_row($vbphrase['join_date_is_after'] . $vbphrase['user_search_date_format_hint'], 'user[joindateafter]');
	print_input_row($vbphrase['join_date_is_before'] . $vbphrase['user_search_date_format_hint'], 'user[joindatebefore]');
	print_input_row($vbphrase['last_activity_is_after'] . $vbphrase['user_search_date_time_format_hint'], 'user[lastactivityafter]');
	print_input_row($vbphrase['last_activity_is_before'] . $vbphrase['user_search_date_time_format_hint'], 'user[lastactivitybefore]');
	print_input_row($vbphrase['last_post_is_after'] . $vbphrase['user_search_date_time_format_hint'], 'user[lastpostafter]');
	print_input_row($vbphrase['last_post_is_before'] . $vbphrase['user_search_date_time_format_hint'], 'user[lastpostbefore]');
	print_input_row($vbphrase['birthday_is_after'] . $vbphrase['user_search_date_format_hint'], 'user[birthdayafter]');
	print_input_row($vbphrase['birthday_is_before'] . $vbphrase['user_search_date_format_hint'], 'user[birthdaybefore]');
	print_input_row($vbphrase['posts_are_greater_than'], 'user[postslower]', '', 1, 7);
	print_input_row($vbphrase['posts_are_less_than'], 'user[postsupper]', '', 1, 7);
	print_input_row($vbphrase['reputation_is_greater_than'], 'user[reputationlower]', '', 1, 7);
	print_input_row($vbphrase['reputation_is_less_than'], 'user[reputationupper]', '', 1, 7);
	print_input_row($vbphrase['warnings_are_greater_than'], 'user[warningslower]', '', 1, 7);
	print_input_row($vbphrase['warnings_are_less_than'], 'user[warningsupper]', '', 1, 7);
	print_input_row($vbphrase['infractions_are_greater_than'], 'user[infractionslower]', '', 1, 7);
	print_input_row($vbphrase['infractions_are_less_than'], 'user[infractionsupper]', '', 1, 7);
	print_input_row($vbphrase['infraction_points_are_greater_than'], 'user[pointslower]', '', 1, 7);
	print_input_row($vbphrase['infraction_points_are_less_than'], 'user[pointsupper]', '', 1, 7);
	print_input_row($vbphrase['userid_is_greater_than'], 'user[useridlower]', '', 1, 7);
	print_input_row($vbphrase['userid_is_less_than'], 'user[useridupper]', '', 1, 7);
	print_input_row($vbphrase['registration_ip_address'], 'user[ipaddress]');

	// privacy consent search fields
	print_yes_no_other_row($vbphrase['admincp_privacyconsent_required_label'], 'user[eustatus_check]', $vbphrase['either'], -1);
	print_radio_row(
		$vbphrase['admincp_privacyconsent_status_label'],
		'user[privacyconsent]',
		[
			'1' => $vbphrase['admincp_privacyconsent_provided'],
			'-1' => $vbphrase['admincp_privacyconsent_withdrawn'],
			'0' => $vbphrase['admincp_privacyconsent_unknown'],
			'any' => $vbphrase['admincp_privacyconsent_any'],
		],
		'any'
	);
	print_input_row($vbphrase['admincp_privacyconsentupdated_after'] . $vbphrase['user_search_date_format_hint'], 'user[privacyconsentupdatedafter]');
	print_input_row($vbphrase['admincp_privacyconsentupdated_before'] . $vbphrase['user_search_date_format_hint'], 'user[privacyconsentupdatedbefore]');
	print_middle_submit_row($actionlabel);

	print_table_header($vbphrase['user_profile_fields']);

	$profilefields = vB::getDbAssertor()->assertQuery('fetchProfileFields');
	foreach ($profilefields AS $profilefield)
	{
		print_profilefield_row('profile', $profilefield);
	}
	print_middle_submit_row($actionlabel);
}

/**
 *	Prints a submit button in the middle of the form to avoid forcing a scroll down on long forms.
 *
 *	Unlike print_submit_row, it does not close the form/table
 */
//this is potentially more general but for the moment I'm only seeing the idiom in the user code
//and it's not entirely clear if all uses are going to be identical (they should be consolidated
//and regularlized where possible).  Leaving it with the user functions until I'm sure it
//generalizes better.
function print_middle_submit_row($submitname, $colspan = 2)
{
	$logicalright = vB_Template_Runtime::fetchStyleVar('right');
	$submitdescription = '<div align="' . $logicalright .'"><input type="submit" class="button" value=" ' . $submitname . ' " tabindex="1" /></div>';
	print_description_row($submitdescription, false, $colspan);
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 114349 $
|| #######################################################################
\*=========================================================================*/
