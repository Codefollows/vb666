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

class vB_Upgrade_530a1 extends vB_Upgrade_Version
{
	/**
	 * Add ishiddeninput column to widgetdefinition
	 */
	public function step_1()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'widgetdefinition', 1, 1),
			'widgetdefinition',
			'ishiddeninput',
			'tinyint',
			array('length' => 4, 'null' => false, 'default' => '0')
		);
	}

	public function step_2()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'userloginmfa'),
			"CREATE TABLE " . TABLE_PREFIX . "userloginmfa (
				userid INT UNSIGNED NOT NULL,
				enabled TINYINT NOT NULL,
				secret VARCHAR(255) NOT NULL,
				dateline INT NOT NULL,
				PRIMARY KEY (userid)
			) ENGINE = " . $this->hightrafficengine . "
			",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_3()
	{
		// hard coded in vbulletin-routes.xml
		$assertor = vB::getDbAssertor();
		$row = $assertor->getRow('routenew', array('name' => 'settings'));

		if (strpos($row['regex'], '|security') === false)
		{
			$regex = str_replace('|notifications', '|notifications|security', $row['regex']);
			$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'routenew'));
			$assertor->assertQuery('routenew',
				array(
					vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
					vB_dB_Query::CONDITIONS_KEY => array('routeid' => $row['routeid']),
					'regex' => $regex
				)
			);
		}
		else
		{
			// we're okay.
			$this->skip_message();
			return;
		}
	}
}

class vB_Upgrade_530b1 extends vB_Upgrade_Version
{
	/**
	 * Update site navbars (calendar subnavbar added to home tab in 5.3.0)
	 */
	public function step_1()
	{
		$this->syncNavbars();
	}
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 112192 $
|| ####################################################################
\*======================================================================*/

class vB_Upgrade_530b2 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$assertor = vB::getDbAssertor();
		$row = $assertor->getRow('routenew', array('name' => 'settings'));

		//correct the name of the tab for account security
		if (strpos($row['regex'], '|mfa') !== false)
		{
			//handle the possibility that somebody ran the previous upgrade
			//both before and after we changed the name
			if (strpos($row['regex'], '|security') === false)
			{
				$regex = str_replace('|mfa', '|security', $row['regex']);
			}
			else
			{
				$regex = str_replace('|mfa', '', $row['regex']);
			}

			$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'routenew'));
			$assertor->assertQuery('routenew',
				array(
					vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
					vB_dB_Query::CONDITIONS_KEY => array('routeid' => $row['routeid']),
					'regex' => $regex
				)
			);
		}
		else
		{
			// we're okay.
			$this->skip_message();
			return;
		}
	}
}

class vB_Upgrade_531a2 extends vB_Upgrade_Version
{
	/**
	 * Add field for event date format
	 */
	public function step_1()
	{
		// this matches the code in sync_database to add this field
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'language', 1, 1),
			'language',
			'eventdateformatoverride',
			'VARCHAR',
			array('length' => 50, 'null' => false, 'default' => '')
		);
	}

	/**
	 * Set language.eventdateformatoverride if needed
	 */
	public function step_2()
	{
		// For each installed language, check if locale is set, and if so,
		// populate eventdateformatoverride with a default value if empty,
		// since if it is left blank when locale is set, the event date
		// won't show at all. When locale is set, we require that all the
		// overrides be set as well.
		// This will essentially be any language that existed before this
		// version and already had locale set.

		$assertor = vB::getDbAssertor();

		// Get languages where locale is defined and eventdateformatoverride is empty
		$languages = $assertor->getRows('language', array(
			vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
			vB_dB_Query::COLUMNS_KEY => array(
				'languageid',
				'title',
				'locale',
				'eventdateformatoverride',
			),
			vB_dB_Query::CONDITIONS_KEY => array(
				array(
					'field'    => 'locale',
					'value'    => '',
					'operator' => vB_dB_Query::OPERATOR_NE
				),
				array(
					'field'    => 'eventdateformatoverride',
					'value'    => '',
					'operator' => vB_dB_Query::OPERATOR_EQ
				),
			),
		));

		$count = count($languages);
		$index = 1;

		foreach ($languages AS $language)
		{
			// Default format if locale is specified: %#d %b
			$values = array('eventdateformatoverride' => '%#d %b');
			$condition = array('languageid' => $language['languageid']);
			$assertor->update('language', $values, $condition);

			$this->show_message(sprintf($this->phrase['version']['531a2']['setting_default_eventdateformatoverride_for_x_y_of_z'], $language['title'], $index, $count));
			++$index;
		}

		if ($count < 1)
		{
			$this->skip_message();
		}
	}

	public function step_3()
	{
		vB::getDatastore()->delete('vBUgChannelAccess');
		$this->show_message(sprintf($this->phrase['core']['remove_datastore_x'], 'vBUgChannelAccess'));
	}
}

