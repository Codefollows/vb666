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

class vB5_Template_Runtime
{
	//This is intended to allow the runtime to know that template it is rendering.
	//It's ugly and shouldn't be used lightly, but making some features widely
	//available to all templates is uglier.
	private static $templates = [];

	private static $dateUtil = null;

	public static function startTemplate($template)
	{
		array_push(self::$templates, $template);
	}

	public static function endTemplate()
	{
		array_pop(self::$templates);
	}

	private static function currentTemplate()
	{
		return end(self::$templates);
	}

	public static $units = [
		'%',
		'px',
		'pt',
		'em',
		'rem',
		'ch',
		'ex',
		'pc',
		'in',
		'cm',
		'mm',
		'vw',
		'vh',
		'vmin',
		'vmax',
	];

	public static function date($timestamp, $format = '', $doyestoday = 1, $adjust = 1)
	{
		/*
		 * It appears that in vB5 its not customary to pass the dateformat from the template so we load it here.

			Dates formatted in templates need to be told what format to	use and if today/yesterday/hours ago is to be used (if enabled)

			This function needs to accept most of vbdate's options if	we still allow the admin to dictate formats and we still
			use today/yesterday/hours ago in some places and not in others.
		 */

		if (!$format)
		{
			$format = vB5_Template_Options::instance()->get('options.dateformat');
		}

		// Timenow.
		if (strtolower($timestamp) == 'timenow')
		{
			$timestamp = time();
		}
		else
		{
			/* Note that negative timestamps are allowed in vB5 */
			$timestamp = intval($timestamp);
		}

		return self::vbdate($format, $timestamp, $doyestoday, true, $adjust);
	}

	public static function time($timestamp, $timeformat = '')
	{
		if (!$timeformat)
		{
			$timeformat = vB5_Template_Options::instance()->get('options.timeformat');
			$userLangLocale = vB5_User::get('lang_locale');
			if ($userLangLocale OR vB5_User::get('lang_timeoverride'))
			{
				$timeformat = vB5_User::get('lang_timeoverride');
			}
		}

		if (empty($timestamp))
		{
			$timestamp = 0;
		}

		return self::vbdate($timeformat, $timestamp, true);
	}

	public static function datetime($timestamp, $format = 'date, time', $formatdate = '', $formattime = '')
	{
		$options = vB5_Template_Options::instance();

		if (!$formatdate)
		{
			$formatdate = $options->get('options.dateformat');
		}

		if (!$formattime)
		{
			$formattime = $options->get('options.timeformat');
			$userLangLocale = vB5_User::get('lang_locale');
			if (($userLangLocale OR vB5_User::get('lang_timeoverride')))
			{
				$formattime = vB5_User::get('lang_timeoverride');
			}
		}

		// Timenow.
		$timenow = time();
		if (strtolower($timestamp) == 'timenow')
		{
			$timestamp = $timenow;
		}
		else
		{
			/* Note that negative
			timestamps are allowed in vB5 */
			$timestamp = intval($timestamp);
		}

		$date = self::vbdate($formatdate, $timestamp, true);
		if ($options->get('options.yestoday') == 2)
		{
			// Process detailed "Datestamp Display Option"
			// 'Detailed' will show times such as '1 Minute Ago', '1 Hour Ago', '1 Day Ago', and '1 Week Ago'.
			$timediff = $timenow - $timestamp;

			if ($timediff >= 0 AND $timediff < 3024000)
			{
				return $date;
			}
		}

		$time = self::vbdate($formattime, $timestamp, true);
		return str_replace(['date', 'time'], [$date, $time], $format);
	}

	public static function escapeJS($javascript)
	{
		return str_replace(['"', "'", "\n", "\r"], ['\"', "\'", ' ', ' '], $javascript);
	}

	public static function numberFormat($number, $decimals = 0)
	{
		return vb_number_format($number, $decimals);
	}

	public static function capNumber($number, $digitCap = 0, $doPlus = true)
	{
		$number = floatval($number);
		$digitCap = max($digitCap, 1);
		$floornumber = floor($number);
		if (strlen($floornumber) > $digitCap)
		{
			return str_repeat('9', $digitCap) . ($doPlus ? '+': '');
		}
		else
		{
			return $number;
		}
	}

	public static function urlEncode($text)
	{
		return urlencode($text);
	}

	public static function parsePhrase($phraseName)
	{
		$phrase = vB5_Template_Phrase::instance();

		//allow the first paramter to be a phrase array	( [$phraseName, $arg1, $arg2, ...]
		//otherwise the parameter is the phraseName and the args list is the phrase array
		//this allows us to pass phrase arrays around and use them directly without unpacking them
		//in the templates (which is both difficult and inefficient in the template code)
		if (is_array($phraseName))
		{
			return $phrase->register($phraseName);
		}
		else
		{
			return $phrase->register(func_get_args());
		}
	}

	// See vB_Template_Runtime for the full docblock
	private static function dechexpadded($dec)
	{
		$hex = dechex($dec);

		// zero pad for two-char hex numbers
		if (strlen($hex) < 2)
		{
			$hex = '0' . $hex;
		}

		return strtoupper($hex);
	}

	// See vB_Template_Runtime for the full docblock
	private static function convertColorFormat($input, $targetFormat)
	{
		// NOTE: There are 3 convertColorFormat() functions-- the two template
		// runtimes, and the JS version in class_stylevar.php Please keep all 3 in sync.

		$format = $red = $green = $blue = '';
		$alpha = '1';

		$len = !is_array($input) ? strlen($input) : 0;

		// array
		if (is_array($input))
		{
			$format = 'array';

			$red = $input['red'];
			$green = $input['green'];
			$blue = $input['blue'];
			$alpha = $input['alpha'];
		}
		// hex
		else if (substr($input, 0, 1) == '#' AND ($len == 4 OR $len == 7))
		{
			$format = 'hex';

			$hexVal = substr($input, 1);

			if (strlen($hexVal == 3))
			{
				$rr = substr($hexVal, 0, 1);
				$gg = substr($hexVal, 1, 1);
				$bb = substr($hexVal, 2, 1);
				$rr = $rr . $rr;
				$gg = $gg . $gg;
				$bb = $bb . $bb;
			}
			else
			{
				$rr = substr($hexVal, 0, 2);
				$gg = substr($hexVal, 2, 2);
				$bb = substr($hexVal, 4, 2);
			}

			$red = hexdec($rr);
			$green = hexdec($gg);
			$blue = hexdec($bb);
		}
		// rgb, rgba
		else if (preg_match('#(rgba?)\(([^)]+)\)#', $input, $matches))
		{
			$format = $matches[1];

			$values = explode(',', $matches[2]);

			$red = $values[0];
			$green = $values[1];
			$blue = $values[2];

			if ($matches[1] == 'rgba')
			{
				$alpha = $values[3];
			}
		}

		$returnValue = [];
		$returnValue['format'] = $format;

		switch ($targetFormat)
		{
			case 'array':
				$returnValue['value'] = [
					'red' => $red,
					'green' => $green,
					'blue' => $blue,
					'alpha' => $alpha,
				];
				break;

			case 'hex':
				$returnValue['value'] = '#' . self::dechexpadded($red) . self::dechexpadded($green) . self::dechexpadded($blue);
				break;

			case 'rgb':
				$returnValue['value'] = 'rgb(' . $red . ', ' . $green . ', ' . $blue . ')';
				break;

			case 'rgba':
				$returnValue['value'] = 'rgba(' . $red . ', ' . $green . ', ' . $blue . ', ' . $alpha . ')';
				break;

			default:
				throw new Exception('Unexpected color format in convertColorFormat(): ' . htmlspecialchars($targetFormat));
				break;
		}

		return $returnValue;
	}

