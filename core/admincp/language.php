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
define('DEFAULT_FILENAME', 'vbulletin-language.xml');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
global $phrasegroups, $specialtemplates, $vbphrase, $vbulletin;
$phrasegroups = ['language'];
$specialtemplates = [];

// ########################## REQUIRE BACK-END ############################
require_once(dirname(__FILE__) . '/global.php');
require_once(DIR . '/includes/adminfunctions_language.php');
$assertor = vB::getDbAssertor();

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!can_administer('canadminlanguages'))
{
	print_cp_no_permission();
}

$vbulletin->input->clean_array_gpc('r', [
	'dolanguageid' => vB_Cleaner::TYPE_INT
]);

// ############################# LOG ACTION ###############################
$message = '';
if (!empty($vbulletin->GPC['dolanguageid']))
{
	$message = "Language ID = " . $vbulletin->GPC['dolanguageid'];
}
log_admin_action($message);

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

$vb5_config =& vB::getConfig();

vB_Utilities::extendMemoryLimit();

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'modify';
}

$langglobals = [
	'title'                    => vB_Cleaner::TYPE_NOHTML,
	'userselect'               => vB_Cleaner::TYPE_INT,
	'options'                  => vB_Cleaner::TYPE_ARRAY_BOOL,
	'languagecode'             => vB_Cleaner::TYPE_STR,
	'charset'                  => vB_Cleaner::TYPE_STR,
	'locale'                   => vB_Cleaner::TYPE_STR,
	'dateoverride'             => vB_Cleaner::TYPE_STR,
	'timeoverride'             => vB_Cleaner::TYPE_STR,
	'registereddateoverride'   => vB_Cleaner::TYPE_STR,
	'calformat1override'       => vB_Cleaner::TYPE_STR,
	'calformat2override'       => vB_Cleaner::TYPE_STR,
	'eventdateformatoverride'  => vB_Cleaner::TYPE_STR,
	'pickerdateformatoverride' => vB_Cleaner::TYPE_STR,
	'logdateoverride'          => vB_Cleaner::TYPE_STR,
	'decimalsep'               => vB_Cleaner::TYPE_STR,
	'thousandsep'              => vB_Cleaner::TYPE_STR,
];
if (!empty($vb5_config['Misc']['debug']))
{
	$langglobals['vblangcode'] = vB_Cleaner::TYPE_STR;
	$langglobals['revision'] = vB_Cleaner::TYPE_UINT;
}

// #############################################################################

if ($_POST['do'] == 'download')
{
	$vbulletin->input->clean_array_gpc('p', [
		'filename'     => vB_Cleaner::TYPE_STR,
		'just_phrases' => vB_Cleaner::TYPE_BOOL,
		'product'      => vB_Cleaner::TYPE_STR,
		'custom'       => vB_Cleaner::TYPE_BOOL,
		'charset'      => vB_Cleaner::TYPE_NOHTML,
	]);

	if (empty($vbulletin->GPC['filename']))
	{
		$vbulletin->GPC['filename'] = DEFAULT_FILENAME;
	}

	vB_Utility_Functions::setPhpTimeout(1200);
	try
	{
		$doc = vB_Api::instanceInternal('language')->export(
			$vbulletin->GPC['dolanguageid'],
			$vbulletin->GPC['product'],
			$vbulletin->GPC['just_phrases'],
			$vbulletin->GPC['custom'],
			$vbulletin->GPC['charset'] ? $vbulletin->GPC['charset'] : 'ISO-8859-1'
		);
	}
	catch (vB_Exception_AdminStopMessage $e)
	{
		print_stop_message2($e->getParams());
	}

	require_once(DIR . '/includes/functions_file.php');
	file_download($doc, $vbulletin->GPC['filename'], 'text/xml');
}

// ##########################################################################

print_cp_header($vbphrase['language_manager_glanguage']);

// #############################################################################
// #############################################################################

