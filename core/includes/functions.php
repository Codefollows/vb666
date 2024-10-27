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

// #############################################################################
/**
* Essentially a wrapper for the ternary operator.
*
* @deprecated	Deprecated as of 3.5. Use the ternary operator.
*
* @param	string	Expression to be evaluated
* @param	mixed	Return this if the expression evaluates to true
* @param	mixed	Return this if the expression evaluates to false
*
* @return	mixed	Either the second or third parameter of this function
*/
function iif($expression, $returntrue, $returnfalse = '')
{
	return ($expression ? $returntrue : $returnfalse);
}

// #############################################################################
/**
* Class factory. This is used for instantiating the extended classes.
*
* @param	string			The type of the class to be called (user, forum etc.)
* @param	vB_Registry	Unused.  Pass as NULL
* @param	integer			One of the ERRTYPE_x constants
* @param	string			no longer used
*
* @return	vB_DataManager	An instance of the desired class
* @deprecated use autoloader/classnames directly.
*/
function &datamanager_init($classtype, &$registry, $errtype = vB_DataManager_Constants::ERRTYPE_STANDARD, $forcefile = '')
{
	if (preg_match('#^\w+$#', $classtype))
	{
		$classtype = strtolower($classtype);
		$classname = 'vB_DataManager_' . $classtype;
		$object = new $classname($errtype);
		return $object;
	}
}

// #############################################################################
/**
* Converts A-Z to a-z, doesn't change any other characters
*
* @deprecated
* @see vB_String::vBStrToLower()
* @param	string	String to convert to lowercase
*
* @return	string	Lowercase string
*/
function vbstrtolower($string)
{
	return vB_String::vBStrToLower($string, false);
}

// #############################################################################
/**
* Use vB_String::vbStrlen()
* Attempts to do a character-based strlen on data that might contain HTML entities.
* By default, it only converts numeric entities but can optional convert &quot;,
* &lt;, etc. Uses a multi-byte aware function to do the counting if available.
*
* @deprecated
* @see vB_String::vbStrlen()
* @param	string	String to be measured
* @param	boolean	If true, run unhtmlspecialchars on string to count &quot; as one, etc.
*
* @return	integer	Length of string
*/
function vbstrlen($string, $unhtmlspecialchars = false)
{
	return vB_String::vbStrlen($string, $unhtmlspecialchars);
}

/**
* Chops off a string at a specific length, counting entities as once character
* and using multibyte-safe functions if available.
*
* @deprecated
* @see vB_String::vbChop()
* @param	string	String to chop
* @param	integer	Number of characters to chop at
*
* @return	string	Chopped string
*/
function vbchop($string, $length)
{
	return vB_String::vbChop($string, $length);
}

// #############################################################################
/**
* Formats a number with user's own decimal and thousands chars
*
* @param	mixed	Number to be formatted: integer / 8MB / 16 GB / 6.0 KB / 3M / 5K / ETC
* @param	integer	Number of decimal places to display
* @param	boolean	Special case for byte-based numbers
*
* @return	mixed	The formatted number
*/
function vb_number_format($number, $decimals = 0, $bytesize = false, $decimalsep = null, $thousandsep = null, $byteUnitSeparator = " ")
{
	global $vbulletin;

	if (defined('VB_API') AND VB_API === true)
	{
		// The number format of API should always be standard
		$decimalsep = '.';
		$thousandsep = '';
	}

	$type = '';

	if (empty($number))
	{
		return 0;
	}
	else if (preg_match('#^(\d+(?:\.\d+)?)(?>\s*)([mkg])b?$#i', trim($number), $matches))
	{
		switch(strtolower($matches[2]))
		{
			case 'g':
				$number = $matches[1] * 1073741824;
				break;
			case 'm':
				$number = $matches[1] * 1048576;
				break;
			case 'k':
				$number = $matches[1] * 1024;
				break;
			default:
				$number = $matches[1] * 1;
		}
	}

	if ($bytesize)
	{
		$vbphrase = vB_Api::instanceInternal('phrase')->fetch(array('gigabytes', 'megabytes', 'kilobytes', 'bytes'));

		if ($number >= 1073741824)
		{
			$number = $number / 1073741824;
			$decimals = 2;
			$type = "{$byteUnitSeparator}$vbphrase[gigabytes]";
		}
		else if ($number >= 1048576)
		{
			$number = $number / 1048576;
			$decimals = 2;
			$type = "{$byteUnitSeparator}$vbphrase[megabytes]";
		}
		else if ($number >= 1024)
		{
			$number = $number / 1024;
			$decimals = 1;
			$type = "{$byteUnitSeparator}$vbphrase[kilobytes]";
		}
		else
		{
			$decimals = 0;
			$type = "{$byteUnitSeparator}$vbphrase[bytes]";
		}
	}

	if ($decimalsep === null)
	{
		$decimalsep = $vbulletin->userinfo['lang_decimalsep'];
	}
	if ($thousandsep === null)
	{
		$thousandsep = $vbulletin->userinfo['lang_thousandsep'];
	}

	return str_replace('_', '&nbsp;', number_format(floatval($number), $decimals, $decimalsep, $thousandsep)) . $type;
}

// #############################################################################
/**
* vBulletin's own random number generator
*
* @param	integer	Minimum desired value
* @param	integer	Maximum desired value
* @param	mixed Param is not used.
*/
function vbrand($min = 0, $max = 0, $seed = -1)
{
	mt_srand(crc32(microtime()));

	if ($max AND $max <= mt_getrandmax())
	{
		$number = mt_rand($min, $max);
	}
	else
	{
		$number = mt_rand();
	}
	// reseed so any calls outside this function don't get the second number
	mt_srand();

	return $number;
}

// #############################################################################
/**
* Returns an array of usergroupids from all the usergroups to which a user belongs
*
* @param	array	User info array - must contain usergroupid and membergroupid fields
* @param	boolean	Whether or not to fetch the user's primary group as part of the returned array
*
* @return	array	Usergroup IDs to which the user belongs
*/
function fetch_membergroupids_array($user, $getprimary = true)
{
	if (!empty($user['membergroupids']))
	{
		$membergroups = explode(',', str_replace(' ', '', $user['membergroupids']));
	}
	else
	{
		$membergroups = [];
	}

	if ($getprimary)
	{
		$membergroups[] = $user['usergroupid'];
	}

	return array_unique($membergroups);
}

// #############################################################################
/**
* Works out if a user is a member of the specified usergroup(s)
*
* This function can be overloaded to test multiple usergroups: is_member_of($user, 1, 3, 4, 6...)
*
* @param	array	User info array - must contain userid, usergroupid and membergroupids fields
* @param	integer	Usergroup ID to test
* @param	boolean	Pull result from cache (no longer used, no caching)
*
* @return	boolean
* @deprecated use vB_Library_User::isMemberOf
*/
//this is used in the templates which may make it trickier to get rid of.  May need to
//make it an API function as well as a library function.
function is_member_of($userinfo, $usergroupid, $cache = true)
{
	//not messing with this.  It's crazy and will go away when the function is.
	//the cache avoids a call to fetch_membergroupids_array which, frankly, isn't worth caching
	//would be nice to have a proper user object so we can store that at an object level but we don't.
	switch (func_num_args())
	{
		// 1 can't happen

		case 2: // note: func_num_args doesn't count args with default values unless they're overridden
			$groups = is_array($usergroupid) ? $usergroupid : array($usergroupid);
		break;

		case 3:
			if (is_array($usergroupid))
			{
				$groups = $usergroupid;
				$cache = (bool)$cache;
			}
			else if (is_bool($cache))
			{
				// passed in 1 group and a cache state
				$groups = array($usergroupid);
			}
			else
			{
				// passed in 2 groups
				$groups = array($usergroupid, $cache);
				$cache = true;
			}
		break;

		default:
			// passed in 4+ args, which means it has to be in the 1,2,3 method
			$groups = func_get_args();
			unset($groups[0]);

			$cache = true;
	}

	return vB_Library::instance('user')->isMemberOf($userinfo, $groups);
}

// #############################################################################
/**
* Works out if the specified user is 'in Coventry'
*
* @param	integer	User ID
* @param	boolean	Whether or not to confirm that the visiting user is himself in Coventry or not
*
* @return	boolean
*/
function in_coventry($userid, $includeself = false)
{
	global $vbulletin;
	static $Coventry;

	// if user is guest, or user is bbuser, user is NOT in Coventry.
	if ($userid == 0 OR ($userid == $vbulletin->userinfo['userid'] AND $includeself == false))
	{
		return false;
	}

	if (!is_array($Coventry))
	{
		$options = vB::getDatastore()->get_value('options');
		if (trim($options['globalignore']) != '')
		{
			$Coventry = preg_split('#\s+#s', $options['globalignore'], -1, PREG_SPLIT_NO_EMPTY);
		}
		else
		{
			$Coventry = array();
		}
	}

	// if Coventry is empty, user is not in Coventry
	if (empty($Coventry))
	{
		return false;
	}

	// return whether or not user's id is in Coventry
	return in_array($userid, $Coventry);
}

// #############################################################################
/**
* Replaces any instances of words censored in $options['censorwords'] with $options['censorchar']
*
* @deprecated
* @see vB_String::fetchCensoredText()
*
* @param	string	Text to be censored
*
* @return	string
*/
function fetch_censored_text($text)
{
	return vB_String::fetchCensoredText($text);
}

// #############################################################################
/**
* Fetches the remaining characters in a filename after the final dot
*
* @param	string	The filename to test
*
* @return	string	The extension of the provided file
*/
function file_extension($filename)
{
	return substr(strrchr($filename, '.'), 1);
}

// #############################################################################
/**
* Tests a string to see if it's a valid email address
*
* @param	string	Email address
*
* @return	boolean
* @deprecated
*/
function is_valid_email($email)
{
	return vB_String::isValidEmail($email);
}

/**
 * fetch_query_sql() refactoring
 *
 * @return	array	Returns an array of values to be used by the assertor object.
 * 					array(
 * 						'set' => If updating array of fields to be set
 * 						'insert' => if inserting array of insertions
 * 						'conditions' => condition in assertor format
 * 					)
 */
function fetchQuerySql($queryvalues, $table, $condition = array(), $exclusions = array())
{
	$setValues = array();
	$insertValues = array();
	$sqlCond = array();
	$structure = vB::getDbAssertor()->fetchTableStructure($table);
	// try to fetch it from vBForum package
	if (empty($structure))
	{
		$structure = vB::getDbAssertor()->fetchTableStructure('vBForum:' . $table);
	}

	// undefined table...
	if (empty($structure))
	{
		return false;
	}
	$structure = $structure['structure'];

	if (!empty($condition))
	{
		foreach($queryvalues AS $fieldname => $value)
		{
			if (!preg_match('#^\w+$#', $fieldname))
			{
				continue;
			}

			if (!in_array($fieldname, $exclusions) AND in_array($fieldname, $structure))
			{
				$setValues[$fieldname] = $value;
			}
		}

		foreach($condition AS $fieldname => $value)
		{
			if (!preg_match('#^\w+$#', $fieldname))
			{
				continue;
			}

			if (!in_array($fieldname, $exclusions) AND in_array($fieldname, $structure))
			{
				$sqlCond[$fieldname] = $value;
			}
		}
	}
	else
	{
		$fieldlist = '';
		$valuelist = '';
		foreach($queryvalues AS $fieldname => $value)
		{
			if (!preg_match('#^\w+$#', $fieldname))
			{
				continue;
			}

			if (!in_array($fieldname, $exclusions) AND in_array($fieldname, $structure))
			{
				$insertValues[$fieldname] = $value;
			}
		}
	}

	return array('set' => $setValues, 'insert' => $insertValues, 'conditions' => $sqlCond);
}

