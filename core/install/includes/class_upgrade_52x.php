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

class vB_Upgrade_520a1 extends vB_Upgrade_Version
{
	/**
	 * Insert a preview image where missing (VBV-13883)
	 * This step is very similar to 513b1 step_4
	 */
	public function step_1($data = null)
	{
		$startat = (int) isset($data['startat']) ? $data['startat'] : 0;
		$batchsize = 100;

		$assertor = vB::getDbAssertor();
		vB_Upgrade::createAdminSession();

		if ($startat == 0)
		{
			$this->show_message(sprintf($this->phrase['version']['520a1']['updating_photo_preview_images']));
		}

		$galleryContentTypeId =  vB_Types::instance()->getContentTypeID('vBForum_Gallery');
		$rootChannelId = vB_Api::instanceInternal('Content_Channel')->fetchChannelIdByGUID(vB_Channel::MAIN_CHANNEL);

		// Get nodeids
		$rows = $assertor->assertQuery('vBInstall:getPhotoNodesWithMissingPreviewImage', array(
			'root_channel' => $rootChannelId,
			'gallery_contenttypeid' => $galleryContentTypeId,
			'batchsize' => $batchsize,
			'last_processed_nodeid' => $startat,
		));
		$nodeids = array();
		foreach ($rows AS $row)
		{
			$nodeids[] = $row['nodeid'];
		}

		if (empty($nodeids))
		{
			// done
			$this->show_message(sprintf($this->phrase['core']['process_done']));

			return;
		}
		else
		{
			// Assign preview images
			foreach ($nodeids AS $nodeid)
			{
				vB_Library::instance('Content_Gallery')->autoPopulatePreviewImage($nodeid);
			}

			$firstNodeId = min($nodeids);
			$lastNodeId = max($nodeids);

			// output progress
			$this->show_message(sprintf($this->phrase['core']['processed_records_x_y'], $firstNodeId, $lastNodeId));

			// return for next batch
			return array('startat' => $lastNodeId);
		}
	}

