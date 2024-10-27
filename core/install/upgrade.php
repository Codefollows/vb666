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
ignore_user_abort(true);

// ##################### DEFINE IMPORTANT CONSTANTS #######################
define('NO_IMPORT_DOTS', true);
if (!defined('VB_AREA')) { define('VB_AREA', 'Upgrade'); }
if (!defined('VB_ENTRY')) { define('VB_ENTRY', 'upgrade.php'); }

//language really isn't used here any longer.  But is needed deeper in the code.  Should
//move it to were it is used and/or consolidate with the options file.
require_once(__DIR__ . '/includes/language.php');
require_once(__DIR__ . '/install_versions.php');

if (!function_exists('version_compare') OR version_compare(PHP_VERSION, $install_versions['install_php_version'], '<'))
{
/*
## This check is here on purpose, do not remove it ##
This is because on older versions the code will die before it reaches the standard check on minimum version
*/
	echo sprintf($install_version_error, $install_versions['install_php_version'], $install_versions['php_required']);
	exit;
}

//Default library initialization.
$library = '';

// Save for later CLI Processing
// Don't set the version/only options for an install. They're probably harmless but they aren't applicable
// and we need to ensure it doesn't mess up the shared logic if they are present.
// Allow the library option.
if (!empty($argv) AND count($argv) > 1)
{
	$options = getopt('', ['version::', 'only::', 'library::']);
	//If library option is available then sets it.
	if (!empty($options['library']))
	{
		$library = $options['library'];
	}
}

if (is_link(dirname($_SERVER["SCRIPT_FILENAME"])))
{
	$frontendConfigPath = dirname(dirname(dirname($_SERVER["SCRIPT_FILENAME"]))) . '/config.php';
	$backendConfigPath = dirname(dirname($_SERVER["SCRIPT_FILENAME"])) . '/includes/config.php';
}
else
{
	$frontendConfigPath = __DIR__  . '/../../config.php';
	$backendConfigPath = __DIR__  . '/../includes/config.php';
}

require_once(__DIR__ . '/includes/class_upgrade.php');

$makeConfigPath = dirname(__FILE__) . '/makeconfig.php';
// Only if we don't have one of the files
if (
	file_exists($makeConfigPath) AND
	(!file_exists($frontendConfigPath) OR !file_exists($backendConfigPath))
)
{
	if (vB_Upgrade::isCLI())
	{
		echo 'Configuration: Either config.php or core/includes/config.php do not exist.' . "\n";
	}
	else
	{
		require_once(__DIR__  . '/makeconfig.php');
	}
	exit;
}

// ########################## REQUIRE BACK-END ############################


require_once(__DIR__ . '/init.php');
require_once(DIR . '/includes/functions.php');

if (VB_AREA == 'Upgrade' AND $db->is_valid())
{
	$db->hide_errors();
	$db->query_first("SELECT * FROM " . TABLE_PREFIX . "user LIMIT 1");
	if ($db->errno())
	{
		if (!vB_Upgrade::isCLI())
		{
			exec_header_redirect('install.php');
		}
		else
		{
			echo $phrases['upgrade']['no_database_found'] . "\n";
			exit;
		}
	}
}

// install/upgrader need vB_Cache_Null implementation
$vb5_config =& vB::getConfig();
if (!isset($vb5_config['Cache']['class']) OR !is_array($vb5_config['Cache']['class']))
{
	$vb5_config['Cache']['class'] = ['vB_Cache_Null', 'vB_Cache_Null', 'vB_Cache_Null'];
}

$cache = $vb5_config['Cache']['class'];
foreach ($cache AS $key => $class)
{
	// backup the original class so we can revert this change when required (see class_upgrade_final)
	$vb5_config['Backup']['Cache']['class'][$key] = $class;
	$vb5_config['Cache']['class'][$key] = 'vB_Cache_Null';
}

// Reset all cache types
vB_Cache::resetAllCache();

$verify =& vB_Upgrade::fetch_library($vbulletin, $phrases, $library, !defined('VBINSTALL'));

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 116483 $
|| #######################################################################
\*=========================================================================*/
