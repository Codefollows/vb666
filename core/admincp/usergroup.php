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
define('CVS_REVISION', '$RCSfile$ - $Revision: 116563 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
global $phrasegroups, $specialtemplates, $vbphrase, $vbulletin;
$phrasegroups = ['cppermission', 'cpuser', 'promotion', 'pm', 'cpusergroup'];
$specialtemplates = [];

// ########################## REQUIRE BACK-END ############################
require_once(dirname(__FILE__) . '/global.php');

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!can_administer('canadminpermissions'))
{
	print_cp_no_permission();
}

// ############################# LOG ACTION ###############################
$vbulletin->input->clean_array_gpc('r', [
	'usergroupid' => vB_Cleaner::TYPE_INT,
]);

$logmessage = '';
if(!empty($vbulletin->GPC['usergroupid']))
{
	$logmessage = "usergroup id = " . $vbulletin->GPC['usergroupid'];
}
log_admin_action($logmessage);

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################
$assertor = vB::getDbAssertor();

print_cp_header($vbphrase['usergroup_manager_gcpusergroup'], '', [
	get_admincp_script_tag('vbulletin_usergroup.js'),
]);

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'modify';
}

// ###################### Start add / update #######################

function usergroup_sort_perms($group)
{
	//php sorting is not stable.  However if we don't have an explicit displayorder
	//set (displayorder 0) then we don't want to change from the order that we
	//get them from the source.  display order should always be positive.
	$newgroups = [];
	foreach($group AS $key => $perms)
	{
		if($perms['displayorder'] == 0)
		{
			$newgroups[$key] = $perms;
		}
	}

	array_subkey_sort($group, 'displayorder');

	foreach($group AS $key => $perms)
	{
		if($perms['displayorder'] > 0)
		{
			$newgroups[$key] = $perms;
		}
	}

	return $newgroups;
}


