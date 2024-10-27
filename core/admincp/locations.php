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
define('CVS_REVISION', '$RCSfile$ - $Revision: 99787 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
global $phrasegroups, $specialtemplates, $vbphrase, $vbulletin;
$phrasegroups = ['cpoption'];
$specialtemplates = [];

// ########################## REQUIRE BACK-END ############################
require_once(dirname(__FILE__) . '/global.php');

// ######################## CHECK ADMIN PERMISSIONS #######################
$userContext = vB::getUserContext();
if (!$userContext->hasAdminPermission('canadminsettingsall') AND !$userContext->hasAdminPermission('canadminsettings'))
{
	print_cp_no_permission();
}

// ############################# LOG ACTION ###############################
log_admin_action();

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################
$assertor = vB::getDbAssertor();

print_cp_header($vbphrase['location_manager']);
print_cp_description($vbphrase, 'locations', $_REQUEST['do']);

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'modify';
}

// ###################### Start edit #######################
if ($_REQUEST['do'] == 'edit' OR $_REQUEST['do'] == 'add')
{
	$optionApi = vB_Api::instanceInternal('options');

	if($_REQUEST['do'] == 'edit')
	{
		$vbulletin->input->clean_array_gpc('r', array(
			'locationid' => vB_Cleaner::TYPE_UINT,
		));
		$location = $optionApi->getLocation($vbulletin->GPC['locationid']);
		$location = $location['location'];
	}
	else
	{
		//set some defaults for the add action
		$location = array(
			'locationid' => '',
			'title' => '',
			'locationcodes' => array(),
		);
	}

	$existingCodesMap = array_flip($location['locationcodes']);

	$countries = $optionApi->getCountryCodes();
	$countries = $countries['codes'];

	print_form_header('admincp/locations', 'dosave');
	construct_hidden_code('locationid', $location['locationid']);

	print_table_header($vbphrase['location']);
	print_input_row($vbphrase['title'], 'title', $location['title']);
	print_yes_no_row($vbphrase['include_unknown_location'], 'include_unknown', isset($existingCodesMap['UNKNOWN']) ? 1 : 0);
	unset($existingCodesMap['UNKNOWN']);
	print_yes_no_row($vbphrase['include_allusers_location'], 'include_allusers', isset($existingCodesMap['ALLUSERS']) ? 1 : 0);
	unset($existingCodesMap['ALLUSERS']);
	print_table_break();

	$columns = 5;
	print_table_header($vbphrase['country_codes'], $columns);


	$cells = array();
	foreach($countries AS $code => $name)
  {
		$cells[] = construct_checkbox_control("$code ($name)", 'location_codes[]', isset($existingCodesMap[$code]), $code);
		unset($existingCodesMap[$code]);
	}
	print_cellgrid_columns($cells, $columns);
	print_table_break();

	//these are the regions that we want display subregions for.  Currently only the US is supported by the
	//underlying API functions (though the logic won't break if unsupported countries are added here).
	//This is all laid out so that we can add additional countries, but the details of how to handle non
	//US regions needs to be worked out.
	$sub_locations = array('US');

	//unfortunately print_submit_row also calls print_table_footer, so we need to call
	//this after every loop *except* the last one.  There isn't a good way of doing this.
	foreach($sub_locations AS $countrycode)
	{
		$regions = $optionApi->getRegionCodes($countrycode);
		$regions = $regions['codes'];

		if($regions)
		{
			print_table_header($countries[$countrycode], $columns);

			$cells = array();
			foreach($regions AS $code => $name)
			{
				$cells[] = construct_checkbox_control("$code ($name)", 'location_codes[]', isset($existingCodesMap[$code]), $code);
				unset($existingCodesMap[$code]);
			}
			print_cellgrid_columns($cells, $columns);
			print_table_break();
		}
	}

	$additional = implode("\n", array_keys($existingCodesMap));
	print_table_header('', $columns);
	print_textarea_row($vbphrase['additional_location_codes'], 'additional_location_codes', $additional, 10);

	print_submit_row('', false, $columns);
}