	/**
	 * Step 2 - Search indices may be missing or incomplete.
	 *	Add an adminCP message notifying admins to run the search reindex tool.
	 *	We do not want to rebuild the search index during upgrades, as
	 *		1) it may not be needed and
	 *		2) it can take a long time, and since the index can be rebuilt while the site is operational (AFAIK), there's
	 *			no reason to translate that time into downtime due to upgrades.
	 */
	public function step_2($data = NULL)
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

class vB_Upgrade_520a2 extends vB_Upgrade_Version
{
	/*
	 * Step1 : VBV-15341 Unset cansearch for legacy "StaticPage" type
	 */
	public function step_1()
	{
		$assertor = vB::getDbAssertor();

		$package = $assertor->getRow('package', array('class' => 'vBCms'));
		if (empty($package['packageid']))
		{
			// this is not an upgrade from a vb4 DB with vBCms package, nothing to do here.
			return $this->skip_message();
		}

		// Mostly copy pasted from 518a6 (VBV-14770)
		$contenttypes = $assertor->getRows(
			'vBForum:contenttype',
			array(
				vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
				vB_dB_Query::CONDITIONS_KEY => array(
					array('field' => 'class',       'value' => array('StaticPage'),	    'operator' =>  vB_dB_Query::OPERATOR_EQ),
					array('field' => 'packageid',   'value' => $package['packageid'],   'operator' =>  vB_dB_Query::OPERATOR_EQ),
					array('field' => 'cansearch',   'value' => 1,                       'operator' =>  vB_dB_Query::OPERATOR_EQ),
				)
			)
		);
		if (empty($contenttypes))
		{
			// Already done. Nothing to do here.
			return $this->skip_message();
		}

		$total = count($contenttypes);
		$i = 0;
		foreach ($contenttypes AS $contenttype)
		{
			$this->show_message(sprintf($this->phrase['vbphrase']['update_table_x'], 'contenttype', ++$i, $total));
			$assertor->update('vBForum:contenttype',
				array(// update values
					'cansearch' => 0,
				),
				array(// update conditions
					'contenttypeid' => $contenttype['contenttypeid']
				)
			);
		}

		// give the cache a kick.
		vB_Types::instance()->reloadTypes();
	}
}

class vB_Upgrade_520a3 extends vB_Upgrade_Version
{
	/**
	 * Add style.styleattributes.
	 * I don't believe this will take too long, as I am not expecting any site to have hundreds of styles.
	 */
	public function step_1()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'style', 1, 1),
			'style',
			'styleattributes',
			'tinyint',
			array('null' => false, 'default' => vB_Library_Style::ATTR_DEFAULT)
		);
	}

	/*
	 * Add the write-protected theme parent, and move the current theme parent under it, and make it editable.
	 */
	public function step_2()
	{
		$this->show_message($this->phrase['version']['520a3']['adding_editable_theme_parent']);

		vB_Upgrade::createAdminSession();
		$xml_importer = new vB_Xml_Import_Theme();
		$themeGrandParent = $xml_importer->getDefaultGrandParentTheme();
		$themeParent = $xml_importer->getDefaultParentTheme();
		$needsUpdates = (
			$themeParent['parentid'] != $themeGrandParent['styleid'] ||
			$themeParent['styleattributes'] != vB_Library_Style::ATTR_DEFAULT
		);
		if ($needsUpdates)
		{
			$this->show_message(sprintf($this->phrase['version']['520a3']['setting_attributes_for_style'], $themeParent['title'], $themeParent['styleid']));
			$assertor = vB::getDbAssertor();
			$assertor->update('vBForum:style',
				array(// update values
					'parentid' => $themeGrandParent['styleid'], // Keep in sync with theme importer's getDefaultParentTheme()
					'styleattributes' => vB_Library_Style::ATTR_DEFAULT,
				),
				array(// update conditions
					'guid' => vB_Xml_Import_Theme::DEFAULT_PARENT_GUID
				)
			);

			$this->doStyleCleanUp($themeParent);
		}
	}

	/*
	 * Create temporary table to hold style record info as we shift things around
	 */
	public function step_3()
	{
		// Only run once.
		if ($this->iRan(__FUNCTION__))
		{
			return;
		}

		// Hope this query doesn't break, because this will only run once.
		if (!$this->tableExists('style_temporary_helper'))
		{
			// Add a helper table to hold some temp information
			$this->run_query(
				sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'style_temporary_helper'),
				"CREATE TABLE " . TABLE_PREFIX . "style_temporary_helper (
					styleid SMALLINT UNSIGNED NOT NULL DEFAULT '0',
					parentid SMALLINT NOT NULL DEFAULT '0',
					guid char(150) NULL DEFAULT NULL UNIQUE,
					children VARCHAR(250) NOT NULL DEFAULT ''
				) ENGINE = " . $this->hightrafficengine . "
				",
				self::MYSQL_ERROR_TABLE_EXISTS
			);
		}
		else
		{
			$this->skip_message();
			return;
		}
	}

	public function step_4()
	{
		// Only run once.
		if ($this->iRan(__FUNCTION__))
		{
			return;
		}

		if (!$this->tableExists('style_temporary_helper'))
		{
			// Not sure if this could ever happen, but if it does, let's print a warning and die.
			$this->echo_phrase(sprintf($this->phrase['version']['520a3']['run_step_x_first'], 3));
			exit(1); // do not log this step, as it needs to run again.
		}

		/*
			Only the themes that have XMLs will be overwritten by upgrade. Let's go through the files
			and grab each GUID
		 */
		vB_Upgrade::createAdminSession();

		$assertor = vB::getDbAssertor();
		$guids = array();
		$themeFiles = $this->grabThemeFiles();
		foreach ($themeFiles AS $filename)
		{
			$xml = vB_Xml_Import::parseFile($filename);
			if (!empty($xml['guid']))
			{
				$guids[$xml['guid']] = $xml['guid'];
			}
		}

		$insertThese = array();
		$styleidsByParent = array();
		$addChildren = array();
		$styles = $assertor->getRows('style'); // grab all styles
		if (!empty($styles))
		{
			foreach ($styles AS $row)
			{
				if (!empty($row['guid']) AND isset($guids[$row['guid']]))
				{
					$insertThese[$row['guid']] = array(
						$row['styleid'],
						$row['parentid'],
						$row['guid'],
					);
					$addChildren[$row['guid']] = $row['styleid'];
				}
				$styleidsByParent[$row['parentid']][$row['styleid']] = $row['styleid'];
			}
		}

		foreach ($addChildren AS $key_guid => $value_styleid)
		{
			$insertThese[$key_guid]['children'] = json_encode($styleidsByParent[$value_styleid]);
		}

		if ($insertThese)
		{
			// store all data
			$try = $assertor->assertQuery(
				'vBInstall:style_temporary_helper',
				array(
					vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_MULTIPLEINSERT,
					vB_dB_Query::FIELDS_KEY => array('styleid', 'parentid', 'guid', 'children'),
					vB_dB_Query::VALUES_KEY => $insertThese
				)
			);
		}

		// break GUIDs
		$assertor->update(
			'style',
			array( // values
				'guid' => vB_dB_Query::VALUE_ISNULL,
			),
			array( // conditions
				'guid' => $guids,
			));

		$this->show_message($this->phrase['version']['520a3']['added_temporary_theme_data']);
		$this->long_next_step();
	}

	public function step_5($data = null)
	{
		// This can actually run more than once since it just imports themes, but it'll be a waste of time to do it again
		// so let's just limit it to one run.
		if ($this->iRan(__FUNCTION__))
		{
			return;
		}

		vB_Library::clearCache();
		return $this->final_load_themes($data);
	}

	public function step_6()
	{
		$this->long_next_step();
	}


	public function step_7($data = null)
	{
		vB_Upgrade::createAdminSession();

		if (!$this->tableExists('style_temporary_helper'))
		{
			$this->skip_message();
			return;
		}

		$assertor = vB::getDbAssertor();

		$chosenOne = $assertor->getRow('vBInstall:style_temporary_helper');
		if (empty($chosenOne))
		{
			$this->run_query(
				sprintf($this->phrase['core']['dropping_old_table_x'], TABLE_PREFIX . "style_temporary_helper"),
				"DROP TABLE IF EXISTS " . TABLE_PREFIX . "style_temporary_helper"
			);

			// clear stylecache & rebuild datastore.stylecache from DB, rebuild template list info etc. Basically
			// everything we do not want to do by hand.
			vB_Library::instance('style')->buildAllStyles(0, 0, true);

			$this->show_message($this->phrase['core']['process_done']);
			return;
		}

		if (!isset($data['startat']))
		{
			$data['startat'] = 0;
		}

		$data['startat']++; // startat has no real significance, it's just a way to allow us to loop this step.

		/*
		Here's the sketchy back-alley magic.
		We broke the guids in the previous step so that new theme parents will be imported.
		Now, we go through the old theme style records, and replace each new editable theme child with the
		old record by stealing its guid & parentid, then nuking the new record.
		This is all so that any old theme(s) that was set as a page/channel/user default will not break,
		and the theme customizations (ATM only css_additional & titleimage) will be maintained.
		*/
		$protectedStyle = $assertor->getRow('style', array('guid' => $chosenOne['guid']));
		$editableChild = $assertor->getRow('style', array('parentid' => $protectedStyle['styleid'], 'styleattributes' => vB_Library_Style::ATTR_DEFAULT));
		if (empty($editableChild))
		{
			// This should never happen, but who knows with wild databases out there. Let's just gracefully skip it for now.
			$this->show_message(sprintf($this->phrase['version']['520a3']['warning_theme_child_not_found'], $protectedStyle['title']));
			return $data;
		}

		// There are a bunch of actions that must be done together. Let's wrap them up in a transaction.
		if ($assertor->inTransaction())
		{
			$assertor->rollbackTransaction();
		}
		$assertor->beginTransaction();

		// drop all dupe templates & stylevars except css_additional & site logo
		$assertor->assertQuery(
			'vBInstall:deleteDupeThemeTemplates',
			array('styleid' => $chosenOne['styleid'])
		);
		$assertor->assertQuery(
			'vBInstall:deleteDupeThemeStylevars',
			array('styleid' => $chosenOne['styleid'])
		);

		// Keep css_additional & titleimage only if they're different from the parent's
		$parentTemplate = $assertor->getRow('template', array('title' => 'css_additional.css', 'styleid' => $protectedStyle['styleid']));
		$childTemplate = $assertor->getRow('template', array('title' => 'css_additional.css', 'styleid' => $chosenOne['styleid']));
		if (!empty($parentTemplate) AND !empty($childTemplate))
		{
			// this one hasn't changed, just inherit from parent and delete current.
			if ($parentTemplate['template_un'] == $childTemplate['template_un'])
			{
				$assertor->assertQuery(
					'template',
					array(
						vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_DELETE,
						'templateid' => $childTemplate['templateid']
					)
				);
			}
		}

		$parentStylevar = $assertor->getRow('stylevar', array('stylevarid' => 'titleimage', 'styleid' => $protectedStyle['styleid']));
		$childStylevar = $assertor->getRow('stylevar', array('stylevarid' => 'titleimage', 'styleid' => $chosenOne['styleid']));
		if (!empty($parentStylevar) AND !empty($childStylevar))
		{
			// this one hasn't changed, just inherit from parent and delete current.
			if ($parentStylevar['value'] == $childStylevar['value'])
			{
				$assertor->assertQuery(
					'template',
					array(
						vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_DELETE,
						'stylevarid' => $childStylevar['stylevarid'],
						'styleid' => $childStylevar['styleid']
					)
				);
			}
		}

		// The ephemeral child style has outlived its usefulness, and we're taking its identity. Let's get rid of the evidence.
		$styleApi = vB_Api::instanceInternal('style');
		$styleApi->deleteStyle($editableChild['styleid'], true); // this doesn't work right ATM

		// Steal the GUID of the deleted child,
		$currentRecord = $assertor->getRow('style', array('styleid' => $chosenOne['styleid']));
		$newValues = array(
			'parentid' => $protectedStyle['styleid'],
			'styleattributes' => vB_Library_Style::ATTR_DEFAULT,
			'guid' => $editableChild['guid'],
			'filedataid' => 0,			// refcount cleanup is down below, look down a few lines.
			'previewfiledataid' => 0,	// ''
		);
		$assertor->update('vBForum:style',
			$newValues, // update values
			array(// update conditions
				'styleid' => $chosenOne['styleid']
			)
		);

		// filedata record cleanup. No reason for an unprotected theme to have its own icons.
		if ($currentRecord['filedataid'] > 0)
		{
			vB::getDbAssertor()->assertQuery('decrementFiledataRefcount', array('filedataid' => $style['filedataid']));
		}
		if ($currentRecord['previewfiledataid'] > 0)
		{
			vB::getDbAssertor()->assertQuery('decrementFiledataRefcount', array('filedataid' => $style['previewfiledataid']));
		}

		$cleanStyle = array(
			'styleid' => $currentRecord['styleid'],
			'title' => $currentRecord['title'],
		);
		$this->doStyleCleanUp($cleanStyle);

		// Remove this from the HELPER table
		$assertor->assertQuery(
			'vBInstall:style_temporary_helper',
			array(
				vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_DELETE,
				'guid' => $chosenOne['guid']
			)
		);

		// Done. Let's commit this chunk and move onto the next one.
		$assertor->commitTransaction();

		$this->show_message(sprintf($this->phrase['version']['520a3']['moving_theme_child'], $protectedStyle['title']));
		return $data;
	}


	public function step_8()
	{

		// Place holder to allow iRan() to work properly, as the last step gets recorded as step '0' in the upgrade log for CLI upgrade.

		$this->skip_message();
		return;

	}

	protected function updateParentList($style)
	{
		$styleLib = vB_Library::instance('style');
		// Force parentlist recreation. Some steps in this upgrade will break parentage.
		$parentlist = $styleLib->fetchTemplateParentlist($style['styleid'], true);
		$try = vB::getDbAssertor()->assertQuery('vBForum:updatestyleparent', array(
			'parentlist' => $parentlist,
			'styleid' => $style['styleid']
		));
	}

	protected function doStyleCleanUp($style)
	{
		vB_Upgrade::createAdminSession();
		// Taken from style API's insertStyle(), basically do all the "cleanup" required after a style change.

		$this->updateParentList($style);
		$styleLib = vB_Library::instance('style');
		$styleLib->buildStyle($style['styleid'], $style['title'], array(
				'docss' => 1,
				'dostylevars' => 1,
				'doreplacements' => 1,
				'doposteditor' => 1
		), false);
		$styleLib->buildStyleDatastore();
	}

	protected function grabThemeFiles()
	{
		$themesdir = DIR . '/install/themes/';
		$themeFiles = array();
		foreach (scandir($themesdir) AS $filename)
		{
			if (!is_dir($themesdir . '/' . $filename)
					AND (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) === 'xml')
			)
			{
				$themeFiles[] = $themesdir . $filename;
			}
		}

		return $themeFiles;
	}

}

class vB_Upgrade_521a1 extends vB_Upgrade_Version
{
	/**
	 * First step of update.
	 * Sets long step
	 */
	public function step_1()
	{
		$this->long_next_step();
	}
	/**
	 * Second step of update.
	 * Alters email index
	 */
	public function step_2()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'user', 1, 2),
			'user',
			'email',
			'email'
		);
		$this->long_next_step();
	}

	/**
	 * Third step of update.
	 * Alters widget table removing the title column
	 */
	public function step_3()
	{
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], 'widget', 2, 2),
			"widget",
			"title"
		);
	}
}