class vB_Upgrade_531a3 extends vB_Upgrade_Version
{
	/*
	#############################################################
	Steps 1 to 4 replicate field changes made in vB3 & vB4
	to store IPv6 Addresses, they are replicated here to keep
	the	tables in sync when upgrading the database to vB5.
	#############################################################
	*/

	/**
	 * Replicates 4.2.5 Beta 2 Step 1
	 * Change host (ip address) field to varchar 45 for IPv6
	 */
	public function step_1()
	{
		if ($this->field_exists('session', 'host'))
		{
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'session', 1, 1),
				"ALTER TABLE " . TABLE_PREFIX . "session CHANGE host host VARCHAR(45) NOT NULL DEFAULT ''"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	 * Replicates 4.2.5 Beta 2 Step 3
	 * Change ip address field to varchar 45 for IPv6
	 */
	public function step_2()
	{
		if ($this->field_exists('apiclient', 'initialipaddress'))
		{
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'apiclient', 1, 1),
				"ALTER TABLE " . TABLE_PREFIX . "apiclient CHANGE initialipaddress initialipaddress VARCHAR(45) NOT NULL DEFAULT ''"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	 * Replicates 4.2.5 Beta 2 Step 4
	 * Change ip address field to varchar 45 for IPv6
	 */
	public function step_3()
	{
		if ($this->field_exists('apilog', 'ipaddress'))
		{
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'apilog', 1, 1),
				"ALTER TABLE " . TABLE_PREFIX . "apilog CHANGE ipaddress ipaddress VARCHAR(45) NOT NULL DEFAULT ''"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	 * Replicates 4.2.5 Beta 2 Step 5
	 * Change ip address field to varchar 45 for IPv6
	 */
	public function step_4()
	{
		if ($this->field_exists('searchlog', 'ipaddress'))
		{
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'searchlog', 1, 1),
				"ALTER TABLE " . TABLE_PREFIX . "searchlog CHANGE ipaddress ipaddress VARCHAR(45) NOT NULL DEFAULT ''"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_5()
	{
		$assertor = vB::getDbAssertor();
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'routenew'));
		$assertor->assertQuery('routenew',
			array(
				vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
				vB_dB_Query::CONDITIONS_KEY => array('guid' => 'vbulletin-4ecbdacd6aac05.50909987'),
				'regex' => 'node/(?P<nodeid>[0-9]+)(?:/contentpage(?P<contentpagenum>[0-9]+))?(?:/page(?P<pagenum>[0-9]+))?',
				'arguments' => 'a:3:{s:6:"nodeid";s:7:"$nodeid";s:7:"pagenum";s:8:"$pagenum";s:14:"contentpagenum";s:15:"$contentpagenum";}',
			)
		);
	}

	public function step_6()
	{
		$assertor = vB::getDbAssertor();
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'routenew'));

		$route = $assertor->getRow('routenew', array('name' => 'settings'));
		if ($route)
		{
			$assertor->assertQuery('routenew',
				array(
					vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
					vB_dB_Query::CONDITIONS_KEY => array('name' => 'settings'),
					'regex' => $route['prefix'] . '(/(?P<tab>profile|account|privacy|notifications|security|subscriptions))?',
				)
			);
		}
	}

	public function step_7()
	{
		//unescaping the data multiple times will be bad and there is no
		//good way to detect that.
		if ($this->iRan(__FUNCTION__))
		{
			return;
		}

		$assertor = vB::getDbAssertor();
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'site'));

		// Get site's current navbar data
		$site = $assertor->getRow('vBForum:site', array('siteid' => 1));

		$headernavbar = @unserialize($site['headernavbar']);
		foreach ((array)$headernavbar AS $key => $currentitem)
		{
			$headernavbar[$key]['url'] = vB_String::unHtmlSpecialChars($headernavbar[$key]['url']);
			// We have the tab, check for subnavs of the tab
			foreach ($currentitem['subnav'] ?? [] AS $subkey => $currentsubitem)
			{
				$headernavbar[$key]['subnav'][$subkey]['url'] = vB_String::unHtmlSpecialChars($headernavbar[$key]['subnav'][$subkey]['url']);
			}
		}

		$assertor->update('vBForum:site',
			array(
				'headernavbar' => serialize($headernavbar),
			),
			array(
				'siteid' => 1,
			)
		);
	}

	public function step_8()
	{
		//unescaping the data multiple times will be bad and there is no
		//good way to detect that.
		if ($this->iRan(__FUNCTION__))
		{
			return;
		}

		$assertor = vB::getDbAssertor();
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'site'));

		// Get site's current navbar data
		$site = $assertor->getRow('vBForum:site', array('siteid' => 1));

		$footernavbar = @unserialize($site['footernavbar']);
		foreach ((array)$footernavbar AS $key => $currentitem)
		{
			$footernavbar[$key]['url'] = vB_String::unHtmlSpecialChars($footernavbar[$key]['url']);
		}

		$assertor->update('vBForum:site',
			array(
				'footernavbar' => serialize($footernavbar),
			),
			array(
				'siteid' => 1,
			)
		);
	}

	public function step_9()
	{
		// Place holder to allow iRan() to work properly, as the last step gets recorded as step '0' in the upgrade log for CLI upgrade.
		$this->skip_message();
		return;
	}
}

