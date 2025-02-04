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
define('CVS_REVISION', '$RCSfile$ - $Revision: 112705 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
global $phrasegroups, $specialtemplates, $vbphrase, $vbulletin;
$phrasegroups = ['logging'];
$specialtemplates = [];

// ########################## REQUIRE BACK-END ############################
require_once(dirname(__FILE__) . '/global.php');

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

// ###################### Start listLogFiles #######################
// function to return an array of log files in the filesystem
function fetch_log_file_array($type = 'database')
{
	global $vbulletin, $vbphrase;

	$filelist = [];
	$filebase = '';
	switch($type)
	{
		case 'database':
			$vb5_config = vB::getConfig();
			$filebase = trim($vb5_config['Database']['logfile']);
			$title = $vbphrase['vbulletin_database_errors'];
			break;

		case 'security':
			$filebase = trim($vbulletin->options['errorlogsecurity']);
			$title = $vbphrase['admin_control_panel_failed_logins'];
			break;

		default:
			return $filelist;
	}

	if ($filebase)
	{
		$slashpos = strrpos($filebase, DIRECTORY_SEPARATOR);
		if ($slashpos === false)
		{
			$basedir = '.';
		}
		else
		{
			$basedir = substr($filebase, 0, $slashpos);
		}
		if ($handle = @opendir($basedir))
		{
			$filebase = substr($filebase, $slashpos + 1);
			$namelength = strlen($filebase);
			while ($file = readdir($handle))
			{
				if (strpos($file, $filebase) === 0)
				{
					if ($unixdate = intval(substr($file, $namelength, -4)))
					{
						$date = vbdate($vbulletin->options['dateformat'] . ' ' . $vbulletin->options['timeformat'], $unixdate);
					}
					else
					{
						$date = '(Current Version)';
					}
					$key = $type . '_' . $unixdate;
					$filelist[$key] = "$title $date";
				}
			}
			@closedir($handle);
			return $filelist;
		}
		else
		{
			echo '<p>' . $vbphrase['invalid_directory_specified'] . '</p>';
		}
	}
	else
	{
		return false;
	}
}

// #############################################################################

$vb5_config =& vB::getConfig();
$assertor = vB::getDbAssertor();
if ($_POST['do'] == 'viewlogfile' AND can_access_logs($vb5_config['SpecialUsers']['canviewadminlog'], 1, '<p>' . $vbphrase['control_panel_log_viewing_restricted'] .'</p>'))
{
	$vbulletin->input->clean_array_gpc('p', array(
		'filename'	=> vB_Cleaner::TYPE_STR,
		'delete'	=> vB_Cleaner::TYPE_STR
	));

	$filebits = explode('_', $vbulletin->GPC['filename']);
	$type = trim($filebits[0]);
	$date = intval($filebits[1]);

	switch($type)
	{
		case 'database':
		case 'security':
		{
			if ($vbulletin->GPC['filename'] = trim($vbulletin->options["errorlog$type"]))
			{
				$vbulletin->GPC['filename'] = $vbulletin->GPC['filename'] . iif($date, $date) . '.log';
				if (file_exists($vbulletin->GPC['filename']))
				{
					if ($vbulletin->GPC['delete'])
					{
						if (can_access_logs($vb5_config['SpecialUsers']['canpruneadminlog'], 0, '<p>' . $vbphrase['log_file_deletion_restricted'] . '</p>'))
						{
							if (@unlink($vbulletin->GPC['filename']))
							{
								print_stop_message2('deleted_file_successfully');
							}
							else
							{
								print_stop_message2('unable_to_delete_file');
							}
						}
					}
					else
					{
						require_once(DIR . '/includes/functions_file.php');
						file_download(implode('', file($vbulletin->GPC['filename'])), substr($vbulletin->GPC['filename'], strrpos($vbulletin->GPC['filename'], '/') + 1), 'baa');
					}
				}
				else
				{
					print_stop_message2('invalid_file_specified');
				}
			}
		}
	}

	$_REQUEST['do'] = 'logfiles';

}

// #############################################################################
print_cp_header($vbphrase['control_panel_log']);
// #############################################################################

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'choose';
}

