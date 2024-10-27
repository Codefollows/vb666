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

class vB_Upgrade_600a1 extends vB_Upgrade_Version
{
	public $PREV_VERSION = '5.7.4+';
	public $VERSION_COMPAT_STARTS = '5.7.4';
	public $VERSION_COMPAT_ENDS   = '5.7.99';

	// Update some meta data for refactored nodevote datastore struture
	public function step_1()
	{
		$this->show_message($this->phrase['version'][$this->SHORT_VERSION]['rebuilding_nodevotes']);
		// Changed how we do the string-keying so that any character is allowed for the votetype labels.
		vB_Library::instance('nodevote')->reloadNodevoteMetaDataFromDB();
	}

	// Some of the reaction save logic now requires this new table to exist first.
	public function step_2()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'reactionoption'),
			"
				CREATE TABLE `" . TABLE_PREFIX . "reactionoption` (
					`votetypeid` SMALLINT UNSIGNED NOT NULL,
					`enabled` TINYINT SIGNED NOT NULL DEFAULT '0',
					`user_rep_factor` TINYINT SIGNED NOT NULL DEFAULT '0',
					`user_like_countable` TINYINT SIGNED NOT NULL DEFAULT '0',
					`emojihtml` MEDIUMTEXT NOT NULL,
					`system` TINYINT SIGNED NOT NULL DEFAULT '0',
					`guid` VARCHAR(255) NULL DEFAULT NULL,
					`order` INT SIGNED NOT NULL DEFAULT '0',
					`filedataid` INT UNSIGNED NOT NULL DEFAULT '0',
					UNIQUE INDEX `votetypeid` (`votetypeid`),
					INDEX `filedataid` (`filedataid`)
				) ENGINE = " . $this->hightrafficengine . "
			",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	// Add default reaction nodevote data
	public function step_3()
	{
		$this->show_message($this->phrase['version'][$this->SHORT_VERSION]['inserting_topic_reactions']);
		$this->addDefaultReactionsGroup();
		// Updated addDefaultReactions() will add AND enable the new emojis by default.
		$this->addDefaultReactions();
	}

	// Porting reputation to reactions: Insert "thumbs up" reactions for any old "likes"
	public function step_4($data = [])
	{
		$thumbsupReaction = $this->getThumbsUpNodevoteData();
		// If something went wrong with step_2() and we don't have the thumbs up reaction inserted, we cannot continue.
		// Skip message for now. May want a different message here?
		if (empty($thumbsupReaction))
		{
			$this->skip_message();
			return;
		}

		if (empty($data['startat']))
		{
			// We're starting with more than just the "thumbs up" as "reputable". In order to make this step re-entrant
			// AFTER we've upgraded to vb6, we cannot run this more than once, because then we might accidentally
			// count a vb6-added reputation record (e.g. from a "grinning face" reaction) as "thumbs up".
			if ($this->iRan(__FUNCTION__))
			{
				return;
			}

			$this->show_message(sprintf($this->phrase['vbphrase']['update_table_x'], TABLE_PREFIX . 'nodevote', 1, 2));
		}

		/*
		$thumbsupReaction:
    [label] => thumbs up
    [votetypeid] => 11418
    [votegroupid] => 45
		 */
		// Copy like counts over
		$queryParams = [
			'votetypeid' => $thumbsupReaction['votetypeid'],
			'votegroupid' => $thumbsupReaction['votegroupid'],
		];
		$walker = new vB_UpdateTableWalker(vB::getDBAssertor());
		$walker->setBatchSize($this->getBatchSize('xlarge', __FUNCTION__));
		$walker->setMaxQuery('vBInstall:getMaxReputationid');
		$walker->setNextidQuery('vBForum:reputation', 'reputationid');
		$walker->setCallbackQuery('vBInstall:copyReputationToNodevotes', $queryParams);

		return $this->updateByWalker($walker, $data);
	}

	// Porting reputation to reactions: fix aggregate counts after nodevotes were potentially changed in step_3
	public function step_5($data = [])
	{
		$thumbsupReaction = $this->getThumbsUpNodevoteData();
		// If something went wrong with step_2() and we don't have the thumbs up reaction inserted, we cannot continue.
		// Skip message for now. May want a different message here?
		if (empty($thumbsupReaction))
		{
			$this->skip_message();
			return;
		}

		if (empty($data['startat']))
		{
			$this->show_message(sprintf($this->phrase['vbphrase']['update_table_x'], TABLE_PREFIX . 'nodevote', 2, 2));
		}

		$walker = new vB_UpdateTableWalker(vB::getDBAssertor());
		$walker->setBatchSize($this->getBatchSize('xlarge', __FUNCTION__));
		$walker->setMaxQuery('vBInstall:getMaxNodevoteNodeid');
		$walker->setNextidQuery('nodevote', 'nodeid');
		$walker->setCallbackQuery('fixNodevoteAggregate');

		return $this->updateByWalker($walker, $data);
	}
}

class vB_Upgrade_600a3 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'systemevent'),
			"
				CREATE TABLE " . TABLE_PREFIX . "systemevent (
					systemeventid INT NOT NULL AUTO_INCREMENT,
					runafter INT NOT NULL,
					priority INT NOT NULL,
					data BLOB NOT NULL,
					processkey VARBINARY(50) NULL,
					processtime INT NOT NULL,
					status VARCHAR(50) NOT NULL,
					errormessage TEXT NOT NULL,
					KEY processkey(processkey),
					KEY status (status, runafter),
					PRIMARY KEY (systemeventid)
				) ENGINE = " . $this->hightrafficengine . "
			",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}
}

class vB_Upgrade_600a7 extends vB_Upgrade_Version
{
	public function step_1($data = [])
	{
		return $this->file_load_legacy_style($data);
	}

