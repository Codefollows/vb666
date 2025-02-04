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
error_reporting(error_reporting() & ~E_NOTICE);

// ##################### DEFINE IMPORTANT CONSTANTS #######################
define('CVS_REVISION', '$RCSfile$ - $Revision: 107437 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
$phrasegroups = array('help_faq');
$specialtemplates = array();

// ########################## REQUIRE BACK-END ############################
require_once(dirname(__FILE__) . '/global.php');
require_once(DIR . '/includes/adminfunctions_help.php');

// ############################# LOG ACTION ###############################
$vbulletin->input->clean_array_gpc('r', array('adminhelpid' => vB_Cleaner::TYPE_INT));
log_admin_action(iif($vbulletin->GPC['adminhelpid'] != 0, "help id = " . $vbulletin->GPC['adminhelpid']));

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'answer';
}

print_cp_header("$vbphrase[admin_help]");

// ############################### start listing answers ##############
if ($_REQUEST['do'] == 'answer')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'page'       => vB_Cleaner::TYPE_STR,
		'pageaction' => vB_Cleaner::TYPE_STR,
		'option'     => vB_Cleaner::TYPE_STR
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

	if ($strpos = strpos($fullpage, '?'))
	{
		$pagename = basename(substr($fullpage, 0, $strpos));
	}
	else
	{
		$pagename = basename($fullpage);
	}

	if ($strpos = strpos($pagename, '.'))
	{
		$pagename = substr($pagename, 0, $strpos); // remove the .php part as people may have different extensions
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

	if (empty($vbulletin->GPC['option']))
	{
		$vbulletin->GPC['option'] = NULL;
	}

	$helptopics = $db->query_read("
		SELECT *, LENGTH(action) AS length
		FROM " . TABLE_PREFIX . "adminhelp
		WHERE script = '" . $db->escape_string($pagename) . "' AND
			(action = '' OR FIND_IN_SET('" . $db->escape_string($action) . "', action))
			" . iif($vbulletin->GPC['option'] !== NULL, "AND
			optionname = '" . $db->escape_string($vbulletin->GPC['option']) . "'") . " AND
			displayorder <> 0
		ORDER BY length, displayorder
	");
	if (($resultcount = $db->num_rows($helptopics)) == 0)
	{
		print_stop_message2('no_help_topics');
	}
	else
	{
		$general = array();
		$specific = array();
		$phraseSQL = array();
		while ($topic = $db->fetch_array($helptopics))
		{
			$phrasename = $db->escape_string(fetch_help_phrase_short_name($topic));
			$phraseSQL[] = "'$phrasename" . "_title'";
			$phraseSQL[] = "'$phrasename" . "_text'";

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
		$phrases = $db->query_read("
			SELECT varname, text, languageid
			FROM " . TABLE_PREFIX . "phrase
			WHERE fieldname = 'cphelptext'
				AND languageid IN(-1, 0, " . LANGUAGEID . ")
				AND varname IN(\n" . implode(",\n", $phraseSQL) . "\n)
			ORDER BY languageid ASC
		");
		while($phrase = $db->fetch_array($phrases))
		{
			$helpphrase["$phrase[varname]"] = vB_Library::instance('Phrase')->replaceOptionsAndConfigValuesInPhrase($phrase['text']);
		}

		if ($resultcount != 1)
		{
			print_form_header('', '');
			print_table_header($vbphrase['quick_help_topic_links'], 1);
			if (sizeof($specific))
			{
				print_description_row($vbphrase['action_specific_topics'], 0, 1, 'thead');
				foreach ($specific AS $topic)
				{
					print_description_row('<a href="#help' . $topic['adminhelpid'] . '">' . $helpphrase[fetch_help_phrase_short_name($topic, '_title')] . '</a>', 0, 1);
				}
			}
			if (sizeof($general))
			{
				print_description_row($vbphrase['general_topics'], 0, 1, 'thead');
				foreach ($general AS $topic)
				{
					print_description_row('<a href="#help' . $topic['adminhelpid'] . '">' . $helpphrase[fetch_help_phrase_short_name($topic, '_title')] . '</a>', 0, 1);
				}
			}
			print_table_footer();
		}

		if (sizeof($specific))
		{
			reset($specific);
			print_form_header('', '');
			if ($resultcount != 1)
			{
				print_table_header($vbphrase['action_specific_topics'], 1);
			}
			foreach ($specific AS $topic)
			{
				print_description_row("<a name=\"help$topic[adminhelpid]\">" . $helpphrase[fetch_help_phrase_short_name($topic, '_title')] . "</a>", 0, 1, 'thead');
				print_description_row($helpphrase[fetch_help_phrase_short_name($topic, '_text')]);
			}
			print_table_footer();
		}

		if (sizeof($general))
		{
			reset($general);
			print_form_header('', '');
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

print_cp_footer();

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 107437 $
|| #######################################################################
\*=========================================================================*/