if ($_POST['do'] == 'update')
{
	$vbulletin->input->clean_array_gpc('p', [
		'fieldname'  => vB_Cleaner::TYPE_NOHTML,
		'pagenumber' => vB_Cleaner::TYPE_UINT,
		'def'        => vB_Cleaner::TYPE_ARRAY_STR,  // default text values array (hidden fields)
		'phr'        => vB_Cleaner::TYPE_ARRAY_STR,  // changed text values array (textarea fields)
		'rvt'        => vB_Cleaner::TYPE_ARRAY_UINT, // revert phrases array (checkbox fields)
		'prod'       => vB_Cleaner::TYPE_ARRAY_STR,  // products the phrases as associated with
	]);

	if (empty($vbulletin->GPC['product']))
	{
		$vbulletin->GPC['product'] = 'vbulletin';
	}

	$updatelanguage = false;

	if (!empty($vbulletin->GPC['rvt']))
	{
		$updatelanguage = true;

		$assertor->delete('vBForum:phrase', ['phraseid' => array_values($vbulletin->GPC['rvt'])]);


		// unset reverted phrases
		foreach (array_keys($vbulletin->GPC['rvt']) AS $varname)
		{
			unset($vbulletin->GPC['def']["$varname"]);
		}
	}

	$sql = [];
	$full_product_info = fetch_product_list(true);

	try
	{
		$userInfo = vB_Api::instanceInternal('user')->fetchUserinfo();
	}
	catch (vB_Exception_Api $ex)
	{
		print_stop_message2($ex->getMessage(), 'language.php', ['do' => 'update']);
	}

	foreach (array_keys($vbulletin->GPC['def']) AS $varname)
	{
		$defphrase =& $vbulletin->GPC['def']["$varname"];
		$newphrase =& $vbulletin->GPC['phr']["$varname"];
		$product   =& $vbulletin->GPC['prod']["$varname"];
		$product_version = $full_product_info["$product"]['version'];

		if ($newphrase != $defphrase)
		{
			$sql[] = [
				'languageid' => $vbulletin->GPC['dolanguageid'],
				'fieldname' => $vbulletin->GPC['fieldname'],
				'varname' => $varname,
				'newphrase' => $newphrase,
				'product' => $product,
				'username' => $userInfo['username'],
				'dateline' => vB::getRequest()->getTimeNow(),
				'version' => $product_version
			];
		}
	}

	if (!empty($sql))
	{
		$updatelanguage = true;
		$assertor->assertQuery('vBForum:updatePhrasesFromLanguage', ['phraserecords' => $sql]);
	}

	if ($updatelanguage)
	{
		build_language($vbulletin->GPC['dolanguageid']);
	}

	if (defined('DEV_AUTOEXPORT') AND DEV_AUTOEXPORT)
	{
		//figure out the products of the phrases processed.
		$products = [];
		foreach (array_keys($vbulletin->GPC['rvt']) AS $varname)
		{
			$products[$vbulletin->GPC['prod']["$varname"]] = 1;
		}

		foreach (array_keys($vbulletin->GPC['def']) AS $varname)
		{
			if ($vbulletin->GPC['def']["$varname"] != $vbulletin->GPC['phr']["$varname"])
			{
				$products[$vbulletin->GPC['prod']["$varname"]] = 1;
			}
		}
		$products = array_keys($products);

		//export those products;
		require_once(DIR . '/includes/functions_filesystemxml.php');
		foreach ($products as $product)
		{
			autoexport_write_language($vbulletin->GPC['dolanguageid'], $product);
		}
	}

	print_stop_message2('saved_language_successfully', 'language', [
		'do' => 'edit',
		'dolanguageid' => $vbulletin->GPC['dolanguageid'],
		'fieldname' => $vbulletin->GPC['fieldname'],
		'page' => $vbulletin->GPC['pagenumber']
	]);
}

// #############################################################################
// #############################################################################

// ##########################################################################

if ($_POST['do'] == 'upload')
{
	ignore_user_abort(true);

	$vbulletin->input->clean_array_gpc('p', [
		'title'        => vB_Cleaner::TYPE_STR,
		'serverfile'   => vB_Cleaner::TYPE_STR,
		'anyversion'   => vB_Cleaner::TYPE_BOOL,
	]);

	$vbulletin->input->clean_array_gpc('f', [
		'languagefile' => vB_Cleaner::TYPE_FILE
	]);
	scanVbulletinGPCFile('languagefile');


	//error 4 is no file found.  In which case we want to move forward so that we can check
	//the server file case.
	if ($vbulletin->GPC['languagefile']['error'] > 0 AND $vbulletin->GPC['languagefile']['error'] != UPLOAD_ERR_NO_FILE)
	{
		if (!function_exists('get_error_phrase_from_upload_file'))
		{
			require_once(DIR . '/includes/functions_file.php');
		}

		$phrase = get_error_phrase_from_upload_file($vbulletin->GPC['languagefile']['error']);
		print_stop_message2($phrase);
	}

	// got an uploaded file?
	// do not use file_exists here, under IIS it will return false in some cases
	if (is_uploaded_file($vbulletin->GPC['languagefile']['tmp_name']))
	{
		$xml = file_read($vbulletin->GPC['languagefile']['tmp_name']);
	}
	// no uploaded file - got a local file?
	else
	{
		$serverfile = resolve_server_path(urldecode($vbulletin->GPC['serverfile']));
		if (file_exists($serverfile))
		{
				$xml = file_read($serverfile);
		}
		// no uploaded file and no local file - ERROR
		else
		{
			print_stop_message2('no_file_uploaded_and_no_local_file_found_gerror');
		}
	}

	vB_Api::instanceInternal('language')->import(
		$xml,
		$vbulletin->GPC['dolanguageid'],
		$vbulletin->GPC['title'],
		$vbulletin->GPC['anyversion'],
		true,
		true
	);

	$args = [];
	$args['do'] = 'rebuild';
	$args['goto'] = 'admincp/language.php';
	print_cp_redirect2('language', $args, 0, 'admincp');
}

// ##########################################################################

