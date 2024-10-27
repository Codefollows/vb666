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
define('CVS_REVISION', '$RCSfile$ - $Revision: 110630 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
global $phrasegroups, $specialtemplates, $vbphrase, $vbulletin;
//the profilefiled is for the types ... this is awkward but don't want to duplicate
$phrasegroups = ['cphome', 'profilefield', 'prefix', 'prefixadmin'];

// ########################## REQUIRE BACK-END ############################
require_once(dirname(__FILE__) . '/global.php');

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!vB::getUserContext()->hasAdminPermission('canadminforums'))
{
	print_cp_no_permission();
}

// ############################# LOG ACTION ###############################
log_admin_action();

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

print_cp_header($vbphrase['custom_node_field_manager']);

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'list';
}

$types = [
	'input'           => $vbphrase['single_line_text_box'],
	'textarea'        => $vbphrase['multiple_line_text_box'],
	'radio'           => $vbphrase['single_selection_radio_buttons'],
	'select'          => $vbphrase['single_selection_menu'],
	'select_multiple' => $vbphrase['multiple_selection_menu'],
	'checkbox'        => $vbphrase['multiple_selection_checkbox']
];


// ########################################################################

if ($_POST['do'] == 'killfield')
{
	$vbulletin->input->clean_array_gpc('p', [
		'fieldid' => vB_Cleaner::TYPE_UINT,
	]);

	$nodefieldApi = vB_Api::instance('nodefield');
	$result = $nodefieldApi->deleteField($vbulletin->GPC['fieldid']);
	print_stop_message_on_api_error($result);
	print_stop_message2('node_field_deleted', 'nodefield', ['do'=>'list']);
}

if ($_REQUEST['do'] == 'deletefield')
{
	$vbulletin->input->clean_array_gpc('r', [
		'fieldid' => vB_Cleaner::TYPE_UINT,
	]);

	$nodefieldApi = vB_Api::instance('nodefield');

	//swap the message on error.
	$field = $nodefieldApi->getField($vbulletin->GPC['fieldid']);
	if(isset($field['errors']))
	{
		print_stop_message2(['could_not_find', '<b>nodefield</b>', 'nodefieldid', $vbulletin->GPC['fieldid']]);
	}
	$field = $field['field'];

	$description = construct_phrase(
		$vbphrase['are_you_sure_want_to_delete_nodefield_x'],
		$field['name'],
		'nodefieldid',
		$field['nodefieldid'],
	);

	$header = construct_phrase($vbphrase['confirm_deletion_x'], htmlspecialchars_uni($field['name']));
	$hidden = ['fieldid' => $field['nodefieldid']];
	print_confirmation($vbphrase, 'nodefield', 'killfield', $description, $hidden, $header);
}

// ########################################################################

if ($_POST['do'] == 'savefield')
{
	$vbulletin->input->clean_array_gpc('p', [
		'fieldid' => vB_Cleaner::TYPE_UINT,
		'categoryid' => vB_Cleaner::TYPE_UINT,
		'name' => vB_Cleaner::TYPE_STR,
		'title' => vB_Cleaner::TYPE_STR,
		'displayorder' => vB_Cleaner::TYPE_UINT,
		'type' => vB_Cleaner::TYPE_STR,
		'required' => vB_Cleaner::TYPE_BOOL,
	]);

	$nodefieldApi = vB_Api::instance('nodefield');

	$data = [
		'nodefieldid' => $vbulletin->GPC['fieldid'],
		'nodefieldcategoryid' => $vbulletin->GPC['categoryid'],
		'name' => $vbulletin->GPC['name'],
		'title' => $vbulletin->GPC['title'],
		'displayorder' => $vbulletin->GPC['displayorder'],
		'type' => $vbulletin->GPC['type'],
		'required' => $vbulletin->GPC['required'],
	];
	$result = $nodefieldApi->saveField($data);
	print_stop_message_on_api_error($result);

	print_stop_message2('node_field_saved', 'nodefield', ['do'=>'list']);
}

