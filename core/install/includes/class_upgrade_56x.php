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

class vB_Upgrade_561a1 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'location'),
			"
				CREATE TABLE " . TABLE_PREFIX . "location (
					locationid INT UNSIGNED NOT NULL AUTO_INCREMENT,
					title VARCHAR(250) NOT NULL DEFAULT '',
					locationcodes TEXT,
					PRIMARY KEY (locationid)
				) ENGINE = " . $this->hightrafficengine . "
			",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_2()
	{
		vB_Upgrade::createAdminSession();

		$library = vB_Library::instance('options');

		//assume that if we have any locations we are dealing with the
		//first upgrade or, at least, we need to restore the defaults.
		$locations = $library->getLocationList();
		if (!$locations)
		{
			$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'setting'));
			$california = array(
				'title' => 'California',
				'locationcodes' => array('US:CA', 'UNKNOWN'),
			);
			$library->saveLocation($california);

			$eu = array(
				'title' => 'European Union',
				'locationcodes' => array(
					'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR', 'HU',
					'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PL', 'PT', 'RO', 'SK', 'SI', 'ES',
					'SE', 'GB', 'AL', 'ME', 'RS', 'MK', 'TR', 'IS', 'LI', 'MC', 'NO', 'CH', 'UA', 'EU',
					'UNKNOWN'
				),
			);
			$euid = $library->saveLocation($eu);

			$options = vB::getDatastore()->getValue('options');

			$privacyoptions = array(
				'enable_privacy_guest',
				'enable_privacy_registered',
				'block_eu_visitors',
				'enable_account_removal',
			);

			//if the privacy option is enabled, then set it to the 'EU' location.
			$db = vB::getDbAssertor();
			$location = json_encode(array($euid));
			foreach($privacyoptions AS $privacyoption)
			{
				if ($options[$privacyoption] == 1)
				{
					//don't use set_option here -- that depends on the option having the correct meta data
					//which it likely doesn't until we run final upgrade.  Likewise we need to make sure that the
					//datatype is "free" or the option import will stomp all over everything.
					$db->update('setting', array('value' => $location, 'datatype' => 'free'), array('varname' => $privacyoption));
				}
			}
		}
		else
		{
			$this->skip_message();
		}

		$this->long_next_step();
	}

	public function step_3()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], TABLE_PREFIX . 'user', 1, 2),
			'user',
			'location',
			'VARCHAR',
			array(
				'length' => 30,
				'default' => 'UNKNOWN',
			)
		);
	}

	public function step_4($data)
	{
		if ($this->field_exists('user', 'eustatus'))
		{
			if (empty($data['startat']))
			{
				$this->show_message(sprintf($this->phrase['vbphrase']['update_table_x'], TABLE_PREFIX . 'user', 1, 1));
			}

			$callback = function($startat, $nextid)
			{
				$db = vB::getDbAssertor();
				$db->update(
					'user',
					array('location' => 'EU'),
					array(
						'eustatus' => 1,
						array('field' => 'userid', 'value' => $startat, 'operator' =>  vB_dB_Query::OPERATOR_GTE),
						array('field' => 'userid', 'value' => $nextid, 'operator' =>  vB_dB_Query::OPERATOR_LT),
					)
				);
			};

			$batchsize = $this->getBatchSize('large', __FUNCTION__);
			$newdata = $this->updateByIdWalk($data, $batchsize, 'vBInstall:getMaxUserid', 'user', 'userid', $callback);

			//this is the last iteration.
			if (!$newdata)
			{
				$this->long_next_step();
			}

			return $newdata;
		}
		else
		{
			$this->skip_message();
			$this->long_next_step();
		}
	}


	public function step_5()
	{
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], TABLE_PREFIX . 'user', 2, 2),
			'user',
			'eustatus'
		);
	}

	public function step_6()
	{
		$db = vB::getDbAssertor();
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table_x'], TABLE_PREFIX . 'ipaddressinfo', 1, 1));
		$db->assertQuery('truncateTable', array('table' => 'ipaddressinfo'));
	}

	public function step_7()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'ipaddressinfo', 1, 2),
			'ipaddressinfo',
			'location',
			'VARCHAR',
			array(
				'length' => 30,
				'default' => '',
			)
		);
	}

	public function step_8()
	{
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], TABLE_PREFIX . 'ipaddressinfo', 2, 2),
			'ipaddressinfo',
			'eustatus'
		);
	}
}

