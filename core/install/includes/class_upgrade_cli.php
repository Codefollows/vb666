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

class vB_Upgrade_Cli extends vB_Upgrade_Abstract
{
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
	* vB_Upgrade Object
	*
	* @var vB_Upgrade
	*/
	protected $upgrade = null;

	/**
	* Identifier for this library
	*
	* @var	string
	*/
	protected $identifier = 'cli';

	/**
	* Limit Step Queries
	*
	* @var	boolean
	*/
	protected $limitqueries = false;

	/**
	* Command Line Options
	*
	* @var	array
	*/
	protected $options = [];

	/**
	 * CLI called with single verion specified
	 * @var bool
	 */
	private $versionselect = false;

	/**
	 * Location of the CLI config file relative to the site root
	 * @var string
	 */
	private static $cli_config_file = "includes/config_cli.php";

	/**
	 *
	 * @var array
	 */
	private static $config_cli = null;

	/**
	 * Constructor.
	 *
	 * @param	vB_Registry	Reference to registry object
	 */
/*
	public function __construct(&$registry, $phrases, $setuptype = 'upgrade', $version = null, $options = [])
	{
		parent::__construct($registry, $phrases, $setuptype, $version, $options);
	}
 */

	/**
	 * Stuff to setup specific to CLI upgrading - executes after upgrade has been established
	 *
	 * 	@param	string	the script to be process
	 * 	@param	bool	whether to process the script immediately
	 */
	protected function init($script, $process = true)
	{
		define('SUPPRESS_KEEPALIVE_ECHO', true);

		if (!defined('SKIPDB'))
		{
			vB_Upgrade::createAdminSession();
			require_once(DIR . '/includes/class_bitfield_builder.php');
			vB_Bitfield_Builder::save($this->db);
		}

		parent::init($script);

		$this->process_options();

		if (!empty($this->startup_errors))
		{
			$this->echo_phrase($this->phrase['core']['startup_errors_desc'] . "\r\n");
			foreach ($this->startup_errors AS $error)
			{
				$this->echo_phrase("* $error\r\n");
			}
			return;
		}

		if (!empty($this->startup_warnings))
		{
			$this->echo_phrase($this->phrase['core']['startup_warnings_desc'] . "\r\n");
			foreach ($this->startup_warnings AS $error)
			{
				$this->echo_phrase("* $error\r\n");
			}

			$response = $this->prompt(
				$this->phrase['core']['startup_warnings_instructions_cli'],
				['1','2']
			);

			//if its 2 we want to continue;
			if ($response == 1)
			{
				return;
			}
		}

		$cliparams = $this->getCliParams();
		//I'm not sure if this is *ever* set.  But this needs to be sorted out in the upgrade
		//where it's used.  For now make the installer work.
		$cliver = $cliparams['cliver'] ?? false;
		$version = isset($this->versions[$cliver]) ? $cliver : false;

		// Where does this upgrade need to begin?
		if ($script)
		{
			$this->scriptinfo['version'] = $script;
		}
		else if ($version)
		{
			$this->versionselect = true;
			$this->scriptinfo['version'] = $version;
			$this->scriptinfo['only'] = $cliparams['clionly'];
			$this->scriptinfo['step']	= 1;
			$this->scriptinfo['startat'] = 0;
		}
		else
		{
			$this->scriptinfo = $this->get_upgrade_start();
		}

		if ($process)
		{
			$version = $this->scriptinfo['version'];
			$singlescript = $cliparams['clionly'] ?? null;

			//if the first step is "final" we want to run it, otherwise
			//it gets handled in process_script as part of the last
			//version run.
			do
			{
				$version = $this->process_script($this->scriptinfo['version']);
				if ($version != 'final' AND !$singlescript)
				{
					$this->scriptinfo = $this->get_upgrade_start($version);
				}
			}	while ($version != 'final' AND !$singlescript);


			if ($this->registry->options['storecssasfile'])
			{
				$this->echo_phrase($this->convert_phrase($this->phrase['core']['after_upgrade_cli']));
			}
		}
	}

