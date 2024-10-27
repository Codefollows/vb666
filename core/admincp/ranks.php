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
define('CVS_REVISION', '$RCSfile$ - $Revision: 116506 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
global $phrasegroups, $specialtemplates, $vbphrase;
$phrasegroups = ['user', 'cphome', 'cpuser', 'cprank'];
$specialtemplates = [];

// ########################## REQUIRE BACK-END ############################
require_once(dirname(__FILE__) . '/global.php');

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!can_administer('canadminusers'))
{
	print_cp_no_permission();
}

$entryStructure = [
	'active'                => vB_Cleaner::TYPE_BOOL,
	'grouping'              => vB_Cleaner::TYPE_STR,
	'priority'              => vB_Cleaner::TYPE_INT,
	'ranklevel'             => vB_Cleaner::TYPE_UINT,
	'minposts'              => vB_Cleaner::TYPE_UINT,
	'startedtopics'         => vB_Cleaner::TYPE_UINT,
	'registrationtime_days' => vB_Cleaner::TYPE_UINT,
	'reputation'            => vB_Cleaner::TYPE_INT,
	'totallikes'            => vB_Cleaner::TYPE_UINT,
	'rankimg'               => vB_Cleaner::TYPE_STR,
	'usergroupid'           => vB_Cleaner::TYPE_INT,
	'doinsert'              => vB_Cleaner::TYPE_STR,
	'rankhtml'              => vB_Cleaner::TYPE_NOTRIM,
	'rankurl'               => vB_Cleaner::TYPE_NOTRIM,
	'stack'                 => vB_Cleaner::TYPE_UINT,
	'display'               => vB_Cleaner::TYPE_UINT,
];

$cleanerObj = vB::getCleaner();
$rankId = $cleanerObj->clean($_REQUEST['rankid'], vB_Cleaner::TYPE_UINT);

// ############################# LOG ACTION ###############################
log_admin_action(!empty($rankId) ? "rank id = " . $rankId : '');

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################
$assertor = vB::getDbAssertor();
/** @var vB_Api_Userrank */
$rankapi =  vB_Api::instanceInternal('Userrank');

print_cp_header($vbphrase['user_rank_manager_gcprank']);

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'modify';
}

