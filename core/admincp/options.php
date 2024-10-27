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
define('CVS_REVISION', '$RCSfile$ - $Revision: 115966 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
global $phrasegroups, $specialtemplates, $vbulletin, $vbphrase;
$phrasegroups = array(
	'timezone',
	'user',
	'cpuser',
	'holiday',
	'cppermission',
	'cpoption',
	'cphome',
	'attachment_image',
	'cprofilefield', // used for the profilefield option type
);

// ########################## REQUIRE BACK-END ############################
require_once(dirname(__FILE__) . '/global.php');

$vbulletin->input->clean_array_gpc('r', [
	'varname' => vB_Cleaner::TYPE_STR,
	'dogroup' => vB_Cleaner::TYPE_STR,
]);
$userContext = vB::getUserContext();

// intercept direct call to do=options with $varname specified instead of $dogroup
if ($_REQUEST['do'] == 'options' AND !empty($vbulletin->GPC['varname']))
{
	if ($vbulletin->GPC['varname'] == '[all]')
	{
		// go ahead and show all settings
		$vbulletin->GPC['dogroup'] = '[all]';
	}
	else if ($group = vB::getDbAssertor()->getRow('setting', ['varname' => $vbulletin->GPC['varname']]))
	{
		$args = [
			'do' => 'options',
			'dogroup' => $group['grouptitle'],
		];

		// redirect to show the correct group and use and anchor to jump to the correct variable
		exec_header_redirect(get_admincp_url('options', $args, $group['varname']));
	}
}

require_once(DIR . '/includes/adminfunctions_options.php');

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!can_administer('canadminsettings') AND !$userContext->hasAdminPermission('canadminsettingsall')
	AND !$userContext->hasAdminPermission('cansetserverconfig'))
{
	print_cp_no_permission();
}

// ############################# LOG ACTION ###############################
log_admin_action();

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################
$assertor = vB::getDbAssertor();
$vb5_config =& vB::getConfig();
$vb_options = vB::getDatastore()->getValue('options');

// query settings phrases
global $settingphrase;
$settingphrase = [];
$phrases = $assertor->assertQuery('vBForum:phrase',
		array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'fieldname' => 'vbsettings',
			'languageid' => array(-1, 0, LANGUAGEID),
		),
		array('field' => 'languageid', 'direction' => vB_dB_Query::SORT_ASC)
);
if ($phrases AND $phrases->valid())
{
	foreach ($phrases AS $phrase)
	{
		$settingphrase["$phrase[varname]"] = $phrase['text'];
	}
}

// #############################################################################

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'options';
}

// ###################### Start download XML settings #######################

if ($_REQUEST['do'] == 'download')
{
	if (!$userContext->hasAdminPermission('canadminsettingsall'))
	{
		print_cp_no_permission();
	}
	require_once(DIR . '/includes/functions_file.php');
	$vbulletin->input->clean_array_gpc('r', array('product' => vB_Cleaner::TYPE_STR));
	$get_settings = vB_Api::instance('Options')->getSettingsXML($vbulletin->GPC['product']);
	print_stop_message_on_api_error($get_settings);
	file_download($get_settings['settings'], 'vbulletin-settings.xml', 'text/xml');
}


// ###################### Start product XML backup #######################

if ($_REQUEST['do'] == 'backup')
{
	if (!$userContext->hasAdminPermission('canadminsettingsall'))
	{
		print_cp_no_permission();
	}
	$vbulletin->input->clean_array_gpc('r', array(
		'product'   => vB_Cleaner::TYPE_STR,
		'blacklist' => vB_Cleaner::TYPE_BOOL,
	));

	require_once(DIR . '/includes/functions_file.php');
	$groupSettings = vB_Api::instance('Options')->getGroupSettingsXML($vbulletin->GPC['blacklist'], $vbulletin->GPC['product']);
	print_stop_message_on_api_error($groupSettings);

	$doc = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\r\n\r\n";
	$doc .= $groupSettings['settings'];
	file_download($doc, 'vbulletin-settings2.xml', 'text/xml');
}

// #############################################################################
// ajax setting value validation
if ($_POST['do'] == 'validate')
{
	$vbulletin->input->clean_array_gpc('p', [
		'varname' => vB_Cleaner::TYPE_STR,
		'setting' => vB_Cleaner::TYPE_ARRAY
	]);

	$validate = vB_Api::instance('options')->validateSettings($vbulletin->GPC['varname'], $vbulletin->GPC['setting']);

	//this is wrong since this is an ajax request, but we need to figure out how to pass back the error message
	//this is better than what was here.
	print_stop_message_on_api_error($validate);

	//we should not be returning an object from the API.
	$validate['xml']->print_xml();
}

// ***********************************************************************

print_cp_header($vbphrase['vbulletin_options']);

