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

// note #1: arrays used by functions in this code are declared at the bottom of the page

/**
* Expand and collapse button labels
*/
define('EXPANDCODE', '&laquo; &raquo;');
define('COLLAPSECODE', '&raquo; &laquo;');

/**
* Size in rows of template editor <select>
*/
define('TEMPLATE_EDITOR_ROWS', 25);

global $vbphrase;

/**
* Initialize the IDs for colour preview boxes
*/
$numcolors = 0;

// #############################################################################
/**
* Checks the style id of a template item and works out if it is inherited or not
*
* @param	integer	Style ID from template record
*
* @return	string	CSS class name to use to display item
*/
function fetch_inherited_color($itemstyleid, $styleid)
{
	switch ($itemstyleid)
	{
		case $styleid: // customized in current style, or is master set
			if ($styleid == -1)
			{
				return 'col-g';
			}
			else
			{
				return 'col-c';
			}
		case -1: // inherited from master set
		case 0:
			return 'col-g';
		default: // inhertited from parent set
			return 'col-i';
	}

}

// #############################################################################
/**
* Saves the correct style parentlist to each style in the database
*/
function build_template_parentlists()
{
	$db = vB::getDbAssertor();
	$styles = $db->assertQuery('vBForum:fetchstyles2');
	foreach ($styles as $style)
	{
		$parentlist = vB_Library::instance('Style')->fetchTemplateParentlist($style['styleid']);
		if ($parentlist != $style['parentlist'])
		{
			$db->assertQuery('vBForum:updatestyleparent', [
				'parentlist' => $parentlist,
				'styleid' => $style['styleid'],
			]);
		}
	}
}

// #############################################################################
/**
* Builds all data from the template table into the fields in the style table
*
* @param	boolean $renumber -- no longer used.  Feature removed.
* @param	boolean	If true, will fix styles with no parent style specified
* @param	string	If set, will redirect to specified URL on completion
* @param	boolean	If true, reset the master cache
* @param	boolean	Whether to print status/edit information
*/
function build_all_styles($renumber = 0, $install = 0, $goto = '', $resetcache = false, $printInfo = true)
{
	// -----------------------------------------------------------------------------
	// -----------------------------------------------------------------------------
	// this bit of text is used for upgrade scripts where the phrase system
	// is not available it should NOT be converted into phrases!!!
	$phrases = [
		'master_style' => 'MASTER STYLE',
		'done' => 'Done',
		'style' => 'Style',
		'styles' => 'Styles',
		'templates' => 'Templates',
		'css' => 'CSS',
		'stylevars' => 'Stylevars',
		'replacement_variables' => 'Replacement Variables',
		'controls' => 'Controls',
		'rebuild_style_information' => 'Rebuild Style Information',
		'updating_style_information_for_each_style' => 'Updating style information for each style',
		'updating_styles_with_no_parents' => 'Updating style sets with no parent information',
		'updated_x_styles' => 'Updated %1$s Styles',
		'no_styles_needed_updating' => 'No Styles Needed Updating',
	];
	$vbphrase = vB_Api::instanceInternal('phrase')->fetch($phrases);
	foreach ($phrases AS $key => $val)
	{
		if (!isset($vbphrase["$key"]))
		{
			$vbphrase["$key"] = $val;
		}
	}
	// -----------------------------------------------------------------------------
	// -----------------------------------------------------------------------------

	$isinstaller = (defined('VB_AREA') AND (VB_AREA == 'Upgrade' OR VB_AREA == 'Install'));
	$doOutput = ($printInfo AND !$isinstaller);

	$form_tags = !empty($goto);
	if ($doOutput)
	{
		echo "<!--<p>&nbsp;</p>-->
		<blockquote>" . ($form_tags ? '<form>' : '') . "<div class=\"tborder\">
		<div class=\"tcat\" style=\"padding:4px\" align=\"center\"><b>" . $vbphrase['rebuild_style_information'] . "</b></div>
		<div class=\"alt1\" style=\"padding:4px\">\n<blockquote>
		";
		vbflush();
	}

	// useful for restoring utterly broken (or pre vb3) styles
	if ($install)
	{
		if ($doOutput)
		{
			echo "<p><b>" . $vbphrase['updating_styles_with_no_parents'] . "</b></p>\n<ul class=\"smallfont\">\n";
			vbflush();
		}

		vB::getDbAssertor()->assertQuery('updt_style_parentlist');
	}

	if ($doOutput)
	{
		// the main bit.
		echo "<p><b>" . $vbphrase['updating_style_information_for_each_style'] . "</b></p>\n";
		vbflush();
	}

	build_template_parentlists();

	$styleactions = ['dostylevars' => 1, 'doreplacements' => 1, 'doposteditor' => 1];
	if (defined('NO_POST_EDITOR_BUILD'))
	{
		$styleactions['doposteditor'] = 0;
	}

	if ($error = build_style(-1, $vbphrase['master_style'], $styleactions, '', '', $resetcache, $printInfo))
	{
		return $error;
	}

	if ($doOutput)
	{
		echo "</blockquote></div>";
		if ($form_tags)
		{
			echo "
			<div class=\"tfoot\" style=\"padding:4px\" align=\"center\">
			<input type=\"button\" class=\"button\" value=\" " . $vbphrase['done'] . " \" onclick=\"window.location='$goto';\" />
			</div>";
		}
		echo "</div>" . ($form_tags ? '</form>' : '') . "</blockquote>
		";
		vbflush();
	}

	vB_Library::instance('Style')->buildStyleDatastore();
}

// #############################################################################
/**
* Displays a style rebuild (build_style) in a nice user-friendly info page
*
* @param	integer	Style ID to rebuild
* @param	string	Title of style
* @param	boolean	Build CSS? (no longer used)
* @param	boolean	Build Stylevars?
* @param	boolean	Build Replacements?
* @param	boolean	Build Post Editor?
*/
function print_rebuild_style($styleid, $title = '', $docss = 1, $dostylevars = 1, $doreplacements = 1, $doposteditor = 1, $printInfo = true)
{
	$vbphrase = vB_Api::instanceInternal('phrase')->fetch(['master_style', 'rebuild_style_information', 'updating_style_information_for_x', 'done']);
	$styleid = intval($styleid);

	if (empty($title))
	{
		if ($styleid == -1)
		{
			$title = $vbphrase['master_style'];
		}
		else
		{
			$getstyle = vB_Library::instance('Style')->fetchStyleByID($styleid);

			if (!$getstyle)
			{
				return;
			}

			$title = $getstyle['title'];
		}
	}

	if ($printInfo AND (VB_AREA != 'Upgrade') AND (VB_AREA != 'Install'))
	{
		echo "<p>&nbsp;</p>
		<blockquote><form><div class=\"tborder\">
		<div class=\"tcat\" style=\"padding:4px\" align=\"center\"><b>" . $vbphrase['rebuild_style_information'] . "</b></div>
		<div class=\"alt1\" style=\"padding:4px\">\n<blockquote>
		<p><b>" . construct_phrase($vbphrase['updating_style_information_for_x'], $title) . "</b></p>
		<ul class=\"lci\">\n";
		vbflush();
	}

	$actions = [
		'dostylevars' => $dostylevars,
		'doreplacements' => $doreplacements,
		'doposteditor' => $doposteditor
	];
	build_style($styleid, $title, $actions, false, '', 1, $printInfo);

	if ($printInfo AND (VB_AREA != 'Upgrade') AND (VB_AREA != 'Install'))
	{
		echo "</ul>\n<p><b>" . $vbphrase['done'] . "</b></p>\n</blockquote></div>
		</div></form></blockquote>
		";
		vbflush();
	}

	vB_Library::instance('Style')->buildStyleDatastore();

}

// #############################################################################

/**
 *	Deletes the old style css directory on disk
 *
 *	@param int $styleid
 *	@param string $dir -- the "direction" of the css to delete.  Either 'ltr' or 'rtl' (there are actually two directories per style)
 *	@param bool $contentsonly	-- whether to delete the newly empty directory (if we are deleting the contents to rewrite there is no need)
 */
function delete_style_css_directory($styleid, $dir = 'ltr', $contentsonly = false)
{
	$styledir = vB_Api::instanceInternal('style')->getCssStyleDirectory($styleid, $dir);
	$styledir = $styledir['directory'];

	if (is_dir($styledir))
	{
		$dirhandle = opendir($styledir);

		if ($dirhandle)
		{
			// loop through the files in the style folder
			while (($fname = readdir($dirhandle)) !== false)
			{
				$filepath = $styledir . '/' . $fname;

				// remove just the files inside the directory
				// and this also takes care of the '.' and '..' folders
				if (!is_dir($filepath))
				{
					@unlink($filepath);
				}
			}
			// Close the handle
			closedir($dirhandle);
		}

		// Remove the style directory
		if (!$contentsonly)
		{
			@rmdir($styledir);
		}
	}
}

// #############################################################################
/**
* Attempts to create a new css file for this style
*
* @param	string	CSS filename
* @param	string	CSS contents
*
* @return	boolean	Success
*/
function write_css_file($filename, $contents)
{
	// attempt to write new css file - store in database if unable to write file
	if ($fp = @fopen($filename, 'wb') AND !is_demo_mode())
	{
		fwrite($fp, $contents);
		@fclose($fp);
		return true;
	}
	else
	{
		@fclose($fp);
		return false;
	}
}

/**
 *	Writes style css directory to disk, this includes SVG templates
 *
 *	@param int $styleid
 *	@param string $parentlist -- csv list of ancestors for this style
 *	@param string $dir -- the "direction" of the css to write.  Either 'ltr' or 'rtl' (there are actually two directories per style)
 */