class vB_Upgrade_521a2 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->drop_table('reminder');
	}

	public function step_2()
	{
		$this->drop_table('pm');
	}

	public function step_3()
	{
		$this->drop_table('pmreceipt');
	}

	public function step_4()
	{
		$this->drop_table('pmtext');
	}

	public function step_5()
	{
		$this->drop_table('pmthrottle');
	}

	public function step_6()
	{
		$this->drop_table('nodevote');
		$this->long_next_step();
	}

	public function step_7()
	{
		$this->drop_table('searchcore');
		$this->long_next_step();
	}

	public function step_8()
	{
		$this->drop_table('searchcore_text');
		$this->long_next_step();
	}

	public function step_9()
	{
		$this->drop_table('searchgroup');
		$this->long_next_step();
	}

	public function step_10()
	{
		$this->drop_table('searchgroup_text');
	}

	public function step_11()
	{
		$this->skip_message();
	}

	public function step_12()
	{
		$this->skip_message();
	}

	public function step_13()
	{
		$this->skip_message();
	}

	public function step_14()
	{
		//just in case this still exists for some reason.  Step used to
		//created it after dropping
		$this->drop_table('access_temp');
	}

	public function step_15()
	{
		$this->skip_message();
	}

	public function step_16()
	{
		$this->skip_message();
	}

	public function step_17()
	{
		$this->skip_message();
	}

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
		$this->drop_table('visitormessage');
	}

	public function step_21()
	{
		$this->drop_table('visitormessage_hash');
	}

	public function step_22()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'hook', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "hook MODIFY COLUMN template varchar(100) NOT NULL DEFAULT ''"
		);
	}

	/**
	 * Add useractivation.reset_attempts
	 */
	public function step_23()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'useractivation', 1, 2),
			'useractivation',
			'reset_attempts',
			'int',
			array('null' => false, 'default' => '0')
		);
	}

	/**
	 * Add useractivation.reset_locked_since
	 */
	public function step_24()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'useractivation', 2, 2),
			'useractivation',
			'reset_locked_since',
			'int',
			array('null' => false, 'default' => '0')
		);
	}
}

class vB_Upgrade_521a3 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->add_index(
			sprintf($this->phrase['core']['create_index_x_on_y'], 'dateline', TABLE_PREFIX . 'searchlog'),
			'searchlog',
			'dateline',
			array('dateline')
		);
	}
}

class vB_Upgrade_521a6 extends vB_Upgrade_Version
{
	public function step_1()
	{
		//this isn't 100% required because the system will init the admin session
		//for the first step as a side effect, but we should be specific about it
		//in case this changes.  It's also needed for the unit testing.
		vB_Upgrade::createAdminSession();
		$this->show_message($this->phrase['version']['521a6']['rebuild_usergroup_permissions']);
		vB::getUserContext()->rebuildGroupAccess();
	}

	/*
	 * Allow usermentions bbcode by default. Run once only.
	 */
	public function step_2()
	{
		// Only run once.
		if ($this->iRan(__FUNCTION__))
		{
			return;
		}
		$this->show_message($this->phrase['version']['521a6']['enabling_usermention_bbcode']);

		$assertor = vB::getDbAssertor();
		$row = $assertor->getRow('setting', ['varname' => 'allowedbbcodes']);
		$row['value'] |= vB_Api_Bbcode::ALLOW_BBCODE_USER;
		$assertor->update('setting',
			['value' => $row['value']],
			['varname' => $row['varname']]
		);

	}

	public function step_3()
	{
		// Place holder to allow iRan() to work properly, as the last step gets recorded as step '0' in the upgrade log for CLI upgrade.
		$this->skip_message();
		return;

	}
}

class vB_Upgrade_522a1 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$assertor = vB::getDbAssertor();
		$assertor->delete('routenew', array('guid' => 'vbulletin-4ecbdacd6a6335.32656589'));
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table_x'], 'routenew', 1, 1));
		$this->long_next_step();
	}

	public function step_2()
	{
		$this->drop_table('post');
	}

	public function step_3()
	{
		$this->drop_table('posthash');
	}

	public function step_4()
	{
		$this->drop_table('postlog');
	}

	public function step_5()
	{
		$this->drop_table('postparsed');
		$this->long_next_step();
	}

	public function step_6()
	{
		$this->drop_table('thread');
	}

	public function step_7()
	{
		$this->drop_table('threadrate');
	}

	public function step_8()
	{
		$this->drop_table('threadread');
	}

	public function step_9()
	{
		$this->drop_table('threadredirect');
	}

	public function step_10()
	{
		$this->drop_table('threadviews');
	}

	public function step_11()
	{
		$this->drop_table('postrelease');
	}

	public function step_12()
	{
		$this->drop_table('skimlinks');
	}
}

class vB_Upgrade_522a2 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table_x'], 'channel', 1, 1));

		$forumoptionbits = vB::getDatastore()->getValue('bf_misc_forumoptions');
		$db = vB::getDbAssertor();

		//if its not a category it should allow threads
		$rows = $db->select('vBForum:channel',
			array(
				'category' => 0,
				array('field' => 'options', 'value' => $forumoptionbits['cancontainthreads'] , 'operator' => vB_dB_Query::OPERATOR_NAND)
			),
			array('nodeid', 'options')
		);

		foreach($rows AS $row)
		{
			$db->update('vBForum:channel',
				array('options' => $row['options'] | $forumoptionbits['cancontainthreads']),
				array('nodeid' => $row['nodeid'])
			);
		}
	}
}

class vB_Upgrade_522a4 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->show_message($this->phrase['version']['522a4']['update_sitemap_directory']);

		$datastore = vB::getDatastore();
		$sitemappath =  $datastore->getOption('sitemap_path');

		//if we currently have an index file, assume that path is absolute or otherwise fine.
		//we check specifically for the file because people not using the sitepath will
		//have the default value and the default directory which should now line up
		if (!resolve_server_path($sitemappath . '/vbulletin_sitemap_index.xml.gz'))
		{
			//attempt to reconstruct the path that would resolve the old way
			if (resolve_server_path('admincp/' . $sitemappath))
			{
				 $datastore->setOption('sitemap_path', 'admincp/' . $sitemappath);
			}
		}
	}
}

class vB_Upgrade_522a5 extends vB_Upgrade_Version
{
	/**
	 * VBV-15926 - Unserialize the show_at_breakpoints module config item
	 */
	public function step_1()
	{
		$db = vB::getDbAssertor();
		$updated = false;

		$instances = $db->select('widgetinstance', [], false,  ['widgetinstanceid', 'adminconfig']);

		foreach ($instances AS $instance)
		{
			if (empty($instance['adminconfig']))
			{
				continue;
			}

			$adminconfig = unserialize($instance['adminconfig']);
			if (!$adminconfig)
			{
				continue;
			}

			if (isset($adminconfig['show_at_breakpoints']))
			{
				if (!is_array($adminconfig['show_at_breakpoints']))
				{
					$unserialized = unserialize($adminconfig['show_at_breakpoints']);
					if ($unserialized)
					{
						$adminconfig['show_at_breakpoints'] = $unserialized;

						$condition = array('widgetinstanceid' => $instance['widgetinstanceid']);
						$values = array('adminconfig' => serialize($adminconfig));
						$db->update('widgetinstance', $values, $condition);

						$updated = true;
					}
				}
			}
		}

		if ($updated)
		{
			$this->show_message($this->phrase['version']['522a5']['update_module_setting_show_at_breakpoints']);
		}
		else
		{
			$this->skip_message();
		}
	}
}