	// See vB_Template_Runtime for the full docblock
	private static function getColorFormatInfo($originalValue)
	{
		// NOTE: There are 3 getColorFormatInfo() functions-- the two template
		// runtimes, and the JS version in class_stylevar.php Please keep all 3 in sync.

		$returnValue = [
			'originalValue' => $originalValue,
			'originalFormat' => '',
			'red' => '',
			'green' => '',
			'blue' => '',
			'alpha' => '',
		];

		$converted = self::convertColorFormat($originalValue, 'array');

		$returnValue['originalFormat'] = $converted['format'];
		$returnValue['red'] = $converted['value']['red'];
		$returnValue['green'] = $converted['value']['green'];
		$returnValue['blue'] = $converted['value']['blue'];
		$returnValue['alpha'] = $converted['value']['alpha'];

		return $returnValue;
	}

	// See vB_Template_Runtime for the full docblock
	private static function transformColor($or, $og, $ob, $oa, $ir, $ig, $ib, $ia, $p1, $p2, $p3, $p4)
	{
		// NOTE: There are 3 transformColor() functions-- the two template
		// runtimes, and the JS version in class_stylevar.php Please keep all 3 in sync.
		// Also keep transformColor and generateTransformParams in sync, as
		// they both depend on the same general algorithm.

		// I think I owe the reader an explanation of how the transformation works.
		//
		// The transformation parameter field contains something that looks like this:
		//
		// <source color>|<int red change>, <int green change>, <int blue change> [, <int alpha transparency change>]
		//
		// The 4 transformation parameters correspond to Red, Green, Blue, and Opactity values,
		// and are found after the vertical pipe. The fourth (opacity) is optional.
		//
		// The first 3 params are for colors. They are a positive or negative int value that
		// represents how far to move the inherited decimal color (0-255). If the inherited color
		// is 150 and the param is -20, then reduce the 150 by 20. A positive param (20) means move
		// toward the outer bound (either 0 or 255), and a negative param (-20) means move in
		// the opposite direction. The outer bound is either 0 or 255, whichever the current value
		// is closer to. So if the current color value is 60, then the outer bound is 0 and a param
		// of 25 would convert it to 35. If the param were -25, it would convert to 85. If the
		// current color is 210, then the outer bound would be 255 and a param of 25 (a positive
		// param) would convert it to 235.
		//
		// The 3 color params correspond (in order) to the red, green, and blue values of the
		// *original* inherited color. Original meaning the one in the MASTER_STYLE. However,
		// these are NOT necessarily matched up in order with the red, green, and blue values
		// of the inherited color that we want to transform. Instead they are matched in terms
		// of intensity. In other words, the highest of the three from the original inherited
		// color is matched with the highest of the current inherited colors and so forth.
		// So if the MASTER_STYLE has a light pink rgb(255, 230, 230), but the current inherited
		// color is a light blue rgb(200, 200, 255), and our three color transformation params
		// are -30, -50, -50. We would normally apply these and darken red by 30, while darkening
		// green and blue by 50. But since the current inherited color is now a light blue, we
		// match the -30 to the red value of the original, and the red value is matched to the
		// blue value of the current inherited color.  This means that even if the user changes
		// the inherited stylevar, we can still apply our transformation because it will keep the
		// same hue/color that the user specified, while still applying the darkening/lightening
		// that we have specified.
		//
		// The opacity value is a positive or negative float and will add or subtract from the
		// inherited opacity value. If the inherited value doesn't specify opacity (as with hex
		// or rgb colors), then it defaults to fully opaque 1.0. If the param introduces
		// transparencey (anything less than 100% opacity) that the inherited color doesn't have,
		// then the output will automatically be converted to rgba, otherwise the output tries
		// to match the input value. Opacity is obviously constrained to the range of 0.0 (completely
		// transparent) to 1.0 (completely opaque).

		// Match the original (MASTER_STYLE) r, g, b values to the corresponding
		// transform parameters 1, 2, 3 in order.
		$parts = [
			[
				'original' => $or,
				'transform' => $p1,
				'new' => '',
			],
			[
				'original' => $og,
				'transform' => $p2,
				'new' => '',
			],
			[
				'original' => $ob,
				'transform' => $p3,
				'new' => '',
			],
		];

		// Set up the inherited color parts, specifying which is red, green, and blue value
		$inheritedParts = [
			[
				'color' => 'red',
				'inherited' => $ir,
			],
			[
				'color' => 'green',
				'inherited' => $ig,
			],
			[
				'color' => 'blue',
				'inherited' => $ib,
			],
		];

		// Calculate what the deviation is from the mid point (128) for both the
		// orginal (MASTER_STYLE) and inherited values to determine which direction
		// the outer bound (0 or 255) is.
		foreach ($parts AS $key => $part)
		{
			// get the deviation from the midpoint, which is 128 for the range 0-255 (original)
			$parts[$key]['deviation'] = abs($part['original'] - 128);
		}

		foreach ($inheritedParts AS $key => $inheritedPart)
		{
			// get the deviation from the midpoint, which is 128 for the range 0-255 (inherited)
			$inheritedParts[$key]['inherited_deviation'] = abs($inheritedPart['inherited'] - 128);
			// get the direction that the color deviates in
			$inheritedParts[$key]['direction'] = $inheritedPart['inherited'] - 128 > 0 ? '+' : '-';
		}

		// Sort by deviation. This allows us to match the most intense color from the original
		// with the most intense color of the inherited, the second most intense with the
		// second most intense, etc.
		usort($parts, function ($a, $b)
		{
			if ($a['deviation'] == $b['deviation'])
			{
				return 0;
			}

			return $a['deviation'] > $b['deviation'] ? -1 : 1;
		});

		usort($inheritedParts, function ($a, $b)
		{
			if ($a['inherited_deviation'] == $b['inherited_deviation'])
			{
				return 0;
			}

			return $a['inherited_deviation'] > $b['inherited_deviation'] ? -1 : 1;
		});

		foreach (range(0, 2) AS $i)
		{
			$parts[$i]['color'] = $inheritedParts[$i]['color'];
			$parts[$i]['inherited'] = $inheritedParts[$i]['inherited'];
			$parts[$i]['inherited_deviation'] = $inheritedParts[$i]['inherited_deviation'];
			$parts[$i]['direction'] = $inheritedParts[$i]['direction'];
		}

		$returnValue = [];

		// Do the transformation
		foreach ($parts AS $key => $part)
		{
			if ($part['direction'] == '+')
			{
				$parts[$key]['transformedValue'] = $part['inherited'] + $part['transform'];
			}
			else
			{
				$parts[$key]['transformedValue'] = $part['inherited'] - $part['transform'];
			}

			$returnValue[$part['color']] = $parts[$key]['transformedValue'];
		}

		// ensure colors are in range (min:0, max:255)
		foreach ($returnValue AS $key => $value)
		{
			$returnValue[$key] = max(0, min(255, $value));
		}

		// handle alpha values if present
		if ($ia != '' AND $p4)
		{
			$returnValue['alpha'] = $ia + $p4;
			// constrain to a range of 0.0 to 1.0
			$returnValue['alpha'] = max(0, min(1, $returnValue['alpha']));
		}
		else
		{
			$returnValue['alpha'] = $ia;
		}

		return $returnValue;
	}

