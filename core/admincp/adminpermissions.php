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
define('CVS_REVISION', '$RCSfile$ - $Revision: 111011 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
global $phrasegroups, $specialtemplates, $vbphrase, $vbulletin;
$phrasegroups = ['cppermission'];
$specialtemplates = [];

// ########################## REQUIRE BACK-END ############################
require_once(dirname(__FILE__) . '/global.php');

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

print_cp_header($vbphrase['administrator_permissions_manager']);

$vb5_config =& vB::getConfig();
$userContext = vB::getUserContext();
$limitedAdmin = [];
if (!$userContext->isSuperAdmin())
{
	if (!empty($vb5_config['SpecialUsers']['administrators']))
	{
		$adminUsers = preg_split('#\s*,\s*#s', $vb5_config['SpecialUsers']['administrators'], -1, PREG_SPLIT_NO_EMPTY);
		if (in_array($userContext->fetchUserId(), $adminUsers))
		{
			if (file_exists(DIR .'/includes/xml/administrator_permissions.xml'))
			{
				$parser = new vB_XML_Parser(false, DIR .'/includes/xml/administrator_permissions.xml');
				$xml = $parser->parse();
				$result = [];
				$bitfields = array_pop($xml);
				foreach($bitfields AS $bitfield)
				{
					$limitedAdmin[$bitfield['name']] = $bitfield['value'];
				}
			}
		}
	}

	if (!count($limitedAdmin))
	{
		print_stop_message2('sorry_you_are_not_allowed_to_edit_admin_permissions');
	}
}
// ############################# LOG ACTION ###############################
$vbulletin->input->clean_array_gpc('r', [
	'userid' => vB_Cleaner::TYPE_INT
]);

$assertor = vB::getDbAssertor();

$targetUserid = $vbulletin->GPC['userid'];
if ($targetUserid)
{
	$targetUserContext = vB::getUserContext($targetUserid);

	//user context converts invalid userids to guest.
	if(!$targetUserContext->fetchUserId())
	{
		print_stop_message2('no_matches_found_gerror');
	}

	$admininfo = $assertor->getRow('vBForum:administrator', ['userid' => $targetUserid]);
	if (!$admininfo)
	{
		if ($targetUserContext->isAdministrator())
		{
			$admindm = new vB_DataManager_Admin(vB_DataManager_Constants::ERRTYPE_SILENT);
			$admindm->set('userid', $targetUserid);
			$admindm->save();
			unset($admindm);
			$admininfo = $assertor->getRow('vBForum:administrator', ['userid' => $targetUserid]);
		}
		else
		{
			print_stop_message2('invalid_user_specified');
		}
	}

	$admindm = new vB_DataManager_Admin(vB_DataManager_Constants::ERRTYPE_SILENT);
	$admindm->set_existing($admininfo);

	$targetUser = vB_Api::instanceInternal('user')->fetchUserinfo($targetUserid);
}
else
{
	//these won't be referenced unless set above but make it clear to static analysis that they exist.
	$targetUserContext = null;
	$admindm = null;
	$targetUser = null;
	$admininfo = null;
}

require_once(DIR . '/includes/class_bitfield_builder.php');
$ADMINPERMISSIONS = [];
$permsphrase = [];
if (vB_Bitfield_Builder::build(false) !== false)
{
	$myobj = vB_Bitfield_Builder::init();
	foreach ($myobj->data['ugp']['adminpermissions'] AS $title => $values)
	{
		// don't show settings that have a group for the usergroup page
		if (empty($values['group']))
		{
			$ADMINPERMISSIONS[$title] = $values['value'];
			$permsphrase[$title] = $vbphrase[$values['phrase']];
		}
	}
}
else
{
	//This isn't likely to happen but it seems like we shouldn't keep going if it does.
	//But not quite sure enough of the implications to do something about it.
	//If we aren't going to continue make sure variables are defined.
	echo "<strong>error</strong>\n";
	print_r(vB_Bitfield_Builder::fetch_errors());
}

$vbulletin->input->clean_array_gpc('p', [
	'oldpermissions' 	 => vB_Cleaner::TYPE_INT,
	'adminpermissions' => vB_Cleaner::TYPE_ARRAY_INT
]);

$message = '';
if($targetUserid)
{
	$message = "user id = $targetUser[userid] ($targetUser[username])";
	if($_POST['do'] == 'update')
	{
		$message .= ' (' . $vbulletin->GPC['oldpermissions'] .' &raquo; ' . convert_array_to_bits($vbulletin->GPC['adminpermissions'], $ADMINPERMISSIONS) . ')';
	}
}
log_admin_action($message);

// #############################################################################

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'modify';
}

// #############################################################################