	public function step_2()
	{
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table_x'], TABLE_PREFIX . 'style', 1, 2));

		$db = vB::getDBAssertor();

		//We could do this an one custom query including the looped updates below but it's not worth the trouble
		//There just aren't going to be that many styles that are direct children of the root.
		$legacy = $db->getRow('style', [
			vB_dB_Query::COLUMNS_KEY => ['styleid'],
			'guid' => 'vbulletin-theme-legacy-668e390263dc4ebd89f967b785bc47da',
		]);

		//these styles should have been created in step 1, but if not ... not much we can do about it.
		if ($legacy)
		{
			$legacywritable = $db->getRow('style', [
				vB_dB_Query::COLUMNS_KEY => ['styleid', 'parentlist'],
				vB_dB_Query::CONDITIONS_KEY => [
					'parentid' => $legacy['styleid'],
					['field' => 'guid', 'value' => '-writable-', vB_dB_Query::OPERATOR_KEY => vB_dB_Query::OPERATOR_INCLUDES],
				]
			]);

			$stylelib = vB_Library::instance('style');
			if ($legacywritable)
			{
				//Assume any styles that inherit directly from the master style and *aren't* themes are vB5 styles and
				//move them to the vB5 style shim.
				$styles = $db->getColumn('style', 'styleid', [vB_dB_Query::CONDITIONS_KEY => [
					'parentid' => -1,
					['field' => 'guid', vB_dB_Query::OPERATOR_KEY => vB_dB_Query::OPERATOR_ISNULL],
				]]);

				foreach ($styles AS $styleid)
				{
					$db->update('style',
						[
							'parentid' => $legacywritable['styleid'],
							'parentlist' => $styleid . ',' . $legacywritable['parentlist'],
						],
						['styleid' => $styleid]
					);

					//force the style lib to update the internal cache to avoid overwriting
					//the parentlist later on.
					$stylelib->fetchTemplateParentlist($styleid, true);
				}
			}
		}

		//we need to rebuild styles after this but the final upgrade script will handle that.
	}

	public function step_3()
	{
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table_x'], TABLE_PREFIX . 'style', 2, 2));

		$db = vB::getDBAssertor();

		$id = $db->insert('style', [
			'title' => $this->phrase['install']['default_style'],
			'parentid' => -1,
			'parentlist' => '',
			'templatelist' => '',
			'replacements' => '',
			'userselect' => 0,
			'displayorder' => 1,
		]);


		//the parent list needs to refer to the styleid which we don't have until the record is created.
		if ($id)
		{
			$db->update('style', ['parentlist' => "$id,-1"], ['styleid' => $id]);
		}
	}
}

class vB_Upgrade_600a8 extends vB_Upgrade_Version
{
	//update the vB5 shim to
	public function step_1()
	{
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table_x'], TABLE_PREFIX . 'style', 1, 2));

		$db = vB::getDBAssertor();

		//we could do this an one custom query but it's not worth the trouble.
		$legacy = $db->update('style',
			['displayorder' => 5],
			['guid' => 'vbulletin-theme-legacy-668e390263dc4ebd89f967b785bc47da']
		);
	}

	public function step_2()
	{
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table_x'], TABLE_PREFIX . 'style', 2, 2));

		$db = vB::getDBAssertor();

		//we could do this an one custom query but it's not worth the trouble.
		$legacy = $db->update('style',
			['displayorder' => 10],
			['guid' => 'vbulletin-theme-parent-readonly-5660da3dd0cc42.92747689']
		);
	}
}

class vB_Upgrade_600b2 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->set_option2('cpstyleimageext', 'default');
	}
}

class vB_Upgrade_601a3 extends vB_Upgrade_Version
{
	private $pmrouteguid = 'vbulletin-4ecbdacd6aac05.50909921';
	public function step_1()
	{
		$db = vB::getDBAssertor();
		$oldroute = $db->getRow('routenew', [
			'guid' => $this->pmrouteguid,
			'prefix' => 'privatemessage',
		]);
		if (!$oldroute)
		{
			$this->skip_message();
			return;
		}

		$conflict = $db->getRow('routenew', [vB_dB_Query::CONDITIONS_KEY => [
			['field' =>'prefix', 'value' => 'messagecenter', 'operator' => vB_dB_Query::OPERATOR_EQ],
			// ignore ones that are redirect301, which happens if there was a custom page with 'messagecenter' as the url, and
			// they moved the page to another URL.
			['field'=>'redirect301', 'operator' => vB_dB_Query::OPERATOR_ISNULL],
		]]);
		if ($conflict)
		{
			// we can't just dedupe this URL, because there are some places that hard-code the privatemessage/messagecenter URL,
			// particularly in JS
			$this->add_adminmessage('cannot_update_pm_route',
				[
					'dismissable' => 1,
					'status'  => 'undone',
				],
				false,
				[$this->LONG_VERSION]
			);
			$this->show_message(sprintf($this->phrase['version']['601a3']['cannot_update_pm_route'], $this->LONG_VERSION));
			return;
		}

		$this->show_message($this->phrase['version']['601a3']['updating_pm_route']);

		$prefix = vB5_Route_PrivateMessage::DEFAULT_PREFIX;
		$regex = $prefix . '/' . vB5_Route_PrivateMessage::REGEXP;
		$db->update('routenew',
			[
				'prefix' => $prefix,
				'regex' => $regex,
			],
			['guid' => $this->pmrouteguid]
		);

		// Clean up any redirect301's
		$conflict = $db->delete('routenew', [
			['field' =>'prefix', 'value' => 'messagecenter', 'operator' => vB_dB_Query::OPERATOR_EQ],
			['field'=>'redirect301', 'operator' => vB_dB_Query::OPERATOR_ISNOTNULL],
			// the default pm route SHOULDN'T have a redirect301 set to anything, but JUST IN CASE avoid nuking
			// the default route.
			['field' =>'guid', 'value' => $this->pmrouteguid, 'operator' => vB_dB_Query::OPERATOR_NE],
		]);
	}

	public function step_2()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'undolog'),
			"
				CREATE TABLE " . TABLE_PREFIX . "undolog (
					undoid INT NOT NULL AUTO_INCREMENT,
					class VARCHAR(50) NOT NULL,
					userid INT UNSIGNED NOT NULL,
					data BLOB NOT NULL,
					created INT NOT NULL,
					deleteafter INT NOT NULL,
					KEY lookup(userid, class),
					KEY deleteafter (deleteafter),
					PRIMARY KEY (undoid)
				) ENGINE = " . $this->hightrafficengine . "
			",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}
}

class vB_Upgrade_601a4 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->add_field2(
			'nodefield',
			'required',
			'TINYINT',
			[
				'attributes' => 'UNSIGNED',
				'null'       => false,
				'default'    => 0,
			]
		);
	}
}

class vB_Upgrade_602a1 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'styleschedule'),
			"
				CREATE TABLE `" . TABLE_PREFIX . "styleschedule` (
					`scheduleid` SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
					`styleid` SMALLINT UNSIGNED NOT NULL,
					`enabled` TINYINT NOT NULL DEFAULT '0',
					`startdate` DATETIME NOT NULL DEFAULT '1000-01-01 00:00:00',
					`startdate_tzoffset` CHAR(4) NOT NULL DEFAULT '0',
					`enddate` DATETIME NOT NULL DEFAULT '9999-12-31 23:59:59',
					`enddate_tzoffset` CHAR(4) NOT NULL DEFAULT '0',
					`useyear` TINYINT NOT NULL DEFAULT '0',
					`priority` SMALLINT UNSIGNED NOT NULL DEFAULT '10',
					`overridechannelcustom` TINYINT NOT NULL DEFAULT '0',
					`overrideusercustom` TINYINT NOT NULL DEFAULT '0',
					`title` VARCHAR(250) NOT NULL DEFAULT ''
				) ENGINE = " . $this->hightrafficengine . "
			",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_2()
	{
		// This used to insert sample schedules, but due to the issues with getting this working on fresh installs,
		// we decided to scrap the samples for now.
		$this->skip_message();
	}
}

