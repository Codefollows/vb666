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
define('CVS_REVISION', '$RCSfile$ - $Revision: 116273 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
global $phrasegroups, $specialtemplates, $vbphrase, $vbulletin;
$phrasegroups = array('cppermission', 'forum');
$specialtemplates = array();

// ########################## REQUIRE BACK-END ############################
require_once(dirname(__FILE__) . '/global.php');

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!can_administer('canadminpermissions'))
{
	print_cp_no_permission();
}

// ############################# LOG ACTION ###############################

$vbulletin->input->clean_array_gpc('r', array(
	'np'	=> vB_Cleaner::TYPE_INT,
	'n'		=> vB_Cleaner::TYPE_INT,
	'u'		=> vB_Cleaner::TYPE_INT
));

if ($vbulletin->GPC['np'] != 0)
{
	$logmessage = 'nodepermission id = ' . $vbulletin->GPC['np'];
}
else if ($vbulletin->GPC['n'] != 0)
{
	$logmessage = 'node id = ' . $vbulletin->GPC['n'];
}
else if ($vbulletin->GPC['u'] != 0)
{
	$logmessage = ' / usergroup id = ' . $vbulletin->GPC['u'];
}
else
{
	$logmessage = '';
}

log_admin_action($logmessage);

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

// Load defaultchannelpermissions datastore as its not loaded by default
vB::getDatastore()->fetch('defaultchannelpermissions');

print_cp_header($vbphrase['channel_permissions_manager']);

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'modify';
}

// ###################### Start edit #######################
if ($_REQUEST['do'] == 'edit')
{
	$nodeid =& $vbulletin->GPC['n'];
	$usergroupid =& $vbulletin->GPC['u'];
	$permissionid =& $vbulletin->GPC['np'];

	?>
	<script type="text/javascript">
	function js_set_custom()
	{
		// Channel "Home" does not have the "inherit" radios.
		if (document.cpform.inherit && document.cpform.inherit[1].checked == false)
		{
			if (confirm("<?php echo $vbphrase['must_enable_custom_permissions']; ?>"))
			{
				document.cpform.inherit[1].checked = true;
				return true;
			}
			else
			{
				return false;
			}
		}
		else
		{
			return true;
		}
	}
	</script>
	<?php

	print_form_header('admincp/forumpermission', 'doupdate');

	if (!empty($permissionid))
	{
		$nodepermission = vB_ChannelPermission::instance()->fetchPermById($permissionid);
		$nodepermission = current($nodepermission);
		$nodeid = $nodepermission['nodeid'];
		$usergroupid = $nodepermission['groupid'];
	}
	else if (!empty($nodeid) AND !empty($usergroupid))
	{
		$nodepermission = vB_ChannelPermission::instance()->fetchPermissions($nodeid, $usergroupid);
		$nodepermission = current($nodepermission);
	}
	else
	{
		print_table_footer();
		print_stop_message2('invalid_channel_permissions_specified');
	}

	if (empty($nodepermission) OR !empty($nodepermission['errors']))
	{
		print_table_footer();
		print_stop_message2('invalid_channel_permissions_specified');
	}

	construct_hidden_code('nodepermission[usergroupid]', $usergroupid);
	construct_hidden_code('nodeid', $nodeid);
	construct_hidden_code('permissionid', $nodepermission['permissionid'] ?? '');
	$channel = vB_Library::instance('node')->getNode($nodeid);
	$usergroup = vB_Api::instance('usergroup')->fetchUsergroupByID($usergroupid);
	if (isset($usergroup['errors']))
	{
		print_stop_message2($usergroup['errors'][0]);
	}

	print_table_header(construct_phrase($vbphrase['edit_channel_permissions_for_usergroup_x_in_channel_y'], $usergroup['title'], $channel['title']));
	if ($nodeid > 1)
	{
		$inherit = '<label for="inherit_1"><input type="radio" name="inherit" value="1" id="inherit_1" onclick="this.form.reset(); this.checked=true;"' .
			(empty($permissionid) ? ' checked="checked"': '') . ' />' . $vbphrase['inherit_channel_permission'] . '</label>';

		$custom = '	<label for="inherit_0"><input type="radio" name="inherit" value="0" id="inherit_0"' .
			(!empty($permissionid) ? ' checked="checked"' : '') . ' />' . $vbphrase['use_custom_permissions'] . '</label>';

		print_description_row($inherit . '<br />' . $custom, false, 2, '', '' , 'mode');
		print_table_break();
	}
	print_channel_permission_rows($vbphrase['edit_channel_permissions'], $nodepermission, ['add_customized_perm_check' => true, ]);

	?>
	<script type="text/javascript">
	{
		let triggers = document.querySelectorAll(".js-set-custom-on-row-change--trigger"),
			inputChangeHandler = evt => {
				if (!js_set_custom.call(evt.currentTarget))
				{
					evt.preventDefault();
					evt.stopPropagation();
				}
			};
		triggers.forEach(el => {
			// "input" event is not cancellable, so attach to keydown for text & click for radio/check.
			// Previously this check was only present on radio etc via the "onclick" attribute.
			el.addEventListener(el.type == "text" ? "keydown" : "click", inputChangeHandler);
		});
	}
	</script>
	<?php


	print_submit_row($vbphrase['save']);

}