class vB_Upgrade_561a2 extends vB_Upgrade_Version
{
	/*
		Removing fields & tables related to public joinable usergroups
		`usergroup`.`ispublicgroup`
		`usergrouprequest`
		`usergroupleader`
	 */

	public function step_1()
	{
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], TABLE_PREFIX . 'usergroup', 1, 1),
			'usergroup',
			'ispublicgroup'
		);
	}

	public function step_2()
	{
		$this->drop_table('usergroupleader');
	}

	public function step_3()
	{
		$this->drop_table('usergrouprequest');
	}
}

class vB_Upgrade_561a3 extends vB_Upgrade_Version
{
	// Remove unused `searchlog`.`completed`
	public function step_1()
	{
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], TABLE_PREFIX . 'searchlog', 1, 1),
			'searchlog',
			'completed'
		);
	}
}

class vB_Upgrade_561a4 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'userreferral'),
			"
				CREATE TABLE " . TABLE_PREFIX . "userreferral (
					userreferralid INT UNSIGNED NOT NULL AUTO_INCREMENT,
					userid INT UNSIGNED NOT NULL DEFAULT '0',
					dateline INT UNSIGNED NOT NULL DEFAULT '0',
					referralcode VARCHAR(40) NULL DEFAULT NULL,
					PRIMARY KEY (userreferralid),
					UNIQUE KEY (referralcode)
				) ENGINE = " . $this->hightrafficengine . "
			",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}
}

class vB_Upgrade_562a1 extends vB_Upgrade_Version
{
	public function step_1()
	{
		// Change from unsigned to signed by removing the unsigned attribute.
		// Since it currently doesn't have a default value, let's not change that.
		$this->alter_field(
			sprintf($this->phrase['core']['altering_x_table'], 'widgetinstance', 1, 1),
			'widgetinstance',
			'pagetemplateid',
			'INT',
			['null'  => false, 'extra' => '']
		);
	}
}

