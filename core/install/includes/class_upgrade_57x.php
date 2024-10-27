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

class vB_Upgrade_570a2 extends vB_Upgrade_Version
{
	// Add Sandbox toggle for PayPal
	public function step_1()
	{
		$assertor = vB::getDbAssertor();
		$paypalAPIs = $assertor->getRows('vBForum:paymentapi', ['classname' => 'paypal']);
		if (empty($paypalAPIs))
		{
			$this->skip_message();
			return;
		}
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table_x'], 'paymentapi', 1, 2));
		$processed = false;
		foreach ($paypalAPIs AS $__paypal)
		{
			$settings = vB_Utility_Unserialize::unserialize($__paypal['settings']);
			if (!isset($settings['sandbox']))
			{
				$processed = true;
				$settings['sandbox'] = [
					'type' => 'yesno',
					'value' => 0,
					'validate' => 'boolean'
				];
				$assertor->update('vBForum:paymentapi', ['settings' => serialize($settings)], ['paymentapiid' => $__paypal['paymentapiid']]);
			}
		}

		if (!$processed)
		{
			$this->skip_message();
		}
	}
}

class vB_Upgrade_570a3 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$db = vB::getDbAssertor();
		$fieldname = 'pagemeta';
		$existing = $db->getRow('phrasetype', ['fieldname' => $fieldname]);
		if (!$existing)
		{
			$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'phrasetype'));
			$db->insert('phrasetype', [
				'fieldname' => $fieldname,
				'title' => $this->phrase['phrasetype'][$fieldname] ?? $fieldname,
				'editrows' => 3,
				'special' => 1,
			]);
		}
		else
		{
			$this->skip_message();
		}
	}

	// Add Stripe payment API
	public function step_2()
	{
		$assertor = vB::getDbAssertor();
		if (!$assertor->getRow('vBForum:paymentapi', ['classname' => 'stripe']))
		{
			$assertor->insert('vBForum:paymentapi', [
				'title'     => 'Stripe',
				'currency'  => 'usd,gbp,eur,aud,cad',
				'recurring' => 1,
				'classname' => 'stripe',
				'active'    => 0,
				'settings'  => serialize([
					'public_key' => [
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
					'webhook_secret' => [
						'type' => 'text',
						'value' => '',
						'validate' => 'string'
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
					'tax_category' => [
						'type' => 'text',
						'value' => '',
						'validate' => 'string'
					],
					'tax_behavior' => [
						'type' => 'select',
						'options' => ['inclusive', 'exclusive'],
						'value' => 'inclusive',
						'validate' => 'string'
					],
				]),
			]);
			$this->show_message(sprintf($this->phrase['vbphrase']['update_table_x'], 'paymentapi', 2, 2));
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_3()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'paymentapi_subscription'),
			"
			CREATE TABLE " . TABLE_PREFIX . "paymentapi_subscription (
				paymentapiid INT UNSIGNED NOT NULL DEFAULT 0,
				vbsubscriptionid SMALLINT UNSIGNED NOT NULL DEFAULT 0,
				userid INT UNSIGNED NOT NULL DEFAULT 0,
				paymentsubid VARCHAR(150) NOT NULL DEFAULT '',
				active SMALLINT NOT NULL DEFAULT 1,
				PRIMARY KEY (paymentapiid, vbsubscriptionid, userid, paymentsubid)
			) ENGINE = " . $this->hightrafficengine . "
			",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}
}

class vB_Upgrade_571a1 extends vB_Upgrade_Version
{
	// Remove google checkout payment API
	public function step_1()
	{
		$assertor = vB::getDbAssertor();
		if ($assertor->getRow('vBForum:paymentapi', ['classname' => 'google']))
		{
			$this->show_message($this->phrase['version']['571a1']['removing_google_checkout']);

			// Remove any subscription specific options (e.g. display this payment option, tax, etc)
			$subs = $assertor->getColumn('vBForum:subscription', 'newoptions', [], false, 'subscriptionid');
			foreach ($subs AS $__id => $__newoptions)
			{
				$__newoptions = unserialize($__newoptions);
				if (isset($__newoptions['api']['google']))
				{
					unset($__newoptions['api']['google']);
					$__newoptions = serialize($__newoptions);
					$assertor->update('vBForum:subscription', ['newoptions' => $__newoptions], ['subscriptionid' => $__id]);
				}
			}

			$assertor->delete('vBForum:paymentapi', ['classname' => 'google']);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_2($data = [])
	{
		if (empty($data['startat']))
		{
			$this->show_message($this->phrase['version']['571a1']['approve_privatemessages']);
		}

		$filter = ['contenttypeid' =>  vB_Types::instance()->getContentTypeID('vBForum_PrivateMessage')];

		$walker = new vB_UpdateTableWalker(vB::getDBAssertor());
		$walker->setBatchSize($this->getBatchSize('xlarge', __FUNCTION__));
		$walker->setMaxQuery('vBInstall:getMaxNodeidForContent', $filter);
		$walker->setNextidQuery('vBForum:node', 'nodeid', $filter);
		$walker->setCallbackQuery('vBInstall:setApprovedForPrivateMessages', $filter);

		return $this->updateByWalker($walker, $data);
	}

	// Steps 3 ~ 7 : Add new columns for user rank qualifiers
	public function step_3()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'ranks', 1, 5),
			'ranks',
			'startedtopics',
			'INT',
			[
				'attributes' => 'UNSIGNED',
				'null'       => false,
				'default'    => 0,
			]
		);
	}

	public function step_4()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'ranks', 2, 5),
			'ranks',
			'registrationtime',
			'INT',
			[
				'attributes' => 'UNSIGNED',
				'null'       => false,
				'default'    => 0,
			]
		);
	}

	public function step_5()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'ranks', 3, 5),
			'ranks',
			'reputation',
			'INT',
			[
				// signed int
				'null'       => false,
				'default'    => 0,
			]
		);
	}

	public function step_6()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'ranks', 4, 5),
			'ranks',
			'totallikes',
			'INT',
			[
				'attributes' => 'UNSIGNED',
				'null'       => false,
				'default'    => 0,
			]
		);
	}

	public function step_7()
	{
		// This field was initially added as "displayorder" in a1, but renamed "priority" in a2.
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'ranks', 5, 5),
			'ranks',
			'priority',
			'INT',
			[
				// signed int
				'null'       => false,
				'default'    => 0,
			]
		);
	}

	public function step_8()
	{
		// This used to rebuild ranks in datastore. I've since added/removed
		// some upgrade steps in the way, and this step has been moved to the end
		$this->skip_message();
		$this->long_next_step();
	}

	// add the startedtopics counter column & populate it.
	public function step_9()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'user', 1, 1),
			'user',
			'startedtopics',
			'INT',
			[
				'attributes' => 'UNSIGNED',
				'null'       => false,
				'default'    => 0,
			]
		);
		$this->long_next_step();
	}

	public function step_10()
	{
		$this->show_message($this->phrase['version']['571a1']['adding_user_startedtopics']);

		// logic & queries are based on misc.php?do=updateposts
		$topChannels = vB_Library::instance('content_channel')->fetchTopLevelChannelIds();
		$checkChannels = [
			$topChannels['forum'],
			$topChannels['blog'],
			$topChannels['groups'],
		];

		$channelContentType = vB_Types::instance()->getContentTypeID('vBForum_Channel');
		$assertor = vB::getDbAssertor();
		$relevantChannelIds = $assertor->getColumn('vBInstall:fetchChannelsForPostCounts', 'nodeid', [
				'topLevelChannels' => $checkChannels,
				'channelContentTypeId' => $channelContentType,
			], false, 'nodeid');
		$assertor->assertQuery('vBInstall:updateUserStartedTopics', ['relevantChannelIds' => $relevantChannelIds]);
		$this->long_next_step();
	}

	// add the totallikes counter column & populate it.
	public function step_11()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'user', 1, 1),
			'user',
			'totallikes',
			'INT',
			[
				'attributes' => 'UNSIGNED',
				'null'       => false,
				'default'    => 0,
			]
		);
		$this->long_next_step();
	}

	public function step_12()
	{
		$this->show_message($this->phrase['version']['571a1']['adding_user_totallikes']);
		$assertor = vB::getDbAssertor();
		$assertor->assertQuery('vBInstall:updateTotalLikes');
	}

	// Set priority to the same as minposts to preserve userrank collapse behavior
	// when a user qualifies for multiple ranks
	public function step_13()
	{
		$this->show_message($this->phrase['version']['571a1']['update_userrank_priority']);
		$assertor = vB::getDbAssertor();
		$assertor->assertQuery('vBInstall:updateUserrankPriority');
	}

	public function step_14()
	{
		// userrank rebuild moved to 571a2 because there are more changes to the userrank table.
		$this->skip_message();
	}

	// Add Square payment API
	public function step_15()
	{
		$assertor = vB::getDbAssertor();
		if (!$assertor->getRow('vBForum:paymentapi', ['classname' => 'square']))
		{
			$assertor->insert('vBForum:paymentapi', [
				'title'     => 'Square',
				'currency'  => 'usd',
				'recurring' => 0,
				'classname' => 'square',
				'active'    => 0,
				'settings'  => serialize([
					'app_id' => [
						'type' => 'text',
						'value' => '',
						'validate' => 'string'
					],
					'access_token' => [
						'type' => 'text',
						'value' => '',
						'validate' => 'string'
					],
					'webhook_url' => [
						'type' => 'text',
						'value' => '',
						'validate' => 'string'
					],
					'webhook_signaure_key' => [
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
					'tax_name' => [
						'type' => 'text',
						'value' => '',
						'validate' => 'string'
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

	// helper tables to handle Square idiosyncrasies... catalog info tracking & intermediary mappers
	// for relational data that's missing exposure in Square API.
	public function step_16()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'paymentapi_remote_catalog'),
			"
			CREATE TABLE " . TABLE_PREFIX . "paymentapi_remote_catalog (
				paymentapiid INT UNSIGNED NOT NULL DEFAULT 0,
				vbsubscriptionid SMALLINT UNSIGNED NOT NULL DEFAULT 0,
				vbsubscription_subid SMALLINT UNSIGNED NOT NULL DEFAULT 0,
				`currency` VARCHAR(4) NOT NULL DEFAULT '#ALL',
				remotecatalogid VARCHAR(150) NOT NULL DEFAULT '',
				data MEDIUMTEXT NOT NULL DEFAULT '',
				active SMALLINT NOT NULL DEFAULT 1,
				PRIMARY KEY (`paymentapiid`, `vbsubscriptionid`, `vbsubscription_subid`, `currency`)
			) ENGINE = " . $this->hightrafficengine . "
			",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_17()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'paymentapi_remote_orderid'),
			"
			CREATE TABLE " . TABLE_PREFIX . "paymentapi_remote_orderid (
				paymentapiid INT UNSIGNED NOT NULL DEFAULT 0,
				hash VARCHAR(32) NOT NULL DEFAULT '',
				remoteorderid VARCHAR(150) NOT NULL DEFAULT '',
				recurring SMALLINT UNSIGNED NOT NULL DEFAULT '0',
				PRIMARY KEY (remoteorderid, paymentapiid)
			) ENGINE = " . $this->hightrafficengine . "
			",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_18()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'paymentapi_remote_invoice_map'),
			"
			CREATE TABLE " . TABLE_PREFIX . "paymentapi_remote_invoice_map (
				paymentapiid INT UNSIGNED NOT NULL DEFAULT 0,
				remotesubscriptionid VARCHAR(150) NOT NULL DEFAULT '',
				hash VARCHAR(32) NOT NULL DEFAULT '',
				INDEX invoice_map(paymentapiid, remotesubscriptionid, hash)
			) ENGINE = " . $this->hightrafficengine . "
			",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}
}

class vB_Upgrade_571a2 extends vB_Upgrade_Version
{
	// remove no longer used index
	public function step_1()
	{
		$this->drop_index(
			sprintf($this->phrase['core']['altering_x_table'], 'ranks', 1, 5),
			'ranks',
			'grouprank'
		);
	}

	public function step_2()
	{
		// Rename `ranks`.`displayorder` to `ranks`.`priority`, but only if we haven't already.
		// This is to block cases of accidentally trying to rename an actual displayorder column
		// if we add one in the future
		if ($this->field_exists('ranks', 'displayorder') AND !$this->field_exists('ranks', 'priority'))
		{
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'ranks', 2, 5),
				"ALTER TABLE " . TABLE_PREFIX . "ranks CHANGE displayorder priority INT NOT NULL DEFAULT 0"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	// add `ranks`.`grouping`
	public function step_3()
	{
		// grouping VARCHAR(191) NOT NULL DEFAULT '',
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'ranks', 3, 5),
			'ranks',
			'grouping',
			'VARCHAR',
			[
				'length' => 191,
				'null'    => false,
				'default' => '',
			],
		);
	}

	// set `ranks`.`grouping` defaults to be associated with usergroup in order
	// to preserve old sorting behavior.
	public function step_4()
	{
		// only run once
		if ($this->iRan(__FUNCTION__))
		{
			return;
		}
		$this->show_message($this->phrase['version']['571a1']['update_userrank_grouping']);
		// Old behavior was `usergroupid` DESC, `minposts` DESC.
		// Now we want `grouping` ASC, `priority` DESC, so
		$assertor = vB::getDbAssertor();
		$usergroupsById = $assertor->getColumn('vBForum:usergroup', 'title', [], false, 'usergroupid');
		$maxUsergroupId = max(array_keys($usergroupsById));
		$digits = strlen($maxUsergroupId);
		// Also add in the "All User Groups" default
		$usergroupsById[0] = "All User Groups";
		$ranks = $assertor->getRows('vBForum:ranks');
		foreach ($ranks AS $__rank)
		{
			$__ugid = $__rank['usergroupid'];
			// Need to preserve usergroupid DESC when we have goruping ASC, so "flip" the usergroupid
			$__prefix = str_pad(($maxUsergroupId - $__ugid), $digits, '0', STR_PAD_LEFT);
			$__usergroup = $usergroupsById[$__ugid] ?? "Unknow User Group";
			$__grouping = "$__prefix : $__usergroup";
			$assertor->update('vBForum:ranks', ['grouping' => $__grouping], ['rankid' => $__rank['rankid']]);
		}
	}

	// add new index
	public function step_5()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'ranks', 4, 5),
			'ranks',
			'grouping',
			['grouping', ]
		);
	}

	// add new index (for joindate cron)
	public function step_6()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'ranks', 5, 5),
			'ranks',
			'registrationtime',
			['registrationtime', ]
		);
	}

	// Trigger rebuild of ranks datastore array
	public function step_7()
	{
		$this->show_message(sprintf($this->phrase['core']['rebuild_x_datastore'], 'ranks'));
		vB::getDatastore()->delete('ranks');
		vB_Library::instance('userrank')->haveRanks();
	}
}