function write_style_css_directory($styleid, $parentlist, $dir = 'ltr')
{
	//verify that we have or can create a style directory
	$styledir = vB_Api::instanceInternal('style')->getCssStyleDirectory($styleid, $dir);
	$styledir = $styledir['directory'];

	//if we have a file that's not a directory or not writable something is wrong.
	if (file_exists($styledir) AND (!is_dir($styledir) OR !is_writable($styledir)))
	{
		return false;
	}

	//clear any old files.
	if (file_exists($styledir))
	{
		delete_style_css_directory($styleid, $dir, true);
	}

	//create the directory -- if it still exists try to continue with the existing dir
	if (!file_exists($styledir))
	{
		if (!mkdir($styledir, 0777, true))
		{
			return false;
		}
	}

	//check for success.
	if (!is_dir($styledir) OR !is_writable($styledir))
	{
		return false;
	}

	// NOTE: SVG templates are processed along with CSS templates.
	// I observed no unwanted behavior by doing this, and if in the
	// future, CSS and SVG templates need to be processed separately
	// we can refactor this at that point.

	//write out the files for this style.
	$parentlistarr = explode(',', $parentlist);
	$set = vB::getDbAssertor()->assertQuery('template_fetch_css_svg_templates', ['styleidlist' => $parentlistarr]);

	//collapse the list.
	$css_svg_templates = [];
	foreach ($set as $row)
	{
		$css_svg_templates[] = $row['title'];
	}

	$stylelib = vB_Library::instance('Style');
	$stylelib->switchCssStyle($styleid, $css_svg_templates);

	// Get new css cache bust
	$stylelib->setCssFileDate($styleid);
	$cssfiledate = $stylelib->getCssFileDate($styleid);

	// Keep pseudo stylevars in sync with the css.php and sprite.php handling
	set_stylevar_ltr(($dir == 'ltr'));
	set_stylevar_meta($styleid);

	$base = get_base_url_for_css();
 	if ($base === false)
	{
		return false;
	}

	$templates = [];
	$templates_not_in_rollups = [];
	foreach ($css_svg_templates AS $title)
	{
		$text = vB_Template::create($title)->render(true);
		$text = css_file_url_fix($base, $text);
		$text = vB_String::getCssMinifiedText($text);

		$templates[$title] = $text;
		$templates_not_in_rollups[$title] = true;
	}

	static $vbdefaultcss = [], $cssfiles = [];

	if (empty($vbdefaultcss))
	{
		$cssfilelist = vB_Api::instanceInternal('product')->loadProductCssRollups();

		if (empty($cssfilelist['vbulletin']))
		{
			$vbphrase = vB_Api::instanceInternal('phrase')->fetch(['could_not_open_x']);
			echo construct_phrase($vbphrase['could_not_open_x'], DIR . '/includes/xml/cssrollup_vbulletin.xml');
			exit;
		}

		$data = $cssfilelist['vbulletin'];
		unset($cssfilelist['vbulletin']);

		if (!is_array($data['rollup'][0]))
		{
			$data['rollup'] = [$data['rollup']];
		}

		foreach ($data['rollup'] AS $file)
		{
			foreach ($file['template'] AS $name)
			{
				$vbdefaultcss["$file[name]"] = $file['template'];
			}
		}

		foreach ($cssfilelist AS $css_file => $data)
		{
			$products = vB::getDatastore()->getValue('products');
			if ($data['product'] AND empty($products["$data[product]"]))
			{
				// attached to a specific product and that product isn't enabled
				continue;
			}

			if (!is_array($data['rollup'][0]))
			{
				$data['rollup'] = [$data['rollup']];
			}

			$cssfiles[$css_file]['css'] = $data['rollup'];
		}
	}


	foreach ($cssfiles AS $css_file => $files)
	{
		if (is_array($files['css']))
		{
			foreach ($files['css'] AS $file)
			{
				$result = process_css_rollup_file($file['name'], $file['template'], $templates,
					$styleid, $styledir, $templates_not_in_rollups, $vbdefaultcss);
				if ($result === false)
				{
					return false;
				}
			}
		}
	}

	foreach ($vbdefaultcss AS $xmlfile => $files)
	{
		$result = process_css_rollup_file($xmlfile, $files, $templates, $styleid, $styledir, $templates_not_in_rollups);
		if ($result === false)
		{
			return false;
		}
	}

	foreach ($templates_not_in_rollups AS $title => $dummy)
	{
		if (!write_css_file("$styledir/$cssfiledate-$title", $templates[$title]))
		{
			return false;
		}
	}

	return true;
}


//I'd call this a hack but there probably isn't a cleaner way to do this.
//The css is published to a different directory than the css.php file
//which means that relative urls that works for css.php won't work for the
//published directory.  Unfortunately urls from the webroot don't work
//because the forum often isn't located at the webroot and we can only
//specify urls from the forum root.  And css doens't provide any way
//of setting a base url like html does.  So we are left to "fixing"
//any relative urls in the published css.
//
//We leave alone any urls starting with '/', 'http', 'https:', 'data:', and '#'
//URLs starting with # are for SVG sprite filter elements (not entirely clear on
//what these are for but *probably* internal links in the SVG file that need to
//stay relative to that file and would be broken by adding the base url)
//there are other valid urls, but nothing that people should be
//using in our css files.
function css_file_url_fix($base, $text)
{
	$re = '#(url\(\s*["\']?)([^)]*\))#';
	$callback = function($matches) use ($base)
	{
		//we aren't really guarenteed that contents is the full contents of the
		//url(...) block.  There are several was a closing paren can sneak in so that
		//we match that but the css parser will skip it.  For instance
		//url("http://www.google.com?x=()") is a valid url and won't break the css
		//however all we really care about is that we get the prefix portion of the
		//url, that we stop before we hit the next url, and that we preserve anything
		//that we don't match -- we just need to be careful to do that last bit instead
		//of assuming we're replacing everything.
		list($all, $prefix, $contents) = $matches;

		//if we have a prefix that suggests some kind of absolute url, leave it alone
		if (preg_match('#^(?:/|http:|https:|data:|\#)#', $contents))
		{
			return $all;
		}
		else
		{
			//otherwise insert the base
			return $prefix . $base . $contents;
		}
	};

	$text = preg_replace_callback($re, $callback, $text);
	return $text;
}



/**
 *	Gets the base url for images in the css files
 *
 *	This will be the site root unless there is a CDN configured
 *	the image will all be specified in the css to the url the css.php file is located.
 *	When writing the css to disk we make the urls absolute because the paths are different
 *	from the static css files.
 */
function get_base_url_for_css()
{
	/*
		We need the frontend base url.  This should be always available but if not
		try to check the backend config.  Not sure this is needed but the old code does it.
		By default this isnt set, but the site administrator can set it.
		If all this fails, we give up and return false
	*/

	$base = vB::getDatastore()->getOption('cdnurl');
	if (!$base)
	{
		$baseurl = '';
		if ($frontendurl = vB::getDatastore()->getOption('frontendurl'))
		{
			$baseurl = $frontendurl;
		}
		else
		{
			$config = vB::getConfig();
			$baseurl = $config['Misc']['baseurl'];
		}

		$base = $baseurl;
	}

	if (substr($base, -1, 1) != '/')
	{
		$base .= '/';
	}

	return $base;
}


function process_css_rollup_file(
	$file,
	$templatelist,
	$templates,
	$styleid,
	$styledir,
	&$templates_not_in_rollups,
	&$vbdefaultcss = []
)
{
	if (!is_array($templatelist))
	{
		$templatelist = [$templatelist];
	}

	if ($vbdefaultcss AND $vbdefaultcss["$file"])
	{
		// Add these templates to the main file rollup
		$vbdefaultcss["$file"] = array_unique(array_merge($vbdefaultcss["$file"], $templatelist));
		return true;
	}

	$count = 0;
	$text = "";
	foreach ($templatelist AS $name)
	{
		unset($templates_not_in_rollups[$name]);
		$template = $templates[$name];
		if ($count > 0)
		{
			$text .= "\r\n\r\n";
			$template = preg_replace("#@charset [^;]*;#i", "", $template);
		}
		$text .= $template;
		$count++;
	}

	$stylelib = vB_Library::instance('Style');
	$cssfiledate = $stylelib->getCssFileDate($styleid);

	if (!write_css_file("$styledir/$cssfiledate-$file", $text))
	{
		return false;
	}

	return true;
}

// #############################################################################
/**
* Converts all data from the template table for a style into the style table
*
* @param	integer	Style ID
* @param	string	Title of style
* @param	array	Array of actions set to true/false: dostylevars/doreplacements (doposteditor is not longer used)
* @param	string	List of parent styles
* @param	string	Indent for HTML printing
* @param	boolean	Reset the master cache
* @param	boolean	Whether to print status/edit information
*/
function build_style($styleid, $title = '', $actions = [], $parentlist = '', $indent = '', $resetcache = false, $printInfo = true)
{
	//not sure if this is required.
	require_once(DIR . '/includes/adminfunctions.php');

	$db = vB::getDbAssertor();
	$datastore = vB::getDatastore();
	/** @var vB_Library_Style */
	$styleLib = vB_Library::instance('Style');

	$isinstaller = (defined('VB_AREA') AND (VB_AREA == 'Upgrade' OR VB_AREA == 'Install'));
	$doOutput = ($printInfo AND !$isinstaller);

	//we only use the phrases if we are doing some output.  No need to load them if we aren't going to use them.
	//note that they are cached so we aren't querying the DB for each style
	if ($doOutput)
	{
		$vbphrase = vB_Api::instanceInternal('phrase')->fetch(['templates', 'stylevars', 'replacement_variables', 'done']);
	}

	//don't propagate any local changes to actions to child rebuilds.
	$originalactions = $actions;
	if ($styleid != -1)
	{
		$usecssfiles = $styleLib->useCssFiles($styleid);

		//this is some *old* code.  I think it's due to some fields that writing css files
		//relies on not getting set, but it's been copied, tweaked, and mangled since cssasfiles
		//referred to the vB3 css and not the css template sheets so it's not 100% if it's needed
		//any longer.
		if (($actions['doreplacements'] OR $actions['dostylevars']) AND $usecssfiles)
		{
			$actions['doreplacements'] = true;
		}

		// VBV-16291 certain actions, like write_css_file(), relies on in-memory cached items that would normally be cleared
		// if going through the styleLIB's buildStyle(). To avoid stale data issues in upgrade, we manually clear it here.
		$styleLib->internalCacheClear($styleid);
		if ($doOutput)
		{
			// echo the title and start the listings
			echo "$indent<li><b>$title</b> ... <span class=\"smallfont\">";
			vbflush();
		}

		// build the templateid cache
		if (!$parentlist)
		{
			$parentlist = $styleLib->fetchTemplateParentlist($styleid);
		}

		$templatelist = $styleLib->buildTemplateIdCache($styleid, 1, $parentlist);

		$styleupdate = [];
		$styleupdate['templatelist'] = $templatelist;
		if ($doOutput)
		{
			echo "($vbphrase[templates]) ";
			vbflush();
		}


		// style vars
		if ($actions['dostylevars'])
		{
			// new stylevars
			static $master_stylevar_cache = null;
			static $resetcachedone = false;
			if ($resetcache AND !$resetcachedone)
			{
				$resetcachedone = true;
				$master_stylevar_cache = null;
			}

			if ($master_stylevar_cache === null)
			{
				$master_stylevar_cache = $styleLib->getRootStylevars();
			}

			$newstylevars = $master_stylevar_cache;

			if (substr(trim($parentlist), 0, -3) != '')
			{
				$newstylevars = $styleLib->addStylevarOverrides($parentlist, $master_stylevar_cache);
			}

			$styleupdate['newstylevars'] = serialize($newstylevars);

			if ($doOutput)
			{
				echo "($vbphrase[stylevars]) ";
				vbflush();
			}
		}

		// cache special templates
		if ($actions['doreplacements'])
		{
			// get replacements for this style -- could probably be collapsed further.
			$replacement_cache = [];
			$templateids = unserialize($templatelist);

			if ($templateids)
			{
				$templates = $db->select('template', ['templateid' => $templateids, 'templatetype' => 'replacement'], false, ['title', 'template']);
				foreach ($templates AS $template)
				{
					$replacement_cache[$template['title']] = $template;
				}
			}

			// rebuild the replacements field for this style
			$replacements = [];
			if (is_array($replacement_cache))
			{
				foreach ($replacement_cache AS $template)
				{
					// set the key to be a case-insentitive preg find string
					$replacementkey = '#' . preg_quote($template['title'], '#') . '#si';
					$replacements[$replacementkey] = $template['template'];
				}
				$styleupdate['replacements'] = serialize($replacements);
			}
			else
			{
				//this feels like it should be an empty array.
				$styleupdate['replacements'] = "''";
			}

			if ($doOutput)
			{
				echo "($vbphrase[replacement_variables]) ";
				vbflush();
			}
		}

		// do the style update query
		if (!empty($styleupdate))
		{
			$styleupdate['styleid'] = $styleid;
			$styleupdate[vB_dB_Query::TYPE_KEY] = vB_dB_Query::QUERY_UPDATE;
			$db->assertQuery('vBForum:style', $styleupdate);
		}

		//write out the new css -- do this *after* we update the style record
		if ($usecssfiles)
		{
			//restore the current style settings to avoid affecting downstream display.
			$originaldirection = vB_Template_Runtime::fetchStyleVar('textdirection');
			$originalstyleid = vB::getCurrentSession()->get('styleid');

			foreach (['ltr', 'rtl'] AS $direction)
			{
				if (!write_style_css_directory($styleid, $parentlist, $direction))
				{
					$error = fetch_error("rebuild_failed_to_write_css");
					if ($doOutput)
					{
						echo $error;
					}
					else
					{
						return $error;
					}
				}
			}

			set_stylevar_ltr($originaldirection == 'ltr');
			set_stylevar_meta($originalstyleid);
		}

		// finish off the listings
		if ($doOutput)
		{
			echo "</span><b>" . $vbphrase['done'] . "</b>.<br />&nbsp;</li>\n";
			vbflush();
		}
	}

	$childsets = $db->getRows('style', ['parentid' => $styleid]);
	if (count($childsets))
	{
		if ($doOutput)
		{
			echo "$indent<ul class=\"ldi\">\n";
		}

		foreach ($childsets as $childset)
		{
			if ($error = build_style($childset['styleid'], $childset['title'], $originalactions, $childset['parentlist'], $indent . "\t", $resetcache, $printInfo))
			{
				return $error;
			}
		}

		if ($doOutput)
		{
			echo "$indent</ul>\n";
		}
	}

	//We want to force a fastDS rebuild, but we can't just call rebuild. There may be dual web servers,
	// and calling rebuild only rebuilds one of them.
	$options = $datastore->getValue('miscoptions');
	$options['tmtdate'] = vB::getRequest()->getTimeNow();
	$datastore->build('miscoptions', serialize($options), 1);
}

