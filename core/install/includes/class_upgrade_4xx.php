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

class vB_Upgrade_400a1 extends vB_Upgrade_Version
{
	public $PREV_VERSION = '3.8.7+';
	public $VERSION_COMPAT_STARTS = '3.8.7';
	public $VERSION_COMPAT_ENDS   = '3.8.99';

	public function step_1()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'reputation', 1, 4),
			"ALTER TABLE " . TABLE_PREFIX . "reputation CHANGE postid postid INT UNSIGNED NOT NULL DEFAULT '1'"
		);
	}

	public function step_2()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'reputation', 2, 4),
			"ALTER TABLE " . TABLE_PREFIX . "reputation CHANGE userid userid INT UNSIGNED NOT NULL DEFAULT '1'"
		);
	}

	public function step_3()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'reputation', 3, 4),
			"ALTER TABLE " . TABLE_PREFIX . "reputation CHANGE whoadded whoadded INT UNSIGNED NOT NULL DEFAULT '0'"
		);
	}

	public function step_4()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'reputation', 4, 4),
			"ALTER TABLE " . TABLE_PREFIX . "reputation CHANGE dateline dateline INT UNSIGNED NOT NULL DEFAULT '0'"
		);
	}

	public function step_5()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'subscribegroup', 1, 1),
			'subscribegroup',
			'emailupdate',
			'enum',
			array('attributes' => "('daily', 'weekly', 'none')", 'null' => false, 'default' => 'none')
		);
	}

	public function step_6()
	{
		$this->skip_message();
		// Not required for vB5 update
	}

	public function step_7()
	{
		//4.0 table changes.
		$this->drop_index(
			sprintf($this->phrase['core']['altering_x_table'], 'reputation', 1, 2),
			'reputation',
			'whoadded_postid'
		);
	}

	public function step_8()
	{
		//previously added "whoadded_postid" but this is dropped in 500a1
		$this->skip_message();
	}

	public function step_9() // DUPLICATE OF STEP 5 //
	{
		$this->skip_message();
	}

	public function step_10()
	{
		$this->skip_message();
		// Not required for vB5 update
	}

	public function step_11()
	{
		$this->skip_message();
		// Not required for vB5
	}

	public function step_12()
	{
		$this->skip_message();
		// Not required for vB5
	}

	public function step_13()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'tachyforumpost', 1, 1),
			'tachyforumpost',
			'lastposterid',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_14()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'tachythreadpost', 1, 1),
			'tachythreadpost',
			'lastposterid',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_15()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'style', 1, 1),
			'style',
			'newstylevars',
			'mediumtext',
			self::FIELD_DEFAULTS
		);
	}

	public function step_16()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'template', 1, 1),
			"ALTER  TABLE  " . TABLE_PREFIX . "template ADD mergestatus ENUM('none', 'merged', 'conflicted') NOT NULL DEFAULT 'none'",
			self::MYSQL_ERROR_COLUMN_EXISTS
		);
	}

	public function step_17()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'templatemerge'),
			"CREATE TABLE " . TABLE_PREFIX . "templatemerge (
				templateid INT UNSIGNED NOT NULL DEFAULT '0',
				template MEDIUMTEXT,
				version VARCHAR(30) NOT NULL DEFAULT '',
				savedtemplateid INT UNSIGNED NOT NULL DEFAULT '0',
				PRIMARY KEY (templateid)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	/*
	* Steps 18, 19 & 20 are not required for vB5 update.
	*/
	public function step_18()
	{
		$this->skip_message();
	}

	public function step_19()
	{
		$this->skip_message();
	}

	public function step_20()
	{
		$this->skip_message();
	}

	public function step_21()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'tag', 1, 1),
			'tag',
			'canonicaltagid',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_22()
	{
		$this->add_index(
			sprintf($this->phrase['core']['create_index_x_on_y'], 'canonicaltagid', TABLE_PREFIX . 'tag'),
			'tag',
			'canonicaltagid',
			array('canonicaltagid')
		);
	}

	public function step_23()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . "phrasetype"),
				"ALTER TABLE " . TABLE_PREFIX . "tag ENGINE={$this->hightrafficengine} ",
				sprintf($this->phrase['vbphrase']['alter_table'], 'tag')
		);
	}

	/**
	* Step #24
	* note -- any changes to the type datamodel in later releases need to be reflected here if they break the core type module.
	* Otherwise the upgrade will not correctly run.  The changes should also be put in the later release upgrade for people
	* upgrading from releases later than 4.0a1 in a way that will not break if they changes were already made (the basic
	* add_field function handles this).
	*/
	public function step_24()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'contenttype'),
			"CREATE TABLE " . TABLE_PREFIX . "contenttype (
				contenttypeid INT UNSIGNED NOT NULL AUTO_INCREMENT,
				class VARBINARY(50) NOT NULL,
				packageid INT UNSIGNED NOT NULL,
				canplace ENUM('0','1') DEFAULT  '0',
				cansearch ENUM('0','1') DEFAULT '0',
				cantag ENUM('0','1') DEFAULT '0',
				canattach ENUM('0','1') DEFAULT '0',
				isaggregator ENUM('0','1') NOT NULL DEFAULT '0',
				PRIMARY KEY (contenttypeid),
				UNIQUE KEY package (packageid, class)
			) ENGINE={$this->hightrafficengine}
			",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_25()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . "contenttype"),
			"INSERT IGNORE INTO " . TABLE_PREFIX . "contenttype
				(contenttypeid, class, packageid, canplace, cansearch, cantag, canattach)
			VALUES
				(1, 'Post', 1, '0', '1', '0', '1'),
				(2, 'Thread', 1, '0', '0', '1', '0'),
				(3, 'Forum', 1, '0', '1', '0', '0'),
				(4, 'Announcement', 1, '0', '0', '0', '0'),
				(5, 'SocialGroupMessage', 1, '0', '1', '0', '0'),
				(6, 'SocialGroupDiscussion', 1, '0', '0', '0', '0'),
				(7, 'SocialGroup', 1, '0', '1', '0', '1'),
				(8, 'Album', 1, '0', '0', '0', '1'),
				(9, 'Picture', 1, '0', '0', '0', '0'),
				(10, 'PictureComment', 1, '0', '0', '0', '0'),
				(11, 'VisitorMessage', 1, '0', '1', '0', '0'),
				(12, 'User', 1, '0', '0', '0', '0'),
				(13, 'Event', 1, '0', '0', '0', '0'),
				(14, 'Calendar', 1, '0', '0', '0', '0')
		");
	}

	public function step_26()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'tagcontent'),
			"CREATE TABLE " . TABLE_PREFIX . "tagcontent (
				tagid INT UNSIGNED NOT NULL DEFAULT 0,
				contenttypeid INT UNSIGNED NOT NULL,
				contentid INT UNSIGNED NOT NULL DEFAULT '0',
				userid INT UNSIGNED NOT NULL DEFAULT '0',
				dateline INT UNSIGNED NOT NULL DEFAULT '0',
				PRIMARY KEY tag_type_cid (tagid, contenttypeid, contentid),
				KEY id_type_user (contentid, contenttypeid, userid),
				KEY user (userid),
				KEY dateline (dateline)
			) ENGINE={$this->hightrafficengine}
			",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_27()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'package'),
			"CREATE TABLE " . TABLE_PREFIX . "package (
				packageid INT UNSIGNED NOT NULL AUTO_INCREMENT,
				productid VARCHAR(25) NOT NULL,
				class VARBINARY(50) NOT NULL,
				PRIMARY KEY  (packageid),
				UNIQUE KEY class (class)
			)
			",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_28()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . "package"),
			"INSERT IGNORE INTO " . TABLE_PREFIX . "package (packageid, productid, class)
				VALUES
			(1, 'vbulletin', 'vBForum')"
		);
	}

	public function step_29()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'route'),
			"CREATE TABLE " . TABLE_PREFIX . "route (
				routeid INT UNSIGNED NOT NULL AUTO_INCREMENT,
				userrequest VARCHAR(50) NOT NULL,
				packageid INT UNSIGNED NOT NULL,
				class VARBINARY(50) NOT NULL,
				PRIMARY KEY (routeid),
				UNIQUE KEY (userrequest),
				UNIQUE KEY(packageid, class)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_30()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . "route"),
			"INSERT IGNORE INTO " . TABLE_PREFIX . "route
				(routeid, userrequest, packageid, class)
			VALUES
				(1, 'error', 1, 'Error')"
		);
	}

	public function step_31()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . "tagcontent"),
			"INSERT INTO " . TABLE_PREFIX . "tagcontent
				(tagid, contenttypeid, contentid, userid, dateline)
			SELECT tagid, 2, threadid, userid, dateline
			FROM " . TABLE_PREFIX . "tagthread
			ON DUPLICATE KEY UPDATE contenttypeid = 2",
			self::MYSQL_ERROR_TABLE_MISSING
		);
	}

	public function step_32()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . "phrasetype"),
			"INSERT IGNORE INTO " . TABLE_PREFIX . "phrasetype
				(title, editrows, fieldname, special)
			VALUES
				('{$this->phrase['phrasetype']['tagscategories']}', 3, 'tagscategories', 0),
				('{$this->phrase['phrasetype']['contenttypes']}', 3, 'contenttypes', 0)
			"
		);
	}

	public function step_33()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'language', 1, 2),
			'language',
			'phrasegroup_tagscategories',
			'mediumtext',
			self::FIELD_DEFAULTS
		);
	}

	public function step_34()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'language', 1, 2),
			'language',
			'phrasegroup_contenttypes',
			'mediumtext',
			self::FIELD_DEFAULTS
		);
	}

	public function step_35()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'cache'),
			"CREATE TABLE " . TABLE_PREFIX . "cache (
				cacheid VARBINARY(64) NOT NULL,
				expires INT UNSIGNED NOT NULL,
				created INT UNSIGNED NOT NULL,
				locktime INT UNSIGNED NOT NULL,
				serialized ENUM('0','1') NOT NULL DEFAULT '0',
				data BLOB,
				PRIMARY KEY (cacheid)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_36()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'cacheevent'),
			"CREATE TABLE " . TABLE_PREFIX . "cacheevent (
				cacheid VARBINARY(64) NOT NULL,
				event VARBINARY(50) NOT NULL,
				PRIMARY KEY (cacheid, event),
				KEY event (event)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_37()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'action'),
			"CREATE TABLE " . TABLE_PREFIX . "action (
				actionid INT UNSIGNED NOT NULL AUTO_INCREMENT,
				routeid INT UNSIGNED NOT NULL,
				packageid INT UNSIGNED NOT NULL,
				controller VARBINARY(50) NOT NULL,
				useraction VARCHAR(50) NOT NULL,
				classaction VARBINARY(50) NOT NULL,
				PRIMARY KEY (actionid),
				UNIQUE KEY useraction (routeid, useraction),
				UNIQUE KEY classaction (packageid, controller, classaction)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_38()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'contentpriority'),
			"CREATE TABLE " . TABLE_PREFIX . "contentpriority (
				contenttypeid varchar(20) NOT NULL,
		  		sourceid INT(10) UNSIGNED NOT NULL,
		  		prioritylevel DOUBLE(2,1) UNSIGNED NOT NULL,
		  		PRIMARY KEY (contenttypeid, sourceid)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	/**
	* Step #39
	*
	*	Add cron job for sitemap
	*/
	public function step_39()
	{
		$this->add_cronjob(
			array(
				'varname'  => 'sitemap',
				'nextrun'  => 1232082000,
				'weekday'  => -1,
				'day'      => -1,
				'hour'     => 5,
				'minute'   => 'a:1:{i:0;i:0;}',
				'filename' => './includes/cron/sitemap.php',
				'loglevel' => 1,
				'volatile' => 1,
				'product'  => 'vbulletin'
			)
		);
	}

	public function step_40()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'searchcore'),
			"CREATE TABLE " . TABLE_PREFIX . "searchcore (
				searchcoreid INT UNSIGNED NOT NULL AUTO_INCREMENT,
				contenttypeid INT UNSIGNED NOT NULL,
				primaryid INT UNSIGNED  NOT NULL,
				groupcontenttypeid INT UNSIGNED NOT NULL,
				groupid INT UNSIGNED NOT NULL DEFAULT 0,
				dateline INT UNSIGNED NOT NULL DEFAULT 0,
				userid INT UNSIGNED NOT NULL DEFAULT 0,
				username VARCHAR(100) NOT NULL,
				ipaddress INT UNSIGNED NOT NULL,
				searchgroupid INT UNSIGNED NOT NULL,
				PRIMARY KEY (searchcoreid),
				UNIQUE KEY contentunique (contenttypeid, primaryid),
				KEY groupid (groupcontenttypeid, groupid),
				KEY ipaddress (ipaddress),
				KEY dateline (dateline),
				KEY userid (userid),
				KEY searchgroupid (searchgroupid)
			) ENGINE={$this->hightrafficengine}
			", self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_41()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'searchcore_text'),
			"CREATE TABLE " . TABLE_PREFIX . "searchcore_text (
				searchcoreid INT UNSIGNED NOT NULL,
				keywordtext MEDIUMTEXT,
				title VARCHAR(255) NOT NULL DEFAULT '',
				PRIMARY KEY (searchcoreid),
				FULLTEXT KEY text (title, keywordtext)
			) ENGINE=MyISAM
			", self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_42()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'searchgroup'),
			"CREATE TABLE " . TABLE_PREFIX . "searchgroup (
				searchgroupid INT UNSIGNED NOT NULL AUTO_INCREMENT,
				contenttypeid INT UNSIGNED NOT NULL,
				groupid INT UNSIGNED  NOT NULL,
				dateline INT UNSIGNED NOT NULL DEFAULT 0,
				userid INT UNSIGNED NOT NULL DEFAULT 0,
				username VARCHAR(100) NOT NULL,
				PRIMARY KEY (searchgroupid),
				UNIQUE KEY groupunique (contenttypeid, groupid),
				KEY dateline (dateline),
				KEY userid (userid)
			) ENGINE={$this->hightrafficengine}
			", self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_43()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'searchgroup_text'),
			"CREATE TABLE " . TABLE_PREFIX . "searchgroup_text (
				searchgroupid INT UNSIGNED NOT NULL,
				title VARCHAR(255) NOT NULL,
				PRIMARY KEY (searchgroupid),
				FULLTEXT KEY grouptitle (title)
			) ENGINE=MyISAM
			", self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_44()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'searchlog'),
			"CREATE TABLE " . TABLE_PREFIX . "searchlog (
				searchlogid INT UNSIGNED NOT NULL AUTO_INCREMENT,
				userid INT UNSIGNED NOT NULL DEFAULT '0',
				ipaddress VARCHAR(15) NOT NULL DEFAULT '',
				searchhash VARCHAR(32) NOT NULL,
				sortby VARCHAR(15) NOT NULL DEFAULT '',
				sortorder ENUM('asc','desc') NOT NULL DEFAULT 'asc',
				searchtime FLOAT UNSIGNED NOT NULL DEFAULT '0',
				dateline INT UNSIGNED NOT NULL DEFAULT '0',
				completed SMALLINT UNSIGNED NOT NULL DEFAULT '0',
				criteria TEXT NOT NULL,
				results MEDIUMBLOB,
				PRIMARY KEY (searchlogid),
				KEY search (userid, searchhash, sortby, sortorder),
				KEY userfloodcheck (userid, dateline),
				KEY ipfloodcheck (ipaddress, dateline)
			) ENGINE={$this->hightrafficengine}
			", self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_45()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'indexqueue'),
			"CREATE TABLE " . TABLE_PREFIX . "indexqueue (
					queueid INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
					contenttype VARCHAR(45) NOT NULL,
					newid INTEGER UNSIGNED NOT NULL,
					id2 INTEGER UNSIGNED NOT NULL,
					package VARCHAR(64) NOT NULL,
					operation VARCHAR(64) NOT NULL,
					data TEXT NOT NULL,
					PRIMARY KEY (queueid)
				)
			", self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_46()
	{
		$this->run_query(
			sprintf($this->phrase['core']['dropping_old_table_x'], TABLE_PREFIX . "search"),
			"DROP TABLE IF EXISTS " . TABLE_PREFIX . "search"
		);
	}

	public function step_47()
	{
		$this->run_query(
			sprintf($this->phrase['core']['dropping_old_table_x'], TABLE_PREFIX . "word"),
			"DROP TABLE IF EXISTS " . TABLE_PREFIX . "word"
		);
	}

	public function step_48()
	{
		$this->run_query(
			sprintf($this->phrase['core']['dropping_old_table_x'], TABLE_PREFIX . "tagthread"),
			"DROP TABLE IF EXISTS " . TABLE_PREFIX . "tagthread"
		);
	}

	public function step_49()
	{
		if (!$this->field_exists('attachment', 'filedataid') AND $this->field_exists('filedata', 'filedataid'))
		{
			// We have a vb3 attachment table and a vb4 filedata table which causes a problem so move the vb4 filedata table
			$this->run_query(
				sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . "filedata"),
				"RENAME TABLE " . TABLE_PREFIX . "filedata TO " . TABLE_PREFIX . "filedata" . vbrand(0, 1000000),
				self::MYSQL_ERROR_TABLE_EXISTS
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_50()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . "attachment"),
			"RENAME TABLE " . TABLE_PREFIX . "attachment TO " . TABLE_PREFIX . "filedata",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_51()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'attachment'),
			"CREATE TABLE " . TABLE_PREFIX . "attachment (
				attachmentid INT UNSIGNED NOT NULL AUTO_INCREMENT,
				contenttypeid INT UNSIGNED NOT NULL DEFAULT '0',
				contentid INT UNSIGNED NOT NULL DEFAULT '0',
				userid INT UNSIGNED NOT NULL DEFAULT '0',
				dateline INT UNSIGNED NOT NULL DEFAULT '0',
				filedataid INT UNSIGNED NOT NULL DEFAULT '0',
				state ENUM('visible', 'moderation') NOT NULL DEFAULT 'visible',
				counter INT UNSIGNED NOT NULL DEFAULT '0',
				posthash VARCHAR(32) NOT NULL DEFAULT '',
				filename VARCHAR(100) NOT NULL DEFAULT '',
				caption TEXT,
				reportthreadid INT UNSIGNED NOT NULL DEFAULT '0',
				PRIMARY KEY (attachmentid),
				KEY contenttypeid (contenttypeid, contentid),
				KEY contentid (contentid),
				KEY userid (userid, contenttypeid),
				KEY posthash (posthash, userid),
				KEY filedataid (filedataid, userid)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_52()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'attachmentcategory'),
			"CREATE TABLE " . TABLE_PREFIX . "attachmentcategory (
				categoryid INT UNSIGNED NOT NULL AUTO_INCREMENT,
				userid INT UNSIGNED NOT NULL DEFAULT '0',
				title VARCHAR(255) NOT NULL DEFAULT '',
				parentid INT UNSIGNED NOT NULL DEFAULT '0',
				displayorder INT UNSIGNED NOT NULL DEFAULT '0',
				PRIMARY KEY (categoryid),
				KEY userid (userid, parentid, displayorder)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_53()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'attachmentcategoryuser'),
			"CREATE TABLE " . TABLE_PREFIX . "attachmentcategoryuser (
				filedataid INT UNSIGNED NOT NULL DEFAULT '0',
				userid INT UNSIGNED NOT NULL DEFAULT '0',
				categoryid INT UNSIGNED NOT NULL DEFAULT '0',
				filename VARCHAR(100) NOT NULL DEFAULT '',
				dateline INT UNSIGNED NOT NULL DEFAULT '0',
				PRIMARY KEY (filedataid, userid),
				KEY categoryid (categoryid, userid, filedataid),
				KEY userid (userid, categoryid, dateline)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_54()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'picturelegacy'),
			"CREATE TABLE " . TABLE_PREFIX . "picturelegacy (
				type ENUM('album', 'group') NOT NULL DEFAULT 'album',
				primaryid INT UNSIGNED NOT NULL DEFAULT '0',
				pictureid INT UNSIGNED NOT NULL DEFAULT '0',
				attachmentid INT UNSIGNED NOT NULL DEFAULT '0',
				PRIMARY KEY (type, primaryid, pictureid)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_55()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'stylevar'),
			"CREATE TABLE " . TABLE_PREFIX . "stylevar (
				stylevarid varchar(191) NOT NULL,
				styleid SMALLINT NOT NULL DEFAULT '-1',
				value MEDIUMBLOB NOT NULL,
				dateline INT UNSIGNED NOT NULL DEFAULT '0',
				username VARCHAR(100) NOT NULL DEFAULT '',
				UNIQUE KEY stylevarinstance (stylevarid, styleid)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_56()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'stylevardfn'),
			"CREATE TABLE " . TABLE_PREFIX . "stylevardfn (
				stylevarid varchar(191) NOT NULL,
				styleid SMALLINT NOT NULL DEFAULT '-1',
				parentid SMALLINT NOT NULL,
				parentlist varchar(250) NOT NULL DEFAULT '0',
				stylevargroup varchar(250) NOT NULL,
				product varchar(25) NOT NULL default 'vbulletin',
				datatype varchar(25) NOT NULL default 'string',
				validation varchar(250) NOT NULL,
				failsafe MEDIUMBLOB NOT NULL,
				units enum('','%','px','pt','em','ex','pc','in','cm','mm') NOT NULL default '',
				uneditable tinyint(3) unsigned NOT NULL default '0',
				PRIMARY KEY (stylevarid)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_57()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'user', 1, 1),
			'user',
			'assetposthash',
			'varchar',
			array('length' => 32, 'attributes' => self::FIELD_DEFAULTS)
		);
	}

	public function step_58()
	{
		$this->skip_message();
	}

	public function step_59()
	{
		$this->skip_message();
	}

	/**
	* Step #60
	* Update attachments
	*
	* @param	array	contains id to startat processing at
	*
	* @return	mixed
	*/
	public function step_60($data = [])
	{
		$startat = intval($data['startat'] ?? 0);

		if ($this->field_exists('filedata', 'attachmentid'))
		{
			if ($startat == 0)
			{
				$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'attachment'));
			}

			$max = $this->db->query_first("
				SELECT MAX(attachmentid) AS maxid
				FROM " . TABLE_PREFIX . "filedata
			");

			$maxattachmentid = $max['maxid'];

			$count = $this->db->query_first("
				SELECT COUNT(*) AS count
				FROM " . TABLE_PREFIX . "filedata
				WHERE attachmentid < $startat
			");

			$done = $count['count'];

			$perpage = 50;
			$files = $this->db->query_read("
				SELECT attachmentid, postid, userid, dateline, visible, counter, filename
				FROM " . TABLE_PREFIX . "filedata
				WHERE attachmentid > $startat
				ORDER BY attachmentid ASC
				" . ($this->limitqueries ? "LIMIT 0, $perpage" : "") . "
			");

			$totalattach = $this->db->num_rows($files);
			if ($totalattach)
			{
				$lastid = 0;
				$sql = $sql2 = array();
				$count = 0;
				$processed = 0;
				while ($file = $this->db->fetch_array($files))
				{
					$count++;
					$sql[] = "(
						$file[attachmentid],
						1,
						$file[postid],
						$file[userid],
						$file[dateline],
						$file[attachmentid],
						'" . ($file['visible'] ? 'visible' : 'moderation') . "',
						$file[counter],
						'" . $this->db->escape_string($file['filename']) . "'
					)";

					$sql2[] = "(
						$file[attachmentid],
						$file[userid],
						0,
						'" . $this->db->escape_string($file['filename']) . "',
						$file[dateline]
					)";
					$lastid = $file['attachmentid'];

					$processed++;
					// Keep the amount of data inserted in one query low -- max_packet!
					if ($processed == $perpage OR $count == $totalattach)
					{
						$this->db->query_write("
							INSERT IGNORE INTO " . TABLE_PREFIX . "attachment
								(attachmentid, contenttypeid, contentid, userid, dateline, filedataid, state, counter, filename)
							VALUES
								" . implode(",\r\n\t\t", $sql) . "
						");
						$this->db->query_write("
							INSERT IGNORE INTO " . TABLE_PREFIX . "attachmentcategoryuser
								(filedataid, userid, categoryid, filename, dateline)
							VALUES
								" . implode(",\r\n\t\t", $sql2) . "
						");
						$sql = $sql2 = array();
						$processed = 0;
					}
				}
				if ($lastid)
				{
					$this->show_message(sprintf($this->phrase['version']['400a1']['convert_attachment'], $startat + 1, $lastid, $maxattachmentid), true);
				}
				else if ($startat)
				{
					$this->skip_message();
				}
				return array('startat' => $lastid);
			}
			else
			{
				$this->show_message($this->phrase['version']['400a1']['update_attachments_complete']);
			}
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_61()
	{
		if ($this->field_exists('filedata', 'attachmentid'))
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['alter_table'], 'filedata'),
				"ALTER TABLE " . TABLE_PREFIX . "filedata CHANGE attachmentid filedataid INT UNSIGNED NOT NULL AUTO_INCREMENT"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_62()
	{
		$this->add_field(
			sprintf($this->phrase['vbphrase']['alter_table'], 'filedata'),
			'filedata',
			'width',
			'smallint',
			self::FIELD_DEFAULTS
		);
	}

	public function step_63()
	{
		$this->add_field(
			sprintf($this->phrase['vbphrase']['alter_table'], 'filedata'),
			'filedata',
			'height',
			'smallint',
			self::FIELD_DEFAULTS
		);
	}

	public function step_64()
	{
		$this->add_field(
			sprintf($this->phrase['vbphrase']['alter_table'], 'filedata'),
			'filedata',
			'thumbnail_width',
			'smallint',
			self::FIELD_DEFAULTS
		);
	}

	public function step_65()
	{
		$this->add_field(
			sprintf($this->phrase['vbphrase']['alter_table'], 'filedata'),
			'filedata',
			'thumbnail_height',
			'smallint',
			self::FIELD_DEFAULTS
		);
	}

	public function step_66()
	{
		$this->add_field(
			sprintf($this->phrase['vbphrase']['alter_table'], 'filedata'),
			'filedata',
			'refcount',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_67()
	{
		if (!$this->field_exists('filedata', 'refcount'))
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['alter_table'], 'filedata'),
				"UPDATE " . TABLE_PREFIX . "filedata SET refcount = 1"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_68()
	{
		$this->add_index(
			sprintf($this->phrase['vbphrase']['alter_table'], 'filedata'),
			'filedata',
			'refcount',
			array('refcount', 'dateline')
		);
	}

	public function step_69()
	{
		$this->drop_field(
			sprintf($this->phrase['vbphrase']['alter_table'], 'filedata'),
			'filedata',
			'filename'
		);
	}

	public function step_70()
	{
		$this->drop_field(
			sprintf($this->phrase['vbphrase']['alter_table'], 'filedata'),
			'filedata',
			'counter'
		);
	}

	public function step_71()
	{
		$this->drop_field(
			sprintf($this->phrase['vbphrase']['alter_table'], 'filedata'),
			'filedata',
			'visible'
		);
	}

	public function step_72()
	{
		$this->drop_field(
			sprintf($this->phrase['vbphrase']['alter_table'], 'filedata'),
			'filedata',
			'postid'
		);
	}

	public function step_73()
	{
		$this->drop_index(
			sprintf($this->phrase['vbphrase']['alter_table'], 'filedata'),
			'filedata',
			'posthash'
		);
	}

	public function step_74()
	{
		$this->drop_field(
			sprintf($this->phrase['vbphrase']['alter_table'], 'filedata'),
			'filedata',
			'posthash'
		);
	}

	public function step_75()
	{
		$this->add_field(
			sprintf($this->phrase['vbphrase']['alter_table'], 'filedata'),
			'attachmenttype',
			'contenttypes',
			'mediumtext',
			self::FIELD_DEFAULTS
		);
	}

	public function step_76()
	{
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'attachmenttype'));
		$extensions = $this->db->query_read("
			SELECT *
			FROM " . TABLE_PREFIX . "attachmenttype
		");
		while ($ext = $this->db->fetch_array($extensions))
		{
			if (isset($ext['enabled']))
			{
				$cache = array(
					1 => array(
						'n' => $ext['newwindow'],
						'e' => $ext['enabled'],
					),
					2 => array(
						'n' => $ext['newwindow'],
						'e' => in_array($ext['extension'], array('gif','jpe','jpeg','jpg','png','bmp')) ? 1 : 0
					)
				);
				$this->db->query_write("
					UPDATE " . TABLE_PREFIX . "attachmenttype
					SET contenttypes = '" . $this->db->escape_string(serialize($cache)) . "'
					WHERE extension = '" . $this->db->escape_string($ext['extension']) . "'
				");
			}
		}
	}

	public function step_77()
	{
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], 'attachmenttype', 1, 3),
			'attachmenttype',
			'enabled'
		);
	}

	public function step_78()
	{
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], 'attachmenttype', 2, 3),
			'attachmenttype',
			'newwindow'
		);
	}

	public function step_79()
	{
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], 'attachmenttype', 3, 3),
			'attachmenttype',
			'thumbnail'
		);
	}

	public function step_80()
	{
		if ($this->field_exists('albumpicture', 'pictureid'))
		{
			$this->add_field(
				sprintf($this->phrase['core']['altering_x_table'], 'albumpicture', 1, 1),
				'albumpicture',
				'attachmentid',
				'int',
				self::FIELD_DEFAULTS
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_81()
	{
		if ($this->field_exists('albumpicture', 'pictureid'))
		{
			$this->add_field(
				sprintf($this->phrase['core']['altering_x_table'], 'socialgrouppicture', 1, 1),
				'socialgrouppicture',
				'attachmentid',
				'int',
				self::FIELD_DEFAULTS
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_82()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'usercss', 1, 1),
			'usercss',
			'converted',
			'smallint',
			self::FIELD_DEFAULTS
		);
	}

	public function step_83()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'picturecomment', 1, 2),
			'picturecomment',
			'filedataid',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_84()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'picturecomment', 2, 2),
			'picturecomment',
			'userid',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_85()
	{
		if ($this->field_exists('picturecomment_hash', 'pictureid'))
		{
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'picturecomment_hash', 1, 2),
				"ALTER TABLE " . TABLE_PREFIX . "picturecomment_hash CHANGE pictureid filedataid INT UNSIGNED NOT NULL DEFAULT '0'"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_86()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'picturecomment_hash', 2, 2),
			'picturecomment_hash',
			'userid',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_87()
	{
		if ($this->field_exists('album', 'coverpictureid'))
		{
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'album', 1, 1),
				"ALTER TABLE " . TABLE_PREFIX . "album CHANGE coverpictureid coverattachmentid INT UNSIGNED NOT NULL DEFAULT '0'"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_88()
	{
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], 'usergroup', 1, 1),
			'usergroup',
			'albumpicmaxsize'
		);
	}

	/**
	* Step #89 - Convert Albums
	*
	* @param	array	contains id to startat processing at
	*
	* @return	mixed	Startat value for next go round
	*/
	public function step_89($data = [])
	{
		require_once(DIR . '/install/legacy/400a1/includes.php');
		vB_Upgrade::createAdminSession();
		$startat = intval($data['startat'] ?? 0);
		$perpage = 25;
		$users = array();
		// Convert Albums
		$db_alter = new vB_Database_Alter_MySQL($this->db);
		if ($db_alter->fetch_table_info('albumpicture'))
		{
			$pictures = $this->db->query_read("
				SELECT
					albumpicture.albumid, albumpicture.dateline,
					picture.*
				FROM " . TABLE_PREFIX . "albumpicture AS albumpicture
				INNER JOIN " . TABLE_PREFIX . "picture AS picture ON (albumpicture.pictureid = picture.pictureid)
				WHERE
					albumpicture.pictureid > $startat
						AND
					albumpicture.attachmentid = 0
				ORDER BY albumpicture.pictureid ASC
				" . ($this->limitqueries ? "LIMIT 0, $perpage" : "") . "
			");

			if ($this->db->num_rows($pictures))
			{
				$lastid = 0;
				while ($picture = $this->db->fetch_array($pictures))
				{
					$this->show_message(sprintf($this->phrase['version']['400a1']['convert_picture'], $picture['pictureid']), true);
					$lastid = $picture['pictureid'];

					if ($this->registry->options['album_dataloc'] == 'db')
					{
						$thumbnail =& $picture['thumbnail'];
						$filedata =& $picture['filedata'];
					}
					else
					{
						$attachpath = $this->registry->options['album_picpath'] . '/' . floor($picture['pictureid'] / 1000) . "/$picture[pictureid].picture";
						if ($this->registry->options['album_dataloc'] == 'fs_directthumb')
						{
							$attachthumbpath = $this->registry->options['album_thumbpath'] . '/' . floor($picture['pictureid'] / 1000);
						}
						else
						{
							$attachthumbpath = $this->registry->options['album_picpath'] . '/' . floor($picture['pictureid'] / 1000);
						}
						$attachthumbpath .= "/$picture[idhash]_$picture[pictureid].$picture[extension]";

						$thumbnail = @file_get_contents($attachthumbpath);
						$filedata = @file_get_contents($attachpath);
						if ($filedata === false)
						{
							$this->show_message(sprintf($this->phrase['version']['400a1']['could_not_find_file'], $attachpath));
							continue;
						}
					}

					$attachdm = new vB_DataManager_AttachmentFiledata(vB_DataManager_Constants::ERRTYPE_UPGRADE);
					$attachdm->set('contenttypeid', 8);
					$attachdm->set('contentid', $picture['albumid']);
					$attachdm->set('filename', $picture['pictureid'] . '.' . $picture['extension']);
					$attachdm->set('width', $picture['width']);
					$attachdm->set('height', $picture['height']);
					$attachdm->set('state', $picture['state']);
					$attachdm->set('reportthreadid', $picture['reportthreadid']);
					$attachdm->set('userid', $picture['userid']);
					$attachdm->set('caption', $picture['caption']);
					$attachdm->set('dateline', $picture['dateline']);
					$attachdm->set('thumbnail_dateline', $picture['thumbnail_dateline']);
					$attachdm->setr('filedata', $filedata);
					$attachdm->setr('thumbnail', $thumbnail);

					if ($attachmentid = $attachdm->save())
					{
						$this->db->query_write("
							UPDATE " . TABLE_PREFIX . "albumpicture
							SET
								attachmentid = $attachmentid
							WHERE
								pictureid = $picture[pictureid]
									AND
								albumid = $picture[albumid]
						");

						$this->db->query_write("
							INSERT IGNORE INTO " . TABLE_PREFIX . "picturelegacy
								(type, primaryid, pictureid, attachmentid)
							VALUES
								('album', $picture[albumid], $picture[pictureid], $attachmentid)
						");

						$this->db->query_write("
							UPDATE " . TABLE_PREFIX . "picturecomment
							SET
								filedataid = " . $attachdm->fetch_field('filedataid') . ",
								userid = $picture[userid]
							WHERE
								pictureid = $picture[pictureid]
						");

						$this->db->query_write("
							UPDATE " . TABLE_PREFIX . "album
							SET coverattachmentid = $attachmentid
							WHERE
								coverattachmentid = $picture[pictureid]
									AND
								albumid = $picture[albumid]
						");

						$oldvalue = "$picture[albumid],$picture[userid]";
						$newvalue = "$picture[albumid],$attachmentid]";
						$this->db->query_write("
							UPDATE " . TABLE_PREFIX . "usercss
							SET
								value = '" . $this->db->escape_string($newvalue) . "',
								converted = 1
							WHERE
								property = 'background_image'
									AND
								value = '" . $this->db->escape_string($oldvalue) . "'
									AND
								userid = $picture[userid]
									AND
								converted = 0
						");
						if ($this->db->affected_rows())
						{
							$users["$picture[userid]"] = 1;
						}
					}
					else
					{
						if (is_array($attachdm->errors))
						{
							foreach($attachdm->errors AS $error)
							{
								$message = array_shift($error);
								echo sprintf($this->phrase['vbphrase'][$message], $this->registry->options['attachpath']);
							}
						}
						else
						{
							echo $this->phrase['core']['unexpected_error'];
						}

						exit;
					}
				}

				return array('startat' => $lastid);
			}
			else
			{
				$this->show_message($this->phrase['version']['400a1']['update_albums_complete']);
			}
		}
		else
		{
			$this->show_message($this->phrase['version']['400a1']['update_albums_complete']);
		}
	}

	/**
	* Step #90 - Convert Social Groups
	*
	* @param	int	id to startat processing at
	*
	* @return	mixed	Startat value for next go round
	*/
	public function step_90($data = [])
	{
		require_once(DIR . '/install/legacy/400a1/includes.php');
		vB_Upgrade::createAdminSession();
		$startat = intval($data['startat'] ?? 0);
		$perpage = 25;
		$db_alter = new vB_Database_Alter_MySQL($this->db);
		if ($db_alter->fetch_table_info('albumpicture'))
		{
			$pictures = $this->db->query_read("
				SELECT
					sgp.groupid, sgp.dateline,
					picture.*
				FROM " . TABLE_PREFIX . "socialgrouppicture AS sgp
				INNER JOIN " . TABLE_PREFIX . "picture AS picture ON (sgp.pictureid = picture.pictureid)
				WHERE
					sgp.pictureid > $startat
						AND
					sgp.attachmentid = 0
				ORDER BY sgp.pictureid ASC
				" . ($this->limitqueries ? "LIMIT 0, $perpage" : "") . "
			");
			if ($this->db->num_rows($pictures))
			{
				$lastid = 0;
				while ($picture = $this->db->fetch_array($pictures))
				{
					$this->show_message(sprintf($this->phrase['version']['400a1']['convert_picture'], $picture['pictureid']), 1);
					$lastid = $picture['pictureid'];

					if ($this->registry->options['album_dataloc'] == 'db')
					{
						$thumbnail =& $picture['thumbnail'];
						$filedata =& $picture['filedata'];
					}
					else
					{
						$attachpath = $this->registry->options['album_picpath'] . '/' . floor($picture['pictureid'] / 1000) . "/$picture[pictureid].picture";
						if ($this->registry->options['album_dataloc'] == 'fs_directthumb')
						{
							$attachthumbpath = $this->registry->options['album_thumbpath'] . '/' . floor($picture['pictureid'] / 1000);
						}
						else
						{
							$attachthumbpath = $this->registry->options['album_picpath'] . '/' . floor($picture['pictureid'] / 1000);
						}
						$attachthumbpath .= "/$picture[idhash]_$picture[pictureid].$picture[extension]";

						$thumbnail = @file_get_contents($attachthumbpath);
						$filedata = @file_get_contents($attachpath);

						if ($filedata === false)
						{
							$this->show_message(sprintf($this->phrase['version']['400a1']['could_not_find_file'], $attachpath));
							continue;
						}
					}

					$attachdm = new vB_DataManager_AttachmentFiledata(vB_DataManager_Constants::ERRTYPE_CP);
					$attachdm->set('contenttypeid', 7);
					$attachdm->set('contentid', $picture['groupid']);
					$attachdm->set('filename', $picture['pictureid'] . '.' . $picture['extension']);
					$attachdm->set('width', $picture['width']);
					$attachdm->set('height', $picture['height']);
					$attachdm->set('state', $picture['state']);
					$attachdm->set('reportthreadid', $picture['reportthreadid']);
					$attachdm->set('userid', $picture['userid']);
					$attachdm->set('caption', $picture['caption']);
					$attachdm->set('dateline', $picture['dateline']);
					$attachdm->set('thumbnail_dateline', $picture['thumbnail_dateline']);
					$attachdm->setr('filedata', $filedata);
					$attachdm->setr('thumbnail', $thumbnail);
					if ($attachmentid = $attachdm->save())
					{
						$this->db->query_write("
							UPDATE " . TABLE_PREFIX . "socialgrouppicture
							SET
								attachmentid = $attachmentid
							WHERE
								pictureid = $picture[pictureid]
									AND
								groupid = $picture[groupid]
						");

						$this->db->query_write("
							INSERT IGNORE INTO " . TABLE_PREFIX . "picturelegacy
								(type, primaryid, pictureid, attachmentid)
							VALUES
								('group', $picture[groupid], $picture[pictureid], $attachmentid)
						");
					}
					else
					{
						//will print errors and die.
						$attachdm->has_errors(true);
					}
				}
				return array('startat' => $lastid);
			}
			else
			{
				$this->show_message($this->phrase['version']['400a1']['update_groups_complete']);
			}
		}
		else
		{
			$this->show_message($this->phrase['version']['400a1']['update_groups_complete']);
		}
	}

	public function step_91()
	{
		if (!empty($this->registry->bf_misc_moderatorpermissions2))
		{
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'moderator', 1, 1),

				"UPDATE " . TABLE_PREFIX . "moderator SET
					permissions2 = permissions2 |
						IF(permissions2 & " . $this->registry->bf_misc_moderatorpermissions2['caneditalbumpicture'] . ", " . intval($this->registry->bf_misc_moderatorpermissions2['caneditgrouppicture']) . ", 0) |
						IF(permissions2 & " . $this->registry->bf_misc_moderatorpermissions2['candeletealbumpicture'] . ", " . intval($this->registry->bf_misc_moderatorpermissions2['candeletegrouppicture']) . ", 0) |
						IF(permissions2 & " . $this->registry->bf_misc_moderatorpermissions2['canmoderatepictures'] . ", " . intval($this->registry->bf_misc_moderatorpermissions2['canmoderategrouppicture']) . ", 0)
				"
			);

		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_92()
	{
		if (!empty($this->registry->bf_ugp_socialgrouppermissions) AND !empty( $this->registry->bf_ugp_albumpermissions)
			AND !empty( $this->registry->bf_ugp_albumpermissions['canalbum']) AND !empty( $this->registry->bf_ugp_albumpermissions['picturefollowforummoderation']))
		{
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'usergroup', 1, 1),
				"UPDATE " . TABLE_PREFIX . "usergroup SET
					socialgrouppermissions = socialgrouppermissions |
						IF(albumpermissions & " . $this->registry->bf_ugp_albumpermissions['canalbum'] . ", " . intval($this->registry->bf_ugp_socialgrouppermissions['canupload']) . ", 0) |
						IF(albumpermissions & " . $this->registry->bf_ugp_albumpermissions['picturefollowforummoderation'] . ", " . intval($this->registry->bf_ugp_socialgrouppermissions['groupfollowforummoderation']) . ", 0)
				"
			);

		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_93()
	{
		$this->run_query(
			sprintf($this->phrase['core']['dropping_old_table_x'], TABLE_PREFIX . "albumpicture"),
			"DROP TABLE IF EXISTS " . TABLE_PREFIX . "albumpicture"
		);
	}

	public function step_94()
	{
		$this->run_query(
			sprintf($this->phrase['core']['dropping_old_table_x'], TABLE_PREFIX . "socialgrouppicture"),
			"DROP TABLE IF EXISTS " . TABLE_PREFIX . "socialgrouppicture"
		);
	}

	public function step_95()
	{
		$this->run_query(
			sprintf($this->phrase['core']['dropping_old_table_x'], TABLE_PREFIX . "picture"),
			"DROP TABLE IF EXISTS " . TABLE_PREFIX . "picture"
		);
	}

	public function step_96()
	{
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], 'picturecomment', 1, 6),
			'picturecomment',
			'pictureid'
		);
	}

	public function step_97()
	{
		$this->drop_index(
			sprintf($this->phrase['core']['altering_x_table'], 'picturecomment', 2, 6),
			'picturecomment',
			'pictureid'
		);
	}

	public function step_98()
	{
		$this->drop_index(
			sprintf($this->phrase['core']['altering_x_table'], 'picturecomment', 3, 6),
			'picturecomment',
			'postuserid'
		);
	}

	public function step_99()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'picturecomment', 4, 6),
			'picturecomment',
			'filedataid',
			array('filedataid', 'userid', 'dateline', 'state')
		);
	}

	public function step_100()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'filedata', 5, 6),
			'picturecomment',
			'postuserid',
			array('postuserid', 'filedataid', 'userid', 'state')
		);
	}

	public function step_101()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'filedata', 6, 6),
			'picturecomment',
			'userid',
			array('userid')
		);
	}

	public function step_102()
	{
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'attachment'));

		require_once(DIR . '/includes/adminfunctions.php');
		build_attachment_permissions();

		// Kill duplicate files in the filedata table
		$files = $this->db->query_read("
			SELECT count(*) AS count, filehash, filesize
			FROM " . TABLE_PREFIX . "filedata
			GROUP BY filehash, filesize
			HAVING count > 1
		");
		while ($file = $this->db->fetch_array($files))
		{
			$refcount = 0;
			$filedataid = 0;
			$killfiles = array();
			$files2 = $this->db->query("
				SELECT
					filedataid, refcount, userid
				FROM " . TABLE_PREFIX . "filedata
				WHERE
					filehash = '$file[filehash]'
						AND
					filesize = $file[filesize]
			");
			while ($file2 = $this->db->fetch_array($files2))
			{
				$refcount += $file2['refcount'];
				if (!$filedataid)
				{
					$filedataid = $file2['filedataid'];
				}
				else
				{
					$killfiles[$file2['filedataid']] = $file2['userid'];
				}
			}

			$this->db->query_write("UPDATE " . TABLE_PREFIX . "filedata SET refcount = $refcount WHERE filedataid = $filedataid");
			$this->db->query_write("UPDATE " . TABLE_PREFIX . "attachment SET filedataid = $filedataid WHERE filedataid IN (" . implode(",", array_keys($killfiles)) . ")");
			$this->db->query_write("DELETE FROM " . TABLE_PREFIX . "filedata WHERE filedataid IN (" . implode(",", array_keys($killfiles)) . ")");
			foreach ($killfiles AS $filedataid => $userid)
			{
				// 2 == ATTACH_AS_FILES_NEW . Skipping include & just hard-coding it to reduce depency in legacy upgrades.
				// This is defined in a few places, such as core/includes/functions_file.php, vB_Image, vB_Library_Content_Attach::uploadAttachment()
				// and also replicated with vB_Library_Filedata::ATTACH_AS_FILES_NEW
				if ($this->registry->GPC['attachtype'] == 2)
				{
					$path = $this->registry->options['attachpath'] . '/' . implode('/', preg_split('//', $userid,  -1, PREG_SPLIT_NO_EMPTY));
				}
				else
				{
					$path = $this->registry->options['attachpath'] . '/' . $userid;
				}
				@unlink($path . '/' . $filedataid . '.attach');
				@unlink($path . '/' . $filedataid . '.thumb');
			}
		}
	}

	public function step_103()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'setting'),
			"UPDATE " . TABLE_PREFIX . "setting
			SET value = 'ssl'
			WHERE varname = 'smtp_tls' AND value = '1'"
		);

		$this->long_next_step();
	}

	public function step_104()
	{
		$this->skip_message(); // Not required for vB5 update
	}

	public function step_105()
	{
		$this->skip_message(); // Not required for vB5 update
	}

	public function step_106()
	{
		$this->skip_message(); // Not required for vB5 update
	}

	public function step_107()
	{
		$this->skip_message(); // Not required for vB5 update
	}

	public function step_108()
	{
		$this->skip_message(); // Not required for vB5 update
	}

	public function step_109()
	{
		$this->skip_message(); // Not required for vB5 update

	}

	/**
	* Step #110  From 3.8.6 Step 1
	*
	*/
	public function step_110()
	{
		$canignorequotaperm = intval($this->registry->bf_ugp_pmpermissions['canignorequota']);

		if ($canignorequotaperm)
		{
			// Update Admins and Super Mods to have the "canignorequota" perm in case they don't have it...
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'usergroup', 1, 1),
				"UPDATE " . TABLE_PREFIX . "usergroup
				SET pmpermissions = pmpermissions + $canignorequotaperm
				WHERE usergroupid IN (5,6) AND NOT (pmpermissions & $canignorequotaperm)"
			);
		}
		else
		{
			$this->skip_message();
		}
	}
}

