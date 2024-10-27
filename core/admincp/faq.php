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
define('CVS_REVISION', '$RCSfile$ - $Revision: 112676 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
global $phrasegroups, $specialtemplates, $ifaqcache, $faqcache, $faqjumpbits, $faqparent, $vbphrase, $vbulletin, $bgcounter;

$phrasegroups = ['cphome', 'help_faq', 'fronthelp', 'language'];
$specialtemplates = [];

// ########################## REQUIRE BACK-END ############################
require_once(dirname(__FILE__) . '/global.php');
require_once(DIR . '/includes/functions_faq.php');

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!can_administer('canadminfaq'))
{
	print_cp_no_permission();
}

// ############################# LOG ACTION ###############################
log_admin_action();

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

$vb5_config =& vB::getConfig();
$assertor = vB::getDbAssertor();

print_cp_header($vbphrase['faq_manager_ghelp_faq']);

// #############################################################################

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'modify';
}

// #############################################################################

if ($_POST['do'] == 'kill')
{
	$vbulletin->input->clean_array_gpc('p', [
		'faqname' => vB_Cleaner::TYPE_STR
	]);

	// get list of items to delete
	$faqDeleteNames = fetch_faq_delete_list($vbulletin->GPC['faqname']);
	$phraseDeleteNamesSql = [];
	foreach ($faqDeleteNames as $name)
	{
		$phraseDeleteNamesSql[] = $name . '_gfaqtitle';
		$phraseDeleteNamesSql[] = $name . '_gfaqtext';
	}

	// delete faq
	$res = $assertor->assertQuery('vBForum:faq', [vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_DELETE, 'faqname' => $faqDeleteNames]);

	if (defined('DEV_AUTOEXPORT') AND DEV_AUTOEXPORT)
	{
		// get phrases to delete
		if (!empty($phraseDeleteNamesSql))
		{
			$set = $assertor->assertQuery('vBForum:getDistinctProduct', ['phraseDeleteNamesSql' => $phraseDeleteNamesSql]);
			$products_to_export = [];
			foreach ($set AS $row)
			{
				$products_to_export[$row['product']] = 1;
			}
		}
	}

	// delete phrases
	$res = $assertor->assertQuery('vBForum:phrase', [
		vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_DELETE,
		'varname' => $phraseDeleteNamesSql,
		'fieldname'=> ['faqtitle', 'faqtext'],
	]);

	if (defined('DEV_AUTOEXPORT') AND DEV_AUTOEXPORT)
	{
		require_once(DIR . '/includes/functions_filesystemxml.php');
		foreach (array_keys($products_to_export) as $product)
		{
			autoexport_write_faq_and_language(-1, $product);
		}
	}
	vB_Cache::instance()->event('vB_FAQ_chg');
	print_stop_message2('deleted_faq_item_successfully', 'faq', ['faq' => $faqcache[$vbulletin->GPC['faqname']]['faqparent']]);
}

// #############################################################################

if ($_REQUEST['do'] == 'delete')
{
	$vbulletin->input->clean_array_gpc('r', [
		'faq' => vB_Cleaner::TYPE_STR
	]);

	print_delete_confirmation('faq', $vbulletin->db->escape_string($vbulletin->GPC['faq']), 'faq', 'kill', 'faq_item', '', $vbphrase['please_note_deleting_this_item_will_remove_children']);
}

// #############################################################################

