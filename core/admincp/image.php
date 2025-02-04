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
define('CVS_REVISION', '$RCSfile$ - $Revision: 115264 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
global $phrasegroups, $specialtemplates, $vbphrase, $colspan, $vbulletin;
$phrasegroups = ['cphome', 'attachment_image', 'cppermission'];
$specialtemplates = [];

// ########################## REQUIRE BACK-END ############################
require_once(dirname(__FILE__) . '/global.php');
$assertor = vB::getDbAssertor();

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!can_administer('canadminimages'))
{
	print_cp_no_permission();
}

// make sure we are dealing with avatars,smilies or icons
$vbulletin->input->clean_array_gpc('r', array(
	'table' => vB_Cleaner::TYPE_STR,
	'id'    => vB_Cleaner::TYPE_UINT,
));

/*
NOTE:
	for use in imagecategory table:
	imagetype = 1 => avatar
	imagetype = 2 => icon
	imagetype = 3 => smilie
*/

$imageTypeInfo = vB_Library::instance('icon')->getIconTypeInfo($vbulletin->GPC['table']);
list('type' => $itemtype, 'typeplural' => $itemtypeplural, 'catid' => $catid) = $imageTypeInfo;

if ((($_REQUEST['do'] == 'insert') OR ($_REQUEST['do'] == 'add')) AND (($itemtype == 'smilie') OR ($itemtype == 'post_icon')) AND !vB::getUserContext()->hasAdminPermission('cansetserverconfig'))
{
	print_cp_no_permission();
}

// ############################# LOG ACTION ###############################
log_admin_action($vbphrase["$itemtypeplural"] . iif($vbulletin->GPC['id'] != 0, " id = " . $vbulletin->GPC['id']));

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

print_cp_header($vbphrase["$itemtypeplural"]);

$tables = array(
	'avatar' => $vbphrase['avatar_gattachment_image'],
	'icon' => $vbphrase['post_icon_gattachment_image'],
	'smilie' => $vbphrase['smilie']
);

$table = $vbulletin->GPC['table'];
$itemid = $table . 'id';
$itempath = $table . 'path';

// ************************************************************
// start functions

$img_per_row = 5;

// ###################### Start showimage #######################
function construct_img_html($imagepath)
{
	global $vbulletin;
	static $smiliePath = false;

	//The handling of images is a bit convoluted, but for smiles we need to respect the datastore smiliepath.  See VBV-12255
	if ($smiliePath === false)
	{
		if (!empty($vbulletin->GPC['table']) AND ($vbulletin->GPC['table'] == 'smilie'))
		{
			$smiliePath = vB::getDatastore()->getOption('smiliepath');
		}
		else
		{
			$smiliePath = '';
		}
	}

	if (substr($imagepath, 0, strlen($smiliePath)) != $smiliePath)
	{
		$imagepath = $smiliePath . $imagepath;
	}

	// returns an image based on imagepath
	return '<img src="' . resolve_cp_image_url($imagepath, true) . "\" alt=\"$imagepath\" align=\"middle\" />";
}

// ###################### Start makeitemrow #######################
function print_image_item_row(&$cell)
{
// returns a row of five cells for use in $do==viewimages
	global $img_per_row;
	$cells = $img_per_row - sizeof($cell);
	for ($i=0; $i < $cells; $i++)
	{
		$cell[] = '';
	}
	print_cells_row($cell, 0, 0, 1, 'bottom');
	$cell = array();
}

// ###################### Start displayitem #######################
function print_image_item($item, $itemid, $itempath, $page, $perpage, $catid, $massmove = false, $imagecategoryid = 0)
{
	// displays an item together with links to edit/remove
	global $vbulletin, $vbphrase;
	static $categories = false;

	if (!$massmove)
	{
		$out = "<b>$item[title]</b><br /><br />"
			. construct_img_html($item["$itempath"])
			. '<br />'
			. ($vbulletin->GPC['table'] == 'smilie' ? " <span class=\"smallfont\">$item[smilietext]</span>" : '')
			. '<br />'
		;

		$out .= construct_link_code(
			$vbphrase['edit'], "image.php?"
			. vB::getCurrentSession()->get('sessionurl')
			. "do=edit"
			. "&table=" . $vbulletin->GPC['table']
			. "&id="    . $item[$itemid]
			. "&pp="    . $perpage
			. "&page="  . $page
			. ($imagecategoryid ? "&imagecategoryid=" . $imagecategoryid : '')
		);

		$out .= construct_link_code(
			$vbphrase['delete'], "image.php?" .
			vB::getCurrentSession()->get('sessionurl')
			. "do=remove"
			. "&table="           . $vbulletin->GPC['table']
			. "&id="              . $item[$itemid]
			. "&pp="              . $perpage
			. "&page="            . $page
			. ($imagecategoryid ? "&imagecategoryid=" . $imagecategoryid : '')
		);

		$out .= " <input type=\"text\" class=\"bginput\" name=\"order[" . $item["$itemid"]
				. "]\" tabindex=\"1\" value=\"$item[displayorder]\" size=\"2\" title=\""
				. $vbphrase['display_order'] . "\" class=\"smallfont\" /> ";
	}
	else
	{

		if (!$categories)
		{
			$categories = '<option value="0"></option>';
			$categories .= construct_select_options(fetch_image_categories_array($catid));
		}
		$title = iif($item['title'], "<a href=\"admincp/image.php?" .
			vB::getCurrentSession()->get('sessionurl')
			. "do=edit"
			. "&amp;table=" 	. $vbulletin->GPC['table']
			. "&amp;id="		. $item[$itemid]
			. "&amp;pp=" 	. $perpage
			. "&amp;page="		. $page
			. "&amp;massmove="	. $massmove
			. "\">$item[title]</a>",
			construct_link_code($vbphrase['edit'],
				"image.php?"
				. vB::getCurrentSession()->get('sessionurl')
				. "do=edit"
				. "&amp;table="		. $vbulletin->GPC['table']
				. "&amp;id="		. $item[$itemid]
				. "&amp;pp="	. $perpage
				. "&amp;page="		. $page
				. "&amp;massmove="	. $massmove
			)
		);

		$out = "<b>"
			. $title
			. "</b><br /><br />"
			. construct_img_html($item["$itempath"])
			. '<br />'
			. iif($vbulletin->GPC['table'] == 'smilie', " <span class=\"smallfont\">$item[smilietext]</span>")
			. '<br />';

		$out .= '<select name="category[' . $item["$itemid"] . ']" class="bginput">' . $categories . '</select>';
	}

	return $out;
}

// ###################### Start getimagecategories #######################
// returns an array of imagecategoryid => title for use in <select> lists
function fetch_image_categories_array($catid)
{
	global $vbulletin, $cats;
	if (!is_array($cats))
	{
		$categories = vB::getDbAssertor()->getRows('vBForum:imagecategory',
			array('imagetype' => $catid),
			array('field' => 'displayorder', 'direction' => vB_dB_Query::SORT_ASC)
		);

		$cats = array();
		foreach ($categories AS $category)
		{
			foreach ($categories AS $category)
			{
				$cats[$category['imagecategoryid']] = $category['title'];
			}
		}
	}

	return $cats;
}

// end functions
// ************************************************************

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'modify';
}