class vB_Upgrade_400a2 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'contenttype', 1, 1),
			'contenttype',
			'isaggregator',
			'enum',
			array('attributes' => "('0', '1')", 'null' => false, 'default' => '0')
		);
	}
}

class vB_Upgrade_400a3 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'attachment', 1, 1),
			'attachment',
			'settings',
			'mediumtext',
			self::FIELD_DEFAULTS
		);
	}
}

class vB_Upgrade_400a4 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'bbcode_video'),
			"CREATE TABLE " . TABLE_PREFIX . "bbcode_video (
			  providerid INT UNSIGNED NOT NULL AUTO_INCREMENT,
			  tagoption VARCHAR(50) NOT NULL DEFAULT '',
			  provider VARCHAR(50) NOT NULL DEFAULT '',
			  url VARCHAR(100) NOT NULL DEFAULT '',
			  regex_url VARCHAR(254) NOT NULL DEFAULT '',
			  regex_scrape VARCHAR(254) NOT NULL DEFAULT '',
			  embed MEDIUMTEXT,
			  priority INT UNSIGNED NOT NULL DEFAULT '0',
			  PRIMARY KEY  (providerid),
			  UNIQUE KEY tagoption (tagoption),
			  KEY priority (priority),
			  KEY provider (provider)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}
}