class vB_Upgrade_523a1 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->drop_table('blog_attachmentlegacy');
	}

	public function step_2()
	{
		$this->drop_table('blog_category');
	}

	public function step_3()
	{
		$this->drop_table('blog_categorypermission');
	}

	public function step_4()
	{
		$this->drop_table('blog_categoryuser');
	}

	public function step_5()
	{
		$this->drop_table('blog_custom_block');
	}

	public function step_6()
	{
		$this->drop_table('blog_custom_block_parsed');
	}

	public function step_7()
	{
		$this->drop_table('blog_deletionlog');
	}

	public function step_8()
	{
		$this->drop_table('blog_editlog');
	}

	public function step_9()
	{
		$this->drop_table('blog_featured');
	}

	public function step_10()
	{
		$this->drop_table('blog_groupmembership');
	}

	public function step_11()
	{
		$this->drop_table('blog_grouppermission');
	}

	public function step_12()
	{
		$this->drop_table('blog_hash');
	}

	public function step_13()
	{
		$this->drop_table('blog_moderation');
	}

	public function step_14()
	{
		$this->drop_table('blog_moderator');
	}

	public function step_15()
	{
		$this->drop_table('blog_pinghistory');
	}

	public function step_16()
	{
		$this->drop_table('blog_rate');
	}

	public function step_17()
	{
		$this->drop_table('blog_read');
	}

	public function step_18()
	{
		$this->drop_table('blog_relationship');
	}

	public function step_19()
	{
		$this->drop_table('blog_search');
	}

	public function step_20()
	{
		$this->drop_table('blog_searchresult');
	}

	public function step_21()
	{
		$this->drop_table('blog_sitemapconf');
	}

	public function step_22()
	{
		$this->drop_table('blog_subscribeentry');
	}

	public function step_23()
	{
		$this->drop_table('blog_subscribeuser');
	}

	public function step_24()
	{
		$this->drop_table('blog_summarystats');
	}

	public function step_25()
	{
		$this->drop_table('blog_tachyentry');
	}

	public function step_26()
	{
		$this->drop_table('blog_text');
	}

	public function step_27()
	{
		$this->drop_table('blog_textparsed');
	}

	public function step_28()
	{
		$this->drop_table('blog_trackback');
	}

	public function step_29()
	{
		$this->drop_table('blog_trackbacklog');
	}

	public function step_30()
	{
		$this->drop_table('blog_user');
	}

	public function step_31()
	{
		$this->drop_table('blog_usercss');
	}

	public function step_32()
	{
		$this->drop_table('blog_usercsscache');
	}

	public function step_33()
	{
		$this->drop_table('blog_userstats');
	}

	public function step_34()
	{
		$this->drop_table('blog_views');
	}

	public function step_35()
	{
		$this->drop_table('blog_visitor');
	}

	public function step_36()
	{
		$this->drop_table('blog');
	}

	/*
		Step 1	-	Remove the "nodestats" (node_dailycleanup.php) cron
	*/
	public function step_37()
	{
		$assertor = vB::getDbAssertor();
		$this->show_message($this->phrase['version']['523a1']['remove_nodestats_cron']);
		$assertor->delete(
			'cron',
			array(
				array('field'=>'varname', 'value' => 'nodestats', vB_dB_Query::OPERATOR_KEY => vB_dB_Query::OPERATOR_EQ)
			)
		);
	}

	/*
		Step 38	-	Remove temporary table previously used by calculateStats queries (downstream of nodestats)
	*/
	public function step_38()
	{
		$this->drop_table('tmp_nodestats');
	}


	/*
		Steps 39 - 41 - ... Remove tables related to the removed nodestats cron
	*/
	public function step_39()
	{
		$this->drop_table('nodestats');
	}

	public function step_40()
	{
		$this->drop_table('nodevisits');
	}

	public function step_41()
	{
		$this->drop_table('nodestatreplies');
	}

	/*
		Clear user.status for banned users.
	 */
	public function step_42($data = null)
	{
		$assertor = vB::getDbAssertor();
		$processlist = $assertor->getRows('vBInstall:findBannedUserWithStatuses', array('timenow' => vB::getRequest()->getTimeNow()));

		if (empty($processlist))
		{
			if (empty($data))
			{
				$this->skip_message();
			}
			else
			{
				$this->show_message(sprintf($this->phrase['core']['process_done']));
			}
			$this->long_next_step();
			return;
		}

		if (!isset($data['startat']))
		{
			$data['startat'] = 0;
			$this->show_message($this->phrase['version']['523a1']['clear_banned_users_statuses']);
		}

		$userids = array();
		foreach ($processlist AS $row)
		{
			$userid = $row['userid'];
			$userids[$userid] = $userid;
		}

		$this->show_message(sprintf($this->phrase['core']['processing_records_x'], count($userids)));

		$assertor->update('user', array('status' => ''), array('userid' => $userids));


		return array('startat' => ++$data['startat']);
	}

	/*
		Steps 43 change user.status to varchar(1000)
	 */
	public function step_43($data = null)
	{
		if ($this->userStatusColumnIsMediumtext())
		{
			$assertor = vB::getDbAssertor();
			$assertor->assertQuery('vBInstall:alterUserStatusToVarchar');
			$this->show_message($this->phrase['version']['523a1']['modify_user_status']);
			return;
		}

		$this->skip_message();
	}

	private function userStatusColumnIsMediumtext()
	{
		$assertor = vB::getDbAssertor();
		$check = $assertor->getRows('vBInstall:showUserColumnStatus');
		if (!empty($check) AND is_array($check))
		{
			$check = reset($check);
			if (isset($check['Type']) AND $check['Type'] == 'mediumtext')
			{
				return true;
			}
		}
		return false;
	}
}

class vB_Upgrade_523a3 extends vB_Upgrade_Version
{
	public function step_1($data = null)
	{
		//would like to do it as a single update query but can't do a
		//limit with a multi-table update and can't use a subquery in an
		//update involving the table you are updating.  So we do it the
		//hard/slow way
		$db = vB::getDbAssertor();
		$batchsize = $this->getBatchSize('xxxsmall', __FUNCTION__);
		$result = $db->assertQuery('vBInstall:selectShowOpenMismatch', ['limit' => $batchsize]);
		$nodes = array();
		foreach($result AS $row)
		{
			$nodes[] = $row['nodeid'];
		}

		if (empty($data['startat']))
		{
			$data['startat'] = 0;
			$this->show_message($this->phrase['version']['523a3']['fix_showopen']);
		}

		$count = count($nodes);
		if ($count)
		{
			$db->update('vBForum:node', ['showopen' => 0], ['nodeid' => $nodes]);

			$this->show_message(sprintf($this->phrase['core']['processing_records_x'], $count));
			// This is just to indicate iteration, the actual value of startat is not used by this method and is not important.
			return ['startat' => 1];
		}
		else
		{
			$this->show_message($this->phrase['core']['process_done']);
			return;
		}
	}

	public function step_2()
	{
		$db = vB::getDbAssertor();
		$row = $db->getRow('vBInstall:checkIndexLimitAdcriteria');
		if (!$row)
		{
			$this->show_message(sprintf($this->phrase['core']['altering_x_table'], 'adcriteria', 1, 1));
			$db->assertQuery('vBInstall:alterIndexLimitAdcriteria');
		}
		else
		{
			$this->show_message(sprintf($this->phrase['version']['523a3']['data_too_long'], 'adcriteria', 191));
		}
	}

	public function step_3()
	{
		$db = vB::getDbAssertor();
		$row = $db->getRow('vBInstall:checkIndexLimitBbcode');
		if (!$row)
		{
			$this->show_message(sprintf($this->phrase['core']['altering_x_table'], 'bbcode', 1, 1));
			$db->assertQuery('vBInstall:alterIndexLimitBbcode');
		}
		else
		{
			$this->show_message(sprintf($this->phrase['version']['523a3']['data_too_long'], 'bbcode', 191));
		}
	}

	public function step_4()
	{
		$db = vB::getDbAssertor();
		$row = $db->getRow('vBInstall:checkIndexLimitFaq');
		if (!$row)
		{
			$this->show_message(sprintf($this->phrase['core']['altering_x_table'], 'faq', 1, 1));
			$db->assertQuery('vBInstall:alterIndexLimitFaq');
		}
		else
		{
			$this->show_message(sprintf($this->phrase['version']['523a3']['data_too_long'], 'faq', 191));
		}
	}

	public function step_5()
	{
		$db = vB::getDbAssertor();
		$row = $db->getRow('vBInstall:checkIndexLimitNoticecriteria');
		if (!$row)
		{
			$this->show_message(sprintf($this->phrase['core']['altering_x_table'], 'noticecriteria', 1, 1));
			$db->assertQuery('vBInstall:alterIndexLimitNoticecriteria');
		}
		else
		{
			$this->show_message(sprintf($this->phrase['version']['523a3']['data_too_long'], 'noticecriteria', 191));
		}
	}

	public function step_6()
	{
		$db = vB::getDbAssertor();
		$row = $db->getRow('vBInstall:checkIndexLimitNotificationevent');
		if (!$row)
		{
			$this->show_message(sprintf($this->phrase['core']['altering_x_table'], 'notificationevent', 1, 1));
			$db->assertQuery('vBInstall:alterIndexLimitNotificationevent');
		}
		else
		{
			$this->show_message(sprintf($this->phrase['version']['523a3']['data_too_long'], 'notificationevent', 191));
		}
	}

	public function step_7()
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

	public function step_8()
	{
		$db = vB::getDbAssertor();
		$row = $db->getRow('vBInstall:checkIndexLimitStylevar');
		if (!$row)
		{
			$this->show_message(sprintf($this->phrase['core']['altering_x_table'], 'stylevar', 1, 1));
			$db->assertQuery('vBInstall:alterIndexLimitStylevar');
		}
		else
		{
			$this->show_message(sprintf($this->phrase['version']['523a3']['data_too_long'], 'stylevar', 191));
		}
	}

	public function step_9()
	{
		$db = vB::getDbAssertor();
		$row = $db->getRow('vBInstall:checkIndexLimitUserstylevar');
		if (!$row)
		{
			$this->show_message(sprintf($this->phrase['core']['altering_x_table'], 'userstylevar', 1, 1));
			$db->assertQuery('vBInstall:alterIndexLimitUserstylevar');
		}
		else
		{
			$this->show_message(sprintf($this->phrase['version']['523a3']['data_too_long'], 'userstylevar', 191));
		}
	}