// ########################################################################
//these actions are 100% the same and we might want to collapse them into just edit
if ($_REQUEST['do'] == 'addfield' OR $_REQUEST['do'] == 'editfield')
{
	$vbulletin->input->clean_array_gpc('r', [
		'fieldid' => vB_Cleaner::TYPE_UINT,
		'categoryid' => vB_Cleaner::TYPE_UINT,
	]);

	$nodefieldApi = vB_Api::instance('nodefield');
	$phraseApi = vB_Api::instance('phrase');

	print_form_header2('admincp/nodefield', 'savefield');
	print_table_start2();

	if ($vbulletin->GPC['fieldid'])
	{
		$field = $nodefieldApi->getField($vbulletin->GPC['fieldid']);
		print_stop_message_on_api_error($field);
		$field = $field['field'];

		//we don't want the translated value here if it exists
		$basephrase = $phraseApi->getBasePhrases([$field['titlephrase']]);
		$field['title'] = htmlspecialchars_uni(reset($basephrase['phrases']));

		$trans_link = '<dfn>' . construct_translation_link($vbphrase['translations'], 'prefix', $field['titlephrase']) . '</dfn>';
		print_table_header($vbphrase['edit_node_field']);
		construct_hidden_code('fieldid', $field['nodefieldid']);
	}
	else
	{
		$field = [
			'nodefieldid' => '',
			'nodefieldcategoryid' => $vbulletin->GPC['categoryid'],
			'name' => '',
			'title' => '',
			'displayorder' => 10,
			'type' => 'input',
			'required' => 0,
		];
		$trans_link = '';

		print_table_header($vbphrase['add_node_field']);
	}

	$categories = $nodefieldApi->getCategoryList();
	print_stop_message_on_api_error($categories);
	$categories = array_column($categories['categorylist'], 'titlephrase', 'nodefieldcategoryid');
	$categories = array_map(function($x) use($vbphrase) {return htmlspecialchars_uni($vbphrase[$x]);}, $categories);
	print_select_row($vbphrase['node_field_category'], 'categoryid', $categories, $field['nodefieldcategoryid']);

	//this is a subset of the types, but this is what we are supporting for now.
	$selecttypes = [
		'input'           => $types['input'],
		'textarea'        => $types['textarea'],
	];
	print_select_row($vbphrase['field_type'], 'type', $selecttypes, $field['type']);
	print_input_row($vbphrase['name'] . '<dfn>' . $vbphrase['alphanumeric_note'] . '</dfn>', 'name', $field['name']);
	print_input_row($vbphrase['title'] . $trans_link , 'title', $field['title']);
	print_yes_no_row($vbphrase['required'], 'required', $field['required']);
	print_input_row($vbphrase['display_order'], 'displayorder', $field['displayorder']);
	print_table_default_footer($vbphrase['save']);
}

// ########################################################################

if ($_POST['do'] == 'killcategory')
{
	$vbulletin->input->clean_array_gpc('p', [
		'categoryid' => vB_Cleaner::TYPE_UINT,
	]);

	$nodefieldApi = vB_Api::instance('nodefield');
	$result = $nodefieldApi->deleteCategory($vbulletin->GPC['categoryid']);
	print_stop_message_on_api_error($result);
	print_stop_message2('node_field_category_deleted', 'nodefield', ['do'=>'list']);
}

// ########################################################################

if ($_REQUEST['do'] == 'deletecategory')
{
	$vbulletin->input->clean_array_gpc('r', [
		'categoryid' => vB_Cleaner::TYPE_UINT,
	]);

	$nodefieldApi = vB_Api::instance('nodefield');

	//swap the message on error.
	$category = $nodefieldApi->getCategory($vbulletin->GPC['categoryid']);
	if(isset($category['errors']))
	{
		print_stop_message2(['could_not_find', '<b>nodefieldcategory</b>', 'nodefieldcategoryid', $vbulletin->GPC['categoryid']]);
	}
	$category = $category['category'];

	$description = construct_phrase(
		$vbphrase['are_you_sure_want_to_delete_nodefieldcategory_x'],
		$category['name'],
		'nodefieldcategoryid',
		$category['nodefieldcategoryid']
	);

	$header = construct_phrase($vbphrase['confirm_deletion_x'], htmlspecialchars_uni($category['name']));
	$hidden = ['categoryid' => $category['nodefieldcategoryid']];
	print_confirmation($vbphrase, 'nodefield', 'killcategory', $description, $hidden, $header);
}

