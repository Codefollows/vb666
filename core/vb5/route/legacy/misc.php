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

class vB5_Route_Legacy_Misc extends vB5_Route_Legacy
{
	protected $prefix = 'misc.php';

	protected function getNewRouteInfo()
	{
		$arguments = & $this->arguments;
		$param = & $this->queryParameters;
		$keys = array_keys($param);
		$idkey = array('t', 'threadid');
		if (empty($param['do']))
		{
			$param['do'] = 'showsmilies';
		}

		switch($param['do'])
		{
		case 'bbcode':
			$this->anchor = 'bbcode_reference/bbcode_why';
			return 'help';
		case 'showrules':
			// Unfortunately we can't remove the 'do=showrules' query param, as that's inherited from outside, see
			// vB_Api_Route::getRoute() ~ line 259-271.
			// At some point, we should figure out how to consolidate our "terms-of-service" route/page and the
			// termsofserviceurl SETTING.
			return 'terms-of-service';
		case 'showsmilies':
			$this->anchor = 'bbcode_reference/bbcode_smilies';
			return 'help';
		case 'whoposted':
			if ($set=array_intersect($keys, $idkey) AND $pid=intval($param[reset($set)]))
			{
				$oldid = $pid;
				$oldcontenttypeid = vB_Types::instance()->getContentTypeID(array('package' => 'vBForum', 'class' =>'Thread'));
				$node = vB::getDbAssertor()->getRow('vBForum:node', array(
					'oldid' => $oldid,
					'oldcontenttypeid' => $oldcontenttypeid
				));

				if (!empty($node))
				{
					$arguments['nodeid'] = $node['nodeid'];
					return $node['routeid'];
				}
			}
		default:
			throw new vB_Exception_404('invalid_page');
		}
	}

	public function getRedirect301()
	{
		$data = $this->getNewRouteInfo();
		$this->queryParameters = array();
		return $data;
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 115142 $
|| #######################################################################
\*=========================================================================*/
