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

class vB_Upgrade_540a1 extends vB_Upgrade_Version
{
	/**
	 * Update the imgdir_spriteiconsvb stylevar
	 */
	public function step_1()
	{
		vB_Upgrade::createAdminSession();
		$assertor = vB::getDbAssertor();
		$updated = false;

		// Get imgdir_spriteiconsvb stylevar for all styles
		$stylevars = $assertor->getRows('stylevar', array('stylevarid' => 'imgdir_spriteiconsvb'));
		foreach ($stylevars AS $stylevar)
		{
			$unserialized = @unserialize($stylevar['value']);
			if ($unserialized AND is_array($unserialized))
			{
				// if it contains the previous default value, let's set it to blank
				// This stylevar is no longer used by the master/default style, but
				// we'll keep it around for custom styles that may use it.
				if ($unserialized['path'] == 'images/css')
				{
					$unserialized['path'] = '';
					$serialized = serialize($unserialized);
					$assertor->update('stylevar', array('value' => $serialized), array('stylevarid' => $stylevar['stylevarid'], 'styleid' => $stylevar['styleid']));
					$this->show_message(sprintf($this->phrase['version']['540a1']['updating_imgdir_spriteiconsvb_stylevar_in_styleid_x'], $stylevar['styleid']));
					$updated = true;
				}
			}
		}

		if (!$updated)
		{
			$this->skip_message();
		}
	}

	// Add admin message to warn about Groups channel permissions (VBV-17996)
	public function step_2()
	{
		$this->add_adminmessage(
			'after_upgrade_check_groups_channel_permissions',
			array(
				'dismissible' => 1,
				'execurl'     => 'forumpermission.php?do=modify',
				'method'      => 'get',
				'status'      => 'undone',
			)
		);
	}
}

class vB_Upgrade_541a1 extends vB_Upgrade_Version
{
	// Set albums as unprotected so they show up in search results
	public function step_1()
	{
		$this->show_message($this->phrase['version']['541a1']['show_albums_in_search']);

		$db = vB::getDbAssertor();

		$albumChannel = $db->getRow('vBForum:channel', array('guid' => vB_Channel::ALBUM_CHANNEL));
		if (!empty($albumChannel['nodeid']))
		{
			$db->assertQuery('vBInstall:updateChannelProtected',
				array(
					'channelid' => $albumChannel['nodeid'],
					'protected' => 0,
				)
			);
		}
	}

	// Set infraction & report nodes to be protected (in case any old ones
	// prior to special channel being protected exist on this forum).
	public function step_2()
	{
		$this->show_message($this->phrase['version']['541a1']['update_old_infraction_report_nodes']);

		$db = vB::getDbAssertor();

		$channels = $db->getRows('vBForum:channel', array('guid' => array(vB_Channel::INFRACTION_CHANNEL, vB_Channel::REPORT_CHANNEL)));
		foreach ($channels AS $channel)
		{
			if (!empty($channel['nodeid']))
			{
				$db->assertQuery('vBInstall:updateChannelProtected',
					array(
						'channelid' => $channel['nodeid'],
						'protected' => 1,
					)
				);
			}
		}
	}

