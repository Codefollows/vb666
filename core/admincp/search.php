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
define('CVS_REVISION', '$RCSfile$ - $Revision: 115851 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
global $phrasegroups, $specialtemplates, $vbphrase, $vbulletin;
$phrasegroups = array('cphome');
$specialtemplates = array();

// ########################## REQUIRE BACK-END ############################
require_once(dirname(__FILE__) . '/global.php');
require_once(DIR . '/includes/adminfunctions_help.php');

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

$vb5_config =& vB::getConfig();


if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'search';
}

// #########################################################################

print_cp_header($vbphrase['search_results']);

if ($_REQUEST['do'] == 'search')
{
	print_form_header('admincp/search', 'dosearch', 1, 1, '');
	print_table_header("Search");
	print_input_row("Search Term", 'terms', '');
	print_submit_row("Search", 0);
	print_cp_footer();
}

// ############################### start do upload help XML ##############
if ($_REQUEST['do'] == 'dosearch')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'terms' 		=> vB_Cleaner::TYPE_STR,
		'page'			=> vB_Cleaner::TYPE_INT,
		'perpage'		=> vB_Cleaner::TYPE_INT,
	));

	$results_per_page = ($vbulletin->GPC['perpage']) ? $vbulletin->GPC['perpage'] : 20;
	$current_page = ($vbulletin->GPC['page']) ? $vbulletin->GPC['page'] : 1;

	$assertor = vB::getDbAssertor();
	$results = admincp_search_get_search_results($assertor, $vbulletin->GPC['terms'], $vb5_config);
 	$results = admincp_search_convert_phrase_to_title($assertor, $results);
 	if (empty($results))
 	{
		// Defaults pulled from https://dev.mysql.com/doc/refman/8.0/en/server-system-variables.html#sysvar_ft_min_word_len
		$minLen = $assertor->getRow('showVariablesLike', ['var' => 'ft_min_word_len']);
		$minLen = $minLen['Value'] ?? 4;
		$maxLen = $assertor->getRow('showVariablesLike', ['var' => 'ft_max_word_len']);
		$maxLen = $maxLen['Value'] ?? 20;
 		admincp_search_no_results($vbulletin->GPC['terms'], $minLen, $maxLen);
 	}
 	else
 	{
 		$pagingInfo = admincp_search_result_paging_info($results, $current_page, $results_per_page);
 		create_paging_urls($pagingInfo, $vbulletin->GPC['terms']);
 		$offset = $pagingInfo['startcount'] - 1;
 		$length = $offset  + $results_per_page;
 		$length = ($length > $pagingInfo['totalcount']) ? $pagingInfo['totalcount'] : $results_per_page;
 		$results = array_slice($results, $offset, $length);
 		admincp_search_display_paging($pagingInfo, $vbulletin->GPC['terms']);
 		admincp_search_displaysearch_results($results, $pagingInfo, $vbulletin->GPC['terms']);
 	}
	print_cp_footer();
}

/*
 *	Helper Functions
 */
