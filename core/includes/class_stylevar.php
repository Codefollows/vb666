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
* Class to handle style variable storage
*
* @package	vBulletin
*/

abstract class vB_StyleVar
{
	public $registry;

	public $stylevarid;

	protected $definition;
	protected $value;
	protected $inherited = 0;		// used to set the color, 0 = unchanged, 1 = inherited from parent, -1 = customized in this style

	protected $defaults = [];

	private $styleid = -1;

	// static variables for printing color input rows
	protected static $need_colorpicker = true;
	protected static $count = 0;

	// only output the background preview js once
	protected static $need_background_preview_js = true;

	// static variables for including stylevars-as-values autocomplete functionality
	protected static $need_stylevar_autocomplete_js = true;

	// Styelvar cache for stylevar as value references
	protected static $stylevar_cache = [];

	public function print_editor()
	{
		global $vbulletin, $vbphrase;

		$vb5_config =& vB::getConfig();

		$header = $vbphrase["stylevar_{$this->stylevarid}_name"] ?? $this->stylevarid;

		$addbit = false;
		if ($vbulletin->GPC['dostyleid'] == -1)
		{
			$header .= ' - <span class="smallfont">' . construct_link_code2($vbphrase['edit'], "admincp/stylevar.php?do=dfnedit&stylevarid=" . $this->stylevarid);
			$addbit = true;
		}

		if ($this->inherited == -1)
		{
			if (!$addbit)
			{
				$header .= ' - <span class="smallfont">';
				$addbit = true;
			}
			else
			{
				$header .= ' - ';
			}

			$header .= construct_link_code2($vbphrase['revert_gcpglobal'], "admincp/stylevar.php?do=confirmrevert&dostyleid=" .
				$vbulletin->GPC['dostyleid'] . "&stylevarid=" . $this->stylevarid . "&rootstyle=-1");
		}

		if ($addbit)
		{
			$header .= '</span>';
		}

		print_table_header($header);

		if ($vbphrase["stylevar_{$this->stylevarid}_description"])
		{
			print_description_row($vbphrase["stylevar_{$this->stylevarid}_description"], false, 2);
		}

		if ($vb5_config['Misc']['debug'])
		{
			print_label_row($vbphrase['stylevarid'], $this->stylevarid);
		}

		// output this stylevar's inheritance level (inherited or customized)
		// so that we can update the stylevar list and show inherited status
		// immediately
		echo '<script type="text/javascript">
			window.vBulletinStylevarInheritance = window.vBulletinStylevarInheritance ? window.vBulletinStylevarInheritance : {};
			window.vBulletinStylevarInheritance["' . $this->stylevarid . '"] = ' . $this->inherited . ';
		</script>';

		// once we have LSB change this to self::
		$this->print_editor_form();

		if ($vb5_config['Misc']['debug'])
		{
			// debug functionality "inherit all properties" and "clear all fields"
			$debug_buttons = '
				<input type="text"
					data-stylevarid="' . $this->stylevarid . '"
					class="bginput js-inherit-all-properties"
					size="35"
					tabindex="1"
					placeholder="' . $vbphrase['enter_stylevar_of_same_type'] . '"
					title="' . $vbphrase['enter_stylevar_of_same_type'] . '" />
				<input type="button"
					data-stylevarid="' . $this->stylevarid . '"
					class="button js-clear-all-stylevar-fields"
					tabindex="1"
					value="' . $vbphrase['clear_all_fields'] . '" />
			';
			print_label_row($vbphrase['inherit_all_properties'], $debug_buttons);
			unset($debug_buttons);
		}
	}

	abstract public function print_editor_form();

	public function set_value($value)
	{
		//try to ensure that all of the fields are in place when loading a stylevar
		$this->value = array_merge($this->defaults, $value);

		// this resolves inheritance from another stylevar
		$stylevar_value_prefix = 'stylevar_';
		foreach ($this->value AS $key => $value)
		{
			if ((strpos($key, $stylevar_value_prefix)) === 0)
			{
				continue;
			}

			$stylevar_value_key = $stylevar_value_prefix . $key;
			if (empty($value) AND !empty($this->value[$stylevar_value_key]))
			{
				$this->value[$key] = $this->fetch_sub_stylevar_value($this->value[$stylevar_value_key]);

				// Apply color inheritance params --
				// If the stylevar part that is being inherited from another stylevar is a
				// "color" part, then in addition to inheriting, we also have to apply the
				// "inherit_param_color" value to properly transform the color.
				if (substr($stylevar_value_key, -5) == 'color' AND !empty($this->value['inherit_param_color']))
				{
					// I'm not thrilled about calling the template runtime here, but I really want
					// to avoid more code duplication than we already have. Maybe another solution
					// would be to create some sort of utility class for some of the inner workings
					// of the stylevar system and call that from here and the template runtimes.

					//We default a bunch of values to null but the function here requires a string.  Not sure why we use null but there might be a reason.
					$this->value[$key] = vB_Template_Runtime::applyStylevarInheritanceParameters($this->value[$key] ?? '', $this->value['inherit_param_color']);
				}
			}
		}
	}

