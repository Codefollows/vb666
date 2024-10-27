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

class vB_Upgrade_550a1 extends vB_Upgrade_Version
{
	// Update orphan `reportnodeid` references for reports pointing to deleted nodes
	public function step_1()
	{
		$this->show_message(sprintf($this->phrase['core']['altering_x_table'], 'report', 1, 1));

		$db = vB::getDbAssertor();
		$db->assertQuery('vBInstall:updateOrphanReports');

		$this->long_next_step();
	}

	// Remove unused `text`.`reportnodeid`
	public function step_2()
	{
		vB_Upgrade::createAdminSession();
		$assertor = vB::getDbAssertor();

		if ($this->field_exists('text', 'reportnodeid'))
	{
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'text', 1, 1),
				"ALTER TABLE " . TABLE_PREFIX . "text
					DROP COLUMN reportnodeid"
			);
		}
		else
		{
			$this->skip_message();
		}
	}
}

class vB_Upgrade_550a2 extends vB_Upgrade_Version
{
	/**
	* Ending version compatibility
	*
	* @var	string
	*/
	public $VERSION_COMPAT_ENDS   = '';

	public function step_1()
	{
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table_x'], 'usergroup', 1, 1));

		$db = vB::getDbAssertor();

		//only change this if the title is the old value.
		$db->update('usergroup',
			array('title' => $this->phrase['install']['usergroup_guest_title']),
			array(
				'systemgroupid' => 1,
				'title' => 'Unregistered / Not Logged In',
			)
		);

		vB_Library::instance('usergroup')->buildDatastore();
	}
}

class vB_Upgrade_551a2 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'node', 1, 1),
			'node',
			'node_parent_lastcontent',
			array('parentid', 'showpublished', 'showapproved', 'lastcontent', 'lastcontentid')
		);
	}
}

class vB_Upgrade_552a2 extends vB_Upgrade_Version
{
	public function step_1()
	{
		/*
			See the note on mysql schema, but in short having the parentid
			index by itself makes it available for mysql optimizer to use in
			some bad index merges when trying to fetch topics in a large channel,
			and anything that uses the parentid index solo can do so by using either
			node_parent_lastcontent(parentid, showpublished, showapproved, lastcontent, lastcontentid),
			or
			node_parent_inlist_lastcontent(parentid, inlist, lastcontent),
			indices, so we *shouldn't* be losing much.
			Dropping this index allows the mysql optimizer to use
			node_parent_lastcontent to short-circuit some specialized topic fetch queries

		 */
		$this->drop_index(
			sprintf($this->phrase['core']['altering_x_table'], 'node', 1, 3),
			'node',
			'node_parent'
		);
		$this->long_next_step();
	}

	public function step_2()
	{
		/*
			Note, node_parent_lastcontent actually works pretty well for the "first page"
			queries, (especially when we replace the showpublished > 0 & showapproved > 0
			to =1's to not inadvertently use range scans on what really should be boolean
			columns).
			However, it seems that this index performs better when there is no equals filter
			on showpublished & showapproved (e.g. an admin or mod viewing the channel).
		*/

		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'node', 2, 3),
			'node',
			'node_parent_inlist_lastcontent',
			array('parentid', 'inlist', 'lastcontent')
		);
		$this->long_next_step();
	}

	public function step_3()
	{
		/*
			Add the parentid,userid index to support queries counting ignored/blacklisted users' topics
		*/

		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'node', 3, 3),
			'node',
			'node_parent_userid',
			array('parentid', 'userid')
		);
	}
}