// ###################### Start do import settings XML #######################
if ($_POST['do'] == 'doimport')
{
	if (!$userContext->hasAdminPermission('canadminsettingsall'))
	{
		print_cp_no_permission();
	}
	$vbulletin->input->clean_array_gpc('p', [
		'serverfile' => vB_Cleaner::TYPE_STR,
		'restore'    => vB_Cleaner::TYPE_BOOL,
		'blacklist'  => vB_Cleaner::TYPE_BOOL,
	]);

	$vbulletin->input->clean_array_gpc('f', [
		'settingsfile' => vB_Cleaner::TYPE_FILE
	]);
	scanVbulletinGPCFile('settingsfile');


	if (is_demo_mode())
	{
		print_cp_message('This function is disabled within demo mode');
	}

	$doImport = vB_Api::instance('Options')->importSettingsXML(
		$vbulletin->GPC['settingsfile'],
		$vbulletin->GPC['serverfile'],
		$vbulletin->GPC['restore'],
		$vbulletin->GPC['blacklist']
	);

	print_stop_message_on_api_error($doImport);
	print_cp_redirect2('options');
}


// ###################### Start kill setting group #######################
if ($_POST['do'] == 'killgroup')
{
	if (!$userContext->hasAdminPermission('canadminsettingsall'))
	{
		print_cp_no_permission();
	}
	$vbulletin->input->clean_array_gpc('p', array('title' => vB_Cleaner::TYPE_STR));
	$doDelete = vB_Api::instance('Options')->deleteGroupSettings($vbulletin->GPC['title']);
	if(isset($doDelete['errors']))
	{
		print_stop_message2($doDelete['errors'][0]);
	}
	else
	{
		print_stop_message2('deleted_setting_group_successfully', 'options');
	}
}

// ###################### Start remove setting group #######################
if ($_REQUEST['do'] == 'removegroup')
{
	if (!$userContext->hasAdminPermission('canadminsettingsall'))
	{
		print_cp_no_permission();
	}
	$vbulletin->input->clean_array_gpc('r', array('grouptitle' => vB_Cleaner::TYPE_STR));
	print_delete_confirmation('settinggroup', $vbulletin->GPC['grouptitle'], 'options', 'killgroup');
}

// ###################### Start insert setting group #######################
if ($_POST['do'] == 'insertgroup')
{
	if (!$userContext->hasAdminPermission('canadminsettingsall'))
	{
		print_cp_no_permission();
	}
	$vbulletin->input->clean_array_gpc('p', array('group' => vB_Cleaner::TYPE_ARRAY));

	$insertGroup = vB_Api::instance('Options')->addGroupSettings($vbulletin->GPC['group']);
	if(isset($insertGroup['errors']))
	{
		print_stop_message2($insertGroup['errors'][0]);
	}

	// fall through to 'updategroup' for the real work...
	$_POST['do'] = 'updategroup';

}

// ###################### Start update setting group #######################
if ($_POST['do'] == 'updategroup')
{
	if (!$userContext->hasAdminPermission('canadminsettingsall'))
	{
		print_cp_no_permission();
	}
	$vbulletin->input->clean_array_gpc('p', [
		'group' => vB_Cleaner::TYPE_ARRAY,
		'oldproduct' => vB_Cleaner::TYPE_STR,
		'adminperm' => vB_Cleaner::TYPE_STR,
	]);

	$updateGroup = vB_Api::instance('Options')->updateGroupSettings(
		$vbulletin->GPC['group'],
		$vbulletin->userinfo['username'],
		$vbulletin->GPC['oldproduct'],
		$vbulletin->GPC['adminperm']
	);

	print_stop_message_on_api_error($updateGroup);

	print_stop_message2(
		['saved_setting_group_x_successfully', $vbulletin->GPC['group']['title']],
		'options',
		['do' => 'options', 'dogroup' => $vbulletin->GPC['group']['grouptitle']]
	);
}

