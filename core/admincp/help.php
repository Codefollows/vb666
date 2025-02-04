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
define('CVS_REVISION', '$RCSfile$ - $Revision: 115374 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
global $phrasegroups, $specialtemplates, $vbphrase, $vbulletin;
$phrasegroups = ['help_faq', 'fronthelp'];
$specialtemplates = [];

// ########################## REQUIRE BACK-END ############################
require_once(dirname(__FILE__) . '/global.php');
require_once(DIR . '/includes/adminfunctions_help.php');

// ############################# LOG ACTION ###############################

$vbulletin->input->clean_array_gpc('r', ['adminhelpid' => vB_Cleaner::TYPE_INT]);

log_admin_action($vbulletin->GPC['adminhelpid'] != 0 ? 'help id = ' . $vbulletin->GPC['adminhelpid'] : '');

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

$vb5_config =& vB::getConfig();
$assertor = vB::getDbAssertor();

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'answer';
}

// ############################### start download help XML ##############
if ($_REQUEST['do'] == 'download')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'product' => vB_Cleaner::TYPE_STR
	));

	$doc = get_help_export_xml($vbulletin->GPC['product']);
	require_once(DIR . '/includes/functions_file.php');
	file_download($doc, 'vbulletin-adminhelp.xml', 'text/xml');
}

// #########################################################################

print_cp_header($vbphrase['admin_help']);

if ($vb5_config['Misc']['debug'])
{
	print_form_header('admincp/', '', 0, 1, 'notaform');
	print_table_header($vbphrase['admin_help_manager_ghelp_faq']);
	print_description_row(
		construct_link_code($vbphrase['add_new_topic'], "help.php?do=edit") .
		construct_link_code($vbphrase['edit_topics'], "help.php?do=manage") .
		construct_link_code($vbphrase['download_upload_adminhelp'], "help.php?do=files"), 0, 2, '', 'center');
	print_table_footer();
}

// ############################### start do upload help XML ##############
if ($_REQUEST['do'] == 'doimport')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'serverfile'	=> vB_Cleaner::TYPE_STR,
	));

	$vbulletin->input->clean_array_gpc('f', array(
		'helpfile'		=> vB_Cleaner::TYPE_FILE,
	));

	// got an uploaded file?
	// do not use file_exists here, under IIS it will return false in some cases
	if (is_uploaded_file($vbulletin->GPC['helpfile']['tmp_name']))
	{
		scanVbulletinGPCFile('helpfile');
		$xml = file_read($vbulletin->GPC['helpfile']['tmp_name']);
	}
	// no uploaded file - got a local file?
	else if (file_exists($vbulletin->GPC['serverfile']))
	{
		$xml = file_read($vbulletin->GPC['serverfile']);
	}
	// no uploaded file and no local file - ERROR
	else
	{
		print_stop_message2('no_file_uploaded_and_no_local_file_found_gerror');
	}

	xml_import_help_topics($xml);

	echo '<p align="center">' . $vbphrase['imported_admin_help_successfully'] . '<br />' .
		construct_link_code($vbphrase['continue'], "help.php?do=manage") . '</p>';
}

// ############################### start upload help XML ##############
if ($_REQUEST['do'] == 'files')
{
	// download form
	print_form_header('admincp/help', 'download', 0, 1, 'downloadform" target="download');
	print_table_header($vbphrase['download']);
	print_select_row($vbphrase['product'], 'product', fetch_product_list());
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

	print_form_header('admincp/help', 'doimport', 1, 1, 'uploadform" onsubmit="return js_confirm_upload(this, this.helpfile);');
	print_table_header($vbphrase['import_admin_help_xml_file']);
	print_upload_row($vbphrase['upload_xml_file'], 'helpfile', 999999999);
	print_input_row($vbphrase['import_xml_file'], 'serverfile', './install/vbulletin-adminhelp.xml');
	print_submit_row($vbphrase['import'], 0);
}

