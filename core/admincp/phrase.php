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

// ######################## SET PHP ENVIRONMENT ###########################

// ##################### DEFINE IMPORTANT CONSTANTS #######################
define('CVS_REVISION', '$RCSfile$ - $Revision: 115002 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
global $phrasegroups, $specialtemplates, $vbphrase, $vbulletin;
$phrasegroups = ['language'];
$specialtemplates = [];

// ########################## REQUIRE BACK-END ############################
require_once(dirname(__FILE__) . '/global.php');
require_once(DIR . '/includes/adminfunctions_language.php');

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!can_administer('canadminlanguages'))
{
	print_cp_no_permission();
}

$vbulletin->input->clean_array_gpc('r', array(
	'phraseid' => vB_Cleaner::TYPE_INT,
));

// ############################# LOG ACTION ###############################
log_admin_action(($vbulletin->GPC['phraseid'] ? "phrase id = " . $vbulletin->GPC['phraseid'] : ''));

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

$vb5_config =& vB::getConfig();

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'modify';
}


// #############################################################################

if ($_REQUEST['do'] == 'quickref')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'languageid' => vB_Cleaner::TYPE_INT,
		'fieldname'  => vB_Cleaner::TYPE_NOHTML,
	));

	if ($vbulletin->GPC['languageid'] == 0)
	{
		$vbulletin->GPC['languageid'] = vB::getDatastore()->getOption('languageid');
	}
	if ($vbulletin->GPC['fieldname'] == '')
	{
		$vbulletin->GPC['fieldname'] = 'global';
	}

	$languages = vB::getDatastore()->getValue('languagecache');
	if ($vb5_config['Misc']['debug'])
	{
		$langoptions['-1'] = $vbphrase['master_language'];
	}
	foreach($languages AS $lang)
	{
		$langoptions["{$lang['languageid']}"] = $lang['title'];
	}
	$phrasetypes = vB_Api::instanceInternal('phrase')->fetch_phrasetypes();
	foreach($phrasetypes AS $fieldname => $type)
	{
		$typeoptions["$fieldname"] = $type['title'] . ' ' . $vbphrase['phrases'];
	}

	define('NO_PAGE_TITLE', true);
	print_cp_header("$vbphrase[quickref] {$langoptions["{$vbulletin->GPC['languageid']}"]} {$typeoptions["{$vbulletin->GPC['fieldname']}"]}", '', '', 0);

	$phrasearray = array();

	if ($vbulletin->GPC['languageid'] != -1)
	{
		$custom = fetch_custom_phrases($vbulletin->GPC['languageid'], $vbulletin->GPC['fieldname']);
		if (!empty($custom))
		{
			foreach($custom AS $phrase)
			{
				$phrasearray[htmlspecialchars_uni($phrase['text'])] = $phrase['varname'];
			}
		}
	}

	$standard = fetch_standard_phrases($vbulletin->GPC['languageid'], $vbulletin->GPC['fieldname']);

	if (is_array($standard))
	{
		foreach($standard AS $phrase)
		{
			$phrasearray[htmlspecialchars_uni($phrase['text'])] = $phrase['varname'];
		}
		$tval = $langoptions["{$vbulletin->GPC['languageid']}"] . ' ' . $typeoptions["{$vbulletin->GPC['fieldname']}"];
	}
	else
	{
		$tval = construct_phrase($vbphrase['no_x_phrases_defined'], '<i>' . $typeoptions["{$vbulletin->GPC['fieldname']}"] . '</i>');
	}

	$directionHtml = 'dir="' . $languages["{$vbulletin->GPC['languageid']}"]['direction'] . '"';

	print_form_header('admincp/phrase', 'quickref', 0, 1, 'cpform', '100%', '', 0);
	print_table_header($vbphrase['quickref'] . ' </b>' . $langoptions["{$vbulletin->GPC['languageid']}"] . ' ' . $typeoptions["{$vbulletin->GPC['fieldname']}"] . '<b>');
	print_label_row("<select size=\"10\" class=\"bginput\" onchange=\"
		if (this.options[this.selectedIndex].value != '')
		{
			this.form.tvar.value = '\$" . "vbphrase[' + this.options[this.selectedIndex].text + ']';
			this.form.tbox.value = this.options[this.selectedIndex].value;
		}
		\">" . construct_select_options($phrasearray) . '</select>','
		<input type="text" class="bginput" name="tvar" size="35" class="button" /><br />
		<textarea name="tbox" class="darkbg" style="font: 11px verdana" rows="8" cols="35" ' . $directionHtml . '>' . $tval . '</textarea>
		');
	print_description_row('
		<center>
		<select name="languageid" accesskey="l" class="bginput">' . construct_select_options($langoptions, $vbulletin->GPC['languageid']) . '</select>
		<select name="fieldname" accesskey="t" class="bginput">' . construct_select_options($typeoptions, $vbulletin->GPC['fieldname']) . '</select>
		<input type="submit" class="button" value="' . $vbphrase['view'] . '" accesskey="s" />
		<input type="button" class="button" value="' . $vbphrase['close_gcpglobal'] . '" accesskey="c" onclick="self.close()" />
		</center>
	', 0, 2, 'thead');
	print_table_footer();
	print_cp_footer();
}

// #############################################################################

if ($_POST['do'] == 'completeorphans')
{
	$vbulletin->input->clean_array_gpc('p', [
		'del'  => vB_Cleaner::TYPE_ARRAY_STR,  // phrases to delete
		'keep' => vB_Cleaner::TYPE_ARRAY_UINT, // phrases to keep
	]);

	vB_Api::instanceInternal('phrase')->processOrphans($vbulletin->GPC['del'], $vbulletin->GPC['keep']);

	$args = [
		'do' => 'rebuild',
		'goto' => 'admincp/phrase.php',
	];
	exec_header_redirect(get_admincp_url('language', $args));
}

// #############################################################################

if ($_POST['do'] != 'doreplace')
{
	print_cp_header($vbphrase['phrase_manager_glanguage']);
}

// #############################################################################

if ($_POST['do'] == 'manageorphans')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'phr' => vB_Cleaner::TYPE_ARRAY_BOOL,
	));

	print_form_header('admincp/phrase', 'completeorphans');

	$hidden_code_num = 0;
	$keepnames = array();

	foreach ($vbulletin->GPC['phr'] AS $key => $keep)
	{
		if ($keep)
		{
			fetch_varname_fieldname($key, $varname, $fieldname);
			$keepnames [] = array('varname' => $varname, 'fieldname' => $fieldname);
		}
		else
		{
			construct_hidden_code("del[$hidden_code_num]", $key);
			$hidden_code_num ++;
		}
	}
	print_table_header($vbphrase['find_orphan_phrases']);

	if (empty($keepnames))
	{
		// there are no phrases to keep, just show a message telling admin to click to proceed
		print_description_row('<blockquote><p><br />' . $vbphrase['delete_all_orphans_notes'] . '</p></blockquote>');
	}
	else
	{
		// there are some phrases to keep, show a message explaining the page
		print_description_row($vbphrase['keep_orphans_notes']);

		$orphans = array();
		$phrases = vB::getDbAssertor()->assertQuery('fetchKeepNames', array(
				vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_METHOD,
				'keepnames' => $keepnames,
		));

		foreach ($phrases as $phrase)
		{
			$orphans["{$phrase['varname']}@{$phrase['fieldname']}"]["{$phrase['languageid']}"] = array('phraseid' => $phrase['phraseid'], 'text' => $phrase['text']);
		}
		$vbulletin->db->free_result($phrases);

		$languages = vB::getDatastore()->getValue('languagecache');
		$phrasetypes = vB_Api::instanceInternal('phrase')->fetch_phrasetypes();

		$bgcounter = 0;
		foreach ($orphans AS $key => $languageids)
		{
			fetch_varname_fieldname($key, $varname, $fieldname);

			if (isset($languageids[vB::getDatastore()->getOption('languageid')]))
			{
				$checked = vB::getDatastore()->getOption('languageid');
			}
			else
			{
				$checked = 0;
			}

			$bgclass = fetch_row_bgclass();

			echo "<tr valign=\"top\">\n";
			echo "\t<td class=\"$bgclass\">" . construct_wrappable_varname($varname, 'font-weight:bold;') . " <dfn>" . construct_phrase($vbphrase['x_phrases'], $phrasetypes["$fieldname"]['title']) . "</dfn></td>\n";
			echo "\t<td style=\"padding:0px\">\n\t\t<table cellpadding=\"2\" cellspacing=\"1\" border=\"0\" width=\"100%\">\n\t\t<col width=\"65%\"><col width=\"35%\" align=\"" . vB_Template_Runtime::fetchStyleVar('right') . "\">\n";

			$i = 0;
			$tr_bgclass = iif((++$bgcounter % 2) == 0, 'alt2', 'alt1');

			foreach ($languages AS $language)
			{
				if (isset($languageids["{$language['languageid']}"]))
				{
					if ($checked)
					{
						if ($language['languageid'] == $checked)
						{
							$checkedhtml = ' checked="checked"';
						}
						else
						{
							$checkedhtml = '';
						}
					}
					else if ($i == 0)
					{
						$checkedhtml = ' checked="checked"';
					}
					else
					{
						$checkedhtml = '';
					}
					$i++;
					$phrase =& $orphans["$key"]["{$language['languageid']}"];

					echo "\t\t<tr class=\"$tr_bgclass\">\n";
					echo "\t\t\t<td class=\"smallfont\"><label for=\"p$phrase[phraseid]\"><i>$phrase[text]</i></label></td>\n";
					echo "\t\t\t<td class=\"smallfont\"><label for=\"p$phrase[phraseid]\"><b>$language[title]</b><input type=\"radio\" name=\"keep[" . urlencode($key) . "]\" value=\"$phrase[phraseid]\" id=\"p$phrase[phraseid]\" tabindex=\"1\"$checkedhtml /></label></td>\n";
					echo "\t\t</tr>\n";
				}
			}

			echo "\n\t\t</table>\n";
			echo "\t\t<div class=\"$bgclass\">&nbsp;</div>\n";
			echo "\t</td>\n</tr>\n";
		}
	}

	print_submit_row($vbphrase['continue'], iif(empty($keepnames), false, " $vbphrase[reset] "));
}