// ###################### Start insert #######################
if ($_POST['do'] == 'insert')
{
	$iClean = $cleanerObj->cleanArray($_POST, $entryStructure);

	if (!$iClean['ranklevel'] OR (!$iClean['rankimg'] AND !$iClean['rankhtml'] AND !$iClean['rankurl']))
	{
		if ($iClean['doinsert'])
		{
			echo '<p><b>' . $vbphrase['invalid_file_path_specified'] . '</b></p>';
			$iClean['rankimg'] = $iClean['doinsert'];
		}
		else
		{
			print_stop_message2('please_complete_required_fields');
		}
	}

	if ($iClean['usergroupid'] == -1)
	{
		$iClean['usergroupid'] = 0;
	}

	if (!empty($iClean['rankhtml']))
	{
		$iClean['rankimg'] = $iClean['rankhtml'];
		$type = 1;
	}
	else if (!empty($iClean['rankurl']))
	{
		$iClean['rankimg'] = $iClean['rankurl'];
		$type = 2;
	}
	else
	{
		$iClean['rankimg'] = preg_replace('/\/$/s', '', $iClean['rankimg']);

		if($dirhandle = @opendir(DIR . '/' . $iClean['rankimg']))
		{
			// Valid directory!
			//readdir($dirhandle);
			//readdir($dirhandle);
			$filesArray = [];
			$allowedExtensions = [
				// Extensions based on the previous list, + webp.
				// We may want to switch this over to the checks in the image handler instead.
				'gif',
				'bmp',
				'jpg',
				'jpeg',
				'png',
				'webp',
			];
			while ($filename = readdir($dirhandle))
			{
				if (substr($filename, 0, 1) === '.')
				{
					continue;
				}
				$filepath = DIR . "/{$iClean['rankimg']}/" . $filename;
				if (is_file($filepath))
				{
					$fileext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
					if (in_array($fileext, $allowedExtensions) )
					{
						$filesArray[] = htmlspecialchars_uni($filename);
					}
				}
			}
			if (empty($filesArray))
			{
				print_stop_message2(['no_images_found_userrank_x', $iClean['rankimg']]);
			}

			print_form_header('admincp/ranks', 'insert', 0, 1, 'name', '');
			print_table_header($vbphrase['images_gcprank']);
			// passthru previous page's data
			construct_hidden_code('grouping', $iClean['grouping']);
			construct_hidden_code('priority', $iClean['priority']);
			construct_hidden_code('usergroupid', $iClean['usergroupid']);
			construct_hidden_code('ranklevel', $iClean['ranklevel']);
			construct_hidden_code('minposts', $iClean['minposts']);
			construct_hidden_code('startedtopics', $iClean['startedtopics']);
			construct_hidden_code('registrationtime_days', $iClean['registrationtime_days']);
			construct_hidden_code('reputation', $iClean['reputation']);
			construct_hidden_code('totallikes', $iClean['totallikes']);
			construct_hidden_code('stack', $iClean['stack']);
			construct_hidden_code('display', $iClean['display']);

			construct_hidden_code('doinsert', $iClean['rankimg']);
			foreach ($filesArray AS $key => $val)
			{
				print_yes_row("<img src='core/" . $iClean['rankimg'] . "/$val' border='0' alt='' align='center' />", 'rankimg', '', '', $iClean['rankimg'] . "/$val");
			}
			print_submit_row($vbphrase['save']);
			closedir($dirhandle);
			exit;
		}
		else
		{
			// Not a valid dir so assume it is a filename
			$iClean['rankimg'] = '/' . ltrim($iClean['rankimg'], '/');

			if (!(@is_file(DIR . $iClean['rankimg'])))
			{
				print_stop_message2('invalid_file_path_specified');
			}
		}
		$type = 0;
	}

	$iClean['grouping'] = trim($iClean['grouping']);

	$regisTimeSeconds = $iClean['registrationtime_days'] * 86400;
	$data = [
		'grouping'         => $iClean['grouping'],
		'priority'         => $iClean['priority'],
		'ranklevel'        => $iClean['ranklevel'],
		'usergroupid'      => $iClean['usergroupid'],
		'minposts'         => $iClean['minposts'],
		'startedtopics'    => $iClean['startedtopics'],
		'registrationtime' => $regisTimeSeconds,
		'reputation'       => $iClean['reputation'],
		'totallikes'       => $iClean['totallikes'],
		'stack'            => $iClean['stack'],
		'display'          => $iClean['display'],
		'type'             => $type,
		'rankurl'          => $iClean['rankurl'],
		'rankimg'          => $iClean['rankimg'],
		'rankhtml'         => $iClean['rankhtml']
	];

	$rankapi->save($data);

	print_stop_message2('saved_user_rank_successfully', 'ranks', ['do'=>'modify']);
}

