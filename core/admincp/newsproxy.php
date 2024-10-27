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
define('CVS_REVISION', '$RCSfile$ - $Revision: 112702 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
global $phrasegroups, $specialtemplates;
$phrasegroups = [];
$specialtemplates = [];

// ########################## REQUIRE BACK-END ############################
require_once(dirname(__FILE__) . '/global.php');

header('Content-Type: text/xml; charset=utf-8');

//allow overriding the license ID in the config, mostly for dev purposes
$config = vB::getConfig();
$licenseid = $config['Misc']['licenseid'] ?? 'LN05842122';

$url = 'https://version.vbulletin.com/news.xml?v=' . SIMPLE_VERSION . "&id=$licenseid";
$data = ['type' => ''];

$vurl = vB::getUrlLoader();
$vurl->setOption(vB_Utility_Url::FOLLOWLOCATION, 1);
$vurl->setOption(vB_Utility_Url::TIMEOUT, 5);
$vurl->setOption(vB_Utility_Url::ENCODING, 'gzip');
$result = $vurl->post($url, $data);

if ($result)
{
	echo $result['body'];
}
else
{
	echo 'Error';
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 112702 $
|| #######################################################################
\*=========================================================================*/