// ############################### start listing answers ##############
if ($_REQUEST['do'] == 'answer')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'page'       => vB_Cleaner::TYPE_STR,
		'pageaction' => vB_Cleaner::TYPE_STR,
		'option'     => vB_Cleaner::TYPE_STR,
	));

	if (empty($vbulletin->GPC['page']))
	{
		$fullpage = REFERRER;
	}
	else
	{
		$fullpage = $vbulletin->GPC['page'];
	}

	if (!$fullpage)
	{
		print_stop_message2('invalid_page_specified');
	}
	$strpos = strpos($fullpage, '?');
	if ($strpos)
	{
		$pagename = basename(substr($fullpage, 0, $strpos));
	}
	else
	{
		$pagename = basename($fullpage);
	}
	$strpos = strpos($pagename, '.');
	if ($strpos)
	{
		// remove the .php part as people may have different extensions
		$pagename = substr($pagename, 0, $strpos);
	}

	if (!empty($vbulletin->GPC['pageaction']))
	{
		$action = $vbulletin->GPC['pageaction'];
	}
	else if ($strpos AND preg_match('#do=([^&]+)(&|$)#sU', substr($fullpage, $strpos), $matches))
	{
		$action = $matches[1];
	}
	else
	{
		$action = '';
	}

	$option = empty($vbulletin->GPC['option']) ? false : $vbulletin->GPC['option'];
	$helptopics = $assertor->assertQuery('vBForum:getHelpLength', array(
		'pagename' => $pagename,
		'action' => $action,
		'option' => $option
	));

	$resultcount = 0;
	if (!$helptopics AND !empty($helptopics['errors']) AND !$helptopics->valid())
	{
		print_stop_message2('no_help_topics');
	}
	else
	{
		$general = array();
		$specific = array();
		$phraseSQL = array();
		foreach ($helptopics AS $topic)
		{
			$resultcount++;
			$phrasename = fetch_help_phrase_short_name($topic);
			$phraseSQL[] = "$phrasename" . "_title";
			$phraseSQL[] = "$phrasename" . "_text";

			if (!$topic['action'])
			{
				$general[] = $topic;
			}
			else
			{
				$specific[] = $topic;
			}
		}

		// query phrases
		$helpphrase = array();
		$phrases = $assertor->assertQuery('vBForum:phrase',
				array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
					vB_dB_Query::CONDITIONS_KEY => array(
						array('field' => 'fieldname', 'value' => 'cphelptext', 'operator' => vB_dB_Query::OPERATOR_EQ),
						array('field' => 'languageid', 'value' => array(-1, 0, LANGUAGEID), 'operator' => vB_dB_Query::OPERATOR_EQ),
						array('field' => 'varname', 'value' => $phraseSQL, 'operator' => vB_dB_Query::OPERATOR_EQ)
					)
				),
				array('field' => 'languageid', 'direction' => vB_dB_Query::SORT_ASC)
			);

		if ($phrases AND (is_object($phrases) OR empty($phrases['errors'])) AND $phrases->valid())
		{
			foreach($phrases AS $phrase)
			{
				$helpphrase["$phrase[varname]"] = vB_Library::instance('Phrase')->replaceOptionsAndConfigValuesInPhrase($phrase['text']);
			}
		}

		if ($resultcount != 1)
		{
			//because of the new base tag we need to match the existing url
			//instead of giving a bare hash or it will attempt to reload the url
			$query['do'] = 'answer';
			$query['page'] = $vbulletin->GPC['page'];
			$query['pageaction'] = $vbulletin->GPC['pageaction'];
			$query['option'] = $vbulletin->GPC['option'];
			$url = 'admincp/help.php?' .  http_build_query($query);

			print_form_header('admincp/', '');
			print_table_header($vbphrase['quick_help_topic_links'], 1);
			if (sizeof($specific))
			{
				print_description_row($vbphrase['action_specific_topics'], 0, 1, 'thead');
				foreach ($specific AS $topic)
				{
					print_description_row('<a href="' . $url . '#help' . $topic['adminhelpid'] . '">' . $helpphrase[fetch_help_phrase_short_name($topic, '_title')] . '</a>', 0, 1);
				}
			}
			if (sizeof($general))
			{
				print_description_row($vbphrase['general_topics'], 0, 1, 'thead');
				foreach ($general AS $topic)
				{
					print_description_row('<a href="' . $url . '#help' . $topic['adminhelpid'] . '">' . $helpphrase[fetch_help_phrase_short_name($topic, '_title')] . '</a>', 0, 1);
				}
			}
			print_table_footer();
		}

		if (sizeof($specific))
		{
			reset($specific);
			print_form_header('admincp/', '');
			if ($resultcount != 1)
			{
				print_table_header($vbphrase['action_specific_topics'], 1);
			}
			foreach ($specific AS $topic)
			{
				print_description_row("<a name=\"help$topic[adminhelpid]\">" . $helpphrase[fetch_help_phrase_short_name($topic, '_title')] . "</a>", 0, 1, 'thead');
				print_description_row($helpphrase[fetch_help_phrase_short_name($topic, '_text')], 0, 1, 'alt1');
				if ($vb5_config['Misc']['debug'])
				{
					print_description_row("<div style=\"float:" . vB_Template_Runtime::fetchStyleVar('right') . "\">" .
						construct_button_code($vbphrase['edit'], "admincp/help.php?do=edit&amp;adminhelpid=$topic[adminhelpid]") .
						"</div><div>action = $topic[action] | optionname = $topic[optionname] | displayorder = $topic[displayorder]</div>", 0, 1, 'alt2 smallfont'
					);
				}
			}
			print_table_footer();
		}

		if (sizeof($general))
		{
			reset($general);
			print_form_header('admincp/', '');
			if ($resultcount != 1)
			{
				print_table_header($vbphrase['general_topics'], 1);
			}
			foreach ($general AS $topic)
			{
				print_description_row("<a name=\"help$topic[adminhelpid]\">" . $helpphrase[fetch_help_phrase_short_name($topic, '_title')] . "</a>", 0, 1, 'thead');
				print_description_row($helpphrase[fetch_help_phrase_short_name($topic, '_text')]);
			}
			print_table_footer();
		}
	}
}