// #############################################################################
//the print_style functions are closely tied to the template.php style manager and are
//unlikely to be useful for anything else.  In particular the logic replies on
//all of the styles being present (potentially aside from the read protected themes)
//along with some javascript set up that only happens there.  This isn't something that
//needs fixing or particularly can be fixed but it's something to be aware of.

/**
* Prints out a style editor block
*
* @param	integer	Style ID
* @param	array	Style info array
*/
function print_style($masterset, $style, $colspan)
{
	global $vbulletin;

	$styleid = $style['styleid'];

	//we really shouldn't be looking up query string/post info inside of functions
	//but at least we can pull it out to the top of the first function instead of
	//embedding it
	$titlesonly = $vbulletin->GPC['titlesonly'];
	$expandset = $vbulletin->GPC['expandset'];
	$group = $vbulletin->GPC['group'];
	$searchstring = $vbulletin->GPC['searchstring'];

	//this is *probably* always set, based on where the function gets called,
	//but the original code had a guard on it and so we'll make sure.
 	$templateid = (!empty($vbulletin->GPC['templateid']) ? $vbulletin->GPC['templateid'] : null);

	$vbphrase = vB_Api::instanceInternal('phrase')->fetch([
		'add_child_style', 'add_new_template', 'all_template_groups', 'allow_user_selection',
		'collapse_all_template_groups', 'collapse_template_group', 'collapse_templates',
		'collapse_x', 'common_templates', 'controls', 'custom_templates', 'customize_gstyle',
		'delete_style', 'edit_display_order', 'download', 'edit', 'edit_fonts_colors_etc', 'edit_settings_gstyle',
		'edit_style_options', 'edit_templates', 'expand_all_template_groups', 'expand_template_group',
		'expand_templates', 'expand_x', 'go', 'replacement_variables', 'revert_all_stylevars',
		'revert_all_templates', 'revert_gcpglobal', 'stylevareditor', 'template_is_customized_in_this_style',
		'template_is_inherited_from_a_parent_style', 'template_is_unchanged_from_the_default_style',
		'template_options', 'view_original', 'view_your_forum_using_this_style', 'x_templates', 'choose_action', 'color_key'
	]);

	$vb5_config = vB::getConfig();
	//in debug mode we show the MASTER style as a the root so we need an extra depth level
	if ($vb5_config['Misc']['debug'] AND $styleid != -1)
	{
		$style['depth']++;
	}


	$showstyle = ($expandset == 'all' OR $expandset == $styleid);

	// show the header row
	print_style_header_row($vbphrase, $style, $group, $showstyle);
	if ($showstyle)
	{
		//Need to figure out how to ensure that the templatelist is properly passed so
		//we don't need this silliness.  But for now pulling it to the top level function
		//for visibility.
		//master style doesn't really exist and therefore can't be loaded.
		if (empty($style['templatelist']))
		{
	 		$style = vB_Library::instance('Style')->fetchStyleById($styleid);
		}

		/*
			If $style was passed into this function, templatelist might still be a serialized string.
			Note, I only ever ran into this once, and it never happened again, so it might've been
			some old cached data somewhere (cached before the removal of templatelist from the stylecache,
			maybe?)
		 */
		if (is_string($style['templatelist']))
		{
			$style['templatelist'] = unserialize($style['templatelist']);
		}

		$groups = vB_Library::instance('template')->getTemplateGroupPhrases();
		$template_groups = vB_Api::instanceInternal('phrase')->renderPhrases($groups);
		$template_groups = $template_groups['phrases'];

		$templates = print_style_get_templates($vbulletin->db, $style, $masterset, $template_groups, $searchstring, $titlesonly);
	 	if ($templates)
		{
			echo '<tr><td colspan="' . $colspan . '" style="padding: 0px;">' . "\n";
			print_style_body($vbphrase, $templates, $template_groups, $style, $group, $templateid, $expandset);
			echo '</td></tr>' . "\n";
		}
	}
}


// Function to break up print_style into something readable.  Should be considered "private" to this file
function print_style_header_row($vbphrase, $style, $group, $showstyle)
{
	$styleid = $style['styleid'];

	$styleLib = vB_Library::instance('style');
	$showReadonlyMarking = !$styleLib->checkStyleReadProtection($styleid, $style, true);

	$cells = [];

	//cell for style title block.
	$title = $style['title'];
	if ($showReadonlyMarking)
	{
		$title = $style['title'] . ' <span class="acp-style-readonly-mark"></span>';
	}

	$label = '&nbsp; ' . construct_depth_mark($style['depth'], '- - ');
	if ($styleid != -1)
	{
		$selectname = 'userselect[' . $styleid . ']';
		if (!$showReadonlyMarking)
		{
			$attributes = [
				'value' => 1,
				'class' => 'bginput js-template-userselect',
				'data-styleid' => $style['styleid'],
				'data-parentid' => $style['writableparentid'],
			];
			if ($style['userselect'])
			{
				$attributes['checked'] = 'checked';
			}

			$userselect = construct_input('checkbox', $selectname, $attributes, false);
			$label = '<label title="' . $vbphrase['allow_user_selection'] . '">' . $label . $userselect . '</label>';
		}
		else
		{
			//We probably don't want to ever have the hidden themes user selectable.  But turning
			//the flag off as a random side effect also seems like a poor choice.  The quick save
			//assumes that all of the styles have a value for the select input and, because checkboxes
			//don't show in forms if they are unchecked there isn't a good way to inspect the form
			//to determine if the checkbox is present or not.
			//This preserves the value if we don't allow it to be set.
			construct_hidden_code($selectname, $style['userselect']);
		}
	}

	//we use the anchor to scroll the list when returning to the open style.  This avoids the user having to constantly find
	//the style they're working on in a long list.
	$anchor = '<span id="styleheader' . $styleid . '" ></span>';
	// This is not switched to buildHomeUrl() because that method doesn't handle query params yet.
	$forumhome_url = vB5_Route::buildUrl('home|fullurl', [], ['styleid' => $styleid]);
	$cells[] = $anchor . $label . '<a href="' . $forumhome_url . '" target="_blank" title="' . $vbphrase['view_your_forum_using_this_style'] . '">' . $title . '</a>';

	//cell for tools.
	$displayorder = '';
	if ($styleid != -1)
	{
		$attributes = [
			'value' => $style['displayorder'],
			'class' => 'bginput display-order',
			'title' => $vbphrase['edit_display_order'],
		];
		$displayorder = construct_input('text', 'displayorder[' . $styleid . ']', $attributes);
	}

	$menuname = ($styleid != -1 ? 'styleEdit_' . $styleid : 'styleEdit_m');
	$selecttext = print_style_get_command_select($vbphrase, $menuname, $style);
	$data = ['menu' => $menuname];
	$goButton = construct_event_button($vbphrase['go'], 'js-button-template-go', $data);

	if ($showstyle)
	{
		$code = COLLAPSECODE;
		$tooltip = $vbphrase['collapse_templates'];
	}
	else
	{
		$code = EXPANDCODE;
		$tooltip = $vbphrase['expand_templates'];
	}

	$data = [
		'group' => $group,
		'styleid' => ($showstyle ? '' : $styleid),
	];
	$expandCollapse = construct_event_button($code, 'js-button-template-expand', $data, [], ['title' => $tooltip]);


	$cells[] = $displayorder;
	$cells[] = $selecttext . $goButton;
	$cells[] = $expandCollapse;

	print_cells_row2($cells, 'nowrap ' . fetch_row_bgclass());
}

function print_style_get_command_select($vbphrase, $menuname, $style)
{
	$styleid = $style['styleid'];

	//this is probably not useful any longer.  If this returns read only the style got filtered
	//out of the list before we got here.
	$styleLib = vB_Library::instance('style');
	$styleIsReadonly = !$styleLib->checkStyleReadProtection($styleid, $style);

	//canadminstyles no and canadmintemplates yes isn't really a sensible combination as a result some of the behavior in this
	//instance is inconsistant.  I'm not sure if you can even get to this function without it an if so if you should.
	//We need to make sure that only canadminstyles and both are well handled.
	$userContext = vB::getUserContext();
	$canadminstyles = $userContext->hasAdminPermission('canadminstyles');
	$canadmintemplates = $userContext->hasAdminPermission('canadmintemplates');

	//this should be all possible option groups.  We'll control via permissions later on.
	//Only groups with options will be displayed.  Order in this array controls display order.
	//We assume that everything except the default "choose" option is in an option group
	//Note that if display_order is not unique then order is undefined (and can vary based on inconsequential changes)
	//We do it this way so that we don't have to consider whether to display an option in the order that we
	//intend to display it -- thus allowing us to avoid massive duplication of logic.
	$optgroups = [
		'template_options' => [],
		'edit_fonts_colors_etc' => [],
		'edit_style_options' => [],
	];

	//these options for for the more detailed template edit options.
	if ($canadmintemplates)
	{
		if (!$styleIsReadonly)
		{
			//these do not apply to the master style
			if ($styleid != -1)
			{
				$optgroups['template_options'][30] = ['phrase' => 'revert_all_templates', 'action' => 'template_revertall'];
			}
		}

		$optgroups['edit_style_options'][30] = ['phrase' => 'download', 'action' => 'template_download'];
	}

	//we now allow this regardless of which permission the admin has
	if (!$styleIsReadonly)
	{
		$optgroups['template_options'][10] = ['phrase' => 'edit_templates', 'action' => 'template_templates'];
		$optgroups['template_options'][20] = ['phrase' => 'add_new_template', 'action' => 'template_addtemplate'];
	}

	if ($canadminstyles)
	{
		if (!$styleIsReadonly)
		{
			$optgroups['edit_fonts_colors_etc'][20] = ['phrase' => 'stylevareditor', 'action' => 'stylevar'];

			if ($styleid != -1)
			{
				$optgroups['edit_fonts_colors_etc'][30] = ['phrase' => 'revert_all_stylevars', 'action' => 'stylevar_revertall'];
			}

			$optgroups['edit_fonts_colors_etc'][40] = ['phrase' => 'replacement_variables', 'action' => 'css_replacements'];
		}

		if ($styleid != -1)
		{
			$optgroups['edit_style_options'][10] = ['phrase' => 'edit_settings_gstyle', 'action' => 'template_editstyle'];
			$optgroups['edit_style_options'][40] = ['phrase' => 'delete_style', 'action' => 'template_delete', 'class' => 'col-c'];
		}

		$optgroups['edit_style_options'][20] = ['phrase' => 'add_child_style', 'action' => 'template_addstyle'];
	}


	$optgrouptext = '';
	foreach ($optgroups AS $groupphrase => $options)
	{
		if ($options)
		{
			ksort($options);
			$optgrouptext .= '<optgroup label="' . $vbphrase[$groupphrase] . '">' . "\n";
			foreach ($options AS $option)
			{
				$class = '';
				if (isset($option['class']))
				{
					$class = ' class="' . $option['class'] . '"';
				}

				$optgrouptext .= '<option value="' . $option['action'] . '"' . $class . '>' . $vbphrase[$option['phrase']] . "</option>\n";
			}
			$optgrouptext .= "</optgroup>\n";
		}
	}

	$select = "<select name=\"$menuname\" data-styleid=\"$styleid\" class=\"js-template-menu bginput picklist\">
		<option selected=\"selected\">" . $vbphrase['choose_action'] . "</option>
		$optgrouptext
		</select>"; //a newline here causes a display change.

	return $select;
}