class vB_Upgrade_552a3 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table_x'], 'paymentapi', 1, 1));
		$db = vB::getDbAssertor();

		//there probably isn't more than one entry with a class of 'authorizenet' but it's possible so
		//we account for it.  None of them are going to work without the signaturekey
		$results = $db->select('vBForum:paymentapi', array('classname' => 'authorizenet'));
		foreach($results AS $row)
		{
			$settings = vB_Utility_Unserialize::unserialize($row['settings']);
			if ($settings)
			{
				unset($settings['authorize_md5secret']);
				if (!isset($settings['signaturekey']))
				{
					$settings['signaturekey'] = array(
						'type' => 'text',
						'value' => '',
						'validate' => 'string',
					);
				}

				$db->update('vBForum:paymentapi', array('settings' => serialize($settings)), array('paymentapiid' => $row['paymentapiid']));
			}
		}
	}

	//this could potentially be collapsed into the prior step, but add_adminmessage doesn't play nice with
	//the messages being sent and it's not worth the effort of figuring that out.  The overhead is trivial.
	public function step_2()
	{
		$db = vB::getDbAssertor();

		//there probably isn't more than one entry with a class of 'authorizenet' but it's possible so
		//we account for it.  Send the message if any of them are active.
		$results = $db->select('vBForum:paymentapi', array('classname' => 'authorizenet'));
		foreach($results AS $row)
		{
			if ($row['active'])
			{
				//if we have muliple active a.net payment entries then we'll only specifically
				//alert for the first one.  It's *really* unlikely that we have more than one
				//and we don't want to spam this message (there really isn't an option for allow
				//duplicates but only if the url is different).  We'll just live with the fact that
				//the one admin affected will need to figure out they have to update all of them.
				$this->add_adminmessage(
					'anet_signaturekey_needs_updating',
					array(
						'dismissable' => 1,
						'script'      => '',
						'action'      => '',
						'execurl'     => 'subscriptions.php?do=apiedit&paymentapiid=' . $row['paymentapiid'],
						'method'      => 'get',
						'status'      => 'undone',
					)
				);

				//if we got this far, we're done here.
				return;
			}
		}

		//we didn't need to post the message.
		$this->skip_message();
	}

	/**
	 * Convert instances of group category modules/widgets to standard channel navigation modules
	 */
	public function step_3()
	{
		$result = $this->replaceModule(
			// the old (now removed) group categories module
			'vbulletin-widget_sgcategories-4eb423cfd6dea7.34930860',
			// the standard channel navigation module we're replacing it with
			'vbulletin-widget_cmschannelnavigation-4eb423cfd6dea7.34930875',
			// the default admin config we want to use for the new module instances
			// this matches the serialized config in vbulletin-pagetemplates.xml for
			// the channel navigation modules that replace the old ones
			// copying the serialized value here for easier maintenance and to avoid
			// typos at the expense of an unserialize call
			unserialize('a:4:{s:12:"root_channel";s:45:"channelguid:vbulletin-4ecbdf567f3a38.99555306";s:5:"title";s:30:"phrase:social_group_categories";s:5:"depth";s:1:"1";s:17:"hide_root_channel";s:1:"1";}')
		);

		if ($result['updated'])
		{
			$this->show_message(sprintf($this->phrase['version']['552a3']['converting_group_category_modules_to_channel_navigation_modules_x_module_instances_updated'], $result['instancesDeleted']));
		}
		else
		{
			$this->skip_message();
		}
	}
}

