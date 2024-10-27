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
define('CVS_REVISION', '$RCSfile$ - $Revision: 111006 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
global $phrasegroups, $specialtemplates, $vbphrase, $vbulletin;
$phrasegroups = ['style'];
$specialtemplates = [];

// ########################## REQUIRE BACK-END ############################
require_once(dirname(__FILE__) . '/global.php');
require_once(DIR . '/includes/adminfunctions_template.php');

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!can_administer('canadminstyles'))
{
	print_cp_no_permission();
}

$vbulletin->input->clean_array_gpc('r', array(
	'templateid' => vB_Cleaner::TYPE_INT,
	'dostyleid'  => vB_Cleaner::TYPE_INT,
));

// ############################# LOG ACTION ###############################
$message = '';
if ($vbulletin->GPC['templateid'] != 0)
{
	$message = 'template id = ' . $vbulletin->GPC['templateid'];
}
else if ($vbulletin->GPC['dostyleid'] != 0)
{
	$message = 'style id = ' . $vbulletin->GPC['dostyleid'];
}
log_admin_action($message);

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

$vb5_config = vB::getConfig();

$extraheaders = '<script type="text/javascript" src="core/clientscript/vbulletin_replacementvars.js?v=' . SIMPLE_VERSION . '"></script>';
print_cp_header($vbphrase['replacement_variable_manager_gstyle'], '', $extraheaders);

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'modify';
}

// *********************** kill *********************
if ($_POST['do'] == 'kill')
{
	$template_api = vB_Api::instance('template');
	$result = $template_api->deleteReplacementVar($vbulletin->GPC['templateid']);
	print_stop_message_on_api_error($result);
	print_cp_redirect(get_redirect_url('replacement', ['do' => 'modify'], 'admincp'), 1);
}

// *********************** remove *********************
if ($_REQUEST['do'] == 'remove')
{
	$vbulletin->input->clean_array_gpc('r', [
		'group' => vB_Cleaner::TYPE_STR
	]);

	$hidden = [];
	$hidden['dostyleid'] =& $vbulletin->GPC['dostyleid'];
	$hidden['group'] = $vbulletin->GPC['group'];
	print_delete_confirmation('template', $vbulletin->GPC['templateid'], 'replacement', 'kill', 'replacement_variable', $hidden, $vbphrase['please_be_aware_replacement_variable_is_inherited']);
}

// *********************** update *********************
if ($_POST['do'] == 'update')
{
	$vbulletin->input->clean_array_gpc('p', [
		'findtext'    => vB_Cleaner::TYPE_STR,
		'replacetext' => vB_Cleaner::TYPE_STR
	]);

	$vbulletin->GPC['findtext'] = strtolower($vbulletin->GPC['findtext']);

	if ($vbulletin->GPC['findtext'] === '')
	{
		print_stop_message2('please_complete_required_fields');
	}

	save_replacementvar($vbulletin->GPC['dostyleid'], $vbulletin->GPC['findtext'], $vbulletin->GPC['replacetext']);
	print_cp_redirect(get_redirect_url('replacement', ['do' => 'modify'], 'admincp'), 1);
}

// *********************** edit *********************
if ($_REQUEST['do'] == 'edit')
{
	$styletitle = get_style_title($vbphrase, $vbulletin->GPC['dostyleid']);

	$replacement = vB_Api::instance('template')->fetchReplacementVarById($vbulletin->GPC['templateid']);
	print_stop_message_on_api_error($replacement);

	$replacement = $replacement['replacevar'];
	$escapedtitle = htmlspecialchars_uni($replacement['title']);

	print_form_header('admincp/replacement', 'update');
	construct_hidden_code('templateid', $vbulletin->GPC['templateid']);
	construct_hidden_code('dostyleid', $vbulletin->GPC['dostyleid']);
	construct_hidden_code('findtext', $replacement['title']);
	if ($replacement['styleid'] == $vbulletin->GPC['dostyleid'])
	{
		print_table_header(construct_phrase($vbphrase['x_y_id_z'], $vbphrase['replacement_variable'], $escapedtitle, $replacement['templateid']));
	}
	else
	{
		print_table_header(construct_phrase($vbphrase['customize_replacement_variable_x'], $escapedtitle));
	}
	print_label_row($vbphrase['style'], $styletitle);
	print_label_row("$vbphrase[search_for_text] <dfn>($vbphrase[case_insensitive])</dfn>", $escapedtitle);
	print_textarea_row($vbphrase['replace_with_text'], 'replacetext', $replacement['template'], 5, 50);
	print_submit_row($vbphrase['save']);
}