// ###################### Start edit #######################
if ($_REQUEST['do'] == 'edit' OR $_REQUEST['do'] == 'add')
{
	if ($_REQUEST['do'] == 'edit')
	{
		$ranks = $rankapi->fetchById($rankId);
		print_form_header('admincp/ranks', 'doupdate');
	}
	else
	{
		$ranks = [
			'active'           => 1,
			'grouping'         => '',
			'priority'         => 0,
			'rankid'           => null,
			'ranklevel'        => 1,
			'usergroupid'      => -1,
			'minposts'         => 10,
			'startedtopics'    => 10,
			'registrationtime' => 0,
			'reputation'       => 0,
			'totallikes'       => 0,
			'rankimg'          => '/images/ranks/',
			'type'             => '',
			'stack'            => 0,
			'display'          => 0,
		];
		print_form_header('admincp/ranks', 'insert');
	}

	$ranktext = '';
	$rankurl = '';
	$rankimg = '/images/ranks/';
	if ($ranks['type'] == 1)
	{
		$ranktext = $ranks['rankimg'];

	}
	else if ($ranks['type'] == 2)
	{
		$rankurl = $ranks['rankimg'];
	}
	else
	{
		$rankimg = $ranks['rankimg'];
		//the path should always start with bburl
		$bburl = vB::getDatastore()->getOption('bburl');

		if (substr($rankimg, 0, strlen($bburl)) == $bburl)
		{
			$rankimg = substr($rankimg, strlen($bburl));
		}
	}

	$displaytype = [
		$vbphrase['always'],
		$vbphrase['if_displaygroup_equals_this_group'],
	];

	construct_hidden_code('rankid', $rankId);
	print_table_header(construct_phrase($vbphrase['x_y_id_z'], $vbphrase['user_rank'], '', $rankId));
	print_yes_no_row($vbphrase['active'], 'active', $ranks['active']);
	print_input_row($vbphrase['rank_grouping'], 'grouping', $ranks['grouping']);
	print_input_row($vbphrase['rank_priority'], 'priority', $ranks['priority']);
	print_input_row($vbphrase['times_to_repeat_rank'], 'ranklevel', $ranks['ranklevel']);
	print_chooser_row($vbphrase['usergroup'], 'usergroupid', 'usergroup', $ranks['usergroupid'], $vbphrase['all_usergroups']);
	print_input_row($vbphrase['minimum_posts'], 'minposts', $ranks['minposts']);
	print_input_row($vbphrase['min_startedtopics'], 'startedtopics', $ranks['startedtopics']);
	$secondsToDays = $ranks['registrationtime'] / 86400;
	print_input_row($vbphrase['min_days_registered'], 'registrationtime_days', $secondsToDays);
	print_input_row($vbphrase['min_reputation'], 'reputation', $ranks['reputation']);
	print_input_row($vbphrase['min_likes'], 'totallikes', $ranks['totallikes']);
	print_yes_no_row($vbphrase['stack_rank'], 'stack', $ranks['stack']);
	print_select_row($vbphrase['display_type'], 'display', $displaytype, $ranks['display']);
	print_table_header($vbphrase['rank_type']);
	print_input_row($vbphrase['user_rank_file_path'], 'rankimg', $rankimg);
	print_input_row($vbphrase['or_user_rank_url'], 'rankurl', $rankurl);
	print_input_row($vbphrase['or_you_may_enter_text'], 'rankhtml', $ranktext);

	print_submit_row();
}

// ###################### Start do update #######################
if ($_POST['do'] == 'doupdate')
{
	$iClean = [];
	foreach ($entryStructure AS $field => $type)
	{
		if ($field != 'doinsert')
		{
			$iClean[$field] = $cleanerObj->clean($_POST[$field], $type);
		}
	}

	if (!$iClean['ranklevel'] OR (!$iClean['rankimg'] AND !$iClean['rankhtml'] AND !$iClean['rankurl']))
	{
		print_stop_message2('please_complete_required_fields');
	}

	if ($iClean['rankhtml'])
	{
		$type = 1;
		$iClean['rankimg'] = $iClean['rankhtml'];
	}
	else if ($iClean['rankurl'])
	{
		$type = 2;
		$iClean['rankimg'] = $iClean['rankurl'];
	}
	else
	{
		$type = 0;
		if (!(@is_file(DIR . $iClean['rankimg'])))
		{
			if (is_file(DIR . '/' . $iClean['rankimg'] ))
			{
				$iClean['rankimg'] = '/' . $iClean['rankimg'];
			}
			else
			{
				print_stop_message2('invalid_file_path_specified');
			}
		}

	}

	$regisTimeSeconds = $iClean['registrationtime_days'] * 86400;

	$iClean['grouping'] = trim($iClean['grouping']);
	$data = [
		'active'           => $iClean['active'],
		'grouping'         => $iClean['grouping'],
		'priority'         => $iClean['priority'],
		'ranklevel'        => $iClean['ranklevel'],
		'usergroupid'      => $iClean['usergroupid'],
		'minposts'         => $iClean['minposts'],
		'startedtopics'    => $iClean['startedtopics'],
		'registrationtime' => $regisTimeSeconds,
		'reputation'       => $iClean['reputation'],
		'totallikes'       => $iClean['totallikes'],
		'stack'            => $iClean['stack'],
		'type'             => $type,
		'display'          => $iClean['display'],
		'rankimg'          => $iClean['rankimg'],
		'rankurl'          => $iClean['rankurl'],
		'rankhtml'         => $iClean['rankhtml'],
	];
	$rankapi->save($data, $rankId);

	print_stop_message2('saved_user_rank_successfully', 'ranks', ['do' => 'modify']);
}
// ###################### Start Remove #######################