if ($_REQUEST['do'] == 'files')
{
	$alllanguages = vB::getDatastore()->getValue('languagecache');
	$languages = [];
	$charsets = [
		'ISO-8859-1' => 'ISO-8859-1'
	];
	$jscharsets = [
		'-1' => 'ISO-8859-1'
	];
	$selected = 'ISO-8859-1';

	/*
		VBL-38
		When you click on the "download_upload_language" link, dolanguageid is not set.
		If you only have 1 language, the logic below makes it so that ONLY ISO-8859-1 is
		ever set.
		To work around that, let's always add the first language's charset & make that
		selected by default instead of ISO-8859-1 (which is falling out of favor)
	 */
	$firstLanguage = reset($alllanguages);
	try
	{
		// Try to clean/validate the language (e.g. make sure utf-8 has a dash) ...
		$firstLangCharset = (new vB_Utility_String($firstLanguage['charset']))->getCharset();
	}
	catch(Exception $e)
	{
		// ... but if it's an unknown charset, let's just fall back to what the language record specified for now.
		$firstLangCharset = $firstLanguage['charset'];
	}
	// Doesn't really matter, but keep it consistent with old behavior & capitalize the charset
	$firstLangCharset = strtoupper($firstLangCharset);
	$charsets[$firstLangCharset] = $firstLangCharset;
	$selected = $firstLangCharset;

	foreach ($alllanguages AS $language)
	{
		// ensure UTF charset has a dash
		try
		{
			$language['charset'] = (new vB_Utility_String($language['charset']))->getCharset();
		}
		catch (Exception $e)
		{
			$language['charset'] = $language['charset'] ;
		}
		$language['charset'] = strtoupper($language['charset']);

		$jscharsets[$language['languageid']] = $language['charset'];
		$languages[$language['languageid']] = $language['title'];
		if ($language['languageid'] == $vbulletin->GPC['dolanguageid'])
		{
			$charset = strtoupper($language['charset']);
			if ($charset != 'ISO-8859-1')
			{
				$charsets[$charset] = $charset;
				$selected = $charset;
			}
		}
	}
	?>
	<script type="text/javascript">
	<!--
	function js_set_charset(formobj, languageid)
	{
		var charsets = {
		<?php
		$output = '';
		foreach ($jscharsets AS $languageid => $charset)
		{
			$output .= "'$languageid' : '$charset',\r\n";
		}
		echo rtrim($output, "\r\n,") . "\r\n";
		?>
		};
		var charsetobj = formobj.charset;
		var charset = charsets[languageid];
		if (charset == charsetobj.options[0].value) // 'ISO-8859-1' which is always in options[0]
		{	// Remove second charset item from list since this language is 'ISO-8859-1'
			if (charsetobj.options.length == 2)
			{
				charsetobj.remove(1);
			}
			charsetobj.selectedIndex = 0;
		}
		else
		{
			if (charsetobj.options.length == 1)
			{	// Add an option!
				var option = document.createElement("option");
				charsetobj.add(option, null);
			}
			// Change the option, maybe to the same thing but that doesn't matter
			charsetobj.options[1].value = charset;
			charsetobj.options[1].text = charset;
			charsetobj.selectedIndex = 1;
		}
	}
	// -->
	</script>
	<?php

	// download form
	print_form_header('admincp/language', 'download', 0, 1, 'downloadform" target="download');
	print_table_header($vbphrase['download']);
	print_label_row($vbphrase['language'], '<select name="dolanguageid" tabindex="1" class="bginput" onchange="js_set_charset(this.form, this.value)">' . ($vb5_config['Misc']['debug'] ? '<option value="-1">' . $vbphrase['master_language'] . '</option>' : '') . construct_select_options($languages, $vbulletin->GPC['dolanguageid']) . '</select>', '', 'top', 'languageid');
	print_select_row($vbphrase['product'], 'product', fetch_product_list());
	print_input_row($vbphrase['filename_gcpglobal'], 'filename', DEFAULT_FILENAME);
	print_select_row($vbphrase['charset'], 'charset', $charsets, $selected);
	print_yes_no_row($vbphrase['include_custom_phrases'], 'custom', 0);
	print_yes_no_row($vbphrase['just_fetch_phrases'], 'just_phrases', 0);
	print_submit_row($vbphrase['download']);

	?>
	<script type="text/javascript">
	<!--
	function js_confirm_upload(tform, filefield)
	{
		if (filefield.value == "")
		{
			return confirm("<?php echo construct_phrase($vbphrase['you_did_not_specify_a_file_to_upload'], '" + tform.serverfile.value + "'); ?>");
		}
		return true;
	}
	//-->
	</script>
	<?php

	// upload form
	print_form_header('admincp/language', 'upload', 1, 1, 'uploadform" onsubmit="return js_confirm_upload(this, this.languagefile);');
	print_table_header($vbphrase['import_language_xml_file']);
	print_upload_row($vbphrase['upload_xml_file'], 'languagefile', 999999999);
	print_input_row($vbphrase['import_xml_file'], 'serverfile', './install/vbulletin-language.xml');
	print_label_row($vbphrase['overwrite_language_dfn'], '<select name="dolanguageid" tabindex="1" class="bginput"><option value="0">(' . $vbphrase['create_new_language'] . ')</option>' . construct_select_options($languages) . '</select>', '', 'top', 'olanguageid');
	print_input_row($vbphrase['title_for_uploaded_language'], 'title');
	print_yes_no_row($vbphrase['ignore_language_version'], 'anyversion', 0);
	print_submit_row($vbphrase['import']);

}

// ##########################################################################

if ($_REQUEST['do'] == 'rebuild')
{
	$vbulletin->input->clean_array_gpc('r', [
		'goto' => vB_Cleaner::TYPE_STR
	]);

	$help = construct_help_button('', NULL, '', 1);

	echo "<p>&nbsp;</p>
	<blockquote><form><div class=\"tborder\">
	<div class=\"tcat\" style=\"padding:4px\" align=\"center\"><div style=\"float:" . vB_Template_Runtime::fetchStyleVar('right') . "\">$help</div><b>" . $vbphrase['rebuild_language_information'] . "</b></div>
	<div class=\"alt1\" style=\"padding:4px\">\n<blockquote>
	";
	vbflush();

	$languages = vB::getDatastore()->getValue('languagecache');
	foreach ($languages AS $language)
	{
		echo "<p>" . construct_phrase($vbphrase['rebuilding_language_x'], "<b>{$language['title']}</b>") . iif($language['languageid'] == $vbulletin->options['languageid'], " ({$vbphrase['default']})") . ' ...';
		vbflush();
		build_language($language['languageid']);
		echo "<b>" . $vbphrase['done'] . "</b></p>\n";
		vbflush();
	}

	vB_Cache::allCacheEvent('vB_Language_languageCache');
	build_language_datastore();

	echo "</blockquote></div>
	<div class=\"tfoot\" style=\"padding:4px\" align=\"center\">
		<input type=\"button\" class=\"button\" value=\" $vbphrase[done] \" onclick=\"vBRedirect('" . str_replace("'", "\\'", htmlspecialchars_uni($vbulletin->GPC['goto'])) . "');\" />
	</div>
	</div></form></blockquote>
	";
	vbflush();

}