// #############################################################################

if ($_REQUEST['do'] == 'findorphans')
{
	// get info for the languages and phrase types
	$phraseAPI = vB_Api::instanceInternal('phrase');
	$datastore = vB::getDatastore();

	$languages = $datastore->getValue('languagecache');
	$phrasetypes = $phraseAPI->fetch_phrasetypes();
	$phrases = $phraseAPI->fetchOrphans();

	if (empty($phrases))
	{
		print_stop_message2('no_phrases_matched_your_query');
	}

	$orphans = array();

	foreach ($phrases as $phrase)
	{
		$phrase['varname'] = urlencode($phrase['varname']);
		$orphans["{$phrase['varname']}@{$phrase['fieldname']}"]["{$phrase['languageid']}"] = true;
	}


	// get the number of columns for the table
	$colspan = sizeof($languages) + 2;

	print_form_header('admincp/phrase', 'manageorphans');
	print_table_header($vbphrase['find_orphan_phrases'], $colspan);

	// make the column headings
	$headings = array($vbphrase['varname']);
	foreach ($languages AS $language)
	{
		$headings[] = $language['title'];
	}
	$headings[] = '<input type="button" class="button" value="' . $vbphrase['keep_all'] .
		'" onclick="js_check_all_option(this.form, 1)" /> <input type="button" class="button" value="' .
		$vbphrase['delete_all_gcpglobal'] . '" onclick="js_check_all_option(this.form, 0)" />';
	print_cells_row($headings, 1);

	// init the counter for our id attributes in label tags
	$i = 0;

	$yesImage = get_cpstyle_href('cp_tick_yes.gif');
	$noImage = get_cpstyle_href('cp_tick_no.gif');

	foreach ($orphans AS $key => $languageids)
	{
		// split the array key
		fetch_varname_fieldname($key, $varname, $fieldname);

		// make the first cell
		$cell = array(construct_wrappable_varname($varname, 'font-weight:bold;') . " <dfn>" .
			construct_phrase($vbphrase['x_phrases'], $phrasetypes["$fieldname"]['title']) . "</dfn>");

		// either display a tick or not depending on whether a translation exists
		foreach ($languages AS $language)
		{
			if (isset($languageids["{$language['languageid']}"]))
			{
				$cell[] = "<img src=\"$yesImage\" alt=\"\" />";
			}
			else
			{
				$cell[] = "<img src=\"$noImage\" alt=\"\" />";
			}
		}

		$i++;
		$varname = urlencode($varname);
		$cell[] = "
		<label for=\"k_$i\"><input type=\"radio\" id=\"k_$i\" name=\"phr[{$varname}@$fieldname]\" value=\"1\" tabindex=\"1\" />$vbphrase[keep]</label>
		<label for=\"d_$i\"><input type=\"radio\" id=\"d_$i\" name=\"phr[{$varname}@$fieldname]\" value=\"0\" tabindex=\"1\" checked=\"checked\" />$vbphrase[delete]</label>
		";

		print_cells_row($cell);
	}

	print_submit_row($vbphrase['continue'], " $vbphrase[reset] ", $colspan);
}

// #############################################################################
// find custom phrases that need updating
if ($_REQUEST['do'] == 'findupdates')
{
	// query custom phrases
	$customcache = vB_Api::instance('phrase')->findUpdates();

	if(isset($customcache['errors']))
	{
		print_stop_message_array($customcache['errors']);
	}

	if (empty($customcache))
	{
		print_stop_message2('all_phrases_are_up_to_date');
	}

	$datastore = vB::getDatastore();
	$languages = $datastore->getValue('languagecache');

	print_form_header('admincp/', '');
	print_table_header($vbphrase['find_updated_phrases_glanguage']);
	print_description_row('<span class="smallfont">' .
		construct_phrase($vbphrase['updated_default_phrases_desc'], $datastore->getOption('templateversion')) . '</span>');
	print_table_break(' ');

	$full_product_info = vB_Library::instance('product')->getFullProducts();
	foreach($languages AS $language)
	{
		$languageid = $language['languageid'];
		if (isset($customcache[$languageid]) AND is_array($customcache[$languageid]))
		{
			print_description_row($language['title'], 0, 2, 'thead');
			foreach($customcache[$languageid] AS $phraseid => $phrase)
			{
				if (!$phrase['customuser'])
				{
					$phrase['customuser'] = $vbphrase['n_a'];
				}
				if (!$phrase['customversion'])
				{
					$phrase['customversion'] = $vbphrase['n_a'];
				}

				$product_name = $full_product_info["$phrase[product]"]['title'];

				print_label_row("
					<b>$phrase[varname]</b> ($phrase[phrasetype_title])<br />
					<span class=\"smallfont\">" .
						construct_phrase($vbphrase['default_phrase_updated_desc'],
							"$product_name $phrase[globalversion]",
							$phrase['globaluser'],
							"$product_name $phrase[customversion]",
							$phrase['customuser'])
					. '</span>',
				'<span class="smallfont">' .
					construct_link_code($vbphrase['edit'], "phrase.php?do=edit&amp;phraseid=$phraseid", 1) . '<br />' .
				'</span>'
				);
			}
		}
	}

	print_table_footer();
}

// #############################################################################

