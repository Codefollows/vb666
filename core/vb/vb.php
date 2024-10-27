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
 * The vB core class.
 * Everything required at the core level should be accessible through this.
 *
 * The core class performs initialisation for error handling, exception handling,
 * application instantiation and optionally debug handling.
 *
 * @package vBulletin
 */
abstract class vB
{
	/*Properties====================================================================*/

	/**
	 * Whether the framework has been initialized.
	 *
	 * @var bool
	 */
	private static $initialized;

	/**
	 * Whether content headers have been sent.
	 * This only tracks the Content-Type and Content-Length headers.  When a view or
	 * other system sends content headers, it should let vB know with
	 * vB::contentHeadersSent(true).
	 *
	 * @var bool
	 */
	private static $content_headers_sent = false;

	/**
	 * Location of the config file relative to the site root -- mostly here to overload for testing
	 * @var string
	 */
	private static $config_file = "includes/config.php";

	/**
	 *
	 * @var array
	 */
	private static $config = null;

	/**
	 *
	 * @var array
	 */
	private static $sensitive_config = null;

	/**
	 *
	 * @var vB_dB_Assertor
	 */
	private static $db_assertor = null;

	/**
	 *
	 * @var vB_Datastore
	 */
	private static $datastore = null;
	private static $usercontexts = [];

	private static $products = null;
	private static $hooks = null;
	private static $options = null;

	/**
	 *
	 * @var vB_Session
	 */
	private static $currentSession = null;

	/**
	 *
	 * @var vB_Request
	 */
	private static $request = null;

	/**
	 *
	 * @var vB_Cleaner
	 */
	private static $cleaner = null;
	private static $string = null;

	protected static $skipShutdown = false;

	//autoloader information
	protected static bool $usephar = false;
	protected static string $pharpath = '';
	protected static string $vbpath = '';
	protected static string $packagepath = '';
	protected static array $autoloadInfo = [];

	/**
	 *	We use warnings loosely here to be any of the myriad non fatal errors
	 *	We only log warning that would ordinarily be reported per the
	 *	error_reporting settings.
	 *
	 * @var loggedWarnings
	 */
	protected static $loggedWarnings = [];

	/*Initialisation================================================================*/

