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

class vB_Upgrade_360 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->drop_index(
			sprintf($this->phrase['core']['altering_x_table'], 'reputation', 1, 2),
			'reputation',
			'whoadded'
		);
	}

	public function step_2()
	{
		//remove duplicate records before trying to add the unique index.
		//this is something of a "big hammer" approach but this site is by definition old if we're
		//starting here and the data involve is not of great value in vB5
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'reputation'));
		$db = vB::getDbAssertor();
		$db->assertQuery('vBinstall:deleteDuplicateReputation', array());
	}

	public function step_3()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'reputation', 2, 2),
			"ALTER TABLE " . TABLE_PREFIX . "reputation ADD UNIQUE INDEX
				whoadded_postid (whoadded, postid)",
			self::MYSQL_ERROR_KEY_EXISTS
		);
	}

	public function step_4()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'useractivation', 1, 1),
			'useractivation',
			'emailchange',
			'smallint',
			self::FIELD_DEFAULTS
		);
	}
}

class vB_Upgrade_360b1 extends vB_Upgrade_Version
{
	public $PREV_VERSION = '3.5.2+';
	public $VERSION_COMPAT_STARTS = '3.5.2';
	public $VERSION_COMPAT_ENDS   = '3.5.99';

	public function step_1()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'post', 1, 1),
			'post',
			'infraction',
			'smallint',
			self::FIELD_DEFAULTS
		);
	}

	public function step_2()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'thread', 1, 1),
			'thread',
			'deletedcount',
			'smallint',
			self::FIELD_DEFAULTS
		);
	}

	public function step_3()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'post', 1, 1),
			'post',
			'reportthreadid',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_4()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'thread', 1, 1),
			'thread',
			'lastpostid',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_5()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'setting', 1, 2),
			"ALTER TABLE " . TABLE_PREFIX . "setting CHANGE datatype datatype ENUM('free', 'number', 'boolean', 'bitfield', 'username') NOT NULL DEFAULT 'free'"
		);
	}

	public function step_6()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'setting', 2, 2),
			'setting',
			'blacklist',
			'smallint',
			self::FIELD_DEFAULTS
		);
	}

	public function step_7()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'forum', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "forum CHANGE childlist childlist TEXT"
		);
	}

	public function step_8()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . "externalcache"),
			"CREATE TABLE " . TABLE_PREFIX . "externalcache (
				cachehash CHAR(32) NOT NULL default '',
				text MEDIUMTEXT,
				headers MEDIUMTEXT,
				dateline INT UNSIGNED NOT NULL default '0',
				PRIMARY KEY (cachehash),
				KEY dateline (dateline, cachehash)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_9()
	{
		// Go medieval on phrases
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'phrase', 1, 7),
			'phrase',
			'fieldname',
			'varchar',
			array('length' => 20, 'attributes' => self::FIELD_DEFAULTS)
		);
	}

	public function step_10()
	{
		$this->drop_index(
			sprintf($this->phrase['core']['altering_x_table'], 'phrase', 2, 7),
			'phrase',
			'languageid'
		);
	}

	public function step_11()
	{
		$this->drop_index(
			sprintf($this->phrase['core']['altering_x_table'], 'phrase', 3, 7),
			'phrase',
			'name_lang_type'
		);
	}

	public function step_12()
	{
		if ($this->field_exists('phrase', 'phrasetypeid'))
		{
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'phrase', 4, 7),
				"UPDATE " . TABLE_PREFIX . "phrase AS phrase, " . TABLE_PREFIX . "phrasetype AS phrasetype
					SET phrase.fieldname = phrasetype.fieldname
				WHERE phrase.phrasetypeid = phrasetype.phrasetypeid"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_13()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'phrase', 5, 7),
			'phrase',
			'languageid',
			array('languageid', 'fieldname')
		);
	}

	public function step_14()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'phrase', 6, 7),
			"ALTER TABLE " . TABLE_PREFIX . "phrase ADD UNIQUE INDEX
				name_lang_type (varname, languageid, fieldname)",
			self::MYSQL_ERROR_KEY_EXISTS
		);
	}

	public function step_15()
	{
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], 'phrase', 7, 7),
			'phrase',
			'phrasetypeid'
		);
	}

	public function step_16()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'phrasetype', 1, 5),
			'phrasetype',
			'special',
			'smallint',
			self::FIELD_DEFAULTS
		);
	}

	public function step_17()
	{
		if ($this->field_exists('phrasetype', 'phrasetypeid'))
		{
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'phrasetype', 2, 5),
				"UPDATE " . TABLE_PREFIX . "phrasetype SET special = 1 WHERE phrasetypeid >= 1000"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_18()
	{
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], 'phrasetype', 3, 5),
			'phrasetype',
			'phrasetypeid'
		);
	}

	public function step_19()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'phrasetype', 5, 5),
			"DELETE FROM " . TABLE_PREFIX . "phrasetype WHERE fieldname = ''"
		);
	}

	public function step_20()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'phrasetype', 4, 5),
			"ALTER TABLE " . TABLE_PREFIX . "phrasetype ADD PRIMARY KEY (fieldname)",
			self::MYSQL_ERROR_PRIMARY_KEY_EXISTS
		);
	}

	public function step_21()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'product', 1, 2),
			'product',
			'url',
			'varchar',
			array('length' => 250, 'attributes' => self::FIELD_DEFAULTS)
		);
	}

	public function step_22()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'product', 2, 2),
			'product',
			'versioncheckurl',
			'varchar',
			array('length' => 250, 'attributes' => self::FIELD_DEFAULTS)
		);
	}

	public function step_23()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . "productdependency"),
			"CREATE TABLE " . TABLE_PREFIX . "productdependency (
				productdependencyid INT NOT NULL AUTO_INCREMENT,
				productid varchar(25) NOT NULL DEFAULT '',
				dependencytype varchar(25) NOT NULL DEFAULT '',
				parentproductid varchar(25) NOT NULL DEFAULT '',
				minversion varchar(50) NOT NULL DEFAULT '',
				maxversion varchar(50) NOT NULL DEFAULT '',
				PRIMARY KEY (productdependencyid)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_24()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'plugin', 1, 1),
			'plugin',
			'executionorder',
			'smallint',
			array('null' => false, 'default' => 5)
		);
	}

	public function step_25()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'event', 1, 2),
			'event',
			'dst',
			'smallint',
			array('attributes' => 'UNSIGNED', 'null' => false, 'default' => 1)
		);
	}

	public function step_26()
	{
		// now we need to update the actual entry
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'event', 2, 2),
			"UPDATE " . TABLE_PREFIX . "event SET
				dst = 0
			WHERE utc = 0"
		);
	}

	public function step_27()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'subscription', 1, 1),
			'subscription',
			'adminoptions',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_28()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'user', 1, 2),
			'user',
			'adminoptions',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_29()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'user', 2, 2),
			'user',
			'lastpostid',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_30()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'cron', 1, 6),
			'cron',
			'active',
			'smallint',
			array('attributes' => 'UNSIGNED', 'null' => false, 'default' => 1)
		);
	}

	public function step_31()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'cron', 2, 6),
			'cron',
			'varname',
			'varchar',
			array('length' => 100, 'attributes' => self::FIELD_DEFAULTS)
		);
	}

	public function step_32()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'cron', 3, 6),
			'cron',
			'volatile',
			'smallint',
			self::FIELD_DEFAULTS
		);
	}

	public function step_33()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'cron', 4, 6),
			'cron',
			'product',
			'varchar',
			array('length' => 25, 'attributes' => self::FIELD_DEFAULTS)
		);
	}

	public function step_34()
	{
		if ($this->field_exists('cron', 'title'))
		{
			$updates = array();
			$cronfiles = array(
				'dailycleanup', 'birthday', 'threadviews', 'promotion', 'digestdaily', 'digestweekly', 'subscriptions',
				'cleanup', 'attachmentviews', 'activate', 'removebans', 'cleanup2', 'stats', 'reminder', 'infractions', 'ccbill', 'rssposter'
			);
			$cron = $this->db->query_read("
				SELECT cronid, filename, title
				FROM " . TABLE_PREFIX . "cron
				WHERE varname = ''
			");
			while ($croninfo = $this->db->fetch_array($cron))
			{
				$create_cron_phrases = true;

				$has_file_match = preg_match('#([a-z0-9_]+)\.php$#si', $croninfo['filename'], $match);
				if ($has_file_match AND in_array(strtolower($match[1]), $cronfiles))
				{
					$croninfo['varname'] = strtolower($match[1]);
					$croninfo['volatile'] = 1;

					// phrases are the XML already, don't need to create them
					$create_cron_phrases = false;
				}
				else if ($has_file_match)
				{
					// have a filename, that's a good way to prepend
					$croninfo['varname'] = strtolower($match[1]) . $croninfo['cronid'];
					$croninfo['volatile'] = 0;
				}
				else
				{
					$croninfo['varname'] = 'task' . $croninfo['cronid'];
					$croninfo['volatile'] = 0;
				}

				if ($create_cron_phrases)
				{
					$title = 'task_' . $this->db->escape_string($croninfo['varname']) . '_title';
					$desc = 'task_' . $this->db->escape_string($croninfo['varname']) . '_desc';
					$log = 'task_' . $this->db->escape_string($croninfo['varname']) . '_log';

					$this->run_query(
						sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . "phrase"),
						"REPLACE INTO " . TABLE_PREFIX . "phrase
							(languageid,  fieldname, varname, text, product)
						VALUES
							(0, 'cron', '$title', '" . $this->db->escape_string($croninfo['title']) . "', 'vbulletin'),
							(0, 'cron', '$desc', '', 'vbulletin'),
							(0, 'cron', '$log', '', 'vbulletin')"
					);
				}

				// now we need to update the actual entry
				$this->run_query(
					$this->phrase['version']['360b1']['updating_cron'],
					"UPDATE " . TABLE_PREFIX . "cron SET
						varname = '" . $this->db->escape_string($croninfo['varname']) . "',
						volatile = $croninfo[volatile]
					WHERE cronid = $croninfo[cronid]"
				);
			}
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_35()
	{
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], 'cron', 5, 6),
			'cron',
			'title'
		);
	}

	public function step_36()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'cron', 6, 6),
			"ALTER TABLE " . TABLE_PREFIX . "cron ADD UNIQUE INDEX varname (varname)",
			self::MYSQL_ERROR_KEY_EXISTS
		);
	}

	public function step_37()
	{
		$this->add_cronjob(
			array(
				'varname'  => 'dailycleanup',
				'nextrun'  => 1053533100,
				'weekday'  => -1,
				'day'      => -1,
				'hour'     => 0,
				'minute'   => 'a:1:{i:0;i:10;}',
				'filename' => './includes/cron/dailycleanup.php',
				'loglevel' => 0,
				'volatile' => 1,
				'product'  => 'vbulletin'
			)
		);
	}

	public function step_38()
	{
		$this->add_cronjob(
			array(
				'varname'  => 'rssposter',
				'nextrun'  => 0,
				'weekday'  => -1,
				'day'      => -1,
				'hour'     => -1,
				'minute'   => 'a:6:{i:0;i:0;i:1;i:10;i:2;i:20;i:3;i:30;i:4;i:40;i:5;i:50;}',
				'filename' => './includes/cron/rssposter.php',
				'loglevel' => 1,
				'volatile' => 1,
				'product'  => 'vbulletin'
			)
		);
	}

	public function step_39()
	{
		$this->add_cronjob(
			array(
				'varname'  => 'infractions',
				'nextrun'  => 1053533100,
				'weekday'  => -1,
				'day'      => -1,
				'hour'     => -1,
				'minute'   => 'a:2:{i:0;i:20;i:1;i:50;}',
				'filename' => './includes/cron/infractions.php',
				'loglevel' => 1,
				'volatile' => 1,
				'product'  => 'vbulletin'
			)
		);
	}

	public function step_40()
	{
		$this->add_cronjob(
			array(
				'varname'  => 'ccbill',
				'nextrun'  => 1053533100,
				'weekday'  => -1,
				'day'      => -1,
				'hour'     => -1,
				'minute'   => 'a:1:{i:0;i:10;}',
				'filename' => './includes/cron/ccbill.php',
				'loglevel' => 1,
				'volatile' => 1,
				'product'  => 'vbulletin'
			)
		);
	}

	public function step_41()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'cronlog', 1, 5),
			'cronlog',
			'type',
			'smallint',
			self::FIELD_DEFAULTS
		);
	}

	public function step_42()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'cronlog', 2, 5),
			'cronlog',
			'varname',
			'varchar',
			array('length' => 100, 'attributes' => self::FIELD_DEFAULTS)
		);
	}

	public function step_43()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'cronlog', 3, 5),
			'cronlog',
			'varname',
			'varname'
		);
	}

	public function step_44()
	{
		if ($this->field_exists('cronlog', 'cronid'))
		{
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'cronlog', 4, 5),
				"UPDATE " . TABLE_PREFIX . "cronlog AS cronlog, " . TABLE_PREFIX . "cron AS cron SET
					cronlog.varname = cron.varname
				WHERE cronlog.cronid = cron.cronid"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_45()
	{
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], 'cronlog', 5, 5),
			'cronlog',
			'cronid'
		);
	}

	public function step_46()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'announcement', 1, 1),
			'announcement',
			'enddate',
			array('enddate', 'forumid', 'startdate')
		);
	}

	public function step_47()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . "announcementread"),
			"CREATE TABLE " . TABLE_PREFIX . "announcementread (
				announcementid INT UNSIGNED NOT NULL DEFAULT '0',
				userid INT UNSIGNED NOT NULL DEFAULT '0',
				PRIMARY KEY (announcementid, userid),
				KEY userid (userid)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_48()
	{
		if (!$this->field_exists('search', 'announceids'))
		{
			// this must only be run once, so make sure the query that follows hasn't been run
			$this->run_query(
				$this->phrase['version']['360b1']['invert_banned_flag'],
				"UPDATE " . TABLE_PREFIX . "usergroup
					SET genericoptions = IF(genericoptions & 32, genericoptions - 32, genericoptions + 32)"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_49()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'search', 1, 1),
			'search',
			'announceids',
			'mediumtext',
			self::FIELD_DEFAULTS
		);
	}

	public function step_50()
	{
		if ($this->field_exists('subscription', 'description'))
		{
			// Phrasing for Subscriptions
			$subs = $this->db->query_read("
				SELECT subscriptionid, title, description
				FROM " . TABLE_PREFIX . "subscription
			");
			while ($sub = $this->db->fetch_array($subs))
			{
				$title = 'sub' . $sub['subscriptionid'] . '_title';
				$desc = 'sub' . $sub['subscriptionid'] . '_desc';

				$this->run_query(
					$this->phrase['version']['360b1']['updating_subscriptions'],
					"REPLACE INTO " . TABLE_PREFIX . "phrase
						(languageid, fieldname, varname, text, product)
					VALUES
						(0, 'subscription', '$title', '" . $this->db->escape_string($sub['title']) . "', 'vbulletin'),
						(0, 'subscription', '$desc', '" . $this->db->escape_string($sub['description']) . "', 'vbulletin')"
				);
			}

			if (!$this->db->num_rows($subs))
			{
				$this->skip_message();
			}
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_51()
	{
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], 'subscription', 1, 2),
			'subscription',
			'title'
		);
	}

	public function step_52()
	{
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], 'subscription', 2, 2),
			'subscription',
			'description'
		);
	}

	public function step_53()
	{
		if ($this->field_exists('holiday', 'varname'))
		{
			// Phrase changes for Holidays (remove varname, simplify)
			$holidays = $this->db->query_read("
				SELECT holidayid, varname
				FROM " . TABLE_PREFIX . "holiday
			");
			while ($holiday = $this->db->fetch_array($holidays))
			{
				$this->run_query(
					'', // only output one message per holiday
					"UPDATE IGNORE " . TABLE_PREFIX . "phrase
						SET varname = 'holiday" . $holiday['holidayid'] . "_title'
					WHERE varname = 'holiday_title_" . $this->db->escape_string($holiday['varname']) . "'"
				);

				$this->run_query(
					$this->phrase['version']['360b1']['updating_holidays'],
					"UPDATE IGNORE " . TABLE_PREFIX . "phrase
						SET varname = 'holiday" . $holiday['holidayid'] . "_desc'
					WHERE varname = 'holiday_event_" . $this->db->escape_string($holiday['varname']) . "'"
				);
			}
			if (!$this->db->num_rows($holidays))
			{
				$this->skip_message();
			}
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_54()
	{
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], 'holiday', 1, 1),
			'holiday',
			'varname'
		);
	}

	public function step_55()
	{
		if ($this->field_exists('profilefield', 'description'))
		{
			// Phrasing for custom profilefields
			$fields = $this->db->query_read("
				SELECT title, description, profilefieldid
				FROM " . TABLE_PREFIX . "profilefield
			");
			while ($field = $this->db->fetch_array($fields))
			{
				$title = 'field' . $field['profilefieldid'] . '_title';
				$desc = 'field' . $field['profilefieldid'] . '_desc';

				$this->run_query(
					$this->phrase['version']['360b1']['updating_profilefields'],
					"REPLACE INTO " . TABLE_PREFIX . "phrase
						(languageid, fieldname, varname, text, product)
					VALUES
						(0, 'cprofilefield', '$title', '" . $this->db->escape_string($field['title']) . "', 'vbulletin'),
						(0, 'cprofilefield', '$desc', '" . $this->db->escape_string($field['description']) . "', 'vbulletin')"
				);
			}

			if (!$this->db->num_rows($fields))
			{
				$this->skip_message();
			}
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_56()
	{
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], 'profilefield', 1, 2),
			'profilefield',
			'title'
		);
	}

	public function step_57()
	{
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], 'profilefield', 2, 2),
			'profilefield',
			'description'
		);
	}

	public function step_58()
	{
		if ($this->field_exists('reputationlevel', 'level'))
		{
			// Phrasing for Reputation Levels
			$levels = $this->db->query_read("
				SELECT level, reputationlevelid
				FROM " . TABLE_PREFIX . "reputationlevel
			");
			while ($level = $this->db->fetch_array($levels))
			{
				$desc = 'reputation' . $level['reputationlevelid'];

				$this->run_query(
					$this->phrase['version']['360b1']['updating_reputationlevels'],
					"REPLACE INTO " . TABLE_PREFIX . "phrase
						(languageid, fieldname, varname, text, product)
					VALUES
						(0, 'reputationlevel', '$desc', '" . $this->db->escape_string($level['level']) . "', 'vbulletin')"
				);
			}
			if (!$this->db->num_rows($levels))
			{
				$this->skip_message();
			}
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_59()
	{
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], 'reputationlevel', 1, 1),
			'reputationlevel',
			'level'
		);
	}

	public function step_60()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'language', 1, 2),
			'language',
			'phrasegroup_cprofilefield',
			'mediumtext',
			self::FIELD_DEFAULTS
		);
	}

	public function step_61()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'language', 2, 2),
			'language',
			'phrasegroup_reputationlevel',
			'mediumtext',
			self::FIELD_DEFAULTS
		);
	}

	public function step_62()
	{
		$cprofilefieldtitle = $this->phrase['phrasetype']['cprofilefield'];
		$reputationleveltitle = $this->phrase['phrasetype']['reputationlevel'];
		// update phrase group list
		$this->run_query(
			sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . "phrasetype"),
			"INSERT IGNORE INTO " . TABLE_PREFIX . "phrasetype
				(title, editrows, fieldname)
			VALUES
				('$cprofilefieldtitle', 3, 'cprofilefield'),
				('$reputationleveltitle', 3, 'reputationlevel')"
		);
	}

	public function step_63()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . "podcast"),
			"CREATE TABLE " . TABLE_PREFIX . "podcast (
				forumid INT UNSIGNED NOT NULL DEFAULT '0',
				author VARCHAR(255) NOT NULL DEFAULT '',
				category VARCHAR(255) NOT NULL DEFAULT '',
				image VARCHAR(255) NOT NULL DEFAULT '',
				explicit SMALLINT NOT NULL DEFAULT '0',
				enabled SMALLINT NOT NULL DEFAULT '1',
				keywords VARCHAR(255) NOT NULL DEFAULT '',
				owneremail VARCHAR(255) NOT NULL DEFAULT '',
				ownername VARCHAR(255) NOT NULL DEFAULT '',
				subtitle VARCHAR(255) NOT NULL DEFAULT '',
				summary MEDIUMTEXT,
				categoryid SMALLINT NOT NULL DEFAULT '0',
				PRIMARY KEY  (forumid)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_64()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'announcement', 1, 5),
			'announcement',
			'announcementoptions',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_65()
	{
		if ($this->field_exists('announcement', 'allowsmilies'))
		{
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'announcement', 2, 5),
				"UPDATE " . TABLE_PREFIX . "announcement
					SET announcementoptions = 0
						+ IF(allowbbcode, 1, 0)
						+ IF(allowhtml, 2, 0)
						+ IF(allowsmilies, 4, 0)
						+ 8  # parseurl = yes
						+ 16 # signature = yes"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_66()
	{
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], 'announcement', 3, 5),
			'announcement',
			'allowbbcode'
		);
	}

	public function step_67()
	{
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], 'announcement', 4, 5),
			'announcement',
			'allowhtml'
		);
	}

	public function step_68()
	{
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], 'announcement', 5, 5),
			'announcement',
			'allowsmilies'
		);
	}

	public function step_69()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'faq', 1, 1),
			'faq',
			'product',
			'varchar',
			array('length' => 25, 'attributes' => self::FIELD_DEFAULTS)
		);
	}

	public function step_70()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'forum', 1, 1),
			'forum',
			'lastpostid',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_71()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'tachythreadpost', 1, 1),
			'tachythreadpost',
			'lastpostid',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_72()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'tachyforumpost', 1, 1),
			'tachyforumpost',
			'lastpostid',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_73()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . "sigpic"),
			"CREATE TABLE " . TABLE_PREFIX . "sigpic (
				  userid int unsigned NOT NULL default '0',
				  filedata mediumblob,
				  dateline int unsigned NOT NULL default '0',
				  filename varchar(100) NOT NULL default '',
				  visible smallint NOT NULL default '1',
				  filesize int unsigned NOT NULL default '0',
				  width smallint unsigned NOT NULL default '0',
				  height smallint unsigned NOT NULL default '0',
				  PRIMARY KEY  (userid)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_74()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'usergroup', 1, 9),
			'usergroup',
			'signaturepermissions',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_75()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'usergroup', 2, 9),
			'usergroup',
			'sigpicmaxwidth',
			'smallint',
			self::FIELD_DEFAULTS
		);
	}

	public function step_76()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'usergroup', 3, 9),
			'usergroup',
			'sigpicmaxheight',
			'smallint',
			self::FIELD_DEFAULTS
		);
	}

	public function step_77()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'usergroup', 4, 9),
			'usergroup',
			'sigpicmaxsize',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_78()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'usergroup', 5, 9),
			'usergroup',
			'sigmaximages',
			'smallint',
			self::FIELD_DEFAULTS
		);
	}

	public function step_79()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'usergroup', 6, 9),
			'usergroup',
			'sigmaxsizebbcode',
			'smallint',
			self::FIELD_DEFAULTS
		);
	}

	public function step_80()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'usergroup', 7, 9),
			'usergroup',
			'sigmaxchars',
			'smallint',
			self::FIELD_DEFAULTS
		);
	}

	public function step_81()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'usergroup', 8, 9),
			'usergroup',
			'sigmaxrawchars',
			'smallint',
			self::FIELD_DEFAULTS
		);
	}

	public function step_82()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'usergroup', 9, 9),
			'usergroup',
			'sigmaxlines',
			'smallint',
			self::FIELD_DEFAULTS
		);
	}

	public function step_83()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'user', 1, 1),
			'user',
			'sigpicrevision',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_84()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . "sigparsed"),
			"CREATE TABLE " . TABLE_PREFIX . "sigparsed (
				userid INT UNSIGNED NOT NULL DEFAULT '0',
				styleid SMALLINT UNSIGNED NOT NULL DEFAULT '0',
				languageid SMALLINT UNSIGNED NOT NULL DEFAULT '0',
				signatureparsed MEDIUMTEXT,
				hasimages SMALLINT UNSIGNED NOT NULL DEFAULT '0',
				PRIMARY KEY (userid, styleid, languageid)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_85()
	{
		if (!$this->field_exists('setting', 'validationcode'))
		{
			// give any group that has sig perms permission to use the appropriate bbcodes, etc
			// the "can have signature" perm has existed for a while, so that will take precedence over these settings
			$sig_perm_bits =(
				(($this->registry->options['allowedbbcodes'] & 1) ? 1 : 0) + // basic bb codes
				(($this->registry->options['allowedbbcodes'] & 2) ? 2 : 0) + // color bb code
				(($this->registry->options['allowedbbcodes'] & 4) ? 4 : 0) + // size bb code
				(($this->registry->options['allowedbbcodes'] & 8) ? 8 : 0) + // font bb code
				(($this->registry->options['allowedbbcodes'] & 16) ? 16 : 0) + // align bb codes
				(($this->registry->options['allowedbbcodes'] & 32) ? 32 : 0) + // list bb code
				(($this->registry->options['allowedbbcodes'] & 64) ? 64 : 0) + // link bb codes
				(($this->registry->options['allowedbbcodes'] & 128) ? 128 : 0) + // code bb code
				(($this->registry->options['allowedbbcodes'] & 256) ? 256 : 0) + // php bb code
				(($this->registry->options['allowedbbcodes'] & 512) ? 512 : 0) + // html bb code
				1024 + // quote is always allowed
				($this->registry->options['allowbbimagecode'] ? 2048 : 0) + // images
				($this->registry->options['allowsmilies'] ? 4096 : 0) + // smilies
				($this->registry->options['allowhtml'] ? 8192 : 0) + // html
				// 16384 isn't used
				// 32768 = sig pics, handled in query itself
				// 65536 = can upload animated sig pics, handled in query
				($this->registry->options['allowbbcode'] ? 131072 : 0) // global bbcode switch
			);

			$can_cp_sql = "adminpermissions & " . $this->registry->bf_ugp_adminpermissions['cancontrolpanel'];

			// this has been removed from vbulletin-settings.xml so may possibly be missing if they used the new xml file before the upgrade
			$this->registry->options['sigmax'] = (isset($this->registry->options['sigmax']) ? $this->registry->options['sigmax'] : 500);

			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'usergroup', 1, 1),
				"UPDATE " . TABLE_PREFIX . "usergroup SET
					signaturepermissions = $sig_perm_bits
						+ IF(genericpermissions & " . $this->registry->bf_ugp_genericpermissions['canuseavatar'] . ", 32768, 0) # sig pic
						+ IF(genericpermissions & " . $this->registry->bf_ugp_genericpermissions['cananimateavatar'] . ", 65536, 0), # animated sig pic
					sigmaxrawchars = IF($can_cp_sql, 0, " . intval(2 * $this->registry->options['sigmax']) . "),
					sigmaxchars = IF($can_cp_sql, 0, " . intval($this->registry->options['sigmax']) . "),
					sigmaxlines = 0,
					sigmaxsizebbcode = 7,
					sigmaximages = IF($can_cp_sql, 0, " . intval($this->registry->options['maximages']) . "),
					sigpicmaxwidth = 500,
					sigpicmaxheight = 100,
					sigpicmaxsize = 20000
				"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_86()
	{
		// add validation code to settings table
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'setting', 1, 1),
			'setting',
			'validationcode',
			'text',
			self::FIELD_DEFAULTS
		);
	}

	public function step_87()
	{
		// rename the post_parsed table so none of our tables have underscores
		$this->run_query(
			 sprintf($this->phrase['version']['360b1']['rename_post_parsed'], TABLE_PREFIX),
			 "ALTER TABLE " . TABLE_PREFIX . "post_parsed RENAME " . TABLE_PREFIX . "postparsed",
			 self::MYSQL_ERROR_TABLE_MISSING
		);
	}

	public function step_88()
	{
		// update thread redirects to have TIMENOW for dateline
		$this->run_query(
			$this->phrase['version']['360b1']['updating_thread_redirects'],
			"UPDATE " . TABLE_PREFIX . "thread
				SET dateline = " . TIMENOW . "
			WHERE open = 10
				AND pollid > 0"
		);
	}

	public function step_89()
	{
		// set canignorequota for usergroups 5, 6 and 7
		if (!$this->field_exists('forum', 'showprivate'))
		{
			$this->run_query(
				$this->phrase['version']['360b1']['install_canignorequota_permission'],
				"UPDATE " . TABLE_PREFIX . "usergroup
					SET pmpermissions = pmpermissions + 4
				 WHERE usergroupid IN (5, 6, 7) AND NOT (pmpermissions & 4)"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_90()
	{
		// add per-forum setting to show/hide private forums
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'forum', 1, 3),
			'forum',
			'showprivate',
			'tinyint',
			self::FIELD_DEFAULTS
		);
	}

	public function step_91()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'forum', 2, 3),
			'forum',
			'defaultsortfield',
			'varchar',
			array('length' => 50, 'null' => false, 'default' => 'lastpost')
		);
	}

	public function step_92()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'forum', 3, 3),
			'forum',
			'defaultsortorder',
			'enum',
			array('attributes' => "('asc', 'desc')", 'null' => false, 'default' => 'desc')
		);
	}
	public function step_93()
	{
		// Infraction Table
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . "infraction"),
			"CREATE TABLE " . TABLE_PREFIX . "infraction (
				infractionid INT UNSIGNED NOT NULL AUTO_INCREMENT ,
				infractionlevelid INT UNSIGNED NOT NULL DEFAULT '0',
				postid INT UNSIGNED NOT NULL DEFAULT '0',
				userid INT UNSIGNED NOT NULL DEFAULT '0',
				whoadded INT UNSIGNED NOT NULL DEFAULT '0',
				points INT UNSIGNED NOT NULL DEFAULT '0',
				dateline INT UNSIGNED NOT NULL DEFAULT '0',
				note varchar(255) NOT NULL DEFAULT '',
				action SMALLINT UNSIGNED NOT NULL DEFAULT '0',
				actiondateline INT UNSIGNED NOT NULL DEFAULT '0',
				actionuserid INT UNSIGNED NOT NULL DEFAULT '0',
				actionreason VARCHAR(255) NOT NULL DEFAULT '',
				expires INT UNSIGNED NOT NULL DEFAULT '0',
				threadid INT UNSIGNED NOT NULL DEFAULT '0',
				PRIMARY KEY (infractionid),
				KEY expires (expires, action),
				KEY userid (userid, action),
				KEY infractonlevelid (infractionlevelid),
				KEY postid (postid),
				KEY threadid (threadid)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_94()
	{
		// Infraction Groups Table
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . "infractiongroup"),
			"CREATE TABLE " . TABLE_PREFIX . "infractiongroup (
				infractiongroupid INT UNSIGNED NOT NULL AUTO_INCREMENT ,
				usergroupid INT NOT NULL DEFAULT '0',
				orusergroupid SMALLINT UNSIGNED NOT NULL DEFAULT '0',
				pointlevel INT UNSIGNED NOT NULL DEFAULT '0',
				PRIMARY KEY (infractiongroupid),
				KEY usergroupid (usergroupid, pointlevel)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_95()
	{
		// Infraction Level Table
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . "infractionlevel"),
			"CREATE TABLE " . TABLE_PREFIX . "infractionlevel (
				infractionlevelid INT UNSIGNED NOT NULL AUTO_INCREMENT ,
				points INT UNSIGNED NOT NULL DEFAULT '0',
				expires INT UNSIGNED NOT NULL DEFAULT '0',
				period ENUM('H','D','M','N') DEFAULT 'H' NOT NULL,
				warning SMALLINT UNSIGNED DEFAULT '0',
				PRIMARY KEY (infractionlevelid)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_96()
	{
		// Add new language Groups
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'language', 1, 2),
			'language',
			'phrasegroup_infraction',
			'mediumtext',
			self::FIELD_DEFAULTS
		);
	}

	public function step_97()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'language', 2, 2),
			'language',
			'phrasegroup_infractionlevel',
			'mediumtext',
			self::FIELD_DEFAULTS
		);
	}

	public function step_98()
	{
		$infractiontitle = $this->phrase['phrasetype']['infraction'];
		$infractionleveltitle = $this->phrase['phrasetype']['infractionlevel'];

		// Add new phrase groups
		$this->run_query(
			sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . "phrasetype"),
			"INSERT IGNORE INTO " . TABLE_PREFIX . "phrasetype
				(fieldname , title , editrows, special)
			VALUES
				('infraction', '$infractiontitle', 3, 0),
				('infractionlevel', '$infractionleveltitle', 3, 0)"
		);
	}

	public function step_99()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . "infractionlevel"),
			"INSERT IGNORE INTO " . TABLE_PREFIX . "infractionlevel
				(infractionlevelid, points, expires, period, warning)
			VALUES
				(1, 1, 10, 'D', 1),
				(2, 1, 10, 'D', 1),
				(3, 1, 10, 'D', 1),
				(4, 1, 10, 'D', 1)"
		);
	}

	public function step_100()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . "phrase"),
			"REPLACE INTO " . TABLE_PREFIX . "phrase
				(languageid, fieldname, varname, text, product)
			VALUES
				(0, 'infractionlevel', 'infractionlevel1_title', '" . $this->db->escape_string($this->phrase['version']['360b1']['infractionlevel1_title']) . "', 'vbulletin'),
				(0, 'infractionlevel', 'infractionlevel2_title', '" . $this->db->escape_string($this->phrase['version']['360b1']['infractionlevel2_title']) . "', 'vbulletin'),
				(0, 'infractionlevel', 'infractionlevel3_title', '" . $this->db->escape_string($this->phrase['version']['360b1']['infractionlevel3_title']) . "', 'vbulletin'),
				(0, 'infractionlevel', 'infractionlevel4_title', '" . $this->db->escape_string($this->phrase['version']['360b1']['infractionlevel4_title']) . "', 'vbulletin')"
		);
	}

	public function step_101()
	{
		// only do these perm updates once
		if (!$this->field_exists('user', 'ipoints'))
		{
			// Make sure to zero out permissions from possible past usage
			$newperms = array(
				'genericpermissions' => array(
					$this->registry->bf_ugp_genericpermissions['canreverseinfraction'],
					$this->registry->bf_ugp_genericpermissions['canseeinfraction'],
					$this->registry->bf_ugp_genericpermissions['cangiveinfraction'],
					$this->registry->bf_ugp_genericpermissions['canemailmember'],
			));

			foreach ($newperms AS $permission => $permissions)
			{
				$this->run_query(
					sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . "usergroup"),
					"UPDATE " . TABLE_PREFIX . "usergroup SET $permission = $permission & ~" . (array_sum($permissions))
				);
			}

			$infractionperms = $this->registry->bf_ugp_genericpermissions['cangiveinfraction'] + $this->registry->bf_ugp_genericpermissions['canseeinfraction'];
			// Set infraction permissions for admins, mods and super mods
			$this->run_query(
				sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . "usergroup"),
				"UPDATE " . TABLE_PREFIX . "usergroup
					SET genericpermissions = genericpermissions | $infractionperms
				WHERE adminpermissions & " . $this->registry->bf_ugp_adminpermissions['cancontrolpanel'] . "
					OR adminpermissions & " . $this->registry->bf_ugp_adminpermissions['ismoderator'] . "
					OR usergroupid = 7"
			);

			// give infraction reversal perms to admins
			$this->run_query(
				sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . "usergroup"),
				"UPDATE " . TABLE_PREFIX . "usergroup
					SET genericpermissions = genericpermissions | " . $this->registry->bf_ugp_genericpermissions['canreverseinfraction'] ."
				WHERE adminpermissions & " . $this->registry->bf_ugp_adminpermissions['cancontrolpanel']
			);

			// Set can email member's permissions
			$this->run_query(
				sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . "usergroup"),
				"UPDATE " . TABLE_PREFIX . "usergroup
					SET genericpermissions = genericpermissions | " . $this->registry->bf_ugp_genericpermissions['canemailmember'] . "
				WHERE usergroupid NOT IN (1,3,4) AND genericoptions & " . $this->registry->bf_ugp_genericoptions['isnotbannedgroup']
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_102()
	{
		// Alter User Table
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'user', 1, 4),
			'user',
			'ipoints',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_103()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'user', 2, 4),
			'user',
			'infractions',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_104()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'user', 3, 4),
			'user',
			'warnings',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_105()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'deletionlog', 1, 2),
			'deletionlog',
			'dateline',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_106()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'deletionlog', 2, 2),
			'deletionlog',
			'type',
			array('type', 'dateline')
		);
	}

	public function step_107()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'moderation', 1, 3),
			'moderation',
			'dateline',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_108()
	{
		$this->drop_index(
			sprintf($this->phrase['core']['altering_x_table'], 'moderation', 2, 3),
			'moderation',
			'type'
		);
	}

	public function step_109()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'moderation', 3, 3),
			'moderation',
			'type',
			array('type', 'dateline')
		);
	}

	public function step_110()
	{
		if (!$this->field_exists('user', 'infractiongroupids'))
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . "deletionlog"),
				"UPDATE " . TABLE_PREFIX . "deletionlog AS deletionlog, " . TABLE_PREFIX . "thread AS thread
					SET deletionlog.dateline = thread.lastpost
				WHERE deletionlog.primaryid = thread.threadid
					AND deletionlog.type = 'thread'"
			);

			$this->run_query(
				sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . "post"),
				"UPDATE " . TABLE_PREFIX . "deletionlog AS deletionlog, " . TABLE_PREFIX . "post AS post
					SET deletionlog.dateline = post.dateline
				WHERE deletionlog.primaryid = post.postid
					AND deletionlog.type = 'post'"
			);

			$this->run_query(
				sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . "moderation"),
				"UPDATE " . TABLE_PREFIX . "moderation AS moderation, " . TABLE_PREFIX . "post AS post
					SET moderation.dateline = post.dateline
				WHERE moderation.postid = post.postid"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_111()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'user', 4, 4),
			'user',
			'infractiongroupids',
			'varchar',
			array('length' => 255, 'attributes' => self::FIELD_DEFAULTS)
		);
	}

	public function step_112()
	{
		// drop usergroup.pmforwardmax
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], 'usergroup', 1, 1),
			'usergroup',
			'pmforwardmax'
		);
	}

	public function step_113()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'profilefield', 1, 1),
			'profilefield',
			'perline',
			'smallint',
			self::FIELD_DEFAULTS
		);
	}

	public function step_114()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . "profilefield"),
			"UPDATE " . TABLE_PREFIX . "profilefield
				SET perline = def
			WHERE type = 'checkbox'"
		);
	}

	public function step_115()
	{
		if ($this->field_exists('adminhelp', 'optionname'))
		{
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'adminhelp', 1, 1),
				"ALTER TABLE " . TABLE_PREFIX . "adminhelp CHANGE optionname optionname VARCHAR(100) NOT NULL DEFAULT ''"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_116()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'session', 1, 1),
			'session',
			'profileupdate',
			'smallint',
			self::FIELD_DEFAULTS
		);
	}

	public function step_117()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'phrase', 1, 3),
			'phrase',
			'username',
			'varchar',
			array('length' => 100, 'attributes' => self::FIELD_DEFAULTS)
		);
	}

	public function step_118()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'phrase', 2, 3),
			'phrase',
			'dateline',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_119()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'phrase', 3, 3),
			'phrase',
			'version',
			'varchar',
			array('length' => 30, 'attributes' => self::FIELD_DEFAULTS)
		);
	}

	public function step_120()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . "subscriptionpermission"),
			"CREATE TABLE " . TABLE_PREFIX . "subscriptionpermission (
				subscriptionpermissionid int(10) unsigned NOT NULL auto_increment,
				subscriptionid int(10) unsigned NOT NULL default '0',
				usergroupid int(10) unsigned NOT NULL default '0',
				PRIMARY KEY  (subscriptionpermissionid),
				UNIQUE KEY subscriptionid (subscriptionid,usergroupid),
				KEY usergroupid (usergroupid)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_121()
	{
		if (!$this->db->query_first("SELECT * FROM " . TABLE_PREFIX . "paymentapi WHERE classname = 'ccbill'"))
		{
			$ccbill_settings =  array(
				'clientAccnum' => array(
					'type' => 'text',
					'value' => '',
					'validate' => 'string'
				),
				'clientSubacc' => array(
					'type' => 'text',
					'value' => '',
					'validate' => 'string'
				),
				'formName' => array(
					'type' => 'text',
					'value' => '',
					'validate' => 'string'
				),
				'secretword' => array(
					'type' => 'text',
					'value' => '',
					'validate' => 'string'
				),
				'username' => array(
					'type' => 'text',
					'value' => '',
					'validate' => 'string'
				),
				'password' => array(
					'type' => 'text',
					'value' => '',
					'validate' => 'string'
				)
			);

			$this->run_query(
				sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . "paymentapi"),
				"INSERT INTO " . TABLE_PREFIX . "paymentapi
					(title, currency, recurring, classname, active, settings)
				VALUES
					('CCBill', 'usd', 0, 'ccbill', 0, '" . $this->db->escape_string(serialize($ccbill_settings)) . "')"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_122()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'paymentinfo', 1, 1),
			'paymentinfo',
			'hash',
			'hash'
		);
	}

	public function step_123()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'paymenttransaction', 1, 7),
			'paymenttransaction',
			'dateline',
			'int',
			 self::FIELD_DEFAULTS
		);
	}

	public function step_124()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'paymenttransaction', 2, 7),
			'paymenttransaction',
			'paymentapiid',
			'int',
			 self::FIELD_DEFAULTS
		);
	}

	public function step_125()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'paymenttransaction', 3, 7),
			'paymenttransaction',
			'request',
			'mediumtext',
			 self::FIELD_DEFAULTS
		);
	}

	public function step_126()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'paymenttransaction', 4, 7),
			'paymenttransaction',
			'reversed',
			'int',
			 self::FIELD_DEFAULTS
		);
	}

	public function step_127()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'paymenttransaction', 5, 7),
			'paymenttransaction',
			'dateline',
			'dateline'
		);
	}

	public function step_128()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'paymenttransaction', 6, 7),
			'paymenttransaction',
			'transactionid',
			'transactionid'
		);
	}

	public function step_129()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'paymenttransaction', 7, 7),
			'paymenttransaction',
			'paymentapiid',
			'paymentapiid'
		);
	}

	public function step_130()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'subscriptionlog', 1, 2),
			'subscriptionlog',
			'userid',
			array('userid', 'subscriptionid')
		);
	}

	public function step_131()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'subscriptionlog', 2, 2),
			'subscriptionlog',
			'subscriptionid',
			'subscriptionid'
		);
	}

	public function step_132()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'attachmenttype', 1, 1),
			'attachmenttype',
			'enabled',
			'enabled'
		);
	}

	public function step_133()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . "attachmentpermission"),
			"CREATE TABLE " . TABLE_PREFIX . "attachmentpermission (
				attachmentpermissionid INT UNSIGNED NOT NULL AUTO_INCREMENT,
				extension VARCHAR(20) BINARY NOT NULL DEFAULT '',
				usergroupid INT UNSIGNED NOT NULL,
				size INT UNSIGNED NOT NULL,
				width SMALLINT UNSIGNED NOT NULL,
				height SMALLINT UNSIGNED NOT NULL,
				attachmentpermissions INT UNSIGNED NOT NULL,
				PRIMARY KEY  (attachmentpermissionid),
				UNIQUE KEY extension (extension,usergroupid),
				KEY usergroupid (usergroupid)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_134()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'datastore', 1, 1),
			'datastore',
			'unserialize',
			'smallint',
			array('attributes' => 'UNSIGNED', 'null' => false, 'default' => 2)
		);
	}

	public function step_135()
	{
		// create rssfeed table
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . "rssfeed"),
			"CREATE TABLE " . TABLE_PREFIX . "rssfeed (
				rssfeedid INT UNSIGNED NOT NULL AUTO_INCREMENT,
				title VARCHAR(250) NOT NULL,
				url VARCHAR(250) NOT NULL,
				port SMALLINT UNSIGNED NOT NULL DEFAULT '80',
				ttl SMALLINT UNSIGNED NOT NULL DEFAULT '1500',
				maxresults SMALLINT NOT NULL DEFAULT '0',
				userid INT UNSIGNED NOT NULL,
				forumid SMALLINT UNSIGNED NOT NULL,
				iconid SMALLINT UNSIGNED NOT NULL,
				titletemplate MEDIUMTEXT NOT NULL,
				bodytemplate MEDIUMTEXT NOT NULL,
				searchwords MEDIUMTEXT NOT NULL,
				itemtype ENUM('thread','announcement') NOT NULL DEFAULT 'thread',
				threadactiondelay SMALLINT UNSIGNED NOT NULL,
				endannouncement INT UNSIGNED NOT NULL,
				options INT UNSIGNED NOT NULL,
				lastrun INT UNSIGNED NOT NULL,
				PRIMARY KEY  (rssfeedid),
				KEY lastrun (lastrun)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_136()
	{
		// create rsslog table
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . "rsslog"),
			"CREATE TABLE " . TABLE_PREFIX . "rsslog (
				rssfeedid INT UNSIGNED NOT NULL,
				itemid INT UNSIGNED NOT NULL,
				itemtype ENUM('thread','announcement') NOT NULL DEFAULT 'thread',
				uniquehash CHAR(32) NOT NULL,
				contenthash CHAR(32) NOT NULL,
				dateline INT UNSIGNED NOT NULL,
				threadactiontime INT UNSIGNED NOT NULL,
				threadactioncomplete TINYINT UNSIGNED NOT NULL,
				PRIMARY KEY (rssfeedid,itemid,itemtype),
				UNIQUE KEY uniquehash (uniquehash)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_137()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . "datastore"),
			"UPDATE " . TABLE_PREFIX . "datastore
				SET unserialize = 1
				WHERE title IN (
					'options', 'forumcache', 'languagecache', 'stylecache', 'bbcodecache',
					'smiliecache', 'wol_spiders', 'usergroupcache', 'attachmentcache',
					'maxloggedin', 'userstats', 'birthdaycache', 'eventcache', 'iconcache',
					'products', 'pluginlist', 'pluginlistadmin', 'bitfields', 'ranks',
					'noavatarperms', 'acpstats', 'profilefield'
				)
			"
		);
	}

	public function step_138()
	{
		$moderator_permissions = array_sum($this->registry->bf_misc_moderatorpermissions) - ($this->registry->bf_misc_moderatorpermissions['newthreademail'] + $this->registry->bf_misc_moderatorpermissions['newpostemail']);

		$supergroups = $this->db->query_read("
			SELECT user.*, usergroup.usergroupid
			FROM " . TABLE_PREFIX . "usergroup AS usergroup
			INNER JOIN " . TABLE_PREFIX . "user AS user ON(user.usergroupid = usergroup.usergroupid OR FIND_IN_SET(usergroup.usergroupid, user.membergroupids))
			LEFT JOIN " . TABLE_PREFIX . "moderator AS moderator ON(moderator.userid = user.userid AND moderator.forumid = -1)
			WHERE (usergroup.adminpermissions & " . $this->registry->bf_ugp_adminpermissions['ismoderator'] . ") AND moderator.forumid IS NULL
			GROUP BY user.userid
		");
		while ($supergroup = $this->db->fetch_array($supergroups))
		{
			$this->run_query(
				sprintf($this->phrase['version']['360b1']['super_moderator_x_updated'], $supergroup['username']),
				"INSERT INTO " . TABLE_PREFIX . "moderator
					(userid, forumid, permissions)
				VALUES
					($supergroup[userid], -1, $moderator_permissions)"
			);
		}
	}

	public function step_139()
	{
		$this->db->query_write("TRUNCATE TABLE " . TABLE_PREFIX . "postparsed");

		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'postparsed', 1, 8),
			'postparsed',
			'styleid',
			'smallint',
			self::FIELD_DEFAULTS
		);
	}

	public function step_140()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'postparsed', 2, 8),
			'postparsed',
			'languageid',
			'smallint',
			self::FIELD_DEFAULTS
		);
	}

	public function step_141()
	{
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], 'postparsed', 3, 8),
			'postparsed',
			'styleid_code'
		);
	}

	public function step_142()
	{
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], 'postparsed', 4, 8),
			'postparsed',
			'styleid_html'
		);
	}

	public function step_143()
	{
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], 'postparsed', 5, 8),
			'postparsed',
			'styleid_php'
		);
	}

	public function step_144()
	{
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], 'postparsed', 6, 8),
			'postparsed',
			'styleid_quote'
		);
	}

	public function step_145()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'postparsed', 7, 8),
			"ALTER TABLE " . TABLE_PREFIX . "postparsed DROP PRIMARY KEY",
			self::MYSQL_ERROR_DROP_KEY_COLUMN_MISSING
		);
	}

	public function step_146()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'postparsed', 8, 8),
			"ALTER TABLE " . TABLE_PREFIX . "postparsed ADD PRIMARY KEY (postid, styleid, languageid)",
			self::MYSQL_ERROR_PRIMARY_KEY_EXISTS
		);
	}

	public function step_147()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'attachment', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "attachment CHANGE extension extension VARCHAR(20) BINARY NOT NULL DEFAULT ''"
		);
	}

	public function step_148()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'attachmenttype', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "attachmenttype CHANGE extension extension VARCHAR(20) BINARY NOT NULL DEFAULT ''"
		);
	}

	public function step_149()
	{
		$this->show_message($this->phrase['core']['cache_update']);
		// Update hidden profile cache to handle hidden AND required fields
		require_once(DIR . '/includes/adminfunctions_profilefield.php');
		build_profilefield_cache();
		$this->db->query_write("DELETE FROM " . TABLE_PREFIX . "datastore WHERE title = 'hidprofilecache'");
	}
}

