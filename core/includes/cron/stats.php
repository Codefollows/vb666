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

// all these stats are for that day
$timestamp = vB::getRequest()->getTimeNow() - 3600 * 23;
// note: we only subtract 23 hours from the current time to account for Spring DST. Bug id 2673.

$month = date('n', $timestamp);
$day = date('j', $timestamp);
$year = date('Y', $timestamp);

$timestamp = mktime(0, 0, 0, $month, $day, $year);
// new users
$newusers = vB::getDbAssertor()->getRow('user', array(
	vB_dB_Query::CONDITIONS_KEY=> array(
		array('field'=>'joindate', 'value' => $timestamp, vB_dB_Query::OPERATOR_KEY => vB_dB_Query::OPERATOR_GTE)
	),
	vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_COUNT
));
$newusers = intval($newusers['count']);

// active users
$activeusers = vB::getDbAssertor()->getRow('user', array(
	vB_dB_Query::CONDITIONS_KEY=> array(
		array('field'=>'lastactivity', 'value' => $timestamp, vB_dB_Query::OPERATOR_KEY => vB_dB_Query::OPERATOR_GTE)
	),
	vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_COUNT
));
$activeusers = intval($activeusers['count']);

// new nodes
$newnodes = vB::getDbAssertor()->getRow('vBForum:node', array(
	vB_dB_Query::CONDITIONS_KEY=> array(
		array('field'=>'publishdate', 'value' => $timestamp, vB_dB_Query::OPERATOR_KEY => vB_dB_Query::OPERATOR_GTE)
	),
	vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_COUNT
));
$newnodes= intval($newnodes['count']);

// new threads
$newtopics = vB::getDbAssertor()->getRow('vBForum:getStarterStats', array('timestamp' => $timestamp));
$newtopics = intval($newtopics['count']);

// also rebuild user stats
vB_Library::instance('user')->buildStatistics();

/*insert query*/
vB::getDbAssertor()->assertQuery('stats', array(
	vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_INSERTIGNORE,
	'dateline' => $timestamp,
	'nuser' => $newusers,
	'npost' => $newnodes,
	'nthread' => $newtopics,
	'ausers' => $activeusers,
));

log_cron_action('', $nextitem, 1);

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 107437 $
|| #######################################################################
\*=========================================================================*/
