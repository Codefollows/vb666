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

class vB_Upgrade_510a1 extends vB_Upgrade_Version
{
	/**
	 * Update site navbars (articles home page is added in 5.0.6)
	 */
	public function step_1()
	{
		$this->syncNavbars('navbar_articles');
		$this->long_next_step();
	}


	/**
	 *	Step 2 - Delete Imported CMS stuff from old 500a1 steps 158, 169, 170 (previously 159, 160)
	 *		We really shouldn't try to salvage the old imported CMS data, because they were imported incorrectly. For instance,
	 *		any nested sections (i.e. further than 1 degree away from the front page) were not imported.
	 */
	public function step_2()
	{
		vB_Upgrade::createAdminSession();
		$assertor = vB::getDbAssertor();

		// based on the old step_158, the oldcontenttypeid for imported sections was the section contenttypeid
		// Since 500a27 step_10 removes old products, we have to try to figure out the contenttypeid of vBCms_Section in a bit roundabout way
		$oldContenttypeid = $assertor->getRow('vBInstall:getvBCMSSectionContenttypeid', array());
		if (!empty($oldContenttypeid))
		{
			$oldContenttypeid = $oldContenttypeid['contenttypeid'];
			// the old cms stuff was imported into the special channel
			$specialChannelId = vB_Api::instanceInternal('content_channel')->fetchChannelIdByGUID(vB_Channel::DEFAULT_CHANNEL_PARENT);

			// step_158 did something weird where it joined to the node table for seemingly no reason, then set the oldid of the CMS root to the nodeid where node.parentid = 0
			// i.e. probably the node id of the root node (which is the only node that would have a parentid = 0).
			// That's why vBInstall:findOldImportedCMSHome has a INNER JOIN {TABLE_PREFIX}node AS p ON p.parentid = 0 AND n.oldid = p.nodeid
			$cmsHome = $assertor->getRow('vBInstall:findOldImportedCMSHome', array('oldcontenttypeid' => $oldContenttypeid, 'parentid' => $specialChannelId));

			if (!empty($cmsHome) AND $this->tableExists('cms_node') AND $this->tableExists('cms_nodeinfo'))
			{
				$this->show_message($this->phrase['version']['510a1']['deleting_old_cms']);


				// Try not not get rid of the ONLY remaining CMS data if they removed or truncated the tables for some reason.
				$cmsIsThere = $assertor->getRow('vBInstall:checkOldCMSTable', array());
				if (!empty($cmsIsThere))
				{
					// delete the home CMS channel.
					vB_Library::instance('content_channel')->delete($cmsHome);
				}
				else
				{
					$this->show_message($this->phrase['version']['510a1']['failed_to_delete_old_cms']);
				}
			}
			else
			{
				// cms home wasn't found, so there is nothing to delete.
				$this->skip_message();
			}
		}
		else
		{
			// we couldn't find a vbcms package, so either vB4 CMS was not installed, or we are simply unable to find the contenttypeid and thus cannot delete.
			$this->skip_message();
		}
	}

	/**
	 * Steps 3-7 :
	 * We need to import the newly added CMS Articles Home Page/Pagetemplate/Channel/Routes (and uncategorized category)
	 * All this data is in the XML files, so we call final_upgrade steps 4-8 to import them, because we need the article channels
	 * in this upgrade version before we can import data into them. Copied from 500a1 steps 128~
	 * First, import widgets
	 */
	public function step_3()
	{
		vB_Library::clearCache();
		$this->final_load_widgets();
	}

	/**
	 * See step_3's comments
	 */
	public function step_4()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'pagetemplate', 1, 1),
			'pagetemplate',
			'screenlayoutsectiondata',
			'text',
			array('null' => false, 'default' => '')
		);
		$this->execute();

		vB_Library::clearCache();
		$this->final_load_pagetemplates();
	}

	/**
	 * See step_3's comments
	 */
	public function step_5()
	{
		vB_Library::clearCache();
		$this->final_load_pages();
	}

	/**
	 * See step_3's comments
	 */
	public function step_6()
	{
		vB_Library::clearCache();
		$this->final_load_channels();
	}

	/**
	 * See step_3's comments
	 */
	public function step_7()
	{
		vB_Library::clearCache();
		$this->final_load_routes();
	}

	/**
	 * See step_3's comments
	 */
	public function step_8()
	{
		vB_Library::clearCache();
		$this->final_create_channelroutes();
	}

	/**
	 * Now the default article channels should be in place, and we're ready to import old vb4 data.
	 *	Step 9 - Import vB4 CMS Home page data
	 */
	public function step_9()
	{
		vB_Upgrade::createAdminSession();

		$currentStep = 9;	// moving upgrade steps around. This is meant to remind me to update it to match the current step

		/* We should run this only once, so that if they update the channel info, then happen to run this upgrade class again,
		 	the data doesn't get wiped.	To do this we check the upgrader log to	see if this step has been previously run.
			Note that if the user wishes to forcefully run this step again, they'll have to manually edit the upgradelog */
		$log = vB::getDbAssertor()->getRow('vBInstall:upgradelog', array('script' => '510a1', 'step' => $currentStep)); // Must match this step.

		// We removed products in one of the upgrade steps. But if they had vb4 cms, cms_node & cms_nodeinfo tables should exist.
		if ($this->tableExists('cms_node') AND $this->tableExists('cms_nodeinfo') AND empty($log))
		{
			$assertor = vB::getDbAssertor();
			$articlesRootChannelId = vB_Api::instanceInternal('content_channel')->fetchChannelIdByGUID(vB_Channel::DEFAULT_ARTICLE_PARENT);
			$sectionTypeId = $assertor->getRow('vBInstall:getvBCMSSectionContenttypeid', array());
			$sectionTypeId = $sectionTypeId['contenttypeid'];
			$oldContenttypeid = vB_Api_ContentType::OLDTYPE_CMS_SECTION;	// oldcontenttypeid should be a defined unique constant that we can refer to later.

			// based on the old 500a1 step_158, the home page is defined as from cms_node.nodeid = 1;
			$homeSection = $assertor->getRow('vBInstall:getOldCMSHome', array('oldcontenttypeid' => $oldContenttypeid));

			if ($homeSection)
			{
				// output what we're doing
				$this->show_message($this->phrase['version']['510a1']['importing_cms_home']);

				// default settings, taken from 500a1 step_158
				foreach(array('showpublished', 'open', 'approved', 'showopen', 'showapproved', 'inlist') AS $field)
				{
					$homeSection[$field] = 1;
				}
				vB_Library::instance('content_channel')->update($articlesRootChannelId, $homeSection);
			}
			else
			{
				// home not found. Nothing to update
				$this->skip_message();
			}
		}
		else
		{
			// did not have vb4 cms OR already ran this step.
			$this->skip_message();
		}
	}

	/**
	 *	Step 10 - Import vB4 CMS Sections (sections are now called categories in vB5)
	 *	articles right under the front page should be added to uncategorized default category.
	 */
	public function step_10($data = null)
	{
		vB_Upgrade::createAdminSession();

		// We removed products in one of the upgrade steps. But if they had vb4 cms, cms_node & cms_nodeinfo tables should exist.
		if ($this->tableExists('cms_node') AND $this->tableExists('cms_nodeinfo'))
		{
			$assertor = vB::getDbAssertor();
			$channelLib = vB_Library::instance('content_channel');

			$sectionTypeId = $assertor->getRow('vBInstall:getvBCMSSectionContenttypeid', array());
			$sectionTypeId = $sectionTypeId['contenttypeid'];
			$oldContenttypeid = vB_Api_ContentType::OLDTYPE_CMS_SECTION;

			// On my machine, 100 channels took about 10s per iteration.
			$batchSize = 100;

			if (!isset($data['startat']))
			{
				// indicate what we're doing for the first run.
				$this->show_message($this->phrase['version']['510a1']['importing_cms_sections']);
			}

			// if we didn't skip this section, but it imported 0 sections, then something might be wrong with the imported CMS home section (step_3 above)
			$sectionsToImport = $assertor->assertQuery('vBInstall:getOldCMSSections',
				array(
					'oldcontenttypeid' => $oldContenttypeid,
					'sectiontypeid' => $sectionTypeId,
					'batchsize' => $batchSize,
			));
			$processed = 0;
			if ($sectionsToImport->valid())
			{
				foreach ($sectionsToImport AS $newSection)
				{
					/* Partially Copied from article library's createArticleCategory & createChannel.
					 * It's moved here because createChannel() also calls some cleanup stuff after node creation,
					 * which is not necessary for upgrade.
					 */

					$newSection['inlist'] = 1;
					$newSection['protected'] = 0;

					$newSection['templates']['vB5_Route_Channel'] = vB_Page::getArticleChannelPageTemplate();
					$newSection['templates']['vB5_Route_Article'] = vB_Page::getArticleConversPageTemplate();
					$newSection['childroute'] = 'vB5_Route_Article';

					// add channel node
					$newSection['page_parentid'] = 0;
					$channelLib->add($newSection,
						array(
							'skipNotifications' => true,
							'skipFloodCheck' => true,
							'skipDupCheck' => true,
							'skipBuildLanguage' => true,
						)
					);

					$processed++;
				}
			}
			else
			{
				// rebuild all languages that were postponed from pagemeta/desc phrase saves
				// triggered downstream of vB_Library_Content_Channel::createChannelPages()
				vB_Library::instance('language')->rebuildAllLanguages();

				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}

			$this->show_message(sprintf($this->phrase['core']['processed_records_x'], $processed));
			// kick off next batch. Return a nonzero startat to make it iterate.
			return array('startat' => 1);
		}
		else
		{
			// did not have vb4 CMS, nothing to import.
			$this->skip_message();
		}
	}


	/**
	 *	Step 11 - Import vB4 CMS Articles & Static Pages
	 */
	public function step_11($data = NULL)
	{
		vB_Upgrade::createAdminSession();

		// We removed products in one of the upgrade steps. But if they had vb4 cms, cms_node & cms_nodeinfo tables should exist.
		if ($this->tableExists('cms_node') AND $this->tableExists('cms_nodeinfo'))
		{
			$assertor = vB::getDbAssertor();

			// using the content add() functions so that they inherit show_X properties properly
			// Going through the lib instead of API because we dont' want to go through convertWysiwygTextToBbcode()
			$textLib = vB_Library::instance('Content_Text');

			// Previously we were importing via vB_Library_Content_Text::add() at about 50 per 6.4s
			// Seems like the new bulk importer can handle about 500 imports in about 8 seconds, so it's
			// about 8 times faster. Going with this batchsize for now.
			$batchsize = 500;

			// contenttypeids
			$articleTypeId = $assertor->getRow('vBInstall:getvBCMSArticleContenttypeid', ['class' => 'Article']);
			$articleTypeId = $articleTypeId['contenttypeid'];

			//depending on when we upgrade from, we could have CMS data but not the static page type.  If that's the case
			//just feed the queries a null value.  It will noop the page operations and trying to detangle the two isn't
			//worth the effort (both types have actually been null to date anyway due to bug in the query).
			$staticPageTypeId = $assertor->getRow('vBInstall:getvBCMSArticleContenttypeid', ['class' => 'StaticPage']);
			$staticPageTypeId = $staticPageTypeId['contenttypeid'] ?? null;

			$textTypeId = vB_Types::instance()->getContentTypeID('vBForum_Text');
			$oldContenttypeidSection = vB_Api_ContentType::OLDTYPE_CMS_SECTION;
			$oldContenttypeidArticle = vB_Api_ContentType::OLDTYPE_CMS_ARTICLE;
			$oldContenttypeidStaticPage = vB_Api_ContentType::OLDTYPE_CMS_STATICPAGE;

			// grab startat
			if (isset($data['startat']))
			{
				$startat = intval($data['startat']);
			}
			else
			{
				$this->show_message($this->phrase['version']['510a1']['importing_cms_articles']);
				// if first iteration, begin at MIN(cms_node.nodeid) for nodes that have not been imported yet
				$min = $assertor->getRow('vBInstall:getMinMissingArticleNodeid',
					array(
						'articleTypeId' => $articleTypeId,
						'staticPageTypeId' => $staticPageTypeId,
						'oldcontenttypeid_article' => $oldContenttypeidArticle,
						'oldcontenttypeid_staticpage' => $oldContenttypeidStaticPage
					)
				);
				// we use exclusive <'s, so startat should start right before the min to import
				$startat = (isset($min['minid']))? intval($min['minid']) - 1 : 0;
			}

			// grab max
			if (!empty($data['max']))
			{
				$max = intval($data['max']);
			}
			else
			{
				$max = $assertor->getRow('vBInstall:getMaxMissingArticleNodeid',
					array(
						'articleTypeId' => $articleTypeId,
						'staticPageTypeId' => $staticPageTypeId,
						'oldcontenttypeid_article' => $oldContenttypeidArticle,
						'oldcontenttypeid_staticpage' => $oldContenttypeidStaticPage
					)
				);
				$max = intval($max['maxid']);
			}

			// if startat is greater than the maximum cms_node.nodeid of previously missing vb4 data, we're done
			if ($startat >= $max)
			{
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}

			require_once(DIR . "/install/includes/bulkimporter.php");
			$importer = new vB_UpgradeHelper_BulkImporter();

			// import data
			$articlesToImport = $assertor->assertQuery('vBInstall:getOldCMSArticles',
				array(
					'startat' => $startat,
					'batchsize' => $batchsize,
					'textTypeId' => $textTypeId,
					'oldcontenttypeid_article' => $oldContenttypeidArticle,
					'oldcontenttypeid_section' => $oldContenttypeidSection,
					'articleTypeId' => $articleTypeId
				)
			);
			if ($articlesToImport->valid())
			{
				$nodeids = $importer->importBulkCMSArticles($articlesToImport);
				if (empty($nodeids))
				{
					/*
						The importer currently has zero error handling features if something
						prevents the import of an article or section.
						I did not encounter any failures during testing, but if the node
						insert failed, presumably we could get stuck in an infinite loop
						in the upgrade step. This exception is meant to stop that.
						I think the most likely thing to happen is either a DB connection
						error or an inconsistent node table structure that somehow slipped
						through.
						In any case, we should improve the error handling as appropriate
						as we encounter them in the wild.
					 */
					throw new Exception("CMS import failed. Please check your database connection and try again.");
				}
			}

			$staticPagesToImport = $assertor->assertQuery('vBInstall:getOldCMSStaticPages',
				array(
					'startat' => $startat,
					'batchsize' => $batchsize,
					'textTypeId' => $textTypeId,
					'oldcontenttypeid_staticpage' => $oldContenttypeidStaticPage,
					'oldcontenttypeid_section' => $oldContenttypeidSection,
					'staticPageTypeId' => $staticPageTypeId
				)
			);

			if ($staticPagesToImport->valid())
			{
				$nodeids2 = $importer->importBulkCMSArticles($staticPagesToImport);
				if (empty($nodeids2))
				{
					// See note above
					throw new Exception("CMS import failed. Please check your database connection and try again.");
				}
			}

			// output current progress
			$this->show_message(sprintf($this->phrase['core']['processed_records_x_y_z'], $startat +1, min($startat + $batchsize, $max), $max));

			// kick off next batch
			return array('startat' => $startat + $batchsize, 'max' => $max);
		}
		else
		{
			// did not have vb4 CMS, nothing to import.
			$this->skip_message();
		}
	}


	/**
	 *	Step 12 - Import vB4 CMS Article Comments
	 *		They are imported as forums, so we should try to find them & move them rather than re-importing.
	 */
	public function step_12($data = null)
	{
		vB_Upgrade::createAdminSession();

		// We removed products in one of the upgrade steps. But if they had vb4 cms, cms_node & cms_nodeinfo tables should exist.
		if ($this->tableExists('cms_node') AND $this->tableExists('cms_nodeinfo'))
		{
			$assertor = vB::getDbAssertor();

			$batchsize = 500; // Need a DB w/ a large enough CMS section to fiddle with this number

			// contenttypeids
			$postTypeId =  vB_Types::instance()->getContentTypeID('vBForum_Post');
			$oldContenttypeidArticle = vB_Api_ContentType::OLDTYPE_CMS_ARTICLE;
			$oldContenttypeidArticleComment = vB_Api_ContentType::OLDTYPE_CMS_COMMENT;
			$oldContenttypeidStaticPage = vB_Api_ContentType::OLDTYPE_CMS_STATICPAGE;

			// grab imported article/staticpage nodeids & put them in an array
			$nodeidQry = $assertor->assertQuery('vBInstall:getUnmovedArticleCommentNodeids',
				array(
					'posttypeid' => $postTypeId,
					'batchsize' => $batchsize,
					'oldcontenttypeid_article' => $oldContenttypeidArticle,
					'oldcontenttypeid_staticpage' => $oldContenttypeidStaticPage,
				)
			);
			$nodeids = array();
			foreach ($nodeidQry AS $node)
			{
				$nodeids[] = $node['nodeid'];
			}

			// set startat
			// we use exclusive <'s, so startat should start right before the min to import
			$startat =  (!empty($nodeids))? min($nodeids) - 1 : 0;

			if (!isset($data['startat']))
			{
				// display message for first iteration
				$this->show_message($this->phrase['version']['510a1']['updating_cms_comments']);
			}

			// grab max
			if (!empty($data['max']))
			{
				$max = intval($data['max']);
			}
			else
			{
				$max = $assertor->getRow('vBInstall:getMaxUnmovedArticleCommentNodeid',
					array(
						'posttypeid' => $postTypeId,
						'oldcontenttypeid_article' => $oldContenttypeidArticle,
						'oldcontenttypeid_staticpage' => $oldContenttypeidStaticPage,
					)
				);
				$max = intval($max['maxid']);
			}

			// if there are no remaining nodes to process, we're done
			if (empty($nodeids))
			{
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}

			// CMS Article Comments were saved as posts with post.threadid = cms_nodeinfo.associatedthreadid
			// So they would have been imported into the node table with oldcontenttypeid = <id for vBForum_Post> AND oldid = <postid>
			// Note that the default starter post that isn't actually a comment has post.parentid = 0.


			// move nodes under article. update routeids (?). Clean up old channel(s)/comment starters?
			// set oldcontenttypeid, also create self & parent closure records. Clean up old closure records (?)
			$assertor->assertQuery('vBInstall:moveArticleCommentNodes',
				array(
					'nodeids' => $nodeids,
					'posttypeid' => $postTypeId,
					'oldcontenttypeid_article' => $oldContenttypeidArticle,
					'oldcontenttypeid_staticpage' => $oldContenttypeidStaticPage,
					'oldcontenttypeid_articlecomment' => $oldContenttypeidArticleComment,
			));
			// remove previous closure parents. Leave existing self closure alone.
			$assertor->assertQuery('vBInstall:removeArticleCommentClosureParents',
				array(
					'nodeids' => $nodeids,
					'oldcontenttypeid_articlecomment' => $oldContenttypeidArticleComment
			));
			// add closure parents.
			$assertor->assertQuery('vBInstall:addArticleCommentClosureParents',
				array(
					'nodeids' => $nodeids,
					'oldcontenttypeid_articlecomment' => $oldContenttypeidArticleComment
			));

			// output current progress
			$this->show_message(sprintf($this->phrase['core']['processed_records_x_y_z'], $startat +1, max($nodeids), $max));

			// kick off next batch
			return array('startat' => max($nodeids), 'max' => $max);
		}
		else
		{
			// did not have vb4 CMS, nothing to import.
			$this->skip_message();
		}
	}





	/**
	 *	Step 13 - Import vB4 CMS Permissions
	 */
	public function step_13()
	{
		if (!$this->tableExists('cms_permissions'))
		{
			$this->skip_message();
		}
		else
		{
			$assertor = vB::getDbAssertor();
			vB_Upgrade::createAdminSession();
			$channelTypeId = vB_Types::instance()->getContentTypeID('vBForum_Channel');
			//because the cms package is not installed we can't user getContenttypeid('vBCMS_Section');
			$package = $assertor->getRow('package', [ 'productid' => 'vbcms']);

			if (!$package OR !empty($package['errors']))
			{
				$this->skip_message();
			}
			$sectionType = $assertor->getRow('vBForum:contenttype', ['packageid' => $package['packageid'], 'class' => 'Section']);

			if (!$sectionType OR !empty($sectionType['errors']))
			{
				$this->skip_message();
			}
			// for the oldcontenttypeid of imported data, we used contenttypeid for vBCms_Section in 500a1,
			// but we're using a defined constant when we import the data again, see "Import vB4 CMS Sections" step above
			$sectionTypeId = vB_Api_ContentType::OLDTYPE_CMS_SECTION;
			$cmsPerms = $assertor->assertQuery('vBInstall:cms_permissions', []);
			//VB4 CMS Permissions are:
			//1: canview
			//2: cancreate
			//4: canedit
			//8: canpublish
			//16: canUseHtml
			//32: canDownload
			$this->show_message($this->phrase['version']['510a1']['setting_cms_perms']);
			if ($cmsPerms->valid())
			{
				$forumBits = $forumBits2 = [];
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
									$forumBits[$bitfield['name']] = intval($bitfield['value']);
								}
							}
							else if ($bfgroup['name'] == 'forumpermissions2')
							{
								foreach ($bfgroup['bitfield'] AS $bitfield)
								{
									$forumBits2[$bitfield['name']] = intval($bitfield['value']);
								}
							}
						}
					}
				}

				$channelPerm = vB_ChannelPermission::instance();
				foreach ($cmsPerms as $cmsPerm)
				{
					$nodeid = $assertor->getField('vBForum:node', [
						'oldid' => $cmsPerm['nodeid'],
						'oldcontenttypeid' => $sectionTypeId,
						vB_dB_Query::COLUMNS_KEY => 'nodeid'
					]);

					if ($nodeid)
					{
						$perms =  $channelPerm->fetchPermissions(1, $cmsPerm['usergroupid']);
						$perms['forumpermissions'] = $perms['forumpermissions'] ?? 0;
						$perms['forumpermissions2'] = $perms['forumpermissions2'] ?? 0;

						if ($cmsPerm['permissions'] & 1)//1: canview
						{
							$perms['forumpermissions'] |= $forumBits['canview'] | $forumBits['canviewthreads'] | $forumBits['canviewothers'];
						}
						else
						{
							$perms['forumpermissions'] &= ~$forumBits['canview'] & ~$forumBits['canviewthreads'] & ~$forumBits['canviewothers'];
						}



						if ($cmsPerm['permissions'] & 2)//2: cancreate
						{
							$perms['forumpermissions'] |= $forumBits['canpostnew'];
						}
						else
						{
							$perms['forumpermissions'] &= ~$forumBits['canpostnew'];
						}

						if ($cmsPerm['permissions'] & 4)//4: canedit
						{
							$perms['forumpermissions'] |= $forumBits['caneditpost'];
						}
						else
						{
							$perms['forumpermissions'] &= ~$forumBits['caneditpost'];
						}

						if ($cmsPerm['permissions'] & 8)//8: canpublish
						{
							$perms['forumpermissions2'] |= $forumBits2['canpublish'];
						}
						else
						{
							$perms['forumpermissions2'] &= ~$forumBits2['canpublish'];
						}

						if ($cmsPerm['permissions'] & 16)//16: canUseHtml
						{
							$perms['forumpermissions2'] |= $forumBits2['canusehtml'];
						}
						else
						{
							$perms['forumpermissions2'] &= ~$forumBits2['canusehtml'];
						}

						if ($cmsPerm['permissions'] & 32)//32: canDownload
						{
							$perms['forumpermissions'] |= $forumBits['cangetattachment'];
						}
						else
						{
							$perms['forumpermissions'] &= ~$forumBits['cangetattachment'];
						}
						$channelPerm->setPermissions($nodeid, $cmsPerm['usergroupid'], $perms);
					}
				}
			}
		}
	}


	/**
	 *	Step 14 - Import vB4 CMS Article Attachments
	 * 	Note, only articles had attachments. As far as I'm aware, static pages could not have attachments anywhere.
	 */
	public function step_14($data = NULL)
	{
		vB_Upgrade::createAdminSession();

		// We removed products in one of the upgrade steps. But if they had vb4 cms, cms_node & cms_nodeinfo tables should exist.
		// also, attachments are in attachment & filedata tables
		if ($this->tableExists('cms_node') AND $this->tableExists('cms_nodeinfo')
			AND $this->tableExists('attachment') AND $this->tableExists('filedata'))
		{
			$assertor = vB::getDbAssertor();

			$batchsize = 4000;

			// contenttypeids
			$articleTypeId = $assertor->getRow('vBInstall:getvBCMSArticleContenttypeid', array('class' => 'Article'));
			$articleTypeId = $articleTypeId['contenttypeid'];
			$attachTypeId =  vB_Types::instance()->getContentTypeID('vBForum_Attach');
			$oldContenttypeidArticle = vB_Api_ContentType::OLDTYPE_CMS_ARTICLE;
			$oldContenttypeidArticleAttachment = vB_Api_ContentType::OLDTYPE_ARTICLEATTACHMENT;

			// In vB4, attachment.contenttypeid = vbcms_article, contentid = cms_article.nodeid

			// grab startat
			if (isset($data['startat']))
			{
				$startat = intval($data['startat']);
			}
			else
			{
				$this->show_message($this->phrase['version']['510a1']['importing_cms_article_attachments']);
				// if first iteration, begin at MIN(attachment.attachmentid) for nodes that have not been imported yet
				$min = $assertor->getRow('vBInstall:getMinMissingArticleAttachmentid',
					array('articletypeid' => $articleTypeId, 'oldcontenttypeid_articleattachment' => $oldContenttypeidArticleAttachment));
				// we use exclusive <'s, so startat should start right before the min to import
				$startat = (isset($min['minid']))? intval($min['minid']) - 1 : 0;
			}
			// grab max
			if (!empty($data['max']))
			{
				$max = intval($data['max']);
			}
			else
			{
				$max = $assertor->getRow('vBInstall:getMaxMissingArticleAttachmentid',
					array('articletypeid' => $articleTypeId, 'oldcontenttypeid_articleattachment' => $oldContenttypeidArticleAttachment));
				$max = intval($max['maxid']);
			}

			// if startat is greater than the maximum cms_node.nodeid of previously missing vb4 data, we're done
			if ($startat >= $max)
			{
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}

			// import data
			/*** first the nodes ***/
			$assertor->assertQuery('vBInstall:insertArticleAttachmentNodes', array(
				'attachtypeid' => $attachTypeId,
				'oldcontenttypeid_articleattachment' => $oldContenttypeidArticleAttachment,
				'oldcontenttypeid_article' => $oldContenttypeidArticle,
				'batchsize' => $batchsize,
				'startat' => $startat,
				'articletypeid' => $articleTypeId,
			));

			//Now populate the attach table
			$assertor->assertQuery('vBInstall:insertArticleAttachments', array(
				'oldcontenttypeid_articleattachment' => $oldContenttypeidArticleAttachment,
				'batchsize' => $batchsize,
				'startat' => $startat,
				'articletypeid' => $articleTypeId,
			));

			//Now the closure record for the node
			$assertor->assertQuery('vBInstall:addClosureSelf', array(
				'contenttypeid' => $oldContenttypeidArticleAttachment,
				'startat' => $startat,
				'batchsize' => $batchsize,
			));

			//Add the closure records to root
			$assertor->assertQuery('vBInstall:addClosureParents', array(
				'contenttypeid' => $oldContenttypeidArticleAttachment,
				'startat' => $startat,
				'batchsize' => $batchsize,
			));


			// output current progress
			$this->show_message(sprintf($this->phrase['core']['processed_records_x_y_z'], $startat +1, min($startat + $batchsize, $max), $max));

			// kick off next batch
			return array('startat' => $startat + $batchsize, 'max' => $max);
		}
		else
		{
			// did not have vb4 CMS, nothing to import.
			$this->skip_message();
		}
	}

	/**
	 * Step 15 - Update imported vb4 CMS Article nodes' textcount with # of children (comments)
	 *		Since the only text-type children of articles should be comments, totalcount & textcount
	 *		should be the total # of comments.
	 *		Articles were imported in step_11. Comments were imported in step_12
	 */
	public function step_15($data = null)
	{
		// We removed products in one of the upgrade steps. But if they had vb4 cms, cms_node & cms_nodeinfo tables should exist.
		if ($this->tableExists('cms_node') AND $this->tableExists('cms_nodeinfo'))
		{
			$assertor = vB::getDbAssertor();

			$batchsize = 500; // filesort with temporary, may not want to push this further. Needs some fiddling around with larger test data

			// contenttypeids
			$oldContenttypeidArticle = vB_Api_ContentType::OLDTYPE_CMS_ARTICLE;
			$oldContenttypeidStaticPage = vB_Api_ContentType::OLDTYPE_CMS_STATICPAGE;
			$oldContenttypeidArticleComment = vB_Api_ContentType::OLDTYPE_CMS_COMMENT;

			// grab startat
			if (isset($data['startat']))
			{
				$startat = intval($data['startat']);
			}
			else
			{
				$this->show_message($this->phrase['version']['510a1']['updating_cms_article_textcount']);
				// start at nodeid 0
				$startat = 0;
			}

			// grab imported article/staticpage nodeids & put them in an array
			$nodeidQry = $assertor->assertQuery('vBInstall:getImportedArticleNodeids',
				array(
					'oldcontenttypeid_article' => $oldContenttypeidArticle,
					'oldcontenttypeid_staticpage' => $oldContenttypeidStaticPage,
					'startat' => $startat,
					'batchsize' => $batchsize
				)
			);
			$nodeids = array();
			foreach ($nodeidQry AS $node)
			{
				$nodeids[] = $node['nodeid'];
			}

			// grab max
			if (!empty($data['max']))
			{
				$max = intval($data['max']);
			}
			else
			{
				$max = $assertor->getRow('vBInstall:getMaxNodeidForOldContent', [
					'oldcontenttypeid' => [$oldContenttypeidArticle, $oldContenttypeidStaticPage],
				]);
				$max = intval($max['maxid']);
			}

			// if startat is greater than the maximum cms_node.nodeid of previously missing vb4 data, we're done
			if ($startat >= $max OR empty($nodeids))
			{
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}

			// import data
			// currently, the group by in the inner select results in filesort, but I don't think we can do anything to avoid that.
			$assertor->assertQuery('vBInstall:updateImportedArticleTextcount',
				array(
					'nodeids' => $nodeids,
					'oldcontenttypeid_article' => $oldContenttypeidArticle,
					'oldcontenttypeid_staticpage' => $oldContenttypeidStaticPage,
					'oldcontenttypeid_articlecomment' => $oldContenttypeidArticleComment
				)
			);

			// insert nodeview records
			$assertor->assertQuery('vBInstall:importArticleViewcount',
				array(
					'nodeids' => $nodeids,
					'oldcontenttypeid_article' => $oldContenttypeidArticle,
					'oldcontenttypeid_staticpage' => $oldContenttypeidStaticPage
				)
			);

			// output current progress
			$this->show_message(sprintf($this->phrase['core']['processed_records_x_y_z'], min($nodeids), max($nodeids), $max));

			// kick off next batch
			return array('startat' => max($nodeids), 'max' => $max);
		}
		else
		{
			// did not have vb4 CMS, nothing to import.
			$this->skip_message();
		}
	}

	/**
	 * Step 16 - Update imported static page nodes with new nodeoption vB_Api_Node::OPTION_NODE_DISABLE_BBCODE = 1024;
	 */
	public function step_16($data = NULL)
	{
		// We removed products in one of the upgrade steps. But if they had vb4 cms, cms_node & cms_nodeinfo tables should exist.
		if ($this->tableExists('cms_node') AND $this->tableExists('cms_nodeinfo'))
		{

			// set-up constants, objects etc.
			$assertor = vB::getDbAssertor();
			$newOption = vB_Api_Node::OPTION_NODE_DISABLE_BBCODE;
			$oldContenttypeidStaticPage = vB_Api_ContentType::OLDTYPE_CMS_STATICPAGE;
			$batchsize = 100000;

			// grab startat
			if (isset($data['startat']))
			{
				$startat = intval($data['startat']);
			}
			else
			{
				// display what we're doing
				$this->show_message(sprintf($this->phrase['version']['510a1']['updating_staticpage_nodeoption']));
				// start at the first imported article nodeid.
				$min = $assertor->getRow('vBInstall:getStaticPageNodeidsToUpdate',
					array(
						'oldcontenttypeid_staticpage' => $oldContenttypeidStaticPage,
						'option_disable_bbcode' => $newOption
					)
				);
				// we use exclusive <'s, so startat should start right before the min to import
				$startat = (isset($min['minid']))? intval($min['minid']) - 1 : 0;

				// also set max while we're at it.
				$data['max'] = $min['maxid'];
			}

			// grab max
			if (!empty($data['max']))
			{
				$max = intval($data['max']);
			}
			else
			{
				$max = $assertor->getRow('vBInstall:getStaticPageNodeidsToUpdate',
					array(
						'oldcontenttypeid_staticpage' => $oldContenttypeidStaticPage,
						'option_disable_bbcode' => $newOption
					)
				);
				$max = intval($max['maxid']);
			}

			// if startat is greater than the maximum imported nodeid, we're done
			if ($startat >= $max)
			{
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}

			// update node table
			$assertor->assertQuery('vBInstall:updateStaticPageNodeOptions',
				array(	'new_option' => $newOption,
						'oldcontenttypeid_staticpage' => $oldContenttypeidStaticPage,
						'startat' => $startat,
						'batchsize' => $batchsize
				)
			);

			// output progress & return for next batch
			$this->show_message(sprintf($this->phrase['core']['processed_records_x_y_z'], $startat + 1, min($startat + $batchsize, $max), $max));
			return array('startat' => ($startat + $batchsize), 'max' => $data['max']);
		}
		else
		{
			// did not have vb4 CMS, nothing to import.
			$this->skip_message();
		}
	}

	/**
	 * Step 17 - Clean up CMS comment threads
	 *	Remove any imported forums associated with CMS
	 *	Note, skipped as of VBV-11640. Let admins manually delete the forums
	 */
	public function step_17($data = NULL)
	{
		$this->skip_message();
	}

	/** We have four new admin permissions. We should set those for non-CLI users
	 *
	 */
	public function step_18($data = NULL)
	{
		// this step has been moved back to 506a1 step_1 to match w/ the SaaS branch. See VBV-12141
		// The other possible fix for this particular step's issue was to check for either this step OR
		// 506a1 step_1 in the upgradelog, but due to the upgrade log bug(?) VBV-12130, that was impossible
		$this->skip_message();

	}

	/**
	 * Step 19 - update sections totalcount data. textcount should already be accurate since
	 *		they were updated when articles & static pages were added in bulk, but comments
	 * 		were moved from their old imported nodes to under the articles without updating
	 *		the channels.
	 *	UPDATE: This step needs to be optimized, but there's an AdminCP tool to fix channel counts.
	 *	For now, just add an admincp message to run the tool
	 */
	public function step_19($data = NULL)
	{
		$this->add_adminmessage('after_upgrade_from_505_cms',
		array(
			'dismissable' => 1,
			'status'  => 'undone',
		)
		);
	}
}