// ###################### Start do update #######################
if ($_POST['do'] == 'doupdate')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'permissionid'	=> vB_Cleaner::TYPE_INT,
		'nodepermission'	=> vB_Cleaner::TYPE_ARRAY_INT,	// Its only ever refrenced as an array would be
		'useusergroup' 		=> vB_Cleaner::TYPE_INT,
		'nodeid' 			=> vB_Cleaner::TYPE_INT,
		'inherit'			=> vB_Cleaner::TYPE_INT
	));

	if ($vbulletin->GPC_exists['permissionid'] AND intval($vbulletin->GPC['permissionid']))
	{
		$groupid = 0;
		$nodeid = 0;
		$params = array('permissionid' => $vbulletin->GPC['permissionid']);
	}
	else if ($vbulletin->GPC_exists['permissionid'] AND $vbulletin->GPC_exists['nodepermission'])
	{
		$groupid =  $vbulletin->GPC['nodepermission']['usergroupid'];
		$nodeid = $vbulletin->GPC['nodeid'];
		$params = array('nodeid' => $nodeid, 'groupid' => $groupid);
		if (!intval($groupid) OR !intval($nodeid))
		{
			print_table_footer();
			print_stop_message2('invalid_usergroup_specified');
		}
	}

	if ($vbulletin->GPC_exists['inherit'] AND intval($vbulletin->GPC['inherit']))
	{
		vB_ChannelPermission::instance()->deletePerms($params);
	}
	else
	{
		$result = vB_ChannelPermission::instance()->setPermissions($nodeid, $groupid, $_POST);
	}
	print_stop_message2('saved_channel_permissions_successfully', 'forumpermission', array(
		'do' => 'modify',
		'n'  => $nodeid .'#node' . $nodeid
	));

}

// ###################### Start duplicator #######################
if ($_REQUEST['do'] == 'duplicate')
{
	$ugarr = vB::getDbAssertor()->getColumn('fetchpermgroups', 'title', [], false, 'usergroupid');
	if (!empty($ugarr))
	{
		$usergroups = vB_Api::instance('usergroup')->fetchUsergroupList();
		print_stop_message_on_api_error($usergroups);

		$usergrouplist = [];
		foreach($usergroups AS $usergroup)
		{
			$usergrouplist[] = "<input type=\"checkbox\" name=\"usergrouplist[$usergroup[usergroupid]]\" value=\"1\" /> $usergroup[title]";
		}

		$usergrouplist = implode("<br />\n", $usergrouplist);

		print_form_header('admincp/forumpermission', 'doduplicate_group');
		print_table_header($vbphrase['usergroup_based_permission_duplicator']);
		print_select_row($vbphrase['copy_permissions_from_group'], 'ugid_from', $ugarr);
		print_label_row($vbphrase['copy_permissions_to_groups'], "<span class=\"smallfont\">$usergrouplist</span>", '', 'top', 'usergrouplist');
		print_channel_chooser($vbphrase['only_copy_permissions_from_channel'], 'limitnodeid', 0);
		print_yes_no_row($vbphrase['overwrite_duplicate_entries'], 'overwritedupes_group', 0);
		print_yes_no_row($vbphrase['overwrite_inherited_entries'], 'overwriteinherited_group', 0);
		print_submit_row($vbphrase['go']);
	}

	// generate forum check boxes
	$channellist = [];
	$channels = vB_Api::instanceInternal('search')->getChannels(true);
	foreach($channels AS $nodeid => $channel)
	{
		$depth = str_repeat('--', $channel['depth']);
		$channellist[] = "<input type=\"checkbox\" name=\"channellist[$channel[nodeid]]\" value=\"1\" tabindex=\"1\" />$depth $channel[htmltitle] ";
	}
	$channellist = implode("<br />\n", $channellist);

	print_form_header('admincp/forumpermission', 'doduplicate_channel');
	print_table_header($vbphrase['channel_based_permission_duplicator']);
	print_channel_chooser($vbphrase['copy_permissions_from_channel'], 'nodeid_from', 0);
	print_label_row($vbphrase['copy_permissions_to_channels'], "<span class=\"smallfont\">$channellist</span>", '', 'top', 'channellist');
	//print_chooser_row($vbphrase['only_copy_permissions_from_group'], 'limitugid', 'usergroup', -1, $vbphrase['all_usergroups']);
	print_yes_no_row($vbphrase['overwrite_duplicate_entries'], 'overwritedupes_channel', 0);
	print_yes_no_row($vbphrase['overwrite_inherited_entries'], 'overwriteinherited_channel', 0);
	print_submit_row($vbphrase['go']);

}