class vB_Upgrade_532a1 extends vB_Upgrade_Version
{
	/**
	 * Step 1 Convert Latest Blog Comments and Latest Group Topics modules
	 * to search modules (VBV-16936)
	 */
	public function step_1()
	{
		$assertor = vB::getDbAssertor();

		$guids = array(
			'vbulletin-widget_blogsidebar-4eb423cfd6dea7.34930851',
			'vbulletin-widget_sgsidebar-4eb423cfd6dea7.34930861',
		);
		$oldWidgets = $assertor->getRows('widget', array('guid' => $guids));

		$searchWidget = $assertor->getRow('widget', array('guid' => 'vbulletin-widget_search-4eb423cfd6a5f3.08329785'));

		$updated = false;

		// matches the new adminconfig value used in vbulletin-pagetemplates.xml for the
		// search modules that replace the two old sidebar modules.
		// copying the whole thing here to better avoid typos and make it easier to keep the
		// two in sync, at the expense of a couple of unserialize calls.
		$newAdminConfigs = array(
			'vbulletin-widget_blogsidebar-4eb423cfd6dea7.34930851' => unserialize('a:3:{s:11:"searchTitle";s:20:"Latest Blog Comments";s:14:"resultsPerPage";s:1:"3";s:10:"searchJSON";s:157:"{"date":{"from":"30"},"sort":{"created":"desc"},"exclude_type":["vBForum_PrivateMessage"],"reply_only":"1","channelguid":"vbulletin-4ecbdf567f3a38.99555305"}";}'),
			'vbulletin-widget_sgsidebar-4eb423cfd6dea7.34930861' => unserialize('a:3:{s:11:"searchTitle";s:19:"Latest Group Topics";s:14:"resultsPerPage";s:1:"5";s:10:"searchJSON";s:159:"{"date":{"from":"30"},"sort":{"created":"desc"},"exclude_type":["vBForum_PrivateMessage"],"starter_only":"1","channelguid":"vbulletin-4ecbdf567f3a38.99555306"}";}'),
		);

		foreach ($oldWidgets AS $oldWidget)
		{
			$oldWidgetId = $oldWidget['widgetid'];

			// convert instances of the old widgets to instances of the search widget
			$oldInstances = $assertor->getRows('widgetinstance', array('widgetid' => $oldWidgetId));
			foreach ($oldInstances AS $oldInstance)
			{
				// change widgetid to the search widget
				$values = array('widgetid' => $searchWidget['widgetid']);
				$conditions = array('widgetinstanceid' => $oldInstance['widgetinstanceid']);

				// change searchJSON to the new default
				$adminconfig = array();
				if (!empty($oldInstance['adminconfig']))
				{
					$temp = unserialize($oldInstance['adminconfig']);
					if ($temp)
					{
						$adminconfig = $temp;
					}
				}
				$adminconfig['searchJSON'] = $newAdminConfigs[$oldWidget['guid']]['searchJSON'];
				// add to array for update
				$values['adminconfig'] = serialize($adminconfig);

				// we will leave the other config items alone (module title, perpage setting, etc.)
				// this works out, since the two old modules actually used the same config settings
				// with the same names as the search module does.

				// run the update
				$assertor->update('widgetinstance', $values, $conditions);
			}

			// delete the old widget & widget definition records
			$assertor->delete('widget', array('widgetid' => $oldWidget['widgetid']));
			$assertor->delete('widgetdefinition', array('widgetid' => $oldWidget['widgetid']));

			$updated = true;
		}

		if ($updated)
		{
			$this->show_message($this->phrase['version']['532a1']['converting_blog_and_group_sidebar_modules_to_search_modules']);
		}
		else
		{
			$this->skip_message();
		}
	}
}

