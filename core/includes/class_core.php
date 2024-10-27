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


//this is also included in vb.php init().  This may no longer be needed here.
require_once('version_base_vbulletin.php');
define('YUI_VERSION', '2.7.0'); // define the YUI version we bundle

/**#@+
* The maximum sizes for the "small" profile avatars
*/
define('FIXED_SIZE_AVATAR_WIDTH',  60);
define('FIXED_SIZE_AVATAR_HEIGHT', 80);
/**#@-*/

/**#@+
* These make up the bit field to disable specific types of BB codes.
*/
define('ALLOW_BBCODE_BASIC',  1);
define('ALLOW_BBCODE_COLOR',  2);
define('ALLOW_BBCODE_SIZE',   4);
define('ALLOW_BBCODE_FONT',   8);
define('ALLOW_BBCODE_ALIGN',  16);
define('ALLOW_BBCODE_LIST',   32);
define('ALLOW_BBCODE_URL',    64);
define('ALLOW_BBCODE_CODE',   128);
define('ALLOW_BBCODE_PHP',    256);
define('ALLOW_BBCODE_HTML',   512);
define('ALLOW_BBCODE_IMG',    1024);
define('ALLOW_BBCODE_QUOTE',  2048);
define('ALLOW_BBCODE_CUSTOM', 4096);
/**#@-*/

/**#@+
* These make up the bit field to control what "special" BB codes are found in the text.
*/
define('BBCODE_HAS_IMG',    1);
define('BBCODE_HAS_ATTACH', 2);
define('BBCODE_HAS_SIGPIC', 4);
define('BBCODE_HAS_RELPATH',8);
/**#@-*/

/**#@+
* Bitfield values for the inline moderation javascript selector which should be self-explanitory
*/
define('POST_FLAG_INVISIBLE', 1);
define('POST_FLAG_DELETED',   2);
define('POST_FLAG_ATTACH',    4);
define('POST_FLAG_GUEST',     8);
/**#@-*/


/**
* Class to handle and sanitize variables from GET, POST and COOKIE etc
*
* @package	vBulletin
* @date		$Date: 2024-05-17 16:46:38 -0700 (Fri, 17 May 2024) $
*/
class vB_Input_Cleaner
{
	use vB_Trait_NoSerialize;

	/**
	* Translation table for short name to long name
	*
	* @var    array
	*/
	var $shortvars = [
		'n'     => 'nodeid',
		'f'     => 'forumid',
		't'     => 'threadid',
		'p'     => 'postid',
		'u'     => 'userid',
		'c'     => 'calendarid',
		'e'     => 'eventid',
		'q'     => 'query',
		'pp'    => 'perpage',
		'page'  => 'pagenumber',
	];

	/**
	* Translation table for short superglobal name to long superglobal name
	*
	* @var     array
	*/
	var $superglobal_lookup = [
		'g' => '_GET',
		'p' => '_POST',
		'r' => '_REQUEST',
		'c' => '_COOKIE',
		's' => '_SERVER',
		'e' => '_ENV',
		'f' => '_FILES'
	];

	/**
	* System state. The complete URL of the current page, without sessionhash
	*
	* @var	string
	*/
	var $scriptpath = '';

	/**
	* System state. The complete URL of the page for Who's Online purposes
	*
	* @var	string
	*/
	var $wolpath = '';

	/**
	* System state. The complete URL of the referring page
	*
	* @var	string
	*/
	var $url = '';

	/**
	* A reference to the main registry object
	*
	* @var	vB_Registry
	*/
	var $registry = null;

	/**
	* Keep track of variables that have already been cleaned
	*
	* @var	array
	*/
	var $cleaned_vars = [];

