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

$datastore = vB::getDatastore();
$vboptions = $datastore->getValue('options');
$string = vB::getString();
$bbtitle_escaped = $string->htmlspecialchars($vboptions['bbtitle']);

if($vboptions['enablebirthdayemails'])
{
	$bf_misc_useroptions = $datastore->getValue('bf_misc_useroptions');
	$bf_ugp_genericoptions = $datastore->getValue('bf_ugp_genericoptions');
	$usergroupcache = $datastore->getValue('usergroupcache');

	$ids = [];
	foreach($usergroupcache AS $usergroupid => $usergroup)
	{
		if (
			$usergroup['genericoptions'] & $bf_ugp_genericoptions['showbirthday'] AND
			$usergroup['genericoptions'] & $bf_ugp_genericoptions['isnotbannedgroup'] AND
			!in_array($usergroup['usergroupid'], [1, 3, 4])
		)
		{
			$ids[] = $usergroupid;
		}
	}

	if($ids)
	{
		$now = vB::getRequest()->getTimeNow();
		$today = date('m-d', $now);

		$conditions = [
			'usergroupid' => $ids,
			['field' => 'options', 'value' => $bf_misc_useroptions['adminemail'], 'operator' =>  vB_dB_Query::OPERATOR_AND],
			['field' => 'options', 'value' => $bf_misc_useroptions['birthdayemail'], 'operator' =>  vB_dB_Query::OPERATOR_AND],
			['field' => 'birthday', 'value' => $today . '-', 'operator' =>  vB_dB_Query::OPERATOR_BEGINS],
		];

		if ($vboptions['birthdayemaillookback'])
		{
			$cutoff = $now - ($vboptions['birthdayemaillookback'] * 86400);
			$conditions[] = ['field' => 'lastvisit', 'value' => $cutoff . '-', 'operator' => vB_dB_Query::OPERATOR_GT];
		}

		$assertor = vB::getDbAssertor();

		$userids = $assertor->getColumn('user', 'userid', [vB_dB_Query::CONDITIONS_KEY => $conditions]);
		// Split up into two queries so that we can get the full list of userid's to prefetch for mailhashes
		// but not pull the rest of the columns until we need them... We might have to improve how this prefetching
		// is done... Hopefully this second query is performant via the userid key
		$birthdays = vB::getDbAssertor()->select('user', ['userid' => $userids], false, ['userid', 'username', 'displayname', 'email', 'languageid']);

		/** @var vB_Library_Unsubscribe */
		$unsubLib = vB_Library::instance('unsubscribe');
		$unsubLib->prefetchUserHashes($userids);

		$usersEmailed = [];
		vB_Mail::vbmailStart();
		foreach ($birthdays AS $userinfo)
		{
			$displayname_safe = $string->htmlspecialchars($userinfo['displayname']);
			$maildata = vB_Api::instanceInternal('phrase')->fetchEmailPhrases(
				// todo: add unsubscribe link to this email
				'birthday',
				[
					$displayname_safe,
					$bbtitle_escaped,
				],
				[$vboptions['bbtitle']],
				$userinfo['languageid']
			);

			// This email has its own user option (birthdayemail) which is checked above, so not checking the mailoption opt-out
			// (one-click / email-footer-link unsubscribe action will also set birthdayemail = 0)
			$recipientData = [
				'userid' => $userinfo['userid'],
				'email' => $userinfo['email'],
				'languageid' => $userinfo['languageid'],
			];
			$mailContent = [
				'toemail' => $userinfo['email'],
				'subject' => $maildata['subject'],
				'message' => $maildata['message'],
			];
			vB_Mail::vbmailWithUnsubscribe($recipientData, $mailContent);
			$usersEmailed[] = $userinfo['username'];
		}
		vB_Mail::vbmailEnd();

		if ($usersEmailed)
		{
			log_cron_action(implode(', ', $usersEmailed), $nextitem, 1);
		}
	}
}
/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 115430 $
|| #######################################################################
\*=========================================================================*/
