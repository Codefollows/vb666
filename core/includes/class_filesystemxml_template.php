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

/**
* Helper class to facilitate storing templates on the file system
*
* @package	vBulletin
*/
class vB_FilesystemXml_Template
{
	use vB_Trait_NoSerialize;

	/**
	* holds error string
	*
	* @var	array
	*/
	protected $errors = [];

	/**
	 * If we are not operating on a working directory we need an svn directory
	 * do the log lookups from.
	 */
	protected $base_svn_url = '';

	/**
	* Array that template information by product
	*
	* @var	array
	*/
	protected $productinfo = [
		'vbulletin' => [
			'relpath' => '/install/vbulletin-style.xml',
			'xmlgroup' => 'templategroup',
		],
		/*
		'googlelogin' => [
			'relpath' => '/packages/googlelogin/xml/product_googlelogin.xml',
			'xmlgroup' => 'templategroup',
		],
		'twitterlogin' => [
			'relpath' => '/packages/googlelogin/xml/product_twitterlogin.xml',
			'xmlgroup' => 'templategroup',
		],
		'shopify' => [
			'relpath' => '/packages/googlelogin/xml/product_shopify.xml',
			'xmlgroup' => 'templategroup',
		],*/
	];

	/**
	* Cached list of templates read from the file system
	*
	* @var	array
	*/
	protected $templatelist = null;

	/**
	* List of templates to be excluded from file writes
	*
	* @var	array
	*/
	protected $exclude = [
		'bbcode_video',
		'ad_test',
		'ad_test2',
	];

	/**
	* Gets the template directory
	*
	* @return	string - path to the template directory
	*/
	public function get_templatedirectory()
	{
		return realpath(DIR . DIRECTORY_SEPARATOR  . 'templates');
	}


	/**
	 * Gets the source for the svn template lookup.  If an svn url is given, use that
	 * Otherwise assume that the templates are in an svn working directory.
	 */
	protected function get_svn_template_source()
	{
		if ($this->base_svn_url)
		{
			return $this->base_svn_url . '/'  . 'templates';
		}
		else
		{
			return $this->get_templatedirectory();
		}
	}

	/**
	* Returns the path to a products xml file
	*
	* @param	string - name of the product
	*
	* @return	mixed - path to the product's xml file, false if not found
	*/
	protected function get_xmlpath($product)
	{
		if (isset($this->productinfo[$product]['relpath']))
		{
			return DIR . $this->productinfo[$product]['relpath'];
		}
		else
		{
			$this->errors[] = "Could not find the path to $product's xml file";
			return false;
		}
	}

	/**
	* Outputs an array of all products this helper class is setup up to process
	*
	* @return	array - strings of all product names with xml files
	*/
	public function get_all_products()
	{
		return array_keys($this->productinfo);
	}

	/**
	 *
	 */
	public function set_base_svn_url($url)
	{
		$this->base_svn_url = $url;
	}

// ################################################################################
// ##                    Master XML to Template Files
// ################################################################################

	/**
	* Takes a the file name of an xml file, and parses it into an xml object
	*
	* @param	string - file name (including path) of the xml file
	*
	* @return	array - parsed xml object of the file
	*/
	protected function parse_xml_from_file($filename)
	{
		$xmlobj = new vB_XML_Parser(false, $filename);

		if ($xmlobj->error_no() == 1 OR $xmlobj->error_no() == 2)
		{
			$this->errors[] = "Please ensure that the file $filename exists";
			return false;
		}

		if (!$parsed_xml = $xmlobj->parse())
		{
			$this->errors[] = 'xml error '.$xmlobj->error_string().', on line ' . $xmlobj->error_line();
			return false;
		}

		return $parsed_xml;
	}