// ###################### Start do duplicate (group-based) #######################
if ($_POST['do'] == 'doduplicate_group')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'ugid_from' 				=> vB_Cleaner::TYPE_INT,
		'limitnodeid' 				=> vB_Cleaner::TYPE_INT,
		'overwritedupes_group' 		=> vB_Cleaner::TYPE_INT,
		'overwriteinherited_group' 	=> vB_Cleaner::TYPE_INT,
		'usergrouplist' 			=> vB_Cleaner::TYPE_ARRAY
	));
	if (sizeof($vbulletin->GPC['usergrouplist']) == 0)
	{
		print_stop_message2('invalid_usergroup_specified');
	}

	$assertor = vB::getDbAssertor();

	foreach ($vbulletin->GPC['usergrouplist'] AS $ugid_to => $confirm)
	{
		$ugid_to = intval($ugid_to);
		if ($vbulletin->GPC['ugid_from'] == $ugid_to OR $confirm != 1)
		{
			continue;
		}

		$params = ['groupid' => $ugid_to];
		$queryid = 'fetchExistingPermsForGroup';
		if ($vbulletin->GPC['limitnodeid'] > 1)
		{
			$params['parentid'] = $vbulletin->GPC['limitnodeid'];
			$queryid = 'fetchExistingPermsForGroupLimit';
		}

		// get existing permissions
		$result = $assertor->assertQuery($queryid, $params);
		$perm_set = [];
		foreach($result AS $permission)
		{
			$perm_set[] = $permission['nodeid'];
		}

		$perm_inherited = [];
		if (sizeof($perm_set) > 0)
		{
			$result = $assertor->assertQuery('vBForum:closure', [
					vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
					vB_dB_Query::CONDITIONS_KEY => [
						['field' => 'parent', 'value' => $perm_set, 'operator' => vB_dB_Query::OPERATOR_EQ],
						['field' => 'depth',	'value' => 0, 'operator' => vB_dB_Query::OPERATOR_GT],
					],
			]);
			foreach($result AS $child)
			{
				$perm_inherited[] = $child['child'];
			}
		}

		$condition = [
			['field' => 'groupid', 'value' => $vbulletin->GPC['ugid_from'], 'operator' => vB_dB_Query::OPERATOR_EQ]
		];
		if (!$vbulletin->GPC['overwritedupes_group'] OR !$vbulletin->GPC['overwriteinherited_group'])
		{
			$exclude = ['1'];
			if (!$vbulletin->GPC['overwritedupes_group'])
			{
				$exclude = array_merge($exclude, $perm_set);
			}
			if (!$vbulletin->GPC['overwriteinherited_group'])
			{
				$exclude = array_merge($exclude, $perm_inherited);
			}
			$exclude = array_unique($exclude);
			$condition[] = ['field'=>'nodeid',	'value'=>$exclude, 'operator' => vB_dB_Query::OPERATOR_NE];
		}

		if ($vbulletin->GPC['limitnodeid'] > 0)
		{
			$result = $assertor->assertQuery('vBForum:closure', [
				vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
				vB_dB_Query::CONDITIONS_KEY => [
					['field' => 'parent', 'value' => $vbulletin->GPC['limitnodeid'], 'operator' => vB_dB_Query::OPERATOR_EQ],
					['field' => 'depth', 'value' => 0, 'operator' => vB_dB_Query::OPERATOR_GT],
				],
			]);

			$children = [];
			foreach($result AS $child)
			{
				$children[] = $child['child'];
			}
			if (!empty($children))
			{
				$condition[] = ['field'=>'nodeid', 'value'=> $children, 'operator' => vB_dB_Query::OPERATOR_EQ];
			}
			else
			{
				$condition[] = ['field'=>'nodeid', 'operator' => vB_dB_Query::OPERATOR_ISNULL];
			}
		}

		$result = $assertor->assertQuery('vBForum:permission', [vB_dB_Query::CONDITIONS_KEY => $condition]);
		foreach($result AS $permission)
		{
			$assertor->assertQuery('replacePermissions', [
				'nodeid' => $permission['nodeid'],
				'usergroupid' => $ugid_to,
				'forumpermissions' => $permission['forumpermissions'],
				'moderatorpermissions' => $permission['moderatorpermissions'],
				'createpermissions' => $permission['createpermissions'],
				'forumpermissions2' => $permission['forumpermissions2'],
				'edit_time' => $permission['edit_time'],
				'maxtags' => $permission['maxtags'],
				'maxstartertags' => $permission['maxstartertags'],
				'maxothertags' => $permission['maxothertags'],
				'maxattachments' => $permission['maxattachments'],
				'maxchannels' => $permission['maxchannels'],
				'channeliconmaxsize' => $permission['channeliconmaxsize'],
			]);
		}
	}
	vB::getUserContext()->rebuildGroupAccess();
	print_stop_message2('duplicated_permissions_successfully', 'forumpermission', ['do'=>'modify']);
}

