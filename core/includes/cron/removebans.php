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

// select all banned users who are due to have their ban lifted
$assertor = vB::getDbAssertor();
$usergroupcache = vB::getDatastore()->getValue('usergroupcache');

$bannedusers = $assertor->assertQuery('getBannedUsers', array('liftdate' => vB::getRequest()->getTimeNow()));

// some users need to have their bans lifted
foreach ($bannedusers as $banneduser)
{
	// get usergroup info
	$getusergroupid = ($banneduser['bandisplaygroupid'] ? $banneduser['bandisplaygroupid'] : $banneduser['banusergroupid']);

	$usergroup = $usergroupcache["$getusergroupid"];
	if ($banneduser['bancustomtitle'])
	{
		$usertitle = $banneduser['banusertitle'];
	}
	else if (!$usergroup['usertitle'])
	{
		$gettitle = $assertor->getRow('usertitle', array(
			vB_dB_Query::CONDITIONS_KEY=> array(
				array('field'=>'minposts', 'value' => $banneduser['posts'], vB_dB_Query::OPERATOR_KEY => vB_dB_Query::OPERATOR_LTE)
			),
			array(array('field' => 'minposts', 'direction' => vB_dB_Query::SORT_DESC))
		));
		$usertitle = $gettitle['title'];
	}
	else
	{
		$usertitle = $usergroup['usertitle'];
	}

	// update users to get their old usergroupid/displaygroupid/usertitle back
	$userdm = new vB_Datamanager_User(vB_DataManager_Constants::ERRTYPE_SILENT);
	$userdm->set_existing($banneduser);
	$userdm->set('usertitle', $usertitle);
	$userdm->set('usergroupid', $banneduser['banusergroupid']);
	$userdm->set('displaygroupid', $banneduser['bandisplaygroupid']);
	$userdm->set('customtitle', $banneduser['bancustomtitle']);

	$userdm->save();
	unset($userdm);

	$users["$banneduser[userid]"] = $banneduser['username'];
}
if (!empty($users))
{
	// delete ban records
	vB::getDbAssertor()->delete('userban', array('userid' => array_keys($users)));

	// log the cron action
	log_cron_action(implode(', ', $users), $nextitem, 1);
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 107437 $
|| #######################################################################
\*=========================================================================*/
