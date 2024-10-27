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

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################
$timenow = vB::getRequest()->getTimeNow();

$assertor = vB::getDbAssertor();
$datastore = vB::getDatastore();
$sessioncutoff = $timenow - $datastore->getOption('cookietimeout');
$assertor->delete('session', [['field'=>'lastactivity', 'value' => $sessioncutoff, vB_dB_Query::OPERATOR_KEY => vB_dB_Query::OPERATOR_LT]]);

//Delete external login session records that don't have a corresponding session (hopefully because we just deleted it)
//we could potentially roll that into the above delete query but this is more robust about clearing out any orphaned records
//because we deleted the session hash and didn't clean this up.
$assertor->assertQuery('cleanupExternalLoginSession', []);

//The hard time of an hour here isn't well document (the setting suggests unlimited).  It's been that way for a long time so not planning to change it.
$cpsessioncuttoff = ($datastore->getOption('timeoutcontrolpanel') ? $sessioncutoff : $timenow - 3600);
$assertor->delete('cpsession', [['field'=>'dateline', 'value' => $cpsessioncuttoff, vB_dB_Query::OPERATOR_KEY => vB_dB_Query::OPERATOR_LT]]);

vB_Library::instance('search')->clean();

// expired lost passwords and email confirmations after 4 days
$assertor->assertQuery('cleanupUA', ['time' => $timenow - 345600]);

$markingcuttoff = $timenow - ($datastore->getOption('markinglimit') * 86400);
$assertor->delete('noderead', [['field'=>'readtime', 'value' => $markingcuttoff, vB_dB_Query::OPERATOR_KEY => vB_dB_Query::OPERATOR_LT]]);

vB_Api_Wol::buildSpiderList();

// Remove expired cache items
vB_Cache::resetCache(true);

log_cron_action('', $nextitem, 1);

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 111435 $
|| #######################################################################
\*=========================================================================*/