class vB_Upgrade_510a2 extends vB_Upgrade_Version
{
	/**Import the vbcms permissions field if appropriate. */
	public function step_1($data = NULL)
	{
		$check = vB::getDbAssertor()->assertQuery('vBInstall:upgradelog', array('script' => '510a2', 'step' => '1'));

		//make sure we only run this once.
		if ($check->valid())
		{
			$this->skip_message();
		}
		else
		{
			$this->show_message($this->phrase['version']['510a2']['setting_cms_admin_permissions']);
			$parsedRaw = vB_Xml_Import::parseFile(DIR . '/includes/xml/bitfield_vbulletin.xml');
			$permBits = array();
			foreach ($parsedRaw['bitfielddefs']['group'] AS $group)
			{
				if ($group['name'] == 'ugp')
				{
					foreach($group['group'] AS $bfgroup)
					{
						if ($bfgroup['name'] == 'adminpermissions')
						{
							foreach ($bfgroup['bitfield'] AS $bitfield)
							{
								$permBits[$bitfield['name']] = intval($bitfield['value']);
							}
						}
					}
				}
			}
			if (!empty($permBits['canadmincms']))
			{
				if ($this->field_exists('administrator', 'vbcmspermissions'))
				{
					vB::getDbAssertor()->assertQuery('vBInstall:setCMSAdminPermFromvB4',
						array('newvalue' => $permBits['canadmincms']));
				}
				else
				{
					vB::getDbAssertor()->assertQuery('vBInstall:setCMSAdminPermFromvExisting',
						array('newvalue' => $permBits['canadmincms'], 'existing' => $permBits['canadminforums']));
				}
			}
		}
		$this->long_next_step();
	}