class vB_Upgrade_532a3 extends vB_Upgrade_Version
{
	// Add apiclient_devicetoken table.
	public function step_1()
	{
		if (!$this->tableExists('apiclient_devicetoken'))
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'apiclient_devicetoken'),
				"
					CREATE TABLE " . TABLE_PREFIX . "apiclient_devicetoken (
						apiclientid INT UNSIGNED NOT NULL DEFAULT '0',
						userid INT UNSIGNED NOT NULL DEFAULT '0',
						devicetoken VARCHAR(191) NOT NULL DEFAULT '',
						PRIMARY KEY  (apiclientid),
						INDEX (userid)
					) ENGINE = " . $this->hightrafficengine . "
				",
				self::MYSQL_ERROR_TABLE_EXISTS
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	// Add fcmessage table.
	public function step_2()
	{
		if (!$this->tableExists('fcmessage'))
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'fcmessage'),
				"
					CREATE TABLE " . TABLE_PREFIX . "fcmessage (
						messageid                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
						message_data                VARCHAR(2048) NOT NULL DEFAULT '',
						message_hash                CHAR(32) NULL DEFAULT NULL,
						PRIMARY KEY (messageid),
						UNIQUE KEY message_hash (message_hash)
					) ENGINE = " . $this->hightrafficengine . "
				",
				self::MYSQL_ERROR_TABLE_EXISTS
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	// Add fcmessage_queue table.
	public function step_3()
	{
		if (!$this->tableExists('fcmessage_queue'))
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'fcmessage_queue'),
				"
					CREATE TABLE " . TABLE_PREFIX . "fcmessage_queue (
						recipient_apiclientid       INT UNSIGNED NOT NULL DEFAULT '0',
						messageid                   INT UNSIGNED NOT NULL DEFAULT '0',
						retryafter                  INT UNSIGNED NOT NULL DEFAULT '0',
						retryafterheader            INT UNSIGNED NOT NULL DEFAULT '0',
						retries						INT UNSIGNED NOT NULL DEFAULT '0',
						status                      ENUM('ready', 'processing') NOT NULL DEFAULT 'ready',
						UNIQUE KEY guid  (recipient_apiclientid, messageid),
						KEY id_status (messageid, status)
					) ENGINE = " . $this->hightrafficengine . "
				",
				self::MYSQL_ERROR_TABLE_EXISTS
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	// add fcmqueue cron
	public function step_4()
	{
		$this->add_cronjob(
			array(
				'varname'  => 'fcmqueue',
				'nextrun'  => 0,
				'weekday'  => -1,
				'day'      => -1,
				'hour'     => -1,
				'minute'   => 'a:2:{i:0;i:0;i:1;i:30;}',
				'filename' => './includes/cron/fcmqueue.php',
				'loglevel' => 1,
				'volatile' => 1,
				'product'  => 'vbulletin'
			)
		);
	}
}

class vB_Upgrade_532a4 extends vB_Upgrade_Version
{
	/**
	 * Add field for datetime picker format
	 */
	public function step_1()
	{
		// this matches the code in sync_database to add this field
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'language', 1, 1),
			'language',
			'pickerdateformatoverride',
			'VARCHAR',
			array('length' => 50, 'null' => false, 'default' => '')
		);
	}

	/**
	 * Populate language.pickerdateformatoverride if needed
	 */
	public function step_2()
	{
		// For each installed language, check if locale is set, and if so,
		// populate pickerdateformatoverride with a default value if empty,
		// since if it is left blank when locale is set, the picker date/time
		// won't show at all. When locale is set, we require that all the
		// overrides be set as well.
		// This will essentially be any language that existed before this
		// version and already had locale set.

		$assertor = vB::getDbAssertor();

		// Get languages where locale is defined and eventdateformatoverride is empty
		$languages = $assertor->getRows('language', array(
			vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
			vB_dB_Query::COLUMNS_KEY => array(
				'languageid',
				'title',
				'locale',
				'pickerdateformatoverride',
			),
			vB_dB_Query::CONDITIONS_KEY => array(
				array(
					'field'    => 'locale',
					'value'    => '',
					'operator' => vB_dB_Query::OPERATOR_NE
				),
				array(
					'field'    => 'pickerdateformatoverride',
					'value'    => '',
					'operator' => vB_dB_Query::OPERATOR_EQ
				),
			),
		));

		$count = count($languages);
		$index = 1;

		foreach ($languages AS $language)
		{
			// Default format if locale is specified: d-m-Y H:i
			$values = array('pickerdateformatoverride' => 'd-m-Y H:i');
			$condition = array('languageid' => $language['languageid']);
			$assertor->update('language', $values, $condition);

			$this->show_message(sprintf($this->phrase['version']['532a4']['setting_default_pickerdateformatoverride_for_x_y_of_z'], $language['title'], $index, $count));
			++$index;
		}

		if ($count < 1)
		{
			$this->skip_message();
		}
	}

	public function step_3()
	{
		$this->show_message($this->phrase['version']['532a4']['rebuild_prefix_datastore']);
		//the datastore format has changed, make sure we have the correct version
		vB_Library::instance('prefix')->buildDatastore();
	}

	/**
	 * Add widgetdefinition.descriptionphrase
	 */
	public function step_4()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'widgetdefinition', 1, 1),
			'widgetdefinition',
			'descriptionphrase',
			'VARCHAR',
			array('length' => 250, 'null' => false, 'default' => '')
		);
		$this->long_next_step();
	}

	public function step_5()
	{
		$this->add_index(
			sprintf($this->phrase['core']['create_index_x_on_y'], 'created', TABLE_PREFIX . 'node'),
			'node',
			'created',
			array('created')
		);
		$this->long_next_step();
	}

	public function step_6()
	{
		$this->add_index(
			sprintf($this->phrase['core']['create_index_x_on_y'], 'totalcount', TABLE_PREFIX . 'node'),
			'node',
			'totalcount',
			array('totalcount')
		);
		$this->long_next_step();
	}

	public function step_7()
	{
		$this->add_index(
			sprintf($this->phrase['core']['create_index_x_on_y'], 'joindate', TABLE_PREFIX . 'user'),
			'user',
			'joindate',
			array('joindate')
		);
	}

	// update fcmqueue cron's minute field
	public function step_8()
	{
		$assertor = vB::getDbAssertor();
		$check = $assertor->getRow('cron',
			array(
				'varname' => "fcmqueue",
			)
		);
		/*
			The old minute field was supposed to be:
			'a:12:{i:0;i:0;i:1;i:5;i:2;i:10;i:3;i:15;i:4;i:20;i:5;i:25;i:6;i:30;i:7;i:35;i:8;i:40;i:9;i:45;i:10;i:50;i:11;i:55;}'
			but turns out the column is only 100 chars, so it was cut off. Furthermore after the demo we decided to run this
			cron every 30min instead of every 5min.
		 */


		if (!empty($check) AND (strpos($check['minute'], 'a:12') === 0 OR $check['hour'] != -1))
		{
			$this->show_message(sprintf($this->phrase['core']['altering_x_table'], 'cron', 1, 1));
			$newMinute = 'a:2:{i:0;i:0;i:1;i:30;}';
			$assertor->update(
				'cron',
				array( // value
					'minute'  => $newMinute,
					'hour' => -1,
				),
				array( // condition
					'varname' => 'fcmqueue',
				)
			);
		}
		else
		{
			$this->skip_message();
		}
	}
}