// #############################################################################
/**
* fetches the proper username markup and title
*
* @param	array	(ref) User info array
* @param	string	Name of the field representing displaygroupid in the User info array
* @param	string	Name of the field representing username in the User info array
*
* @return	string
*/
function fetch_musername(&$user, $displaygroupfield = 'displaygroupid', $usernamefield = 'username')
{
	global $vbulletin;

	if (!empty($user['musername']))
	{
		// function already been called
		return $user['musername'];
	}

	$username = $user["$usernamefield"];

	if (!empty($user['infractiongroupid']) AND $vbulletin->usergroupcache["$user[usergroupid]"]['genericoptions'] & $vbulletin->bf_ugp_genericoptions['isnotbannedgroup'])
	{
		$displaygroupfield = 'infractiongroupid';
	}

	if (isset($user["$displaygroupfield"], $vbulletin->usergroupcache["$user[$displaygroupfield]"]) AND $user["$displaygroupfield"] > 0)
	{
		// use $displaygroupid
		$displaygroupid = $user["$displaygroupfield"];
	}
	else if (isset($vbulletin->usergroupcache["$user[usergroupid]"]) AND $user['usergroupid'] > 0)
	{
		// use primary usergroupid
		$displaygroupid = $user['usergroupid'];
	}
	else
	{
		// use guest usergroup
		$displaygroupid = 1;
	}

	$user['musername'] = $vbulletin->usergroupcache["$displaygroupid"]['opentag'] . $username . $vbulletin->usergroupcache["$displaygroupid"]['closetag'];
	$user['displaygrouptitle'] = $vbulletin->usergroupcache["$displaygroupid"]['title'];
	$user['displayusertitle'] = $vbulletin->usergroupcache["$displaygroupid"]['usertitle'];

	if ($displaygroupfield == 'infractiongroupid' AND $usertitle = $vbulletin->usergroupcache["$user[$displaygroupfield]"]['usertitle'])
	{
		$user['usertitle'] = $usertitle;
	}
	else if (isset($user['customtitle']) AND $user['customtitle'] == 2)
	{
		$user['usertitle'] = htmlspecialchars_uni($user['usertitle']);
	}

	return $user['musername'];
}

// #############################################################################
/**
* Returns an array containing info for the specified forum, or false if forum is not found
*
* @param	integer	(ref) Forum ID
* @param	boolean	Whether or not to return the result from the forumcache if it exists
*
* @deprecated
* @return	mixed
*/
function fetch_foruminfo(&$forumid, $usecache = true)
{
	//this function previously queried a table that may not even exist
	//nothign good can come of this, but it's called from a number of places that
	//need to be tracked down.
	return false;
}

// #############################################################################
// these aren't currently used, but they belong to the deprecated fetch_userinfo
// function, which is.  Remove them when that function is removed.
define('FETCH_USERINFO_AVATAR',     0x02);
define('FETCH_USERINFO_ADMIN',      0x10);
define('FETCH_USERINFO_SIGPIC',     0x20);
define('FETCH_USERINFO_ISFRIEND',   0x80);

/**
* Fetches an array containing info for the specified user, or false if user is not found
*
* Values for Option parameter:
* 1 - Nothing ...
* 2 - Get avatar
* 4 - No longer used
* 8 - Join the customprofilpic table to get the userid just to check if we have a picture
* 16 - Join the administrator table to get various admin options
* 32 - Join the sigpic table to get the userid just to check if we have a picture
* 64 - No longer used
* 128 - Is the logged in User a friend of this person?
* Therefore: Option = 6 means 'Get avatar' and 'Process online location'
* See fetch_userinfo() in the do=getinfo section of member.php if you are still confused
*
* @deprecated -- use the fetchUserinfo method of vB_User
* @param	integer	(ref) User ID
* @param	integer	Bitfield Option (see description)
*
* @return	array	The information for the requested user
*/
function fetch_userinfo(&$userid, $option = 0, $languageid = false, $nocache = false)
{
	$optionMap = array(
		'2' => 'avatar',
		'16' => 'admin',
		'32' => 'signpic',
		'128' => 'isfriend'
	);

	$options = array();
	foreach($optionMap as $bit => $value)
	{
		if ($option & $bit)
		{
			$options[] = $value;
		}
	}

	return vB_User::fetchUserinfo($userid, $options, $languageid, $nocache);
}


// #############################################################################
/**
* Strips away [quote] tags and their contents from the specified string
*
* @param	string	Text to be stripped of quote tags
*
* @return	string
*/
function strip_quotes($text)
{
	$lowertext = strtolower($text);

	// find all [quote tags
	$start_pos = array();
	$curpos = 0;
	do
	{
		$pos = strpos($lowertext, '[quote', $curpos);
		if ($pos !== false AND ($lowertext[$pos + 6] == '=' OR $lowertext[$pos + 6] == ']'))
		{
			$start_pos["$pos"] = 'start';
		}

		$curpos = $pos + 6;
	}
	while ($pos !== false);

	if (sizeof($start_pos) == 0)
	{
		return $text;
	}

	// find all [/quote] tags
	$end_pos = array();
	$curpos = 0;
	do
	{
		$pos = strpos($lowertext, '[/quote]', $curpos);
		if ($pos !== false)
		{
			$end_pos["$pos"] = 'end';
			$curpos = $pos + 8;
		}
	}
	while ($pos !== false);

	if (sizeof($end_pos) == 0)
	{
		return $text;
	}

	// merge them together and sort based on position in string
	$pos_list = $start_pos + $end_pos;
	ksort($pos_list);

	do
	{
		// build a stack that represents when a quote tag is opened
		// and add non-quote text to the new string
		$stack = array();
		$newtext = '';
		$substr_pos = 0;
		foreach ($pos_list AS $pos => $type)
		{
			$stacksize = sizeof($stack);
			if ($type == 'start')
			{
				// empty stack, so add from the last close tag or the beginning of the string
				if ($stacksize == 0)
				{
					$newtext .= substr($text, $substr_pos, $pos - $substr_pos);
				}
				array_push($stack, $pos);
			}
			else
			{
				// pop off the latest opened tag
				if ($stacksize)
				{
					array_pop($stack);
					$substr_pos = $pos + 8;
				}
			}
		}

		// add any trailing text
		$newtext .= substr($text, $substr_pos);

		// check to see if there's a stack remaining, remove those points
		// as key points, and repeat. Allows emulation of a non-greedy-type
		// recursion.
		if ($stack)
		{
			foreach ($stack AS $pos)
			{
				unset($pos_list["$pos"]);
			}
		}
	}
	while ($stack);

	return $newtext;
}

// #############################################################################
/**
* Strips away bbcode from a given string, leaving plain text
*
* @deprecated
* @see vB_String::stripBbcode()
* @param	string	Text to be stripped of bbcode tags
* @param	boolean	If true, strip away quote tags AND their contents
* @param	boolean	If true, use the fast-and-dirty method rather than the shiny and nice method
* @param	boolean	If true, display the url of the link in parenthesis after the link text
* @param	boolean	If true, strip away img/video tags and their contents
* @param	boolean	If true, keep [quote] tags. Useful for API.
*
* @return	string
*/
function strip_bbcode($message, $stripquotes = false, $fast_and_dirty = false, $showlinks = true, $stripimg = false, $keepquotetags = false)
{
	return vB_String::stripBbcode($message, $stripquotes, $fast_and_dirty, $showlinks, $stripimg, $keepquotetags);
}

// #############################################################################
/**
* Sets a cookie based on vBulletin environmental settings
*
* @param	string	Cookie name
* @param	mixed	Value to store in the cookie
* @param	boolean	If true, do not set an expiry date for the cookie
* @param	boolean	Allow secure cookies (SSL)
* @param	boolean	Set 'httponly' for cookies in supported browsers
*/
function vbsetcookie($name, $value = '', $permanent = true, $allowsecure = true, $httponly = false)
{
	if (defined('NOCOOKIES'))
	{
		return;
	}

	global $vbulletin;

	$vb5_config =& vB::getConfig();

	if ($permanent)
	{
		$expire = vB::getRequest()->getTimeNow() + 60 * 60 * 24 * 365;
	}
	else
	{
		$expire = 0;
	}

	// IE for Mac doesn't support httponly
	$httponly = (($httponly AND (is_browser('ie') AND is_browser('mac'))) ? false : $httponly);

	$options = vB::getDatastore()->get_value('options');

	// check for HTTPS -- Check the configured URL instead of the request URL - VBV-19495
	$secure = ($allowsecure AND strtolower(substr($options['frontendurl'], 0, 5)) == 'https');

	$name = COOKIE_PREFIX . $name;

	$filename = 'N/A';
	$linenum = 0;

	if (!headers_sent($filename, $linenum))
	{
		// consider showing an error message if they're not sent using above variables?
		if ($value === '' OR $value === false)
		{
			// this will attempt to unset the cookie at each directory up the path.
			// ie, path to file = /test/vb3/. These will be unset: /, /test, /test/, /test/vb3, /test/vb3/
			// This should hopefully prevent cookie conflicts when the cookie path is changed.

			if (!empty($_SERVER['PATH_INFO']) OR !empty($_ENV['PATH_INFO']))
			{
				$scriptpath = $_SERVER['PATH_INFO'] ? $_SERVER['PATH_INFO'] : $_ENV['PATH_INFO'];
			}
			else if ($_SERVER['REDIRECT_URL'] OR $_ENV['REDIRECT_URL'])
			{
				$scriptpath = $_SERVER['REDIRECT_URL'] ? $_SERVER['REDIRECT_URL'] : $_ENV['REDIRECT_URL'];
			}
			else
			{
				$scriptpath = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_ENV['PHP_SELF'];
			}

			$scriptpath = preg_replace(
				array(
					'#/[^/]+\.php$#i',
					'#/(' . preg_quote('admincp', '#') . '|' . preg_quote($vb5_config['Misc']['modcpdir'], '#') . ')(/|$)#i'
				),
				'',
				$scriptpath
			);

			$dirarray = explode('/', preg_replace('#/+$#', '', $scriptpath));

			$alldirs = '';
			$havepath = false;
			if (!defined('SKIP_AGGRESSIVE_LOGOUT'))
			{
				// sending this many headers has caused problems with a few
				// servers, especially with IIS. Defining SKIP_AGGRESSIVE_LOGOUT
				// reduces the number of cookie headers returned.
				foreach ($dirarray AS $thisdir)
				{
					$alldirs .= "$thisdir";

					if ($alldirs == $options['cookiepath'] OR "$alldirs/" == $options['cookiepath'])
					{
						$havepath = true;
					}

					if (!empty($thisdir))
					{
						// try unsetting without the / at the end
						exec_vbsetcookie($name, $value, $expire, $alldirs, $options['cookiedomain'], $secure, $httponly);
					}

					$alldirs .= "/";
					exec_vbsetcookie($name, $value, $expire, $alldirs, $options['cookiedomain'], $secure, $httponly);
				}
			}

			if ($havepath == false)
			{
				exec_vbsetcookie($name, $value, $expire, $options['cookiepath'], $options['cookiedomain'], $secure, $httponly);
			}
		}
		else
		{
			exec_vbsetcookie($name, $value, $expire, $options['cookiepath'], $options['cookiedomain'], $secure, $httponly);
		}
	}
}