function admincp_search_get_search_results($assertor, $terms, $vb5_config)
{
	$help_matches = [];
	$option_matches = [];
	$settings_matches = [];
	$settinggroups_matches = [];
	$settings_phrases = [];
	$tmp_results = [];

	$navcpMap = fetch_xml_data();
	$i = 1;

	$rows = $assertor->assertQuery('admincpSearch', ['terms' => $terms]);
	foreach($rows AS $row)
	{
		$varname = preg_replace('#_(text|title)$#', '', $row['varname']);

		switch ($row['fieldname'])
		{
			case '':
				//$option_matches[$varname] = 1;
				break;
			case 'cphelptext':
				//get the helptextid from the phrase name
				$help_matches[$varname] = "var_$i";
				$tmp_results["var_$i"] = 1;
				break;
			case 'cphome':
			case 'cpglobal':
				if (isset($navcpMap[$varname]))
				{
					$tmp_results["var_$i"] = [
						'urls' => ['admincp/' . $navcpMap[$varname]['link']],
						'title_phrase' => $navcpMap[$varname]['text'],
						'description' => 'description'
					];
				}
				break;
			case 'vbsettings':
				if (preg_match('#^setting_(?<varname>.*)_(title|desc)$#i', $row['varname'], $matches))
				{
					$varname = $matches['varname'];
					$settings_matches[$varname] = $varname;
					$settings_phrases["setting_{$varname}_title"] = "setting_{$varname}_title";
					$tmp_results["var_$i"] = $varname;
				}
				else if (preg_match('#^settinggroup_(?<groupname>.*)$#i', $row['varname'], $matches))
				{
					$phrasename = $row['varname'];
					$groupname = $matches['groupname'];
					$settinggroups_matches[$phrasename] = $groupname;
					$settings_phrases[$phrasename] = $phrasename;
					$tmp_results["var_$i"] = $phrasename;
				}
				break;
			default:
				break;
		}

		$i++;
	}

	// AFAIK setting/settinggroup titles don't have fancy replacements, so skipping
	// call to vB_Library_Phrase::replaceOptionsAndConfigValuesInPhrase() unlike the
	// help phrases.
	$phrases = vB_Api::instance('phrase')->fetch($settings_phrases);
	$mapSettings = [];
	$settings = $assertor->assertQuery('setting', ['varname' => $settings_matches]);
	foreach ($settings AS $__row)
	{
		$__varname = $__row['varname'];
		$__group = $__row['grouptitle'];
		$__title = $settings_phrases["setting_{$__varname}_title"];
		$__title = $phrases[$__title] ?? $__title;
		$__url = "admincp/options.php?do=options&dogroup={$__group}#{$__varname}";
		$mapSettings[$__varname] = [
			'urls' => [$__url],
			'title_phrase' => $__title,
			'optiongroup' => $__group,
		];
	}
	$mapSettingGroups = [];
	foreach ($settinggroups_matches AS $__phrasename => $__group)
	{
		$__title = $phrases[$__phrasename] ?? $__phrasename;
		$__url = "admincp/options.php?do=options&dogroup={$__group}";
		$mapSettingGroups[$__phrasename] = [
			'urls' => [$__url],
			'title_phrase' => $__title,
			'optiongroup' => $__group,
		];
	}

	$mapResult = [];
	$unique_help_matches = admincp_search_help_entry_to_info_array($assertor, $help_matches, $mapResult);

	// $tmp_results helps keep sorting by relevance.
	foreach ($tmp_results as $key => $value)
	{
		if (!is_array($value))
		{
			if (isset($mapResult[$key]))
			{
				$tmp_results[$key] = $unique_help_matches[$mapResult[$key]];
			}
			else if (isset($mapSettings[$value]))
			{
				$tmp_results[$key] = $mapSettings[$value];
			}
			else if (isset($mapSettingGroups[$value]))
			{
				$tmp_results[$key] = $mapSettingGroups[$value];
			}
			else
			{
				unset($tmp_results[$key]);
			}
		}
	}


	// Hide certain setting groups from results outside of debug mode.
	// TODO: Also hide setting or settinggroup iff it has a required adminperm
	// and current user is missing it?
	$rmgroups = empty($vb5_config['Misc']['debug']) ? ['version', 'versionsafe'] : [];
	$results = [];
	foreach($tmp_results AS $info)
	{
		foreach ($rmgroups AS $__check)
		{
			if (($info['optiongroup'] ?? '') == $__check)
			{
				continue 2;
			}
		}

		foreach ($info['urls'] AS $url)
		{
			// keying by URL to dedupe between settings matches & helptext matches.
			$results[$url] = [
				'url' => $url,
				'title' => $info['title_phrase'],
				'description' => 'description'
			];
		}
	}
	return $results;
}