// *********************** insert *********************
if ($_POST['do'] == 'insert')
{
	$vbulletin->input->clean_array_gpc('p', [
		'findtext'    => vB_Cleaner::TYPE_STR,
		'replacetext' => vB_Cleaner::TYPE_STR,
		'listreturn' => vB_Cleaner::TYPE_BOOL,
	]);

	$vbulletin->GPC['findtext'] = strtolower($vbulletin->GPC['findtext']);

	if ($vbulletin->GPC['findtext'] === '')
	{
		print_stop_message2('please_complete_required_fields');
	}

	$template_api = vB_Api::instance('template');
	$result = $template_api->fetchReplacementVar($vbulletin->GPC['findtext'], $vbulletin->GPC['dostyleid']);
	print_stop_message_on_api_error($result);

	$existing = $result['replacevar'];
	if ($existing)
	{
		print_stop_message2([
			'replacement_already_exists',
			htmlspecialchars($existing['title']),
			htmlspecialchars($existing['template']),
			htmlspecialchars("admincp/replacement.php?do=edit&dostyleid=$existing[styleid]&templateid=$existing[templateid]"),
		]);
	}
	else
	{
		$result = $template_api->insertReplacementVar($vbulletin->GPC['dostyleid'], $vbulletin->GPC['findtext'], $vbulletin->GPC['replacetext']);
		print_stop_message_on_api_error($result);

		if($vbulletin->GPC['listreturn'])
		{
			$args = ['do' => 'editlist', 'dostyleid' => $vbulletin->GPC['dostyleid']];
		}
		else
		{
			$args = ['do' => 'modify'];
		}

		print_cp_redirect(get_redirect_url('replacement', $args, 'admincp'), 1);
	}
}

// *********************** add *********************
if ($_REQUEST['do'] == 'add')
{
	$vbulletin->input->clean_array_gpc('r', [
		'listreturn' => vB_Cleaner::TYPE_BOOL,
	]);

	print_form_header('admincp/replacement', 'insert');
	construct_hidden_code('listreturn', $vbulletin->GPC['listreturn']);
	print_table_header($vbphrase['add_new_replacement_variable']);
	print_style_chooser_row('dostyleid', $vbulletin->GPC['dostyleid'], $vbphrase['master_style'], $vbphrase['style'], $vb5_config['Misc']['debug']);
	print_input_row("$vbphrase[search_for_text] <dfn>($vbphrase[case_insensitive])</dfn>", 'findtext', '');
	print_textarea_row($vbphrase['replace_with_text'], 'replacetext', '', 5, 50);
	print_submit_row($vbphrase['save']);
}