class vB_Upgrade_552a4 extends vB_Upgrade_Version
{
	/**
	 * Convert widgetinstance.adminconfig. See VBV-19237
	 *
	 * @param null $data
	 */
	public function step_1($data = null)
	{
		$config = vB::getConfig();
		$assertor = vB::getDbAssertor();
		//First we need to get the default character set for table and database.  Because
		// the column charset can be set at the database, table, or column.  If the field is
		// already utf8 or utf8mb4 we do nothing.
		$defaultCharset = false;
		$dbCreate = $assertor->assertQuery(
			'vBInstall:getDbStructure',
			array('dbName' => $config['Database']['dbname'])
		);

		$dbInfo = $dbCreate->current();

		if ($dbInfo = $dbInfo['Create Database'])
		{
			$matches = array();

			if (preg_match("~DEFAULT CHARACTER SET (\w+)~i", $dbInfo, $matches)
				AND !empty($matches[1]))
			{
				$defaultCharset = $matches[1];
			}
		}

		$structure = $assertor->getRow(
			'vBInstall:getTableStructure',
			array('tablename' => 'widgetinstance')
		);

		$lines = explode("\n", $structure['Create Table']);
		$changeit = false;
		$changeCharset = false;

		foreach ($lines AS $line)
		{
			if (strpos($line, 'adminconfig') !== false)
			{
				$matches = array();

				if (strpos($line, 'blob'))
				{
					$changeit = true;
				}

				if (preg_match("~DEFAULT CHARSET\s?=\s?(\w+)~i", $line, $matches)
					AND !empty($matches[1]))
				{
					$charset = $matches[1];
				}
			}
			else if (strpos($line, 'ENGINE') !== false)
			{
				if (preg_match("~DEFAULT CHARSET\s?=\s?(\w+)~i", $line, $matches)
					AND !empty($matches[1]))
				{
					$defaultCharset = $matches[1];
				}
			}
		}

		if (empty($charset))
		{
			$charset = $defaultCharset;
		}

		if (empty($charset) OR ($charset != 'utf8'))
		{
			$changeCharset = true;
		}

		if ($changeit)
		{
			$this->show_message($this->phrase['version']['552a4']['updating_widgetinstance_adminconfig']);

			if ($changeCharset)
			{
				$assertor->assertQuery('vBInstall:makeWidgetInstanceConfBinary', array());
				$assertor->assertQuery('vBInstall:updtWidgetInstanceConf', array());
			}
			$assertor->assertQuery('vBInstall:makeWidgetInstanceConfUtf8', array());
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	 * Add the new default widget instances to group related pages if they are not already
	 * there. As the normal default, we don't add new widget instances, but in some cases
	 * it makes sense to do so.
	 */
	public function step_2()
	{
		vB_Upgrade::createAdminSession();
		$assertor = vB::getDbAssertor();

		$this->show_message(sprintf($this->phrase['vbphrase']['importing_file'], 'vbulletin-pagetemplates.xml'));

		$pageTemplateFile = DIR . '/install/vbulletin-pagetemplates.xml';
		if (!($xml = file_read($pageTemplateFile)))
		{
			$this->add_error(sprintf($this->phrase['vbphrase']['file_not_found'], 'vbulletin-pagetemplates.xml'), self::PHP_TRIGGER_ERROR, true);
			return;
		}

		// update the specified pages, inserting specific widget instances
		// that were added in this release
		$options = vB_Xml_Import::OPTION_ADDSPECIFICWIDGETS;
		$xml_importer = new vB_Xml_Import_PageTemplate('vbulletin', $options);

		// set up the specific modules that need to be added to what pages
		$modulesToAdd = array(
			// ----- GROUPS HOME PAGE -----
			// Group Categories Module (channel navigation module)
			array(
				'pagetemplateguid' => 'vbulletin-4ecbdac93742a5.43676037',
				'widgetguid' => 'vbulletin-widget_cmschannelnavigation-4eb423cfd6dea7.34930875',
			),
			// ----- GROUPS CATEGORY PAGE -----
			// My Groups Module (search module)
			array(
				'pagetemplateguid' => 'vbulletin-sgcatlist93742a5.43676040',
				'widgetguid' => 'vbulletin-widget_search-4eb423cfd6a5f3.08329785',
				// comparison function needed since there might be other
				// search modules on this page template
				'comparisonFunction' => function($existingModuleInstance)
				{
					$adminconfig = $existingModuleInstance['adminconfig'];
					if (!empty($adminconfig['searchJSON']))
					{
						$searchJSON = json_decode($adminconfig['searchJSON'], true);
						if (
							$searchJSON AND
							!empty($searchJSON['my_channels']) AND
							!empty($searchJSON['my_channels']['type']) AND
							$searchJSON['my_channels']['type'] == 'group'
						)
						{
							return true;
						}
					}

					return false;
				},
			),
			// ----- GROUP PAGE -----
			// Group Categories Module (channel navigation module)
			array(
				'pagetemplateguid' => 'vbulletin-sgroups93742a5.43676038',
				'widgetguid' => 'vbulletin-widget_cmschannelnavigation-4eb423cfd6dea7.34930875',
			),
			// Latest Group Topics Module (search module)
			array(
				'pagetemplateguid' => 'vbulletin-sgroups93742a5.43676038',
				'widgetguid' => 'vbulletin-widget_search-4eb423cfd6a5f3.08329785',
				// comparison function needed since there might be other
				// search modules on this page template
				'comparisonFunction' => function($existingModuleInstance) use ($assertor)
				{
					$adminconfig = $existingModuleInstance['adminconfig'];
					if (!empty($adminconfig['searchJSON']))
					{
						$searchJSON = json_decode($adminconfig['searchJSON'], true);
						if ($searchJSON)
						{
							$groupsChannelGuid = 'vbulletin-4ecbdf567f3a38.99555306';

							if (!empty($searchJSON['channelguid']) AND $searchJSON['channelguid'] == $groupsChannelGuid)
							{
								return true;
							}

							if (!empty($searchJSON['channel']))
							{
								$channel = $assertor->getRow('vBForum:channel', array('channelid' => $searchJSON['channel']));
								if ($channel['guid'] == $groupsChannelGuid)
								{
									return true;
								}
							}
						}
					}

					return false;
				},
			),
			// ----- GROUP TOPIC PAGE -----
			// Group Summary Module
			array(
				'pagetemplateguid' => 'vbulletin-sgtopic93742a5.43676039',
				'widgetguid' => 'vbulletin-widget_groupsummary-4eb423cfd6dea7.34930863',
			),
		);
		$xml_importer->setWidgetsToAdd($modulesToAdd);

		// only modify these page templates
		$onlyThisGuid = array(
			// groups home
			'vbulletin-4ecbdac93742a5.43676037',
			// groups category page
			'vbulletin-sgcatlist93742a5.43676040',
			// group
			'vbulletin-sgroups93742a5.43676038',
			// group discussion/topic
			'vbulletin-sgtopic93742a5.43676039',
		);
		$xml_importer->importFromFile($pageTemplateFile, $onlyThisGuid);

		$this->show_message($this->phrase['core']['import_done']);
	}

	public function step_3()
	{
		//note that we actually do two updates, but this isn't substantial enough to warrent the overhead
		//of an extra upgrade step.  If this times out the webserver, there are much larger problems.
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table_x'], TABLE_PREFIX . 'usergroup', 1, 1));

		$db = vB::getDbAssertor();

		$datastore = vB::getDatastore();
		$permissions = $datastore->getValue("bf_ugp_genericoptions");
		$perm = $permissions['showmemberlist'];

		//first turn the flag on for all groups.
		$db->update('usergroup',
			array(
				vB_dB_Query::BITFIELDS_KEY => array (
					array('field' => 'genericoptions', 'operator' => vB_dB_Query::BITFIELDS_SET, 'value' => $perm),
				)
			),
			vB_dB_Query::CONDITION_ALL
		);

		$groupsToExclude = array(
			vB_Api_UserGroup::UNREGISTERED_SYSGROUPID,
			vB_Api_UserGroup::AWAITINGEMAIL_SYSGROUPID,
			vB_Api_UserGroup::AWAITINGMODERATION_SYSGROUPID,
			vB_Api_UserGroup::BANNED,
		);

		//the remove the flag for the ones that don't pick it up in the install
		$db->update('usergroup',
			array(
				vB_dB_Query::BITFIELDS_KEY => array (
					array('field' => 'genericoptions', 'operator' => vB_dB_Query::BITFIELDS_UNSET, 'value' => $perm),
				)
			),
			array(
				'systemgroupid' => $groupsToExclude
			)
		);
	}

	public function step_4()
	{
		if ($this->field_exists('language', 'imagesoverride'))
		{
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], TABLE_PREFIX . 'language', 1, 1),
				"ALTER TABLE " . TABLE_PREFIX . "language DROP COLUMN imagesoverride"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_5()
	{
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table_x'], TABLE_PREFIX . 'widgetinstance', 1, 1));

		require_once(DIR . '/install/unserializefix.php');
		$db = vB::getDbAssertor();

		$result = $db->select('widgetinstance', [], false, ['adminconfig', 'widgetinstanceid']);
		foreach($result AS $row)
		{
			$data = $row['adminconfig'];
			$widgetinstanceid = $row['widgetinstanceid'];

			if (strlen($data))
			{
				if (unserialize($data) === false)
				{
					$value = vB_Install_UnserializeFix::unserialize($data);
					$value = serialize($value);

					$db->update('widgetinstance', ['adminconfig' => $value], ['widgetinstanceid' => $widgetinstanceid]);
				}
			}
		}
	}
}

class vB_Upgrade_552b1 extends vB_Upgrade_Version
{
	/**
	 * Fix the incorrect pagetemplateid for pages associated with Group channels
	 * that existed when the installation was upgraded from vB4. They were using
	 * the default page template for forum topics (vB_Page::TEMPLATE_CONVERSATION)
	 * instead of the default page template for group topics which is
	 * vB_Page::TEMPLATE_SOCIALGROUPCONVERSATION. If there are group channel pages
	 * that are using the incorrect *default* they will be changed to the correct
	 * default. If they are using any other page template (for example, a custom
	 * page template), they will not be changed.
	 */
	public function step_1()
	{
		$assertor = vB::getDbAssertor();

		// create a lookup table for all group channels
		$groupParentChannel = $assertor->getRow('vBForum:channel', array('guid' => vB_Channel::DEFAULT_SOCIALGROUP_PARENT));
		$channelNodeIds = $assertor->getColumn('vBForum:channel', 'nodeid', array());
		$groupChannelLookup = $assertor->getColumn('vBForum:closure', 'child', array(
			'parent' => $groupParentChannel['nodeid'],
			'child' => $channelNodeIds,
		), false, 'child');

		// scan all conversation (topic) routes to isolate the ones that
		// are for group topic pages, based on the route arguments
		$groupChannelPageIds = array();
		$routes = $assertor->getRows('routenew', array('class' => 'vB5_Route_Conversation'));
		foreach ($routes AS $route)
		{
			$args = array();
			if (!empty($route['arguments']))
			{
				$temp = unserialize($route['arguments']);
				if ($temp)
				{
					$args = $temp;
				}
			}

			if (!empty($args['channelid']))
			{
				$channelId = (int) $args['channelid'];
				if (!empty($groupChannelLookup[$channelId]))
				{
					$pageId = (int) $args['pageid'];
					if ($pageId)
					{
						$groupChannelPageIds[] = $pageId;
					}
				}
			}
		}

		// if we have group channel pages, check if any of them need to be updated
		$updated = 0;
		if (!empty($groupChannelPageIds))
		{
			// get pages that need to be updated
			$conversationPageTemplate = $assertor->getRow('pagetemplate', array('guid' => vB_Page::TEMPLATE_CONVERSATION));
			$conditions = array(
				'pagetemplateid' => $conversationPageTemplate['pagetemplateid'],
				'pageid' => $groupChannelPageIds,
			);
			$pageIds = $assertor->getColumn('vBForum:page', 'pageid', $conditions);

			if (!empty($pageIds))
			{
				// we found some pages that have this issue
				// update the pagetemplate from the default forum topic page template
				// to the default group topic page template
				$groupConversationPageTemplate = $assertor->getRow('pagetemplate', array('guid' => vB_Page::TEMPLATE_SOCIALGROUPCONVERSATION));
				$values = array(
					'pagetemplateid' => $groupConversationPageTemplate['pagetemplateid'],
				);
				$updated = $assertor->update('vBForum:page', $values, $conditions);
			}
		}

		// tell the user what we did
		if ($updated)
		{
			$this->show_message(sprintf($this->phrase['version']['552b1']['updating_group_topic_page_templates_x'], $updated));
		}
		else
		{
			$this->skip_message();
		}
	}
}

class vB_Upgrade_553a1 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$db = vB::getDbAssertor();

		$datastore = vB::getDatastore();
		$permissions = $datastore->getValue('bf_ugp_forumpermissions2');
		$topicperm = $permissions['skipmoderatetopics'];
		$replyperm = $permissions['skipmoderatereplies'];
		$attachperm = $permissions['skipmoderateattach'];

		//we're going to remove this field so check for reentrance.
		if ($this->field_exists('permission', 'skip_moderate'))
		{
			$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'permission'));

