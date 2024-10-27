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
* Select and search functions for the userlog changes
*
* @package	vBulletin
*/
class vB_UserChangeLog
{
	/**
	* Full query or just count?
	*
	* @var	boolean
	*/
	private $just_count = false;

	/**
	* Set the just_count flag
	*
	* @param	boolean
	*/
	public function set_just_count($just_count = false)
	{
		if ($just_count)
		{
			$this->just_count = true;
		}
		else
		{
			$this->just_count = false;
		}
	}

	// ###################### Userchangelog Select "by something" Proxy Functions #######################
	//In vB4 there were a lot more of these.  If we need to add them back that should be possible
	//The underlying query will actually handle most of them fairly easily
	/**
	* Select the userlog by user
	*
	* @param	integer	userid of the user
	* @param	integer minimum time (UNIX_TIMESTAMP)
	* @param	integer maximum time (UNIX_TIMESTAMP)
	*
	* @return	mixed	sql query (no execute) / select resultset (execute + no just_count) / selected count (execute + just_count)
	*/
	public function sql_select_by_userid($userid, $time_start = 0, $time_end = 0, $page = 0, $limit = 100)
	{
		return $this->sql_select_core(['userid' => $userid], $time_start, $time_end, $page, $limit);
	}

	/**
	* Select the userlog by username
	*
	* @param	string	The username
	* @param	integer minimum time (UNIX_TIMESTAMP)
	* @param	integer maximum time (UNIX_TIMESTAMP)
	*
	* @return	mixed	sql query (no execute) / select resultset (execute + no just_count) / selected count (execute + just_count)
	*/
	public function sql_select_by_username($username, $time_start = 0, $time_end = 0, $page = 0, $limit = 100)
	{
		$filter = [
			'fieldname' => 'username',
			'fieldvalue' => $username,
		];
		return $this->sql_select_core($filter, $time_start, $time_end, $page, $limit);
	}

	// ###################### Userchangelog Select Core Functions #######################
	/**
	* Select query builder / executer
	*
	* @param	array	$filter -- the inital filter values
	* @param	integer $time_start -- minimum time (UNIX_TIMESTAMP)
	* @param	integer $time_end -- maximum time (UNIX_TIMESTAMP)
	* @param	integer $page -- which page we want to select
	* @param	integer $limit -- how many row on the page
	*
	* @return	mixed	sql query (no execute) / select resultset (execute + no just_count) / selected count (execute + just_count)
	*/
	private function sql_select_core($filter, $time_start, $time_end, $page, $limit)
	{
		$where = $filter;

		// when we have timeframe for the select then we add that to the condition
		if ($time_start)
		{
			$where['time_start'] = intval($time_start); // Send time_start for >= comparison
		}
		if ($time_end)
		{
			$where['time_end'] = intval($time_end); // Send time_end for <= comparison
		}

		$where[vB_dB_Query::PARAM_LIMITPAGE] = $page;
		$where[vB_dB_Query::PARAM_LIMIT] = $limit;

		// let's build the query if we got $where condition
		if ($where)
		{
			$assertor = vB::getDbAssertor();
			if ($this->just_count)
			{
				$where['just_count'] = true;
				$result = $assertor->getRow('getChangelogData', $where);
				$result = $result['change_count'];
			}
			else
			{
				$result = $assertor->getRows('getChangelogData', $where);
			}
		}

		return $result;
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 108362 $
|| #######################################################################
\*=========================================================================*/