// ###################### Start edit setting group #######################
if ($_REQUEST['do'] == 'editgroup' OR $_REQUEST['do'] == 'addgroup')
{
	if (!$userContext->hasAdminPermission('canadminsettingsall'))
	{
		print_cp_no_permission();
	}

	$vbulletin->input->clean_array_gpc('r', [
		'grouptitle' => vB_Cleaner::TYPE_STR,
	]);

	$assertor = vB::getDbAssertor();

	if ($_REQUEST['do'] == 'editgroup')
	{
		$group = $assertor->getRow('settinggroup', ['grouptitle' => $vbulletin->GPC['grouptitle']]);

		$phrase = $assertor->getRow('vBForum:phrase', [
			'languageid' => [-1, 0],
			'fieldname' => 'vbsettings',
			'varname' => "settinggroup_" . $group['grouptitle']
		]);

		$group['title'] = $phrase['text'];
		$pagetitle = construct_phrase($vbphrase['x_y_id_z'], $vbphrase['setting_group'], $group['title'], $group['grouptitle']);
		$formdo = 'updategroup';
	}
	else
	{
		$ordercheck = $assertor->getRow('settinggroup', [], ['field' => 'displayorder', 'direction' => vB_dB_Query::SORT_DESC]);

		$group = [
			'displayorder' => ($ordercheck['displayorder'] ?? 0) + 10,
			'volatile' => ($vb5_config['Misc']['debug'] ? 1 : 0),
			'grouptitle' => '',
			'title' => '',
			'product' => 'vbulletin',
			'adminperm' => '',
		];

		$pagetitle = $vbphrase['add_new_setting_group'];
		$formdo = 'insertgroup';
	}

	print_form_header('admincp/options', $formdo);
	print_table_header($pagetitle);

	if ($_REQUEST['do'] == 'editgroup')
	{
		print_label_row($vbphrase['varname'], "<b>$group[grouptitle]</b>");
		construct_hidden_code('group[grouptitle]', $group['grouptitle']);
	}
	else
	{
		print_input_row($vbphrase['varname'], 'group[grouptitle]', $group['grouptitle']);
	}
	print_input_row($vbphrase['title'], 'group[title]', $group['title']);
	construct_hidden_code('oldproduct', $group['product']);
	print_select_row($vbphrase['product'], 'group[product]', fetch_product_list(), $group['product']);
	print_input_row($vbphrase['display_order'], 'group[displayorder]', $group['displayorder']);

	if ($vb5_config['Misc']['debug'])
	{
		print_yes_no_row($vbphrase['vbulletin_default'], 'group[volatile]', $group['volatile']);
	}
	else
	{
		construct_hidden_code('group[volatile]', $group['volatile']);
	}

	if ($userContext->hasAdminPermission('canadminsettingsall') AND !empty($vb5_config['Misc']['debug']))
	{
		print_input_row($vbphrase['group_requires_admin_perm'], 'adminperm', $group['adminperm'], true, 32);
	}
	print_submit_row($vbphrase['save']);

}

// ###################### Start kill setting #######################
if ($_POST['do'] == 'killsetting')
{
	if (!$userContext->hasAdminPermission('canadminsettingsall'))
	{
		print_cp_no_permission();
	}
	$vbulletin->input->clean_array_gpc('p', ['title' => vB_Cleaner::TYPE_STR]);

	$delete = vB_Api::instance('Options')->killSetting($vbulletin->GPC['title']);
	print_stop_message_on_api_error($delete);

	print_stop_message2('deleted_setting_successfully',	'options', ['do' => 'options', 'dogroup' => $delete['setting']['grouptitle']]);
}

// ###################### Start remove setting #######################
if ($_REQUEST['do'] == 'removesetting')
{
	if (!$userContext->hasAdminPermission('canadminsettingsall'))
	{
		print_cp_no_permission();
	}
	print_delete_confirmation('setting', $vbulletin->GPC['varname'], 'options', 'killsetting');
}

// ###################### Start insert setting #######################
if ($_POST['do'] == 'insertsetting')
{
	if (!$userContext->hasAdminPermission('canadminsettingsall'))
	{
		print_cp_no_permission();
	}
	$vbulletin->input->clean_array_gpc('p', array(
		// setting stuff
		'varname'        => vB_Cleaner::TYPE_STR,
		'grouptitle'     => vB_Cleaner::TYPE_STR,
		'optioncode'     => vB_Cleaner::TYPE_STR,
		'defaultvalue'   => vB_Cleaner::TYPE_STR,
		'displayorder'   => vB_Cleaner::TYPE_UINT,
		'volatile'       => vB_Cleaner::TYPE_INT,
		'datatype'       => vB_Cleaner::TYPE_STR,
		'validationcode' => vB_Cleaner::TYPE_STR,
		'product'        => vB_Cleaner::TYPE_STR,
		'blacklist'      => vB_Cleaner::TYPE_BOOL,
		'ispublic'          => vB_Cleaner::TYPE_BOOL,
		// phrase stuff
		'title'          => vB_Cleaner::TYPE_STR,
		'description'    => vB_Cleaner::TYPE_STR,
		'oldproduct'     => vB_Cleaner::TYPE_STR,
		'adminperm'     => vB_Cleaner::TYPE_STR,
	));

	if (is_demo_mode())
	{
		print_cp_message('This function is disabled within demo mode');
	}

	$setting = array(
		'varname' => $vbulletin->GPC['varname'],
		'grouptitle' => $vbulletin->GPC['grouptitle'],
		'optioncode' => $vbulletin->GPC['optioncode'],
		'defaultvalue' => $vbulletin->GPC['defaultvalue'],
		'displayorder' => $vbulletin->GPC['displayorder'],
		'volatile' => $vbulletin->GPC['volatile'],
		'datatype' => $vbulletin->GPC['datatype'],
		'validationcode' => $vbulletin->GPC['validationcode'],
		'product' => $vbulletin->GPC['product'],
		'blacklist' => $vbulletin->GPC['blacklist'],
		'title' => $vbulletin->GPC['title'],
		'username' => $vbulletin->userinfo['username'],
		'description' => $vbulletin->GPC['description'],
		'ispublic' => $vbulletin->GPC['ispublic'],
		'adminperm' => $vbulletin->GPC['adminperm'],
	);
	$insert = vB_Api::instance('Options')->insertSetting($setting);

	if (isset($insert['errors']))
	{
		print_stop_message_array($insert['errors']);
	}
	else
	{
		if (defined('DEV_AUTOEXPORT') AND DEV_AUTOEXPORT)
		{
			require_once(DIR . '/includes/functions_filesystemxml.php');
			autoexport_write_settings_and_language(($vbulletin->GPC['volatile'] ? -1 : 0), $vbulletin->GPC['product']);
		}

		print_stop_message2(array('saved_setting_x_successfully', $vbulletin->GPC['title']),
			'options', array('do' => 'options', 'dogroup' => $vbulletin->GPC['grouptitle'])
		);
	}
}