// ###################### Start Update Permissions #######################
if ($_POST['do'] == 'updatepermissions')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'iperm'           => vB_Cleaner::TYPE_ARRAY,
		'imagecategoryid' => vB_Cleaner::TYPE_INT
	));

	$result = vB_Api::instance('icon')->setCategoryPermissions($vbulletin->GPC['imagecategoryid'], $vbulletin->GPC['iperm']);
	print_stop_message_on_api_error($result);

	print_stop_message2('saved_permissions_successfully', 'image', [
		'do' => 'modify',
		'table' => $vbulletin->GPC['table'],
	]);
}

// ###################### Start Edit Permissions #######################
if ($_REQUEST['do'] == 'editpermissions')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'imagecategoryid'	=> vB_Cleaner::TYPE_INT
	));

	$categoryinfo = $assertor->getRow('vBForum:imagecategory', array(
		'imagecategoryid' => $vbulletin->GPC['imagecategoryid'],
		vB_dB_Query::COLUMNS_KEY => array('imagetype', 'title'),
	));

	if ($categoryinfo['imagetype'] == 3)
	{
		print_stop_message2('smilie_categories_dont_support_permissions');
	}

	$usergroups = $assertor->assertQuery('vBForum:fetchUsergroupImageCategories',
		array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_STORED,
			'imagecategoryid' => $vbulletin->GPC['imagecategoryid']
		)
	);

	print_form_header('admincp/image', 'updatepermissions');
	construct_hidden_code('table', $vbulletin->GPC['table']);
	construct_hidden_code('imagecategoryid', $vbulletin->GPC['imagecategoryid']);
	print_table_header(construct_phrase($vbphrase["permissions_for_{$itemtype}_category_x"], $categoryinfo['title']));
	print_label_row('<span class="smallfont"><b>' . $vbphrase['usergroup'] . '</b></span>', '<span class="smallfont"><b>' .
		$vbphrase["can_use_this_{$itemtype}_category"] . '</b></span>');
	if ($usergroups AND $usergroups->valid())
	{
		foreach ($usergroups AS $usergroup)
		{
			$usergroupid = $usergroup['usergroupid'];
			$canuse = iif($usergroup['nopermission'], 0, 1);
			print_yes_no_row($usergroup['title'], "iperm[$usergroupid]", $canuse);
		}
	}
	print_submit_row($vbphrase['save']);

}

// ###################### Start Kill Category #######################
if ($_POST['do'] == 'killcategory')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'imagecategoryid' => vB_Cleaner::TYPE_INT,
		'destinationid' 	=> vB_Cleaner::TYPE_INT,
		'deleteitems' 		=> vB_Cleaner::TYPE_NOCLEAN
	));

	if ($vbulletin->GPC['deleteitems'] == 1)
	{
		$assertor->assertQuery("vBForum:". $vbulletin->GPC['table'],
			array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_DELETE, 'imagecategoryid' => $vbulletin->GPC['imagecategoryid'])
		);
		$extra = $vbphrase["{$itemtypeplural}_deleted"];
	}
	else
	{
		$assertor->assertQuery("vBForum:". $vbulletin->GPC['table'],
			array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
				'imagecategoryid' => $vbulletin->GPC['destinationid'],
				vB_dB_Query::CONDITIONS_KEY => array(
					array('field' => 'imagecategoryid', 'value' => $vbulletin->GPC['imagecategoryid'], 'operator' => vB_dB_Query::OPERATOR_EQ)
				)
			)
		);
		$extra = $vbphrase["{$itemtypeplural}_deleted"];
	}

	$assertor->assertQuery('vBForum:imagecategory',
		array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_DELETE, 'imagecategoryid' => $vbulletin->GPC['imagecategoryid'])
	);
	$assertor->assertQuery('vBForum:imagecategorypermission',
		array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_DELETE, 'imagecategoryid' => $vbulletin->GPC['imagecategoryid'])
	);


	build_image_cache($vbulletin->GPC['table']);

	### KIER LOOK HERE ###
	print_stop_message2('deleted_category_successfully_gerror','image', array(
		'do' => 'modify',
		'table' => $vbulletin->GPC['table']
	));
	### END LOOK HERE ###
}

// ###################### Start Remove Category #######################
if ($_REQUEST['do'] == 'removecategory')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'imagecategoryid' => vB_Cleaner::TYPE_INT
	));

	$categories = $assertor->getRows('vBForum:imagecategory',
		array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT, 'imagetype' => $catid),
		array('field' => 'displayorder', 'direction' => vB_dB_Query::SORT_ASC)
	);

	if (count($categories) < 2)
	{
		print_stop_message2("cant_remove_last_{$itemtype}_category");
	}
	else
	{
		$category = array();
		$destcats = array();
		foreach ($categories AS $tmp)
		{
			if ($tmp['imagecategoryid'] == $vbulletin->GPC['imagecategoryid'])
			{
				$category = $tmp;
			}
			else
			{
				$destcats[$tmp['imagecategoryid']] = $tmp['title'];
			}
		}
		unset($tmp);

		echo "<p>&nbsp;</p><p>&nbsp;</p>\n";

		print_form_header('admincp/image', 'killcategory');
		construct_hidden_code('imagecategoryid', $category['imagecategoryid']);
		construct_hidden_code('table', $vbulletin->GPC['table']);
		print_table_header(construct_phrase($vbphrase["confirm_deletion_of_{$itemtype}_category_x"], $category['title']));
		print_description_row('<blockquote>' . construct_phrase($vbphrase["are_you_sure_you_want_to_delete_the_{$itemtype}_category_called_x"], $category['title'], construct_select_options($destcats)) . '</blockquote>');
		print_submit_row($vbphrase['delete'], '', 2, $vbphrase['go_back']);
	}

}

// ###################### Start Update Category #######################
if ($_POST['do'] == 'insertcategory')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'title'        => vB_Cleaner::TYPE_NOHTML,
		'displayorder' => vB_Cleaner::TYPE_INT
	));

	if (empty($vbulletin->GPC['title']))
	{
		print_stop_message2('please_complete_required_fields');
	}

	/*insert query*/
	$assertor->assertQuery('vBForum:imagecategory',
		array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_INSERT,
			'title' => $vbulletin->GPC['title'],
			'imagetype' => $catid,
			'displayorder' => $vbulletin->GPC['displayorder']
		)
	);

	build_image_cache($vbulletin->GPC['table']);

	print_stop_message2(array('saved_category_x_successfully',  $vbulletin->GPC['title']),'image', array(
		'do' => 'modify',
		'table' => $vbulletin->GPC['table']
	));
}

// ###################### Start Add Category #######################
if ($_REQUEST['do'] == 'addcategory')
{
	print_form_header('admincp/image', 'insertcategory');
	construct_hidden_code('table', $vbulletin->GPC['table']);
	print_table_header($vbphrase["add_new_{$itemtype}_category"]);
	print_input_row($vbphrase['title'], 'title');
	print_input_row($vbphrase['display_order'], 'displayorder', 1);
	print_submit_row($vbphrase['save']);
}