			//set the new perm bit fields to match the skip_moderate field
			$db->update('vBForum:permission',
				array(
					vB_dB_Query::BITFIELDS_KEY => array (
						array('field' => 'forumpermissions2', 'operator' => vB_dB_Query::BITFIELDS_SET, 'value' => $topicperm),
						array('field' => 'forumpermissions2', 'operator' => vB_dB_Query::BITFIELDS_SET, 'value' => $replyperm),
						array('field' => 'forumpermissions2', 'operator' => vB_dB_Query::BITFIELDS_SET, 'value' => $attachperm),
					)
				),
				array('skip_moderate' => 1)
			);

			//set the new perm bit fields to match the skip_moderate field
			$db->update('vBForum:permission',
				array(
					vB_dB_Query::BITFIELDS_KEY => array (
						array('field' => 'forumpermissions2', 'operator' => vB_dB_Query::BITFIELDS_UNSET, 'value' => $topicperm),
						array('field' => 'forumpermissions2', 'operator' => vB_dB_Query::BITFIELDS_UNSET, 'value' => $replyperm),
						array('field' => 'forumpermissions2', 'operator' => vB_dB_Query::BITFIELDS_UNSET, 'value' => $attachperm),
					)
				),
				array('skip_moderate' => 0)
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_2()
	{
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], 'permission', 1, 1),
			"permission",
			"skip_moderate"
		);
	}
}

