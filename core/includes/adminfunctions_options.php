<?php if (!defined('VB_ENTRY')) die('Access denied.');
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

/**
* Prints a setting group for use in options.php?do=options
*
* @param	string	Settings group ID
* @param	boolean	Show advanced settings?
*/
function print_setting_group($dogroup, $advanced = 0)
{
	global $settingscache, $grouptitlecache, $bgcounter, $settingphrase;

	if (!is_array($settingscache["$dogroup"]))
	{
		return;
	}
	$userContext = vB::getUserContext();

	if (!empty($settingscache[$dogroup]['groupperm']) AND !$userContext->hasAdminPermission($settingscache[$dogroup]['groupperm']))
	{
		return;
	}

	$vbphrase = vB_Api::instanceInternal('phrase')->fetch(['group_requires_admin_perm', 'edit', 'delete', 'add_setting', 'rebuild', 'rebuild_all_attachments']);
	print_column_style_code(['width:45%', 'width:55%']);

	echo "<thead>\r\n";

	$vb5_config = vB::getConfig();
	$title = $settingphrase["settinggroup_$grouptitlecache[$dogroup]"];
	if ($vb5_config['Misc']['debug'] AND $userContext->hasAdminPermission('canadminsettingsall'))
	{
		$title .=
			'<span class="normal">' .
				construct_link_code($vbphrase['edit'], "options.php?do=editgroup&amp;grouptitle=$dogroup") .
				construct_link_code($vbphrase['delete'], "options.php?do=removegroup&amp;grouptitle=$dogroup") .
				construct_link_code($vbphrase['add_setting'], "options.php?do=addsetting&amp;grouptitle=$dogroup") .
			'</span>';
	}

	print_table_header($title);
	echo "</thead>\r\n";

	$bgcounter = 1;

	foreach ($settingscache[$dogroup] AS $settingid => $setting)
	{
		if (!empty($setting['adminperm']) AND !$userContext->hasAdminPermission($setting['adminperm']))
		{
			continue;
		}

		if (($advanced OR !$setting['advanced']) AND !empty($setting['varname']))
		{
			print_setting_row($setting, $settingphrase, true, $userContext);
		}
	}

}

