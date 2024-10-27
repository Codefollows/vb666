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
$phrasegroups = ['cpglobal', 'cphome',];
$specialtemplates = [];

// ########################## REQUIRE BACK-END ############################
require_once(dirname(__FILE__) . '/global.php');

$userContext = vB::getUserContext();
// ######################## CHECK ADMIN PERMISSIONS #######################
if (!$userContext->hasAdminPermission('canadminsettingsall') AND !$userContext->hasAdminPermission('canadminsettings'))
{
	print_cp_no_permission();
}

// ############################# LOG ACTION ###############################
log_admin_action();


// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

$vb5_config =& vB::getConfig();
$vboptions = vB::getDatastore()->getValue('options');
// For JS, css etc
$extraheader = [];
$extraheader[] = '<script type="text/javascript" src="core/clientscript/vbulletin_paginate.js?v=' . $vboptions['simpleversion'] . '"></script>';

// print header
print_cp_header($vbphrase['reactions_manager'], '', $extraheader);


if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'list';
}

/** @var vB_Api_Reactions */
$api = vB_Api::instanceInternal('reactions');
/** @var vB_Library_Reactions */
$lib = vB_Library::instance('reactions');

// print_form_header2() always appends '.php?do'...
$thispagelink = 'admincp/managereactions';
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

	$allreactions = $lib->getReactionsEmojisData();
	$totalcount = count($allreactions);

	if ($perpage)
	{
		$slice = array_slice($allreactions, ($page - 1) * $perpage, $perpage, true);
	}
	else
	{
		$slice = $allreactions;
	}

	$perRow = 10;
	// Select/unselect all checkboxes for each column
	$enabled = "<label class=\"h-cursor\">
		{$vbphrase['enabled']}
		<span class=\"h-checkbox-label h-align-middle \">
			<input class=\"h-cursor js-checkbox-master\" data-child=\"js-checkbox-enabled-child\" type=\"checkbox\" name=\"helper\" value=\"0\">
		</span>
	</label>";
	$userrep = "<label class=\"h-cursor\">
		{$vbphrase['affects_userreputation']}
		<span class=\"h-checkbox-label h-align-middle \">
			<input class=\"h-cursor js-checkbox-master\" data-child=\"js-checkbox-userrep-child\" type=\"checkbox\" name=\"helper\" value=\"0\">
		</span>
	</label>";
	$usercount = "<label class=\"h-cursor\">
		{$vbphrase['affects_userreactioncount']}
		<span class=\"h-checkbox-label h-align-middle \">
			<input class=\"h-cursor js-checkbox-master\" data-child=\"js-checkbox-usercount-child\" type=\"checkbox\" name=\"helper\" value=\"0\">
		</span>
	</label>";

	$headers = [
		"<span class=\"h-center-content\">" . $vbphrase['reaction'] . "</span>",
		$enabled,
		$userrep,
		$usercount,
		"<span class=\"h-center-content\">" . $vbphrase['display_order'] . "</span>",
		"<span class=\"h-center-content\">" . $vbphrase['controls'] . "</span>",
	];
	$colSpan = count($headers);

	$paginationParams = [
		'page' => $page,
		'perpage' => $perpage,
	];
	print_pagination_form($thispagelink, $thispagedo, $paginationParams, $totalcount);

	// These are <int reactionid> => <float reputation / like factor (1.0f atm)>
	$countables = $lib->getUserRepCountableTypes();
	$reputables = $lib->getReputableTypesAndFactors();
	$enabled = $lib->getEmojisEnabledStatus();

	// START FORM & TABLE PRINTING
	print_form_header2($thispagelink, 'save_reaction_options');
	print_table_start();
	print_table_header($vbphrase['manage_reactions_gpchome'], $colSpan);
	print_cells_row2($headers, 'thead h-align-middle--only');

	construct_hidden_code('page', $page);
	construct_hidden_code('perpage', $perpage);

	$i = 0;
	$cells = [];
	foreach ($slice AS $__rxn)
	{
		$rowCells = get_reaction_options_row($__rxn, $enabled, $reputables, $countables, $vbphrase);

		// controls
		$args = [
			'do' => 'edit',
			'page' => $page,
			'perpage' => $perpage,
			'votetypeid' => $__rxn['votetypeid'],
		];
		$url = get_admincp_url('managereactions', $args);
		$rowCells[] = construct_link_code2($vbphrase['edit'], $url, false, '', 'h-center-content');

		print_cells_row2($rowCells);
	}

	$args = [
		'do' => 'add',
		'page' => $page,
		'perpage' => $perpage,
	];
	$url = get_admincp_url('managereactions', $args);
	$buttons = [
		construct_link_button($vbphrase['add_new_reaction_gpchome'] , $url),
		['submit', $vbphrase['reactions_save_options']],
	];
	print_table_button_footer($buttons, $perRow);

	print_pagination_form($thispagelink, $thispagedo, $paginationParams, $totalcount);
}


