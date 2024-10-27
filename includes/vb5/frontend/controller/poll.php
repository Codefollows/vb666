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

class vB5_Frontend_Controller_Poll extends vB5_Frontend_Controller
{

	function actionVote()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		if (!isset($_POST['polloptionid']) AND !isset($_POST['polloptionids']))
		{
			$this->sendAsJson(false);
			exit();
		}

		if (!isset($_POST['polloptionid']))
		{
			$_POST['polloptionid'] = 0;
		}
		if (!isset($_POST['polloptionids']))
		{
			$_POST['polloptionids'] = array();
		}

		$input = array(
			'polloptionid' => intval($_POST['polloptionid']),
			'polloptionids' => (array)$_POST['polloptionids'],
		);

		$options = array();
		if ($input['polloptionids'])
		{
			$options = $input['polloptionids'];
		}
		else
		{
			$options = array($input['polloptionid']);
		}

		$api = Api_InterfaceAbstract::instance();
		$nodeid = $api->callApi('content_poll', 'vote', array($options));

		if (!$nodeid OR !is_numeric($nodeid))
		{
			$this->sendAsJson(false);
			exit();
		}

		// Get new poll data
		$this->ajaxPollData($nodeid);
	}

	function actionGet()
	{
		$input = array(
			'nodeid' => intval($_REQUEST['nodeid']),
		);

		$this->ajaxPollData($input['nodeid']);
	}

	function actionGetVoters()
	{
		$input = [
			'nodeid'       => intval($_REQUEST['nodeid']),
			'polloptionid' => intval($_REQUEST['polloptionid']),
		];

		$api = Api_InterfaceAbstract::instance();
		$poll = $api->callApi('content_poll', 'getContent', [$input['nodeid']]);
		if (empty($poll['errors']))
		{
			$poll = $poll[$input['nodeid']];

			//we need this and the rest of the poll information because we update the poll when we load
			//the voter info.  It's not entirely clear that we should because we can simplify a lot of
			//things if we don't.
			foreach($poll['options'] AS $key => $value)
			{
				//using vb_number_format on the front end isn't entirely kosher, but we don't have
				//a better way and need to figure out how to handle date/number formating correctly
				//we also use this in the template/bbcode on the front end
				$poll['options'][$key]['display_percentage'] = vb_number_format($value['percentage'], 2);
			}

			$voters = [];
			$pollOption = $poll['options'][$input['polloptionid']];
			if (!empty($pollOption['voters']))
			{
				$userinfo = $api->callApi('user', 'getNamecardInfoBulk', [$pollOption['voters']]);
				// some kind of error. If it's just the voters info, let's mask it and return the rest of the info.
				if (!empty($userinfo['infos']))
				{
					$voters = $userinfo['infos'];
				}
			}
			$poll['options'][$input['polloptionid']]['votersinfo'] = $voters;
		}

		$this->sendAsJson($poll);
	}

	private function ajaxPollData($nodeid)
	{
		$poll = Api_InterfaceAbstract::instance()->callApi('content_poll', 'getContent', array($nodeid));
		foreach ($poll as $v)
		{
			foreach($v['options'] AS $key => $value)
			{
				//using vb_number_format on the front end isn't entirely kosher, but we don't have
				//a better way and need to figure out how to handle date/number formating correctly
				//we also use this in the template/bbcode on the front end
				$v['options'][$key]['display_percentage'] = vb_number_format($value['percentage'], 2);
			}

			$this->sendAsJson(array(
				'options' => $v['options'],
				'poll_votes' => $v['poll_votes']
			));
			return;
		}
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 109872 $
|| #######################################################################
\*=========================================================================*/