	// See vB_Template_Runtime for the full docblock
	private static function applyStylevarInheritanceParameters($inheritedValue, $inheritParameters)
	{
		// NOTE: There are 3 applyStylevarInheritanceParameters() functions-- the two template
		// runtimes, and the JS version in class_stylevar.php Please keep all 3 in sync.

		// NOTE: This function currently only applies to "color" stylevar properties
		// (color, border, and background)

		[$originalColor, $params] = explode('|', $inheritParameters);
		$params = explode(',', $params);
		$originalInfo = self::getColorFormatInfo($originalColor);
		$inheritedInfo = self::getColorFormatInfo($inheritedValue);

		if (empty($inheritedInfo['originalFormat']))
		{
			// return without applying any transformation because the
			// source format is an unexpected value
			return $inheritedValue;
		}

		// apply transformation
		$transformed = self::transformColor(
			$originalInfo['red'],
			$originalInfo['green'],
			$originalInfo['blue'],
			$originalInfo['alpha'],
			$inheritedInfo['red'],
			$inheritedInfo['green'],
			$inheritedInfo['blue'],
			$inheritedInfo['alpha'],
			$params[0],
			$params[1],
			$params[2],
			isset($params[3]) ? $params[3] : '1'
		);

		// format the color (back to the original if possible, or rgba if not 100% opaque)
		$colorFormat = $inheritedInfo['originalFormat'];
		if ($transformed['alpha'] < 1)
		{
			$colorFormat = 'rgba';
		}
		$converted = self::convertColorFormat($transformed, $colorFormat);

		return $converted['value'];
	}

