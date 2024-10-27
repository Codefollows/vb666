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

class vB5_Template_Options
{
	private static $instance;
	private $cache = [];

	public static function instance()
	{
		if (!isset(self::$instance))
		{
			self::$instance = new self;
			self::$instance->getOptions();
		}

		return self::$instance;
	}

	public function get($name)
	{
		$path = explode('.', $name);

		$var = $this->cache;
		foreach ($path AS $t)
		{
			if (isset($var[$t]))
			{
				$var = $var[$t];
			}
			else
			{
				return null;
			}
		}

		return $var;
	}

	public function getOptions()
	{
		if (!isset($this->cache['options']))
		{
			$this->fetchOptions();
		}

		return $this->cache;
	}

	private function fetchOptions()
	{
		$response = Api_InterfaceAbstract::instance()->callApi('options', 'fetch');

		foreach ($response AS $key => $value)
		{
			$this->cache[$key] = $value;
		}
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 116130 $
|| #######################################################################
\*=========================================================================*/