if ($_POST['do'] == 'update')
{
	if($targetUserid)
	{
		$vbulletin->input->clean_array_gpc('p', array(
			'cssprefs'      => vB_Cleaner::TYPE_STR,
			'dismissednews' => vB_Cleaner::TYPE_STR
		));

		//limited Admins can't edit super admins
		if (!$userContext->isSuperAdmin() AND $targetUserContext->isSuperAdmin())
		{
			print_cp_no_permission();
		}

		foreach ($ADMINPERMISSIONS as $key => $value)
		{
			//Does the current user have rights to set this?
			if (!count($limitedAdmin) OR isset($limitedAdmin[$key]))
			{
				$admindm->set_bitfield('adminpermissions', $key, $vbulletin->GPC['adminpermissions'][$key]);
			}
			else if (!empty($admininfo['adminpermissions']) AND ($admininfo['adminpermissions'] & $value))
			{
				$admindm->set_bitfield('adminpermissions', $key, $value);
			}
		}

		$admindm->set('cssprefs', $vbulletin->GPC['cssprefs']);
		$admindm->set('dismissednews', $vbulletin->GPC['dismissednews']);
		$admindm->save();

		vB_Cache::instance()->event('permissions_' . $admininfo['userid']);
		vB_Cache::instance()->event('userPerms_' . $admininfo['userid']);

		$extra = ['#' => "user$admininfo[userid]"];
		print_stop_message2('saved_administrator_permissions_successfully', 'adminpermissions', $extra);
	}
	else
	{
		print_stop_message2('invalid_user_specified');
	}
}

// #############################################################################

if ($_REQUEST['do'] == 'edit')
{
	if($targetUserid)
	{
		echo "<p align=\"center\">{$vbphrase['give_admin_access_arbitrary_html']}</p>";
		print_form_header('admincp/adminpermissions', 'update');
		construct_hidden_code('userid', $targetUserid);
		construct_hidden_code('oldpermissions', $admininfo['adminpermissions']);

		print_table_header(construct_phrase($vbphrase['x_y_id_z'], $vbphrase['administrator_permissions'], $targetUser['username'], $admininfo['userid']));

		$url = get_admincp_url('user', ['do' => 'edit', 'userid' => $targetUserid]);
		$value = '<div style="text-align: var(--vb-right);"><input type="button" class="button" value=" ' . $vbphrase['all_yes'] .
			' " onclick="js_check_all_option(this.form, 1);" /> <input type="button" class="button" value=" ' . $vbphrase['all_no'] . ' " onclick="js_check_all_option(this.form, 0);" /></div>';
		print_label_row("$vbphrase[administrator]: <a href=\"" . $url . "\">$targetUser[username]</a>", $value, 'thead');

		foreach (convert_bits_to_array($admininfo['adminpermissions'], $ADMINPERMISSIONS) AS $field => $value)
		{
			//skip bitfields this user can't set.
			if (count($limitedAdmin) AND !isset($limitedAdmin[$field]))
			{
				continue;
			}
			print_yes_no_row(($permsphrase["$field"] == '' ? $vbphrase['n_a'] : $permsphrase["$field"]), "adminpermissions[$field]", $value);
		}

		// Legacy Hook 'admin_permissions_form' Removed //

		print_select_row($vbphrase['control_panel_style_choice'], 'cssprefs', array_merge(array('' => "($vbphrase[default])"), fetch_cpcss_options()), $admininfo['cssprefs']);
		print_input_row($vbphrase['dismissed_news_item_ids'], 'dismissednews', $admininfo['dismissednews']);

		print_submit_row();
	}
	else
	{
		print_stop_message2('invalid_user_specified');
	}
}

// #############################################################################

if ($_REQUEST['do'] == 'modify')
{
	print_form_header('admincp/adminpermissions', 'edit');
	print_table_header($vbphrase['administrator_permissions'], 3);

	$users = $assertor->assertQuery('vBAdmincp:getAdminstrators', []);
	foreach($users AS $user)
	{
		$userid = $user['userid'];
		//if this user isn't an admin or the current user can't edit them then skip
		$thisContext = vB::getUserContext($userid);
		if (!$thisContext->isAdministrator() OR (!$userContext->isSuperAdmin() AND $thisContext->isSuperAdmin()))
		{
			continue;
		}

		$cells = [
			//not sure what the purpose of the name attribute is.
			'<a href="' . get_admincp_url('user', ['do' => 'edit', 'userid' => $userid]) . '" name="user' . $userid . '"><b>' . $user['username'] . '</b></a>',
			'-',
			construct_link_code2($vbphrase['view_control_panel_log'], get_admincp_url('adminlog', ['do' => 'view', 'userid' => $userid])) .
			construct_link_code2($vbphrase['edit_permissions'], get_admincp_url('adminpermissions', ['do' => 'edit', 'userid' => $userid]))
		];
		print_cells_row($cells, 0, '', 0);
	}

	print_table_footer();
}

print_cp_footer();

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 111011 $
|| #######################################################################
\*=========================================================================*/
