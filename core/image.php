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
error_reporting(error_reporting() & ~E_NOTICE);

// #################### DEFINE IMPORTANT CONSTANTS #######################
define('NOSHUTDOWNFUNC', 1);
define('NOCOOKIES', 1);
define('THIS_SCRIPT', 'image');
define('CSRF_PROTECTION', true);
define('VB_AREA', 'Forum');
if (!defined('VB_ENTRY'))
{
	define('VB_ENTRY', 1);
}

if (
	(!empty($_SERVER['HTTP_IF_MODIFIED_SINCE']) OR !empty($_SERVER['HTTP_IF_NONE_MATCH'])) AND
	(!isset($_GET['type']) OR $_GET['type'] != 'regcheck')
)
{
	http_response_code(304);
	exit;
}

// #################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = [];

// get special data templates from the datastore
$specialtemplates = [];

// pre-cache templates used by all actions
$globaltemplates = [];

// pre-cache templates used by specific actions
$actiontemplates = [];

// ######################### REQUIRE BACK-END ############################
define('SKIP_SESSIONCREATE', 1);
define('SKIP_USERINFO', 1);
define('SKIP_DEFAULTDATASTORE', 1);

require_once(__DIR__ . '/includes/init.php');
$vbulletin->input->clean_array_gpc('r', [
	'type'   => vB_Cleaner::TYPE_STR,
	'thumb'   => vB_Cleaner::TYPE_BOOL,
	'userid' => vB_Cleaner::TYPE_UINT,
	'groupid' => vB_Cleaner::TYPE_UINT
]);

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

if ($vbulletin->GPC['userid'] == 0 AND $vbulletin->GPC['groupid'] == 0)
{
	$vbulletin->GPC['type'] = 'hv';
}

if ($vbulletin->GPC['type'] == 'hv')
{
	$vbulletin->input->clean_array_gpc('r', [
		'hash' => vB_Cleaner::TYPE_STR,
		'i'    => vB_Cleaner::TYPE_STR,
	]);

	$moveabout = true;
	if ($vbulletin->GPC['hash'] == '' OR $vbulletin->GPC['hash'] == 'test' OR vB::getDatastore()->getOption('hv_type') != 'Image')
	{
		$imageinfo = [
			'answer' => 'vBulletin',
		];

		$moveabout = $vbulletin->GPC['hash'] == 'test' ? true : false;
	}
	else if (!($imageinfo = vB::getDbAssertor()->getRow('humanverify', ['hash' => $vbulletin->GPC['hash'], 'viewed' => 0])))
	{
		header('Content-type: image/gif');
		readfile(DIR . '/clear.gif');
		exit;
	}
	else
	{
		$affected_rows = vB::getDbAssertor()->update('humanverify', ['viewed' => 1], ['hash' => $vbulletin->GPC['hash'], 'viewed' => 0]);
		if ($affected_rows == 0)
		{	// image managed to get viewed by someone else between the $imageinfo query above and now
			header('Content-type: image/gif');
			readfile(DIR . '/clear.gif');
			exit;
		}
	}

	$vboptions = vB::getDatastore()->getValue('options');
	// 'i' param should be gd or imagick
	switch ($vbulletin->GPC['i'])
	{
		case 'gd':
			$image = new vB_Image_GD($vboptions);
			break;
		case 'imagick':
			$image = new vB_Image_Imagick($vboptions);
			break;
		default:
			$image = vB_Image::instance('hv');
			break;
	}

	$imageInfo = $image->getImageFromString($imageinfo['answer'], $moveabout);

	header('Content-disposition: inline; filename=image.' . $imageInfo['filetype']);
	header('Content-transfer-encoding: binary');
	header('Content-Type: ' . $imageInfo['contentType']);
	header("Content-Length: " . $imageInfo['filesize']);
	echo $imageInfo['filedata'];
}
else if ($vbulletin->GPC['userid'])
{
	$vbulletin->input->clean_array_gpc('r', [
		'dateline' => vB_Cleaner::TYPE_UINT,
	]);

	$filedata = 'filedata';
	if (!$vbulletin->GPC['type'] == 'profile' AND ($vbulletin->GPC['type'] == 'thumb' OR !empty($vbulletin->GPC['thumb'])))
	{
		$filedata = 'filedata_thumb';
	}

	header('Cache-control: max-age=31536000');
	header('Expires: ' . gmdate('D, d M Y H:i:s', (TIMENOW + 31536000)) . ' GMT');

	$imageinfo = vB::getDbAssertor()->getRow('customavatar', [
		vB_dB_Query::CONDITIONS_KEY => [
			'userid' =>  $vbulletin->GPC['userid'],
			'visible' => 1,
   		['field' => $filedata, 'value' => '', 'operator' =>  vB_dB_Query::OPERATOR_NE],
		],
		vB_dB_Query::COLUMNS_KEY => [$filedata, 'dateline', 'filename']
	]);

	if ($imageinfo)
	{
		header('Content-disposition: inline; filename=' . $imageinfo['filename']);
		header('Content-Length: ' . strlen($imageinfo[$filedata]));
		header('ETag: "' . $imageinfo['dateline'] . '-' . $vbulletin->GPC['userid'] . '"');
		$extension = trim(substr(strrchr(strtolower($imageinfo['filename']), '.'), 1));
		if ($extension == 'jpg' OR $extension == 'jpeg')
		{
			header('Content-type: image/jpeg');
		}
		else if ($extension == 'png')
		{
			header('Content-type: image/png');
		}
		else if ($extension == 'webp')
		{
			header('Content-type: image/webp');
		}
		else
		{
			header('Content-type: image/gif');
		}
		echo $imageinfo[$filedata];
	}
	else
	{
		header('Content-disposition: inline; filename=default_avatar_large.png');
		header('Content-transfer-encoding: binary');
		header('Content-type: image/png');
		if ($filesize = @filesize(DIR . '/images/default/default_avatar_large.png'))
		{
			header('Content-Length: ' . $filesize);
		}
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s', 0) . ' GMT');
		readfile(DIR . '/images/default/default_avatar_large.png');
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 116483 $
|| #######################################################################
\*=========================================================================*/