// ###################### Start Save #######################
if ($_REQUEST['do'] == 'save_reaction_options')
{
	$vbulletin->input->clean_array_gpc('r', [
		'enabled'       => vB_Cleaner::TYPE_ARRAY_INT,
		'userreputable' => vB_Cleaner::TYPE_ARRAY_INT,
		'usercountable' => vB_Cleaner::TYPE_ARRAY_INT,
		'order'         => vB_Cleaner::TYPE_ARRAY_INT,
		'page'          => vB_Cleaner::TYPE_INT,
		'perpage'       => vB_Cleaner::TYPE_INT,
	]);
	['p' => $page, 'pp' => $perpage] = getPageAndPerPage($vbulletin->GPC);

	$delta = [];
	foreach ($vbulletin->GPC['enabled'] AS $__votetypeid => $__enabled)
	{
		// Here we're relying on the fact that currently, everything is keyed by votetypeid and
		// every option is present even if checkbox is toggled off.
		$delta[$__votetypeid] = [
			'votetypeid'          => $__votetypeid,
			'enabled'             => $__enabled,
			'user_rep_factor'     => $vbulletin->GPC['userreputable'][$__votetypeid],
			'user_like_countable' => $vbulletin->GPC['usercountable'][$__votetypeid],
			'order'               => $vbulletin->GPC['order'][$__votetypeid],
		];
	}

	$lib->saveReactionOptions($delta);

	// I don't think get_admincp_url works with customs...
	$args = [
		'do' => 'list',
		'page' => $page,
		'perpage' => $perpage,
	];
	$url = get_admincp_url('managereactions', $args);
	print_cp_redirect($url, 2);
}


// ###################### Start Customize #######################
if ($_REQUEST['do'] == 'add' OR $_REQUEST['do'] == 'edit')
{
	$vbulletin->input->clean_array_gpc('r', [
		'votetypeid'          => vB_Cleaner::TYPE_INT,
		'page'          => vB_Cleaner::TYPE_INT,
		'perpage'       => vB_Cleaner::TYPE_INT,
	]);
	['p' => $page, 'pp' => $perpage] = getPageAndPerPage($vbulletin->GPC);

	$votetypeid = $vbulletin->GPC['votetypeid'];

	$reaction = [
		'enabled' => 1,
		'user_rep_factor' => 1,
		'user_like_countable' => 1,
		'emojihtml' => '<span></span>',
		'order' => 0,
		'label' => '',
		'filedataid' => 0,
	];
	if (!empty($votetypeid))
	{
		$allreactions = $lib->getReactionsEmojisData();
		$check = array_column($allreactions, null, 'votetypeid');
		$reaction = $check[$votetypeid] ?? $reaction;
	}

	// START FORM & TABLE PRINTING
	print_form_header2($thispagelink, 'do_save', [], ['enctype' => 'multipart/form-data']);
	print_table_start();

	construct_hidden_code('page', $page);
	construct_hidden_code('perpage', $perpage);

	construct_hidden_code('votetypeid', $votetypeid);
	construct_hidden_code('oldfiledataid', $reaction['filedataid']);

	print_table_header(construct_phrase($vbphrase['x_y_id_z'], $vbphrase['reaction'], '', $votetypeid));
	print_input_row($vbphrase['reaction_label_desc'], 'label', $reaction['label']);

	if (!empty($reaction['emojihtml']) OR $reaction['filedataid'] > 0)
	{
		print_label_row($vbphrase['reaction_emojihtml_rendered'], getReactionHtml($reaction));
	}

	// Use the custom image upload logic that allows for preview, remove & revert logic.
	print_image_upload_row($vbphrase['reaction_custom_image'], 'upload_img', null, $reaction['filedataid'], 'filedataid');


	print_textarea_row2($vbphrase['reaction_emojihtml_label_desc'], 'emojihtml', htmlentities($reaction['emojihtml']), [], [], false);

	print_checkbox_row($vbphrase['enabled'], 'enabled', $reaction['enabled']);
	print_checkbox_row($vbphrase['affects_userreputation'], 'user_rep_factor', $reaction['user_rep_factor']);
	print_checkbox_row($vbphrase['affects_userreactioncount'], 'user_like_countable', $reaction['user_like_countable']);
	print_input_row($vbphrase['display_order'], 'order', $reaction['order']);

	print_table_default_footer($vbphrase['save_reaction']);
}