/**
* Prints a setting row for use in options.php?do=options
*
* @param	array	Settings array
* @param	array	Phrases
*/
function print_setting_row($setting, $settingphrase, $option_config = true, $userContext = false)
{
	global $vbulletin, $bgcounter, $vbphrase;
	$settingid = $setting['varname'];

	$datastore = vB::getDatastore();

	if (empty($userContext))
	{
		$userContext = vB::getUserContext();
	}

	echo '<tbody>';

	$varname = $setting['varname'];
	$controls = '';
	$optiontitleattr = '';
	if (vB::isDebug())
	{
		$optiontitleattr = ' title="Option name: ' . $varname . '"';
		if ($option_config AND $userContext->hasAdminPermission('canadminsettingsall'))
		{
			$controls = '<div class="smallfont h-right">' .
				construct_link_code($vbphrase['edit'], "options.php?do=editsetting&amp;varname=$setting[varname]") .
				construct_link_code($vbphrase['delete'], "options.php?do=removesetting&amp;varname=$setting[varname]") .
			'</div>';
		}
	}

	print_description_row($controls . '<div id=' . $varname . ' . ' . $optiontitleattr . ' >' . $settingphrase["setting_{$varname}_title"] . "</div>", 0, 2, 'optiontitle');
	echo "</tbody><tbody id=\"tbody_$settingid\">\r\n";

	// make sure all rows use the alt1 class
	$bgcounter--;

	$description = "<div class=\"smallfont\"" . $optiontitleattr . ">" . $settingphrase["setting_{$varname}_desc"] . '</div>';
	$name = "setting[$setting[varname]]";
	$right = "<span class=\"smallfont\">$vbphrase[error]</span>";
	$width = 40;
	$rows = 8;

	if (preg_match('#^input:?(\d+)$#s', $setting['optioncode'], $matches))
	{
		$width = $matches[1];
		$setting['optioncode'] = '';
	}
	else if (preg_match('#^textarea:?(\d+)(?:,(\d+))?$#s', $setting['optioncode'], $matches))
	{
		$rows = $matches[1];
		$width = $matches[2] ?? $width;
		$setting['optioncode'] = 'textarea';
	}
	else if (preg_match('#^bitfield:(.*)$#siU', $setting['optioncode'], $matches))
	{
		$setting['optioncode'] = 'bitfield';
		$setting['bitfield'] =& fetch_bitfield_definitions($matches[1]);
	}
	else if (preg_match('#^(select|selectmulti|radio|checkboxlist):(piped|eval)(\r\n|\n|\r)(.*)$#siU', $setting['optioncode'], $matches))
	{
		$setting['optioncode'] = "$matches[1]:$matches[2]";
		$setting['optiondata'] = trim($matches[4]);
	}
	else if (preg_match('#^usergroup:?(\d+)$#s', $setting['optioncode'], $matches))
	{
		$size = intval($matches[1]);
		$setting['optioncode'] = 'usergroup';
	}
	else if (preg_match('#^(usergroupextra)(\r\n|\n|\r)(.*)$#siU', $setting['optioncode'], $matches))
	{
		$setting['optioncode'] = 'usergroupextra';
		$setting['optiondata'] = trim($matches[3]);
	}
	else if (preg_match('#^profilefield:?([a-z0-9,;=]*)(?:\r\n|\n|\r)(.*)$#siU', $setting['optioncode'], $matches))
	{
		$setting['optioncode'] = 'profilefield';
		$setting['optiondata'] = [
			'constraints'  => trim($matches[1]),
			'extraoptions' => trim($matches[2]),
		];
	}

	// Make setting's value the default value if it's null
	if ($setting['value'] === null)
	{
		$setting['value'] = $setting['defaultvalue'];
	}

	//hack to store this value seperate from the normal option datastore items.  This was previously
	//handled by custom html for the option however this:
	//1) Requires cloning and modifying the textarea logic and keeping it up to date
	//2) Doesn't keep the special logic confined to the settings xml since we have special
	//	handling on save for this option already.
	//3) Hides some fragile code in a location that's not very visible -- we had some obscure
	//	custom code for this option elsewhere to ensure that the datastore value was loaded
	//	in the legacy registry object that, frankly, looked like it was an error.
	//
	//I considered being more aggressive and treating the value as a standard option.  However the
	//data is only needed very rarely and has the potential to be quite large (a long running site
	//could generate tens of thousands of banned email addressess) and the options get loaded
	//with every page.
	//
	//If this becomes more general we might want some kind of "special storage" option setting
	//and generalize this without specific logic. But right now it's just the one setting.
	if ($settingid == 'banemail')
	{
		$setting['value'] = $datastore->getValue('banemail');
	}

	switch ($setting['optioncode'])
	{
		// input type="text"
		case '':
		{
			print_input_row($description, $name, $setting['value'], 1, $width);
		}
		break;

		// input type="radio"
		case 'yesno':
		{
			print_yes_no_row($description, $name, $setting['value']);
		}
		break;

		// textarea
		case 'textarea':
		{
			print_textarea_row($description, $name, $setting['value'], $rows, "$width\" style=\"width:90%");
		}
		break;

		// bitfield
		case 'bitfield':
		{
			$setting['value'] = intval($setting['value']);
			$setting['html'] = '';

			if ($setting['bitfield'] === NULL)
			{
				print_label_row($description, construct_phrase("<strong>$vbphrase[settings_bitfield_error]</strong>", implode(',', vB_Bitfield_Builder::fetch_errors())), '', 'top', $name, 40);
			}
			else
			{
				#$setting['html'] .= "<fieldset><legend>$vbphrase[yes] / $vbphrase[no]</legend>";
				$setting['html'] .= "<div id=\"ctrl_setting[$setting[varname]]\" class=\"smallfont\">\r\n";
				$setting['html'] .= "<input type=\"hidden\" name=\"setting[$setting[varname]][0]\" value=\"0\" />\r\n";
				foreach ($setting['bitfield'] AS $key => $value)
				{
					$value = intval($value);
					$setting['html'] .= "<table style=\"width:175px; float:" . vB_Template_Runtime::fetchStyleVar('left') . "\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\"><tr valign=\"top\">
					<td><input type=\"checkbox\" name=\"setting[$setting[varname]][$value]\" id=\"setting[$setting[varname]]_$key\" value=\"$value\"" . (($setting['value'] & $value) ? ' checked="checked"' : '') . " /></td>
					<td width=\"100%\" style=\"padding-top:4px\"><label for=\"setting[$setting[varname]]_$key\" class=\"smallfont\">" . fetch_phrase_from_key($key) . "</label></td>\r\n</tr></table>\r\n";
				}

				$setting['html'] .= "</div>\r\n";
				#$setting['html'] .= "</fieldset>";
				print_label_row($description, $setting['html'], '', 'top', $name, 40);
			}
		}
		break;

		// checkboxes allow for multiple selections, so naturally they're some kind of delimited strings.
		// For *saving* we'll always store them as a json_encoded array. However there isn't currently a
		// settings data type for a json_encoded array, so for now you'll have to add special handling for
		// each new setting using this type in save_settings() function. See handling for 'enabled_scanner'
		// for an example, but consider adding a new json-array data type for generic handling if we ever
		// get more than 1 setting using this scheme.
		case 'checkbox:json_array':
			$setting['value'] = json_decode($setting['value'], true);
			$setting['value'] = (is_array($setting['value']) ? $setting['value'] : []);
			// ATM we don't have any pre-defined options for this. May want to allow the :piped type?
			$options = [];
			$options = additional_options($settingid, $setting['optioncode'], $options);

			print_option_checkboxes_row($description, $name, $options, $setting['value']);
		break;

		case 'checkboxlist:piped':

			$setting['value'] = json_decode($setting['value'], true);
			$setting['value'] = (is_array($setting['value']) ? $setting['value'] : []);

			$options = [];
			$options = fetch_piped_options($setting['optiondata']);
			$options = additional_options($settingid, $setting['optioncode'], $options);

			print_option_checkboxes_list_row($description, $name, $options, $setting['value']);
		break;

		case 'checkboxlist:eval':

			$setting['value'] = json_decode($setting['value'], true);
			$setting['value'] = (is_array($setting['value']) ? $setting['value'] : []);

			$options = null;
			eval($setting['optiondata']);
			$options = additional_options($settingid, $setting['optioncode'], $options);

			print_option_checkboxes_list_row($description, $name, $options, $setting['value']);
		break;

		// select:piped
		case 'select:piped':
		{
			$options = fetch_piped_options($setting['optiondata']);
			$options = additional_options($settingid, $setting['optioncode'], $options);
			print_option_select_row($description, $name, $options, $setting['value']);
		}
		break;

		//the multi options are unused and don't appear to work properly
		case 'selectmulti:piped':
		{
			$options = fetch_piped_options($setting['optiondata']);
			$options = additional_options($settingid, $setting['optioncode'], $options);
			print_option_select_row($description, $name, $options, $setting['value'], 5);
		}
		break;

		// select:eval
		case 'select:eval':
		{
			$options = null;
			eval($setting['optiondata']);
			$options = additional_options($settingid, $setting['optioncode'], $options);
			print_option_select_row($description, $name, $options, $setting['value']);
		}
		break;

		//the multi options are unused and don't appear to work properly
		// selectmulti:eval
		case 'selectmulti:eval':
		{
			$options = null;
			eval($setting['optiondata']);
			$options = additional_options($settingid, $setting['optioncode'], $options);
			print_option_select_row($description, $name, $options, $setting['value'], 5);
		}
		break;

		// radio:piped
		case 'radio:piped':
		{
			print_radio_row($description, $name, fetch_piped_options($setting['optiondata']), $setting['value'], 'smallfont');
		}
		break;

		// radio:eval
		case 'radio:eval':
		{
			$options = null;

			eval($setting['optiondata']);

			if (is_array($options) AND !empty($options))
			{
				print_radio_row($description, $name, $options, $setting['value'], 'smallfont');
			}
			else
			{
				print_input_row($description, $name, $setting['value']);
			}
		}
		break;

		case 'username':
		{
			$userinfo = vB::getDbAssertor()->assertQuery('user', ['userid' => $setting['value']]);
			if (intval($setting['value']) AND $userinfo AND $userinfo->valid())
			{
				$userInfo = $userinfo->current();
				print_input_row($description, $name, $userInfo['username'], false);
			}
			else
			{
				print_input_row($description, $name);
			}
			break;
		}

		//appears to be unused.
		case 'usergroup':
		{
			$usergrouplist = [];
			$usergroupcache = $datastore->getValue('usergroupcache');
			foreach ($usergroupcache AS $usergroup)
			{
				$usergrouplist["$usergroup[usergroupid]"] = $usergroup['title'];
			}

			if ($size > 1)
			{
				print_select_row($description, $name . '[]', [0 => ''] + $usergrouplist, unserialize($setting['value']), false, $size, true);
			}
			else
			{
				print_select_row($description, $name, $usergrouplist, $setting['value']);
			}
			break;
		}

		case 'usergroupextra':
		{
			$usergrouplist = fetch_piped_options($setting['optiondata']);
			$usergroupcache = $datastore->getValue('usergroupcache');
			foreach ($usergroupcache AS $usergroup)
			{
				$usergrouplist["$usergroup[usergroupid]"] = $usergroup['title'];
			}

			print_select_row($description, $name, $usergrouplist, $setting['value']);
			break;
		}

		//appears to be unused.
		case 'profilefield':
		{
			static $profilefieldlistcache = [];
			$profilefieldlisthash = md5(serialize($setting['optiondata']));

			if (!isset($profilefieldlistcache[$profilefieldlisthash]))
			{
				$profilefieldlist = fetch_piped_options($setting['optiondata']['extraoptions']);

				$constraints = preg_split('#;#', $setting['optiondata']['constraints'], -1, PREG_SPLIT_NO_EMPTY);
				$conditions = [];
				foreach ($constraints AS $constraint)
				{
					$constraint = explode('=', $constraint);
					switch ($constraint[0])
					{
						case 'editablegt':
							$conditions[] = ['field' => 'editable', 'value' => intval($constraint[1]), 'operator' => vB_dB_Query::OPERATOR_GT];
							break;
						case 'types':
							$constraint[1] = preg_split('#,#', $constraint[1], -1, PREG_SPLIT_NO_EMPTY);
							if (!empty($constraint[1]))
							{
								$conditions[] = ['field' => 'type', 'value' => $constraint[1], 'operator' => vB_dB_Query::OPERATOR_EQ];
							}
							break;
					}
				}

				$profilefields = vB::getDbAssertor()->assertQuery('vBForum:profilefield',
					[vB_dB_Query::CONDITIONS_KEY => $conditions],
					['field' => 'displayorder', 'direction' => vB_dB_Query::SORT_ASC]
				);

				foreach ($profilefields AS $profilefield)
				{
					$fieldname = "field$profilefield[profilefieldid]";
					$profilefieldlist[$fieldname] = construct_phrase($vbphrase['profilefield_x_fieldid_y'], fetch_phrase_from_key("{$fieldname}_title"), $fieldname);
				}

				$profilefieldlistcache[$profilefieldlisthash] = $profilefieldlist;
				unset($profilefieldlist, $constraints, $constraint, $profilefields, $profilefield, $fieldname);
			}

			print_select_row($description, $name, $profilefieldlistcache[$profilefieldlisthash], $setting['value']);
			break;
		}

		//note this isn't currently used and doesn't entirely work.
		// arbitrary number of <input type="text" />
		case 'multiinput':
		{
			$setting['html'] = "<div id=\"ctrl_$setting[varname]\"><fieldset id=\"multi_input_fieldset_$setting[varname]\" style=\"padding:4px\">";

			$setting['values'] = unserialize($setting['value']);
			$setting['values'] = (is_array($setting['values']) ? $setting['values'] : []);
			$setting['values'][] = '';

			foreach ($setting['values'] AS $key => $value)
			{
				$setting['html'] .= "<div id=\"multi_input_container_$setting[varname]_$key\">" . ($key + 1) .
					" <input type=\"text\" class=\"bginput\" name=\"setting[$setting[varname]][$key]\" id=\"multi_input_$setting[varname]_$key\" size=\"40\" value=\"" .
					htmlspecialchars_uni($value) . "\" tabindex=\"1\" /></div>";
			}

			$i = sizeof($setting['values']);
			if ($i == 0)
			{
				$setting['html'] .= "<div><input type=\"text\" class=\"bginput\" name=\"setting[$setting[varname]][$i]\" size=\"40\" tabindex=\"1\" /></div>";
			}

			$userinfo = vB_User::fetchUserinfo(0, ['admin']);
			$setting['html'] .= "
				</fieldset>
				<div class=\"smallfont\"><a href=\"#\" onclick=\"return multi_input['$setting[varname]'].add()\">Add Another Option</a></div>
				<script type=\"text/javascript\">
				<!--
				multi_input['$setting[varname]'] = new vB_Multi_Input('$setting[varname]', $i, '" . $userinfo['cssprefs'] . "');
				//-->
				</script>
			";

			print_label_row($description, $setting['html']);
			break;
		}

		// cp folder options
		case 'cpstylefolder':
		{
			if ($folders = fetch_cpcss_options() AND !empty($folders))
			{
				print_select_row($description, $name, $folders, $setting['value'], 1, 6);
			}
			else
			{
				print_input_row($description, $name, $setting['value'], 1, 40);
			}
		}
		break;

		// cookiepath / cookiedomain options
		case 'cookiepath':
		case 'cookiedomain':
		{
			$func = 'fetch_valid_' . $setting['optioncode'] . 's';

			$cookiesettings = $func(($setting['optioncode'] == 'cookiepath' ? $vbulletin->script : $_SERVER['HTTP_HOST']), $vbphrase['blank']);

			$setting['found'] = in_array($setting['value'], array_keys($cookiesettings));

			$setting['html'] = "
			<div id=\"ctrl_$setting[varname]\">
			<fieldset>
				<legend>$vbphrase[suggested_settings]</legend>
				<div style=\"padding:4px\">
					<select name=\"setting[$setting[varname]]\" tabindex=\"1\" class=\"bginput\">" .
						construct_select_options($cookiesettings, $setting['value']) . "
					</select>
				</div>
			</fieldset>
			<br />
			<fieldset>
				<legend>$vbphrase[custom_setting]</legend>
				<div style=\"padding:4px\">
					<label for=\"{$settingid}o\"><input type=\"checkbox\" id=\"{$settingid}o\" name=\"setting[{$settingid}_other]\" tabindex=\"1\" value=\"1\"" . ($setting['found'] ? '' : ' checked="checked"') . " />$vbphrase[use_custom_setting]
					</label><br />
					<input type=\"text\" class=\"bginput\" size=\"25\" name=\"setting[{$settingid}_value]\" value=\"" . ($setting['found'] ? '' : $setting['value']) . "\" />
				</div>
			</fieldset>
			</div>";

			print_label_row($description, $setting['html'], '', 'top', $name, 50);
		}
		break;

		//this appears to be unused.
		case 'forums:all':
		{
			$array = construct_forum_chooser_options(-1,$vbphrase['all']);
			$size = sizeof($array);

			$vbphrase['forum_is_closed_for_posting'] = $vbphrase['closed'];
			print_select_row($description, $name.'[]', $array, unserialize($setting['value']), false, ($size > 10 ? 10 : $size), true);
		}

		break;

		//this appears to be unused.
		case 'forums:none':
		{
			$array = construct_forum_chooser_options(0,$vbphrase['none']);
			$size = sizeof($array);

			$vbphrase['forum_is_closed_for_posting'] = $vbphrase['closed'];
			print_select_row($description, $name.'[]', $array, unserialize($setting['value']), false, ($size > 10 ? 10 : $size), true);
		}
		break;

		// File upload
		case 'fileupload:image':
			$decoded = json_decode($setting['value'], true);
			// Right now, this is only for the manifest_icon setting which is actually stored as a complex value with
			// 2 filedataid's (due to needing 2 sized icons)... the one we want to "show" here is the larger one, filedataid_512,
			// but as we add more file-upload-type options, that may change. So let's track the key as "uploadkey", and default
			// to 'filedataid' for simpler types.
			$imageuploadkey = $decoded['uploadkey'] ?? 'filedataid';
			$filedataid = $decoded[$imageuploadkey] ?? 0;
			print_image_upload_row($description, $name, null, $filedataid);
			break;

		// just a label
		default:
		{
			$handled = false;
			// Legacy Hook 'admin_options_print' Removed //
			if (!$handled)
			{
				eval("\$right = \"<div id=\\\"ctrl_setting[$setting[varname]]\\\">$setting[optioncode]</div>\";");
				print_label_row($description, $right, '', 'top', $name, 50);
			}
		}
		break;
	}

	echo "</tbody>\r\n";
	$valid = exec_setting_validation_code($setting['varname'], $setting['value'], $setting['validationcode'], $setting['value']);

	echo "<tbody id=\"tbody_error_$settingid\" style=\"display:" . (($valid === 1 OR $valid === true) ? 'none' : '') . "\"><tr><td class=\"alt1 smallfont\" colspan=\"2\"><div style=\"padding:4px; border:solid 1px red; background-color:white; color:black\"><strong>$vbphrase[error]</strong>:<div id=\"span_error_$settingid\">$valid</div></div></td></tr></tbody>";
}