class vB_Upgrade_553a2 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->drop_table('customprofilepic');
	}

	public function step_2()
	{
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], TABLE_PREFIX . 'usergroup', 1, 3),
			'usergroup',
			'profilepicmaxwidth'
		);
	}

	public function step_3()
	{
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], TABLE_PREFIX . 'usergroup', 2, 3),
			'usergroup',
			'profilepicmaxheight'
		);
	}

	public function step_4()
	{
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], TABLE_PREFIX . 'usergroup', 3, 3),
			'usergroup',
			'profilepicmaxsize'
		);
	}

	public function step_5()
	{
		$db = vB::getDbAssertor();

		$datastore = vB::getDatastore();
		$forumpermissions = $datastore->getValue('bf_ugp_forumpermissions2');
		$topicperm = $forumpermissions['skipmoderatetopics'];
		$replyperm = $forumpermissions['skipmoderatereplies'];
		$attachperm = $forumpermissions['skipmoderateattach'];

		$permissions = $datastore->getValue('bf_ugp_genericoptions');
		$notbannedgroup = $permissions['isnotbannedgroup'];

		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'usergroup'));
		//we don't have skip_moderate for the usergroups -- it's only on the forumpermission record.  It
		//does not appear that we are actually doing anything with the formpermission2 record -- changing
		//it on the user group page actually sets it on the root forum -- so let's set some sensisble defaults
		//that should sync the behavior with new installs and call it a day.

		//set the permissions to true if the group is not banned.
		$db->update('usergroup',
			array(
				vB_dB_Query::BITFIELDS_KEY => array (
					array('field' => 'forumpermissions2', 'operator' => vB_dB_Query::BITFIELDS_SET, 'value' => $topicperm),
					array('field' => 'forumpermissions2', 'operator' => vB_dB_Query::BITFIELDS_SET, 'value' => $replyperm),
					array('field' => 'forumpermissions2', 'operator' => vB_dB_Query::BITFIELDS_SET, 'value' => $attachperm),
				)
			),
			array(array('field' => 'genericoptions', 'operator' => vB_dB_Query::OPERATOR_AND, 'value' => $notbannedgroup))
		);

		//set the permissions to false if the group is banned.
		$db->update('usergroup',
			array(
				vB_dB_Query::BITFIELDS_KEY => array (
					array('field' => 'forumpermissions2', 'operator' => vB_dB_Query::BITFIELDS_UNSET, 'value' => $topicperm),
					array('field' => 'forumpermissions2', 'operator' => vB_dB_Query::BITFIELDS_UNSET, 'value' => $replyperm),
					array('field' => 'forumpermissions2', 'operator' => vB_dB_Query::BITFIELDS_UNSET, 'value' => $attachperm),
				)
			),
			array(array('field' => 'genericoptions', 'operator' => vB_dB_Query::OPERATOR_NAND, 'value' => $notbannedgroup))
		);
	}

	// Add covering index to support fcm_cron's fetching fcmessage_offload by receiveafter
	public function step_6()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'fcmessage_offload', 1, 1),
			'fcmessage_offload',
			'removeafter_hash',
			array('removeafter', 'hash')
		);
	}

	public function step_7()
	{
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], TABLE_PREFIX . 'user', 1, 3),
			'user',
			'profilepicrevision'
		);
	}

	public function step_8()
	{
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], TABLE_PREFIX . 'user', 2, 3),
			'user',
			'logintype'
		);
	}

	public function step_9()
	{
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], TABLE_PREFIX . 'user', 3, 3),
			'user',
			'fbaccesstoken'
		);
	}

	/**
	 * Update user.startofweek to 1 (Sunday), if they currently have -1 (an invalid value)
	 */
	public function step_10($data = null)
	{
		if (empty($data['startat']))
		{
			$this->show_message(sprintf($this->phrase['vbphrase']['update_table_x'], TABLE_PREFIX . 'user', 1, 1));
		}

		$filter = ['startofweek' => -1];

		$walker = new vB_UpdateTableWalker(vB::getDBAssertor());
		$walker->setBatchSize($this->getBatchSize('small', __FUNCTION__));
		$walker->setMaxQuery('vBinstall:getMaxUserid');
		$walker->setNextidQuery('user', 'userid', $filter);
		$walker->setCallbackUpdateTable(['startofweek' => 1], $filter);

		return $this->updateByWalker($walker, $data);
	}
}