	/**  Add the public_preview field to the node table*/
	public function step_2($data = NULL)
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'node', 1, 2),
			'node',
			'public_preview',
			'SMALLINT',
			self::FIELD_DEFAULTS
		);
		$this->long_next_step();
	}

	/** Copy over publicpreview from vB4 **/
	public function step_3($data = NULL)
	{
		if ($this->tableExists('cms_node'))
		{
			$check = vB::getDbAssertor()->assertQuery('vBInstall:upgradelog', array('script' => '510a2', 'step' => '4'));

			if ($check->valid())
			{
				$this->skip_message();
			}
			else
			{
				$this->show_message($this->phrase['version']['510a2']['setting_public_preview']);
				vB::getDbAssertor()->assertQuery('vBInstall:importPublicPreview',
				 	array('oldcontenttypes' => array(vB_Api_ContentType::OLDTYPE_CMS_ARTICLE,
				 	vB_Api_ContentType::OLDTYPE_CMS_STATICPAGE)));
			}
		}
		else
		{
			$this->skip_message();
		}

	}

	/**  index the public_preview field*/
	public function step_4($data = NULL)
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'node', 2, 2),
			'node',
			'ppreview',
			'public_preview'
		);
	}

	/*
	   Add scheduled task to check for nodes that need to be published or unpublished.
	*/
	public function step_5()
	{
		$this->add_cronjob(
		array(
			'varname'  => 'scheduled_publish',
			'nextrun'  => 1320000000,
			'weekday'  => -1,
			'day'      => -1,
			'hour'     => -1,
			'minute'   => 'a:6:{i:0;i:0;i:1;i:10;i:2;i:20;i:3;i:30;i:4;i:40;i:5;i:50;}',
			'filename' => './includes/cron/unpublished.php',
			'loglevel' => 1,
			'volatile' => 1,
			'product'  => 'vbulletin'
		)
		);
	}

	/**
	 * Importing cms tags
	 */
	public function step_6($data = [])
	{
		if ($this->tableExists('cms_node'))
		{
			$assertor = vB::getDbAssertor();
			$batchsize = 1000;
			$startat = intval($data['startat'] ?? 0);
			$assertor = vB::getDbAssertor();

			if (isset($data['maxId']))
			{
				$maxId = intval($data['maxId']);
			}
			else
			{
				$this->show_message($this->phrase['version']['510a2']['importing_cms_tags']);
				$maxNode = $assertor->getRow('vBInstall:maxCMSNode');
				$maxId = intval($maxNode['maxId']);
			}

			if ($startat > $maxId)
			{
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}

			$this->show_message(sprintf($this->phrase['core']['processing_records_x'], $batchsize));
			$assertor->assertQuery('vBInstall:importCMSTags', array('startat' => $startat,
				'batchsize' => $batchsize, 'cmstypes' => array(vB_Api_ContentType::OLDTYPE_CMS_STATICPAGE,
					 vB_Api_ContentType::OLDTYPE_CMS_ARTICLE)));
			return array('startat' => ($startat + $batchsize), 'maxId' => $maxId);
		}
		else
		{
			$this->skip_message();
		}
	}


	/**
	 * Importing cms categories as tags
	 */
	public function step_7($data = array())
	{
		if ($this->tableExists('cms_category'))
		{
			$this->show_message($this->phrase['version']['510a2']['importing_cms_category_tags']);
			vB::getDbAssertor()->assertQuery('vBInstall:importCMSCategoryTags',
				array('timenow' => vB::getRequest()->getTimeNow()));
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	 * Importing cms categories as tags
	 */
	public function step_8($data = [])
	{
		if ($this->tableExists('cms_node'))
		{
			vB_Upgrade::createAdminSession();
			$assertor = vB::getDbAssertor();
			$batchsize = 1000;
			$startat = intval($data['startat'] ?? 0);
			$assertor = vB::getDbAssertor();

			if (isset($data['maxId']))
			{
				$maxId = intval($data['maxId']);
			}
			else
			{
				$this->show_message($this->phrase['version']['510a2']['assigning_cms_category_tags']);
				$maxNode = $assertor->getRow('vBInstall:maxCMSNode');
				$maxId = intval($maxNode['maxId']);
			}

			if ($startat > $maxId)
			{
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}

			$this->show_message(sprintf($this->phrase['core']['processing_records_x'], $batchsize));
			$assertor->assertQuery('vBInstall:assignCMSCategoryTags', array('startat' => $startat,
				'batchsize' => $batchsize, 'cmstypes' => array(vB_Api_ContentType::OLDTYPE_CMS_STATICPAGE,
					vB_Api_ContentType::OLDTYPE_CMS_ARTICLE), 'userid' => vB::getCurrentSession()->get('userid'),
					'timenow' => vB::getRequest()->getTimeNow()));
			return array('startat' => ($startat + $batchsize), 'maxId' => $maxId);
		}
		else
		{
			$this->skip_message();
		}
	}


	/**
	 * Set new CMS nodeoptions
	 */
	public function step_9($data = array())
	{
		if ($this->tableExists('cms_node'))
		{
			$check = vB::getDbAssertor()->assertQuery('vBInstall:upgradelog', array('script' => '510a2', 'step' => '9'));

			if ($check->valid())
			{
				$this->skip_message();
			}
			else
			{
				$this->show_message($this->phrase['version']['510a2']['setting_cms_node_options']);
				vB::getDbAssertor()->assertQuery('vBInstall:importCMSnodeOptions',
					array(
						'cmstypes' => array(vB_Api_ContentType::OLDTYPE_CMS_STATICPAGE,
											vB_Api_ContentType::OLDTYPE_CMS_ARTICLE),
						'optiontitle' => vB_Api_Node::OPTION_NODE_HIDE_TITLE,
						'optionauthor' => vB_Api_Node::OPTION_NODE_HIDE_AUTHOR,
						'optionpubdate' => vB_Api_Node::OPTION_NODE_HIDE_PUBLISHDATE,
						'optionfulltext' => vB_Api_Node::OPTION_NODE_DISPLAY_FULL_IN_CATEGORY,
						'optionpageview' => vB_Api_Node::OPTION_NODE_DISPLAY_PAGEVIEWS,
						'optioncomment' => vB_Api_Node::OPTION_ALLOW_POST,	// first invert then bitwise & to unset this bit, then import vb4 field into the bit
					)
				);
			}
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	 * Set node taglist field for the imported articles.
	 */
	public function step_10($data = [])
	{
		if ($this->tableExists('cms_node'))
		{
			$assertor = vB::getDbAssertor();
			$batchsize = 500;
			$startat = intval($data['startat'] ?? 0);
			$assertor = vB::getDbAssertor();

			if (isset($data['maxId']))
			{
				$maxId = intval($data['maxId']);
			}
			else
			{
				$this->show_message($this->phrase['version']['510a2']['setting_taglist_field']);
				$maxNode = $assertor->getRow('vBInstall:maxCMSNode');
				$maxId = intval($maxNode['maxId']);
			}

			if ($startat > $maxId)
			{
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}

			$this->show_message(sprintf($this->phrase['core']['processing_records_x'], $batchsize));
			$nodeTags = $assertor->assertQuery('vBInstall:fetchCMSNodeTags', array('startat' => $startat,
				'batchsize' => $batchsize, 'cmstypes' => array(vB_Api_ContentType::OLDTYPE_CMS_STATICPAGE,
					 vB_Api_ContentType::OLDTYPE_CMS_ARTICLE)));
			$taglist = [];
			if ($nodeTags->valid())
			{
				foreach ($nodeTags as $nodeTag)
				{
					$taglist[$nodeTag['nodeid']][] = $nodeTag['tagtext'];
				}
			}

			foreach ($taglist as $nodeid =>$tags)
			{
				$assertor->assertQuery('vBForum:node', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
				 'nodeid' => $nodeid, 'taglist' => implode(',', $tags)));
			}

			return array('startat' => ($startat + $batchsize), 'maxId' => $maxId);
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	 * Corrent the arguments and regex for vbcms redirect;
	 */
	public function step_11($data = [])
	{
		if ($this->tableExists('cms_node'))
		{
			$assertor = vb::getDbAssertor();
			$regex = '^content[^0-9]*(?P<oldid>[0-9]+)?(-)?(?P<urlident>[^/]*)?(/view/)?(?P<oldpage>[0-9]+)?';
			$arguments = serialize(array('oldid' => '$oldid', 'oldpage' => '$oldpage', 'urlident' > '$urlident'));
			$this->show_message($this->phrase['version']['510a2']['updating_cms_legacy_route']);
			$assertor->assertQuery('routenew', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
				 'regex' => $regex,
				'arguments' => $arguments,
				vB_dB_Query::CONDITIONS_KEY => array('class' => 'vB5_Route_Legacy_vBCms', 'prefix' => 'content.php')));

			$check = $assertor->assertQuery('routenew', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
				'class' => 'vB5_Route_Legacy_vBCms', 'prefix' => 'content'
			));


			if (!$check->valid())
			{
				$data = array(
					'prefix'	=> 'content',
					'regex'		=> $regex,
					'class'		=> 'vB5_Route_Legacy_vBCms',
					'arguments'	=> $arguments,
					'product'	=> 'vbulletin'
				);
				$data['guid'] = vB_Xml_Export_Route::createGUID($data);
				$assertor->insert('routenew', $data);
			}

		}
		else
		{
			$this->skip_message();
		}
	}
}

class vB_Upgrade_510a4 extends vB_Upgrade_Version
{
	/**
	 * Step 1 - Update blog nodes, set displayorder = 1;
	 */
	public function step_1($data = NULL)
	{
		if (empty($data['startat']))
		{
			// display what we're doing if it's the first iteration
			$this->show_message(sprintf($this->phrase['version']['510a4']['update_blog_displayorder']));
		}

		$batchsize = 10000; // takes about 5s per batch on a 500k node DB.

		$startat = intval($data['startat'] ?? 0);

		$assertor = vB::getDbAssertor();
		$blogChannelId = vB_Library::instance('Blog')->getBlogChannel();

		// grab blog nodeids & put them in an array
		$nodeidQry = $assertor->assertQuery('vBInstall:getBlogsWithNullDisplayorder', [
			'blogChannelId' => $blogChannelId,
			'batchsize' => $batchsize,
		]);

		$nodeids = [];
		foreach ($nodeidQry AS $node)
		{
			$nodeids[] = $node['nodeid'];
		}

		// if no nodes are left to update, we're done
		if (count($nodeids) == 0)
		{
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			return;
		}

		// update node table
		$assertor->assertQuery('vBInstall:updateNodesDisplayorder', ['nodeids' => $nodeids]);

		// output progress & return for next batch
		$this->show_message(sprintf($this->phrase['core']['processed_records_x_y'], min($nodeids), max($nodeids)));
		return ['startat' => 1];
	}

	/**
	 * Step 2 - Update social group & sg category nodes, set displayorder = 1;
	 */
	public function step_2($data = NULL)
	{
		if (empty($data['startat']))
		{
			// display what we're doing if it's the first iteration
			$this->show_message(sprintf($this->phrase['version']['510a4']['update_socialgroup_displayorder']));
		}

		$batchsize = 10000; // takes about 5s per batch on a 500k node DB.

		$startat = intval($data['startat'] ?? 0);

		$assertor = vB::getDbAssertor();
		$sgChannelId = vB_Library::instance('node')->getSGChannel();
		// grab SG nodeids & put them in an array. This assumes that any node in the closure table
		// with depth 2 from the social group root channel is a social group.
		// Depth 1 is a SG category, depth 3+ would be discussions
		$nodeidQry = $assertor->assertQuery('vBInstall:getSocialGroupsWithNullDisplayorder', [
			'sgChannelId' => $sgChannelId,
			'batchsize' => $batchsize,
		]);

		$nodeids = [];
		foreach ($nodeidQry AS $node)
		{
			$nodeids[] = $node['nodeid'];
		}

		// if no nodes are left to update, we're done
		if (count($nodeids) == 0)
		{
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			return;
		}

		// update node table
		$assertor->assertQuery('vBInstall:updateNodesDisplayorder', ['nodeids' => $nodeids]);

		// output progress & return for next batch
		$this->show_message(sprintf($this->phrase['core']['processed_records_x_y'], min($nodeids), max($nodeids)));
		return ['startat' => 1];
	}

	/**
	 * Step 3 - Updates filedata records to change refcount from 0 to 1 if the image
	 * is being used as a link preview image (VBV-11243)
	 */
	public function step_3($data = null)
	{
		$batchsize = 500;

		$startat = intval($data['startat'] ?? 0);

		$assertor = vB::getDbAssertor();

		if ($startat == 0)
		{
			$this->show_message(sprintf($this->phrase['version']['510a4']['updating_link_preview_images']));
		}

		// Get filedataids
		// Don't send the startat value to the query. The offset will always be 0
		// because the previous records are now updated and will no longer match.
		$filedataidRes = $assertor->getRows('vBInstall:getLinkPreviewFiledataidsWithRefcountZero', array(
			'batchsize' => $batchsize,
		));
		$filedataids = array();
		foreach ($filedataidRes AS $filedataid)
		{
			$filedataids[] = $filedataid['filedataid'];
		}

		$filedataidCount = count($filedataids);

		if ($filedataidCount > 0)
		{
			// process filedata records
			$assertor->update('filedata', array('refcount' => 1), array('filedataid' => $filedataids));

			// output progress
			$this->show_message(sprintf($this->phrase['core']['processed_records_x_y'], $startat + 1, $startat + $filedataidCount));

			// return for next batch
			// send the calculated startat value for display purposes only
			return array('startat' => $startat + $filedataidCount);
		}
		else
		{
			// done
			$this->show_message(sprintf($this->phrase['core']['process_done']));

			return;
		}
	}
}

class vB_Upgrade_510a5 extends vB_Upgrade_Version
{
	/**
	 * Step 1 - Long step warning for step 2.
	 */
	public function step_1($data = null)
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'usergroup', 1, 1),
			'usergroup',
			'forumpermissions2',
			'int',
			self::FIELD_DEFAULTS
		);

		$this->long_next_step();
	}

	/**
	 * Step 2 - Updates preview image to a valid vB5 value (VBV-11788).
	 */
	public function step_2($data = null)
	{
		$startat = (int) isset($data['startat']) ? $data['startat'] : 0;
		$batchsize = 300;

		$assertor = vB::getDbAssertor();

		if ($startat == 0)
		{
			$this->show_message(sprintf($this->phrase['version']['510a5']['updating_article_preview_images']));
		}

		// Get nodeids
		// don't send startat to the query, since the previous nodes have been modified
		// and will no longer match this query (it always needs to start from offset 0).
		$rows = $assertor->getRows('vBInstall:getNodesWithUrlPreviewImage', array(
			'batchsize' => $batchsize,
		));

		if (empty($rows))
		{
			// done
			$this->show_message(sprintf($this->phrase['core']['process_done']));

			return;
		}
		else
		{
			// process the preview images

			vB_Upgrade::createAdminSession();

			$processedFrom = 0;
			$processedTo = 0;
			$legacyattachmentids = array();

			// Remove any current previewimages in case the legacy attachment is not available
			// or autoPopulatePreviewImage doesn't find one to set
			$nodeids = array();
			foreach ($rows AS $row)
			{
				$nodeids[] = $row['nodeid'];
			}
			$assertor->update('vBForum:text', array('previewimage' => ''), array('nodeid' => $nodeids));
			unset($nodeids);

			foreach ($rows AS $row)
			{
				if (preg_match('/attachment\.php\?attachmentid=(\d+)/i', $row['previewimage'], $match))
				{
					$legacyattachmentids[$row['nodeid']] = (int) $match[1];
				}
				else
				{
					// Handle custom preview image tag for static HTML pages and PHP eval pages/
					// In this case, we will scan the article and auto-assign a preview as we do
					// for all regular articles in vB5.
					vB_Api::instanceInternal('Content_Text')->autoPopulatePreviewImage($row['nodeid']);
				}

				if ($processedFrom == 0)
				{
					$processedFrom = $row['nodeid'];
					$processedTo = $row['nodeid'];
				}
				$processedFrom = min($processedFrom, $row['nodeid']);
				$processedTo = max($processedFrom, $row['nodeid']);
			}

			// get nodeids for the attachments
			if (!empty($legacyattachmentids))
			{
				$legacyattachments = vB_Api::instanceInternal('filedata')->fetchLegacyAttachments(array_values($legacyattachmentids));

				foreach ($legacyattachmentids AS $nodeid => $attachmentid)
				{
					if (isset($legacyattachments[$attachmentid]))
					{
						$legacyattachment = $legacyattachments[$attachmentid];

						/*update query*/
						$assertor->update('vBForum:text', array('previewimage' => $legacyattachment['nodeid']), array('nodeid' => $nodeid));
					}
				}
				unset($legacyattachmentids, $legacyattachments, $legacyattachment);
			}

			// output progress
			$this->show_message(sprintf($this->phrase['core']['processed_records_x_y'], $processedFrom, $processedTo));

			// return for next batch
			return array('startat' => $startat + $batchsize);
		}
	}

	/** Make sure the two article system usergroups exist */
	public function step_3()
	{
		vB_Upgrade::createAdminSession();
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'permission'));
		$this->createSystemGroups();
	}

	/** Set default Article permissions
	*/
	public function step_4()
	{
		// we should only run this once.  If completed this will show as step = 0 because it's the last.
		$check = vB::getDbAssertor()->assertQuery('vBInstall:upgradelog', array('script' => '510a5', 'step' => '0'));

		if ($check->valid())
		{
			$this->skip_message();
		}
		else
		{
			vB_Upgrade::createAdminSession();
			$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'permission'));
			$parsedRaw = vB_Xml_Import::parseFile(DIR . '/includes/xml/bitfield_vbulletin.xml');
			$permBits = [];
			foreach ($parsedRaw['bitfielddefs']['group'] AS $group)
			{
				if ($group['name'] == 'ugp')
				{
					foreach($group['group'] AS $bfgroup)
					{
						if (($bfgroup['name'] == 'forumpermissions2') OR ($bfgroup['name'] == 'forumpermissions') OR
							($bfgroup['name'] == 'createpermissions'))
						{
							$permBits[$bfgroup['name']] = [];
							foreach ($bfgroup['bitfield'] AS $bitfield)
							{
								$permBits[$bfgroup['name']][$bitfield['name']] = intval($bitfield['value']);
							}
						}
					}
				}
			}
			//revoke create from registered users and guests from Articles channel.
			$channel = vB_Library::instance('content_channel')->fetchChannelByGUID(vB_Channel::DEFAULT_ARTICLE_PARENT);
			$groupApi = vB_Api::instanceInternal('usergroup');
			$registered = $groupApi->fetchUsergroupBySystemID(vB_Api_UserGroup::REGISTERED_SYSGROUPID);
			$registered = $registered['usergroupid'];
			$guest = $groupApi->fetchUsergroupBySystemID(vB_Api_UserGroup::UNREGISTERED_SYSGROUPID);
			$guest = $guest['usergroupid'];
			$author = $groupApi->fetchUsergroupBySystemID(vB_Api_UserGroup::CMS_AUTHOR_SYSGROUPID);
			$author = $author['usergroupid'];
			$editor = $groupApi->fetchUsergroupBySystemID(vB_Api_UserGroup::CMS_EDITOR_SYSGROUPID);
			$editor = $editor['usergroupid'];
			$channelPermHandler = vB_ChannelPermission::instance();

			$defaultPerms = vB::getDbAssertor()->getRow('vBForum:permission', ['nodeid' => 1, 'groupid' => $registered]);
			unset($defaultPerms['permissionid']);
			unset($defaultPerms['groupid']);
			unset($defaultPerms['nodeid']);
			$found = [];


			//obsolete bitfield values.  However they were value when the DB was in the current in transit state.
			//and might be used in a future step.
			$canpostattachmentbit = 8192;

			$perms = $defaultPerms;
			$perms['forumpermissions'] |= $permBits['forumpermissions']['canpostnew']  |
				$permBits['forumpermissions']['canseedelnotice'] | $permBits['forumpermissions']['canview'] |
				$permBits['forumpermissions']['canviewthreads'] | $permBits['forumpermissions']['canviewothers'] |
				$permBits['forumpermissions']['canreply'] | $permBits['forumpermissions']['caneditpost'] |
				$permBits['forumpermissions']['cangetattachment'] | $canpostattachmentbit |
				$permBits['forumpermissions']['cantagown'] | $permBits['forumpermissions']['candeletetagown'];
			$perms['forumpermissions2'] |= $permBits['forumpermissions2']['canalwaysview'];
			//but not canpublish
			$perms['forumpermissions2'] &= ~$permBits['forumpermissions2']['canpublish'];
			// and force moderation
			$perms['createpermissions'] |= $permBits['createpermissions']['vbforum_text'] |
				$permBits['createpermissions']['vbforum_gallery'] | $permBits['createpermissions']['vbforum_poll'] |
				$permBits['createpermissions']['vbforum_attach'] | $permBits['createpermissions']['vbforum_photo'] |
				$permBits['createpermissions']['vbforum_video'] | $permBits['createpermissions']['vbforum_link'];
			//Allow to edit own for 365 days.
			//Allow to edit for 365 days.
			$perms['edit_time'] = 365;
			//and save
			$channelPermHandler->setPermissions($channel['nodeid'], $author, $perms);

			$perms = $defaultPerms;
			$perms['forumpermissions'] |= $permBits['forumpermissions']['canpostnew'] |
				$permBits['forumpermissions']['canseedelnotice'] | $permBits['forumpermissions']['canview'] |
				$permBits['forumpermissions']['canviewthreads'] | $permBits['forumpermissions']['canviewothers'] |
				$permBits['forumpermissions']['canreply'] | $permBits['forumpermissions']['caneditpost'] |
				$permBits['forumpermissions']['cangetattachment'] | $canpostattachmentbit |
				$permBits['forumpermissions']['cantagown'] | $permBits['forumpermissions']['candeletetagown'] |
				$permBits['forumpermissions']['caneditpost'];
			$perms['forumpermissions2'] |= $permBits['forumpermissions2']['canalwaysview'];
			$perms['forumpermissions2'] |= $permBits['forumpermissions2']['canalwayspost'];
			$perms['forumpermissions2'] |= $permBits['forumpermissions2']['canpublish'];
			$perms['forumpermissions2'] |= $permBits['forumpermissions2']['caneditothers'];
			$perms['createpermissions'] |= $permBits['createpermissions']['vbforum_text'] |
				$permBits['createpermissions']['vbforum_gallery'] | $permBits['createpermissions']['vbforum_poll'] |
				$permBits['createpermissions']['vbforum_attach'] | $permBits['createpermissions']['vbforum_photo'] |
				$permBits['createpermissions']['vbforum_video'] |$permBits['createpermissions']['vbforum_link'];
			//Allow to edit own for 365 days.
			$perms['edit_time'] = 365;
			//and save
			$channelPermHandler->setPermissions($channel['nodeid'], $editor, $perms);

			$perms = $defaultPerms;
			$perms['forumpermissions'] &= ~$permBits['forumpermissions']['canpostnew'];
			$perms['forumpermissions2'] &= ~$permBits['forumpermissions2']['canpublish'];
			// followforummoderation, 131072 was removed from bitfields in VBV-7734, but still referenced in a few upgrade steps. Hard coding it for now.
			$perms['forumpermissions'] |= $permBits['forumpermissions']['canreply'] | $permBits['forumpermissions']['canview'] |
				$permBits['forumpermissions']['canviewthreads'] | $permBits['forumpermissions']['canviewothers'] |
				$permBits['forumpermissions']['cangetattachment'] | 131072;
			$perms['forumpermissions2'] |= $permBits['forumpermissions2']['cancomment'];
			$channelPermHandler->setPermissions($channel['nodeid'], $registered, $perms);


			$perms = vB::getDbAssertor()->getRow('vBForum:permission', array('nodeid' => $channel['nodeid'], 'groupid' => $guest));

			// if there's no existing channel permission for guests for the article channel, let's take their perms for the root channel
			// there's no fallback for the root channel because I don't think it's possible for that particular permission record to be missing
			if (empty($perms))
			{
				$rootchannel = vB_Library::instance('content_channel')->fetchChannelByGUID(vB_Channel::MAIN_CHANNEL);
				$perms = vB::getDbAssertor()->getRow('vBForum:permission', array('nodeid' => $rootchannel['nodeid'], 'groupid' => $guest));
				unset($perms['permissionid']);
				unset($perms['groupid']);
				unset($perms['nodeid']);
			}

			$perms['forumpermissions'] &= ~$permBits['forumpermissions']['canpostnew'];
			$perms['forumpermissions'] &= ~$permBits['forumpermissions']['canreply'];
			$perms['forumpermissions2'] &= ~$permBits['forumpermissions2']['canpublish'];
			$perms['forumpermissions2'] &= ~$permBits['forumpermissions2']['cancomment'];
			$channelPermHandler->setPermissions($channel['nodeid'], $guest, $perms);
		}
	}
}

class vB_Upgrade_510a6 extends vB_Upgrade_Version
{
	/**Set the moderatepublish flag for article channel */
	public function step_1()
	{
		// we should only run this once.  If completed this will show as step = 0 because it's the last.
		$check = vB::getDbAssertor()->assertQuery('vBInstall:upgradelog', array('script' => '510a6'));

		if ($check->valid())
		{
			$this->skip_message();
		}
		else
		{
			$this->show_message(sprintf($this->phrase['version']['510a6']['updating_article_options']));
			$parsedRaw = vB_Xml_Import::parseFile(DIR . '/includes/xml/bitfield_vbulletin.xml');
			foreach ($parsedRaw['bitfielddefs']['group'] AS $group)
			{
				if ($group['name'] == 'misc')
				{
					foreach($group['group'] AS $bfgroup)
					{
						if (($bfgroup['name'] == 'forumoptions'))
						{
							$optBits = array();
							foreach ($bfgroup['bitfield'] AS $bitfield)
							{
								if ($bitfield['name'] == 'moderatepublish')
								{
									$modPublish = $bitfield['value'];
									break;
								}
							}
						}
					}
				}
			}
			$articleChannel = vB_Library::instance('content_channel')->fetchChannelByGUID(vB_Channel::DEFAULT_ARTICLE_PARENT);
			vB::getDbAssertor()->assertQuery('vBInstall:updateChannelOptions', array('nodeids' => $articleChannel['nodeid'], 'setOption' => $modPublish));
		}
	}
}

class vB_Upgrade_510a7 extends vB_Upgrade_Version
{
	/*
	 *	Step 1 :
	 *	There are 3 possibly dupe page records inserted by 500a1 step_23.
	 * 	The first of these is causing problems, so let's delete it.
	 *	upgrade final's step_8 should fix the route record
	 */
	public function step_1()
	{
		$possibleDupes = array(
			array("pageid" => 1, "parentid" => 0, "routeid" => 9, "guid" => "vbulletin-4ecbdac82ef5d4.12817784"),
			//array("pageid" => 2, "parentid" => 0, "routeid" => 24, "guid" => "vbulletin-52b4c3c6590572.75515897"),
			//array("pageid" => 3, "parentid" => 30, "routeid" => 30, "guid" => "vbulletin-52b4c3c65906c1.50869326"),
		);

		$this->show_message($this->phrase['version']['510a7']['removing_duplicate_page']);

		$importChannels = false;
		foreach($possibleDupes AS $page)
		{
			$dupes = vB::getDbAssertor()->getRows('vBForum:page',
				array(
					'guid' => $page['guid'],
				)
			);

			if (count($dupes) > 1)
			{
				vB::getDbAssertor()->assertQuery('vBForum:page',
					array(
						vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_DELETE,
						'pageid' => $page['pageid'],
						'parentid' => $page['parentid'],
						'routeid' => $page['routeid'],
						'guid' => $page['guid'],
					)
				);
				$importChannels = true;
			}
		}

		$this->final_load_routes();
	}
}

class vB_Upgrade_510a8 extends vB_Upgrade_Version
{
	/*
	 *	Step 1 - Find stray polls whose nodes have been deleted but poll.nodeid haven't been updated
	 *		properly and update those records.
	 *		Most of this step is copied from 503rc1 step_1 since it replicates the portion
	 *		of that step that was skipped due to the typo.
	*/
	public function step_1($data = [])
	{
		if ($this->tableExists('poll') AND $this->tableExists('polloption') AND $this->tableExists('thread'))
		{
			// output what we're doing
			$this->show_message($this->phrase['version']['510a8']['fixing_imported_polls']);

			$assertor = vB::getDbAssertor();
			$batchsize = 500000;
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
			$pollData = $assertor->assertQuery('vBInstall:getStrayPolls',
				array(
					'startat' => $startat,
					'batchsize' => $batchsize,
					'pollcontenttypeid' => vB_Api_ContentType::OLDTYPE_POLL
				)
			);

			if (!$pollData->valid())
			{
				return array('startat' => ($startat + $batchsize), 'maxToFix' => $maxToFix);
			}

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
						vB_dB_Query::CONDITIONS_KEY => array(array('field' => 'pollid', 'value' => $poll['pollid'])),
						'nodeid' => $poll['nodeid'],
						'options' => serialize($options),
						'votes' => $votes
					));

				}
			}

			// output current progress
			$this->show_message(sprintf($this->phrase['core']['processed_records_x_y_z'], $startat + 1, $startat + $batchsize, $maxToFix));

			return array('startat' => ($startat + $batchsize), 'maxToFix' => $maxToFix);
		}
		else
		{
			$this->skip_message();
		}
	}
}