// ###################### Start update setting #######################
if ($_POST['do'] == 'updatesetting')
{
	if (!$userContext->hasAdminPermission('canadminsettingsall'))
	{
		print_cp_no_permission();
	}
	$vbulletin->input->clean_array_gpc('p', array(
		// setting stuff
		'varname'        => vB_Cleaner::TYPE_STR,
		'grouptitle'     => vB_Cleaner::TYPE_STR,
		'optioncode'     => vB_Cleaner::TYPE_STR,
		'defaultvalue'   => vB_Cleaner::TYPE_STR,
		'displayorder'   => vB_Cleaner::TYPE_UINT,
		'volatile'       => vB_Cleaner::TYPE_INT,
		'datatype'       => vB_Cleaner::TYPE_STR,
		'validationcode' => vB_Cleaner::TYPE_STR,
		'product'        => vB_Cleaner::TYPE_STR,
		'blacklist'      => vB_Cleaner::TYPE_BOOL,
		'ispublic'       => vB_Cleaner::TYPE_BOOL,
		'adminperm'      => vB_Cleaner::TYPE_STR,
		// phrase stuff
		'title'          => vB_Cleaner::TYPE_STR,
		'description'    => vB_Cleaner::TYPE_STR,
	));

	if (is_demo_mode())
	{
		print_cp_message('This function is disabled within demo mode');
	}

	$values = array(
		'varname' => $vbulletin->GPC['varname'],
		'grouptitle' => $vbulletin->GPC['grouptitle'],
		'optioncode' => $vbulletin->GPC['optioncode'],
		'defaultvalue' => $vbulletin->GPC['defaultvalue'],
		'displayorder' => $vbulletin->GPC['displayorder'],
		'volatile' => $vbulletin->GPC['volatile'],
		'datatype' => $vbulletin->GPC['datatype'],
		'validationcode' => $vbulletin->GPC['validationcode'],
		'product' => $vbulletin->GPC['product'],
		'blacklist' => $vbulletin->GPC['blacklist'],
		'title' => $vbulletin->GPC['title'],
		'username' => $vbulletin->userinfo['username'],
		'description' => $vbulletin->GPC['description'],
		'ispublic' => $vbulletin->GPC['ispublic'],
		'adminperm' => $vbulletin->GPC['adminperm']
	);

	$update = vB_Api::instance('Options')->updateSetting($values);

	if (defined('DEV_AUTOEXPORT') AND DEV_AUTOEXPORT)
	{
		require_once(DIR . '/includes/functions_filesystemxml.php');
		autoexport_write_settings_and_language(($vbulletin->GPC['volatile'] ? -1 : 0), $vbulletin->GPC['product']);
	}

	if (isset($update['errors']))
	{
		print_stop_message2($update['errors'][0]);
	}
	else
	{
		print_stop_message2(array('saved_setting_x_successfully', $vbulletin->GPC['title']),
			'options', array('do' => 'options', 'dogroup' => $vbulletin->GPC['grouptitle'])
		);
	}
}