// ##########################################################################

if ($_REQUEST['do'] == 'setdefault')
{
	if ($vbulletin->GPC['dolanguageid'] == 0)
	{
		print_stop_message2('invalid_language_specified');
	}

	vB_Api::instanceInternal('language')->setDefault($vbulletin->GPC['dolanguageid']);

	$_REQUEST['do'] = 'modify';
}

// ##########################################################################

if ($_REQUEST['do'] == 'view')
{
	if ($vbulletin->GPC['dolanguageid'] != -1)
	{
		$language = $assertor->getRow('language', ['languageid' => $vbulletin->GPC['dolanguageid']]);
		$phrase = unserialize($language['language']);
	}
	else
	{
		$phrase = [];
		$language['title'] = $vbphrase['master_language'];

		$getphrases = $assertor->getRows('getLanguagePhrases', []);
		foreach ($getphrases AS $getphrase)
		{
			$phrase["$getphrase[varname]"] = $getphrase['text'];
		}
	}

	if (!empty($phrase))
	{
		print_form_header('admincp/', '');
		print_table_header($vbphrase['view_language'] . " <span class=\"normal\">$language[title]<span>");
		print_cells_row([$vbphrase['varname'], $vbphrase['replace_with_text']], 1, 0, 1);
		foreach ($phrase AS $varname => $text)
		{
			print_cells_row(["<span style=\"white-space: nowrap\">\$vbphrase[<b>$varname</b>]</span>", "<span class=\"smallfont\">" . htmlspecialchars_uni($text) . "</span>"], 0, 0, -1);
		}
		print_table_footer();
	}
	else
	{
		print_stop_message2('no_phrases_defined');
	}

}

// ##########################################################################

if ($_POST['do'] == 'kill')
{
	if ($vbulletin->GPC['dolanguageid'] == $vbulletin->options['languageid'])
	{
		//On the one hand, I'm not sure the back button is going to work quite like it should.
		//On the other, you have to work *really* hard to get here, so it probably doesn't matter.
		print_stop_message2('cant_delete_default_language');
	}
	else
	{
		$result = vB_Api::instance('language')->delete($vbulletin->GPC['dolanguageid']);
		print_stop_message_on_api_error($languageid);

		print_stop_message2('deleted_language_successfully', 'language');
	}
}

// ##########################################################################

if ($_REQUEST['do'] == 'delete')
{

	if ($vbulletin->GPC['dolanguageid'] == $vbulletin->options['languageid'])
	{
		print_stop_message2('cant_delete_default_language');
	}

	print_delete_confirmation('language', $vbulletin->GPC['dolanguageid'], 'language', 'kill', 'language', 0, $vbphrase['deleting_this_language_will_delete_custom_phrases']);

}

// ##########################################################################

if ($_POST['do'] == 'insert')
{
	$vbulletin->input->clean_array_gpc('p', $langglobals);

	$languageid = vB_Api::instance('language')->save($vbulletin->GPC);
	print_stop_message_on_api_error($languageid);
	print_stop_message2(['saved_language_x_successfully',  $vbulletin->GPC['title']], 'language', ['dolanguageid' => $languageid]);
}

// ##########################################################################

if ($_REQUEST['do'] == 'add')
{
	print_form_header('admincp/language', 'insert');
	print_table_header($vbphrase['add_new_language']);

	print_description_row($vbphrase['general_settings'], 0, 2, 'thead');
	print_input_row($vbphrase['title'], 'title');
	if ($vb5_config['Misc']['debug'])
	{
		print_input_row($vbphrase['vblangcode'], 'vblangcode', '', 0);
		print_input_row($vbphrase['revision'], 'revision', '', 0);
	}
	print_yes_no_row($vbphrase['allow_user_selection'], 'userselect');
	print_yes_no_row($vbphrase['enable_directional_markup_fix'], 'options[dirmark]');
	print_label_row($vbphrase['text_direction'],
		"<label for=\"rb_l2r\"><input type=\"radio\" name=\"options[direction]\" id=\"rb_l2r\" value=\"1\" tabindex=\"1\" checked=\"checked\" />$vbphrase[left_to_right]</label><br />
		 <label for=\"rb_r2l\"><input type=\"radio\" name=\"options[direction]\" id=\"rb_r2l\" value=\"0\" tabindex=\"1\" />$vbphrase[right_to_left]</label>",
		'', 'top', 'direction'
	);
	print_input_row($vbphrase['language_code'], 'languagecode', 'en');
	print_input_row($vbphrase['html_charset'] . "<code>&lt;meta http-equiv=&quot;Content-Type&quot; content=&quot;text/html; charset=<b>UTF-8</b>&quot; /&gt;</code>", 'charset', 'UTF-8');

	print_description_row($vbphrase['date_time_formatting'], 0, 2, 'thead');
	print_input_row($vbphrase['locale'], 'locale', '');
	print_input_row($vbphrase['date_format_override'], 'dateoverride', '');
	print_input_row($vbphrase['time_format_override'], 'timeoverride', '');
	print_input_row($vbphrase['registereddate_format_override'], 'registereddateoverride', '');
	print_input_row($vbphrase['calformat1_format_override'], 'calformat1override', '');
	print_input_row($vbphrase['calformat2_format_override'], 'calformat2override', '');
	print_input_row($vbphrase['eventdateformat_override'], 'eventdateformatoverride', '');
	print_input_row($vbphrase['pickerdateformat_override'], 'pickerdateformatoverride', '');
	print_input_row($vbphrase['logdate_format_override'], 'logdateoverride', '');

	print_description_row($vbphrase['number_formatting'], 0, 2, 'thead');
	print_input_row($vbphrase['decimal_separator'], 'decimalsep', '.', 1, 3, 1);
	print_input_row($vbphrase['thousands_separator'], 'thousandsep', ',', 1, 3, 1);

	print_submit_row($vbphrase['save']);
}