	/**
	* Returns the parsed xml data that is pertinent to product
	*
	* @param	string - the product name
	*
	* @return	array - parsed xml pertinent to the product
	*/
	protected function get_template_xml($product)
	{
		// get the path name for the products's xml file
		if (!$productpath = $this->get_xmlpath($product))
		{
			return false;
		}

		// attempt to parse the xml
		if (!$parsed_xml = $this->parse_xml_from_file($productpath))
		{
			return false;
		}

		// now, grab only the appropriate data from the parsed xml array
		// making sure we can find the product template data in the parsed xml
		if (isset($this->productinfo[$product]['xmlgroup']) AND isset($parsed_xml[$this->productinfo[$product]['xmlgroup']]))
		{
			// wrap single xml element in an array if neccessary
			return vB_XML_Parser::getList($parsed_xml, $this->productinfo[$product]['xmlgroup']);
		}
		else
		{
			$this->errors[] = "Could not find $product template data in $productpath";
			return false;
		}
	}

	/**
	* Writes a single template to the file system
	*
	* @param	string - template
	* @param	string - the actual contents of the template
	* @param	string - the product to which the template belongs
	* @param	string - the version string
	* @param	string - the username of last editor
	* @param	string - the datestamp of last edit
	* @param	string - the old title if available
	* @param	array  - additional attributes, such as "compiletype"
	*
	* @return	bool - true if successful, false otherwise
	*/
	public function write_template_to_file($name, $text, $product, $version, $username, $datestamp, $oldname = '', $extra = [])
	{
		if (in_array($name, $this->exclude))
		{
			return true;
		}

		try
		{
			$template_path = $this->get_templatedirectory() . DIRECTORY_SEPARATOR . "$name.xml";

			if ($oldname and $oldname != $name)
			{
				$old_template_path = $this->get_templatedirectory() . DIRECTORY_SEPARATOR . "$oldname.xml";
				if (file_exists($old_template_path))
				{
					//$message = 'Auto export template name changed in db, renaming file to match.';
					if (file_exists($template_path))
					{
						unlink($template_path);
					}

					$cmd = "svn rename $old_template_path $template_path";
					shell_exec($cmd);
				}
			}
			//we only want to set the time/date the first time a template is saved.
			//additional updates will be drawn from the svn repository.
			//the goal is to avoid generating an svn conflict every time a template is
			//edited on two branches, while still preserving all of the legacy data
			//on the templates.

			$new_file = false;
			if (file_exists($template_path))
			{
				$parsed = $this->parse_xml_from_file($template_path);

				if (!empty($parsed['username']))
				{
					$username = $parsed['username'];
				}

				if (!empty($parsed['username']))
				{
					$datestamp = $parsed['date'];
				}
			}
			else
			{
				$new_file= true;
			}

			$attributes = [
				'product' => $product,
				'version' => $version,
				'username' => $username,
				'date' => $datestamp
			];

			//full is assumed, don't write it out
			if (!empty($extra['compiletype']) AND $extra['compiletype'] != 'full')
			{
				$attributes['compiletype'] = $extra['compiletype'];
			}

			$xml = new vB_XML_Builder(null, 'ISO-8859-1');
			$xml->add_tag('template', $text, $attributes, true);

			file_put_contents($template_path, $xml->fetch_xml());

			if ($new_file)
			{
				$cmd = "svn add $template_path";
				shell_exec($cmd);
			}
		}

		// if an error occured we dont care about the type, just make sure we track it
		catch (Exception $e)
		{
			$this->errors[] = "Could not write template $name to the file system";
			return false;
		}

		return true;
	}

	public function delete_template_file($name)
	{
		$template_path = $this->get_templatedirectory() . DIRECTORY_SEPARATOR . "$name.xml";
		if (file_exists($template_path))
		{
			$cmd = "svn --force delete $template_path";
			shell_exec($cmd);
		}
	}