// ###################### Start Update Category #######################
if ($_POST['do'] == 'updatecategory')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'imagecategoryid'	=> vB_Cleaner::TYPE_INT,
		'title'				=> vB_Cleaner::TYPE_NOHTML,
		'displayorder'		=> vB_Cleaner::TYPE_INT
	));

	if (empty($vbulletin->GPC['title']))
	{
		print_stop_message2('please_complete_required_fields');
	}

	$assertor->assertQuery('vBForum:imagecategory',
		array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
			'title' => $vbulletin->GPC['title'],
			'displayorder' => $vbulletin->GPC['displayorder'],
			vB_dB_Query::CONDITIONS_KEY => array(
				array('field' => 'imagecategoryid', 'value' => $vbulletin->GPC['imagecategoryid'], 'operator' => vB_dB_Query::OPERATOR_EQ)
			)
		)
	);

	build_image_cache($vbulletin->GPC['table']);

	print_stop_message2(array('saved_category_x_successfully',  $vbulletin->GPC['title']),'image', array(
		'do' => 'modify',
		'table' => $vbulletin->GPC['table']
	));
}

// ###################### Start Edit Category #######################
if ($_REQUEST['do'] == 'editcategory')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'imagecategoryid'	=> vB_Cleaner::TYPE_INT
	));

	$category = $assertor->getRow('vBForum:imagecategory',
		array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT, 'imagecategoryid' => $vbulletin->GPC['imagecategoryid'])
	);

	print_form_header('admincp/image', 'updatecategory');
	construct_hidden_code('table', $vbulletin->GPC['table']);
	construct_hidden_code('imagecategoryid', $category['imagecategoryid']);
	print_table_header(construct_phrase($vbphrase['x_y_id_z'], $vbphrase["{$itemtype}_category"], $category['title'], $category['imagecategoryid']));
	print_input_row($vbphrase['title'], 'title', $category['title'], 0);
	print_input_row($vbphrase['display_order'], 'displayorder', $category['displayorder']);
	print_submit_row();

}

// ###################### Start Update Smiley Category Display Order #######################
if ($_REQUEST['do'] == 'docategorydisplayorder')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'order'	=> vB_Cleaner::TYPE_NOCLEAN
	));

	if (is_array($vbulletin->GPC['order']))
	{
		$categories = $assertor->assertQuery('vBForum:imagecategory',
			array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT, 'imagetype' => $catid)
		);
		if ($categories AND $categories->valid())
		{
			foreach ($categories AS $category)
			{
				$displayorder = intval($vbulletin->GPC['order']["$category[imagecategoryid]"]);
				if ($category['displayorder'] != $displayorder)
				{
					$assertor->assertQuery('vBForum:imagecategory',
						array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
							'displayorder' => $displayorder,
							vB_dB_Query::CONDITIONS_KEY => array(
								array('field' => 'imagecategoryid', 'value' => $category['imagecategoryid'], 'operator' => vB_dB_Query::OPERATOR_EQ),
								array('field' => 'imagetype', 'value' => $catid, 'operator' => vB_dB_Query::OPERATOR_EQ)
							)
						)
					);
				}
			}
		}
	}

	print_stop_message2('saved_display_order_successfully','image', array(
		'do' => 'modify',
		'table' => $vbulletin->GPC['table']
	));
}

// ###################### Start Do Upload #######################
if ($_POST['do'] == 'doupload')
{
	$vbulletin->input->clean_array_gpc('f', array(
		'upload'  => vB_Cleaner::TYPE_FILE,
	));
	scanVbulletinGPCFile('upload');

	$vbulletin->input->clean_array_gpc('p', array(
		'imagespath' => vB_Cleaner::TYPE_STR,
		'title'      => vB_Cleaner::TYPE_STR,
		'smilietext' => vB_Cleaner::TYPE_STR
	));

	if (empty($vbulletin->GPC['title']) OR ($vbulletin->GPC['table'] == 'smilie' AND empty($vbulletin->GPC['smilietext'])))
	{
		print_stop_message2('please_complete_required_fields');
	}
	require_once(DIR . '/includes/class_upload.php');

	$upload = new vB_Upload_Image($vbulletin);
	$upload->image = vB_Image::instance();

	// If we are running through the presentation layer we need to adjust the location.
	// This was failing. If I set an absolute path for the upload then it works properly,
	// but we want to put the path relative to the core installation into the database.
	$fixpath = false;

	if ($itemtype == 'post_icon')
	{
		$vbulletin->GPC['imagespath'] = vB::getDatastore()->getOption('posticon_location');

	}
	else if ($itemtype == 'smilie')
	{
		$vbulletin->GPC['imagespath'] = vB::getDatastore()->getOption('smiliepath');
	}

	$upload->path = $vbulletin->GPC['imagespath'];

	if (file_exists( './' . $vbulletin->GPC['imagespath'] . '/' . $vbulletin->GPC['upload']['name']) OR
		file_exists(DIR . '/' . $vbulletin->GPC['imagespath'] . '/' . $vbulletin->GPC['upload']['name']))
	{
		print_stop_message2(array('file_x_already_exists',  htmlspecialchars_uni($vbulletin->GPC['upload']['name'])));
	}

	if (!is_dir( $vbulletin->GPC['imagespath']))
	{
		$location = DIR . '/' . $vbulletin->GPC['imagespath'];

		if (is_dir($location))
		{
			//We'll put the absolute path. Then we need to strip out the core installation later.
			$upload->path = $location;
			$fixpath = true;
		}
	}

	if (!($imagepath = $upload->process_upload($vbulletin->GPC['upload'])))
	{
		print_stop_message2(array('there_were_errors_encountered_with_your_upload_x',  $upload->fetch_error()));
	}

	if ($fixpath AND (substr($imagepath, 0, strlen(DIR)) == DIR))
	{
		//Just put the path relative to the core installation location into the database record.
		$imagepath = substr($imagepath,  strlen(DIR) + 1);
	}

	define('IMAGE_UPLOADED', true);
	$_POST['do'] = 'insert';
}

// ###################### Start Upload #######################
if ($_REQUEST['do'] == 'upload')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'imagecategoryid'	=> vB_Cleaner::TYPE_INT
	));

	print_form_header('admincp/image', 'doupload', 1);
	construct_hidden_code('table', $vbulletin->GPC['table']);
	print_table_header($vbphrase['upload_' . $itemtype . '_gcphome']);
	print_upload_row($vbphrase['filename_gcpglobal'], 'upload');
	print_input_row($vbphrase['title'], 'title');
	switch($vbulletin->GPC['table'])
	{
		case 'avatar':
			print_input_row($vbphrase['minimum_posts'], 'minimumposts', 0);
			break;
		case 'smilie':
			print_input_row($vbphrase['text_to_replace'], 'smilietext', '', true, 35, 100);
			break;
	}

	if (($itemtype  != 'smilie') AND ($itemtype != 'post_icon'))
	{
		print_input_row($vbphrase["{$itemtype}_file_path_dfn"], 'imagespath', 'images/' . $vbulletin->GPC['table'] . 's');
	}
	print_label_row($vbphrase["{$itemtype}_category"], "<select name=\"imagecategoryid\" tabindex=\"1\" class=\"bginput\">" . construct_select_options(fetch_image_categories_array($catid), $vbulletin->GPC['imagecategoryid']) . '</select>', '', 'top', 'imagecategoryid');
	print_input_row($vbphrase['display_order'], 'displayorder', 1);
	print_submit_row($vbphrase['upload']);
}