class vB_Upgrade_533a1 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->add_index(
			sprintf($this->phrase['core']['create_index_x_on_y'], 'infractednodeid', TABLE_PREFIX . 'infraction'),
			'infraction',
			'infractednodeid',
			array('infractednodeid')
		);
	}

	public function step_2()
	{
		$this->show_message(sprintf($this->phrase['core']['altering_x_table'], 'infraction', 1, 1));

		$db = vB::getDbAssertor();
		$db->assertQuery('vBInstall:updateOrphanInfractions');
	}

	public function step_3()
	{
		$this->show_message(sprintf($this->phrase['core']['altering_x_table'], 'channel', 1, 1));

		$db = vB::getDbAssertor();

		$nodeids = $db->getColumn('vBForum:channel', 'nodeid', array('guid' => array('vbulletin-4ecbdf567f3341.44451100', 'vbulletin-4ecbdf567f3a38.99555308')));

		if ($nodeids)
		{
			$db->update('vBForum:node', array('protected' => 1), array('nodeid' => $nodeids));
		}

		$this->long_next_step();
	}

	public function step_4()
	{
		$this->add_index(
			sprintf($this->phrase['core']['create_index_x_on_y'], 'showpublished', TABLE_PREFIX . 'node'),
			'node',
			'showpublished',
			['showpublished']
		);
	}

	/*
	 * Prep for step_6: Need to import the settings XML in case this install doesn't have the new option yet.
	 */
	public function step_5()
	{
		vB_Library::clearCache();
		$this->final_load_settings();
	}

	// If using imagick, check ghostscript & enable or disable imagick_pdf_thumbnail option.
	public function step_6()
	{
		vB_Upgrade::createAdminSession();
		$options = vB::getDatastore()->getValue('options');
		if ($options['imagetype'] == 'Magick')
		{
			if (isset($options['imagick_pdf_thumbnail']))
			{
				$this->show_message($this->phrase['version']['533a1']['checking_ghostscript']);
				$pdfSupported = vB_Image::instance()->canThumbnailPdf();
				$this->set_option('imagick_pdf_thumbnail', 'attachment', $pdfSupported);
				if (!$pdfSupported)
				{
					$this->add_adminmessage(
						'after_upgrade_imagick_pdf_disabled',
						[
							'dismissible' => 1,
							'execurl'     => 'options.php?do=options&dogroup=attachment',
							'method'      => 'get',
							'status'      => 'undone',
						]
					);
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
}

class vB_Upgrade_533a3 extends vB_Upgrade_Version
{
	// Add trending table.
	public function step_1()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'trending'),
			"
				CREATE TABLE " . TABLE_PREFIX . "trending (
					nodeid INT UNSIGNED NOT NULL PRIMARY KEY,
					weight INT UNSIGNED NOT NULL,
					KEY weight (weight)
				) ENGINE = " . $this->hightrafficengine . "
			",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_2()
	{
		$this->add_cronjob(
			array(
				'varname'  => 'trending',
				'nextrun'  => 0,
				'weekday'  => -1,
				'day'      => -1,
				'hour'     => -1,
				'minute'   => serialize(array(50)),
				'filename' => './includes/cron/trending.php',
				'loglevel' => 1,
				'volatile' => 1,
				'product'  => 'vbulletin'
			)
		);
	}

	// Add fcmessage_offload table.
	public function step_3()
	{
		if (!$this->tableExists('fcmessage_offload'))
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'fcmessage_offload'),
				"
					CREATE TABLE " . TABLE_PREFIX . "fcmessage_offload (
						recipientids                VARCHAR(2048) NOT NULL DEFAULT '',
						message_data                VARCHAR(2048) NOT NULL DEFAULT '',
						hash                        CHAR(32) NOT NULL DEFAULT '',
						removeafter                 INT UNSIGNED NOT NULL DEFAULT '0',
						UNIQUE KEY guid  (hash)
					) ENGINE = " . $this->hightrafficengine . "
				",
				self::MYSQL_ERROR_TABLE_EXISTS
			);
		}
		else
		{
			$this->skip_message();
		}
	}
}