class vB_Upgrade_602a2 extends vB_Upgrade_Version
{
	// only affects alpha 1 builds only, `timezoneoffset` was spit into `startdate_tzoffset` &
	// `enddate_tzoffset`.
	public function step_1()
	{
		if ($this->field_exists('styleschedule', 'startdate_tzoffset'))
		{
			$this->skip_message();
			return;
		}

		// AFAIK the actual field changes are done at the end, so we can't do things like
		// "add column and copy data" in a single step.
		$this->add_field2(
			'styleschedule',
			'startdate_tzoffset',
			'CHAR',
			[
				'length' => 4,
				'null'       => false,
				'default'    => '0',
			]
		);
		$this->add_field2(
			'styleschedule',
			'enddate_tzoffset',
			'CHAR',
			[
				'length' => 4,
				'null'       => false,
				'default'    => '0',
			]
		);
	}

	// copy alpha1's timezoneoffset to alpha2's enddate_tzoffset
	public function step_2()
	{
		if (!$this->field_exists('styleschedule', 'timezoneoffset'))
		{
			$this->skip_message();
			return;
		}

		$this->show_message($this->phrase['version']['602a2']['adding_start_end_timezones']);
		$assertor = vB::getDbAssertor();
		$assertor->assertQuery('vBInstall:copyStyleschedleTzOffsetToStartEndTzoffsets');
		$this->drop_field2('styleschedule', 'timezoneoffset');
	}

	// Some of the reaction save logic now requires this new table to exist first.
	public function step_3()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'reactionoption'),
			"
				CREATE TABLE `" . TABLE_PREFIX . "reactionoption` (
					`votetypeid` SMALLINT UNSIGNED NOT NULL,
					`enabled` TINYINT SIGNED NOT NULL DEFAULT '0',
					`user_rep_factor` TINYINT SIGNED NOT NULL DEFAULT '0',
					`user_like_countable` TINYINT SIGNED NOT NULL DEFAULT '0',
					`emojihtml` MEDIUMTEXT NOT NULL,
					`system` TINYINT SIGNED NOT NULL DEFAULT '0',
					`guid` VARCHAR(255) NULL DEFAULT NULL,
					`order` INT SIGNED NOT NULL DEFAULT '0',
					`filedataid` INT UNSIGNED NOT NULL DEFAULT '0',
					UNIQUE INDEX `votetypeid` (`votetypeid`),
					INDEX `filedataid` (`filedataid`)
				) ENGINE = " . $this->hightrafficengine . "
			",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	// Add the new reactions for VB6-213
	public function step_4()
	{
		// Re-using old version's upgrade phrase.
		$this->show_message($this->phrase['version']['600a1']['inserting_topic_reactions']);
		// Updated addDefaultReactions() will add AND enable the new emojis by default.
		$this->addDefaultReactions();
	}
}

class vB_Upgrade_602a3 extends vB_Upgrade_Version
{
	public function step_1($data = [])
	{
		if (!$this->tableExists('announcement'))
		{
			$this->skip_message();
			return;
		}

		if (empty($data['startat']))
		{
			$this->show_message(sprintf($this->phrase['vbphrase']['update_table_x'], TABLE_PREFIX . 'notice', 1, 1));
		}

		//We need to process these one by one but there shouldn't be that many.  Batch just to be sure.
		$walker = vB_UpdateTableWalker::getSimpleTableWalker(
			vB::getDBAssertor(),
			$this->getBatchSize(20, __FUNCTION__),
			'vBInstall:announcement',
			'announcementid',
		);

		$walker->setCallback(function($startat, $nextid)
		{
			$db = vB::getDBAssertor();
			$notice = vB_Library::instance('notice');

			$announcements = $db->select('vBInstall:announcement', [
				['field' => 'announcementid', 'value' => $startat, 'operator' => vB_dB_Query::OPERATOR_GTE],
				['field' => 'announcementid', 'value' => $nextid, 'operator' => vB_dB_Query::OPERATOR_LT],
			]);

			$row = $db->getRow('vBForum:notice', [
				vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SUMMARY,
				vB_dB_Query::COLUMNS_KEY => ['MAX(displayorder)'],
			]);
			$displayorder = $row['max'];

			foreach ($announcements AS $announcement)
			{
				//the notice and announcements use the same bitfield values except that notices don't
				//have signatures.  However we need to expand to an array if we plan to use the save
				//function.  Hard code incase we want to rename the bitfields -- it will mess with
				//reentrency if we rely on the bitfield array.
				$announcementoptions = $announcement['announcementoptions'];
				$noticeoptions = [
					'allowbbcode' => boolval($announcementoptions & 1),
					'allowhtml' => boolval($announcementoptions & 2),
					'allowsmilies' => boolval($announcementoptions & 4),
					'parseurl' => boolval($announcementoptions & 8),
				];

				//Note that unixtimestamps are in UTC regardless of what TZ you tell it in the constructor, but we happen to want that.
				//The timestamps for announcements appear to be for UTC though that's not explict
				$start = new DateTimeImmutable('@' . $announcement['startdate']);
				$end = new DateTimeImmutable('@' . $announcement['enddate']);
				$format = 'd-m-Y';

				$displayorder += 10;
				$criteria = [
					'is_date_range' => ['condition1' => $start->format($format), 'condition2' => $end->format($format), 'condition3' => 1],
				];

				if ($announcement['nodeid'] != -1)
				{
					$criteria['browsing_forum_x_and_children'] = ['condition1' => $announcement['nodeid']];
				}

				// Notice titles may not be empty. Just fill it with something.
				if (empty($announcement['title']))
				{
					$announcement['title']  = 'Untitled';
				}

				$notice->save([
					'title' => $announcement['title'],
					'text' => $announcement['pagetext'],
					'displayorder' => $displayorder,
					'active' => 1,
					'persistent' => 1,
					'dismissible' => 1,
					'noticeoptions' => $noticeoptions,
					'criteria' => $criteria,
				]);
			}
		});

		return $this->updateByWalker($walker, $data);
	}