	//this is the analog to the AJAX upgrade fetching params from the web params arrays
	//this used to be done in the upgrade.php and passed in a dubious way from the registry
	//but it's only relevant to the CLI upgrader and there isn't a reason for this class to not
	//assume that we are calling the command line because that's the point of this class.
	private function getCliParams()
	{
		global $argv;

		// Don't set the version/only options for an install. They're probably harmless but they aren't applicable
		// and we need to ensure it doesn't mess up the shared logic if they are present.
		$cli = [];
		if (VB_AREA == 'Upgrade')
		{
			if (!empty($argv) AND count($argv) > 1)
			{
				$options = getopt('', ['version::', 'only::', 'library::']);
				if (!empty($options['version']))
				{
					$cli['cliver'] = $options['version'];
					$cli['clionly'] = (isset($options['only']) AND (($options['only'] == 'y') OR ($options['only'] == 1))) ? 1 : 0;
				}
				else
				{
					$cli['cliver'] = trim($argv[1] ?? 'xxx');
					$cli['clionly'] = (isset($argv[2]) AND ($argv[2] == 'y')) ? 1 : 0;
				}
			}
			else
			{
				$cli['cliver'] = 'xxx';
				$cli['clionly'] = 0;
			}
		}
		return $cli;
	}

	protected function show_errors_only()
	{
		$this->echo_phrase($this->phrase['core']['startup_errors_desc'] . "\r\n");
		foreach ($this->startup_errors AS $error)
		{
			$this->echo_phrase("* $error\r\n");
		}
	}

	/**
	* Process Command Line options on $this->options
	*
	*/
	private function process_options()
	{
		if (in_array('skip_template_merge', $_SERVER['argv']))
		{
			$this->options['skiptemplatemerge'] = true;
		}
	}

	/**
	* Echo a phrase after converting to console charset
	*
	* @var	string	Phrase to do charset conversion on for
	*
	*/
	private function echo_phrase($string)
	{
		if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')
		{
			echo to_charset($string, 'ISO-8859-1', 'IBM850');
		}
		else
		{
			echo $string;
		}
	}

	/**
	*	Attempt to make a standard phrase, with HTML markup, useable by the CLI system
	*
	*	@var	string	Phrase to do HTML rudimentery replacement on
	*
	* @return string
	*/
	protected function convert_phrase($phrase)
	{
		$phrase = str_replace('\r\n', "\r\n", $phrase);
		$search = array(
			'#<br\s+/?' . '>#i',
			'#</p>#i',
			'#<p>#i',
		);
		$replace = array(
			"\r\n",
			"",
			"\r\n",
		);
		$phrase = strip_tags(preg_replace($search, $replace, $phrase));
		if (!(preg_match("#\r\n$#si", $phrase)))
		{
			$phrase .= "\r\n";
		}

		return $phrase;
	}

	/**
	* Process a script
	*
	* @var	string	script version
	* @var	string	execute a single script
	*/
	protected function process_script($version)
	{
		$script = $this->load_script($version);

		if (!$this->verify_version($version, $script) AND !$this->versionselect)
		{
			if ($version == 'final')
			{
				end($this->versions);
				$version = key($this->versions);
			}

			$response = $this->prompt(
				sprintf($this->phrase['core']['wrong_version_cli'],
				$this->versions[$version],
				$this->registry->options['templateversion']),
				['1','2']
			);
		}

		if (empty($response) OR $response == 1)
		{
			//we may, in some cases, have the step set to 0
			$startstep = max($this->scriptinfo['step'], 1);
			$endstep = $script->stepcount;

			$this->echo_phrase("\r\n");
			if (in_array($this->scriptinfo['version'], $this->endscripts))
			{
				$this->echo_phrase($this->convert_phrase($this->phrase['core']['processing_' . $this->scriptinfo['version']]));
			}
			else
			{
				// <!-- "upgradeing" is a purposeful typo -->
				$this->echo_phrase($this->convert_phrase(sprintf($this->phrase['core']['upgradeing_to_x'], $script->LONG_VERSION)));
			}
			$this->echo_phrase("----------------------------------\r\n");

			if ($endstep)
			{
				for ($x = $startstep; $x <= $endstep; $x++)
				{
					$this->echo_phrase(sprintf($this->phrase['core']['step_x'], $x));
					$this->execute_step($x, $script);
					$script->log_upgrade_step($x);
				}
			}
			else
			{
				$script->log_upgrade_step(0);
			}
			$this->echo_phrase($this->convert_phrase($this->phrase['core']['upgrade_complete']));
			$version = $script->SHORT_VERSION;

			if ($endstep)
			{
				$this->process_script_end();
			}
		}
		else
		{
			$version = $this->fetch_short_version($this->registry->options['templateversion']);
		}

		return $version;
	}