if ($_REQUEST['do'] == 'do_save')
{
	$vbulletin->input->clean_array_gpc('r', [
		'votetypeid'          => vB_Cleaner::TYPE_INT,
		'label'               => vB_Cleaner::TYPE_STR,
		'emojihtml'           => vB_Cleaner::TYPE_STR,
		'enabled'             => vB_Cleaner::TYPE_UINT,
		'user_rep_factor'     => vB_Cleaner::TYPE_INT,
		'user_like_countable' => vB_Cleaner::TYPE_UINT,
		'order'               => vB_Cleaner::TYPE_INT,
		// This is handled specially via handleUploadImage()
		//'upload_img'          => vB_Cleaner::TYPE_FILE,
		'oldfiledataid'       => vB_Cleaner::TYPE_UINT,
		'filedataid'          => vB_Cleaner::TYPE_UINT,
		// passthru to next page
		'page'          => vB_Cleaner::TYPE_INT,
		'perpage'       => vB_Cleaner::TYPE_INT,
	]);
	['p' => $page, 'pp' => $perpage] = getPageAndPerPage($vbulletin->GPC);

	$votetypeid = $vbulletin->GPC['votetypeid'];
	$reactiondata = [
		'votetypeid',
		'label',
		'emojihtml',
		'enabled',
		'user_rep_factor',
		'user_like_countable',
		'order',
		'filedataid',
	];
	$reaction = [];
	foreach ($reactiondata AS $k)
	{
		$reaction[$k] = $vbulletin->GPC[$k];
	}

	// it *might* be nice to check for validity of some fields *before* we upload the image...
	// but don't want to duplicate code atm...


	$oldfiledataid = $vbulletin->GPC['oldfiledataid'];
	// Let's try to upload the image first (if we have one)...
	$filedataid = handleUploadImage('upload_img');
	if ($filedataid > 0)
	{
		$reaction['filedataid'] = $filedataid;
		// note, filedata publicview & increment/decrement logic comes after
		// reaction save, so that we don't cause orphaned filedata if the
		// reaction save fails for any reason.
	}
	else
	{
		// else keep the old one (which might be 0 if this reaction isn't using images)
		$filedataid = $reaction['filedataid'];
	}


	// Check existing reaction.
	$allreactions = $lib->getReactionsEmojisData();
	$check = array_column($allreactions, null, 'votetypeid');
	$oldreaction = $check[$votetypeid] ?? [];

	/** @var vB_Library_Nodevote */
	$nodevoteLib = vB_Library::instance('nodevote');
	$assertor = vB::getDbAssertor();
	if (empty($votetypeid))
	{
		// todo... this isn't a wrapped API call and will throw raw exceptions
		// save new reaction.
		$votetypeid = $lib->addReaction($reaction['label'], $reaction);
	}
	else
	{
		// check for some data that goes into nodevote table.
		if ($oldreaction['label'] != $reaction['label'])
		{
			$check = $nodevoteLib->updateVotetypeLabel($votetypeid, $reaction['label']);
		}

		$lib->saveReactionOptions($reaction);
	}

	// filedata.publicview & increment/decrement refcount.
	// Perhaps we could also regex the emojihtml (& add and check old_emojihtml) and inc/dec-rement any explicitly
	// referenced filedataid's, so that admins can manually refer to other existing filedataid(s) without going through
	// the upload, or even show multiple filedataids for a single reaction, but that seems too complicated & frail for
	// an unasked-for feature for now.
	$assertor = vB::getDbAssertor();
	if ($filedataid > 0 AND $oldfiledataid != $filedataid)
	{
		$assertor->assertQuery('incrementFiledataRefcountAndMakePublic', ['filedataid' => $filedataid]);
	}
	if ($oldfiledataid > 0 AND $oldfiledataid != $filedataid)
	{
		$assertor->assertQuery('decrementFiledataRefcount', ['filedataid' => $oldfiledataid]);
	}


	// todo: would be really nice to be able to find what page this is on and go there...

	$args = [
		'do' => 'list',
		'page' => $page,
		'perpage' => $perpage,
	];
	$url = get_admincp_url('managereactions', $args);
	print_cp_redirect($url, 2);
}





print_cp_footer();