class vB_Upgrade_360b2 extends vB_Upgrade_Version
{
	public function step_1()
	{
		if (!$this->field_exists('adminmessage', 'adminmessageid'))
		{
			// Make sure to zero out permissions from possible past usage
			$newperms = array(
				'genericpermissions' => array(
					$this->registry->bf_ugp_genericpermissions['cangivearbinfraction'],
			));

			foreach ($newperms AS $permission => $permissions)
			{
				$this->run_query(
					sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . "usergroup"),
					"UPDATE " . TABLE_PREFIX . "usergroup SET $permission = $permission & ~" . (array_sum($permissions))
				);
			}
			// give arbitrary infraction perms to admins
			$this->run_query(
				sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . "usergroup"),
				"UPDATE " . TABLE_PREFIX . "usergroup
					SET genericpermissions = genericpermissions | " . $this->registry->bf_ugp_genericpermissions['cangivearbinfraction'] ."
				WHERE adminpermissions & " . $this->registry->bf_ugp_adminpermissions['cancontrolpanel']
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_2()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . "adminmessage"),
			"CREATE TABLE " . TABLE_PREFIX . "adminmessage (
				adminmessageid INT UNSIGNED NOT NULL AUTO_INCREMENT,
				varname varchar(250) NOT NULL DEFAULT '',
				dismissable SMALLINT UNSIGNED NOT NULL DEFAULT '0',
				script varchar(50) NOT NULL DEFAULT '',
				action varchar(20) NOT NULL DEFAULT '',
				execurl mediumtext NOT NULL,
				method enum('get','post') NOT NULL DEFAULT 'post',
				dateline INT UNSIGNED NOT NULL DEFAULT '0',
				status enum('undone','done','dismissed') NOT NULL default 'undone',
				statususerid INT UNSIGNED NOT NULL DEFAULT '0',
				args MEDIUMTEXT,
				PRIMARY KEY (adminmessageid),
				KEY script_action (script, action)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_3()
	{
		$this->add_adminmessage(
			'after_upgrade_36_update_counters',
			array(
				'dismissable' => 1,
				'script'      => 'misc.php',
				'action'      => 'updatethread',
				'execurl'     => 'misc.php?do=updatethread',
				'method'      => 'get',
				'status'      => 'undone',
			)
		);
	}


