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
$infractions = $assertor->assertQuery('getUserExpiredInfractions', ['timenow' => $timenow]);

if ($infractions->valid())
{
	$infractionid = [];

	$warningarray = [];
	$infractionarray = [];
	$ipointsarray = [];

	$userids = [];
	$usernames = [];
	$textnodeids = [];

	$clearCacheNodeIds = [];

	if (defined('IN_CONTROL_PANEL'))
	{
		echo '<h4>Expire Infractions:</h4>';
		echo '<ol>';
	}

	foreach ($infractions AS $infraction)
	{
		if (defined('IN_CONTROL_PANEL'))
		{
			echo '<li>Infraction NodeID: ' . $infraction['nodeid'];
		}

		$quantity = $assertor->update('infraction',
			['action' => 1, 'actiondateline' => $timenow],
			['nodeid' => $infraction['nodeid'], 'action' => 0]
		);

		// enforce atomic update so that related records are only updated at most one time,
		// in the event this task is executed more than one time
		if ($quantity)
		{
			if (defined('IN_CONTROL_PANEL'))
			{
				echo ' Updated';
			}

			// clear cache for these infraction nodes
			$clearCacheNodeIds[] = $infraction['nodeid'];

			$userids["$infraction[infracteduserid]"] = $infraction['username'];

			if ($infraction['points'])
			{

				$infractionarray["$infraction[infracteduserid]"]++;
				$ipointsarray["$infraction[infracteduserid]"] += $infraction['points'];
			}
			else
			{
					$warningarray["$infraction[infracteduserid]"]++;
			}

			if ($infraction['infractednodeid'] > 0)
			{
				$textnodeids[] = $infraction['infractednodeid'];
			}
		}
		else
		{
			if (defined('IN_CONTROL_PANEL'))
			{
				echo ' Update not needed';
			}
		}

		if (defined('IN_CONTROL_PANEL'))
		{
			echo '</li>';
		}
	}

	if (defined('IN_CONTROL_PANEL'))
	{
		echo '</ol>';
	}

	// ############################ MAGIC(tm) ###################################
	if (!empty($userids))
	{
		$result = $assertor->assertquery('buildUserInfractions', array(
			'points' => $ipointsarray,
			'infractions' => $infractionarray,
			'warnings' => $warningarray
			)
		);

		if ($result)
		{
			vB_Library::instance('Content_Infraction')->buildInfractionGroupIds(array_keys($userids));
		}

		if (!empty($textnodeids))
		{
			// mark the infracted node's text record as not having an infraction any more
			// 1 = infraction, 2 = warning, 0 = no infraction or warning (or an expired/reversed infraction)
			$assertor->update('vBforum:text', ['infraction' => 0], ['nodeid' => $textnodeids]);

			// clear cache for these text nodes
			$clearCacheNodeIds = array_merge($clearCacheNodeIds, $textnodeids);
		}

		if (defined('IN_CONTROL_PANEL'))
		{
			echo 'Updated user and text tables.';
		}
	}

	if (!empty($clearCacheNodeIds))
	{
		// invalidate cache
		vB_Api::instance('node')->clearCacheEvents($clearCacheNodeIds);
	}

	if (!empty($userids))
	{
	log_cron_action(implode(', ', $userids), $nextitem, 1);
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 107785 $
|| #######################################################################
\*=========================================================================*/