function fetch_xml_data()
{
	$navigation = [];

	$navfiles = vB_Api::instance('product')->loadProductCpnavFiles();
	if (empty($navfiles['vbulletin']))	// cpnav_vbulletin.xml is missing
	{
		$phrases = vB_Api::instance('phrase')->renderPhrases(['error' => ['could_not_open_x', DIR . '/includes/xml/cpnav_vbulletin.xml']]);
		print_stop_message_on_api_error($phrases);
		echo $phrases['phrases']['error'];
		exit;
	}

	foreach ($navfiles AS $nav_file => $file)
	{
		$xmlobj = new vB_XML_Parser(false, $file);
		$xml = $xmlobj->parse();

		$master = $xml['master'] ?? false;
		$navgroups = vB_XML_Parser::getList($xml, 'navgroup');
		unset($xml, $xmlobj);

		foreach ($navgroups AS $navgroup)
		{
			$navoptions = vB_XML_Parser::getList($navgroup, 'navoption');
			foreach ($navoptions AS $navoption)
			{
				$navoption['link'] = str_replace(
					[
						'{$vbulletin->config[Misc][modcpdir]}',
						'{$vbulletin->config[Misc][admincpdir]}'
					],
					['modcp', 'admincp'],
					$navoption['link']
				);
				$navoption['text'] = fetch_nav_text($navoption);

				if ($navoption['phrase'] AND (!isset($navigation[$navoption['phrase']]) OR $master))
				{
					$navigation[$navoption['phrase']] = [
						'text' => $navoption['text'],
						'link' => $navoption['link'],
					];
				}
			}
		}
	}

	return $navigation;
}

function fetch_nav_text($navoption)
{
	global $vbphrase;

	if (isset($navoption['phrase']) AND isset($vbphrase["$navoption[phrase]"]))
	{
		return $vbphrase["$navoption[phrase]"];
	}
	else if (isset($navoption['text']))
	{
		return $navoption['text'];
	}
	else
	{
		return '*[' . $navoption['phrase'] . ']*';
	}
}

function create_paging_urls(&$pagingInfo, $terms)
{
	if ($pagingInfo['prevurl'])
	{
		$pageNb = $pagingInfo['currentpage'] - 1;
		$pagingInfo['prevurl'] = 'search.php?do=dosearch&terms=' . $terms . '&page=' . $pageNb;
	}

	if ($pagingInfo['nexturl'])
	{
		$pageNb = $pagingInfo['currentpage'] + 1;
		$pagingInfo['nexturl'] = 'search.php?do=dosearch&terms=' . $terms . '&page=' . $pageNb;
	}
}

function admincp_search_result_paging_info($results, $currentPage, $perPage)
{
	$pageNavData = array();
	$totalCount = count($results);
	$perPage = (int) $perPage;
	$perPage = $perPage < 1 ? 1 : $perPage;

	$totalPages = ceil($totalCount / $perPage);
	if ($totalPages == 0)
	{
		$totalPages = 1;
	}

	$pageNum = (int) $currentPage;
	if ($pageNum < 1)
	{
		$pageNum = 1;
	}
	else if ($pageNum > $totalPages)
	{
		$pageNum = ($totalPages > 0) ? $totalPages : 1;
	}

	$prevUrl = $nextUrl = '';

	if ($pageNum > 1)
	{
		$prevUrl = 1;
	}

	if ($pageNum < $totalPages)
	{
		$nextUrl = 1;
	}

	$startCount = ($pageNum * $perPage) - $perPage + 1;
	$endCount = $pageNum * $perPage;
	if ($endCount > $totalCount)
	{
		$endCount = $totalCount;
	}

	$pageNavData = array(
		'startcount' => $startCount,
		'endcount' => $endCount,
		'totalcount' => $totalCount,
		'currentpage' => $pageNum,
		'prevurl' => $prevUrl,
		'nexturl' => $nextUrl,
		'totalpages' => $totalPages,
		'perpage' => $perPage,
	);

	return $pageNavData;
}


function admincp_search_convert_phrase_to_title($assertor, $results)
{
	if (!$results)
	{
		return array();
	}

	$title_phrases = array();
	foreach ($results as $result)
	{
		$title_phrases[] = $result['title'];
	}

	// query phrases
	$helpphrase = array();
	$phrases = $assertor->assertQuery('vBForum:phrase',
		array(
			vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'fieldname' => 'cphelptext',
			'languageid' => array(-1, 0, LANGUAGEID),
			'varname' => $title_phrases
		),
		array('field' => 'languageid', 'direction' => vB_dB_Query::SORT_ASC)
	);
	unset($title_phrases);

	if ($phrases AND (is_object($phrases) OR empty($phrases['errors'])) AND $phrases->valid())
	{
		$title_map = array();
		foreach($phrases AS $phrase)
		{
			$title_map["$phrase[varname]"] = vB_Library::instance('Phrase')->replaceOptionsAndConfigValuesInPhrase($phrase['text']);
		}
	}

	foreach ($results as $key => $value)
	{
		if (isset($title_map[$results[$key]['title']]))
		{
			$results[$key]['title'] = $title_map[$results[$key]['title']];
		}
	}

	return $results;
}