	public function step_4()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'adminlog', 1, 1),
			'adminlog',
			'script_action',
			array('script', 'action')
		);
	}

	public function step_5()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'attachment', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "attachment CHANGE extension extension VARCHAR(20) BINARY NOT NULL DEFAULT ''"
		);
	}

	public function step_6()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'attachmenttype', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "attachmenttype CHANGE extension extension VARCHAR(20) BINARY NOT NULL DEFAULT ''"
		);
	}

	public function step_7()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'attachmentpermission', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "attachmentpermission CHANGE extension extension VARCHAR(20) BINARY NOT NULL DEFAULT ''"
		);
	}

	public function step_8()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'user', 1, 1),
			'user',
			'sigpicrevision',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_9()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'infraction', 1, 1),
			'infraction',
			'customreason',
			'varchar',
			array('length' => 255, 'attributes' => self::FIELD_DEFAULTS)
		);
	}
}

class vB_Upgrade_360b4 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . "threadredirect"),
			"CREATE TABLE " . TABLE_PREFIX . "threadredirect (
				threadid INT UNSIGNED NOT NULL DEFAULT '0',
				expires INT UNSIGNED NOT NULL DEFAULT '0',
				PRIMARY KEY (threadid),
				KEY expires (expires)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}
}

class vB_Upgrade_360rc1 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'adminmessage', 1, 1),
			'adminmessage',
			'varname',
			'varname'
		);
	}

	public function step_2()
	{
		$this->add_adminmessage(
			'after_upgrade_360rc1',
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

class vB_Upgrade_360rc2 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . "podcasturl"),
			"CREATE TABLE " . TABLE_PREFIX . "podcasturl (
				postid INT UNSIGNED NOT NULL DEFAULT '0',
				url VARCHAR(255) NOT NULL DEFAULT '',
				length INT UNSIGNED NOT NULL DEFAULT '0',
				PRIMARY KEY  (postid)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}
}

class vB_Upgrade_360rc3 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'infractiongroup', 1, 1),
			'infractiongroup',
			'override',
			'smallint',
			self::FIELD_DEFAULTS
		);
	}

	public function step_2()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'user', 1, 1),
			'user',
			'infractiongroupid',
			'smallint',
			self::FIELD_DEFAULTS
		);
	}
}

class vB_Upgrade_361 extends vB_Upgrade_Version
{
	public function step_1()
	{
		if (!$this->field_exists('infractionlevel', 'extend'))
		{
			$avatarids = array();
			$avatars = $this->db->query_read("
				SELECT userid
				FROM " . TABLE_PREFIX . "customavatar
			");
			while ($avatar = $this->db->fetch_array($avatars))
			{
				$avatarids[] = $avatar['userid'];
			}

			if ($avatarids)
			{
				$adminAvtOpt = 0;
				if (!empty($this->registry->bf_misc_adminoptions['adminavatar']))
				{
					$adminAvtOpt = $this->registry->bf_misc_adminoptions['adminavatar'];
				}
				$this->run_query(
					sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . "user"),
					"UPDATE " . TABLE_PREFIX . "user
					SET adminoptions = adminoptions | " . $adminAvtOpt . "
					WHERE userid IN (" . implode(',', $avatarids) . ")"
				);
			}

			if (!$avatarids)
			{
				$this->skip_message();
			}
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_2()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'infractionlevel', 1, 1),
			'infractionlevel',
			'extend',
			'smallint',
			self::FIELD_DEFAULTS
		);
	}

	public function step_3()
	{
		if (!$this->field_exists('podcastitem', 'explicit'))
		{
			$this->add_field(
				sprintf($this->phrase['core']['altering_x_table'], 'podcasturl', 1, 4),
				'podcasturl',
				'explicit',
				'smallint',
				self::FIELD_DEFAULTS
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_4()
	{
		if (!$this->field_exists('podcastitem', 'explicit'))
		{
			$this->add_field(
				sprintf($this->phrase['core']['altering_x_table'], 'podcasturl', 2, 4),
				'podcasturl',
				'keywords',
				'varchar',
				array('length' => 255, 'attributes' => self::FIELD_DEFAULTS)
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_5()
	{
		if (!$this->field_exists('podcastitem', 'explicit'))
		{
			$this->add_field(
				sprintf($this->phrase['core']['altering_x_table'], 'podcasturl', 3, 4),
				'podcasturl',
				'subtitle',
				'varchar',
				array('length' => 255, 'attributes' => self::FIELD_DEFAULTS)
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_6()
	{
		if (!$this->field_exists('podcastitem', 'explicit'))
		{
			$this->add_field(
				sprintf($this->phrase['core']['altering_x_table'], 'podcasturl', 4, 4),
				'podcasturl',
				'author',
				'varchar',
				array('length' => 255, 'attributes' => self::FIELD_DEFAULTS)
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_7()
	{
		if (!$this->field_exists('podcastitem', 'explicit'))
		{
			$this->run_query(
				 sprintf($this->phrase['version']['361']['rename_podcasturl'], TABLE_PREFIX),
				 "ALTER TABLE " . TABLE_PREFIX . "podcasturl RENAME " . TABLE_PREFIX . "podcastitem",
				 self::MYSQL_ERROR_TABLE_MISSING
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_8()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'administrator', 1, 1),
			'administrator',
			'dismissednews',
			'text',
			self::FIELD_DEFAULTS
		);
	}

	public function step_9()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . "infractionban"),
			"CREATE TABLE " . TABLE_PREFIX . "infractionban (
				infractionbanid int unsigned NOT NULL auto_increment,
				usergroupid int NOT NULL DEFAULT '0',
				banusergroupid int unsigned NOT NULL DEFAULT '0',
				amount int unsigned NOT NULL DEFAULT '0',
				period char(5) NOT NULL DEFAULT '',
				method enum('points','infractions') NOT NULL default 'infractions',
				PRIMARY KEY (infractionbanid),
				KEY usergroupid (usergroupid)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_10()
	{
		$changed_strip = false;

		if (strpos($this->registry->options['blankasciistrip'], 'u8204') === false)
		{
			$this->registry->options['blankasciistrip'] .= ' u8204 u8205';
			$changed_strip = true;
		}
		if (strpos($this->registry->options['blankasciistrip'], 'u8237') === false)
		{
			$this->registry->options['blankasciistrip'] .= ' u8237 u8238';
			$changed_strip = true;
		}

		if ($changed_strip)
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . "setting"),
				"UPDATE " . TABLE_PREFIX . "setting SET
					value = '" . $this->db->escape_string($this->registry->options['blankasciistrip']) . "'
				WHERE varname = 'blankasciistrip'"
			);
		}
		else
		{
			$this->skip_message();
		}
	}
}

class vB_Upgrade_364 extends vB_Upgrade_Version
{
	public function step_1()
	{
		//Note that for later 4.X versions this table is not installed.
		if ($this->tableExists('search'))
		{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'search', 1, 1),
			'search',
			'completed',
			'smallint',
			array('null' => false, 'default' => 1)
		);
	}
	}
}

class vB_Upgrade_365 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . "user"),
			"UPDATE " . TABLE_PREFIX . "user SET
				infractiongroupids = '',
				infractiongroupid = 0
			WHERE
				ipoints = 0
			AND
				(infractiongroupids <> '' OR infractiongroupid <> 0)"
		);
	}
}

class vB_Upgrade_366 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'avatar', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "avatar CHANGE minimumposts minimumposts INT UNSIGNED NOT NULL DEFAULT '0'"
		);
	}

	public function step_2()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'ranks', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "`ranks` CHANGE minposts minposts INT UNSIGNED NOT NULL DEFAULT '0'"
		);
	}

	public function step_3()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'usertitle', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "usertitle CHANGE minposts minposts INT UNSIGNED NOT NULL DEFAULT '0'"
		);
	}

	public function step_4()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'calendar', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "calendar CHANGE neweventemail neweventemail TEXT"
		);
	}

	public function step_5()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'forum', 1, 2),
			"ALTER TABLE " . TABLE_PREFIX . "forum CHANGE newpostemail newpostemail TEXT"
		);
	}

	public function step_6()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'forum', 2, 2),
			"ALTER TABLE " . TABLE_PREFIX . "forum CHANGE newthreademail newthreademail TEXT"
		);
	}

	public function step_7()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'datastore', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "datastore CHANGE title title VARCHAR(50) NOT NULL DEFAULT ''"
		);
	}

	public function step_8()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . "userlist"),
			"CREATE TABLE " . TABLE_PREFIX . "userlist (
				userid INT UNSIGNED NOT NULL DEFAULT '0',
				relationid INT UNSIGNED NOT NULL DEFAULT '0',
				type ENUM('buddy', 'ignore') NOT NULL DEFAULT 'buddy',
				PRIMARY KEY (userid, relationid, type),
				KEY userid (relationid)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_9()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . "profilefieldcategory"),
			"CREATE TABLE " . TABLE_PREFIX . "profilefieldcategory (
				profilefieldcategoryid SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
				displayorder SMALLINT UNSIGNED NOT NULL,
				PRIMARY KEY (profilefieldcategoryid)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_10()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'profilefield', 1, 2),
			'profilefield',
			'profilefieldcategoryid',
			'smallint',
			self::FIELD_DEFAULTS
		);
	}

	public function step_11()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'profilefield', 2, 2),
			'profilefield',
			'profilefieldcategoryid',
			'profilefieldcategoryid'
		);
	}

	public function step_12()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'externalcache', 1, 2),
			'externalcache',
			'forumid',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_13()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'externalcache', 2, 2),
			'externalcache',
			'forumid',
			'forumid'
		);
	}

	public function step_14()
	{
		$this->drop_index(
			sprintf($this->phrase['core']['altering_x_table'], 'template', 1, 2),
			'template',
			'title'
		);
	}

	public function step_15()
	{
		$skip = true;
		/* this deals with the older templates */
		$badtemplates = $this->db->query_read("
			SELECT styleid, title, templatetype, MAX(dateline) AS newest, COUNT(*) AS total
			FROM " . TABLE_PREFIX . "template
			GROUP BY styleid, title, templatetype
			HAVING total > 1
		");
		while ($template = $this->db->fetch_array($badtemplates))
		{
			$skip = false;
			$this->run_query(
				sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . "user"),
				"DELETE FROM " . TABLE_PREFIX . "template
				WHERE styleid = $template[styleid]
					AND title = '" . $this->db->escape_string($template['title']) . "'
					AND templatetype = '" . $this->db->escape_string($template['templatetype']) . "'
					AND dateline < " . intval($template['newest'])
			);
		}
		if ($skip)
		{
			$this->skip_message();
		}
	}

	public function step_16()
	{
		$skip = true;
		/* now to deal with those that have the same date */
		$badtemplates = $this->db->query_read("
			SELECT styleid, title, templatetype, MAX(templateid) AS newest, COUNT(*) AS total
			FROM " . TABLE_PREFIX . "template
			GROUP BY styleid, title, templatetype
			HAVING total > 1
		");
		while ($template = $this->db->fetch_array($badtemplates))
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . "user"),
				"DELETE FROM " . TABLE_PREFIX . "template
				WHERE styleid = $template[styleid]
					AND title = '" . $this->db->escape_string($template['title']) . "'
					AND templatetype = '" . $this->db->escape_string($template['templatetype']) . "'
					AND templateid <> " . intval($template['newest'])
			);
			$skip = false;
		}
		if ($skip)
		{
			$this->skip_message();
		}
	}

	public function step_17()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'template', 2, 2),
			'template',
			'title',
			array('title', 'styleid', 'templatetype'),
			'unique'
		);
	}
}