// ###################### Start edit / add setting #######################
if ($_REQUEST['do'] == 'editsetting' OR $_REQUEST['do'] == 'addsetting')
{
	if (!$userContext->hasAdminPermission('canadminsettingsall'))
	{
		print_cp_no_permission();
	}

	$vbulletin->input->clean_array_gpc('r', [
		'grouptitle' => vB_Cleaner::TYPE_STR
	]);

	if (is_demo_mode())
	{
		print_cp_message('This function is disabled within demo mode');
	}

	$assertor = vB::getDbAssertor();

	$product = '';
	$settinggroups = [];
	$groups = $assertor->assertQuery('settinggroup', [], ['field' => 'displayorder', 'direction' => vB_dB_Query::SORT_ASC]);
	foreach ($groups AS $group)
	{
		$settinggroups["$group[grouptitle]"] = $settingphrase["settinggroup_$group[grouptitle]"];
		if ($group['grouptitle'] == $vbulletin->GPC['grouptitle'])
		{
			$product = $group['product'];
		}
	}

	if ($_REQUEST['do'] == 'editsetting')
	{
		$setting = $assertor->getRow('setting', ['varname' => $vbulletin->GPC['varname']]);

		$langid = $setting['volatile'] ? -1 : 0;
		$phrases =  $assertor->assertQuery('vBForum:phrase', [
		'languageid' => $langid,
		'fieldname' => 'vbsettings',
		'varname' => ["setting_" . $setting['varname'] . "_title", "setting_" . $setting['varname'] . "_desc"]
		]);

		foreach ($phrases AS $phrase)
		{
			if ($phrase['varname'] == "setting_$setting[varname]_title")
			{
				$setting['title'] = $phrase['text'];
			}
			else if ($phrase['varname'] == "setting_$setting[varname]_desc")
			{
				$setting['description'] = $phrase['text'];
			}
		}

		$pagetitle = construct_phrase($vbphrase['x_y_id_z'], $vbphrase['setting'], $setting['title'], $setting['varname']);
		$formdo = 'updatesetting';
	}
	else
	{
		$ordercheck = $assertor->getRow('setting',
			['grouptitle' => $vbulletin->GPC['grouptitle']],
			['field' => 'displayorder', 'direction' => vB_dB_Query::SORT_DESC]
		);

		$setting = [
			'grouptitle'     => $vbulletin->GPC['grouptitle'],
			'displayorder'   => ($ordercheck['displayorder'] ?? 0) + 10,
			'volatile'       => $vb5_config['Misc']['debug'] ? 1 : 0,
			'product'        => $product,
			'varname'        => '',
			'title'          => '',
			'description'    => '',
			'optioncode'     => '',
			'validationcode' => '',
			'adminperm'      => '',
			'defaultvalue'   => '',
			'datatype'       => 'free',
			'ispublic'       => false,
			'blacklist'      => false,
		];

		$pagetitle = $vbphrase['add_new_setting'];
		$formdo = 'insertsetting';
	}

	print_form_header('admincp/options', $formdo);
	print_table_header($pagetitle);
	if ($_REQUEST['do'] == 'editsetting')
	{
		construct_hidden_code('varname', $setting['varname']);
		print_label_row($vbphrase['varname'], "<b>$setting[varname]</b>");
	}
	else
	{
		print_input_row($vbphrase['varname'], 'varname', $setting['varname']);
	}
	print_select_row($vbphrase['setting_group'], 'grouptitle', $settinggroups, $setting['grouptitle']);
	print_select_row($vbphrase['product'], 'product', fetch_product_list(), $setting['product']);
	print_input_row($vbphrase['title'], 'title', $setting['title']);
	print_textarea_row($vbphrase['description_gcpglobal'], 'description', $setting['description'], 4, '50" style="width:100%');
	print_textarea_row($vbphrase['option_code'], 'optioncode', $setting['optioncode'], 4, '50" style="width:100%');
	print_textarea_row($vbphrase['default'], 'defaultvalue', $setting['defaultvalue'], 4, '50" style="width:100%');

	$types = [
		'free'     => 'datatype_free',
		'number'   => 'datatype_numeric',
		'integer'  => 'datatype_integer',
		'posint'   => 'datatype_posint',
		'boolean'  => 'datatype_boolean',
		'bitfield' => 'datatype_bitfield',
		'username' => 'datatype_username',
	];

	$controls = [];
	foreach($types AS $type => $phrase)
	{
		$id = 'rb_dt_' . $type;
		$checked = ($setting['datatype'] == $type ? 'checked="checked"' : '');
		$controls[] = '<label for="' . $id . '"><input type="radio" name="datatype" id="' . $id .
			'" tabindex="1" value="' . $type . '" ' . $checked . ' />' . $vbphrase[$phrase] . '</label>';
	}

	print_label_row($vbphrase['data_validation_type'], '<div class="smallfont">' . "\n" . implode("\n", $controls) . "\n" . '</div>');
	print_textarea_row($vbphrase['validation_php_code'], 'validationcode', $setting['validationcode'], 4, '50" style="width:100%');

	print_input_row($vbphrase['display_order'], 'displayorder', $setting['displayorder']);
	print_yes_no_row($vbphrase['blacklist'], 'blacklist', $setting['blacklist']);
	if ($vb5_config['Misc']['debug'])
	{
		print_yes_no_row($vbphrase['vbulletin_default'], 'volatile', $setting['volatile']);
		print_yes_no_row($vbphrase['ispublic'], 'ispublic', $setting['ispublic']);
	}
	else
	{
		construct_hidden_code('volatile', $setting['volatile']);
	}


	if ($userContext->hasAdminPermission('canadminsettingsall') AND !empty($vb5_config['Misc']['debug']))
	{
		print_input_row($vbphrase['requires_admin_perm'], 'adminperm', $setting['adminperm'], true, 32);
	}

	print_submit_row($vbphrase['save']);
}