// #############################################################################
/**
* Calls PHP's setcookie() or sends raw headers if 'httponly' is required.
* Should really only be called through vbsetcookie()
*
* @param	string	Name
* @param	string	Value
* @param	int		Expire
* @param	string	Path
* @param	string	Domain
* @param	boolean	Secure
* @param	boolean	HTTP only - see http://msdn.microsoft.com/workshop/author/dhtml/httponly_cookies.asp
*
* @return	boolean	True on success
*/
function exec_vbsetcookie($name, $value, $expires, $path = '', $domain = '', $secure = false, $httponly = false)
{
	if ($httponly AND $value)
	{
		// cookie names and values may not contain any of the characters listed
		foreach (array(",", ";", " ", "\t", "\r", "\n", "\013", "\014") AS $bad_char)
		{
			if (strpos($name, $bad_char) !== false OR strpos($value, $bad_char) !== false)
			{
				return false;
			}
		}

		// name and value
		$cookie = "Set-Cookie: $name=" . urlencode($value);

		// expiry
		$cookie .= ($expires > 0 ? '; expires=' . gmdate('D, d-M-Y H:i:s', $expires) . ' GMT' : '');

		// path
		$cookie .= ($path ? "; path=$path" : '');

		// domain
		$cookie .= ($domain ? "; domain=$domain" : '');

		// secure
		$cookie .= ($secure ? '; secure' : '');

		// httponly
		$cookie .= ($httponly ? '; HttpOnly' : '');

		header($cookie, false);
		return true;
	}
	else
	{
		return setcookie($name, $value, $expires, $path, $domain, $secure);
	}
}

// #############################################################################
/**
* Signs a string we intend to pass to the client but don't want them to alter
*
* @param	string	String to be signed
*
* @return	string	MD5 hash followed immediately by the string
*/
function sign_client_string($string, $extra_entropy = '')
{
	if (preg_match('#[\x00-\x1F\x80-\xFF]#s', $string))
	{
		$string = base64_encode($string);
		$prefix = 'B64:';
	}
	else
	{
		$prefix = '';
	}

	return $prefix . sha1($string . sha1(vB_Request_Web::$COOKIE_SALT) . $extra_entropy) . $string;
}

// #############################################################################
/**
* Verifies a string return from a client that it has been unaltered
*
* @param	string	String from the client to be verified
*
* @return	string|boolean	String without the verification hash or false on failure
*/
function verify_client_string($string, $extra_entropy = '')
{
	if (substr($string, 0, 4) == 'B64:')
	{
		$firstpart = substr($string, 4, 40);
		$return = substr($string, 44);
		$decode = true;
	}
	else
	{
		$firstpart = substr($string, 0, 40);
		$return = substr($string, 40);
		$decode = false;
	}

	if (sha1($return . sha1(vB_Request_Web::$COOKIE_SALT) . $extra_entropy) === $firstpart)
	{
		return ($decode ? base64_decode($return) : $return);
	}

	return false;
}

// #############################################################################
/**
* Verifies a security token is valid
*
* @param	string	Security token from the REQUEST data
* @param	string	Security token used in the hash
*
* @return	boolean	True if the hash matches and is within the correct TTL
*/
function verify_security_token($request_token, $user_token)
{
	global $vbulletin;

	// This is for backwards compatability before tokens had TIMENOW prefixed
	if (strpos($request_token, '-') === false)
	{
		return ($request_token === $user_token);
	}

	list($time, $token) = explode('-', $request_token);

	if ($token !== sha1($time . $user_token))
	{
		return false;
	}

	// A token is only valid for 3 hours
	if ($time <= TIMENOW - 10800)
	{
		$vbulletin->GPC['securitytoken'] = 'timeout';
		return false;
	}

	return true;
}

// #############################################################################
/**
* Ensures that the variables for a multi-page display are sane
*
* @param	integer	Total number of items to be displayed
* @param	integer	(ref) Current page number
* @param	integer	(ref) Desired number of results to show per-page
* @param	integer	Maximum allowable results to show per-page
* @param	integer	Default number of results to show per-page
*/
function sanitize_pageresults($numresults, &$page, &$perpage, $maxperpage = 20, $defaultperpage = 20)
{
	$perpage = fetch_perpage($perpage, $maxperpage, $defaultperpage);
	$numpages = ceil($numresults / $perpage);
	if ($numpages == 0)
	{
		$numpages = 1;
	}

	if ($page < 1)
	{
		$page = 1;
	}
	else if ($page > $numpages)
	{
		$page = $numpages;
	}
}

/**
* Returns the number of items to display on a page based on a desired value and
* constraints.
*
* If the desired value is not given use the default.  Under no circumstances allow
* a value greater than maxperpage.
*
* @param	integer	Desired number of results to show per-page
* @param	integer	Maximum allowable results to show per-page
* @param	integer	Default number of results to show per-page
* @return actual per page results
*/
function fetch_perpage($perpage, $maxperpage = 20, $defaultperpage = 20)
{
	$perpage = intval($perpage);
	if ($perpage < 1)
	{
		$perpage = $defaultperpage;
	}

	if ($perpage > $maxperpage)
	{
		$perpage = $maxperpage;
	}
	return $perpage;
}

// #############################################################################
/**
* Construct Phrase
*
* this function is actually just a wrapper for sprintf but makes identification of phrase code easier
* and will not error if there are no additional arguments. The first parameter is the phrase text, and
* the (unlimited number of) following parameters are the variables to be parsed into that phrase.
*
* @param	string	Text of the phrase
* @param	mixed	First variable to be inserted
* ..		..		..
* @param	mixed	Nth variable to be inserted
*
* @return	string	The parsed phrase
*/
function construct_phrase($phrasename, ...$args)
{
	$numargs = sizeof($args);

	// FAIL SAFE: check if only parameter is an array,
	// if so we should have called construct_phrase_from_array instead
	if ($numargs == 0 AND is_array($phrasename))
	{
		$args = $phrasename;
		$phrasename = array_shift($args);
	}
	// if this function was called with the phrase as the first argument, and an array
	// of parameters as the second
	else if ($numargs == 1 AND is_string($phrasename) AND is_array($args[0]))
	{
		$args = $args[0];
	}

	$phraseLib = vB_Library::instance('phrase');
	return $phraseLib->renderPhrase($phrasename, $args);
}

/**
* Construct Phrase from Array
*
* this function is actually just a wrapper for sprintf but makes identification of phrase code easier
* and will not error if there are no additional arguments. The first element of the array is the phrase text, and
* the (unlimited number of) following elements are the variables to be parsed into that phrase.
*
* @param	array	array containing phrase and arguments
*
* @return	string	The parsed phrase
*/
function construct_phrase_from_array($phrase_array)
{
	$phraseargs = $phrase_array;
	$phrasename = array_shift($phraseargs);

	$phraseLib = vB_Library::instance('phrase');
	return $phraseLib->renderPhrase($phrasename, $phraseargs);
}

// #############################################################################
/**
* Converts an array of error values to error strings by calling the fetch_error
* function.
*
* @param	Array(mixed) Errors to compile.  Values can either be a string, which is taken
* 	be be the error varname or it can be an array in which case it is viewed as
*		a list of parameters to pass to fetch_error
*
* @return	Array(string)	The array of compiled error messages.  Keys are preserved.
*/
function fetch_error_array($errors)
{
	$compiled_errors = array();

	if (!is_array($errors))
	{
		$compiled_errors[] = $errors;
		return $compiled_errors;
	}

	foreach ($errors as $key => $value)
	{
		if (is_string($value))
		{
			$compiled_errors[$key] = fetch_error($value);
		}
		else if (is_array($value))
		{
			$compiled_errors[$key] = call_user_func_array('fetch_error', $value);
		}
	}

	return $compiled_errors;
}

// #############################################################################
/**
* Fetches an error phrase from the database and inserts values for its embedded variables
*
* @param	string	Varname of error phrase
* @param	mixed	Value of 1st variable
* @param	mixed	Value of 2nd variable
* @param	mixed	Value of Nth variable
*
* @return	string	The parsed phrase text
*/
function fetch_error()
{
	$vbulletin = vB::get_registry();

	$args = func_get_args();

	// Allow an array of phrase and variables to be passed in as arg0 (for some internal functions)
	if (is_array($args[0]))
	{
		$args = $args[0];
	}

	if (isset($vbulletin->GPC) AND !empty($vbulletin->GPC['ajax']))
	{
		switch ($args[0])
		{
			case 'invalidid':
			case 'nopermission_loggedin':
			case 'forumpasswordmissing':
				$args[0] = $args[0] . '_ajax';
		}
	}

	// API only needs error phrase name and args.
	if (defined('VB_API') AND VB_API === true)
	{
		return $args;
	}

	$phraseAux = vB_Api::instanceInternal('phrase')->fetch(array($args[0]));
	$args[0] = $phraseAux[$args[0]];

	if (sizeof($args) > 1)
	{
		return call_user_func_array('construct_phrase', $args);
	}
	else
	{
		return $args[0];
	}
}

// #############################################################################
/**
* Halts execution and redirects to the specified URL invisibly
*
* @param	string	Destination URL
*/
function exec_header_redirect($url, $redirectcode = 302)
{
	$url = create_full_url($url);
	$url = str_replace('&amp;', '&', $url); // prevent possible oddity
	header("Location: $url", true, $redirectcode);

	//not sure we really want to be doing this any longer but need to investigate.
	global $vbulletin;
	$vbulletin->shutdown->shutdown();
	if (defined('NOSHUTDOWNFUNC'))
	{
		exec_shut_down();
	}
	exit;
}

// #############################################################################
/**
* Translates a relative URL to a fully-qualified URL. URLs not beginning with
* a / are assumed to be within the main vB-directory
*
* @param	string	Relative URL
* @param  string  Always use the bburl setting as the base path regardless of admin options.
* 	(Unless already an absolute path).  Primarily used in the archives where its currently
* 	hardcoded.
*
* @param	string	Fully-qualified URL
*/
function create_full_url($url = '', $force_bburl = false, $force_frontendurl = false)
{
	global $vbulletin;

	// enforces HTTP 1.1 compliance
	if (!preg_match('#^[a-z]+(?<!about|javascript|vbscript|data)://#i', $url))
	{
		if ($url AND '/' == $url[0])
		{
			$url = vB::getRequest()->getVbUrlWebroot() . $url;
		}
		else
		{
			$url = $vbulletin->input->fetch_relpath($url);
			if ($force_bburl)
			{
				$base = vB::getDatastore()->getOption('bburl') . "/";
			}
			elseif ($force_frontendurl)
			{
				$base = vB::getDatastore()->getOption('frontendurl') . "/";
			}
			//these areas depend on the redirection being done against the VB_URL_BASE_PATH path explicitly.
			else if (defined('VB_AREA') AND in_array(VB_AREA, array('Install', 'Upgrade', 'AdminCP', 'ModCP', 'Archive', 'tools')))
			{
				$base = VB_URL_BASE_PATH;
			}
			else
			{
				$base = $vbulletin->input->fetch_basepath();
			}

			if (strtolower(substr($base, 0, 4)) != 'http')
			{
				$base = vB::getRequest()->getVbUrlScheme() . $base;
			}
			$url = $base . ltrim($url, ':/\\');
		}
	}
	else
	{
		$url = $vbulletin->cleaner->xssCleanUrl($url);
	}

	// Collapse ../ and ./
	$url = normalize_path($url);

	return $url;
}


/**
 * Collapses ../ and ./ in a path.
 *
 * @param	string		The path to normalize
 * @return	string		The nromalized path
 */
function normalize_path($path)
{
	// Collapse ../
	$path = preg_replace('#\w+\/\.\.\/#', '', $path);

	// Collapse ./
	$path = preg_replace('#\/\.\/#', '/', $path);

	return $path;
}