	public function step_10()
	{
		$db = vB::getDbAssertor();
		$row = $db->getRow('vBInstall:checkIndexLimitStylevardfn');
		if (!$row)
		{
			$this->show_message(sprintf($this->phrase['core']['altering_x_table'], 'stylevardfn', 1, 1));
			$db->assertQuery('vBInstall:alterIndexLimitStylevardfn');
		}
		else
		{
			$this->show_message(sprintf($this->phrase['version']['523a3']['data_too_long'], 'stylevardfn', 191));
		}
	}

	public function step_11()
	{
		$this->drop_table('profileblockprivacy');
	}
}

class vB_Upgrade_523a4 extends vB_Upgrade_Version
{
	/*
	 * Enable canusepmchat defaults. Run once only.
	 */
	public function step_1()
	{
		// Only run once.
		if ($this->iRan(__FUNCTION__))
		{
			return;
		}
		$this->skip_message();
		return;
		/*
			This step used to set canusepmchat based on the bitfield_vbulletin XML file for the "install" usergroups.
			Per VBV-16295, current pmquota & canignorepmquota permissions should be used instead of the "default" groups
			to determine which usergroups get canusepmchat enabled on upgrade. This is done as part of the 523rc2 upgrade class.
		 */
	}

	public function step_2()
	{
		// Place holder to allow iRan() to work properly, as the last step gets recorded as step '0' in the upgrade log for CLI upgrade.
		$this->skip_message();
		return;
	}
}

class vB_Upgrade_523a5 extends vB_Upgrade_Version
{
	/**
	 * Change moderator id field from small int to int
	 */
	public function step_1()
	{
		if ($this->field_exists('moderator', 'moderatorid'))
		{
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'moderator', 1, 1),
				"ALTER TABLE " . TABLE_PREFIX . "moderator CHANGE moderatorid moderatorid INT(10) UNSIGNED NOT NULL AUTO_INCREMENT"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	 * Change ip address field to varchar 45 for IPv6
	 */
	public function step_2()
	{
		if ($this->field_exists('strikes', 'strikeip'))
		{
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'strikes', 1, 1),
				"ALTER TABLE " . TABLE_PREFIX . "strikes CHANGE strikeip strikeip VARCHAR(45) NOT NULL DEFAULT ''"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	 * Change ip address field to varchar 45 for IPv6
	 */
	public function step_3()
	{
		if ($this->field_exists('adminlog', 'ipaddress'))
		{
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'adminlog', 1, 1),
				"ALTER TABLE " . TABLE_PREFIX . "adminlog CHANGE ipaddress ipaddress VARCHAR(45) NOT NULL DEFAULT ''"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	 * Change ip address field to varchar 45 for IPv6
	 */
	public function step_4()
	{
		if ($this->field_exists('moderatorlog', 'ipaddress'))
		{
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'moderatorlog', 1, 1),
				"ALTER TABLE " . TABLE_PREFIX . "moderatorlog CHANGE ipaddress ipaddress VARCHAR(45) NOT NULL DEFAULT ''"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	 * Change ip address field to varchar 45 for IPv6
	 */
	public function step_5()
	{
		if ($this->field_exists('user', 'ipaddress'))
		{
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'user', 1, 1),
				"ALTER TABLE " . TABLE_PREFIX . "user CHANGE ipaddress ipaddress VARCHAR(45) NOT NULL DEFAULT ''"
			);
		}
		else
		{
			$this->skip_message();
		}
	}
}

class vB_Upgrade_523b2 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$mapper = new vB_Stylevar_Mapper();

		// Add mappings
		$mapper->addMapping('pmchat_chatwindow_content_background', 'vbmessenger_chatwindow_content_background', 'vbulletin', true);
		$mapper->addMapping('pmchat_chatwindow_message_mine_background', 'vbmessenger_chatwindow_message_mine_background', 'vbulletin', true);
		$mapper->addMapping('pmchat_chatwindow_message_theirs_background', 'vbmessenger_chatwindow_message_theirs_background', 'vbulletin', true);
		$mapper->addMapping('pmchat_chatwindow_participants_background', 'vbmessenger_chatwindow_participants_background', 'vbulletin', true);
		$mapper->addMapping('pmchat_chatwindow_widget_background', 'vbmessenger_chatwindow_widget_background', 'vbulletin', true);

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
}

class vB_Upgrade_523rc2 extends vB_Upgrade_Version
{
	/*
	 * Enable canusepmchat only for groups with pmquota > 0
	 */
	public function step_1()
	{
		/*
			This step effectively overrides 523a4.
		 */


		// Only run once.
		if ($this->iRan(__FUNCTION__))
		{
			return;
		}
		$this->show_message($this->phrase['version']['523rc2']['setting_ugp_canusepmchat']);



		/*
			Set up readable bitfield
		 */
		$parsedRaw = vB_Xml_Import::parseFile(DIR . '/includes/xml/bitfield_vbulletin.xml');
		$pmpermissions = array();
		foreach ($parsedRaw['bitfielddefs']['group'] AS $group)
		{
			if ($group['name'] == 'ugp')
			{
				foreach($group['group'] AS $bfgroup)
				{
					if (($bfgroup['name'] == 'pmpermissions'))
					{
						foreach ($bfgroup['bitfield'] AS $bitfield)
						{
							$pmpermissions[$bitfield['name']] = $bitfield['value'];
						}
					}
				}
			}
		}


		/*
			Grab all usergroups & pick out which ones should get canusepmchat set
		 */
		$assertor = vB::getDbAssertor();
		$allgroupids = array();
		$usergroupsToEnablePmchat = array();
		$usergroups = $assertor->assertQuery(
			'vBForum:usergroup',
			array(
				vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
				vB_dB_Query::COLUMNS_KEY => array('usergroupid', 'pmquota', 'pmpermissions'),
			)
		);
		if ($usergroups AND $usergroups->valid())
		{
			foreach ($usergroups AS $usergroup)
			{
				$allgroupids[] = $usergroup['usergroupid'];
				if ($usergroup['pmquota'] > 0)
				{
					$usergroupsToEnablePmchat[] = $usergroup['usergroupid'];
				}
			}
		}

		// first, just turn it off for everyone.
		vB::getDbAssertor()->assertQuery('vBInstall:unsetUsergroupPmpermissionsBit', array('bit' => $pmpermissions['canusepmchat'], 'groupids' => $allgroupids));

		// Now turn it on for only those who need it.
		if (!empty($usergroupsToEnablePmchat))
		{
			vB::getDbAssertor()->assertQuery('vBInstall:setUsergroupPmpermissionsBit', array('bit' => $pmpermissions['canusepmchat'], 'groupids' => $usergroupsToEnablePmchat));
		}

		// rebuild usergroup cache, reset global $vbulletin's array, & usergroup API instance's array.
		vB_Upgrade::createAdminSession();
		vB_Library::instance('usergroup')->buildDatastore();
		vB_Api::instanceInternal('usergroup')->fetchUsergroupList(true);
	}

	public function step_2()
	{
		// Place holder to allow iRan() to work properly in step_1(), as the last step gets recorded as step '0' in the upgrade log for CLI upgrade.
		$this->skip_message();
		return;

	}
}

class vB_Upgrade_524a1 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->drop_table('forum');
	}

	public function step_2()
	{
		$this->drop_table('forumread');
	}

	public function step_3()
	{
		$this->drop_table('groupmessage');
	}

	public function step_4()
	{
		$this->drop_table('groupmessage_hash');
	}

	public function step_5()
	{
		$this->drop_table('groupread');
	}

	public function step_6()
	{
		$this->drop_table('navigation');
	}

	public function step_7()
	{
		$this->drop_table('plugin');
	}

	public function step_8()
	{
		$this->drop_table('route');
	}

	public function step_9()
	{
		$this->drop_table('socialgroup');
	}

	public function step_10()
	{
		$this->drop_table('socialgroupcategory');
	}

	public function step_11()
	{
		$this->drop_table('socialgroupicon');
	}

	public function step_12()
	{
		$this->drop_table('socialgroupmember');
	}

	public function step_13()
	{
		$this->drop_table('subscribeforum');
	}

	public function step_14()
	{
		$this->drop_table('subscribethread');
	}
}

class vB_Upgrade_524a2 extends vB_Upgrade_Version
{
	public function step_1($data = null)
	{
		$batchSize = 1000;

		if (empty($data['startat']))
		{
			$data['startat'] = 0;
			$this->show_message($this->phrase['version']['524a2']['fix_userpmtotals']);
		}

		$assertor = vB::getDbAssertor();

		$users = $assertor->assertQuery('user',
			array(
				vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
				vB_dB_Query::CONDITIONS_KEY => array(
					array('field' => 'userid', 'value' => $data['startat'], 'operator' =>  vB_dB_Query::OPERATOR_GTE),
				),
				vB_dB_Query::PARAM_LIMIT => $batchSize,
				vB_dB_Query::COLUMNS_KEY => array('userid')
			),
			'userid'
		);

		$userids = array();
		foreach($users AS $user)
		{
			$userids[] = $user['userid'];
		}

		if (count($userids))
		{
			vB_Library::instance('content_privatemessage')->buildPmTotals($userids);

			$this->show_message(sprintf($this->phrase['core']['processing_records_x'], count($userids)));
			return array('startat' => $userids[count($userids)-1]+1);
		}
		else
		{
			$this->show_message($this->phrase['core']['process_done']);
			return;
		}
	}
}

