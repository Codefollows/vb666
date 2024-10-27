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
define('CVS_REVISION', '$RCSfile$ - $Revision: 111008 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
global $phrasegroups, $specialtemplates, $vbphrase, $vbulletin;
$phrasegroups = ['cpglobal', 'cphome', 'cppermission',];
$specialtemplates = [];

// ########################## REQUIRE BACK-END ############################
require_once(dirname(__FILE__) . '/global.php');

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!can_administer('canadminsettings'))
{
	print_cp_no_permission();
}

// ############################# LOG ACTION ###############################
log_admin_action();


// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

print_cp_header($vbphrase['calendar_manager'], '', [
	get_admincp_script_tag('vbulletin_paginate.js'),
]);


if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'list';
}

/** @var vB_Api_Calendar */
$api = vB_Api::instanceInternal('calendar');
$assertor = vB::getDbAssertor();

// print_form_header2() always appends '.php?do'...
$thispagelink = 'admincp/calendar';
$thispagedo = 'list';

function getPageAndPerPage($GPC)
{
	$page = max($GPC['page'], 1);
	// print_pagination_form() & associated JS expect perpage of 0 to be "Show All".
	// So we have to differentiate between "default because first load / never set" and
	// "show all"
	$perpage = max($GPC['perpage'], 0);
	if (!isset($_REQUEST['perpage']))
	{
		$perpage = 10;
	}

	return [
		'p' => $page,
		'pp' => $perpage,
	];
}


// ###################### Start List #######################
if ($_REQUEST['do'] == 'list')
{
	$vbulletin->input->clean_array_gpc('r', [
		'page'	  => vB_Cleaner::TYPE_INT,
		'perpage' => vB_Cleaner::TYPE_INT,
	]);
	['p' => $page, 'pp' => $perpage] = getPageAndPerPage($vbulletin->GPC);


	$totalcount = $assertor->getField('calendarevent', [
		vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SUMMARY,
		vB_dB_Query::COLUMNS_KEY => ['COUNT(*)'],
	]);
	// allow perpage = 0 to be show all
	$conditions = [];
	if ($perpage > 0)
	{
		$conditions = [
			vB_dB_Query::PARAM_LIMIT => $perpage,
			vB_dB_Query::PARAM_LIMITSTART => ($page - 1) * $perpage,
		];
	}
	$sort = [
		'field' => ['month', 'day'],
		'direction' => [vB_dB_Query::SORT_ASC, vB_dB_Query::SORT_ASC],
	];
	$holidays = $assertor->assertQuery('calendarevent', $conditions, $sort);

	$paginationParams = [
		'page' => $page,
		'perpage' => $perpage,
	];
	print_pagination_form($thispagelink, $thispagedo, $paginationParams, $totalcount);

	$checkall = "
		<label class=\"h-cursor\">
			<span class=\"h-checkbox-label h-align-middle \">
				<input class=\"h-cursor js-checkbox-master\" data-child=\"js-checkbox-child-top\" type=\"checkbox\" name=\"checkall\" value=\"1\">
			</span>
			{$vbphrase['all']}
		</label>";
	$headers = [
		$checkall,
		"<span class=\"h-center-content\">" . $vbphrase['month'] . "</span>",
		"<span class=\"h-center-content\">" . $vbphrase['day'] . "</span>",
		"<span class=\"h-center-content\">" . $vbphrase['year'] . "</span>",
		"<span class=\"h-center-content\">" . $vbphrase['controls'] . "</span>",
	];
	$colSpan = count($headers);

	// We want the left column left-aligned, everything else center aligned. We may want to push this to the print_cells_row2 function if this config
	// turns out to be common.
	$aligns = array_fill(0, $colSpan, 'vbcenter');
	$aligns[0] = 'vbleft';

	print_form_header2($thispagelink, 'delete_holidays');
	print_table_start();
	print_table_header($vbphrase['view_holidays'], $colSpan);
	$bgClasses = array_fill(0, $colSpan, 'thead');
	$bgClasses[0] = 'thead h-fixed-width-32';
	print_cells_row2($headers, $bgClasses, $aligns);

	construct_hidden_code('page', $page);
	construct_hidden_code('perpage', $perpage);

	//print_cells_row2($firstRow, [], $aligns);
	// Hack to pre-increment the global counter for our fetch_prev_row_bgclass() use below.
	fetch_row_bgclass();
	$strUtil = vB::getString();
	$recurring = "<i class='fa-solid fa-repeat h-cursor' title='{$vbphrase['calendar_recurring']}'></i>";
	foreach ($holidays AS $__row)
	{
		$__id = $__row['eventid'];
		$__url = get_admincp_url('calendar', ['do' => 'edit', 'eventid' => $__id]);
		$__editLink = construct_link_code2($vbphrase['edit'], $__url, false, '', 'h-center-content');
		$__desc = $strUtil->htmlSpecialChars($__row['description'] ?? '');
		// don't wrap title (e.g. icons etc), calendar view normally shouldn't wrap it.
		$__title = "<span class=\"nowrap\" title=\"{$__desc}\">{$__row['title']}</span>";
		// put the checkbox next to the title (inside the label).
		$__checkbox =  "<label class=\"h-cursor h-flex-left h-gap-4\">
				<span class=\"h-checkbox-label  \">
					<input class=\"h-cursor js-checkbox-child-top\" type=\"checkbox\" name=\"eventid[]\" value=\"$__id\">
				</span>
				$__title
			</label>";
		$rowCells = [
			//"<span class=\"nowrap\" title=\"{$__desc}\">{$__row['title']}</span>",
			$__checkbox,
			$__row['month'],
			$__row['day'],
			($__row['is_recurring'] ? $recurring : $__row['year']),
			//$__row['rrule'], // recurrence rule
			$__editLink,
		];

		// print_cells_row2() will always call fetch_row_bgclass() which increments the global counter. fetch_prev..() does not increment the counter.
		$__defaultBgClass = fetch_prev_row_bgclass();
		// vertically align every column (i.e. checkboxes vs text labels) to look a bit neater.
		$__bgClasses = array_fill(0, $colSpan, $__defaultBgClass . ' h-align-middle--only');
		print_cells_row2($rowCells, $__bgClasses, $aligns);
	}

	$args = [
		'do' => 'edit',
		'page' => $page,
		'perpage' => $perpage,
	];
	$editUrl = get_admincp_url('calendar', $args);
	$buttons = [
		// note, the bulk delete action is a form submit because we need the eventid checkboxes.
		// Otherwise, we'd need to js-submit in order to carry the eventids over.
		['submit', $vbphrase['delete_holidays']],
		construct_link_button($vbphrase['add_new_holiday'] , $editUrl),
		//['submit', $vbphrase['reactions_save_options']],
	];


	print_table_button_footer($buttons, $colSpan);

	print_pagination_form($thispagelink, $thispagedo, $paginationParams, $totalcount);
}