// ########################################################################

if ($_POST['do'] == 'savecategory')
{
	$vbulletin->input->clean_array_gpc('p', [
		'categoryid' => vB_Cleaner::TYPE_UINT,
		'name' => vB_Cleaner::TYPE_STR,
		'title' => vB_Cleaner::TYPE_STR,
		'displayorder' => vB_Cleaner::TYPE_UINT,
		'nodeids' => vB_Cleaner::TYPE_ARRAY_UINT,
	]);

	$nodefieldApi = vB_Api::instance('nodefield');

	$data = [
		'nodefieldcategoryid' => $vbulletin->GPC['categoryid'],
		'name' => $vbulletin->GPC['name'],
		'title' => $vbulletin->GPC['title'],
		'displayorder' => $vbulletin->GPC['displayorder'],
		'channelids' => $vbulletin->GPC['nodeids'],
	];

	$result = $nodefieldApi->saveCategory($data);
	print_stop_message_on_api_error($result);

	print_stop_message2('node_field_category_saved', 'nodefield', ['do'=>'list']);
}

// ########################################################################

if ($_REQUEST['do'] == 'addcategory' OR $_REQUEST['do'] == 'editcategory')
{
	$vbulletin->input->clean_array_gpc('r', [
		'categoryid' => vB_Cleaner::TYPE_UINT
	]);

	$nodefieldApi = vB_Api::instance('nodefield');
	$phraseApi = vB_Api::instance('phrase');

	if ($vbulletin->GPC['categoryid'])
	{
		$category = $nodefieldApi->getCategory($vbulletin->GPC['categoryid']);
		print_stop_message_on_api_error($category);
		$category = $category['category'];

		//we don't want the translated value here if it exists
		$basephrase = $phraseApi->getBasePhrases([$category['titlephrase']]);
		$category['title'] = htmlspecialchars_uni(reset($basephrase['phrases']));

		//we'll probably want this eventually. But not for now.
		$trans_link = '<dfn>' . construct_translation_link($vbphrase['translations'], 'prefix', $category['titlephrase']) . '</dfn>';
		$formtitle = $vbphrase['edit_node_field_category'];

		construct_hidden_code('categoryid', $category['nodefieldcategoryid']);
	}
	else
	{
		$category = [
			'nodefieldcategoryid' => 0,
			'name' => '',
			'title' => '',
			'displayorder' => 10,
			'channelids' => [],
		];
		$trans_link = '';
		$formtitle = $vbphrase['add_custom_node_field_category'];
	}

	print_form_header2('admincp/nodefield', 'savecategory');
	print_table_start2();

	print_table_header($formtitle);
	print_input_row($vbphrase['name'] . '<dfn>' . $vbphrase['alphanumeric_note'] . '</dfn>', 'name', $category['name']);
	print_input_row($vbphrase['title'] . $trans_link,	'title', $category['title']);
	print_input_row($vbphrase['display_order'], 'displayorder', $category['displayorder']);

	print_channel_chooser($vbphrase['use_node_fields_in_these_channels'], 'nodeids[]', $category['channelids'], '', false, true);
	print_table_default_footer($vbphrase['save']);
}

// ########################################################################