// ###################### Start do duplicate (forum-based) #######################
if ($_POST['do'] == 'doduplicate_channel')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'nodeid_from'					=> vB_Cleaner::TYPE_INT,
		'overwritedupes_channel'		=> vB_Cleaner::TYPE_INT,
		'overwriteinherited_channel'	=> vB_Cleaner::TYPE_INT,
		'channellist' 					=> vB_Cleaner::TYPE_ARRAY
	));

	if (sizeof($vbulletin->GPC['channellist']) == 0)
	{
		print_stop_message2('invalid_channel_specified');
	}

	$assertor = vB::getDbAssertor();

	$copyperms = $assertor->getRows('vBForum:permission', ['nodeid' => $vbulletin->GPC['nodeid_from']], false, 'groupid');
	if (!$copyperms)
	{
		print_stop_message2('no_permissions_set');
	}

	$permscache = [];
	if (!$vbulletin->GPC['overwritedupes_channel'] OR !$vbulletin->GPC['overwriteinherited_channel'])
	{
		// query channel permissions
		$result = $assertor->assertQuery('fetchinherit', []);
		foreach($result AS $permission)
		{
			$permscache[$permission['nodeid']][$permission['groupid']] = $permission['inherited'];
		}
	}

	foreach ($vbulletin->GPC['channellist'] AS $nodeid_to => $confirm)
	{
		$nodeid_to = intval($nodeid_to);
		if ($nodeid_to == $vbulletin->GPC['nodeid_from'] OR !$confirm)
		{
			continue;
		}
		foreach ($copyperms AS $usergroupid => $permission)
		{
			if (!$vbulletin->GPC['overwritedupes_channel'] AND isset($permscache["$nodeid_to"]["$usergroupid"]) AND $permscache["$nodeid_to"]["$usergroupid"] == 0)
			{
				continue;
			}
			if (!$vbulletin->GPC['overwriteinherited_channel'] AND $permscache["$nodeid_to"]["$usergroupid"] == 1)
			{
				continue;
			}
			/*insert query*/
			$assertor->assertQuery('replacePermissions', array(
				vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_STORED,
				'nodeid' => $nodeid_to,
				'usergroupid' => $usergroupid,
				'forumpermissions' => $permission['forumpermissions'],
				'moderatorpermissions' => $permission['moderatorpermissions'],
				'createpermissions' => $permission['createpermissions'],
				'forumpermissions2' => $permission['forumpermissions2'],
				'edit_time' => $permission['edit_time'],
				'maxtags' => $permission['maxtags'],
				'maxstartertags' => $permission['maxstartertags'],
				'maxothertags' => $permission['maxothertags'],
				'maxattachments' => $permission['maxattachments'],
				'maxchannels' => $permission['maxchannels'],
				'channeliconmaxsize' => $permission['channeliconmaxsize'],
			));
		}
	}

	vB::getUserContext()->rebuildGroupAccess();

	print_stop_message2('duplicated_permissions_successfully', 'forumpermission', array('do'=>'modify'));
}

// ###################### Start quick edit #######################
if ($_REQUEST['do'] == 'quickedit')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'orderby' => vB_Cleaner::TYPE_STR
	));

	print_form_header('admincp/forumpermission', 'doquickedit');
	print_table_header($vbphrase['permissions_quick_editor'], 4);
	print_cells_row(array(
		'<input type="checkbox" name="allbox" title="' . $vbphrase['check_all'] . '" onclick="js_check_all(this.form);" />',
		"<a href=\"admincp/forumpermission.php?do=quickedit&amp;orderby=channel\" title=\"" . $vbphrase['order_by_channel'] . "\">" . $vbphrase['channel'] . "</a>",
		"<a href=\"admincp/forumpermission.php?do=quickedit&amp;orderby=usergroup\" title=\"" . $vbphrase['order_by_usergroup'] . "\">" . $vbphrase['usergroup'] . "</a>",
		$vbphrase['controls']
	), 1);

	$result = vB::getDbAssertor()->assertQuery('fetchperms', [
		'order_first' => ($vbulletin->GPC['orderby'] == 'usergroup' ? 'usergroup' : 'node')
	]);

	if($result->valid())
	{
		foreach($result AS $perm)
		{
			print_cells_row([
				"<input type=\"checkbox\" name=\"permission[$perm[permissionid]]\" value=\"1\" tabindex=\"1\" />",
				$perm['node_title'],
				$perm['ug_title'],
				construct_link_code($vbphrase['edit'], "forumpermission.php?do=edit&amp;np=$perm[permissionid]"),
			]);
		}
		print_submit_row($vbphrase['delete_selected_permissions'], $vbphrase['reset'], 4);
	}
	else
	{
		print_description_row($vbphrase['nothing_to_do'], 0, 4, '', 'center');
	}

	print_table_footer();
}