	public function step_2()
	{
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table_x'], TABLE_PREFIX . 'widgetinstance', 1, 1));

		$db = vB::getDBAssertor();
		//there should only be one result.
		$widgetid = $db->getColumn('widget', 'widgetid', ['guid' => 'vbulletin-widget_announcement-4eb423cfd6dea7.34930845']);
		$widgetid = current($widgetid);

		$widgetinstances = $db->getColumn('widgetinstance', 'widgetinstanceid', ['widgetid' => $widgetid]);
		vB_Library::instance('widget')->deleteWidgetInstances($widgetinstances, true);
	}

	public function step_3()
	{
		$this->show_message(sprintf($this->phrase['core']['delete_widget_x'], 'Announcement'));

		//thre aren't a ton of widgets/widget instances in a database.  Shouldn't need to batch and doing so will almost certainly
		//be less efficient/make things take longer
		$db = vB::getDBAssertor();

		//this is somewhat general but need to recursively clean up after container widgets -- which the announcement widget is not
		$db->assertQuery('vBInstall:deleteWidget', ['guid' => 'vbulletin-widget_announcement-4eb423cfd6dea7.34930845']);
	}

	public function step_4()
	{
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table_x'], TABLE_PREFIX . 'hook', 1, 1));
		$db = vB::getDBAssertor();
		$db->delete('hook', ['hookname' => ['announcement_total', 'announcement_after_list', 'announcement_no_announcement']]);
	}

	public function step_5()
	{
		$this->drop_field2('rssfeed', 'endannouncement');
	}

	public function step_6()
	{
		$this->drop_table('announcement');
	}

	public function step_7()
	{
		$this->drop_table('announcementread');
	}

	public function step_8()
	{
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table_x'], TABLE_PREFIX . 'contenttype', 1, 1));
		$announcementtypeid = vB_Types::instance()->getContentTypeID('vBForum_Announcement');
		if ($announcementtypeid)
		{
			$db = vB::getDBAssertor();
			$db->delete('vBForum:contenttype', ['contenttypeid' => $announcementtypeid]);
		}
	}
}