// ###################### Start Kill #######################
if ($_POST['do'] == 'kill')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'avatarid'        => vB_Cleaner::TYPE_INT,
		'iconid'          => vB_Cleaner::TYPE_INT,
		'smilieid'        => vB_Cleaner::TYPE_INT,
		'page'            => vB_Cleaner::TYPE_INT,
		'perpage'         => vB_Cleaner::TYPE_INT,
		'imagecategoryid' => vB_Cleaner::TYPE_UINT
	));

	if ($vbulletin->GPC['avatarid'])
	{
		$id = $vbulletin->GPC['avatarid'];
	}
	else if ($vbulletin->GPC['iconid'])
	{
		$id = $vbulletin->GPC['iconid'];
	}
	else if ($vbulletin->GPC['smilieid'])
	{
		$id = $vbulletin->GPC['smilieid'];
	}

	$image = $assertor->getRow("vBForum:" . $vbulletin->GPC['table'],
		array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT, $itemid => $id)
	);
	$imagecategoryid = $image['imagecategoryid'];

	$assertor->assertQuery("vBForum:" . $vbulletin->GPC['table'],
		array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_DELETE, $itemid => $id)
	);

	if ($vbulletin->GPC['avatarid'])
	{
		@unlink(DIR . "/images/avatars/thumbs/{$vbulletin->GPC['avatarid']}.gif");
	}

	build_image_cache($vbulletin->GPC['table']);

	$fields = array(
		'do' => 'viewimages',
		'table' => $vbulletin->GPC['table'],
		'page' => $vbulletin->GPC['page'],
		'pp' => $vbulletin->GPC['perpage']
	);
	if (!empty($vbulletin->GPC['imagecategoryid']))
	{
		$fields['imagecategoryid'] = $vbulletin->GPC['imagecategoryid'];
	}
	print_stop_message2("deleted_{$itemtype}_successfully",'image', $fields);
}

// ###################### Start Remove #######################
if ($_REQUEST['do'] == 'remove')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'id'              => vB_Cleaner::TYPE_INT,
		'page'            => vB_Cleaner::TYPE_INT,
		'perpage'         => vB_Cleaner::TYPE_INT,
		'imagecategoryid' => vB_Cleaner::TYPE_UINT
	));

	$hidden = array(
		'table'           => $vbulletin->GPC['table'],
		'page'            => $vbulletin->GPC['page'],
		'perpage'         => $vbulletin->GPC['perpage'],
		'imagecategoryid' => $vbulletin->GPC['imagecategoryid']
	);

	print_delete_confirmation(
		$vbulletin->GPC['table'],
		$vbulletin->GPC['id'],
		'image',
		'kill',
		$itemtype,
		$hidden
	);
}

// ###################### Start Do Insert Multiple #######################
if ($_POST['do'] == 'doinsertmultiple')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'page'             => vB_Cleaner::TYPE_INT,
		'perpage'          => vB_Cleaner::TYPE_INT,
		'imagespath'       => vB_Cleaner::TYPE_STR,
		'doinsertmultiple' => vB_Cleaner::TYPE_STR,
		'ititle'           => vB_Cleaner::TYPE_ARRAY_STR,
		'icat'             => vB_Cleaner::TYPE_ARRAY_STR,
		'ismilietext'      => vB_Cleaner::TYPE_ARRAY_STR,
		'iminimumposts'    => vB_Cleaner::TYPE_ARRAY_STR,
		'doimage'          => vB_Cleaner::TYPE_NOCLEAN
	));

	if (empty($vbulletin->GPC['doinsertmultiple']))
	{
		// just go back to the interface if a page button was pressed, rather than the actual submit button
		$_REQUEST['do'] = 'insertmultiple';
	}
	else if (!is_array($vbulletin->GPC['doimage']))
	{
		// return error if no images checked for insertion
		print_stop_message2("no_{$itemtypeplural}_selected");
	}
	else
	{
		echo "<ul>\n";

		$duplicates = [];
		if ($vbulletin->GPC['table'] == 'smilie')
		{
			// Make sure we don't generate duplicates
			$smiliestext = [];
			foreach($vbulletin->GPC['doimage'] AS $path => $yes)
			{
				$smiliestext[] = $vbulletin->GPC['ismilietext']["$path"];
			}

			$duplicatesq = $assertor->assertQuery('vBForum:smilie',	['smilietext' => $smiliestext]);
			foreach ($duplicatesq AS $smilie)
			{
				$duplicates[$smilie['smilietext']] = 1;
			}
			unset($smiliestext);
		}

		foreach ($vbulletin->GPC['doimage'] AS $path => $yes)
		{
			if ($yes)
			{
				//some of these don't seem to be always present depending on the "imagetype"
				//default the ones that need it.
				$title 			= $vbulletin->GPC['ititle'][$path];
				$minimumposts 	= $vbulletin->GPC['iminimumposts'][$path] ?? 0;
				$smilietext 	= $vbulletin->GPC['ismilietext'][$path];
				$category 		= $vbulletin->GPC['icat'][$path];
				$path 			= $vbulletin->GPC['imagespath'] . '/' . urldecode($path);

				echo "\t<li>" . $vbphrase["processing_{$itemtype}"] . " ";

				if (!isset($duplicates["$smilietext"]))
				{
					/*insert query*/
					$params = [vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_INSERT, $itemid => NULL, 'title' => $title];
					if ($vbulletin->GPC['table'] == 'avatar')
					{
						$params['minimumposts'] = $minimumposts;
					}
					if ($vbulletin->GPC['table'] == 'smilie')
					{
						$params['smilietext'] = $smilietext;
					}
					$params = array_merge($params, [$itempath => $path, 'imagecategoryid' => $category, 'displayorder' => 1]);
					$assertor->assertQuery("vBForum:" . $vbulletin->GPC['table'], $params);
					echo $vbphrase['okay'] . ".</li>\n";
				}
				else
				{
					echo construct_phrase($vbphrase['smilietext_x_taken'], $smilietext) . ".</li>\n";
				}
			}
		}
		echo "</ul>\n";

	}
	build_image_cache($vbulletin->GPC['table']);

	$doneinsert = 1;
	$_REQUEST['do'] = 'insertmultiple';
}