class vB_Upgrade_553a4 extends vB_Upgrade_Version
{
	public function step_1()
	{
		/*
			We used to always append node.nodeid ASC sorting to every single search
			query.
			We've changed that to only append it when it's needed (when there's a
			created ASC|DESC sort), and to *match* the direction of the created sorting.
			The same direction allows mysql to use a (created, nodeid) index to sort
			and speed up certain queries.
		 */
		$this->drop_index(
			sprintf($this->phrase['core']['altering_x_table'], 'node', 1, 2),
			'node',
			'created'
		);
		$this->long_next_step();
	}

	public function step_2()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'node', 2, 2),
			'node',
			'created',
			array('created', 'nodeid')
		);
	}

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

	// Update the permissions for skip_moderate in 'defaultchannelpermissions'
	public function step_4()
	{
		$datastore = vB::getDatastore();
		$defaultchannelpermissions = $datastore->getValue('defaultchannelpermissions');
		if (empty($defaultchannelpermissions))
		{
			// if this isn't created yet, then there's nothing to do.
			return $this->skip_message();
		}

		if (!is_array($defaultchannelpermissions))
		{
			$defaultchannelpermissions = vB_Utility_Unserialize::unserialize($defaultchannelpermissions);
		}

		$forumpermissions = $datastore->getValue('bf_ugp_forumpermissions2');
		$topicperm = $forumpermissions['skipmoderatetopics'];
		$replyperm = $forumpermissions['skipmoderatereplies'];
		$attachperm = $forumpermissions['skipmoderateattach'];

		$allperms = $topicperm | $replyperm | $attachperm;

		$needsRebuild = false;
		foreach($defaultchannelpermissions AS $__nodekey => $__innerArr)
		{
			foreach ($__innerArr AS $__groupkey => $permissions)
			{
				if (isset($permissions['skip_moderate']))
				{
					$needsRebuild = true;
					if ($permissions['skip_moderate'])
					{
						$defaultchannelpermissions[$__nodekey][$__groupkey]['forumpermissions2'] |= $allperms;
					}
					else
					{
						$defaultchannelpermissions[$__nodekey][$__groupkey]['forumpermissions2'] &= ~$allperms;
					}

					unset($defaultchannelpermissions[$__nodekey][$__groupkey]['skip_moderate']);
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

class vB_Upgrade_554a1 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$db = vB::getDbAssertor();
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'faq'));
		$db->delete('vBForum:faq', array('faqname' => 'general_facebook_publish'));
	}

	/**
	 * Add 'monitoredword' notification type to 'about' in privatemessage
	 */
	public function step_2()
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

class vB_Upgrade_554a2 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->show_message($this->phrase['version']['554a2']['update_imagetype']);
		$current = vB::getDatastore()->getOption('imagetype');

		if (empty($current))
		{
			vB::getDatastore()->setOption('imagetype', 'GD', true);
		}
	}

	public function step_2()
	{
		$this->show_message($this->phrase['version']['552a4']['updating_widgetinstance_adminconfig']);

		$db = vB::getDbAssertor();
		$row = $db->getRow('vBInstall:getWidgetInstanceAdminDetails', array());
		//this should already be changed to utf8.  If it isn't let's not change it to avoid
		//breaking the field -- especially the serialization.
		if (stripos($row['Collation'], 'utf8_') === 0)
		{
			$db->assertQuery('vBInstall:makeWidgetInstanceConfUtf8mb4');
		}
	}
}

