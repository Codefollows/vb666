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
define('CVS_REVISION', '$RCSfile$ - $Revision: 110630 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
global $phrasegroups, $specialtemplates, $vbphrase, $vbulletin;
// todo...
$phrasegroups = ['cphome', 'style', 'timezone',];

// ########################## REQUIRE BACK-END ############################
require_once(dirname(__FILE__) . '/global.php');

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!vB::getUserContext()->hasAdminPermission('canadminstyles'))
{
	print_cp_no_permission();
}

// ############################# LOG ACTION ###############################
log_admin_action();

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

print_cp_header($vbphrase['styleschedule_manager']);

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'list';
}

/** @var vB_Api_Styleschedule */
$scheduleApi = vB_Api::instance('styleschedule');

// because $db is assigned to a reference in core/includes/init.php (which gets included by admincp/global.php)
// reassigning something else to $db will completely hose everything later because the value overwrites back
// to $vbulletin->db, which the shutdown proceses depend on.
//$db = vB::getDbAssertor();
$assertor = vB::getDbAssertor();

// ########################################################################

if ($_POST['do'] == 'dodelete')
{
	$vbulletin->input->clean_array_gpc('p', [
		'scheduleid' => vB_Cleaner::TYPE_UINT,
	]);

	$result = $scheduleApi->deleteSchedule($vbulletin->GPC['scheduleid']);
	print_stop_message_on_api_error($result);
	print_stop_message2('styleschedule_deleted', 'styleschedule', ['do'=>'list']);
}

if ($_REQUEST['do'] == 'deleteschedule')
{
	$vbulletin->input->clean_array_gpc('r', [
		'scheduleid' => vB_Cleaner::TYPE_UINT,
	]);

	//swap the message on error.
	$schedule = $scheduleApi->getSchedule($vbulletin->GPC['scheduleid']);
	if(isset($schedule['errors']))
	{
		print_stop_message2(['could_not_find', '<b>styleschedule</b>', 'scheduleid', $vbulletin->GPC['scheduleid']]);
	}
	/** @var vB_Entity_Styleschedule */
	$schedule = $schedule['schedule'];
	$schedule = $schedule->toArray();

	$description = construct_phrase(
		$vbphrase['are_you_sure_want_to_delete_styleschedule_x'],
		htmlspecialchars_uni($schedule['title']),
		'scheduleid',
		$schedule['scheduleid']
	);

	$header = construct_phrase($vbphrase['confirm_deletion_x'], htmlspecialchars_uni($schedule['title']));
	$hidden = ['scheduleid' => $schedule['scheduleid']];
	print_confirmation($vbphrase, 'styleschedule', 'dodelete', $description, $hidden, $header);
}

// ########################################################################

if ($_POST['do'] == 'saveschedule')
{
	$vbulletin->input->clean_array_gpc('p', [
		'scheduleid' => vB_Cleaner::TYPE_UINT,
		'dostyleid' => vB_Cleaner::TYPE_UINT,
		'enabled' => vB_Cleaner::TYPE_BOOL,
		'startdate' => vB_Cleaner::TYPE_ARRAY_INT,
		'startdate_tzoffset' => vB_Cleaner::TYPE_STR,
		'enddate' => vB_Cleaner::TYPE_ARRAY_INT,
		'enddate_tzoffset' => vB_Cleaner::TYPE_STR,
		'recurring' => vB_Cleaner::TYPE_BOOL,
		'priority' => vB_Cleaner::TYPE_INT,
		'overridechannelcustom' => vB_Cleaner::TYPE_BOOL,
		'overrideusercustom' => vB_Cleaner::TYPE_BOOL,
		'title' => vB_Cleaner::TYPE_STR,
	]);

	$startdate = arrayToMysqlTimeString($vbulletin->GPC['startdate']);
	$enddate = arrayToMysqlTimeString($vbulletin->GPC['enddate']);

	$data = [
		'styleid' => $vbulletin->GPC['dostyleid'],
		'enabled' => $vbulletin->GPC['enabled'],
		'startdate' => $startdate,
		'startdate_tzoffset' => $vbulletin->GPC['startdate_tzoffset'],
		'enddate' => $enddate,
		'enddate_tzoffset' => $vbulletin->GPC['enddate_tzoffset'],
		'useyear' => !$vbulletin->GPC['recurring'],
		'priority' => $vbulletin->GPC['priority'],
		'overridechannelcustom' => $vbulletin->GPC['overridechannelcustom'],
		'overrideusercustom' => $vbulletin->GPC['overrideusercustom'],
		'title' => $vbulletin->GPC['title'],
	];
	if (!empty($vbulletin->GPC['scheduleid']))
	{
		$data['scheduleid'] = $vbulletin->GPC['scheduleid'];
	}
	$result = $scheduleApi->saveSchedule($data);
	print_stop_message_on_api_error($result);
	print_stop_message2('styleschedule_saved', 'styleschedule', ['do'=>'list']);
}