// ############################### start form for adding/editing help topics ##############
if ($_REQUEST['do'] == 'edit')
{
	$vbulletin->input->clean_array_gpc('r', [
		'adminhelpid'	=> vB_Cleaner::TYPE_INT,
		'script'	=> vB_Cleaner::TYPE_NOHTML,
		'scriptaction'	=> vB_Cleaner::TYPE_NOHTML,
		'option'	=> vB_Cleaner::TYPE_NOHTML,
	]);

	print_form_header('admincp/help', 'doedit');
	if (empty($vbulletin->GPC['adminhelpid']))
	{
		$adminhelpid = 0;
		$helpdata = [
			'adminhelpid'  => 0,
			'script'       => $vbulletin->GPC['script'],
			'action'       => $vbulletin->GPC['scriptaction'],
			'optionname'   => $vbulletin->GPC['option'],
			'product'      => 'vbulletin',
			'displayorder' => 1,
			'volatile'     => ($vb5_config['Misc']['debug'] ? 1 : 0),
		];

		$titleval = '';
		$textval = '';

		print_table_header($vbphrase['add_new_topic']);
	}
	else
	{
		$helpdata = $assertor->getRow('vBForum:adminhelp', ['adminhelpid' => $vbulletin->GPC['adminhelpid']]);

		$action = $helpdata['action'] ? "_" . $helpdata['action'] : "";
		$optionname = $helpdata['optionname'] ? "_" . $helpdata['optionname'] : "";
		$titlephrase = fetch_help_phrase_short_name($helpdata, '_title');
		$textphrase = fetch_help_phrase_short_name($helpdata, '_text');

		// query phrases -- don't use the API render because we specifically want the master language/special
		// "app master" language (used for the "master" for nonstandard vb phrases) instead of any of the
		// actual language records.
		$conditions = [
			'fieldname' => 'cphelptext',
			'languageid' => ($helpdata['volatile'] ? -1 : 0),
			'varname' => [$titlephrase, $textphrase]
		];
		$helpphrase = $assertor->getColumn('vBForum:phrase', 'text', $conditions, false, 'varname');
		$titleval = $helpphrase[$titlephrase];
		$textval = $helpphrase[$textphrase];

		construct_hidden_code('orig[script]', $helpdata['script']);
		construct_hidden_code('orig[action]', $helpdata['action']);
		construct_hidden_code('orig[optionname]', $helpdata['optionname']);
		construct_hidden_code('orig[product]', $helpdata['product']);
		construct_hidden_code('orig[title]', $titleval);
		construct_hidden_code('orig[text]', $textval);

		print_table_header(construct_phrase($vbphrase['x_y_id_z'], $vbphrase['topic'], $titleval, $helpdata['adminhelpid']));
	}

	print_input_row($vbphrase['script'], 'help[script]', $helpdata['script']);
	print_input_row($vbphrase['action_leave_blank'], 'help[action]', $helpdata['action']);

	print_select_row($vbphrase['product'], 'help[product]', fetch_product_list(), $helpdata['product']);

	print_input_row($vbphrase['option'], 'help[optionname]', $helpdata['optionname']);
	print_input_row($vbphrase['display_order'], 'help[displayorder]', $helpdata['displayorder']);

	print_input_row($vbphrase['title'], 'title', $titleval);
	print_textarea_row($vbphrase['text_gcpglobal'], 'text', $textval, 10, '50" style="width:100%');

	if ($vb5_config['Misc']['debug'])
	{
		print_yes_no_row($vbphrase['vbulletin_default'], 'help[volatile]', $helpdata['volatile']);
	}
	else
	{
		construct_hidden_code('help[volatile]', $helpdata['volatile']);
	}

	construct_hidden_code('adminhelpid', $vbulletin->GPC['adminhelpid']);
	print_submit_row($vbphrase['save']);
}