// ##########################################################################

if ($_POST['do'] == 'update_settings')
{
	$vbulletin->input->clean_array_gpc('p', array_merge($langglobals, ['isdefault' => vB_Cleaner::TYPE_BOOL]));

	// Auto correction. See VBV-8639
	if (strtolower($vbulletin->GPC['charset']) == 'utf8')
	{
		$vbulletin->GPC['charset'] = 'utf-8';
	}
	$vbulletin->GPC['charset'] = strtoupper($vbulletin->GPC['charset']);

	$languageid = vB_Api::instance('language')->save($vbulletin->GPC, $vbulletin->GPC['dolanguageid']);
	print_stop_message_on_api_error($languageid);

	if ($vbulletin->GPC['isdefault'] AND $vbulletin->GPC['dolanguageid'] != $vbulletin->options['languageid'])
	{
		$do = 'setdefault';
	}
	else
	{
		$do = 'modify';
	}

	vB_Cache::allCacheEvent('vB_Language_languageCache');
	build_language_datastore();

	print_stop_message2(['saved_language_x_successfully',  $vbulletin->GPC['title']], 'language', ['dolanguageid' => $vbulletin->GPC['dolanguageid'],'do' => $do]);
}

// ##########################################################################
if ($_REQUEST['do'] == 'edit_settings')
{
	$language = vB_Api::instanceInternal('language')->fetchAll($vbulletin->GPC['dolanguageid']);

	$getoptions = convert_bits_to_array($language['options'], $vbulletin->bf_misc_languageoptions);
	$language = array_merge($language, $getoptions);
	print_form_header('admincp/language', 'update_settings');
	construct_hidden_code('dolanguageid', $vbulletin->GPC['dolanguageid']);
	print_table_header(construct_phrase($vbphrase['x_y_id_z'], $vbphrase['language'], $language['title'], $language['languageid']));

	print_description_row($vbphrase['general_settings'], 0, 2, 'thead');
	print_input_row($vbphrase['title'], 'title', $language['title'], 0);
	if ($vb5_config['Misc']['debug'])
	{
		print_input_row($vbphrase['vblangcode'], 'vblangcode', $language['vblangcode'], 0);
		print_input_row($vbphrase['revision'], 'revision', $language['revision'], 0);
	}
	elseif (!empty($language['vblangcode']) AND !empty($language['revision']))
	{
		print_label_row($vbphrase['vblangcode'], $language['vblangcode']);
		print_label_row($vbphrase['revision'], $language['revision']);
	}
	print_yes_no_row($vbphrase['allow_user_selection'], 'userselect', $language['userselect']);
	$yesnoValue = ($vbulletin->GPC['dolanguageid'] == $vbulletin->options['languageid'] ? 1 : 0);
	print_yes_no_row($vbphrase['is_default_language'], 'isdefault', $yesnoValue);
	print_yes_no_row($vbphrase['enable_directional_markup_fix'], 'options[dirmark]', $language['dirmark']);
	print_label_row($vbphrase['text_direction'],
		'<label for="rb_l2r"><input type="radio" name="options[direction]" id="rb_l2r" value="1" tabindex="1"' . iif($language['direction'], ' checked="checked"') . " />$vbphrase[left_to_right]</label><br />" . '
		 <label for="rb_r2l"><input type="radio" name="options[direction]" id="rb_r2l" value="0" tabindex="1"' . iif(!($language['direction']), ' checked="checked"') . " />$vbphrase[right_to_left]</label>",
		'', 'top', 'direction'
	);
	print_input_row($vbphrase['language_code'], 'languagecode', $language['languagecode']);
	print_input_row($vbphrase['html_charset'], 'charset', $language['charset']);

	print_description_row($vbphrase['date_time_formatting'], 0, 2, 'thead');
	print_input_row($vbphrase['locale'], 'locale', $language['locale']);
	print_input_row($vbphrase['date_format_override'], 'dateoverride', $language['dateoverride']);
	print_input_row($vbphrase['time_format_override'], 'timeoverride', $language['timeoverride']);
	print_input_row($vbphrase['registereddate_format_override'], 'registereddateoverride', $language['registereddateoverride']);
	print_input_row($vbphrase['calformat1_format_override'], 'calformat1override', $language['calformat1override']);
	print_input_row($vbphrase['calformat2_format_override'], 'calformat2override', $language['calformat2override']);
	print_input_row($vbphrase['eventdateformat_override'], 'eventdateformatoverride', $language['eventdateformatoverride']);
	print_input_row($vbphrase['pickerdateformat_override'], 'pickerdateformatoverride', $language['pickerdateformatoverride']);
	print_input_row($vbphrase['logdate_format_override'], 'logdateoverride', $language['logdateoverride']);

	print_description_row($vbphrase['number_formatting'], 0, 2, 'thead');
	print_input_row($vbphrase['decimal_separator'], 'decimalsep', $language['decimalsep'], 1, 3, 1);
	print_input_row($vbphrase['thousands_separator'], 'thousandsep', $language['thousandsep'], 1, 3, 1);

	print_submit_row($vbphrase['save']);

}