	private static function outputStyleVar($base_stylevar, $parts = [], $withUnits = false)
	{
		//if we don't have a stylevar data array then no good will come of this.
		if (!$base_stylevar)
		{
			return '';
		}

		$stylevars = vB5_Template_Stylevar::instance();

		// apply stylevar inheritance
		$stylevar_value_prefix = 'stylevar_';
		foreach ($base_stylevar AS $key => $value)
		{
			if ($key == 'datatype' OR strpos($key, $stylevar_value_prefix) === 0 OR strpos($key, 'inherit_param_') === 0)
			{
				continue;
			}

			$stylevar_value_key = $stylevar_value_prefix . $key;
			if (empty($value) AND !empty($base_stylevar[$stylevar_value_key]))
			{
				// set the inherited value
				$base_stylevar[$key] = self::fetchStyleVar($base_stylevar[$stylevar_value_key]);

				// if the current part is a *color* part, apply the inheritance transformation params
				if (!empty($base_stylevar['inherit_param_' . $key]) AND substr($key, -5) == 'color')
				{
					$base_stylevar[$key] = self::applyStylevarInheritanceParameters($base_stylevar[$key], $base_stylevar['inherit_param_' . $key]);
				}
			}


			// Don't give access to the stylevars directly.
			unset($base_stylevar[$stylevar_value_key]);
		}

		// Set up the background gradient for "background" type stylevars for
		// both branches below
		if (isset($base_stylevar['datatype']) AND $base_stylevar['datatype'] == 'background')
		{
			foreach (['gradient_type', 'gradient_direction', 'gradient_start_color', 'gradient_mid_color', 'gradient_end_color'] AS $key)
			{
				if (!isset($base_stylevar[$key]))
				{
					$base_stylevar[$key] = '';
				}
			}

			$colorSteps = [];
			foreach (['gradient_start_color', 'gradient_mid_color', 'gradient_end_color'] AS $colorStep)
			{
				if (!empty($base_stylevar[$colorStep]))
				{
					$colorSteps[] = $base_stylevar[$colorStep];
				}
			}

			if (
				$base_stylevar['gradient_type'] AND
				$base_stylevar['gradient_direction'] AND
				count($colorSteps) >= 2
			)
			{
				$base_stylevar['gradient'] = $base_stylevar['gradient_type'] . '(' .
					$base_stylevar['gradient_direction'] . ', ' .
					implode(', ', $colorSteps) . ')';
			}
			else
			{
				$base_stylevar['gradient'] = '';
			}
		}


		// Output a stylevar *part*, for example, myBackgroundStylevar.backgroundImage
		if (isset($parts[1]))
		{
			$types = [
				'background' => [
					'backgroundColor' => 'color',
					'backgroundImage' => 'image',
					'backgroundRepeat' => 'repeat',
					'backgroundPositionX' => 'x',
					'backgroundPositionY' => 'y',
					'backgroundPositionUnits' => 'units',
					'backgroundGradient' => 'gradient',
					// make short names valid too
					'color' => 'color',
					'image' => 'image',
					'repeat' => 'repeat',
					'x' => 'x',
					'y' => 'y',
					'units' => 'units',
					'gradient' => 'gradient',
					'gradient_type' => 'gradient_type',
					'gradient_direction' => 'gradient_direction',
					'gradient_start_color' => 'gradient_start_color',
					'gradient_mid_color' => 'gradient_mid_color',
					'gradient_end_color' => 'gradient_end_color',
				],

				'font' => [
					'fontWeight' => 'weight',
					'units' => 'units',
					'fontSize' => 'size',
					'lineHeight' => 'lineheight',
					'fontFamily' => 'family',
					'fontStyle' => 'style',
					'fontVariant' => 'variant',
					// make short names valid too
					'weight' => 'weight',
					'size' => 'size',
					'lineheight' => 'lineheight',
					'family' => 'family',
					'style' => 'style',
					'variant' => 'variant',
				],

				'padding' => [
					'units' => 'units',
					'paddingTop' => 'top',
					'paddingRight' => 'right',
					'paddingBottom' => 'bottom',
					'paddingLeft' => 'left',
					// make short names valid too
					'top' => 'top',
					'right' => 'right',
					'bottom' => 'bottom',
					'left' => 'left',
				],

				'margin' => [
					'units' => 'units',
					'marginTop' => 'top',
					'marginRight' => 'right',
					'marginBottom' => 'bottom',
					'marginLeft' => 'left',
					// make short names valid too
					'top' => 'top',
					'right' => 'right',
					'bottom' => 'bottom',
					'left' => 'left',
				],

				'border' => [
					'borderStyle' => 'style',
					'units' => 'units',
					'borderWidth' => 'width',
					'borderColor' => 'color',
					// make short names valid too
					'style' => 'style',
					'width' => 'width',
					'color' => 'color',
				],
			];

			//handle is same for margin and padding -- allows the top value to be
			//used for all padding values
			if (in_array($base_stylevar['datatype'], ['padding', 'margin']) AND $parts[1] <> 'units')
			{
				if (isset($base_stylevar['same']) AND $base_stylevar['same'])
				{
					$parts[1] = $base_stylevar['datatype'] . 'Top';
				}
			}

			if (isset($types[$base_stylevar['datatype']]))
			{
				$mapping = $types[$base_stylevar['datatype']][$parts[1]];
				// If a particular stylevar has not been updated since a new "part" was
				// added to its stylevar type, it won't have the array element here. For
				// this reason, check if the array element exists before accessing it.
				// Eg. the 'lineheight' value that was added to the Font stylevar type.
				$output = $base_stylevar[$mapping] ?? '';
			}
			else
			{
				$output = $base_stylevar;
				for ($i = 1; $i < sizeof($parts); $i++)
				{
					$output = $output[$parts[$i]];
				}
			}

			// add units if required
			if ($withUnits)
			{
				// default to px
				$output .= !empty($base_stylevar['units']) ? $base_stylevar['units'] : 'px';
			}
		}
		// Output the full/combined value of a stylevar
		else
		{
			$output = '';

			switch($base_stylevar['datatype'])
			{
				case 'color':
					$output = $base_stylevar['color'];
					break;

				case 'background':
					$base_stylevar['x'] = !empty($base_stylevar['x']) ? $base_stylevar['x'] : '0';
					$base_stylevar['y'] = !empty($base_stylevar['y']) ? $base_stylevar['y'] : '0';
					$base_stylevar['repeat'] = !empty($base_stylevar['repeat']) ? $base_stylevar['repeat'] : '';
					$base_stylevar['units'] = !empty($base_stylevar['units']) ? $base_stylevar['units'] : '';
					switch ($base_stylevar['x'])
					{
						case 'stylevar-left':
							$base_stylevar['x'] = $stylevars->get('left.string');
							break;
						case 'stylevar-right':
							$base_stylevar['x'] = $stylevars->get('right.string');
							break;
						default:
							$base_stylevar['x'] = $base_stylevar['x'] . $base_stylevar['units'];
							break;
					}
					// The order of the background layers is important. Color is lowest,
					// then gradient, then the image is the topmost layer.
					// Keep syncronized with the other runtime outputStyleVar() implementation
					// and the previewBackground() Javascript funtion in class_stylevar.php
					$backgroundLayers = [];
					$backgroundLayers[] = (!empty($base_stylevar['image']) ? "$base_stylevar[image]" : 'none') . ' ' .
						$base_stylevar['repeat'] . ' ' . $base_stylevar['x'] . ' ' .
						$base_stylevar['y'] .
						$base_stylevar['units'];
					if (!empty($base_stylevar['gradient']))
					{
						$backgroundLayers[] = $base_stylevar['gradient'];
					}
					if (!empty($base_stylevar['color']))
					{
						$backgroundLayers[] = $base_stylevar['color'];
					}
					$output = implode(', ', $backgroundLayers);
					break;

				case 'textdecoration':
					if ($base_stylevar['none'])
					{
						$output = 'none';
					}
					else
					{
						unset($base_stylevar['datatype'], $base_stylevar['none']);
						$output = implode(' ', array_keys(array_filter($base_stylevar)));
					}
					break;

				case 'texttransform':
					$output = !empty($base_stylevar['texttransform']) ? $base_stylevar['texttransform'] : 'none';
					break;

				case 'textalign':
					// Default to left and not inherit or initial because the select menu in the stylevar editor
					// defaults to left (the first option). If they create the stylevar, see it's set to left and
					// don't edit it to actually save the value, we'll have an empty value here and should use left.
					$output = !empty($base_stylevar['textalign']) ? $base_stylevar['textalign'] : 'left';
					// if it's left/right, use the left/right stylevar value,
					// which changes to the opposite in RTL. See VBV-15458.
					if ($output == 'left')
					{
						$output = $stylevars->get('left.string');
					}
					else if ($output == 'right')
					{
						$output = $stylevars->get('right.string');
					}
					break;

				case 'font':
					$fontSizeKeywords = [
						'xx-small',
						'x-small',
						'small',
						'medium',
						'large',
						'x-large',
						'xx-large',
						'smaller',
						'larger',
						'initial',
						'inherit',
					];
					$fontSize = $base_stylevar['size'];
					if (!in_array($fontSize, $fontSizeKeywords, true))
					{
						$fontSize .= $base_stylevar['units'];
					}
					$fontLineHeight = !empty($base_stylevar['lineheight']) ? '/' . $base_stylevar['lineheight'] : '';
					$output = $base_stylevar['style'] . ' ' .
						$base_stylevar['variant'] . ' ' .
						$base_stylevar['weight'] . ' ' .
						$fontSize .
						$fontLineHeight . ' ' .
						$base_stylevar['family'];
					break;

				case 'imagedir':
					$output = $base_stylevar['imagedir'];
					break;

				case 'string':
					$output = $base_stylevar['string'];
					break;

				case 'numeric':
					$output = $base_stylevar['numeric'];
					break;

				case 'size':
					$output = $base_stylevar['size'] . $base_stylevar['units'];
					break;

				case 'boolean':
					$output = $base_stylevar['boolean'];
					break;

				case 'url':
					if (filter_var($base_stylevar['url'], FILTER_VALIDATE_URL))
					{
						$output = $base_stylevar['url'];
					}
					else
					{
						// Assume that the url is relative url
						$output = $base_stylevar['url'];
					}
					break;

				case 'path':
					$output = $base_stylevar['path'];
					break;

				case 'fontlist':
					$output = implode(',', preg_split('/[\r\n]+/', trim($base_stylevar['fontlist']), -1, PREG_SPLIT_NO_EMPTY));
					break;

				case 'border':
					$output = $base_stylevar['width'] . $base_stylevar['units'] . ' ' .
						$base_stylevar['style'] . ' ' . $base_stylevar['color'];
					break;

				case 'dimension':
					$output = 'width: ' . intval($base_stylevar['width'])  . $base_stylevar['units'] .
						'; height: ' . intval($base_stylevar['height']) . $base_stylevar['units'] . ';';
					break;

				case 'padding':
				case 'margin':
					foreach (['top', 'right', 'bottom', 'left'] AS $side)
					{
						if (isset($base_stylevar[$side]) AND $base_stylevar[$side] != 'auto')
						{
							$base_stylevar[$side] = $base_stylevar[$side] . $base_stylevar['units'];
						}
					}
					if (isset($base_stylevar['same']) AND $base_stylevar['same'])
					{
						$output = $base_stylevar['top'];
					}
					else
					{
						if (self::fetchStyleVar('textdirection') == 'ltr')
						{
							$output = $base_stylevar['top'] . ' ' . $base_stylevar['right'] . ' ' . $base_stylevar['bottom'] . ' ' . $base_stylevar['left'];
						}
						else
						{
							$output = $base_stylevar['top'] . ' ' . $base_stylevar['left'] . ' ' . $base_stylevar['bottom'] . ' ' . $base_stylevar['right'];
						}
					}
					break;
			}
		}

		return $output;
	}

	public static function fetchStyleVar($stylevar, $withUnits = false)
	{
		$parts = explode('.', $stylevar);

		return self::outputStyleVar(vB5_Template_Stylevar::instance()->get($parts[0]), $parts, $withUnits);
	}

	public static function fetchCustomStylevar($stylevar, $user = false)
	{
		$parts = explode('.', $stylevar);
		$api = Api_InterfaceAbstract::instance();

		// get user info for the currently logged in user
		$customstylevar  = $api->callApi('stylevar', 'get', [$parts[0], $user]);
		//$customstylevar = vB_Api::instanceInternal('stylevar')->get($parts[0], $user);
		return self::outputStyleVar($customstylevar[$parts[0]], $parts);
	}