	private function fetch_sub_stylevar_value($stylevar, &$seen=[])
	{
		if (isset($seen[$stylevar]))
		{
			return '';
		}

		$styleid = $this->styleid;

		if (!isset(self::$stylevar_cache[$styleid]))
		{
			// We don't want to use vB_Api_Style::fetchStyleVars here because API fetchStyleVars() will call
			// getValidStyleFromPreference(), which will "resolve" styleid = -1 to the forum default style...
			// but when we're trying to resolve stylevar inheritance in adminCP, we be using actual stylevars
			// for styleid -1 that's not rolled up in any cache. So we use the LIB version that now has the
			// $resolveStyleid bypass to avoid converting the styleid -1 over.
			// Above does not seem to affect the frontend rendering, as that goes through a different path which
			// begins by fetching the requested style (which will NEVER be -1 since that style doesn't actually
			// exist).
			/** @var vB_Library_Style */
			$lib = vB_Library::instance('style');
			self::$stylevar_cache[$styleid] = $lib->fetchStyleVars([$styleid], false);
		}

		$style = self::$stylevar_cache[$styleid];

		$parts = explode('.', $stylevar);
		if (isset($style[$parts[0]]))
		{
			if (isset($parts[1]) AND empty($style[$parts[0]][$parts[1]]) AND !empty($style[$parts[0]]['stylevar_' . $parts[1]]))
			{
				$seen[$stylevar] = 1;
				return $this->fetch_sub_stylevar_value($style[$parts[0]]['stylevar_' . $parts[1]], $seen);
			}
			else if (isset($parts[1]))
			{
				//we don't go and update old stylevars when we add fields so sometimes they go missing.
				return $style[$parts[0]][$parts[1]] ?? null;
			}
		}

		return $stylevar;
	}

	public function set_definition($definition)
	{
		$this->definition = $definition;
	}

	public function set_inherited($inherited)
	{
		$this->inherited = $inherited;
	}

	public function set_stylevarid($stylevarid)
	{
		$this->stylevarid = $stylevarid;
	}

	public function set_styleid($styleid)
	{
		$this->styleid = $styleid;
	}

	public function get()
	{
		return ($this->value);
	}

	protected function fetch_inherit_color()
	{
		switch($this->inherited)
		{
			case 0:
				$class = 'col-g';
				break;

			case 1:
				$class = 'col-i';
				break;

			case -1:
			default:
				$class = 'col-c';
				break;
		}
		return $class;
	}