function additional_options($settingid, $optioncode, $options)
{
	vB::getHooks()->invoke('hookAdminSettingsSelectOptions', [
		'settingid' => $settingid,
		'optioncode' => $optioncode,
		'options' => &$options,
	]);

	return $options;
}


function print_option_checkboxes_row($description, $name, $options, $value, $width = 40)
{
	$html = "<div id=\"ctrl_{$name}\" class=\"smallfont\">\r\n";
	// Without this hidden input, "uncheck all" state doesn't get saved due to no post data with this key
	// getting passed to save_setting(). Assumes that there will never be a "vbulletin__ignore" product.
	$html .= "<input type=\"hidden\" name=\"{$name}[vbulletin__ignore]\" value=\"0\" />\r\n";
	foreach ($options AS $key => $label)
	{
		$checked = (!empty($value[$key]) ? ' checked="checked"' : '');
		$inputHtml = "<input type=\"checkbox\" name=\"{$name}[{$key}]\" id=\"{$name}_{$key}\" value=\"1\"{$checked} />";
		// todo: fetch_phrase_from_key() instead of $label?
		$html .= 	"<table style=\"width:175px; float:" . vB_Template_Runtime::fetchStyleVar('left') . "\"
						cellpadding=\"0\" cellspacing=\"0\" border=\"0\">
						<tr valign=\"top\">
							<td>$inputHtml</td>
							<td width=\"100%\" style=\"padding-top:4px\">
								<label for=\"{$name}_{$key}\" class=\"smallfont\">
									$label
								</label>
							</td>\r\n
						</tr>
					</table>\r\n";
	}
	$html .= "</div>\r\n";
	print_label_row($description, $html, '', 'top', $name, $width);
}

function print_option_checkboxes_list_row($description, $name, $options, $current, $width = 40)
{
	$controls = [];
	$cbname = $name . '[]';
	foreach ($options AS $value => $label)
	{
		$controls[] = construct_checkbox_control($label, $cbname, in_array($value, $current), $value);
	}
	$html = '<input type="hidden" name="' . $name . '[vbulletin__ignore]" value="0" />' . "\n" . implode('<br />', $controls);
	print_label_row($description, $html, '', 'top', $name, $width);
}

function print_option_select_row($description, $name, $options, $value, $size=0)
{
	if (is_array($options) AND !empty($options))
	{
		//if this is a multi-select box then we need to convert the post return
		//to an array value.
		if ($size > 0)
		{
			$name .= '[]';
		}

		//size controls how many options show.  0 doesn't set the size param
		//multi (the last param) allows allow multiple values to be selected
		//In theory we could show the select with multiple values visible (instead of drop down)
		//but we don't want to do that, so let's just use the size param to controls this.
		print_select_row($description, $name, $options, $value, true, $size, (bool) $size);
	}
	else
	{
		print_input_row($description, $name, $value);
	}
}

// Not used atm, but may be used before calling convertUploadFileToFiledataid
function checkUploadError($uploadname, $files) : bool
{
	// scanFiles() must be called by the caller.
	// Based heavily on the forum.php custom channel icon upload handling logic.

	// If there was an error with the upload (which may cause tmp_name to be
	// empty), pass that along and let the API functions map the error code to
	// an error phrase. However ignore the error if it's a NO_FILE error (that
	// just means no icon was uploaded)
	$hasUpload = (
		!empty($files['tmp_name'][$uploadname])
		OR
		!empty($files['error'][$uploadname]) AND
		$files['error'][$uploadname] !== UPLOAD_ERR_NO_FILE
	);

	return $hasUpload;
}

function convertFileSuperArrayToSingleArray($uploadname, $files) : array
{
	// Extract out the individual file array from the data split across the
	// $files super array, e.g. take something like
	// [
	// 	'name' => [
	// 		'a' => 'cat.jpg',
	// 		'b' => 'logo.svg',
	// 	],
	// 	'type' => [
	// 		'a' => 'image/jpeg',
	// 		'b' => 'image/svg+xml',
	// 	],
	// 	'tmp_name' => [
	// 		'a' => 'something.tmp',
	// 		'b' => 'somethingelse.tmp',
	// 	],
	// 	...
	// ]
	// and pull out the $uploadname = 'a' values out, like
	// [
	// 	'name' => 'cat.jpg',
	// 	'type' => 'image/jpeg',
	// 	'tmp_name' => 'something.tmp',
	// 	...
	// ]

	$keys = [
		'name',
		'type',
		'tmp_name',
		'error',
		'size',
	];
	$fileArray = [];
	foreach ($keys AS $__key)
	{
		$fileArray[$__key] = $files[$__key][$uploadname] ?? '';
	}

	return $fileArray;
}

function convertUploadErrorToPhrase($error) : mixed
{
	// Copied from vB_Api_Content_Attach::checkUploadErrors(), because our
	// current use-case of pre-resizing the image prevents us from going through
	// the API

	// Encountered PHP upload error
	$maxupload = @ini_get('upload_max_filesize');
	if (!$maxupload)
	{
		$maxupload = 10485760;
	}
	$maxattachsize = vb_number_format($maxupload, 1, true);

	switch($error)
	{
		case '1': // UPLOAD_ERR_INI_SIZE
		case '2': // UPLOAD_ERR_FORM_SIZE
			return ['upload_file_exceeds_php_limit', $maxattachsize];
		case '3': // UPLOAD_ERR_PARTIAL
			return ['upload_file_partially_uploaded'];
		case '4':
			return ['upload_file_failed'];
		case '6':
			return ['missing_temporary_folder'];
		case '7':
			return ['upload_writefile_failed'];
		case '8':
			return ['upload_stopped_by_extension'];
		case '0':
			return [];
		default:
			return ['upload_file_failed_php_error_x', intval($error)];
	}
}

// Pasa an uploaded file through the Content_Attach class to store it
// in filedata system & get the filedataid out. Assumes all pre-checks are
// done, like checkUploadError(), and that any array-uploads are being
// handled one at a time via convertFileSuperArrayToSingleArray()
function convertUploadedFileToFiledataid($fileArray) : int
{
	// scanFiles() must be called by the caller.
	// Based heavily on the forum.php custom channel icon upload handling logic.

	// We may want to add an uploadfrom, with matching handling code in
	// vB_Library_Content_Attach::uploadAttachment() For now, set nothing,
	// and it'll be treated as an attachment as far as upload perm checks
	// go.
	// $fileArray['uploadfrom'] = 'settings';

	if (empty($fileArray['tmp_name']))
	{
		return -1;
	}


	// We cannot use the API with manifest_icons, because we pre-resize the icons
	// to the required dimensions, which makes the is_uploaded_file() check fail.
	// /** @var vB_Api_Content_Attach */
	// $attachAPI = vB_Api::instance('content_attach');
	// $result = $attachAPI->upload($fileArray);
	/** @var vB_Library_Content_Attach */
	$attachLIB = vB_Library::instance('content_attach');
	$userid = vB::getCurrentSession()->get('userid');
	$result = $attachLIB->uploadAttachment($userid, $fileArray);
	print_stop_message_on_api_error($result);

	return $result['filedataid'] ?? -1;
}