	// Update any imported albums with the incorrect channel routeid to conversation routeid
	public function step_3()
	{
		vB_Upgrade::createAdminSession();
		$assertor = vB::getDbAssertor();
		$albumChannel = vB_Library::instance('node')->fetchAlbumChannel();
		$albumRouteid = $assertor->getColumn('vBForum:node', 'routeid', array('nodeid' => $albumChannel));
		$albumRouteid = reset($albumRouteid);

		$check = $assertor->getRow('vBForum:node', array('parentid' => $albumChannel, 'routeid' => $albumRouteid));
		if (!empty($check))
		{
			$channelRoute = $assertor->getRow('routenew',
				array(
					'contentid' => $albumChannel,
					'class' => 'vB5_Route_Channel',
				)
			);
			$convoRoute = $assertor->getRow('routenew',
				array(
					'contentid' => $albumChannel,
					'class' => 'vB5_Route_Conversation',
				)
			);
			/*
			Note:
				500b12 step_3()'s fixNodeRouteid , which comes after 500a28 step_17()'s import album,
				failed to fix the broken starter routeids because it seems at the first import, the starters
				are NOT set. The starters seem to be fixed later down the line in another upgrade step.
			 */
			$this->show_message($this->phrase['version'][$this->SHORT_VERSION]['updating_imported_album_routeid']);
			$assertor->assertQuery('vBInstall:updateRouteidForStarterNodeWithParentAndRoute',
				array(
					'newRouteid' => $convoRoute['routeid'],
					'oldRouteid' => $channelRoute['routeid'],
					'parentid' => $albumChannel,
				)
			);
		}
		else
		{
			$this->skip_message();
		}
	}


	// Add admin message to warn about broken view permssions for updated albums
	public function step_4()
	{
		$this->add_adminmessage(
			'after_upgrade_notify_users_check_album_viewperms',
			array(
				'dismissible' => 1,
				'execurl'     => 'announcement.php?do=add',
				'method'      => 'get',
				'status'      => 'undone',
			)
		);
	}

	// change require_moderate to skip_moderate pt 1
	// add column
	public function step_5()
	{
		if (!$this->field_exists('permission', 'skip_moderate'))
		{

			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'permission', 1, 3),
				"ALTER TABLE " . TABLE_PREFIX . "permission
					ADD COLUMN skip_moderate SMALLINT UNSIGNED NOT NULL DEFAULT 0"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	// change require_moderate to skip_moderate pt 2
	// flip the bit & save to new column
	public function step_6($data = null)
	{
		vB_Upgrade::createAdminSession();
		$assertor = vB::getDbAssertor();

		$count = $assertor->getRow('vBInstall:getRequireModerateNeedingConversionCount');
		if (!empty($count))
		{
			$count = $count['count'];
		}

		if ($this->field_exists('permission', 'require_moderate'))
		{
			if (!empty($count))
			{
				$this->show_message(
					sprintf($this->phrase['core']['altering_x_table'], 'permission', 2, 3)
				);
				$assertor->assertQuery('vBInstall:convertRequireModerateToSkipModerate');
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

	// change require_moderate to skip_moderate pt 3
	// drop old column once finished.
	public function step_7($data = null)
	{
		vB_Upgrade::createAdminSession();
		$assertor = vB::getDbAssertor();

		$count = $assertor->getRow('vBInstall:getRequireModerateNeedingConversionCount');
		if (!empty($count))
		{
			$count = $count['count'];
		}

		if ($this->field_exists('permission', 'require_moderate'))
		{
			if (empty($count))
			{
				$this->run_query(
					sprintf($this->phrase['core']['altering_x_table'], 'permission', 3, 3),
					"ALTER TABLE " . TABLE_PREFIX . "permission
						DROP COLUMN require_moderate"
				);
				// rebuild any caches that might reference permission table fields (AFAIK mostly in memory in permissioncontext)
				vB::getUserContext()->rebuildGroupAccess();
				// done
				$this->show_message(sprintf($this->phrase['core']['process_done']));
			}
			else
			{
				// step 6 isn't finished.. not much we can do here.
				$this->skip_message();
			}
		}
		else
		{
			$this->skip_message();
		}
	}
}

class vB_Upgrade_541a3 extends vB_Upgrade_Version
{
	public function step_1($data)
	{
		if ($this->tableExists('attachment'))
		{
			$oldcontenttype = array(
				vB_Api_ContentType::OLDTYPE_POSTATTACHMENT,
				vB_Api_ContentType::OLDTYPE_THREADATTACHMENT,
				vB_Api_ContentType::OLDTYPE_BLOGATTACHMENT,
				vB_Api_ContentType::OLDTYPE_ARTICLEATTACHMENT,
			);


			if (empty($data['startat']))
			{
				$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'node'));
			}

			$callback = function($startat, $nextid) use ($oldcontenttype)
			{
				vB::getDbAssertor()->assertQuery('vBInstall:fixAttachmentUser', array(
					'oldcontenttypeid' => $oldcontenttype,
					'startat' => $startat,
					'nextid' => $nextid,
				));
			};

			//this is a bit wierd because we iterate over the attach table but update using the node and attachment
			//tables.  This is because the query *really* wants to use the node rather than the attachment as the
			//driver (which limits the advantage of doing a range on attachment ids), but this allows us to only
			//look at attachment nodes without having to scan and filter on the much larger node table.
			//this does mean we'll look at all attachments and not just the ones we imported from vB4.  That's extra
			//work but doesn't hurt anything (and in most cases less extra work than trying to iterate over the node table)
			$batchsize = $this->getBatchSize('small', __FUNCTION__);
			return $this->updateByIdWalk($data, $batchsize, 'vBInstall:getMaxAttachNodeid', 'vBForum:attach', 'nodeid', $callback);
		}
		else
		{
			$this->skip_message();
		}
	}

	//we do this as a seperate step because some databases seem to have the correct
	//username but a blank authorname
	public function step_2($data)
	{
		$oldcontenttype = array(
			vB_Api_ContentType::OLDTYPE_POSTATTACHMENT,
			vB_Api_ContentType::OLDTYPE_THREADATTACHMENT,
			vB_Api_ContentType::OLDTYPE_BLOGATTACHMENT,
			vB_Api_ContentType::OLDTYPE_ARTICLEATTACHMENT,
		);

		if (empty($data['startat']))
		{
			$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'node'));
		}

		$callback = function($startat, $nextid) use ($oldcontenttype)
		{
			vB::getDbAssertor()->assertQuery('vBInstall:fixAttachmentUsername', array(
				'oldcontenttypeid' => $oldcontenttype,
				'startat' => $startat,
				'nextid' => $nextid,
			));
		};

		//this is a bit wierd because we iterate over the attach table but update using the node and user tables.
		$batchsize = $this->getBatchSize('small', __FUNCTION__);
		return $this->updateByIdWalk($data, $batchsize, 'vBInstall:getMaxAttachNodeid', 'vBForum:attach', 'nodeid', $callback);
	}