class vB_Upgrade_562a3 extends vB_Upgrade_Version
{
	public function step_1()
	{
		// We're just changing any logip == 2 to 1, and that doesn't
		// require the new setting import first.

		vB_Upgrade::createAdminSession();
		$assertor = vB::getDbAssertor();
		$options = vB::getDatastore()->getValue('options');
		if ($options['logip'] == 2)
		{
			$this->set_option('logip', '', 1);
			$this->show_message($this->phrase['version']['562a3']['updating_logip']);
			$this->add_adminmessage(
				'after_upgrade_logip_changed',
				array(
					'dismissible' => 1,
					'execurl'     => 'options.php?do=options&dogroup=posting#logip',
					'method'      => 'get',
					'status'      => 'undone',
				)
			);

		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_2()
	{
		vB_Upgrade::createAdminSession();

		$library = vB_Library::instance('options');

		$this->show_message(sprintf($this->phrase['vbphrase']['update_table_x'], TABLE_PREFIX . 'location', 1, 1));
		$allusers = array(
			'title' => 'All Users',
			'locationcodes' => array('ALLUSERS'),
		);
		$library->saveLocation($allusers);
	}

}

class vB_Upgrade_562a4 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'user', 1, 2),
			'user',
			'moderatornotificationoptions',
			'int',
			array(
				'attributes' => 'UNSIGNED',
				'null'       => false,
				// default to all four moderator notification options on
				'default'    => 15,
				'extra'      => '',
			)
		);
	}

	public function step_2()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'user', 2, 2),
			'user',
			'moderatoremailnotificationoptions',
			'int',
			array(
				'attributes' => 'UNSIGNED',
				'null'       => false,
				// default to all four moderator email notification options off
				'default'    => 0,
				'extra'      => '',
			)
		);
	}

	/**
	 * Add 'reportedpost', 'unapprovedpost', and 'spampost' notification types to 'about' in privatemessage
	 */
	public function step_3()
	{
		if ($this->field_exists('privatemessage', 'about'))
		{
			$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'privatemessage', 1, 1),
				"ALTER TABLE " . TABLE_PREFIX . "privatemessage MODIFY COLUMN about ENUM(
					'" . vB_Library_Content_Privatemessage::NOTIFICATION_TYPE_VOTE . "',
					'" . vB_Library_Content_Privatemessage::NOTIFICATION_TYPE_VOTEREPLY . "',
					'" . vB_Library_Content_Privatemessage::NOTIFICATION_TYPE_RATE . "',
					'" . vB_Library_Content_Privatemessage::NOTIFICATION_TYPE_REPLY . "',
					'" . vB_Library_Content_Privatemessage::NOTIFICATION_TYPE_FOLLOW . "',
					'" . vB_Library_Content_Privatemessage::NOTIFICATION_TYPE_FOLLOWING . "',
					'" . vB_Library_Content_Privatemessage::NOTIFICATION_TYPE_VM . "',
					'" . vB_Library_Content_Privatemessage::NOTIFICATION_TYPE_COMMENT . "',
					'" . vB_Library_Content_Privatemessage::NOTIFICATION_TYPE_THREADCOMMENT . "',
					'" . vB_Library_Content_Privatemessage::NOTIFICATION_TYPE_SUBSCRIPTION . "',
					'" . vB_Library_Content_Privatemessage::NOTIFICATION_TYPE_MODERATE . "',
					'" . vB_Library_Content_Privatemessage::NOTIFICATION_TYPE_USERMENTION . "',
					'" . vB_Library_Content_Privatemessage::NOTIFICATION_TYPE_MONITOREDWORD . "',
					'" . vB_Library_Content_Privatemessage::NOTIFICATION_TYPE_REPORTEDPOST . "',
					'" . vB_Library_Content_Privatemessage::NOTIFICATION_TYPE_UNAPPROVEDPOST . "',
					'" . vB_Library_Content_Privatemessage::NOTIFICATION_TYPE_SPAMPOST . "',
					'" . vB_Api_Node::REQUEST_TAKE_OWNER . "',
					'" . vB_Api_Node::REQUEST_TAKE_MODERATOR . "',
					'" . vB_Api_Node::REQUEST_GRANT_OWNER . "',
					'" . vB_Api_Node::REQUEST_GRANT_MODERATOR . "',
					'" . vB_Api_Node::REQUEST_GRANT_MEMBER . "',
					'" . vB_Api_Node::REQUEST_TAKE_MEMBER . "',
					'" . vB_Api_Node::REQUEST_TAKE_SUBSCRIBER . "',
					'" . vB_Api_Node::REQUEST_GRANT_SUBSCRIBER . "',
					'" . vB_Api_Node::REQUEST_SG_TAKE_OWNER . "',
					'" . vB_Api_Node::REQUEST_SG_TAKE_MODERATOR . "',
					'" . vB_Api_Node::REQUEST_SG_GRANT_OWNER . "',
					'" . vB_Api_Node::REQUEST_SG_GRANT_MODERATOR . "',
					'" . vB_Api_Node::REQUEST_SG_GRANT_SUBSCRIBER . "',
					'" . vB_Api_Node::REQUEST_SG_TAKE_SUBSCRIBER . "',
					'" . vB_Api_Node::REQUEST_SG_GRANT_MEMBER . "',
					'" . vB_Api_Node::REQUEST_SG_TAKE_MEMBER . "'
				);"
			);
		}
		else
		{
			$this->skip_message();
		}
	}
}

class vB_Upgrade_563a3 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->drop_table('sigparsed');
	}

	// add admin message to rebuild search indices (for tags)
	public function step_2()
	{
		$this->add_adminmessage(
			'after_upgrade_rebuild_search_index',
			array(
				'dismissible' => 1,			// Note, DB column is "dismissable" with an a, but the function param is "dismissible" with an i.
				'script'      => 'misc.php',
				'action'      => 'doindextypes',
				'execurl'     => 'misc.php?do=doindextypes&indextypes=0&perpage=250&startat=0&autoredirect=1',
				'method'      => 'post',
				'status'      => 'undone',
			)
		);
	}
}