class vB_Upgrade_603a1 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$extension = 'mov';
		$db = vB::getDbAssertor();
		$row = $db->getRow('vBForum:attachmenttype', ['extension' => $extension]);

		if (!$row)
		{
			$query = getAttachmenttypeInsertQuery($this->db, [$extension]);
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

class vB_Upgrade_603a3 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'reactionoption'),
			"
				CREATE TABLE `" . TABLE_PREFIX . "reactionoption` (
					`votetypeid` SMALLINT UNSIGNED NOT NULL,
					`enabled` TINYINT SIGNED NOT NULL DEFAULT '0',
					`user_rep_factor` TINYINT SIGNED NOT NULL DEFAULT '0',
					`user_like_countable` TINYINT SIGNED NOT NULL DEFAULT '0',
					`emojihtml` MEDIUMTEXT NOT NULL,
					`system` TINYINT SIGNED NOT NULL DEFAULT '0',
					`guid` VARCHAR(255) NULL DEFAULT NULL,
					`order` INT SIGNED NOT NULL DEFAULT '0',
					`filedataid` INT UNSIGNED NOT NULL DEFAULT '0',
					UNIQUE INDEX `votetypeid` (`votetypeid`),
					INDEX `filedataid` (`filedataid`)
				) ENGINE = " . $this->hightrafficengine . "
			",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	// Populate `reactionoption` with default values. Also pulls the "enabled" data that was, before this version, ONLY saved
	// in the datastore.
	public function step_2()
	{
		// TODO: add step to delete legacy data after we're sure we don't need it?

		$assertor = vB::getDbAssertor();
		$check = $assertor->getRow('reactionoption');
		if (!empty($check))
		{
			$this->skip_message();
			return;
		}
		else
		{
			$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'reactionoption'));
		}


		/** @var vB_Library_Reactions */
		$lib = vB_Library::instance('reactions');
		// this function should not cross wires with `reactions` table so should be safe to use.
		$knownreactions = $lib->getReactionsNodevotetypes();
		$reactionLabelsByKey = array_column($knownreactions, 'label', 'votetypeid');
		$reactionIdsByLabel =  array_column($knownreactions, 'votetypeid', 'label');

		// Depending on the specific upgrade path (e.g. 5 -> 602, 600 -> 602 etc) some might already
		// have the option record set up. Only handle the ones that are missing, expected to be in the
		// 600 or 601 upgrade to 602.
		$alreadyExists = $assertor->getRows('reactionoption', [], false, 'votetypeid');
		$enabled = $this->getLegacyEmojisEnabledStatus();
		$enabledTypeids = [];
		foreach ($enabled AS $__label => $__enabled)
		{
			$__id = $reactionIdsByLabel[$__label];
			if (!isset($alreadyExists[$__id]))
			{
				$enabledTypeids[] = $__id;
			}
		}

		if (!$enabledTypeids)
		{
			$this->skip_message();
			return;
		}


		// We don't want this dynamic, because we're trying to set up the default value.
		// If any new reactions are added after this step, we don't care -- their rep/count values
		// should be handled by the new adminCP pages, or by the upgrade steps adding them if
		// they should start off with some defaults.
		$reputables = [
			'grinning face' => 1,
			'smiling face with hearts' => 1,
			'smiling face with sunglasses' => 1,
			'thumbs up' => 1,
			'face with tears of joy' => 1,
		];

		$insert = [];
		foreach ($enabledTypeids AS $__votetypeid)
		{
			$__label = $reactionLabelsByKey[$__votetypeid];
			// If we do NOT have the enabled DS set, assume this is the first time & enable all by default.
			// Otherwise, if it's missing in the enabled list, keep it disabled.
			// Note taht the legacy datstore item was keyed by label instead of votetypeid.
			$__enabled = $enabled ? ($enabled[$__label] ?? 0) : 1;
			// At this time, thke reputables & countables were hard-coded & set to be the same as each other. Avoiding using the
			// getReputableTypesAndFactors() ETC to avoid weird cyclic dependencies between reactionoption table & the library
			// during this step that's supposed to populate the table for the first time.
			$__rep = $reputables[$__label] ?? 0;
			$__count = $reputables[$__label] ?? 0;
			$insert[] = [
				$__votetypeid,
				$__enabled,
				$__rep,
				$__count,
			];
		}
		// Insert several prefixsets and prefixes
		$assertor->insertMultiple(
			'reactionoption',
			['votetypeid', 'enabled', 'user_rep_factor', 'user_like_countable'],
			$insert
		);
	}

	// Trying to remove some circular dependencies -- the upgrade step is supposed to populate `reactionoption` for the first
	// time, but vB_Library_Reactions::getEmojisEnabledStatus() may end up hitting the same table and rebuild data from that.
	private function getLegacyEmojisEnabledStatus()
	{
		$datastore = vB::getDatastore();
		$enabled = $datastore->getValue(vB_Library_Reactions::EMOJI_ENABLED_LEGACY_DS_KEY);

		if ($enabled)
		{
			$enabled = json_decode($enabled, true);
		}
		// handle cases wehre json decode fails, or ds::get missed
		$enabled = ($enabled ? $enabled : []);

		return $enabled;
	}

	public function step_3()
	{
		// Add index for recalculating user.totallikes
		$this->add_index2('nodevote', 'totallikes_agg_helper', ['userid', 'votetypeid', 'whovoted',]);
	}

	public function step_4()
	{
		// AFAIK the actual field changes are done at the end, so we can't do things like
		// "add column and copy data" in a single step.
		$this->add_field2(
			'reactionoption',
			'emojihtml',
			'MEDIUMTEXT',
			[
				'null'       => true,
				'default'    => null,
			]
		);
		$this->add_field2(
			'reactionoption',
			'system',
			'TINYINT',
			[
				'attributes' => 'SIGNED',
				'null'       => false,
				'default'    => '0',
			]
		);
		$this->add_field2(
			'reactionoption',
			'guid',
			'VARCHAR',
			[
				'length' => 255,
				'null'       => true,
				'default'    => null,
			]
		);
		$this->add_field2(
			'reactionoption',
			'order',
			'INT',
			[
				'attributes' => 'SIGNED',
				'null'       => false,
				'default'    => '0',
			]
		);
	}

	// fill data for the system default reactions
	public function step_5()
	{
		// Check for re-entry. If the admin uses this after the new 603 features and re-names a default system reaction,
		// we might misidentify certain ones that used to be keyed by label only and cause problems below. And there's
		// probably no reason to run the updates again, since doing so would just possibly wipe customized emojihtml or
		// order data.
		$assertor = vB::getDbAssertor();
		$check = $assertor->getRow('reactionoption', ['system' => 1]);
		if (!empty($check))
		{
			$this->skip_message();
			return;
		}

		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'reactionoption'));
		// Side note, using this function instead of hard-coding the *current* list means
		// future updates to the list would affect this step.
		$defaultLabels = $this->fetchDefaultReactionLabels();
		// Defaults at the time of this writing:
		// $defaults = [
		// 	'grinning face',
		// 	'smiling face with hearts',
		// 	'smiling face with sunglasses',
		// 	'enraged face',
		// 	'nauseated face',
		// 	'thumbs up',
		// 	'thumbs down',
		// 	// Added in VB6-213
		// 	'face with tears of joy',
		// 	'face blowing a kiss',
		// 	'disappointed face',
		// 	'hot beverage',
		// ];

		/** @var vB_Library_Reactions */
		$lib = vB_Library::instance('reactions');
		$knownreactions = $lib->getReactionsNodevotetypes();
		$reactionIdsByLabel =  array_column($knownreactions, 'votetypeid', 'label');
		['fulldata' => $emojionlydata,] = $lib->loadSourceEmojisData();
		$byLabel = array_column($emojionlydata, null, 'label');
		// All existing default reactions should have `reactionoption` records by this point.
		// Running on that assumption.
		foreach ($defaultLabels AS $__label)
		{
			$__data = $byLabel[$__label] ?? [];
			$__id = $reactionIdsByLabel[$__label];
			if (empty($__data))
			{
				continue;
			}
			$assertor->update('reactionoption',
				[
					'system' => 1,
					'guid' => $__label,
					'emojihtml' => $__data['emojihtml'],
					'order' => $__data['order'],
				],
				['votetypeid' => $__id]
			);
		}
		$lib->purgeReactionCaches();
	}

	public function step_6()
	{
		// delete old no longer used datastore entries...
		$datastore = vB::getDatastore();
		$delete = [
			'vb_reactions_userreputables',
			'vb_reactions_usercountables',
			'vb_emoji_enabled_byid',
		];
		foreach ($delete AS $__deleteme)
		{
			$this->show_message(sprintf($this->phrase['core']['remove_datastore_x'], $__deleteme));
			$datastore->delete($__deleteme);
		}
	}

	public function step_7()
	{
		$this->add_field2(
			'reactionoption',
			'filedataid',
			'INT',
			[
				'attributes' => 'UNSIGNED',
				'null'       => false,
				'default'    => 0,
			]
		);
		/** @var vB_Library_Reactions */
		$lib = vB_Library::instance('reactions');
		$lib->purgeReactionCaches();
	}

	public function step_8()
	{
		$this->add_index2('reactionoption', 'filedataid', ['filedataid',]);
	}

	public function step_9()
	{
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

	public function step_10()
	{
		$this->add_index2('channel', 'topicexpirehelper', ['topicexpiretype', 'nodeid', 'topicexpireseconds']);
	}
}

class vB_Upgrade_603a4 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$db = vB::getDBAssertor();
		$settingnames = [
			'google_ownership_verification_tag',
			'google_ownership_verification_enable',
			'bing_ownership_verification_tag',
			'bing_ownership_verification_enable',
		];

		$settings = $db->getColumn('setting', 'value', ['varname' => $settingnames], false, 'varname');
		// If we don't have all of the settings here (either because we're upgrading before they existed
		// or because we're reentering after they were renamed/removed) then the is nothing useful we can do.
		if (array_diff($settingnames, array_keys($settings)))
		{
			$this->skip_message();
			return;
		}

		// only retain the values if the setting is enabled.
		$newvalue = [];
		if ($settings['google_ownership_verification_enable'])
		{
			$newvalue[] = $settings['google_ownership_verification_tag'];
		}

		if ($settings['bing_ownership_verification_enable'])
		{
			$newvalue[] = $settings['bing_ownership_verification_tag'];
		}

		// We'll use the "google" value as the one that continues to exist.  We'll rename it later.
		// The "enable" options will be removed but as a standard setting update.
		$this->set_option2('google_ownership_verification_tag', implode("\n\n", $newvalue), 1, 2);
	}

	public function step_2()
	{
		$db = vB::getDBAssertor();
		$settingnames = [
			'ga_enabled',
			'ga_code',
		];

		$settings = $db->getColumn('setting', 'value', ['varname' => $settingnames], false, 'varname');

		// we only really need ga_enabled but check that ga_code also exists so we don't try to update
		// an option that isn't there.  If it's enabled there is nothing to do.
		if (array_diff($settingnames, array_keys($settings)) OR $settings['ga_enabled'])
		{
			$this->skip_message();
			return;
		}

		//otherwise blank the value (we'll be removing the enabled setting later).
		$this->set_option2('ga_code', '', 2, 2);
	}

	public function step_3()
	{
		$this->rename_option('ga_code', 'footer_code', 1, 2);
	}

	public function step_4()
	{
		$this->rename_option('google_ownership_verification_tag', 'header_code', 2, 2);
	}
}