class vB_Upgrade_571a3 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->drop_field2('session',	'profileupdate');
	}

	public function step_2()
	{
		$assertor = vB::getDbAssertor();
		$check = $assertor->getRow('setting', ['varname' => 'errorlogdatabase']);
		if (!empty($check['value']))
		{
			$this->add_adminmessage(
				'errorlogdatabase_moved_to_config_set_x',
				[
					'dismissible' => 1,
					'status'      => 'undone',
				],
				false,
				[$check['value']]
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_3()
	{
		$this->remove_datastore_entry('noavatarperms');
	}
}

class vB_Upgrade_571a4 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->drop_field2('usertextfield', 'searchprefs');
	}

	public function step_2()
	{
		$this->drop_field2('session', 'bypass');
	}

	//copied from 553a4.  We need it both places because some older steps break if this the situation but
	//we need to clean up any newer DBs as well.
	public function step_3()
	{
		$datastore = vB::getDatastore();
		$defaultchannelpermissions = $datastore->getValue('defaultchannelpermissions');

		$changed = false;
		foreach($defaultchannelpermissions AS $node => $groups)
		{
			foreach($groups AS $group => $permissions)
			{
				foreach($permissions AS $name => $value)
				{
					//this should never be an array but a bug in caused arrays to be created instead of values overwritten
					//no good can come of this.  The last value in the array is the one most likely intended as the correct
					//value so we'll go with that.
					if (is_array($value))
					{
						$changed = true;
						$defaultchannelpermissions[$node][$group][$name] = end($value);
					}
				}
			}
		}

		if ($changed)
		{
			$this->show_message(sprintf($this->phrase['core']['rebuild_x_datastore'], 'defaultchannelpermissions'));
			$datastore->build('defaultchannelpermissions', serialize($defaultchannelpermissions), 1);
		}
		else
		{
			$this->skip_message();
		}
	}

	// Steps 4 & 5: Update answered topics in invalid states due to either the answer being soft/hard deleted, or the topic being soft deleted.
	// Edit: Also handle unapproved answers like soft-deleted answers
	// First, unset isanswer for soft-deleted answers
	// Then, fix any topics with either case.
	public function step_4()
	{
		$this->show_message(sprintf($this->phrase['version']['571a4']['unset_deleted_answers_x_of_y'], 1, 2));
		// I don't expect that there are enough relevant nodes to require batching.
		// The fetch query took about 1.7s on a 1m node database with ~60k artificially broken answers for testing.
		$assertor = vB::getDbAssertor();
		$assertor->assertQuery('vBInstall:fixSoftDeletedOrUnapprovedAnswers');
		$this->long_next_step();
	}

	public function step_5()
	{
		$this->show_message(sprintf($this->phrase['version']['571a4']['unset_deleted_answers_x_of_y'], 2, 2));
		// I don't expect that there are enough relevant nodes to require batching.
		// The query took about 5.3s on a 1million node database with 60k ~ 120k topics artificially broken for testing.
		// It's a bit slow, but should be well under any timeouts.
		$assertor = vB::getDbAssertor();
		$assertor->assertQuery('vBInstall:fixAnsweredNodesWithDeletedAnswers');
	}

	// Add admin message to let them know there's a new (empty) terms of service page
	public function step_6()
	{
		$this->add_adminmessage(
			'after_upgrade_check_tos_page',
			[
				'dismissable' => 1,
				'script'      => '',
				'action'      => '',
				'execurl'     => '',
				'method'      => 'get',
				'status'      => 'undone',
			]
		);
	}


	//Prep for step 8: Need to import the settings XML in case this install doesn't have the new option yet.
	public function step_7()
	{
		// Only run once.
		if ($this->iRan(__FUNCTION__))
		{
			return;
		}

		vB_Library::clearCache();
		$this->final_load_settings();
	}

	/*
	 * Copy current threadpreview value into emailpreview. Run once only.
	 */
	public function step_8()
	{
		// Only run once.
		if ($this->iRan(__FUNCTION__))
		{
			return;
		}
		vB_Upgrade::createAdminSession();
		$this->show_message($this->phrase['version']['571a4']['copying_threadpreview']);
		$options = vB::getDatastore()->getValue('options');
		if (isset($options['threadpreview']) AND isset($options['emailpreview']))
		{
			$this->set_option('emailpreview', 'email', intval($options['threadpreview']));
		}
	}


	public function step_9()
	{
		// Placeholder last step allow iRan() to work properly, as the last step gets recorded as step '0' in the upgrade log for CLI upgrade.
		$this->skip_message();
	}

}