// ##########################################################################

if ($_REQUEST['do'] == 'edit')
{
	$vbulletin->input->clean_array_gpc('r', [
		'fieldname'  => vB_Cleaner::TYPE_NOHTML,
		'pagenumber' => vB_Cleaner::TYPE_UINT,
		'prev'       => vB_Cleaner::TYPE_STR,
		'next'       => vB_Cleaner::TYPE_STR
	]);

	if ($vbulletin->GPC['prev'] != '' OR $vbulletin->GPC['next'] != '')
	{
		if ($vbulletin->GPC['prev'] != '')
		{
			$vbulletin->GPC['pagenumber'] -= 1;
		}
		else
		{
			$vbulletin->GPC['pagenumber'] += 1;
		}
	}

	if ($vbulletin->GPC['fieldname'] == '')
	{
		$vbulletin->GPC['fieldname'] = 'global';
	}

	// ***********************
	if ($vbulletin->GPC['dolanguageid'] == 0)
	{
		$_REQUEST['do'] = 'modify';
	}
	else
	{
	// ***********************

	$perpage = 10;

	print_phrase_ref_popup_javascript();

	?>
	<script type="text/javascript">
	<!--
	function js_fetch_default(varname)
	{
		var P = eval('document.forms.cpform.P_' + varname);
		var D = eval('document.forms.cpform.D_' + varname);
		P.value = D.value;
	}

	function js_change_direction(direction, varname)
	{
		var P = eval('document.forms.cpform.P_' + varname);
		P.dir = direction;
	}
	// -->
	</script>
	<?php

	// build top options and get language info
	$languages = vB::getDatastore()->getValue('languagecache');
	if ($vb5_config['Misc']['debug'])
	{
		$mlanguages = [
			'-1' => [
				'languageid' => -1,
				'title' => $vbphrase['master_language'],
				'direction' => 'ltr',
			]
		];
		$languages = $mlanguages + $languages;
	}
	$langoptions = [];

	foreach ($languages AS $lang)
	{
		$langoptions["{$lang['languageid']}"] = $lang['title'];
		if ($lang['languageid'] == $vbulletin->GPC['dolanguageid'])
		{
			$language = $lang;
		}
	}

	$phrasetypeoptions = [];
	$phrasetypes = vB_Api::instanceInternal('phrase')->fetch_phrasetypes();
	foreach ($phrasetypes AS $fieldname => $type)
	{
		$phrasetypeoptions["$fieldname"] = $type['title'];
	}

	print_phrase_ref_popup_javascript();

	// get custom phrases
	$numcustom = 0;
	if ($vbulletin->GPC['dolanguageid'] != -1)
	{
		$custom_phrases = fetch_custom_phrases($vbulletin->GPC['dolanguageid'], $vbulletin->GPC['fieldname']);
		$numcustom = sizeof($custom_phrases);
	}
	// get inherited and customized phrases
	$standard_phrases = fetch_standard_phrases($vbulletin->GPC['dolanguageid'], $vbulletin->GPC['fieldname'], $numcustom);

	$numstandard = sizeof($standard_phrases);
	$totalphrases = $numcustom + $numstandard;

	$numpages = ceil($totalphrases / $perpage);

	if ($vbulletin->GPC['pagenumber'] < 1)
	{
		$vbulletin->GPC['pagenumber'] = 1;
	}
	if ($vbulletin->GPC['pagenumber'] > $numpages)
	{
		$vbulletin->GPC['pagenumber'] = $numpages;
	}
	$startat = ($vbulletin->GPC['pagenumber'] - 1) * $perpage;
	$endat = $startat + $perpage;
	if ($endat >= $totalphrases)
	{
		$endat = $totalphrases;
	}

	$i = 15;

	$p = 0;
	$pageoptions = [];
	for ($i = 0; $i < $totalphrases; $i += $perpage)
	{
		$p++;
		$firstphrase = $i;
		$lastphrase = $firstphrase + $perpage - 1;
		if ($lastphrase >= $totalphrases)
		{
			$lastphrase = $totalphrases - 1;
		}
		$pageoptions["$p"] = "$vbphrase[page_gcpglobal] $p ";//<!--(" . ($firstphrase + 1) . " to " . ($lastphrase + 1) . ")-->";
	}

	$showprev = true;
	$shownext = true;
	if ($vbulletin->GPC['pagenumber'] == 1)
	{
		$showprev = false;
	}
	if ($vbulletin->GPC['pagenumber'] >= $numpages)
	{
		$shownext = false;
	}

	// #############################################################################

	print_form_header('admincp/language', 'edit', 0, 1, 'qform', '90%', '', 1, 'get');
	$langSelect = construct_select_options($langoptions, $vbulletin->GPC['dolanguageid']);
	$phraseTypeSelect = construct_select_options($phrasetypeoptions, $vbulletin->GPC['fieldname']);
	$pageSelect = construct_select_options($pageoptions, $vbulletin->GPC['pagenumber']);
	$prevButtonDisable = $showprev ? '' : ' disabled="disabled" ';
	$nextButtonDisable = $shownext ? '' : ' disabled="disabled" ';
	$alreadyDefault = ($vbulletin->GPC['dolanguageid'] == -1 OR $vbulletin->GPC['dolanguageid'] == $vbulletin->options['languageid']);
	if ($alreadyDefault)
	{
		$setDefaultAttributes = ' disabled="disabled" ';
	}
	else
	{
		$setDefaultAttributes = ' title="' . construct_phrase($vbphrase['set_language_as_default_x'], $language['title'])	. '" ';
	}

	echo <<<EOT
<style>
.language-pagination-header {
	display: flex;
	justify-content: space-around;
}
.left-column {
	/* give this a min width to keep it looking consistent for smaller viewport widths.
	The middle column, with flex-grow:1 basically acts as a sacrificial padding provider
	as needed
	 */
	flex-basis: 30%;
	display: flex;
	flex-direction: column;
}
.left-column div {
	/* there are two rows in this column, make each one look like .thead */
	min-height: 30px;
	display: flex;
	align-items: center;
}
.left-column div span {
	/* Trick to make the language & phrase type drop downs left-aligned */
	flex-basis: 30%;
}
.middle-column {
	/* grow as much as needed to not leave any "gaps" between the 3 columns. We don't want
	to set a fixed width because that won't allow for "sacrificial" room for the fixed width
	of the left-column for smaller viewports.
	 */
	flex-grow: 1;
	display: flex;
	justify-content: center;
	align-items: center;
	flex-basis: 40%;
}

/* Pop the pagination select out into its own line above the prev/next buttons if screensize
 is too cramped, in media query as to not wrap too early like the right column */
@media(max-width: 767px) {
	.middle-column{
		flex-wrap: wrap;
	}
	.middle-column select {
		flex: 0 0 100%;
		order: -1;
	}
}
.right-column {
	/* Make the right column, which has the least elements, yield most quickly if we're on a smaller
	viewport in order to keep the alignment on the densest left-column as long as possible. */
	flex-shrink: 2;
	flex-wrap: wrap;
	display: flex;
	justify-content: center;
	align-items: center;
	flex-basis: 40%;
}
</style>

<div class="language-pagination-header">
	<div class="left-column thead">
		<div>
			<span>{$vbphrase['language']}:</span>
			<select name="dolanguageid" onchange="this.form.submit()" class="bginput">
				$langSelect
			</select>
			<!--
			<input type="submit" class="button" value="{$vbphrase['go']}" />
			-->
		</div>
		<div>
			<span>{$vbphrase['phrase_type']}:</span>
			<select
				name="fieldname"
				onchange="this.form.page.selectedIndex = 0; this.form.submit()"
				class="bginput">
				$phraseTypeSelect
			</select>
		</div>
	</div>


	<div class="middle-column thead" >
		<input type="submit" class="button" name="prev" value="&laquo; {$vbphrase['prev']}" $prevButtonDisable />
		<select name="page" onchange="this.form.submit()" class="bginput">$pageSelect</select>
		<input type="submit" class="button" name="next" value="{$vbphrase['next']} &raquo;" $nextButtonDisable />
	</div>


	<div class="right-column thead">
		<input type="button" class="button"
			value="{$vbphrase['view_quickref_glanguage']}"
			onclick="js_open_phrase_ref({$vbulletin->GPC['dolanguageid']}, '{$vbulletin->GPC['fieldname']}');"
			/>
		<input type="button" class="button"
			value="{$vbphrase['set_default']}"
			$setDefaultAttributes
			onclick="vBRedirect('admincp/language.php?do=setdefault&amp;dolanguageid={$vbulletin->GPC['dolanguageid']}');"
			/>

	</div>
</div>
EOT;
	print_table_footer();

	$printers = [];

	$i = 0;
	if ($startat < $numcustom)
	{
		for ($i = $startat; $i < $endat AND $i < $numcustom; $i++)
		{
			$printers["$i"] =& $custom_phrases["$i"];
		}
	}
	if ($i < $endat)
	{
		if ($i == 0)
		{
			$i = $startat;
		}
		for ($i; $i < $endat AND $i < $totalphrases; $i++)
		{
			$printers["$i"] =& $standard_phrases["$i"];
		}
	}

	// ******************

	print_form_header('admincp/language', 'update');
	construct_hidden_code('dolanguageid', $vbulletin->GPC['dolanguageid']);
	construct_hidden_code('fieldname', $vbulletin->GPC['fieldname']);
	construct_hidden_code('page', $vbulletin->GPC['pagenumber']);

	$edittranslate = construct_phrase(
		$vbphrase['edit_translate_x_y_phrases'],
		$languages[$vbulletin->GPC['dolanguageid']]['title'],
		"<span class=\"normal\">" . $phrasetypes[$vbulletin->GPC['fieldname']]['title'] . "</span>"
	);

	print_table_header($edittranslate . ' <span class="normal">' . construct_phrase($vbphrase['page_x_of_y'], $vbulletin->GPC['pagenumber'], $numpages) . '</span>');
	print_column_style_code(['', '" width="20']);
	$lasttype = '';
	foreach ($printers AS $key => $printer)
	{
		if ($lasttype != $printer['type'])
		{
			print_label_row($vbphrase['varname'], $vbphrase['text_gcpglobal'], 'thead');
		}
		print_phrase_row($printer, $phrasetypes[$vbulletin->GPC['fieldname']]['editrows'], $key, $language['direction']);

		$lasttype = $printer['type'];
	}
	print_submit_row();

	// ******************

	if ($numpages > 1)
	{
		print_form_header('admincp/language', 'edit', 0, 1, 'qform', '90%', '', 1, 'get');
		construct_hidden_code('dolanguageid', $vbulletin->GPC['dolanguageid']);
		construct_hidden_code('fieldname', $vbulletin->GPC['fieldname']);
		$pagebuttons = '';
		for ($p = 1; $p <= $numpages; $p++)
		{
			$pagebuttons .= "\n\t\t\t\t<input type=\"submit\" class=\"button\" style=\"font:10px verdana\" name=\"page\" value=\"$p\" tabindex=\"1\" title=\"$vbphrase[page_gcpglobal] $p\"" . iif($p == $vbulletin->GPC['pagenumber'], ' disabled="disabled"') . ' />';
		}
		echo '
		<tr>' . iif($showprev, '
			<td class="thead"><input type="submit" class="button" name="prev" value="&laquo; ' . $vbphrase['prev'] . '" tabindex="1" /></td>') . '
			<td class="thead" width="100%" align="center"><input type="hidden" name="page" value="' . $vbulletin->GPC['pagenumber'] . '" />' . $pagebuttons . '
			</td>' . iif($shownext, '
			<td class="thead"><input type="submit" class="button" name="next" value="' . $vbphrase['next'] . ' &raquo;" tabindex="1" /></td>') . '
		</tr>
		';
		print_table_footer();
	}

	// ***********************
	} // end if ($languageid != 0)
	// ***********************

}