if ($_REQUEST['do'] == 'remove')
{
	print_form_header('admincp/ranks', 'kill');
	construct_hidden_code('rankid', $rankId);
	print_table_header($vbphrase['confirm_deletion_gcpglobal']);
	print_description_row($vbphrase['are_you_sure_you_want_to_delete_this_user_rank']);
	print_submit_row($vbphrase['yes'], '', 2, $vbphrase['no']);

}

// ###################### Start Kill #######################

if ($_POST['do'] == 'kill')
{
	$rankapi->delete($rankId);

	print_stop_message2('deleted_user_rank_successfully', 'ranks', ['do' => 'modify']);
}

// ###################### Start save_active #######################
if ($_POST['do'] == 'save_active')
{
	$vbulletin->input->clean_array_gpc('r', [
		'active' => vB_Cleaner::TYPE_ARRAY_UINT,
		'prev_active' => vB_Cleaner::TYPE_ARRAY_UINT
	]);
	$active = $vbulletin->GPC['active'];
	$prev_active = $vbulletin->GPC['prev_active'];
	$new_active = array_diff($active, $prev_active);
	$new_inactive = array_diff($prev_active, $active);

	$assertor = vB::getDbAssertor();
	$assertor->update('vBForum:ranks', ['active' => 1], ['rankid' => $new_active]);
	$assertor->update('vBForum:ranks', ['active' => 0], ['rankid' => $new_inactive]);

	// Trigger a rebuild of the 'ranks' datastore cache.. but it's unclear what value
	// this datastore cache actually provides as it's just a serialized store of the
	// whole ranks table.
	// Doing it like this as I'm not entirely sure I want to make buildRanks() public
	vB::getDatastore()->delete('ranks');
	vB_Library::instance('userrank')->haveRanks();

	print_stop_message2('saved_user_rank_successfully', 'ranks', ['do' => 'modify']);
}

// ###################### Start modify #######################
if ($_REQUEST['do'] == 'modify')
{
	$ranks = $rankapi->fetchAll();

	print_form_header2('', '');
	print_table_start2();
	print_table_header($vbphrase['user_rank_manager_gcprank']);

	$colcount = 1;

	$description = [
		$vbphrase['user_ranks_desc'],
		construct_phrase($vbphrase['it_is_recommended_that_you_update_user_titles'], htmlspecialchars(get_admincp_url('misc', [])))
	];

	print_description_row(implode('<br /><br />', $description), false, $colcount);
	print_table_footer();

	print_form_header2('admincp/ranks', 'save_active');
	print_table_start2();

	if ($ranks)
	{
		$colcount = print_rank_cells($ranks, $vbphrase);
	}
	else
	{
		print_table_header('', $colcount, false, '', 'center', false);
		print_description_row($vbphrase['no_user_ranks_defined'], false, $colcount, '', 'center');
	}

	$buttons = [
		['submit', $vbphrase['save_active']],
		construct_link_button($vbphrase['add_new_user_rank'], get_admincp_url('ranks', ['do' => 'add'])),
	];
	print_table_button_footer($buttons, $colcount);
}