class vB_Upgrade_400a5 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'picturelegacy'),
			"CREATE TABLE " . TABLE_PREFIX . "picturelegacy (
				type ENUM('album', 'group') NOT NULL DEFAULT 'album',
				primaryid INT UNSIGNED NOT NULL DEFAULT '0',
				pictureid INT UNSIGNED NOT NULL DEFAULT '0',
				attachmentid INT UNSIGNED NOT NULL DEFAULT '0',
				PRIMARY KEY (type, primaryid, pictureid)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_2()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'style', 1, 1),
			'style',
			'dateline',
			'int',
			self::FIELD_DEFAULTS
		);
	}
}

class vB_Upgrade_400b1 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->drop_index(
			sprintf($this->phrase['core']['altering_x_table'], 'attachment', 1, 2),
			'attachment',
			'contenttypeid'
		);
	}

	public function step_2()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'attachment', 2, 2),
			'attachment',
			'contenttypeid',
			array('contenttypeid', 'contentid', 'attachmentid')
		);
	}

	public function step_3()
	{
		$row = $this->db->query_first("
			SELECT COUNT(*) AS count FROM " . TABLE_PREFIX . "notice WHERE title = 'default_guest_message'
		");

		if ($row['count'] == 0)
		{
			$this->show_message('Adding a notice');

			$data = array(
				'title' => 'default_guest_message',
				'text' => $this->phrase['install']['default_guest_message'],
				'displayorder' => 10,
				'active' => 1,
				'persistent' => 1,
				'dismissible' => 1,
				'criteria' => array('in_usergroup_x' => array('condition1' => 1)),
			);
			vB_Library::instance('notice')->save($data);
		}
		else
		{
			$this->skip_message();
		}
	}
}