	/**
	 * Initializes the vB framework.
	 * All framework level objects and services are created so that they are available
	 * throughout the application.  This is only done once even if the function is called
	 * multiple times.
	 */
	public static function init($relative_path = false)
	{
		if (self::$initialized)
		{
			return;
		}

/*
 *	Set a bunch of constants.
 */

		//We were getting CWD defined as something like '<install location>/vb5/..'
		//This causes problems when uploading a default avatar, where we need to parse the path.
		//It's much easier to decode if the path is just <install location>
		if (!defined('CWD'))
		{
			if (is_link(dirname($_SERVER["SCRIPT_FILENAME"])))
			{
				$cwd = dirname($_SERVER["SCRIPT_FILENAME"]) . DIRECTORY_SEPARATOR . 'core';
			}
			else
			{
				$cwd = dirname(__FILE__);
			}

			if (($pos = strrpos($cwd, DIRECTORY_SEPARATOR)) === false)
			{
				//we can't figure this out.
				define('CWD', dirname(__FILE__) . DIRECTORY_SEPARATOR . '..');
			}
			else
			{
				define('CWD', substr($cwd, 0, $pos) );
			}
		}

		if (!defined('DIR'))
		{
			define('DIR', CWD);
		}

		if (!defined('VB_PKG_PATH'))
		{
			define('VB_PKG_PATH', realpath(DIR . '/packages') . '/');
		}

		if (!defined('VB_ENTRY'))
		{
			define('VB_ENTRY', 1);
		}

		require_once (DIR . '/includes/version_base_vbulletin.php');

/*
 *	Clean up the php environment
 */
		//this shouldn't be a problem any longer but it doesn't hurt anything
		if (isset($_REQUEST['GLOBALS']) OR isset($_FILES['GLOBALS']))
		{
			echo 'Request tainting attempted.';
			exit(4);
		}

		@ini_set('pcre.backtrack_limit', -1);

		@date_default_timezone_set(date_default_timezone_get());

		//Clean up super globals to get to a state that vB expects.  Should probably isolate this to the
		//web session and/or front end code since we shoudln't be accessing these variables inside the
		//API (they won't exist for command line scritps)

		// overwrite GET[x] and REQUEST[x] with POST[x] if it exists (overrides server's GPC order preference)
		if (isset($_SERVER['REQUEST_METHOD']) AND $_SERVER['REQUEST_METHOD'] == 'POST')
		{
			foreach (array_keys($_POST) AS $key)
			{
				if (isset($_GET[$key]))
				{
					$_GET[$key] = $_REQUEST[$key] = $_POST[$key];
				}
			}
		}

		// deal with cookies that may conflict with _GET and _POST data, and create our own _REQUEST with no _COOKIE input
		foreach (array_keys($_COOKIE) AS $varname)
		{
			unset($_REQUEST[$varname]);
			if (isset($_POST[$varname]))
			{
				$_REQUEST[$varname] = &$_POST[$varname];
			}
			else if (isset($_GET[$varname]))
			{
				$_REQUEST[$varname] = &$_GET[$varname];
			}
		}

		//set a flag to denote that this is an ajax request.  We shouldn't be setting this to the superglobals
		//but the legacy code depends on it.
		if (
			($_SERVER['REQUEST_METHOD'] ?? '') == 'POST' AND
			($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') == 'XMLHttpRequest' AND
			empty($_REQUEST['forcenoajax'])
		)
		{
			$_POST['ajax'] = $_REQUEST['ajax'] = 1;
		}

/*
 *	Register Callback functions
 */

		//we want to have an exception handler defined (the default handler has bad habit of
		//leaking information that shouldn't be leaked), but we don't want to override any frontend handler,
		//that might be set.  PHP doesn't allow us to check the current handler, but we can change it and
		//then change it back if we don't like the result.
		if (null !== set_exception_handler(['vB', 'handleException']))
		{
			restore_exception_handler();
		}

		if (null !== set_error_handler(['vB', 'handleError']))
		{
			restore_error_handler();
		}

		// Set unserializer to use spl registered autoloader
		ini_set('unserialize_callback_func', 'spl_autoload_call');

		// Set shutdown function
		register_shutdown_function(['vB', 'shutdown']);

		self::initAutoloader(DIR);

		// Done
		self::$initialized = true;
	}

	private static function initAutoloader(string $dir) : void
	{
		self::$pharpath = 'phar://' . $dir . '/vb/vb.phar/';
		self::$vbpath = $dir . '/';
		self::$usephar = file_exists(self::$pharpath);

		//This value is shared between the autoloader and the packages code. Need to figure out how to avoid
		//duplicated logic while keeping the autoloader code somewhat seperated (for one thing the utility classes/tests
		//depend on it but we explicity don't want to call init for those tests).
		self::$packagepath = realpath($dir . '/packages') . '/';

		// Set class autoloader
		spl_autoload_register(Closure::fromCallable(['vB', 'autoload']));
	}

	public static function silentWarnings()
	{
		set_error_handler(['vB', 'handleErrorQuiet']);
	}

	public static function normalWarnings()
	{
		set_error_handler(['vB', 'handleError']);
	}

	public static function getLoggedWarnings()
	{
		return self::$loggedWarnings;
	}

	/*Response======================================================================*/

	/**
	 * Gets or sets whether content headers have been sent.
	 *
	 * @param bool $sent						- If true, the headers have been sent
	 */
	public function contentHeadersSent($sent = false)
	{
		if ($sent)
		{
			self::$content_headers_sent = true;
		}

		return self::$content_headers_sent;
	}



	/*Autoload======================================================================*/

	/**
	 * Autloads a class file when required.
	 * Classnames are broken down into segments which are used to resolve the class
	 * directory and filename.
	 *
	 * If the first segment matches 'vB' then it is in /vB else it is in
	 * /packages/segment/
	 *
	 * @param string $classname	- The name of the class to load
	 */
	private static function autoload(string $classname) : void
	{
		if (!$classname)
		{
			return;
		}

		$filename = '';
		$fclassname = strtolower($classname);

		if (str_starts_with($fclassname, 'vb_'))
		{
			if (self::$usephar)
			{
				$filename = self::$pharpath;
				//not a great way to manage this without altering the string.
				$fclassname = substr($fclassname, 3);
			}
			else
			{
				$filename = self::$vbpath;
			}
		}
		else if (str_starts_with($fclassname, 'vb5_'))
		{
			$filename = self::$vbpath;
		}
		else
		{
			$filename = self::$packagepath;
		}

		$filename .= str_replace('_', '/', $fclassname) . '.php';
		$exists = file_exists($filename);

		//This is more expensive than actually finding the filename from the class and
		//only exists for some debug reporting.  It's not objectively that much time/memory
		//but we should consider skipping it if we aren't reporting (but reading the debug
		//state from the config is tricky.
		self::$autoloadInfo[$classname] = [
			'loader' => 'core',
			'filename' => $filename,
			'loaded' => $exists,
		];

		// Include the required class file
		if (!file_exists($filename))
		{
			return;
		}
		require($filename);
	}

	/**
	 * Returns debug autoload info
	 *
	 * @return array Array of debug info containing 'classes' and 'count'
	 */
	public static function getAutoloadInfo()
	{
		return self::$autoloadInfo;
	}

	/**
	 * Allows autoloaders to be registered before the vb autoloader.
	 *
	 * @param callback $callback
	 */
	public static function autoloadPreregister($add_callback)
	{
		$registered = spl_autoload_functions();

		foreach ($registered AS $callback)
		{
			spl_autoload_unregister($callback);
		}

		array_unshift($registered, $add_callback);

		foreach ($registered AS $callback)
		{
			spl_autoload_register($callback);
		}
	}

	/**
	 * Notify the vB object not to do the normal shutdown
	 *
	 *	@param	boolean		whether to skip the shutdown- yes means don't do it.
	 */
	public static function skipShutdown($skip = true)
	{
		self::$skipShutdown = $skip;
	}


	/**
	 * Shutdown - primarily writing session information. May be some delayed queries.
	 */
	public static function shutdown()
	{
		try
		{
			if (self::$skipShutdown)
			{
				//always shutdown cache
				vB_Cache::instance(vB_Cache::CACHE_FAST)->shutdown();
				vB_Cache::instance(vB_Cache::CACHE_LARGE)->shutdown();
				vB_Cache::instance(vB_Cache::CACHE_STD)->shutdown();
			}
			else
			{
				vB_Shutdown::instance()->shutdown();
			}
		}
		//there isn't much we can do at this point, and having error output causes more problems
		//than it solves.  So log it and hope for the best.
		catch(Error $e)
		{
			if (ini_get('log_errors'))
			{
				$message = $e->getMessage() . "\n## " . $e->getFile() .
					'(' . $e->getLine() . ") Exception Thrown \n" . $e->getTraceAsString();
				error_log($message);
			}

		}
		catch(Exception $e)
		{
			if (ini_get('log_errors'))
			{
				$message = $e->getMessage() . "\n## " . $e->getFile() .
					'(' . $e->getLine() . ") Exception Thrown \n" . $e->getTraceAsString();
				error_log($message);
			}
		}
	}

	/**
	 * This is a temporary getter for retrieving the vbulletin object while we still needed
	 * TODO: remove this method
	 *
	 * @global vB_Registry $vbulletin
	 * @return vB_Registry
	 */
	public static function &get_registry($skipDatastoreOptions = false)
	{
		global $vbulletin;

		if (!isset($vbulletin))
		{
			//move some initialization of the registry from the legacy bootstrap.
			require_once(DIR . '/includes/class_core.php');
			$vbulletin = new vB_Registry();

			//this is the *only* place we should call getDBConnection!!!
			$vbulletin->db = vB::getDBAssertor()->getDBConnection();

			$vbulletin->datastore = vB::getDatastore();

			//force load some values the vbulletin object.  Otherwise init.php can crap out.
			if (!$vbulletin->datastore->registerCount() AND !$skipDatastoreOptions)
			{
				$vbulletin->datastore->getValue('options');
			}
			$vbulletin->datastore->init_registry();

			$request = self::getRequest();
			if ($request)
			{
				//a bit of a hack, but we only have URL values if this comes in from the web
				//we may need to sort things out better so that we do something with these
				//functions when we don't have a web request, but this is simpler and this
				//is temporary code anyway.
				if ($request instanceof vB_Request_Web)
				{
					$cleaner = self::getCleaner();

					// store the current script
					$vbulletin->script = $_SERVER['SCRIPT_NAME'];

					// store the scriptpath
					$vbulletin->scriptpath = $request->getScriptPath();
				}
			}
		}

		return $vbulletin;
	}

	public static function getProducts()
	{
		if (!isset(self::$products))
		{
			//if hooks are disabled, act like we don't have any products.  Even if we do.
			$productstore = [];

			$datastore = self::getDatastore();
			$hooksEnabled = $datastore->getOption('enablehooks');
			if ($hooksEnabled AND !(defined('DISABLE_HOOKS') AND DISABLE_HOOKS))
			{
				$productstore = $datastore->getValue('products');
				if (empty($productstore))
				{
					/*
						At the very least, it should have 'vbulletin' as a product. If it's empty, suspect that the datastore
						was emptied for some reason and attempt to rebuild.
						Without this, the admincp nav bar (& possibly other parts of admincp, but did not check) fails to load.
					*/
					try
					{
						vB_Library::instance('product')->buildProductDatastore();
						$productstore = $datastore->getValue('products');
					}
					catch(Exception $e)
					{
						/*
							Not sure when this might happen, but if buildProductDatastore() failed, let's continue on and hope for the best rather than throw an exception here.
						*/
					}
				}
			}
			self::$products = new vB_Products($productstore, VB_PKG_PATH, true);
		}

		return self::$products;
	}

	public static function getHooks()
	{
		if (!isset(self::$hooks))
		{
			try
			{
				$options = self::getDatastore()->getValue('options');
				if ((defined('DISABLE_HOOKS') AND DISABLE_HOOKS) OR !$options['enablehooks'])
				{
					self::$hooks = new vB_Utility_Hook_Disabled();
				}
				else
				{
					$hooklist = self::getProducts()->getHookClasses();
					self::$hooks = new vB_Utility_Hook_Live($hooklist);
				}
			}
			catch(Exception $e)
			{
				//if we don't have a DB then treat hooks as disabled
				//but don't cache the result in case we later set the DB up.
				//This is primarily an issue for scripting and/or installation code.
				if ($e->getMessage() == 'no_vb5_database')
				{
					return new vB_Utility_Hook_Disabled();
				}
				else
				{
					throw $e;
				}
			}
		}

		return self::$hooks;
	}



	/**
	 * Returns a by-reference the config object
	 * @return array
	 */
	public static function &getConfig()
	{
		if (!isset(self::$config))
		{
			self::fetch_config();
		}

		return self::$config;
	}

	/**
	 * Returns sensitive config options.
	 *
	 * @return array
	 */
	protected static function &getSensitiveConfig()
	{
		// TODO: Do any hooks need this information?
		if (!isset(self::$sensitive_config))
		{
			self::fetch_config();
		}

		return self::$sensitive_config;
	}

	public static function sensitiveConfigOverride($override)
	{
		//make sure it's loaded;
		self::getSensitiveConfig();
		self::$sensitive_config = array_replace_recursive(self::$sensitive_config, $override);
	}

	public static function setConfigFile($config_file)
	{
		self::$config_file = $config_file;
	}

	public static function getConfigFile()
	{
		return self::$config_file;
	}

	/**
	* Fetches database/system configuration
	* Code extracted from vB_Registry::fetch_config (class_core)
	*/
	private static function fetch_config()
	{
		// Set the default values here.
		$default['Cache']['class'] = vB_Cache::getDefaults();

		// parse the config file
		if (file_exists(DIR . '/' . self::$config_file))
		{
			include(DIR . '/' . self::$config_file);
		}
		else
		{
			if (defined('STDIN')) exit(5);
			die('<br /><br /><strong>Configuration</strong>: includes/config.php does not exist. For a new install click <a href="core/install/makeconfig.php">here</a>');
		}

		// TODO: this should be handled with an exception, the backend shouldn't produce output
		if (empty($config))
		{
			// config.php exists, but does not define $config
			if (defined('STDIN')) exit(5);
			die('<br /><br /><strong>Configuration</strong>: includes/config.php exists, but is not in the 3.6+ format. Please convert your config file via the new config.php.new.');
		}

		// make the usepconnect config option optional (VBV-12036)
		if (empty($config['MasterServer']['usepconnect']))
		{
			$config['MasterServer']['usepconnect'] = 0;
		}
		if (empty($config['SlaveServer']['usepconnect']))
		{
			$config['SlaveServer']['usepconnect'] = 0;
		}

		/*
			Pull sensitive DB configs out of the global config.
			There's really no sane way to have $sensitive_config & $config share references AND contain
			different data without going nuts, so we'll need to minimize their intersection.
			Ex.
				self::$sensitive_config =& self::$config;
				works such that anything added to self::$config later will be added to sensitive, but you cannot unset anything
				from self::$config without it being unset from self::$sensitive_config, and you cannot set anything to self::$sensitive_config
				without it being set to self::$config.

				foreach (self::$config AS $key => &$value)
				{
					self::$sensitive_config[$key] =& $value;
				}
				works such that anything *modified* in self::$config later will be modified in self::$sensitive_config, and you can
				add things to self::$sensitive_config exclusively, but you cannot add/remove something to/from self::$config and have that
				be reflected in self::$sensitive_config
		 */
		self::$sensitive_config = [];
		$sensitives = ['Database', 'MasterServer', 'SlaveServer', 'Mysqli'];
		foreach ($sensitives AS $var_key)
		{
			if (isset($config[$var_key]))
			{
				self::$sensitive_config[$var_key] = $config[$var_key];
				unset($config[$var_key]);
			}
		}
		/*
			Add/scrub any nested sensitive outside of the loop here, ex
			$config['Deep'] = ['notsecret' => 'abc', 'secret' => 'efg'];
			self::$sensitive_config['Deep']['secret'] = $config['Deep']['secret'];
			unset($config['Deep']['secret']);
		 */
		self::$config = array_replace_recursive($default, $config);

		/*
			And of course we have exceptions...
		 */
		$whitelisted = [
			'Database' => [
				// dbname required by sphinxsearch
				'dbname'	        => true,
				'technicalemail'    => true,
				// logfile & errorlogmaxsize required by log_vbulletin_error()
				'logfile'           => true,
				'errorlogmaxsize'   => true,
				'force_sql_mode'    => true,
				'no_force_sql_mode' => true,
			],
			'MasterServer' => [
				'usepconnect' => true, // required by mysql querydefs
			],
			'SlaveServer' => [
				'usepconnect' => true, // for consistency. Set by assertor if empty.
			],
		];
		foreach ($whitelisted AS $outerkey => $inner)
		{
			foreach ($inner AS $innerkey => $junk)
			{
				if (isset(self::$sensitive_config[$outerkey][$innerkey]))
				{
					self::$config[$outerkey][$innerkey] =& self::$sensitive_config[$outerkey][$innerkey];
				}
			}
		}

		// if a configuration exists for this exact HTTP host, use it
		if (isset($_SERVER['HTTP_HOST']) AND isset(self::$config["$_SERVER[HTTP_HOST]"]))
		{
			self::$sensitive_config['MasterServer'] = self::$config["$_SERVER[HTTP_HOST]"];
		}

		// define table and cookie prefix constants
		define('TABLE_PREFIX', trim(isset(self::$sensitive_config['Database']['tableprefix']) ? self::$sensitive_config['Database']['tableprefix'] : ''));
		define('COOKIE_PREFIX', (empty(self::$config['Misc']['cookieprefix']) ? 'bb' : self::$config['Misc']['cookieprefix']));

		// Set debug mode, always default this to false unless it is explicitly set to true (see VBV-2948).
		self::$config['Misc']['debug'] = ((self::$config['Misc']['debug'] ?? false) === true);

		// This will not exist if a pre vB5 config file is still in use. @TODO, change the default when everything can cope with a blank setting.
		if (!isset(self::$config['SpecialUsers']['superadmins']))
		{
			self::$config['SpecialUsers']['superadmins'] = '1'; // Not ideal, but some areas (and the upgrader) choke on a blank setting atm.
		}
	}

	/**
	 * Returns a by-reference the assertor object
	 * @return vB_dB_Assertor
	 */
	public static function &getDbAssertor()
	{
		if (!isset(self::$db_assertor))
		{
			vB_dB_Assertor::init(self::getSensitiveConfig(), self::getConfig());
			self::$db_assertor = vB_dB_Assertor::instance();
		}

		return self::$db_assertor;
	}

	/**
	 * Returns a by-reference the config object
	 * @return vB_Datastore
	 */
	public static function &getDatastore()
	{
		if (!isset(self::$datastore)) {
			$vb5_config = self::getConfig();
			$datastore_class = (!empty($vb5_config['Datastore']['class'])) ? $vb5_config['Datastore']['class'] : 'vB_Datastore';
			self::$datastore = new $datastore_class($vb5_config, self::getDbAssertor());
		}

		return self::$datastore;
	}

	/**
	 * Checks if usercontext is set
	 *
	 * @param <type> $userId
	 * @return bool
	 */
	public static function isUserContextSet($userId = null)
	{
		if (!$userId)
		{
			$session = self::getCurrentSession();
			if (empty($session))
			{
				$return = null;
				return $return;
			}
			$userId = $session->get('userid');
		}

		return isset(self::$usercontexts[$userId]) ? true : false;
	}

	/**
	 * Returns a by-reference the usercontext object specified by $userId
	 * If no userId is specified, it uses the current session user
	 *
	 * @param <type> $userId
	 * @return vB_UserContext
	 */
	public static function &getUserContext($userId = null)
	{
		if (!isset($userId))
		{
			$session = self::getCurrentSession();
			if (empty($session))
			{
				$return = null;
				return $return;
			}
			$userId = $session->get('userid');
		}

		//cap the number of stored usercontexts
		if (count(self::$usercontexts) > 20)
		{
			$loggedUserId = 0;
			$session = self::getCurrentSession();
			if (!empty($session))
			{
				$loggedUserId = $session->get('userid');
			}

			//remove the first 5 keys in the array.  These should be the
			//first five entered.  FIFO is simpler that LRU and should be
			//sufficient for our purposses here.  Do not evict the current
			//user, we're going to keep needing that.
			reset(self::$usercontexts);
			$keystoremove = [];
			for ($i = 0; $i < 5; $i++)
			{
				$key = key(self::$usercontexts);

				//don't just unset here -- that could play merry buggers with the
				//internal array pointer.
				if ($key != $loggedUserId)
				{
					$keystoremove[] = $key;
				}

				//we are guarunteed to have at least five items, no need to check
				//for the end of the array.
				next(self::$usercontexts);
			}

			foreach ($keystoremove AS $key)
			{
				unset(self::$usercontexts[$key]);
			}
		}

		$userId = intval($userId);
		if (!isset(self::$usercontexts[$userId]))
		{
			self::$usercontexts[$userId] = new vB_UserContext($userId, self::getDbAssertor(), self::getDatastore(), self::getConfig());
		}

		return self::$usercontexts[$userId];
	}

	/**
	 *
	 * @param vB_Request $request
	 */
	public static function setRequest(vB_Request $request)
	{
		self::$request = & $request;
	}

	/**
	 *
	 * @return vB_Request
	 */
	public static function &getRequest()
	{
		return self::$request;
	}

	/**
	 * Sets the session as part of initializing the vB system
	 * @param vB_Session $session
	 */
	//$session defaulted because otherwise a "null" value causes problems with the type hint
	//setting to null is used in the unit testing to reset the session value and should not
	//be used in normal app code.
	public static function setCurrentSession(vB_Session $session = null)
	{
		if (self::$currentSession !== null AND $session !== null)
		{
			//if we are changing to a new user, let's reload the permissions. It may be slower, but it should
			//be safer and shouldn't be that common.
			unset(self::$usercontexts[$session->get('userid')]);
		}

		self::$currentSession = &$session;

		// this should be the ONLY way of setting $vbulletin->session and $vbulletin->userinfo attributes
		// old code may set attributes inside session and userinfo, but as we have references the session object
		// should be updated as well
		$vbulletin = & self::get_registry();
		$vbulletin->session = & $session;

		if ($session)
		{
			$vbulletin->userinfo = & $session->fetch_userinfo();
		}
		else
		{
			$vbulletin->userinfo = null;
		}
	}

	/**
	 * @return vB_Session
	 */
	public static function &getCurrentSession()
	{
		return self::$currentSession;
	}


	/**
	 *
	 * @return vB_Cleaner
	 */
	public static function &getCleaner()
	{
		if (!isset(self::$cleaner))
		{
			self::$cleaner = new vB_Cleaner();
		}

		return self::$cleaner;
	}


	/**
	 * Get the string utility class
	 * @return vB_Utility_String
	 */
	public static function getString()
	{
		//we really want to hide how we look up the charset because
		//it's currently a mess and *absolutely* needs to change.
		if (!isset(self::$string))
		{
			//this looks weird because it is.  We want to remove the charset lookup from
			//the user/string/language and move that to a more modular framework.  However
			//until that happens we don't want to be inserting something we want to change
			//everywhere the string class is used.  So let's centralize it here.
			self::$string = new vB_Utility_String(vB_String::getCharset());
		}

		return self::$string;
	}

	/**
	 *	Helper function to create a wordlist object directly from option text.
	 *
	 *	This is the most common way we create word lists.
	 */
	public static function getWordList(string $optioname) : vB_Utility_Wordlist
	{
		return vB_Utility_Wordlist::createFromText(self::getString(), self::getDatastore()->getOption($optioname) ?? '');
	}

	/**
	 *	Get the utility url loader
	 *	@param bool $forceAllowLocal -- this will allways allow connections to
	 *		local IP addresses regardless of the config setting.  This is intended for integration
	 *		with services with a known url that might reside locally (for instant an OAUTH server).
	 *		It should never be used in a context where the url comes from user data.
	 *	@return vB_Utility_Url
	 */
	public static function getUrlLoader($forceAllowLocal = false)
	{
		//we don't want to use a singleton here.
		$config = vB::getConfig();
		$frontendurl = self::getDatastore()->getOption('frontendurl');

		//allowedports is an array of ports we will allow connections to (in addition to 80/443)
		//allowip is a boolean to allow urls with ip addresses instead of hostnames
		//allowlocal is a boolean to allow local ranged ip addresses
		//allowedsiteroot is a string containing the root url for what is "this site".  If
		//	passed we'll allow any url contained in the site regardless of other validation
		//	settings.
		$validationConfig = ['allowedports', 'allowip', 'allowlocal'];
		$validationOptions = [];
		foreach ($validationConfig AS $name)
		{
			if (isset($config['Misc']["upload$name"]))
			{
				$validationOptions[$name] = $config['Misc']["upload$name"];
			}
		}

		$validationOptions['allowedsiteroot'] = $frontendurl;

		if ($forceAllowLocal)
		{
			$validationOptions['allowlocal'] = true;
		}

		return new vB_Utility_Url(vB::getString(), $validationOptions);
	}

	public static function getEventQueue()
	{
		return new vB_Systemevent_Queue(self::getDbAssertor());
	}

	public static function getUndoLog() : vB_Undo_Log
	{
		return new vB_Undo_Log(self::getDbAssertor(), vB::getRequest());
	}

	public static function getHashchecker()
	{
		$defaultIgnoredDirs = [
			DIR . '/cpstyles',
			DIR . '/includes/datastore',
		];

		$datastore = self::getDatastore();

		if ($datastore->getOption('storecssasfile'))
		{
			$cssfilelocation = ltrim($datastore->getOption('cssfilelocation'), '/');
			if (!empty($cssfilelocation))
			{
				/*
				We could potentially list all *.css files and only add those
				to the ignoredFiles list so that any non .css files get flagged.

				Also, we may want to only list the subfolders so that the default
				index.html gets scanned. For now, keeping it simple.
				 */
				$defaultIgnoredDirs[] = DIR . '/' . $cssfilelocation;
			}
		}

		if ($datastore->getOption('cache_templates_as_files'))
		{
			// Template path may be absolute OR relative to core.
			$templatefilelocation = $datastore->getOption('template_cache_path');
			if ($templatefilelocation)
			{
				$realTemplateFilelocation = realpath($templatefilelocation);
				if ($realTemplateFilelocation === false)
				{
					$realTemplateFilelocation = realpath(DIR . '/' . $templatefilelocation);
				}
			}

			if ($realTemplateFilelocation)
			{
				$defaultIgnoredDirs[] = $realTemplateFilelocation;
			}
		}

		$options = [
			'checksumFileDir' => DIR  . '/includes',
			'ignoredFiles' =>  [
				DIR . '/../config.php',
				DIR . '/../config.php.bkp',
				DIR . '/includes/config.php',
				DIR . '/includes/config.php.new',
				DIR . '/install/install.php',
				DIR . '/install/makeconfig.php',
				DIR . '/includes/version_vbulletin.php',
				DIR . '/../.htaccess',
				DIR . '/../robots.txt',
			],
			'ignoredDirs' => $defaultIgnoredDirs,
			'allowedMissing' => [
				DIR . '/../htaccess.txt',
				DIR . '/../robots.txt.new',
			],
			'scanRootDir' => DIR,
		];

		return new vB_Utility_Hashchecker(self::getString(), $options);
	}

	/**
	 *	Utility function to get the class for an extendable class.
	 *
	 *	This creates the classname from a tag value (suitable for use as a config
	 *	options).  Checks that the package, if any, is enabled.  It also checks
	 *	that the class selected actually implements the interface associated
	 *	with the base name.
	 *
	 *	@param string $tag -- the individual tag for the class.  If it only contains
	 *		a single string then it will be treated as a vB core class. If it's of the
	 *		form "prefix:name" we'll treat it as a class in package prefix.  The value
	 *		for name will have the first character uppercased so vB_Some_Class_Foo can
	 *		be specified as "foo" (with the proper base
	 *	@param string $base -- the base part of the string without the front or the end
	 *		so "Some_Class" for a class that should look like vB_Some_Class_Foo.
	 *	@param string $interface.  This can be an actual interface or a base class, but
	 *		The class *must* be an instance of this class/interface to be valid.  This
	 *		is a security validation check to help avoid running classes based on potentially
	 *		passed in data that may not be what they seem. Generally this will be vB_Some_Class
	 *		when the base is 'Some_Class'.
	 */
	//This is based on the API class but removes some of the quirks from that logic
	//we should eventually consolidate that logic back from the API/Library stuff
	//to use this function.
	public static function getVbClassName($tag, $base, $interface)
	{
		$values = explode(':', $tag);
		$package = '';
		if (count($values) == 1)
		{
			$package = 'vB';
			$implementation = $tag;
		}
		else
		{
			list($package, $implementation) = $values;
		}

		$c = $package . '_' . $base . '_' . ucfirst(strtolower($implementation));
		if ($package != 'vB')
		{
			$products = self::getDatastore()->getValue('products');
			if (empty($products[$package]))
			{
				throw new vB_Exception_Api('cant_load_product_x_disabled', [$c, $package]);
			}
		}

		if (!class_exists($c))
		{
			throw new Exception(sprintf("Can't find class %s", htmlspecialchars($c)));
		}

		if (!is_subclass_of($c, $interface))
		{
			throw new Exception(sprintf('Class %s is not a subclass of ' . $interface, htmlspecialchars($c)));
		}
		return $c;
	}

	/**
	*	Intended for unit tests, this resets the portion of the test needed for
	* testing to avoid cross contamination
	*/
	public static function reset()
	{
		self::$usercontexts = [];
		self::$datastore = null;
		self::$products = null;
		self::$hooks = null;
		self::$string = null;
	}

	/**
	 * Mainly intended for unit tests, this clears out the "additional" userContexts that
	 * are cached which may hold stale cached channel & other data
	 */
	public static function resetOtherUserContexts($userId = NULL)
	{
		if ($userId === NULL)
		{
			$session = self::getCurrentSession();
			if (!empty($session))
			{
				$userId = $session->get('userid');
			}
		}

		$userId = intval($userId);

		foreach (self::$usercontexts AS $__userid => $__uc)
		{
			if ($__userid !== $userId)
			{
				/*
				Note, unsetting whole static properties only unsets for the
				rest of the function context and restores it immediately,
				but unsetting elements of a static array seems to work fine.
				If this ever changes we'll need to change this & the unset
				code in getUserContext() to set each to NULL or something.
				 */
				unset(self::$usercontexts[$__userid]);
			}
		}
	}

	//this is a public function because it needs to be, however it should not be
	//called except as the exception handler.
	public static function handleException($e)
	{
		try
		{
			$config = self::getConfig();
			$debug = $config['Misc']['debug'];
		}
		catch(Throwable)
		{
			$debug = false;
		}

		echo $e->getMessage() . "\n";

		if ($debug)
		{
			echo '## ' . $e->getFile() . '(' . $e->getLine() . ") Exception Thrown \n" . $e->getTraceAsString();
		}
	}

	public static function handleError($errno, $errstr, $errfile, $errline)
	{
		return self::handleErrorInternal($errno, $errstr, $errfile, $errline, false);
	}

	public static function handleErrorQuiet($errno, $errstr, $errfile, $errline)
	{
		return self::handleErrorInternal($errno, $errstr, $errfile, $errline, true);
	}

	/**
	 *	handle vBulletin PHP error.
	 *
	 *	This does a bunch of error handling.  Primarily it supressing path information
	 *	for errors.  It throws an exception for any fatal errors that actually get
	 *	passed here (which is very few in 5 and 7 has switched to throwing not exceptions
	 *	instead of fatal errors in most cases)
	 *
	 *	@param $errorno -- standard error handler param
	 *	@param $errstr -- standard error handler param
	 *	@param $errfile -- standard error handler param
	 *	@param $errline -- standard error handler param
	 *	@param boolean $quiet -- if true do not output error message, do additional logging instead
	 */
	public static function handleErrorInternal($errno, $errstr, $errfile, $errline, $quiet)
	{
		//we should honor the error reporting settings (which the error handler
		//system does *not* do by default -- we get everything here.  We return
		//false so that the default error handler triggers.  It won't display
		//anything either, but it will correctly exit so we don't need to figure
		//out if we have to.

		//php changed the way dispay_errors is reported in version 5.2.  We probably don't
		//have to care about the old way, but this covers all of the bases.
		$display_errors = in_array(strtolower(ini_get('display_errors')), ['on', '1']);
		if (!(error_reporting() & $errno) OR !$display_errors)
		{
			return false;
		}

		//Note that not all of these error codes are trappable and therefore
		//many cannot actually occur here.  They are listed for completeness
		//and possible future proofing if that changes.
		$label = "";
		$fatal = false;
		switch($errno)
		{
			case E_STRICT:
				$label = "Strict standards";
				break;

			case E_DEPRECATED:
			case E_USER_DEPRECATED:
				$label = "Notice";
				break;

			case E_WARNING:
			case E_CORE_WARNING:
			case E_USER_WARNING:
			case E_COMPILE_WARNING:
				$label = "Warning";
				break;

			case E_NOTICE:
			case E_USER_NOTICE:
				$label = "Notice";
				break;

			case E_ERROR:
			case E_PARSE:
			case E_CORE_ERROR:
			case E_COMPILE_ERROR:
			case E_USER_ERROR:
			case E_RECOVERABLE_ERROR:
				$label = "Fatal error";
				$fatal = true;
				break;

			//if we don't know what the error type is, php added it after 5.6
			//we'll punt to the system error handler because we simply don't know
			//what we are dealing with.  This risks leaking the path on files, but
			//that's not as bad as exiting on a warning or not exiting on a fatal error
			default:
				return false;
				break;
		}

		if (!defined('DIR'))
		{
			//if we don't have DIR defined yet, let's show the error and live
			//with the potential path exposure.  Things are really borked.
			$safe_errfile = $errfile;
			$safe_errstr = $errstr;
		}
		else
		{
			//make the output safe for public consumption
			$safe_errfile = str_replace(DIR, '...', $errfile);
			$safe_errstr = str_replace(DIR, '...', $errstr);
		}

		$safe_message = "$label: $safe_errstr in $safe_errfile on line $errline\n";
		$message = "$label: $errstr in $errfile on line $errline";

		//echo the error
		if (!$fatal)
		{
			if (!$quiet)
			{
				echo $safe_message;
			}
			else
			{
				//log the warning
				if (defined('DIR'))
				{
					require_once(DIR . '/includes/functions_log_error.php');
					log_vbulletin_error($message, 'php');
				}

				self::$loggedWarnings[] = $safe_message;
			}
		}

		//try to mimic the logging behavior of the default function
		if (ini_get('log_errors'))
		{
			error_log($message);
		}

		if ($fatal)
		{
			//log the error
			if (defined('DIR'))
			{
				require_once(DIR . '/includes/functions_log_error.php');
				$trace = vB_Utilities::getStackTrace();
				log_vbulletin_error("$message\n\n$trace", 'php');
			}

			//the exception will handle the stack trace display.
			throw new vB_Exception_Api('php_error_x', [$safe_message]);
		}

		//we've got this -- no need to bother the default handler
		return true;
	}

	//some utility functions to normalize checking some application state
	public static function isDebug()
	{
		return !empty(self::getConfig()['Misc']['debug']);
	}

	public static function isInstaller()
	{
		return (defined('VB_AREA') AND (VB_AREA == 'Upgrade' OR VB_AREA == 'Install'));
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 116483 $
|| #######################################################################
\*=========================================================================*/