	// Add userauth table.
	public function step_3()
	{
		// moved to 541a4
		$this->skip_message();
	}

	// Add loginlibraries table.
	public function step_4()
	{
		// moved to 541a4
		$this->skip_message();
	}

	// Add sessionauth table.
	public function step_5()
	{
		// moved to 541a4
		$this->skip_message();
	}

	/**
	* Handle customized values for stylevars that have been renamed
	*/
	public function step_6()
	{
		$mapper = new vB_Stylevar_Mapper();

		// "post_rating_color" was renamed to "reputation_bar_active_background" and the
		// datatype changed from color to background. Transfer the color value only.
		// No preset values need to be added, since all the other values in the background
		// type can safely be left empty.
		$mapper->addMapping('post_rating_color.color', 'reputation_bar_active_background.color');

		// Do the processing
		if ($mapper->load() AND $mapper->process())
		{
			$this->show_message($this->phrase['version']['541a1']['mapping_customized_stylevars']);
			$mapper->processResults();
		}
		else
		{
			$this->skip_message();
		}
	}
}

class vB_Upgrade_541a4 extends vB_Upgrade_Version
{
	/*
		Steps 1-6:
		For alpha/beta testers, we have to resize the `additional_params` columns in userauth & sessionauth tables,
		relabel `loginlibrary_id` columns to `loginlibraryid`, and relabel the `loginlibraries` table to `loginlibrary`.
		Simplest thing to do is to just drop all the tables and recreate them at this point.
		This means they'll lose existing user links, but it'll only affect the first upgrade.
	 */
	public function step_1()
	{
		// Only run once.
		if ($this->iRan(__FUNCTION__))
		{
			return;
		}

		$this->drop_table('sessionauth');
	}