// ###################### Start do options #######################
if ($_POST['do'] == 'dooptions')
{
	if (!$userContext->hasAdminPermission('canadminsettingsall') AND !$userContext->hasAdminPermission('canadminsettings'))
	{
		print_cp_no_permission();
	}

	$vbulletin->input->clean_array_gpc('p', array(
		'setting'  => vB_Cleaner::TYPE_ARRAY,
		'advanced' => vB_Cleaner::TYPE_BOOL
	));

	if (!empty($vbulletin->GPC['setting']))
	{
		try
		{
			$save = save_settings($vbulletin->GPC['setting']);
		}
		catch (vB_Exception_Api $e)
		{
			$errors = $e->get_errors();
			print_stop_message_array($errors);
		}

		if ($save)
		{
			print_stop_message2('saved_settings_successfully', 'options',
				array('do' => 'options', 'dogroup' => $vbulletin->GPC['dogroup'], 'advanced' => $vbulletin->GPC['advanced']));
		}
		else
		{
			print_stop_message2('nothing_to_do');
		}
	}
	else
	{
		print_stop_message2('nothing_to_do');
	}

}
// ###################### Start modify options #######################
if ($_REQUEST['do'] == 'options')
{
	if (!$userContext->hasAdminPermission('canadminsettingsall') AND !$userContext->hasAdminPermission('canadminsettings'))
	{
		print_cp_no_permission();
	}
	global $settingscache, $grouptitlecache;

	require_once(DIR . '/includes/adminfunctions_language.php');

	$vbulletin->input->clean_array_gpc('r', [
		'advanced' => vB_Cleaner::TYPE_BOOL,
		'expand'   => vB_Cleaner::TYPE_BOOL,
	]);

	echo '<script type="text/javascript" src="core/clientscript/vbulletin_cpoptions_scripts.js?v=' . SIMPLE_VERSION . '"></script>';

	// display links to settinggroups and create settingscache
	$settingscache = [];
	$options = ['[all]' => '-- ' . $vbphrase['show_all_settings'] . ' --'];
	$lastgroup = '';

	$settings = vB::getDbAssertor()->assertQuery('vBForum:fetchSettingsByGroup', ['debug' => $vb5_config['Misc']['debug']]);

	if (empty($vbulletin->GPC['dogroup']) AND $vbulletin->GPC['expand'])
	{
		foreach ($settings AS $setting)
		{
			// TODO: Issue #29084 - Reenable Profile Styling
			if ('profile_customization' == $setting['grouptitle'])
			{
				continue;
			}
			//check the permissions
			if ((!empty($setting['groupperm']) AND !$userContext->hasAdminpermission($setting['groupperm'])) OR
				(!empty($setting['adminperm']) AND !$userContext->hasAdminpermission($setting['adminperm'])))
			{
				continue;
			}

			$settingscache["$setting[grouptitle]"]["$setting[varname]"] = $setting;
			if ($setting['grouptitle'] != $lastgroup)
			{
				$grouptitlecache["$setting[grouptitle]"] = $setting['grouptitle'];
				$grouptitle = $settingphrase["settinggroup_$setting[grouptitle]"];
			}
			$options["$grouptitle"]["$setting[varname]"] = $settingphrase["setting_$setting[varname]_title"];
			$lastgroup = $setting['grouptitle'];
		}

		$altmode = 0;
		$linktext =& $vbphrase['collapse_setting_groups'];
	}
	else
	{
		foreach ($settings AS $setting)
		{
			// TODO: Issue #29084 - Reenable Profile Styling
			if ('profile_customization' == $setting['grouptitle'])
			{
				continue;
			}

			//check the permissions
			if ((!empty($setting['groupperm']) AND !$userContext->hasAdminpermission($setting['groupperm'])) OR
				(!empty($setting['adminperm']) AND !$userContext->hasAdminpermission($setting['adminperm'])))
			{
				continue;
			}


			$settingscache["$setting[grouptitle]"]["$setting[varname]"] = $setting;
			if ($setting['grouptitle'] != $lastgroup)
			{
				$grouptitlecache["$setting[grouptitle]"] = $setting['grouptitle'];
				$options["$setting[grouptitle]"] = $settingphrase["settinggroup_$setting[grouptitle]"];
			}
			$lastgroup = $setting['grouptitle'];
		}

		$altmode = 1;
		$linktext =& $vbphrase['expand_setting_groups'];
	}

	//style should be moved to the admincp css file.
	$attributes = [
		'id' => 'settings-filter',
		'placeholder' => $vbphrase['filter_settings'],
		'autocomplete' => 'off',
		'style' => 'margin:0 0 6px 0;width:338px;',
	];

	$optionsfilter = construct_input('text', '', $attributes, false) . '<br />';

	$attributes = [
		'id' => 'settings-select',
		'style' => 'width:350px',
	];

	if(!$vbulletin->GPC['dogroup'])
	{
		$selected = '[all]';
		$attributes['size'] = 20;
		$attributes['ondblclick'] = 'this.form.submit();';
	}
	else
	{
		$selected = $vbulletin->GPC['dogroup'];
		$attributes['onchange'] = 'this.form.submit();';
	}

	$name = ($vbulletin->GPC['expand'] ? 'varname' : 'dogroup');
	$optionsmenu = "\n\t" . construct_select($name, $options, $selected, $attributes) .  "\n\t";

	print_form_header2('admincp/options', 'options', [], ['name' => 'groupForm', 'method' => 'get']);
	//about 90% sure that the id isn't necessary here.
	print_table_start2([], ['id' => 'groupForm_table']);

	if (empty($vbulletin->GPC['dogroup'])) // show the big <select> with no options
	{
		print_table_header($vbphrase['vbulletin_options']);

		$extralabelinsert = [];
		if($vb5_config['Misc']['debug'] AND $userContext->hasAdminPermission('canadminsettingsall'))
		{
			$extralabelinsert[] = '<br />' .
				'<table><tr><td><fieldset><legend>Developer Options</legend>
					<div style="padding: 2px"><a href="admincp/options.php?do=addgroup">' . $vbphrase['add_new_setting_group'] . '</a></div>
					<div style="padding: 2px"><a href="admincp/options.php?do=files">' . $vbphrase['download_upload_settings'] . '</a></div>' .
				'</fieldset></td></tr></table>';
		}

		$extralabelinsert[] = "<p><a href=\"admincp/options.php?expand=$altmode\">$linktext</a></p> ";

		if($userContext->hasAdminPermission('canadminsettingsall'))
		{
			$extralabelinsert[] = "<p><a href=\"admincp/options.php?do=backuprestore\">" . $vbphrase['backup_restore_settings'] . "</a></p>";
		}

		print_label_row($vbphrase['settings_to_edit'] . implode("\n", $extralabelinsert), $optionsfilter . $optionsmenu);
		print_table_default_footer($vbphrase['edit_settings']);
	}
	// show the small list with selected setting group(s) options
	else
	{
		print_table_header("$vbphrase[setting_group] $optionsmenu <input type=\"submit\" value=\"$vbphrase[go]\" class=\"button\" tabindex=\"1\" />");
		print_table_footer();

		// show selected settings

		//this phrase name could stand to be a little more specific
		register_js_phrase('error_confirmation_phrase');
		print_form_header2('admincp/options', 'dooptions', ['js-setting-form'], ['name' => 'optionsform', 'enctype' => 'multipart/form-data']);
		//about 90% sure that the id isn't necessary here.
		print_table_start2([], ['id' => 'optionsform_table']);
		construct_hidden_code('dogroup', $vbulletin->GPC['dogroup']);
		construct_hidden_code('advanced', $vbulletin->GPC['advanced']);

		if ($vbulletin->GPC['dogroup'] == '[all]') // show all settings groups
		{
			foreach ($grouptitlecache AS $curgroup => $group)
			{
				print_setting_group($curgroup, $vbulletin->GPC['advanced']);
				echo '<tbody>';
				$button = construct_submit_button($vbphrase['save'], [], ['title' => $vbphrase['save_settings']]);
				print_description_row($button, 0, 2, 'tfoot" style="padding:1px" align="right');
				echo '</tbody>';
				print_table_break(' ');
			}
		}
		else
		{
			print_setting_group($vbulletin->GPC['dogroup'], $vbulletin->GPC['advanced']);
		}

		print_table_default_footer($vbphrase['save']);
		//this should probably be moved to the header, but there are some wierd events in here that we probably should avoid
		//if we don't need them.
		echo get_admincp_script_tag('vbulletin_settings_validate.js');
	}
}