if ($_REQUEST['do'] == 'add' OR $_REQUEST['do'] == 'edit')
{
	$columnStyleCode = ['width: 70%', 'width: 30%'];

	$vbulletin->input->clean_array_gpc('r', [
		'defaultgroupid' => vB_Cleaner::TYPE_INT
	]);

	$groupApi = vB_Api::instanceInternal('usergroup');
	$bf_ugp = vB::getDatastore()->getValue('bf_ugp');
	$usergroupid = $vbulletin->GPC['usergroupid'];
	$defaultgroupid = $vbulletin->GPC['defaultgroupid'];

	require_once(DIR . '/includes/class_bitfield_builder.php');
	if (vB_Bitfield_Builder::build(false) !== false)
	{
		$myobj = vB_Bitfield_Builder::init();
		if (sizeof($myobj->datastore_total['ugp']) != sizeof($bf_ugp))
		{
			$myobj->save();
			vB_Library::instance('usergroup')->buildDatastore();
			vB::getUserContext()->rebuildGroupAccess();

			$extra = [];
			parse_str(vB::getRequest()->getVbUrlQuery(), $extra);
			print_stop_message2('rebuilt_bitfields_successfully', 'usergroup', $extra);
		}
	}
	else
	{
		echo "<strong>error</strong>\n";
		print_r(vB_Bitfield_Builder::fetch_errors());
	}


	if ($_REQUEST['do'] == 'add')
	{
		// get a list of other usergroups to base this one off of
		print_form_header('admincp/usergroup', 'add');
		$groups = $groupApi->fetchUsergroupList();
		$groups = array_column($groups, 'title', 'usergroupid');
		$selectgroups = construct_select('defaultgroupid', $groups, $defaultgroupid);

		$description = 	construct_table_help_button('defaultgroupid') . '<b>' .	$vbphrase['create_usergroup_based_off_of_usergroup'] . '</b> ' .
			$selectgroups . '<input type="submit" class="button" value="' . $vbphrase['go'] .	'" tabindex="1" />';
		print_description_row($description, 0, 2, 'tfoot', 'center');
		print_table_footer();
	}

	print_form_header('admincp/usergroup', 'update');
	print_column_style_code($columnStyleCode);
	$channePermHandler = vB_ChannelPermission::instance();
	$channelPerms = $channePermHandler->fetchPermSettings();
	$channelPermFields = $channePermHandler->fetchPermFields();
	//we don't need to bitmap fields- those we handle differently
	unset ($channelPermFields['moderatorpermissions']);
	unset ($channelPermFields['createpermissions']);
	unset ($channelPermFields['forumpermissions']);
	unset ($channelPermFields['forumpermissions2']);
	$channelPhrases = $channePermHandler->fetchPermPhrases();

	//initialize the array fully with keys both so that we keep consistant
	//order between the add and update screens (we initialize the array seperately
	//and, without this, we implicitly display the sections in the order of initialization)
	//and to make it clearer what order we are displaying things in (and easier to
	//edit that order in the future);
	$groupinfo = [
		'moderator_permissions',
		'createpermissions',
		'forum_permissions',
		'forum_viewing_permissions',
		'forum_searching_permissions',
		'post_thread_permissions',
		'attachment_permissions_gcppermission',
		'poll_permissions',
		'private_message_permissions',
		'whos_online_permissions',
		'administrator_permissions',
		'general_permissions',
		'picture_uploading_permissions',
		'signature_permissions',
		'user_reputation_permissions',
		'user_infraction_permissions',
		'user_album_permissions_gcppermission',
		'usercss_permissions',
		'usergroup_options_gcppermission',
		'visitor_message_permissions',
		'social_group_permissions'
	];

	$groupinfo = array_fill_keys($groupinfo, []);


	if ($_REQUEST['do'] == 'add')
	{
		if ($defaultgroupid)
		{
			$usergroup = $groupApi->fetchUsergroupByID($defaultgroupid);
		}
		else
		{
			// set default numeric permissions
			$usergroup = [
				'usergroupid' => 0,
				'pmquota' => 0,
				'pmsendmax' => 5,
				'attachlimit' => 1000000,
				'avatarmaxwidth' => 200,
				'avatarmaxheight' => 200,
				'avatarmaxsize' => 20000,
				'sigmaxsizebbcode' => 7,
				'title' => '',
				'description' => '',
				'usertitle' => '',
				'opentag' => '',
				'closetag' => '',
				'passwordexpires' => 0,
				'passwordhistory' => 0,
				'canoverride' => 0,
				'sigpicmaxwidth' => 0,
				'sigpicmaxheight' => 0,
				'sigpicmaxsize' => 0,
				'sigmaximages' => 0,
				'sigmaxchars' => 0,
				'sigmaxrawchars' => 0,
				'sigmaxlines' => 0,
				'albumpicmaxwidth' => 0,
				'albumpicmaxheight' => 0,
				'albummaxpics' => 0,
				'albummaxsize' => 0,
			];
		}
	}
	else
	{
		$usergroup = $groupApi->fetchUsergroupByID($usergroupid);
	}

	//This should probably be consolidated further.  But it needs more research into the difference between the
	//add blank or edit/add from existing cases.
	$ug_bitfield = [];
	if(!$usergroup['usergroupid'])
	{
		$disabled_perms = [];

		//we don't have the problem that the edit/existing case does with loading the original
		//usergroup, but set this to something for symmetry.
		$usergroup_org = $usergroup;

		foreach ($channelPermFields AS $field => $type)
		{
			if($type != vB_ChannelPermission::TYPE_BITMAP)
			{
				$usergroup[$field] = ($type == vB_ChannelPermission::TYPE_BOOL ? false : 0);
			}
		}

		//we'll default values to 0 later, we only need to mark the things on by default.
		$ug_bitfield = [
			'genericoptions' => ['showgroup' => 1, 'showeditedby' => 1, 'isnotbannedgroup' => 1],
			'forumpermissions' => [
				'canview' => 1,
				'canviewothers' => 1,
				'cangetattachment' => 1,
				'cansearch' => 1,
				'canthreadrate' => 1,
				'canpostpoll' => 1,
				'canvote' => 1,
				'canviewthreads' => 1,
			],
			'forumpermissions2' => ['cangetimgattachment' => 1],
			'wolpermissions' => ['canwhosonline' => 1],
			'createpermissions' => [],
			'moderatorpermissions' => [],
			'genericpermissions' => [
				'canviewmembers' => 1,
				'canmodifyprofile' => 1,
				'canusesignature' => 1,
				'cannegativerep' => 1,
				'canuserep' => 1,
				'cansearchft_nl' => 1,
			],
			'genericpermissions2' => [
				'canreferusers' => 1,
			],
		];

		foreach($bf_ugp AS $permissiongroup => $fields)
		{
			$permissions = 0;
			if(isset($ug_bitfield[$permissiongroup]))
			{
				$permissions = convert_array_to_bits($ug_bitfield[$permissiongroup], $fields);
			}
			$ug_bitfield[$permissiongroup] = convert_bits_to_array($permissions, $fields);
		}

		foreach ($channelPerms['moderatorpermissions'] AS $moderatorpermission)
		{
			if ($moderatorpermission['used'])
			{
				$ug_bitfield['moderatorpermissions'][$moderatorpermission['name']] = 0;
				$groupinfo['moderator_permissions'][$moderatorpermission['name']] = [
					'phrase' => $moderatorpermission['phrase'],
					'parentgroup' => 'moderatorpermissions',
					'intperm' => false,
					'readonly' => [],
					'ignoregroups' => [],
					'displayorder' => 0,
				];
			}
		}

		foreach ($channelPerms['createpermissions'] AS $createpermission)
		{
			$default = explode(',', $createpermission['install']);
			$ug_bitfield['createpermissions'][$createpermission['name']] = in_array(2, $default);
		}

		foreach ($channelPermFields AS $key => $permType)
		{
			$intperm = ($permType != vB_ChannelPermission::TYPE_BOOL);
			$groupinfo['forum_permissions'][$key] = [
				'intperm' => $intperm,
				'phrase' => $channelPhrases[$key],
				'parentgroup' => 'forumpermissions',
				'readonly' => [],
				'ignoregroups' => [],
				'displayorder' => 0,
			];

			if (!$intperm)
			{
				$default = explode(',', $channelPerms[$key]['install']);
				$ug_bitfield['forum_permissions'][$key] = in_array(2, $default);;
			}
		}
	}
	else
	{
		$disabled_perms = get_disabled_perms($usergroup);

		//$usergroup contains disabled fields that are set in fetchUsergroupByID to -1 so we need the original values
		$usergroup_org = $assertor->getRow('usergroup', ['usergroupid' => $usergroup['usergroupid']]);

		//a bit of a hack but if we have a validid then we loaded the group and will need to
		//expand the fields.  This is a little bit more coincidental that I like but we need to consolidate the logic
		foreach($bf_ugp AS $permissiongroup => $fields)
		{
			$ug_bitfield[$permissiongroup] = convert_bits_to_array($usergroup[$permissiongroup], $fields);
			if (array_key_exists($permissiongroup, $usergroup_org))
			{
				$usergroup_org[$permissiongroup] = convert_bits_to_array($usergroup_org[$permissiongroup], $fields);
			}
		}

		try
		{
			$channelPerms = $channePermHandler->fetchPermissions(1, $usergroup['usergroupid']);
			if (!empty($channelPerms) AND !empty($channelPerms[$usergroup['usergroupid']]))
			{
				$channelPerms = $channelPerms[$usergroup['usergroupid']];
				foreach ($channelPermFields AS $field => $type)
				{
					if($type != vB_ChannelPermission::TYPE_BITMAP)
					{
						$usergroup[$field] = $channelPerms[$field];
					}
				}

				$ug_bitfield['createpermissions'] = [];
				$usergroup['moderator_permissions'] = [];
				foreach ($channelPerms['bitfields']['createpermissions'] AS $createPerm)
				{
					if ($createPerm['used'])
					{
						$ug_bitfield['createpermissions'][$createPerm['name']] = $createPerm['set'];
						$groupinfo['createpermissions'][$createPerm['name']] = [
							'phrase' => $createPerm['phrase'],
							'parentgroup' => 'createpermissions',
							'intperm' => false,
							'readonly' => [],
							'ignoregroups' => [],
							'displayorder' => 0,
						];
					}
				};

				foreach ($channelPerms['bitfields']['moderatorpermissions'] AS $modPerm)
				{
					if ($modPerm['used'])
					{
						$ug_bitfield['moderatorpermissions'][$modPerm['name']] = $modPerm['set'];
						$groupinfo['moderator_permissions'][$modPerm['name']] = [
							'phrase' => $modPerm['phrase'],
							'parentgroup' => 'moderatorpermissions',
							'intperm' => false,
							'readonly' => [],
							'ignoregroups' => [],
							'displayorder' => 0,
						];
					}
				};

				foreach ($channelPerms['bitfields']['forumpermissions2'] AS $forumPerm2)
				{
					if ($forumPerm2['used'])
					{
						$ug_bitfield['forumpermissions2'][$forumPerm2['name']] = $forumPerm2['set'];
					}
				}
			}

			//and the added channel permissions
			foreach ($channelPermFields AS $key => $permType)
			{
				if (!isset($groupinfo[$key]))
				{
					$intperm = ($permType != vB_ChannelPermission::TYPE_BOOL);
					$groupinfo['forum_permissions'][$key] = [
						'intperm' => $intperm,
						'phrase' => $channelPhrases[$key],
						'parentgroup' => 'forumpermissions',
						'readonly' => [],
						'ignoregroups' => [],
						'displayorder' => 0,
					];

					if (!$intperm)
					{
						$ug_bitfield['forum_permissions'][$key] = $channelPerms[$key];
					}
				}
			}
		}
		catch(Exception $e)
		{
			//this is troubling and I don't know why we do it.
		}
	}


	if ($_REQUEST['do'] == 'add')
	{
		$permgroups = $assertor->getColumn('vBForum:getUserGroupPermissions', 'title', [], false, 'usergroupid');
		$ugarr = ['-1' => '--- ' . $vbphrase['none'] . ' ---'] + $permgroups;
		print_table_header($vbphrase['default_forum_permissions']);
		print_select_row($vbphrase['create_permissions_based_off_of_forum'], 'ugid_base', $ugarr, $defaultgroupid);
		print_table_break();
		print_table_header($vbphrase['add_new_usergroup_gcpusergroup']);
	}
	else
	{
		construct_hidden_code('usergroupid', $usergroupid);
		print_table_header(construct_phrase($vbphrase['x_y_id_z'], $vbphrase['usergroup'], $usergroup['title'], $usergroup['usergroupid']), 2, 0);
	}

	print_input_row($vbphrase['title'], 'usergroup[title]', $usergroup['title']);
	print_input_row($vbphrase['description_gcpglobal'], 'usergroup[description]', $usergroup['description']);
	print_input_row($vbphrase['usergroup_user_title'], 'usergroup[usertitle]', $usergroup['usertitle'], true, 35, 100);
	print_label_row($vbphrase['username_markup'],
		'<span style="white-space:nowrap">
		<input size="15" type="text" class="bginput" name="usergroup[opentag]" value="' . htmlspecialchars_uni($usergroup['opentag']) . '" tabindex="1" />
		<input size="15" type="text" class="bginput" name="usergroup[closetag]" value="' . htmlspecialchars_uni($usergroup['closetag']) . '" tabindex="1" />
		</span>', '', 'top', 'htmltags');
	print_input_row($vbphrase['password_expiry'], 'usergroup[passwordexpires]', $usergroup['passwordexpires']);
	print_input_row($vbphrase['password_history'], 'usergroup[passwordhistory]', $usergroup['passwordhistory']);

	// additional system usergroups with unpredicatable usergroupids
	if ((isset($usergroup['systemgroupid']) AND $usergroup['systemgroupid'] == 0) OR $_REQUEST['do'] == 'add')
	{
		print_yes_no_row($vbphrase['can_override_primary_group_title'], 'usergroup[canoverride]', $usergroup['canoverride']);
	}

	print_table_break();
	print_column_style_code($columnStyleCode);

	// Legacy Hook 'admin_usergroup_edit' Removed //


	// If we are removing permissions, they should be removed completely and not just hidden/excluded here.
	// However we many of these are referenced in old upgrade steps, removing the permission can break those
	// and rooting them out can be more trouble than it is worth.

	// display only BF used in a nicer way. Removing unused BF for usergroup manager needs more planning.
	$excludedBF = [
		'forumpermissions' => ['canemail', 'canpostpoll', 'canthreadrate'],
		'forumpermissions2' => ['canalwaysview', 'canalwayspostnew', 'canalwayspost', 'exemptfromspamcheck', 'canmanageownchannels'],
		'pmpermissions' => ['cantrackpm', 'candenypmreceipts', 'pmthrottlequantity'],
		'genericpermissions' => [
			'canviewothersusernotes',
			'canmanageownusernotes',
			'canbeusernoted',
			'canviewownusernotes',
			'canmanageothersusernotes',
			'canpostownusernotes',
			'canpostothersusernotes',
			'caneditownusernotes',
			'cannegativerep',
			'cansearchft_bool',
			'canemailmember',
		],
		'genericoptions' => ['showgroup'],
		'socialgrouppermissions' => [
			/*
				Used bits:
				- usercontext
					canviewgroups		(used by usercontext::getReadChannels() to add SG channel to 'cantRead' array)
					cancreatediscussion (seems to be required in conjunction with the various createpermissions in usercontext::getCanCreate())

			 */
			'maximumsocialgroups', // use maxchannels channel perm instead
			'canlimitdiscussion',
			'candeleteowngroups',
			'canjoingroups',
			'canmanageowngroups',
			'caneditowngroups',
			'canmanagediscussions',
			'canmanagemessages',
			'cancreategroups',
			'canpostmessage',
			'followforummoderation',
			'canuploadgroupicon',
			'cananimategroupicon',
			'groupiconmaxsize',
			'canalwayspostmessage',
			'canalwayscreatediscussion',
			'groupfollowforummoderation',
			'canupload',
		],
		'albumpermissions' => ['canalbum', 'canpiccomment', 'caneditownpiccomment', 'candeleteownpiccomment', 'canmanagepiccomment', 'commentfollowforummoderation'],
	];
	$bfGroups = array_keys($excludedBF);
	foreach ($myobj->data['ugp'] AS $grouptitle => $perms)
	{
		foreach ($perms AS $permtitle => $permvalue)
		{
			if (empty($permvalue['group']))
			{
				continue;
			}

			if (in_array($grouptitle, $bfGroups) AND in_array($permtitle, $excludedBF[$grouptitle]))
			{
				continue;
			}

			//a lof of this logic *should* be pushed back to the vB_Bitfield_Builder class
			//so as to provide a more consistant interface to the callers.  But that's quite a bit
			//more work and risk than handling it here.
			$info = [
				'parentgroup' => $grouptitle,
				'phrase' => $permvalue['phrase'],
				'ignoregroups' => $permvalue['ignoregroups'] ?? '',
				'options' => $permvalue['options'] ?? '',
				'intperm' => $permvalue['intperm'] ?? false,
				'displayorder' => $permvalue['displayorder'],
			];

			//note that readonly is currently only enforced on display.  However it's only currently used
			//for preventing the admin from removing cancontrolpanel from the admin group.  This *is*
			//specifically enforced on save.  The feature, outside of this specific case, cannot be considered
			//well tested.

			$csvFields = [
				'ignoregroups',
				'readonly',
			];

			foreach($csvFields AS $field)
			{
				if (!empty($permvalue[$field]))
				{
					$info[$field] = explode(',', $permvalue[$field]);
				}
				else
				{
					$info[$field] = [];
				}
			}

			if (!empty($myobj->data['layout'][$permvalue['group']]['ignoregroups']))
			{
				$groupinfo[$permvalue['group']]['ignoregroups'] = $myobj->data['layout'][$permvalue['group']]['ignoregroups'];
			}

			$groupinfo[$permvalue['group']][$permtitle] = $info;
		}
	}

	foreach ($groupinfo AS $grouptitle => $group)
	{
		//for some reason we never intialized the key in the sections array.
		if(is_null($group))
		{
			continue;
		}

		// This set of permissions is hidden from a specific group
		if (isset($group['ignoregroups']))
		{
			$ignoreids = explode(',', $group['ignoregroups']);
			if (in_array($usergroupid, $ignoreids))
			{
				continue;
			}
			else
			{
				unset($group['ignoregroups']);
			}
		}

		$group = usergroup_sort_perms($group);

		print_table_header($vbphrase[$grouptitle]);
		foreach ($group AS $permtitle => $permvalue)
		{
			// Permission is shown only if a particular option is enabled.
			if (!empty($permvalue['options']) AND !$vbulletin->options[$permvalue['options']])
			{
				continue;
			}

			// Permission is hidden from specific groups
			if (in_array($usergroupid, $permvalue['ignoregroups']))
			{
				continue;
			}

			$permphrase = $vbphrase[$permvalue['phrase']] ?? ('~~' . $permvalue['phrase'] . '~~');

			//note that readonly is currently only enforced on display.  However it's only currently used
			//for preventing the admin from removing cancontrolpanel from the admin group.  This *is*
			//specifically enforced on save.  The feature, outside of this specific case, cannot be considered
			//well tested.

			if ($permvalue['intperm'])
			{
				$getval = $usergroup[$permtitle];
				// This permission is readonly for certain usergroups
				if (in_array($usergroupid, $permvalue['readonly']))
				{
					print_label_row($permphrase, $getval);
					construct_hidden_code("usergroup[$permtitle]", $getval);
					continue;
				}

				//this value has been disabled
				if (array_key_exists($permtitle, $disabled_perms) AND array_key_exists($permtitle, $usergroup_org))
				{
					$getval = $usergroup_org[$permtitle];
				}

				print_input_row($permphrase, "usergroup[$permtitle]", $getval, 1, 20);
			}
			else
			{
				$inputname = 'usergroup[' . $permvalue['parentgroup'] . '][' . $permtitle . ']';

				$getval = $ug_bitfield[$permvalue['parentgroup']][$permtitle];
				if (!isset($getval))
				{
					$getval = $usergroup[$permtitle];
				}

				// This permission is readonly for certain usergroups
				if (in_array($usergroupid, $permvalue['readonly']))
				{
					$labelphrase = ($getval ? 'yes' : 'no');
					print_yes_row($permphrase, $inputname, $vbphrase[$labelphrase], $getval);
					continue;
				}

				//this value has been disabled
				if (array_key_exists($permvalue['parentgroup'], $disabled_perms))
				{
					$getval = !empty($usergroup_org[$permvalue['parentgroup']][$permtitle]);
				}

				//There are two canopenclose permissions. To allow the help text to be different we need a prefix on the moderator permission.
				$helpOptions = [];
				if (($permvalue['parentgroup'] == 'moderatorpermissions') AND ($permtitle == 'canopenclose'))
				{
					$helpOptions = ['prefix' => $permvalue['parentgroup']];
				}

				print_yes_no_row($permphrase, $inputname, $getval, [], $helpOptions);
			}
		}
		print_table_break();
		print_column_style_code($columnStyleCode);
	}

	$submitphrase = ($_REQUEST['do'] == 'add' ? 'save' : 'update');
	print_submit_row($vbphrase[$submitphrase]);
}

// ###################### Start insert / update #######################
if ($_POST['do'] == 'update')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'usergroup' => vB_Cleaner::TYPE_ARRAY,
		'ugid_base' => vB_Cleaner::TYPE_INT,
		'usergroupid' => vB_Cleaner::TYPE_INT
	));
	$ugpermissions = $vbulletin->GPC['usergroup'];

	// These ones go in the permission table
	$channelPerms = vB_ChannelPermission::fetchPermFields();
	$fmcPermissions = [];
	foreach($ugpermissions AS $key => $value)
	{
		if (isset($channelPerms[$key]))
		{
			$fmcPermissions[$key] = $value;
			if ($key !== 'forumpermissions')
			{
				unset($ugpermissions[$key]);
			}
		}
	}

	/*
	 * This is the main save, usergroup permissions and for node 1
	 */
	$resultUg = vB_Api::instance('usergroup')->save($ugpermissions,	$vbulletin->GPC['ugid_base'],	$vbulletin->GPC['usergroupid']);
	print_stop_message_on_api_error($resultUg);

	// This section is used to not delete the values in the permissions stored in forumpermissions2,
	// they used as channel permissions and are not displayed in the usergroup manager, which causes them to be set to No, VBV-10060
	$nodeid = vB_Api::instanceInternal('content_channel')->fetchChannelIdByGUID(vB_Channel::MAIN_CHANNEL);
	$homePerms = vB_ChannelPermission::instance()->fetchPermissions($nodeid, $resultUg);

	if (!empty($homePerms) AND !empty($homePerms[$resultUg]))
	{
		$channelPerms = $homePerms[$resultUg];

		foreach ($channelPerms['bitfields']['forumpermissions2'] AS $perm)
		{
			if ($perm['used'] AND !isset($fmcPermissions['forumpermissions2'][$perm['name']]))
			{
				$fmcPermissions['forumpermissions2'][$perm['name']] = $perm['set'];
			}
		}
	}

	vB_ChannelPermission::instance()->setPermissions($nodeid, $resultUg, $fmcPermissions);

	/*
	 * This section is to save the create channel permission for the Blog channel
	 * in the permission table for the corresponding node, the request for the current
	 * permissions in each node is to don't overwrite them, this is because if
	 * the method vB_ChannelPermission::instance()->setPermissions() doesn't receive
	 * a set of permissions it sets them to No.
	 * The Social Group permission setting has been removed as it was causing more problems
	 * than it was helping, because a lot of the permissions set at the Group channel wasn't
	 * supposed to be set like that, and was supposed to be dealt with using the
	 * CHANNEL_OWNER/MODERATOR/MEMBER system groups to give group owners/mods/members specific
	 * permissions in groups that they had groupsintopic records for (which is created automatically
	 * when they create or join a group)
	 * I'm leaving the blog one in for now since it's in a different permission group than
	 * social group permissions, and it only sets 1 channel permission bit (& header navbars)
	 */

	$blogChannel = vB_Api::instanceInternal('blog')->getBlogChannel();
	$blogPerms = vB_ChannelPermission::instance()->fetchPermissions($blogChannel, $resultUg);
	unset($ug_bitfield);
	$ug_bitfield = array();
	if (!empty($blogPerms) AND !empty($blogPerms[$resultUg]))
	{
		$channelPerms = $blogPerms[$resultUg];
		$ug_bitfield['createpermissions'] = array();
		$ug_bitfield['forumpermissions'] = array();
		$ug_bitfield['moderatorpermissions'] = array();
		foreach ($channelPerms['bitfields']['createpermissions'] AS $createPerm)
		{
			if ($createPerm['used'])
			{
				$ug_bitfield['createpermissions'][$createPerm['name']] = $createPerm['set'];
			}
		}
		foreach ($channelPerms['bitfields']['forumpermissions'] AS $perm)
		{
			if ($perm['used'])
			{
				$ug_bitfield['forumpermissions'][$perm['name']] = $perm['set'];
			}
		}
		foreach ($channelPerms['bitfields']['moderatorpermissions'] AS $perm)
		{
			if ($perm['used'])
			{
				$ug_bitfield['moderatorpermissions'][$perm['name']] = $perm['set'];
			}
		}
	}

	// All this section is due to the subnav bar 'create a new blog'
	$siteLibrary =  vB_Library::instance('site');
	$siteNavs = $siteLibrary->loadHeaderNavbar(1, false, 1);
	$break = false;
	foreach ($siteNavs AS $k => &$item)
	{
		foreach (array('isAbsoluteUrl', 'normalizedUrl') AS $urlvar)
		{
			if (array_key_exists($urlvar, $item) AND empty($item[$urlvar]))
			{
				unset($item[$urlvar]);
			}
		}

		if (!empty($item['phrase']) AND ($item['phrase'] === 'navbar_blogs') AND !empty($item['subnav']))
		{
			foreach ($item['subnav'] AS &$subnav)
			{
				foreach (array('isAbsoluteUrl', 'normalizedUrl') AS $urlvar)
				{
					if (array_key_exists($urlvar, $subnav) AND empty($subnav[$urlvar]))
					{
						unset($subnav[$urlvar]);
					}
				}
				if (!empty($subnav['phrase']) AND $subnav['phrase'] === 'navbar_create_a_new_blog' AND !empty($subnav['usergroups']))
				{
					$foundKey = -1;
					if(is_array($subnav['usergroups']))
					{
						foreach ($subnav['usergroups'] AS $key => $ug)
						{
							if ($ug == $resultUg)
							{
								$foundKey = $key;
							}
						}
					}
					if ($ugpermissions['forumpermissions']['cancreateblog']) // permission
					{
						if ($foundKey == -1)
						{
							$subnav['usergroups'][] = $resultUg;
						}
					}
					else
					{
						if ($foundKey >= 0)
						{
							unset($subnav['usergroups'][$foundKey]);
							$subnav['usergroups'] = array_values($subnav['usergroups']);
						}
					}
					break;
				}
			}
		}
	}
	$siteLibrary->saveHeaderNavbar(1, $siteNavs);

	$ug_bitfield['createpermissions']['vbforum_channel'] = $ugpermissions['forumpermissions']['cancreateblog'];
	vB_ChannelPermission::instance()->setPermissions($blogChannel, $resultUg, $ug_bitfield);
	/*
	 * End of section 'create channel' for blog
	 */

	// Album channel
	$albumChannel = vB_Api::instanceInternal('node')->fetchAlbumChannel();
	$albumPerms = vB_ChannelPermission::instance()->fetchPermissions($albumChannel, $resultUg);
	$bitfields = vB_ChannelPermission::instance()->fetchPermSettings();

	if ($ugpermissions['albumpermissions']['canviewalbum'])
	{
		$albumPerms[$resultUg]['forumpermissions'] |= intval($bitfields['forumpermissions']['canview']['value']);
	}
	else
	{
		$albumPerms[$resultUg]['forumpermissions'] &= ~intval($bitfields['forumpermissions']['canview']['value']);
	}

	vB_ChannelPermission::instance()->setPermissions($albumChannel, $resultUg, $albumPerms[$resultUg]);

	print_stop_message2(array('saved_usergroup_x_successfully', htmlspecialchars_uni($vbulletin->GPC['usergroup']['title'])), 'usergroup', array('do'=>'modify'));
}