function admincp_search_no_results($terms, $minLen, $maxLen)
{
	global $vbphrase;

	print_table_start();
	print_table_header(construct_phrase($vbphrase['there_are_no_results_for_x'], htmlspecialchars($terms)), 2);
	$message = construct_phrase($vbphrase['search_terms_len_x_y'], $minLen, $maxLen);
	print_cells_row2([$message], false, 'vbleft');
	print_table_footer();
}

function admincp_search_display_paging($pagingInfo, $terms)
{
	global $vbphrase;

	if ($pagingInfo['totalpages'] > 1)
	{
		$pageoptions = array();
		for ($i = 1; $i <= $pagingInfo['totalpages']; $i++)
		{
			$pageoptions["$i"] = "$vbphrase[page_gcpglobal] $i / $pagingInfo[totalpages]";
		}
		$_HIDDENFIELDS = array('terms' => $terms);

		print_form_header('admincp/search', 'dosearch', false, true, 'navform', '90%', '', true, 'post');
		echo "<input type=\"hidden\" name=\"terms\" value=\"$terms\" />\n";
		echo '
		<colgroup span="5">
			<col width="90%" align="center"></col>
			<col width="10%" style="white-space:nowrap"></col>
			<col></col>
			<col></col>
		</colgroup>
		<tr>
			<td class="thead">' .
				'<input type="button"' . iif(!$pagingInfo['prevurl'], ' disabled="disabled"') . ' class="button" value="&laquo; ' . $vbphrase['prev'] . '" tabindex="1" onclick="this.form.page.selectedIndex -= 1; this.form.submit()" />' .
				'<select name="page" tabindex="1" onchange="this.form.submit()" class="bginput">' . construct_select_options($pageoptions, $pagingInfo['currentpage']) . '</select>' .
				'<input type="button"' . iif(!$pagingInfo['nexturl'], ' disabled="disabled"') . ' class="button" value="' . $vbphrase['next'] . ' &raquo;" tabindex="1" onclick="this.form.page.selectedIndex += 1; this.form.submit()" />
			</td>
			<td class="thead">' . $vbphrase['per_page'] . ':</td>
			<td class="thead"><input type="text" class="bginput" name="perpage" value="' . $pagingInfo['perpage'] . '" tabindex="1" size="5" /></td>
			<td class="thead"><input type="submit" class="button" value=" ' . $vbphrase['go'] . ' " tabindex="1" accesskey="s" /></td>
		</tr>';
		print_table_footer();
	}
}

function admincp_search_displaysearch_results($results, $pagingInfo, $terms)
{
	global $vbphrase;

	print_table_start();
	print_table_header(
		construct_phrase($vbphrase['showing_x_to_y_of_z_results_for_t'], $pagingInfo['startcount'], $pagingInfo['endcount'], $pagingInfo['totalcount'], $terms),
		2
	);

	foreach($results AS $result)
	{
		print_cells_row([
			'<a href="' . $result['url'] . '">' . $result['title'] . '</a>'
		]);
	}

	print_table_footer();
}


/*
 *	Get a list of info arrays in the same order as the help_matches.
 *
 *	the info is
 *	[
 *		'urls' => A list of urls for this item (if there is more than one action for a help item it can generate multiple urls)
 *		'key' => A unique key for this item (we use this to remove duplicates -- the first item is the one used).
 *		'title_phrase' => The phrase that should be used to display the title.
 *	]
 */