	public static function runMaths($str)
	{
		//this would usually be dangerous, but none of the units make sense
		//in a math string anyway.  Note that there is ambiguty between the '%'
		//unit and the modulo operator.  We don't allow the latter anyway
		//(though we do allow bitwise operations !?)

		$units_found = [];
		foreach (self::$units AS $unit)
		{
			//this assumes that we don't have any unicode characters in our math strings
			//if that's a problem we'll need to work in unicode mode which *should* be
			//supported.  But give that we should be dealing with numbers and css units
			//it shouldn't be necesary.
			$re = '#(?:^|[^a-zA-Z])' . preg_quote($unit, '#') . '(?:$|[^a-zA-Z])#';
			if (preg_match($re, $str))
			{
				$units_found[] = $unit;
			}
		}

		//mixed units.
		if (count($units_found) > 1)
		{
			return "/* ~~cannot perform math on mixed units ~~ found (" .
			implode(",", $units_found) . ") in $str */";
		}

		$str = preg_replace('#([^+\-*=/\(\)\d\^<>&|\.]*)#', '', $str);

		if (empty($str))
		{
			$str = '0';
		}
		else
		{
			//hack: if the math string is invalid we can get a php parse error here.
			//a bad expression or even a bad variable value (blank instead of a number) can
			//cause this to occur.  This fails quietly, but also sets the status code to 500
			//(but, due to a bug in php only if display_errors is *off* -- if display errors
			//is on, then it will work just fine only $str below will not be set.
			//
			//This can result is say an almost correct css file being ignored by the browser
			//for reasons that aren't clear (and goes away if you turn error reporting on).
			//We can check to see if eval hit a parse error and, if so, we'll attempt to
			//clear the 500 status (this does more harm then good) and send an error
			//to the file.  Since math is mostly used in css, we'll provide error text
			//that works best with that.
			try
			{
				$status = @eval("\$str = $str;");
			}
			catch(Error $e)
			{
				$status = false;
			}

			if ($status === false)
			{
				if (!headers_sent())
				{
					http_response_code(200);
				}
				return "/* Invalid math expression */";
			}

			if (count($units_found) == 1)
			{
				$str = $str . $units_found[0];
			}
		}

		return $str;
	}

	public static function parseData()
	{
		$arguments = func_get_args();
		$controller = array_shift($arguments);
		$method = array_shift($arguments);

		$api = Api_InterfaceAbstract::instance();
		$result = $api->callApi($controller, $method, $arguments, false, true);

		if (is_array($result) AND isset($result['errors']))
		{
			throw new vB5_Exception_Api($controller, $method, $arguments, $result['errors']);
		}

		return $result;
	}

	public static function parseDataWithErrors()
	{
		$arguments = func_get_args();
		$controller = array_shift($arguments);
		$method = array_shift($arguments);

		$api = Api_InterfaceAbstract::instance();
		$result = $api->callApi($controller, $method, $arguments);

		return $result;
	}

	public static function parseAction($controller, $method, ...$arguments)
	{
		$controller = str_replace(':', '.', $controller);
		$class = vB5_Frontend_Routing::getControllerClassFromName($controller);
		if (!class_exists($class) || !method_exists($class, $method))
		{
			return null;
		}

		$c = new $class();
		$result = call_user_func_array([$c, $method], $arguments);

		return $result;
	}

	public static function parseJSON($searchJSON, $arguments)
	{
		$search_structure = json_decode($searchJSON, true);
		if (empty($search_structure))
		{
			return "{}";
		}

		$all_arguments = [];
		foreach ($arguments as $argument)
		{
			if (!is_array($argument))
			{
				continue;
			}

			// Stick widgetConfig.module_filter_node into searchJSON.
			if (isset($argument['module_filter_nodes']) AND !isset($search_structure['filterChannels']))
			{
				// Special handling for dynamically replacing "current channel". If channelid exists
				// (e.g. from page var), replaceJSON() will dynamically set this.
				// The search criteria receives this in add_inc_exc_channel_filter().
				$argument['module_filter_nodes']['currentChannelid'] = ['param' => 'channelid'];

				$search_structure['module_filter_nodes'] = $argument['module_filter_nodes'];
			}

			$all_arguments = array_merge($argument, $all_arguments);
		}
		$search_structure = self::replaceJSON($search_structure, $all_arguments);
		return json_encode($search_structure);
	}

	protected static function replaceJSON($search_structure, $all_arguments)
	{
		foreach ($search_structure AS $filter => $value)
		{
			if (is_array($value))
			{
				if (array_key_exists("param", $value))
				{
					$param_name = $value['param'];
					$param_value = null;
					if (array_key_exists($param_name, $all_arguments))
					{
						$search_structure[$filter] = (string) $all_arguments[$param_name];
					}
					else
					{
						unset($search_structure[$filter]);
						// re-indexing an indexed array so it won't be considered associative
						if (is_numeric($filter))
						{
							$search_structure = array_values($search_structure);
						}
					}
				}
				else
				{
					$val = self::replaceJSON($value, $all_arguments);
					if ($val === null)
					{
						unset($search_structure[$filter]);
					}
					else
					{
						$search_structure[$filter] = $val;
					}
				}
			}
		}
		if (empty($search_structure))
		{
			$search_structure = null;
		}

		return $search_structure;
	}

	public static function includeTemplate()
	{
		$arguments = func_get_args();

		$template_id = array_shift($arguments);
		$args = array_shift($arguments);

		$cache = vB5_Template_Cache::instance();

		return $cache->register($template_id, $args);
	}

	public static function includeJs()
	{
		$scripts = func_get_args();

		if (!empty($scripts) AND ($scripts[0] == 'insert_here' OR $scripts[0] == '1'))
		{
			$scripts = array_slice($scripts, 1);
			if (!empty($scripts))
			{
				$javascript = vB5_Template_Javascript::instance();
				$rendered =  $javascript->insertJsInclude($scripts);
				return $rendered;
			}

			return '';
		}

		$javascript = vB5_Template_Javascript::instance();

		return $javascript->register($scripts);
	}

	public static function includeHeadLink()
	{
		$link = func_get_args();
		$headlink = vB5_Template_Headlink::instance();

		return $headlink->register(array_shift($link));
	}

	public static function includeCss()
	{
		$stylesheets = func_get_args();
		foreach ($stylesheets AS $key => $stylesheet)
		{
			//For when we remove a record per below
			if (empty($stylesheet))
			{
				unset($stylesheets[$key]);
				continue;
			}

			if ((substr($stylesheet, -7, 7) == 'userid=' ))
			{
				// This is apparently because currently,
				//   {vb:cssExtra css_profile.css&userid={vb:raw page.userid}&showusercss={vb:raw showusercss}}
				// gets compiled into something like
				//   ...includeCss('css_profile.css&userid=', ($page['userid'] ?? null), '&showusercss=', ($showusercss ?? null))
				// so we have to combine the "next parameters" back into a single string.. this seems problematic but is not
				// very tractable atm.
				if (($key < count($stylesheets) - 1) AND (is_numeric($stylesheets[$key + 1])))
				{
					$stylesheets[$key] .= $stylesheets[$key + 1];
					unset($stylesheets[$key + 1]);
				}
				if (isset($stylesheets[$key + 2]) AND isset($stylesheets[$key + 3]) AND
					($stylesheets[$key + 2] == '&showusercss=') OR ($stylesheets[$key + 2] == '&amp;showusercss='))
				{
					$stylesheets[$key] .= $stylesheets[$key + 2] . $stylesheets[$key + 3];
					unset($stylesheets[$key + 2]);
					unset($stylesheets[$key + 3]);
				}
			}
		}
		$stylesheet = vB5_Template_Stylesheet::instance();

		return $stylesheet->register($stylesheets);
	}