class vB_Upgrade_367 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->show_message($this->phrase['version']['367']['remove_calendar_xss_fix']);
		require_once(DIR . '/includes/adminfunctions_product.php');
		delete_product('vb_calendar366_xss_fix', true);
	}
}

class vB_Upgrade_368 extends vB_Upgrade_Version
{
	public function step_1()
	{
		for ($x = 1; $x < 6; $x++)
		{
			$this->add_field(
				sprintf($this->phrase['core']['altering_x_table'], 'moderatorlog', $x, 9),
				'moderatorlog',
				"id$x",
				'int',
				self::FIELD_DEFAULTS
			);
		}
	}

	public function step_2()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'moderatorlog', 6, 9),
			'moderatorlog',
			'product',
			'varchar',
			array(
				'length'     => 25,
				'attributes' => self::FIELD_DEFAULTS
		));
	}

	public function step_3()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'moderatorlog', 7, 9),
			'moderatorlog',
			'product',
			'product'
		);
	}


	public function step_4()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'moderatorlog', 8, 9),
			'moderatorlog',
			'id1',
			'id1'
		);
	}

	public function step_5()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'moderatorlog', 9, 9),
			'moderatorlog',
			'id2',
			'id2'
		);
	}
}

class vB_Upgrade_370b2 extends vB_Upgrade_Version
{
	public $PREV_VERSION = '3.6.8+';
	public $VERSION_COMPAT_STARTS = '3.6.8';
	public $VERSION_COMPAT_ENDS   = '3.6.99';