function handleUploadImage($uploadname = 'upload_img') : int
{
	// Based heavily on the forum.php custom channel icon upload handling logic.
	global $vbulletin;

	$vbulletin->input->clean_array_gpc('f', [
		$uploadname => vB_Cleaner::TYPE_FILE
	]);
	scanVbulletinGPCFile($uploadname);


	// Try uploading the channel icon first. If there was an error with the upload
	// (which may cause tmp_name to be empty), pass that along and let the API
	// functions map the error code to an error phrase. However ignore the error
	// if it's a NO_FILE error (that just means no icon was uploaded)
	$hasUpload = (
		!empty($vbulletin->GPC[$uploadname]['tmp_name'])
		OR
		!empty($vbulletin->GPC[$uploadname]['error']) AND
		$vbulletin->GPC[$uploadname]['error'] !== UPLOAD_ERR_NO_FILE
	);
	if ($hasUpload)
	{
		//$vbulletin->GPC[$uploadname]['parentid'] = $iconChannelId;
		// TODO: add an uploadfrom? Needs matching handling code in vB_Library_Content_Attach::uploadAttachment()
		// For now, set nothing, and it'll be treated as an attachment as far as upload perm checks go.
		//$vbulletin->GPC[$uploadname]['uploadfrom'] = 'channelicon';

		/** @var vB_Api_Content_Attach */
		$attachAPI = vB_Api::instance('content_attach');
		$result = $attachAPI->upload($vbulletin->GPC[$uploadname]);
		print_stop_message_on_api_error($result);

		return $result['filedataid'] ?? -1;
	}

	return -1;
}


// These functions should be considered "private" to this script.
function get_reaction_options_row($reaction, array $enabled, array $reputables, array $countables, $vbphrase)
{
	/*
reaction: Array
(
    [order] => 100
    [label] => enraged face
    [emojihtml] => &#X1F621
    [votetypeid] => 11416
    [votegroupid] => 45
)
	*/
	$id = $reaction['votetypeid'];
	$label = $reaction['label'];
	$checkedEnabled = $checkedRep = $checkedCount = '';

	if ($reputables[$id] > 0)
	{
		$checkedRep = ' checked';
	}

	if ($countables[$id])
	{
		$checkedCount = ' checked';
	}

	if ($enabled[$id])
	{
		$checkedEnabled = ' checked';
	}

	$defaulthiddenvalue = 0;
	// thumbs up is, for now, privileged and cannot be disabled.
	if ($label == vB_Library_Reactions::THUMBS_UP_LABEL)
	{
		$defaulthiddenvalue = 1;
		$checkedEnabled .= ' disabled';
		$checkedRep .= ' disabled';
		$checkedCount .= ' disabled';
	}

	// Note, this doesn't work for this case, it ends up overwriting our actual values.
	// construct_hidden_code("enabled[$id]", $defaulthiddenvalue);
	// construct_hidden_code("userreputable[$id]", $defaulthiddenvalue);
	// construct_hidden_code("usercountable[$id]", $defaulthiddenvalue);
	$emojihtml = getReactionHtml($reaction);
	$cells = [];
	$cells[] =  <<<EOT
<div class="reaction-cell-wrapper">
	<div class="reaction-label">$label</div>
	{$emojihtml}
	<input type="hidden" name="enabled[$id]" value="$defaulthiddenvalue">
	<input type="hidden" name="userreputable[$id]" value="$defaulthiddenvalue">
	<input type="hidden" name="usercountable[$id]" value="$defaulthiddenvalue">
</div>
EOT;
	$cells[] = "<label class=\"h-checkbox-label\"><input class=\"js-checkbox-enabled-child\" type=\"checkbox\" name=\"enabled[$id]\" value=\"1\" $checkedEnabled></label>";
	$cells[] = "<label class=\"h-checkbox-label\"><input class=\"js-checkbox-userrep-child\" type=\"checkbox\" name=\"userreputable[$id]\" value=\"1\" $checkedRep></label>";
	$cells[] = "<label class=\"h-checkbox-label\"><input class=\"js-checkbox-usercount-child\" type=\"checkbox\" name=\"usercountable[$id]\" value=\"1\" $checkedCount></label>";
	//Display Order colunm
	$attributes = [
		'value' => $reaction['order'],
		'class' => 'bginput display-order',
		'title' => $vbphrase['display_order'],
	];
	$cells[] = construct_input('text', 'order[' . $id . ']', $attributes);

	return $cells;
}

function getReactionHtml($reaction) : string
{
	// Keep the parts inside .reaction-item in sync with reactions_list_template & reactions_conversation_footer templates
	if ($reaction['filedataid'] > 0)
	{
		$escaped_label = htmlentities(trim($reaction['label']));
		return <<<EOT
<div class="reaction-item">
	<img src="filedata/fetch?filedataid={$reaction['filedataid']}" alt="$escaped_label">
</div>
EOT;
	}
	else
	{
		return <<<EOT
<div class="reaction-item">
	{$reaction['emojihtml']}
</div>
EOT;
	}
}


/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 111008 $
|| #######################################################################
\*=========================================================================*/
