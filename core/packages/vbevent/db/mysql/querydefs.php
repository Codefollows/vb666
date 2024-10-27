<?php
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

/**
 * @package vBulletin
 */
class vBEvent_dB_MYSQL_QueryDefs extends vB_dB_MYSQL_QueryDefs
{
	/*Properties====================================================================*/

	//type-specific
	protected $db_type = 'MYSQL';

	/**
	 * This is the definition for tables we will process through. It saves a
	 * database query to put them here.
	 */
	protected $table_data = [
		'systemevent' => [
			'key' => 'systemeventid',
			'structure' => [
				'data', 'errormessage', 'priority', 'processkey', 'processtime', 'runafter', 'status',
				'systemeventid'
			],
			'forcetext' => ['errormessage', 'status'],
		],
	];

	/*
	 * This is the definition for queries.
	 */

	protected $query_data = [
		//Order by priority (boosted by time elapsed).  In the case of a tie select the older of the two.
		//We do the update/select approach to avoid more complicated locking that might result in stuff
		//getting permanently locked. Two processes might select the same record but two processes can't
		//update the same record since the updates are autonomous and we check that the processtime hasn't been
		//set yet.  The selects will then pull the correct marked records since process is a unique value.
		//
		//We need to do something about "stuck" events in case the PHP function crashes but it's not entirely
		//clear what.  A process shouldn't last more than a minute or so (on linux the timeout doesn't account
		//for "outside" time like DB calls) and so we'll treat anything older than two minutes as "unclaimed"
		//(might need to make that configurable).  In theory we should check the processkey but that's redundant
		//since processtime will always be 0 if an item is unclaimed.
		//
		//There is no index for the ORDER BY because there really isn't a good way to materialize a calculation
		//based on the current date without doing periodic updates on the table (which is likely more expensive
		//than just dealing with real time sorting).  This is implemented to prevent livelock of lower priority
		//events.  Hopefully there won't be enough rows in the table for this to be a major bottleneck.
		'claimNextEvent' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_UPDATE,
			'query_string' => "
				UPDATE `{TABLE_PREFIX}systemevent`
				SET `processkey` = {processkey},
					`processtime` = {timenow}
				WHERE
					`status` = '' AND
					`runafter` <= {timenow} AND
					`processtime` < {timenow} - 120
				ORDER BY (({timenow} - `runafter`) DIV 60) + `priority` DESC, `runafter` ASC
				LIMIT 1
			"
		],
	];
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 112143 $
|| #######################################################################
\*=========================================================================*/