	/**
	* Constructor
	*
	* First, reverses the effects of magic quotes on GPC
	* Second, translates short variable names to long (u --> userid)
	* Third, deals with $_COOKIE[userid] conflicts
	*
	* @param	vB_Registry	The instance of the vB_Registry object
	*/
	function __construct(&$registry)
	{
		$this->registry =& $registry;

		// We need the GET to transfer over to $VB_API_PARAMS_TO_VERIFY *before* it hits
		// convert_shortvars, as that will stuff a 'pagenum' param that was never passed in
		// in place of a 'page' param that *was* passed in, which causes the api signature
		// validation to fail.
		// This is kind of crappy, but we can't easily change the order of ops right now
		// so we do this instead. Consumed by vB_Session_Api ::validateApiSession()
		// called downstream of core/api.php
		// Note, the signature api_sig is only checked/verified if api_c is passed in.
		if (isset($_REQUEST['api_c']) OR isset($_REQUEST['api_sig']))
		{
			global $VB_API_PARAMS_TO_VERIFY;

			$VB_API_PARAMS_TO_VERIFY = $_GET;
			unset($VB_API_PARAMS_TO_VERIFY['']); // See VBM-835

			unset(
				$VB_API_PARAMS_TO_VERIFY['api_c'],
				$VB_API_PARAMS_TO_VERIFY['api_v'],
				$VB_API_PARAMS_TO_VERIFY['api_s'],
				$VB_API_PARAMS_TO_VERIFY['api_sig'],
				$VB_API_PARAMS_TO_VERIFY['debug'],
				$VB_API_PARAMS_TO_VERIFY['showall'],
				$VB_API_PARAMS_TO_VERIFY['do'],
				$VB_API_PARAMS_TO_VERIFY['r']
			);

			ksort($VB_API_PARAMS_TO_VERIFY);
		}

		foreach (['_GET', '_POST'] AS $arrayname)
		{
			if (isset($GLOBALS["$arrayname"]['do']))
			{
				$GLOBALS["$arrayname"]['do'] = trim($GLOBALS["$arrayname"]['do']);
			}

			$this->convert_shortvars($GLOBALS["$arrayname"]);
		}

		// fetch url of current page for Who's Online
		if (!defined('SKIP_WOLPATH') OR !SKIP_WOLPATH)
		{
			$registry->wolpath = $this->fetch_wolpath();
			if ($registry->wolpath)
			{
				define('WOLPATH', $registry->wolpath);
			}
		}
	}

	/**
	 * Fetches a value from $_SERVER or $_ENV
	 *
	 * @param string $name
	 * @return string
	 */
	function fetch_server_value($name)
	{
		if (isset($_SERVER[$name]) AND $_SERVER[$name])
		{
			return $_SERVER[$name];
		}

		if (isset($_ENV[$name]) AND $_ENV[$name])
		{
			return $_ENV[$name];
		}

		return false;
	}


	/**
	 * Adds a query string to a path, fixing the query characters.
	 *
	 * @param 	string		The path to add the query to
	 * @param 	string		The query string to add to the path
	 *
	 * @return	string		The resulting string
	 */
	function add_query($path, $query = false)
	{
		if (false === $query)
		{
			$query = VB_URL_QUERY;
		}

		if (!$query OR !($query = trim($query, '?&')))
		{
			return $path;
		}

		return $path . '?' . $query;
	}

	/**
	 * Adds a fragment to a path
	 *
	 * @param 	string		The path to add the fragment to
	 * @param 	string		The fragment to add to the path
	 *
	 * @return	string		The resulting string
	 */
	function add_fragment($path, $fragment = false)
	{
		if (!$fragment)
		{
			return $path;
		}

		return $path . '#' . $fragment;
	}

	/**
	* Makes GPC variables safe to use
	*
	* @param	string	Either, g, p, c, r or f (corresponding to get, post, cookie, request and files)
	* @param	array	Array of variable names and types we want to extract from the source array
	*
	* @return	void
	*/
	function clean_array_gpc($source, $variables)
	{
		$sg =& $GLOBALS[$this->superglobal_lookup["$source"]];

		foreach ($variables AS $varname => $vartype)
		{
			// clean a variable only once unless its a different type
			if (!isset($this->cleaned_vars["$varname"]) OR $this->cleaned_vars["$varname"] != $vartype)
			{
				$this->registry->GPC_exists["$varname"] = isset($sg["$varname"]);
				$this->registry->GPC["$varname"] =& $this->registry->cleaner->clean(
					$sg["$varname"],
					$vartype,
					isset($sg["$varname"])
				);
				// All STR type passed from API client should be in UTF-8 encoding and we need to convert it back to vB's current encoding.
				// We also need to do this this for the ajax requests for the mobile style.
				// Checking the forcenoajax flag isn't ideal, but it works and limits the scope of the fix (and the risk).
				if ((defined('VB_API') AND VB_API === true) OR !empty($GLOBALS[$this->superglobal_lookup['r']]['forcenoajax']))
				{
					switch ($vartype) {
						case vB_Cleaner::TYPE_STR:
						case vB_Cleaner::TYPE_NOTRIM:
						case vB_Cleaner::TYPE_NOHTML:
						case vB_Cleaner::TYPE_NOHTMLCOND:
							if (!($charset = vB_Template_Runtime::fetchStyleVar('charset')))
							{
								$charset = $this->registry->userinfo['lang_charset'];
							}

							$lower_charset = strtolower($charset);
							if ($lower_charset != 'utf-8')
							{
								if ($lower_charset == 'iso-8859-1')
								{
									$this->registry->GPC["$varname"] = to_charset(ncrencode($this->registry->GPC["$varname"], true, true), 'utf-8');
								}
								else
								{
									$this->registry->GPC["$varname"] = to_charset($this->registry->GPC["$varname"], 'utf-8');
								}
							}
					}
				}
				$this->cleaned_vars["$varname"] = $vartype;
			}
		}
	}