	public function step_2()
	{
		// Only run once.
		if ($this->iRan(__FUNCTION__))
		{
			return;
		}

		$this->drop_table('userauth');
	}

	public function step_3()
	{
		// Only run once.
		if ($this->iRan(__FUNCTION__))
		{
			return;
		}

		// plural `loginlibraries` is intentional.
		// Before alpha 4, `loginlibrary` table was called `loginlibraries`, and
		// we're relabeling it from a3 -a4 along with some other table modifications
		// via just dropping the old table & readding the table with the new name.
		$this->drop_table('loginlibraries');
	}


	// Add userauth table.
	public function step_4()
	{
		if (!$this->tableExists('userauth'))
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'userauth'),
				"
					CREATE TABLE `" . TABLE_PREFIX . "userauth` (
						`userid`               INT UNSIGNED NOT NULL DEFAULT '0',
						`loginlibraryid`      INT UNSIGNED NOT NULL DEFAULT '0',
						`external_userid`      VARCHAR(191) NOT NULL DEFAULT '',
						`token`                VARCHAR(191) NOT NULL DEFAULT '',
						`token_secret`         VARCHAR(191) NOT NULL DEFAULT '',
						`additional_params`    VARCHAR(2048) NOT NULL DEFAULT '',

						PRIMARY KEY `user_platform_constraint`  (`userid`, `loginlibraryid`),
						UNIQUE KEY `platform_extuser_constraint`  (`loginlibraryid`, `external_userid`),
						KEY         `token_lookup`              (`userid`, `loginlibraryid`, `token`)
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

	// Add loginlibrary table.
	public function step_5()
	{
		if (!$this->tableExists('loginlibrary'))
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'loginlibrary'),
				"
					CREATE TABLE `" . TABLE_PREFIX . "loginlibrary` (
						`loginlibraryid`      INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
						`productid`            VARCHAR(25) NOT NULL,
						`class`                VARCHAR(64) NOT NULL,

						UNIQUE KEY (`productid`)
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

	// Add sessionauth table.
	public function step_6()
	{
		if (!$this->tableExists('sessionauth'))
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'sessionauth'),
				"
					CREATE TABLE `" . TABLE_PREFIX . "sessionauth` (
						`sessionhash`          CHAR(32) NOT NULL DEFAULT '',
						`loginlibraryid`      INT UNSIGNED NOT NULL DEFAULT '0',
						`token`                VARCHAR(191) NOT NULL DEFAULT '',
						`token_secret`         VARCHAR(191) NOT NULL DEFAULT '',
						`additional_params`    VARCHAR(2048) NOT NULL DEFAULT '',
						`expires`              INT UNSIGNED NOT NULL,

						PRIMARY KEY `session_platform_constraint`  (`sessionhash`, `loginlibraryid`),
						INDEX (`expires`)
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

	// Reinstall twitterlogin package to fix the externallogin settinggroup displayorder and to pick up
	// any other product XML changes in alpha/beta versions
	public function step_7()
	{
		// Moved to 541b1 step_1()
		$this->skip_message();

		$this->long_next_step();
	}

	public function step_8($data)
	{
		if (empty($data['startat']))
		{
			$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'node'));
		}

		$callback = function($startat, $nextid)
		{
			$types = vB_Types::instance();

			$channeltypeid = $types->getContentTypeID('vBForum_Channel');

			$db = vB::getDbAssertor();
			//the comment starter needs to run first because it depends on the fact that
			//replies aren't fixed yet
			$result = $db->assertQuery('vBInstall:fixReplyCommentStarter', [
				'channeltypeid' => $channeltypeid,
				'startat' => $startat,
				'nextid' => $nextid,
			]);
		};

		$batchsize = $this->getBatchSize('large', __FUNCTION__);
		return $this->updateByIdWalk($data, $batchsize, 'vBInstall:getMaxNodeid', 'vBForum:node', 'nodeid', $callback);
	}

	public function step_9()
	{
		$this->alter_field(
			sprintf($this->phrase['core']['altering_x_table'], 'permission', 1, 1),
			'permission',
			'edit_time',
			'float',
			self::FIELD_DEFAULTS
		);
	}
}

class vB_Upgrade_541b1 extends vB_Upgrade_Version
{
	// Reinstall twitterlogin package step moved to 541b2.
	public function step_1()
	{
		$this->skip_message();
	}


