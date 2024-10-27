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

class vB_Upgrade_Ajax extends vB_Upgrade_Abstract
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
	protected $identifier = 'ajax';

	/**
	* Limit Step Queries
	*
	* @var	boolean
	*/
	protected $limitqueries = true;

	/**
	* Customer Number
	*
	* @var	string
	*/
	private $custnumber = '';

	/**
	* Options for HTML display
	*
	* @var	array
	*/
	private $htmloptions = [];

	/**
	* Constructor.
	*
	* @param	vB_Registry	Reference to registry object
	* @var	string	Setup type - 'install' or 'upgrade'
	 */
/*
	public function __construct(&$registry, $phrases, $setuptype = 'upgrade', $version = null, $options = [])
	{
		parent::__construct($registry, $phrases, $setuptype);
	}
 */

	/**
	* Stuff to setup specific to Ajax upgrading - executes after upgrade has been established
	*
	*/
	protected function init($script, $process = true)
	{
		$this->registry->input->clean_array_gpc('r', [
			'step'       => vB_Cleaner::TYPE_UINT,
			'firstrun'   => vB_Cleaner::TYPE_BOOL,
		]);

		if (!defined('SKIPDB'))
		{
			if ($this->registry->GPC['firstrun'] OR $this->registry->GPC['step']  == 1)
			{
					vB_Upgrade::createAdminSession();
					require_once(DIR . '/includes/class_bitfield_builder.php');
					vB_Bitfield_Builder::save($this->db);
			}
		}

		parent::init($script);
		$this->custnumber = (strlen('76e681115a8f0ced2bcd2b9379a898db') == 32) ? '76e681115a8f0ced2bcd2b9379a898db' : md5(strtoupper('76e681115a8f0ced2bcd2b9379a898db'));

		$this->registry->input->clean_array_gpc('p', [
			'ajax'	=> vB_Cleaner::TYPE_BOOL,
			'jsfail' => vB_Cleaner::TYPE_BOOL,
		]);

		if ($this->registry->GPC['jsfail'])
		{
			$this->startup_errors[] = $this->phrase['core']['javascript_disabled'];
		}

		$this->registry->input->clean_array_gpc('c', [
			'bbcustomerid' => vB_Cleaner::TYPE_STR,
		]);

		//some things we won't be able to do until later but let's get as much of it initialized as early as possible.
		$this->htmloptions = $this->init_htmloptions();

		if ($this->registry->GPC['ajax'])
		{
			$this->registry->input->clean_array_gpc('p', [
				'step'       => vB_Cleaner::TYPE_UINT,
				'startat'    => vB_Cleaner::TYPE_INT,
				'max'        => vB_Cleaner::TYPE_INT,
				'version'    => vB_Cleaner::TYPE_NOHTML,
				'response'   => vB_Cleaner::TYPE_NOHTML,
				'checktable' => vB_Cleaner::TYPE_BOOL,
				'status'     => vB_Cleaner::TYPE_BOOL,
				'firstrun'   => vB_Cleaner::TYPE_BOOL,
				'only'       => vB_Cleaner::TYPE_BOOL,
				'htmlsubmit' => vB_Cleaner::TYPE_BOOL,
				'htmldata'   => vB_Cleaner::TYPE_ARRAY,
				'options'    => vB_Cleaner::TYPE_ARRAY,
			]);

			$this->registry->GPC['response'] = convert_urlencoded_unicode($this->registry->GPC['response']);
			$this->registry->GPC['htmldata'] = convert_urlencoded_unicode($this->registry->GPC['htmldata']);

			if ($this->registry->GPC['bbcustomerid'] != $this->custnumber)
			{
				$xml = new vB_XML_Builder_Ajax('text/xml', vB_Template_Runtime::fetchStyleVar('charset'));
					$xml->add_tag('error', $this->phrase['authenticate']['cust_num_incorrect']);
				$xml->print_xml();
			}

			if ($this->registry->GPC['status'])
			{
				$this->fetch_query_status();
			}

			$this->scriptinfo = [
				'version' => $this->fetch_short_version($this->registry->GPC['version']),
				'startat' => $this->registry->GPC['startat'],
				'step'    => $this->registry->GPC['step'],
				'only'    => $this->registry->GPC['only'],
			];
			$script = $this->load_script($this->scriptinfo['version']);

			$data = [
				'startat'    => $this->registry->GPC['startat'],
				'max'        => $this->registry->GPC['max'],
				'htmlsubmit' => $this->registry->GPC['htmlsubmit'],
				'htmldata'   => $this->registry->GPC['htmldata'],
				'options'    => $this->registry->GPC['options'],
			];

			//we should probably always set the key and set to null if it's not passed
			//but the lower down we don't set the key in the data array if the param
			//that the data array is replacing for this was null so we mimic that behavior
			//the steps that deal with the response *probably* work if we explicitly
			//set to null but not going to borrow trouble here.
			if ($this->registry->GPC_exists['response'])
			{
				$data['response'] = $this->registry->GPC['response'];
			}

			$this->process_step(
				$this->registry->GPC['version'],
				$this->registry->GPC['step'],
				$data,
				$this->registry->GPC['checktable'],
				$this->registry->GPC['firstrun'],
				$this->registry->GPC['only']
			);
		}
		else
		{
			$this->registry->input->clean_array_gpc('r', [
				'version'  => vB_Cleaner::TYPE_NOHTML,
				'startat'  => vB_Cleaner::TYPE_UINT,
				'step'     => vB_Cleaner::TYPE_UINT,
				'only'     => vB_Cleaner::TYPE_BOOL,
			]);

			//initializing script info is a mess.  We do it all over the place and it's not clear
			//what order and what context it's happening in.
			$version = $this->registry->GPC['version'];
			$scriptinfo = [
				'version' => $version,
				'startat' => $this->registry->GPC['startat'],
				'step'    => $this->registry->GPC['step'],
			];

			//check if we've successfully navigated the login process.
			if ($this->checklogin($this->registry->GPC['bbcustomerid']))
			{
				//in some cases version --primarily when resolving a version mismatch -- is already the long version and
				//thus won't be in the versions array.  That should be figured out, but not now.
				if (
					!empty($_REQUEST['version']) AND $version AND
					(!empty($this->versions[$version]) OR in_array($version, $this->endscripts, true))
				)
				{
					$this->scriptinfo = $scriptinfo;
				}

				$this->begin_upgrade($this->scriptinfo['version'], $this->registry->GPC['only']);
			}
			else
			{
				//the exact values we set here appear to be pretty irrelevant -- the keys just need populating
				//but trying to push the scriptinfo stuff up to the point were we figure it out once and
				//call it a day.
				$this->set_login_htmloptions($scriptinfo, $this->registry->GPC['only']);
			}

			$this->print_html();
		}
	}

	private function init_htmloptions()
	{
		$htmloptions = [];

		$corephrases = $this->phrase['core'];
		$finalversion = end($this->versions);

		//set up some values that depend on globals that are already determined early so that we
		//make sure they are set no matter how we process things.
		$htmloptions['finalversion'] = $finalversion;

		//various processed phrases.  Might be best to handle these seperately as a "phrasing" system
		$htmloptions['progress'] = $corephrases[$this->setuptype . '_progress'];
		$htmloptions['upgrading_to_x'] = sprintf($corephrases[$this->setuptype . 'ing_to_x'], $finalversion);
		$htmloptions['setuptype'] = sprintf($corephrases['vb_' . $this->setuptype . '_system'], $finalversion);
		$htmloptions['begin_setup'] = $corephrases['begin_' . $this->setuptype];
		$htmloptions['enter_system'] = $this->phrase['authenticate']['enter_' . $this->setuptype . '_system'];

		//just some blank values so that we can rely on the keys existing.
		$htmloptions['upgrademessage'] = '';
		$htmloptions['suggestconsole'] = '';
		$htmloptions['startup_errors'] = '';
		$htmloptions['startup_warnings'] = '';
		$htmloptions['status'] = '';

		$htmloptions['login'] = false;
		$htmloptions['loginerror'] = false;

		$htmloptions['mismatch'] = false;
		$htmloptions['processlog'] = false;
		$htmloptions['totalsteps'] = 0;

		return $htmloptions;
	}

	/**
	* Is this a big board?
	*
	* @return	mixed
	*/
	private function is_big_forum()
	{
		try
		{
			$postcount = $this->registry->db->query_first("
				SELECT COUNT(*) AS postcount
				FROM " . TABLE_PREFIX . "node"
			);
		}
		catch(Exception $e)
		{
			$postcount = $this->registry->db->query_first("
				SELECT SUM(replycount) AS postcount
				FROM " . TABLE_PREFIX . "forum"
			);
		}

		if ($postcount['postcount'] > 1200000)
		{
			return vb_number_format(
				$postcount['postcount'],
				0,
				false,
				vB_Template_Runtime::fetchStyleVar('decimalsep'),
				vB_Template_Runtime::fetchStyleVar('thousandsep')
			);
		}
		else
		{
			return false;
		}
	}

	protected function fetch_additional_scripts($startscript)
	{
		$result = [];
		$start = false;
		foreach ($this->endscripts AS $product)
		{
			if ($start)
			{
				$result[] = $product;
			}
			if ($product == $startscript)
			{
				$start = true;
			}
			if ($product == 'final')
			{
				return $result;
			}
		}

		return $result;
	}

	protected function upgrade_warning($from, $to)
	{
		$version = intval($from);

		if ($version < 5)
		{
			return '<br /><br />' . sprintf($this->phrase['core']['products_removed'], intval($to), $to);
		}

		return '';
	}

	/**
	* Begin Upgrade
	*
	* @var	string	script version
	* @var	bool		Only execute one script, must be specified in version=
	*/
	private function begin_upgrade($version, $only = false)
	{
		if ($this->registry->GPC['ajax'])
		{
			return;
		}
		$script = $this->load_script($version);

		//confirm is a boolean, but since its a submit button the value
		//needs to be the user label so it can't be counted on to be anything
		//specific
		$this->registry->input->clean_array_gpc('p', [
			'version' => vB_Cleaner::TYPE_NOHTML,
			'confirm' => vB_Cleaner::TYPE_NOHTML,
		]);

		if (!$this->verify_version($version, $script) AND !in_array($this->registry->GPC['version'], [$version, $this->registry->options['templateversion']]))
		{
			$this->htmloptions['mismatch'] = true;
			$this->htmloptions['version'] = $version;
			$this->htmloptions['step'] = ($this->scriptinfo['step'] ? $this->scriptinfo['step'] : 1);
			$this->htmloptions['startat'] = intval($this->scriptinfo['startat']);
			$this->htmloptions['only'] = $this->scriptinfo['only'];
		}
		else
		{
			if ($this->registry->GPC['version'])
			{
				$this->scriptinfo = [
					'version' => $this->fetch_short_version($this->registry->GPC['version']),
					'only'    => $only,
					'step'    => $this->scriptinfo['step'],
					'startat' => 0,
				];
				$script = $this->load_script($this->scriptinfo['version']);
			}

			$process = false;
			$totalsteps = ($script->stepcount ? $script->stepcount : 1);
			if ($this->scriptinfo['step'])
			{
				$totalsteps = $totalsteps - $this->scriptinfo['step'] + 1;
			}

			if (!$only)
			{
				foreach ($this->versions AS $version => $longversion)
				{
					$version = strval($version);
					if ($this->scriptinfo['version'] === $version)
					{
						$process = true;
						continue;
					}
					if ($process AND strpos($version, '*') === false)
					{
						$tempscript = $this->load_script($version);
						$totalsteps += $tempscript->stepcount ? $tempscript->stepcount : 1;
						unset($tempscript);
					}
				}
			}

			$addscripts = [];

			if (!in_array($script->SHORT_VERSION, $this->endscripts))
			{
				$addscripts = ['final'];
				if ($this->install_suite())
				{
					foreach($this->products AS $product)
					{
						$addscripts[] = $product;
					}
				}
				if ($this->setuptype == 'install')
				{
					$this->htmloptions['upgrademessage'] = sprintf($this->phrase['core']['press_button_to_begin_install'], end($this->versions));
				}
				else
				{
					$display_version = ($this->registry->GPC['version']) ? $script->LONG_VERSION : $this->registry->options['templateversion'];
					$this->htmloptions['upgrademessage'] = sprintf($this->phrase['core']['press_button_to_begin_upgrade'], $display_version, end($this->versions));
					$this->htmloptions['upgrademessage'] .= $this->upgrade_warning($this->registry->options['templateversion'], end($this->versions));
					$this->htmloptions['suggestconsole'] = $this->is_big_forum();
				}
			}
			else
			{
				$addscripts = $this->fetch_additional_scripts($script->SHORT_VERSION);
				$this->htmloptions['upgrademessage'] = $this->phrase['core']['press_button_to_begin_' . $script->SHORT_VERSION . '_upgrade'];
			}

			if (!$only)
			{
				foreach ($addscripts AS $scriptname)
				{
					$tempscript = $this->load_script($scriptname);
					$totalsteps += $tempscript->stepcount;
					unset($tempscript);
				}
			}

			$this->htmloptions['totalsteps'] = $totalsteps;
			$this->htmloptions['version'] = $this->scriptinfo['version'];
			$this->htmloptions['longversion'] = $script->LONG_VERSION;
			$this->htmloptions['step'] = ($this->scriptinfo['step'] ? $this->scriptinfo['step'] : 1);
			$this->htmloptions['startat'] = intval($this->scriptinfo['startat']);
			$this->htmloptions['only'] = $this->scriptinfo['only'];
			$this->htmloptions['status'] =
				$script->stepcount
					? $this->fetch_status($script->LONG_VERSION, $this->htmloptions['step'], $script->stepcount)
					: $this->fetch_status($script->LONG_VERSION);


			if ($this->startup_errors)
			{
				$this->htmloptions['startup_errors'] = '<li>' . implode('</li><li>', $this->startup_errors) . '</li>';
			}
			//don't show both errors and warnings -- don't show the warnings if the user hit confirm
			else if (!$this->registry->GPC['confirm'] AND $this->startup_warnings)
			{
				$this->htmloptions['startup_warnings'] = '<li>' . implode('</li><li>', $this->startup_warnings) . '</li>';
			}
			else
			{
				$this->htmloptions['processlog'] = true;
			}
		}
	}

	private function set_login_htmloptions($scriptinfo, $only)
	{
		$this->htmloptions['version'] = $scriptinfo['version'];
		$this->htmloptions['step'] = $scriptinfo['step'];
		$this->htmloptions['startat'] = $scriptinfo['startat'];
		$this->htmloptions['only'] = $only;
	}

	protected function show_errors_only()
	{
		$this->registry->input->clean_array_gpc('r', [
			'version'  => vB_Cleaner::TYPE_NOHTML,
			'startat'  => vB_Cleaner::TYPE_UINT,
			'step'     => vB_Cleaner::TYPE_UINT,
			'only'     => vB_Cleaner::TYPE_BOOL,
		]);

		$this->htmloptions = $this->init_htmloptions();

		$scriptinfo = [
			'version' => '',
			'step' => 0,
			'startat' => 0,
		];

		$this->set_login_htmloptions($scriptinfo, false);
		$this->htmloptions['startup_errors'] = '<li>' . implode('</li><li>', $this->startup_errors) . '</li>';
		$this->print_html();
	}

	/**
	* Process a step
	*
	* @param	string	Version
	* @param	int		Step
	* @param	array		data
	* 	'startat'
	* 	'htmlsubmit'
	* 	'htmldata'
	* 	'options'
	* 	'response' (optional);
	*
	* @param 	bool	Check table status
	* @param	bool	First run of the script
	*/
	private function process_step(
		$version,
		$step,
		$data,
		$checktable,
		$firstrun,
		$only
	)
	{
		$startat = $data['startat'];

		$script = $this->load_script($version);
		$startstep = $step ? $step : 1;
		$endstep = $script->stepcount;
		$xml = new vB_XML_Builder_Ajax('text/xml', vB_String::getCharset());

		$xml->print_xml_header();
		$xml->add_group('upgrade');

		$only = ($this->scriptinfo['only'] OR $only);

		try
		{
			$returnval = null;

			if ($endstep)
			{
				$result = $this->execute_step($startstep, $data, $script, $xml, $checktable);

				if ($result)
				{
					$returnval = $result['returnvalue'];
					if (!empty($returnval['startat']))
					{
						$script->log_upgrade_step($startstep, $returnval['startat'], $only);
						$nextstep = $startstep;
					}
					else if (!empty($returnval['prompt']) OR !empty($returnval['html']))
					{
						$nextstep = $startstep;
					}
					else
					{
						$script->log_upgrade_step($startstep, 0, $only);
						if ($startstep == $endstep)
						{
							$version = $script->SHORT_VERSION;
							$nextstep = 0;
						}
						else
						{
							$nextstep = $startstep + 1;
						}
					}
				}
				else
				{
					$nextstep = $startstep;
				}
			}
			else
			{
				$script->log_upgrade_step(0, 0, $only);
				$version = $script->SHORT_VERSION;
				$nextstep = 0;
				$xml->add_tag('empty_script', true); // Do not print NO_MESSAGE since this script actually had no steps to run.
			}

			if ($nextstep == 0)
			{
				if ($this->scriptinfo['version'] == 'final' OR $only)
				{
					$this->scriptinfo['version'] = 'done';
					$status = $this->phrase['core']['status_done'];
				}
				else
				{
					$this->scriptinfo = $this->get_upgrade_start($version);
					$nextscript = $this->load_script($this->scriptinfo['version']);
					if ($nextscript->stepcount)
					{
						$status = $this->fetch_status($nextscript->LONG_VERSION, 1, $nextscript->stepcount);
					}
					else
					{
						$status = $this->fetch_status($nextscript->LONG_VERSION);
					}
				}
				$nextstep = 1;
				$this->process_script_end();
			}
			else
			{
				$status = $this->fetch_status($script->LONG_VERSION, $nextstep, $endstep);
			}

			if ($returnval)
			{
				//it's not clear why we have the different options here or to what extent
				//order matters (I supect we only expect one to ever be set at a time).

				$fields = [];
				if (!empty($returnval['startat']))
				{
					$fields = [
						'startat' => 'v',
						'max' => 'v',
					];
				}
				else if (!empty($returnval['prompt']))
				{
					$fields = [
						'prompt' => 'v',
						'confirm' => 'v',
						'hidecancel' => 'b',
						'cancel' => 'v',
						'ok' => 'v',
						'title' => 'v',
						'reset' => 'b',
					];
				}
				else if (!empty($returnval['html']))
				{
					$fields = [
						'html' => 'v',
						'width' => 'v',
						'height' => 'v',
						'hidecancel' => 'b',
						'cancel' => 'v',
						'ok' => 'v',
						'title' => 'v',
						'reset' => 'b',
					];
				}

				$this->add_result_tags($xml, $fields, $returnval);
			}

			$xml->add_tag('status', $status);
			$xml->add_tag('longversion', $script->LONG_VERSION);
			$xml->add_tag('version', $this->scriptinfo['version']);
			$xml->add_tag('nextstep', $nextstep);
			if ($this->scriptinfo['version'] == 'done')
			{
				$xml->add_tag('upgradecomplete', $this->phrase['final'][$this->setuptype . '_complete']);
				$xml->add_tag('ok', $this->phrase['final']['goto_admincp']);
				$xml->add_tag('cancel', $this->phrase['final']['back_to_' . $this->setuptype]);
			}

			//we want to use the startat for the step we just processed rather than the result of the step
			//(IE the value we want back for the *next* step).  What we are saying is if this is the very
			//first time we call the first step of this version we want to put out a message (or if we just
			//restarted the upgrade script somewhere in the middle output the message for the first version
			//processed regardless of where we are).
			if ($firstrun OR ($startat == 0 AND $startstep == 1))
			{
				// Might be able to replace the array_merge with $this->endscripts --- Check
				$specialScript = (in_array($this->scriptinfo['version'], array_merge(array('install'), $this->endscripts)) AND
					$script->LONG_VERSION == $this->scriptinfo['version']);

				//if we don't have a phrase for this special version use the generic.  The front end is not happy if we
				//send back a blank string for this.
				if ($specialScript AND isset($this->phrase['core']['processing_' . $this->scriptinfo['version']]))
				{
					$xml->add_tag('upgradenotice', $this->phrase['core']['processing_' . $this->scriptinfo['version']]);
				}
				else
				{
					// <!-- "upgradeing" is a purposeful typo -->
					$xml->add_tag('upgradenotice', sprintf($this->phrase['core']['upgradeing_to_x'], $script->LONG_VERSION));
				}
			}

			$warnings = vB::getLoggedWarnings();
			if ($warnings)
			{
				$xml->add_group('warnings');
				foreach($warnings AS $warning)
				{
					$xml->add_group('warning');
					$xml->add_tag('description', $warning);
					$xml->close_group('warning');
				}
				$xml->close_group('warnings');
			}

			$xml->close_group('upgrade');
		}
		catch (vB_Exception_Api $e)
		{
			$xml->add_tag('version', $this->scriptinfo['version']);
			$xml->add_tag('step', $startstep);

			$xml->add_group('fatal_error');
			foreach($e->get_errors() AS $error)
			{
				$message = $this->render_phrase_array($error);
				$message = "Error $message on " .  $e->getFile() . ' : ' . $e->getLine();
				$xml->add_tag('description', $message);
			}
			$xml->close_group('fatal_error');
			$xml->close_group('upgrade');
		}
		catch (Throwable $e)
		{
			$xml->add_tag('version', $this->scriptinfo['version']);
			$xml->add_tag('step', $startstep);

			$xml->add_group('fatal_error');
			$xml->add_tag('description', $e->getMessage() . ' on ' .  $e->getFile() . ' : ' . $e->getLine() );
			$xml->close_group('fatal_error');
			$xml->close_group('upgrade');
		}

		$xml->print_xml_end();
	}

	private function add_result_tags($xml, $fields, $result)
	{
		// This is abstracting prior behavior to strip away cruft.
		// It's not remotely clear *why* we do this but we should really be always sending
		// these tags with empty/false values if we don't have anything.  But we need to
		// make sense of it first. (Really this should be JSON).
		//		'b' => boolean -- add "true" tag if value is true
		//		'v' => add tag with the value if the value is true (but actually we'll treat anything not b as v)

		foreach($fields AS $field => $type)
		{
			$value = $result[$field] ?? null;
			if ($type == 'b')
			{
				$value = boolval($value);
			}

			if ($value)
			{
				$xml->add_tag($field, $value);
			}
		}
	}

	/**
	* Is this a vB version step, or a cms/blog/install step
	*
	* @var string	Script Tag
	* @var int		Beginning step
	* @var int		End Step
	*
	* @return string
	*/
	private function fetch_status($script, $start = null, $end = null)
	{
		if (!$start)
		{
			return sprintf($this->phrase['core']['status_x'], $script);
		}
		else
		{
			//if we don't have a status message the front end breaks.  So let's go generic if
			//that happens.
			if (preg_match('#^\d#', $script) OR !isset($this->phrase['core']["status_{$script}_x_y"]))
			{
				return sprintf($this->phrase['core']['status_x_y_z'], $script, $start, $end);
			}
			else
			{
				return sprintf($this->phrase['core']["status_{$script}_x_y"], $start, $end);
			}
		}
	}

	/**
	* Execute a step
	*/
	private function execute_step($step, $data, $script, &$xml, $checktable)
	{
		$result = $script->execute_step($step, $checktable, $data);
		if ($result['message'])
		{
			foreach ($result['message'] AS $message)
			{
				$xml->add_tag('message', $message['value'], ['replace' => intval($message['replace'])]);
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
							$xml->add_tag('apperror', sprintf($this->phrase['core']['dberror_ajax'], $this->scriptinfo['version'], $step, $error['value']['errno'], $error['value']['error'], $error['value']['message']), ['type' => 'MYSQL_HALT']);
							return false;
							break;

						case vB_Upgrade_Version::PHP_TRIGGER_ERROR:
							$xml->add_tag('apperror', sprintf($this->phrase['core']['phperror_ajax'], $this->scriptinfo['version'], $step, $error['value']), ['type' => 'PHP_TRIGGER_ERROR']);
							return false;
							break;

						case vB_Upgrade_Version::APP_CREATE_TABLE_EXISTS:
							$xml->add_tag('apperror', sprintf($this->phrase['core']['tables_exist_ajax'], $error['value']), ['type' => 'APP_CREATE_TABLE_EXISTS']);
							return false;
							break;
					}
				}
			}
		}

		return $result;
	}

	/**
	* Display Login
	*
	* @param string $bbcustomerid -- the stored token.
	* @return	boolean true if we verified the user login (either from a prompt or a token).
	*/
	private function checklogin($bbcustomerid)
	{
		$proceed = false;

		//the stored customer number doens't match (maybe we don't have one)
		if ($bbcustomerid != $this->custnumber)
		{
			//the user tried to log in with the customer number
			if (isset($_POST['customerid']))
			{
				$this->registry->input->clean_array_gpc('p', [
					'customerid' => vB_Cleaner::TYPE_NOHTML,
				]);

				//we succeeded.  Set the token for next time and proceed.
				if (md5(strtoupper($this->registry->GPC['customerid'])) == $this->custnumber)
				{
					$port = (int) $_SERVER['SERVER_PORT'];
					$https = (!empty($_SERVER['HTTPS']) AND $_SERVER['HTTPS'] !== 'off');
					$secure = ($port == 443 OR $https);

					setcookie('bbcustomerid', $this->custnumber, 0, '/', '', $secure, true);
					$proceed = true;
				}
				//We failed.  Display the login form and show the login error
				else
				{
					$this->htmloptions['login'] = true;
					$this->htmloptions['loginerror'] = true;
				}
			}
			//We didn't try.  Show the login form but don't show the error.
			else
			{
				$this->htmloptions['login'] = true;
			}
		}
		//we have a store customernumber token, no need to bother the user
		else
		{
			$proceed = true;
		}

		return $proceed;
	}


	/**
	* Retrieve current MYSQL process list
	*
	*/
	protected function fetch_query_status()
	{
		$xml = new vB_XML_Builder_Ajax('text/xml', vB_Template_Runtime::fetchStyleVar('charset'));
		$xml->add_group('processes');
		$xml->add_tag('query_status', $this->phrase['core']['query_status_title']);

		$processes = $this->db->fetch_my_query_status();

		if (empty($processes))
		{
			$xml->add_tag('noprocess', $this->phrase['core']['no_processes_found']);
		}
		else
		{
			foreach ($processes AS $process)
			{
				$process['Info'] = preg_replace("/^(\s+)?### vBulletin Database Alter ###/s", "", $process['Info']);
				$totalseconds = intval($process['Time']);

				$hours = floor($totalseconds / 3600);
				$totalseconds -= $hours * 3600;

				$minutes = floor($totalseconds / 60);
				$totalseconds -= $minutes * 60;

				$seconds = $totalseconds;

				$phrase = construct_phrase(
					$this->phrase['core']['process_x_y_z'],
					str_pad($hours, 2, "0", STR_PAD_LEFT),
					str_pad($minutes, 2, "0", STR_PAD_LEFT),
					str_pad($seconds, 2, "0", STR_PAD_LEFT),
					htmlspecialchars_uni($process['State']),
					htmlspecialchars_uni($process['Info'])
				);
				$xml->add_tag('process', $phrase);
			}
		}

		$xml->close_group('processes');
		$xml->print_xml();
	}

	/**
	* Output page HTML
	*
	*/
	private function print_html()
	{
		header('Expires: ' . gmdate('D, d M Y H:i:s', TIMENOW) . ' GMT');
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s', TIMENOW) . ' GMT');

		$hiddenfields = '';
		if ($this->registry->GPC['version'])
		{
			$hiddenfields = "<input type=\"hidden\" name=\"version\" value=\"{$this->registry->GPC['version']}\" />";
		}
		$hiddenfields .= "
			<input type=\"hidden\" name=\"step\" value=\"{$this->registry->GPC['step']}\" />
			<input type=\"hidden\" name=\"startat\" value=\"{$this->registry->GPC['startat']}\" />
			<input type=\"hidden\" name=\"only\" value=\"{$this->registry->GPC['only']}\" />
		";

		//I don't think this can every be defined in the installer.  Looks like it may have been copy/pasted from elsewhere.
		if (defined('ADMIN_VERSION_VBULLETIN') )
		{
			$output_verions = ADMIN_VERSION_VBULLETIN;
		}
		else
		{
			if (!empty($this->registry->options['templateversion']))
			{
				$output_version = $this->registry->options['templateversion'];
			}
			else
			{
				$output_version = $this->htmloptions['finalversion'];
			}
		}

		?>
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
		<html xmlns="http://www.w3.org/1999/xhtml" dir="<?php echo vB_Template_Runtime::fetchStyleVar('textdirection'); ?>" lang="<?php echo vB_Template_Runtime::fetchStyleVar('languagecode'); ?>">
		<head>
			<meta http-equiv="Content-Type" content="text/html; charset=<?php echo vB_Template_Runtime::fetchStyleVar('charset'); ?>" />
			<meta http-equiv="Expires" content="Sat, 26 Jul 1997 05:00:00 GMT" />
			<meta http-equiv="Pragma" content="no-cache, must-revalidate" />
			<title><?php echo $this->htmloptions['setuptype']; ?></title>
			<link rel="stylesheet" href="<?php echo "../cpstyles/{$this->registry->options['cpstylefolder']}/controlpanel.css"; ?>" />
			<link rel="stylesheet" href="vbulletin-upgrade.css" />
			<style type="text/css">
				/*some variables to pass config information to the css */
				:root {
					--vb-left: <?php echo vB_Template_Runtime::fetchStyleVar('left') ?>;
					--vb-right: <?php echo vB_Template_Runtime::fetchStyleVar('right') ?>;
				}
			</style>
			<script type="text/javascript">
			<!--
				var IMGDIR_MISC = "../cpstyles/<?php echo $this->registry->options['cpstylefolder']; ?>";
				<?php
				$baseurl_core = '..';
				try
				{
					//If this is the first run, startat will not be set, and also there will be no datastore.
					if (!empty($this->scriptinfo['startat']))
					{
						if ($ds = vB::getDatastore())
						{
							$baseurl_core = $ds->getOption('bburl');
						}
					}
				}
				catch (Exception $e)
				{
					$baseurl_core = '..';
				}

				// I don't think the cookie script is used right now in the installer but if we don't include
				// it the cookie feature won't work and I'm concern about that introducing weird bugs.
 				?>
				var VERSION = "<?php echo $this->htmloptions['version']; ?>";
				var SCRIPTINFO = {
					version: "<?php echo $this->htmloptions['version']; ?>",
					startat: "<?php echo $this->htmloptions['startat']; ?>",
					step	 : "<?php echo $this->htmloptions['step']; ?>",
					only	 : "<?php echo $this->htmloptions['only']; ?>"
				};
				var TOTALSTEPS = <?php echo intval($this->htmloptions['totalsteps']); ?>;
				var ABORTMSG = "<?php echo $this->phrase['core']['status_aborted']; ?>";
				var SCRIPT_X_STEP_Y = "<?php echo $this->phrase['core']['script_x_step_y']; ?>";
				var SETUPTYPE = "<?php echo $this->setuptype; ?>";
				var SERVER_NO_RESPONSE = "<?php echo $this->phrase['core']['server_no_response']; ?>";
				var FATAL_ERROR_OCCURRED = "<?php echo $this->phrase['core']['fatal_error_occurred']; ?>";
			//-->
			</script>
			<script type="text/javascript" src="../clientscript/yui/yuiloader-dom-event/yuiloader-dom-event.js"></script>
			<script type="text/javascript" src="../clientscript/yui/connection/connection-min.js"></script>
			<script type="text/javascript" src="../../js/jquery/jquery-<?php echo JQUERY_VERSION; ?>.min.js"></script>
			<script type="text/javascript" src="../../js/jquery/js.cookie.min.js?v=<?php echo SIMPLE_VERSION; ?>"></script>
			<script type="text/javascript" src="../clientscript/vbulletin_global.js?v=<?php echo SIMPLE_VERSION; ?>"></script>
			<script type="text/javascript" src="../clientscript/vbulletin-core.js?v=<?php echo SIMPLE_VERSION; ?>"></script>
			<script type="text/javascript" src="vbulletin-upgrade.js?v=<?php echo SIMPLE_VERSION; ?>""></script>
		</head>
		<body>
		<?php echo $this->getPhraseDiv(); ?>
		<div id="vb_overlay_background" class="hidden"></div>
		<div id="acp-logo-bar" class="navbody floatcontainer">
			<div class="xml4">
				<ul>
					<li><?php echo $this->xml_versions['style']; ?></li>
					<li><?php echo $this->xml_versions['settings']; ?></li>
				</ul>
			</div>
			<div class="xml3">
				<ul>
					<li>vbulletin-style.xml:</li>
					<li>vbulletin-settings.xml:</li>
				</ul>
			</div>
			<div class="xml2">
				<ul>
					<li><?php echo $this->xml_versions['language']; ?></li>
					<li><?php echo $this->xml_versions['adminhelp']; ?></li>
				</ul>
			</div>
			<div class="xml1">
				<ul>
					<li>vbulletin-language.xml:</li>
					<li>vbulletin-adminhelp.xml:</li>
				</ul>
			</div>
			<div class="logo">
				<img src="../cpstyles/<?php echo $this->registry->options['cpstylefolder']; ?>/cp_logo.png" alt="" title="vBulletin 5 &copy; <?php echo date('Y'); ?>, MH Sub I, LLC dba vBulletin. All rights reserved." />
			</div>
			<div class="notice">
				<strong><?php echo $this->htmloptions['setuptype']; ?></strong><br />
				<?php echo $this->phrase['core']['may_take_some_time']; ?>
			</div>
		</div>

		<div id="all">
			<div class="tborder<?php if (!$this->htmloptions['startup_warnings']) { echo " hidden"; } ?>" id="startup_warnings">
				<div class="navbody messageheader"><?php echo $this->phrase['core']['startup_warnings']; ?></div>
				<div class="messagebody logincontrols">
					<?php echo $this->phrase['core']['startup_warnings_desc'] . ' ' . $this->phrase['core']['startup_warnings_instructions_ajax']; ?>
					<ul>
						<li class="hidden"></li>
						<?php echo $this->htmloptions['startup_warnings']; ?>
					</ul>
					<form class='buttons' action="<?php echo $this->setuptype;?>.php" method="post">
						<input class="button" type="submit" tabindex="1" name="" value="<?php echo $this->phrase['core']['refresh']; ?>" />
						<input class="button" type="submit" tabindex="1" name="confirm" value="<?php echo $this->phrase['core']['confirm']; ?>" />
						<?php echo $hiddenfields ?>
					</form>
				</div>
			</div>

			<div class="tborder<?php if (!$this->htmloptions['startup_errors']) { echo " hidden"; } ?>" id="startup_errors">
				<div class="navbody messageheader"><?php echo $this->phrase['core']['startup_errors']; ?></div>
				<div class="messagebody logincontrols">
					<?php echo $this->phrase['core']['startup_errors_desc']; ?>
					<ul>
						<li class="hidden"></li>
						<?php echo $this->htmloptions['startup_errors']; ?>
					</ul>
				</div>
			</div>
			<div class="tborder<?php if (!$this->htmloptions['login']) { echo " hidden"; } ?>" id="authenticate">
				<div class="navbody messageheader"><?php echo $this->phrase['authenticate']['enter_cust_num']; ?></div>
				<div class="messagebody logincontrols">
					<?php echo $this->phrase['authenticate']['cust_num_explanation']; ?>
					<form action="<?php echo $this->setuptype; ?>.php" method="post">
						<input type="text" tabindex="1" value="" name="customerid" id="customerid" />
						<?php if ($this->htmloptions['loginerror']) { ?><div id="customerid_error" class="navbody"><?php echo $this->phrase['authenticate']['cust_num_incorrect']; ?></div><?php } ?>
						<input class="button" type="submit" tabindex="1" accesskey="s" id="authsubmit" value="<?php echo $this->htmloptions['enter_system'] ?>" />
						<?php echo $hiddenfields ?>
					</form>
				</div>
			</div>
			<div class="tborder<?php if (!$this->htmloptions['mismatch']) { echo " hidden"; } ?>" id="mismatch">
				<div class="navbody messageheader"><?php echo $this->phrase['core']['version_mismatch']; ?></div>
				<div class="messagebody logincontrols">
					<?php
					if ($this->htmloptions['mismatch'])
					{
						$currentversion = $this->registry->options['templateversion'];

						$lastupgrade = $this->htmloptions['version'];
						if ($lastupgrade == 'final')
						{
							end($this->versions);
							$lastupgrade = key($this->versions);
						}

						//not sure why we do this for the lastupgrade and not the current upgrade especially since we
						//don't do it for display but refactoring from some existing behavior
						$lastupgradedisplay = $this->versions[$lastupgrade];

						echo construct_phrase($this->phrase['core']['wrong_version'], $lastupgradedisplay, $currentversion);
						echo '
							<form action="upgrade.php" method="post">
								<input type="hidden" name="mismatch" value="1" />
								<label for="version1"><input id="version1" type="radio" name="version" value="' . htmlspecialchars_uni($lastupgrade) .  '" />' .
									construct_phrase($this->phrase['core']['upgrade_from_x'], $lastupgradedisplay) . '</label>
								<label for="version2"><input id="version2" type="radio" name="version" value="' . htmlspecialchars_uni($currentversion) . '" />' .
									construct_phrase($this->phrase['core']['upgrade_from_x'], $currentversion) . '</label>
						<input class="button" type="submit" tabindex="1" accesskey="s" name="" value="' . $this->htmloptions['enter_system'] . '" /> ' . "\n" .
						$hiddenfields . "\n" .
						'</form>' . "\n";
					}
					?>
				</div>
			</div>

			<div class="tborder<?php if (!$this->htmloptions['processlog']) { echo " hidden"; } ?>" id="progressbox">
				<div class="navbody messageheader"><?php echo $this->htmloptions['upgrading_to_x']; ?></div>
				<div class="messagebody logincontrols">
					<div class="hidden" id="progresssection">
						<div id="progressmessage"><?php echo $this->htmloptions['status'] ?></div>
						<div id="progressbar_container">
							<div id="progressbar"></div>
							<div id="percentageout"></div>
						</div>
						<div id="progressnotice"></div>
						<div class="buttons floatcontainer">
							<img id="upgradeprogress" class="hidden" src="../cpstyles/<?php echo $this->registry->options['cpstylefolder']; ?>/progress.gif" alt="" />
							<input class="button" type="button" id="showdetails" tabindex="1" name="" value="<?php echo $this->phrase['core']['show_details'] ?>" />
							<input class="button hidden" type="button" id="hidedetails" tabindex="1" name="" value="<?php echo $this->phrase['core']['hide_details'] ?>" />
							<input class="button hidden" type="button" id="admincp" tabindex="1" name="" value="<?php echo $this->phrase['core']['admin_cp'] ?>" />
							<input class="button hidden" type="button" id="querystatus" tabindex="1" name="" value="<?php echo $this->phrase['core']['query_status'] ?>" />
						</div>
					</div>
					<div id="beginsection">
						<form action="<?php echo $this->setuptype; ?>.php" id="optionsform" method="post">
							<?php if ($this->htmloptions['suggestconsole']) { echo '<div class="consolemsg">' . construct_phrase($this->phrase['core']['console_upgrade_steps'], $this->htmloptions['suggestconsole']) . '</div>'; } ?>
							<p><?php echo $this->htmloptions['upgrademessage']; ?></p>
							<input type="hidden" name="jsfail" value="1" />
							<div class="hidden" id="optionsbox">
								<p>
									<?php echo $this->phrase['core']['merge_template_updates']; ?>:
									<?php echo $this->phrase['vbphrase']['yes'] ?> <input id="rb_merge1" type="radio" name="options[skiptemplatemerge]" value="0" checked="checked" />
									<?php echo $this->phrase['vbphrase']['no'] ?> <input id="rb_merge2" type="radio" name="options[skiptemplatemerge]" value="1" />
								</p>
							</div>
							<input class="button" type="button" id="beginupgrade" tabindex="1" value="<?php echo $this->htmloptions['begin_setup'] ?>" />
							<input class="button" type="button" id="options" tabindex ="2" value="<?php echo $this->phrase['core']['options'] ?>" />
					</form>
					</div>
				</div>
			</div>

			<div id="detailbox" class="tborder hidden">
				<div class="navbody messageheader"><?php echo $this->htmloptions['progress']; ?></div>
				<div id="mainmessage" class="messagebody logincontrols"></div>
				<div class="status">
					<span id="statusmessage"><?php echo $this->htmloptions['status'] ?></span>
				</div>
			</div>

			<div class="tborder hidden" id="prompt">
				<div class="navbody messageheader" id="prompttitle"><?php echo $this->phrase['core']['action_required']; ?></div>
				<div class="messagebody logincontrols">
					<div id="promptmessage"></div>
					<form action="upgrade.php" method="post" id="promptform">
						<input type="text" tabindex="1" value="" name="promptresponse" id="promptresponse" />
						<div class="submit">
							<input class="button" type="submit" tabindex="1" id="promptsubmit" value="<?php echo $this->phrase['vbphrase']['ok']; ?>" />
							<input class="button hidden" type="reset" tabindex="1" id="promptreset" value="<?php echo $this->phrase['vbphrase']['reset']; ?>" />
							<input class="button hidden" type="button" tabindex="1" id="promptcancel" value="<?php echo $this->phrase['vbphrase']['cancel']; ?>" />
						</div>
					</form>
				</div>
			</div>

			<div class="tborder hidden" id="confirm">
				<div class="navbody messageheader" id="confirmtitle"><?php echo $this->phrase['core']['action_required']; ?></div>
				<div class="messagebody logincontrols">
					<form action="<?php echo $this->setuptype; ?>.php" method="post" id="confirmform">
						<div id="confirmmessage"></div>
						<div class="submit">
							<input class="button" type="submit" name="submit" tabindex="1" accesskey="s" id="confirmok" value="<?php echo $this->phrase['vbphrase']['ok']; ?>" />
							<input class="button hidden" type="reset" name="reset" tabindex="1" id="confirmreset" value="<?php echo $this->phrase['vbphrase']['reset']; ?>" />
							<input class="button" type="button" name="cancel" tabindex="1" accesskey="s" id="confirmcancel" value="<?php echo $this->phrase['vbphrase']['cancel']; ?>" />
						</div>
					</form>
				</div>
			</div>

		</div>

		<p align="center"><a href="https://www.vbulletin.com/" target="_blank" class="copyright">
		<?php echo construct_phrase($this->phrase['vbphrase']['vbulletin_copyright_orig'], $output_version, date('Y')); ?>
		</a></p>
		</body>
		</html>
		<?php
	}

	private function getPhraseDiv()
	{
		$phrases = [
			'core' => [
				'version_x_step_y_z',
				'unexpected_text',
			],
			'upgrade' => [
				'no_message',
			],
		];

		$phrasestrings = [];
		foreach($phrases AS $group => $values)
		{
			foreach($values AS $phrase)
			{
				$phrasestrings[] = 'data-' . $phrase . '="' . htmlspecialchars($this->phrase[$group][$phrase]) . '"';
			}
		}

		return '<div class="js-phrase-data" style="display:none" ' . "\n" . implode("\n", $phrasestrings) . "\n" . '></div>';
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 114015 $
|| ####################################################################
\*======================================================================*/