class vB_Upgrade_510a9 extends vB_Upgrade_Version
{
	/**
	* Handle changes to the password history table.
	*/
	public function step_1()
	{
		if (!$this->field_exists('passwordhistory', 'token'))
		{
			//the previous password history is invalid since we started changing the salts.
			//we've fixed that but the existing records are pretty much useless
			$db = vB::getDbAssertor();
			$db->delete('passwordhistory', vB_dB_Query::CONDITION_ALL);

			$this->add_field(
				sprintf($this->phrase['core']['altering_x_table'], 'passwordhistory', 1, 4),
				'passwordhistory',
				'token',
				'varchar',
				array('length' => 255, 'default' => '',)
			);

			$this->add_field(
				sprintf($this->phrase['core']['altering_x_table'], 'passwordhistory', 2, 4),
				'passwordhistory',
				'scheme',
				'varchar',
				array('length' => 100, 'default' => '',)
			);

			$this->drop_field(
				sprintf($this->phrase['core']['altering_x_table'], 'passwordhistory', 3, 4),
				'passwordhistory',
				'password'
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
			sprintf($this->phrase['core']['altering_x_table'], 'passwordhistory', 4, 4),
			"ALTER TABLE " . TABLE_PREFIX . "passwordhistory MODIFY passworddate INT NOT NULL DEFAULT '0'"
		);
	}

	public function step_3()
	{
		if (!$this->field_exists('user', 'token'))
		{
			$this->add_field(
				sprintf($this->phrase['core']['altering_x_table'], 'user', 1, 5),
				'user',
				'token',
				'varchar',
				array('length' => 255, 'default' => '',)
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_4()
	{
		if (!$this->field_exists('user', 'scheme'))
		{
			$this->add_field(
				sprintf($this->phrase['core']['altering_x_table'], 'user', 2, 5),
				'user',
				'scheme',
				'varchar',
				array('length' => 100, 'default' => '',)
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_5()
	{
		if (!$this->field_exists('user', 'secret'))
		{
			$this->add_field(
				sprintf($this->phrase['core']['altering_x_table'], 'user', 3, 5),
				'user',
				'secret',
				'varchar',
				array('length' => 100, 'default' => '',)
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_6()
	{
		if ($this->field_exists('user', 'password'))
		{
			$this->show_message($this->phrase['version']['510a9']['updating_password_schemes']);
			$assertor = vB::getDbAssertor();
			$assertor->update('user', array('scheme' => 'legacy'), vB_dB_Query::CONDITION_ALL);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_7()
	{
		if ($this->field_exists('user', 'password'))
		{
			$this->show_message($this->phrase['version']['510a9']['updating_password_tokens']);
			$assertor = vB::getDbAssertor();
			$assertor->assertQuery('vBInstall:updatePasswordTokenAndSecret');
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_8()
	{
		if ($this->field_exists('user', 'password'))
		{
			$this->drop_field(
				sprintf($this->phrase['core']['altering_x_table'], 'user', 4, 5),
				'user',
				'password'
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_9()
	{
		if ($this->field_exists('user', 'salt'))
		{
			$this->drop_field(
				sprintf($this->phrase['core']['altering_x_table'], 'user', 5, 5),
				'user',
				'salt'
			);
		}
		else
		{
			$this->skip_message();
		}
	}
}

class vB_Upgrade_510b2 extends vB_Upgrade_Version
{
	/**
	 *	Step 1:	Remove "canremoveposts" (physical delete) moderator permission from channel permissions
	 *		for CHANNEL_MODERATORS
	 */
	public function step_1($data = NULL)
	{
		$assertor = vB::getDbAssertor();
		// we should only run this once.
		$check = $assertor->assertQuery('vBInstall:upgradelog', array('script' => $this->SHORT_VERSION, 'step' => '1'));

		if ($check->valid())
		{
			$this->skip_message();
		}
		else
		{
			$this->show_message($this->phrase['version'][$this->SHORT_VERSION]['updating_channelmod_permissions']);

			vB_Upgrade::createAdminSession();
			$channelmods = vB_Api::instanceInternal('usergroup')->fetchUsergroupBySystemID(vB_Api_UserGroup::CHANNEL_MODERATOR_SYSGROUPID);
			$assertor = vB::getDbAssertor();
			$assertor->assertQuery('vBInstall:unsetChannelModeratorPermissionCanremoveposts',
				array('channel_moderators_usergroupid' => $channelmods['usergroupid'])
			);
		}
	}

	/*
	 *	Step 2: Current unused, required here because step_1 uses a upgradelog check, and thus cannot be the
	 *		last step.
	 */
	public function step_2($data = NULL)
	{
		// There's a bug where the last step of a script is always recorded with step = 0. ANY step that uses an
		// upgradelog check to run only once CANNOT be the last step in the script. As such, this is just a filler.
		$this->skip_message();
	}
}

class vB_Upgrade_511a3 extends vB_Upgrade_Version
{
	/**
	 * Step 1
	 */
	public function step_1()
	{
		$this->long_next_step();
	}

	/**
	 * Step 2 - Change nodeoptions to INT
	 */
	public function step_2()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'node', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "node
			CHANGE nodeoptions nodeoptions INT UNSIGNED NOT NULL DEFAULT '138'"
		);
	}
}

class vB_Upgrade_511a5 extends vB_Upgrade_Version
{
	/**
	 * Make sure the bitfields are up-to-date (for next step)
	 */
	public function step_1()
	{
		vB_Upgrade::createAdminSession();
		require_once(DIR . '/includes/class_bitfield_builder.php');
		vB_Bitfield_Builder::save();
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table'], 'permission'));
	}

	/**
	 * Ensure that the new 'cangetimgattachment' setting matches the
	 * value of 'cangetattachment' for all channels.
	 */
	public function step_2()
	{
		// Get the bitfields
		$forumpermissions = vB::getDatastore()->getValue('bf_ugp_forumpermissions');
		$forumpermissions2 = vB::getDatastore()->getValue('bf_ugp_forumpermissions2');

		// Set the new 'cangetimageattachment' permission.
		// Everyone who has the 'cangetattachment' permission gets this
		// new one when upgrading.
		$this->run_query(sprintf($this->phrase['vbphrase']['update_table'], 'permission'), "
			UPDATE " . TABLE_PREFIX . "permission
			SET forumpermissions2 = forumpermissions2 | " . intval($forumpermissions2['cangetimgattachment']) . "
			WHERE forumpermissions & " . intval($forumpermissions['cangetattachment']) . "
		");
	}
}

class vB_Upgrade_511a7 extends vB_Upgrade_Version
{
	/**
	 * Step 1 :
	 *		Add 'subscription' notification types to about in privatemessage
	 */
	public function step_1()
	{
		if ($this->field_exists('privatemessage', 'about'))
		{
			$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'privatemessage', 1, 1),
				"ALTER TABLE " . TABLE_PREFIX . "privatemessage MODIFY COLUMN about ENUM(
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
					'" . vB_Api_Node::REQUEST_SG_TAKE_MEMBER . "'
				);"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	 * Change useragent from 100 to 255 chars (Basically replicates 4.2.3 Beta 4 Step 2)
	 */
	public function step_2()
	{
		if ($this->field_exists('session', 'useragent'))
		{
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'session', 1, 1),
				"ALTER TABLE " . TABLE_PREFIX . "session CHANGE useragent useragent CHAR(255) NOT NULL DEFAULT ''"
			);
		}
		else
		{
			$this->skip_message();
		}
	}
}

class vB_Upgrade_511a8 extends vB_Upgrade_Version
{
	/**
	 *	Set the bitfield forumpermissions.canattachmentcss (restored from vB4) for
	 *	admin & super moderator usergroups, only for upgrades from vB5
	 *	(this step was copied & modified from 511a5 step_1 & step_2)
	 */
	public function step_1()
	{
		// only run this once.
		$log = vB::getDbAssertor()->assertQuery('vBInstall:upgradelog', array('script' => '511a8', 'step' => 1));
		if ($log->valid())
		{
			$this->skip_message();
			return;
		}

		// Set the restored 'canattachmentcss' permission for vB5 to vB5.1.1 upgrades.
		// Since it occupies the same bitfield as in vB4, this shouldn't be done for upgrades from vB4
		// I'm making an assumption here that if this forum ran the 500a1 upgrade, then it upgraded
		// from vB4 (since a new vB5 install wouldn't have had to run the 500 upgrade steps). Of course,
		// the user could've manually ran the 500 steps for fun, but in that case they can manually set
		// the permissions :)
		$log = vB::getDbAssertor()->assertQuery('vBInstall:upgradelog', array('script' => '500a1'));
		if ($log->valid())
		{
			$this->skip_message();
			return;
		}

		// First make sure that the bitfields are up to date
		vB_Upgrade::createAdminSession();
		require_once(DIR . '/includes/class_bitfield_builder.php');
		$saveSuccess  = vB_Bitfield_Builder::save();

		// Get the bitfields
		$forumpermissions = vB::getDatastore()->getValue('bf_ugp_forumpermissions');

		// grab groups 5 & 6 (Administrators & Super Mods)
		$groupApi = vB_Api::instanceInternal('usergroup');
		$admins = $groupApi->fetchUsergroupBySystemID(vB_Api_UserGroup::ADMINISTRATOR);
		$admins = $admins['usergroupid'];
		$supermods = $groupApi->fetchUsergroupBySystemID(vB_Api_UserGroup::SUPER_MODERATOR);
		$supermods = $supermods['usergroupid'];

		// These groups should get the permission set by default.
		$this->run_query(sprintf($this->phrase['vbphrase']['update_table'], 'permission'), "
			UPDATE " . TABLE_PREFIX . "permission
			SET forumpermissions = (forumpermissions | " . intval($forumpermissions['canattachmentcss']) . ")
			WHERE groupid IN ($admins, $supermods)
		");
	}

	/*
	 *	Step 2: Currently unused, it's here because previous step uses a upgradelog check,
	 *		and we don't want it to be the last step to avoid confusion in case steps are
	 *		added or removed.
	 */
	public function step_2($data = NULL)
	{
		// There's a bug/intended-feature where the last step of a script is always recorded with step = 0.
		// ANY step that uses an upgradelog check to run only once either should NOT be the last step in
		// the script OR must remember to check for step = 0 instead of its real step and be careful about
		// maintenance when another step is added afterwards. To reduce maintenance, let's just keep an empty
		// step at the end.
		$this->skip_message();
		/*
						 __		   					 ___________  ________
					___./ /     _.---.				|		  	| \_   __/
					\__  (__..-`       \			|	^		|  /  /
					   \            O   |			|____		|_/	 /
						`..__.   ,=====/			|_______________/
						  `._/_.'_____/

		 * IF ANOTHER STEP IS ADDED, PLEASE REPLACE THIS ONE. HOWEVER, ADD A NOTE ON THAT STEP THAT
		 * IF THE STEP IS TO BE REMOVED, A BLANK ONE SHOULD BE INSERTED AGAIN.
		 */
	}
}

class vB_Upgrade_511a9 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'rssfeed', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "rssfeed MODIFY COLUMN nodeid INT UNSIGNED NOT NULL DEFAULT '0'"
		);
	}

	public function step_2()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'moderator', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "moderator MODIFY COLUMN nodeid INT NOT NULL DEFAULT '0'"
		);
	}

	/**
	 * Add the usergroup.forumpermissions2 column
	 */
	public function step_3()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'usergroup', 1, 1),
			'usergroup',
			'forumpermissions2',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	/**
	 * Make sure the bitfields are up-to-date (for next step)
	 */
	public function step_4()
	{
		vB_Upgrade::createAdminSession();
		require_once(DIR . '/includes/class_bitfield_builder.php');
		vB_Bitfield_Builder::save();
		$this->show_message(sprintf($this->phrase['core']['rebuild_x_datastore'], 'bitfields'));
	}

	/**
	 * Ensure that the new 'cangetimgattachment' setting matches the
	 * value of 'cangetattachment' for all usergroups (this was done
	 * for permission records in the 511a5 upgrade)
	 */
	public function step_5()
	{
		// Get the bitfields
		$forumpermissions = vB::getDatastore()->getValue('bf_ugp_forumpermissions');
		$forumpermissions2 = vB::getDatastore()->getValue('bf_ugp_forumpermissions2');

		// Set the new 'cangetimageattachment' permission.
		// Everyone who has the 'cangetattachment' permission gets this
		// new one when upgrading.
		$this->run_query(
			sprintf($this->phrase['vbphrase']['update_table_x'], TABLE_PREFIX . 'usergroup', 1, 2),
			"
				UPDATE " . TABLE_PREFIX . "usergroup
				SET forumpermissions2 = forumpermissions2 | " . intval($forumpermissions2['cangetimgattachment']) . "
				WHERE forumpermissions & " . intval($forumpermissions['cangetattachment']) . "
			"
		);
	}

	public function step_6()
	{
		$db = vB::getDbAssertor();

		$this->show_message(sprintf($this->phrase['vbphrase']['update_table_x'], TABLE_PREFIX . 'usergroup', 2, 2));

		$groupinfo = $this->getDefaultGroupPerms();
		foreach($groupinfo AS $id => $perms)
		{
			//so the defaults in the bitfields are messed up for groups that aren't the
			//original eight that we've always had (not only do we not have them set we
			//can't rely on the usergroupid to be same across installs.
			//The added groups should already have the correct privs so skip them
			if ($id > 8)
			{
				continue;
			}

			$db->update('usergroup', array('forumpermissions2' => $perms['forumpermissions2']), array('usergroupid' => $id));
		}
	}
}

class vB_Upgrade_512a2 extends vB_Upgrade_Version
{
	/**
	 *	Steps 1 & 2 :
	 *		Add the style.guid & style.filedataid columns
	 */
	public function step_1()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'style', 1, 1),
			'style',
			'guid',
			'char',
			array('null' => true, 'length' => 150, 'default' => null, 'extra' => 'UNIQUE')
		);
	}

	public function step_2()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'style', 1, 1),
			'style',
			'filedataid',
			'int',
			self::FIELD_DEFAULTS
		);
	}

	public function step_3()
	{
		// Create textonly field
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'template', 1, 1),
			'template',
			'textonly',
			'smallint',
			self::FIELD_DEFAULTS
		);
	}
}

class vB_Upgrade_512a3 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->show_message($this->phrase['version'][$this->SHORT_VERSION]['updating_datastore_special_templates']);
		//changed the format of this field so we need to rebuild the datastore value.
		vB_Library::instance('template')->rebuildTextonlyDS();
	}

	/**
	 * Step 2: Add the previewfiledataid column on the style table
	 */
	public function step_2()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'style', 1, 1),
			'style',
			'previewfiledataid',
			'int',
			self::FIELD_DEFAULTS
		);
	}
}