	/**
	* Makes a single GPC variable safe to use and returns it
	*
	* @param	array	The source array containing the data to be cleaned
	* @param	string	The name of the variable in which we are interested
	* @param	integer	The type of the variable in which we are interested
	*
	* @return	mixed
	*/
	function &clean_gpc($source, $varname, $vartype = vB_Cleaner::TYPE_NOCLEAN)
	{
		// clean a variable only once unless its a different type
		if (!isset($this->cleaned_vars["$varname"]) OR $this->cleaned_vars["$varname"] != $vartype)
		{
			$sg =& $GLOBALS[$this->superglobal_lookup["$source"]];

			$this->registry->GPC_exists["$varname"] = isset($sg["$varname"]);
			$this->registry->GPC["$varname"] =& $this->registry->cleaner->clean(
				$sg["$varname"],
				$vartype,
				isset($sg["$varname"])
			);
			$this->cleaned_vars["$varname"] = $vartype;
		}

		return $this->registry->GPC["$varname"];
	}

	/**
	 * Cleans a query string.
	 * Unicode is decoded, url entities are kept encoded, and slashes are preserved.
	 *
	 * @param string $path
	 * @return string
	 */
	function utf8_clean_path($path, $reencode = true)
	{
		$path = explode('/', $path);
		$path = array_map('urldecode', $path);

		if ($reencode)
		{
			$path = array_map('urlencode_uni', $path);
		}

		$path = implode('/', $path);

		return $path;
	}

	/**
	* Turns $_POST['t'] into $_POST['threadid'] etc.
	*
	* @param	array	The name of the array
	*/
	function convert_shortvars(&$array, $setglobals = true)
	{
		// extract long variable names from short variable names
		foreach ($this->shortvars AS $shortname => $longname)
		{
			if (isset($array["$shortname"]) AND !isset($array["$longname"]))
			{
				$array["$longname"] =& $array["$shortname"];
				if ($setglobals)
				{
					$GLOBALS['_REQUEST']["$longname"] =& $array["$shortname"];
				}
			}
		}
	}

	/**
	* Strips out the s=gobbledygook& rubbish from URLs
	*
	* @param	string	The URL string from which to remove the session stuff
	*
	* @return	string
	*/
	function strip_sessionhash($string)
	{
		$string = preg_replace('/(s|sessionhash)=[a-z0-9]{32}?&?/', '', $string);
		return $string;
	}

	/**
	 * Fetches the 'basepath' variable that can be used as <base>.
	 *
	 * @return string
	 */
	function fetch_basepath($rel_modifier = false)
	{
		if ($this->registry->basepath != '')
		{
			return $this->registry->basepath;
		}

		if ($this->registry->options['bburl_basepath'])
		{
			$basepath = trim($this->registry->options['bburl'], '/\\') . '/';
		}
		else
		{
			$basepath = VB_URL_BASE_PATH;
		}

		return $basepath = $basepath . ($rel_modifier ? $this->registry->cleaner->xssClean($rel_modifier) : '');
	}