class vB_Upgrade_400b3 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->show_message($this->phrase['core']['updating_bbcode']);
		require_once(DIR . '/includes/functions_databuild.php');
		//a user must be logged in to compile bbcode template
		vB_Upgrade::createAdminSession();
		build_bbcode_video();
	}

	/**
	* Step #2 - retire existing styles
	*
	*/
	public function step_2()
	{
		$this->run_query(
			$this->phrase['version']['400b3']['updating_styles'],
			"UPDATE " . TABLE_PREFIX . "style
			SET userselect = 0,
				displayorder = 65432,
			    title =
			    	IF(title LIKE '%" . $this->db->escape_string_like($this->phrase['version']['400b3']['incompatible']) . "',
			    	title,
			    	CONCAT(title, '" . $this->db->escape_string($this->phrase['version']['400b3']['incompatible']) . "'))
		");
	}

	/**
	* Step #3 - disassociate styles with forums
	*
	*/
	public function step_3()
	{
		$this->run_query(
			$this->phrase['version']['400b3']['updating_forum_styles'],
			"UPDATE " . TABLE_PREFIX . "forum
			SET styleid = 0
		");
	}

	/**
	* Step #4 - clear user style preferences
	*
	*/
	public function step_4()
	{
		$this->run_query(
			$this->phrase['version']['400b3']['updating_user_styles'],
			"UPDATE " . TABLE_PREFIX . "user
			SET styleid = 0
		");
	}

	/**
	* Step #5 - clear blog style
	*
	*/
	public function step_5()
	{
		$this->run_query(
			$this->phrase['version']['400b3']['updating_blog_styles'],
			"UPDATE " . TABLE_PREFIX . "setting
			SET value = '0'
			WHERE varname = 'vbblog_style'
		");
	}

	/**
	* Step #6 - Create new style
	*/
	public function step_6()
	{
		$this->skip_message(); // Not required for vB5 update
	}
}

class vB_Upgrade_400b4 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . "ad"),
			"CREATE TABLE " . TABLE_PREFIX . "ad (
				adid INT UNSIGNED NOT NULL auto_increment,
				title VARCHAR(250) NOT NULL DEFAULT '',
				adlocation VARCHAR(250) NOT NULL DEFAULT '',
				displayorder INT UNSIGNED NOT NULL DEFAULT '0',
				active SMALLINT UNSIGNED NOT NULL DEFAULT '0',
				snippet MEDIUMTEXT,
				PRIMARY KEY (adid),
				KEY active (active)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_2()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . "adcriteria"),
			"CREATE TABLE " . TABLE_PREFIX . "adcriteria (
				adid INT UNSIGNED NOT NULL DEFAULT '0',
				criteriaid VARCHAR(191) NOT NULL DEFAULT '',
				condition1 VARCHAR(250) NOT NULL DEFAULT '',
				condition2 VARCHAR(250) NOT NULL DEFAULT '',
				condition3 VARCHAR(250) NOT NULL DEFAULT '',
				PRIMARY KEY (adid,criteriaid)
			)
			",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_3()
	{
		if (!$this->field_exists('language', 'phrasegroup_advertising'))
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . "advertising"),
				"ALTER TABLE " . TABLE_PREFIX . "language ADD phrasegroup_advertising mediumtext not null"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_4()
	{
		if (!$this->db->query_first("SELECT * FROM " . TABLE_PREFIX . "phrasetype WHERE fieldname = 'advertising'"))
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . "phrasetype"),
				"INSERT INTO " . TABLE_PREFIX . "phrasetype
				VALUES
					('advertising', 'Advertising', 3, '', 0)
				"
			);
		}
		else
		{
			$this->skip_message();
		}
	}
}

class vB_Upgrade_400b5 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'search_text', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "searchcore_text MODIFY title VARCHAR(254) NOT NULL DEFAULT ''"
		);
	}

	public function step_2()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'searchgroup_text', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "searchgroup_text MODIFY title VARCHAR(254) NOT NULL DEFAULT ''"
		);
	}
}

class vB_Upgrade_400rc1 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'block'),
			"CREATE TABLE " . TABLE_PREFIX . "block (
				blockid INT UNSIGNED NOT NULL AUTO_INCREMENT,
				blocktypeid INT NOT NULL DEFAULT '0',
				title VARCHAR(255) NOT NULL DEFAULT '',
				description MEDIUMTEXT,
				url VARCHAR(100) NOT NULL DEFAULT '',
				cachettl INT NOT NULL DEFAULT '0',
				displayorder SMALLINT NOT NULL DEFAULT '0',
				active SMALLINT NOT NULL DEFAULT '0',
				configcache MEDIUMBLOB,
				PRIMARY KEY (blockid),
				KEY blocktypeid (blocktypeid)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_2()
	{
		$this->skip_message();
	}

	public function step_3()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'blocktype'),
			"CREATE TABLE " . TABLE_PREFIX . "blocktype (
				blocktypeid INT UNSIGNED NOT NULL AUTO_INCREMENT,
				productid VARCHAR(25) NOT NULL DEFAULT '',
				name VARCHAR(50) NOT NULL DEFAULT '',
				title VARCHAR(255) NOT NULL DEFAULT '',
				description MEDIUMTEXT,
				allowcache TINYINT NOT NULL DEFAULT '0',
				PRIMARY KEY (blocktypeid),
				UNIQUE KEY (name),
				KEY productid (productid)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	/**
	* Step #4 - New phrase types
	*
	*/
	public function step_4()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'language', 1, 1),
			'language',
			'phrasegroup_vbblock',
			'mediumtext',
			self::FIELD_DEFAULTS
		);
	}

	public function step_5()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'language', 1, 1),
			'language',
			'phrasegroup_vbblocksettings',
			'mediumtext',
			self::FIELD_DEFAULTS
		);
	}

	public function step_6()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . "phrasetype"),
			"INSERT IGNORE INTO " . TABLE_PREFIX . "phrasetype
				(title, editrows, fieldname, special)
			VALUES
				('{$this->phrase['phrasetype']['vbblock']}', 3, 'vbblock', 0),
				('{$this->phrase['phrasetype']['vbblocksettings']}', 3, 'vbblocksettings', 0)
			"
		);
	}
}

class vB_Upgrade_400rc2 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->add_index(
			sprintf($this->phrase['core']['create_index_x_on_y'], 'user_activity', TABLE_PREFIX . 'session'),
			'session',
			'user_activity',
			array('userid', 'lastactivity')
		);
	}

	public function step_2()
	{
		$this->add_index(
			sprintf($this->phrase['core']['create_index_x_on_y'], 'guest_lookup', TABLE_PREFIX . 'session'),
			'session',
			'guest_lookup',
			array('idhash', 'host', 'userid')
		);
	}

	public function step_3()
	{
		$this->add_index(
			sprintf($this->phrase['core']['create_index_x_on_y'], 'styleid', TABLE_PREFIX . 'template'),
			'template',
			'styleid',
			array('styleid')
		);
	}

	public function step_4()
	{
		$profile_field_category_locations = array(
			'profile_left_first'  => 'profile_tabs_first',
			'profile_left_last'   => 'profile_tabs_last',
			'profile_right_first' => 'profile_sidebar_first',
			'profile_right_mini'  => 'profile_sidebar_stats',
			'profile_right_album' => 'profile_sidebar_albums',
			'profile_right_last'  => 'profile_sidebar_last',
		);

		foreach ($profile_field_category_locations AS $old_category_location => $new_category_location)
		{
			$this->run_query(
				$this->phrase['version']['400rc2']['updating_profile_field_category_data'],
				"UPDATE " . TABLE_PREFIX . "profilefieldcategory
					SET location = '$new_category_location'
					WHERE location = '$old_category_location'"
			);
		}
	}
}

class vB_Upgrade_400rc4 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'postparsed', 1, 1),
			"TRUNCATE TABLE " . TABLE_PREFIX . "postparsed"
		);
	}

	public function step_2()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'template', 1, 1),
			"DELETE FROM " . TABLE_PREFIX . "template WHERE title = 'bbcode_video' AND templateid = 0"
		);
	}
}

class vB_Upgrade_402 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$doads = [
			'thread_first_post_content' => 1,
			'thread_last_post_content'  => 1,
		];

		$ads = $this->db->query_read("
			SELECT adlocation, COUNT( * ) AS count
			FROM " . TABLE_PREFIX . "ad
			WHERE
				adlocation IN ('" . implode('\', \'', array_keys($doads)) . "')
					AND
				active = 1
			GROUP BY
				adlocation
		");
		while ($ad = $this->db->fetch_array($ads))
		{
			unset($doads[$ad['adlocation']]);
		}

		$templateLib = vB_Library::instance('template');
		$count = 0;
		foreach (array_keys($doads) AS $ad)
		{
			$count++;
			$template_un = '';
			$template = $templateLib->compile($template_un, 'full', false);
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'template', $count, count($doads)),
				"UPDATE " . TABLE_PREFIX . "template
				SET
					template = '" . $this->db->escape_string($template) . "',
					template_un = '',
					dateline = " . TIMENOW . "
				WHERE
					styleid IN (-1,0)
						AND
					title = 'ad_" . $this->db->escape_string($ad) . "'
				"
			);
		}
		if (!$count)
		{
			$this->skip_message();
		}
	}

	public function step_2()
	{
		$this->skip_message();
	}

	/**
	* Step #3 - change the standard icons to the new png images.
	*
	*/
	public function step_3()
	{
		for ($i = 1; $i < 15; $i++)
		{
			$this->run_query(
				sprintf($this->phrase['version']['402']['update_icon'], $i, 14),
				"UPDATE " . TABLE_PREFIX . "icon SET iconpath = 'images/icons/icon$i.png'
				WHERE iconpath = 'images/icons/icon$i.gif' AND imagecategoryid = 2"
			);
		}

		require_once(DIR . '/includes/adminfunctions.php');
		build_image_cache('icon');
	}
}