// ###################### Start do quick edit #######################
if ($_POST['do'] == 'doquickedit')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'permission' => vB_Cleaner::TYPE_ARRAY
	));

	if (sizeof($vbulletin->GPC['permission'])  == 0)
	{
		print_stop_message2('nothing_to_do');
	}

	$removeids = array();
	foreach ($vbulletin->GPC['permission'] AS $permissionid => $confirm)
	{
		if ($confirm == 1)
		{
			$removeids[] = intval($permissionid);
		}
	}

	$result = vB::getDbAssertor()->assertQuery('permission', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_DELETE, 'permissionid' => $removeids));

	vB::getUserContext()->rebuildGroupAccess();

	print_stop_message2('deleted_forum_permissions_successfully', 'forumpermission', array('do'=>'modify'));
}

// ###################### Start quick forum setup #######################
if ($_REQUEST['do'] == 'quickforum')
{
	$usergroups = vB_Api::instance('usergroup')->fetchUsergroupList();
	if (isset($usergroups['errors']))
	{
		print_stop_message2($usergroups['errors'][0]);
	}

	$usergrouplist = array();
	foreach($usergroups AS $usergroup)
	{
		$usergrouplist[] = "<input type=\"checkbox\" name=\"usergrouplist[$usergroup[usergroupid]]\" id=\"usergrouplist_$usergroup[usergroupid]\" value=\"1\" tabindex=\"1\" /><label for=\"usergrouplist_$usergroup[usergroupid]\">$usergroup[title]</label>";
	}
	$usergrouplist = implode('<br />', $usergrouplist);

	print_form_header('admincp/forumpermission', 'doquickforum');
	print_table_header($vbphrase['quick_channel_permission_setup']);
	print_channel_chooser($vbphrase['apply_permissions_to_channel'], 'nodeid', 0);
	print_label_row($vbphrase['apply_permissions_to_usergroup'], "<span class=\"smallfont\">$usergrouplist</span>", '', 'top', 'usergrouplist');
	print_description_row($vbphrase['permission_overwrite_notice']);

	print_table_break();
	print_channel_permission_rows($vbphrase['permissions'], [], ['onclick' => '', 'add_disable_checkbox' => true,]);
	print_submit_row();

	// we need the JS after the elements if we don't want to delegate the events (not as trivial without jquery)
	?>
	<script type="text/javascript">
		{
			let uncheckTriggers = document.querySelectorAll(".js-uncheck-on-row-change--trigger");
			uncheckTriggers.forEach(el => {
				el.addEventListener("input", (evt) => {
					js_uncheck_on_row_change.call(evt.currentTarget);
				});
			});
		}

		function js_uncheck_on_row_change(newchecked = false, all = false)
		{
			var disableButton;
			if (!all)
			{
				disableButton = document.querySelector(`input.js-uncheck-on-row-change[data-trigger-name="${this.name}"]`);
				if (disableButton)
				{
					disableButton.checked = newchecked;
				}
			}
			else
			{
				disableButton = document.querySelectorAll(`input.js-uncheck-on-row-change`);
				disableButton.forEach(el => {
					el.checked = newchecked;
				});
			}
		}
	</script>
	<?php
}