	/**
	 * Fetches the path for the current request relative to the basepath.
	 * This is useful for local anchors (<a href="{vb:raw relpath}#post">).
	 *
	 * Substracts any overlap between basepath and path with the following results:
	 *
	 * 		base:		http://www.example.com/forums/
	 * 		path:		/forums/content.php
	 * 		result:		content.php
	 *
	 * 		base:		http://www.example.com/forums/admincp
	 * 		path:		/forums/content/1-Article
	 * 		result:		../content/1-Article
	 *
	 * @return string
	 */
	function fetch_relpath($path = false)
	{
		if (!$path AND (isset($this->registry->relpath) AND $this->registry->relpath != ''))
		{
			return $this->registry->relpath;
		}

		// if no path specified, use the request path
		if (!$path)
		{
			if ($_SERVER['REQUEST_METHOD'] == 'POST' AND isset($_SERVER['HTTP_X_REQUESTED_WITH']) AND
			 $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest' AND !empty($_POST['relpath']))
			{
				$relpath = $_POST['relpath'];
				$query = '';
			}
			else
			{
				$relpath = VB_URL_PATH;
				$query = VB_URL_QUERY;
				$fragment = "";
			}
		}
		else
		{
			// if the path is already absolute there's nothing to do
			if (strpos($path, '://'))
			{
				return $path;
			}

			if (!$path)
			{
				return $path;
			}

			$relpath = vB_String::parseUrl($path, PHP_URL_PATH);
			$query = vB_String::parseUrl($path, PHP_URL_QUERY);
			$fragment = vB_String::parseUrl($path, PHP_URL_FRAGMENT);
		}

		$relpath = ltrim(strval($relpath), '/');
		$basepath = @vB_String::parseUrl($this->fetch_basepath(), PHP_URL_PATH);
		$basepath = trim($basepath, '/');

		// get path segments for comparison
		$relpath = explode('/', $relpath);
		$basepath = explode('/', $basepath);

		// remove segments that basepath and relpath share
		foreach ($basepath AS $segment)
		{
			if ($segment == current($relpath))
			{
				array_shift($basepath);
				array_shift($relpath);
			}
			else
			{
				break;
			}
		}

		// rebuild the relpath
		$relpath = implode('/', $relpath);

		// add the query string if the current path is being used
		if ($query)
		{
			$relpath = $this->add_query($relpath, $query);
		}

		// add the fragment back
		if ($fragment)
		{
			$relpath = $this->add_fragment($relpath, $fragment);
		}

		return $relpath;
	}


	/**
	* Fetches the 'wolpath' variable - ie: the same as 'scriptpath' but with a handler for the POST request method
	*
	* @return	string
	*/
	function fetch_wolpath()
	{
		$request = vB::getRequest();
		if (!$request)
		{
			return '';
		}

		$wolpath = $request->getScriptPath();

		if (!empty($_SERVER['REQUEST_METHOD']) AND ($_SERVER['REQUEST_METHOD'] == 'POST'))
		{
			// Tag the variables back on to the filename if we are coming from POST so that WOL can access them.
			$tackon = '';

			if (is_array($_POST))
			{
				foreach ($_POST AS $varname => $value)
				{
					switch ($varname)
					{
						case 'forumid':
						case 'threadid':
						case 'postid':
						case 'userid':
						case 'eventid':
						case 'calendarid':
						case 'do':
						case 'method': // postings.php
						case 'dowhat': // private.php
						{
							if (is_array($value))
							{
								// See VBV-9534
								break;
							}
							$tackon .= ($tackon == '' ? '' : '&amp;') . $varname . '=' . $value;
							break;
						}
					}
				}
			}
			if ($tackon != '')
			{
				$wolpath .= (strpos($wolpath, '?') !== false ? '&amp;' : '?') . "$tackon";
			}
		}

		return $wolpath;
	}

	/**
	* Fetches the 'url' variable - usually the URL of the previous page in the history
	*
	* @return	string
	*/
	function fetch_url()
	{
		$scriptpath = vB::getRequest()->getScriptPath();

		//note regarding the default url if not set or inappropriate.
		//started out as index.php then moved to options['forumhome'] . '.php' when that option was added.
		//now we've changed to to the forumhome url since there is now quite a bit of logic around that.
		//Its not clear, however, with the expansion of vb if that's the most appropriate generic landing
		//place (perhaps it *should* be index.php).
		//In any case there are several places in the code that check for the default page url and change it
		//to something more appropriate.  If the default url changes, so do those checks.
		//The solution is, most likely, to make some note when vbulletin->url is the default so it can be overridden
		//without worrying about what the exact text is.

		$url = null;
		if (empty($_REQUEST['url']))
		{
			$url = (!empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '');
		}
		else
		{
			$temp_url = $_REQUEST['url'];
			if (empty($_SERVER['HTTP_REFERER']) OR ($temp_url != $_SERVER['HTTP_REFERER']))
			{
				$url = $temp_url;
			}
		}

		if (empty($url) OR $url == $scriptpath)
		{
			//don't die just because we can't generate the home url
			//we might not even use it or otherwise care.
			try
			{
				$url = vB5_Route::buildHomeUrl('fullurl');
			}
			catch (Exception $e)
			{
				$url = '';
			}
		}

		$url = $this->registry->cleaner->xssClean($url);
		return $url;
	}
}

// #############################################################################
// data registry class

/**
* Class to store commonly-used variables
*
* @package	vBulletin
* @date		$Date: 2024-05-17 16:46:38 -0700 (Fri, 17 May 2024) $
*/
class vB_Registry
{
	use vB_Trait_NoSerialize;

	// general objects
	/**
	* Datastore object.
	*
	* @var	vB_Datastore
	*/
	var $datastore;

	/**
	* Input cleaner object.
	*
	* @var	vB_Input_Cleaner
	*/
	var $input;

	/**
	* Database object.
	*
	* @var	vB_Database
	*/
	var $db;

	// user/session related
	/**
	* Array of info about the current browsing user. In the case of a registered
	* user, this will be results of fetch_userinfo(). A guest will have slightly
	* different entries.
	*
	* @var	array
	*/
	var $userinfo;

	/**
	* Session object.
	*
	* @var vB_Session
	*/
	var $session;

	/**
	* Array of do actions that are exempt from checks
	*
	* @var array
	*/
	var $csrf_skip_list = [];

	// configuration
	/**
	* Array of data from config.php.
	*
	* @var	array
	*/
	var $config;

	// GPC input
	/**
	* Array of data that has been cleaned by the input cleaner.
	*
	* @var	array
	*/
	var $GPC = [];

