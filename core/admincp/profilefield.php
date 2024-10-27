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
define('CVS_REVISION', '$RCSfile$ - $Revision: 116202 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
global $phrasegroups, $specialtemplates, $vbphrase, $vbulletin;
$phrasegroups = ['profilefield', 'cprofilefield'];
$specialtemplates = [];

// ########################## REQUIRE BACK-END ############################
require_once(dirname(__FILE__) . '/global.php');
require_once(DIR . '/includes/adminfunctions_profilefield.php');

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!can_administer('canadminusers'))
{
	print_cp_no_permission();
}

$vbulletin->input->clean_array_gpc('r', [
	'profilefieldid' => vB_Cleaner::TYPE_UINT,
]);

// ############################# LOG ACTION ###############################
log_admin_action(($vbulletin->GPC['profilefieldid'] != 0 ? "profilefield id = " . $vbulletin->GPC['profilefieldid'] : ''));

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

print_cp_header($vbphrase['user_profile_field_manager_gprofilefield']);

$types = [
	'input'           => $vbphrase['single_line_text_box'],
	'textarea'        => $vbphrase['multiple_line_text_box'],
	'radio'           => $vbphrase['single_selection_radio_buttons'],
	'select'          => $vbphrase['single_selection_menu'],
	'select_multiple' => $vbphrase['multiple_selection_menu'],
	'checkbox'        => $vbphrase['multiple_selection_checkbox']
];

$category_locations = [
	''                        => $vbphrase['only_in_about_me_tab'],
	'profile_tabs_first'      => $vbphrase['main_column_first_tab'],
	'profile_tabs_last'       => $vbphrase['main_column_last_tab'],
	'profile_sidebar_first'   => $vbphrase['blocks_column_first'],
	'profile_sidebar_stats'   => $vbphrase['blocks_column_after_mini_stats'],
	'profile_sidebar_friends' => $vbphrase['blocks_column_after_friends'],
	'profile_sidebar_albums'  => $vbphrase['blocks_column_after_albums'],
	'profile_sidebar_groups'  => $vbphrase['blocks_column_after_groups'],
	'profile_sidebar_last'    => $vbphrase['blocks_column_last']
];

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'modify';
}

// #############################################################################
// functions

//this should be moved to the user api class.  Declining because I think we should
//consolidate this with a profile field load and eat the overhead of getting the whole
//thing but I haven't figured that part out yet
function profilefield_get_boxdata($id)
{
	$db = vB::getDbAssertor();

	$boxdata = $db->getRow('vBForum:profilefield', [
		vB_dB_Query::COLUMNS_KEY => ['data', 'type'],
		'profilefieldid' => $id
	]);

	$boxdata['data'] = vb_unserialize_array($boxdata['data']);
	return $boxdata;
}