	/**
	* Writes an entire product's templates to the filesystem from master xml
	*
	* @param	string - product name
	*
	* @return	bool - true if successful, false otherwise
	*/
	public function write_product_to_files($product)
	{
		// get the xml array that applies to the product
		if (!$template_xml = $this->get_template_xml($product))
		{
			return false;
		}

		// loop through each template group in the product
		foreach ($template_xml AS $templategroup)
		{
			$successful = true;

			// loop through each template in the template group
			// wrap template text in xml, and write to file system
			$tg_array = vB_XML_Parser::getList($templategroup, 'template');
			foreach ($tg_array AS $template)
			{
				if ($template['templatetype'] != 'template')
				{
					//we don't want no regular templates here, at least not right now.
					continue;
				}

				// attempt to output the template to the file system
				// if we failed, keep writing templates, but track that we failed
				if (!$this->write_template_to_file($template['name'], $template['value'], $product, $template['version'], $template['username'], $template['date']))
				{
					$successful = false;
				}
			}
		}

		return $successful;
	}


// ################################################################################
// ##                    Roll-up Functions
// ################################################################################

	/**
	* Rolls up all the template files for a product
	*
	* @param	string - the product id
	*
	* @return	bool - true if successful
	*/
	public function rollup_product_templates($product)
	{
		// get the path name for the products's xml file
		if (!$templates = $this->get_template_lists($product))
		{
			$this->errors[] = "Could not find any templates for product: $product";
			return false;
		}

		// prepare product xml using template array
		if ($product == 'vbulletin')
		{
			$xml = $this->get_vbulletin_template_xml($templates);
		}
		else
		{
			$xml = $this->get_product_template_xml($templates);
		}

		if (empty($xml))
		{
			$this->errors[] = "Could not prepare the XML for product: $product";
			return false;
		}

		// use a helper class to replace the changes to the style as
		// we write the master xml file to the filesystem
		require_once(DIR . '/includes/class_filesystemxml_replace.php');

		//We use different subclasses between the roll up and the remove for some reason.
		//This used to be based on the core "vbulletin" product vs an acual product xml file
		//but that got collapsed at some point but collapsed a different way in different places.
		//I'm don't think that we roll up product templates any longer or if that still works.
		$r = new vB_FilesystemXml_Replace_Product_Template($this->get_xmlpath($product), $xml);
		$success = $r->replace();
		unset($r);

		// if success is not set replace was successful, hence the strict equality check
		return $success !== false;
	}


	/**
	* Rolls up all the template files for a product
	*
	* @param	string - the product id
	*
	* @return	bool - true if successful
	*/
	public function remove_product_templates($product)
	{
		$path = $this->get_xmlpath($product);
		if (!$path)
		{
			return false;
		}

		return $this->remove_templates_from_xml($path);
	}

	public function write_product_xml($product, $xmltext)
	{
		$path = $this->get_xmlpath($product);
		if (!$path)
		{
			return;
		}

		file_put_contents($path, $xmltext);
	}

	public function remove_templates_from_xml($filepath)
	{
		// use a helper class to replace the changes to the style as
		// we write the master xml file to the filesystem
		require_once(DIR . '/includes/class_filesystemxml_replace.php');

		//see comment on the write function.
		$xml = "\n\t<templates></templates>";
		$r = new vB_FilesystemXml_Replace_Style_Template($filepath, $xml);
		return $r->replace();
	}


	/**
	* Gets all the templates from the file system and puts it into an array
	*
	* @param	string - (Optional) the product id, returns all products by default
	*
	* @return	array - information about all the templates stored in the file system
	*/
	protected function get_template_lists($product = null)
	{
		// check to see if we already have read and cached templates from filesystem
		if (!isset($this->templatelist))
		{
			$this->templatelist = [];

			$template_names = $this->get_template_list();
			$template_dir = $this->get_templatedirectory();

			foreach ($template_names AS $name)
			{
				$path_info = pathinfo($name);
				if ($parsed = $this->parse_xml_from_file($template_dir . '/' . $name))
				{
					$parsed['lastupdated'] = $parsed['date'];
					$this->templatelist[$parsed['product']][$path_info['filename']] = $parsed;
				}
			}

			$svn_data = $this->get_svn_data($template_names);
			if ($svn_data)
			{
				foreach ($this->templatelist AS $product_key => $list)
				{
					foreach ($list AS $name => $template)
					{
						if (isset($svn_data["$name.xml"]))
						{
							$this->templatelist[$product_key][$name]['lastupdated'] = $svn_data["$name.xml"]['lastupdated'];
							$this->templatelist[$product_key][$name]['username'] = $svn_data["$name.xml"]['username'];
						}
					}
				}
			}
		}

		// check if we only want to return a product specific template array
		// otherwise, return all product template array
		return !empty($product) ? $this->templatelist[$product] : $this->templatelist;
	}