	/**
	* Array of booleans. When cleaning a variable, you often lose the ability
	* to determine if it was specified in the user's input. Entries in this
	* array are true if the variable existed before cleaning.
	*
	* @var	array
	*/
	var $GPC_exists = [];

	/**
	* The URL of the currently browsed page.
	*
	* @var	string
	*/
	var $scriptpath;

	/**
	 * The request basepath.
	 * Use for <base>
	 *
	 * @var string
	 */
	var $basepath;

	/**
	* Similar to the URL of the current page, but expands some items and includes
	* data submitted via POST. Used for Who's Online purposes.
	*
	* @var	string
	*/
	var $wolpath;

	/**
	* The URL of the current page, without anything after the '?'.
	*
	* @var	string
	*/
	var $script;

	/**
	* Generally the URL of the referring page if there is one, though it is often
	* set in various places of the code. Used to determine the page to redirect
	* to, if necessary.
	*
	* @var	string
	*/
	var $url;


	/*
	 *	Bitfields are expected to be always defined on the registry
	 *	But we should start trimming them to what is actually used (and
	 *	transition to using the datastore class directly)
	 */

	// usergroup permission bitfields
	/**#@+
	* Bitfield arrays for usergroup permissions.
	*
	* @var	array
	*/
	public $bf_ugp;
	// $bf_ugp_x is a reference to $bf_ugp['x']
	public $bf_ugp_albumpermissions;
	public $bf_ugp_adminpermissions;
	public $bf_ugp_createpermissions;
	public $bf_ugp_forumpermissions;
	public $bf_ugp_forumpermissions2;
	public $bf_ugp_genericoptions;
	public $bf_ugp_genericpermissions;
	public $bf_ugp_genericpermissions2;
	public $bf_ugp_pmpermissions;
	public $bf_ugp_signaturepermissions;
	public $bf_ugp_socialgrouppermissions;
	public $bf_ugp_usercsspermissions;
	public $bf_ugp_wolpermissions;
	public $bf_ugp_visitormessagepermissions;
	/**#@-*/

	// misc bitfield arrays
	/**#@+
	* Bitfield arrays for miscellaneous permissions and options.
	*
	* @var	array
	*/
	public $bf_misc;
	// $bf_misc_x is a reference to $bf_misc['x']
	public $bf_misc_adminoptions;
	public $bf_misc_bbcodeoptions;
	public $bf_misc_calmoderatorpermissions;
	public $bf_misc_feedoptions;
	public $bf_misc_forumoptions;
	public $bf_misc_hvcheck;
	public $bf_misc_intperms;
	public $bf_misc_languageoptions;
	public $bf_misc_moderatoremailnotificationoptions;
	public $bf_misc_moderatornotificationoptions;
	public $bf_misc_moderatorpermissions;
	public $bf_misc_moderatorpermissions2;
	public $bf_misc_prefixoptions;
	public $bf_misc_regoptions;
	public $bf_misc_socialgroupoptions;
	public $bf_misc_usernotificationoptions;
	public $bf_misc_useroptions;
	/**#@-*/


	/*
	 *	The Datastore class will only set not bitfield items on load if there is a member variable defined.  This
	 *	avoids warnings on PHP8.2 but also allows us to better track what is and is not being used via the
	 *	legacy registry. We should start elimintating these and eventually get rid of this class.
	 */

	/**#@+
	 * Results for specific entries in the datastore.
	 *
	 * @var	mixed	Mixed, though mostly arrays.
   */

	//confirmed to be accessed from the legacy registry object.
	public $bbcodecache = null;
	public $languagecache = null;
	public $loadcache = null;
	public $options = null;
	public $products = null;
	public $smiliecache = null;
	public $usergroupcache = null;

	//set, but does not appear to be read.  Should see if we can remove these items.
	public $attachmentcache = null;
	public $eventcache = null;
	public $iconcache = null;
	public $wol_spiders = null;


	//Leaving the used but not from this class datastore items commented out to
	//document that they have been checked.
	//not used from registry.  Can probably remove these member variables entirely.
//	public $banemail = null;
//	public $birthdaycache = null;
//	public $cron = null;
//	public $mailqueue = null;
//	public $maxloggedin = null;
//	public $noticecache = null;
//	public $ranks = null;
//	public $stylecache = null;
//	public $userstats = null;


	//These datastore items have never been part of the registry class
	//'defaultchannelpermissions',
	//'hooks',
	//'miscoptions',
	//'locations',
	//'prefixcache',
	//'profilefield',
	//'publicoptions',
	//'pwschemes',
	//'routes',
	//'spiders',
	//'textonlyTemplates',
	//'themeImportProgress',
	//'vBChannelTypes',
	//'vBNotificationEvents',
	//'vBNotificationTypes',
	//'vBNodevoteMetadata',
	//'vBUgChannelPermissionsFrom',

	/**#@-*/