	public static function includeCssFile()
	{
		$stylesheets = func_get_args();
		$stylesheet = vB5_Template_Stylesheet::instance();

		return $stylesheet->getCssFile($stylesheets[0]);
	}

	/**
	 * This is a no-op here, and is implemented in the core vB_Template_Runtime,
	 * since CSS is rendered in the back end via css.php, and the {vb:spritepath}
	 * tag is only used in CSS files.
	 */
	public static function includeSpriteFile($filename)
	{
		$api = Api_InterfaceAbstract::instance();

		$styleid = self::fetchStyleVar('styleid');
		$textdirection = self::fetchStyleVar('textdirection');

		$url = $api->callApi('style', 'getSpriteUrl', [$filename, $styleid, $textdirection]);
		if (isset($result['errors']))
		{
			throw new Exception('Failed to load sprite');
		}

		return $url['url'];
	}

	public static function doRedirect($url, $bypasswhitelist = false)
	{
		$application = vB5_ApplicationAbstract::instance();
		if (!$bypasswhitelist AND !$application->allowRedirectToUrl($url))
		{
			throw new vB5_Exception('invalid_redirect_url');
		}

		if (vB5_Request::get('useEarlyFlush'))
		{
			echo '<script type="text/javascript">window.location = "' . $url . '";</script>';
		}
		else
		{
			header('Location: ' . $url);
		}

		die();
	}

	private static function setLocale($locale)
	{
		setlocale(LC_TIME, $locale);
		if (substr($locale, 0, 5) != 'tr_TR')
		{
			setlocale(LC_CTYPE, $locale);
		}
	}

	/**
	 * Formats a UNIX timestamp into a human-readable string according to vBulletin prefs
	 *
	 * Note: Ifvbdate() is called with a date format other than than one in $vbulletin->options[],
	 * set $locale to false unless you dynamically set the date() and strftime() formats in the vbdate() call.
	 *
	 * @param	string	Date format string (same syntax as PHP's date() function). It also supports the following vB specific date/time format:
	 *                  'registered' - Format For Registration Date
	 *                  'cal1' - Format For Birthdays with Year Specified
	 *                  'cal2' - Format For Birthdays with Year Unspecified
	 *                  'event' - Format event start date in the upcoming events module
	 *                  'log' - Log Date Format
	 * @param	integer	Unix time stamp
	 * @param	boolean	If true, attempt to show strings like "Yesterday, 12pm" instead of full date string
	 * @param	boolean	If true, and user has a language locale, use strftime() to generate language specific dates
	 * @param	boolean	If true, don't adjust time to user's adjusted time .. (think gmdate instead of date!)
	 * @param	boolean	If true, uses gmstrftime() and gmdate() instead of strftime() and date()
	 *
	 * @return	string	Formatted date string
	 */

	protected static function vbdate($format, $timestamp = 0, $doyestoday = false, $locale = true, $adjust = true, $gmdate = false)
	{
		$timenow = time();
		if (!$timestamp)
		{
			$timestamp = $timenow;
		}

		$options = vB5_Template_Options::instance();

		$uselocale = false;

		// TODO: use vB_Api_User::fetchTimeOffset() for maintainnability...
		$timezone = vB5_User::get('timezoneoffset');
		if (vB5_User::get('dstonoff') || (vB5_User::get('dstauto') AND $options->get('options.dstonoff')))
		{
			// DST is on, add an hour
			$timezone++;
		}
		$hourdiff = (date('Z', time()) / 3600 - $timezone) * 3600;

		if (vB5_User::get('lang_locale'))
		{
			$userLangLocale = vB5_User::get('lang_locale');
		}

		if (!empty($userLangLocale))
		{
			$uselocale = true;
			$currentlocale = setlocale(LC_TIME, 0);
			self::setLocale($userLangLocale);
		}

		if ($uselocale AND $locale)
		{
			if ($gmdate)
			{
				$datefunc = 'gmstrftime';
			}
			else
			{
				$datefunc = 'strftime';
			}
		}
		else
		{
			if ($gmdate)
			{
				$datefunc = 'gmdate';
			}
			else
			{
				$datefunc = 'date';
			}
		}

		// vB Specified format
		switch ($format)
		{
			case 'registered':
				if (($uselocale OR vB5_User::get('lang_registereddateoverride')) AND $locale)
				{
					$format = vB5_User::get('lang_registereddateoverride');
				}
				else
				{
					$format = $options->get('options.registereddateformat');
				}
				break;

			case 'cal1':
				if (($uselocale OR vB5_User::get('lang_calformat1override')) AND $locale)
				{
					$format = vB5_User::get('lang_calformat1override');
				}
				else
				{
					$format = $options->get('options.calformat1');
				}
				break;

			case 'cal2':
				if (($uselocale OR vB5_User::get('lang_calformat2override')) AND $locale)
				{
					$format = vB5_User::get('lang_calformat2override');
				}
				else
				{
					$format = $options->get('options.calformat2');
				}
				break;

			case 'event':
				if (($uselocale OR vB5_User::get('lang_eventdateformatoverride')) AND $locale)
				{
					$format = vB5_User::get('lang_eventdateformatoverride');
				}
				else
				{
					$format = $options->get('options.eventdateformat');
				}
				break;

			// NOTE: We don't handle the lang_pickerdateformatoverride item here,
			// since it is only used by flatpickr, and not by any template {vb:date} calls
			// AND since the format tokens are specific to flatpickr, not PHP's date or strftime.

			case 'log':
				if (($uselocale OR vB5_User::get('lang_logdateoverride')) AND $locale)
				{
					$format = vB5_User::get('lang_logdateoverride');
				}
				else
				{
					$format = $options->get('options.logdateformat');
				}
				break;

			case 'full':
				//Hack: This whole function needs to be taken out an shot.
				//We simply want a way to display the entire datetime in the ISO 8601 that is used as an alternative
				//machine readable format.  The exact timezone we render to isn't very important
				//since the time should be adjusted and the TZ offset is part of the format.
				//Until we can refactor the time/date/timezone settings to get to sanity we're stuck with this approach.
				$format = 'c';
				$useFormat = 'c';
				$doyestoday = false;
				$locale = false;
				$adjust = false;
			 	$gmdate = false;
				$datefunc = 'date';
				break;
		}

		if (!$adjust)
		{
			$hourdiff = 0;
		}

		if ($timestamp < 0)
		{
			$timestamp_adjusted = $timestamp;
		}
		else
		{
			$timestamp_adjusted = max(0, $timestamp - $hourdiff);
		}

		/*
			Special handling for some formats we only allow internally
		 */
		$returnEarly = null;
		if ($format === 'U' OR $format === '%s')
		{
			/*
				date('U') or strftime('%s') is basically shorthand for time(),
				except if a timestamp is provided it should return that.
				It seems we use the U format to perform TZ adjustments on the
				timestamp to do some internal calculations/comparisons.
				However, the old logic would try to swap to strftime('U') which
				would not work. Ideally we could pivot to strftime('%s') in that
				case, but on certain OSes (Windows), strftime('%s') does not work
				at all while on others strftime('%s') is bugged
				(https://stackoverflow.com/a/55503017).
				As such this is special logic to basically funnel the template
				usage of 'U' to the expected old behavior while fixing issues
				that this had when language locale overrides were in effect
				(which just broke these calculations previously).
			 */

			if ($gmdate)
			{
				$datefunc = 'gmdate';
			}
			else
			{
				$datefunc = 'date';
			}

			$returnEarly = $datefunc('U', $timestamp_adjusted);
		}
		else if ($format == 'r')
		{
			// This is used by debug_info template
			// The strftime equivalent to r would be
			// '%a, %d %b %Y %T %z' but %z support is fishy for Windows
			// Force date.
			$returnEarly = date('r', $timestamp_adjusted);
		}

		if (!is_null($returnEarly))
		{
			// undo any locale changes before returning early.
			if (!empty($userLangLocale))
			{
				self::setLocale($currentlocale);
			}

			return $returnEarly;
		}

		if ($format == $options->get('options.dateformat') AND ($uselocale OR vB5_User::get('lang_dateoverride')) AND $locale)
		{
			$format = vB5_User::get('lang_dateoverride');
		}

		if (!$uselocale AND $format == vB5_User::get('lang_dateoverride'))
		{
			if ($gmdate)
			{
				$datefunc = 'gmstrftime';
			}
			else
			{
				$datefunc = 'strftime';
			}
		}
		if (!$uselocale AND $format == vB5_User::get('lang_timeoverride'))
		{
			if ($gmdate)
			{
				$datefunc = 'gmstrftime';
			}
			else
			{
				$datefunc = 'strftime';
			}
		}

		// Convert the current date (or strftime, we don't know) format to the proper one
		// for $datefunc.
		// Not overriding because the original format string may be
		// used for checking against options etc. In the future,
		// we may want to convert the $format to the canonical then compare??
		$useFormat ??= self::convertFormat($datefunc, $format);

		if (($format == $options->get('options.dateformat') OR $format == vB5_User::get('lang_dateoverride')) AND $doyestoday AND $options->get('options.yestoday'))
		{
			if ($options->get('options.yestoday') == 1)
			{
				if (!defined('TODAYDATE'))
				{
					define('TODAYDATE', self::vbdate('n-j-Y', $timenow, false, false));
					define('YESTDATE', self::vbdate('n-j-Y', $timenow - 86400, false, false));
					define('TOMDATE', self::vbdate('n-j-Y', $timenow + 86400, false, false));
				}

				$datetest = @date('n-j-Y', $timestamp - $hourdiff);

				if ($datetest == TODAYDATE)
				{
					$returndate = self::parsePhrase('today');
				}
				else if ($datetest == YESTDATE)
				{
					$returndate = self::parsePhrase('yesterday');
				}
				else
				{
					$returndate = $datefunc($useFormat, $timestamp_adjusted);
				}
			}
			else
			{
				$timediff = $timenow - $timestamp;

				if ($timediff >= 0)
				{
					if ($timediff < 120)
					{
						$returndate = self::parsePhrase('1_minute_ago');
					}
					else if ($timediff < 3600)
					{
						$returndate = self::parsePhrase('x_minutes_ago', intval($timediff / 60));
					}
					else if ($timediff < 7200)
					{
						$returndate = self::parsePhrase('1_hour_ago');
					}
					else if ($timediff < 86400)
					{
						$returndate = self::parsePhrase('x_hours_ago', intval($timediff / 3600));
					}
					else if ($timediff < 172800)
					{
						$returndate = self::parsePhrase('1_day_ago');
					}
					else if ($timediff < 604800)
					{
						$returndate = self::parsePhrase('x_days_ago', intval($timediff / 86400));
					}
					else if ($timediff < 1209600)
					{
						$returndate = self::parsePhrase('1_week_ago');
					}
					else if ($timediff < 3024000)
					{
						$returndate = self::parsePhrase('x_weeks_ago', intval($timediff / 604900));
					}
					else
					{
						$returndate = $datefunc($useFormat, $timestamp_adjusted);
					}
				}
				else
				{
					$returndate = $datefunc($useFormat, $timestamp_adjusted);
				}
			}
		}
		else
		{
			$returndate = $datefunc($useFormat, $timestamp_adjusted);
		}

		if (!empty($userLangLocale))
		{
			self::setLocale($currentlocale);
		}

		return $returndate;
	}