// ###################### Start modify options #######################
// The backuprestore and files actions are very similar and it's not clear why we have both
// should probably, at the very least, combine the common logic and handle the differences as options.
if ($_REQUEST['do'] == 'backuprestore')
{
	if (!$userContext->hasAdminPermission('canadminsettingsall'))
	{
		print_cp_no_permission();
	}

	if (is_demo_mode())
	{
		print_cp_message('This function is disabled within demo mode');
	}

	// download form
	print_form_header2('admincp/options', 'backup', [], ['name' => 'downloadform']);
	print_table_start2([], ['id' => 'downloadform_table']);
	print_table_header($vbphrase['backup']);
	print_select_row($vbphrase['product'], 'product', fetch_product_list());
	print_yes_no_row($vbphrase['ignore_blacklisted_settings'], 'blacklist', 1);
	print_table_default_footer($vbphrase['backup']);

	print_form_header2('admincp/options', 'doimport', [], ['name' => 'uploadform', 'enctype' => 'multipart/form-data']);
	print_table_start2([], ['id' => 'uploadform_table']);
	construct_hidden_code('restore', 1);
	print_table_header($vbphrase['restore_settings_xml_file']);
	print_yes_no_row($vbphrase['ignore_blacklisted_settings'], 'blacklist', 1);
	print_upload_row($vbphrase['upload_xml_file'], 'settingsfile', 999999999);
	print_input_row($vbphrase['restore_xml_file'], 'serverfile', './install/vbulletin-settings.xml');
	print_table_default_footer($vbphrase['restore']);
}