if ($_REQUEST['do'] == 'dosearch')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'searchstring'  => vB_Cleaner::TYPE_STR,
		'searchwhere'   => vB_Cleaner::TYPE_UINT,
		'casesensitive' => vB_Cleaner::TYPE_BOOL,
		'exactmatch'    => vB_Cleaner::TYPE_BOOL,
		'languageid'    => vB_Cleaner::TYPE_INT,
		'phrasetype'    => vB_Cleaner::TYPE_ARRAY_NOHTML,
		'transonly'     => vB_Cleaner::TYPE_BOOL,
		'product'       => vB_Cleaner::TYPE_STR,
	));

	if ($vbulletin->GPC['searchstring'] == '')
	{
		print_stop_message2('please_complete_required_fields');
	}

	$criteria = array();
	$criteria['exactmatch'] = $vbulletin->GPC['exactmatch'] ? 1 : 0;
	$criteria['casesensitive'] = $vbulletin->GPC['casesensitive'] ? 1 : 0;
	$criteria['searchwhere'] = $vbulletin->GPC['searchwhere'];
	$criteria['phrasetype'] = $vbulletin->GPC['phrasetype'];
	$criteria['searchstring'] = $vbulletin->GPC['searchstring'];
	$criteria['languageid'] = $vbulletin->GPC['languageid'];
	$criteria['transonly'] = $vbulletin->GPC['transonly'];
	$criteria['product'] = $vbulletin->GPC['product'];

	$phrasearray = vB_Api::instanceInternal('phrase')->search($criteria);

	if (empty($phrasearray))
	{
		print_stop_message2('no_phrases_matched_your_query');
	}

	$phrasetypes = vB_Api::instanceInternal('phrase')->fetch_phrasetypes();

	print_form_header('admincp/phrase', 'edit');
	print_table_header($vbphrase['search_results'], 5);

	// add search params so we can return to search
	construct_hidden_code('return_to_search', '1');
	construct_hidden_code('search_searchstring', $vbulletin->GPC['searchstring']);
	construct_hidden_code('search_searchwhere', $vbulletin->GPC['searchwhere']);
	construct_hidden_code('search_casesensitive', $vbulletin->GPC['casesensitive']);
	construct_hidden_code('search_exactmatch', $vbulletin->GPC['exactmatch']);
	construct_hidden_code('search_languageid', $vbulletin->GPC['languageid']);
	foreach ($vbulletin->GPC['phrasetype'] AS $k => $v)
	{
		construct_hidden_code('search_phrasetype[' . $k . ']', $v);
	}
	construct_hidden_code('search_transonly', $vbulletin->GPC['transonly']);
	construct_hidden_code('search_product', $vbulletin->GPC['product']);

	$ignorecase = ($vbulletin->GPC['casesensitive'] ? false : true);

	foreach($phrasearray AS $fieldname => $x)
	{
		// display the header for the phrasetype
		print_description_row(construct_phrase($vbphrase['x_phrases_containing_y'], $phrasetypes[$fieldname]['title'], htmlspecialchars_uni($vbulletin->GPC['searchstring'])), 0, 5, 'thead" align="center');

		if ($fieldname == 'cphelptext' AND $vb5_config['Misc']['debug'] AND defined('DEV_AUTOEXPORT') AND DEV_AUTOEXPORT)
		{
			// Hard-coded dev-only warning.
			print_description_row('Edit Control Panel Help Text phrases in the <a href="admincp/help.php?do=manage&amp;script=NOSCRIPT">Admin Help Manager</a> so they export to <code>vbulletin-adminhelp.xml</code>. They are not exported to <code>vbulletin-language.xml</code>. ', 0, 5, 'warning" align="center');
		}

		// sort the phrases alphabetically by $varname
		ksort($x);
		foreach($x AS $varname => $y)
		{
			foreach($y AS $phrase)
			{
				$cell = array();
				$cell[] = '<b>' . ($vbulletin->GPC['searchwhere'] > 0 ? fetch_highlighted_search_results($vbulletin->GPC['searchstring'], $varname, $ignorecase) : $varname) . '</b>';
				$cell[] = '<span class="smallfont">' . fetch_language_type_string($phrase['languageid'], $phrase['title']) . '</span>';
				$cell[] = '<span class="smallfont">' . nl2br(($vbulletin->GPC['searchwhere'] % 10 == 0) ? fetch_highlighted_search_results($vbulletin->GPC['searchstring'], $phrase['text'], $ignorecase) : htmlspecialchars_uni($phrase['text'])) . '</span>';
				$cell[] = "<input type=\"submit\" class=\"button\" value=\" $vbphrase[edit] \" name=\"e[$fieldname][" . urlencode($varname) . "]\" />";
				if (($vb5_config['Misc']['debug'] AND $phrase['languageid'] == -1) OR $phrase['languageid'] == 0)
				{
					$cell[] = "<input type=\"submit\" class=\"button\" value=\" $vbphrase[delete] \" name=\"delete[$fieldname][" . urlencode($varname) . "]\" />";
				}
				else
				{
					$cell[] = '';
				}
				print_cells_row($cell, 0, 0, -2);
			} // end foreach($y)
		} // end foreach($x)
	} // end foreach($phrasearray)

	print_table_footer();

	$_REQUEST['do'] = 'search';

}

// #############################################################################

if ($_REQUEST['do'] == 'search')
{
	if (!isset($_REQUEST['languageid']))
	{
		$_REQUEST['languageid'] = -10;
	}

	$vbulletin->input->clean_array_gpc('r', array(
		'searchstring'  => vB_Cleaner::TYPE_STR,
		'searchwhere'   => vB_Cleaner::TYPE_UINT,
		'casesensitive' => vB_Cleaner::TYPE_BOOL,
		'exactmatch'    => vB_Cleaner::TYPE_BOOL,
		'languageid'    => vB_Cleaner::TYPE_INT,
		'phrasetype'    => vB_Cleaner::TYPE_ARRAY_NOHTML,
		'transonly'     => vB_Cleaner::TYPE_BOOL,
		'product'       => vB_Cleaner::TYPE_STR,
	));

	// get all languages
	$languageselect = [-10 => $vbphrase['all_languages']];

	if ($vb5_config['Misc']['debug'])
	{
		$languageselect[$vbphrase['developer_options']] = [
			-1 => $vbphrase['master_language'] . ' (-1)',
			0  => $vbphrase['custom_language'] . ' (0)'
		];
	}

	$languageselectall = vB::getDatastore()->getValue('languagecache');
	foreach ($languageselectall AS $infos)
	{
		$languageselect[$vbphrase['translations']][$infos['languageid']] = $infos['title'];
	}

	// get all phrase types
	$phrasetypes_result = vB::getDbAssertor()->assertQuery('phrasetype', [], ['field' => 'title', 'direction' => vB_dB_Query::SORT_ASC]);
	$phrasetypes = ['' => ''];
	foreach ($phrasetypes_result AS $phrasetype)
	{
		$phrasetypes[$phrasetype['fieldname']] = $phrasetype['title'];
	}

	print_form_header('admincp/phrase', 'dosearch');
	print_table_header($vbphrase['search_in_phrases_glanguage']);
	print_input_row($vbphrase['search_for_text'], 'searchstring', $vbulletin->GPC['searchstring'], 1, 50);
	print_select_row($vbphrase['search_in_language'], 'languageid', $languageselect, $vbulletin->GPC['languageid']);
	print_select_row($vbphrase['product'], 'product', array('' => $vbphrase['all_products']) + fetch_product_list(), $vbulletin->GPC['product']);
	print_yes_no_row($vbphrase['search_translated_phrases_only'], 'transonly', $vbulletin->GPC['transonly']);
	print_select_row($vbphrase['phrase_type'], 'phrasetype[]', $phrasetypes, $vbulletin->GPC['phrasetype'], false, 10, true);

	$title = construct_phrase($vbphrase['search_in_x'], '...');
	$options = [
		0 => $vbphrase['phrase_text_only'],
		1 => $vbphrase['phrase_name_only'],
		10 => $vbphrase['phrase_text_and_phrase_name'],
	];
	print_radio_row($title, 'searchwhere', $options, $vbulletin->GPC['searchwhere']);
	print_yes_no_row($vbphrase['case_sensitive'], 'casesensitive', $vbulletin->GPC['casesensitive']);
	print_yes_no_row($vbphrase['exact_match'], 'exactmatch', $vbulletin->GPC['exactmatch']);
	print_submit_row($vbphrase['find']);

	// search & replace
	//remove the default "all languages" header for the search and replace.
	unset($languageselect[-10]);
	print_form_header('admincp/phrase', 'replace', 0, 1, 'srform');
	print_table_header($vbphrase['find_and_replace_in_languages']);
	print_select_row($vbphrase['search_in_language'], 'languageid', $languageselect);
	print_textarea_row($vbphrase['search_for_text'], 'searchstring', '', 5, 60, 1, 0);
	print_textarea_row($vbphrase['replace_with_text'], 'replacestring', '', 5, 60, 1, 0);
	print_submit_row($vbphrase['replace']);
}

// #############################################################################

if ($_POST['do'] == 'doreplace')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'replace'       => vB_Cleaner::TYPE_ARRAY_UINT,
		'searchstring'  => vB_Cleaner::TYPE_STR,
		'replacestring' => vB_Cleaner::TYPE_STR,
		'languageid'    => vB_Cleaner::TYPE_INT
	));

	if (empty($vbulletin->GPC['replace']))
	{
		print_stop_message2('please_complete_required_fields');
	}

	$products_to_export = vB_Api::instanceInternal('phrase')->replace(
		array_keys($vbulletin->GPC['replace']),
		$vbulletin->GPC['searchstring'],
		$vbulletin->GPC['replacestring'],
		$vbulletin->GPC['languageid']
	);

	if (defined('DEV_AUTOEXPORT') AND DEV_AUTOEXPORT AND !empty($products_to_export))
	{
		require_once(DIR . '/includes/functions_filesystemxml.php');
		foreach($products_to_export as $product)
		{
			autoexport_write_language($vbulletin->GPC['languageid'], $product);
		}
	}
	$args = [
		'do' => 'rebuild',
		'goto' => 'admincp/phrase.php?do=search',
	];

	exec_header_redirect(get_admincp_url('language', $args));
}

// #############################################################################