	private static function convertFormat($datefunc, $format)
	{
		if (is_null(self::$dateUtil))
		{
			self::$dateUtil = new vB_Utility_Date();
		}

		return self::$dateUtil->convertFormat($datefunc, $format);
	}

	public static function buildUrlAdmincpTemp($route, array $parameters = [])
	{
		$config = vB5_Config::instance();

		static $baseurl = null;
		if ($baseurl === null)
		{
			$baseurl = vB5_Template_Options::instance()->get('options.frontendurl');
		}

		// @todo: this might need to be a setting
		$admincp_directory = 'admincp';

		// @todo: This would be either index.php or empty, depending on use of mod_rewrite
		$index_file = 'index.php';

		$url = "$baseurl/$admincp_directory/$index_file";

		if (!empty($route))
		{
			$url .= '/' . htmlspecialchars($route);
		}
		if (!empty($parameters))
		{
			$url .= '?' . http_build_query($parameters, '', '&amp;');
		}

		return $url;
	}

	/**
	 * Returns the URL for a route with the passed parameters
	 * @param mixed $route - Route identifier (routeid or name)
	 * @param array $data - Data for building route
	 * @param array $extra - Additional data to be added
	 * @param array $options - Options for building URL
	 *					- noBaseUrl: skips adding the baseurl
	 *					- anchor: anchor id to be added
	 * @return type
	 * @throws vB5_Exception_Api
	 */
	public static function buildUrl($route, $data = [], $extra = [], $options = [])
	{
		return vB5_Template_Url::instance()->register($route, $data, $extra, $options);
	}

	public static function hook($hookName, $vars = [])
	{
		$hooks = Api_InterfaceAbstract::instance()->callApi('template','fetchTemplateHooks', ['hookName' => $hookName]);

		$placeHolders = '';
		if ($hooks)
		{
			foreach ($hooks AS $templates)
			{
				foreach ($templates AS $template => $arguments)
				{
					$passed = self::buildVars($arguments, $vars);
					$placeHolders .= self::includeTemplate($template, $passed) . "\r\n";
				}
			}

			unset($vars);
		}

		// Check whether or not we should show the hook positions and the "add hook" links, but only once
		// per page load.
		static $showhookposition, $addhooklink;
		if (is_null($showhookposition))
		{
			$showhookposition = vB5_Template_Options::instance()->get('options.showhookposition');
			$addhooklink = false;
			if ($showhookposition)
			{
				$showhooklinkOption = vB5_Template_Options::instance()->get('options.showhooklink');
				switch ($showhooklinkOption)
				{
					case 2:
						$addhooklink = true;
						break;
					case 1: // show to can admin products
						$userContext = vB::getUserContext();
						$addhooklink = $userContext->hasAdminPermission('canadminproducts');
						break;
					case 0:
					default:
						// do not show the links
						break;
				}
			}
		}

		if ($showhookposition)
		{
			$htmlSafeHookName = htmlentities($hookName);

			$placeHolders = "<!-- BEGIN_HOOK: $htmlSafeHookName -->" . $placeHolders . "<!-- END_HOOK: $htmlSafeHookName -->";
			if ($addhooklink)
			{
				$placeHolders = "
						<div>
						<a class=\"debug-hook-info\" href=\"admincp/hook.php?do=add&hookname=" . htmlentities(urlencode($hookName)). "\" title=\"$htmlSafeHookName\">
							ADD HOOK ($hookName)
						</a>
						</div>
					" . $placeHolders;
			}
		}

		return $placeHolders;
	}