class vB_Upgrade_512a4 extends vB_Upgrade_Version
{
	/**
	 * Correct the subnav bar to use the new search json if it has not been edited.
	 */
	public function step_1()
	{
		$assertor = vB::getDbAssertor();
		$this->show_message($this->phrase['version']['512a4']['updating_todays_post_url']);
		$sites = $assertor->assertQuery('vBForum:site');
		foreach ($sites AS $site)
		{
			$changed = false;
			$header = unserialize($site['headernavbar']);
			if (!empty($header))
			{
				foreach ($header as $key => $h)
				{
					if ($h['title'] == 'navbar_home')
					{
						foreach ($h['subnav'] AS $subKey => $subnav)
						{
							if ($subnav['title'] == 'navbar_todays_posts' AND !empty($subnav['url']))
							{
								//Found the correct item.  See if it has been edited.
								if ($subnav['url'] == 'search?searchJSON=%7B%22date%22%3A%7B%22from%22%3A%22lastDay%22%7D%2C%22view%22%3A%22topic%22%2C%22sort%22%3A%7B%22lastcontent%22%3A%22desc%22%7D%2C%22exclude_type%22%3A%5B%22vBForum_PrivateMessage%22%5D%7D')
								{
									$header[$key]['subnav'][$subKey]['url'] = 'search?searchJSON=%7B%22last%22%3A%7B%22from%22%3A%22lastDay%22%7D%2C%22view%22%3A%22topic%22%2C%22starter_only%22%3A+1%2C%22sort%22%3A%7B%22lastcontent%22%3A%22desc%22%7D%2C%22exclude_type%22%3A%5B%22vBForum_PrivateMessage%22%5D%7D';
									$changed = true;
									break;
								}
								else
								{
									return;
								}
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

class vB_Upgrade_512a5 extends vB_Upgrade_Version
{
	// VBV-1375 -- remove unwanted widget
	public function step_1()
	{
		// widget guid: vbulletin-widget_groupnodes-4eb423cfd6dea7.34930864
		// template: widget_sgnodes
		// title phrase: widget_sgnodes_widgettitle

		$assertor = vB::getDbAssertor();

		$widgetid = $assertor->getRow('widget', array('guid' => 'vbulletin-widget_groupnodes-4eb423cfd6dea7.34930864'));
		if ($widgetid)
		{
			$this->show_message($this->phrase['version'][$this->SHORT_VERSION]['removing_unused_widget']);

			$widgetid = $widgetid['widgetid'];

			// delete widget instances
			$assertor->delete('widgetinstance', array('widgetid' => $widgetid));

			// delete the widget
			$assertor->delete('widget', array('guid' => 'vbulletin-widget_groupnodes-4eb423cfd6dea7.34930864'));

			// delete the widget's template
			$assertor->delete('template', array('title' => 'widget_sgnodes'));

			// delete the widget title phrase
			$assertor->delete('phrase', array('varname' => 'widget_sgnodes_widgettitle'));

			$assertor->delete('widgetdefinition', array('widgetid' => $widgetid));
		}
		else
		{
			$this->skip_message();
		}
	}
}

class vB_Upgrade_513a1 extends vB_Upgrade_Version
{
	public function step_1()
	{
		if ($this->tableExists('album'))
		{
			$Types = vB_Types::instance();
			$albumType = $Types->getContentTypeID('vBForum_Album');
			$galleryType = $Types->getContentTypeID('vBForum_Gallery');
			$photoType = $Types->getContentTypeID('vBForum_Photo');

			$this->show_message(sprintf($this->phrase['version']['513a1']['setting_private_albums_from_4'], 'forum'));
			vB::getDbAssertor()->assertQuery('vBInstall:setPrivateAlbums', array('albumtype' => $albumType, 'gallerytype' => $galleryType, 'phototype' => $photoType));
		}
		else
		{
			$this->skip_message();
		}
	}

	public function step_2()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'hook', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "hook MODIFY COLUMN hookname  varchar(100)"
		);
	}
}

class vB_Upgrade_513a2 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->show_message($this->phrase['version']['513a2']['setting_page_types']);
		$parser = new vB_XML_Parser(false, DIR . '/install/vbulletin-pages.xml');
		$pages = $parser->parse();
		$pages = array_pop($pages);
		$guids = array();
		foreach($pages AS $page)
		{
			if ($page['pagetype'] == 'default')
			{
				$guids[] = $page['guid'];
			}
		}
		vB::getDbAssertor()->assertQuery('page', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
			vB_dB_Query::CONDITIONS_KEY => array('guid' => $guids), 'pagetype' => 'default'));
	}

	public function step_2()
	{
		$this->show_message($this->phrase['version'][$this->SHORT_VERSION]['removing_orphaned_subscription_records']);
		vB::getDbAssertor()->assertQuery('vBInstall:deleteOrphanedSubscriptionRecords');
	}

	public function step_3()
	{
		$this->show_message($this->phrase['version']['513a2']['preparing_routenew_table_for_adding_index']);
		//If there are empty string names update them to NULL
		$assertor = vB::getDbAssertor();
		$assertor->assertQuery('vBInstall:setEmptyStringsToNullRoutenew');

		// find all duplicates and get the lowest routeid for each
		$duplicates = $assertor->assertQuery('vBInstall:findDuplicateRouteNames');

		$can_add_index=true;
		//loop trough each duplicates name
		foreach($duplicates AS $duplicate)
		{
			// Get the record with the lowest routeid for this duplicate
			$record_min_routeid = $assertor->getRow('routenew', array(
				vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
				vB_dB_Query::CONDITIONS_KEY => array(
					array('field' => 'name', 'value' => $duplicate['name'], 'operator' =>  vB_dB_Query::OPERATOR_EQ),
					array('field' => 'routeid', 'value' => $duplicate['min_routeid'], 'operator' =>  vB_dB_Query::OPERATOR_EQ)
					)
				)
			);
			//get all records for current duplicate name other than the one with the lowest routeid
			$current_duplicate_records = $assertor->getRows('routenew', array(
				vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
				vB_dB_Query::CONDITIONS_KEY => array(
					array('field' => 'name', 'value' => $duplicate['name'], 'operator' =>  vB_dB_Query::OPERATOR_EQ),
					array('field' => 'routeid', 'value' => $duplicate['min_routeid'], 'operator' =>  vB_dB_Query::OPERATOR_NE)
					)
				)
			);

			//loop trough each duplicate record
			foreach ($current_duplicate_records as $record)
			{
				// check if it is a complete duplicate and delete if so
				$complate_duplicate = true;
				foreach ($record as $key=>$value)
				{

					if ($key != 'routeid' AND $value != $record_min_routeid[$key])
					{
						$complate_duplicate = false;
						$can_add_index =  false;
					}
				}
				if ($complate_duplicate)
				{
					//update page table
					$assertor->assertQuery('page', array(
						vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
						vB_dB_Query::CONDITIONS_KEY => array(
							array('field' => 'routeid', 'value' => $record['routeid'], vB_dB_Query::OPERATOR_KEY => vB_dB_Query::OPERATOR_EQ)
						),
						'routeid' => $duplicate['min_routeid']
					));
					//update node table
					$assertor->assertQuery('vBForum:node', array(
						vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
						vB_dB_Query::CONDITIONS_KEY => array(
							array('field' => 'routeid', 'value' => $record['routeid'], vB_dB_Query::OPERATOR_KEY => vB_dB_Query::OPERATOR_EQ)
						),
						'routeid' => $duplicate['min_routeid']
					));
					//delete the route record
					$assertor->assertQuery('routenew', array(
						vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_DELETE, 'routeid' => $record['routeid']
					));
				}
			}
		}
		if ($can_add_index)
		{
			$this->drop_index(
				sprintf($this->phrase['core']['altering_x_table'], 'routenew', 1, 2),
				'routenew',
				'route_name'
			);
			$this->add_index(
				sprintf($this->phrase['core']['altering_x_table'], 'routenew', 2, 2),
				'routenew',
				'route_name',
				array('name'),
				'unique'
			);
		}
		else
		{
			$this->add_adminmessage($this->phrase['version']['513a2']['cannot_add_routenew_index'],array());
		}
	}
}

class vB_Upgrade_513b1 extends vB_Upgrade_Version
{
	/**
	 * 	Reset publicview for filedata records that shouldn't be public
	 */
	public function step_1()
	{
		$this->show_message($this->phrase['version']['513b1']['fix_publicview_attach']);
		/*
		 *	Tried on a test DB that had 4k filedata & attach record pairs and the update didn't
		 *	more than a couple seconds at most, so I didn't batch this.
		 *	I did not add the queries to the vbinstall package to make it simpler for support to
		 *	upload this as a single script.
		 */
		$assertor = vB::getDbAssertor();
		$logos = $assertor->assertQuery('stylevar', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT, 'stylevarid' => 'titleimage'));
		$logoFiledataids = array();
		foreach ($logos AS $logo)
		{
			$value = unserialize($logo['value']);
			if (!empty($value['url']) )
			{
				if (preg_match('#^filedata/fetch\?filedataid=(?P<filedataid>[0-9]+)$#i', $value['url'], $matches))
				{
					$filedataid = (int)$matches['filedataid'];
					$logoFiledataids[$filedataid] = $filedataid;
				}
			}
		}

		if (!empty($logoFiledataids))
		{
			$assertor->assertQuery('vBInstall:fixAttachPublicviewSkipFiledataids',
				array(
					'skipfiledataids' => $logoFiledataids
				)
			);
		}
		else
		{
			$assertor->assertQuery('vBInstall:fixAttachPublicview');
		}
	}

	/**
	 * 	Reset publicview for filedata records that have 0 refcount, in case any
	 *	attachments were removed and filedata record was missed by step_1
	 */
	public function step_2()
	{
		$this->show_message($this->phrase['version']['513b1']['fix_publicview_unref']);
		$assertor = vB::getDbAssertor();
		$assertor->assertQuery('vBInstall:fixUnreferencedFiledataPublicview');

		$this->long_next_step();
	}

	/**
	 * Fix any attachments that are stored incorrectly in the fs. VBV-13339
	 */
	public function step_3()
	{
		$vboptions = vB::getDatastore()->getValue('options');

		if ($vboptions['attachfile'] == vB_Library_Filedata::ATTACH_AS_FILES_NEW AND file_exists($vboptions['attachpath']))
		{
			$this->show_message($this->phrase['version']['513b1']['move_attachment_files']);

			$filedataLib = vB_Library::instance('filedata');
			$count = 0;

			foreach (new DirectoryIterator($vboptions['attachpath']) AS $fileinfo)
			{
				if (!$fileinfo->isDir())
				{
					continue;
				}

				$filename = $fileinfo->getFilename();

				if (!preg_match('/^[0-9][0-9]+$/', $filename))
				{
					continue;
				}

				// If we reach this point, these attachments are stored as
				// ATTACH_AS_FILES_OLD instead of ATTACH_AS_FILES_NEW.
				// The directory name is the userid
				$newpath = $filedataLib->fetchAttachmentPath($filename, vB_Library_Filedata::ATTACH_AS_FILES_NEW);

				// Move attachments to new location
				foreach (new DirectoryIterator($fileinfo->getPathname()) AS $fileinfo2)
				{
					if (!$fileinfo2->isFile())
					{
						continue;
					}

					if (preg_match('/^([0-9]+)\.?(attach|icon|thumb|small|medium|large)$/', $fileinfo2->getFilename(), $matches))
					{
						// 1 - filedataid
						// 2 - extension/type

						$currentFile = $fileinfo2->getPathname();
						$targetFile = $newpath . '/' . $matches[1] . '.' . $matches[2];

						// If target is an existing file with different filesize, don't attempt the rename
						if (!file_exists($targetFile) OR filesize($targetFile) == filesize($currentFile))
						{
							rename($currentFile, $targetFile);
							++$count;
						}
					}
				}

				// attempt to remove the directory, if empty
				@rmdir($fileinfo->getPathname());
			}

			if ($count > 0)
			{
				$this->show_message(sprintf($this->phrase['version']['513b1']['x_attachment_files_moved'], $count));
			}
			else
			{
				$this->show_message($this->phrase['version']['513b1']['no_attachment_files_to_move']);
			}
		}
		else
		{
			$this->skip_message();
		}

		$this->long_next_step();
	}

	/**
	 * Insert a preview image where missing (VBV-11329)
	 */
	public function step_4($data = null)
	{
		$startat = (int) isset($data['startat']) ? $data['startat'] : 0;
		$batchsize = 100;

		$assertor = vB::getDbAssertor();
		vB_Upgrade::createAdminSession();

		if ($startat == 0)
		{
			$this->show_message(sprintf($this->phrase['version']['510a5']['updating_article_preview_images']));
		}

		$attachContentTypeId =  vB_Types::instance()->getContentTypeID('vBForum_Attach');
		$articlesRootChannelId = vB_Api::instanceInternal('Content_Channel')->fetchChannelIdByGUID(vB_Channel::DEFAULT_ARTICLE_PARENT);

		// Get nodeids
		$rows = $assertor->assertQuery('vBInstall:getNodesWithMissingPreviewImage', array(
			'root_article_channel' => $articlesRootChannelId,
			'attach_contenttypeid' => $attachContentTypeId,
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
				vB_Api::instanceInternal('Content_Text')->autoPopulatePreviewImage($nodeid);
			}

			$firstNodeId = min($nodeids);
			$lastNodeId = max($nodeids);

			// output progress
			$this->show_message(sprintf($this->phrase['core']['processed_records_x_y'], $firstNodeId, $lastNodeId));

			// return for next batch
			return array('startat' => $lastNodeId);
		}
	}

}

class vB_Upgrade_514a1 extends vB_Upgrade_Version
{
	/**
	 * Fix the who's online page so it shows everyone who's online instead of just the viewing user
	 */
	public function step_1()
	{
		$this->run_query(
		sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . 'widgetinstance'),
		"
		UPDATE " . TABLE_PREFIX . "widgetinstance
			SET adminconfig = REPLACE(adminconfig, 's:12:\"thisPageOnly\";s:1:\"1\";', 's:12:\"thisPageOnly\";s:1:\"0\";')
			WHERE widgetid = (
				SELECT widgetid
				FROM " . TABLE_PREFIX . "widget
				WHERE guid = 'vbulletin-widget_12-4eb423cfd6b362.34901422'
			)
			"
		);
	}
}

class vB_Upgrade_514a2 extends vB_Upgrade_Version
{
	/**
	 * Update the Who's Online page to use the full width layout
	 */
	public function step_1()
	{
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'pagetemplate', 1, 1),
			"UPDATE " . TABLE_PREFIX . "pagetemplate
			SET screenlayoutid = 1
			WHERE guid = 'vbulletin-4ecbdac93721f3.19350821' AND screenlayoutid = 2"
		);
	}

	/**
	 * Add guid column to screenlayout
	 */
	public function step_2()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'screenlayout', 1, 6),
			'screenlayout',
			'guid',
			'varchar',
			array('length' => 150, 'null' => true, 'default' => null, 'attributes' => self::FIELD_DEFAULTS)
		);
	}

	/**
	 * Add unique index on screenlayout.guid
	 */
	public function step_3()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'screenlayout', 2, 6),
			'screenlayout',
			'guid',
			'guid',
			'unique'
		);
	}

	/**
	 * Add unique index on screenlayout.varname
	 */
	public function step_4()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'screenlayout', 3, 6),
			'screenlayout',
			'varname',
			'varname',
			'unique'
		);
	}

	/**
	 * Add standard GUIDs for the 3 default screenlayouts we've had up to now
	 */
	public function step_5()
	{
		$items = array(
			1 => 'vbulletin-screenlayout-full-ef8c99cab374d2.91030970',
			2 => 'vbulletin-screenlayout-wide-narrow-ef8c99cab374d2.91030971',
			4 => 'vbulletin-screenlayout-narrow-wide-ef8c99cab374d2.91030972',
		);

		$index = 4;
		foreach ($items AS $screenlayoutid => $guid)
		{
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'screenlayout', $index, 6),
				"UPDATE " . TABLE_PREFIX . "screenlayout
				SET guid = '" . $this->db->escape_string($guid) . "'
				WHERE screenlayoutid = " . intval($screenlayoutid) . "
				"
			);
			++$index;
		}
	}

	/**
	 * Rename the screenlayout templates
	 *
	 * Note: the template names in the screenlayout records will be updated
	 * automatically in final upgrade.
	 */
	public function step_6()
	{
		$items = [
			'screenlayout_1'       => 'screenlayout_display_full',
			'screenlayout_2'       => 'screenlayout_display_wide_narrow',
			'screenlayout_4'       => 'screenlayout_display_narrow_wide',
			'admin_screenlayout_1' => 'screenlayout_admin_full',
			'admin_screenlayout_2' => 'screenlayout_admin_wide_narrow',
			'admin_screenlayout_4' => 'screenlayout_admin_narrow_wide',
		];

		$this->rename_templates($items);
	}
}

class vB_Upgrade_514a4 extends vB_Upgrade_Version
{
	/**
	 * VBV-13464 (VBV-3594)
	 * Update the bitfield value for email notifications in the default
	 * user registration settings.
	 */
	public function step_1()
	{
		// if 'subscribe_none' is set, then remove it and set 'emailnotification_none'
		// 512 was 'subscribe_none', is now 'autosubscribe'
		// 1024 was 'subscribe_nonotify', is now 'emailnotification_none'
		// subscribe_none used to be the default, and emailnotification_none is now the default

		$regoptions = vB::getDatastore()->getValue('bf_misc_regoptions');
		$existing = vB::getDbAssertor()->getRow('setting', array('varname' => 'defaultregoptions'));

		if ($existing['value'] & $regoptions['autosubscribe'])
		{
			// remove 'subscribe_none' (renamed to 'autosubscribe')
			$this->run_query(
				sprintf($this->phrase['vbphrase']['update_table_x'], TABLE_PREFIX . 'setting', 1, 2),
				"UPDATE " . TABLE_PREFIX . "setting
				SET value = value & ~" . intval($regoptions['autosubscribe']) . "
				WHERE varname = 'defaultregoptions'"
			);

			// add 'emailnotification_none'
			$this->run_query(
				sprintf($this->phrase['vbphrase']['update_table_x'], TABLE_PREFIX . 'setting', 2, 2),
				"UPDATE " . TABLE_PREFIX . "setting
				SET value = value | " . intval($regoptions['emailnotification_none']) . "
				WHERE varname = 'defaultregoptions'"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	 * VBV-13449 (VBV-3594)
	 * Rename autosubscribe to emailnotification
	 */
	public function step_2()
	{
		if ($this->field_exists('user', 'autosubscribe') AND !$this->field_exists('user', 'emailnotification'))
		{
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'user', 1, 1),
				"ALTER TABLE " . TABLE_PREFIX . "user CHANGE autosubscribe emailnotification SMALLINT UNSIGNED NOT NULL DEFAULT '0'"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	 * VBV-13451 (VBV-3594)
	 * Add (new) autosubscribe column
	 */
	public function step_3()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'user', 1, 1),
			'user',
			'autosubscribe',
			'smallint',
			self::FIELD_DEFAULTS
		);
	}
}

class vB_Upgrade_514a7 extends vB_Upgrade_Version
{
	/**
	 * Add the nodeid as one of the arguments to all channel routes that are missing it.
	 */
	public function step_1()
	{
		$assertor = vB::getDbAssertor();

		$routes = $assertor->assertQuery('routenew', array(
			vB_dB_Query::CONDITIONS_KEY => array(
				'class' => 'vB5_Route_Channel',
			),
		));

		$updated = false;
		foreach ($routes AS $route)
		{
			$args = unserialize($route['arguments']);

			if (!$args OR !empty($args['nodeid']))
			{
				continue;
			}

			// add nodeid
			$args['nodeid'] = $args['channelid'];

			$values = array('arguments' => serialize($args));
			$conditions = array('routeid' => $route['routeid']);
			$assertor->update('routenew', $values, $conditions);

			$updated = true;
		}

		if ($updated)
		{
			$this->show_message($this->phrase['version']['514a7']['fixing_channel_routes_missing_nodeid']);
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	 * Add contentpagenum as one of the arguments to all conversation routes that are missing it.
	 */
	public function step_2()
	{
		$assertor = vB::getDbAssertor();

		$routes = $assertor->assertQuery('routenew', array(
			vB_dB_Query::CONDITIONS_KEY => array(
				'class' => 'vB5_Route_Conversation',
			),
		));

		$updated = false;
		foreach ($routes AS $route)
		{
			$args = unserialize($route['arguments']);

			if (!$args OR !empty($args['contentpagenum']))
			{
				continue;
			}

			// don't mess with the regex for custom URLs
			if (!empty($args['customUrl']))
			{
				continue;
			}

			// add contentpagenum to arguments
			$args['contentpagenum'] = '$contentpagenum';

			// update regex to include contentpagenum
			$regex = preg_quote($route['prefix']) . '/' . vB5_Route_Conversation::REGEXP;

			// do update
			$values = array('arguments' => serialize($args));
			$conditions = array(
				'routeid' => $route['routeid'],
				'regex' => $regex,
			);
			$assertor->update('routenew', $values, $conditions);

			$updated = true;
		}

		if ($updated)
		{
			$this->show_message($this->phrase['version']['514a7']['fixing_conversation_routes_missing_contentpagenum']);
		}
		else
		{
			$this->skip_message();
		}
	}
}

class vB_Upgrade_514b3 extends vB_Upgrade_Version
{
	/**
	* Step #1 - Import screenlayoutids. This is required before step_2(). VBV-13771
	*
	*/
	public function step_1()
	{
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'screenlayout', 1, 1),
			'screenlayout',
			'sectiondata',
			'text',
			['null' => false, 'default' => '']
		);

		$this->execute();

		vB_Library::clearCache();
		$this->final_load_screenlayouts();
	}

	/**
	* Step #2 - Fix pagetemplate records with missing screenlayoutids. VBV-13771
	*
	*/
	public function step_2()
	{
		$assertor = vB::getDbAssertor();

		$fixThese = $assertor->getRows('pagetemplate', ['screenlayoutid' => 0]);

		if (empty($fixThese))
		{
			$this->skip_message();
			return;
		}

		$this->show_message($this->phrase['version']['514b3']['fixing_pagetemplate_missing_screenlayoutid']);

		// If we're in debug mode, this step will throw errors or output notices if broken database records are found that cannot
		// be fixed by this step. The point is to catch any errors pagetemplate add/update/delete in development.
		$config = vB::getConfig();

		// Let's go through the pagetemplates file & grab the GUIDs, so we don't have to make a hard-coded list that might need
		// to be updated manually in the future.
		$pageTemplateFile = DIR . '/install/vbulletin-pagetemplates.xml';
		if (!($xml = file_read($pageTemplateFile)))
		{
			$this->add_error(sprintf($this->phrase['vbphrase']['file_not_found'], 'vbulletin-pagetemplates.xml'), self::PHP_TRIGGER_ERROR, true);
			return;
		}
		$pageTemplateParsed = vB_Xml_Import::parseFile($pageTemplateFile);

		// Translates pagetemplate's GUID to screenlayout's GUID
		$pagetemplateGuidToScreenlayoutGuid = array();
		// Hold all unique screenlayoutguids so that we can fetch their screenlayoutids from the `screenlayout` table,
		// then check that ALL screenlayoutids exist in the database.
		$screenlayoutGuidsToFetch = array();
		foreach ($pageTemplateParsed['pagetemplate'] AS $key => $pagetemplateData)
		{
			if (isset($pagetemplateData['guid']) AND isset($pagetemplateData['screenlayoutguid']))
			{
				$pagetemplateGuidToScreenlayoutGuid[$pagetemplateData['guid']] = $pagetemplateData['screenlayoutguid'];
				$screenlayoutGuidsToFetch[$pagetemplateData['screenlayoutguid']] = $pagetemplateData['screenlayoutguid'];
			}
		}


		/* I could probably create a temp table and do fancy a triple join update, but I don't know if that'd be any better
		 * than doing these foreach loops in PHP. There are only like 40 pagetemplate records to update (if there are more
		 * records, chances are they're custom records saved in a previous vB5 install meaning they never hit this issue
		 * in the first place) so I don't foresee an upgrade performance issue.
		 */

		$layouts = $assertor->getRows('screenlayout', array('guid' => $screenlayoutGuidsToFetch));
		$screenlayoutGuidsToId = array();
		foreach ($layouts AS $screenlayoutRecord)
		{
			$guid = $screenlayoutRecord['guid'];
			$id = $screenlayoutRecord['screenlayoutid'];
			$screenlayoutGuidsToId[$guid] = $id;
			unset($screenlayoutGuidsToFetch[$guid]);
		}

		if (!empty($screenlayoutGuidsToFetch))
		{
			/*
			 * I haven't actually hit this error, but I'm putting it here because I'm paranoid possibly due to being
			 * either slightly too deprived of or overdosed on coffee.
			 *
			 * If we ran $this->step_1() and we're STILL missing some screenlayout records, we're in some trouble.
			 *
			 * Time Capsule Message @ Future Dev(s):
			 * If anyone needs to look at this in the future, it probably means that either a screenlayoutguid that was
			 * added to the vbulletin-pagetemplates XML file is missing its component in the vbulletin-screenlayouts
			 * XML file, or the import step (currently final upgrade's step_5()) is broken. If it's the first issue,
			 * go find the person who edited the pagetemplates file & request that they update the screenlayouts file.
			 * If it's the latter, you'll have to figure out what broke with the importer and fix it. The importer is
			 * @ core/vb/xml/import/screenlayout.php .
			 * Those are the best guesses I have at the moment for why this step might be unhappy. Good luck.
			 */
			$guidString = implode(", \n ", $screenlayoutGuidsToFetch); // ATM i don't see a reason to escape this, as guids are pulled from internal FILES, not DB/user.
			$this->add_error(sprintf($this->phrase['version']['514b3']['missing_screenlayout_guids_x'], $guidString), self::PHP_TRIGGER_ERROR, true);
			return;
		}

		$updatedata = array();
		// notices/warnings for catching potential bugs in development. They will only be used while in debug mode.
		$notices = array();
		$warnings = array();
		foreach ($fixThese AS $pagetemplateRecord)
		{
			$pagetemplateGuid = $pagetemplateRecord['guid'];

			// there could be 2 cases. One is that
			if (empty($pagetemplateGuid))
			{
				// apparently there can be pagetemplate records with NULL guids and empty screenlayoutids, often also missing everything but the pagetemplateid.
				// In that case, let's ignore them but notify the installer.
				$notices[$pagetemplateRecord['pagetemplateid']] = intval($pagetemplateRecord['pagetemplateid']);
				continue;
			}


			/*

			$guid = $screenlayoutRecord['guid'];
			$id = $screenlayoutRecord['screenlayoutid'];
			$screenlayoutGuidsToId[$guid] = $id;
			 */
			if (!isset($pagetemplateGuidToScreenlayoutGuid[$pagetemplateGuid]))
			{
				/*
				 * Another error that currently doesn't happen, but catching just in case.
				 *
				 * Time Capsule Message @ Future Dev(s):
				 * If you hit this, you have a pagetemplate record in DB with screenlayoutid = 0, but it's not
				 * a default pagetemplate record that exists in the vbulletin-pagetemplates XML file.
				 * My best guess as to why this could happen is that someone added a new pagetemplate to the
				 * vbulletin-pagetemplates XML file but forgot to specify a valid screenlayoutguid while
				 * inserting the pagetemplate record into the DB manually instead of going through final_upgrade.
				 *
				 * If this isn't a newly added default pagetemplate record, and you don't care that its
				 * screenlayoutid is 0, you could try ignoring this error (comment out below).
				 *
				 * UPDATE 2014-11-03:
				 * The upgrade of live forum hit this error. Upon checking the DB, I saw that they had NULL guid,
				 * so I added a continue; with a notice for those records so that the admin may review the DB &
				 * delete them if they like.
				 * With the above change, if we got to this point, that means that they have 0 screenlayoutid,
				 * but a not-empty, unknown guid. That is, a custom page without a screenlayoutid, which is unusable.
				 * I'm going to change this from an error to a warning. The reason I'm changing it from an error is that
				 * the forum *probably* can continue living without this record, as it's not a default one. However,
				 * the guid indicates that it's a more recent record rather than an obsolte one, and it might indicate
				 * an error in the page save or delete code that we will want to know about & fix. As such I'm going to
				 * make this a warning instead of a notice like the above.
				 */
				$warnings[$pagetemplateGuid] = vB_String::htmlSpecialCharsUni($pagetemplateGuid);
				continue;
			}
			$screenlayoutGuid = $pagetemplateGuidToScreenlayoutGuid[$pagetemplateGuid];
			$screenlayoutId = $screenlayoutGuidsToId[$screenlayoutGuid];

			// add to map for bulk update query
			$updatedata[] = array(
				'pagetemplateid' => intval($pagetemplateRecord['pagetemplateid']),
				'screenlayoutid' => intval($screenlayoutId),
			);
		}


		if (!empty($updatedata))
		{
			$assertor->assertQuery('vBInstall:updatePagetemplateScreenlayoutid', array('pagetemplaterecords' => $updatedata));
		}

		if (!empty($config['Misc']['debug']))
		{
			if (!empty($notices))
			{
				// These are probably obsolete records. Let the admin that they can delete them.
				$idsString = implode(", \n ", $notices); // ATM i don't see a reason to escape this, as guids are pulled from internal files.
				$this->add_message(sprintf($this->phrase['version']['514b3']['notice_empty_guid_x'], $idsString));
			}

			if (!empty($warnings))
			{
				$idsString = implode(", \n ", $warnings); // ATM i don't see a reason to escape this, as guids are pulled from internal files.
				$this->add_message(sprintf($this->phrase['version']['514b3']['warning_undefined_pagetemplateguid_x'], $idsString));
			}
		}
	}
}

class vB_Upgrade_515a1 extends vB_Upgrade_Version
{
	/**
	 * Step 1 :
	 *		Add 'usermention' notification type to 'about' in privatemessage
	 */
	public function step_1()
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

class vB_Upgrade_515a2 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'channel', 1, 1),
			'channel',
			'guid',
			'guid',
			'unique'
		);
	}
}