	/**#@+
	* Miscellaneous variables
	*
	* @var	mixed
	*/
	var $bbcode_style = ['code' => -1, 'html' => -1, 'php' => -1, 'quote' => -1];
	var $templatecache = [];
	var $versionnumber;
	var $debug;
	public $stylevars;

	/**
	 * Shutdown handler
	 *
	 * @var vB_Shutdown
	 */
	var $shutdown;
	/**#@-*/

	/**
	* For storing global information specific to the CMS
	*
	* @var	array
	*/
	var $vbcms = [];


	var $cleaner = null;

	/**
	* Constructor -
	* and calls and instance of the vB_Input_Cleaner class
	*/
	function __construct()
	{
		// initialize the input handler
		$this->cleaner =& vB::getCleaner();
		$this->input = new vB_Input_Cleaner($this);

		// initialize the shutdown handler
		$this->shutdown = vB_Shutdown::instance();

		$this->config =& vB::getConfig();

		$this->csrf_skip_list = (defined('CSRF_SKIP_LIST') ? explode(',', CSRF_SKIP_LIST) : []);
	}
}

/**
* This class implements variable-registration-based template evaluation,
* wrapped around the legacy template format. It will be extended in the
* future to support the new format/syntax without requiring changes to
* code written with it.
*
* Currently these vars are automatically registered: $vbphrase
*    $show, $bbuserinfo, $session, $vboptions
*
* @package	vBulletin
*/
class vB_Template
{
	use vB_Trait_NoSerialize;

	/**
	 * Preregistered variables.
	 * Variables can be preregistered before a template is created and will be
	 * imported and reset when the template is created.
	 * The array should be in the form [template_name => [key => variable]]
	 *
	 * @var array mixed
	 */
	protected static $pre_registered = [];
	protected static $placeHolders;

	/**
	* Name of the template to render
	*
	* @var	string
	*/
	protected $template = '';

	/**
	 * Array of registered variables.
	 * @see vB_Template::preRegister()
	*
	* @var	array
	*/
	protected $registered = [];

	/**
	 * Whether the globally accessible vars have been registered.
	 *
	 * @var bool
	 */
	protected $registered_globals;

	/**
	* Debug helper to count how many times a template was used on a page.
	*
	* @var	array
	*/
	public static $template_usage = [];

	/**
	* Debug helper to list the templates that were fetched out of the database (not cached properly).
	*
	* @var	array
	*/
	public static $template_queries = [];

	/**
	 * Factory method to create the template object.
	 * Will choose the correct template type based on the request. Any preregistered
	 * variables are also registered and cleared from the preregister cache.
	*
	* @param	string	Name of the template to be evaluated
	* @return	vB_Template	Template object
	*/
	public static function create($template_name)
	{
		$template = new vB_Template($template_name);

		if (isset(self::$pre_registered[$template_name]))
		{
			$template->quickRegister(self::$pre_registered[$template_name]);
		}
		return $template;
	}

	/**
	 * vB_Template constructor.
	 * Protected constructor to enforce the factory pattern.
	 * Ensures the chrome templates have been processed.
	 * @param $template_name
	 */
	protected function __construct($template_name)
	{
		$this->template = $template_name;
	}

	/**
	* Returns the name of the template that will be rendered.
	*
	* @return	string
	*/
	public function get_template_name()
	{
		return $this->template;
	}

	/**
	 * Preregisters variables before template instantiation.
	 *
	 * @param	string	The name of the template to register for
	 * @param	array	The variables to register
	 */
	public static function preRegister($template_name, array $variables = NULL)
	{
		if ($variables)
		{
			if (!isset(self::$pre_registered[$template_name]))
			{
				self::$pre_registered[$template_name] = [];
			}

			self::$pre_registered[$template_name] = array_merge(self::$pre_registered[$template_name], $variables);
		}
	}

	/**
	* Register a variable with the template.
	*
	* @param	string	Name of the variable to be registered
	* @param	mixed	Value to be registered. This may be a scalar or an array.
	 * @param	bool	Whether to overwrite existing vars
	 * @return	bool	Whether the var was registered
	*/
	public function register($name, $value, $overwrite = true)
	{
		if (!$overwrite AND $this->is_registered($name))
		{
			return false;
		}

		$this->registered[$name] = $value;

		return true;
	}

	/**
	 * Registers an array of variables with the template.
	 *
	 * @param	mixed	Assoc array of name => value to be registered
	 */
	public function quickRegister($values, $overwrite = true)
	{
		if (!is_array($values))
		{
			return;
		}

		foreach ($values AS $name => $value)
		{
			$this->register($name, $value, $overwrite);
		}
	}

	/**
	 * Registers a named global variable with the template.
	 *
	 * @param	string	The global to register
	 * @param	bool	Whether to overwrite on a name collision
	 */
	public function register_global($name, $overwrite = true)
	{
		if (!$overwrite AND $this->is_registered($name))
		{
			return false;
		}

		return isset($GLOBALS[$name]) ? $this->register_ref($name, $GLOBALS[$name]) : false;
	}