	public function step_1()
	{
		if (!isset($this->registry->bf_misc_moderatorpermissions2['caneditvisitormessages']))
		{
			$this->add_error($this->phrase['core']['wrong_bitfield_xml'], self::PHP_TRIGGER_ERROR, true);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_2()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'userchangelog'),
			"CREATE TABLE " . TABLE_PREFIX . "userchangelog (
				changeid INT UNSIGNED NOT NULL AUTO_INCREMENT,
				userid INT UNSIGNED NOT NULL DEFAULT '0',
				fieldname VARCHAR(250) NOT NULL DEFAULT '',
				newvalue VARCHAR(250) NOT NULL DEFAULT '',
				oldvalue VARCHAR(250) NOT NULL DEFAULT '',
				adminid INT UNSIGNED NOT NULL DEFAULT '0',
				change_time INT UNSIGNED NOT NULL DEFAULT '0',
				change_uniq VARCHAR(32) NOT NULL DEFAULT '',
				PRIMARY KEY  (changeid),
				KEY userid (userid,change_time),
				KEY change_time (change_time),
				KEY change_uniq (change_uniq),
				KEY fieldname (fieldname,change_time),
				KEY adminid (adminid,change_time)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_3()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . "forumprefix"),
			"CREATE TABLE " . TABLE_PREFIX . "forumprefixset (
				forumid INT UNSIGNED NOT NULL DEFAULT '0',
				prefixsetid VARCHAR(25) NOT NULL DEFAULT '',
				PRIMARY KEY (forumid, prefixsetid)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_4()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . "socialgroup"),
			"CREATE TABLE " . TABLE_PREFIX . "socialgroup (
				groupid INT unsigned NOT NULL auto_increment,
				name VARCHAR(255) NOT NULL DEFAULT '',
				description TEXT,
				creatoruserid INT unsigned NOT NULL DEFAULT '0',
				dateline INT unsigned NOT NULL DEFAULT '0',
				members INT unsigned NOT NULL DEFAULT '0',
				picturecount INT unsigned NOT NULL DEFAULT '0',
				lastpost INT unsigned NOT NULL DEFAULT '0',
				lastposter VARCHAR(255) NOT NULL DEFAULT '',
				lastposterid INT UNSIGNED NOT NULL DEFAULT '0',
				lastgmid INT UNSIGNED NOT NULL DEFAULT '0',
				visible INT UNSIGNED NOT NULL DEFAULT '0',
				deleted INT UNSIGNED NOT NULL DEFAULT '0',
				moderation INT UNSIGNED NOT NULL DEFAULT '0',
				PRIMARY KEY  (groupid),
				KEY creatoruserid (creatoruserid),
				KEY dateline (dateline),
				FULLTEXT KEY name (name, description)
			) ENGINE=MyISAM",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_5()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . "socialgroupmember"),
			"CREATE TABLE " . TABLE_PREFIX . "socialgroupmember (
				userid INT unsigned NOT NULL DEFAULT '0',
				groupid INT unsigned NOT NULL DEFAULT '0',
				dateline INT unsigned NOT NULL DEFAULT '0',
				PRIMARY KEY (groupid, userid),
				KEY userid (userid)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_6()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . "socialgrouppicture"),
			"CREATE TABLE " . TABLE_PREFIX . "socialgrouppicture (
				groupid INT UNSIGNED NOT NULL DEFAULT '0',
				pictureid INT UNSIGNED NOT NULL DEFAULT '0',
				dateline INT UNSIGNED NOT NULL DEFAULT '0',
				PRIMARY KEY (groupid, pictureid),
				KEY groupid (groupid, dateline),
				KEY pictureid (pictureid)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_7()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . "prefix"),
			"CREATE TABLE " . TABLE_PREFIX . "prefix (
				prefixid VARCHAR(25) NOT NULL DEFAULT '',
				prefixsetid VARCHAR(25) NOT NULL DEFAULT '',
				displayorder INT UNSIGNED NOT NULL DEFAULT '0',
				PRIMARY KEY (prefixid),
				KEY prefixsetid (prefixsetid, displayorder)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_8()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . "prefixset"),
			"CREATE TABLE " . TABLE_PREFIX . "prefixset (
				prefixsetid VARCHAR(25) NOT NULL DEFAULT '',
				displayorder INT UNSIGNED NOT NULL DEFAULT '0',
				PRIMARY KEY (prefixsetid)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_9()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'notice'),
			"CREATE TABLE " . TABLE_PREFIX . "notice (
				noticeid INT UNSIGNED NOT NULL auto_increment,
				title VARCHAR(250) NOT NULL DEFAULT '',
				displayorder INT UNSIGNED NOT NULL DEFAULT '0',
				persistent SMALLINT UNSIGNED NOT NULL default '0',
				active SMALLINT UNSIGNED NOT NULL DEFAULT '0',
				PRIMARY KEY (noticeid),
				KEY active (active)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_10()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'noticecriteria'),
			"CREATE TABLE " . TABLE_PREFIX . "noticecriteria (
				noticeid INT UNSIGNED NOT NULL DEFAULT '0',
				criteriaid VARCHAR(191) NOT NULL DEFAULT '',
				condition1 VARCHAR(250) NOT NULL DEFAULT '',
				condition2 VARCHAR(250) NOT NULL DEFAULT '',
				condition3 VARCHAR(250) NOT NULL DEFAULT '',
				PRIMARY KEY (noticeid,criteriaid)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_11()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'postlog'),
			"CREATE TABLE " . TABLE_PREFIX . "postlog (
				postid INT UNSIGNED NOT NULL DEFAULT '0',
				useragent CHAR(100) NOT NULL DEFAULT '',
				ip INT UNSIGNED NOT NULL DEFAULT '0',
				dateline INT UNSIGNED NOT NULL DEFAULT '0',
				PRIMARY KEY (postid),
				KEY dateline (dateline),
				KEY ip (ip)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_12()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'spamlog'),
			"CREATE TABLE " . TABLE_PREFIX . "spamlog (
				postid INT UNSIGNED NOT NULL DEFAULT '0',
				PRIMARY KEY (postid)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_13()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'bookmarksite'),
			"CREATE TABLE " . TABLE_PREFIX . "bookmarksite (
				bookmarksiteid INT UNSIGNED NOT NULL AUTO_INCREMENT,
				title VARCHAR(250) NOT NULL DEFAULT '',
				iconpath VARCHAR(250) NOT NULL DEFAULT '',
				active  SMALLINT UNSIGNED NOT NULL DEFAULT '0',
				displayorder INT UNSIGNED NOT NULL DEFAULT '0',
				url VARCHAR(250) NOT NULL DEFAULT '',
				PRIMARY KEY (bookmarksiteid)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_14()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'tag'),
			"CREATE TABLE " . TABLE_PREFIX . "tag (
				tagid INT UNSIGNED NOT NULL AUTO_INCREMENT,
				tagtext VARCHAR(100) NOT NULL DEFAULT '',
				dateline INT UNSIGNED NOT NULL DEFAULT '0',
				PRIMARY KEY (tagid),
				UNIQUE KEY tagtext (tagtext)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_15()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'tagthread'),
			"CREATE TABLE " . TABLE_PREFIX . "tagthread (
				tagid INT UNSIGNED NOT NULL DEFAULT '0',
				threadid INT UNSIGNED NOT NULL DEFAULT '0',
				userid INT UNSIGNED NOT NULL DEFAULT '0',
				dateline INT UNSIGNED NOT NULL DEFAULT '0',
				PRIMARY KEY (tagid, threadid),
				KEY threadid (threadid, userid),
				KEY dateline (dateline)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_16()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'tagsearch'),
			"CREATE TABLE " . TABLE_PREFIX . "tagsearch (
				tagid INT UNSIGNED NOT NULL DEFAULT '0',
				dateline INT UNSIGNED NOT NULL DEFAULT '0',
				KEY (tagid)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_17()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'postedithistory'),
			"CREATE TABLE " . TABLE_PREFIX . "postedithistory (
				postedithistoryid INT UNSIGNED NOT NULL AUTO_INCREMENT,
				postid INT UNSIGNED NOT NULL DEFAULT '0',
				userid INT UNSIGNED NOT NULL DEFAULT '0',
				username VARCHAR(100) NOT NULL DEFAULT '',
				title VARCHAR(250) NOT NULL DEFAULT '',
				iconid INT UNSIGNED NOT NULL DEFAULT '0',
				dateline INT UNSIGNED NOT NULL DEFAULT '0',
				reason VARCHAR(200) NOT NULL DEFAULT '',
				original SMALLINT NOT NULL DEFAULT '0',
				pagetext MEDIUMTEXT,
				PRIMARY KEY  (postedithistoryid),
				KEY postid (postid,userid)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_18()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'usercss'),
			"CREATE TABLE " . TABLE_PREFIX . "usercss (
				userid INT UNSIGNED NOT NULL DEFAULT '0',
				selector VARCHAR(30) NOT NULL DEFAULT '',
				property VARCHAR(30) NOT NULL DEFAULT '',
				value VARCHAR(255) NOT NULL DEFAULT '',
				PRIMARY KEY (userid, selector, property),
				KEY property (property, userid, value(20))
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_19()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'usercsscache'),
			"CREATE TABLE " . TABLE_PREFIX . "usercsscache (
				userid INT UNSIGNED NOT NULL DEFAULT '0',
				cachedcss TEXT,
				buildpermissions INT UNSIGNED NOT NULL DEFAULT '0',
				PRIMARY KEY (userid)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_20()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'paymentapi'),
			"UPDATE " . TABLE_PREFIX . "paymentapi SET currency = 'usd,gbp,eur,aud,cad' WHERE classname = 'moneybookers'"
		);
	}

	public function step_21()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'tachyforumcounter'),
			"CREATE TABLE " . TABLE_PREFIX . "tachyforumcounter (
				userid int(10) unsigned NOT NULL default '0',
				forumid smallint(5) unsigned NOT NULL default '0',
				threadcount mediumint(8) unsigned NOT NULL default '0',
				replycount int(10) unsigned NOT NULL default '0',
				PRIMARY KEY  (userid,forumid)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_22()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'tachythreadcounter'),
			"CREATE TABLE " . TABLE_PREFIX . "tachythreadcounter (
				userid int(10) unsigned NOT NULL default '0',
				threadid int(10) unsigned NOT NULL default '0',
				replycount int(10) unsigned NOT NULL default '0',
				PRIMARY KEY  (userid,threadid)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_23()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'profilevisitor'),
			"CREATE TABLE " . TABLE_PREFIX . "profilevisitor (
				userid INT UNSIGNED NOT NULL DEFAULT '0',
				visitorid INT UNSIGNED NOT NULL DEFAULT '0',
				dateline INT UNSIGNED NOT NULL DEFAULT '0',
				visible SMALLINT UNSIGNED NOT NULL DEFAULT '1',
				PRIMARY KEY (visitorid, userid),
				KEY userid (userid, visible, dateline)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_24()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'visitormessage'),
			"CREATE TABLE " . TABLE_PREFIX . "visitormessage (
			  vmid INT UNSIGNED NOT NULL auto_increment,
				userid INT UNSIGNED NOT NULL DEFAULT '0',
				postuserid INT UNSIGNED NOT NULL DEFAULT '0',
				postusername VARCHAR(100) NOT NULL  DEFAULT '',
				dateline INT UNSIGNED NOT NULL DEFAULT '0',
				state ENUM('visible','moderation','deleted') NOT NULL default 'visible',
				title VARCHAR(255) NOT NULL DEFAULT '',
				pagetext MEDIUMTEXT,
				ipaddress INT UNSIGNED NOT NULL DEFAULT '0',
				allowsmilie SMALLINT UNSIGNED NOT NULL DEFAULT '0',
				reportthreadid INT UNSIGNED NOT NULL DEFAULT '0',
				messageread SMALLINT UNSIGNED NOT NULL DEFAULT '0',
			  PRIMARY KEY  (vmid),
				KEY postuserid (postuserid, userid, state),
				KEY userid (userid, dateline, state)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_25()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'visitormessage_hash'),
			"CREATE TABLE " . TABLE_PREFIX . "visitormessage_hash (
				postuserid INT UNSIGNED NOT NULL DEFAULT '0',
				userid INT UNSIGNED NOT NULL DEFAULT '0',
				dupehash VARCHAR(32) NOT NULL DEFAULT '',
				dateline INT UNSIGNED NOT NULL DEFAULT '0',
				KEY postuserid (postuserid, dupehash),
				KEY dateline (dateline)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_26()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'groupmessage'),
			"CREATE TABLE " . TABLE_PREFIX . "groupmessage (
				gmid INT UNSIGNED NOT NULL auto_increment,
				groupid INT UNSIGNED NOT NULL DEFAULT '0',
				postuserid INT UNSIGNED NOT NULL DEFAULT '0',
				postusername VARCHAR(100) NOT NULL DEFAULT '',
				dateline INT UNSIGNED NOT NULL DEFAULT '0',
				state ENUM('visible','moderation','deleted') NOT NULL default 'visible',
				title VARCHAR(255) NOT NULL DEFAULT '',
				pagetext MEDIUMTEXT,
				ipaddress INT UNSIGNED NOT NULL DEFAULT '0',
				allowsmilie SMALLINT UNSIGNED NOT NULL DEFAULT '0',
				reportthreadid INT UNSIGNED NOT NULL DEFAULT '0',
				PRIMARY KEY  (gmid),
				KEY postuserid (postuserid, groupid, state),
				KEY groupid (groupid, dateline, state)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_27()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'groupmessage_hash'),
			"CREATE TABLE " . TABLE_PREFIX . "groupmessage_hash (
				postuserid INT UNSIGNED NOT NULL DEFAULT '0',
				groupid INT UNSIGNED NOT NULL DEFAULT '0',
				dupehash VARCHAR(32) NOT NULL DEFAULT '',
				dateline INT UNSIGNED NOT NULL DEFAULT '0',
				KEY postuserid (postuserid, dupehash),
				KEY dateline (dateline)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_28()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'album'),
			"CREATE TABLE " . TABLE_PREFIX . "album (
				albumid INT UNSIGNED NOT NULL AUTO_INCREMENT,
				userid INT UNSIGNED NOT NULL DEFAULT '0',
				createdate INT UNSIGNED NOT NULL DEFAULT '0',
				lastpicturedate INT UNSIGNED NOT NULL DEFAULT '0',
				picturecount INT UNSIGNED NOT NULL DEFAULT '0',
				title VARCHAR(100) NOT NULL DEFAULT '',
				description TEXT,
				public SMALLINT UNSIGNED NOT NULL DEFAULT '1',
				coverpictureid INT UNSIGNED NOT NULL DEFAULT '0',
				PRIMARY KEY (albumid),
				KEY userid (userid, lastpicturedate)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_29()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'albumpicture'),
			"CREATE TABLE " . TABLE_PREFIX . "albumpicture (
				albumid INT UNSIGNED NOT NULL DEFAULT '0',
				pictureid INT UNSIGNED NOT NULL DEFAULT '0',
				dateline INT UNSIGNED NOT NULL DEFAULT '0',
				PRIMARY KEY (albumid, pictureid),
				KEY albumid (albumid, dateline),
				KEY pictureid (pictureid)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_30()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'picture'),
			"CREATE TABLE " . TABLE_PREFIX . "picture (
				pictureid INT UNSIGNED NOT NULL AUTO_INCREMENT,
				userid INT UNSIGNED NOT NULL DEFAULT '0',
				caption TEXT,
				extension VARCHAR(20) NOT NULL DEFAULT '',
				filedata MEDIUMBLOB,
				filesize INT UNSIGNED NOT NULL DEFAULT '0',
				width SMALLINT UNSIGNED NOT NULL DEFAULT '0',
				height SMALLINT UNSIGNED NOT NULL DEFAULT '0',
				thumbnail MEDIUMBLOB,
				thumbnail_filesize INT UNSIGNED NOT NULL DEFAULT '0',
				thumbnail_width SMALLINT UNSIGNED NOT NULL DEFAULT '0',
				thumbnail_height SMALLINT UNSIGNED NOT NULL DEFAULT '0',
				thumbnail_dateline INT UNSIGNED NOT NULL DEFAULT '0',
				idhash VARCHAR(32) NOT NULL DEFAULT '',
				reportthreadid INT UNSIGNED NOT NULL DEFAULT '0',
				PRIMARY KEY (pictureid),
				KEY userid (userid)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_31()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'humanverify'),
			"CREATE TABLE " . TABLE_PREFIX . "humanverify (
				hash CHAR(32) NOT NULL DEFAULT '',
				answer MEDIUMTEXT,
				dateline INT UNSIGNED NOT NULL DEFAULT '0',
				viewed SMALLINT UNSIGNED NOT NULL DEFAULT '0',
				KEY hash (hash),
				KEY dateline (dateline)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_32()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'hvanswer'),
			"CREATE TABLE " . TABLE_PREFIX . "hvanswer (
	 			answerid INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	 			questionid INT NOT NULL DEFAULT '0',
	 			answer VARCHAR(255) NOT NULL DEFAULT '',
	 			dateline INT UNSIGNED NOT NULL DEFAULT '0',
	 			INDEX (questionid)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_33()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'hvquestion'),
			"CREATE TABLE " . TABLE_PREFIX . "hvquestion (
	 			questionid INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	 			regex VARCHAR(255) NOT NULL DEFAULT '',
	 			dateline INT UNSIGNED NOT NULl DEFAULT '0'
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_34()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'thread', 1, 2),
			'thread',
			'prefixid',
			'varchar',
			array('length' => 25, 'attributes' => self::FIELD_DEFAULTS)
		);
	}

	public function step_35()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'thread', 2, 3),
			'thread',
			'prefixid',
			array('prefixid', 'forumid')
		);
	}

	public function step_36()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'thread', 3, 3),
			'thread',
			'taglist',
			'mediumtext',
			self::FIELD_DEFAULTS
		);
	}

	public function step_37()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'deletionlog', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "deletionlog CHANGE type type ENUM('post', 'thread', 'visitormessage', 'groupmessage') NOT NULL DEFAULT 'post'"
		);
	}

	public function step_38()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'customavatar', 1, 3),
			'customavatar',
			'filedata_thumb',
			'mediumblob',
			self::FIELD_DEFAULTS
		);
	}

	public function step_39()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'customavatar', 2, 3),
			'customavatar',
			'width_thumb',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_40()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'customavatar', 3, 3),
			'customavatar',
			'height_thumb',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_41()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'forum', 1, 2),
			'forum',
			'lastprefixid',
			'varchar',
			array('length' => 25, 'attributes' => self::FIELD_DEFAULTS)
		);
	}

	public function step_42()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'forum', 2, 2),
			'forum',
			'imageprefix',
			'varchar',
			array('length' => 100, 'attributes' => self::FIELD_DEFAULTS)
		);
	}

	public function step_43()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'tachyforumpost', 1, 1),
			'tachyforumpost',
			'lastprefixid',
			'varchar',
			array('length' => 25, 'attributes' => self::FIELD_DEFAULTS)
		);
	}

	public function step_44()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'rssfeed', 1, 1),
			'rssfeed',
			'prefixid',
			'varchar',
			array('length' => 25, 'attributes' => self::FIELD_DEFAULTS)
		);
	}

	public function step_45()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'search', 1, 1),
			'search',
			'prefixchoice',
			'mediumtext',
			self::FIELD_DEFAULTS
		);
	}

	public function step_46()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'language', 1, 5),
			'language',
			'phrasegroup_prefix',
			'mediumtext',
			self::FIELD_DEFAULTS
		);
	}

	public function step_47()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'language', 2, 5),
			'language',
			'phrasegroup_socialgroups',
			'mediumtext',
			self::FIELD_DEFAULTS
		);
	}

	public function step_48()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'language', 3, 5),
			'language',
			'phrasegroup_prefixadmin',
			'mediumtext',
			self::FIELD_DEFAULTS
		);
	}

	public function step_49()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'language', 4, 5),
			'language',
			'phrasegroup_notice',
			'mediumtext',
			self::FIELD_DEFAULTS
		);
	}

	public function step_50()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'language', 5, 5),
			'language',
			'phrasegroup_album',
			'mediumtext',
			self::FIELD_DEFAULTS
		);
	}

	public function step_51()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'editlog', 1, 1),
			'editlog',
			'hashistory',
			'SMALLINT',
			self::FIELD_DEFAULTS
		);
	}

	public function step_52()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'userlist', 1, 5),
			"ALTER  TABLE  " . TABLE_PREFIX . "userlist ADD friend ENUM('yes', 'no', 'pending', 'denied') NOT NULL DEFAULT 'no'",
			self::MYSQL_ERROR_COLUMN_EXISTS
		);
	}

	public function step_53()
	{
		$this->drop_index(
			sprintf($this->phrase['core']['altering_x_table'], 'userlist', 2, 5),
			'userlist',
			'relationid'
		);
	}

	public function step_54()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'userlist', 3, 5),
			'userlist',
			'relationid',
			array('relationid', 'type', 'friend')
		);
	}

	public function step_55()
	{
		// moved to step 97 & 98
		$this->skip_message();
	}

	public function step_56()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'user', 1, 5),
			'user',
			'profilevisits',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_57()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'user', 2, 5),
			'user',
			'friendcount',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_58()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'user', 3, 5),
			'user',
			'friendreqcount',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_59()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'user', 4, 5),
			'user',
			'vmunreadcount',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_60()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'user', 5, 5),
			'user',
			'vmmoderatedcount',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_61()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'bbcode', 1, 1),
			'bbcode',
			'options',
			'int',
			array('default' => 1, 'attributes' => self::FIELD_DEFAULTS)
		);
	}

	public function step_62()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'moderator', 1, 1),
			'moderator',
			'permissions2',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_63()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'usergroup', 1, 10),
			'usergroup',
			'visitormessagepermissions',
			'INT',
			self::FIELD_DEFAULTS
		);
	}

	public function step_64()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'usergroup', 2, 10),
			'usergroup',
			'socialgrouppermissions',
			'INT',
			self::FIELD_DEFAULTS
		);
	}

	public function step_65()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'usergroup', 3, 10),
			'usergroup',
			'usercsspermissions',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_66()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'usergroup', 4, 10),
			'usergroup',
			'albumpermissions',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_67()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'usergroup', 5, 10),
			'usergroup',
			'albumpicmaxwidth',
			'smallint',
			self::FIELD_DEFAULTS
		);
	}

	public function step_68()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'usergroup', 6, 10),
			'usergroup',
			'albumpicmaxheight',
			'smallint',
			self::FIELD_DEFAULTS
		);
	}

	public function step_69()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'usergroup', 7, 10),
			'usergroup',
			'albumpicmaxsize',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_70()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'usergroup', 8, 10),
			'usergroup',
			'albummaxpics',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_71()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'usergroup', 9, 10),
			'usergroup',
			'albummaxsize',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_72()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'usergroup', 10, 10),
			'usergroup',
			'genericpermissions2',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_73()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'moderation', 1, 6),
			"ALTER TABLE " . TABLE_PREFIX . "moderation CHANGE type type ENUM('thread', 'reply', 'visitormessage', 'groupmessage') NOT NULL DEFAULT 'thread'"
		);
	}

	public function step_74()
	{
		if ($this->field_exists('moderation', 'threadid'))
		{
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'moderation', 2, 6),
				"ALTER TABLE " . TABLE_PREFIX . "moderation CHANGE threadid primaryid INT UNSIGNED NULL DEFAULT '0'"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_75()
	{
		if ($this->field_exists('moderation', 'postid'))
		{
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'moderation', 3, 6),
				"UPDATE " . TABLE_PREFIX . "moderation SET primaryid = postid WHERE type = 'reply'"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_76()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'moderation', 4, 6),
			"ALTER TABLE " . TABLE_PREFIX . "moderation DROP PRIMARY KEY",
			self::MYSQL_ERROR_DROP_KEY_COLUMN_MISSING
		);
	}

	public function step_77()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'moderation', 5, 6),
			"ALTER TABLE " . TABLE_PREFIX . "moderation ADD PRIMARY KEY (primaryid, type)",
			self::MYSQL_ERROR_KEY_EXISTS
		);
	}

	public function step_78()
	{
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], 'moderation', 6, 6),
			'moderation',
			'postid'
		);
	}

	public function step_79()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'profilefieldcategory', 1, 1),
			'profilefieldcategory',
			'location',
			'varchar',
			array('length' => 25, 'attributes' => self::FIELD_DEFAULTS)
		);
	}

	public function step_80()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'rssfeed', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "rssfeed CHANGE url url TEXT"
		);
	}

	public function step_81()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'regimage', 1, 1),
			"DROP TABLE IF EXISTS " . TABLE_PREFIX . "regimage"
		);
	}

	public function step_82()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'setting', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "setting CHANGE datatype datatype ENUM('free', 'number', 'boolean', 'bitfield', 'username', 'integer') NOT NULL DEFAULT 'free'"
		);
	}

	public function step_83()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'search', 1, 1),
			'search',
			'completed',
			'smallint',
			array('null' => false, 'default' => 1)
		);
	}

	public function step_84()
	{
		$bookmarkcount = $this->db->query_first("
			SELECT COUNT(*) AS total
			FROM " . TABLE_PREFIX . "bookmarksite
		");
		if ($bookmarkcount['total'] == 0)
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . "bookmarksite"),
				"INSERT INTO " . TABLE_PREFIX . "bookmarksite
					(title, active, displayorder, iconpath, url)
				VALUES
					('Digg',        1, 10, 'bookmarksite_digg.gif',        'http://digg.com/submit?phrase=2&amp;url={URL}&amp;title={TITLE}'),
					('del.icio.us', 1, 20, 'bookmarksite_delicious.gif',   'http://del.icio.us/post?url={URL}&amp;title={TITLE}'),
					('StumbleUpon', 1, 30, 'bookmarksite_stumbleupon.gif', 'http://www.stumbleupon.com/submit?url={URL}&amp;title={TITLE}'),
					('Google',      1, 40, 'bookmarksite_google.gif',      'http://www.google.com/bookmarks/mark?op=edit&amp;output=popup&amp;bkmk={URL}&amp;title={TITLE}')
				"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_85()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . "phrasetype"),
			"INSERT IGNORE INTO " . TABLE_PREFIX . "phrasetype
				(title, editrows, fieldname, special)
			VALUES
				('{$this->phrase['phrasetype']['prefix']}', 3, 'prefix', 0),
				('{$this->phrase['phrasetype']['prefixadmin']}', 3, 'prefixadmin', 0),
				('{$this->phrase['phrasetype']['socialgroups']}', 3, 'socialgroups', 0),
				('{$this->phrase['phrasetype']['notice']}', 3, 'notice', 0),
				('{$this->phrase['phrasetype']['hvquestion']}', 3, 'hvquestion', 1),
				('{$this->phrase['phrasetype']['album']}', 3, 'album', 0)"
		);
	}

	public function step_86()
	{
		if (trim($this->registry->options['globalignore']) != '')
		{
			$this->add_adminmessage(
				'after_upgrade_37_update_counters',
				array(
					'dismissable' => 1,
					'script'      => 'misc.php',
					'action'      => 'updatethread',
					'execurl'     => 'misc.php?do=updatethread',
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

	/**
	* Check for modified Additional CSS template
	*/
	public function step_87()
	{
		if ($this->db->query_first("
			SELECT template.styleid
			FROM " . TABLE_PREFIX . "template AS template
			INNER JOIN " . TABLE_PREFIX . "style AS style USING (styleid)
			WHERE
				template.title = 'EXTRA' AND
				template.templatetype = 'css' AND
				template.product IN ('', 'vbulletin') AND
				template.styleid <> -1
			LIMIT 1
		"))
		{
			$rows = $this->db->query_first("SELECT COUNT(*) AS count FROM " . TABLE_PREFIX . "adminmessage WHERE varname = 'after_upgrade_37_modified_css'");
			if ($rows['count'] == 0)
			{
				$this->add_adminmessage(
					'after_upgrade_37_modified_css',
					array(
						'dismissable' => 1,
						'script'      => 'template.php',
						'action'      => 'modify',
						'execurl'     => 'template.php?do=modify',
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
		else
		{
			$this->skip_message();
		}
	}

	public function step_88()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'usergroup', 1, 1),
			"UPDATE " . TABLE_PREFIX . "usergroup SET
				forumpermissions = forumpermissions |
					IF(forumpermissions & " . $this->registry->bf_ugp_forumpermissions['canpostnew'] . ", " . $this->registry->bf_ugp_forumpermissions['cantagown'] . ", 0) |
					IF(forumpermissions & " . self::$legacy_bf['forumpermissions']['canreplyothers'] . ", " . $this->registry->bf_ugp_forumpermissions['cantagothers'] . ", 0) |
					IF(forumpermissions & " . $this->registry->bf_ugp_forumpermissions['candeletethread'] . ", " . $this->registry->bf_ugp_forumpermissions['candeletetagown'] . ", 0),
				genericpermissions = genericpermissions |
					IF(forumpermissions & " . self::$legacy_bf['forumpermissions']['canreplyothers'] . "
						OR forumpermissions & " . $this->registry->bf_ugp_forumpermissions['canpostnew'] . ", " . $this->registry->bf_ugp_genericpermissions['cancreatetag'] . ", 0),

				genericpermissions2 = genericpermissions2 |
					IF(genericpermissions & " . self::$legacy_bf['genericpermissions']['canseeprofilepic'] . "
						OR genericpermissions & " . $this->registry->bf_ugp_genericpermissions['canemailmember'] . ", " . $this->registry->bf_ugp_genericpermissions2['canusefriends'] . ", 0),

				albumpermissions = albumpermissions |
					IF(genericpermissions & " . $this->registry->bf_ugp_genericpermissions['canmodifyprofile'] . "
						AND genericpermissions & " . self::$legacy_bf['genericpermissions']['canprofilepic'] . ", " . $this->registry->bf_ugp_albumpermissions['canalbum'] . ", 0) |
					IF(genericpermissions & " . $this->registry->bf_ugp_genericpermissions['canviewmembers'] . ", " . $this->registry->bf_ugp_albumpermissions['canviewalbum'] . ", 0),
				albumpicmaxwidth = 600,
				albumpicmaxheight = 600,
				albumpicmaxsize = 100000,
				albummaxpics = 100,
				albummaxsize = 0,

				usercsspermissions = usercsspermissions |
					IF(genericpermissions & " . $this->registry->bf_ugp_genericpermissions['canmodifyprofile'] . "
						AND signaturepermissions & " . $this->registry->bf_ugp_signaturepermissions['canbbcodefont'] . ", " . $this->registry->bf_ugp_usercsspermissions['caneditfontfamily'] . ", 0) |
					IF(genericpermissions & " . $this->registry->bf_ugp_genericpermissions['canmodifyprofile'] . "
						AND signaturepermissions & " . $this->registry->bf_ugp_signaturepermissions['canbbcodefont'] . ", " . $this->registry->bf_ugp_usercsspermissions['caneditfontsize'] . ", 0) |
					IF(genericpermissions & " . $this->registry->bf_ugp_genericpermissions['canmodifyprofile'] . "
						AND signaturepermissions & " . $this->registry->bf_ugp_signaturepermissions['canbbcodecolor'] . ", " . $this->registry->bf_ugp_usercsspermissions['caneditcolors'] . ", 0) |
					IF(genericpermissions & " . $this->registry->bf_ugp_genericpermissions['canmodifyprofile'] . "
						AND genericpermissions & " . self::$legacy_bf['genericpermissions']['canprofilepic']  . "
						AND signaturepermissions & " . $this->registry->bf_ugp_signaturepermissions['canbbcodecolor'] . ", " . $this->registry->bf_ugp_usercsspermissions['caneditbgimage'] . ", 0) |
					IF(genericpermissions & " . $this->registry->bf_ugp_genericpermissions['canmodifyprofile'] . "
						AND signaturepermissions & " . $this->registry->bf_ugp_signaturepermissions['canbbcodecolor'] . ", " . $this->registry->bf_ugp_usercsspermissions['caneditborders'] . ", 0),

				visitormessagepermissions = visitormessagepermissions |
					IF(forumpermissions & " . self::$legacy_bf['forumpermissions']['canreplyothers'] . "
						OR forumpermissions & " . $this->registry->bf_ugp_forumpermissions['canreply'] . ", " . $this->registry->bf_ugp_visitormessagepermissions['canmessageownprofile'] . ", 0) |
					IF(forumpermissions & " . self::$legacy_bf['forumpermissions']['canreplyothers'] . ", " . $this->registry->bf_ugp_visitormessagepermissions['canmessageothersprofile'] . ", 0) |
					IF(forumpermissions & " . $this->registry->bf_ugp_forumpermissions['caneditpost'] . ", " . $this->registry->bf_ugp_visitormessagepermissions['caneditownmessages'] . ", 0) |
					IF(forumpermissions & " . $this->registry->bf_ugp_forumpermissions['candeletepost'] . ", " . $this->registry->bf_ugp_visitormessagepermissions['candeleteownmessages'] . ", 0) |
					IF(forumpermissions & " . self::$legacy_bf['forumpermissions']['followforummoderation'] . ", " . $this->registry->bf_ugp_visitormessagepermissions['followforummoderation'] . ", 0) |
					IF(forumpermissions & " . $this->registry->bf_ugp_forumpermissions['caneditpost'] . "
						OR forumpermissions & " . $this->registry->bf_ugp_forumpermissions['candeletepost'] . ", " . $this->registry->bf_ugp_visitormessagepermissions['canmanageownprofile'] . ", 0),

				socialgrouppermissions = socialgrouppermissions |
					IF(genericpermissions & " . $this->registry->bf_ugp_genericpermissions['canviewmembers'] . ", " . $this->registry->bf_ugp_socialgrouppermissions['canjoingroups'] . ", 0) |
					IF(forumpermissions & " . $this->registry->bf_ugp_forumpermissions['canpostnew'] . ", " . $this->registry->bf_ugp_socialgrouppermissions['cancreategroups'] . ", 0) |
					IF(forumpermissions & " . $this->registry->bf_ugp_forumpermissions['caneditpost'] . ", " . $this->registry->bf_ugp_socialgrouppermissions['caneditowngroups'] . ", 0) |
					IF(forumpermissions & " . $this->registry->bf_ugp_forumpermissions['candeletethread'] . ", " . $this->registry->bf_ugp_socialgrouppermissions['candeleteowngroups'] . ", 0) |
					IF(genericpermissions & " . $this->registry->bf_ugp_genericpermissions['canviewmembers'] . ", " . $this->registry->bf_ugp_socialgrouppermissions['canviewgroups'] . ", 0) |
					IF(forumpermissions & " . $this->registry->bf_ugp_forumpermissions['caneditpost']  . "
						OR forumpermissions & " . $this->registry->bf_ugp_forumpermissions['candeletepost'] . ", " . $this->registry->bf_ugp_socialgrouppermissions['canmanagemessages'] . ", 0) |
					IF(adminpermissions & " . $this->registry->bf_ugp_adminpermissions['ismoderator'] . ", " . $this->registry->bf_ugp_socialgrouppermissions['canalwayspostmessage'] . ", 0) |
					" . $this->registry->bf_ugp_socialgrouppermissions['followforummoderation'] . "
			"
		);
	}

	public function step_89()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'forumpermission', 1, 1),
			"UPDATE " . TABLE_PREFIX . "forumpermission SET
				forumpermissions = forumpermissions |
					IF(forumpermissions & " . $this->registry->bf_ugp_forumpermissions['canpostnew'] . ", " . $this->registry->bf_ugp_forumpermissions['cantagown'] . ", 0) |
					IF(forumpermissions & " . self::$legacy_bf['forumpermissions']['canreplyothers'] . ", " . $this->registry->bf_ugp_forumpermissions['cantagothers'] . ", 0) |
					IF(forumpermissions & " . $this->registry->bf_ugp_forumpermissions['candeletethread'] . ", " . $this->registry->bf_ugp_forumpermissions['candeletetagown'] . ", 0)
			"
		);
	}

	public function step_90()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'moderator', 1, 1),
			"UPDATE " . TABLE_PREFIX . "moderator SET
				permissions2 = permissions2 |
					IF(permissions & " . $this->registry->bf_misc_moderatorpermissions['caneditposts'] . ", " . $this->registry->bf_misc_moderatorpermissions2['caneditvisitormessages'] . ", 0) |
					IF(permissions & " . $this->registry->bf_misc_moderatorpermissions['candeleteposts'] . ", " . $this->registry->bf_misc_moderatorpermissions2['candeletevisitormessages'] . ", 0) |
					IF(permissions & " . $this->registry->bf_misc_moderatorpermissions['canremoveposts'] . ", " . $this->registry->bf_misc_moderatorpermissions2['canremovevisitormessages'] . ", 0) |
					IF(permissions & " . $this->registry->bf_misc_moderatorpermissions['canmoderateposts'] . ", " . $this->registry->bf_misc_moderatorpermissions2['canmoderatevisitormessages'] . ", 0) |
					IF(permissions & " . $this->registry->bf_misc_moderatorpermissions['caneditavatar'] . ", " . $this->registry->bf_misc_moderatorpermissions2['caneditalbumpicture'] . ", 0) |
					IF(permissions & " . $this->registry->bf_misc_moderatorpermissions['caneditavatar'] . ", " . $this->registry->bf_misc_moderatorpermissions2['candeletealbumpicture'] . ", 0) |
					IF(permissions & " . $this->registry->bf_misc_moderatorpermissions['caneditposts'] . ", " . $this->registry->bf_misc_moderatorpermissions2['caneditsocialgroups'] . ", 0) |
					IF(permissions & " . $this->registry->bf_misc_moderatorpermissions['candeleteposts'] . ", " . $this->registry->bf_misc_moderatorpermissions2['candeletesocialgroups'] . ", 0) |
					IF(permissions & " . $this->registry->bf_misc_moderatorpermissions['candeleteposts'] . ", " . $this->registry->bf_misc_moderatorpermissions2['candeletegroupmessages'] . ", 0) |
					IF(permissions & " . $this->registry->bf_misc_moderatorpermissions['canremoveposts'] . ", " . $this->registry->bf_misc_moderatorpermissions2['canremovegroupmessages'] . ", 0) |
					IF(permissions & " . $this->registry->bf_misc_moderatorpermissions['canmoderateposts'] . ", " . $this->registry->bf_misc_moderatorpermissions2['canmoderategroupmessages'] . ", 0) |
					IF(permissions & " . $this->registry->bf_misc_moderatorpermissions['caneditposts'] . ", " . $this->registry->bf_misc_moderatorpermissions2['caneditgroupmessages'] . ", 0)
			"
		);
	}

	public function step_91()
	{
		$this->add_adminmessage(
			'after_upgrade_37_moderator_permissions',
			array(
				'dismissable' => 1,
				'script'      => 'moderator.php',
				'action'      => 'showlist',
				'execurl'     => 'moderator.php?do=showlist',
				'method'      => 'get',
				'status'      => 'undone',
			)
		);
	}

	public function step_92()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'user'),
			"UPDATE " . TABLE_PREFIX . "user SET options = options | " . $this->registry->bf_misc_useroptions['showusercss'] . " | " . $this->registry->bf_misc_useroptions['receivefriendemailrequest']
		);
	}

	public function step_93()
	{
		if ($this->registry->options['thumbquality'] <= 70)
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . "setting"),
				"UPDATE " . TABLE_PREFIX . "setting SET value = '65' WHERE varname = 'thumbquality'"
			);
		}
		else if ($this->registry->options['thumbquality'] >= 90)
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . "setting"),
				"UPDATE " . TABLE_PREFIX . "setting SET value = '95' WHERE varname = 'thumbquality'"
			);
		}
		else if ($this->registry->options['thumbquality'] >= 80)
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . "setting"),
				"UPDATE " . TABLE_PREFIX . "setting SET value = '95' WHERE varname = 'thumbquality'"
			);
		}
		else
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . "setting"),
				"UPDATE " . TABLE_PREFIX . "setting SET value = '75' WHERE varname = 'thumbquality'"
			);
		}
	}

	public function step_94()
	{
		$this->skip_message();
	}

	public function step_95()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'setting', 1, 1),
			"UPDATE " . TABLE_PREFIX . "setting SET value = 'GDttf' WHERE varname = 'regimagetype' AND value = 'GD'"
		);
	}

	public function step_96()
	{
		if ($this->db->query_first("SELECT varname FROM " . TABLE_PREFIX . "setting WHERE varname = 'regimagetype' AND value IN ('GDttf', 'GD')"))
		{
			require_once(DIR . '/includes/adminfunctions_options.php');
			$gdinfo = fetch_gdinfo();
			if ($gdinfo['freetype'] != 'freetype')
			{
				// they won't be able to use the simple text version and they don't have FreeType support, so no image verification
				$this->run_query(
					sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'setting'),
					"INSERT IGNORE INTO " . TABLE_PREFIX . "setting
						(varname, grouptitle, value, volatile, product)
					VALUES
						('hv_type', 'version', '0', 1, 'vbulletin')"
				);

				$this->add_adminmessage(
					'after_upgrade_37_image_verification_disabled',
					array(
						'dismissable' => 1,
						'script'      => 'verify.php',
						'action'      => '',
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
		else
		{
			$this->skip_message();
		}
	}

	/*
	 * Drop existing userid index (from create table 366 step_8) before adding index with
	 * the same name
	 */
	public function step_97()
	{
		$this->drop_index(
			sprintf($this->phrase['core']['altering_x_table'], 'userlist', 4, 5),
			'userlist',
			'userid'
		);
	}

	public function step_98()
	{
		// This used to be step 55, but was causing a duplicate index error
		// due to the `userid` key already existing for certain vB3 DBs.
		// As such moved near the end with the required drop_index() call above.
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'userlist', 5, 5),
			'userlist',
			'userid',
			array('userid', 'type', 'friend')
		);
	}

	public function step_99($data = null)
	{
		$startat = intval($data['startat']);

		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'userlist'));
		$perpage = 100;
		$users = $this->db->query_read("
			SELECT *
			FROM " . TABLE_PREFIX . "user AS user
			LEFT JOIN ". TABLE_PREFIX . "usertextfield AS usertextfield ON (usertextfield.userid = user.userid)
			WHERE user.userid > $startat AND (usertextfield.ignorelist <> '' OR usertextfield.buddylist <> '')
			ORDER BY user.userid ASC
			" . ($this->limitqueries ? "LIMIT 0, $perpage" : "") . "
		");

		// check to see if we have some results...
		if ($this->db->num_rows($users))
		{
			$lastid = 0;
			while ($user = $this->db->fetch_array($users))
			{
				$this->show_message(sprintf($this->phrase['version']['370b2']['build_userlist'], $user['userid']));

				$buddylist = preg_split('/( )+/', trim($user['buddylist']), -1, PREG_SPLIT_NO_EMPTY);
				$ignorelist = preg_split('/( )+/', trim($user['ignorelist']), -1, PREG_SPLIT_NO_EMPTY);

				if (!empty($buddylist))
				{
					$buddylist = array_map('intval', $buddylist);
					foreach ($buddylist AS $buddyid)
					{
						$this->db->query_write("INSERT IGNORE INTO " . TABLE_PREFIX . "userlist (userid, relationid, type, friend) VALUES (" . $user['userid'] . ", " . $buddyid . ", 'buddy', 'no')");
					}
				}

				if (!empty($ignorelist))
				{
					$ignorelist = array_map('intval', $ignorelist);
					foreach ($ignorelist AS $ignoreid)
					{
						$this->db->query_write("INSERT IGNORE INTO " . TABLE_PREFIX . "userlist (userid, relationid, type, friend) VALUES (" . $user['userid'] . ", " . $ignoreid . ", 'ignore', 'no')");
					}
				}
				$lastid = $user['userid'];
			}
			return array('startat' => $lastid);
		}
		else
		{
			$this->show_message($this->phrase['version']['370b2']['build_userlist_complete']);
		}
	}
}