// ###################### Start Remove #######################

if ($_REQUEST['do'] == 'remove')
{
	$groupApi = vB_Api::instanceInternal('usergroup');
	$usergroupid = $vbulletin->GPC['usergroupid'];

	$group = $groupApi->fetchUsergroupByID($usergroupid);

	//don't allow deleting system groups
	if ($group['systemgroupid'])
	{
		print_stop_message2('cant_delete_usergroup');
	}
	else
	{
		$regulargroup = $groupApi->fetchUsergroupBySystemID(vB_Api_UserGroup::REGISTERED_SYSGROUPID);
		$message = construct_phrase($vbphrase['all_members_of_this_usergroup_will_revert'], $regulargroup['title']);
		print_delete_confirmation('usergroup', $usergroupid, 'usergroup', 'kill', 'usergroup', 0, $message);
	}
}

// ###################### Start Kill #######################
if ($_POST['do'] == 'kill')
{
	vB_Api::instanceInternal('usergroup')->delete($vbulletin->GPC['usergroupid']);
	print_stop_message2('deleted_usergroup_successfully', 'usergroup', ['do' => 'modify']);
}

// ###################### Start modify #######################
if ($_REQUEST['do'] == 'modify')
{
	$groupcache = vB_Api::instanceInternal('usergroup')->fetchUsergroupList();
	$groupcache = array_column($groupcache, null, 'usergroupid');

	//ensure the extra count columns we're adding exist for everybody.
	foreach ($groupcache AS $id => $group)
	{
		$groupcache[$id]['count'] = 0;
		$groupcache[$id]['secondarycount'] = 0;
	}

	// count primary users
	$groupcounts = $assertor->assertQuery('vBForum:getPrimaryUsersCount');
	foreach ($groupcounts AS $groupcount)
	{
		$groupcache[$groupcount['usergroupid']]['count'] = $groupcount['total'];
	}
	unset($groupcount);

	// count secondary users
	$groupcounts = $assertor->assertQuery('user', [
		vB_dB_Query::COLUMNS_KEY => ['usergroupid', 'membergroupids'],
		vB_dB_Query::CONDITIONS_KEY => [
			['field' => 'membergroupids','value' => '', 'operator'=> vB_dB_Query::OPERATOR_NE]
		]
	]);
	foreach ($groupcounts AS $groupcount)
	{
		$ids = fetch_membergroupids_array($groupcount, false);
		foreach ($ids AS $index => $value)
		{
			if ($groupcount['usergroupid'] != $value AND !empty($groupcache[$value]))
			{
				$groupcache[$value]['secondarycount']++;
			}
		}
	}
	unset($groupcount);

	$usergroups = array_fill_keys(['custom', 'default'], []);
	foreach($groupcache AS $id => $group)
	{
		$type = ($group['systemgroupid'] == 0 ? 'custom' : 'default');
		$usergroups[$type][$id] = $group;
	}

	$promotions = [];
	$proms = $assertor->assertQuery('getUserGroupIdCountByPromotion');
	foreach ($proms AS $prom)
	{
		$promotions[$prom['usergroupid']] = $prom['count'];
	}
	unset($proms);

	// ###################### Start makeusergroupcode #######################
	function print_usergroup_row($usergroup, $options, $promotions)
	{
		global $vbphrase, $vbulletin;

		$id = $usergroup['usergroupid'];
		if (!empty($promotions[$id]))
		{
			$options['promote'] .= ' (' . $promotions[$id] . ')';
		}

		$cell = [];
		$cell[] = "<b>$usergroup[title]" . ($usergroup['canoverride'] ? '*' : '') . "</b>";
		$cell[] = ($usergroup['count'] ? vb_number_format($usergroup['count']) : '-');
		$cell[] = ($usergroup['secondarycount'] ? vb_number_format($usergroup['secondarycount']) : '-');

		$options['edit'] .= " (id: $id)";
		$cell[] = construct_jump_control('ugjump', $id,  $vbphrase['go'], $options);
		print_cells_row2($cell);
	}

	print_form_header('admincp/usergroup', 'add');

	$options_custom = [
		'edit'       => $vbphrase['edit_usergroup'],
		'promote'    => $vbphrase['edit_promotions'],
		'kill'       => $vbphrase['delete_usergroup'],
		'list'       => $vbphrase['show_all_primary_users'],
		'list2'      => $vbphrase['show_all_additional_users'],
		'reputation' => $vbphrase['view_reputation'],
	];

	//this is a little backwards but removing options is easier to maintain order.
	$options_default = $options_custom;
	unset($options_default['kill']);

	$header = [
		$vbphrase['title'],
		$vbphrase['primary_users_gcpuser'],
		$vbphrase['additional_users_gcpuser'],
		$vbphrase['controls']
	];
	$headcount = sizeof($header);

	print_table_header($vbphrase['default_usergroups'], $headcount);
	print_cells_row2($header, 'thead');
	foreach($usergroups['default'] AS $usergroup)
	{
		print_usergroup_row($usergroup, $options_default, $promotions);
	}

	if ($usergroups['custom'])
	{
		print_table_break();
		print_table_header($vbphrase['custom_usergroups'], $headcount);
		print_cells_row2($header, 'thead');
		foreach($usergroups['custom'] AS $usergroup)
		{
			print_usergroup_row($usergroup, $options_custom, $promotions);
		}
		print_description_row('<span class="smallfont">' . $vbphrase['note_groups_marked_with_a_asterisk'] . '</span>', false, $headcount);
	}

	print_submit_row($vbphrase['add_new_usergroup_gcpusergroup'], 0, $headcount);
}

