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

// ####################### SET PHP ENVIRONMENT ###########################

// #################### DEFINE IMPORTANT CONSTANTS #######################
define('THIS_SCRIPT', 'login');
//define('CSRF_PROTECTION', true);
define('CSRF_SKIP_LIST', 'login');
define('CONTENT_PAGE', false);
if (!defined('VB_ENTRY'))
{
	define('VB_ENTRY', 1);
}
// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
global $phrasegroups, $specialtemplates, $globaltemplates, $actiontemplates, $show;
$phrasegroups = ['global', 'cpglobal'];

// get special data templates from the datastore
$specialtemplates = array();

// pre-cache templates used by all actions
$globaltemplates = array();

// pre-cache templates used by specific actions
$actiontemplates = array(
	'lostpw' => array(
		'lostpw',
		'humanverify'
	)
);

//init the language since it doesn't quite work here otherwise.
global $vbulletin, $vbphrase;
$session = vB::getCurrentSession();
$session->clearUserInfo();

//init language logic salvaged from the legacy bootstrap
require_once(dirname(__FILE__) . '/includes/init.php');
fetch_options_overrides($vbulletin->userinfo);
fetch_time_data();
vB_Language::preloadPhraseGroups($phrasegroups);
$vbphrase = init_language();

//init style logic salvaged from the legacy bootstrap
//as far as I can tell we only need this for the charset because, for not so great reasons,
//we read this from the stylevars.  And only sometimes.
$styleid = $vbulletin->userinfo['styleid'];
$style = vB_Library::instance('style')->getStyleById($styleid);
$vbulletin->stylevars = $style['newstylevars'];
fetch_stylevars($style, $vbulletin->userinfo);

require_once(DIR . '/includes/adminfunctions.php');

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

if (empty($_REQUEST['do']))
{
	exec_header_redirect(vB5_Route::buildHomeUrl('fullurl'));
}

// ############################### start do login ###############################
// this was a _REQUEST action but where do we all login via request?
if ($_POST['do'] == 'login')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'vb_login_username'        => vB_Cleaner::TYPE_STR,
		'vb_login_password'        => vB_Cleaner::TYPE_STR,
		'vb_login_md5password'     => vB_Cleaner::TYPE_STR,
		'vb_login_md5password_utf' => vB_Cleaner::TYPE_STR,
		'vb_login_mfa_authcode'    => vB_Cleaner::TYPE_NOHTML,
		'postvars'                 => vB_Cleaner::TYPE_BINARY,
		'cookieuser'               => vB_Cleaner::TYPE_BOOL,
		'logintype'                => vB_Cleaner::TYPE_STR,
		'cssprefs'                 => vB_Cleaner::TYPE_STR,
		'inlineverify'             => vB_Cleaner::TYPE_BOOL,
	));

	$passwords = array(
		'password' => $vbulletin->GPC['vb_login_password'],
		'md5password' => $vbulletin->GPC['vb_login_md5password'],
		'md5password_utf' => $vbulletin->GPC['vb_login_md5password_utf'],
	);

	$extraAuth = array(
		'mfa_authcode' => $vbulletin->GPC['vb_login_mfa_authcode'],
	);

	$userApi = vB_Api::instance('user');
	$res = $userApi->login2($vbulletin->GPC['vb_login_username'], $passwords, $extraAuth, $vbulletin->GPC['logintype']);
	cache_permissions($vbulletin->userinfo);

	if (isset($res['errors']))
	{
		//we need the header here now that we know we won't be setting cookies.
		print_cp_header();

		$error = $res['errors'][0];
		$errorid = $error[0];
		$knownloginerror = (strpos($errorid, 'badmfa') === 0 OR strpos($errorid, 'badlogin') === 0 OR $errorid == 'strikes');

		//we should only be using this for a cp login at this point, but leaving this check in
		//in an abundance of caution.  Note that this redirect doesn't handle general errors
		//so we need to check if its one of the one's we do handle.  Otherwise use a more generic
		//error display below.
		if ($knownloginerror AND $vbulletin->GPC['logintype'] === 'cplogin' OR $vbulletin->GPC['logintype'] === 'modcplogin')
		{
			$url = unhtmlspecialchars($vbulletin->url);

			$urlarr = vB_String::parseUrl($url);

			$urlquery = $urlarr['query'] ?? '';

			$oldargs = array();
			if ($urlquery)
			{
				parse_str($urlquery, $oldargs);
			}

			$args = $oldargs;
			unset($args['loginerror']);

			$args['loginerror'] = $errorid;
			$args['vb_login_username'] = $vbulletin->GPC['vb_login_username'];
			addStrikeParams($error, $args);
			$argstr = http_build_query($args);

			$url = $urlarr['path'];

			if ($argstr)
			{
				$url .= '?' . $argstr;
			}

			print_cp_redirect(create_full_url($url));
		}

		print_stop_message2($error);
	}

	if ($vbulletin->GPC['logintype'] === 'cplogin')
	{
		vB_User::setAdminCss($res['userid'], $vbulletin->GPC['cssprefs']);
	}

	// set cookies (temp hack for admincp)
	if (isset($res['cpsession']))
	{
		vbsetcookie('cpsession', $res['cpsession'], false, true, true);
	}

	vbsetcookie('sessionhash', $res['sessionhash'], false, true, true);

	//this *has* to happen after the set cookies or bad things can happen.
	print_cp_header();
	do_login_redirect();
}
else if ($_GET['do'] == 'login')
{
	// add consistency with previous behavior
	exec_header_redirect(vB5_Route::buildHomeUrl('fullurl'));
}