// ###################### Start Insert Multiple #######################
if ($_REQUEST['do'] == 'insertmultiple')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'imagespath'      => vB_Cleaner::TYPE_STR,
		'perpage'         => vB_Cleaner::TYPE_INT,
		'page'            => vB_Cleaner::TYPE_STR, // this must be str for the trim()!
		'imagecategoryid' => vB_Cleaner::TYPE_INT
	));

	$vbulletin->GPC['imagespath'] = preg_replace('/\/$/s', '', $vbulletin->GPC['imagespath']);
	$path = $vbulletin->GPC['imagespath'];

	// try to open the specified file path to images
	if (!$handle = @opendir(DIR . "/" . $vbulletin->GPC['imagespath']))
	{
		print_stop_message2('invalid_file_path_specified');
	}
	else
	{
		// make a $pathcache array containing the filepaths of the existing images in the db
		$pathcache = array();
		$items = $assertor->assertQuery('vBForum:' . $vbulletin->GPC['table'], []);
		foreach ($items AS $item)
		{
			$pathcache[$item[$itempath]] = 1;
		}
		unset($item);

		// populate the $filearray with paths of images that are not contained in the $pathcache
		$imagelist = ['.jpg', '.gif', '.jpeg', '.jpe', '.png', '.bmp'];

		$filearray = [];
		while($file = readdir($handle))
		{
			if ($file == '.' OR $file == '..')
			{
				continue;
			}
			$ext = strtolower(strrchr($file, '.'));
			if (in_array($ext, $imagelist) AND !isset($pathcache["$path/$file"]))
			{
				$filearray[] = $file;
			}
		}
		unset($pathcache);
		closedir($handle);

		// sort naturally, but redo the keys
		natcasesort($filearray);
		$filearray = array_values($filearray);

		// now display the returned items

		// get some variables defining what parts of the $filearray to show
		$page = intval($vbulletin->GPC['page']);
		if ($page < 1)
		{
			$page = 1;
		}

		if ($vbulletin->GPC['perpage'] < 1)
		{
			$vbulletin->GPC['perpage'] = 10;
		}

		$startat = ($page - 1) * $vbulletin->GPC['perpage'];
		$endat = $startat + $vbulletin->GPC['perpage'];
		$totalitems = sizeof($filearray);
		$totalpages = ceil($totalitems / $vbulletin->GPC['perpage']);

		// if $endat is greater than $totalitems truncate it so we don't get empty rows in the table
		if ($endat > $totalitems)
		{
			$endat = $totalitems;
		}

		// check to see that the file array actually has some contents
		if ($totalitems == 0)
		{
			// check to see if we are coming from an insert operation...
			if (!empty($doneinsert))
			{
				if ($itemtype == 'avatar')
				{
					print_stop_message2('need_to_rebuild_avatars');
				}
				else
				{
					print_stop_message2("all_{$itemtypeplural}_added",'image', array(
						'do' => 'modify',
						'table' => $vbulletin->GPC['table']
					));
				}
			}
			else
			{
				print_stop_message2("no_new_{$itemtypeplural}");
			}
		}
		else
		{
			print_form_header('admincp/image', 'doinsertmultiple');
			construct_hidden_code('table', $vbulletin->GPC['table']);
			construct_hidden_code('imagespath', $vbulletin->GPC['imagespath']);
			construct_hidden_code('perpage', $vbulletin->GPC['perpage']);
			construct_hidden_code('imagecategoryid', $vbulletin->GPC['imagecategoryid']);

			// make the headings for the table
			$header = array();
			$header[] = $vbphrase['image'];
			$header[] = $vbphrase['title'];
			switch ($vbulletin->GPC['table'])
			{
				case 'avatar':
					$header[] = $vbphrase['minimum_posts'];
					break;
				case 'smilie':
					$header[] = $vbphrase['text_to_replace'];
					break;
			}
			$header[] = '<input type="checkbox" name="allbox" title="' . $vbphrase['check_all'] . '" onclick="js_check_all(this.form);" /><input type="hidden" name="page" value="' . $page . '" />';

			// get $colspan based on the number of headings and use it for the print_table_header() call
			print_table_header(construct_phrase($vbphrase["adding_multiple_{$itemtypeplural}_reading_from_x"], $vbulletin->options['bburl'] . '/' . $path), sizeof($header));
			// display the column headings
			print_cells_row($header, 1, 0, 1);

			// now run through the appropriate bits of $filearray and display
			for ($i = $startat; $i < $endat; $i++)
			{
				// make a nice title from the filename
				$titlefield = substr($filearray[$i], 0, strrpos($filearray[$i], '.'));

				$cell = [];
				$cell[] = construct_img_html("$path/". $filearray[$i]) . "<br /><span class=\"smallfont\">" . $filearray[$i] . '</span>';
				$cell[] = "<input type=\"text\" class=\"bginput\" name=\"ititle[" . urlencode($filearray["$i"]) . "]\" tabindex=\"1\" value=\"" . ucwords(preg_replace('/(_|-)/siU', ' ', $titlefield)) . "\" size=\"25\" />\n\t<select name=\"icat[" . urlencode($filearray["$i"]) . "]\" tabindex=\"1\" class=\"bginput\">\n" . construct_select_options(fetch_image_categories_array($catid), $vbulletin->GPC['imagecategoryid']) . "\t</select>\n\t";

				// add extra cells if needed
				switch ($vbulletin->GPC['table'])
				{
					case 'avatar':
						$cell[] = "<input type=\"text\" class=\"bginput\" name=\"iminimumposts[" . urlencode($filearray["$i"]) . "]\" tabindex=\"1\" value=\"0\" size=\"5\" />";
						break;
					case 'smilie':
						$cell[] = "<input type=\"text\" class=\"bginput\" name=\"ismilietext[" . urlencode($filearray["$i"]) . "]\" tabindex=\"1\" value=\":$titlefield:\" size=\"15\" maxlength=\"20\" />";
						break;
				}

				$cell[] = "<input type=\"checkbox\" name=\"doimage[" . urlencode($filearray["$i"]) . "]\" value=\"1\" tabindex=\"1\" />";

				print_cells_row($cell, 0, 0, 1);
			}

			//This is kind of a mess.  We move the normal submit buttons to a description row when we have pagination (which occupies
			//the table footer slot).  This messes with the table striping and the flow of the usual function calls.  We should move this to
			//use print_pagination_form and standardize the format but that's beyond the currnet scope.
			$addbutton = construct_submit_button($vbphrase['add_' . $itemtypeplural], [], ['name' => 'doinsertmultiple']);

			// make a page navigator if $totalitems is greater than $perpage
			if ($vbulletin->GPC['perpage'] < $totalitems)
			{
				print_description_row($addbutton, false, $colspan, '', 'vbright');

				$pagenav = "<span class=\"smallfont\">" . $vbphrase['pages'] . " ($totalpages)</span> &nbsp; &nbsp; ";
				for ($i = 1; $i <= $totalpages; $i++)
				{
					$attributes = ['name' => 'page'];
					if ($i == $page)
					{
						$attributes['disabled'] = 'diabled';
					}

					$pagenav .= construct_submit_button($i, [], $attributes);
				}
				print_table_footer($colspan, $pagenav);
			}
			else
			{
				print_table_button_footer([$addbutton], $colspan);
			}
		} // end if($totalitems)
	} // end if(opendir())
}