class vB_Upgrade_533b1 extends vB_Upgrade_Version
{
	public function step_1($data = [])
	{

		$assertor = vB::getDbAssertor();
		$batchsize = 500000;
		$startat = intval($data['startat'] ?? 0);

		// output what we're doing -- but only on the first iteration
		if ($startat == 0)
		{
			$this->show_message($this->phrase['version']['510a8']['fixing_imported_polls']);
		}

		//First see if we need to do something. Maybe we're O.K.
		if (!empty($data['maxToFix']))
		{
			$maxToFix = $data['maxToFix'];
		}
		else
		{
			$maxToFix = $assertor->getRow('vBInstall:getMaxPollNodeid', array());
			$maxToFix = intval($maxToFix['maxToFix']);
			//If we don't have any we're done.
			if (intval($maxToFix) < 1)
			{
				$this->skip_message();
				return;
			}
		}

		if ($startat >= $maxToFix)
		{
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			return;
		}

		// Get the poll data, nodeid of the starter, nodeid of the poll and the options from poll table
		$pollData = $assertor->assertQuery('vBInstall:pollFixPollVote',
			array(
				'startat' => $startat,
				'batchsize' => $batchsize,
			)
		);

		$processed = min($startat + $batchsize, $maxToFix);

		$this->show_message(sprintf($this->phrase['core']['processed_records_x_y_z'], $startat + 1, $processed, $maxToFix));
		return array('startat' => ($startat + $batchsize), 'maxToFix' => $maxToFix);
	}
}

class vB_Upgrade_534a1 extends vB_Upgrade_Version
{
	// SphinxSearch index changes, VBV-17631 & VBV-17629, requires rebuilding the index
	public function step_1()
	{
		$this->add_adminmessage(
			'need_sphinx_index_rebuild',
			array(
				'dismissable' => 1,
				'script'      => '',
				'action'      => '',
				'execurl'     => '',
				'method'      => '',
				'status'      => 'undone',
			)
		);
	}
}

class vB_Upgrade_534a3 extends vB_Upgrade_Version
{
	// add index on event.eventenddate
	public function step_1()
	{
		$this->add_index(
			sprintf($this->phrase['core']['create_index_x_on_y'], 'eventenddate', TABLE_PREFIX . 'event'),
			'event',
			'eventenddate',
			'eventenddate'
		);
	}

