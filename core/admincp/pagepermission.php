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
define('CVS_REVISION', '$RCSfile$ - $Revision: 111008 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
global $phrasegroups, $specialtemplates, $vbphrase, $vbulletin;
$phrasegroups = ['cpglobal', 'cphome', 'cppermission',];
$specialtemplates = [];

// ########################## REQUIRE BACK-END ############################
require_once(dirname(__FILE__) . '/global.php');

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!can_administer('canadminpermissions'))
{
	print_cp_no_permission();
}

// ############################# LOG ACTION ###############################
log_admin_action();


// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

print_cp_header($vbphrase['page_permissions_manager'], '', [
	get_admincp_script_tag('vbulletin_paginate.js'),
]);


if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'list';
}

/** @var vB_Api_Page */
$api = vB_Api::instanceInternal('page');
/** @var vB_Library_Page */
$lib = vB_Library::instance('page');
$assertor = vB::getDbAssertor();

// print_form_header2() always appends '.php?do'...
$thispagelink = 'admincp/pagepermission';
$thispagedo = 'list';

function getPageAndPerPage($GPC)
{
	$page = max($GPC['page'], 1);
	// print_pagination_form() & associated JS expect perpage of 0 to be "Show All".
	// So we have to differentiate between "default because first load / never set" and
	// "show all"
	$perpage = max($GPC['perpage'], 0);
	if (!isset($_REQUEST['perpage']))
	{
		$perpage = 10;
	}

	return [
		'p' => $page,
		'pp' => $perpage,
	];
}


