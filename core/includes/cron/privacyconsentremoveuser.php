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
require_once(DIR . '/includes/functions.php');

$timeNow = vB::getRequest()->getTimeNow();
$datastore = vB::getDatastore();
$vboptions = $datastore->getValue('options');

//if an invalid value got saved, default to 3
$deletelimit = $vboptions['user_autodelete_limit'];
if ($deletelimit <= 0)
{
	$deletelimit = 3;
}

// If invalid value was somehow saved into the setting, fallback to the default of 3 days.
$cooldownperiod = floatval($vboptions['user_autodelete_cooldown']);
if ($cooldownperiod <= 0)
{
	$cooldownperiod = 3;
}

// 1 day = 86400 seconds
$cooldownperiod = $cooldownperiod * 86400;

$config = vB::getConfig();
$noalter = [];
if (!empty($config['SpecialUsers']['undeletableusers']))
{
	$noalter = explode(',', $config['SpecialUsers']['undeletableusers']);
}

$baseConditions = [
	['field' => 'privacyconsent', 'value' => '-1', 'operator' => vB_dB_Query::OPERATOR_EQ],
	['field' => 'privacyconsentupdated', 'value' => '0', 'operator' => vB_dB_Query::OPERATOR_GT],
	['field' => 'privacyconsentupdated', 'value' => $timeNow - $cooldownperiod, 'operator' => vB_dB_Query::OPERATOR_LTE],
];

$conditions = $baseConditions;
if (!empty($noalter))
{
	$conditions[] = ['field' => 'userid', 'value' => $noalter, 'operator' => vB_dB_Query::OPERATOR_NE];
}

$assertor = vB::getDbAssertor();
$rows = $assertor->assertQuery('user', [
	vB_dB_Query::CONDITIONS_KEY => $conditions,
	vB_dB_Query::PARAM_LIMIT => $deletelimit,
]);

$userLibrary = vB_Library::instance('user');
$phraseApi  = vB_Api::instanceInternal('phrase');
// use default language since we don't know which user's session is running this.
// ... I wonder if anything terrible would happen if one of the deleted users is the one
// whose session this cron kicked off of...
$languageid = $vboptions['languageid'];
if (!$languageid)
{
	$languageid = -1;
}

foreach ($rows AS $__row)
{
	$__timeStart = microtime(true);
	$__userid = $__row['userid'];

	try
	{
		$userLibrary->delete($__userid, false);
	}
	catch (vB_Exception_Api $e)
	{
		// we have traditionally only taken the first error message -- and it's highly unlikely
		// that there will be more than one -- but we should, perhaps, consider handling the case
		// where there are multiple errors.
		$phrases = $phraseApi->renderPhrases($e->get_errors(), $languageid);
		$message = reset($phrases['phrases']);

		$phrases = $phraseApi->renderPhrases(['logmessage' => ['failed_to_delete_user_x_because_y', $__userid, $message]], $languageid);
		log_cron_action($phrases['phrases']['logmessage'], $nextitem, 0);
		continue;
	}
	catch (Exception $e)
	{
		$phrases = $phraseApi->renderPhrases(['logmessage' => ['failed_to_delete_user_x_because_y', $__userid, $e->getMessage()]], $languageid);
		log_cron_action($phrases['phrases']['logmessage'], $nextitem, 0);
		continue;
	}

	$__timeElapsed = microtime(true) - $__timeStart;
	$__timeElapsed = number_format($__timeElapsed, 2, '.', ',');
	log_cron_action(serialize([$__userid, $__row['privacyconsentupdated'], $__timeElapsed]), $nextitem, 1);
}

// Check for undeletable users who's withdrawn consent and flag them.
if (!empty($noalter))
{
	$conditions = $baseConditions;
	$conditions[] = ['field' => 'userid', 'value' => $noalter, 'operator' => vB_dB_Query::OPERATOR_EQ];

	// Should we have the limit here?  It probably doesn't matter this shouldn't really happen.
	$rows = $assertor->assertQuery('user', [
		vB_dB_Query::CONDITIONS_KEY => $conditions,
		vB_dB_Query::PARAM_LIMIT => $deletelimit,
	]);

	foreach ($rows AS $__row)
	{
		$phrases = $phraseApi->renderPhrases(['logmessage' => ['undeletable_user_withdrawn_consent', $__row['userid'], $__row['privacyconsentupdated']]], $languageid);
		log_cron_action($phrases['phrases']['logmessage'], $nextitem, 0);
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 114868 $
|| #######################################################################
\*=========================================================================*/