/**
* Updates the setting table based on data passed in then rebuilds the datastore.
* Only entries in the array are updated (allows partial updates).
*
* @param array $settings -- Array of settings. Format: [setting_name] = new_value
* @return bool
*/
function save_settings($settings)
{
	global $vbulletin;

	// Setting file uploads currently look like
	// $_FILES['setting']['name'][$uploadname] ...
	// $_FILES['setting']['tmp_name'][$uploadname] etc
	// Just cleaning the GPC arr like this will cause the $vbulletin->GPC['setting'] array to be
	// overridden with the _FILES array :
	//   $vbulletin->input->clean_array_gpc('f', [
	//   	'setting' => vB_Cleaner::TYPE_ARRAY_FILE
	//   ]);
	//   scanVbulletinGPCFile('setting');
	// That could be problematic because 1) if the top script still needs to refer to it after
	// calling save_settings(), the expected values wouldn't be there and 2) There could be weird
	// name conflict issues (though very unlikely) between the $_POST form inputs and the $_FILES
	// inputs... So let's just pull it aside separately here.
	$cleaner = vB::getCleaner();
	$files = $cleaner->clean($_FILES['setting'], vB_Cleaner::TYPE_ARRAY_FILE);
	scanFiles($files['tmp_name'] ?? []);

	//a few variables to track changes for processing after all variables are updated.
	$rebuildstyle = false;
	$templatecachepathchanged = false;
	$oldtemplatepath = null;
	$newtemplatepath = null;

	$datastore = vB::getDatastore();

	$userContext = vB::getUserContext();
	$cleaner = vB::getCleaner();
	$canAdminAll = $userContext->hasAdminPermission('canadminsettingsall');

	$assertor = vB::getDbAssertor();
	$oldsettings = $assertor->assertQuery('vBAdmincp:getCurrentSettings',	['varname' => array_keys($settings)]);

	// first getProducts() returns the products OBJ, its getProducts() returns an array of
	// (string) productid => (bool) enabled.
	$enabledProducts = vB::getProducts()->getProducts();

	foreach ($oldsettings AS $oldsetting)
	{
		//check the setting and group permissions
		if (
			(!empty($oldsetting['adminperm']) AND !$userContext->hasAdminPermission($oldsetting['adminperm'])) OR
			(!empty($oldsetting['groupperm']) AND !$userContext->hasAdminPermission($oldsetting['groupperm']))
		)
		{
			throw new vB_Exception_Api('no_permission');
		}

		switch ($oldsetting['varname'])
		{
			// **************************************************
			case 'bbcode_html_colors':
			{
				$settings['bbcode_html_colors'] = serialize($settings['bbcode_html_colors']);
			}
			break;

			// **************************************************
			case 'styleid':
			{
				//should use the api/library to save but the functions aren't set up to ignore data we
				//don't have and we don't have any data to speak up.
				$assertor->update('vBForum:style', ['userselect' => 1], ['styleid' => $settings['styleid']]);
				vB_Library::instance('style')->buildStyleDatastore();
			}
			break;

			// **************************************************
			case 'banemail':
			{
				$datastore->build('banemail', $settings['banemail']);
				$settings['banemail'] = '';
			}
			break;

			// **************************************************
			case 'editormodes':
			{
				$vbulletin->input->clean_array_gpc('p', [
					'fe' => vB_Cleaner::TYPE_UINT,
					'qr' => vB_Cleaner::TYPE_UINT,
					'qe' => vB_Cleaner::TYPE_UINT,
				]);

				$settings['editormodes'] = serialize([
					'fe' => $vbulletin->GPC['fe'],
					'qr' => $vbulletin->GPC['qr'],
					'qe' => $vbulletin->GPC['qe']
				]);
			}
			break;

			// **************************************************
			case 'attachresizes':
			{
				$vbulletin->input->clean_array_gpc('p', [
					'attachresizes' => vB_Cleaner::TYPE_ARRAY_UINT,
				]);

				$value = @unserialize($oldsetting['value']);
				$invalidate = [];
				if ($value[vB_Api_Filedata::SIZE_ICON] != $vbulletin->GPC['attachresizes'][vB_Api_Filedata::SIZE_ICON])
				{
					$invalidate[] = vB_Api_Filedata::SIZE_ICON;
				}
				if ($value[vB_Api_Filedata::SIZE_THUMB] != $vbulletin->GPC['attachresizes'][vB_Api_Filedata::SIZE_THUMB])
				{
					$invalidate[] = vB_Api_Filedata::SIZE_THUMB;
				}
				if ($value[vB_Api_Filedata::SIZE_SMALL] != $vbulletin->GPC['attachresizes'][vB_Api_Filedata::SIZE_SMALL])
				{
					$invalidate[] = vB_Api_Filedata::SIZE_SMALL;
				}
				if ($value[vB_Api_Filedata::SIZE_MEDIUM] != $vbulletin->GPC['attachresizes'][vB_Api_Filedata::SIZE_MEDIUM])
				{
					$invalidate[] = vB_Api_Filedata::SIZE_MEDIUM;
				}
				if ($value[vB_Api_Filedata::SIZE_LARGE] != $vbulletin->GPC['attachresizes'][vB_Api_Filedata::SIZE_LARGE])
				{
					$invalidate[] = vB_Api_Filedata::SIZE_LARGE;
				}
				if (!empty($invalidate))
				{
					$assertor->update('vBForum:filedataresize', ['reload' => 1], ['resize_type' => $invalidate]);
				}

				$settings['attachresizes'] = serialize([
					vB_Api_Filedata::SIZE_ICON   => $vbulletin->GPC['attachresizes'][vB_Api_Filedata::SIZE_ICON],
					vB_Api_Filedata::SIZE_THUMB  => $vbulletin->GPC['attachresizes'][vB_Api_Filedata::SIZE_THUMB],
					vB_Api_Filedata::SIZE_SMALL  => $vbulletin->GPC['attachresizes'][vB_Api_Filedata::SIZE_SMALL],
					vB_Api_Filedata::SIZE_MEDIUM => $vbulletin->GPC['attachresizes'][vB_Api_Filedata::SIZE_MEDIUM],
					vB_Api_Filedata::SIZE_LARGE  => $vbulletin->GPC['attachresizes'][vB_Api_Filedata::SIZE_LARGE]
				]);
			}
			break;
			case 'thumbquality':
				if ($oldsetting['value'] != $settings['thumbquality'])
				{
					$assertor->update('vBForum:filedataresize', ['reload' => 1], vB_dB_Query::CONDITION_ALL);
				}
			break;

			// **************************************************
			case 'cookiepath':
			case 'cookiedomain':
			{
				if (!empty($settings[$oldsetting['varname'] . '_other']) AND $settings[$oldsetting['varname'] . '_value'])
				{
					$settings[$oldsetting['varname']] = $settings[$oldsetting['varname'] . '_value'];
				}
			}
			break;

			case 'imagetype':
				// If they're not coming from AdminCP such that imagick_pdf_thumbnail is
				// not set, don't touch it.
				if (isset($settings['imagick_pdf_thumbnail']))
				{
					// Check pdf thumbnail-ability when option is changed to ImageMagick
					$changedToImagick = ($settings['imagetype'] == 'Imagick' AND $oldsetting['value'] != 'Imagick');
					// If the option is already enabled, maybe they manually enabled it,
					// so leave it alone.
					$pdfSetToNo = !$settings['imagick_pdf_thumbnail'];
					if ($changedToImagick AND $pdfSetToNo)
					{
						/*
							Pull the correct image class instance
						 */
						$vboptions = $datastore->getValue('options');
						$image = new vB_Image_Imagick($vboptions);
						$pdfOkay = $image->canThumbnailPdf();
						/*
							We currently don't really have a good way to check if the user
							manually set this or not. One annoying bit could be if they
							wanted to manually set this to "No" while switching over to
							imagemagick, and this re-enables it.
							We may end up reverting this depending on which we decide
							is more useful.

							As one step towards mitigating it, if the old PDF setting was set to
							'yes', we will not try to override the *new* setting to 'yes', just
							in case the admin manually set this to no.
							If the old setting was set to 'no', there's no distinction between
							the admin not changing it and the admin manually setting it to 'no',
							so we'll just go with the auto-update to 'yes'.
							So the current remaining problem case is when the admin explicitly set
							this option to 'no', then later changes the image processing library
							and wants to leave the option to 'no' but doesn't realize that this
							automatic check/switch exists.
						 */
						$oldSettingPdfSupport = $assertor->getRow('setting', ['varname' => 'imagick_pdf_thumbnail']);
						$oldSettingPdfSupport = $oldSettingPdfSupport['value'] ?? 0;

						if ($pdfOkay AND !$oldSettingPdfSupport)
						{
							$settings['imagick_pdf_thumbnail'] = 1;
						}
					}
					else if ($settings['imagetype'] == 'GD')
					{
						/*
							Disable this option so that we can always check this when going
							*to* ImageMagick.
							If they switch back and forth between the 2 classes a lot and had
							to manually enable this option, then having this keep turning off
							would be annoying, but I don't think any admin will switch that
							frequently unless they're testing something.
						 */
						$settings['imagick_pdf_thumbnail'] = 0;
					}
				}
				break;

			// type "free", but json-encoded array of productid => bool enabled
			case 'enabled_scanner':
				if (!is_array($settings[$oldsetting['varname']]))
				{
					$settings[$oldsetting['varname']] = [];
				}
				foreach ($settings[$oldsetting['varname']] AS $__tag => $__enabled)
				{
					$__productid = explode(':', $__tag)[0];
					if (empty($enabledProducts[$__productid]))
					{
						unset($settings[$oldsetting['varname']][$__tag]);
					}
				}

				$settings[$oldsetting['varname']] = json_encode($settings[$oldsetting['varname']]);
				break;

			case 'manifest_icon':
				save_settings__manifest_icon($oldsetting, $files, $settings);
				break;

			// **************************************************
			default:
			{
				$type = strstr($oldsetting['optioncode'], ':', true);
				if ($type == 'checkboxlist')
				{
					$store = $settings[$oldsetting['varname']];
					if (!is_array($store))
					{
						$store = [];
					}

					//hidden field to ensure that we actually have a value passed back.
					unset($store['vbulletin__ignore']);

					$settings[$oldsetting['varname']] = json_encode($store);
				}

				//none of these codes appears to be used any longer.
				if ($oldsetting['optioncode'] == 'multiinput')
				{
					$store = [];
					foreach ($settings["$oldsetting[varname]"] AS $value)
					{
						if ($value != '')
						{
							$store[] = $value;
						}
					}
					$settings["$oldsetting[varname]"] = serialize($store);
				}
				else if (preg_match('#^(usergroup|forum)s?:([0-9]+|all|none)$#', $oldsetting['optioncode']))
				{
					// serialize the array of usergroup inputs
					if (!is_array($settings["$oldsetting[varname]"]))
					{
						 $settings["$oldsetting[varname]"] = [];
					}
					$settings["$oldsetting[varname]"] = array_map('intval', $settings["$oldsetting[varname]"]);
					$settings["$oldsetting[varname]"] = serialize($settings["$oldsetting[varname]"]);
				}
			}
		}

		//this will destroy json/serialized arrays if the validation code is set to something other than
		//"free".  Fixing this requires also changing the validation for the setOption code in the datastore
		//and raises all kinds of other problems (like the fact that we don't present arrays as arrays in
		//the option datastore array and using setOption in the datastore doesn't handle arrays directly).
		//
		//This is a massive ball of string that I'm declining to pull on right now.
		$newvalue = validate_setting_value($settings["$oldsetting[varname]"], $oldsetting['datatype']);

		if ($canAdminAll AND isset($_POST['adminperm_' . $oldsetting['varname']]))
		{
			$newAdminPerm = substr($cleaner->clean($_POST['adminperm_' . $oldsetting['varname']], vB_Cleaner::TYPE_STR), 0, 32);
		}
		else
		{
			$newAdminPerm = $oldsetting['adminperm'];
		}

		// this is a strict type check because we want '' to be different from 0
		// some special cases below only use != checks to see if the logical value has changed
		if (
			$oldsetting['value'] === NULL OR
			(strval($oldsetting['value']) !== strval($newvalue)) OR
			(strval($oldsetting['adminperm']) !== strval($newAdminPerm))
		)
		{
			switch ($oldsetting['varname'])
			{
				case 'cache_templates_as_files':
				{
					if (!is_demo_mode())
					{
						$templatecachepathchanged = true;
					}
				}
				break;

				case 'template_cache_path':
				{

					if (!is_demo_mode())
					{
						$oldtemplatepath = strval($oldsetting['value']);
						$newtemplatepath = $newvalue;
					}
				}
				break;

				case 'languageid':
				{
					if ($oldsetting['value'] != $newvalue)
					{
						$datastore->setOption('languageid', $newvalue, false);
						require_once(DIR . '/includes/adminfunctions_language.php');
						build_language($newvalue);
					}
				}
				break;

				case 'cpstylefolder':
				{
					$admindm =& datamanager_init('Admin', $vbulletin, vB_DataManager_Constants::ERRTYPE_CP);

					$admindm->set_existing(vB::getCurrentSession()->fetch_userinfo());
					$admindm->set('cssprefs', $newvalue);
					$admindm->save();
					unset($admindm);
				}
				break;

				case 'styleid':
				{
					if ($datastore->getOption('storecssasfile'))
					{
						$rebuildstyle = true;
					}
				}
				break;

				case 'attachthumbssize':
				{
					if ($oldsetting['value'] != $newvalue)
					{
						$rebuildstyle = true;
					}
				}
				break;

				case 'storecssasfile':
				{
					if (!is_demo_mode() AND $oldsetting['value'] != $newvalue)
					{
						$datastore->setOption('storecssasfile', $newvalue, false);
						$rebuildstyle = true;
					}
				}
				break;

				case 'cssfilelocation':
				{
					if (!is_demo_mode() AND $oldsetting['value'] != $newvalue)
					{
						if ($datastore->getOption('storecssasfile'))
						{
							$datastore->setOption('cssfilelocation', $newvalue, false);
							$rebuildstyle = true;
						}
					}
				}
				break;

				case 'loadlimit':
				{
					update_loadavg();
				}
				break;

				case 'tagcloud_usergroup':
				{
					build_datastore('tagcloud', serialize(''), 1);
				}
				break;

				default:
				{
					// Legacy Hook 'admin_options_processing_build' Removed //
				}
			}

			if (is_demo_mode() AND
				in_array($oldsetting['varname'], [
					'cache_templates_as_files', 'template_cache_path', 'storecssasfile', 'attachfile', 'usefileavatar',
					'errorlogsecurity', 'safeupload', 'tmppath'
				])
			)
			{
				continue;
			}

			$updateSetting = $assertor->assertQuery('setting', [
				vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
				'value' => $newvalue,
				'adminperm' => $newAdminPerm,
				vB_dB_Query::CONDITIONS_KEY => ['varname' => $oldsetting['varname']],
			]);
		}
	}

	if (!isset($oldsetting))
	{
		return false;
	}

	$datastore->build_options();

	if (defined('DEV_AUTOEXPORT') AND DEV_AUTOEXPORT)
	{
		require_once(DIR . '/includes/functions_filesystemxml.php');
		$xml = get_settings_export_xml('vbulletin');
		autoexport_write_file(DIR . '/install/vbulletin-settings.xml', $xml);
	}

	//handle changes for cache_templates_as_files and template_cache_path
	//we do it here because there are interactions between them and we don't
	//want to redo the chache changes twice if both are changed.
	$api = vB_Api::instanceInternal('template');
	if ($templatecachepathchanged OR (!is_null($oldtemplatepath) AND !is_null($newtemplatepath)))
	{
		if ($datastore->getOption('cache_templates_as_files'))
		{
			if (!is_null($oldtemplatepath))
			{
				//temporarily set the datastore path to the old value to clear it.
				$datastore->setOption('template_cache_path', $oldtemplatepath, false);
				$api->deleteAllTemplateFiles();
				$datastore->setOption('template_cache_path', $newtemplatepath, false);
			}

			$api->saveAllTemplatesToFile();
		}
		else
		{
			//we we changed directories and the cache is off, delete from the old directory
			if (!is_null($oldtemplatepath))
			{
				$datastore->setOption('template_cache_path', $oldtemplatepath, false);
				$api->deleteAllTemplateFiles();
				$datastore->setOption('template_cache_path', $newtemplatepath, false);
			}
			//otherwise delete from the current directory.
			else
			{
				$api->deleteAllTemplateFiles();
			}
		}
	}

	if ($rebuildstyle)
	{
		require_once(DIR . '/includes/adminfunctions_template.php');
		print_rebuild_style(-1, '', 1, 0, 0, 0);
	}
	return true;
}