class vB_Upgrade_403 extends vB_Upgrade_Version
{
	/**
	* Step #1 - give all admins tags perms if they have thread perms
	*
	*/
	public function step_1()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'administrator'),
			"UPDATE " . TABLE_PREFIX . "administrator SET
				adminpermissions = adminpermissions | " . $this->registry->bf_ugp_adminpermissions['canadmintags'] . "
			WHERE
				adminpermissions & " . $this->registry->bf_ugp_adminpermissions['canadminthreads']
		);
	}

	public function step_2()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'attachment', 1, 1),
			'attachment',
			'displayorder',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_3()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'attachment'),
			"UPDATE " . TABLE_PREFIX . "attachment SET displayorder = attachmentid
		");
	}

	/**
	* Step #4 - correctly store master style template history records
	*
	*/
	public function step_4()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'templatehistory', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "templatehistory CHANGE styleid styleid SMALLINT NOT NULL DEFAULT '0'"
		);
	}

	public function step_5()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'templatehistory'),
			"UPDATE " . TABLE_PREFIX . "templatehistory SET styleid = -1 WHERE styleid = 0"
		);
	}

	/**
	* Step #6 - Make sure there's a cron job to do queued cache updates
	*
	*/
	public function step_6()
	{
		$this->add_cronjob(
			array(
				'varname'  => 'queueprocessor',
				'nextrun'  => 1232082000,
				'weekday'  => -1,
				'day'      => -1,
				'hour'     => -1,
				'minute'   => 'a:6:{i:0;i:0;i:1;i:10;i:2;i:20;i:3;i:30;i:4;i:40;i:5;i:50;}',
				'filename' => './includes/cron/queueprocessor.php',
				'loglevel' => 1,
				'volatile' => 1,
				'product'  => 'vbulletin'
			)
		);
	}

	public function step_7()
	{
		$this->skip_message(); // Not required for vB5 update
	}

	/**
	* Step #8  From 3.8.5 Step 1
	*
	*/
	public function step_8()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'bookmarksite', 1, 1),
			'bookmarksite',
			'utf8encode',
			'smallint',
			self::FIELD_DEFAULTS
		);
	}

	/**
	* Step #9 - widen attachment filenames
	*
	*/
	public function step_9()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'attachment', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "attachment CHANGE filename filename VARCHAR(255) NOT NULL DEFAULT ''"
		);
	}

	public function step_10()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'attachmentcategoryuser', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "attachmentcategoryuser CHANGE filename filename VARCHAR(255) NOT NULL DEFAULT ''"
		);
	}

	/**
	* Step #11 - widen the user salt  From 3.8.5 Step 1
	*
	*/
	public function step_11()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'user', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "user MODIFY salt CHAR(30) NOT NULL DEFAULT ''"
		);
	}

	/**
	* Step #12 - rebuild ads that use the is_date criterion #36100 or browsing forum criteria #34416
	*/
	public function step_12()
	{
		$this->show_message($this->phrase['version']['403']['rebuilding_ad_criteria']);

		$ad_result = $this->db->query_read("
			SELECT ad.*
			FROM " . TABLE_PREFIX . "ad AS ad
			LEFT JOIN " . TABLE_PREFIX . "adcriteria AS adcriteria ON(adcriteria.adid = ad.adid)
			WHERE adcriteria.criteriaid IN('is_date', 'browsing_forum_x', 'browsing_forum_x_and_children')
		");
		if ($this->db->num_rows($ad_result) > 0)
		{
			$ad_cache = array();
			$ad_locations = array();

			while ($ad = $this->db->fetch_array($ad_result))
			{
				$ad_cache["$ad[adid]"] = $ad;
				$ad_locations[] = $ad['adlocation'];
			}

			require_once(DIR . '/includes/functions_ad.php');

			$templateLib = vB_Library::instance('template');
			foreach($ad_locations AS $location)
			{
				$template_un = wrap_ad_template(build_ad_template($location), $location);
				$template = $templateLib->compile($template_un, 'full', false);

				// Failed compile check was added by VBIV-10653 //
				if (template === false)
				{
					$this->show_message(
						sprintf($this->phrase['vbphrase']['compile_template_x_failed'], 'ad_' . $location)
					);
				}
				else
				{
					$this->run_query(
						sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'templatehistory'),
						"
							UPDATE " . TABLE_PREFIX . "template SET
								template = '" . $this->db->escape_string($template) . "',
								template_un = '" . $this->db->escape_string($template_un) . "',
								dateline = " . TIMENOW . ",
								username = '" . $this->db->escape_string($this->registry->userinfo['username']) . "'
							WHERE
								title = 'ad_" . $this->db->escape_string($location) . "'
								AND styleid IN (-1,0)
						"
					);
				}
			}

			build_all_styles();
		}
	}

	/**
	* Step #13 - add the facebook userid to the user table
	*
	*/
	public function step_13()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'user', 1, 4),
			'user',
			'fbuserid',
			'VARCHAR',
			array(
				'length' => 255
			)
		);
	}

	/**
	* Step #14 - add index to facebook userid
	*
	*/
	public function step_14()
	{
		$this->add_index(
			sprintf($this->phrase['core']['create_index_x_on_y'], 'fbuserid', TABLE_PREFIX . 'user'),
			'user',
			'fbuserid',
			array('fbuserid')
		);
	}

	/**
	* Step #15 - add facebook join date to the user table
	*
	*/
	public function step_15()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'user', 2, 4),
			'user',
			'fbjoindate',
			'INT',
			self::FIELD_DEFAULTS
		);
	}

	/**
	* Step #16 - add the facebook name to the user table
	*
	*/
	public function step_16()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'user', 3, 4),
			'user',
			'fbname',
			'VARCHAR',
			array(
				'length' => 255
			)
		);
	}

	public function step_17()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'user', 4, 4),
			'user',
			'logintype',
			'enum',
			array('attributes' => "('vb', 'fb')", 'null' => false, 'default' => 'vb')
		);
	}
}

class vB_Upgrade_404 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'userchangelog', 1, 1),
			'userchangelog',
			'ipaddress',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	/**
	* Step #2 - remove orphaned stylevars
	*
	*/
	public function step_2()
	{
		$this->show_message($this->phrase['version']['404']['checking_orphaned_stylevars']);

		$skipstyleids = '-1';
		$style_result = $this->db->query_read("SELECT styleid FROM " . TABLE_PREFIX . "style");
		while ($style_row = $this->db->fetch_array($style_result))
		{
			$skipstyleids .= ',' . intval($style_row['styleid']);
		}
		$this->db->query_write("DELETE FROM " . TABLE_PREFIX . "stylevar WHERE styleid NOT IN($skipstyleids)");

		$orphaned_stylevar_count = $this->db->affected_rows();
		if ($orphaned_stylevar_count > 0)
		{
			$this->show_message(sprintf($this->phrase['version']['404']['removed_x_orphaned_stylevars'], $orphaned_stylevar_count));
		}
		else
		{
			$this->show_message($this->phrase['version']['404']['no_orphaned_stylevars']);
		}
	}

	public function step_3()
	{
		$smilies_to_change = array(
			'smile', 'redface', 'biggrin', 'wink', 'tongue', 'cool',
			'rolleyes', 'mad', 'eek', 'confused', 'frown'
		);

		//change the standard icons to the new png images.
		$i = 0;
		foreach ($smilies_to_change as $smilie)
		{
			$i++;
			$this->run_query(
				sprintf($this->phrase['version']['404']['update_smilie'], $i, count($smilies_to_change)),
				"UPDATE " . TABLE_PREFIX . "smilie SET smiliepath = 'images/smilies/$smilie.png'
				WHERE smiliepath = 'images/smilies/$smilie.gif' AND imagecategoryid = 1"
			);
		}
	}

	public function step_4()
	{
		require_once(DIR . '/includes/adminfunctions.php');
		build_image_cache('smilie');

		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], 'usergroup', 1, 1),
			'usergroup',
			'albumpicmaxsize'
		);
	}

	public function step_5()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'usertitle', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "usertitle CHANGE usertitleid usertitleid INT UNSIGNED NOT NULL AUTO_INCREMENT"
		);
	}

	public function step_6()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'album', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "album CHANGE description description MEDIUMTEXT"
		);
	}

	/**
	* Step #7 - The default on this field is not relevant since this value is determined at user creation but let's match what mysql-schema has
	*
	*/
	public function step_7()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'user', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "user CHANGE options options INT UNSIGNED NOT NULL DEFAULT '167788559'"
		);
	}

	public function step_8()
	{
		$this->drop_index(
			sprintf($this->phrase['core']['altering_x_table'], 'contenttype', 1, 4),
			'contenttype',
			'package'
		);
	}

	public function step_9()
	{
		$this->drop_index(
			sprintf($this->phrase['core']['altering_x_table'], 'contenttype', 2, 4),
			'contenttype',
			'packageclass'
		);
	}

	public function step_10()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'contenttype', 3, 4),
			'contenttype',
			'packageclass',
			array('packageid', 'class'),
			'unique'
		);
	}

	public function step_11()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'contenttype', 4, 4),
				"ALTER TABLE " . TABLE_PREFIX . "contenttype ENGINE={$this->hightrafficengine}"
		);
	}

	public function step_12()
	{
		$this->drop_index(
			sprintf($this->phrase['core']['altering_x_table'], 'prefixpermission', 1, 3),
			'prefixpermission',
			'prefixsetid'
		);
	}

	public function step_13()
	{
		$this->drop_index(
			sprintf($this->phrase['core']['altering_x_table'], 'prefixpermission', 2, 3),
			'prefixpermission',
			'prefixusergroup'
		);
	}

	public function step_14()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'prefixpermission', 3, 3),
			'prefixpermission',
			'prefixsetid',
			array('prefixid', 'usergroupid')
		);
	}

	public function step_15()
	{
		$this->drop_index(
			sprintf($this->phrase['core']['altering_x_table'], 'groupmessage', 1, 2),
			'groupmessage',
			'postuserid'
		);
	}

	public function step_16()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'groupmessage', 2, 2),
			'groupmessage',
			'postuserid',
			array('postuserid', 'discussionid', 'state')
		);
	}

	public function step_17()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'editlog', 1, 1),
				"ALTER TABLE " . TABLE_PREFIX . "editlog CHANGE hashistory hashistory SMALLINT UNSIGNED NOT NULL DEFAULT '0'"
		);
	}

	public function step_18()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'profilevisitor', 1, 1),
				"ALTER TABLE " . TABLE_PREFIX . "profilevisitor CHANGE visible visible SMALLINT UNSIGNED NOT NULL DEFAULT '1'"
		);
	}

	public function step_19()
	{
		$this->drop_index(
			sprintf($this->phrase['core']['altering_x_table'], 'groupmessage', 1, 1),
			'groupmessage',
			'gm_ft'
		);

		$this->long_next_step();
	}

	public function step_20()
	{
		$this->drop_index(
			sprintf($this->phrase['core']['altering_x_table'], 'socialgroup', 1, 1),
			'socialgroup',
			'name'
		);

		$this->long_next_step();
	}

	public function step_21()
	{
		$this->skip_message();
	}

	/**
	 * Step #22 - Set viewattachedimages = 3 when we had thumbnails disabled and view full images enabled
	 */
	public function step_22()
	{
		//the attach thumbs options has been removed, but probably still exists if we're hitting this
		//point in the upgrade from vB4.  If we're rerunning this step after upgrading to vB5
		//(and I can't think of any scenario where doing that with a step this old would be a good idea)
		//this just skip this update.  It's not clear what we'd want to do and the admin can always
		//fix the config manually in this case.
		if (
			isset($this->registry->options['attachthumbs']) AND
			!$this->registry->options['attachthumbs'] AND
			$this->registry->options['viewattachedimages'] == 1
		)
		{
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'setting', 1, 1),
					"UPDATE " . TABLE_PREFIX . "setting SET value = 3 WHERE varname = 'viewattachedimages'"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	* Step #23 - add the facebook name to the user table
	*
	*/
	public function step_23()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'user', 1, 2),
			'user',
			'fbaccesstoken',
			'VARCHAR',
			array(
				'length' => 255
			)
		);
	}

	/**
	* Step #24 - add the facebook profilepic to the user table
	*
	*/
	public function step_24()
	{
		//this field was removed in 407.  Not much point in adding it here.
		$this->skip_message();
	}

	/*
	* Step #25 Removed, its not required for upgrading to vB5
	*/
}

class vB_Upgrade_405 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'template', 1, 1),
			"UPDATE " . TABLE_PREFIX . "template SET version = '4.0.4' WHERE version = '4.04'"
		);
	}

	public function step_2()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'phrase', 1, 1),
			"UPDATE " . TABLE_PREFIX . "phrase SET version = '4.0.4' WHERE version = '4.04'"
		);
	}
}

class vB_Upgrade_406 extends vB_Upgrade_Version
{
	public function step_1()
	{
		// fix imgdir_gradients stylevar in non-MASTER styles VBIV-8052
		$stylevar_result = $this->db->query_read("
			SELECT *
			FROM " . TABLE_PREFIX . "stylevar
			WHERE stylevarid = 'imgdir_gradients'
		");

		$stylevars = array();

		while ($stylevar = $this->db->fetch_array($stylevar_result))
		{
			if ($stylevar['styleid'] == -1)
			{
				continue;
			}

			$value = unserialize($stylevar['value']);
			if (key($value) == 'string')
			{
				$stylevars[] = $stylevar;
			}
		}

		$total = count($stylevars);

		if ($total > 0)
		{
			$i = 1;
			foreach ($stylevars AS $stylevar)
			{
				$value = unserialize($stylevar['value']);
				$new_value = array('imagedir' => $value['string']);

				$this->run_query(
					sprintf($this->phrase['version']['406']['updating_stylevars_in_styleid_x_y_of_z'], $stylevar['styleid'], $i, $total),
					"UPDATE " . TABLE_PREFIX . "stylevar
					SET value = '" . $this->db->escape_string(serialize($new_value)) . "'
					WHERE
						stylevarid = 'imgdir_gradients'
							AND
						styleid = " . intval($stylevar['styleid']) . "
				");
				$i++;
			}
		}
		else
		{
			$this->skip_message();
		}
	}
}

class vB_Upgrade_407 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->add_index(
			sprintf($this->phrase['core']['create_index_x_on_y'], 'active', TABLE_PREFIX . 'product'),
			'product',
			'active',
			array('active')
		);
		// fbprofilepicurl is obsolete VBIV-7592
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], 'user', 1, 1),
			'user',
			'fbprofilepicurl'
		);
	}
}