class vB_Upgrade_603b1 extends vB_Upgrade_Version
{
	public function step_1($data = [])
	{
		if (empty($data['startat']))
		{
			$this->show_message(sprintf($this->phrase['vbphrase']['update_table_x'], TABLE_PREFIX . 'user', 1, 1));
		}

		$walker = vB_UpdateTableWalker::getSimpleTableWalkerFilter(
			vB::getDBAssertor(),
			$this->getBatchSize('xlarge', __FUNCTION__),
			'user',
			'userid',
			['privacyconsent' => -1]
		);
		$walker->setCallbackQuery('vBInstall:clearUserDelete');
		return $this->updateByWalker($walker, $data);
	}
}

class vB_Upgrade_604a2 extends vB_Upgrade_Version
{
	// Add PayPal v2 payment API
	public function step_1()
	{
		$assertor = vB::getDbAssertor();
		if (!$assertor->getRow('vBForum:paymentapi', ['classname' => 'paypal2']))
		{
			$assertor->insert('vBForum:paymentapi', [
				'title'     => 'PayPal API',
				'currency'  => 'usd,gbp,eur,aud,cad',
				'recurring' => 1,
				'classname' => 'paypal2',
				'active'    => 0,
				'settings'  => serialize([
					'client_id' => [
						'type' => 'text',
						'value' => '',
						'validate' => 'string'
					],
					'secret_key' => [
						'type' => 'text',
						'value' => '',
						'validate' => 'string'
					],
					'webhook_id' => [
						'type' => 'text',
						'value' => '',
						'validate' => 'string'
					],
					'sandbox' => [
						'type' => 'yesno',
						'value' => 0,
						'validate' => 'boolean'
					],
				]),
				'subsettings' => serialize([
					'show' => [
						'type' => 'yesno',
						'value' => 1,
						'validate' => 'boolean'
					],
					'tax' => [
						'type' => 'yesno',
						'value' => 0,
						'validate' => 'boolean'
					],
					'tax_percentage' => [
						'type' => 'text',
						'value' => '',
						'validate' => 'number'
					],
					'tax_behavior' => [
						'type' => 'select',
						'options' => ['inclusive', 'additive'],
						'value' => 'inclusive',
						'validate' => 'string'
					],
				]),
			]);
			$this->show_message(sprintf($this->phrase['vbphrase']['update_table_x'], 'paymentapi', 1, 1));
		}
		else
		{
			$this->skip_message();
		}
	}

	// Update remote catalog table
	public function step_2()
	{
		if (!$this->field_exists('paymentapi_remote_catalog', 'currency'))
		{
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'paymentapi_remote_catalog', 1, 1),
				"ALTER TABLE `" . TABLE_PREFIX . "paymentapi_remote_catalog` DROP PRIMARY KEY,
					ADD COLUMN `type` VARCHAR(30) NOT NULL DEFAULT '',
					ADD COLUMN `currency` VARCHAR(4) NOT NULL DEFAULT '#ALL',
					ADD PRIMARY KEY (`paymentapiid`, `vbsubscriptionid`, `vbsubscription_subid`, `type`, `currency`)",
				self::MYSQL_ERROR_DROP_KEY_COLUMN_MISSING
			);
		}
		else
		{
			$this->skip_message();
		}
	}
}

class vB_Upgrade_604a3 extends vB_Upgrade_Version
{
	// Update title for old paypal paymentmethod
	public function step_1()
	{
		$assertor = vB::getDbAssertor();
		$title = $assertor->getColumn('vBForum:paymentapi', 'title', ['classname' => 'paypal']);
		$title = reset($title);
		if ($title AND strtolower($title) == 'paypal')
		{
			$assertor->update('vBForum:paymentapi', ['title' => 'PayPal IPN'], ['classname' => 'paypal']);
			$this->show_message(sprintf($this->phrase['vbphrase']['update_table_x'], 'paymentapi', 1, 1));
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_2()
	{
		$templates = [
			'header' => 'page_header',
			'bare_header' => 'page_header_bare',
			'footer' => 'page_footer',
			'bare_footer' => 'page_footer_bare',
			'head_include' => 'page_head_include',
			'preheader' => 'page_preheader',
		];

		$this->rename_templates($templates);
	}

	public function step_3()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'pageviewpermission'),
			"
				CREATE TABLE `" . TABLE_PREFIX . "pageviewpermission` (
					`pageid` INT UNSIGNED NOT NULL,
					`usergroupid` INT UNSIGNED NOT NULL DEFAULT 0,
					`viewpermission` SMALLINT SIGNED NOT NULL DEFAULT 1,
					UNIQUE INDEX `permission` (`pageid`, `usergroupid`)
				) ENGINE = " . $this->hightrafficengine . "
			",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}
}

class vB_Upgrade_604a4 extends vB_Upgrade_Version
{
	// Remove general_tos from FAQ
	public function step_1()
	{
		$assertor = vB::getDbAssertor();
		$conditions = [
			'product' => 'vbulletin',
			'faqname' => 'general_tos',
			'faqparent' => 'community_overview',
		];
		$assertor->delete('vBForum:faq', $conditions);
		// note, we're relying on the upgrade finish cache clear to clear out 'vb_FAQ_Titles'
		// (see vB_Api_Help::getTitles() for where it's cached)
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table_x'], 'faq', 1, 1));
	}