class vB_Upgrade_554a4 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->drop_table('picturecomment');
	}

	public function step_2()
	{
		$this->drop_table('picturecomment_hash');
	}

	//several fields were set in a prior upgrade step, but not changed for new installs
	//we need to repeat this for sites installed in the interim.
	public function step_3()
	{
		$this->updateIPField('node', 'ipaddress');
	}

	public function step_4()
	{
		$this->updateIPField('session', 'host');
	}

	public function step_5()
	{
		$this->updateIPField('apiclient', 'initialipaddress');
	}

	public function step_6()
	{
		$this->updateIPField('apilog', 'ipaddress');
	}

	public function step_7()
	{
		$this->updateIPField('searchlog', 'ipaddress');
	}

	public function step_8()
	{
		$this->updateIPField('userchangelog', 'ipaddress');
	}

	public function step_9()
	{
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], 'userchangelog'));
		$db = vB::getDbAssertor();
		$db->assertQuery('vBInstall:updateUserchangeLogIp', array());
	}

	//Maybe this should be a protected function on the parent class.  However I'm hoping it
	//won't be a thing over multiple versions and I don't really want to clutter the parent
	//class with a function that's going to be of limited use. On the other hand I don't
	//want to case and paste a dozen versions of this.
	private function updateIPField($table, $field)
	{
		$this->alter_field(
			sprintf($this->phrase['core']['altering_x_table'], $table, 1, 1),
			$table,
			$field,
			'varchar',
			array(
				'length' => 45,
				'attributes' => self::FIELD_DEFAULTS
			)
		);
	}

}

class vB_Upgrade_555a2 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->alter_field(
			sprintf($this->phrase['core']['altering_x_table'], 'contentpriority', 1, 1),
			'contentpriority',
			'prioritylevel',
			'double',
			array(
				'length' => '2,1',
			)
		);
	}

	public function step_2()
	{
		$this->show_message($this->phrase['version']['555a2']['remove_product_custom_phrases']);

		/*
			Delete invalid "custom" (languageid = 0) phrases that were added for fresh installs with
			language packs due to step_3() importing the product translation XMLs before step_13() could
			import the product XMLs (that contain the master phrases).

			While it's hypothetically possible that non-vbulletin products might've been affected, I'm
			trying to limit the scope of change to the ones we definitively saw on the affected cloud
			installs.
		 */
		$products = vB_Products::DEFAULT_VBULLETIN_PRODUCTS;
		if (!empty($products))
		{
			$assertor = vB::getDbAssertor();
			$assertor->delete('phrase', array('languageid' => 0, 'product' => $products));
		}
	}
}

class vB_Upgrade_555a3 extends vB_Upgrade_Version
{
	public function step_1()
	{
		// noop step just to output the long step message
		// for updating the birthdayemail user option
		$this->long_next_step();
	}

	// Set the user 'birthdayemail' option to 'on' for all existing users. There previously
	// was no option and all users would get the email.
	public function step_2()
	{
		$assertor = vB::getDbAssertor();

		// bitfields are rebuilt as part of the upgrade initialization, so the
		// new bitfield will already be present here (see class_upgrade init())
		$bf_misc_useroptions = vB::getDatastore()->getValue('bf_misc_useroptions');

		$check = $assertor->getRow('vBInstall:checkUserOptionBirthdayEmails', array(
			'birthdayemailmask' => $bf_misc_useroptions['birthdayemail'],
		));

		if (!$check)
		{
			$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'user'));

			// If setting this bit in one go turns out to be too much, we can use
			// updateByIdWalk(). But we allow this type of query in the Admin CP
			// query tool, so I think it will be okay here. Added the 'long next step'
			// message just to be on the safe side.
			$result = $assertor->assertQuery('vBInstall:updateUserOptionBirthdayEmails', array(
				'birthdayemailmask' => $bf_misc_useroptions['birthdayemail'],
			));
		}
		else
		{
			// There is at least one user with the birthday email option turned on, so
			// we'll assume that this step has already run or that v555a3 or later has
			// already been installed. In either case, we don't want to run this step
			// and risk enabling 'birthdayemail' for users who have already turned it
			// off.
			$this->skip_message();
		}
	}
}

class vB_Upgrade_555a4 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'noticecriteria'));
		$db = vB::getDbAssertor();
		$db->assertQuery('vBinstall:updateNotificationDateCriteria', array());
	}
}

class vB_Upgrade_556a1 extends vB_Upgrade_Version
{
	public function step_1()
	{
		// import vbulletin-settings.xml so "useemoji" exists for step 2
		vB_Library::clearCache();
		$this->final_load_settings();
	}

	public function step_2()
	{
		// See also vB_Upgrade_install::step_5
		$assertor = vB::getDbAssertor();
		$charsets = $assertor->getDbCharsets('text', 'rawtext');
		if ($charsets['effectiveCharset'] == 'utf8mb4')
		{
			$this->show_message($this->phrase['version']['556a1']['enabling_ckeditor_emoji_plugin']);

			vB_Upgrade::createAdminSession();
			// NOTE: set_option requires the option to already exist or else it won't set it.
			$this->set_option('useemoji', '', 1);
		}
		else
		{
			$this->skip_message();
		}
	}


}