// ###################### Start List #######################
if ($_REQUEST['do'] == 'list')
{
	$vbulletin->input->clean_array_gpc('r', [
		'page'	  => vB_Cleaner::TYPE_INT,
		'perpage' => vB_Cleaner::TYPE_INT,
	]);
	['p' => $page, 'pp' => $perpage] = getPageAndPerPage($vbulletin->GPC);

	$apiResult = $api->getPagesForPageViewPermissions();
	$pages = $apiResult['pages'];
	$totalcount = $apiResult['totalcount'];

	if ($perpage)
	{
		$pages = array_slice($pages, ($page - 1) * $perpage, $perpage, true);
	}

	$pageids = array_column($pages, 'pageid');
	$qry = $assertor->select('pageviewpermission', ['pageid' => $pageids]);
	$pageviewpermissions = [];
	foreach($qry AS $__row)
	{
		$pageviewpermissions[$__row['pageid']][$__row['usergroupid']] = $__row['viewpermission'];
	}

	$paginationParams = [
		'page' => $page,
		'perpage' => $perpage,
	];
	print_pagination_form($thispagelink, $thispagedo, $paginationParams, $totalcount);

	$id = uniqid();
	$firstRow = [
		"
		<label for=\"$id\">
			{$vbphrase['all']}
		</label>
		<label class=\"h-cursor\"><span class=\"h-checkbox-label h-align-middle \">
			<input id=\"$id\" class=\"h-cursor js-checkbox-master\" data-child=\"js-checkbox-child-top\" type=\"checkbox\" name=\"\" value=\"1\">
		</span></label>
		",
	];
	$headers = [
		"<span class=\"h-center-content\">" . $vbphrase['page'] . "</span>",
	];
	/** @var vB_Api_UserGroup */
	$usergroupApi = vB_Api::instanceInternal('usergroup');
	$groupcache = $usergroupApi->fetchUsergroupList();
	$skipSystemGroupIds = [
		vB_Api_UserGroup::ADMINISTRATOR => 1,
		vB_Api_UserGroup::BANNED => 1,

		vB_Api_UserGroup::CHANNEL_OWNER_SYSGROUPID => 1,
		vB_Api_UserGroup::CHANNEL_MODERATOR_SYSGROUPID => 1,
		vB_Api_UserGroup::CHANNEL_MEMBER_SYSGROUPID => 1,

		vB_Api_UserGroup::CMS_AUTHOR_SYSGROUPID => 1,
		vB_Api_UserGroup::CMS_EDITOR_SYSGROUPID => 1,
	];
	foreach ($groupcache AS $__usergroup)
	{
		$__systemgroupid = $__usergroup['systemgroupid'];
		$__usergroupid = $__usergroup['usergroupid'];
		if (isset($skipSystemGroupIds[$__systemgroupid]))
		{
			continue;
		}
		$firstRow[] = "<label class=\"h-cursor\"><span class=\"h-checkbox-label h-align-middle \">
			<input class=\"h-cursor js-checkbox-master js-checkbox-child-top\" data-child=\"js-checkbox-enabled-child-{$__usergroupid}\" type=\"checkbox\" name=\"\" value=\"1\">
		</span></label>";
		$headers[] = "<div title=\"usergroupid: $__usergroupid\">{$__usergroup['title']}</div>";
	}
	$colSpan = count($headers);

	// We want the left column left-aligned, everything else center aligned. We may want to push this to the print_cells_row2 function if this config
	// turns out to be common.
	$aligns = array_fill(0, $colSpan, 'vbcenter');
	$aligns[0] = 'vbleft';

	print_form_header2($thispagelink, 'save_page_permissions');
	print_table_start();
	print_table_header($vbphrase['page_permissions_gcphome'], $colSpan);
	$bgClasses = 'thead';
	print_cells_row2($headers, $bgClasses, $aligns);

	construct_hidden_code('page', $page);
	construct_hidden_code('perpage', $perpage);

	print_cells_row2($firstRow, [], $aligns);

	foreach ($pages AS $__page)
	{
		$__pageid = $__page['pageid'];
		$__url = htmlentities($__page['url']);
		$rowCells = [
			"<a href=\"{$__url}\" title=\"pageid: $__pageid\">{$__page['html_label']}</a>",
		];
		foreach ($groupcache AS $__usergroup)
		{
			$__systemgroupid = $__usergroup['systemgroupid'];
			$__usergroupid = $__usergroup['usergroupid'];
			if (isset($skipSystemGroupIds[$__systemgroupid]))
			{
				// We have to make sure that the skipped ones have "view" perms set, otherwise
				// the unset ones will be "always blocked".
				// The reason why we don't assume permissive for missing groups is to default
				// usergroups added *after* page view permission change to default block.
				// Note that if the page has NO records against any usergroups, then it defaults
				// permissive.
				// We might want to make this a bit more nuanced, e.g. block banned users.
				construct_hidden_code("canview[{$__pageid}][{$__usergroupid}]", 1);
			}
			else
			{
				$__checked = isset($pageviewpermissions[$__pageid]) ? ($pageviewpermissions[$__pageid][$__usergroupid] ?? false): true;
				$__checked = $__checked ? 'checked' : '';
				$rowCells[] = "<label class=\"h-cursor\"><span class=\"h-checkbox-label h-align-middle \">
					<input type=\"hidden\" name=\"canview[{$__pageid}][{$__usergroupid}]\" value=\"0\">
					<input class=\"h-cursor js-checkbox-enabled-child-{$__usergroupid}\" type=\"checkbox\" name=\"canview[{$__pageid}][{$__usergroupid}]\" value=\"1\" {$__checked}>
				</span></label>";
			}
		}

		print_cells_row2($rowCells, [], $aligns);
	}

	$buttons = [
		['submit', $vbphrase['save']],
	];
	print_table_button_footer($buttons, $colSpan);

	print_pagination_form($thispagelink, $thispagedo, $paginationParams, $totalcount);
}


// ###################### Start Save #######################
if ($_REQUEST['do'] == 'save_page_permissions')
{
	$vbulletin->input->clean_array_gpc('r', [
		'canview'       => vB_Cleaner::TYPE_ARRAY,
		'page'	  => vB_Cleaner::TYPE_INT,
		'perpage' => vB_Cleaner::TYPE_INT,
	]);
	['p' => $page, 'pp' => $perpage] = getPageAndPerPage($vbulletin->GPC);
	$canview = $vbulletin->GPC['canview'];

	$assertor = vB::getDbAssertor();
	foreach ($canview AS $__pageid => $__row)
	{
		foreach ($__row AS $__usergroupid => $__canview)
		{
			$assertor->replace('pageviewpermission', ['pageid' => $__pageid, 'usergroupid' => $__usergroupid, 'viewpermission' => $__canview]);
		}
	}

	$args = [
		'do' => 'list',
		'page' => $page,
		'perpage' => $perpage,
	];
	$url = get_admincp_url('pagepermission', $args);
	print_cp_redirect($url, 2);
}





print_cp_footer();


/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 111008 $
|| #######################################################################
\*=========================================================================*/