class vB_Upgrade_524a4 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->drop_table('subscribegroup');
	}

	// add missing css units
	public function step_2()
	{
		if ($this->field_exists('stylevardfn', 'units'))
		{
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'stylevardfn', 1, 1),
				"
					ALTER TABLE " . TABLE_PREFIX . "stylevardfn
					MODIFY COLUMN units
						ENUM('','%','px','pt','em','rem','ch','ex','pc','in','cm','mm','vw','vh','vmin','vmax')
						NOT NULL DEFAULT ''
				"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/*
	 * Prep for step_4: Need to import the settings XML in case this install doesn't have the new option yet.
	 */
	public function step_3()
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
	 * Copy current floodchecktime value into pm_floodchecktime. Run once only.
	 */
	public function step_4()
	{
		// Only run once.
		if ($this->iRan(__FUNCTION__))
		{
			return;
		}

		vB_Upgrade::createAdminSession();
		$this->show_message($this->phrase['version']['524a4']['copying_floodchecktime']);
		$options = vB::getDatastore()->getValue('options');
		if (isset($options['floodchecktime']) AND isset($options['pm_floodchecktime']))
		{
			$this->set_option('pm_floodchecktime', 'pm', intval($options['floodchecktime']));
		}
	}

	public function step_5()
	{
		// Place holder to allow iRan() to work properly, as the last step gets recorded as step '0' in the upgrade log for CLI upgrade.
		$this->skip_message();
		return;
	}
}

class vB_Upgrade_524b1 extends vB_Upgrade_Version
{
	//Need to set smilie location, see VBSAAS-1303
	public function step_1()
	{
		$this->show_message($this->phrase['version']['524b1']['setting_smilie_location']);
		$current = vB::getDatastore()->getOption('smiliepath');

		if (empty($current))
		{
			vB::getDatastore()->setOption('smiliepath', 'images/smilies/', true);
		}
	}
}

class vB_Upgrade_525a1 extends vB_Upgrade_Version
{
	/*
	 * Enable user.options[134217728] by default. Run once only.
	 */
	public function step_1($data = null)
	{
		// Only run once.
		if (empty($data['startat']) AND $this->iRan(__FUNCTION__))
		{
			return;
		}
		elseif (empty($data['startat']))
		{
			$this->show_message($this->phrase['version']['525a1']['setting_user_vbmessenger']);
			$data['startat'] = 0;
		}

		$startat = $data['startat'];
		$batchsize = 5000;

		$max = vB::getDbAssertor()->getRow('vBInstall:getMaxUserid');
		$maxid = $max['maxid'];
		if ($maxid <= $startat)
		{
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			return;
		}


		$count = vB::getDbAssertor()->assertQuery('vBInstall:setUserPmchatOption', array('startat' => $startat, 'batchsize' => $batchsize));
		$this->show_message(sprintf($this->phrase['core']['processed_x_records_starting_at_y'], $count, $startat + 1));

		$data['startat'] = $startat + $batchsize;
		return $data;
	}

	public function step_2()
	{
		// Place holder to allow iRan() to work properly, as the last step gets recorded as step '0' in the upgrade log for CLI upgrade.
		$this->skip_message();
		return;
	}
}