// #############################################################################
/**
* Sets various time and date related variables according to visitor's preferences
*
* Sets $timediff, $datenow, $timenow, $copyrightyear
*/
function fetch_time_data()
{
	global $vbulletin, $timediff, $datenow, $timenow, $copyrightyear;

	$options = vB::getDatastore()->getValue('options');
	// preserve timzoneoffset for profile editing and proper event display
	$vbulletin->userinfo['tzoffset'] = $vbulletin->userinfo['timezoneoffset'];

	if ($vbulletin->userinfo['dstonoff'])
	{
		// DST is on, add an hour
		$vbulletin->userinfo['tzoffset']++;

		if (substr($vbulletin->userinfo['tzoffset'], 0, 1) != '-')
		{
			// recorrect so that it has + sign, if necessary
			$vbulletin->userinfo['tzoffset'] = '+' . $vbulletin->userinfo['tzoffset'];
		}
	}

	//this does nothing useful.  It used to be $vbulletin->options which was global.
	//removed all references to $vbulletin->options['hourdiff'] from the code.
	// some stuff for the gmdate bug
	//$options['hourdiff'] = (date('Z', vB::getRequest()->getTimeNow()) / 3600 - $vbulletin->userinfo['tzoffset']) * 3600;

	if ($vbulletin->userinfo['tzoffset'])
	{
		if ($vbulletin->userinfo['tzoffset'] > 0 AND strpos($vbulletin->userinfo['tzoffset'], '+') === false)
		{
			$vbulletin->userinfo['tzoffset'] = '+' . $vbulletin->userinfo['tzoffset'];
		}
		if (abs($vbulletin->userinfo['tzoffset']) == 1)
		{
			$timediff = ' ' . $vbulletin->userinfo['tzoffset'] . ' hour';
		}
		else
		{
			$timediff = ' ' . $vbulletin->userinfo['tzoffset'] . ' hours';
		}
	}
	else
	{
		$timediff = '';
	}

	$datenow       = vbdate($options['dateformat'], vB::getRequest()->getTimeNow());
	$timenow       = vbdate($options['timeformat'], vB::getRequest()->getTimeNow());
	$copyrightyear = vbdate('Y', vB::getRequest()->getTimeNow(), false, false);
}

// #############################################################################
/**
* Formats a UNIX timestamp into a human-readable string according to vBulletin prefs
*
* Note: Ifvbdate() is called with a date format other than than one in $vbulletin->options[],
* set $locale to false unless you dynamically set the date() and strftime() formats in the vbdate() call.
*
* @param	string	Date format string (same syntax as PHP's date() function)
* @param	integer	Unix time stamp. Note, if this value is 0, it will use the current time from vB::getRequest()->getTimeNow()
* @param	boolean	If true, attempt to show strings like "Yesterday, 12pm" instead of full date string
* @param	boolean	If true, and user has a language locale, use strftime() to generate language specific dates
* @param	boolean	If true, don't adjust time to user's adjusted time .. (think gmdate instead of date!)
* @param	boolean	If true, uses gmstrftime() and gmdate() instead of strftime() and date()
* @param array    If set, use specified info instead of $vbulletin->userinfo
*
* @return	string	Formatted date string
*/
function vbdate($format, $timestamp = 0, $doyestoday = false, $locale = true, $adjust = true, $gmdate = false, $userinfo = '')
{
	global $vbulletin, $vbphrase;

	if (!$timestamp)
	{
		$timestamp = vB::getRequest()->getTimeNow();
	}

	$uselocale = false;
	$options = vB::getDatastore()->getValue('options');
	if (defined('VB_API') AND VB_API === true)
	{
		$doyestoday = false;
	}

	if (!is_array($userinfo) OR empty($userinfo))
	{
		$userinfo = vB::getCurrentSession()->fetch_userinfo();
	}

	if ($userinfo['lang_locale'])
	{
		$uselocale = true;
		$currentlocale = setlocale(LC_TIME, 0);
		setlocale(LC_TIME, $userinfo['lang_locale']);
		if (substr($userinfo['lang_locale'], 0, 5) != 'tr_TR')
		{
			setlocale(LC_CTYPE, $userinfo['lang_locale']);
		}
	}
	if ($userinfo['dstonoff'] OR ($userinfo['dstauto'] AND $options['dstonoff']))
	{
		// DST is on, add an hour
		$userinfo['timezoneoffset']++;
		if (substr($userinfo['timezoneoffset'], 0, 1) != '-')
		{
			// recorrect so that it has a + sign, if necessary
			$userinfo['timezoneoffset'] = '+' . $userinfo['timezoneoffset'];
		}
	}

	$hourdiff = (date('Z', vB::getRequest()->getTimeNow()) / 3600 - $userinfo['timezoneoffset']) * 3600;

	if ($uselocale AND $locale AND !(defined('VB_API') AND VB_API === true))
	{
		if ($gmdate)
		{
			$datefunc = 'gmstrftime';
		}
		else
		{
			$datefunc = 'strftime';
		}
	}
	else
	{
		if ($gmdate)
		{
			$datefunc = 'gmdate';
		}
		else
		{
			$datefunc = 'date';
		}
	}
	if (!$adjust)
	{
		$hourdiff = 0;
	}
	$timestamp_adjusted = max(0, $timestamp - $hourdiff);

	/*
		Special handling for some formats we only allow internally
	 */
	$returnEarly = null;
	if ($format === 'U' OR $format === '%s')
	{
		/*
			date('U') or strftime('%s') is basically shorthand for time(),
			except if a timestamp is provided it should return that.
			It seems we use the U format to perform TZ adjustments on the
			timestamp to do some internal calculations/comparisons.
			However, the old logic would try to swap to strftime('U') which
			would not work. Ideally we could pivot to strftime('%s') in that
			case, but on certain OSes (Windows), strftime('%s') does not work
			at all while on others strftime('%s') is bugged
			(https://stackoverflow.com/a/55503017).
			As such this is special logic to basically funnel the template
			usage of 'U' to the expected old behavior while fixing issues
			that this had when language locale overrides were in effect
			(which just broke these calculations previously).
		 */

		if ($gmdate)
		{
			$datefunc = 'gmdate';
		}
		else
		{
			$datefunc = 'date';
		}

		$returnEarly = $datefunc('U', $timestamp_adjusted);
	}
	else if ($format == 'r')
	{
		// This is used by debug_info template
		// The strftime equivalent to r would be
		// '%a, %d %b %Y %T %z' but %z support is fishy for Windows
		// Force date.
		$returnEarly = date('r', $timestamp_adjusted);
	}

	if (!is_null($returnEarly))
	{
		// undo any locale changes before returning early.
		setlocale(LC_TIME, $currentlocale);
		if (substr($currentlocale, 0, 5) != 'tr_TR')
		{
			setlocale(LC_CTYPE, $currentlocale);
		}

		return $returnEarly;
	}

	// Convert the current date (or strftime, we don't know) format to the proper one
	// for $datefunc.
	$dateUtil = new vB_Utility_Date();
	$useFormat = $dateUtil->convertFormat($datefunc, $format);

	if ($format == $options['dateformat'] AND $doyestoday AND $options['yestoday'])
	{
		if ($options['yestoday'] == 1)
		{
			if (!defined('TODAYDATE'))
			{
				define ('TODAYDATE', vbdate('n-j-Y', vB::getRequest()->getTimeNow(), false, false));
				define ('YESTDATE', vbdate('n-j-Y', vB::getRequest()->getTimeNow() - 86400, false, false));
				define ('TOMDATE', vbdate('n-j-Y', vB::getRequest()->getTimeNow() + 86400, false, false));
			}

			$datetest = @date('n-j-Y', $timestamp - $hourdiff);

			if ($datetest == TODAYDATE)
			{
				$returndate = $vbphrase['today'];
			}
			else if ($datetest == YESTDATE)
			{
				$returndate = $vbphrase['yesterday'];
			}
			else
			{
				$returndate = $datefunc($useFormat, $timestamp_adjusted);
			}
		}
		else
		{
			$timediff = vB::getRequest()->getTimeNow() - $timestamp;

			if ($timediff >= 0)
			{
				if ($timediff < 120)
				{
					$returndate = $vbphrase['1_minute_ago'];
				}
				else if ($timediff < 3600)
				{
					$returndate = construct_phrase($vbphrase['x_minutes_ago'], intval($timediff / 60));
				}
				else if ($timediff < 7200)
				{
					$returndate = $vbphrase['1_hour_ago'];
				}
				else if ($timediff < 86400)
				{
					$returndate = construct_phrase($vbphrase['x_hours_ago'], intval($timediff / 3600));
				}
				else if ($timediff < 172800)
				{
					$returndate = $vbphrase['1_day_ago'];
				}
				else if ($timediff < 604800)
				{
					$returndate = construct_phrase($vbphrase['x_days_ago'], intval($timediff / 86400));
				}
				else if ($timediff < 1209600)
				{
					$returndate = $vbphrase['1_week_ago'];
				}
				else if ($timediff < 3024000)
				{
					$returndate = construct_phrase($vbphrase['x_weeks_ago'], intval($timediff / 604900));
				}
				else
				{
					$returndate = $datefunc($useFormat, $timestamp_adjusted);
				}
			}
			else
			{
				$returndate = $datefunc($useFormat, $timestamp_adjusted);
			}
		}
	}
	else
	{
		$returndate = $datefunc($useFormat, $timestamp_adjusted);
	}

	if (!empty($userinfo['lang_locale']))
	{
		setlocale(LC_TIME, $currentlocale);
		if (substr($currentlocale, 0, 5) != 'tr_TR')
		{
			setlocale(LC_CTYPE, $currentlocale);
		}
	}
	return $returndate;
}

// #############################################################################
/**
* Returns a string where HTML entities have been converted back to their original characters
*
* @deprecated
* @see vB_String::unHtmlSpecialChars()
* @param	string	String to be parsed
* @param	boolean	Convert unicode characters back from HTML entities?
*
* @return	string
*/
function unhtmlspecialchars($text, $doUniCode = false)
{
	return vB_String::unHtmlSpecialChars($text, $doUniCode);
}

// #############################################################################
/** PHP 5.5
 * Callback function for preg_replace callbacks of convert_int_to_utf8()
 *
 * @return bool
 */
function convert_int_to_utf8_callback($matches)
{
	return vB_String::convertIntToUtf8($matches[1]);
}

// #############################################################################
/**
 * Checks if PCRE supports unicode
 *
 * @return bool
 */
function is_pcre_unicode()
{
	static $enabled;

	if (NULL !== $enabled)
	{
		return $enabled;
	}

	return $enabled = @preg_match('#\pN#u', '1');
}

// #############################################################################
/**
* Converts Unicode entities of the format %uHHHH where each H is a hexadecimal
* character to &#DDDD; or the appropriate UTF-8 character based on current charset.
*
* @param	Mixed		array or text
*
* @return	string	Decoded text
*/
function convert_urlencoded_unicode($text)
{
	if (is_array($text))
	{
		foreach ($text AS $key => $value)
		{
			$text["$key"] = convert_urlencoded_unicode($value);
		}
		return $text;
	}

	if (!($charset = vB_Template_Runtime::fetchStyleVar('charset')))
	{
		$session = vB::getCurrentSession();
		if ($session)
		{
			$userInfo = $session->fetch_userinfo();
			$charset = $userInfo['lang_charset'];
		}
		else
		{
			$charset = 'utf-8';
		}
	}

	$return = preg_replace_callback('#%u([0-9A-F]{1,4})#i',
		function($matches) use ($charset)
		{
			return vB_String::convertUnicodeCharToCharset(hexdec($matches[1]), $charset);
		},
		$text
	);

	$lower_charset = strtolower($charset);

	if ($lower_charset != 'utf-8' AND function_exists('html_entity_decode'))
	{
		// this converts certain &#123; entities to their actual character
		// set values; don't do this if using UTF-8 as it's already done above.
		// note: we don't want to convert &gt;, etc as that undoes the effects of STR_NOHTML
		$return = preg_replace('#&([a-z]+);#i', '&amp;$1;', $return);

		if ($lower_charset == 'windows-1251')
		{
			// there's a bug in PHP5 html_entity_decode that decodes some entities that
			// it shouldn't. So double encode them to ensure they don't get decoded.
			$return = preg_replace('/&#(128|129|1[3-9][0-9]|2[0-4][0-9]|25[0-5]);/', '&amp;#$1;', $return);
		}

		$return = @html_entity_decode($return, ENT_NOQUOTES, $charset);
	}

	return $return;
}