// ###################### Start Delete #######################
if ($_REQUEST['do'] == 'delete_holidays')
{
	$vbulletin->input->clean_array_gpc('r', [
		'eventid' => vB_Cleaner::TYPE_ARRAY_INT,
		// preserve for redirect
		'page' => vB_Cleaner::TYPE_INT,
		'perpage' => vB_Cleaner::TYPE_INT,
	]);
	$eventids = $vbulletin->GPC['eventid'];

	$events = $assertor->getRows('calendarevent', ['eventid' => $eventids]);
	if (count($events) <= 0)
	{
		print_stop_message('please_complete_required_fields');
	}

	print_form_header2($thispagelink, 'kill_holidays');

	$titles = [];
	$strUtil = vB::getString();
	foreach ($events AS $__row)
	{
		construct_hidden_code("eventid[{$__row['eventid']}]", $__row['eventid']);
		$__desc = $strUtil->htmlSpecialChars($__row['description'] ?? '');
		$__row['month'] = str_pad($__row['month'], 2, '0', STR_PAD_LEFT);
		$__row['day'] = str_pad($__row['day'], 2, '0', STR_PAD_LEFT);
		$__date = "{$__row['month']}-{$__row['day']}";
		$titles[] = "<li><span title=\"$__desc\">{$__row['title']} ($__date)</span></li>";
	}
	$titles = '<ul>' . implode('<br />', $titles) . '</ul>';

	construct_hidden_code('page', $vbulletin->GPC['page']);
	construct_hidden_code('perpage', $vbulletin->GPC['perpage']);
	print_table_start();
	print_table_header($vbphrase['confirm_deletion_gcpglobal']);
	print_description_row(construct_phrase($vbphrase['are_you_sure_want_to_delete_holidays_x'], $titles));
	print_table_button_footer([['submit', $vbphrase['delete']], ['goback', $vbphrase['cancel']]]);
}

if ($_REQUEST['do'] == 'kill_holidays')
{
	$vbulletin->input->clean_array_gpc('r', [
		'eventid' => vB_Cleaner::TYPE_ARRAY_INT,
		// preserve for redirect
		'page' => vB_Cleaner::TYPE_INT,
		'perpage' => vB_Cleaner::TYPE_INT,
	]);
	$eventids = $vbulletin->GPC['eventid'];
	if ($eventids)
	{
		$assertor->delete('calendarevent', ['eventid' => $eventids]);
	}

	$args = [
		'do' => 'list',
		'page' => $vbulletin->GPC['page'],
		'perpage' => $vbulletin->GPC['perpage'],
	];
	$url = get_admincp_url('calendar', $args);
	print_cp_redirect($url, 2);
}