if ($_POST['do'] == 'update')
{
	$vbulletin->input->clean_array_gpc('p', [
		'faq' 			=> vB_Cleaner::TYPE_STR,
		'faqparent' => vB_Cleaner::TYPE_STR,
		'deftitle'	=> vB_Cleaner::TYPE_NOHTML,
		'deftext'		=> vB_Cleaner::TYPE_STR,
		'text'		  => vB_Cleaner::TYPE_ARRAY_STR,	// Originally NULL though not type checking incode, used as an array
	]);

	if ($vbulletin->GPC['deftitle'] == '')
	{
		print_stop_message2('invalid_title_specified');
	}

	if (!preg_match('#^[a-z0-9_]+$#i', $vbulletin->GPC['faq']))
	{
		print_stop_message2('invalid_faq_varname');
	}

	if (!validate_string_for_interpolation($vbulletin->GPC['deftext']))
	{
		print_stop_message2('faq_text_not_safe');
	}

	foreach ($vbulletin->GPC['text'] AS $text)
	{
		if (!validate_string_for_interpolation($text))
		{
			print_stop_message2('faq_text_not_safe');
		}
	}

	if ($vbulletin->GPC['faqparent'] == $vbulletin->GPC['faq'])
	{
		print_stop_message2('cant_parent_faq_item_to_self');
	}
	else
	{
		$faqarray = [];
		$getfaqs = $assertor->assertQuery('vBForum:faq', []);
		if ($getfaqs AND $getfaqs->valid())
		{
			foreach ($getfaqs AS $current)
			{
				$faqarray["$current[faqname]"] = $current['faqparent'];
			}
		}

		$parent_item = $vbulletin->GPC['faqparent'];
		$i = 0;
		// Traverses up the parent list to check we're not moving an faq item to something already below it
		while ($parent_item != 'faqroot' AND $parent_item != '' AND $i++ < 100)
		{
			$parent_item = $faqarray["$parent_item"];
			if ($parent_item == $vbulletin->GPC['faq'])
			{
				print_stop_message2('cant_parent_faq_item_to_child');
			}
		}
	}

	$conditions = [];
	$conditions[] = ['field' => 'varname', 'value' => [$vbulletin->GPC['faq'] . '_gfaqtitle', $vbulletin->GPC['faq'] . '_gfaqtext'], 'operator' => vB_dB_Query::OPERATOR_EQ];
	if (!$vb5_config['Misc']['debug']){
		$conditions[] = ['field' => 'languageid','value' => -1, 'operator' => vB_dB_Query::OPERATOR_NE];
	}
	$res = $assertor->assertQuery('vBForum:phrase', [vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_DELETE, vB_dB_Query::CONDITIONS_KEY => $conditions]);

	if (defined('DEV_AUTOEXPORT') AND DEV_AUTOEXPORT)
	{
		$old_products = $assertor->assertQuery('vBForum:faq',	['faqname' => $vbulletin->GPC['faq']]);
		if ($old_products AND $old_products->valid())
		{
			foreach ($old_products AS $current)
			{
				$old_product[] = $current['product'];
			}
		}
	}

	$res = $assertor->assertQuery('vBForum:faq', [vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_DELETE, 'faqname' => $vbulletin->GPC['faq']]);
	vB_Cache::instance()->event('vB_FAQ_chg');
	$_POST['do'] = 'insert';
}

// #############################################################################

