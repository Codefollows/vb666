<?php if (!defined('VB_ENTRY')) die('Access denied.');
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
// if (!is_object($vbulletin->db))
// {
// 	exit;
// }

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################
$datastore  = vB::getDatastore();
$miscoptions = $datastore->getValue('miscoptions');
$timenow = vB::getRequest()->getTimeNow();
// Seems like what $timenow - ($nextrun - $timenow) is supposed to be is "get the time difference between now and nextrun, and assume
// that we ran that same diff ago".
// $nextrun is not set when running a specific cron in admincp (runById()). We may want to calculate it based on $nextitem but genearlly,
// promotions_lastrun should be available. For now let's add a fallback that'll be similar to old behavior (undefined -> zero).
$lastrun = !empty($miscoptions['promotions_lastrun']) ? $miscoptions['promotions_lastrun'] : $timenow - (($nextrun ?? 0) - $timenow);

/** @var vB_Library_Usergroup */
$lib = vB_Library::instance('usergroup');
[
	'itemsToLog' => $itemsToLog,
] = $lib->promoteUsers($lastrun);

$assertor = vB::getDbAssertor();
$usergroupTitles = $assertor->getColumn('usergroup', 'title', [], false, 'usergroupid');
// Log primary usergroup changes notated by * in the log
if (!empty($itemsToLog['primaryupdates']))
{
	foreach ($itemsToLog['primaryupdates'] AS $__joinusergroupid => $__userids)
	{
		$__usernames = $assertor->getColumn('user', 'username', ['userid' => $__userids]);
		if (!empty($__usernames))
		{
			$__usernames = implode(', ', $__usernames);
			$__log = [
				$usergroupTitles[$__joinusergroupid],
				'*',
				$__usernames,
			];
			// the "1" indicates to use the second line of the phrase specified for this task
			log_cron_action(serialize($__log), $nextitem, 1);
		}
	}
}
// membergroup changes notated by %
if (!empty($itemsToLog['secondaryupdates']))
{
	foreach ($itemsToLog['secondaryupdates'] AS $__joinusergroupid => $__userids)
	{
		$__usernames = $assertor->getColumn('user', 'username', ['userid' => $__userids]);
		if (!empty($__usernames))
		{
			$__usernames = implode(', ', $__usernames);
			$__log = [
				$usergroupTitles[$__joinusergroupid],
				'%',
				$__usernames,
			];
			// the "1" indicates to use the second line of the phrase specified for this task
			log_cron_action(serialize($__log), $nextitem, 1);
		}
	}
}

$miscoptions['promotions_lastrun'] = $timenow;
$datastore->build('miscoptions', serialize($miscoptions), 1);


/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 116345 $
|| ####################################################################
\*======================================================================*/
?>