//we need to extract the strikes value from the error return because
//we can't really pass the url as a url parameter for XSS reasons
//this is ugly because we really don't want to unroll and the rewrap
//the error message on other end but until we can remove the redirect
//their isn't much of an option.
function addStrikeParams($error, &$args)
{
	//not sure if we need to set the strikes to 0 but keeping prior behavior.
	$errorid = $error[0];

	//if this isn't a "strikes" error then we don't have strikes.
	if (strpos($errorid, 'strikes') === false)
	{
		$args['strikes'] = 0;
	}
	else if (strpos($errorid, 'badmfa') === 0)
	{
		$args['strikes'] = $error[1];
		$args['limit'] = $error[2];
	}
	else if (strpos($errorid, 'badlogin') === 0)
	{
		$args['strikes'] = $error[2];
		$args['limit'] = $error[3];
	}
	else
	{
		//note that the error "strikes" does not actually have any strikes to extract
		//but will pass the first check for strikes phrases.
		$args['strikes'] = 0;
	}
}

// ###################### Start do login redirect #######################
// Moved here from functions_login to consolidate legacy code in one plac
function do_login_redirect()
{
	global $vbulletin, $vbphrase;

	$vbulletin->input->fetch_basepath();

	if (
		preg_match('#login.php(?:\?|$)#', $vbulletin->url)
		OR strpos($vbulletin->url, 'do=logout') !== false
		OR (!$vbulletin->options['allowmultiregs'] AND strpos($vbulletin->url, $vbulletin->basepath . 'register.php') === 0)
	)
	{
		$forumHome = vB_Library::instance('content_channel')->getForumHomeChannel();
		$vbulletin->url = vB5_Route::buildUrl($forumHome['routeid'] . '|fullurl');
	}
	else
	{
		$vbulletin->url = addslashes($vbulletin->url );
		$vbulletin->url = preg_replace('#^/+#', '/', $vbulletin->url); // bug 3654 don't ask why
	}

	$temp = strpos($vbulletin->url, '?');
	if ($temp)
	{
		$formfile = substr($vbulletin->url, 0, $temp);
	}
	else
	{
		$formfile =& $vbulletin->url;
	}

	$postvars = $vbulletin->GPC['postvars'];


	// recache the global group to get the stuff from the new language
	$globalgroup = $vbulletin->db->query_first_slave("
		SELECT phrasegroup_global, languagecode, charset
		FROM " . TABLE_PREFIX . "language
		WHERE languageid = " . intval($vbulletin->userinfo['languageid'] ? $vbulletin->userinfo['languageid'] : $vbulletin->options['languageid'])
	);
	if ($globalgroup)
	{
		$vbphrase = array_merge($vbphrase, unserialize($globalgroup['phrasegroup_global']));
		if (vB_Template_Runtime::fetchStyleVar('charset') != $globalgroup['charset'])
		{
			// change the character set in a bunch of places - a total hack
			global $headinclude;

			$headinclude = str_replace(
				"content=\"text/html; charset=" . vB_Template_Runtime::fetchStyleVar('charset') . "\"",
				"content=\"text/html; charset=$globalgroup[charset]\"",
				$headinclude
			);

			vB_Template_Runtime::addStyleVar('charset', $globalgroup['charset'], 'imgdir');
			$vbulletin->userinfo['lang_charset'] = $globalgroup['charset'];

			exec_headers();
		}

		if ($vbulletin->GPC['postvars'])
		{
			$postvars = array();
			$client_string = verify_client_string($vbulletin->GPC['postvars']);
			if ($client_string)
			{
				$postvars = @json_decode($client_string, true);
			}

			if (($postvars['securitytoken'] ?? '') == 'guest')
			{
				$vbulletin->userinfo['securitytoken_raw'] = sha1($vbulletin->userinfo['userid'] . sha1($vbulletin->userinfo['secret']) . sha1(vB_Request_Web::$COOKIE_SALT));
				$vbulletin->userinfo['securitytoken'] = TIMENOW . '-' . sha1(TIMENOW . $vbulletin->userinfo['securitytoken_raw']);
				$postvars['securitytoken'] = $vbulletin->userinfo['securitytoken'];
				$vbulletin->GPC['postvars'] = sign_client_string(json_encode($postvars));
			}
		}

		vB_Template_Runtime::addStyleVar('languagecode', $globalgroup['languagecode']);
	}

	//this is only called for the cp login anymore.  And the other redirect branch had bad code.
	//so we'll just issue the cp redirect and call it a day.
	print_cp_redirect(create_full_url($vbulletin->url));
}

/**
* Sends the appropriate HTTP headers for the page that is being displayed
*
* @param	boolean	If true, send HTTP 200
* @param	boolean	If true, send no-cache headers
*/
//this used to be a general function but is now only used by do_login_redirect
function exec_headers()
{
	global $vbulletin;
	$options = vB::getDatastore()->getValue('options');
	$contenttype = 'text/html';

	$langcharset = $vbulletin->userinfo['lang_charset'];

	$sendcontent = true;
	if ($options['nocacheheaders'])
	{
		// no caching
		exec_nocache_headers($sendcontent);
	}
	else
	{
		@header("Cache-Control: private");
		@header("Pragma: private");
		$charset = ($langcharset ? $langcharset : vB_Template_Runtime::fetchStyleVar('charset'));
		@header('Content-Type: ' . $contenttype . '; charset=' . $charset);
	}
}



/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 115041 $
|| #######################################################################
\*=========================================================================*/