// ###################### Start view db error log #######################
if ($_REQUEST['do'] == 'logfiles' AND can_access_logs($vb5_config['SpecialUsers']['canviewadminlog'], 1, '<p>' . $vbphrase['control_panel_log_viewing_restricted'] . '</p>'))
{
	// get database and security log files list
	$dblogs = fetch_log_file_array('database');
	$cplogs = fetch_log_file_array('security');

	if ($dblogs === false AND $cplogs === false)
	{
		print_stop_message2('no_log_file_defined_in_vbulletin_options');
	}

	if ($dblogs)
	{
		$dblogs = '<optgroup label="vBulletin Database Errors">' . construct_select_options($dblogs) . '</optgroup>';
	}
	else
	{
		// avoid implicit array to string conversion below.
		$dblogs = '';
	}

	if ($cplogs)
	{
		$cplogs = '<optgroup label="Admin Control Panel Failed Logins">' . construct_select_options($cplogs) . '</optgroup>';
	}
	else
	{
		$cplogs = '';
	}

	print_form_header('admincp/adminlog', 'viewlogfile');
	print_table_header($vbphrase['view_logs']);
	print_label_row($vbphrase['logs'], '<select name="filename" size="15" tabindex="1" class="bginput">' . $dblogs . $cplogs  . '<option value="">' . str_repeat('&nbsp; ', 40) . '</option></select>');
	print_table_footer(2, '<input type="submit" class="button" value=" ' . $vbphrase['view'] . ' " accesskey="s" tabindex="1" /> <input type="submit" class="button" value=" ' . $vbphrase['delete'] . ' " name="delete" tabindex="1" />');
}

// ###################### Start view #######################
if ($_REQUEST['do'] == 'view' AND can_access_logs($vb5_config['SpecialUsers']['canviewadminlog'], 1, '<p>' . $vbphrase['control_panel_log_viewing_restricted'] . '</p>'))
{
	$vbulletin->input->clean_array_gpc('r', array(
		'userid'	=> vB_Cleaner::TYPE_UINT,
		'script'	=> vB_Cleaner::TYPE_NOHTML,
		'perpage'   => vB_Cleaner::TYPE_INT,
		'pagenumber'=> vB_Cleaner::TYPE_INT,
		'orderby'   => vB_Cleaner::TYPE_STR,
		'startdate' => vB_Cleaner::TYPE_UNIXTIME,
		'enddate'   => vB_Cleaner::TYPE_UNIXTIME
	));

	if ($vbulletin->GPC['perpage'] < 1)
	{
		$vbulletin->GPC['perpage'] = 15;
	}
	if ($vbulletin->GPC['pagenumber'] < 1)
	{
		$vbulletin->GPC['pagenumber'] = 1;
	}
	$startat = ($vbulletin->GPC['pagenumber'] - 1) * $vbulletin->GPC['perpage'];

	$counter = $assertor->getRow('vBForum:fetchAdminLogCount',
		array('userid' => $vbulletin->GPC['userid'],
			'script' => $vbulletin->GPC['script'],
			'startdate' => $vbulletin->GPC['startdate'],
			'enddate' => $vbulletin->GPC['enddate']
		)
	);
	$totalpages = ceil($counter['total'] / $vbulletin->GPC['perpage']);

	$logs = $assertor->assertQuery('vBForum:fetchAdminLog',
		array('userid' => $vbulletin->GPC['userid'],
			'script' => $vbulletin->GPC['script'],
			'startdate' => $vbulletin->GPC['startdate'],
			'enddate' => $vbulletin->GPC['enddate'],
			'orderby' => $vbulletin->GPC['orderby'],
			vB_dB_Query::PARAM_LIMITSTART => $startat,
			vB_dB_Query::PARAM_LIMIT => $vbulletin->GPC['perpage'],
		)
	);

	if ($logs AND $logs->valid())
	{
		$baseUrl = 'admincp/adminlog.php?';
		//this get reused a number of places.  If you change any parameters
		//you need to make sure that they get reset downstream
		$query = array (
			'do' => 'view',
			'script' => $vbulletin->GPC['script'],
			'u' => $vbulletin->GPC['userid'],
			'pp' => $vbulletin->GPC['perpage'],
			'orderby' => $vbulletin->GPC['orderby'],
			'page' => $vbulletin->GPC['pagenumber'],
			'startdate' => $vbulletin->GPC['startdate'],
			'enddate' => $vbulletin->GPC['enddate'],
		);

		print_form_header('admincp/adminlog', 'remove');
		print_description_row(construct_link_code($vbphrase['restart'], 'adminlog.php?'), 0, 7, 'thead', vB_Template_Runtime::fetchStyleVar('right'));
		print_table_header(construct_phrase($vbphrase['control_panel_log_viewer_page_x_y_there_are_z_total_log_entries'],
			vb_number_format($vbulletin->GPC['pagenumber']), vb_number_format($totalpages), vb_number_format($counter['total'])), 7);

		$headings = array();
		$headings[] = $vbphrase['id'];

		$query['orderby'] = 'user';
		$url = htmlspecialchars($baseUrl . http_build_query($query));
		$headings[] = "<a href='$url' title='" . $vbphrase['order_by_username'] . "'>" . $vbphrase['username'] . "</a>";

		$query['orderby'] = 'date';
		$url = htmlspecialchars($baseUrl . http_build_query($query));
		$headings[] = "<a href='$url' title='" . $vbphrase['order_by_date'] . "'>" . $vbphrase['date'] . "</a>";

		$query['orderby'] = 'script';
		$url = htmlspecialchars($baseUrl . http_build_query($query));
		$headings[] = "<a href='$url' title='" . $vbphrase['order_by_script'] . "'>" . $vbphrase['script'] . "</a>";

		$headings[] = $vbphrase['action'];
		$headings[] = $vbphrase['info'];
		$headings[] = $vbphrase['ip_address'];
		print_cells_row($headings, 1);

		foreach ($logs AS $log)
		{
			$cell = array();
			$cell[] = $log['adminlogid'];
			$cell[] = (!empty($log['username']) ? "<a href=\"admincp/user.php?do=edit&u=$log[userid]\"><b>$log[username]</b></a>" : $vbphrase['n_a']);
			$cell[] = '<span class="smallfont">' . vbdate($vbulletin->options['logdateformat'], $log['dateline']) . '</span>';
			$cell[] = htmlspecialchars_uni($log['script']);
			$cell[] = htmlspecialchars_uni($log['action']);
			$cell[] = htmlspecialchars_uni($log['extrainfo']);
			$cell[] = '<span class="smallfont">' . ($log['ipaddress'] ? "<a href=\"admincp/usertools.php?do=gethost&ip=$log[ipaddress]\">$log[ipaddress]</a>" : '&nbsp;') . '</span>';
			print_cells_row($cell);
		}

		$paging = get_log_paging_html($vbulletin->GPC['pagenumber'], $totalpages, $baseUrl, $query, $vbphrase);
		print_table_footer(7, $paging);
	}
	else
	{
		print_stop_message2('no_log_entries_matched_your_query');
	}
}