class vB_Upgrade_370b3 extends vB_Upgrade_Version
{
	public function step_1()
	{
		if (!isset($this->registry->bf_misc_moderatorpermissions2['caneditvisitormessages']))
		{
			$this->add_error($this->phrase['core']['wrong_bitfield_xml'], self::PHP_TRIGGER_ERROR, true);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_2()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'phrasetype', 1, 1),
			"UPDATE " . TABLE_PREFIX . "phrasetype SET special = 1 WHERE fieldname = 'hvquestion'"
		);
	}

	public function step_3()
	{
		// support for limited social groups
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'user', 1, 2),
			'user',
			'socgroupinvitecount',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_4()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'user', 2, 2),
			'user',
			'socgroupreqcount',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_5()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'socialgroup', 1, 2),
			'socialgroup',
			'type',
			'enum',
			array('attributes' => "('public', 'moderated', 'inviteonly')", 'null' => false, 'default' => 'public')
		);
	}

	public function step_6()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'socialgroup', 2, 2),
			'socialgroup',
			'moderatedmembers',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_7()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'socialgroupmember', 1, 4),
			'socialgroupmember',
			'type',
			'enum',
			array('attributes' => "('member', 'moderated', 'invited')", 'null' => false, 'default' => 'member')
		);
	}

	public function step_8()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'socialgroupmember', 2, 4),
			'socialgroupmember',
			'groupid',
			array('groupid', 'type')
		);
	}

	public function step_9()
	{
		$this->drop_index(
			sprintf($this->phrase['core']['altering_x_table'], 'socialgroupmember', 3, 4),
			'socialgroupmember',
			'userid'
		);
	}

	public function step_10()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'socialgroupmember', 4, 4),
			'socialgroupmember',
			'userid',
			array('userid', 'type')
		);
	}

	public function step_11()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'socialgrouppicture', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "socialgrouppicture
				CHANGE groupid groupid INT UNSIGNED NOT NULL DEFAULT '0',
				CHANGE pictureid pictureid INT UNSIGNED NOT NULL DEFAULT '0',
				CHANGE dateline dateline INT UNSIGNED NOT NULL DEFAULT '0'
		");
	}

	public function step_12()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'notice', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "notice
				CHANGE title title VARCHAR(250) NOT NULL DEFAULT '',
				CHANGE displayorder displayorder INT UNSIGNED NOT NULL DEFAULT '0',
				CHANGE active active SMALLINT UNSIGNED NOT NULL DEFAULT '0'
		");
	}

	public function step_13()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'noticecriteria', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "noticecriteria
				CHANGE noticeid noticeid INT UNSIGNED NOT NULL DEFAULT '0',
				CHANGE criteriaid criteriaid VARCHAR(191) NOT NULL DEFAULT '',
				CHANGE condition1 condition1 VARCHAR(250) NOT NULL DEFAULT '',
				CHANGE condition2 condition2 VARCHAR(250) NOT NULL DEFAULT '',
				CHANGE condition3 condition3 VARCHAR(250) NOT NULL DEFAULT ''
		");
	}

	public function step_14()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'tag', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "tag
				CHANGE tagtext tagtext VARCHAR(100) NOT NULL DEFAULT '',
				CHANGE dateline dateline INT UNSIGNED NOT NULL DEFAULT '0'
		");
	}

	public function step_15()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'tagthread', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "tagthread
				CHANGE tagid tagid INT UNSIGNED NOT NULL DEFAULT '0',
				CHANGE threadid threadid INT UNSIGNED NOT NULL DEFAULT '0',
				CHANGE userid userid INT UNSIGNED NOT NULL DEFAULT '0',
				CHANGE dateline dateline INT UNSIGNED NOT NULL DEFAULT '0'
		");
	}

	public function step_16()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'tagsearch', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "tagsearch
				CHANGE tagid tagid INT UNSIGNED NOT NULL DEFAULT '0',
				CHANGE dateline dateline INT UNSIGNED NOT NULL DEFAULT '0'
		");
	}

	public function step_17()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'postedithistory', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "postedithistory
				CHANGE postid postid INT UNSIGNED NOT NULL DEFAULT '0',
				CHANGE userid userid INT UNSIGNED NOT NULL DEFAULT '0',
				CHANGE username username VARCHAR(100) NOT NULL DEFAULT '',
				CHANGE title title VARCHAR(250) NOT NULL DEFAULT '',
				CHANGE iconid iconid INT UNSIGNED NOT NULL DEFAULT '0',
				CHANGE dateline dateline INT UNSIGNED NOT NULL DEFAULT '0',
				CHANGE reason reason VARCHAR(200) NOT NULL DEFAULT '',
				CHANGE original original SMALLINT NOT NULL DEFAULT '0'
		");
	}

	public function step_18()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'usercsscache', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "usercsscache
				CHANGE cachedcss cachedcss TEXT
		");
	}

	public function step_19()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'visitormessage', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "visitormessage
				CHANGE userid userid INT UNSIGNED NOT NULL DEFAULT '0',
				CHANGE postuserid postuserid INT UNSIGNED NOT NULL DEFAULT '0',
				CHANGE postusername postusername VARCHAR(100) NOT NULL DEFAULT '',
				CHANGE dateline dateline INT UNSIGNED NOT NULL DEFAULT '0',
				CHANGE ipaddress ipaddress INT UNSIGNED NOT NULL DEFAULT '0'
		");
	}

	public function step_20()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'groupmessage', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "groupmessage
				CHANGE groupid groupid INT UNSIGNED NOT NULL DEFAULT '0',
				CHANGE postuserid postuserid INT UNSIGNED NOT NULL DEFAULT '0',
				CHANGE postusername postusername VARCHAR(100) NOT NULL DEFAULT '',
				CHANGE dateline dateline INT UNSIGNED NOT NULL DEFAULT '0',
				CHANGE ipaddress ipaddress INT UNSIGNED NOT NULL DEFAULT '0'
		");
	}

	public function step_21()
	{
		if ($this->field_exists('album', 'picturecount'))
		{
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'album', 1, 1),
				"ALTER TABLE " . TABLE_PREFIX . "album
					CHANGE userid userid INT UNSIGNED NOT NULL DEFAULT '0',
					CHANGE createdate createdate INT UNSIGNED NOT NULL DEFAULT '0',
					CHANGE lastpicturedate lastpicturedate INT UNSIGNED NOT NULL DEFAULT '0',
					CHANGE picturecount picturecount INT UNSIGNED NOT NULL DEFAULT '0',
					CHANGE title title VARCHAR(100) NOT NULL DEFAULT '',
					CHANGE description description TEXT,
					CHANGE coverpictureid coverpictureid INT UNSIGNED NOT NULL DEFAULT '0'
			");
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_22()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'albumpicture', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "albumpicture
				CHANGE albumid albumid INT UNSIGNED NOT NULL DEFAULT '0',
				CHANGE pictureid pictureid INT UNSIGNED NOT NULL DEFAULT '0',
				CHANGE dateline dateline INT UNSIGNED NOT NULL DEFAULT '0'
		");
	}

	public function step_23()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'picture', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "picture
				CHANGE userid userid INT UNSIGNED NOT NULL DEFAULT '0',
				CHANGE caption caption TEXT,
				CHANGE extension extension VARCHAR(20) NOT NULL DEFAULT '',
				CHANGE filedata filedata MEDIUMBLOB,
				CHANGE filesize filesize INT UNSIGNED NOT NULL DEFAULT '0',
				CHANGE width width SMALLINT UNSIGNED NOT NULL DEFAULT '0',
				CHANGE height height SMALLINT UNSIGNED NOT NULL DEFAULT '0',
				CHANGE thumbnail thumbnail MEDIUMBLOB,
				CHANGE thumbnail_filesize thumbnail_filesize INT UNSIGNED NOT NULL DEFAULT '0',
				CHANGE thumbnail_width thumbnail_width SMALLINT UNSIGNED NOT NULL DEFAULT '0',
				CHANGE thumbnail_height thumbnail_height SMALLINT UNSIGNED NOT NULL DEFAULT '0',
				CHANGE thumbnail_dateline thumbnail_dateline INT UNSIGNED NOT NULL DEFAULT '0',
				CHANGE idhash idhash VARCHAR(32) NOT NULL DEFAULT '',
				CHANGE reportthreadid reportthreadid INT UNSIGNED NOT NULL DEFAULT '0'
		");
	}

	/**
	* Step #24 - For MySQL 5 compat, TEXT fields do not have NOT NULL or DEFAULT
	*
	*/
	public function step_24()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'rssfeed', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "rssfeed CHANGE url url TEXT"
		);
	}

	public function step_25()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'socialgroup', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "socialgroup CHANGE description description TEXT"
		);
	}

	public function step_26()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'usercsscache', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "usercsscache CHANGE cachedcss cachedcss TEXT"
		);
	}
}