class vB_Upgrade_515a3 extends vB_Upgrade_Version
{
	/**
	 * Remove 'lastVisit' from the "New Topics" subnavbar search json it has not been edited.
	 * Mostly copied from 512a4 step_1
	 */
	public function step_1()
	{
		$assertor = vB::getDbAssertor();
		$this->show_message($this->phrase['version']['515a3']['updating_new_topics_url']);
		$sites = $assertor->assertQuery('vBForum:site');
		$oldUrl = 'search?searchJSON=%7B%22date%22%3A%22lastVisit%22%2C%22view%22%3A%22topic%22%2C%22unread_only%22%3A1%2C%22sort%22%3A%7B%22lastcontent%22%3A%22desc%22%7D%2C%22exclude_type%22%3A%5B%22vBForum_PrivateMessage%22%5D%7D';
		$newUrl = 'search?searchJSON=%7B%22view%22%3A%22topic%22%2C%22unread_only%22%3A1%2C%22sort%22%3A%7B%22lastcontent%22%3A%22desc%22%7D%2C%22exclude_type%22%3A%5B%22vBForum_PrivateMessage%22%5D%7D';

		foreach ($sites AS $site)
		{
			$doupdate = false;
			$header = unserialize($site['headernavbar']);
			if (!empty($header))
			{
				foreach ($header as $key => $h)
				{
					if ($h['title'] == 'navbar_home')
					{
						foreach ($h['subnav'] AS $subKey => $subnav)
						{
							if ($subnav['title'] == 'navbar_newtopics' AND !empty($subnav['url']))
							{
								//Found the correct item.  See if it has been edited.
								if ($subnav['url'] == $oldUrl)
								{
									$header[$key]['subnav'][$subKey]['url'] = $newUrl;
									$doupdate = true;
									break;
								}
								else
								{
									break;
								}
							}
						}
					}
				}
			}
			if ($doupdate)
			{
				$assertor->update('vBForum:site', array('headernavbar' => serialize($header)), array('siteid' => $site['siteid']));
			}
		}
	}
}

class vB_Upgrade_515a4 extends vB_Upgrade_Version
{
	/**
	 * Long next step message for the ALTER
	 */
	public function step_1()
	{
		$this->long_next_step();
	}

	/**
	 * Add index on node.featured
	 */
	public function step_2()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'node', 1, 2),
			'node',
			'node_featured',
			'featured'
		);

		$this->long_next_step();
	}

	/**
	 * Add index on node.inlist
	 */
	public function step_3()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'node', 2, 2),
			'node',
			'node_inlist',
			'inlist'
		);
	}
}

class vB_Upgrade_515a5 extends vB_Upgrade_Version
{
	/**
	 * Turn the user mention notification option on for new registrations by default
	 */
	public function step_1()
	{
		// Add 268435456 (general_usermention) to the default value for notification_options
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'user', 1, 2),
			"ALTER TABLE " . TABLE_PREFIX . "user
			CHANGE notification_options notification_options INT UNSIGNED NOT NULL DEFAULT '536870906'"
		);
	}

	/**
	 * Turn the user mention notification option on for all current users by default
	 */
	public function step_2()
	{
		// Turn 268435456 on (general_usermention) for all users
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'user', 2, 2),
			"UPDATE " . TABLE_PREFIX . "user
			SET notification_options = (notification_options | 268435456)"
		);
	}
}

class vB_Upgrade_515b2 extends vB_Upgrade_Version
{
	/**
	 * VBV-14079 and VBV-14084 - clean attachment settings and filenames
	 */
	public function step_1($data = null)
	{
		vB_Upgrade::createAdminSession();

		$startat = (int) isset($data['startat']) ? $data['startat'] : 0;
		$batchsize = 100;

		$assertor = vB::getDbAssertor();

		if ($startat == 0)
		{
			$this->show_message($this->phrase['version']['515b2']['cleaning_attachment_filenames_and_settings']);
		}

		// For each attachment, we need to:
		// 1. html escape the attachment filename, if not already escaped (VBV-14084)
		// 2. pull data from settings without using unserialize, then re-serialize it
		//    to mitigate the PHP unserialize object injection vulnerability (VBV-14079)
		// 3. check that the author has 'canattachmentcss' for in this channel, and
		//    if not, blank out the settings[styles] value that they don't have
		//    permission to set. This mitigates the XSS vulnerability. (VBV-14079)

		// get attach records
		$rows = $assertor->getRows('vBInstall:getAttachmentsWithParentAndAuthor', array(
			'startat' => (int) $startat,
			'batchsize' => (int) $batchsize,
		));

		$rowcount = count($rows);

		if ($rowcount == 0)
		{
			// done
			$this->show_message(sprintf($this->phrase['core']['process_done']));

			return;
		}
		else
		{
			// make changes
			foreach ($rows AS $row)
			{
				// Escape filenames (VBV-14084)
				$updates = array();
				if ($row['filename'] === vB_String::unHtmlSpecialChars($row['filename']))
				{
					// unescaping didn't change anything, so this filename may need to be escaped
					$escaped = vB_String::htmlSpecialCharsUni($row['filename']);
					if ($escaped !== $row['filename'])
					{
						// there was a change, need to save it
						$updates['filename'] = $escaped;
					}
				}

				if (!empty($row['settings']))
				{
					// ensure that settings contains only valid serialized
					// data we are expecting. parseAttachSettings will fail
					// if there is anything unexpected such as a serialized
					// object.
					$settings = $this->parseAttachSettings($row['settings']);

					if (empty($settings))
					{
						// insert the defaults
						$settings = array(
							'alignment'   => 'none',
							'size'        => 'full',
							'title'       => '',
							'description' => '',
							'styles'      => '',
							'link'        => 0,
							'linkurl'     => '',
							'linktarget'  => 0,
						);
					}

					// remove settings['styles'] if the user doesn't have
					// 'canattachmentcss' (VBV-14079)
					$usercontext = vB::getUserContext($row['userid']);
					if (!$usercontext OR !$usercontext->getChannelPermission('forumpermissions', 'canattachmentcss', $row['nodeid']))
					{
						// user doesn't have the permission for this node
						$settings['styles'] = '';
					}

					$settings = serialize($settings);

					if ($settings !== $row['settings'])
					{
						$updates['settings'] = $settings;
					}
				}

				if (!empty($updates))
				{
					// save any changes....
					$assertor->update('vBForum:attach', $updates, array('nodeid' => $row['nodeid']));
				}
			}

			// output progress
			$from = $startat + 1;
			$to = $from + $rowcount - 1;
			$this->show_message(sprintf($this->phrase['core']['processed_records_x_y'], $from, $to));

			// return for next batch
			return array('startat' => $startat + $batchsize);
		}

	}

	/**
	 * Internal function used by step_1 to safely parse serialized data
	 * without the risk of trying to instantiate serialized objects, etc.
	 * This is *not* a full unserialize function, it *only* handles the
	 * data that we expect to be in the settings field, in the format that
	 * we expect, namely an array of specific strings.
	 *
	 * @param  string Serialized array of settings
	 * @return array  The unserialized array of settings, checked against
	 *                a whitelist, OR an empty array of any unexpected
	 *                data was found
	 */
	protected function parseAttachSettings($settings)
	{
		// expect an array
		if (!preg_match('#^a:(\d+):{(.+)}$#', $settings, $matches))
		{
			return array();
		}

		$count = $matches[1];
		$elementString = $matches[2];
		$elements = array();

		$whitelist = array(
			'alignment',
			'size',
			'title',
			'description',
			'styles',
			'link',
			'linkurl',
			'linktarget',
		);

		// each array element should have a string key and a string or int value
		for ($i = 0; $i < $count; ++$i)
		{
			// get key length
			if (!preg_match('#^s:(\d+):#', $elementString, $matches))
			{
				return array();
			}
			$keyLen = $matches[1];
			$matchLen = strlen($matches[0]);
			$elementString = substr($elementString, $matchLen);
			$key = (string) substr($elementString, 1, $keyLen); // 1 to advance past the opening quote (")
			$elementString = substr($elementString, $keyLen + 3); // +3 to account for the quotes ("") and ending (;)

			// get value
			if (!preg_match('#^(s|i):#', $elementString, $matches))
			{
				return array();
			}
			$type = $matches[1];
			if ($type == 's')
			{
				if (!preg_match('#^s:(\d+):#', $elementString, $matches))
				{
					return array();
				}
				$valueLen = $matches[1];
				$matchLen = strlen($matches[0]);
				$elementString = substr($elementString, $matchLen);
				$value = (string) substr($elementString, 1, $valueLen); // 1 to advance past the opening quote (")
				$elementString = substr($elementString, $valueLen + 3); // +3 to account for the quotes ("") and ending (;)
			}
			else // 'i'
			{
				if (!preg_match('#^i:(\d+);#', $elementString, $matches))
				{
					return array();
				}
				$value = (int) $matches[1];
				$matchLen = strlen($matches[0]);
				$elementString = substr($elementString, $matchLen);
			}

			if (in_array($key, $whitelist, true))
			{
				if ($key === 'alignment')
				{
					$value = in_array($value, array('none', 'left', 'center', 'right'), true) ? $value : 'none';
				}
				else if ($key === 'size')
				{
					$value = vB_Api::instanceInternal('Filedata')->sanitizeFiletype($value);
				}

				$elements[$key] = $value;
			}
		}

		return $elements;
	}
}

class vB_Upgrade_516a1 extends vB_Upgrade_Version
{
	/*
	  Step 1 - Add E-Mail Scheduled Task
	*/
	public function step_1()
	{
		$this->add_cronjob(
			array(
				'varname'  => 'cronmail',
				'nextrun'  => 1320000000,
				'weekday'  => -1,
				'day'      => -1,
				'hour'     => -1,
				'minute'   => 'a:6:{i:0;i:0;i:1;i:10;i:2;i:20;i:3;i:30;i:4;i:40;i:5;i:50;}',
				'filename' => './includes/cron/mailqueue.php',
				'loglevel' => 1,
				'volatile' => 1,
				'product'  => 'vbulletin'
			)
		);
	}

	/*
	 * Step 2 ~ 5 were for 516 notification refactor, which is not fully compatible with the 517
	 * refactor, so they've been moved & updated in the 517a3 upgrader
	 */
	public function step_2()
	{
		$this->skip_message();
	}
}

class vB_Upgrade_516a5 extends vB_Upgrade_Version
{
	/*
	 * Steps 1~4 were for 516 notification refactor, which is not fully compatible with the 517
	 * refactor, so they've been moved & updated in the 517a3 upgrader
	 */
	public function step_1()
	{
		$this->skip_message();
	}
}

class vB_Upgrade_516a6 extends vB_Upgrade_Version
{
	/**
	 * Steps 1 was for 516 notification refactor, which is not fully compatible with the 517
	 * refactor, so they've been moved & updated in the 517a3 upgrader
	 */
	public function step_1($data = NULL)
	{
		$this->skip_message();
	}
}

class vB_Upgrade_517a2 extends vB_Upgrade_Version
{
	/**
	 * Step 1 : Remove any old references to forumcache in datastore VBV-14253
	 */
	public function step_1()
	{
		$this->remove_datastore_entry('forumcache');
	}
}