if ($_POST['do'] == 'insert')
{
	$vars = [
		'faq'			=> vB_Cleaner::TYPE_STR,
		'faqparent'		=> vB_Cleaner::TYPE_STR,
		'volatile'		=> vB_Cleaner::TYPE_INT,
		'product'		=> vB_Cleaner::TYPE_STR,
		'displayorder'		=> vB_Cleaner::TYPE_INT,
		'title'			=> vB_Cleaner::TYPE_ARRAY_STR,	// Originally NULL though not type checking incode, used as an array
		'text'			=> vB_Cleaner::TYPE_ARRAY_STR,	// Originally NULL though not type checking incode, used as an array
		'deftitle'		=> vB_Cleaner::TYPE_NOHTML,
		'deftext'		=> vB_Cleaner::TYPE_STR
	];

	$vbulletin->input->clean_array_gpc('r', $vars);

	if ($vbulletin->GPC['deftitle'] == '')
	{
		print_stop_message2('invalid_title_specified');
	}

	if (!preg_match('#^[a-z0-9_]+$#i', $vbulletin->GPC['faq']))
	{
		print_stop_message2('invalid_faq_varname');
	}


	if (!validate_string_for_interpolation($vbulletin->GPC['deftext']))
	{
		print_stop_message2('faq_text_not_safe');
	}

	foreach ($vbulletin->GPC['text'] AS $text)
	{
		if (!validate_string_for_interpolation($text))
		{
			print_stop_message2('faq_text_not_safe');
		}
	}

	// ensure that the faq name is in 'word_word_word' format
	$fixedfaq = strtolower(preg_replace('#\s+#s', '_', $vbulletin->GPC['faq']));
	if ($fixedfaq !== $vbulletin->GPC['faq'])
	{
		print_form_header('admincp/faq', 'insert');
		print_table_header($vbphrase['faq_link_name_changed']);
		print_description_row(construct_phrase($vbphrase['to_maintain_compatibility_with_the_system_name_changed'], $vbulletin->GPC['faq'], $fixedfaq));
		print_input_row($vbphrase['varname'], 'faq', $fixedfaq);

		$vbulletin->GPC['faq'] = $fixedfaq;

		foreach (array_keys($vars) AS $varname_outer)
		{
			$var &= $vbulletin->GPC[$varname_outer];
			if (is_array($var))
			{
				foreach ($var AS $varname_inner => $value)
				{
					construct_hidden_code($varname_outer . "[$varname_inner]", $value);
				}
			}
			else if ($vbulletin->GPC['varname'] != 'faq')
			{
				construct_hidden_code($varname_outer, $var);
			}
		}

		print_submit_row($vbphrase['continue'], 0, 2, $vbphrase['go_back']);

		print_cp_footer();
		exit;
	}

	$check = $assertor->assertQuery('vBForum:faq', ['faqname' => $vbulletin->GPC['faq']]);
	if ($check AND $check->valid())
	{
		$current = $check->current();
		print_stop_message2(['there_is_already_faq_item_named_x', $current['faqname']]);
	}

	$conditions = [[
		'field' => 'varname',
		'value' => [$vbulletin->GPC['faq'] . '_gfaqtitle', $vbulletin->GPC['faq'] . '_gfaqtext'],
		'operator' => vB_dB_Query::OPERATOR_EQ
	]];

	if (!$vb5_config['Misc']['debug'])
	{
		$conditions[] = ['field' => 'languageid','value' => -1, 'operator'=> vB_dB_Query::OPERATOR_NE];
	}

	$check = $assertor->assertQuery('vBForum:phrase',	[vB_dB_Query::CONDITIONS_KEY => $conditions]
	);

	if ($check AND $check->valid())
	{
		$current = $check->current();
		$varname = $current['varname'];
		print_stop_message2(['there_is_already_faq_item_named_x', $varname]);
	}

	$faqname = $vbulletin->db->escape_string($vbulletin->GPC['faq']);

	// set base language versions
	$baselang = ($vbulletin->GPC['volatile'] ? -1 : 0);

	if ($baselang != -1 OR $vb5_config['Misc']['debug'])
	{
		// can't edit a master version if not in debug mode
		$vbulletin->GPC['title']["$baselang"] =& $vbulletin->GPC['deftitle'];
		$vbulletin->GPC['text']["$baselang"] =& $vbulletin->GPC['deftext'];
	}

	$full_product_info = fetch_product_list(true);
	$product_version = $full_product_info[$vbulletin->GPC['product']]['version'];

	$insertSql = [];

	foreach (array_keys($vbulletin->GPC['title']) AS $languageid)
	{
		$newtitle = trim($vbulletin->GPC['title']["$languageid"]);
		$newtext = trim($vbulletin->GPC['text']["$languageid"]);

		if ($newtitle OR $newtext)
		{
			$assertor->assertQuery('vBForum:phrase',
				[
					vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_MULTIPLEINSERT,
					vB_dB_Query::FIELDS_KEY => ['languageid', 'varname', 'text', 'fieldname', 'product', 'username', 'dateline', 'version'],
					vB_dB_Query::VALUES_KEY => [
						[$languageid, $faqname . "_gfaqtitle", $newtitle, 'faqtitle', $vbulletin->GPC['product'], $vbulletin->userinfo['username'], vB::getRequest()->getTimeNow(), $product_version],
						[$languageid, $faqname . "_gfaqtext", $newtext, 'faqtext', $vbulletin->GPC['product'], $vbulletin->userinfo['username'], vB::getRequest()->getTimeNow(), $product_version]
					]
				]
			);
		}
	}

	/*insert query*/
	$set = $assertor->assertQuery('vBForum:replaceIntoFaq',
			[
				'faqname' => $faqname,
				'faqparent' => $vbulletin->GPC['faqparent'],
				'displayorder' => $vbulletin->GPC['displayorder'],
				'volatile' => $vbulletin->GPC['volatile'],
				'product' => $vbulletin->GPC['product']
			]
	);

	if (defined('DEV_AUTOEXPORT') AND DEV_AUTOEXPORT)
	{
		require_once(DIR . '/includes/functions_filesystemxml.php');
		$products_to_export = [$vbulletin->GPC['product']];
		if (isset($old_product['product']))
		{
			$products_to_export[] = $old_product['product'];
		}
		autoexport_write_faq_and_language($baselang, $products_to_export);
	}
	vB_Cache::instance()->event('vB_FAQ_chg');
	print_stop_message2(['saved_faq_x_successfully', $vbulletin->GPC['deftitle']],'faq', ['faq' => $vbulletin->GPC['faqparent']]);
}