class vB_Upgrade_370b4 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->skip_message();
	}

	public function step_2()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'album', 1, 3),
			'album',
			'state',
			'enum',
			array('attributes' => "('public', 'private', 'profile')", 'null' => false, 'default' => 'public')
		);
	}

	public function step_3()
	{
		if ($this->field_exists('album', 'public'))
		{
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'album', 2, 3),
				"UPDATE " . TABLE_PREFIX . "album SET state = 'private' WHERE public = 0"
			);

			$this->drop_field(
				sprintf($this->phrase['core']['altering_x_table'], 'album', 3, 3),
				'album',
				'public'
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_4()
	{
		// Change the extension field to binary - all extension fields must be binary
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'picture', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "picture CHANGE extension extension VARCHAR(20) BINARY NOT NULL DEFAULT ''"
		);
	}

	public function step_5()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'picturecomment_hash'),
			"CREATE TABLE " . TABLE_PREFIX . "picturecomment_hash (
				postuserid INT UNSIGNED NOT NULL DEFAULT '0',
				pictureid INT UNSIGNED NOT NULL DEFAULT '0',
				dupehash VARCHAR(32) NOT NULL DEFAULT '',
				dateline INT UNSIGNED NOT NULL DEFAULT '0',
				KEY postuserid (postuserid, dupehash),
				KEY dateline (dateline)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_6()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'picturecomment'),
			"CREATE TABLE " . TABLE_PREFIX . "picturecomment (
				commentid INT UNSIGNED NOT NULL AUTO_INCREMENT,
				pictureid INT UNSIGNED NOT NULL DEFAULT '0',
				postuserid INT UNSIGNED NOT NULL DEFAULT '0',
				postusername varchar(100) NOT NULL DEFAULT '',
				dateline INT UNSIGNED NOT NULL DEFAULT '0',
				state ENUM('visible','moderation','deleted') NOT NULL DEFAULT 'visible',
				title VARCHAR(255) NOT NULL DEFAULT '',
				pagetext MEDIUMTEXT,
				ipaddress INT UNSIGNED NOT NULL DEFAULT '0',
				allowsmilie SMALLINT NOT NULL DEFAULT '1',
				reportthreadid INT UNSIGNED NOT NULL DEFAULT '0',
				messageread SMALLINT UNSIGNED NOT NULL DEFAULT '0',
				PRIMARY KEY (commentid),
				KEY pictureid (pictureid, dateline, state),
				KEY postuserid (postuserid, pictureid, state)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_7()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'deletionlog', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "deletionlog CHANGE type type ENUM('post', 'thread', 'visitormessage', 'groupmessage', 'picturecomment') NOT NULL DEFAULT 'post'"
		);
	}

	public function step_8()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'moderation', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "moderation CHANGE type type ENUM('thread', 'reply', 'visitormessage', 'groupmessage', 'picturecomment') NOT NULL DEFAULT 'thread'"
		);
	}

	public function step_9()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'user', 1, 2),
			'user',
			'pcunreadcount',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_10()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'user', 2, 2),
			'user',
			'pcmoderatedcount',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_11()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'poll', 1, 1),
			"DELETE pollvote, poll
			FROM " . TABLE_PREFIX . "poll AS poll
			LEFT JOIN " . TABLE_PREFIX . "pollvote AS pollvote ON (poll.pollid = pollvote.pollid)
			LEFT JOIN " . TABLE_PREFIX . "thread AS thread ON (poll.pollid = thread.pollid)
			WHERE thread.threadid IS NULL"
		);
	}

	public function step_12()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'calendarcustomfield', 1, 1),
			"DELETE {$needprefix}calendarcustomfield
			FROM " . TABLE_PREFIX . "calendarcustomfield AS calendarcustomfield
			LEFT JOIN " . TABLE_PREFIX . "calendar AS calendar ON (calendar.calendarid = calendarcustomfield.calendarid)
			WHERE calendar.calendarid IS NULL"
		);
	}

	public function step_13()
	{
		$bookmarkcount = $this->db->query_first("
			SELECT COUNT(*) AS total
			FROM " . TABLE_PREFIX . "bookmarksite
		");
		if ($bookmarkcount['total'] == 0)
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . "bookmarksite"),
				"INSERT INTO " . TABLE_PREFIX . "bookmarksite
					(title, active, displayorder, iconpath, url)
				VALUES
					('Digg',        1, 10, 'bookmarksite_digg.gif',        'http://digg.com/submit?phrase=2&amp;url={URL}&amp;title={TITLE}'),
					('del.icio.us', 1, 20, 'bookmarksite_delicious.gif',   'http://del.icio.us/post?url={URL}&amp;title={TITLE}'),
					('StumbleUpon', 1, 30, 'bookmarksite_stumbleupon.gif', 'http://www.stumbleupon.com/submit?url={URL}&amp;title={TITLE}'),
					('Google',      1, 40, 'bookmarksite_google.gif',      'http://www.google.com/bookmarks/mark?op=edit&amp;output=popup&amp;bkmk={URL}&amp;annotation={TITLE}')
				"
			);
		}
		else
		{
				$this->skip_message();
		}
	}

	public function step_14()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'usergroup', 1, 1),
			"UPDATE " . TABLE_PREFIX . "usergroup SET
				albumpermissions = albumpermissions |
					IF(forumpermissions & " . self::$legacy_bf['forumpermissions']['canreplyothers'] . ", " . $this->registry->bf_ugp_albumpermissions['canpiccomment'] . ", 0) |
					IF(forumpermissions & " . $this->registry->bf_ugp_forumpermissions['caneditpost'] . ", " . $this->registry->bf_ugp_albumpermissions['caneditownpiccomment'] . ", 0) |
					IF(forumpermissions & " . $this->registry->bf_ugp_forumpermissions['candeletepost'] . ", " . $this->registry->bf_ugp_albumpermissions['candeleteownpiccomment'] . ", 0) |
					IF(forumpermissions & " . self::$legacy_bf['forumpermissions']['followforummoderation'] . ", " . $this->registry->bf_ugp_albumpermissions['commentfollowforummoderation'] . ", 0) |
					IF(forumpermissions & " . $this->registry->bf_ugp_forumpermissions['caneditpost'] . "
						OR forumpermissions & " . $this->registry->bf_ugp_forumpermissions['candeletepost'] . ", " . $this->registry->bf_ugp_albumpermissions['canmanagepiccomment'] . ", 0)
			"
		);
	}

	public function step_15()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'moderator', 1, 1),
			"UPDATE " . TABLE_PREFIX . "moderator SET
				permissions2 = permissions2 |
					IF(permissions & " . $this->registry->bf_misc_moderatorpermissions['caneditposts'] . ", " . self::$legacy_bf['moderatorpermissions2']['caneditpicturecomments'] . ", 0) |
					IF(permissions & " . $this->registry->bf_misc_moderatorpermissions['candeleteposts'] . ", " . self::$legacy_bf['moderatorpermissions2']['candeletepicturecomments'] . ", 0) |
					IF(permissions & " . $this->registry->bf_misc_moderatorpermissions['canremoveposts'] . ", " . self::$legacy_bf['moderatorpermissions2']['canremovepicturecomments'] . ", 0) |
					IF(permissions & " . $this->registry->bf_misc_moderatorpermissions['canmoderateposts'] . ", " . self::$legacy_bf['moderatorpermissions2']['canmoderatepicturecomments'] . ", 0)
			"
		);
	}

	public function step_16()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'setting', 1, 1),
			"UPDATE " . TABLE_PREFIX . "setting SET
				value = '1'
			WHERE varname = 'contactustype' AND value = '2'"
		);
	}
}

class vB_Upgrade_370b5 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'socialgroup', 1, 4),
			'socialgroup',
			'visible',
			'visible'
		);
	}

	public function step_2()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'socialgroup', 2, 4),
			'socialgroup',
			'picturecount',
			'picturecount'
		);
	}

	public function step_3()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'socialgroup', 3, 4),
			'socialgroup',
			'members',
			'members'
		);
	}

	public function step_4()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'socialgroup', 4, 4),
			'socialgroup',
			'lastpost',
			'lastpost'
		);
	}

	public function step_5()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'template', 1, 2),
			"UPDATE IGNORE " . TABLE_PREFIX . "template SET title = '.inlinemod' WHERE title = 'td.inlinemod'"
		);
	}

	public function step_6()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'template', 2, 2),
			"DELETE FROM " . TABLE_PREFIX . "template WHERE title = 'td.inlinemod'"
		);
	}

	public function step_7()
	{
		$this->drop_index(
			sprintf($this->phrase['core']['altering_x_table'], 'pmreceipt', 1, 2),
			'pmreceipt',
			'userid'
		);
	}

	public function step_8()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'pmreceipt', 2, 2),
			'pmreceipt',
			'userid',
			array('userid', 'readtime')
		);
	}
}

class vB_Upgrade_370b6 extends vB_Upgrade_Version
{
	public function step_1()
	{
		if (!isset($this->registry->bf_misc_useroptions['vm_enable']))
		{
			$this->add_error($this->phrase['core']['wrong_bitfield_xml'], self::PHP_TRIGGER_ERROR, true);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_2()
	{
		// Enable Visitor Messages for all users
		$this->run_query(
			sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'user'),
			"UPDATE " . TABLE_PREFIX . "user SET options = options | " . $this->registry->bf_misc_useroptions['vm_enable']
		);
	}

	public function step_3()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'setting'),
			"UPDATE " . TABLE_PREFIX . "setting SET
				value = value | " . $this->registry->bf_misc_regoptions['vm_enable'] . "
			WHERE varname = 'defaultregoptions'"
		);
	}

	public function step_4()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'socialgroup', 1, 1),
			'socialgroup',
			'options',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_5()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'user', 1, 1),
			'user',
			'gmmoderatedcount',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_6()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'socialgroup'),
			"UPDATE " . TABLE_PREFIX . "socialgroup SET
				options = options | " .
				($this->registry->options['socnet_groups_albums_enabled'] ? $this->registry->bf_misc_socialgroupoptions['enable_group_albums'] : 0) . " | " .
				($this->registry->options['socnet_groups_msg_enabled'] ? $this->registry->bf_misc_socialgroupoptions['enable_group_messages'] : 0)
		);
	}

	public function step_7()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'picture', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "picture ADD state ENUM('visible', 'moderation') NOT NULL DEFAULT 'visible'",
			self::MYSQL_ERROR_COLUMN_EXISTS
		);
	}

	public function step_8()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'album', 1, 2),
			'album',
			'moderation',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_9()
	{
		if ($this->field_exists('album', 'picturecount'))
		{
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'album', 2, 2),
				"ALTER TABLE " . TABLE_PREFIX . "album CHANGE picturecount visible INT UNSIGNED NOT NULL DEFAULT '0'"
			);
		}
	}

	public function step_10()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'usergroup', 1, 1),
			"UPDATE " . TABLE_PREFIX . "usergroup SET
				albumpermissions = albumpermissions | IF(forumpermissions & " . self::$legacy_bf['forumpermissions']['followforummoderation'] . ", " . $this->registry->bf_ugp_albumpermissions['picturefollowforummoderation'] . ", 0)
			"
		);
	}

	public function step_11()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'moderator', 1, 1),
			"UPDATE " . TABLE_PREFIX . "moderator SET
				permissions2 = permissions2 | IF(permissions & " . self::$legacy_bf['moderatorpermissions2']['canmoderatepicturecomments'] . ", " . $this->registry->bf_misc_moderatorpermissions2['canmoderatepictures'] . ", 0)
			"
		);
	}

	public function step_12()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'usergroup', 1, 1),
			"UPDATE " . TABLE_PREFIX . "usergroup SET
				socialgrouppermissions = socialgrouppermissions |
					IF(visitormessagepermissions & " . $this->registry->bf_ugp_visitormessagepermissions['canmanageownprofile'] . ", " . $this->registry->bf_ugp_socialgrouppermissions['canmanageowngroups'] . ", 0)
			"
		);
	}

	public function step_13()
	{
		//this step previously updated the faq phrases.  We'll do that again in 502 so there is no reason to do it twice.
		$this->skip_message();
	}

	public function step_14()
	{
		//this step previously added an admincp notice for the changes in step13 which is no longer relevant.
		$this->skip_message();
	}

	public function step_15()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'pollvote', 1, 5),
			"ALTER TABLE " . TABLE_PREFIX . "pollvote CHANGE userid userid INT UNSIGNED NULL DEFAULT NULL"
		);
	}

	public function step_16()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'pollvote', 2, 5),
			"ALTER TABLE " . TABLE_PREFIX . "pollvote ADD votetype INT UNSIGNED NOT NULL DEFAULT '0'",
			self::MYSQL_ERROR_COLUMN_EXISTS
		);
	}

	public function step_17()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'pollvote'),
			"UPDATE " . TABLE_PREFIX . "pollvote AS pollvote, " . TABLE_PREFIX . "poll AS poll
			SET pollvote.votetype = pollvote.voteoption
		 	WHERE pollvote.pollid = poll.pollid
		 		AND poll.multiple = 1
			"
		);
	}

	public function step_18()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'pollvote'),
			"UPDATE " . TABLE_PREFIX . "pollvote SET userid = NULL WHERE userid = 0"
		);
	}


	public function step_19()
	{
		//run this before we remove the pollid index or it's going to take forever.

		//There is some danger of losing data here and this will absolutely change things
		//But it's enforcing the one person, one poll, one vote rule so we'll be eliminating votes from
		//totals where they shouldn't exist.  (Note that votetype is there to handle multiple selection polls
		//so "one vote" can encompass multiple records for different options and they won't be removed
		//as duplicates nor violate the unique constraint in the future step).
		//
		//We do not check if multiple records for single selection polls are all for the same option
		//we wouldn't know which was the right one anyway.
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'pollvote'));
		$db = vB::getDbAssertor();
		$db->assertQuery('vBinstall:deleteDuplicatePollVotes', array());
	}

	public function step_20()
	{
		$this->drop_index(
			sprintf($this->phrase['core']['altering_x_table'], 'pollvote', 3, 5),
			'pollvote',
			'pollid'
		);
	}

	public function step_21()
	{
		//this index will be removed later, but it's safter to keep it in the event that we
		//do some manipulations later on in the upgrade that would violate the constraint.
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'pollvote', 4, 5),
			"ALTER TABLE " . TABLE_PREFIX . "pollvote ADD UNIQUE INDEX pollid (pollid,userid,votetype)",
			self::MYSQL_ERROR_KEY_EXISTS
		);
	}

	public function step_22()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'pollvote', 5, 5),
			'pollvote',
			'userid',
			'userid'
		);
	}
}

class vB_Upgrade_370rc1 extends vB_Upgrade_Version
{
	public function step_1()
	{
		if (!isset($this->registry->bf_misc_useroptions['vm_enable']))
		{
			$this->add_error($this->phrase['core']['wrong_bitfield_xml'], self::PHP_TRIGGER_ERROR, true);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_2()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'bookmarksite', 1, 1),
			"UPDATE " . TABLE_PREFIX . "bookmarksite
			SET url = 'http://www.google.com/bookmarks/mark?op=edit&amp;output=popup&amp;bkmk={URL}&amp;title={TITLE}'
			WHERE url = 'http://www.google.com/bookmarks/mark?op=edit&amp;output=popup&amp;bkmk={URL}&amp;annotation={TITLE}' AND title = 'Google'
			"
		);
	}

	public function step_3()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'user', 1, 1),
			'user',
			'referrerid',
			array('referrerid')
		);
	}
}

class vB_Upgrade_370rc3 extends vB_Upgrade_Version
{
	/**
	* Step #1 - give all admins notices permissions by default
	*
	*/
	public function step_1()
	{
		if (!isset($this->registry->bf_ugp_adminpermissions['canadminnotices']))
		{
			$this->add_error($this->phrase['core']['wrong_bitfield_xml'], self::PHP_TRIGGER_ERROR, true);
		}

		$this->run_query(
			sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'administrator'),
			"UPDATE " . TABLE_PREFIX . "administrator SET
				adminpermissions = adminpermissions | " .
					($this->registry->bf_ugp_adminpermissions['canadminnotices'] + $this->registry->bf_ugp_adminpermissions['canadminmodlog'])
		);
	}

	public function step_2()
	{
		$tables = $this->db->query_write("SHOW TABLES");
		$skip = true;
		while ($table = $this->db->fetch_array($tables, vB_Database::DBARRAY_NUM))
		{
			if (strpos($table[0], TABLE_PREFIX . 'aaggregate_temp_') !== false OR strpos($table[0], TABLE_PREFIX . 'taggregate_temp_') !== false)
			{
				if (!preg_match('/_(\d+)$/siU', $table[0], $matches))
				{
					continue;
				}

				if ($matches[1] > TIMENOW - 3600)
				{
					continue;
				}

				$skip = false;
				$this->run_query(
					sprintf($this->phrase['core']['dropping_old_table_x'], $table[0]),
					"DROP TABLE IF EXISTS " . $table[0]
				);
			}
		}

		if ($skip)
		{
			$this->skip_message();
		}
	}
}

class vB_Upgrade_370rc4 extends vB_Upgrade_Version
{
	/**
	* Step #1
	* special case: memberlist, modifyattachments, reputationbit
	* add missing sessionhash. Let later query add security tokens
	* special case for headinclude: the JS variable
	*
	*/
	public function step_1()
	{
		//Any user styles that exist at this point are incompatible with vb5.  So this
		//would do nothing useful in the long run.  We don't even use the sessionhash
		//params in vB5. And we don't want to compile any old style templates
		//because that will likely fail
		$this->skip_message();
	}

	/**
	* Step #2 - add the security token to all forms
	*
	*/
	public function step_2()
	{
		//Any user styles that exist at this point are incompatible with vb5.  So this
		//would do nothing useful in the long run.  And we don't want to compile any old style templates
		//because that will likely fail
		$this->skip_message();
	}

	/**
	* Step #3 - special case for headinclude: the JS variable
	*
	*/
	public function step_3()
	{
		//This template will eventually be removed from the master style.  Any user
		//styles that exist at this point are incompatible with vb5.
		//This also will attempt to compile old style
		//template syntax, which is being removed from the system.
		$this->skip_message();
	}

	/**
	* Step #4 - special case for who's online: a form that should be get
	*
	*/
	public function step_4()
	{
		//Any user styles that exist at this point are incompatible with vb5.  So this
		//would do nothing useful in the long run.  And we don't want to compile any old style templates
		//because that will likely fail
		$this->skip_message();
	}

	public function step_5()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'setting', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "setting CHANGE datatype datatype ENUM('free', 'number', 'boolean', 'bitfield', 'username', 'integer', 'posint') NOT NULL DEFAULT 'free'"
		);
	}
}

class vB_Upgrade_380a2 extends vB_Upgrade_Version
{
	public $PREV_VERSION = '3.7.1+';
	public $VERSION_COMPAT_STARTS = '3.7.1';
	public $VERSION_COMPAT_ENDS   = '3.7.99';