// Function to break up print_style into something readable.  Should be considered "private" to this file
function print_style_get_templates($db, $style, $masterset, $template_groups, $searchstring, $titlesonly)
{
	$searchconds = [];

	if (!empty($searchstring))
	{
		$containsSearch = "LIKE('%" . $db->escape_string_like($searchstring) . "%')";
		if ($titlesonly)
		{
			$searchconds[] = "t1.title $containsSearch";
		}
		else
		{
			$searchconds[] = "( t1.title $containsSearch OR template_un $containsSearch ) ";
		}
	}

	// not sure if this if is necesary any more.  The template list should always be set
	// at this point and if it isn't things don't work properly.  However I need to stop
	// fixing things at some point and wrap this up.
	if (!empty($style['templatelist']) AND is_array($style['templatelist']))
	{
		$templateids = implode(',' , $style['templatelist']);
		if (!empty($templateids))
		{
			$searchconds[] = "templateid IN($templateids)";
		}
	}

	$sql = "
		SELECT templateid, IF(((t1.title LIKE '%.css') AND (t1.title NOT like 'css_%')),
			CONCAT('csslegacy_', t1.title), title) AS title, styleid, templatetype, dateline, username
		FROM " . TABLE_PREFIX . "template AS t1
		WHERE
			templatetype IN('template', 'replacement') AND " . implode(' AND ', $searchconds) . "
		ORDER BY title
	";

	$templates = $db->query_read($sql);

	// just exit if no templates found
	$numtemplates = $db->num_rows($templates);
	if ($numtemplates == 0)
	{
		return false;
	}

	$result = [
		'replacements' => [],
		'customtemplates' => [],
		'maintemplates' => [],
	];

	while ($template = $db->fetch_array($templates))
	{
		$templateid = $template['templateid'];
		if ($template['templatetype'] == 'replacement')
		{
			$result['replacements'][$templateid] = $template;
		}
		else
		{
			$title = $template['title'];

			$groupname = explode('_', $title, 2);
			$groupname = $groupname[0];

			if ($template['styleid'] != -1 AND !isset($masterset[$title]) AND !isset($template_groups[$groupname]))
			{
				$result['customtemplates'][$templateid] = $template;
			}
			else
			{
				$result['maintemplates'][$templateid] = $template;
			}
		}
	}

	return $result;
}

// Function to break up print_style into something readable.  Should be considered "private" to this file
function print_style_body($vbphrase, $templates, $template_groups, $style, $group, $selectedtemplateid, $expandset)
{
	$userContext = vB::getUserContext();
	$canadmintemplates = $userContext->hasAdminPermission('canadmintemplates');

	$directionLeft = vB_Template_Runtime::fetchStyleVar('left');
	$styleid = $style['styleid'];

	//it's really not clear *why* we need to do this.  But in some instances we need an id of 0
	//instead of -1 for the master style.  Need to figure that out, but for now we'll keep the
	//previous behavior.
	//It seems like the JS variables use 0 as the key for the master style.
	$THISstyleid = $styleid;
	if ($styleid == -1)
	{
		$THISstyleid = 0;
	}

	echo '
		<!-- start template list for style "' . $style['styleid'] . '" -->
		<table cellpadding="0" cellspacing="10" border="0" align="center">
			<tr valign="top">
				<td>
					<select data-styleid = "' . $THISstyleid . '" name="tl' . $THISstyleid . '" class="darkbg js-templatelist" size="' . TEMPLATE_EDITOR_ROWS . '" style="width:450px">
					<option class="templategroup" value="">- - ' . construct_phrase($vbphrase['x_templates'], $style['title']) . ' - -</option>
	';

	// custom templates
	if (!empty($templates['customtemplates']))
	{
		echo "<optgroup label=\"\">\n";
		echo "\t<option class=\"templategroup\" value=\"\">" . $vbphrase['custom_templates'] . "</option>\n";

		foreach ($templates['customtemplates'] AS $template)
		{
			echo construct_template_option($template, $selectedtemplateid, $styleid);
			vbflush();
		}

		echo '</optgroup>';
	}

	// main templates
	if ($canadmintemplates AND !empty($templates['maintemplates']))
	{
		$lastgroup = '';
		$echo_ul = 0;

		$prefixgroups = getPrefixGroups($template_groups);
		foreach ($templates['maintemplates'] AS $template)
		{
			$showtemplate = 1;
			if (!empty($lastgroup) AND isTemplateInGroup($template['title'], $lastgroup, $prefixgroups))
			{
				if ($group == 'all' OR $group == $lastgroup)
				{
					echo construct_template_option($template, $selectedtemplateid, $styleid);
					vbflush();
				}
			}
			else
			{
				foreach ($template_groups AS $thisgroup => $display)
				{
					if ($lastgroup != $thisgroup AND $echo_ul == 1)
					{
						echo "</optgroup>";
						$echo_ul = 0;
					}

					if (isTemplateInGroup($template['title'], $thisgroup, $prefixgroups))
					{
						$lastgroup = $thisgroup;
						if ($group == 'all' OR $group == $lastgroup)
						{
							//don't select a group if we are selecting a template
							$selected = '';
							if ($group == $thisgroup AND !$selectedtemplateid)
							{
								$selected = ' selected="selected"';
							}

							echo "<optgroup label=\"\">\n";
							echo "\t<option class=\"templategroup\" value=\"[]\"" . $selected . ">" .
								construct_phrase($vbphrase['x_templates'], $display) . " &laquo;</option>\n";
							$echo_ul = 1;
						}
						else
						{
							echo "\t<option class=\"templategroup\" value=\"[$thisgroup]\">" . construct_phrase($vbphrase['x_templates'], $display) . " &raquo;</option>\n";
							$showtemplate = 0;
						}
						break;
					}
				} // end foreach ($template_groups

				if ($showtemplate)
				{
					echo construct_template_option($template, $selectedtemplateid, $styleid);
					vbflush();
				}
			} // end if template string same AS last
		}
	}

	$data =  ['group' => 'all','styleid' => $expandset];
	$expandbutton = construct_event_button(EXPANDCODE, 'js-button-template-expand', $data, [], ['title' => $vbphrase['expand_all_template_groups']]);

	$data =  ['group' => '','styleid' => $expandset];
	$collapsebutton = construct_event_button(COLLAPSECODE, 'js-button-template-expand', $data, [], ['title' => $vbphrase['collapse_all_template_groups']]);

	$data =  ['styleid' => $THISstyleid, 'request' => ''];
	$customizebutton = construct_event_button($vbphrase['customize_gstyle'], 'js-button-template-action', $data, [], ['id' => 'cust' . $THISstyleid]);

	$title = trim(construct_phrase($vbphrase['expand_x'], '')) . '/' . trim(construct_phrase($vbphrase['collapse_x'], ''));
	$menuexpandbutton = construct_event_button($title, 'js-button-template-action', $data, [], ['id' => 'expa' . $THISstyleid]);

	$editbutton = construct_event_button($vbphrase['edit'], 'js-button-template-action', $data, [], ['id' => 'edit' . $THISstyleid]);

	$data['request'] = 'vieworiginal';
	$viewbutton = construct_event_button($vbphrase['view_original'], 'js-button-template-action', $data, [], ['id' => 'orig' . $THISstyleid]);

	$data['request'] = 'killtemplate';
	$killbutton = construct_event_button($vbphrase['revert_gcpglobal'], 'js-button-template-action', $data, [], ['id' => 'kill' . $THISstyleid]);

	echo '
		</select>
	</td>';

	echo "
	<td width=\"100%\" align=\"center\" valign=\"top\">
	<table cellpadding=\"4\" cellspacing=\"1\" border=\"0\" class=\"tborder\" width=\"300\">
	<tr align=\"center\">
		<td class=\"tcat\"><b>$vbphrase[controls]</b></td>
	</tr>
	<tr>
		<td class=\"alt2\" align=\"center\" style=\"font: 11px tahoma, verdana, arial, helvetica, sans-serif\"><div style=\"margin-bottom: 4px;\">\n" .
			$customizebutton . "\n" .
			$menuexpandbutton .
			"</div>\n" .
			$editbutton . "\n" .
			$viewbutton . "\n" .
			$killbutton . "\n" .
			"<div class=\"darkbg\" style=\"margin: 4px; padding: 4px; border: 2px inset; text-align: " . $directionLeft . "\" id=\"helparea$THISstyleid\">
				" . construct_phrase($vbphrase['x_templates'], '<b>' . $style['title'] . '</b>') . "
			</div>\n" .
			$expandbutton . "\n",
			'<b>' . $vbphrase['all_template_groups'] . "</b>\n" .
			$collapsebutton . "\n" .
		"</td>
	</tr>
	</table>
	<br />
	<table cellpadding=\"4\" cellspacing=\"1\" border=\"0\" class=\"tborder\" width=\"300\">
	<tr align=\"center\">
		<td class=\"tcat\"><b>$vbphrase[color_key]</b></td>
	</tr>
	<tr>
		<td class=\"alt2\">
		<div class=\"darkbg\" style=\"margin: 4px; padding: 4px; border: 2px inset; text-align: " . $directionLeft . "\">
		<span class=\"col-g\">" . $vbphrase['template_is_unchanged_from_the_default_style'] . "</span><br />
		<span class=\"col-i\">" . $vbphrase['template_is_inherited_from_a_parent_style'] . "</span><br />
		<span class=\"col-c\">" . $vbphrase['template_is_customized_in_this_style'] . "</span>
		</div>
		</td>
	</tr>
	</table>
	";

	echo "\n</td>\n</tr>\n</table>\n";
	echo "<!-- end template list for style '$style[styleid]' -->\n\n";
}


// This is a mess we need to clean up, but currently we consider a template to be part of
// a group if the group name is a prefix of a template name.  But in some cases we have two
// groups where one is the prefix of the other then a template can have both groups as a
// prefix and depending on order it can be placed in one group or another.
//
// We  should make the grouping explicit in the database or, failing that, require the names
// to be something like groupname_remainder (most follow this pattern) so that we can explicitly
// check.  But renaming templates is not without risk and there are enough exceptions to
// want to avoid doing that right now.  We could hardcode the culprits (page/pagnav and blog/blogadmin)
// but let's catch all instances.
function getPrefixGroups(array $template_groups) : array
{
	$prefixgroups = [];
	foreach ($template_groups AS $shortname => $dummy)
	{
		$re = '#^' . preg_quote($shortname, '#') . '.#i';
		foreach ($template_groups AS $longname => $dummy)
		{
			if (preg_match($re, $longname))
			{
				$prefixgroups[$shortname][] = $longname;
			}
		}
	}
	return $prefixgroups;
}

/**
 * Tests if the given template is part of the template "group"
 *
 * @param	string	$templatename
 * @param	string	$groupname
 * @param array $prefixgroups The result of the getPrefixGroups funcion
 *
 * @return	bool	True if the given template is part of the group, false otherwise
 */
