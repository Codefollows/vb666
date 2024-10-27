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
 * 	Utility functions
 *
 *	Both the vB_Upgrade_Abstract and vB_Upgrade version classes end up needing
 *	some of the same basic utilities.  This allows us to share code without
 *	copy/pasting
 *
 */
//this is probably a sign that we need to rearchitect the upgrade classes
//to better handle this.  However that's not likely soon and this should
//at least prevent us from continuing to duplicate code.
//
//remember that if a function refers to a class variable it must be
//in both classes and an equivilant object
trait vB_Trait_Upgrade_Utilities
{
	protected function tableExists($tablename)
	{
		try
		{
			$tables = $this->db->query_first("
			SHOW TABLES LIKE '" . TABLE_PREFIX . "$tablename'");
			return (!empty($tables));
		}
		catch(Exception $e)
		{
			return false;
		}
	}

	protected function render_phrase_array($array)
	{
		//we assume that the main vb phrase system isn't working
		//so we only check local phrases.  We don't load phrases until
		//the end so even if the code is in place the phrases will be
		//empty on install and a version out of date on upgrade.
		//
		//Phrases needed for install/upgrade should be copied to the
		//install/upgrade file
		if (isset($this->phrase['vbphrase'][$array[0]]))
		{
			$array[0] = $this->phrase['vbphrase'][$array[0]];
			return construct_phrase($array);
		}
		else
		{
			$phrasename = array_shift($array);
			$args = "";
			if (count($array))
			{
				$args = '"' . implode('", "', $array) . "'";
			}
			return sprintf($this->phrase['core']['phrase_not_found'], $phrasename, $args);
		}
	}

	//This is fraught in a lot of ways -- symlinks can play merry havoc with trying to climb
	//the file tree.  But there isn't a robust way to do it.  Let's isolate the logic so that
	//we can manage any globally.
	private function get_vb_root()
	{
		return __DIR__ . '/../../../';
	}
}

/**
* Fetch upgrade lib based on PHP environment
*
* @package vBulletin
*
*/
class vB_Upgrade
{
	/**
	* Singleton emulation: Select library
	*
	*	@var	vB_Registry object
	*	@var	string	Override library detection routine
	* @var	boolean	Upgrade/true, Install/false
	*/
	public static function &fetch_library(&$registry, $phrases, $library = '', $upgrade = true, $script = null, $forcenew = false, $options = [])
	{
		global $show;

		//having this is dubious because everything happens in the constructor of the library class below.
		//So any additional calls to fetch_library are going to happen before we finish assigning anything
		//to instance.  It will still be false on the second trip.  We should really rewrite the class
		//to have a seperate execute call to avoid problems like this.
		static $instance = false;

		if (!$instance OR $forcenew)
		{
			if ($library)
			{
				$chosenlib = $library;
			}
			else
			{
				$chosenlib = self::isCLI() ? 'cli' : 'ajax';
			}

			$selectclass = 'vB_Upgrade_' . $chosenlib;
			$chosenlib = strtolower($chosenlib);

			//allow the caller to include the class if they want to put it somewhere else
			if (!class_exists($selectclass))
			{
				require_once(DIR . '/install/includes/class_upgrade_' . $chosenlib . '.php');
			}
			$instance = new $selectclass($registry, $phrases, $upgrade ? 'upgrade' : 'install', $script, $options);
		}

		return $instance;
	}

	public static function fetch_language()
	{
		static $phrases = false;

		if (!$phrases)
		{
			$languagecode = defined('UPGRADE_LANGUAGE') ? UPGRADE_LANGUAGE : 'en';
			$xmlobj = new vB_XML_Parser(false, DIR . '/install/upgrade_language_' . $languagecode . '.xml');
			$xml = $xmlobj->parse(defined('UPGRADE_ENCODING') ? UPGRADE_ENCODING : 'ISO-8859-1');

			foreach ($xml['group'] AS $value)
			{
				if (isset($value['group']) AND is_array($value['group']))
				{
					// step phrases
					foreach ($value['group'] AS $value2)
					{
						if (!isset($value2['phrase'][0]))
						{
							$value2['phrase'] = [$value2['phrase']];
						}
						foreach ($value2['phrase'] AS $value3)
						{
							$phrases[$value['name']][$value2['name']][$value3['name']] = $value3['value'];
						}
					}
				}
				else
				{
					if (!isset($value['phrase'][0]))
					{
						$value['phrase'] = [$value['phrase']];
					}
					foreach ($value['phrase'] AS $value2)
					{
						$phrases[$value['name']][$value2['name']] = $value2['value'];
					}
				}
			}
			$GLOBALS['vbphrase'] =& $phrases['vbphrase'];
		}

		return $phrases;
	}

	//create a guest session.
	public static function createSession()
	{
		$session = vB::getCurrentSession();
		if (empty($session))
		{
			$session = new vB_Session_Cli(vB::getDbAssertor(), vB::getDatastore(), vB::getConfig(), 0);
			vB::setCurrentSession($session);
		}
	}

	/**
	 * When running from the command line we don't have a session. So if
	 * we want to use API functions we need to create one
	 */
	public static function createAdminSession()
	{
		$session = vB::getCurrentSession();

		//things rely on the fact that we can call this mutiple times and only create the session once.
		//we should probably check that it's an actual admin/cpsession but we don't create sessions for
		//a particular user that aren't admin sessions so it works.
		if (empty($session) OR ($session->get('userid') <= 0))
		{
			$userid = vB_PermissionContext::getAdminUser();
			$session = new vB_Session_Cli(vB::getDbAssertor(), vB::getDatastore(), vB::getConfig(),  $userid);
			$session->fetchCpsessionHash();
			vB::setCurrentSession($session);
		}
	}

	/**
	 * PHP-CLI mode check
	 *
	 * @return boolean    Returns true if PHP is running from the CLI even if on CGI-mode, or else false.
	 *
	 */
	public static function isCLI()
	{
		if (!defined('STDIN') AND self::isCgi())
		{
			return empty($_SERVER['REQUEST_METHOD']);
		}

		return defined('STDIN');
	}

	/**
	 * PHP-CGI mode check
	 *
	 * @return boolean   Returns true if PHP is running as CGI module or else false.
	 *
	 */
	public static function isCgi()
	{
		return (substr(PHP_SAPI, 0, 3) == 'cgi');
	}
}

abstract class vB_Upgrade_Abstract
{
	use vB_Trait_Upgrade_Utilities;
	/*Constants=====================================================================*/

	/*Properties====================================================================*/

	/**
	* The vBulletin registry object
	*
	* @var	vB_Registry
	*/
	protected $registry = null;

	/**
	* The object that will be used to execute queries
	*
	* @var	vB_Database
	*/
	protected $db = null;

	/**
	* Array of Steps Objects
	*
	*	@var object
	*/
	public $steps = [];

	/**
	* Upgrade start point
	*
	*	@var array
	*/
	protected $scriptinfo = [
		'version' => null,
		'startat' => 0,
		'perpage' => 20,
		'step'    => 1,
	];

	/**
	* Startup warning messages
	*
	*	@var array
	*/
	protected $startup_warnings = [];

	/**
	* XML file versions
	*
	*	@var array
	*/
	protected $xml_versions = [
		'language'  => null,
		'style'     => null,
		'adminhelp' => null,
		'settings'  => null
	];