// #############################################################################

if ($_REQUEST['do'] == 'edit' OR $_REQUEST['do'] == 'add')
{
	require_once(DIR . '/includes/adminfunctions_language.php');

	$faqphrase = [];

	if ($_REQUEST['do'] == 'edit')
	{
		$vbulletin->input->clean_array_gpc('r', [
			'faq' => vB_Cleaner::TYPE_STR
		]);

		$faq = $assertor->getRow('vBForum:faq', ['faqname' => $vbulletin->GPC['faq']]);
		if (!$faq)
		{
			print_stop_message2('no_matches_found_gerror');
		}

		$phrases = $assertor->assertQuery('vBForum:phrase', [
			vB_dB_Query::CONDITIONS_KEY => [
				['field' => 'varname','value' => $vbulletin->GPC['faq'], 'operator' => vB_dB_Query::OPERATOR_BEGINS]
			]
		]);

		foreach ($phrases AS $phrase)
		{
			$type = ($phrase['fieldname'] == 'faqtitle' ? 'title' : 'text');
			$faqphrase[$phrase['languageid']][$type] = $phrase['text'];
		}

		print_form_header('admincp/faq', 'update');
		construct_hidden_code('faq', $faq['faqname']);
		print_table_header(construct_phrase($vbphrase['x_y_id_z'], $vbphrase['faq_item'], $faqphrase['-1']['title'], $faq['faqname']));
		print_label_row($vbphrase['varname'], $faq['faqname']);
	}
	else
	{
		$vbulletin->input->clean_array_gpc('r', [
			'faq' => vB_Cleaner::TYPE_STR
		]);

		$faq = [
			'faqname' => '',
			'faqparent' => ($vbulletin->GPC['faq'] ? $vbulletin->GPC['faq'] : 'faqroot'),
			'displayorder' => 1,
			'volatile' => ($vb5_config['Misc']['debug'] ? 1 : 0),
			'product' => 'vbulletin',
		];

		?>
		<script type="text/javascript">
		<!--
		function js_check_shortname(theform)
		{
			theform.faq.value = theform.faq.value.toLowerCase();

			for (i = 0; i < theform.faqparent.options.length; i++)
			{
				if (theform.faq.value == theform.faqparent.options[i].value)
				{
					alert(" <?php echo $vbphrase['sorry_there_is_already_an_item_called']; ?> '" + theform.faq.value + "'");
					return false;
				}
			}
			return true;
		}
		//-->
		</script>
		<?php

		print_form_header('admincp/faq', 'insert', 0, 1, 'cpform" onsubmit="return js_check_shortname(this);');
		print_table_header($vbphrase['add_new_faq_item_ghelp_faq']);
		print_input_row($vbphrase['varname'], 'faq', '', 0, '35" onblur="js_check_shortname(this.form);');
	}

	//magically set up global variable that most of the fetch_faq_* functions rely on
	cache_ordered_faq();

	$parentoptions = ['faqroot' => $vbphrase['no_parent_faq_item']];
	fetch_faq_parent_options($parentoptions, $faq['faqname']);
	print_select_row($vbphrase['parent_faq_item'], 'faqparent', $parentoptions, $faq['faqparent']);

	if (isset($faqphrase['-1']) AND is_array($faqphrase['-1']))
	{
		$defaultlang = -1;
	}
	else
	{
		$defaultlang = 0;
	}

	$title = $faqphrase[$defaultlang]['title'] ?? '';
	$text = $faqphrase[$defaultlang]['text'] ?? '';

	if ($vb5_config['Misc']['debug'] OR $defaultlang == 0)
	{
		print_input_row($vbphrase['title'], 'deftitle', $title, false, '70" style="width:100%');
		print_textarea_row($vbphrase['text_gcpglobal'], 'deftext', $text, 10, '70" style="width:100%');
	}
	else
	{
		construct_hidden_code('deftitle', $title, 1, 69);
		construct_hidden_code('deftext', $text, 10, 70);
		print_label_row($vbphrase['title'], $title);
		print_label_row($vbphrase['text_gcpglobal'], nl2br(htmlspecialchars($text)));
	}

	print_input_row($vbphrase['display_order'], 'displayorder', $faq['displayorder']);

	if ($vb5_config['Misc']['debug'])
	{
		print_yes_no_row($vbphrase['vbulletin_default'], 'volatile', $faq['volatile']);
	}
	else
	{
		construct_hidden_code('volatile', $faq['volatile']);
	}

	print_select_row($vbphrase['product'], 'product', fetch_product_list(), $faq['product']);

	// do translation boxes
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
	foreach ($languages AS $lang)
	{
		$languageid = $lang['languageid'];
		$translation =  '<span style="white-space:nowrap">' . construct_phrase($vbphrase['x_translation'], "<b>{$lang['title']}</b>") . '</span>';

		$faqtitle = $faqphrase[$languageid]['title'] ?? '';
		$faqttext = $faqphrase[$languageid]['text'] ?? '';

		print_input_row($translation . '<dfn>(' . $vbphrase['title'] . ')</dfn>', "title[$languageid]", $faqtitle, 1, 69, 0, $lang['direction']);
		//keep the backgrounds the same color.
		$GLOBALS['bgcounter']--;

		$fun = htmlspecialchars('copy_default_text("' . $lang['languageid'] . '")');
		$button = '<input type="button" class="button" value="' . $vbphrase['copy_default_text'] . '" tabindex="1" onclick="' . $fun . '" />';

		print_label_row(
			$translation . '<dfn>(' . $vbphrase['text_gcpglobal'] . ')</dfn>' . $button,
			"<textarea name=\"text[$languageid]\" id=\"text_$languageid\" rows=\"4\" cols=\"70\" tabindex=\"1\" wrap=\"virtual\" dir=\"{$lang['direction']}\">$faqttext</textarea>"
		);

		print_description_row('<img src="images/clear.gif" width="1" height="1" alt="" />', 0, 2, 'thead');
	}

	print_submit_row($vbphrase['save']);
}