if ($_POST['do'] == 'displayorder')
{
	$vbulletin->input->clean_array_gpc('p', [
		'field_order' => vB_Cleaner::TYPE_ARRAY_UINT,
		'category_order' => vB_Cleaner::TYPE_ARRAY_UINT,
	]);

	$nodefieldApi = vB_Api::instance('nodefield');
	foreach ($vbulletin->GPC['field_order'] AS $fieldid => $displayorder)
	{
		$result = $nodefieldApi->saveField([
			'nodefieldid' => $fieldid,
			'displayorder' => $displayorder,
		]);
		print_stop_message_on_api_error($result);
	}

	foreach ($vbulletin->GPC['category_order'] AS $categorydid => $displayorder)
	{
		$result = $nodefieldApi->saveCategory([
			'nodefieldcategoryid' => $categorydid,
			'displayorder' => $displayorder,
		]);
		print_stop_message_on_api_error($result);
	}

	print_stop_message2('saved_display_order_successfully', 'nodefield', ['do'=>'list']);
}

// ########################################################################

if ($_REQUEST['do'] == 'list')
{
	$assertor = vB::getDbAssertor();
	$nodefieldApi = vB_Api::instance('nodefield');

	$categories = $nodefieldApi->getCategoryList();
	print_stop_message_on_api_error($categories);
	$categories = $categories['categorylist'];

	$fields = $nodefieldApi->getFieldList();
	print_stop_message_on_api_error($fields);
	$fields = $fields['fieldlist'];

	$fieldsbycat = [];
	foreach($fields AS $field)
	{
		$fieldsbycat[$field['nodefieldcategoryid']][] = $field;
	}
	unset($fields);

	$colspan = 4;
	print_form_header2('admincp/nodefield', 'displayorder');
	print_table_start();
	print_table_header($vbphrase['custom_node_fields'], $colspan);

	if (!$categories)
	{
		print_description_row($vbphrase['no_node_field_categories_defined'], false, $colspan, '', 'center');
	}
	else
	{
		// display existing sets
		foreach ($categories AS $category)
		{
			$categoryid = $category['nodefieldcategoryid'];
			$categorytitle = htmlspecialchars_uni($vbphrase[$category['titlephrase']]);

			$cells = [];
			$cells[] = $categorytitle;
			$cells[] = '';
			$cells[] = construct_input('text', 'category_order[' . $categoryid . ']', ['size' => 3, 'value' => $category['displayorder']]);
			$cells[] = '<div class="normal">' .
				construct_link_code2($vbphrase['add_node_field'], get_admincp_url('nodefield', ['do' => 'addfield', 'categoryid' => $categoryid])) .
				construct_link_code2($vbphrase['edit'], get_admincp_url('nodefield', ['do' => 'editcategory', 'categoryid' => $categoryid])) .
				construct_link_code2($vbphrase['delete'], get_admincp_url('nodefield', ['do' => 'deletecategory', 'categoryid' => $categoryid])) .
				'</div>';

			print_cells_row2($cells, 'thead');

			$fields = $fieldsbycat[$categoryid] ?? [];
			if (!$fields)
			{
				print_description_row(construct_phrase($vbphrase['no_node_fields_defined'], $categorytitle), false, $colspan, '', 'center');
			}
			else
			{
				foreach ($fields AS $field)
				{
					$fieldid = $field['nodefieldid'];
					$fieldtitle = htmlspecialchars_uni($vbphrase[$field['titlephrase']]);

					$cells = [];
					$cells[] = $fieldtitle;
					$cells[] = $types[$field['type']];
					$cells[] = construct_input('text', 'field_order[' . $fieldid . ']', ['size' => 3, 'value' => $field['displayorder']]);
					$cells[] = '<div class="smallfont">' .
						construct_link_code2($vbphrase['edit'], get_admincp_url('nodefield', ['do' => 'editfield', 'fieldid' => $fieldid])) .
						construct_link_code2($vbphrase['delete'], get_admincp_url('nodefield', ['do' => 'deletefield', 'fieldid' => $fieldid])) .
						'</div>';

					print_cells_row2($cells);
				}
			}
		}
	}

	$buttons = [
		construct_submit_button($vbphrase['save_display_order']),
		construct_link_button($vbphrase['add_custom_node_field_category'] , get_admincp_url('nodefield', ['do' => 'addcategory'])),
	];

	print_table_button_footer($buttons, $colspan);
}

print_cp_footer();

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 110630 $
|| #######################################################################
\*=========================================================================*/