// ###################### Start modify promotions #######################
if ($_REQUEST['do'] == 'modifypromotion')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'returnug' => vB_Cleaner::TYPE_BOOL
	));

	$title = $assertor->assertQuery('vBForum:usergroup', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT, 'usergroupid' => $vbulletin->GPC['usergroupid']));
	if ($title AND $title->valid())
	{
		$title = $title->current();
	}

	$promotions = vB_Api::instanceInternal('usergroup')->fetchPromotions($vbulletin->GPC['usergroupid'] ? $vbulletin->GPC['usergroupid'] : 0);

	print_form_header('admincp/usergroup', 'updatepromotion');
	if (isset($vbulletin->usergroupcache["{$vbulletin->GPC['usergroupid']}"]))
	{
		construct_hidden_code('usergroupid', $vbulletin->GPC['usergroupid']);
	}
	if ($vbulletin->GPC['returnug'])
	{
		construct_hidden_code('returnug', 1);
	}

	$headers = [
		$vbphrase['usergroup'],
		$vbphrase['promotion_type'],
		$vbphrase['promotion_strategy'],
		$vbphrase['reputation_level_gcpglobal'],
		$vbphrase['days_registered'],
		$vbphrase['posts'],
		$vbphrase['days_since_lastpost'],
		$vbphrase['controls']
	];

	foreach($promotions AS $groupid => $promos)
	{
		$addNewPromotionLink = construct_link_code(
			$vbphrase['add_new_promotion'],
			"usergroup.php?" . "do=updatepromotion&amp;usergroupid=$groupid" . ($vbulletin->GPC['returnug'] ? '&amp;returnug=1' : '')
		);
		$title = "$vbphrase[promotions]: <span style=\"font-weight:normal\">" . $vbulletin->usergroupcache[$groupid]['title'] . ' ' . $addNewPromotionLink . "</span>";
		print_table_header(
			$title,
			count($headers)
		);
		print_cells_row($headers, 1);

		foreach($promos AS $promotion)
		{
			$condition = (($promotion['strategy'] > 7 AND $promotion['strategy'] < 16) OR $promotion['strategy'] == 24);
			$promotion['strategy'] = $condition ? $promotion['strategy'] - 8 : $promotion['strategy'];
			if ($promotion['strategy'] == 16)
			{
				$type = $vbphrase['reputation'];
			}
			else if ($promotion['strategy'] == 17)
			{
				$type = $vbphrase['posts'];
			}
			else if ($promotion['strategy'] == 18)
			{
				$type = $vbphrase['join_date'];
			}
			else
			{
				$type = $vbphrase['promotion_strategy' . ($promotion['strategy'] + 1)];
			}
			print_cells_row([
				"<b>$promotion[title]</b>",
				$promotion['type'] == 1 ? $vbphrase['primary_usergroup'] : $vbphrase['additional_usergroups'],
				$type,
				$promotion['reputation'],
				$promotion['date'],
				$promotion['posts'],
				$promotion['days_since_lastpost'],
				construct_link_code($vbphrase['edit'], "usergroup.php?" . "userpromotionid=$promotion[userpromotionid]&do=updatepromotion" . ($vbulletin->GPC['returnug'] ? '&returnug=1' : '')) . construct_link_code($vbphrase['delete'], "usergroup.php?" . vB::getCurrentSession()->get('sessionurl') . "userpromotionid=$promotion[userpromotionid]&do=removepromotion" . ($vbulletin->GPC['returnug'] ? '&returnug=1' : '')),
			]);
		}
	}

	print_submit_row($vbphrase['add_new_promotion'], 0, count($headers));
}