class vB_Upgrade_572a1 extends vB_Upgrade_Version
{
	// Steps 1-3 convert old index on paymentinfo.hash to a unique index, revert to old index if collision.
	public function step_1()
	{
		// Only run once.
		if ($this->iRan(__FUNCTION__))
		{
			return;
		}

		// the $overwrite param for add_index does not work properly for this. It only seems to overwrite (i.e. drop the
		// old index before adding the new one) if the old index's constituent columns are different from the new index's
		// columns. As such we have to explicitly drop it before trying to add the new one.
		$this->drop_index(
			sprintf($this->phrase['core']['altering_x_table'], 'paymentinfo', 1, 3),
			'paymentinfo',
			'hash'
		);
	}

	public function step_2()
	{
		// Only run once.
		if ($this->iRan(__FUNCTION__))
		{
			return;
		}

		// See VBV-21266 -- this is extremely unlikely to hit an error, and there aren't great ways to automatically
		// "fix" a hash collision if it does happen. It'll require manual investigation and judgment on which hash to drop.
		// Most likely, it was some manual db changes/inserts that caused this. As such, IF a hash collision does happen
		// while trying to convert the index to UNIQUE, we're going to ignore it, then put the old non-unique index back.

		// Try to add index
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'paymentinfo', 2, 3),
			'paymentinfo',
			'hash',
			['hash', ],
			'unique',
			false,
			[self::MYSQL_ERROR_KEY_EXISTS, self::MYSQL_ERROR_UNIQUE_CONSTRAINT]
		);
	}

	public function step_3()
	{
		// See comment block in step_2(). If the unique index creation hit an error, just
		// quietly put the old index back.
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'paymentinfo', 3, 3),
			'paymentinfo',
			'hash',
			['hash', ]
		);
	}
}

