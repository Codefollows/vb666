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

/**
*
* @package vBulletin
* @version $Revision: 110802 $
* @since $Date: 2022-11-07 10:41:13 -0800 (Mon, 07 Nov 2022) $

*/
class shopify_dB_MYSQL_QueryDefs extends vB_dB_MYSQL_QueryDefs
{

	/**
	* This class is called by the new vB_dB_Assertor database class
	* It does the actual execution. See the vB_dB_Assertor class for more information
	*
	* Note that there is no install package. Therefore the ONLY thing that should be in this are queries unique to
	* the install/upgrade process. Especially there should be no table definitions unless they are vB3/4 tables not used
	* in vB5.
	*
	**/

	/*Properties====================================================================*/

	//type-specific

	protected $db_type = 'MYSQL';

	protected $table_data = [
		'shopify_session' => [
			'key' => '',
			'structure' => ['expires', 'id_hash', 'session'],
			'forcetext' => ['id_hash', 'session'],
		],
	];

	/**
	 * This is the definition for queries.
	 */
	protected $query_data = [
		/*
		//simple max queries -- candidates for reuse
		//the "oldcontenttypeid" queries can definitely be collapsed into one query
		'getMaxNodeid' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => 'SELECT MAX(nodeid) AS maxid FROM {TABLE_PREFIX}node'
		],
		*/
		'create_shopify_session' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_CREATE,
			'query_string' => 'CREATE TABLE IF NOT EXISTS  {TABLE_PREFIX}shopify_session (
				`id_hash` CHAR(32) NULL DEFAULT NULL,
				`session` MEDIUMTEXT,
				`expires` INT(10) UNSIGNED NOT NULL DEFAULT \'0\',
				UNIQUE KEY `lookup`  (`id_hash`),
				KEY `expires`  (`expires`)
			)'
		],
		'drop_shopify_session' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_DROP,
			'query_string' => "DROP TABLE IF EXISTS  `{TABLE_PREFIX}shopify_session`"
		],
	];
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 110802 $
|| #######################################################################
\*=========================================================================*/