// #############################################################################
if ($_REQUEST['do'] == 'deletecat')
{
	$vbulletin->input->clean_array_gpc('r', [
		'profilefieldcategoryid' => vB_Cleaner::TYPE_UINT
	]);

	if ($pfc = $vbulletin->db->query_first("
		SELECT pfc.*,
			COUNT(profilefieldid) AS profilefieldscount
		FROM " . TABLE_PREFIX . "profilefieldcategory AS pfc
		LEFT JOIN " . TABLE_PREFIX . "profilefield AS pf ON(pf.profilefieldcategoryid = pfc.profilefieldcategoryid)
		WHERE pfc.profilefieldcategoryid = " . $vbulletin->GPC['profilefieldcategoryid'] . "
		GROUP BY pfc.profilefieldcategoryid
	"))
	{
		print_form_header('admincp/profilefield', 'removecat');
		construct_hidden_code('profilefieldcategoryid', $pfc['profilefieldcategoryid']);
		print_table_header($vbphrase['confirm_deletion_gcpglobal']);
		print_description_row(construct_phrase(
			$vbphrase['are_you_sure_you_want_to_delete_user_profile_field_category_x'],
			$vbphrase['category' . $pfc['profilefieldcategoryid'] . '_title'],
			$pfc['profilefieldscount'],
			$vbphrase['uncategorized_gprofilefield']
		));
		print_submit_row($vbphrase['yes'], '', 2, $vbphrase['no']);
	}
	else
	{
		$_REQUEST['do'] = 'modifycats';
	}
}

// #############################################################################
if ($_POST['do'] == 'removecat')
{
	$vbulletin->input->clean_array_gpc('p', [
		'profilefieldcategoryid' => vB_Cleaner::TYPE_UINT
	]);

	$assertor = vB::getDbAssertor();

	$pfc = $assertor->getRow('vBForum:profilefieldcategory', ['profilefieldcategoryid' => $vbulletin->GPC['profilefieldcategoryid']]);
	if ($pfc)
	{
		$assertor->update('vBForum:profilefield', ['profilefieldcategoryid' => 0], ['profilefieldcategoryid' => $pfc['profilefieldcategoryid']]);
		$assertor->delete('vBForum:profilefieldcategory', ['profilefieldcategoryid' => $pfc['profilefieldcategoryid']]);

		vB_Library::instance('phrase')->deleteByVarname([
			'category' . $pfc['profilefieldcategoryid'] . '_title',
			'category' . $pfc['profilefieldcategoryid'] . '_desc',
		]);

		// redirect to category list page
		print_stop_message('deleted_profile_field_category_successfully', get_admincp_url('profilefield', ['do' => 'modifycats']));
	}
	else
	{
		$_REQUEST['do'] = 'modifycats';
	}
}

// #############################################################################
if ($_POST['do'] == 'updatecat')
{
	$vbulletin->input->clean_array_gpc('p', [
		'profilefieldcategoryid'	=> vB_Cleaner::TYPE_UINT,
		'displayorder'						=> vB_Cleaner::TYPE_UINT,
		'title'										=> vB_Cleaner::TYPE_NOHTML,
		'location'								=> vB_Cleaner::TYPE_STR,
		'desc'										=> vB_Cleaner::TYPE_STR,
		'allowprivacy'						=> vB_Cleaner::TYPE_BOOL
	]);

	if (empty($vbulletin->GPC['title']))
	{
		print_stop_message2('please_complete_required_fields');
	}

	$profilefieldcategoryid = $vbulletin->GPC['profilefieldcategoryid'];

	$assertor = vB::getDbAssertor();
	$data = [
		'displayorder' => $vbulletin->GPC['displayorder'],
		'location' => $vbulletin->GPC['location'],
		'allowprivacy' => intval($vbulletin->GPC['allowprivacy']),
	];

	if ($profilefieldcategoryid)
	{
		$assertor->update('vBForum:profilefieldcategory', $data, ['profilefieldcategoryid' => $profilefieldcategoryid]);
	}
	else
	{
		$profilefieldcategoryid = $assertor->insert('vBForum:profilefieldcategory', $data);
	}

	$phraseLib = vB_Library::instance('phrase');
	$phraseLib->saveCustom('vbulletin', 'cprofilefield', 'category' . $profilefieldcategoryid . '_title', $vbulletin->GPC['title'], true);
	$phraseLib->saveCustom('vbulletin', 'cprofilefield', 'category' . $profilefieldcategoryid . '_desc', $vbulletin->GPC['desc']);

	// redirect to category list page
	print_stop_message(['saved_x_successfully', $vbulletin->GPC['title']], get_admincp_url('profilefield', ['do' => 'modifycats']));
}

// #############################################################################
if ($_REQUEST['do'] == 'addcat' OR $_REQUEST['do'] == 'editcat')
{
	$vbulletin->input->clean_array_gpc('r', [
		'profilefieldcategoryid' => vB_Cleaner::TYPE_UINT
	]);

	print_form_header2('admincp/profilefield', 'updatecat');
	print_table_start2();

	$pfc = [];
	if ($_REQUEST['do'] == 'editcat' AND $vbulletin->GPC['profilefieldcategoryid'])
	{
		$assertor = vB::getDbAssertor();
		$pfc = $assertor->getRow('vBForum:profilefieldcategory', ['profilefieldcategoryid' => $vbulletin->GPC['profilefieldcategoryid']]);
	}

	if ($pfc)
	{
		print_table_header($vbphrase['edit_user_profile_field_category'] .
			' <span class="normal">' . $vbphrase['category' . $pfc['profilefieldcategoryid'] . '_title'] .
			" (id $pfc[profilefieldcategoryid])</span>"
		);
		construct_hidden_code('profilefieldcategoryid', $pfc['profilefieldcategoryid']);

		$titlephrase = 'category' . $pfc['profilefieldcategoryid'] . '_title';
		$descphrase = 'category' . $pfc['profilefieldcategoryid'] . '_desc';

		$phraseApi = vB_Api::instance('phrase');
		$basephrases = $phraseApi->getBasePhrases([$titlephrase, $descphrase]);
		print_stop_message_on_api_error($basephrases);
		$pfc['title'] = $basephrases['phrases'][$titlephrase];
		$pfc['desc'] = $basephrases['phrases'][$titlephrase];

		$transLinkTitle = construct_translation_link($vbphrase['translations'], 'cprofilefield', $titlephrase);
		$transLinkDesc = construct_translation_link($vbphrase['translations'], 'cprofilefield', $descphrase);
	}
	else
	{
		print_table_header($vbphrase['add_new_profile_field_category']);

		$pfc = [
			'profilefieldcategoryid' => 0,
			'location' => '',
			'displayorder' => 1,
			'title' => '',
			'desc' => ''
		];

		$transLinkTitle = '';
		$transLinkDesc = '';
	}

	print_input_row(get_text_with_description($vbphrase['title'], $transLinkTitle), 'title', $pfc['title'], false);
	print_textarea_row(get_text_with_description($vbphrase['description_gcpglobal'], $transLinkDesc), 'desc', $pfc['desc']);
	print_input_row($vbphrase['display_order'], 'displayorder', $pfc['displayorder']);
	print_table_default_footer($vbphrase['save']);
}

// #############################################################################
if ($_POST['do'] == 'displayordercats')
{
	$vbulletin->input->clean_array_gpc('p', [
		'order' => vB_Cleaner::TYPE_ARRAY_UINT,
	]);

	if (!empty($vbulletin->GPC['order']))
	{
		$sql = '';
		foreach ($vbulletin->GPC['order'] AS $profilefieldcategoryid => $displayorder)
		{
			$sql .= "WHEN " . intval($profilefieldcategoryid) . " THEN " . intval($displayorder) . "\n";
		}
		$vbulletin->db->query_write("
			UPDATE " . TABLE_PREFIX . "profilefieldcategory
			SET displayorder = CASE profilefieldcategoryid
			$sql ELSE displayorder END
		");

		print_stop_message2('saved_display_order_successfully', 'profilefield', ['do'=>'modifycats']);
	}
	else
	{
		$_REQUEST['do'] = 'modifycats';
	}
}

// #############################################################################
if ($_REQUEST['do'] == 'modifycats')
{
	$pfcs_result = $vbulletin->db->query_read("
		SELECT pfc.*,
			COUNT(profilefieldid) AS profilefieldscount
		FROM " . TABLE_PREFIX . "profilefieldcategory AS pfc
		LEFT JOIN " . TABLE_PREFIX . "profilefield AS pf ON(pf.profilefieldcategoryid = pfc.profilefieldcategoryid)
		GROUP BY pfc.profilefieldcategoryid
		ORDER BY pfc.displayorder
	");

	print_form_header('admincp/profilefield', 'displayordercats');
	print_table_header($vbphrase['user_profile_field_categories_gprofilefield'], 4);

	if ($vbulletin->db->num_rows($pfcs_result))
	{
		print_cells_row([
			'ID',
			$vbphrase['title'],
			$vbphrase['display_order'],
			$vbphrase['controls']
		], true, false, -1);

		while ($pfc = $vbulletin->db->fetch_array($pfcs_result))
		{
			print_cells_row([
				$pfc['profilefieldcategoryid'],
				"<div class=\"smallfont\" style=\"float:" . vB_Template_Runtime::fetchStyleVar('right') . "\"><em>" . construct_phrase($vbphrase['contains_x_fields'], $pfc['profilefieldscount']) . "</em></div>
					<strong>" . $vbphrase['category' . $pfc['profilefieldcategoryid'] . '_title'] . '</strong>
					<dfn>' . $vbphrase['category' . $pfc['profilefieldcategoryid'] . '_desc'] . "</dfn>",
				"<input type=\"text\" name=\"order[$pfc[profilefieldcategoryid]]\" size=\"5\" value=\"$pfc[displayorder]\" class=\"bginput\" tabindex=\"1\" style=\"text-align:" . vB_Template_Runtime::fetchStyleVar('right') . "\" />",
				construct_link_code($vbphrase['edit'], "profilefield.php?" . vB::getCurrentSession()->get('sessionurl') . "do=editcat&amp;profilefieldcategoryid=$pfc[profilefieldcategoryid]") .
					construct_link_code($vbphrase['delete'], "profilefield.php?" . vB::getCurrentSession()->get('sessionurl') . "do=deletecat&amp;profilefieldcategoryid=$pfc[profilefieldcategoryid]")
			], false, false, -1);

		}

		print_submit_row($vbphrase['save_display_order'], '', 4);
	}
	else
	{
		print_description_row($vbphrase['no_user_profile_field_categories_have_been_created'], false, 4);
		print_table_footer();
	}

	echo '<div align="center">' . construct_link_code($vbphrase['add_new_profile_field_category'], 'profilefield.php?' . vB::getCurrentSession()->get('sessionurl') . 'do=addcat') . '</div>';
}

// ###################### Start Update Display Order #######################
if ($_POST['do'] == 'displayorder')
{
	$vbulletin->input->clean_array_gpc('p', [
		'order' => vB_Cleaner::TYPE_ARRAY_UINT,
	]);

	if (!empty($vbulletin->GPC['order']))
	{
		$sql = '';
		foreach ($vbulletin->GPC['order'] AS $_profilefieldid => $displayorder)
		{
			$sql .= "WHEN " . intval($_profilefieldid) . " THEN " . intval($displayorder) . "\n";
		}
		$vbulletin->db->query_write("
			UPDATE " . TABLE_PREFIX . "profilefield
			SET displayorder = CASE profilefieldid
			$sql ELSE displayorder END
		");
		build_profilefield_cache();

		print_stop_message2('saved_display_order_successfully', 'profilefield', ['do'=>'modify']);
	}
	else
	{
		$_REQUEST['do'] = 'modify';
	}
}

// ###################### Start Insert / Update #######################
if ($_POST['do'] == 'update')
{
	$vbulletin->input->clean_array_gpc('p', [
		'type'         => vB_Cleaner::TYPE_STR,
		'profilefield' => vB_Cleaner::TYPE_ARRAY_STR,
		'modifyfields' => vB_Cleaner::TYPE_STR,
		'newtype'      => vB_Cleaner::TYPE_STR,
		'title'        => vB_Cleaner::TYPE_STR,
		'description'  => vB_Cleaner::TYPE_STR
	]);

	$profilefield = $vbulletin->GPC['profilefield'];
	$profilefield['profilefieldid'] = $vbulletin->GPC['profilefieldid'];
	$profilefield['type'] = $vbulletin->GPC['type'];
	$profilefield['newtype'] = $vbulletin->GPC['newtype'];
	$profilefield['title'] = $vbulletin->GPC['title'];
	$profilefield['description'] = $vbulletin->GPC['description'];

	if (isset($profilefield['data']) AND in_array($profilefield['type'], ['select', 'radio', 'checkbox', 'select_multiple']))
	{
		$data = explode("\n", htmlspecialchars_uni($profilefield['data']));
		$data = array_values(array_filter(array_map('trim', $data)));
		$profilefield['data'] = $data;
	}

	$result = vB_Api::instance('user')->saveProfileFieldDefinition($profilefield);

	print_stop_message_on_api_error($result);

	if ($vbulletin->GPC['modifyfields'])
	{
		$args = [
			'do' => 'modifycheckbox',
			'profilefieldid' => $result['profilefieldid'],
		];
	}
	else
	{
		$args = ['do'=>'modify'];
	}

	print_stop_message2(['saved_x_successfully',  htmlspecialchars_uni($vbulletin->GPC['title'])], 'profilefield', $args);
}

// ###################### Start add #######################
if ($_REQUEST['do'] == 'add' OR $_REQUEST['do'] == 'edit')
{
	$vbulletin->input->clean_array_gpc('r', [
		'profilefieldtype' => vB_Cleaner::TYPE_STR,
	]);

	if ($_REQUEST['do'] == 'add')
	{
		$type = $vbulletin->GPC['profilefieldtype'];
		if (!$type)
		{
			print_form_header('admincp/profilefield', 'add');
			print_table_header($vbphrase['add_new_user_profile_field_gprofilefield']);
			print_select_row($vbphrase['profile_field_type'], 'profilefieldtype', $types);
			print_submit_row($vbphrase['continue'], 0);
			print_cp_footer();
			exit;
		}

		$maxprofile = $vbulletin->db->query_first("SELECT COUNT(*) AS count FROM " . TABLE_PREFIX . "profilefield");

		$profilefield = [
			'profilefieldcategoryid' => 0,
			'data' => '',
			'maxlength' => 100,
			'size' => 25,
			'height' => 4,
			'displayorder' => $maxprofile['count'] + 1,
			'required' => 0,
			'editable' => 1,
			'def' => 1,
			'hidden' => 0,
			'searchable' => 1,
			'memberlist' => 1,
			'showonpost' => 0,
			'limit' => 0,
			'perline' => 0,
			'boxheight' => 0,
			'regex' => '',
			'optional' => 0,
		];

		print_form_header('admincp/profilefield', 'update');
		construct_hidden_code('type', $type);
		print_table_header($vbphrase['add_new_user_profile_field_gprofilefield'] . ' <span class="normal">' . $types[$type] . '</span>', 2, 0);

		$titletranslate = '';
		$titlevalue = '';

		$desctranslate = '';
		$descvalue = '';
	}
	else
	{
		$assertor = vB::getDbAssertor();
		$profilefield = $assertor->getRow('vBForum:profilefield', ['profilefieldid' => $vbulletin->GPC['profilefieldid']]);
		$type = $profilefield['type'];

		if ($type == 'select' OR $type == 'radio')
		{
			$profilefield['data'] = implode("\n", unserialize($profilefield['data']));
		}
		$profilefield['limit'] = $profilefield['size'];
		$profilefield['boxheight'] = $profilefield['height'];

		if ($type == 'checkbox')
		{
			echo '<p><b>' . $vbphrase['you_close_before_modifying_checkboxes'] . '</b></p>';
		}

		$titlevarname = 'field' . $profilefield['profilefieldid'] . '_title';
		$descvarname = 'field' . $profilefield['profilefieldid'] . '_desc';

		//don't use the phrase API here because we definitely want language ID 0 and not the board/user default.
		//We probably ought to extend the phrase API/library to handle this case (and better handle custom phrases
		//all around but that's a different issue).
		$conditions = ['languageid' => 0, 'fieldname' => 'cprofilefield', 'varname' => [$titlevarname, $descvarname]];
		$phrases = $assertor->getColumn('phrase', 'text', $conditions , false, 'varname');

		print_form_header('admincp/profilefield', 'update');
		construct_hidden_code('type', $type);
		construct_hidden_code('profilefieldid', $profilefield['profilefieldid']);
		print_table_header(construct_phrase($vbphrase['x_y_id_z'], $vbphrase['user_profile_field'], $phrases[$titlevarname], $profilefield['profilefieldid'] . " - $profilefield[type]"), 2, 0);

		$url = "phrase.php?do=edit&fieldname=cprofilefield&varname=$titlevarname&t=1";
		$titletranslate = '<dfn>' . construct_link_code($vbphrase['translations'], htmlspecialchars($url), 1) . '</dfn>';
		$titlevalue = $phrases[$titlevarname];

		$url = "phrase.php?do=edit&fieldname=cprofilefield&varname=$descvarname&t=1";
		$desctranslate = '<dfn>' . construct_link_code($vbphrase['translations'], htmlspecialchars($url), 1) . '</dfn>';
		$descvalue = $phrases[$descvarname];
	}

	print_input_row($vbphrase['title'] . $titletranslate, 'title', $titlevalue);

	$extra = '';
	if ($type == 'checkbox')
	{
		$extra = '<dfn>' . $vbphrase['choose_limit_choices_add_info'] . '<dfn>';

	}

	print_textarea_row($vbphrase['description_gcpglobal'] . $extra . $desctranslate, 'description', $descvalue);

	$pfcs = [0 => '(' . $vbphrase['uncategorized_gprofilefield'] . ')'];
	$pfcs_result = $assertor->select('vBForum:profilefieldcategory', [], false, ['profilefieldcategoryid']);
	foreach ($pfcs_result AS $row)
	{
		$pfcs[$row['profilefieldcategoryid']] = $vbphrase['category' . $row['profilefieldcategoryid'] . '_title'];
	}

	if (!$pfcs[$profilefield['profilefieldcategoryid']])
	{
		$profilefield['profilefieldcategoryid'] = 0;
	}
	print_radio_row($vbphrase['profile_field_category'], 'profilefield[profilefieldcategoryid]', $pfcs, $profilefield['profilefieldcategoryid']);

	if ($type == 'input')
	{
		print_input_row($vbphrase['default_value_you_may_specify_a_default_registration_value'], 'profilefield[data]', $profilefield['data'], 0);
	}

	if ($type == 'textarea')
	{
		print_textarea_row($vbphrase['default_value_you_may_specify_a_default_registration_value'], 'profilefield[data]', $profilefield['data'], 10, 40, 0);
	}

	if ($type == 'textarea' OR $type == 'input')
	{
		print_input_row($vbphrase['max_length_of_allowed_user_input'], 'profilefield[maxlength]', $profilefield['maxlength']);
		print_input_row($vbphrase['field_length'], 'profilefield[size]', $profilefield['size']);
	}

	if ($type == 'textarea')
	{
		print_input_row($vbphrase['text_area_height'], 'profilefield[height]', $profilefield['height']);
	}

	if ($type == 'select')
	{
		print_textarea_row(construct_phrase($vbphrase['x_enter_the_options_that_the_user_can_choose_from'], $vbphrase['options']), 'profilefield[data]', $profilefield['data'], 10, 40, 0);

		$options = [
			0 => $vbphrase['none'],
			1 => $vbphrase['yes_including_a_blank'],
			2 => $vbphrase['yes_but_no_blank_option']
		];
		print_select_row($vbphrase['set_default_if_yes_first'], 'profilefield[def]', $options,  $profilefield['def']);
	}

	if ($type == 'radio')
	{
		print_textarea_row(construct_phrase($vbphrase['x_enter_the_options_that_the_user_can_choose_from'], $vbphrase['options']), 'profilefield[data]', $profilefield['data'], 10, 40, 0);
		print_yes_no_row($vbphrase['set_default_if_yes_first'], 'profilefield[def]', $profilefield['def']);
	}

	if ($type == 'checkbox')
	{
		print_input_row($vbphrase['limit_selection'], 'profilefield[size]', $profilefield['limit']);
		if ($_REQUEST['do'] == 'add')
		{
			print_textarea_row(construct_phrase($vbphrase['x_enter_the_options_that_the_user_can_choose_from'], $vbphrase['options']) . "<br /><dfn>$vbphrase[note_max_31_options]</dfn>", 'profilefield[data]', '', 10, 40, 0);
		}
		else
		{
			print_label_row($vbphrase['fields'], '<input type="image" src="images/clear.gif"><input type="submit" class="button" value="' . $vbphrase['modify'] . '" tabindex="1" name="modifyfields">');
		}
	}

	if ($type == 'select_multiple')
	{
		print_input_row($vbphrase['limit_selection'], 'profilefield[size]', $profilefield['limit']);
		print_input_row($vbphrase['box_height'], 'profilefield[height]', $profilefield['boxheight']);
		if ($_REQUEST['do'] == 'add')
		{
			print_textarea_row(construct_phrase($vbphrase['x_enter_the_options_that_the_user_can_choose_from'], $vbphrase['options']) . "<br /><dfn>$vbphrase[note_max_31_options]</dfn>", 'profilefield[data]', '', 10);
		}
		else
		{
			print_label_row($vbphrase['fields'], '<input type="image" src="images/clear.gif"><input type="submit" class="button" value="' . $vbphrase['modify'] . '" tabindex="1" name="modifyfields">');
		}
	}

	if ($_REQUEST['do'] == 'edit')
	{
		if ($type == 'input' OR $type == 'textarea')
		{
			$checkboxes = [
				'input' => $vbphrase['single_line_text_box'],
				'textarea' => $vbphrase['multiple_line_text_box'],
			];
			print_radio_row($vbphrase['profile_field_type'], 'newtype', $checkboxes, $type);
		}
		else if ($type == 'checkbox' OR $type == 'select_multiple')
		{
			$checkboxes = [
				'checkbox' => $vbphrase['multiple_selection_checkbox'],
				'select_multiple' => $vbphrase['multiple_selection_menu'],
			];
			print_radio_row($vbphrase['profile_field_type'], 'newtype', $checkboxes, $type);
		}
	}

	print_input_row($vbphrase['display_order'], 'profilefield[displayorder]', $profilefield['displayorder']);

	$options = [
		1 => $vbphrase['yes_at_registration'],
		3 => $vbphrase['yes_always'],
		0 => $vbphrase['no'],
		2 => $vbphrase['no_but_on_register']
	];
	print_select_row($vbphrase['field_required'], 'profilefield[required]', $options, $profilefield['required']);

	$options = [
		1 => $vbphrase['yes'],
		0 => $vbphrase['no'],
		2 => $vbphrase['only_at_registration']
	];
	print_select_row($vbphrase['field_editable_by_user'], 'profilefield[editable]', $options, $profilefield['editable']);

	print_yes_no_row($vbphrase['field_hidden_on_profile'], 'profilefield[hidden]', $profilefield['hidden']);
	print_yes_no_row($vbphrase['field_searchable_on_members_list'], 'profilefield[searchable]', $profilefield['searchable']);

	if ($type != 'textarea')
	{
		print_yes_no_row($vbphrase['show_on_members_list'], 'profilefield[memberlist]', $profilefield['memberlist']);
	}

	print_yes_no_row($vbphrase['show_on_post'], 'profilefield[showonpost]', $profilefield['showonpost']);

	if ($type == 'select' OR $type == 'radio')
	{
		print_table_break();
		print_table_header($vbphrase['optional_input']);
		print_yes_no_row($vbphrase['allow_user_to_input_their_own_value_for_this_option'], 'profilefield[optional]', $profilefield['optional']);
		print_input_row($vbphrase['max_length_of_allowed_user_input'], 'profilefield[maxlength]', $profilefield['maxlength']);
		print_input_row($vbphrase['field_length'], 'profilefield[size]', $profilefield['size']);
	}

	if ($type != 'select_multiple' AND $type != 'checkbox')
	{
		print_input_row($vbphrase['regular_expression_require_match_gprofilefield'], 'profilefield[regex]', $profilefield['regex']);
	}

	print_submit_row($vbphrase['save']);
}

// ###################### Start Rename Checkbox Data #######################
if ($_REQUEST['do'] == 'renamecheckbox')
{
	$vbulletin->input->clean_array_gpc('r', [
		'id' => vB_Cleaner::TYPE_UINT,
	]);

	$boxdata = profilefield_get_boxdata($vbulletin->GPC['profilefieldid']);
	$data = $boxdata['data'];
	foreach ($data AS $index => $value)
	{
		if ($index + 1 == $vbulletin->GPC['id'])
		{
			$oldfield = $value;
			break;
		}
	}

	print_form_header('admincp/profilefield', 'dorenamecheckbox');
	construct_hidden_code('profilefieldid', $vbulletin->GPC['profilefieldid']);
	construct_hidden_code('id', $vbulletin->GPC['id']);
	print_table_header($vbphrase['rename_gprofilefield']);
	print_input_row($vbphrase['name'], 'newfield', $oldfield);
	print_submit_row($vbphrase['save']);

}

// ###################### Start Rename Checkbox Data #######################
if ($_POST['do'] == 'dorenamecheckbox')
{
	$vbulletin->input->clean_array_gpc('p', [
		'newfield' => vB_Cleaner::TYPE_NOHTML,
		'id'       => vB_Cleaner::TYPE_UINT
	]);

	if (!empty($vbulletin->GPC['newfield']))
	{
		$boxdata = profilefield_get_boxdata($vbulletin->GPC['profilefieldid']);
		$data = $boxdata['data'];
		foreach ($data AS $index => $value)
		{
			if (strtolower($value) == strtolower($vbulletin->GPC['newfield']))
			{
				print_stop_message2(['this_is_already_option_named_x',  $value]);
			}
		}

		$index = $vbulletin->GPC['id'] - 1;
		$data["$index"] = $vbulletin->GPC['newfield'];

		$vbulletin->db->query_write("
			UPDATE " . TABLE_PREFIX . "profilefield
			SET data = '" . $vbulletin->db->escape_string(serialize($data)) . "'
			WHERE profilefieldid = " . $vbulletin->GPC['profilefieldid'] . "
		");
	}
	else
	{
		print_stop_message2('please_complete_required_fields');
	}

	print_stop_message2(['saved_option_x_successfully',  $vbulletin->GPC['newfield']], 'profilefield', ['do'=>'modifycheckbox', 'profilefieldid' => $vbulletin->GPC['profilefieldid']]);
}

// ###################### Start Remove #######################
if ($_REQUEST['do'] == 'deletecheckbox')
{
	$vbulletin->input->clean_array_gpc('r', [
		'id' => vB_Cleaner::TYPE_UINT
	]);

	print_form_header('admincp/profilefield', 'dodeletecheckbox');
	construct_hidden_code('profilefieldid', $vbulletin->GPC['profilefieldid']);
	construct_hidden_code('id', $vbulletin->GPC['id']);
	print_table_header($vbphrase['confirm_deletion_gcpglobal']);
	print_description_row($vbphrase['are_you_sure_you_want_to_delete_this_user_profile_field']);
	print_submit_row($vbphrase['yes'], '', 2, $vbphrase['no']);

}

// ###################### Process Remove Checkbox Option #######################
if ($_POST['do'] == 'dodeletecheckbox')
{
	$vbulletin->input->clean_array_gpc('r', [
		'id' => vB_Cleaner::TYPE_UINT
	]);

	$boxdata = profilefield_get_boxdata($vbulletin->GPC['profilefieldid']);
	$data = $boxdata['data'];

	$vbulletin->db->query_write("UPDATE " . TABLE_PREFIX . "userfield SET temp = field" . $vbulletin->GPC['profilefieldid']);

	foreach ($data AS $index => $value)
	{
		$index;
		$index2 = $index + 1;
		if ($index2 >= $vbulletin->GPC['id'])
		{
			if ($vbulletin->GPC['id'] == $index2)
			{
				build_profilefield_bitfields($vbulletin->GPC['profilefieldid'], $index2); // Delete this value
			}
			else
			{
				build_profilefield_bitfields($vbulletin->GPC['profilefieldid'], $index2, $index);
			}
			if ($index2 == sizeof($data))
			{
				unset($data["$index"]);
			}
			else
			{
				$data[$index] = $data[$index2];
			}
		}
	}

	$vbulletin->db->query_write("
		UPDATE " . TABLE_PREFIX . "userfield
		SET field" . $vbulletin->GPC['profilefieldid'] . " = temp,
		temp = ''
	");

	$vbulletin->db->query_write("
		UPDATE " . TABLE_PREFIX . "profilefield
		SET data = '" . $vbulletin->db->escape_string(serialize($data)) . "'
		WHERE profilefieldid = " . $vbulletin->GPC['profilefieldid'] . "
	");

	print_stop_message2('deleted_option_successfully', 'profilefield', ['do'=>'modifycheckbox', 'profilefieldid' => $vbulletin->GPC['profilefieldid']]);
}

// ###################### Start Add Checkbox #######################
if ($_POST['do'] == 'addcheckbox')
{
	$vbulletin->input->clean_array_gpc('p', [
		'newfield'    => vB_Cleaner::TYPE_NOHTML,
		'newfieldpos' => vB_Cleaner::TYPE_UINT,
	]);

	if (!empty($vbulletin->GPC['newfield']))
	{
		$boxdata = profilefield_get_boxdata($vbulletin->GPC['profilefieldid']);
		$data = $boxdata['data'];

		if (sizeof($data) >= 31)
		{
 			print_stop_message2(['too_many_profile_field_options',  sizeof($data)]);
 		}

		foreach ($data AS $index => $value)
		{
			if (strtolower($value) == strtolower($vbulletin->GPC['newfield']))
			{
				print_stop_message2(['this_is_already_option_named_x',  $value]);
			}
		}

		$vbulletin->db->query_write("UPDATE " . TABLE_PREFIX . "userfield SET temp = field" . $vbulletin->GPC['profilefieldid']);

		for ($x = sizeof($data); $x >= 0; $x--)
		{
			if ($x > $vbulletin->GPC['newfieldpos'])
			{
				$data["$x"] = $data[$x - 1];
				build_profilefield_bitfields($vbulletin->GPC['profilefieldid'], $x, $x + 1);
			}
			else if ($x == $vbulletin->GPC['newfieldpos'])
			{
				$data["$x"] = $vbulletin->GPC['newfield'];
			}
		}

		$vbulletin->db->query_write("
			UPDATE " . TABLE_PREFIX . "userfield
			SET field" . $vbulletin->GPC['profilefieldid'] . " = temp,
			temp = ''
		");

		$vbulletin->db->query_write("
			UPDATE " . TABLE_PREFIX . "profilefield SET
			data = '" . $vbulletin->db->escape_string(serialize($data)) . "'
			WHERE profilefieldid = " . $vbulletin->GPC['profilefieldid'] . "
		");

		print_stop_message2('saved_option_successfully', 'profilefield', ['do'=>'modifycheckbox', 'profilefieldid' => $vbulletin->GPC['profilefieldid']]);
	}
	else
	{
		print_stop_message2('invalid_option_specified');
	}

}

// ###################### Start Move Checkbox #######################

if ($_REQUEST['do'] == 'movecheckbox')
{
	$vbulletin->input->clean_array_gpc('r', [
		'direction' => vB_Cleaner::TYPE_STR,
		'id'        => vB_Cleaner::TYPE_UINT
	]);

	$boxdata = profilefield_get_boxdata($vbulletin->GPC['profilefieldid']);
	$data = $boxdata['data'];

	$vbulletin->db->query_write("UPDATE " . TABLE_PREFIX . "userfield SET temp = field" . $vbulletin->GPC['profilefieldid']);

	if ($vbulletin->GPC['direction'] == 'up')
	{
		build_bitwise_swap($vbulletin->GPC['profilefieldid'], $vbulletin->GPC['id'], $vbulletin->GPC['id'] - 1);
	}
	else
	{ // Down
		build_bitwise_swap($vbulletin->GPC['profilefieldid'], $vbulletin->GPC['id'], $vbulletin->GPC['id'] + 1);
	}

	foreach ($data AS $index => $value)
	{
		if ($index + 1 == $vbulletin->GPC['id'])
		{
			$temp = $data["$index"];
			if ($vbulletin->GPC['direction'] == 'up')
			{
				$data["$index"] = $data[strval($index - 1)];
				$data[strval($index - 1)] = $temp;
			}
			else

			{ // Down
				$data["$index"] = $data[strval($index + 1)];
				$data[strval($index + 1)] = $temp;
			}
			break;
		}
	}

	$vbulletin->db->query_write("
		UPDATE " . TABLE_PREFIX . "userfield
		SET field" . $vbulletin->GPC['profilefieldid'] . " = temp,
		temp = ''
	");

	$vbulletin->db->query_write("
		UPDATE " . TABLE_PREFIX . "profilefield
		SET data = '" . $vbulletin->db->escape_string(serialize($data)) . "'
		WHERE profilefieldid = " . $vbulletin->GPC['profilefieldid'] . "
	");

	$_REQUEST['do'] = 'modifycheckbox';

}

// ###################### Start Modify Checkbox Data #######################
if ($_REQUEST['do'] == 'modifycheckbox')
{
	$boxdata = profilefield_get_boxdata($vbulletin->GPC['profilefieldid']);
	$data = $boxdata['data'];

	if ($data)
	{
		//renumber the keys so they match the 1 based indexing we need here.
		$data = array_combine(array_map(function($x) {return $x+1;}, array_keys($data)), array_values($data));

		$upImage = '<img src="' .  get_cpstyle_href('move_up.gif') . '" />';
		$downImage = '<img src="' .  get_cpstyle_href('move_down.gif') . '" />';

		$args = [
			'profilefieldid' => $vbulletin->GPC['profilefieldid'],
		];

		//need to get this to common html functions.  I don't think this is RTL friendly.
		$output = '<table cellspacing="0" cellpadding="4"><tr><td>&nbsp;</td><td><b>' . $vbphrase['move_gcpglobal'] . '</b></td><td colspan=2><b>' . $vbphrase['option'] . '</b></td></tr>';
		foreach ($data AS $index => $value)
		{
			$args['id'] = $index;

			if ($index != 1)
			{
				$url = get_admincp_href('profilefield', $args + ['do' => 'movecheckbox', 'direction' => 'up']);
				$moveup = '<a href="' . $url . '">' . $upImage . '</a>';
			}
			else
			{
				//Should really move the spacer to css but it might be better to use a float right/float left appraoch to
				//swap for RTL.  Leaving this in place until we sort that out, but at least we aren't using the clear spacer image.
				$moveup = '<span style="width:11px;display:inline-block;" ></span>';
			}

			if ($index != sizeof($data))
			{
				$url = get_admincp_href('profilefield', $args + ['do' => 'movecheckbox', 'direction' => 'down']);
				$movedown = '<a href="' . $url . '">' . $downImage . '</a>';
			}
			else
			{
				$movedown = '';
			}

			$output .= '<tr>' .
				//this should probably be swapped in RTL.
				'<td align="right">' . $index . '.</td>' .
				'<td>' . $moveup . ' ' . $movedown . '</td>' .
				'<td>' . $value . '</td>' .
				'<td>' . construct_link_code2($vbphrase['rename_gprofilefield'], get_admincp_url('profilefield', $args + ['do' => 'renamecheckbox'])) . '</td>' .
				'<td>';

			if (sizeof($data) > 1)
			{
				$output .= construct_link_code2($vbphrase['delete'], get_admincp_url('profilefield', $args + ['do' => 'deletecheckbox']));
			}

			$output .= "</td></tr>\n";
		}
		$output .= '</table>';
	}
	else
	{
		$output = "<p>" . construct_phrase($vbphrase['this_profile_fields_no_options'], $boxdata['type']) . "</p>";
	}

	unset($args['id']);

	print_form_header2('', '');
	print_table_start2();
	print_table_header(
		construct_phrase($vbphrase['x_y_id_z'],
		$vbphrase['user_profile_field'],
		construct_link_code2($vbphrase['field' . $vbulletin->GPC['profilefieldid'] . '_title'], get_admincp_url('profilefield', $args + ['do' => 'edit'])),
		$vbulletin->GPC['profilefieldid'])
	);
	print_table_break();
	print_table_header($vbphrase['modify']);
	print_description_row($output);
	print_table_footer();


	if (sizeof($data) < 31)
	{
		print_form_header2('admincp/profilefield', 'addcheckbox');
		print_table_start2();
		construct_hidden_code('profilefieldid', $vbulletin->GPC['profilefieldid']);
		print_table_header($vbphrase['add']);
		print_description_row($vbphrase['note_max_31_options']);
		print_input_row($vbphrase['name'], 'newfield');

		$options = array_map(function($x) use ($vbphrase) {return construct_phrase($vbphrase['after_x'], $x);}, $data);
		print_select_row($vbphrase['postition'], 'newfieldpos', ['0' => $vbphrase['first']] + $options, $index);
		print_table_default_footer($vbphrase['add_new_option']);
	}
}

// ###################### Start Remove #######################
if ($_REQUEST['do'] == 'remove')
{

	print_form_header('admincp/profilefield', 'kill');
	construct_hidden_code('profilefieldid', $vbulletin->GPC['profilefieldid']);
	print_table_header(construct_phrase($vbphrase['confirm_deletion_x'], htmlspecialchars_uni($vbphrase['field' . $vbulletin->GPC['profilefieldid'] . '_title'])));
	print_description_row($vbphrase['are_you_sure_you_want_to_delete_this_user_profile_field']);
	print_submit_row($vbphrase['yes'], '', 2, $vbphrase['no']);
}

// ###################### Start Kill #######################

if ($_POST['do'] == 'kill')
{
	$vbulletin->db->query_write("
		DELETE FROM " . TABLE_PREFIX . "phrase
		WHERE fieldname = 'cprofilefield' AND
				varname IN ('field" . $vbulletin->GPC['profilefieldid'] . "_title', 'field" . $vbulletin->GPC['profilefieldid'] . "_desc')
	");

	require_once(DIR . '/includes/adminfunctions_language.php');
	build_language();

	require_once(DIR . '/includes/class_dbalter.php');
	$db_alter = new vB_Database_Alter_MySQL($vbulletin->db);

	$vbulletin->db->query_write("DELETE FROM " . TABLE_PREFIX . "profilefield WHERE profilefieldid = " . $vbulletin->GPC['profilefieldid']);
	if ($db_alter->fetch_table_info('userfield'))
	{
		$db_alter->drop_field("field" . $vbulletin->GPC['profilefieldid']);
	}
	$vbulletin->db->query_write("OPTIMIZE TABLE " . TABLE_PREFIX . "userfield");

	build_profilefield_cache();

	print_stop_message2('deleted_user_profile_field_successfully', 'profilefield', ['do'=>'modify']);
}

// ###################### Start modify #######################
if ($_REQUEST['do'] == 'modify')
{
	$assertor = vB::getDbAssertor();

	// cache profile field categories
	$pfcs = $assertor->getColumn('vBForum:profilefieldcategory', 'profilefieldcategoryid', [], 'displayorder');
	array_unshift($pfcs, 0);

	// query profile fields
	$columns = [
		'profilefieldid',
		'profilefieldcategoryid',
		'type',
		'form',
		'displayorder',
		'required',
		'editable',
		'hidden',
		'searchable',
		'memberlist',
		'showonpost'
	];
	$profilefields = $assertor->select('vBForum:profilefield', [], false, $columns);

	if ($profilefields->valid())
	{
		$forms = [
			0 => $vbphrase['edit_your_details'],
			1 => "$vbphrase[options]: $vbphrase[log_in] / $vbphrase[privacy]",
			2 => "$vbphrase[options]: $vbphrase[messaging] / $vbphrase[notification]",
			3 => "$vbphrase[options]: $vbphrase[thread_viewing]",
			4 => "$vbphrase[options]: $vbphrase[date] / $vbphrase[time]",
			5 => "$vbphrase[options]: $vbphrase[other_gprofilefield]",
		];

		$optionfields = [
			'required'   => $vbphrase['required'],
			'editable'   => $vbphrase['editable'],
			'hidden'     => $vbphrase['hidden'],
			'searchable' => $vbphrase['searchable'],
			'memberlist' => $vbphrase['members_list'],
			'showonpost' => $vbphrase['post'],
		];

		$fields = [];
		foreach ($profilefields AS $profilefield)
		{
			if ($profilefield['required'] == 2)
			{
				$profilefield['required'] == 0;
			}

			$profilefield['title'] = htmlspecialchars_uni($vbphrase['field' . $profilefield['profilefieldid'] . '_title']);
			$fields[$profilefield['form']][$profilefield['profilefieldcategoryid']][$profilefield['displayorder']][$profilefield['profilefieldid']] = $profilefield;
		}

		// sort by form and displayorder
		foreach ($fields AS $profilefieldcategoryid => $profilefieldcategory)
		{
			ksort($fields[$profilefieldcategoryid]);
			foreach (array_keys($fields[$profilefieldcategoryid]) AS $key)
			{
				ksort($fields[$profilefieldcategoryid][$key]);
			}
		}

		$numareas = sizeof($fields);
		$areacount = 0;

		print_form_header('admincp/profilefield', 'displayorder');

		foreach ($forms AS $formid => $formname)
		{
			if (isset($fields[$formid]) AND is_array($fields[$formid]))
			{
				print_table_header(construct_phrase($vbphrase['user_profile_fields_in_area_x'], $formname), 5);

				echo "
				<col width=\"50%\" align=\"" . vB_Template_Runtime::fetchStyleVar('left') . "\"></col>
				<col width=\"50%\" align=\"" . vB_Template_Runtime::fetchStyleVar('left') . "\"></col>
				<col align=\"" . vB_Template_Runtime::fetchStyleVar('left') . "\" style=\"white-space:nowrap\"></col>
				<col align=\"center\" style=\"white-space:nowrap\"></col>
				<col align=\"center\" style=\"white-space:nowrap\"></col>
				";

				print_cells_row([
					"$vbphrase[title] / $vbphrase[profile_field_type]",
					$vbphrase['options'],
					$vbphrase['name'],
					'<nobr>' . $vbphrase['display_order'] . '</nobr>',
					$vbphrase['controls']
				], 1, '', -1);

				foreach ($pfcs AS $pfcid)
				{
					if (isset($fields[$formid][$pfcid]) AND is_array($fields[$formid][$pfcid]))
					{
						if ($pfcid > 0)
						{
							print_description_row($vbphrase['category' . $pfcid . '_title'] . '<div class="normal">' . $vbphrase['category' . $pfcid . '_desc'] . '</div>', false, 5, 'optiontitle');
						}
						else
						{
							print_description_row('(' . $vbphrase['uncategorized_gprofilefield'] . ')', false, 5, 'optiontitle');
						}

						foreach ($fields["$formid"]["$pfcid"] AS $displayorder => $profilefields)
						{
							foreach ($profilefields AS $_profilefieldid => $profilefield)
							{
								$bgclass = fetch_row_bgclass();

								$options = [];
								foreach ($optionfields AS $fieldname => $optionname)
								{
									if ($profilefield[$fieldname])
									{
										$options[] = $optionname;
									}
								}
								$options = implode(', ', $options) . '&nbsp;';

								echo "
								<tr>
									<td class=\"$bgclass\"><strong>$profilefield[title] <dfn>{$types["{$profilefield['type']}"]}</dfn></strong></td>
									<td class=\"$bgclass\">$options</td>
									<td class=\"$bgclass\">field$_profilefieldid</td>
									<td class=\"$bgclass\"><input type=\"text\" class=\"bginput\" name=\"order[$_profilefieldid]\" value=\"$profilefield[displayorder]\" size=\"5\" /></td>
									<td class=\"$bgclass\">" .
									construct_link_code2($vbphrase['edit'], 'admincp/profilefield.php?do=edit&profilefieldid=' . $_profilefieldid) .
									construct_link_code2($vbphrase['delete'], 'admincp/profilefield.php?do=remove&profilefieldid=' . $_profilefieldid) .
									"</td>
								</tr>";
							}
						}
					}
				}

				print_description_row("<input type=\"submit\" class=\"button\" value=\"$vbphrase[save_display_order]\" accesskey=\"s\" />", 0, 5, 'tfoot', vB_Template_Runtime::fetchStyleVar('right'));

				if (++$areacount < $numareas)
				{
					print_table_break('');
				}
			}
		}

		print_table_footer();
	}
	else
	{
		print_stop_message2('no_profile_fields_defined');
	}

}
// #############################################################################

print_cp_footer();

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 116202 $
|| #######################################################################
\*=========================================================================*/