// #############################################################################

if ($_POST['do'] == 'updateorder')
{
	$vbulletin->input->clean_array_gpc('p', [
		'order' 	=> vB_Cleaner::TYPE_NOCLEAN,
		'faqparent'	=> vB_Cleaner::TYPE_STR
	]);

	if (empty($vbulletin->GPC['order']) OR !is_array($vbulletin->GPC['order']))
	{
		print_stop_message2('invalid_array_specified');
	}

	$faqnames = [];
	$faqnamesNONEscaped = [];

	foreach ($vbulletin->GPC['order'] AS $faqname => $displayorder)
	{
		$vbulletin->GPC['order']["$faqname"] = intval($displayorder);
		$faqnames[] = "'" . $vbulletin->db->escape_string($faqname) . "'";
		$faqnamesNONEscaped[] = $faqname;
	}

	$faqs = $assertor->assertQuery('vBForum:faq', ['faqname' => $faqnamesNONEscaped]);
	if ($faqs AND $faqs->valid())
	{
		foreach ($faqs AS $faq)
		{
			if ($faq['displayorder'] != $vbulletin->GPC['order']["$faq[faqname]"])
			{
				$response = $assertor->assertQuery('vBForum:faq',
					[
						vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
						'displayorder' => $vbulletin->GPC['order']["$faq[faqname]"],
						vB_dB_Query::CONDITIONS_KEY => [
							['field'=>'faqname','value'=>$faq['faqname'], 'operator'=> vB_dB_Query::OPERATOR_EQ]
						]
					]
				);
			}
		}
	}

	if (defined('DEV_AUTOEXPORT') AND DEV_AUTOEXPORT)
	{
		require_once(DIR . '/includes/functions_filesystemxml.php');
		$products = $assertor->assertQuery('vBForum:getDistinctProductFAQ', ['faqnames'=>implode(', ', $faqnames)]);

		if ( $products AND $products->valid() )
		{
			foreach ($products AS $product)
			{
				autoexport_write_faq($product['product']);
			}
		}
	}
	vB_Cache::instance()->event('vB_FAQ_chg');
	print_stop_message2('saved_display_order_successfully','faq', ['faq' => $vbulletin->GPC['faqparent']]);
}