class vB_Upgrade_517a3 extends vB_Upgrade_Version
{
	/*
	 * Step 1 : Add notificationevent table
	 */
	public function step_1()
	{
		if (!$this->tableExists('notificationevent'))
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'notificationevent'),
				"CREATE TABLE " . TABLE_PREFIX . "notificationevent (
					eventname		VARCHAR(191) NOT NULL UNIQUE,
					classes 		MEDIUMTEXT NULL DEFAULT NULL,
					PRIMARY KEY  	(eventname)
				) ENGINE = " . $this->hightrafficengine . "
				",
				self::MYSQL_ERROR_TABLE_EXISTS
			);
		}
		else
		{
			$this->skip_message();
		}
		$this->long_next_step();
	}

	/*
	 * Step 2 : notificationtype table
	 */
	public function step_2($data = [])
	{
		if (!$this->tableExists('notificationtype'))
		{
			$this->run_query(
				sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'notificationtype'),
				"CREATE TABLE " . TABLE_PREFIX . "notificationtype (
					typeid 			SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
					typename 		VARCHAR(191) NOT NULL UNIQUE,
					class			VARCHAR(191) NOT NULL UNIQUE,
					PRIMARY KEY  	(typeid)
				) ENGINE = " . $this->hightrafficengine . "
				",
				self::MYSQL_ERROR_TABLE_EXISTS
			);
		}
		else
		{
			// potentially a re-run, or we may have 516 tables to worry about.
			if ($this->field_exists('notificationtype', 'categoryid'))
			{
				$this->run_query(
					sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . "notificationtype"),
					"RENAME TABLE " . TABLE_PREFIX . "notificationtype TO " . TABLE_PREFIX . "notificationtype_temporary",
					self::MYSQL_ERROR_TABLE_EXISTS
				);

				// Now create the 517 table. We'll import old data in the next step(s).
				return ['startat' => 1];
			}
			else
			{
				$this->skip_message();
			}
		}
	}

	/*
	 * Add the new notificationtype & notificationevent data
	 */
	public function step_3()
	{
		vB_Library::clearCache();
		$this->final_add_notificationdefaults();
		$this->long_next_step();
	}

	/*
	 * Step 4 : Add notification table
	 */
	public function step_4($data = [])
	{
		if (!$this->tableExists('notification'))
		{
			// No 516 to worry about, add the new table.
			$this->run_query(
				sprintf($this->phrase['vbphrase']['create_table'], TABLE_PREFIX . 'notification'),
				"CREATE TABLE " . TABLE_PREFIX . "notification (
					notificationid		INT UNSIGNED NOT NULL AUTO_INCREMENT,
					recipient	 		INT UNSIGNED NOT NULL,
					sender	 			INT UNSIGNED DEFAULT NULL,
					lookupid			VARCHAR(150) NULL DEFAULT NULL,
					lookupid_hashed		CHAR(32) NULL DEFAULT NULL,
					sentbynodeid		INT UNSIGNED DEFAULT NULL,
					customdata			MEDIUMTEXT,
					typeid				SMALLINT UNSIGNED NOT NULL,
					lastsenttime 		INT(10) UNSIGNED NOT NULL DEFAULT '0',
					lastreadtime 		INT(10) UNSIGNED NOT NULL DEFAULT '0',
					PRIMARY KEY 	(notificationid),
					UNIQUE KEY guid	(recipient, lookupid_hashed),
					KEY 			(recipient),
					KEY 			(lookupid_hashed),
					KEY 			(lastsenttime),
					KEY 			(lastreadtime)
				) ENGINE = " . $this->hightrafficengine . "
				",
				self::MYSQL_ERROR_TABLE_EXISTS
			);
		}
		else
		{
			// potentially a re-run, or we may have 516 tables to worry about.
			if ($this->field_exists('notification', 'aboutstarterid'))
			{
				$this->run_query(
					sprintf($this->phrase['vbphrase']['update_table'], TABLE_PREFIX . "notification"),
					"RENAME TABLE " . TABLE_PREFIX . "notification TO " . TABLE_PREFIX . "notification_temporary",
					self::MYSQL_ERROR_TABLE_EXISTS
				);

				// Now create the 517 table. We'll import old data in the next step(s).
				return ['startat' => 1];
			}
			else
			{
				$this->skip_message();
			}
		}
		$this->long_next_step();
	}

	// Add delete helper column for step_6. THIS CAN TAKE A WHILE
	public function step_5($data = [])
	{
		if ($this->tableExists('notification_temporary')
			AND !$this->field_exists('notification_temporary', 'deleted')
		)
		{

			$this->add_field(
				sprintf($this->phrase['core']['altering_x_table'], 'notification_temporary ', 1, 1),
				'notification_temporary',
				'deleted',
				'tinyint',
				['null' => false, 'default' => 0]
			);
		}
		else
		{
			$this->skip_message();
		}
		$this->long_next_step();
	}

	// Import 516 notification data.
	public function step_6($data = [])
	{
		if ($this->tableExists('notification_temporary'))
		{
			$batchSize = 5000;
			$assertor = vB::getDbAssertor();
			$oldNotifications = $assertor->getRows(
				'vBInstall:fetch516Notifications',
				array(
					'batchsize'	=> $batchSize
				)
			);

			if (empty($oldNotifications))
			{
				$this->long_next_step();
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}

			$typeQuery = $assertor->getRows('vBInstall:fetch516NotificationTypeData');
			$oldTypesByTypeid = [];
			foreach ($typeQuery AS $row)
			{
				$oldTypesByTypeid[$row['typeid']] = $row;
			}

			// old categoryname.typename => new notification class name
			$newTypeClassMap = array(
				'content' => array(
					'subscription' => 'vB_Notification_Content_GroupByStarter_Subscription',
					'reply' => 'vB_Notification_Content_GroupByStarter_Reply',
					'comment' => 'vB_Notification_Content_GroupByParentid_Comment',
					'threadcomment' => 'vB_Notification_Content_GroupByParentid_ThreadComment',
				),
				'special' => array(
					'usermention' => 'vB_Notification_Content_UserMention',
					'vm' => 'vB_Notification_VisitorMessage',
				),
				'pollvote' => array(
					'vote' => 'vB_Notification_PollVote',
				),
				'nodeaction' => array(
					'like' => 'vB_Notification_LikedNode',
				),
				'userrelation' => array(
					'following' => 'vB_Notification_UserRelation_SenderIsfollowing',
					'accepted_follow' => 'vB_Notification_UserRelation_SenderAcceptedFollowRequest',
				),
			);

			$newTypesByName = vB_Library::instance('notification')->getNotificationTypes();

			$start = NULL;
			$end = NULL;
			$deleteThese = array();
			$insertThese = array();
			foreach ($oldNotifications AS $row)
			{
				$deleteThese[] = $row['notificationid'];
				$end = $row['notificationid'];
				if (is_null($start))
				{
					$start = $row['notificationid'];
				}


				$oldType = $oldTypesByTypeid[$row['typeid']];
				if (empty($oldType) OR !isset($newTypeClassMap[$oldType['categoryname']][$oldType['typename']]))
				{
					// We don't know what type this is.
					continue;
				}

				if ($oldType['categoryname'] == 'content' AND (empty($row['parentid']) OR empty($row['starter'])))
				{
					// this indicates a node was removed in 516. We need to skip importing this or else vB_Notification::fetchLookupid()
					// will throw an exception.
					continue;
				}

				$notificationClass = $newTypeClassMap[$oldType['categoryname']][$oldType['typename']];
				if (!is_subclass_of($notificationClass, 'vB_Notification'))
				{
					continue;
				}
				$lookupid = $notificationClass::fetchLookupid($row, true);
				if (is_null($lookupid))
				{
					$row['lookupid_hashed'] = NULL;
				}
				else
				{
					$row['lookupid_hashed'] = $notificationClass::getHashedLookupid($lookupid); // lookupid_hashed		CHAR(32), md5() is 32 chars.
					$row['lookupid'] = substr($lookupid, 0, 150);	// lookupid		VARCHAR(150) NULL DEFAULT NULL,
				}

				$row['typeid'] = $newTypesByName[$notificationClass::TYPENAME]['typeid'];


				if (empty($row['lookupid_hashed']))
				{
					$insertThese[] = $row;
				}
				else
				{
					$key = "_{$row['recipient']}" . vB_Notification::DELIM . $row['lookupid_hashed'];
					// For these imports, just ignore priority and just import the last one if any old ones now collapse.
					$insertThese[$key] = $row;
				}
			}

			if (!empty($insertThese))
			{
				$assertor->assertQuery('vBForum:addNotifications', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_METHOD, 'notifications' => $insertThese));
			}


			if (!empty($deleteThese))
			{
				$assertor->assertQuery('vBInstall:delete516Notifications', array('deleteids' => $deleteThese));
			}
			$this->show_message(sprintf($this->phrase['core']['processed_records_x_y'], $start, $end));

			return array('startat' => $end);
		}
		else
		{
			$this->skip_message();
		}
		$this->long_next_step();
	}


	//Drop the old data.
	public function step_7($data = [])
	{
		if ($this->tableExists('notification_temporary')
			OR $this->tableExists('notificationtype_temporary')
			OR $this->tableExists('notificationcategory')
		)
		{
			$batchSize = 1;
			$assertor = vB::getDbAssertor();
			$oldNotifications = $assertor->getRows(
				'vBInstall:fetch516Notifications',
				array(
					'batchsize'	=> $batchSize
				)
			);

			if (empty($oldNotifications))
			{
				$this->run_query(
					sprintf($this->phrase['core']['dropping_old_table_x'], TABLE_PREFIX . "notification_temporary"),
					"DROP TABLE IF EXISTS " . TABLE_PREFIX . "notification_temporary"
				);
				$this->run_query(
					sprintf($this->phrase['core']['dropping_old_table_x'], TABLE_PREFIX . "notificationtype_temporary"),
					"DROP TABLE IF EXISTS " . TABLE_PREFIX . "notificationtype_temporary"
				);
				$this->run_query(
					sprintf($this->phrase['core']['dropping_old_table_x'], TABLE_PREFIX . "notificationcategory"),
					"DROP TABLE IF EXISTS " . TABLE_PREFIX . "notificationcategory"
				);
				return;
			}
		}
		else
		{
			$this->skip_message();
		}
		$this->long_next_step();
	}

	/*
		Below steps were previously in 516a5.
		Import pre-516 data into 517.

		The basic plan is to fetch old notifications data by a query based on the one used by
		vBForum:listNotifications, insert them into the new table (excepting any that might be duplicated
		or was sent by the recipient), and then update the old PM/sentto notification records to deleted = 0 .
		The PM cron will handle actually deleting the old notifications.
		At the moment, we don't check for showpublished or showapproved for the import. Note that in 5.1.6
		*generation* requires that node is published & approved.
	 */

	//import old content notifications: 'reply', 'comment', 'threadcomment', 'subscription', 'usermention', 'vm'
	public function step_8($data = [])
	{
		$assertor = vB::getDbAssertor();

		// This batchsize was selected based on testing on the saas DB with 3 million legacy notifications.
		$batchSize = 5000;

		if (empty($data['startat']))
		{
			$this->show_message($this->phrase['version']['517a3']['importing_content_notification']);
		}
		else
		{
			$startat = intval($data['startat']);	// not used
		}

		$pmLib = vB_Library::instance('content_privatemessage');
		$allowedTypes = ['Gallery', 'Link', 'Poll', 'Text', 'Video'];
		$allowedTypeids = [];
		$typePrefix = 'vBForum_';
		foreach ($allowedTypes AS $typeText)
		{
			$typeText = $typePrefix . $typeText;
			$typeid = vB_Types::instance()->getContentTypeID($typeText);
			if (!empty($typeid))
			{
				$allowedTypeids[$typeText] = $typeid;
			}
		}
		$importThese = $assertor->getRows(
			'vBInstall:fetchOldContentNotifications',
			array(
				'batchsize'	=> $batchSize
			)
		);

		$newTypesByName = vB_Library::instance('notification')->getNotificationTypes();
		$deleteNodeids = [];
		$notifications = [];
		$start = NULL;
		$end = NULL;
		foreach ($importThese AS $row)
		{
			$deleteNodeids[] = $row['delete_nodeid'];
			$end = $row['delete_nodeid'];
			if (is_null($start))
			{
				$start = $row['delete_nodeid'];
			}

			// Ignorelist & missing user record checks
			if (!empty($row['skip']) OR !empty($row['skip_missing_recipient']))
			{
				continue;
			}

			/*
				About $row['message_sender'] : In the old system, when a notification overwrite a previous one, the aboutid changes to the
				new content node, but the notification's sender stays the same. This conflicts, for example, if Alex has a thread and Bob
				and Cat responds to that thread, respectively, where the message_sender would point to Bob, but the aboutid will point to
				Cat's reply. I've decided to ignore the sentto data and rely on the content node's userid as the sender, but I've left
				the old column select to allow this explanation to have some context.
			 */

			/*
				comments and thread comments use the "parent" instead of the actual comment node
				for aboutid, so we need to grab the first comment for each parent...
				If 'sentbynodeid' is a reply (which most likely it is), its starter will be equal to its parent.
				If somehow it's the comment, then its starter will not be its parent.
			 */
			if ($row['about'] == "comment" OR $row['about'] == "threadcomment")
			{
				$firstCommentNode = $assertor->getRow(
					'vBInstall:fetchFirstComment',
					array(
						'parentid'	=> $row['sentbynodeid'],
						'contenttypeids'	=> $allowedTypeids,
						'recipient'	=> $row['recipient'],
					)
				);
				if (!empty($firstCommentNode['nodeid']))
				{
					$row['sender'] = $firstCommentNode['userid'];
					$row['sentbynodeid'] = $firstCommentNode['nodeid'];
				}
				else
				{
					// If we cannot find the first comment that's not owned by
					// the recipient or an ignored user, just skip importing this one.
					continue;
				}
			}

			$typeName = $pmLib->convertLegacyNotificationAboutString($row['about']);
			$typeData = $newTypesByName[$typeName];
			if (!empty($typeData['class']) AND is_subclass_of($typeData['class'], 'vB_Notification'))
			{
				$row['typeid'] = $typeData['typeid'];

				$notificationClass = $typeData['class'];
				$lookupid = $notificationClass::fetchLookupid($row, true);
				$row['lookupid_hashed'] = $notificationClass::getHashedLookupid($lookupid);
				if (!is_null($lookupid))
				{
					$row['lookupid'] = substr($lookupid, 0, 150);
				}


				$addme = array(
					'recipient' 	=> $row['recipient'],
					'sender' 		=> $row['sender'],
					'lookupid'		=> $row['lookupid'],
					'lookupid_hashed'	=> $row['lookupid_hashed'],
					'sentbynodeid'	=> $row['sentbynodeid'],
					'typeid' 		=> $row['typeid'],
					'lastsenttime' 	=> $row['lastsenttime'],
				);

				$notifications[] = $addme;
			}
		}

		if (!empty($notifications))
		{
			$try = $assertor->assertQuery(
				'vBInstall:insertOldNotification',
				['notifications' => $notifications]
			);
		}

		if (!empty($deleteNodeids))
		{
			$assertor->assertQuery(
				'vBInstall:flagNotificationsForDelete',
				['deleteNodeids' => $deleteNodeids]
			);

			$this->show_message(sprintf($this->phrase['core']['processed_records_x_y'], $start, $end));
			// startat not used, but we need to loop this step. Any other data you might pass through is not reliable due to
			// bugs w/ web interface
			return ['startat' => $end];
		}
		else
		{
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			return;
		}
	}



	//import old vote notifications: 	import old like (previously 'rate') notifications
	public function step_9($data = [])
	{
		$assertor = vB::getDbAssertor();
		// See notes about batch size in step_8()
		$batchSize = 5000;

		if (empty($data['startat']))
		{
			$this->show_message($this->phrase['version']['517a3']['importing_pollvote_like_notification']);
		}
		else
		{
			$startat = intval($data['startat']);	// not used
		}

		$pmLib = vB_Library::instance('content_privatemessage');
		$importThese = $assertor->getRows(
			'vBInstall:fetchOldContentlessNotifications',
			['batchsize'	=> $batchSize]
		);

		$newTypesByName = vB_Library::instance('notification')->getNotificationTypes();
		$start = NULL;
		$end = NULL;
		$deleteNodeids = [];
		$notifications = [];
		foreach ($importThese AS $row)
		{
			$deleteNodeids[] = $row['delete_nodeid'];
			$end = $row['delete_nodeid'];
			if (is_null($start))
			{
				$start = $row['delete_nodeid'];
			}

			// Ignorelist & missing user record checks
			if (!empty($row['skip']) OR !empty($row['skip_missing_recipient']))
			{
				continue;
			}

			$typeName = $pmLib->convertLegacyNotificationAboutString($row['about']);
			$typeData = $newTypesByName[$typeName];
			if (!empty($typeData['class']) AND is_subclass_of($typeData['class'], 'vB_Notification'))
			{
				$row['typeid'] = $typeData['typeid'];

				$notificationClass = $typeData['class'];
				$lookupid = $notificationClass::fetchLookupid($row, true);
				$row['lookupid_hashed'] = $notificationClass::getHashedLookupid($lookupid);
				if (!is_null($lookupid))
				{
					$row['lookupid'] = substr($lookupid, 0, 150);
				}

				$addme = array(
					'recipient' 	=> $row['recipient'],
					'sender' 		=> $row['sender'],
					'lookupid'		=> $row['lookupid'],
					'lookupid_hashed'	=> $row['lookupid_hashed'],
					'sentbynodeid'	=> $row['sentbynodeid'],
					'typeid' 		=> $row['typeid'],
					'lastsenttime' 	=> $row['lastsenttime'],
				);

				$notifications[] = $addme;
			}
		}

		if (!empty($notifications))
		{
			$try = $assertor->assertQuery(
				'vBInstall:insertOldNotification',
				['notifications' => $notifications]
			);
		}

		if (!empty($deleteNodeids))
		{
			$assertor->assertQuery(
				'vBInstall:flagNotificationsForDelete',
				['deleteNodeids' => $deleteNodeids]
			);

			$this->show_message(sprintf($this->phrase['core']['processed_records_x_y'], $start, $end));
			// startat not used, but we need to loop this step. Any other data you might pass through is not reliable due to
			// bugs w/ web interface
			return ['startat' => $end];
		}
		else
		{
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			return;
		}
	}

	public function step_10()
	{
		$this->add_field(
			sprintf($this->phrase['vbphrase']['alter_table'], 'profilefield'),
			'profilefield',
			'showonpost',
			'smallint',
			self::FIELD_DEFAULTS
		);
	}

	public function step_11()
	{
		$this->show_message(sprintf($this->phrase['core']['rebuild_x_datastore'], 'profilefield'));
		vB_Library::instance('user')->buildProfileFieldDatastore();
	}

	public function step_12()
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

	public function step_13()
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

	//import old follow & following notifications
	public function step_14($data = [])
	{
		$assertor = vB::getDbAssertor();

		// See notes about batch size in step_8()
		$batchSize = 5000;

		if (empty($data['startat']))
		{
			$this->show_message($this->phrase['version']['517a3']['importing_userrelation_notification']);
		}
		else
		{
			$startat = intval($data['startat']);	// not used
		}

		$pmLib = vB_Library::instance('content_privatemessage');
		$importThese = $assertor->getRows(
			'vBInstall:fetchOldUserrelationNotifications',
			[
				'batchsize'	=> $batchSize,
			]
		);

		$newTypesByName = vB_Library::instance('notification')->getNotificationTypes();
		$start = NULL;
		$end = NULL;
		$deleteNodeids = [];
		$notifications = [];
		foreach ($importThese AS $row)
		{
			$deleteNodeids[] = $row['delete_nodeid'];
			$end = $row['delete_nodeid'];
			if (is_null($start))
			{
				$start = $row['delete_nodeid'];
			}

			// Ignorelist & missing user record checks
			if (!empty($row['skip']) OR !empty($row['skip_missing_recipient']) OR !empty($row['skip_missing_sender']))
			{
				continue;
			}

			$typeName = $pmLib->convertLegacyNotificationAboutString($row['about']);
			$typeData = $newTypesByName[$typeName];
			if (!empty($typeData['class']) AND is_subclass_of($typeData['class'], 'vB_Notification'))
			{
				$row['typeid'] = $typeData['typeid'];

				$notificationClass = $typeData['class'];
				$lookupid = $notificationClass::fetchLookupid($row, true);
				$row['lookupid_hashed'] = $notificationClass::getHashedLookupid($lookupid);
				if (!is_null($lookupid))
				{
					$row['lookupid'] = substr($lookupid, 0, 150);
				}


				$addme = array(
					'recipient' 	=> $row['recipient'],
					'sender' 		=> $row['sender'],
					'lookupid'		=> $row['lookupid'],
					'lookupid_hashed'	=> $row['lookupid_hashed'],
					'sentbynodeid'	=> $row['sentbynodeid'],
					'typeid' 		=> $row['typeid'],
					'lastsenttime' 	=> $row['lastsenttime'],
				);

				$notifications[] = $addme;
			}
		}

		if (!empty($notifications))
		{
			$try = $assertor->assertQuery(
				'vBInstall:insertOldNotification',
				['notifications' => $notifications]
			);
		}

		if (!empty($deleteNodeids))
		{
			$assertor->assertQuery(
				'vBInstall:flagNotificationsForDelete',
				['deleteNodeids' => $deleteNodeids]
			);

			$this->show_message(sprintf($this->phrase['core']['processed_records_x_y'], $start, $end));

			// startat not used, but we need to loop this step. Any other data you might pass through is not reliable due to
			// bugs w/ web interface
			return ['startat' => $end];
		}
		else
		{
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			return;
		}
	}


	/**
	 * Import notifications for new visitor messages
	 * Moved here from 500rc1 step_8 because inserting notifications post 516 & 517 refactors
	 * requires the existence of the new notification tables & default data, which are added
	 * in the earlier upgrade steps.
	 */
	public function step_15($data = [])
	{
		$process = 500;

		if (empty($data['startat']))
		{
			$this->show_message($this->phrase['version']['517a3']['importing_vm_notification']);
		}
		$startat = intval($data['startat'] ?? 0);

		//First see if we need to do something. Maybe we're O.K.
		if (!empty($data['maxvB5']))
		{
			$maxvB5 = $data['maxvB5'];
		}
		else
		{
			$maxvB5 = vB::getDbAssertor()->getRow('vBInstall:getMaxUseridWithVM');
			$maxvB5 = $maxvB5['maxid'];
		}

		if ($maxvB5 <= $startat)
		{
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			return;
		}

		// Note, vB_dB_Query::OPERATOR_GT makes $starat an *exclusive* limit.
		$endat = ($startat + $process); // Note, vB_dB_Query::OPERATOR_LTE below makes this an *inclusive* limit.
		$bf_masks = vB::getDatastore()->getValue('bf_misc_usernotificationoptions');
		// Fetch user info
		$users = vB::getDbAssertor()->assertQuery('user', array(
			vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
			vB_dB_Query::COLUMNS_KEY => ['userid', 'vmunreadcount', 'notification_options'],
			vB_dB_Query::CONDITIONS_KEY => array(
				['field' => 'userid', 'value' => $startat, 'operator' => vB_dB_Query::OPERATOR_GT],
				['field' => 'userid', 'value' => $endat, 'operator' => vB_dB_Query::OPERATOR_LTE],
				['field' => 'vmunreadcount', 'value' => 0, 'operator' => vB_dB_Query::OPERATOR_GT],
			)
		));

		if ($users)
		{
			vB_Upgrade::createAdminSession();

			$messageLibrary = vB_Library::instance('Content_Privatemessage');
			$notificationLibrary = vB_Library::instance('Notification');
			$nodeLibrary = vB_Library::instance('node');
			$vmChannelId = $nodeLibrary->fetchVMChannel();
			$vmTypeid = vB_Types::instance()->getContentTypeID('vBForum_VisitorMessage');

			$notifications = [];

			$recipients = [];
			foreach($users AS $user)
			{

				$userReceivesNotification = ( $bf_masks['general_vm'] & $user['notification_options'] );
				if (!$userReceivesNotification)
				{
					continue;
				}

				if ($user['vmunreadcount'] > 0)
				{
					// fetch last N visitor messages
					$lastVM = vB::getDbAssertor()->assertQuery(
						'vBInstall:fetchImportedVMNodes',
						array(
							'recipient' => $user['userid'],
							'vmChannelId' => $vmChannelId,
							'vmTypeId' => $vmTypeid,
							'vmunreadcount' => $user['vmunreadcount'],
						)
					);

					if ($lastVM)
					{
						foreach ($lastVM AS $node)
						{
							$recipients[$node['setfor']] = $node['setfor'];
							// Group by recipient & sender. Prevent subsequent, older ones from the same sender from overwriting newer ones.
							$key = $node['setfor'] . "_" . $node['userid'];
							if (!isset($notifications[$key]))
							{
								$notifications[$key] = array(
									'sentbynodeid' => $node['nodeid'],
								);
							}
						}
					}
				}
			}

			foreach ($notifications AS $notificationData)
			{
				$notificationLibrary->triggerNotificationEvent('new-visitormessage', $notificationData, []);
			}
			$notificationLibrary->insertNotificationsToDB();

			// set unreadcount to 0. Otherwise, re-running this step will keep adding the VM notifications.
			if (!empty($recipients))
			{
				$params = [
					vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
					'userid' => $recipients, 	// key, condition
					'vmunreadcount' => 0,
				];
				vB::getDbAssertor()->assertQuery('user', $params);
			}
		}

		$this->show_message(sprintf($this->phrase['core']['processed_records_x_y'], $startat + 1, $endat));

		// See notes above $endat assignment above for more info. startat is exclusive, $endat is inclusive.
		return ['startat' => $endat, 'maxvB5' => $maxvB5];
	}

	//Add Notification cleanup cron
	public function step_16()
	{
		$this->add_cronjob(
			[
				'varname'  => 'notificationcleanup',
				'nextrun'  => 0,
				'weekday'  => -1,
				'day'      => -1,
				'hour'     => 0,
				'minute'   => 'a:1:{i:0;i:20;}',
				'filename' => './includes/cron/notification_cleanup.php',
				'loglevel' => 1,
				'volatile' => 1,
				'product'  => 'vbulletin'
			]
		);
	}
}

class vB_Upgrade_517a4 extends vB_Upgrade_Version
{
	/**
	 * Step 1 : Possibly long next step
	 */
	public function step_1()
	{
		$this->long_next_step();
	}

	/**
	 * Step 2 : nuke all legacy, orphaned notifications
	 */
	public function step_2($data = null)
	{
		$this->show_message($this->phrase['version']['517a4']['removing_orphan_notifications']);
		// We may need to move this into a stored query so we can put a LIMIT on it.
		$assertor = vB::getDbAssertor();
		$oldNotification = $assertor->getRow(
			'vBForum:privatemessage',
			array(
				'msgtype'	=> 'notification',
				'deleted'	=> 0,
			)
		);

		if (!empty($oldNotification))
		{
			$startat = $oldNotification['nodeid'];
			$count = $assertor->assertQuery('vBInstall:flagRemainingNotificationsForDelete');
			$this->show_message(sprintf($this->phrase['core']['processed_x_records_starting_at_y'], $count, $startat));
			return array('startat' => $startat);
		}
		else
		{
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			return;
		}
	}

	/**
	 * Rename widgetinstance.parent to widgetinstance.containerinstanceid
	 */
	public function step_3()
	{
		if ($this->field_exists('widgetinstance', 'parent'))
		{
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'widgetinstance', 1, 1),
				"ALTER TABLE " . TABLE_PREFIX . "widgetinstance CHANGE parent containerinstanceid INT UNSIGNED NOT NULL DEFAULT '0'"
			);
		}
		else
		{
			$this->skip_message();
		}

		$this->long_next_step();
	}

	public function step_4()
	{
		$result = vB::getDbAssertor()->getRow('vBInstall:checkDuplicatAttachRecords');
		if (!$result)
		{
			$this->drop_index(
				sprintf($this->phrase['core']['altering_x_table'], 'attach', 1, 2),
				'attach',
				'attach_nodeid'
			);

			$this->add_index(
				sprintf($this->phrase['core']['altering_x_table'], 'attach', 2, 2),
				'attach',
				'PRIMARY',
				array('nodeid'),
				'primary'
			);
		}
		else
		{
			$this->add_adminmessage('unique_index_x_failed',
				array(
					'dismissable' => 1,
					'status'  => 'undone',
				),
				true,
				array('PRIMARY KEY', 'attach', $this->LONG_VERSION)
			);

			$this->show_message(sprintf($this->phrase['core']['unique_index_x_failed'], 'PRIMARY KEY', 'attach', $this->LONG_VERSION));
		}
	}

	//we are okay dropping/creating this index without a check because the existing index is
	//unique.  We are merely changing it to a primary key.
	public function step_5()
	{
		$this->drop_index(
			sprintf($this->phrase['core']['altering_x_table'], 'redirect', 1, 2),
			'redirect',
			'nodeid'
		);
	}

	public function step_6()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'redirect', 2, 2),
			'redirect',
			'PRIMARY',
			array('nodeid'),
			'primary'
		);
	}

	public function step_7()
	{
		$result = vB::getDbAssertor()->assertQuery('vBInstall:removeAutoincrementPhoto');
		$this->show_message(sprintf($this->phrase['core']['altering_x_table'], 'photo', 1, 3));
	}

	public function step_8()
	{
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], 'photo', 2, 3),
			'photo',
			'photoid'
		);
	}

	public function step_9()
	{
		$this->add_index(
			sprintf($this->phrase['core']['altering_x_table'], 'photo', 3, 3),
			'photo',
			'PRIMARY',
			array('nodeid'),
			'primary'
		);
	}

	public function step_10()
	{
		$result = vB::getDbAssertor()->assertQuery('vBInstall:removeAutoincrementPoll');
		$this->show_message(sprintf($this->phrase['core']['altering_x_table'], 'poll', 1, 4));
	}

	public function step_11()
	{
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], 'poll', 2, 4),
			'poll',
			'pollid'
		);
	}

	public function step_12()
	{
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], 'poll', 3, 4),
			'poll',
			'question'
		);
	}

	public function step_13()
	{
		$this->drop_field(
			sprintf($this->phrase['core']['altering_x_table'], 'poll', 4, 4),
			'poll',
			'dateline'
		);
	}

	/**
	 * Change widgetdefinition.label to labelphrase
	 */
	public function step_14()
	{
		if ($this->field_exists('widgetdefinition', 'label'))
		{
			$this->run_query(
				sprintf($this->phrase['core']['altering_x_table'], 'widgetdefinition', 1, 1),
				"ALTER TABLE " . TABLE_PREFIX . "widgetdefinition CHANGE label labelphrase VARCHAR(250) NOT NULL DEFAULT ''"
			);
		}
		else
		{
			$this->skip_message();
		}
	}

	/**
	 * Add the widget.parentid field for widget inheritance
	 */
	public function step_15()
	{
		/*
			Note, for vB4 upgrades, the parentid can also be added prior to this by
			vB_Xml_Import_Widget::checkWidgetParentidAndAlterTable() . See VBV-16969
		 */
		$this->add_field(
			sprintf($this->phrase['core']['altering_x_table'], 'widget', 1, 1),
			'widget',
			'parentid',
			'int',
			self::FIELD_DEFAULTS
		);
	}


	public function step_16($data = null)
	{
		$this->show_message($this->phrase['version']['517a4']['fixing_node_showapproved']);

		// We may need to move this into a stored query so we can put a LIMIT on it.
		$assertor = vB::getDbAssertor();
		$next = $assertor->getRow(
			'vBForum:node',
			array(
				'approved' => 0,
				'showapproved' => 1,
				vB_dB_Query::COLUMNS_KEY => array('nodeid')
			)
		);

		if (!empty($next))
		{
			$startat = $next['nodeid'];
			$count = $assertor->assertQuery('vBInstall:fixShowApproved', array('batch_size' => 10000));
			$this->show_message(sprintf($this->phrase['core']['processed_x_records_starting_at_y'], $count, $startat));
			return array('startat' => $startat);
		}
		else
		{
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			return;
		}
	}
}