/**
* Poor man's urlencode that only encodes specific characters and preserves unicode.
* Use urldecode() to decode.
*
* @param	string	String to encode
* @return	string	Encoded string
*/
function urlencode_uni($str)
{
	return preg_replace_callback(
		'`([\s/\\\?:@=+$,<>\%"\'\.\r\n\t\x00-\x1f\x7f]|(?(?<!&)#|#(?![0-9]+;))|&(?!#[0-9]+;)|(?<!&#\d|&#\d{2}|&#\d{3}|&#\d{4}|&#\d{5});)`',
		function($matches)
		{
			return urlencode($matches[1]);
		},
		$str
	);
}

/**
 * Converts a string to utf8
 *
 * @deprecated
 * @see vB_String::toUtf8()
 * @param	string	The variable to clean
 * @param	string	The source charset
 * @param	bool	Whether to strip invalid utf8 if we couldn't convert
 * @return	string	The reencoded string
 */
function to_utf8($in, $charset = false, $strip = true)
{
	return vB_String::toUtf8($in, $charset, $strip);
}

/**
 * Converts a string from one character encoding to another.
 * If the target encoding is not specified then it will be resolved from the current
 * language settings.
 *
 * @deprecated
 * @see vB_String::toCharset()
 * @param	string	The string to convert
 * @param	string	The source encoding
 * @return	string	The target encoding
 */
function to_charset($in, $in_encoding, $target_encoding = false)
{
	return vB_String::toCharset($in, $in_encoding, $target_encoding);
}

/**
 * Strips NCRs from a string.
 *
 * @deprecated
 * @see vB_String::stripNcrs()
 * @param	string	The string to strip from
 * @return	string	The result
 */
function stripncrs($str)
{
	return vB_String::stripNcrs($str);
}

/**
 * Converts a UTF-8 string into unicode NCR equivelants.
 *
 * @deprecated
 * @param	string	String to encode
 * @param	bool	Only ncrencode unicode bytes
 * @param	bool	If true and $skip_ascii is true, it will skip windows-1252 extended chars
 * @return	string	Encoded string
 */
function ncrencode($str, $skip_ascii = false, $skip_win = false)
{
	return vB_String::ncrEncode($str, $skip_ascii, $skip_win);
}

/**
 * NCR encodes matches from a preg_replace.
 * Single byte characters are preserved.
 *
 * @param	string	The character to encode
 * @return	string	The encoded character
 */
function ncrencode_matches($matches, $skip_ascii = false, $skip_win = false)
{
	$ord = ord_uni($matches[0]);

	if ($skip_win)
	{
		$start = 254;
	}
	else
	{
		$start = 128;
	}

	if ($skip_ascii AND $ord < $start)
	{
		return $matches[0];
	}

	return '&#' . ord_uni($matches[0]) . ';';
}

/**
 * Gets the Unicode Ordinal for a UTF-8 character.
 *
 * @param	string	Character to convert
 * @return	bool|int		Ordinal value or false if invalid
 */
function ord_uni($chr)
{
	// Valid lengths and first byte ranges
	static $check_len = array(
		1 => array(0, 127),
		2 => array(192, 223),
		3 => array(224, 239),
		4 => array(240, 247),
		5 => array(248, 251),
		6 => array(252, 253)
	);

	// Get length
	$blen = strlen($chr);

	// Get single byte ordinals
	$b = array();
	for ($i = 0; $i < $blen; $i++)
	{
		$b[$i] = ord($chr[$i]);
	}

	// Check expected length
	foreach ($check_len AS $len => $range)
	{
		if (($b[0] >= $range[0]) AND ($b[0] <= $range[1]))
		{
			$elen = $len;
		}
	}

	// If no range found, or chr is too short then it's invalid
	if (!isset($elen) OR ($blen < $elen))
	{
		return false;
	}

	// Normalise based on octet-sequence length
	switch ($elen)
	{
		case (1):
			return $b[0];
		case (2):
			return ($b[0] - 192) * 64 + ($b[1] - 128);
		case (3):
			return ($b[0] - 224) * 4096 + ($b[1] - 128) * 64 + ($b[2] - 128);
		case (4):
			return ($b[0] - 240) * 262144 + ($b[1] - 128) * 4096 + ($b[2] - 128) * 64 + ($b[3] - 128);
		case (5):
			return ($b[0] - 248) * 16777216 + ($b[1] - 128) * 262144 + ($b[2] - 128) * 4096 + ($b[3] - 128) * 64 + ($b[4] - 128);
		case (6):
			return ($b[0] - 252) * 1073741824 + ($b[1] - 128) * 16777216 + ($b[2] - 128) * 262144 + ($b[3] - 128) * 4096 + ($b[4] - 128) * 64 + ($b[5] - 128);
	}

	return false;
}

// #############################################################################

/**
* Sends no-cache HTTP headers
*
* @param	boolean	If true, send content-type header
*/
function exec_nocache_headers($sendcontent = true)
{
	global $vbulletin;
	static $sentheaders;

	if (!$sentheaders)
	{
		@header("Expires: 0"); // Date in the past
		#@header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); // always modified
		#@header("Cache-Control: no-store, no-cache, must-revalidate"); // HTTP/1.1
		@header("Cache-Control: private, post-check=0, pre-check=0, max-age=0", false);
		@header("Pragma: no-cache"); // HTTP/1.0
		if ($sendcontent)
		{
			@header('Content-Type: text/html' . iif($vbulletin->userinfo['lang_charset'] != '', '; charset=' . $vbulletin->userinfo['lang_charset']));
		}
	}

	$sentheaders = true;
}

// #############################################################################
/**
* Converts a bitfield into an array of 1 / 0 values based on the array describing the resulting fields
*
* @param	integer	(ref) Bitfield
* @param	array	Array containing field definitions - ['canx' => 1, 'cany' => 2, 'canz' => 4] etc
*
* @return	array
*/
function convert_bits_to_array(&$bitfield, $_FIELDNAMES)
{
	$bitfield = intval($bitfield);
	$arry = [];
	foreach ($_FIELDNAMES AS $field => $bitvalue)
	{
		$arry[$field] = (($bitfield & $bitvalue) ? 1 : 0);
	}
	return $arry;
}

/**
 * takes an array and returns the bitwise value
 * If a bit isn't found in the source array, assume that should be unset
 * in the returned bitfield (explicitly allow this).
 */
function convert_array_to_bits(&$arry, $_FIELDNAMES, $unset = 0)
{
	$bits = 0;
	foreach($_FIELDNAMES AS $fieldname => $bitvalue)
	{
		if (($arry[$fieldname] ?? 0) == 1)
		{
			$bits += $bitvalue;
		}
		if ($unset)
		{
			unset($arry[$fieldname]);
		}
	}
	return $bits;
}

// #############################################################################
/**
* Returns the full set of permissions for the specified user (called by global or init)
*
* @param	array	(ref) User info array
* @param	boolean	If true, returns combined usergroup permissions, individual forum permissions, individual calendar permissions and attachment permissions
* @param boolean        Reset the accesscache array for permissions following access mask update. Only allows one reset.
*
* @return	array	Permissions component of user info array
* @deprecated Use the usercontext object for permission checking.
*/
function cache_permissions(&$user, $getforumpermissions = true, $resetaccess = false)
{
	global $vbulletin;

	$options = vB::getDatastore()->getValue('options');
	// these are the arrays created by this function

	// set the usergroupid of the user's primary usergroup
	$primarygroupid = $user['usergroupid'];

	if ($primarygroupid == 0)
	{
		// set a default usergroupid if none is set
		$primarygroupid = 1;
	}

	$primarygroup = $vbulletin->usergroupcache[$primarygroupid];

	// initialise $membergroups - make an array of the usergroups to which this user belongs
	$membergroupids = fetch_membergroupids_array($user);

	// build usergroup permissions
	if (sizeof($membergroupids) == 1 OR !($primarygroup['genericoptions'] & $vbulletin->bf_ugp_genericoptions['allowmembergroups']))
	{
		// if primary usergroup doesn't allow member groups then get rid of them!
		$membergroupids = [$primarygroupid];

		// just return the permissions for the user's primary group (user is only a member of a single group)
		$user['permissions'] = $primarygroup;
	}
	else
	{
		// initialise fields to 0
		foreach ($vbulletin->bf_ugp AS $dbfield => $permfields)
		{
			$user['permissions'][$dbfield] = 0;
		}

		// return the merged array of all user's membergroup permissions (user has additional member groups)
		foreach ($membergroupids AS $usergroupid)
		{
			$currentusergroup = $vbulletin->usergroupcache[$usergroupid];

			foreach ($vbulletin->bf_ugp AS $dbfield => $permfields)
			{
				//'createpermissions is new in vB5 and won't be available.
				if ($dbfield != 'createpermissions')
				{
					$user['permissions'][$dbfield] |= $currentusergroup[$dbfield];
				}
			}

			$intperms = [];
			foreach ($vbulletin->bf_misc_intperms AS $dbfield => $precedence)
			{
				// put in some logic to handle $precedence
				if (!isset($intperms[$dbfield]))
				{
					$intperms[$dbfield] = $currentusergroup[$dbfield];
				}
				else if (!$precedence)
				{
					if ($currentusergroup[$dbfield] > $intperms[$dbfield])
					{
						$intperms[$dbfield] = $currentusergroup[$dbfield];
					}
				}
				// Set value to 0 as it overrides all
				else if ($currentusergroup[$dbfield] == 0 OR (isset($intperms[$dbfield]) AND $intperms[$dbfield] == 0))
				{
					$intperms[$dbfield] = 0;
				}
				else if ($currentusergroup[$dbfield] > $intperms[$dbfield])
				{
					$intperms[$dbfield] = $currentusergroup[$dbfield];
				}
			}
		}
		$user['permissions'] = array_merge($primarygroup, $user['permissions'], $intperms);
	}

	if (!empty($user['infractiongroupids']))
	{
		$infractiongroupids = explode(',', str_replace(' ', '', $user['infractiongroupids']));
	}
	else
	{
		$infractiongroupids = [];
	}

	foreach ($infractiongroupids AS $usergroupid)
	{
		foreach ($vbulletin->bf_ugp AS $dbfield => $permfields)
		{
			if(isset($vbulletin->usergroupcache[$usergroupid][$dbfield]))
			{
				$user['permissions'][$dbfield] &= $vbulletin->usergroupcache[$usergroupid][$dbfield];
			}
		}

		foreach ($vbulletin->bf_misc_intperms AS $dbfield => $precedence)
		{
			if (!$precedence)
			{
				if ($vbulletin->usergroupcache["$usergroupid"]["$dbfield"] < $user['permissions']["$dbfield"])
				{
					$user['permissions']["$dbfield"] = $vbulletin->usergroupcache["$usergroupid"]["$dbfield"];
				}
			}
			else if ($vbulletin->usergroupcache["$usergroupid"]["$dbfield"] < $user['permissions']["$dbfield"] AND $vbulletin->usergroupcache["$usergroupid"]["$dbfield"] != 0)
			{
				$user['permissions']["$dbfield"] = $vbulletin->usergroupcache["$usergroupid"]["$dbfield"];
			}
		}
	}

	if (defined('SKIP_SESSIONCREATE') AND $user['userid'] == $vbulletin->userinfo['userid'] AND !($user['permissions']['forumpermissions'] & $vbulletin->bf_ugp_forumpermissions['canview']))
	{
		// grant canview for usergroup if session skipping is defined.
		$user['permissions']['forumpermissions'] += $vbulletin->bf_ugp_forumpermissions['canview'];
	}

	// if we do not need to grab the forum/calendar permissions
	// then just return what we have so far
	if ($getforumpermissions == false)
	{
		return $user['permissions'];
	}

	if (!isset($user['channelpermissions']) OR !is_array($user['channelpermissions']))
	{
		$user['channelpermissions'] = array();
	}

	$channels = vB_Cache::instance(vB_Cache::CACHE_STD)->read('vB_ChannelStructure');
	if (empty($channels))
	{
		$channels = vB_Api::instanceInternal('search')->getChannels(true);

		// remove unrequired info to reduce cache size
		$requiredFields = array('nodeid', 'title');
		foreach ($channels as $nodeid => $channel)
		{
			$newValue = array();
			foreach($requiredFields AS $field)
			{
				if(isset($channel[$field]))
				{
					$newValue[$field] = $channel[$field];
				}
			}
			$channels[$nodeid] = $newValue;
		}

		vB_Cache::instance(vB_Cache::CACHE_STD)->write('vB_ChannelStructure', $channels, 1440, 'vB_ChannelStructure_chg');
	}

	foreach ($channels AS $nodeid => $channel)
	{
		if (!isset($user['channelpermissions']["$nodeid"]))
		{
			$user['channelpermissions']["$nodeid"] = 0;
		}
	}

	return $user['permissions'];
}