// #############################################################################

if ($_REQUEST['do'] == 'modify')
{
	$vbulletin->input->clean_array_gpc('r', [
		'faq' 	=> vB_Cleaner::TYPE_STR
	]);

	$faqparent = (empty($vbulletin->GPC['faq']) ? 'faqroot': $vbulletin->GPC['faq']);
	cache_ordered_faq();

	//we can only land on faq entry that has children.  If this is a valid faq entry but doesn't
	//have children than bump up the the entry's parent.
	if (!isset($ifaqcache[$faqparent]))
	{
		$faqparent = $faqcache[$faqparent]['faqparent'];
		if (!isset($ifaqcache[$faqparent]))
		{
			print_stop_message2('invalid_faq_item_specified');
		}
	}

	global $parents;
	$parents = [];
	fetch_faq_parents($faqcache[$faqparent]['faqname'] ?? '');
	$parents = array_reverse($parents);
	$nav = "<a href=\"admincp/faq.php\">$vbphrase[faq]</a>";
	if (!empty($parents))
	{
		$i = 1;
		foreach ($parents AS $link => $name)
		{
			$nav .= '<br />' . str_repeat('&nbsp; &nbsp; ', $i) . (empty($link) ? $name : "<a href=\"admincp/$link\">$name</a>");
			$i ++;
		}
		$nav .= '
			<span class="smallfont">' .
			construct_link_code($vbphrase['edit'], "faq.php?do=edit&amp;faq=" . urlencode($faqparent)) .
			construct_link_code($vbphrase['add_child_faq_item'], "faq.php?do=add&amp;faq=" . urlencode($faqparent)) .
			construct_link_code($vbphrase['delete'], "faq.php?do=delete&amp;faq=" . urlencode($faqparent)) .
			'</span>';
	}

	print_form_header('admincp/faq', 'updateorder');
	construct_hidden_code('faqparent', $faqparent);
	print_table_header($vbphrase['faq_manager_ghelp_faq'], 3);
	print_description_row("<b>$nav</b>", 0, 3);
	print_cells_row([$vbphrase['title'], $vbphrase['display_order'], $vbphrase['controls']], 1);

	foreach ($ifaqcache["$faqparent"] AS $faq)
	{
		print_faq_admin_row($faq);
		if (isset($ifaqcache[$faq['faqname']]) AND is_array($ifaqcache[$faq['faqname']]))
		{
			foreach ($ifaqcache["$faq[faqname]"] AS $subfaq)
			{
				print_faq_admin_row($subfaq, '&nbsp; &nbsp; &nbsp;');
			}
		}
	}

	print_submit_row($vbphrase['save_display_order'], false, 3);
}

print_cp_footer();

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 112676 $
|| #######################################################################
\*=========================================================================*/