// this should be consered private to save_settings()
function save_settings__manifest_icon($oldsetting, $files, &$settings)
{
	// In case the input name changes, makes it easier to propagate that below.
	$uploadname = 'manifest_icon';

	$file = convertFileSuperArrayToSingleArray($uploadname, $files);
	$uploadError = $file['error'] ?? 0;
	// First, check for any upload errors, but ignore the NO_FILE errors because
	// that just means they didn't upload anything.
	if (!empty($uploadError) AND $uploadError !== UPLOAD_ERR_NO_FILE)
	{
		$phrasedata = convertUploadErrorToPhrase($uploadError);
		print_stop_message($phrasedata);
	}
	$hasUpload = (
		!empty($file['tmp_name'])
		AND
		$uploadError == UPLOAD_ERR_OK
	);

	// There will be an input like setting[manifest_icon][filedataid] that holds the previous filedataid
	// or 0 (if "remove" button was clicked). Grab it first to handle the remove feature.
	$filedataid = $settings['manifest_icon']['filedataid'] ?? 0;
	$removeSetting = (!$hasUpload AND $filedataid == 0);
	$old = json_decode($oldsetting['value'], true);
	// Check if base filedataid changed.
	// If yes, and not SVG:
	// 	* create the new resize series
	// 	* decrement the old series, increment the new series.
	// If yes, and SVG:
	// 	* mark as SVG
	// 	* decrement the old filedataid
	// 	* increment the new filedataid
	// If no and remove:
	// 	* decrement the old filedataid
	// If no and not remove:
	// 	* do nothing
	$incrementFiledataids = [];
	$decrementFiledataids = [];

	$default = [
		'filedataid_512' => 0,
		'filedataid_192' => 0,
		'sizes' => [512],
		// our default fallback is an SVG.
		'isSVG' => true,
		'mimetype' => 'image/svg+xml',
	];
	// First time fallback
	if (!isset($old['filedataid_512']))
	{
		$old = $default;
	}
	$new = $default;

	if ($hasUpload)
	{
		$location = $file['tmp_name'];
		// If the specified path is local instead of uploaded, something weird is going on.
		// Let's just reject it.
		if (!is_uploaded_file($location))
		{
			print_stop_message('invalid_file_specified');
		}

		// apparently fileinfo may be missing on windows installs
		if (function_exists('mime_content_type'))
		{
			$mimetype = mime_content_type($location);
		}
		else
		{
			// Note, this isn't trust worthy, but we don't have much else we can
			// do without trying to check the bytes ourselves.
			$mimetype = $file['type'];
			// This probably isn't needed, but just in case, let's make it consistent.
			$mimetype = trim(strtolower($mimetype));
		}


		// While image/svg+xml is the only correct mimetype, there are reports in
		// the wild of certain SVG tools exporting it with the wrong mimetype, so
		// let's handle the other alleged case.
		if ($mimetype === 'image/svg+xml' OR $mimetype === 'image/svg')
		{
			$xml = simplexml_load_file($location);
			if ($xml === false)
			{
				print_stop_message('mainfest_icon_requirements');
			}
			$firstTag = $xml->getName();
			if (strtolower($firstTag) !== 'svg')
			{
				print_stop_message('mainfest_icon_requirements');
			}
			$filedataid = convertUploadedFileToFiledataid($file);
			if ($filedataid > 0)
			{
				$new = [
					'filedataid_512' => $filedataid,
					'filedataid_192' => 0,
					'sizes' => [512],
					'isSVG' => true,
					'mimetype' => 'image/svg+xml',
				];
			}
			else
			{
				// let's not try to fallback to anything if the save failed for some reason.
				print_stop_message('mainfest_icon_requirements');
			}
		}
		else
		{
			$imageHandler = vB_Image::instance();
			$isImage = $imageHandler->fileLocationIsImage($location);
			// The manifest icon has to be an SVG or some known image type, because we need to know the image size
			// & resize as necessary.
			if (!$isImage)
			{
				print_stop_message('mainfest_icon_requirements');
			}

			// Rewrite image as part of regular security process before we pass
			// it through any sensitive internal binaries (imagick)
			$newImageData = $imageHandler->loadImage($location);
			// Once we hit above, we do not care about the old file regardless
			// of if it's "safe" or "dangerous".
			if (file_exists($location))
			{
				// Remove old file. Image Handler intentionally DOES NOT write
				// to the old file in case the caller needs to access it.
				@unlink($location);
				unset($location);
			}
			if (empty($newImageData))
			{
				print_stop_message('invalid_file_specified');
			}

			// use re-written image from this point on.
			$location = $newImageData['tmp_name'];
			$file['tmp_name'] = $location;
			$fileInfo = $imageHandler->fetchImageInfo($location);
			[0 => $width, 1 => $height, 2 => $type] = $fileInfo;

			// Currently, google/chrome requires two icons, 192px & 512px square icons.
			// Automatically generate the 2 required icon sizes. First, if the uploaded file is not 512^2,
			// scale it up or down (we don't really care which way)
			$file512 = $file;
			// ATM the image resize wrapper functions we have available maintain aspect ratio. Trying to pass
			// in a non-square icon seems to prevent installability when testing in chrome dev tools, so let's
			// make square icons a requirement for now.
			if ($width != $height)
			{
				print_stop_message('mainfest_icon_requirements');
			}
			if ($width != $height OR $width != 512 OR $height != 512)
			{
				$file512 = resizeImage($file, 512, 512);
			}

			// Now generate the smaller icon (using the first rewritten original, as to minimize weird artifacting
			// if it were originally between 192 & 512) and store that. Ideally we would use the UN-REWRITTEN original, but
			// we cannot trust the raw file before we potentially pass it into sensitive image binaries (e.g. imagemagick).
			// This does mean that we rewrite each version at least twice before save -- perhaps we need to open up access to
			// vB_Library_Content_AttachsaveUpload() so we can optionally skip the library-rewrite.
			// We do it here, because vB_Library_Content_Attach::uploadAttachment() called downstream of
			// convertUploadedFileToFiledataid() below will rewrite the image & delete tmp_name first thing as part of regular
			// security processes (assuming tmp_name holds an image), but resizeImage() requires a file location.
			$file192 = resizeImage($file, 192, 192);

			$filedataid = convertUploadedFileToFiledataid($file512);
			// There are a LOT of steps in this process and so many things that could go wrong...
			// we may need to specialize these error messages for various steps if this happens frequently.
			if ($filedataid <= 0)
			{
				// delete the resized image too since we won't get around to saving it as filedata (if the attach library didn't
				// get around to doing that first).
				if (file_exists($file512['tmp_name']))
				{
					@unlink($file512['tmp_name']);
				}
				// delete the resized image too since we won't get around to saving it as filedata.
				@unlink($file192['tmp_name']);

				print_stop_message('invalid_file_specified');
			}
			$new['filedataid_512'] = $filedataid;

			$filedataid = convertUploadedFileToFiledataid($file192);
			if ($filedataid <= 0)
			{
				// delete the resized image too since we won't get around to saving it as filedata (if the attach library didn't
				// get around to doing that first).
				// Nothing to do about file512 since it's already saved as filedata, and as part of the process the initial
				// tmp_name should've been deleted already.
				if (file_exists($file192['tmp_name']))
				{
					@unlink($file192['tmp_name']);
				}

				print_stop_message('invalid_file_specified');
			}
			$new['filedataid_192'] = $filedataid;
			$new['sizes'] = [512, 192];
			$new['isSVG'] = false;
			// This could be better, since the uploaded mimetype might be BS...
			$new['mimetype'] = $mimetype;
		}
	}
	else
	{
		if ($removeSetting)
		{
			$new = $default;
		}
		else
		{
			// no changes
			$new = $old;
		}
	}

	// This is some logic to handle the increment/decrement properly for the various cases of
	// * no changes
	// * uploaded new icon
	// * removed existing icon
	if ($new['filedataid_512'] > 0 AND ($old['filedataid_512'] ?? 0) != $new['filedataid_512'])
	{
		$incrementFiledataids[] = $new['filedataid_512'];
		$decrementFiledataids[] = $old['filedataid_512'];
	}
	if ($new['filedataid_192'] > 0 AND ($old['filedataid_192'] ?? 0) != $new['filedataid_192'])
	{
		$incrementFiledataids[] = $new['filedataid_192'];
		$decrementFiledataids[] = $old['filedataid_192'];
	}

	// Set filedata.publicview & increment/decrement refcount.
	$assertor = vB::getDbAssertor();
	if ($incrementFiledataids)
	{
		$assertor->assertQuery('incrementFiledataRefcountAndMakePublic', ['filedataid' => $incrementFiledataids]);
	}
	if ($decrementFiledataids)
	{
		$assertor->assertQuery('decrementFiledataRefcount', ['filedataid' => $decrementFiledataids]);
	}

	// We need to know which file to show in the image upload row.
	$new['uploadkey'] = 'filedataid_512';
	$settings['manifest_icon'] = json_encode($new);
}