// ###################### Start Save #######################
if ($_REQUEST['do'] == 'edit')
{
	$vbulletin->input->clean_array_gpc('r', [
		'eventid' => vB_Cleaner::TYPE_INT,
		// preserve for redirect
		'page' => vB_Cleaner::TYPE_INT,
		'perpage' => vB_Cleaner::TYPE_INT,
	]);
	$eventid = $vbulletin->GPC['eventid'];
	if ($eventid)
	{
		$event = $assertor->getRow('calendarevent', ['eventid' => $eventid]);
	}
	else
	{
		$event = [
			'eventid' => 0,
			'title' => '',
			'description' => '',
			'month' => '',
			'day' => '',
			'year' => '',
			'is_recurring' => 1,
			//'rrule' => '',
		];
	}


	print_form_header2($thispagelink, 'save');
	construct_hidden_code('page', $vbulletin->GPC['page']);
	construct_hidden_code('perpage', $vbulletin->GPC['perpage']);
	if ($eventid)
	{
		construct_hidden_code('eventid', $eventid);
	}
	print_table_start();
	print_table_header($vbphrase['edit_holiday']);

	print_textarea_row2($vbphrase['title'], 'title', $event['title']);
	print_textarea_row2($vbphrase['description'], 'description', $event['description'] ?? '');
	print_input_row($vbphrase['month'], 'month', $event['month']);
	print_input_row($vbphrase['day'], 'day', $event['day']);

	print_yes_no_row($vbphrase['calendar_recurring'], 'recurring', $event['is_recurring']);
	print_input_row($vbphrase['calendar_year_desc'], 'year', $event['year']);

	//print_input_row($vbphrase['rrule'], 'rrule', $event['rrule']);
	print_table_default_footer($vbphrase['save']);
}

// ###################### Start Save #######################
if ($_REQUEST['do'] == 'save')
{
	$vbulletin->input->clean_array_gpc('p', [
		'eventid' => vB_Cleaner::TYPE_INT,
		'title' => vB_Cleaner::TYPE_STR,
		'description' => vB_Cleaner::TYPE_STR,
		'month' => vB_Cleaner::TYPE_INT,
		'day' => vB_Cleaner::TYPE_INT,
		'recurring' => vB_Cleaner::TYPE_BOOL,
		'year' => vB_Cleaner::TYPE_INT,
		//'rrule' => vB_Cleaner::TYPE_STR,
		// preserve for redirect
		'page' => vB_Cleaner::TYPE_INT,
		'perpage' => vB_Cleaner::TYPE_INT,
	]);

	if ($vbulletin->GPC['recurring'])
	{
		$vbulletin->GPC['year'] = 0;
	}
	else if ($vbulletin->GPC['year'] <= 0)
	{
		print_stop_message(['please_complete_required_fields_x', $vbphrase['year']]);
	}
	unset($vbulletin->GPC['recurring']);

	$eventid = $vbulletin->GPC['eventid'];
	$event = [
		'title' => $vbulletin->GPC['title'],
		'description' => $vbulletin->GPC['description'],
		'month' => $vbulletin->GPC['month'],
		'day' => $vbulletin->GPC['day'],
		'year' => $vbulletin->GPC['year'],
		//'rrule' => $vbulletin->GPC['rrule'],
	];

	$required = [
		'title',
		'month',
		'day',
	];
	$missing = [];
	foreach ($required AS $__fieldname)
	{
		if (empty($event[$__fieldname]))
		{
			$missing[] = $vbphrase[$__fieldname] ?? $__fieldname;
		}
	}
	if (!empty($missing))
	{
		print_stop_message(['please_complete_required_fields_x', implode('<br />', $missing)]);
	}

	if (!validate_date_internal($vbulletin->GPC['month'], $vbulletin->GPC['day'], $vbulletin->GPC['year']))
	{
		print_stop_message('invalid_date_specified');
	}


	if ($eventid)
	{
		$assertor->update('calendarevent', $event, ['eventid' => $eventid]);
	}
	else
	{
		$assertor->insert('calendarevent', $event);
	}

	//$phrase = ['saved_holiday_x_successfully', $event['title']];
	$args = [
		'do' => 'list',
		'page' => $vbulletin->GPC['page'],
		'perpage' => $vbulletin->GPC['perpage'],
	];
	$url = get_admincp_url('calendar', $args);
	print_cp_redirect($url, 2);
}



print_cp_footer();

// This function should be considered private to this script.
function validate_date_internal($month, $day, $year) : bool
{
	if ($month < 1 OR $month > 12)
	{
		return false;
	}

	if ($day < 1 OR $day > 31)
	{
		return false;
	}

	// 65535 = SMALLINT UNSIGNED maximum
	if ($year < 0 OR $year > 65535)
	{
		return false;
	}

	// year = 0 means recurring, so unclear what to do for things like leapdays.
	if ($year > 0)
	{
		return checkdate($month, $day, $year);
	}
	else
	{
		// For recurring years, let's consider both regular & leap years and if it passes either,
		// consider it valid.
		$checkLeap = checkdate($month, $day, 2000);
		$checkRegular = checkdate($month, $day, 2001);
		return ($checkLeap OR $checkRegular);
	}


	return true;
}
{

}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 111008 $
|| #######################################################################
\*=========================================================================*/