class vB_Upgrade_517a5 extends vB_Upgrade_Version
{
	/**
	 * Step 1 : Remove obsolete vBNotificationCategories from datastore
	 */
	public function step_1()
	{
		vB::getDbAssertor()->assertQuery(
			'datastore',
			array(
				vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_DELETE,
				'title' => 'vBNotificationCategories'
			)
		);
		$this->show_message($this->phrase['version']['517a5']['remove_vBNotificationCategories']);
	}
}

class vB_Upgrade_517b3 extends vB_Upgrade_Version
{
	/**
	 * VBV-14663 Clean node.title & photo.caption. Based on 515b2 step_1
	 */
	public function step_1($data = null)
	{
		vB_Upgrade::createAdminSession();

		$startat = (int) isset($data['startat']) ? $data['startat'] : 0;
		$batchsize = 100;

		$assertor = vB::getDbAssertor();

		if ($startat == 0)
		{
			$this->show_message($this->phrase['version']['517b3']['cleaning_photo_captions']);
		}

		// For each photo, we need to html escape the associated node.title & photo.caption, if not already escaped
		// According to vB_Api_Content::cleanInput(), title should be cleaned regardless of user's canusehtml permission,
		// so we'll not bother checking permissions and just clean them.

		// get attach records
		$rows = $assertor->getRows('vBInstall:getPhotos', array(
			'startat' => (int) $startat,
			'batchsize' => (int) $batchsize,
		));

		$rowcount = count($rows);

		if ($rowcount == 0)
		{
			// done
			$this->show_message(sprintf($this->phrase['core']['process_done']));

			return;
		}
		else
		{
			// make changes
			foreach ($rows AS $row)
			{
				// Escape filenames (VBV-14084)
				$updates = array();
				$needsUpdate = false;
				foreach (array('title', 'caption') AS $field)
				{
					/*
						Note, this bit is different from 515b2 step_1() since we update two tables (node & photo) simultaneously.
						I wanted to keep the update method query logic simple, so I'm setting $updates has to be outside of the "if changed" condition
						below. If *either* field requires update, this ensures both title & caption will have a value to be set to even if one doesn't
						strictly require changing. See vBInstall:updatePhotoTitleAndCaption for the query.
					 */
					$escaped = vB_String::htmlSpecialCharsUni($row[$field]);
					$updates[$field] = $escaped;
					if ($row[$field] === vB_String::unHtmlSpecialChars($row[$field]))
					{
						// unescaping didn't change anything, so this filename may need to be escaped
						if ($escaped !== $row[$field])
						{
							// there was a change, need to save it
							$needsUpdate = true;
						}
					}
				}

				if ($needsUpdate)
				{
					$updates['nodeid'] = $row['nodeid'];
					// save any changes....
					$assertor->assertQuery('vBInstall:updatePhotoTitleAndCaption', $updates);
				}
			}

			// output progress
			$from = $startat + 1;
			$to = $from + $rowcount - 1;
			$this->show_message(sprintf($this->phrase['core']['processed_records_x_y'], $from, $to));

			// return for next batch
			return array('startat' => $startat + $batchsize);
		}
	}
}

class vB_Upgrade_518a1 extends vB_Upgrade_Version
{
	public function step_1($data = NULL)
	{
		$this->long_next_step();
	}

	/*
	 *	step 2: remove old notifications with deleted recipients
	 */
	public function step_2($data = NULL)
	{
		$assertor = vB::getDbAssertor();

		$batchSize = 10000;

		if (empty($data['startat']))
		{
			$this->show_message($this->phrase['version']['518a1']['removing_recipientless_notifications']);
		}

		$rows = $assertor->getRows(
			'vBInstall:fetchNotificationsWithDeletedRecipients',
			array(
				'batchsize'	=> $batchSize
			)
		);
		if (empty($rows))
		{
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			$this->long_next_step();
			return;
		}
		else
		{
			$ids = array();
			$start = reset($rows);
			$start = $start['notificationid'];
			$end = null;
			foreach($rows AS $row)
			{
				$end = $ids[] = $row['notificationid'];
			}

			vB::getDbAssertor()->assertQuery(
				'vBForum:notification',
				array(
					vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_DELETE,
					'notificationid' => $ids
				)
			);

			$this->show_message(sprintf($this->phrase['core']['processed_records_x_y'], $start, $end));
			// startat not used, but we need to loop this step. Any other data you might pass through is not reliable due to
			// bugs w/ web interface
			return array('startat' => $end);
		}
	}

	/*
	 *	step 3: Delete any userrelation notifications sent from a deleted
	 *		user.
	 */
	public function step_3($data = NULL)
	{
		$assertor = vB::getDbAssertor();

		$batchSize = 10000;

		if (empty($data['startat']))
		{
			$this->show_message($this->phrase['version']['518a1']['removing_senderless_notifications_userrelation']);
		}

		$typesByName = vB_Library::instance('notification')->getNotificationTypes();

		$typeids = array(
			$typesByName[vB_Notification_UserRelation_SenderAcceptedFollowRequest::TYPENAME]['typeid'],
			$typesByName[vB_Notification_UserRelation_SenderIsfollowing::TYPENAME]['typeid']
		);

		$rows = $assertor->getRows(
			'vBInstall:fetchNotificationsWithDeletedSendersOfTypesX',
			array(
				'batchsize'	=> $batchSize,
				'typeids' => $typeids,
			)
		);
		if (empty($rows))
		{
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			$this->long_next_step();
			return;
		}
		else
		{
			$ids = array();
			$start = reset($rows);
			$start = $start['notificationid'];
			$end = null;
			foreach($rows AS $row)
			{
				$end = $ids[] = $row['notificationid'];
			}

			vB::getDbAssertor()->assertQuery(
				'vBForum:notification',
				array(
					vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_DELETE,
					'notificationid' => $ids
				)
			);

			$this->show_message(sprintf($this->phrase['core']['processed_records_x_y'], $start, $end));
			// startat not used, but we need to loop this step. Any other data you might pass through is not reliable due to
			// bugs w/ web interface
			return array('startat' => $end);
		}
	}

	// step 4 update VMs
	public function step_4($data = NULL)
	{
		/*
			VM logic is complex, so let's just fetch 1 deleted sender at a time and go through the vB_Notification_VisitorMessage class
		 */
		$assertor = vB::getDbAssertor();

		if (empty($data['startat']))
		{
			$this->show_message($this->phrase['version']['518a1']['removing_senderless_notifications_vm']);
		}

		$typesByName = vB_Library::instance('notification')->getNotificationTypes();

		$vmTypeid = $typesByName[vB_Notification_VisitorMessage::TYPENAME]['typeid'];

		$row = $assertor->getRow(
			'vBInstall:fetchDeletedSenderForNotificationsOfTypeX',
			array(
				'typeid' => $vmTypeid,
			)
		);
		if (empty($row))
		{
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			$this->long_next_step();
			return;
		}
		else
		{
			vB_Notification_VisitorMessage::handleUpdateEvents('deleted_user', array('userid' => $row['sender']));

			$this->show_message(sprintf($this->phrase['version']['518a1']['updating_deleted_sender_x'], $row['sender']));
			// startat not used, but we need to loop this step. Any other data you might pass through is not reliable due to
			// bugs w/ web interface
			return array('startat' => $row['sender']);
		}
	}

	// step 5 update non userrelation, nonvms to guest
	public function step_5($data = null)
	{
		$assertor = vB::getDbAssertor();

		$batchSize = 10000;

		if (empty($data['startat']))
		{
			$this->show_message($this->phrase['version']['518a1']['updating_senderless_notifications_to_guest']);
		}

		$typesByName = vB_Library::instance('notification')->getNotificationTypes();

		$typeidsToSkip = array(
			$typesByName[vB_Notification_UserRelation_SenderAcceptedFollowRequest::TYPENAME]['typeid'],
			$typesByName[vB_Notification_UserRelation_SenderIsfollowing::TYPENAME]['typeid'],
			$typesByName[vB_Notification_VisitorMessage::TYPENAME]['typeid'],
		);

		$rows = $assertor->getRows(
			'vBInstall:fetchNotificationsWithDeletedSendersOfTypesNOTX',
			array(
				'batchsize'	=> $batchSize,
				'typeids' => $typeidsToSkip,
			)
		);
		if (empty($rows))
		{
			$this->show_message(sprintf($this->phrase['core']['process_done']));
			$this->long_next_step();
			return;
		}
		else
		{
			$ids = array();
			$start = reset($rows);
			$start = $start['notificationid'];
			$end = null;
			foreach($rows AS $row)
			{
				$end = $ids[] = $row['notificationid'];
			}

			$assertor->assertQuery(
				'vBForum:notification',
				array(
					vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
					vB_dB_Query::CONDITIONS_KEY => array(
						array('field' => 'notificationid', 'value' => $ids, 'operator' =>  vB_dB_Query::OPERATOR_EQ),
					),
					'sender' => 0,
				)
			);

			$this->show_message(sprintf($this->phrase['core']['processed_records_x_y'], $start, $end));
			// startat not used, but we need to loop this step. Any other data you might pass through is not reliable due to
			// bugs w/ web interface
			return array('startat' => $end);
		}
	}
}

class vB_Upgrade_518a2 extends vB_Upgrade_Version
{
	public function step_1()
	{
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table_x'], 'package', 1, 1));
		$db = vB::getDbAssertor();
		$package = $db->getRow('package', array('class' => 'vBBlog'));
		if (!$package)
		{
			//we need this for the legacy but there is no longer a blog product.
			$result = $db->insert('package', array(
				'productid' => 'vbulletin',
				'class' => 'vBBlog'
			));
		}
		else
		{
			if ($package['productid'] != 'vbulletin')
			{
				$db->update('package', array('productid' => 'vbulletin'), array('packageid' => $package['packageid']));
			}
		}
	}

	public function step_2()
	{
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table_x'], 'contenttype', 1, 2));

		//legacy type information for the mobile API
		$db = vB::getDbAssertor();
		$contenttype = $db->getRow('vBForum:contenttype', array('class' => 'BlogEntry'));
		if (!$contenttype)
		{

			//we should have verified that this exists in step1
			$package = $db->getRow('package', array('class' => 'vBBlog'));

			$db->insert('vBForum:contenttype', array(
				'class' => 'BlogEntry',
				'packageid' => $package['packageid'],
				'canplace' => '0',
				'cansearch' => '0',
				'cantag' => '0',
				'canattach' => '1',
				'isaggregator' => '0'
			));
		}
	}


	public function step_3()
	{
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table_x'], 'contenttype', 2, 2));

		//legacy type information for the mobile API
		$db = vB::getDbAssertor();
		$contenttype = $db->getRow('vBForum:contenttype', array('class' => 'BlogComment'));
		if (!$contenttype)
		{
			//we should have verified that this exists in step1
			$package = $db->getRow('package', array('class' => 'vBBlog'));

			$db->insert('vBForum:contenttype', array(
				'class' => 'BlogComment',
				'packageid' => $package['packageid'],
				'canplace' => '0',
				'cansearch' => '0',
				'cantag' => '0',
				'canattach' => '1',
				'isaggregator' => '0'
			));
		}
	}
}

class vB_Upgrade_518a3 extends vB_Upgrade_Version
{
	/**
	 * Turn the user mention notification option on for new registrations by default
	 */
	public function step_1()
	{
		// Add 268435456 (general_quote) to the default value for notification_options
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'user', 1, 2),
			"ALTER TABLE " . TABLE_PREFIX . "user
			CHANGE notification_options notification_options INT UNSIGNED NOT NULL DEFAULT '1073741818'"
		);
	}

	/**
	 * Turn the quote notification option on for all current users by default
	 */
	public function step_2()
	{
		// Turn 536870912 on (general_quote) for all users
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'user', 2, 2),
			"UPDATE " . TABLE_PREFIX . "user
			SET notification_options = (notification_options | 536870912)"
		);
	}


	public function step_3()
	{
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table_x'], 'package', 1, 1));
		$db = vB::getDbAssertor();
		$package = $db->getRow('package', array('class' => 'vBCms'));
		if (!$package)
		{
			//we need this for the legacy but there is no longer a blog product.
			$result = $db->insert('package', array(
				'productid' => 'vbulletin',
				'class' => 'vBCms'
			));
		}
		else
		{
			if ($package['productid'] != 'vbulletin')
			{
				$db->update('package', array('productid' => 'vbulletin'), array('packageid' => $package['packageid']));
			}
		}
	}

	public function step_4()
	{
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table_x'], 'contenttype', 1, 1));

		//legacy type information for the mobile API
		$db = vB::getDbAssertor();
		$contenttype = $db->getRow('vBForum:contenttype', array('class' => 'Article'));
		if (!$contenttype)
		{
			//we should have verified that this exists in step1
			$package = $db->getRow('package', array('class' => 'vBCms'));

			$db->insert('vBForum:contenttype', array(
				'class' => 'Article',
				'packageid' => $package['packageid'],
				'canplace' => '0',
				'cansearch' => '0',
				'cantag' => '1',
				'canattach' => '1',
				'isaggregator' => '0'
			));
		}
	}
}

class vB_Upgrade_518a6 extends vB_Upgrade_Version
{
	/*
	 * Steps 1 & 2: VBV-14770 - Unset cansearch for the legacy content types that were added for vb4 mapi search reasons.
	 */
	public function step_1()
	{
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table_x'], 'contenttype', 1, 2));

		$assertor = vB::getDbAssertor();

		// This package was added/verified in 518a2
		$package = $assertor->getRow('package', array('class' => 'vBBlog'));
		$contenttypes = $assertor->getRows(
			'vBForum:contenttype',
			array(
				vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
				vB_dB_Query::CONDITIONS_KEY => array(
					array('field' => 'class',		'value' => array('BlogEntry', 'BlogComment'),	'operator' =>  vB_dB_Query::OPERATOR_EQ),
					array('field' => 'packageid',	'value' => $package['packageid'],	'operator' =>  vB_dB_Query::OPERATOR_EQ),
					array('field' => 'cansearch',	'value' => 1, 						'operator' =>  vB_dB_Query::OPERATOR_EQ),
				)
			)
		);
		foreach ($contenttypes AS $contenttype)
		{
			$assertor->update('vBForum:contenttype',
				array(// update values
					'cansearch' => 0,
				),
				array(// update conditions
					'contenttypeid' => $contenttype['contenttypeid']
				)
			);
		}
	}

	public function step_2()
	{
		$this->show_message(sprintf($this->phrase['vbphrase']['update_table_x'], 'contenttype', 1, 2));

		$assertor = vB::getDbAssertor();

		// This package was added/verified in 518a3
		$package = $assertor->getRow('package', array('class' => 'vBCms'));
		$contenttypes = $assertor->getRows(
			'vBForum:contenttype',
			array(
				vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
				vB_dB_Query::CONDITIONS_KEY => array(
					array('field' => 'class',		'value' => 'Article',				'operator' =>  vB_dB_Query::OPERATOR_EQ),
					array('field' => 'packageid',	'value' => $package['packageid'],	'operator' =>  vB_dB_Query::OPERATOR_EQ),
					array('field' => 'cansearch',	'value' => 1, 						'operator' =>  vB_dB_Query::OPERATOR_EQ),
				)
			)
		);
		foreach ($contenttypes AS $contenttype)
		{
			$assertor->update('vBForum:contenttype',
				array(// update values
					'cansearch' => 0,
				),
				array(// update conditions
					'contenttypeid' => $contenttype['contenttypeid']
				)
			);
		}
	}
}

class vB_Upgrade_519a5 extends vB_Upgrade_Version
{
	/** We have one new admin permission. We should set that based on existing admin permissions. Anyone with canadmincron should get the new canadminrss
	 *
	 */
	public function step_1($data = NULL)
	{
		$assertor = vB::getDbAssertor();
		// we should only run this once.
		$check = $assertor->assertQuery('vBInstall:upgradelog', array('script' => '519a5', 'step' => '1'));

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
			$assertor->assertQuery('vBInstall:updateAdminPerms', array('existing' => $adminperms['canadmincron'], 'new' => $adminperms['canadminrss']));
		}

	}

	/** This step is here to make the upgradelog check in step 1 work properly
	 *
	 */

	public function step_2()
	{
		$this->skip_message();
	}
}

class vB_Upgrade_519a6 extends vB_Upgrade_Version
{
	/**
	 * Step 1 - Add admin message for VBV-14825
	 */
	public function step_1()
	{
		$this->add_adminmessage(
			'after_upgrade_519_reinstall_products',
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

	/**
	 * Step 2 - Remove widget records with duplicate GUIDs (THIS EFFECTIVELY REMOVES CUSTOM MODULES) except for one.
	 *			Collapse any newly orphaned widgetdefinition & widgetinstance records onto the single
	 *			remaining widgetid for the dupe-guid widget records.
	 */
	public function step_2($data = null)
	{
		$assertor = vB::getDbAssertor();
		$dupeCheck = $assertor->getRow('vBInstall:getDuplicateWidgetGuids');
		if (!$dupeCheck OR empty($dupeCheck['guid']))
		{
			if ($data === null)
			{
				$this->skip_message();
				return;
			}
			else
			{
				$this->show_message(sprintf($this->phrase['core']['process_done']));
				return;
			}
		}
		else
		{
			if ($data === null)
			{
				$this->show_message($this->phrase['version']['519a6']['deduping_widget_records']);
			}
		}

		$widgetRecords = $assertor->getRows('widget', array('guid' => $dupeCheck['guid']));
		$defaultWidget = array();
		$defaultDefinitions = array();
		$counter = 0;
		foreach ($widgetRecords AS $widget)
		{
			if (empty($defaultWidget))
			{
				$defaultWidget = $widget;
				$definitionsQry = $assertor->getRows('widgetdefinition', array('widgetid' => $widget['widgetid']));
				foreach ($definitionsQry AS $def)
				{
					// Change below to = $def if we need to use the definition data for some kind of smart merging below.
					$defaultDefinitions[$def['name']] = true;
				}
			}
			else
			{
				$counter++;
				// Delete this duplicate widget record, remove the unnecessary widgetdefinition records,
				// and update the widgetinstance records
				$assertor->delete('widget', array('widgetid' => $widget['widgetid']));

				// If the definition already exists for the "default", delete the dupe. Else, update it to
				// make it owned by the default.
				$widgetDefinitions = $assertor->getRows('widgetdefinition', array('widgetid' => $widget['widgetid']));
				foreach ($widgetDefinitions AS $def)
				{
					if (isset($defaultDefinitions[$def['name']]))
					{
						// At the moment, we don't try any kind of merging. These will likely be overwritten by final_upgrade anyway
						// when default widgets are imported.
						$assertor->delete(
							'widgetdefinition',
							array(
								'widgetid' => $def['widgetid'],
								'name' => $def['name']
							)
						);
					}
					else
					{
						$assertor->update('widgetdefinition',
							array( // VALUES
								'widgetid' => $defaultWidget['widgetid'],
							),
							array( // CONDITION
								'widgetid' => $def['widgetid'],
								'name' => $def['name'],
							)
						);
					// Change below to = $def & add $defaultDefinitions[$def['name']]['widgetid'] = $defaultWidget['widgetid']
					// if we need to use the definition data for some kind of smart merging above.
						$defaultDefinitions[$def['name']] = true;
					}
				}

				// Fix now-orphaned widgetinstances
				$assertor->update('widgetinstance',
					array( // VALUES
						'widgetid' => $defaultWidget['widgetid']
					),
					array( // CONDITION
						'widgetid' => $widget['widgetid']
					)
				);
			}
		}

		$this->show_message(sprintf($this->phrase['version']['519a6']['deduped_x_for_widget_y'], $counter, $defaultWidget['template']));

		$startat = isset($data['startat']) ? intval($data['startat']) : 0;
		return array('startat' => ++$startat);
	}

	/**
	 * Step 3 - Make widget.guid UNIQUE
	 */
	public function step_3()
	{
		// Step 2 should have removed/collapsed any widget records with a duplicate GUID.
		$this->run_query(
			sprintf($this->phrase['core']['altering_x_table'], 'widget', 1, 1),
			"ALTER TABLE " . TABLE_PREFIX . "widget
			CHANGE guid guid char(150) NULL DEFAULT NULL UNIQUE"
		);
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 112194 $
|| #######################################################################
\*=========================================================================*/
