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

global $vbphrase, $phrasegroups, $vbulletin, $specialtemplates;

// identify where we are
define('VB_AREA', 'AdminCP');
if(!defined('VB_ENTRY'))
{
	define('VB_ENTRY', 1);
}
define('IN_CONTROL_PANEL', true);

if (!isset($phrasegroups) OR !is_array($phrasegroups))
{
	$phrasegroups = array('global');
}
if (!in_array('global', $phrasegroups))
{
	$phrasegroups[] = 'global';
}
$phrasegroups[] = 'cpglobal';

if (!isset($specialtemplates) OR !is_array($specialtemplates))
{
	$specialtemplates = array('mailqueue');
}

// ###################### Start functions #######################

require_once(__DIR__ . '/../includes/init.php');
require_once(DIR . '/includes/adminfunctions.php');

set_exception_handler(function($e)
{
	try
	{
		$errors = array();
		if($e instanceof vB_Exception_Api)
		{
			$errors = $e->get_errors();
			$config = vB::getConfig();
			if (!empty($config['Misc']['debug']))
			{
				$trace = '## ' . $e->getFile() . '(' . $e->getLine() . ") Exception Thrown \n" . $e->getTraceAsString();
				$errors[] = array("exception_trace", $trace);
			}
			print_stop_message_array($errors);
		}

		else if ($e instanceof vB_Exception_Database)
		{
			$config = vB::getConfig();
			if (!empty($config['Misc']['debug']) OR vB::getUserContext()->hasAdminPermission('cancontrolpanel'))
			{
				$errors = array('Error ' . $e->getMessage());
				$trace = '## ' . $e->getFile() . '(' . $e->getLine() . ") Exception Thrown \n" . $e->getTraceAsString();
				$errors[] = array("exception_trace", $trace);
				print_stop_message_array($errors);
			}
			else
			{
				// This text is purposely hard-coded since we don't have
				// access to the database to get a phrase
				print_cp_message('There has been a database error, and the current page cannot be displayed. Site staff have been notified.');
			}
		}
		else
		{
			$errors = array(array('unexpected_error', $e->getMessage()));
			$config = vB::getConfig();
			if (!empty($config['Misc']['debug']))
			{
				$trace = '## ' . $e->getFile() . '(' . $e->getLine() . ") Exception Thrown \n" . $e->getTraceAsString();
				$errors[] = array("exception_trace", $trace);
			}
			print_stop_message_array($errors);
		}
	}
	//if the above throws and exception we're cooked -- just do what we can
	catch (Error $e2)
	{
		print_cp_message('Got error "' . $e2->getMessage() . '" while trying to process error "' . $e->getMessage() . '"');
	}
	//if the above throws and exception we're cooked -- just do what we can
	catch (Exception $e2)
	{
		print_cp_message('Got error "' . $e2->getMessage() . '" while trying to process error "' . $e->getMessage() . '"');
	}
});

$config = vB::getConfig();
if (!empty($config['Security']['AdminIP']))
{
	$cpips = $config['Security']['AdminIP'];
	if (!is_array($cpips))
	{
		$cpips = explode(',', $cpips);
	}

	$ip = vB::getRequest()->getIpAddress();
	if (!vB_Ip::ipInArray($ip, $cpips))
	{
		print_stop_message2('no_permission');
	}
}

//we no longer double load the session which means that the previous session was
//loaded before we got to the admincp code.  So we fix some things.
//We really need to better control the loading of user information to avoid
//having to do this, but that requires considerable cleanup in the the
//admincp.
$session = vB::getCurrentSession();

$session->clearUserInfo();
$vbulletin->userinfo = &$session->fetch_userinfo();


$vb5_config =& vB::getConfig();
$assertor = vB::getDbAssertor();

// ###################### Start headers (send no-cache) #######################
exec_nocache_headers();

# cache full permissions so scheduled tasks will have access to them
$permissions = cache_permissions($vbulletin->userinfo);
$vbulletin->userinfo['permissions'] =& $permissions;

$usercontext = vB::getUserContext();
$checkpwd = (
	// this checks for superadmins, basic admin control and administrator table
	// administrator table has adminpermissions = 0 ?!?
	!$usercontext->hasAdminPermission('cancontrolpanel') AND
	// this checks for datastore (not sure what this means)
	!$usercontext->hasPermission('adminpermissions', 'cancontrolpanel')
);


// ###################### Get date / time info #######################
// override date/time settings if specified
fetch_options_overrides($vbulletin->userinfo);
fetch_time_data();

// ############################################ LANGUAGE STUFF ####################################
// initialize $vbphrase and set language constants
vB_Language::preloadPhraseGroups($phrasegroups);
$vbphrase = init_language();
//its not clear that we need this query.  The only thing that the function uses is the styleid
//which we already have and we're calling the function if we find the record or not.
if ($stylestuff = $assertor->getRow('vBForum:style', ['styleid' => $vbulletin->options['styleid']], []))
{
	fetch_stylevars($stylestuff, $vbulletin->userinfo);
}
else
{
	//not sure if this case can actually be tripped.
	fetch_stylevars(['styleid' => $vbulletin->options['styleid']], $vbulletin->userinfo);
}