// #############################################################################
/**
* Returns permissions for given forum and user
*
* @param	integer	Forum ID
* @param	integer	User ID
* @param	array	User info array
*
* @return	mixed
*/
function fetch_permissions($forumid = 0, $userid = -1, $userinfo = false)
{
	// gets permissions, depending on given userid and forumid
	global $vbulletin, $usercache, $permscache;

	$userid = intval($userid);
	if ($userid == -1)
	{
		$userid = $vbulletin->userinfo['userid'];
		$usergroupid = $vbulletin->userinfo['usergroupid'];
	}

	if ($userid == $vbulletin->userinfo['userid'])
	{
		// we are getting permissions for $vbulletin->userinfo
		// so return permissions built in querypermissions
		if ($forumid)
		{
			return $vbulletin->userinfo['forumpermissions']["$forumid"];
		}
		else
		{
			return $vbulletin->userinfo['permissions'];
		}
	}
	else
	{
	// we are getting permissions for another user...
		if (!is_array($userinfo))
		{
			return 0;
		}
		if ($forumid)
		{
			cache_permissions($userinfo);
			return $userinfo['forumpermissions']["$forumid"];
		}
		else
		{
			return cache_permissions($userinfo, false);
		}
	}

}

// #############################################################################
/**
* Returns whether or not the given user can perform a specific moderation action in the specified forum
*
* @param	integer	Forum ID
* @param	string	If you want to check a particular moderation permission, name it here
* @param	integer	User ID
* @param	string	Comma separated list of usergroups to which the user belongs.  We don't need this, but legacy code passes it.
*
* @return	boolean
*/
function can_moderate($forumid = 0, $do = '', $userid = -1, $usergroupids = '')
{
	if ($userid == -1)
	{
		$userid = vB::getCurrentSession()->get('userid');
	}

	$userContext = vB::getUserContext($userid);

	if (empty($forumid))
	{
		//if we aren't a moderator this should always be false
		//if we are a moderator and we haven't requested a specific permission
		//this is true.
		$ismod = $userContext->isModerator();
		if (!$do OR !$ismod)
		{
			return $ismod;
		}

		return $userContext->hasModeratorPermission($do);
	}

	return $userContext->hasModeratorPermission($do, $forumid);
}

// #############################################################################
/**
* Returns whether or not vBulletin is running in demo mode
*
* if DEMO_MODE is defined and set to true in config.php this function will return false,
* the main purpose of which is to disable parsing of stuff that is undesirable for a
* board running with a publicly accessible admin control panel
*
* @return	boolean
*/
function is_demo_mode()
{
	return (defined('DEMO_MODE') AND DEMO_MODE == true) ? true : false;
}

// #############################################################################
/**
* Browser detection system - returns whether or not the visiting browser is the one specified
*
* @param	string	Browser name (opera, ie, mozilla, firebord, firefox... etc. - see $is array)
* @param	float	Minimum acceptable version for true result (optional)
*
* @return	boolean
*/
function is_browser($browser, $version = 0)
{
	static $is;
	if (!is_array($is))
	{
		$useragent = strtolower(vB::getRequest()->getUserAgent()); //strtolower($_SERVER['HTTP_USER_AGENT']);
		$is = array(
			'opera'     => 0,
			'ie'        => 0,
			'mozilla'   => 0,
			'firebird'  => 0,
			'firefox'   => 0,
			'camino'    => 0,
			'konqueror' => 0,
			'safari'    => 0,
			'webkit'    => 0,
			'webtv'     => 0,
			'netscape'  => 0,
			'mac'       => 0
		);

		// detect opera
			# Opera/7.11 (Windows NT 5.1; U) [en]
			# Mozilla/4.0 (compatible; MSIE 6.0; MSIE 5.5; Windows NT 5.0) Opera 7.02 Bork-edition [en]
			# Mozilla/4.0 (compatible; MSIE 6.0; MSIE 5.5; Windows NT 4.0) Opera 7.0 [en]
			# Mozilla/4.0 (compatible; MSIE 5.0; Windows 2000) Opera 6.0 [en]
			# Mozilla/4.0 (compatible; MSIE 5.0; Mac_PowerPC) Opera 5.0 [en]
		if (strpos($useragent, 'opera') !== false)
		{
			preg_match('#opera(/| )([0-9\.]+)#', $useragent, $regs);
			$is['opera'] = $regs[2];
		}

		// detect internet explorer
			# Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; Q312461)
			# Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.0.3705)
			# Mozilla/4.0 (compatible; MSIE 5.22; Mac_PowerPC)
			# Mozilla/4.0 (compatible; MSIE 5.0; Mac_PowerPC; e504460WanadooNL)
		if (strpos($useragent, 'msie ') !== false AND !$is['opera'])
		{
			preg_match('#msie ([0-9\.]+)#', $useragent, $regs);
			$is['ie'] = $regs[1];
		}

		// detect macintosh
		if (strpos($useragent, 'mac') !== false)
		{
			$is['mac'] = 1;
		}

		// detect safari
			# Mozilla/5.0 (Macintosh; U; PPC Mac OS X; en-us) AppleWebKit/74 (KHTML, like Gecko) Safari/74
			# Mozilla/5.0 (Macintosh; U; PPC Mac OS X; en) AppleWebKit/51 (like Gecko) Safari/51
			# Mozilla/5.0 (Windows; U; Windows NT 6.0; en) AppleWebKit/522.11.3 (KHTML, like Gecko) Version/3.0 Safari/522.11.3
			# Mozilla/5.0 (iPhone; U; CPU like Mac OS X; en) AppleWebKit/420+ (KHTML, like Gecko) Version/3.0 Mobile/1C28 Safari/419.3
			# Mozilla/5.0 (iPod; U; CPU like Mac OS X; en) AppleWebKit/420.1 (KHTML, like Gecko) Version/3.0 Mobile/3A100a Safari/419.3
		if (strpos($useragent, 'applewebkit') !== false)
		{
			preg_match('#applewebkit/([0-9\.]+)#', $useragent, $regs);
			$is['webkit'] = $regs[1];

			if (strpos($useragent, 'safari') !== false)
			{
				preg_match('#safari/([0-9\.]+)#', $useragent, $regs);
				$is['safari'] = $regs[1];
			}
		}

		// detect konqueror
			# Mozilla/5.0 (compatible; Konqueror/3.1; Linux; X11; i686)
			# Mozilla/5.0 (compatible; Konqueror/3.1; Linux 2.4.19-32mdkenterprise; X11; i686; ar, en_US)
			# Mozilla/5.0 (compatible; Konqueror/2.1.1; X11)
		if (strpos($useragent, 'konqueror') !== false)
		{
			preg_match('#konqueror/([0-9\.-]+)#', $useragent, $regs);
			$is['konqueror'] = $regs[1];
		}

		// detect mozilla
			# Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.4b) Gecko/20030504 Mozilla
			# Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.2a) Gecko/20020910
			# Mozilla/5.0 (X11; U; Linux 2.4.3-20mdk i586; en-US; rv:0.9.1) Gecko/20010611
		if (strpos($useragent, 'gecko') !== false AND !$is['safari'] AND !$is['konqueror'])
		{
			// See bug #26926, this is for Gecko based products without a build
			$is['mozilla'] = 20090105;
			if (preg_match('#gecko/(\d+)#', $useragent, $regs))
			{
				$is['mozilla'] = $regs[1];
			}

			// detect firebird / firefox
				# Mozilla/5.0 (Windows; U; WinNT4.0; en-US; rv:1.3a) Gecko/20021207 Phoenix/0.5
				# Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.4b) Gecko/20030516 Mozilla Firebird/0.6
				# Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.4a) Gecko/20030423 Firebird Browser/0.6
				# Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.6) Gecko/20040206 Firefox/0.8
			if (strpos($useragent, 'firefox') !== false OR strpos($useragent, 'firebird') !== false OR strpos($useragent, 'phoenix') !== false)
			{
				preg_match('#(phoenix|firebird|firefox)( browser)?/([0-9\.]+)#', $useragent, $regs);
				$is['firebird'] = $regs[3];

				if ($regs[1] == 'firefox')
				{
					$is['firefox'] = $regs[3];
				}
			}

			// detect camino
				# Mozilla/5.0 (Macintosh; U; PPC Mac OS X; en-US; rv:1.0.1) Gecko/20021104 Chimera/0.6
			if (strpos($useragent, 'chimera') !== false OR strpos($useragent, 'camino') !== false)
			{
				preg_match('#(chimera|camino)/([0-9\.]+)#', $useragent, $regs);
				$is['camino'] = $regs[2];
			}
		}

		// detect web tv
		if (strpos($useragent, 'webtv') !== false)
		{
			preg_match('#webtv/([0-9\.]+)#', $useragent, $regs);
			$is['webtv'] = $regs[1];
		}

		// detect pre-gecko netscape
		if (preg_match('#mozilla/([1-4]{1})\.([0-9]{2}|[1-8]{1})#', $useragent, $regs))
		{
			$is['netscape'] = "$regs[1].$regs[2]";
		}
	}

	// sanitize the incoming browser name
	$browser = strtolower($browser);
	if (substr($browser, 0, 3) == 'is_')
	{
		$browser = substr($browser, 3);
	}

	// return the version number of the detected browser if it is the same as $browser
	if ($is["$browser"])
	{
		// $version was specified - only return version number if detected version is >= to specified $version
		if ($version)
		{
			if ($is["$browser"] >= $version)
			{
				return $is["$browser"];
			}
		}
		else
		{
			return $is["$browser"];
		}
	}

	// if we got this far, we are not the specified browser, or the version number is too low
	return 0;
}

// #############################################################################
/**
* Check webserver's make and model
*
* @param	string	Browser name (apache, iis, samber, nginx... etc. - see $is array)
* @param	float	Minimum acceptable version for true result (optional)
*
* @return	boolean
*/
function is_server($server_name, $version = 0)
{
	static $server;

	// Resolve server
	if (!is_array($server))
	{
		$server_name = preg_quote(strtolower($server_name), '#');
		$server = strtolower($_SERVER['SERVER_SOFTWARE']);
		$matches = array();

		if (preg_match("#(.*)(?:/| )([0-9\.]*)#i", $server, $matches))
		{
			$server = array('name' => $matches[1]);
			$server['version'] = (isset($matches[2]) AND $matches[2]) ? $matches[2] : true;
		}
	}

	if (strpos($server['name'], $server_name))
	{
		if (!$version OR (true === $server['version']) OR ($server['version'] >= $version))
		{
			return true;
		}
	}

	return false;
}