	// Replace require_moderate with skip_moderate in 'defaultchannelpermissions'
	// See VBV-18294
	public function step_2()
	{
		$datastore = vB::getDatastore();
		$datastore->fetch('defaultchannelpermissions');
		$defaultchannelpermissions = vB::getDatastore()->getValue('defaultchannelpermissions');
		if (empty($defaultchannelpermissions))
		{
			// if this isn't created yet, then there's nothing to do.
			return $this->skip_message();
		}

		if (!is_array($defaultchannelpermissions))
		{
			$defaultchannelpermissions = vB_Utility_Unserialize::unserialize($defaultchannelpermissions);
		}

		$needsRebuild = false;
		foreach($defaultchannelpermissions AS $__nodekey => $__innerArr)
		{
			foreach ($__innerArr AS $__groupkey => $permissions)
			{
				if (isset($permissions['require_moderate']))
				{
					$needsRebuild = true;
					$defaultchannelpermissions[$__nodekey][$__groupkey]['skip_moderate'] = !($permissions['require_moderate']);
					unset($defaultchannelpermissions[$__nodekey][$__groupkey]['require_moderate']);
				}
			}
		}

		if ($needsRebuild)
		{
			// show message.
			$this->show_message($this->phrase['version']['541b1']['rebuilding_defaultchannelperms_datastore']);
			$datastore->build('defaultchannelpermissions', serialize($defaultchannelpermissions), 1);
		}
		else
		{
			$this->skip_message();
		}
	}
}

class vB_Upgrade_541b2 extends vB_Upgrade_Version
{
	// Reinstall twitterlogin package to fix the externallogin settinggroup displayorder
	// and to pick up any other product XML changes (e.g. modified/new templates & phrases)
	// in alpha/beta versions
	public function step_1()
	{
		// Packages are installed/upgraded as part of upgrade_final step_13
		$this->skip_message();
	}
}

class vB_Upgrade_542a1 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'adcriteria', 1, 1),
			'adcriteria',
			'conditionjson',
			'text',
			array('null' => false, 'default' => '')
		);
	}
}

class vB_Upgrade_542a2 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'redirect', 1, 1),
			'redirect',
			'tonodeid',
			array('tonodeid')
		);
	}
}

class vB_Upgrade_542a4 extends vB_Upgrade_Version
{
	/*
	 * Pickup product XML changes in alpha 4 (moved from alpha 3)
	 */
	public function step_1()
	{
		// Packages are installed/upgraded as part of upgrade_final step_13
		$this->skip_message();
	}

	/*
	 *	Fix channel route REs so that they work with and without a prefix without modification.
	 */
	public function step_2()
	{
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'routenew'));

		$db = vB::getDBAssertor();
		$set = $db->select('routenew', array('class' => 'vB5_Route_Channel'));
		foreach($set AS $row)
		{
			//we have a difference based on whether or nto the prefix is blank
			//(to account for the fact that we only have a slash before the page if
			//we have
			$expected = ($row['prefix'] === '' ? '(?:page(' : '(?:/page(');
			$newre = str_replace($expected, '(?:(?:/|^)page(', $row['regex']);
			$row = $db->update('routenew', array('regex' => $newre), array('routeid' => $row['routeid']));
		}
	}

	public function step_3()
	{
		$this->drop_table('bookmarksite');
	}

	public function step_4()
	{
		vB::getDatastore()->delete('bookmarksitecache');
		$this->show_message(sprintf($this->phrase['core']['remove_datastore_x'], 'bookmarksitecache'));
	}

	public function step_5()
	{
		$this->drop_table('indexqueue');
	}

	public function step_6()
	{
		$this->drop_table('discussionread');
	}

	public function step_7()
	{
		$this->drop_table('picturelegacy');
	}

	public function step_8()
	{
		$this->drop_table('podcast');
	}

	public function step_9()
	{
		$this->drop_table('podcastitem');
	}

}