class vB_Upgrade_572a2 extends vB_Upgrade_Version
{
	public function step_1($data = [])
	{
		if (empty($data['startat']))
		{
			$this->show_message(sprintf($this->phrase['core']['delete_orphaned_records_x'], TABLE_PREFIX . 'subscribediscussion'));
		}

		$batchsize = $this->getBatchSize('large', __FUNCTION__);
		$result = $this->proccessChangeBatch('vBInstall:deleteOrphanedSubscriptions', [], $batchsize, $data);
		if (!$result)
		{
			$this->long_next_step();
		}
		return $result;
	}


	public function step_2($data = [])
	{
		if (empty($data['startat']))
		{
			$this->show_message(sprintf($this->phrase['core']['delete_orphaned_records_x'], TABLE_PREFIX . 'closure'));
		}

		$batchsize = $this->getBatchSize('large', __FUNCTION__);
		$result = $this->proccessChangeBatch('vBInstall:deleteOrphanedClosureParents', [], $batchsize, $data);
		if (!$result)
		{
			$this->long_next_step();
		}
		return $result;
	}

	public function step_3($data = [])
	{
		if (empty($data['startat']))
		{
			$this->show_message(sprintf($this->phrase['core']['delete_orphaned_records_x'], TABLE_PREFIX . 'closure'));
		}

		$batchsize = $this->getBatchSize('large', __FUNCTION__);
		return $this->proccessChangeBatch('vBInstall:deleteOrphanedClosureChildren', [], $batchsize, $data);
	}
}