	public static function buildVars($select, &$master)
	{
		$args = [];

		foreach ($select AS $argname => $argval)
		{
			$result = [];

			foreach ($argval AS $varname => $value)
			{
				if (is_array($value))
				{
					self::nextLevel($result, $value, $master[$varname]);
				}
				else
				{
					$result = $master[$varname];
				}
			}

			$args[$argname] = $result;
		}

		return $args;
	}

	public static function nextLevel(&$res, $array, &$master)
	{
		foreach ($array AS $varname => $value)
		{
			if (is_array($value))
			{
				self::nextLevel($res, $value, $master[$varname]);
			}
			else
			{
				$res = $master[$varname];
			}
		}
	}

	public static function vBVar($value)
	{
		return vB5_String::htmlSpecialCharsUni($value);
	}

	/**
	 * Display schema data if schemas are enabled.
	 *
	 * This has mutltiple forms (to avoid needing multiple curly tags to handle it) triggered
	 * off the first parameter.  Cases
	 *
	 * Array -- Render elements based on the array and an approved element list.  This use takes no additional
	 * 		parameters and is deprecated.
	 * 'itemtype' -- Defines an element scope based on the type.  This will add the itemscope attribute (which
	 * 		has no value) and the itemtype attribute with the value given by the second parameter.  This is either
	 * 		a fully qualified url to the schema defintion or a schema.org type (which will render as the fully
	 * 		qualified url for that type -- the type is not validated and invalid urls will be produced for
	 * 		invalid types).
	 * 'itemprop' -- Defines an element as a property.  Adds an itemprop attribute with the value of the second
	 * 		parameter.  Optionally allows the type to be specified for compound types which acts as if the
	 * 		function were called for the itemprop and then itemptype.
	 * 	'meta' -- Defines a meta schema element.  Adds a meta tag with the propname given by the second parameter
	 * 		and value given by the third.  This is used to add schema information independant of the markup.
	 *
	 *	If an invalid sequence of parameters is given or schemas are disabled in settings then this returns
	 *	an empty string.
	 *
	 * @param array|string $arg1 -- Either the array or the type of schema element rendered
	 * @param mixed $args -- variadic param list
	 * @return string
	 */
	public static function parseSchema($arg1, ...$args)
	{
		//if for any reason we don't like what we are passed we simply return nothing.
		//this isn't worth throwing an error over.
		if (!vB5_Template_Options::instance()->get('options.schemaenabled'))
		{
			return '';
		}

		//param as array -- this is deprecated
		if (is_array($arg1))
		{
			if (!count($arg1) OR count($args) != 0)
			{
				return '';
			}

			return self::parseSchemaArray($arg1);
		}
		//schema notation -- mark something as the container (with type) or as a property
		else
		{
			$argcounts = [
				'itemprop' => [1,2],
				'itemtype' => [1,1],
				'meta' => [2,2],
			];

			//default to impossible values so we'll fail the check.
			$argcounts = $argcounts[$arg1] ?? [PHP_INT_MAX, PHP_INT_MIN];
			if (count($args) < $argcounts[0] OR count($args) > $argcounts[1])
			{
				return '';
			}

			if (in_array($arg1, ['itemprop', 'itemtype']))
			{
				$output = [];
				if ($arg1 == 'itemprop')
				{
					$output[] = 'itemprop="' . vB5_String::htmlSpecialCharsUni($args[0]) . '"';
					$type = $args[1] ?? null;
				}
				else
				{
					$type = $args[0];
				}

				if ($type)
				{
					//It's expected that the types won't be fully qualified, but allow it.
					if (strpos($type, 'http') !== 0)
					{
						$type = 'https://schema.org/' . $type;
					}

					$output[] = 'itemscope itemtype="' . vB5_String::htmlSpecialCharsUni($type) . '"';
				}

				return implode(' ', $output);
			}
			//meta tag, include the propname and value when we don't have a tag to mrak
			else if ($arg1 == 'meta')
			{
				return '<meta itemprop="' . vB5_String::htmlSpecialCharsUni($args[0]) . '" content="' . vB5_String::htmlSpecialCharsUni($args[1]) . '" />';
			}
		}
	}

	//this usage is deprecated and left in in case people have customized templates with the old usage.
	//will remove after a while.
	private static function parseSchemaArray($schemaInfo)
	{
		//Not all of these have been replicated in the new code.  Not entirely clear what some of the unused tags are even for.
		//id -- not used.
		//itemref -- not used
		//datetime -- used to propulate the datetime attribute of the time tag.  Not really schema related and moved to vb:datetime
		//content -- strictly part of the meta tag
		//rel -- not used
		//link -- not used

		//Note the itemscope attribute was removed in an early phase of this.  We could restore it but it will be ignored and
		//set on the itemtype property.

		$attributes = ['id', 'itemprop', 'itemref', 'itemtype', 'datetime', 'content', 'rel'];
		$allowedTags = ['meta', 'link'];

		$output = [];
		foreach ($schemaInfo AS $key => $value)
		{
			if (!in_array($key, $attributes))
			{
				continue;
			}

			//A bit of a hack.  The item type is optional but if given should always appear
			//on the same element as the itemscope.  Since we always want to set the type we can
			//combine them and just add the scope tag when we add the type.
			if ($key == 'itemtype')
			{
				$key = 'itemscope itemtype';
				//It's expected that the types won't be fully qualified, but allow it.
				if (strpos($value, 'http') !== 0)
				{
					$value = 'https://schema.org/' . $value;
				}
			}
			else if ($key == 'datetime')
			{
				$value = date('Y-m-d\TH:i:s', $value);
			}

			$output[] = $key . '="' . vB5_String::htmlSpecialCharsUni($value) . '"';
		}

		$output = implode(' ', $output);
		if (!empty($schemaInfo['tag']) AND in_array($schemaInfo['tag'], $allowedTags))
		{
			$tag = $schemaInfo['tag'];
			return "<$tag $output />";
		}
		else
		{
			return $output;
		}
	}

	/**
	 * Implements {vb:debugexit}, which allows placing a "breakpoint" in a template
	 * for debugging purposes.
	 */
	public static function debugExit()
	{
		echo ob_get_clean();
		echo "<br />\n";
		echo "=======================<br />\n";
		echo "======= vB Exit =======<br />\n";
		echo "=======================<br />\n";
		exit;
	}

	/**
	 * Implements {vb:debugtimer}, which allows timing exectution time
	 * takes from one call to another.
	 *
	 * @param  string timer name
	 * @return string rendered time
	 */
	public static function debugTimer($timerName)
	{
		static $timers = [];

		if (!isset($timers[$timerName]))
		{
			// start timer
			$timers[$timerName] = microtime(true);

			return '';
		}
		else
		{
			// stop timer and return elapsed time
			$elapsed = microtime(true) - $timers[$timerName];

			return '<div style="border:1px solid red;padding:10px;margin:10px;">' . htmlspecialchars($timerName) . ': ' . $elapsed . '</div>';
		}
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 115904 $
|| #######################################################################
\*=========================================================================*/