class vB_Upgrade_525a2 extends vB_Upgrade_Version
{
	/*
	 * Steps 1 ~ 3
	 * Change 'pmchat' product from route, page, & pagetemplate records. This was left over from
	 * when it used to be in its own product during dev, but it's been part of vbulletin core before release.
	 * We only update the route, page & pagetemplate records, not widget, widgetdef, or phrase records.
	 * `widget` has product = vbulletin for the pmchat_widget_chat, because the XML file was imported with vB_Xml_Import_Widget::productid = 'vbulletin'
	 * (see saveWidget() function)
	 * widget importer updates widgetdefinition in final upgrade.
	 * Similarly, the page & pagetemplate imports all take care of replacing the phrase records for us in final upgrade.
	 */
	public function step_1()
	{
		// hard coded in vbulletin-routes.xml
		$guid = 'vbulletin-pmchat-route-chat-573cbacdc65943.65236568';
		$assertor = vB::getDbAssertor();
		$row = $assertor->getRow('routenew', array('guid' => $guid));
		if (!empty($row['product']) AND $row['product'] == 'pmchat')
		{
			$this->show_message(sprintf($this->phrase['version']['525a2']['fixing_product_for_pmchat_table_x'], 'routenew'));
			$assertor->assertQuery('routenew',
				array(
					vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
					vB_dB_Query::CONDITIONS_KEY => array('guid' => $guid),
					'product' => 'vbulletin'
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

	public function step_2()
	{
		// hard coded in vbulletin-pages.xml
		$guid = 'vbulletin-pmchat-page-chat-573cba8f1d2283.90944371';
		$assertor = vB::getDbAssertor();
		$row = $assertor->getRow('page', array('guid' => $guid));
		if (!empty($row['product']) AND $row['product'] == 'pmchat')
		{
			$this->show_message(sprintf($this->phrase['version']['525a2']['fixing_product_for_pmchat_table_x'], 'page'));
			$assertor->assertQuery('page',
				array(
					vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
					vB_dB_Query::CONDITIONS_KEY => array('guid' => $guid),
					'product' => 'vbulletin'
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

	public function step_3()
	{
		// hard coded in vbulletin-pagetemplates.xml
		$guid = 'vbulletin-pmchat-pagetemplate-chat-573ca81b74e5b0.79208063';
		$assertor = vB::getDbAssertor();
		$row = $assertor->getRow('pagetemplate', array('guid' => $guid));
		if (!empty($row['product']) AND $row['product'] == 'pmchat')
		{
			$this->show_message(sprintf($this->phrase['version']['525a2']['fixing_product_for_pmchat_table_x'], 'pagetemplate'));
			$assertor->assertQuery('pagetemplate',
				array(
					vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
					vB_dB_Query::CONDITIONS_KEY => array('guid' => $guid),
					'product' => 'vbulletin'
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

	/*
	 *	If widgetinstance record is missing for pmchat, re-load the pagetemplate.
	 */
	public function step_4()
	{
		$assertor = vB::getDbAssertor();
		$pagetemplate = $assertor->getRow('pagetemplate', array('guid' => 'vbulletin-pmchat-pagetemplate-chat-573ca81b74e5b0.79208063'));
		$widget = $assertor->getRow('widget', array('guid' => 'vbulletin-pmchat-widget-chat-573cb2b3a78a93.12390691'));
		$widgetinstance = $assertor->getRow('widgetinstance', array('pagetemplateid' => $pagetemplate['pagetemplateid']));
		// if pagetemplate is empty, we might be a fresh upgrade and not actually have any pmchat related stuff...
		if (!empty($pagetemplate) AND
			(
				empty($widget) OR
				empty($widgetinstance) OR 	// we've only seen this case, and only in upgradeTests.
				($widgetinstance['widgetid'] != $widget['widgetid'])
			)
		)
		{
			$this->show_message(sprintf($this->phrase['vbphrase']['importing_file'], 'vbulletin-pagetemplates.xml'));


			$pageTemplateFile = DIR . '/install/vbulletin-pagetemplates.xml';
			if (!($xml = file_read($pageTemplateFile)))
			{
				$this->add_error(sprintf($this->phrase['vbphrase']['file_not_found'], 'vbulletin-pagetemplates.xml'), self::PHP_TRIGGER_ERROR, true);
				return;
			}


			// TODO: there might be some upgrades in which we do want to add some widgetinstances
			$options = (vB_Xml_Import::OPTION_OVERWRITE | vB_Xml_Import::OPTION_ADDWIDGETS );
			$xml_importer = new vB_Xml_Import_PageTemplate('vbulletin', $options);
			$onlyThisGuid = 'vbulletin-pmchat-pagetemplate-chat-573ca81b74e5b0.79208063';
			$xml_importer->importFromFile($pageTemplateFile, $onlyThisGuid);
			$this->show_message($this->phrase['core']['import_done']);


		}
		else
		{
			// we're okay.
			$this->skip_message();
			return;
		}
	}

	public function step_5()
	{
		$this->skip_message();
	}
}

class vB_Upgrade_525a3 extends vB_Upgrade_Version
{
	/** Update default for user.options */
	public function step_1()
	{
		if ($this->field_exists('user', 'options'))
		{
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'user', 1, 1),
				"ALTER TABLE " . TABLE_PREFIX . "user CHANGE COLUMN `options` `options` INT UNSIGNED NOT NULL DEFAULT '167788559'
				"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	 * Add screenlayout.sectiondata
	 */
	public function step_2()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'screenlayout', 1, 1),
			'screenlayout',
			'sectiondata',
			'text',
			array('null' => false, 'default' => '')
		);
	}

	/**
	 * Add pagetemplate.screenlayoutsectiondata
	 */
	public function step_3()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'pagetemplate', 1, 1),
			'pagetemplate',
			'screenlayoutsectiondata',
			'text',
			array('null' => false, 'default' => '')
		);
	}

	/**
	 * Import default pagetemplate.screenlayoutsectiondata values if needed
	 */
	public function step_4()
	{
		vB_Upgrade::createSession();
		$assertor = vB::getDbAssertor();

		$addSectionData = false;
		$guids = array(
			// all the default page templates that use the narrow-wide screenlayout
			'vbulletin-4ecbdac9372590.52063766', // User Profile Template
			'vbulletin-4ecbdac93742a5.43676026', // Private Messages Template (Message Center)
			'vbulletin-4ecbdac93742a5.43676027', // Subscription Template (User Profile => Subscriptions)
			'vbulletin-4ecbdac93742a5.43676029', // Visitor Message Display Template (User Profile => Click "See More" on a Visitor Message)
		);
		$pagetemplates = $assertor->assertQuery('pagetemplate', array(
			'guid' => $guids,
		));
		foreach ($pagetemplates AS $pagetemplate)
		{
			if (empty($pagetemplate['screenlayoutsectiondata']))
			{
				$addSectionData = true;
				break;
			}
		}

		if ($addSectionData)
		{
			$this->show_message(sprintf($this->phrase['vbphrase']['importing_file'], 'vbulletin-pagetemplates.xml'));

			$assertor->assertQuery('vBInstall:deleteOrphanedWidgetDefintions');

			$pageTemplateFile = DIR . '/install/vbulletin-pagetemplates.xml';
			if (!($xml = file_read($pageTemplateFile)))
			{
				$this->add_error(sprintf($this->phrase['vbphrase']['file_not_found'], 'vbulletin-pagetemplates.xml'), self::PHP_TRIGGER_ERROR, true);
				return;
			}

			$options = vB_Xml_Import::OPTION_OVERWRITECOLUMN;
			$xml_importer = new vB_Xml_Import_PageTemplate('vbulletin', $options);
			$xml_importer->setOverwriteColumn('screenlayoutsectiondata');
			$xml_importer->importFromFile($pageTemplateFile, $guids);
			$this->show_message($this->phrase['core']['import_done']);
		}
		else
		{
			$this->skip_message();
		}
	}

}

class vB_Upgrade_526a1 extends vB_Upgrade_Version
{
	/**
	 * Import default pagetemplate.screenlayoutsectiondata values if needed
	 */
	public function step_1()
	{
		vB_Upgrade::createSession();
		$assertor = vB::getDbAssertor();

		$addSectionData = false;
		$guids = array(
			// Some, but not all, of the default page templates that use the wide-narrow
			// screenlayout, specifically, the blog and group pages.
			'vbulletin-4ecbdac93742a5.43676030', // Individual Blog Page Template
			'vbulletin-sgroups93742a5.43676038', // Individual Group Page Template
		);
		$pagetemplates = $assertor->assertQuery('pagetemplate', array(
			'guid' => $guids,
		));
		foreach ($pagetemplates AS $pagetemplate)
		{
			if (empty($pagetemplate['screenlayoutsectiondata']))
			{
				$addSectionData = true;
				break;
			}
		}

		if ($addSectionData)
		{
			$this->show_message(sprintf($this->phrase['vbphrase']['importing_file'], 'vbulletin-pagetemplates.xml'));

			$pageTemplateFile = DIR . '/install/vbulletin-pagetemplates.xml';
			if (!($xml = file_read($pageTemplateFile)))
			{
				$this->add_error(sprintf($this->phrase['vbphrase']['file_not_found'], 'vbulletin-pagetemplates.xml'), self::PHP_TRIGGER_ERROR, true);
				return;
			}

			$options = vB_Xml_Import::OPTION_OVERWRITECOLUMN;
			$xml_importer = new vB_Xml_Import_PageTemplate('vbulletin', $options);
			$xml_importer->setOverwriteColumn('screenlayoutsectiondata');
			$xml_importer->importFromFile($pageTemplateFile, $guids);
			$this->show_message($this->phrase['core']['import_done']);
		}
		else
		{
			$this->skip_message();
		}
	}
}

class vB_Upgrade_526a2 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$db = vB::getDbAssertor();
		$row = $db->getRow('vBForum:attachmenttype', array('extension' => 'docx'));

		if (!$row)
		{
			$query = getAttachmenttypeInsertQuery($this->db, array('docx'));
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

class vB_Upgrade_526a3 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->skip_message();
	}

	public function step_2($data = NULL)
	{
		$db = vB::getDbAssertor();
		$batchsize = 1000;

		$startat = (empty($data['startat']) ? 0 : $data['startat']);

		$this->show_message(sprintf($this->phrase['version']['526a3']['deleting_orphaned_tag_associations_x'], $startat), true);

		$nodeids = $db->getColumn('vBInstall:getOrphanedTagAssociations', 'nodeid',
			array('startatnodeid' => 'startat', 'batchsize' => $batchsize)
		);

		//putting distinct in the query causes mysql to do a sort.  Which interacts
		//badly with doing the limit on what could be a fairly expensive query to
		//run to completion.
		$nodeids = array_unique($nodeids);

		if (!$nodeids)
		{
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			return;
		}

		$db->delete('vBForum:tagnode', array('nodeid' => $nodeids));

		return array('startat' => end($nodeids));
	}
}

class vB_Upgrade_526a4 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->drop_table('usercss');
	}

	public function step_2()
	{
		$this->drop_table('usercsscache');
	}
}

class vB_Upgrade_526a5 extends vB_Upgrade_Version
{
	public function step_1()
	{
		//this might be a good idea, but it doesn't match the install and the table
		//isn't currently used.
		$this->drop_index(
			sprintf($this->phrase['core']['altering_x_table'], 'sentto', 1, 1),
			'sentto',
			'userid'
		);
	}

	public function step_2()
	{
		$this->add_index(
			sprintf($this->phrase['core']['create_index_x_on_y'], 'user_read_deleted', TABLE_PREFIX . 'sentto'),
			'sentto',
			'user_read_deleted',
			array('userid', 'msgread', 'deleted')
		);
	}

	//the accessmask phrase group has been removed from the language file.  The
	//language import will add new groups, but won't remove any that have been removed
	//so we need to manually remove the field from the language table.
	public function step_3()
	{
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], 'language', 1, 1),
			"language",
			"phrasegroup_accessmask"
		);
	}

	//and the record from the phrasetype table.  The language import will handle everything else.
	public function step_4()
	{
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table_x'], 'phrasetype', 1, 1));
		$db = vB::getDbAssertor();
		$db->delete('phrasetype', array('fieldname' => 'accessmask'));
	}


	/*
	 * Event contenttype related upgrade steps.
	 */
	// Check for vb4 event table, rename it to legacyevent.
	public function step_5()
	{
		if (!$this->tableExists('legacyevent') AND $this->tableExists('event') AND $this->field_exists('event', 'calendarid'))
		{
			$assertor = vB::getDbAssertor();
			$assertor->assertQuery('vBInstall:renameLegacyEventTable');
			$this->show_message($this->phrase['version']['526a5']['renaming_legacyevent']);
		}
		else
		{
			$this->skip_message();
		}
	}

	// Add event table.
	public function step_6()
	{
		if (!$this->tableExists('event'))
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'event'),
				"
					CREATE TABLE " . TABLE_PREFIX . "event (
						nodeid          INT UNSIGNED NOT NULL PRIMARY KEY,
						eventstartdate  INT UNSIGNED NOT NULL DEFAULT '0',
						eventenddate    INT UNSIGNED NOT NULL DEFAULT '0',
						location        VARCHAR (191) NOT NULL DEFAULT '',
						KEY eventstartdate (eventstartdate)
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

	// `contenttype` record (contenttypeid). Move vb4 event type to 'legacyevent' and add 'event'
	public function step_7()
	{
		$assertor = vB::getDbAssertor();
		$this->show_message($this->phrase['version']['526a5']['checking_legacyevent_conflicts']);

		$package = $assertor->getRow('package', array('productid' => "vbulletin", 'class' => 'vBForum'));
		if (empty($package['packageid']))
		{
			// something went wrong.. skip.
			$this->skip_message();
			return;
		}
		$packageid = $package['packageid'];

		$eventType = $assertor->getRow('vBForum:contenttype', array('packageid' => $packageid, 'class' => 'Event'));
		$legacyEventType = $assertor->getRow('vBForum:contenttype', array('packageid' => $packageid, 'class' => 'LegacyEvent'));
		$calendarType = $assertor->getRow('vBForum:contenttype', array('packageid' => $packageid, 'class' => 'Calendar'));

		$doInsert = false;
		if (empty($eventType))
		{
			$doInsert = true;
		}
		else
		{
			// We have an event type. If Calendar exists, but LegacyEvent does not, this is an upgrade that needs the
			// old event type renamed & the new event type added.
			// If Calendar and LegacyEvent both exist, the existing Event type is the new one, so we're good.
			if (!empty($calendarType) AND empty($legacyEventType))
			{
				// This is vB4's event. Let's keep it but rename it as legacyevent, similar to the data table.
				$assertor->update(
					'vBForum:contenttype',
					array('class' => 'LegacyEvent'),	// values
					array('packageid' => $packageid, 'class' => 'Event')	// conditions
				);
				$this->show_message($this->phrase['version']['526a5']['renaming_contenttype_legacyevent']);
				$doInsert = true;
			}
			else
			{
				// event type exists, and it's not the legacy one. We're golden.
				return $this->show_message(sprintf($this->phrase['core']['process_done']));
			}
		}


		if ($doInsert)
		{
			$this->show_message($this->phrase['version']['526a5']['inserting_contenttype_event']);
			// just insert a new one.
			$data = array(
				'class' => 'Event',
				'packageid' => $packageid,
				'canplace' => 1,
				'cansearch' => 1,
				'cantag' => 1,
				'canattach' => 1,
				'isaggregator' => 0,
			);
			$assertor->insert('vBForum:contenttype', $data);
		}
	}

	// Add createpermissions bit for event. Follow vbforum_text
	public function step_8()
	{
		// Only run once.
		if ($this->iRan(__FUNCTION__))
		{
			return;
		}

		$assertor = vB::getDbAssertor();
		$this->show_message($this->phrase['version']['526a5']['adding_event_createpermissions']);


		$permBits = $this->getUGPBitfields();

		$params = array(
			'textbit' => $permBits['createpermissions']['vbforum_text'],
			'eventbit' => $permBits['createpermissions']['vbforum_event'],
		);
		$assertor->assertQuery('vBInstall:setVbforumEventPermission', $params);
	}

	/**
	 * Add the widget.titlephrase field
	 */
	public function step_9()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'widget', 1, 1),
			'widget',
			'titlephrase',
			'VARCHAR',
			array('length' => 255, 'null' => false, 'default' => '')
		);
	}
}