// ###################### Start prune log #######################
if ($_REQUEST['do'] == 'prunelog' AND can_access_logs($vb5_config['SpecialUsers']['canpruneadminlog'], 0, '<p>' . $vbphrase['control_panel_log_pruning_permission_restricted'] . '</p>'))
{
	$vbulletin->input->clean_array_gpc('r', array(
		'userid'	=> vB_Cleaner::TYPE_INT,
		'script'	=> vB_Cleaner::TYPE_STR,
		'daysprune'	=> vB_Cleaner::TYPE_INT
	));

	$datecut = TIMENOW - (86400 * $vbulletin->GPC['daysprune']);

	$logs = $assertor->getRow('vBForum:countAdminLogByDateCut',
		array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_METHOD,
			'datecut' => $datecut,
			'userid' => $vbulletin->GPC['userid'],
			'script' => $vbulletin->GPC['script']
		)
	);

	if ($logs['total'])
	{
		print_form_header('admincp/adminlog', 'doprunelog');
		construct_hidden_code('datecut', $datecut);
		construct_hidden_code('script', $vbulletin->GPC['script']);
		construct_hidden_code('userid', $vbulletin->GPC['userid']);
		print_table_header($vbphrase['prune_control_panel_log']);
		print_description_row(construct_phrase($vbphrase['are_you_sure_you_want_to_prune_x_log_entries_from_control_panel_log'], vb_number_format($logs['total'])));
		print_submit_row($vbphrase['yes'], 0, 0, $vbphrase['no']);
	}
	else
	{
		print_stop_message2('no_log_entries_matched_your_query');
	}
}