class vB_Upgrade_572a3 extends vB_Upgrade_Version
{
	// Add new bbcode_data table
	public function step_1()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'bbcode_data'),
			"
			CREATE TABLE " . TABLE_PREFIX . "bbcode_data (
				`id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
				`nodeid`      INT UNSIGNED NOT NULL,
				`bbcode_type` VARCHAR(40) NOT NULL DEFAULT '',
				`hash`        CHAR(32) NOT NULL DEFAULT '',
				`data`        MEDIUMTEXT NOT NULL,
				`expires`     INT UNSIGNED NOT NULL DEFAULT '0',
				PRIMARY KEY (`id`),
				INDEX `bbcode_lookup` (`bbcode_type`, `nodeid`),
				UNIQUE INDEX `data_pooling` (`nodeid`, `bbcode_type`, `hash`)
			) ENGINE = " . $this->hightrafficengine . "
			",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}
}

class vB_Upgrade_572a4 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'nodefieldcategorychannel'),
			"
				CREATE TABLE `" . TABLE_PREFIX . "nodefieldcategorychannel` (
					`nodeid` INT UNSIGNED NOT NULL DEFAULT '0',
					`nodefieldcategoryid` INT UNSIGNED NOT NULL DEFAULT '0',
					PRIMARY KEY (`nodeid`, `nodefieldcategoryid`)
				) ENGINE = " . $this->hightrafficengine . "
			",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_2()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'nodefieldcategory'),
			"
				CREATE TABLE `" . TABLE_PREFIX . "nodefieldcategory` (
					`nodefieldcategoryid` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
					`name` VARCHAR(50) NOT NULL DEFAULT '',
					`displayorder` INT UNSIGNED NOT NULL DEFAULT '0',
					UNIQUE KEY `name` (`name`)
				) ENGINE = " . $this->hightrafficengine . "
			",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_3()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'nodefield'),
			"
				CREATE TABLE `" . TABLE_PREFIX . "nodefield` (
					`nodefieldid` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
					`nodefieldcategoryid` INT UNSIGNED NOT NULL,
					`name` VARCHAR(50) NOT NULL DEFAULT '',
					`displayorder` INT UNSIGNED NOT NULL DEFAULT '0',
					`type` ENUM('input', 'select', 'radio', 'textarea', 'checkbox', 'select_multiple') NOT NULL DEFAULT 'input',
					KEY `nodefieldcategoryid` (`nodefieldcategoryid`),
					UNIQUE KEY `name` (`name`)
				) ENGINE = " . $this->hightrafficengine . "
			",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_4()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'nodefieldvalue'),
			"
				CREATE TABLE `" . TABLE_PREFIX . "nodefieldvalue` (
					`nodefieldvalueid` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
					`nodefieldid` INT UNSIGNED NOT NULL,
					`nodeid` INT UNSIGNED NOT NULL,
					`value` TEXT,
					UNIQUE KEY `nodeid_nodefieldid` (`nodeid`, `nodefieldid`),
					KEY `nodefieldid` (`nodefieldid`)
 				) ENGINE = " . $this->hightrafficengine . "
			",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	public function step_5()
	{
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table_x'], TABLE_PREFIX . 'routenew', 1, 1));

		//Reset the RE for conversation route to the default unless the route is custom.
		//(Non custom conversations routes should always be the default RE).
		//In this case to fix an issue when the title contains a space.
		$db = vB::getDbAssertor();
		$routes = $db->assertQuery('routenew', ['class' => 'vB5_Route_Conversation']);
		foreach ($routes AS $route)
		{
			if (!empty($args['customUrl']))
			{
				continue;
			}

			$regex = preg_quote($route['prefix']) . '/' . vB5_Route_Conversation::REGEXP;
			$db->update('routenew', ['regex' => $regex], ['routeid' => $route['routeid']]);
		}
	}
}

class vB_Upgrade_573a3 extends vB_Upgrade_Version
{
	public function step_1()
	{
		//this should be an integer value but gets installed as empty string instead.  This mostly works but in currently
		//PHP versions 0 == '' is not longer true
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table_x'], TABLE_PREFIX . 'setting', 1, 1));

		$assertor = vB::getDbAssertor();
		$row = $assertor->getRow('setting', ['varname' => 'attachfile']);
		if ($row['value'] === '')
		{
			vB_Upgrade::createAdminSession();
			$this->set_option('attachfile', '', 0);
		}
	}
}

class vB_Upgrade_573a4 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->drop_field2('session', 'inforum');
	}

	public function step_2()
	{
		$this->drop_field2('session', 'inthread');
	}

	public function step_3()
	{
		$this->drop_field2('session', 'incalendar');
	}

	public function step_4()
	{
		$this->alter_field2('userauth', 'additional_params', 'TEXT', self::FIELD_DEFAULTS);
	}

	public function step_5()
	{
		$this->alter_field2('sessionauth', 'additional_params', 'TEXT', self::FIELD_DEFAULTS);
	}
}

class vB_Upgrade_574a1 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->drop_table('calendarpermission');
	}

	public function step_2()
	{
		$this->drop_field2('usergroup', 'calendarpermissions');
	}
}

class vB_Upgrade_574a2 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table_x'], TABLE_PREFIX . 'routenew', 1, 1));

		//Reset the RE for conversation route to the default unless the route is custom.
		//(Non custom conversations routes should always be the default RE).
		//In this case to fix an issue when the title contains brackets.
		$db = vB::getDbAssertor();
		$routes = $db->assertQuery('routenew', ['class' => 'vB5_Route_Conversation']);
		foreach ($routes AS $route)
		{
			if (!empty($args['customUrl']))
			{
				continue;
			}

			$regex = preg_quote($route['prefix']) . '/' . vB5_Route_Conversation::REGEXP;
			$db->update('routenew', ['regex' => $regex], ['routeid' => $route['routeid']]);
		}
	}

	public function step_2($data = [])
	{
		if (empty($data['startat']))
		{
			$this->show_message(sprintf($this->phrase['vbphrase']['update_table_x'], TABLE_PREFIX . 'node', 1, 1));
		}

		//There isn't a great way to do the batching here.  We really want an efficient way to batch over all
		//nodes with a open or close bracket in the ident but there just isn't a good way to do that.  We either
		//need to scan for brackets on both the next id query and the process query, which is potentially a table
		//scan over a lot of data.  Or we can just scan over the process which has the problem of requiring
		//painfully small batch sizes, many of which may be empty, or the potentially hitting a batch with too many
		//hits to handle -- we expect a very few affected nodes but can't guarentee it.
		//
		//Hit upon the strategy of walking over all of the nodes in large batches and approximating the ident fix
		//instead of regenerating it entirely via code (which saves an update query for every affected node).
		//Because sql doesn't handle RE replacement gracefully (it's 8.0+ and buggy in some versions) we'll
		//have to do string replacements directly.  Which means that we might get duplicated '-' characters in
		//some cases instead of completely collapsing all sequences of '-' to a single one.  We'll live with it.
		$channeltypeid = vB_Types::instance()->getContentTypeID('vBForum_Channel');
		$walker = new vB_UpdateTableWalker(vB::getDBAssertor());
		$walker->setBatchSize($this->getBatchSize('xlarge', __FUNCTION__));
		$walker->setMaxQuery('vBInstall:getMaxNodeid');
		$walker->setNextidQuery('vBForum:node', 'nodeid');
		$walker->setCallbackQuery('vBInstall:replaceBracketsInTopicIdent', ['channeltypeid' => $channeltypeid]);

		return $this->updateByWalker($walker, $data);
	}

	public function step_3()
	{
		// In specific vb5.x->6 upgrade paths, saveHeaderNavbar() downstream of below will throw errors without
		// these new columns, even though these columns are added in vb6
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

	// Update old header nav default items to use routeid
	public function step_4()
	{
		// first, ensure all routes we're going to use are imported
		//$this->ensureNavBarsRoutes();
		// Edit, importing routes actually requires importing everythign else first (pagetemplates, page, channel)
		// Let's just set the guids for missing routes, and then let upgrade_final clean it up.

		// Now, update the default items. This is tricky because we won't necessarily know which navitems
		// are default and which have been edited by the admin, but still has some of the same attributes.
		// We'll use title & url, and if they both match the default expected values, check the route. If the
		// route matches the expected target route guid, we'll assume it's default & add the route information.
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table_x'], TABLE_PREFIX . 'site', 1, 1));

		$this->addRoutesToDefaultNavbars();
	}

	private function fetchNavbarRouteDefaults($navbar)
	{
		$defaults = [];
		foreach ($navbar AS $__item)
		{
			if (isset($__item['route_guid']))
			{
				$__key = $this->generateUniqueIDForNavitem($__item);
				// This should have url, title, route_guid
				$__copy = $__item;
				unset($__copy['subnav']);
				$defaults[$__key] = $__copy;
			}
			if (!empty($__item['subnav']))
			{
				$defaults = array_merge($defaults, $this->fetchNavbarRouteDefaults($__item['subnav']));
			}
		}

		return $defaults;
	}

	private function generateUniqueIDForNavitem($navitem)
	{
		// Let's use "title" & "url" of the defaults to identify each navitem/subitem entry
		return md5(json_encode([
			$navitem['title'],
			$navitem['url'],
		]));
	}

	private function fixDefaultNavbars(array &$navbar, array $defaultData, array $routes) : bool
	{
		$changed = false;
		foreach ($navbar AS &$__item)
		{
			$__key = $this->generateUniqueIDForNavitem($__item);
			// This indirectly checks that title & url match the defaults
			$__default = $defaultData[$__key] ?? null;
			if ($__default)
			{
				$__guid = $__default['route_guid'];
				$__route = $routes[$__guid] ?? [];
				// this is kind of sketchy
				$__urlMatchesCloseEnough = (
					strpos(trim($__route['prefix'] ?? '', '/'), trim($__default['url'], '/')) !== false
				);
				// If this is a default item (we think, per above), and...
				// If default expected route URL hasn't changed, and isn't a redirect (i.e admin did not change
				// the default page's URL), and this item isn't already associated with a routeid, then we can
				// probably change assume that this is a default navitem that wasn't modified, and can change
				// it to associate with the appropriate routes.
				$__doChange = ($__urlMatchesCloseEnough AND
					(
						// Importing routes requires first importing pagetemplates & pages (& possibly channels?)
						// We're just going to do that again in final upgrade, so let's just let this pass for now
						// and assume that if the route is missing, it's a new one that'll be imported in upgrade-final
						!$__route OR
						!$__route['redirect301'] AND empty($__item['routeid'])
					)
				);

				if ($__doChange)
				{
					if (!empty($__route['routeid']))
					{
						$__item['routeid'] = $__route['routeid'];
					}
					else
					{
						// We may not have this route imported yet.
						// Set it to guid and let upgrade final step_22
						// take care of fixing it.
						$__item['route_guid'] = $__default['route_guid'];
					}
					$changed = true;
				}
			}

			if (!empty($__item['subnav']))
			{
				$changed = $this->fixDefaultNavbars($__item['subnav'], $defaultData, $routes) || $changed;
			}
		}

		return $changed;
	}

	private function addRoutesToDefaultNavbars()
	{
		$siteId = 1;
		$navbars = get_default_navbars();
		$defaultData = array_merge(
			$this->fetchNavbarRouteDefaults($navbars['header']),
			$this->fetchNavbarRouteDefaults($navbars['footer'])
		);

		$guids = array_column($defaultData, 'route_guid');

		/** @var vB_Library_Site */
		$siteLib = vB_Library::instance('site');
		$assertor = vB::getDbAssertor();
		$routes = $assertor->getRows('routenew', ['guid' => $guids], false, 'guid');

		$site = vB::getDbAssertor()->getRow('vBForum:site', ['siteid' => $siteId]);
		$site['headernavbar'] = vB_Utility_Unserialize::unserialize($site['headernavbar'] ?? 'a:0:{}');
		$site['footernavbar'] = vB_Utility_Unserialize::unserialize($site['footernavbar'] ?? 'a:0:{}');

		// Need a session for route URL generation for the route_guid/routeid conversion to URLs downstream of saveNavbar calls...
		vB_Upgrade::createAdminSession();
		$changed = $this->fixDefaultNavbars($site['headernavbar'], $defaultData, $routes);
		if ($changed)
		{
			$siteLib->saveHeaderNavbar($siteId, $site['headernavbar'], true);
		}

		$changed = $this->fixDefaultNavbars($site['footernavbar'], $defaultData, $routes);
		if ($changed)
		{
			$siteLib->saveFooterNavbar($siteId, $site['footernavbar'], true);
		}
	}
}

class vB_Upgrade_575a1 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->add_index2('notification', 'sentbynodeid', ['sentbynodeid']);
	}

	public function step_2($data = [])
	{
		if (empty($data['startat']))
		{
			$this->show_message(sprintf($this->phrase['vbphrase']['update_table_x'], TABLE_PREFIX . 'notification', 1, 1));
		}

		$walker = new vB_UpdateTableWalker(vB::getDBAssertor());
		$walker->setBatchSize($this->getBatchSize('xlarge', __FUNCTION__));
		$walker->setMaxQuery('vBInstall:getMaxNotificationid');
		$walker->setNextidQuery('vBForum:notification', 'notificationid');
		$walker->setCallbackQuery('vBInstall:deleteOrphanedNotifications');

		return $this->updateByWalker($walker, $data);
	}
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision$
|| ####################################################################
\*======================================================================*/