// #############################################################################
/**
* Sets up the Fakey stylevars
*
* @param	array	(ref) Style info array
* @param	array	User info array
*/
function fetch_stylevars($style, $userinfo)
{
	// PLEASE keep this function in sync with vB5_Template_Stylevar::fetchStyleVars()
	// in terms of setting fake/pseudo stylevars

	// get text direction, left/right, and pos/neg values
	if (is_array($userinfo['lang_options']))
	{
		$ltr = (bool) $userinfo['lang_options']['direction'];
		$dirmark = (bool) $userinfo['lang_options']['dirmark'];
	}
	else
	{
		$bitfields = vB::getDatastore()->getValue('bf_misc_languageoptions');
		$ltr = (bool) ($userinfo['lang_options'] & $bitfields['direction']);
		$dirmark = (bool) ($userinfo['lang_options'] & $bitfields['dirmark']);
	}

	set_stylevar_ltr($ltr);

	if ($dirmark)
	{
		vB_Template_Runtime::addStyleVar('dirmark', $ltr ? '&lrm;' : '&rlm;');
	}

	// get the 'lang' attribute for <html> tags
	vB_Template_Runtime::addStyleVar('languagecode', $userinfo['lang_code']);

	// get the 'charset' attribute
	vB_Template_Runtime::addStyleVar('charset', $userinfo['lang_charset']);

	set_stylevar_meta($style['styleid']);
}

// #############################################################################
/**
 *	Sets the ltr/rtl stylevars
 *
 *	@param $ltr bool do we want to render styles as ltr
 */
function set_stylevar_ltr($ltr)
{
	if ($ltr)
	{
		vB_Template_Runtime::addStyleVar('left', 'left');
		vB_Template_Runtime::addStyleVar('right', 'right');
		vB_Template_Runtime::addStyleVar('textdirection', 'ltr');
		vB_Template_Runtime::addStyleVar('pos', '');
		vB_Template_Runtime::addStyleVar('neg', '-');
	}
	else
	{
		vB_Template_Runtime::addStyleVar('left', 'right');
		vB_Template_Runtime::addStyleVar('right', 'left');
		vB_Template_Runtime::addStyleVar('textdirection', 'rtl');
		vB_Template_Runtime::addStyleVar('pos', '-');
		vB_Template_Runtime::addStyleVar('neg', '');
	}
}

// #############################################################################
/**
 * Adds a couple of pseudo stylevars with some meta info (styleid and cssdate), which
 * are used the the SVG icon system.
 *
 * @param int Style ID
 */
function set_stylevar_meta($styleid)
{
	// add 'styleid' of the current style for the sprite.php call to load the SVG icon sprite
	vB_Template_Runtime::addStyleVar('styleid', $styleid);

	// add 'cssdate' for the cachebreaker for the sprite.php call to load the SVG icon sprite
	$miscoptions = vB::getDatastore()->getValue('miscoptions');
	$cssdate = intval($miscoptions['cssdate']);
	if (!$cssdate)
	{
		$cssdate = time(); // fallback so we get the latest css
	}
	vB_Template_Runtime::addStyleVar('cssdate', $cssdate);
}

// #############################################################################
/**
* Function to override various settings in $vbulletin->options depending on user preferences
*
* @param	array	User info array
*/
function fetch_options_overrides($userinfo)
{
	global $vbulletin;

	$vbulletin->options['default_dateformat'] = $vbulletin->options['dateformat'];
	$vbulletin->options['default_timeformat'] = $vbulletin->options['timeformat'];

	if ($userinfo['lang_dateoverride'] != '')
	{
		$vbulletin->options['dateformat'] = $userinfo['lang_dateoverride'];
	}
	if ($userinfo['lang_timeoverride'] != '')
	{
		$vbulletin->options['timeformat'] = $userinfo['lang_timeoverride'];
	}
	if ($userinfo['lang_registereddateoverride'] != '')
	{
		$vbulletin->options['registereddateformat'] = $userinfo['lang_registereddateoverride'];
	}
	if ($userinfo['lang_calformat1override'] != '')
	{
		$vbulletin->options['calformat1'] = $userinfo['lang_calformat1override'];
	}
	if ($userinfo['lang_calformat2override'] != '')
	{
		$vbulletin->options['calformat2'] = $userinfo['lang_calformat2override'];
	}
	if ($userinfo['lang_eventdateformatoverride'] != '')
	{
		$vbulletin->options['eventdateformat'] = $userinfo['lang_eventdateformatoverride'];
	}
	if ($userinfo['lang_pickerdateformatoverride'] != '')
	{
		$vbulletin->options['pickerdateformat'] = $userinfo['lang_pickerdateformatoverride'];
	}
	if ($userinfo['lang_pickerdateformatoverride'] != '')
	{
		$vbulletin->options['pickerdateformat'] = $userinfo['lang_pickerdateformatoverride'];
	}
	if ($userinfo['lang_logdateoverride'] != '')
	{
		$vbulletin->options['logdateformat'] = $userinfo['lang_logdateoverride'];
	}
	if ($userinfo['lang_locale'] != '')
	{
		$locale1 = setlocale(LC_TIME, $userinfo['lang_locale']);
		if (substr($userinfo['lang_locale'], 0, 5) != 'tr_TR')
		{
			$locale2 = setlocale(LC_CTYPE, $userinfo['lang_locale']);
		}
	}

	if (defined('VB_API') AND VB_API === true)
	{
		// vboptions overwrite for API
		$vbulletin->options['dateformat'] = 'm-d-Y';
		$vbulletin->options['timeformat'] = 'h:i A';
		$vbulletin->options['registereddateformat'] = 'm-d-Y';
	}

}

// #############################################################################
/**
* Returns the initial $vbphrase array
*
* @return	array
*/
function init_language()
{
	global $vbulletin, $phrasegroups;
	global $copyrightyear, $timediff, $timenow, $datenow;

	$userInfo = vB::getCurrentSession()->fetch_userinfo();

	$options = vB::getDatastore()->getValue('options');

	// define languageid
	$languageid = !empty($userInfo['languageid']) ? $userInfo['languageid'] : intval($options['languageid']);
	define('LANGUAGEID', $languageid);

	$phraseinfo = vB_Language::getPhraseInfo($languageid);
	if (!$phraseinfo)
	{
		// can't phrase this since we can't find the language
		throw new Exception('The requested language does not exist, reset via tools.php.');
	}

	// initialize the $vbphrase array
	$vbphrase = [];

	// populate the $vbphrase array with phrase groups
	if (empty($phrasegroups))
	{
		$phrasegroups = ['global'];
	}

	foreach ($phrasegroups AS $phrasegroup)
	{
		$tmp = unserialize($phraseinfo["phrasegroup_$phrasegroup"] ?? '');
		if (is_array($tmp))
		{
			$vbphrase = array_merge($vbphrase, $tmp);
		}
	}

	// Phrase {shortcode} replacements
	// This replacement happens in several places. Please keep them synchronized.
	// You can search for {shortcode} in php and js files.
	// Shortcode replacements happen *before* inserting params into the phrases,
	// so we don't accidentally do a replacement in the data that was inserted
	// into the phrase.
	$shortcode_replace_map = array (
		'{sitename}'        => $options['bbtitle'],
		'{musername}'       => $userInfo['musername'],
		'{username}'        => $userInfo['username'],
		'{userid}'          => $userInfo['userid'],
		'{registerurl}'     => vB5_Route::buildUrl('register|fullurl'),
		'{activationurl}'   => vB5_Route::buildUrl('activateuser|fullurl'),
		'{helpurl}'         => vB5_Route::buildUrl('help|fullurl'),
		'{contacturl}'      => vB5_Route::buildUrl('contact-us|fullurl'),
		'{homeurl}'         => $options['frontendurl'],
		'{date}'            => vbdate($options['dateformat']),
		'{webmasteremail}'  => $options['webmasteremail'],
		// ** leave deprecated codes in to avoid breaking existing data **
		// deprecated - the previous *_page codes have been replaced with the *url codes
		'{register_page}'   => vB5_Route::buildUrl('register|fullurl'),
		'{activation_page}' => vB5_Route::buildUrl('activateuser|fullurl'),
		'{help_page}'       => vB5_Route::buildUrl('help|fullurl'),
		// deprecated - session url codes are no longer needed
		'{sessionurl}'      => '',
		'{sessionurl_q}'    => '',
	);
	$shortcode_find = array_keys($shortcode_replace_map);
	foreach ($vbphrase AS $k => $v)
	{
		$vbphrase[$k] = str_replace($shortcode_find, $shortcode_replace_map, $vbphrase[$k]);
	}

	// prepare phrases for construct_phrase / sprintf use
	//$vbphrase = preg_replace('/\{([0-9]+)\}/siU', '%\\1$s', $vbphrase);

	// pre-parse some global phrases
	$tzoffset = iif($vbulletin->userinfo['tzoffset'], ' ' . $vbulletin->userinfo['tzoffset']);
	$vbphrase['all_times_are_gmt_x_time_now_is_y'] = construct_phrase($vbphrase['all_times_are_gmt_x_time_now_is_y'], $tzoffset, $timenow, $datenow);
	$vbphrase['vbulletin_copyright_orig'] = $vbphrase['vbulletin_copyright'];
	$vbphrase['vbulletin_copyright'] = construct_phrase($vbphrase['vbulletin_copyright'], $options['templateversion'], $copyrightyear);
	$vbphrase['powered_by_vbulletin'] = construct_phrase($vbphrase['powered_by_vbulletin'], $options['templateversion'], $copyrightyear);
	$vbphrase['timezone'] = construct_phrase($vbphrase['timezone'], $timediff, $timenow, $datenow);

	// all done
	return $vbphrase;
}

// #############################################################################
/**
* Saves the specified data into the datastore
*
* @param	string	The name of the datastore item to save
* @param	mixed	The data to be saved
* @param	integer 1 or 0 as to whether this value is to be automatically unserialised on retrieval
* @deprecated	use the datastore class directly.
*/
function build_datastore($title = '', $data = '', $unserialize = 0)
{
	vB::getDatastore()->build($title, $data, $unserialize);
}

// #############################################################################
/**
* Checks whether or not user came from search engine
*/
function is_came_from_search_engine()
{
	global $vbulletin;

	static $is_came_from_search_engine;	// hey, you, user, you came from search engine?
        $options = vB::getDatastore()->getValue('options');
	if (!isset($is_came_from_search_engine))
	{
		$user_referrer = $_SERVER['HTTP_REFERER'] . '.';		// we're trusting wherever the user claims they're from, if they lie, we can't do anything about it

		if ($options['searchenginereferrers'] = trim($options['searchenginereferrers']))
		{

			$searchengines = preg_split('#\s+#', $options['searchenginereferrers'], -1, PREG_SPLIT_NO_EMPTY);
			foreach ($searchengines AS $searchengine)
			{
				if (strpos($searchengine, '*') === false AND $searchengine[strlen($searchengine) - 1] != '.')
				{
					$searchengine .= '.';
				}

				$searchengine_regex = str_replace('\*', '(.*)', preg_quote($searchengine, '#'));
				if (preg_match('#' . $searchengine_regex . '#U', $user_referrer))
				{
					$is_came_from_search_engine = true;
					return $is_came_from_search_engine;
				}
			}
		}

		// if nothing to match against (or we didn't return earlier)
		$is_came_from_search_engine = false;
	}

	return $is_came_from_search_engine;
}

// #############################################################################
/**
* Updates the LoadAverage DataStore
*/

function update_loadavg()
{
	global $vbulletin;

	if (!isset($vbulletin->loadcache))
	{
		$vbulletin->loadcache = array();
	}

	if (function_exists('exec') AND $stats = @exec('uptime 2>&1') AND trim($stats) != '' AND preg_match('#: ([\d.,]+),?\s+([\d.,]+),?\s+([\d.,]+)$#', $stats, $regs))
	{
		$vbulletin->loadcache['loadavg'] = $regs[2];
	}
	else if (@file_exists('/proc/loadavg') AND $filestuff = @file_get_contents('/proc/loadavg'))
	{
		$loadavg = explode(' ', $filestuff);

		$vbulletin->loadcache['loadavg'] = $loadavg[1];
	}
	else
	{
 		$vbulletin->loadcache['loadavg'] = 0;
	}

	$vbulletin->loadcache['lastcheck'] = TIMENOW;
	build_datastore('loadcache', serialize($vbulletin->loadcache), 1);
}

