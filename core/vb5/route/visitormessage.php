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

class vB5_Route_VisitorMessage extends vB5_Route
{
	const REGEXP = 'member/(?P<userid>[0-9]+)(?P<username>(-[^?/]*)*)/visitormessage/(?P<nodeid>[0-9]+)(?P<title>(-[^!@\\#\\$%\\^&\\*\\(\\)\\+\\?/:;"\'\\\\,\\.<>= _]*)*)';
	protected $controller = 'page';

	private $routeArgs = '';


	public function __construct($routeInfo, $matches, $queryString = '', $anchor = '')
	{
		//we need to pass this along in the canonical route function and there is no good way
		//to reconstruct it, so we'll store it here.
		$this->routeArgs = $routeInfo['arguments'];

		parent::__construct($routeInfo, $matches, $queryString, $anchor);

		if (empty($matches['nodeid']))
		{
			throw new vB_Exception_Router('invalid_request');
		}
		else
		{
			$vmchannel = vB_Library::instance('node')->fetchVMChannel();
			if (!empty($vmchannel))
			{
				$this->arguments['channelid'] = $vmchannel;
			}

			$routeInfo['nodeid'] =  $matches['nodeid'];
			$this->arguments['nodeid'] = $matches['nodeid'];
			$this->arguments['contentid'] = $matches['nodeid'];
		}

		if (!empty($matches['title']))
		{
			$routeInfo['title'] = $matches['title'];
			$this->arguments['title'] = $matches['title'];
		}
		$node = vB_Library::instance('node')->getNodeBare($this->arguments['nodeid']);
		if (!empty($routeInfo['title']))
		{
			$this->arguments['title'] = vB_String::getUrlIdent($routeInfo['title']);
			// @TODO handle this in another way.
			$phrases = vB_Api::instanceInternal("phrase")->fetch(array('visitor_message_from_x'));
			$this->arguments['title'] = sprintf($phrases['visitor_message_from_x'], vB_String::getUrlIdent($node['authorname']));
		}

		// get userid and username
		if (empty($this->arguments['userid']))
		{
			//get userInfo
			if ($node['setfor'])
			{
				$user = vB_Api::instanceInternal('user')->fetchUsernames(array($node['setfor']));
				$user = $user[$node['setfor']];
				$this->arguments['userid'] = $node['setfor'];
				$this->arguments['username'] = $user['username'];
			}
		}
	}

	protected function setBreadCrumbs()
	{
		// I don't think anyone would set a "member/{userid}/visitormessage/123" as a home page and
		// not sure if we want to support that. Skipping homeroute check

		$profileurl = vB5_Route::buildUrl('profile',
			[
				'userid' => $this->arguments['userid'],
				'username' => $this->arguments['username'],
			]
		);

		$this->breadcrumbs = [
			0 => [
				'title' => $this->arguments['username'],
				'url' => $profileurl
			],
			1 => [
				'phrase' => 'visitor_message',
				'url' => ''
			],
		];
	}

	protected static function validInput(array &$data)
	{
		if (!parent::validInput($data) OR !isset($data['nodeid']) OR !is_numeric($data['nodeid']))
		{
			return FALSE;
		}

		$data['pageid'] = intval($data['pageid']);
		$data['prefix'] = $data['prefix'];
		$data['regex'] = $data['prefix'] . '/' . self::REGEXP;
		$data['arguments'] = serialize(
			array(
				'nodeid'	=> '$nodeid',
				'pageid'	=> $data['pageid']
			)
		);

		$data['class'] = __CLASS__;
		$data['controller']	= 'page';
		$data['action']		= 'index';
		// this field will be used to delete the route when deleting the channel (contains channel id)

		unset($data['pageid']);

		return parent::validInput($data);
	}

	public function getUrl()
	{
		if (empty($this->arguments['title']))
		{
			$node = vB_Library::instance('node')->getNodeBare($this->arguments['nodeid']);
			if (empty($node) OR !empty($node['errors']))
			{
				return FALSE;
			}

			if ($node['urlident'])
			{
				$this->arguments['title'] = $node['urlident'];
			}
			else
			{
				$this->arguments['title'] = vB_String::getUrlIdent($node['title']);
			}

		}

		if (empty($this->arguments['userid']))
		{
			if (!isset($node['nodeid']))
			{
				$node = vB_Library::instance('node')->getNodeBare($this->arguments['nodeid']);
			}

			if ($node['setfor'])
			{
				$user = vB_User::fetchUserinfo($node['setfor']);
				$this->arguments['userid'] = $user['userid'];
				$this->arguments['username'] = $user['username'];
			}
		}

		$url = '/member/' . $this->arguments['userid'] . '-' . vB_String::getUrlIdent($this->arguments['username']) . '/visitormessage/' . $this->arguments['nodeid'] . '-' . vB_String::vBStrToLower(vB_String::htmlSpecialCharsUni(str_replace(' ', '-', $this->arguments['title'])));

		if (strtolower(vB_String::getCharset()) != 'utf-8')
		{
			$url = vB_String::encodeUtf8Url($url);
		}

		return $url;

	}

	public function getCanonicalRoute()
	{
		if (!isset($this->canonicalRoute))
		{
			if (empty($this->arguments['title']))
			{
				$node = vB_Library::instance('node')->getNodeBare($this->arguments['nodeid']);

				if (empty($node) OR !empty($node['errors']))
				{
					return FALSE;
				}

				$this->arguments['title'] = $node['title'];
			}

			$routeInfo = array(
				'routeid' => $this->routeId,
				'prefix' => $this->prefix,
				'regex' => $this->regex,
				'nodeid' => $this->arguments['nodeid'],
				'title' => $this->arguments['title'],
				'controller' => $this->controller,
				'pageid' => $this->arguments['contentid'],
				'action' => $this->action,
				'arguments' => $this->routeArgs,
			);
			$this->canonicalRoute = new vB5_Route_VisitorMessage($routeInfo, array('nodeid' => $this->arguments['nodeid']),
				http_build_query($this->queryParameters));
		}

		return $this->canonicalRoute;
	}

	/**
	 * Returns arguments to be exported
	 * @param array $arguments
	 * @return array
	 */
	public static function exportArguments($arguments)
	{
		self::pageIdtoGuid($arguments);
		return $arguments;
	}

	/**
	 * Returns an array with imported values for the route
	 * @param array $arguments
	 * @return array
	 */
	public static function importArguments($arguments)
	{
		self::pageGuidToId($arguments);
		return $arguments;
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 111805 $
|| #######################################################################
\*=========================================================================*/