	/**
	* Array of vBulletin versions supported for upgrade
	*
	* @var array
	*/
	protected $versions = [
		'35*'    => '352',	// allow any version 3.5.x version that is 3.5.4 and greater..
		'360b1'  => '3.6.0 Beta 1',
		'360b2'  => '3.6.0 Beta 2',
		'360b3'  => '3.6.0 Beta 3',
		'360b4'  => '3.6.0 Beta 4',
		'360rc1' => '3.6.0 Release Candidate 1',
		'360rc2' => '3.6.0 Release Candidate 2',
		'360rc3' => '3.6.0 Release Candidate 3',
		'360'    => '3.6.0',
		'361'    => '3.6.1',
		'362'    => '3.6.2',
		'363'    => '3.6.3',
		'364'    => '3.6.4',
		'365'    => '3.6.5',
		'366'    => '3.6.6',
		'367'    => '3.6.7',
		'368'    => '3.6.8',
		'36*'    => '',
		'370b2'  => '3.7.0 Beta 2',
		'370b3'  => '3.7.0 Beta 3',
		'370b4'  => '3.7.0 Beta 4',
		'370b5'  => '3.7.0 Beta 5',
		'370b6'  => '3.7.0 Beta 6',
		'370rc1' => '3.7.0 Release Candidate 1',
		'370rc2' => '3.7.0 Release Candidate 2',
		'370rc3' => '3.7.0 Release Candidate 3',
		'370rc4' => '3.7.0 Release Candidate 4',
		'370'    => '3.7.0',
		'371'    => '3.7.1',
		'37*'    => '',
		'380a2'  => '3.8.0 Alpha 2',
		'380b1'  => '3.8.0 Beta 1',
		'380b2'  => '3.8.0 Beta 2',
		'380b3'  => '3.8.0 Beta 3',
		'380b4'  => '3.8.0 Beta 4',
		'380rc1' => '3.8.0 Release Candidate 1',
		'380rc2' => '3.8.0 Release Candidate 2',
		'380'    => '3.8.0',
		'381'	 => '3.8.1',
		'382'	 => '3.8.2',
		'383'	 => '3.8.3',
		'384'	 => '3.8.4',
		'385'	 => '3.8.5',
		'386'	 => '3.8.6',
		'387b1'	 => '3.8.7 Beta 1',
		'387'	 => '3.8.7',
		'38*'    => '', // Skips past any other 3.8.x versions
		'400a1'  => '4.0.0 Alpha 1',
		'400a2'  => '4.0.0 Alpha 2',
		'400a3'  => '4.0.0 Alpha 3',
		'400a4'  => '4.0.0 Alpha 4',
		'400a5'  => '4.0.0 Alpha 5',
		'400a6'  => '4.0.0 Alpha 6',
		'400b1'  => '4.0.0 Beta 1',
		'400b2'  => '4.0.0 Beta 2',
		'400b3'  => '4.0.0 Beta 3',
		'400b4'  => '4.0.0 Beta 4',
		'400b5'  => '4.0.0 Beta 5',
		'400rc1' => '4.0.0 Release Candidate 1',
		'400rc2' => '4.0.0 Release Candidate 2',
		'400rc3' => '4.0.0 Release Candidate 3',
		'400rc4' => '4.0.0 Release Candidate 4',
		'400rc5' => '4.0.0 Release Candidate 5',
		'400'    => '4.0.0',
		'401'    => '4.0.1',
		'402'    => '4.0.2',
		'403'    => '4.0.3',
		'404'    => '4.0.4',
		'405'    => '4.0.5',
		'406'    => '4.0.6',
		'407'    => '4.0.7',
		'408'    => '4.0.8',
		'410b1'  => '4.1.0 Beta 1',
		'410'    => '4.1.0',
		'411a1'  => '4.1.1 Alpha 1',
		'411b1'  => '4.1.1 Beta 1',
		'411'    => '4.1.1',
		'412b1'  => '4.1.2 Beta 1',
		'412'    => '4.1.2',
		'413b1'  => '4.1.3 Beta 1',
		'413'    => '4.1.3',
		'414b1'  => '4.1.4 Beta 1',
		'414'    => '4.1.4',
		'415b1'  => '4.1.5 Beta 1',
		'415'    => '4.1.5',
		'416b1'  => '4.1.6 Beta 1',
		'416'    => '4.1.6',
		'417b1'  => '4.1.7 Beta 1',
		'417'    => '4.1.7',
		'418b1'  => '4.1.8 Beta 1',
		'418'    => '4.1.8',
		'419b1'  => '4.1.9 Beta 1',
		'419'    => '4.1.9',
		'4110a1' => '4.1.10 Alpha 1',
		'4110a2' => '4.1.10 Alpha 2',
		'4110a3' => '4.1.10 Alpha 3',
		'4110b1' => '4.1.10 Beta 1',
		'4110'   => '4.1.10',
		'4111a1' => '4.1.11 Alpha 1',
		'4111a2' => '4.1.11 Alpha 2',
		'4111b1' => '4.1.11 Beta 1',
		'4111b2' => '4.1.11 Beta 2',
		'4111'   => '4.1.11',
		'4112a1' => '4.1.12 Alpha 1',
		'4112b1' => '4.1.12 Beta 1',
		'4112b2' => '4.1.12 Beta 2',
		'4112'   => '4.1.12',
		'420a1'  => '4.2.0 Alpha 1',
		'420b1'  => '4.2.0 Beta 1',
		'420'    => '4.2.0',
		'421a1'  => '4.2.1 Alpha 1',
		'421b1'  => '4.2.1 Beta 1',
		'421'    => '4.2.1',
		'422a1'  => '4.2.2 Alpha 1',
		'422b1'  => '4.2.2 Beta 1',
		'422'    => '4.2.2',
		'423a1'  => '4.2.3 Alpha 1',
		'423b1'  => '4.2.3 Beta 1',
		'423b2'  => '4.2.3 Beta 2',
		'423b3'  => '4.2.3 Beta 3',
		'423b4'  => '4.2.3 Beta 4',
		'423rc1' => '4.2.3 Release Candidate 1',
		'423'    => '4.2.3',
		'424b1'  => '4.2.4 Beta 1',
		'424b2'  => '4.2.4 Beta 2',
		'424b3'  => '4.2.4 Beta 3',
		'424rc1' => '4.2.4 Release Candidate 1',
		'424rc2' => '4.2.4 Release Candidate 2',
		'424rc3' => '4.2.4 Release Candidate 3',
		'424'    => '4.2.4',
		'425a1'  => '4.2.5 Alpha 1',
		'425a2'  => '4.2.5 Alpha 2',
		'425a3'  => '4.2.5 Alpha 3',
		'425b1'  => '4.2.5 Beta 1',
		'425b2'  => '4.2.5 Beta 2',
		'425b3'  => '4.2.5 Beta 3',
		'425b4'  => '4.2.5 Beta 4',
		'425rc1' => '4.2.5 Release Candidate 1',
		'425rc2' => '4.2.5 Release Candidate 2',
		'425'    => '4.2.5',
		'500a1'  => '5.0.0 Alpha 1',
		'500a2'  => '5.0.0 Alpha 2',
		'500a3'  => '5.0.0 Alpha 3',
		'500a4'  => '5.0.0 Alpha 4',
		'500a5'  => '5.0.0 Alpha 5',
		'500a6'  => '5.0.0 Alpha 6',
		'500a7'  => '5.0.0 Alpha 7',
		'500a8'  => '5.0.0 Alpha 8',
		'500a9'  => '5.0.0 Alpha 9',
		'500a10' => '5.0.0 Alpha 10',
		'500a11' => '5.0.0 Alpha 11',
		'500a12' => '5.0.0 Alpha 12',
		'500a13' => '5.0.0 Alpha 13',
		'500a14' => '5.0.0 Alpha 14',
		'500a15' => '5.0.0 Alpha 15',
		'500a16' => '5.0.0 Alpha 16',
		'500a17' => '5.0.0 Alpha 17',
		'500a18' => '5.0.0 Alpha 18',
		'500a19' => '5.0.0 Alpha 19',
		'500a20' => '5.0.0 Alpha 20',
		'500a21' => '5.0.0 Alpha 21',
		'500a22' => '5.0.0 Alpha 22',
		'500a23' => '5.0.0 Alpha 23',
		'500a24' => '5.0.0 Alpha 24',
		'500a25' => '5.0.0 Alpha 25',
		'500a26' => '5.0.0 Alpha 26',
		'500a27' => '5.0.0 Alpha 27',
		'500a28' => '5.0.0 Alpha 28',
		'500a29' => '5.0.0 Alpha 29',
		'500a30' => '5.0.0 Alpha 30',
		'500a31' => '5.0.0 Alpha 31',
		'500a32' => '5.0.0 Alpha 32',
		'500a33' => '5.0.0 Alpha 33',
		'500a34' => '5.0.0 Alpha 34',
		'500a35' => '5.0.0 Alpha 35',
		'500a36' => '5.0.0 Alpha 36',
		'500a37' => '5.0.0 Alpha 37',
		'500a38' => '5.0.0 Alpha 38',
		'500a39' => '5.0.0 Alpha 39',
		'500a40' => '5.0.0 Alpha 40',
		'500a41' => '5.0.0 Alpha 41',
		'500a42' => '5.0.0 Alpha 42',
		'500a43' => '5.0.0 Alpha 43',
		'500a44' => '5.0.0 Alpha 44',
		'500a45' => '5.0.0 Alpha 45',
		'500b1'  => '5.0.0 Beta 1',
		'500b2'  => '5.0.0 Beta 2',
		'500b3'  => '5.0.0 Beta 3',
		'500b4'  => '5.0.0 Beta 4',
		'500b5'  => '5.0.0 Beta 5',
		'500b6'  => '5.0.0 Beta 6',
		'500b7'  => '5.0.0 Beta 7',
		'500b8'  => '5.0.0 Beta 8',
		'500b9'  => '5.0.0 Beta 9',
		'500b10' => '5.0.0 Beta 10',
		'500b11' => '5.0.0 Beta 11',
		'500b12' => '5.0.0 Beta 12',
		'500b13' => '5.0.0 Beta 13',
		'500b14' => '5.0.0 Beta 14',
		'500b15' => '5.0.0 Beta 15',
		'500b16' => '5.0.0 Beta 16',
		'500b17' => '5.0.0 Beta 17',
		'500b18' => '5.0.0 Beta 18',
		'500b19' => '5.0.0 Beta 19',
		'500b20' => '5.0.0 Beta 20',
		'500b21' => '5.0.0 Beta 21',
		'500b22' => '5.0.0 Beta 22',
		'500b23' => '5.0.0 Beta 23',
		'500b24' => '5.0.0 Beta 24',
		'500b25' => '5.0.0 Beta 25',
		'500b26' => '5.0.0 Beta 26',
		'500b27' => '5.0.0 Beta 27',
		'500b28' => '5.0.0 Beta 28',
		'500rc1' => '5.0.0 Release Candidate 1',
		'500'    => '5.0.0',
		'501a1'  => '5.0.1 Alpha 1',
		'501a2'  => '5.0.1 Alpha 2',
		'501rc1' => '5.0.1 Release Candidate 1',
		'501'    => '5.0.1',
		'502a1'  => '5.0.2 Alpha 1',
		'502a2'  => '5.0.2 Alpha 2',
		'502b1'  => '5.0.2 Beta 1',
		'502rc1' => '5.0.2 Release Candidate 1',
		'502'    => '5.0.2',
		'503a1'  => '5.0.3 Alpha 1',
		'503a2'  => '5.0.3 Alpha 2',
		'503a3'  => '5.0.3 Alpha 3',
		'503b1'  => '5.0.3 Beta 1',
		'503rc1' => '5.0.3 Release Candidate 1',
		'503'    => '5.0.3',
		'504a1'  => '5.0.4 Alpha 1',
		'504a2'  => '5.0.4 Alpha 2',
		'504a3'  => '5.0.4 Alpha 3',
		'504rc1' => '5.0.4 Release Candidate 1',
		'504'    => '5.0.4',
		'505a1'  => '5.0.5 Alpha 1',
		'505a2'  => '5.0.5 Alpha 2',
		'505a3'  => '5.0.5 Alpha 3',
		'505a4'  => '5.0.5 Alpha 4',
		'505rc1' => '5.0.5 Release Candidate 1',
		'505rc2' => '5.0.5 Release Candidate 2',
		'505'    => '5.0.5',
		'506a1'  => '5.0.6 Alpha 1',
		'506'    => '5.0.6',
		'510a1'  => '5.1.0 Alpha 1',
		'510a2'  => '5.1.0 Alpha 2',
		'510a3'  => '5.1.0 Alpha 3',
		'510a4'  => '5.1.0 Alpha 4',
		'510a5'  => '5.1.0 Alpha 5',
		'510a6'  => '5.1.0 Alpha 6',
		'510a7'  => '5.1.0 Alpha 7',
		'510a8'  => '5.1.0 Alpha 8',
		'510a9'  => '5.1.0 Alpha 9',
		'510b1'  => '5.1.0 Beta 1',
		'510b2'  => '5.1.0 Beta 2',
		'510b3'  => '5.1.0 Beta 3',
		'510b4'  => '5.1.0 Beta 4',
		'510rc1' => '5.1.0 Release Candidate 1',
		'510'    => '5.1.0',
		'511a1'  => '5.1.1 Alpha 1',
		'511a2'  => '5.1.1 Alpha 2',
		'511a3'  => '5.1.1 Alpha 3',
		'511a4'  => '5.1.1 Alpha 4',
		'511a5'  => '5.1.1 Alpha 5',
		'511a6'  => '5.1.1 Alpha 6',
		'511a7'  => '5.1.1 Alpha 7',
		'511a8'  => '5.1.1 Alpha 8',
		'511a9'  => '5.1.1 Alpha 9',
		'511a10' => '5.1.1 Alpha 10',
		'511a11' => '5.1.1 Alpha 11',
		'511rc1' => '5.1.1 Release Candidate 1',
		'511'    => '5.1.1',
		'512a1'  => '5.1.2 Alpha 1',
		'512a2'  => '5.1.2 Alpha 2',
		'512a3'  => '5.1.2 Alpha 3',
		'512a4'  => '5.1.2 Alpha 4',
		'512a5'  => '5.1.2 Alpha 5',
		'512a6'  => '5.1.2 Alpha 6',
		'512b1'  => '5.1.2 Beta 1',
		'512b2'  => '5.1.2 Beta 2',
		'512rc1' => '5.1.2 Release Candidate 1',
		'512rc2' => '5.1.2 Release Candidate 2',
		'512'    => '5.1.2',
		'513a1'  => '5.1.3 Alpha 1',
		'513a2'  => '5.1.3 Alpha 2',
		'513a3'  => '5.1.3 Alpha 3',
		'513a4'  => '5.1.3 Alpha 4',
		'513a5'  => '5.1.3 Alpha 5',
		'513a6'  => '5.1.3 Alpha 6',
		'513b1'  => '5.1.3 Beta 1',
		'513b2'  => '5.1.3 Beta 2',
		'513rc1' => '5.1.3 Release Candidate 1',
		'513'    => '5.1.3',
		'514a1'  => '5.1.4 Alpha 1',
		'514a2'  => '5.1.4 Alpha 2',
		'514a3'  => '5.1.4 Alpha 3',
		'514a4'  => '5.1.4 Alpha 4',
		'514a5'  => '5.1.4 Alpha 5',
		'514a6'  => '5.1.4 Alpha 6',
		'514a7'  => '5.1.4 Alpha 7',
		'514a8'  => '5.1.4 Alpha 8',
		'514b1'  => '5.1.4 Beta 1',
		'514b2'  => '5.1.4 Beta 2',
		'514b3'  => '5.1.4 Beta 3',
		'514rc1' => '5.1.4 Release Candidate 1',
		'514'    => '5.1.4',
		'515a1'  => '5.1.5 Alpha 1',
		'515a2'  => '5.1.5 Alpha 2',
		'515a3'  => '5.1.5 Alpha 3',
		'515a4'  => '5.1.5 Alpha 4',
		'515a5'  => '5.1.5 Alpha 5',
		'515a6'  => '5.1.5 Alpha 6',
		'515a7'  => '5.1.5 Alpha 7',
		'515a8'  => '5.1.5 Alpha 8',
		'515b1'  => '5.1.5 Beta 1',
		'515b2'  => '5.1.5 Beta 2',
		'515b3'  => '5.1.5 Beta 3',
		'515'    => '5.1.5',
		'516a1'  => '5.1.6 Alpha 1',
		'516a2'  => '5.1.6 Alpha 2',
		'516a3'  => '5.1.6 Alpha 3',
		'516a4'  => '5.1.6 Alpha 4',
		'516a5'  => '5.1.6 Alpha 5',
		'516a6'  => '5.1.6 Alpha 6',
		'516a7'  => '5.1.6 Alpha 7',
		'516b1'  => '5.1.6 Beta 1',
		'516b2'  => '5.1.6 Beta 2',
		'516rc1' => '5.1.6 Release Candidate 1',
		'516'    => '5.1.6',
		'517a1'  => '5.1.7 Alpha 1',
		'517a2'  => '5.1.7 Alpha 2',
		'517a3'  => '5.1.7 Alpha 3',
		'517a4'  => '5.1.7 Alpha 4',
		'517a5'  => '5.1.7 Alpha 5',
		'517b1'  => '5.1.7 Beta 1',
		'517b2'  => '5.1.7 Beta 2',
		'517b3'  => '5.1.7 Beta 3',
		'517rc1' => '5.1.7 Release Candidate 1',
		'517'    => '5.1.7',
		'518a1'  => '5.1.8 Alpha 1',
		'518a2'  => '5.1.8 Alpha 2',
		'518a3'  => '5.1.8 Alpha 3',
		'518a4'  => '5.1.8 Alpha 4',
		'518a5'  => '5.1.8 Alpha 5',
		'518a6'  => '5.1.8 Alpha 6',
		'518a7'  => '5.1.8 Alpha 7',
		'518a8'  => '5.1.8 Alpha 8',
		'518rc1' => '5.1.8 Release Candidate 1',
		'518'    => '5.1.8',
		'519a1'  => '5.1.9 Alpha 1',
		'519a2'  => '5.1.9 Alpha 2',
		'519a3'  => '5.1.9 Alpha 3',
		'519a4'  => '5.1.9 Alpha 4',
		'519a5'  => '5.1.9 Alpha 5',
		'519a6'  => '5.1.9 Alpha 6',
		'519b1'  => '5.1.9 Beta 1',
		'519b2'  => '5.1.9 Beta 2',
		'519rc1' => '5.1.9 Release Candidate 1',
		'519rc2' => '5.1.9 Release Candidate 2',
		'519'    => '5.1.9',
		'5110a1' => '5.1.10 Alpha 1',
		'5110a2' => '5.1.10 Alpha 2',
		'5110a3' => '5.1.10 Alpha 3',
		'5110a4' => '5.1.10 Alpha 4',
		'5110a5' => '5.1.10 Alpha 5',
		'5110b1' => '5.1.10 Beta 1',
		'5110b2' => '5.1.10 Beta 2',
		'5110b3' => '5.1.10 Beta 3',
		'5110rc1' => '5.1.10 Release Candidate 1',
		'5110rc2'   => '5.1.10 Release Candidate 2',
		'5110'      => '5.1.10',
		'5111a1' => '5.1.11 Alpha 1',
		'5111a2' => '5.1.11 Alpha 2',
		'520a1'  => '5.2.0 Alpha 1',
		'520a2'  => '5.2.0 Alpha 2',
		'520a3'     => '5.2.0 Alpha 3',
		'520b1'     => '5.2.0 Beta 1',
		'520b2'     => '5.2.0 Beta 2',
		'520rc1'    => '5.2.0 Release Candidate 1',
		'520rc2'    => '5.2.0 Release Candidate 2',
		'520'       => '5.2.0',
		'521a1'     => '5.2.1 Alpha 1',
		'521a2'     => '5.2.1 Alpha 2',
		'521a3'     => '5.2.1 Alpha 3',
		'521a4'     => '5.2.1 Alpha 4',
		'521a5'     => '5.2.1 Alpha 5',
		'521a6'     => '5.2.1 Alpha 6',
		'521b1'     => '5.2.1 Beta 1',
		'521b2'     => '5.2.1 Beta 2',
		'521rc1'    => '5.2.1 Release Candidate 1',
		'521rc2'    => '5.2.1 Release Candidate 2',
		'521rc3'    => '5.2.1 Release Candidate 3',
		'521'       => '5.2.1',
		'522a1'     => '5.2.2 Alpha 1',
		'522a2'     => '5.2.2 Alpha 2',
		'522a3'     => '5.2.2 Alpha 3',
		'522a4'     => '5.2.2 Alpha 4',
		'522a5'     => '5.2.2 Alpha 5',
		'522b1'     => '5.2.2 Beta 1',
		'522b2'     => '5.2.2 Beta 2',
		'522rc1'    => '5.2.2 Release Candidate 1',
		'522'       => '5.2.2',
		'523a1'     => '5.2.3 Alpha 1',
		'523a2'     => '5.2.3 Alpha 2',
		'523a3'     => '5.2.3 Alpha 3',
		'523a4'     => '5.2.3 Alpha 4',
		'523a5'     => '5.2.3 Alpha 5',
		'523a6'     => '5.2.3 Alpha 6',
		'523b1'     => '5.2.3 Beta 1',
		'523b2'     => '5.2.3 Beta 2',
		'523rc1'    => '5.2.3 Release Candidate 1',
		'523rc2'    => '5.2.3 Release Candidate 2',
		'523rc3'    => '5.2.3 Release Candidate 3',
		'523rc4'    => '5.2.3 Release Candidate 4',
		'523'       => '5.2.3',
		'524a1'     => '5.2.4 Alpha 1',
		'524a2'     => '5.2.4 Alpha 2',
		'524a3'     => '5.2.4 Alpha 3',
		'524a4'     => '5.2.4 Alpha 4',
		'524a5'     => '5.2.4 Alpha 5',
		'524b1'     => '5.2.4 Beta 1',
		'524rc1'    => '5.2.4 Release Candidate 1',
		'524rc2'    => '5.2.4 Release Candidate 2',
		'524rc3'    => '5.2.4 Release Candidate 3',
		'524rc4'    => '5.2.4 Release Candidate 4',
		'524'       => '5.2.4',
		'525a1'     => '5.2.5 Alpha 1',
		'525a2'     => '5.2.5 Alpha 2',
		'525a3'     => '5.2.5 Alpha 3',
		'525a4'     => '5.2.5 Alpha 4',
		'525a5'     => '5.2.5 Alpha 5',
		'525a6'     => '5.2.5 Alpha 6',
		'525rc1'    => '5.2.5 Release Candidate 1',
		'525'       => '5.2.5',
		'526a1'     => '5.2.6 Alpha 1',
		'526a2'     => '5.2.6 Alpha 2',
		'526a3'     => '5.2.6 Alpha 3',
		'526a4'     => '5.2.6 Alpha 4',
		'526a5'     => '5.2.6 Alpha 5',
		'526a6'     => '5.2.6 Alpha 6',
		'526b1'     => '5.2.6 Beta 1',
		'526b2'     => '5.2.6 Beta 2',
		'526rc1'    => '5.2.6 Release Candidate 1',
		'526rc2'    => '5.2.6 Release Candidate 2',
		'526'       => '5.2.6',
		'527a1'     => '5.2.7 Alpha 1',
		'527a2'     => '5.2.7 Alpha 2',
		'527a3'     => '5.2.7 Alpha 3',
		'527a4'     => '5.2.7 Alpha 4',
		'530a1'     => '5.3.0 Alpha 1',
		'530b1'     => '5.3.0 Beta 1',
		'530b2'     => '5.3.0 Beta 2',
		'530rc1'    => '5.3.0 Release Candidate 1',
		'530'       => '5.3.0',
		'531a1'     => '5.3.1 Alpha 1',
		'531a2'     => '5.3.1 Alpha 2',
		'531a3'     => '5.3.1 Alpha 3',
		'531a4'     => '5.3.1 Alpha 4',
		'531b1'     => '5.3.1 Beta 1',
		'531b2'     => '5.3.1 Beta 2',
		'531rc1'    => '5.3.1 Release Candidate 1',
		'531'       => '5.3.1',
		'532a1'     => '5.3.2 Alpha 1',
		'532a2'     => '5.3.2 Alpha 2',
		'532a3'     => '5.3.2 Alpha 3',
		'532a4'     => '5.3.2 Alpha 4',
		'532b1'     => '5.3.2 Beta 1',
		'532b2'     => '5.3.2 Beta 2',
		'532rc1'    => '5.3.2 Release Candidate 1',
		'532rc2'    => '5.3.2 Release Candidate 2',
		'532'       => '5.3.2',
		'533a1'     => '5.3.3 Alpha 1',
		'533a2'     => '5.3.3 Alpha 2',
		'533a3'     => '5.3.3 Alpha 3',
		'533a4'     => '5.3.3 Alpha 4',
		'533b1'     => '5.3.3 Beta 1',
		'533b2'     => '5.3.3 Beta 2',
		'533rc1'    => '5.3.3 Release Candidate 1',
		'533rc2'    => '5.3.3 Release Candidate 2',
		'533rc3'    => '5.3.3 Release Candidate 3',
		'533'       => '5.3.3',
		'534a1'     => '5.3.4 Alpha 1',
		'534a2'     => '5.3.4 Alpha 2',
		'534a3'     => '5.3.4 Alpha 3',
		'534a4'     => '5.3.4 Alpha 4',
		'534b1'     => '5.3.4 Beta 1',
		'534b2'     => '5.3.4 Beta 2',
		'534rc1'    => '5.3.4 Release Candidate 1',
		'534rc2'    => '5.3.4 Release Candidate 2',
		'534rc3'    => '5.3.4 Release Candidate 3',
		'534'       => '5.3.4',
		'535a1'     => '5.3.5 Alpha 1',
		'535a2'     => '5.3.5 Alpha 2',
		'535a3'     => '5.3.5 Alpha 3',
		'535a4'     => '5.3.5 Alpha 4',
		'540a1'     => '5.4.0 Alpha 1',
		'540b1'     => '5.4.0 Beta 1',
		'540b2'     => '5.4.0 Beta 2',
		'540rc1'    => '5.4.0 Release Candidate 1',
		'540rc2'    => '5.4.0 Release Candidate 2',
		'540rc3'    => '5.4.0 Release Candidate 3',
		'540'       => '5.4.0',
		'541a1'     => '5.4.1 Alpha 1',
		'541a2'     => '5.4.1 Alpha 2',
		'541a3'     => '5.4.1 Alpha 3',
		'541a4'     => '5.4.1 Alpha 4',
		'541b1'     => '5.4.1 Beta 1',
		'541b2'     => '5.4.1 Beta 2',
		'541rc1'    => '5.4.1 Release Candidate 1',
		'541'       => '5.4.1',
		'542a1'     => '5.4.2 Alpha 1',
		'542a2'     => '5.4.2 Alpha 2',
		'542a3'     => '5.4.2 Alpha 3',
		'542a4'     => '5.4.2 Alpha 4',
		'542b1'     => '5.4.2 Beta 1',
		'542b2'     => '5.4.2 Beta 2',
		'542rc1'    => '5.4.2 Release Candidate 1',
		'542rc2'    => '5.4.2 Release Candidate 2',
		'542'       => '5.4.2',
		'543a1'     => '5.4.3 Alpha 1',
		'543a2'     => '5.4.3 Alpha 2',
		'543a3'     => '5.4.3 Alpha 3',
		'543a4'     => '5.4.3 Alpha 4',
		'543b1'     => '5.4.3 Beta 1',
		'543b2'     => '5.4.3 Beta 2',
		'543rc1'    => '5.4.3 Release Candidate 1',
		'543'       => '5.4.3',
		'544a1'     => '5.4.4 Alpha 1',
		'544a2'     => '5.4.4 Alpha 2',
		'544a3'     => '5.4.4 Alpha 3',
		'544a4'     => '5.4.4 Alpha 4',
		'544b1'     => '5.4.4 Beta 1',
		'544b2'     => '5.4.4 Beta 2',
		'544rc1'    => '5.4.4 Release Candidate 1',
		'544'       => '5.4.4',
		'545a1'     => '5.4.5 Alpha 1',
		'545a2'     => '5.4.5 Alpha 2',
		'545a3'     => '5.4.5 Alpha 3',
		'545a4'     => '5.4.5 Alpha 4',
		'545b1'     => '5.4.5 Beta 1',
		'545b2'     => '5.4.5 Beta 2',
		'545rc1'    => '5.4.5 Release Candidate 1',
		'545'       => '5.4.5',
		'546a1'     => '5.4.6 Alpha 1',
		'546a2'     => '5.4.6 Alpha 2',
		'550a1'     => '5.5.0 Alpha 1',
		'550a2'     => '5.5.0 Alpha 2',
		'550b1'     => '5.5.0 Beta 1',
		'550b2'     => '5.5.0 Beta 2',
		'550rc1'    => '5.5.0 Release Candidate 1',
		'550rc2'    => '5.5.0 Release Candidate 2',
		'550'       => '5.5.0',
		'551a1'     => '5.5.1 Alpha 1',
		'551a2'     => '5.5.1 Alpha 2',
		'551a3'     => '5.5.1 Alpha 3',
		'551a4'     => '5.5.1 Alpha 4',
		'551b1'     => '5.5.1 Beta 1',
		'551b2'     => '5.5.1 Beta 2',
		'551rc1'    => '5.5.1 Release Candidate 1',
		'551'       => '5.5.1',
		'552a1'     => '5.5.2 Alpha 1',
		'552a2'     => '5.5.2 Alpha 2',
		'552a3'     => '5.5.2 Alpha 3',
		'552a4'     => '5.5.2 Alpha 4',
		'552b1'     => '5.5.2 Beta 1',
		'552b2'     => '5.5.2 Beta 2',
		'552rc1'    => '5.5.2 Release Candidate 1',
		'552rc2'    => '5.5.2 Release Candidate 2',
		'552'       => '5.5.2',
		'553a1'     => '5.5.3 Alpha 1',
		'553a2'     => '5.5.3 Alpha 2',
		'553a3'     => '5.5.3 Alpha 3',
		'553a4'     => '5.5.3 Alpha 4',
		'553b1'     => '5.5.3 Beta 1',
		'553b2'     => '5.5.3 Beta 2',
		'553rc1'    => '5.5.3 Release Candidate 1',
		'553rc2'    => '5.5.3 Release Candidate 2',
		'553'       => '5.5.3',
		'554a1'     => '5.5.4 Alpha 1',
		'554a2'     => '5.5.4 Alpha 2',
		'554a3'     => '5.5.4 Alpha 3',
		'554a4'     => '5.5.4 Alpha 4',
		'554b1'     => '5.5.4 Beta 1',
		'554b2'     => '5.5.4 Beta 2',
		'554rc1'    => '5.5.4 Release Candidate 1',
		'554'       => '5.5.4',
		'555a1'     => '5.5.5 Alpha 1',
		'555a2'     => '5.5.5 Alpha 2',
		'555a3'     => '5.5.5 Alpha 3',
		'555a4'     => '5.5.5 Alpha 4',
		'555b1'     => '5.5.5 Beta 1',
		'555b2'     => '5.5.5 Beta 2',
		'555rc1'    => '5.5.5 Release Candidate 1',
		'555'       => '5.5.5',
		'556a1'     => '5.5.6 Alpha 1',
		'556a2'     => '5.5.6 Alpha 2',
		'556a3'     => '5.5.6 Alpha 3',
		'556a4'     => '5.5.6 Alpha 4',
		'556b1'     => '5.5.6 Beta 1',
		'556b2'     => '5.5.6 Beta 2',
		'556rc1'    => '5.5.6 Release Candidate 1',
		'556rc2'    => '5.5.6 Release Candidate 2',
		'556rc3'    => '5.5.6 Release Candidate 3',
		'556'       => '5.5.6',
		'557a1'     => '5.5.7 Alpha 1',
		'557a2'     => '5.5.7 Alpha 2',
		'560a1'     => '5.6.0 Alpha 1',
		'560a2'     => '5.6.0 Alpha 2',
		'560b1'     => '5.6.0 Beta 1',
		'560b2'     => '5.6.0 Beta 2',
		'560rc1'    => '5.6.0 Release Candidate 1',
		'560rc2'    => '5.6.0 Release Candidate 2',
		'560'       => '5.6.0',
		'561a1'     => '5.6.1 Alpha 1',
		'561a2'     => '5.6.1 Alpha 2',
		'561a3'     => '5.6.1 Alpha 3',
		'561a4'     => '5.6.1 Alpha 4',
		'561b1'     => '5.6.1 Beta 1',
		'561b2'     => '5.6.1 Beta 2',
		'561rc1'    => '5.6.1 Release Candidate 1',
		'561rc2'    => '5.6.1 Release Candidate 2',
		'561'       => '5.6.1',
		'562a1'     => '5.6.2 Alpha 1',
		'562a2'     => '5.6.2 Alpha 2',
		'562a3'     => '5.6.2 Alpha 3',
		'562a4'     => '5.6.2 Alpha 4',
		'562b1'     => '5.6.2 Beta 1',
		'562b2'     => '5.6.2 Beta 2',
		'562rc1'    => '5.6.2 Release Candidate 1',
		'562rc2'    => '5.6.2 Release Candidate 2',
		'562'       => '5.6.2',
		'563a1'     => '5.6.3 Alpha 1',
		'563a2'     => '5.6.3 Alpha 2',
		'563a3'     => '5.6.3 Alpha 3',
		'563a4'     => '5.6.3 Alpha 4',
		'563b1'     => '5.6.3 Beta 1',
		'563b2'     => '5.6.3 Beta 2',
		'563b3'     => '5.6.3 Beta 3',
		'563rc1'    => '5.6.3 Release Candidate 1',
		'563'       => '5.6.3',
		'564a1'     => '5.6.4 Alpha 1',
		'564a2'     => '5.6.4 Alpha 2',
		'564a3'     => '5.6.4 Alpha 3',
		'564a4'     => '5.6.4 Alpha 4',
		'564b1'     => '5.6.4 Beta 1',
		'564b2'     => '5.6.4 Beta 2',
		'564rc1'    => '5.6.4 Release Candidate 1',
		'564rc2'    => '5.6.4 Release Candidate 2',
		'564rc3'    => '5.6.4 Release Candidate 3',
		'564'       => '5.6.4',
		'565a1'     => '5.6.5 Alpha 1',
		'565a2'     => '5.6.5 Alpha 2',
		'565a3'     => '5.6.5 Alpha 3',
		'565a4'     => '5.6.5 Alpha 4',
		'565a5'     => '5.6.5 Alpha 5',
		'565a6'     => '5.6.5 Alpha 6',
		'565a7'     => '5.6.5 Alpha 7',
		'565a8'     => '5.6.5 Alpha 8',
		'565a9'     => '5.6.5 Alpha 9',
		'565a10'    => '5.6.5 Alpha 10',
		'565a11'    => '5.6.5 Alpha 11',
		'565b1'     => '5.6.5 Beta 1',
		'565b2'     => '5.6.5 Beta 2',
		'565rc1'    => '5.6.5 Release Candidate 1',
		'565rc2'    => '5.6.5 Release Candidate 2',
		'565'       => '5.6.5',
		'566a1'     => '5.6.6 Alpha 1',
		'566a2'     => '5.6.6 Alpha 2',
		'566a3'     => '5.6.6 Alpha 3',
		'566a4'     => '5.6.6 Alpha 4',
		'566a5'     => '5.6.6 Alpha 5',
		'566b1'     => '5.6.6 Beta 1',
		'566b2'     => '5.6.6 Beta 2',
		'566rc1'    => '5.6.6 Release Candidate 1',
		'566rc2'    => '5.6.6 Release Candidate 2',
		'566'       => '5.6.6',
		'567a1'     => '5.6.7 Alpha 1',
		'567a2'     => '5.6.7 Alpha 2',
		'567a3'     => '5.6.7 Alpha 3',
		'567a4'     => '5.6.7 Alpha 4',
		'567b1'     => '5.6.7 Beta 1',
		'567b2'     => '5.6.7 Beta 2',
		'567rc1'    => '5.6.7 Release Candidate 1',
		'567'       => '5.6.7',
		'568a1'     => '5.6.8 Alpha 1',
		'568a2'     => '5.6.8 Alpha 2',
		'568a3'     => '5.6.8 Alpha 3',
		'568a4'     => '5.6.8 Alpha 4',
		'568b1'     => '5.6.8 Beta 1',
		'568b2'     => '5.6.8 Beta 2',
		'568rc1'    => '5.6.8 Release Candidate 1',
		'568'       => '5.6.8',
		'569a1'     => '5.6.9 Alpha 1',
		'569a2'     => '5.6.9 Alpha 2',
		'569a3'     => '5.6.9 Alpha 3',
		'569a4'     => '5.6.9 Alpha 4',
		'569b1'     => '5.6.9 Beta 1',
		'569b2'     => '5.6.9 Beta 2',
		'569rc1'    => '5.6.9 Release Candidate 1',
		'569rc2'    => '5.6.9 Release Candidate 2',
		'569'       => '5.6.9',
		'570a1'     => '5.7.0 Alpha 1',
		'570a2'     => '5.7.0 Alpha 2',
		'570a3'     => '5.7.0 Alpha 3',
		'570a4'     => '5.7.0 Alpha 4',
		'570b1'     => '5.7.0 Beta 1',
		'570b2'     => '5.7.0 Beta 2',
		'570rc1'    => '5.7.0 Release Candidate 1',
		'570rc2'    => '5.7.0 Release Candidate 2',
		'570'       => '5.7.0',
		'571a1'     => '5.7.1 Alpha 1',
		'571a2'     => '5.7.1 Alpha 2',
		'571a3'     => '5.7.1 Alpha 3',
		'571a4'     => '5.7.1 Alpha 4',
		'571b1'     => '5.7.1 Beta 1',
		'571b2'     => '5.7.1 Beta 2',
		'571rc1'    => '5.7.1 Release Candidate 1',
		'571rc2'    => '5.7.1 Release Candidate 2',
		'571'       => '5.7.1',
		'572a1'     => '5.7.2 Alpha 1',
		'572a2'     => '5.7.2 Alpha 2',
		'572a3'     => '5.7.2 Alpha 3',
		'572a4'     => '5.7.2 Alpha 4',
		'572b1'     => '5.7.2 Beta 1',
		'572b2'     => '5.7.2 Beta 2',
		'572rc1'    => '5.7.2 Release Candidate 1',
		'572rc2'    => '5.7.2 Release Candidate 2',
		'572rc3'    => '5.7.2 Release Candidate 3',
		'572'       => '5.7.2',
		'573a1'     => '5.7.3 Alpha 1',
		'573a2'     => '5.7.3 Alpha 2',
		'573a3'     => '5.7.3 Alpha 3',
		'573a4'     => '5.7.3 Alpha 4',
		'573b1'     => '5.7.3 Beta 1',
		'573b2'     => '5.7.3 Beta 2',
		'573rc1'    => '5.7.3 Release Candidate 1',
		'573rc2'    => '5.7.3 Release Candidate 2',
		'573'       => '5.7.3',
		'574a1'     => '5.7.4 Alpha 1',
		'574a2'     => '5.7.4 Alpha 2',
		'574a3'     => '5.7.4 Alpha 3',
		'574a4'     => '5.7.4 Alpha 4',
		'574b1'     => '5.7.4 Beta 1',
		'574b2'     => '5.7.4 Beta 2',
		'574rc1'    => '5.7.4 Release Candidate 1',
		'574'       => '5.7.4',
		'575a1'     => '5.7.5 Alpha 1',
		'575a2'     => '5.7.5 Alpha 2',
		'575a3'     => '5.7.5 Alpha 3',
		'575a4'     => '5.7.5 Alpha 4',
		'575b1'     => '5.7.5 Beta 1',
		'575b2'     => '5.7.5 Beta 2',
		'575rc1'    => '5.7.5 Release Candidate 1',
		'575'       => '5.7.5',
		'576a1'     => '5.7.6 Alpha 1',
		'576a2'     => '5.7.6 Alpha 2',
		'576a3'     => '5.7.6 Alpha 3',
		'57*'       => '',
		'600a1'     => '6.0.0 Alpha 1',
		'600a2'     => '6.0.0 Alpha 2',
		'600a3'     => '6.0.0 Alpha 3',
		'600a4'     => '6.0.0 Alpha 4',
		'600a5'     => '6.0.0 Alpha 5',
		'600a6'     => '6.0.0 Alpha 6',
		'600a7'     => '6.0.0 Alpha 7',
		'600a8'     => '6.0.0 Alpha 8',
		'600b1'     => '6.0.0 Beta 1',
		'600b2'     => '6.0.0 Beta 2',
		'600rc1'    => '6.0.0 Release Candidate 1',
		'600rc2'    => '6.0.0 Release Candidate 2',
		'600rc3'    => '6.0.0 Release Candidate 3',
		'600'       => '6.0.0',
		'601a1'     => '6.0.1 Alpha 1',
		'601a2'     => '6.0.1 Alpha 2',
		'601a3'     => '6.0.1 Alpha 3',
		'601a4'     => '6.0.1 Alpha 4',
		'601b1'     => '6.0.1 Beta 1',
		'601b2'     => '6.0.1 Beta 2',
		'601rc1'    => '6.0.1 Release Candidate 1',
		'601rc2'    => '6.0.1 Release Candidate 2',
		'601'       => '6.0.1',
		'602a1'     => '6.0.2 Alpha 1',
		'602a2'     => '6.0.2 Alpha 2',
		'602a3'     => '6.0.2 Alpha 3',
		'602a4'     => '6.0.2 Alpha 4',
		'602b1'     => '6.0.2 Beta 1',
		'602b2'     => '6.0.2 Beta 2',
		'602rc1'    => '6.0.2 Release Candidate 1',
		'602'       => '6.0.2',
		'603a1'     => '6.0.3 Alpha 1',
		'603a2'     => '6.0.3 Alpha 2',
		'603a3'     => '6.0.3 Alpha 3',
		'603a4'     => '6.0.3 Alpha 4',
		'603b1'     => '6.0.3 Beta 1',
		'603b2'     => '6.0.3 Beta 2',
		'603rc1'    => '6.0.3 Release Candidate 1',
		'603rc2'    => '6.0.3 Release Candidate 2',
		'603'       => '6.0.3',
		'604a1'     => '6.0.4 Alpha 1',
		'604a2'     => '6.0.4 Alpha 2',
		'604a3'     => '6.0.4 Alpha 3',
		'604a4'     => '6.0.4 Alpha 4',
		'604b1'     => '6.0.4 Beta 1',
		'604b2'     => '6.0.4 Beta 2',
		'604rc1'    => '6.0.4 Release Candidate 1',
		'604rc2'    => '6.0.4 Release Candidate 2',
		'604'       => '6.0.4',
		'605a1'     => '6.0.5 Alpha 1',
		'605a2'     => '6.0.5 Alpha 2',
		'605a3'     => '6.0.5 Alpha 3',
		'605a4'     => '6.0.5 Alpha 4',
		'605b1'     => '6.0.5 Beta 1',
		'605b2'     => '6.0.5 Beta 2',
		'605rc1'    => '6.0.5 Release Candidate 1',
		'605'       => '6.0.5',
		'606a1'     => '6.0.6 Alpha 1',
		'606a2'     => '6.0.6 Alpha 2',
		'606a3'     => '6.0.6 Alpha 3',
		'606a4'     => '6.0.6 Alpha 4',
		'606b1'     => '6.0.6 Beta 1',
		'606b2'     => '6.0.6 Beta 2',
		'606rc1'    => '6.0.6 Release Candidate 1',
		'606'       => '6.0.6',
	];