if ($_POST['do'] == 'replace')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'searchstring'  => vB_Cleaner::TYPE_STR,
		'replacestring' => vB_Cleaner::TYPE_STR,
		'languageid'    => vB_Cleaner::TYPE_INT
	));

	if (empty($vbulletin->GPC['searchstring']) OR empty($vbulletin->GPC['replacestring']))
	{
		print_stop_message2('please_complete_required_fields');
	}

	// do a rather clever query to find what phrases to display
	$phrases = vB::getDbAssertor()->assertQuery('fetchPhrasesForDisplay', array(
		'searchstring' => $vbulletin->GPC['searchstring'],
		'languageid' => $vbulletin->GPC['languageid'],
	));

	$phrasearray = array();

	foreach ($phrases as $phrase)
	{
		$phrasearray["$phrase[fieldname]"]["$phrase[varname]"]["$phrase[languageid]"] = $phrase;
	}
	unset($phrase);

	if (empty($phrasearray))
	{
		print_stop_message2('no_phrases_matched_your_query');
	}

	$phrasetypes = vB_Api::instanceInternal('phrase')->fetch_phrasetypes();

	print_form_header('admincp/phrase', 'doreplace');
	print_table_header($vbphrase['search_results'], 4);

	construct_hidden_code('searchstring', $vbulletin->GPC['searchstring']);
	construct_hidden_code('replacestring', $vbulletin->GPC['replacestring']);
	construct_hidden_code('languageid', $vbulletin->GPC['languageid']);

	foreach($phrasearray AS $fieldname => $x)
	{
		// display the header for the phrasetype
		print_description_row(construct_phrase($vbphrase['x_phrases_containing_y'], $phrasetypes["$fieldname"]['title'],
			htmlspecialchars_uni($vbulletin->GPC['searchstring'])), 0, 4, 'thead" align="center');

		// sort the phrases alphabetically by $varname
		ksort($x);
		foreach($x AS $varname => $y)
		{
			foreach($y AS $phrase)
			{
				$cell = array();
				$cell[] = '<b>' . $varname . '</b>';
				$cell[] = '<span class="smallfont">' . fetch_language_type_string($phrase['languageid'], $phrase['title']) . '</span>';
				$cell[] = '<span class="smallfont">' . fetch_highlighted_search_results($vbulletin->GPC['searchstring'], $phrase['text'], false) . '</span>';
				$cell[] = "<input type=\"checkbox\" value=\"1\" name=\"replace[{$phrase['phraseid']}]\" />";
				print_cells_row($cell, 0, 0, -2);
			} // end foreach($y)
		} // end foreach($x)
	} // end foreach($phrasearray)
	print_submit_row($vbphrase['replace'], '', 4, '',
		'<label for="cb_checkall"><input type="checkbox" name="allbox" id="cb_checkall" onclick="js_check_all(this.form)" />' .
		$vbphrase['check_uncheck_all'] . '</label>');
}

// #############################################################################

if ($_POST['do'] == 'kill')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'fieldname'            => vB_Cleaner::TYPE_NOHTML,
		'pagenumber'           => vB_Cleaner::TYPE_UINT,
		'perpage'              => vB_Cleaner::TYPE_UINT,
		'sourcefieldname'      => vB_Cleaner::TYPE_NOHTML,
		'return_to_search'     => vB_Cleaner::TYPE_BOOL,
		'search_searchstring'  => vB_Cleaner::TYPE_STR,
		'search_searchwhere'   => vB_Cleaner::TYPE_UINT,
		'search_casesensitive' => vB_Cleaner::TYPE_BOOL,
		'search_exactmatch'    => vB_Cleaner::TYPE_BOOL,
		'search_languageid'    => vB_Cleaner::TYPE_INT,
		'search_phrasetype'    => vB_Cleaner::TYPE_ARRAY_NOHTML,
		'search_transonly'     => vB_Cleaner::TYPE_BOOL,
		'search_product'       => vB_Cleaner::TYPE_STR,
	));

	$getvarname = vB_Api::instance('phrase')->delete($vbulletin->GPC['phraseid']);
	if(isset($getvarname['errors']))
	{
		print_stop_message_array($getvarname['errors']);
	}

	//not sure if this can actually happen but don't want to remove it without
	//checking carefully.  At least the common case will be trapped in the error handler.
	if (empty($getvarname))
	{
		print_stop_message2('invalid_phrase_specified');
	}

	if (defined('DEV_AUTOEXPORT') AND DEV_AUTOEXPORT)
	{
		require_once(DIR . '/includes/functions_filesystemxml.php');
		autoexport_write_language($getvarname['languageid'], $getvarname['product']);
	}

	$printStopMessageValues = array(
		'fieldname' => $vbulletin->GPC['sourcefieldname'],
		'page' => $vbulletin->GPC['pagenumber'],
		'pp' => $vbulletin->GPC['perpage'],
	);

	if ($vbulletin->GPC['return_to_search'])
	{
		$printStopMessageValues['do'] = 'dosearch';
		$printStopMessageValues['searchstring'] = $vbulletin->GPC['search_searchstring'];
		$printStopMessageValues['searchwhere'] = $vbulletin->GPC['search_searchwhere'];
		$printStopMessageValues['casesensitive'] = $vbulletin->GPC['search_casesensitive'];
		$printStopMessageValues['exactmatch'] = $vbulletin->GPC['search_exactmatch'];
		$printStopMessageValues['languageid'] = $vbulletin->GPC['search_languageid'];
		foreach ($vbulletin->GPC['search_phrasetype'] AS $k => $v)
		{
			$printStopMessageValues['phrasetype[' . $k . ']'] = $v;
		}
		$printStopMessageValues['transonly'] = $vbulletin->GPC['search_transonly'];
		$printStopMessageValues['product'] = $vbulletin->GPC['search_product'];
	}

//	print_stop_message2('deleted_phrase_successfully', 'phrase', $printStopMessageValues, null, true);
	print_stop_message2('deleted_phrase_successfully', 'phrase', $printStopMessageValues);
}

// #############################################################################

if ($_POST['do'] == 'insert' OR $_POST['do'] == 'update')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'fieldname'            => vB_Cleaner::TYPE_NOHTML,
		'oldfieldname'         => vB_Cleaner::TYPE_NOHTML,
		'languageid'           => vB_Cleaner::TYPE_INT,
		'oldvarname'           => vB_Cleaner::TYPE_STR,
		'varname'              => vB_Cleaner::TYPE_STR,
		'text'                 => vB_Cleaner::TYPE_ARRAY_NOTRIM,
		'ismaster'             => vB_Cleaner::TYPE_INT,
		'sourcefieldname'      => vB_Cleaner::TYPE_NOHTML,
		't'                    => vB_Cleaner::TYPE_BOOL,
		'product'              => vB_Cleaner::TYPE_STR,
		'pagenumber'           => vB_Cleaner::TYPE_UINT,
		'perpage'              => vB_Cleaner::TYPE_UINT,
		'return_to_search'     => vB_Cleaner::TYPE_BOOL,
		'return_to_add'        => vB_Cleaner::TYPE_BOOL,
		'search_searchstring'  => vB_Cleaner::TYPE_STR,
		'search_searchwhere'   => vB_Cleaner::TYPE_UINT,
		'search_casesensitive' => vB_Cleaner::TYPE_BOOL,
		'search_exactmatch'    => vB_Cleaner::TYPE_BOOL,
		'search_languageid'    => vB_Cleaner::TYPE_INT,
		'search_phrasetype'    => vB_Cleaner::TYPE_ARRAY_NOHTML,
		'search_transonly'     => vB_Cleaner::TYPE_BOOL,
		'search_product'       => vB_Cleaner::TYPE_STR,
	));

	$varname = $vbulletin->GPC['varname'];
	$fieldname = $vbulletin->GPC['fieldname'];
	$oldvarname = $vbulletin->GPC['oldvarname'];
	$oldfieldname = $vbulletin->GPC['oldfieldname'];

	if (defined('DEV_AUTOEXPORT') AND DEV_AUTOEXPORT AND $vbulletin->GPC['ismaster'])
	{
		$old_product = vB::getDbAssertor()->getRow('phrase', [
			'languageid' => -1,
			'varname' => $oldvarname,
			'fieldname' => $oldfieldname
		]);
	}

	if ($_POST['do'] == 'update')
	{
		$vbulletin->GPC['ismaster'] = ($vbulletin->GPC['languageid'] == -1) ? true : false;
	}

	$result = vB_Api::instance('phrase')->save(
		$fieldname,
		$varname,
		[
			'text' => $vbulletin->GPC['text'],
			'oldvarname' => $oldvarname,
			'oldfieldname' => $oldfieldname,
			't' => $vbulletin->GPC['t'],
			'ismaster' => $vbulletin->GPC['ismaster'],
			'product' => $vbulletin->GPC['product'],
		]
	);

	print_stop_message_on_api_error($result);

	if (defined('DEV_AUTOEXPORT') AND DEV_AUTOEXPORT AND $vbulletin->GPC['ismaster'])
	{
		require_once(DIR . '/includes/functions_filesystemxml.php');
		$products_to_export = [$vbulletin->GPC['product']];
		if (isset($old_product['product']))
		{
			$products_to_export[] = $old_product['product'];
		}
		autoexport_write_language(-1, $products_to_export);
	}

	$printStopMessageValues = [
		'fieldname' => $vbulletin->GPC['sourcefieldname'],
		'page' => $vbulletin->GPC['pagenumber'],
		'pp' => $vbulletin->GPC['perpage'],
	];

	if ($vbulletin->GPC['return_to_search'])
	{
		$printStopMessageValues['do'] = 'dosearch';
		$printStopMessageValues['searchstring'] = $vbulletin->GPC['search_searchstring'];
		$printStopMessageValues['searchwhere'] = $vbulletin->GPC['search_searchwhere'];
		$printStopMessageValues['casesensitive'] = $vbulletin->GPC['search_casesensitive'];
		$printStopMessageValues['exactmatch'] = $vbulletin->GPC['search_exactmatch'];
		$printStopMessageValues['languageid'] = $vbulletin->GPC['search_languageid'];
		foreach ($vbulletin->GPC['search_phrasetype'] AS $k => $v)
		{
			$printStopMessageValues['phrasetype[' . $k . ']'] = $v;
		}
		$printStopMessageValues['transonly'] = $vbulletin->GPC['search_transonly'];
		$printStopMessageValues['product'] = $vbulletin->GPC['search_product'];
	}

	if ($vbulletin->GPC['return_to_add'])
	{
		$printStopMessageValues['do'] = 'add';
		$printStopMessageValues['fieldname'] = $vbulletin->GPC['fieldname'];
		$printStopMessageValues['product'] = $vbulletin->GPC['product'];
		$printStopMessageValues['return_to_add'] = $vbulletin->GPC['return_to_add'];
	}

	print_stop_message2(array('saved_phrase_x_successfully', $vbulletin->GPC['varname']), 'phrase', $printStopMessageValues);
}