class vB_Upgrade_408 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'customprofile') ,
			"CREATE TABLE " . TABLE_PREFIX . "customprofile (
			customprofileid integer AUTO_INCREMENT,
			title VARCHAR(100),
			thumbnail VARCHAR(255),
			userid INT NOT NULL,
			themeid INT,
			font_family VARCHAR(255),
			fontsize VARCHAR(20),
			title_text_color VARCHAR(20),
			page_background_color VARCHAR(20),
			page_background_image VARCHAR(255),
			page_background_repeat  VARCHAR(20),
			module_text_color VARCHAR(20),
			module_link_color VARCHAR(20),
			module_background_color VARCHAR(20),
			module_background_image VARCHAR(255),
			module_background_repeat VARCHAR(20),
			module_border VARCHAR(20),
			content_text_color VARCHAR(20),
			content_link_color VARCHAR(20),
			content_background_color VARCHAR(20),
			content_background_image VARCHAR(255),
			content_background_repeat VARCHAR(20),
			content_border VARCHAR(20),
			button_text_color VARCHAR(20),
			button_background_color VARCHAR(20),
			button_background_image VARCHAR(255),
			button_background_repeat VARCHAR(20),
			button_border VARCHAR(20),
			moduleinactive_text_color varchar(20),
			moduleinactive_link_color varchar(20),
			moduleinactive_background_color varchar(20),
			moduleinactive_background_image varchar(255),
			moduleinactive_background_repeat varchar(20),
			moduleinactive_border varchar(20),
			headers_text_color varchar(20),
			headers_link_color varchar(20),
			headers_background_color varchar(20),
			headers_background_image varchar(255),
			headers_background_repeat varchar(20),
			headers_border varchar(20),
			page_link_color varchar(20),
			PRIMARY KEY  (customprofileid),
			KEY(userid)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_2()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'dbquery'),
			"CREATE TABLE " . TABLE_PREFIX . "dbquery (	dbqueryid varchar(32) NOT NULL,
				querytype enum('u','d', 'i', 's') NOT NULL,
				query_string text,
				PRIMARY KEY (dbqueryid)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_3()
	{
		$this->run_query(
		sprintf($this->phrase['version']['408']['granting_profile_customization_permission']),
		"UPDATE " . TABLE_PREFIX . "usergroup set usercsspermissions  = (usercsspermissions | 192) where usercsspermissions > 0;"
		);
	}

	public function step_4()
	{
		$items = array(
			'text' => array(
				'font_family'      => 'font_family',
			),
			'main' => array(
				'background_color' => 'page_background_color',
				'background_image' => 'page_background_image',
				'background_repeat'=> 'page_background_repeat',
			),
			'tableheader' => array(
				'color'            => 'module_text_color',
				'background_color' => 'module_background_color',
				'background_image' => 'module_background_image',
				'background_repeat'=> 'module_background_repeat',
			),
			'alternating' => array(
				'color'            => 'content_text_color',
				'linkcolor'        => 'content_link_color',
				'background_color' => 'content_background_color',
				'background_image' => 'content_background_image',
				'background_repeat'=> 'content_background_repeat',
			),
			'inputs'      => array(
				'color'            => 'button_text_color',
				'background_color' => 'button_background_color',
				'background_image' => 'button_background_image',
				'background_repeat'=> 'button_background_repeat',
				'border_color'     => 'button_border',
			)
		);

		$total = 1;
		foreach ($items AS $selector => $properties)
		{
			foreach ($properties AS $property => $pr)
			{
				$total++;
			}
		}

		$this->run_query(
		sprintf($this->phrase['version']['408']['converting_3x_customization'], 1, $total),
			"INSERT into " . TABLE_PREFIX . "customprofile(userid)
			SELECT distinct u.userid FROM " . TABLE_PREFIX . "user u INNER JOIN " . TABLE_PREFIX . "usercss css
			ON css.userid = u.userid
			LEFT JOIN " . TABLE_PREFIX . "customprofile cp ON cp.userid = u.userid
			WHERE cp.customprofileid IS NULL;"
		);

		$count = 2;
		foreach ($items AS $selector => $properties)
		{
			foreach ($properties AS $property => $pr)
			{
				$this->run_query(
				sprintf($this->phrase['version']['408']['converting_3x_customization'], $count, $total),
				"UPDATE " . TABLE_PREFIX . "customprofile pr
					INNER JOIN " . TABLE_PREFIX . "usercss c ON c.userid = pr.userid AND c.selector = '$selector' AND property = '$property'
					SET pr.$pr = c.value;"
				);
				$count++;
			}
		}

		$this->long_next_step();
	}

	public function step_5()
	{
		$this->skip_message(); // Not required for vB5 update
//		$this->run_query(
//			sprintf($this->phrase['core']['altering_x_table'], 'post', 1, 1),
//			"ALTER TABLE " . TABLE_PREFIX . "post DROP INDEX userid, ADD INDEX userid (userid, parentid)",
//			self::MYSQL_ERROR_KEY_EXISTS
//		);
	}

	public function step_6()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . "profilefield"),
			"UPDATE " . TABLE_PREFIX . "profilefield
			SET
				maxlength=16384, size=50, type='textarea'
			WHERE
				profilefieldid = 1
		");
	}

	public function step_7()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['update_table'], 'postparsed'),
			"TRUNCATE TABLE " . TABLE_PREFIX . "postparsed"
		);
	}

	public function step_8()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['update_table'], 'sigparsed'),
			"TRUNCATE TABLE " . TABLE_PREFIX . "sigparsed"
		);
	}

	public function step_9()
	{
		if ($this->field_exists('blog_textparsed', 'blogtextid')) // table exists
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['update_table'], 'blog_textparsed'),
				"TRUNCATE TABLE " . TABLE_PREFIX . "blog_textparsed"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	* Step #10 - Update default post icon since gif version is removed in 4.0.8
	*
	*/
	public function step_10()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . "setting"),
			"UPDATE " . TABLE_PREFIX . "setting
			SET value = REPLACE(value, 'images/icons/icon1.gif', 'images/icons/icon1.png')
			WHERE varname = 'showdeficon'
		");
	}

	/**
	* Step #11 - Change the gif smilies to the new png images
	*
	*/
	public function step_11()
	{
		$smilies_to_change = array (
			'smile', 'redface', 'biggrin', 'wink', 'tongue', 'cool',
			'rolleyes', 'mad', 'eek', 'confused', 'frown'
		);
		$i = 0;
		foreach ($smilies_to_change as $smilie)
		{
			$i++;
			$this->run_query(
				sprintf($this->phrase['vbphrase']['update_table_x'], 'smilie', $i, count($smilies_to_change)),
				"UPDATE " . TABLE_PREFIX . "smilie
				SET smiliepath = REPLACE(smiliepath, 'images/smilies/$smilie.gif', 'images/smilies/$smilie.png')
				WHERE imagecategoryid = 1 AND
					smiliepath LIKE '%images/smilies/$smilie.gif'"
			);
		}
	}

	/**
	* Step #12 - Change the gif post icons to the new png images
	*
	*/
	public function step_12()
	{
		for ($i = 1; $i < 15; ++$i)
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['update_table_x'], 'icon', $i, 15),
				"UPDATE " . TABLE_PREFIX . "icon
				SET iconpath = REPLACE(iconpath, 'images/icons/icon$i.gif', 'images/icons/icon$i.png')
				WHERE imagecategoryid = 2 AND
					iconpath LIKE '%images/icons/icon$i.gif'"
			);
		}
	}

	public function step_13()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'product', 1, 1),
			"UPDATE " . TABLE_PREFIX . "product SET versioncheckurl = '', url= '' WHERE productid = 'vbblog'"
		);
	}

	public function step_14()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['update_table'], 'searchgroup_text'),
			"DELETE " . TABLE_PREFIX . "searchgroup_text
			FROM " . TABLE_PREFIX . "searchgroup_text
			LEFT JOIN " . TABLE_PREFIX . "searchgroup ON (" . TABLE_PREFIX . "searchgroup.searchgroupid = " . TABLE_PREFIX . "searchgroup_text.searchgroupid)
			WHERE " . TABLE_PREFIX . "searchgroup.searchgroupid IS NULL"
		);
	}

	public function step_15()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['update_table'], 'searchcore_text'),
			"DELETE " . TABLE_PREFIX . "searchcore_text
			FROM " . TABLE_PREFIX . "searchcore_text
			LEFT JOIN " . TABLE_PREFIX . "searchcore ON (" . TABLE_PREFIX . "searchcore.searchcoreid = " . TABLE_PREFIX . "searchcore_text.searchcoreid)
			WHERE " . TABLE_PREFIX . "searchcore.searchcoreid IS NULL"
		);

		// We have changed smilies and post icons
		require_once(DIR . '/includes/adminfunctions.php');
		build_image_cache('smilie');
		build_image_cache('icon');
	}

	public function step_16()
	{
		$stylevarbase = implode(',', [
			"'forummenu_background'",
			"'body_background'",
			"'forummenu_border'",
			"'forummenu_color'",
			"'forummenu_font'",
			"'albumtop_list_a_color'",
			"'albumtop_list_a_hover_color'",
			"'forum_sidebar_background'",
			"'forum_sidebar_border'",
			"'forum_sidebar_content_background'",
			"'forum_sidebar_content_border'",
			"'forum_sidebar_content_color'",
			"'mid_fontSize'",
			"'forum_sidebar_contentavatar_width'",
			"'forum_sidebar_content_separator_background'",
			"'forum_sidebar_content_separator_height'",
			"'usercp_forum_icon_legend_top_border'",
			"'forum_sidebar_header_color'",
			"'forum_sidebar_header_font'",
			"'wgo_background'",
			"'wgo_border'",
			"'body_color'",
			"'wgo_fontSize'",
			"'wgoheader_background'",
			"'wgoheader_border'",
			"'wgoheader_color'",
			"'wgoheader_font'",
			"'wgosubheader_font'",
		]);

		$this->show_message($this->phrase['version']['408']['retrieving_customized_stylevar_values']);
		$style_result = $this->db->query_read("
			SELECT stylevarid, styleid, value
			FROM " . TABLE_PREFIX . "stylevar
			WHERE
				stylevarid IN (" . $stylevarbase . ")
				AND
				styleid > 0
		");
		$stylevar = [];
		while ($style_row = $this->db->fetch_array($style_result))
		{
			if (!isset($stylevar[$style_row['stylevarid']]))
			{
				$stylevar[$style_row['stylevarid']] = [];
			}
			$stylevar[$style_row['stylevarid']][$style_row['styleid']] = $style_row['value'];
		}

		$this->show_message($this->phrase['version']['408']['mapping_customized_stylevars']);
		$stylevarmap = [
			['new' => 'toolsmenu_background',      'old' => 'forummenu_background', 'part' => ''],
			['new' => 'toolsmenu_bevel', 			     'old' => 'body_background', 				'part' => 'color'],
			['new' => 'toolsmenu_border', 			   'old' => 'forummenu_border', 				'part' => ''],
			['new' => 'toolsmenu_color', 			     'old' => 'forummenu_color', 				'part' => ''],
			['new' => 'toolsmenu_fontSize', 			 'old' => 'forummenu_font', 				'part' => 'units,size'],
			['new' => 'toolsmenu_link_color',      'old' => 'albumtop_list_a_color', 			'part' => ''],
			['new' => 'toolsmenu_linkhover_color', 'old' => 'albumtop_list_a_hover_color', 		'part' => ''],
			['new' => 'sidebar_background',        'old' => 'forum_sidebar_background', 			'part' => ''],
			['new' => 'sidebar_border',            'old' => 'fourm_sidebar_border', 			'part' => ''],
			['new' => 'sidebar_content_background', 		'old' => 'forum_sidebar_content_background', 		'part' => ''],
			['new' => 'sidebar_content_border', 		'old' => 'forum_sidebar_content_border',		'part' => ''],
			['new' => 'sidebar_content_bevel', 		'old' => 'forum_sidebar_content_background', 		'part' => 'color'],
			['new' => 'sidebar_content_color',			'old' => 'forum_sidebar_content_color', 		'part' => ''],
			['new' => 'sidebar_content_fontSize',		'old' => 'mid_fontSize', 				'part' => ''],
			['new' => 'sidebar_contentavatar_width',		'old' => 'forum_sidebar_contentavatar_width', 		'part' => ''],
			['new' => 'sidebar_contentseparator_background',	'old' => 'forum_sidebar_content_separator_background',	'part' => ''],
			['new' => 'sidebar_contentseparator_height', 	'old' => 'forum_sidebar_content_separator_height', 	'part' => ''],
			['new' => 'sidebar_contentlist_separator', 	'old' => 'usercp_forum_icon_legend_top_border',		'part' => ''],
			['new' => 'sidebar_header_color',			'old' => 'forum_sidebar_header_color',			'part' => ''],
			['new' => 'sidebar_header_fontSize',		'old' => 'forum_sidebar_header_font',			'part' => 'units,size'],
			['new' => 'secondarycontent_background',		'old' => 'wgo_background',				'part' => ''],
			['new' => 'secondarycontent_border',		'old' => 'wgo_border',					'part' => ''],
			['new' => 'secondarycontent_color',		'old' => 'body_color',					'part' => ''],
			['new' => 'secondarycontent_fontSize', 		'old' => 'wgo_fontSize',				'part' => ''],
			['new' => 'secondarycontent_header_background',	'old' => 'wgoheader_background', 			'part' => ''],
			['new' => 'secondarycontent_header_border',	'old' => 'wgoheader_border',				'part' => ''],
			['new' => 'secondarycontent_header_color',		'old' => 'wgoheader_color',				'part' => ''],
			['new' => 'secondarycontent_header_fontSize', 	'old' => 'wgoheader_font',				'part' => ''],
			['new' => 'secondarycontent_subheader_fontSize',	'old' => 'wgosubheader_font', 				'part' => 'units,size'],
		];

		// check for existing target stylevars so we know whether to insert or update
		$target_stylevars = [];
		foreach ($stylevarmap AS $map)
		{
			$target_stylevars[] = $this->db->escape_string($map['new']);
		}
		$existing_result = $this->db->query_read("
			SELECT stylevarid
			FROM " . TABLE_PREFIX . "stylevar
			WHERE
				stylevarid IN ('" . implode("', '", $target_stylevars) . "')
				AND
				styleid > 0
		");
		$existing_target_stylevars = [];
		while($row = $this->db->fetch_array($existing_result))
		{
			$existing_target_stylevars[$row['stylevarid']] = true;
		}

		// map the stylevars
		foreach ($stylevarmap AS $map)
		{
			//if the old stylevar doesn't exist, skip.  We may not have them yet in which case we don't
			//have any customized settings to worry about.
			if (!is_array($stylevar[$map['old']] ?? null))
			{
				continue; // source stylevar hasn't been customized
			}

			foreach ($stylevar[$map['old']] AS $styleid => $source_stylevar)
			{
				if (!empty($map['part']))
				{
					$values = [];
					$parts = [];
					$parts = explode(',', $map['part']);
					$v = unserialize($source_stylevar);
					for ($i = 0; $i < count($parts); $i++)
					{
						$values[$parts[$i]] = $v[$parts[$i]];
					}
					$newvalue = serialize($values);
				}
				else
				{
					$newvalue = $source_stylevar;
				}

				// we never want to UPDATE, as that would overwrite stylevar customizations
				if (!isset($existing_target_stylevars[$map['new']]))
				{
					$this->run_query(
						sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'stylevar'),
						"INSERT INTO " . TABLE_PREFIX . "stylevar
						(stylevarid, styleid, value)
						VALUES(
							'" . $this->db->escape_string($map['new']) . "',
							" . intval($styleid) . ",
							'" . $this->db->escape_string($newvalue) . "'
						)
					");
				}
			}
		}
	}
}

class vB_Upgrade_410 extends vB_Upgrade_Version
{
	/* VBV-5679
	This has been taken from the vB 4.1.0 Blog Upgrader.
	The field htmlstate needs to exist for a later step and
	because this upgrade step only exists in the blog product
	on vB4, it gets missed by the standard upgrader. */

	public function step_1($data = array())
	{
		if ($this->tableExists('blog_text'))
		{
			$this->add_field(
				sprintf($this->phrase['core']['altering_x_table'], 'blog_text', 1, 1),
				'blog_text',
				'htmlstate',
				'enum',
				array('attributes' => "('off', 'on', 'on_nl2br')", 'null' => false, 'default' => 'on_nl2br')
			);
		}
		else
		{
			$this->skip_message();
		}
	}
}

class vB_Upgrade_410b1 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'apiclient'),
			"CREATE TABLE " . TABLE_PREFIX . "apiclient (
				apiclientid INT UNSIGNED NOT NULL auto_increment,
				secret VARCHAR(32) NOT NULL DEFAULT '',
				apiaccesstoken VARCHAR(32) NOT NULL DEFAULT '',
				userid INT UNSIGNED NOT NULL DEFAULT '0',
				clienthash VARCHAR(32) NOT NULL DEFAULT '',
				clientname VARCHAR(250) NOT NULL DEFAULT '',
				clientversion VARCHAR(50) NOT NULL DEFAULT '',
				platformname VARCHAR(250) NOT NULL DEFAULT '',
				platformversion VARCHAR(50) NOT NULL DEFAULT '',
				uniqueid VARCHAR(250) NOT NULL DEFAULT '',
				initialipaddress VARCHAR(15) NOT NULL DEFAULT '',
				dateline INT UNSIGNED NOT NULL,
				lastactivity INT UNSIGNED NOT NULL,
				PRIMARY KEY  (apiclientid),
				KEY clienthash (uniqueid)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);

	}

	public function step_2()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'session', 1, 3),
			'session',
			'apiclientid',
			'INT',
			self::FIELD_DEFAULTS
		);
	}

	public function step_3()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'session', 2, 3),
			'session',
			'apiaccesstoken',
			'VARCHAR',
			array('length' => 32, 'attributes' => self::FIELD_DEFAULTS)
		);
	}

	public function step_4()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'session', 3, 3),
			'session',
			'apiaccesstoken',
			'apiaccesstoken'
		);
	}

	public function step_5()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'apilog'),
			"CREATE TABLE " . TABLE_PREFIX . "apilog (
				apilogid INT UNSIGNED NOT NULL auto_increment,
				apiclientid INT UNSIGNED NOT NULL DEFAULT '0',
				dateline INT UNSIGNED NOT NULL DEFAULT '0',
				method VARCHAR(32) NOT NULL DEFAULT '',
				paramget MEDIUMTEXT,
				parampost MEDIUMTEXT,
				ipaddress VARCHAR(15) NOT NULL DEFAULT '',
				PRIMARY KEY  (apilogid),
				KEY apiclientid (apiclientid, method, dateline)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}
}