	protected function get_vbulletin_template_xml($templates)
	{
		require_once(DIR . '/includes/adminfunctions_template.php');

		global $vbphrase;

		//In some cases (particularly unit tests) we call this without a vB database present, which cases this code to fail
		//The previous code used the vbphrase array to pull the phrases, which in this instance is blank.  That's concerning
		//but this is used for internal scripting only and it works that way so restore the previous behavior when
		//we don't have a database.

		//if we are only partially initialized, fall back on the local lists.
		if (vB::getRequest())
		{
			$template_groups = vB_Library::instance('template')->getTemplateGroupPhrases();
			$template_groups = vB_Api::instanceInternal('phrase')->renderPhrases($template_groups);
			$template_groups = $template_groups['phrases'];
		}
		else
		{
			$template_groups = $this->getTemplateGroups();
			foreach ($template_groups AS $key => $phrase)
			{
				$template_groups[$key] = $vbphrase[$phrase] ?? null;
			}
		}

		$groups = [];
		$ugcount = $ugtemplates = 0;
		foreach ($templates AS $name => $template)
		{
			$isgrouped = false;
			if (!empty($template_groups))
			{
				foreach (array_keys($template_groups) AS $group)
				{
					if (strpos(strtolower(" $name"), $group) == 1)
					{
						$groups[$group][$name] = $template;
						$isgrouped = true;
					}
				}
			}

			if (!$isgrouped)
			{
				if ($ugtemplates % 10 == 0)
				{
					$ugcount++;
				}
				$ugtemplates++;
				//sort ungrouped templates last.
				$ugcount_key = 'zzz' . str_pad($ugcount, 5, '0', STR_PAD_LEFT);
				$groups[$ugcount_key][$name] = $template;
				$template_groups[$ugcount_key] = (isset($vbphrase['ungrouped_templates_x']) ? construct_phrase($vbphrase['ungrouped_templates_x'], $ugcount) : null);
			}
		}

		if (!empty($templates))
		{
			ksort($groups);
		}
		unset ($templates);

		$xml = new vB_XML_Builder(null, 'ISO-8859-1');
		$xml->add_group('temp');
		foreach ($groups AS $group => $grouptemplates)
		{
			uksort($grouptemplates, "strnatcasecmp");
			$xml->add_group('templategroup', ['name' => ($template_groups[$group] ?? $group)]);

			$grouptemplates = array_map([$this, 'get_standard_template_record'], array_keys($grouptemplates), $grouptemplates);
			style_xml_export_templates($xml, $grouptemplates);

			$xml->close_group();
		}
		$xml->close_group();
		$text = $xml->fetch_xml();
		unset($xml);
		return substr($text, strpos($text, '<temp>') + strlen("<temp>"), -1 * strlen('</temp>\n'));
	}

	protected function get_product_template_xml($templates)
	{
		require_once(DIR . '/includes/adminfunctions_template.php');

		uksort($templates, "strnatcasecmp");

		$xml = new vB_XML_Builder(null, 'ISO-8859-1');
		$xml->add_group('temp');
		$xml->add_group('templates');

		$templates = array_map([$this, 'get_standard_template_record'], array_keys($templates), $templates);
		style_xml_export_templates($xml, $templates);

		$xml->close_group();
		$xml->close_group();
		$text = $xml->fetch_xml();
		unset($xml);
		return substr($text, strpos($text, '<temp>') + strlen("<temp>"), -1 * strlen('</temp>\n'));
	}

