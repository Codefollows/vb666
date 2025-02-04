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
define('CVS_REVISION', '$RCSfile$ - $Revision: 111013 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
global $phrasegroups, $specialtemplates, $vbphrase, $vbulletin;
$phrasegroups = ['stats', 'logging'];
$specialtemplates = ['userstats', 'maxloggedin'];

// ########################## REQUIRE BACK-END ############################
require_once(dirname(__FILE__) . '/global.php');

// ############################# LOG ACTION ###############################
log_admin_action();

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

print_cp_header($vbphrase['api_stats']);

if (empty($_REQUEST['do']) OR $_REQUEST['do'] == 'index' OR $_REQUEST['do'] == 'top' OR $_REQUEST['do'] == 'method' OR $_REQUEST['do'] == 'client')
{
	if (!$vbulletin->options['enableapilog'])
	{
		print_warning_table($vbphrase['apilog_disabled_options']);
	}

	print_form_header('admincp/stats', 'index');
	print_table_header($vbphrase['api_stats']);
	print_label_row(construct_link_code($vbphrase['top_statistics'], 'apistats.php?do=top'), '');
	print_label_row(construct_link_code($vbphrase['top_apimethods'], 'apistats.php?do=method'), '');
	print_label_row(construct_link_code($vbphrase['top_apiclients'], 'apistats.php?do=client'), '');
	print_label_row(construct_link_code($vbphrase['apiclient_activity_statistics'], 'apistats.php?do=activity'), '');
	print_table_footer();
}

// Find most popular things below
if ($_REQUEST['do'] == 'top')
{
	// Top Client
	$maxclient = vB::getDbAssertor()->getRow('api_fetchmaxclient');

	// Top API Method
	$maxmethod = vB::getDbAssertor()->getRow('api_fetchmaxmethod');

	print_form_header('admincp/');
	print_table_header($vbphrase['top']);

	$linktext =	construct_link_code(
		vB_String::htmlSpecialCharsUni($maxclient['clientname']) . " ($vbphrase[id]: " . $maxclient['apiclientid'] . ')',
		"apilog.php?do=viewclient&apiclientid=" . $maxclient['apiclientid']
	);

	print_label_row($vbphrase['top_apiclient'], $linktext . " (" . construct_phrase($vbphrase['api_x_calls'], $maxclient['c']) . ")");
	print_label_row($vbphrase['top_apimethod'], vB_String::htmlSpecialCharsUni($maxmethod['method']) .
		" (" . construct_phrase($vbphrase['api_x_calls'], $maxclient['c']) . ")");
	print_table_footer();
}

if ($_REQUEST['do'] == 'method')
{
	$vbulletin->input->clean_array_gpc('r', [
		'pagenumber'	=> vB_Cleaner::TYPE_INT,
		'perpage'	=> vB_Cleaner::TYPE_INT,
	]);

	if ($vbulletin->GPC['perpage'] < 1)
	{
		$vbulletin->GPC['perpage'] = 15;
	}
	if ($vbulletin->GPC['pagenumber'] < 1)
	{
		$vbulletin->GPC['pagenumber'] = 1;
	}
	$startat = ($vbulletin->GPC['pagenumber'] - 1) * $vbulletin->GPC['perpage'];

	$counter = vB::getDbAssertor()->getRow('api_methodcount');
	$totalpages = ceil($counter['total'] / $vbulletin->GPC['perpage']);

	$logs = vB::getDbAssertor()->getRows('api_methodlogs', [
		'startat' => $startat,
		'limit' => $vbulletin->GPC['perpage'],
	]);

	if (count($logs) > 0)
	{
		$baseUrl = 'admincp/apistats.php?';
		$query = [
			'do' => 'method',
			'pp' => $vbulletin->GPC['perpage'],
			'page' => $vbulletin->GPC['pagenumber']
		];

		print_form_header('admincp/apilog', 'remove');
		print_table_header(construct_phrase($vbphrase['top_api_methods_viewer_page_x_y_there_are_z_total_methods'], vb_number_format($vbulletin->GPC['pagenumber']), vb_number_format($totalpages), vb_number_format($counter['total'])), 8);

		$headings = [];
		$headings[] = $vbphrase['apimethod'];
		$headings[] = $vbphrase['apicalls'];
		print_cells_row($headings, 1);

		foreach ($logs as $log)
		{
			$cell = [];
			$cell[] = htmlspecialchars_uni($log['method']);
			$cell[] = $log['c'];
			print_cells_row($cell);
		}

		$paging = get_log_paging_html($vbulletin->GPC['pagenumber'], $totalpages, $baseUrl, $query, $vbphrase);
		print_table_footer(2, $paging);
	}
	else
	{
		print_stop_message2('no_log_entries_matched_your_query');
	}
}

if ($_REQUEST['do'] == 'client')
{
	$vbulletin->input->clean_array_gpc('r', [
		'pagenumber'	=> vB_Cleaner::TYPE_INT,
		'perpage'	=> vB_Cleaner::TYPE_INT,
	]);

	if ($vbulletin->GPC['perpage'] < 1)
	{
		$vbulletin->GPC['perpage'] = 15;
	}
	if ($vbulletin->GPC['pagenumber'] < 1)
	{
		$vbulletin->GPC['pagenumber'] = 1;
	}
	$startat = ($vbulletin->GPC['pagenumber'] - 1) * $vbulletin->GPC['perpage'];

	$counter = vB::getDbAssertor()->getRow('api_clientcount');
	$totalpages = ceil($counter['total'] / $vbulletin->GPC['perpage']);

	$logs = vB::getDbAssertor()->getRows('api_clientlogs', array(
		'startat' => $startat,
		'limit' => $vbulletin->GPC['perpage'],
	));

	if (count($logs) > 0)
	{
		$baseUrl = 'admincp/apistats.php?';
		$query = array(
			'do' => 'client',
			'pp' => $vbulletin->GPC['perpage'],
			'page' => $vbulletin->GPC['pagenumber'],
		);

		print_form_header('admincp/apilog', 'remove');
		print_table_header(construct_phrase($vbphrase['top_api_clients_viewer_page_x_y_there_are_z_total_clients'], vb_number_format($vbulletin->GPC['pagenumber']), vb_number_format($totalpages), vb_number_format($counter['total'])), 8);

		$headings = array();
		$headings[] = $vbphrase['apiclientid'];
		$headings[] = $vbphrase['apiclientname'];
		$headings[] = $vbphrase['username'];
		$headings[] = $vbphrase['apicalls'];
		$headings[] = $vbphrase['controls'];
		print_cells_row($headings, 1);

		foreach ($logs as $log)
		{
			$cell = array();
			$cell[] = "<a href=\"admincp/apilog.php?do=viewclient&amp;apiclientid=$log[apiclientid]\"><b>$log[apiclientid]</b></a>";
			$cell[] = "<a href=\"admincp/apilog.php?do=viewclient&amp;apiclientid=$log[apiclientid]\"><b>" . htmlspecialchars_uni($log['clientname']) . "</b></a>";
			$cell[] = iif(!empty($log['username']), "<a href=\"admincp/user.php?do=edit&amp;u=$log[userid]\"><b>$log[username]</b></a>", $vbphrase['guest']);
			$cell[] = $log['c'];
			$cell[] = "<input type=\"button\" class=\"button\" value=\"$vbphrase[view_logs]\" onclick=\"vBRedirect('admincp/apilog.php?do=view&amp;apiclientid=$log[apiclientid]');\" />";
			print_cells_row($cell);
		}

		$paging = get_log_paging_html($vbulletin->GPC['pagenumber'], $totalpages, $baseUrl, $query, $vbphrase);
		print_table_footer(5, $paging);
	}
	else
	{
		print_stop_message2('no_log_entries_matched_your_query');
	}
}

if ($_REQUEST['do'] == 'activity')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'start'     => vB_Cleaner::TYPE_ARRAY_INT,
		'end'       => vB_Cleaner::TYPE_ARRAY_INT,
		'scope'     => vB_Cleaner::TYPE_STR,
		'sort'      => vB_Cleaner::TYPE_STR,
		'nullvalue' => vB_Cleaner::TYPE_BOOL,
	));

	// Default View Values
	if (empty($vbulletin->GPC['start']))
	{
		$vbulletin->GPC['start'] = TIMENOW - 3600 * 24 * 30;
	}

	if (empty($vbulletin->GPC['end']))
	{
		$vbulletin->GPC['end'] = TIMENOW;
	}

	print_statistic_code($vbphrase['apiclient_activity_statistics'], 'activity', $vbulletin->GPC['start'], $vbulletin->GPC['end'], $vbulletin->GPC['nullvalue'], $vbulletin->GPC['scope'], $vbulletin->GPC['sort'], 'apistats');

	if (!empty($vbulletin->GPC['scope']))
	{ // we have a submitted form
		$start_time = intval(mktime(0, 0, 0, $vbulletin->GPC['start']['month'], $vbulletin->GPC['start']['day'], $vbulletin->GPC['start']['year']));
		$end_time = intval(mktime(0, 0, 0, $vbulletin->GPC['end']['month'], $vbulletin->GPC['end']['day'], $vbulletin->GPC['end']['year']));
		if ($start_time >= $end_time)
		{
			print_stop_message2('start_date_after_end_gerror');
		}

		switch ($vbulletin->GPC['scope'])
		{
			case 'weekly':
				$phpformat = '# (! Y)';
				break;
			case 'monthly':
				$phpformat = '! Y';
				break;
			default:
				$phpformat = '! d, Y';
				break;
		}

		$statistics = vB::getDbAssertor()->getRows('fetchApiActivity', array(
			'start_time' => $start_time,
			'end_time' => $end_time,
			'sort' => $vbulletin->GPC['sort'],
			'scope' => $vbulletin->GPC['scope'],
			'nullvalue' => $vbulletin->GPC['nullvalue'],
		));

		$results = [];
		$dates = [];
		foreach ($statistics AS $stats)
		{
			// we will now have each days total of the type picked and we can sort through it
			$month = strtolower(date('F', $stats['dateline']));
			$dates[] = str_replace(' ', '&nbsp;',
				str_replace('#', $vbphrase['week'] . '&nbsp;' . strftime('%U', $stats['dateline']),
					str_replace('!', $vbphrase["$month"], date($phpformat, $stats['dateline']))));
			$results[] = $stats['total'];
		}

		if (!sizeof($results))
		{
			print_stop_message2('no_matches_found_gerror');
		}

		// we'll need a poll image
		$style = vB::getDbAssertor()->getRow('style', array('styleid' => $vbulletin->options['styleid']));
		$vbulletin->stylevars = unserialize($style['newstylevars']);
		fetch_stylevars($style, $vbulletin->userinfo);

		print_form_header('admincp/');
		print_table_header($vbphrase['results'], 3);
		print_cells_row(array($vbphrase['date'], '&nbsp;', $vbphrase['total']), 1);
		$maxvalue = max($results);
		$i = 0;
		foreach ($results AS $key => $value)
		{
			$i++;
			$bar = ($i % 6) + 1;
			if ($maxvalue == 0)
			{
				$percentage = 100;
			}
			else
			{
				$percentage = ceil(($value/$maxvalue) * 100);
			}
			print_statistic_result($dates[$key], $bar, $value, $percentage);
		}
		print_table_footer(3);
	}
}

print_cp_footer();

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 111013 $
|| #######################################################################
\*=========================================================================*/