// ###################### Start Insert #######################
if ($_POST['do'] == 'insert')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'title'           => vB_Cleaner::TYPE_STR,
		'minimumposts'    => vB_Cleaner::TYPE_INT,
		'smilietext'      => vB_Cleaner::TYPE_STR,
		'imagespath'      => vB_Cleaner::TYPE_STR,
		'imagecategoryid' => vB_Cleaner::TYPE_INT,
		'displayorder'    => vB_Cleaner::TYPE_INT
	));

	if (!$vbulletin->GPC['imagespath'] OR ($vbulletin->GPC['table'] == 'smilie' AND !$vbulletin->GPC['smilietext']) OR !$vbulletin->GPC['title'])
	{
		print_stop_message2('please_complete_required_fields');
	}

	if ($vbulletin->GPC['table'] == 'smilie' AND $assertor->getRow('vBForum:getSmilieTextCmp', array('smilietext' => $vbulletin->GPC['smilietext'])))
	{
		if (defined('IMAGE_UPLOADED') AND IMAGE_UPLOADED AND file_exists($imagepath))
		{
			// if the image is being uploaded zap it
			unlink($imagepath);
		}
		// this smilie already exists
		print_stop_message2(array('smilie_replace_text_x_exists',  $vbulletin->GPC['smilietext']));
	}

	if (!defined('IMAGE_UPLOADED') OR IMAGE_UPLOADED !== true)
	{
		// we are adding a single item via the form, use user input for path
		$imagepath =& $vbulletin->GPC['imagespath'];
	}

	/*insert query*/
	$params = array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_INSERT, $itemid => NULL, 'title' => $vbulletin->GPC['title']);
	if ($vbulletin->GPC['table'] == 'avatar')
	{
		$params['minimumposts'] = $vbulletin->GPC['minimumposts'];
	}
	if ($vbulletin->GPC['table'] == 'smilie')
	{
		$params['smilietext'] = $vbulletin->GPC['smilietext'];
	}
	$params = array_merge($params, array($itempath => $imagepath, 'imagecategoryid' => $vbulletin->GPC['imagecategoryid'], 'displayorder' => $vbulletin->GPC['displayorder']));
	$assertor->assertQuery("vBForum:" . $vbulletin->GPC['table'], $params);

	build_image_cache($vbulletin->GPC['table']);

	if ($itemtype == 'avatar')
	{
		print_stop_message2('need_to_rebuild_avatars');
	}
	else
	{
		print_stop_message2("saved_{$itemtype}_successfully",'image', array(
			'do' => 'viewimages',
			'table' => $vbulletin->GPC['table'],
			'imagecategoryid' => $vbulletin->GPC['imagecategoryid']
		));
	}
}

// ###################### Start Add #######################
if ($_REQUEST['do'] == 'add')
{
	print_form_header('admincp/image', 'insert');
	construct_hidden_code('table', $vbulletin->GPC['table']);
	print_table_header($vbphrase["add_a_single_{$itemtype}"]);
	print_input_row($vbphrase['title'], 'title');
	switch($vbulletin->GPC['table'])
	{
		case 'avatar':
			print_input_row($vbphrase['minimum_posts'], 'minimumposts', 0);
			break;
		case 'smilie':
			print_input_row($vbphrase['text_to_replace'], 'smilietext', '', true, 35, 100);
			break;
	}
	print_input_row($vbphrase["{$itemtype}_file_path"], 'imagespath');
	print_select_row($vbphrase["{$itemtype}_category"], 'imagecategoryid', fetch_image_categories_array($catid));
	print_input_row($vbphrase['display_order'],'displayorder',1);
	print_submit_row($vbphrase["add_{$itemtype}"]);

	print_form_header('admincp/image', 'insertmultiple');
	construct_hidden_code('table', $vbulletin->GPC['table']);
	print_table_header($vbphrase["add_multiple_{$itemtypeplural}"]);
	print_select_row($vbphrase["{$itemtype}_category"], 'imagecategoryid', fetch_image_categories_array($catid));
	print_input_row($vbphrase["{$itemtypeplural}_file_path"], 'imagespath', "images/" . $vbulletin->GPC['table'] . 's');
	print_input_row($vbphrase["{$itemtypeplural}_to_show_per_page"], 'perpage', 10);
	print_submit_row($vbphrase["add_{$itemtypeplural}"]);

}

// ###################### Start Update #######################
if ($_POST['do'] == 'update')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'id'                    => vB_Cleaner::TYPE_INT,
		'title'                 => vB_Cleaner::TYPE_STR,
		'minimumposts'          => vB_Cleaner::TYPE_INT,
		'imagespath'            => vB_Cleaner::TYPE_STR,
		'imagecategoryid'       => vB_Cleaner::TYPE_INT,
		'displayorder'          => vB_Cleaner::TYPE_INT,
		'smilietext'            => vB_Cleaner::TYPE_STR,
		'page'                  => vB_Cleaner::TYPE_INT,
		'perpage'               => vB_Cleaner::TYPE_INT,
		'massmove'              => vB_Cleaner::TYPE_INT,
		'returnimagecategoryid' => vB_Cleaner::TYPE_UINT,
	));

	if (!$vbulletin->GPC['imagespath'] OR ($vbulletin->GPC['table'] == 'smilie' AND !$vbulletin->GPC['smilietext']) OR !$vbulletin->GPC['title'])
	{
		print_stop_message2('please_complete_required_fields');
	}

	if ($vbulletin->GPC['table'] == 'smilie')
	{
		$oldtext = $assertor->getRow("vBForum:" . $vbulletin->GPC['table'],	[$itemid => $vbulletin->GPC['id']]);

		$smilie = $assertor->getRow('vBForum:fetchSmilieId', ['smilietext' => $vbulletin->GPC['smilietext']]);
		if ( ($oldtext['smilietext'] != $vbulletin->GPC['smilietext']) AND $smilie AND $smilie->valid())
		{
			// this smilie already exists
			print_stop_message2(['smilie_replace_text_x_exists',  $vbulletin->GPC['smilietext']]);
		}

		$prefix = vB::getDatastore()->getOption('smiliepath');

		if (substr($vbulletin->GPC['imagespath'], strlen($prefix)) != $prefix)
		{
			$vbulletin->GPC['imagespath'] = $prefix . $vbulletin->GPC['imagespath'];
		}
	}

	$params = [vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE, 'title' => $vbulletin->GPC['title']];
	if ($vbulletin->GPC['table'] == 'avatar')
	{
		$params['minimumposts'] = $vbulletin->GPC['minimumposts'];
	}
	if ($vbulletin->GPC['table'] == 'smilie')
	{
		$params['smilietext'] = $vbulletin->GPC['smilietext'];
	}
	$params = array_merge($params,
		array($itempath => $vbulletin->GPC['imagespath'],
			'imagecategoryid' => $vbulletin->GPC['imagecategoryid'],
			'displayorder' => $vbulletin->GPC['displayorder'],
			vB_dB_Query::CONDITIONS_KEY => array(
				array('field' => $itemid, 'value' =>$vbulletin->GPC['id'], 'operator' => vB_dB_Query::OPERATOR_EQ)
			)
		)
	);
	$assertor->assertQuery("vBForum:" . $vbulletin->GPC['table'], $params);

	build_image_cache($vbulletin->GPC['table']);

	if ($itemtype == 'avatar')
	{
			$backurl =  "image.php?do=viewimages" .
				"&amp;table=" . $vbulletin->GPC['table'] .
				"&amp;pp=" . $vbulletin->GPC['perpage'] .
				"&amp;page=" . $vbulletin->GPC['page'] .
				"&amp;massmove=" . $vbulletin->GPC['massmove'] .
				"&amp;imagecategoryid=" . $vbulletin->GPC['returnimagecategoryid'];
			print_stop_message2('need_to_rebuild_avatars', NULL, [], $backurl);
	}
	else
	{
		print_stop_message2("saved_{$itemtype}_successfully",'image', array(
			'do' => 'viewimages',
			'table' => $vbulletin->GPC['table'],
			'page' => $vbulletin->GPC['page'],
			'pp' => $vbulletin->GPC['perpage'],
			'massmove' => $vbulletin->GPC['massmove'],
			'imagecategoryid' => $vbulletin->GPC['returnimagecategoryid']
		));
	}
}