	/**
	* Array of non vB version scripts. 'final' must be at the end
	*
	* @var array
	*/
	protected $endscripts = [
		'final',
	];

	/**
	* Array of products installed by suite
	*
	* @var array
	*/
	protected $products = [];

	/**
	* Execution type, either 'browser' or 'cli'
	*
	* @var string
	*/
	protected $exectype = null;

	/**
	* Phrases
	*
	* @var	array
	*/
	protected $phrase = [];

	/**
	* Startup Errors
	*
	* @var	array
	*/
	protected $startup_errors = [];

	/**
	* Setup type, new install or upgrade?
	*
	* @var	string
	*/
	protected $setuptype = 'upgrade';

	//these should be overridden by the child classes.
	protected $identifier = '';
	protected $limitqueries = true;

	protected bool $showWarnings = false;

	/**
	* Constructor.
	*
	* @param	vB_Registry	Reference to registry object
	* @var	string	Setup type - 'install' or 'upgrade'
	*/
	public function __construct(&$registry, $phrases, $setuptype = 'upgrade', $script = null, $options = [])
	{
		$this->showWarnings = !empty($options['showwarnings']);

		if (empty($registry))
		{
			$registry = vB::get_registry();
		}

		if (is_object($registry))
		{
			$this->registry =& $registry;
			$this->db =& $this->registry->db;
		}
		else
		{
			throw new Exception('vB_Upgrade: $this->registry is not an object.');
		}

		$this->setuptype = $setuptype;
		$this->phrase = $phrases;

		require_once(DIR . '/includes/adminfunctions.php');

		$this->verify_environment();
		$this->setup_environment_static();

		//we probably want to bail at this point for *any* errors but taking the cowards way out
		//at the moment to avoid causing more problems than I solve.
		if (!$this->db->is_valid() AND $this->startup_errors)
		{
			$this->show_errors_only();
			return;
		}

		$this->setup_environment();
		$this->sync_database();

		$config = vB::getConfig();
		if (!empty($config['Misc']['debug']) AND file_exists(DIR . '/install/includes/class_upgrade_dev.php'))
		{
			array_unshift($this->endscripts, 'dev');
		}

		//the options execute hack has been removed, I don't think we need it.  Only the CLI implementation
		//respects it and it was put in place to allow creating a new copy of the library without actually
		//running things.  Solved a different way.  We can probably remove this now.
		if (isset($options['execute']))
		{
			$this->init($script, $options['execute']);
		}
		else
		{
			$this->init($script);
		}
	}

	protected abstract function show_errors_only();

	/**
	* Init
	*
	*  	@param	string	the script to be process
	* 	@param	bool	whether to process the script immediately
	*/
	protected function init($script, $process = true)
	{
		//Set version number, its needed by the upgrader.
		$this->registry->versionnumber =& $this->registry->options['templateversion'];

		// Where does this upgrade need to begin?
		$this->scriptinfo = $this->get_upgrade_start();
	}

	/**
	* Things to do after each script is processed
	*
	*/
	protected function process_script_end()
	{
		build_bbcode_cache();
		$this->registry->options = vB::getDatastore()->build_options();
		require_once(DIR . '/includes/functions_databuild.php');
		vB_Upgrade::createAdminSession();
		require_once(DIR . '/includes/class_bitfield_builder.php');
		vB_Bitfield_Builder::save($this->db);
	}

	//hack to allow version script to load the final script to load some things early.
	public function load_final()
	{
		return $this->load_script('final');
	}

	/**
	*	Load an upgrade script and return object
	*
	*	@var	string	Version number
	*
	* @return object
	*/
	protected function load_script($version)
	{
		// ensure comparisons are done as strings
		$version = (string) $version;

		$classname = $this->get_upgrade_classname($version);

		$script = new $classname($this->registry, $this->phrase, $version, $this->versions, $this->showWarnings);
		$script->library = $this;
		$script->caller = $this->identifier;
		$script->limitqueries = $this->limitqueries;

		return $script;
	}

	protected function get_upgrade_classname($version)
	{
		//if we don't find anything default to the empty class.
		$classname = 'vB_Upgrade_Version_Empty';

		$tags = $this->get_upgrade_scripttags($version);
		foreach ($tags AS $tag)
		{
			$versionfile = DIR . "/install/includes/class_upgrade_$tag.php";
			if (file_exists($versionfile))
			{
				require_once($versionfile);
				$tempclass = "vB_Upgrade_$version";
				if (class_exists($tempclass, false))
				{
					$classname = $tempclass;
					break;
				}
			}
		}
		return $classname;
	}

	private function get_upgrade_scripttags($version)
	{
		//the full version is always a tag.
		$tags = [$version];

		//we can't collaspe end scripts (currently only final).
		if (!in_array($version, $this->endscripts))
		{
			//find the base version number as a string.
			$version = substr($version, 0, strspn($version, '0123456789'));

			//Assume that any four digit version numbers are d.d.dd in format.
			//This is ambiguous without the delimiters but we have some.
			$tags[] = substr($version, 0, 2) . 'x';
			$tags[] = substr($version, 0, 1) . 'xx';
		}

		return $tags;
	}