// ########################################################################
//these actions are 100% the same and we might want to collapse them into just edit
if ($_REQUEST['do'] == 'add' OR $_REQUEST['do'] == 'edit')
{
	$vbulletin->input->clean_array_gpc('r', [
		'scheduleid' => vB_Cleaner::TYPE_UINT,
	]);

	$phraseApi = vB_Api::instance('phrase');

	print_form_header2('admincp/styleschedule', 'saveschedule');
	print_table_start2();

	if ($vbulletin->GPC['scheduleid'])
	{
		$schedule = $scheduleApi->getSchedule($vbulletin->GPC['scheduleid']);
		print_stop_message_on_api_error($schedule);
		/** @var vB_Entity_Styleschedule */
		$schedule = $schedule['schedule'];
		$schedule = $schedule->toArray();
		print_table_header($vbphrase['edit_styleschedule']);
		construct_hidden_code('scheduleid', $schedule['scheduleid']);

		// Internally, we actually set up the hh:mm:ss for the schedules in case we need that in the future.
		// For now however, we're going to ignore that and assume 00:00:00 ~ 23:59:59 in the backend (time
		// is when not explicitly provided), so we should snip them off here.
		$schedule['startdate'] = removeTimeFromDatetime($schedule['startdate']);
		$schedule['enddate'] = removeTimeFromDatetime($schedule['enddate']);
	}
	else
	{
		$date = new DateTime('now');
		$year = $date->format('Y');
		$schedule = [
			'styleid'               => 0,
			'enabled'               => true,
			'startdate'             => "$year-01-01",
			'startdate_tzoffset'    => 0,
			'enddate'               => "$year-12-31",
			'enddate_tzoffset'      => 0,
			'useyear'               => false,
			'priority'              => 10,
			'overridechannelcustom' => true,
			'overrideusercustom'    => true,
			'title'                 => '',
		];

		print_table_header($vbphrase['add_styleschedule']);
	}



	print_style_chooser_row('dostyleid', $schedule['styleid'], null, $vbphrase['use_style_for_schedule'], false);
	print_yes_no_row($vbphrase['enabled'], 'enabled', $schedule['enabled']);

	[$year, $month, $day] = explode('-', $schedule['startdate']);
	print_time_row_array($vbphrase['schedule_startdate'], 'startdate', ['year' => $year, 'month' => $month, 'day' => $day], false);
	print_select_row($vbphrase['timezone'], 'startdate_tzoffset', fetch_timezones_array(), $schedule['startdate_tzoffset']);

	[$year, $month, $day] = explode('-', $schedule['enddate']);
	print_time_row_array($vbphrase['schedule_enddate'], 'enddate', ['year' => $year, 'month' => $month, 'day' => $day], false);
	print_select_row($vbphrase['timezone'], 'enddate_tzoffset', fetch_timezones_array(), $schedule['enddate_tzoffset']);

	$phrase = $vbphrase['styleschedule_recurring_yearly'] . '<dfn>' . $vbphrase['schedule_recurring_desc'] . '</dfn>';
	print_yes_no_row($phrase, 'recurring', !$schedule['useyear']);

	$phrase = $vbphrase['schedule_priority'] . '<dfn>' . $vbphrase['schedule_priority_desc'] . '</dfn>';
	print_input_row($phrase, 'priority', $schedule['priority']);

	$phrase = $vbphrase['overridechannelcustom'] . '<dfn>' . $vbphrase['overridechannelcustom_desc']. '</dfn>';
	print_yes_no_row($phrase, 'overridechannelcustom', $schedule['overridechannelcustom']);

	$phrase = $vbphrase['overrideusercustom'] . '<dfn>' . $vbphrase['overrideusercustom_desc']. '</dfn>';
	print_yes_no_row($phrase, 'overrideusercustom', $schedule['overrideusercustom']);

	print_input_row($vbphrase['schedule_title'], 'title', $schedule['title']);

	print_table_default_footer($vbphrase['save']);
}