	public function build()
	{
		if (!is_array($this->value))
		{
			$this->value = [$this->value];
		}

		$value = serialize($this->value);
		$this->registry->db->query_write("
			REPLACE INTO " . TABLE_PREFIX . "stylevar
			(stylevarid, styleid, value, dateline, username)
			VALUE
			(
				'" . $this->registry->db->escape_string($this->stylevarid) . "',
				" . intval($this->styleid) . ",
				'" . $this->registry->db->escape_string($value) . "',
				" . TIMENOW . ",
				'" . $this->registry->db->escape_string($this->registry->userinfo['username']) . "'
			)
		");
	}

	protected function print_units($current_units, $stylevar_value)
	{
		global $vbphrase;
		$svunitsarray = [
			''     => '',
			'%'    => '%',
			'px'   => 'px',
			'pt'   => 'pt',
			'em'   => 'em',
			'rem'  => 'rem',
			'ch'   => 'ch',
			'ex'   => 'ex',
			'pc'   => 'pc',
			'in'   => 'in',
			'cm'   => 'cm',
			'mm'   => 'mm',
			'vw'   => 'vw',
			'vh'   => 'vh',
			'vmin' => 'vmin',
			'vmax' => 'vmax',
		];

		$this->print_select_row($vbphrase['units'], $this->stylevarid, 'units', $svunitsarray, $current_units, $stylevar_value);
	}

	public function print_border_style($current_style, $stylevar_value)
	{
		global $vbphrase;

		$svborderstylearray = [
			'none'    => 'none',
			'hidden'  => 'hidden',
			'dotted'  => 'dotted',
			'dashed'  => 'dashed',
			'solid'   => 'solid',
			'double'  => 'double',
			'groove'  => 'groove',
			'ridge'   => 'ridge',
			'inset'   => 'inset',
			'outset'  => 'outset',
			'inherit' => 'inherit',
		];

		$this->print_select_row($vbphrase['border_style'], $this->stylevarid, 'style', $svborderstylearray, $current_style, $stylevar_value);
	}

	/**
	 * Returns the UI to display and/or choose a stylevar "part" for this stylevar part
	 * to inherit from. If the stylevar part is a color, this also creates the UI to
	 * specify the transformation to apply (if any) to the color when inheriting.
	 * This is where the stylevar inheritance UI is generated.
	 *
	 * @param  string Stylevar ID
	 * @param  string Stylevar input type
	 * @param  mixed  Stylevar value
	 * @param  string The inherit param value
	 *
	 * @return string HTML output
	 */
	protected function fetch_stylevar_input($stylevarid, $input_type, $stylevar_value, $inherit_param_value = '')
	{
		global $vbphrase;

		$vb5_config =& vB::getConfig();

		$autocomplete_js = '';
		if (self::$need_stylevar_autocomplete_js == true)
		{
			// This relies on GPC['dostyleid']. We're assuming this won't change in a way where you can edit multiple styles at the same time.
			$style = fetch_stylevars_array();
			$global_stylevars = [];
			$global_stylevar_values = [];

			foreach ($style AS $group => $val)
			{
				foreach ($style[$group] AS $global_stylevarid => $global_stylevar)
				{
					$global_stylevar = unserialize($global_stylevar['value']);
					$global_stylevar_values[$global_stylevarid] = $global_stylevar;

					foreach (array_keys($global_stylevar) AS $type)
					{
						if (strpos($type, 'stylevar_') === 0 OR strpos($type, 'inherit_param_') === 0)
						{
							continue;
						}

						$global_stylevars[] = "'" . vB_Template_Runtime::escapeJS($global_stylevarid) . '.' . $type . "'";
					}
				}
			}

			$autocomplete_js .=
				'<script type="text/javascript">
				//<!--
				$(() => initStylevarInheritanceControls([' . implode(', ', $global_stylevars) . '], ' . json_encode($global_stylevar_values) . '));
				//-->
				</script>
				<div class="stylevar-autocomplete-menu"></div>';

			self::$need_stylevar_autocomplete_js = false;
		}

		$stylevar_name = 'stylevar[' . $stylevarid .'][stylevar_' . $input_type . ']';
		$stylevar_title_attr = "title=\"name=&quot;$stylevar_name&quot;\"";
		$uniqueid = fetch_uniqueid_counter();

		$inherit_param_output = '';
		if (substr($input_type, -5) == 'color')
		{
			$inherit_param_name = 'stylevar[' . $stylevarid .'][inherit_param_' . $input_type . ']';
			$inherit_param_title_attr = "title=\"name=&quot;$inherit_param_name&quot;\"";
			$inherit_param_uniqueid = fetch_uniqueid_counter();

			$inherit_param_output = "<br />
				<input type=\"text\"
					name=\"$inherit_param_name\"
					id=\"inp_{$inherit_param_name}_$inherit_param_uniqueid\"
					value=\"" . htmlspecialchars_uni($inherit_param_value) . "\"
					tabindex=\"1\"
					size=\"35\"
					$inherit_param_title_attr
					class=\"js-inherit-params\"
					placeholder=\"$vbphrase[color_transformation_parameters]\"
					/>
				<input type=\"text\"
					class=\"inherit-param-display-value readonly\"
					tabindex=\"1\"
					size=\"8\"
					readonly=\"readonly\" />
				<input type=\"text\"
					class=\"inherit-param-display-value-color readonly hide\"
					style=\"width:10px\"
					disabled=\"disabled\" /><br />
				<input type=\"text\"
					tabindex=\"1\"
					size=\"35\"
					class=\"js-generate-transform-params\"
					placeholder=\"$vbphrase[enter_target_color_to_generate_params]\"
					/>
			";

			construct_hidden_code('stylevar[' . $stylevarid .'][original_inherit_param_' . $input_type . ']', htmlspecialchars_uni($inherit_param_value), false);
		}

		// NOTE see further below-- inheritance is currently never shown
		// outside debug mode.
		if (!$vb5_config['Misc']['debug'] AND !$stylevar_value)
		{
			// Remove the entire line if they can't change it and there's no value set
			return '';
		}

		$disabled_attr = !$vb5_config['Misc']['debug'] ? ' readonly="readonly"' : '';
		$addClass = !$vb5_config['Misc']['debug'] ? ' readonly' : '';

		$phraseText = $vb5_config['Misc']['debug'] ? $vbphrase['or_inherit_from'] : $vbphrase['inherits_from'];

		$output = '<div style="clear:both"></div><div class="js-inheritance-container" style="margin-top:5px;white-space:nowrap;"> ' . $phraseText . ': <br /> ' .
			"<input type=\"text\"
				name=\"$stylevar_name\"
				id=\"inp_{$stylevar_name}_$uniqueid\"
				class=\"stylevar-autocomplete$addClass\"
				value=\"" . htmlspecialchars_uni($stylevar_value) . "\"
				tabindex=\"1\"
				size=\"35\"
				$stylevar_title_attr
				data-options-id=\"sel_{$stylevar_name}_$uniqueid\"
				placeholder=\"$vbphrase[stylevar_to_inherit_from]\"
				$disabled_attr /> " .
			"<input type=\"text\"
				class=\"stylevar-display-value readonly\"
				tabindex=\"1\"
				size=\"8\"
				readonly=\"readonly\" /> " .
			"<input type=\"text\"
				class=\"stylevar-display-value-color readonly hide\"
				style=\"width:10px\"
				disabled=\"disabled\" /> " .
			$inherit_param_output .
			"</div>\n" .
			$autocomplete_js;

		// ensure we are able to check if the stylevar inheritance has changed at save time
		construct_hidden_code('stylevar[' . $stylevarid .'][original_stylevar_' . $input_type . ']', htmlspecialchars_uni($stylevar_value), false);

		// there is code above to change the display to a read-only UI when debug
		// mode is turned off. But it's not clear that we want to display anything about
		// stylevar inheritance outside debug mode, so we'll turn that off for now.
		// I'll leave the code there in case we want to show what's happening with
		// inheritance outside debug mode at a later date
		if (!$vb5_config['Misc']['debug'])
		{
			$output = '';
		}

		return $output;
	}

	protected function print_input_row($title, $stylevarid, $input_type, $value, $stylevar_value)
	{
		$name = 'stylevar[' . $stylevarid . '][' . $input_type . ']';
		$id = 'ctrl_' . $name;
		$value = htmlspecialchars_uni($value);

		$input = construct_input('text', $name, [
			'size' => 35,
			'dir' => verify_text_direction(''),
			'value' => $value
		]);

		//not sure why we put the ID in a div wrapper.  We seem to put it on the control directly elsewhere.
		$cell = '<div id="' . $id . '">' . $input;
		$cell .= $this->fetch_stylevar_input($stylevarid, $input_type, $stylevar_value);
		$cell .= "</div>\n";

		print_label_row(
			$title,
			$cell,
			'', 'top', $name
		);
		construct_hidden_code('stylevar[' . $stylevarid . '][original_' . $input_type . ']', $value, false);
	}

	protected function print_textarea_row($title, $stylevarid, $input_type, $value, $stylevar_value)
	{
		global $vbphrase;
		$vb5_config =& vB::getConfig();

		$name = 'stylevar[' . $stylevarid . '][' . $input_type . ']';
		$textarea_id = 'ta_' . $name . '_' . fetch_uniqueid_counter();
		$value = htmlspecialchars_uni($value);
		$cols = 40;
		$rows = 20;
		$direction = verify_text_direction('');

		$cell = "<div id=\"ctrl_$name\"><textarea name=\"$name\" id=\"$textarea_id\" rows=\"$rows\" cols=\"$cols\" wrap=\"virtual\" dir=\"$direction\" tabindex=\"1\"" . iif($vb5_config['Misc']['debug'], " title=\"name=&quot;$name&quot;\"") . ">" . $value . "</textarea>";

		$cell .= $this->fetch_stylevar_input($stylevarid, $input_type, $stylevar_value);

		$cell .= "</div>\n";

		print_label_row(
			$title,
			$cell,
			'', 'top', $name
		);
		construct_hidden_code('stylevar[' . $stylevarid . '][original_' . $input_type . ']', $value, false);
	}

	protected function print_select_row($title, $stylevarid, $input_type, $array, $value, $stylevar_value)
	{
		global $vbphrase;
		$vb5_config =& vB::getConfig();

		$name = 'stylevar[' . $stylevarid . '][' . $input_type . ']';
		$uniqueid = fetch_uniqueid_counter();

		$select = "<div id=\"ctrl_$name\"><select name=\"$name\" id=\"sel_{$name}_$uniqueid\" tabindex=\"1\" class=\"bginput\"" . iif($vb5_config['Misc']['debug'], " title=\"name=&quot;$name&quot;\"") . ">\n";
		$select .= construct_select_options($array, $value, true);
		$select .= "</select>";

		$select .= $this->fetch_stylevar_input($stylevarid, $input_type, $stylevar_value);

		$select .= "</div>\n";

		print_label_row($title,
			$select, '', 'top', $name
		);
		construct_hidden_code('stylevar[' . $stylevarid . '][original_' . $input_type . ']', $value, false);
	}

	protected function print_yes_no_row($title, $stylevarid, $input_type, $value, $stylevar_value)
	{
		$name = 'stylevar[' . $stylevarid . '][' . $input_type . ']';
		$value = intval($value);

		$cell  = get_yes_no_block($name, $value) . $this->fetch_stylevar_input($stylevarid, $input_type, $stylevar_value);
		print_label_row($title, $cell, '', 'top', $name);
		construct_hidden_code('stylevar[' . $stylevarid . '][original_' . $input_type . ']', $value, false);
	}

	protected function print_color_input_row($title, $stylevarid, $color_value, $stylevar_value, $inherit_param_value = '', $name = 'color')
	{
		global $vbphrase;

		$cp = '';

		$color_value = htmlspecialchars_uni($color_value);

		//only include the colorpicker on the first color element.
		if (self::$need_colorpicker)
		{
			//construct all of the markup/javascript for the color picker.

			//set from construct_color_picker
			global $colorPickerWidth, $colorPickerType;

			$cp = '<script type="text/javascript" src="core/clientscript/vbulletin_cpcolorpicker.js?v=' .
				vB::getDatastore()->getOption('simpleversion') . '"></script>' . "\n";
			$cp .= construct_color_picker(11);

			register_js_phrase('css_value_invalid');
			register_js_phrase('color_picker_not_ready');

			$userinfo = vB_User::fetchUserinfo(0, ['admin']);
			$cp .= '
					<script type="text/javascript">
					<!--
					var bburl = "' . vB::getDatastore()->getOption('bburl') .'";
					var cpstylefolder = "' . $userinfo['cssprefs'] . '";
					var colorPickerWidth = ' . intval($colorPickerWidth) . ';
					var colorPickerType = ' . intval($colorPickerType) . ';
					//-->
				</script>';

			self::$need_colorpicker = false;
		}

		$vb5_config =& vB::getConfig();

		$id = 'color_'. self::$count;
		$color_name = 'stylevar[' . $stylevarid .'][' . $name . ']';

		$title_attr = ($vb5_config['Misc']['debug'] ? " title=\"name=&quot;$color_name&quot;\"" : '');
		$cell =
			"<div id=\"ctrl_$color_name\" class=\"color_input_container\">" .
				"<input type=\"text\" name=\"$color_name\" id=\"$id\" " .
					"value=\"$color_value\" " .
					"tabindex=\"1\" $title_attr class=\"color_input_control\"/>" .
			"</div>";

		$color_preview = '<div id="preview_' . self::$count .
			'" class="colorpreview" onclick="open_color_picker(' . self::$count . ', event)"></div>';

		// input to specify the stylevar to inherit from
		$or_stylevar = $this->fetch_stylevar_input($stylevarid, $name, $stylevar_value, $inherit_param_value);

		print_label_row(
			$title,
			$cp . $cell . $color_preview . $or_stylevar,
			'', 'top', $color_name
		);
		construct_hidden_code('stylevar[' . $stylevarid .'][original_' . $name . ']', $color_value, false);

		self::$count++;
	}

	//let's rely on conventions where we can to cut down on parameter passing for readability.
	//we may still need the existing functions for edge cases where we aren't pulling from
	//the values array
	protected function print_color_input_row_quick($phrase, $name)
	{
		global $vbphrase;
		return $this->print_color_input_row(
			$vbphrase[$phrase],
			$this->stylevarid,
			$this->value[$name],
			$this->value['stylevar_' . $name],
			$this->value['inherit_param_' . $name],
			$name
		);
	}

	protected function print_background_output()
	{
		//this assumes that there is a base tag such that all relative links are to the site root

		global $vbphrase;
		$image = $this->value['image'] ?? '';

		// if the image path was entered with quotes, it will cause problems due to the
		// relative path added above, and when outputting the value in the style="" tag below
		$image = str_replace(['"', "'"], '', $image);

		$background_preview_js = '';
		if (self::$need_background_preview_js)
		{
			$background_preview_js = '
				<script type="text/javascript">
				<!--
					function previewBackground(stylevar)
					{
						/**
						 * @param	string	name of the stylevar
						 * @param	string	the item you want to fetch (color, background image, repeat, etc)
						 * @return	string	the value from the form element
						 */
						var fetch_form_element_value = function(stylevar, item)
						{
							var wrapperid = "ctrl_stylevar[" + stylevar + "][" + item + "]";
							var wrapper = YAHOO.util.Dom.get(wrapperid);

							if (item == "color" || item == "image" || item == "x" || item == "y" || item == "gradient_start_color" || item == "gradient_mid_color" || item == "gradient_end_color")
							{
								// input for color, image, offsets, and gradient color stops
								var formel = wrapper.getElementsByTagName("input");
								if (formel && formel[0])
								{
									if (!formel[0].value && formel[1] && formel[1].value)
									{
										// get inherited value from formel[1].value
										return vBulletin.getInheritedStylevarValue(formel[1], formel[1].value).transformed;
									}
									else
									{
										return formel[0].value;
									}
								}
							}
							else
							{
								// select for background repeat, units, gradient_type, and gradient_direction
								formel = wrapper.getElementsByTagName("select");
								var inheritel = wrapper.getElementsByTagName("input");
								if (formel && formel[0])
								{
									if (!formel[0].value && inheritel[0] && inheritel[0].value)
									{
										// get inherited value from inheritel[0].value
										return vBulletin.getInheritedStylevarValue(inheritel[0], inheritel[0].value).transformed;
									}
									else
									{
										return formel[0].value;
									}
								}
							}
						};

						// The order of the background layers is important. Color is lowest,
						// then gradient, then the image is the topmost layer.
						// Keep syncronized with both runtime outputStyleVar() implementations.

						var backgroundLayers = [],
							// This assumes the images folder is stored in root directory
							image_path = fetch_form_element_value(stylevar, "image"),
							offset_units = fetch_form_element_value(stylevar, "units"),
							x_offset = fetch_form_element_value(stylevar, "x") || 0,
							y_offset = fetch_form_element_value(stylevar, "x") || 0,
							backgroundColor = fetch_form_element_value(stylevar, "color"),
							gradientType = fetch_form_element_value(stylevar, "gradient_type"),
							gradientDirection = fetch_form_element_value(stylevar, "gradient_direction"),
							gradientColors = [];

						backgroundLayers.push(
							(image_path ? image_path : "none") + " " +
							fetch_form_element_value(stylevar, "repeat") + " " +
							x_offset + offset_units + " " +
							y_offset + offset_units
						);

						$.each(["gradient_start_color", "gradient_mid_color", "gradient_end_color"], function(idx, el)
						{
							var colorVal = fetch_form_element_value(stylevar, el);
							if (colorVal)
							{
								gradientColors.push(colorVal);
							}
						});

						if (gradientType && gradientDirection && gradientColors.length >= 2)
						{
							backgroundLayers.push(gradientType + "(" + gradientDirection + ", " + gradientColors.join(", ") + ")");
						}

						if (backgroundColor)
						{
							backgroundLayers.push(backgroundColor);
						}

						backgroundLayers = backgroundLayers.join(", ");

						//alert("backgroundLayers: " + backgroundLayers);
						YAHOO.util.Dom.get("preview_bg_" + stylevar).style.background = backgroundLayers;
					}
				-->
				</script>';
			self::$need_background_preview_js = false;
		}

		$cell = "
			<div id=\"preview_bg_" . $this->stylevarid . "\"
				style=\"width:100%;height:30px;border:1px solid #000000;\">
			</div>
			<script type=\"text/javascript\">previewBackground('" . $this->stylevarid . "');</script>
		";

		$label = '<a href="javascript:previewBackground(\'' . $this->stylevarid . '\');">'. $vbphrase['click_here_to_preview'] .' </a>';
		print_label_row($label, $background_preview_js . $cell);
	}
}

class vB_StyleVar_default extends vB_StyleVar
{
	private $datatype;

