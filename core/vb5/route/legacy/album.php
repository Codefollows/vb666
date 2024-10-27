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

class vB5_Route_Legacy_Album extends vB5_Route_Legacy
{
	protected $prefix = 'album.php';

	protected function getNewRouteInfo()
	{
		$argument = & $this->arguments;
		$param = & $this->queryParameters;
		if (!empty($param['u']))
		{
			$argument['userid'] = intval($param['u']);
			$argument['tab'] = 'media';
			return 'profile';
		}
		else if (!empty($param['albumid']))
		{
			$oldid = intval($param['albumid']);
			$oldcontenttypeid = vB_Types::instance()->getContentTypeID('vBForum_Album');
			$node = vB::getDbAssertor()->getRow('vBForum:node', array(
				'oldid' => $oldid,
				'oldcontenttypeid' => $oldcontenttypeid
			));

			if (empty($node))
			{
				throw new vB_Exception_404('invalid_page');
			}

			$argument['nodeid'] = $node['nodeid'];
			return 'album';
		}
		else
		{
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
|| # CVS: $RCSfile$ - $Revision: 102676 $
|| #######################################################################
\*=========================================================================*/
