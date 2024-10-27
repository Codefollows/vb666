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

// ##################### DEFINE IMPORTANT CONSTANTS #######################
define('CVS_REVISION', '$RCSfile$ - $Revision: 115856 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
global $phrasegroups, $specialtemplates, $vbulletin, $session, $stylestuff;
global $masterset, $vbphrase;

$phrasegroups = ['style'];
$specialtemplates = [];

// ########################## REQUIRE BACK-END ############################
require_once(dirname(__FILE__) . '/global.php');
require_once(DIR . '/includes/adminfunctions_template.php');

ignore_user_abort(true);
vB_Utility_Functions::setPhpTimeout(0);

$userContext =  vB::getUserContext();
$canadmintemplates = $userContext->hasAdminPermission('canadmintemplates');
$canadminstyles = $userContext->hasAdminPermission('canadminstyles');
$canAdminTemplatesOrStyles = ($canadmintemplates OR $canadminstyles);

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!$canAdminTemplatesOrStyles)
{
	print_cp_no_permission();
}

$vbulletin->input->clean_array_gpc('r', [
	'templateid'   => vB_Cleaner::TYPE_INT,
	'dostyleid'    => vB_Cleaner::TYPE_INT,
]);

// ############################# LOG ACTION ###############################
$message = '';
if ($vbulletin->GPC['templateid'])
{
	$message = 'template id = ' . $vbulletin->GPC['templateid'];
}
else if ($vbulletin->GPC['dostyleid'])
{
	$message = 'style id = ' . $vbulletin->GPC['dostyleid'];
}

log_admin_action($message);

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

$vb5_config =& vB::getConfig();
$vb5_options = vB::getDatastore()->getValue('options');

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'modify';
}

$full_product_info = fetch_product_list(true);

if ($_REQUEST['do'] != 'download')
{
	print_cp_header($vbphrase['style_manager_gstyle'], '', [
		get_admincp_css_tag('codemirror/lib/codemirror.css'),
		get_admincp_css_tag('codemirror/addon/hint/show-hint.css'),
		get_admincp_script_tag('vbulletin_templatemgr.js'),
	]);
}

// #############################################################################
// find custom templates that need updating

if ($_REQUEST['do'] == 'findupdates')
{
	if (!$canadmintemplates)
	{
		print_cp_no_permission();
	}
	$customcache = fetch_changed_templates();
	if (empty($customcache))
	{
		print_stop_message2('all_templates_are_up_to_date');
	}

	$stylecache = vB_Library::instance('Style')->fetchStyles(false, false);

	print_form_header('admincp/template', 'dismissmerge');
	print_table_header($vbphrase['updated_default_templates']);
	print_description_row('<span class="smallfont">' .
	construct_phrase($vbphrase['updated_default_templates_desc'],
	$vbulletin->options['templateversion']) . '</span>');
	print_table_break(' ');

	$have_dismissible = false;

	foreach ($stylecache AS $styleid => $style)
	{
		if (isset($customcache[$styleid]) AND is_array($customcache[$styleid]))
		{
			print_description_row($style['title'], 0, 3, 'thead');
			foreach ($customcache["$styleid"] AS $templateid => $template)
			{
				if (!$template['customuser'])
				{
					$template['customuser'] = $vbphrase['n_a'];
				}
				if (!$template['customversion'])
				{
					$template['customversion'] = $vbphrase['n_a'];
				}

				$product_name = $full_product_info["$template[product]"]['title'];

				if ($template['custommergestatus'] == 'merged')
				{
					$merge_text = '<span class="smallfont template-text-merged">'
						. $vbphrase['changes_automatically_merged_into_template']
						. '<span>';
				}
				else if ($template['custommergestatus'] == 'conflicted')
				{
					$merge_text = '<span class="smallfont template-text-conflicted">'
						. $vbphrase['attempted_merge_failed_conflicts']
						. '<span>';
				}
				else
				{
					$merge_text = '';
				}

				$title =
					"<b>$template[title]</b><br />"
					. "<span class=\"smallfont\">"
					. construct_phrase($vbphrase['default_template_updated_desc'],
						"$product_name $template[globalversion]",
						$template['globaluser'],
						"$product_name $template[customversion]",
						$template['customuser']
					)
					. '</span><br/>' . $merge_text
				;

				$links = [];

				$links[] = construct_link_code($vbphrase['edit_template'],
					"template.php?" . vB::getCurrentSession()->get('sessionurl') . "do=edit&amp;templateid=$templateid",
					'templatewin');

				$links[] = construct_link_code($vbphrase['view_highlighted_changes'],
					"template.php?" . vB::getCurrentSession()->get('sessionurl') .
						"do=docompare3&amp;templateid=$templateid",
					'templatewin');

				if ($template['custommergestatus'] == 'merged' AND $template['savedtemplateid'])
				{
					$links[] = construct_link_code($vbphrase['view_pre_merge_version'],
						"template.php?" . vB::getCurrentSession()->get('sessionurl') .
							"do=viewversion&amp;id=$template[savedtemplateid]&amp;type=historical",
						'templatewin');
				}

				$links[] = construct_link_code($vbphrase['revert_gcpglobal'],
					"template.php?" . vB::getCurrentSession()->get('sessionurl') .
						"do=delete&amp;templateid=$templateid&amp;dostyleid=$styleid",
					'templatewin');

				$value = '<span class="smallfont">' . implode('<br />', $links) . '</span>';

				if ($template['custommergestatus'] == 'merged')
				{
					$dismiss_checkbox = '<input type="checkbox" name="dismiss_merge[]" value="' . $templateid . '" />';
					$have_dismissible = true;
				}
				else
				{
					$dismiss_checkbox = '&nbsp;';
				}

				$cells = [
					$title,
					$value,
					$dismiss_checkbox
				];

				print_cells_row($cells, false, false, -1);
			}
		}
	}

	if ($have_dismissible)
	{
		print_submit_row($vbphrase['dismiss_selected_notifications'], false, 3);
		echo '<p class="smallfont" align="center">' . $vbphrase['dismissing_merge_notifications_cause_not_appear'] . '</p>';
	}
	else
	{
		print_table_footer();
	}
}

// #############################################################################
if ($_REQUEST['do'] == 'dismissmerge')
{
	if (!$canadmintemplates)
	{
		print_cp_no_permission();
	}
	$vbulletin->input->clean_array_gpc('r', [
		'dismiss_merge' => vB_Cleaner::TYPE_ARRAY_UINT,
	]);

	if (!$vbulletin->GPC['dismiss_merge'])
	{
		print_stop_message2('did_not_select_merge_notifications_dismiss');
	}
	else
	{
		print_form_header('admincp/template', 'dodismissmerge');
		print_table_header($vbphrase['dismiss_template_merge_notifications']);
		print_description_row(construct_phrase($vbphrase['sure_dismiss_x_merge_notifications'], sizeof($vbulletin->GPC['dismiss_merge'])));
		foreach ($vbulletin->GPC['dismiss_merge'] AS $templateid)
		{
			construct_hidden_code("dismiss_merge[$templateid]", $templateid);
		}
		print_submit_row($vbphrase['dismiss_template_merge_notifications'], false);
	}
}

// #############################################################################
if ($_POST['do'] == 'dodismissmerge')
{
	if (!$canadmintemplates)
	{
		print_cp_no_permission();
	}


	$vbulletin->input->clean_array_gpc('p', [
		'dismiss_merge' => vB_Cleaner::TYPE_ARRAY_UINT,
	]);

	if ($vbulletin->GPC['dismiss_merge'])
	{
		vB_Api::instanceInternal('template')->dismissMerge($vbulletin->GPC['dismiss_merge']);
	}

	print_stop_message2('template_merge_notifications_dismissed', 'template', ['do' => 'findupdates']);
}

// #############################################################################
// download style

if ($_REQUEST['do'] == 'download')
{
	// Allow canadminstyles OR canadmintemplates to download styles
	if (!$canAdminTemplatesOrStyles)
	{
		print_cp_no_permission();
	}

	//this is duplicative of the set at the top of the file, but leaving in because
	//we should really get more selective about when we are doing this
	vB_Utility_Functions::setPhpTimeout(1200);

	$vbulletin->input->clean_array_gpc('r', [
		'filename'        => vB_Cleaner::TYPE_STR,
		'title'           => vB_Cleaner::TYPE_NOHTML,
		'mode'            => vB_Cleaner::TYPE_UINT,
		'product'         => vB_Cleaner::TYPE_STR,
		'remove_guid'     => vB_Cleaner::TYPE_BOOL,
		'stylevars_only'  => vB_Cleaner::TYPE_BOOL,
		'stylevar_groups' => vB_Cleaner::TYPE_ARRAY_STR, // used by stylevar editor's "download stylevar group" link
	]);


	// --------------------------------------------
	// work out what we are supposed to do

	// set a default filename
	if (empty($vbulletin->GPC['filename']))
	{
		$vbulletin->GPC['filename'] = 'vbulletin-style.xml';
	}

	try
	{
		$doc = get_style_export_xml(
			$vbulletin->GPC['dostyleid'],
			$vbulletin->GPC['product'],
			$full_product_info[$vbulletin->GPC['product']]['version'],
			$vbulletin->GPC['title'],
			$vbulletin->GPC['mode'],
			$vbulletin->GPC['remove_guid'],
			$vbulletin->GPC['stylevars_only'],
			$vbulletin->GPC['stylevar_groups']
		);
	}
	catch (vB_Exception_AdminStopMessage $e)
	{
		print_stop_message2($e->getParams());
	}

	require_once(DIR . '/includes/functions_file.php');
	file_download($doc, $vbulletin->GPC['filename'], 'text/xml');
}

// #############################################################################
// upload style
if ($_REQUEST['do'] == 'upload')
{
	// keep this check in sync with ($_REQUEST['do'] == 'confirmoverwrite') AND vB_Xml_Import_Theme's importAdminCP()
	// Allow upload of styles if user has either canadminstyles or canadmintemplates.
	// However, canadminstyles will only allow textonly or limited template uploads!!!
	if (!$canAdminTemplatesOrStyles)
	{
		print_cp_no_permission();
	}

	$fields = [
		'overwritestyleid' => vB_Cleaner::TYPE_INT,
		'serverfile'       => vB_Cleaner::TYPE_STR,
		'parentid'         => vB_Cleaner::TYPE_INT,
		'title'            => vB_Cleaner::TYPE_STR,
		'anyversion'       => vB_Cleaner::TYPE_BOOL,
		'displayorder'     => vB_Cleaner::TYPE_INT,
		'userselect'       => vB_Cleaner::TYPE_BOOL,
		'startat'          => vB_Cleaner::TYPE_INT,
		'overwrite'        => vB_Cleaner::TYPE_BOOL,
	];

	$vbulletin->input->clean_array_gpc('r', $fields);
	$vbulletin->input->clean_array_gpc('f', [
		'stylefile'        => vB_Cleaner::TYPE_FILE,
	]);
	scanVbulletinGPCFile('stylefile');

	// Legacy Hook 'admin_style_import' Removed //

	//only do multipage processing for a local file.  If we do it for an uploaded file we need
	//to figure out how to
	//a) store the file locally so it will be available on subsequent page loads.
	//b) make sure that that location is shared across an load balanced servers (which
	//	eliminates any php tempfile functions)

	// got an uploaded file?
	// do not use file_exists here, under IIS it will return false in some cases
	if (is_uploaded_file($vbulletin->GPC['stylefile']['tmp_name']))
	{
		$xml = file_read($vbulletin->GPC['stylefile']['tmp_name']);
		$startat = null;
		$perpage = null;
	}
	// no uploaded file - got a local file?
	else
	{
		$serverfile = resolve_server_path(urldecode($vbulletin->GPC['serverfile']));
		if (file_exists($serverfile))
		{
			$xml = file_read($serverfile);
			$startat = $vbulletin->GPC['startat'];
			$perpage = 10;
		}
		// no uploaded file and no local file - ERROR
		else
		{
			print_stop_message2('no_file_uploaded_and_no_local_file_found_gerror');
		}
	}

	$styleApi = vB_Api::instanceInternal('style');
	$canImportCheck = $styleApi->checkCanImportStyleXML($xml);
	if (!$canImportCheck['canimport'])
	{
		print_stop_message2($canImportCheck['reason']);
	}

	// themes check.
	$xmlobj = new vB_XML_Parser($xml);
	$parsedXML = $xmlobj->parse();

	//treat a style with a child (writable) theme guid as not a theme on import.
	if (!empty($parsedXML['guid']) AND strpos($parsedXML['guid'], '-writable-') === false)
	{
		// it's a theme!

		// if overwrite isn't set, let's check if the theme already exists, and redirect to a overwrite confirmation page.
		if (empty($vbulletin->GPC['overwrite']))
		{
			$existingTheme = vB::getDbAssertor()->getRow('style', ['guid' => $parsedXML['guid']]);
			if (!empty($existingTheme))
			{
				// Redirect to a page to request overwrite.
				$args = [];
				$args['do'] = 'confirmoverwrite';
				$args['overwritestyleid'] = $existingTheme['styleid'];
				unset($fields['overwritestyleid']);

				foreach ($fields AS $name => $type)
				{
					// This was copy pasted from below
					if ($type == vB_Cleaner::TYPE_STR)
					{
						$args[$name] = $vbulletin->GPC[$name];
					}
					else if ($type == vB_Cleaner::TYPE_INT OR $type = vB_Cleaner::TYPE_BOOL)
					{
						$args[$name] = intval($vbulletin->GPC[$name]);
					}
				}

				if (is_uploaded_file($vbulletin->GPC['stylefile']['tmp_name']))
				{
					// need to keep this file uploaded at the second pageload...
					$args['require_reupload'] = true;
				}

				print_cp_redirect2('template', $args, 1 , 'admincp');
			}
		}

		$xml_importer = new vB_Xml_Import_Theme();
		try
		{
			// Note, title & parentid will be ignored for overwrite.
			$extras = [
				'title' => $vbulletin->GPC['title'],
				'parentid' => $vbulletin->GPC['parentid'],
				'displayorder' => $vbulletin->GPC['displayorder'],
				'userselect' => $vbulletin->GPC['userselect'],
			];

			$imported = $xml_importer->importAdminCP(
				$parsedXML,
				$startat,
				$perpage,
				$vbulletin->GPC['overwrite'],
				$vbulletin->GPC['overwritestyleid'],
				$vbulletin->GPC['anyversion'],
				$extras
			);
		}
		catch (vB_Exception_AdminStopMessage $e)
		{
			$args = $e->getParams();
			$errorMsg = construct_phrase($vbphrase['theme_import_failed_x'], $vbphrase[$args[0]]);
			print_cp_message($errorMsg);
		}

		// need to pass in some data to the next POST
		if (!$imported['done'] AND isset($imported['overwritestyleid']))
		{
			// See a few lines below where $args are set from the existing GPC values before they're passed into print_cp_redirect2();
			$vbulletin->GPC['overwritestyleid'] = $imported['overwritestyleid'];
		}
	}
	else
	{
		$imported = xml_import_style(
			$xml,
			$vbulletin->GPC['overwritestyleid'],
			$vbulletin->GPC['parentid'],
			$vbulletin->GPC['title'],
			$vbulletin->GPC['anyversion'],
			$vbulletin->GPC['displayorder'],
			$vbulletin->GPC['userselect'],
			$startat, $perpage
		);
	}

	if (!$imported['done'])
	{
		//build the next page url;
		$startat = $startat + $perpage;
		$args = [];
		$args['do'] = 'upload';
		$args['startat'] = $startat;

		unset($fields['startat']);
		foreach ($fields AS $name => $type)
		{
			//if its some other type this trick probably won't work and will need to be
			//handled seperately.
			if ($type == vB_Cleaner::TYPE_STR)
			{
				$args[$name] = $vbulletin->GPC[$name];
			}
			else if ($type == vB_Cleaner::TYPE_INT OR $type = vB_Cleaner::TYPE_BOOL)
			{
				$args[$name] = intval($vbulletin->GPC[$name]);
			}
		}

		print_cp_redirect(get_admincp_url('template', $args), 1);
	}

	if ($imported['master'])
	{
		$args = [];
		$args['do'] = 'massmerge';
		$args['product'] = urlencode($imported['product']);
		$args['redirect'] = 'template.php';
		$args['hash'] = CP_SESSIONHASH;
		print_cp_redirect(get_admincp_url('template', $args), 1);
	}
	else
	{
		$args = [];
		$args['do'] = 'rebuild';
		$args['goto'] = 'template.php';
		print_cp_redirect(get_admincp_url('template', $args), 1);
	}
}