	// Our template handling is all over the map.  Which makes consolidating the logic for xml generation
	// complicated.  This is a shim to paper over the difference in formats.
	private function get_standard_template_record(string $name, array $template) : array
	{
		$template['title'] = $name;
		$template['templatetype'] = 'template';
		$template['dateline'] = $template['lastupdated'];
		$template['template'] = $template['value'];

		//username and version are the same.

		if (!isset($template['compiletype']))
		{
			$template['compiletype'] = (!empty($template['textonly']) ? 'textonly' : 'full');
		}

		unset($template['value'], $template['lastupdated']);
		return $template;
	}

	public function get_template_list()
	{
		$template_dir = $this->get_templatedirectory();
		foreach (new DirectoryIterator($template_dir) AS $fileinfo)
		{
			if (!$fileinfo->isFile())
			{
				continue;
			}

			$path_info = pathinfo($fileinfo->getFilename());
			if ($path_info['extension'] != 'xml')
			{
				continue;
			}

			$template_names[] = $fileinfo->getFilename();
		}
		return $template_names;
	}

	//The $minsvnversion doesn't work the same way as it does for the original function in that it actually works.
	//In most cases the original will override the param.  The only place that uses this function is a script that's
	//defunct at this point (the original function did work at some point but adding the caching broke it and it appears
	//that nobody noticed.
	//
	//If we need skip_revisions we can do it by detecting templates that match that revision and then calling the
	//"ls" command on the revision prior to get the versions for those templates prior to that revision (if there were
	//any revision after then those would be the one displayed initially).  But since that parameter appears unused
	//I'm declining to implement it.
	public function get_svn_data($template_filenames, $minsvnversion = 1)
	{
		//When we started this we had a bunch of templates that predated the individual SVN files, so using the "last updated"
		//version in SVN was no good because the initial checkin didn't correspond to to actual time the template was updated.
		//So we took the approach of exporting the creation date for the template in the XML and then not using the "added"
		//commit for the lastupdate calculations from SVN. If we don't have the file in the svn data (because it hasn't been
		//updated) then we use the timestamp in the file. We don't really need to do that anymore -- the date we add a new
		//template to SVN is just fine as the initial last updated -- however we don't want to *change* any existing timestamps
		//on templates we ship.  This has some implications for "is this template newer than the customizations" that could
		//prove a pain for people.  So we'll hardcode the affected templates (the ls function doesn't give us any insight into
		//whether the file was updated or not) and the last revision at the time of the cutover.  If the last revisions is
		//still the same as the historical one, exclude from the set to match prior behavior.  New templates will use the
		//SVN last update from the get go and won't be a problem.
		//
		//Will need to refresh the list when we actually switch from get_svn_data or update it to produce the same results
		//as this function on newly added templates.
		//
		//We should periodically cull this list for any templates that we no longer need the special behavior for
		//and remove them.  When it is empty we can remove the timestamp from the xml files in the template directory.
		$special = [
			'admin_configuresite_homepage.xml' => '102727',
			'construct_select_options.xml' => '68184',
			'css_b_comp_menu_vert.css.xml' => '85850',
			'css_b_list.css.xml' => '88567',
			'css_fonts.css.xml' => '80330',
			'css_links.xml' => '70916',
			'css_sprite_icons_general.css.xml' => '96747',
			'css_unreset.css.xml' => '70888',
			'inlinemod_channelselect.xml' => '104214',
			'password_requirements.xml' => '103632',
			'privacy_policy_page.xml' => '98303',
			'widget_cmschanneldisplay_list.xml' => '78194',
			'widget_contentslider_admin.xml' => '82920',
			'widget_displaytemplate.xml' => '98296',
			'widget_search_admin.xml' => '82920',
		];

		$template_dir = $this->get_svn_template_source();

		$cmd = 'svn ls --xml "' . $template_dir . '"';
		$text = shell_exec($cmd);
		$xmlobj = new vB_XML_Parser($text);
		$parsed_xml = $xmlobj->parse();

		if ($parsed_xml === false)
		{
			$this->errors[] = sprintf ("xml error '%s', on line '%d'", $xmlobj->error_string(), $xmlobj->error_line());
			return false;
		}

		if (!is_array($parsed_xml))
		{
			// There are no log entries within the <log> tags. It's just \r\n.
			return false;
		}

		$data = [];
		$logentries = vB_XML_Parser::getList($parsed_xml['list'], 'entry');
		foreach ($logentries AS $entry)
		{
			$name = $entry['name'];
			$author = $entry['commit']['author'];
			$last_updated = strtotime($entry['commit']['date']);
			$revision = intval($entry['commit']['revision']);

			//If the latest revision is less than the minrevision then we a revision for this file in range to return.
			//I'm not actually sure what this is used for but the settemplateversion script sets it.
			if (in_array($name, $template_filenames) AND $revision >= $minsvnversion AND ($special[$name] ?? -1) != $revision)
			{
				$data[$name] = [
					'lastupdated' => $last_updated,
					'username' => $author,
				];
			}
		}

		//Due to some weirdness this got moved (which counts as modified in the original) then deleted and then added.
		//The result is that the original skips the add but picks up the older commit and logs it as the date.  Since
		//we want this to be bug compatible with the original for historic data we can't just exclude this from the return
		//and fix the file date so the rollup is the same.  Instead we'll detect if we're picking upt the "Add" timestamp
		//and then substitute the orginal.  (We don't just want to exclude that commit and check that it's missing because
		//we don't want to continue returning it if it's deleted).
		if (($data['widget_container_admin.xml']['lastupdated'] ?? 0) == 1556322811)
		{
			$data['widget_container_admin.xml'] = [
			'lastupdated' => 1412018945,
			'username' => 'dgrove',
			];
		}

		ksort($data);
		return $data;
	}

