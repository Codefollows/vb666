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

// ##################### DEFINE IMPORTANT CONSTANTS #######################
define('ONEDAY', 86400);
define('TWODAYS', 172800);
define('FIVEDAYS', 432000);
define('SIXDAYS', 518400);

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

// Send the reminder email only twice. After 1 day and then 5 Days.


$library = vB_Library::instance('user');

$timenow = vB::getRequest()->getTimeNow();
$bf_misc_useroptions = vB::getDatastore()->getValue('bf_misc_useroptions');
$users = vB::getDbAssertor()->assertQuery('fetchUsersToActivate', [
	'time1' => $timenow - TWODAYS,
	'time2' => $timenow - ONEDAY,
	'time3' => $timenow - SIXDAYS,
	'time4' => $timenow - FIVEDAYS,
	'noactivationmails' => $bf_misc_useroptions['noactivationmails']
]);

vB_Mail::vbmailStart();
$emails = [];

foreach ($users AS $user)
{
	try
	{
		//this reloads the user when we could more efficiently fetch it as part of the query but the overhead
		//isn't that much in this context and we don't want to repeat all of the logic here.
		//
		//not sure why we don't check coppa/moderation here but the old logic didn't so declining to change it.
		$library->sendActivateEmail($user['userid'], false, false);
	}
	catch (Exception $e)
	{
		//should probably log but don't want a issue with one user killing the script.
	}

	$emails[] = $user['username'];
}

if ($emails)
{
	log_cron_action(implode(', ', $emails), $nextitem, 1);
}

vB_Mail::vbmailEnd();

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 115033 $
|| #######################################################################
\*=========================================================================*/