function isTemplateInGroup(string $templatename, string $groupname, array $prefixgroups) : bool
{
	if (preg_match('#^' . preg_quote($groupname, '#') . '#i', $templatename))
	{
		// if the template matches a longer groupname then it's not in this group.
		foreach ($prefixgroups[$groupname] ?? [] AS $longname)
		{
			if (preg_match('#^' . preg_quote($longname, '#') . '#i', $templatename))
			{
				return false;
			}
		}

		return true;
	}

	return false;
}

// #############################################################################
/**
* Constructs a single template item for the style editor form
*
* @param	array	Template info array
* @param	integer	Style ID of style being shown
* @param	boolean	No longer used
* @param	boolean	HTMLise template titles?
*
* @return	string	Template <option>
*/
function construct_template_option($template, $selectedtemplateid, $styleid)
{
	$selected = '';
	if ($selectedtemplateid == $template['templateid'])
	{
		$selected = ' selected="selected"';
	}

	//deal with the title.  The csslegacy thing is a hack we can probably remove -- but that will
	//require a bit of work to make sure we don't break anything.
	$title = $template['title'];
	$title = preg_replace('#^csslegacy_#i', '', $title);
	$title = htmlspecialchars_uni($title);

	$i = $template['username'] . ';' . $template['dateline'];

	$tsid = '';

	if ($styleid == -1)
	{
		$class = '';
		$value = $template['templateid'];
	}
	else
	{
		switch ($template['styleid'])
		{
			// template is inherited from the master set
			case 0:
			case -1:
			{
				$class = 'col-g';
				$value = '~';
				break;
			}

			// template is customized for this specific style
			case $styleid:
			{
				$class = 'col-c';
				$value = $template['templateid'];
				break;
			}

			// template is customized in a parent style - (inherited)
			default:
			{
				$class = 'col-i';
				$value = '[' . $template['templateid'] . ']';
				$tsid = $template['styleid'];
				break;
			}
		}
	}

	$option = "\t" . '<option class="template-option ' . $class . '" value="' . $value . '" ';
	if ($tsid)
	{
		$option .= 'tsid="' . $tsid . '" ';
	}
	$option .= 'i="' . $i . '"' . $selected . '>' . $title . "</option>\n";

	return $option;
}

// #############################################################################
function activateCodeMirror()
{
	//also requires the logic in vbulletin_templatemgr.js which is included as part
	//of the template code that calls this.
	register_js_phrase('fullscreen');
	?>
				<script src="core/clientscript/codemirror/lib/codemirror.js?v=<?php echo SIMPLE_VERSION ?>"></script>

				<script src="core/clientscript/codemirror/mode/xml/xml.js?v=<?php echo SIMPLE_VERSION ?>"></script>
				<script src="core/clientscript/codemirror/mode/javascript/javascript.js?v=<?php echo SIMPLE_VERSION ?>"></script>
				<script src="core/clientscript/codemirror/mode/css/css.js?v=<?php echo SIMPLE_VERSION ?>"></script>
				<!-- <script src="core/clientscript/codemirror/mode/clike/clike.js?v=<?php echo SIMPLE_VERSION ?>"></script> -->
				<script src="core/clientscript/codemirror/mode/htmlmixed/htmlmixed.js?v=<?php echo SIMPLE_VERSION ?>"></script>
				<script src="core/clientscript/codemirror/mode/vbulletin/vbulletin.js?v=<?php echo SIMPLE_VERSION ?>"></script>

				<script src="core/clientscript/codemirror/addon/mode/overlay.js?v=<?php echo SIMPLE_VERSION ?>"></script>
				<script src="core/clientscript/codemirror/addon/selection/active-line.js?v=<?php echo SIMPLE_VERSION ?>"></script>
				<script src="core/clientscript/codemirror/addon/edit/matchbrackets.js?v=<?php echo SIMPLE_VERSION ?>"></script>
				<script src="core/clientscript/codemirror/addon/fold/foldcode.js?v=<?php echo SIMPLE_VERSION ?>"></script>
				<script src="core/clientscript/codemirror/addon/search/search.js?v=<?php echo SIMPLE_VERSION ?>"></script>
				<script src="core/clientscript/codemirror/addon/search/searchcursor.js?v=<?php echo SIMPLE_VERSION ?>"></script>
				<script src="core/clientscript/codemirror/addon/search/match-highlighter.js?v=<?php echo SIMPLE_VERSION ?>"></script>
				<script src="core/clientscript/codemirror/addon/edit/closetag.js?v=<?php echo SIMPLE_VERSION ?>"></script>
				<script src="core/clientscript/codemirror/addon/hint/show-hint.js?v=<?php echo SIMPLE_VERSION ?>"></script>
				<script src="core/clientscript/codemirror/addon/hint/vbulletin-hint.js?v=<?php echo SIMPLE_VERSION ?>"></script>
	<?php
}

function activateCodeMirrorPHP($ids)
{
	$vbphrase = vB_Api::instanceInternal('phrase')->fetch(['fullscreen']);
	?>
				<script src="core/clientscript/codemirror/lib/codemirror.js?v=<?php echo SIMPLE_VERSION ?>"></script>
				<script src="core/clientscript/codemirror/mode/clike/clike.js?v=<?php echo SIMPLE_VERSION ?>"></script>
				<script src="core/clientscript/codemirror/mode/php/php.js?v=<?php echo SIMPLE_VERSION ?>"></script>

				<script src="core/clientscript/codemirror/addon/selection/active-line.js?v=<?php echo SIMPLE_VERSION ?>"></script>
				<script src="core/clientscript/codemirror/addon/edit/matchbrackets.js?v=<?php echo SIMPLE_VERSION ?>"></script>
				<script src="core/clientscript/codemirror/addon/fold/foldcode.js?v=<?php echo SIMPLE_VERSION ?>"></script>
				<script src="core/clientscript/codemirror/addon/search/match-highlighter.js?v=<?php echo SIMPLE_VERSION ?>"></script>
				<script src="core/clientscript/codemirror/addon/edit/closetag.js?v=<?php echo SIMPLE_VERSION ?>"></script>
				<script src="core/clientscript/codemirror/addon/hint/show-hint.js?v=<?php echo SIMPLE_VERSION ?>"></script>
			<script type="text/javascript">
			<!--
			window.onload = function() {
				$(["<?php echo implode('","', $ids)?>"]).each(function(){
					setUpCodeMirror({
						textarea_id : this,
						phrase_fullscreen : "<?php echo $vbphrase['fullscreen']; ?>",
						mode: "application/x-httpd-php-open"
					})
				});
			};
			//-->
			</script><?php
}

// ###########################################################################################
// START XML STYLE FILE FUNCTIONS