class vB_Upgrade_4110a3 extends vB_Upgrade_Version
{
	/** In general, upgrade files between 4.1.5 and 500a1 are likely to be different in vB5 from their equivalent in vB4.
	 *  Since large portions of vB4 code were removed in vB5, the upgrades to ensure that code works is unnecessary. If
	 *  there are actual errors that affect vB5, those must be included of course. If there are changes whose absence would
	 *  break a later step, those are required.
	 *
	 * But since these files will only be used to upgrade to versions after 5.0.0 alpha 1, most of the upgrade steps can be
	 * omitted. We could use skip_message(), but that takes up a redirect and, in the cli upgrade, a recursion. We would rather
	 * avoid those. So we have removed those steps,
	 * step 1 in the original is not needed because it deals with stylevar mapping, which is a later vB4 concept which doesn't exist in vB5
	 * steps 2 and 4 in the original are not needed because permissions in vB5 are done differently and come from a different table
	 * step 3 in the original is not needed because this field is not present and not used in vB5
	 *
	 * Which leaves steps 1 and 5
	 */

	/*
	 * VBIV-5472 : Convert old encoded filenames
	 */
	public function step_1($data = []) //was step 5
	{
		$process = 1000;
		$startat = intval($data['startat'] ?? 0);

		if ($startat == 0)
		{
			$attachments = $this->db->query_first_slave("
				SELECT COUNT(*) AS attachments
				FROM " . TABLE_PREFIX . "attachment
			");

			$total = $attachments['attachments'];

			if ($total)
			{
				$this->show_message(sprintf($this->phrase['version']['4110a3']['processing_filenames'],$total));
				return array('startat' => 1);
			}
			else
			{
				$this->skip_message();
				return;
			}
		}
		else
		{
			$first = $startat - 1;
		}

		$attachments = $this->db->query_read_slave("
			SELECT filename, attachmentid
			FROM " . TABLE_PREFIX . "attachment
			LIMIT $first, $process
		");

		$rows = $this->db->num_rows($attachments);

		if ($rows)
		{
			while ($attachment = $this->db->fetch_array($attachments))
			{
				$aid = $attachment['attachmentid'];
				$filename = $attachment['filename'];
				$newfilename = $this->db->escape_string(html_entity_decode($filename, ENT_QUOTES));

				if ($filename != $newfilename)
				{
					$this->db->query_write("
						UPDATE " . TABLE_PREFIX . "attachment
						SET filename = '$newfilename'
						WHERE attachmentid = $aid
					");
				}
			}

			$this->db->free_result($attachments);
			$this->show_message(sprintf($this->phrase['version']['4110a3']['updated_attachments'],$first + $rows));

			return array('startat' => $startat + $process);
		}
		else
		{
			$this->show_message($this->phrase['version']['4110a3']['updated_attachments_complete']);
		}
	}
}

class vB_Upgrade_4111a1 extends vB_Upgrade_Version
{
	/**
	 * In general, upgrade files between 4.1.5 and 500a1 are likely to be different in vB5 from their equivalent in vB4.
	 * Since large portions of vB4 code were removed in vB5, the upgrades to ensure that code works is unnecessary. If
	 * there are actual errors that affect vB5, those must be included of course. If there are changes whose absence would
	 * break a later step, those are required.
	 *
	 * But since these files will only be used to upgrade to versions after 5.0.0 alpha 1, most of the upgrade steps can be
	 * omitted. We could use skip_message(), but that takes up a redirect and, in the cli upgrade, a recursion. We would rather
	 * avoid those. So we have removed those steps,
	 * steps 1 and 2 the original are bad. Since a new install or upgrade from an early vB5 alpha install would not have this,
	 *	we would have different data properties in the wild.
	 * Steps 4,5,6 are not needed because vB4 mobile styles are not used in vB5, and wouldn't work anyway.
	 *
	 * Not certain about Step 4, so let's leave that in.
	 */

	/*
	  Step 1 - Drop primary key on stylevardfn
	*/
	public function step_1()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'stylevardfn', 1, 2),
			"ALTER TABLE " . TABLE_PREFIX . "stylevardfn DROP PRIMARY KEY",
			self::MYSQL_ERROR_DROP_KEY_COLUMN_MISSING
		);
	}

	/*
	  Step 2 - Add primary key that allows stylevardfn per styleid
	*/
	public function step_2()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'stylevardfn', 2, 2),
			'stylevardfn',
			'PRIMARY',
			array('stylevarid', 'styleid'),
			'primary'
		);
	}

	/*
	  Step 3 - Make sure there is no -2 styles in style
	*/
	public function step_3()
	{
		if ($this->registry->db->query_first("SELECT styleid FROM " . TABLE_PREFIX . "style WHERE styleid = -2"))
		{
			$max = $this->registry->db->query_first("
				SELECT MAX(styleid) AS styleid FROM " . TABLE_PREFIX . "style
			");

			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'style', 1, 2),
				"UPDATE " . TABLE_PREFIX . "style SET
					styleid = " . ($max['styleid'] + 1) . ",
					parentlist = '" . ($max['styleid'] + 1) . ",-1'
				WHERE styleid = -2"
			);

			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'style', 2, 2),
				"ALTER TABLE  " . TABLE_PREFIX . "style CHANGE styleid styleid INT UNSIGNED NOT NULL AUTO_INCREMENT"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/*
	 * Step 2 - Updating the default mime type for bmp images.
	 */
	public function step_4() //Was step 7
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['update_table'], "attachmenttype"),
			"UPDATE " . TABLE_PREFIX . "attachmenttype
			SET mimetype = '" . $this->db->escape_string(serialize(array('Content-type: image/bmp'))) . "'
			WHERE extension = 'bmp'
		");
	}


}

class vB_Upgrade_4112a1 extends vB_Upgrade_Version
{
	/** In general, upgrade files between 4.1.5 and 500a1 are likely to be different in vB5 from their equivalent in vB4.
	 *  Since large portions of vB4 code were removed in vB5, the upgrades to ensure that code works is unnecessary. If
	 *  there are actual errors that affect vB5, those must be included of course. If there are changes whose absence would
	 *  break a later step, those are required.
	 *
	 * But since these files will only be used to upgrade to versions after 5.0.0 alpha 1, most of the upgrade steps can be
	 * omitted. We could use skip_message(), but that takes up a redirect and, in the cli upgrade, a recursion. We would rather
	 * avoid those. So we have removed those steps,
	 * step 2 is not needed because it creates an index on the post table, which we no longer use.
	 */

	/**
	* Adds index to the dateline field in tagsearch table for tag search improvment
	*
	*/
	public function step_1()
	{
		$this->add_index(
			sprintf($this->phrase['core']['create_index_x_on_y'], 'dateline', 'tagsearch'),
			'tagsearch',
			'dateline',
			'dateline'
		);

		$this->long_next_step();
	}

}

class vB_Upgrade_411b1 extends vB_Upgrade_Version
{
	public function step_1()
	{
		//delete any orphaned cms_article records. These are created by deleting articles from
		// the admincp content manager, fixed in this release.
		$contentinfo = $this->db->query_first("SELECT c.contenttypeid FROM " . TABLE_PREFIX .
		"contenttype c INNER JOIN " . TABLE_PREFIX . "package AS p ON p.packageid = c.packageid
		WHERE c.class='Article' AND p.productid = 'vbcms' ;");

		if ($contentinfo AND $contentinfo['contenttypeid'])
		{
			$this->run_query(
			$this->phrase['version']['411']['delete_orphan_articles'],
			"DELETE a FROM " . TABLE_PREFIX . "cms_article AS a LEFT JOIN " . TABLE_PREFIX .
			"cms_node AS n ON (n.contentid = a.contentid AND n.contenttypeid = " . $contentinfo['contenttypeid'] .")
			WHERE n.contentid IS NULL;");
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_2()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'cache', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "cache CHANGE data data MEDIUMTEXT"
		);
	}

	public function step_3()
	{
		//From 4.1.0 we could have a setting record with volatile = 0. That would be bad in finalupgrade.
			$this->run_query(
			sprintf($this->phrase['version']['411']['setting_volatile_flag'], 'socnet'),
			"UPDATE " . TABLE_PREFIX . "setting SET volatile=1 where varname='socnet';");

	}
 }

class vB_Upgrade_413b1 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->add_index(
			sprintf($this->phrase['core']['create_index_x_on_y'], 'email', TABLE_PREFIX . 'user'),
			'user',
			'email',
			array('email')
		);
	}
}

class vB_Upgrade_414b1 extends vB_Upgrade_Version
{
	/**
	* Step #1 - Add phrasegroup to language table
	*
	*/
	public function step_1()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'language', 1, 2),
			'language',
			'phrasegroup_ckeditor',
			'mediumtext',
			self::FIELD_DEFAULTS
		);
	}

	/**
	* Step #2 - Add phrasegroupinfo to language
	*
	*/
	public function step_2()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'language', 2, 2),
			'language',
			'phrasegroupinfo',
			'mediumtext',
			self::FIELD_DEFAULTS
		);
	}

	public function step_3()
	{
		$this->drop_index(
			sprintf($this->phrase['core']['altering_x_table'], 'phrase', 1, 2),
			'phrase',
			'languageid'
		);
	}

	public function step_4()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'phrase', 2, 2),
			'phrase',
			'languageid',
			array('languageid', 'fieldname', 'dateline')
		);
	}

	/**
	* Step #5 - Add phrasetype for CKEditor phrases
	*
	*/
	public function step_5()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . "phrasetype"),
			"INSERT IGNORE INTO " . TABLE_PREFIX . "phrasetype
				(title, editrows, fieldname, special)
			VALUES
				('" . $this->db->escape_string($this->phrase['phrasetype']['ckeditor']) . "', 3, 'ckeditor', 0)
			"
		);
	}

	/**
	* Step #6 - Add autosave table
	*
	*/
	public function step_6()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'autosave'),
			"CREATE TABLE " . TABLE_PREFIX . "autosave (
				contenttypeid VARBINARY(100) NOT NULL DEFAULT '',
				parentcontentid INT UNSIGNED NOT NULL DEFAULT '0',
				contentid INT UNSIGNED NOT NULL DEFAULT '0',
				userid INT UNSIGNED NOT NULL DEFAULT '0',
				pagetext MEDIUMTEXT,
				title MEDIUMTEXT,
				posthash CHAR(32) NOT NULL DEFAULT '',
				dateline INT UNSIGNED NOT NULL DEFAULT '0',
				PRIMARY KEY (contentid, parentcontentid, contenttypeid, userid),
				KEY userid (userid),
				KEY contenttypeid (contenttypeid, userid)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	/**
	* Step #7 - Add New Contenttypes
	*
	*/
	public function step_7()
	{
		$this->add_contenttype('vbulletin', 'vBForum', 'PrivateMessage');
	}

	/**
	* Step #8 - Add New Contenttypes
	*
	*/
	public function step_8()
	{
		$this->add_contenttype('vbulletin', 'vBForum', 'Infraction');
	}

	/**
	* Step #9 - Add New Contenttypes
	*
	*/
	public function step_9()
	{
		$this->add_contenttype('vbulletin', 'vBForum', 'Signature');
	}

	/**
	* Step #10 - Add New Contenttypes
	*
	*/
	public function step_10()
	{
		$this->add_contenttype('vbulletin', 'vBForum', 'UserNote');
	}

	public function step_11()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'session', 1, 1),
			'session',
			'isbot',
			'tinyint',
			self::FIELD_DEFAULTS
		);
	}
}