	/**
	 * Registers a reference to a variable.
	 *
	 * @param	string	Name of the variable to be registered
	 * @param	mixed	Value to be registered. This may be a scalar or an array
	 * @param	bool	Whether to overwrite existing vars
	 * @return	bool	Whether the var was registered
	 */
	public function register_ref($name, &$value, $overwrite = true)
	{
		if (!$overwrite AND $this->is_registered($name))
		{
			return false;
		}

		$this->registered[$name] =& $value;

		return true;
	}

	/**
	* Unregisters a previously registered variable.
	*
	* @param	string	Name of variable to be unregistered
	* @return	mixed	Null if the variable wasn't registered, otherwise the value of the variable
	*/
	public function unregister($name)
	{
		if (isset($this->registered[$name]))
		{
			$value = $this->registered[$name];
			unset($this->registered[$name]);
			return $value;
		}
		else
		{
			return null;
		}
	}

	/**
	 * Determines if a named variable is registered.
	*
	* @param	string	Name of variable to check
	* @return	bool
	*/
	public function is_registered($name)
	{
		return isset($this->registered[$name]);
	}

	/**
	* Return the value of a registered variable or all registered values
	 * If no variable name is specified then all variables are returned.
	*
	* @param	string	The name of the variable to get the value for.
	* @return	mixed	If a name is specified, the value of the variable or null if it doesn't exist.
	*/
	public function registered($name = '')
	{
		if ($name !== '')
		{
			return (isset($this->registered[$name]) ? $this->registered[$name] : null);
		}
		else
		{
			return $this->registered;
		}
	}

	/**
	* Automatically register the page-level templates footer, header,
	* and headinclude based on their global values.
	*/
	public function register_page_templates()
	{
		// Only method forum requires these templates
		if (defined('VB_API') AND VB_API === true AND VB_ENTRY !== 'forum.php')
		{
			return true;
		}

		$this->register_global('footer');
		$this->register_global('header');
		$this->register_global('headinclude');
		$this->register_global('headinclude_bottom');
	}

	/**
	 * Register globally accessible vars.
	 *
	 * @param bool $final_render				- Whether we are rendering the final response
	*/
	protected function register_globals($final_render = false)
	{
		if ($this->registered_globals)
		{
			return;
		}
		$this->registered_globals = true;

		global $vbulletin, $style;

		$session = vB::getCurrentSession();
		$this->register_ref('bbuserinfo', $session->fetch_userinfo());
		// Currently datastore::init_registry() does not gurantee that options
		// went through check_options(). It's not exactly straight forward, but
		// in overly-simplified summary if the VERY FIRST fetch from the datastore
		// is not the options, it happens (call stack looks like
		// ds::fetch() => ds::register() => vB::getRegistry() => ds::init_registry())
		// Trying to untangle that is nigh impossible atm, so let's just circumvent
		// by fetching options from the datastore directly, which hopefully should
		// BETTER ensure that the pseudo-options like "simpleversion" are set.
		//$this->register_ref('vboptions', $vbulletin->options);
		// However, let's ensure that the frontend parser and this backend are using
		// the same set of publicly visible options via fetching from the options API.
		// Note, we're not handling the case if/when options::fetch() may error out.
		// This is because options::fetch() is whitelisted, so I think the only way
		// it could error is if there's something seriously wrong with the system
		// which will cause other problems before this point. We could wrap this
		// in a try/catch and fallback to $vbulletin->options if we're being extra
		// cautious.
		['options' => $options] = vB_Api::instanceInternal('options')->fetch();
		$this->register_ref('vboptions', $options);

		$allvars = $session->getAllVars();
		$this->register_ref('session', $allvars);

		$this->register_global('vbphrase');
		$this->register_global('vbcollapse');
		$this->register_global('style');

		$this->register_global('show', false);
		$this->register('simpleversion', SIMPLE_VERSION, true);
	}


	/**
	 * Renders the template.
	 *
	 * @param	boolean	Whether to suppress the HTML comment surrounding option (for JS, etc)
	 * @return	string	Rendered version of the template
	 */
	public function render($suppress_html_comments = false, $final_render = false, $nopermissioncheck = false)
	{
		// Register globally accessible data
		$this->register_globals($final_render);

		// Render the output in the appropriate format
		$final = $this->render_output($suppress_html_comments, $nopermissioncheck);

		if (!empty(self::$placeHolders))
		{
			foreach (self::$placeHolders AS $placeHolder => $text)
			{
				$final = str_replace($placeHolder, $text, $final);
			}
		}

		return $final;
	}