	//we need a way to get these offline and we can't call the library where we store the
	//usual copy for normal operation.
	//Keep in sync with the list from vB_Library_Template
	private function getTemplateGroups()
	{
		$groups = [
			'admin'          => 'group_admin',
			'article'        => 'group_article',
			'bbcode'         => 'group_bbcode',
			'blog'           => 'group_blog',
			'blogadmin'      => 'group_blogadmin',
			'color'          => 'group_color',
			'contententry'   => 'group_contententry',
			'conversation'   => 'group_conversation',
			'css'            => 'group_css',
			'dialog'         => 'group_dialog',
			'display'        => 'group_display',
			'editor'         => 'group_editor',
			'error'          => 'group_error',
			'group'          => 'group_sgroup',
			'humanverify'    => 'group_human_verification',
			'inlinemod'      => 'group_inlinemod',
			'link'           => 'group_link',
			'login'          => 'group_login',
			'media'          => 'group_media',
			'memberlist'     => 'group_memberlist',
			'modify'         => 'group_modify',
			'page'           => 'group_page',
			'pagenav'        => 'group_pagenav',
			'photo'          => 'group_photo',
			'picture'        => 'group_picture_templates',
			'pmchat'         => 'group_pmchat',
			'privatemessage' => 'group_private_message',
			'profile'        => 'group_profile',
			'screenlayout'   => 'group_screen',
			'search'         => 'group_search',
			'sgadmin'        => 'group_sgadmin',
			'site'           => 'group_site',
			'subscription'   => 'group_paidsubscription',
			'subscriptions'  => 'group_subscription',
			'sprite'         => 'group_sprite',
			'tag'            => 'group_tag',
			'top_menu'       => 'group_top_menu',
			'userfield'      => 'group_user_profile_field',
			'usersettings'   => 'group_usersetting',
			'video'          => 'group_video',
			'widget'         => 'group_widget',
		];
		return $groups;
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 115786 $
|| #######################################################################
\*=========================================================================*/