class vB_Upgrade_415b1 extends vB_Upgrade_Version
{
	/**
	* Step #1 - Add api post log table
	*
	*/
	public function step_1()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'apipost'),
			"CREATE TABLE " . TABLE_PREFIX . "apipost (
			  apipostid INT UNSIGNED NOT NULL AUTO_INCREMENT,
			  userid INT UNSIGNED NOT NULL DEFAULT '0',
			  contenttypeid INT UNSIGNED NOT NULL DEFAULT '0',
			  contentid INT UNSIGNED NOT NULL DEFAULT '0',
			  clientname VARCHAR(250) NOT NULL DEFAULT '',
			  clientversion VARCHAR(50) NOT NULL DEFAULT '',
			  platformname VARCHAR(250) NOT NULL DEFAULT '',
			  platformversion VARCHAR(50) NOT NULL DEFAULT '',
			  PRIMARY KEY (apipostid),
			  KEY contenttypeid (contenttypeid, contentid)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	/**
	* Step #2 - VBIV-7754, increase field size
	*
	*/
	public function step_2()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'searchlog', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "searchlog CHANGE criteria criteria MEDIUMTEXT NOT NULL"
		);
	}
}

class vB_Upgrade_417b1 extends vB_Upgrade_Version
{
	/** In general, upgrade files between 4.1.5 and 500a1 are likely to be different in vB5 from their equivalent in vB4.
	 *  Since large portions of vB4 code were removed in vB5, the upgrades to ensure that code works is unnecessary. If
	 *  there are actual errors that affect vB5, those must be included of course. If there are changes whose absence would
	 *  break a later step, those are required.
	 *
	 * But since these files will only be used to upgrade to versions after 5.0.0 alpha 1, most of the upgrade steps can be
	 * omitted.
	 */


	/** In general, upgrade files between 4.1.5 and 500a1 are likely to be different in vB5 from their equivalent in vB4.
	 *  Since large portions of vB4 code were removed in vB5, the upgrades to ensure that code works is unnecessary. If
	 *  there are actual errors that affect vB5, those must be included of course. If there are changes whose absence would
	 *  break a later step, those are required.
	 *
	 * But since these files will only be used to upgrade to versions after 5.0.0 alpha 1, most of the upgrade steps can be
	 * omitted. We could use skip_message(), but that takes up a redirect and, in the cli upgrade, a recursion. We would rather
	 * avoid those. So we have removed those steps,
	 * step 1- Since we no longer use the thread table there's no reason to make changes to it
	 */
	/*
	  Steps 1 & 2 - VBIV-10514 : Add last_activity index.
	*/
	public function step_1() //Was step 2
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'session', 1, 4),
			'ALTER TABLE ' . TABLE_PREFIX . 'session DROP INDEX last_activity',
			'1091'
		);
	}

	public function step_2() //Was step 3
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'session', 2, 4),
			'ALTER TABLE ' . TABLE_PREFIX . 'session ADD INDEX last_activity USING BTREE (lastactivity)',
			'1061'
		);
	}


	/*
	  Steps 3 & 4 - VBIV-10514 : Rebuild user_activity index as BTREE.
	*/
	public function step_3()  //Was step 4
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'session', 3, 4),
			'ALTER TABLE ' . TABLE_PREFIX . 'session DROP INDEX user_activity',
			'1091'
		);
	}

	public function step_4() //Was step 5
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'session', 4, 4),
			'ALTER TABLE ' . TABLE_PREFIX . 'session ADD INDEX user_activity USING BTREE (userid, lastactivity)',
			'1061'
		);
	}

	/*
	  Step 6 & 7 - VBIV-10525 : Correct clienthash index.
	*/
	public function step_5() //Was step 6
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'apiclient', 1, 2),
			'ALTER TABLE ' . TABLE_PREFIX . 'apiclient DROP INDEX clienthash',
			'1091'
		);
	}

	public function step_6() //Was step 7
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'apiclient', 2, 2),
			'ALTER TABLE ' . TABLE_PREFIX . 'apiclient ADD INDEX clienthash (clienthash)',
			'1061'
		);
	}
}

class vB_Upgrade_418b1 extends vB_Upgrade_Version
{
	/** In general, upgrade files between 4.1.5 and 500a1 are likely to be different in vB5 from their equivalent in vB4.
	 *  Since large portions of vB4 code were removed in vB5, the upgrades to ensure that code works is unnecessary. If
	 *  there are actual errors that affect vB5, those must be included of course. If there are changes whose absence would
	 *  break a later step, those are required.
	 *
	 * But since these files will only be used to upgrade to versions after 5.0.0 alpha 1, most of the upgrade steps can be
	 * omitted.
	 *
	 * Steps 3 & 4- not needed. Both were for the mobile style, which doesnt exist in vb5, these particular changes were superceeded as well.
	 * 	 *
	 */

	/*
	  Step 1 - VBIV-6641 : Add cache index for expires.
	*/
	public function step_1()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'cache', 1, 1),
			'cache',
			'expires',
			array('expires')
		);
	}

	/*
	  Step 2 - VBIV-6641 : Clean out expired events in cache and cacheevent tables.
	*/
	public function step_2()
	{
		$this->run_query(
			sprintf($this->phrase['core']['cache_update']),
				'DELETE cache, cacheevent FROM ' . TABLE_PREFIX . 'cache as cache
				INNER JOIN ' . TABLE_PREFIX . 'cacheevent as cacheevent USING (cacheid)
				WHERE expires BETWEEN 1 and ' . TIMENOW
		);
	}
}

class vB_Upgrade_420a1 extends vB_Upgrade_Version
{
	/**
	 * In general, upgrade files between 4.1.5 and 500a1 are likely to be different in vB5 from their equivalent in vB4.
	 * Since large portions of vB4 code were removed in vB5, the upgrades to ensure that code works is unnecessary. If
	 * there are actual errors that affect vB5, those must be included of course. If there are changes whose absence would
	 * break a later step, those are required.
	 *
	 * But since these files will only be used to upgrade to versions after 5.0.0 alpha 1, most of the upgrade steps can be
	 * omitted. We could use skip_message(), but that takes up a redirect and, in the cli upgrade, a recursion. We would rather
	 * avoid those. So we have removed those steps,
	 * step 1 in the original is not needed because we don't use the navigation table in vB5
	 * step 2- Not needed, this was part of the changes for sending mail by vb cron, this functionality wont currently exist in vb5
	 * Step 3- We don't use the user.newrepcount field in vB5.
	 * Step 4- we never query anything sorted by user.lastactivity or join on it.
	 * Step 5- we don't use the contentread table in vB5
	 * Step 6- we don't use the ipdate table in vB5
	 * Step 7- Not needed, all products are zapped by vB5 anyway.
	 * Step 8- We don't use the block table
	 * Step 9- Not needed, this was for double post prevention added in 4.2, it doesnt exist in vb5.
	 * Step 10- We don't use the forum table
	 * Step 12- We don't use the activitystreamtype table
	 *  Step 13, 14 - We don't use the activitystream table. We have a similar concept in vB5 but handled differently
	 *  Step 15, 16- Since we don't use activitystream we don't need the phrase group or type
	 *  Step 17, 18, 19- We don't use the picturecomment table. The hierarchy is handled completely differently in vB5
	 *  Step 20- We don't use the thread table
	 *  Step 21- We don't use activitystream approach
	 *  Step 22- this inserts a cron job to keep the activitystream up to date- but we don't use that approach.
	 *
	 * So we have some use for step 11
	 */

	/*
	  Step 1 - Add Index to Upgrade Log
	*/
	public function step_1() //Was Step 11
	{
		$this->add_index(
			sprintf($this->phrase['core']['create_index_x_on_y'], 'script', 'upgradelog'),
			'upgradelog',
			'script',
			'script'
		);
	}
}

class vB_Upgrade_421a1 extends vB_Upgrade_Version
{
	/** In general, upgrade files between 4.1.5 and 500a1 are likely to be different in vB5 from their equivalent in vB4.
	 *  Since large portions of vB4 code were removed in vB5, the upgrades to ensure that code works is unnecessary. If
	 *  there are actual errors that affect vB5, those must be included of course. If there are changes whose absence would
	 *  break a later step, those are required.
	 *
	 * But since these files will only be used to upgrade to versions after 5.0.0 alpha 1, most of the upgrade steps can be
	 * omitted. We could use skip_message(), but that takes up a redirect and, in the cli upgrade, a recursion. We would rather
	 * avoid those. So we have removed those steps,
	 * Step 1 in the original is not needed because we don't use the navigation table in vB5
	 * Step 2 we don't use 'ignored' as template mergestatus. We can add the step later in vB5 upgrade steps if we port this feature into vB5
	 * Step 6 in the original is not needed because we don't use the navigation table in vB5
	 *
	 * So we have some use for step 3, 4, 5 to keep update for old events even if we don't have event module in vB5 yet.
	 * We kept event table in vB5 so we may use its old data in future.
	 */

	/*
	 * Step 1 - Add field to track titles that have been converted
	 * this ensures that no field gets double encoded if the upgrade is executed multiple times
	 */
	public function step_1() // Was Step 3
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'event', 1, 1),
			'event',
			'title_encoded',
			'smallint',
			self::FIELD_DEFAULTS
		);
	}

	/*
	 * Step 2 - encode event titles
	 */
	public function step_2($data = []) // Was Step 4
	{
		$process = 1000;
		$startat = intval($data['startat'] ?? 0);

		if ($startat == 0)
		{
			$events = $this->db->query_first_slave("
				SELECT COUNT(*) AS events
				FROM " . TABLE_PREFIX . "event
				WHERE title_encoded = 0
			");

			$total = $events['events'];

			if ($total)
			{
				$this->show_message(sprintf($this->phrase['version']['421a1']['processing_event_titles'], $total));
				return array('startat' => 1);
			}
			else
			{
				$this->skip_message();
				return;
			}
		}
		else
		{
			$first = $startat - 1;
		}

		$events = $this->db->query_read_slave("
			SELECT title, eventid
			FROM " . TABLE_PREFIX . "event
			WHERE title_encoded = 0
			LIMIT $first, $process
		");

		$rows = $this->db->num_rows($events);

		if ($rows)
		{
			while ($event = $this->db->fetch_array($events))
			{
				$newtitle = htmlspecialchars_uni($event['title']);

				$this->db->query_write("
					UPDATE " . TABLE_PREFIX . "event
					SET
						title = '" . $this->db->escape_string($newtitle) . "',
						title_encoded = 1
					WHERE
						eventid = {$event['eventid']}
							AND
						title_encoded = 0
				");

			}

			$this->db->free_result($events);
			$this->show_message(sprintf($this->phrase['version']['421a1']['updated_event_titles'], $first + $rows));

			return array('startat' => $startat + $process);
		}
		else
		{
			$this->show_message($this->phrase['version']['421a1']['updated_event_titles_complete']);
		}
	}

	/*
	 * Step 3 - change default on title_encoded to 1 so any events added after this upgrade
	 * won't get double encoded if the upgrade is executed again
	 *
	 */
	public function step_3() // Was Step 5
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'event', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "event CHANGE title_encoded title_encoded SMALLINT NOT NULL DEFAULT '1'"
		);
	}
}

class vB_Upgrade_424b1 extends vB_Upgrade_Version
{
	/**
	* Check attachment refcounts and fix any that are broken
	*/
	public function step_1()
	{
		$sql = "
			UPDATE " . TABLE_PREFIX . "filedata
			LEFT JOIN (
				SELECT filedataid, COUNT(attachmentid) AS actual
				FROM " . TABLE_PREFIX . "attachment
				GROUP BY filedataid
			) list USING (filedataid)
			SET refcount = IFNULL(actual, 0)
			WHERE refcount <> IFNULL(actual, 0)
		";

		$res = $this->run_query(sprintf($this->phrase['vbphrase']['update_table_x'], 'filedata', 1, 1), $sql);
	}

	/*
	Step 2 Moved, it is now 5.2.3 Alpha 5, Step 2
	Step 3 Moved, it is now 5.2.3 Alpha 5, Step 3
	Step 4 Moved, it is now 5.2.3 Alpha 5, Step 4
	Step 5 Moved, it is now 5.2.3 Alpha 5, Step 5
	Step 6 Removed, it was an update to the Post Table, unused in vB5
	*/
}

class vB_Upgrade_424b3 extends vB_Upgrade_Version
{
	/**
	 * Change moderator id field from small int to int
	 *
	 * Step 1 Moved, it is now 5.2.3 Alpha 5, Step 1
	 * (Kept, but skipped, to avoid renumbering steps).
	 */
	public function step_1()
	{
		$this->skip_message();
	}

	/**
	 * Change [passwordhistory] passworddate field default for MySQL 5.7
	 */
	public function step_2()
	{
		if ($this->field_exists('passwordhistory', 'passworddate'))
		{
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'passwordhistory', 1, 2),
				"ALTER TABLE " . TABLE_PREFIX . "passwordhistory CHANGE COLUMN passworddate passworddate DATE NOT NULL DEFAULT '1000-01-01'"
			);

			// There shouldn't be any to change, but lets play safe.
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'passwordhistory', 2, 2),
				"UPDATE " . TABLE_PREFIX . "passwordhistory SET passworddate = '1000-01-01' WHERE passworddate = '0000-00-00'"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	 * Change [user] passworddate field default for MySQL 5.7
	 */
	public function step_3()
	{
		if ($this->field_exists('user', 'passworddate'))
		{
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'user', 1, 4),
				"ALTER TABLE " . TABLE_PREFIX . "user CHANGE COLUMN passworddate passworddate DATE NOT NULL DEFAULT '1000-01-01'"
			);

			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'user', 2, 4),
				"UPDATE " . TABLE_PREFIX . "user SET passworddate = '1000-01-01' WHERE passworddate = '0000-00-00'"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	 * Change [user] birthday_search field default for MySQL 5.7
	 */
	public function step_4()
	{
		if ($this->field_exists('user', 'birthday_search'))
		{
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'user', 3, 4),
				"ALTER TABLE " . TABLE_PREFIX . "user CHANGE COLUMN birthday_search birthday_search DATE NOT NULL DEFAULT '1000-01-01'"
			);

			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'user', 4, 4),
				"UPDATE " . TABLE_PREFIX . "user SET birthday_search = '1000-01-01' WHERE birthday_search = '0000-00-00'"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/*
	Step 5
	Delete old Panjo & Postrelease products.
	Not required as vB5 already deleted all the old products.
	*/
}

class vB_Upgrade_424rc3 extends vB_Upgrade_Version
{
	/*
	Update Read Marking Option
	This sets everyone to use DB marking as we removed the option in 4.2.5.
	I believe vB5 still has this option so left this in so upgraded sites will continue to
	use the option consistantly (vB5 really should remove the cookie based system as well).
	*/
	public function step_1()
	{
		$this->run_query(
			$this->phrase['version']['424rc3']['update_marking'],
			"UPDATE ".TABLE_PREFIX."setting SET value = '2' WHERE varname = 'threadmarking'"
		);
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 112201 $
|| #######################################################################
\*=========================================================================*/