// #############################################################################

if ($_REQUEST['do'] == 'add')
{
	register_js_phrase('default_text_is_empty');
	$vbulletin->input->clean_array_gpc('r', array(
		'fieldname'     => vB_Cleaner::TYPE_NOHTML,
		'pagenumber'    => vB_Cleaner::TYPE_UINT,
		'perpage'       => vB_Cleaner::TYPE_UINT,
		'return_to_add' => vB_Cleaner::TYPE_BOOL,
		'product'       => vB_Cleaner::TYPE_STR,
	));

	// make phrasetype options
	$phrasetypes = vB_Api::instanceInternal('phrase')->fetch_phrasetypes();
	$typeoptions = array();
	$type_product_options = array();
	foreach($phrasetypes AS $fieldname => $phrasetype)
	{
		$typeoptions["$fieldname"] = $phrasetype['title'];
		$type_product_options["$fieldname"] = $phrasetype['product'];
	}

	print_form_header('admincp/phrase', 'insert');
	print_table_header($vbphrase['add_new_phrase']);

	if (!empty($vb5_config['Misc']['debug']))
	{
		$thistitle = construct_phrase($vbphrase['insert_into_master_language_developer_option'], "<b></b>");
		print_yes_no_row($thistitle, 'ismaster', 1);
	}

	print_select_row($vbphrase['phrase_type'], 'fieldname', $typeoptions, $vbulletin->GPC['fieldname']);

	print_select_row($vbphrase['product'], 'product', fetch_product_list(), ($vbulletin->GPC['product'] ?? $type_product_options[$vbulletin->GPC['fieldname']]));

	// main input fields
	print_input_row($vbphrase['varname'], 'varname', '', 1, 60);
	//not sure if we should be overriding the text dir here or not.
	$attributes = [
		'dir' => 'ltr',
		'rows' => 5,
		'cols' => 60,
	];
	$sourcetextareaid = print_textarea_row2($vbphrase['text_gcpglobal'], 'text[0]', '', $attributes, [], false);

	if ($vb5_config['Misc']['debug'])
	{
		$thistitle = construct_phrase($vbphrase['add_another_phrase_after_saving'], "<b></b>");
		print_yes_no_row($thistitle, 'return_to_add', $vbulletin->GPC['return_to_add']);
	}

	// do translation boxes
	// this appears 3 places, keep them in sync
	print_table_header($vbphrase['translations']);
	print_description_row("
			<ul>
				<li>$vbphrase[phrase_translation_desc_1]</li>
				<li>$vbphrase[phrase_translation_desc_2]</li>
				<li>$vbphrase[phrase_translation_desc_3]</li>
				<li>$vbphrase[phrase_translation_desc_4]</li>
			</ul>
		",
		0, 2, 'tfoot'
	);

	$languages = vB::getDatastore()->getValue('languagecache');
	foreach($languages AS $lang)
	{
		//we need this prior to calling the print row function because we have to pass the button code
		//so create it custom
		$textareaid = 'text_' . $lang['languageid'];
		$data = [
			'sourceid' => $sourcetextareaid,
			'targetid' => $textareaid,
		];

		$button = construct_event_button($vbphrase['copy_default_text'], 'js-copy-default', $data);
		$label = construct_phrase($vbphrase['x_translation'], "<b>{$lang['title']}</b>") . " <dfn>($vbphrase[optional])</dfn><br />" . $button;

		$attributes = [
			'dir' => $lang['direction'],
			'id' => $textareaid,
			'rows' => 5,
			'cols' => 60,
		];

		print_textarea_row2($label, 'text[' . $lang['languageid'] . ']', '', $attributes, [], false);

//		print_description_row('<img src="images/clear.gif" width="1" height="1" alt="" />', 0, 2, 'thead');
	}

	construct_hidden_code('page', $vbulletin->GPC['pagenumber']);
	construct_hidden_code('perpage', $vbulletin->GPC['perpage']);
	construct_hidden_code('sourcefieldname', $vbulletin->GPC['fieldname']);
	print_submit_row($vbphrase['save']);

}

// #############################################################################