	private $titlemap = [
		'string' => 'string',
		'url' => 'url_gstyle',
		'imagedir' => 'image_path',
		'path' => 'path',
		'numeric' => 'numeric',
		'size' => 'size_gstyle',
		'boolean' => 'enable',
		'fontlist' => 'fontlist',
	];

	public function __construct($datatype)
	{
		$this->datatype = $datatype;

		$this->defaults = [
			$datatype => null,
			'stylevar_' . $datatype => null,
		];

		//it's possible that this should be split from the "default" type because
		//it's a little more complex than the others
		if ($datatype == 'size')
		{
			$this->defaults['units'] = null;
			$this->defaults['stylevar_units'] = null;
		}
	}

	public function print_editor_form()
	{
		global $vbphrase;
		$titlephrase = $this->titlemap[$this->datatype];

		// imagedir, url, path, and string are technically all just strings
		switch ($this->datatype)
		{
			case 'string':
			case 'url':
			case 'imagedir':
			case 'path':
			case 'numeric':
				$this->print_input_row($vbphrase[$titlephrase],  $this->stylevarid, $this->datatype, $this->value[$this->datatype], $this->value['stylevar_' . $this->datatype]);
				break;

			case 'size':
				$this->print_units($this->value['units'], $this->value['stylevar_units']);
				$this->print_input_row($vbphrase[$titlephrase],  $this->stylevarid, $this->datatype, $this->value[$this->datatype], $this->value['stylevar_' . $this->datatype]);
				break;

			case 'boolean':
				$this->print_yes_no_row($vbphrase[$titlephrase], $this->stylevarid, $this->datatype, $this->value[$this->datatype], $this->value['stylevar_' . $this->datatype]);
				break;

			case 'fontlist':
				$this->print_textarea_row($vbphrase[$titlephrase], $this->stylevarid, $this->datatype, $this->value[$this->datatype], $this->value['stylevar_' . $this->datatype]);
				break;
		}
	}
}

class vB_StyleVar_padding extends vB_StyleVar
{
	protected $defaults = [
		'units' => null,
		'same' => null,
		'top' => null,
		'right' => null,
		'bottom' => null,
		'left' => null,
		'stylevar_units' => null,
		'stylevar_same' => null,
		'stylevar_top' => null,
		'stylevar_right' => null,
		'stylevar_bottom' => null,
		'stylevar_left' => null,
	];