function admincp_search_help_entry_to_info_array($assertor, $help_matches, &$mapResult)
{
	if (!$help_matches)
	{
		return [];
	}

	$scripts = [];
	foreach (array_keys($help_matches) AS $varname)
	{
		$scripts[help_phraseid_to_script($varname)] = 1;
	}

	//the phrase ids are ambiguous: a_b_c could be script a, actions 'b,c or script 1, action b, option c.
	//we need to grab the db records matching the phrase ids to disambiguate.
	$helptext = $assertor->assertQuery('vBForum:adminhelp', ['script' => array_keys($scripts)]);

	$actions = [];
	foreach($helptext AS $row)
	{
		$varname = fetch_help_phrase_short_name($row);
		//get the varname for the help record.
		if (isset($help_matches[$varname]))
		{
			$row['map_key'] = $help_matches[$varname];
			$help_matches[$varname] = $row;
		}
	}
	$helptext->free();

	//Create the option group map
	$options = [];
	foreach($help_matches AS $key => $row)
	{
		if (!isset($row['script']))
		{
			/*
				It seems this can rarely happen when translations aren't caught up.
				For example, if a help text's "action" is changed from "edit" to "add,edit",
				an older translation might still have something like {script}_edit_{option}
				instead of {script}_add_edit_{option}, and we don't have a partial matching
				for `adminhelp` records. For now, let's just skip this explicitly (currently
				just emits notices)
			*/
			unset($help_matches[$key]);
			continue;
		}

		if ($row['script'] == 'options')
		{
			$options[] = $row['optionname'];
		}
	}

	if (!empty($options))
	{
		$option_group_map = $assertor->getColumn('setting', 'grouptitle', ['varname' => $options], false, 'varname');
	}

	foreach ($help_matches AS $key => $row)
	{
		if ($row['script'] == 'options')
		{
			$group = $option_group_map[$row['optionname']] ?? false;
			if ($group)
			{
				$help_matches[$key] = admincp_search_help_text_option_to_info($group, $row);
			}
			else
			{
				unset($help_matches[$key]);
			}
		}
		else
		{
			$help_matches[$key] = admincp_search_help_text_to_info($row);
		}
	}

	unset($option_group_map);

	//collapse the results by script/action ignoring options.  We need to do it this way to preserve order.
	$unique_help_matches = [];
	foreach($help_matches AS $info)
	{
		$unique_help_matches[$info['key']] = $info;
		if (!in_array($info['key'], $mapResult))
		{
			$mapResult[$info['map_key']] = $info['key'];
		}
	}
	unset($help_matches);

	return $unique_help_matches;
}


/*
 *	Figure out the script name from the help phrase name.
 */
function help_phraseid_to_script($phraseid)
{
	$sections = explode('_', $phraseid);
	return $sections[0];
}


/*
 *	Convert an option to the search result array.  We handle options seperately because the data in
 *	the help text entry doesn't work well with them.
 */
function admincp_search_help_text_option_to_info($group, $row)
{
	$info = [
		'urls' => ['admincp/options.php?do=options&dogroup=' . $group . '#' . $row['optionname']],
		//'option' => $row['optionname'],
		'key' => $row['script'] . '_' . $row['optionname'],
		'title_phrase' => fetch_help_phrase_short_name($row) . '_title',
		'map_key' => $row['map_key'],
		'optiongroup' => $group,
	];

	return $info;
}


/*
 *	Convert the standard help text to a search result array
 */
function admincp_search_help_text_to_info($row)
{
	$actions = explode(',', $row['action']);
	$urls = [];
	foreach($actions AS $action)
	{
		//edit actions don't really work as links because they generally require some data.
		if (!in_array($action, ['edit', 'doedit']))
		{
			$anchor = '';
			if ($row['optionname'])
			{
				$anchor = '#' . $row['script'] . '_' . $action . '_' . $row['optionname'];
			}

			$urls[$action] = 'admincp/' . $row['script'] . '.php?do=' . $action . $anchor;
		}
	}

	$info = [
		'urls' => $urls,
		//'option' => $row['optionname'],
		'key' => $row['script'] . '_' . $row['action'],
		'title_phrase' => fetch_help_phrase_short_name($row) . '_title',
		'map_key' => $row['map_key'],
	];

	return $info;
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 115851 $
|| #######################################################################
\*=========================================================================*/