// ###################### Start Edit #######################
if ($_REQUEST['do'] == 'edit')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'id'              => vB_Cleaner::TYPE_INT,
		'page'            => vB_Cleaner::TYPE_INT,
		'perpage'         => vB_Cleaner::TYPE_INT,
		'massmove'        => vB_Cleaner::TYPE_INT,
		'imagecategoryid' => vB_Cleaner::TYPE_UINT
	));

	$item = $assertor->getRow('vBForum:' . $vbulletin->GPC['table'],
		array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT, $itemid => $vbulletin->GPC['id'])
	);

	print_form_header('admincp/image', 'update');
	construct_hidden_code('id', $vbulletin->GPC['id']);
	construct_hidden_code('table', $vbulletin->GPC['table']);
	construct_hidden_code('page', $vbulletin->GPC['page']);
	construct_hidden_code('perpage', $vbulletin->GPC['perpage']);
	construct_hidden_code('massmove', $vbulletin->GPC['massmove']);
	construct_hidden_code('returnimagecategoryid', $vbulletin->GPC['imagecategoryid']);
	print_table_header(construct_phrase($vbphrase['x_y_id_z'], $vbphrase["$itemtype"], $item['title'], $item["$itemid"]));
	print_label_row($vbphrase['image'], construct_img_html($item["$itempath"]));
	print_input_row($vbphrase['title'], 'title', $item['title']);

	if ($vbulletin->GPC['table'] == 'smilie')
	{
		$prefix = vB::getDatastore()->getOption('smiliepath');

		if (substr($item[$itempath], 0, strlen($prefix)) == $prefix)
		{
			$item[$itempath] = substr($item[$itempath], strlen($prefix));
		}
	}
	switch($vbulletin->GPC['table'])
	{
		case 'avatar':
			print_input_row($vbphrase['minimum_posts'], 'minimumposts', $item['minimumposts']);
			break;
		case 'smilie':
			print_input_row($vbphrase['text_to_replace'], 'smilietext', $item['smilietext'], true, 35, 100);
			break;
	}
	print_input_row($vbphrase["{$itemtype}_file_path"], 'imagespath', $item["$itempath"]);
	print_select_row($vbphrase["{$itemtype}_category"], 'imagecategoryid', fetch_image_categories_array($catid), $item['imagecategoryid']);
	print_input_row($vbphrase['display_order'], 'displayorder', $item['displayorder']);
	print_submit_row();
}

// ###################### Start Update Display Order #######################
if ($_POST['do'] == 'displayorder')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'order'				=> vB_Cleaner::TYPE_NOCLEAN,
		'category'			=> vB_Cleaner::TYPE_ARRAY_INT,
		'doorder' 			=> vB_Cleaner::TYPE_STR,
		'massmove' 			=> vB_Cleaner::TYPE_INT
	));


	// check that the correct submit button was pressed...
	if ($vbulletin->GPC['doorder'])
	{
		if (!$vbulletin->GPC['massmove'] AND !is_array($vbulletin->GPC['order']))
		{
			print_stop_message2('please_complete_required_fields');
		}
		else if ($vbulletin->GPC['massmove'])
		{
			foreach($vbulletin->GPC['category'] AS $id => $imagecategoryid)
			{
				if ($imagecategoryid)
				{
					$assertor->assertQuery('vBForum:' . $table, array(
						vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
						'imagecategoryid' => $imagecategoryid,
						vB_dB_Query::CONDITIONS_KEY => array($itemid => $id)
					));
				}
			}
		}
		else
		{
			$items = $assertor->assertQuery('vBForum:' . $table,
				array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT)
			);
			$ordercache = array();
			if ($items AND $items->valid())
			{
				foreach ($items AS $item)
				{
					$ordercache["$item[$itemid]"] = $item['displayorder'];
				}
			}
			unset($item);

			foreach($vbulletin->GPC['order'] AS $id => $displayorder)
			{
				$id = intval($id);
				$displayorder = intval($displayorder);
				if ($displayorder != $ordercache["$id"])
				{
					$assertor->assertQuery('vBForum:' . $table, array(
						vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
						'displayorder' => $displayorder,
						vB_dB_Query::CONDITIONS_KEY => array($itemid => $id)
					));
				}
			}
		}
	}
	build_image_cache($vbulletin->GPC['table']);

	$_REQUEST['do'] = 'viewimages';
}