// ###################### Start do update #######################
if ($_POST['do'] == 'dosave')
{
		$vbulletin->input->clean_array_gpc('p', array(
			'locationid' => vB_Cleaner::TYPE_UINT,
			'location_codes' => vB_Cleaner::TYPE_ARRAY,
			'additional_location_codes' => vB_Cleaner::TYPE_NOHTML,
			'title' => vB_Cleaner::TYPE_NOHTML,
			'include_unknown' => vB_Cleaner::TYPE_BOOL,
			'include_allusers' => vB_Cleaner::TYPE_BOOL,
		));

		$data = array();
		if($vbulletin->GPC['locationid'])
		{
			$data['locationid'] = $vbulletin->GPC['locationid'];
		}

		$data['title'] = $vbulletin->GPC['title'];
		$data['locationcodes'] = $vbulletin->GPC['location_codes'];

		if($vbulletin->GPC['include_unknown'])
		{
			$data['locationcodes'][] = 'UNKNOWN';
		}

		if($vbulletin->GPC['include_allusers'])
		{
			$data['locationcodes'][] = 'ALLUSERS';
		}


		if($vbulletin->GPC['additional_location_codes'])
		{
			//we want things delimited by line, but let's also quitely allow CSV because somebody
			//is going to do it that way.
			$additional = array();
			foreach(explode("\n",  $vbulletin->GPC['additional_location_codes']) AS $line)
			{
				$additional = array_merge($additional, explode(',', $line));
			}

			$additional = array_filter(array_map('trim', $additional));
			//if there is an error in the above and we end up without an array value, then this merge
			//will wipe out the values we already have.  Which is bad.
			if(is_array($additional))
			{
				$data['locationcodes'] = array_merge($data['locationcodes'], $additional);
			}
		}

		$optionApi = vB_Api::instanceInternal('options');
		$locationid = $optionApi->saveLocation($data);

		print_stop_message2('saved_location_successfully', 'locations', array('do'=>'modify'));
}

// ###################### Start Remove #######################

if ($_REQUEST['do'] == 'remove')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'locationid' => vB_Cleaner::TYPE_UINT,
	));

	$optionApi = vB_Api::instanceInternal('options');
	$location = $optionApi->getLocation($vbulletin->GPC['locationid']);
	$location = $location['location'];

	print_form_header('admincp/locations', 'kill');
	construct_hidden_code('locationid', $location['locationid']);
	print_table_header($vbphrase['confirm_deletion_gcpglobal']);
	print_description_row(construct_phrase($vbphrase['are_you_sure_you_want_to_delete_location_x'], $location['title']));
	print_submit_row($vbphrase['yes'], '', 2, $vbphrase['no']);
}

// ###################### Start Kill #######################

if ($_POST['do'] == 'kill')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'locationid' => vB_Cleaner::TYPE_UINT,
	));

	$optionApi = vB_Api::instanceInternal('options');
	$optionApi->deleteLocation($vbulletin->GPC['locationid']);
	print_stop_message2('deleted_location_successfully', 'locations', array('do'=>'modify'));
}

// ###################### Start modify #######################
if ($_REQUEST['do'] == 'modify')
{
	print_form_header('admincp/locations', 'add');

	$title_cells  = array(
		$vbphrase['title'],
		$vbphrase['controls']
	);

	print_table_header($vbphrase['locations'], count($title_cells));
	print_cells_row2($title_cells, 'thead');

	$optionApi = vB_Api::instanceInternal('options');
	$locations = $optionApi->getLocationList();

	$align = array(
		vB_Template_Runtime::fetchStyleVar('left'),
		vB_Template_Runtime::fetchStyleVar('right'),
	);

	foreach($locations['locations'] AS $location)
	{
		$editurl = 'locations.php?do=edit&locationid=' . $location['locationid'];
		$deleteurl = 'locations.php?do=remove&locationid=' . $location['locationid'];

		$cells = array(
			$location['title'],
			'<div class="smallfont">' .
				construct_link_code($vbphrase['edit'], htmlspecialchars($editurl)) .
				construct_link_code($vbphrase['delete'], htmlspecialchars($deleteurl)) .
			'</div>'
		);

		print_cells_row2($cells);
	}

	print_submit_row($vbphrase['add_new_location'], false, count($title_cells));
}

print_cp_footer();

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 99787 $
|| #######################################################################
\*=========================================================================*/