// *********************** modify *********************
if ($_REQUEST['do'] == 'modify')
{
	print_form_header('admincp/', '');
	print_table_header($vbphrase['color_key']);
	print_description_row('
	<div class="darkbg" style="border: 2px inset;"><ul class="darkbg">
		<li class="col-g">' . $vbphrase['replacement_variable_is_new'] . '</li>
		<li class="col-i">' . $vbphrase['replacement_variable_is_inherited_from_a_parent_style'] . '</li>
		<li class="col-c">' . $vbphrase['replacement_variable_is_customized_in_this_style'] . '</li>
	</ul></div>
	');
	print_table_footer();

	echo "<center>\n";
	echo "<div class=\"tborder\" style=\"width: 100%\">";
	echo "<div class=\"tcat\" style=\"padding:4px\" align=\"center\"><b>$vbphrase[replacement_variables]</b></div>\n";
	echo "<div class=\"alt1\" style=\"padding: 8px\">";
	echo "<div class=\"darkbg\" style=\"padding: 4px; border: 2px inset; text-align: " . vB_Template_Runtime::fetchStyleVar('left') . "\">\n";

	print_replacements();

	echo "</div></div></div>\n</center>\n";
}

// ###################### Start Update Special Templates #######################
if ($_POST['do'] == 'updatelist')
{
	$vbulletin->input->clean_array_gpc('p', [
		'dostyleid'       => vB_Cleaner::TYPE_INT,
		'replacement'     => vB_Cleaner::TYPE_ARRAY,
		'delete'          => vB_Cleaner::TYPE_ARRAY,
	]);

	$styletitle = get_style_title($vbphrase, $vbulletin->GPC['dostyleid']);

	$template_api = vB_Api::instance('template');

	// update replacements
	if ($vbulletin->GPC['replacement'])
	{
		$deleted = $vbulletin->GPC['delete'] ?? [];
		foreach ($vbulletin->GPC['replacement'] AS $key => $replacebits)
		{
			if(!isset($deleted[$key]))
			{
				save_replacementvar($vbulletin->GPC['dostyleid'], $replacebits['find'], $replacebits['replace']);
			}
			else
			{
				$template_api->deleteReplacementVar($deleted[$key]);
			}
		}
	}

	print_rebuild_style(
		$vbulletin->GPC['dostyleid'],
		$styletitle,
		false,
		false,
		true,
		false
	);

	$args = [];
	$args['do'] = 'editlist';
	$args['dostyleid'] = $vbulletin->GPC['dostyleid'];
	print_cp_redirect(get_redirect_url('replacement', $args, 'admincp'), 1);
}

// ###################### Start Choose What to Edit #######################
if ($_REQUEST['do'] == 'editlist')
{
	$vbulletin->input->clean_array_gpc('r', [
		'dostyleid' => vB_Cleaner::TYPE_INT,
	]);

	$styleid = $vbulletin->GPC['dostyleid'];

	if ($styleid == 0 OR $styleid < -1)
	{
		print_stop_message2('invalid_style_specified');
	}

	$styleLib = vB_Library::instance('Style');
	$styles = $styleLib->fetchStyles(false, false);

	//not 100% sure what this is about.  I *think* it's to allow the -1 (master style) case but only in debug mode
	//(combined with checking that the style exists).  I don't think we actually want to allow an unknown style
	//aside from the master even in debug mode.
	if (!isset($stylecache[$styleid]) AND !$vb5_config['Misc']['debug'])
	{
		print_stop_message2('invalid_style_specified');
	}

	print_form_header('admincp/', '');
	print_table_header($vbphrase['color_key']);
	print_description_row('
		<div class="darkbg" style="border: 2px inset;"><ul class="darkbg">
			<li class="col-g">' . $vbphrase['template_is_unchanged_from_the_default_style'] . '</li>
			<li class="col-i">' . $vbphrase['replacement_variable_is_inherited_from_a_parent_style'] . '</li>
			<li class="col-c">' . $vbphrase['replacement_variable_is_customized_in_this_style'] . '</li>
		</ul></div>
	');
	print_table_footer();

	if($styleid == -1)
	{
		$templates = $assertor->assertQuery('template', ['templatetype' => 'replacement', 'styleid' => -1]);
	}
	else
	{
		$style = $styleLib->getStyleById($styleid);
		$templates = $assertor->assertQuery('getReplacementTemplates', ['templateids' => $style['templatelist']]);
	}

	// #############################################################################
	// start main form
	print_form_header('admincp/replacement', 'updatelist', 0, 1, 'replacelistform');

	print_table_header($vbphrase['style']);
	print_style_chooser_row('dostyleid', $styleid, $vbphrase['master_style'], $vbphrase['style'], $vb5_config['Misc']['debug']);
	print_table_break(' ');

	print_table_header($vbphrase['replacement_variables'], 3);
	if ($templates->valid())
	{
		print_cells_row([$vbphrase['search_for_text'], $vbphrase['replace_with_text'], ''], 1);
		$count = 0;
		foreach($templates AS $template)
		{
			print_replacement_row($vbphrase, $template['templateid'], $styleid, $template['styleid'], $template['title'], $template['template']);
		}
	}
	else
	{
		print_description_row($vbphrase['no_replacements_defined']);
	}

	$link = construct_link_code2($vbphrase['add_new_replacement_variable'], 'admincp/replacement.php?do=add&listreturn=1&dostyleid=' . $styleid);
	print_table_break('<center>' . $link . '</center>');

	print_submit_row($vbphrase['save']);
}

print_cp_footer();

// Local helper functions
//If we are only trying to load the style to get the styleid and we need to deal with the master style.
function get_style_title($vbphrase, $styleid)
{
	if($styleid == -1)
	{
		$title = $vbphrase['master_style'];
	}
	else
	{
		$title = vB_Library::instance('Style')->getStyleById($styleid)['title'];
	}
	return $title;
}

function save_replacementvar($styleid, $findtext, $replacetext)
{
	/*
	 	Some notes:
		1) We may want to make this an API function, but we *need* to consolidate the save logic first and
			clean up the admincp specific code.

		2) We will quietly skip any *nothing to do* updates.  This avoids creating a custom replacement from a
			parent when the text hasn't changed.
	*/
	$template_api = vB_Api::instance('template');
	$existing = $template_api->fetchReplacementVar($findtext, $styleid, true);
	print_stop_message_on_api_error($existing);

	$existing = $existing['replacevar'];
	if(!$existing)
	{
		//this case isn't currently used.  It will only trigger when the replacement is completely new
		//(not even a parent value that's inherited).  But we only call this from "edit" contexts where
		//we are showing some kind of existing value for the user to react to.  However we might
		//want to handle this case in the future and it doesn't really hurt.
		$result = $template_api->insertReplacementVar($styleid, $findtext, $replacetext);
		print_stop_message_on_api_error($result);
	}
	else
	{
		//if the text didn't change -- there is nothing we should do.  Especially not create an identical "customized" template.
		if ($existing['template'] != $replacetext)
		{
			//changing inherited variable, insert a value for this style
			if ($existing['styleid'] != $styleid)
			{
				$result = $template_api->insertReplacementVar($styleid, $findtext, $replacetext);
				print_stop_message_on_api_error($result);
			}

			//changing a variable for this style, update it.
			else
			{
				$result = $template_api->updateReplacementVar($existing['templateid'], $replacetext);
				print_stop_message_on_api_error($result);
			}
		}
	}
}

function print_replacement_row($vbphrase, $replacementid, $styleid, $replacmentstyleid, $find, $replace)
{
	$attributes = [
		'rows' => 2,
		'cols' => 50,
		'class' => fetch_inherited_color($replacmentstyleid, $styleid),
	];

	$attributes = construct_control_attributes('replacement[' . $replacementid . '][replace]', $attributes);
	$textarea = '<textarea ' . $attributes . '>' . htmlspecialchars_uni($replace) . '</textarea>';

	$deletecontrol = '&nbsp';
	$inheritinfo = '';

	if($replacmentstyleid == $styleid)
	{
		$inheritinfo = "($vbphrase[customized_in_this_style])";
		$deletecontrol = construct_checkbox_control($vbphrase['delete'], "delete[$replacementid]", false, $replacementid);
	}
	else if ($replacmentstyleid != -1)
	{
		//if the replacement var was added in the master don't show the customized message.  It's not clear
		//what the use case is for adding replacement vars in the master style (you can only do this in
		//debug mode) but if we do then we don't allow deleting them here so just be quiet about it.
		$inheritinfo = '(' . construct_phrase($vbphrase['customized_in_style_x'], get_style_title($vbphrase, $replacmentstyleid)) . ')';
	}

	construct_hidden_code("replacement[$replacementid][find]", $find);
	print_cells_row([
		'<pre>' . htmlspecialchars_uni($find) . '</pre>',
		"\n\t" . '<span class="smallfont">' . $textarea. '<br />' . $inheritinfo . '</span>' . "\n\t",
		'<span class="smallfont">' . $deletecontrol . '</span>'
	]);
}

function print_replacements($parentid = -1, $indent = "\t", $namecache = [])
{
	global $vbphrase;
	static $stylecache = [];

	$vb5_config =& vB::getConfig();
	$assertor = vB::getDbAssertor();
	$styleLib = vB_Library::instance('Style');

	if ($parentid == -1 AND $vb5_config['Misc']['debug'])
	{

		echo "$indent<ul class=\"lsq\">\n";

		echo "$indent<li><b>" . $vbphrase['master_style'] . "</b>" . construct_link_code2($vbphrase['add_new_replacement_variable'], 'admincp/replacement.php?do=add&dostyleid=-1') . "\n";
		echo "$indent\t<ul class=\"ldi\">\n";

		$templates = $assertor->assertQuery('template', ['templatetype' => 'replacement', 'styleid' => -1]);
		print_replacements_for_style($vbphrase, "$indent\t\t", -1, $templates, $namecache);
		echo "$indent\t</ul><br />\n";
		echo "$indent</li>\n";
		echo "$indent</ul>\n<hr size=\"1\" />\n";
	}

	if (empty($stylecache))
	{
		$styles = $styleLib->fetchStyles(false, false, ['skipReadCheck' => true]);
		foreach ($styles AS $style)
		{
			$stylecache[$style['parentid']][$style['displayorder']][$style['styleid']] = $style;
		}
	}

	// Check style actually exists / has children
	if (!isset($stylecache[$parentid]))
	{
		return;
	}

	foreach ($stylecache[$parentid] AS $holder)
	{
		echo "$indent<ul class=\"lsq\">\n";
		foreach ($holder AS $styleid => $style)
		{
			$style = $styleLib->getStyleById($styleid);

			// Themes are read-only, but they all have children that are writable.
			// Since each "level" has a read-only parent holding writable children,
			// If we just skip the read-only ones outright, no themes will show up.
			// If it's read-only, go through & print its descendants.
			if (!$styleLib->checkStyleReadProtection($styleid, $style))
			{
				print_replacements($styleid, "$indent\t");
				continue;
			}

			$link = construct_link_code2($vbphrase['add_new_replacement_variable'], 'admincp/replacement.php?do=add&dostyleid=' . $styleid);
			echo "$indent<li><b>$style[title]</b>$link\n";
			echo "$indent\t<ul class=\"ldi\">\n";

			//we can almost certainly reduce duplication here by keeping a list of templates we've already fetched and only loading the ones
			//we haven't seen before.  But replacement variables are not generally a huge number so it probably isn't worth doing.
			$templates = $assertor->assertQuery('getReplacementTemplates', ['templateids' => $style['templatelist']]);
			print_replacements_for_style($vbphrase, "$indent\t\t", $styleid, $templates, $namecache);

			echo "$indent\t</ul><br />\n";
			print_replacements($styleid, "$indent\t", $namecache);
			echo "$indent</li>\n";
		}

		echo "$indent</ul>\n";
		if ($style['parentid'] == -1)
		{
			echo "<hr size=\"1\" />\n";
		}
	}
}

function print_replacements_for_style(array $vbphrase, string $indent, int $styleid, Iterator $replacements, &$namecache)
{
	static $donecache = [];

	if($replacements->valid())
	{
		$args = ['dostyleid' => $styleid];
		foreach ($replacements AS $template)
		{
			$args['templateid'] = $template['templateid'];
			if (isset($donecache[$template['templateid']]))
			{
				$args['do'] = 'edit';
				$links = construct_link_code2($vbphrase['customize_gstyle'], "admincp/replacement.php?" . http_build_query($args));
				$class = 'col-i';
			}
			else
			{
				$args['do'] = 'edit';
				$links = construct_link_code2($vbphrase['edit'],  "admincp/replacement.php?" . http_build_query($args));
				$args['do'] = 'remove';
				$links .= construct_link_code2($vbphrase['delete'],  "admincp/replacement.php?" . http_build_query($args));

				$class = (isset($namecache[$template['title']]) ? 'col-c' : 'col-g');

				$donecache[$template['templateid']] = true;
				$namecache[$template['title']] = true;
			}

			echo "$indent<li class=\"$class\">" . htmlspecialchars_uni($template['title']) . $links . "</li>\n";

		}
	}
	else
	{
		echo "$indent<li>" . $vbphrase['no_replacements_defined'] . "</li>\n";
	}
}


/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 111006 $
|| #######################################################################
\*=========================================================================*/