if ($_REQUEST['do'] == 'edit')
{
	register_js_phrase('default_text_is_empty');
	$vbulletin->input->clean_array_gpc('r', array(
		'e'                    => vB_Cleaner::TYPE_ARRAY_ARRAY,
		'delete'               => vB_Cleaner::TYPE_ARRAY_ARRAY,
		'pagenumber'           => vB_Cleaner::TYPE_UINT,
		'perpage'              => vB_Cleaner::TYPE_UINT,
		'fieldname'            => vB_Cleaner::TYPE_NOHTML,
		'varname'              => vB_Cleaner::TYPE_STR,
		't'                    => vB_Cleaner::TYPE_BOOL, // Display only the translations and no delete button
		'return_to_search'     => vB_Cleaner::TYPE_BOOL,
		'search_searchstring'  => vB_Cleaner::TYPE_STR,
		'search_searchwhere'   => vB_Cleaner::TYPE_UINT,
		'search_casesensitive' => vB_Cleaner::TYPE_BOOL,
		'search_exactmatch'    => vB_Cleaner::TYPE_BOOL,
		'search_languageid'    => vB_Cleaner::TYPE_INT,
		'search_phrasetype'    => vB_Cleaner::TYPE_ARRAY_NOHTML,
		'search_transonly'     => vB_Cleaner::TYPE_BOOL,
		'search_product'       => vB_Cleaner::TYPE_STR,
	));
	if (!empty($vbulletin->GPC['delete']))
	{
		$editvarname =& $vbulletin->GPC['delete'];
		$_REQUEST['do'] = 'delete';
	}
	else
	{
		$editvarname =& $vbulletin->GPC['e'];
	}

	// make phrasetype options
	$phrasetypes = vB_Api::instanceInternal('phrase')->fetch_phrasetypes();
	$typeoptions = array();
	foreach($phrasetypes AS $fieldname => $phrasetype)
	{
		$typeoptions["$fieldname"] = $phrasetype['title'];
	}

	if (!empty($editvarname))
	{
		foreach($editvarname AS $fieldname => $varnames)
		{
			foreach($varnames AS $varname => $type)
			{
				$varname = urldecode($varname);
				$phrase = vB::getDbAssertor()->getRow('fetchPhrassesByLanguage',
					array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_STORED,
						'varname' => $varname,
						'fieldname' => $fieldname
					)
				);
				break;
			}
		}
	}
	else if ($vbulletin->GPC['phraseid'])
	{
		$phrase = vB::getDbAssertor()->getRow('phrase', array(
			vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'phraseid' => $vbulletin->GPC['phraseid'],
		));
	}
	else if ($vbulletin->GPC['fieldname'] AND $vbulletin->GPC['varname'])
	{
		$varname = urldecode($vbulletin->GPC['varname']);
		$phrase = vB::getDbAssertor()->getRow('fetchPhrassesByLanguage',
			array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_STORED,
				'varname' => $varname,
				'fieldname' => $vbulletin->GPC['fieldname']
			)
		);
	}

	if (!$phrase['phraseid'] OR !$phrase['varname'])
	{
		print_stop_message2('no_phrases_matched_your_query');
	}

	if ($_REQUEST['do'] == 'delete')
	{
		$vbulletin->GPC['phraseid'] = $phrase['phraseid'];
	}
	else
	{
		// delete link
		if (($vb5_config['Misc']['debug'] OR $phrase['languageid'] != '-1') AND !$vbulletin->GPC['t'])
		{
			print_form_header('admincp/phrase', 'delete');
			construct_hidden_code('phraseid', $phrase['phraseid']);

			if ($vbulletin->GPC['return_to_search'])
			{
				construct_hidden_code('return_to_search', '1');
				construct_hidden_code('search_searchstring', $vbulletin->GPC['search_searchstring']);
				construct_hidden_code('search_searchwhere', $vbulletin->GPC['search_searchwhere']);
				construct_hidden_code('search_casesensitive', $vbulletin->GPC['search_casesensitive']);
				construct_hidden_code('search_exactmatch', $vbulletin->GPC['search_exactmatch']);
				construct_hidden_code('search_languageid', $vbulletin->GPC['search_languageid']);
				foreach ($vbulletin->GPC['search_phrasetype'] AS $k => $v)
				{
					construct_hidden_code('search_phrasetype[' . $k . ']', $v);
				}
				construct_hidden_code('search_transonly', $vbulletin->GPC['search_transonly']);
				construct_hidden_code('search_product', $vbulletin->GPC['search_product']);
			}

			print_table_header($vbphrase['if_you_would_like_to_remove_this_phrase'] . ' &nbsp; &nbsp; <input type="submit" class="button" tabindex="1" value="' . $vbphrase['delete'] . '" />');
			print_table_footer();
		}

		print_form_header('admincp/phrase', 'update', false, true, 'phraseform');

		if ($vbulletin->GPC['return_to_search'])
		{
			construct_hidden_code('return_to_search', '1');
			construct_hidden_code('search_searchstring', $vbulletin->GPC['search_searchstring']);
			construct_hidden_code('search_searchwhere', $vbulletin->GPC['search_searchwhere']);
			construct_hidden_code('search_casesensitive', $vbulletin->GPC['search_casesensitive']);
			construct_hidden_code('search_exactmatch', $vbulletin->GPC['search_exactmatch']);
			construct_hidden_code('search_languageid', $vbulletin->GPC['search_languageid']);
			foreach ($vbulletin->GPC['search_phrasetype'] AS $k => $v)
			{
				construct_hidden_code('search_phrasetype[' . $k . ']', $v);
			}
			construct_hidden_code('search_transonly', $vbulletin->GPC['search_transonly']);
			construct_hidden_code('search_product', $vbulletin->GPC['search_product']);
		}

		print_table_header(construct_phrase($vbphrase['x_y_id_z'], iif(
			$phrase['languageid'] == 0,
			$vbphrase['custom_phrase'],
			$vbphrase['standard_phrase']
		), $phrase['varname'], $phrase['phraseid']));
		construct_hidden_code('oldvarname', $phrase['varname']);
		construct_hidden_code('t', $vbulletin->GPC['t']);

		if ($phrase['fieldname'] == 'cphelptext' AND $vb5_config['Misc']['debug'] AND defined('DEV_AUTOEXPORT') AND DEV_AUTOEXPORT)
		{
			// Hard-coded dev-only warning.
			list($phrase_cphelp_script) = explode('_', $phrase['varname']);
			print_description_row('Edit Control Panel Help Text phrases in the <a href="admincp/help.php?do=manage&amp;script=' . $phrase_cphelp_script . '">Admin Help Manager</a> so they export to <code>vbulletin-adminhelp.xml</code>. They are not exported to <code>vbulletin-language.xml</code>. ', 0, 2, 'warning" align="center');
			unset($phrase_cphelp_script);
		}

		if ($vb5_config['Misc']['debug'])
		{
			print_select_row($vbphrase['language'], 'languageid', array('-1' => $vbphrase['master_language'], '0' => $vbphrase['custom_language']), $phrase['languageid']);
			construct_hidden_code('oldfieldname', $phrase['fieldname']);
			print_select_row($vbphrase['phrase_type'], 'fieldname', $typeoptions, $phrase['fieldname']);
		}
		else
		{
			construct_hidden_code('languageid', $phrase['languageid']);
			construct_hidden_code('oldfieldname', $phrase['fieldname']);
			construct_hidden_code('fieldname', $phrase['fieldname']);
		}

		print_select_row($vbphrase['product'], 'product', fetch_product_list(), $phrase['product']);

		$autofocus = true;
		if (($phrase['languageid'] == 0 OR $vb5_config['Misc']['debug']) AND !$vbulletin->GPC['t'])
		{
			print_input_row($vbphrase['varname'], 'varname', $phrase['varname'], 1, 50);

			$attributes = [
				'dir' => 'ltr',
				'rows' => 5,
				'cols' => 60,
			];
			if($autofocus)
			{
				$attributes['autofocus'] = null;
				$autofocus = false;
			}

			$sourcetextareaid = print_textarea_row2($vbphrase['text_gcpglobal'], 'text[0]', htmlspecialchars_uni($phrase['text']), $attributes, [], false);
		}
		else
		{
			$sourcetextareaid = 'default_phrase';
			print_label_row($vbphrase['varname'], '$vbphrase[<b>' . $phrase['varname'] . '</b>]');
			construct_hidden_code('varname', $phrase['varname']);

			print_label_row($vbphrase['text_gcpglobal'], nl2br(htmlspecialchars_uni($phrase['text'])) .
				'<input type="hidden" id="' . $sourcetextareaid . '" value="' . htmlspecialchars_uni($phrase['text']) . '" />');
			if (!$vbulletin->GPC['t'])
			{
				construct_hidden_code('text[0]', $phrase['text']);
			}
		}

		// do translation boxes
		// this appears 3 places, keep them in sync
		print_table_header($vbphrase['translations']);
		print_description_row("
				<ul>
					<li>$vbphrase[phrase_translation_desc_1]</li>
					<li>$vbphrase[phrase_translation_desc_2]</li>
					<li>$vbphrase[phrase_translation_desc_3]</li>
					<li>$vbphrase[phrase_translation_desc_4]</li>
				</ul>
			",
			0, 2, 'tfoot'
		);

		$languages = vB::getDatastore()->getValue('languagecache');

		$translations = vB::getDbAssertor()->assertQuery('phrase',
			array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
				vB_dB_Query::CONDITIONS_KEY => array(
					array('field' => 'varname', 'value' => $phrase['varname'], 'operator' => vB_dB_Query::OPERATOR_EQ),
					array('field' => 'languageid', 'value' => $phrase['languageid'], 'operator' => vB_dB_Query::OPERATOR_NE),
					array('field' => 'fieldname', 'value' => $phrase['fieldname'], 'operator' => vB_dB_Query::OPERATOR_EQ),
				)
			)
		);

		$text = array_fill_keys(array_column($languages, 'languageid'), '');
		foreach ($translations AS $translation)
		{
			$text[$translation['languageid']] = $translation['text'];
		}
		// remove escape junk from javascript phrases for nice editable look
		fetch_js_unsafe_string($text);

		foreach($languages AS $lang)
		{
			//we need this prior to calling the print row function because we have to pass the button code
			//so create it custom
			$textareaid = 'text_' . $lang['languageid'];
			$data = [
				'sourceid' => $sourcetextareaid ,
				'targetid' => $textareaid,
			];

			$button = construct_event_button($vbphrase['copy_default_text'], 'js-copy-default', $data);
			$label = construct_phrase($vbphrase['x_translation'], "<b>{$lang['title']}</b>") . " <dfn>($vbphrase[optional])</dfn><br />" . $button;

			$attributes = [
				'dir' => $lang['direction'],
				'id' => $textareaid,
				'rows' => 5,
				'cols' => 60,
			];
			if($autofocus)
			{
				$attributes['autofocus'] = null;
				$autofocus = false;
			}

			print_textarea_row2($label, 'text[' . $lang['languageid'] . ']', htmlspecialchars_uni($text[$lang['languageid']]), $attributes, [], false);
//			print_description_row('<img src="images/clear.gif" width="1" height="1" alt="" />', 0, 2, 'thead');
		}

		construct_hidden_code('page', $vbulletin->GPC['pagenumber']);
		construct_hidden_code('perpage', $vbulletin->GPC['perpage']);
		construct_hidden_code('sourcefieldname', $vbulletin->GPC['fieldname']);
		print_submit_row($vbphrase['save']);
	}
}

// #############################################################################