// #############################################################################
// Check if user wants to overwrite the existing theme
if ($_REQUEST['do'] == 'confirmoverwrite')
{
	// keep this check in sync with ($_REQUEST['do'] == 'upload') AND vB_Xml_Import_Theme's importAdminCP()
	// canadminstyle should allow style uploads and overwrites, but ONLY iff there are no template overwrites
	// or if all overwritten templates are textonly.
	if (!$canAdminTemplatesOrStyles)
	{
		print_cp_no_permission();
	}

	$fields = [
		'overwritestyleid' => vB_Cleaner::TYPE_INT,
		'serverfile'       => vB_Cleaner::TYPE_STR,
		'parentid'         => vB_Cleaner::TYPE_INT,
		'title'            => vB_Cleaner::TYPE_STR,
		'anyversion'       => vB_Cleaner::TYPE_BOOL,
		'displayorder'     => vB_Cleaner::TYPE_INT,
		'userselect'       => vB_Cleaner::TYPE_BOOL,
		'startat'          => vB_Cleaner::TYPE_INT,
		'require_reupload' => vB_Cleaner::TYPE_BOOL,
	];

	$vbulletin->input->clean_array_gpc('r', $fields);

	$args = [];
	$args['do'] = 'upload';
	$args['overwrite'] = true;

	foreach ($fields AS $name => $type)
	{
		// This was copy pasted from do = 'upload'
		if ($type == vB_Cleaner::TYPE_STR)
		{
			$args[$name] = $vbulletin->GPC[$name];
		}
		else if ($type == vB_Cleaner::TYPE_INT OR $type = vB_Cleaner::TYPE_BOOL)
		{
			$args[$name] = intval($vbulletin->GPC[$name]);
		}
	}

	if ($vbulletin->GPC['require_reupload'])
	{
		// If this was triggered by a file upload, not a file on the server, ignore the serverfile and require a re-upload.
		// Since the previously uploaded temporary file isn't guaranteed to still be there, we need to re-upload it and allow
		// the importer to import it all in one go.
		unset($args['serverfile']);
		// Copied from print_confirmation(). We basically want the confirmation page with the upload row replacing
		// the description row.  This is a one off but would be nice to consolidate the formatting.
		echo "<p>&nbsp;</p><p>&nbsp;</p>";
		print_form_header2('admincp/template', 'upload', [], ['enctype' => 'multipart/form-data']);
		print_table_start2();

		foreach ($args AS $varname => $value)
		{
			construct_hidden_code($varname, $value);
		}
		print_table_header($vbphrase['confirm_action']);
		print_upload_row($vbphrase['theme_overwrite_reupload_xml_file'], 'stylefile', 999999999);
		print_table_button_footer([['submit', $vbphrase['continue']], ['goback', $vbphrase['go_back']]]);
	}
	else
	{
		print_confirmation($vbphrase, 'template', 'upload', $vbphrase['theme_confirm_overwrite'], $args);
	}
}

