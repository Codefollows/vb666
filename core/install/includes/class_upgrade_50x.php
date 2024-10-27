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

class vB_Upgrade_500a1 extends vB_Upgrade_Version
{
	/**
	 * page table
	 */
	public function step_1()
	{
		if (!$this->tableExists('page'))
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'page'),
				"
				CREATE TABLE " . TABLE_PREFIX . "page (
				  pageid int(10) unsigned NOT NULL AUTO_INCREMENT,
				  parentid int(10) unsigned NOT NULL,
				  pagetemplateid int(10) unsigned NOT NULL,
				  title varchar(200) NOT NULL,
				  metadescription varchar(200) NOT NULL,
				  urlprefix varchar(200) NOT NULL,
				  routeid int(10) unsigned NOT NULL,
				  moderatorid int(10) unsigned NOT NULL,
				  displayorder int(11) NOT NULL,
				  pagetype enum('default','custom') NOT NULL DEFAULT 'custom',
				  guid char(150) DEFAULT NULL,
				  PRIMARY KEY (pageid)
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

	/**
	 * pagetemplate table
	 */
	public function step_2()
	{
		if (!$this->tableExists('pagetemplate'))
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'pagetemplate'),
				"
				CREATE TABLE " . TABLE_PREFIX . "pagetemplate (
				  pagetemplateid int(10) unsigned NOT NULL AUTO_INCREMENT,
				  title varchar(200) NOT NULL,
				  screenlayoutid int(10) unsigned NOT NULL,
				  content text NOT NULL,
				  guid char(150) DEFAULT NULL,
				  PRIMARY KEY (pagetemplateid)
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

	/**
	 * routenew table
	 */
	public function step_3()
	{
		if (!$this->tableExists('routenew'))
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'routenew'),
				"
				CREATE TABLE " . TABLE_PREFIX . "routenew (
				  routeid int(10) unsigned NOT NULL AUTO_INCREMENT,
				  name varchar(100) DEFAULT NULL,
				  redirect301 int(10) unsigned DEFAULT NULL,
				  prefix varchar(" . vB5_Route::PREFIX_MAXSIZE . ") NOT NULL,
				  regex varchar(" . vB5_Route::REGEX_MAXSIZE . ") NOT NULL,
				  class varchar(100) DEFAULT NULL,
				  controller varchar(100) NOT NULL,
				  action varchar(100) NOT NULL,
				  template varchar(100) NOT NULL,
				  arguments mediumtext NOT NULL,
				  contentid int(10) unsigned NOT NULL,
				  guid char(150) DEFAULT NULL,
				  PRIMARY KEY (routeid),
				  KEY regex (regex),
				  KEY prefix (prefix),
				  KEY route_name (name),
				  KEY route_class_cid (class, contentid)
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

	/**
	 * screenlayout table
	 */
	public function step_4()
	{
		if (!$this->tableExists('screenlayout'))
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'screenlayout'),
				"
				CREATE TABLE " . TABLE_PREFIX . "screenlayout (
				  screenlayoutid int(10) unsigned NOT NULL AUTO_INCREMENT,
				  varname varchar(20) NOT NULL,
				  title varchar(200) NOT NULL,
				  displayorder smallint(5) unsigned NOT NULL,
				  columncount tinyint(3) unsigned NOT NULL,
				  template varchar(200) NOT NULL,
				  admintemplate varchar(200) NOT NULL,
				  PRIMARY KEY (screenlayoutid)
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

	/**
	 * widget table
	 */
	public function step_5()
	{
		if (!$this->tableExists('widget'))
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'widget'),
				"
				CREATE TABLE " . TABLE_PREFIX . "widget (
				  widgetid int(10) unsigned NOT NULL AUTO_INCREMENT,
				  title varchar(200) NOT NULL,
				  template varchar(200) NOT NULL,
				  admintemplate varchar(200) NOT NULL,
				  icon varchar(200) NOT NULL,
				  isthirdparty tinyint(3) unsigned NOT NULL,
				  category varchar(100) NOT NULL DEFAULT 'uncategorized',
				  cloneable tinyint(3) unsigned NOT NULL DEFAULT '1',
				  canbemultiple tinyint(3) unsigned NOT NULL DEFAULT '1',
				  product VARCHAR(25) NOT NULL DEFAULT 'vbulletin',
				  guid char(150) DEFAULT NULL,
				  PRIMARY KEY (widgetid)
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

	/***	widgetdefinition*/
	public function step_6()
	{
		if (!$this->tableExists('widgetdefinition'))
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'widgetdefinition'),
				"
				CREATE TABLE " . TABLE_PREFIX . "widgetdefinition (
				  widgetid int(10) unsigned NOT NULL,
				  field varchar(50) NOT NULL,
				  name varchar(50) NOT NULL,
				  label varchar(200) NOT NULL,
				  defaultvalue blob NOT NULL,
				  isusereditable tinyint(4) NOT NULL DEFAULT '1',
				  ishiddeninput tinyint(4) NOT NULL DEFAULT '0',
				  isrequired tinyint(4) NOT NULL DEFAULT '0',
				  displayorder smallint(6) NOT NULL,
				  validationtype enum('force_datatype','regex','method') NOT NULL,
				  validationmethod varchar(200) NOT NULL,
				  product VARCHAR(25) NOT NULL DEFAULT 'vbulletin',
				  data text NOT NULL,
				  KEY (widgetid),
				  KEY product (product)
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

	/***	widgetinstance table*/
	public function step_7()
	{
		if (!$this->tableExists('widgetinstance'))
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'widgetinstance'),
				"
				CREATE TABLE " . TABLE_PREFIX . "widgetinstance (
				  widgetinstanceid int(10) unsigned NOT NULL AUTO_INCREMENT,
				  containerinstanceid int(10) unsigned NOT NULL DEFAULT '0',
				  pagetemplateid int(10) unsigned NOT NULL,
				  widgetid int(10) unsigned NOT NULL,
				  displaysection tinyint(3) unsigned NOT NULL,
				  displayorder smallint(5) unsigned NOT NULL,
				  adminconfig mediumtext CHARACTER SET utf8 NOT NULL,
				  PRIMARY KEY (widgetinstanceid),
				  KEY pagetemplateid (pagetemplateid,widgetid)
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

	/***	widgetuserconfig table*/
	public function step_8()
	{
		if (!$this->tableExists('widgetuserconfig'))
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'widgetuserconfig'),
				"
				CREATE TABLE " . TABLE_PREFIX . "widgetuserconfig (
				  widgetinstanceid int(10) unsigned NOT NULL,
				  userid int(10) unsigned NOT NULL,
				  userconfig blob NOT NULL,
				  UNIQUE KEY widgetinstanceid (widgetinstanceid,userid)
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

	/***	node table*/
	public function step_9()
	{
		if (!$this->tableExists('node'))
		{
			$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'node'),
			"
			CREATE TABLE " . TABLE_PREFIX . "node (
			nodeid INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
			routeid INT UNSIGNED NOT NULL DEFAULT 0,
			contenttypeid SMALLINT NOT NULL,
			publishdate INTEGER,
			unpublishdate INTEGER,
			userid INT UNSIGNED ,
			groupid INT UNSIGNED,
			authorname VARCHAR(100),
			description VARCHAR(1024),
			title VARCHAR(512),
			htmltitle VARCHAR(512),
			parentid INTEGER NOT NULL,
			urlident VARCHAR(512),
			displayorder SMALLINT,
			starter INT NOT NULL DEFAULT '0',
			created INT,
			lastcontent INT NOT NULL DEFAULT '0',
			lastcontentid INT NOT NULL DEFAULT '0',
			lastcontentauthor VARCHAR(100) NOT NULL DEFAULT '',
			lastauthorid INT UNSIGNED NOT NULL DEFAULT '0',
			lastprefixid VARCHAR(25) NOT NULL DEFAULT '',
			textcount mediumint UNSIGNED NOT NULL DEFAULT '0',
			textunpubcount mediumint UNSIGNED NOT NULL DEFAULT '0',
			totalcount mediumint UNSIGNED NOT NULL DEFAULT '0',
			totalunpubcount mediumint UNSIGNED NOT NULL DEFAULT '0',
			ipaddress CHAR(15) NOT NULL DEFAULT '',
			showpublished SMALLINT UNSIGNED NOT NULL DEFAULT '0',
			oldid INT UNSIGNED,
			oldcontenttypeid INT UNSIGNED,
			nextupdate INTEGER,
			lastupdate INTEGER,
			featured SMALLINT NOT NULL DEFAULT 0,
			CRC32 VARCHAR(10) NOT NULL DEFAULT '',
			taglist MEDIUMTEXT,
			inlist SMALLINT UNSIGNED NOT NULL DEFAULT '1',
			protected SMALLINT UNSIGNED NOT NULL DEFAULT '0',
			setfor INTEGER NOT NULL DEFAULT 0,
			votes SMALLINT(5) UNSIGNED NOT NULL DEFAULT '0',
			hasphoto SMALLINT NOT NULL DEFAULT '0',
			hasvideo SMALLINT NOT NULL DEFAULT '0',
			deleteuserid  INT UNSIGNED,
			deletereason VARCHAR(125),
			open SMALLINT NOT NULL DEFAULT '1',
			showopen SMALLINT NOT NULL DEFAULT '1',
			sticky TINYINT(1) NOT NULL DEFAULT '0',
			approved TINYINT(1) NOT NULL DEFAULT '1',
			showapproved TINYINT(1) NOT NULL DEFAULT '1',
			viewperms TINYINT NOT NULL DEFAULT 2,
			commentperms TINYINT NOT NULL DEFAULT 1,
			nodeoptions INT UNSIGNED NOT NULL DEFAULT 138,
			prefixid VARCHAR(25) NOT NULL DEFAULT '',
			iconid SMALLINT NOT NULL DEFAULT '0',
			public_preview SMALLINT NOT NULL DEFAULT '0',
			INDEX node_lastauthorid(lastauthorid),
			INDEX node_lastcontent(lastcontent),
			INDEX node_textcount(textcount),
			INDEX node_ip(ipaddress),
			INDEX node_pubdate(publishdate, nodeid),
			INDEX node_unpubdate(unpublishdate),
			INDEX node_parent(parentid),
			INDEX node_nextupdate(nextupdate),
			INDEX node_lastupdate(lastupdate),
			INDEX node_user(userid),
			INDEX node_oldinfo(oldcontenttypeid, oldid),
			INDEX node_urlident(urlident),
			INDEX node_sticky(sticky),
			INDEX node_starter(starter),
			INDEX node_approved(approved),
			INDEX node_ppreview(public_preview),
			INDEX node_showapproved(showapproved),
			INDEX node_ctypid_userid_dispo_idx(contenttypeid, userid, displayorder),
			INDEX node_setfor_pubdt_idx(setfor, publishdate),
			INDEX prefixid (prefixid, nodeid),
			INDEX nodeid (nodeid, contenttypeid)
			) ENGINE = " . $this->hightrafficengine . "
						",
			self::MYSQL_ERROR_TABLE_EXISTS
			);
		}
		else
		{
			// we need to reset any reference to deleted routes (page table is dropped, so only do it for nodes)
			/* Dont really see why we would need this, commented out for now, unless someone can explain the logic here.
			$this->run_query(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'node'),
				'UPDATE ' . TABLE_PREFIX . 'node SET routeid = 0');
			*/
			$this->skip_message();
		}
	}

	public function step_10()
	{
		$this->skip_message();
	}

	public function step_11()
	{
		$this->skip_message();
	}

	public function step_12()
	{
		/* See Step 36 */
		$this->skip_message();
	}

	/***	closure table*/
	public function step_13()
	{
		if (!$this->tableExists('closure'))
		{
			$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'closure'),
			"
			CREATE TABLE " . TABLE_PREFIX . "closure (
				parent INT UNSIGNED NOT NULL,
				child INT UNSIGNED NOT NULL,
				depth SMALLINT NULL,
				displayorder SMALLINT NOT NULL DEFAULT 0,
				publishdate INT,
				KEY parent_2 (parent, depth, publishdate, child),
				KEY publishdate (publishdate, child),
				KEY child (child, depth),
				KEY (displayorder),
				UNIQUE KEY closure_uniq (parent, child)
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

	/***	text table*/
	public function step_14()
	{
		if (!$this->tableExists('text'))
		{
			$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'text'),
			"
			CREATE TABLE " . TABLE_PREFIX . "text (
				nodeid INT UNSIGNED NOT NULL PRIMARY KEY,
				previewtext VARCHAR(2048),
				previewimage VARCHAR(256),
				previewvideo TEXT,
				imageheight SMALLINT,
				imagewidth SMALLINT,
				rawtext MEDIUMTEXT,
				pagetextimages TEXT,
				moderated smallint,
				pagetext MEDIUMTEXT,
				htmlstate ENUM('off', 'on', 'on_nl2br'),
				allowsmilie SMALLINT NOT NULL DEFAULT '0',
				showsignature SMALLINT NOT NULL DEFAULT '0',
				attach SMALLINT UNSIGNED NOT NULL DEFAULT '0',
				infraction SMALLINT UNSIGNED NOT NULL DEFAULT '0'
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

	/***	channel table*/
	public function step_15()
	{
		if (!$this->tableExists('channel'))
		{
			$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'channel'),
			"
			CREATE TABLE " . TABLE_PREFIX . "channel (
				nodeid INT UNSIGNED NOT NULL PRIMARY KEY,
				styleid SMALLINT NOT NULL DEFAULT '0',
				options INT(10) UNSIGNED NOT NULL DEFAULT 1728,
				daysprune SMALLINT NOT NULL DEFAULT '0',
				newcontentemail TEXT,
				defaultsortfield VARCHAR(50) NOT NULL DEFAULT 'lastcontent',
				defaultsortorder ENUM('asc', 'desc') NOT NULL DEFAULT 'desc',
				imageprefix VARCHAR(100) NOT NULL DEFAULT '',
				guid char(150) DEFAULT NULL,
				filedataid INT,
				category SMALLINT UNSIGNED NOT NULL DEFAULT '0'
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

	/***	attach table*/
	public function step_16()
	{
		if (!$this->tableExists('attach'))
		{
			$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'attach'),
			"
			CREATE TABLE " . TABLE_PREFIX . "attach (
				nodeid INT UNSIGNED NOT NULL,
				filedataid INT UNSIGNED NOT NULL,
				visible SMALLINT NOT NULL DEFAULT 1,
				counter INT UNSIGNED NOT NULL DEFAULT '0',
				posthash VARCHAR(32) NOT NULL DEFAULT '',
				filename VARCHAR(255) NOT NULL DEFAULT '',
				caption TEXT,
				reportthreadid INT UNSIGNED NOT NULL DEFAULT '0',
				settings MEDIUMTEXT,
				KEY attach_nodeid(nodeid),
				KEY attach_filedataid(filedataid)
				) ENGINE = " . $this->hightrafficengine . "
				",
				self::MYSQL_ERROR_TABLE_EXISTS
			);
		}
		else
		{
			if ($this->field_exists('node', 'attachid'))
			{
				$this->drop_field(
					sprintf($this->phrase['core']['altering_x_table'], 'attach', 1, 1),
					'attach',
					'attachid'
				);
			}
			else
			{
				$this->skip_message();
			}

		}
	}

	/***	permission table*/
	public function step_17()
	{
		if (!$this->tableExists('permission'))
		{
			$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'permission'),
			"
			CREATE TABLE " . TABLE_PREFIX . "permission (
				permissionid INT UNSIGNED NOT NULL AUTO_INCREMENT,
				nodeid INT UNSIGNED NOT NULL,
				groupid INT UNSIGNED NOT NULL,
				forumpermissions INT UNSIGNED NOT NULL DEFAULT 0,
				forumpermissions2 INT UNSIGNED NOT NULL DEFAULT 0,
				moderatorpermissions INT UNSIGNED NOT NULL DEFAULT 0,
				createpermissions INT UNSIGNED NOT NULL DEFAULT 0,
				edit_time INT UNSIGNED NOT NULL DEFAULT 0,
				skip_moderate SMALLINT UNSIGNED NOT NULL DEFAULT 1,
				maxtags SMALLINT UNSIGNED NOT NULL DEFAULT 0,
				maxstartertags SMALLINT UNSIGNED NOT NULL DEFAULT 0,
				maxothertags SMALLINT UNSIGNED NOT NULL DEFAULT 0,
				maxattachments SMALLINT UNSIGNED NOT NULL DEFAULT 0,
				maxchannels SMALLINT UNSIGNED NOT NULL DEFAULT 0,
				channeliconmaxsize INT UNSIGNED NOT NULL DEFAULT 0,
				PRIMARY KEY (permissionid),
				KEY perm_nodeid (nodeid),
				KEY perm_groupid (nodeid),
				KEY perm_group_node (groupid, nodeid)
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

	/***	contentpriority table*/
	public function step_18()
	{
		if (!$this->tableExists('contentpriority'))
		{
			$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'contentpriority'),
			"
			CREATE TABLE " . TABLE_PREFIX . "contentpriority (
				contenttypeid VARCHAR(20) NOT NULL,
				sourceid INT(10) UNSIGNED NOT NULL,
				prioritylevel DOUBLE(2,1) UNSIGNED NOT NULL,
				PRIMARY KEY (contenttypeid, sourceid)
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


	/***	tagnode table*/
	public function step_19()
	{
		if (!$this->tableExists('tagnode'))
		{
			$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'tagnode'),
			"
			CREATE TABLE " . TABLE_PREFIX . "tagnode (
				tagid INT UNSIGNED NOT NULL DEFAULT 0,
				nodeid INT UNSIGNED NOT NULL DEFAULT '0',
				userid INT UNSIGNED NOT NULL DEFAULT '0',
				dateline INT UNSIGNED NOT NULL DEFAULT '0',
				PRIMARY KEY tag_type_cid (tagid, nodeid),
				KEY id_type_user (nodeid, userid),
				KEY id_type_node (nodeid),
				KEY id_type_tag (tagid),
				KEY user (userid),
				KEY dateline (dateline)
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

	public function step_20()
	{
		$this->skip_message();
	}

	public function step_21()
	{
		$this->skip_message();
	}

	/** Make sure we have channel, text and poll type**/
	public function step_22()
	{
		$contenttype = $this->db->query_first("
			SELECT contenttypeid FROM " . TABLE_PREFIX . "contenttype
			WHERE class = 'Text'");
		if (empty($contenttype) OR empty($contenttype['contenttypeid']))
		{
			$this->run_query(
			sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'contenttype'),
			"INSERT INTO " . TABLE_PREFIX . "contenttype(class,
			packageid,	canplace,	cansearch,	cantag,	canattach,	isaggregator)
			SELECT 'Text', packageid, '0', '1', '1', '1', '0'  FROM " . TABLE_PREFIX . "package where class = 'vBForum';");
			$textTypeId = $this->db->insert_id();
		}
		//If this is the first time upgrade has been run we won't have channel and text types
		$contenttype = $this->db->query_first("
			SELECT contenttypeid FROM " . TABLE_PREFIX . "contenttype
			WHERE class = 'Channel'");

		if (empty($contenttype) OR empty($contenttype['contenttypeid']))
		{
			$this->db->query_write(
				"INSERT INTO " . TABLE_PREFIX . "contenttype(class,
			packageid,	canplace,	cansearch,	cantag,	canattach,	isaggregator)
			SELECT 'Channel', packageid, '0','1', '0', '0', '1' FROM " . TABLE_PREFIX . "package where class = 'vBForum';");
			vB_Types::instance()->reloadTypes();
			$contenttypeid = vB_Types::instance()->getContentTypeID('vBForum_Channel');
		}



		$contenttype = $this->db->query_first("
			SELECT contenttypeid FROM " . TABLE_PREFIX . "contenttype
			WHERE class = 'Poll'");
		if (empty($contenttype) OR empty($contenttype['contenttypeid']))
		{
			$this->run_query(
			sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'contenttype'),
			"INSERT INTO " . TABLE_PREFIX . "contenttype(class,
			packageid,	canplace,	cansearch,	cantag,	canattach,	isaggregator)
			SELECT 'Poll', packageid, '0', '1', '0', '0', '0'  FROM " . TABLE_PREFIX . "package where class = 'vBForum';");
			$pollTypeId = $this->db->insert_id();

		}
		else
		{
			$this->skip_message();
		}
	}
	/***	Create the home page record
		This step is skipped because it used to manually add pages. Pages are now imported from the xml files.
	*/
	public function step_23()
	{
		$this->skip_message();
	}

	public function step_24()
	{
		$this->skip_message();
		$this->long_next_step();
	}

	public function step_25()
	{
		// Make a backup of the poll tables, so that we can remove any orphaned poll records. Otherwise, the mapping in steps step_149~152 will
		// fail and upgrade cannot continue due to Duplicate entry ... for key 'PRIMARY' due to NULLs in the poll.nodeid column.
		// Creating the backup before steps 34~39 alters the vb4 poll table.

		if (!$this->tableExists('legacy_poll'))
		{
			$this->show_message(sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'legacy_poll'));
			$assertor = vB::getDbAssertor();
			$assertor->assertQuery('vBInstall:createPollBackup');
			$assertor->assertQuery('vBInstall:populatePollBackup');
		}
		else
		{
			$this->skip_message();
		}
		$this->long_next_step();
	}

	public function step_26()
	{
		// see above comment.
		if (!$this->tableExists('legacy_pollvote'))
		{
			$this->show_message(sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'legacy_pollvote'));
			$assertor = vB::getDbAssertor();
			$assertor->assertQuery('vBInstall:createPollvoteBackup');
			$assertor->assertQuery('vBInstall:populatePollvoteBackup');
		}
		{
			$this->skip_message();
		}
		$this->long_next_step();
	}

	public function step_27()
	{
		// Remove the orphaned poll records. Orphaned poll record is defined as a vb4 poll record whose thread record matched by poll.threadid
		// does not exist.
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'poll'));
		$assertor = vB::getDbAssertor();
		$assertor->assertQuery('vBInstall:removeOrphanedPollRecords');

		$this->long_next_step();
	}

	// Displayname column was added in 566, but many queries that are downstream of content::add() for example expect the field to exist, so
	// we must add it before we start importing data via vB5 APIs.
	public function step_28()
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

	public function step_29()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'user', 2, 3),
			'user',
			'displayname',
			['displayname', ]
		);
		$this->long_next_step();
	}

	public function step_30($data)
	{
		if ($this->field_exists('user', 'displayname'))
		{
			// Avoid rerunning this if we already ran it at least once
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

	public function step_31()
	{
		if (!$this->tableExists('words'))
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'words'),
				"CREATE TABLE " . TABLE_PREFIX . "words (
					wordid int(11) NOT NULL AUTO_INCREMENT,
					word varchar(50) NOT NULL,
					PRIMARY KEY (wordid),
					UNIQUE KEY word (word)
				) ENGINE = " . $this->hightrafficengine . ";"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_32()
	{
		$created = false;
		for ($i=ord('a'); $i<=ord('z'); $i++)
		{
			if (!$this->tableExists("searchtowords_".chr($i)))
			{
				$this->run_query(
					sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . "searchtowords_".chr($i)),
					"CREATE TABLE " . TABLE_PREFIX . "searchtowords_".chr($i)." (
						wordid int(11) NOT NULL,
						nodeid int(11) NOT NULL,
						is_title TINYINT(1) NOT NULL DEFAULT '0',
						score INT NOT NULL DEFAULT '0',
						position INT NOT NULL DEFAULT '0',
						UNIQUE (wordid, nodeid),
						UNIQUE (nodeid, wordid)
					) ENGINE = " . $this->hightrafficengine . ""
				);

				$created = true;
			}
		}
		if (!$created)
		{
			$this->skip_message();
		}
	}

	public function step_33()
	{
		if (!$this->tableExists('searchtowords_other'))
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . "searchtowords_other"),
				"CREATE TABLE " . TABLE_PREFIX . "searchtowords_other (
					wordid int(11) NOT NULL,
					nodeid int(11) NOT NULL,
					is_title TINYINT(1) NOT NULL DEFAULT '0',
					score INT NOT NULL DEFAULT '0',
					position INT NOT NULL DEFAULT '0',
					UNIQUE (wordid, nodeid),
					UNIQUE (nodeid, wordid)
				) ENGINE = " . $this->hightrafficengine . ""
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	// Poll upgrade
	/** Add polloptions table **/
	public function step_34()
	{
		if (!$this->tableExists('polloption'))
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'polloption'),
				"
					CREATE TABLE " . TABLE_PREFIX . "polloption (
					polloptionid int(10) unsigned NOT NULL AUTO_INCREMENT,
					nodeid int(10) unsigned NOT NULL DEFAULT '0',
					title text,
					votes int(10) unsigned NOT NULL DEFAULT '0',
					voters text,
					PRIMARY KEY (polloptionid),
					KEY nodeid (nodeid)
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

	/** remove the auto-increment from pollid **/
	public function step_35()
	{
		if ($this->field_exists('poll', 'pollid'))
		{
			// Poll table
			// Remove pollid's AUTO_INCREMENT
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'poll', 1, 7),
				"ALTER TABLE " . TABLE_PREFIX . "poll CHANGE pollid pollid INT UNSIGNED NOT NULL DEFAULT '0'"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/**  Add index to pollid for better performance**/
	public function step_36()
	{
		if ($this->field_exists('poll', 'pollid'))
		{
			$this->add_index(
				sprintf($this->phrase['core']['altering_x_table'], 'poll', 2, 7),
				'poll',
				'oldpollid',
				'pollid'
			);
		}
		else
		{
			$this->skip_message();
		}

	}

	/**  Change timeout to an INT **/
	public function step_37()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'poll', 3, 7),
			"ALTER TABLE " . TABLE_PREFIX . "poll CHANGE timeout timeout INT UNSIGNED NOT NULL DEFAULT '0'"
		);
	}


	/**Drop poll table's primary key **/
	public function step_38()
	{
		$polldescr = $this->db->query_first("SHOW COLUMNS FROM " . TABLE_PREFIX . "poll LIKE 'pollid'");

		if (!empty($polldescr['Key']) AND ($polldescr['Key'] == 'PRI'))
		{
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'poll', 4, 7),
				"ALTER TABLE " . TABLE_PREFIX . "poll DROP PRIMARY KEY",
				self::MYSQL_ERROR_DROP_KEY_COLUMN_MISSING);
		}
		else
		{
			$this->skip_message();
		}

	}

	/** Add nodeid to the poll table **/
	public function step_39()
	{
		if (!$this->field_exists('poll', 'nodeid'))
		{
			// Create nodeid field
			$this->add_field(
				sprintf($this->phrase['core']['altering_x_table'], 'poll', 5, 7),
				'poll',
				'nodeid',
				'INT',
				array(
					'extra' => ' AFTER pollid',
					'default' => null,
				)
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/** change the votes field **/
	public function step_40()
	{
		// Rename old voters field to votes
		if ($this->field_exists('poll', 'voters'))
		{
			// Drop old votes field
			$this->drop_field(
				sprintf($this->phrase['core']['altering_x_table'], 'poll', 6, 7),
				'poll',
				'votes'
			);

			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'poll', 7, 7),
				"ALTER TABLE " . TABLE_PREFIX . "poll CHANGE voters votes SMALLINT UNSIGNED NOT NULL DEFAULT '0'"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/**  set the timeout field to be seconds not days **/
	public function step_41()
	{
		if ($this->field_exists('poll', 'dateline'))
		{
			$this->run_query(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'poll'),
				'UPDATE ' . TABLE_PREFIX . 'poll SET timeout = dateline + timeout * 3600 * 24 WHERE timeout < 99999 AND timeout > 0');
		}
		else
		{
			$this->skip_message();
		}
	}


	/** Add polloptionid and nodeid field to pollvote table **/
	public function step_42()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'pollvote', 1, 7),
			'pollvote',
			'nodeid',
			'INT',
			self::FIELD_DEFAULTS
		);
	}

	/** Add polloptionid and nodeid field to pollvote table**/
	public function step_43()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'pollvote', 2, 7),
			'pollvote',
			'polloptionid',
			'INT',
			self::FIELD_DEFAULTS
		);

		// For step_160
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'pollvote', 3, 7),
			'pollvote',
			'polloptionid',
			array('polloptionid')
		);
	}

	/** Add index to pollvote table**/
	public function step_44()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'pollvote', 4, 7),
			'pollvote',
			'nodeid',
			array('nodeid', 'userid', 'polloptionid')
		);
	}

	/** drop an unnecessary index **/
	public function step_45()
	{
		// poll table
		$this->drop_index(
			sprintf($this->phrase['core']['altering_x_table'], 'pollvote', 5, 7),
			'pollvote',
			'pollid'
		);

		// For step_160
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'pollvote', 6, 7),
			'pollvote',
			'pollid',
			array('pollid', 'voteoption')
		);
	}

	/** drop the votetype field in pollvote **/
	public function step_46()
	{
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], 'pollvote', 7, 7),
			'pollvote',
			'votetype'
		);
	}

	public function step_47()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'gallery'),
			"
				CREATE TABLE " . TABLE_PREFIX . "gallery (
				nodeid INT UNSIGNED NOT NULL,
				caption VARCHAR(512),
				PRIMARY KEY (nodeid)
				) ENGINE = " . $this->hightrafficengine . "
				",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
		//If this is the first time upgrade has been run we won't have Gallery type
		$contenttype = $this->db->query_first("
			SELECT contenttypeid FROM " . TABLE_PREFIX . "contenttype
			WHERE class = 'Gallery'");

		if (empty($contenttype) OR empty($contenttype['contenttypeid']))
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'contenttype'),
				"INSERT INTO " . TABLE_PREFIX . "contenttype(class,
			packageid,	canplace,	cansearch,	cantag,	canattach,	isaggregator)
			SELECT 'Gallery', packageid,  '1', '0', '1', '1', '1' FROM " . TABLE_PREFIX . "package where class = 'vBForum';");

			$contenttype = $this->db->query_first("
				SELECT contenttypeid FROM " . TABLE_PREFIX . "contenttype
				WHERE class = 'Photo'");

			if (empty($contenttype) OR empty($contenttype['contenttypeid']))
			{
				$this->run_query(
					sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'contenttype'),
					"INSERT INTO " . TABLE_PREFIX . "contenttype(class,
				packageid,	canplace,	cansearch,	cantag,	canattach,	isaggregator)
				SELECT 'Photo', packageid,  '0', '0', '1', '1', '1' FROM " . TABLE_PREFIX . "package where class = 'vBForum';");
			}
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_48()
	{
		vB_Types::instance()->reloadTypes();
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'photo'),
			"
			CREATE TABLE " . TABLE_PREFIX . "photo (
			photoid  INT UNSIGNED NOT NULL AUTO_INCREMENT,
			nodeid INT UNSIGNED NOT NULL,
			filedataid INT UNSIGNED NOT NULL,
			caption VARCHAR(512),
			height SMALLINT UNSIGNED NOT NULL DEFAULT '0',
			width SMALLINT UNSIGNED NOT NULL DEFAULT '0',
			style varchar(512),
			PRIMARY KEY (photoid),
			KEY (nodeid)
			) ENGINE = " . $this->hightrafficengine . "
		",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	/** Update Infraction Data
	 *
	 **/
	public function step_49()
	{
		if ($this->field_exists('infraction', 'nodeid'))
		{
			$this->skip_message();
		}
		else
		{
			$this->add_field(
				sprintf($this->phrase['core']['altering_x_table'], 'infraction', 1, 2),
				'infraction',
				'nodeid',
				'INT',
				self::FIELD_DEFAULTS
			);

			$this->add_field(
				sprintf($this->phrase['core']['altering_x_table'], 'infraction', 2, 2),
				'infraction',
				'channelid',
				'INT',
				self::FIELD_DEFAULTS
			);
		}
	}

	/**
	 * add field  publicview in filedata table
	 *
	 */
	public function step_50()
	{
		if ($this->tableExists('filedata') AND !$this->field_exists('filedata', 'publicview'))
		{
			$this->add_field(
				sprintf($this->phrase['core']['altering_x_table'], 'filedata ', 1, 1),
				'filedata',
				'publicview',
				'smallint',
				self::FIELD_DEFAULTS
			);
		}
		else
		{
			$this->skip_message();
		}

	}

	/** Create initial screen layouts
	 *
	 */
	public function step_51()
	{
		$screenLayOutRecords = $this->db->query_first("
		SELECT screenlayoutid FROM " . TABLE_PREFIX . "screenlayout");

		if (empty($screenLayOutRecords) OR empty($screenLayOutRecords['screenlayoutid']))
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'screenlayout'),
				"INSERT INTO " . TABLE_PREFIX . "screenlayout
			(screenlayoutid, varname, title, displayorder, columncount, template, admintemplate)
			VALUES
			(1, '100', '100', 4, 1, 'screenlayout_1', 'admin_screenlayout_1'),
			(2, '70-30', '70/30', 1, 2, 'screenlayout_2', 'admin_screenlayout_2'),
			(3, '50-50', '50/50', 2, 2, 'screenlayout_3', 'admin_screenlayout_3'),
			(4, '30-70', '30/70', 3, 2, 'screenlayout_4', 'admin_screenlayout_4');
			"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	 * Fixing the contenttype table
	 */
	public function step_52()
	{
		$not_searchable = array(
			"Post",
			"Thread",
			"Forum",
			"Announcement",
			"SocialGroupMessage",
			"SocialGroupDiscussion",
			"SocialGroup",
			"Album",
			"Picture",
			"PictureComment",
			"VisitorMessage",
			"User",
			"Event",
			"Calendar",
			"BlogEntry",
			"Channel",
			"BlogComment"
		);
		$searchable = array(
			"Text",
			"Attach",
			"Poll",
			"Photo",
			"Gallery"
		);
		$this->run_query(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . "contenttype"),
			"UPDATE " . TABLE_PREFIX  . "contenttype SET cansearch = '0' WHERE class IN (\"" . implode('","',$not_searchable) . "\");");

		$this->run_query(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . "contenttype"),
			"UPDATE " . TABLE_PREFIX  . "contenttype SET cansearch = '1' WHERE class IN (\"" . implode('","',$searchable) . "\") AND packageid = 1;");
	}

	/***	Adding who is online fields to session table*/
	public function step_53()
	{
		// Clear all sessions first, otherwise we can fail with "table full" error.
		$this->run_query(sprintf($this->phrase['vbphrase']['update_table'], 'session'),
			"TRUNCATE TABLE " . TABLE_PREFIX . "session"
		);

		if ( !$this->field_exists('session', 'wol'))
		{
			$this->add_field(
				sprintf($this->phrase['core']['altering_x_table'], 'session', 1, 5),
				'session',
				'wol',
				'char',
				array('length' => 255)
			);
		}
		else
		{
			$this->skip_message();
		}

		if ( !$this->field_exists('session', 'nodeid'))
		{
			$this->add_field(
				sprintf($this->phrase['core']['altering_x_table'], 'session', 2, 5),
				'session',
				'nodeid',
				'int',
				self::FIELD_DEFAULTS
			);
			$this->add_index(
				sprintf($this->phrase['core']['altering_x_table'], 'session', 3, 5),
				'session',
				'nodeid',
				'nodeid'
			);
		}
		else
		{
			$this->skip_message();
		}

		if ( !$this->field_exists('session', 'pageid'))
		{
			$this->add_field(
				sprintf($this->phrase['core']['altering_x_table'], 'session', 4, 5),
				'session',
				'pageid',
				'int',
				self::FIELD_DEFAULTS
			);
			$this->add_index(
				sprintf($this->phrase['core']['altering_x_table'], 'session', 5, 5),
				'session',
				'pageid',
				'pageid'
			);
		}

		else
		{
			$this->skip_message();
		}

	}

	/***	Setting inlist to the node table*/
	public function step_54()
	{
		$this->run_query(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'node'),
			"UPDATE " . TABLE_PREFIX . "node AS node INNER JOIN " . TABLE_PREFIX . "contenttype AS t ON t.contenttypeid = node.contenttypeid
		SET node.inlist = 0 WHERE t.canplace = '0';");
	}

	/** Creating site table */
	public function step_55()
	{
		if (!$this->tableExists('site'))
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'site'),
				"
				CREATE TABLE " . TABLE_PREFIX . "site (
					siteid INT NOT NULL AUTO_INCREMENT,
					title VARCHAR(100) NOT NULL,
					headernavbar MEDIUMTEXT NULL,
					footernavbar MEDIUMTEXT NULL,
					PRIMARY KEY (siteid)
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

	/** If this is a 3.X blog there's some changes we need to make. **/

	/** We need to handle any blog table changes **/
	public function step_56()
	{
		if (!$this->tableExists('blog'))
		{
			$this->skip_message();
		}
		else
		{
			if (! $this->field_exists('blog', 'categories'))
			{
				$this->add_field(
					sprintf($this->phrase['core']['altering_x_table'], 'blog', 1, 3),
					'blog',
					'categories',
					'mediumtext',
					self::FIELD_DEFAULTS
				);
			}
			else
			{
				$this->skip_message();
			}


			if (! $this->field_exists('blog', 'taglist'))
			{
				$this->add_field(
					sprintf($this->phrase['core']['altering_x_table'], 'blog', 2, 3),
					'blog',
					'taglist',
					'mediumtext',
					self::FIELD_DEFAULTS
				);
			}
			else
			{
				$this->skip_message();
			}

			if (! $this->field_exists('blog', 'postedby_userid'))
			{
				$this->add_field(
					sprintf($this->phrase['core']['altering_x_table'], 'blog', 3, 3),
					'blog',
					'postedby_userid',
					'int',
					self::FIELD_DEFAULTS
				);
			}
			else
			{
				$this->skip_message();
			}

		}
	}

	/***	Adding htmlstate to the cms_article table (a vB4 table).
			Apparently this is to avoid problems with the CMS import later on */
	public function step_57()
	{
		if (isset($this->registry->products['vbcms']) AND $this->registry->products['vbcms'])
		{
			if ($this->tableExists('cms_article') AND !$this->field_exists('cms_article', 'htmlstate'))
			{
				$this->add_field(
					sprintf($this->phrase['core']['altering_x_table'], 'cms_article', 1, 1),
					'cms_article',
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
		else
		{
			$this->skip_message();
		}
		$this->long_next_step();
	}

	public function step_58()
	{
		$this->skip_message();
	}

	public function step_59()
	{
		// updating searchlog
		if (!$this->field_exists('searchlog', 'json'))
		{
			$this->add_field(
				sprintf($this->phrase['core']['altering_x_table'], 'searchlog ', 1, 3),
				'searchlog',
				'json',
				'text',
				self::FIELD_DEFAULTS
			);
			$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'searchlog'));
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_60()
	{
		// updating searchlog
		if (!$this->field_exists('searchlog', 'results_count'))
		{
			$this->add_field(
				sprintf($this->phrase['core']['altering_x_table'], 'searchlog ', 2, 3),
				'searchlog',
				'results_count',
				'text',
				self::FIELD_DEFAULTS
			);

			$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'searchlog'));
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_61()
	{
		// updating searchlog
		if ($this->field_exists('searchlog', 'criteria'))
		{
			$this->drop_field(
				sprintf($this->phrase['core']['altering_x_table'], 'searchlog ', 3, 3),
				'searchlog',
				'criteria'
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
			sprintf($this->phrase['core']['altering_x_table'], 'usergroup', 1, 1),
			'usergroup',
			'forumpermissions2',
			'int',
			self::FIELD_DEFAULTS
		);
	}


	/** Adding systemgroupid to usergroup table **/
	public function step_63()
	{
		if (!$this->field_exists('usergroup', 'systemgroupid'))
		{
			$this->add_field(
				sprintf($this->phrase['core']['altering_x_table'], 'usergroup', 1, 1),
				'usergroup',
				'systemgroupid',
				'SMALLINT',
				array('attributes' => vB_Upgrade_Version::FIELD_DEFAULTS)
			);
		}

		// we need this step to be run before 155 cause we set sitebuilder permission based on systemgroupid there
		$this->run_query(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'usergroup'),
			"UPDATE " . TABLE_PREFIX . "usergroup
			SET systemgroupid = usergroupid
			WHERE usergroupid <= 7"
		);
	}

	/** Make sure we have the six system groups */
	public function step_64()
	{
		vB_Upgrade::createAdminSession();

		//this is really to update the cache after the previous step updates the table.  However run_query
		//still has the (outdated) logic to only run after the step function returns and so including it in the
		//prior step wouldn't help anything.  Ensure that systemids are availble for this step.
		vB_Library::instance('usergroup')->buildDatastore();

		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'usergroup'));
		$this->createSystemGroups();
	}

	public function step_65()
	{
		$this->skip_message();
	}

	public function step_66()
	{
		$this->skip_message();
	}

	public function step_67()
	{
		$this->skip_message();
	}

	public function step_68()
	{
		$this->skip_message();
	}


	public function step_69()
	{
		$this->skip_message();
	}

	public function step_70()
	{
		if (!$this->tableExists('nodevote'))
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'nodevote'),
				"
				CREATE TABLE " . TABLE_PREFIX . "nodevote (
					nodevoteid INT UNSIGNED NOT NULL AUTO_INCREMENT,
					nodeid INT UNSIGNED NOT NULL DEFAULT '0',
					userid INT UNSIGNED NULL DEFAULT NULL,
					votedate INT UNSIGNED NOT NULL DEFAULT '0',
					PRIMARY KEY (nodevoteid),
					UNIQUE KEY nodeid (nodeid, userid),
					KEY userid (userid)
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

	public function step_71()
	{
		$this->skip_message();
	}

	/*** Create the groupintopic table
	 */
	public function step_72()
	{
		if (!$this->tableExists('groupintopic'))
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'groupintopic'),
				"
			CREATE TABLE " . TABLE_PREFIX . "groupintopic (
				userid INT UNSIGNED NOT NULL,
				groupid INT UNSIGNED NOT NULL,
				nodeid INT UNSIGNED NOT NULL,
				UNIQUE KEY (userid, groupid, nodeid),
				KEY (userid)
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

	/** create nodestats table **/
	public function step_73()
	{
		// removed, VBV-11871
		$this->skip_message();
	}

	/** create nodevisits table **/
	public function step_74()
	{
		// removed, VBV-11871
		$this->skip_message();
	}

	/** create nodestatsmax table **/
	public function step_75()
	{
		// removed, VBV-11871
		$this->skip_message();
	}

	/**
	 * Create noderead table
	 */
	public function step_76()
	{
		if (!$this->tableExists('noderead'))
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'noderead'),
				"
				CREATE TABLE " . TABLE_PREFIX . "noderead (
					userid int(10) unsigned NOT NULL default '0',
					nodeid int(10) unsigned NOT NULL default '0',
					readtime int(10) unsigned NOT NULL default '0',
					PRIMARY KEY  (userid, nodeid),
					KEY readtime (readtime)
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

	/* Get paymenttransaction table */
	public function step_77()
	{
		if (!$this->tableExists('paymenttransaction'))
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'paymenttransaction'),
				"
				CREATE TABLE " . TABLE_PREFIX . "paymenttransaction (
					paymenttransactionid INT UNSIGNED NOT NULL AUTO_INCREMENT,
					paymentinfoid INT UNSIGNED NOT NULL DEFAULT '0',
					transactionid VARCHAR(250) NOT NULL DEFAULT '',
					state SMALLINT UNSIGNED NOT NULL DEFAULT '0',
					amount DOUBLE UNSIGNED NOT NULL DEFAULT '0',
					currency VARCHAR(5) NOT NULL DEFAULT '',
					dateline INT UNSIGNED NOT NULL DEFAULT '0',
					paymentapiid INT UNSIGNED NOT NULL DEFAULT '0',
					request MEDIUMTEXT,
					reversed INT UNSIGNED NOT NULL DEFAULT '0',
					PRIMARY KEY (paymenttransactionid),
					KEY dateline (dateline),
					KEY transactionid (transactionid),
					KEY paymentapiid (paymentapiid)
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

	public function step_78()
	{
		if (!$this->tableExists('privatemessage'))
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'privatemessage'),
				"CREATE TABLE " . TABLE_PREFIX . "privatemessage (
				nodeid INT UNSIGNED NOT NULL,
				msgtype enum('message','notification','request') NOT NULL default 'message',
				about enum('vote', 'vote_reply', 'rate', 'reply', 'follow', 'vm', 'comment' ),
				aboutid INT,
				PRIMARY KEY (nodeid)
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

	//Add sentto table for private messages
	public function step_79()
	{
		if (!$this->tableExists('sentto'))
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'sentto'),
				"CREATE TABLE " . TABLE_PREFIX . "sentto (
				nodeid INT NOT NULL,
				userid INT NOT NULL,
				folderid INT NOT NULL,
				deleted SMALLINT NOT NULL DEFAULT 0,
				msgread SMALLINT NOT NULL DEFAULT 0,
				PRIMARY KEY(nodeid, userid, folderid),
				KEY (nodeid),
				KEY (userid),
				KEY (folderid)
			) ENGINE = " . $this->hightrafficengine . "",
				self::MYSQL_ERROR_TABLE_EXISTS
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	//Add messagefolder table for private messages
	public function step_80()
	{
		if (!$this->tableExists('messagefolder'))
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'messagefolder'),
				"CREATE TABLE " . TABLE_PREFIX . "messagefolder (
				folderid INT UNSIGNED NOT NULL AUTO_INCREMENT,
				userid INT UNSIGNED NOT NULL,
				title varchar(512),
				titlephrase varchar(250),
				PRIMARY KEY (folderid),
				KEY (userid)
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

	//Add google provider to user table
	public function step_81()
	{
		if (!$this->field_exists('user', 'google'))
		{
			$this->add_field(
				sprintf($this->phrase['core']['altering_x_table'], 'user ', 1, 1),
				'user',
				'google',
				'char',
				array('length' => 32, 'default' => '', 'extra' => 'after skype')
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/*** Add the nodeid to the moderators table */
	public function step_82()
	{
		if (!$this->field_exists('moderator', 'nodeid'))
		{
			$this->add_field(
				sprintf($this->phrase['core']['altering_x_table'], 'moderator', 1, 1),
				'moderator',
				'nodeid',
				'INT',
				self::FIELD_DEFAULTS
			);
		}
		else
		{
			$this->skip_message();
		}
	}


	/*** Add the nodeid to the moderatorlog table */
	public function step_83()
	{
		if (!$this->field_exists('moderatorlog', 'nodeid'))
		{
			$this->add_field(
				sprintf($this->phrase['core']['altering_x_table'], 'moderatorlog', 1, 1),
				'moderatorlog',
				'nodeid',
				'INT',
				self::FIELD_DEFAULTS
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/*** Add the nodeid to the access table */
	public function step_84()
	{
		if (!$this->field_exists('access', 'nodeid'))
		{
			$this->add_field(
				sprintf($this->phrase['core']['altering_x_table'], 'access', 1, 1),
				'access',
				'nodeid',
				'INT',
				self::FIELD_DEFAULTS
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	// Update old reputation table
	public function step_85()
	{
		if (!$this->field_exists('reputation', 'nodeid'))
		{
			$this->add_field(
				sprintf($this->phrase['core']['altering_x_table'], 'reputation', 1, 1),
				'reputation',
				'nodeid',
				'INT',
				self::FIELD_DEFAULTS
			);
		}
		else
		{
			$this->skip_message();
		}


	}

	public function step_86()
	{
		$this->skip_message();
	}

	public function step_87()
	{
		$this->skip_message();
	}

	public function step_88()
	{
		$this->skip_message();
	}

	//For handling private message deletion
	public function step_89()
	{
		if (!$this->field_exists('privatemessage', 'deleted'))
		{
			$this->add_field(
				sprintf($this->phrase['core']['altering_x_table'], 'privatemessage', 1, 1),
				'privatemessage',
				'deleted',
				'INT',
				self::FIELD_DEFAULTS
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_90()
	{
		$this->skip_message();
	}

	public function step_91()
	{
		$this->skip_message();
	}

	public function step_92()
	{
		$this->skip_message();
	}

	public function step_93()
	{
		$this->skip_message();
	}

	public function step_94()
	{
		$this->skip_message();
	}

	public function step_95()
	{
		$this->skip_message();
	}

	public function step_96()
	{
		$this->skip_message();
	}

	public function step_97()
	{
		$this->skip_message();
	}

	public function step_98()
	{
		$this->skip_message();
	}

	public function step_99()
	{
		$this->skip_message();
	}

	public function step_100()
	{
		$this->skip_message();
	}

	public function step_101()
	{
		$this->skip_message();
	}

	public function step_102()
	{
		if (!$this->tableExists('widgetchannelconfig'))
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'widgetchannelconfig'),
				"
				CREATE TABLE " . TABLE_PREFIX . "widgetchannelconfig (
				  widgetinstanceid int(10) unsigned NOT NULL,
				  nodeid int(10) unsigned NOT NULL,
				  channelconfig blob NOT NULL,
				  UNIQUE KEY widgetinstanceid (widgetinstanceid,nodeid)
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

	public function step_103()
	{
		$this->skip_message();
	}

	/** Adding blog phrase type for language table **/
	public function step_104()
	{
		if (!$this->field_exists('language', 'phrasegroup_vb5blog'))
		{
			$this->add_field(
				sprintf($this->phrase['core']['altering_x_table'], 'language', 1, 1),
				'language',
				'phrasegroup_vb5blog',
				'mediumtext',
				self::FIELD_DEFAULTS
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_105()
	{
		$this->skip_message();
	}

	public function step_106()
	{
		$this->skip_message();
	}

	public function step_107()
	{
		$this->skip_message();
	}

	public function step_108()
	{
		if (!$this->field_exists('pagetemplate', 'product'))
		{
			$this->add_field(
				sprintf($this->phrase['core']['altering_x_table'], 'pagetemplate', 1, 1),
				'pagetemplate',
				'product',
				'VARCHAR',
				array(
					'length' => 25,
					'default' => 'vbulletin',
				)
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_109()
	{
		if (!$this->field_exists('page', 'product'))
		{
			$this->add_field(
				sprintf($this->phrase['core']['altering_x_table'], 'page', 1, 1),
				'page',
				'product',
				'VARCHAR',
				array(
					'length' => 25,
					'default' => 'vbulletin',
				)
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_110()
	{
		$this->add_field2(
			'channel',
			'product',
			'VARCHAR',
			[
				'length' => 25,
				'default' => 'vbulletin',
			]
		);

		// step_132() below will throw errors without these new columns, even though
		// these columns are added in vb6
		$this->add_field2(
			'channel',
			'topicexpiretype',
			'ENUM',
			[
				'attributes' => "('none', 'soft', 'hard')",
				'null'       => false,
				'default'    => 'none',
			]
		);
		$this->add_field2(
			'channel',
			'topicexpireseconds',
			'INT',
			[
				'attributes' => 'UNSIGNED',
				'null'       => false,
				'default'    => 0,
			]
		);
	}

	public function step_111()
	{
		if (!$this->field_exists('routenew', 'product'))
		{
			$this->add_field(
				sprintf($this->phrase['core']['altering_x_table'], 'routenew', 1, 1),
				'routenew',
				'product',
				'VARCHAR',
				array(
					'length' => 25,
					'default' => 'vbulletin',
				)
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/** adding ipv6 fields to strike table **/
	public function step_112()
	{
		if (!$this->field_exists('strikes', 'ip_4'))
		{
			// add new IP fields for IPv4-mapped IPv6 addresses
			$this->add_field(
				sprintf($this->phrase['core']['altering_x_table'], 'strikes', 1, 6),
				'strikes',
				'ip_4',
				'INT UNSIGNED',
				array(
					'null' => false,
					'default' => 0
				)
			);
		}
		else
		{
			$this->skip_message();
		}
	}
	/** adding ipv6 fields to strike table **/
	public function step_113()
	{
		if (!$this->field_exists('strikes', 'ip_3'))
		{
			$this->add_field(
				sprintf($this->phrase['core']['altering_x_table'], 'strikes', 2, 6),
				'strikes',
				'ip_3',
				'INT UNSIGNED',
				array(
					'null' => false,
					'default' => 0
				)
			);
		}
		else
		{
			$this->skip_message();
		}
	}
	/** adding ipv6 fields to strike table **/
	public function step_114()
	{
		if (!$this->field_exists('strikes', 'ip_2'))
		{
			$this->add_field(
				sprintf($this->phrase['core']['altering_x_table'], 'strikes', 3, 6),
				'strikes',
				'ip_2',
				'INT UNSIGNED',
				array(
					'null' => false,
					'default' => 0
				)
			);
		}
		else
		{
			$this->skip_message();
		}
	}
	/** adding ipv6 fields to strike table **/
	public function step_115()
	{
		if (!$this->field_exists('strikes', 'ip_1'))
		{
			$this->add_field(
				sprintf($this->phrase['core']['altering_x_table'], 'strikes', 4, 6),
				'strikes',
				'ip_1',
				'INT UNSIGNED',
				array(
					'null' => false,
					'default' => 0
				)
			);

			// add indexes
			$this->add_index(
				sprintf($this->phrase['core']['altering_x_table'], 'strikes', 5, 6),
				'strikes',
				'ip',
				array('ip_4', 'ip_3', 'ip_2', 'ip_1')
			);

		}
		else
		{
			$this->skip_message();
		}
	}
	/** adding ipv6 fields to strike table **/
	public function step_116()
	{
		// increase length for IPv6 addresses
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'strikes', 6, 6),
			"ALTER TABLE " . TABLE_PREFIX . "strikes MODIFY COLUMN strikeip char(39) NOT NULL"
		);
	}

	// Add ispublic field
	public function step_117()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'setting', 1, 2),
			'setting',
			'ispublic',
			'SMALLINT',
			self::FIELD_DEFAULTS
		);
	}

	// Add ispublic index
	public function step_118()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'setting', 2, 2),
			'setting',
			'ispublic',
			'ispublic'
		);
	}

	public function step_119()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'moderatorlog', 1, 1),
			'moderatorlog',
			'nodetitle',
			'VARCHAR',
			array('length' => 256, 'attributes' => self::FIELD_DEFAULTS)
		);
	}

	public function step_120()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'customavatar', 1, 1),
			'customavatar',
			'extension',
			'VARCHAR',
			array('length' => 10, 'null' => false, 'default' => '')
		);
	}

	public function step_121()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'user ', 1, 3),
			'user',
			'status',
			'mediumtext',
			array('null' => true, 'extra' => 'after google')
		);
	}

	public function step_122()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'user ', 2, 3),
			'user',
			'notification_options',
			'int',
			array('attributes' => 'UNSIGNED', 'default' => '134217722', 'extra' => 'after options')
		);
	}

	public function step_123()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'user ', 3, 3),
			'user',
			'privacy_options',
			'mediumtext',
			array('null' => true, 'extra' => 'after options')
		);
	}

	public function step_124()
	{
		//need to add this to make the import work in step_129 blow.  Added here
		//because it was a convenient blank step that avoided renumbering all of the
		//steps
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'pagetemplate', 1, 1),
			'pagetemplate',
			'screenlayoutsectiondata',
			'text',
			array('null' => false, 'default' => '')
		);
		$this->execute();
	}

	public function step_125()
	{
		/* Need to save these for later,
		as the steps below wipe them out */
		$this->run_query(
			sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'adminutil'),
			"INSERT IGNORE INTO " . TABLE_PREFIX . "adminutil
			(title,	text)
			SELECT varname, value
			FROM " . TABLE_PREFIX . "setting
			WHERE varname IN ('as_expire', 'as_perpage') ");
	}

	/** Add the private message content type if needed **/
	public function step_126()
	{
		$contenttype = $this->db->query_first("
			SELECT contenttypeid FROM " . TABLE_PREFIX . "contenttype
			WHERE class = 'PrivateMessage'");

		if (empty($contenttype) OR empty($contenttype['contenttypeid']))
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'contenttype'),
				"INSERT INTO " . TABLE_PREFIX . "contenttype
				(class,	packageid,	canplace,	cansearch,	cantag,	canattach,	isaggregator)
			SELECT 'PrivateMessage', packageid, '0', '1', '0', '0', '0'  FROM " . TABLE_PREFIX . "package WHERE class = 'vBForum';");
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	 * drop unneeded indices.
	 */
	public function step_127()
	{
		// Drop old indexes
		$this->drop_index(
			sprintf($this->phrase['core']['altering_x_table'], 'reputation', 1, 2),
			'reputation',
			'whoadded_postid'
		);

		$this->drop_index(
			sprintf($this->phrase['core']['altering_x_table'], 'reputation', 2, 2),
			'reputation',
			'multi'
		);
	}

	/** We need to import the initial information from the xml files. This is part of final upgrade **/
	public function step_128()
	{
		vB_Library::clearCache();
		$this->final_load_widgets();
	}

	/** We need to import the initial information from the xml files. This is part of final upgrade **/
	public function step_129()
	{
		$this->final_load_pagetemplates();
	}

	/* Add editlog.nodeid -- this will get non first posts.
	*/
	public function step_130()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'editlog', 1, 1),
			'editlog',
			'nodeid',
			'int',
			self::FIELD_DEFAULTS
		);
	}


	/** We need to import the initial information from the xml files. This is part of final upgrade **/
	public function step_131()
	{
		vB_Library::clearCache();
		$this->final_load_pages();
	}

	/** We need to import the initial information from the xml files. This is part of final upgrade **/
	public function step_132()
	{
		vB_Library::clearCache();
		$this->final_load_channels();
	}

	/** We need to import the initial information from the xml files. This is part of final upgrade **/
	public function step_133()
	{
		vB_Api::clearCache();
		vB_Library::clearCache();
		$this->final_load_routes();
	}

	/** We need to import the initial information from the xml files. This is part of final upgrade **/
	public function step_134()
	{
		$this->final_configure_channelwidgetinstance();
	}

	/** We need to import the initial information from the xml files. This is part of final upgrade **/
	public function step_135()
	{
		vB_Api::clearCache();
		vB_Library::clearCache();
		$this->final_create_channelroutes();
	}

	/** We need to import the initial information from the xml files. This is part of final upgrade **/
	public function step_136()
	{
		$this->final_add_noderoutes();
	}

	/** Convert any vB3 API Settings to vbulletin product **/
	public function step_137()
	{
		$query = "
			UPDATE " . TABLE_PREFIX . "setting
			SET product = 'vbulletin' WHERE product = 'vbapi'
			";

		$this->run_query(sprintf($this->phrase['vbphrase']['update_table'], 'setting'), $query);
	}

	// insert special legacy routes that has to be done before step 139
	public function step_138()
	{
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], 'routenew'));
		$db = vB::getDbAssertor();

		// insert forumhome route
		$c = 'vB5_Route_Legacy_Forumhome';
		$prefix = vB::getDatastore()->getOption('forumhome') . ".php";
		$data = array(
			'prefix' 	=> $prefix,
			'regex'		=> $prefix,
			'class'		=> $c,
			'arguments'	=> serialize(array())
		);
		$data['guid'] = vB_Xml_Export_Route::createGUID($data);
		$db->delete('routenew', array('class' => $c));
		$db->insert('routenew', $data);

		// insert vbcms route if package exist
		if ($packageId = $db->getField('package', array('class' => 'vBCms')))
		{
			$c = 'vB5_Route_Legacy_vBCms';
			$idkey = vB::getDatastore()->getOption('route_requestvar');
			$route = new $c;
			$data = array(
				'prefix'	=> $route->getPrefix(),
				'regex'		=> $route->getRegex(),
				'class'		=> $c,
				'arguments'	=> serialize(array_merge($route->getArguments(), array('requestvar' => $idkey))),
			);
			$data['guid'] = vB_Xml_Export_Route::createGUID($data);
			$db->delete('routenew', array('class' => $c));
			$db->insert('routenew', $data);
		}
	}

	/**
	 * We need to import the initial information from the xml files. This is part of final upgrade.
	 * At this point, any removed setting will be removed from setting table and from datastore
	 */
	public function step_139()
	{
		$this->final_load_settings();
	}

	public function step_140()
	{
		//vblangcode/revision is also added in 503a3 step 6 because we added it here after the fact and
		//we need to make sure it gets added in upgrades that passed this step prior to
		//that happening. Keep them in sync.
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'language', 1, 2),
			'language',
			'vblangcode',
			'varchar',
			array('length' => 12, 'default' => '',)
		);

		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'language', 2, 2),
			'language',
			'revision',
			'smallint',
			self::FIELD_DEFAULTS
		);
	}

	/**
	 * load top-level forums from vb4. These are now channels.
	 */
	public function step_141()
	{
		$forumTypeId = vB_Types::instance()->getContentTypeID('vBForum_Forum');
		vB_Upgrade::createAdminSession();

		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'node'));

		$query =
			"/*** now forums  top level  ***/
			SELECT f.title, f.title_clean, f.description, f.forumid, f.displayorder, f.options,
				IF(isnull(lp.userid), 0, lp.userid) AS lastauthorid,
				IF(isnull(lp.username), '', lp.username) AS lastcontentauthor
			FROM " . TABLE_PREFIX . "forum AS f
				LEFT JOIN " . TABLE_PREFIX . "post AS lp ON lp.postid = f.lastpostid
				LEFT JOIN " . TABLE_PREFIX . "node AS existing ON existing.oldid = f.forumid AND existing.oldcontenttypeid = $forumTypeId
			WHERE f.parentid < 1 AND existing.nodeid IS NULL";
		$needed = $this->db->query_read($query);

		if ($needed)
		{
			$parentid = vB::getDbAssertor()->getField('vBForum:channel', [vB_dB_Query::CONDITIONS_KEY => ['guid' => vB_Channel::DEFAULT_FORUM_PARENT]]);

			$channelLib = vB_Library::instance('content_channel');
			while($channel = $this->db->fetch_array($needed))
			{
				$channel['oldid'] = $channel['forumid'];
				$channel['oldcontenttypeid'] = $forumTypeId;
				$channel['parentid'] = $parentid;
				$channel['htmltitle'] = $channel['title_clean'];
				$channel['urlident'] = vB_Library::instance('content_channel')->getUniqueUrlIdent($channel['title']);
				unset($channel['forumid']);
				/* Forum options bit 1 is the forum active flag.
				If the forum is not active, we set its display order to zero.
				This is a necessary fudge atm because in vB5 there is no way to set/reset the active status,
				but the forum should be hidden on upgrade as non active forums did not display in vB3 or vB4 */
				if (($channel['options'] & 1) == 0)
				{
					$channel['displayorder'] = 0;
				}
				$response = $channelLib->add($channel, ['skipNotifications' => true, 'skipFloodCheck' => true, 'skipDupCheck' => true]);
				$response = $response['nodeid'];
			}
		}
	}

	/**
	 * load remaining forums from vb4. These are also channels.
	 *
	 */
	public function step_142($data = [])
	{
		//first we need to channel content type id's.
		$forumTypeId =  vB_Types::instance()->getContentTypeID('vBForum_Forum');

		$process = 200;
		$startat = intval($data['startat'] ?? 0);
		$checkArray = $this->db->query_first("
			SELECT f.forumid
			FROM " . TABLE_PREFIX . "forum AS f
				LEFT JOIN " . TABLE_PREFIX . "node AS existing ON existing.oldid = f.forumid AND existing.oldcontenttypeid = $forumTypeId
				JOIN " . TABLE_PREFIX . "node AS node ON node.oldid = f.parentid AND node.oldcontenttypeid = $forumTypeId
			WHERE f.parentid > 0 AND existing.nodeid IS NULL LIMIT 1
		");

		if (empty($checkArray) AND !$startat)
		{
			$this->skip_message();
			return;
		}
		else if (empty($checkArray))
		{
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			return;
		}
		else if (!$startat)
		{
			$this->show_message(sprintf($this->phrase['version']['500a1']['importing_x_records'], 'forum'));
			$this->show_message(sprintf($this->phrase['core']['processing_records_x'], $process));
			return array('startat' => 1); // Go back and actually process
		}

		$query = "/*** and forums below root ***/
		SELECT node.nodeid AS parentid, f.title, f.title_clean, f.description, f.forumid, f.displayorder, f.options,
		IF(isnull(lp.userid),0,lp.userid) AS lastauthorid,
		IF(isnull(lp.username),'',lp.username) AS lastcontentauthor
		FROM " . TABLE_PREFIX . "forum AS f
		LEFT JOIN " . TABLE_PREFIX . "post AS lp ON lp.postid = f.lastpostid
		LEFT JOIN " . TABLE_PREFIX . "node AS existing ON existing.oldid = f.forumid AND existing.oldcontenttypeid = $forumTypeId
		JOIN " . TABLE_PREFIX . "node AS node ON node.oldid = f.parentid AND node.oldcontenttypeid = $forumTypeId
		WHERE f.parentid > 0 AND existing.nodeid IS NULL
		LIMIT $process";
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'node'));
		$needed = $this->db->query_read($query);

		if (!empty($needed))
		{
			vB_Upgrade::createAdminSession();
			$channelLib = vB_Library::instance('content_channel');
			while($channel =  $this->db->fetch_array($needed))
			{
				$channel['oldid'] = $channel['forumid'];
				$channel['oldcontenttypeid'] = $forumTypeId;
				$channel['htmltitle'] = $channel['title_clean'];
				$channel['urlident'] = vB_Library::instance('content_channel')->getUniqueUrlIdent($channel['title']);
				unset($channel['forumid']);
				/* See explanation in previous step. */
				if (($channel['options'] & 1) == 0)
				{
					$channel['displayorder'] = 0;
				}
				$channelLib->add($channel, array('skipNotifications' => true, 'skipFloodCheck' => true, 'skipDupCheck' => true));
			}
		}

		$this->show_message(sprintf($this->phrase['core']['processed_records_x'], $process));

		return array('startat' => ($startat + 1));
	}

	public function step_143()
	{
		/*
		This step removes duff thread records that have no first postid.
		These threads have no posts, so adding them would be pointless and cause issues down the line.
		Exclude the threads with open == 10 which are thread redirects
		*/
		$query = "
			DELETE FROM " . TABLE_PREFIX . "thread
			WHERE firstpostid = 0 AND open <> 10
		";

		$this->run_query(
			sprintf($this->phrase['vbphrase']['update_table'], 'thread'), $query
		);
	}

	/** Make sure we have attach type**/
	public function step_144()
	{
		$contenttype = $this->db->query_first("
			SELECT contenttypeid FROM " . TABLE_PREFIX . "contenttype
			WHERE class = 'Attach'");

		if (empty($contenttype['contenttypeid']))
		{
			$this->run_query(
			sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'contenttype'),
			"INSERT INTO " . TABLE_PREFIX . "contenttype(class,
			packageid,	canplace,	cansearch,	cantag,	canattach,	isaggregator)
			SELECT 'Attach', packageid, '0','1', '0', '0', '0' FROM " . TABLE_PREFIX . "package where class = 'vBForum';");

		}
		else
		{
			$this->skip_message();
		}
	}

	//Now we can import threads, which come to vB5 as starters
	public function step_145($data = NULL)
	{
		$assertor = vB::getDbAssertor();

		$types = vB_Types::instance();
		$forumTypeId = $types->getContentTypeID('vBForum_Forum');
		$threadTypeId = $types->getContentTypeID('vBForum_Thread');
		$textTypeId = $types->getContentTypeID('vBForum_Text');

		if (empty($data['startat']))
		{
			$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'node'));
			$types->reloadTypes();

			//reprocessing a range we've already processed is problematic.  We'll most likely get key errors below.
			//We should probably fix the queries to avoid that -- especially since if we're interruptted we'll likely
			//get bad datas bacause the batch didn't fully process and we'll skip it on restart.  But that's been a
			//problem for some while and fixing it without causing performance problems isn't obvious.
			$maxvB5 = $assertor->getRow('vBInstall:getMaxOldidForOldContent', ['oldcontenttypeid' => $threadTypeId]);
			// the query below uses th.threadid >= startat, so we need +1 to avoid re-importing the last max.
			$data['startat'] = intval($maxvB5['maxid']) + 1;
		}

		$callback = function($startat, $nextid) use ($assertor, $forumTypeId, $threadTypeId, $textTypeId)
		{
			/**
			 * 	Thread starters. We need to insert the node records, text records, and closure records
			 *
			 *	visible = 0 means unapproved/moderated
			 *	visible = 1 normal/visible
			 *	visible = 2 deleted.
			 **/
			$query = "
				INSERT INTO " . TABLE_PREFIX . "node(contenttypeid, parentid, routeid, title, htmltitle, userid, authorname,
					oldid, oldcontenttypeid, created, ipaddress,
					starter, inlist,
					publishdate,
					unpublishdate,
					showpublished,
					showopen,
					approved,
					showapproved,
					textcount, totalcount, textunpubcount, totalunpubcount, lastcontent,
					lastcontentauthor, lastauthorid, prefixid, iconid, sticky,
					deleteuserid, deletereason,
					open)
				SELECT $textTypeId, node.nodeid, node.routeid, th.title, th.title, th.postuserid, th.postusername,
					th.threadid, $threadTypeId, th.dateline, p.ipaddress,
					1, 1,
					th.dateline,
					(CASE WHEN th.visible < 2 THEN 0 ELSE 1 END),
					(CASE WHEN th.visible < 2 THEN 1 ELSE 0 END),
					th.open,
					(CASE th.visible WHEN 0 THEN 0 ELSE 1 END),
					(CASE th.visible WHEN 0 THEN 0 ELSE 1 END),
					th.replycount,th.replycount, (th.hiddencount + th.deletedcount), (th.hiddencount + th.deletedcount), th.lastpost,
					lp.username, lp.userid, th.prefixid, th.iconid, th.sticky,
					dl.userid, dl.reason,
					th.open
				FROM " . TABLE_PREFIX . "thread AS th
					INNER JOIN " . TABLE_PREFIX . "post AS p ON p.postid = th.firstpostid
					INNER JOIN " . TABLE_PREFIX . "node AS node ON node.oldid = th.forumid AND node.oldcontenttypeid = $forumTypeId
					LEFT JOIN " . TABLE_PREFIX . "post AS lp ON lp.postid = th.lastpostid
					LEFT JOIN " . TABLE_PREFIX . "deletionlog AS dl ON dl.primaryid = th.threadid AND dl.type = 'thread'
				WHERE th.threadid >= $startat AND th.threadid < $nextid
				ORDER BY th.threadid
			";
			$result = $this->db->query_write($query);

			//read the new nodes for processing
			$query = "SELECT nodeid, title, oldid
				FROM " . TABLE_PREFIX . "node
				WHERE oldcontenttypeid = $threadTypeId
				AND oldid >= $startat AND oldid < $nextid
			";

			$nodes = $this->db->query_read($query);
			$records = $this->db->num_rows($nodes);

			// Only bother with the rest of the processing if we actually added some more
			// nodes, otherwise just move on
			if ($records)
			{
				$sql = '';
				while ($node = $this->db->fetch_array($nodes))
				{
					$ident = vB_String::getUrlIdent($node['title']);
					$sql .= "WHEN " . intval($node['nodeid']) . " THEN '" . $this->db->escape_string($ident) . "' \n";
				}

				//Set the urlident values
				$query = "
					UPDATE " . TABLE_PREFIX . "node AS node
					SET urlident = CASE nodeid
						$sql
						ELSE urlident END
					WHERE node.oldcontenttypeid = $threadTypeId AND node.oldid >= $startat AND node.oldid < $nextid
				";
				$this->db->query_write($query);

				//Now fix the starter
				$query = "
					UPDATE " . TABLE_PREFIX . "node AS node
					SET starter = nodeid
					WHERE oldcontenttypeid = $threadTypeId
						AND oldid >= $startat AND oldid < $nextid
				";
				$this->db->query_write($query);

				//Now populate the text table
				$query = "
					INSERT INTO " . TABLE_PREFIX . "text(nodeid, rawtext)
					SELECT node.nodeid, p.pagetext AS rawtext
					FROM " . TABLE_PREFIX . "thread AS th
						INNER JOIN " . TABLE_PREFIX . "post AS p ON p.postid = th.firstpostid
						INNER JOIN " . TABLE_PREFIX . "node AS node ON node.oldid = th.threadid AND node.oldcontenttypeid = $threadTypeId
					WHERE th.threadid >= $startat AND th.threadid < $nextid
				";
				$this->db->query_write($query);

				$params = [
					'oldcontenttypeid' => $threadTypeId,
					'startat' => $startat,
					'nextid' => $nextid,
				];

				$assertor->assertQuery('vBInstall:createClosureSelfOldIdRange', $params);
				$assertor->assertQuery('vBInstall:createClosureParentsOldIdRange', $params);
				$assertor->assertQuery('vBInstall:updateChannelRoutes2', $params);
			}
		};



		$batchsize = $this->getBatchSize('xsmall', __FUNCTION__);
		return $this->updateByIdWalk($data, $batchsize, 'vBInstall:getMaxThreadid', 'vBInstall:thread', 'threadid', $callback);
	}

	//private function addSkippedThreadNodes()
	public function step_146($data = NULL)
	{
		// For reasons that are currently unknown to me, there exist partially upgraded vB4 databases where some threads got skipped
		// in the initial iteration of this step. This means that the final import is missing several threads, and critically, this
		// can unrecoverably block the upgrade if any of those happen to be polls, because this means poll.nodeid can be left null for
		// some records, and those cause a key collision when the nodeid column is turned into a unique index in step 151, and those
		// threads cannot be imported when running this step again unless they just happen to be at the very end of the thread records.
		// From running the above queries manually on affected databases, they *should* be captured by the import query so I'm assuming
		// that there were problems with older iterations of this upgrade step, and that this is an edge case that only occurs with
		// those partially upgraded databases. However, it seems like it's frequent enough that we should try to handle it.
		// Here, we try to detect such a case after the standard updateByIdWalk() is finished (i.e. we've already imported the max threadid)
		// and import the missing ones. We do it here so that
		if (empty($data['startat']))
		{
			$this->show_message(sprintf($this->phrase['version']['500a1']['checking_skipped_x'], 'threads'));
		}

		// Copied and modified from step_145. Done this way instead of refactoring the function in step_145 to handle both as to
		// minimize regression risk.
		// We still have not identified whether this issue still occurs, and how it occurs, so as far as we're aware this function
		// should not be hit in normal upgrades.


		$assertor = vB::getDbAssertor();
		$types = vB_Types::instance();
		$forumTypeId = $types->getContentTypeID('vBForum_Forum');
		$threadTypeId = $types->getContentTypeID('vBForum_Thread');
		$textTypeId = $types->getContentTypeID('vBForum_Text');

		// oldcontenttypeid_poll note:
		// Step 149 below will convert imported threads associated polls to have contenttypeid = {Poll typeid} & oldcontenttypeid = 9011
		// If we happened to hit that already (or another, much later step that do similar things), avoid re-importing them.
		$threadids = $assertor->getColumn('vBInstall:getMissingThreadids', 'threadid', [
			'forumtypeid' => $forumTypeId,
			'threadtypeid' => $threadTypeId,
			'oldcontenttypeid_poll' => 9011,
		]);

		$batchsize = $this->getBatchSize('xsmall', __FUNCTION__);
		$totalcount = count($threadids);

		if (empty($threadids))
		{
			$this->show_message(sprintf($this->phrase['core']['process_done']), true);
			return;
		}

		$threadids = array_slice($threadids, 0, $batchsize);
		// unless the DB is severely mucked with, the threadids should be ints. But let's be sure.
		$threadids = array_map('intval', $threadids);
		$inSQL = implode(',', $threadids);
		$thiscount = count($threadids);
		$remainder = max($totalcount - $thiscount, 0);

		// Note, this has only been tested on a DB with ~100 skipped threads.
		$this->show_message(sprintf($this->phrase['core']['processed_records_x_y_remaining'], $thiscount, $remainder));

		/**
		 * 	Thread starters. We need to insert the node records, text records, and closure records
		 *
		 *	visible = 0 means unapproved/moderated
		 *	visible = 1 normal/visible
		 *	visible = 2 deleted.
		 **/
		$query = "
			INSERT INTO " . TABLE_PREFIX . "node(contenttypeid, parentid, routeid, title, htmltitle, userid, authorname,
				oldid, oldcontenttypeid, created, ipaddress,
				starter, inlist,
				publishdate,
				unpublishdate,
				showpublished,
				showopen,
				approved,
				showapproved,
				textcount, totalcount, textunpubcount, totalunpubcount, lastcontent,
				lastcontentauthor, lastauthorid, prefixid, iconid, sticky,
				deleteuserid, deletereason,
				open)
			SELECT $textTypeId, node.nodeid, node.routeid, th.title, th.title, th.postuserid, th.postusername,
				th.threadid, $threadTypeId, th.dateline, p.ipaddress,
				1, 1,
				th.dateline,
				(CASE WHEN th.visible < 2 THEN 0 ELSE 1 END),
				(CASE WHEN th.visible < 2 THEN 1 ELSE 0 END),
				th.open,
				(CASE th.visible WHEN 0 THEN 0 ELSE 1 END),
				(CASE th.visible WHEN 0 THEN 0 ELSE 1 END),
				th.replycount,th.replycount, (th.hiddencount + th.deletedcount), (th.hiddencount + th.deletedcount), th.lastpost,
				lp.username, lp.userid, th.prefixid, th.iconid, th.sticky,
				dl.userid, dl.reason,
				th.open
			FROM " . TABLE_PREFIX . "thread AS th
				INNER JOIN " . TABLE_PREFIX . "post AS p ON p.postid = th.firstpostid
				INNER JOIN " . TABLE_PREFIX . "node AS node ON node.oldid = th.forumid AND node.oldcontenttypeid = $forumTypeId
				LEFT JOIN " . TABLE_PREFIX . "post AS lp ON lp.postid = th.lastpostid
				LEFT JOIN " . TABLE_PREFIX . "deletionlog AS dl ON dl.primaryid = th.threadid AND dl.type = 'thread'
			WHERE th.threadid IN ($inSQL)
			ORDER BY th.threadid
		";
		$result = $this->db->query_write($query);

		//read the new nodes for processing
		$nodes = $assertor->getRows('vBForum:node', [
			vB_dB_Query::COLUMNS_KEY => ['nodeid', 'title', 'oldid'],
			vB_dB_Query::CONDITIONS_KEY => [
				'oldid' => $threadids,
				'oldcontenttypeid' => $threadTypeId,
			]
		]);

		// Only bother with the rest of the processing if we actually added some more
		// nodes, otherwise just move on
		if ($nodes)
		{
			$sql = '';
			foreach ($nodes AS $node)
			{
				$ident = vB_String::getUrlIdent($node['title']);
				$sql .= "WHEN " . intval($node['nodeid']) . " THEN '" . $this->db->escape_string($ident) . "' \n";
			}

			//Set the urlident values
			$query = "
				UPDATE " . TABLE_PREFIX . "node AS node
				SET urlident = CASE nodeid
					$sql
					ELSE urlident END
				WHERE node.oldcontenttypeid = $threadTypeId AND node.oldid IN ($inSQL)
			";
			$this->db->query_write($query);

			//Now fix the starter
			$query = "
				UPDATE " . TABLE_PREFIX . "node
				SET starter = nodeid
				WHERE oldcontenttypeid = $threadTypeId
					AND oldid IN ($inSQL)
			";
			$this->db->query_write($query);

			//Now populate the text table
			$query = "
				INSERT INTO " . TABLE_PREFIX . "text(nodeid, rawtext)
				SELECT node.nodeid, p.pagetext AS rawtext
				FROM " . TABLE_PREFIX . "thread AS th
					INNER JOIN " . TABLE_PREFIX . "post AS p ON p.postid = th.firstpostid
					INNER JOIN " . TABLE_PREFIX . "node AS node ON node.oldid = th.threadid AND node.oldcontenttypeid = $threadTypeId
				WHERE th.threadid IN ($inSQL)
			";
			$this->db->query_write($query);

			$params = [
				'oldcontenttypeids' => [$threadTypeId, 9011],
				'oldids' => $threadids,
			];

			$assertor->assertQuery('vBInstall:createClosureSelfOldIdIn', $params);
			$assertor->assertQuery('vBInstall:createClosureParentsOldIdIn', $params);
			$assertor->assertQuery('vBInstall:updateChannelRoutesOldIdIn', $params);
		}

		// empty(startat) signifies the end, so this is just to trick the system to loop while not
		// doing a range batch.
		return ['startat' => 1,];
	}

	//Now non-starter posts, which come in as responses
	public function step_147($data = [])
	{
		$threadTypeId =  vB_Types::instance()->getContentTypeID('vBForum_Thread');
		$postTypeId =  vB_Types::instance()->getContentTypeID('vBForum_Post');
		$process = 6000;
		$startat = intval($data['startat'] ?? 0);

		if (!$startat)
		{
			$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'node'));
		}

		//note that data does not handle arbitrary parameters -- even though a large number of steps
		//assume that it does.  The maxvB4 value will never be passed back to us.  Leaving this
		//in place because it doesn't change for each call to the step and we should eventually fix this
		//so it works.
		if (!empty($data['maxvB4']))
		{
			$maxvB4 = $data['maxvB4'];
		}
		else
		{
			$maxImportedThread = $this->db->query_first("SELECT MAX(oldid) AS maxid FROM " . TABLE_PREFIX . "node WHERE oldcontenttypeid = $threadTypeId");
			$maxImportedThread = $maxImportedThread['maxid'];
			//If we don't have any threads, we're done.
			if (intval($maxImportedThread) < 1)
			{
				$this->skip_message();
				return;
			}

			//this is an alternate way to get the max post id.  It relies on the fact that
			//a) The first item returned based on that sort *has* to be the max
			//b) The optimizer doesn't need to sort, it will do an index scan on the postid field.
			//c) The p.postid <> t.firstpostid filter won't filter out a huge swath of records so
			//	the record we return will be towards the beginning of the scan
			//
			//This reduces the number of rows from post that we actually have to join to the thread table.
			//The query is faster than the old max(p.postid) query that we used -- especially on MYISAM
			//tables which we still encounter when upgrading old dbs.
			$query = "
				SELECT p.postid AS maxid
				FROM " . TABLE_PREFIX . "post AS p
				INNER JOIN " . TABLE_PREFIX . "thread AS t ON (t.threadid = p.threadid)
				WHERE p.threadid <= $maxImportedThread AND p.postid <> t.firstpostid
				ORDER By p.postid DESC
				LIMIT 1
			";

			$maxvB4 = $this->db->query_first($query);
			$maxvB4 = intval($maxvB4['maxid'] ?? 0);
			//If we don't have any posts, we're done.
			if ($maxvB4 < 1)
			{
				$this->skip_message();
				return;
			}
		}

		$maxvB5 = $this->db->query_first("SELECT MAX(oldid) AS maxid FROM " . TABLE_PREFIX . "node WHERE oldcontenttypeid = $postTypeId");
		if (!empty($maxvB5) AND !empty($maxvB5['maxid']))
		{
			$maxvB5 = $maxvB5['maxid'];
		}
		else
		{
			$maxvB5 = 0;
		}

		$maxvB5 = max($startat, $maxvB5);
		if (($maxvB4 <= $maxvB5) AND !$startat)
		{
			$this->skip_message();
			return;
		}
		else if ($maxvB4 <= $maxvB5)
		{
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			return;
		}

		$textTypeId = vB_Types::instance()->getContentTypeID('vBForum_Text');

		/*** posts ***/
		$query = "
		INSERT INTO " . TABLE_PREFIX . "node(contenttypeid, parentid, routeid, title,  htmltitle,
			oldid, oldcontenttypeid, created, ipaddress, starter, inlist, userid, authorname,
			publishdate,
			unpublishdate,
			showpublished,
			showopen,
			approved,
			showapproved,
			iconid,
			deleteuserid, deletereason
		)
		SELECT $textTypeId, node.nodeid, node.routeid, p.title, p.title,
			p.postid, $postTypeId, p.dateline, p.ipaddress, node.nodeid, 1, p.userid, p.username,
			p.dateline,
			(CASE WHEN p.visible < 2 THEN 0 ELSE 1 END),
			(CASE WHEN t.visible < 2 AND p.visible < 2 THEN 1 ELSE 0 END),
			t.open,
			(CASE WHEN p.visible = 0 THEN 0 ELSE 1 END),
			(CASE WHEN t.visible = 0 OR p.visible = 0 THEN 0 ELSE 1 END),
			p.iconid,
			dl.userid, dl.reason
		FROM " . TABLE_PREFIX . "post AS p
		INNER JOIN " . TABLE_PREFIX . "thread AS t ON (p.threadid = t.threadid)
		INNER JOIN " . TABLE_PREFIX . "node AS node ON node.oldid = p.threadid AND node.oldcontenttypeid = $threadTypeId
		LEFT JOIN " . TABLE_PREFIX . "deletionlog AS dl ON dl.primaryid = p.postid AND dl.type = 'post'
		WHERE p.postid > $maxvB5  AND p.postid < ($maxvB5 + $process) AND t.firstpostid <> p.postid
		ORDER BY p.postid";

		$this->db->query_write($query);

		//Now populate the text table
		if ($this->field_exists('post', 'htmlstate'))
		{
			$query = "INSERT INTO " . TABLE_PREFIX . "text(nodeid, rawtext, htmlstate)
			SELECT node.nodeid, IF(p.title <> '', CONCAT(p.title, '\r\n\r\n', p.pagetext) , p.pagetext) AS rawtext, htmlstate \n";
		}
		else
		{
			$query = "INSERT INTO " . TABLE_PREFIX . "text(nodeid, rawtext)
			SELECT node.nodeid, IF(p.title <> '', CONCAT(p.title, '\r\n\r\n', p.pagetext) , p.pagetext) AS rawtext\n ";
		}
		$query .= "
		FROM " . TABLE_PREFIX . "post AS p
		INNER JOIN " . TABLE_PREFIX . "node AS node ON node.oldid = p.postid AND node.oldcontenttypeid = $postTypeId
			WHERE p.postid > $maxvB5  AND p.postid < ($maxvB5 + $process)
		";
		$this->db->query_write($query);

		//Now the closure record for the node
		$query = "INSERT INTO " . TABLE_PREFIX . "closure (parent, child, depth)
			SELECT node.nodeid, node.nodeid, 0
			FROM " . TABLE_PREFIX . "node AS node
			WHERE node.oldcontenttypeid = $postTypeId AND node.oldid > $maxvB5  AND node.oldid < ($maxvB5 + $process)";
		$this->db->query_write($query);

		//Add the closure records to root
		$query = "INSERT INTO " . TABLE_PREFIX . "closure (parent, child, depth)
			SELECT parent.parent, node.nodeid, parent.depth + 1
			FROM " . TABLE_PREFIX . "node AS node
			INNER JOIN " . TABLE_PREFIX . "closure AS parent ON parent.child = node.parentid
			WHERE node.oldcontenttypeid = $postTypeId AND node.oldid > $maxvB5 AND node.oldid < ($maxvB5 + $process)";
		$this->db->query_write($query);
		$this->show_message(sprintf($this->phrase['core']['processed_records_x_y_z'], $maxvB5 + 1, min($maxvB5 + $process - 1, $maxvB4), $maxvB4), true);


		return array('startat' => ($maxvB5 + $process - 1), 'maxvB4' => $maxvB4);
	}

	public function step_148($data = [])
	{
		// See the note in 145. IF any threads got skipped, then their replies surely got skipped. We need to import those separately
		// as it's not guaranteed that the current batch window will capture those missing replies.

		if (empty($data['startat']))
		{
			$this->show_message(sprintf($this->phrase['version']['500a1']['checking_skipped_x'], 'replies'));
		}
		return $this->handleSkippedThreadReplies('thread');
	}

	public function step_149($data = [])
	{
		// Almost the same as step_148. Look for missing replies, but this handles the edge case where a poll's reply(s) was skipped,
		// but the imported poll-starter was already converted to have oldcontenttypeid = 9011 (POLL OLDTYPEID) in the worst case scenario.
		// I think this would only happen for replies that were skipped on their own for some reason, NOT replies that were skipped because their
		// threads were skiped, unlike step 148..
		// This section was written "just in case" to try to cover as much abnormalities as possible, but I have not encountered this specific case.

		if (empty($data['startat']))
		{
			$this->show_message(sprintf($this->phrase['version']['500a1']['checking_skipped_x'], 'poll-replies'));
		}
		return $this->handleSkippedThreadReplies('poll');
	}

	private function handleSkippedThreadReplies($threadtype = 'thread')
	{
		$types = vB_Types::instance();
		$threadTypeId =  $types->getContentTypeID('vBForum_Thread');
		$postTypeId =  $types->getContentTypeID('vBForum_Post');
		$textTypeId = $types->getContentTypeID('vBForum_Text');
		$oldcontenttypeid_poll = 9011;
		$assertor = vB::getDbAssertor();

		if ($threadtype == 'thread')
		{
			$postids = $assertor->getColumn('vBInstall:getMissingThreadReplies', 'postid', [
				'posttypeid' => $postTypeId,
				'threadtypeid' => $threadTypeId,
			]);

			if (empty($postids))
			{
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}
		}
		else if ($threadtype == 'poll')
		{
			$postids = $assertor->getColumn('vBInstall:getMissingPollReplies', 'postid', [
				'posttypeid' => $postTypeId,
				'oldcontenttypeid_poll' => $oldcontenttypeid_poll,
			]);

			if (empty($postids))
			{
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}
		}
		else
		{
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			return;
		}

		$batchsize = $this->getBatchSize('xsmall', __FUNCTION__);
		$totalcount = count($postids);

		$postids = array_slice($postids, 0, $batchsize);
		// unless the DB is severely mucked with, the postids should be ints. But let's be sure.
		$postids = array_map('intval', $postids);
		$inSQL = implode(',', $postids);
		$thiscount = count($postids);
		$remainder = max($totalcount - $thiscount, 0);

		// Note, this has only been tested on a DB where most of skipped replies happened to be
		// outside of the max-imported range, and only ~3 were within the already processed range.
		// As such, the batchsize is a completely arbitrary guess and may not be the optimal size.
		$this->show_message(sprintf($this->phrase['core']['processed_records_x_y_remaining'], $thiscount, $remainder));

		// Below block is copied from the base step, with the postid range conditions replaced with
		// postid IN(...) conditions.

		/*** posts ***/
		$query = "
		INSERT INTO " . TABLE_PREFIX . "node(contenttypeid, parentid, routeid, title,  htmltitle,
			oldid, oldcontenttypeid, created, ipaddress, starter, inlist, userid, authorname,
			publishdate,
			unpublishdate,
			showpublished,
			showopen,
			approved,
			showapproved,
			iconid,
			deleteuserid, deletereason
		)
		SELECT $textTypeId, node.nodeid, node.routeid, p.title, p.title,
			p.postid, $postTypeId, p.dateline, p.ipaddress, node.nodeid, 1, p.userid, p.username,
			p.dateline,
			(CASE WHEN p.visible < 2 THEN 0 ELSE 1 END),
			(CASE WHEN t.visible < 2 AND p.visible < 2 THEN 1 ELSE 0 END),
			t.open,
			(CASE WHEN p.visible = 0 THEN 0 ELSE 1 END),
			(CASE WHEN t.visible = 0 OR p.visible = 0 THEN 0 ELSE 1 END),
			p.iconid,
			dl.userid, dl.reason
		FROM " . TABLE_PREFIX . "post AS p
		INNER JOIN " . TABLE_PREFIX . "thread AS t ON (p.threadid = t.threadid)
		INNER JOIN " . TABLE_PREFIX . "node AS node ON node.oldid = p.threadid AND node.oldcontenttypeid = $threadTypeId
		LEFT JOIN " . TABLE_PREFIX . "deletionlog AS dl ON dl.primaryid = p.postid AND dl.type = 'post'
		WHERE p.postid IN ($inSQL) AND t.firstpostid <> p.postid
		ORDER BY p.postid";

		$this->db->query_write($query);

		//Now populate the text table
		if ($this->field_exists('post', 'htmlstate'))
		{
			$query = "INSERT INTO " . TABLE_PREFIX . "text(nodeid, rawtext, htmlstate)
			SELECT node.nodeid, IF(p.title <> '', CONCAT(p.title, '\r\n\r\n', p.pagetext) , p.pagetext) AS rawtext, htmlstate \n";
		}
		else
		{
			$query = "INSERT INTO " . TABLE_PREFIX . "text(nodeid, rawtext)
			SELECT node.nodeid, IF(p.title <> '', CONCAT(p.title, '\r\n\r\n', p.pagetext) , p.pagetext) AS rawtext\n ";
		}
		$query .= "
		FROM " . TABLE_PREFIX . "post AS p
		INNER JOIN " . TABLE_PREFIX . "node AS node ON node.oldid = p.postid AND node.oldcontenttypeid = $postTypeId
			WHERE p.postid IN ($inSQL)
		";
		$this->db->query_write($query);

		//Now the closure record for the node
		$query = "INSERT INTO " . TABLE_PREFIX . "closure (parent, child, depth)
			SELECT node.nodeid, node.nodeid, 0
			FROM " . TABLE_PREFIX . "node AS node
			WHERE node.oldcontenttypeid = $postTypeId AND node.oldid IN ($inSQL)";
		$this->db->query_write($query);

		//Add the closure records to root
		$query = "INSERT INTO " . TABLE_PREFIX . "closure (parent, child, depth)
			SELECT parent.parent, node.nodeid, parent.depth + 1
			FROM " . TABLE_PREFIX . "node AS node
			INNER JOIN " . TABLE_PREFIX . "closure AS parent ON parent.child = node.parentid
			WHERE node.oldcontenttypeid = $postTypeId AND node.oldid IN ($inSQL)";
		$this->db->query_write($query);

		return ['startat' => 1,];
	}

	//Now attachments from posts (not starters)
	public function step_150($data = [])
	{
		if ($this->tableExists('attachment') AND $this->tableExists('filedata') AND $this->tableExists('thread') AND $this->tableExists('post'))
		{
			$process = 5000;
			$startat = intval($data['startat'] ?? 0);
			$attachTypeId =  vB_Types::instance()->getContentTypeID('vBForum_Attach');
			$postTypeId =  vB_Types::instance()->getContentTypeID('vBForum_Post');

			if (!$startat)
			{
				$this->show_message(sprintf($this->phrase['version']['500a1']['importing_x'], 'post-attachments'));
			}

			//First see if we need to do something. Maybe we're O.K.
			if (empty($data['maxvB4']))
			{
				$maxvB4 = $this->db->query_first("SELECT MAX(a.attachmentid) AS maxid
					FROM " . TABLE_PREFIX . "attachment AS a
					INNER JOIN " . TABLE_PREFIX . "post AS p ON a.contentid = p.postid
					INNER JOIN " . TABLE_PREFIX . "thread AS th ON p.threadid = th.threadid AND th.firstpostid <> p.postid
					WHERE a.contenttypeid = $postTypeId
				");
				$maxvB4 = $maxvB4['maxid'];

				//If we don't have any attachments, we're done.
				if (intval($maxvB4) < 1)
				{
					$this->skip_message();
					return;
				}
			}
			else
			{
				$maxvB4 = $data['maxvB4'];
			}

			$maxvB5 = $this->db->query_first("SELECT MAX(oldid) AS maxid FROM " . TABLE_PREFIX . "node WHERE oldcontenttypeid = " . vB_Api_ContentType::OLDTYPE_POSTATTACHMENT);
			if (empty($maxvB5) OR empty($maxvB5['maxid']))
			{
				$maxvB5 = 0;
			}
			else
			{
				$maxvB5 = $maxvB5['maxid'];
			}

			$maxvB5 = max($maxvB5, $startat);
			if (($maxvB4 <= $maxvB5) AND !$startat)
			{
				$this->skip_message();
				return;
			}
			else if ($maxvB4 <= $maxvB5)
			{
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}

			/*** first the nodes ***/
				$query = "
			INSERT INTO " . TABLE_PREFIX . "node(
				userid, authorname, contenttypeid, parentid, routeid, title,  htmltitle,
				publishdate, oldid, oldcontenttypeid, created,
				starter, inlist, showpublished, showapproved, showopen
			)
			SELECT a.userid, u.username, $attachTypeId, node.nodeid, node.routeid, '', '',
				a.dateline, a.attachmentid,	" . vB_Api_ContentType::OLDTYPE_POSTATTACHMENT . ", a.dateline,
				node.starter, 0, 1, 1, 1
			FROM " . TABLE_PREFIX . "attachment AS a
			INNER JOIN " . TABLE_PREFIX . "node AS node ON node.oldid = a.contentid AND node.oldcontenttypeid = $postTypeId
			INNER JOIN " . TABLE_PREFIX . "post AS p ON a.contentid = p.postid
			INNER JOIN " . TABLE_PREFIX . "thread AS th ON p.threadid = th.threadid AND th.firstpostid <> p.postid
			LEFT JOIN " . TABLE_PREFIX . "user AS u ON a.userid = u.userid
			WHERE a.attachmentid > $maxvB5 AND a.attachmentid < ($maxvB5 + $process) AND a.contenttypeid = $postTypeId
			ORDER BY a.attachmentid
			LIMIT $process;";
			$this->db->query_write($query);

			//Now populate the attach table
			$query = "
			INSERT INTO ". TABLE_PREFIX . "attach
			(nodeid, filedataid, visible, counter, posthash, filename, caption, reportthreadid, settings)
				SELECT n.nodeid, a.filedataid,
				 CASE WHEN a.state = 'moderation' then 0 else 1 end AS visible, a.counter, a.posthash, a.filename, a.caption, a.reportthreadid, a.settings
			FROM ". TABLE_PREFIX . "attachment AS a INNER JOIN ". TABLE_PREFIX . "node AS n ON n.oldid = a.attachmentid AND n.oldcontenttypeid = " . vB_Api_ContentType::OLDTYPE_POSTATTACHMENT . "
			WHERE a.attachmentid > $maxvB5  AND a.attachmentid < ($maxvB5 + $process) AND a.contenttypeid = $postTypeId;
			";
			$this->db->query_write($query);

			//Now the closure record for the node
			$query = "INSERT INTO " . TABLE_PREFIX . "closure (parent, child, depth)
			SELECT node.nodeid, node.nodeid, 0
			FROM " . TABLE_PREFIX . "node AS node
			WHERE node.oldcontenttypeid = " . vB_Api_ContentType::OLDTYPE_POSTATTACHMENT . " AND node.oldid > $maxvB5 AND node.oldid< ($maxvB5 + $process)";
			$this->db->query_write($query);

			//Add the closure records to root
			$query = "INSERT INTO " . TABLE_PREFIX . "closure (parent, child, depth)
			SELECT parent.parent, node.nodeid, parent.depth + 1
			FROM " . TABLE_PREFIX . "node AS node
			INNER JOIN " . TABLE_PREFIX . "closure AS parent ON parent.child = node.parentid
			WHERE node.oldcontenttypeid = " . vB_Api_ContentType::OLDTYPE_POSTATTACHMENT . " AND node.oldid > $maxvB5 AND node.oldid< ($maxvB5 + $process) ";
			$this->db->query_write($query);

			$this->show_message(sprintf($this->phrase['core']['processed_records_x_y'], $maxvB5 + 1, $maxvB5 + $process - 1));

			return array('startat' => ($maxvB5 + $process - 1), 'maxvB4' => $maxvB4);
		}
	}

	public function step_151($data = [])
	{
		// grab any skipped posts' missing attachments.
		// Note, the thread missing attach is done near the end because for some reason, we import thread attachments later...
		if (empty($data['startat']))
		{
			$this->show_message(sprintf($this->phrase['version']['500a1']['checking_skipped_x'], 'post-attachments'));
		}


		return $this->handleSkippedPostAttachments('post', $data);
	}

	private function handleSkippedPostAttachments($attachtype = 'post', $data = [])
	{
		$types = vB_Types::instance();
		$threadTypeId =  $types->getContentTypeID('vBForum_Thread');
		$postTypeId =  $types->getContentTypeID('vBForum_Post');
		$attachTypeId =  $types->getContentTypeID('vBForum_Attach');
		$oldtypeid = vB_Api_ContentType::OLDTYPE_POSTATTACHMENT;
		$parentOldTypeid = $postTypeId;
		$threadJoin = "INNER JOIN " . TABLE_PREFIX . "thread AS th ON p.threadid = th.threadid AND th.firstpostid <> p.postid";
		$nodeJoin = "INNER JOIN " . TABLE_PREFIX . "node AS node ON node.oldid = a.contentid AND node.oldcontenttypeid = $parentOldTypeid";
		$getMissingQuery = 'vBInstall:getMissingPostAttachmentid';
		if ($attachtype == 'thread')
		{
			$oldtypeid = vB_Api_ContentType::OLDTYPE_THREADATTACHMENT;
			$parentOldTypeid = $threadTypeId;
			$threadJoin = "INNER JOIN " . TABLE_PREFIX . "thread AS th ON a.contentid = th.firstpostid";
			$nodeJoin = "INNER JOIN " . TABLE_PREFIX . "node AS node ON node.oldid = th.threadid AND node.oldcontenttypeid = $parentOldTypeid";
			// Note, this query will actually detect both text-thread (this block) & poll-thread (next block) cases. In order to
			// avoid getting stuck in an infinite loop, we sort by attachmentid ASC, and always skip the last max processed attachmentid
			// even if the insert failed (more specifically, the SELECT subquery of the INSERT SELECT did not fetch anything due to the
			// different expected node data between an imported text-thread & imported poll-thread items)
			$getMissingQuery = 'vBInstall:getMissingThreadAttachmentid';
		}
		else if ($attachtype == 'poll')
		{
			// imported poll threads get its oldcontenttypeid set to OLDTYPE_POLL and its oldid = poll.pollid.
			// This used to be done after the fact in a latter step, but the upgrade also used to add a separate
			// poll node as a reply to a thread node (which is invalid because in vB5, a poll is a starter) then
			// try to fix it after the fact, which caused all kinds of issues.
			// Now, the imported poll-starter node *should* have the final oldid & oldcontenttypeid after step_159()
			// but that also means every time we try to do something against "threads", we also need to mostly
			// duplicate it against "polls".
			$oldtypeid = vB_Api_ContentType::OLDTYPE_THREADATTACHMENT;
			$parentOldTypeid = vB_Api_ContentType::OLDTYPE_POLL;
			$threadJoin = "INNER JOIN `" . TABLE_PREFIX . "thread` AS `th` ON `a`.`contentid` = `th`.`firstpostid` AND `th`.`open` <> 10";
			$threadJoin .= "\n" . "INNER JOIN `". TABLE_PREFIX . "poll` AS `poll` ON (`th`.`pollid` = `poll`.`pollid`)";
			$nodeJoin = "INNER JOIN `" . TABLE_PREFIX . "node` AS `node` ON `node`.`oldid` = `poll`.`pollid` AND `node`.`oldcontenttypeid` = $parentOldTypeid";
			$getMissingQuery = 'vBInstall:getMissingThreadAttachmentid';

		}
		$assertor = vB::getDbAssertor();

		// Note, sort order attachmentid asc is important.
		$attachmentids = $assertor->getColumn($getMissingQuery, 'attachmentid', [
			'posttypeid' => $postTypeId,
			'oldtypeid' => $oldtypeid,
			'lastimportedattachmentid' => $data['startat'] ?? 0,
		]);


		if (empty($attachmentids))
		{
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			return;
		}

		$batchsize = $this->getBatchSize('xsmall', __FUNCTION__);
		$totalcount = count($attachmentids);

		$attachmentids = array_slice($attachmentids, 0, $batchsize);
		// unless the DB is severely mucked with, the postids should be ints. But let's be sure.
		$attachmentids = array_map('intval', $attachmentids);
		$inSQL = implode(',', $attachmentids);
		$thiscount = count($attachmentids);
		$remainder = max($totalcount - $thiscount, 0);
		$first = reset($attachmentids);
		$last = end($attachmentids);

		// Note, this has only been tested on a DB with ~3 skipped replies.
		$this->show_message(sprintf($this->phrase['core']['processed_records_w_between_x_y_z_remaining'], $thiscount, $first, $last, $remainder));



		/*** first the nodes ***/
		$query = "
		INSERT INTO " . TABLE_PREFIX . "node(
			userid, authorname, contenttypeid, parentid, routeid, title,  htmltitle,
			publishdate, oldid, oldcontenttypeid, created,
			starter, inlist, showpublished, showapproved, showopen
		)
		SELECT a.userid, u.username, $attachTypeId, node.nodeid, node.routeid, '', '',
			a.dateline, a.attachmentid,	" . $oldtypeid . ", a.dateline,
			node.starter, 0, 1, 1, 1
		FROM " . TABLE_PREFIX . "attachment AS a
		INNER JOIN " . TABLE_PREFIX . "post AS p ON a.contentid = p.postid
		$threadJoin
		$nodeJoin
		LEFT JOIN " . TABLE_PREFIX . "user AS u ON a.userid = u.userid
		WHERE a.attachmentid IN ($inSQL) AND a.contenttypeid = $postTypeId
		ORDER BY a.attachmentid";


		$this->db->query_write($query);

		//Now populate the attach table
		$query = "
		INSERT INTO ". TABLE_PREFIX . "attach
		(nodeid, filedataid, visible, counter, posthash, filename, caption, reportthreadid, settings)
			SELECT n.nodeid, a.filedataid,
				CASE WHEN a.state = 'moderation' then 0 else 1 end AS visible, a.counter, a.posthash, a.filename, a.caption, a.reportthreadid, a.settings
		FROM ". TABLE_PREFIX . "attachment AS a INNER JOIN ". TABLE_PREFIX . "node AS n ON n.oldid = a.attachmentid AND n.oldcontenttypeid = " . $oldtypeid . "
		WHERE a.attachmentid IN ($inSQL) AND a.contenttypeid = $postTypeId;
		";
		$this->db->query_write($query);

		//Now the closure record for the node
		$query = "INSERT INTO " . TABLE_PREFIX . "closure (parent, child, depth)
		SELECT node.nodeid, node.nodeid, 0
		FROM " . TABLE_PREFIX . "node AS node
		WHERE node.oldcontenttypeid = " . $oldtypeid . " AND node.oldid IN ($inSQL)";
		$this->db->query_write($query);

		//Add the closure records to root
		$query = "INSERT INTO " . TABLE_PREFIX . "closure (parent, child, depth)
		SELECT parent.parent, node.nodeid, parent.depth + 1
		FROM " . TABLE_PREFIX . "node AS node
		INNER JOIN " . TABLE_PREFIX . "closure AS parent ON parent.child = node.parentid
		WHERE node.oldcontenttypeid = " . $oldtypeid . " AND node.oldid IN ($inSQL) ";
		$this->db->query_write($query);


		return ['startat' => $last, ];
	}

	// These are just fillers to just make renumbering all the steps below easier (by 10 instead of by 3)
	// This also means we have a bit of wiggle room if we have to come back and add some more steps though
	// hopefully we won't have to.
	// <TODO>
	public function step_152()
	{
		$this->skip_message();
	}
	public function step_153()
	{
		$this->skip_message();
	}
	public function step_154()
	{
		$this->skip_message();
	}
	public function step_155()
	{
		$this->skip_message();
	}
	public function step_156()
	{
		$this->skip_message();
	}
	public function step_157()
	{
		$this->skip_message();
	}

	public function step_158()
	{
		$timenow = time();
		$query = "
			UPDATE " . TABLE_PREFIX . "node
			SET created = $timenow
			WHERE created IS NULL";

		$this->run_query(
			sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'node'),
			$query);
	}

	/** Insert Poll data into the node table **/
	public function step_159()
	{
		$threadTypeId =  vB_Types::instance()->getContentTypeID('vBForum_Thread');
		if ($this->field_exists('poll', 'pollid'))
		{
			// Create new nodes
			$pollTypeId = vB_Types::instance()->getContentTypeID('vBForum_Poll');

			// This step used to insert a poll node that was a child of the thread node of the thread
			// that was associated with the poll. This is incorrect, and 503rc1 & 510a8 apparently fix
			// the data after the fact. However, there's either been some changes or some specific cases
			// where this data will cause total failure of the upgrade steps, because

			$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'node'));
			$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'poll'));
			$assertor = vB::getDbAssertor();
			$assertor->assertQuery('vBInstall:updateImportedPollThreadsAndNodeids', [
				'threadtypeid' => $threadTypeId,
				'polltypeid' => $pollTypeId,
				'oldcontenttypeid_poll' => 9011,
			]);
		}
		else
		{
			$this->skip_message();
		}
	}

	/** set the nodeid **/
	public function step_160()
	{
		// This step used to update the poll.nodeid column, but step 149 does this now.
		// Now, we'll do a final check and remove any poll records that FAILED to be imported entirely,
		// just so that we don't block the upgrades entirely due to a few corrupted polls.
		// Note that the legacy_poll table SHOULD have the old poll table data if needed.
		if ($this->field_exists('poll', 'pollid') AND $this->field_exists('poll', 'nodeid'))
		{
			$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'poll'));
			$assertor = vB::getDbAssertor();
		//	$assertor->assertQuery('vBInstall:removeFailedToImportPollRecords');
		}
		else
		{
			$this->skip_message();
		}
	}

	/** make nodeid the primary key**/
	public function step_161()
	{
		if ($this->field_exists('poll', 'pollid') AND $this->field_exists('poll', 'nodeid'))
		{
			vB::getDbAssertor()->assertQuery('vBForum:poll', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_DELETE, 'nodeid' => 0));
			$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'poll'));
		}
		else
		{
			$this->skip_message();
		}

		// Add nodeid as primary key
		$polldescr = $this->db->query_first("SHOW COLUMNS FROM " . TABLE_PREFIX . "poll LIKE 'nodeid'");

		if (!empty($polldescr['Key']) AND ($polldescr['Key'] == 'PRI'))
		{
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'poll', 1, 1),
				"ALTER TABLE " . TABLE_PREFIX . "poll DROP PRIMARY KEY, ADD PRIMARY KEY(nodeid)",
				self::MYSQL_ERROR_DROP_KEY_COLUMN_MISSING
			);
		}
		else
		{
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'poll', 1, 1),
				"ALTER TABLE " . TABLE_PREFIX . "poll ADD PRIMARY KEY(nodeid)"
			);
		}
	}

	/** set the polloptions nodeid **/
	public function step_162()
	{
		if ($this->field_exists('poll', 'pollid'))
		{
			$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'polloptions'));

			// Insert polloptions
			$polls = $this->db->query_read("
				SELECT poll.*, pollnode.nodeid
				FROM " . TABLE_PREFIX . "poll AS poll
				JOIN " . TABLE_PREFIX . "node AS pollnode ON (poll.pollid = pollnode.oldid AND pollnode.oldcontenttypeid = 9011)
			");
			while ($poll = $this->db->fetch_array($polls))
			{
				// Poll options
				$polloptions = explode('|||', $poll['options']);
				$optionstosave = array();
				foreach ($polloptions as $k => $polloption)
				{
					$this->db->query_write("
						INSERT INTO " . TABLE_PREFIX . "polloption
						(nodeid, title)
						VALUES
						($poll[nodeid], '" . $this->db->escape_string(trim($polloption)) . "')
					");

					$polloptionid = $this->db->insert_id();

					// Update nodeid and polloptionid
					$v = $k + 1;
					$this->db->query_write("
						UPDATE " . TABLE_PREFIX . "pollvote
						SET nodeid = $poll[nodeid], polloptionid = $polloptionid
						WHERE voteoption = $v AND pollid = $poll[pollid] "
					);

					// Get a list of votes
					$pollvotes = $this->db->query_read("
						SELECT * FROM " . TABLE_PREFIX . "pollvote AS pollvote
						WHERE polloptionid = $polloptionid
					");

					$votecount = 0;
					$voters = array();
					while ($pollvote = $this->db->fetch_array($pollvotes))
					{
						$votecount++;
						$voters[] = $pollvote['userid'];
					}

					// Update polloption
					$this->db->query_write("
						UPDATE " . TABLE_PREFIX . "polloption
						SET
							voters = '" . $this->db->escape_string(serialize($voters)) . "',
							votes = $votecount
						WHERE polloptionid = $polloptionid
					");

					$optionstosave[$polloptionid] = array(
						'polloptionid' => $polloptionid,
						'nodeid'       => $poll['nodeid'],
						'title'        => trim($polloption),
						'votes'        => $votecount,
						'voters'       => $voters,
					);
				}

				// Total votes for this poll

				$result = $this->db->query_read("
					SELECT COUNT(*) AS count
					FROM " . TABLE_PREFIX . "pollvote as pollvote
					WHERE pollvote.nodeid = $poll[nodeid]
				");

				$votes = $this->db->fetch_array($result);
				$votes = $votes['count'] ?? 0;

				// Update poll cache
				$this->db->query_write("
					UPDATE " . TABLE_PREFIX . "poll
					SET
						options = '" . $this->db->escape_string(serialize($optionstosave)) . "',
						votes = $votes
					WHERE nodeid = $poll[nodeid]
				");
			}
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_163()
	{
		// Note: The rest of the infractions importing is handled in the 501a2 script
		$forumTypeId =  vB_Types::instance()->getContentTypeID('vBForum_Forum');
		$threadTypeId =  vB_Types::instance()->getContentTypeID('vBForum_Thread');
		$this->run_query(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . "infraction"),
		"UPDATE " . TABLE_PREFIX  . "infraction AS i INNER JOIN " . TABLE_PREFIX  . "node AS p ON p.oldid = i.postid AND p.oldcontenttypeid = $forumTypeId
			LEFT JOIN " . TABLE_PREFIX  . "node AS t ON t.oldid = i.threadid AND t.oldcontenttypeid = $threadTypeId
			SET i.nodeid = p.nodeid, i.channelid = t.nodeid;");
	}

	/**
	 * Fixing the contenttype table
	 */
	public function step_164()
	{
		$not_searchable = array(
			"Post",
			"Thread",
			"Forum",
			"Announcement",
			"SocialGroupMessage",
			"SocialGroupDiscussion",
			"SocialGroup",
			"Album",
			"Picture",
			"PictureComment",
			"VisitorMessage",
			"User",
			"Event",
			"Calendar",
			"BlogEntry",
			"Channel",
			"BlogComment"
		);
		$searchable = array(
			"Text",
			"Attach",
			"Poll",
			"Photo",
			"Gallery"
		);
		$this->run_query(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . "contenttype"),
		"UPDATE " . TABLE_PREFIX  . "contenttype SET cansearch = '0' WHERE class IN (\"" . implode('","',$not_searchable) . "\");");

		$this->run_query(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . "contenttype"),
		"UPDATE " . TABLE_PREFIX  . "contenttype SET cansearch = '1' WHERE class IN (\"" . implode('","',$searchable) . "\") AND packageid = 1;");
	}

	/***	Set canplace properly*/
	public function step_165()
	{
		$this->run_query(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'contenttype'),
			"UPDATE " . TABLE_PREFIX . "contenttype SET canplace = '0' where NOT class IN ('Text','Channel','Poll','Gallery')");
		$this->run_query(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'contenttype'),
			"UPDATE " . TABLE_PREFIX . "contenttype SET canplace = '1' where class IN ('Text','Channel','Poll','Gallery')");
	}

	/** Insert default data in site table */
	public function step_166()
	{
		$siteRecords = $this->db->query_first("
			SELECT siteid FROM " . TABLE_PREFIX . "site
		");

		if (empty($siteRecords) OR empty($siteRecords['siteid']))
		{
			$navbars = get_default_navbars();
			$headernavbar = serialize($navbars['header']);
			$footernavbar = serialize($navbars['footer']);

			$this->run_query(
				sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'site'),
				"
				INSERT INTO " . TABLE_PREFIX . "site
				(title, headernavbar, footernavbar)
				VALUES
				('Default Site','$headernavbar','$footernavbar');
			"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/** Adding sitebuild perm to usergroup.adminpermissions */
	public function step_167()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'usergroup'),
			"
				UPDATE " . TABLE_PREFIX . "usergroup
				SET adminpermissions = (3 | 16777216)
				WHERE systemgroupid = 6;
			"
		);
	}

	public function step_168()
	{
		$canjoin = $this->registry->bf_ugp_forumpermissions['canjoin'];
		$cancreateblog = $this->registry->bf_ugp_forumpermissions['cancreateblog'];

		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'usergroup'));

		//set the canjoin and cancreate blog permissions to their defaults for existing system groups to
		//match what they would be on a new install.  New usergroups that we'll create in other steps
		//will automatically get the correct values.
		vB::getDbAssertor()->update('vBForum:usergroup',
			array(
				vB_dB_Query::BITFIELDS_KEY => array (
					array('field' => 'forumpermissions', 'operator' => vB_dB_Query::BITFIELDS_SET, 'value' => $canjoin),
					array('field' => 'forumpermissions', 'operator' => vB_dB_Query::BITFIELDS_SET, 'value' => $cancreateblog),
				)
			),
			array('systemgroupid' => array(2, 5, 6, 7))
		);
	}

	/**
	 * vB4 CMS Data import is done in 510a1. Steps 168-170 are not used any longer, so they have been removed.
	 * Note, these used to be 159 & 160, but have been renumbered to fit in some extra steps above to fix some
	 * poll/thread import issues.
	 * This step used to import the CMS sections. See 510a1 step_9 for the updated version.
	 */
	public function step_169()
	{
		$this->skip_message();
	}

	/**
	 * vB4 CMS Data import is done in 510a1. Steps 168-170 are not used any longer, so they have been removed.
	 * This step used to import the CMS articles. See 510a1 step_10 for the updated version.
	 */
	public function step_170()
	{
		$this->skip_message();
	}

	// We have step_150() & step_176(), which allegedly are supposed to import attachments for posts & threads respectively.
	// I am not sure attachments step_171() and step_172() are supposed to be importing `node` & `attach` records from `attachment`
	// outside of that, and the commit history does not reveal much. I don't think they would be for blog entry or cms, since the
	// attachment import depends on their CONTENT nodes already being imported and we haven't imported those at this point...
	// Though previously, above steps used to import CMS data, so perhaps it was related to that.
	// On a test DB, it seems like this currently magically hits the "Album" contenttypes...

	/**
	 * Now attachments
	 *
	 */
	public function step_171($data = [])
	{
		if ($this->tableExists('attachment') AND $this->tableExists('filedata'))
		{
			$process = 200;
			$startat = intval($data['startat'] ?? 0);
			$checkArray = $this->db->query_first("
				SELECT a.attachmentid
				FROM ". TABLE_PREFIX . "attachment AS a
				INNER JOIN ". TABLE_PREFIX . "node AS n ON n.oldid = a.contentid AND n.oldcontenttypeid = a.contenttypeid
				LEFT JOIN ". TABLE_PREFIX . "node AS existing ON existing.oldid = a.attachmentid AND existing.oldcontenttypeid = " . vB_Api_ContentType::OLDTYPE_POSTATTACHMENT . "
				WHERE existing.nodeid IS NULL LIMIT 1
			");

			if (empty($checkArray) AND !$startat)
			{
				$this->skip_message();
				return;
			}
			else if (empty($checkArray))
			{
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}
			else if (!$startat)
			{
				$this->show_message(sprintf($this->phrase['version']['500a1']['importing_x_records'], 'attachment'));
				$this->show_message(sprintf($this->phrase['core']['processing_records_x'], $process));
				return array('startat' => 1); // Go back and actually process
			}

			$attachTypeId = vB_Types::instance()->getContentTypeID('vBForum_Attach');
			$timenow = time();

			$this->run_query('adding attachment records',
				"/** Attachments **/
				INSERT INTO " . TABLE_PREFIX . "node(contenttypeid, parentid, description, publishdate, showpublished, showapproved, showopen, routeid, oldid, oldcontenttypeid)
				SELECT $attachTypeId, n.nodeid, a.caption, $timenow, 1, 1, 1, n.routeid, a.attachmentid, " . vB_Api_ContentType::OLDTYPE_POSTATTACHMENT . "
				FROM ". TABLE_PREFIX . "attachment AS a
				INNER JOIN ". TABLE_PREFIX . "node AS n ON n.oldid = a.contentid AND n.oldcontenttypeid = a.contenttypeid
				LEFT JOIN ". TABLE_PREFIX . "node AS existing ON existing.oldid = a.attachmentid AND existing.oldcontenttypeid = " . vB_Api_ContentType::OLDTYPE_POSTATTACHMENT . "
				WHERE existing.nodeid IS NULL LIMIT $process;
			");

			$this->show_message(sprintf($this->phrase['core']['processed_records_x'], $process));
			return array('startat' => ($startat + 1));
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	 * Now attachments
	 *
	 */
	public function step_172($data = [])
	{
		$process = 200;
		$startat = intval($data['startat'] ?? 0);
		if ($this->tableExists('attachment') AND $this->tableExists('attachment') AND $this->tableExists('filedata'))
		{
			$checkArray = $this->db->query_first("
			SELECT a.attachmentid
			FROM ". TABLE_PREFIX . "attachment AS a INNER JOIN ". TABLE_PREFIX . "node AS n ON n.oldid = a.attachmentid AND n.oldcontenttypeid = " . vB_Api_ContentType::OLDTYPE_POSTATTACHMENT . "

			LEFT JOIN ". TABLE_PREFIX . "attach AS existing ON existing.nodeid = n.nodeid AND existing.filedataid = a.filedataid
			WHERE existing.nodeid IS NULL LIMIT 1
		");

			if (empty($checkArray) AND !$startat)
			{
				$this->skip_message();
				return;
			}
			else if (empty($checkArray))
			{
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}
			else if (!$startat)
			{
				$this->show_message(sprintf($this->phrase['version']['500a1']['importing_x_records'], 'attachment'));
				$this->show_message(sprintf($this->phrase['core']['processing_records_x'], $process));
				return array('startat' => 1); // Go back and actually process
			}

			$this->run_query('adding attachment records', "INSERT INTO ". TABLE_PREFIX . "attach
			(nodeid, filedataid,visible, counter,posthash,filename,caption,reportthreadid,settings)
			SELECT n.nodeid, a.filedataid,
			CASE WHEN a.state = 'moderation' then 0 else 1 end AS visible, a.counter, a.posthash, a.filename, a.caption, a.reportthreadid, a.settings
			FROM ". TABLE_PREFIX . "attachment AS a INNER JOIN ". TABLE_PREFIX . "node AS n ON n.oldid = a.attachmentid AND n.oldcontenttypeid = " . vB_Api_ContentType::OLDTYPE_POSTATTACHMENT . "
			LEFT JOIN ". TABLE_PREFIX . "attach AS existing ON existing.nodeid = n.nodeid AND existing.filedataid = a.filedataid
			WHERE existing.nodeid IS NULL LIMIT $process;"
			);

			$this->show_message(sprintf($this->phrase['core']['processed_records_x'], $process));
			return array('startat' => ($startat + 1));
		}
		else
		{
			$this->skip_message();
		}
	}


	/**
	 * Update the "last" data for forums.
	 *
	 */
	public function step_173()
	{
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'node'));
		vB::getDbAssertor()->assertQuery('vBInstall:updateForumLast', array('forumTypeid' => vB_Types::instance()->getContentTypeID('vBForum_Forum'),
			'postTypeid' => vB_Types::instance()->getContentTypeID('vBForum_Post')));
	}

	/**
	 * The above step will fail if the last post is a thread with no replies. That requires a different query.
	 *
	 */
	public function step_174()
	{
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'node'));
		$types = vB_Types::instance();
		vB::getDbAssertor()->assertQuery('vBInstall:updateForumLastThreadOnly', [
			'threadTypeid' => $types->getContentTypeID('vBForum_Thread'),
			'forumTypeid' => $types->getContentTypeID('vBForum_Forum')
		]);
	}

	/**
	 * Update "last" data for threads. We need to do this in blocks because the there could potentially be hundreds of thousands.
	 *
	 */
	public function step_175($data = [])
	{
		$threadTypeId =  vB_Types::instance()->getContentTypeID('vBForum_Thread');
		$postTypeId = vB_Types::instance()->getContentTypeID('vBForum_Post');
		$batchsize = 4000;
		$startat = intval($data['startat'] ?? 0);
		$assertor = vB::getDbAssertor();

		$maxvB5 = $assertor->getRow('vBInstall:getMaxImportedPost', array('contenttypeid' => $threadTypeId));
		$maxvB5 = intval($maxvB5['maxid']);

		if ($startat == 0)
		{
			$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'node'));
		}
		else if ($startat >= $maxvB5)
		{
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			return;
		}
		$assertor->assertQuery('vBInstall:updateThreadLast', array('threadTypeid' => $threadTypeId, 'postTypeid' => $postTypeId, 'startat' => $startat, 'batchsize' => $batchsize));
		$this->show_message(sprintf($this->phrase['core']['processed_records_x'], $batchsize));
		return array('startat' => $startat + $batchsize);
	}

	//Now attachments from threads
	public function step_176($data = [])
	{
		if ($this->tableExists('attachment') AND $this->tableExists('filedata') AND $this->tableExists('thread') AND $this->tableExists('post'))
		{
			$process = 5000;
			$startat = intval($data['startat'] ?? 0);
			$attachTypeId =  vB_Types::instance()->getContentTypeID('vBForum_Attach');
			$threadTypeId =  vB_Types::instance()->getContentTypeID('vBForum_Thread');
			$postTypeId =  vB_Types::instance()->getContentTypeID('vBForum_Post');
			if (!$startat)
			{
				$this->show_message(sprintf($this->phrase['version']['500a1']['importing_x'], 'thread-attachments'));
			}
			//First see if we need to do something. Maybe we're O.K.
			if (empty($data['maxvB4']))
			{
				$maxvB4 = $this->db->query_first("SELECT MAX(a.attachmentid) AS maxid
					FROM " . TABLE_PREFIX . "attachment AS a
					INNER JOIN " . TABLE_PREFIX . "post AS p ON a.contentid = p.postid
					INNER JOIN " . TABLE_PREFIX . "thread AS th ON p.threadid = th.threadid AND th.firstpostid = p.postid
					WHERE a.contenttypeid = $postTypeId
				");
				$maxvB4 = $maxvB4['maxid'];

				//If we don't have any attachments, we're done.
				if (intval($maxvB4) < 1)
				{
					$this->skip_message();
					return;
				}
			}
			else
			{
				$maxvB4 = $data['maxvB4'];
			}

			$maxvB5 = $this->db->query_first("SELECT MAX(oldid) AS maxid FROM " . TABLE_PREFIX . "node WHERE oldcontenttypeid = " . vB_Api_ContentType::OLDTYPE_THREADATTACHMENT);
			if (empty($maxvB5) OR empty($maxvB5['maxid']))
			{
				$maxvB5 = 0;
			}
			else
			{
				$maxvB5 = $maxvB5['maxid'];
			}

			if (($maxvB4 <= $maxvB5) AND !$startat)
			{
				$this->skip_message();
				return;
			}
			else if ($maxvB4 <= $maxvB5)
			{
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}
			$maxvB5 = max($maxvB5, $startat);

			/*** first the nodes ***/
				$query = "
			INSERT INTO " . TABLE_PREFIX . "node(
				userid, authorname, contenttypeid, parentid, routeid, title,  htmltitle,
				publishdate, oldid, oldcontenttypeid, created,
				starter, inlist, showpublished, showapproved, showopen
			)
			SELECT a.userid, u.username, $attachTypeId, n.nodeid, n.routeid, '', '',
				a.dateline, a.attachmentid,	" . vB_Api_ContentType::OLDTYPE_THREADATTACHMENT . ", a.dateline,
				n.starter, 0, 1, 1, 1
			FROM " . TABLE_PREFIX . "attachment AS a
			INNER JOIN " . TABLE_PREFIX . "thread AS th ON a.contentid = th.firstpostid
			INNER JOIN " . TABLE_PREFIX . "node AS n ON n.oldid = th.threadid AND n.oldcontenttypeid = " . $threadTypeId . "
			LEFT JOIN " . TABLE_PREFIX . "user AS u ON a.userid = u.userid
			WHERE a.attachmentid > $maxvB5 AND a.attachmentid < ($maxvB5 + $process) AND a.contenttypeid = $postTypeId
			ORDER BY a.attachmentid;";
			$this->db->query_write($query);

			//Now populate the attach table
			$query = "
			INSERT INTO ". TABLE_PREFIX . "attach
			(nodeid, filedataid, visible, counter, posthash, filename, caption, reportthreadid, settings)
				SELECT n.nodeid, a.filedataid,
				 CASE WHEN a.state = 'moderation' then 0 else 1 end AS visible, a.counter, a.posthash, a.filename, a.caption, a.reportthreadid, a.settings
			FROM ". TABLE_PREFIX . "attachment AS a
			INNER JOIN ". TABLE_PREFIX . "node AS n ON n.oldid = a.attachmentid AND n.oldcontenttypeid = " . vB_Api_ContentType::OLDTYPE_THREADATTACHMENT . "
			WHERE a.attachmentid > $maxvB5  AND a.attachmentid < ($maxvB5 + $process) AND a.contenttypeid = $postTypeId;";
			$this->db->query_write($query);

			//Now the closure record for the node
			$query = "INSERT INTO " . TABLE_PREFIX . "closure (parent, child, depth)
			SELECT node.nodeid, node.nodeid, 0
			FROM " . TABLE_PREFIX . "node AS node
			WHERE node.oldcontenttypeid = " . vB_Api_ContentType::OLDTYPE_THREADATTACHMENT . " AND node.oldid > $maxvB5 AND node.oldid < ($maxvB5 + $process);";
			$this->db->query_write($query);

			//Add the closure records to root
			$query = "INSERT INTO " . TABLE_PREFIX . "closure (parent, child, depth)
			SELECT parent.parent, node.nodeid, parent.depth + 1
			FROM " . TABLE_PREFIX . "node AS node
			INNER JOIN " . TABLE_PREFIX . "closure AS parent ON parent.child = node.parentid
			WHERE node.oldcontenttypeid = " . vB_Api_ContentType::OLDTYPE_THREADATTACHMENT . " AND node.oldid > $maxvB5 AND node.oldid < ($maxvB5 + $process);";
			$this->db->query_write($query);

			$this->show_message(sprintf($this->phrase['core']['processed_records_x'], $process));

			return array('startat' => ($maxvB5 + $process - 1), 'maxvB4' => $maxvB4);
		}
	}

	public function step_177($data = [])
	{
		// grab any skipped threads' missing attachments
		if (empty($data['startat']))
		{
			$this->show_message(sprintf($this->phrase['version']['500a1']['checking_skipped_x'], 'thread-attachments'));
		}

		return $this->handleSkippedPostAttachments('thread', $data);
	}

	public function step_178($data = [])
	{
		// grab any skipped polls' missing attachments. Need to process these separately because of the
		// different oldid & oldcontenttypeid makes step_177() miss importing them even though they're detected.
		if (empty($data['startat']))
		{
			$this->show_message(sprintf($this->phrase['version']['500a1']['checking_skipped_x'], 'poll-attachments'));
		}

		return $this->handleSkippedPostAttachments('poll', $data);
	}
}

class vB_Upgrade_500a10 extends vB_Upgrade_Version
{
	/**
	 * Change settings routenew.name class
	 */
	public function step_1()
	{
		$this->skip_message();
	}

	/**
	 * Change settings routenew.arguments
	 */
	public function step_2()
	{
		$this->skip_message();
	}

	/**
	 * Change showpublished field to 1 for Albums and Private Messages
	 */
	public function step_3()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . "node"),
			"UPDATE " . TABLE_PREFIX . "node
			SET showpublished = '1'
			WHERE showpublished = '0' AND
				contenttypeid = '23' AND
				title IN ('Albums', 'Private Messages')
			"
		);
	}

	/*** Add index on nodeid to the moderators table */
	public function step_4()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'moderator', 1, 1),
			'moderator',
			'nodeid',
			'nodeid'
		);
	}


	/*** set the nodeid for moderators */
	public function step_5()
	{
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], 'moderator'));
		vB::getDbAssertor()->assertQuery('vBInstall:setModeratorNodeid',
			array('forumTypeId' => vB_Types::instance()->getContentTypeID('vBForum_Forum')));
	}

	/*** Add index on nodeid to the moderatorlog table */
	public function step_6()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'moderatorlog', 1, 1),
			'moderatorlog',
			'nodeid',
			'nodeid'
		);
	}


	/*** set the nodeid for moderatorlog */
	public function step_7()
	{
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], 'moderatorlog'));
		vB::getDbAssertor()->assertQuery('vBInstall:setModeratorlogThreadid',
			array('threadTypeId' => vB_Types::instance()->getContentTypeID('vBForum_Thread')));
	}

	/*** Add index on nodeid to the access table */
	public function step_8()
	{
		$this->skip_message();
	}


	/*** set the nodeid for access */
	public function step_9()
	{
		$this->skip_message();
	}
}

class vB_Upgrade_500a11 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->skip_message();
	}

	public function step_2()
	{
		$this->skip_message();
	}

	/**
	 * Report / Flag
	 */
	public function step_3()
	{
		// Reports Channel
		$reportChannel = $this->db->query_first("
			SELECT node.nodeid, node.oldcontenttypeid
			FROM " . TABLE_PREFIX . "node AS node
			INNER JOIN " . TABLE_PREFIX . "channel AS channel ON (node.nodeid = channel.nodeid)
			WHERE channel.guid = '" . vB_Channel::REPORT_CHANNEL . "'");
		$oldContentTypeId = 9997;
		if (!empty($reportChannel) AND $reportChannel['oldcontenttypeid'] != $oldContentTypeId)
		{
			// Set the oldcontenttypeid and oldid if they're not set. The channel should've already been created in 500a1.
			$query = "
			UPDATE " . TABLE_PREFIX . "node
			SET oldid = 1, oldcontenttypeid = " . $oldContentTypeId . "
			WHERE nodeid = " . $reportChannel['nodeid'];
			$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], 'node'));
			$this->db->query_write(
				$query);
		}
		else
		{
			$this->skip_message();
		}

		$contenttype = $this->db->query_first("
			SELECT contenttypeid FROM " . TABLE_PREFIX . "contenttype
			WHERE class = 'Report'");
		if (empty($contenttype) OR empty($contenttype['contenttypeid']))
		{
			$this->run_query(
			sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'contenttype'),
			"INSERT INTO " . TABLE_PREFIX . "contenttype(class,
			packageid,	canplace,	cansearch,	cantag,	canattach,	isaggregator)
			SELECT 'Report', packageid, '0', '0', '0', '0', '0'  FROM " . TABLE_PREFIX . "package where class = 'vBForum';");
		}
		else
		{
			$this->skip_message();
		}

		$this->run_query(
		sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'link'),
		"
			CREATE TABLE " . TABLE_PREFIX . "report (
				nodeid INT UNSIGNED NOT NULL,
				reportnodeid INT UNSIGNED NOT NULL DEFAULT '0',
				closed SMALLINT(5) UNSIGNED NOT NULL DEFAULT '0',
				PRIMARY KEY (nodeid),
				KEY (reportnodeid, closed)
			) ENGINE = " . $this->hightrafficengine . "
		",
		self::MYSQL_ERROR_TABLE_EXISTS
		);
	}
}

class vB_Upgrade_500a14 extends vB_Upgrade_Version
{
	/** Adding UUID field to page table **/
	public function step_1()
	{
		$this->skip_message();
	}

	public function step_2()
	{
		// The problem that I think this step was created to fix was default vBulletin
		// pages that did't have a GUID. I don't think this can happen any more because
		// now we create the page table in 500a1 step1 and import the pages from the XML
		// in 500a1 step131 which runs the final upgrade step to import pages, meaning
		// that all default vB pages should always have their GUID set. That said, fully
		// investigating this is out of scope for what I'm currently doing. Steps 2, 4, 6
		// 8, and 10 in this file are probably all obsolete and can be made no-ops. But,
		// since I can't do that at this point, I'll opt for a non-invasive fix that
		// prevents throwing the exception when duplicate page titles exist in the pages
		// XML file (see the 3 "Forums" home pages we now have, VBV-19556).
		// The fix entails adding the route GUID to the "key" ($title) that is used to
		// identify the unique page. Note steps 1-10 in this file are probably all
		// obsolete, as the GUID should be properly imported in those cases.

		$missing = $this->db->query_read('
			SELECT p.pageid, p.title, p.pagetype, r.guid AS routeguid
			FROM ' . TABLE_PREFIX . 'page AS p
			LEFT JOIN ' . TABLE_PREFIX . 'routenew AS r ON (p.routeid = r.routeid)
			WHERE p.guid IS NULL
		');

		if ($this->db->num_rows($missing) > 0)
		{
			$parsedXml = vB_Xml_Import::parseFile(dirname(__FILE__) . '/../vbulletin-pages.xml');

			$pages = array();
			foreach($parsedXml['page'] AS $t)
			{
				$title = ($t['title'] == 'Forums') ? $t['title'] . '-' . $t['pagetype'] . '-' . $t['routeGuid'] : $t['title'];

				if (isset($pages[$title]))
				{
					throw new Exception("Duplicate id when updating page GUIDs! ($title)");
				}

				$pages[$title] = $t['guid'];
			}

			while ($page = $this->db->fetch_array($missing))
			{
				$title = ($page['title'] == 'Forums') ? $page['title'] . '-' . $page['pagetype'] . '-' . $page['routeguid'] : $page['title'];
				$guid = (isset($pages[$title]) AND !empty($pages[$title])) ? $pages[$title] : vB_Xml_Export_Page::createGUID($page);
				$this->run_query(
					sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'page'),
					"UPDATE " . TABLE_PREFIX . "page
					SET guid = '{$guid}'
					WHERE pageid = {$page['pageid']}"
				);
			}
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_3()
	{
		$this->skip_message();
	}

	public function step_4()
	{
		$parsedXml = vB_Xml_Import::parseFile(dirname(__FILE__) . '/../vbulletin-pagetemplates.xml');

		$templates = array();
		foreach($parsedXml['pagetemplate'] AS $t)
		{
			if (isset($templates[$t['title']]))
			{
				throw new Exception("Duplicate id when updating page template GUIDs! ({$t['title']})");
			}

			$templates[$t['title']] = $t['guid'];
		}

		$missing = $this->db->query_read('SELECT pagetemplateid, title FROM ' . TABLE_PREFIX . 'pagetemplate WHERE guid IS NULL');

		if ($this->db->num_rows($missing) > 0)
		{
			while ($pagetemplate = $this->db->fetch_array($missing))
			{
				$guid = (isset($templates[$pagetemplate['title']]) AND !empty($templates[$pagetemplate['title']])) ? $templates[$pagetemplate['title']] : vB_Xml_Export::createGUID($pagetemplate);
				$this->run_query(
						sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'pagetemplate'),
						"UPDATE " . TABLE_PREFIX . "pagetemplate
						SET guid = '{$guid}'
						WHERE pagetemplateid = {$pagetemplate['pagetemplateid']}"
				);
			}
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_5()
	{
		$this->skip_message();
	}

	public function step_6()
	{
		$this->skip_message();
	}

	public function step_7()
	{
		$this->skip_message();
	}

	public function step_8()
	{
		$parsedXml = vB_Xml_Import::parseFile(dirname(__FILE__) . '/../vbulletin-channels.xml');

		$channels = array();
		foreach($parsedXml['channel'] AS $t)
		{
			if (isset($channels[$t['node']['title']]))
			{
				throw new Exception("Duplicate id when updating channel GUIDs! ({$t['node']['title']})");
			}

			$channels[$t['node']['title']] = $t['guid'];
		}

		$missing = $this->db->query_read(
			'SELECT c.nodeid, n.title
			FROM ' . TABLE_PREFIX . 'channel AS c
			INNER JOIN ' . TABLE_PREFIX . 'node AS n ON n.nodeid = c.nodeid
			WHERE guid IS NULL'
		);

		if ($this->db->num_rows($missing) > 0)
		{
			while ($channel = $this->db->fetch_array($missing))
			{
				$guid = (isset($channels[$channel['title']]) AND !empty($channels[$channel['title']])) ? $channels[$channel['title']] : vB_Xml_Export::createGUID($channel);
				$this->run_query(
						sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'channel'),
						"UPDATE " . TABLE_PREFIX . "channel
						SET guid = '{$guid}'
						WHERE nodeid = {$channel['nodeid']}"
				);
			}
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_9()
	{
		$this->skip_message();
	}

	public function step_10()
	{
		$parsedXml = vB_Xml_Import::parseFile(dirname(__FILE__) . '/../vbulletin-routes.xml');

		$routes = [];
		foreach($parsedXml['route'] AS $t)
		{
			//The class isn't set for all routes in the xml.  It will be blank if it's not specified.
			//Note that the "title" isn't used aside from matching existing routes to the xml values
			$title = $t['prefix'] . '-' . ($t['class'] ?? '');
			if (isset($routes[$title]))
			{
				throw new Exception("Duplicate id when updating route GUIDs! ({$title})");
			}

			$routes[$title] = $t['guid'];
		}

		$missing = $this->db->query_read('SELECT routeid, prefix, class FROM ' . TABLE_PREFIX . 'routenew WHERE guid IS NULL');

		if ($this->db->num_rows($missing) > 0)
		{
			while ($route = $this->db->fetch_array($missing))
			{
				$temp_id = $route['prefix'] . '-' . $route['class'];
				$guid = (!empty($routes[$temp_id]) ? $routes[$temp_id] : vB_Xml_Export::createGUID($route));
				$this->run_query(
					sprintf($this->phrase['vbphrase']['update_table'], 'routenew'),
					"UPDATE " . TABLE_PREFIX . "routenew
					SET guid = '{$guid}'
					WHERE routeid = {$route['routeid']}"
				);
			}
		}
		else
		{
			$this->skip_message();
		}
	}

	// Update old reputation table
	public function step_11()
	{
		$this->skip_message();
	}

	//Set nodeid in reputation table.
	public function step_12($data = NULL)
	{
		if (empty($data['startat']))
		{
			$this->show_message(sprintf($this->phrase['vbphrase']['update_table_x'], TABLE_PREFIX . 'reputation', 1, 2));
		}

		$callback = function($startat, $nextid)
		{
			$postTypeId = vB_Types::instance()->getContentTypeID('vBForum_Post');

			vB::getDbAssertor()->assertQuery('vBInstall:setReputationNodeid', array(
				'oldcontenttypeid' => $postTypeId,
				'startat' => $startat,
				'nextid' => $nextid,
			));
		};

		$batchsize = $this->getBatchSize('small', __FUNCTION__);
		return $this->updateByIdWalk($data, $batchsize, 'vBInstall:getMaxReputationid', 'vBForum:reputation', 'reputationid', $callback);
	}

	public function step_13($data = NULL)
	{
		if (empty($data['startat']))
		{
			$this->show_message(sprintf($this->phrase['vbphrase']['update_table_x'], TABLE_PREFIX . 'reputation', 2, 2));
		}

		$callback = function($startat, $nextid)
		{
			$threadTypeId = vB_Types::instance()->getContentTypeID('vBForum_Thread');

			vB::getDbAssertor()->assertQuery('vBInstall:setReputationNodeidThread', array(
				'oldcontenttypeid' => $threadTypeId,
				'startat' => $startat,
				'nextid' => $nextid,
			));
		};

		$batchsize = $this->getBatchSize('xsmall', __FUNCTION__);
		return $this->updateByIdWalk($data, $batchsize, 'vBInstall:getMaxReputationid', 'vBForum:reputation', 'reputationid', $callback);
	}

	// Update reputation table
	public function step_14()
	{
		// Drop orphans
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'reputation', 1, 3),
			"DELETE FROM " . TABLE_PREFIX . "reputation
			WHERE nodeid = 0"
		);

	}
	// Update reputation table
	public function step_15()
	{
		// Add new index
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'reputation', 2, 3),
			'reputation',
			'whoadded_nodeid',
			array('whoadded', 'nodeid'),
			'unique'
		);

	}
	// Update reputation table
	public function step_16()
	{
		// Add new index
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'reputation', 3, 3),
			'reputation',
			'multi',
			array('nodeid', 'userid')
		);

	}

	public function step_17()
	{
		// Drop nodevote table
		$this->run_query(
			sprintf($this->phrase['core']['dropping_old_table_x'], TABLE_PREFIX . "nodevote"),
			"DROP TABLE IF EXISTS " . TABLE_PREFIX . "nodevote"
		);
	}

	/*
	 * VBV-6546 : Set node hasphoto value
	 */
	public function step_18($data = [])
	{
		$process = 2500;
		$maxnode = intval($data['maxnode'] ?? 0);
		$startat = intval($data['startat'] ?? 0);

		if ($startat == 0)
		{
			// Initial pass, get max nodeid
			$data = $this->db->query_first("
				SELECT MAX(nodeid) AS maxnode
				FROM " . TABLE_PREFIX . "text
			");

			$maxnode = intval($data['maxnode']);

			if (!$maxnode)
			{ // Nothing to process (unlikely ....)
				$this->skip_message();
				return;
			}

			$this->show_message($this->phrase['version']['500a14']['processing_photos']);
			return array('startat' => 1, 'maxnode' => $maxnode);
		}
		else
		{	// Subsequent passes
			$first = $startat;
			$last = $first + $process - 1;

			if ($first > $maxnode)
			{
				$this->show_message($this->phrase['version']['500a14']['update_photos_complete']);
				return;
			}
		}

		$nodes = $this->db->query_read_slave("
			SELECT n.nodeid, t.rawtext, n.hasphoto
			FROM " . TABLE_PREFIX . "node AS n
			INNER JOIN " . TABLE_PREFIX . "text as t
			USING (nodeid)
			WHERE n.nodeid >= $first AND n.nodeid <= $last
		");

		$nodelist = array();
		$rows = $this->db->num_rows($nodes);

		if ($rows)
		{
			while ($node = $this->db->fetch_array($nodes))
			{
				if (!$node['hasphoto']
				// Make sure we have an opening and closing tag
				AND strripos($node['rawtext'], '[attach') !== false
				AND strripos($node['rawtext'], '[/attach') !== false)
				{
					$nodelist[] = $node['nodeid'];
				}
			}
		}

		if ($nodelist)
		{
			$nodes = implode(',', $nodelist);

			$this->db->query_write("
				UPDATE " . TABLE_PREFIX . "node
				SET hasphoto = 1
				WHERE nodeid IN ($nodes)
			");
		}

		$this->show_message(sprintf($this->phrase['version']['500a14']['processed_nodes'], $first, $last, $rows));

		return array('startat' => $last + 1, 'maxnode' => $maxnode);
	}

	/*
	 * VBV-6546 : Set node hasvideo value
	 */
	public function step_19($data = [])
	{
		$process = 2500;
		$maxnode = intval($data['maxnode'] ?? 0);
		$startat = intval($data['startat'] ?? 0);

		if ($startat == 0)
		{
			// Initial pass, get max nodeid
			$data = $this->db->query_first("
				SELECT MAX(nodeid) AS maxnode
				FROM " . TABLE_PREFIX . "text
			");

			$maxnode = intval($data['maxnode']);

			if (!$maxnode)
			{
				// Nothing to process (unlikely ....)
				$this->skip_message();
				return;
			}

			$this->show_message($this->phrase['version']['500a14']['processing_videos']);
			return array('startat' => 1, 'maxnode' => $maxnode);
		}
		else
		{	// Subsequent passes
			$first = $startat;
			$last = $first + $process - 1;

			if ($first > $maxnode)
			{
				$this->show_message($this->phrase['version']['500a14']['update_videos_complete']);
				return;
			}
		}

		$nodes = $this->db->query_read_slave("
			SELECT n.nodeid, t.rawtext, n.hasvideo
			FROM " . TABLE_PREFIX . "node AS n
			INNER JOIN " . TABLE_PREFIX . "text as t
			USING (nodeid)
			WHERE n.nodeid >= $first AND n.nodeid <= $last
		");

		$nodelist = array();
		$rows = $this->db->num_rows($nodes);

		if ($rows)
		{
			while ($node = $this->db->fetch_array($nodes))
			{
				if (!$node['hasvideo']
				// Make sure we have an opening and closing tag
				AND strripos($node['rawtext'], '[video') !== false
				AND strripos($node['rawtext'], '[/video') !== false)
				{
					$nodelist[] = $node['nodeid'];
				}
			}
		}

		if ($nodelist)
		{
			$nodes = implode(',', $nodelist);

			$this->db->query_write("
				UPDATE " . TABLE_PREFIX . "node
				SET hasvideo = 1
				WHERE nodeid IN ($nodes)
			");
		}

		$this->show_message(sprintf($this->phrase['version']['500a14']['processed_nodes'], $first, $last, $rows));

		return array('startat' => $last + 1, 'maxnode' => $maxnode);
	}

	//For handling private message deletion
	public function step_20()
	{
		$this->skip_message();
	}

	//cron job for private message deletion.
	public function step_21()
	{
		$assertor = vB::getDbAssertor();
		$existing = $assertor->getRow('cron', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'varname' => 'privatemessages'));
		if ($existing AND empty($existing['errors']))
		{
			$this->skip_message();
		}
		else
		{
			$data = array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_INSERT,
			'nextrun' =>  vB::getRequest()->getTimeNow(), 'weekday' => -1, 'day' => -1,
			'hour' => -1, 'minute' => 'a:1:{i:0;i:40;}','filename' => './includes/cron/privatemessage_cleanup.php',
			'loglevel' => 1, 'varname' => 'privatemessages', 'volatile' => 1,'product' => 'vbulletin');

			$assertor->assertQuery('cron', $data);
			$this->show_message($this->phrase['version']['500a14']['adding_pm_scheduled_task']);
		}

		$this->long_next_step();
	}

	/**
	 * Remove following widget information
	 */
	public function step_22()
	{
		$this->run_query(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'routenew'),
			"DELETE FROM " . TABLE_PREFIX . "routenew WHERE guid = 'vbulletin-4ecbdacd6a7ef6.07321454'"
		);

		$this->run_query(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'page'),
			"DELETE FROM " . TABLE_PREFIX . "page WHERE guid = 'vbulletin-4ecbdac82f17e1.17839721'"
		);

		// get the pagetemplateid to delete pages and routenew records
		$templateInfo = $this->db->query_first("
			SELECT pagetemplateid FROM " . TABLE_PREFIX . "pagetemplate
			WHERE guid = 'vbulletin-4ecbdac9373089.38426136'"
		);

		if ($templateInfo AND isset($templateInfo['pagetemplateid']) AND !empty($templateInfo['pagetemplateid']))
		{
			$this->run_query(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'pagetemplate'),
				"DELETE FROM " . TABLE_PREFIX . "pagetemplate WHERE pagetemplateid = " . $templateInfo['pagetemplateid']
			);

			// fetch pages using the template
			$pages = $this->db->query_read("
				SELECT pageid, routeid FROM " . TABLE_PREFIX . "page
				WHERE pagetemplateid = " . $templateInfo['pagetemplateid']
			);
			$pageIds = array();
			$routeIds = array();
			while ($page = $this->db->fetch_array($pages))
			{
				$pageIds[] = $page['pageid'];
				$routeIds[] = $page['routeid'];
			}

			// delete page...
			if (!empty($pageIds))
			{
				$this->run_query(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'page'),
					"DELETE FROM " . TABLE_PREFIX . "page WHERE pageid IN (" . implode(', ', $pageIds) . ")"
				);
			}
		}

		// ...and routenew records
		if (!empty($routeIds))
		{
			$this->run_query(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'routenew'),
				"DELETE FROM " . TABLE_PREFIX . "routenew WHERE routeid IN (" . implode(', ', $routeIds) . ")"
			);
		}

		// now from widget tables
		$widgetInfo = $this->db->query_first("
			SELECT widgetid FROM " . TABLE_PREFIX . "widget
			WHERE guid = 'vbulletin-widget_following-4eb423cfd6c778.30550576'"
		);

		if ($widgetInfo AND isset($widgetInfo['widgetid']) AND !empty($widgetInfo['widgetid']))
		{
			$this->run_query(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'widget'),
				"DELETE FROM " . TABLE_PREFIX . "widget WHERE guid = 'vbulletin-widget_following-4eb423cfd6c778.30550576'"
			);

			$this->run_query(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'widgetdefinition'),
				"DELETE FROM " . TABLE_PREFIX . "widgetdefinition WHERE widgetid = " . $widgetInfo['widgetid']
			);

			$this->run_query(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'widgetinstance'),
				"DELETE FROM " . TABLE_PREFIX . "widgetinstance WHERE widgetid = " . $widgetInfo['widgetid']
			);
		}

		$this->long_next_step();
	}

	/**
	 * Remove followers widget information
	 */
	public function step_23()
	{
		$this->run_query(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'routenew'),
			"DELETE FROM " . TABLE_PREFIX . "routenew WHERE guid = 'vbulletin-4ecbdacd6a8b25.50710303'"
		);

		$this->run_query(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'page'),
			"DELETE FROM " . TABLE_PREFIX . "page WHERE guid = 'vbulletin-4ecbdac82f1bf0.76172990'"
		);

		// get the pagetemplateid to delete pages and routenew records
		$templateInfo = $this->db->query_first("
			SELECT pagetemplateid FROM " . TABLE_PREFIX . "pagetemplate
			WHERE guid = 'vbulletin-4ecbdac9373422.51068894'"
		);

		if ($templateInfo AND !empty($templateInfo['pagetemplateid']))
		{
			$this->run_query(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'pagetemplate'),
				"DELETE FROM " . TABLE_PREFIX . "pagetemplate WHERE pagetemplateid = " . $templateInfo['pagetemplateid']
			);

			// fetch pages using the template
			$pages = $this->db->query_read("
				SELECT pageid, routeid FROM " . TABLE_PREFIX . "page
				WHERE pagetemplateid = " . $templateInfo['pagetemplateid']
			);

			$pageIds = [];
			$routeIds = [];
			while ($page = $this->db->fetch_array($pages))
			{
				$pageIds[] = $page['pageid'];
				$routeIds[] = $page['routeid'];
			}

			// delete page...
			if (!empty($pageIds))
			{
				$this->run_query(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'page'),
					"DELETE FROM " . TABLE_PREFIX . "page WHERE pageid IN (" . implode(', ', $pageIds) . ")"
				);
			}

			// and routenew records
			if (!empty($routeIds))
			{
				$this->run_query(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'routenew'),
					"DELETE FROM " . TABLE_PREFIX . "routenew WHERE routeid IN (" . implode(', ', $routeIds) . ")"
				);
			}
		}

		// now from widget tables
		$widgetInfo = $this->db->query_first("
			SELECT widgetid FROM " . TABLE_PREFIX . "widget
			WHERE guid = 'vbulletin-widget_followers-4eb423cfd6cac2.78540773'"
		);

		if ($widgetInfo AND !empty($widgetInfo['widgetid']))
		{
			$this->run_query(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'widget'),
				"DELETE FROM " . TABLE_PREFIX . "widget WHERE guid = 'vbulletin-widget_followers-4eb423cfd6cac2.78540773'"
			);

			$this->run_query(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'widgetdefinition'),
				"DELETE FROM " . TABLE_PREFIX . "widgetdefinition WHERE widgetid = " . $widgetInfo['widgetid']
			);

			$this->run_query(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'widgetinstance'),
				"DELETE FROM " . TABLE_PREFIX . "widgetinstance WHERE widgetid = " . $widgetInfo['widgetid']
			);
		}

		$this->long_next_step();
	}

	/**
	 * Remove groups widget information
	 */
	public function step_24()
	{
		$this->run_query(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'routenew'),
			"DELETE FROM " . TABLE_PREFIX . "routenew WHERE guid = 'vbulletin-4ecbdacd6a8f29.89433296'"
		);

		$this->run_query(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'page'),
			"DELETE FROM " . TABLE_PREFIX . "page WHERE guid = 'vbulletin-4ecbdac82f2008.58648267'"
		);

		// get the pagetemplateid to delete pages and routenew records
		$templateInfo = $this->db->query_first("
			SELECT pagetemplateid FROM " . TABLE_PREFIX . "pagetemplate
			WHERE guid = 'vbulletin-4ecbdac93737c2.35059434'"
		);

		if ($templateInfo AND !empty($templateInfo['pagetemplateid']))
		{
			$this->run_query(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'pagetemplate'),
				"DELETE FROM " . TABLE_PREFIX . "pagetemplate WHERE pagetemplateid = " . $templateInfo['pagetemplateid']
			);

			// fetch pages using the template
			$pages = $this->db->query_read("
				SELECT pageid, routeid FROM " . TABLE_PREFIX . "page
				WHERE pagetemplateid = " . $templateInfo['pagetemplateid']
			);

			$pageIds = [];
			$routeIds = [];
			while ($page = $this->db->fetch_array($pages))
			{
				$pageIds[] = $page['pageid'];
				$routeIds[] = $page['routeid'];
			}

			// delete page...
			if (!empty($pageIds))
			{
				$this->run_query(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'page'),
					"DELETE FROM " . TABLE_PREFIX . "page WHERE pageid IN (" . implode(', ', $pageIds) . ")"
				);
			}

			// ...and routenew records
			if (!empty($routeIds))
			{
				$this->run_query(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'routenew'),
					"DELETE FROM " . TABLE_PREFIX . "routenew WHERE routeid IN (" . implode(', ', $routeIds) . ")"
				);
			}
		}

		// now from widget tables
		$widgetInfo = $this->db->query_first("
			SELECT widgetid FROM " . TABLE_PREFIX . "widget
			WHERE guid = 'vbulletin-widget_groups-4eb423cfd6ce25.12220055'"
		);


		if ($widgetInfo AND !empty($widgetInfo['widgetid']))
		{
			$this->run_query(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'widget'),
				"DELETE FROM " . TABLE_PREFIX . "widget WHERE guid = 'vbulletin-widget_groups-4eb423cfd6ce25.12220055'"
			);

			$this->run_query(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'widgetdefinition'),
				"DELETE FROM " . TABLE_PREFIX . "widgetdefinition WHERE widgetid = " . $widgetInfo['widgetid']
			);

			$this->run_query(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'widgetinstance'),
				"DELETE FROM " . TABLE_PREFIX . "widgetinstance WHERE widgetid = " . $widgetInfo['widgetid']
			);
		}
	}

	// Set all users to have collapsed signature by default
	public function step_25()
	{
		$bf_misc_useroptions = vB::getDatastore()->get_value('bf_misc_useroptions');

		$this->run_query(
			sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'user'),
			"UPDATE " . TABLE_PREFIX . "user
			SET options = options - {$bf_misc_useroptions['showsignatures']}
			WHERE (options & {$bf_misc_useroptions['showsignatures']})"
		);
	}


	//Add setfor- needed for Visitor Message.
	public function step_26()
	{
		$assertor = vB::getDbAssertor();

		$current = $assertor->getRows('routenew', array('name' => 'album'));

		if (empty($current) OR !empty($current['routeid']))
		{
			$this->show_message($this->phrase['version']['500a13']['adding_album_widget']);

			$data = array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_INSERT,
			'title' => 'Album Template', 'screenlayoutid' => 1);
			$pagetemplateid = $assertor->assertQuery('pagetemplate', $data);
			$pagetemplateid = $pagetemplateid[0];

			$data = array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_INSERT,
			'parentid' => 0, 'pagetemplateid' => $pagetemplateid, 'title' => 'Album',
			'metadescription' => 'vBulletin Photo Album',
			'routeid' => 10, 'displayorder' => 1, 'pagetype' => 'custom');
			$pageid = $assertor->assertQuery('page', $data);
			$pageid = $pageid[0];

			$data = array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_INSERT,
			'name' => 'album', 'prefix' => 'album', 'regex' => 'album/(?P<nodeid>[0-9]+)(?P<title>(-[^!@\\#\\$%\\^&\\*\\(\\)\\+\\?/:;"\'\\\\,\\.<>= _]*)*)',
			'class' => 'vB5_Route_album','controller' => 'page','action' => 'index',
			'template' => 'widget_album','arguments' => serialize(array('contentid' => $pageid)),'contentid' => $pageid);

			$routeid = $assertor->assertQuery('routenew', $data);
			$routeid = $routeid[0];

			$data = array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
			'pageid' => $pageid, 'routeid' => $routeid);

			$assertor->assertQuery('page', $data);

			$data = array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_INSERT,
			'pagetemplateid' => $pagetemplateid, 'widgetid' => 30, 'displaysection' => 0,
			'displayorder' => 0);

			$assertor->assertQuery('widgetinstance', $data);
		}
		else
		{
			$this->skip_message();
		}
	}
}

class vB_Upgrade_500a15 extends vB_Upgrade_Version
{
	/** Adding soft delete fields */
	// Update old announcement table
	public function step_1()
	{
		if (!$this->field_exists('announcement', 'nodeid'))
		{
			// Add nodeid field
			$this->add_field(
				sprintf($this->phrase['core']['altering_x_table'], 'announcement', 1, 5),
				'announcement',
				'nodeid',
				'INT',
				array(
					'attributes' => '',
					'null'       => false,
					'default'    => 0,
					'extra'      => ''
				)
			);

		}
		else
		{
			$this->skip_message();
		}
	}

	// Update old announcement table
	public function step_2()
	{
		// Drop old indexes
		$this->drop_index(
			sprintf($this->phrase['core']['altering_x_table'], 'announcement', 2, 5),
			'announcement',
			'forumid'
		);
	}

	// Update old announcement table
	public function step_3()
	{
			$this->drop_index(
				sprintf($this->phrase['core']['altering_x_table'], 'announcement', 3, 5),
				'announcement',
				'startdate'
			);
	}

	// Update old announcement table
	public function step_4()
	{
		// Add new indices
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'announcement', 4, 5),
			'announcement',
			'nodeid',
			array('nodeid')
		);
	}

	// Update old announcement table
	public function step_5()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'announcement', 5, 5),
			'announcement',
			'startdate',
			array('enddate', 'nodeid', 'startdate')
		);

	}

	// Update old announcement table
	public function step_6()
	{
		if ($this->field_exists('announcement', 'forumid'))
		{
			$forumTypeid = vB_Types::instance()->getContentTypeID('vBForum_Forum');
			// Convert the old forumid into new nodeid
			$this->run_query(sprintf($this->phrase['vbphrase']['update_table'], 'announcement'),
				"UPDATE " . TABLE_PREFIX . "announcement as announcement
				SET nodeid = (
					SELECT nodeid FROM " . TABLE_PREFIX . "node as node
					WHERE node.oldid = announcement.forumid AND node.oldcontenttypeid = $forumTypeid
					LIMIT 1
				)
				WHERE nodeid = 0 AND forumid > 0
				"
			);
			// Old forumid may be -1. If so we copy it to nodeid
			$this->run_query(sprintf($this->phrase['vbphrase']['update_table'], 'announcement'),
				"UPDATE " . TABLE_PREFIX . "announcement as announcement
				SET nodeid = -1
				WHERE nodeid = 0 AND forumid = -1
				"
			);

		}
		else
		{
			$this->skip_message();
		}
	}

	// This step is to fix VBV-176. The issue only happens for older versions before Alpha 15. New installation isn't affected.
	public function step_7()
	{
		$check = $this->db->query_first("SELECT routeid, class FROM " . TABLE_PREFIX . "routenew WHERE name = 'advanced_search'");

		if (!$check OR $check['class'] != 'vB5_Route_Search')
		{
			$this->skip_message();
			return;
		}

		if ($check['class'] == 'vB5_Route_Search')
		{
			// We need to perform the fix

			$page = $this->db->query_first("
				SELECT pageid
				FROM " . TABLE_PREFIX . "page
				WHERE guid = 'vbulletin-4ecbdac82efb61.17736147'
			");

			if ($page)
			{
				$this->db->query_write("
					UPDATE " . TABLE_PREFIX . "routenew
					SET
						class = 'vB5_Route_Page',
						arguments = '" . serialize(array('pageid' => $page['pageid'])) . "',
						contentid = " . $page['pageid'] . ",
						guid = 'vbulletin-4ecbdacd6a8335.81846640'
					WHERE routeid = $check[routeid]
				");

				$this->db->query_write("
					UPDATE " . TABLE_PREFIX . "page
					SET routeid = $check[routeid]
					WHERE guid = 'vbulletin-4ecbdac82efb61.17736147'
				");
			}
		}

		$check = $this->db->query_first("SELECT routeid, class FROM " . TABLE_PREFIX . "routenew WHERE name = 'search'");

		if ($check AND $check['class'] == 'vB5_Route_Search')
		{
			// We need to perform the fix
			$this->skip_message();

			$page = $this->db->query_first("
				SELECT pageid
				FROM " . TABLE_PREFIX . "page
				WHERE guid = 'vbulletin-4ecbdac82f2815.04471586'
			");

			if ($page)
			{
				$this->run_query(sprintf($this->phrase['vbphrase']['update_table'], 'routenew'),"
					UPDATE " . TABLE_PREFIX . "routenew
					SET
						class = 'vB5_Route_Page',
						arguments = '" . serialize(array('pageid' => $page['pageid'])) . "',
						contentid = " . $page['pageid'] . ",
						guid = 'vbulletin-4ecbdacd6aa3b7.75359902'
					WHERE routeid = $check[routeid]
				");

				$this->run_query(sprintf($this->phrase['vbphrase']['update_table'], 'page'),"
					UPDATE " . TABLE_PREFIX . "page
					SET routeid = $check[routeid]
					WHERE guid = 'vbulletin-4ecbdac82f2815.04471586'
				");
			}
		}
		else
		{
			$this->skip_message();
		}
	}
}

class vB_Upgrade_500a17 extends vB_Upgrade_Version
{
	/** Make Attach contenttype not searchable*/
	public function step_1()
	{
		vB_Cache::instance()->purge('vb_types.types');
		$cansearch = $this->db->query_first('SELECT cansearch FROM ' . TABLE_PREFIX . 'contenttype WHERE class = "Attach"');
		if ($cansearch['cansearch'])
		{
			$this->run_query(
						sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'contenttype'),
						"UPDATE " . TABLE_PREFIX . "contenttype
						SET cansearch = '0'
						WHERE class = 'Attach'"
				);

		}
		else
		{
			$this->skip_message();
		}
	}
}

class vB_Upgrade_500a18 extends vB_Upgrade_Version
{
	/** Add additional request types.
	 *
	 */
	public function step_1()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'privatemessage', 1, 1),"
			ALTER TABLE " . TABLE_PREFIX . "privatemessage
			CHANGE about about ENUM('vote', 'vote_reply', 'rate', 'reply', 'follow', 'vm', 'comment', 'owner_to', 'owner_from', 'moderator', 'member')
		");
	}
}

class vB_Upgrade_500a19 extends vB_Upgrade_Version
{
	/** removing redundant CRC32 field */
	public function step_1()
	{
		if ($this->field_exists('searchlog', 'CRC32'))
		{
			$this->drop_field(
					sprintf($this->phrase['core']['altering_x_table'], 'searchlog', 1, 1),
					'searchlog',
					'CRC32'
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/** update nav bar blog link **/
	public function step_2()
	{
		$this->show_message($this->phrase['version']['500a17']['adding_blog_navbar_link']);
		$assertor = vB::getDbAssertor();
		$sites = $assertor->getRows('vBForum:site', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT));
		foreach ($sites as $site)
		{
			$headerNav = unserialize($site['headernavbar'])	;
			foreach ($headerNav as $key => $nav)
			{
				if (($nav['title'] == 'Blogs') AND ($nav['url'] == '#'))
				{
					$headerNav[$key]['url'] = 'blogs';
					$assertor->assertQuery('vBForum:site', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
					'siteid' => $site['siteid'], 'headernavbar' => serialize($headerNav)));
					break;
				}
			}

		}
	}

	/** Blog Posts were originally set to protected, but they shouldn't be. They should be visible. **/
	public function step_3()
	{
		try
		{
			$blogChannel = vB_Library::instance('Blog')->getBlogChannel();
			if (!empty($blogChannel))
			{
				$this->run_query(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'node'),
				"UPDATE " . TABLE_PREFIX . "node AS node INNER JOIN " . TABLE_PREFIX . "closure AS cl ON cl.child = node.nodeid
				AND cl.parent = $blogChannel
				SET node.protected = 0 ;");
			}
			else
			{
				$this->skip_message();
			}
		}
		catch (vB_Exception_Api $e)
		{
			$this->skip_message();
		}
	}
}

class vB_Upgrade_500a2 extends vB_Upgrade_Version
{
	public function step_1()
	{
		// insert two new config fields for the video module
		$exists = $this->db->query_first("
			SELECT name
			FROM " . TABLE_PREFIX . "widgetdefinition
			WHERE
				widgetid = 2
					AND
				name = 'provider'
		");
		if (!$exists)
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['update_table'], 'widgetdefinition'),
				"
					INSERT INTO `" . TABLE_PREFIX . "widgetdefinition`
					(`widgetid`, `field`, `name`, `label`, `defaultvalue`, `isusereditable`, `isrequired`, `displayorder`, `validationtype`, `validationmethod`, `data`)
					VALUES
					(2, 'Text', 'title', 'Video Title', 'Video Title', 1, 1, 1, '', '', ''),
					(2, 'Select', 'provider', 'Provider', 'youtube', 1, 1, 2, '', '', 'a:2:{s:7:\"youtube\";s:7:\"YouTube\";s:11:\"dailymotion\";s:11:\"DailyMotion\";}')
				"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_2()
	{
		// change display order and label for a video module config field
		$this->run_query(
			sprintf($this->phrase['vbphrase']['update_table'], 'widgetdefinition'),
			"
				UPDATE " . TABLE_PREFIX . "widgetdefinition
				SET
					label = 'Video ID',
					displayorder = 3
				WHERE
					widgetid = 2
						AND
					name = 'videoid'
			"
		);
	}
}

class vB_Upgrade_500a20 extends vB_Upgrade_Version
{
	/** Make Attach contenttype not searchable*/
	public function step_1()
	{
		$this->skip_message();
	}

	/** Adding inserting new Blog Phrase Type **/
	public function step_2()
	{
		$existing = vB::getDbAssertor()->getRow('phrasetype', array('fieldname' => 'vb5blog'));


		if (!$existing OR !empty($existing['errors']))
		{

			$this->run_query(
			sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'phrasetype'),
			"INSERT INTO " . TABLE_PREFIX . "phrasetype (fieldname, title, editrows, special)
			VALUES ('vb5blog', 'Blogs', 3, 0)"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/** Add a blog channel permission.**/
	public function step_3()
	{
		try
		{
			//see if there is one now.
			$nodeid = vB_Library::instance('Blog')->getBlogChannel();
			$showMessage = true;
			if (!empty($nodeid))
			{
				$assertor = vB::getDbAssertor();
				$existing = $assertor->getRow('vBForum:permission', array('groupid' => 2, 'nodeid' => $nodeid));
				if (empty($existing) OR !empty($existing['errors']))
				{
					$this->show_message($this->phrase['version']['500a20']['adding_blog_channel_permission']);
					$assertor->assertQuery('permission', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_INSERT,
					'groupid' => 2, 'nodeid' => $nodeid, 'forumpermissions' => 74461201,  'moderatorpermissions' => 0,
					'createpermissions' => 520195,  'edit_time' => 24,  'skip_moderate' => 1,  'maxtags' => 6,  'maxstartertags' => 3,  'maxothertags' => 3,
					'maxattachments' => 5));
					$showMessage = false;
				}
			}

			if ($showMessage)
			{
				$this->skip_message();
			}
		}
		catch (vB_Exception_Api $e)
		{
			$this->skip_message();
		}
	}

	/** Add enum for invite members **/
	public function step_4()
	{
		if ($this->field_exists('privatemessage', 'about'))
		{
			$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'privatemessage', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "privatemessage MODIFY COLUMN about ENUM('vote','vote_reply','rate','reply','follow','vm','comment','owner_to','moderator_to','owner_from','moderator','member', 'member_to', 'subscribe_content')
				"
			);
		}
		else
		{
			$this->skip_message();
		}

		$this->long_next_step();
	}

	/**
	 * Fixing blog page routes (VBV-618)
	 */
	public function step_5()
	{
		$this->skip_message();
		$this->long_next_step();
	}

	/**
	 * Fixing blogs to be moved when blog parent is modified  (VBV-529)
	 */
	public function step_6()
	{
		try
		{
			$blogChannel = vB_Library::instance('Blog')->getBlogChannel();
			if (!empty($blogChannel))
			{
				$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], 'node'));
				$channelContentTypeId = vB_Types::instance()->getContentTypeID('vBForum_Channel');
				vB::getDbAssertor()->update('vBForum:node', array('inlist' => 1), array('parentid' => $blogChannel, 'contenttypeid' => $channelContentTypeId));
			}
			else
			{
				$this->skip_message();
			}
		}
		catch (vB_Exception_Api $e)
		{
			$this->skip_message();
		}
	}

	/** removing redundant field in moderatorlog table */
	public function step_7()
	{
		if ($this->field_exists('moderatorlog', 'pollid'))
		{
			$this->drop_field(
					sprintf($this->phrase['core']['altering_x_table'], 'moderatorlog', 1, 2),
					'moderatorlog',
					'pollid'
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/** removing redundant field in moderatorlog table */
	public function step_8()
	{
		if ($this->field_exists('moderatorlog', 'attachmentid'))
		{
			$this->drop_field(
					sprintf($this->phrase['core']['altering_x_table'], 'moderatorlog', 2, 2),
					'moderatorlog',
					'attachmentid'
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/** removing redundant CRC32 field */
	public function step_9()
	{
		if ($this->field_exists('searchlog', 'CRC32'))
		{
			$this->drop_field(
					sprintf($this->phrase['core']['altering_x_table'], 'searchlog', 1, 1),
					'searchlog',
					'CRC32'
			);
		}
		else
		{
			$this->skip_message();
		}
	}
}

class vB_Upgrade_500a21 extends vB_Upgrade_Version
{
	/** Dummy step to show long next step message */
	public function step_1()
	{
		$this->skip_message();

		$this->long_next_step();
	}

	/**
	 * Removing channel widget
	 */
	public function step_2()
	{
		$assertor = vB::getDbAssertor();

		$channelWidget = $assertor->getRow('widget', array('guid' => 'vbulletin-widget_3-4eb423cfd69533.90014617'));

		if ($channelWidget)
		{
			// remove all instances
			$assertor->delete('widgetinstance', array('widgetid' => $channelWidget['widgetid']));

			// remove widget
			$assertor->delete('widget', array('widgetid' => $channelWidget['widgetid']));
		}
		else
		{
			$this->skip_message();
		}
	}
}

class vB_Upgrade_500a22 extends vB_Upgrade_Version
{
	/** migrate blog channels. First a blog channel per user **/
	public function step_1($data = [])
	{
		if (isset($this->registry->products['vbblog']) AND $this->registry->products['vbblog'])
		{
			$this->show_message(sprintf($this->phrase['vbphrase']['importing_from_x'], 'blog'));
			$startat = intval($data['startat'] ?? 0);
			$batchsize = 500;
			//we create a blog channel per user. So get a list of blogposts since our last update
			$assertor = vB::getDbAssertor();

			if ($startat == 0)
			{
				$query = $assertor->getRow('vBInstall:getMaxBlogUserId', array('contenttypeid' => 9985));
				$startat = intval($query['maxuserId']);
			}
			$blogs  = $assertor->assertQuery('vBInstall:getBlogs4Import', array('maxexisting' => $startat,
				'blocksize' => $batchsize));

			if (!$blogs->valid())
			{
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}
			$toImport = array();
			$owners = array();

			foreach($blogs AS $blog)
			{
				$toImport[$blog['blogid']] = $blog;
				$owners[$blog['userid']] = 0;
			}

			if (count($owners) < 1)
			{
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}

			vB_Upgrade::createAdminSession();
			$checkExisting = $assertor->assertQuery('vBForum:node',
				array(
					vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
					'parentid' => vB_Library::instance('blog')->getBlogChannel() ,
					'userid' => array_keys($owners)
				)
			);
			foreach ($checkExisting AS $existing)
			{
				$owners[$existing['userid']] = 1;
			}

			$blogLib = vB_Library::instance('blog');
			$channelLib = vB_Library::instance('content_channel');
			foreach ($toImport AS $blog)
			{
				if ($owners[$blog['userid']] == 0)
				{
					$blog['oldid'] = $blog['blogid'];
					$blog['oldcontenttypeid'] = '9999';
					$blog['publishdate'] = $blog['dateline'];
					$blog['showpublished'] = 1;
					$blog['created'] = $blog['dateline'];
					$blog['title'] = $blog['username'];
					$blog['urlident'] = $channelLib->getUniqueUrlIdent($blog['username']);
					$blogLib->createBlog($blog);
					$owners[$blog['userid']] = 1;
				}
			}

			$this->show_message(sprintf($this->phrase['core']['processed_records_x'], $batchsize));
			return array('startat' => max(array_keys($owners)));
		}
		else
		{
			$this->skip_message();
		}
	}

	/** migrate blog post starters **/
	public function step_2($data = NULL )
	{
		if (isset($this->registry->products['vbblog']) AND $this->registry->products['vbblog'])
		{
			vB_Upgrade::createAdminSession();
			$this->show_message(sprintf($this->phrase['vbphrase']['importing_from_x'], 'blog'));
			$batchsize = 500;
			//Get the highest post we've inserted.
			$assertor = vB::getDbAssertor();

			//we create a blog channel per user. So get a list of blogposts since our last update
			$assertor = vB::getDbAssertor();

			$query = $assertor->getRow('vBInstall:getMaxImportedPost', array('contenttypeid' => 9985));

			$startat = intval($query['maxid']);
			$textTypeId = vB_Types::instance()->getContentTypeID('vBForum_Text');

			/*** Blog starters. We need to insert the node records, text records, and closure records ***/
			$assertor->assertQuery('vBInstall:importBlogStarters', array('bloghome' => vB_Library::instance('blog')->getBlogChannel(),
				'batchsize' => $batchsize, 'startat' => $startat, 'texttype' => $textTypeId));

			$processed = $assertor->getRow('vBInstall:getProcessedCount', array());
			//set the starter
			$assertor->assertQuery('vBInstall:setStarter', array('startat' => $startat, 'contenttypeid' => 9985));
			$assertor->assertQuery('vBInstall:updateChannelRoutes', array('contenttypeid' => 9985, 'startat' => $startat,
				'batchsize' => 999999));

			//Now populate the text table
			if ($this->field_exists('blog_text', 'htmlstate'))
			{
				$assertor->assertQuery('vBInstall:importBlogText', array('contenttypeid' => 9985, 'startat' => $startat));
			}
			else
			{
				$assertor->assertQuery('vBInstall:importBlogTextNoState', array('contenttypeid' => 9985, 'startat' => $startat));
			}

			//Now the closure record for depth=0
			$assertor->assertQuery('vBInstall:addClosureSelf', array('contenttypeid' => 9985, 'startat' => $startat, 'batchsize' => $batchsize));

			//Add the closure records to root
			$assertor->assertQuery('vBInstall:addClosureParents', array('contenttypeid' => 9985, 'startat' => $startat, 'batchsize' => $batchsize));

			if (!$processed OR !empty($processed['errors']) OR (intval($processed['recs']) < 1))
			{
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}
			else
			{
				$this->show_message(sprintf($this->phrase['core']['processed_records_x'], $batchsize));
				return array('startat' => ($startat + 1));
			}
		}
		else
		{
			$this->skip_message();
		}
	}

	/** migrate blog post responses **/
	public function step_3($data = NULL )
	{
		if (isset($this->registry->products['vbblog']) AND $this->registry->products['vbblog'])
		{
			$this->show_message(sprintf($this->phrase['vbphrase']['importing_from_x'], 'blog'));
			$batchsize = 500;
			//Get the highest post we've inserted.
			$assertor = vB::getDbAssertor();

			$query = $assertor->getRow('vBInstall:getMaxImportedPost', array('contenttypeid' => 9984));
			$startat = intval($query['maxid']);
			$textTypeId = vB_Types::instance()->getContentTypeID('vBForum_Text');

			/*** Blog Responses. We need to insert the node records, text records, and closure records ***/
			$assertor->assertQuery('vBInstall:importBlogResponses', array('batchsize' => $batchsize,
				'startat' => $startat, 'texttypeid' => $textTypeId));

			$processed = $assertor->getRow('vBInstall:getProcessedCount', array());

			//Now populate the text table
			if ($this->field_exists('blog_text', 'htmlstate'))
			{
				$assertor->assertQuery('vBInstall:importBlogText', array('contenttypeid' => 9984, 'startat' => $startat));
			}
			else
			{
				$assertor->assertQuery('vBInstall:importBlogTextNoState', array('contenttypeid' => 9984, 'startat' => $startat));
			}


			//Now the closure record for depth=0
			$assertor->assertQuery('vBInstall:addClosureSelf', array('contenttypeid' => 9984, 'startat' => $startat, 'batchsize' => $batchsize));

			//Add the closure records to root
			$assertor->assertQuery('vBInstall:addClosureParents', array('contenttypeid' => 9984, 'startat' => $startat, 'batchsize' => $batchsize));
			if (!$processed OR !empty($processed['errors']) OR (intval($processed['recs']) < 1))
			{
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}
			else
			{
				$this->show_message(sprintf($this->phrase['core']['processed_records_x'], $batchsize));
				return array('startat' => ($startat + 1));
			}
		}
		else
		{
			$this->skip_message();
		}
	}
}

class vB_Upgrade_500a23 extends vB_Upgrade_Version
{
	// Change user's moderatefollowers option enabled by default
	public function step_1()
	{
		$useroptions = vB::getDatastore()->getValue('bf_misc_useroptions');

		if (isset($useroptions['moderatefollowers']))
		{
			$moderatefollowers = $useroptions['moderatefollowers'];
		}
		else
		{
			$moderatefollowers = 67108864;
		}

		$this->run_query(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'user'),
			"UPDATE " . TABLE_PREFIX . "user
			SET options = options | " . $moderatefollowers);
	}

	// Add moderatefollowers to defaultregoptions
	public function step_2()
	{
		$regoptions = vB::getDatastore()->getValue('bf_misc_regoptions');

		if (isset($regoptions['moderatefollowers']))
		{
			$moderatefollowers = $regoptions['moderatefollowers'];
		}
		else
		{
			$moderatefollowers = 134217728;
		}

		$this->run_query(
			sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'setting'),
			"UPDATE " . TABLE_PREFIX . "setting SET
			value = value | " . $moderatefollowers . "
			WHERE varname = 'defaultregoptions'"
		);
	}

	/** Adding styleid field for channels **/
	public function step_3()
	{
		$this->skip_message();
	}

	/** modifying default value for options field in channel **/
	public function step_4()
	{
		$this->skip_message();
	}

	/** migrating forum styleid and options **/
	public function step_5()
	{
		if ($this->tableExists('forum'))
		{
			$this->run_query(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'channel'),
				'UPDATE ' . TABLE_PREFIX . 'channel c
				INNER JOIN ' . TABLE_PREFIX . 'node n ON n.nodeid = c.nodeid
				INNER JOIN ' . TABLE_PREFIX . 'forum f ON f.forumid = n.oldid
				SET c.styleid = f.styleid, c.options = f.options');
		}
		else
		{
			$this->skip_message();
		}
	}
}

class vB_Upgrade_500a24 extends vB_Upgrade_Version
{
	/** we want lastcontent and lastcontentid to always have a value except for channels. **/
	public function step_1($data)
	{
		if (empty($data['startat']))
		{
			$this->show_message(sprintf($this->phrase['vbphrase']['update_table_x'], TABLE_PREFIX . 'node', 1, 1));
		}

		$callback = function($startat, $nextid)
		{
			$channelTypeId = vB_Types::instance()->getContentTypeId('vBForum_Channel');

			//I'm really not sure that this query is remotely correct -- it sets the lastcontent to the publish date
			//for any nodes that have lastcontent=0.  That's not right but it's possible that we correct that in a later step
			vB::getDbAssertor()->assertQuery('vBInstall:setNodeLastContent', array(
				'channelContenttypeid' => $channelTypeId,
				'startat' => $startat,
				'nextid' => $nextid,
			));
		};

		$batchsize = $this->getBatchSize('large', __FUNCTION__);
		return $this->updateByIdWalk($data, $batchsize, 'vBInstall:getMaxNodeid', 'vBForum:node', 'nodeid', $callback);
	}

	/** adding ipv6 fields to strike table **/
	public function step_2()
	{
		$this->skip_message();
	}

	/** update new ip fields with IPv4 addresses **/
	public function step_3()
	{
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'strikes'));

		$strikeIPs = $this->db->query_read("SELECT DISTINCT strikeip FROM " . TABLE_PREFIX . "strikes WHERE ip_1 = 0 AND ip_2 = 0 AND ip_3 = 0 AND ip_4 = 0");
		while ($strike = $this->db->fetch_array($strikeIPs))
		{
			if (vB_Ip::isValidIPv4($strike['strikeip']))
			{
				$ipFields = vB_Ip::getIpFields($strike['strikeip']);
				vB::getDbAssertor()->update('vBForum:strikes',
						array(
							'ip_4' => vB_dB_Type_UInt::instance($ipFields['ip_4']),
							'ip_3' => vB_dB_Type_UInt::instance($ipFields['ip_3']),
							'ip_2' => vB_dB_Type_UInt::instance($ipFields['ip_2']),
							'ip_1' => vB_dB_Type_UInt::instance($ipFields['ip_1'])
						),
						array('strikeip' => $strike['strikeip'])
				);
			}
		}
	}

	/** renaming the filter_conversations widget item**/
	public function step_4()
	{
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'widgetdefinition'));

		$home_template = vB::getDbAssertor()->getRow('pagetemplate', array('guid' => 'vbulletin-4ecbdac9370e30.09770013'));
		$home_activity_widget = vB::getDbAssertor()->getRow('widget', array('guid' => 'vbulletin-widget_4-4eb423cfd69899.61732480'));
		$existing = vB::getDbAssertor()->getRows('widgetdefinition', array('name' => 'filter_conversations'), false, 'widgetid');

		vB::getDbAssertor()->update('widgetdefinition', array('name' => 'filter_new_topics', 'defaultvalue' => '0', 'label' => 'Show New Topics?'), array('name' => 'filter_conversations'));
		vB::getDbAssertor()->update('widgetdefinition', array('defaultvalue' => '1'), array('name' => 'filter_new_topics', 'widgetid' => $home_activity_widget['widgetid']));

		if (!empty($existing))
		{
			$instances = vB::getDbAssertor()->assertQuery('widgetinstance', array('widgetid' => array_keys($existing)));
			foreach ($instances as $instance)
			{
				if (isset($adminconfig['filter_conversations']))
				{
					unset($adminconfig['filter_conversations']);
					$adminconfig['filter_new_topics'] = $instance['pagetemplateid'] == $home_template['pagetemplateid'] ? 1 : 0;
					$instances = vB::getDbAssertor()->update('widgetinstance', array('adminconfig' => serialize($adminconfig)), array('widgetinstanceid' => $instance['widgetinstanceid']));
				}
			}
		}
	}

	// Add ispublic field
	public function step_5()
	{
		$this->skip_message();
	}
}

class vB_Upgrade_500a25 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'cron'));
		vB::getDbAssertor()->delete('cron', array(
						array('field'=>'varname', 'value' => array('threadviews', 'attachmentviews'), vB_dB_Query::OPERATOR_KEY => vB_dB_Query::OPERATOR_EQ)
				));
	}

	/**
	 * Porting rssfeed table. (Using nodeid and changing enum field)
	 */
	public function step_2()
	{
		$alterSql = array();
		if ($this->field_exists('rssfeed', 'forumid'))
		{
			$alterSql[] = "CHANGE COLUMN forumid nodeid SMALLINT(5) UNSIGNED NOT NULL DEFAULT '0'";
		}

		if ($this->field_exists('rssfeed', 'threadactiondelay'))
		{
			$alterSql[] = "CHANGE COLUMN threadactiondelay topicactiondelay SMALLINT(5) UNSIGNED NOT NULL DEFAULT '0'";
		}

		if ($this->field_exists('rssfeed', 'itemtype'))
		{
			$alterSql[] = "CHANGE COLUMN itemtype itemtype ENUM('topic','announcement') NOT NULL DEFAULT 'topic'";
		}

		if (!empty($alterSql))
		{
			$sql = implode(', ' , $alterSql);
			$this->run_query(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'rssfeed'),
				"ALTER TABLE " . TABLE_PREFIX . "rssfeed $sql"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	 * Update with new rssfeed enum value
	 */
	public function step_3()
	{
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'rssfeed'));
		vB::getDbAssertor()->update('vBForum:rssfeed', array('itemtype' => 'topic'), array('itemtype' => ''));
	}

	/**
	 * Update rsslog with new values
	 */
	public function step_4()
	{
		$alterSql = array();
		if ($this->field_exists('rsslog', 'threadactiontime'))
		{
			$alterSql[] = "CHANGE COLUMN threadactiontime topicactiontime INT(10) UNSIGNED NOT NULL DEFAULT '0'";
		}

		if ($this->field_exists('rsslog', 'threadactioncomplete'))
		{
			$alterSql[] = "CHANGE COLUMN threadactioncomplete topicactioncomplete INT(10) UNSIGNED NOT NULL DEFAULT '0'";
		}

		if ($this->field_exists('rsslog', 'itemtype'))
		{
			$alterSql[] = "CHANGE COLUMN itemtype itemtype ENUM('topic','announcement') NOT NULL DEFAULT 'topic'";
		}

		if (!empty($alterSql))
		{
			$sql = implode(', ' , $alterSql);
			$this->run_query(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'rsslog'),
				"ALTER TABLE " . TABLE_PREFIX . "rsslog $sql"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	 * Update with new rsslog enum value
	 */
	public function step_5()
	{
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], 'rsslog'));
		vB::getDbAssertor()->update('vBForum:rsslog', array('itemtype' => 'topic'), array('itemtype' => ''));
	}
	/**
	 * Update session table
	 */
	public function step_6()
	{
		// Clear all sessions first, otherwise we can fail with "table full" error.
		$this->run_query(sprintf($this->phrase['vbphrase']['update_table'], 'session'),
			"TRUNCATE TABLE " . TABLE_PREFIX . "session"
		);

		if ($this->field_exists('session', 'nodeid'))
		{
			$this->drop_field(
				sprintf($this->phrase['core']['altering_x_table'], 'session', 1, 4),
				'session',
				'nodeid'
			);
		}
		else
		{
			$this->skip_message();
		}

		if ($this->field_exists('session', 'pageid'))
		{
			$this->drop_field(
				sprintf($this->phrase['core']['altering_x_table'], 'session', 2, 4),
				'session',
				'pageid'
			);
		}
		else
		{
			$this->skip_message();
		}

		if (!$this->field_exists('session', 'pagekey'))
		{
			$this->add_field(
				sprintf($this->phrase['core']['altering_x_table'], 'session', 3, 4),
				'session',
				'pagekey',
				'VARCHAR',
				array('null' => false, 'length' => 255)
			);
			$this->add_index(
				sprintf($this->phrase['core']['altering_x_table'], 'session', 4, 4),
				'session',
				'pagekey',
				array('pagekey')
			);
		}
		else
		{
			$this->skip_message();
		}
	}
}

class vB_Upgrade_500a27 extends vB_Upgrade_Version
{
	//Add userstylevar table
	public function step_1()
	{
		if (!$this->tableExists('userstylevar'))
		{
			$this->run_query(
					sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'userstylevar'),
					"CREATE TABLE " . TABLE_PREFIX . "userstylevar (
						stylevarid varchar(191) NOT NULL,
						userid int(6) NOT NULL DEFAULT '-1',
						value mediumblob NOT NULL,
						dateline int(10) NOT NULL DEFAULT '0',
						PRIMARY KEY  (stylevarid, userid)
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

	/* Add hook table for template hooks */
	public function step_2()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'hook'),
			"
				CREATE TABLE " . TABLE_PREFIX . "hook (
				hookid INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
				product VARCHAR(25) NOT NULL DEFAULT 'vbulletin',
				hookname VARCHAR(30) NOT NULL DEFAULT '',
				title VARCHAR(50) NOT NULL DEFAULT '',
				active TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
				hookorder TINYINT(3) UNSIGNED NOT NULL DEFAULT 10,
				template VARCHAR(30) NOT NULL DEFAULT '',
				arguments TEXT NOT NULL,
				PRIMARY KEY (hookid),
				KEY product (product, active, hookorder),
				KEY hookorder (hookorder)
			) ENGINE = " . $this->hightrafficengine . "
			",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}


	/* Add product column */
	public function step_3()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'widget', 1, 1),
			"ALTER  TABLE  " . TABLE_PREFIX . "widget ADD product VARCHAR(25) NOT NULL DEFAULT 'vbulletin'",
			self::MYSQL_ERROR_COLUMN_EXISTS
		);
	}


	/* Add product column */
	public function step_4()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'widgetdefinition', 1, 1),
			"ALTER  TABLE  " . TABLE_PREFIX . "widgetdefinition ADD product VARCHAR(25) NOT NULL DEFAULT 'vbulletin'",
			self::MYSQL_ERROR_COLUMN_EXISTS
		);
	}


	/* Add product index */
	public function step_5()
	{
		$this->add_index(
			sprintf($this->phrase['core']['create_index_x_on_y'], 'product', TABLE_PREFIX . 'widget'),
			'widget',
			'product',
			array('product')
		);
	}


	/* Add product index */
	public function step_6()
	{
		$this->add_index(
			sprintf($this->phrase['core']['create_index_x_on_y'], 'product', TABLE_PREFIX . 'widgetdefinition'),
			'widgetdefinition',
			'product',
			array('product')
		);
	}

	/* Get paymenttransaction table */
	public function step_7()
	{
		$this->skip_message();
	}

	/** Add nav bar Social Groups link **/
	public function step_8()
	{
		$this->show_message($this->phrase['version']['500a27']['adding_socialgroup_navbar_link']);
		$assertor = vB::getDbAssertor();
		$sites = $assertor->getRows('vBForum:site', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT));
		foreach ($sites as $site)
		{
			$headerNav = unserialize($site['headernavbar'])	;
			$foundSG = false;
			foreach ($headerNav as $key => $nav)
			{
				if (($nav['url'] == 'sghome') OR (($nav['url'] == 'social-groups') AND $foundSG))
				{
					unset($headerNav[$key]);
				}

				if ($nav['url'] == 'social-groups')
				{
					$foundSG = true;
				}
			}

			if ((!$foundSG))
			{
				$phrase = vB_Api::instanceInternal('phrase')->fetch(array('groups'));
				$headerNav[] = array('title' => $phrase['groups'], 'url' => 'social-groups', 'newWindow' => 0);
			}
			$assertor->assertQuery('vBForum:site', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
				'siteid' => $site['siteid'], 'headernavbar' => serialize($headerNav)));
		}
	}

	/** modifying "default" field in widgetdefinition **/
	public function step_9()
	{
		if ($this->field_exists('widgetdefinition', 'default'))
		{
			$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'widgetdefinition', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "widgetdefinition CHANGE COLUMN `default` defaultvalue BLOB NOT NULL"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/* Remove old products */
	public function step_10($data = [])
	{
		$startat = intval($data['startat'] ?? 0);

		$product = vB::getDbAssertor()->getRow('product');
		require_once(DIR . '/includes/adminfunctions_product.php');

		if ($product)
		{
			if (!$startat)
			{
				$this->show_message($this->phrase['version']['500a27']['products_removal']);
			}

			delete_product($product['productid']);
			$this->show_message(sprintf($this->phrase['version']['500a27']['removed_product'],$product['title']));
			return array('startat' => $startat+1);
		}
		else
		{
			if (!$startat)
			{
				$this->skip_message();
			}
			else
			{
				$this->show_message($this->phrase['version']['500a27']['products_removed']);
			}
		}
	}

	/** Importing vb4 profile stylevars to vb5 **/
	public function step_11()
	{
		if ($this->tableExists('customprofile'))
		{
			$this->show_message($this->phrase['version']['500a27']['mapping_vb4_vb5_profile']);

			$results = vB::getDbAssertor()->assertQuery('vBForum:customprofile', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT));

			$replaceValues = array();
			foreach($results as $profile_customization)
			{
				$stylevars = array();

				// Active Tabs
				if (!empty($profile_customization['module_background_color']))
				{
					$stylevars['module_tab_background_active']['color'] = $profile_customization['module_background_color'];
				}
				if (!empty($profile_customization['module_background_image']))
				{
					$stylevars['module_tab_background_active']['image'] = $profile_customization['module_background_image'];
				}
				if (!empty($profile_customization['module_background_repeat']))
				{
					$stylevars['module_tab_background_active']['repeat'] = $profile_customization['module_background_repeat'];
				}
				if (!empty($profile_customization['module_border']))
				{
					$stylevars['module_tab_border_active']['color'] = $profile_customization['module_border'];
				}
				if (!empty($profile_customization['module_text_color']))
				{
					$stylevars['module_tab_text_color_active']['color'] = $profile_customization['module_text_color'];
				}

				// Inactive Tabs
				if (!empty($profile_customization['moduleinactive_background_color']))
				{
					$stylevars['module_tab_background']['color'] = $profile_customization['moduleinactive_background_color'];
				}
				if (!empty($profile_customization['moduleinactive_background_image']))
				{
					$stylevars['module_tab_background']['image'] = $profile_customization['moduleinactive_background_image'];
				}
				if (!empty($profile_customization['moduleinactive_background_repeat']))
				{
					$stylevars['module_tab_background']['repeat'] = $profile_customization['moduleinactive_background_repeat'];
				}
				if (!empty($profile_customization['moduleinactive_border']))
				{
					$stylevars['module_tab_border']['color'] = $profile_customization['moduleinactive_border'];
				}
				if (!empty($profile_customization['moduleinactive_text_color']))
				{
					$stylevars['module_tab_text_color']['color'] = $profile_customization['moduleinactive_text_color'];
				}

				// Buttons
				if (!empty($profile_customization['button_background_color']))
				{
					$stylevars['profile_button_primary_background']['color'] = $profile_customization['button_background_color'];
				}
				if (!empty($profile_customization['button_background_image']))
				{
					$stylevars['profile_button_primary_background']['image'] = $profile_customization['button_background_image'];
				}
				if (!empty($profile_customization['button_background_repeat']))
				{
					$stylevars['profile_button_primary_background']['repeat'] = $profile_customization['button_background_repeat'];
				}
				if (!empty($profile_customization['button_border']))
				{
					$stylevars['button_primary_border']['color'] = $profile_customization['button_border'];
				}
				if (!empty($profile_customization['button_text_color']))
				{
					$stylevars['button_primary_text_color']['color'] = $profile_customization['button_text_color'];
				}

				// Content
				if (!empty($profile_customization['content_background_color']))
				{
					$stylevars['profile_content_background']['color'] = $profile_customization['content_background_color'];
				}
				if (!empty($profile_customization['content_background_image']))
				{
					$stylevars['profile_content_background']['image'] = $profile_customization['content_background_image'];
				}
				if (!empty($profile_customization['content_background_repeat']))
				{
					$stylevars['profile_content_background']['repeat'] = $profile_customization['content_background_repeat'];
				}
				if (!empty($profile_customization['content_border']))
				{
					$stylevars['profile_content_border']['color'] = $profile_customization['content_border'];
				}
				if (!empty($profile_customization['content_text_color']))
				{
					$stylevars['profile_content_primarytext']['color'] = $profile_customization['content_text_color'];
				}
				if (!empty($profile_customization['content_link_color']))
				{
					$stylevars['profile_content_linktext']['color'] = $profile_customization['content_link_color'];
				}

				// Content Headers
				if (!empty($profile_customization['headers_background_color']))
				{
					$stylevars['profile_section_background']['color'] = $profile_customization['headers_background_color'];
				}
				if (!empty($profile_customization['headers_background_image']))
				{
					$stylevars['profile_section_background']['image'] = $profile_customization['headers_background_image'];
				}
				if (!empty($profile_customization['headers_background_repeat']))
				{
					$stylevars['profile_section_background']['repeat'] = $profile_customization['headers_background_repeat'];
				}
				if (!empty($profile_customization['headers_border']))
				{
					$stylevars['profile_section_border']['color'] = $profile_customization['headers_border'];
				}
				if (!empty($profile_customization['headers_text_color']))
				{
					$stylevars['profile_section_text_color']['color'] = $profile_customization['headers_text_color'];
				}

				foreach ($stylevars as $stylevar => $value)
				{
					$replaceValues[] = array(
						'stylevarid' => $stylevar,
						'userid' => $profile_customization['userid'],
						'value' => serialize($value),
						'dateline' => vB::getRequest()->getTimeNow()
					);
				}
			}
			vB::getDbAssertor()->assertQuery('insertignoreValues', array('table' => 'userstylevar', 'values' => $replaceValues));
		}
		else
		{
			$this->skip_message();
		}
	}

	/** Step 12 removed, it was a duplicate of stuff done in step 8 **/
}

class vB_Upgrade_500a28 extends vB_Upgrade_Version
{
	/**
	* Add oldfolderid field to messagefolder table
	*/
	public function step_1()
	{
		if (!$this->field_exists('messagefolder', 'oldfolderid'))
		{

			$this->add_field(
				sprintf($this->phrase['core']['altering_x_table'], 'messagefolder ', 1, 2),
				'messagefolder',
				'oldfolderid',
				'tinyint',
				array('null' => true, 'default' => NULL)
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	* Add UNIQUE index to the userid, oldfolderid pair on the messagefolder table
	* For ensuring no duplicate imports from vb4 custom folders
	*/
	public function step_2()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'messagefolder', 2, 2),
			'messagefolder',
			'userid_oldfolderid',
			array('userid', 'oldfolderid'),
			'unique'
		);
	}

	/**
	 * Importing custom folders
	 */
	public function step_3($data = [])
	{
		$assertor = vB::getDbAssertor();
		$batchsize = 1000;
		$startat = intval($data['startat'] ?? 0);

		// Check if any users have custom folders
		if (!empty($data['totalUsers']))
		{
			$totalUsers = $data['totalUsers'];
		}
		else
		{
			// Get the number of users that has custom pm folders
			$totalUsers = $assertor->getRow('vBInstall:getTotalUsersWithFolders');
			$totalUsers = intval($totalUsers['totalusers']);

			if (intval($totalUsers) < 1)
			{
				$this->skip_message();
				return;
			}
			else
			{
				$this->show_message($this->phrase['version']['500b27']['importing_custom_folders']);
			}
		}

		if ($startat >= $totalUsers)
		{
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			return;
		}

		// Get the users for import
		// The messaging could be improved here and the query is probably inefficient if we have a lot of
		// records/batches to process.  But I really need a better example dataset to do something about it.
		$this->show_message(sprintf($this->phrase['core']['processing_records_x'], $batchsize));
		$users = $assertor->getRows('vBInstall:getUsersWithFolders', array('startat' => $startat, 'batchsize' => $batchsize));
		$insertValues = array();
		foreach ($users AS $user)
		{
			$pmFolders = unserialize($user['pmfolders']);

			//this is probably due to some weird charset mismatch messing with the serialization
			//but if we can't unsearilize the value we got then we end up with messy
			if (is_array($pmFolders))
			{
				foreach ($pmFolders AS $folderid => $title)
				{
					$insertValues[] = array(
						'userid' => $user['userid'],
						'title' => $title,
						'oldfolderid' => $folderid,
					);
				}
			}
		}

		//its technically possible to get here without adding anything to the array -- either because of
		//serialization errors or somehow getting empty arrays in the DB.  Let's go ahead and check out
		//of an abundance of caution.
		if ($insertValues)
		{
			$assertor->assertQuery('insertignoreValues', array('table' => 'messagefolder', 'values' => $insertValues));
		}

		return array('startat' => ($startat + $batchsize), 'totalUsers' => $totalUsers);
	}

	/** Create the "sent" private message folders*/
	public function step_4($data = array())
	{
		if ($this->tableExists('pm') AND $this->tableExists('pmtext'))
		{
			$db = vB::getDbAssertor();

			if (empty($data['startat']))
			{
				$this->show_message(sprintf($this->phrase['version']['500a28']['importing_privatemessages'], 1, 4));

				//I'm not 100% sure what this is about.  It *appears* that we can already a have sent_item folders and
				//nothing will stop us from adding another.  So we find the last user that already has one and assume
				//that we already have what we need for earlier users.  It's not clear that this is a good assumption
				//and it would perhaps be better if we fixed the insert query to avoid duplicates.  But that's a much
				//nastier fix and its been this way for a very long time.
				$maxvB5 = $db->getRow('vBInstall:getMaxPMFolderUser', array('titlephrase' => 'sent_items'));
				if (!empty($maxvB5) AND !empty($maxvB5['maxid']))
				{
					//we want to start *after* the largest already existing user
					$data['startat'] = $maxvB5['maxid'] + 1;
				}
			}

			$callback = function($startat, $nextid) use($db)
			{
				$db->assertQuery('vBInstall:createPMFoldersSent', array(
					'startat' => $startat,
					'nextid' => $nextid
				));
			};

			//note while we iterate over the user table we take as the max the largest userid with a sent message.
			//this works because we're actually querying the pmtext table sender id (so we don't need to check any
			//userids that don't appear in that table).
			$batchsize = $this->getBatchSize('small', __FUNCTION__);
			return $this->updateByIdWalk($data, $batchsize, 'vBInstall:getMaxPMSenderid', 'user', 'userid', $callback);
		}
		else
		{
			$this->skip_message();
		}

	}

	/** Create the "messages" private message folders*/
	public function step_5($data = array())
	{
		if ($this->tableExists('pm') AND $this->tableExists('pmtext'))
		{
			$db = vB::getDbAssertor();

			if (empty($data['startat']))
			{
				$this->show_message(sprintf($this->phrase['version']['500a28']['importing_privatemessages'], 2, 4));

				//I'm not 100% sure what this is about.  It *appears* that we can already a have messages folders and
				//nothing will stop us from adding another.  So we find the last user that already has one and assume
				//that we already have what we need for earlier users.  It's not clear that this is a good assumption
				//and it would perhaps be better if we fixed the insert query to avoid duplicates.  But that's a much
				//nastier fix and its been this way for a very long time.
				$maxvB5 = $db->getRow('vBInstall:getMaxPMFolderUser', array('titlephrase' => 'messages'));
				if (!empty($maxvB5) AND !empty($maxvB5['maxid']))
				{
					//we want to start *after* the largest already existing user
					$data['startat'] = $maxvB5['maxid'] + 1;
				}
			}

			$callback = function($startat, $nextid) use($db)
			{
				$db->assertQuery('vBInstall:createPMFoldersMsg', array(
					'startat' => $startat,
					'nextid' => $nextid
				));
			};

			//note while we iterate over the user table we take as the max the largest userid with a sent message.
			//this works because we're actually querying the pmtext table sender id (so we don't need to check any
			//userids that don't appear in that table).
			return $this->updateByIdWalk($data, 5000, 'vBInstall:getMaxPMSenderid', 'user', 'userid', $callback);
		}
		else
		{
			$this->skip_message();
		}
	}

	/** Import private messages with no starters */
	public function step_6($data = [])
	{
		if ($this->tableExists('pm') AND $this->tableExists('pmtext'))
		{
			//I'd like to move this to updateByIdWalk and we probably can.  But there are a lot of queries and
			//the principle of "first do no harm" suggests leaving this alone and just fixing the messaging.

			$assertor = vB::getDbAssertor();
			$batchsize = 5000;

			if (empty($data['startat']))
			{
				$this->show_message(sprintf($this->phrase['version']['500a28']['importing_privatemessages'], 3, 4));
			}

			$startat = intval($data['startat'] ?? 0);

			//First see if we need to do something. Maybe we're O.K.
			if (!empty($data['maxvB4']))
			{
				$maxPMTid = $data['maxvB4'];
			}
			else
			{
				$maxPMTid = $assertor->getRow('vBInstall:getMaxPMStarter', array());
				$maxPMTid = intval($maxPMTid['maxid']);
				//If we don't have any threads, we're done.
				if (intval($maxPMTid) < 1)
				{
					$this->skip_message();
					return;
				}
			}

			if ($startat == 0)
			{
				$maxvB5 = $assertor->getRow('vBInstall:getMaxImportedPost', array('contenttypeid' => 9989));

				if (!empty($maxvB5) AND !empty($maxvB5['maxid']))
				{
					$startat = $maxvB5['maxid'];
				}
			}

			if ($startat >= $maxPMTid)
			{
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}

			$nodeLib = vB_Library::instance('node');
			$pmHomeid = $nodeLib->fetchPMChannel();
			$pmHome = $nodeLib->getNode($pmHomeid);
			$assertor->assertQuery('vBInstall:importPMStarter', array(
				'startat' => $startat,
				'batchsize' => $batchsize,
				'pmRouteid' => $pmHome['routeid'],
				'privatemessageType' => vB_Types::instance()->getContentTypeID('vBForum_PrivateMessage'),
				'privateMessageChannel' => $pmHomeid
			));

			$assertor->assertQuery('vBInstall:setPMStarter', array('startat' => $startat, 'batchsize' => $batchsize, 'contenttypeid' => 9989));
			$assertor->assertQuery('vBInstall:importPMText', array('startat' => $startat, 'batchsize' => $batchsize, 'contenttypeid' => 9989));
			$assertor->assertQuery('vBInstall:importPMMessage', array('startat' => $startat, 'batchsize' => $batchsize, 'contenttypeid' => 9989));

			$assertor->assertQuery('vBInstall:importPMSent', array('startat' => $startat, 'batchsize' => $batchsize, 'contenttypeid' => 9989));
			$assertor->assertQuery('vBInstall:importPMInbox', array('startat' => $startat, 'batchsize' => $batchsize, 'contenttypeid' => 9989));
			$assertor->assertQuery('vBInstall:addClosureSelf', array('startat' => $startat, 'batchsize' => $batchsize, 'contenttypeid' => 9989));
			$assertor->assertQuery('vBInstall:addClosureParents', array('startat' => $startat, 'batchsize' => $batchsize, 'contenttypeid' => 9989));
			$assertor->assertQuery('vBInstall:updateChannelRoutes', array('startat' => $startat, 'batchsize' => $batchsize, 'contenttypeid' => 9989));

			$this->show_message(sprintf($this->phrase['core']['processed_records_x_y_z'], $startat + 1, ($startat + $batchsize), $maxPMTid), true);

			return array('startat' => ($startat + $batchsize), 'maxvB4' => $maxPMTid);
		}
		else
		{
			$this->skip_message();
		}
	}


	/** Import private messages with starters*/
	public function step_7($data = array())
	{
		if ($this->tableExists('pm') AND $this->tableExists('pmtext'))
		{
			//I'd like to move this to updateByIdWalk and we probably can.  But there are a lot of queries and
			//the principle of "first do no harm" suggests leaving this alone and just fixing the messaging.
			//This even more so that the last step.

			/** Here we iterate for two reasons:
			 The outer loop is for the standard reason- to limit the number of queries and make sure we don't timeout.
			 *
			 * But also- here we are importing a hierarchical structure, which we need to maintain. So if we're importing
			 * pmtextid's 5,000- 10,000, but node 9999 may be a child of 9997 which is a child of 9996, etc.
			 *
			 * Simple example: A sends emails to B and C.
			 * B replies to A and C, C replies to B and A
			 * C replies to B, A replies to B
			 * B replies to A
			 *
			 * Now at each step we record the highest node id, and at the next import query we only want children of
			 * parent nodes higher than that.
			 *
			 * The highest existing pm nodeid is 1000. We run and import A's email
			 * Max existing pmid is now 1001. Second run skips A but imports B's and C's replies
			 * Max existing pmid is now 1003. Third run skips the three imported nodes and imports C's and A's replies
			 * Max existing pmid is now 1005. Fourth run skips the five existing nodes and run imports B's reply
			 * Max existing pmid is now 1006. Fifth run imports nothing, so the updates at the end of the group run and
			 * 	we run the queries at the end.
			 *
			 * Often the parentid will be outside the current block. But since it will have already been imported, and the
			 * range limit is on the child, that won't result in lost data.
			 */

			$assertor = vB::getDbAssertor();
			$batchsize = 2000;

			if (empty($data['startat']))
			{
				$this->show_message(sprintf($this->phrase['version']['500a28']['importing_privatemessages'], 4, 4));
			}

			if (isset($data['startat']))
			{
				$startat = $data['startat'];
			}

			//First see if we need to do something. Maybe we're O.K.
			if (!empty($data['maxvB4']))
			{
				$maxPMTid = $data['maxvB4'];
			}
			else
			{
				$maxPMTid = $assertor->getRow('vBInstall:getMaxPMResponse', array());
				$maxPMTid = intval($maxPMTid['maxid']);
				//If we don't have any threads, we're done.
				if (intval($maxPMTid) < 1)
				{
					$this->skip_message();
					return;
				}
			}

			if (!isset($startat))
			{
				$maxvB5 = $assertor->getRow('vBInstall:getMaxImportedPost', array('contenttypeid' => 9981));

				if (!empty($maxvB5) AND !empty($maxvB5['maxid']))
				{
					$startat = $maxvB5['maxid'];
				}
				else
				{
					$startat = 1;
				}
			}

			if ($startat >= $maxPMTid)
			{
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}

			//See if we have any nodes to import in this block.
			$lastMaxId = 0;
			$processed = array('recs' => 1);
			$processedCount = -1;
			while (!empty($processed) AND !empty($processed['recs']))
			{
				$processedCount += $processed['recs'];
				//We have to see if we have more to import.(empty($maxNode) OR !empty($maxNode['errors']))
				$maxNode = $assertor->getRow('vBInstall:getMaxPMNodeid', array());

				if (empty($maxNode) OR !empty($maxNode['errors']))
				{
					$maxNodeid = 0;
				}
				else
				{
					$maxNodeid = $maxNode['maxid'];
				}
				$assertor->assertQuery('vBInstall:importPMResponse', array(
					'startat' => $startat,
					'batchsize' => $batchsize,
					'privatemessageType' => vB_Types::instance()->getContentTypeID('vBForum_PrivateMessage'),
					'maxNodeid' => $lastMaxId
				));
				$processed = $assertor->getRow('vBInstall:getProcessedCount', array());
				$lastMaxId = $maxNodeid;
			}

			//If we didn't import any records, don't bother to run these queries
			if ($processed > 0)
			{
				$assertor->assertQuery('vBInstall:setResponseStarter', array('startat' => $startat, 'batchsize' => $batchsize, 'contenttypeid' => 9981));
				$assertor->assertQuery('vBInstall:importPMText', array('startat' => $startat, 'batchsize' => $batchsize, 'contenttypeid' => 9981));
				$assertor->assertQuery('vBInstall:importPMMessage', array('startat' => $startat, 'batchsize' => $batchsize, 'contenttypeid' => 9981));

				$assertor->assertQuery('vBInstall:importPMSent', array('startat' => $startat, 'batchsize' => $batchsize, 'contenttypeid' => 9981));
				$assertor->assertQuery('vBInstall:importPMInbox', array('startat' => $startat, 'batchsize' => $batchsize, 'contenttypeid' => 9981));
				$assertor->assertQuery('vBInstall:addClosureSelf', array('startat' => $startat, 'batchsize' => $batchsize, 'contenttypeid' => 9981));
				$assertor->assertQuery('vBInstall:addClosureParents', array('startat' => $startat, 'batchsize' => $batchsize, 'contenttypeid' => 9981));
			}

			$this->show_message(sprintf($this->phrase['core']['processed_records_x_y_z'], $startat + 1, ($startat + $batchsize), $maxPMTid), true);

			return array('startat' => ($startat + $batchsize), 'maxvB4' => $maxPMTid);
		}
		else
		{
			$this->skip_message();
		}
	}

	/** Drop plugins column **/
	public function step_8()
	{
		if ($this->field_exists('language', 'phrasegroup_plugins'))
		{
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'language', 1, 2),
				"ALTER TABLE " . TABLE_PREFIX . "language DROP COLUMN phrasegroup_plugins"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/** Add hooks column **/
	public function step_9()
	{
		if (!$this->field_exists('language', 'phrasegroup_hooks'))
		{
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'language', 2, 2),
				"ALTER TABLE " . TABLE_PREFIX . "language ADD COLUMN phrasegroup_hooks MEDIUMTEXT NULL"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/** Update phrases **/
	public function step_10()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'phrase', 1, 1),
			"UPDATE " . TABLE_PREFIX . "phrase SET fieldname = 'hooks' WHERE fieldname = 'plugins'"
		);
	}

	/** Update phrasetypes **/
	public function step_11()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'phrasetype', 1, 2),
			"DELETE FROM " . TABLE_PREFIX . "phrasetype WHERE fieldname = 'plugins'"
		);
	}

	/** Update phrasetypes **/
	public function step_12()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'phrasetype', 2, 2),
			"INSERT IGNORE INTO " . TABLE_PREFIX . "phrasetype (fieldname, title, editrows) VALUES ('hooks', 'Hooks System', 3)"
		);
	}

	/** Add additional request types.
	 *
	 */
	public function step_13()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'privatemessage', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "privatemessage CHANGE about about ENUM('vote', 'vote_reply', 'rate', 'reply', 'follow', 'vm', 'comment',
				'" . vB_Api_Node::REQUEST_TAKE_OWNER ."',
				'" . vB_Api_Node::REQUEST_TAKE_MODERATOR ."',
				'" . vB_Api_Node::REQUEST_GRANT_OWNER ."',
				'" . vB_Api_Node::REQUEST_GRANT_MODERATOR ."',
				'" . vB_Api_Node::REQUEST_GRANT_MEMBER ."',
				'" . vB_Api_Node::REQUEST_TAKE_MEMBER ."',
				'" . vB_Api_Node::REQUEST_SG_TAKE_OWNER ."',
				'" . vB_Api_Node::REQUEST_SG_TAKE_MODERATOR ."',
				'" . vB_Api_Node::REQUEST_SG_GRANT_OWNER ."',
				'" . vB_Api_Node::REQUEST_SG_GRANT_MODERATOR ."',
				'" . vB_Api_Node::REQUEST_SG_GRANT_MEMBER ."',
				'" . vB_Api_Node::REQUEST_SG_TAKE_MEMBER ."'); "
		);
	}

	public function step_14()
	{
		$this->skip_message();
		return;
	}

	/** make sure we have a social group channel */
	public function step_15()
	{
		if ($this->tableExists('socialgroup') AND $this->tableExists('discussion') AND $this->tableExists('groupmessage'))
		{
			//Make sure we have a session
			vB_Upgrade::createAdminSession();
			$guid = vB_Channel::DEFAULT_SOCIALGROUP_PARENT;
			$assertor = vB::getDbAssertor();
			$existing = $assertor->getRow('vBForum:channel', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT, 'guid' => $guid));
			if (empty($existing) OR !empty($existing['errors']))
			{
				$this->show_message($this->phrase['version']['500a28']['creating_socialgroup_channel']);
				$channelLib = vB_Library::instance('content_channel');
				$data = array('parentid'=> 1, 'oldid' => 2, 'oldcontenttypeid' => 9994, 'guid' => $guid, 'title' => 'Social Group');
				$options = array('skipNotifications' => true, 'skipFloodCheck' => true, 'skipDupCheck' => true);
				$channelLib->add($data, $options);
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

	/** Importing Visitor Messages **/
	public function step_16($data = [])
	{
		if ($this->tableExists('visitormessage'))
		{
			$assertor = vB::getDbAssertor();
			$batchsize = 5000;
			$this->show_message($this->phrase['version']['500a28']['importing_visitor_messages']);
			$this->show_message(sprintf($this->phrase['core']['processing_records_x'], $batchsize));
			$startat = intval($data['startat'] ?? 0);
			$textTypeid = vB_Types::instance()->getContentTypeID('vBForum_Text');
			$vMTypeid = vB_Types::instance()->getContentTypeID('vBForum_VisitorMessage');

			//First see if we need to do something. Maybe we're O.K.
			if (!empty($data['maxvB4']))
			{
				$max4VM = $data['maxvB4'];
			}
			else
			{
				$max4VM = $assertor->getRow('vBInstall:getMax4VM', array());
				$max4VM = intval($max4VM['maxid']);
				//If we don't have any threads, we're done.
				if (intval($max4VM) < 1)
				{
					$this->skip_message();
					return;
				}
			}

			if ($startat == 0)
			{
				$maxvB5 = $assertor->getRow('vBInstall:getMaxImportedPost', array('contenttypeid' => $vMTypeid));

				if (!empty($maxvB5) AND !empty($maxvB5['maxid']))
				{
					$startat = $maxvB5['maxid'];
				}
			}

			if ($startat >= $max4VM)
			{
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}

			$nodeLib = vB_Library::instance('node');
			$vmHomeid = $nodeLib->fetchVMChannel();
			$vmHome = $nodeLib->getNode($vmHomeid);
			$assertor->assertQuery('vBInstall:ImportVisitorMessages', array('startat' => $startat, 'batchsize' => $batchsize,
				'vmRouteid' => $vmHome['routeid'], 'visitorMessageType' => $vMTypeid,
				'vmChannel' => $vmHomeid, 'texttypeid' => $textTypeid));
			$assertor->assertQuery('vBInstall:importVMText', array('startat' => $startat, 'batchsize' => $batchsize, 'visitorMessageType' => $vMTypeid));
			$assertor->assertQuery('vBInstall:addClosureSelf', array('startat' => $startat, 'batchsize' => $batchsize, 'contenttypeid' => $vMTypeid));
			$assertor->assertQuery('vBInstall:addClosureParents', array('startat' => $startat, 'batchsize' => $batchsize, 'contenttypeid' => $vMTypeid));
			$assertor->assertQuery('vBInstall:updateChannelRoutes', array('contenttypeid' => $vMTypeid, 'startat' => $startat, 'batchsize' => $batchsize));
			$assertor->assertQuery('vBInstall:setStarter', array('contenttypeid' => $vMTypeid, 'startat' => $startat));
			return array('startat' => ($startat + $batchsize), 'maxvB4' => $max4VM);
		}
		else
		{
			$this->skip_message();
		}
	}

	/** Importing Albums **/
	public function step_17($data = [])
	{
		if ($this->tableExists('album') AND $this->tableExists('attachment')  AND $this->tableExists('filedata'))
		{
			$assertor = vB::getDbAssertor();
			$batchSize = 1000;
			$startat = intval($data['startat'] ?? 0);
			$albumTypeid = vB_Types::instance()->getContentTypeID('vBForum_Album');

			/*
			 * 	$startat starts at 0 (or oldid of last, already imported album)
			 *	and increments by $batchSize every iteration
			 * 	The batch limit is ($startat + 1) to ($startat + $batchSize), inclusive
			 */
			if ($startat == 0)
			{
				$maxvB5 = $assertor->getRow('vBInstall:getMaxvB5Album', array('albumtypeid' => $albumTypeid));
				$startat = intval($maxvB5['maxid']);
			}

			if (!empty($data['maxvB4']))
			{
				$maxvB4 = intval($data['maxvB4']);
			}
			else
			{
				$maxvB4 = $assertor->getRow('vBInstall:getMaxvB4Album', array());
				$maxvB4 = intval($maxvB4['maxid']);

				//If we don't have any posts, we're done.
				if ($maxvB4 < 1)
				{
					$this->skip_message();
					return;
				}
			}

			if ($maxvB4 <= $startat)
			{
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}

			$this->show_message($this->phrase['version']['500a28']['importing_albums']);
			$albumChannel = vB_Library::instance('node')->fetchAlbumChannel();
			$route = $assertor->getRow('routenew',
				array(
					'contentid' => $albumChannel,
					'class' => 'vB5_Route_Conversation',
				)
			);

			$assertor->assertQuery('vBInstall:importAlbumNodes',
				array('albumtype' => $albumTypeid, 'startat' => $startat, 'batchsize' => $batchSize,
				'gallerytype' => vB_Types::instance()->getContentTypeID('vBForum_Gallery'),
				'albumChannel' => $albumChannel, 'routeid' => $route['routeid']));

			$assertor->assertQuery('vBInstall:importAlbums2Gallery',
				array('albumtype' => $albumTypeid, 'startat' => $startat, 'batchsize' => $batchSize));

			$assertor->assertQuery('vBInstall:addClosureSelf',
				array('startat' => $startat, 'batchsize' => $batchSize, 'contenttypeid' => $albumTypeid));

			$assertor->assertQuery('vBInstall:addClosureParents',
				array('startat' => $startat, 'batchsize' => $batchSize, 'contenttypeid' => $albumTypeid));

			$this->show_message(sprintf($this->phrase['core']['processed_records_x'], $batchSize));


			return array('startat' => ($startat + $batchSize));
		}
		else
		{
			$this->skip_message();
		}
	}

	/** Importing Photos **/
	public function step_18($data = [])
	{
		if ($this->tableExists('album') AND $this->tableExists('attachment') AND $this->tableExists('filedata'))
		{
			$assertor = vB::getDbAssertor();
			$batchSize = 5000;
			$startat = intval($data['startat'] ?? 0);
			$photoTypeid = vB_Types::instance()->getContentTypeID('vBForum_Photo');
			$albumTypeid = vB_Types::instance()->getContentTypeID('vBForum_Album');

			if ($startat == 0)
			{
				$maxvB5 = $assertor->getRow('vBInstall:getMaxImportedPost', array('contenttypeid' => vB_Api_ContentType::OLDTYPE_PHOTO));
				$startat = intval($maxvB5['maxid']);
			}

			if (!empty($data['maxvB4']))
			{
				$maxvB4 = intval($data['maxvB4']);
			}
			else
			{
				$maxvB4 = $assertor->getRow('vBInstall:getMaxvB4Photo', array('albumtype' => $albumTypeid));
				$maxvB4 = intval($maxvB4['maxid']);

				//If we don't have any posts, we're done.
				if ($maxvB4 < 1)
				{
				$this->skip_message();
				return;
			}
			}

			if ($maxvB4 <= $startat)
			{
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}
			$this->show_message($this->phrase['version']['500a28']['importing_photos']);
			$assertor->assertQuery('vBInstall:importPhotoNodes',
				array('albumtype' => $albumTypeid, 'startat' => $startat, 'batchsize' => $batchSize,
					'phototype' => $photoTypeid));

			$assertor->assertQuery('vBInstall:importPhotos2Gallery',
				array('albumtype' => $albumTypeid, 'startat' => $startat, 'batchsize' => $batchSize,
					'gallerytype' => vB_Types::instance()->getContentTypeID('vBForum_Gallery')));

			$assertor->assertQuery('vBInstall:addClosureSelf',
				array('startat' => $startat, 'batchsize' => $batchSize, 'contenttypeid' => vB_Api_ContentType::OLDTYPE_PHOTO));

			$assertor->assertQuery('vBInstall:addClosureParents',
				array('startat' => $startat, 'batchsize' => $batchSize, 'contenttypeid' => vB_Api_ContentType::OLDTYPE_PHOTO));


			$this->show_message(sprintf($this->phrase['core']['processed_records_x'], $batchSize));

			return array('startat' => ($startat + $batchSize));
		}
		else
		{
			$this->skip_message();
		}
	}

	/** Add subscribe message types to about */
	public function step_19()
	{
		if ($this->field_exists('privatemessage', 'about'))
		{
			$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'privatemessage', 1, 1),
				"ALTER TABLE " . TABLE_PREFIX . "privatemessage MODIFY COLUMN about ENUM('vote','vote_reply','rate','reply','follow','vm','comment','owner_to','moderator_to','owner_from','moderator','member', 'member_to', 'subscriber', 'subscriber_to', 'sg_subscriber', 'sg_subscriber_to')"
			);
		}
		else
		{
			$this->skip_message();
		}

		$this->long_next_step();
	}

	/* Change any pagetemplates that use the 50/50 screenlayout to use the 70/30 screenlayout
	*/
	public function step_20()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'pagetemplate', 1, 1),
			"UPDATE " . TABLE_PREFIX . "pagetemplate SET screenlayoutid = 2 WHERE screenlayoutid = 3"
		);
	}

	/**
	 * Remove the 50/50 screenlayout (screenlayout 3)
	 */
	public function step_21()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'screenlayout', 1, 1),
			"DELETE FROM " . TABLE_PREFIX . "screenlayout WHERE screenlayoutid = 3"
		);
	}

	public function step_22()
	{
		//This used to move the annoucement widget to a differnet place.  But not only will
		//they be removed, they may not have been installed because the step that does that
		//relies on the XML files. So just punt.
		$this->skip_message();
	}

	/**
	 * Add latest group topics widget to pagetemplates
	 */
	public function step_23()
	{
		$widgetId = $this->db->query_first("
			SELECT widgetid
			FROM " . TABLE_PREFIX . "widget
			WHERE guid = 'vbulletin-widget_sgsidebar-4eb423cfd6dea7.34930861'"
		);

		if (!empty($widgetId['widgetid']))
		{
			$widgetId = $widgetId['widgetid'];
		}
		else
		{
			$this->skip_message();
			return;
		}

		$templateIds = $this->db->query_read("
			SELECT pagetemplateid, guid
			FROM " . TABLE_PREFIX . "pagetemplate
			WHERE guid IN ('vbulletin-4ecbdac93742a5.43676037', 'vbulletin-sgtopic93742a5.43676039', 'vbulletin-sgcatlist93742a5.43676040')
		");

		$records = array();
		$updates = array();
		$viewValues = array(
			'vbulletin-4ecbdac93742a5.43676037' => array('starter_only' => 1),
			'vbulletin-sgtopic93742a5.43676039' => array('view' => 'activity'),
			'vbulletin-sgcatlist93742a5.43676040' => array('starter_only' => 1)
		);
		$defaultVals = array(
			"searchTitle" => "Latest Group Topics", "resultsPerPage" => 60,
			"searchJSON" => array(
				"type" => array("vBForum_Text","vBForum_Poll","vBForum_Gallery","vBForum_Video","vBForum_Link"),
				"channel" => array("param" => "channelid"),
				"sort" => array("relevance" => "desc")
			)
		);

		while ($templateId = $this->db->fetch_array($templateIds))
		{
			$widgetinstanceIds = $this->db->query_read("
				SELECT widgetinstanceid, displaysection, displayorder, widgetid
				FROM " . TABLE_PREFIX . "widgetinstance
				WHERE pagetemplateid = " . $templateId['pagetemplateid'] . "
			");

			// check if we have a widgetinstance...
			$add = true;
			$displayOrder = 0;
			while ($instance = $this->db->fetch_array($widgetinstanceIds))
			{
				if ($instance['widgetid'] == $widgetId)
				{
					$add = false;
				}
				else if ($instance['displaysection'] == 1)
				{
					$displayOrder = $instance['displayorder'];
				}
			}

			if ($add)
			{
				$records[] = array('displayorder' => ($displayOrder + 1), 'widgetid' => $widgetId, 'pagetemplateid' => $templateId['pagetemplateid'], 'templateguid' => $templateId['guid']);
			}
			else
			{
				$updates[] = array('id' => $templateId['pagetemplateid'], 'templateguid' => $templateId['guid']);
			}
		}

		$inserts = array();
		foreach ($records AS $rec)
		{
			$adminConfig = (!empty($viewValues[$rec['templateguid']])) ? $viewValues[$rec['templateguid']] : array();
			$defaultVals['searchJSON'] = array_merge($adminConfig, $defaultVals['searchJSON']);
			$inserts[] = $rec['pagetemplateid'] . ", " . $rec['widgetid'] . ", 1, " . $rec['displayorder'] . ", '" . serialize($defaultVals) . "'";
		}

		// insert if needed
		if (!empty($inserts))
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['update_table'], 'widgetinstance'),
				"INSERT INTO " . TABLE_PREFIX . "widgetinstance
				(pagetemplateid, widgetid, displaysection, displayorder, adminconfig)
				VALUES
				(" . implode("), (", $inserts) . ")
			");
		}

		// update admin default config if needed
		foreach ($updates AS $value)
		{
			$tmp = $defaultVals;
			$adminConfig = (!empty($viewValues[$value['templateguid']])) ? $viewValues[$value['templateguid']] : array();
			$tmp['searchJSON'] = array_merge($adminConfig, $tmp['searchJSON']);
			$this->run_query(
				sprintf($this->phrase['vbphrase']['update_table'], 'widgetinstance'),
				"UPDATE " . TABLE_PREFIX . "widgetinstance
				SET adminconfig = '" . serialize($tmp) . "'
				WHERE widgetid = '" . $widgetId . "' AND pagetemplateid = " . $value['id'] . " AND adminconfig = ''
			");
		}
	}

	/**
	 * Change default Admin CP style 1
	 */
	public function step_24()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'setting', 1, 2),
			"
				UPDATE " . TABLE_PREFIX . "setting
				SET
					value = 'vBulletin_5_Default',
					defaultvalue = 'vBulletin_5_Default'
				WHERE varname = 'cpstylefolder'
			"
		);
	}

	/**
	 * Change default Admin CP style 2
	 */
	public function step_25()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'setting', 2, 2),
			"
				UPDATE " . TABLE_PREFIX . "setting
				SET
					value = 'png',
					defaultvalue = 'png'
				WHERE varname = 'cpstyleimageext'
			"
		);
	}

	/**
	 * Change default Admin CP style 3
	 */
	public function step_26()
	{
		// update all admins to use the new style
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'administrator', 1, 1),
			"
				UPDATE " . TABLE_PREFIX . "administrator
				SET cssprefs = 'vBulletin_5_Default'
				WHERE cssprefs <> ''
			"
		);
	}
}

class vB_Upgrade_500a29 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->skip_message();
	}

	// Import social group categories
	public function step_2()
	{
		if ($this->tableExists('socialgroup') AND $this->tableExists('discussion') AND $this->tableExists('groupmessage'))
		{
			vB_Upgrade::createAdminSession();

			$this->show_message($this->phrase['version']['500a28']['importing_socialgroup_categories']);
			$assertor = vB::getDbAssertor();

			$categories = $assertor->assertQuery('vBInstall:getMissingGroupCategories', []);
			$channelLib = vB_Library::instance('content_channel');
			$sgChannel = vB_Library::instance('node')->getSGChannel();
			foreach ($categories AS $category)
			{
				//a blank title shouldn't be possible but apparently it happens.  It's not clear
				//why it happens, but the observed cases all involve the default "Uncategorized"
				//category so we'll go ahead and roll with it.  This will at least result in a
				//successful upgrade where the user can change the category names if they don't like them.
				$defaultTitle = 'Uncategorized';

				//this step previously didn't update oldid correctly.  We may not be able to depend on all
				//sites having that set correctly in the future.
				$channel = [
					'parentid' => $sgChannel,
					'oldid' => $category['socialgroupcategoryid'],
					'title' => ($category['title'] ? $category['title'] : $defaultTitle),
					'description' =>  $category['description'],
					'urlident' => $channelLib->getUniqueUrlIdent($category['title']),
					'oldid' => $category['socialgroupcategoryid'],
					'oldcontenttypeid' => 9988
				];

				$channelLib->add($channel , ['skipNotifications' => true, 'skipFloodCheck' => true, 'skipDupCheck' => true]);
			}
		}
		else
		{
			$this->skip_message();
		}
	}

	// Import social groups
	public function step_3($data = null)
	{
		if ($this->tableExists('socialgroup') AND $this->tableExists('discussion') AND $this->tableExists('groupmessage'))
		{
			//we may not need this any longer since we're only using library classes but leaving it in out of caution.
			vB_Upgrade::createAdminSession();

			if (empty($data['startat']))
			{
				$this->show_message($this->phrase['version']['500a28']['importing_socialgroups']);
			}

			$callback = function($startat, $nextid)
			{
				$contentLib = vB_Library::instance('content_channel');
				$nodeLib = vB_Library::instance('node');

				$assertor = vB::getDbAssertor();
				$oldContentType = vB_Types::instance()->getContentTypeID('vBForum_SocialGroup');
				$uncategorized = $assertor->getRow('vBForum:channel', array('guid' => vB_Channel::DEFAULT_UNCATEGORIZEDGROUPS_PARENT));

				$groups = $assertor->assertQuery('vBInstall:getMissingSocialGroups', [
					'startat' => $startat,
					'nextid' => $nextid,
					'socialgroupType' => $oldContentType,
				]);

				$channelAddOptions = [
					'skipNotifications' => true,
					'skipFloodCheck' => true,
					'skipDupCheck' => true,

					//we don't need to this for each channel and we'll do it before it matters
					//later in the upgrade.
					'skipBuildLanguage' => true,

					//this is too expensive to do for every channel add and we don't really need to
					//it'll get taken care of before it matters.  Note that there is alreay magic
					//logic in then library to skip this for install/upgrade calls but this makes
					//it explicit.
					'skipBuildPermissions' => true,
				];
				foreach ($groups AS $group)
				{
					$data = [
						'parentid' => $group['categoryid'],
						'oldid' => $group['groupid'],
						'oldcontenttypeid' => $oldContentType,
						'title' => $group['name'],
						'description' => $group['description'],
					];

					if (intval($group['transferuserid']))
					{
						$data['userid'] = $group['transferuserid'];
						$data['authorname'] = $group['transferusername'];
					}
					else
					{
						$data['userid'] = $group['userid'];
						$data['authorname'] = $group['username'];
					}

					if (empty($group['routeid']))
					{
						$data['parentid'] = $uncategorized['nodeid'];
					}

					$data['urlident'] = $contentLib->getUniqueUrlIdent($data['title']);

					$response = $contentLib->add($data, $channelAddOptions);
					$nodeid = $response['nodeid'];

					// @TODO translate old group options to the new nodeoptions
					// import the group type properly

					$updates = [];
					switch ($group['type'])
					{
						case 'public':
							$updates = ['approve_membership' => 1, 'invite_only' => 0];
							break;
						case 'moderated':
							$updates = ['approve_membership' => 0, 'invite_only' => 0];
							break;
						case 'inviteonly':
							$updates = ['approve_membership' => 0, 'invite_only' => 1];
							break;
					}

					$nodeLib->setNodeOptions($nodeid, $updates);
				}
			};

			$batchsize = $this->getBatchSize('tiny', __FUNCTION__);
			return $this->updateByIdWalk($data,	$batchsize, 'vBInstall:getMaxSocialgroupid', 'vBInstall:socialgroup', 'groupid', $callback);
		}
		else
		{
			$this->skip_message();
		}
	}

	// assign group owners
	public function step_4()
	{
		if ($this->tableExists('socialgroup') AND $this->tableExists('discussion') AND $this->tableExists('groupmessage'))
		{
			vB_Upgrade::createAdminSession();

			$assertor = vB::getDbAssertor();
			$oldContentType = vB_Types::instance()->getContentTypeID('vBForum_SocialGroup');
			vB_Api::instanceInternal('usergroup')->fetchUsergroupList(true);

			//we just added the new usergroups and the usergroup cache hasn't been rebuilt. So we can't use it.
			$group = $assertor->getRow('usergroup', array('systemgroupid' => vB_Api_UserGroup::CHANNEL_OWNER_SYSGROUPID));
			if (empty($group['usergroupid']))
			{
				$message = sprintf($this->phrase['version']['500a29']['usergroup_x_not_found'], vB_Api_UserGroup::CHANNEL_OWNER_SYSGROUPID);
				$this->add_error($message, self::PHP_TRIGGER_ERROR, true);
				return;
			}

			$this->show_message($this->phrase['version']['500a28']['assigning_group_owners']);
			$assertor->assertQuery('vBInstall:addGroupOwners', array('groupid' => $group['usergroupid'], 'socialgroupType' => $oldContentType));
		}
		else
		{
			$this->skip_message();
		}
	}

	// assign group members
	public function step_5()
	{
		if ($this->tableExists('socialgroup') AND $this->tableExists('discussion') AND $this->tableExists('groupmessage'))
		{
			$assertor = vB::getDbAssertor();
			$oldContentType = vB_Types::instance()->getContentTypeID('vBForum_SocialGroup');
			$group = $assertor->getRow('usergroup', array('systemgroupid' => vB_Api_UserGroup::CHANNEL_MEMBER_SYSGROUPID));
			if (empty($group['usergroupid']))
			{
				$message = sprintf($this->phrase['version']['500a29']['usergroup_x_not_found'], vB_Api_UserGroup::CHANNEL_MEMBER_SYSGROUPID);
				$this->add_error($message, self::PHP_TRIGGER_ERROR, true);
				return;
			}

			$this->show_message($this->phrase['version']['500a28']['assigning_group_members']);
			$assertor->assertQuery('vBInstall:addGroupMembers', array('groupid' => $group['usergroupid'], 'socialgroupType' => $oldContentType));
		}
		else
		{
			$this->skip_message();
		}
	}

	/** Import discussions **/
	public function step_6($data = [])
	{
		if ($this->tableExists('socialgroup') AND $this->tableExists('discussion') AND $this->tableExists('groupmessage'))
		{
			$startat = intval($data['startat'] ?? 0);
			$assertor = vB::getDbAssertor();
			$groupTypeid = vB_Types::instance()->getContentTypeID('vBForum_SocialGroup');
			$discussionTypeid = vB_Types::instance()->getContentTypeID('vBForum_SocialGroupDiscussion');
			$batchsize = 2000;

			if ($startat == 0)
			{
				$maxvB5 = $assertor->getRow('vBInstall:getMaxImportedPost', array('contenttypeid' => $discussionTypeid));
				$startat = intval($maxvB5['maxid']);
			}

			if (!empty($data['maxvB4']))
			{
				$maxvB4 = intval($data['maxvB4']);
			}
			else
			{
				$maxvB4 = $assertor->getRow('vBInstall:getMaxSGDiscussionID', array());
				$maxvB4 = intval($maxvB4['maxid']);

				//If we don't have any posts, we're done.
				if ($maxvB4 < 1)
				{
					$this->skip_message();
					return;
				}
			}

			if ($maxvB4 <= $startat)
			{
				$this->show_message($this->phrase['core']['process_done']);
				return;
			}

			$this->show_message($this->phrase['version']['500a28']['importing_discussions']);
			$assertor->assertQuery('vBInstall:importSGDiscussions',
				array('textTypeid' =>  vB_Types::instance()->getContentTypeID('vBForum_Text'),
				'startat' => $startat, 'batchsize' => $batchsize,'discussionTypeid' => $discussionTypeid,
				'grouptypeid' => $groupTypeid));

			$assertor->assertQuery('vBInstall:importSGDiscussionText',
				array('textTypeid' =>  vB_Types::instance()->getContentTypeID('vBForum_SocialGroupDiscussion'),
					'startat' => $startat, 'batchsize' => $batchsize,'discussionTypeid' => $discussionTypeid,
					'grouptypeid' => $groupTypeid));

			$assertor->assertQuery('vBInstall:addClosureSelf',
				array('startat' => $startat, 'batchsize' => $batchsize, 'contenttypeid' => $discussionTypeid));

			$assertor->assertQuery('vBInstall:addClosureParents',
				array('startat' => $startat, 'batchsize' => $batchsize, 'contenttypeid' => $discussionTypeid));
			$assertor->assertQuery('vBInstall:setPMStarter',
				array('startat' => $startat, 'batchsize' => $batchsize, 'contenttypeid' => $discussionTypeid));
			$assertor->assertQuery('vBInstall:updateChannelRoutes',
				array('startat' => $startat, 'batchsize' => $batchsize, 'contenttypeid' => $discussionTypeid));

			$this->show_message(sprintf($this->phrase['core']['processed_records_x'], $batchsize));
			return array('startat' => ($startat + $batchsize));

		}
		else
		{
			$this->skip_message();
		}
	}

	/** Import Group Messages **/
	public function step_7($data = [])
	{
		if ($this->tableExists('socialgroup') AND $this->tableExists('discussion') AND $this->tableExists('groupmessage'))
		{
			$messageTypeid = vB_Types::instance()->getContentTypeID('vBForum_SocialGroupMessage');
			$discussionTypeid = vB_Types::instance()->getContentTypeID('vBForum_SocialGroupDiscussion');
			$assertor = vB::getDbAssertor();
			$batchsize = 2000;
			$startat = intval($data['startat'] ?? 0);

			if ($startat == 0)
			{
				$maxvB5 = $assertor->getRow('vBInstall:getMaxImportedPost', array('contenttypeid' => $messageTypeid));
				$startat = intval($maxvB5['maxid']);
			}

			if (!empty($data['maxvB4']))
			{
				$maxvB4 = intval($data['maxvB4']);
			}
			else
			{
				$maxvB4 = $assertor->getRow('vBInstall:getMaxSGPost', array());
				$maxvB4 = intval($maxvB4['maxid']);

				//If we don't have any posts, we're done.
				if ($maxvB4 < 1)
				{
					$this->skip_message();
					return;
				}
			}

			if ($maxvB4 <= $startat)
			{
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}

			$this->show_message($this->phrase['version']['500a28']['importing_discussions']);
			$assertor->assertQuery('vBInstall:importSGPosts',
				array('textTypeid' =>  vB_Types::instance()->getContentTypeID('vBForum_Text'),
					'startat' => $startat, 'batchsize' => $batchsize,'discussionTypeid' => $discussionTypeid,
					'messageTypeid' => $messageTypeid));

			$assertor->assertQuery('vBInstall:importSGPostText',
				array('textTypeid' =>  vB_Types::instance()->getContentTypeID('vBForum_SocialGroupDiscussion'),
					'startat' => $startat, 'batchsize' => $batchsize,'discussionTypeid' => $messageTypeid,
					'messageTypeid' => $messageTypeid));

			$assertor->assertQuery('vBInstall:addClosureSelf',
				array('startat' => $startat, 'batchsize' => $batchsize, 'contenttypeid' => $messageTypeid));

			$assertor->assertQuery('vBInstall:addClosureParents',
				array('startat' => $startat, 'batchsize' => $batchsize, 'contenttypeid' => $messageTypeid));

			$this->show_message(sprintf($this->phrase['core']['processed_records_x'], $batchsize));
			return array('startat' => ($startat + $batchsize));
		}
		else
		{
			$this->skip_message();
		}
	}

	/** Create Gallery from SG **/
	public function step_8($data = [])
	{
		if ($this->tableExists('socialgroup') AND $this->tableExists('node') AND $this->tableExists('attachment') AND $this->tableExists('gallery'))
		{
			$startat = intval($data['startat'] ?? 0);
			$assertor = vB::getDbAssertor();
			$groupTypeid = vB_Types::instance()->getContentTypeID('vBForum_SocialGroup');
			$galleryTypeid = vB_Types::instance()->getContentTypeID('vBForum_Gallery');
			$batchsize = 2000;
			if ($startat == 0)
			{
				$maxvB5 = $assertor->getRow('vBInstall:getMaxImportedPost', array(
					'contenttypeid' => 9983
				));
				$startat = intval($maxvB5['maxid']);
			}

			if (!empty($data['maxvB4']))
			{
				$maxvB4 = intval($data['maxvB4']);
			}
			else
			{
				$maxvB4 = $assertor->getRow('vBInstall:getMaxSGGallery', array('grouptypeid' => $groupTypeid));
				$maxvB4 = intval($maxvB4['maxid']);

				//If we don't have any posts, we're done.
				if ($maxvB4 < 1)
				{
					$this->skip_message();
					return;
				}
			}

			if ($maxvB4 <= $startat)
			{
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}

			$this->show_message($this->phrase['version']['500a29']['importing_socialgroup_galleries']);
			$assertor->assertQuery('vBInstall:importSGGalleryNode',
				array('gallerytypeid' =>  $galleryTypeid,
				'startat' => $startat, 'batchsize' => $batchsize, 'grouptypeid' => $groupTypeid
			));

			$assertor->assertQuery('vBInstall:importSGGallery',
				array('startat' => $startat, 'batchsize' => $batchsize, 'grouptypeid' => $groupTypeid,
					'caption' => $this->phrase['version']['500a29']['imported_socialgroup_galleries']
			));
			$assertor->assertQuery('vBInstall:importSGText',
				array('startat' => $startat, 'batchsize' => $batchsize, 'grouptypeid' => $groupTypeid,
					'caption' => $this->phrase['version']['500a29']['imported_socialgroup_galleries']
				));

			$assertor->assertQuery('vBInstall:addClosureSelf',
				array('startat' => $startat, 'batchsize' => $batchsize, 'contenttypeid' => 9983));

			$assertor->assertQuery('vBInstall:addClosureParents',
				array('startat' => $startat, 'batchsize' => $batchsize, 'contenttypeid' => 9983));
			$assertor->assertQuery('vBInstall:setPMStarter',
				array('startat' => $startat, 'batchsize' => $batchsize, 'contenttypeid' => 9983));
			$assertor->assertQuery('vBInstall:updateChannelRoutes',
				array('startat' => $startat, 'batchsize' => $batchsize, 'contenttypeid' => 9983));

			$this->show_message(sprintf($this->phrase['core']['processed_records_x'], $batchsize));
			return array('startat' => ($startat + $batchsize));
		}
		else
		{
			$this->skip_message();
		}
	}

	/** Create Gallery Post from SG Photos **/
	public function step_9($data = [])
	{
		if ($this->tableExists('socialgroup') AND $this->tableExists('node') AND $this->tableExists('attachment') AND $this->tableExists('photo'))
		{
			$startat = intval($data['startat'] ?? 0);
			$assertor = vB::getDbAssertor();
			$groupTypeid = vB_Types::instance()->getContentTypeID('vBForum_SocialGroup');
			$photoTypeid = vB_Types::instance()->getContentTypeID('vBForum_Photo');
			$batchsize = 2000;

			if ($startat == 0)
			{
				$maxvB5 = $assertor->getRow('vBInstall:getMaxImportedPost', array(
					'contenttypeid' => 9987
				));
				$startat = intval($maxvB5['maxid']);
			}

			if (!empty($data['maxvB4']))
			{
				$maxvB4 = intval($data['maxvB4']);
			}
			else
			{
				$maxvB4 = $assertor->getRow('vBInstall:getMaxSGPhotoID', array('grouptypeid' => $groupTypeid));
				$maxvB4 = intval($maxvB4['maxid']);

				//If we don't have any posts, we're done.
				if ($maxvB4 < 1)
				{
					$this->skip_message();
					return;
				}
			}

			if ($maxvB4 <= $startat)
			{
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}

			$this->show_message($this->phrase['version']['500a28']['importing_photos']);
			$assertor->assertQuery('vBInstall:importSGPhotoNodes',
				array('startat' => $startat, 'batchsize' => $batchsize,'phototypeid' => $photoTypeid,
				'grouptypeid' => $groupTypeid));

			$assertor->assertQuery('vBInstall:importSGPhotos',
				array('textTypeid' =>  vB_Types::instance()->getContentTypeID('vBForum_SocialGroupDiscussion'),
					'startat' => $startat, 'batchsize' => $batchsize, 'grouptypeid' => $groupTypeid));

			$assertor->assertQuery('vBInstall:addClosureSelf',
				array('startat' => $startat, 'batchsize' => $batchsize, 'contenttypeid' => 9987));

			$assertor->assertQuery('vBInstall:addClosureParents',
				array('startat' => $startat, 'batchsize' => $batchsize, 'contenttypeid' => 9987));

			$assertor->assertQuery('vBInstall:fixLastGalleryData',
				array('startat' => $startat, 'batchsize' => $batchsize
			));

			$this->show_message(sprintf($this->phrase['core']['processed_records_x'], $batchsize));
			return array('startat' => ($startat + $batchsize));
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_10()
	{
		$this->skip_message();
	}

	/* Add widgetid index */
	public function step_11()
	{
		$this->add_index(
			sprintf($this->phrase['core']['create_index_x_on_y'], 'widgetid', TABLE_PREFIX . 'widgetdefinition'),
			'widgetdefinition',
			'widgetid',
			array('widgetid')
		);
	}

	/**Add an enumerated value to private message types **/
	public function step_12()
	{
		vB_Upgrade::createAdminSession();
		$types = "'" . implode("','", array_merge(vB_Library::instance('content_privatemessage')->fetchNotificationTypes(),
		vB_Library::instance('content_privatemessage')->getChannelRequestTypes())) .  "'";
		$this->run_query(

			sprintf($this->phrase['core']['altering_x_table'], 'privatemessage', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "privatemessage CHANGE about about ENUM($types); "
		);
	}


	public function step_13()
	{
		$this->skip_message();
	}

	public function step_14()
	{
			$bf_ugp = vB::getDatastore()->getValue('bf_ugp_adminpermissions');
			$this->run_query(
				sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'usergroup'),
				"UPDATE " . TABLE_PREFIX . "usergroup AS usergroup SET adminpermissions = adminpermissions | " . $bf_ugp['canadminstyles'] . " WHERE usergroupid = 6;"
			);
	}

	//We can hit the myisam index limit below if the table is already on utf8mb4 before we upgrade.
	//(We'll also hit a limit in INNODB if the table is in COMPACT mode but we can have a longer fieldlength
	//if the table is in DYNAMIC mode)
	public function step_15()
	{
		$db = vB::getDbAssertor();
		$row = $db->getRow('vBInstall:checkIndexLimitPhrase');
		if (!$row)
		{
			$this->show_message(sprintf($this->phrase['core']['altering_x_table'], 'phrase', 1, 1));
			$db->assertQuery('vBInstall:alterIndexLimitPhrase');
		}
		else
		{
			$this->show_message(sprintf($this->phrase['version']['523a3']['data_too_long'], 'phrase', 191));
		}
	}

	/** Set category field based on cancontainthreads forum options only for imported channels **/
	public function step_16()
	{
		if ($this->tableExists('forum'))
		{
			// Forum options were imported in 500a23 step 5
			$options = vB::getDatastore()->getValue('bf_misc_forumoptions');
			$forumType = vB_Types::instance()->getContentTypeID('vBForum_Forum');
			$this->run_query(
				sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'channel'),
				"UPDATE " . TABLE_PREFIX . "channel c
				INNER JOIN " . TABLE_PREFIX . "node n ON n.nodeid = c.nodeid
				INNER JOIN " . TABLE_PREFIX . "forum f ON f.forumid = n.oldid AND oldcontenttypeid = $forumType
				SET c.category = if (f.options & {$options['cancontainthreads']}, 0, 1)
				WHERE c.category = 0;"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/** Update the modcp link in the footer **/
	public function step_17()
	{
		$assertor = vB::getDbAssertor();
		//This might need to change (siteid) if multiple site will be supported
		//(unlikely at this point)
		$footer = $assertor->getRow('vBForum:site', ['siteid' => 1]);

		$footernavbar = unserialize($footer['footernavbar']);

		foreach ($footernavbar as $key => $item)
		{
			if ($item['url'] == 'modcp')
			{
				$item['url'] = 'modcp/';
				$footernavbar[$key] = $item;
			}
		}

		$footernavbar = serialize($footernavbar);

		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'site'));
		$queryParams = [
			vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
			vB_dB_Query::CONDITIONS_KEY => ['siteid' => 1],
			'footernavbar' => $footernavbar
		];
		$assertor->assertQuery('vBForum:site', $queryParams);
	}

	/* Add fulltext index on phrase table */
	public function step_18()
	{
		if ($this->tableExists('phrase'))
		{
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'phrase', 1, 1),
				"ALTER TABLE " . TABLE_PREFIX . "phrase ENGINE=MYISAM"
			);

			if ($this->field_exists('phrase', 'text'))
			{
				$this->add_index(
					sprintf($this->phrase['version']['380a2']['fulltext_index_on_x'], TABLE_PREFIX . 'phrase'),
					'phrase',
					'pt_ft',
					['text'],
					'fulltext'
				);
			}
			else
			{
				$this->skip_message();
			}
		}
	}

	/**
	 * Update lastcontentid data for socialgroups
	 *
	 */
	public function step_19($data = [])
	{
		$messageTypeid = vB_Types::instance()->getContentTypeID('vBForum_SocialGroupMessage');
		$discussionTypeid = vB_Types::instance()->getContentTypeID('vBForum_SocialGroupDiscussion');
		$batchsize = 10000;
		$startat = intval($data['startat'] ?? 0);
		$assertor = vB::getDbAssertor();

		$maxvB5 = $assertor->getRow('vBInstall:getMaxImportedPost', array('contenttypeid' => $messageTypeid));
		$maxvB5 = intval($maxvB5['maxid']);

		if ($startat == 0)
		{
			$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'node'));
		}
		else if ($startat >= $maxvB5)
		{
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			return;
		}
		$assertor->assertQuery('vBInstall:updateDiscussionLastContentId', array('messageTypeid' => $messageTypeid,
			'discussionTypeid' => $discussionTypeid,'startat' => $startat, 'batchsize' => $batchsize));
		$this->show_message(sprintf($this->phrase['core']['processed_records_x'], $batchsize));
		return array('startat' => $startat + $batchsize);
	}

	/** Update Last data for non-category channels **/
	public function step_20($data = [])
	{
		$channelTypeid = vB_Types::instance()->getContentTypeID('vBForum_Channel');
		$assertor = vB::getDbAssertor();
		$batchsize = 40000;
		$startat = intval($data['startat'] ?? 0);

		$maxNodeid = $assertor->getRow('vBInstall:getMaxNodeid', array());
		$maxNodeid = intval($maxNodeid['maxid']);

		if ($maxNodeid < $startat)
		{
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			return;
		}

		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'node'));
		$assertor->assertQuery('vBInstall:updateChannelLast',
			array('channeltypeid' =>  $channelTypeid,
				'startat' => $startat, 'batchsize' => $batchsize));

		$this->show_message(sprintf($this->phrase['core']['processed_records_x'], $batchsize));
		return array('startat' => ($startat + $batchsize));
	}


	/** Update Last data for category channels **/
	public function step_21($data = [])
	{
		$channelTypeid = vB_Types::instance()->getContentTypeID('vBForum_Channel');
		$assertor = vB::getDbAssertor();
		$batchsize = 40000;
		$startat = intval($data['startat'] ?? 0);

		if (!empty($data['maxvB4']))
		{
			$maxvB4 = intval($data['maxvB4']);
		}
		else
		{
			$maxvB4 = $assertor->getRow('vBInstall:getMaxNodeid', array());
		}

		if ($maxvB4 <= $startat)
		{
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			return;
		}

		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'node'));
		$assertor->assertQuery('vBInstall:updateCategoryLast',
			array('channeltypeid' =>  $channelTypeid));
	}

	// There was a step 22- Update Channel counts. But this was not quite right so we corrected it and moved to beta 11.
}

class vB_Upgrade_500a3 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->skip_message();
	}

	public function step_2()
	{
		$this->skip_message();
	}

	public function step_3()
	{
		$this->skip_message();
	}

	/***	adding relationship 'follow' to userlist table*/
	public function step_4()
	{
		$this->run_query(sprintf($this->phrase['core']['altering_x_table'], 'userlist', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "userlist CHANGE type type ENUM('buddy', 'ignore', 'follow') NOT NULL DEFAULT 'buddy';");
	}

	/** Add the route for the profile pages**/
	public function step_5()
	{
		$this->skip_message();
	}

	/***	Setting default adminConfig for activity stream widget */
	public function step_6()
	{
		$this->run_query(
		sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'widgetinstance'),
		"
		UPDATE " . TABLE_PREFIX . "widgetinstance
			SET adminconfig = 'a:4:{s:11:\"filter_sort\";s:11:\"sort_recent\";s:11:\"filter_time\";s:8:\"time_all\";s:11:\"filter_show\";s:8:\"show_all\";s:20:\"filter_conversations\";s:1:\"1\";}'
			WHERE widgetid = (SELECT widgetid FROM " . TABLE_PREFIX . "widget WHERE title = 'Activity Stream') AND adminconfig = ''
			"
		);
	}

	/**
	 * Add default header navbar items for Blogs
	 */
	public function step_7()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'site'),
			"
				UPDATE " . TABLE_PREFIX . "site
				SET headernavbar = 'a:1:{i:0;a:2:{s:5:\"title\";s:5:\"Blogs\";s:3:\"url\";s:1:\"#\";}}'
				WHERE
					siteid = 1
						AND
					headernavbar = ''
			"
		);
	}

	public function step_8()
	{
		$this->skip_message();
	}
}

class vB_Upgrade_500a30 extends vB_Upgrade_Version
{
	/** reseting blog pagetemplates to update blog sidebar **/
	public function step_1()
	{
		vB_Upgrade::createAdminSession();
		// we need to force the page template to be updated
		$blogPageTemplate = vB_Page::getBlogChannelPageTemplate();

		$db = vB::getDbAssertor();

		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'widgetinstance'));
		$db->delete('widgetinstance', array('pagetemplateid' => $blogPageTemplate));
		$db->delete('pagetemplate', array('pagetemplateid' => $blogPageTemplate));

		// import widgets and pagetemplates
		$this->show_message($this->phrase['final']['import_latest_widgets']);
		$widgetFile = DIR . '/install/vbulletin-widgets.xml';

		if (!($xml = file_read($widgetFile)))
		{
			$this->add_error(sprintf($this->phrase['vbphrase']['file_not_found'], 'vbulletin-widgets.xml'), self::PHP_TRIGGER_ERROR, true);
			return;
		}

		$this->show_message(sprintf($this->phrase['vbphrase']['importing_file'], 'vbulletin-widgets.xml'));
		$xml_importer = new vB_Xml_Import_Widget();
		$xml_importer->importFromFile($widgetFile);
		$this->show_message($this->phrase['core']['import_done']);

		$pageTemplateFile = DIR . '/install/vbulletin-pagetemplates.xml';

		if (!($xml = file_read($pageTemplateFile)))
		{
			$this->add_error(sprintf($this->phrase['vbphrase']['file_not_found'], 'vbulletin-pagetemplates.xml'), self::PHP_TRIGGER_ERROR, true);
			return;
		}

		$this->show_message(sprintf($this->phrase['vbphrase']['importing_file'], 'vbulletin-pagetemplates.xml'));
		$xml_importer = new vB_Xml_Import_PageTemplate('vbulletin', 0);
		$xml_importer->importFromFile($pageTemplateFile);

		// now update pages with new pagetemplate
		$newBlogPageTemplate = vB_Page::getBlogChannelPageTemplate();
		$db->update('page', array('pagetemplateid' => $newBlogPageTemplate), array('pagetemplateid' => $blogPageTemplate));
	}
}

class vB_Upgrade_500a32 extends vB_Upgrade_Version
{
	// need to fix perm_groupid index in the permission table
	// need to make perm_group_node index unique
	public function step_1()
	{
		// drop them first
		$this->drop_index(
			sprintf($this->phrase['core']['altering_x_table'], 'permission', 1, 4),
			'permission',
			'perm_groupid'
		);
		$this->drop_index(
			sprintf($this->phrase['core']['altering_x_table'], 'permission', 2, 4),
			'permission',
			'perm_group_node'
		);
	}

	public function step_2()
	{
		// Add new indexes
		$this->add_index(
				sprintf($this->phrase['core']['altering_x_table'], 'permission', 3, 4),
				'permission',
				'perm_groupid',
				array('groupid')
		);
		$this->add_index(
				sprintf($this->phrase['core']['altering_x_table'], 'permission', 4, 4),
				'permission',
				'perm_group_node',
				array('nodeid', 'groupid')
		);
	}
}

class vB_Upgrade_500a33 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->skip_message();
	}

	/**
	* Step #2 - Retire existing vB4 styles
	*
	*/
	public function step_2()
	{
		$this->run_query(
			$this->phrase['version']['500a33']['updating_styles'],
			"UPDATE " . TABLE_PREFIX . "style
			SET userselect = 0,	displayorder = 65432,
			title = IF(title LIKE '%Incompatible%', title, CONCAT(title, ' " . $this->db->escape_string($this->phrase['version']['500a33']['incompatible']) . "'))
			WHERE NOT (dateline = 99999999 OR title LIKE '%" . $this->db->escape_string($this->phrase['version']['500a33']['default_style']) . "%')
		");
	}

	/**
	* Step #3 - Create new vB5 style
	*
	*/
	public function step_3()
	{
		$check = $this->db->query_first("
			SELECT styleid FROM " . TABLE_PREFIX . "style WHERE dateline = 99999999
			OR title LIKE '%" . $this->db->escape_string($this->phrase['version']['500a33']['default_style']) . "%'
		");

		if (!empty($check['styleid']))
		{
			$this->skip_message();
		}
		else
		{
			$this->db->query("
				INSERT INTO " . TABLE_PREFIX . "style
					(title,	parentid, userselect, displayorder, dateline)
					VALUES
					('" . $this->db->escape_string($this->phrase['version']['500a33']['default_style']) . "', -1, 1, 1, 99999999)
			");

			$styleid = $this->db->insert_id();

			$this->run_query(
				$this->phrase['version']['500a33']['creating_default_style'],
				"UPDATE " . TABLE_PREFIX . "style
				SET parentlist = '" . intval($styleid) . ",-1'
				WHERE styleid = " . intval($styleid)
			);

			$this->run_query(
				$this->phrase['version']['500a33']['updating_style'],
				"UPDATE " . TABLE_PREFIX . "setting
				SET value = '" . intval($styleid) . "'
				WHERE varname = 'styleid'
			");
		}
	}

	/**
	* Step #4 - Update some settings
	*
	*/
	public function step_4()
	{
		/* Update the bburl path, this is still used
		by the backend atm and needs to point to the core */
		if ($this->caller == 'cli')
		{
			/* CLI, so just append /core to what exists */
			$this->run_query(
				$this->phrase['version']['500a33']['updating_options'],
				"UPDATE " . TABLE_PREFIX . "setting
				SET value = IF(value LIKE '%/core',	value, CONCAT(value, '/core'))
				WHERE varname = 'bburl'
			");
		}
		else // ajax //
		{
			/* WEB, so try and rebuild it from scratch */
			$port = intval($_SERVER['SERVER_PORT']);
			$port = in_array($port, array(80, 443)) ? '' : ':' . $port;
			$scheme = (($port == ':443') OR (isset($_SERVER['HTTPS']) AND $_SERVER['HTTPS'] AND ($_SERVER['HTTPS'] != 'off'))) ? 'https://' : 'http://';
			$path = $scheme . $_SERVER['SERVER_NAME'] . $port . substr(SCRIPTPATH, 0, strpos(SCRIPTPATH, '/install/'));

			$this->run_query(
				$this->phrase['version']['500a33']['updating_options'],
				"UPDATE " . TABLE_PREFIX . "setting
				SET value = '$path'
				WHERE varname = 'bburl'
			");
		}
	}

	/**
	* Step #5 - Update modcp route
	*
	*/
	public function step_5()
	{

		$this->show_message($this->phrase['version']['500a33']['fix_modcp_route']);
		vB::getDbAssertor()->update('routenew', array('prefix' => 'modcp'), array('regex' => 'modcp/(?P<file>[a-zA-Z0-9_.-]*)'));
	}
}

class vB_Upgrade_500a36 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], 'site'));

		$assertor = vB::getDbAssertor();
		$phraseApi = vB_Api::instanceInternal('phrase');

		$sites = $assertor->assertQuery('vBForum:site', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT));

		$phrases = array();

		foreach ($sites AS $site)
		{
			$header = unserialize($site['headernavbar']);
			if (!empty($header))
			{
				foreach ($header as &$h)
				{
					$this->getNavbarPhrase($h, $phrases);
				}
			}

			$footer = unserialize($site['footernavbar']);
			if (!empty($footer))
			{
				foreach ($footer as &$f)
				{
					$this->getNavbarPhrase($f, $phrases);
				}
			}

			// remove phrases that were already created
			$existingPhrases = $phraseApi->fetch(array_keys($phrases));
			if (!empty($existingPhrases))
			{
				foreach($existingPhrases as $name => $phrase)
				{
					if ($name != $phrases[$name]['title'])
					{
						// replace title with phrase name
						$phrases[$name]['title'] = $name;
					}

					unset($phrases[$name]);
				}
			}

			if (!empty($phrases))
			{
				vB_Upgrade::createAdminSession();

				// create missing phrases
				foreach($phrases as $name => $data)
				{
					$phraseApi->save('navbarlinks', $name, array(
							'text' => array(0 => $data['title']),
							'oldvarname' => $name,
							'oldfieldname' => 'navbarlinks',
							't' => 0,
							'ismaster' => 0,
							'product' => 'vbulletin'
					));
					$phrases[$name]['title'] = $name;
				}

				// now update footer and header
				$assertor->update('vBForum:site', array(
					'headernavbar' => serialize($header),
					'footernavbar' => serialize($footer)
				), array('siteid' => $site['siteid']));
			}
		}
	}

	protected function getNavbarPhrase(&$navbar, &$phrases)
	{
		// Already processed ....
		if (substr($navbar['title'],0,7) == 'navbar_')
		{
			return;
		}

		$words = explode(' ', $navbar['title']);
		$words = array_map('trim', $words);
		$phraseName = 'navbar_' . strtolower(implode('_', $words));

		// avoid duplicates
		$i = 1;
		$temp = $phraseName;
		while (isset($phrases[$temp]))
		{
			$temp = $phraseName . "_$i";
			$i++;
		}
		$phraseName = $temp;

		$phrases[$phraseName] =& $navbar;

		if (isset($navbar['subnav']) AND !empty($navbar['subnav']))
		{
			foreach ($navbar['subnav'] AS &$s)
			{
				$this->getNavbarPhrase($s, $phrases);
			}
		}
	}


	/**
	 * Forum prefix set update
	 */
	public function step_2()
	{
		if (!$this->tableExists('channelprefixset'))
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'channelprefixset'),
				"
				CREATE TABLE " . TABLE_PREFIX . "channelprefixset (
					nodeid INT UNSIGNED NOT NULL DEFAULT '0',
					prefixsetid VARCHAR(25) NOT NULL DEFAULT '',
					PRIMARY KEY (nodeid, prefixsetid)
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

	public function step_3()
	{
		// convert old forumprefixset table data and insert into the new channelprefixset table
		if ($this->tableExists('channelprefixset') AND $this->tableExists('forumprefixset'))
		{
			$prefixsets = $this->db->query_read("
				SELECT forumprefixset.forumid, forumprefixset.prefixsetid, node.nodeid
				FROM " . TABLE_PREFIX . "forumprefixset AS forumprefixset
				JOIN " . TABLE_PREFIX . "node AS node ON (forumprefixset.forumid = node.oldid AND node.oldcontenttypeid = 3)
			");

			$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'channelprefixset'));

			while ($prefixset = $this->db->fetch_array($prefixsets))
			{
				$this->db->query_write("
					INSERT INTO " . TABLE_PREFIX . "channelprefixset
					(nodeid, prefixsetid)
					VALUES
					($prefixset[nodeid], '$prefixset[prefixsetid]')
				");
			}

			// Drop old forumprefixset table
			$this->run_query(
				sprintf($this->phrase['core']['dropping_old_table_x'], "forumprefixset"),
				"DROP TABLE IF EXISTS " . TABLE_PREFIX . "forumprefixset"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_4()
	{
		/* Adding of prefixid to node table removed.
		Its part of the table creation in Alpha 1 Step 9, and also in the installs schema file
		No legitimate upgrades should ever need it adding here, as this version was never public */

		$this->skip_message();
	}
}

class vB_Upgrade_500a37 extends vB_Upgrade_Version
{
	/** fix screen layouts
	 *
	 */
	public function step_1()
	{
		$screenLayOutRecords = $this->db->query_first("
			SELECT template
			FROM " . TABLE_PREFIX . "screenlayout
			WHERE screenlayoutid = 1
		");

		if ($screenLayOutRecords['template'] == 'sb_screenlayout_1')
		{
			$this->db->query_write("
				TRUNCATE " . TABLE_PREFIX . "screenlayout
			");

			$this->db->query_write("
				INSERT INTO " . TABLE_PREFIX . "screenlayout
				(screenlayoutid, varname, title, displayorder, columncount, template, admintemplate)
				VALUES
				(1, '100', '100', 4, 1, 'screenlayout_1', 'admin_screenlayout_1'),
				(2, '70-30', '70/30', 1, 2, 'screenlayout_2', 'admin_screenlayout_2'),
				(4, '30-70', '30/70', 3, 2, 'screenlayout_4', 'admin_screenlayout_4')
			");

			$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], 'screenlayout'));

			$this->db->query_write("
				UPDATE " . TABLE_PREFIX . "widget
				SET template = SUBSTR(template,4)
				WHERE template LIKE 'sb_%'
			");

			$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], 'widget'));
		}
		else
		{
			$this->skip_message();
		}
	}
}

class vB_Upgrade_500a39 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'permission'));
		$assertor = vB::getDbAssertor();
		$assertor->assertQuery('vBInstall:updateModeratorPermission', array('modPermissions' => 59902175, 'systemgroups' => array(9,12)));
		$assertor->assertQuery('vBInstall:updateModeratorPermission', array('modPermissions' => 26347743, 'systemgroups' => array(10,13)));
	}
}

class vB_Upgrade_500a4 extends vB_Upgrade_Version
{
	/***	Updating initial widget definition records ***/
	public function step_1()
	{
		$skip_message = false;
		$search_results_widget = $this->db->query_first("
			SELECT widgetid FROM " . TABLE_PREFIX . "widget
			WHERE template = 'widget_search_results'");

		if (!empty($search_results_widget['widgetid']))
		{
			$widgetDefRecords = $this->db->query_first("
				SELECT widgetid FROM " . TABLE_PREFIX . "widgetdefinition WHERE name = 'searchResultTitle' AND widgetid = '".$search_results_widget['widgetid']."'
			");

			if (empty($widgetDefRecords) OR empty($widgetDefRecords['widgetid']))
			{
				$this->run_query(
					sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'widgetdefinition'),
					"
					INSERT INTO `" . TABLE_PREFIX . "widgetdefinition`
					(`widgetid`, `field`, `name`, `label`, `defaultvalue`, `isusereditable`, `isrequired`, `displayorder`, `validationtype`, `validationmethod`)
					VALUES
					('".$search_results_widget['widgetid']."', 'Text', 'searchResultTitle', 'WidgetTitle', '', 1, 0, 1, '', '')
					"
				);
			}
			else
			{
				$skip_message = true;
			}

		}
		else
		{
			$skip_message = true;
		}

		$search_criteria_widget = $this->db->query_first("
			SELECT widgetid FROM " . TABLE_PREFIX . "widget
			WHERE template = 'widget_search_criteria'");

		if (!empty($search_criteria_widget['widgetid']))
		{
			$widgetDefRecords = $this->db->query_first("
				SELECT widgetid FROM " . TABLE_PREFIX . "widgetdefinition WHERE name = 'searchCriteriaTitle' AND widgetid = '".$search_criteria_widget['widgetid']."'
			");

			if (empty($widgetDefRecords) OR empty($widgetDefRecords['widgetid']))
			{
				$this->run_query(
					sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'widgetdefinition'),
					"
					INSERT INTO `" . TABLE_PREFIX . "widgetdefinition`
					(`widgetid`, `field`, `name`, `label`, `defaultvalue`, `isusereditable`, `isrequired`, `displayorder`, `validationtype`, `validationmethod`)
					VALUES
					('".$search_criteria_widget['widgetid']."', 'Text', 'searchCriteriaTitle', 'WidgetTitle', '', 1, 0, 1, '', '')
					"
				);
			}
			else
			{
				$skip_message = true;
			}

		}
		else
		{
			$skip_message = true;
		}
		if ($skip_message)
		{
			$this->skip_message();
		}
	}
	/**
	 * adding search results template
	 */
	public function step_2()
	{
		$this->db->query_write("
			UPDATE " . TABLE_PREFIX . "pagetemplate SET title = 'Advanced Search Template' WHERE title = 'Default Search Template'
		");
		$pageTemplateRecords = $this->db->query_first("
			SELECT pagetemplateid FROM " . TABLE_PREFIX . "pagetemplate WHERE title = 'Search Result Template'
		");

		if (empty($pageTemplateRecords) OR empty($pageTemplateRecords['pagetemplateid']))
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'pagetemplate'),
				"
				INSERT INTO `" . TABLE_PREFIX . "pagetemplate`
				(`title`, `screenlayoutid`)
				VALUES
				('Search Result Template', '1')
				"
			);
		}
		else
		{
			$this->skip_message();
		}
	}
	/**
	 * moving the search criteria widget to a separate template
	 */
	public function step_3()
	{
		$pageTemplateRecords = $this->db->query_first("
			SELECT pagetemplateid FROM " . TABLE_PREFIX . "pagetemplate WHERE title = 'Advanced Search Template'
		");
		$search_results_widget = $this->db->query_first("
			SELECT widgetid FROM " . TABLE_PREFIX . "widget
			WHERE template = 'widget_search_results'");

		$this->run_query(
				sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'pagetemplate'),
				"
				UPDATE " . TABLE_PREFIX . "widgetinstance SET pagetemplateid = '".$pageTemplateRecords['pagetemplateid']."', widgetid = '".$search_results_widget['widgetid']."' WHERE pagetemplateid = '4' AND widgetid = '14'
				"
		);
	}
	/**
	 * creating route for search results
	 */
	public function step_4()
	{
		$this->skip_message();
	}

	/**
	 * creating page for search results
	 */
	public function step_5()
	{
		$this->skip_message();
	}
	/** Video */
	public function step_6()
	{
		$contenttype = $this->db->query_first("
			SELECT contenttypeid FROM " . TABLE_PREFIX . "contenttype
			WHERE class = 'Video'");
		if (empty($contenttype) OR empty($contenttype['contenttypeid']))
		{
			$this->run_query(
			sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'contenttype'),
			"INSERT INTO " . TABLE_PREFIX . "contenttype(class,
			packageid,	canplace,	cansearch,	cantag,	canattach,	isaggregator)
			SELECT 'Video', packageid, '1', '1', '1', '1', '1'  FROM " . TABLE_PREFIX . "package where class = 'vBForum';");
		}
		else
		{
			$this->skip_message();
		}

		$this->run_query(
		sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'video'),
		"
			CREATE TABLE " . TABLE_PREFIX . "video (
				nodeid INT UNSIGNED NOT NULL,
				PRIMARY KEY (nodeid)
			) ENGINE = " . $this->hightrafficengine . "
		",
		self::MYSQL_ERROR_TABLE_EXISTS
		);

		$this->run_query(
		sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'videoitem'),
		"
			CREATE TABLE " . TABLE_PREFIX . "videoitem (
				videoitemid INT UNSIGNED NOT NULL AUTO_INCREMENT,
				nodeid INT UNSIGNED NOT NULL,
				caption VARCHAR(255),
				provider VARCHAR(255),
				code VARCHAR(255),
				url VARCHAR(255),
				PRIMARY KEY (videoitemid),
				KEY nodeid (nodeid)
			) ENGINE = " . $this->hightrafficengine . "
		",
		self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	/**
	 * Video widget
	 */
	public function step_7()
	{
		$videowidget = $this->db->query_first("
			SELECT widgetid FROM " . TABLE_PREFIX . "widget
			WHERE template = 'widget_2'");

		if (!empty($videowidget['widgetid']))
		{
			// Rename video widget
			$this->run_query(
				sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'widget'),
				"
					UPDATE " . TABLE_PREFIX . "widget SET title = 'Video' WHERE widgetid = $videowidget[widgetid]
				"
			);

			// Modify video widget options
			$this->run_query(
				sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'widgetdefinition'),
				"
					DELETE FROM " . TABLE_PREFIX . "widgetdefinition WHERE widgetid = $videowidget[widgetid] AND name IN ('provider', 'videoid', 'url')
				"
			);
			$this->run_query(
				sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'widgetdefinition'),
				"
					INSERT INTO " . TABLE_PREFIX . "widgetdefinition
					(`widgetid`, `field`, `name`, `label`, `defaultvalue`, `isusereditable`, `isrequired`, `displayorder`, `validationtype`, `validationmethod`, `data`)
					VALUES
					($videowidget[widgetid], 'Text', 'url', 'Video Link', 'http://', 1, 1, 2, '', '', '')
				"
			);

		}
		else
		{
			$this->skip_message();
		}

	}

	/** Add users following moderate setting */
	public function step_8()
	{
		$this->run_query(
				sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'user'),
				"
				UPDATE " . TABLE_PREFIX . "user
				SET options = (options | 67108864)
				WHERE usergroupid IN (4, 8)
				"
		);
	}

	/*** Correct some routes*/
	public function step_9()
	{
		$this->skip_message();
	}


	/**
	 * Change the URL for the "search" navbar tab from 'search' to 'advanced_search'
	 */
	public function step_10()
	{
		/* This step was no longer needed
		as we no longer add the Search Tab */
		$this->skip_message();
	}

	/**
	 * Add default "Home" item to navbar
	 */
	public function step_11()
	{
		$site = $this->db->query_first("
			SELECT headernavbar
			FROM " . TABLE_PREFIX . "site
			WHERE siteid = 1
		");

		$update = true;

		if ($site AND $site['headernavbar'] AND ($navbar = @unserialize($site['headernavbar'])))
		{
			foreach ($navbar AS &$item)
			{
				if ($item['url'] == '/')
				{
					$update = false;
					break;
				}
			}
		}

		if (isset($navbar) AND $navbar AND $update)
		{
			$newItem = array(
				'url' => '/',
				'title' => 'Home',
			);

			array_unshift($navbar, $newItem);

			$this->run_query(
				sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'site'),
				"
					UPDATE " . TABLE_PREFIX . "site
					SET headernavbar = '" . serialize($navbar) . "'
					WHERE siteid = 1
				"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	 * Add default footer navigation items
	 */
	public function step_12()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'site'),
			"
				UPDATE " . TABLE_PREFIX . "site
				SET footernavbar = 'a:3:{i:0;a:3:{s:5:\"title\";s:10:\"Contact Us\";s:3:\"url\";s:10:\"contact-us\";s:9:\"newWindow\";s:1:\"0\";}i:1;a:4:{s:5:\"title\";s:5:\"Admin\";s:3:\"url\";s:7:\"admincp\";s:9:\"newWindow\";s:1:\"0\";s:10:\"usergroups\";a:1:{i:0;i:6;}}i:2;a:4:{s:5:\"title\";s:3:\"Mod\";s:3:\"url\";s:5:\"modcp\";s:9:\"newWindow\";s:1:\"0\";s:10:\"usergroups\";a:1:{i:0;i:6;}}}'
				WHERE
					siteid = 1
						AND
					footernavbar = ''
			"
		);

	}
}

class vB_Upgrade_500a41 extends vB_Upgrade_Version
{

	public function step_1()
	{
		if (!$this->field_exists('searchlog', 'type'))
		{
			$this->add_field(
				sprintf($this->phrase['core']['altering_x_table'], 'searchlog', 1, 2),
				'searchlog',
				'type',
				'SMALLINT',
				array('null' => false, 'default' => 0)
			);
		}
		else
		{
			$this->skip_message();
		}
	}
}

class vB_Upgrade_500a44 extends vB_Upgrade_Version
{
	/** turn off all access for password-protected forums.  */
	public function step_1()
	{
		if ($this->tableExists('forum'))
		{
			$this->show_message(sprintf($this->phrase['version']['500a44']['importing_forum_perms_1']));
			vB::getDbAssertor()->assertQuery('vBInstall:hidePasswordForums', array('forumTypeid' =>vB_Types::instance()->getContentTypeID('vBForum_Forum')));
		}
		else
		{
			$this->skip_message();
		}
	}

	//Importing forum permissions.
	public function step_2()
	{
		if ($this->tableExists('forum'))
		{
			$this->show_message(sprintf($this->phrase['version']['500a44']['importing_forum_perms_2']));
			$options = vB::getDatastore()->getValue('options');

/*
 			//we moved these from global options to channel by channel usergroup perms in vB5
			//however for varous and sundry reasons we added steps to import the settings
			//earlier and earlier in the upgrade process (so that code we rely on in various places still works)
			//which blows away these values.  This has been setting a lot of things to zeros by accident
			//for some time.  We also never attempted to set any of the new channels we create to these values,
			//instead using some hard coded params.
			$params['maxtags'] = $options['maxtags'];
			$params['maxstartertags'] = $options['tagmaxstarter'];
			$params['maxothertags'] = $options['tagmaxuser'];
			$params['maxattachments'] = $options['attachlimit'];
 */

			//hardcode values based on the defaults in the channel importer.
			$params = [
				'forumTypeid' => vB_Types::instance()->getContentTypeID('vBForum_Forum'),
				'editTime' => $options['noeditedbytime'],
				'maxtags' => 10,
				'maxstartertags' => 5,
				'maxothertags' => 5,
				'maxattachments' => 5,
			];

			vB::getDbAssertor()->assertQuery('vBInstall:setForumPermissions', $params);
		}
		else
		{
			$this->skip_message();
		}
	}

	/* Clear any style settings in user table. Those will only break the display in vB5 */
	public function step_3()
	{
		//We only need to do this if we are upgraded  a vB 3/4 install
		if ($this->tableExists('forum'))
		{
			$this->show_message(sprintf($this->phrase['version']['500a44']['clearing_user_styles']));
			vB::getDbAssertor()->assertQuery('vBInstall:clearUserStyle', array());
		}
	else
		{
			$this->skip_message();
		}
	}

}

class vB_Upgrade_500a45 extends vB_Upgrade_Version
{
	/** We display a message about search indices */
	public function step_1()
	{
		$this->show_message($this->phrase['version']['500a45']['rebuild_search_indices']);
	}

	public function step_2()
	{
		/* Adding of iconid to node table removed.
		Its part of the table creation in Alpha 1 Step 9, and also in the installs schema file
		No legitimate upgrades should ever need it adding here, as this version was never public */

		$this->skip_message();
	}
}

class vB_Upgrade_500a5 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'routenew'),
			"
			INSERT IGNORE INTO `" . TABLE_PREFIX . "routenew`
			(`name`, `prefix`, `regex`, `class`, `controller`, `action`, `template`, `arguments`, `contentid`)
			VALUES
			('admincp', 'admincp', 'admincp/(?P<file>[a-zA-Z0-9_.-]*)', 'vB5_Route_Admincp', 'relay', 'admincp', '', 'a:1:{s:4:\"file\";s:5:\"\$file\";}', 0)
			"
		);
	}

	public function step_2()
	{
		$route = $this->db->query_first("SELECT routeid FROM " . TABLE_PREFIX . "routenew WHERE name = 'profile'");
		$page = $this->db->query_first("SELECT pageid FROM " . TABLE_PREFIX . "page WHERE routeid = " . $route['routeid']);

		$query = "UPDATE " . TABLE_PREFIX . "page SET
			pagetype = '" . vB_Page::TYPE_CUSTOM . "'
			WHERE pageid = " . $page['pageid'];
		$this->run_query(
		sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'routenew'),$query);

		$query = "UPDATE " . TABLE_PREFIX . "routenew SET
			class = 'vB5_Route_Profile',
			prefix = '" . vB5_Route_Profile::DEFAULT_PREFIX . "',
			regex = '" . vB5_Route_Profile::DEFAULT_PREFIX . '/' . vB5_Route_Profile::REGEXP . "',
			arguments = '" . serialize(array('userid'=>'$userid', 'pageid'=>$page['pageid'])) . "'
			WHERE routeid = " . $route['routeid'];
		$this->run_query(
		sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'routenew'),$query);
	}
}

class vB_Upgrade_500a6 extends vB_Upgrade_Version
{
	//Set the "protected" flag properly
	public function step_1()
	{
		$this->skip_message();
	}

	public function step_2()
	{
		$this->skip_message();
	}
}

class vB_Upgrade_500a8 extends vB_Upgrade_Version
{
	/** Change user.autosubscribe default from -1 to 0 */
	public function step_1()
	{
		if ($this->field_exists('user', 'autosubscribe'))
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'user'),
				"UPDATE " . TABLE_PREFIX . "user
				SET autosubscribe = '0'
				WHERE autosubscribe = '-1'
			");
		}
		else
		{
			$this->skip_message();
		}
	}

	/** And change autosubscribe column */
	public function step_2()
	{
		if ($this->field_exists('user', 'autosubscribe'))
		{
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'user', 1, 1),
				"ALTER TABLE " . TABLE_PREFIX . "user CHANGE COLUMN autosubscribe autosubscribe SMALLINT(6) UNSIGNED NOT NULL DEFAULT '0'
				"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	// Fix duplicated phrase varnames for any custom languages in upgrade script
	// Add unique index on varname in phrase table (remove fieldname from current unique index)
	public function step_3()
	{
		// All languages including MASTER should be processed here.
		// Otherwise we can't add varname field as unique

		$phrase = array();
		$results = $this->db->query_read("
			SELECT varname
			FROM " . TABLE_PREFIX . "phrase
			GROUP BY varname
			HAVING COUNT(varname) > 1
		");
		while ($result = $this->db->fetch_array($results))
		{
			$phrase[] = $result['varname'];
		}

		if ($phrase)
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'phrase'),
				"UPDATE " . TABLE_PREFIX . "phrase SET
					varname = CONCAT(varname, '_g', fieldname)
				WHERE
					varname IN ('" . implode("', '", $phrase) . "')
						AND
					fieldname <> 'global'
			");
		}

		// Unique index
		$this->drop_index(
			sprintf($this->phrase['core']['altering_x_table'], 'phrase', 1, 2),
			'phrase',
			'name_lang_type'
		);

		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'phrase', 2, 2),
			'phrase',
			'name_lang_type',
			array('varname', 'languageid'),
			'unique'
		);

	}
	/** Add user.privacy_options field */
	public function step_4()
	{
		$this->skip_message();
	}
}

class vB_Upgrade_500a9 extends vB_Upgrade_Version
{
	/**
	 * Add default header navbar items
	 */
	public function step_1()
	{
		/* This step was no longer needed
		as we no longer add the Profile Tab */
		$this->skip_message();
	}

	/**
	 * Change subscribed/subscribers routenew.class name
	 */
	public function step_2()
	{
		if ($this->field_exists('routenew', 'name'))
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . "routenew"),
				"UPDATE " . TABLE_PREFIX . "routenew
				SET class = 'vB5_Route_Page'
				WHERE name = 'following' OR name = 'followers'
				"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	 * Change subscribed routenew.arguments
	 */
	public function step_3()
	{
		if ($this->field_exists('routenew', 'arguments') AND $this->field_exists('routenew', 'name'))
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . "routenew"),
				"UPDATE " . TABLE_PREFIX . "routenew
				SET arguments = '" . serialize(array('pageid' => 9)) . "'
				WHERE name = 'following'
				"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	 * Change subscribers routenew.arguments
	 */
	public function step_4()
	{
		if ($this->field_exists('routenew', 'arguments') AND $this->field_exists('routenew', 'name'))
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . "routenew"),
				"UPDATE " . TABLE_PREFIX . "routenew
				SET arguments = '" . serialize(array('pageid' => 10)) . "'
				WHERE name = 'followers'
				"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	 * Link
	 */
	public function step_5()
	{
		$contenttype = $this->db->query_first("
			SELECT contenttypeid FROM " . TABLE_PREFIX . "contenttype
			WHERE class = 'Link'");
		if (empty($contenttype) OR empty($contenttype['contenttypeid']))
		{
			$this->run_query(
			sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'contenttype'),
			"INSERT INTO " . TABLE_PREFIX . "contenttype(class,
			packageid,	canplace,	cansearch,	cantag,	canattach,	isaggregator)
			SELECT 'Link', packageid, '1', '1', '1', '1', '0'  FROM " . TABLE_PREFIX . "package where class = 'vBForum';");
		}
		else
		{
			$this->skip_message();
		}

		$this->run_query(
		sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'link'),
		"
			CREATE TABLE " . TABLE_PREFIX . "link (
				nodeid INT UNSIGNED NOT NULL,
				filedataid INT UNSIGNED NOT NULL DEFAULT '0',
				url VARCHAR(255),
				url_title VARCHAR(255),
				meta MEDIUMTEXT,
				PRIMARY KEY (nodeid),
				KEY (filedataid)
			) ENGINE = " . $this->hightrafficengine . "
		",
		self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	/**
	 * make search widget clonable
	 */
	public function step_6()
	{
		$skip_message = false;
		$search_results_widget = $this->db->query_first("
			SELECT widgetid FROM " . TABLE_PREFIX . "widget
			WHERE template = 'widget_search'");
		if (!empty($search_results_widget['widgetid']) AND empty($search_results_widget['cloneable']))
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'widget'),
				"
				UPDATE `" . TABLE_PREFIX . "widget` SET cloneable = '1' WHERE widgetid = '$search_results_widget[widgetid]'
				"
			);
		}
		else
		{
			$skip_message = true;
		}
	}

	/**
	 * add data in permission table for link and video
	 *
	 */
	public function step_7()
	{
		$this->run_query(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'permission'),
		"UPDATE " . TABLE_PREFIX . "permission
		SET createpermissions = createpermissions | 131072 |262144 WHERE createpermissions > 1;");
	}
}

class vB_Upgrade_500b1 extends vB_Upgrade_Version
{
	/*
	 *	We changed how we choose the table driver for "memory" tables to
	 *	favor Innodb over the memory engine.  Convert the engine here.
	 */
	public function step_1()
	{
		global $db;
		$memory = get_memory_engine($db);
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'cpsession', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "cpsession ENGINE = $memory"
		);

		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'session', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "session ENGINE = $memory"
		);
	}

	/**
	 * hide the create blog subnav item for usergroups that are not allowed to create blogs
	 */
	public function step_2()
	{
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], 'site'));

		$assertor = vB::getDbAssertor();

		$forumpermissions = vB::getDatastore()->getValue('bf_ugp_forumpermissions');
		if (empty($forumpermissions['cancreateblog']))
		{
			$forumpermissions = array();
			$parsedRaw = vB_Xml_Import::parseFile(DIR . '/includes/xml/bitfield_vbulletin.xml');
			foreach ($parsedRaw['bitfielddefs']['group'] AS $group)
			{
				if ($group['name'] == 'ugp')
				{
					foreach($group['group'] AS $bfgroup)
					{
						if ($bfgroup['name'] == 'forumpermissions')
						{
							foreach ($bfgroup['bitfield'] AS $bitfield)
							{
								$forumpermissions[$bitfield['name']] = intval($bitfield['value']);
							}
						}
					}
				}
			}
		}
		//these are the user groups that are allowed to create blogs
		$groups = $assertor->getRows('usergroup', array(
				vB_dB_Query::CONDITIONS_KEY => array(
						array('field' => 'forumpermissions', 'value' => $forumpermissions['cancreateblog'], 'operator' => vB_dB_Query::OPERATOR_AND)
				)
			),
			false,
			'usergroupid'
		);

		$sites = $assertor->assertQuery('vBForum:site');
		foreach ($sites AS $site)
		{
			$changed = false;
			$header = unserialize($site['headernavbar']);
			if (!empty($header))
			{
				foreach ($header as &$h)
				{
					if ($h['title'] == 'navbar_blogs' AND !empty($h['subnav']))
					{
						foreach ($h['subnav'] as &$sn)
						{
							if ($sn['title'] == 'navbar_create_a_new_blog')
							{
								$sn['usergroups'] = array_keys($groups);
								$changed = true;
								break;
							}
						}
					}
				}
			}
			if ($changed)
			{
				$assertor->update('vBForum:site', array('headernavbar' => serialize($header)), array('siteid' => $site['siteid']));
			}
		}
	}
}

class vB_Upgrade_500b11 extends vB_Upgrade_Version
{
	/**
	 * add subscribediscussion.oldid
	 * To import subscriptions
	 *
	 */
	public function step_1()
	{
		if ($this->tableExists('subscribediscussion') AND !$this->field_exists('subscribediscussion', 'oldid'))
		{
			$this->add_field(
				sprintf($this->phrase['core']['altering_x_table'], 'subscribediscussion', 1, 4),
				'subscribediscussion',
				'oldid',
				'INT',
				array('length' => 10)
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	 * add subscribediscussion.oldtypeid
	 * To import subscriptions
	 *
	 */
	public function step_2()
	{
		if ($this->tableExists('subscribediscussion') AND !$this->field_exists('subscribediscussion', 'oldtypeid'))
		{
			$this->add_field(
				sprintf($this->phrase['core']['altering_x_table'], 'subscribediscussion', 2, 4),
				'subscribediscussion',
				'oldtypeid',
				'INT',
				array('length' => 10)
			);

			$this->drop_index(
				sprintf($this->phrase['core']['altering_x_table'], 'subscribediscussion', 3, 4),
				'subscribediscussion',
				'userdiscussion'
			);

			$this->add_index(
				sprintf($this->phrase['core']['altering_x_table'], 'subscribediscussion', 4, 4),
				'subscribediscussion',
				'userdiscussion_type',
				array('userid', 'discussionid', 'oldtypeid')
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	 * Import group discussion subscriptions
	 */
	public function step_3($data = [])
	{
		if ($this->tableExists('subscribediscussion') AND $this->tableExists('node') AND $this->tableExists('discussion'))
		{
			$startat = intval($data['startat'] ?? 0);
			$assertor = vB::getDbAssertor();
			$discussionTypeid = vB_Types::instance()->getContentTypeID('vBForum_SocialGroupDiscussion');
			$batchsize = 5000;

			if ($startat == 0)
			{
				$maxvB5 = $assertor->getRow('vBInstall:getMaxImportedSubscription', array(
					'oldtypeid' => $discussionTypeid
				));
				$startat = intval($maxvB5['maxid']);
			}

			if (!empty($data['maxvB4']))
			{
				$maxvB4 = intval($data['maxvB4']);
			}
			else
			{
				$maxvB4 = $assertor->getRow('vBInstall:getMaxGroupDiscussionSubscriptionId', array('discussiontypeid' => $discussionTypeid));
				$maxvB4 = intval($maxvB4['maxid']);

				//If we don't have any posts, we're done.
				if ($maxvB4 < 1)
				{
					$this->skip_message();
					return;
				}
			}

			if ($maxvB4 <= $startat)
			{
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}

			$this->show_message(sprintf($this->phrase['version']['500b11']['importing_x_subscriptions'], 'Group Discussions'));
			$assertor->assertQuery('vBInstall:importDiscussionSubscriptions',
				array('startat' => $startat, 'batchsize' => $batchsize,'discussiontypeid' => $discussionTypeid));

			$this->show_message(sprintf($this->phrase['core']['processed_records_x'], $batchsize));
			return array('startat' => ($startat + $batchsize));
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	 * Remove no longer needed records
	 */
	public function step_4()
	{
		$this->show_message($this->phrase['core']['may_take_some_time']);
		$this->show_message($this->phrase['version']['500b11']['cleaning_subscribediscussion_table']);
		$assertor = vB::getDbAssertor();
		$discussionTypeid = vB_Types::instance()->getContentTypeID('vBForum_SocialGroupDiscussion');
		$maxvB4 = $assertor->getRow('vBInstall:getMaxGroupDiscussionSubscriptionId', array('discussiontypeid' => $discussionTypeid));
		if ($maxvB4 < 1)
		{
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			return;
		}

		vB::getDbAssertor()->assertQuery('vBInstall:deleteGroupSubscribedDiscussion', array('discussiontypeid' => $discussionTypeid));
		$this->show_message(sprintf($this->phrase['core']['process_done']));
	}

	/**
	 * Import forum subscriptions
	 */
	public function step_5($data = [])
	{
		if ($this->tableExists('subscribediscussion') AND $this->tableExists('node') AND $this->tableExists('forum') AND $this->tableExists('subscribeforum'))
		{
			$startat = intval($data['startat'] ?? 0);
			$assertor = vB::getDbAssertor();
			$forumtypeid = vB_Types::instance()->getContentTypeID('vBForum_Forum');
			$batchsize = 5000;

			if ($startat == 0)
			{
				$maxvB5 = $assertor->getRow('vBInstall:getMaxImportedSubscription', array(
					'oldtypeid' => $forumtypeid
				));
				$startat = intval($maxvB5['maxid']);
			}

			if (!empty($data['maxvB4']))
			{
				$maxvB4 = intval($data['maxvB4']);
			}
			else
			{
				$maxvB4 = $assertor->getRow('vBInstall:getMaxForumSubscriptionId', array('forumtypeid' => $forumtypeid));
				$maxvB4 = intval($maxvB4['maxid']);

				//If we don't have any posts, we're done.
				if ($maxvB4 < 1)
				{
					$this->skip_message();
					return;
				}
			}

			if ($maxvB4 <= $startat)
			{
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}

			$this->show_message(sprintf($this->phrase['version']['500b11']['importing_x_subscriptions'], 'Forum'));
			$assertor->assertQuery('vBInstall:importForumSubscriptions',
				array('startat' => $startat, 'batchsize' => $batchsize, 'forumtypeid' => $forumtypeid));

			$this->show_message(sprintf($this->phrase['core']['processed_records_x'], $batchsize));
			return array('startat' => ($startat + $batchsize));
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	 * Import thread subscriptions
	 */
	public function step_6($data = [])
	{
		if ($this->tableExists('subscribediscussion') AND $this->tableExists('node') AND $this->tableExists('thread') AND $this->tableExists('subscribethread'))
		{
			$startat = intval($data['startat'] ?? 0);
			$assertor = vB::getDbAssertor();
			$threadtypeid = vB_Types::instance()->getContentTypeID('vBForum_Thread');
			$batchsize = 5000;

			if ($startat == 0)
			{
				$maxvB5 = $assertor->getRow('vBInstall:getMaxImportedSubscription', array(
					'oldtypeid' => $threadtypeid
				));
				$startat = intval($maxvB5['maxid']);
			}

			if (!empty($data['maxvB4']))
			{
				$maxvB4 = intval($data['maxvB4']);
			}
			else
			{
				$maxvB4 = $assertor->getRow('vBInstall:getMaxThreadSubscriptionId', array('threadtypeid' => $threadtypeid));
				$maxvB4 = intval($maxvB4['maxid']);

				//If we don't have any posts, we're done.
				if ($maxvB4 < 1)
				{
					$this->skip_message();
					return;
				}
			}

			if ($maxvB4 <= $startat)
			{
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}

			$this->show_message(sprintf($this->phrase['version']['500b11']['importing_x_subscriptions'], 'Thread'));
			$assertor->assertQuery('vBInstall:importThreadSubscriptions',
				array('startat' => $startat, 'batchsize' => $batchsize, 'threadtypeid' => $threadtypeid));

			$this->show_message(sprintf($this->phrase['core']['processed_records_x_y'], $startat + 1, $startat + $batchsize - 1));
			return array('startat' => ($startat + $batchsize));
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	 * Import group subscriptions
	 */
	public function step_7($data = [])
	{
		if ($this->tableExists('subscribediscussion') AND $this->tableExists('node') AND $this->tableExists('socialgroup') AND $this->tableExists('subscribegroup'))
		{
			$startat = intval($data['startat'] ?? 0);
			$assertor = vB::getDbAssertor();
			$grouptypeid = vB_Types::instance()->getContentTypeID('vBForum_SocialGroup');
			$batchsize = 5000;

			if ($startat == 0)
			{
				$maxvB5 = $assertor->getRow('vBInstall:getMaxImportedSubscription', array(
					'oldtypeid' => $grouptypeid
				));
				$startat = intval($maxvB5['maxid']);
			}

			if (!empty($data['maxvB4']))
			{
				$maxvB4 = intval($data['maxvB4']);
			}
			else
			{
				$maxvB4 = $assertor->getRow('vBInstall:getMaxGroupSubscriptionId', array('grouptypeid' => $grouptypeid));
				$maxvB4 = intval($maxvB4['maxid']);

				//If we don't have any posts, we're done.
				if ($maxvB4 < 1)
				{
					$this->skip_message();
					return;
				}
			}

			if ($maxvB4 <= $startat)
			{
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}

			$this->show_message(sprintf($this->phrase['version']['500b11']['importing_x_subscriptions'], 'Social Group'));
			$assertor->assertQuery('vBInstall:importGroupSubscriptions',
				array('startat' => $startat, 'batchsize' => $batchsize, 'grouptypeid' => $grouptypeid));

			$this->show_message(sprintf($this->phrase['core']['processed_records_x'], $batchsize));
			return array('startat' => ($startat + $batchsize));
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_8($data = array())
	{
		if ($this->tableExists('blog_text'))
		{
			$this->show_message(sprintf($this->phrase['vbphrase']['importing_from_x'], 'blog_text'));
			if (empty($data['startat']))
			{
				$startat = 0;
			}
			else
			{
				$startat = $data['startat'];
			}
			$assertor = vB::getDbAssertor();
			$userid = $assertor->getRow('vBInstall:getNextBlogUserid', array('startat' => $startat));

			if (empty($userid) OR !empty($userid['errors']) or empty($userid['userid']))
			{
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}
			$userid = $userid['userid'];

			$maxNodeId = $assertor->getRow('vBInstall:getMaxNodeid', array());
			$maxNodeId = $maxNodeId['maxid'];
			$missingQry = $assertor->assertQuery('vBInstall:getMissedBlogStarters', array('userid' => $userid));

			if (!$missingQry->valid())
			{
				return array('startat' => $userid);
			}

			$blogtextids = array();
			$parentid = 0;

			foreach($missingQry AS $blogInfo)
			{
				$blogtextids[] = $blogInfo['blogtextid'];

				if (!$parentid)
				{
					$parentid = $blogInfo['nodeid'];
					$routeid =  $blogInfo['routeid'];
				}
			}
			$texttype = vB_Types::instance()->getContentTypeID('vBForum_Text');
			$assertor->assertQuery('vBInstall:importMissingBlogStarters',
				array('texttype' => $texttype, 'parentid' => $parentid, 'blogtextids' => $blogtextids, 'routeid' => $routeid));
			$reccount = $assertor->getRow('vBInstall:getProcessedCount', array());

			if (empty($reccount) OR !empty($reccount['errors']) or empty($reccount['recs']))
			{
				return array('startat' => $userid);
			}

			$assertor->assertQuery('vBInstall:fixMissingBlogStarter', array('startnodeid' => $maxNodeId));
			$assertor->assertQuery('vBInstall:importMissingBlogResponses',
				array('texttype' => $texttype, 'blogtextids' => $blogtextids));
			$assertor->assertQuery('vBInstall:importMissingBlogText', array('startnodeid' => $maxNodeId));
			$assertor->assertQuery('vBInstall:createMissingBlogClosureSelf', array('startnodeid' => $maxNodeId));
			$assertor->assertQuery('vBInstall:createMissingBlogClosurefromParent', array('startnodeid' => $maxNodeId,
				'oldcontenttypeid' => 9985));
			$assertor->assertQuery('vBInstall:createMissingBlogClosurefromParent', array('startnodeid' => $maxNodeId,
				'oldcontenttypeid' => 9984));

			return array('startat' => $userid);
		}
		else
		{
			$this->skip_message();
		}
	}

	/**  Fix the blog starter counts */
	public function step_9()
	{
		if ($this->tableExists('blog_text'))
		{
			$assertor = vB::getDbAssertor();
			$this->show_message(sprintf($this->phrase['version']['500b11']['updating_blog_summary_step_x'], 1));

			$assertor->assertQuery('vBInstall:fixBlogStarterLast', array());
		}
		else
		{
			$this->skip_message();
		}
	}

	/**  Fix the blog counts */
	public function step_10()
	{
		if ($this->tableExists('blog_text'))
		{
			$assertor = vB::getDbAssertor();
			$this->show_message(sprintf($this->phrase['version']['500b11']['updating_blog_summary_step_x'], 2));

			$assertor->assertQuery('vBInstall:fixBlogChannelCount', array());
		}
		else
		{
			$this->skip_message();
		}
	}

	/**  Fix the blog last date */
	public function step_11()
	{
		if ($this->tableExists('blog_text'))
		{
			$assertor = vB::getDbAssertor();
			$this->show_message(sprintf($this->phrase['version']['500b11']['updating_blog_summary_step_x'], 3));

			$assertor->assertQuery('vBInstall:fixBlogChannelLast', array());
		}
		else
		{
			$this->skip_message();
		}
	}

	/** Update Channel counts**/
	public function step_12($data = NULL)
	{
		//Here we run until we aren't changing anything. In essence each time we run we ascend one time up the hierarchy.
		$assertor = vB::getDbAssertor();
		$startat = intval($data['startat'] ?? 0);

		if ($startat > 10)
		{
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			return;
		}

		$this->show_message(sprintf($this->phrase['version']['500b11']['correcting_channel_counts'], TABLE_PREFIX . 'node'));
		$assertor->assertQuery('vBInstall:updateChannelCounts',
			array('channelTypeid' => vB_Types::instance()->getContentTypeID('vBForum_Channel'),
				'textTypeid' => vB_Types::instance()->getContentTypeID('vBForum_Text'),
				'pollTypeid' =>  vB_Types::instance()->getContentTypeID('vBForum_Poll')));
		$processed = $assertor->getRow('vBInstall:getProcessedCount', array());
		$processed = $processed['recs'];
		if (empty($processed))
		{
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			return;
		}

		return array('startat' => ($startat + 1));
	}

	/**
	 * Import user's blog subscriptions
	 */
	public function step_13($data = [])
	{
		if ($this->tableExists('subscribediscussion') AND $this->tableExists('node') AND $this->tableExists('blog_subscribeuser'))
		{
			$startat = intval($data['startat'] ?? 0);
			$assertor = vB::getDbAssertor();
			$channeltypeid = vB_Types::instance()->getContentTypeID('vBForum_Channel');
			$membergid = $assertor->getRow('usergroup', array(
				'systemgroupid' => vB_Api_UserGroup::CHANNEL_MEMBER_SYSGROUPID
			));
			$groupid = $membergid['usergroupid'];
			$batchsize = 5000;

			if ($startat == 0)
			{
				$maxvB5 = $assertor->getRow('vBInstall:getMaxImportedBlogUserSubscriptionId', array(
					'channeltypeid' => $channeltypeid,
					'membergroupid' => $groupid
				));
				$startat = intval($maxvB5['maxid']);
			}

			if (!empty($data['maxvB4']))
			{
				$maxvB4 = intval($data['maxvB4']);
			}
			else
			{
				$maxvB4 = $assertor->getRow('vBInstall:getMaxBlogUserSubscriptionId', array('channeltypeid' => $channeltypeid));
				$maxvB4 = intval($maxvB4['maxid']);

				//If we don't have any posts, we're done.
				if ($maxvB4 < 1)
				{
					$this->skip_message();
					return;
				}
			}

			if ($maxvB4 <= $startat)
			{
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}

			$this->show_message(sprintf($this->phrase['version']['500b11']['importing_x_subscriptions'], 'Blog User'));
			$assertor->assertQuery('vBInstall:importBlogUserSubscriptions',
				array('startat' => $startat, 'batchsize' => $batchsize,'channeltypeid' => $channeltypeid, 'membergroupid' => $groupid));

			$this->show_message(sprintf($this->phrase['core']['processed_records_x'], $batchsize));
			return array('startat' => ($startat + $batchsize));
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	 * Import blog entries subscriptions
	 */
	public function step_14($data = [])
	{
		if ($this->tableExists('subscribediscussion') AND $this->tableExists('node') AND $this->tableExists('blog_subscribeentry'))
		{
			$startat = intval($data['startat'] ?? 0);
			$assertor = vB::getDbAssertor();
			$batchsize = 5000;
			if ($startat == 0)
			{
				$maxvB5 = $assertor->getRow('vBInstall:getMaxImportedSubscription', array(
					'oldtypeid' => 9985
				));
				$startat = intval($maxvB5['maxid']);
			}

			if (!empty($data['maxvB4']))
			{
				$maxvB4 = intval($data['maxvB4']);
			}
			else
			{
				$maxvB4 = $assertor->getRow('vBInstall:getMaxBlogEntrySubscriptionId');
				$maxvB4 = intval($maxvB4['maxid']);

				//If we don't have any posts, we're done.
				if ($maxvB4 < 1)
				{
					$this->skip_message();
					return;
				}
			}

			if ($maxvB4 <= $startat)
			{
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}

			$this->show_message(sprintf($this->phrase['version']['500b11']['importing_x_subscriptions'], 'Blog Entries'));
			$assertor->assertQuery('vBInstall:importBlogEntrySubscriptions',
				array('startat' => $startat, 'batchsize' => $batchsize, 'blogentryid' => 9985));

			$this->show_message(sprintf($this->phrase['core']['processed_records_x'], $batchsize));
			return array('startat' => ($startat + $batchsize));
		}
		else
		{
			$this->skip_message();
		}
	}

	/**Add an enumerated value to private message types **/
	public function step_15()
	{
		vB_Upgrade::createAdminSession();
		$types = "'" . implode("','", array_merge(vB_Library::instance('content_privatemessage')->fetchNotificationTypes(),
				vB_Library::instance('content_privatemessage')->getChannelRequestTypes())) .  "'";
		$this->run_query(

				sprintf($this->phrase['core']['altering_x_table'], 'privatemessage', 1, 1),
				"ALTER TABLE " . TABLE_PREFIX . "privatemessage CHANGE about about ENUM($types); "
		);
	}
}

class vB_Upgrade_500b12 extends vB_Upgrade_Version
{
	/** Add two indices to the route table
	 */
	public function step_1()
	{
		// Add new index
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'routenew', 1, 2),
			'routenew',
			'route_name',
			'name'
		);

	}

	/** Add two indices to the route table
	 */
	public function step_2()
	{
		// Add new index
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'routenew', 2, 2),
			'routenew',
			'route_class_cid',
			array('class', 'contentid')
		);
	}

	/** Make sure every node has a routed
	 */
	public function step_3($data = array())
	{
		$batchsize = 10000;
		if (empty($data['startat']))
		{
			$startat = 0;
		}
		else
		{
			$startat = $data['startat'];
		}
		$this->show_message(sprintf($this->phrase['version']['500b12']['updating_content_routes'], $startat));
		$assertor = vB::getDbAssertor();
		$maxNodeId = $assertor->getRow('vBInstall:getMaxNodeid', array());
		$maxNodeId = $maxNodeId['maxid'];

		if ($startat >= $maxNodeId)
		{
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			return;
		}
		$assertor->assertQuery('vBInstall:fixNodeRouteid', array('startat' => $startat,
			'batchsize' => $batchsize, 'channelContenttypeid' =>  vB_Types::instance()->getContentTypeID('vBForum_Channel')));

		return array('startat' => $startat + $batchsize);
	}

	/** Remove blogcategories from the widgetinstance table	 */
	public function step_4()
	{
		$this->show_message($this->phrase['version']['500b12']['deleting_blog_categories_widget']);
		$assertor = vB::getDbAssertor();
		$widget = $assertor->getRow('widget', array(
			vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
			vB_dB_Query::CONDITIONS_KEY => array('guid' => 'vbulletin-widget_blogcategories-4eb423cfd6dea7.34930850')
		));

		$assertor->delete('widgetinstance', array('widgetid' => $widget['widgetid']));
	}

	/** Fix routeid in page table for social group home if needed */
	public function step_5()
	{

		$assertor = vB::getDbAssertor();
		$sgPage = $assertor->getRow('page', array(
			vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
			vB_dB_Query::CONDITIONS_KEY => array('routeid' => 0, 'guid' => 'vbulletin-4ecbdac82f2c27.60323372')
		));

		if ($sgPage)
		{
			$this->show_message($this->phrase['version']['500b12']['fix_sghome_routeid']);

			$route = $assertor->getRow('routenew', array(
				vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
				vB_dB_Query::CONDITIONS_KEY => array('guid' => 'vbulletin-4ecbdac93742a5.43676037')
			));

			$assertor->update('page',
				array('routeid' => $route['routeid']),
				array('pageid' => $sgPage['pageid'])
			);
		}
		else
		{
			$this->skip_message();
		}
	}
}

class vB_Upgrade_500b15 extends vB_Upgrade_Version
{
	/**
	 * Set systemgroupid for those groups
	 * Needed here due beta maintenance, we don't want to rerun old upgraders for this
	 */
	public function step_1()
	{
		if ($this->field_exists('usergroup', 'systemgroupid'))
		{
			vB::getDbAssertor()->assertQuery('vBInstall:alterSystemgroupidField');
		}

		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'usergroup'));
		vB::getDbAssertor()->assertQuery('vBInstall:setDefaultUsergroups', array());
	}

	/**
	 * Set banned group as custom
	 */
	public function step_2()
	{
		$this->show_message($this->phrase['version']['500b15']['setting_banned_ugp']);
		$ugpOptions = vB::getDatastore()->getValue('bf_ugp_genericoptions');
		vB::getDbAssertor()->assertQuery('vBInstall:setUgpAsDefault',
			array('ugpid' => 8, 'bf_value' => $ugpOptions['isnotbannedgroup'])
		);
	}
}

class vB_Upgrade_500b16 extends vB_Upgrade_Version
{
	/* Step #1
	 *
	 * Drop the parent index from closure as we need to extend it with more fields. This may be painful
	 */
	public function step_1()
	{
		$this->drop_index(
			sprintf($this->phrase['core']['altering_x_table'], 'closure', 1, 3),
			'closure',
			'parent'
		);
	}

	/*Step #2
	 *
	 * Add more fields to the parent index on closure for the updateLastData query
	 */
	public function step_2()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'closure', 2, 3),
			'closure',
			'parent_2',
			array('parent', 'depth', 'publishdate', 'child')
		);
	}

	/*Step #3
	 *
	 * Add index on publishdate for the updateLastData query
	 */
	public function step_3()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'closure', 3, 3),
			'closure',
			'publishdate',
			array('publishdate', 'child')
		);
	}

	/** Add a message about counts */
	public function step_4()
	{
		$this->add_adminmessage('after_upgrade_from_4',
			array('dismissable' => 1,
			'status'  => 'undone',));
	}

	/*Step #5
	 *
	 * Add index on node for selecting by nodeid and ordering by contenttypeid
	 */
	public function step_5()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'node', 1, 1),
			'node',
			'nodeid',
			array('nodeid', 'contenttypeid')
		);
	}
}

class vB_Upgrade_500b17 extends vB_Upgrade_Version
{
	/**
	 * Add missing text records for albums
	 */
	public function step_1($data = [])
	{
		if ($this->tableExists('album'))
		{
			$assertor = vB::getDbAssertor();
			$batchSize = 1000;
			$startat = intval($data['startat'] ?? 0);
			$albumTypeid = vB_Types::instance()->getContentTypeID('vBForum_Album');

			if ($startat == 0)
			{
				$this->show_message($this->phrase['version']['500b17']['adding_album_textrecords']);
				$maxvB5 = $assertor->getRow('vBInstall:getMaxvB5AlbumText', array('albumtypeid' => $albumTypeid));
				$startat = intval($maxvB5['maxid']);
			}

			if (!empty($data['maxvB4']))
			{
				$maxvB4 = intval($data['maxvB4']);
			}
			else
			{
				$maxvB4 = $assertor->getRow('vBInstall:getMaxvB4AlbumMissingText', array('albumtypeid' => $albumTypeid));
				$maxvB4 = intval($maxvB4['maxid']);

				//If we don't have any posts, we're done.
				if ($maxvB4 < 1)
				{
					$this->skip_message();
					return;
				}
			}

			if ($maxvB4 <= $startat)
			{
				$this->show_message($this->phrase['core']['process_done']);
				return;
			}

			$assertor->assertQuery('vBInstall:addMissingTextAlbumRecords',
				array('albumtypeid' => $albumTypeid, 'startat' => $startat, 'batchsize' => $batchSize));

			// and set starter
			$this->show_message(sprintf($this->phrase['core']['processed_records_x'], $batchSize));
			return array('startat' => ($startat + $batchSize), 'maxvB4' => $maxvB4);
		}
		else
		{
			$this->skip_message();
		}

	}

	public function step_2()
	{
		if ($this->tableExists('album'))
		{
			$assertor = vB::getDbAssertor();
			$albumTypeid = vB_Types::instance()->getContentTypeID('vBForum_Album');

			$oldid = $assertor->getRow('vBInstall:getMinvB5AlbumMissingStarter', array('albumtypeid' => $albumTypeid));
			if (empty($oldid['minid']))
			{
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}
			$this->show_message(sprintf($this->phrase['version']['500b17']['setting_x_starters'], 'Album'));
			$assertor->assertQuery('vBInstall:setStarter', array('contenttypeid' => $albumTypeid, 'startat' => ($oldid['minid'] - 1)));
		}
		else
		{
			$this->skip_message();
		}
	}

	/** This set the moderator permissions */
	public function step_3()
	{
		if ($this->field_exists('moderator', 'forumid'))
		{
			$this->show_message($this->phrase['version']['500b17']['updating_moderator_permissions']);
			$assertor = vB::getDbAssertor();
			$assertor->assertQuery('vBForum:moderator', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE, 'nodeid' => 1,
				vB_dB_Query::CONDITIONS_KEY => array('forumid' => -1)));
			$assertor->assertQuery('vBInstall:updateModeratorNodeid',
				array('forumtype' => vB_Types::instance()->getContentTypeID('vBForum_Forum')));
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_4()
	{
		if (!$this->tableExists('mapiposthash'))
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'mapiposthash'),
				"
				CREATE TABLE " . TABLE_PREFIX . "mapiposthash (
					posthashid INT UNSIGNED NOT NULL AUTO_INCREMENT,
					posthash VARCHAR(32) NOT NULL DEFAULT '',
					filedataid INT UNSIGNED NOT NULL DEFAULT '0',
					dateline INT UNSIGNED NOT NULL DEFAULT '0',
					PRIMARY KEY (posthashid),
					KEY posthash (posthash)
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

	public function step_5($data = [])
	{
		if ($this->tableExists('customavatar'))
		{
			$assertor = vB::getDbAssertor();
			$batchSize = 1000;

			if (!empty($data['startat']))
			{
				$this->show_message($this->phrase['version']['500b17']['fixing_custom_avatars']);
			}

			$fixId = $assertor->getRow('vBInstall:getMinCustomAvatarToFix');
			$startat = intval($fixId['minid']);

			if (!$startat)
			{
				$this->skip_message();
				return;
			}

			$assertor->assertQuery('vBInstall:fixCustomAvatars', ['startat' => ($startat - 1), 'batchsize' => $batchSize]);

			// and set starter
			$this->show_message(sprintf($this->phrase['core']['processed_records_x'], $batchSize));
			return ['startat' => ($startat + $batchSize)];
		}
		else
		{
			$this->skip_message();
		}
	}
}

class vB_Upgrade_500b18 extends vB_Upgrade_Version
{
	/** Set Imported Blog Post Url Identities **/
	public function step_1($data = [])
	{
		if (isset($this->registry->products['vbblog']) AND $this->registry->products['vbblog'])
		{
			$batchsize = 2000;
			$startat = intval($data['startat'] ?? 0);
			$assertor = vB::getDbAssertor();
			if (!isset($data))
			{
				$data = array();
			}

			if ($startat == 0)
			{
				$this->show_message($this->phrase['version']['500b18']['fixing_blog_post_url_identities']);
			}

			if (!isset($data['maxoldid']))
			{
				$maxOldIdQuery = $assertor->getRow('vBInstall:getMaxImportedBlogStarter', []);
				$data['maxoldid'] = intval($maxOldIdQuery['maxid']);
			}

			if ($startat > $data['maxoldid'])
			{
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}

			$blogNodes = $assertor->assertQuery('vBForum:node', array(
				vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
				vB_dB_Query::COLUMNS_KEY => array('nodeid', 'title'),
				vB_dB_Query::CONDITIONS_KEY => array(
					array('field' => 'oldid', 'value' => $startat, 'operator' => vB_dB_Query::OPERATOR_GT),
					array('field' => 'oldid', 'value' => ($startat + $batchsize + 1), 'operator' => vB_dB_Query::OPERATOR_LT),
					array('field' => 'oldcontenttypeid', 'value' => 9985, 'operator' => vB_dB_Query::OPERATOR_EQ),
				),
			));

			$urlIdentNodes = array();
			foreach($blogNodes AS $key => $node)
			{
				$node['urlident'] = vB_String::getUrlIdent($node['title']);
				$urlIdentNodes[] = $node;
			}
			$assertor->assertQuery('vBInstall:updateUrlIdent', array('nodes' => $urlIdentNodes));
			$this->show_message(sprintf($this->phrase['core']['processed_records_x'], $batchsize));
			return array('startat' => ($startat + $batchsize), 'maxoldid' => $data['maxoldid']);
		}
		else
		{
			$this->skip_message();
		}
	}

	/** Set Imported Group Discussion Url Identities **/
	public function step_2($data = [])
	{
		if ($this->tableExists('socialgroup') AND $this->tableExists('discussion') AND $this->tableExists('groupmessage'))
		{
			$batchsize = 2000;
			$startat = intval($data['startat'] ?? 0);
			$assertor = vB::getDbAssertor();
			$discussionTypeid = vB_Types::instance()->getContentTypeID('vBForum_SocialGroupDiscussion');
			if (!isset($data))
			{
				$data = array();
			}

			if ($startat == 0)
			{
				$this->show_message($this->phrase['version']['500b18']['fixing_group_discussion_url_identities']);
			}

			if (!isset($data['maxoldid']))
			{
				$maxOldIdQuery = $assertor->getRow('vBInstall:getMaxSGDiscussion', array('discussionTypeid' => $discussionTypeid));
				$data['maxoldid'] = intval($maxOldIdQuery['maxid']);
			}

			if ($startat > $data['maxoldid'])
			{
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}

			$discussionNodes = $assertor->assertQuery('vBForum:node', array(
				vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
				vB_dB_Query::COLUMNS_KEY => array('nodeid', 'title'),
				vB_dB_Query::CONDITIONS_KEY => array(
					array('field' => 'oldid', 'value' => $startat, 'operator' => vB_dB_Query::OPERATOR_GT),
					array('field' => 'oldid', 'value' => ($startat + $batchsize + 1), 'operator' => vB_dB_Query::OPERATOR_LT),
					array('field' => 'oldcontenttypeid', 'value' => $discussionTypeid, 'operator' => vB_dB_Query::OPERATOR_EQ),
				),
			));

			$urlIdentNodes = array();
			foreach($discussionNodes AS $key => $node)
			{
				$node['urlident'] = vB_String::getUrlIdent($node['title']);
				$urlIdentNodes[] = $node;
			}
			$assertor->assertQuery('vBInstall:updateUrlIdent', array('nodes' => $urlIdentNodes));
			$this->show_message(sprintf($this->phrase['core']['processed_records_x'], $batchsize));
			return array('startat' => ($startat + $batchsize), 'maxoldid' => $data['maxoldid']);
		}
		else
		{
			$this->skip_message();
		}
	}

	/** Drop csscolors column **/
	public function step_3()
	{
		if ($this->field_exists('style', 'csscolors'))
		{
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'style', 1, 3),
				"ALTER TABLE " . TABLE_PREFIX . "style DROP COLUMN csscolors"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/** Drop css column **/
	public function step_4()
	{
		if ($this->field_exists('style', 'css'))
		{
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'style', 2, 3),
				"ALTER TABLE " . TABLE_PREFIX . "style DROP COLUMN css"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/** Drop stylevars column **/
	public function step_5()
	{
		if ($this->field_exists('style', 'stylevars'))
		{
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'style', 3, 3),
				"ALTER TABLE " . TABLE_PREFIX . "style DROP COLUMN stylevars"
			);
		}
		else
		{
			$this->skip_message();
		}

		$this->long_next_step();
	}

	/*Step #6
	 *
	 * Add index on node.lastauthorid
	 */
	public function step_6()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'node', 1, 1),
			'node',
			'node_lastauthorid',
			'lastauthorid'
		);
	}
}

class vB_Upgrade_500b19 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->skip_message();
	}

	/** Update profile picture size to 200x200 */
	public function step_2()
	{
		$this->show_message($this->phrase['version']['500b19']['update_profile_picture_size']);

		vB::getDbAssertor()->update('usergroup',
			[
				'avatarmaxwidth' => 200,
				'avatarmaxheight' => 200,
			],
			[
				'avatarmaxwidth' => 165,
				'avatarmaxheight' => 165,
			]
		);
	}
}

class vB_Upgrade_500b20 extends vB_Upgrade_Version
{
	/**
	 * Step 1
	 */
	public function step_1()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'widget', 1, 1),
			'widget',
			'canbemultiple',
			'TINYINT',
			[
				'length' => 3,
				'default' => '1'
			]
		);
	}

	/** Add threadcomment notification types to about in privatemessage */
	public function step_2()
	{
		if ($this->field_exists('privatemessage', 'about'))
		{
			$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'privatemessage', 1, 1),
				"ALTER TABLE " . TABLE_PREFIX . "privatemessage MODIFY COLUMN about ENUM('vote','vote_reply','rate','reply',
				'follow','vm','comment','threadcomment','owner_to','moderator_to','owner_from','moderator','member', 'member_to', 'subscriber', 'subscriber_to', 'sg_subscriber', 'sg_subscriber_to')"
			);
		}
		else
		{
			$this->skip_message();
		}
	}
}

class vB_Upgrade_500b22 extends vB_Upgrade_Version
{
	/*
		Set activity stream values based on vB 4.2 values, if they exist.
	*/
	public function step_1()
	{
		$assertor = vB::getDbAssertor();

		/* These were saved in alpha 1 step */
		$as_expire = $assertor->getRow('adminutil', array('title' => 'as_expire'));
		$as_perpage = $assertor->getRow('adminutil', array('title' => 'as_perpage'));

		if ($as_expire AND $as_perpage)
		{
			/* vB5 time filtering is very limited
			So we translate the value as best we can
			1 - 4 days = today
			5 - 14 days = last week
			15 - 89 days = last month
			90+ days = all time */
			$filter = $as_expire['text'] < 5 ? 'time_today' : 'time_lastweek';
			$filter = $as_expire['text'] < 15 ? $filter : 'time_lastmonth';
			$filter = $as_expire['text'] < 90 ? $filter : 'time_all';

			/* Limit perpage between 10 and 60 */
			$perpage = $as_perpage['text'] < 10 ? 10 : $as_perpage['text'];
			$perpage = $as_perpage['text'] > 60 ? 60 : $perpage;

			$widget = $assertor->getRow('widget', array('guid' => 'vbulletin-widget_4-4eb423cfd69899.61732480'));
			$widgetInstance = $assertor->getRow('widgetinstance', array('widgetid' => $widget['widgetid']));

			if ($widgetInstance)
			{
				$data = unserialize($widgetInstance['adminconfig']);
				$widgetInstanceid = $widgetInstance['widgetinstanceid'];

				$data['filtertime_activitystream'] = $filter;
				$data['resultsperpage_activitystream'] = $perpage;

				$savedata = serialize($data);

				$assertor->update('widgetinstance',
					array('adminconfig' => $savedata),
					array('widgetinstanceid' => $widgetInstanceid)
				);

				$assertor->delete('adminutil', array('title' => 'as_expire'));
				$assertor->delete('adminutil', array('title' => 'as_perpage'));

				$this->show_message($this->phrase['version']['500b22']['activity_update']);
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

	//Add thread_post table
	public function step_2()
	{
		if (!$this->tableExists('thread_post'))
		{
			$this->run_query(
					sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'thread_post'),
					"CREATE TABLE " . TABLE_PREFIX . "thread_post (
						nodeid INT UNSIGNED NOT NULL,
						threadid INT UNSIGNED NOT NULL,
						postid INT UNSIGNED NOT NULL,
						PRIMARY KEY (nodeid),
						UNIQUE KEY thread_post (threadid, postid),
						KEY threadid (threadid),
						KEY postid (postid)
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

	//Now we can import threads, which come to vB5 as starters
	public function step_3($data = [])
	{
		if ($this->tableExists('post'))
		{
			vB_Types::instance()->reloadTypes();
			$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'thread_post'));
			$threadTypeId =  vB_Types::instance()->getContentTypeID('vBForum_Thread');
			$process = 500; /* In my testing, larger cycles get bogged down in temporary table copying -freddie */
			$startat = intval($data['startat'] ?? 0);

			//First see if we need to do something. Maybe we're O.K.
			if (!empty($data['maxvB4']))
			{
				$maxvB4 = $data['maxvB4'];
			}
			else
			{
				$maxvB4 = $this->db->query_first("SELECT MAX(threadid) AS maxid FROM " . TABLE_PREFIX . "post");
				$maxvB4 = $maxvB4['maxid'];

				//If we don't have any posts, we're done.
				if (intval($maxvB4) < 1)
				{
					$this->skip_message();
					return;
				}
			}

			$maxvB5 = $this->db->query_first("SELECT MAX(threadid) AS maxid FROM " . TABLE_PREFIX . "thread_post");

			if (!empty($maxvB5) AND !empty($maxvB5['maxid']))
			{
				$maxvB5 = $maxvB5['maxid'];
			}
			else
			{
				$maxvB5 = 0;
			}

			$maxvB5 = max($startat, $maxvB5);
			if (($maxvB4 <= $maxvB5) AND !$startat)
			{
				$this->skip_message();
				return;
			}
			else if ($maxvB4 <= $maxvB5)
			{
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}

			vB::getDbAssertor()->assertQuery('vBInstall:importToThread_post', array('maxvB5' => $maxvB5,
				'process' => $process, 'threadTypeId' => $threadTypeId));
			$this->show_message(sprintf($this->phrase['core']['processed_records_x_y'], $maxvB5 + 1, $maxvB5 + $process - 1));

			return array('startat' => ($maxvB5 + $process - 1), 'maxvB4' => $maxvB4);
		}
		else
		{
			$this->skip_message();
		}
	}
}

class vB_Upgrade_500b23 extends vB_Upgrade_Version
{
	public function step_1()
	{
		/* Clear old widget definitions */
		vB::getDbAssertor()->assertQuery('vBInstall:updateWidgetDefs');
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], 'widgetdefinition'));
	}

	/**
	 * Add the table for thread redirects
	 */
	public function step_2()
	{
		if (!$this->tableExists('redirect'))
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'redirect'),
				"
				CREATE TABLE " . TABLE_PREFIX . "redirect (
					nodeid INT UNSIGNED NOT NULL,
					tonodeid INT UNSIGNED NOT NULL,
					UNIQUE KEY (nodeid)
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

	/**
	 * Add content type for thread redirects
	 */
	public function step_3()
	{
		$this->add_contenttype('vbulletin', 'vBForum', 'Redirect');
	}
}

class vB_Upgrade_500b24 extends vB_Upgrade_Version
{
	/*
	 * Step 2 - Drop primary key on editlog.postid
	 */
	public function step_1()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'editlog', 1, 7),
			"ALTER TABLE " . TABLE_PREFIX . "editlog DROP PRIMARY KEY",
			self::MYSQL_ERROR_DROP_KEY_COLUMN_MISSING
		);
	}

	/*
	 * Step 2 - Add editlog.nodeid
	 */
	public function step_2()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'editlog', 2, 7),
			'editlog',
			'nodeid',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	/*
	 * Step 3 - Add index on editlog.postid
	 */
	public function step_3()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'editlog', 3, 7),
			'editlog',
			'postid',
			'postid'
		);
	}

	/*
	 * Step 4 - Update editlog.nodeid -- this will get non first posts.
	 */
	public function step_4()
	{
		$postTypeId = vB_Types::instance()->getContentTypeID('vBForum_Post');
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'editlog', 4, 7),
				"UPDATE " . TABLE_PREFIX . "editlog AS e
				 INNER JOIN " . TABLE_PREFIX . "node AS n ON (e.postid = n.oldid AND n.oldcontenttypeid = {$postTypeId} AND e.postid <> 0)
				 SET e.nodeid = n.nodeid
		");
	}

	/*
	 * Step 5 - Update editlog.nodeid -- this will get first posts, which are now saved as thread type in vB5.
	 * We can't use oldcontenttypeid to tie these directly back to the editlog data so we use the thread_post table to get the threadid.
	 */
	public function step_5()
	{
		$threadTypeId = vB_Types::instance()->getContentTypeID('vBForum_Thread');
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'editlog', 5, 7),
				"UPDATE " . TABLE_PREFIX . "editlog AS e
				 INNER JOIN " . TABLE_PREFIX . "thread_post AS tp ON (e.postid = tp.postid AND e.postid <> 0)
				 INNER JOIN " . TABLE_PREFIX . "node AS n ON (tp.threadid = n.oldid AND n.oldcontenttypeid = {$threadTypeId})
				 SET e.nodeid = n.nodeid
		");
	}

	/*
	 * Step 6 - We may have some orphan logs that reference a non-existant post/thread. These logs have nodeids of 0.
	 * Remove them.
	 */
	public function step_6()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'editlog', 6, 7),
				"DELETE FROM " . TABLE_PREFIX . "editlog
				 WHERE nodeid = 0
		");
	}

	/*
	 * Step 7 - Add PRIMARY KEY on editlog.nodeid
	 */
	public function step_7()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'editlog', 7, 7),
			'editlog',
			'PRIMARY',
			array('nodeid'),
			'primary'
		);
	}

	/* This index was missing from the schema file, so it
	wont exist in vB5 installations that were not upgrades.
	This will add it if its not there, otherwise it will do nothing. */
	public function step_8()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'node', 1, 1),
			'node',
			'node_unpubdate',
			array('unpublishdate')
		);
	}

	/*
	 * Step 9 - Update blog channel permissions,
	 * in order to comment to blog entries,  unregistered users should have
	 * forumpermission canreply   and
	 * createpermission vbforum_text
	 */
	public function step_9()
	{
		// create a user session..
		vB_Upgrade::createAdminSession();
		// need to grab the node id for blog channel and the usergroup id
		$blogNodeId = vB_Library::instance('Blog')->getBlogChannel();
		$unregisteredugid = vB_Api_UserGroup::UNREGISTERED_SYSGROUPID;
		// get the permissions..
		//$existingPermissions  = vB::getDbAssertor()->getRow('vBForum:permission', array('groupid' => $unregisteredugid, 'nodeid' => $blogNodeId));
		$existingPermissions = vB_ChannelPermission::instance()->fetchPermissions($blogNodeId, $unregisteredugid);
		$existingPermissions = $existingPermissions[$unregisteredugid];
		// get the bitfields..
		$forumpermissions = vB::getDatastore()->getValue('bf_ugp_forumpermissions');
		$createpermissions = vB::getDatastore()->getValue('bf_ugp_createpermissions');
		// set the permissions..
		$existingPermissions['forumpermissions'] |= $forumpermissions['canreply'];
		$existingPermissions['createpermissions'] |= $createpermissions['vbforum_text'];
		// save the permissions..
		vB_ChannelPermission::instance()->setPermissions($blogNodeId, $unregisteredugid, $existingPermissions, true);
		$this->show_message(sprintf($this->phrase['version']['500b24']['blog_channel_permission_update']));
	}

	/**
	 * Update info for imported thread redirects
	 */
	public function step_10()
	{
		if ($this->tableExists('thread') AND $this->tableExists('threadredirect'))
		{
			$assertor = vB::getDbAssertor();

			vB_Types::instance()->reloadTypes();
			$forumTypeId =  vB_Types::instance()->getContentTypeID('vBForum_Forum');
			$threadTypeId =  vB_Types::instance()->getContentTypeID('vBForum_Thread');
			$redirectTypeId = vB_Types::instance()->getContentTypeId('vBForum_Redirect');

			$assertor->assertQuery('vBInstall:importRedirectThreads',
				array(
					'forumTypeId' => $forumTypeId,
					'redirectTypeId' => $redirectTypeId
				)
			);
			$imported = $assertor->affected_rows();

			if ($imported)
			{
				$this->show_message(sprintf($this->phrase['version']['500b24']['thread_redirect_import']));

				$nodes = $assertor->getRows('vBInstall:fetchRedirectThreads');

				$urlIdentNodes = array();
				$updateNodeids = array();
				foreach($nodes AS $node)
				{
					$node['urlident'] = vB_String::getUrlIdent($node['title']);
					$urlIdentNodes[] = $node;
					$updateNodeids[] = $node['nodeid'];
				}

				// Insert records into redirect table
				$assertor->assertQuery('vBInstall:insertRedirectRecords', array('nodes' => $updateNodeids, 'contenttypeid' => $threadTypeId));

				//Set the urlident values
				$assertor->assertQuery('vBInstall:updateUrlIdent', array('nodes' => $urlIdentNodes));

				//Now fix the starter
				$assertor->assertQuery('vBInstall:updateNodeStarter', array('contenttypeid' => 9980));

				//Now the closure record for depth=0
				$assertor->assertQuery('vBInstall:insertNodeClosure', array('contenttypeid' => 9980));

				//Add the closure records to root
				$assertor->assertQuery('vBInstall:insertNodeClosureRoot', array('contenttypeid' => 9980));

				// Update route
				vB::getDbAssertor()->assertQuery('vBInstall:updateRedirectRoutes', array('contenttypeid' => 9980));
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

class vB_Upgrade_500b25 extends vB_Upgrade_Version
{
	/**
	 * step 1 - Add maxchannels -- channel limit permission.
	 */
	public function step_1()
	{
		vB_Upgrade::createAdminSession();
		if (!$this->field_exists('permission', 'maxchannels'))
		{
			$this->show_message($this->phrase['version']['500b24']['adding_maxchannel_field']);
			vB::getDbAssertor()->assertQuery('vBInstall:addMaxChannelsField');

			$usergroupinfo = vB::getDbAssertor()->getRows('vBForum:usergroup', [
				vB_dB_Query::CONDITIONS_KEY => [['field' => 'maximumsocialgroups', 'value' => 0, vB_dB_Query::OPERATOR_KEY => vB_dB_Query::OPERATOR_NE]]
			]);

			if (is_array($usergroupinfo)  AND !isset($usergroupinfo['errors']) AND !empty($usergroupinfo))
			{
				$updates = [];
				foreach ($usergroupinfo AS $ugp)
				{
					$updates[$ugp['usergroupid']] = $ugp['maximumsocialgroups'];
				}

				// do the actual update
				vB::getDbAssertor()->assertQuery('vBInstall:updateUGPMaxSGs', [
					'groups' => $updates,
					'sgnodeid' => vB_Api::instanceInternal('content_channel')->fetchChannelIdByGUID(vB_Channel::DEFAULT_SOCIALGROUP_PARENT),
				]);
			}
			else
			{
				$this->show_message($this->phrase['core']['process_done']);
				return;
			}
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	 * step 2 - Mapping social group member systemgroup permissions
	 */
	public function step_2()
	{
		$assertor = vB::getDbAssertor();
		$sgmember = $assertor->assertQuery('vBForum:usergroup', array('systemgroupid' => 14));
		if ($sgmember AND $sgmember->valid())
		{
			$this->show_message($this->phrase['version']['500b24']['removing_sg_membergroup']);
			$moveperms = true;

			$sgmemberinfo = $sgmember->current();
			$sgparent = vB_Api::instanceInternal('content_channel')->fetchChannelIdByGUID(vB_Channel::DEFAULT_SOCIALGROUP_PARENT);
			$perm = $assertor->assertQuery('vBForum:permission',
				array('groupid' => $sgmemberinfo['usergroupid'], 'nodeid' => $sgparent)
			);
			$memberugp = $assertor->getRow('vBForum:usergroup', array('systemgroupid' => vB_Api_UserGroup::CHANNEL_MEMBER_SYSGROUPID));

			// make sure we don't have memberugp records on sg parent
			$assertor->assertQuery('vBForum:permission', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_DELETE,
				vB_dB_Query::CONDITIONS_KEY => array(
					array('field' => 'nodeid', 'value' => $sgparent, 'operator' => vB_dB_Query::OPERATOR_EQ),
					array('field' => 'groupid', 'value' => $memberugp['usergroupid'], 'operator' => vB_dB_Query::OPERATOR_EQ)
				)
			));

			if ($perm AND $perm->valid())
			{
				// if we have permission sets at sg channel then just change the group id
				$perminfo = $perm->current();
				$assertor->assertQuery('vBForum:permission', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
					vB_dB_Query::CONDITIONS_KEY => array(array('field' => 'permissionid', 'value' => $perminfo['permissionid'])),
					'groupid' => $memberugp['usergroupid']
				));
			}
			else
			{
				//  but if we don't we take'em from root node
				$perm = $assertor->assertQuery('vBForum:permission',
					array('groupid' => $sgmemberinfo['usergroupid'], 'nodeid' => vB_Api::instanceInternal('content_channel')->fetchChannelIdByGUID(vB_Channel::MAIN_CHANNEL))
				);

				if ($perm AND $perm->valid())
				{
					$permission = $perm->current();
					$assertor->assertQuery('vBForum:permission', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
						vB_dB_Query::CONDITIONS_KEY => array(array('field' => 'permissionid', 'value' => $permission['permissionid'])),
						'nodeid' => vB_Api::instanceInternal('content_channel')->fetchChannelIdByGUID(vB_Channel::DEFAULT_SOCIALGROUP_PARENT), 'groupid' => $memberugp['usergroupid']
					));
				}
			}

			// remove the rest of permissions, update channel group info, and change GIT records to make sense
			$assertor->assertQuery('vBForum:permission', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_DELETE, 'groupid' => $sgmemberinfo['usergroupid']));
			$assertor->assertQuery('vBForum:usergroup', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
				vB_dB_Query::CONDITIONS_KEY => array(array('field' => 'systemgroupid', 'value' => vB_Api_UserGroup::CHANNEL_MEMBER_SYSGROUPID)),
				'title' => $this->phrase['install']['channelmember_title']
			));

			$assertor->assertQuery('vBForum:groupintopic', array(
				vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
				vB_dB_Query::CONDITIONS_KEY => array(
					array('field' => 'groupid', 'value' => $sgmemberinfo['usergroupid'])
				),
				'groupid' => $memberugp['usergroupid']
			));
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	 * step 3 - Mapping social group moderator systemgroup permissions
	 */
	public function step_3()
	{
		vB_Upgrade::createAdminSession();
		$assertor = vB::getDbAssertor();
		$sgmod = $assertor->assertQuery('vBForum:usergroup', array('systemgroupid' => 13));
		if ($sgmod AND $sgmod->valid())
		{
			$this->show_message($this->phrase['version']['500b24']['removing_sg_modgroup']);
			$moveperms = true;

			$sgmodinfo = $sgmod->current();
			$sgparent = vB_Api::instanceInternal('content_channel')->fetchChannelIdByGUID(vB_Channel::DEFAULT_SOCIALGROUP_PARENT);

			$perm = $assertor->assertQuery('vBForum:permission',
				array('groupid' => $sgmodinfo['usergroupid'], 'nodeid' => $sgparent)
			);
			$modupg = $assertor->getRow('vBForum:usergroup', array('systemgroupid' => vB_Api_UserGroup::CHANNEL_MODERATOR_SYSGROUPID));

			// make sure we don't have modugp records on sg parent
			$assertor->assertQuery('vBForum:permission', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_DELETE,
				vB_dB_Query::CONDITIONS_KEY => array(
					array('field' => 'nodeid', 'value' => $sgparent, 'operator' => vB_dB_Query::OPERATOR_EQ),
					array('field' => 'groupid', 'value' => $modupg['usergroupid'], 'operator' => vB_dB_Query::OPERATOR_EQ)
				)
			));

			if ($perm AND $perm->valid())
			{
				// if we have permission sets at sg channel then just change the group id
				$perminfo = $perm->current();
				$assertor->assertQuery('vBForum:permission', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
					vB_dB_Query::CONDITIONS_KEY => array(array('field' => 'permissionid', 'value' => $perminfo['permissionid'])),
					'groupid' => $modupg['usergroupid']
				));
			}
			else
			{
				//  but if we don't we take'em from root node
				$perm = $assertor->assertQuery('vBForum:permission',
					array('groupid' => $sgmodinfo['usergroupid'], 'nodeid' => vB_Api::instanceInternal('content_channel')->fetchChannelIdByGUID(vB_Channel::MAIN_CHANNEL))
				);

				if ($perm AND $perm->valid())
				{
					$permission = $perm->current();
					$assertor->assertQuery('vBForum:permission', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
						vB_dB_Query::CONDITIONS_KEY => array(array('field' => 'permissionid', 'value' => $permission['permissionid'])),
						'nodeid' => vB_Api::instanceInternal('content_channel')->fetchChannelIdByGUID(vB_Channel::DEFAULT_SOCIALGROUP_PARENT), 'groupid' => $modupg['usergroupid']
					));
				}
			}

			// remove the rest of permissions, update channel group info, and change GIT records to make sense
			$assertor->assertQuery('vBForum:permission', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_DELETE, 'groupid' => $sgmodinfo['usergroupid']));
			$assertor->assertQuery('vBForum:usergroup', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
				vB_dB_Query::CONDITIONS_KEY => array(array('field' => 'systemgroupid', 'value' => vB_Api_UserGroup::CHANNEL_MODERATOR_SYSGROUPID)),
				'title' => $this->phrase['install']['channelmod_title']
			));
			$assertor->assertQuery('vBForum:groupintopic', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
				vB_dB_Query::CONDITIONS_KEY => array(array('field' => 'groupid', 'value' => $sgmodinfo['usergroupid'])),
				'groupid' => $modupg['usergroupid']
			));
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	 * step 4 - Mapping social group owner systemgroup permissions
	 */
	public function step_4()
	{
		$assertor = vB::getDbAssertor();
		$sgowner = $assertor->assertQuery('vBForum:usergroup', array('systemgroupid' => 12));
		if ($sgowner AND $sgowner->valid())
		{
			$this->show_message($this->phrase['version']['500b24']['removing_sg_ownergroup']);
			$moveperms = true;

			$sgownerinfo = $sgowner->current();
			$sgparent = vB_Api::instanceInternal('content_channel')->fetchChannelIdByGUID(vB_Channel::DEFAULT_SOCIALGROUP_PARENT);
			$perm = $assertor->assertQuery('vBForum:permission',
				array('groupid' => $sgownerinfo['usergroupid'], 'nodeid' => $sgparent)
			);
			$ownerugp = $assertor->getRow('vBForum:usergroup', array('systemgroupid' => vB_Api_UserGroup::CHANNEL_OWNER_SYSGROUPID));

			// make sure we don't have modugp records on sg parent
			$assertor->assertQuery('vBForum:permission', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_DELETE,
				vB_dB_Query::CONDITIONS_KEY => array(
					array('field' => 'nodeid', 'value' => $sgparent, 'operator' => vB_dB_Query::OPERATOR_EQ),
					array('field' => 'groupid', 'value' => $ownerugp['usergroupid'], 'operator' => vB_dB_Query::OPERATOR_EQ)
				)
			));

			if ($perm AND $perm->valid())
			{
				// if we have permission sets at sg channel then just change the group id
				$perminfo = $perm->current();
				$assertor->assertQuery('vBForum:permission', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
					vB_dB_Query::CONDITIONS_KEY => array(array('field' => 'permissionid', 'value' => $perminfo['permissionid'])),
					'groupid' => $ownerugp['usergroupid']
				));
			}
			else
			{
				//  but if we don't we take'em from root node
				$perm = $assertor->assertQuery('vBForum:permission',
					array('groupid' => $sgownerinfo['usergroupid'], 'nodeid' => vB_Api::instanceInternal('content_channel')->fetchChannelIdByGUID(vB_Channel::MAIN_CHANNEL))
				);

				if ($perm AND $perm->valid())
				{
					$permission = $perm->current();
					$assertor->assertQuery('vBForum:permission', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
						vB_dB_Query::CONDITIONS_KEY => array(array('field' => 'permissionid', 'value' => $permission['permissionid'])),
						'nodeid' => vB_Api::instanceInternal('content_channel')->fetchChannelIdByGUID(vB_Channel::DEFAULT_SOCIALGROUP_PARENT), 'groupid' => $ownerugp['usergroupid']
					));
				}
			}

			// remove the rest of permissions, update channel group info, and change GIT records to make sense
			$assertor->assertQuery('vBForum:permission', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_DELETE, 'groupid' => $sgownerinfo['usergroupid']));
			$assertor->assertQuery('vBForum:usergroup', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
				vB_dB_Query::CONDITIONS_KEY => array(array('field' => 'systemgroupid', 'value' => vB_Api_UserGroup::CHANNEL_OWNER_SYSGROUPID)),
				'title' => $this->phrase['install']['channelowner_title']
			));
			$assertor->assertQuery('vBForum:groupintopic', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
				vB_dB_Query::CONDITIONS_KEY => array(array('field' => 'groupid', 'value' => $sgownerinfo['usergroupid'])),
				'groupid' => $ownerugp['usergroupid']
			));
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	 * step 5 - Remove social group system usergroups
	 */
	public function step_5()
	{
		$this->show_message($this->phrase['version']['500b24']['removing_sg_ugps']);
		vB::getDbAssertor()->assertQuery('vBInstall:removeSGSystemgroups');
	}
}

class vB_Upgrade_500b26 extends vB_Upgrade_Version
{
	public function step_1()
	{
		//this duplicates a previous step and a future one but step_2
		//requires that the field exist and one of the tests starts from a version
		//that manages to miss the places where the field was added (it was actually added
		//in 504 but some of the code retroactively requires it).
		//
		//It's exceedingly unlikely we'll actually turn up such a database in the wild.
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'page', 1, 1),
			'page',
			'product',
			'VARCHAR',
			array(
				'length' => 25,
				'default' => 'vbulletin',
			)
		);
		// step_2() below will throw errors while trying to load some channel data without these new columns, even though
		// these columns are added in vb6
		$this->add_field2(
			'channel',
			'topicexpiretype',
			'ENUM',
			[
				'attributes' => "('none', 'soft', 'hard')",
				'null'       => false,
				'default'    => 'none',
			]
		);
		$this->add_field2(
			'channel',
			'topicexpireseconds',
			'INT',
			[
				'attributes' => 'UNSIGNED',
				'null'       => false,
				'default'    => 0,
			]
		);
	}

	// Fixing category field and removing conversation routes for root channels
	public function step_2()
	{
		vB_Upgrade::createSession();

		//some upgrades may not have gotten this column from a step added to 500a1 after the release of that alpha version
		if (!$this->field_exists('channel', 'product'))
		{
			$this->add_field(
				sprintf($this->phrase['core']['altering_x_table'], 'channel', 1, 1),
				'channel',
				'product',
				'VARCHAR',
				array(
					'length' => 25,
					'default' => 'vbulletin',
				)
			);
			$this->execute();
		}

		$this->show_message(sprintf($this->phrase['version']['500b26']['fixing_category_channels']));

		$channels = vB::getDbAssertor()->assertQuery('vBInstall:getRootChannels', array('rootGuids' => array(
			vB_Channel::MAIN_CHANNEL,
			vB_Channel::DEFAULT_FORUM_PARENT,
			vB_Channel::DEFAULT_BLOG_PARENT,
			vB_Channel::DEFAULT_SOCIALGROUP_PARENT,
			vB_Channel::DEFAULT_CHANNEL_PARENT,
		)));

		$library = vB_Library::instance('content_channel');
		foreach ($channels AS $channel)
		{
			if ($channel['category'] == 0 OR !empty($channel['routeid']))
			{
				// Since we are fixing some inconsistencies, we need to force this method to rebuild routes
				$library->switchForumCategory(true, $channel['nodeid'], true);
			}
		}
	}

	/**
	 * Add the nodehash table
	 */
	public function step_3()
	{
		if (!$this->tableExists('nodehash'))
		{
			$this->run_query(
					sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'nodehash'),
					"
					CREATE TABLE " . TABLE_PREFIX . "nodehash (
						userid INT UNSIGNED NOT NULL,
						nodeid INT UNSIGNED NOT NULL,
						dupehash char(32) NOT NULL,
						dateline INT UNSIGNED NOT NULL,
						KEY (userid, dupehash),
						KEY (dateline)
					)
					ENGINE = " . $this->hightrafficengine,
					self::MYSQL_ERROR_TABLE_EXISTS
			);
		}
		else
		{
			$this->skip_message();
		}
	}
}

class vB_Upgrade_500b27 extends vB_Upgrade_Version
{
	/**
	 * Handle customized values for stylevars that have been renamed
	 */
	public function step_1()
	{
		// Renamed stylevars (no datatype change)
		$mapRenamed = array(
			'body_bg_color' => array('body_background', 'header_background'),
			'display_tab_background' => 'module_tab_background',
			'display_tab_background_active' => 'module_tab_background_active',
			'display_tab_border' => 'module_tab_border',
			'display_tab_border_active' => 'module_tab_border_active',
			'display_tab_text_color' => 'module_tab_text_color',
			'display_tab_text_color_active' => 'module_tab_text_color_active',
			'footer_bar_bg' => 'footer_background',
			'inline_edit_search_bar_background_color_active' => 'inline_edit_search_bar_background_active',
			'inline_edit_search_bar_background_color_hover' => 'inline_edit_search_bar_background_hover',
			'left_nav_background' => 'side_nav_background',
			'left_nav_button_background_active_color' => 'side_nav_button_background_active',
			'left_nav_number_messages_color' => 'side_nav_number_messages_color',
			'list_item_bg' => 'list_item_background',
			'module_content_bg' => 'module_content_background',
			'tabbar_bg' => array('header_tabbar_background', 'header_tab_background'),
			'tabbar_list_item_color' => 'header_tab_text_color',
			'wrapper_bg_color' => 'wrapper_background',
		);

		// Renamed and datatype change, color to border
		$mapColorToBorder = array(
			'activity_stream_avatar_border_color' => 'activity_stream_avatar_border',
			'announcement_border_color' => 'announcement_border',
			'button_primary_border_color' => 'button_primary_border',
			'button_primary_border_color_hover' => 'button_primary_border_hover',
			'button_secondary_border_color' => 'button_secondary_border',
			'button_secondary_border_color_hover' => 'button_secondary_border_hover',
			'button_special_border_color' => 'button_special_border',
			'button_special_border_color_hover' => 'button_special_border_hover',
			'display_tab_border_color' => 'module_tab_border',
			'display_tab_border_color_active' => 'module_tab_border_active',
			'filter_bar_border_color' => 'toolbar_border',
			'filter_bar_button_border_color' => 'filter_bar_button_border',
			'filter_bar_form_field_border_color' => 'filter_bar_form_field_border',
			'filter_dropdown_border_color' => 'filter_dropdown_border',
			'form_dropdown_border_color' => 'form_dropdown_border',
			'form_field_border_color' => 'form_field_border',
			'inline_edit_button_border_color' => 'inline_edit_button_border',
			'inline_edit_field_border_color' => 'inline_edit_field_border',
			'left_nav_avatar_border_color' => 'side_nav_avatar_border',
			'left_nav_divider_border' => 'side_nav_item_border_top',
			'left_nav_divider_border_bottom' => 'side_nav_item_border_bottom',
			'main_nav_button_border_color' => 'main_nav_button_border',
			'module_content_border_color' => 'module_content_border',
			'module_header_border_color' => 'module_header_border',
			'notice_border_color' => 'notice_border',
			'photo_border_color' => 'photo_border',
			'photo_border_hover_color' => 'photo_border_hover',
			'poll_result_border_color' => 'poll_result_border',
			'popup_border_color' => 'popup_border',
			'post_border_color' => 'post_border',
			'post_deleted_border_color' => 'post_deleted_border',
			'profile_section_border_color' => 'profile_section_border',
			'profilesidebar_button_border_color' => 'profilesidebar_button_border',
			'secondary_content_border_color' => 'secondary_content_border',
			'thread_view_avatar_border_color' => 'thread_view_avatar_border',
		);

		$mapper = new vB_Stylevar_Mapper();

		// Add mappings
		foreach ($mapRenamed AS $old => $newArr)
		{
			$newArr = (array) $newArr;
			foreach ($newArr AS $new)
			{
				$mapper->addMapping($old, $new);
			}
		}
		foreach ($mapColorToBorder AS $old => $newArr)
		{
			$newArr = (array) $newArr;
			foreach ($newArr AS $new)
			{
				$mapper->addMapping($old . '.color', $new . '.color');
				$mapper->addPreset($new . '.units', 'px');
				$mapper->addPreset($new . '.style', 'solid');
				$mapper->addPreset($new . '.width', '1');
			}
		}

		// Do the processing
		if ($mapper->load() AND $mapper->process())
		{
			$this->show_message($this->phrase['version']['408']['mapping_customized_stylevars']);
			//$mapper->displayResults(); // Debug only
			$mapper->processResults();
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	 * Add url, url_title, meta fields to video table
	 */
	public function step_2()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'video', 1, 3),
			'video',
			'url',
			'VARCHAR',
			array('length' => 255)
		);
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'video', 2, 3),
			'video',
			'url_title',
			'VARCHAR',
			array('length' => 255)
		);
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'video', 3, 3),
			'video',
			'meta',
			'MEDIUMTEXT',
			self::FIELD_DEFAULTS
		);
	}

	public function step_3()
	{
		if (!$this->field_exists('permission', 'channeliconmaxsize'))
		{
			$this->add_field(
				sprintf($this->phrase['core']['altering_x_table'], 'permission', 1, 1),
				'permission',
				'channeliconmaxsize',
				'INT',
				array('attributes' => 'UNSIGNED', 'null' => false, 'default' => 65535)
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	* Add oldfolderid field to messagefolder table
	*/
	public function step_4()
	{
		if (!$this->field_exists('messagefolder', 'oldfolderid'))
		{

			$this->add_field(
				sprintf($this->phrase['core']['altering_x_table'], 'messagefolder ', 1, 2),
				'messagefolder',
				'oldfolderid',
				'tinyint',
				array('null' => true, 'default' => NULL)
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	* Add UNIQUE index to the userid, oldfolderid pair on the messagefolder table
	* For ensuring no duplicate imports from vb4 custom folders
	*/
	public function step_5()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'messagefolder', 2, 2),
			'messagefolder',
			'userid_oldfolderid',
			['userid', 'oldfolderid'],
			'unique'
		);
	}

	/**
	 * Importing custom folders
	 */
	public function step_6($data = [])
	{
		$assertor = vB::getDbAssertor();
		$batchsize = 1000;
		$startat = intval($data['startat'] ?? 0);

		// Check if any users have custom folders
		if (!empty($data['totalUsers']))
		{
			$totalUsers = $data['totalUsers'];
		}
		else
		{
			// Get the number of users that has custom pm folders
			$totalUsers = $assertor->getRow('vBInstall:getTotalUsersWithFolders');
			$totalUsers = intval($totalUsers['totalusers']);

			if (intval($totalUsers) < 1)
			{
				$this->skip_message();
				return;
			}
			else
			{
				$this->show_message($this->phrase['version']['500b27']['importing_custom_folders']);
			}
		}

		if ($startat >= $totalUsers)
		{
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			return;
		}

		// Get the users for import
		$this->show_message(sprintf($this->phrase['core']['processing_records_x'], $batchsize));
		$users = $assertor->getRows('vBInstall:getUsersWithFolders', ['startat' => $startat, 'batchsize' => $batchsize]);
		$inserValues = [];
		foreach ($users as $user)
		{
			$pmFolders = unserialize($user['pmfolders']);

			//in some cases the serialized data appears to be corrupt.  There isn't much we can do about it
			//at this point
			if ($pmFolders)
			{
				foreach ($pmFolders as $folderid => $title)
				{
					$inserValues[] = [
						'userid' => $user['userid'],
						'title' => $title,
						'oldfolderid' => $folderid,
					];
				}
			}
		}
		$assertor->assertQuery('insertignoreValues', ['table' => 'messagefolder', 'values' => $inserValues]);

		return ['startat' => ($startat + $batchsize), 'totalUsers' => $totalUsers];
	}

	/**
	 * Dropping unique key on regex (recreating in step 14)
	 */
	public function step_7()
	{
		$this->drop_index(sprintf($this->phrase['core']['altering_x_table'], 'routenew', 1, 1), 'routenew', 'regex');
	}

	/*
	 *	This step was supposed to update the non-custom (custom: prefix = regex) conversation routes' REGEX with the
	 *	less restrictive one defined @ vB5_Route_Conversation::REGEXP. However, the prefix wasn't regex escaped with
	 *	preg_quote, so it broke the routing for any channels with regex control chars in the title.
	 */
	public function step_8()
	{
		// moved to class_upgrade_504a3.php, step_1
		$this->skip_message();
	}

	// No longer needed.
	public function step_9()
	{
		$this->skip_message();
	}

	/**
	 * Fix mimetype defaults
	 */
	public function step_10()
	{
		$assertor = vB::getDbAssertor();
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'attachmenttype'));
		$assertor->update('vBInstall:attachmenttype',
			array('mimetype' => serialize(array('Content-type: text/plain'))),
			array('extension' => 'txt')
		);
		$assertor->update('vBInstall:attachmenttype',
			array('mimetype' => serialize(array('Content-type: image/bmp'))),
			array('extension' => 'bmp')
		);
		$assertor->update('vBInstall:attachmenttype',
			array('mimetype' => serialize(array('Content-type: image/vnd.adobe.photoshop'))),
			array('extension' => 'psd')
		);
	}

	/**
	 * Fix pm responses starter
	 */
	public function step_11($data = array())
	{
		if ($this->tableExists('pm') AND $this->tableExists('pmtext'))
		{
			$assertor = vB::getDbAssertor();
			$batchsize = 2000;
			$this->show_message($this->phrase['version']['500b27']['fixing_pm_records']);

			if (isset($data['startat']))
			{
				$startat = $data['startat'];
			}

			if (!empty($data['maxvB4']))
			{
				$maxPMid = $data['maxvB4'];
			}
			else
			{
				$maxPMid = $assertor->getRow('vBInstall:getMaxPMResponseToFix', array('contenttypeid' => 9981));
				$maxPMid = intval($maxPMid['maxid']);

				//If there are no responses to fix...
				if (intval($maxPMid) < 1)
				{
					$this->skip_message();
					return;
				}
			}

			if (!isset($startat))
			{
				$maxvB5 = $assertor->getRow('vBInstall:getMaxFixedPMResponse', array('contenttypeid' => 9981));

				if (!empty($maxvB5) AND !empty($maxvB5['maxid']))
				{
					$startat = $maxvB5['maxid'];
				}
				else
				{
					$startat = 1;
				}
			}

			if ($startat >= $maxPMid)
			{
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}

			// fix starter from pm replies
			$assertor->assertQuery('vBInstall:setResponseStarter', array('startat' => $startat, 'batchsize' => $batchsize, 'contenttypeid' => 9981));
			$assertor->assertQuery('vBInstall:setShowValues', array('startat' => $startat, 'batchsize' => $batchsize, 'contenttypeid' => 9981, 'value' => 1));
			return array('startat' => ($startat + $batchsize), 'maxvB4' => $maxPMid);
		}
		else
		{
			$this->skip_message();
		}
	}

	/** fixing ipv6 fields in strike table **/
	public function step_12()
	{
		$this->show_message(sprintf($this->phrase['core']['altering_x_table'], 'strikes', 1, 1));

		vB::getDbAssertor()->assertQuery('vBInstall:fixStrikeIPFields');
	}

	/**
	 * Modifying regex size in routenew
	 */
	public function step_13()
	{
		$this->show_message(sprintf($this->phrase['core']['altering_x_table'], 'routenew', 1, 1));

		vB::getDbAssertor()->assertQuery('vBInstall:alterRouteRegexSize', array('regexSize' => vB5_Route::REGEX_MAXSIZE));
	}

	/**
	 * Recreating regex index
	 */
	public function step_14()
	{
		$this->add_index(sprintf($this->phrase['core']['altering_x_table'], 'routenew', 1, 1), 'routenew', 'regex', 'regex');
	}
}

class vB_Upgrade_500b28 extends vB_Upgrade_Version
{
	/*
	 * Step 1 - create postedithistory if it doesn't exist because this forum started on vB 5
	 */
	public function step_1()
	{
			$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'postedithistory'),
			"CREATE TABLE " . TABLE_PREFIX . "postedithistory (
				postedithistoryid INT UNSIGNED NOT NULL AUTO_INCREMENT,
				postid INT UNSIGNED NOT NULL DEFAULT '0',
				nodeid INT UNSIGNED NOT NULL DEFAULT '0',
				userid INT UNSIGNED NOT NULL DEFAULT '0',
				username VARCHAR(100) NOT NULL DEFAULT '',
				title VARCHAR(250) NOT NULL DEFAULT '',
				iconid INT UNSIGNED NOT NULL DEFAULT '0',
				dateline INT UNSIGNED NOT NULL DEFAULT '0',
				reason VARCHAR(200) NOT NULL DEFAULT '',
				original SMALLINT NOT NULL DEFAULT '0',
				pagetext MEDIUMTEXT,
				PRIMARY KEY  (postedithistoryid),
				KEY nodeid (nodeid,userid)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	/*
	 * Step 2 - Add postedithistory.nodeid
	 */
	public function step_2()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'postedithistory', 1, 5),
			'postedithistory',
			'nodeid',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	/*
	 * Step 3 - Add index on postedithistory.nodeid
	 */
	public function step_3()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'postedithistory', 2, 5),
			'postedithistory',
			'nodeid',
			array('nodeid', 'userid')
		);
	}

	/*
	 * Step 4 - Update postedithistory.nodeid -- this will get non first posts.
	 */
	public function step_4()
	{
		$this->show_message(sprintf($this->phrase['core']['altering_x_table'], 'postedithistory', 3, 5));
		$postTypeId = vB_Types::instance()->getContentTypeID('vBForum_Post');
		vB::getDbAssertor()->assertQuery('vBInstall:500b28_updatePostHistory1', array('posttypeid' => $postTypeId));
	}

	/*
	 * Step 5 - Update postedithistory.nodeid -- this will get first posts, which are now saved as thread type in vB5.
	 * We can't use oldcontenttypeid to tie these directly back to the postedithistory data so we use the thread_post table to get the threadid.
	 */
	public function step_5()
	{
		$this->show_message(sprintf($this->phrase['core']['altering_x_table'], 'postedithistory', 4, 5));
		$threadTypeId = vB_Types::instance()->getContentTypeID('vBForum_Thread');
		vB::getDbAssertor()->assertQuery('vBInstall:500b28_updatePostHistory2', array('threadtypeid' => $threadTypeId));
	}

	/*
	 * Step 6 - We may have some orphan logs that reference a non-existant post/thread. These logs have nodeids of 0.
	 * Remove them.
	 */
	public function step_6()
	{
		$this->show_message(sprintf($this->phrase['core']['altering_x_table'], 'postedithistory', 5, 5));
		vB::getDbAssertor()->assertQuery('vBInstall:500b28_updatePostHistory3');
	}

	/*
	 * Step 7 - Remove not needed index if it exists from messagefolder table
	 */
	public function step_7()
	{
		$this->drop_index(
			sprintf($this->phrase['core']['altering_x_table'], 'messagefolder', 1, 1),
			'messagefolder',
			'userid_title_titlephrase'
		);
	}

	/*
	 * Step 8 - fix starter on imported album photos
	 */
	public function step_8($data = array())
	{
		$assertor = vB::getDbAssertor();
		$batchsize = 2000;
		$this->show_message($this->phrase['version']['500b28']['fixing_aphoto_records']);

		if (isset($data['startat']))
		{
			$startat = $data['startat'];
		}

		if (!empty($data['maxvB4']))
		{
			$maxPMid = $data['maxvB4'];
		}
		else
		{
			$maxid = $assertor->getRow('vBInstall:getMaxNodeRecordToFix', array('contenttypeid' => 9986));
			$maxid = intval($maxid['maxid']);

			//If there are no records to fix...
			if (intval($maxid) < 1)
			{
				$this->skip_message();
				return;
			}
		}

		if (!isset($startat))
		{
			$maxvB5 = $assertor->getRow('vBInstall:getMaxNodeRecordFixed', array('contenttypeid' => 9986));

			if (!empty($maxvB5) AND !empty($maxvB5['maxid']))
			{
				$startat = $maxvB5['maxid'];
			}
			else
			{
				$startat = 1;
			}
		}

		if ($startat >= $maxid)
		{
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			return;
		}

		// fix starter from album photos
		$assertor->assertQuery('vBInstall:setResponseStarter', array('startat' => $startat, 'batchsize' => $batchsize, 'contenttypeid' => 9986));
		return array('startat' => ($startat + $batchsize), 'maxvB4' => $maxid);
	}

	/*
	 * Step 9 Change nodeoption 'moderate_comments' to 'moderate_topics' in groups
	 *
	 */
	public function step_9()
	{
		$sgChannel = vB_Library::instance('node')->getSGChannel();
		$options = vB_Api::instanceInternal('node')->getOptions();
		$moderate_comments = $options['moderate_comments'];
		$moderate_topics = $options['moderate_topics'];

		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'node', 1, 1),
				"UPDATE " . TABLE_PREFIX . "node AS n
				 INNER JOIN " . TABLE_PREFIX . "closure cl on n.nodeid = cl.child
				 INNER JOIN " . TABLE_PREFIX . "channel ch on n.nodeid = ch.nodeid
				 SET n.nodeoptions = n.nodeoptions - $moderate_comments + $moderate_topics
				 where cl.parent = $sgChannel AND ch.category = 0 AND n.nodeoptions & $moderate_comments
		");
	}

	/**
	 * Handle customized values for stylevars that have been renamed
	 */
	public function step_10()
	{
		$mapper = new vB_Stylevar_Mapper();

		// Add mappings
		$mapper->addMapping('filter_bar_button_border', 'toolbar_button_border');
		$mapper->addMapping('filter_bar_form_field_background', 'toolbar_form_field_background');
		$mapper->addMapping('filter_bar_form_field_border', 'toolbar_form_field_border');
		$mapper->addMapping('filter_bar_form_field_placeholder_text_color', 'toolbar_form_field_placeholder_text_color');
		$mapper->addMapping('filter_bar_text_color', 'toolbar_text_color');
		$mapper->addMapping('filter_dropdown_background_gradient_end', 'toolbar_dropdown_background_gradient_end');
		$mapper->addMapping('filter_dropdown_background_gradient_start', 'toolbar_dropdown_background_gradient_start');
		$mapper->addMapping('filter_dropdown_border', 'toolbar_dropdown_border');
		$mapper->addMapping('filter_dropdown_divider_color', 'toolbar_dropdown_divider_color');
		$mapper->addMapping('filter_dropdown_text_color', 'toolbar_dropdown_text_color');
		$mapper->addMapping('filter_dropdown_text_color_active', 'toolbar_dropdown_text_color_active');

		// Do the processing
		if ($mapper->load() AND $mapper->process())
		{
			$this->show_message($this->phrase['version']['408']['mapping_customized_stylevars']);
			//$mapper->displayResults(); // Debug only
			$mapper->processResults();
		}
		else
		{
			$this->skip_message();
		}
	}

	/*
	 * Step 11 - Add filedataresize table
	 */
	public function step_11()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'filedataresize'),
			"CREATE TABLE " . TABLE_PREFIX . "filedataresize (
				filedataid INT UNSIGNED NOT NULL,
				resize_type ENUM('icon', 'thumb', 'small', 'medium', 'large') NOT NULL DEFAULT 'thumb',
				resize_filedata MEDIUMBLOB,
				resize_filesize INT UNSIGNED NOT NULL DEFAULT '0',
				resize_dateline INT UNSIGNED NOT NULL DEFAULT '0',
				resize_width SMALLINT UNSIGNED NOT NULL DEFAULT '0',
				resize_height SMALLINT UNSIGNED NOT NULL DEFAULT '0',
				reload TINYINT UNSIGNED NOT NULL DEFAULT '0',
				PRIMARY KEY (filedataid, resize_type),
				KEY type (resize_type)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	/*
	 * Step 12 - Convert filedata
	 */
	public function step_12($data = NULL)
	{
		if ($this->field_exists('filedata', 'thumbnail'))
		{
			if (empty($data['startat']))
			{
				$this->show_message(sprintf($this->phrase['vbphrase']['update_table_x'], TABLE_PREFIX . 'filedataresize', 1, 1));
			}

			$callback = function($startat, $nextid)
			{
				vB::getDbAssertor()->assertQuery('vBInstall:insertThumbnailsIntoFiledataresize', array(
					'startat' => $startat,
					'nextid' => $nextid,
				));
			};

			return $this->updateByIdWalk($data, 500, 'vBInstall:getMaxFiledataid', 'vBForum:filedata', 'filedataid', $callback);
		}
		else
		{
			$this->skip_message();
		}
	}

	/*
	 * Drop thumbnail fields
	 */
	public function step_13()
	{
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], 'filedata', 1, 5),
			'filedata',
			'thumbnail'
		);
	}

	/*
	 * Drop thumbnail fields
	 */
	public function step_14()
	{
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], 'filedata', 2, 5),
			'filedata',
			'thumbnail_filesize'
		);
	}

	/*
	 * Drop thumbnail fields
	 */
	public function step_15()
	{
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], 'filedata', 3, 5),
			'filedata',
			'thumbnail_width'
		);
	}

	/*
	 * Drop thumbnail fields
	 */
	public function step_16()
	{
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], 'filedata', 4, 5),
			'filedata',
			'thumbnail_height'
		);
	}

	/*
	 * Drop thumbnail fields
	 */
	public function step_17()
	{
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], 'filedata', 5, 5),
			'filedata',
			'thumbnail_dateline'
		);
	}
}

class vB_Upgrade_500b9 extends vB_Upgrade_Version
{
	//We have some data elements with missing closure records. Let's repair them.
	public function step_1($data = [])
	{
		if ($this->tableExists('blog'))
		{
			$repairTypes = [
				1 => vB_Types::instance()->getContentTypeID('vBForum_Album'),
				2 => 9984,
				3 => 9011,
				4 => 9986,
				5 => 9990
			];

			$startat = max(1, $data['startat'] ?? 0);

			$this->show_message(sprintf($this->phrase['version']['500b9']['fixing_closure_records_step_x'], $startat), true);
			$assertor = vB::getDbAssertor();
			$nodeids = [];
			$nodeQry = $assertor->assertQuery('vBInstall:missingClosureByType', ['oldcontenttypeid' => $repairTypes[$startat], 'batchsize' => 250]);

			if (!$nodeQry->valid())
			{
				//If we have already scanned all the types, we are done.
				if ($startat >= 5)
				{
					$this->show_message(sprintf($this->phrase['core']['process_done']));
					return;
				}
				return(['startat' => $startat + 1]);
			}

			foreach($nodeQry AS $node)
			{
				$nodeids[] = $node['nodeid'];
			}

			//make sure we have no detritus for these nodes.
			$assertor->assertQuery('vBForum:closure', [vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_DELETE, 'child' => $nodeids]);
			//First the record with depth = 0
			$assertor->assertQuery('vBInstall:addClosureSelfForNodes', ['nodeid' => $nodeids]);
			//Then the parent records.
			$assertor->assertQuery('vBInstall:addClosureParentsForNodes', ['nodeid' => $nodeids]);
			return(['startat' => $startat]);
		}
		else
		{
			$this->skip_message();
		}
	}

	/** Some blog posts are marked approved when they shouldn't be.
	 *
	 */
	public function step_2($data = array())
	{
		if ($this->tableExists('blog') AND $this->tableExists('blog_text'))
		{
			$this->show_message(sprintf($this->phrase['version']['500b9']['fixing_blog_counts_step_x'], 1));
			vB::getDbAssertor()->assertQuery('vBInstall:updateBlogModerated', array());
		}
		else
		{
			$this->skip_message();
		}
	}

	/** The count was incorrect in the vb4 blog table, so let's correct
	 *
	 */
	public function step_3($data = array())
	{
		if ($this->tableExists('blog') AND $this->tableExists('blog_text'))
		{
			$this->show_message(sprintf($this->phrase['version']['500b9']['fixing_blog_counts_step_x'], 2));
			vB::getDbAssertor()->assertQuery('vBInstall:updateBlogCounts', array());
		}
		else
		{
			$this->skip_message();
		}
	}
}

class vB_Upgrade_500rc1 extends vB_Upgrade_Version
{
	/**
	 * Handle customized values for stylevars that have been renamed
	 */
	public function step_1()
	{
		$mapper = new vB_Stylevar_Mapper();

		// Add mappings
		$mapper->addMapping('thread_comment_background', 'comment_background');
		$mapper->addMapping('thread_comment_divider_color', 'comment_divider_color');

		// Do the processing
		if ($mapper->load() AND $mapper->process())
		{
			$this->show_message($this->phrase['version']['408']['mapping_customized_stylevars']);
			$mapper->processResults();
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	 * Add forumpermissons2 field in permission table.
	 */
	public function step_2()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'permission', 1, 1),
			'permission',
			'forumpermissions2',
			'int',
			['length' => 10, 'null' => false, 'default' => 0, 'attributes' => 'UNSIGNED']
		);
	}

	/**
	 * Update site navbars
	 */
	public function step_3()
	{
		$this->syncNavbars();
	}

	public function step_4()
	{
		$this->skip_message();
	}

	/** Drop hasowner column **/
	public function step_5()
	{
		if ($this->field_exists('node', 'hasowner'))
		{
			$this->drop_field(
				sprintf($this->phrase['core']['altering_x_table'], 'node', 1, 1),
				'node',
				'hasowner'
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/*
	 * The channel owner should have canconfigchannel in any channels where they are a moderator
	 */
	public function step_6()
	{
		vB_Upgrade::createAdminSession();
		$this->show_message($this->phrase['version']['500rc1']['correcting_channelowner_permission']);
		$forumPerms = vB::getDatastore()->getValue('bf_ugp_forumpermissions2');
		vB::getDbAssertor()->assertQuery('vBInstall:grantOwnerForumPerm:', [
			'permission' => $forumPerms['canconfigchannel'],
			'systemgroupid' => 9
		]);
		vB::getUserContext()->rebuildGroupAccess();

	}

	/**
	 * Add missing request and notification types
	 */
	public function step_7()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'privatemessage', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "privatemessage CHANGE about about ENUM(
				'vote',
				'vote_reply',
				'rate',
				'reply',
				'follow',
				'following',
				'vm',
				'comment',
				'threadcomment',
				'subscription',
				'moderate',
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
				'" . vB_Api_Node::REQUEST_SG_TAKE_MEMBER . "');
			"
		);
	}

	/**
	 * Import notifications for new visitor messages
	 */
	public function step_8($data = NULL)
	{
		// THIS HAS BEEN UPDATED AND MOVED INTO 516A6 STEP 1, AS IT REQUIRES
		// THE NEW NOTIFICATION TABLES & TYPE DATA.
		$this->skip_message();
		return;
	}

	/**
	 * Import notifications for social group invites
	 */
	public function step_9($data = null)
	{
		if (!$this->tableExists('socialgroupmember'))
		{
			$this->skip_message();
			return;
		}

		if (empty($data['startat']))
		{
			$this->show_message($this->phrase['version']['500rc1']['import_group_invites']);
		}

		$callback = function($startat, $nextid)
		{
			$db = vB::getDbAssertor();

			// Fetch user info
			$users = $db->assertQuery('vBInstall:user', [
				vB_dB_Query::COLUMNS_KEY => ['userid', 'socgroupinvitecount'],
				vB_dB_Query::CONDITIONS_KEY => [
					['field' => 'userid', 'value' => $startat, 'operator' => vB_dB_Query::OPERATOR_GTE],
					['field' => 'userid', 'value' => $nextid, 'operator' => vB_dB_Query::OPERATOR_LT],
				]
			]);

			if ($users)
			{
				vB_Upgrade::createAdminSession();

				// build a map of group info, indexed by the old groupid
				$groupInfo = [];

				$oldContentTypeId = vB_Types::instance()->getContentTypeID('vBForum_SocialGroup');
				$oldSocialGroups = $db->assertQuery('vbForum:node', [
					vB_dB_Query::COLUMNS_KEY => ['nodeid', 'oldid', 'userid'],
					vB_dB_Query::CONDITIONS_KEY => [
						['field' => 'oldcontenttypeid', 'value' => $oldContentTypeId],
					],
				]);
				if ($oldSocialGroups)
				{
					foreach ($oldSocialGroups AS $oldSocialGroup)
					{
						$groupInfo[$oldSocialGroup['oldid']] = $oldSocialGroup;
					}
				}

				// Note: These are requests, not notifications, and are not affected by notification refactor.
				$notifications = [];

				foreach($users AS $user)
				{

					if ($user['socgroupinvitecount'] > 0)
					{
						// get groups that this user has been invited to
						$groups = $db->getRows('vBInstall:socialgroupmember', [
							vB_dB_Query::COLUMNS_KEY => ['groupid'],
							vB_dB_Query::CONDITIONS_KEY => [
								['field' => 'userid', 'value' => $user['userid']],
								['field' => 'type', 'value' => 'invited'],
							],
						]);

						// get vB5 node information for the groups
						$nodes = [];
						foreach ($groups AS $group)
						{
							if ($group['groupid'] > 0)
							{
								$nodes[] = $groupInfo[$group['groupid']];
							}
						}

						// prepare notifications
						foreach ($nodes AS $node)
						{
							$notifications[] = [
								'about' => vB_Api_Node::REQUEST_SG_TAKE_MEMBER,
								'aboutid' => $node['nodeid'],
								'sentto' => $user['userid'],
								'sender' => $node['userid'],
							];
						}
					}
				}

				$messageLibrary = vB_Library::instance('Content_Privatemessage');

				foreach ($notifications AS $notification)
				{
					$notification['msgtype'] = 'request';
					$notification['rawtext'] = '';

					// send notification only if receiver is not the sender.
					// also check receiver's notification options with userReceivesNotification(userid, about)
					if (($notification['sentto'] != $notification['sender']) AND $messageLibrary->userReceivesNotification($notification['sentto'], $notification['about']))
					{
						// check for duplicate requests
						$messageLibrary->checkFolders($notification['sentto']);
						$folders = $messageLibrary->fetchFolders($notification['sentto']);
						$folderid = $folders['systemfolders'][vB_Library_Content_Privatemessage::REQUEST_FOLDER];

						$dupeCheck = $db->getRows('vBInstall:500rc1_checkDuplicateRequests', [
							'userid' => $notification['sentto'],
							'folderid' => $folderid,
							'aboutid' => $notification['aboutid'],
							'about' => vB_Api_Node::REQUEST_SG_TAKE_MEMBER,
						]);

						// if not duplicate, insert the message
						if (count($dupeCheck) == 0)
						{
							$nodeid = $messageLibrary->addMessageNoFlood($notification);
						}
					}
				}
			}
		};

		$batchsize = $this->getBatchSize('xxxsmall', __FUNCTION__);
		return $this->updateByIdWalk($data, $batchsize, 'vBInstall:getMaxUserid', 'user', 'userid', $callback);
	}

	/**
	 * Import notifications for social group join requests
	 */
	public function step_10($data = null)
	{
		if (!$this->tableExists('socialgroupmember'))
		{
			$this->skip_message();
			return;
		}

		if (empty($data['startat']))
		{
			$this->show_message($this->phrase['version']['500rc1']['import_group_requests']);
		}

		$callback = function($startat, $nextid)
		{
			$db = vB::getDbAssertor();

			// Fetch user info
			$users = $db->assertQuery('vBInstall:user', [
				vB_dB_Query::COLUMNS_KEY => ['userid', 'socgroupreqcount'],
				vB_dB_Query::CONDITIONS_KEY => [
					['field' => 'userid', 'value' => $startat, 'operator' => vB_dB_Query::OPERATOR_GTE],
					['field' => 'userid', 'value' => $nextid, 'operator' => vB_dB_Query::OPERATOR_LTE],
				]
			]);

			if ($users)
			{
				vB_Upgrade::createAdminSession();

				//$nodeLibrary = vB_Library::instance('node');

				// build a map of group info, indexed by the old groupid
				$groupInfo = [];

				$oldContentTypeId = vB_Types::instance()->getContentTypeID('vBForum_SocialGroup');
				$oldSocialGroups = $db->assertQuery('vbForum:node', [
					vB_dB_Query::COLUMNS_KEY => ['nodeid', 'oldid', 'userid'],
					vB_dB_Query::CONDITIONS_KEY => [
						['field' => 'oldcontenttypeid', 'value' => $oldContentTypeId],
					],
				]);
				if ($oldSocialGroups)
				{
					foreach ($oldSocialGroups AS $oldSocialGroup)
					{
						$groupInfo[$oldSocialGroup['oldid']] = $oldSocialGroup;
					}
				}

				// Note: These are requests, not notifications, and are not affected by notification refactor.
				$notifications = [];
				foreach($users AS $user)
				{
					if ($user['socgroupreqcount'] > 0)
					{
						// get nodes that this user owns or moderates
						$modNodeResult = vB_Library::instance('user')->getGroupInTopic($user['userid']);
						$modNodes = [];
						if ($modNodeResult)
						{
							foreach ($modNodeResult AS $modNodeResultItem)
							{
								$modNodes[] = $modNodeResultItem['nodeid'];
							}
						}

						// based on nodes, get groups that this user owns or moderates
						$modGroupOldIds = [];
						$oldContentTypeId = vB_Types::instance()->getContentTypeID('vBForum_SocialGroup');
						$modGroupsResult = $db->assertQuery('vbForum:node', [
							vB_dB_Query::COLUMNS_KEY => ['nodeid', 'oldid'],
							vB_dB_Query::CONDITIONS_KEY => [
								['field' => 'oldcontenttypeid', 'value' => $oldContentTypeId],
								['field' => 'nodeid', 'value' => $modNodes],
							],
						]);

						if ($modGroupsResult)
						{
							foreach ($modGroupsResult AS $modGroupsResultItem)
							{
								$modGroupOldIds[] = $modGroupsResultItem['oldid'];
							}
						}

						// form this user's groups, get the ones that have pending (moderated) users waiting for approval
						$groups = $db->getRows('vBInstall:socialgroupmember', [
							vB_dB_Query::COLUMNS_KEY => ['groupid', 'userid'],
							vB_dB_Query::CONDITIONS_KEY => [
								['field' => 'groupid', 'value' => $modGroupOldIds],
								['field' => 'type', 'value' => 'moderated'],
							],
						]);

						// get vB5 node information for the groups and add the userid of the pending / moderated user
						$nodes = [];
						$i = 0;
						foreach ($groups AS $group)
						{
							if ($group['groupid'] > 0)
							{
								$nodes[$i] = $groupInfo[$group['groupid']];
								$nodes[$i]['moderateduserid'] = $group['userid'];
								++$i;
							}
						}

						// prepare notifications
						foreach ($nodes AS $node)
						{
							$notifications[] = [
								'about' => vB_Api_Node::REQUEST_SG_GRANT_MEMBER,
								'aboutid' => $node['nodeid'],
								'sentto' => $user['userid'],
								'sender' => $node['moderateduserid'],
							];
						}
					}
				}

				$messageLibrary = vB_Library::instance('Content_Privatemessage');
				foreach ($notifications AS $notification)
				{
					$notification['msgtype'] = 'request';
					$notification['rawtext'] = '';

					// send notification only if receiver is not the sender.
					// also check receiver's notification options with userReceivesNotification(userid, about)
					if (($notification['sentto'] != $notification['sender']) AND $messageLibrary->userReceivesNotification($notification['sentto'], $notification['about']))
					{
						// check for duplicate requests
						$messageLibrary->checkFolders($notification['sentto']);
						$folders = $messageLibrary->fetchFolders($notification['sentto']);
						$folderid = $folders['systemfolders'][vB_Library_Content_Privatemessage::REQUEST_FOLDER];

						$dupeCheck = $db->getRows('vBInstall:500rc1_checkDuplicateRequests', [
							'userid' => $notification['sentto'],
							'folderid' => $folderid,
							'aboutid' => $notification['aboutid'],
							'about' => vB_Api_Node::REQUEST_SG_GRANT_MEMBER,
						]);

						// if not duplicate, insert the message
						if (count($dupeCheck) == 0)
						{
							$nodeid = $messageLibrary->addMessageNoFlood($notification, ['skipNonExistentRecipients' => true]);
						}
					}
				}
			}
		};

		$batchsize = $this->getBatchSize('xxxsmall', __FUNCTION__);
		return $this->updateByIdWalk($data, $batchsize, 'vBInstall:getMaxUserid', 'user', 'userid', $callback);
	}

	/*
	 * Set forum html state for imported starters (not set in 500a1 step_145)
	 */
	public function step_11($data = NULL)
	{
		//this step needs to happen before we set empty htmlstate values to off because it only
		//updates empty values. We lose that information once we force the default.  Swapping steps
		//to get the right order after collapsing the default setting from a later step.
		//
		//this is a little tricky.  In the big data set I'm looking at there is only 1 affected record.
		//normally a limit approach would be in order, but what we are looking for isn't on any kind of
		//index so we'll have to scan the resultset to find the affected records to start counting the limit.
		//At that point you might as well let the query run -- which is much faster for this DB, but risks
		//timeout on a DB with a lot more threads.  So we'll iterate over the smallest table we touch
		//(thread_post) but we'll also find the next ID instead of blindly iterating over potentently empty
		//ranges -- if we're only processing 500 at a time we should make sure there are 500 to process.
		//This requires scanning over our batch size to find the last record in the limit set, but that's bounded
		//by the batch size (and we find our starting point on the index).
		if ($this->tableExists('forum') AND $this->tableExists('post') AND $this->field_exists('post', 'htmlstate'))
		{
			if (empty($data['startat']))
			{
				$this->show_message($this->phrase['version']['500rc1']['updating_text_nodes']);
			}

			$callback = function($startat, $nextid)
			{
				vB::getDbAssertor()->assertQuery('vBInstall:updateStarterPostHtmlState', array(
					'startat' => $startat,
					'nextthreadid' => $nextid,
				));
			};


			$batchsize = $this->getBatchSize('xxxsmall', __FUNCTION__);
			return $this->updateByIdWalk($data, $batchsize, 'vBInstall:getThreadPostMaxThread', 'vBForum:thread_post', 'threadid', $callback);
		}
		else
		{
			$this->skip_message();
		}
	}

	/*
	 * Turn off html for imported posts that don't have allowhtml in the original forum.
	 * Relies on forum options being imported correctly before granting allow html option in a later upgrade step.
	 */
	public function step_12($data = NULL)
	{
		$db = vB::getDbAssertor();

		$batchsize = 5000;
		$threadTypeId = vB_Types::instance()->getContentTypeID('vBForum_Thread');
		$postTypeId = vB_Types::instance()->getContentTypeID('vBForum_Post');
		$forumOptions = vB::getDatastore()->getValue('bf_misc_forumoptions');

		if (!empty($data['max']))
		{
			$max = $data['max'];
		}
		else
		{
			$max = $db->getRow('vBInstall:getMaxNodeid');
			$max = $max['maxid'];

			//If we don't have any posts, we're done.
			if (intval($max) < 1)
			{
				$this->skip_message();
				return;
			}
		}

		$startat = intval($data['startat'] ?? 0);
		if ($startat)
		{
			$this->show_message($this->phrase['version']['500rc1']['updating_text_nodes']);
		}

		if ($startat > $max)
		{
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			return;
		}

		$this->show_message(sprintf($this->phrase['core']['processed_records_x_y_z'], $startat, min($startat + $batchsize, $max), $max), true);

		//I'm not entirely clear on the purpose of this condition.  We don't reference any of these tables in the
		//query we wrap this in so it's not to prevent a DB error.  It's possible that the query changed at some
		//point.  I'm collapsing two steps into a single pass of the text table.  Previously this entire step
		//was wrapped in this block, but we should run *a* query here.  We'll fall back to the query the other
		//step ran previously if we wouldn't have run this step originally.  updateImportedForumPostHtmlState now
		//does what updateAllTextHtmlStateDefault in addition to what it used to do.
		//
		//Most of the time spent is in the batching/running through the records on the DB.  The actual updating is
		//small change so this should be a substantial time savings over running them twice.
		if ($this->tableExists('forum') AND $this->tableExists('post') AND $this->field_exists('post', 'htmlstate'))
		{
			$db->assertQuery('vBInstall:updateImportedForumPostHtmlState', array(
				'startat' => $startat,
				'batchsize' => $batchsize,
				'allowhtmlpermission' => $forumOptions['allowhtml'],
				'oldcontenttypeids' => array($threadTypeId, $postTypeId),
			));
		}
		else
		{
			$db->assertQuery('vBInstall:updateAllTextHtmlStateDefault', array(
				'startat' => $startat,
				'batchsize' => $batchsize
			));
		}
		return array('startat' => ($startat + $batchsize), 'max' => $max);
	}

	/**
	 * Turn off htmlstate for blog entries and comments
	 * 	from vb3/4.
	 * Turning off vB5 blog entries / comments should be handled by
	 *  updateAllTextHtmlStateDefault since they'll be null
	 * We'll tackle handling it correctly in the future, but right now we want to avoid potential XSS issues.
	 */
	public function step_13($data = NULL)
	{
		//
		// We scan the entire text table to set the html state in step 11 anyway (and previously step 17 which
		// was collapsed into step 11 -- this could be collapsed there as well)
		// What we want to set it to is different, but that could be handled with one join and some extra "or" logic.
		// Using or is frequently bad, but in this case it won't disrupt any indexes or cause us to
		// scan extra rows.  The join is going to be the bigger deal, but it's going to be faster
		// than scanning everything twice.
		//

		// check if blog product exists. Else, no action needed
		if (isset($this->registry->products['vbblog']) AND $this->registry->products['vbblog'])
		{
			$this->show_message($this->phrase['version']['500rc1']['updating_text_nodes_for_blogs']);
			$batchsize = 500;
			// contenttypeid 9985 - from class_upgrade_500a22 step2
			// contenttypeid 9984 - from class_upgrade_500a22 step3
			$oldContetypeid_blogStarter = 9985;
			$oldContetypeid_blogReply = 9984;

			//this doesn't really work because "max" isn't propagated in $data, but
			//leaving it in so that it will work if we fix that.
			if (!empty($data['max']))
			{
				$max = $data['max'];
			}
			else
			{
				// grab the max id for imported vb3/4 blog entry/reply content types
				$max = vB::getDbAssertor()->getRow('vBInstall:getMaxNodeidForOldContent', [
					'oldcontenttypeid' => [$oldContetypeid_blogStarter, $oldContetypeid_blogReply]
				]);
				$max = $max['maxid'];
			}

			$startat = intval($data['startat'] ?? 0);
			if ($startat > $max)
			{
				// we're done here
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}
			else
			{
				// let's just turn them all off for now.
				vB::getDbAssertor()->assertQuery('vBInstall:updateImportedBlogPostHtmlState', array(
					'startat' => $startat,
					'batchsize' => $batchsize,
					'oldcontenttypeids' => array($oldContetypeid_blogStarter, $oldContetypeid_blogReply)
				));

				// start next batch
				return array('startat' => ($startat + $batchsize), 'max' => $max);
			}
		}
		else
		{
			// no action needed for vb5 upgrades for now
			$this->skip_message();
		}
	}

	/*
	 * Update default channel options. Allow HTML by default. Leave it to channel permissions.
	 */
	public function step_14()
	{
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], 'channel'));
		vB::getDbAssertor()->assertQuery('vBInstall:alterChannelOptions');
	}

	/*
	 * Set allowhtml for channels. This should be handled by channel permissions and text.htmlstate.
	 */
	public function step_15()
	{
		$this->show_message($this->phrase['version']['500rc1']['updating_channel_options']);
		$forumOptions = vB::getDatastore()->getValue('bf_misc_forumoptions');
		vB::getDbAssertor()->assertQuery('vBInstall:updateAllowHtmlChannelOption', array(
			'allowhtmlpermission' => $forumOptions['allowhtml']
		));
	}

	/*
	 * Set the html state to not be null and a sane default
	 */
	public function step_16()
	{
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], 'text'));
		vB::getDbAssertor()->assertQuery('vBInstall:alterTextHtmlstate');
	}

	/**
	 * Update the default text.allow html to be 'off' instead of NULL or ''
	 * this was added to the query in step_11 and is no longer needed
	 */
	public function step_17()
	{
		$this->skip_message();
	}

	/**
	 * Fix contenttypeid for redirect nodes
	 */
	public function step_18($data = null)
	{
		$db = vB::getDbAssertor();

		$batchsize = 500;

		//old redirects
		$oldcontenttype = 9980;

		//this doesn't really work because "max" isn't propagated in $data, but
		//leaving it in so that it will work if we fix that.
		if (!empty($data['max']))
		{
			$max = $data['max'];
		}
		else
		{
			// grab the max id for imported vb3/4 blog entry/reply content types
			$max = $db->getRow('vBInstall:getMaxOldidForOldContent', ['oldcontenttypeid' => $oldcontenttype]);
			if (empty($max['maxid']))
			{
				$this->skip_message();
				return;
			}

			$max = $max['maxid'];
		}

		$startat = intval($data['startat'] ?? 0);
		if ($startat > $max)
		{
			// we're done here
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			return;
		}

		$nextrow = $db->getRows(
			'vBForum:node',
			array(
				vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
				vB_dB_Query::CONDITIONS_KEY => array(
					'oldcontenttypeid' => $oldcontenttype,
					array('field' => 'oldid', 'value' => $startat, 'operator' =>  vB_dB_Query::OPERATOR_GT),
				),
				vB_dB_Query::COLUMNS_KEY => array('oldid'),
				vB_Db_Query::PARAM_LIMIT => 1,
				vB_Db_Query::PARAM_LIMITSTART => $batchsize
			),
			array('oldcontenttypeid', 'nodeid')
		);

		//if we don't have a row, we paged off the table so we just need to go from start to the end
		if ($nextrow)
		{
			$nextrow = reset($nextrow);
			$nextoldid = $nextrow['oldid'];
		}
		else
		{
			//we don't include the next threadid in the query below so we need to go "one more than max"
			//to ensure that we process the last record and terminate on the next call.
			$nextoldid = $max+1;
		}

		$this->show_message(sprintf($this->phrase['version']['500rc1']['updating_nodes_to_oldid_x_max_y'], $nextoldid, $max));

		vB_Types::instance()->reloadTypes();
		$redirectTypeId = vB_Types::instance()->getContentTypeId('vBForum_Redirect');

		$db->assertQuery('vBInstall:fixRedirectContentTypeId', array(
			'redirectContentTypeId' => $redirectTypeId,
			'redirectOldContentTypeId' => $oldcontenttype,
			'startat' => $startat,
			'nextoldid' => $nextoldid,
		));

		// start next batch
		return array('startat' => $nextoldid, 'max' => $max);
	}

	/**
	 * Fix some forumpermissons2 values as three bitfields moved.
	 */
	public function step_19()
	{
		/* We only need to run this once.
		To do this we check the upgrader log to
		see if this step has been previously run. */
		$log = vB::getDbAssertor()->getRow('vBInstall:upgradelog', array('script' => '500rc1', 'step' => 19)); // Must match this step.

		if (empty($log))
		{
			vB::getDbAssertor()->assertQuery(
			'vBInstall:fixFperms2',
			array (
					'oldp1' => 16777216, // canattachmentcss in vB4.
					'oldp2' => 33554432, // bypassdoublepost in vB4.
					'oldp3' => 67108864, // canwrtmembers in vB4.
					'newp1' => 8,
					'newp2' => 16,
					'newp3' => 32,
				)
			);

			$this->show_message(sprintf($this->phrase['vbphrase']['update_table_x'], TABLE_PREFIX . 'permission', 1, 1));
		}
		else
		{
			$this->skip_message();
		}
	}

	/*
	 * Remove the vB4.2 cronemail task if it exists.
	 */
	public function step_20()
	{
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], 'cron'));
		vB::getDbAssertor()->delete('cron', array('varname' => array('cronmail', 'reminder', 'activitypopularity')));
	}

	/*
	 Step 21 - Used to add the nodestats (node_dailycleanup.php) cron, but the cron's been removed VBV-11871
	*/
	public function step_21()
	{
		$this->skip_message();
	}
}

class vB_Upgrade_501a1 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->skip_message();
	}

	/*
	 Step 2 - Used to add the nodestats (node_dailycleanup.php) cron, but the cron's been removed VBV-11871
	*/
	public function step_2()
	{
		$this->skip_message();
	}

	/*
	 * Remove the vB4 tasks
	 */
	public function step_3()
	{
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], 'cron'));
		vB::getDbAssertor()->delete('cron', array('varname' => array('cronmail', 'reminder', 'activitypopularity')));
	}

	/** Fix blog title, description **/
	public function step_4($data = [])
	{
		if ($this->tableExists('blog_user') AND $this->tableExists('node'))
		{
			$this->show_message($this->phrase['version']['501a1']['fixing_blog_data']);
			$startat = intval($data['startat'] ?? 0);
			$batchsize = 2000;
			$assertor = vB::getDbAssertor();

			if (isset($data['startat']))
			{
				$startat = $data['startat'];
			}

			if (!empty($data['maxvB4']))
			{
				$maxToFix = $data['maxvB4'];
			}
			else
			{
				$maxToFix = $assertor->getRow('vBInstall:getMaxBlogUserIdToFix', array('contenttypeid' => 9999));
				$maxToFix = intval($maxToFix['maxid']);

				//If there are no blogs to fix...
				if (intval($maxToFix) < 1)
				{
					$this->skip_message();
					return;
				}
			}

			if (!isset($startat))
			{
				$maxFixed = $assertor->getRow('vBInstall:getMaxFixedBlogUserId', array('contenttypeid' => 9999));

				if (!empty($maxFixed) AND !empty($maxFixed['maxid']))
				{
					$startat = $maxFixed['maxid'];
				}
				else
				{
					$startat = 0;
				}
			}

			$blogs  = $assertor->assertQuery('vBInstall:getBlogsUserToFix', array('startat' => $startat, 'batchsize' => $batchsize, 'contenttypeid' => 9999));
			if (!$blogs->valid())
			{
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}

			vB_Upgrade::createAdminSession();
			$channelLib = vB_Library::instance('content_channel');
			foreach ($blogs AS $blog)
			{
				$blog['urlident'] = $channelLib->getUniqueUrlIdent($blog['title']);
				$channelLib->update($blog['nodeid'], $blog);
			}

			$this->show_message(sprintf($this->phrase['core']['processed_records_x'], $batchsize));
			return array('startat' => ($startat + $batchsize), 'maxvB4' => $maxToFix);
		}
		else
		{
			$this->skip_message();
		}
	}

	/** Fix nodeoptions **/
	public function step_5($data = [])
	{
		if ($this->tableExists('blog_user') AND $this->tableExists('node'))
		{
			$this->show_message($this->phrase['version']['501a1']['fixing_blog_options']);
			$startat = intval($data['startat'] ?? 0);
			$batchsize = 2000;
			$assertor = vB::getDbAssertor();

			if (isset($data['startat']))
			{
				$startat = $data['startat'];
			}

			if (!empty($data['maxvB4']))
			{
				$maxToFix = $data['maxvB4'];
			}
			else
			{
				$maxToFix = $assertor->getRow('vBInstall:getMaxBlogUserIdToFixOptions', array('contenttypeid' => 9999));
				$maxToFix = intval($maxToFix['maxid']);

				//If there are no blogs to fix...
				if (intval($maxToFix) < 1)
				{
					$this->skip_message();
					return;
				}
			}

			if (!isset($startat))
			{
				$maxFixed = $assertor->getRow('vBInstall:getMaxOptionsFixedBlogUserId', array('contenttypeid' => 9999));

				if (!empty($maxFixed) AND !empty($maxFixed['maxid']))
				{
					$startat = $maxFixed['maxid'];
				}
				else
				{
					$startat = 0;
				}
			}

			$blogs  = $assertor->assertQuery('vBInstall:getBlogsUserToFixOptions', array('startat' => $startat, 'batchsize' => $batchsize, 'contenttypeid' => 9999));
			if (!$blogs->valid())
			{
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}

			// let's map the vB4 options and port them to vB5
			// allow smilie 					=> 1/0
			// moderate comments 				=> 1
			// parse links						=> 2
			// allow comments 					=> 4
			// options_member/guest can view	=> 1
			// options_member/guest can post 	=> 2
			$options = array(1 => vB_Api_Node::OPTION_MODERATE_COMMENTS, 2 => vB_Api_Node::OPTION_NODE_PARSELINKS, 4 => vB_Api_Node::OPTION_ALLOW_POST);
			$perms = array('viewperms' => 1, 'commentperms' => 2);
			vB_Upgrade::createAdminSession();
			$nodeLib = vB_Library::instance('node');
			$nodeApi = vB_Api::instance('node');
			foreach ($blogs AS $blog)
			{
				$nodeoption = 0;
				if (!$blog['allowsmilie'])
				{
					$nodeoption |= vB_Api_Node::OPTION_NODE_DISABLE_SMILIES;
				}

				foreach ($options AS $vb4 => $vb5)
				{
					if ($blog['options'] & $vb4)
					{
						$nodeoption |= $vb5;
					}
				}

				$nodeperms = array();
				foreach ($perms AS $name => $val)
				{
					// everyone
					if (($blog['options_member'] & $val) AND ($blog['options_guest'] & $val))
					{
						$nodeperms[$name] = 2;
					}
					// registered and members
					else if (($blog['options_member'] & $val) AND !($blog['options_guest'] & $val))
					{
						$nodeperms[$name] = 1;
					}
					// there's no currently guest only option in blogs...leave this alone
					else if (!($blog['options_member'] & $val) AND ($blog['options_guest'] & $val))
					{
						$nodeperms[$name] = 2;
					}
					// let's just leave any other case as blog members
					else
					{
						$nodeperms[$name] = 0;
					}
				}

				$nodeLib->setNodeOptions($blog['nodeid'], $nodeoption);
				$nodeApi->setNodePerms($blog['nodeid'], $nodeperms);
			}

			$this->show_message(sprintf($this->phrase['core']['processed_records_x'], $batchsize));
			return array('startat' => ($startat + $batchsize), 'maxvB4' => $maxToFix);
		}
		else
		{
			$this->skip_message();
		}
	}

	/*
	 * Step 6 - Grant canalwaysview, canalwayspost, canalwayspostnew to admin and super mods
	 */
	public function step_6()
	{
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], 'permission'));
		vB::getDbAssertor()->assertQuery('vBInstall:setCanAlwaysPerms', array('groupids' => array(vB_Api_UserGroup::SUPER_MODERATOR, vB_Api_UserGroup::ADMINISTRATOR)));
	}

	/**
	*	Correctly import htmlstate for blog entries
	*
	*/
	public function step_7($data = [])
	{
		// check if blog product exists. Else, no action needed
		if 	(
			(isset($this->registry->products['vbblog']) AND $this->registry->products['vbblog']) OR
				$this->field_exists('usergroup', 'vbblog_entry_permissions')
		)
		{
			$db = vB::getDbAssertor();
			$batchsize = $this->getBatchSize('xxxsmall', __FUNCTION__);

			$this->show_message($this->phrase['version']['501a1']['importing_htmlstates_for_blog_entries']);
			$startat = intval($data['startat'] ?? 0);
			// contenttypeid 9985 - from class_upgrade_500a22 step2
			$oldContetypeid_blogStarter = 9985;

			if (!empty($data['usersWithAllowHTML']))
			{
				$usersWithAllowHTML = $data['usersWithAllowHTML'];
			}
			else
			{
				// grab all groups that have vbblog_entry_permissions & blog_allowhtml
				// Bitfields from vb4:
				// <bitfield name="blog_allowhtml" group="vbblog_entry_permissions" phrase="allow_html">8192</bitfield>
				// <bitfield name="blog_allowhtml" group="vbblog_comment_permissions" phrase="allow_html">1024</bitfield>
				// blog_comment_bitfield is 0 so that we ignore vbblog_comment_permissiosn
				$userGroupsWithAllowHTMLqry = $db->assertQuery('vBInstall:getUsergroupsWithAllowHtml', [
					'blog_entry_bitfield' => 8192,
					'blog_comment_bitfield' => 0
				]);

				// if there are no usergroups with the permissions, we're done.
				if (!$userGroupsWithAllowHTMLqry->valid())
				{
					$this->show_message(sprintf($this->phrase['core']['process_done']));
					return;
				}

				// make a list of usergroups
				$userGroupsWithAllowHTML = [];
				foreach($userGroupsWithAllowHTMLqry AS $qryRow)
				{
					$userGroupsWithAllowHTML[] = $qryRow['usergroupid'];
				}

				// generate a list of users that are in these usergroups
				$usersWithAllowHTMLQry = $db->assertQuery('vBInstall:getUsersInUsergroups', [
					'usergroupids' => $userGroupsWithAllowHTML
				]);

				// if there are no users in the usergroups, we're done.
				if (!$usersWithAllowHTMLQry->valid())
				{
					$this->show_message(sprintf($this->phrase['core']['process_done']));
					return;
				}

				// make a list of users
				$usersWithAllowHTML = [];
				foreach($usersWithAllowHTMLQry AS $qryRow)
				{
					$usersWithAllowHTML[] = $qryRow['userid'];
				}
			}


			if (!empty($data['max']))
			{
				$max = $data['max'];
			}
			else
			{
				// grab the max id for imported vb3/4 blog entry/reply content types
				$max = $db->getRow('vBInstall:getMaxNodeidForOldContent',	['oldcontenttypeid' => [$oldContetypeid_blogStarter]]);
				$max = $max['maxid'];
			}

			if ($startat > $max)
			{
				// we're done here
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}
			else
			{
				// import them for the users in $usersWithAllowHTML
				$db->assertQuery('vBInstall:importBlogEntryHtmlState', [
					'startat' => $startat,
					'batchsize' => $batchsize,
					'oldcontenttypeids' => $oldContetypeid_blogStarter,
					'usersWithAllowHTML' => $usersWithAllowHTML
				]);

				// output current progress
				$this->show_message(sprintf($this->phrase['core']['processed_records_x_y'], $startat + 1, $startat + $batchsize));
				// start next batch
				return ['startat' => ($startat + $batchsize), 'max' => $max, 'usersWithAllowHTML' => $usersWithAllowHTML];
			}
		}
		else
		{
			// no action needed since they didn't have blogs
			$this->skip_message();
		}
	}


	/**
	*	Correctly import htmlstate for blog comments
	*
	*/
	public function step_8($data = [])
	{
		// check if blog product exists. Else, no action needed
		if (
			(isset($this->registry->products['vbblog']) AND $this->registry->products['vbblog']) OR
				$this->field_exists('usergroup', 'vbblog_comment_permissions')
		)
		{

			$this->show_message($this->phrase['version']['501a1']['importing_htmlstates_for_blog_comments']);
			$startat = intval($data['startat'] ?? 0);
			$batchsize = 500;
			// contenttypeid 9985 - from class_upgrade_500a22 step2
			// contenttypeid 9984 - from class_upgrade_500a22 step3
			$oldContetypeid_blogReply = 9984;

			if (!empty($data['usersWithAllowHTML']))
			{
				$usersWithAllowHTML = $data['usersWithAllowHTML'];
			}
			else
			{
				// grab all groups that have vbblog_comment_permissiosn & blog_allowhtml
				// Bitfields from vb4:
				// <bitfield name="blog_allowhtml" group="vbblog_entry_permissions" phrase="allow_html">8192</bitfield>
				// <bitfield name="blog_allowhtml" group="vbblog_comment_permissions" phrase="allow_html">1024</bitfield>
				// blog_entry_bitfield is 0 so that we ignore vbblog_entry_permission
				$userGroupsWithAllowHTMLqry = vB::getDbAssertor()->assertQuery('vBInstall:getUsergroupsWithAllowHtml', array(
						'blog_entry_bitfield' => 0,
						'blog_comment_bitfield' => 1024
				));

				// if there are no usergroups with the permissions, we're done.
				if (!$userGroupsWithAllowHTMLqry->valid())
				{
					$this->show_message(sprintf($this->phrase['core']['process_done']));
					return;
				}

				// make a list of usergroups
				$userGroupsWithAllowHTML = array();
				foreach($userGroupsWithAllowHTMLqry AS $qryRow)
				{
					$userGroupsWithAllowHTML[] = $qryRow['usergroupid'];
				}

				// generate a list of users that are in these usergroups
				$usersWithAllowHTMLQry = vB::getDbAssertor()->assertQuery('vBInstall:getUsersInUsergroups', array(
						'usergroupids' => $userGroupsWithAllowHTML
				));

				// if there are no users in the usergroups, we're done.
				if (!$usersWithAllowHTMLQry->valid())
				{
					$this->show_message(sprintf($this->phrase['core']['process_done']));
					return;
				}

				// make a list of users
				$usersWithAllowHTML = array();
				foreach($usersWithAllowHTMLQry AS $qryRow)
				{
					$usersWithAllowHTML[] = $qryRow['userid'];
				}
			}


			if (!empty($data['max']))
			{
					$max = $data['max'];
			}
			else
			{
				// grab the max id for imported vb3/4 blog entry/reply content types
				$max = vB::getDbAssertor()->getRow(	'vBInstall:getMaxNodeidForOldContent',
											array(	'oldcontenttypeid' => array($oldContetypeid_blogReply))
										);
				$max = $max['maxid'];
			}

			if ($startat > $max)
			{
				// we're done here
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}
			else
			{
				// import them for the users in $usersWithAllowHTML
				vB::getDbAssertor()->assertQuery('vBInstall:importBlogCommentHtmlState', array(
					'startat' => $startat,
					'batchsize' => $batchsize,
					'oldcontenttypeids' => $oldContetypeid_blogReply,
					'usersWithAllowHTML' => $usersWithAllowHTML
				));

				// output current progress
				$this->show_message(sprintf($this->phrase['core']['processed_records_x_y'], $startat + 1, $startat + $batchsize));
				// start next batch
				return array('startat' => ($startat + $batchsize), 'max' => $max, 'usersWithAllowHTML' => $usersWithAllowHTML);
			}
		}
		else
		{
			// no action needed since they didn't have blogs
			$this->skip_message();
		}
	}

	/*
	 * Step 9 - Import blog membership records
	 * Note that we user startat to send the channel membergroupid so we don't request again.
	 */
	public function step_9($data = [])
	{
		if ($this->tableExists('blog_user') AND $this->tableExists('node') AND $this->tableExists('blog_groupmembership') AND $this->tableExists('groupintopic'))
		{
			$assertor = vB::getDbAssertor();
			$maxToImport = $assertor->getRow('vBInstall:getMaxBlogMemberToImport', array('contenttypeid' => 9999));
			$maxToImport = intval($maxToImport['maxnodeid']);

			//If there are no records
			if (intval($maxToImport) < 1)
			{
				$this->skip_message();
				return;
			}

			$batchsize = 2000;
			$this->show_message($this->phrase['version']['501a1']['importing_blog_members']);

			if (!empty($data['startat']))
			{
				$membergroupid = intval($data['startat']);
			}
			else
			{
				$membergroupRec = $assertor->getRow('vBForum:usergroup', array('systemgroupid' => vB_Api_UserGroup::CHANNEL_MEMBER_SYSGROUPID));
				$membergroupid = $membergroupRec['usergroupid'];
			}

			$assertor->assertQuery('vBInstall:importBlogMembers', array('batchsize' => $batchsize, 'contenttypeid' => 9999, 'groupid' => $membergroupid));
			$this->show_message(sprintf($this->phrase['core']['processed_records_x'], $batchsize));
			return array('startat' => $membergroupid);
		}
		else
		{
			$this->skip_message();
		}
	}

	/*
	 * Step 10 - Create needed PM starter sentto records for pm replies.
	 * Note that we user startat to send the pmtypeid so we don't request again.
	 */
	public function step_10($data = [])
	{
		if ($this->tableExists('node') AND $this->tableExists('sentto') AND $this->tableExists('messagefolder'))
		{
			$assertor = vB::getDbAssertor();
			if (!empty($data['startat']))
			{
				$pmType = $data['startat'];
			}
			else
			{
				$pmType = vB_Types::instance()->getContentTypeID('vBForum_PrivateMessage');
			}

			$maxToImport = $assertor->getRow('vBInstall:getMaxPmStarterToCreate', array('contenttypeid' => 9981, 'pmtypeid' => $pmType));
			$maxToImport = intval($maxToImport['maxid']);

			//If there are no records
			if (intval($maxToImport) < 1)
			{
				$this->skip_message();
				return;
			}

			$batchsize = 2000;
			$this->show_message($this->phrase['version']['501a1']['fixing_pm_starters']);
			$assertor->assertQuery('vBInstall:createStarterPmRecords', array('batchsize' => $batchsize, 'contenttypeid' => 9981, 'pmtypeid' => $pmType));
			$this->show_message(sprintf($this->phrase['core']['processed_records_x'], $batchsize));
			return array('startat' => $pmType);
		}
		else
		{
			$this->skip_message();
		}
	}

	/*
	 *	Step 11
	 *	Correct imported groups' pagetemplateids
	 */
	public function step_11($data = [])
	{
		if ($this->tableExists('socialgroup'))
		{
			// social groups don't have a specific hard-coded oldcontenttypeid (see 500a29 step_2~4)
			if (!empty($data['oldContentTypeId']))
			{
				$oldContentTypeId = $data['oldContentTypeId'];
			}
			else
			{
				$oldContentTypeId = vB_Types::instance()->getContentTypeID('vBForum_SocialGroup');
			}

			// grab the default channel page template's id
			if (!empty($data['defaultChannelTemplateid']))
			{
				$defaultChannelTemplateid = $data['defaultChannelTemplateid'];
			}
			else
			{
				$templatetitle = 'Default Channel Page Template';
				$titleQry = vB::getDbAssertor()->getRow('vBInstall:getPagetemplateidByTitle',	['templatetitle' => $templatetitle]);
				$defaultChannelTemplateid = $titleQry['pagetemplateid'];
			}

			// grab the default channel page template's id
			if (!empty($data['defaultGroupshomeTemplateid']))
			{
				$defaultGroupshomeTemplateid = $data['defaultGroupshomeTemplateid'];
			}
			else
			{
				$templatetitle = 'Group';
				$titleQry = vB::getDbAssertor()->getRow( 'vBInstall:getPagetemplateidByTitle', ['templatetitle' => $templatetitle]);
				$defaultGroupshomeTemplateid = $titleQry['pagetemplateid'];
			}

			$this->show_message($this->phrase['version']['501a1']['setting_imported_group_pagetemplateids']);
			$startat = intval($data['startat'] ?? 0);
			$batchsize = 1000;
			// grab max from the data passed in or re-fetch it via query
			if (!empty($data['max']))
			{
					$max = $data['max'];
			}
			else
			{
				// Get the max pageid for imported social groups
				$max = vB::getDbAssertor()->getRow(	'vBInstall:getMaxPageidForOldSocialGroups', ['oldcontenttypeid' => [$oldContentTypeId]]);
				$max = $max['maxid'];
			}

			if ($startat > $max)
			{
				// we're done here
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}
			else
			{
				// update the pagetemplateid for the next batch
				vB::getDbAssertor()->assertQuery('vBInstall:updateImportedGroupsPagetemplateid', array(
					'startat' => $startat,
					'batchsize' => $batchsize,
					'oldcontenttypeid' => $oldContentTypeId,
					'changefrom' => $defaultChannelTemplateid,
					'changeto' => $defaultGroupshomeTemplateid
				));

				// start next batch
				return array(
					'startat' => ($startat + $batchsize),
					'max' => $max,
					'oldContentTypeId' => $oldContentTypeId,
					'defaultChannelTemplateid' => $defaultChannelTemplateid,
					'defaultGroupshomeTemplateid' => $defaultGroupshomeTemplateid
				);
			}
		}
		else
		{
			$this->skip_message();
		}
	}
}

class vB_Upgrade_501a2 extends vB_Upgrade_Version
{
	/*
	 * Step 1 - Move MSN account info to Skype field when possible
	 */
	public function step_1($data = NULL)
	{
		if ($data == NULL)
		{
			$this->show_message($this->phrase['version']['501a2']['moving_msn_info']);
		}

		$batchsize = 2000;
		$assertor = vB::getDbAssertor();

		$assertor->assertQuery('vBInstall:moveMsnInfo', array('batchsize' => $batchsize));

		$affectedRows = $assertor->affected_rows();

		if ($affectedRows < $batchsize )
		{
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			return;
		}
		else
		{
			$this->show_message(sprintf($this->phrase['core']['processed_records_x'], $affectedRows));

			// keep updating
			return ['startat' => 1];
		}
	}

	public function step_2()
	{
		$this->skip_message();
	}

	/*
	 * Step 3 - Update spamlog, add nodeid
	 */
	public function step_3()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'spamlog', 1, 5),
			'spamlog',
			'nodeid',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	/*
	 * Step 4 - Update spamlog.nodeid -- this will get non first posts.
	 */
	public function step_4()
	{
		if ($this->field_exists('spamlog', 'postid'))
		{
			$this->show_message(sprintf($this->phrase['core']['altering_x_table'], 'spamlog', 2, 5));
			$postTypeId = vB_Types::instance()->getContentTypeID('vBForum_Post');
			vB::getDbAssertor()->assertQuery('vBInstall:501a2_updateSpamlog1', array('posttypeid' => $postTypeId));
		}
		else
		{
			$this->skip_message();
		}
	}

	/*
	 * Step 5 - Update spamlog.nodeid -- this will get first posts, which are now saved as thread type in vB5.
	 */
	public function step_5()
	{
		if ($this->field_exists('spamlog', 'postid'))
		{
			$this->show_message(sprintf($this->phrase['core']['altering_x_table'], 'spamlog', 3, 5));
			$threadTypeId = vB_Types::instance()->getContentTypeID('vBForum_Thread');
			vB::getDbAssertor()->assertQuery('vBInstall:501a2_updateSpamlog2', array('threadtypeid' => $threadTypeId));
		}
		else
		{
			$this->skip_message();
		}
	}

	/*
	 * Step 6 - We may have some orphan logs that reference a non-existant post/thread. These logs have nodeids of 0.
	 * Remove them.
	 */
	public function step_6()
	{
		if ($this->field_exists('spamlog', 'postid'))
		{
			$this->show_message(sprintf($this->phrase['core']['altering_x_table'], 'spamlog', 4, 5));
			vB::getDbAssertor()->assertQuery('vBInstall:501a2_updateSpamlog3');
		}
		else
		{
			$this->skip_message();
		}
	}

	/*
	 * Step 7
	 */
	public function step_7()
	{
		if ($this->field_exists('spamlog', 'postid'))
		{
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'spamlog', 5, 5),
				"ALTER TABLE " . TABLE_PREFIX . "spamlog DROP PRIMARY KEY, ADD PRIMARY KEY(nodeid)",
				self::MYSQL_ERROR_DROP_KEY_COLUMN_MISSING
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/*
	 * Step 8 - Converting buddies to subscribers/subscriptions
	 */
	public function step_8()
	{
		$this->show_message($this->phrase['version']['501a2']['converting_friends']);
		vB::getDbAssertor()->assertQuery('vBInstall:convertFriends');
	}

	/**
	 * Update the infractions table schema
	 */
	public function step_9()
	{
		if ($this->field_exists('infraction', 'nodeid') AND !$this->field_exists('infraction', 'infractednodeid'))
		{
			// the node that received the infraction
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'infraction', 1, 6),
				"ALTER TABLE " . TABLE_PREFIX . "infraction CHANGE nodeid infractednodeid INT UNSIGNED NOT NULL DEFAULT '0'"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	 * Update the infractions table schema
	 */
	public function step_10()
	{
		if ($this->field_exists('infraction', 'userid') AND !$this->field_exists('infraction', 'infracteduserid'))
		{
			// the user who received the infraction
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'infraction', 2, 6),
				"ALTER TABLE " . TABLE_PREFIX . "infraction CHANGE userid infracteduserid INT UNSIGNED NOT NULL DEFAULT '0'"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	 * Update the infractions table schema
	 */
	public function step_11()
	{
		if ($this->field_exists('infraction', 'whoadded'))
		{
			// the user who gave the infraction
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'infraction', 3, 6),
				"ALTER TABLE " . TABLE_PREFIX . "infraction CHANGE whoadded userid INT UNSIGNED NOT NULL DEFAULT '0'"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	 * Update the infractions table schema
	 */
	public function step_12()
	{
		if ($this->field_exists('infraction', 'infractionid'))
		{
		// remove auto_increment from infractionid
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'infraction', 4, 6),
			"ALTER TABLE " . TABLE_PREFIX . "infraction CHANGE infractionid infractionid INT UNSIGNED NOT NULL DEFAULT '0'"
		);
	}
		else
		{
			$this->skip_message();
		}
	}

	/**
	 * Update the infractions table schema
	 */
	public function step_13()
	{
		// drop primary index from infractionid
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'infraction', 5, 6),
			"ALTER TABLE " . TABLE_PREFIX . "infraction DROP PRIMARY KEY",
			self::MYSQL_ERROR_DROP_KEY_COLUMN_MISSING
		);
	}

	/**
	 * Update the infractions table schema
	 */
	public function step_14()
	{
		// add the new nodeid column
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'infraction', 6, 6),
			'infraction',
			'nodeid',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	/**
	 * Add the infraction content type if needed
	 */
	public function step_15()
	{
		$this->add_contenttype('vbulletin', 'vBForum', 'Infraction');
	}

	/**
	 * Add the Infraction channel
	 */
	public function step_16()
	{
		//we probably don't need this anymore here.
		vB_Upgrade::createAdminSession();

		$db = vB::getDbAssertor();
		$infractionChannel = $db->getRow('vBForum:channel', array('guid' => vB_Channel::INFRACTION_CHANNEL));

		//Check if the infraction channel exists if not create it.
		$createInfractionChannel = (!$infractionChannel OR empty($infractionChannel['nodeid']));

		// If we have a legacy infractions forum, use it
		$forumId = $db->getRow('vBInstall:getUiForumId');
		if (!empty($forumId) AND $forumId['value'] > 0)
		{
			$forumTypeId =  vB_Types::instance()->getContentTypeID('vBForum_Forum');
			$oldInfractionForum = $db->getRow('vBInstall:getInfractionChannelNodeId', array(
					'oldForumTypeId' => $forumTypeId,
					'forumId' => $forumId['value']
			));

			if ($oldInfractionForum AND $oldInfractionForum['nodeid'])
			{
				if ($oldInfractionForum['guid'] != vB_Channel::INFRACTION_CHANNEL)
				{
					//delete the brand new infraction channel created inside the upgrade
					if (!empty($infractionChannel['nodeid']))
					{
						$channelLibrary = vB_Library::instance('content_channel');
						$channelLibrary->delete($infractionChannel['nodeid']);
					}

					$db->assertQuery('vBInstall:setGuidToInfractionChannel', array(
						'guidInfraction' => vB_Channel::INFRACTION_CHANNEL,
						'infractionChannelId' => $oldInfractionForum['nodeid']
					));
				}

				$createInfractionChannel = false;
			}
		}

		if ($createInfractionChannel)
		{
			$channelApi = vB_Library::instance('content_channel');
			$title = "Infractions";
			$sectionData = array(
				'title' => $title,
				'parentid' => $channelApi->fetchChannelIdByGUID(vB_Channel::DEFAULT_FORUM_PARENT),
				'htmltitle' => $title,
				'description' => $title,
				'publishdate' => vB::getRequest()->getTimeNow(),
				'userid' => 1,
				'guid' => vB_Channel::INFRACTION_CHANNEL
			);
			$channelApi->add($sectionData);
			$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'channel'));
		}
		else
		{
			$this->skip_message();
		}
	}

	/*
	 * Set infractednodeid = 0 (Fixes problems introduced in step_153 in the 500a1 upgrade script)
	 */
	public function step_17($data = null)
	{
		$batchsize = 1000;
		$assertor = vB::getDbAssertor();

		$startat = intval($data['startat'] ?? 0);
		if (!empty($data['max']))
		{
			$maxToFix = $data['max'];
		}
		else
		{
			$maxToFix = vB::getDbAssertor()->getRow('vBInstall:getMaxInfractedNodeWrong');
			$maxToFix = intval($maxToFix['maxid']);
		}

		if ($startat > $maxToFix)
		{
			//// we're done here
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			return;
		}

		else
		{
			vB::getDbAssertor()->assertQuery('vBInstall:fixInfractedNodeWrong', [
				'startat' => $startat,
				'batchsize' => $batchsize
			]);
			$this->show_message(sprintf($this->phrase['core']['processed_records_x'], $batchsize));
			return ['startat' => ($startat + $batchsize), 'max' => $maxToFix];
		}
	}

	/**
	 * Import infractions threads (infracted threads)
	 */
	public function step_18($data = NULL)
	{
		$assertor = vB::getDbAssertor();
		$batchsize = 500;
		$threadTypeId =  vB_Types::instance()->getContentTypeID('vBForum_Thread');

		$startat = intval($data['startat'] ?? 0);

		if (!empty($data['max']))
		{
			$maxToFix = $data['max'];
		}
		else
		{
			// grab the max id for imported vb3/4 blog entry/reply content types
			$maxToFix = vB::getDbAssertor()->getRow('vBInstall:getMaxThreadNodeidForInfractions', ['threadTypeId' => $threadTypeId]);
			$maxToFix = intval($maxToFix['maxid']);
		}

		if ($startat > $maxToFix)
		{
			//// we're done here
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			return;
		}
		else
		{
			vB::getDbAssertor()->assertQuery('vBInstall:importThreadsInfractions', [
				'threadTypeId' => $threadTypeId,
				'startat' => $startat,
				'batchsize' => $batchsize
			]);
			$this->show_message(sprintf($this->phrase['core']['processed_records_x'], $batchsize));
			return ['startat' => ($startat + $batchsize), 'max' => $maxToFix];
		}

	}

	/**
	 * Import infractions Post (infracted post)
	 */
	public function step_19($data = NULL)
	{
		$assertor = vB::getDbAssertor();
		$batchsize = 500;
		$postTypeId =  vB_Types::instance()->getContentTypeID('vBForum_Post');

		$startat = intval($data['startat'] ?? 0);
		if (!empty($data['max']))
		{
			$maxToFix = $data['max'];
		}
		else
		{
			// grab the max id for imported vb3/4 blog entry/reply content types
			$maxToFix = vB::getDbAssertor()->getRow('vBInstall:getMaxPostNodeidForInfractions', array('postTypeId' => $postTypeId));
			$maxToFix = intval($maxToFix['maxid']);
		}

		if ($startat > $maxToFix)
		{
			//// we're done here
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			return;
		}
		else
		{
			vB::getDbAssertor()->assertQuery('vBInstall:importPostInfractions', [
				'postTypeId' => $postTypeId,
				'startat' => $startat,
				'batchsize' => $batchsize
			]);

			$this->show_message(sprintf($this->phrase['core']['processed_records_x'], $batchsize));
			return ['startat' => ($startat + $batchsize), 'max' => $maxToFix];
		}

	}

	/*
	 * Add infraction threads to infraction channel
	 * 9976 oldcontentype for infractions on THREADS with infractions discussions
	 * infractions that has threadid > 0
	 */
	public function step_20($data = NULL)
	{
		$batchsize = 1000;

		$assertor = vB::getDbAssertor();
		$threadTypeId =  vB_Types::instance()->getContentTypeID('vBForum_Thread');
		$infractionTypeId =  vB_Types::instance()->getContentTypeID('vBForum_Infraction');
		$infractionChannel = $assertor->getRow('vBForum:channel', ['guid' => vB_Channel::INFRACTION_CHANNEL]);

		$startat = intval($data['startat'] ?? 0);

		if (!empty($data['max']))
		{
			$maxToFix = $data['max'];
		}
		else
		{
			$maxToFix = $assertor->getRow('vBInstall:getMaxThreadNodeidForInfractionChannel',  ['oldTypeId' => $threadTypeId]);
			$maxToFix = intval($maxToFix['maxid']);
		}

		if ($startat > $maxToFix)
		{
			//// we're done here
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			return;
		}
		else
		{
			$assertor->assertQuery('vBInstall:importThreadNodesToInfractionChannel', [
				'oldTypeId' => $threadTypeId,
				'infractionTypeId' => $infractionTypeId,
				//'infractionChannel' => $infractionChannel['nodeid'],
				'oldInfractionTypeId' => '9976',
				'startat' => $startat,
				'batchsize' => $batchsize
			]);

			$nodesInfo = $assertor->getRows('vBInstall:getNodeIdsToMove', [
				'oldTypeId' => '9976',
				'startat' => $startat,
				'batchsize' => $batchsize
			]);

			if (!empty($nodesInfo))
			{
				$nodeIdsArray = [];
				foreach($nodesInfo as $nodeid)
				{
					$nodeIdsArray[] = $nodeid['nodeid'];
				}

				vB_Upgrade::createAdminSession();
				vB_Api::instance('node')->moveNodes($nodeIdsArray, $infractionChannel['nodeid']);
			}

			$this->show_message(sprintf($this->phrase['core']['processed_records_x'], $batchsize));
			return ['startat' => ($startat + $batchsize), 'max' => $maxToFix];
		}
	}


	 /*
	 * Add infraction threads to infraction channel
	 * 9975 oldcontentype for on POSTS infraction threads
	 * infractions that has threadid > 0
	 */
	public function step_21($data = NULL)
	{
		$batchsize = 1000;

		$assertor = vB::getDbAssertor();
		$threadTypeId =  vB_Types::instance()->getContentTypeID('vBForum_Thread');
		$infractionTypeId =  vB_Types::instance()->getContentTypeID('vBForum_Infraction');
		$infractionChannel = $assertor->getRow('vBForum:channel', ['guid' => vB_Channel::INFRACTION_CHANNEL]);

		$startat = intval($data['startat'] ?? 0);

		if (!empty($data['max']))
		{
			$maxToFix = $data['max'];
		}
		else
		{
			$maxToFix = $assertor->getRow('vBInstall:getMaxPostNodeidForInfractionChannel',  ['oldTypeId' => $threadTypeId]);
			$maxToFix = intval($maxToFix['maxid']);
		}

		if ($startat > $maxToFix)
		{
			//// we're done here
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			return;
		}
		else
		{
			$assertor->assertQuery('vBInstall:importPostNodesToInfractionChannel', [
				'oldTypeId' => $threadTypeId,
				'infractionTypeId' => $infractionTypeId,
				//'infractionChannel' => $infractionChannel['nodeid'],
				'oldInfractionTypeId' => '9975',
				'startat' => $startat,
				'batchsize' => $batchsize
			]);

			$nodesInfo = $assertor->getRows('vBInstall:getNodeIdsToMove', [
				'oldTypeId' => '9975',
				'startat' => $startat,
				'batchsize' => $batchsize
			]);

			if (!empty($nodesInfo))
			{
				$nodeIdsArray = [];
				foreach($nodesInfo as $nodeid)
				{
					$nodeIdsArray[] = $nodeid['nodeid'];
				}

				vB_Upgrade::createAdminSession();
				vB_Api::instance('node')->moveNodes($nodeIdsArray, $infractionChannel['nodeid']);
			}

			$this->show_message(sprintf($this->phrase['core']['processed_records_x'], $batchsize));
			return ['startat' => ($startat + $batchsize), 'max' => $maxToFix];
		}
	}


	/**
	 * Add Nodes for orphan infractions (with no infraction threads on them)
	 * In this case we add the nodes for threads
	 * 9979 is now the oldcontentype for postid starter which got infracted
	 */
	public function step_22($data = NULL)
	{
		$batchsize = 1000;

		$assertor = vB::getDbAssertor();
		$threadTypeId = vB_Types::instance()->getContentTypeID('vBForum_Thread');
		$infractionTypeId =  vB_Types::instance()->getContentTypeID('vBForum_Infraction');
		$infractionChannel = $assertor->getRow('vBForum:channel', ['guid' => vB_Channel::INFRACTION_CHANNEL]);

		$startat = intval($data['startat'] ?? 0);

		if (!empty($data['max']))
		{
			$maxToFix = $data['max'];
		}
		else
		{
			$maxToFix = $assertor->getRow('vBInstall:getMaxOrphanInfraction', ['oldTypeId' => $threadTypeId]);
			$maxToFix = intval($maxToFix['maxid']);
		}
		if (!$startat)
		{
			$maxFixed = $assertor->getRow('vBInstall:getMaxFixedOrphanInfraction', array('oldTypeId' => '9979'));

			if (!empty($maxFixed) AND !empty($maxFixed['maxid']))
			{
				$startat = $maxFixed['maxid'];
			}
			else
			{
				$startat = 0;
			}
		}

		if ($startat >= $maxToFix)
		{
			//// we're done here
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			return;
		}
		else
		{
			$assertor->assertQuery('vBInstall:addNodesForOrphanInfractions', [
				'infractionTypeId' => $infractionTypeId,
				'oldInfractionTypeId' => '9979',
				'infractionChannelId' => $infractionChannel['nodeid'],
				'oldTypeId' => $threadTypeId,
				'startat' => $startat,
				'batchsize' => $batchsize
			]);

			$querydata = [
				'startat' => $startat,
				'batchsize' => $batchsize,
				'contenttypeid' => '9979'
			];

			$assertor->assertQuery('vBInstall:addClosureSelfInfraction', $querydata);
			$assertor->assertQuery('vBInstall:addClosureParentsInfraction', $querydata);
			$assertor->assertQuery('vBInstall:setPMStarter', $querydata);

			$this->show_message(sprintf($this->phrase['core']['processed_records_x'], $batchsize));
			return ['startat' => ($startat + $batchsize), 'max' => $maxToFix];
		}
	}

	/**
	 * Add Nodes for orphan infractions (with no infraction threads on them)
	 * In this case we add the nodes for post
	 * 9978 is now the oldcontentype for postid starter which got infracted
	 */
	public function step_23($data = NULL)
	{
		$batchsize = 1000;

		$assertor = vB::getDbAssertor();
		$postTypeId = vB_Types::instance()->getContentTypeID('vBForum_Post');
		$infractionTypeId =  vB_Types::instance()->getContentTypeID('vBForum_Infraction');
		$infractionChannel = $assertor->getRow('vBForum:channel', ['guid' => vB_Channel::INFRACTION_CHANNEL]);

		$startat = intval($data['startat'] ?? 0);

		if (!empty($data['max']))
		{
			$maxToFix = $data['max'];
		}
		else
		{
			$maxToFix = $assertor->getRow('vBInstall:getMaxOrphanInfraction', ['oldTypeId' => $postTypeId]);
			$maxToFix = intval($maxToFix['maxid']);
		}
		if (!$startat)
		{
			$maxFixed = $assertor->getRow('vBInstall:getMaxFixedOrphanInfraction', array('oldTypeId' => '9978'));

			if (!empty($maxFixed) AND !empty($maxFixed['maxid']))
			{
				$startat = $maxFixed['maxid'];
			}
			else
			{
				$startat = 0;
			}
		}

		if ($startat >= $maxToFix)
		{
			//// we're done here
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			return;
		}
		else
		{
			$assertor->assertQuery('vBInstall:addNodesForOrphanInfractions', [
				'infractionTypeId' => $infractionTypeId,
				'oldInfractionTypeId' => '9978',
				'infractionChannelId' => $infractionChannel['nodeid'],
				'oldTypeId' => $postTypeId,
				'startat' => $startat,
				'batchsize' => $batchsize
			]);

			$querydata = [
				'startat' => $startat,
				'batchsize' => $batchsize,
				'contenttypeid' => '9978',
			];

			$assertor->assertQuery('vBInstall:addClosureSelfInfraction', $querydata);
			$assertor->assertQuery('vBInstall:addClosureParentsInfraction', $querydata);
			$assertor->assertQuery('vBInstall:setPMStarter', $querydata);

			$this->show_message(sprintf($this->phrase['core']['processed_records_x'], $batchsize));
			return ['startat' => ($startat + $batchsize), 'max' => $maxToFix];
		}
	}

	/**
	 * Add Nodes for orphan infractions (with no infraction threads on them)
	 * In this case we add the nodes for profile
	 * 9977 is now the oldcontentype for postid=0 on infraction table (profile)
	 */
	public function step_24($data = NULL)
	{
		$batchsize = 1000;

		$assertor = vB::getDbAssertor();
		$infractionTypeId = vB_Types::instance()->getContentTypeID('vBForum_Infraction');
		$infractionChannel = $assertor->getRow('vBForum:channel', ['guid' => vB_Channel::INFRACTION_CHANNEL]);

		$startat = intval($data['startat'] ?? 0);

		if (!empty($data['max']))
		{
			$maxToFix = $data['max'];
		}
		else
		{
			$maxToFix = $assertor->getRow('vBInstall:getMaxOrphanProfileInfraction');
			$maxToFix = intval($maxToFix['maxid']);
		}
		if (!$startat)
		{
			$maxFixed = $assertor->getRow('vBInstall:getMaxFixedOrphanProfileInfraction', array('oldTypeId' => '9977'));

			if (!empty($maxFixed) AND !empty($maxFixed['maxid']))
			{
				$startat = $maxFixed['maxid'];
			}
			else
			{
				$startat = 0;
			}
		}

		if ($startat >= $maxToFix)
		{
			//// we're done here
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			return;
		}
		else
		{
			$assertor->assertQuery('vBInstall:addNodesForOrphanProfileInfractions', [
				'infractionTypeId' => $infractionTypeId,
				'oldInfractionTypeId' => '9977',
				'infractionChannelId' => $infractionChannel['nodeid'],
				'startat' => $startat,
				'batchsize' => $batchsize
			]);

			$querydata = [
				'startat' => $startat,
				'batchsize' => $batchsize,
				'contenttypeid' => '9977',
			];

			$assertor->assertQuery('vBInstall:addClosureSelfInfraction', $querydata);
			$assertor->assertQuery('vBInstall:addClosureParentsInfraction', $querydata);
			$assertor->assertQuery('vBInstall:setPMStarter', $querydata);

			$this->show_message(sprintf($this->phrase['core']['processed_records_x'], $batchsize));
			return ['startat' => ($startat + $batchsize), 'max' => $maxToFix];
		}
	}

	/**
	 * Add nodeid into the infraction table, we are adding in this steps for threads infraction only
	 */
	public function step_25($data = NULL)
	{
		$batchsize = 1000;

		$assertor = vB::getDbAssertor();
		$infractionTypeId = vB_Types::instance()->getContentTypeID('vBForum_Infraction');

		$startat = intval($data['startat'] ?? 0);

		if (!empty($data['max']))
		{
			$maxToFix = $data['max'];
		}
		else
		{
			$maxToFix = $assertor->getRow('vBInstall:getMaxNodeidIntoInfraction', [
				'oldInfractionTypeId' => ['9979','9976', '9975'],
				'infractionTypeId' => $infractionTypeId,
			]);
			$maxToFix = intval($maxToFix['maxid']);
		}

		if ($startat > $maxToFix)
		{
			//// we're done here
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			return;
		}
		else
		{
			$assertor->assertQuery('vBInstall:addNodeidIntoInfraction', [
				'oldInfractionTypeId' => ['9979','9976', '9975'],
				'infractionTypeId' => $infractionTypeId,
				'startat' => $startat,
				'batchsize' => $batchsize
			]);
			$this->show_message(sprintf($this->phrase['core']['processed_records_x'], $batchsize));
			return ['startat' => ($startat + $batchsize), 'max' => $maxToFix];
		}
	}

	/**
	 * Add nodeid into the infraction table, we are adding in this steps for Post infraction only
	 *
	 */
	public function step_26($data = NULL)
	{
		$batchsize = 1000;

		$assertor = vB::getDbAssertor();
		$infractionTypeId =  vB_Types::instance()->getContentTypeID('vBForum_Infraction');

		$startat = intval($data['startat'] ?? 0);

		if (!empty($data['max']))
		{
			$maxToFix = $data['max'];
		}
		else
		{
			$maxToFix = $assertor->getRow('vBInstall:getMaxNodeidIntoInfraction', [
				'oldInfractionTypeId' => '9978',
				'infractionTypeId' => $infractionTypeId
			]);
			$maxToFix = intval($maxToFix['maxid']);
		}

		if ($startat > $maxToFix)
		{
			//// we're done here
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			return;
		}
		else
		{
			$assertor->assertQuery('vBInstall:addNodeidIntoInfraction', [
				'oldInfractionTypeId' => '9978',
				'infractionTypeId' => $infractionTypeId,
				'startat' => $startat,
				'batchsize' => $batchsize,
			]);

			$this->show_message(sprintf($this->phrase['core']['processed_records_x'], $batchsize));
			return ['startat' => ($startat + $batchsize), 'max' => $maxToFix];
		}
	}

	/**
	 * Add nodeid into the infraction table, we are adding in this steps for Profile infraction only
	 */
	public function step_27($data = NULL)
	{
		$batchsize = 1000;

		$assertor = vB::getDbAssertor();
		$infractionTypeId =  vB_Types::instance()->getContentTypeID('vBForum_Infraction');

		$startat = intval($data['startat'] ?? 0);

		if (!empty($data['max']))
		{
			$maxToFix = $data['max'];
		}
		else
		{
			$maxToFix = $assertor->getRow('vBInstall:getMaxNodeidIntoInfraction', [
				'oldInfractionTypeId' => '9977',
				'infractionTypeId' => $infractionTypeId,
			]);
			$maxToFix = intval($maxToFix['maxid']);
		}

		if ($startat > $maxToFix)
		{
			//// we're done here
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			return;
		}
		else
		{
			$assertor->assertQuery('vBInstall:addNodeidIntoInfraction', [
				'oldInfractionTypeId' => '9977',
				'infractionTypeId' => $infractionTypeId,
				'startat' => $startat,
				'batchsize' => $batchsize,
			]);
			$this->show_message(sprintf($this->phrase['core']['processed_records_x'], $batchsize));
			return ['startat' => ($startat + $batchsize), 'max' => $maxToFix];
		}
	}

	/*
	 * Removed physically deleted posts from infraction table
	 * At this point we only have nodeid = 0 in infraction table for physically deleted posts
	 */
	public function step_28($data = NULL)
	{
		$batchsize = 1000;

		$assertor = vB::getDbAssertor();

		$startat = intval($data['startat'] ?? 0);

		$maxRecord = $assertor->getRow('vBInstall:getMaxInfractionIdPDeleted');
		$maxRecord = intval($maxRecord['maxid']);

		if ($maxRecord == 0)
		{
			//// we're done here
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			return;
		}
		else
		{
			$assertor->assertQuery('vBInstall:removedPDeletedInfractions', [
				'batchsize' => $batchsize
			]);
			$this->show_message(sprintf($this->phrase['core']['processed_records_x'], $batchsize));
			return ['startat' => ($startat + $batchsize)];
		}
	}

	/**
	 * Update the infractions table schema
	 */

	public function step_29()
	{
		// Add nodeid as primary key
		$infractiondescr = $this->db->query_first("SHOW COLUMNS FROM " . TABLE_PREFIX . "infraction LIKE 'nodeid'");

		if (!empty($infractiondescr['Key']) AND ($infractiondescr['Key'] == 'PRI'))
		{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'infraction', 1, 1),
				"ALTER TABLE " . TABLE_PREFIX . "infraction DROP PRIMARY KEY, ADD PRIMARY KEY(nodeid)",
				self::MYSQL_ERROR_DROP_KEY_COLUMN_MISSING
			);
		}
		else
		{
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'infraction', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "infraction ADD PRIMARY KEY (nodeid)"
		);
	}
	}

	/**
	 * Update the titles for infractions nodes (Threads)
	 * For starters where oldcontenttypeid = 9979, 9975, 9976
	 */
	public function step_30($data = NULL)
	{
		vB_Upgrade::createAdminSession();

		$batchsize = 1000;

		// get phrases for title & rawtext
		$assertor = vB::getDbAssertor();
		$infractionTypeId = vB_Types::instance()->getContentTypeID('vBForum_Infraction');
		$threadTypeId = vB_Types::instance()->getContentTypeID('vBForum_Thread');

		$startat = intval($data['startat'] ?? 0);

		if (!empty($data['max']))
		{
			$maxToFix = $data['max'];
		}
		else
		{
			$maxToFix = $assertor->getRow('vBInstall:getMaxInfractionWithoutNodeTitle', [
				'infractionTypeId' => $infractionTypeId,
				'oldTypeId' => ['9979','9976', '9975'],
			]);
			$maxToFix = intval($maxToFix['maxid']);
		}

		if ($startat >= $maxToFix)
		{
			//// we're done here
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			return;
		}
		else
		{
			$infractionsInfo = $assertor->getRows('vBInstall:getInfractedNodeTitles', [
				'oldTypeId' => $threadTypeId,
				'startat' => $startat,
				'batchsize' => $batchsize
			]);

			$titles = [];
			$text = [];

			foreach($infractionsInfo AS $infractionInfo)
			{
				$infractionNodeId = $infractionInfo['nodeid'];
				$phraseLevelid = 'infractionlevel' . $infractionInfo['ilevelid'] . '_title';
				$infractionLevelInfo = $this->phrase['custom']['infractionlevel'][$phraseLevelid];

				if ($infractionInfo['ipoints'] == 0)
				{
					$titlephrase = 'warning_for_x_y_in_topic_z';
				}
				else
				{
					$titlephrase = 'infraction_for_x_y_in_topic_z';
				}

				$titles[$infractionNodeId] = sprintf(
					$this->phrase['version']['501a2'][$titlephrase],
					$infractionInfo['iusername'],
					$infractionLevelInfo,
					$infractionInfo['title']
				);

				$nodeUrl = vB5_Route::buildUrl(
					$infractionInfo['routeid'] . '|fullurl',
					[
						'nodeid' => $infractionInfo['infractednodeid'],
						'title' => $infractionInfo['title'],
					],
					['p' => $infractionInfo['infractednodeid']]
				);

				//this should be handled internally to the route code but that would require more
				//specific testing as a change in behavior.
			 	$nodeUrl .=	'#post' . $infractionInfo['infractednodeid'];

				$text[$infractionNodeId] = sprintf(
					$this->phrase['version']['501a2']['infraction_topic_post'],
					// link to infracted node
					$nodeUrl,
					// infracted topic title
					$infractionInfo['title'],
					// infracted user link
					vB5_Route::buildUrl('profile|fullurl', ['userid' => $infractionInfo['iuserid']]),
					// infracted user name
					$infractionInfo['iusername'],
					// infraction title
					$titles[$infractionInfo['nodeid']],
					// infraction points
					$infractionInfo['ipoints'],
					// administrative note
					$infractionInfo['note'],
					// original post (infracted node)
					$infractionInfo['irawtext']
				);
			}

			foreach($titles AS $nodeid => $title)
			{
				$assertor->assertQuery('vBInstall:setTitleForInfractionNodes', [
					'infractionNodeTitle' => $title,
					'urlident' => vB_String::getUrlIdent($title),
					'infractionNodeId' => $nodeid,
				]);
			}

			foreach($text AS $nodeid => $nodeText)
			{
				$assertor->assertQuery('vBInstall:setTextForInfractionNodes', [
					'infractionText' => $nodeText,
					'nodeid' => $nodeid
				]);
			}

			$this->show_message(sprintf($this->phrase['core']['processed_records_x'], $batchsize));
			return ['startat' => ($startat + $batchsize), 'max' => $maxToFix];
		}
	}

	/**
	 * Update the titles for infractions nodes (Post)
	 * For replies where oldcontenttypeid = 9978
	 */
	public function step_31($data = NULL)
	{
		vB_Upgrade::createAdminSession();

		$batchsize = 1000;

		// get phrases for title & rawtext
		$assertor = vB::getDbAssertor();
		$infractionTypeId = vB_Types::instance()->getContentTypeID('vBForum_Infraction');
		$postTypeId = vB_Types::instance()->getContentTypeID('vBForum_Post');

		$startat = intval($data['startat'] ?? 0);

		if (!empty($data['max']))
		{
			$maxToFix = $data['max'];
		}
		else
		{
			$maxToFix = $assertor->getRow('vBInstall:getMaxInfractionWithoutNodeTitle', [
				'infractionTypeId' => $infractionTypeId,
				'oldTypeId' => '9978'
			]);
			$maxToFix = intval($maxToFix['maxid']);
		}

		if ($startat >= $maxToFix)
		{
			//// we're done here
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			return;
		}
		else
		{
			$infractionsInfo = $assertor->getRows('vBInstall:getInfractedForPostNodeTitles', [
				'oldTypeId' => $postTypeId,
				'startat' => $startat,
				'batchsize' => $batchsize
			]);

			$titles = [];
			$text = [];
			foreach($infractionsInfo AS $infractionInfo)
			{
				$infractionNodeId = $infractionInfo['nodeid'];
				$phraseLevelid = 'infractionlevel' . $infractionInfo['ilevelid'] . '_title';
				$infractionLevelInfo = $this->phrase['custom']['infractionlevel'][$phraseLevelid];

				if ($infractionInfo['ipoints'] == 0)
				{
					$titlephrase = 'warning_for_x_y_in_topic_z';
				}
				else
				{
					$titlephrase = 'infraction_for_x_y_in_topic_z';
				}

				$titles[$infractionNodeId] = sprintf(
					$this->phrase['version']['501a2'][$titlephrase],
					$infractionInfo['iusername'],
					$infractionLevelInfo,
					$infractionInfo['title']
				);


				$nodeUrl = vB5_Route::buildUrl(
					$infractionInfo['routeid'] . '|fullurl',
					[
						'nodeid' => $infractionInfo['infractednodeid'],
						'title' => $infractionInfo['title'],
					],
					['p' => $infractionInfo['infractednodeid']]
				);

				//this should be handled internally to the route code but that would require more
				//specific testing as a change in behavior.
			 	$nodeUrl .=	'#post' . $infractionInfo['infractednodeid'];

				$text[$infractionNodeId] = sprintf(
					$this->phrase['version']['501a2']['infraction_topic_post'],
					// link to infracted node
					$nodeUrl,
					// infracted topic title
					$infractionInfo['title'],
					// infracted user link
					vB5_Route::buildUrl('profile|fullurl', array('userid' => $infractionInfo['iuserid'])),
					// infracted user name
					$infractionInfo['iusername'],
					// infraction title
					$titles[$infractionInfo['nodeid']],
					// infraction points
					$infractionInfo['ipoints'],
					// administrative note
					$infractionInfo['note'],
					// original post (infracted node)
					$infractionInfo['irawtext']
				);
			}

			foreach($titles AS $nodeid => $title)
			{
				$assertor->assertQuery('vBInstall:setTitleForInfractionNodes', [
					'infractionNodeTitle' => $title,
					'urlident' => vB_String::getUrlIdent($title),
					'infractionNodeId' => $nodeid,
				]);
			}

			foreach($text AS $nodeid => $nodeText)
			{
				$assertor->assertQuery('vBInstall:setTextForInfractionNodes', [
					'infractionText' => $nodeText,
					'nodeid' => $nodeid,
				]);
			}

			$this->show_message(sprintf($this->phrase['core']['processed_records_x'], $batchsize));
			return ['startat' => ($startat + $batchsize), 'max' => $maxToFix];
		}
	}

	/**
	 * Update the titles for infractions nodes (Profile)
	 * For profiles where oldcontenttypeid = 9977
	 */
	public function step_32($data = NULL)
	{
		vB_Upgrade::createAdminSession();

		$batchsize = 1000;

		// get phrases for title & rawtext
		$assertor = vB::getDbAssertor();
		$infractionTypeId = vB_Types::instance()->getContentTypeID('vBForum_Infraction');

		$startat = intval($data['startat'] ?? 0);

		if (!empty($data['max']))
		{
			$maxToFix = $data['max'];
		}
		else
		{
			$maxToFix = $assertor->getRow('vBInstall:getMaxInfractionWithoutNodeTitle', [
				'infractionTypeId' => $infractionTypeId,
				'oldTypeId' => '9977',
			]);
			$maxToFix = intval($maxToFix['maxid']);
		}

		if ($startat >= $maxToFix)
		{
			//// we're done here
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			return;
		}
		else
		{
			$infractionsInfo = $assertor->getRows('vBInstall:getInfractedProfileInfo', [
				'oldTypeId' => '9977',
				'startat' => $startat,
				'batchsize' => $batchsize
			]);

			$titles = [];
			$text = [];
			foreach($infractionsInfo AS $infractionInfo)
			{
				$phraseLevelid = 'infractionlevel' . $infractionInfo['ilevelid'] . '_title';
				$infractionLevelInfo = $this->phrase['custom']['infractionlevel'][$phraseLevelid];

				if ($infractionInfo['ipoints'] == 0)
				{
					$titlephrase = 'warning_for_x_y';
				}
				else
				{
					$titlephrase = 'infraction_for_x_y';
				}

				$titles[$infractionInfo['nodeid']] = sprintf(
					$this->phrase['version']['501a2'][$titlephrase],
					$infractionInfo['iusername'],
					$infractionLevelInfo
				);

				$text[$infractionInfo['nodeid']] =sprintf(
					$this->phrase['version']['501a2']['infraction_topic_profile'],
					// infracted user link
					vB5_Route::buildUrl('profile|fullurl', ['userid' => $infractionInfo['iuserid']]),
					// infracted user name
					$infractionInfo['iusername'],
					// infraction title
					$infractionLevelInfo,
					// infraction points
					$infractionInfo['ipoints'],
					// administrative note
					$infractionInfo['note']
				);
			}

			foreach($titles AS $nodeid => $title)
			{
				$assertor->assertQuery('vBInstall:setTitleForInfractionNodes', [
					'infractionNodeTitle' => $title,
					'urlident' => vB_String::getUrlIdent($title),
					'infractionNodeId' => $nodeid
				]);
			}

			foreach($text AS $nodeid => $nodeText)
			{
				$assertor->assertQuery('vBInstall:setTextForInfractionNodes', [
					'infractionText' => $nodeText,
					'nodeid' => $nodeid
				]);
			}

			$this->show_message(sprintf($this->phrase['core']['processed_records_x'], $batchsize));
			return ['startat' => ($startat + $batchsize), 'max' => $maxToFix];
		}
	}

	//this step has been moved to 502a1
	public function step_33()
	{
		$this->skip_message();
	}

	/*
	 * Removed the channelid field
	 */
	public function step_34()
	{
		if ($this->field_exists('infraction', 'channelid'))
			{
				$this->drop_field(
					sprintf($this->phrase['core']['altering_x_table'], 'infraction', 1, 1),
					'infraction',
					'channelid'
				);
			}
			else
			{
				$this->skip_message();
			}
	}

	public function step_35()
	{
		$db = vB::getDbAssertor();

		$infractionChannel = $db->getRow('vBForum:channel', ['guid' => vB_Channel::INFRACTION_CHANNEL]);
		$routeInfo = $db->getRow('routenew', [
			'class' => 'vB5_Route_Conversation',
			'contentid' => $infractionChannel['nodeid'],
		]);

		$db->assertQuery('vBInstall:setInfractionConversationRouteId', [
			'infractionRouteId' => $routeInfo['routeid'],
			'infractionNodeId' => $infractionChannel['nodeid'],
		]);
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'node'));
	}

	public function step_36()
	{
		$db = vB::getDbAssertor();
		$infractionChannel = $db->getRow('vBForum:channel', ['guid' => vB_Channel::INFRACTION_CHANNEL]);
		$textCount = $db->getRow('vBInstall:totalStarters');

		$db->assertQuery('vBInstall:setTextCountForInfractionChannel', [
			'textCount' => $textCount['totalCount'],
			'infractionNodeid' => $infractionChannel['nodeid']
		]);

		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'node'));
	}

	public function step_37()
	{
		$db = vB::getDbAssertor();
		$infractionChannel = $db->getRow('vBForum:channel', ['guid' => vB_Channel::INFRACTION_CHANNEL]);
		$channelTypeid = vB_Types::instance()->getContentTypeID('vBForum_Channel');

		$db->assertQuery('vBAdmincp:updateChannelCounts', [
			'nodeids' => [$infractionChannel['nodeid']],
			'channelTypeid' => $channelTypeid,
		]);
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'node'));
	}

	/**
	 * Update the denormalized values for text.infraction that
	 * show if the node has been infracted or warned.
	 */
	public function step_38($data = null)
	{
		$batchsize = 500;

		$assertor = vB::getDbAssertor();

		$startat = intval($data['startat'] ?? 0);

		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'text'));

		$infractions = $assertor->getRows('vBforum:infraction', [
			vB_dB_Query::CONDITIONS_KEY => [
				'action' => 0,
				[
					'field' => 'infractednodeid',
					'value' => 0,
					'operator' => vB_dB_Query::OPERATOR_GT,
				],
			],
			vB_dB_Query::PARAM_LIMITSTART => $startat,
			vB_dB_Query::PARAM_LIMIT => $batchsize,
		]);

		if (!$infractions)
		{
			// done
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			return;
		}
		else
		{
			foreach ($infractions AS $infraction)
			{
				// 1=infraction, 2=warning
				$value = $infraction['points'] > 0 ? 1 : 2;
				$assertor->update('vBforum:text', ['infraction' => $value], ['nodeid' => $infraction['infractednodeid']]);
			}

			$this->show_message(sprintf($this->phrase['core']['processed_records_x'], $batchsize));
			return ['startat' => ($startat + $batchsize)];
		}
	}
}

class vB_Upgrade_501rc1 extends vB_Upgrade_Version
{
	/**
	 * Remove invalid profile routes
	 *
	 */
	public function step_1()
	{
		$this->show_message(sprintf($this->phrase['core']['altering_x_table'], 'routenew', 1, 1));

		// we changed class to vB5_Route_Content at some point in upgrade so let's get rid of them.
		vB::getDbAssertor()->assertQuery('routenew', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_DELETE,
			vB_dB_Query::CONDITIONS_KEY => array(
				array('field' => 'class', 'value' => 'vB5_Route_Content', 'operator' => vB_dB_Query::OPERATOR_EQ),
				array('field' => 'name', 'value' => array('profile', 'following', 'followers', 'groups', 'settings'), 'operator' => vB_dB_Query::OPERATOR_EQ)
			)
		));

		// we fix settings route class during the upgrade but we have a wrong regex for the duplicate.
		vB::getDbAssertor()->assertQuery('routenew', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_DELETE,
			vB_dB_Query::CONDITIONS_KEY => array(
				array('field' => 'name', 'value' => 'settings', 'operator' => vB_dB_Query::OPERATOR_EQ),
				array('field' => 'regex', 'value' => 'settings', 'operator' => vB_dB_Query::OPERATOR_EQ)
			)
		));
	}
}

class vB_Upgrade_502a1 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->skip_message();
	}

	//Now attachments from blogs
	public function step_2($data = [])
	{
		if (
			isset($this->registry->products['vbblog']) AND $this->registry->products['vbblog']
			AND $this->tableExists('attachment') AND $this->tableExists('filedata')
		)
		{
			$assertor = vB::getDbAssertor();

			$process = 5000;
			$startat = intval($data['startat'] ?? 0);
			$attachTypeId =  vB_Types::instance()->getContentTypeID('vBForum_Attach');
			$blogEntryTypeId =  $assertor->getField('vBForum:contenttype', array(
				vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
				vB_dB_Query::COLUMNS_KEY => array('contenttypeid'),
				vB_dB_Query::CONDITIONS_KEY => array('class' => 'BlogEntry')
			));

			//First see if we need to do something. Maybe we're O.K.
			$maxvB4 = $assertor->getField('vBInstall:getMaxImportedAttachment', array('contentTypeId' => $blogEntryTypeId));

			//If we don't have any attachments, we're done.
			if (intval($maxvB4) < 1)
			{
				$this->skip_message();
				return;
			}

			$maxvB5 = $assertor->getField('vBInstall:getMaxImportedPost', array('contenttypeid' => vB_Api_ContentType::OLDTYPE_BLOGATTACHMENT));
			if (empty($maxvB5))
			{
				$maxvB5 = 0;
			}

			if (($maxvB4 <= $maxvB5) AND !$startat)
			{
				$this->skip_message();
				return;
			}
			else if ( ($maxvB4 <= $maxvB5) OR ($maxvB4 < $startat) )
			{
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}
			$maxvB5 = max($maxvB5, $startat);

			/*** first the nodes ***/
			$assertor->assertQuery('vBInstall:insertBlogAttachmentNodes', array(
				'attachTypeId' => $attachTypeId,
				'oldContentTypeId' => vB_Api_ContentType::OLDTYPE_BLOGATTACHMENT,
				'blogStarterOldContentTypeId' => vB_Api_ContentType::OLDTYPE_BLOGSTARTER_PRE502a2,
				'batchSize' => $process,
				'startAt' => $maxvB5,
				'blogEntryTypeId' => $blogEntryTypeId,
			));

			//Now populate the attach table
			$assertor->assertQuery('vBInstall:insertBlogAttachments', array(
				'oldContentTypeId' => vB_Api_ContentType::OLDTYPE_BLOGATTACHMENT,
				'batchSize' => $process,
				'startAt' => $maxvB5,
				'blogEntryTypeId' => $blogEntryTypeId,
			));

			//Now the closure record for the node
			$assertor->assertQuery('vBInstall:addClosureSelf', array(
				'contenttypeid' => vB_Api_ContentType::OLDTYPE_BLOGATTACHMENT,
				'startat' => $maxvB5,
				'batchsize' => $process,
			));

			//Add the closure records to root
			$assertor->assertQuery('vBInstall:addClosureParents', array(
				'contenttypeid' => vB_Api_ContentType::OLDTYPE_BLOGATTACHMENT,
				'startat' => $maxvB5,
				'batchsize' => $process,
			));

			$this->show_message(sprintf($this->phrase['core']['processed_records_x'], $process));

			return array('startat' => ($maxvB5 + $process - 1));
		}
		else
		{
			$this->skip_message();
		}
	}
	/**
	 * Remove/Rename FAQ link from footer navigation items
	 * Add Help link to the footer
	 */
	public function step_3()
	{
		$footernavbar = $this->db->query_first("
				SELECT footernavbar FROM " . TABLE_PREFIX . "site
				WHERE siteid = 1");

		$footernavbar_array = array();
		if (!empty($footernavbar['footernavbar']))
		{
			$footernavbar_array = unserialize($footernavbar['footernavbar']);
			$found_help = false;
			$found_faq = false;
			foreach ($footernavbar_array as $index => $footernavbar_item)
			{
				if ($footernavbar_item['url'] == 'help')
				{
					$found_help = true;
				}
				if ($footernavbar_item['url'] == 'faq')
				{
					$found_faq = true;
					unset($footernavbar_array[$index]);
				}
			}
			// add help link if it is not there
			if (!$found_help)
			{
				$this->show_message($this->phrase['version']['501a1']['adding_help_to_footer']);
				array_unshift($footernavbar_array, array(
						'title' => 'navbar_help',
						'url' => 'help',
						'newWindow' => 0
				));
				$this->run_query(
						sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'site'),
						"
						UPDATE " . TABLE_PREFIX . "site
						SET footernavbar = '" . serialize($footernavbar_array) . "'
						WHERE
						siteid = 1
						"
				);
			}
			elseif ($found_faq)
			{
				$this->show_message($this->phrase['version']['501a1']['remove_footer_link']);
				//already removed the faq from the list above
				$this->run_query(
						sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'site'),
						"
						UPDATE " . TABLE_PREFIX . "site
						SET footernavbar = '" . serialize($footernavbar_array) . "'
						WHERE
						siteid = 1
						"
				);
			}
			else
			{
				$this->skip_message();
			}
		}
	}
}

class vB_Upgrade_502a2 extends vB_Upgrade_Version
{
	/*
	 *	Step 1
	 *	Correct oldid for imported blog channels.
	 *	This step does not delete the "false" blog
	 *	channels (blog entries that were imported
	 *	as channels instead in 500a22 step 1).
	 */
	public function step_1($data = [])
	{
		// check if blogs were imported. If so, there should be a blog_user table
		if ($this->tableExists('blog_user') AND $this->tableExists('node'))
		{
			$this->show_message($this->phrase['version']['502a2']['updating_oldid_for_imported_blogs']);
			$startat = intval($data['startat'] ?? 0);
			$batchsize = 20000;
			// $blog['oldcontenttypeid'] = '9999'; 	 from class_upgrade_500a22 step1
			$oldContetypeid_blogChannel = vB_Api_ContentType::OLDTYPE_BLOGCHANNEL_PRE502a2;

			if (!empty($data['max']))
			{
					$max = $data['max'];
			}
			else
			{
				// grab the max id for imported vb3/4 blog channel
				$max = vB::getDbAssertor()->getRow(	'vBInstall:getMaxNodeidForOldContent',
											array(	'oldcontenttypeid' => array($oldContetypeid_blogChannel))
										);
				$max = $max['maxid'];
			}

			if ($startat > $max)
			{
				// we're done here
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}
			else
			{

				// blog_user.bloguserid is the userid, and what oldid should be set to
				// blog.userid should be equivalent to bloguserid, and since it's easier to map
				// the imported node row to the row in the blog table, let's just use blog.userid
				vB::getDbAssertor()->assertQuery('vBInstall:updateImportedBlogChannelOldid', array(
					'startat' => $startat,
					'batchsize' => $batchsize,
					'oldcontenttypeid' => $oldContetypeid_blogChannel,
					'oldcontenttypeid_new' => vB_Api_ContentType::OLDTYPE_BLOGCHANNEL
				));

				// start next batch
				return array('startat' => ($startat + $batchsize), 'max' => $max);
			}
		}
		else
		{
			$this->skip_message();
		}

	}

	public function step_2()
	{
		$this->skip_message();
	}


	/*	Step 3
	 *	Change oldid for imported blog entries.
	 * 	class_ugprade_500a22 steps 2 & 3 set oldid for blog starters & replies
	 * 	to be blog_text.blogtextid. With the legacy entry.php rerouting, the
	 * 	oldid's for blog entries have to be blog.blogid
	*/
	public function step_3($data = [])
	{
		// check if blogs were imported. If so, there should be a blog_user table
		if ($this->tableExists('blog_user') AND $this->tableExists('node'))
		{
			$this->show_message($this->phrase['version']['502a2']['updating_oldid_for_imported_blog_entries']);
			$startat = intval($data['startat'] ?? 0);
			$batchsize = 50000;
			// blog starters: 9985, blog responses:9984 	 from class_upgrade_500a22 steps 2 & 3
			$oldContetypeid_blogStarter = vB_Api_ContentType::OLDTYPE_BLOGSTARTER_PRE502a2;

			if (!empty($data['max']))
			{
					$max = $data['max'];
			}
			else
			{
				// grab the max id for imported vb3/4 blog channel
				$max = vB::getDbAssertor()->getRow(	'vBInstall:getMaxNodeidForOldContent',
											array(	'oldcontenttypeid' => array($oldContetypeid_blogStarter))
										);
				$max = $max['maxid'];
			}

			if ($startat > $max)
			{
				// we're done here
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}
			else
			{
				// first, the blog starters
				vB::getDbAssertor()->assertQuery('vBInstall:updateImportedBlogEntryOldid', array(
					'startat' => $startat,
					'batchsize' => $batchsize,
					'oldcontenttypeid' => $oldContetypeid_blogStarter,
					'oldcontenttypeid_new' => vB_Api_ContentType::OLDTYPE_BLOGSTARTER
				));

				// blog responses should be left alone, since they don't go through entry.php the same way.

				// start next batch
				return array('startat' => ($startat + $batchsize), 'max' => $max);
			}
		}
		else
		{
			$this->skip_message();
		}

	}

	/**
	 * Step 4-5: Update paid subscriptions related data
	 */
	public function step_4()
	{
		if (!$this->field_exists('paymentapi', 'subsettings'))
		{
			// Create nodeid field
			$this->add_field(
				sprintf($this->phrase['core']['altering_x_table'], 'paymentapi', 1, 1),
				'paymentapi',
				'subsettings',
				'mediumtext',
				self::FIELD_DEFAULTS
			);
		}
		else
		{
			$this->skip_message();
		}

		if (!$this->field_exists('subscription', 'newoptions'))
		{
			// Create nodeid field
			$this->add_field(
				sprintf($this->phrase['core']['altering_x_table'], 'subscription', 1, 1),
				'subscription',
				'newoptions',
				'mediumtext',
				self::FIELD_DEFAULTS
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_5()
	{
		$this->show_message($this->phrase['version']['502a2']['converting_subscription_options']);
		$processed = false;

		// Need to go through old subscriptions and convert options to newoptions
		$subscriptions = $this->db->query_read("
			SELECT *
			FROM " . TABLE_PREFIX . "subscription
			ORDER BY subscriptionid
		");

		$_SUBSCRIPTIONOPTIONS = array(
			'tax'       => 1,
			'shipping1' => 2,
			'shipping2' => 4,
		);

		while ($sub = $this->db->fetch_array($subscriptions))
		{
			if (empty($sub['newoptions']))
			{
				$processed = true;
				$oldoptions = array_merge($sub, convert_bits_to_array($sub['options'], $_SUBSCRIPTIONOPTIONS));
				$shipping_address = ($sub['options'] & $_SUBSCRIPTIONOPTIONS['shipping1']) + ($sub['options'] & $_SUBSCRIPTIONOPTIONS['shipping2']);
				switch ($shipping_address)
				{
					case 0:
						$shipping_address = 'none';
						break;

					case 2:
						$shipping_address = 'optional';
						break;

					case 3:
						$shipping_address = 'required';
						break;
				}

				$newoption['api']['paypal'] = array(
					'show' => '1',
					'tax' => $oldoptions['tax'],
					'shipping_address' => $shipping_address,
				);

				$this->db->query_write("
					UPDATE " . TABLE_PREFIX . "subscription
					SET newoptions = '" . $this->db->escape_string(serialize($newoption)) . "'
					WHERE subscriptionid = $sub[subscriptionid]
				");

			}
		}

		// Insert subsettings field for paymentapi
		$paymentapis = $this->db->query_read("
			SELECT *
			FROM " . TABLE_PREFIX . "paymentapi
			ORDER BY paymentapiid
		");

		while ($api = $this->db->fetch_array($paymentapis))
		{
			if (empty($api['subsettings']))
			{
				$processed = true;
				$setting = array();
				switch ($api['classname'])
				{
					case 'paypal':
						$setting = array(
							'show' => array(
								'type' => 'yesno',
								'value' => 1,
								'validate' => 'boolean'
							),
							'tax' => array(
								'type' => 'yesno',
								'value' => 0,
								'validate' => 'boolean'
							),
							'shipping_address' => array(
								'type' => 'select',
								'options' => array(
									'none',
									'optional',
									'required',
								),
								'value' => 'none',
								'validate' => 'boolean'
							),
						);
						break;

					default:
						$setting = array(
							'show' => array(
								'type' => 'yesno',
								'value' => 1,
								'validate' => 'boolean'
							),
						);
				}

				$this->db->query_write("
					UPDATE " . TABLE_PREFIX . "paymentapi
					SET subsettings = '" . $this->db->escape_string(serialize($setting)) . "'
					WHERE paymentapiid = $api[paymentapiid]
				");

			}
		}

		if (!$processed)
		{
			$this->skip_message();
		}
	}

	/**
	 * Step 6 - Drop old autosave table if it exists (from vB4)
	 */
	public function step_6()
	{
		if ($this->tableExists('autosave'))
		{
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'autosave', 1, 1),
				"DROP TABLE IF EXISTS " . TABLE_PREFIX . "autosave"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	 * Step 7 - Add new autosave table
	 */
	public function step_7()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . "autosavetext"),
			"CREATE TABLE " . TABLE_PREFIX . "autosavetext (
				parentid INT UNSIGNED NOT NULL DEFAULT '0',
				nodeid INT UNSIGNED NOT NULL DEFAULT '0',
				userid INT UNSIGNED NOT NULL DEFAULT '0',
				pagetext MEDIUMTEXT,
				dateline INT UNSIGNED NOT NULL DEFAULT '0',
				PRIMARY KEY (nodeid, parentid, userid),
				KEY userid (userid),
				KEY parentid (parentid, userid),
				KEY dateline (dateline)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}
}

class vB_Upgrade_502b1 extends vB_Upgrade_Version
{
	/**
	 * Step 1 - Add Google Checkout
	 */
	public function step_1()
	{
		// This step used to add google checkout paymentapi (paymentapi.classname = 'google'), but google checkout has long since been
		// discontinued and we've removed it. Skipping this.
		$this->skip_message();
	}

	/**
	* Remove any volatile phrases
	*/
	public function step_2()
	{
		$assertor = vB::getDbAssertor();
		$assertor->delete('vBForum:faq', array('volatile' => 1));

		//if any non-volatile faq names have been left orphaned by the delete, promote them to top level.  This will
		//likely require the admin to do some cleanup, but it means his articles won't randomly disappear.  Any structure
		//between nonvolatile articles will be preserved.
		$faq = $assertor->getColumn('vBForum:faq', 'faqname');
		if (count($faq))
		{
			$assertor->update('vBForum:faq', array('faqparent' => 'faqroot'),
				array(array('field' => 'faqparent', 'value' => $faq, 'operator' => vB_dB_Query::OPERATOR_NE))
			);
		}
		$this->show_message($this->phrase['version']['502b1']['removing_faq_entries']);
	}

	public function step_3()
	{
		$schema = $this->load_schema();

		// insert the updated FAQ Structure
		$this->run_query (
			$this->phrase['version']['502b1']['updating_faq_entries'],
			$schema['INSERT']['query']['faq']
		);
	}
}

class vB_Upgrade_502rc1 extends vB_Upgrade_Version
{
		/*
	 *	Step 1 - Find and import the missing albums VBV-8952
	 *	This is similar to step_17 of class_upgrade_500a28,
	 *	and imports the albums that the step missed.
	 *	The closure records that the mentioned step left out
	 *	should be added by step 3
	*/
	public function step_1($data = [])
	{
		if ($this->tableExists('album') AND $this->tableExists('attachment')  AND $this->tableExists('filedata'))
		{
			// output what we're doing
			$this->show_message($this->phrase['version']['502rc1']['importing_missing_albums']);

			$assertor = vB::getDbAssertor();
			$batchSize = 1000;
			$startat = intval($data['startat'] ?? 0);
			$albumTypeid = vB_Types::instance()->getContentTypeID('vBForum_Album');

			/*
			 * 	The batching for this is a bit different.
			 *	It will essentially import $batchSize missing albums at a time
			 *	Until vBInstall:getMissingAlbums does not return any more.
			 *	If it never stops, that probably means something went wrong in the
			 * 	importing &	getMissingAlbums keeps pulling results
			 *
			 *	Update note: VBV-9795, sometimes users can be deleted but their
			 *	albums can persist due to VBIV-10754. To prevent this issue from
			 *	causing the upgrader to run this step forever, only missing albums
			 *	with an existing user record will be fetched, via INNER JOIN
			 */
			$missingAlbums = $assertor->assertQuery('vBInstall:getMissingAlbums',
				array(
					'oldcontenttypeid' => $albumTypeid,
					'batchsize' => $batchSize,
				)
			);

			// if there are no more albums missing, then we are done.
			if (!$missingAlbums->valid())
			{
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}

			// otherwise, let's grab the albumid's from the query
			$albumIdList = array();
			foreach ($missingAlbums AS $albumRow)
			{
				$albumIdList[] = $albumRow['albumid'];
			}

			// we need the routeid for the album channel
			$albumChannel = vB_Library::instance('node')->fetchAlbumChannel();
			$album = $assertor->getRow('vBForum:node', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
				'nodeid' => $albumChannel));
			$route = $assertor->getRow('routenew',
				array(
					'contentid' => $albumChannel,
					'class' => 'vB5_Route_Conversation',
				)
			);

			// import the albums into the node table as galleries.
			$assertor->assertQuery('vBInstall:importMissingAlbumNodes',
				array(
					'oldcontenttypeid' => $albumTypeid,
					'albumIdList' => $albumIdList,
					'gallerytypeid' => vB_Types::instance()->getContentTypeID('vBForum_Gallery'),
					'albumChannel' => $albumChannel,
					'routeid' => $route['routeid']
				)
			);
			// Set starter = nodeid for the galleries.
			$assertor->assertQuery('vBInstall:setStarterForImportedAlbums',
				array(
					'oldcontenttypeid' => $albumTypeid,
					'albumIdList' => $albumIdList
				)
			);

			// import text records
			$assertor->assertQuery('vBInstall:addMissingTextAlbumRecords_502rc1',
				array(
					'oldcontenttypeid' => $albumTypeid,
					'albumIdList' => $albumIdList
				)
			);

			// import albums into gallery table
			$assertor->assertQuery('vBInstall:importMissingAlbums2Gallery',
				array(
					'oldcontenttypeid' => $albumTypeid,
					'albumIdList' => $albumIdList,
				)
			);

			// adding closure records will come in a future step.


			// output current progress
			$this->show_message(sprintf($this->phrase['core']['processed_records_x'], count($albumIdList)));

			// return something so it'll kick off the next batch, even though this batching doesn't use startat
			return array('startat' => 1);
		}
		else
		{
			$this->skip_message();
		}
	}

	/*
	 *	Step 2 - Find and import the missing photos VBV-8952
	 *	This is similar to step_18 of class_upgrade_500a28,
	 * 	and imports the photos that the step missed.
	 *	The closure records that the mentioned step left out
	 *	should be added by step 3
	*/
	public function step_2($data = [])
	{
		if ($this->tableExists('album') AND $this->tableExists('attachment')  AND $this->tableExists('filedata'))
		{
			// output what we're doing
			$this->show_message($this->phrase['version']['502rc1']['importing_missing_photos']);

			$assertor = vB::getDbAssertor();
			$batchSize = 1000;
			$startat = intval($data['startat'] ?? 0);
			$photoTypeid = vB_Types::instance()->getContentTypeID('vBForum_Photo');
			$albumTypeid = vB_Types::instance()->getContentTypeID('vBForum_Album');


			/*
			 * 	The batching for this is like in step 1.
			 *
			 *	Update note: VBV-9795, see comment above getMissingAlbums.
			 *	Only missing albums with an existing user record will be
			 * 	fetched, via INNER JOIN
			 */
			$missingPhotos = $assertor->assertQuery('vBInstall:getMissingPhotos',
				array(
					'oldcontenttypeid' => vB_Api_ContentType::OLDTYPE_PHOTO,
					'batchsize' => $batchSize,
					'albumtypeid' => $albumTypeid
				)
			);

			// if there are no more photos missing, then we are done.
			if (!$missingPhotos->valid())
			{
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}

			// otherwise, let's grab the attachmentid's from the query
			$attachmentIdList = array();
			foreach ($missingPhotos AS $photoRow)
			{
				$attachmentIdList[] = $photoRow['attachmentid'];
			}

			// import them into the node table.
			// starter = parentid will be set with the query (in reference to 500b28 step_8 / VBV-7108)
			$assertor->assertQuery('vBInstall:importMissingPhotoNodes',
				array(
					'phototypeid' => $photoTypeid,
					'albumtypeid' => $albumTypeid,
					'attachmentIdList' => $attachmentIdList
				)
			);

			// import photos into photo table
			$assertor->assertQuery('vBInstall:importMissingPhotos2Photo',
				array(
					'attachmentIdList' => $attachmentIdList
				)
			);


			// output current progress
			$this->show_message(sprintf($this->phrase['core']['processed_records_x'], count($attachmentIdList)));

			// return something so it'll kick off the next batch, even though this batching doesn't use startat
			return array('startat' => 1);
		}
		else
		{
			$this->skip_message();
		}
	}



	/*
	 * 	Step 3 - Add missing closure records VBV-8952 / VBV-8975
	 * 	This step is inefficient. It walks through the entire node table
	 *	and finds nodes with missing closure records via
	 *	getNoclosureNodes & getOrphanNodes.
	 *	Since it's rare that either query actually returns anything,
	 *	this step will probably do quite a few iterations doing nothing
	 *	until a node with missing closure records is found.
	*/
	public function step_3($data = [])
	{
		if ($this->tableExists('node') AND $this->tableExists('closure'))
		{
			$batchSize = 100000;

			$assertor = vB::getDbAssertor();
			$albumTypeid = vB_Types::instance()->getContentTypeID('vBForum_Album');

			$startat = intval($data['startat'] ?? 0);

			// get max nodeid
			if (!empty($data['maxNodeid']))
			{
				$maxNodeid = intval($data['maxNodeid']);
			}
			else
			{
				$maxNodeid = $assertor->getRow('vBInstall:getMaxNodeid', array());
				$maxNodeid = intval($maxNodeid['maxid']);
			}

			//If we don't have any nodes, we do nothing.
			if ($maxNodeid < 1)
			{
				$this->skip_message();
				return;
			}

			// finish condition
			if ($maxNodeid <= $startat)
			{
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}


			// output what we're doing
			$this->show_message($this->phrase['version']['502rc1']['adding_missing_closure']);


			// grab the nodeid's for the nodes missing closure records
			$nodesMissingSelfClosures = $assertor->assertQuery('vBInstall:getNoclosureNodes',
				array(
					'oldcontenttypeid' => $albumTypeid,
					'startat' => $startat,
					'batchsize' => $batchSize,
				)
			);
			// some nodes might have self closure but no parent closure.
			$nodesMissingParentClosures = $assertor->assertQuery('vBInstall:getOrphanNodes',
				array(
					'oldcontenttypeid' => $albumTypeid,
					'startat' => $startat,
					'batchsize' => $batchSize,
				)
			);

			// If there were no nodes missing closure in this batch, quickly move on to the next batch
			if ( !($nodesMissingSelfClosures->valid() OR $nodesMissingParentClosures->valid()) )
			{
				// output current progress
				$this->show_message(sprintf($this->phrase['core']['processed_records_x_y_z'], $startat + 1, min($startat + $batchSize, $maxNodeid), $maxNodeid));
				// kick off the next batch
				return array('startat' => ($startat + $batchSize), 'maxNodeid' => $maxNodeid);
			}

			// otherwise, let's grab the nodeid's from the query
			$nodeIdList_needself = array();
			foreach ($nodesMissingSelfClosures AS $nodeRow)
			{
				$nodeIdList_needself[] = $nodeRow['nodeid'];
			}

			$nodeIdList_needparents = array();
			foreach ($nodesMissingParentClosures AS $nodeRow)
			{
				$nodeIdList_needparents[] = $nodeRow['nodeid'];
			}


			// add closure records
			if (!empty($nodeIdList_needself))
			{
				$assertor->assertQuery('vBInstall:addMissingClosureSelf',
					array(
						'nodeIdList' => $nodeIdList_needself
					)
				);
			}

			if (!empty($nodeIdList_needparents))
			{
				$assertor->assertQuery('vBInstall:addMissingClosureParents',
					array(
						'nodeIdList' => $nodeIdList_needparents
					)
				);
			}


			// output current progress
			$this->show_message(sprintf($this->phrase['core']['processed_records_x_y_z'], $startat + 1, $startat + $batchSize, $maxNodeid));
			// kick off the next batch
			return array('startat' => ($startat + $batchSize), 'maxNodeid' => $maxNodeid);
		}
		else
		{
			$this->skip_message();
		}
	}


	/** This adds the new candeletechannesl permission for channel owners  */
	public function step_4()
	{
		$this->show_message($this->phrase['version']['502rc1']['adding_owner_candelete_permission']);
		vB::getDbAssertor()->assertQuery('vBInstall:grantOwnerForumPerm', array('permission' => 256, 'systemgroupid' => 9));
	}
}

class vB_Upgrade_503 extends vB_Upgrade_Version
{

	/**
	 *		Step 1, add the /page regex & pagenum argument to channel routes
	 */
	public function step_1()
	{
		// output what we're doing
		$this->show_message($this->phrase['version']['503']['updating_channel_regex']);
		$assertor = vB::getDbAssertor();

		// guid vbulletin-4ecbdacd6a4ad0.58738735 is the home channel, which doesn't
		// have a regex, apparently. Only grab the other channels.
		// the query grabs all vB5_Route_Channel routes without %\(\?\:/page% (unescaped: "(?:/page"  ) in the regex
		$brokenChannels = $assertor->assertQuery('vBInstall:getChannelsMissingPageRegex', array());

		// if the query found no channels missing /page in the regex, we're done
		if (!$brokenChannels->valid())
		{
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			return;
		}

		$totalCount = iterator_count($brokenChannels);
		$i = 0;

		foreach($brokenChannels AS $channel)
		{
			// regex definition taken from vB5_Route_Channel::validInput()
			//preg_quote($data['prefix']) . '(?:/page(?P<pagenum>[0-9]+))?';
			$newregex = preg_quote($channel['prefix']) . '(?:/page(?P<pagenum>[0-9]+))?';
			$arguments = unserialize($channel['arguments']);
			$arguments['pagenum'] = '$pagenum';

			// update each channel one at a time. Not the fastest to do, but fastest to code.
			$this->db->query_write(
				"UPDATE " . TABLE_PREFIX . "routenew
					SET regex = '" . $newregex . "', arguments = '" . serialize($arguments) . "'
					WHERE routeid = " . $channel['routeid'] . "
				;"
			);

				// output progress
			if ( ((++$i)%100) === 0 OR ($i >= $totalCount) )
			{
				$this->show_message(sprintf($this->phrase['core']['processed_records_x_y_z'],
					max($i - 99, 0), $i, $totalCount));
			}
		}

		$this->show_message(sprintf($this->phrase['core']['process_done']));
	}
}

class vB_Upgrade_503a1 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->skip_message();
	}
}

class vB_Upgrade_503a2 extends vB_Upgrade_Version
{
	/*	Step 1
	 *	Revert changes to blog responses caused by a now-removed part of step_3 502a2
	 */
	public function step_1($data = [])
	{
		// check if blogs were imported. If so, there should be a blog_text table
		if ($this->tableExists('blog_text') AND $this->tableExists('node'))
		{
			$this->show_message($this->phrase['version']['503a2']['reverting_oldid_for_imported_blog_responses']);
			$startat = intval($data['startat'] ?? 0);
			$batchsize = 1000;
			// oldcontenttypeid for the imported nodes we need to fix
			$oldContetypeid_blogResponse = vB_Api_ContentType::OLDTYPE_BLOGRESPONSE_502a2;

			if (!empty($data['max']))
			{
					$max = $data['max'];
			}
			else
			{
				// grab the max id for imported vb3/4 blog response
				$max = vB::getDbAssertor()->getRow(	'vBInstall:getMaxNodeidForOldContent',
											array(	'oldcontenttypeid' => array($oldContetypeid_blogResponse))
										);
				$max = $max['maxid'];
			}

			if ($startat >= $max)
			{
				// we're done here
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}
			else
			{
				// node.created should match blog_text.dateline.
				// This relies on the high likelihood that no two blog responses to the same blog
				// will have the same dateline.
				vB::getDbAssertor()->assertQuery('vBInstall:revertImportedBlogResponseOldid', array(
					'startat' => $startat,
					'batchsize' => $batchsize,
					'oldcontenttypeid' => $oldContetypeid_blogResponse,
					'oldcontenttypeid_new' => vB_Api_ContentType::OLDTYPE_BLOGRESPONSE
				));

				// output progress
				$this->show_message(sprintf($this->phrase['core']['processed_records_x_y'], $startat + 1, $startat + $batchsize));
				// start next batch
				return array('startat' => ($startat + $batchsize), 'max' => $max);
			}
		}
		else
		{
			$this->skip_message();
		}

	}

	public function step_2()
	{
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], 'routenew'));
		$db = vB::getDbAssertor();

		// delete routes that should not exist
		$legacyClass = array(
			'vB5_Route_Legacy_Bloghome',
		);
		foreach ($legacyClass as $c)
		{
			$db->delete('routenew', array('class' => $c));
		}

	}

	/*
	 * insert legacy routes
	 *
	 * vB5_Route_Legacy_Forumhome is skipped since it's inserted already
	 * vB5_Route_Legacy_vBCms will be tried again since it might not exist
	 */
	public function step_3()
	{
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], 'routenew'));

		$db = vB::getDbAssertor();
		$legacyClass = array(
			'vB5_Route_Legacy_Activity',
			'vB5_Route_Legacy_Announcement',
			'vB5_Route_Legacy_Archive',
			'vB5_Route_Legacy_Blog',
			'vB5_Route_Legacy_Converse',
			'vB5_Route_Legacy_Entry',
			'vB5_Route_Legacy_Faq',
			'vB5_Route_Legacy_Forum',
			//'vB5_Route_Legacy_Forumhome',
			'vB5_Route_Legacy_Group',
			'vB5_Route_Legacy_Member',
			'vB5_Route_Legacy_Misc',
			'vB5_Route_Legacy_Online',
			'vB5_Route_Legacy_Poll',
			'vB5_Route_Legacy_Post',
			'vB5_Route_Legacy_Register',
			'vB5_Route_Legacy_Search',
			'vB5_Route_Legacy_Sendmessage',
			'vB5_Route_Legacy_Subscription',
			'vB5_Route_Legacy_Tag',
			'vB5_Route_Legacy_Thread',
			'vB5_Route_Legacy_Threadprint',
			'vB5_Route_Legacy_Usercp',
		);

		// include vB5_Route_Legacy_vBCms if package exists and the route was not inserted before
		$packageId = $db->getField('package', array('class' => 'vBCms'));
		$total = $db->getRow('routenew', array(
			vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_COUNT,
			'class' => 'vB5_Route_Legacy_vBCms',
		));
		if ($packageId AND empty($total['count']))
		{
			$legacyClass[] = 'vB5_Route_Legacy_vBCms';
		}

		foreach ($legacyClass as $c)
		{
			$route = new $c;
			$data = array(
				'prefix'	=> $route->getPrefix(),
				'regex'		=> $route->getRegex(),
				'class'		=> $c,
				'arguments'	=> serialize($route->getArguments()),
			);
			$data['guid'] = vB_Xml_Export_Route::createGUID($data);
			$db->delete('routenew', array('class' => $c));
			$db->insert('routenew', $data);
		}
	}

	/*
	 *	Step 4 - Find and import the missing sent_items folders
	 *  missed by the query: vBInstall:createPMFoldersSent, VBV-9232
	 *
	*/
	public function step_4($data = [])
	{

		if ($this->tableExists('pmtext') AND $this->tableExists('messagefolder'))
		{
			// output what we're doing
			$this->show_message($this->phrase['version']['503a2']['importing_missing_PM_sent_items_folders']);

			$assertor = vB::getDbAssertor();
			$batchsize = 50000;
			$startat = intval($data['startat'] ?? 0);

			//First see if we need to do something. Maybe we're O.K.
			if (!empty($data['maxToFix']))
			{
				$maxToFix = $data['maxToFix'];
			}
			else
			{
				$maxToFix = $assertor->getRow('vBInstall:getMaxMissingPMFoldersSent', array());
				$maxToFix = intval($maxToFix['maxToFix']);
				//If we don't have any missing, we're done.
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

			$assertor->assertQuery('vBInstall:importMissingPMFoldersSent', array('startat' => $startat, 'batchsize' => $batchsize));

			// output current progress
			$this->show_message(sprintf($this->phrase['core']['processed_records_x_y_z'], $startat + 1, $startat + $batchsize, $maxToFix));

			return array('startat' => ($startat + $batchsize), 'maxToFix' => $maxToFix);
		}
		else
		{
			$this->skip_message();
		}

	}

	/*
	 *	Step 5 - Find and import the missing 'messages' folders
	 *  missed by the query: vBInstall:createPMFoldersMsg, VBV-9232
	 *
	*/
	public function step_5($data = [])
	{

		if ($this->tableExists('pm') AND $this->tableExists('messagefolder'))
		{
			// output what we're doing
			$this->show_message($this->phrase['version']['503a2']['importing_missing_PM_messages_folders']);

			$assertor = vB::getDbAssertor();
			$batchsize = 25000;
			$startat = intval($data['startat'] ?? 0);

			//First see if we need to do something. Maybe we're O.K.
			if (!empty($data['maxToFix']))
			{
				$maxToFix = $data['maxToFix'];
			}
			else
			{
				$maxToFix = $assertor->getRow('vBInstall:getMaxMissingPMMessagesFolder', array());
				$maxToFix = intval($maxToFix['maxToFix']);
				//If we don't have any missing, we're done.
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

			$assertor->assertQuery('vBInstall:importMissingPMMessagesFolder', array('startat' => $startat, 'batchsize' => $batchsize));

			// output current progress
			$this->show_message(sprintf($this->phrase['core']['processed_records_x_y_z'], $startat + 1, $startat + $batchsize, $maxToFix));

			return array('startat' => ($startat + $batchsize), 'maxToFix' => $maxToFix);
		}
		else
		{
			$this->skip_message();
		}
	}

	/*
	 *	Step 6 - Correct node fields starter = nodeid, lastcontentid = nodeid
	 *	missed by the query: vBInstall:setPMStarter, class_upgrade_501a2	step_22-24
	 *	VBV-9232
	 */
	public function step_6($data = null)
	{
		$batchsize = 1000;

		// output what we're doing
		$this->show_message($this->phrase['version']['503a2']['correcting_nodefields_orphan_infractions']);

		$assertor = vB::getDbAssertor();
		$startat = intval($data['startat'] ?? 0);

		// the following oldcontenttypes used setPMStarter
		$oldcontenttypeidsToFix = [
			vB_Api_ContentType::OLDTYPE_ORPHAN_INFRACTION_THREAD,
			vB_Api_ContentType::OLDTYPE_ORPHAN_INFRACTION_POST,
			vB_Api_ContentType::OLDTYPE_ORPHAN_INFRACTION_PROFILE,
		];

		//First see if we need to do something. Maybe we're O.K.
		if (!empty($data['maxToFix']))
		{
			$maxToFix = $data['maxToFix'];
		}
		else
		{
			$maxToFix = $assertor->getRow('vBInstall:getMaxOldidMissingNodeStarter',
				['oldcontenttypeids' => $oldcontenttypeidsToFix]
			);
			$maxToFix = intval($maxToFix['maxid']);
			//If we don't have any missing, we're done.
			if (intval($maxToFix) < 1)
			{
				$this->skip_message();
				return;
			}
		}

		// finish condition
		if ($startat >= $maxToFix)
		{
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			return;
		}

		$assertor->assertQuery('vBInstall:setNodeStarter', [
			'oldcontenttypeids' => $oldcontenttypeidsToFix,
			'startat' => $startat,
			'batchsize' => $batchsize,
		]);

		// output current progress
		$this->show_message(sprintf($this->phrase['core']['processed_records_x_y_z'], $startat + 1, $startat + $batchsize, $maxToFix));

		// kickoff next batch
		return ['startat' => ($startat + $batchsize), 'maxToFix' => $maxToFix];
	}
}

class vB_Upgrade_503a3 extends vB_Upgrade_Version
{
	/**
	* Step 1 - Add index on cacheevent.event
	*/
	public function step_1()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'cacheevent', 1, 1),
			'cacheevent',
			'event',
			'event'
		);
	}

	/*
	 *	Step 2 - Find and import the missing PM Starters
	 *  missed by the query: vBInstall:importPMStarter in 500a28 step 6, VBV-9232
	 *	Closure records are fixed after all missing records are imported.
	 *
	*/
	public function step_2($data = [])
	{

		if ($this->tableExists('pmtext') AND $this->tableExists('pm'))
		{
			// output what we're doing
			$this->show_message($this->phrase['version']['503a3']['importing_missing_PM_starters']);

			$assertor = vB::getDbAssertor();
			$batchsize = 10000;
			$startat = intval($data['startat'] ?? 0);

			//First see if we need to do something. Maybe we're O.K.
			if (!empty($data['maxToFix']))
			{
				$maxToFix = $data['maxToFix'];
			}
			else
			{
				$maxToFix = $assertor->getRow('vBInstall:getMaxMissingPMStarter', array('contenttypeid' => vB_Api_ContentType::OLDTYPE_PMSTARTER));
				$maxToFix = intval($maxToFix['maxToFix']);
				//If we don't have any missing, we're done.
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

			$nodeLib = vB_Library::instance('node');
			$pmHomeid = $nodeLib->fetchPMChannel();
			$pmHome = $nodeLib->getNode($pmHomeid);
			$assertor->assertQuery('vBInstall:importMissingPMStarter', array('startat' => $startat, 'batchsize' => $batchsize,
			'pmRouteid' => $pmHome['routeid'], 'privatemessageType' => vB_Types::instance()->getContentTypeID('vBForum_PrivateMessage'),
			'privateMessageChannel' => $pmHomeid, 'contenttypeid' => vB_Api_ContentType::OLDTYPE_PMSTARTER));
			$assertor->assertQuery('vBInstall:setPMStarter', array('startat' => $startat, 'batchsize' => $batchsize, 'contenttypeid' => vB_Api_ContentType::OLDTYPE_PMSTARTER));
			$assertor->assertQuery('vBInstall:importMissingPMText', array('startat' => $startat, 'batchsize' => $batchsize, 'contenttypeid' => vB_Api_ContentType::OLDTYPE_PMSTARTER));
			$assertor->assertQuery('vBInstall:importMissingPMMessage', array('startat' => $startat, 'batchsize' => $batchsize, 'contenttypeid' => vB_Api_ContentType::OLDTYPE_PMSTARTER));
			$assertor->assertQuery('vBInstall:importMissingPMSent', array('startat' => $startat, 'batchsize' => $batchsize, 'contenttypeid' => vB_Api_ContentType::OLDTYPE_PMSTARTER));
			$assertor->assertQuery('vBInstall:importMissingPMInbox', array('startat' => $startat, 'batchsize' => $batchsize, 'contenttypeid' => vB_Api_ContentType::OLDTYPE_PMSTARTER));
			$assertor->assertQuery('vBInstall:updateChannelRoutes', array('startat' => $startat, 'batchsize' => $batchsize, 'contenttypeid' => vB_Api_ContentType::OLDTYPE_PMSTARTER));

			// output current progress
			$this->show_message(sprintf($this->phrase['core']['processed_records_x_y_z'], $startat + 1, $startat + $batchsize, $maxToFix));

			return array('startat' => ($startat + $batchsize), 'maxToFix' => $maxToFix);
		}
		else
		{
			$this->skip_message();
		}
	}

	/*
	 * insert album legacy routes
	 */
	public function step_3()
	{
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], 'routenew'));

		$db = vB::getDbAssertor();
		$legacyClass = array(
			'vB5_Route_Legacy_Album'
		);

		foreach ($legacyClass as $c)
		{
			$route = new $c;
			$data = array(
				'prefix'	=> $route->getPrefix(),
				'regex'		=> $route->getRegex(),
				'class'		=> $c,
				'arguments'	=> serialize($route->getArguments()),
			);
			$data['guid'] = vB_Xml_Export_Route::createGUID($data);
			$db->delete('routenew', array('class' => $c));
			$db->insert('routenew', $data);
		}
	}

	/*
	 *	Step 4 - Find and import the missing PM responses
	 *  missed by the query: vBInstall:importPMResponse in 500a28 step 7, VBV-9232
	 *	Closure records are fixed after all missing records are imported.
	 *
	*/
	public function step_4($data = [])
	{

		if ($this->tableExists('pmtext') AND $this->tableExists('pm'))
		{
			// output what we're doing
			$this->show_message($this->phrase['version']['503a3']['importing_missing_PM_responses']);

			$assertor = vB::getDbAssertor();
			$batchsize = 1000;

			//First see if we need to do something. Maybe we're O.K.
			if (!empty($data['maxToFix']))
			{
				$maxToFix = $data['maxToFix'];
			}
			else
			{
				$maxToFix = $assertor->getRow('vBInstall:getMaxMissingPMResponse', array('contenttypeidResponse' => vB_Api_ContentType::OLDTYPE_PMRESPONSE,
					'contenttypeidStarter' => vB_Api_ContentType::OLDTYPE_PMSTARTER));
				$maxToFix = intval($maxToFix['maxToFix']);
				//If we don't have any missing, we're done.
				if ($maxToFix < 1)
				{
					$this->skip_message();
					return;
				}
			}

			// Here we fetch the minimum record to fix, this is to avoid unnecessary query executions if there are too sparse missing records
			$minToFix = $assertor->getRow('vBInstall:getMinMissingPMResponse', array('contenttypeidResponse' => vB_Api_ContentType::OLDTYPE_PMRESPONSE,
					'contenttypeidStarter' => vB_Api_ContentType::OLDTYPE_PMSTARTER));
			$startat = intval($minToFix['minToFix']);

			if (($startat < 1) OR ($startat > $maxToFix))
			{
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}

			// Here we decrement to take into account the '>' sign for the $startat in queries used in other steps
			$startat--;

			$nodeLib = vB_Library::instance('node');
			$pmHomeid = $nodeLib->fetchPMChannel();
			$pmHome = $nodeLib->getNode($pmHomeid);
			$assertor->assertQuery('vBInstall:importMissingPMResponse', array('startat' => $startat, 'batchsize' => $batchsize,
			'pmRouteid' => $pmHome['routeid'], 'privatemessageType' => vB_Types::instance()->getContentTypeID('vBForum_PrivateMessage'),
			'privateMessageChannel' => $pmHomeid, 'contenttypeidResponse' => vB_Api_ContentType::OLDTYPE_PMRESPONSE, 'contenttypeidStarter' => vB_Api_ContentType::OLDTYPE_PMSTARTER));
			$assertor->assertQuery('vBInstall:importMissingPMText', array('startat' => $startat, 'batchsize' => $batchsize, 'contenttypeid' => vB_Api_ContentType::OLDTYPE_PMRESPONSE));
			$assertor->assertQuery('vBInstall:importMissingPMMessage', array('startat' => $startat, 'batchsize' => $batchsize, 'contenttypeid' => vB_Api_ContentType::OLDTYPE_PMRESPONSE));
			$assertor->assertQuery('vBInstall:importMissingPMSent', array('startat' => $startat, 'batchsize' => $batchsize, 'contenttypeid' => vB_Api_ContentType::OLDTYPE_PMRESPONSE));
			$assertor->assertQuery('vBInstall:importMissingPMInbox', array('startat' => $startat, 'batchsize' => $batchsize, 'contenttypeid' => vB_Api_ContentType::OLDTYPE_PMRESPONSE));

			// output current progress, increment $startat just for display purposes, it is overwritten in the next iteration
			$startat++;
			$this->show_message(sprintf($this->phrase['core']['processed_records_x_y_z'], $startat, $startat + $batchsize, $maxToFix));

			return array('startat' => $startat, 'maxToFix' => $maxToFix);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_5()
	{
		$this->add_index(
			sprintf($this->phrase['core']['create_index_x_on_y'], 'contenttypeid_parentid', TABLE_PREFIX . 'node'),
			'node',
			'contenttypeid_parentid',
			array('contenttypeid', 'parentid')
		);
	}

	/**
	 * Create new fields to support official custom languages
	 */
	public function step_6()
	{
		//vblangcode/revision is also added in 500a1 step 140 because we need to make sure that
		//the field exists on some earlier steps.  Keep them in sync.
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'language', 1, 2),
			'language',
			'vblangcode',
			'varchar',
			array('length' => 12, 'default' => '',)
		);

		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'language', 2, 2),
			'language',
			'revision',
			'smallint',
			self::FIELD_DEFAULTS
		);
	}

	/*
	 *	Step 7- Find and import the missing Visitor Messages
	 *  missed by the query: vBInstall:ImportVisitorMessages, importVMText
	 * 	in 500a28 step_16, VBV-9232
	 *
	*/
	public function step_7($data = [])
	{
		// visitormessage table needs to exist for importing...
		if ($this->tableExists('visitormessage') AND $this->tableExists('node') AND $this->tableExists('text'))
		{
			// output what we're doing
			$this->show_message($this->phrase['version']['503a3']['importing_missing_VM']);

			$assertor = vB::getDbAssertor();
			$batchSize = 1000;
			$startat = intval($data['startat'] ?? 0);
			$textTypeid = vB_Types::instance()->getContentTypeID('vBForum_Text');
			$vmTypeid = vB_Types::instance()->getContentTypeID('vBForum_VisitorMessage');

			/*
			 *	The batching for this is a bit different. Basically, this step should
			 *	loop until getMissingVM doesn't find any more missing VM's. If it enters an
			 *	infinite loop, something went wrong in the importing.
			 */

			// We grab missing vm's.
			$missingVMs = $assertor->assertQuery('vBInstall:getMissingVM',
				array(
					'vmtypeid' => $vmTypeid,
					'batchsize' => $batchSize
				)
			);

			// if there are no more visitor messages missing, then we are done.
			if (!$missingVMs->valid())
			{
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}

			// otherwise, let's grab the vmid's from the query
			$vmidList = array();
			foreach ($missingVMs AS $vmRow)
			{
				$vmidList[] = $vmRow['vmid'];
			}

			$nodeLib = vB_Library::instance('node');
			$vmHomeid = $nodeLib->fetchVMChannel();
			$vmHome = $nodeLib->getNode($vmHomeid);

			// import the visitor messages into the node table
			$assertor->assertQuery('vBInstall:ImportMissingVisitorMessages', array(
				'vmChannel' => $vmHomeid,
				'texttypeid' => $textTypeid,
				'visitorMessageType' => $vmTypeid,
				'vmRouteid' => $vmHome['routeid'],
				'vmIds' => $vmidList
			));
			// then the text table..
			$assertor->assertQuery('vBInstall:importMissingVMText', array(
				'visitorMessageType' => $vmTypeid,
				'vmIds' => $vmidList
			));

			// then create the closure records..
			$assertor->assertQuery('vBInstall:addClosureSelfForOldids', array(
				'contenttypeid' => $vmTypeid,
				'oldids' => $vmidList,
			));
			$assertor->assertQuery('vBInstall:addClosureParentsForOldids', array(
				'contenttypeid' => $vmTypeid,
				'oldids' => $vmidList,
			));
			// now set some channel routes & node starters
			$assertor->assertQuery('vBInstall:updateChannelRoutesAndStarter_503a3', array(
				'contenttypeid' => $vmTypeid,
				'oldids' => $vmidList
			));

			// output current progress
			$this->show_message(sprintf($this->phrase['core']['processed_records_x'], count($vmidList)));

			// return something so it'll kick off the next batch, even though this batching doesn't use startat
			return array('startat' => 1);
		}
		else
		{
			$this->skip_message();
		}
	}

	/*
	 *	Step 8 - Find and import the missing SG discussions and their first post as starters
	 *  missed by the query: vBInstall:importSGDiscussions in 500a29 step 6, VBV-9232
	 *	Closure records are fixed after all missing records are imported.
	 *
	*/
	public function step_8($data = [])
	{
		if ($this->tableExists('socialgroup') AND $this->tableExists('discussion') AND $this->tableExists('groupmessage'))
		{
			$startat = intval($data['startat'] ?? 0);
			$assertor = vB::getDbAssertor();
			$groupTypeid = vB_Types::instance()->getContentTypeID('vBForum_SocialGroup');
			$discussionTypeid = vB_Types::instance()->getContentTypeID('vBForum_SocialGroupDiscussion');
			$batchsize = 1000;

			// Get the missing SG discussions
			$missingSGDiscussions = $assertor->assertQuery('vBInstall:getMissingSGDiscussions',
				array(
					'groupTypeid' => $groupTypeid,
					'discussionTypeid' => $discussionTypeid,
					'batchsize' => $batchsize
				)
			);

			// if there are no more records missing, then we are done.
			if (!$missingSGDiscussions->valid())
			{
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}

			// Get the discussionid from the query
			$discussionList = array();
			foreach ($missingSGDiscussions AS $discussion)
			{
				$discussionList[] = $discussion['discussionid'];
			}

			// And import them as text
			$this->show_message($this->phrase['version']['503a3']['importing_missing_discussions']);
			$assertor->assertQuery('vBInstall:importMissingSGDiscussions',array(
				'textTypeid' =>  vB_Types::instance()->getContentTypeID('vBForum_Text'),
				'discussionTypeid' => $discussionTypeid,
				'discussionList' => $discussionList,
				'grouptypeid' => $groupTypeid
			));

			// Get the nodeids from the newly imported discussions
			$discussionsText = $assertor->assertQuery('vBInstall:getMissingSGDiscussionText',array(
				'batchsize' => $batchsize,
				'discussionTypeid' => $discussionTypeid
			));

			$nodeList = array();
			foreach ($discussionsText AS $text)
			{
				$nodeList[] = $text['nodeid'];
			}

			// set them as starters
			$assertor->assertQuery('vBInstall:setStarterByNodeList',array(
				'nodeList' => $nodeList
			));

			// and import them to the text table
			$assertor->assertQuery('vBInstall:importMissingSGDiscussionText',array(
				'discussionTypeid' => $discussionTypeid,
				'textList' => $nodeList
			));

			$assertor->assertQuery('vBInstall:updateChannelRoutesByNodeList',array(
				'nodeList' => $nodeList,
				'contenttypeid' => $discussionTypeid
			));

			$this->show_message(sprintf($this->phrase['core']['processed_records_x'], $batchsize));
			return array('startat' => ($startat));

		}
		else
		{
			$this->skip_message();
		}
	}

	/*
	 *	Step 9 - Find and import the missing SG messages
	 *  missed by the query: vBInstall:importSGPosts in 500a29 step 7, VBV-9232
	 *	Closure records are fixed after all missing records are imported.
	 *
	*/
	public function step_9($data = [])
	{
		if ($this->tableExists('socialgroup') AND $this->tableExists('discussion') AND $this->tableExists('groupmessage'))
		{
			$startat = intval($data['startat'] ?? 0);
			$assertor = vB::getDbAssertor();
			$groupTypeid = vB_Types::instance()->getContentTypeID('vBForum_SocialGroup');
			$discussionTypeid = vB_Types::instance()->getContentTypeID('vBForum_SocialGroupDiscussion');
			$messageTypeid = vB_Types::instance()->getContentTypeID('vBForum_SocialGroupMessage');
			$batchsize = 1000;

			// Get the missing SG posts
			$missingSGPosts = $assertor->assertQuery('vBInstall:getMissingSGPosts',
				array(
					'messageTypeid' => $messageTypeid,
					'discussionTypeid' => $discussionTypeid,
					'batchsize' => $batchsize
				)
			);

			// if there are no more records missing, then we are done.
			if (!$missingSGPosts->valid())
			{
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}

			// Get the groupmessage id from the query
			$messageList = array();
			foreach ($missingSGPosts AS $message)
			{
				$messageList[] = $message['gmid'];
			}

			// And import them as text
			$this->show_message($this->phrase['version']['503a3']['importing_missing_messages']);
			$assertor->assertQuery('vBInstall:importMissingSGPosts',array(
				'textTypeid' =>  vB_Types::instance()->getContentTypeID('vBForum_Text'),
				'discussionTypeid' => $discussionTypeid,
				'messageList' => $messageList,
				'messageTypeid' => $messageTypeid
			));

			// Get the nodeids from the newly imported posts
			$postsText = $assertor->assertQuery('vBInstall:getMissingSGPostsText',array(
				'batchsize' => $batchsize,
				'messageTypeid' => $messageTypeid
			));

			$nodeList = array();
			foreach ($postsText AS $text)
			{
				$nodeList[] = $text['nodeid'];
			}

			// and import them to the text table
			$assertor->assertQuery('vBInstall:importMissingSGPostText',array(
				'messageTypeid' => $messageTypeid,
				'nodeList' => $nodeList
			));

			$this->show_message(sprintf($this->phrase['core']['processed_records_x'], count($messageList)));
			return array('startat' => ($startat));

		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	* Removing Search Queue Processor Scheduled Tasks
	**/
	public function step_10()
	{
			$this->show_message(sprintf($this->phrase['version']['503a3']['delete_queue_processor_cron']));
			vB::getDbAssertor()->delete('cron',array(
				'varname' => 'searchqueueupdates',
				'volatile' => 1,
				'product' => 'vbulletin'
			));
	}

	/*
	 *	Step 11 - Find and import the missing SG galleries
	 *  missed by the query: vBInstall:importSGGalleryNode in 500a29 step 8, VBV-9232
	 *	Closure records are fixed after all missing records are imported.
	 *
	*/
	public function step_11($data = [])
	{
		if ($this->tableExists('socialgroup') AND $this->tableExists('node') AND $this->tableExists('attachment') AND $this->tableExists('gallery'))
		{
			$startat = intval($data['startat'] ?? 0);
			$assertor = vB::getDbAssertor();
			$groupTypeid = vB_Types::instance()->getContentTypeID('vBForum_SocialGroup');
			$galleryTypeid = vB_Types::instance()->getContentTypeID('vBForum_Gallery');
			$batchsize = 1000;

			// Get the missing SG galleries
			$missingSGGalleries = $assertor->assertQuery('vBInstall:getMissingSGGalleryNode',
				array(
					'groupTypeid' => $groupTypeid,
					'oldGalleryTypeid' => vB_Api_ContentType::OLDTYPE_SGGALLERY,
					'batchsize' => $batchsize
				)
			);

			// if there are no more records missing, then we are done.
			if (!$missingSGGalleries->valid())
			{
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}

			// Get the gallery id from the query
			$galleryList = array();
			foreach ($missingSGGalleries AS $gallery)
			{
				$galleryList[] = $gallery['galleryid'];
			}

			// And import them
			$this->show_message($this->phrase['version']['503a3']['importing_missing_SG_galleries']);
			$assertor->assertQuery('vBInstall:importMissingSGGalleryNode',array(
				'groupTypeid' =>  $groupTypeid,
				'galleryTypeid' => $galleryTypeid,
				'galleryList' => $galleryList
			));

			// Get the nodeids from the newly imported galleries
			$galleyNodes = $assertor->assertQuery('vBInstall:getMissingSGGallery',array(
				'batchsize' => $batchsize,
				'groupTypeid' => $groupTypeid,
				'oldGalleryTypeid' => vB_Api_ContentType::OLDTYPE_SGGALLERY
			));

			$nodeList = array();
			foreach ($galleyNodes AS $gallery)
			{
				$nodeList[] = $gallery['nodeid'];
			}

			// import them to the gallery table
			$assertor->assertQuery('vBInstall:importMissingSGGallery',array(
				'oldGalleryTypeid' => vB_Api_ContentType::OLDTYPE_SGGALLERY,
				'nodeList' => $nodeList,
				'groupTypeid' => $groupTypeid,
				'caption' => $this->phrase['version']['500a29']['imported_socialgroup_galleries']
			));

			// and to the text table
			$assertor->assertQuery('vBInstall:importMissingSGText',array(
				'oldGalleryTypeid' => vB_Api_ContentType::OLDTYPE_SGGALLERY,
				'nodeList' => $nodeList,
				'groupTypeid' => $groupTypeid,
				'caption' => $this->phrase['version']['500a29']['imported_socialgroup_galleries']
			));

			// set them as starters
			$assertor->assertQuery('vBInstall:setStarterByNodeList',array(
				'nodeList' => $nodeList
			));

			$assertor->assertQuery('vBInstall:updateChannelRoutesByNodeList',array(
				'nodeList' => $nodeList,
				'contenttypeid' => vB_Api_ContentType::OLDTYPE_SGGALLERY
			));

			$this->show_message(sprintf($this->phrase['core']['processed_records_x'], count($galleryList)));
			return array('startat' => ($startat));

		}
		else
		{
			$this->skip_message();
		}
	}

	/*
	 *	Step 12 - Find and import the missing SG photos
	 *  missed by the query: vBInstall:importSGPhotoNodes in 500a29 step 9, VBV-9232
	 *	Closure records are fixed after all missing records are imported.
	 *
	*/
	public function step_12($data = [])
	{
		if ($this->tableExists('socialgroup') AND $this->tableExists('node') AND $this->tableExists('attachment') AND $this->tableExists('photo'))
		{
			$startat = intval($data['startat'] ?? 0);
			$assertor = vB::getDbAssertor();
			$groupTypeid = vB_Types::instance()->getContentTypeID('vBForum_SocialGroup');
			$galleryTypeid = vB_Types::instance()->getContentTypeID('vBForum_Gallery');
			$photoTypeid = vB_Types::instance()->getContentTypeID('vBForum_Photo');
			$batchsize = 1000;

			// Get the missing SG photos
			$missingSGPhotos = $assertor->assertQuery('vBInstall:getMissingSGPhotoNodes',
				array(
					'groupTypeid' => $groupTypeid,
					'oldGalleryTypeid' => vB_Api_ContentType::OLDTYPE_SGGALLERY,
					'oldPhotoTypeid' => vB_Api_ContentType::OLDTYPE_SGPHOTO,
					'batchsize' => $batchsize
				)
			);

			// if there are no more records missing, then we are done.
			if (!$missingSGPhotos->valid())
			{
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}

			// Get the attachmentid from the query
			$photoList = array();
			foreach ($missingSGPhotos AS $photo)
			{
				$photoList[] = $photo['attachmentid'];
			}

			// And import them
			$this->show_message($this->phrase['version']['503a3']['importing_missing_SG_photos']);
			$assertor->assertQuery('vBInstall:importMissingSGPhotoNodes',array(
				'groupTypeid' =>  $groupTypeid,
				'oldGalleryTypeid' => vB_Api_ContentType::OLDTYPE_SGGALLERY,
				'oldPhotoTypeid' => vB_Api_ContentType::OLDTYPE_SGPHOTO,
				'photoTypeid' => $photoTypeid,
				'photoList' => $photoList
			));

			$assertor->assertQuery('vBInstall:importMissingSGPhotos', array(
				'groupTypeid' =>  $groupTypeid,
				'oldPhotoTypeid' => vB_Api_ContentType::OLDTYPE_SGPHOTO,
				'photoList' => $photoList
			));

			$assertor->assertQuery('vBInstall:fixMissingLastGalleryData',array(
				'photoList' => $photoList
			));

			$this->show_message(sprintf($this->phrase['core']['processed_records_x'], count($photoList)));
			return array('startat' => ($startat));

		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_13()
	{
		$this->skip_message();
	}

	/**
	 * Update lastcontentid data for socialgroups, this is a carbon copy of the one in 500a29, step_19
	 * The query getMaxImportedPost is returning the max node.oldid, but for the batching in updateDiscussionLastContentId
	 * node.nodeid was being used, therefore usually not updating the nodes.
	 * updateDiscussionLastContentId was fixed to use oldid, this step will fix installations already upgraded to 5.0.x and missing nodes (VBV-9232),
	 *
	 */
	public function step_14($data = [])
	{
		$batchsize = 10000;

		$messageTypeid = vB_Types::instance()->getContentTypeID('vBForum_SocialGroupMessage');
		$discussionTypeid = vB_Types::instance()->getContentTypeID('vBForum_SocialGroupDiscussion');
		$startat = intval($data['startat'] ?? 0);
		$assertor = vB::getDbAssertor();

		$maxvB5 = $assertor->getRow('vBInstall:getMaxImportedPost', array('contenttypeid' => $messageTypeid));
		$maxvB5 = intval($maxvB5['maxid']);

		if ($startat == 0)
		{
			$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'node'));
		}
		else if ($startat >= $maxvB5)
		{
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			return;
		}
		$assertor->assertQuery('vBInstall:updateDiscussionLastContentId', array('messageTypeid' => $messageTypeid,
			'discussionTypeid' => $discussionTypeid,'startat' => $startat, 'batchsize' => $batchsize));
		$this->show_message(sprintf($this->phrase['core']['processed_records_x'], $batchsize));
		return array('startat' => $startat + $batchsize);
	}

	/*
	 * 	Step 15 - Add missing closure records VBV-9232
	 * 	This step is inefficient. It walks through the entire node table
	 *	and finds nodes with missing closure records via
	 *	getNoclosureNodes & getOrphanNodes.
	 *	Since it's rare that either query actually returns anything,
	 *	this step will probably do quite a few iterations doing nothing
	 *	until a node with missing closure records is found.
	*/
	public function step_15($data = NULL)
	{
		if ($this->tableExists('node') AND $this->tableExists('closure'))
		{
			$batchSize = 50000;

			$albumTypeid = vB_Types::instance()->getContentTypeID('vBForum_Album');

			$assertor = vB::getDbAssertor();
			$startat = intval($data['startat'] ?? 0);

			// get max nodeid
			if (!empty($data['maxNodeid']))
			{
				$maxNodeid = intval($data['maxNodeid']);
			}
			else
			{
				$maxNodeid = $assertor->getRow('vBInstall:getMaxNodeid', array());
				$maxNodeid = intval($maxNodeid['maxid']);
			}
			//If we don't have any nodes, we do nothing.
			if ($maxNodeid < 1)
			{
				$this->skip_message();
				return;
			}

			// finish condition
			if ($maxNodeid <= $startat)
			{
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}

			$end = min($startat + $batchSize, $maxNodeid);

			// output what we're doing
			$this->show_message($this->phrase['version']['502rc1']['adding_missing_closure']);


			// grab the nodeid's for the nodes missing closure records
			$nodesMissingSelfClosures = $assertor->assertQuery('vBInstall:getNoclosureNodes',
				array(
					'oldcontenttypeid' => $albumTypeid,
					'startat' => $startat,
					'batchsize' => $batchSize,
				)
			);
			// some nodes might have self closure but no parent closure.
			$nodesMissingParentClosures = $assertor->assertQuery('vBInstall:getOrphanNodes',
				array(
					'oldcontenttypeid' => $albumTypeid,
					'startat' => $startat,
					'batchsize' => $batchSize,
				)
			);

			// If there were no nodes missing closure in this batch, quickly move on to the next batch
			if ( !($nodesMissingSelfClosures->valid() OR $nodesMissingParentClosures->valid()) )
			{
				// output current progress
				$this->show_message(sprintf($this->phrase['core']['processed_records_x_y_z'], $startat + 1, $end, $maxNodeid));
				// kick off the next batch
				return array('startat' => ($startat + $batchSize), 'maxNodeid' => $maxNodeid);
			}

			// otherwise, let's grab the nodeid's from the query
			$nodeIdList_needself = array();
			foreach ($nodesMissingSelfClosures AS $nodeRow)
			{
				$nodeIdList_needself[] = $nodeRow['nodeid'];
			}

			$nodeIdList_needparents = array();
			foreach ($nodesMissingParentClosures AS $nodeRow)
			{
				$nodeIdList_needparents[] = $nodeRow['nodeid'];
			}


			// add closure records
			if (!empty($nodeIdList_needself))
			{
				$assertor->assertQuery('vBInstall:addMissingClosureSelf',
					array(
						'nodeIdList' => $nodeIdList_needself
					)
				);
			}

			if (!empty($nodeIdList_needparents))
			{
				$assertor->assertQuery('vBInstall:addMissingClosureParents',
					array(
						'nodeIdList' => $nodeIdList_needparents
					)
				);
			}


			// output current progress
			$this->show_message(sprintf($this->phrase['core']['processed_records_x_y_z'], $startat + 1, $end, $maxNodeid));
			// kick off the next batch
			return array('startat' => ($startat + $batchSize), 'maxNodeid' => $maxNodeid);
		}
		else
		{
			$this->skip_message();
		}
	}
}

class vB_Upgrade_503b1 extends vB_Upgrade_Version
{
	/*
	 *	Steps 1 & 2 are copied from sprint69m8's class_upgrade_502. VBV-9700
	 *	Step 1 - Fix the node.open flag for threads incorrectly set by 500a1 steps 145
	 *	We're making a couple assumptions here:
	 *		One, thread table has not been removed
	 *		Two, a thread that was closed in vB4 has NOT been re-opened in vB5.
	 *			- Any vB4 thread that was re-opened in vB5 will be closed again.
	*/
	public function step_1($data = [])
	{
		// if imported from vB5, there should be a thread table (vB4) & node table (vB5)
		if ($this->tableExists('thread') AND $this->tableExists('node'))
		{
			//We only need to run this once.
			if (empty($data['startat']))
			{
				$log = vB::getDbAssertor()->assertQuery('vBInstall:upgradelog', array('script' => '503b1', 'step' => 1)); // Must match this step.

				if ($log->valid())
				{
					$this->skip_message();
					return;
				}
			}

			// output what we're doing
			$this->show_message($this->phrase['version']['503b1']['correcting_node_field_open']);

			$assertor = vB::getDbAssertor();
			$batchSize = 100000;
			// we're looking for imported threads.
			$threadId = vB_Types::instance()->getContentTypeID('vBForum_Thread');

			// grab startat
			$startat = intval($data['startat'] ?? 0);
			// grab max if using CLI & not first iteration, else fetch it from DB
			if (!empty($data['max']))
			{
				$max = intval($data['max']);
			}
			else
			{
				$max = $assertor->getRow('vBInstall:getMaxImportedPost',
					array(
						'contenttypeid' => $threadId
					)
				);
				$max = intval($max['maxid']);
			}

			// finish condition is when no thread were imported or we already processed the max oldid
			if ($max == 0 OR $max <= $startat)
			{
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}


			// fix corrupt node.open flags for imported threads
			$assertor->assertQuery('vBInstall:fixCorruptOpenFlags',
				array(
					'oldcontenttypeid' => $threadId,
					'startat' => $startat,
					'batchsize' => $batchSize,
				)
			);

			/* fix corrupt node.open flags for imported threads
			 * the reason we cannot just run this second query by itself is in
			 * case the thread table is truncated. Also, running the first query
			 * by itself misses the following edge case:
			 * 		Thread A is closed in vB4. Site is upgraded to vB5 5.0.1.
			 *		Thread A's parent channel is closed.
			 *		They upgrade to 5.0.2 / run the first query
			 */
			$assertor->assertQuery('vBInstall:importClosedThreadOpenFlags',
				array(
					'oldcontenttypeid' => $threadId,
					'startat' => $startat,
					'batchsize' => $batchSize,
				)
			);

			// output current progress
			$this->show_message(sprintf($this->phrase['core']['processed_records_x_y_z'], $startat +1, min($startat + $batchSize, $max), $max));

			// kick off next batch
			return array('startat' => $startat + $batchSize, 'max' => $max);
		}
		else
		{
			$this->skip_message();
		}
	}

	/*
	 *	Step 2 - Fix the node.showopen flag for non-starter posts incorrectly set by 500a1 step 146
	 *		Also need to set the node.showopen flag to 0 for any posts made post-upgrade against
	 *		threads closed in vB4.
	 *	We're making a few assumptions here:
	 *		One, thread & post tables have not been removed
	*/
	public function step_2($data = [])
	{
		// if imported from vB5, there should be a thread table (vB4) & node table (vB5)
		if ($this->tableExists('thread') AND $this->tableExists('post') AND $this->tableExists('node'))
		{
			//We only need to run this once.
			if (empty($data['startat']))
			{
				$log = vB::getDbAssertor()->assertQuery('vBInstall:upgradelog', array('script' => '503b1', 'step' => 2)); // Must match this step.

				if ($log->valid())
				{
					$this->skip_message();
					return;
				}
			}

			// output what we're doing
			if (empty($data['startat']))
			{
				$this->show_message($this->phrase['version']['503b1']['correcting_node_field_showopen']);
			}

			$assertor = vB::getDbAssertor();
			$batchSize = 100000;
			// we only care about nodes whose parents are imported threads.
			$threadId = vB_Types::instance()->getContentTypeID('vBForum_Thread');

			// grab startat
			$startat = intval($data['startat'] ?? 0);
			// grab max if using CLI & not first iteration, else fetch it from DB
			if (!empty($data['max']) AND !empty($data['maxThread']))
			{
				$max = intval($data['max']);
				$maxThread = intval($data['maxThread']);
			}
			else
			{
				// get the max nodeid
				$max = $assertor->getRow('vBInstall:getMaxNodeid', array());
				$max = intval($max['maxid']);
				// also grab the max imported thread's oldid
				$maxThread = $assertor->getRow('vBInstall:getMaxImportedPost',
					array(
						'contenttypeid' => $threadId
					)
				);
				$maxThread = intval($maxThread['maxid']);
			}

			// finish condition is when we've walked through all nodes, or there are no nodes.
			if ($maxThread == 0 OR $max == 0 OR $max <= $startat)
			{
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}

			//We're only fixing the showopen flags for nodes whose parent was an imported, closed thread
			$assertor->assertQuery('vBInstall:fixIncorrectShowopenFlags',
				array(
					'oldcontenttypeid' => $threadId,
					'startat' => $startat,
					'batchsize' => $batchSize,
				)
			);

			// output current progress
			$this->show_message(sprintf($this->phrase['core']['processed_records_x_y_z'], $startat +1, min($startat + $batchSize, $max), $max));

			// kick off next batch
			return array('startat' => $startat + $batchSize, 'max' => $max, 'maxThread' => $maxThread);
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	* Step 3 - Rename layouts. Repurpose title to hold the phrase title.
	*/
	public function step_3()
	{
		$assertor = vB::getDbAssertor();
		// 100 => full
		$assertor->assertQuery('screenlayout', array(
			vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
			'screenlayoutid' => 1,
			'varname' => 'full',
			'title' => 'layout_full'
		));

		// 70/30 => Wide/Narrow
		$assertor->assertQuery('screenlayout', array(
			vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
			'screenlayoutid' => 2,
			'varname' => 'wide-narrow',
			'title' => 'layout_wide_narrow'
		));

		// 30/70 => Narrow/Wide
		$assertor->assertQuery('screenlayout', array(
			vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
			'screenlayoutid' => 4,
			'varname' => 'narrow-wide',
			'title' => 'layout_narrow_wide'
		));

		$this->show_message($this->phrase['version']['503b1']['rename_screen_layout']);
	}

	/**
	 * fix the missing '$pagenum' arguments for channel routes
	 * missed by query in class_upgrade_final:step5
	 */
	public function step_4($data = [])
	{
		$db = vB::getDbAssertor();
		$batchsize = 100;
		$results = $db->assertQuery('vBInstall:fixMissingPageArgumentsForChannels',
			array('batchsize' => $batchsize)
		);

		if (!$results->valid())
		{
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			return;
		}
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], 'routenew'));

		foreach($results AS $record)
		{
			$arguments = unserialize($record['arguments']);
			if (empty($arguments['pagenum']))
			{
				$arguments['pagenum'] = '$pagenum';
				$db->assertQuery('routenew', array(
					vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
					'routeid' => $record['routeid'],
					'arguments' => serialize($arguments)
				));
			}
		}

		// return dummy value to loop the step
		return array('startat' => 1);
	}

	/*
	 * Step 5 - Remove the orphan "Main Forum" Channel if added during upgrade from vB4
	 */
	public function step_5()
	{
		// output what we're doing
		$this->show_message($this->phrase['version']['503b1']['removing_orphan_channels']);

		// need the assertor
		$db = vB::getDbAssertor();

		// get the channel contenttypeid
		$channelTypeId = vB_Types::instance()->getContentTypeID('vBForum_Channel');

		$results = $db->assertQuery('vBInstall:findOrphanChildlessChannels',
			array(
				'contenttypeid' => $channelTypeId,
			)
		);

		if (!$results->valid())
		{
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			return;
		}

		// we need to create an admin session first so checkForumClosed() doesn't fail
		vB_Upgrade::createAdminSession();
		// instantiate vB_Library_Content_Channel, so we can call its delete() function on the node
		$channelLib = vB_Library::instance('content_channel');

		// delete these nodes. There shouldn't be very many of these channels.
		// The only one expected is the "Main Forum" channel
		foreach($results AS $node)
		{
			$success = $channelLib->delete(intval($node['nodeid']));

			// if it failed to delete, there's not much we can do.
			if (!$success)
			{
				$this->show_message(sprintf($this->phrase['version']['503b1']['node_deletion_failed_for_x'], $node['nodeid']));
			}
			else
			{
				$this->show_message(sprintf($this->phrase['version']['503b1']['deleted_node_x'], $node['nodeid']));
			}
		}

		$this->show_message(sprintf($this->phrase['core']['process_done']));
	}

	/**
	 * Step 6 - Ensure cache.data is MEDIUMTEXT .. not blob
	 */
	public function step_6()
	{
		$tableinfo = $this->fetch_table_info('cache');
		if (!empty($tableinfo) AND $tableinfo['data']['Type'] != strtolower('mediumtext'))
		{
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'cache', 1, 1),
				"ALTER TABLE " . TABLE_PREFIX . "cache CHANGE data data MEDIUMTEXT"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	 * Step 7 - Remove no longer needed phrasetypes
	 */
	public function step_7()
	{
		if ($this->tableExists('phrasetype'))
		{
			$this->show_message($this->phrase['version']['503b1']['removing_old_phrasetypes']);
			vB::getDbAssertor()->delete('phrasetype', array(
				'fieldname' => array('contenttypes', 'holiday', 'vbblocksettings'))
			);
		}
		else
		{
			$this->skip_message();
		}
	}
}

class vB_Upgrade_503rc1 extends vB_Upgrade_Version
{
	/*
	 *	Step 1 - Find incorrectly imported polls and update the starter node
	 *  The issue ocurred in upgrader 500a1 steps 149-152, VBV-9818
	 *
	 *	NOTE: Also see 510a8 step_1
	*/
	public function step_1($data = NULL)
	{
		if ($this->tableExists('poll') AND $this->tableExists('polloption'))
		{
			$batchsize = 500000;

			// output what we're doing
			$this->show_message($this->phrase['version']['503rc1']['importing_stray_polls']);

			$assertor = vB::getDbAssertor();
			$startat = intval($data['startat'] ?? 0);

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
			$pollData = $assertor->assertQuery('vBInstall:getStrayPollsAndOptions', array(
				'startat' => $startat,
				'batchsize' => $batchsize,
				'pollcontenttypeid' => vB_Api_ContentType::OLDTYPE_POLL,
				'threadcontenttypeid' => vB_Types::instance()->getContentTypeID('vBForum_Thread'),
			));

			if (!$pollData->valid())
			{
				return array('startat' => ($startat + $batchsize), 'maxToFix' => $maxToFix);
			}

			// Update the polloption table
			$assertor->assertQuery('vBInstall:fixNodeidInPolloption', array(
				'startat' => $startat,
				'batchsize' => $batchsize,
				'contenttypeid' => vB_Api_ContentType::OLDTYPE_POLL,
			));

			$oldPollList = array();
			foreach ($pollData AS $poll)
			{
				$votes = 0;
				if (!empty($poll['options']) AND ($options = unserialize($poll['options']))
						AND is_array($options))
				{
					foreach ($options AS $key => $option)
					{
						$options[$key]['nodeid'] = $poll['nodeid'];
						$votes += $options[$key]['votes'];
					}

					foreach ($options AS &$option)
					{
						if ($votes)
						{
							$option['percentage'] = number_format($option['votes'] / $votes * 100, 2);
						}
						else
						{
							$option['percentage'] = 0;
						}
					}

					// Update nodeid, poll options and number of votes for each poll
					$assertor->assertQuery('vBForum:poll', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
						vB_dB_Query::CONDITIONS_KEY => array(array('field' => 'nodeid', 'value' => $poll['pollnodeid'])),
						'nodeid' => $poll['nodeid'],
						'options' => serialize($options),
						'votes' => $votes
					));

				}
				$oldPollList[] = $poll['pollnodeid'];
			}

			// Fix starter contenttypes
			$assertor->assertQuery('vBInstall:fixPollContentTypes', array('startat' => $startat, 'batchsize' => $batchsize, 'contenttypeid' => vB_Api_ContentType::OLDTYPE_POLL));

			// Remove this batch of old poll nodes (the ones that shouldn't have been created)
			$assertor->assertQuery('vBForum:node', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_DELETE, 'nodeid' => $oldPollList));
			$assertor->assertQuery('vBForum:closure', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_DELETE, 'child' => $oldPollList));

			// output current progress
			$this->show_message(sprintf($this->phrase['core']['processed_records_x_y_z'], $startat + 1, $startat + $batchsize, $maxToFix));

			return array('startat' => ($startat + $batchsize), 'maxToFix' => $maxToFix);
		}
		else
		{
			$this->skip_message();
		}
	}

	// removing redundant infraction table fields
	public function step_2()
	{
		$made_changes = false;
		if ($this->field_exists('infraction', 'infractionid'))
		{
			$this->drop_field(
					sprintf($this->phrase['core']['altering_x_table'], 'infraction', 1, 6),
					'infraction',
					'infractionid'
			);
			$made_changes = true;
		}

		if ($this->field_exists('infraction', 'postid'))
		{
			$this->drop_field(
					sprintf($this->phrase['core']['altering_x_table'], 'infraction', 2, 6),
					'infraction',
					'postid'
			);
			$made_changes = true;
		}

		if ($this->field_exists('infraction', 'userid'))
		{
			$this->drop_field(
					sprintf($this->phrase['core']['altering_x_table'], 'infraction', 3, 6),
					'infraction',
					'userid'
			);
			$made_changes = true;
		}

		if ($this->field_exists('infraction', 'dateline'))
		{
			$this->drop_field(
					sprintf($this->phrase['core']['altering_x_table'], 'infraction', 4, 6),
					'infraction',
					'dateline'
			);
			$made_changes = true;
		}
		if ($this->field_exists('infraction', 'channelid'))
		{
			$this->drop_field(
					sprintf($this->phrase['core']['altering_x_table'], 'infraction', 5, 6),
					'infraction',
					'channelid'
			);
			$made_changes = true;
		}

		if ($this->field_exists('infraction', 'threadid'))
		{
			$this->drop_field(
					sprintf($this->phrase['core']['altering_x_table'], 'infraction', 6, 6),
					'infraction',
					'threadid'
			);
			$made_changes = true;
		}

		if (!$made_changes)
		{
			$this->skip_message();
		}
	}
}

class vB_Upgrade_504a1 extends vB_Upgrade_Version
{
	/**
	 * 501a2 step8 changed all userlist.type = 'buddy' fields to
	 * userlist.type = 'follow'.  This included those with friend = 'denied.
	 * vB5 sets userlist.type = 'ignore', userlist.friend = 'denied'
	 * to equate a deny so we must update these bogus records.
	 * Update userlist.type = 'follow', userlist.friend = 'denied' to
	 * userlist.type = 'ignore', userlist.friend = 'denied'
	 *
	 * VBV-11987: In previous versions, the state "type=buddy,friend=denied" was when you sent
	 * a friend request to someone and they denied it. It was shown to you as still pending.
	 * In vB5, if your follow request is denied, the state is "type=follow,friend=pending", so
	 * we should change "type=follow,friend=denied" (buddy has been changed to follow) to
	 * "type=follow,friend=pending" instead of trying to convert it into an ignore user record
	 * when it was never intended to be an ignore. Trying to convert it to an ignore record
	 * is the source of the duplicate key error, since you could have both a friend request
	 * to someone that they have denied (type=follow,friend=denied) and you could ignore
	 * (type=ignore) that same user. As for sites that successfully ran this step, there is no
	 * way to reliably detect the incorrect ignore records and reverse them, since they are
	 * marked the same "type=ignore,friend=denied" as vB5 currently marks them.
	 */
	public function step_1()
	{
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], 'userlist'));
		vB::getDbAssertor()->update('userlist', array(
			'friend' => 'pending'
		), array(
			'type'   => 'follow',
			'friend' => 'denied'
		));
	}

	/**
	 * Import follow requests .. but not those that are ignored
	 */
	public function step_2($data = null)
	{
		$batchsize = 125;
		$startat = intval($data['startat'] ?? 0);

		// Check if any users have custom folders
		if (!empty($data['totalUsers']))
		{
			$totalUsers = $data['totalUsers'];
		}
		else
		{
			// Get the number of users that have pending requests
			$totalUsers = vB::getDbAssertor()->getRow('vBInstall:getTotalPendingFriends');
			$totalUsers = intval($totalUsers['totalusers']);

			if (intval($totalUsers) < 1)
			{
				$this->skip_message();
				return;
			}
			else
			{
				$this->show_message($this->phrase['version']['504a1']['converting_pending_friend_requests']);
			}
		}

		if ($startat >= $totalUsers)
		{
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			return;
		}

		$totaldone = (($startat + $batchsize) > $totalUsers ? $totalUsers : $startat + $batchsize);
		$this->show_message(sprintf($this->phrase['core']['processed_records_x_y_z'], $startat + 1, $totaldone, $totalUsers));
		$users = vB::getDbAssertor()->getRows('vBInstall:convertPendingFriends', ['startat' => $startat, 'batchsize' => $batchsize]);

		if ($users)
		{
			vB_Upgrade::createAdminSession();

			$messageLibrary = vB_Library::instance('Content_Privatemessage');
			// Note: These are requests, not notifications, and are not affected by notification refactor.
			$notifications = [];

			foreach($users AS $user)
			{
				if (!$user['ignored'])
				{
					// Check if this request already has a notification
					$existing = vB::getDbAssertor()->getRows('vBInstall:getCurrentPendingRequest', ['userid' => $user['userid'], 'relationid' => $user['relationid']]);
					if (!$existing)
					{
						$notifications[] = array(
							'msgtype' => 'request',
							'sentto'  => $user['relationid'],
							'aboutid' => $user['userid'],
							'about'   => 'follow',
							'sender'  => $user['userid']
						);
					}
				}
			}

			foreach ($notifications AS $notification)
			{
				// send notification only if receiver is not the sender.
				if ($notification['sentto'] != $notification['sender'])
				{
					try
					{
						$check = vB::getDbAssertor()->getField('user', [
							vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_COUNT,
							//this is almost certainly not right and just as certainly means this entire upgrade step
							//has not nor has ever done anything useful.  This query can only ever match one record.
							//however I'm not clear on the implications of fixing this and don't want to bury it as
							//part of a much larger sweep.
							'userid' => [$notification['sender'], $notification['sender']],
						]);

						//There should be two records.
						if ($check == 2)
						{
							$nodeid = $messageLibrary->addMessageNoFlood($notification);
						}
					}
					catch (vB_Exception_Api $e)
					{}
				}
			}
		}

		return ['startat' => ($startat + $batchsize), 'totalUsers' => $totalUsers];
	}

	/**
	 * convert title, meta keywords and description of user custom existing pages into phrases
	 * remove columns from page table
	 */
	public function step_3()
	{
		if (!$this->field_exists('page', 'title'))
		{
			$this->skip_message();
			return;
		}

		$this->show_message($this->phrase['version']['504a1']['converting_page_metadata_to_phrases']);

		vB_Upgrade::createAdminSession();

		$phraseApi = vB_Api::instanceInternal('phrase');

		// List pages
		$pages = vB::getDbAssertor()->getRows('page');
		$replace = array(
			0 => array('find' => array('.', 'vbulletin-'), 'replace' => ''),
			1 => array('find' => array('-'), 'replace' => '_')
		);
		foreach ($pages as $page)
		{
			$guidforphrase = str_replace($replace[0]['find'], $replace[0]['replace'], $page['guid']);
			$guidforphrase = str_replace($replace[1]['find'], $replace[1]['replace'], $guidforphrase);

			if (!empty($page['title']))
			{
				$check = vB::getDbAssertor()->getField('vBInstall:checkPagePhrase', array('varname' => 'page_' . $guidforphrase . '_title'));
				if (empty($check))
				{
					$phraseApi->save('pagemeta',
						'page_' . $guidforphrase . '_title',
						array(
							'text' => array($page['title']),
							'product' => 'vbulletin',
							'oldvarname' => 'page_' . $guidforphrase . '_title',
							'oldfieldname' => 'global',
							'skipdebug' => 1,
						)
					);
				}
			}

			if (!empty($page['metadescription']))
			{
				$check = vB::getDbAssertor()->getField('vBInstall:checkPagePhrase', array('varname' => 'page_' . $guidforphrase . '_metadesc'));
				if (empty($check))
				{
					$phraseApi->save('pagemeta',
						'page_' . $guidforphrase . '_metadesc',
						array(
							'text' => array($page['metadescription']),
							'product' => 'vbulletin',
							'oldvarname' => 'page_' . $guidforphrase . '_metadesc',
							'oldfieldname' => 'global',
							'skipdebug' => 1,
						)
					);
				}
			}

				}

		$this->show_message($this->phrase['core']['done']);

		// TODO: we should remove these 3 fields from page table later.
		// title, metadescription, metakeywords
	}

	/**
	 * Increase the size of 'title' field in product table
	 */
	public function step_4()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'product', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "product CHANGE title title VARCHAR(250) NOT NULL DEFAULT '0'"
		);
	}
}

class vB_Upgrade_504a2 extends vB_Upgrade_Version
{
	/**
	 * Generate correct value for vboptions: frontendurl
	 */
	public function step_1()
	{
		// Get the settings directly
		$data = $this->db->query_read("
			SELECT varname, value
			FROM " . TABLE_PREFIX . "setting
			WHERE varname IN ('bburl', 'frontendurl')
			ORDER BY varname
		");

		$frontendurl = false;

		while ($setting = $this->db->fetch_array($data))
		{
			switch ($setting['varname'])
			{
				case 'bburl':
					$bburl = $setting['value'];
				break;

				case 'frontendurl':
					$frontendurl = $setting['value'];
				break;
			}
		}

		if (!$frontendurl)
		{
			$newurl = $this->db->escape_string(substr($bburl,0, strpos($bburl, '/core')));
			$this->show_message($this->phrase['version']['504a2']['updating_frontendurl_settings']);

			if (!$frontendurl)
			{
				if ($frontendurl === false)
				{
					/* Setting does not exist, add it.
					The settings import will fill in the blanks */
					$this->db->query_write("
						INSERT INTO " . TABLE_PREFIX . "setting
						(varname, value, volatile)
						VALUES
						('frontendurl', '$newurl', 1)
					");
				}
				else
				{
					// Setting exists, update it
					$this->db->query_write("
						UPDATE " . TABLE_PREFIX . "setting
						SET value = '$newurl'
						WHERE varname = 'frontendurl'
					");
				}
			}
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	 * New emailstamp field for session table for guest email flood check
	 */
	public function step_2()
	{
		if (!$this->field_exists('session', 'emailstamp'))
		{
			// Create nodeid field
			$this->add_field(
				sprintf($this->phrase['core']['altering_x_table'], 'session', 1, 1),
				'session',
				'emailstamp',
				'INT',
				self::FIELD_DEFAULTS
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	 * Adding product to pagetemplate
	 */
	public function step_3()
	{
		if (!$this->field_exists('pagetemplate', 'product'))
		{
			$this->add_field(
				sprintf($this->phrase['core']['altering_x_table'], 'pagetemplate', 1, 1),
				'pagetemplate',
				'product',
				'VARCHAR',
				array(
					'length' => 25,
					'default' => 'vbulletin',
				)
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	 * Adding product to page
	 */
	public function step_4()
	{
		if (!$this->field_exists('page', 'product'))
		{
			$this->add_field(
				sprintf($this->phrase['core']['altering_x_table'], 'page', 1, 1),
				'page',
				'product',
				'VARCHAR',
				array(
					'length' => 25,
					'default' => 'vbulletin',
				)
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	 * Adding product to channel
	 */
	public function step_5()
	{
		if (!$this->field_exists('channel', 'product'))
		{
			$this->add_field(
				sprintf($this->phrase['core']['altering_x_table'], 'channel', 1, 1),
				'channel',
				'product',
				'VARCHAR',
				array(
					'length' => 25,
					'default' => 'vbulletin',
				)
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	 * Adding product to routenew
	 */
	public function step_6()
	{
		if (!$this->field_exists('routenew', 'product'))
		{
			$this->add_field(
				sprintf($this->phrase['core']['altering_x_table'], 'routenew', 1, 1),
				'routenew',
				'product',
				'VARCHAR',
				array(
					'length' => 25,
					'default' => 'vbulletin',
				)
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	 * Remove meta keyword field from node table
	 */
	public function step_7()
	{
	if ($this->field_exists('node', 'keywords'))
		{
			//If we have over a million posts we won't do this.
			$check = vB::getDbAssertor()->getRow('vBInstall:getMaxNodeid', array());

			if ($check AND !empty($check['maxid']) AND (intval($check['maxid']) > 1000000))
			{
				$this->skip_message();
				$this->add_adminmessage('can_drop_node_keywords', array('dismissable' => 1,
					'status'  => 'undone',));
			}
			else
			{
				$this->drop_field(sprintf($this->phrase['core']['altering_x_table'], 'node', 1, 1),
					'node', 'keywords'
				);

			}
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	 * Remove meta keyword field from page table
	 */
	public function step_8()
	{
	if ($this->field_exists('page', 'metakeywords'))
		{
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'page', 1, 1),
				"ALTER TABLE " . TABLE_PREFIX . "page DROP COLUMN metakeywords"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	 * Add reputation penalty for an infraction
	 */
	public function step_9()
	{
		if (!$this->field_exists('infraction', 'reputation_penalty'))
		{
			$this->add_field(
				sprintf($this->phrase['core']['altering_x_table'], 'infraction', 1, 1),
				'infraction',
				'reputation_penalty',
				'INT',
				self::FIELD_DEFAULTS
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	 * Add reputation penalty for an infraction level
	 */
	public function step_10()
	{
		if (!$this->field_exists('infractionlevel', 'reputation_penalty'))
		{
			$this->add_field(
				sprintf($this->phrase['core']['altering_x_table'], 'infractionlevel', 1, 1),
				'infractionlevel',
				'reputation_penalty',
				'INT',
				self::FIELD_DEFAULTS
			);
		}
		else
		{
			$this->skip_message();
		}
	}
}

class vB_Upgrade_504a3 extends vB_Upgrade_Version
{

	/**
	 * Step 1	-	500b27 step_8 did not escape the prefix before putting it into
	 *			the regex, so any conversation routes with regex in the urlIdent
	 *			need to be repaired. Since step_8 is removed now, just re-create
	 *			the regex for all conversations
	 */
	public function step_1($data = NULL)
	{
		// this step has been moved to 505rc1, because the conversation route's regex has been updated in 5.0.5
		$this->skip_message();
	}

	/**
	 * Step 2	-	Since we used to allow [ and ] in the urlIdent, we have to modify
	 *			the regex for conversation routes that affect any topics with [ and ] in the title
	 *			so that they can be routed.
	 */
	public function step_2($data = NULL)
	{
		// this step has been moved to 505rc1, because the conversation route's regex has been updated in 5.0.5
		$this->skip_message();
	}
}

class vB_Upgrade_504rc1 extends vB_Upgrade_Version
{
	/**
	 * Step 1 - Add new sigpicnew table
	 */
	public function step_1()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . "sigpicnew"),
			"CREATE TABLE " . TABLE_PREFIX . "sigpicnew (
				userid int(10) unsigned NOT NULL default '0',
				filedataid int(10) unsigned NOT NULL default '0',
				PRIMARY KEY  (userid),
				KEY filedataid (filedataid)
			)",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	/**
	 * Update site navbars
	 */
	public function step_2()
	{
		$this->syncNavbars();
	}

	/**
	 * Step 3 - Update blog nodes with new nodeoption vB_Api_Node::OPTION_AUTOSUBSCRIBE_ON_JOIN = 512;
	 */
	public function step_3($data = null)
	{
		$batchsize = 100000;

		// display what we're doing
		$this->show_message(sprintf($this->phrase['version']['504rc1']['update_blog_nodeoption']));

		// set-up constants, objects etc.
		$blogChannelId = vB_Library::instance('Blog')->getBlogChannel();
		$assertor = vB::getDbAssertor();
		// We'll be updating the node table. At about 100k, a similar update query (different parentid)
		// on the node table on the dev DB took ~5s
		$startat = intval($data['startat'] ?? 0);
		// fetch max nodeid if necessary
		if (!isset($data['max']))
		{
			$maxNodeid  = $assertor->getRow('vBInstall:getMaxChildNodeid', array('parentid' => $blogChannelId));
			$data['max'] = intval($maxNodeid['maxid']);
		}
		$max = intval($data['max']);

		// if we went through all the blog nodes, we're done
		if ($startat >= $max)
		{
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			return;
		}

		// update node table
		$assertor->assertQuery('vBInstall:updateBlogNodeOptions', [
			'setNewOption' => vB_Api_Node::OPTION_AUTOSUBSCRIBE_ON_JOIN,
			'blogChannelId' => $blogChannelId,
			'startat' => $startat,
			'batchsize' => $batchsize,
		]);

		// output progress & return for next batch
		$this->show_message(sprintf($this->phrase['core']['processed_records_x_y_z'], $startat + 1, $startat + $batchsize, $max));
		return array('startat' => ($startat + $batchsize), 'max' => $data['max']);
	}

	/**
	 * Step 4 - Add subscriptions for blog owners, moderators & members
	 */
	public function step_4($data = NULL)
	{
		// display what we're doing
		$this->show_message(sprintf($this->phrase['version']['504rc1']['add_blog_subscription']));

		// set-up constants, objects etc.
		$blogChannelId = vB_Library::instance('Blog')->getBlogChannel();
		$assertor = vB::getDbAssertor();

		// fetch blog channel GIT records that're missing subscriptions
		$queryResult = $assertor->assertQuery('vBInstall:fetchBlogGroupintopicMissingSubscriptions',
			array(
					'blogChannelId' => $blogChannelId
			));

		// if none found, nothing to do.
		if (!$queryResult->valid())
		{
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			return;
		}

		$subscriptionToAdd = array();
		foreach ($queryResult AS $key => $gitRow)
		{
			$subscriptionToAdd[] = array("nodeid" => $gitRow['nodeid'], "userid" => $gitRow['userid']);
		}

		// remove duplicates
		$subscriptionToAdd = array_map('unserialize', array_unique(array_map('serialize', $subscriptionToAdd)));

		// batchSize note: I do not believe this step requires batching. Tested on about 50k records to insert
		// 		which took a few seconds. I am not aware of there being that many blogs * (owner + moderators + members)
		//		in the wild.
		// Add subscription records.
		$assertor->assertQuery('vBInstall:addSubscriptionRecords', array('subscriptions' => $subscriptionToAdd));


		// finished
		$this->show_message(sprintf($this->phrase['core']['process_done']));
	}
}

class vB_Upgrade_505a2 extends vB_Upgrade_Version
{
	/**
	 * populating tagnode table based on tagcontent table
	 */
	public function step_1()
	{
		if ($this->tableExists('tagcontent'))
		{
			$log = vB::getDbAssertor()->getRow('vBInstall:upgradelog', array('script' => $this->SHORT_VERSION, 'step' => 1)); // Must match this step.

			if (empty($log))
			{
				$this->show_message($this->phrase['version']['505a2']['importing_tags']);
				vB::getDbAssertor()->assertQuery('vBInstall:importTagContent');
				$this->show_message(sprintf($this->phrase['core']['import_done']));
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
	 * populating the taglist field in the node table
	 */
	public function step_2()
	{
		$log = vB::getDbAssertor()->getRow('vBInstall:upgradelog', array('script' => $this->SHORT_VERSION, 'step' => 2)); // Must match this step.

		if (empty($log))
		{
			$this->show_message($this->phrase['version']['505a2']['updating_node_tags']);
			vB::getDbAssertor()->assertQuery('vBInstall:updateNodeTags');
			$this->show_message(sprintf($this->phrase['core']['process_done']));
		}
		else
		{
			$this->skip_message();
		}
	}


	/**
	 * Change old page meta phrases from GLOBAL group to pagemeta group
	 */
	public function step_3()
	{
		$this->show_message($this->phrase['version']['505a2']['moving_page_metadata_phrases']);
		vB::getDbAssertor()->assertQuery('vbinstall:movePageMetadataPhrases');
		$this->show_message('done');

		// We don't need to rebuild language as it will be rebuilt in final upgrades
	}
}

class vB_Upgrade_505a3 extends vB_Upgrade_Version
{
	/** The format of the profilefield has changed and needs to be regenerated */
	public function step_1()
	{
		$this->show_message(sprintf($this->phrase['core']['rebuild_x_datastore'], 'profilefield'));
		vB_Library::instance('user')->buildProfileFieldDatastore();
	}

	/** Add thumbnail caching to the video table */
	public function step_2()
	{
		$created = false;
		if (!$this->field_exists('video', 'thumbnail'))
		{
			// Create thumbnail field
			$this->add_field(
				sprintf($this->phrase['core']['altering_x_table'], 'video', 1, 2),
				'video',
				'thumbnail',
				'VARCHAR',
				array('length' => 255, 'default' => '')
			);
			$created = true;
		}

		if (!$this->field_exists('video', 'thumbnail_date'))
		{
			// Create thumbnail_date field
			$this->add_field(
					sprintf($this->phrase['core']['altering_x_table'], 'video', 2, 2),
					'video',
					'thumbnail_date',
					'INT',
					array('length' => 11, 'null' => false, 'default' => 0, 'attributes' => 'UNSIGNED')
			);
			$created = true;
		}

		if (!$created)
		{
			$this->skip_message();
		}

	}

	/**
	 * Step 3 add nodeview table
	 */
	public function step_3()
	{
		if (!$this->tableExists('nodeview'))
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'nodeview'),
				"
				CREATE TABLE " . TABLE_PREFIX . "nodeview (
					nodeid INT UNSIGNED NOT NULL DEFAULT '0',
					count INT UNSIGNED NOT NULL DEFAULT '0',
					PRIMARY KEY (nodeid)
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

	/**
	 * Step 4 update nodeviews table with thread (column views) & threadviews tables if they exist
	 */
	public function step_4($data = [])
	{
		// both thread & threadviews tables are standard tables in vB4. If, for some reason, they have dropped the threadviews table,
		// but left the thread table in-tact, rather than having 2 separate upgrade classes they should just create the threadviews
		// table & re-run the upgrader. If they dropped or truncated the thread table, then we can't import the old view counts.
		if ($this->tableExists('thread') AND $this->tableExists('threadviews'))
		{
			/*
			 *	There is a bug where sometimes the step # isn't recorded properly. It seems to affect the last step, which is
			 *	why there is a blank step_5 in this class, just so that step_4 isn't the last one.
			 *	If the upgradelog didn't record the step properly, the user will see a MySQL error:
			 *	Duplicate entry <> for key 'PRIMARY'
			 *	Rather than doing complex checks or truncating the table & rebuilding it, I've just added a blank step_5
			*/
			//We only need to run this once.
			if (empty($data['startat']))
			{
				$log = vB::getDbAssertor()->assertQuery('vBInstall:upgradelog', array('script' => '505a3', 'step' => 4)); // Must match this step.

				if ($log->valid())
				{
					$this->skip_message();
					return;
				}
			}

			// output what we're doing
			$this->show_message($this->phrase['version']['505a3']['import_thread_views']);

			$assertor = vB::getDbAssertor();
			$batchSize = 150000;	// using the dev_42 DB, this seems to be about the sweet spot, ~3s per step
			// we only update the nodeview for threads imported from vb4
			$threadId = vB_Types::instance()->getContentTypeID('vBForum_Thread');

			// grab startat
			$startat = intval($data['startat'] ?? 0);
			// grab max if using CLI & not first iteration, else fetch it from DB
			if (!empty($data['max']))
			{
				$max = intval($data['max']);
			}
			else
			{
				$max = $assertor->getRow('vBInstall:getMaxThreadid', array());
				$max = intval($max['maxid']);
			}

			// finish condition is when no thread were imported or we already processed the max oldid
			if ($max == 0 OR $max <= $startat)
			{
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}


			// import views from thread tables
			$assertor->assertQuery('vBInstall:importThreadviews',
				array(
					'oldcontenttypeid' => $threadId,
					'startat' => $startat,
					'batchsize' => $batchSize,
				)
			);

			// output current progress
			$this->show_message(sprintf($this->phrase['core']['processed_records_x_y_z'], $startat +1, min($startat + $batchSize, $max), $max));

			// kick off next batch
			return array('startat' => $startat + $batchSize, 'max' => $max);
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	 * Step 5 update nodeviews table with blog (column views) & blog_views tables if they exist
	 */
	public function step_5($data = [])
	{
		// Using basically the same logic as step_4, just different tables
		if ($this->tableExists('blog') AND $this->tableExists('blog_views'))
		{
			//We only need to run this once.
			if (empty($data['startat']))
			{
				$log = vB::getDbAssertor()->assertQuery('vBInstall:upgradelog', array('script' => '505a3', 'step' => 5)); // Must match this step.

				if ($log->valid())
				{
					$this->skip_message();
					return;
				}
			}

			// output what we're doing
			$this->show_message($this->phrase['version']['505a3']['import_blog_views']);

			$assertor = vB::getDbAssertor();
			// dev_42 only has ~200 blogs. Inserted about 350k blog starter nodes and this size takes about ~3s per step
			// the query is very similar to step_4, so I expect their times/batchSize to be equivalent
			$batchSize = 150000;
			// we only update the nodeview for blogs imported from vb4
			$blogId = vB_Api_ContentType::OLDTYPE_BLOGSTARTER;

			// grab startat
			$startat = intval($data['startat'] ?? 0);
			// grab max if using CLI & not first iteration, else fetch it from DB
			if (!empty($data['max']))
			{
				$max = intval($data['max']);
			}
			else
			{
				$max = $assertor->getRow('vBInstall:getMaxBlogid', array());
				$max = intval($max['maxid']);
			}

			// finish condition is when no thread were imported or we already processed the max oldid
			if ($max == 0 OR $max <= $startat)
			{
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}


			// import views from blog tables
			$assertor->assertQuery('vBInstall:importBlogviews',
				array(
					'oldcontenttypeid' => $blogId,
					'startat' => $startat,
					'batchsize' => $batchSize,
				)
			);

			// output current progress
			$this->show_message(sprintf($this->phrase['core']['processed_records_x_y_z'], $startat +1, ($startat + $batchSize), $max));

			// kick off next batch
			return array('startat' => $startat + $batchSize, 'max' => $max);
		}
		else
		{
			$this->skip_message();
		}
	}

	/*
	 *	Step 6 - this step doesnt' do anything, but is necessary. For some reason, the last step in each class
	 *		doesn't seem to get recorded properly in the upgradelog. Since step_4/step_5 require a proper record,
	 *		I'm leaving this just so that step_5 isn't the last step.
	 */
	public function step_6($data = NULL)
	{
		/*
						 __		   					 ___________  ________
					___./ /     _.---.				|		  	| \_   __/
					\__  (__..-`       \			|	^		|  /  /
					   \            O   |			|____		|_/	 /
						`..__.   ,=====/			|_______________/
						  `._/_.'_____/

		 * IF ANOTHER STEP IS ADDED, PLEASE REPLACE THIS ONE. HOWEVER, ADD A NOTE ON THAT STEP THAT
		 * IF STEP_6 IS TO BE REMOVED, A BLANK ONE SHOULD BE INSERTED AGAIN.
		 */
		$this->skip_message();
	}
}

class vB_Upgrade_505a4 extends vB_Upgrade_Version
{
	/**
	 * Set systemgroupid for those groups
	 * Needed here due beta maintenance, we don't want to rerun old upgraders for this
	 */
	public function step_1()
	{
		$this->show_message(sprintf($this->phrase['core']['altering_x_table'], 'widegetinstance', 1, 1));
		vB::getDbAssertor()->assertQuery('vBInstall:makeWidgetInstanceConfUtf8');
	}

	/*
	 *	Step 2 - Make sure old pages have GUID
	 */
	public function step_2()
	{
		$this->show_message(sprintf($this->phrase['version']['505a4']['fix_page_guid']));

		$assertor = vB::getDbAssertor();
		$pages = $assertor->getRows('getPagesWithoutGUID');

		foreach ($pages as $page)
		{
			$assertor->update('page', array('guid' => vB_GUID::get()), array('pageid' => $page['pageid']));
		}

		$this->show_message(sprintf($this->phrase['core']['process_done']));
	}

	/**
	 * Scan and fix filedata refcount
	 */
	public function step_3($data = null)
	{
		$batchsize = 1000;

		$assertor = vB::getDbAssertor();
		$startat = intval($data['startat'] ?? 0);
		$nextid = $assertor->getRow('vBInstall:getNextZeroRefcount', array('startat' => $startat));

		// Check if any users have custom folders
		if (empty($nextid) OR !empty($nextid['errors']) OR empty($nextid['filedataid']))
		{
			if (empty($startat))
			{
				$this->skip_message();
				return;
			}
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			return;
		}

		if (empty($startat))
		{
			$this->show_message($this->phrase['version']['505a4']['fix_filedata_refcount']);
		}

		$startat = $nextid['filedataid'];
		// Get the users for import
		$this->show_message(sprintf($this->phrase['core']['processing_records_x'], $batchsize));
		$assertor->assertQuery('vbinstall:fixRefCount', array('startat' => $startat, 'batchsize' => $batchsize));
		return array('startat' => ($startat + $batchsize), 'maxid' => $maxid);
	}
}

class vB_Upgrade_505rc1 extends vB_Upgrade_Version
{
	/**
	 * Step 1 - This is exactly the same as what 504a3 step 1 used to do, except we're now allowing underscore _ in the
	 *	urlIdents and as such the step has been moved here so that the conversation regexes will be properly updated.
	 */
	public function step_1($data = NULL)
	{
		// don't have a DB with more than a few hundred routes to test any higher batch sizes
		$batchsize = 200;

		$this->show_message(sprintf($this->phrase['version']['505rc1']['update_conversation_route_regex']));
		$assertor = vB::getDbAssertor();
		$startat = intval($data['startat'] ?? 0);

		// fetch max routeid if necessary
		if (!isset($data['max']))
		{
			$maxRouteid  = $assertor->getRow('vBInstall:getMaxRouteid', array());
			$data['max'] = intval($maxRouteid['routeid']);
		}
		$max = intval($data['max']);

		// if we went through all the routes, we're done
		if ($startat >= $max)
		{
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			return;
		}

		// grab conversations in current batch
		$conversationRoutes  = $assertor->assertQuery('vBInstall:getConversationRoutes', array('startat' => $startat, 'batchsize' => $batchsize));

		// nothing to update this batch, kick off next batch
		if (!$conversationRoutes->valid())
		{
			$this->show_message(sprintf($this->phrase['core']['processed_records_x_y_z'], $startat + 1, min($startat + $batchsize, $max), $max));
			return array('startat' => ($startat + $batchsize), 'max' => $data['max']);
		}

		// construct params for method query updateConversationRouteRegex
		$routes = array();
		foreach($conversationRoutes AS $key => $routeRow)
		{
			// 500b27 could've broken custom URLs, but since it wasn't a vb4 thing, I'm assuming
			// that none existed prior to 500b27 and thus none require fixing.
			// However, we need to go through all the conversation routes and make sure that the
			// prefixes are preg_quoted.
			// Custom URLs are defined as regex == prefix at the moment.
			if ($routeRow['regex'] !== preg_quote($routeRow['prefix']))
			{
				$route['routeid'] = $routeRow['routeid'];

				$route['regex'] = preg_quote($routeRow['prefix']) . '/' . vB5_Route_Conversation::REGEXP;
				$route['customregex'] = preg_quote($routeRow['prefix']);
				$routes[] = $route;
			}
		}

		// update regex
		$assertor->assertQuery('vBInstall:updateConversationRouteRegex', array('routes' => $routes));

		// output progress & return for next batch
		$this->show_message(sprintf($this->phrase['core']['processed_records_x_y_z'], $startat + 1, min($startat + $batchsize, $max), $max));
		return array('startat' => ($startat + $batchsize), 'max' => $data['max']);
	}

	/**
	 * Step 2	-	This is exactly the same as what 504a3 step 2 used to do, except we're now allowing underscore _ in the
	 *		urlIdents and as such the step has been moved here so that the conversation regexes will be properly updated.
	 *		Note that $oldRegex does not have an underscore in 505, as opposed to 504.
	 */
	public function step_2($data = NULL)
	{
		// it's gonna join on the the node table. Each step took a couple seconds at most on the dev DB
		$batchsize = 100000;

		$this->show_message(sprintf($this->phrase['version']['505rc1']['update_conversation_route_old_regex']));
		$assertor = vB::getDbAssertor();
		$startat = intval($data['startat'] ?? 0);

		// note that the old regex didn't have _\\[\\]. We are now disallowing [ & ], but we want to keep the
		// old regex for any prefixes that got a [ or ] in it, other wise they can't be routed.
		$oldRegex = '(?P<nodeid>[0-9]+)(?P<title>(-[^!@\\#\\$%\\^&\\*\\(\\)\\+\\?/:;"\'\\\\,\\.<>= ]*)*)(?:/page(?P<pagenum>[0-9]+))?';

		// fetch max nodeid if necessary
		if (!isset($data['max']))
		{
			$maxNodeid  = $assertor->getRow('vBInstall:getMaxNodeid', array());
			$data['max'] = intval($maxNodeid['maxid']);
		}
		$max = intval($data['max']);

		// if we went through all the routes, we're done
		if ($startat >= $max)
		{
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			return;
		}

		// grab conversations in current batch
		$conversationRoutes  = $assertor->assertQuery('vBInstall:getConversationRoutesRequiringOldRegex', array('startat' => $startat, 'batchsize' => $batchsize));

		// nothing to update this batch, kick off next batch
		if (!$conversationRoutes->valid())
		{
			$this->show_message(sprintf($this->phrase['core']['processed_records_x_y_z'], $startat + 1, $startat + $batchsize, $max));
			return array('startat' => ($startat + $batchsize), 'max' => $data['max']);
		}

		// construct params for method query updateConversationRouteRegex
		$routes = array();
		foreach($conversationRoutes AS $key => $routeRow)
		{
			// Skip custom URLs, same reasoning as step 1
			if ($routeRow['regex'] !== preg_quote($routeRow['prefix']))
			{
				$route['routeid'] = $routeRow['routeid'];

				// we have to use the old regex instead of vB5_Route_Conversation::REGEXP.
				// Prefixes are based on channels, and there could be a topic with brackets
				// in it that was in a channel without any reserved characters. If we just
				// update all conversations with the new regex that disallows [ & ], any old topics with brackets would be broken.
				// However, any *new* conversation routes created will have the updated regexp
				$route['regex'] = preg_quote($routeRow['prefix']) . '/' . $oldRegex;
				$route['customregex'] = preg_quote($routeRow['prefix']);
				$routes[] = $route;
			}
		}

		// update regex
		$assertor->assertQuery('vBInstall:updateConversationRouteRegex', array('routes' => $routes));

		// output progress & return for next batch
		$this->show_message(sprintf($this->phrase['core']['processed_records_x_y_z'], $startat + 1, $startat + $batchsize, $max));
		return array('startat' => ($startat + $batchsize), 'max' => $data['max']);
	}

	/**
	 * Drop userid_forumid index from moderator table.
	 *
	 */
	public function step_3()
	{
		$this->drop_index(
			sprintf($this->phrase['core']['altering_x_table'], 'moderator', 1, 3),
			'moderator',
			'userid_forumid'
		);
	}

	/**
	 * Add userid_nodeid index on moderator table.
	 *
	 */
	public function step_4()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'moderator', 2, 3),
			'moderator',
			'userid_nodeid',
			array('userid', 'nodeid'),
			'UNIQUE'
		);
	}

	/**
	 * Add nodeid index on moderator table.
	 *
	 */
	public function step_5()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'moderator', 3, 3),
			'moderator',
			'nodeid',
			'nodeid'
		);
	}
}

class vB_Upgrade_506a1 extends vB_Upgrade_Version
{

	/** We have four new admin permissions. We should set those for non-CLI users
	 *
	 */
	public function step_1($data = NULL)
	{
		//We don't want to run this except in the case that the original install was pre-5.1.0.
		$assertor = vB::getDbAssertor();
		$check = $assertor->assertQuery('vBInstall:upgradelog', array('script' => '505rc1'));

		if ($check->valid())
		{
			// we should only run this once.
			$check = $assertor->assertQuery('vBInstall:upgradelog', array('script' => '506a1', 'step' => '1'));

			if ($check->valid())
			{
				$this->skip_message();
			}
			else
			{
				$this->show_message($this->phrase['version']['506a1']['updating_admin_permissions']);
				/*get the administrator permissions*/
				$parser = new vB_XML_Parser(false, DIR . '/includes/xml/bitfield_vbulletin.xml');
				$bitfields = $parser->parse();
				$adminperms = array();

				foreach ($bitfields['bitfielddefs']['group'] AS $topGroup)
				{
					if (($topGroup['name'] == 'ugp'))
					{
						foreach ($topGroup['group'] AS $group)
						{
							if ($group['name'] == 'adminpermissions')
							{
								foreach ($group['bitfield'] as $fielddef)
								{
									$adminperms[$fielddef['name']] = $fielddef['value'];
								}
								break;
							}
						}
					}
				}


				$changes = array(array('old' => 'canadminstyles', 'new' => 'canadmintemplates'), array('old' => 'canadminsettings', 'new' => 'canadminsettingsall'),
					array('old' => 'canadminimages', 'new' => 'cansetserverconfig'), array('old' => 'canadminusers', 'new' => 'cansetserverconfig'),
					array('old' => 'canadminthreads', 'new' => 'cansetserverconfig'), array('old' => 'canadminmaintain', 'new' => 'canuseallmaintenance'),
					array('old' => 'canadminsettings', 'new' => 'cansetserverconfig'));

				foreach ($changes as $change)
				{
 					if (!empty($adminperms[$change['old']]) AND !empty($adminperms[$change['new']]))
					{
						$assertor->assertQuery('vBInstall:updateAdminPerms', array('existing' => $adminperms[$change['old']], 'new' => $adminperms[$change['new']]));
					}
				}
			}
		}
		else
		{
			$this->skip_message();
		}
	}

	/*
	 *	Step 2: Required here because step_1 uses a upgradelog check, and thus cannot be the
	 *		last step. See VBV-12130
	 */
	public function step_2($data = NULL)
	{
		$this->skip_message();
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 112204 $
|| #######################################################################
\*=========================================================================*/