	/**
	*	Verify if specified version number is the next version that we should be upgrading to
	*
	*	@var	string	Version number
	*
	* @return bool
	*/
	protected function verify_version($version, $script)
	{
		if ($version == 'install')
		{
			return true;
		}

		if (
			version_compare($this->registry->options['templateversion'], $script->VERSION_COMPAT_STARTS, '>=') AND
			version_compare($this->registry->options['templateversion'], $script->VERSION_COMPAT_ENDS, '<')
		)
		{
			return true;
		}
		else if ($this->registry->options['templateversion'] == $script->PREV_VERSION)
		{
			return true;
		}
		else if (in_array($version, $this->endscripts) AND end($this->versions) == $this->registry->options['templateversion'])
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	* Fetch the upgrade log information from the database - past upgrade process
	*
	* @var		string	If defined, start upgrade at this version
	*
	* @return	array	Version information about upgrade point at which to start
	*/
	protected function get_upgrade_start($version = null)
	{
		if ($this->setuptype == 'install' AND !$version)
		{
			return [
				'version' => 'install',
				'only' => false,
				'step' => 1,
				'startat' => 0,
			];
		}

		//the logic below relies on the shortversions being strings and not ints, while PHP
		//will "helpfully" convert integer strings to integers when used as array keys.
		$shortversions = array_map('strval', array_keys($this->versions));

		$gotlog = false;
		if (!$version)
		{
			if ($log = $this->db->query_first("SELECT * FROM " . TABLE_PREFIX . "upgradelog ORDER BY upgradelogid DESC LIMIT 1"))
			{
				$gotlog = true;
			}
		}

		if ($gotlog)
		{
			if (!preg_match('/^upgrade_(\w+)\.php$/siU', $log['script'], $reg))
			{
				$gotlog = false;

				if (in_array($log['script'], $this->endscripts) OR preg_match('#^\d+((a|b|g|rc|pl)\d+)?$#si', $log['script']))
				{
					$gotlog = true;
					$scriptver = $log['script'];
				}
			}
			else
			{
				if (!array_search($reg[1], $shortversions))
				{
					$gotlog = false;
				}
				else
				{
					$scriptver = $reg[1];
				}
			}
		}

		if ($gotlog)
		{
			if ($log['step'] == 0)
			{
				// the last entry has step = 0, meaning the script completed...
				$versionkey = array_search($scriptver, $shortversions);
				$shorten = 0;

				$wildversion = '';
				while ($versionkey === false AND $wildversion != '*')
				{
					$wildversion = substr_replace($scriptver, '*', --$shorten);
					$versionkey = array_search($wildversion, $shortversions);
				}

				//note that $shortversions[false] is interpreted as $shortversions[0] so isset($shortversions[false]) is true
				//and without the versionkey check here (added for a different reason) this can infinite loop in some edge cases.
				if ($versionkey !== false)
				{
					++$versionkey;

					// to handle the case when we are running the version before a wildcard version
					while (isset($shortversions[$versionkey]) AND strpos($shortversions[$versionkey], '*') !== false)
					{
						++$versionkey;
					}
				}

				if ($versionkey !== false AND isset($shortversions["$versionkey"]))
				{
					$scriptinfo['version'] = $shortversions["$versionkey"];
				}
				else if (($currentkey = array_search($scriptver, $this->endscripts)) !== false)
				{
					$scriptinfo['version'] = $this->endscripts[$currentkey + 1];
				}
				else
				{
					$scriptinfo['version'] = $this->endscripts[count($this->products)];	// any non suite products
				}

				$scriptinfo['only'] = false;
				$scriptinfo['step']    = 1;
				$scriptinfo['startat'] = 0;
			}
			else if ($log['startat'])
			{
				$scriptinfo['version'] = $scriptver;
				$scriptinfo['step']    = $log['step'];
				$scriptinfo['startat'] = $log['startat'] + $log['perpage'];
				$scriptinfo['only'] = $log['only'];
			}
			else
			{
				$scriptinfo['version'] = $scriptver;
				$scriptinfo['step']    = $log['step'] + 1;
				$scriptinfo['only']    = $log['only'];
				$scriptinfo['startat'] = 0;
			}
		}
		else
		{
			if ($version)
			{
				$shortver = $version;
			}
			else
			{
				$shortver = $this->fetch_short_version($this->registry->versionnumber);
			}

			//note that if $shortver is the special value "install" then versionkey will be ''
			if (!$version AND in_array($this->registry->options['templateversion'], $this->versions))
			{
				$key = array_search($this->registry->options['templateversion'], $this->versions, true);
				$versionkey = array_search((string)$key, $shortversions, true);
			}
			else
			{
				$versionkey = array_search($shortver, $shortversions);
			}

			$shorten = 0;
			$wildversion = '';
			while ($versionkey === false AND $wildversion != '*')
			{
				$wildversion = substr_replace($shortver, '*', --$shorten);
				$versionkey = array_search($wildversion, $shortversions);
			}

			if ($versionkey !== false)
			{
				++$versionkey;
				// to handle the case when we are running the version before a wildcard version
				while ($versionkey AND isset($shortversions[$versionkey]) AND strpos($shortversions[$versionkey], '*') !== false)
				{
					++$versionkey;
				}
			}

			$onproduct = false;
			if ($versionkey !== false AND isset($shortversions[$versionkey]))
			{
				// we know what script this version needs to go to
				$scriptinfo['version'] = $shortversions["$versionkey"];
				$onproduct = true;
			}
			else if ($shortver != 'final' AND (($value = array_search($shortver, $this->endscripts)) !== false))
			{
				$scriptinfo['version'] = $this->endscripts[$value + 1];
				$onproduct = true;
			}
			else if (($version == 'install' OR ($versionkey == count($shortversions))))
			{
				$scriptinfo['version'] = $this->endscripts[0]; // 'vbblog'
				$onproduct = true;
			}

			if (!$onproduct)
			{
				if (in_array(intval($this->registry->versionnumber), [3,4,5]))
				{
					// assume we are finished
					$scriptinfo['version'] = 'final';
				}
				else
				{
					//this probably can't happen and probably won't work but not really sure changing it is worth the trouble.
					// no log and invalid version, so assume it's 2.x
					$scriptinfo['version'] = '400';
				}
			}

			//no matter how we get the version we don't set the other keys here.  Let's default them to
			//something that will work.
			$scriptinfo['only'] = false;
			$scriptinfo['step']    = 1;
			$scriptinfo['startat'] = 0;
		}

		return $scriptinfo;
	}

	//this is likely obsolete.  It used to determine if a vB4 install was a suite install or not
	//based on the presense/absense of the suite products.  It appears that the product list is
	//always empty (and it's looking in the wrong place for vB5 products).
	protected function install_suite()
	{
		foreach ($this->products as $productid)
		{
			if (!file_exists(DIR . "/includes/xml/product-$productid.xml"))
			{
				return false;
			}
		}
		return true;
	}

	/**
	* Convert a "Long version" string into a short version
	*
	* @var string
	*
	* @return string
	*/
	protected function fetch_short_version($version, $typeonly = false)
	{
		if (preg_match('/^(\w+\s+)?(\d+)\.(\d+)\.(\d+)(\s+(a|alpha|b|beta|g|gamma|rc|release candidate|gold|stable|final|pl|patch level)(\s+(\d+))?)?$/siU', $version, $regs))
		{
			$major = $regs[2];
			$minor = $regs[3];
			$point = $regs[4];

			//we aren't guarenteed to have a value in the matches array because re group is optional.
			$modifier = $regs[6] ?? '';
			$plnumber = $regs[8] ?? '';

			switch (strtolower($modifier))
			{
				case 'alpha':
					$type = -5;
					$modifier = 'a';
					break;
				case 'beta':
					$type = -4;
					$modifier = 'b';
					break;
				case 'gamma':
					$type = -3;
					$modifier = 'g';
					break;
				case 'release candidate':
					$type = -2;
					$modifier = 'rc';
					break;
				case 'patch level':
					$type = 1;
					$modifier = 'pl';
					break;
				case 'gold':
				case 'stable':
				case 'final':
					$type = -1;
					$modifier = '';
					break;
				default:
					$type = 0;
					break;
			}

			if ($typeonly)
			{
				return $type;
			}
			else
			{
				return $major . $minor . $point . $modifier . $plnumber;
			}
		}
		else
		{
			if ($typeonly)
			{
				return 2; // Non standard type
			}
			else
			{
				return $version;
			}
		}
	}

	/**
	 * Database queries that need to be executed to ensure that the database is in a known state that is functional
	 * with the upgrade. Pre 3.6.0 there were quite a bit of queries here
	 */
	protected function sync_database()
	{
		if (defined('SKIPDB'))
		{
			return;
		}

		$this->db->hide_errors();


		//this is related to the file based datastore class.
		// need to do this here or we might get problems if options are built before the end of the script
		$this->db->query_write("REPLACE INTO " . TABLE_PREFIX . "adminutil (title, text) VALUES ('datastorelock', '0')");

		// post_parsed needs to be called postparsed for some of the rebuild functions to work correctly
		$this->startup_alter("ALTER TABLE " . TABLE_PREFIX . "post_parsed RENAME " . TABLE_PREFIX . "postparsed");

		// These tables are referenced by upgrade scripts that predate these modifications
		$this->startup_alter("ALTER TABLE " . TABLE_PREFIX . "upgradelog ADD only TINYINT NOT NULL DEFAULT '0'");
		$this->startup_alter("ALTER TABLE " . TABLE_PREFIX . "adminmessage ADD args MEDIUMTEXT");

		// When vB_Upgrade::init creates an adminsession to rebuild bitfields, the language
		// table is eventually accessed downstream in the fetchLanguage() stored query,
		// which explicitly selects the 'eventdateformatoverride' field. If this field is
		// not present, the admin user language setup then fails with the error "The requested
		// language does not exist, reset via tools.php", causing the upgrade to fail before
		// the upgrade step that creates the column can run. This matches the add_field
		// call in 531a2 step_1
		$this->startup_alter("ALTER TABLE " . TABLE_PREFIX . "language ADD eventdateformatoverride VARCHAR(50) NOT NULL default ''");
		// The same applies to the pickerdateformatoverride field.
		// This matches the add_field call in 532a4 step_1
		$this->startup_alter("ALTER TABLE " . TABLE_PREFIX . "language ADD pickerdateformatoverride VARCHAR(50) NOT NULL default ''");

		$this->startup_alter("ALTER TABLE " . TABLE_PREFIX . "setting ADD adminperm VARCHAR(32) NOT NULL default ''");
		$this->startup_alter("ALTER TABLE " . TABLE_PREFIX . "settinggroup ADD adminperm VARCHAR(32) NOT NULL default ''");

		//this field is added in 500rc2 and will cause problems if we try to create a user session prior to it existing.
		$this->startup_alter("ALTER TABLE " . TABLE_PREFIX . "permission ADD forumpermissions2 INT UNSIGNED NOT NULL default 0");

		// This field is added in 566a5 (copied to 500a1), and core queries depend on it existing (e.g. queries that getFullContent depends on).
		// Note that some embedded data generated between 500a2 & 566a4 that expect displaynames may be incorrect while the field is empty before 566a5.
		$this->startup_alter("ALTER TABLE " . TABLE_PREFIX . "user ADD displayname VARCHAR(191) NOT NULL DEFAULT ''");

		$this->db->show_errors();
	}

	/**
	 * Database queries that need to be executed to ensure that the database is in a known state that is functional
	 * with the upgrade. Pre 3.6.0 there were quite a bit of queries here.
	 *	Error: 1142 SQLSTATE: 42000 (ER_TABLEACCESS_DENIED_ERROR)
	 *	Message: %s command denied to user '%s'@'%s' for table '%s'
	 *	Error: 1143 SQLSTATE: 42000 (ER_COLUMNACCESS_DENIED_ERROR)
	 *	Message: %s command denied to user '%s'@'%s' for column '%s' in table '%s'	 *
	 *
	 * @param	string	Alter Query
	 *
	 */
	private function startup_alter($query)
	{
		static $found = false;

		if ($errorstate = $this->db->reporterror)
		{
			$this->db->hide_errors();
		}
		$this->db->query_write($query);
		if ($errorstate)
		{
			$this->db->show_errors();
		}

		if (!$found AND ($this->db->errno == 1142 OR $this->db->errno == 1143))
		{
			$this->startup_errors[] = $this->phrase['core']['no_alter_permission'];
			$found = true;
		}
	}

	/**
	 * Verify CSS dir can be written to
	 *
	 * @param	int	$styleid -- -1 to check all
	 *
	 * @return	boolean
	 */
	private function verify_cssdir($styleid = -1)
	{
		if ($this->setuptype == 'install' OR empty($this->registry->options['storecssasfile']))
		{
			return true;
		}

		if ($styleid != -1)
		{
			if (!$this->verify_write_cssdir($styleid, 'ltr') OR !$this->verify_write_cssdir($styleid, 'rtl'))
			{
				return false;
			}
		}

		$db = vB::getDbAssertor();
		$childsets = $db->select('style', ['parentid' => $styleid], false, ['styleid']);
		foreach ($childsets AS $childset)
		{
			if (!$this->verify_cssdir($childset['styleid']))
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Verify directory can be written to
	 *
	 * @param	int	Styelid
	 * @param	str	Text direction
	 *
	 * @return	boolean	Success
	 */
	private function verify_write_cssdir($styleid, $dir = 'ltr')
	{
		$styledir = vB_Api::instanceInternal('style')->getCssStyleDirectory($styleid, $dir);
		$styledir = $styledir['directory'];

		//if exists then it should be a directory and writable, otherwise we have a problem
		if (file_exists($styledir))
		{
			return (is_dir($styledir) AND is_writable($styledir));
		}

		static $cancreatewritable = null;

		if (is_null($cancreatewritable))
		{
			//attempt to create the directory.  We may or may not need a directory for each style
			//but we probably don't have the ability to check at this point -- the fields and
			//other relevant information simply may not exist yet, especially if this is a vB4
			//upgrade. But if we can create writiable directories for the styles, it will all
			//work out later, we don't actually need to create them here.  But the best way
			//to check that is to create a directory and then remove it.  We'll make the assumption
			//that if it works once, it will keep working to avoid doing this every time.

			//create the directory -- if it still exists try to continue with the existing dir
			if (!@mkdir($styledir))
			{
				$cancreatewritable= false;
			}
			else
			{
				$cancreatewritable = (is_dir($styledir) AND is_writable($styledir));
				@rmdir($styledir);
			}
		}

		return $cancreatewritable;
	}

	/**
	 * Verify conditions are acceptable to perform the upgrade/install
	 */
	protected function verify_environment()
	{
		$install_versions = [];
		//defines "install_versions" array -- seperated to a seperate file for easy editing.
		require(DIR . '/install/install_versions.php');

		// php version check
		if (version_compare(PHP_VERSION, $install_versions['php_required'], '<'))
		{
			$this->startup_errors[] = sprintf($this->phrase['core']['php_version_too_old'], $install_versions['php_required'], PHP_VERSION);
		}

		//if MYSQL_VERSION isn't defined its because we are in the installer and haven't figured out the database yet.  We'll
		//verify the version separately when do.  Note that if we change this logic we also need to change the verify_install_environment below.
		if (defined('MYSQL_VERSION'))
		{
			$this->checkDBVersion(MYSQL_VERSION, $install_versions);
		}

		// config file check
		if (!file_exists(DIR . '/includes/config.php'))
		{
			$this->startup_errors[] = $this->phrase['core']['cant_find_config'];
		}
		else if (!is_readable(DIR . '/includes/config.php'))
		{
			$this->startup_errors[] = $this->phrase['core']['cant_read_config'];
		}

		if (!$this->verify_cssdir())
		{
			$this->startup_errors[] = $this->phrase['core']['css_not_writable'];
		}

		// Actually will never get here if the 'connect' function doesn't exist as we've already tried to connect
		if (!$this->db->is_install_valid())
		{
			$this->startup_errors[] = sprintf($this->phrase['core']['database_functions_not_detected'], 'mysqli');
			//if we hit this error, anything further is going to be a trainwreck.
			return;
		}

		if (!file_exists($this->get_vb_root() . '/.htaccess'))
		{
			$this->startup_warnings[] = $this->phrase['core']['htaccess_file_not_found'];
		}

		// Cryto secure random generation is better supported both by operating systems and
		// PHP than they used to be so I'm not sure how necesary this check is.  I also suspect
		// the solution in the error message may be out of date.  But it still seems possible
		// to get an error here due to configuration issues so not removing the check.
		try
		{
			(new vB_Utility_Random())->hex(20);
		}
		catch (Exception $e)
		{
			$this->startup_errors[] = $this->phrase['core']['required_psrng_missing'];
		}

		$this->verify_install_environment($install_versions);
		$this->verify_files();
	}

	protected function verify_files()
	{
		if (!empty($this->startup_errors) OR defined('SKIP_UPGRADE_FILE_CHECK'))
		{
			return;
		}

		$hashChecker = vB::getHashchecker();
		$check = $hashChecker->verifyFiles();

		if (!$check['success'])
		{
			foreach ($check['fatalErrors'] AS $__error)
			{
				$this->startup_warnings[] = $this->get_parsed_phrase($__error);
			}
			return;
		}

		$errorstring = '';

		if (!empty($check['startupWarnings']))
		{
			foreach ($check['startupWarnings'] AS $__phrase)
			{
				$this->startup_warnings[] = $this->get_parsed_phrase($__phrase);
			}
		}

		if (!empty($check['errors']))
		{
			$errorstring .= $this->phrase['core']['suspect_files_detected'];
		}

		foreach ($check['fileCounts'] AS $directory => $__filecount)
		{
			if (isset($check['errors'][$directory]))
			{
				foreach ($check['errors'][$directory] AS $file => $errors)
				{
					foreach ($errors AS $__key => $__val)
					{
						$errors[$__key] = $this->get_parsed_phrase($__val);
					}
					if ($directory == DIRECTORY_SEPARATOR)
					{
						$filename = DIRECTORY_SEPARATOR . $file;
					}
					else
					{
						$filename = $directory . DIRECTORY_SEPARATOR . $file;
					}
					$errorstring .= "\n<br /><strong>$filename</strong> - " . implode('<br />', $errors);
				}
			}
		}
		/*
		TODO: FLAG SKIPPED FOLDERS & FILES FOR MANUAL REVIEW - Skipping as it's new behavior not in scope,
		but need to revisit this later.
		if (!empty($check['skippedDirs']))
		{
		}
		if (!empty($check['skippedFiles']))
		{
		}
		 */

		if ($errorstring)
		{
			$this->startup_warnings[] = $errorstring;
		}
	}

	private function get_parsed_phrase($phraseData, $group = 'core')
	{
		/*
		We might get a {phraseid} or [{phraseid}, {phrasedata1}...]
		We can't quite just use construct_phrase() and call it a day
		because we have to exchange the phraseid for the raw phrase
		first.
		 */
		$ogphraseData = $phraseData;
		if (is_string($phraseData))
		{
			$phrase = $this->phrase[$group][$phraseData] ?? $phraseData;
		}
		else
		{
			$phraseData[0] = $this->phrase[$group][$phraseData[0]] ?? $phraseData[0];
			$phrase = construct_phrase_from_array($phraseData);
		}

		return $phrase;
	}

	protected function verify_install_environment($install_versions)
	{
		if (defined('SKIPDB'))
		{
			$vb5_config =& vB::getConfig();
			$this->db->hide_errors();

			$db_error = '';
			// make database connection
			try
			{
				$this->db->connect_using_dbconfig();
			}
			catch(vB_Exception_Database $e)
			{
				$db_error = $e->getMessage();
			}

			$connect_errno = $this->db->errno();
			$connect_error = ($this->db->error ? $this->db->error : $this->db->error());

			if ($this->db->is_valid())
			{
				$no_force_sql_mode = vB::getDbAssertor()->getNoForceSqlMode();
				if (empty($no_force_sql_mode))
				{
					$this->db->force_sql_mode('');
					// small hack to prevent the above query from generating an error below
					$this->db->query_read('SELECT 1 + 1');
				}
				//mysql version check on install
				$mysqlversion = $this->db->query_first("SELECT version() AS version");
				define('MYSQL_VERSION', $mysqlversion['version']);
				$this->checkDBVersion(MYSQL_VERSION, $install_versions);

				if ($connect_errno)
				{ // error found
					if ($connect_errno == 1049)
					{
						$this->db->create_database_using_dbconfig();
						$this->db->select_db_using_dbconfig();
						if ($this->db->errno() == 1049)
						{
							// unable to create database
							$this->startup_errors[] = sprintf($this->phrase['install']['unable_to_create_db']);
						}
					}
					else
					{ // Unknown Error
						$this->startup_errors[] = sprintf($this->phrase['install']['connect_failed'], $connect_errno, $connect_error);
					}
				}
			}
			else
			{
				// Unable to connect to database
				$error = ($connect_error ? $connect_error : $db_error);
				if ($error)
				{
					$this->startup_errors[] = sprintf($this->phrase['install']['db_error_desc'], $error);
				}
				$this->startup_errors[] = $this->phrase['install']['no_connect_permission'];
			}
			$this->db->show_errors();
		}
	}

	/**
	 *	Check the db version
	 *
	 *	Also will set an error in this->startup_errors if the check fails
	 *
	 *	@return boolean true if passed/false otherwise
	 */
	protected function checkDBVersion($versionString, $install_versions)
	{
		$versionInfo = explode('-', $versionString);

		//mysql just returns the version
		if (count($versionInfo) == 1)
		{
			$required_version = $install_versions['mysql_required'];
			$database_type = 'MySql';
		}
		else if ($versionInfo[1] == 'MariaDB')
		{
			$required_version = $install_versions['mariadb_required'];
			$database_type = 'MariaDB';
		}
		else
		{
			//if we don't know what we are dealing with pretend its mysql.
			//if we got this far it answered to mysql syntax and probably uses mysql versioning
			//its better to pass the check and fail on install than it is to refuse an install
			//that succeeds.
			$required_version = $install_versions['mysql_required'];
			$database_type = 'MySql';
		}

		if (version_compare($versionInfo[0], $required_version, '<'))
		{
			$this->startup_errors[] = sprintf(
				$this->phrase['core']['database_version_too_old'],
				$required_version,
				$versionInfo[0],
				$database_type
			);
			return false;
		}
		return true;
	}

	/**
	 * Setup environment common to all upgrades for the install specific code
	 */
	//This setup assumes very little is working aside from the core install files.
	//In particular do not assume that there is a functional database connection at this point
	//Primary we need to bootstrap the system here sufficient to display critical startup errors.
	private function setup_environment_static()
	{
		/* We always use vBulletin_5_Default because when upgrading
		from vB3 and vB4 we don't have the old cp styles any more. */
		$this->registry->options['cpstylefolder'] = 'vBulletin_5_Default';

		vB_Utility_Functions::setPhpTimeout(0);

		if (!defined('VERSION'))
		{
			define('VERSION', defined('FILE_VERSION') ? FILE_VERSION : '');
		}

		// Notices
		$vb5_config =& vB::getConfig();
		if (!empty($vb5_config['Database']['no_force_sql_mode']))
		{
			// check to see if MySQL is running strict mode and recommend disabling it
			$this->db->hide_errors();
			$strict_mode_check = $this->db->query_first("SHOW VARIABLES LIKE 'sql\\_mode'");
			if (strpos(strtolower($strict_mode_check['Value']), 'strict_') !== false)
			{
				$this->startup_warnings[] = $this->phrase['core']['mysql_strict_mode'];
			}
			$this->db->show_errors();
		}

		if (is_array($this->phrase['stylevar']))
		{
			foreach ($this->phrase['stylevar'] AS $stylevarname => $stylevarvalue)
			{
				vB_Template_Runtime::addStyleVar($stylevarname, $stylevarvalue);
			}
		}


		// Get versions of .xml files for header diagnostics
		foreach ($this->xml_versions AS $file => $null)
		{
			if ($fp = @fopen(DIR . '/install/vbulletin-' . $file . '.xml', 'rb'))
			{
				$data = @fread($fp, 400);
				if (
					($file != 'settings' AND preg_match('#vbversion="(.*?)"#', $data, $matches))
						OR
					($file == 'settings' AND preg_match('#<setting varname="templateversion".*>(.*)</setting>#sU', $data, $matches) AND preg_match('#<defaultvalue>(.*?)</defaultvalue>#', $matches[1], $matches))
				)
				{
					$this->xml_versions[$file] = $matches[1];
				}
				else
				{
					$this->xml_versions[$file] =  $this->phrase['core']['unknown'];
				}
				fclose($fp);
			}
			else
			{
				$this->xml_versions[$file] = $this->phrase['core']['file_not_found'];
			}
		}
	}

	/**
	 *	Set up involving the broader vB system
	 */
	//this assumes that we haven't cratered on bootstrap and can access more
	//of the system to do our init
	private function setup_environment()
	{
		if ($this->setuptype == 'upgrade')
		{
			$db = vB::getDbAssertor();

			// for is_newer_version()
			require_once(DIR . '/includes/adminfunctions.php');

			// if it's an upgrade, use the previous default language's charset instead of the hard coded default value in the
			// upgrade_language_{languagecode}.xml file

			//we only want to warn if this is a site that exists prior
			//to 5.2.5.  People who installed on 5.2.5 or newer can have the
			//same charset mismatch, but *should not make the suggested change*
			//since they will not have run their site in the same broken configuration
			//(5.2.5 attempts to fix that problem but will break sites that were
			//already in that circumstance).
			$current = $this->registry->options['templateversion'];
			if (is_newer_version('5.2.5 Alpha 1', $current))
			{
				//if they've explicitly set the character set
				$legacydb = $db->getDBConnection();
				if (!$legacydb->hasConfigCharset())
				{
					$client_charset = $legacydb->getInitialClientCharset();
					$row = $db->getRow('vBInstall:getDatabaseCharacterSet');
					if (strcasecmp($row['db_charset'], $client_charset) != 0)
					{
						$this->startup_warnings[] = sprintf($this->phrase['core']['database_charset_mismatch'],
							$row['db_charset'], $client_charset);
					}
				}
			}

			$row = $db->getRow('setting', ['varname' => 'languageid']);
			if ($row AND isset($row['value']))
			{
				$charset = $db->getColumn('language', 'charset', ['languageid' => $row['value']]);
				if (is_array($charset))
				{
					$charset = $charset[0];
				}
				vB_Template_Runtime::addStyleVar('charset', $charset);
			}

			//if this is a site older than 5.0 and the db contains 5.0 tables then show a warning.
			if (is_newer_version('5.0.0 Alpha 1', $current))
			{
				//we will use the node and tables as a proxy for "has vB5 tables" and show the warning if we have
				//either.  The node table is iconic, the page table is the first one we create.
				if ($this->tableExists('node') OR $this->tableExists('page'))
				{
					$this->startup_warnings[] = construct_phrase($this->phrase['core']['vb5tables_exist'], $current);
				}
			}
		}
	}

	public function loadDSSettingsfromConfig()
	{
		return true;
	}
}

abstract class vB_Upgrade_Version
{
	use vB_Trait_Upgrade_Utilities;

	/*Constants=====================================================================*/
	const MYSQL_ERROR_CANT_CREATE_TABLE       = 1005;
	const MYSQL_ERROR_TABLE_EXISTS            = 1050;
	const MYSQL_ERROR_COLUMN_EXISTS           = 1060;
	const MYSQL_ERROR_KEY_EXISTS              = 1061;
	const MYSQL_ERROR_UNIQUE_CONSTRAINT       = 1062;
	const MYSQL_ERROR_PRIMARY_KEY_EXISTS      = 1068;
	const MYSQL_ERROR_DROP_KEY_COLUMN_MISSING = 1091;
	const MYSQL_ERROR_TABLE_MISSING           = 1146;
	const FIELD_DEFAULTS                      = '__use_default__';
	const PHP_TRIGGER_ERROR                   = 1;
	const MYSQL_HALT                          = 2;
	const MYSQL_ERROR                         = 3;
	const APP_CREATE_TABLE_EXISTS             = 4;
	const CLI_CONF_USER_DATA_MISSING          = 5;


	//these are old bitfield numbers that were removed from the "live" bitfield
	//lookup arrays.  But we still need them for old upgrade steps or things break.
	//Since they won't change (and if they do the upgrader needs to reference the old
	//values anyway) we can store them here.
	//
	//Previously we were hardcoding them in the local functions, but this centralizes things.
	protected static $legacy_bf = [
		'genericpermissions' => [
			'canprofilepic' => 128,
			'canseeprofilepic' => 4096,
		],

		'forumpermissions' => [
			'canreplyothers' => 64,
			'followforummoderation' => 131072,
		],

		'moderatorpermissions2' => [
			'canmoderatepicturecomments' => 4096,
			'candeletepicturecomments' => 8192,
			'canremovepicturecomments' => 16384,
			'caneditpicturecomments' => 32768,
		],
	];

	/*Properties====================================================================*/
	/**
	* Number of substeps in this step
	*
	* @var int
	*/
	public $stepcount = 0;

	/**
	* The vBulletin registry object
	*
	* @var	vB_Registry
	*/
	protected $registry = null;

	/**
	* The object that will be used to execute queries
	*
	* @var	vB_Database
	*/
	protected $db = null;

	/**
	* A list of modifications to be made when execute is called.
	*
	* @var	array
	*/
	protected $modifications = [];

	/**
	* List of various messages to send back to the delegate class
	*
	* @var	array
	*/
	protected $response = [];

	/**
	* A cache of table alter objects, to reduce the amount of overhead
	* when there are multiple alters to a single table.
	*
	* @var	array
	*/
	public $alter_cache = [];

	/**
	*	Do we support innodb?
	*
	* @var string
	*/
	protected $hightrafficengine = '';

	protected $phrase = [];

	/**
	* Identifier of library that called this script - cli and ajax at present
	*
	* @var 	string
	*/
	public $caller = '';
	public $library;

	/**
	 * Set to true if step queries are to be $perpage limited, yes for Ajax, no for CLI
	 * only affects some *very* old steps.  Copied from the main upgrade class for each step
	 *
	 * @var 	boolean
	 */
	public $limitqueries = true;

	/**
	 * Identifier of max upgrade version for library scripts
	 * @var	string
	 */
	public $maxversion = '';

	/**
 	 * The short version of the script
	 * @var	string
	 */
	public $SHORT_VERSION = null;

	/**
	 * The long version of the script
	 * @var	string
	 */
	public $LONG_VERSION  = null;

	/**
	 * Versions that can upgrade to this script
	 * @var	string
	 */
	public $PREV_VERSION = null;

	/**
	* Beginning version compatibility
	*
	* @var	string
	*/
	public $VERSION_COMPAT_STARTS = '';

	/**
	* Ending version compatibility
	*
	* @var	string
	*/
	public $VERSION_COMPAT_ENDS = '';

	protected bool $showWarnings = false;

	/**
	* Constructor.
	*
	* @param	vB_Registry	Reference to registry object
	* @param	Array		Phrases
	* @param	string	Max upgrade version
	*/
	public function __construct(&$registry, $phrase, $version, $versions, $showWarnings)
	{
		if (is_object($registry))
		{
			$this->registry = $registry;
			$this->db = $this->registry->db;
		}
		else
		{
			throw new Exception('vB_Upgrade: $this->registry is not an object.');
		}

		$this->phrase =& $phrase;
		$this->setVersions($version, $versions);

		foreach (get_class_methods($this) AS $method_name)
		{
			if (preg_match('#^step_(\d+)$#', $method_name, $matches))
			{
				$this->stepcount++;
			}
		}

		// Maintain backwards compatibility with install system
		require_once(DIR . '/install/functions_installupgrade.php');

		//it turns out that we load this class even if we fail
		//verify_environment.  We really need to untangle that because
		//all kinds of things could be problematic if we have errors
		//but we need to get though this in order to display to the user
		//for now will just skip setting this if we fail to load
		//the engine for it.
		try
		{
			$this->hightrafficengine = get_innodb_engine($this->db);
		}
		catch(Throwable $e)
		{
			//nothing to do here.
		}

		require_once(DIR . '/includes/class_dbalter.php');

		$this->showWarnings = $showWarnings;
	}

	/**
	 * Sets the class version information based on the creation info.
	 *
	 * @param string $version -- the current short version
	 * @param array $versions -- the calculated versions array
	 */
	private function setVersions($version, $versions)
	{
		$this->maxversion = end($versions);

		$this->SHORT_VERSION ??= $version;

		if (!isset($this->LONG_VERSION))
		{
			if (!$versions[$version])
			{
				throw new Exception('No long version found for version ' . $version);
			}

			$this->LONG_VERSION = $versions[$version];
		}

		//PREV_VERSION is used to check if we loaded the right script in sequence however we should get that
		//checking out of the upgrade classes to the extent we really need it.  It's not really relevant if
		//the "COMPAT" verisons are set or the script is an end script.
		if (!isset($this->PREV_VERSION))
		{
			$previous = false;
			foreach ($versions AS $short => $long)
			{
				if ($short == $version)
				{
					break;
				}

				$previous = $long;
			}

			if (!$previous)
			{
				//this literally should never happen in production so don't want to
				//go through the effort of phrasing it it.
				throw new Exception('No previous version found for version ' . $version);
			}

			$this->PREV_VERSION = $previous;
		}
	}

	//This is a hack.  We have some chicken and egg situations where we need some information from settings
	//or other XML data (or simply need the setting to exist so we can potentially set a new setting based
	//on the environment instead of a hard coded default).  And we don't load that stuff until the final
	//upgrade step.  We we have upgrade steps that run the final upgrade steps early in some instances to
	//work around this.  This is an attempt to sweep some of the details under the rug to avoid problems
	//if things change.
	private function run_final_step($method, $data = [])
	{
		$finalUpgrader = $this->library->load_final();
		$result = $finalUpgrader->$method($data);
		$this->copy_messages_from_substep($finalUpgrader);
		return $result;
	}

	//When we create a step and call the functions on it we don't pick up the messages. We need to copy
	//them explicity to get them to show up on the client.
	private function copy_messages_from_substep($substep)
	{
		foreach ($substep->modifications AS $modification)
		{
			if ($modification['modification_type'] == 'show_message')
			{
				$this->show_message($modification['message'], $modification['replace']);
			}
		}
	}

	//Hide the details of which step is which so if it changes we can avoid hitting all of the old
	//steps.  Also make sure that we handling all of the sesssion/cache clear logic consistently
	//across the places we call these functions.
	//
	//We probably shouldn't be creating the sessions here.  It appears to mostly duplicate calls that
	//exist in the steps themselves and either the steps need them or they don't.  It shouldn't depend
	//on how they are called.
	//
	//We should be doing the vB_Library::clearCache/vB_Api::clearCache calls either here or in the
	//final class functions instead of the individual upgrade class steps.  However not only is the
	//usage inconsistent for different places the different functions are called, changing it make
	//it consistent caused weird errors that need to be investigated.
	protected function final_load_settings()
	{
		vB_Upgrade::createAdminSession();
		$this->run_final_step('step_1');
	}

	protected function final_load_widgets()
	{
		$this->run_final_step('step_4');
	}

	protected function final_load_screenlayouts()
	{
		vB_Upgrade::createAdminSession();
		$this->run_final_step('step_5');
	}

	protected function final_load_pagetemplates()
	{
		vB_Upgrade::createAdminSession();
		$this->run_final_step('step_6');
	}

	protected function final_load_pages()
	{
		vB_Upgrade::createAdminSession();
		$this->run_final_step('step_7');
	}

	protected function final_load_channels()
	{
		vB_Upgrade::createAdminSession();
		$this->run_final_step('step_8');
	}

	protected function final_load_routes()
	{
		vB_Upgrade::createAdminSession();
		$this->run_final_step('step_9');
	}

	protected function final_configure_channelwidgetinstance()
	{
		vB_Upgrade::createAdminSession();
		$this->run_final_step('step_10');
	}

	protected function final_create_channelroutes()
	{
		vB_Upgrade::createAdminSession();
		$this->run_final_step('step_11');
	}

	protected function final_add_noderoutes()
	{
		vB_Upgrade::createAdminSession();
		$this->run_final_step('step_12');
	}

	protected function final_add_notificationdefaults()
	{
		vB_Library::clearCache();
		vB_Upgrade::createAdminSession();
		$this->run_final_step('addNotificationDefaultData');
	}

	//this is it's own special snowflake in many ways.  It's the only one that takes and
	//returns data and it has special message handling.  We should probably bake data into
	//the standard method caller function and change the final method use show_message
	//now that that works as expected.  But for now leave as a special snowflake.
	protected function final_load_themes($data)
	{
		vB_Library::clearCache();
		vB_Upgrade::createAdminSession();

		$finalUpgrader = $this->library->load_final();
		$result = $finalUpgrader->importThemes($data);

		//This works around the message issue in a different way by trapping them and passing them
		//back.  It's annoying to have it be different but not worth trying to fix at the moment.
		if (!empty($result['messages']))
		{
			foreach ($result['messages'] AS $msg)
			{
				$this->show_message($msg);
			}
		}

		if (isset($result['startat']))
		{
			return ['startat' => $result['startat']];
		}

		return;
	}

	protected function file_load_legacy_style($data)
	{
		return $this->run_final_step('importLegacyStyle', $data);
	}

	/**
	* Tests to see if the specified field exists in a table.
	*
	* @param	string	Table to test. Do not include table prefix!
	* @param	string	Name of field to test
	*
	* @return	boolean	True if field exists, false if it doesn't
	*/
	protected function field_exists($table, $field)
	{
		$error_state = $this->db->reporterror;
		if ($error_state)
		{
			$this->db->hide_errors();
		}

		$this->db->query_write("SELECT $field FROM " . TABLE_PREFIX . "$table LIMIT 1");

		if ($error_state)
		{
			$this->db->show_errors();
		}

		if ($this->db->errno())
		{
			$this->db->errno = 0;
			return false;
		}
		else
		{
			return true;
		}
	}

	/**
	* Alters an existing field in a table.
	*
	* @param	string	Message to display
	* @param	string	Name of the table to alter. Do not include table prefix!
	* @param	string	Name of the field to add
	* @param	array	Extra attributes. Supports: length, attributes, null, default, extra. You may also use the define FIELD_DEFAULTS.
	*/
	protected function alter_field($message, $table, $field, $type, $extra)
	{
		$extra = $this->getFieldDefaults($type, $extra);
		$this->modifications[] = [
			'modification_type' => 'alter_field',
			'alter'             => true,
			'message'           => $message,
			'data'              => [
				'table'      => $table,
				'name'       => $field,
				'type'       => $type,
				'length'     => $extra['length'],
				'attributes' => $extra['attributes'],
				'null'       => (!empty($extra['null']) ? true : false),
				'default'    => $extra['default'],
				'extra'      => $extra['extra'],
				'ignorable_errors' => [],
			]
		];
	}

	/**
	 * Cover for alter_field to standardize messaging.
	 */
	protected function alter_field2($table, $field, $type, $extra, $count = 1, $of = 1)
	{
		$this->alter_field(
			sprintf($this->phrase['core']['altering_x_table'], TABLE_PREFIX . $table, $count, $of),
			$table,
			$field,
			$type,
			$extra
		);
	}

	/**
	* Adds a field to a table.
	*
	* @param	string	Message to display
	* @param	string	Name of the table to alter. Do not include table prefix!
	* @param	string	Name of the field to add
	* @param	array	Extra attributes. Supports: length, attributes, null, default, extra. You may also use the define FIELD_DEFAULTS.
	*/
	protected function add_field($message, $table, $field, $type, $extra)
	{
		$extra = $this->getFieldDefaults($type, $extra);
		$this->modifications[] = [
			'modification_type' => 'add_field',
			'alter'             => true,
			'message'           => $message,
			'data'              => [
				'table'      => $table,
				'name'       => $field,
				'type'       => $type,
				'length'     => $extra['length'],
				'attributes' => $extra['attributes'],
				'null'       => boolval($extra['null']),
				'default'    => $extra['default'],
				'extra'      => $extra['extra'],
				'ignorable_errors' => [self::MYSQL_ERROR_COLUMN_EXISTS],
			]
		];
	}

	protected function add_field2($table, $field, $type, $extra, $count = 1, $of = 1)
	{
		$message = sprintf($this->phrase['core']['altering_x_table'], $table, $count, $of);
		$this->add_field($message, $table, $field, $type, $extra);
	}

	private function getFieldDefaults($type, $extra)
	{
		//set some defaults to ensure that we have everything we rely on defined upstream.
		//even if the user passed a custom "extra" array and we skip the type level defaults.
		$defaults = [
			'attributes' => '',
			'extra'      => '',
			'null'			 => true,
			'length'		 => null,
			'default'    => null,
		];

		if ($extra == self::FIELD_DEFAULTS OR (isset($extra['attributes']) AND $extra['attributes'] == self::FIELD_DEFAULTS))
		{
			switch (strtolower($type))
			{
				case 'float':
				case 'double':
				{
					$typedefaults = [
						'null'       => false,
						'default'    => 0,
					];
				}
				break;

				case 'tinyint':
				case 'smallint':
				case 'mediumint':
				case 'int':
				case 'bigint':
				{
					$typedefaults = [
						'attributes' => 'UNSIGNED',
						'null'       => false,
						'default'    => 0,
					];
				}
				break;

				case 'char':
				case 'varchar':
				case 'binary':
				case 'varbinary':
				{
					if ($extra == self::FIELD_DEFAULTS)
					{
						$this->add_error("You must specify a length for fields of type $type to use the defaults.", self::PHP_TRIGGER_ERROR, true);
						return $this->response;
					}

					$typedefaults = [
						'length'     => $extra['length'],
						'null'       => false,
						'default'    => '',
					];
				}
				break;

				case 'tinytext':
				case 'text':
				case 'mediumtext':
				case 'longtext':
				case 'tinyblob':
				case 'blob':
				case 'mediumblob':
				case 'longblob':
				{
					$typedefaults = [
						'attributes' => '',
						'null'       => true,
					];
				}
				break;

				default:
				{
					$this->add_error("No defaults specified for fields of type $type.", self::PHP_TRIGGER_ERROR, true);
					return $this->response;
				}
			}

			$defaults = array_merge($defaults, $typedefaults);

			//clean up the special use defaults flag however it was provided.
			if (is_array($extra))
			{
				unset($extra['attributes']);
			}
			else
			{
				$extra = [];
			}
		}

		$extra = array_merge($defaults, $extra);

		//floats allow a length of float(m,d) where m is total digits and d is digits after the decimal
		//this is deprecated, appears to be used by one field and I'm not clear on why.  But if we try to
		//convert passed float lengths to an int things will break (any empty value will be ignored)
		if (!in_array(strtolower($type), ['float', 'double']))
		{
			$extra['length'] = intval($extra['length']);
		}

		return $extra;
	}

	/**
	* Drops a field from a table.
	*
	* @param	string	Message to display
	* @param	string	Table to drop from. Do not include table prefix!
	* @param	string	Field to drop
	*/
	protected function drop_field($message, $table, $field)
	{
		$this->modifications[] = [
			'modification_type' => 'drop_field',
			'alter'             => true,
			'message'           => $message,
			'data'              => [
				'table' => $table,
				'name'  => $field,
				'ignorable_errors' => [self::MYSQL_ERROR_DROP_KEY_COLUMN_MISSING],
			]
		];
	}

	/**
	 * Replacement for drop_field that standardizes the messaging
	 */
	protected function drop_field2($table, $field, $count = 1, $of = 1)
	{
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], TABLE_PREFIX . $table, $count, $of),
			$table,
			$field
		);
	}