class vB_Upgrade_563a4 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->drop_table('album');
	}

	public function step_2()
	{
		$this->drop_table('albumupdate');
	}
}

class vB_Upgrade_564a2 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$db = vB::getDbAssertor();

		$guid = 'vbulletin-widget_15-4eb423cfd6bd63.20171439';
		$oldWidget = $db->getRow('widget', ['guid' => $guid]);

		if ($oldWidget)
		{
			// delete the old widget & widget definition records
			$db->delete('widgetinstance', ['widgetid' => $oldWidget['widgetid']]);
			$db->delete('widgetdefinition', ['widgetid' => $oldWidget['widgetid']]);
			$db->delete('widget', ['widgetid' => $oldWidget['widgetid']]);
			$this->show_message($this->phrase['version']['564a2']['remove_php_widget']);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_2()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'usertextfield', 1, 1),
			'usertextfield',
			'status',
			'MEDIUMTEXT',
			self::FIELD_DEFAULTS
		);
	}
	public function step_3($data)
	{
		if ($this->field_exists('user', 'status'))
		{
			if (empty($data['startat']))
			{
				$this->show_message(sprintf($this->phrase['vbphrase']['update_table_x'], TABLE_PREFIX . 'user', 1, 1));
			}

			$callback = function($startat, $nextid)
			{
				$db = vB::getDbAssertor();
				$db->assertQuery('vBInstall:updateUserStatus', ['startat' => $startat, 'nextid' => $nextid]);
			};

			$batchsize = $this->getBatchSize('large', __FUNCTION__);
			return $this->updateByIdWalk($data, $batchsize, 'vBInstall:getMaxUserid', 'user', 'userid', $callback);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_4()
	{
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], TABLE_PREFIX . 'user', 1, 1),
			'user',
			'status'
		);
	}
}

class vB_Upgrade_565a6 extends vB_Upgrade_Version
{
	/**
	* Ending version compatibility
	*
	* @var	string
	*/
	public $VERSION_COMPAT_ENDS = '';

	public function step_1()
	{
		if ($this->iRan(__FUNCTION__))
		{
			return;
		}

		/*
		TODO runonce update allowedbbcodes to
		allowedbbcodes | 8192 | 1024 to enable img & video bbcodes by default (current behavior)
		 */
		$this->show_message($this->phrase['version']['565a6']['enabling_img_video_bbcodes']);

		$assertor = vB::getDbAssertor();
		$row = $assertor->getRow('setting', ['varname' => 'allowedbbcodes']);
		$row['value'] = $row['value'] |
						vB_Api_Bbcode::ALLOW_BBCODE_IMG |
						vB_Api_Bbcode::ALLOW_BBCODE_VIDEO;
		$assertor->update('setting',
			['value'     => $row['value'], ], // values
			['varname' => $row['varname'], ]  // condition
		);
	}

	public function step_2()
	{
		// Place holder to allow iRan() to work properly for CLI upgrade.
		$this->skip_message();
	}
}