// should be considered private to save_settings__manifest_icon()
function resizeImage($filearray, $targetWidth, $targetHeight) : array
{
	$newFileLocation = vB_Utilities::getTmpFileName(rand(), 'vB_');
	if (empty($newFileLocation))
	{
		// something happened (like we can't access the tempdir) and we can't get a write location.
		print_stop_message('invalid_file_specified');
	}
	// We may want to drop this down, because there have been cases where the resized thumbnail bloats in filesize.
	// For now, assuming we want the maximum quality for manifest icon reszies
	$thumbquality = 100;
	$imageHandler = vB_Image::instance();
	try
	{
		$resizedImage = $imageHandler->fetchThumbnail(
			$filearray['name'],
			$filearray['tmp_name'],
			$targetWidth,
			$targetHeight,
			$thumbquality
		);
	}
	catch(Exception $e)
	{
		print_stop_message('invalid_file_specified');
	}

	if (!file_put_contents($newFileLocation, $resizedImage['filedata']))
	{
		print_stop_message('invalid_file_specified');
	}

	// Keep some of the old data, like file name.
	$filearray['tmp_name'] = $newFileLocation;
	$filearray['size'] = $resizedImage['filesize'];
	// we should probably check $resizedImage['type'] as well, but unfortunately that is NOT the mimetype that the
	// file array expects... For now assuming no conversion happened. AFAIk the only conversion that
	// happens is for GIFs -> JPEG if jpegconvert = true (false by default)

	return $filearray;
}

/**
* Attempts to run validation code on a setting
*
* @param	string	Setting varname
* @param	mixed	Setting value
* @param	string	Setting validation code
*
* @return	mixed
*/
function exec_setting_validation_code($varname, $value, $validation_code, $raw_value)
{
	if ($validation_code != '')
	{
		//this is a terrible hack, but there aren't a lot of options for converting the code
		//text to a function now that create_function is deprecated.  Longer term we should
		//convert this to a function name or class/function pair and move the code out the
		//db and into the filesystem
		$validation_function = '';
		eval("\$validation_function = function(&\$data, \$raw_data) { $validation_code };");
		$validation_result = $validation_function($value, $raw_value);

		if ($validation_result === false OR $validation_result === null)
		{
			$customerror = 'setting_validation_error_' . $varname;
			$phrases = vB_Api::instanceInternal('phrase')->renderPhrases([
				'custom' => $customerror,
				'generic' => 'you_did_not_enter_a_valid_value',
			]);
			$phrases = $phrases['phrases'];

			//if we don't have a phrase defined we'll return the phrase name.  If so, use the generic.
			if ($phrases['custom'] != $customerror)
			{
				return $phrases['custom'];
			}
			else
			{
				return $phrases['generic'];
			}
		}
		else
		{
			return $validation_result;
		}
	}

	return 1;
}