	public function print_editor_form()
	{
		global $vbphrase;
		$this->print_units($this->value['units'], $this->value['stylevar_units']);
		$this->print_yes_no_row($vbphrase['use_same_padding_margin'], $this->stylevarid, 'same', $this->value['same'], $this->value['stylevar_same']);
		$this->print_input_row($vbphrase['top'], $this->stylevarid, 'top', $this->value['top'], $this->value['stylevar_top']);
		$this->print_input_row($vbphrase['right_gstyle'], $this->stylevarid, 'right', $this->value['right'], $this->value['stylevar_right']);
		$this->print_input_row($vbphrase['bottom'], $this->stylevarid, 'bottom', $this->value['bottom'], $this->value['stylevar_bottom']);
		$this->print_input_row($vbphrase['left_gstyle'], $this->stylevarid, 'left', $this->value['left'], $this->value['stylevar_left']);
	}
}

class vB_StyleVar_margin extends vB_StyleVar
{
	protected $defaults = [
		'units' => null,
		'same' => null,
		'top' => null,
		'right' => null,
		'bottom' => null,
		'left' => null,
		'stylevar_units' => null,
		'stylevar_same' => null,
		'stylevar_top' => null,
		'stylevar_right' => null,
		'stylevar_bottom' => null,
		'stylevar_left' => null,
	];