class vB_Upgrade_565a8 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->long_next_step();
	}

	public function step_2()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'node', 1, 5),
			'node',
			'hasanswer',
			'TINYINT',
			[
				'attributes' => 'UNSIGNED',
				'null'       => false,
				'default'    => 0,
			]
		);
		$this->long_next_step();
	}

	public function step_3()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'node', 2, 5),
			'node',
			'isanswer',
			'TINYINT',
			['null' => false, 'default' => '0']
		);
		$this->long_next_step();
	}

	public function step_4()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'node', 3, 5),
			'node',
			'answer_set_by_user',
			'INT',
			[
				'attributes' => 'UNSIGNED',
				'null'       => false,
				'default'    => 0,
			]
		);
		$this->long_next_step();
	}

	public function step_5()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'node', 4, 5),
			'node',
			'answer_set_time',
			'INT',
			[
				'attributes' => 'UNSIGNED',
				'null'       => false,
				'default'    => 0,
			]
		);
		$this->long_next_step();
	}

	// create nodevote related tables
	public function step_6()
	{
		$this->run_query(
			sprintf($this->phrase['version']['565a8']['adding_nodevote_tables_x_of_y'], 1, 4),
			"
			CREATE TABLE " . TABLE_PREFIX . "nodevote (
				voteid INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
				nodeid INT UNSIGNED NOT NULL DEFAULT 0,
				userid INT UNSIGNED NOT NULL DEFAULT 0,
				votetypeid SMALLINT UNSIGNED NOT NULL DEFAULT 0,
				votegroupid SMALLINT UNSIGNED NOT NULL DEFAULT 0,
				whovoted INT UNSIGNED NOT NULL DEFAULT 0,
				dateline INT UNSIGNED NOT NULL DEFAULT 0,
				UNIQUE KEY enforce_single (nodeid, whovoted, votetypeid),
				KEY node_votes (nodeid, votegroupid, whovoted),
				KEY user_votes (userid, votegroupid, whovoted),
				KEY vote_agg_helper (nodeid, votetypeid)
			) ENGINE = " . $this->hightrafficengine . "
			",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_7()
	{
		$this->run_query(
			sprintf($this->phrase['version']['565a8']['adding_nodevote_tables_x_of_y'], 2, 4),
			"
			CREATE TABLE " . TABLE_PREFIX . "nodevotetype (
				votetypeid SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
				label VARCHAR(191) NOT NULL,
				votegroupid SMALLINT UNSIGNED NOT NULL DEFAULT 0,
				PRIMARY KEY (votetypeid),
				UNIQUE KEY (label, votegroupid)
			) ENGINE = " . $this->hightrafficengine . "
			",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_8()
	{
		$this->run_query(
			sprintf($this->phrase['version']['565a8']['adding_nodevote_tables_x_of_y'], 3, 4),
			"
			CREATE TABLE " . TABLE_PREFIX . "nodevotegroup (
				votegroupid SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
				label VARCHAR(191) NOT NULL UNIQUE,
				onchange ENUM('deny', 'radio', 'checkbox'),
				PRIMARY KEY (votegroupid)
			) ENGINE = " . $this->hightrafficengine . "
			",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_9()
	{
		$this->run_query(
			sprintf($this->phrase['version']['565a8']['adding_nodevote_tables_x_of_y'], 4, 4),
			"
			CREATE TABLE " . TABLE_PREFIX . "nodevoteaggregate (
				nodeid INT UNSIGNED NOT NULL DEFAULT 0,
				votetypeid SMALLINT UNSIGNED NOT NULL DEFAULT 0,
				votes INT UNSIGNED NOT NULL DEFAULT 0,
				PRIMARY KEY (nodeid, votetypeid)
			) ENGINE = " . $this->hightrafficengine . "
			",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_10()
	{
		$this->show_message($this->phrase['core']['cache_update']);
		// mainly just a utility for a/b testers since some of the internal cached data has changed.
		// force rebuild of cached nodevotes datastore items just to be safe.
		vB_Library::instance('nodevote')->reloadNodevoteMetaDataFromDB();
		$this->long_next_step();
	}

	public function step_11()
	{
		// Fix any alpha data generated while we had the `node`.`answerid` column
		if ($this->field_exists('node', 'answerid') AND $this->field_exists('node', 'hasanswer'))
		{
			$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'node'));
			vB::getDbAssertor()->assertQuery('vBInstall:565a8_moveAnsweridToHasanswer');
		}
		else
		{
			$this->skip_message();
		}
		$this->long_next_step();
	}

	public function step_12()
	{
		// Remove the replaced answerid field if exists. This field was added then removed in Alpha 8
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], 'node', 5, 5),
			'node',
			'answerid'
		);
	}
}

class vB_Upgrade_566a1 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'widgetdefinition'));
		vB::getDbAssertor()->assertQuery('vBInstall:deleteOrphanedWidgetDefintions');
	}

	public function step_2()
	{
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'setting'));
		$db  = vB::getDbAssertor();

		//Renaming an existing setting is *surprisingly* hard.  The issue is that there are several cases
		//1) Neither setting exists because we're upgrading from before it was created
		//2) The old setting exists because we're upgrading from after.
		//3) Only the new setting exists because we hit a step that loads the settings (but didn't have the old one)
		//4) We have both because we had the old setting and hit a step that loads the settings.
		//
		//Not all of these are possible for any given setting but trying to figure out what's possible and what
		//isn't based on exactly which versions of vbulletin this site got upgraded to (as opposed to "passing through"
		//a the versions without running final/reimporting the settings) is a mug's game.
		//
		//Mostly we want to preserve the value that the user may have set.  The rest is meta data controlled by the XML
		//and we want to have as light of a touch as possible.  So we'll do the following
		//1) If the new setting doesn't exist then move the old setting to be the new setting.  Implicitly we'll
		//	do nothing if we don't have either (nothing to do there)
		//2) If we have both, copy the old value to the new
		//3) If we only have the new, assume it's correct and go on with life, nothing to do.
		//
		//Note that this only handles the values.  We'll rely on the setting reimport to handle any other changes (we
		//don't want to attempt to preserve that anyway).  We also constantly rebuild the setting datastore after every
		//step and there isn't much point to doing it explicitly a second time.
		//
		//I couldn't find anywhere else where we've done this in the upgrade but if we have to do it again it might
		//be worth breaking this into a utility function.
		$column = $db->getColumn('setting', 'varname', ['varname' => 'trendingminimum']);
		if (!$column)
		{
			$db->update('setting', ['varname' => 'trendingminimum'], ['varname' => 'trendingminium']);
		}
		else
		{
			$db->assertQuery('vBInstall:copyOldOptionValue', [
				'oldvarname' => 'trendingminium',
				'newvarname' => 'trendingminimum',
			]);
		}
	}

}