/**
* Validates the provided value of a setting against its datatype
*
* @param	mixed	(ref) Setting value
* @param	string	Setting datatype ('number', 'boolean' or other)
* @param	boolean	Represent boolean with 1/0 instead of true/false
* @param boolean  Query database for username type
*
* @return	mixed	Setting value
*/
function validate_setting_value(&$value, $datatype, $bool_as_int = true, $username_query = true)
{
	switch ($datatype)
	{
		//We want to preserve the int/float type of the string representation but there isn't a good
		//way to determine if a string is an int or not.  However if the string isn't numeric then this
		//is a fatal error so don't do that.
		case 'number':
			if (!is_numeric($value))
			{
				$value = 0;
			}
			$value = $value + 0;
			break;

		case 'integer':
			$value = intval($value);
			break;

		case 'arrayinteger':
			$key = array_keys($value);
			$size = sizeOf($key);
			for ($i = 0; $i < $size; $i++)
			{
				$value[$key[$i]] = intval($value[$key[$i]]);
			}
			break;

		case 'arrayfree':
			$key = array_keys($value);
			$size = sizeOf($key);
			for ($i = 0; $i < $size; $i++)
			{
				$value[$key[$i]] = trim($value[$key[$i]]);
			}
			break;

		case 'posint':
			$value = max(1, intval($value));
			break;

		case 'boolean':
			$value = ($bool_as_int ? ($value ? 1 : 0) : ($value ? true : false));
			break;

		case 'bitfield':
			if (is_array($value))
			{
				$bitfield = 0;
				foreach ($value AS $bitval)
				{
					$bitfield += $bitval;
				}
				$value = $bitfield;
			}
			else
			{
				$value += 0;
			}
			break;

		case 'username':
			$value = trim($value);
			if ($username_query)
			{
				$userinfo = vB::getDbAssertor()->getRow('user', ['username' => htmlspecialchars_uni($value)]);
				if (empty($value))
				{
					$value =  0;
				}
				else if ($userinfo)
				{
					$value = $userinfo['userid'];
				}
				else
				{
					$value = false;
				}
			}
			break;

		default:
			// e.g. checkboxlist
			if (is_array($value))
			{
				$value = array_map('trim', $value);
				unset($value['vbulletin__ignore']);
			}
			else
			{
				$value = trim($value);
			}
	}

	return $value;
}

/**
* Returns a list of valid settings for $vbulletin->options['cookiedomain'] based on $_SERVER['HTTP_HOST']
*
* @param	string	$_SERVER['HTTP_HOST']
* @param	string	Phrase to use for blank option
*
* @return	array
*/
function fetch_valid_cookiedomains($http_host, $blank_phrase)
{
	$cookiedomains = ['' => $blank_phrase];
	$domain = $http_host;

	while (substr_count($domain, '.') > 1)
	{
		$dotpos = strpos($domain, '.');
		$newdomain = substr($domain, $dotpos);
		$cookiedomains["$newdomain"] = $newdomain;
		$domain = substr($domain, $dotpos + 1);
	}

	return $cookiedomains;
}

/**
* Returns a list of valid settings for $vbulletin->options['cookiepath'] based on $vbulletin->script
*
* @param	string	$vbulletin->script
*
* @return	array
*/
function fetch_valid_cookiepaths($script)
{
	$cookiepaths = ['/' => '/'];
	$curpath = '/';

	$path = preg_split('#/#', substr($script, 0, strrpos($script, '/')), -1, PREG_SPLIT_NO_EMPTY);

	for ($i = 0; $i < sizeof($path) - 1; $i++)
	{
		$curpath .= "$path[$i]/";
		$cookiepaths["$curpath"] = $curpath;
	}

	return $cookiepaths;
}


function get_settings_export_xml($product)
{
	$setting = [];
	$settinggroup = [];

	$groups = vB::getDbAssertor()->assertQuery('settinggroup',
		['volatile' => 1],
		['field' => ['displayorder', 'grouptitle'], 'direction' => [vB_dB_Query::SORT_ASC, vB_dB_Query::SORT_ASC]]
	);

	if ($groups AND $groups->valid())
	{
		foreach ($groups AS $group)
		{
			$settinggroup["$group[grouptitle]"] = $group;
		}
	}

	$conditions = ['product' => $product];
	if ($product == 'vbulletin')
	{
		$conditions['product'] = ['vbulletin', ''];
	}

	$sets = vB::getDbAssertor()->select('setting', $conditions, ['field' => ['displayorder', 'varname']]);

	if ($sets AND $sets->valid())
	{
		foreach ($sets AS $set)
		{
			$setting["$set[grouptitle]"][] = $set;
		}
	}
	unset($set);

	$xml = new vB_XML_Builder();
	$xml->add_group('settinggroups', ['product' => $product]);

	foreach ($settinggroup AS $grouptitle => $group)
	{
		$group = $settinggroup["$grouptitle"];

		//allow blank groups for the vbulletin product -- they are placeholders for products to put options in.
		if (!empty($setting["$grouptitle"]) OR ($product=='vbulletin' AND in_array($group['product'], ['vbulletin', ''])))
		{
			if (empty($group['adminperm']))
			{
				$xml->add_group('settinggroup',
				[
					'name' => htmlspecialchars($group['grouptitle']),
					'displayorder' => $group['displayorder'],
					'product' => $group['product'],
				]);
			}
			else
			{
				$xml->add_group('settinggroup',
				[
					'name' => htmlspecialchars($group['grouptitle']),
					'displayorder' => $group['displayorder'],
					'product' => $group['product'],
					'adminperm' => $group['adminperm'],
				]);
			}

			if (!empty($setting["$grouptitle"]))
			{
				foreach ($setting["$grouptitle"] AS $set)
				{
					$arr = ['varname' => $set['varname'], 'displayorder' => $set['displayorder']];
					if ($set['advanced'])
					{
						$arr['advanced'] = 1;
					}
					$xml->add_group('setting', $arr);

					if ($set['datatype'])
					{
						$xml->add_tag('datatype', $set['datatype']);
					}

					if ($set['optioncode'] != '')
					{
						$xml->add_tag('optioncode', $set['optioncode']);
					}

					if ($set['validationcode'])
					{
						$xml->add_tag('validationcode', $set['validationcode']);
					}

					if ($set['defaultvalue'] != '')
					{
						$xml->add_tag('defaultvalue', ($set['varname'] == 'templateversion' ? vB::getDatastore()->getOption('templateversion') : $set['defaultvalue']));
					}

					if ($set['blacklist'])
					{
						$xml->add_tag('blacklist', 1);
					}

					if ($set['ispublic'])
					{
						$xml->add_tag('ispublic', 1);
					}

					if (!empty($set['adminperm']))
					{
						$xml->add_tag('adminperm', $set['adminperm']);
					}
					$xml->close_group();
				}
			}
			$xml->close_group();
		}
	}

	$xml->close_group();

	$doc = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\r\n\r\n";

	$doc .= $xml->output();
	$xml = null;
	return $doc;
}

/**
* Imports settings from an XML settings file
*
* @param string $xml -- XML text
*/
function xml_import_settings($xml)
{
	//shortcodes depend on the settings we're currently importing...
	$vbphrase = vB_Api::instanceInternal('phrase')->renderPhrasesNoShortcode([
		'please_wait' => 'please_wait',
		'importing_settings' => 'importing_settings',
	]);
	$vbphrase = $vbphrase['phrases'];
	print_dots_start('<b>' . $vbphrase['importing_settings'] . "</b>, $vbphrase[please_wait]", ':', 'dspan');

	$xmlobj = new vB_XML_Parser($xml);
	if ($xmlobj->error_no())
	{
			print_dots_stop();
			print_stop_message2('no_xml_and_no_path');
	}

	if (!$arr = $xmlobj->parse())
	{
		print_dots_stop();
		print_stop_message(['xml_error_x_at_line_y', $xmlobj->error_string(), $xmlobj->error_line()]);
	}

	if (!$arr['settinggroup'])
	{
		print_dots_stop();
		print_stop_message2('invalid_file_specified');
	}

	$product = (empty($arr['product']) ? 'vbulletin' : $arr['product']);

	$db = vB::getDbAssertor();

	// delete old volatile settings and settings that might conflict with new ones...
	$db->assertQuery('vBForum:deleteSettingGroupByProduct', ['product' => $product]);
	$db->assertQuery('vBForum:deleteSettingByProduct', ['product' => $product]);

	// run through imported array
	if (!is_array($arr['settinggroup'][0]))
	{
		$arr['settinggroup'] = [$arr['settinggroup']];
	}

	foreach ($arr['settinggroup'] AS $group)
	{
		// need check to make sure group product== xml product before inserting new settinggroup
		if (empty($group['product']) OR $group['product'] == $product)
		{
			// insert setting group
			$db->assertQuery('settinggroup', [
				vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_INSERTIGNORE,
				'grouptitle' => $group['name'],
				'displayorder' => $group['displayorder'],
				'volatile' => 1,
				'product' => $product,
				'adminperm' => $group['adminperm'] ?? '',
			]);
		}

		// build insert query for this group's settings
		$qBits = [];
		if (isset($group['setting']))
		{
			if (!is_array($group['setting'][0] ?? null))
			{
				$group['setting'] = [$group['setting']];
			}

			foreach ($group['setting'] AS $setting)
			{
				$default = $setting['defaultvalue'] ?? '';
				$newvalue = vB::getDatastore()->getOption($setting['varname']);
				if ($newvalue === null)
				{
					$newvalue = $default;
				}

				$qBits[] = [
					$setting['varname'],
					$group['name'],
					trim($newvalue),
					trim($default),
					trim($setting['datatype']),
					$setting['optioncode'] ?? '',
					$setting['displayorder'],
					$setting['advanced'] ?? 0,
					1,
					$setting['validationcode'] ?? '',
					$setting['blacklist'] ?? 0,
					$product,
					$setting['ispublic'] ?? 0,
					$setting['adminperm'] ?? '',
				];
			}

			$fieldsArray = [
				'varname',
				'grouptitle',
				'value',
				'defaultvalue',
				'datatype',
				'optioncode',
				'displayorder',
				'advanced',
				'volatile',
				'validationcode',
				'blacklist',
				'product',
				'ispublic',
				'adminperm'
			];

			/*insert query*/
			$insertSettings = $db->assertQuery('setting', [
				vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_MULTIPLEINSERT,
				vB_dB_Query::FIELDS_KEY => $fieldsArray,
				vB_dB_Query::VALUES_KEY => $qBits
			]);
		}
	}

	// rebuild the options array
	vB::getDatastore()->build_options();

	// stop the 'dots' counter feedback
	print_dots_stop();

}

