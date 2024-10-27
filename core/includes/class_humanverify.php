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
* Abstracted human verification class
*
* @package 		vBulletin
*
*/
class vB_HumanVerify
{
	private static $instance;

	/**
	* Singleton emulation
	*
	* @return	object
	*/
	public static function &fetch_library()
	{
		if (!isset(self::$instance))
		{
			self::setLibrary();
		}

		return self::$instance;
	}

	public static function disableHV()
	{
		self::setLibrary('disabled');
	}

	public static function setLibrary($library = '')
	{
		if (empty($library))
		{
			$vboptions = vB::getDatastore()->getValue('options');
			$library = strtolower($vboptions['hv_type'] ? $vboptions['hv_type'] : 'disabled');
		}
		else
		{
			$library = strtolower($library);
		}

		$selectclass = 'vB_HumanVerify_' . $library;
		require_once(DIR . '/includes/class_humanverify_' . $library . '.php');
		self::$instance = new $selectclass();
	}
}


/**
* Abstracted human verification class
*
* @package 		vBulletin
* @date 		$Date: 2021-12-01 17:32:14 -0800 (Wed, 01 Dec 2021) $
*
* @abstract
*/
abstract class vB_HumanVerify_Abstract
{
	/**
	* Error string
	*
	* @var	string
	*/
	protected $error = '';

	/**
	* Last generated hash
	*
	* @var	string
	*/
	protected $hash = '';

	/**
	 * Deleted a Human Verification Token
	 *
	 * @param	string	The hash to delete
	 * @param	string	The Corresponding Option
	 * @param	integer	Whether the token has been viewd
	 *
	 * @return	boolean	Was anything deleted?
	 *
	*/
	protected function delete_token($hash, $answer = NULL, $viewed = NULL)
	{
		$data = array(
			'hash' => $hash,
		);

		if ($answer !== NULL)
		{
			$data['answer'] = $answer;
		}
		if ($viewed !== NULL)
		{
			$data['viewed'] = intval($viewed);
		}

		if ($this->hash == $hash)
		{
			$this->hash = '';
		}

		vB::getDbAssertor()->assertQuery('humanverify', array(
			vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_DELETE,
			vB_dB_Query::CONDITIONS_KEY => $data,
		));

		return vB::getDbAssertor()->affected_rows() ? true : false;
	}

	/**
	 * Generates a Random Token and stores it in the database
	 *
	 * @param	boolean	Delete the previous hash generated
	 *
	 * @return	array	an array consisting of the hash, and the answer
	 *
	*/
	public function generate_token($deletehash = true)
	{
		$verify = array(
			'hash'   => md5(uniqid(vbrand(), true)),
			'answer' => $this->fetch_answer(),
		);

		if ($deletehash AND $this->hash)
		{
			$this->delete_token($this->hash);
		}
		$this->hash = $verify['hash'];

		vB::getDbAssertor()->assertQuery('humanverify', array(
			vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_INSERT,
			'hash' => $verify['hash'],
			'answer' => $verify['answer'],
			'dateline' => vB::getRequest()->getTimeNow()
		));

		return $verify;
	}

	/**
	 * Verifies whether the HV entry was correct
	 *
	 * @param	array	An array consisting of the hash, and the inputted answer
	 *
	 * @return	boolean
	 *
	*/
	public function verify_token($input)
	{
		return true;
	}

	/**
	 * Returns any errors that occurred within the class
	 *
	 * @return	mixed
	 *
	*/
	public function fetch_error()
	{
		return $this->error;
	}

	/**
	 * Generates an expected answer
	 *
	 * @return	mixed
	 *
	*/
	protected function fetch_answer() {}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 108193 $
|| #######################################################################
\*=========================================================================*/