// ###################### Start do prune log #######################
if ($_POST['do'] == 'doprunelog' AND can_access_logs($vb5_config['SpecialUsers']['canpruneadminlog'], 0, '<p>' . $vbphrase['control_panel_log_pruning_permission_restricted'] . '</p>'))
{
	$vbulletin->input->clean_array_gpc('p', array(
		'userid'	=> vB_Cleaner::TYPE_INT,
		'script'	=> vB_Cleaner::TYPE_STR,
		'datecut'	=> vB_Cleaner::TYPE_INT
	));

	$assertor->assertQuery('vBForum:deleteAdminLogByDateCut',
		array('datecut' =>  $vbulletin->GPC['datecut'],
			'userid' => $vbulletin->GPC['userid'],
			'script' => $vbulletin->GPC['script']
		)
	);

	print_stop_message2('pruned_control_panel_log_successfully', 'adminlog', array('do'=>'choose'));
}

// ###################### Start modify #######################
if ($_REQUEST['do'] == 'choose')
{
	if (can_access_logs($vb5_config['SpecialUsers']['canviewadminlog'], 1))
	{
		log_admin_action();

		$files = $assertor->assertQuery('vBForum:fetchDistinctScript', []);
		$filelist = ['no_value' => $vbphrase['all_scripts_glogging']];
		if ($files AND $files->valid())
		{
			foreach($files AS $file)
			{
				$file['script'] = htmlspecialchars_uni($file['script']);
				$filelist["$file[script]"] = $file['script'];
			}
		}

		$users = $assertor->assertQuery('vBForum:fetchDistinctUsers', []);
		$userlist = ['no_value' => $vbphrase['all_users']];
		if ($users AND $users->valid())
		{
			foreach($users AS $user)
			{
				$userlist["$user[userid]"] = $user['username'];
			}
		}

		$perpage_options = array(
			5 => 5,
			10 => 10,
			15 => 15,
			20 => 20,
			25 => 25,
			30 => 30,
			40 => 40,
			50 => 50,
			100 => 100,
		);

		print_form_header('admincp/adminlog', 'view');
		print_table_header($vbphrase['control_panel_log_viewer']);
		print_select_row($vbphrase['log_entries_to_show_per_page'], 'perpage', $perpage_options, 15);
		print_select_row($vbphrase['show_only_entries_relating_to_script'], 'script', $filelist);

		print_select_row($vbphrase['show_only_entries_generated_by'], 'userid', $userlist);

		print_time_row($vbphrase['start_date'], 'startdate', 0, 0);
		print_time_row($vbphrase['end_date'], 'enddate', 0, 0);

		print_select_row($vbphrase['order_by_gcpglobal'], 'orderby', ['date' => $vbphrase['date'], 'user' => $vbphrase['user'], 'script' => $vbphrase['script']], 'date');
		print_submit_row($vbphrase['view'], 0);

		if (can_access_logs($vb5_config['SpecialUsers']['canpruneadminlog'], 1))
		{
			print_form_header('admincp/adminlog', 'prunelog');
			print_table_header($vbphrase['prune_control_panel_log']);
			print_label_row($vbphrase['remove_entries_relating_to_script'], '<select name="script" tabindex="1" class="bginput">' . construct_select_options($filelist) . '</select>', '', 'top', 'pscript');
			print_label_row($vbphrase['remove_entries_logged_by_user'], '<select name="userid" tabindex="1" class="bginput">' . construct_select_options($userlist) . '</select>', '', 'top', 'puserid');
			print_input_row($vbphrase['remove_entries_older_than_days'], 'daysprune', 30);
			print_submit_row($vbphrase['prune_control_panel_log'], 0);
		}
		else
		{
			echo '<p>' . $vbphrase['control_panel_log_pruning_permission_restricted'] . '</p>';
		}
	}
	else
	{
		echo '<p>' . $vbphrase['control_panel_log_viewing_restricted'] . '</p>';
	}
}

//this could probably be handled more gracefully.
$data = [
	'page' => 'adminlog',
	'pageaction' => 'restrict',
	'option' => '',
];

echo '<p class="smallfont" align="center">' . construct_event_link($vbphrase['want_to_access_grant_access_to_this_script'], 'js-helplink', $data) . '</p>';

print_cp_footer();

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 112705 $
|| #######################################################################
\*=========================================================================*/
