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

define('VB_REQUEST_START_TIME', microtime(true));

if (!defined('VB_ENTRY'))
{
	define('VB_ENTRY', 1);
}

// Check for cached image calls to filedata/fetch?
if (
	isset($_REQUEST['routestring']) AND
	$_REQUEST['routestring'] == 'filedata/fetch' AND
	(!empty($_SERVER['HTTP_IF_MODIFIED_SINCE']) OR !empty($_SERVER['HTTP_IF_NONE_MATCH']))
)
{
	http_response_code(304);
}

require_once('includes/vb5/autoloader.php');
vB5_Autoloader::register(__DIR__);

//For a few set routes we can run a streamlined function.
if (vB5_Frontend_ApplicationLight::isQuickRoute())
{
	$app = vB5_Frontend_ApplicationLight::init(__DIR__);
	vB5_Frontend_ExplainQueries::initialize();
	if ($app->execute())
	{
		vB5_Frontend_ExplainQueries::finish();
		exit();
	}
}

$app = vB5_Frontend_Application::init(__DIR__);
$config = vB5_Config::instance();
if (!$config->report_all_php_errors)
{
	// Note that E_STRICT became part of E_ALL in PHP 5.4
	error_reporting(error_reporting() & ~(E_NOTICE | E_STRICT));
}

$routing = $app->getRouter();
$method = $routing->getAction();
$class = $routing->getControllerClass();

if (!class_exists($class))
{
	// @todo - this needs a proper error message
	die("Couldn't find controller file for $class");
}

vB5_Frontend_ExplainQueries::initialize();
$c = new $class();
call_user_func_array([&$c, $method], array_values($routing->getArguments()));

vB5_Frontend_ExplainQueries::finish();

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 111777 $
|| #######################################################################
\*=========================================================================*/