class vB_Upgrade_526b1 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->show_message($this->phrase['version']['526a6']['updating_searchwidgets']);

		$assertor = vB::getDbAssertor();
		// Some search & sgsidebar widgets have the content types specified, which makes it troublesome when
		// we add new contenttypes.
		$guids = array("vbulletin-widget_search-4eb423cfd6a5f3.08329785",
			"vbulletin-widget_sgsidebar-4eb423cfd6dea7.34930861"
		);
		$widgetRows = $assertor->getRows('widget', array('guid' => $guids));
		$widgetIDs = array();
		foreach($widgetRows AS $__row)
		{
			$widgetIDs[] = $__row['widgetid'];
		}


		$types = array(
			array("vBForum_Gallery","vBForum_Link","vBForum_Photo","vBForum_Poll","vBForum_Text","vBForum_Video"),
			array("vBForum_Text","vBForum_Poll","vBForum_Gallery","vBForum_Video","vBForum_Link"),
		);
		$updated = array();
		$widgetinstanceRows = $assertor->getRows('widgetinstance', array('widgetid' => $widgetIDs));
		foreach($widgetinstanceRows AS $__row)
		{
			$__widgetinstanceid = $__row['widgetinstanceid'];
			$__adminconfig = unserialize($__row['adminconfig']);

			if (isset($__adminconfig['searchJSON']))
			{
				// Sometimes searchJSON can apparently be saved as an array instead of a json_encoded string
				if (!is_array($__adminconfig['searchJSON']))
				{
					$__searchJSON = json_decode($__adminconfig['searchJSON'], 1);
				}
				if (!empty($__searchJSON['type']))
				{
					foreach($types AS $__types)
					{
						if (
							$__searchJSON['type'] == $__types OR
							empty(array_diff($__searchJSON['type'], $__types)) AND empty(array_diff($__types, $__searchJSON['type']))
						)
						{
							unset($__searchJSON['type']);
							$__adminconfig['searchJSON'] = json_encode($__searchJSON);
							$__serialized = serialize($__adminconfig);
							if (!empty($__serialized))
							{
								$try = $assertor->update(
									'widgetinstance',
									array('adminconfig' => $__serialized),
									array('widgetinstanceid' => $__widgetinstanceid)
								);
								$updated[] =  $__widgetinstanceid;
							}
							break;
						}
					}
				}
			}
		}

		$this->show_message(sprintf($this->phrase['core']['processed_records_x'], count($updated)));
	}
}

class vB_Upgrade_526rc2 extends vB_Upgrade_Version
{
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

class vB_Upgrade_527a1 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->show_message($this->phrase['version']['526a7']['updating_recentblogposts_widget']);

		$assertor = vB::getDbAssertor();
		// Some search & sgsidebar widgets have the content types specified, which makes it troublesome when
		// we add new contenttypes.
		$widgetguid = "vbulletin-widget_search-4eb423cfd6a5f3.08329785";
		$widgetRow = $assertor->getRow('widget', array('guid' => $widgetguid));
		$widgetID = $widgetRow['widgetid'];
		$pagetemplateguid = "vbulletin-4ecbdac9370e30.09770013";
		$pagetemplateRow = $assertor->getRow('pagetemplate', array('guid' => $pagetemplateguid));
		$pagetemplateID = $pagetemplateRow['pagetemplateid'];

		$updated = array();
		$widgetinstanceRows = $assertor->getRows('widgetinstance', array('widgetid' => $widgetID, 'pagetemplateid' => $pagetemplateID));
		foreach($widgetinstanceRows AS $__row)
		{
			$__widgetinstanceid = $__row['widgetinstanceid'];
			$__adminconfig = unserialize($__row['adminconfig']);

			if (!empty($__adminconfig['searchTitle']) AND "Recent Blog Posts" == $__adminconfig['searchTitle'] AND !empty($__adminconfig['searchJSON']))
			{
				$__searchJSON = json_decode($__adminconfig['searchJSON'], 1);

				/*
					The old serialized searchJSON was:
						s:10:"searchJSON";s:125:"{"date":{"from":"30"},"channel":["5"],"sort":{"created":"desc"},"exclude_type":["vBForum_PrivateMessage"],"starter_only":"1"}";
					with channel = array("5");

				 */
				if (!empty($__searchJSON['channel']) AND is_array($__searchJSON['channel']) AND reset($__searchJSON['channel']) == 5)
				{
					unset($__searchJSON['channel']);
					$__searchJSON['channelguid'] = "vbulletin-4ecbdf567f3a38.99555305"; // Blogs channel GUID taken from the channels XML
					$__adminconfig['searchJSON'] = json_encode($__searchJSON);
					$__serialized = serialize($__adminconfig);
					if (!empty($__serialized))
					{
						$try = $assertor->update(
							'widgetinstance',
							array('adminconfig' => $__serialized),
							array('widgetinstanceid' => $__widgetinstanceid)
						);
						$updated[] = $__widgetinstanceid;
					}
				}
			}
		}

		$this->show_message(sprintf($this->phrase['core']['processed_records_x'], count($updated)));
	}
}

class vB_Upgrade_527a2 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->drop_table('access');
	}
}

class vB_Upgrade_527a3 extends vB_Upgrade_Version
{
	/**
	 * VBV-17001 - if the infractions tab exists in the display tabs setting for widget_profile,
	 * reset the config to default to avoid displaying a blank tab
	 */
	public function step_1()
	{
		$db = vB::getDbAssertor();
		$updated = false;

		$widget = $db->getRow('widget', array('guid' => 'vbulletin-widget_profile-4eb423cfd6d4b0.24011159'));
		$instances = $db->getRows('widgetinstance', array('widgetid' => $widget['widgetid']));

		foreach ($instances AS $instance)
		{
			if (empty($instance['adminconfig']))
			{
				continue;
			}

			$adminconfig = unserialize($instance['adminconfig']);
			if (!$adminconfig)
			{
				continue;
			}

			if (!empty($adminconfig['display_tabs']) AND is_array($adminconfig['display_tabs']))
			{
				$foundBadTab = false;
				foreach ($adminconfig['display_tabs'] AS $tab)
				{
					if ($tab == '#infractions-tab')
					{
						$foundBadTab = true;
						break;
					}
				}

				if ($foundBadTab)
				{
					// reset tabs to default value
					$adminconfig['display_tabs'] = '';
					$adminconfig['tab_order'] = '';
					$adminconfig['default_tab'] = '';

					// update
					$condition = array('widgetinstanceid' => $instance['widgetinstanceid']);
					$values = array('adminconfig' => serialize($adminconfig));
					$db->update('widgetinstance', $values, $condition);

					$updated = true;
				}
			}
		}

		if ($updated)
		{
			$this->show_message($this->phrase['version']['527a3']['updating_profile_module_tab_config']);
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
|| # CVS: $RCSfile$ - $Revision: 112193 $
|| ####################################################################
\*======================================================================*/
