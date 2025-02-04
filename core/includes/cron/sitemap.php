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
error_reporting(error_reporting() & ~E_NOTICE);

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

require_once (DIR . '/includes/class_sitemap.php');
$runner = new vB_SiteMapRunner_Cron(vB::get_registry());
$runner->set_cron_item($nextitem);

$status = $runner->check_environment();
if ($status['error'])
{
	// if an error has happened, display/log it if necessary and die

	if (VB_AREA == 'AdminCP')
	{
		print_stop_message($status['error']);
	}
	else if ($status['loggable'])
	{
		$rows = vB::getDbAssertor()->getRow('adminmessage', array(
			vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_COUNT,
			'varname' => $status['error'],
			'status' => 'undone'
		));

		if ($rows['count'] == 0)
		{
			vB::getDbAssertor()->insert('adminmessage', array(
				'varname' => $status['error'],
				'dismissable' => 1,
				'script' => 'sitemap.php',
				'action' => 'buildsitemap',
				'execurl' => 'sitemap.php?do=buildsitemap',
				'method' => 'get',
				'dateline' => vB::getRequest()->getTimeNow(),
				'status' => 'undone'
			));
		}
	}

	exit;
}

$log_text = array();
while (!$runner->is_finished)
{
	$runner->generate();
	$log_text[] = $runner->written_filename;

	if (defined('IN_CONTROL_PANEL'))
	{
		echo "<p>$runner->written_filename</p>";
	}
}

// all done, sitemap index should have been generated at the end.
$log_text[] = 'vbulletin_sitemap_index.xml';
if (defined('IN_CONTROL_PANEL'))
{
	echo "<p>vbulletin_sitemap_index.xml</p>";
}
$log_text = implode(", ", $log_text);

log_cron_action($log_text, $nextitem, 1);

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 115374 $
|| #######################################################################
\*=========================================================================*/