// ###################### Start do quick forum #######################
if ($_POST['do'] == 'doquickforum')
{
	$vbulletin->input->clean_array_gpc('p', [
		'usergrouplist'     => vB_Cleaner::TYPE_ARRAY,
		'nodeid'            => vB_Cleaner::TYPE_INT,
		'forumpermissions'  => vB_Cleaner::TYPE_ARRAY_INT,
		'forumpermissions2' => vB_Cleaner::TYPE_ARRAY_INT,
		'moderatorpermissions' => vB_Cleaner::TYPE_ARRAY_INT,
		'createpermissions' => vB_Cleaner::TYPE_ARRAY_INT,
		'edit_time'         => vB_Cleaner::TYPE_STR, //this will be validated in setPermissions
		'maxtags'           => vB_Cleaner::TYPE_INT,
		'maxstartertags'    => vB_Cleaner::TYPE_INT,
		'maxothertags'      => vB_Cleaner::TYPE_INT,
		'maxattachments'    => vB_Cleaner::TYPE_INT,
		'maxchannels'       => vB_Cleaner::TYPE_INT,
		'channeliconmaxsize' => vB_Cleaner::TYPE_INT,
		'ignore'            => vB_Cleaner::TYPE_ARRAY,
	]);

	if (sizeof($vbulletin->GPC['usergrouplist']) == 0)
	{
		print_stop_message2('invalid_usergroup_specified');
	}

	$datastore = vB::getDatastore();
	$bf_ugp_forumpermissions = $datastore->getValue('bf_ugp_forumpermissions');
	$bf_ugp_forumpermissions2 = $datastore->getValue('bf_ugp_forumpermissions2');
	$bf_misc_moderatorpermissions = $datastore->getValue('bf_misc_moderatorpermissions');
	$bf_ugp_createpermissions = $datastore->getValue('bf_ugp_createpermissions');

	$bitfieldsCollapseMap = [
		'forumpermissions' => $bf_ugp_forumpermissions,
		'forumpermissions2' => $bf_ugp_forumpermissions2,
		'moderatorpermissions' => $bf_misc_moderatorpermissions,
		'createpermissions' => $bf_ugp_createpermissions,
	];


	$nodeid = $vbulletin->GPC['nodeid'];
	$ignore = $vbulletin->GPC['ignore'];

	foreach ($vbulletin->GPC['usergrouplist'] AS $usergroupid => $confirm)
	{
		if ($confirm == 1)
		{
			$usergroupid = intval($usergroupid);

			$existingPermissions = vB_ChannelPermission::instance()->fetchPermissions($nodeid, $usergroupid);
			$existingPermissions = $existingPermissions[$usergroupid];
			$permissions =  [
				'forumpermissions'   => $vbulletin->GPC['forumpermissions'],
				'moderatorpermissions' => $vbulletin->GPC['moderatorpermissions'],
				'createpermissions'  => $vbulletin->GPC['createpermissions'],
				'forumpermissions2'  => $vbulletin->GPC['forumpermissions2'],
				'edit_time'          => $vbulletin->GPC['edit_time'],
				'maxtags'            => $vbulletin->GPC['maxtags'],
				'maxstartertags'     => $vbulletin->GPC['maxstartertags'],
				'maxothertags'       => $vbulletin->GPC['maxothertags'],
				'maxattachments'     => $vbulletin->GPC['maxattachments'],
				'maxchannels'        => $vbulletin->GPC['maxchannels'],
				'channeliconmaxsize' => $vbulletin->GPC['channeliconmaxsize'],
			];
			// persist existing permissions for ignored perms
			foreach ($permissions AS $__group => $__bfOrIntperm)
			{
				if (is_array($__bfOrIntperm))
				{
					// bitfield array
					foreach ($__bfOrIntperm AS $__perm => $__val)
					{
						if (isset($ignore[$__group][$__perm]))
						{
							$found = array_filter(
								$existingPermissions['bitfields'][$__group],
								function($arr) use ($__perm) {
									return ($arr['name'] == $__perm);
								}
							);
							if (!empty($found))
							{
								$found = current($found);
								$permissions[$__group][$__perm] = $found['set'];
							}
						}
					}
				}
				else
				{
					if (isset($ignore[$__group]))
					{
						$permissions[$__group] = $existingPermissions[$__group];
					}
				}

			}

			foreach ($bitfieldsCollapseMap AS $__group => $__bfmap)
			{
				$permissions[$__group] = convert_array_to_bits($permissions[$__group] , $__bfmap, 1);
			}

			vB_ChannelPermission::instance()->setPermissions($nodeid, $usergroupid, $permissions);
		}
	}

	print_stop_message2('saved_channel_permissions_successfully', 'forumpermission' ,array(
		'do' => 'modify',
		'n' => $vbulletin->GPC['nodeid'],
	));
}

// ###################### Start quick set #######################
if ($_REQUEST['do'] == 'quickset')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'type'		=> vB_Cleaner::TYPE_STR,
		'nodeid'	=> vB_Cleaner::TYPE_INT
	));

	verify_cp_sessionhash();

	if (!$vbulletin->GPC['nodeid'])
	{
		print_stop_message2('invalid_channel_specified');
	}

	try
	{
		$channel = vB_Library::instance('node')->getNode($vbulletin->GPC['nodeid']);

		if ($channel['parentid'] == 0)
		{
			print_stop_message2('invalid_channel_specified');
		}
	}
	catch(exception $e)
	{
		print_stop_message2('invalid_channel_specified');
	}

	switch ($vbulletin->GPC['type'])
	{
		case 'reset':
			vB::getDbAssertor()->assertQuery('vBForum:permission', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_DELETE,
				'nodeid' => $vbulletin->GPC['nodeid']));

			// If the nodeid is in default permissions, we need to copy default permissions back to permission table
			$defaultpermissions = vB_ChannelPermission::loadDefaultChannelPermissions();
			if (!empty($defaultpermissions["node_" . $vbulletin->GPC['nodeid']]))
			{
				foreach ($defaultpermissions["node_" . $vbulletin->GPC['nodeid']] as $groupid => $perm)
				{
					$groupid = str_replace('group_', '', $groupid);

					$params = array();
					$params[vB_dB_Query::TYPE_KEY] = vB_dB_Query::QUERY_INSERT;
					$params['nodeid'] = $vbulletin->GPC['nodeid'];
					$params['groupid'] = intval($groupid);
					foreach ($perm as $k => $v)
					{
						$params[$k] = $v;
					}
					$id = vB::getDbAssertor()->assertQuery('vBForum:permission', $params);
				}
			}

			break;

		case 'deny':
			$usergroupcache = &vB::getDatastore()->getValue('usergroupcache');
			foreach ($usergroupcache as $group)
			{
				/*insert query*/
				vB::getDbAssertor()->assertQuery('replacePermissions', array(
					'nodeid' => $vbulletin->GPC['nodeid'],
					'usergroupid' => $group['usergroupid'],
					'forumpermissions' => 0,
					'moderatorpermissions' => 0,
					'createpermissions' => 0,
					'forumpermissions2' => 0,
					'edit_time' => 2,
					'maxtags' => 0,
					'maxstartertags' => 0,
					'maxothertags' => 0,
					'maxattachments' => 0,
					'maxchannels' => 0,
					'channeliconmaxsize' => 0,
				));
			}
			break;

		default:
			print_stop_message2('invalid_quick_set_action');
	}

	vB_Cache::instance()->event('perms_changed');
	vB::getUserContext()->rebuildGroupAccess();
	print_stop_message2('saved_channel_permissions_successfully', 'forumpermission', array(
		'do' => 'modify',
		'n'  => $vbulletin->GPC['nodeid']
	));
}