// ##########################################################################

if ($_REQUEST['do'] == 'modify')
{
	function print_language_row($language, $debug, $vbphrase, $align)
	{
		$languageid = $language['languageid'];
		$isdefault = $languageid == vB::getDatastore()->getOption('languageid');
		$ismaster = $languageid == -1;

		$cells = [];
		$cells[] = (($debug AND !$ismaster) ? '-- ' : '') . fetch_tag_wrap($language['title'], 'b', $isdefault);

		$url = get_admincp_url('language', ['do' => 'edit', 'dolanguageid' => $languageid]);
		$cells[] = '<a href="' . $url . '">' . construct_phrase($vbphrase['edit_translate_x_y_phrases'], $language['title'], '') . '</a>';

		$actions = [];
		if (!$ismaster)
		{
			$actions[] = construct_link_code2($vbphrase['edit_settings_glanguage'], get_admincp_url('language', ['do' => 'edit_settings', 'dolanguageid' => $languageid]));
			$actions[] = construct_link_code2($vbphrase['delete'], get_admincp_url('language', ['do' => 'delete', 'dolanguageid' => $languageid]));
		}

		$actions [] = construct_link_code2($vbphrase['download'], get_admincp_url('language', ['do' => 'files', 'dolanguageid' => $languageid]));
		$cells[] = implode("\n", $actions);

		if (!$ismaster)
		{
			$extra = [];
			if ($isdefault)
			{
				$extra['disabled'] = null;
			}

			$cells[] = construct_link_button($vbphrase['set_default'], get_admincp_url('language', ['do' => 'setdefault', 'dolanguageid' => $languageid]), false, [], $extra);
		}
		else
		{
			$cells[] = '';
		}

		print_cells_row2($cells, '', $align);
	}

	$debug = !empty($vb5_config['Misc']['debug']);

	print_form_header2('', '');
	print_table_start();

	$align = ['vbleft', 'vbleft', 'vbleft', 'center'];
	$headers = [$vbphrase['language'], '', '', $vbphrase['default']];
	$colspan = count($headers);
	print_table_header($vbphrase['language_manager_glanguage'], $colspan);
	print_cells_row2($headers, 'thead', $align);

	if ($debug)
	{
		print_language_row(['languageid' => -1, 'title' => '<i>' . $vbphrase['master_language'] . '</i>'], $debug, $vbphrase, $align);
	}

	$languages = vB::getDatastore()->getValue('languagecache');
	foreach ($languages AS $language)
	{
		print_language_row($language, $debug, $vbphrase, $align);
	}

	$gotourl = get_admincp_url('language', []);
/*
	$links = [
		construct_link_code2($vbphrase['search_phrases'], get_admincp_url('phrase', ['do' => 'search'])),
		construct_link_code2($vbphrase['view_quickref_glanguage'], 'javascript:js_open_phrase_ref(0,0);'),
		construct_link_code2($vbphrase['rebuild_all_languages'], get_admincp_url('language', ['do' => 'rebuild', 'goto' => $gotourl])),
		construct_link_code2($vbphrase['download_upload_language'] , get_admincp_url('language', ['do' => 'files'])),
		construct_link_code2($vbphrase['add_new_language'] , get_admincp_url('language', ['do' => 'add'])),
	];
	print_phrase_ref_popup_javascript();
	print_description_row(implode("\n", $links), 0, $colspan, 'thead" style="text-align:center; font-weight:normal');
	print_table_footer();
*/

	$buttons = [
		construct_link_button($vbphrase['search_phrases'], get_admincp_url('phrase', ['do' => 'search'])),
		construct_event_button($vbphrase['view_quickref_glanguage'], 'js-link-popup', [
			'href' => get_admincp_url('phrase', ['do' => 'quickref']),
			'width' => 1000,
			'height' => 300,
		]),
		construct_link_button($vbphrase['rebuild_all_languages'], get_admincp_url('language', ['do' => 'rebuild', 'goto' => $gotourl])),
		construct_link_button($vbphrase['download_upload_language'] , get_admincp_url('language', ['do' => 'files'])),
		construct_link_button($vbphrase['add_new_language'] , get_admincp_url('language', ['do' => 'add'])),
	];
	print_table_button_footer($buttons, $colspan);
}

print_cp_footer();

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 115002 $
|| #######################################################################
\*=========================================================================*/