// #############################################################################
/**
* Escapes quotes in strings destined for Javascript
*
* @param	string	String to be prepared for Javascript
* @param	string	Type of quote (single or double quote)
*
* @return	string
*/
function addslashes_js($text, $quotetype = "'")
{
	if ($quotetype == "'")
	{
		// single quotes
		$replaced = str_replace(array('\\', '\'', "\n", "\r"), array('\\\\', "\\'","\\n", "\\r"), $text);
	}
	else
	{
		// double quotes
		$replaced = str_replace(array('\\', '"', "\n", "\r"), array('\\\\', "\\\"","\\n", "\\r"), $text);
	}

	$replaced = preg_replace('#(-(?=-))#', "-$quotetype + $quotetype", $replaced);
	$replaced = preg_replace('#</script#i', "<\\/scr$quotetype + {$quotetype}ipt", $replaced);

	return $replaced;
}

// #############################################################################
/**
* Finishes off the current page (using templates), prints it out to the browser and halts execution
*
* @param	string	The HTML of the page to be printed
* @param	boolean	Send the content length header?
* @deprecated
*/
//only called from payment_gateway.php.  The code needs to be cleaned up/updated in general.
function print_output($vartext, $sendheader = true)
{
	global $querytime, $vbulletin, $show, $vbphrase;

	$options = vB::getDatastore()->getValue('options');
	$vb5_config =& vB::getConfig();

	if ($options['addtemplatename'])
	{
		if ($doctypepos = @strpos($vartext, vB_Template_Runtime::fetchStyleVar('htmldoctype')))
		{
			$comment = substr($vartext, 0, $doctypepos);
			$vartext = substr($vartext, $doctypepos + strlen(vB_Template_Runtime::fetchStyleVar('htmldoctype')));
			$vartext = vB_Template_Runtime::fetchStyleVar('htmldoctype') . "\n" . $comment . $vartext;
		}
	}

	$output = $vartext;

	if ($vb5_config['Misc']['debug'] AND function_exists('memory_get_usage'))
	{
		$output = preg_replace('#(<!--querycount-->Executed <b>\d+</b> queries<!--/querycount-->)#siU', 'Memory Usage: <strong>' . number_format((memory_get_usage() / 1024)) . 'KB</strong>, \1', $output);
	}

	// make sure headers sent returns correctly
	if (ob_get_level() AND ob_get_length())
	{
		ob_end_flush();
	}

	if (!headers_sent())
	{
		if ($sendheader)
		{
			@header('Content-Length: ' . strlen($output));
		}
	}

	// Trigger shutdown event
	$vbulletin->shutdown->shutdown();

	if (defined('NOSHUTDOWNFUNC'))
	{
		exec_shut_down();
	}

	// show regular page
	if ($vbulletin->db->isExplainEmpty())
	{
		echo $output;
	}
	// show explain
	else
	{
		$querytime = $vbulletin->db->time_total;
		echo "\n<b>Page generated in $totaltime seconds with " . $vbulletin->db->querycount .
			" queries,\nspending $querytime doing MySQL queries and " . ($totaltime - $querytime) .
			" doing PHP things.\n\n<hr />Shutdown Queries:</b>" . (defined('NOSHUTDOWNFUNC') ? " <b>DISABLED</b>" : '') . "<hr />\n\n";
	}

	// broken if zlib.output_compression is on with Apache 2
	if (PHP_SAPI != 'apache2handler')
	{
		flush();
	}

	exit;
}

// #############################################################################
/**
* Performs general clean-up after the system exits, such as running shutdown queries
*/
function exec_shut_down()
{
	global $vbulletin;
	$options = vB::getDatastore()->getValue('options');
	if (defined('VB_AREA') AND (VB_AREA == 'Install' OR VB_AREA == 'Upgrade'))
	{
		return;
	}

	if ($vbulletin->db)
	{
		$vbulletin->db->unlock_tables();
	}

	if (!$options['bbactive'] AND !vB::getUserContext()->hasAdminPermission('cancontrolpanel'))
	{
		// Forum is disabled and this is not someone with admin access
		$vbulletin->userinfo['badlocation'] = 2;
	}

	if (is_object($vbulletin->session))
	{
		$vbulletin->session->set('badlocation', $vbulletin->userinfo['badlocation'] ?? '');
		if (vB::getCurrentSession()->get('loggedin') == 1 AND !$vbulletin->session->created)
		{
			// If loggedin = 1, this is out first page view after a login so change value to 2 to signify we are past the first page view
			// We do a DST update check if loggedin = 1
			$vbulletin->session->set('loggedin', 2);
		}
		$vbulletin->session->save();
	}

	if (is_array($vbulletin->db->shutdownqueries))
	{
		$vbulletin->db->hide_errors();
		foreach($vbulletin->db->shutdownqueries AS $name => $query)
		{
			if (!empty($query))
			{
				$vbulletin->db->query_write($query);
			}
		}
		$vbulletin->db->show_errors();
	}

	// execute the queries that have been registered in the assertor
	vB::getDbAssertor()->executeShutdownQueries();

	// Make sure the database connection is closed since it can get hung up for a long time on php4 do to the mysterious echo() lagging issue
	// If NOSHUTDOWNFUNC is defined then this function should always be the last one called, before echoing of data
	if (defined('NOSHUTDOWNFUNC') AND !empty($vbulletin->db))
	{
		$vbulletin->db->close();
		vB_Shutdown::instance()->setCalled(); // Stop this running as DB connection is closed.
	}

	$vbulletin->db->shutdownqueries = array();
	// bye bye!
}

/**
 * Spreads an array of values across the given number of stepped levels based on
 * their standard deviation from the mean value.
 *
 * The function accepts an array of $id => $value and returns $id => $level.
 *
 * @param array $values							- Array of id => values
 * @param integer $levels						- Number of levels to assign
 */
function fetch_standard_deviated_levels($values, $levels=5)
{
	if (!$count = sizeof($values))
	{
		return array();
	}

	$total = $summation = 0;
	$results = array();

	// calculate the total
	foreach ($values AS $value)
	{
		$total += $value;
	}

	// calculate the mean
	$mean = $total / $count;

	// calculate the summation
	foreach ($values AS $id => $value)
	{
		$summation += pow(($value - $mean), 2);
	}

	$sd = sqrt($summation / $count);

	if ($sd)
	{
		$sdvalues = array();
		$lowestsds = 0;
		$highestsds = 0;

		// find the max and min standard deviations
		foreach ($values AS $id => $value)
		{
			$value = (($value - $mean) / $sd);
			$values[$id] = $value;

			$lowestsds = min($value, $lowestsds);
			$highestsds = max($value, $highestsds);
		}

		foreach ($values AS $id => $value)
		{
			// normalize the std devs to 0 - 1, then map back to 1 - #levls
			$values[$id] = round((($value - $lowestsds) / ($highestsds - $lowestsds)) * ($levels - 1)) + 1;
		}
	}
	else
	{
		foreach ($values AS $id => $value)
		{
			$values[$id] = round($levels / 2);
		}
	}

	return $values;
}

/**
 *	Resolve paths that appear in the admincp as "server" paths.
 *
 *	Rather than copy various logic all around, we want to centralize it
 *	in case it changes.  This permits us to change things all at once.
 *	The current logic is that absolute paths are left alone and relative
 *	paths are resolved assuming they are in the core directory.
 *
 *	@param string $path -- the path to resolve
 *	@return string|false  -- the fully qualified path if it exists, false otherwise.
 */
function resolve_server_path($path)
{
	//this is funky, but it turns out there isn't a standard way of
	//detecting a fully qualified path (and it gets *complicated* on
	//windows machines).  However realpath will handle relative paths
	//according to the cwd.
	$currentDir = getcwd();
	chdir(DIR);
	$path = realpath($path);
	chdir($currentDir);
	return $path;
}

/**
 *	Sort an array of arrays by subkey
 *
 *	This will sort an array of array based on the values of
 *	$array[$key][$subkey]
 *	This function assumes that all elements of $array are arrays and they all
 *	contain a field named $subkey.  Key mappings will be preserved.
 *
 *	This uses php sorts internally and is, therefore, not a stable sort.
 *
 * 	@param array $array -- an array of arrays
 * 	@param mixed $subkey -- the subkey to check.  This can be any valid
 * 		array key type.
 */
function array_subkey_sort(&$array, $subkey)
{
	uasort($array, function($a, $b) use ($subkey)
	{
		return $a[$subkey] <=> $b[$subkey];
	});
}

/**
 *	Hide the details of unserialization to allow us to change/tighten configuration as it evolves.
 */
//This is approach is faster than using the vB_Utility_Unserialize::unserialize approach though
//the latter is more robust in that it will not attempt to unserialize objects at all.
function vb_unserialize($text)
{
	return unserialize($text, ['allowed_classes' => false]);
}

//In most cases we are serializing an array but we have a bad habit of storing empty results as the emtpy string.
//Sweep that under the rug.  We may want additional validation in the case where the actual unserialized string
//is not an array, but need some better sence of use cases before doing that.
function vb_unserialize_array($text)
{
	if(trim($text) == '')
	{
		return [];
	}
	else
	{
		return unserialize($text, ['allowed_classes' => false]);
	}
}


//originally from functions_misc.php
// ###################### Start vbmktime #######################
/**
 *	Convert an array into a unix timestamp taking into account the timezone correction as per
 *	the vbmktime function.
 *
 *	@param array $time -- any fields not in the array will be assumed to be zero
 *		The names of the fields intentionally match values in existing controls and
 *		are therefore not consistant with the vbmktime parameter names.
 *			int year
 *			int month
 *			int day
 *			int hour
 *			int minute
 *			int second
 *	@return int -- the unix timestamp
 */
function vbmktime_array($time)
{
	return vbmktime(
		isset($time['hour']) ? $time['hour'] : 0,
		isset($time['minute']) ? $time['minute'] : 0,
		isset($time['second']) ? $time['second'] : 0,
		isset($time['month']) ? $time['month'] : 0,
		isset($time['day']) ? $time['day'] : 0,
		isset($time['year']) ? $time['year'] : 0
	);
}

function vbmktime($hours = 0, $minutes = 0, $seconds = 0, $month = 0, $day = 0, $year = 0)
{
	$userinfo = vB::getCurrentSession()->fetch_userinfo();
	return mktime(intval($hours), intval($minutes), intval($seconds), intval($month),
		intval($day), intval($year)) + $userinfo['servertimediff'];
}

function validate_string_for_interpolation($string)
{
	$start = '{$';
	$end = '}';

	$pos = 0;
	$start_count = 0;
	$content_start = 0;

	while ($pos < strlen($string))
	{
		if($start_count == 0)
		{
			$pos = strpos($string, $start, $pos);

			//no curlies
			if ($pos === false)
			{
				break;
			}

			$pos += strlen($start);

			$start_count = 1;
			$content_start = $pos;
		}
		else
		{
			$start_pos = strpos($string, $start, $pos);
			$end_pos = strpos($string, $end, $pos);

			//nothing more to find.
			if ($start_pos === false AND $end_pos === false)
			{
				break;
			}

			//end_pos is the next position found
			else if ($start_pos === false OR ($end_pos < $start_pos))
			{
				$start_count--;
				$pos = $end_pos + strlen($end);
			}

			//otherwise start_pos must've been next
			else
			{
				$start_count++;
				$pos = $end_pos + strlen($end);
			}

			if ($start_count == 0)
			{
				//this is the string from contentstart to the place before the last brace
				$curly_content = substr($string, $content_start, $pos-$content_start-1);
				if (!preg_match('#^[-\p{L}0-9_>\\[\\]"\'\\s]*$#', $curly_content))
				{
					return false;
				}
			}
		}
	}

	return true;
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 115279 $
|| #######################################################################
\*=========================================================================*/