// #############################################################################
// file manager
if ($_REQUEST['do'] == 'files')
{
	// Allow either canadmintemplates or canadminstyles to access download/upload.
	// For canadminstyle only, upload should NOT allow uploads of any non-textonly-templates.
	// I don't think we care about downloads as long as they have *either* perm.
	if (!$canAdminTemplatesOrStyles)
	{
		print_cp_no_permission();
	}

	/*
		Inputs used by the "Download Stylevar Link" - prefill the download form
	 */
	$vbulletin->input->clean_array_gpc('r', [
		'skip_upload_form'   => vB_Cleaner::TYPE_BOOL,
		'filename'        => vB_Cleaner::TYPE_STR,
		'title'           => vB_Cleaner::TYPE_NOHTML,
		'mode'            => vB_Cleaner::TYPE_UINT,
		'readonly'        => vB_Cleaner::TYPE_ARRAY_BOOL,
		'mode_readonly'   => vB_Cleaner::TYPE_BOOL,
		'product'         => vB_Cleaner::TYPE_STR,
		'remove_guid'     => vB_Cleaner::TYPE_BOOL,
		'stylevars_only'  => vB_Cleaner::TYPE_BOOL,
		'stylevar_groups' => vB_Cleaner::TYPE_ARRAY_STR, // used by stylevar editor's "download stylevar group" link
	]);


	$stylecache = vB_Library::instance('Style')->fetchStyles(false, false);
	register_js_phrase('you_did_not_specify_a_file_to_upload');
	?>
	<script type="text/javascript">
	<!--
	function js_fetch_style_title(sel)
	{
		let option = sel.options[sel.selectedIndex];
		document.forms.downloadform.title.value = option.text.replace(/^-* */, '');
	}
	// -->
	</script>
	<?php

	$depthoffset = 0;
	$styleoptions = [];
	if (vB::isDebug())
	{
		$styleoptions[-1] = $vbphrase['master_style'];
		$depthoffset = 1;
	}
	foreach ($stylecache AS $styleid => $style)
	{
		$styleoptions[$styleid] = construct_depth_mark($style['depth'] + $depthoffset, '--') . ' ' . $style['title'];
	}

	//If we didn't pass a style then use the first one explicitly, this will be the master if we are showing it.
	$styleid = $vbulletin->GPC['dostyleid'];
	if ($styleid == 0)
	{
		$styleid = array_key_first($styleoptions);
	}

	/*
		Download Form
		If any values were explicitly specified (currently only by the "Download StyleVar Group" link in the
		stylevar editor, see admincp/stylevar.php), prefill those values.
	 */

	print_form_header('admincp/template', 'download', 0, 1, 'downloadform" target="download');
	print_table_header($vbphrase['download']);

	// styleid
	$disableOthers = false;
	if (!empty($vbulletin->GPC['readonly']['dostyleid']))
	{
		$disableOthers = true;
	}
	print_label_row($vbphrase['style'], '
		<select name="dostyleid" onchange="js_fetch_style_title(this);" tabindex="1" class="bginput">
		' . construct_select_options($styleoptions, $styleid, false, $disableOthers) . '
		</select>
	', '', 'top', 'dostyleid');

	// product, title, & filename
	print_select_row($vbphrase['product'], 'product', fetch_product_list());
	//it's not clear if we ever pass a title to this action and previously it would get overwritten by a JS call
	//anyway but it seems like if we have it and tried to set it we should honor that.  Otherwise set to the default
	//stylename (which mimics the prior js approach).
	$defaulttitle = ($vbulletin->GPC['title'] ? $vbulletin->GPC['title'] : $styleoptions[$styleid]);
	print_input_row($vbphrase['title'], 'title', $defaulttitle);
	$filename = (empty($vbulletin->GPC['filename']) ? 'vbulletin-style.xml' : $vbulletin->GPC['filename']);
	print_input_row($vbphrase['filename_gcpglobal'], 'filename', $filename);

	// Export options
	$modeReadonly = "";
	if (!empty($vbulletin->GPC['readonly']['mode']))
	{
		// This is used by the stylevar editor's Download StyleVar Group link.
		// If the style ONLY has inherited stylevars and no customizations on its own,
		// let's not allow them to pick the "Get customizations made only in this style"
		// because that'll just show them an error page.
		$modeReadonly = ' disabled';
	}

	$modeChecked = [
		0 => ' checked="checked"',
		1 => $modeReadonly,
		2 => $modeReadonly,
	];

	if (isset($_REQUEST['mode']))
	{
		$modeChecked[0] = $modeReadonly;
		$modeChecked[intval($vbulletin->GPC['mode'])] = ' checked="checked"';
	}

	print_label_row($vbphrase['options'], '
		<span class="smallfont">
			<label for="rb_mode_0"><input type="radio" name="mode" value="0" id="rb_mode_0" tabindex="1"' . $modeChecked[0] . ' />' . $vbphrase['get_customizations_from_this_style_only'] . '</label><br />
			<label for="rb_mode_1"><input type="radio" name="mode" value="1" id="rb_mode_1" tabindex="1"' . $modeChecked[1] . ' />' . $vbphrase['get_customizations_from_parent_styles'] . '</label><br />' .
			($vb5_config['Misc']['debug'] ?
				'<label for="rb_mode_2"><input type="radio" name="mode" value="2" id="rb_mode_2" tabindex="1"' . $modeChecked[2] . ' />' . $vbphrase['download_as_master'] . '</label>' : '') . '
		</span>
	', '', 'top', 'mode');

	// remove_guid
	$disableCheckbox = false;
	if (!empty($vbulletin->GPC['readonly']['remove_guid']))
	{
		$disableCheckbox = true;
	}
	print_checkbox_row(
		$vbphrase['remove_unique_identifier'], //title
		'remove_guid', //name
		$vbulletin->GPC['remove_guid'], //checked
		1, //value
		$vbphrase['remove_guid'], //labeltext
		[], //extra
		$disableCheckbox // disabled
	);

	// Stylevar only
	$disabled = "";
	if (!empty($vbulletin->GPC['readonly']['stylevars_only']))
	{
		$disabled = " disabled";
	}
	print_label_row(
		$vbphrase['stylevars_only_label'],
		'<span class="smallfont">
			<label for="rb_stylevars_only_0">
				<input type="radio" name="stylevars_only" value="0" id="rb_stylevars_only_0" tabindex="1"
					class="js-stylevar-only"'
					// If stylevars_only is explicitly set (by the stylevar group download link, for e.g.
					// just disable the other option so they can't accidentally change it.
					. (empty($vbulletin->GPC['stylevars_only']) ? ' checked="checked"' : $disabled)
					. ' />'
				. $vbphrase['export_all_style_data'] .
			'</label><br />
			<label for="rb_stylevars_only_1">
				<input type="radio" name="stylevars_only" value="1" id="rb_stylevars_only_1" tabindex="1"
					class="js-stylevar-only"'
					. (empty($vbulletin->GPC['stylevars_only']) ? $disabled : ' checked="checked"')
					. ' />'
					. $vbphrase['export_stylevars_only']
			. '</label><br />
		</span>'
		,
		'', // class (for styling, e.g. alt, alt2)
		'top', // valign
		'stylevars_only' // helpname, todo: add admin help
	);

	// Stylevar Group(s)
	/*
		If we want to allow specifying which stylevar groups to export here, we need to take the
		styleid, & mode (to see if we need to pull stylevars from parent or just this) and
		fetch which stylevar groups are available for the style via generating the styleid list,
		see how get_stylevars_for_export() is used in adminfunctions_template.php .
		I don't think we want to generate *all* possible combinations here, so we'd probably want
		to do it via ajax or something, but leaving it out for now until requested.
		Update: Per VBV-17844, we'll just show all stylevar groups.
	 */
	/*
		This is only passed in/used by the "download stylevar group" link in the stylevar editor.
		See admincp/stylevar.php
	 */
	$stylevarGroupRows = vB::getDbAssertor()->getRows("vBForum:getStylevarGroups");
	$stylevarGroups = [];
	foreach ($stylevarGroupRows AS $__row)
	{
		$stylevarGroups[$__row['stylevargroup']] = $__row['stylevargroup'];
	}
	ksort($stylevarGroups);

	print_select_row(
		$vbphrase['download_stylevar_group_label'], // title
		'stylevar_groups[]', // name
		$stylevarGroups, // value => text array for stylevar options
		$vbulletin->GPC['stylevar_groups'],  // selected options
		false, // htmlise
		0,  // size
		true // multiple
	);


	print_submit_row($vbphrase['download']);

	//Upload Form
	if (empty($vbulletin->GPC['skip_upload_form']))
	{
		print_form_header2('admincp/template', 'upload', [], [
			'name' => 'uploadform',
			'enctype' => 'multipart/form-data',
		]);
		print_table_start2();

		print_table_header($vbphrase['import_style_xml_file']);
		print_upload_row($vbphrase['upload_xml_file'], 'stylefile', 999999999);
		print_input_row($vbphrase['import_xml_file'], 'serverfile', './install/vbulletin-style.xml');
		print_style_chooser_row('overwritestyleid', -1, '(' . $vbphrase['create_new_style'] . ')', $vbphrase['overwrite_style'], 1);
		print_yes_no_row($vbphrase['ignore_style_version'], 'anyversion', 0);
		print_description_row($vbphrase['following_options_apply_only_if_new_style'], 0, 2, 'thead" style="font-weight:normal; text-align:center');
		print_input_row($vbphrase['title_for_uploaded_style'], 'title');
		print_style_chooser_row('parentid', -1, $vbphrase['no_parent_style'], $vbphrase['parent_style'], 1);
		print_input_row($vbphrase['display_order'], 'displayorder', 1);
		print_yes_no_row($vbphrase['allow_user_selection'], 'userselect', 1);
		print_table_default_footer($vbphrase['import']);
	}
}

// #############################################################################
// find & replace
if ($_POST['do'] == 'replace')
{
	if (!$canadmintemplates)
	{
		print_cp_no_permission();
	}
	$vbulletin->input->clean_array_gpc('r', [
		'startat_template' => vB_Cleaner::TYPE_INT,
		'startat_style'    => vB_Cleaner::TYPE_INT,
		'requirerebuild'   => vB_Cleaner::TYPE_BOOL,
		'test'             => vB_Cleaner::TYPE_BOOL,
		'regex'            => vB_Cleaner::TYPE_BOOL,
		'case_insensitive' => vB_Cleaner::TYPE_BOOL,
		'searchstring'     => vB_Cleaner::TYPE_NOTRIM,
		'replacestring'    => vB_Cleaner::TYPE_NOTRIM,
	]);
	$result = vB_Api::instanceInternal('template')->searchAndReplace(
		$vbulletin->GPC['dostyleid'],
		$vbulletin->GPC['searchstring'],
		$vbulletin->GPC['replacestring'],
		$vbulletin->GPC['case_insensitive'],
		$vbulletin->GPC['regex'],
		$vbulletin->GPC['test'],
		$vbulletin->GPC['startat_style'],
		$vbulletin->GPC['startat_template']
	);
	if (empty($result))
	{
		print_stop_message2('completed_search_successfully', 'template', ['do' => 'search']);
	}

	echo "<p><b>" . construct_phrase($vbphrase['search_in_x'], "<i>{$result['styleinfo']['title']}</i>") . "</b></p>\n";

	$testOnly = ($vbulletin->GPC['test'] ? "<p><i>$vbphrase[test_replace_only]</i></p>" : '');
	echo "<p><b>$vbphrase[search_results]</b><br />$vbphrase[page_gcpglobal] {$result['stats']['page']}, $vbphrase[templates] {$result['stats']['first']} - {$result['stats']['last']}</p>" . $testOnly . "\n";
	if ($vbulletin->GPC['regex'])
	{
		echo "<p span=\"smallfont\"><b>" . $vbphrase['regular_expression_used'] . ":</b> " . htmlspecialchars_uni("#" . $vbulletin->GPC['searchstring'] . "#siU") . "</p>\n";
	}
	echo "<ol class=\"smallfont\" start=\"{$result['stats']['first']}\">\n";
	foreach ($result['processed_templates'] as $temp) {
		echo "<li><a href=\"admincp/template.php?" . vB::getCurrentSession()->get('sessionurl') . "do=edit&amp;templateid=$temp[templateid]&amp;dostyleid=$temp[styleid]\">$temp[title]</a>\n";
		vbflush();
		if ($vbulletin->GPC['test'])
		{
			if ($temp['newtemplate'] != htmlspecialchars_uni($temp['template_un']))
			{
				echo "<hr />\n<font size=\"+1\"><b>$temp[title]</b></font> (templateid: $temp[templateid], styleid: $temp[styleid])\n<pre class=\"smallfont\">" . str_replace("\t", " &nbsp; &nbsp; ", $temp['newtemplate']) . "</pre><hr />\n</li>\n";
			}
			else
			{
				echo ' (' . $vbphrase['0_matches_found'] . ")</li>\n";
			}
		}
		else
		{
			if ($temp['newtemplate'] != htmlspecialchars_uni($temp['template_un']))
			{
				echo "<span class=\"col-i\"><b>" . $vbphrase['done'] . "</b></span></li>\n";
			}
			else
			{
				echo ' (' . $vbphrase['0_matches_found'] . ")</li>\n";
			}
		}
	}
	echo "</ol>\n";

	print_form_header('admincp/template', 'replace', false, false);
		construct_hidden_code('regex', $vbulletin->GPC['regex']);
		construct_hidden_code('case_insensitive', $vbulletin->GPC['case_insensitive']);
		construct_hidden_code('requirerebuild', $result['requirerebuild']);
		construct_hidden_code('test', $vbulletin->GPC['test']);
		construct_hidden_code('dostyleid', $vbulletin->GPC['dostyleid']);
		construct_hidden_code('startat_template', $result['startat_template']);
		construct_hidden_code('startat_style', $result['startat_style']);
		construct_hidden_code('searchstring', $vbulletin->GPC['searchstring']);
		construct_hidden_code('replacestring', $vbulletin->GPC['replacestring']);
		echo "<input type=\"submit\" class=\"button\" tabindex=\"1\" value=\"$vbphrase[next_page]\" accesskey=\"s\" />";
	print_table_footer();

	print_cp_footer();
}

// #############################################################################
// form for search / find & replace
if ($_REQUEST['do'] == 'search')
{
	if (!$canadmintemplates)
	{
		print_cp_no_permission();
	}

	$includingMaster = (vB::isDebug() ? ' (' . $vbphrase['including_master_style'] . ')' : '');

	// search only
	print_form_header('admincp/template', 'modify', false, true, 'sform', '90%', '', true, 'get');
	print_table_header($vbphrase['search_templates']);
	print_style_chooser_row("searchset", $vbulletin->GPC['dostyleid'], $vbphrase['search_in_all_styles'] . $includingMaster, $vbphrase['search_in_style'], 1);
	print_textarea_row($vbphrase['search_for_text'], "searchstring");
	print_yes_no_row($vbphrase['search_titles_only'], "titlesonly", 0);
	print_submit_row($vbphrase['find']);

	// search & replace
	print_form_header('admincp/template', 'replace', 0, 1, 'srform');
	print_table_header($vbphrase['find_and_replace_in_templates']);
	print_style_chooser_row("dostyleid", $vbulletin->GPC['dostyleid'], $vbphrase['search_in_all_styles'] .  $includingMaster, $vbphrase['search_in_style'], 1);
	print_textarea_row($vbphrase['search_for_text'], 'searchstring', '', 5, 60, 1, 0);
	print_textarea_row($vbphrase['replace_with_text'], 'replacestring', '', 5, 60, 1, 0);
	print_yes_no_row($vbphrase['test_replace_only'], 'test', 1);
	print_yes_no_row($vbphrase['use_regular_expressions'], 'regex', 0);
	print_yes_no_row($vbphrase['case_insensitive'], 'case_insensitive', 0);
	print_submit_row($vbphrase['find']);

	print_form_header('admincp/', '', 0, 1, 'regexform');
	print_table_header($vbphrase['notes_for_using_regex_in_find_replace']);
	print_description_row($vbphrase['regex_help']);
	print_table_footer(2, $vbphrase['strongly_recommend_testing_regex_replace']);

}

// #############################################################################
// query to insert a new style
// $dostyleid then gets passed to 'updatestyle' for cache and template list rebuild
if ($_POST['do'] == 'insertstyle')
{
	if (!$canadminstyles)
	{
		print_cp_no_permission();
	}
	$vbulletin->input->clean_array_gpc('p', [
		'title'        => vB_Cleaner::TYPE_STR,
		'parentid'     => vB_Cleaner::TYPE_INT,
		'userselect'   => vB_Cleaner::TYPE_INT,
		'displayorder' => vB_Cleaner::TYPE_UINT,
		'group'        => vB_Cleaner::TYPE_STR,
		'guid'         => vB_Cleaner::TYPE_STR,
	]);

	$vbulletin->input->clean_array_gpc('f', [
		'icon_file'          => vB_Cleaner::TYPE_FILE,
		'preview_image_file' => vB_Cleaner::TYPE_FILE,
	]);
	scanVbulletinGPCFile(['icon_file', 'preview_image_file']);

	$iconFile = $previewImageFile = '';
	if (!empty($vbulletin->GPC['icon_file']['tmp_name']) AND is_uploaded_file($vbulletin->GPC['icon_file']['tmp_name']))
	{
		$iconFile = file_get_contents($vbulletin->GPC['icon_file']['tmp_name']);
	}
	if (!empty($vbulletin->GPC['preview_image_file']['tmp_name']) AND is_uploaded_file($vbulletin->GPC['preview_image_file']['tmp_name']))
	{
		$previewImageFile = file_get_contents($vbulletin->GPC['preview_image_file']['tmp_name']);
	}

	if ($vbulletin->GPC['displayorder'] == 0)
	{
		$vbulletin->GPC['displayorder'] = 1;
	}

	$result = vB_Api::instance('style')->insertStyle(
		$vbulletin->GPC['title'],
		$vbulletin->GPC['parentid'],
		$vbulletin->GPC['userselect'],
		$vbulletin->GPC['displayorder'],
		$vbulletin->GPC['guid'],
		$iconFile,
		$previewImageFile
	);
	print_stop_message_on_api_error($result);

	$args = [
		'do' => 'modify',
		'expandset' => $result['styleid'],
		'group' => $vbulletin->GPC['group'],
	];

	print_stop_message2(['saved_style_x_successfully', $vbulletin->GPC['title']], 'template', $args);
}

// #############################################################################
// form to create a new style
if ($_REQUEST['do'] == 'addstyle')
{
	if (!$canadminstyles)
	{
		print_cp_no_permission();
	}

	$vbulletin->input->clean_array_gpc('r', [
		'parentid' => vB_Cleaner::TYPE_INT,
	]);

	$parentid = $vbulletin->GPC['parentid'];

	$stylecache = vB_Library::instance('Style')->fetchStyles(false, false);

	$title = '';
	if ($parentid > 0 AND is_array($stylecache[$parentid]))
	{
		$title = construct_phrase($vbphrase['child_of_x'], $stylecache[$parentid]['title']);
	}

	print_form_header('admincp/template', 'insertstyle', true);
	print_table_header($vbphrase['add_new_style']);
	print_style_chooser_row('parentid', $vbulletin->GPC['parentid'], $vbphrase['no_parent_style'], $vbphrase['parent_style'], 1);
	print_input_row($vbphrase['title'], 'title', $title);
	print_yes_no_row($vbphrase['allow_user_selection'], 'userselect', 1);
	print_input_row($vbphrase['display_order'], 'displayorder');

	// show theme options (guid, icon, preview image) in debug mode
	if ($vb5_config['Misc']['debug'])
	{
		//defaults for the blank style
		$style = [
			'guid' => '',
			'filedataid' => 0,
			'previewfiledataid' => 0,
		];

		template_print_theme_edit_fields($vbphrase, $style);
	}

	// Legacy Hook 'admin_style_form' Removed //

	print_submit_row($vbphrase['save']);

}

function template_print_theme_edit_fields($vbphrase, $style)
{
	print_input_row($vbphrase['theme_guid'], 'guid', $style['guid']);

	$urlPrefix = vB::getDatastore()->getOption('frontendurl') . '/filedata/fetch?filedataid=';

	// icon
	if ($style['filedataid'] > 0)
	{
		print_label_row($vbphrase['theme_icon'], "<div><strong>$vbphrase[current_theme_icon]</strong></div><img src=\"$urlPrefix$style[filedataid]\" alt=\"\" />", '', 'top', 'icon_file');
		print_checkbox_row('', 'icon_remove', false, 1, $vbphrase['theme_remove_icon_desc']);
		print_upload_row('', 'icon_file', 2000000, 35, '');
	}
	else
	{
		print_upload_row($vbphrase['theme_icon'], 'icon_file', 2000000);
	}

	// preview image
	if ($style['previewfiledataid'] > 0)
	{
		print_label_row($vbphrase['theme_preview_image'], "<div><strong>$vbphrase[current_theme_preview_image]</strong></div><img src=\"$urlPrefix$style[previewfiledataid]\" alt=\"\" />", '', 'top', 'preview_image_file');
		print_checkbox_row('', 'preview_image_remove', false, 1, $vbphrase['theme_remove_preview_image_desc']);
		print_upload_row('', 'preview_image_file', 2000000, 35, '');
	}
	else
	{
		print_upload_row($vbphrase['theme_preview_image'], 'preview_image_file', 2000000);
	}
}

// #############################################################################
// query to update a style
// also rebuilds parent lists and template id cache if parentid is altered
if ($_POST['do'] == 'updatestyle')
{
	$vbulletin->input->clean_array_gpc('p', [
		'parentid'             => vB_Cleaner::TYPE_INT,
		'oldparentid'          => vB_Cleaner::TYPE_INT,
		'userselect'           => vB_Cleaner::TYPE_INT,
		'displayorder'         => vB_Cleaner::TYPE_UINT,
		'title'                => vB_Cleaner::TYPE_STR,
		'group'                => vB_Cleaner::TYPE_STR,
		'guid'                 => vB_Cleaner::TYPE_STR,
		'icon_remove'          => vB_Cleaner::TYPE_BOOL,
		'preview_image_remove' => vB_Cleaner::TYPE_BOOL,
	]);

	$vbulletin->input->clean_array_gpc('f', [
		'icon_file'          => vB_Cleaner::TYPE_FILE,
		'preview_image_file' => vB_Cleaner::TYPE_FILE,
	]);
	scanVbulletinGPCFile(['icon_file', 'preview_image_file']);

	$iconFile = '';
	if (!empty($vbulletin->GPC['icon_file']['tmp_name']) AND is_uploaded_file($vbulletin->GPC['icon_file']['tmp_name']))
	{
		$iconFile = file_get_contents($vbulletin->GPC['icon_file']['tmp_name']);
	}

	$previewImageFile = '';
	if (!empty($vbulletin->GPC['preview_image_file']['tmp_name']) AND is_uploaded_file($vbulletin->GPC['preview_image_file']['tmp_name']))
	{
		$previewImageFile = file_get_contents($vbulletin->GPC['preview_image_file']['tmp_name']);
	}

	$result = vB_Api::instance('style')->updateStyle(
		$vbulletin->GPC['dostyleid'],
		$vbulletin->GPC['title'],
		$vbulletin->GPC['parentid'],
		$vbulletin->GPC['userselect'],
		$vbulletin->GPC['displayorder'],
		$vbulletin->GPC['guid'],
		$iconFile,
		$vbulletin->GPC['icon_remove'],
		$previewImageFile,
		$vbulletin->GPC['preview_image_remove']
	);

	print_stop_message_on_api_error($result);

	print_stop_message2(
		['saved_style_x_successfully', $vbulletin->GPC['title']],
		'template',
		['do' => 'modify', 'expandset' => $vbulletin->GPC['dostyleid'], 'group' => $vbulletin->GPC['group']]
	);
}

// #############################################################################
// form to edit a style
if ($_REQUEST['do'] == 'editstyle')
{
	if (!$canadminstyles)
	{
		print_cp_no_permission();
	}

	$vbulletin->input->clean_array_gpc('r', [
		'dostyleid' => vB_Cleaner::TYPE_INT,
	]);

	$styleLib = vB_Library::instance('Style');
	$style = $styleLib->fetchStyleByID($vbulletin->GPC['dostyleid']);

	print_form_header('admincp/template', 'updatestyle', true);
	construct_hidden_code('dostyleid', $vbulletin->GPC['dostyleid']);
	construct_hidden_code('oldparentid', $style['parentid']);
	print_table_header(construct_phrase($vbphrase['x_y_id_z'], $vbphrase['style'], $style['title'], $style['styleid']), 2, 0);

	// Do not allow a child of a read-protected parent to change parents (because read-protected parent will not show up in style chooser.
	if ($styleLib->checkStyleReadProtection($style['parentid']))
	{
		print_style_chooser_row('parentid', $style['parentid'], $vbphrase['no_parent_style'], $vbphrase['parent_style'], 1);
	}
	else
	{
		construct_hidden_code('parentid', $style['parentid']);
	}
	print_input_row($vbphrase['title'], 'title', $style['title']);
	print_yes_no_row($vbphrase['allow_user_selection'], 'userselect', $style['userselect']);
	print_input_row($vbphrase['display_order'], 'displayorder', $style['displayorder']);

	// show theme options (guid, icon, preview image) in debug mode
	if ($vb5_config['Misc']['debug'])
	{
		template_print_theme_edit_fields($vbphrase, $style);
	}

	// Legacy Hook 'admin_style_form' Removed //

	print_submit_row($vbphrase['save']);

}

// #############################################################################
// kill a style, set parents for child forums and update template id caches for dependent styles
if ($_POST['do'] == 'killstyle')
{
	if (!$canadminstyles)
	{
		print_cp_no_permission();
	}

	$vbulletin->input->clean_array_gpc('p', [
		'group' => vB_Cleaner::TYPE_STR,
	]);

	$result = vB_Api::instance('style')->deleteStyle($vbulletin->GPC['dostyleid']);
	print_stop_message_on_api_error($result);

	print_cp_redirect2('template', ['do' => 'modify', 'group' => $vbulletin->GPC['group']], 1, 'admincp');
}

// #############################################################################
// delete style - confirmation for style deletion
if ($_REQUEST['do'] == 'deletestyle')
{
	if (!$canadminstyles)
	{
		print_cp_no_permission();
	}

	$styleApi = vB_Api::instance('style');

	//if this isn't an error then we can proceed.
	$result = $styleApi->canDeleteStyle($vbulletin->GPC['dostyleid']);
	print_stop_message_on_api_error($result);

	print_delete_confirmation('style', $vbulletin->GPC['dostyleid'], 'template', 'killstyle', 'style', [], $vbphrase['please_be_aware_this_will_delete_custom_templates']);
}

// #############################################################################
// do revert all templates in a style
if ($_POST['do'] == 'dorevertall')
{
	if (!$canadmintemplates)
	{
		print_cp_no_permission();
	}

	$vbulletin->input->clean_array_gpc('p', [
		'group' => vB_Cleaner::TYPE_STR,
	]);

	$styleid = $vbulletin->GPC['dostyleid'];

	$result = vB_Api::instance('template')->revertAllInStyle($styleid);
	print_stop_message_on_api_error($result);
	if (!$result)
	{
		print_stop_message2('nothing_to_do');
	}

	$args = [];
	$args['do'] = 'modify';
	$args['expandset'] = $styleid;
	$args['group'] = $vbulletin->GPC['group'];
	print_cp_redirect(get_admincp_url('template', $args), 1);
}

// #############################################################################
// revert all templates in a style
if ($_REQUEST['do'] == 'revertall')
{
	if (!$canadmintemplates)
	{
		print_cp_no_permission();
	}
	$vbulletin->input->clean_array_gpc('r', [
		'group' => vB_Cleaner::TYPE_STR,
	]);

	if ($vbulletin->GPC['dostyleid'] != -1 AND $style = vB_Library::instance('Style')->fetchStyleByID($vbulletin->GPC['dostyleid']))
	{
		if (!$style['parentlist'])
		{
			$style['parentlist'] = '-1';
		}
		$templates = vB::getDbAssertor()->assertQuery('template_getrevertingtemplates', [
			'styleparentlist' => explode(',', $style['parentlist']),
			'styleid'         => $style['styleid'],
		]);

		if ($templates->valid())
		{
			$templatelist = '';
			foreach ($templates as $template)
			{
				$templatelist .= "<li>$template[title]</li>\n";
			}
			echo "<br /><br />";

			print_form_header('admincp/template', 'dorevertall');
			print_table_header($vbphrase['revert_all_templates']);
			print_description_row("
				<blockquote><br />
				" . construct_phrase($vbphrase["revert_all_templates_from_style_x"], $style['title'], $templatelist) . "
				<br /></blockquote>
			");
			construct_hidden_code('dostyleid', $style['styleid']);
			construct_hidden_code('group', $vbulletin->GPC['group']);
			print_submit_row($vbphrase['yes'], 0, 2, $vbphrase['no']);

		}
		else
		{
			print_stop_message2('nothing_to_do');
		}
	}
	else
	{
		print_stop_message2('invalid_style_specified');
	}
}

// #############################################################################
if ($_REQUEST['do'] == 'massmerge')
{
	if (!$canadmintemplates)
	{
		print_cp_no_permission();
	}
	$vbulletin->input->clean_array_gpc('r', [
		'startat'  => vB_Cleaner::TYPE_UINT,
		'product'  => vB_Cleaner::TYPE_STR,
		'redirect' => vB_Cleaner::TYPE_STR,
	]);

	verify_cp_sessionhash();

	$result = vB_Api::instance('template')->massMerge($vbulletin->GPC['product'], $vbulletin->GPC['startat']);
	if ($result == -1)
	{
		$file = '';
		$args = [];
		if ($vbulletin->GPC['redirect'])
		{
			$redirect = vB::getString()->parseUrl($vbulletin->GPC['redirect']);
			$pathinfo = pathinfo($redirect['path']);
			[$file] = explode('.', $pathinfo['basename']);
			parse_str($redirect['query'] ?? '', $args);
		}
		print_stop_message2('templates_merged', $file, $args, '', true);
	}
	else
	{
		// more templates to merge
		$args = [
			'do' => 'massmerge',
			'product' => urlencode($vbulletin->GPC['product']),
			'hash' => CP_SESSIONHASH,
			'redirect' => urlencode($vbulletin->GPC['redirect']),
			'startat' => $result
		];
		print_cp_redirect2('template', $args, 1 , 'admincp');
	}
}

// #############################################################################
// view the history of a template, including old versions and diffs between versions
if ($_REQUEST['do'] == 'history')
{
	if (!$canadmintemplates)
	{
		print_cp_no_permission();
	}
	$vbulletin->input->clean_array_gpc('r', [
		'title' => vB_Cleaner::TYPE_STR,
	]);

	$revisions = vB_Api::instanceInternal('template')->history($vbulletin->GPC['title'], $vbulletin->GPC['dostyleid']);

	$history_count = 0;
	print_form_header('admincp/template', 'historysubmit');
	print_table_header(construct_phrase($vbphrase['history_of_template_x'], htmlspecialchars_uni($vbulletin->GPC['title'])), 7);
	print_cells_row([
		$vbphrase['delete'],
		$vbphrase['type_gstyle'],
		$vbphrase['version_gstyle'],
		$vbphrase['last_modified'],
		$vbphrase['view'],
		$vbphrase['old'],
		$vbphrase['new']
	], true, false, 1);

	$have_left_sel = false;
	$have_right_sel = false;

	foreach ($revisions AS $revision)
	{
		$left_sel = false;
		$right_sel = false;

		if ($revision['type'] == 'current')
		{
			// we are marking this entry (ignore all other entries)
			if ($revision['styleid'] == -1)
			{
				$type = $vbphrase['current_default'];
			}
			else
			{
				$type = $vbphrase['current_version'];
			}

			if ($have_right_sel)
			{
				$left_sel = ' checked="checked"';
				$have_left_sel = true;
			}
			else
			{
				$right_sel = ' checked="checked"';
				$have_right_sel = true;
				if (sizeof($revisions) == 1)
				{
					$left_sel = ' checked="checked"';
					$left_sel_sel = true;
				}
			}

			$id = $revision['templateid'];
			$deletebox = '&nbsp;';
		}
		else
		{
			if ($revision['styleid'] == '-1')
			{
				$type = $vbphrase['old_default'];
			}
			else
			{
				$type = $vbphrase['historical'];
			}

			$id = $revision['templatehistoryid'];
			$deletebox = '<input type="checkbox" name="delete[]" value="' . $id . '" />';
			$history_count ++;
		}

		if (!$revision['version'])
		{
			$revision['version'] = '<i>' . $vbphrase['unknown'] . '</i>';
		}

		$date = vbdate($vbulletin->options['dateformat'], $revision['dateline']);
		$time = vbdate($vbulletin->options['timeformat'], $revision['dateline']);
		$last_modified = "<i>$date $time</i> / <b>$revision[username]</b>";

		$view_link = construct_link_code($vbphrase['view'], "template.php?" . vB::getCurrentSession()->get('sessionurl') . "do=viewversion&amp;id=$id&amp;type=$revision[type]");

		$left = '<input type="radio" name="left_template" tabindex="1" value="' . "$id|$revision[type]" . "\"$left_sel />";
		$right = '<input type="radio" name="right_template" tabindex="1" value="' . "$id|$revision[type]" . "\"$right_sel />";

		if ($revision['comment'])
		{
			$comment = htmlspecialchars_uni($revision['comment']);

			$type = "<div title=\"$comment\">$type*</div>";
			$last_modified = "<div title=\"$comment\">$last_modified</div>";
			$revision['version'] = "<div title=\"$comment\">$revision[version]</div>";
			$view_link = "<div title=\"$comment\">$view_link</div>";
		}

		print_cells_row([
			$deletebox,
			$type,
			$revision['version'],
			$last_modified,
			$view_link,
			$left,
			$right,
		], false, false, 1);
	}

	construct_hidden_code('wrap', 1);
	construct_hidden_code('inline', 1);
	construct_hidden_code('dostyleid', $vbulletin->GPC['dostyleid']);
	construct_hidden_code('title', $vbulletin->GPC['title']);

	print_description_row(
		'<span style="float:' . vB_Template_Runtime::fetchStyleVar('right') . '"><input type="submit" class="button" tabindex="1" name="docompare" value="' . $vbphrase['compare_versions_gstyle'] . '" /></span>' .
		($history_count ? '<input type="submit" class="button" tabindex="1" name="dodelete" value="' . $vbphrase['delete'] . '" />' : '&nbsp;'), false, 7, 'tfoot');
	print_table_footer();

	echo '<div align="center" class="smallfont" style="margin-top:4px;">' . $vbphrase['entry_has_a_comment'] . '</div>';
}

// #############################################################################
// generate a diff between two templates (current or historical versions)
if ($_REQUEST['do'] == 'viewversion')
{
	if (!$canadmintemplates)
	{
		print_cp_no_permission();
	}
	$vbulletin->input->clean_array_gpc('r', [
		'id'   => vB_Cleaner::TYPE_UINT,
		'type' => vB_Cleaner::TYPE_STR,
	]);

	$template = vB_Api::instanceInternal('template')->fetchVersion($vbulletin->GPC['id'], $vbulletin->GPC['type']);
	if ($vbulletin->GPC['type'] == 'current')
	{
		$type = ($template['styleid'] == -1 ? $vbphrase['current_default'] : $vbphrase['current_version']);
	}
	else
	{
		$type = ($template['styleid'] == -1 ? $vbphrase['old_default'] : $vbphrase['historical']);
	}

	$date = vbdate($vbulletin->options['dateformat'], $template['dateline']);
	$time = vbdate($vbulletin->options['timeformat'], $template['dateline']);
	$last_modified = "<i>$date $time</i> / <b>$template[username]</b>";

	print_form_header('admincp/', '');
	print_table_header(construct_phrase($vbphrase['viewing_version_of_x'], htmlspecialchars_uni($template['title'])));
	print_label_row($vbphrase['type_gstyle'], $type);
	print_label_row($vbphrase['last_modified'], $last_modified);
	if ($template['version'])
	{
		print_label_row($vbphrase['version_gstyle'], $template['version']);
	}
	if ($template['comment'])
	{
		print_label_row($vbphrase['comment_gstyle'], $template['comment']);
	}
	print_description_row('<textarea class="code" style="width:95%; height:500px">' . htmlspecialchars_uni($template['templatetext']) . '</textarea>', false, 2, '', 'center');
	print_table_footer();

}

// #############################################################################
// just a small action to figure out which submit button was pressed
if ($_POST['do'] == 'historysubmit')
{
	if (!$canadmintemplates)
	{
		print_cp_no_permission();
	}
	$vbulletin->input->clean_array_gpc('p', ['dodelete' => vB_Cleaner::TYPE_STR]);

	if ($vbulletin->GPC['dodelete'])
	{
		$_POST['do'] = 'dodelete';
	}
	else
	{
		$_POST['do'] = 'docompare';
	}
}

// #############################################################################
// delete history points
if ($_POST['do'] == 'dodelete')
{
	if (!$canadminstyles)
	{
		print_cp_no_permission();
	}
	$vbulletin->input->clean_array_gpc('p', [
		'delete'    => vB_Cleaner::TYPE_ARRAY_INT,
		'dostyleid' => vB_Cleaner::TYPE_INT,
		'title'     => vB_Cleaner::TYPE_STR,
	]);

	if ($vbulletin->GPC['delete'])
	{
		vB_Api::instanceInternal('template')->deleteHistoryVersion($vbulletin->GPC['delete']);
	}

	print_stop_message2('template_history_entries_deleted', 'template', [
		'do' => 'history',
		'dostyleid' => $vbulletin->GPC['dostyleid'],
		'title' => urlencode($vbulletin->GPC['title'])
	]);
}



// #############################################################################
// generate a diff between two templates (current or historical versions)
if ($_POST['do'] == 'docompare')
{
	if (!$canadmintemplates)
	{
		print_cp_no_permission();
	}
	// Consolidating duplicate code used in this do branch into a function
	// not sure this is the right place for this.
	function docompare_print_control_form($inline, $wrap, $context_lines)
	{
		global $vbulletin, $vbphrase;

		static $form_count = 0;
		++$form_count;

		print_form_header('admincp/template', 'docompare', false, true, 'cpform' . $form_count, '90%', '', false, 'post', 0, true);
		print_table_header($vbphrase['display_options'], 1);
		?>
		<tr>
			<td colspan="4" class="tfoot" align="center">
				<input type="submit" name="switch_inline" class="submit" value="<?php echo ($inline ? $vbphrase['view_side_by_side'] : $vbphrase['view_inline']); ?>" accesskey="r" />
				<input type="submit" name="switch_wrapping" class="submit" value="<?php echo ($wrap ? $vbphrase['disable_wrapping'] : $vbphrase['enable_wrapping']); ?>" accesskey="s" />
		<?php
		if ($inline)
		{
		?>
				&nbsp;&nbsp;&nbsp;&nbsp;
				<input type="text" name="context_lines" value="<?php echo $context_lines; ?>" size="2" class="ctrl_context_lines" dir="<?php echo vB_Template_Runtime::fetchStyleVar('textdirection'); ?>" accesskey="t" />
				<strong><?php echo $vbphrase['lines_around_each_diff']; ?></strong>
				&nbsp;&nbsp;&nbsp;&nbsp;
				<input type="submit" name="submit_diff" class="submit" value="<?php echo $vbphrase['update'] ?>" accesskey="u" />
		<?php
		}
		?>
			</td>
		</tr>
		<?php

		construct_hidden_code('left_template', $vbulletin->GPC['left_template']);
		construct_hidden_code('right_template', $vbulletin->GPC['right_template']);
		construct_hidden_code('do_compare_text', $vbulletin->GPC['do_compare_text']);
		construct_hidden_code('left_template_text', $vbulletin->GPC['left_template_text']);
		construct_hidden_code('right_template_text', $vbulletin->GPC['right_template_text']);
		construct_hidden_code('wrap', $wrap);
		construct_hidden_code('inline', $inline);

		print_table_footer(1);
	}


	$vbulletin->input->clean_array_gpc('p', [
		'left_template'       => vB_Cleaner::TYPE_STR,
		'right_template'      => vB_Cleaner::TYPE_STR,
		'switch_wrapping'     => vB_Cleaner::TYPE_NOHTML,
		'switch_inline'       => vB_Cleaner::TYPE_NOHTML,
		'wrap'                => vB_Cleaner::TYPE_BOOL,
		'inline'              => vB_Cleaner::TYPE_BOOL,
		'context_lines'       => vB_Cleaner::TYPE_UINT,
		'do_compare_text'     => vB_Cleaner::TYPE_BOOL,
		'left_template_text'  => vB_Cleaner::TYPE_STR,
		'right_template_text' => vB_Cleaner::TYPE_STR,
		'template_name'       => vB_Cleaner::TYPE_NOHTML,
	]);

	$wrap = ($vbulletin->GPC_exists['switch_wrapping'] ? !$vbulletin->GPC['wrap'] : $vbulletin->GPC['wrap']);
	$inline = ($vbulletin->GPC_exists['switch_inline'] ? !$vbulletin->GPC['inline'] : $vbulletin->GPC['inline']);
	$context_lines = ($vbulletin->GPC_exists['context_lines'] ? $vbulletin->GPC['context_lines'] : 3);

	if ($vbulletin->GPC['do_compare_text'])
	{
		// Compare posted text instead of comparing templates saved in the database
		$left_template = [
			'templatetext' => $vbulletin->GPC['left_template_text'],
			'title'        => $vbulletin->GPC['template_name'],
		];
		$right_template = [
			'templatetext' => $vbulletin->GPC['right_template_text'],
		];
	}
	else
	{
		list($left_id, $left_type) = explode('|', $vbulletin->GPC['left_template']);
		list($right_id, $right_type) = explode('|', $vbulletin->GPC['right_template']);

		$left_template = fetch_template_current_historical($left_id, $left_type);
		$right_template = fetch_template_current_historical($right_id, $right_type);
	}

	if (!$left_template OR !$right_template)
	{
		exit;
	}

	require_once(DIR . '/includes/class_diff.php');

	$diff = new vB_Text_Diff($left_template['templatetext'], $right_template['templatetext']);
	$entries =& $diff->fetch_diff();


	docompare_print_control_form($inline, $wrap, $context_lines);


	print_table_start(true, '90%', '', '', true);
	print_table_header(construct_phrase($vbphrase['comparing_versions_of_x'], htmlspecialchars_uni($left_template['title'])), 4);

	if (!$inline)
	{
		// side by side
		print_cells_row([
			$vbphrase['old_version'],
			$vbphrase['new_version']
		], true, false, 1);

		foreach ($entries AS $diff_entry)
		{
			// possible classes: unchanged, notext, deleted, added, changed
			echo "<tr>\n\t";
			echo '<td width="50%" valign="top" class="diff-' . $diff_entry->fetch_data_old_class() . '" dir="ltr">';

			foreach ($diff_entry->fetch_data_old() AS $content)
			{
				echo $diff_entry->prep_diff_text($content, $wrap) . "<br />\n";
			}

			echo '</td><td width="50%" valign="top" class="diff-' . $diff_entry->fetch_data_new_class() . '" dir="ltr">';

			foreach ($diff_entry->fetch_data_new() AS $content)
			{
				echo $diff_entry->prep_diff_text($content, $wrap) . "<br />\n";
			}

			echo "</td></tr>\n\n";
		}
	}
	else
	{
		// inline
		echo "	<tr valign=\"top\" align=\"center\">
					<td class=\"thead\">$vbphrase[old]</td>
					<td class=\"thead\">$vbphrase[new]</td>
					<td class=\"thead\" width=\"100%\">$vbphrase[content]</td>
				</tr>";

		$wrap_buffer = [];
		$first_diff = true;

		foreach ($entries AS $diff_entry)
		{
			if ('unchanged' == $diff_entry->old_class)
			{
				$old_data = $diff_entry->fetch_data_old();
				$new_data_keys = array_keys($diff_entry->fetch_data_new());

				if (sizeof($entries) <= 1)
				{
					$context_lines = sizeof($old_data);
				}

				if (!$context_lines)
				{
					continue;
				}

				// add unchanged lines to wrap buffer
				foreach ($diff_entry->fetch_data_old() AS $lineno => $content)
				{
					$wrap_buffer[] = [
						'oldline' => $lineno,
						'newline' => array_shift($new_data_keys),
						'content' => $content
					];
				}

				continue;
			}
			else if (sizeof($wrap_buffer))
			{
				if (sizeof($wrap_buffer) > $context_lines)
				{
					if (!$first_diff)
					{
						$buffer = array_slice($wrap_buffer, 0, $context_lines);
						$buffer[] = ['oldline' => '', 'newline' => '', 'content' => '<hr />'];
						$wrap_buffer = array_merge($buffer, array_slice($wrap_buffer, -$context_lines));
					}
					else
					{
						$wrap_buffer = array_slice($wrap_buffer, -$context_lines);
						$first_diff = false;
					}
				}

				foreach ($wrap_buffer AS $wrap_line)
				{
					if (!$wrap_line['oldline'] AND !$wrap_line['newline'])
					{
						echo '<tr><td class="diff-linenumber">...</td><td class="diff-linenumber">...</td>';
						echo '<td colspan="2" class="diff-unchanged diff-inline-break"></td></tr>';
					}
					else
					{
						echo "<tr>\n\t<td class=\"diff-linenumber\">$wrap_line[oldline]</td><td class=\"diff-linenumber\">$wrap_line[newline]</td>";
						echo '<td colspan="2" valign="top" class="diff-unchanged" dir="ltr">';
						echo $diff_entry->prep_diff_text($wrap_line['content'], $wrap);
						echo "</td></tr>\n\n";
					}
				}

				$wrap_buffer = [];
			}

			$data_old = $diff_entry->fetch_data_old();
			$data_new = $diff_entry->fetch_data_new();
			$data_old_len = sizeof($data_old);
			$data_new_len = sizeof($data_new);

			$first = true;
			$current = 1;

			foreach ($data_old AS $lineno => $content)
			{
				$class = 'diff-deleted';

				// only top border the first line
				$class .= ($first ? ' diff-inline-deleted-start' : '');

				// only bottom border the last line if it is not followed by a new diff
				$class .= ($current >= $data_old_len ? ($data_new_len ? '' : ' diff-inline-deleted-end') : '');

				echo "<tr>\n\t<td class=\"diff-linenumber\">$lineno</td><td class=\"diff-linenumber\">&nbsp;</td>";
				echo '<td colspan="" valign="top" class="' . $class . '" dir="ltr">';
				echo $diff_entry->prep_diff_text($content, $wrap);
				echo "</td></tr>\n\n";

				$first = false;
				$current++;
			}

			$first = true;
			$current = 1;

			foreach ($data_new AS $lineno => $content)
			{
				$class = 'diff-inline-added';

				// only top border the first line if it doesn't consecutively follow an old diff comparison
				$class .= ($first ? ($data_old_len ? '' : ' diff-inline-added-start') : '');

				// only bottom border the last line
				$class .= ($current >= $data_new_len ? ' diff-inline-added-end' : '');

				echo "<tr>\n\t<td class=\"diff-linenumber\">&nbsp;</td><td class=\"diff-linenumber\">$lineno</td>";
				echo '<td colspan="" valign="top" class="' . $class . '" dir="ltr">';
				echo $diff_entry->prep_diff_text($content, $wrap);
				echo "</td></tr>\n\n";

				$first = false;
				$current++;
			}
		}

		// If any buffer remains display the first two lines
		if (sizeof($wrap_buffer))
		{
			$i = 0;
			while ($i < $context_lines AND ($wrap_line = array_shift($wrap_buffer)))
			{
				echo "<tr>\n\t<td class=\"diff-linenumber\">$wrap_line[oldline]</td><td class=\"diff-linenumber\">$wrap_line[newline]</td>";
				echo '<td colspan="2" valign="top" class="diff-unchanged" dir="ltr">';
				echo $diff_entry->prep_diff_text($wrap_line['content'], $wrap);
				echo "</td></tr>\n\n";

				$i++;
			}
		}
		unset($wrap_buffer);
	}

	print_table_footer();

	echo '<br />';
	docompare_print_control_form($inline, $wrap, $context_lines);


	print_form_header('admincp/', '');
	print_table_header($vbphrase['comparison_key']);

	if ($inline)
	{
		echo "<tr><td class=\"diff-deleted diff-inline-deleted-end\" align=\"center\">$vbphrase[text_in_old_version]</td></tr>\n";
		echo "<tr><td class=\"diff-added diff-inline-added-end\" align=\"center\">$vbphrase[text_in_new_version]</td></tr>\n";
		echo "<tr><td class=\"diff-unchanged\" align=\"center\">$vbphrase[text_surrounding_changes]</td></tr>\n";
	}
	else
	{
		echo "<tr><td class=\"diff-deleted\" align=\"center\" width=\"50%\">$vbphrase[text_removed_from_old_version]</td><td class=\"diff-notext\">&nbsp;</td></tr>\n";
		echo "<tr><td class=\"diff-changed\" colspan=\"2\" align=\"center\">$vbphrase[text_changed_between_versions]</td></tr>\n";
		echo "<tr><td class=\"diff-notext\" width=\"50%\">&nbsp;</td><td class=\"diff-added\" align=\"center\">$vbphrase[text_added_in_new_version]</td></tr>\n";
	}

	print_table_footer();
}

// #############################################################################
// generate a diff between two templates (current or historical versions)
if ($_REQUEST['do'] == 'docompare3')
{
	if (!$canadmintemplates)
	{
		print_cp_no_permission();
	}

	/*
		Copied from vB_Text_Diff_Entry::prep_diff_text
		I don't want to put html formatting code in the merge class, but I'm not
		sure this really belongs here either.
	*/

	function docompare3_print_control_form($inline, $wrap)
	{
		global $vbphrase, $vbulletin;

		$editlink = '?do=edit&amp;templateid=' . $vbulletin->GPC['templateid'] .
			'&amp;group=&amp;searchstring=&amp;expandset=5&amp;showmerge=1';

		print_form_header('admincp/template', 'docompare3', false, true, 'cpform', '90%', '', false);
		construct_hidden_code('templateid', $vbulletin->GPC['templateid']);
		construct_hidden_code('wrap', $wrap);
		construct_hidden_code('inline', $inline);

		print_table_header($vbphrase['display_options']);
		print_table_footer(2,
			'<div style="float:' . vB_Template_Runtime::fetchStyleVar('right') . '"><a href="' . $editlink . '" style="font-weight: bold">' . $vbphrase['merge_edit_link'] . '</a></div>
			<div align="' . vB_Template_Runtime::fetchStyleVar('left') . '"><input type="submit" name="switch_inline" class="submit" value="' . ($inline ? $vbphrase['view_side_by_side'] : $vbphrase['view_inline']) . '" accesskey="r" />
			<input type="submit" name="switch_wrapping" class="submit" value="' . ($wrap ? $vbphrase['disable_wrapping'] : $vbphrase['enable_wrapping']) . '" accesskey="s" /></div>'
		);
	}

	//get values
	$vbulletin->input->clean_array_gpc('r', [
		'templateid'      => vB_Cleaner::TYPE_STR,
		'switch_wrapping' => vB_Cleaner::TYPE_NOHTML,
		'switch_inline'   => vB_Cleaner::TYPE_NOHTML,
		'inline'          => vB_Cleaner::TYPE_BOOL,
		'wrap'            => vB_Cleaner::TYPE_BOOL,
	]);

	if ($vbulletin->GPC_exists['wrap'])
	{
		$wrap = ($vbulletin->GPC_exists['switch_wrapping'] ? !$vbulletin->GPC['wrap'] : $vbulletin->GPC['wrap']);
	}
	else
	{
		$wrap = true;
	}

	if ($vbulletin->GPC_exists['inline'])
	{
		$inline = ($vbulletin->GPC_exists['switch_inline'] ? !$vbulletin->GPC['inline'] : $vbulletin->GPC['inline']);
	}
	else
	{
		$inline = true;
	}

	$templateid = $vbulletin->GPC['templateid'];

	//find templates
	try
	{
		$templates = fetch_templates_for_merge($templateid);
		$new = $templates["new"];
		$custom = $templates["custom"];
		$origin = $templates["origin"];
	}
	catch (Exception $e)
	{
		print_cp_message($e->getMessage());
	}

	require_once (DIR . '/includes/class_merge.php');
	// Output progress to browser #34585
	$merge = new vB_Text_Merge_Threeway($origin['template_un'], $new['template_un'], $custom['template_un'], true);
	$chunks = $merge->get_chunks();

	docompare3_print_control_form($inline, $wrap);

	print_table_start(true, '90%', 0, ($inline ? 'compare_inline' : 'compare_side'));
	print_table_header(
		construct_phrase($vbphrase['comparing_versions_of_x'], htmlspecialchars_uni($custom['title'])),
		$inline ? 1 : 3
	);

	if ($inline)
	{
		foreach ($chunks as $chunk)
		{
			if ($chunk->is_stable())
			{
				$formatted_text = format_diff_text($chunk->get_text_original(), $wrap);
				$class = "merge-nochange";
			}
			else
			{
				//not sure why the merge class can return null instead of empty string but
				//don't want to dig into that code right now.
				$text = $chunk->get_merged_text() ?? '';
				if ($text === false)
				{
					$formatted_text = format_conflict_text(
						$chunk->get_text_right(), $chunk->get_text_original(), $chunk->get_text_left(),
						$origin['version'], $new['version'], true, $wrap
					);
					$class = "merge-conflict";
				}
				else
				{
					$formatted_text = format_diff_text($text, $wrap);
					$class = "merge-successful";
				}
			}
			echo "<tr>\n\t";
			echo "<td width='100%' valign='top' class='$class' dir='ltr'>\n";
			echo $formatted_text;
			echo "\n</td>\n</tr>\n\n";
		}
	}
	else
	{
		$cells = [
			$vbphrase['your_customized_template'],
			$vbphrase['merged_template_conflicts_show_original'],
			$vbphrase['new_default_template']
		];
		print_cells_row($cells, true, false, 1);

		foreach ($chunks as $chunk)
		{
			if ($chunk->is_stable())
			{
				$col1 = $chunk->get_text_original();
				$col2 = $col1;
				$col3 = $col1;
				$class = "merge-nochange";
			}
			else
			{
				$col1 = $chunk->get_text_right();
				$col2 = $chunk->get_merged_text();
				if ($col2 === false) {
					$class = "merge-conflict";
					$col2 = $chunk->get_text_original();
				}
				else
				{
					$class = "merge-successful";
				}

				$col3 = $chunk->get_text_left();
			}

			// possible classes: unchanged, notext, deleted, added, changed
			echo "<tr>\n\t";
			echo '<td width="33%" valign="top" class="' . $class . '" dir="ltr">';
			echo	format_diff_text($col1, $wrap);
			echo '</td><td width="34%" valign="top" class="' . $class . '" dir="ltr">';
			echo	format_diff_text($col2, $wrap);
			echo '</td><td width="33%" valign="top" class="' . $class . '" dir="ltr">';
			echo	format_diff_text($col3, $wrap);
			echo "</td></tr>\n\n";
		}
	}
	print_table_footer();

	echo '<br />';
	docompare3_print_control_form($inline, $wrap);

	print_form_header('admincp/', '');
	print_table_header($vbphrase['comparison_key']);

	$conflictkey = "";
	echo '<tr><td class="merge-conflict" align="center">' . $vbphrase['merge_key_conflict'] .
		$conflictkey . "</td></tr>\n";
	echo '<tr><td class="merge-successful" align="center">' . $vbphrase['merge_key_merged'] . "</td></tr>\n";
	echo '<tr><td class="merge-nochange" align="center">' . $vbphrase['merge_key_none'] . "</td></tr>\n";

	print_table_footer();
}

function print_template_confirm_error_page($vbphrase, $action, $params, $error) : never
{
	print_form_header2('admincp/template', $action);
	print_table_start2();

	construct_hidden_code('confirmerrors', 1);
	construct_hidden_code('confirmremoval', 1);

	$persist = [
		'title',
		'template',
		'templateid',
		'group',
		'searchstring',
		'dostyleid',
		'product',
		'savehistory',
		'histcomment',
		'return',
		'compiletype',
	];
	foreach ($persist AS $varname)
	{
		construct_hidden_code($varname, $params[$varname]);
	}

	print_table_header($vbphrase['vbulletin_message']);
	if (is_array($error))
	{
		$error = format_template_error_list($error);
	}

	print_description_row($error);

	print_table_button_footer([
		['submit', $vbphrase['continue']],
		['goback', $vbphrase['go_back']],
	]);
	print_cp_footer();
}

function format_template_error_list(array $errors) : string
{
	//we need to format this differently, for now just skip it.
	foreach ($errors AS $key => $value)
	{
		if ($value[0] == 'exception_trace')
		{
			unset($errors[$key]);
		}
	}

	$phrases = vB_Api::instanceInternal('phrase')->renderPhrases($errors);

	//we might have error complaining about the same thing and until we have line numbers or
	//something to report it's not very useful.
	$phrases = array_unique($phrases['phrases']);

	$message = array_shift($phrases);

	//there shoudld be more
	if ($phrases)
	{
		$message .= '<ul><li>' . implode('</li><li>', $phrases) . '</li></ul>';
	}

	return $message;
}

// Handle copyright removal from footer
function handle_vbulletin_copyright_removal($gpc, $page)
{
	global $vbphrase;
	if ($gpc['title'] == 'footer' AND !$gpc['confirmremoval'])
	{
		if (
			(strpos($gpc['template'], '{vb:rawphrase powered_by_vbulletin') === false) AND
			(strpos($gpc['template'], '{vb:phrase powered_by_vbulletin') === false)
		)
		{
			print_form_header('admincp/template', $page, 0, 1, '', '75%');
			construct_hidden_code('confirmremoval', 1);
			construct_hidden_code('title', $gpc['title']);
			construct_hidden_code('template', $gpc['template']);
			if (!empty($gpc['templateid']))
			{
				construct_hidden_code('templateid', $gpc['templateid']);
			}
			construct_hidden_code('group', $gpc['group']);
			construct_hidden_code('searchstring', $gpc['searchstring']);
			construct_hidden_code('dostyleid', $gpc['dostyleid']);
			construct_hidden_code('savehistory', intval($gpc['savehistory']));
			construct_hidden_code('histcomment', $gpc['histcomment']);
			construct_hidden_code('product', $gpc['product']);
			print_table_header($vbphrase['confirm_removal_of_copyright_notice']);
			print_description_row($vbphrase['it_appears_you_are_removing_vbulletin_copyright']);
			print_submit_row($vbphrase['yes'], 0, 2, $vbphrase['no']);
			print_cp_footer();
			exit;
		}
	}
}
// #############################################################################
// insert queries and cache rebuilt for template insertion
if ($_POST['do'] == 'inserttemplate')
{
	$vbulletin->input->clean_array_gpc('p', [
		'title'          => vB_Cleaner::TYPE_STR,
		'product'        => vB_Cleaner::TYPE_STR,
		'template'       => vB_Cleaner::TYPE_NOTRIM,
		'searchstring'   => vB_Cleaner::TYPE_STR,
		'expandset'      => vB_Cleaner::TYPE_NOHTML,
		'searchset'      => vB_Cleaner::TYPE_NOHTML,
		'savehistory'    => vB_Cleaner::TYPE_BOOL,
		'histcomment'    => vB_Cleaner::TYPE_STR,
		'return'         => vB_Cleaner::TYPE_STR,
		'group'          => vB_Cleaner::TYPE_STR,
		'confirmremoval' => vB_Cleaner::TYPE_BOOL,
		'confirmerrors'  => vB_Cleaner::TYPE_BOOL,
		'compiletype'    => vB_Cleaner::TYPE_STR,
	]);

	if (!$vbulletin->GPC['title'])
	{
		print_stop_message2('please_complete_required_fields');
	}

	handle_vbulletin_copyright_removal($vbulletin->GPC, 'inserttemplate');

	$templateid = vB_Api::instance('template')->insert(
		$vbulletin->GPC['dostyleid'],
		$vbulletin->GPC['title'],
		$vbulletin->GPC['template'],
		$vbulletin->GPC['product'],
		$vbulletin->GPC['savehistory'],
		$vbulletin->GPC['histcomment'],
		$vbulletin->GPC['confirmerrors'],
		['compiletype' => $vbulletin->GPC['compiletype']]
	);

	//we can't easily display multiple errors in the admincp code, so display the first one.
	if (isset($templateid['errors']))
	{
		//this needs to be rewritten it's... problematic.
		$error = $templateid['errors'][0];

		if ($error[0] == 'template_eval_error' OR $error[0] == 'template_compile_error')
		{
			print_template_confirm_error_page($vbphrase, 'inserttemplate', $vbulletin->GPC, construct_phrase($vbphrase['template_eval_error'], fetch_error_array($error[1])));
		}
		elseif ($error[0] == 'template_x_exists_error')
		{
			$vbulletin->GPC['templateid'] = $error[2];
			print_template_confirm_error_page($vbphrase, 'updatetemplate', $vbulletin->GPC, construct_phrase($vbphrase['template_x_exists_error'], fetch_error_array($error[1])));
		}
		else
		{
			print_stop_message_array($templateid['errors']);
		}
	}

	//if no error, then redirect the page
	$args = [];
	$args['searchset'] = $vbulletin->GPC['searchset'];
	$args['group'] = $vbulletin->GPC['group'];
	$args['templateid'] = $templateid;
	$args['searchstring'] = $vbulletin->GPC['searchstring'];

	if ($vbulletin->GPC['return'])
	{
		$args['do'] = 'edit';
		$args['expandset'] = $vbulletin->GPC['expandset'];
		$fragment = '';
	}
	else
	{
		$args['do'] = 'modify';
		$args['expandset'] = $vbulletin->GPC['dostyleid'];
		$fragment = 'styleheader' . $vbulletin->GPC['dostyleid'];
	}

	print_cp_redirect(get_admincp_url('template', $args, $fragment), 1);
}


function print_template_editor($vbphrase, $action, $canadmintemplates, $style, $template, $data)
{
	$debug = vB::isDebug();

	print_form_header2('admincp/template', $action);
	print_table_start2();

	construct_hidden_codes_from_params($data);

	//if the template doesn't match the style (or we don't have a template) then we are editing
	//otherwise we are adding (either a new custom template or
	$isEdit = ($template['styleid'] == $style['styleid']);
	if ($isEdit)
	{
		$formtitle = construct_phrase($vbphrase['x_y_id_z'], $vbphrase['template'], $template['title'], $template['templateid']);
	}
	else if ($template['title'])
	{
		$formtitle = construct_phrase($vbphrase['customize_template_x'], $template['title']);
	}
	else
	{
		$formtitle = $vbphrase['add_new_template'];
	}

	print_column_style_code(['width:20%', 'width:80%']);
	print_table_header($formtitle);

	$products = fetch_product_list();

	if ($debug AND $style['styleid'] == -1)
	{
		print_select_row($vbphrase['product'], 'product', $products, $template['product']);
	}
	else
	{
		construct_hidden_code('product', $template['product']);
		print_label_row($vbphrase['product'], $products[$template['product']]);
	}

	if ($isEdit)
	{
		//some of these values are only passed for the edit.
		$args = [
			'do' => 'modify',
			'templateid' => $data['templateid'],
			'group' => $data['group'],
			'expandset' => $data['expandset'],
			'searchset' => $data['expandset'],
			'searchstring' => $data['searchstring'],
		];

		print_label_row($vbphrase['style'], '<a href="' . get_admincp_url('template', $args) . '" title="' . $vbphrase['edit_templates'] . '"><b>' . $style['title'] . '</b></a>');
	}
	else
	{
		print_style_chooser_row('dostyleid', $style['styleid'], $vbphrase['master_style'], $vbphrase['style'], $debug);
	}


	$title = $vbphrase['title'];
	if ($canadmintemplates)
	{
		$history = vB_Api::instanceInternal('template')->history($template['title'], $style['styleid']);
		if ($history)
		{
			$historyurl = get_admincp_url('template', [
				'do' => 'history',
				'dostyleid' => $style['styleid'],
				'title' => $template['title'],
			]);

			$title = get_text_with_description($title, construct_link_code2($vbphrase['view_history_gstyle'], $historyurl));
		}
	}

	print_input_row($title, 'title',	$template['title']);

	if ($template['compiletype'] != 'full' OR  $canadmintemplates)
	{
		$options = [
			'textonly' => $vbphrase['template_textonly'],
			'limited' => $vbphrase['template_limited'],
		];

		if ($canadmintemplates)
		{
			$options['full'] = $vbphrase['template_full'];
		}

		$text = get_text_with_description($vbphrase['compiletype'], $vbphrase['compiletype_desc']);
		print_select_row($text, 'compiletype', $options, $template['compiletype']);
	}
	else
	{
		print_label_row($vbphrase['compiletype'], $vbphrase['template_full']);
	}


	if ($isEdit AND $data['fromeditconflict'])
	{
		print_description_row($vbphrase['template_current_version_merge_here'], false, 2, 'tfoot', 'center');
	}

	$textareatitle = $vbphrase['template'];
	if ($style['styleid'] != -1 AND $template['title'])
	{
		$defaulturl = get_admincp_url('template', ['do' => 'view', 'title' => $template['title']]);
		$textareatitle = get_text_with_description($textareatitle, construct_link_code2($vbphrase['show_default'], $defaulturl, true));
	}

	$textareaid = print_textarea_row($textareatitle, 'template', $template['template_un'], 22, '5000" style="width:99%', true, false, 'ltr', 'js-code-mirror code');
	activateCodeMirror();

	print_label_row($vbphrase['search_in_template'], '
		<input type="text" class="bginput searchstring" name="string" accesskey="t" value="" size="20" />
		<input type="button" class="button findbutton" style="font-weight:normal" value=" ' . $vbphrase['find'] . ' " accesskey="f" />');

	if ($canadmintemplates)
	{
		print_label_row($vbphrase['save_in_template_history'],
			'<label for="savehistory"><input type="checkbox" name="savehistory" id="savehistory" value="1" tabindex="1" />' .
				$vbphrase['yes'] . '</label><br /><span class="smallfont">' . $vbphrase['comment_gstyle'] .
				'</span> <input type="text" name="histcomment" value="" tabindex="1" class="bginput" size="50" />');
	}

	if ($canadmintemplates OR $template['compiletype'] != 'full')
	{
		print_table_button_footer([
			['submit', $vbphrase['save']],
			construct_submit_button($vbphrase['save_and_reload'], ['js-reload-to-position'], ['name' => 'return']),
		]);
	}
	else
	{
		print_table_footer(2, $vbphrase['permission_limited_template_types']);
	}
}


// #############################################################################
// add a new template form
if ($_REQUEST['do'] == 'add')
{
	$vbulletin->input->clean_array_gpc('r', [
		'title'        => vB_Cleaner::TYPE_STR,
		'group'        => vB_Cleaner::TYPE_STR,
		'searchstring' => vB_Cleaner::TYPE_STR,
		'expandset'    => vB_Cleaner::TYPE_STR,
	]);

	if ($vbulletin->GPC['dostyleid'] == -1)
	{
		$style['styleid'] = -1;
		$style['title'] = $vbphrase['global_templates'];
	}
	else
	{
		$style = vB_Library::instance('Style')->fetchStyleByID($vbulletin->GPC['dostyleid']);
	}

	if ($vbulletin->GPC['title'])
	{
		$templateinfo = vB::getDbAssertor()->getRow('template', [
			'title' => $vbulletin->GPC['title'],
			'styleid' => [-1, 0]
		]);
	}
	else if ($vbulletin->GPC['templateid'])
	{
		$templateinfo = vB_Api::instanceInternal('template')->fetchByID($vbulletin->GPC['templateid']);
	}
	else
	{
		$templateinfo = [
			'title' => '',
			'template_un' => '',
			'product' => 'vbulletin',
			'compiletype' => ($canadmintemplates ? 'full' : 'limited'),
			'styleid' => '',
		];
	}

	$data = [
		'group' => $vbulletin->GPC['group'],
		'expandset' => $vbulletin->GPC['expandset'],
		'searchset' => $vbulletin->GPC['expandset'],
		'searchstring' => $vbulletin->GPC['searchstring'],
	];

	print_template_editor($vbphrase, 'inserttemplate', $canadmintemplates, $style, $templateinfo, $data);
}

// #############################################################################
// simple update query for an existing template
$updatetemplate_edit_conflict = false;
if ($_POST['do'] == 'updatetemplate')
{
	$vbulletin->input->clean_array_gpc('p', [
		'title'             => vB_Cleaner::TYPE_STR,
		'template'          => vB_Cleaner::TYPE_NOTRIM,
		'group'             => vB_Cleaner::TYPE_STR,
		'product'           => vB_Cleaner::TYPE_STR,
		'savehistory'       => vB_Cleaner::TYPE_BOOL,
		'histcomment'       => vB_Cleaner::TYPE_STR,
		'string'            => vB_Cleaner::TYPE_STR,
		'searchstring'      => vB_Cleaner::TYPE_STR,
		'expandset'         => vB_Cleaner::TYPE_NOHTML,
		'searchset'         => vB_Cleaner::TYPE_NOHTML,
		'return'            => vB_Cleaner::TYPE_STR,
		'confirmerrors'     => vB_Cleaner::TYPE_BOOL,
		'confirmremoval'    => vB_Cleaner::TYPE_BOOL,
		'lastedit'          => vB_Cleaner::TYPE_UINT,
		'hash'              => vB_Cleaner::TYPE_STR,
		'fromeditconflict'  => vB_Cleaner::TYPE_BOOL,
		'compiletype'       => vB_Cleaner::TYPE_STR,
		'windowScrollTop'   => vB_Cleaner::TYPE_UINT,
		'textareaScrollTop' => vB_Cleaner::TYPE_UINT,
	]);

	handle_vbulletin_copyright_removal($vbulletin->GPC, 'updatetemplate');

	$result = vB_Api::instance('template')->update(
		$vbulletin->GPC['templateid'],
		$vbulletin->GPC['title'],
		$vbulletin->GPC['template'],
		$vbulletin->GPC['product'],
		false,
		$vbulletin->GPC['savehistory'],
		$vbulletin->GPC['histcomment'],
		!empty($vbulletin->GPC['confirmerrors']),
		['compiletype' => $vbulletin->GPC['compiletype']]
	);

	if (isset($result['errors']))
	{
		$error = $result['errors'][0];
		if ($error == 'edit_conflict')
		{
			$updatetemplate_edit_conflict = true;
		}
		else if ($error[0] == 'template_eval_error' OR $error[0] == 'template_compile_error')
		{
			print_template_confirm_error_page($vbphrase, 'updatetemplate', $vbulletin->GPC, $result['errors']);
		}
		else
		{
			print_stop_message_array($result['errors']);
		}
	}

	$args = [
		'templateid'   => $vbulletin->GPC['templateid'],
		'group'        => $vbulletin->GPC['group'],
		'expandset'    => $vbulletin->GPC['expandset'],
		'searchset'    => $vbulletin->GPC['searchset'],
		'searchstring' => $vbulletin->GPC['searchstring'],
	];

	if ($vbulletin->GPC['return'])
	{
		$args['do'] = 'edit';

		$args['windowScrollTop'] = $vbulletin->GPC['windowScrollTop'];
		$args['textareaScrollTop'] = $vbulletin->GPC['textareaScrollTop'];
		$fragment = '';
	}
	else
	{
		$args['do'] = 'modify';
		$args['expandset'] = $vbulletin->GPC['dostyleid'];
		$fragment = 'styleheader' . $vbulletin->GPC['dostyleid'];
	}

	print_cp_redirect(get_admincp_url('template', $args, $fragment), 1);
}

// #############################################################################
// edit form for an existing template
if ($_REQUEST['do'] == 'edit')
{
	function edit_get_merged_text($templateid)
	{
		global $vbphrase;

		$templates = fetch_templates_for_merge($templateid);
		$new = $templates["new"];
		$custom = $templates["custom"];
		$origin = $templates["origin"];

		require_once (DIR . '/includes/class_merge.php');
		$merge = new vB_Text_Merge_Threeway($origin['template_un'], $new['template_un'], $custom['template_un']);
		$chunks = $merge->get_chunks();

		$text = "";
		foreach ($chunks as $chunk)
		{
			if ($chunk->is_stable())
			{
				$text .= $chunk->get_text_original();
			}
			else
			{
				$chunk_text = $chunk->get_merged_text();
				if ($chunk_text === false)
				{
					$new_title = construct_phrase($vbphrase['merge_title_new'], $new['version']);
					$chunk_text = format_conflict_text($chunk->get_text_right(), $chunk->get_text_original(),
						$chunk->get_text_left(), $origin['version'], $new['version']);
				}
				$text .= $chunk_text;
			}
		}

		return $text;
	}

	$vbulletin->input->clean_array_gpc('r', [
		'group'        => vB_Cleaner::TYPE_STR,
		'searchstring' => vB_Cleaner::TYPE_STR,
		'expandset'    => vB_Cleaner::TYPE_STR,
		'showmerge'    => vB_Cleaner::TYPE_BOOL,
	]);

	$template = vB::getDbAssertor()->getRow('fetchTemplateWithStyle', ['templateid' => $vbulletin->GPC['templateid']]);

	if ($template['styleid'] == -1)
	{
		$template['style'] = $vbphrase['master_style'];
	}

	if ($vbulletin->GPC['showmerge'])
	{
		try
		{
			$text = edit_get_merged_text($vbulletin->GPC['templateid']);
		}
		catch (Exception $e)
		{
			print_cp_message($e->getMessage());
		}

		print_table_start();
		print_description_row(
			construct_phrase($vbphrase['edting_merged_version_view_highlighted'], "template.php?do=docompare3&amp;templateid=$template[templateid]")
		);
		print_table_footer();
	}
	else
	{
		if ($template['mergestatus'] == 'conflicted')
		{
			print_table_start();
			print_description_row(
				construct_phrase($vbphrase['default_version_newer_merging_failed'],
					"admincp/template.php?do=docompare3&amp;templateid=$template[templateid]",
					$vbulletin->scriptpath . '&amp;showmerge=1'
				)
			);
			print_table_footer();
		}
		else if ($template['mergestatus'] == 'merged')
		{
			$merge_info = vB::getDbAssertor()->getRow('templatemerge', ['templateid' => $template['templateid']]);

			print_table_start();
			print_description_row(
				construct_phrase($vbphrase['changes_made_default_merged_customized'],
					"admincp/template.php?do=docompare3&amp;templateid=$template[templateid]",
					"admincp/template.php?do=viewversion&amp;id=$merge_info[savedtemplateid]&amp;type=historical"
				)
			);
			print_table_footer();
		}

		$text = $template['template_un'];
	}

	if ($updatetemplate_edit_conflict)
	{
		if ($vbulletin->GPC['fromeditconflict'])
		{
			print_warning_table($vbphrase['template_was_changed_again']);
		}

		// An edit conflict was detected in do=updatetemplate
		print_form_header('admincp/template', 'docompare', false, true, 'editconfcompform', '90%', '_new');
		print_table_header($vbphrase['edit_conflict']);
		print_description_row($vbphrase['template_was_changed']);
		construct_hidden_code('left_template_text', $vbulletin->GPC['template']);
		construct_hidden_code('right_template_text', $text);
		construct_hidden_code('do_compare_text', 1);
		construct_hidden_code('template_name', urlencode($template['title']));
		construct_hidden_code('inline', 1);
		construct_hidden_code('wrap', 0);
		print_submit_row($vbphrase['view_comparison_your_version_current_version'], false);
	}


	$data = [
		'templateid' => $template['templateid'],
		'group' => $vbulletin->GPC['group'],
		'searchstring' => $vbulletin->GPC['searchstring'],
		'dostyleid' => $template['styleid'],
		'expandset' => $vbulletin->GPC['expandset'],
		'lastedit' => $template['dateline'],
		'hash' => $template['hash'],
		'fromeditconflict' => $updatetemplate_edit_conflict ? 1 : 0,
	];

	$style = [
		'styleid' => $template['styleid'],
		'title' => $template['style'],
	];

	print_template_editor($vbphrase, 'updatetemplate', $canadmintemplates, $style, $template, $data);

	if ($updatetemplate_edit_conflict)
	{
		print_form_header('admincp/', '', false, true, 'cpform_oldtemplate');
		print_column_style_code(['width:20%', 'width:80%']);
		print_table_header($vbphrase['your_version_of_template']);
		print_description_row($vbphrase['template_your_version_merge_from_here']);
		print_textarea_row($vbphrase['template'], 'oldtemplate_editconflict', $vbulletin->GPC['template'], 22, '5000" style="width:99%" readonly="readonly', true, false, 'ltr', 'code');
		print_table_footer();
	}
}

// #############################################################################
// kill a template and update template id caches for dependent styles
if ($_POST['do'] == 'kill')
{
	$vbulletin->input->clean_array_gpc('p', [
		'group' => vB_Cleaner::TYPE_STR,
	]);

	$template = vB_Api::instanceInternal('template')->fetchByID($vbulletin->GPC['templateid']);
	if ($template)
	{
		vB_Api::instanceInternal('template')->delete($vbulletin->GPC['templateid']);
	}

	?>
	<script type="text/javascript">
	<!--

	// refresh the opening window (used for the revert updated default templates action)
	if (window.opener && String(window.opener.location).indexOf("admincp/template.php?do=findupdates") != -1)
	{
		window.opener.window.location = window.opener.window.location;
	}

	//-->
	</script>
	<?php

	if (defined('DEV_AUTOEXPORT') AND DEV_AUTOEXPORT)
	{
		require_once(DIR . '/includes/functions_filesystemxml.php');
		// todo: expand to also handle vb5 shim
		if ($template['styleid'] == -1)
		{
			autoexport_delete_template($template['title']);
		}
		else
		{
			autoexport_write_default_style_and_themes($template['styleid']);
		}
	}

	$args = [];
	$args['do'] = 'modify';
	$args['expandset'] = $template['styleid'];
	$args['group'] = $vbulletin->GPC['group'];
	print_cp_redirect2('template', $args, 1, 'admincp');
}

// #############################################################################
// confirmation for template deletion
if ($_REQUEST['do'] == 'delete')
{
	$vbulletin->input->clean_array_gpc('r', [
		'group' => vB_Cleaner::TYPE_STR,
	]);

	$hidden = [];
	$hidden['group'] = $vbulletin->GPC['group'];
	print_delete_confirmation('template', $vbulletin->GPC['templateid'], 'template', 'kill', 'template', $hidden, $vbphrase['please_be_aware_template_is_inherited']);
}

// #############################################################################
// lets the user see the original template
if ($_REQUEST['do'] == 'view')
{
	$vbulletin->input->clean_array_gpc('r', [
		'title' => vB_Cleaner::TYPE_STR,
	]);

	$template = vB::getDbAssertor()->getRow('template', ['title' => $vbulletin->GPC['title'], 'styleid' => [-1,0]]);

	//we can get here with custom templates which don't have records for styles -1 or 0.  Which do one of
	//1) Detect and hide that link.
	//2) Show the original template here (understanding that it may not be the lowest styleid depending on style hierarchy)
	//3) Show an error message here (but really we should do on of the above).
	//For now make sure we don't have warnings when this happens.
	print_form_header('admincp/', '');
	print_table_header($vbphrase['show_default']);
	print_textarea_row($template['title'], '--[-ORIGINAL-TEMPLATE-]--', $template['template_un'] ?? '', 20, 80, true, true, 'ltr', 'code');
	print_table_footer();
}

// #############################################################################
// update display order values
if ($_POST['do'] == 'dodisplayorder')
{
	$vbulletin->input->clean_array_gpc('p', [
		'displayorder' => vB_Cleaner::TYPE_ARRAY_INT,
		'userselect'   => vB_Cleaner::TYPE_ARRAY_INT,
		'expandset'    => vB_Cleaner::TYPE_NOHTML,
		'searchstring' => vB_Cleaner::TYPE_STR,
		'group'        => vB_Cleaner::TYPE_NOHTML,
	]);


	$userselect = $vbulletin->GPC['userselect'];

	//Don't allow deselecting the default style
	//We assume that all styles are being saved here but only "selected" styles actually have keys.
	//we will need to fix this if we ever want to page the style list and handle partial saves.
	//This duplicates a check in the API but avoids a partial save if the check fails here.
	if(!($userselect[$vb5_options['styleid']] ?? 0))
	{
		print_stop_message2('default_style_must_be_selectable');
	}

	$styleAPI = vB_Api::instanceInternal('style');
	$styles = $styleAPI->fetchStyles(false, false);
	foreach ($styles AS $styleid => $style)
	{
		//checkboxes are, unfortunately, only present in the form when they are checked.
		$uperm = $userselect[$styleid] ?? 0;
		$order = $vbulletin->GPC['displayorder'][$styleid];
		if ($style['displayorder'] != $order OR $style['userselect'] != $uperm)
		{
			$styleAPI->updateStyle($style['styleid'], $style['title'], $style['parentid'], $uperm, $order, $style['guid']);
		}
	}

	$args = [
		'do' => 'modify',
		'group' => $vbulletin->GPC['group'],
		'searchstring' => $vbulletin->GPC['searchstring'],
	];

	$fragment = '';

	$expandset = $vbulletin->GPC['expandset'];
	if ($expandset)
	{
		$args['expandset'] = $expandset;
		$fragment = 'styleheader' . $expandset;
	}
	print_cp_redirect(get_admincp_url('template', $args, $fragment), 1);
}

// #############################################################################
// main template list display
if ($_REQUEST['do'] == 'modify')
{
	$vbulletin->input->clean_array_gpc('r', [
		'searchset'    => vB_Cleaner::TYPE_INT,
		'expandset'    => vB_Cleaner::TYPE_NOHTML,
		'searchstring' => vB_Cleaner::TYPE_STR,
		'titlesonly'   => vB_Cleaner::TYPE_BOOL,
		'group'        => vB_Cleaner::TYPE_NOHTML,
	]);

	// sort out parameters for searching
	if ($vbulletin->GPC['searchstring'])
	{
		$vbulletin->GPC['group'] = 'all';
		if ($vbulletin->GPC['searchset'] > 0)
		{
			$vbulletin->GPC['expandset'] = $vbulletin->GPC['searchset'];
		}
		else
		{
			$vbulletin->GPC['expandset'] = 'all';
		}
	}
	else
	{
		$vbulletin->GPC['searchstring'] = '';
	}

	$styleLib = vB_Library::instance('Style');

	$isDebug = vB::isDebug();

	//We currently need all of the titles because we can refer to a hidden style in some of the messaging.
	//We really shouldn't be we need to fix that before doing anything about this.
	// populate the stylecache -- we need all of the styles for the js variables
	$allstylecache = $styleLib->fetchStyles(false, false, [
		'themes' => true,
		'skipReadCheck' => true,
	]);
	if ($isDebug)
	{
		$JS_STYLETITLES[] = "\"0\" : \"" . $vbphrase['master_style'] . "\"";
	}
	foreach ($allstylecache AS $style)
	{
		$JS_STYLETITLES[] = "\"$style[styleid]\" : \"" . addslashes_js($style['title'], '"') . "\"";
	}
	unset($allstylecache);

	//but don't necessarily want them all for the actual display
	//populate the stylecache -- we need all of the styles for the js variables
	$stylecache = $styleLib->fetchStyles(false, false, ['themes' => true]);
	foreach ($stylecache AS $styleid => $style)
	{
		$parents = explode(',', $style['parentlist']);
		foreach ($parents AS $parentid)
		{
			if ($parentid == $styleid)
			{
				continue;
			}

			//when we aren't in debug mode the parents we want to skip won't be in the stylecache array at all
			if ($parentid == -1 OR (isset($stylecache[$parentid]) AND $styleLib->checkStyleReadProtection($parentid, $stylecache[$parentid], true)))
			{
				$stylecache[$styleid]['writableparentid'] = $parentid;
				break;
			}
		}
	}

	$i = 0;
	$JS_MONTHS = [];
	$months = ['january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december'];
	foreach ($months AS $month)
	{
		$JS_MONTHS[] = "\"$i\" : \"" . $vbphrase["$month"] . "\"";
		$i++;
	}

	register_js_phrase([
		'click_the_expand_collapse_button',
		'this_template_has_been_customized_in_a_parent_style',
		'this_template_has_not_been_customized',
		'this_template_has_been_customized_in_this_style',
		'template_last_edited_js',
		'x_templates',
	]);

	// TODO: what are the STYLETITLE & MONTH js vars below??
?>
<script type="text/javascript">
<!--
var STYLETITLE = { <?php echo implode(', ', $JS_STYLETITLES); ?> };
var MONTH = { <?php echo implode(', ', $JS_MONTHS); ?> };
// -->
</script>
<?php

	//needs to be phrased before we unhide the title row but useful for the moment as a placeholder.
	$headers = [
		$vbphrase['title'] . ' (' . $vbphrase['active'] . ')',
		$vbphrase['display'],
		$vbphrase['controls'],
		$vbphrase['templates'],
	];

	$headercount = count($headers);

	print_form_header('admincp/template', 'dodisplayorder', false, true, 'tform');
	print_column_style_code(['width:100%', '', '', '']);
	print_table_header($vbphrase['style_manager'], $headercount);
	print_cells_row2($headers, 'thead');
	construct_hidden_code('expandset',  $vbulletin->GPC['expandset']);
	construct_hidden_code('searchstring',  $vbulletin->GPC['searchstring']);
	construct_hidden_code('group',  $vbulletin->GPC['group']);

	$masterset = [];
	if (!empty($vbulletin->GPC['expandset']))
	{
		$masters = vB::getDbAssertor()->select('template', ['templatetype' => 'template', 'styleid' => [-1, 0]], 'title');
		foreach ($masters AS $master)
		{
			$masterset[$master['title']] = $master['templateid'];
		}
	}

	if ($isDebug)
	{
		$style = [
			'styleid' => -1,
			'title' => $vbphrase['master_style'],
			'templatelist' => $masterset,
			'depth' => 0,
		];

		print_style($masterset, $style, $headercount);
	}

	foreach ($stylecache AS $styleid => $style)
	{
		print_style($masterset, $style, $headercount);
	}

	if ($isDebug)
	{
		//* - This style will be overwritten on upgrade
		print_description_row($vbphrase['admincp_style_readonly_warning'], false, $headercount, 'warning');
	}

	$buttons = [];
	if ($canadminstyles)
	{
		$buttons[] = ['submit', $vbphrase['save']];
	}

	if ($canadmintemplates)
	{
		$buttons[] = construct_link_button($vbphrase['search_in_templates_gstyle'], 'admincp/template.php?do=search');
	}
	print_table_button_footer($buttons, $headercount);

	//not sure what to do about these links.  Can't find another instance of similar UI to standardize to.
	echo '<p align="center" class="smallfont">';
	echo construct_link_code2($vbphrase['add_new_style'], 'admincp/template.php?do=addstyle');
	echo construct_link_code2($vbphrase['rebuild_all_styles'], 'admincp/template.php?do=rebuild&goto=template.php');
	echo "</p>\n";
}

// #############################################################################
// rebuilds all parent lists and id cache lists
if ($_REQUEST['do'] == 'rebuild')
{
	//Don't need a permission check here.  We already did this at the top of the page.

	$vbulletin->input->clean_array_gpc('r', [
		'install'	=> vB_Cleaner::TYPE_INT,
		'goto'		=> vB_Cleaner::TYPE_STR,
	]);

	echo "<p>&nbsp;</p>";
	vB_Library::instance('style')->buildAllStyles(false, $vbulletin->GPC['install']);

	$execurl = vB::getString()->parseUrl($vbulletin->GPC['goto']);
	$pathinfo = pathinfo($execurl['path']);
	parse_str(strval($execurl['query']), $args);
	print_cp_redirect(get_admincp_url($pathinfo['filename'], $args), 2);
}

print_cp_footer();

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 115856 $
|| #######################################################################
\*=========================================================================*/