	/**
	 * Renders the output after preperation.
	 * @see vB_Template::render()
	 *
	 * @param boolean	Whether to suppress the HTML comment surrounding option (for JS, etc)
	 * @return string
	 */
	protected function render_output($suppress_html_comments = false, $nopermissioncheck = false)
	{
		//This global statement is here to expose $vbulletin to the templates.
		//It must remain in the same function as the template eval
		global $vbulletin;
		extract($this->registered, EXTR_SKIP | EXTR_REFS);

		$template_code = vB_Library::instance('Template')->fetch($this->template, vB::getCurrentSession()->get('styleid'), $nopermissioncheck);

		if ($template_code['compiletype'] == 'textonly')
		{
			$placeholder = '<##-- ' . $this->template . '--#>';
			self::$placeHolders[$placeholder] = $template_code['template'];
			$final_rendered = $template_code['template'];
		}
		else
		{
			eval($template_code['template']);
		}

		if ($vbulletin->options['addtemplatename'] AND !$suppress_html_comments)
		{
			$template_name = preg_replace('#[^a-z0-9_]#i', '', $this->template);
			if (substr($this->template, -4) == '.css')
			{
				$final_rendered = "/* BEGIN TEMPLATE: $template_name */\n$final_rendered\n/* END TEMPLATE: $template_name */";
			}
			else
			{
				$final_rendered = "<!-- BEGIN TEMPLATE: $template_name -->\n$final_rendered\n<!-- END TEMPLATE: $template_name -->";
			}
		}

		return $final_rendered;
	}

	/**
	* Returns a single template from the templatecache or the database and returns
	* the raw contents of it. Note that text will be escaped for eval'ing.
	*
	* @param	string	Name of template to be fetched
	*
	* @return	string
	*/
	public static function fetch_template_raw($template_name)
	{
		$template_code = vB_Api::instanceInternal('template')->fetch($template_name);

		if (strpos($template_code, '$final_rendered') !== false)
		{
			return preg_replace('#^\$final_rendered = \'(.*)\';$#s', '\\1', $template_code);
		}
		else
		{
			return $template_code;
		}
	}
}

// #############################################################################
// TODO: replace with vB_String::htmlSpecialCharsUni
/**
* Unicode-safe version of htmlspecialchars()
*
* @param	string	Text to be made html-safe
*
* @return	string
*/
function htmlspecialchars_uni($text, $entities = true)
{
	$text = strval($text);
	if ($entities)
	{
		$text = preg_replace_callback(
			'/&((#([0-9]+)|[a-z]+);)?/si',
			'htmlspecialchars_uni_callback',
			$text
		);
	}
	else
	{
		$text = preg_replace(
			// translates all non-unicode entities
			'/&(?!(#[0-9]+|[a-z]+);)/si',
			'&amp;',
			$text
		);
	}

	return str_replace(
		// replace special html characters
		['<', '>', '"'],
		['&lt;', '&gt;', '&quot;'],
			$text
	);
}

function htmlspecialchars_uni_callback($matches)
{
 	if (count($matches) == 1)
 	{
 		return '&amp;';
 	}

	if (strpos($matches[2], '#') === false)
	{
		// &gt; like
		if ($matches[2] == 'shy')
		{
			return '&shy;';
		}
		else
		{
			return "&amp;$matches[2];";
		}
	}
	else
	{
		// Only convert chars that are in ISO-8859-1
		if (($matches[3] >= 32 AND $matches[3] <= 126)
			OR
			($matches[3] >= 160 AND $matches[3] <= 255))
		{
			return "&amp;#$matches[3];";
		}
		else
		{
			return "&#$matches[3];";
		}
	}
}


function css_escape_string($string)
{
	static $map = null;
	//url(<something>) is valid.

	$checkstr = strtolower(trim($string));
	$add_url = false;
	if ((substr($checkstr, 0, 4) == 'url(') AND (substr($checkstr,-1,1) == ')'))
	{
		//we need to leave the "url()" part alone.
		$add_url = true;
		$string = trim($string);
		$string = substr($string,4, strlen($string)- 5);
		if ((($string[0] == '"') AND (substr($checkstr,-1,1) == '"'))
			OR
			(($string[0] == "'") AND (substr($checkstr,-1,1) == "'")))
		{
			$string = substr($string,1, strlen($string)- 2);
		}
	}

	if (is_null($map))
	{
		$chars = [
			'\\', '!', '@', '#', '$', '%', '^',  '*', '"', "'",
			'<', '>', ',', '`', '~','/','&', '.',':', ')','(', ';'
		];

		foreach ($chars as $char)
		{
			$map[$char] = '\\' . dechex(ord($char)) . ' ';
		}
	}

	$string = str_replace(array_keys($map), $map, $string);

	//add back the url() if we need it.
	if ($add_url)
	{
		$string = 'url(\'' . $string . '\')';
	}
	return $string;
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 116130 $
|| #######################################################################
\*=========================================================================*/