// considered private to this script
function print_rank_cells($ranks, $vbphrase)
{
	$headers = [
		$vbphrase['active'],
		$vbphrase['user_rank'],
		$vbphrase['rank_priority'],
		// qualifiers
		$vbphrase['minimum_posts'],
		$vbphrase['min_startedtopics'],
		$vbphrase['min_days_registered'],
		$vbphrase['min_reputation'],
		$vbphrase['min_likes'],
		// other info, controls
		$vbphrase['display_type'],
		$vbphrase['stack_rank'],
		$vbphrase['controls'],
	];
	$colLen = count($headers);

	$ungrouped = false;

	// the $tempgroup check in the foreach below relies on the first pass of $tempgroup not being 0,
	// which it will be if it is init to false.
	$tempgroup = null;
	$groupcounter = 0;
	foreach ($ranks AS $rank)
	{
		$ungrouped = ($rank['grouping'] == '');
		if ($tempgroup !== $rank['grouping'])
		{
			if (!empty($tempgroup))
			{
				print_table_break();
			}
			$tempgroup = $rank['grouping'];
			$groupcounter++;

			if ($ungrouped)
			{
				// Differentiate between our ungrouped ranks and an actual grouping
				// labeled literally "Ungrouped Ranks"
				$headerPhrase = '<i>' . $vbphrase['ungrouped_ranks'] . '</i>';
				$htmlise = false;
			}
			else
			{
				// pre-htmlise so that we can wrap it in the group-checkbox label.
				$headerPhrase = htmlspecialchars_uni($rank['grouping']);
				$htmlise = false;
			}

			$headerPhrase = "<label class=\"h-cursor\">
								<span class=\"h-checkbox-label h-align-middle \">
									<input class=\"h-cursor js-checkbox-master\" data-child=\"js-checkbox-child-{$groupcounter}\" type=\"checkbox\" name=\"\" value=\"1\">
								</span>
								{$headerPhrase}
							</label>";

			print_table_header($headerPhrase, $colLen, $htmlise);
			print_cells_row2($headers, 'thead', 'center');
		}

		$__checked = $rank['active'] ? ' checked="checked"' : '';
		$__prevActive = $rank['active'] ? "<input type=\"hidden\" name=\"prev_active[{$rank['rankid']}]\" value=\"{$rank['rankid']}\">" : '';
		$active = "<label class=\"h-cursor h-flex-left h-gap-4\">
				<span class=\"h-checkbox-label  \">
					<input class=\"h-cursor js-checkbox-child-{$groupcounter}\" type=\"checkbox\" name=\"active[{$rank['rankid']}]\" value=\"{$rank['rankid']}\"{$__checked}>
					$__prevActive
				</span>
			</label>";

		$count = 0;
		$rankhtml = '';
		while ($count++ < $rank['ranklevel'])
		{
			if (!$rank['type'])
			{
				$rankhtml .= "<img src=\"core$rank[rankimg]\" border=\"0\" alt=\"\" />";
			}
			else if ($rank['type'] == 2 )
			{
				$rankhtml .= '<img src="' . $rank['rankimg'] . '"/>';
			}
			else
			{
				$rankhtml .= $rank['rankimg'];
			}
		}

		$registrationTimeDays = $rank['registrationtime'] / 86400;
		$cell = [
			$active,
			$rankhtml,
			$rank['priority'],
			// qualifiers
			$rank['minposts'],
			$rank['startedtopics'],
			$registrationTimeDays,
			$rank['reputation'],
			$rank['totallikes'],
			// other info, controls
			($rank['display'] ? $vbphrase['displaygroup'] : $vbphrase['always']),
			($rank['stack'] ? $vbphrase['yes'] : $vbphrase['no']),
			construct_link_code2($vbphrase['edit'], get_admincp_url('ranks', ['do' => 'edit', 'rankid' => $rank['rankid']])) .
				construct_link_code2($vbphrase['delete'], get_admincp_url('ranks', ['do' => 'remove', 'rankid' => $rank['rankid']])),
		];
		print_cells_row2($cell, '', 'center');
	}
	return $colLen;
}

print_cp_footer();

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 116506 $
|| #######################################################################
\*=========================================================================*/