function get_style_export_xml
(
	$styleid,
	$product,
	$product_version,
	$title,
	$mode,
	$remove_guid = false,
	$stylevars_only = false,
	$stylevar_groups = []
)
{
	global $vbulletin;

	/* Load the master 'style' phrases and then
	build a local $template_groups array using them. */
	$vbphrase = vB_Api::instanceInternal('phrase')->fetchByGroup('style', -1);

	$groups = vB_Library::instance('template')->getTemplateGroupPhrases();
	$template_groups = vB_Api::instanceInternal('phrase')->renderPhrases($groups);
	$template_groups = $template_groups['phrases'];

	$vb5_config = vB::getConfig();
	if (!$vb5_config)
	{
		$vb5_config =& vB::getConfig();
	}

	if ($styleid == -1)
	{
		// set the style title as 'master style'
		$style = ['title' => $vbphrase['master_style']];
		$sqlcondition = "styleid = -1";
		$parentlist = "-1";
		$is_master = true;
	}
	else
	{
		// query everything from the specified style
		$style = $vbulletin->db->query_first("
			SELECT *
			FROM " . TABLE_PREFIX . "style
			WHERE styleid = " . $styleid
		);

		//export as master -- export a style with all changes as a new master style.
		if ($mode == 2)
		{
			//only allowed in debug mode.
			if (!$vb5_config['Misc']['debug'])
			{
				print_cp_no_permission();
			}

			// get all items from this style and all parent styles
			$sqlcondition = "templateid IN(" . implode(',', unserialize($style['templatelist'])) . ")";
			$sqlcondition .= " AND title NOT LIKE 'vbcms_grid_%'";
			$parentlist = $style['parentlist'];
			$is_master = true;
			$title = $vbphrase['master_style'];
		}

		//export with parent styles
		else if ($mode == 1)
		{
			// get all items from this style and all parent styles (except master)
			$sqlcondition = "styleid <> -1 AND templateid IN(" . implode(',', unserialize($style['templatelist'])) . ")";
			//remove the master style id off the end of the list
			$parentlist = substr(trim($style['parentlist']), 0, -3);
			$is_master = false;
		}

		//this style only
		else
		{
			// get only items customized in THIS style
			$sqlcondition = "styleid = " . $styleid;
			$parentlist = $styleid;
			$is_master = false;
		}
	}

	if ($product == 'vbulletin')
	{
		$sqlcondition .= " AND (product = '" . vB::getDbAssertor()->escape_string($product) . "' OR product = '')";
	}
	else
	{
		$sqlcondition .= " AND product = '" . vB::getDbAssertor()->escape_string($product) . "'";
	}

	// set a default title
	if ($title == '' OR $styleid == -1)
	{
		$title = $style['title'];
	}

	if (!empty($style['dateline']))
	{
		$dateline = $style['dateline'];
	}
	else
	{
		$dateline = vB::getRequest()->getTimeNow();
	}

	// --------------------------------------------
	// query the templates and put them in an array

	$templates = [];

	if (!$stylevars_only)
	{
		$gettemplates = $vbulletin->db->query_read("
			SELECT
				title,
				templatetype,
				username,
				dateline,
				version,
				compiletype,
				IF(templatetype = 'template', template_un, template) AS template
			FROM " . TABLE_PREFIX . "template
			WHERE $sqlcondition
			ORDER BY title
		");

		$ugcount = $ugtemplates = 0;
		while ($gettemplate = $vbulletin->db->fetch_array($gettemplates))
		{
			switch($gettemplate['templatetype'])
			{
				case 'template': // regular template

					// if we have ad template, and we are exporting as master, make sure we do not export the ad data
					if (substr($gettemplate['title'], 0, 3) == 'ad_' AND $mode == 2)
					{
						$gettemplate['template'] = '';
					}

					$isgrouped = false;
					foreach (array_keys($template_groups) AS $group)
					{
						if (strpos(strtolower(" $gettemplate[title]"), $group) == 1)
						{
							$templates["$group"][] = $gettemplate;
							$isgrouped = true;
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
						$templates[$ugcount_key][] = $gettemplate;
						$template_groups[$ugcount_key] = construct_phrase($vbphrase['ungrouped_templates_x'], $ugcount);
					}
				break;

				case 'replacement': // replacement
					$templates[$vbphrase['replacement_var_special_templates']][] = $gettemplate;
				break;
			}
		}
		unset($gettemplate);
		$vbulletin->db->free_result($gettemplates);
		if (!empty($templates))
		{
			ksort($templates);
		}

	}

	// --------------------------------------------
	// fetch stylevar-dfns

	$stylevarinfo = get_stylevars_for_export($product, $parentlist, $stylevar_groups);
	$stylevar_cache = $stylevarinfo['stylevars'];
	$stylevar_dfn_cache = $stylevarinfo['stylevardfns'];

	if (empty($templates) AND empty($stylevar_cache) AND empty($stylevar_dfn_cache))
	{
		throw new vB_Exception_AdminStopMessage('download_contains_no_customizations');
	}

	// --------------------------------------------
	// now output the XML

	$xml = new vB_XML_Builder();
	$rootAttributes = [
		'name' => $title,
		'vbversion' => $product_version,
		'product' => $product,
		'type' => $is_master ? 'master' : 'custom',
		'dateline' => $dateline,
	];

	if (isset($style['styleattributes']) AND $style['styleattributes'] != vB_Library_Style::ATTR_DEFAULT)
	{
		$rootAttributes['styleattributes'] = $style['styleattributes'];
	}
	$xml->add_group('style',
		$rootAttributes
	);


	/*
	 * Check if it's a THEME, and add extra guid, icon & previewimage tags.
	 */
	if (!empty($style['guid']))
	{
		// we allow removing the GUID
		if (!$remove_guid)
		{
			$xml->add_tag('guid', $style['guid']);
		}

		// optional, image data
		$optionalImages = [
			// DB column name => XML tag name
			'filedataid' => 'icon',
			'previewfiledataid' => 'previewimage',
		];
		foreach ($optionalImages AS $dbColumn => $tagname)
		{
			if (!empty($style[$dbColumn]))
			{
				$filedata = vB_Api::instanceInternal('filedata')->fetchImageByFiledataid($style[$dbColumn]);
				if (!empty($filedata['filedata']))
				{
					$xml->add_tag($tagname, base64_encode($filedata['filedata']));
				}
			}
		}
	}

	foreach ($templates AS $group => $grouptemplates)
	{
		$xml->add_group('templategroup', ['name' => $template_groups[$group] ?? $group]);
		style_xml_export_templates($xml, $grouptemplates);
		$xml->close_group();
	}

	$xml->add_group('stylevardfns');
	foreach ($stylevar_dfn_cache AS $stylevargroupname => $stylevargroup)
	{
		$xml->add_group('stylevargroup', ['name' => $stylevargroupname]);
		foreach ($stylevargroup AS $stylevar)
		{
			$xml->add_tag('stylevar', '',
				[
					'name' => htmlspecialchars($stylevar['stylevarid']),
					'datatype' => $stylevar['datatype'],
					'validation' => base64_encode($stylevar['validation']),
					'failsafe' => base64_encode($stylevar['failsafe'])
				]
			);
		}
		$xml->close_group();
	}
	$xml->close_group();

	$xml->add_group('stylevars');
	foreach ($stylevar_cache AS $stylevarid => $stylevar)
	{
		$xml->add_tag('stylevar', '',
			[
				'name' => htmlspecialchars($stylevar['stylevarid']),
				'value' => base64_encode($stylevar['value'])
			]
		);
	}
	$xml->close_group();

	$xml->close_group();

	$doc = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\r\n\r\n";
	$doc .= $xml->output();
	$xml = null;
	return $doc;
}

function style_xml_export_templates($xml, $templates)
{
	foreach ($templates AS $template)
	{
		$attributes = [
			'name' => htmlspecialchars($template['title']),
			'templatetype' => $template['templatetype'],
			'date' => $template['dateline'],
			'username' => $template['username'],
			'version' => htmlspecialchars_uni($template['version']),
		];

		// full is the default (and the overwhelming actual value) so we don't want to
		// spam the file with unneccesary attributes.
		if ($template['compiletype'] != 'full')
		{
			$attributes['compiletype'] = $template['compiletype'];
		}

		$xml->add_tag('template', $template['template'], $attributes, true);
	}
}

/// #############################################################################
/**
* Reads XML style file and imports data from it into the database
*
* @param	string	$xml		XML data
* @param	integer	$styleid	Style ID
* @param	integer	$parentid	Parent style ID
* @param	string	$title		New style title
* @param	boolean	$anyversion	Allow vBulletin version mismatch
* @param	integer	$displayorder	Display order for new style
* @param	boolean	$userselct	Allow user selection of new style
* @param  	int|null	$startat	Starting template group index for this run of importing templates (0 based). Null means all templates (single run)
* @param  	int|null	$perpage	Number of template groups to import at a time
* @param	boolean	$silent		Run silently (do not echo)
* @param	array|boolean	$parsed_xml	Parsed array of XML data. If provided the function will ignore $xml and use the provided, already parsed data.
*
* @return	array	Array of information about the imported style
*/
//not documenting the "istheme" parameter because we should really detect that internally, handle theme file imports consistently, and remove it
//it allows us to handle some things differently for overwriting themes.
function xml_import_style(
	$xml,
	$styleid = -1,
	$parentid = -1,
	$title = '',
	$anyversion = false,
	$displayorder = 1,
	$userselect = true,
	$startat = null,
	$perpage = null,
	$silent = false,
	$parsed_xml = false,
	$requireUniqueTitle = true,
	$istheme = false
)
{
	//checking the root node name
	if (!empty($xml))
	{
		$r = new XMLReader();
		if ($r->xml($xml))
		{
			if ($r->read())
			{
				$node_name = $r->name;
				if ($node_name != 'style')
				{
					print_stop_message2('file_uploaded_not_in_right_format_error');
				}
			}
			else
			{
				//can not read the document
				print_stop_message2('file_uploaded_unreadable');
			}
		}
		else
		{
			//can not open the xml
			print_stop_message2('file_uploaded_unreadable');
		}
	}

	if (!$silent)
	{
		$vbphrase = vB_Api::instanceInternal('phrase')->fetch(['importing_style', 'please_wait', 'creating_a_new_style_called_x']);
		print_dots_start('<b>' . $vbphrase['importing_style'] . "</b>, $vbphrase[please_wait]", ':', 'dspan');
	}

	if (empty($parsed_xml))
	{
		//where is this used?  I hate having this random global value in the middle of this function
		$xmlobj = new vB_XML_Parser($xml);
		if ($xmlobj->error_no())
		{
			if ($silent)
			{
				throw new vB_Exception_AdminStopMessage('no_xml_and_no_path');
			}
			print_dots_stop();
			print_stop_message2('no_xml_and_no_path');
		}

		if (!$parsed_xml = $xmlobj->parse())
		{
			if ($silent)
			{
				throw new vB_Exception_AdminStopMessage(['xml_error_x_at_line_y', $xmlobj->error_string(), $xmlobj->error_line()]);
			}
			print_dots_stop();
			print_stop_message2(['xml_error_x_at_line_y', $xmlobj->error_string(), $xmlobj->error_line()]);
		}
	}

	$version = $parsed_xml['vbversion'];
	$master = ($parsed_xml['type'] == 'master' ? 1 : 0);
	$title = (empty($title) ? $parsed_xml['name'] : $title);
	$product = (empty($parsed_xml['product']) ? 'vbulletin' : $parsed_xml['product']);
	$styleattributes = (isset($parsed_xml['styleattributes']) ? intval($parsed_xml['styleattributes']) : vB_Library_Style::ATTR_DEFAULT);
	$dateline = (isset($parsed_xml['dateline']) ? intval($parsed_xml['dateline']) : vB::getRequest()->getTimeNow());

	$assertor = vB::getDbAssertor();


	// for fetch_version_array().
	require_once(DIR . '/includes/adminfunctions.php');
	// VB6-587: Block uploads of vB3 or 4 XMLs, regardless of the $anyversion flag.
	[0 => $major_version] = fetch_version_array($version);
	if ($major_version <= 4)
	{
		if ($silent)
		{
			throw new vB_Exception_AdminStopMessage(['cannot_import_old_vbversion_xml', $version]);
		}
		print_dots_stop();
		print_stop_message(['cannot_import_old_vbversion_xml', $version]);
	}

	$one_pass = (is_null($startat) AND is_null($perpage));
	if (!$one_pass AND (!is_numeric($startat) OR !is_numeric($perpage) OR $perpage <= 0 OR $startat < 0))
	{
			if ($silent)
			{
				throw new vB_Exception_AdminStopMessage('');
			}
			print_dots_stop();
			print_stop_message2('');
	}

	$outputtext = '';
	if ($one_pass OR ($startat == 0))
	{
		require_once(DIR . '/includes/adminfunctions.php');
		// version check
		$full_product_info = fetch_product_list(true);
		$product_info = $full_product_info["$product"];

		if ($version != $product_info['version'] AND !$anyversion AND !$master)
		{
			if ($silent)
			{
				throw new vB_Exception_AdminStopMessage(['upload_file_created_with_different_version', $product_info['version'], $version]);
			}
			print_dots_stop();
			print_stop_message2(['upload_file_created_with_different_version', $product_info['version'], $version]);
		}

		//Initialize the style -- either init the master, create a new style, or verify the style to overwrite.
		if ($master)
		{
			$import_data = @unserialize(fetch_adminutil_text('master_style_import'));
			if (!empty($import_data) AND (TIMENOW - $import_data['last_import']) <= 30)
			{
				if ($silent)
				{
					throw new vB_Exception_AdminStopMessage(['must_wait_x_seconds_master_style_import', vb_number_format($import_data['last_import'] + 30 - TIMENOW)]);
				}
				print_dots_stop();
				print_stop_message2(['must_wait_x_seconds_master_style_import',  vb_number_format($import_data['last_import'] + 30 - TIMENOW)]);
			}

			$products = [$product];
			if ($product == 'vbulletin')
			{
				$products[] = '';
			}
			$assertor->assertQuery('vBForum:deleteProductTemplates', ['products' =>$products]);
			$assertor->assertQuery('vBForum:updateProductTemplates', ['products' =>$products]);
			$styleid = -1;
		}
		else
		{
			if ($styleid == -1)
			{
				// creating a new style
				if ($requireUniqueTitle AND $assertor->getRow('style', ['title' => $title]))
				{
					if ($silent)
					{
						throw new vB_Exception_AdminStopMessage(['style_already_exists', $title]);
					}
					print_dots_stop();
					print_stop_message2(['style_already_exists',  $title]);
				}
				else
				{
					if (!$silent)
					{
						$outputtext = construct_phrase($vbphrase['creating_a_new_style_called_x'], $title) . "<br>\n";
					}

					/*insert query*/
					$styleid = $assertor->insert('style', [
						'title' => $title,
						'parentid' => $parentid,
						'displayorder' => $displayorder,
						'userselect' => $userselect ? 1 : 0,
						'styleattributes' => $styleattributes,
						'dateline' => $dateline,
					]);

					if (is_array($styleid))
					{
						$styleid = array_pop($styleid);
					}
				}
			}
			else
			{
				// overwriting an existing style
				if ($oldStyleData = $assertor->getRow('style', ['styleid' => $styleid]))
				{
					/*
						Do an update if needed.
						Especially required for forcing theme XML changes to stick during upgrade
						(ex adding/changing styleattributes)
					*/
					$changed = (
						$oldStyleData['title'] != $title ||
						$oldStyleData['parentid'] != $parentid ||
						$oldStyleData['displayorder'] != $displayorder ||
						$oldStyleData['userselect'] != $userselect ||
						$oldStyleData['styleattributes'] != $styleattributes ||
						$oldStyleData['dateline'] != $dateline
					);

					if ($changed)
					{
						$assertor->update('style',
							[
								'title' => $title,
								'parentid' => $parentid,
								'displayorder' => $displayorder,
								'userselect' => $userselect ? 1 : 0,
								'styleattributes' => $styleattributes,
								'dateline' => $dateline,
							],
							['styleid' => $styleid]
						);
					}
				}
				else
				{
					if ($silent)
					{
						throw new vB_Exception_AdminStopMessage('cant_overwrite_non_existent_style');
					}
					print_dots_stop();
					print_stop_message2('cant_overwrite_non_existent_style');
				}
			}
		}

		//Remove existing templates for the hidden theme.  We want to remove any templates that were removed
		//from the xml and we don't need to worry about any user customizations.  Need to make sure we only
		//do this on the first pass.

		//shouldn't have $styleid -1 at this point with a theme but be paranoid
		if ($istheme AND $styleid !== -1)
		{
			//When reverting we would normally check to make sure that these templates aren't
			//custom (i.e. they exist in at least one parent).  However the point of the hidden theme
			//is that we can aggressively overwrite it with changes without worrying about user
			//customizations.
			$filter = [
				'styleid' => $styleid,
				'templatetype' => 'template',
			];

			//this could be written as a multi table delete query if it every becomes burdonsome to run.
			$templateids = $assertor->getColumn('template', 'templateid', $filter);
			$assertor->delete('templatemerge', ['templateid' => $templateids]);
			$assertor->delete('template', $filter);
		}
	}
	else
	{
		//We should never get styleid = -1 unless $master is true;
		if (($styleid == -1) AND !$master)
		{
			// According to this code, a style's title is a unique identifier (why not use guid?). This might be problematic.
			$stylerec = $assertor->getRow('style', ['title' => $title]);

			if ($stylerec AND intval($stylerec['styleid']))
			{
				$styleid = $stylerec['styleid'];
			}
			else
			{
				if ($silent)
				{
					throw new vB_Exception_AdminStopMessage(['incorrect_style_setting', $title]);
				}
				print_dots_stop();
				print_stop_message2(['incorrect_style_setting',  $title]);
			}
		}
	}

	//load the templates
	$arr = vB_XML_Parser::getList($parsed_xml, 'templategroup');
	if ($arr)
	{
		$templates_done = (is_numeric($startat) AND (count($arr) <= $startat));
		if ($one_pass OR !$templates_done)
		{
			if (!$one_pass)
			{
				$arr = array_slice($arr, $startat, $perpage);
			}

			//this is overly complicated but for some reson the product use a different keepalive setup than the styles
			//and I don't want to change it now.  So making the shared function parameterized on when to output and what
			//character to use.  This should likely be normalized but need to figure out if there is a reason they're different
			//first.
			$isinstall = vB::isInstaller();
			$keepalivechar = (defined('SUPPRESS_KEEPALIVE_ECHO') ? ($isinstall ? ' ' : '-') : null);
			$outputtext .= xml_import_template_groups($styleid, $product, $arr, !$one_pass, false, !$isinstall, $keepalivechar);
		}
	}
	else
	{
		$templates_done = true;
	}

	//note that templates may actually be done at this point, but templates_done is
	//only true if templates were completed in a prior step. If we are doing a multi-pass
	//process, we don't want to install stylevars in the same pass.  We aren't really done
	//until we hit a pass where the templates are done before processing.
	$done = ($one_pass OR $templates_done);
	if ($done)
	{
		//load stylevars and definitions
		// re-import any stylevar definitions
		if ($master AND !empty($parsed_xml['stylevardfns']['stylevargroup']))
		{
			xml_import_stylevar_definitions($parsed_xml['stylevardfns'], 'vbulletin');
		}

		//if the tag is present but empty we'll end up with a string with whitespace which
		//is a non "empty" value.
		if (!empty($parsed_xml['stylevars']) AND is_array($parsed_xml['stylevars']))
		{
			xml_import_stylevars($parsed_xml['stylevars'], $styleid, $istheme);
		}

		if ($master)
		{
			xml_import_restore_ad_templates();
			build_adminutil_text('master_style_import', serialize(['last_import' => TIMENOW]));
		}
		if (!$silent)
		{
			print_dots_stop();
		}
	}
	$fastDs = vB_FastDS::instance();

	//We want to force a fastDS rebuild, but we can't just call rebuild. There may be dual web servers,
	// and calling rebuild only rebuilds one of them.
	$options = vB::getDatastore()->getValue('miscoptions');
	$options['tmtdate'] = vB::getRequest()->getTimeNow();
	vB::getDatastore()->build('miscoptions', serialize($options), 1);

	return [
		'version' => $version,
		'master'  => $master,
		'title'   => $title,
		'product' => $product,
		'done'    => $done,
		'overwritestyleid' => $styleid,
		'output'  => $outputtext,
	];
}

function xml_import_template_groups($styleid, $product, $templategroup_array, $output_group_name, $save_as_files, $doOutput, $keepalivechar)
{
	global $vbphrase;

	$db = vB::getDbAssertor();
	$safe_product =  vB::getDbAssertor()->escape_string($product);

	$querytemplates = 0;
	$outputtext = '';
	if ($doOutput)
	{
		echo defined('NO_IMPORT_DOTS') ? "\n" : '<br />';
		vbflush();
	}

	$templateLib = vB_Library::instance('template');
	foreach ($templategroup_array AS $templategroup)
	{
		$tg = vB_XML_Parser::getList($templategroup, 'template');
		if ($output_group_name)
		{
			$text = construct_phrase($vbphrase['template_group_x'], $templategroup['name']);
			$outputtext .= $text;
			if ($doOutput)
			{
				echo $text;
				vbflush();
			}
		}

		foreach ($tg AS $template)
		{
			// Skip non-template templatetypes.
			if ($template['templatetype'] != 'template')
			{
				$parsedTemplate = $template['value'];
				// This is really only appropriate to actual templates but we have to set it to something for replacementvars too.
				$compiletype = 'textonly';
			}
			else
			{
				// Handle legacy xml files with the textonly attribute.  This should be considered deprecated but
				// it doesn't hurt anything to leave this in for a long while.
				$compiletype = $template['compiletype'] ?? (!empty($template['textonly']) ? 'textonly' : 'full');
				$parsedTemplate = $templateLib->compile($template['value'], $compiletype, false);
			}

			$querybit = [
				'styleid'     => $styleid,
				'title'       => $template['name'],
				'template'    => $template['templatetype'] == 'template' ? $parsedTemplate : $template['value'],
				'template_un' => $template['templatetype'] == 'template' ? $template['value'] : '',
				'dateline'    => $template['date'],
				'username'    => $template['username'],
				'version'     => $template['version'],
				'product'     => $product,
				'compiletype' => $compiletype,
			];
			$querybit['templatetype'] = $template['templatetype'];

			$querybits[] = $querybit;

			if (++$querytemplates % 10 == 0 OR $templategroup['name'] == 'Css')
			{
				save_template_bunch($db, $querybits, $save_as_files);
				$querybits = [];
			}

			// Send some output to the browser inside this loop so certain hosts
			// don't artificially kill the script. See bug #34585
			if (!is_null($keepalivechar))
			{
				echo $keepalivechar;
				vbflush();
			}
		}

		if ($doOutput)
		{
			echo defined('NO_IMPORT_DOTS') ? "\n" : '<br />';
			vbflush();
		}
	}

	// insert any remaining templates
	if (!empty($querybits))
	{
		save_template_bunch($db, $querybits, $save_as_files);
		$querybits = [];
	}

	return $outputtext;
}

function save_template_bunch($assertor, $querybits, $save_as_files)
{
	$assertor->assertQuery('replaceValues', ['values' => $querybits, 'table' => 'template']);

	//we only do this for products.  I'm not sure why but not changing it now.
	if ($save_as_files)
	{
		$template_names = [];
		foreach ($querybits AS $querybit)
		{
			if ($querybit['templatetype'] == 'template')
			{
				$template_names[] = $querybits['title'];
			}
		}

		$result = $assertor->select('template', ['styleid' => -1, 'title' => $template_names], false, ['templateid']);
		$templateids = [];
		foreach ($result AS $row)
		{
			$templateids[] = $row['templateid'];
		}

		vB_Library::instance('template')->saveTemplatesToFile($templateids);
	}
}


function xml_import_restore_ad_templates()
{
	// Get the template titles
	$save = [];
	$save_tables = vB::getDbAssertor()->assertQuery('template', [
		vB_dB_Query::CONDITIONS_KEY=> [
			['field'=>'templatetype', 'value' => 'template', vB_dB_Query::OPERATOR_KEY => vB_dB_Query::OPERATOR_EQ],
			['field'=>'styleid', 'value' => -10, vB_dB_Query::OPERATOR_KEY => vB_dB_Query::OPERATOR_EQ],
			['field'=>'product', 'value' => ['vbulletin', ''], vB_dB_Query::OPERATOR_KEY => vB_dB_Query::OPERATOR_EQ],
			['field'=>'title', 'value' => 'ad_', vB_dB_Query::OPERATOR_KEY => vB_dB_Query::OPERATOR_BEGINS],
		]
	]);

	foreach ($save_tables as $table)
	{
		$save[] = $table['title'];
	}

	// Are there any
	if (count($save))
	{
		// Delete any style id -1 ad templates that may of just been imported.
		vB::getDbAssertor()->delete('template', [
			['field'=>'templatetype', 'value' => 'template', vB_dB_Query::OPERATOR_KEY => vB_dB_Query::OPERATOR_EQ],
			['field'=>'styleid', 'value' => -1, vB_dB_Query::OPERATOR_KEY => vB_dB_Query::OPERATOR_EQ],
			['field'=>'product', 'value' => ['vbulletin', ''], vB_dB_Query::OPERATOR_KEY => vB_dB_Query::OPERATOR_EQ],
			['field'=>'title', 'value' => $save, vB_dB_Query::OPERATOR_KEY => vB_dB_Query::OPERATOR_EQ],
		]);

		// Replace the -1 templates with the -10 before they are deleted
		vB::getDbAssertor()->assertQuery('template', [
			vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
			vB_dB_Query::CONDITIONS_KEY => [
				['field'=>'templatetype', 'value' => 'template', vB_dB_Query::OPERATOR_KEY => vB_dB_Query::OPERATOR_EQ],
				['field'=>'styleid', 'value' => -10, vB_dB_Query::OPERATOR_KEY => vB_dB_Query::OPERATOR_EQ],
				['field'=>'product', 'value' => ['vbulletin', ''], vB_dB_Query::OPERATOR_KEY => vB_dB_Query::OPERATOR_EQ],
				['field'=>'title', 'value' => $save, vB_dB_Query::OPERATOR_KEY => vB_dB_Query::OPERATOR_EQ],
			],
			'styleid' => -1,
		]);
	}
}

function xml_import_stylevar_definitions($stylevardfns, $product)
{
	global $vbulletin;

	$querybits = [];
	$stylevardfns = vB_XML_Parser::getList($stylevardfns, 'stylevargroup');

	/*
		Delete the existing stylevars
		parentid will = 0 for imported stylevars,
		but is set to -1 for custom added sytlevars.
		We only really care about this for default
		vbulletin as any other products will clear up
		their own stylevars when they are uninstalled.
	*/

	if ($product == 'vbulletin')
	{
		$where = ['product' => 'vbulletin', 'parentid' => 0];
	}
	else
	{
		$where = ['product' => $product];
	}

	vB::getDbAssertor()->delete('vBForum:stylevardfn', $where);

	foreach ($stylevardfns AS $stylevardfn_group)
	{
		$sg = vB_XML_Parser::getList($stylevardfn_group, 'stylevar');
		foreach ($sg AS $stylevardfn)
		{
			$querybits[] = "('" . $vbulletin->db->escape_string($stylevardfn['name']) . "', -1, '" .
				$vbulletin->db->escape_string($stylevardfn_group['name']) . "', '" .
				$vbulletin->db->escape_string($product) . "', '" .
				$vbulletin->db->escape_string($stylevardfn['datatype']) . "', '" .
				$vbulletin->db->escape_string(base64_decode($stylevardfn['validation'])) . "', '" .
				$vbulletin->db->escape_string(base64_decode($stylevardfn['failsafe'])) . "', 0, 0
			)";
		}

		if (!empty($querybits))
		{
			$vbulletin->db->query_write("
				REPLACE INTO " . TABLE_PREFIX . "stylevardfn
				(stylevarid, styleid, stylevargroup, product, datatype, validation, failsafe, parentid, parentlist)
				VALUES
				" . implode(',', $querybits) . "
			");
		}
		$querybits = [];
	}
}

function xml_import_stylevars($stylevars, $styleid, $replaceall = false)
{
	$db = vB::getDbAssertor();

	//Make the entire stylevar list look like the import so if stylevars have been
	//removed from the style we'll remove them here.  Mostly for themes.
	if ($replaceall AND $styleid !== -1)
	{
		$db->delete('stylevar', ['styleid' => $styleid]);
	}

	$values = [];
	$sv = vB_XML_Parser::getList($stylevars, 'stylevar');

	foreach ($sv AS $stylevar)
	{
		//the parser merges attributes and child nodes into a single array.  The unnamed text
		//children get placed into a key called "value" automagically.  Since we don't have any
		//text children we just take the first one.
		$values[] = [
			'stylevarid' => $stylevar['name'],
			'styleid' => $styleid,
			'value' => base64_decode($stylevar['value'][0]),
			'dateline' => time(),
			'username' => 'Style-Importer',
		];
	}

	if (!empty($values))
	{
		$db->assertQuery('replaceValues', ['table' => 'stylevar', 'values' => $values]);
	}
}


/**
*	Get the stylevar list processed to export
*
*	Seperated into its own function for reuse by products
*
*	@param string product -- The name of the product to
*	@param string stylelist -- The styles to export as a comma seperated string
*		(in descending order of precedence).  THE CALLER IS RESPONSIBLE FOR SANITIZING THE
*		INPUT.
*/
function get_stylevars_for_export($product, $stylelist, $stylevar_groups = [])
{
	$assertor = vB::getDbAssertor();
	$queryParams = [
		'product'   => ($product == 'vbulletin') ? ['vbulletin', ''] : [strval($product)],
		'stylelist' => explode(',', $stylelist),
		'stylevar_groups' => $stylevar_groups,
	];

	$stylevar_cache = [];
	$stylevars = $assertor->getRows('vBForum:getStylevarsForExport', $queryParams);
	foreach ($stylevars AS $stylevar)
	{
		$stylevar_cache[$stylevar['stylevarid']] = $stylevar;
		ksort($stylevar_cache);
	}

	$stylevar_dfn_cache = [];
	$stylevar_dfns = $assertor->getRows('vBForum:getStylevarsDfnForExport', $queryParams);
	foreach ($stylevar_dfns AS $stylevar_dfn)
	{
		$stylevar_dfn_cache[$stylevar_dfn['stylevargroup']][] = $stylevar_dfn;
	}

	return ['stylevars' => $stylevar_cache, 'stylevardfns' => $stylevar_dfn_cache];
}

/**
* Function used for usort'ing a collection of templates.
* This function will return newer versions first.
*
* @param	array	First version
* @param	array	Second version
*
* @return	integer	-1, 0, 1
*/
function history_compare($a, $b)
{
	// if either of them does not have a version, make it look really old to the
	// comparison tool so it doesn't get bumped all the way up when its not supposed to
	if (!$a['version'])
	{
		$a['version'] = "0.0.0";
	}

	if (!$b['version'])
	{
		$b['version'] = "0.0.0";
	}

	// these return values are backwards to sort in descending order
	require_once(DIR . '/includes/adminfunctions.php');
	if (is_newer_version($a['version'], $b['version']))
	{
		return -1;
	}
	else if (is_newer_version($b['version'], $a['version']))
	{
		return 1;
	}
	else
	{
		if ($a['type'] == $b['type'])
		{
			return ($a['dateline'] > $b['dateline']) ? -1 : 1;
		}
		else if ($a['type'] == "historical")
		{
			return 1;
		}
		else
		{
			return -1;
		}
	}
}

/**
* Fetches a current or historical template.
*
* @param	integer	The ID (in the appropriate table) of the record you want to fetch
* @param	string	Type of template you want to fetch; should be "current" or "historical"
*
* @return	array	The data for the matching record
*/
function fetch_template_current_historical(&$id, $type)
{
	global $vbulletin;

	$id = intval($id);

	if ($type == 'current')
	{
		return $vbulletin->db->query_first("
			SELECT *, template_un AS templatetext
			FROM " . TABLE_PREFIX . "template
			WHERE templateid = $id
		");
	}
	else
	{
		return $vbulletin->db->query_first("
			SELECT *, template AS templatetext
			FROM " . TABLE_PREFIX . "templatehistory
			WHERE templatehistoryid = $id
		");
	}
}


/**
* Fetches the list of templates that have a changed status in the database
*
* List is hierarchical by style.
*
* @return array Associative array of styleid => template list with each template
* list being an array of templateid => template record.
*/
function fetch_changed_templates()
{
	$templates = [];
	$set = vB::getDbAssertor()->assertQuery('vBForum:fetchchangedtemplates', []);
	foreach ($set AS $template)
	{
		$templates[$template['styleid']][$template['templateid']] = $template;
	}
	return $templates;
}

/**
* Fetches the count templates that have a changed status in the database
*
* @return int Number of changed templates
*/
function fetch_changed_templates_count()
{
	$result = vB::getDbAssertor()->getRow('vBForum:getChangedTemplatesCount');
	return $result['count'];
}

/**
*	Get the template from the template id
*
*	@param id template id
* @return array template table record
*/
function fetch_template_by_id($id)
{
	$filter = ['templateid' => intval($id)];
	return fetch_template_internal($filter);
}

/**
*	Get the template from the template using the style and title
*
*	@param 	int 	styleid
* 	@param  string	title
* 	@return array 	template table record
*/
function fetch_template_by_title($styleid, $title)
{
	$filter = ['styleid' => intval($styleid), 'title' => strval($title), 'templatetype' => 'template'];
	return fetch_template_internal($filter);
}


/**
*	Get the template from the templatemerge (saved origin templates in the merge process)
* using the id
*
* The record is returned with the addition of an extra template_un field.
* This is set to the same value as the template field and is intended to match up the
* fields in the merge table with the fields in the main template table.
*
*	@param 	int 	id - Note that this is the same value as the main template table id
* 	@return array 	template record with extra template_un field
*/
function fetch_origin_template_by_id($id)
{
	$result = vB::getDbAssertor()->getRow('templatemerge', ['templateid' => intval($id)]);

	if ($result)
	{
		$result['template_un'] = $result['template'];
	}
	return $result;
}

/**
*	Get the template from the template using the id
*
* The record is returned with the addition of an extra template_un field.
* This is set to the same value as the template field and is intended to match up the
* fields in the merge table with the fields in the main template table.
*
*	@param int id - Note that this is the not same value as the main template table id,
*		there can be multiple saved history versions for a given template
* @return array template record with extra template_un field
*/
function fetch_historical_template_by_id($id)
{
	$result = vB::getDbAssertor()->getRow('templatehistory', ['templatehistoryid' => intval($id)]);

	//adjust to look like the main template result
	if ($result)
	{
		$result['template_un'] = $result['template'];
	}
	return $result;
}

/**
*	Get the template record
*
* This should only be called by cover functions in the file
* caller is responsible for sql security on $filter;
*
*	@filter Array	Filters to be used in the where clause. Field should be the key:
*					e.g: ['templateid' => $someValue]
* @private
*/
function fetch_template_internal($filter)
{
	$assertor = vB::getDbAssertor();
	$structure = $assertor->fetchTableStructure('template');
	$structure = $structure['structure'];

	$queryParams = [];
	foreach ($filter AS $field => $val)
	{
		if (in_array($field, $structure))
		{
			$queryParams[$field] = $val;
		}
	}

	return $assertor->getRow('template', $queryParams);
}


/**
* Get the requested templates for a merge operation
*
*	This gets the templates needed to show the merge display for a given custom
* template.  These are the custom template, the current default template, and the
* origin template saved when the template was initially merged.
*
* We can only display merges for templates that were actually merged during upgrade
*	as we only save the necesary information at that point.  If we don't have the
* available inforamtion to support the merge display, then an exception will be thrown
* with an explanatory message. Updating a template after upgrade
*
*	If the custom template was successfully merged we return the historical template
* save at upgrade time instead of the current (automatically updated at merge time)
* template.  Otherwise the differences merged into the current template will not be
* correctly displayed.
*
*	@param int templateid - The id of the custom user template to start this off
*	@throws Exception thrown if state does not support a merge display for
* 	the requested template
*	@return array ['custom' => $custom, 'new' => $new, 'origin' => $origin]
*/
function fetch_templates_for_merge($templateid)
{
	global $vbphrase;
	if (!$templateid)
	{
		throw new Exception($vbphrase['merge_error_invalid_template']);
	}

	$custom = fetch_template_by_id($templateid);
	if (!$custom)
	{
		throw new Exception(construct_phrase($vbphrase['merge_error_notemplate'], $templateid));
	}

	if ($custom['mergestatus'] == 'none')
	{
		throw new Exception($vbphrase['merge_error_nomerge']);
	}

	$new = fetch_template_by_title(-1, $custom['title']);
	if (!$new)
	{
		throw new Exception(construct_phrase($vbphrase['merge_error_nodefault'],  $custom['title']));
	}

	$origin = fetch_origin_template_by_id($custom['templateid']);
	if (!$origin)
	{
		throw new Exception(construct_phrase($vbphrase['merge_error_noorigin'],  $custom['title']));
	}

	if ($custom['mergestatus'] == 'merged')
	{
		$custom = fetch_historical_template_by_id($origin['savedtemplateid']);
		if (!$custom)
		{
			throw new Exception(construct_phrase($vbphrase['merge_error_nohistory'],  $custom['title']));
		}
	}

	return ['custom' => $custom, 'new' => $new, 'origin' => $origin];
}


/**
* Format the text for a merge conflict
*
* Take the three conflict text strings and format them into a human readable
* text block for display.
*
* @param string	Text from custom template
* @param string	Text from origin template
* @param string	Text from current VBulletin template
* @param string	Version string for origin template
* @param string	Version string for currnet VBulletin template
* @param bool	Whether to output the wrapping text with html markup for richer display
*
* @return string -- combined text
*/
function format_conflict_text($custom, $origin, $new, $origin_version, $new_version, $html_markup = false, $wrap = true)
{
	$phrases = vB_Api::instanceInternal('phrase')->renderPhrases([
		'new_default_value',
		'old_default_value',
		'your_customized_value',
	]);
	list($new_title, $origin_title, $custom_title) = $phrases['phrases'];

	if ($html_markup)
	{
		$text =
			"<div class=\"merge-conflict-row\"><b>$custom_title</b><div>" . format_diff_text($custom, $wrap) . "</div></div>"
			. "<div class=\"merge-conflict-row\"><b>$origin_title</b><div>" . format_diff_text($origin, $wrap) . "</div></div>"
			. "<div class=\"merge-conflict-final-row\"><b>$new_title</b><div>" . format_diff_text($new, $wrap) . "</div></div>";
	}
	else
	{
		$origin_bar = "======== $origin_title ========";

		$text  = "<<<<<<<< $custom_title <<<<<<<<\n";
		$text .= $custom;
		$text .= $origin_bar . "\n";
		$text .= $origin;
		$text .= str_repeat("=", strlen($origin_bar)) . "\n";
		$text .= $new;
		$text .= ">>>>>>>> $new_title >>>>>>>>\n";
	}

	return $text;
}

function format_diff_text($string, $wrap = true)
{
	if (trim($string) === '')
	{
		return '&nbsp;';
	}
	else
	{
		if ($wrap)
		{
			$string = nl2br(htmlspecialchars_uni($string));
			$string = preg_replace('#( ){2}#', '&nbsp; ', $string);
			$string = str_replace("\t", '&nbsp; &nbsp; ', $string);
			return "<code>$string</code>";
		}
		else
		{
			return '<pre style="display:inline">' . "\n" . htmlspecialchars_uni($string) . '</pre>';
		}
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 116517 $
|| #######################################################################
\*=========================================================================*/
