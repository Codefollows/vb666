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

$assertor = vB::getDbAssertor();
$datastore = vB::getDatastore();

//  Clean up MAPI attachment helper table
$timenow = vB::getRequest()->getTimeNow();
$twodaysago = $timenow - (60 * 60 * 24 * 2);
$onemonthago = $timenow - (60 * 60 * 24 * 30);
$result = $assertor->assertQuery('vBMAPI:cleanPosthash', ['cutoff' => $twodaysago]);

// Clean the nodehash table
$assertor->delete('vBForum:nodehash', [['field' => 'dateline', 'value' => $twodaysago, 'operator' => vB_dB_Query::OPERATOR_LT]]);
// Clean all expired redirects
 vB_Library::instance('content_redirect')->deleteExpiredRedirects();

$searchCloudeHistory = $datastore->getOption('tagcloud_searchhistory');
if ($searchCloudeHistory)
{
	$assertor->delete('vBForum:tagsearch', [
		[
			'field'=>'dateline',
			'value' => $timenow - ($searchCloudeHistory * 60 * 60 * 24),
			vB_dB_Query::OPERATOR_KEY => vB_dB_Query::OPERATOR_LT,
		]
	]);
}

// Clean out autosave
$assertor->delete('vBForum:autosavetext', [['field' => 'dateline', 'value' => $onemonthago, 'operator' => vB_dB_Query::OPERATOR_LT]]);

$userLib = vB_Library::instance('user');
$userLib->cleanIpInfo();

if ($datastore->getOption('expireoldpasswords'))
{
	$userLib->invalidateOldPasswords(60 * 60 * 24 * 365, 10000);
}

// clean up expired user referral codes
vB_Library::instance('referuser')->deleteExpiredReferralCodes();

// Also update some user ranks contingent on joindates. We may split this
// out into its own cron later if we move the other userrank checks to crons.
updateUserRanksForCron();


// Automatic topic expiries.
/** @var vB_Library_Node */
$nodeLib = vB_Library::instance('node');
$nodeLib->autoExpireTopics();




log_cron_action('', $nextitem, 1);


// considered private to this script.
function updateUserRanksForCron()
{
	$assertor = vB::getDbAssertor();
	$ranklib = vB_Library::instance('userrank');
	$haveRanks = $ranklib->haveRanks();
	if (!$haveRanks)
	{
		return;
	}

	$ranks = $assertor->getColumn('vBForum:ranks', 'registrationtime', [
			vB_dB_Query::CONDITIONS_KEY => [
				['field' => 'registrationtime', 'value' => 0, 'operator' => vB_dB_Query::OPERATOR_GT]
			],
		],
		['field' => 'registrationtime', 'direction' => vB_dB_Query::SORT_DESC,]
	);
	// Note, make sure this is > 0.
	$fudgeFactorDays = 1;
	$fudgeFactor = $fudgeFactorDays * 86400;
	$timenow = vB::getRequest()->getTimeNow();
	$lastMax = 0;
	$ranges = [];
	foreach ($ranks AS $__secondsSinceRegistration)
	{
		$__cutoff = $timenow - $__secondsSinceRegistration;
		$__min = $__cutoff - $fudgeFactor;
		$__max = $__cutoff + $fudgeFactor;
		if (!empty($ranges) AND $__min <= $lastMax)
		{
			// If next higher range's min overlaps with our previous's max, just
			// merge with previous range.
			end($ranges);
			$__k = key($ranges);
			$ranges[$__k][1] = $__max;
		}
		else
		{
			$ranges[] = [$__min, $__max];

		}
		$lastMax = $__max;
	}

	if (empty($ranges))
	{
		return;
	}

	foreach ($ranges AS [$__min, $__max])
	{
		$__conditions = [
			['field' => 'joindate', 'value' => $__min, 'operator' => vB_dB_Query::OPERATOR_GTE],
			['field' => 'joindate', 'value' => $__max, 'operator' => vB_dB_Query::OPERATOR_LTE],
		];

		// based on vB_Library_User::updatePostCountInfo()
		$__userinfos = $assertor->select(
			'user',
			$__conditions,
			false,
			[
				'customtitle',
				'usertitle',
				'userid',
				'posts',
				'usergroupid',
				'displaygroupid',
				'membergroupids',
				'joindate',
				'startedtopics',
				'reputation',
				'totallikes',
			]
		);

		foreach ($__userinfos AS $__info)
		{
			// ranks are stored in usertextfield.rank . Not sure if it's worth checking for "did change"...
			$__rankHtml = $ranklib->getRankHtml($__info);
			// Note, running an update while holding a select cursor open might be problematic if we ever switch to
			// unbuffered in general...
			$assertor->update('vBForum:usertextfield', ['rank' => $__rankHtml], ['userid' => $__info['userid']]);
		}
	}

}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 114729 $
|| #######################################################################
\*=========================================================================*/
