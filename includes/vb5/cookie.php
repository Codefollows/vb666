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

class vB5_Cookie
{
	protected static $enabled = null;
	protected static $cookiePrefix = null;
	protected static $secure = null;

	// vB options
	protected static $path = null;
	protected static $domain = null;
	protected static $privacy_consent_cookie_name = null;
	protected static $privacy_consent_delete_cookie = null;

	const TYPE_UINT = 1;
	const TYPE_STRING = 2;

	/**
	 * Sets a cookie. Adds the configured COOKIE_PREFIX to the cookie name
	 *
	 * @param string Cookie name (COOKIE_PREFIX is added by default)
	 * @param string Cookie value
	 * @param int    Expire days
	 * @param bool   Is HTTP only?
	 */
	public static function set($name, $value, $expireDays = 0, $httpOnly = true)
	{
		self::setInternal($name, $value, $expireDays, $httpOnly, true);
	}

	private static function setInternal($name, $value, $expireDays, $httpOnly, $addPrefix)
	{
		if (!self::$enabled)
		{
			return;
		}

		if ($expireDays == 0)
		{
			$expire = 0;
		}
		else
		{
			$expire = time() + ($expireDays * 86400);
		}

		if ($addPrefix)
		{
			$name = self::$cookiePrefix . $name;
		}

		if (!setcookie($name, $value, $expire, self::$path, self::$domain, self::$secure, $httpOnly))
		{
			throw new Exception('Unable to set cookies');
		}
	}

	/**
	 * Returns the value of a cookie
	 *
	 * @param  string Cookie name to retrieve
	 * @param  int    One of the cookie value clean type constants from this class.
	 *
	 * @return string Cookie value
	 */
	public static function get($name, $type)
	{
		$name = self::$cookiePrefix . $name;

		$value = isset($_COOKIE[$name]) ? $_COOKIE[$name] : null;

		switch($type)
		{
			case self::TYPE_UINT:
				$value = intval($value);
				$value = $value < 0 ? 0 : $value;
				break;

			case self::TYPE_STRING:
				$value = strval($value);
				break;

			default:
				throw new Exception('Invalid cookie clean type');
				break;
		}

		return $value;
	}

	/**
	 * Deletes a cookie (prefix is added to the cookie name, if applicable)
	 *
	 * @param string Cookie name
	 */
	public static function delete($name)
	{
		self::setInternal($name, '', -1, true, true);
	}

	/**
	 * Deletes a cookie based on the passed name only, without adding the prefix
	 *
	 * @param string Cookie name
	 */
	protected static function deleteNoPrefix($name)
	{
		self::setInternal($name, '', -1, true, false);
	}

	/**
	 * Deletes all (most) cookies starting with cookiePrefix
	 */
	public static function deleteAll()
	{
		$prefix_length = strlen(self::$cookiePrefix);

		// Explicitly special case any cookies to keep instead of doing something
		// automagical like having the cookie not use the cookie_prefix so that it
		// doesn't get deleted, which is less transparent and wouldn't work anyway
		// if no prefix is set (all cookies are deleted in that case).
		$keepCookies = [];

		// keep the privacy consent cookie if applicable
		if (!self::$privacy_consent_delete_cookie)
		{
			// set up cookie name
			// keep this cookie name logic in sync with initGuestPrivacyBanner() in the JS
			$cookieName = self::$privacy_consent_cookie_name;
			if ($cookieName == '')
			{
				$prefix = $prefix_length ? self::$cookiePrefix : '';
				$cookieName = $prefix . 'privacy_consent_guest';
			}

			$keepCookies[$cookieName] = true;
		}

		// Ensure we delete certain cookies that don't use the prefix,
		// even if the site is set up to use a cookie prefix
		$prefixlessCookiesToDelete = [];

		// delete the privacy consent cookie if applicable
		if (self::$privacy_consent_delete_cookie AND self::$privacy_consent_cookie_name != '')
		{
			$prefixlessCookiesToDelete[self::$privacy_consent_cookie_name] = true;
		}

		// Delete cookies
		foreach ($_COOKIE AS $key => $val)
		{
			// keep certain cookies
			if (isset($keepCookies[$key]))
			{
				continue;
			}

			// delete any prefixless cookies
			if (isset($prefixlessCookiesToDelete[$key]))
			{
				self::deleteNoPrefix($key);
				continue;
			}

			// delete normal, potentially prefixed, cookies.
			// if we have a prefix, we only want to delete prefixed
			// cookies. If not, we delete all cookies.
			if ($prefix_length > 0)
			{
				if (strpos($key, self::$cookiePrefix) === 0)
				{
					self::deleteNoPrefix($key);
				}
			}
			else
			{
				self::deleteNoPrefix($key);
			}
		}
	}

	public static function isEnabled()
	{
		return self::$enabled;
	}

	public static function loadConfig($options)
	{
		$config = vB5_Config::instance();

		// these could potentially all be config options
		self::$enabled = ($config->cookie_enabled !== false);
		self::$cookiePrefix = $config->cookie_prefix;

		// vB options
		self::$path = $options['cookiepath'];
		self::$domain = $options['cookiedomain'];
		self::$privacy_consent_cookie_name = $options['privacy_consent_cookie_name'];
		self::$privacy_consent_delete_cookie = $options['privacy_consent_delete_cookie'];

		//if the site is on https, set cookies to secure.  Otherwise we can't without breaking things.
		//note that we should not trigger on the current url because
		//We can't always reliably detect if the current link is https because it can be offloaded to a proxy.
		$frontendurl = $options['frontendurl'];
		self::$secure = (stripos($frontendurl, 'https:') !== false);
	}

	/**
	 * Returns the value for an array stored in a cookie
	 *
	 * @param	string	Name of the cookie
	 * @param	mixed	ID of the data within the cookie
	 *
	 * @return	mixed
	 */
	public static function fetchBbarrayCookie($cookiename, $id)
	{
		$cookieValue = null;
		$cookie = self::get($cookiename, self::TYPE_STRING);
		if ($cookie != '')
		{
			$decodedCookie = json_decode($cookie, true);
			$cookieValue = $decodedCookie[$id] ?? null;
		}
		return $cookieValue;
	}

	/*
	 *	HELPER FUNCTIONS FOR COMMON CASES
	 */

	/**
	 * Sets cookies needed for session authentication
	 *
	 * @param	array	$loginInfo -
	 * 	sessionhash -- the sessionhash cookie value
	 * 	cpsession -- the hash for the "cp" login (only if $logintype == 'cplogin')
	 * 	password -- the rememberme token (not the actually a password, only if remember is true)
	 *
	 * @param	string $loginType -- 'cplogin' or ''
	 * @param bool $rememberme -- set the rememberme token.
	 * @return void
	 */
	public static function setSessionCookies(array $loginInfo, string $loginType, bool $remember) : void
	{
		vB5_Cookie::set('sessionhash', $loginInfo['sessionhash'], 0, true);

		if ($loginType === 'cplogin')
		{
			vB5_Cookie::set('cpsession', $loginInfo['cpsession'], 0, true);
		}

		// in frontend we set these cookies only if rememberme is on
		if ($remember)
		{
			self::setRememberMeCookies($loginInfo['password'], $loginInfo['userid'], 365);
		}
	}

	public static function setRememberMeCookies($rememberMeToken, $userid, $expires = 30)
	{
		vB5_Cookie::set('password', $rememberMeToken, $expires, true);
		vB5_Cookie::set('userid', $userid, $expires, true);
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 113874 $
|| #######################################################################
\*=========================================================================*/