class vB_Upgrade_556a2 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], TABLE_PREFIX . 'notice', 1, 1),
			'notice',
			'noticeoptions',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_2()
	{
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'routenew'));
		$db = vB::getDbAssertor();

		$bitfields = vB::getDatastore()->getValue('bf_misc_announcementoptions');

		$options = 0;
		$options |= $bitfields['allowhtml'];

		$db->update('vBForum:notice', array('noticeoptions' => $options), array('noticeoptions' => 0));
	}

	public function step_3()
	{
		$this->show_message(sprintf($this->phrase['core']['rebuild_x_datastore'], 'noticecache'));
		vB_Library::instance('notice')->buildNoticeDatastore();
	}

	public function step_4()
	{
		$this->add_field(
			sprintf($this->phrase['vbphrase']['alter_table'], 'profilefield'),
			'profilefield',
			'showonpost',
			'smallint',
			self::FIELD_DEFAULTS
		);
	}

	public function step_5()
	{
		$this->show_message(sprintf($this->phrase['core']['rebuild_x_datastore'], 'profilefield'));
		vB_Library::instance('user')->buildProfileFieldDatastore();
	}
}

class vB_Upgrade_556a3 extends vB_Upgrade_Version
{
	public function step_1()
	{
		//We previously treated a blank value for the cssfilelocation to 'clientscript/vbulletin_css'
		//This isn't really in line with how we deal with options in general and we want to put in
		//a real value at all times (and change it while we're at it).  This will also put things
		//more in line with how we handle the template file cache.
		$options = vB::getDatastore()->getValue('options');
		if (!$options['cssfilelocation'])
		{
			$db = vB::getDbAssertor();

			//Calling $this->set_option will potentially trigger a style rebuild as a side effect of
			//changing the file path.  We don't want to do that in an upgrade step as it can take a
			//while and will do it again when we import the master style (not to mention that we
			//don't actually need it because we won't be changing the actual path of a live directory).
			//
			//if storecssasfile is in use we need to preserve the old behavior to avoid breaking the site
			//if it's not, then we'll just update to the current default.
			$path = ($options['storecssasfile'] ? 'clientscript/vbulletin_css' : 'cache/css');
			$db->update('setting', array('value' => $path), array('varname' => 'cssfilelocation'));
			$this->show_message(sprintf($this->phrase['vbphrase']['update_table_x'], TABLE_PREFIX . 'setting', 1, 1));
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_2()
	{
		$this->alter_field(
			sprintf($this->phrase['core']['altering_x_table'], 'smilie', 1, 1),
			'smilie',
			'smilietext',
			'VARCHAR',
			array('length' => 100, 'null' => false, 'default' => '')
		);
	}
}

class vB_Upgrade_556a4 extends vB_Upgrade_Version
{
	/**
	 * Handle customized values (in custom styles) for stylevars that have been renamed
	 */
	public function step_1()
	{
		$mapper = new vB_Stylevar_Mapper();

		// Map the entire stylevar value from old to new since this is only a rename
		// No need for mapping of any of the stylevar parts or any presets, since
		// we only renamed the stylevar and didn't change the data type.
		$mapper->addMapping('icon_size_forum_icon', 'forum_icon_size');

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
	public function step_2()
	{
		$mapper = new vB_Stylevar_Mapper();
		$result = $mapper->updateInheritance('icon_size_forum_icon', 'forum_icon_size');

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

class vB_Upgrade_557a2 extends vB_Upgrade_Version
{
	// create the eventhighlight table
	public function step_1()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'eventhighlight'),
			"
				CREATE TABLE " . TABLE_PREFIX . "eventhighlight (
					eventhighlightid INT UNSIGNED NOT NULL AUTO_INCREMENT,
					backgroundcolor VARCHAR(50) NOT NULL DEFAULT '',
					textcolor VARCHAR(50) NOT NULL DEFAULT '',
					displayorder INT UNSIGNED NOT NULL DEFAULT '0',
					denybydefault TINYINT UNSIGNED NOT NULL DEFAULT '1',
					PRIMARY KEY (eventhighlightid),
					INDEX displayorder (displayorder)
				) ENGINE = " . $this->hightrafficengine . "
			",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	// create the eventhighlightpermission table
	public function step_2()
	{
		$this->run_query(
			sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'eventhighlightpermission'),
			"
				CREATE TABLE " . TABLE_PREFIX . "eventhighlightpermission (
					eventhighlightid INT UNSIGNED NOT NULL DEFAULT '0',
					usergroupid INT UNSIGNED NOT NULL DEFAULT '0',
					UNIQUE INDEX eventhighlightid (eventhighlightid, usergroupid)
				) ENGINE = " . $this->hightrafficengine . "
			",
			self::MYSQL_ERROR_TABLE_EXISTS
		);
	}

	// add 'eventhighlightid' column to the event table
	public function step_3()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'event', 1, 1),
			'event',
			'eventhighlightid',
			'INT',
			array(
				'attributes' => 'UNSIGNED',
				'null'       => false,
				'default'    => '0',
				'extra'      => '',
			)
		);
	}
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 112190 $
|| ####################################################################
\*======================================================================*/