if ($_REQUEST['do'] == 'delete')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'pagenumber'           => vB_Cleaner::TYPE_UINT,
		'perpage'              => vB_Cleaner::TYPE_UINT,
		'fieldname'            => vB_Cleaner::TYPE_NOHTML,
		'return_to_search'     => vB_Cleaner::TYPE_BOOL,
		'search_searchstring'  => vB_Cleaner::TYPE_STR,
		'search_searchwhere'   => vB_Cleaner::TYPE_UINT,
		'search_casesensitive' => vB_Cleaner::TYPE_BOOL,
		'search_exactmatch'    => vB_Cleaner::TYPE_BOOL,
		'search_languageid'    => vB_Cleaner::TYPE_INT,
		'search_phrasetype'    => vB_Cleaner::TYPE_ARRAY_NOHTML,
		'search_transonly'     => vB_Cleaner::TYPE_BOOL,
		'search_product'       => vB_Cleaner::TYPE_STR,
	));

	//Check if Phrase belongs to Master Language -> only able to delete if $vbulletin->debug=1
	$getvarname = vB::getDbAssertor()->getRow('phrase', array(
			vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'phraseid' => $vbulletin->GPC['phraseid'],
	));

	/**TODO
	 * This query should be checked; languageid = '-1' seems weird; string or integer ?
	 */
	$conditions = array();
	$conditions[] = array('field' => 'varname', 'value' => $getvarname['varname'], 'operator' => vB_dB_Query::OPERATOR_EQ);
	$conditions[] = array('field' => 'languageid', 'value' => '-1', 'operator' => vB_dB_Query::OPERATOR_EQ);
	if($getvarname['fieldname'])
	{
		$conditions[] = array('field' => 'fieldname', 'value' => $getvarname['fieldname'], 'operator' => vB_dB_Query::OPERATOR_EQ);
	}
	$ismasterphrase = vB::getDbAssertor()->getRow('phrase',
		array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT, vB_dB_Query::CONDITIONS_KEY => $conditions)
	);
	if (!$vb5_config['Misc']['debug'] AND $ismasterphrase)
	{
		print_stop_message2('cant_delete_master_phrase');
	}

	$deleteConfValues = array(
		'sourcefieldname' => $vbulletin->GPC['fieldname'],
		'fieldname'       => $getvarname['fieldname'],
		'pagenumber'      => $vbulletin->GPC['pagenumber'],
		'perpage'         => $vbulletin->GPC['perpage']
	);

	if ($vbulletin->GPC['return_to_search'])
	{
		$deleteConfValues['return_to_search'] = '1';
		$deleteConfValues['search_searchstring'] = $vbulletin->GPC['search_searchstring'];
		$deleteConfValues['search_searchwhere'] = $vbulletin->GPC['search_searchwhere'];
		$deleteConfValues['search_casesensitive'] = $vbulletin->GPC['search_casesensitive'];
		$deleteConfValues['search_exactmatch'] = $vbulletin->GPC['search_exactmatch'];
		$deleteConfValues['search_languageid'] = $vbulletin->GPC['search_languageid'];
		foreach ($vbulletin->GPC['search_phrasetype'] AS $k => $v)
		{
			$deleteConfValues['search_phrasetype[' . $k . ']'] = $v;
		}
		$deleteConfValues['search_transonly'] = $vbulletin->GPC['search_transonly'];
		$deleteConfValues['search_product'] = $vbulletin->GPC['search_product'];
	}

	print_delete_confirmation('phrase', $vbulletin->GPC['phraseid'], 'phrase', 'kill', 'phrase', $deleteConfValues, $vbphrase['if_you_delete_this_phrase_translations_will_be_deleted']);

}

// #############################################################################

if ($_REQUEST['do'] == 'modify')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'fieldname'  => vB_Cleaner::TYPE_NOHTML,
		'perpage'    => vB_Cleaner::TYPE_INT,
		'pagenumber' => vB_Cleaner::TYPE_INT,
		'showpt'     => vB_Cleaner::TYPE_ARRAY_UINT,
	));

	$phrasetypes = vB_Api::instanceInternal('phrase')->fetch_phrasetypes();

	// make sure $fieldname is valid
	if ($vbulletin->GPC['fieldname'] != '' AND !isset($phrasetypes["{$vbulletin->GPC['fieldname']}"]))
	{
		$vbulletin->GPC['fieldname'] = 'global';
	}

	// check display values are valid
	if ($vbulletin->GPC['perpage'] < 1)
	{
		$vbulletin->GPC['perpage'] = 15;
	}
	if ($vbulletin->GPC['pagenumber'] < 1)
	{
		$vbulletin->GPC['pagenumber'] = 1;
	}

	// count phrases
	$countphrases = vB::getDbAssertor()->getRow('fetchCountPhrasesByLang',
		array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_METHOD, 'fieldname' => $vbulletin->GPC['fieldname'])
	);

	$numphrases =& $countphrases['total'];
	$numpages = ceil($numphrases / $vbulletin->GPC['perpage']);

	if ($numpages < 1)
	{
		$numpages = 1;
	}
	if ($vbulletin->GPC['pagenumber'] > $numpages)
	{
		$vbulletin->GPC['pagenumber'] = $numpages;
	}

	$showprev = false;
	$shownext = false;

	if ($vbulletin->GPC['pagenumber'] > 1)
	{
		$showprev = true;
	}
	if ($vbulletin->GPC['pagenumber'] < $numpages)
	{
		$shownext = true;
	}

	$pageoptions = array();
	for ($i = 1; $i <= $numpages; $i++)
	{
		$pageoptions["$i"] = "$vbphrase[page_gcpglobal] $i / $numpages";
	}

	$phraseoptions = array('' => $vbphrase['all_phrase_groups']);
	foreach($phrasetypes AS $fieldname => $type)
	{
		$phraseoptions["$fieldname"] = $type['title'];
	}

	print_form_header('admincp/phrase', 'modify', false, true, 'navform', '90%', '', true, 'get');
	$phraseTypeSelect = construct_select_options($phraseoptions, $vbulletin->GPC['fieldname']);
	$pageSelect = construct_select_options($pageoptions, $vbulletin->GPC['pagenumber']);
	$prevDisabled = $showprev ? '' : ' disabled="disabled"';
	$nextDisabled = $shownext ? '' : ' disabled="disabled"';
	echo <<<EOT
<style>
.phrase-pagination-header {
	display: flex;
	justify-content: space-around;
	align-items: center;
	/*
	The old pagination header seemingly had a ton of vertical whitespace everywhere but on the
	"phrases to show per page" label, but that was actually because that label was getting
	horizontally squished and artificially pushing out the height of the whole element.
	Now that the squish issue is gone, the "correct" behavior would be without the padding,
	but it just doesn't feel quite the same or as good without ANY vertical padding... So
	inserting in some padding. We could also opt for min-height here instead of top & bottom
	padding.
	*/
	padding-top: 10px;
	padding-bottom: 10px;
}
.phrase-pagination-header > div {
	display: flex;
	align-items: center;
}
/* Give the labels some spacing equal to the buttons' */
.phrase-pagination-header > div > span {
	margin-left: 0px;
	margin-right: 10px;
}
html[dir="rtl"] .phrase-pagination-header > div > span {
	margin-right: 0px;
	margin-left: 10px;
}
.left-column {
	flex-basis: 30%;
}
.middle-column {
	flex-basis: 40%;
	/* grow as much as needed to not leave any "gaps" between the 3 columns. We don't want
	to set a fixed width because that won't allow for "sacrificial" room for the fixed width
	of the left-column for smaller viewports.
		*/
	flex-grow: 1;
	justify-content: center;
}

/* Pop the pagination select out into its own line above the prev/next buttons if screensize
	is too cramped, in media query as to not wrap (e.g. the "go button") too early */
@media(max-width: 1024px) {
	.left-column,
	.middle-column,
	.right-column {
		flex-wrap: wrap;
	}
	.middle-column select {
		flex: 0 0 100%;
		order: -1;
	}
}
.right-column {
	flex-basis: 40%;
	justify-content: end;
}
</style>

<div class="phrase-pagination-header thead">
	<div class="left-column ">
		<span>{$vbphrase['phrase_type']}:</span>
		<select name="fieldname" class="bginput" tabindex="1"
			onchange="this.form.page.selectedIndex = 0; this.form.submit()"
		>
			$phraseTypeSelect
		</select>
	</div>

	<div class="middle-column " >
		<input type="button" $prevDisabled class="button" value="&laquo; {$vbphrase['prev']}" tabindex="1" onclick="this.form.page.selectedIndex -= 1; this.form.submit()" />
		<select name="page" tabindex="1" onchange="this.form.submit()" class="bginput">$pageSelect</select>
		<input type="button" $nextDisabled class="button" value="{$vbphrase['next']} &raquo;" tabindex="1" onclick="this.form.page.selectedIndex += 1; this.form.submit()" />
	</div>

	<div class="right-column ">
		<span>{$vbphrase['phrases_to_show_per_page']}:</span>
		<input type="text" class="bginput" name="perpage" value="{$vbulletin->GPC['perpage']}" tabindex="1" size="5" />
		<input type="submit" class="button" value="{$vbphrase['go']}" tabindex="1" accesskey="s" />
	</div>