// ########################################################################
if ($_REQUEST['do'] == 'list')
{
	$schedules = $scheduleApi->getAllSchedules();
	print_stop_message_on_api_error($schedules);
	/** @var vB_Entity_Styleschedule[] */
	$schedules = $schedules['schedules'];

	$stylecache = vB_Library::instance('Style')->fetchStyles(false, false);
	$stylenames = array_column($stylecache, 'title', 'styleid');

	$align = [
		'vbleft',
		'vbcenter',
		// This is mainly to align the "Active" date ranges to the left, as that's a bit easier to parse.
		'vbleft',
		'vbcenter',
		'vbcenter',
	];
	$cells = [];
	$cells[] = $vbphrase['enabled'];
	$cells[] = $vbphrase['style'];
	$cells[] = $vbphrase['active'];
	//$cells[] = $vbphrase['meta'];
	$cells[] = $vbphrase['schedule_priority'];
	$cells[] = $vbphrase['controls'];
	$colspan = count($cells);
	print_form_header2('admincp/styleschedule', 'displayorder');
	print_table_start();
	print_table_header($vbphrase['styleschedules'], $colspan);
	print_cells_row2($cells, 'thead', $align);
	if (!$schedules)
	{
		print_description_row($vbphrase['no_style_schedules'], false, $colspan, '', 'center');
	}
	else
	{
		// display existing sets
		foreach ($schedules AS $__schedule)
		{
			$__scheduleid = $__schedule->scheduleid;
			$cells = [];
			$cells[] = construct_checkbox_control(htmlspecialchars_uni($__schedule->title), 'enabled[' . $__scheduleid . ']', $__schedule->enabled, 1, ['title' => $vbphrase['enabled']]);
			$cells[] = '<span title="styleid ' . $__schedule->styleid . '">' . ($stylenames[$__schedule->styleid] ?? '<Styleid Unknown>') . '</span>'
						. '&nbsp;' . construct_metadata_icons($__schedule, $vbphrase);
			$cells[] = construct_date_range($__schedule, $vbphrase);
			//$cells[] = construct_metadata_icons($__schedule, $vbphrase);
			$cells[] = construct_input('text', 'priority[' . $__scheduleid . ']', ['size' => 3, 'value' => $__schedule->priority]);
			$cells[] = '<div class="normal">' .
				construct_link_code2($vbphrase['edit'], get_admincp_url('styleschedule', ['do' => 'edit', 'scheduleid' => $__scheduleid])) .
				construct_link_code2($vbphrase['delete'], get_admincp_url('styleschedule', ['do' => 'deleteschedule', 'scheduleid' => $__scheduleid])) .
				'</div>';
			print_cells_row2($cells, '', $align);
		}
	}

	$buttons = [
		//construct_submit_button($vbphrase['save_display_order']),
		construct_submit_button($vbphrase['save']),
		construct_link_button($vbphrase['add_styleschedule'] , get_admincp_url('styleschedule', ['do' => 'add'])),
	];

	print_table_button_footer($buttons, $colspan);
}


// ########################################################################

if ($_POST['do'] == 'displayorder')
{
	$vbulletin->input->clean_array_gpc('p', [
		'priority' => vB_Cleaner::TYPE_ARRAY_UINT,
		'enabled' => vB_Cleaner::TYPE_ARRAY_UINT,
	]);

	$schedules = $scheduleApi->getAllSchedules();
	print_stop_message_on_api_error($schedules);
	/** @var vB_Entity_Styleschedule[] */
	$schedules = $schedules['schedules'];

	$priorities = $vbulletin->GPC['priority'];
	$enables = $vbulletin->GPC['enabled'];
	// Because of the nature of checkboxes, !enabled schedules don't have keys in that array.
	$scheduleids = array_keys($priorities);
	$toUpdate = [];
	foreach ($scheduleids AS $__scheduleid)
	{
		$__priority = $priorities[$__scheduleid];
		$__enabled = $enables[$__scheduleid] ?? false;
		if (!isset($schedules[$__scheduleid]))
		{
			// throw error? Shouldn't happen but just being paranoid.
			continue;
		}
		$__schedule = $schedules[$__scheduleid];
		// skip save for ones that didn't change.
		if ($__priority  != $__schedule->priority || $__enabled != $__schedule->enabled)
		{
			$__schedule->priority = $__priority;
			$__schedule->enabled = $__enabled;
			$toUpdate[] = $__schedule;
		}
	}

	if ($toUpdate)
	{
		$result = $scheduleApi->saveSchedulesBulk($toUpdate);
		print_stop_message_on_api_error($result);
	}

	// todo: we're saving enabled & display order.. worth it to add a new phrase to say that??
	print_stop_message2('saved_display_order_successfully', 'styleschedule', ['do'=>'list']);
}