	public function print_editor_form()
	{
		global $vbphrase;
		$this->print_units($this->value['units'], $this->value['stylevar_units']);
		$this->print_yes_no_row($vbphrase['use_same_padding_margin'], $this->stylevarid, 'same', $this->value['same'], $this->value['stylevar_same']);
		$this->print_input_row($vbphrase['top'], $this->stylevarid, 'top', $this->value['top'], $this->value['stylevar_top']);
		$this->print_input_row($vbphrase['right_gstyle'], $this->stylevarid, 'right', $this->value['right'], $this->value['stylevar_right']);
		$this->print_input_row($vbphrase['bottom'], $this->stylevarid, 'bottom', $this->value['bottom'], $this->value['stylevar_bottom']);
		$this->print_input_row($vbphrase['left_gstyle'], $this->stylevarid, 'left', $this->value['left'], $this->value['stylevar_left']);
	}
}

class vB_StyleVar_textdecoration extends vB_StyleVar
{
	protected $defaults = [
		'none' => null,
		'underline' => null,
		'overline' => null,
		'line-through' => null,
		'stylevar_none' => null,
		'stylevar_underline' => null,
		'stylevar_overline' => null,
		'stylevar_line-through' => null,
	];

	public function print_editor_form()
	{
		global $vbphrase;
		// needs checked
		$this->print_yes_no_row($vbphrase['none'], $this->stylevarid, 'none', $this->value['none'], $this->value['stylevar_none']);
		$this->print_yes_no_row($vbphrase['underline_gstyle'], $this->stylevarid, 'underline', $this->value['underline'], $this->value['stylevar_underline']);
		$this->print_yes_no_row($vbphrase['overline'], $this->stylevarid, 'overline', $this->value['overline'], $this->value['stylevar_overline']);
		$this->print_yes_no_row($vbphrase['linethrough'], $this->stylevarid, 'line-through', $this->value['line-through'], $this->value['stylevar_line-through']);
	}
}

class vB_StyleVar_texttransform extends vB_StyleVar
{
	protected $defaults = [
		'texttransform' => null,
		'stylevar_texttransform' => null,
	];

