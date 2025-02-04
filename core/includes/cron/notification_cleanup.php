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

// the time stamps use getTimeNow() with uses PHP's time() which is in seconds. The settings are in days
$days_to_seconds = 86400;
$assertor = vB::getDbAssertor();
$timenow = vB::getRequest()->getTimeNow();

// Note, the dismissed delete & new delete are completely separate & do not affect each other, per their setting descriptions.
// VBV-14180

// 0 means disabled.
$dismissed_ttl = ((int) vB::getDatastore()->getOption('dismissed_notification_ttl_days')) * $days_to_seconds;
$dismissed_cutoff = $timenow - $dismissed_ttl;
if (!empty($dismissed_ttl) AND $dismissed_cutoff > 0)
{
	$assertor->assertQuery('vBForum:deleteNotifications_dismissed', ['cutoff' => $dismissed_cutoff] );
}

// 0 means disabled.
$new_ttl = ((int) vB::getDatastore()->getOption('new_notification_ttl_days')) * $days_to_seconds;
$new_cutoff = $timenow - $new_ttl;
if (!empty($new_ttl) AND $new_cutoff > 0)
{
	$assertor->assertQuery('vBForum:deleteNotifications_new', ['cutoff' => $new_cutoff] );
}


log_cron_action('', $nextitem, 1);
/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 114953 $
|| #######################################################################
\*=========================================================================*/