	// Update article topics' displayorders from null => 0, since we're actually going to use the
	// field now.
	public function step_2()
	{
		// column default is null, so articles by default have a null displayorder. Note that as soon
		// as any displayorder is bulk saved via admincp, all articles on that page will get an int
		// displayorder, defaulting to 0. The null display order is confusing (mysql will put nulls
		// first when ASC) and not intended for sorting, so we don't want to keep them.
		// Generally there are a lot fewer articles than forum/blog/socialgroup posts, so we're not
		// batching this.
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table_x'], TABLE_PREFIX . 'node', 1, 1));
		/** @var vB_Library_Node */
		$lib = vB_Library::instance('node');
		$articleChannel = $lib->fetchArticleChannel();
		$assertor = vB::getDbAssertor();
		$assertor->assertQuery('vBInstall:updateArticleDisplayorder', ['articleroot' => $articleChannel]);
	}
}

class vB_Upgrade_605a1 extends vB_Upgrade_Version
{
	// Add mailhash & mailoption tables
	public function step_1()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'mailhash'),
			"
				CREATE TABLE `" . TABLE_PREFIX . "mailhash` (
					`userid` INT UNSIGNED NOT NULL,
					`hash` CHAR(64) NOT NULL,
					`tokencreated` INT UNSIGNED NOT NULL DEFAULT '0',
					`tokenlastused` INT UNSIGNED NOT NULL DEFAULT '0',
					PRIMARY KEY (`userid`, `hash`),
					UNIQUE KEY `userid_time` (`userid`, `tokencreated`)
				) ENGINE = " . $this->hightrafficengine . "
			",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_2()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'mailoption'),
			"
				CREATE TABLE `" . TABLE_PREFIX . "mailoption` (
					`userid` INT UNSIGNED NOT NULL,
					`emailoption` TINYINT SIGNED NOT NULL DEFAULT '0',
					`emailoptionupdated` INT UNSIGNED NOT NULL DEFAULT '0',
					PRIMARY KEY (`userid`)
				) ENGINE = " . $this->hightrafficengine . "
			",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}
}

class vB_Upgrade_605a3 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->add_field2(
			'template',
			'compiletype',
			'ENUM',
			[
				'attributes' => "('full', 'limited', 'textonly')",
				'null'       => false,
				'default'    => 'full',
			]
		);
	}

	public function step_2()
	{
		if (!$this->field_exists('template', 'textonly'))
		{
			$this->skip_message();
			return;
		}

		//this is a simple query and even with a lot of custom templates this shouldn't be burdonsome to run in one go
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table_x'], TABLE_PREFIX . 'template', 1, 3));
		vB::getDbAssertor()->update('template', ['compiletype' => 'textonly'], ['textonly' => 1]);
	}

	public function step_3()
	{
		$this->drop_field2('template', 'textonly');
	}

	public function step_4()
	{
		// standardize the replacement vars as being "textonly".  It doesn't particularly matter but it's closer
		// to how replacement var "templates" are treated than the full compile type.
		// this is a simple query and even with a lot of replacement vars this shouldn't be burdonsome to run in one go
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table_x'], TABLE_PREFIX . 'template', 2, 3));
		vB::getDbAssertor()->update('template', ['compiletype' => 'textonly'], ['templatetype' => 'replacement']);
	}

	// Add adinstance table
	public function step_5()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'adinstance'),
			"
				CREATE TABLE `" . TABLE_PREFIX . "adinstance` (
					`adid` INT UNSIGNED NOT NULL auto_increment,
					`adlocation` VARCHAR(250) NOT NULL DEFAULT '',
					`displayorder` INT UNSIGNED NOT NULL DEFAULT '0',
					`active` SMALLINT UNSIGNED NOT NULL DEFAULT '0',
					PRIMARY KEY (`adid`, `adlocation`),
					INDEX `active_count` (`active`, `adid`)
				) ENGINE = " . $this->hightrafficengine . "
			",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	// Move data into the adinstance table
	public function step_6()
	{
		// If legacy column adlocation does not exist, assume we've already gone through this & next steps
		if (!$this->field_exists('ad', 'adlocation'))
		{
			$this->skip_message();
			return;
		}

		$assertor = vB::getDbAssertor();
		$this->show_message($this->phrase['version']['605a3']['populate_adinstance']);
		// AFAIK there shouldn't be enough ad records to make this require batching
		$assertor->assertQuery('vBInstall:populateAdinstanceWithLegacyData');
		//$this->drop_field2('ad', 'adlocation');
	}

	public function step_7()
	{
		if ($this->field_exists('ad', 'globally_active'))
		{
			$this->skip_message();
			return;
		}
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'ad', 1, 3),
			// active -> globalactive -- we're going to have specific adinstance.active, which will be overriden by globalactive == 0.
			// Renaming it to reduce potential confusion especialy if we do table joins for data returns.
			"ALTER TABLE `" . TABLE_PREFIX . "ad`
				CHANGE COLUMN `active` `globally_active` SMALLINT UNSIGNED NOT NULL DEFAULT '0'
			",
			self::MYSQL_ERROR_DROP_KEY_COLUMN_MISSING
		);
	}

	public function step_8()
	{
		$this->drop_field2('ad', 'adlocation', 2, 3);
		$this->drop_field2('ad', 'displayorder', 3, 3);
	}

	// Handle incorrect defaults and null values in the template table.  This shouldn't happen but apparently can
	// Some changes in 6.0.5 make the effects of this worse so try to clean it up.
	public function step_9()
	{
		$this->show_message(sprintf($this->phrase['core']['altering_x_table'], TABLE_PREFIX . 'template', 1, 1));
		vB::getDbAssertor()->assertQuery('vbinstall:alterTemplateType');
	}

	public function step_10()
	{
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table_x'], TABLE_PREFIX . 'template', 3, 3));
		vB::getDbAssertor()->assertQuery('vbinstall:updateNullTemplateType');
	}
}

class vB_Upgrade_606a1 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->show_message($this->phrase['version']['606a1']['remove_reputationlevel_phrases']);
		$assertor = vB::getDbAssertor();
		$assertor->delete('phrase', ['fieldname' => 'reputationlevel']);
		$assertor->delete('phrasetype', ['fieldname' => 'reputationlevel']);
	}

	public function step_2()
	{
		$this->drop_field2('language', 'phrasegroup_reputationlevel');
	}

	public function step_3()
	{
		// insert the default userranks...
		$this->show_message($this->phrase['version']['606a1']['inserting_default_ranks']);
		$assertor = vB::getDbAssertor();
		$values = get_default_reputationranks();
		$first = reset($values);
		$columns = array_keys($first);
		$assertor->insertMultiple('vBForum:ranks', $columns, $values);

		// Need to clear the ranks cached in datastore after modifying.
		$datastore = vB::getDatastore();
		$datastore->delete('ranks');
	}

	public function step_4()
	{
		$this->drop_field2('user', 'reputationlevelid');
	}

	public function step_5()
	{
		$this->drop_table('reputationlevel');
	}

	public function step_6()
	{
		$this->add_adminmessage(
			'after_upgrade_rebuild_user_ranks',
			[
				'dismissible' => 1,
				'script'      => 'misc.php',
				'action'      => 'updateuser',
				'execurl'     => 'misc.php?do=updateuser&perpage=1000&startat=0&autoredirect=1',
				'method'      => 'post',
				'status'      => 'undone',
			]
		);
	}
}