// ############################### start actually adding/editing help topics ##############
if ($_POST['do'] == 'doedit')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'adminhelpid'	=> vB_Cleaner::TYPE_INT,
		'help'			=> vB_Cleaner::TYPE_ARRAY_STR,
		'orig'			=> vB_Cleaner::TYPE_ARRAY_STR,
		'title' 		=> vB_Cleaner::TYPE_STR,
		'text' 			=> vB_Cleaner::TYPE_STR
	));

	if (!$vbulletin->GPC['help']['script'])
	{
		print_stop_message2('please_complete_required_fields');
	}

	//no longer need the escape here, handled by db assetor
	//$newphrasename = $vbulletin->db->escape_string(fetch_help_phrase_short_name($vbulletin->GPC['help']));
	$newphrasename = fetch_help_phrase_short_name($vbulletin->GPC['help']);

	$languageid = ($vbulletin->GPC['help']['volatile'] ? -1 : 0);

	$full_product_info = fetch_product_list(true);
	$product_version = $full_product_info[$vbulletin->GPC['help']['product']]['version'];

	if (!empty($vbulletin->GPC['orig'])) // update
	{
		$action = $vbulletin->GPC['orig']['action'] ? "_" . $vbulletin->GPC['orig']['action'] : "";
		$optionname = $vbulletin->GPC['orig']['optionname'] ? "_" . $vbulletin->GPC['orig']['optionname'] : "";
		$oldphrasename = fetch_help_phrase_short_name($vbulletin->GPC['orig']);

		// update help item
		$assertor->assertQuery('vBForum:adminhelp',
				array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
					'script' => $vbulletin->GPC['help']['script'],
					'action' => $vbulletin->GPC['help']['action'],
					'product' => $vbulletin->GPC['help']['product'],
					'optionname' => $vbulletin->GPC['help']['optionname'],
					'displayorder' => $vbulletin->GPC['help']['displayorder'],
					'volatile' => $vbulletin->GPC['help']['volatile'],
					vB_dB_Query::CONDITIONS_KEY => array(
						array('field'=>'adminhelpid','value'=>$vbulletin->GPC['adminhelpid'], 'operator'=> vB_dB_Query::OPERATOR_EQ)
					)
				)
		);

		// update phrase titles for all languages
		if ($newphrasename != $oldphrasename)
		{
			$assertor->assertQuery('vBForum:phrase',
				array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
					'varname' => $newphrasename . "_title",
					vB_dB_Query::CONDITIONS_KEY => array(
						array('field' => 'fieldname','value' => 'cphelptext', 'operator' => vB_dB_Query::OPERATOR_EQ),
						array('field' => 'varname', 'value' => $oldphrasename . "_title", 'operator' => vB_dB_Query::OPERATOR_EQ)
					)
				)
			);
			$assertor->assertQuery('vBForum:phrase',
				array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
					'varname' => $newphrasename . "_text",
					vB_dB_Query::CONDITIONS_KEY => array(
						array('field' => 'fieldname','value' => 'cphelptext', 'operator' => vB_dB_Query::OPERATOR_EQ),
						array('field' => 'varname', 'value' => $oldphrasename . "_text", 'operator' => vB_dB_Query::OPERATOR_EQ)
					)
				)
			);
		}

		// update phrase title contents for master language
		if ($vbulletin->GPC['orig']['title'] != $vbulletin->GPC['title'])
		{
			$assertor->assertQuery('replaceIntoPhrases',
					array(
						'text' => $vbulletin->GPC['title'],
						'languageid' => $languageid,
						'varname' => $newphrasename . '_title',
						'product' => $vbulletin->GPC['help']['product'],
						'enteredBy' => $vbulletin->userinfo['username'],
						'dateline' => vB::getRequest()->getTimeNow(),
						'version' => $product_version,
						'fieldname' => 'cphelptext',
					)
			);
		}
		else if ($vbulletin->GPC['orig']['product'] != $vbulletin->GPC['help']['product'])
		{
			// haven't changed the title, but we changed the product,
			// so we need to reflect that
			$assertor->assertQuery('vBForum:phrase',
					array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
						'product' => $vbulletin->GPC['help']['product'],
						'username' => $vbulletin->userinfo['username'],
						'dateline' => vB::getRequest()->getTimeNow(),
						'version' => $product_version,
						vB_dB_Query::CONDITIONS_KEY => array(
							array('field' => 'fieldname','value'=>'cphelptext', 'operator'=> vB_dB_Query::OPERATOR_EQ),
							array('field' => 'varname', 'value'=>$newphrasename . "_title", 'operator'=> vB_dB_Query::OPERATOR_EQ)
						)
					)
			);
		}

		// update phrase text contents for master language
		if ($vbulletin->GPC['orig']['text'] != $vbulletin->GPC['text'])
		{
			$assertor->assertQuery('replaceIntoPhrases',
					array(
						'text' => $vbulletin->GPC['text'],
						'languageid' => $languageid,
						'varname' => $newphrasename . '_text',
						'product' => $vbulletin->GPC['help']['product'],
						'enteredBy' => $vbulletin->userinfo['username'],
						'dateline' => vB::getRequest()->getTimeNow(),
						'version' => $product_version,
						'fieldname' => 'cphelptext',
					)
			);
		}
		else if ($vbulletin->GPC['orig']['product'] != $vbulletin->GPC['help']['product'])
		{
			// haven't changed the text, but we changed the product, so we need to reflect that
			$assertor->assertQuery('vBForum:phrase',
				[
					vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
					'product' => $vbulletin->GPC['help']['product'],
					'username' => $vbulletin->userinfo['username'],
					'dateline' => vB::getRequest()->getTimeNow(),
					'version' => $product_version,
					vB_dB_Query::CONDITIONS_KEY => [
						'fieldname' => 'cphelptext',
						'varname' => $newphrasename . '_text',
					]
				]
			);
		}
	}
	else // insert
	{
		$sql = $assertor->assertQuery('vBForum:adminhelp',
			[
				vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
				'script' => $vbulletin->GPC['help']['script'],
				'action' => $vbulletin->GPC['help']['action'],
				'optionname' => $vbulletin->GPC['help']['optionname']
			]
		);

		if ($sql AND $sql->valid())
		{ // error message, this already exists
			// why phrase when its only available in debug mode and its meant for us?
			print_cp_message('This help item already exists.');
		}

		// insert help item
		$res = $assertor->assertQuery('vBForum:adminhelp',
			[
				vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_INSERT,
				'script' => $vbulletin->GPC['help']['script'],
				'action' => $vbulletin->GPC['help']['action'],
				'optionname' => $vbulletin->GPC['help']['optionname'],
				'displayorder'=> $vbulletin->GPC['help']['displayorder'],
				'volatile' => $vbulletin->GPC['help']['volatile'],
				'product' => $vbulletin->GPC['help']['product']
			]
		);

		// insert new phrases
		$assertor->assertQuery('vBForum:phrase',
			[
				vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_MULTIPLEINSERT,
				vB_dB_Query::FIELDS_KEY => ['languageid', 'fieldname', 'varname', 'text', 'product', 'username', 'dateline', 'version'],
				vB_dB_Query::VALUES_KEY => [
					[$languageid, 'cphelptext', $newphrasename . '_title', $vbulletin->GPC['title'], $vbulletin->GPC['help']['product'], $vbulletin->userinfo['username'], vB::getRequest()->getTimeNow(), $product_version],
					[$languageid, 'cphelptext', $newphrasename . '_text', $vbulletin->GPC['text'], $vbulletin->GPC['help']['product'], $vbulletin->userinfo['username'], vB::getRequest()->getTimeNow(), $product_version]
				]
			]
		);
	}

	if (defined('DEV_AUTOEXPORT') AND DEV_AUTOEXPORT)
	{
		require_once(DIR . '/includes/functions_filesystemxml.php');
		autoexport_write_help([$vbulletin->GPC['orig']['product'] ?? '',	$vbulletin->GPC['help']['product']]);
	}

	print_stop_message2(['saved_topic_x_successfully', $vbulletin->GPC['title']], 'help',
		['do' => 'manage', 'script' => $vbulletin->GPC['help']['script']]);
}