	// add event.allday column
	public function step_2()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'event', 1, 1),
			'event',
			'allday',
			'tinyint',
			array('length' => 1, 'null' => false, 'default' => '0')
		);
	}

	public function step_3()
	{
		if ($this->field_exists('event', 'allday'))
		{
			// First, update all events with eventenddate = 0 to set allday = 1
			$this->show_message($this->phrase['version']['534a4']['setting_event_allday']);
			$assertor = vB::getDbAssertor();
			$updateConditions = array(
				array('field' => 'eventenddate', 'value' => 0, vB_dB_Query::OPERATOR_KEY => vB_dB_Query::OPERATOR_EQ),
				array('field' => 'allday', 'value' => 0, vB_dB_Query::OPERATOR_KEY => vB_dB_Query::OPERATOR_EQ),
			);
			$needUpdate = $assertor->getRow(
				'vBForum:event',
				array(
					vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_COUNT,
					vB_dB_Query::CONDITIONS_KEY => $updateConditions,
				)
			);

			if (empty($needUpdate['count']))
			{
				return $this->skip_message();
			}
			else
			{
				$this->show_message(sprintf($this->phrase['core']['processing_records_x'], $needUpdate['count']));
				$assertor->assertQuery(
					'vBForum:event',
					array(
						vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
						vB_dB_Query::CONDITIONS_KEY => $updateConditions,
						'allday' => 1
					)
				);
			}
		}
		else
		{
			// If, somehow, the order got messed up or there are DB errors such that the previous step didn't create the
			// required index, there's nothing we can do here.
			$this->skip_message();
		}
	}

	public function step_4($data = null)
	{
		if ($this->field_exists('event', 'allday'))
		{
			// fetch events whose eventenddate = 0 AND allday = 1 (went through step 3), ordered by
			// userid, and calculate the enddate for each event (11:59:59PM user's timezone)

			if (empty($data['startat']))
			{
				$this->show_message($this->phrase['version']['534a4']['updating_allday_event_enddates']);
				$data['startat'] = 0;
			}

			$assertor = vB::getDbAssertor();
			$batchSize = 1000;
			$needUpdate = $assertor->getRows(
				'vBInstall:getAlldayEventsMissingEnddates',
				array('batchsize' => $batchSize,)
			);


			if (empty($needUpdate))
			{
				if (empty($data['startat']))
				{
					return $this->skip_message();
				}
				else
				{
					return $this->show_message(sprintf($this->phrase['core']['process_done']));
				}
			}
			else
			{
				$userAPI = vB_Api::instanceInternal('user');
				$eventLib = vB_Library::instance('content_event');
				$eventUpdates = array();
				foreach ($needUpdate AS $__row)
				{
					$__nodeid = $__row['nodeid'];
					$__userid = $__row['userid'];
					// We need to grab the offset for the specific date..
					$__startdate = $__row['eventstartdate'];
					$__startdate = $eventLib->getEndOfDayUnixtime($__startdate, $__userid, "12:00:00 AM");
					$__enddate = $eventLib->getEndOfDayUnixtime($__startdate, $__userid);
					// Handle unlikey case that startdate for an allday event is 11:59:59PM.
					// Although this technically makes it elapse 2 days, we should ensure
					// that eventenddate > eventstartdate.
					if ($__enddate <= $__startdate)
					{
						$__enddate = $__startdate + 1;
					}

					$eventUpdates[$__nodeid] = array(
						'nodeid' => $__nodeid,
						'eventenddate' => $__enddate,
						'eventstartdate' => $__startdate,
					);
				}
				$this->show_message(sprintf($this->phrase['core']['processing_records_x'], count($eventUpdates)));
				$assertor->assertQuery('vBInstall:updateEventEnddates', array("events" => $eventUpdates));

				// startat isn't used for anything other than forcing the next iteration.
				return array('startat' => ++$data['startat']);
			}
		}
		else
		{
			// If, somehow, the order got messed up or there are DB errors such that the previous step didn't create the
			// required index, there's nothing we can do here.
			$this->skip_message();
		}
	}

	public function step_5()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'event', 1, 1),
			'event',
			'maplocation',
			'VARCHAR',
			array('length' => 191, 'null' => false, 'default' => '')
		);
	}
}

class vB_Upgrade_535a1 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$code = vB::getDatastore()->getOption('ga_code');
		if ($code AND !preg_match('#^\s*<script#', $code))
		{
			$code = '<script type="text/javascript">' . "\n$code\n" . '</script>';
			vB_Library::instance('options')->updateValue('ga_code', $code);
		}
		$this->show_message($this->phrase['version']['535a1']['update_google_analytics']);
	}

	/**
	 * Removing Search Queue Processor Scheduled Tasks
	 */
	public function step_2()
	{
		//it turns out this got a different varname if it was added by the upgrader than if it was
		//added by the installer.  We got one a long time ago, we need to get the other one now.
		$this->show_message(sprintf($this->phrase['version']['503a3']['delete_queue_processor_cron']));
		vB::getDbAssertor()->delete('cron', array(
			'varname' => 'queueprocessor',
			'volatile' => 1,
			'product' => 'vbulletin',
		));
	}

	public function step_3($data)
	{
		if (empty($data['startat']))
		{
			$this->show_message(sprintf($this->phrase['vbphrase']['update_table_x'], TABLE_PREFIX . 'user', 1, 1));
		}

		$callback =	function($startat, $nextid)
		{
			$db = vB::getDbAssertor();
			$db->update(
				'user',
				['maxposts' => -1],
				[
					'maxposts' => 0,
					['field' => 'userid', 'value' => $startat, 'operator' =>  vB_dB_Query::OPERATOR_GTE],
					['field' => 'userid', 'value' => $nextid, 'operator' =>  vB_dB_Query::OPERATOR_LT],
				]
			);
		};

		$batchsize = $this->getBatchSize('large', __FUNCTION__);
		return $this->updateByIdWalk($data, $batchsize, 'vBInstall:getMaxUserid', 'user', 'userid', $callback);
	}
}