// ###################### Start View Images #######################
if ($_REQUEST['do'] == 'viewimages')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'pagesub'         => vB_Cleaner::TYPE_INT,
		'page'            => vB_Cleaner::TYPE_INT,
		'perpage'         => vB_Cleaner::TYPE_INT,
		'imagecategoryid' => vB_Cleaner::TYPE_INT,
		'massmove'        => vB_Cleaner::TYPE_INT
	));

	$page = $vbulletin->GPC['page'];
	if (!empty($vbulletin->GPC['pagesub']))
	{
		$page = $vbulletin->GPC['pagesub'];
	}

	$page = max($page, 1);

	if ($vbulletin->GPC['perpage'] < 1)
	{
		$vbulletin->GPC['perpage'] = 20;
	}

	$startat = ($page - 1) * $vbulletin->GPC['perpage'];

	// check to see if we should be displaying a single image category
	$categoryinfo = array();
	if ($vbulletin->GPC['imagecategoryid'])
	{
		$categoryinfo = $assertor->getRow('vBForum:imagecategory', array('imagecategoryid' => $vbulletin->GPC['imagecategoryid']));
		// check to ensure that the returned category is of the appropriate type
		if ($categoryinfo['imagetype'] != $catid)
		{
			$categoryinfo = array();
			$vbulletin->GPC['imagecategoryid'] = 0;
		}
	}

	$params = array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_COUNT);
	if ($vbulletin->GPC['imagecategoryid'])
	{
		$params['imagecategoryid'] = $vbulletin->GPC['imagecategoryid'];

	}
	$count = $assertor->getRow('vBForum:' . $table, $params);

	$totalitems = $count['count'];
	$totalpages = max(1, ceil($totalitems / $vbulletin->GPC['perpage']));

	if ($startat > $totalitems)
	{
		$page = 1;
		$startat = 0;
	}

	$items = $assertor->assertQuery('vBForum:fetchImagesSortedLimited',
		array(
			'table' => $vbulletin->GPC['table'],
			'categoryinfo' => $categoryinfo,
			vB_dB_Query::PARAM_LIMITSTART => $startat,
			vB_dB_Query::PARAM_LIMIT => $vbulletin->GPC['perpage'],
			'imagecategoryid' => $vbulletin->GPC['imagecategoryid']
		)
	);
	$itemcache = [];
	if ($items AND $items->valid())
	{
		foreach ($items AS $item)
		{
			if ($vbulletin->GPC['table'] != 'avatar')
			{
				$item['minimumposts'] = 0;
			}
			$itemcache["$item[minimumposts]"][] = $item;
		}
	}

	print_form_header('admincp/image', 'displayorder');
	construct_hidden_code('table', $vbulletin->GPC['table']);
	construct_hidden_code('imagecategoryid', $vbulletin->GPC['imagecategoryid']);
	construct_hidden_code('massmove', $vbulletin->GPC['massmove']);
	print_table_header(
		$vbphrase["{$itemtype}_manager_gattachment_image"]
		. ' <span class="normal">'
		. ($categoryinfo ? "$categoryinfo[title] - " : '')
		. construct_phrase($vbphrase['page_x_of_y'], $page, $totalpages)
		. '</span>',
		$img_per_row
	);

	$lastcategory = null;
	foreach ($itemcache AS $minimumposts => $val)
	{
		if ($vbulletin->GPC['table'] == 'avatar')
		{
			print_description_row($vbphrase['minimum_posts'] . ': ' . $minimumposts, 0, $img_per_row, 'thead', 'center');
			$lastcategory = 0;
		}

		$cell = [];
		$i = 0;
		foreach ($val AS $item)
		{
			if ($item['imagecategoryid'] != $lastcategory AND !$categoryinfo)
			{
				//if we have a prior category we need to print it's last row
				if($cell)
				{
					//clean up the row with blank cells
					while($i < $img_per_row)
					{
						$cell[] = '&nbsp;';
						$i++;
					}
					print_image_item_row($cell);
				}

				$i = 0;

				if(empty($item['category']))
				{
					$description = '(' . $vbphrase['uncategorized_gattachment_image'] . ')';
				}
				else
				{
					$description = $item['category'];
				}
				print_description_row('- - ' . $description . ' - -', 0, $img_per_row, 'thead', 'center');
			}

			//the row is full, start a new one before we add the cell
			if ($i >= $img_per_row)
			{
				$i = 0;
				print_image_item_row($cell);
			}

			$cell[] = print_image_item(
				$item,
				$itemid,
				$itempath,
				$vbulletin->GPC['page'],
				$vbulletin->GPC['perpage'],
				$catid,
				$vbulletin->GPC['massmove'],
				$vbulletin->GPC['imagecategoryid']
			);

			$lastcategory = $item['imagecategoryid'];
			$i++;
		}

		//clean up the row with blank cells
		while($i < $img_per_row)
		{
			$cell[] = '&nbsp;';
			$i++;
		}
		print_image_item_row($cell);
	}

	construct_hidden_code('page', $page);
	if ($totalitems > $vbulletin->GPC['perpage'])
	{
		$pagebuttons = "\n\t" . $vbphrase['pages'] . ": ($totalpages)\n";
		for ($i = 1; $i <= $totalpages; $i++)
		{
			$pagebuttons .= "\t<input type=\"submit\" class=\"button\" name=\"pagesub\" value=\" $i \"" . ($i == $page ? ' disabled="disabled"' : '') . " tabindex=\"1\" />\n";
		}
		$pagebuttons .= "\t&nbsp; &nbsp; &nbsp; &nbsp;";
	}
	else
	{
		$pagebuttons = '';
	}

	if ($vbulletin->GPC['massmove'])
	{
		$categories = '<option value="0"></option>';
		$categories .= construct_select_options(fetch_image_categories_array($catid));
		$categories = '<select name="selectall" class="bginput" onchange="js_select_all(this.form);">' . $categories . '</select>';

		$buttontext = $vbphrase['mass_move_gcpglobal'];
	}
	else
	{
		$categories = '';
		$buttontext = $vbphrase['save_display_order'];
	}

	$actionbutton = "<input type=\"submit\" class=\"button\" name=\"doorder\" value=\"" . $buttontext . "\" tabindex=\"1\" />";
	$perpagebox = "<input type=\"text\" name=\"perpage\" value=\"" . $vbulletin->GPC['perpage'] . "\" size=\"3\" tabindex=\"1\" />";
	$gobutton = "<input type=\"submit\" class=\"button\" value=\"" . $vbphrase['go'] . "\" tabindex=\"1\" />";

	print_table_footer($img_per_row, "\n\t$categories $actionbutton\n\t&nbsp; &nbsp; &nbsp; &nbsp;$pagebuttons" . "\n\t" .
		$vbphrase['per_page'] . "\n\t" . $perpagebox . "\n\t" . $gobutton . "\n\t");

	echo "<p align=\"center\">" .
		construct_link_code($vbphrase["add_{$itemtype}"], "image.php?do=add&table=" . $vbulletin->GPC['table']) .
		construct_link_code($vbphrase["edit_{$itemtype}_categories"], "image.php?do=modify&table=" . $vbulletin->GPC['table']) .
	"</p>";
}

// ###################### Start Modify Categories #######################
if ($_REQUEST['do'] == 'modify')
{
	$categories = $assertor->assertQuery('vBForum:fetchCategoryImages', [
		'table' => $vbulletin->GPC['table'],
		'itemid' => $itemid,
		'catid' => $catid
	]);

	if ($categories->valid())
	{
		$table = $vbulletin->GPC['table'];
		print_form_header2('admincp/image', 'docategorydisplayorder');
		print_table_start2();

		construct_hidden_code('table', $table);

		$headers = [
			$vbphrase['title'],
			$vbphrase['contains'],
			$vbphrase['display_order'],
			$vbphrase['controls']
		];
		$colcount = count($headers);

		print_table_header($vbphrase['edit_' . $itemtype . '_categories'], $colcount);
		print_cells_row2($headers, 'thead');
		foreach ($categories AS $category)
		{
			$args = [
				'imagecategoryid' => $category['imagecategoryid'],
				'table' => $table,
			];

			$viewurl = get_admincp_url('image', $args + ['do' => 'viewimages']);

			$controlLinks =
				construct_link_code2($vbphrase['mass_move_gcpglobal'], get_admincp_url('image', $args + ['do' => 'viewimages', 'massmove' => 1])) .
				construct_link_code2($vbphrase['view'], $viewurl) .
				construct_link_code2($vbphrase['edit'], get_admincp_url('image', $args + ['do' => 'editcategory'])) .
				construct_link_code2($vbphrase['delete'], get_admincp_url('image', $args + ['do' => 'removecategory']));

			if($category['imagetype'] != 3)
			{
				$controlLinks .= construct_link_code2($vbphrase[$itemtype . '_permissions'], get_admincp_url('image', $args + ['do' => 'editpermissions']));
			};

			$cell = [];
			$cell[] = '<a href="' . htmlspecialchars($viewurl) . '">' . $category['title'] . '</a>';
			$cell[] = vb_number_format($category['items']) . ' ' . $vbphrase[$itemtypeplural];
			$cell[] = '<input type="text" class="bginput" name="order[' . $category['imagecategoryid'] . ']" value="' . $category['displayorder'] . '" tabindex="1" size="3" />';
			$cell[] = $controlLinks;

			print_cells_row2($cell);
		}

		print_table_default_footer($vbphrase['save_display_order'], $colcount);
		echo '<p align="center">' .
			construct_link_code2($vbphrase['add_new_' . $itemtype . '_category'],  get_admincp_url('image', ['do' => 'addcategory', 'table' => $table])) .
			construct_link_code2($vbphrase['show_all_' . $itemtypeplural],  get_admincp_url('image', ['do' => 'viewimages', 'table' => $table])) .
		'</p>';
	}
	else
	{
		print_stop_message2('no_' . $itemtype . '_categories_found', 'image', ['do'=>'addcategory','table' => $table]);
	}
}

print_cp_footer();

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 115264 $
|| #######################################################################
\*=========================================================================*/