// ############################### start confirmation for deleting a help topic ##############
if ($_REQUEST['do'] == 'delete')
{
	$vbulletin->input->clean_array_gpc('r', [
		'adminhelpid' => vB_Cleaner::TYPE_INT,
		'script'	=> vB_Cleaner::TYPE_STR,
	]);

	print_delete_confirmation('adminhelp', $vbulletin->GPC['adminhelpid'], 'help',
		'dodelete', 'topic', ['script' => $vbulletin->GPC['script']]);
}

// ############################### start actually deleting the help topic ##############
if ($_POST['do'] == 'dodelete')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'adminhelpid' => vB_Cleaner::TYPE_INT,
		'script'	=> vB_Cleaner::TYPE_STR,
	));

	$help = $assertor->assertQuery('vBForum:adminhelp', array('adminhelpid' => $vbulletin->GPC['adminhelpid']));

	if ($help AND $help->valid())
	{
		$result = $help->current();
		$assertor->assertQuery('vBForum:adminhelp', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_DELETE, 'adminhelpid' => $vbulletin->GPC['adminhelpid']));

		// delete associated phrases
		$phrasename = $vbulletin->db->escape_string(fetch_help_phrase_short_name($result));
		$assertor->assertQuery('vBForum:phrase',
				array(
					vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_DELETE,
					'fieldname'           => 'cphelptext',
					'varname'             => array(
						$phrasename . '_title',
						$phrasename . '_text'
					)
		));

		// update language records
		require_once(DIR . '/includes/adminfunctions_language.php');
		build_language();

		if (defined('DEV_AUTOEXPORT') AND DEV_AUTOEXPORT)
		{
			require_once(DIR . '/includes/functions_filesystemxml.php');
			autoexport_write_help($result['product']);
		}
	}

	//redirect back to the same script unless this is the last item
	$params = array('do' => 'manage');
	$script = $vbulletin->GPC['script'];
	if($script)
	{
		$count = $assertor->getRow('vBForum:adminhelp', array(
			vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_COUNT,
			'script' => $script
		));

		if($count['count'])
		{
			$params['script'] = $script;
		}
	}

	print_stop_message2('deleted_topic_successfully', 'help', $params);
}