class vB_Upgrade_535a3 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->drop_table('block');
	}

	public function step_2()
	{
		$this->drop_table('blockconfig');
	}

	public function step_3()
	{
		$this->drop_table('blocktype');
	}

	public function step_4()
	{
		$this->drop_table('blog_userread');
	}

	public function step_5()
	{
		$this->drop_table('action');
	}

	public function step_6()
	{
		$this->drop_table('dbquery');
	}

	public function step_7()
	{
		$this->drop_table('contentread');
	}

	public function step_8()
	{
		$this->drop_table('apipost');
	}

	public function step_9()
	{
		$hvtype = vB::getDatastore()->getOption('hv_type');
		if ($hvtype == 'Recaptcha')
		{
			vB_Upgrade::createAdminSession();
			$this->show_message($this->phrase['version']['535a3']['update_recaptcha1']);
			$this->set_option('hv_type', '', 'Image');

			$this->add_adminmessage(
				'recapcha_removal_warning',
				array(
					'dismissable' => 1,
					'script'      => 'verify.php',
					'action'      => '',
					'execurl'     => 'verify.php',
					'method'      => 'get',
					'status'      => 'undone',
				),
				false
			);
		}
		else
		{
			$this->skip_message();
		}
	}
}

class vB_Upgrade_535a4 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$db = vB::getDBAssertor();
		$row = $db->getRow('routenew', array('name' => 'blog'));

		$changes = array();
		if ($row['prefix'] == $row['regex'])
		{
			$changes['regex'] = $row['prefix'] . '(?:(?:/|^)page(?P<pagenum>[0-9]+))?';
		}
		else
		{
			$newre = str_replace('(?:/page(', '(?:(?:/|^)page(', $row['regex']);

			if ($newre != $row['regex'])
			{
				$changes['regex'] = $newre;
			}
		}

		$arguments = unserialize($row['arguments']);
		if (!isset($arguments['channelid']) OR !isset($arguments['pagenum']))
		{
			if (!isset($arguments['channelid']))
			{
				$arguments['channelid'] = vB_Library::instance('blog')->getBlogChannel();
			}

			if (!isset($arguments['pagenum']))
			{
				$arguments['pagenum'] = '$pagenum';
			}

			$changes['arguments'] = serialize($arguments);
		}

		if ($changes)
		{
			$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'routenew'));
			$row = $db->update('routenew', $changes, array('name' => 'blog'));
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_2()
	{
		$db = vB::getDBAssertor();
		$row = $db->getRow('routenew', array('name' => 'sghome'));

		$changes = array();
		if ($row['prefix'] == $row['regex'])
		{
			$changes['regex'] = $row['prefix'] . '(?:(?:/|^)page(?P<pagenum>[0-9]+))?';
		}
		else
		{
			$newre = str_replace('(?:/page(', '(?:(?:/|^)page(', $row['regex']);

			if ($newre != $row['regex'])
			{
				$changes['regex'] = $newre;
			}
		}

		$arguments = unserialize($row['arguments']);
		if (!isset($arguments['channelid']) OR !isset($arguments['pagenum']))
		{
			if (!isset($arguments['channelid']))
			{
				$arguments['channelid'] = vB_Library::instance('node')->getSGChannel();
			}

			if (!isset($arguments['pagenum']))
			{
				$arguments['pagenum'] = '$pagenum';
			}

			$changes['arguments'] = serialize($arguments);
		}

		if ($changes)
		{
			$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'routenew'));
			$row = $db->update('routenew', $changes, array('name' => 'sghome'));
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_3()
	{
		$db = vB::getDBAssertor();
		$set = $db->select('routenew', array(array('field' => 'redirect301', 'operator' => vB_dB_Query::OPERATOR_ISNOTNULL)));

		$redirectMap = array();
		foreach ($set AS $row)
		{
			$redirectMap[$row['routeid']] = $row['redirect301'];
		}

		$haveupdate = false;
		//collapse redirects so we aren't redirecting to a redirect...
		foreach ($redirectMap AS $routeid => $redirectid)
		{
			//if we are redirecting to a redirect
			if (isset($redirectMap[$redirectid]))
			{
				$haveupdate = true;
				$seen = array();
				$finalredirectid = $redirectid;
				while (isset($redirectMap[$finalredirectid]))
				{
					$seen[] = $finalredirectid;
					$finalredirectid = $redirectMap[$finalredirectid];

					//if we've already seen this ID, we have a redirect loop.  This shouldn't happen,
					//but it's best to avoid infinite loops and who knows what's out there in the wild
					//In theory we should probably do something about this (likely deleting all the
					//routes in question, since they can't do anything good) but I'd rather wait for
					//a concrete example to test before doing something rash
					if (in_array($finalredirectid, $seen))
					{
						continue 2;
					}
				}

				$row = $db->update('routenew', array('redirect301' => $finalredirectid), array('routeid' => $routeid));
			}
		}

		if ($haveupdate)
		{
			$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'routenew'));
		}
		else
		{
			$this->skip_message();
		}
	}
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 112192 $
|| ####################################################################
\*======================================================================*/