class vB_Upgrade_543a1 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'event', 1, 1),
			'event',
			'ignoredst',
			'TINYINT',
			array('length' => 1, 'null' => false, 'default' => '1')
		);
	}
}

class vB_Upgrade_543a2 extends vB_Upgrade_Version
{
	// Add ipaddressinfo table.
	public function step_1()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'ipaddressinfo'),
			"
				CREATE TABLE " . TABLE_PREFIX . "ipaddressinfo (
					`ipaddressinfoid` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
					`ipaddress` VARCHAR(45) NOT NULL DEFAULT '',
					`eustatus` TINYINT NOT NULL DEFAULT 0,
					`created` INT UNSIGNED NOT NULL,
					PRIMARY KEY (`ipaddressinfoid`),
					UNIQUE KEY (`ipaddress`),
					KEY (`created`)
				) ENGINE = " . $this->hightrafficengine . "
			",
			self::MYSQL_ERROR_TABLE_EXISTS
		);

		$this->long_next_step();
	}


	// add `user`.`privacyconsent` column
	public function step_2()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'user', 1, 2),
			'user',
			'privacyconsent',
			'tinyint',
			array('attributes' => 'SIGNED', 'null' => false, 'default' => '0')
		);
		$this->long_next_step();
	}

	// add `user`.`privacyconsentupdated` column
	public function step_3()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'user', 2, 2),
			'user',
			'privacyconsentupdated',
			'int',
			array('attributes' => 'UNSIGNED', 'null' => false, 'default' => '0')
		);
		$this->long_next_step();
	}

	// add `privacy_updated` index for `user` table
	public function step_4()
	{
		$this->add_index(
			sprintf($this->phrase['core']['create_index_x_on_y'], 'privacy_updated', TABLE_PREFIX . 'user'),
			'user',
			'privacy_updated',
			array('privacyconsent', 'privacyconsentupdated')
		);
	}

	public function step_5()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'user', 1, 1),
			'user',
			'eustatus',
			'TINYINT',
			array('null' => false, 'default' => '0')
		);
	}

	// add privacyconsent table
	public function step_6()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'privacyconsent'),
			"
				CREATE TABLE " . TABLE_PREFIX . "privacyconsent (
					privacyconsentid INT UNSIGNED NOT NULL AUTO_INCREMENT,
					ipaddress VARCHAR(45) NOT NULL DEFAULT '',
					created INT UNSIGNED NOT NULL DEFAULT '0',
					consent TINYINT UNSIGNED NOT NULL DEFAULT '0',
					PRIMARY KEY (privacyconsentid),
					KEY (ipaddress),
					KEY (created)
				) ENGINE = " . $this->hightrafficengine . "
			",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}
	// add privacy consent withdrawn user delete cron
	public function step_7()
	{
		$this->add_cronjob(
			array(
				'varname'  => 'privacyconsentremoveuser',
				'nextrun'  => 0,
				'weekday'  => -1,
				'day'      => -1,
				'hour'     => -1,
				'minute'   => serialize(array(15)),
				'filename' => './includes/cron/privacyconsentremoveuser.php',
				'loglevel' => 1,
				'volatile' => 1,
				'product'  => 'vbulletin'
			)
		);
	}

}

class vB_Upgrade_543a3 extends vB_Upgrade_Version
{
	/**
	 * Add admin message to let them know there's a new (empty) privacy page VBV-18551
	 */
	public function step_1()
	{
		$this->add_adminmessage(
			'after_upgrade_check_privacy_policy_page',
			array(
				'dismissable' => 1,
				'script'      => '',
				'action'      => '',
				'execurl'     => '',
				'method'      => 'get',
				'status'      => 'undone',
			)
		);
	}
}