class vB_Upgrade_566a2 extends vB_Upgrade_Version
{
	// If using the now removed legacy CLI imagemagick, switch them to GD
	public function step_1()
	{
		vB_Upgrade::createAdminSession();
		$options = vB::getDatastore()->getValue('options');
		if ($options['imagetype'] == 'Magick')
		{
			$this->show_message($this->phrase['version']['566a2']['switching_to_gd']);
			$this->set_option('imagetype', 'attachment', 'GD');
			$this->add_adminmessage(
				'after_upgrade_legacy_magick_disabled',
				array(
					'dismissible' => 1,
					'execurl'     => 'options.php?do=options&dogroup=attachment',
					'method'      => 'get',
					'status'      => 'undone',
				)
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_2()
	{
		vB_Upgrade::createAdminSession();
		$options = vB::getDatastore()->getValue('options');
		if ($options['regimagetype'] == 'Magick')
		{
			$this->show_message($this->phrase['version']['566a2']['switching_to_gd_hv']);
			// I don't know why this is named GDttf instead of GD even though the default value for this
			// setting is GD. But the option displayed in verify.php is GDttf and not GD, and while I think
			// setting it to GD actually works (both will default to GD), it doesn't look great.
			// Going with the apparently canonical value.
			$this->set_option('regimagetype', 'attachment', 'GDttf');
			$this->add_adminmessage(
				'after_upgrade_legacy_magick_disabled_hv',
				array(
					'dismissible' => 1,
					'execurl'     => 'verify.php',
					'method'      => 'get',
					'status'      => 'undone',
				)
			);
		}
		else
		{
			$this->skip_message();
		}
	}
}

class vB_Upgrade_566a5 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->long_next_step();
	}

	public function step_2()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'user', 1, 3),
			'user',
			'displayname',
			'VARCHAR',
			[
				'length'  => 191,
				'null'    => false,
				'default' => '',
			]
		);
		$this->long_next_step();
	}

	public function step_3()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'user', 2, 3),
			'user',
			'displayname',
			['displayname', ]
		);
		$this->long_next_step();
	}

	public function step_4($data)
	{
		if ($this->field_exists('user', 'displayname'))
		{
			// Avoid rerunning this if we already ran it at least once (especially as part of 500a1).
			// Note that the copyUnescapedUsernameToDisplayName query itself has a check for displayname = '' built in and will not
			// update those updated, but no sense in scrolling through the user table again if we don't need to.
			// Side note, we may want to add an adminCP tool to re-run the query for user ranges to "bulk reset" user displaynames.
			$check = vB::getDbAssertor()->getRow('user', ['displayname' => '']);
			if (empty($check))
			{
				$this->skip_message();
				return;
			}

			if (empty($data['startat']))
			{
				$this->show_message(sprintf($this->phrase['vbphrase']['update_table_x'], 'user', 3, 3));
			}

			$callback = function($startat, $nextid)
			{
				vB::getDbAssertor()->assertQuery('vBInstall:copyUnescapedUsernameToDisplayName', [
					'startat' => $startat,
					'nextid' => $nextid,
				]);
			};

			$batchsize = $this->getBatchSize('large', __FUNCTION__);
			$newdata = $this->updateByIdWalk($data, $batchsize, 'vBInstall:getMaxUserid', 'user', 'userid', $callback);

			return $newdata;
		}
		else
		{
			$this->skip_message();
		}
	}
}