// ###################### Start fpgetstyle #######################
function fetch_forumpermission_style($permissions)
{
	global $vbulletin;

	if (!($permissions & $vbulletin->bf_ugp_forumpermissions['canview']))
	{
		return " style=\"list-style-type:circle;\"";
	}
	else
	{
		return '';
	}
}

// ###################### Start modify #######################
if ($_REQUEST['do'] == 'modify')
{
	print_form_header('admincp/', '');
	print_table_header($vbphrase['additional_functions_gcppermission']);
	print_description_row("<b><a href=\"admincp/forumpermission.php?" . vB::getCurrentSession()->get('sessionurl') . "do=duplicate\">" . $vbphrase['permission_duplication_tools'] . "</a> | <a href=\"admincp/forumpermission.php?" . vB::getCurrentSession()->get('sessionurl') . "do=quickedit\">" . $vbphrase['permissions_quick_editor'] . "</a> | <a href=\"admincp/forumpermission.php?" . vB::getCurrentSession()->get('sessionurl') . "do=quickforum\">" . $vbphrase['quick_channel_permission_setup'] . "</a></b>", 0, 2, '', 'center');
	print_table_footer();

	print_form_header('admincp/', '');
	print_table_header($vbphrase['view_channel_permissions_gcppermission']);
	print_description_row('
		<div class="darkbg" style="border: 2px inset"><ul class="darkbg">
		<li><b>' . $vbphrase['color_key'] . '</b></li>
		<li class="col-g">' . $vbphrase['standard_using_default_channel_permissions'] . '</li>
		<li class="col-c">' . $vbphrase['customized_using_custom_permissions_for_this_usergroup_gcppermission'] . '</li>
		<li class="col-i">' . $vbphrase['inherited_using_custom_permissions_inherited_channel_a_parent_channel'] . '</li>
		</ul></div>
	');
	print_table_footer();

	// get moderators
	cache_moderators();

	//query channel permissions
	global $npermscache;
	$result = vB::getDbAssertor()->assertQuery('vBForum:permission', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT));
	$npermscache = array();
	foreach ($result as $permission)
	{
		$npermscache[$permission['nodeid']][$permission['groupid']] = $permission;
	}

	// get usergroup default permissions
	$permissions = array();
	$usergroupcache = vB::getDatastore()->getValue('usergroupcache');
	foreach($usergroupcache AS $usergroupid => $usergroup)
	{
		$permissions["$usergroupid"] = $usergroup['forumpermissions'];
	}
?>
<center>
<div class="tborder" style="width: 100%">
<div class="alt1" style="padding: 8px">
<div class="darkbg" style="padding: 4px; border: 2px inset; text-align: <?php echo vB_Template_Runtime::fetchStyleVar('left'); ?>">
<?php

	// run the display function
	if ($vbulletin->options['cp_collapse_forums'])
	{
?>
	<script type="text/javascript">
	<!--
	function js_forum_jump(nodeid)
	{
		if (nodeid > 0)
		{
			vBRedirect('admincp/forumpermission.php?do=modify&n=' + nodeid);
		}
	}
	-->
	</script>
		<?php
		$vbulletin->input->clean_array_gpc('g', array('nodeid' => vB_Cleaner::TYPE_INT));
		define('ONLYID', (!empty($vbulletin->GPC['nodeid']) ? $vbulletin->GPC['nodeid'] : $vbulletin->GPC['n']));

		$select = '<div align="center"><select name="nodeid" id="sel_foruid" tabindex="1" class="bginput" onchange="js_forum_jump(this.options[selectedIndex].value);">';
		$select .= construct_channel_chooser(ONLYID, true);
		$select .= "</select></div>\n";
		echo $select;

	}
	print_channels($permissions);

?>
</div>
</div>
</div>
</center>
<?php

}
function print_channels($permissions, $inheritance = array(), $channels = false, $indent = '	')
{
	global $vbulletin, $imodcache, $npermscache, $vbphrase;
	if ($channels === false)
	{
		$channels = vB_Api::instanceInternal('search')->getChannels(false, array('include_protected' => true, 'no_perm_check' => true));
	}

	$usergroups = vB_Api::instance('usergroup')->fetchUsergroupList();
	if (isset($usergroups['errors']))
	{
		print_stop_message2($usergroups['errors'][0]);
	}

	foreach ($channels AS $nodeid => $node)
	{
		// make a copy of the current permissions set up
		$perms = $permissions;

		// make a copy of the inheritance set up
		$inherit = $inheritance;

		// echo channel title and links
		if (!defined('ONLYID') OR $nodeid == ONLYID)
		{
			echo "$indent<ul class=\"lsq\">\n";
			echo "$indent<li><b><a id=\"channel$nodeid\" href=\"admincp/forum.php?" . vB::getCurrentSession()->get('sessionurl') . "do=edit&amp;n=$nodeid\">$node[htmltitle]</a></b>";
			if ($node['parentid'] != 0)
			{
				echo " <b><span class=\"smallfont\">(" . construct_link_code($vbphrase['reset'],
					"forumpermission.php?do=quickset&amp;type=reset&amp;n=$nodeid&amp;hash=" .
					CP_SESSIONHASH) . construct_link_code($vbphrase['deny_all'],
					"forumpermission.php?do=quickset&amp;type=deny&amp;n=$nodeid&amp;hash=" . CP_SESSIONHASH) . ")</span></b>";
			}

			// get moderators
			if (isset($imodcache[$nodeid]) AND is_array($imodcache[$nodeid]))
			{
				echo "<span class=\"smallfont\"><br /> - <i>" . $vbphrase['moderators'] . ":";
				foreach($imodcache[$nodeid] AS $moderator)
				{
					// moderator username and links
					echo " <a href=\"admincp/moderator.php?" . vB::getCurrentSession()->get('sessionurl') . "do=edit&amp;moderatorid=$moderator[moderatorid]\">$moderator[username]</a>";
				}
				echo "</i></span>";
			}

			echo "$indent\t<ul class=\"usergroups\">\n";
		}
		$nplink = "";
		foreach($usergroups AS $usergroup)
		{
			$usergroupid = $usergroup['usergroupid'];

			if (isset($inherit[$usergroupid]) AND $inherit[$usergroupid] == 'col-c')
			{
				$inherit[$usergroupid] = 'col-i';
			}

			// if there is a custom permission for the current usergroup, use it
			if (isset($npermscache["$nodeid"]["$usergroupid"]) AND $node['parentid'] != 0 AND vB_ChannelPermission::compareDefaultChannelPermissions($nodeid, $usergroupid, $npermscache["$nodeid"]["$usergroupid"]))
			{
				$inherit[$usergroupid] = 'col-c';
				$perms[$usergroupid] = $npermscache[$nodeid][$usergroupid]['forumpermissions'];
				$nplink = 'np=' . $npermscache[$nodeid][$usergroupid]['permissionid'];
			}
			else
			{
				$nplink = "n=$nodeid&amp;u=$usergroupid";
			}

			// work out display style
			$liStyle = '';
			if (isset($inherit[$usergroupid]))
			{
				$liStyle = " class=\"$inherit[$usergroupid]\"";
			}
			else
			{
				$liStyle = " class=\"col-g\"";
			}

			if (!($perms["$usergroupid"] & $vbulletin->bf_ugp_forumpermissions['canview']))
			{
				$liStyle .= " style=\"list-style:circle\"";
			}

			if (!defined('ONLYID') OR $nodeid == ONLYID)
			{
				echo "$indent\t<li$liStyle>" . construct_link_code($vbphrase['edit'], "forumpermission.php?" . vB::getCurrentSession()->get('sessionurl') . "do=edit&amp;$nplink") . $usergroup['title'] . "</li>\n";
			}
		}
		if (!defined('ONLYID') OR $nodeid == ONLYID)
		{
			echo "$indent\t</ul><br />\n";
		}

		if (defined('ONLYID') AND $nodeid == ONLYID)
		{
			echo "$indent</li>\n";
			echo "$indent</ul>\n";
			return;
		}
		if (!empty($node['channels']))
		{
			print_channels($perms, $inherit, $node['channels'], "$indent	");
		}
		if (!defined('ONLYID') OR $nodeid == ONLYID)
		{
			echo "$indent</li>\n";
		}
		unset($inherit);
		if (!defined('ONLYID') OR $nodeid == ONLYID)
		{
			echo "$indent</ul>\n";
		}

		if (!defined('ONLYID') AND $node['parentid'] == -1)
		{
			echo "<hr size=\"1\" />\n";
		}
	}
}

print_cp_footer();

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 116273 $
|| #######################################################################
\*=========================================================================*/