class vB_Upgrade_544a2 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->updateHeaderUrls(array(
			'blogadmin/create' => 'blogadmin/create/settings',
			'sgadmin/create' => 'sgadmin/create/settings'
		));
	}

	public function step_2()
	{
		//update the pages so they point back to the routes that point at them.  This managed to get out of
		//sync due to some previous bugs.  Don't update redirect routes -- we never want a page to point to
		//a redirect route.
		$db = vB::getDbAssertor();
		$routes = $db->select('routenew', array(array('field' => 'redirect301', 'operator' => vB_dB_Query::OPERATOR_ISNULL)));
		foreach($routes AS $route)
		{
			$args = unserialize($route['arguments']);
			if (!empty($args['pageid']))
			{
				//we should always have a page, but if we don't we aren't going to try to correct that here.
				$page = $db->getRow('page', array('pageid' => $args['pageid']));
				if ($page AND ($page['routeid'] != $route['routeid']))
				{
					$db->update('page', array('routeid' => $route['routeid']), array('pageid' => $page['pageid']));
				}
			}
		}
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'page'));
	}
}

class vB_Upgrade_544a3 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->add_index(
			sprintf($this->phrase['core']['create_index_x_on_y'], 'routeid', TABLE_PREFIX . 'node'),
			'node',
			'routeid',
			array('routeid')
		);
	}

	public function step_2()
	{
		//previous logic didn't update the contentid of the route when changing the conversation route for
		//a topic to a custom url.  In which case we'll have multiple conversation routes that have a
		//channels noteid as the contentid.  We use this to set the routeid for new nodes in channel
		//which could cause all kinds of problems if people actually set the url for a topic.
		//
		//Due to they way mysql works this is likely to continue working... right up until it stops.
		//so let's point custom topic urls at the topic node instead.
		$db = vB::getDbAssertor();
		$routes = $db->select('routenew', array(
			'class' => 'vB5_Route_Conversation',
			array('field' => 'redirect301', 'operator' => vB_dB_Query::OPERATOR_ISNULL)
		));

		foreach($routes AS $route)
		{
			$args = unserialize($route['arguments']);
			if (!empty($args['customUrl']) AND $args['nodeid'] != $route['contentid'])
			{
				$db->update('routenew', array('contentid' => $args['nodeid']), array('routeid' => $route['routeid']));
			}
		}
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'route'));
	}
}

class vB_Upgrade_544a4 extends vB_Upgrade_Version
{
	public function step_1()
	{
		// Create ishomeroute field
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'routenew', 1, 2),
			'routenew',
			'ishomeroute',
			'TINYINT',
			array(
				'null' => true,
			)
		);
	}

	public function step_2()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'routenew', 2, 2),
			'routenew',
			'ishomeroute',
			array('ishomeroute')
		);
	}

	public function step_3()
	{
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table_x'], 'routenew', 1, 1));
		$db = vB::getDbAssertor();

		//we need to set the blank prefix to a placeholder.  This won't really change the behavior (aside
		//from {site}/homepage being a redirect) but we can't have a blank prefix or things get weird
		//when you make another page the homepage.
		$newprefix = 'homepage';

		$result = $db->select('routenew', array('prefix' => ''));
		foreach($result AS $row)
		{
			$re = $newprefix;

			//the default conversation route does it's own thing in regards to the prefix.  We want a slash after the
			//prefix *unless* the prefix is blank.  There isn't a good way to capture this case because it's implicitly
			//buried in the route logic (specifically in the isValid function of conversation route class and the
			//updateContentRoute function of the channel route class.
			//
			//Custom topic urls follow the normal case of not having a slash directly after hte prefix.
			//Also do not add a slash if that's already the first character.  That *shouldn't* happen
			//but we never want a double slash.
			if (is_a($row['class'], 'vB5_Route_Conversation', true) AND $row['regex'][0] != '/')
			{
				$arguments = unserialize($row['arguments']);
				if (empty($arguments['customUrl']))
				{
					$re .= '/';
				}
			}

			$re .= $row['regex'];

			$data = array(
				'prefix' => $newprefix,
				'regex' => $re,
				'ishomeroute' => 1
			);

			$db->update('routenew', $data, array('routeid' => $row['routeid']));
		}
	}
}

