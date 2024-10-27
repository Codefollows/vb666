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
 * Singleton object for accessing information about the current web request
 */
class vB5_Request implements ArrayAccess
{
	/**
	 * Singleton instance
	 * @var	vB5_User
	 */
	protected static $instance = null;

	/**
	 * Request inforamtion
	 * @var	array
	 */
	protected $data = array();

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
	protected function __construct()
	{
		$this->data = Api_InterfaceAbstract::instance()->callApi('request', 'getRequestInfo', array());
	}

	/**
	 * Returns information from the user array
	 *
	 * @param	string	Key in the user array
	 *
	 * @return	mixed	Value
	 */
	protected function _get($key)
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

	/**
	 * Functions to implement array access for this object
	 */

	public function offsetSet($key, $value) : void
	{
		throw new Exception('Cannot set request values via vB5_Request');
	}

	public function offsetUnset($key) : void
	{
		throw new Exception('Cannot change request values via vB5_Request');
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