/**
* Restores a settings backup from an XML file
*
* Call as follows:
* $path = './path/to/install/vbulletin-settings.xml';
* xml_restore_settings($xml);
*
* @param	mixed	Either XML string or boolean false to use $path global variable
* @param bool	Ignore blacklisted settings
*/
function xml_restore_settings($xml = false, $blacklist = true)
{
	$newsettings = [];

	$vbphrase = vB_Api::instanceInternal('phrase')->fetch(['please_wait', 'importing_settings']);
	print_dots_start('<b>' . $vbphrase['importing_settings'] . "</b>, $vbphrase[please_wait]", ':', 'dspan');

	$xmlobj = new vB_XML_Parser($xml, $GLOBALS['path']);
	if ($xmlobj->error_no() == 1)
	{
			print_dots_stop();
			print_stop_message2('no_xml_and_no_path');
	}
	else if ($xmlobj->error_no() == 2)
	{
			print_dots_stop();
			print_stop_message(['please_ensure_x_file_is_located_at_y', 'vbulletin-settings.xml', $GLOBALS['path']]);
	}

	if (!$newsettings = $xmlobj->parse())
	{
		print_dots_stop();
		print_stop_message(['xml_error_x_at_line_y', $xmlobj->error_string(), $xmlobj->error_line()]);
	}

	if (!$newsettings['setting'])
	{
		print_dots_stop();
		print_stop_message2('invalid_file_specified');
	}

	$product = (empty($newsettings['product']) ? 'vbulletin' : $newsettings['product']);

	$db = vB::getDbAssertor();
	foreach (vB_XML_Parser::getList($newsettings, 'setting') AS $setting)
	{
		// Loop to update all the settings
		$conditions = [
			'varname' => $setting['varname'],
			'product' => $product
		];
		if ($blacklist)
		{
			$conditions['blacklist'] = 0;
		}

		//if this is nothing but whitespace we just set to blank string.
		//we might want to trim all values, but I'm not sure that's a great idea.
		//note that trim(0) is a string so this won't do anything strange with
		//zero values.
		if (trim($setting['value']) == '')
		{
			$setting['value'] = '';
		}

		$db->assertQuery('setting', [
			vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
			'value' => $setting['value'],
			vB_dB_Query::CONDITIONS_KEY => $conditions
		]);
	}

	unset($newsettings);

	// rebuild the options array
	vB::getDatastore()->build_options();

	// stop the 'dots' counter feedback
	print_dots_stop();
}

/**
* Fetches an array of style titles for use in select menus
*
* @param	string	Prefix for titles
* @param	boolean	Display top level style?
*
* @return	array
*/
function fetch_style_title_options_array($titleprefix = '', $displaytop = false)
{
	$stylecache = vB_Library::instance('Style')->fetchStyles(false, false);

	$out = [];
	foreach ($stylecache AS $style)
	{
		$out[$style['styleid']] = $titleprefix . construct_depth_mark($style['depth'] + ($displaytop ? 1 : 0), '--') . ' ' . $style['title'];
	}

	return $out;
}

/**
* Fetches information about GD
*
* @return	array
*/
function fetch_gdinfo()
{
	$gdinfo = [];

	if (function_exists('gd_info'))
	{
		$gdinfo = gd_info();
	}
	else if (function_exists('phpinfo') AND function_exists('ob_start'))
	{
		if (@ob_start())
		{
			eval('@phpinfo();');
			$info = @ob_get_contents();
			@ob_end_clean();
			preg_match('/GD Version[^<]*<\/td><td[^>]*>(.*?)<\/td><\/tr>/si', $info, $version);
			preg_match('/FreeType Linkage[^<]*<\/td><td[^>]*>(.*?)<\/td><\/tr>/si', $info, $freetype);
			$gdinfo = [
				'GD Version'       => $version[1],
				'FreeType Linkage' => $freetype[1],
			];
		}
	}

	if (empty($gdinfo['GD Version']))
	{
		$vbphrase = vB_Api::instanceInternal('phrase')->fetch(['n_a']);
		$gdinfo['GD Version'] = $vbphrase['n_a'];
	}
	else
	{
		$gdinfo['version'] = preg_replace('#[^\d\.]#', '', $gdinfo['GD Version']);
	}

	if (preg_match('#with (unknown|freetype|TTF)( library)?#si', trim($gdinfo['FreeType Linkage']), $freetype))
	{
		$gdinfo['freetype'] = $freetype[1];
	}

	return $gdinfo;
}

/**
* Fetches an array describing the bits in the requested bitfield
*
* @param	string	Represents the array key required... use x|y|z to fetch ['x']['y']['z']
*
* @return	array	Reference to the requested array from includes/xml/bitfield_{product}.xml
*/
function &fetch_bitfield_definitions($string)
{
	static $bitfields = null;

	if ($bitfields === null)
	{
		require_once(DIR . '/includes/class_bitfield_builder.php');
		$bitfields = vB_Bitfield_Builder::return_data();
	}

	$keys = "['" . implode("']['", preg_split('#\|#si', $string, -1, PREG_SPLIT_NO_EMPTY)) . "']";

	$return = [];
	eval('$return =& $bitfields' . $keys . ';');
	return $return;
}

/**
* Attempts to fetch the text of a phrase from the given key.
* If the phrase is not found, the key is returned.
*
* @param	string	Phrase key
*
* @return	string
*/
function fetch_phrase_from_key($phrase_key)
{
	$vbphrase = vB_Api::instanceInternal('phrase')->fetch([$phrase_key]);
	return (isset($vbphrase["$phrase_key"])) ? $vbphrase["$phrase_key"] : $phrase_key;
}

/**
* Returns an array of options and phrase values from a piped list
* such as 0|no\n1|yes\n2|maybe
*
* @param	string	Piped data
*
* @return	array
*/
function fetch_piped_options($piped_data)
{
	$options = [];

	$option_lines = preg_split("#(\r\n|\n|\r)#s", $piped_data, -1, PREG_SPLIT_NO_EMPTY);
	foreach ($option_lines AS $option)
	{
		if (preg_match('#^([^\|]+)\|(.+)$#siU', $option, $option_match))
		{
			$option_text = explode('(,)', $option_match[2]);
			foreach (array_keys($option_text) AS $idx)
			{
				$option_text["$idx"] = fetch_phrase_from_key(trim($option_text["$idx"]));
			}
			$options["$option_match[1]"] = implode(', ', $option_text);
		}
	}

	return $options;
}

/**
 * Called by exec_setting_validation_code(), verifies if this forum can change their 'logintype' setting
 * to the requested value.
 * @param	int    $data    Integer (or numeric string, note no hex) value 0-2, where:
 * 			                   0|login_only_email
 * 			                   1|login_only_username
 * 			                   2|login_both
 *
 * @return	mixed	Boolean true if validated without issues, String error message if validation failed.
 */
function validate_setting_logintype($data)
{
	/*
		See core/clientscripts/vbulletin_settings_validate.js's handle_validation() function.
		If you return anything (string, boolean, integer) that can loosely match '1', that'll be a success.
		So a string (that's not literal "1") will be considered an error, and it'll be displayed by JS.

		Loose equality makes me nervous, but I'll return boolean not integer on success just to be consistent with
		some other settings validation code I've seen.
	 */
	$data = intval($data);
	switch($data)
	{
		case 1:
			/*
				Username is the default, & we're not gonna have dupe usernames. They should always be able to use usernames for login.
			 */
			return true;
			break; // code standards.
		case 0:
			/*
				Here, we need to make sure that using email is OK.
			 */
			$requireuniqueemail = vB::getDatastore()->getOption('requireuniqueemail');
			if (!$requireuniqueemail)
			{
				return fetch_error('setting_validation_error_logintype_requireuniqueemail_conflict');
			}


			$duplicateEmails = vB::getDbAssertor()->getRows('vBAdminCP:checkDuplicateEmails');
			$sharedEmailsCount = count($duplicateEmails);
			if ($sharedEmailsCount > 0)
			{
				return fetch_error('setting_validation_error_logintype_nonunique_emails_found', $sharedEmailsCount);
			}

			return true;
			break;

		case 2:
			/*
				So here, some users might be "sharing emails" (that is 1 person has multiple users).
				In such a case, using an email is not really defined behavior FOR THAT USER, but we expect that forums using this setting & allowing
				multiple usernames per email is an extreme edge case, so we're leaving it to the end user to be smart enough to know to use a username
				and not an email to log-in if they're the unicorn that has multiple usernames per email on a forum that allowed this setting.

				Leaving this separate from the case 1 for context reasons.
			 */
			return true;
			break;
		default:
			// if they're not in the 0-2 range, something done trucked up. Let the usual error handler handle it...
			return false;
			break;
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 116739 $
|| #######################################################################
\*=========================================================================*/
