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
 * Singleton object for accessing information about the currently logged in user
 */
class vB5_User implements ArrayAccess
{
	/**
	 * Singleton instance
	 * @var	vB5_User
	 */
	private static $instance = null;

	/**
	 * User inforamtion
	 * @var	array
	 */
	private $data = [];
	private $groupscachekey;

	/**
	 * Singleton instance getter
	 *
	 * @return	vB5_User
	 */
	public static function instance()
	{
		if (self::$instance === null)
		{
			$class = __CLASS__;
			self::$instance = new $class;
		}

		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct()
	{
		$this->data = Api_InterfaceAbstract::instance()->callApi('user', 'fetchCurrentUserinfo', array());
	}

	/**
	 * Returns information from the user array
	 *
	 * @param	string	Key in the user array
	 *
	 * @return	mixed	Value
	 */
	private function _get($key)
	{
		return isset($this->data[$key]) ? $this->data[$key] : null;
	}

	/**
	 * Static getter
	 *
	 * @param	string	Key in the user array
	 *
	 * @return	mixed	Value
	 */
	public static function get($key)
	{
		return self::instance()->_get($key);
	}

	/**
	 * Magic getter
	 *
	 * @param	string	Key in the user array
	 *
	 * @return	mixed	Value
	 */
	public function __get($key)
	{
		return $this->_get($key);
	}

	public static function getLanguageId()
	{
		if ($languageid = vB5_Cookie::get('languageid', vB5_Cookie::TYPE_UINT))
		{
			return $languageid;
		}
		else
		{
			return self::get('languageid');
		}
	}

	public static function getGroupKey()
	{
		$instance = self::instance();
		if(!$instance->groupscachekey)
		{
			//note we're being extremely paranoid about validation here because minor differences
			//could result in a different hash even though we don't want it to.
			//A membergroup string of '1, 2, 3' should be the same as '1,2,3' we need to make sure it is.
			$groupid = $instance->usergroupid;
			$membergroupids = $instance->membergroupids;

			//this should really be converted to an array before now let's check so we
			//don't get breakage if that's changed.
			if(!is_array($membergroupids))
			{
				$membergroupids = trim($membergroupids);
				if($membergroupids)
				{
					$membergroupids = explode(',', $membergroupids);
				}
				else
				{
					$membergroupids = [];
				}
			}

			$membergroupids[] = $groupid;
			$membergroupids = array_unique(array_map('intval', $membergroupids));
			sort($membergroupids);

			$instance->groupscachekey = implode(',', $membergroupids);
		}
		return $instance->groupscachekey;
	}

	/**
	 * Functions to implement array access for this object
	 */

	public function offsetSet($key, $value) : void
	{
		throw new Exception('Cannot set user values via vB5_User');
	}

	public function offsetUnset($key) : void
	{
		throw new Exception('Cannot change user values via vB5_User');
	}

	public function offsetExists($key) : bool
	{
		return isset($this->data[$key]);
	}

	public function offsetGet($key) : mixed
	{
		return isset($this->data[$key]) ? $this->data[$key] : null;
	}

}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 115976 $
|| #######################################################################
\*=========================================================================*/