	public function step_1()
	{
		if (!isset($this->registry->bf_ugp_socialgrouppermissions['canuploadgroupicon']))
		{
			$this->add_error($this->phrase['core']['wrong_bitfield_xml'], self::PHP_TRIGGER_ERROR, true);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_2()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'prefixpermission'),
			"CREATE TABLE " . TABLE_PREFIX . "prefixpermission (
				prefixid VARCHAR(25) NOT NULL,
				usergroupid SMALLINT UNSIGNED NOT NULL,
				KEY prefixusergroup (prefixid, usergroupid)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_3()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'albumupdate'),
			"CREATE TABLE " . TABLE_PREFIX . "albumupdate (
				albumid INT UNSIGNED NOT NULL DEFAULT '0',
				dateline INT UNSIGNED NOT NULL DEFAULT '0',
				PRIMARY KEY (albumid)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_4()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'pmthrottle'),
			"CREATE TABLE " . TABLE_PREFIX . "pmthrottle (
				userid INT unsigned NOT NULL,
				dateline INT unsigned NOT NULL,
				KEY userid (userid)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_5()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'discussion'),
			"CREATE TABLE " . TABLE_PREFIX . "discussion (
				discussionid INT unsigned NOT NULL auto_increment,
				groupid INT unsigned NOT NULL,
				firstpostid INT unsigned NOT NULL,
				lastpostid INT unsigned NOT NULL,
				lastpost INT unsigned NOT NULL,
				lastposter VARCHAR(255) NOT NULL,
				lastposterid INT unsigned NOT NULL,
				visible INT unsigned NOT NULL default '0',
				deleted INT unsigned NOT NULL default '0',
				moderation INT unsigned NOT NULL default '0',
				subscribers ENUM('0', '1') default '0',
				PRIMARY KEY  (discussionid),
				KEY groupid (groupid, lastpost)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_6()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'groupread'),
			"CREATE TABLE " . TABLE_PREFIX . "groupread (
				userid INT unsigned NOT NULL,
				groupid INT unsigned NOT NULL,
				readtime INT unsigned NOT NULL,
				PRIMARY KEY  (userid, groupid),
				KEY readtime (readtime)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_7()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'discussionread'),
			"CREATE TABLE " . TABLE_PREFIX . "discussionread (
				userid INT unsigned NOT NULL,
				discussionid INT unsigned NOT NULL,
				readtime INT unsigned NOT NULL,
				PRIMARY KEY (userid, discussionid),
				KEY readtime (readtime)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_8()
	{
	 	$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'socialgroupcategory'),
			"CREATE TABLE " . TABLE_PREFIX . "socialgroupcategory (
				 socialgroupcategoryid INT unsigned NOT NULL auto_increment,
				 creatoruserid INT unsigned NOT NULL,
				 title VARCHAR(250) NOT NULL,
				 description TEXT NOT NULL,
				 displayorder INT unsigned NOT NULL,
				 lastupdate INT unsigned NOT NULL,
				 `groups` INT unsigned default '0',
				 PRIMARY KEY  (socialgroupcategoryid),
				 KEY displayorder (displayorder)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_9()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'subscribegroup'),
			"CREATE TABLE " . TABLE_PREFIX . "subscribegroup (
				subscribegroupid INT unsigned NOT NULL auto_increment,
				userid INT unsigned NOT NULL,
				groupid INT unsigned NOT NULL,
				PRIMARY KEY  (subscribegroupid),
				UNIQUE KEY usergroup (userid, groupid),
				KEY groupid (groupid)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_10()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'subscribediscussion'),
			"CREATE TABLE " . TABLE_PREFIX . "subscribediscussion (
				subscribediscussionid INT unsigned NOT NULL auto_increment,
				userid INT unsigned NOT NULL,
				discussionid INT unsigned NOT NULL,
				emailupdate SMALLINT unsigned NOT NULL default '0',
				PRIMARY KEY (subscribediscussionid),
				UNIQUE KEY userdiscussion (userid, discussionid),
				KEY discussionid (discussionid)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_11()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'socialgroupicon'),
			"CREATE TABLE " . TABLE_PREFIX . "socialgroupicon (
				groupid INT unsigned NOT NULL default '0',
				userid INT unsigned default '0',
				filedata mediumblob,
				extension VARCHAR(20) NOT NULL default '',
				dateline INT unsigned NOT NULL default '0',
				width INT unsigned NOT NULL default '0',
				height INT unsigned NOT NULL default '0',
				thumbnail_filedata mediumblob,
				thumbnail_width INT unsigned NOT NULL default '0',
				thumbnail_height INT unsigned NOT NULL default '0',
				PRIMARY KEY  (groupid)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_12()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'profileblockprivacy'),
			"CREATE TABLE " . TABLE_PREFIX . "profileblockprivacy (
				userid INT UNSIGNED NOT NULL,
				blockid varchar(255) NOT NULL,
				requirement SMALLINT UNSIGNED NOT NULL DEFAULT '0',
				PRIMARY KEY (userid, blockid)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_13()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'noticedismissed'),
			"CREATE TABLE " . TABLE_PREFIX . "noticedismissed (
				noticeid INT UNSIGNED NOT NULL DEFAULT '0',
				userid INT UNSIGNED NOT NULL DEFAULT '0',
				PRIMARY KEY (noticeid,userid),
				KEY userid (userid)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_14()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'event', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "event CHANGE utc utc DECIMAL(4,2) NOT NULL DEFAULT '0.0'"
		);
	}

	public function step_15()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'prefix', 1, 1),
			'prefix',
			'options',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_16()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'pm', 1, 1),
			'pm',
			'parentpmid',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_17()
	{
		$this->add_field(
			$this->phrase['version']['380a2']['updating_profile_categories'],
			'profilefieldcategory',
			'allowprivacy',
			'smallint',
			self::FIELD_DEFAULTS
		);
	}

	public function step_18()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'socialgroup', 1, 5),
			'socialgroup',
			'lastdiscussionid',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_19()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'socialgroup', 2, 5),
			'socialgroup',
			'discussions',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_20()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'socialgroup', 3, 5),
			'socialgroup',
			'lastdiscussion',
			'varchar',
			array('length' => 255, 'attributes' => self::FIELD_DEFAULTS)
		);
	}

	public function step_21()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'socialgroup', 4, 5),
			'socialgroup',
			'lastupdate',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_22()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'socialgroup', 5, 5),
			'socialgroup',
			'transferowner',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_23()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'usergroup', 1, 3),
			'usergroup',
			'pmthrottlequantity',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_24()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'usergroup', 2, 3),
			'usergroup',
			'groupiconmaxsize',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_25()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'usergroup', 3, 3),
			'usergroup',
			'maximumsocialgroups',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_26()
	{
		$this->add_index(
			sprintf($this->phrase['version']['380a2']['create_index_on_x'], TABLE_PREFIX . 'usernote'),
			'usernote',
			'posterid',
			array('posterid')
		);
	}

	public function step_27()
	{
		$this->drop_index(
			sprintf($this->phrase['version']['380a2']['alter_index_on_x'], TABLE_PREFIX . 'moderator'),
			'moderator',
			'userid'
		);
	}

	public function step_28()
	{
		//used to add userid_forumid which is dropped in 505rc1
		$this->skip_message();
	}

	public function step_29()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'groupmessage'),
			"UPDATE " . TABLE_PREFIX . 'socialgroup
			SET lastupdate = ' . TIMENOW
		);
	}

	public function step_30()
	{
		if ($this->field_exists('groupmessage', 'groupid'))
		{
			$this->drop_index(
				sprintf($this->phrase['version']['380a2']['alter_index_on_x'], TABLE_PREFIX . 'groupmessage'),
				'groupmessage',
				'groupid'
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_31()
	{
		if ($this->field_exists('groupmessage', 'groupid'))
		{
			$this->add_field(
				sprintf($this->phrase['core']['altering_x_table'], 'groupmessage', 1, 1),
				'groupmessage',
				'discussionid',
				'int',
				self::FIELD_DEFAULTS
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_32()
	{
		if ($this->field_exists('groupmessage', 'groupid'))
		{
			$this->add_index(
				sprintf($this->phrase['version']['380a2']['create_index_on_x'], TABLE_PREFIX . 'groupmessage'),
				'groupmessage',
				'discussionid',
				array('discussionid', 'dateline', 'state')
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_33()
	{
		if ($this->field_exists('groupmessage', 'groupid'))
		{
			$this->add_index(
				sprintf($this->phrase['version']['380a2']['fulltext_index_on_x'], TABLE_PREFIX . 'groupmessage'),
				'groupmessage',
				'gm_ft',
				array('title', 'pagetext'),
				'fulltext'
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_34()
	{
		if ($this->field_exists('groupmessage', 'groupid'))
		{
			$this->run_query(
				$this->phrase['version']['380a2']['convert_messages_to_discussion'],
				"REPLACE INTO " . TABLE_PREFIX . "discussion (groupid, firstpostid, lastpostid)
				SELECT gm.groupid, MIN(gm.gmid) AS firstpostid, MAX(gm.gmid) AS lastpostid
				FROM " . TABLE_PREFIX . "groupmessage AS gm
				LEFT JOIN " . TABLE_PREFIX . "socialgroup AS sg
				 ON sg.groupid = gm.groupid
				GROUP BY gm.groupid
			");
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_35()
	{
		if ($this->field_exists('groupmessage', 'groupid'))
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'groupmessage'),
				"UPDATE " . TABLE_PREFIX . "groupmessage AS gm, " . TABLE_PREFIX . "discussion as gd
				SET gm.discussionid = gd.discussionid
				WHERE gm.groupid = gd.groupid
			");
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_36()
	{
		if ($this->field_exists('groupmessage', 'groupid'))
		{
			$this->run_query(
				$this->phrase['version']['380a2']['set_discussion_titles'],
				"UPDATE " . TABLE_PREFIX . "groupmessage gm
				INNER JOIN " . TABLE_PREFIX . "discussion d
				 ON gm.gmid = d.firstpostid
				INNER JOIN " . TABLE_PREFIX . "socialgroup sg
				 ON sg.groupid = d.groupid
				SET gm.title = IF(gm.title='',sg.name,gm.title)
			");
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_37()
	{
		if ($this->field_exists('groupmessage', 'groupid'))
		{
			$this->run_query(
				$this->phrase['version']['380a2']['update_last_post'],
				"UPDATE " . TABLE_PREFIX . "discussion d
				INNER JOIN " . TABLE_PREFIX . "groupmessage gm
				 ON gm.gmid = d.lastpostid
				SET d.lastpost = gm.dateline,
				    d.lastposter = gm.postusername,
				    d.lastposterid = gm.postuserid
			");
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_38()
	{
		if ($this->field_exists('groupmessage', 'groupid'))
		{
			// Get discussion counters
			$temptable = TABLE_PREFIX . 'discussion_temp_' . TIMENOW;

			$this->run_query($this->phrase['version']['380a2']['update_discussion_counters'],
				"CREATE TABLE $temptable (
					discussionid INT unsigned NOT NULL,
					visible INT unsigned DEFAULT '0',
					moderation INT unsigned DEFAULT '0',
					deleted INT unsigned DEFAULT '0',
					PRIMARY KEY(discussionid)
				)"
			);

			$this->run_query($this->phrase['version']['380a2']['update_discussion_counters'],
				"REPLACE INTO $temptable (discussionid, visible, moderation, deleted)
				SELECT discussionid,
					SUM(IF(state = 'visible', 1, 0)) AS visible,
					SUM(IF(state = 'deleted', 1, 0)) AS deleted,
					SUM(IF(state = 'moderation', 1, 0)) AS moderation
				FROM " . TABLE_PREFIX . "groupmessage
				GROUP BY discussionid
			");

			$this->run_query($this->phrase['version']['380a2']['update_discussion_counters'],
				"UPDATE " . TABLE_PREFIX . "discussion AS d
				INNER JOIN $temptable AS temp
				 ON temp.discussionid = d.discussionid
				SET d.visible = temp.visible,
					d.moderation = temp.moderation,
					d.deleted = temp.deleted
			");

			$this->run_query($this->phrase['version']['380a2']['update_discussion_counters'],
				"DROP TABLE $temptable"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_39()
	{
		if ($this->field_exists('groupmessage', 'groupid'))
		{
			$temptable = TABLE_PREFIX . "socialgroup" . TIMENOW;

			$this->run_query($this->phrase['version']['380a2']['update_group_message_counters'],
				"CREATE TABLE $temptable (
					groupid INT unsigned NOT NULL,
					visible INT unsigned DEFAULT '0',
					moderation INT unsigned DEFAULT '0',
					deleted INT unsigned DEFAULT '0',
					discussions INT unsigned DEFAULT '0',
					PRIMARY KEY (groupid)
				)
			");

			$this->run_query($this->phrase['version']['380a2']['update_group_message_counters'],
				"REPLACE INTO $temptable (groupid, visible, moderation, deleted, discussions)
				SELECT discussion.groupid,
						SUM(IF(state != 'visible',0,visible)) AS visible,
						SUM(deleted) AS deleted,
						SUM(moderation) AS moderation,
						SUM(IF(state = 'visible', 1, 0)) AS discussions
				FROM " . TABLE_PREFIX . "discussion AS discussion
				LEFT JOIN " . TABLE_PREFIX . "groupmessage AS gm
					ON gm.gmid = discussion.firstpostid
				GROUP BY discussion.groupid
			");

			$this->run_query($this->phrase['version']['380a2']['update_group_message_counters'],
				"UPDATE " . TABLE_PREFIX . "socialgroup AS sg
				INNER JOIN $temptable AS temp
				 ON temp.groupid = sg.groupid
				SET sg.visible = temp.visible,
					sg.moderation = temp.moderation,
					sg.deleted = temp.deleted,
					sg.discussions = temp.discussions
			");

			$this->run_query($this->phrase['version']['380a2']['update_group_message_counters'],
				"DROP TABLE $temptable"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_40()
	{
		if ($this->field_exists('groupmessage', 'groupid'))
		{
			$this->drop_field(
				sprintf($this->phrase['core']['altering_x_table'], 'groupmessage', 1, 1),
				'groupmessage',
				'groupid'
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_41()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'socialgroup', 1, 1),
			'socialgroup',
			'socialgroupcategoryid',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_42()
	{
		$this->add_index(
			sprintf($this->phrase['version']['380a2']['create_index_on_x'], TABLE_PREFIX . 'socialgroup'),
			'socialgroup',
			'socialgroupcategoryid',
			array('socialgroupcategoryid')
		);
	}

	public function step_43()
	{
		$this->run_query(
			$this->phrase['version']['380a2']['creating_default_group_category'],
			"REPLACE INTO " . TABLE_PREFIX . "socialgroupcategory
				(socialgroupcategoryid, creatoruserid, title, description, displayorder, lastupdate)
			VALUES
				(1, 1, '" . $this->db->escape_string($this->phrase['version']['380a2']['uncategorized']) . "',
				'" . $this->db->escape_string($this->phrase['version']['380a2']['uncategorized_description']) . "', 1, " . TIMENOW . ")
		");
	}

	public function step_44()
	{
		$this->run_query(
			$this->phrase['version']['380a2']['move_groups_to_default_category'],
			"UPDATE " . TABLE_PREFIX . "socialgroup
			SET socialgroupcategoryid = 1
		");
	}

	public function step_45()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'pmtext', 1, 1),
			'pmtext',
			'reportthreadid',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_46()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'notice', 1, 1),
			'notice',
			'dismissible',
			'smallint',
			self::FIELD_DEFAULTS
		);
	}

	public function step_47()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'useractivation', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "useractivation CHANGE activationid activationid VARCHAR(40) NOT NULL DEFAULT ''"
		);
	}

	public function step_48()
	{
		// Human Verification options and permissions
		if ($this->registry->options['hvcheck_registration']
			OR $this->registry->options['hvcheck_post']
			OR $this->registry->options['hvcheck_search']
			OR $this->registry->options['hvcheck_contactus']
			OR $this->registry->options['hvcheck_lostpw'])
		{
			$hvcheck = 0;
			$hvcheck += ($this->registry->options['hvcheck_registration'] ? $this->registry->bf_misc_hvcheck['register'] : 0);
			$hvcheck += ($this->registry->options['hvcheck_post'] ? $this->registry->bf_misc_hvcheck['post'] : 0);
			$hvcheck += ($this->registry->options['hvcheck_search'] ? $this->registry->bf_misc_hvcheck['search'] : 0);
			$hvcheck += ($this->registry->options['hvcheck_contactus'] ? $this->registry->bf_misc_hvcheck['contactus'] : 0);
			$hvcheck += ($this->registry->options['hvcheck_lostpw'] ? $this->registry->bf_misc_hvcheck['lostpw'] : 0);

			$this->run_query(
				$this->phrase['version']['380a2']['updating_usergroup_permissions'],
				"UPDATE " . TABLE_PREFIX . "usergroup SET
					genericpermissions = genericpermissions | " . $this->registry->bf_ugp_genericoptions['requirehvcheck'] . "
				 WHERE usergroupid = 1"
			);
		}
		else
		{
			$hvcheck = array_sum($this->registry->bf_misc_hvcheck);
		}

		$this->run_query(
			$this->phrase['version']['380a2']['update_hv_options'],
			"REPLACE INTO " . TABLE_PREFIX . "setting
				(varname, grouptitle, value, volatile, product)
			VALUES ('hvcheck', 'humanverification', $hvcheck, 1, 'vbulletin')"
		);
	}

	public function step_49()
	{
		//some old bitfield values that we have removed from the current App.  Leaving them in the
		//xml causes other issues (we have to hide them in UI that works off the XML etc).
		//We'll just hard code them in the old steps that use them.
		$canprofilepic = 128;
		$cananimateprofilepic = 134217728;

		$this->run_query(
			sprintf($this->phrase['version']['380a2']['granting_permissions'], 'usergroup', 1, 1),
			"UPDATE " . TABLE_PREFIX . "usergroup SET
				usercsspermissions = usercsspermissions |
					IF(forumpermissions & " . $this->registry->bf_ugp_genericpermissions['canmodifyprofile'] . ", " . $this->registry->bf_ugp_usercsspermissions['caneditprivacy'] . ", 0),
				forumpermissions = forumpermissions |
					IF(forumpermissions & " . $this->registry->bf_ugp_forumpermissions['cangetattachment'] . ", " . $this->registry->bf_ugp_forumpermissions['canseethumbnails'] . ", 0),
				genericoptions = genericoptions |
					IF(usergroupid = 1," . $this->registry->bf_ugp_genericoptions['requirehvcheck'] . ", 0),
				socialgrouppermissions = socialgrouppermissions |
					IF(forumpermissions & " . $this->registry->bf_ugp_forumpermissions['canreply'] . ", " . $this->registry->bf_ugp_socialgrouppermissions['canpostmessage'] . ", 0) |
					IF(adminpermissions & " . $this->registry->bf_ugp_adminpermissions['ismoderator'] . ", " . $this->registry->bf_ugp_socialgrouppermissions['canalwayspostmessage'] . ", 0) |
					IF(forumpermissions & " . $this->registry->bf_ugp_forumpermissions['canpostnew'] . ", " . $this->registry->bf_ugp_socialgrouppermissions['cancreatediscussion'] . ", 0) |
					IF(adminpermissions & " . $this->registry->bf_ugp_adminpermissions['ismoderator'] . ", " . $this->registry->bf_ugp_socialgrouppermissions['canalwayscreatediscussion'] . ", 0) |
					IF(forumpermissions & " . $this->registry->bf_ugp_forumpermissions['canopenclose'] . ", " . $this->registry->bf_ugp_socialgrouppermissions['canlimitdiscussion'] . ", 0) |
					IF(forumpermissions & " . $this->registry->bf_ugp_forumpermissions['candeletethread'] . ", " . $this->registry->bf_ugp_socialgrouppermissions['canmanagediscussions'] . ", 0) |
					IF(genericpermissions & " . $canprofilepic . ", " . $this->registry->bf_ugp_socialgrouppermissions['canuploadgroupicon'] . ", 0) |
					IF(genericpermissions & " . $cananimateprofilepic . ", " . $this->registry->bf_ugp_socialgrouppermissions['cananimategroupicon'] . ", 0),
				groupiconmaxsize = profilepicmaxsize,
				pmthrottlequantity = 0,
				maximumsocialgroups = 5
			"
		);
	}

	public function step_50()
	{
		$this->run_query(
			sprintf($this->phrase['version']['380a2']['granting_permissions'], 'forumpermission', 1, 1),
			"UPDATE " . TABLE_PREFIX . "forumpermission SET
				forumpermissions = forumpermissions |
					IF(forumpermissions & " . $this->registry->bf_ugp_forumpermissions['cangetattachment'] . ", " . $this->registry->bf_ugp_forumpermissions['canseethumbnails'] . ", 0)
			"
		);
	}

	public function step_51()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'moderator', 1, 1),
			"UPDATE " . TABLE_PREFIX . "moderator SET
				permissions2 = permissions2 |
					IF(permissions & " . $this->registry->bf_misc_moderatorpermissions2['caneditgroupmessages'] . ", " . $this->registry->bf_misc_moderatorpermissions2['caneditsocialgroups'] . ", 0) |
					IF(permissions & " . $this->registry->bf_misc_moderatorpermissions2['candeletegroupmessages'] . ", " . $this->registry->bf_misc_moderatorpermissions2['candeletediscussions'] . ", 0) |
					IF(permissions & " . $this->registry->bf_misc_moderatorpermissions2['candeletesocialgroups'] . ", " . $this->registry->bf_misc_moderatorpermissions2['cantransfersocialgroups'] . ", 0) |
					IF(permissions & " . $this->registry->bf_misc_moderatorpermissions2['canremovegroupmessages'] . ", " . $this->registry->bf_misc_moderatorpermissions2['canremovediscussions'] . ", 0) |
					IF(permissions & " . $this->registry->bf_misc_moderatorpermissions2['canmoderategroupmessages'] . ", " . $this->registry->bf_misc_moderatorpermissions2['canmoderatediscussions'] . ", 0)
			"
		);
	}

	public function step_52()
	{
		$this->show_message($this->phrase['version']['380a2']['update_album_update_counters']);
		require_once(DIR . '/install/legacy/380a2/includes.php');
		$this->registry->options['album_recentalbumdays'] = 7;
		exec_rebuild_album_updates();

		$this->db->query_write("
			DELETE FROM " . TABLE_PREFIX . "phrase
			WHERE varname LIKE 'notice\_%\_title'
				AND fieldname = 'global'
		");

		require_once(DIR . '/includes/adminfunctions_prefix.php');
		build_prefix_datastore();
	}
}

class vB_Upgrade_380b2 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'user'),
			"UPDATE " . TABLE_PREFIX . "user SET options = options | " . $this->registry->bf_misc_useroptions['pmdefaultsavecopy']
		);
	}

	/**
	* Step #2 - retire existing styles
	*
	*/
	public function step_2()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'setting'),
			"UPDATE " . TABLE_PREFIX . "setting SET value = value | " . $this->registry->bf_misc_useroptions['pmdefaultsavecopy'] . " WHERE varname = 'defaultregoptions'"
		);
	}
}

class vB_Upgrade_380b3 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'forumpermission', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "forumpermission CHANGE forumpermissionid forumpermissionid INT UNSIGNED NOT NULL AUTO_INCREMENT"
		);
	}
}

class vB_Upgrade_380rc1 extends vB_Upgrade_Version
{
	public function step_1()
	{
		require_once(DIR . '/includes/functions_calendar.php');
		build_events();
		$this->show_message($this->phrase['version']['380rc1']['rebuild_event_cache']);
	}
}

class vB_Upgrade_380rc2 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->run_query(
			$this->phrase['version']['380rc2']['updating_mail_ssl_setting'],
			"UPDATE " . TABLE_PREFIX . "setting SET value = 'ssl', datatype = 'free' WHERE varname = 'smtp_tls' AND value = '1'"
		);
	}
}

class vB_Upgrade_386 extends vB_Upgrade_Version
{
	public function step_1()
	{
		//Update Admins and Super Mods to have the "canignorequota" perm in case they don't have it...
		//This is the value for bf_ugp_pmpermissions['canignorequota'] but rather than relying
		//on the system being sufficiently functional at this point to load it from the DB we'll
		//hardcode it.  Even if it for some reason changed any DB we'd be upgrading is going to be
		//expecting it be the old value anyway.
		$canignorequotaperm = 4;
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'usergroup', 1, 1),
			"UPDATE " . TABLE_PREFIX . "usergroup
			SET pmpermissions = pmpermissions + $canignorequotaperm
			WHERE usergroupid IN (5, 6) AND NOT (pmpermissions & $canignorequotaperm)
		");
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 112199 $
|| #######################################################################
\*=========================================================================*/