	public function print_editor_form()
	{
		global $vbphrase;

		$values = [
			'none'       => $vbphrase['none'],
			'capitalize' => $vbphrase['capitalize'],
			'uppercase'  => $vbphrase['uppercase'],
			'lowercase'  => $vbphrase['lowercase'],
			'initial'    => $vbphrase['initial'],
			'inherit'    => $vbphrase['inherit'],
		];

		$this->print_select_row($vbphrase['text_transform'], $this->stylevarid, 'texttransform', $values, $this->value['texttransform'], $this->value['stylevar_texttransform']);
	}
}

class vB_StyleVar_textalign extends vB_StyleVar
{
	protected $defaults = [
		'textalign' => null,
		'stylevar_textalign' => null,
	];

	public function print_editor_form()
	{
		global $vbphrase;

		$values = [
			'left'    => $vbphrase['left_gstyle'],
			'right'   => $vbphrase['right_gstyle'],
			'center'  => $vbphrase['center'],
			'justify' => $vbphrase['justify'],
			'initial' => $vbphrase['initial'],
			'inherit' => $vbphrase['inherit'],
		];

		$this->print_select_row($vbphrase['text_align'], $this->stylevarid, 'textalign', $values, $this->value['textalign'], $this->value['stylevar_textalign']);
	}
}

class vB_StyleVar_font extends vB_StyleVar
{
	protected $defaults = [
		'family' => null,
		'units' => null,
		'size' => null,
		'lineheight' => null,
		'weight' => null,
		'style' => null,
		'variant' => null,
		'stylevar_family' => null,
		'stylevar_units' => null,
		'stylevar_size' => null,
		'stylevar_lineheight' => null,
		'stylevar_weight' => null,
		'stylevar_style' => null,
		'stylevar_variant' => null,
	];

	public function print_editor_form()
	{
		global $vbphrase;

		$font_weights = [
			'' => '',
			'300' =>  $vbphrase['light_gstyle'],
			'normal' => $vbphrase['normal_gstyle'],
			'500' =>  $vbphrase['medium_gstyle'],
			'600' =>  $vbphrase['semibold'],
			'bold' => $vbphrase['bold_gstyle'],
			'bolder' => $vbphrase['bolder'],
			'lighter' => $vbphrase['lighter'],
		];

		$font_styles = [
			'' => '',
			'normal' => $vbphrase['normal_gstyle'],
			'italic' => $vbphrase['italic_gstyle'],
			'oblique' => $vbphrase['oblique'],
		];

		$font_variants = [
			'' => '',
			'normal' => $vbphrase['normal_gstyle'],
			'small-caps' => $vbphrase['small_caps'],
		];

		$this->print_input_row($vbphrase['font_family_gstyle'], $this->stylevarid, 'family', $this->value['family'], $this->value['stylevar_family']);
		$this->print_units($this->value['units'], $this->value['stylevar_units']);
		$this->print_input_row($vbphrase['font_size'], $this->stylevarid, 'size', $this->value['size'], $this->value['stylevar_size']);
		$this->print_input_row($vbphrase['font_lineheight'], $this->stylevarid, 'lineheight', $this->value['lineheight'], $this->value['stylevar_lineheight']);
		$this->print_select_row($vbphrase['font_weight'], $this->stylevarid, 'weight', $font_weights, $this->value['weight'], $this->value['stylevar_weight']);
		$this->print_select_row($vbphrase['font_style'], $this->stylevarid, 'style', $font_styles, $this->value['style'], $this->value['stylevar_style']);
		$this->print_select_row($vbphrase['font_variant'], $this->stylevarid, 'variant', $font_variants, $this->value['variant'], $this->value['stylevar_variant']);
	}
}

class vB_StyleVar_background extends vB_StyleVar
{
	protected $defaults = [
		'color' => null,
		'image' => null,
		'x' => null,
		'y' => null,
		'repeat' => null,
		'units' => null,
		'gradient_type' => null,
		'gradient_direction' => null,
		'gradient_start_color' => null,
		'gradient_mid_color' => null,
		'gradient_end_color' => null,

		//everything has a "stylevar_" field as well, We might want to automate this/clean up the function passing
		//by relying on these conventions assuming they are consistant across all types.
		'stylevar_color' => null,
		'stylevar_image' => null,
		'stylevar_x' => null,
		'stylevar_y' => null,
		'stylevar_repeat' => null,
		'stylevar_units' => null,
		'stylevar_gradient_type' => null,
		'stylevar_gradient_direction' => null,
		'stylevar_gradient_start_color' => null,
		'stylevar_gradient_mid_color' => null,
		'stylevar_gradient_end_color' => null,

		//colors also have the inherit filter parameter.
		'inherit_param_color' => null,
		'inherit_param_gradient_start_color' => null,
		'inherit_param_gradient_mid_color' => null,
		'inherit_param_gradient_end_color' => null,
	];

