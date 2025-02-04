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
define('CVS_REVISION', '$RCSfile$ - $Revision: 108363 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
global $phrasegroups, $specialtemplates, $vbphrase, $vbulletin;
$phrasegroups = array();
$specialtemplates = array();

// ########################## REQUIRE BACK-END ############################
require_once(dirname(__FILE__) . '/global.php');

// ############################# LOG ACTION ###############################
log_admin_action();

// #############################################################################
// ########################### START MAIN SCRIPT ###############################
// #############################################################################

if (!vB::getUserContext()->hasAdminPermission('canadminsettings') AND !vB::getUserContext()->hasAdminPermission('canadminsettingsall'))
{
	print_cp_no_permission();
}

print_cp_header($vbphrase['api']);

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'key';
}

if (in_array($_REQUEST['do'], array('key')))
{
	if (!$vbulletin->options['enableapi'])
	{
		print_warning_table($vbphrase['api_disabled_options']);
	}
}

// ###################### Start API Key #######################
if ($_REQUEST['do'] == 'key')
{
	if (!$vbulletin->options['apikey'])
	{
		print_form_header('admincp/api', 'newkey');
		print_table_header($vbphrase['api_key']);
		print_description_row($vbphrase['api_key_empty']);
		print_submit_row($vbphrase['go'], '');
	}
	else
	{
		print_table_start();
		print_table_header($vbphrase['api_key']);
		print_label_row(
		$vbphrase['api_key'],
		"<div id=\"ctrl_apikey\"><input type=\"text\" class=\"bginput\" name=\"apikey\" id=\"apikey\" value=\"" . $vbulletin->options['apikey'] . "\" size=\"35\" dir=\"\" tabindex=\"1\" readonly=\"readonly\" /></div>",
		'', 'top', 'apikey'
		);
		print_description_row($vbphrase['api_key_description']);
		print_table_footer(2, '', '', false);
	}
}

// ###################### Start Generate API Key #######################
if ($_REQUEST['do'] == 'newkey')
{
	if ($vbulletin->options['apikey'])
	{
		print_stop_message2('already_has_api_key');
	}

	vB_Library::instance('api')->generateAPIKey();
	print_stop_message2('api_key_generated_successfully', 'api');
}

print_cp_footer();

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 108363 $
|| #######################################################################
\*=========================================================================*/