	/**
	* Executes the specified step
	*
	* @param	int			Step to execute
	* @param		object	upgrade script object
	* @param	boolen	Check if table exists for create table commands
	* @param	array		Data to send to step (startat, prompt results, etc)
	*
	*/
	public function execute_step($step, $script, $check_table = true, $data = [], $recurse = true)
	{
		$data = $this->getData($data);
		try
		{
			$data['options'] = $this->options;
			$result = $script->execute_step($step, $check_table, $data);
		}
		catch (vB_Exception_Api $e)
		{
			foreach($e->get_errors() AS $error)
			{
				$message = $this->render_phrase_array($error);
				$message = "Error $message";
				$this->echo_phrase($this->convert_phrase($message));
				$this->echo_phrase(unhtmlspecialchars(vB_Utilities::getExceptionTrace($e)));
			}
			exit(7);
		}
		catch (Throwable $e)
		{
			$message = 'Error ' . $e->getMessage();
			$this->echo_phrase($this->convert_phrase($message));
			$this->echo_phrase(unhtmlspecialchars(vB_Utilities::getExceptionTrace($e)));
			exit(7);
		}


		$output = false;
		if ($result['message'])
		{
			$count = 0;
			foreach ($result['message'] AS $message)
			{
				if (trim($message['value']))
				{
					$output = true;
					if ($count > 0)
					{
						$this->echo_phrase(str_pad('  ', strlen(sprintf($this->phrase['core']['step_x'], $step)), ' ', STR_PAD_LEFT));
					}
					$this->echo_phrase($this->convert_phrase($message['value']));
					$count++;
				}
			}
		}

		if (!empty($result['error']))
		{
			foreach($result['error'] AS $error)
			{
				if ($error['fatal'])
				{
					switch ($error['code'])
					{
						case vB_Upgrade_Version::MYSQL_HALT:
							$this->echo_phrase("\r\n----------------------------------\r\n");
							$this->echo_phrase($error['value']['error']);
							$this->echo_phrase("\r\n----------------------------------\r\n");
							$this->echo_phrase($error['value']['message']);
							exit(2);
							break;
						case vB_Upgrade_Version::PHP_TRIGGER_ERROR:
							trigger_error($this->convert_phrase($error['value']), E_USER_ERROR);
							break;
						case vB_Upgrade_Version::APP_CREATE_TABLE_EXISTS:
							$response = $this->prompt(sprintf($this->phrase['core']['tables_exist_cli'], $error['value']), array('1',''));
							if ($response !== '1')
							{
								exit(2);
							}
							else
							{
								$this->execute_step($step, $script, false);
								if (!$output)
								{
									$output = true;
								}
							}
							break;

						case vB_Upgrade_Version::CLI_CONF_USER_DATA_MISSING:
							$this->echo_phrase("\r\n\r\n--- CLI_CONFIG_FILE_ERRORS: ---\r\n\r\n");
							foreach ($error['value'] AS $key => $value)
							{
								$this->echo_phrase("\r\n- $value -\r\n");
							}
							exit(6);

							break;
						default:
							break;
					}
				}
			}
		}

		if (!$output)
		{
			$this->echo_phrase(str_pad('  ', strlen(sprintf($this->phrase['core']['step_x'], $step)), ' ', STR_PAD_LEFT));
			$this->echo_phrase($this->phrase['upgrade']['no_message'] . "\r\n");
		}


		$nextstartat = $result['returnvalue']['startat'] ?? 0;

		if ($nextstartat)
		{
			$script->log_upgrade_step($step, $nextstartat);

			if ($recurse)
			{
				$args = $result['returnvalue'];
				do
				{
					$detailResult = $this->execute_step($step, $script, true, $args, false);
					$args = $detailResult['returnvalue'];
				}
				while (!empty($detailResult['returnvalue']['startat']));
			}
			else
			{
				return $result;
			}
		}
		else if (!empty($result['returnvalue']['prompt']))
		{
			$response = $this->prompt($result['returnvalue']['prompt']);
			$result = $this->execute_step($step, $script, true, ['response' => $response, 'startat' => $nextstartat]);
		}
		return $result;
	}