// ############################### start list of existing help topics ##############
if ($_REQUEST['do'] == 'manage')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'script'	=> vB_Cleaner::TYPE_STR
	));

	// query phrases
	$helpphrase = array();
	$phrases = $assertor->assertQuery('vBForum:phrase',
		array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT, 'fieldname' => 'cphelptext')
	);
	if ($phrases AND $phrases->valid())
	{
		foreach($phrases AS $phrase)
		{
			$helpphrase["$phrase[varname]"] = $phrase['text'];
		}
	}

	// query scripts
	$scripts = array();
	$getscripts = $assertor->assertQuery('vBForum:getDistinctScriptHelp');
	if ($getscripts AND $getscripts->valid())
	{
		foreach($getscripts AS $getscript)
		{
			$scripts["$getscript[script]"] = "$getscript[script].php";
		}
	}

	// query topics
	$topics = array();
	$conditions = array();
	if ($vbulletin->GPC['script'])
	{
		$conditions['script'] = $vbulletin->GPC['script'];
	}
	$gettopics = $assertor->assertQuery('vBForum:adminhelp',
		$conditions,
		array('field' => 'script', 'direction' => vB_dB_Query::SORT_ASC)
	);

	if ($gettopics AND $gettopics->valid())
	{
		foreach($gettopics AS $gettopic)
		{
			$topics["$gettopic[script]"][] = $gettopic;
		}
	}

	// build the form
	print_form_header('admincp/help', 'manage', false, true, 'helpform' ,'90%', '', true, 'get');
	print_table_header($vbphrase['topic_manager'], 5);
	$description = '<div align="center">' . $vbphrase['script'] .
		': <select name="script" tabindex="1" onchange="this.form.submit()" class="bginput"><option value="">' .
		$vbphrase['all_scripts_ghelp_faq'] . '</option>' . construct_select_options($scripts, $vbulletin->GPC['script']) .
		'</select> <input type="submit" class="button" value="' . $vbphrase['go'] . '" tabindex="1" /></div>';
	print_description_row($description, 0, 5, 'thead');

	foreach($topics AS $script => $scripttopics)
	{
		print_table_header($script . '.php', 5);
		print_cells_row(
			array(
				$vbphrase['action'],
				$vbphrase['option'],
				$vbphrase['title'],
				$vbphrase['order_by_gcpglobal'],
				''
			), 1, 0, -5
		);

		foreach($scripttopics AS $topic)
		{
			$editurl = 'help.php?do=edit&adminhelpid=' . $topic['adminhelpid'];
			$deleteurl = 'help.php?do=delete&adminhelpid=' . $topic['adminhelpid'];
			if($script)
			{
				$deleteurl .= '&script=' . $vbulletin->GPC['script'];
			}

			$cells =	array(
				'<span class="smallfont">' . $topic['action'] . '</span>',
				'<span class="smallfont">' . $topic['optionname'] . '</span>',
				'<span class="smallfont"><b>' . $helpphrase[fetch_help_phrase_short_name($topic, '_title')] . '</b></span>',
				'<span class="smallfont">' . $topic['displayorder'] . '</span>',
				'<span class="smallfont">' .
					construct_link_code($vbphrase['edit'], htmlspecialchars($editurl)) .
					construct_link_code($vbphrase['delete'], htmlspecialchars($deleteurl)) .
				'</span>',
			);
			print_cells_row($cells, 0, 0, -5);
		}
	}

	print_table_footer();
}

print_cp_footer();

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 115374 $
|| #######################################################################
\*=========================================================================*/