class vB_Upgrade_544b1 extends vB_Upgrade_Version
{
	/**
	 *	Fix nodes that point to redirect routes.  This not only causes extra processing when generating
	 *	urls for those nodes, it causes other difficulties.
	 */
	public function step_1($data = [])
	{
		if (empty($data['startat']))
		{
			$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'node'));
		}

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
			$max = $db->getRow('vBInstall:getMaxNodeRedirectRoute');

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

		//there aren't going to be *that* many affected routes, but the update query could
		//be a little time consuming if we have a lot of nodes in a topic.  So we'll handle
		//it one by one
		$route = $db->getRow('vBInstall:getNodeRedirectRoutes', array('startat' => $startat));
		if ($route)
		{
			$db->update('vBForum:node', array('routeid' => $route['redirect301']), array('routeid' => $route['routeid']));
			$this->show_message(sprintf($this->phrase['core']['processed_records_x_y'], $route['routeid'], $route['routeid']), true);
		}
		else
		{
			//this probably shouldn't happen since we should hit the greater than max
			//case above in all cases.  But just in case.
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			return;
		}

		return array('startat' => $route['routeid'] + 1, 'max' => $max);
	}
}

class vB_Upgrade_545a1 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->show_message(sprintf($this->phrase['core']['altering_x_table'], 'notificationtype', 1, 1));
		vB::getDbAssertor()->assertQuery('vBInstall:alterIndexLimitNotificationtype');
	}
}

class vB_Upgrade_545a3 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->drop_table('tagcontent');
	}

	public function step_2()
	{
		$this->drop_table('activitystream');
	}

	public function step_3()
	{
		$this->drop_table('activitystreamtype');

		$this->long_next_step();
	}

	public function step_4()
	{
		$this->alter_field(
			sprintf($this->phrase['core']['altering_x_table'], 'filedata', 1, 1),
			'filedata',
			'filedata',
			'longblob',
			self::FIELD_DEFAULTS
		);
	}
}

class vB_Upgrade_545a4 extends vB_Upgrade_Version
{
	public function step_1()
	{
		// this corrects 541a4 step9 that due to a bug in alter_field (VBV-18813)
		// would set the column to allow NULL values.
		$this->alter_field(
			sprintf($this->phrase['core']['altering_x_table'], 'permission', 1, 1),
			'permission',
			'edit_time',
			'float',
			self::FIELD_DEFAULTS
		);
	}

	/**
	 * Handle customized values (in custom styles) for stylevars that have been renamed
	 */
	public function step_2()
	{
		$mapper = new vB_Stylevar_Mapper();

		// Map the entire stylevar value from old to new since this is only a rename
		// No need for mapping of any of the stylevar parts or any presets, since
		// we only renamed the stylevar and didn't change the data type.
		$mapper->addMapping('body_font', 'global_text_font');

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

	/**
	 * Update inheritance for stylevars that inherit from a stylevar that was just renamed
	 */
	public function step_3()
	{
		$mapper = new vB_Stylevar_Mapper();
		$result = $mapper->updateInheritance('body_font', 'global_text_font');

		if ($result)
		{
			$this->show_message($this->phrase['core']['updating_customized_stylevar_inheritance']);
		}
		else
		{
			$this->skip_message();
		}
	}
}

class vB_Upgrade_546a2 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'user', 1, 1),
			'user',
			'editorstate',
			'int',
			array(
				'attributes' => 'UNSIGNED',
				'null'       => false,
				'default'    => 1,
				'extra'      => '',
			)
		);
	}
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 112191 $
|| ####################################################################
\*======================================================================*/