class vB_Upgrade_567a3 extends vB_Upgrade_Version
{
	public function step_1()
	{
		// Only run once.
		if ($this->iRan(__FUNCTION__))
		{
			return;
		}
		$this->show_message($this->phrase['version']['567a3']['enabling_hashtag_bbcode']);

		$assertor = vB::getDbAssertor();
		$row = $assertor->getRow('setting', ['varname' => 'allowedbbcodes']);
		$row['value'] |= vB_Api_Bbcode::ALLOW_BBCODE_HASHTAG;
		$assertor->update('setting',
			['value' => $row['value']],
			['varname' => $row['varname']]
		);
	}

	public function step_2()
	{
		// Place holder to allow iRan() to work properly, as the last step gets recorded as step '0' in the upgrade log for CLI upgrade.
		$this->skip_message();
		return;
	}

}

class vB_Upgrade_567a4 extends vB_Upgrade_Version
{
	private $userFieldCount = 19;

	public function step_1()
	{
		$db = vB::getDbAssertor();
		$row = $db->getRow('vBInstall:checkIndexLimitFacebook');
		if (!$row)
		{
			$this->show_message(sprintf($this->phrase['core']['altering_x_table'], TABLE_PREFIX . 'user', 1, $this->userFieldCount));
			$db->assertQuery('vBInstall:alterIndexLimitFacebook');
		}
		else
		{
			$this->show_message(sprintf($this->phrase['version']['523a3']['data_too_long'], 'user', 191));
		}
	}

	public function step_2()
	{
		$this->alter_field(
			sprintf($this->phrase['core']['altering_x_table'], TABLE_PREFIX . 'user', 2, $this->userFieldCount),
			'user',
			'membergroupids',
			'varchar',
			[
				'length' => 250,
				'attributes' => self::FIELD_DEFAULTS
			]
		);
	}

	public function step_3()
	{
		$this->alter_field(
			sprintf($this->phrase['core']['altering_x_table'], TABLE_PREFIX . 'user', 3, $this->userFieldCount),
			'user',
			'email',
			'varchar',
			[
				'length' => 100,
				'attributes' => self::FIELD_DEFAULTS
			]
		);
	}

	public function step_4()
	{
		$this->alter_field(
			sprintf($this->phrase['core']['altering_x_table'], TABLE_PREFIX . 'user', 4, $this->userFieldCount),
			'user',
			'parentemail',
			'varchar',
			[
				'length' => 100,
				'attributes' => self::FIELD_DEFAULTS
			]
		);
	}

	public function step_5()
	{
		$this->alter_field(
			sprintf($this->phrase['core']['altering_x_table'], TABLE_PREFIX . 'user', 5, $this->userFieldCount),
			'user',
			'homepage',
			'varchar',
			[
				'length' => 100,
				'attributes' => self::FIELD_DEFAULTS
			]
		);
	}

