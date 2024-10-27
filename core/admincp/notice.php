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
define('CVS_REVISION', '$RCSfile$ - $Revision: 115264 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
global $phrasegroups, $specialtemplates, $vbphrase, $vbulletin;
$phrasegroups = ['notice', 'posting'];
$specialtemplates = [];

// ########################## REQUIRE BACK-END ############################
require_once(dirname(__FILE__) . '/global.php');
$assertor = vB::getDbAssertor();

// ############################# LOG ACTION ###############################
if (!can_administer('canadminnotices'))
{
	print_cp_no_permission();
}

$vbulletin->input->clean_array_gpc('r', ['noticeid' => vB_Cleaner::TYPE_INT]);

log_admin_action($vbulletin->GPC['noticeid'] != 0 ? "notice id = " . $vbulletin->GPC['noticeid'] : '');

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

print_cp_header($vbphrase['notices_manager']);

if (empty($_REQUEST['do']))
{
	if (!empty($_REQUEST['noticeid']))
	{
		$_REQUEST['do'] = 'edit';
	}
	else
	{
		$_REQUEST['do'] = 'modify';
	}
}

// #############################################################################
// remove a notice
if ($_POST['do'] == 'remove')
{
	$vbulletin->input->clean_array_gpc('p', [
		'noticeid' => vB_Cleaner::TYPE_UINT
	]);

	$result = vB_Api::instance('notice')->delete($vbulletin->GPC['noticeid']);
	if (isset($result['errors']))
	{
		print_stop_message_array($result['errors']);
	}

	print_stop_message2('deleted_notice_successfully', 'notice', ['do'=>'modify']);
}

// #############################################################################
// confirm deletion of a notice
if ($_REQUEST['do'] == 'delete')
{
	$vbulletin->input->clean_array_gpc('r', [
		'noticeid' => vB_Cleaner::TYPE_UINT
	]);

	print_delete_confirmation('notice', $vbulletin->GPC['noticeid'], 'notice', 'remove');
}

// #############################################################################
// update or insert a notice
if ($_POST['do'] == 'update')
{
	$vbulletin->input->clean_array_gpc('p', [
		'noticeid'      => vB_Cleaner::TYPE_UINT,
		'title'         => vB_Cleaner::TYPE_NOHTML,
		'html'          => vB_Cleaner::TYPE_STR,
		'displayorder'  => vB_Cleaner::TYPE_UINT,
		'active'        => vB_Cleaner::TYPE_BOOL,
		'persistent'    => vB_Cleaner::TYPE_BOOL,
		'dismissible'   => vB_Cleaner::TYPE_BOOL,
		'noticeoptions' => vB_Cleaner::TYPE_ARRAY_BOOL,
		'criteria'      => vB_Cleaner::TYPE_ARRAY,
	]);
	$noticeid =& $vbulletin->GPC['noticeid'];

	// Check to see if there is criteria
	$criteria = [];
	foreach ($vbulletin->GPC['criteria'] AS $criteriaid =>  $criterion)
	{
		if (!empty($criterion['active']))
		{
			unset($criterion['active']);
			$criteria[$criteriaid] = $criterion;
		}
	}

	$data = [
		'title'         => $vbulletin->GPC['title'],
		'text'          => $vbulletin->GPC['html'],
		'displayorder'  => $vbulletin->GPC['displayorder'],
		'active'        => $vbulletin->GPC['active'],
		'persistent'    => $vbulletin->GPC['persistent'],
		'dismissible'   => $vbulletin->GPC['dismissible'],
		'noticeoptions' => $vbulletin->GPC['noticeoptions'],
		'criteria'      => $criteria,
	];

	if ($vbulletin->GPC['noticeid'])
	{
		$data['noticeid'] = $vbulletin->GPC['noticeid'];
	}

	$result = vB_Api::instance('notice')->save($data);
	if (isset($result['errors']))
	{
		print_stop_message_array($result['errors']);
	}

	print_stop_message2(['saved_notice_x_successfully',  $vbulletin->GPC['title']], 'notice', ['do' => 'modify']);
}

// #############################################################################
// edit a notice
if ($_REQUEST['do'] == 'edit' OR $_REQUEST['do'] == 'add')
{
	function print_notice_criterion($phrase, $optionid, $option, $current)
	{
		//we don't usually like to use IDs for things anymore, but we have a legacy ID for the checkbox already
		$texttemplate = '<input type="text" name="criteria[%1$s][condition%2$d]" size="%3$d" ' .
			'class="bginput js-autocheck-master" data-on="#cb_%1$s" "tabindex="1" value="%4$s" />';

		$selecttemplate = '<select name="criteria[%1$s][condition%2$d]" class="js-autocheck-master" data-on="#cb_%1$s" tabindex="1">"%3$s</select>' ;

		$phraseArgs = [];
		$phraseArgs[] = $phrase;
		switch($option['type'])
		{
			case 'list':
				$default1 = $current['condition1'] ?? $option['default1'] ?? null;
				$phraseArgs[] = sprintf($selecttemplate, $optionid, 1, construct_select_options($option['options'], $default1));
				break;

			case 'boolean':
				break;

			case 'text':
				$default1 = $current['condition1'] ?? $option['default1'] ?? '';
				$default1 = vB::getString()->htmlspecialchars($default1);
				$size = $option['size'] ?? 5;
				$phraseArgs[] = sprintf($texttemplate, $optionid, 1, $size, $default1);
				break;

			case 'dualtext':
				//Deliberately not making size configurable here despite the inconsistancy. We don't have any criteria
				//that use it and implementing it ahead of requirements is a recipe for having to do it again later.
				$default1 = $current['condition1'] ?? $option['default1'] ?? '';
				$default2 = $current['condition2'] ?? $option['default2'] ?? '';

				$default1 = vB::getString()->htmlspecialchars($default1);
				$default2 = vB::getString()->htmlspecialchars($default2);

				$phraseArgs[] = sprintf($texttemplate, $optionid, 1, 5, $default1);
				$phraseArgs[] = sprintf($texttemplate, $optionid, 2, 5, $default2);
				break;

			//currently unused, but leaving in for the time being.
			case 'date':
				$default1 = $current['condition1'] ?? $option['default1'] ?? null;
				$default2 = $current['condition2'] ?? $option['default2'] ?? null;

				$phraseArgs[] = sprintf($texttemplate, $optionid, 1, 10, $default1);
				$phraseArgs[] = sprintf($selecttemplate, $optionid, 2, construct_select_options($option['tzoptions'], $default2));
				break;

			//for now, these are exactly the same, but in the future they will likely be different
			//so keep them as seperate type strings but the same implementation
			case 'daterange':
			case 'time':
				$default1 = $current['condition1'] ?? $option['default1'] ?? null;
				$default2 = $current['condition2'] ?? $option['default2'] ?? null;
				$default3 = $current['condition3'] ?? $option['default3'] ?? null;
				$size = $option['size'] ?? 5;

				$phraseArgs[] = sprintf($texttemplate, $optionid, 1, $size, $default1);
				$phraseArgs[] = sprintf($texttemplate, $optionid, 2, $size, $default2);
				$phraseArgs[] = sprintf($selecttemplate, $optionid, 3, construct_select_options($option['tzoptions'], $default3));
				break;

			default:
				throw new Exception('Invalid criteria type');
				break;
		}

		//we can't wrap the entire phrase in a label because we don't want the input controls we're shoving
		//into the phrase to trigger the checkbox.  Though we might want to vet whether this is still a
		//problem on modern browswers.  The original code where this was implemented is *old*.
		$labelStart = '<label for="cb_' . $optionid . '">';
		for ($i = 1; $i < count($phraseArgs); $i++)
		{
			$phraseArgs[$i] = '</label>' . $phraseArgs[$i] . $labelStart;
		}

		$checkbox = '<input type="checkbox" id="cb_' . $optionid . '" name="criteria[' . $optionid . '][active]" ' .
			'value="1" tabindex="1"' . ($current ? ' checked="checked"' : '') . ' />';

		$text = $labelStart . construct_phrase_from_array($phraseArgs) . '</label>';

		print_description_row($checkbox . $text);
	}

	$vbulletin->input->clean_array_gpc('r', [
		'noticeid' => vB_Cleaner::TYPE_UINT
	]);

	$noticeid = $vbulletin->GPC['noticeid'];

	$noticeApi = vB_Api::instance('notice');

	//get some global notice info
	$notice_name_cache = [];
	$max_displayorder = 0;

	$notice_result = $assertor->select('vBForum:notice', [], 'displayorder', ['noticeid', 'title', 'displayorder']);
	foreach ($notice_result AS $notice)
	{
		if ($notice['noticeid'] != $noticeid)
		{
			$notice_name_cache[$notice['noticeid']] = $notice['title'];
		}

		$max_displayorder = max($notice['displayorder'], $max_displayorder);
	}

	// set some default values
	$notice = [
		'title' => '',
		'displayorder' => $max_displayorder + 10,
		'active' => true,
		'persistent' => true,
		'dismissible' => true,
		'noticeoptions' => [
			'allowhtml' => true,
			'allowbbcode' => false,
			'parseurl' => false,
			'allowsmilies' => false,
		],
		'criteria' => [],
	];

	$noticetext = '';

	// are we editing or adding?
	if ($noticeid)
	{
		$result = $noticeApi->getNotice($noticeid);
		if (isset($result['errors']))
		{
			print_stop_message_array($result['errors']);
		}
		$notice = $result['notice'];

		$phrase_result = $assertor->getRow('vBForum:phrase', ['varname' => $notice['notice_phrase_varname'], 'languageid' => 0]);
		$noticetext = $phrase_result['text'];
	}

	// build list of usergroup titles
	$usergroup_options = [];
	foreach ($vbulletin->usergroupcache AS $usergroupid => $usergroup)
	{
		$usergroup_options[$usergroupid] = $usergroup['title'];
	}

	$channels = vB_Api::instanceInternal('search')->getChannels(true, ['no_perm_check' => true]);
	foreach ($channels AS $nodeid => $channel)
	{
		$channel_options[$nodeid] = construct_depth_mark($channel['depth'], '--') . ' ' . $channel['title'];
	}


	// build list of style names
	$stylecache = vB_Library::instance('Style')->fetchStyles(false, false);
	$style_options = [];
	foreach ($stylecache AS $styleid => $style)
	{
		$style_options[$styleid] = construct_depth_mark($style['depth'], '--') . ' ' . $style['title'];
	}

	$tzoptions = [
		0 => $vbphrase['user_timezone'],
		1 => $vbphrase['utc_universal_time'],
	];

	// build the list of criteria options
	$criteria_options = [
		'in_usergroup_x' => [
			'type' => 'list',
			'options' => $usergroup_options,
			'default1' => 2,
		],

		'not_in_usergroup_x' => [
			'type' => 'list',
			'options' => $usergroup_options,
			'default1' => 6,
		],

		'browsing_forum_x' => [
			'type' => 'list',
			'options' => $channel_options,
		],

		'browsing_forum_x_and_children' => [
			'type' => 'list',
			'options' => $channel_options,
		],

		'style_is_x' => [
			'type' => 'list',
			'options' => $style_options,
		],

		'no_visit_in_x_days' => [
			'type' => 'text',
			'default1' => 30,
		],

		'no_posts_in_x_days' => [
			'type' => 'text',
			'default1' => 30,
		],

		'has_x_postcount' => [
			'type' => 'dualtext',
		],

		'has_never_posted' => [
			'type' => 'boolean',
		],

		'has_x_infraction_points' => [
			'type' => 'dualtext',
			'default1' => 5,
			'default2' => 10,
		],

		'has_x_reputation' => [
			'type' => 'dualtext',
			'default1' => 90,
			'default2' => 100,
		],

		'pm_storage_x_percent_full' => [
			'type' => 'dualtext',
			'default1' => 90,
			'default2' => 100,
		],

		'username_is' => [
			'type' => 'text',
			'size' => 20,
			'default1' => $vbulletin->userinfo['username'],
		],

		'is_birthday' => [
			'type' => 'boolean',
		],

		'came_from_search_engine' => [
			'type' => 'boolean',
		],

		'in_coventry' => [
			'type' => 'boolean'
		],

		'is_date_range' => [
			'type' => 'daterange',
			'tzoptions' => $tzoptions,
			'size' => 10,
			'default1' => vbdate('d-m-Y', TIMENOW, false, false),
			'default2' => vbdate('d-m-Y', TIMENOW, false, false),
		],

		'is_time' => [
			'type' => 'time',
			'tzoptions' => $tzoptions,
			'default1' => vbdate('H:i', TIMENOW, false, false),
			//I am not sure why this isn't vbdate('H', TIMENOW + 3600, false, false) but they aren't exactly
			//the same and I don't want to spent the time to figure out if the differences matter
			'default2' => (intval(vbdate('H', TIMENOW, false, false)) + 1) . vbdate(':i', TIMENOW, false, false),
		],

		/*
		* These are flagged for a future version
		'userfield_x_equals_y' => [
		],

		'userfield_x_contains_y' => [
		],
		*/
	];

	if (!empty($notice_name_cache))
	{
		$criteria_options['notice_x_not_displayed'] = [
			'type' => 'list',
			'options' => $notice_name_cache,
		];
	}

	// build the editor form

	$table_title = $vbphrase['add_new_notice'];
	$translations_block = '';
	if ($noticeid)
	{
		$table_title = $vbphrase['edit_notice'] . " <span class=\"normal\">$notice[title]</span>";
		$translations_block = '<dfn>' . construct_translation_link($vbphrase['translations'], 'global', $notice['notice_phrase_varname']) . '</dfn>';
	}

	print_form_header('admincp/notice', 'update');
	construct_hidden_code('noticeid', $vbulletin->GPC['noticeid']);
	print_table_header($table_title);

	print_input_row($vbphrase['title'] . '<dfn>' . $vbphrase['notice_title_description'] . '</dfn>', 'title', $notice['title'], 0, 60);

	$textareadescription = $vbphrase['notice_html'] . '<dfn>' . $vbphrase['notice_html_description'] . '</dfn>' . $translations_block;
	print_textarea_row($textareadescription, 'html', $noticetext, 8, 60, true, false);

	print_input_row($vbphrase['display_order'], 'displayorder', $notice['displayorder'], 0, 10);
	print_yes_no_row($vbphrase['active_gcpglobal'] . '<dfn>' . $vbphrase['notice_active_description'] . '</dfn>', 'active', $notice['active']);
	print_yes_no_row($vbphrase['persistent'] . '<dfn>' . $vbphrase['persistent_description'] . '</dfn>', 'persistent', $notice['persistent']);
	print_yes_no_row($vbphrase['dismissible'], 'dismissible', $notice['dismissible']);
	print_yes_no_row($vbphrase['allow_bbcode'], 'noticeoptions[allowbbcode]', $notice['noticeoptions']['allowbbcode']);
	print_yes_no_row($vbphrase['automatically_parse_links_in_text'], 'noticeoptions[parseurl]', $notice['noticeoptions']['parseurl']);
	print_yes_no_row($vbphrase['allow_html'], 'noticeoptions[allowhtml]', $notice['noticeoptions']['allowhtml']);
	print_yes_no_row($vbphrase['allow_smilies'], 'noticeoptions[allowsmilies]', $notice['noticeoptions']['allowsmilies']);
	print_description_row('<strong>' . $vbphrase['display_notice_if_elipsis'] . '</strong>', false, 2, 'tcat', '', 'criteria');

	foreach ($criteria_options AS $optionid => $option)
	{
		print_notice_criterion($vbphrase[$optionid . '_criteria'], $optionid, $option, $notice['criteria'][$optionid] ?? null);
	}

	print_submit_row();
}

// #############################################################################
// quick update of active and display order fields
if ($_POST['do'] == 'quickupdate')
{
	$vbulletin->input->clean_array_gpc('p', [
		'active'            => vB_Cleaner::TYPE_ARRAY_BOOL,
		'persistent'        => vB_Cleaner::TYPE_ARRAY_BOOL,
		'dismissible'				=> vB_Cleaner::TYPE_ARRAY_BOOL,
		'displayorder'      => vB_Cleaner::TYPE_ARRAY_UINT,
		'displayorderswap'  => vB_Cleaner::CONVERT_KEYS
	]);

	//we should really pull this from the post data in case we have some subset of all
	//notices displayed (this only works because the notices are not paginated);
	$noticeids = $assertor->getColumn('vBForum:notice', 'noticeid', []);
	foreach ($noticeids AS $noticeid)
	{
		$data = [
			'active' => intval($vbulletin->GPC['active'][$noticeid] ?? false),
			'persistent' => intval($vbulletin->GPC['persistent'][$noticeid] ?? false),
			'dismissible' => intval($vbulletin->GPC['dismissible'][$noticeid] ?? false),
			'displayorder' => $vbulletin->GPC['displayorder'][$noticeid],
		];
		$assertor->update('vBForum:notice', $data, ['noticeid' => $noticeid]);
	}

	//remove the dismiss notices for items that are no longer dismissable.
	//in reality dismissable is only going to contain true values due to the way
	//checkboxes work but let's proof it against changes like fixing the checkbox
	//control logic to include the hidden field trick to ensure the value
	$nondimissableids = array_diff($noticeids, array_keys(array_filter($vbulletin->GPC['dismissible'])));
	$assertor->delete('vBForum:noticedismissed', ['noticeid' => $nondimissableids]);

	// handle swapping
	if (!empty($vbulletin->GPC['displayorderswap']))
	{
		list($orig_noticeid, $swap_direction) = explode(',', $vbulletin->GPC['displayorderswap'][0]);

		if (isset($vbulletin->GPC['displayorder']["$orig_noticeid"]))
		{
			$notice_orig = [
				'noticeid'     => $orig_noticeid,
				'displayorder' => $vbulletin->GPC['displayorder']["$orig_noticeid"]
			];

			$sort = ['field' => ['displayorder', 'title']];
			$queryConditions = [];
			switch ($swap_direction)
			{
				case 'lower':
				{
					$comp = '<';
					$queryConditions[vB_dB_Query::CONDITIONS_KEY][] = ['field' => 'displayorder', 'value' => $notice_orig['displayorder'], vB_dB_Query::OPERATOR_KEY => vB_dB_Query::OPERATOR_LT];
					$sort['direction'] = [vB_dB_Query::SORT_DESC, vB_dB_Query::SORT_ASC];
					break;
				}
				case 'higher':
				{
					$comp = '>';
					$queryConditions[vB_dB_Query::CONDITIONS_KEY][] = ['field' => 'displayorder', 'value' => $notice_orig['displayorder'], vB_dB_Query::OPERATOR_KEY => vB_dB_Query::OPERATOR_GT];
					$sort['direction'] = [vB_dB_Query::SORT_ASC, vB_dB_Query::SORT_ASC];
					break;
				}
				default:
				{
					$comp = false;
					$sort = false;
				}
			}

			if ($comp AND $sort AND $notice_swap = $assertor->getRow('vBForum:notice', $queryConditions, $sort))
			{
				$assertor->assertQuery('vBForum:doNoticeSwap', [
					'orig_noticeid' => $notice_orig['noticeid'],
					'swap_noticeid' => $notice_swap['noticeid'],
					'orig_displayorder' => $notice_orig['displayorder'],
					'swap_displayorder' => $notice_swap['displayorder']
				]);
			}
		}
	}

	vB_Library::instance('notice')->buildNoticeDatastore();
	$_REQUEST['do'] = 'modify';
}

// #############################################################################
// list existing notices
if ($_REQUEST['do'] == 'modify')
{
	print_form_header('admincp/notice', 'quickupdate');
	print_column_style_code(['width:100%', 'white-space:nowrap']);
	print_table_header($vbphrase['notices_manager']);

	$notice_result = $assertor->getRows('vBForum:notice', [], ['displayorder', 'title']);
	$notice_count = count($notice_result);

	if ($notice_count)
	{
		print_description_row('<label><input type="checkbox" checked="checked" class="js-checkbox-master" />' . $vbphrase['toggle_active_status_for_all'] .
			'</label><input type="image" value="" src="images/clear.gif" name="normalsubmit" />', false, 2, 'thead checkbox-in-thead');

		$upImage = get_cpstyle_href('move_up.gif');
		$downImage = get_cpstyle_href('move_down.gif');

		foreach ($notice_result AS $notice)
		{
			print_label_row(
				'<a href="admincp/notice.php?do=edit&amp;noticeid=' . $notice['noticeid'] . '" title="' . $vbphrase['edit_notice'] . '">' . $notice['title'] . '</a>',
				'<div style="white-space:nowrap">' .
				'<label class="smallfont"><input type="checkbox" class="js-checkbox-child" name="active[' . $notice['noticeid'] . ']" value="1"' . ($notice['active'] ? ' checked="checked"' : '') . ' />' . $vbphrase['active_gcpglobal'] . '</label> ' .
				'<label class="smallfont"><input type="checkbox" name="persistent[' . $notice['noticeid'] . ']" value="1"' . ($notice['persistent'] ? ' checked="checked"' : '') . ' />' . $vbphrase['persistent'] . '</label> ' .
				'<label class="smallfont"><input type="checkbox" name="dismissible[' . $notice['noticeid'] . ']" value="1"' . ($notice['dismissible'] ? ' checked="checked"' : '') . ' />' . $vbphrase['dismissible'] . '</label> &nbsp; ' .
				'<input type="image" src="' . $downImage . '" name="displayorderswap[' . $notice['noticeid'] . ',higher]" />' .
				'<input type="text" name="displayorder[' . $notice['noticeid'] . ']" value="' . $notice['displayorder'] . '" class="bginput" size="4" title="' . $vbphrase['display_order'] . '" style="text-align:' . vB_Template_Runtime::fetchStyleVar('right') . '" />' .
				'<input type="image" src="' . $upImage . '" name="displayorderswap[' . $notice['noticeid'] . ',lower]" />' .
				construct_link_code($vbphrase['edit'], 'notice.php?do=edit&amp;noticeid=' . $notice['noticeid']) .
				construct_link_code($vbphrase['delete'], 'notice.php?do=delete&amp;noticeid=' . $notice['noticeid']) .
				'</div>'
			);
		}
	}

	$buttons = [];
	if ($notice_count)
	{
		$buttons[] = ['submit', $vbphrase['save']];
	}
	$buttons[] = construct_link_button($vbphrase['add_new_notice'] , get_admincp_url('notice', ['do' => 'add']));
	print_table_button_footer($buttons);
}

print_cp_footer();

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 115264 $
|| #######################################################################
\*=========================================================================*/