	//set the default data to match the the ajax defaults
	private function getData($data)
	{
		$defaultdata = [
			'startat'    => 0,
			'max'        => 0,
			'htmlsubmit' => false,
			'htmldata'   => [],
			'options'    => [],
		];

		return array_merge($defaultdata, $data);
	}

	/**
	 * Output a command prompt
	 *
	 * @var	string	String to echo
	 * @var	array		Accepted responses
	 *
	 * @return	string	Response value
	 */
	protected function prompt($value, $responses = null)
	{
		$count = 1;
		do
		{
			$this->echo_phrase("\r\n----------------------------------\r\n");
			$this->echo_phrase($this->convert_phrase($value));
			$this->echo_phrase('>');
			$response = trim(@fgets(STDIN));

			if ($count++ >= 10 )
			{
				//This should happen only when we're in a script.
				throw new Exception($this->phrase['install']['cli_no_valid_response'], 100);
			}
		}
		while (!empty($responses) AND is_array($responses) AND !in_array($response, $responses));

		$this->db->ping();

		return $response;
	}

	/**
	 * Fetches database/system configuration file when called from CLI
	 */
	private static function fetch_config_cli()
	{
		// parse the config file
		if (file_exists(DIR . '/' . self::$cli_config_file))
		{
			include(DIR . '/' . self::$cli_config_file);
		}
		else
		{
			if (defined('STDIN'))
			{
				return 5;
			}
			echo ('<br /><br /><strong>Configuration</strong>: core/includes/config_cli.php does not exist. Please fill out the data in config_cli.php.new and rename it to config_cli.php');
			return;
		}

		// TODO: this should be handled with an exception, the backend shouldn't produce output
		if (sizeof($config_cli) == 0)
		{
			// config.php exists, but does not define $config
			if (defined('STDIN'))
			{
				return 5;
			}
			echo('<br /><br /><strong>Configuration</strong>: core/includes/config_cli.php exists, but is not in the 3.6+ format. Please fill out the data in config_cli.php.new and rename it to config_cli.php.');
			return;
		}
		self::$config_cli = $config_cli;
	}

	/**
	 * Returns a by-reference the cli-config object
	 * @return array
	 */
	public static function &getConfigCLI()
	{
		if (!isset(self::$config_cli)) {
			self::fetch_config_cli();
		}

		return self::$config_cli;
	}

	/**
	 * Loads and setting from the command-line configuration file
	 */
	public function loadDSSettingsfromConfig()
	{
		$cliConfig = vB_Upgrade_Cli::getConfigCLI();

		if (!empty($cliConfig['cli']['settings']) AND is_array($cliConfig['cli']['settings']))
		{
			$datastore = vB::getDatastore();
			foreach ($cliConfig['cli']['settings'] as $name => $value)
			{
				$datastore->setOption($name,  $value);
			}
		}
	}

}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 116483 $
|| #######################################################################
\*=========================================================================*/