	/**
	 *	Drops a table
	 *
	 *	@param $table -- name of the table without the table prefix.
	 */
	protected function drop_table($table)
	{
		//let's hide this both to reduce repetative code, but also so that
		//we can change it it without altering tons of additional code
		$this->run_query(
			sprintf($this->phrase['core']['dropping_old_table_x'], TABLE_PREFIX . $table),
			"DROP TABLE IF EXISTS " . TABLE_PREFIX . $table
		);
	}

	/**
	* Adds an index to a table. Can span multiple fields.
	*
	* @param	string			Message to display
	* @param	string			Table to add the index to. Do not include table prefix!
	* @param	string			Name of the index
	* @param	string|array	Fields to cover. Must be an array if more than one
	* @param	string			Type of index (empty defaults to a normal/no constraint index)
	* @param  boolean 		overwrite.  If true drop the index before we create the new one
	*/
	protected function add_index($message, $table, $index_name, $fields, $type = '', $overwrite = false, $ignorable_errors = null)
	{
		$this->modifications[] = [
			'modification_type' => 'add_index',
			'alter'             => true,
			'message'           => $message,
			'data'              => [
				'table'  => $table,
				'name'   => $index_name,
				'fields' => (!is_array($fields) ? [$fields] : $fields),
				'type'   => $type,
				'ignorable_errors' => $ignorable_errors ?? [self::MYSQL_ERROR_KEY_EXISTS],
				'overwrite' => $overwrite
			]
		];
	}