	public function print_editor_form()
	{
		global $vbphrase;

		$repeatValues = [
			'' => '',
			'repeat' => $vbphrase['repeat'],
			'repeat-x' => $vbphrase['repeat_x'],
			'repeat-y' => $vbphrase['repeat_y'],
			'no-repeat' => $vbphrase['no_repeat'],
		];

		$gradientTypeValues = [
			'' => '',
			'linear-gradient' => $vbphrase['linear_gradient'],
			'radial-gradient' => $vbphrase['radial_gradient'],
			'repeating-linear-gradient' => $vbphrase['repeating_linear_gradient'],
			'repeating-radial-gradient' => $vbphrase['repeating_radial_gradient'],
		];

		$gradientDirectionValues = [
			'' => '',
			'to top' => $vbphrase['to_top'],
			'to top right' => $vbphrase['to_top_right'],
			'to right' => $vbphrase['to_right'],
			'to bottom right' => $vbphrase['to_bottom_right'],
			'to bottom' => $vbphrase['to_bottom'],
			'to bottom left' => $vbphrase['to_bottom_left'],
			'to left' => $vbphrase['to_left'],
			'to top left' => $vbphrase['to_top_left'],
		];

		$this->print_color_input_row_quick('background_color', 'color');
		$this->print_input_row($vbphrase['background_image'], $this->stylevarid, 'image', $this->value['image'], $this->value['stylevar_image']);
		$this->print_select_row($vbphrase['background_repeat'], $this->stylevarid, 'repeat', $repeatValues, $this->value['repeat'], $this->value['stylevar_repeat']);
		$this->print_units($this->value['units'], $this->value['stylevar_units']);
		$this->print_input_row($vbphrase['background_position_x'], $this->stylevarid, 'x', $this->value['x'], $this->value['stylevar_x']);
		$this->print_input_row($vbphrase['background_position_y'], $this->stylevarid, 'y', $this->value['y'], $this->value['stylevar_y']);
		$this->print_select_row($vbphrase['background_gradient_type'], $this->stylevarid, 'gradient_type', $gradientTypeValues, $this->value['gradient_type'], $this->value['stylevar_gradient_type']);
		$this->print_select_row($vbphrase['background_gradient_direction'], $this->stylevarid, 'gradient_direction', $gradientDirectionValues, $this->value['gradient_direction'], $this->value['stylevar_gradient_direction']);
		$this->print_color_input_row_quick('background_gradient_start_color', 'gradient_start_color');
		$this->print_color_input_row_quick('background_gradient_mid_color', 'gradient_mid_color');
		$this->print_color_input_row_quick('background_gradient_end_color', 'gradient_end_color');
		$this->print_background_output();
	}
}

class vB_StyleVar_dimension extends vB_StyleVar
{
	protected $defaults = [
		'units' => null,
		'width' => null,
		'height' => null,
		'stylevar_units' => null,
		'stylevar_width' => null,
		'stylevar_height' => null,
	];

	public function print_editor_form()
	{
		global $vbphrase;

		$this->print_units($this->value['units'], $this->value['stylevar_units']);
		$this->print_input_row($vbphrase['width'], $this->stylevarid, 'width', $this->value['width'], $this->value['stylevar_width']);
		$this->print_input_row($vbphrase['height'], $this->stylevarid, 'height', $this->value['height'], $this->value['stylevar_height']);
	}
}

class vB_StyleVar_border extends vB_StyleVar
{
	protected $defaults = [
		'units' => null,
		'width' => null,
		'style' => null,
		'color' => null,
		'stylevar_units' => null,
		'stylevar_width' => null,
		'stylevar_style' => null,
		'stylevar_color' => null,
		'inherit_param_color' => null,
	];

	public function print_editor_form()
	{
		global $vbphrase;

		$this->print_units($this->value['units'], $this->value['stylevar_units']);
		$this->print_input_row($vbphrase['width'], $this->stylevarid, 'width', $this->value['width'], $this->value['stylevar_width']);
		$this->print_border_style($this->value['style'], $this->value['stylevar_style']);
		$this->print_color_input_row_quick('color_gstyle', 'color');
	}
}

class vB_StyleVar_color extends vB_StyleVar
{
	protected $defaults = [
		'color' => null,
		'stylevar_color' => null,
		'inherit_param_color' => null,
	];

	public function print_editor_form()
	{
		$this->print_color_input_row_quick('color_gstyle', 'color');
	}
}

class vB_StyleVar_factory
{
	/**
	 * Creates a stylevar.
	 *
	 * @param string $type
	 * @return vB_StyleVar
	 */
	public static function create($type)
	{
		// not really a good factory, in fact, this is a dumb factory
		$stylevarobj = null;
		switch ($type)
		{
			case 'numeric':
			case 'string':
			case 'url':
			case 'imagedir':
			case 'image':
			case 'path':
			case 'fontlist':
			case 'size':
			case 'boolean':
				$stylevarobj = new vB_StyleVar_default($type);
				break;

			case 'color':
				$stylevarobj = new vB_StyleVar_color();
				break;

			case 'background':
				$stylevarobj = new vB_StyleVar_background();
				break;

			case 'textdecoration':
				$stylevarobj = new vB_StyleVar_textdecoration();
				break;

			case 'texttransform':
				$stylevarobj = new vB_StyleVar_texttransform();
				break;

			case 'textalign':
				$stylevarobj = new vB_StyleVar_textalign();
				break;

			case 'font':
				$stylevarobj = new vB_StyleVar_font();
				break;

			case 'dimension':
				$stylevarobj = new vB_StyleVar_dimension();
				break;

			case 'border':
				$stylevarobj = new vB_StyleVar_border();
				break;

			case 'padding':
				$stylevarobj = new vB_StyleVar_padding();
				break;

			case 'margin':
				$stylevarobj = new vB_StyleVar_margin();
				break;

			default:
				trigger_error("Unknown Data Type ( Type: " . $type . ")", E_USER_ERROR);
		}

		return $stylevarobj;
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 115978 $
|| #######################################################################
\*=========================================================================*/