</div>

EOT;
	print_table_footer();

	/*print_form_header('admincp/phrase', 'modify');
	print_table_header($vbphrase['controls'], 3);
	echo '
	<tr>
		<td class="tfoot">
			<select name="fieldname" class="bginput" tabindex="1" onchange="this.form.page.selectedIndex = 0; this.form.submit()">' . construct_select_options($phraseoptions, $vbulletin->GPC['fieldname']) . '</select><br />
			<table cellpadding="0" cellspacing="0" border="0">
			<tr>
				<td><b>Show Master Phrases?</b> &nbsp; &nbsp; &nbsp;</td>
				<td><label for="rb_smy"><input type="radio" name="showpt[master]" id="rb_smy" value="1"' . $checked['master1'] . ' />' . $vbphrase['yes'] . '</label></td>
				<td><label for="rb_smn"><input type="radio" name="showpt[master]" id="rb_smn" value="0"' . $checked['master0'] . ' />' . $vbphrase['no'] . '</label></td>
			</tr>
			<tr>
				<td><b>Show Custom Phrases?</b> &nbsp; &nbsp; &nbsp;</td>
				<td><label for="rb_scy"><input type="radio" name="showpt[custom]" id="rb_scy" value="1"' . $checked['custom1'] . ' />' . $vbphrase['yes'] . '</label></td>
				<td><label for="rb_scn"><input type="radio" name="showpt[custom]" id="rb_scn" value="0"' . $checked['custom0'] . ' />' . $vbphrase['no'] . '</label></td>
			</tr>
			</table>
		</td>
		<td class="tfoot" align="center">
			<div style="margin-bottom:4px"><b>' . $vbphrase['phrases_to_show_per_page'] . ':</b> <input type="text" class="bginput" name="perpage" value="' . $vbulletin->GPC['perpage'] . '" tabindex="1" size="5" /></div>
			<input type="button"' . iif(!$showprev, ' disabled="disabled"') . ' class="button" value="&laquo; ' . $vbphrase['prev'] . '" tabindex="1" onclick="this.form.page.selectedIndex -= 1; this.form.submit()" />' .
			'<select name="page" tabindex="1" onchange="this.form.submit()" class="bginput">' . construct_select_options($pageoptions, $vbulletin->GPC['pagenumber']) . '</select>' .
			'<input type="button"' . iif(!$shownext, ' disabled="disabled"') . ' class="button" value="' . $vbphrase['next'] . ' &raquo;" tabindex="1" onclick="this.form.page.selectedIndex += 1; this.form.submit()" />
		</td>
		<td class="tfoot" align="center"><input type="submit" class="button" value=" ' . $vbphrase['go'] . ' " tabindex="1" accesskey="s" /></td>
	</tr>
	';
	print_table_footer();*/

	print_phrase_ref_popup_javascript();

	$masterphrases = vB::getDbAssertor()->assertQuery('fetchPhrasesOrderedPaged',
		array(
			vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_METHOD,
			'fieldname' => $vbulletin->GPC['fieldname'],
			vB_dB_Query::PARAM_LIMITPAGE => ($vbulletin->GPC['pagenumber'] - 1),
			vB_dB_Query::PARAM_LIMIT => $vbulletin->GPC['perpage']
		)
	);

	$phrasenames = array();
	if ($masterphrases AND $masterphrases->valid())
	{
		foreach ($masterphrases AS $masterphrase)
		{
			$phrasenames [] = array('varname' => $masterphrase['varname'], 'fieldname' => $masterphrase['fieldname']);
		}
	}
	unset($masterphrase);

	$cphrases = array();
	if (!empty($phrasenames))
	{
		$phrases = vB::getDbAssertor()->assertQuery('fetchKeepNames', array(
			vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_METHOD,
			'keepnames' => $phrasenames,
		));

		unset($phrasenames);
		foreach ($phrases as $phrase)
		{
			$cphrases["{$phrase['fieldname']}"]["{$phrase['varname']}"]["{$phrase['languageid']}"] = $phrase['phraseid'];
		}
		unset($phrase);
	}

	$languages = vB::getDatastore()->getValue('languagecache');
	$numlangs = sizeof($languages);
	$colspan = $numlangs + 2;

	print_form_header('admincp/phrase', 'add', false, true, 'phraseform', '90%', '', true, 'post', 1);
	construct_hidden_code('fieldname', $vbulletin->GPC['fieldname']);

	echo "\t<colgroup span=\"" . (sizeof($languages) + 1) . "\"></colgroup>\n";
	echo "\t<col style=\"white-space:nowrap\"></col>\n";

	// show phrases
	foreach($cphrases AS $_fieldname => $varnames)
	{
		print_table_header(construct_phrase($vbphrase['x_phrases'], $phrasetypes["$_fieldname"]['title']) . " <span class=\"normal\">(fieldname = $_fieldname)</span>", $colspan);

		$headings = array($vbphrase['varname']);
		foreach($languages AS $language)
		{
			$headings[] = "<a href=\"javascript:js_open_phrase_ref({$language['languageid']},'$_fieldname');\" title=\"" . $vbphrase['view_quickref_glanguage'] . ": {$language['title']}\">{$language['title']}</a>";
		}
		$headings[] = '';
		print_cells_row($headings, 0, 'thead');

		$yesImage = get_cpstyle_href('cp_tick_yes.gif');
		$noImage = get_cpstyle_href('cp_tick_no.gif');

		ksort($varnames);
		foreach($varnames AS $varname => $phrase)
		{
			$cell = array(construct_wrappable_varname($varname, 'font-weight:bold;', 'smallfont', 'span'));
			if (isset($phrase['-1']))
			{
				$phraseid = $phrase['-1'];
				$custom = 0;
			}
			else

			{
				$phraseid = $phrase['0'];
				$custom = 1;
			}

			foreach($languages AS $language)
			{
				if(isset($phrase["{$language['languageid']}"]))
				{
					$cell[] = "<img src=\"$yesImage\" alt=\"\" />";
				}
				else
				{
					$cell[] = "<img src=\"$noImage\" alt=\"\" />";
				}
			}
			$cell[] = '<span class="smallfont">' . construct_link_code(fetch_tag_wrap($vbphrase['edit'], 'span class="col-i"', $custom==1), "phrase.php?" . vB::getCurrentSession()->get('sessionurl') . "do=edit&amp;phraseid=$phraseid&amp;page=" . $vbulletin->GPC['pagenumber'] . "&amp;pp=" . $vbulletin->GPC['perpage'] . "&amp;fieldname=" . $vbulletin->GPC['fieldname']) . iif($custom OR $vb5_config['Misc']['debug'], construct_link_code(fetch_tag_wrap($vbphrase['delete'], 'span class="col-i"', $custom==1), "phrase.php?" . vB::getCurrentSession()->get('sessionurl') . "do=delete&amp;phraseid=$phraseid&amp;page=" . $vbulletin->GPC['pagenumber'] . "&amp;pp=" . $vbulletin->GPC['perpage'] . "&amp;fieldname=" . $vbulletin->GPC['fieldname']), '') . '</span>';
			print_cells_row($cell, 0, 0, 0, 'top', 0);
		}
	}

	print_table_footer($colspan, "
		<input type=\"button\" class=\"button\" value=\"" . $vbphrase['search_in_phrases_glanguage'] .
			"\" tabindex=\"1\" onclick=\"vBRedirect('admincp/phrase.php?&amp;do=search');\" />
		&nbsp; &nbsp;
		<input type=\"button\" class=\"button\" value=\"" . $vbphrase['add_new_phrase'] .
			"\" tabindex=\"1\" onclick=\"vBRedirect('admincp/phrase.php?" .
			"do=add&amp;fieldname=" . $vbulletin->GPC['fieldname'] . "&amp;page=" . $vbulletin->GPC['pagenumber'] . "&amp;pp=" .
			$vbulletin->GPC['perpage'] . "');\" />
		&nbsp; &nbsp;
		<input type=\"button\" class=\"button\" value=\"" . $vbphrase['find_orphan_phrases'] .
			"\" tabindex=\"1\" onclick=\"vBRedirect('admincp/phrase.php?do=findorphans');\" />
	");


}

// #############################################################################

print_cp_footer();

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 115002 $
|| #######################################################################
\*=========================================================================*/