	public function step_6()
	{
		$this->alter_field(
			sprintf($this->phrase['core']['altering_x_table'], TABLE_PREFIX . 'user', 6, $this->userFieldCount),
			'user',
			'usertitle',
			'varchar',
			[
				'length' => 250,
				'attributes' => self::FIELD_DEFAULTS
			]
		);
	}

	public function step_7()
	{
		$this->alter_field(
			sprintf($this->phrase['core']['altering_x_table'], TABLE_PREFIX . 'user', 7, $this->userFieldCount),
			'user',
			'icq',
			'varchar',
			[
				'length' => 20,
				'attributes' => self::FIELD_DEFAULTS
			]
		);
	}


	public function step_8()
	{
		$this->alter_field(
			sprintf($this->phrase['core']['altering_x_table'], TABLE_PREFIX . 'user', 8, $this->userFieldCount),
			'user',
			'yahoo',
			'varchar',
			[
				'length' => 32,
				'attributes' => self::FIELD_DEFAULTS
			]
		);
	}

	public function step_9()
	{
		$this->alter_field(
			sprintf($this->phrase['core']['altering_x_table'], TABLE_PREFIX . 'user', 9, $this->userFieldCount),
			'user',
			'skype',
			'varchar',
			[
				'length' => 32,
				'attributes' => self::FIELD_DEFAULTS
			]
		);
	}


	public function step_10()
	{
		$this->alter_field(
			sprintf($this->phrase['core']['altering_x_table'], TABLE_PREFIX . 'user', 10, $this->userFieldCount),
			'user',
			'google',
			'varchar',
			[
				'length' => 32,
				'attributes' => self::FIELD_DEFAULTS
			]
		);
	}

	public function step_11()
	{
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], TABLE_PREFIX . 'user', 11, $this->userFieldCount),
			'user',
			'assetposthash'
		);
	}

	public function step_12()
	{
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], TABLE_PREFIX . 'user', 12, $this->userFieldCount),
			'user',
			'msn'
		);
	}

	public function step_13()
	{
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], TABLE_PREFIX . 'user', 13, $this->userFieldCount),
			'user',
			'aim'
		);
	}

	public function step_14()
	{
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], TABLE_PREFIX . 'user', 14, $this->userFieldCount),
			'user',
			'gmmoderatedcount'
		);
	}

	public function step_15()
	{
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], TABLE_PREFIX . 'user', 15, $this->userFieldCount),
			'user',
			'pmpopup'
		);
	}

	public function step_16()
	{
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], TABLE_PREFIX . 'user', 16, $this->userFieldCount),
			'user',
			'socgroupinvitecount'
		);
	}

	public function step_17()
	{
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], TABLE_PREFIX . 'user', 17, $this->userFieldCount),
			'user',
			'socgroupreqcount'
		);
	}

	public function step_18()
	{
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], TABLE_PREFIX . 'user', 18, $this->userFieldCount),
			'user',
			'pcunreadcount'
		);
	}

	public function step_19()
	{
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], TABLE_PREFIX . 'user', 19, $this->userFieldCount),
			'user',
			'pcmoderatedcount'
		);
	}

	public function step_20()
	{
		$mapper = new vB_Stylevar_Mapper();

		// Changing a Color to Border type
		$mapper->addMapping('profile_content_divider_border.color', 'profile_content_divider_border.color');

		// Do the processing
		if ($mapper->load() AND $mapper->process())
		{
			$this->show_message($this->phrase['core']['mapping_customized_stylevars']);
			$mapper->processResults();
		}
		else
		{
			$this->skip_message();
		}
	}
}

class vB_Upgrade_569a2 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->long_next_step();
	}

	public function step_2()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'node', 1, 1),
			'node',
			'answer_set_by_user',
			['answer_set_by_user', ]
		);
	}
}

class vB_Upgrade_569a4 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$db = vB::getDbAssertor();
		$row = $db->getRow('vBForum:attachmenttype', ['extension' => 'webp']);

		if (!$row)
		{
			$query = getAttachmenttypeInsertQuery($this->db, ['webp']);
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'attachmenttype', 1, 1),
				$query
			);
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
|| # CVS: $RCSfile$ - $Revision: 112204 $
|| ####################################################################
\*======================================================================*/