print_cp_footer();

//
// These functions should be considered "private" to this script
//

function construct_date_range(vB_Entity_Styleschedule $schedule, array $vbphrase) : string
{
	$timenow = vB::getRequest()->getTimeNow();
	$useyear = $schedule->useyear;
	[
		'startunixtime' => $startunixtime,
		'endunixtime' => $endunixtime
	] = $schedule->getNearestStartAndEndUnixtimes($timenow);

	// maybe we should use IntlDateFormatter here with $userinfo['lang_locale'], but IntlDateFormatter
	// is not guaranteed to be installed.

	$format = 'Y-m-d \U\T\CP';
	$note = '';
	if (!$useyear)
	{
		// We're using the *calculated* dates above, so let's just show the year. It's easier to read/parse for
		// human eyes if we just show the same YYYY-MM-DD format everywhere.
		// Perhaps we should pre-calculate all of the schedules and cache all of them somewhere...
		//$format = 'm-d';
		$note = '<i class="fa-solid fa-repeat h-cursor" title="' .$vbphrase['styleschedule_recurring_yearly'] . '"></i>';
	}

	$isActive = ($startunixtime <= $timenow AND $timenow <= $endunixtime);
	if ($isActive)
	{
		// Date ranges are just hard to parse for me, ok.
		// Adding an icon to note which is actually active *right now* at a glance.
		$note .= '<i class="fa-solid fa-calendar-check h-cursor" title="' .$vbphrase['active'] . '"></i>';
	}
	else
	{
		// If we want to show the inactive icon too, use fa-regular for either this one or the above one to
		// help visually distinguish it a little easier, as the icons are a bit small in adminCP ATM.
		//$note .= '<i class="fa-regular fa-calendar-xmark h-cursor" title="' .$vbphrase['inactive'] . '"></i>';
	}

	$start = convertUnixtimeToFormat($startunixtime, $schedule->vbstartdate->tzoffset, $format);
	$end = convertUnixtimeToFormat($endunixtime, $schedule->vbenddate->tzoffset, $format);

	return "$start - $end {$note}";
}

function convertUnixtimeToFormat($timestamp, $tzoffset, $format)
{
	$tz = new DateTimeZone($tzoffset);
	//return (new DateTimeImmutable('@' . $timestamp, $tz))->format($format);
	//"The $timezone parameter and the current timezone are ignored when the $datetime parameter either is a UNIX timestamp (e.g. @946684800)
	// or specifies a timezone (e.g. 2010-01-28T15:00:00+02:00)."
	// Not sure why it ignores the timezone parameter for UNIX time, but we have to do it roundabout.
	$dt =new DateTime('@' . $timestamp, $tz);
	$dt = $dt->setTimezone($tz);
	return $dt->format($format);
}

function construct_metadata_icons(vB_Entity_Styleschedule $schedule, array $vbphrase) : string
{
	$extra = '';
	if ($schedule->overridechannelcustom)
	{
		$extra .= '<i class="fa-solid fa-sitemap h-cursor" title="' . $vbphrase['overridechannelcustom'] . '"></i>';
	}
	if ($schedule->overrideusercustom)
	{
		$extra .= '<i class="fa-solid fa-user h-cursor" title="' . $vbphrase['overrideusercustom'] . '"></i>';
	}
	return $extra;

}

function removeTimeFromDatetime(string $datetime): string
{
	return preg_replace('#^(\d{4}-\d{2}-\d{2}).*$#','$1', $datetime, 1);
}

function arrayToMysqlTimeString(array $arr) : string
{
	return str_pad($arr['year'], 4, '0', STR_PAD_LEFT) . '-' . str_pad($arr['month'], 2, '0', STR_PAD_LEFT) . '-' . str_pad($arr['day'], 2, '0', STR_PAD_LEFT);
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 110630 $
|| #######################################################################
\*=========================================================================*/