class vB_Upgrade_606a2 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->add_field2(
			'userpromotion',
			'days_since_lastpost',
			'INT',
			[
				'attributes' => 'UNSIGNED',
				'null'       => false,
				'default'    => 0,
			]
		);
		$this->long_next_step();
	}

	public function step_2()
	{
		$this->add_index2('user', 'lastactivity', ['lastactivity',], 1, 2);
		$this->long_next_step();
	}

	public function step_3()
	{
		$this->add_index2('user', 'lastpost', ['lastpost',], 2, 2);
	}

	public function step_4()
	{
		// If we're reentering this step and have already renamed the new sigpic table we do *not* want to do this again.
		if ($this->field_exists('sigpic', 'filedata'))
		{
			$this->show_message(sprintf($this->phrase['core']['rename_table_x_to_y'], TABLE_PREFIX . 'sigpic', TABLE_PREFIX . 'sigpicold'));
			$result = vB::getDbAssertor()->assertQuery('vBInstall:renameOldSigpicTable');
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_5()
	{
		if ($this->tableExists('sigpicnew'))
		{
			$this->show_message(sprintf($this->phrase['core']['rename_table_x_to_y'], TABLE_PREFIX . 'sigpicnew', TABLE_PREFIX . 'sigpic'));
			$result = vB::getDbAssertor()->assertQuery('vBInstall:renameNewSigpicTable');
		}
		else
		{
			$this->skip_message();
		}
	}
}

class vB_Upgrade_606a3 extends vB_Upgrade_Version
{
	public function step_1()
	{
		// We may end up modifying the user table, which can take a bit of time.
		$this->long_next_step();
	}

	public function step_2()
	{
		// myisam doesn't support virtual columns. Check if user table is myisam and convert to innodb.
		// if something else entirely, don't touch it.
		$assertor = vB::getDbAssertor();
		$check = $assertor->getRow('vBInstall:showUserTableStatus');

		if (strtolower($check['Engine']) == 'myisam')
		{
			$this->show_message(sprintf($this->phrase['vbphrase']['alter_table_step_x'], TABLE_PREFIX . 'user', 1, 1));
			$assertor->assertQuery('vBInstall:convertUserTableToInnoDB');
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_3()
	{
		// https://dev.mysql.com/blog-archive/virtual-columns-and-effective-functional-indexes-in-innodb
		// virtual columns don't require full table rebuild (STORED do)
		// if we change these to STORED we should stick an extra step ahead for $this->long_next_step();

		$this->add_field2(
			'user',
			'birthday_month',
			'TINYINT',
			[
				'attributes' => 'UNSIGNED',
				// mariadb 10.4.10 currently does not seem to support NOT NULL on virtual column.
				// possibly due to https://github.com/typeorm/typeorm/issues/2691 ?
				// Not sure if DEFAULT works on mysql -- idea of default on a generated column is
				// kind of dubious anyways.. it always should be calculated from the dependent columns
				// + deterministic functions, so "default" would mean the fn(defaults of the dependent columns)
				//'null'       => false,
				//'default'    => 0,
				'extra'		 => "GENERATED ALWAYS AS (MONTH(`birthday_search`)) VIRTUAL",
			],
			1, 4
		);

	}

	public function step_4()
	{
		// https://dev.mysql.com/blog-archive/virtual-columns-and-effective-functional-indexes-in-innodb
		// virtual columns don't require full table rebuild (STORED do)
		// if we change these to STORED we should stick an extra step ahead for $this->long_next_step();

		$this->add_field2(
			'user',
			'birthday_day',
			'TINYINT',
			[
				'attributes' => 'UNSIGNED',
				//'null'       => false,
				//'default'    => 0,
				'extra'		 => "GENERATED ALWAYS AS (DAY(`birthday_search`)) VIRTUAL",
			],
			2, 4
		);

	}

	public function step_5()
	{
		$this->add_field2(
			'user',
			'calendar_show_birthday',
			'TINYINT',
			[
				'attributes' => 'UNSIGNED',
				//'null'       => false,
				//'default'    => 0,
				'extra'		 => "GENERATED ALWAYS AS (IF(`showbirthday` = 2 OR `showbirthday` = 3, 1, 0)) VIRTUAL",
			],
			3, 4
		);
		// adding indexes on user table may observable time for lots of users.
		$this->long_next_step();
	}

	public function step_6()
	{
		$this->add_index2('user', 'calendar_birthday', ['calendar_show_birthday', 'birthday_month', 'birthday_day',], 4, 4);
	}

	public function step_7()
	{
		//hardcode the bitfields because if they change in the future these values should not
		$canadminpm = 2147483648;
		$canadminusers = 256;
		$canadminthreads = 64;

		$db = vB::getDbAssertor();
		$db->assertQuery('vBInstall:updateAdminPerms', [
			'existing' => $canadminusers | $canadminthreads,
			'new' => $canadminpm,
		]);

		$this->show_message($this->phrase['version']['506a1']['updating_admin_permissions']);
	}
}

class vB_Upgrade_606a4 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'calendarevent'),
			"
				CREATE TABLE `" . TABLE_PREFIX . "calendarevent` (
					`eventid`            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
					`month`              TINYINT UNSIGNED NOT NULL DEFAULT 0,
					`day`                TINYINT UNSIGNED NOT NULL DEFAULT 0,
					`year`               SMALLINT UNSIGNED NOT NULL DEFAULT 0,
					`is_recurring`       TINYINT UNSIGNED GENERATED ALWAYS AS (`year` = 0) VIRTUAL,
					`title`              VARCHAR(512) NOT NULL DEFAULT '',
					`description`        MEDIUMTEXT,
					KEY `calendar_lookup` (`month`, `day`, `year`)
				) ENGINE = " . $this->hightrafficengine . "
			",
			self::MYSQL_ERROR_TABLE_EXISTS
		);

		$this->long_next_step();
	}

	public function step_2()
	{
		$this->drop_index2('user', 'birthday');
	}

	public function step_3()
	{
		$this->add_cronjob(
			array(
				'varname'  => 'calendar_cleanup',
				'nextrun'  => 0,
				'weekday'  => 0,
				'day'      => -1,
				'hour'     => 0,
				'minute'   => serialize([45]),
				'filename' => './includes/cron/calendar_cleanup.php',
				'loglevel' => 1,
				'volatile' => 1,
				'product'  => 'vbulletin'
			)
		);
	}

	public function step_4()
	{
		$this->add_field2(
			'ranks',
			'active',
			'TINYINT',
			[
				'attributes' => 'UNSIGNED',
				'null'       => false,
				'default'    => 1,
			],
			1, 1
		);
	}
}
/*======================================================================*\
|| ####################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision$
|| ####################################################################
\*======================================================================*/