// ############################################ Check for files existance ####################################
if (empty($vb5_config['Misc']['debug']) and !defined('BYPASS_FILE_CHECK'))
{
	// check for files existance. Potential security risks!
	$continue = false;
	if (is_dir(DIR . '/install') == true)
	{
		if ($_SERVER['REQUEST_METHOD'] == 'GET')
		{
			$continue = $vbulletin->scriptpath;
		}
		print_stop_message2(array('security_alert_x_still_exists'));
	}
	else if (file_exists(DIR . '/admincp/tools.php'))
	{
		if ($_SERVER['REQUEST_METHOD'] == 'GET')
		{
			$continue = $vbulletin->scriptpath;
		}
		print_stop_message2(array('security_alert_tools_still_exists_in_x',  'admincp'));
	}
	else if (file_exists(DIR . '/' . $vb5_config['Misc']['modcpdir'] . '/tools.php'))
	{
		if ($_SERVER['REQUEST_METHOD'] == 'GET')
		{
			$continue = $vbulletin->scriptpath;
		}
		print_stop_message2(array('security_alert_tools_still_exists_in_x',  $vb5_config['Misc']['modcpdir']),NULL,array(),NULL, $continue);
	}
}

// ############################################ Start Login Check ####################################
$vbulletin->input->clean_array_gpc('p', array(
	'adminhash' => vB_Cleaner::TYPE_STR,
	'ajax'      => vB_Cleaner::TYPE_BOOL,
));

//a bunch of files in the admincp assume that the "do" params of one or both of these arrays
//exist.  Changing the arrays is bad form and we should probably come up with a way to avoid
//direct references.  But that's a lot of code to change for little real benefit.
$_REQUEST['do'] = $_REQUEST['do'] ?? '';
$_POST['do'] = $_POST['do'] ?? '';

assert_cp_sessionhash();

if (!CP_SESSIONHASH OR $checkpwd OR ($vbulletin->options['timeoutcontrolpanel'] AND !vB::getCurrentSession()->get('loggedin')))
{
	// #############################################################################
	// Put in some auto-repair ;)

	$check = $assertor->getColumn('datastore', 'title', []);
	$check = array_flip($check);

	$datastore = vB::getDatastore();

	//the format is
	//datastore key => [updatefunction, params]
	//if more complicated update is require either use an anonymous function
	//or just don't use the table.
	$rebuild = [
		'maxloggedin' => [[$datastore, 'build'], ['maxloggedin', '', 1]],
		'mailqueue' => [[$datastore, 'build'], ['mailqueue', '', 0]],
		'cron' => [[$datastore, 'build'], ['cron', '', 0]],
		'attachmentcache' => [[$datastore, 'build'], ['attachmentcache', '', 1]],
		'wol_spiders' => [[$datastore, 'build'], ['wol_spiders', '', 1]],

		//Not sure what's up with this.  As far as I can tell this is always a copy of the value
		//in the options datastore array and carefully maintained as such.  I'm not sure why
		//we don't just consistantly reference the option and avoid the hassel.
		//But going to root it all out at the present time
		'banemail' => [[$datastore, 'build'], ['banemail', $datastore->getOption('banemail'), 0]],

		'smiliecache' => ['build_image_cache', ['smilie']],
		'iconcache' => ['build_image_cache', ['icon']],
		'bbcodecache' => ['build_bbcode_cache', []],
		'loadcache' => ['update_loadavg', []],

		'userstats' => [[vB_Library::instance('user'), 'buildStatistics'], []],
		'stylecache' => [[vB_Library::instance('style'), 'buildStyleDatastore'], []],
		'usergroupcache' => [[vB_Library::instance('usergroup'), 'buildDatastore'], []],
	];

	foreach($rebuild AS $title => $fun)
	{
		if(!isset($check[$title]))
		{
			call_user_func_array($fun[0], $fun[1]);
		}
	}

	// end auto-repair
	// #############################################################################

	print_cp_login();
}
else if ($_POST['do'] AND ADMINHASH != $vbulletin->GPC['adminhash'])
{
	print_cp_login(true);
}

if (file_exists(DIR . '/includes/version_vbulletin.php'))
{
	include_once(DIR . '/includes/version_vbulletin.php');
}

if (defined('FILE_VERSION_VBULLETIN') AND FILE_VERSION_VBULLETIN !== '')
{
	define('ADMIN_VERSION_VBULLETIN', FILE_VERSION_VBULLETIN);
}
else
{
	define('ADMIN_VERSION_VBULLETIN', $vbulletin->options['templateversion']);
}

// Legacy Hook 'admin_global' Removed //

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 116483 $
|| #######################################################################
\*=========================================================================*/