// ###################### Start edit/insert promotions #######################
if ($_REQUEST['do'] == 'updatepromotion')
{
	$vbulletin->input->clean_array_gpc('r', [
		'userpromotionid' => vB_Cleaner::TYPE_INT,
		'returnug'        => vB_Cleaner::TYPE_BOOL,
	]);

	$usergroups = [];
	foreach($vbulletin->usergroupcache AS $usergroup)
	{
		$usergroups["{$usergroup['usergroupid']}"] = $usergroup['title'];
	}

	print_form_header('admincp/usergroup', 'doupdatepromotion');

	if (!$vbulletin->GPC['userpromotionid'])
	{
		$promotion = [
			'reputation' => 1000,
			'date' => 30,
			'posts' => 100,
			'days_since_lastpost' => 0,
			'type' => 1,
			'reputationtype' => 0,
			'strategy' => 16,
			'usergroupid' => 0,
			'joinusergroupid' => 0,
		];

		if ($vbulletin->GPC['usergroupid'])
		{
			$promotion['usergroupid'] = $vbulletin->GPC['usergroupid'];
		}

		if ($vbulletin->GPC['returnug'])
		{
			construct_hidden_code('returnug', 1);
		}
		print_table_header($vbphrase['add_new_promotion']);
		print_select_row($vbphrase['usergroup'], 'promotion[usergroupid]', $usergroups, $promotion['usergroupid']);

	}
	else
	{
		$promotion = $assertor->assertQuery('getUserPromotionsAndUserGroups', array('userpromotionid' => $vbulletin->GPC['userpromotionid']));
		if ($promotion AND $promotion->valid())
		{
			$promotion = $promotion->current();
		}

		// It's not clear why we did it this way, but this is to invert the offset-by-8 that happens in savePromotion() when
		// reputationtype == 'Less than' (1) instead of the default 'Greater or equal to' (0).
		if (($promotion['strategy'] > 7 AND $promotion['strategy'] < 16) OR $promotion['strategy'] == 24)
		{
			$promotion['reputationtype'] = 1;
			$promotion['strategy'] -= 8;
		}
		else
		{
			$promotion['reputationtype'] = 0;
		}
		if ($vbulletin->GPC['returnug'])
		{
			construct_hidden_code('returnug', 1);
		}
		construct_hidden_code('userpromotionid', $vbulletin->GPC['userpromotionid']);
		construct_hidden_code('usergroupid', $promotion['usergroupid']);
		print_table_header(construct_phrase($vbphrase['x_y_id_z'], $vbphrase['promotion'], $promotion['title'], $promotion['userpromotionid']));
	}

	// Note, in vB_Api_Usergroup::savePromotion() there's this bit of code:
	//  if (!empty($promotion['reputationtype']) AND $promotion['strategy'] <= 16)
	//  {
	//  	$promotion['strategy'] += 8;
	//  }
	// which forces strategy values of 8~15 as well as 24 to be reserved...
	// Also note, reputationtype's description says that it only applies to
	// reputation and only when the strategy is reputation (16), but it's not
	// entirely clear if that's true from looking at savePromotion() and the
	// cron (promotion.php)

	$promotionarray = [
		17 => $vbphrase['posts'],
		18 => $vbphrase['join_date'],
		16 => $vbphrase['reputation'],
		vB_Library_Usergroup::PROMOTION_STRATEGY_DAYS_SINCE_LAST_POST => $vbphrase['days_since_lastpost'],
		// 24 is reserved for use internally
		0  => $vbphrase['promotion_strategy1'],
		1  => $vbphrase['promotion_strategy2'],
		2  => $vbphrase['promotion_strategy3'],
		3  => $vbphrase['promotion_strategy4'],
		4  => $vbphrase['promotion_strategy5'],
		5  => $vbphrase['promotion_strategy6'],
		6  => $vbphrase['promotion_strategy7'],
		7  => $vbphrase['promotion_strategy8'],
		// 8 ~ 15 are reserved for use internally
	];

	print_input_row($vbphrase['reputation_level_gcpglobal'], 'promotion[reputation]', $promotion['reputation']);
	print_input_row($vbphrase['days_registered'], 'promotion[date]', $promotion['date']);
	print_input_row($vbphrase['posts'], 'promotion[posts]', $promotion['posts']);
	print_input_row($vbphrase['days_since_lastpost'], 'promotion[days_since_lastpost]', $promotion['days_since_lastpost']);
	print_select_row($vbphrase['promotion_strategy'] . " <dfn> $vbphrase[promotion_strategy_description]</dfn>", 'promotion[strategy]', $promotionarray, $promotion['strategy']);
	print_select_row($vbphrase['promotion_type'] . ' <dfn>' . $vbphrase['promotion_type_description_primary_additional'] . '</dfn>', 'promotion[type]', array(1 => $vbphrase['primary_usergroup'], 2 => $vbphrase['additional_usergroups']), $promotion['type']);
	print_select_row($vbphrase['reputation_comparison_type'] . '<dfn>' . $vbphrase['reputation_comparison_type_desc'] . '</dfn>', 'promotion[reputationtype]', array($vbphrase['greater_or_equal_to'], $vbphrase['less_than']), $promotion['reputationtype']);
	print_chooser_row($vbphrase['move_user_to_usergroup_gpromotion'] . " <dfn>$vbphrase[move_user_to_usergroup_description]</dfn>", 'promotion[joinusergroupid]', 'usergroup', $promotion['joinusergroupid'], '&nbsp;');

	print_table_default_footer($vbphrase['save']);
}