// ###################### Start import settings XML #######################
if ($_REQUEST['do'] == 'files')
{
	if (!$userContext->hasAdminPermission('canadminsettingsall'))
	{
		print_cp_no_permission();
	}

	if (is_demo_mode())
	{
		print_cp_message('This function is disabled within demo mode');
	}

	$vbulletin->input->clean_array_gpc('r', array(
		'type' => vB_Cleaner::TYPE_NOHTML
	));

	// download form
	print_form_header2('admincp/options', 'download', [], ['name' => 'downloadform']);
	print_table_start2([], ['id' => 'downloadform_table']);
	print_table_header($vbphrase['download']);
	print_select_row($vbphrase['product'], 'product', fetch_product_list());
	print_table_default_footer($vbphrase['download']);

	print_form_header2('admincp/options', 'doimport', [], ['name' => 'uploadform', 'enctype' => 'multipart/form-data']);
	print_table_start2([], ['id' => 'uploadform_table']);
	print_table_header($vbphrase['import_settings_xml_file']);
	print_upload_row($vbphrase['upload_xml_file'], 'settingsfile', 999999999);
	print_input_row($vbphrase['import_xml_file'], 'serverfile', './install/vbulletin-settings.xml');
	print_table_default_footer($vbphrase['import']);
}

// #################### Start Change Search Type #####################
if ($_REQUEST['do'] == 'searchtype')
{
	if (!$userContext->hasAdminPermission('canadminsettingsall'))
	{
		print_cp_no_permission();
	}

	print_form_header('admincp/options', 'dosearchtype');
	print_table_header("$vbphrase[search_type_gcpglobal]");

	print_select_row($vbphrase["select_search_implementation"], 'implementation',
		fetch_search_implementation_list(), $vbulletin->options['searchimplementation']);

	print_description_row($vbphrase['search_reindex_required']);
	print_submit_row($vbphrase['go'], 0);
}

// #################### Start Change Search Type #####################
if ($_POST['do'] == 'dosearchtype')
{
	if (!$userContext->hasAdminPermission('canadminsettingsall'))
	{
		print_cp_no_permission();
	}

	$vbulletin->input->clean_array_gpc('p', array(
		'implementation' => vB_Cleaner::TYPE_NOHTML
	));

	$changeSearch = vB_Api::instance('Options')->changeSearchType($vbulletin->GPC['implementation']);
	if (isset($changeSearch['errors']))
	{
		print_stop_message2($changeSearch['errors'][0]);
	}
	else
	{
		print_stop_message2('saved_settings_successfully', 'index');
	}
}

// #################### Spam Management Quick Page #####################
if ($_REQUEST['do'] == 'spam')
{
	if (!$userContext->hasAdminPermission('canadminsettingsall') AND !$userContext->hasAdminPermission('canadminsettings'))
	{
		print_cp_no_permission();
	}
	global $settingscache;

	$settingscache = [];
	$settings = vB::getDbAssertor()->assertQuery('vBForum:fetchSettingsByGroup', []);

	foreach ($settings AS $setting)
	{
		$settingscache["$setting[grouptitle]"]["$setting[varname]"] = $setting;
	}
	// show selected settings

	register_js_phrase('error_confirmation_phrase');
	print_form_header2('admincp/options', 'dooptions', ['js-setting-form'], ['name' => 'optionsform',]);
	//about 90% sure that the id isn't necessary here.
	print_table_start2([], ['id' => 'optionsform_table']);

	construct_hidden_code('spam', 1);

	echo "<thead>\r\n";
	print_table_header($vbphrase['akismet_settings']);
	echo "</thead>\r\n";

	print_description_row($vbphrase['akismet_desc']);

	$forumSpamCount = 0;
	foreach ($settingscache['spam_management'] AS $settingid => $setting)
	{
		$foundForumSpam = false;
		if (stripos($setting['varname'], 'vb_antispam_sfs') !== false)
		{
			$forumSpamCount++;
		}

		if ($forumSpamCount == 1)
		{
			$foundStopForumSpam = false;
			print_table_break();

			print_column_style_code(['width:45%', 'width:55%']);
			echo "<thead>\r\n";
			print_table_header($vbphrase['stopforumspam_settings']);
			echo "</thead>\r\n";

			print_description_row($vbphrase['stopforumspam_desc']);
		}

		if ($setting['varname'] == 'vb_antispam_badwords')
		{
			print_table_break();

			print_column_style_code(['width:45%', 'width:55%']);
			echo "<thead>\r\n";
			print_table_header($vbphrase['automoderation_settings']);
			echo "</thead>\r\n";

			print_description_row($vbphrase['automoderation_desc']);
		}

		if (!empty($setting['varname']))
		{
			print_setting_row($setting, $settingphrase);
		}
	}

	print_submit_row($vbphrase['save']);
	echo '<script type="text/javascript" src="core/clientscript/vbulletin_settings_validate.js?v=' .  SIMPLE_VERSION . '"></script>';
}

function fetch_search_implementation_list()
{
	// Legacy Hook 'admin_search_options' Removed //
	// See hookSearchOptions

	return vB_Library::instance('options')->getSearchImplementations();
}

print_cp_footer();

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 115966 $
|| #######################################################################
\*=========================================================================*/