	//Some cover functions for simple cases.  Doesn't handle all of the params of the original.
	protected function add_index2($table, $index_name, $fields, $count = 1, $of = 1)
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], TABLE_PREFIX . $table, $count, $of),
			$table,
			$index_name,
			$fields
		);
	}

	protected function add_unique_index($table, $index_name, $fields, $count = 1, $of = 1)
	{
		//Might want to pass [self::MYSQL_ERROR_KEY_EXISTS, self::MYSQL_ERROR_UNIQUE_CONSTRAINT] as the
		//ignorable errors in all cases for unique indexes.  But it's not entirely clear how best to go about
		//that.  Adding unique indexes after the fact is always dicey.
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], TABLE_PREFIX . $table, $count, $of),
			$table,
			$index_name,
			$fields,
			'unique'
		);
	}

	protected function rename_templates(array $templates) : void
	{
		$index = 1;
		foreach ($templates AS $oldTitle => $newTitle)
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['update_table_x'], 'template', $index, count($templates)),
				"UPDATE " . TABLE_PREFIX . "template
				SET title = '" . $this->db->escape_string($newTitle) . "'
				WHERE title = '" . $this->db->escape_string($oldTitle) . "'
				"
			);
			++$index;
		}

		$index = 1;
		foreach ($templates AS $oldTitle => $newTitle)
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['update_table_x'], 'templatehistory', $index, count($templates)),
				"UPDATE " . TABLE_PREFIX . "templatehistory
				SET title = '" . $this->db->escape_string($newTitle) . "'
				WHERE title = '" . $this->db->escape_string($oldTitle) . "'
				"
			);
			++$index;
		}
	}

	protected function add_cronjob($data)
	{
		if (!$this->db->query_first("SELECT filename FROM " . TABLE_PREFIX . "cron WHERE filename = '" . $this->db->escape_string($data['filename']) . "'"))
		{
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'cron', 1, 1),
				"INSERT INTO " . TABLE_PREFIX . "cron
					(nextrun, weekday, day, hour, minute, filename, loglevel, varname, volatile, product)
				VALUES
				(
					" . intval($data['nextrun']) . ",
					'" . intval($data['weekday']) . "',
					'" . intval($data['day']) ."',
					'" . intval($data['hour']) . "',
					'" . $this->db->escape_string($data['minute']) . "',
					'" . $this->db->escape_string($data['filename']) . "',
					'" . intval($data['loglevel']) . "',
					'" . $this->db->escape_string($data['varname']) . "',
					" . intval($data['volatile']) . ",
					'" . $this->db->escape_string($data['product']) . "'
				)"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	* Adds an adminmessage to the system. Checks if message already exists.
	*
	* @param	string			varname of message (unique)
	* @param	array				Adminmessage schema (dismissable, script, action, execurl, method, status)
	* @param	bool				Allow duplicate entry on varname?
	* @param	array				Values to send into the phrase at run time
	*/
	protected function add_adminmessage($varname, $data, $duplicate = false, $args = null)
	{
		$db = vB::getDbAssertor();

		if (!$duplicate)
		{
			$exists = $db->getRow('adminmessage', ['varname' => $varname, 'status' => 'undone']);
			if ($exists)
			{
				$this->skip_message();
				return;
			}
		}

		// This function takes "dismissible", but column name is "dismissable". This caused some confusion where quite a number of
		// callers passed in the latter. Accept the latter if former isn't set.
		if (!isset($data['dismissible']) && isset($data['dismissable']))
		{
			$data['dismissible'] = intval($data['dismissable']);
		}

		$this->run_query(
			sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . "adminmessage"),
			"INSERT INTO " . TABLE_PREFIX . "adminmessage
				(varname, dismissable, script, action, execurl, method, dateline, status, args)
			VALUES
				(
					'" . $this->db->escape_string($varname) . "',
					" . intval($data['dismissible']) . ",
					'" . $this->db->escape_string($data['script'] ?? '') . "',
					'" . $this->db->escape_string($data['action'] ?? '') . "',
					'" . $this->db->escape_string($data['execurl'] ?? '') . "',
					'" . $this->db->escape_string($data['method'] ?? '') . "',
					" . TIMENOW . ",
					'" . $this->db->escape_string($data['status']) . "',
					'" . ($args ? $this->db->escape_string(@serialize($args)) : '') . "'
			)");
	}

	/**
	 * Adds a new contenttype
	 *
	 * @param	string	Productid (vbulletin, vbcms, vbblog, etc)
	 * @param	string	Package Class (vBForum, vBBlog, vBCms, etc)
	 * @param	string	Contenttype (Post, Thread, Forum, etc)
	 * @param	int		Can Place?
	 * @param	int		Can Search
	 * @param	int		Can Tag
	 * @param	int		Can Attach
	 * @param	int		Is aggregator
	 */
	protected function add_contenttype($productid, $package_class, $contenttype_class, $canplace = 0, $cansearch = 0, $cantag = 0, $canattach = 0, $isaggregator = 0)
	{
		$packageinfo = $this->db->query_first("
			SELECT packageid
			FROM " . TABLE_PREFIX . "package
			WHERE
				productid = '" . $this->db->escape_string($productid) . "'
					AND
				class = '" . $this->db->escape_string($package_class) . "'
		");
		if ($packageinfo)
		{
			$contenttypeinfo = $this->db->query_first("
				SELECT contenttypeid
				FROM " . TABLE_PREFIX . "contenttype
				WHERE
					packageid = {$packageinfo['packageid']}
						AND
					class = '" . $this->db->escape_string($contenttype_class) . "'
			");
			if (!$contenttypeinfo)
			{
				$this->run_query(
				sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . "contenttype"),
				"INSERT IGNORE INTO " . TABLE_PREFIX . "contenttype
						(class, packageid, canplace, cansearch, cantag, canattach, isaggregator)
					VALUES
						(	'" . $this->db->escape_string($contenttype_class) . "',
							{$packageinfo['packageid']},
							'{$canplace}',
							'{$cansearch}',
							'{$cantag}',
							'{$canattach}',
							'{$isaggregator}'
						)
					"
				);

				return true;
			}
		}
		$this->skip_message();
	}
	/**
	* Drops an index from a table.
	*
	* @param	string	Message to display
	* @param	string	Table to drop the index from. Do not include table prefix!
	* @param	string	Name of the index to remove
	*/
	protected function drop_index($message, $table, $index_name)
	{
		$this->modifications[] = [
			'modification_type' => 'drop_index',
			'alter'             => true,
			'message'           => $message,
			'data'              => [
				'table' => $table,
				'name'  => $index_name,
				'ignorable_errors' => [self::MYSQL_ERROR_DROP_KEY_COLUMN_MISSING],
			]
		];
	}

	protected function drop_index2($table, $index_name, $count = 1, $of = 1)
	{
		$this->drop_index(
			sprintf($this->phrase['core']['altering_x_table'], TABLE_PREFIX . $table, $count, $of),
			$table,
			$index_name
		);
	}

	protected function remove_datastore_entry($key)
	{
		vB::getDbAssertor()->delete('datastore', ['title' => $key]);
		$this->show_message(sprintf($this->phrase['core']['remove_x_datastore'], $key));
	}

	/**
	* Executes the specified step
	*
	* @param	int			Step to execute
	* @param	boolen	Check if table exists for create table commands
	* @param	array		Data to send to step (startat, prompt results, etc)
	*
	* @return	mixed	Return array upon error
	*/
	public function execute_step($step, $check_table = true, $data = null)
	{
		$this->response = [
			'error' => [],
			'message' => [],
		];

		$stepname = "step_$step";


		$result = $this->$stepname($data);

		return $this->execute($check_table, $result);
	}

	/**
	* Executes the specified modifications.
	*
	* @param	boolen	Check if table exists for create table commands
	* @param	array		return value from step execution
	*
	* @return	mixed	Return array upon error
	*/
	public function execute($check_table = true, $result = null)
	{
		$this->response['returnvalue'] = $result;

		if ($check_table AND !$this->check_table_conflict())
		{
			$this->add_message($this->phrase['core']['table_conflict']);
			$this->modifications = [];
			return $this->response;
		}

		foreach ($this->modifications AS $modification)
		{
			$this->add_message($modification['message'], 'STANDARD', $modification['replace'] ?? false);
			$data =& $modification['data'];

			if (!empty($modification['alter']))
			{
				$db_alter =& $this->setup_db_alter_class($data['table']);
			}
			else
			{
				unset($db_alter);
			}

			$alter_result = null;

			switch ($modification['modification_type'])
			{
				case 'add_field':
					$alter_result = $db_alter->add_field($data);
					$alter_result = $this->ignore_errors($alter_result, $this->db->errno(), $db_alter->error_no,
						$data['ignorable_errors'], [ERRDB_FIELD_EXISTS]);
					break;

				case 'alter_field':
					$alter_result = $db_alter->alter_field($data);
					$alter_result = $this->ignore_errors($alter_result, $this->db->errno(), $db_alter->error_no,
						$data['ignorable_errors'], []);
					break;

				case 'drop_field':
					$alter_result = $db_alter->drop_field($data['name']);
					$alter_result = $this->ignore_errors($alter_result, $this->db->errno(), $db_alter->error_no,
						$data['ignorable_errors'], [ERRDB_FIELD_DOES_NOT_EXIST]);
					break;

				case 'add_index':
					$alter_result = $db_alter->add_index($data['name'], $data['fields'], $data['type'], $data['overwrite']);

					$alter_ignorable_errors = [];
					if (in_array(self::MYSQL_ERROR_UNIQUE_CONSTRAINT, $data['ignorable_errors']))
					{
						// Duplicate key errors will cause vB_Database_Alter_MySQL::add_index() to set this.
						// I think this *should* be OK since ignore_errors() checks for both with an AND
						$alter_ignorable_errors[] = ERRDB_MYSQL;
					}
					//note that we do not ignore ERRDB_FIELD_EXISTS here deliberately.  This is only set if the
					//index exists and does not match the one being added.
					$alter_result = $this->ignore_errors($alter_result, $this->db->errno(), $db_alter->error_no,
						$data['ignorable_errors'], $alter_ignorable_errors);
					break;

				case 'drop_index':
					$alter_result = $db_alter->drop_index($data['name']);
					$alter_result = $this->ignore_errors($alter_result, $this->db->errno(), $db_alter->error_no,
						$data['ignorable_errors'], [ERRDB_FIELD_DOES_NOT_EXIST]);
					break;

				case 'run_query':
					$error_state = $this->db->reporterror;
					if ($error_state)
					{
						$this->db->hide_errors();
					}

					$query_result = $this->db->query_write("### vBulletin Database Alter ###\r\n" . $data['query']);

					if ($errno = $this->db->errno())
					{
						if (!in_array($errno, $data['ignorable_errors']))
						{
							if ($errno == self::MYSQL_ERROR_CANT_CREATE_TABLE)
							{
								if (stripos($this->db->error, 'errno: 121') !== false AND stripos($data['query'], 'engine=innodb'))
								{
									preg_match('#CREATE TABLE ([a-z0-9_]+)#si', $data['query'], $matches);
									$this->add_error(sprintf($this->phrase['core']['table_creation_x_failed'], $matches[1]), self::PHP_TRIGGER_ERROR, true);
									$this->modifications = [];
									return $this->response;
								}
							}

							$this->add_error(
								[
									'message' => $data['query'],
									'error'   => $this->db->error(),
									'errno'   => $this->db->errno()
								],
								self::MYSQL_HALT,
								true
							);

							$this->modifications = [];
							return $this->response;
						}
						else
						{
							// error occurred, but was ignorable
							$this->db->errno = 0;
						}
					}

					if ($this->showWarnings)
					{
						$warning = $this->db->warning();
						if ($warning)
						{
							$this->add_message('WARNING:' . $warning);
						}

						if ($error_state)
						{
							$this->db->show_errors();
						}
					}

					break;

				case 'show_message':
					// do nothing -- just show the message
					break;

				case 'debug_break':
				//	echo "</ul><div>Debug break point. Stopping execution.</div>";
				//	exit;

				default:
					$this->add_error(sprintf($this->phrase['core']['invalid_modification_type_x'], $modification['modification_type']), self::PHP_TRIGGER_ERROR, true);
					$this->modifications = [];
					return $this->response;
			}

			if ($alter_result === false)
			{
				if ($db_alter->error_no == ERRDB_MYSQL)
				{
					$this->db->show_errors();
					$this->db->sql = $db_alter->sql;
					$this->db->connection_recent = null;
					$this->db->error = $db_alter->error_desc;
					$this->db->errno = -1;

					$this->add_error([
						'message' => $this->db->sql,
						'error'   => $this->db->error,
						'errno'   => $this->db->errno
						], self::MYSQL_HALT, true);

					$this->modifications = [];
					return $this->response;
				}
				else
				{
					if (ob_start())
					{
						print_r($modification);
						$results = ob_get_contents();
						ob_end_clean();
					}
					else
					{
						$results = serialize($modification);
					}

					$this->add_error([
						'message' => $results,
						'error'   => $db_alter->error_desc,
						'errno'   => $db_alter->error_no
						], self::MYSQL_HALT, true);
					$this->modifications = [];
					return $this->response;
				}
			}
		}
		$this->modifications = [];

		return $this->response;
	}

	/**
	 * Checks if given error needs to be ignored.
	 * @param $alter_result
	 * @param $db_errno
	 * @param $alter_errno
	 * @param $db_allowed_errors
	 * @param $alter_allowed_errors
	 *
	 * @return bool
	 */
	protected function ignore_errors($alter_result, $db_errno, $alter_errno, $db_allowed_errors, $alter_allowed_errors)
	{
		if (!$alter_result)
		{
			//if we don't have some kind of error here then don't attempt to clear the flags, it
			//must be some other reason we failed.
			if ($db_errno OR $alter_errno)
			{

				//either we don't have an error or we have an error value we can skip.
				$is_db_valid = (!$db_errno OR in_array($db_errno, $db_allowed_errors));
				$is_alter_valid = (!$alter_errno OR in_array($alter_errno, $alter_allowed_errors));

				if ($is_db_valid AND $is_alter_valid)
				{
					return true;
				}
			}
		}

		return $alter_result;
	}

	/**
	* Runs an arbitrary query. An error will stop execution unless
	* the error code is listed as ignored
	*
	* @param	string	Message to display
	* @param	string	Query to execute.
	* @param	array	List of error codes that should be ignored.
	*/
	protected function run_query($message, $query, $ignorable_errors = [])
	{
		$this->modifications[] = [
			'modification_type' => 'run_query',
			'message'           => $message,
			'data'              => [
				'query'            => $query,
				'ignorable_errors' => (!is_array($ignorable_errors) ? [$ignorable_errors] : $ignorable_errors)
			]
		];
	}

	/**
	* Shortcut for adding the "long next step" message
	*
	*/
	public function long_next_step()
	{
		$this->show_message($this->phrase['core']['next_step_long_time']);
	}

	/**
	* Shortcut for adding the "skipping step" message
	*
	*/
	public function skip_message()
	{
		$this->show_message($this->phrase['core']['skipping_not_needed']);
	}

	/**
	* Does nothing but shows a message.
	*
	* @param	string	Message to display
	* @param	boolean	Replace the previous message with this message, if the previous message also had $replace set
	*/
	public function show_message($message, $replace = false)
	{
		$this->modifications[] = [
			'modification_type' => 'show_message',
			'message'           => $message,
			'data'              => [],
			'replace'           => $replace,
		];
	}

	/**
	* This is a function useful for debugging. It will stop execution of the
	* modifications when this call is reached, allowing emulation of an upgrade
	* step that failed at a specific point.
	*/
	protected function debug_break()
	{
		$this->modifications[] = [
			'modification_type' => 'debug_break',
			'message'           => '',
			'data'              => []
		];
	}

	/**
	* Sets up a DB alter object for a table. Only called internally.
	*
	* @param	string	Table the object should be instantiated for
	*
	* @return	object	Instantiated alter object
	*/
	private function &setup_db_alter_class($table)
	{
		if (isset($this->alter_cache["$table"]))
		{
			return $this->alter_cache["$table"];
		}
		else
		{
			$this->alter_cache["$table"] = new vB_Database_Alter_MySQL($this->db);
			$this->alter_cache["$table"]->fetch_table_info($table);
			return $this->alter_cache["$table"];
		}
	}

	/**
	 * Retrieve schema about a table
	 * @param string $table Table Name
	 */
	protected function fetch_table_info($table)
	{
		$db_alter = $this->setup_db_alter_class($table);
		return $db_alter->table_field_data;
	}

	/**
	* Checks if a create table call will conflict with an existing table of the same name
	*
	* @return	array	Data about the success of the check, 'error' will be empty if the query is ok
	*/
	protected function check_table_conflict()
	{
		$error = false;
		foreach ($this->modifications AS $modification)
		{
			if (
				$modification['modification_type'] == 'run_query'
					AND
				preg_match('#^\s*create\s+table\s+' . TABLE_PREFIX . '([a-z0-9_\-]+)\s+\((.*)\)#si', $modification['data']['query'], $matches)
			)
			{
				$db_alter = $this->setup_db_alter_class($matches[1]);
				if ($this->alter_cache["$matches[1]"]->init)
				{
					$existingtable = array_keys($db_alter->table_field_data);
					$create = preg_split("#,\s*(\r|\t)#si", $matches[2], -1, PREG_SPLIT_NO_EMPTY);
					$newtable = [];

					foreach ($create AS $field)
					{
						$field = trim($field);
						if (preg_match('#^\s*(((fulltext|primary|unique)\s*)?key\s+|index\s+|engine\s*=)#si', $field))
						{
							continue;
						}
						if (preg_match('#^(`?)([a-z0-9_\-]+)(\\1)#si', $field, $matches2))
						{
							$newtable[] = $matches2[2];
						}
					}

					if (array_diff($existingtable, $newtable))
					{
						$this->add_error(TABLE_PREFIX . $matches[1], self::APP_CREATE_TABLE_EXISTS, true);
						$error = true;
					}
				}
			}
		}

		return !$error;
	}

	/**
	* Add an error
	*
	* @param	string	Data of item to be output
	* @param	int			Key of item
	* @param	boolean	This error signals stoppage of the upgrade process if true
	*/
	public function add_error($value = '', $code = '', $fatal = false)
	{
		$this->response['error'][] = [
			'code'  => $code,
			'value' => $value,
			'fatal' => $fatal,
		];
	}

	/**
	* Add a message
	*
	* @param	string	Key of item
	* @param	string	Data of item to be output
	* @param	boolean	Replace previous message with this message, if it had $replace set as well..
	*/
	protected function add_message($value = '', $code = 'STANDARD', $replace = false)
	{
		$this->response['message'][] = [
			'code'    => $code,
			'value'   => $value,
			'replace' => (bool) $replace,
		];
	}


	/**
	 * This sets an option. It's for where we need to change an existing value
	 *
	 *	@param string
	 *	@param string //we actually don't currently use this parameter
	 *	@param string
	 *	@deprecated
	 */
	protected function set_option($varname, $grouptitle, $value)
	{
		include_once DIR . '/includes/adminfunctions_options.php';
		save_settings([$varname => $value]);
	}

	/**
	 * This sets an option. It's for where we need to change an existing value
	 *
	 * This will not rebuild the style.  Either the caller must do that or rely on the rebuild in upgrade final
	 *
	 *	@param string $varname
	 *	@param string $value
	 *	@param int $count -- The index of the options being set (for the message with multiple calls)
	 *	@param int $of -- The total options being set.
	 *	@return void
	 */
	protected function set_option2($varname, $value, $count = 1, $of = 1)
	{
		//We need an admin session to call save_settings.  We should create a version that doesn't to use the API internally.
		//Note that this is also needed for set_option but the callers handle that and there shouldn't be any
		//*new* callers of that function so leaving well enough alone.
		vB_Upgrade::createAdminSession();
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table_x'], TABLE_PREFIX . 'option', $count, $of));

		include_once DIR . '/includes/adminfunctions_options.php';
		save_settings([$varname => $value]);
	}

	protected function rename_option($oldvarname, $newvarname, $count = 1, $of = 1)
	{
		// This deliberate does not handle updating all of the associated data for a setting, particularly the phrases.
		// It is also necesary to update the setting files to reflect the change (and the DB will be out of sync until the
		// setting update runs).  This isn't a problem if the upgrade completes as expected (and rerunning will fix things
		// if it doesn't).
		//
		// This is needed because the setting file does't handle renames and we want to capture the existing value.
		vB_Upgrade::createAdminSession();
		$this->show_message(sprintf($this->phrase['core']['rename_option_x'], $oldvarname, $newvarname, $count, $of));

		$db = vB::getDbAssertor();
		$db->update('setting', ['varname' => $newvarname], ['varname' => $oldvarname]);
	}

	/**
	 * This sets an option. It should rarely used. Its primary use is for temporarily
	 * storing the version number from which this upgrade started. Any other use should be
	 * carefully considered as to why you don't just put in the XML file.
	 *
	 *	@param string
	 *	@param string //we actually don't currently use this parameter
	 *	@param string
	 */
	protected function set_new_option($varname, $grouptitle, $value, $datatype, $default_value = false, $optioncode = '', $product = 'vbulletin', $adminperm = '')
	{
		$row = vB::getDbAssertor()->getRow('setting', ['varname' => $varname]);
		if (!$row)
		{
			$params = [
				'product' => $product,
				'varname' => $varname,
				'grouptitle' => $grouptitle,
				'value' => $value,
				'datatype' => $datatype,
				'optioncode' => $optioncode,
				'adminperm' => $adminperm
			];
			if (!empty($default_value))
			{
				$params['default_value'] = $default_value;
			}
			vB::getDbAssertor()->assertQuery('replaceSetting', $params);
		}
		include_once DIR . '/includes/adminfunctions_options.php';
		$values = [$varname => $value];
		if ($default_value)
		{
			$values[$varname]['default_value'] = $default_value;
		}
		save_settings($values, [$row]);

	}

	/**
	* Log the current location of the upgrade
	*
	* @param	string	Upgrade Step
	* @param	int			Startat value for multi step steps
	* @param	bool		Process only the current version upgrade
	*/
	public function log_upgrade_step($step, $startat = 0, $only = false)
	{
		$complete = ($step == $this->stepcount);
		$perpage = 0;
		$insertstep = true;

		if ($complete)
		{
			$step = 0;
			if ($this->SHORT_VERSION == 'final' OR $only)
			{
				//This needs an index on 'script' added
				$this->db->query_write("
					DELETE FROM " . TABLE_PREFIX . "upgradelog
					WHERE script IN ('final')
				");

				$insertstep = false;
			}
			else
			{
				require_once(DIR . '/includes/adminfunctions_template.php');
				if (is_newer_version($this->LONG_VERSION, $this->registry->options['templateversion']))
				{
					$this->db->query_write("UPDATE " . TABLE_PREFIX . "setting SET value = '" .
						$this->LONG_VERSION . "' WHERE varname = 'templateversion'");
				}
				if (!defined('SKIPDB'))
				{
					vB::getDatastore()->build_options();
				}

				$this->registry->options['templateversion'] = $this->LONG_VERSION;
			}
		}

		if ($insertstep AND !defined('SKIPDB'))
		{
			// use time() not TIMENOW to actually time the script's execution
			/*insert query*/
			$this->db->query_write("
				INSERT INTO " . TABLE_PREFIX . "upgradelog(script, steptitle, step, startat, perpage, dateline, only)
				VALUES (
					'" . $this->db->escape_string($this->SHORT_VERSION) . "',
					'',
					$step,
					$startat,
					$perpage,
					" . time() . ",
					" . intval($only) . "
			)");
		}
	}

	/**
	* Parse exception
	*
	* @param	string	error msg to parse
	*
	* @return	string
	*/
	protected function stop_exception($e)
	{
		$args = $e->getParams();
		vB_Upgrade::createAdminSession();
		$phraseAux = vB_Api::instanceInternal('phrase')->fetch([$args[0]]);
		$message = $phraseAux[$args[0]];

		if (sizeof($args) > 1)
		{
			$args[0] = $message;
			$message = call_user_func_array('construct_phrase', $args);
		}

		return $message;
	}

	protected function getBatchInfo($startat, $process, $total)
	{
		$batchInfo = [];

		$batchInfo['startat'] = $startat;
		$batchInfo['first'] = (($startat > 1)? (($startat - 1) * $process) : 0);
		$batchInfo['more'] = (($batchInfo['first'] < $total) ? true : false);
		$batchInfo['records'] = ((($batchInfo['first'] + $process) < $total) ? ($batchInfo['first'] + $process) : $total);
		$batchInfo['message'] = "";
		$batchInfo['returnInfo'] = "";
		if ($startat == 0)
		{
			if ($total)
			{
				$this->show_message(sprintf($this->phrase['core']['processing_records_x'], $total));
				$batchInfo['startat'] = 1;
				return $batchInfo;
			}
			else
			{
				$batchInfo['displaySkipMessage'] = 1;
				return $batchInfo;
			}
		}

		return $batchInfo;

	}

	protected function getDefaultGroupPerms()
	{
		require_once(DIR . '/includes/class_bitfield_builder.php');
		if (vB_Bitfield_Builder::build(false) !== false)
		{
			$myobj = vB_Bitfield_Builder::init();
		}
		else
		{
			print_r(vB_Bitfield_Builder::fetch_errors());
		}

		$groupinfo = [];
		foreach ($myobj->data['ugp'] AS $grouptitle => $perms)
		{
			for ($x = 1; $x <= 10; $x++)
			{
				$groupinfo["$x"]["$grouptitle"] = 0;
			}

			foreach ($perms AS $permtitle => $permvalue)
			{
				if (empty($permvalue['group']))
				{
					continue;
				}

				if (!empty($permvalue['install']))
				{
					foreach ($permvalue['install'] AS $gid)
					{
						$groupinfo["$gid"]["$grouptitle"] += $permvalue['value'];
					}
				}
			}
		}

		return $groupinfo;
	}

	protected function createSystemGroups()
	{
		$groupinfo = $this->getDefaultGroupPerms();

		// KEEP THIS IN SYNC with mysql-schema's usergroup code (lines~ 4103) until we refactor this & get rid of dupe code.
		$pmquota = 500;
		$systemgroups = [
			vB_Api_UserGroup::CHANNEL_OWNER_SYSGROUPID => $this->createUserGroupArray(
				vB_Api_UserGroup::CHANNEL_OWNER_SYSGROUPID,
				'channelowner_title',
				$groupinfo[9]
			),
			vB_Api_UserGroup::CHANNEL_MODERATOR_SYSGROUPID => $this->createUserGroupArray(
				vB_Api_UserGroup::CHANNEL_MODERATOR_SYSGROUPID,
				'channelmod_title',
				$groupinfo[10]
			),
			vB_Api_UserGroup::CHANNEL_MEMBER_SYSGROUPID => $this->createUserGroupArray(
				vB_Api_UserGroup::CHANNEL_MEMBER_SYSGROUPID,
				'channelmember_title',
				//this is wrong but still needs to be cleaned up VBV-12400
				$groupinfo[2]
			),
			vB_Api_UserGroup::CMS_AUTHOR_SYSGROUPID => $this->createUserGroupArray(
				vB_Api_UserGroup::CMS_AUTHOR_SYSGROUPID,
				'cms_author_title',
				//this is wrong but still needs to be cleaned up VBV-12400
				$groupinfo[2]
			),
			vB_Api_UserGroup::CMS_EDITOR_SYSGROUPID => $this->createUserGroupArray(
				vB_Api_UserGroup::CMS_EDITOR_SYSGROUPID,
				'cms_editor_title',
				//this is wrong but still needs to be cleaned up VBV-12400
				$groupinfo[2]
			),
		];

		$groupApi = vB_Api::instanceInternal('usergroup');
		$assertor = vB::getDbAssertor();
		foreach ($systemgroups AS $groupid => $data)
		{
			//If the usergroup doesn't exist, the api throws an exception. That drives the behavior.
			try
			{
				$group = $groupApi->fetchUsergroupBySystemID($groupid);
				if (empty($group['usergroupid']))
				{
					$data[vB_dB_Query::TYPE_KEY] = vB_dB_Query::QUERY_INSERT;
					$assertor->assertQuery('usergroup', $data);
				}
			}
			catch(Exception $e)
			{
				$data[vB_dB_Query::TYPE_KEY] = vB_dB_Query::QUERY_INSERT;
				$assertor->assertQuery('usergroup', $data);
			}
		}

		// rebuild usergroup cache
		vB_Library::instance('usergroup')->buildDatastore();
		$groupApi->fetchUsergroupList(true);
	}

	private function createUserGroupArray($systemgroupid, $titlephrase, $groupinfo)
	{
		// This function collapses the default values for the groups used *in this file*
		// which were mostly repeated.  If we need to make them different I recommend taking
		// the approach of batching which permissions belong together (for instance image size limits)
		// and creating an array for each different "package" and passing that in similarly to the
		// $groupinfo array.  (We don't necesarily need one for each group -- if there are two sets
		// of values for the five groups then we only need to arrays).  The goal is to avoid having
		// to constantly repeat massive arrays of identical values which will frequently fail to get
		// updated properly if we decide to change a default across the board.
		$pmquota = 500;
		$group = [
			'title' => $this->phrase['install'][$titlephrase],
			'description' => '',
			'usertitle' => '',
			'passwordexpires' => 0,
			'passwordhistory' => 0,
			'pmquota' => $pmquota,
			'pmsendmax' => 5,
			'opentag' => '',
			'closetag' => '',
			'canoverride' => 0,
			'attachlimit' => 0,
			'avatarmaxwidth' => 200,
			'avatarmaxheight' => 200,
			'avatarmaxsize' => 100000,
			'sigmaxrawchars' => 1000,
			'sigmaxchars' => 500,
			'sigmaxlines' => 0,
			'sigmaxsizebbcode' => 7,
			'sigmaximages' => 4,
			'sigpicmaxwidth' => 500,
			'sigpicmaxheight' => 100,
			'sigpicmaxsize' => 10000,
			'albumpicmaxwidth' => 600,
			'albumpicmaxheight' => 600,
			'albummaxpics' => 100,
			'albummaxsize' => 0,
			'pmthrottlequantity' => 0,
			'groupiconmaxsize' => 65535,
			'maximumsocialgroups' => 5,
			'systemgroupid' => $systemgroupid,

			//the group permissions contain a field that doesn't belong here.
			//let's only copy what we know we need to avoid errors.
			'forumpermissions' => $groupinfo['forumpermissions'],
			'forumpermissions2' => $groupinfo['forumpermissions2'],
			'pmpermissions' => $groupinfo['pmpermissions'],
			'wolpermissions' => $groupinfo['wolpermissions'],
			'adminpermissions' => $groupinfo['adminpermissions'],
			'genericpermissions' => $groupinfo['genericpermissions'],
			'genericpermissions2' => $groupinfo['genericpermissions2'],
			'signaturepermissions' => $groupinfo['signaturepermissions'],
			'genericoptions' => $groupinfo['genericoptions'],
			'usercsspermissions' => $groupinfo['usercsspermissions'],
			'visitormessagepermissions' => $groupinfo['visitormessagepermissions'],
			'socialgrouppermissions' => $groupinfo['socialgrouppermissions'],
			'albumpermissions' => $groupinfo['albumpermissions'],
		];

		return $group;
	}

	// Originally this was called at the top of syncNavBars() & insertDefaultNavbars(), but
	// removed because we actually have to import other things first if we want to import routes,
	// so that was quickly getting out of hand. We now use the approach of having syncNavBars()
	// (that might be called in earlyer 5xx upgrade steps) just save the route_guids, then
	// having insertDefaultNavbars() called in final::step_22 check for any guid references
	// and call save again, since by that point we should have the latest routes imported.
	/*
	protected function ensureNavBarsRoutes($showskipmessage = false)
	{
		$navbars = get_default_navbars();
		$guids = array_merge($this->recursivelyGetGuid($navbars['header']), $this->recursivelyGetGuid($navbars['footer']));
		$guids = array_unique($guids);

		$assertor = vB::getDbAssertor();
		$check = $assertor->getRows('routenew', ['guid' => $guids]);
		if (count($check) < count($guids))
		{
			$this->final_load_routes();
		}
		else
		{
			// Switching this to be called at the top of syncNavbars(). But if saving the
			// navbars on top fo the route import causes timeouts in the wild, we will need to
			// split them up into separate calls...
			if ($showskipmessage)
			{
				$this->skip_message();
			}
		}
	}
	*/

	private function recursivelyGetGuid($arr)
	{
		$guids = [];
		foreach ($arr AS $__elem)
		{
			if (isset($__elem['route_guid']))
			{
				$guids[] = $__elem['route_guid'];
			}
			if (!empty($__elem['subnav']))
			{
				$guids = array_merge($guids, $this->recursivelyGetGuid($__elem['subnav']));
			}
		}
		return $guids;
	}

	protected function insertDefaultNavbars()
	{
		$siteId = 1;
		$assertor = vB::getDbAssertor();
		/** @var vB_Library_Site */
		$siteLib = vB_Library::instance('site');
		// Need a session for route URL generation for the route_guid/routeid conversion to URLs
		// downstream of saveNavbar calls, since certain route construction may check view permissions
		// which explicitly require a session to exist.
		vB_Upgrade::createAdminSession();
		$site = $assertor->getRow('vBForum:site', ['siteid' => $siteId]);
		if (!empty($site))
		{
			// Some navbar was inserted at some point. Call syncNavbars([$item]) explicitly
			// in order to inject any newly/recently added default navbar item. See 574a2::step_4()
			// for converting a legacy navbar to route-associated navbar

			// Edit: We now check for any navitems referring to route_guid and do the save again
			// to allow for one last chance at replacing the guids with routeids, as when this is
			// called by final::step_22(), we know for sure we have all of the pages & routes imported.
			// We no longer import routes in syncNavbars(), because doing so actually requires
			// importing the pagetemplates & pages before it too, and trying to update all of the
			// older upgrade steps to do that was going to be painful. As such the syncNavbars() calls
			// could end up just saving the guids and not the routeids.

			$skipMessage = true;

			$site['headernavbar'] = vB_Utility_Unserialize::unserialize($site['headernavbar']);
			$guids = $this->recursivelyGetGuid($site['headernavbar']);
			if (count($guids) > 0)
			{
				$siteLib->saveHeaderNavbar($siteId, $site['headernavbar'], true);
				$skipMessage = false;
			}

			$site['footernavbar'] = vB_Utility_Unserialize::unserialize($site['footernavbar']);
			$guids = $this->recursivelyGetGuid($site['footernavbar']);
			if (count($guids) > 0)
			{
				$siteLib->saveFooterNavbar($siteId, $site['footernavbar'], true);
				$skipMessage = false;
			}

			if ($skipMessage)
			{
				$this->skip_message();
			}
			else
			{
				// A bit weird that we do the messaging *after* we run the processes, but not sure if it's
				// worth adding more complexity to above to get the order correct. AFIAK show_message() just
				// queues the message rather than immediately echoing it out anyways.
				$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'site'));
			}
		}
		else
		{
			// The SAVE code currently requires a site record to exist, & there might be other
			// areas that just expect one to exist, so we have to insert a blank one before
			// calling site functions.
			$assertor->insert('vBForum:site', ['siteid' => $siteId]);

			// With the new route_guid/routeid associations, we need
			// 1) the routes imported before we can save navbars and 2)
			// must go through the library save rather than serializing and
			// saving it directly.
			//$this->ensureNavBarsRoutes();

			$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'site'));
			$navbars = get_default_navbars();
			// Note about the 3rd bool flag -- annoyingly, in the DB navitem.title is a phrase key. Outside,
			// it apparently is the phrase value. This makes using the lib methods directly difficult if you're
			// not using it through sitebuilder exclusively, which IMO makes it a bad lib method. And surprise,
			// get_default_navbars() returns phrase keys in "title", since it's used to DIRECTLY insert the
			// data into DB by an insert query. I would change get_default_navbars(), but it's used in a number
			// of older upgrade steps that I don't have the time or means to test right now, so I can't.
			// So this bool is a workaround for that.
			$siteLib->saveHeaderNavbar($siteId, $navbars['header'], true);
			$siteLib->saveFooterNavbar($siteId, $navbars['footer'], true);
		}
	}

	// This function is weird and patched over the years, but more or less, what this does is
	// * For default "top level" nav items, whose 'title' matches the default entry:
	//      Add any and all missing default subnavs.
	// * For any default "top level" nav items that are missing from the current navbar:
	//      Only add default data for the top level item it's explicitly specified in $addNavBars
	// * It does not handle more than 1 level of subnavs (not sure if system properly supports
	//   that atm)
	protected function syncNavbars($addNavBars = [])
	{
		// With the new route_guid/routeid associations, we need
		// 1) the routes imported before we can save navbars and 2)
		// must go through the library save rather than serializing and
		// saving it directly.
		//$this->ensureNavBarsRoutes();
		// TODO: there's too many dependencies, e.g. routes import need pages imported, which might also need
		// page templates & possibly channels imported first.
		// As such, perhaps we should just save with GUIDs, then let final upgrade step_22 do the clean up
		// since at that point we know for sure the routes are imported?

		if (!is_array($addNavBars))
		{
			$addNavBars = [$addNavBars];
		}
		$navbars = get_default_navbars();
		$headernavbar = $navbars['header'];

		// Get site's current navbar data
		$siteId = 1;
		// We should probably fetch the navbar via the library, but the current lib methods have a few front-end tie-ins that
		// make that difficult (e.g. sitebuilder perm check, removing & adding certain data)
		// From what I can see, the save *should* still work OK.
		$site = vB::getDbAssertor()->getRow('vBForum:site', ['siteid' => $siteId]);

		$currentheadernavbar = vB_Utility_Unserialize::unserialize($site['headernavbar'] ?? 'a:0:{}');
		if (!$currentheadernavbar)
		{
			$currentheadernavbar = [];
		}

		foreach ($headernavbar AS $j => $item)
		{
			$tabExists = false;
			// Check Tab
			foreach ($currentheadernavbar AS $k => $currentitem)
			{
				if ($currentitem['title'] == $item['title'])
				{
					$tabExists = true;
					// We have the tab, check for subnavs of the tab
					$subnav = $item['subnav'] ?? [];
					foreach ($subnav AS $subitem)
					{
						$currentsubnav = $currentitem['subnav'] ?? [];
						foreach ($currentsubnav AS $currentsubitem)
						{
							if ($subitem['title'] == $currentsubitem['title'])
							{
								// The site already has the subitem, skip to next one
								continue 2;
							}
						}
						// The site doesn't have the subitem, we insert it
						$currentheadernavbar[$k]['subnav'][] = $subitem;
					}
				}
			}

			/* If tab does not exist and was specified in the params, insert the tab.
			 * This is to prevent addition of any default items that the user deleted from the header nav bar.
			 * As such, when adding new nav bar item(s) to the header in functions_installupgrade.php's
			 * get_default_navbars(), the upgrade step calling syncNavBars() should specify the title(s) of the
			 * newly added navBar(s)
			 */
			if (!$tabExists AND in_array($item['title'], $addNavBars))
			{
				// insert the item into header @ default index
				array_splice($currentheadernavbar, $j, 0, [$item]);
			}

		}

		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'site'));
		/** @var vB_Library_Site */
		$siteLib = vB_Library::instance('site');
		// Need a session for route URL generation for the route_guid/routeid conversion to URLs downstream of saveNavbar calls...
		vB_Upgrade::createAdminSession();
		$siteLib->saveHeaderNavbar($siteId, $currentheadernavbar, true);
	}

	/**
	 *	Updates the header tab urls with new urls.
	 *
	 *	This is intended to update default urls in the headers if they changed.
	 *	We work with an exact match to replace.  We don't really worry about
	 *	where we matched because if a default url changed, it changed
	 *	(and by doing an exact match we don't have to worry as much about
	 *	a similar url we weren't expecting).
	 */
	protected function updateHeaderUrls($urls)
	{
		$assertor = vB::getDbAssertor();

		// Get site's current navbar data
		$site = $assertor->getRow('vBForum:site', ['siteid' => 1]);

		$changed = false;
		$headernavbar = vB_Utility_Unserialize::unserialize($site['headernavbar']);
		foreach ((array)$headernavbar AS $key => $currentitem)
		{
			foreach ($urls AS $old => $new)
			{
				if ($headernavbar[$key]['url'] == $old)
				{
					$headernavbar[$key]['url'] = $new;
					$changed = true;
				}
			}

			// We have the tab, check for subnavs of the tab
			foreach ($currentitem['subnav'] ?? [] AS $subkey => $currentsubitem)
			{
				foreach ($urls AS $old => $new)
				{
					if ($headernavbar[$key]['subnav'][$subkey]['url'] == $old)
					{
						$headernavbar[$key]['subnav'][$subkey]['url'] = $new;
						$changed = true;
					}
				}

				$headernavbar[$key]['subnav'][$subkey]['url'] = vB_String::unHtmlSpecialChars($headernavbar[$key]['subnav'][$subkey]['url']);
			}
		}

		if ($changed)
		{
			$assertor->update('vBForum:site', ['headernavbar' => serialize($headernavbar)], ['siteid' => 1]);
		}

		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'site'));
	}

	protected function iRan($function, $startat = 0, $showmsg = true)
	{
		/*
			Don't forget to add a dummy, empty last step if the caller to this would be the last step otherwise.
		 */
		$script = str_replace('vB_Upgrade_', '', get_class($this));
		$step = str_replace('step_', '', $function);
		$log = vB::getDbAssertor()->assertQuery('vBInstall:upgradelog', ['script' => $script, 'step' => $step, 'startat' => $startat]);

		if ($log->valid())
		{
			if ($showmsg)
			{
				$this->show_message(sprintf($this->phrase['core']['skipping_already_ran'], $script, $step));
			}
			return true;
		}

		return false;
	}

	protected function getUGPBitfields($groupnames = [])
	{
		$requested = [];
		if (is_string($groupnames))
		{
			// Assume it's just 1 that's requested
			// todo: allow delimited string?
			$groupnames = [$groupnames];
		}

		if (!is_array($groupnames))
		{
			throw new Exception('Invalid groupnames');
		}

		foreach ($groupnames AS $__groupname)
		{
			// for O(1) isset() check.
			$requested[$__groupname] = $__groupname;
		}

		// taken from 510a5 upgrade. TODO: refactor to remove dupe code.
		vB_Upgrade::createAdminSession();
		$parsedRaw = vB_Xml_Import::parseFile(DIR . '/includes/xml/bitfield_vbulletin.xml');
		$permBits = [];
		foreach ($parsedRaw['bitfielddefs']['group'] AS $group)
		{
			if ($group['name'] == 'ugp')
			{
				foreach ($group['group'] AS $bfgroup)
				{
					if (($bfgroup['name'] == 'forumpermissions2') OR ($bfgroup['name'] == 'forumpermissions') OR
						($bfgroup['name'] == 'createpermissions') OR isset($requested[$bfgroup['name']])
					)
					{
						$permBits[$bfgroup['name']] = [];
						foreach ($bfgroup['bitfield'] AS $bitfield)
						{
							$permBits[$bfgroup['name']][$bitfield['name']] = intval($bitfield['value']);
						}
					}
				}
			}
		}

		return $permBits;
		/*
		use like
		$someNewPermsToSave['createpermissions'] |= $permBits['createpermissions']['vbforum_text'] |
		$permBits['createpermissions']['vbforum_gallery'] | $permBits['createpermissions']['vbforum_poll'] |
		$permBits['createpermissions']['vbforum_attach'] | $permBits['createpermissions']['vbforum_photo'] |
		$permBits['createpermissions']['vbforum_video'] | $permBits['createpermissions']['vbforum_link'];
			*/
	}


	//
	//	Upgrade step templates
	//
	//	There are some things we do a lot of and it would be good to abstract that here so that we
	//	aren't constantly rewriting the same code over and over. And so that the actual differences
	//	become clearer.  We've sort of down that with a few functions already (and we might want
	//	to move some functions down to this section).
	//

	protected function getBatchSize($size, $function)
	{
		$batch_options = [];
		require(DIR . '/install/upgrade_options.php');

		$stepkey = $this->SHORT_VERSION . ':' . $function;
		if (isset($batch_options['steps'][$stepkey]))
		{
			$size = $batch_options['steps'][$stepkey];
		}
		else if (!is_numeric($size))
		{
			if (!isset($batch_options['sizes'][$size]))
			{
				throw new Exception('Could not find batch size for ' . $size);
			}
			$size = $batch_options['sizes'][$size];
		}

		//always return at least one or something could go horribly awry
		return max(intval($size * $batch_options['masterslider']), 1);
	}


	/**
	 * Update in batches iterated over a table unique id field
	 *
	 * This will automatically run a query to generate the next id to avoid situations where
	 * gaps in the ids mean that the batch processing ends up taking longer than processing
	 * the actual records.  For example on instance where there were over a million missing
	 * ids that we iterated over 500 id numbers at a time -- selecting precisely 0 records
	 * to update each pass for *hours*.  This function ensures that each pass will process
	 * $batchsize records except for possibly the last step even if the numbers are not
	 * sequential.
	 *
	 * NOTE -- Do not blindly use this function if there is a better index to iterator
	 * 	over that better matches the records that need processing.  This is useful if
	 * 	we need to walk an entirely table based on the primary key either because we
	 * 	need to process every record or because we
	 *
	 * @param array $data -- The data array passed to the step
	 * @param int $batchsize -- The number of records to process per iteration
	 * @param string $maxquery -- Assertor queryname to get the max record id.  Unfortunately we
	 * 	can't currently get that information from a table query so we need to create a stored
	 * 	query and pass it seperately.
	 * @param string $table -- The table to iterate over.  Note that the actual update
	 * 	query doesn't need to confined to this table so long as the ID range being
	 * 	processed is on an id field in this table
	 * @param string $idfield -- The name of the field to iterate over.  This field
	 * 	*must* be an integer field and it must be indexed and the values in the field must
	 * 	be unique.  Otherwise we cannot guarentee that we'll process efficiently or that
	 * 	we will process every record in the table.
	 * 	Typically this will be the standard primary key for the table.
	 * @param callable $callback -- The function to call to handle this iteration.  This
	 * 	function should output a message via show_message.  The following parameters will
	 * 	be passed
	 * 	-- $startat the first id to process (will start at 0 even though we generally don't
	 * 		have an ID 0).  The callback should process an ID range *inclusive* of startat
	 * 	-- $nextid the next to process after this batch.  The callback should process an ID
	 * 		range *exclusive* of nextid
	 *
	 * @return array|null either a data array for step iteration or a null value
	 * 	if the process is complete.  Either way it's appropriate to return the
	 * 	value from a step without modification
	 *
	 * @deprecated use updateByWalker
	 */
	protected function updateByIdWalk($data, $batchsize, $maxquery, $table, $idfield, $callback)
	{
		$db = vB::getDbAssertor();
		$startat = intval($data['startat'] ?? 0);

		//this doesn't really work because "max" isn't propagated in $data, but
		//leaving it in so that it will work if we fix that.
		if (!empty($data['max']))
		{
			$max = $data['max'];
		}
		else
		{
			$max = $db->getRow($maxquery);
			$max = $max['maxid'];

			//If we don't have any posts, we're done.
			if (intval($max) < 1)
			{
				$this->skip_message();
				return;
			}
		}

		if ($startat > $max)
		{
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			return;
		}

		$nextrow = $db->getRows(
			$table,
			[
				vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
				vB_dB_Query::CONDITIONS_KEY => [
					['field' => $idfield, 'value' => $startat, 'operator' =>  vB_dB_Query::OPERATOR_GT],
				],
				vB_dB_Query::COLUMNS_KEY => [$idfield],
				vB_dB_Query::PARAM_LIMIT => 1,
				vB_dB_Query::PARAM_LIMITSTART => $batchsize
			],
			$idfield
		);

		//if we don't have a row, we paged off the table so we just need to go from start to the end
		if ($nextrow)
		{
			$nextrow = reset($nextrow);
			$nextid = $nextrow[$idfield];
		}
		else
		{
			//we don't include the next threadid in the query below so we need to go "one more than max"
			//to ensure that we process the last record and terminate on the next call.
			$nextid = $max + 1;
		}

		$result = call_user_func($callback, $startat, $nextid);

		if (empty($result['skipmessage']))
		{
			//in some cases we need to make sure nextid is greater than max but the next value is not inclusive we'll
			//generate results for startat <= id < nextid.  So let's report one less than $nextid as the end point.
			//This may result in showing 1-50 when we only have ids 49 and 51, but that's not totally inaccurate.
			//More importantly it's less obviously wrong then showing "1 to 51 (of 50)"
			$this->show_message(sprintf($this->phrase['core']['processed_records_x_y_z'], $startat, $nextid-1, $max), true);
		}

		return ['startat' => $nextid, 'max' => $max];
	}

	/*
	//I don't have time to test this right now, but this is the replacement for the deprecated walk function
	//in terms of the new walker class functionality
	protected function updateByIdWalk($data, $batchsize, $maxquery, $table, $idfield, $callback)
	{
		$walker = new vB_UpdateTableWalker(vB::getDBAssertor());
		$walker->setBatchSize($this->getBatchSize($batchsize);
		$walker->setMaxQuery($maxquery);
		$walker->setNextidQuery($table, $idfield);
		$walker->setCallback($callback);

		return $this->updateByWalker($walker, $data);
	}
	*/

	protected function updateByWalker(vB_Interface_UpdateWalker $walker, array $data) : array
	{
		$startat = intval($data['startat'] ?? 0);

		if (!empty($data['max']))
		{
			$max = $data['max'];
		}
		else
		{
			$max = $walker->getMax();

			//If we don't have any records, we're done.
			if ($max < 1)
			{
				$this->skip_message();
				return [];
			}
		}

		if ($startat > $max)
		{
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			return [];
		}

		//if we run off the table, nextid will be PHP_INT_MAX.  That actually should work since there shouldn't
		//be an records beyond $max to worry about (by definition) so any value > $max should be as good as any
		//other for the end case.  But this makes the messaging cleaner
		$nextid = $walker->getNextId($startat);
		$nextid = min($nextid, $max + 1);

		$result = $walker->runBatch($startat, $nextid);

		if (empty($result['skipmessage']))
		{
			//The nextid is not inclusive -- we generate results for startat <= id < nextid
			//And we aren't guarenteed that that results are solid so it's possible that (nextid-1) doesn't exist
			//but we also don't have a good way of finding out what the actual id previous to nextid really is.
			//
			//We'll if the start is 1 and the nextid is 51 we'll show "1 to 50" as the range being searched.
			//It's not completely inaccurate and it more accurate than "1 to 51".  Moreover it prevents showing
			//"51 to 101 (of 100) for the last step, which just looks wrong.
			//
			//The display here is mostly just to show progress anyway so the fuzziness of the details isn't
			//going to cause any concern

			$this->show_message(sprintf($this->phrase['core']['processed_records_x_y_z'], $startat, $nextid-1, $max), true);
		}

		return ['startat' => $nextid, 'max' => $max];
	}

	/**
	 * 	Repeat a batch with diminishing matches.
	 *
	 *	This is intended to handle situations where we don't have a good id range to iterate on and the
	 *	query is such that it will naturally reduce the population of matches -- specifically updates/deletes
	 *	that remove/change the records so they no longer match the query.
	 *
	 *	THIS DEPENDS ON THE QUERY BEING PASSED TO RETURN THE NUMBER OF MATCHES AND TO RETURN 0 MATCHES
	 *	WHEN THE WORK IS COMPLETED, OTHERWISE THERE IS A RISK OF AN INFINITE LOOP
	 *
	 */
	//we might want to loosen this up like the updatebywalk but it may not be necesary because
	//1) It's simpler, we don't have seperate queries for the max/nextid/process
	//2) It feels like we won't have a case beyond a single query and if we do we can fake that with a method query
	//3) If we could use a table query, we should probably be iterating over that table's key anyway.
	protected function proccessChangeBatch(string $query, array $params, int $batchsize, array $data) : array
	{
		//we use the start at to count batches but not to handle processing
		$startat = $data['startat'] ?? 0;

		//This is reasonably safe because we don't seperately detect the records to process.  If we processed records we'll assume
		//we might need to do so again.  If we don't procees records were done.  Even if there are records we should process and don't
		//then we'll be fine.  The only problem is if we update records in a way that doesn't remove them from the list of records to
		//be processed.  We might to include a failsafe maximum but it's hard to figure out how to do that -- especially since the
		//orphaned record queries doing the full table scan for a count potentially a little much.
		$result = vB::getDbAssertor()->assertQuery($query, array_merge($params, ['batchsize' => $batchsize]));

		//startat is zero base but one based indexing is more user friendly so adjust the display.
		if ($result)
		{
			$this->show_message(sprintf($this->phrase['core']['processed_batch_x_y'], $startat + 1, $batchsize), true);
			return ['startat' => $startat + 1];
		}
		else
		{
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			return [];
		}
	}

	public function reinstallProductPackage(
		$package = 'twitterlogin',
		$forceFreshInstall = false,
		$deferRebuild = false,
		$skipProductAutoInstallCheck = false
	)
	{
		// Call this to pick up any product XML changes between versions (including alpha/beta)
		$assertor = vB::getDbAssertor();
		$check = $assertor->getRow('product', ['productid' => $package]);

		$datastore = vB::getDatastore();
		$hooksEnabled = $datastore->getOption('enablehooks');
		if (!empty($check) OR $forceFreshInstall)
		{
			vB_Upgrade::createAdminSession();


			if (!defined('VB_PKG_PATH'))
			{
				$packagesDir = realpath(DIR . '/packages') . '/';
			}
			else
			{
				$packagesDir = VB_PKG_PATH;
			}


			$xmlDir = $packagesDir . "$package/xml";
			$class = $package . '_Product';

			$overwrite = true;
			// todo: some extensions do not have a product php file, but rather the
			// extension class specifies the autoinstall, and vb_api_extensions handles autoinstall
			// (via the same vB_Library_Functions::installProduct()).
			// How do we specify those to be forcibly upgraded?
			$autoInstall = (class_exists($class) AND property_exists($class, 'AutoInstall') AND $class::$AutoInstall);

			if ($skipProductAutoInstallCheck OR $autoInstall)
			{
				if (empty($check))
				{
					$this->show_message(sprintf($this->phrase['final']['installing_product_x'], $package));
				}
				else
				{
					$this->show_message(sprintf($this->phrase['final']['updating_product_x'], $package));
				}
				$printInfo = false;
				$info = vB_Library_Functions::installProduct($package, $xmlDir, '', $overwrite, $printInfo, $deferRebuild);

				$disableProduct = true;
				$disableReason = "";

				/*
					If the product build was successful, info is an array of various information,
					with a 'need_merge' key pointing to a boolean true/false.
					If the product build threw an exception (e.g. due to dependency failures),
					vB_Library_Functions::installProductXML() returns boolean false.
					If build_all_styles() failed, install_product() returns the error string.
				 */
				if (is_string($info))
				{
					// This is *probably* a string error message. It's possibly from a style build failure
					$disableReason = $info;
				}
				elseif (is_array($info))
				{
					// success
					$disableProduct = false;
				}
				elseif ($info === false)
				{
					// exception, probably dependency failure.
					// ATM this doesn't get out of the exception catch.
					$disableReason = vB_Library_Functions::getLastError();
					if (empty($disableReason))
					{
						$disableReason = "Unknown Error. Possibly a product dependency check failure.";
					}
				}
				else
				{
					// unknown/undefined condition. Not known to happen at the moment
				}

				vB_Library::instance('product')->buildProductDatastore();
				if (!$hooksEnabled)
				{
					/*
						Re-installing the twitterlogin package automatically enables the hook system.
						If they had it disabled earlier, set it back to disabled.
						We could consider just skipping the re-install altogether if the hook system is
						disabled, but I worry that doing so would lead to data inconsistencies in the
						tester DBs. Note that we check for the existence of the twitterlogin package first,
						which implies (I think) that at *some* point, the product got installed and the
						hook/product system was enabled, then disabled by the admin.
					 */
					$assertor->update('setting', ['value' => 0], ['varname' => 'enablehooks']);
					vB::getDatastore()->build_options();
				}


				// Use title if it's available in the product table.
				// This is a bit sketchy, but we'd have to re-parse the XML to get the title if it's
				// a new install and it failed.
				if (!empty($check['title']))
				{
					$productTitle = $check['title'];
				}
				else
				{
					// If the install failed before the product DB insert, the only place remaining
					// that has the title is the XML. For now, just use the productid.
					$check2 = $assertor->getRow('product', ['productid' => $package]);
					if (!empty($check2['title']))
					{
						$productTitle = $check2['title'];
					}
					else
					{
						$productTitle = $package;
					}
				}


				if ($disableProduct OR !empty($check) AND $check['active'] == 0)
				{
					// Either install failed, or this was previously disabled so we're setting it back to disabled.
					$assertor->update('product', ['active' => 0], ['productid' => $package]);

					if (!empty($disableReason))
					{
						$this->show_message(sprintf($this->phrase['final']['product_x_disabled_reason_y'], $productTitle, $disableReason));
						$this->add_adminmessage(
							'disabled_product_x_y_z',
							[
								'dismissable' => 1,
								'script'      => '',
								'action'      => '',
								'execurl'     => '',
								'method'      => '',
								'status'      => 'undone',
							],
							true,
							[$productTitle, $package, $disableReason]
						);
					}
					else
					{
						$this->show_message(sprintf($this->phrase['final']['product_x_updated_remains_disabled'], $package));
					}
				}
			}
			else
			{
				$this->skip_message();
			}
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	 * Replace the instances of a module ("old module") with instance of another module
	 * ("new module"), and then delete the old module & module definitions. The new module being
	 * used must already exist.
	 *
	 * This does *NOT* replace phrase keys or channel guids in the config data, but that
	 * will happen in upgrade_final, which should be fine.
	 */
	protected function replaceModule($oldWidgetGuid, $newWidgetGuid, $newDefaultAdminConfig = [])
	{
		$assertor = vB::getDbAssertor();

		$oldWidget = $assertor->getRow('widget', ['guid' => $oldWidgetGuid]);
		$newWidget = $assertor->getRow('widget', ['guid' => $newWidgetGuid]);

		$instancesDeleted = 0;
		$widgetDeleted = false;

		if ($oldWidget)
		{
			// convert instances of the old widget to instances of the new widget
			$oldInstances = $assertor->getRows('widgetinstance', ['widgetid' => $oldWidget['widgetid']]);
			foreach ($oldInstances AS $oldInstance)
			{
				$conditions = ['widgetinstanceid' => $oldInstance['widgetinstanceid']];
				$values = [];

				// change to new  widgetid
				$values['widgetid'] = $newWidget['widgetid'];

				// change admin config to the new default
				// we simply copy the new default adminconfig, but
				// if the old adminconfig has any of the same config
				// settings as the new one, the old values are preserved
				// e.g., results per page or hide title, etc.
				$oldAdminConfig = [];
				$newAdminConfig = $newDefaultAdminConfig;
				if (!empty($oldInstance['adminconfig']))
				{
					$temp = vB_Utility_Unserialize::unserialize($oldInstance['adminconfig']);
					if ($temp)
					{
						$oldAdminConfig = $temp;
					}
				}
				foreach ($newAdminConfig AS $k => $v)
				{
					if (isset($oldAdminConfig[$k]))
					{
						$newAdminConfig[$k] = $oldAdminConfig[$k];
					}
				}
				$values['adminconfig'] = serialize($newAdminConfig);

				// update
				$assertor->update('widgetinstance', $values, $conditions);

				++$instancesDeleted;
			}

			// delete the old widget & widget definition records
			$assertor->delete('widget', ['widgetid' => $oldWidget['widgetid']]);
			$assertor->delete('widgetdefinition', ['widgetid' => $oldWidget['widgetid']]);

			$widgetDeleted = true;
		}

		return [
			'updated' => $widgetDeleted,
			'instancesDeleted' => $instancesDeleted,
		];
	}

	/**
	 * Load MYSQL schema
	 *
	 * @return	array
	 */
	protected function load_schema()
	{
		$db = $this->db;
		$vbphrase = $this->phrase['vbphrase'];
		$install_phrases = $this->phrase['install'];
		$phrasetype = $this->phrase['phrasetype'];
		$customphrases = $this->phrase['custom'];

		$schema = [];
		if (vB_Upgrade::isCLI())
		{
			require(DIR . '/install/mysql-schema.php');
		}
		else
		{
			require_once(DIR . '/install/mysql-schema.php');
		}

		return $schema;
	}

	/**
	 * @return string[]
	 */
	protected function fetchDefaultReactionLabels() : array
	{
		// This was functioned in case we wanted to re-use it for
		// explicitly enabling the default emojis instead of relying
		// on the library's current behavior of implicitly enabling
		// ALL emojis when unspecified by default.
		// Issue VBV-21488, pre-enabled list.
		// Keep this in sync with the last column in core/libraries/misc/emoji/emoji-ordering.txt
		$defaults = [
			'grinning face',
			'smiling face with hearts',
			'smiling face with sunglasses',
			'enraged face',
			'nauseated face',
			'thumbs up',
			'thumbs down',
			// Added in VB6-213
			'face with tears of joy',
			'face blowing a kiss',
			'disappointed face',
			'hot beverage',
		];

		return $defaults;
	}

	protected function addDefaultReactionsGroup()
	{
		/** @var vB_Library_Nodevote */
		$nodevoteLib = vB_Library::instance('nodevote');
		$votegroup = vB_Library_Reactions::VOTE_GROUP;

		$check = $nodevoteLib->getVoteMetaData('votegroupsByLabel');
		if (empty($check[$votegroup]))
		{
			$nodevoteLib->addVoteGroup($votegroup, 'radio');
		}

		return true;
	}

	protected function addDefaultReactions()
	{
		/** @var vB_Library_Nodevote */
		$nodevoteLib = vB_Library::instance('nodevote');
		/** @var vB_Library_Reactions */
		$reactionsLib = vB_Library::instance('reactions');
		$votegroup = vB_Library_Reactions::VOTE_GROUP;

		// If, for some reason, something happened and we don't have the required group yet, abort.
		$check = $nodevoteLib->getVoteMetaData('votegroupsByLabel');
		if (empty($check[$votegroup]))
		{
			return false;
		}

		/*
		I keep going back and forth on it, but we are going to start off with a small preset list of emojis
		for MVP.
		To achieve this, we could could potentially only import the 8 or so subset, or we could import the
		current full list of emojis then disable all but 8 of them.
		I think both sides have their pros and cons, but for now I'm deciding to go with only importing
		the small subset we need, unless need arises (e.g. for testing?) where I need more than the 8
		defaults.
		 */

		// Previously, nodevotes & reactions were tied by their human-readable "label".
		// We may want to change it to something else in the future if new emojis have
		// characters unsuitable for `nodevotetype`.`label` db column in their labels,
		// but for now I prefer a legible label, and using the raw utf8 emoji or htmlentities
		// would not be readable for most db clients AFAIK.
		// Edit: Nowadays, we have various `reactionoption`s that are tied to the `nodevotetype`
		// via the `votetypeid` for programmatic reasons. However for inserts we still
		// key everything off the human-readable "label" (since at this stage we don't have
		// any votetypeids)
		$defaultOptions = $this->getDefaultReactionOptions();

		foreach ($defaultOptions AS $__label => $__options)
		{
			try
			{
				$reactionsLib->addReaction($__label, $__options);
			}
			catch (Exception $e)
			{
				// This might happen with potential collisions (e.g. if already inserted)
				// This should be fine to skip, unless we start allowing custom data that
				// might begin conflicting against defaults.
				// We might, in the future, want to pre-check for collisions before e.g.
				// adding a new subset of emojis, but for now relying on the library
				// rejecting collisions and skipping is intentional.
			}
		}

		return true;
	}
	protected function getDefaultReactionOptions()
	{
		/** @var vB_Library_Reactions */
		$reactionsLib = vB_Library::instance('reactions');
		['fulldata' => $emojionlydata,] = $reactionsLib->loadSourceEmojisData();
		$emojihtmlByLabel = array_column($emojionlydata, 'emojihtml', 'label');
		$orderByLabel = array_column($emojionlydata, 'order', 'label');

		$defaults = $this->fetchDefaultReactionLabels();
		$reputables = [
			'grinning face' => 1,
			'smiling face with hearts' => 1,
			'smiling face with sunglasses' => 1,
			'thumbs up' => 1,
			'face with tears of joy' => 1,
		];
		$defaultoptions = [];
		foreach ($defaults AS $__label)
		{
			$defaultoptions[$__label] = [
				'enabled' => 1,
				'user_rep_factor' => $reputables[$__label] ?? 0,
				'user_like_countable' => $reputables[$__label] ?? 0,
				'system' => 1,
				'guid' => $__label,
				'emojihtml' => $emojihtmlByLabel[$__label],
				'order' => $orderByLabel[$__label],
			];
		}
		return $defaultoptions;
	}

	// Copy of vB_Library_Reactions::getThumbsUp() to avoid caching certain interim data during upgrades
	protected function getThumbsUpNodevoteData()
	{
		/** @var vB_Library_Reactions */
		$reactionsLib = vB_Library::instance('reactions');
		$reactionsLib->getReactionsNodevotetypes();
		return $reactions[vB_Library_Reactions::THUMBS_UP_LABEL] ?? [];
	}
}

//provide a concrete child class for Empty upgrade steps.  This use do to more but the
//version calculation logic was moved to the parent.  We may not need it as a seperate class
//but it's potentially useful if Empty steps diverge again.
class vB_Upgrade_Version_Empty extends vB_Upgrade_Version
{
}

//This defines the functions that the walker function will need to process the update
//Actual initialization functions are specific to the implementation and are used
//by the individual upgrade step
interface vB_Interface_UpdateWalker
{
	public function getMax() : int;
	public function getNextId(int $startat) : int;
	public function runBatch(int $startat, int $nextid) : array;
}

class vB_UpdateTableWalker implements vB_Interface_UpdateWalker
{
	private $db;

	private $batchsize;

	private $maxquery;
	private $maxqueryparams;

	private $table;
	private $idfield;
	private $tableparams;

	private $callback;

	/**
	 * Factory method to get walker that trivially iterates over an entire table with no particular filtering.
	 *
	 * Caller still needs to register a callback function.
	 */
	public static function getSimpleTableWalker(vB_dB_Assertor $db, int $batchsize, string $table, string $idfield) : vB_Interface_UpdateWalker
	{
		$walker = new vB_UpdateTableWalker($db);
		$walker->setBatchSize($batchsize);
		$walker->setMaxQuery($table, [
			vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SUMMARY,
			vB_dB_Query::COLUMNS_KEY => ['MAX(' . $idfield . ')'],
		]);
		$walker->setNextidQuery($table, $idfield);
		return $walker;
	}

	/**
	 *  Factory method to get walker that trivially iterates over an entire table with the same filtering on the
	 *  max query as well as the next id query.
	 *
	 * Caller still needs to register a callback function.
	 */
	public static function getSimpleTableWalkerFilter(vB_dB_Assertor $db, int $batchsize, string $table, string $idfield, array $filter) : vB_Interface_UpdateWalker
	{
		$walker = new vB_UpdateTableWalker($db);
		$walker->setBatchSize($batchsize);
		$walker->setMaxQuery($table, [
			vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SUMMARY,
			vB_dB_Query::COLUMNS_KEY => ['MAX(' . $idfield . ')'],
			vB_dB_Query::CONDITIONS_KEY => $filter,
		]);
		$walker->setNextidQuery($table, $idfield, $filter);
		return $walker;
	}

	public function __construct(vB_dB_Assertor $db)
	{
		$this->db = $db;
	}

	public function setBatchSize(int $batchsize)
	{
		$this->batchsize = $batchsize;
	}

	public function setMaxQuery(string $query, array $conditions = []) : void
	{
		$this->maxquery = $query;
		$this->maxqueryparams = $conditions;
	}

	public function setNextidQuery(string $table, string $idfield, array $conditions = []) : void
	{
		$this->table = $table;
		$this->idfield = $idfield;
		$this->tableparams = $conditions;
	}

	public function setCallback(Callable $callback)
	{
		$this->callback = $callback;
	}

	/**
	 * A cover function to allow for the simple and common case where a single stored query should be run
	 *
	 * The startat and nextid
	 *
	 * @param string $query -- The stored/method query to be used.
	 * @param array $conditions -- Conditions that need to be passed to the query.  The startat
	 * 	nextid params for batching will automaically be added to the conditions array.
	 *
	 * @return void
	 */
	public function setCallbackQuery(string $query, array $conditions = []) : void
	{
		$this->callback = function($startat, $nextid) use ($query, $conditions)
		{
			$this->db->assertQuery($query, array_merge($conditions, ['startat' => $startat, 'nextid' => $nextid]));
		};
	}

	/**
	 * A cover function to handle updating fields statically on the table we are scanning
	 *
	 * This will update the table set via setNextidQuery using the same idfield value
	 * passed to that function.
	 *
	 *
	 * @param array $fields -- The field update array in the form of ['field' => value]
	 * @param array $conditions -- optional additional conditions to pass for the table.
	 * 	For instance if we want to change a column from 0 to 7 we'd pass to ['field' => 0]
	 * 	so we don't update fields set to 5.
	 */
	//It's possible that we may want to update a different table that based on a scan of
	//a table that shares the same id values.  That's more a more complex case and can be
	//handled by a custom callback (or a new cover function if it proves common enough)
	public function setCallbackUpdateTable(array $fields, $conditions = []) : void
	{
		$this->callback = function($startat, $nextid) use ($fields, $conditions)
		{
			//if the startat/next values on the table don't match field we looked up in the index we are
			$rangeconditions = [
				['field' => $this->idfield, 'value' => $startat, 'operator' => vB_dB_Query::OPERATOR_GTE],
				['field' => $this->idfield, 'value' => $nextid, 'operator' => vB_dB_Query::OPERATOR_LT],
			];

			$this->db->update($this->table, $fields, array_merge($conditions, $rangeconditions));
		};
	}

	public function getMax() : int
	{
		$max = $this->db->getRow($this->maxquery, $this->maxqueryparams);
		return intval($max['max'] ?? $max['maxid'] ?? 0);
	}

	public function getNextId(int $startat) : int
	{
		$conditions = $this->tableparams;
		$conditions[] = ['field' => $this->idfield, 'value' => $startat, 'operator' =>  vB_dB_Query::OPERATOR_GT];
		$nextrow = $this->db->getRows(
			$this->table,
			[
				vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
				vB_dB_Query::CONDITIONS_KEY => $conditions,
				vB_dB_Query::COLUMNS_KEY => [$this->idfield],
				vB_dB_Query::PARAM_LIMIT => 1,
				vB_dB_Query::PARAM_LIMITSTART => $this->batchsize
			],
			$this->idfield
		);

		if ($nextrow)
		{
			$nextrow = reset($nextrow);
			return intval($nextrow[$this->idfield]);
		}

		//returning false here complicates the typing (we can't specificy multiple types for a return until PHP8)
		//and PHP_INT_MAX is as good of a dummy value as false (better since it might actually work as a real value)
		return PHP_INT_MAX;
	}

	public function runBatch(int $startat, int $nextid) : array
	{
		return ($this->callback)($startat, $nextid) ?? [];
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 116853 $
|| #######################################################################
\*=========================================================================*/