// ###################### Start do edit/insert promotions #######################
if ($_POST['do'] == 'doupdatepromotion')
{

	$vbulletin->input->clean_array_gpc('p', array(
		'promotion'       => vB_Cleaner::TYPE_ARRAY,
		'userpromotionid' => vB_Cleaner::TYPE_INT,
		'returnug'        => vB_Cleaner::TYPE_BOOL,
	));

	try
	{
		vB_Api::instanceInternal('usergroup')->savePromotion(
			$vbulletin->GPC['promotion'],
			$vbulletin->GPC['usergroupid'],
			$vbulletin->GPC['userpromotionid']
		);
	}
	catch (vB_Exception_Api $e)
	{
		$errors = $e->get_errors();
		print_stop_message2($errors[0]);
	}

	$args = array(
		'do' => 'modifypromotion'
	);
	if ($vbulletin->GPC['returnug'])
	{
		$args['returnug'] = 1;
		$args['usergroupid'] = $vbulletin->GPC['usergroupid'];
	}
	print_stop_message2('saved_promotion_successfully', 'usergroup', $args);
}

// ###################### Start Remove #######################

if ($_REQUEST['do'] == 'removepromotion')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'userpromotionid' => vB_Cleaner::TYPE_INT,
		'returnug'        => vB_Cleaner::TYPE_BOOL,
	));
	print_delete_confirmation('userpromotion', $vbulletin->GPC['userpromotionid'], 'usergroup', 'killpromotion', 'promotion_usergroup', array('returnug' => $vbulletin->GPC['returnug']));

}

// ###################### Start Kill #######################
if ($_POST['do'] == 'killpromotion')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'userpromotionid' => vB_Cleaner::TYPE_INT,
		'returnug'        => vB_Cleaner::TYPE_BOOL,
	));
	vB_Api::instanceInternal('usergroup')->deletePromotion($vbulletin->GPC['userpromotionid']);

	$args = array(
		'do' => 'modifypromotion'
	);
	if ($vbulletin->GPC['returnug'])
	{
		$args['returnug'] = 1;
		$args['usergroupid'] = $vbulletin->GPC['usergroupid'];
	}
	print_stop_message2('deleted_promotion_successfully', 'usergroup', $args);
}

print_cp_footer();

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 116563 $
|| #######################################################################
\*=========================================================================*/
