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

class vBMAPI_dB_MYSQL_QueryDefs extends vB_dB_MYSQL_QueryDefs
{
	protected $db_type = 'MYSQL';

	protected $table_data = [
		'mapiposthash' => [
			'key' => 'posthashid',
			'structure' => ['dateline', 'filedataid', 'posthash', 'posthashid'],
			'forcetext' => ['posthash'],
		],
	];

	protected $query_data = array(
		'getPosthashFiledataids' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => 'SELECT filedataid FROM {TABLE_PREFIX}mapiposthash WHERE posthash = {posthash}'
		),
		'insertPosthashFiledataid' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_INSERT,
			'query_string' => 'INSERT INTO {TABLE_PREFIX}mapiposthash(posthash, filedataid, dateline) VALUES({posthash}, {filedataid}, {dateline})'
		),
		'cleanPosthash' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_DELETE,
			'query_string' => 'DELETE FROM {TABLE_PREFIX}mapiposthash WHERE dateline < {cutoff}'
		),
	);
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 105533 $
|| #######################################################################
\*=========================================================================*/
