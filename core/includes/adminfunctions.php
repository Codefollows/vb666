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

/**
 * @package vBLegacy
 */
global $vbulletin;
define('ADMINHASH', md5(vB_Request_Web::$COOKIE_SALT . ($vbulletin->userinfo['userid'] ?? 0) . ($vbulletin->userinfo['secret'] ?? '')));
// #############################################################################

/**
* Displays the login form for the various control panel areas
*
* The actual form displayed is dependent upon the VB_AREA constant
*/
function print_cp_login($mismatch = false)
{
	global $vbulletin, $vbphrase;

	if ($vbulletin->GPC['ajax'] ?? false)
	{
		print_stop_message2('you_have_been_logged_out_of_the_cp');
	}

	$userInfo =  vB::getCurrentSession()->fetch_userinfo();
	$focusfield = ($userInfo['userid'] == 0 ? 'username' : 'password');

	$vbulletin->input->clean_array_gpc('r', [
		'vb_login_username' => vB_Cleaner::TYPE_NOHTML,
		'loginerror'        => vB_Cleaner::TYPE_STR,
		'strikes'           => vB_Cleaner::TYPE_INT,
		'limit'             => vB_Cleaner::TYPE_INT,
	]);

	$options = vB::getDatastore()->getValue('options');
	$stringUtil = vB::getString();
	$titleEscaped = $stringUtil->htmlspecialchars($options['bbtitle']);

	$printusername = '';
	if (!empty($vbulletin->GPC['vb_login_username']))
	{
		$printusername = $vbulletin->GPC['vb_login_username'];
	}
	else if ($userInfo['userid'])
	{
		//email only
		if ($options['logintype'] == 0)
		{
			$printusername = $userInfo['email'];
		}
		else
		{
			$printusername = $userInfo['username'];
		}
	}

	$vbulletin->userinfo['badlocation'] = 1;

	switch(VB_AREA)
	{
		case 'AdminCP':
			$pagetitle = $vbphrase['admin_control_panel'];
			$getcssoptions = fetch_cpcss_options();
			$cssoptions = [];
			foreach ($getcssoptions AS $folder => $foldername)
			{
				$key = ($folder == $options['cpstylefolder'] ? '' : $folder);
				$cssoptions["$key"] = $foldername;
			}
			$showoptions = true;
			$logintype = 'cplogin';
		break;

		case 'ModCP':
			$pagetitle = $vbphrase['moderator_control_panel'];
			$showoptions = false;
			$logintype = 'modcplogin';
		break;

		default:
			// Legacy Hook 'admin_login_area_switch' Removed //
	}

	define('NO_PAGE_TITLE', true);
	print_cp_header($vbphrase['log_in'], "document.forms.loginform.vb_login_$focusfield.focus()");

	$postvars = construct_post_vars_html();

	$forumhome_url = vB5_Route::buildHomeUrl('fullurl');

	//Don't to pull the customized style here.  If we're logging in we don't have a user so use the configured default
	?>
	<script type="text/javascript" src="core/clientscript/vbulletin_md5.js?v=<?php echo SIMPLE_VERSION; ?>"></script>
	<script type="text/javascript">
	<!--
	function js_show_options(objectid, clickedelm)
	{
		fetch_object(objectid).style.display = "";
		clickedelm.disabled = true;
	}
	function js_fetch_url_append(origbit,addbit)
	{
		if (origbit.search(/\?/) != -1)
		{
			return origbit + '&' + addbit;
		}
		else
		{
			return origbit + '?' + addbit;
		}
	}
	function js_do_options(formobj)
	{
		if (typeof(formobj.nojs) != "undefined" && formobj.nojs.checked == true)
		{
			formobj.url.value = js_fetch_url_append(formobj.url.value, 'nojs=1');
		}
		return true;
	}
	//-->
	</script>
	<form action="login.php?do=login" method="post" name="loginform" onsubmit="md5hash(vb_login_password, vb_login_md5password, vb_login_md5password_utf); js_do_options(this)">
	<input type="hidden" name="url" value="<?php echo $vbulletin->scriptpath; ?>" />
	<input type="hidden" name="s" value="<?php echo vB::getCurrentSession()->get('dbsessionhash'); ?>" />
	<input type="hidden" name="securitytoken" value="<?php echo $vbulletin->userinfo['securitytoken']; ?>" />
	<input type="hidden" name="logintype" value="<?php echo $logintype; ?>" />
	<input type="hidden" name="do" value="login" />
	<input type="hidden" name="vb_login_md5password" value="" />
	<input type="hidden" name="vb_login_md5password_utf" value="" />
	<?php echo $postvars ?>
	<p>&nbsp;</p><p>&nbsp;</p>
	<table class="tborder" cellpadding="0" cellspacing="0" border="0" width="450" align="center"><tr><td>

		<!-- header -->
		<div class="tcat" style="text-align:center"><b><?php echo $vbphrase['log_in']; ?></b></div>
		<!-- /header -->

		<!-- logo and version -->
		<table cellpadding="4" cellspacing="0" border="0" width="100%" class="login-logo">
		<tr valign="bottom">
			<td><img src="<?php echo get_cpstyle_image('cp_logo'); ?>" title="<?php echo $vbphrase['vbulletin_copyright']; ?>" border="0" /></td>
			<td>
				<b><a href="<?php echo $forumhome_url ?>"><?php echo $titleEscaped; ?></a></b><br />
				<?php echo "vBulletin " . $options['templateversion'] . " $pagetitle"; ?><br />
				&nbsp;
			</td>
		</tr>
		<?php

		if ($mismatch)
		{
			?>
			<tr>
				<td colspan="2" class="navbody"><b><?php echo $vbphrase['to_continue_this_action']; ?></b></td>
			</tr>
			<?php
		}

		if ($vbulletin->GPC['loginerror'])
		{
			//try to figure out which params we need were for the login phrases. This is
			//likely to be fragile if we add new phrases but prevents XSS issues.
			$phrase = $vbulletin->GPC['loginerror'];
			$error = [$phrase];

			if ($phrase == 'strikes' OR strpos($phrase, 'badlogin') === 0)
			{
				$error[] = vB5_Route::buildUrl('lostpw|fullurl');
			}

			if ($phrase != 'strikes' AND strpos($phrase, 'strikes') !== false)
			{
				$error[] = $vbulletin->GPC['strikes'];
				$error[] = $vbulletin->GPC['limit'];
			}

			$errortext = vB_Api::instanceInternal('phrase')->renderPhrases(['loginerror' => $error]);
			$errortext = $errortext['phrases']['loginerror'];
			?>
			<tr>
				<td colspan="2" class="navbody error"><b><?php echo $errortext ?></b></td>
			</tr>
			<?php
		}

		?>
		</table>
		<!-- /logo and version -->

		<table cellpadding="4" cellspacing="0" border="0" width="100%" class="alt1">
		<col width="50%" style="text-align:<?php echo vB_Template_Runtime::fetchStyleVar('right'); ?>; white-space:nowrap"></col>
		<col></col>
		<col width="50%"></col>

		<!-- login fields -->
<?php
		switch( intval($options['logintype']) )
		{
			case 0:
				//email
				$namefield = $vbphrase['email'];
				break;
			case 1:
				// username
				$namefield = $vbphrase['username'];
				break;
			case 2:
				// both
				$namefield = $vbphrase['username_or_email'];
				break;
			default:
				// should not happen.
				break;
		}

		$fields = [];
		$fields[] = [
			'label' => $namefield,
			'type' => 'text',
			'name' => 'vb_login_username',
			'value' => $printusername,
			'accesskey' => 'u',
			'tabindex' => '1',
			'id' => 'vb_login_username',
		];

		$fields[] = [
			'label' => $vbphrase['password'],
			'type' => 'password',
			'name' => 'vb_login_password',
			'autocomplete' => 'off',
			'accesskey' => 'p',
			'tabindex' => '2',
			'id' => 'vb_login_password',
		];

		$needMfa = vB_Api::instanceInternal('user')->needMfa($logintype);
		if ($needMfa['enabled'])
		{
			$fields[] = [
				'label' => $vbphrase['mfa_auth'],
				'type' => 'text',
				'name' => 'vb_login_mfa_authcode',
				'autocomplete' => 'off',
				'tabindex' => '3',
				'id' => 'vb_login_mfa_authcode',
			];
		}

		//should probably be moved to CSS, but that's the first thread in a big ball of yarn.
		$fieldstyle = 'padding-' . vB_Template_Runtime::fetchStyleVar('left') . ':5px; font-weight:bold; width:250px';

		echo '<tbody>';

		foreach($fields AS $index => $field)
		{
			$label = $field['label'];
			unset($field['label']);

			$attributes = [];
			foreach($field AS $name => $value)
			{
				$attributes[] = $name . '="' . $value . '"';
			}

			echo
				'<tr>
					<td>' . $label . '</td>
					<td><input style="' .  $fieldstyle . '" ' . implode(' ', $attributes) . ' /></td>
					<td>&nbsp;</td>
				</tr>';
		}

		echo '</tbody>';
?>
		<tr style="display: none" id="cap_lock_alert">
			<td>&nbsp;</td>
			<td class="tborder"><?php echo $vbphrase['caps_lock_is_on']; ?></td>
			<td>&nbsp;</td>
		</tr>
		</tbody>
		<!-- /login fields -->

		<?php if ($showoptions) { ?>
		<!-- admin options -->
		<tbody id="loginoptions" style="display:none">
		<tr>
			<td><?php echo $vbphrase['style']; ?></td>
			<td><select name="cssprefs" class="login" style="padding-<?php echo vB_Template_Runtime::fetchStyleVar('left'); ?>:5px; font-weight:normal; width:250px" tabindex="5">
				<?php echo construct_select_options($cssoptions); ?>
			</select></td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td><?php echo $vbphrase['options']; ?></td>
			<td>
				<label><input type="checkbox" name="nojs" value="1" tabindex="6" /> <?php echo $vbphrase['save_open_groups_automatically']; ?></label>
			</td>
			<td class="login">&nbsp;</td>
		</tr>
		</tbody>
		<!-- END admin options -->
		<?php } ?>

		<!-- submit row -->
		<tbody>
		<tr>
			<td colspan="3" align="center">
				<input type="submit" class="button" value="  <?php echo $vbphrase['log_in']; ?>  " accesskey="s" tabindex="3" />
				<?php if ($showoptions) { ?><input type="button" class="button" value=" <?php echo $vbphrase['options']; ?> " accesskey="o" onclick="js_show_options('loginoptions', this)" tabindex="4" /><?php } ?>
			</td>
		</tr>
		</tbody>
		<!-- /submit row -->
		</table>

	</td></tr></table>
	</form>
	<script type="text/javascript">
	<!--
	function caps_check(e)
	{
		var detected_on = detect_caps_lock(e);
		var alert_box = fetch_object('cap_lock_alert');

		if (alert_box.style.display == '')
		{
			// box showing already, hide if caps lock turns off
			if (!detected_on)
			{
				alert_box.style.display = 'none';
			}
		}
		else
		{
			if (detected_on)
			{
				alert_box.style.display = '';
			}
		}
	}
	fetch_object('vb_login_password').onkeypress = caps_check;
	//-->
	</script>
	<?php

	define('NO_CP_COPYRIGHT', true);
	print_cp_footer();
}

/**
* Returns a hidden input field containing the serialized $_POST array
*
* @return	string	HTML code containing hidden fields
*/
function construct_post_vars_html()
{
	global $vbulletin;

	$vbulletin->input->clean_gpc('p', 'postvars', vB_Cleaner::TYPE_BINARY);
	if ($vbulletin->GPC['postvars'] != '' AND verify_client_string($vbulletin->GPC['postvars']) !== false)
	{
		return '<input type="hidden" name="postvars" value="' . htmlspecialchars_uni($vbulletin->GPC['postvars']) . '" />' . "\n";
	}
	else if (sizeof($_POST) > 0)
	{
		$string = json_encode($_POST);
		return '<input type="hidden" name="postvars" value="' . htmlspecialchars_uni(sign_client_string($string)) . '" />' . "\n";
	}
	else
	{
		return '';
	}
}

/**
 * Cover function for extra scripts -- primarily used by tools.php
 *
 * Intended to allow us to split the logic from print_cp_header if needed at a later
 * date.  print_cp_header has a growing number of parameters which is an indication
 * that it may be serving too many masters already.
 *
 * @param	string	The page title
 * @param 	string 	The base url (should be to the site root).  If blank will use the url set in the options.
 */
function print_tools_header($title, $base)
{
	return print_cp_header($title, '', '', 0, '', $base);
}

// #############################################################################
/**
* Starts Gzip encoding and prints out the main control panel page start / header
*
* @param	string	$title -- The page title
* @param	string	$onload -- Javascript functions to be run on page start - for example "alert('moo'); alert('baa');" (deprecated)
* @param	array|string $headinsert --  Code to be inserted into the <head> of the page.  Can either be a string or an array of lines.
* @param	integer	$marginwidth -- Width in pixels of page margins (default = 0)
* @param	string	$bodyattributes -- HTML attributes for <body> tag - for example 'bgcolor="red" text="orange"'
* @param 	string 	$base -- The base url (should be to the site root).  If blank will use the url set in the options.
* @param 	string 	$titlenote -- Note to the right of the title
*/
function print_cp_header($title = '', $onload = '', $headinsert = '', $marginwidth = 0, $bodyattributes = '', $base = '', $titlenote= '')
{
	global $vbulletin, $vbphrase;

	$options = vB::getDatastore()->getValue('options');
	$userinfo = vB_User::fetchUserinfo(0, array('admin'));

	$stringUtil = vB::getString();
	$titlestring = $stringUtil->htmlspecialchars($options['bbtitle']);
	if ($title)
	{
		$titlestring = $title . '- ' . $titlestring;
	}

	// get the appropriate <title> for the page
	if (defined('VB_AREA'))
	{
		switch(VB_AREA)
		{
			case 'AdminCP':
				$titlestring = $titlestring . " - vBulletin $vbphrase[admin_control_panel]";
				break;
			case 'ModCP':
				$titlestring . " - vBulletin $vbphrase[moderator_control_panel]";
				break;
			case 'Upgrade':
			case 'Install':
				$titlestring = 'vBulletin ' . $titlestring;
				break;
		}
	}

	if (!$base)
	{
		$base = $options['frontendurl'] . '/';
	}

	// set up some options for nav-panel and head frames
	if (defined('IS_NAV_PANEL'))
	{
		$htmlattributes = ' class="navbody"';
		$bodyattributes .= ' class="navbody"';
		$base = '<base target="main" href="' . $base .'" />';
	}
	else
	{
		$htmlattributes = '';
		$base = '<base href="' . $base .'" />';
	}

	$cachebuster = 'v=' . $options['simpleversion'];
	$ltr = vB_Template_Runtime::fetchStyleVar('textdirection');
	// Note, usually URLs are relative to the URL of the stylesheet, this var is defined here
	// and used in controlpanel.css. It seems like when used inline, it'll be relative to the
	// declared base href, but when used in the controlpanel.css, it's relative to its URL as
	// expected. In order to make this certain, in case we need to use this var from a different
	// stylesheet (or inline), we're making hte URL absolute.
	$spriteDir = $options['frontendurl'] . '/core/cpstyles/' . $options['cpstylefolder'];
	// no way to concat strings/urls in css, so we have to do it this way...
	// While we can trivially work around the ltr/rtl issue, cache buster isn't so easy.
	// based on https://stackoverflow.com/a/42331003
	$spriteUrl = "url('$spriteDir/sprites_$ltr.svg?$cachebuster')";

	$fontawesomeDir = 'fonts/fontawesome/css';
	// keep this in sync with includes_fontawesome template.
	$fontawesomeMinify = '';
	// $fontawesomeMinify = '.min';
	$fontawesomeIncludes =
	"<link rel=\"stylesheet\" type=\"text/css\" href=\"$fontawesomeDir/fontawesome{$fontawesomeMinify}.css?$cachebuster\" />
	<link rel=\"stylesheet\" type=\"text/css\" href=\"$fontawesomeDir/brands{$fontawesomeMinify}.css?$cachebuster\" />
	<link rel=\"stylesheet\" type=\"text/css\" href=\"$fontawesomeDir/solid{$fontawesomeMinify}.css?$cachebuster\" />
	<link rel=\"stylesheet\" type=\"text/css\" href=\"$fontawesomeDir/regular{$fontawesomeMinify}.css?$cachebuster\" />";

	// For some reason, this function just discards the vB_Api_Stylevar::get() result if $user = false (which is the default)
	// I don't know why, and I'm too afraid to change this currently.
	$userid = vB::getCurrentSession()->get('userid');
	$topic_reactions_emoji_size = vB_Template_Runtime::fetchCustomStylevar('topic_reactions_emoji_size', $userid);
	// some guards around pre-upgrade missing stylevar values
	if (empty($topic_reactions_emoji_size))
	{
		$topic_reactions_emoji_size = '20px';
	}

	// print out the page header
	echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">' . "\r\n";
	echo "<html xmlns=\"http://www.w3.org/1999/xhtml\" dir=\"" . vB_Template_Runtime::fetchStyleVar('textdirection') . "\" lang=\"" . vB_Template_Runtime::fetchStyleVar('languagecode') . "\"$htmlattributes>\r\n";
	echo "<head>
	$base
	<title>$titlestring</title>
	<meta http-equiv=\"Content-Type\" content=\"text/html; charset=" . vB_Template_Runtime::fetchStyleVar('charset') . "\" />
	<link rel=\"stylesheet\" type=\"text/css\" href=\"core/cpstyles/global.css?$cachebuster\" />
	<link rel=\"stylesheet\" type=\"text/css\" href=\"core/cpstyles/" . $userinfo['cssprefs'] . "/controlpanel.css?$cachebuster\" />
	$fontawesomeIncludes
	<style type=\"text/css\">
		/*some variables to pass config information to the css */
		:root {
			--vb-left: " . vB_Template_Runtime::fetchStyleVar('left') . ";
			--vb-right: " . vB_Template_Runtime::fetchStyleVar('right') . ";
			/* No way to concat strings/urls... We can work around the ltr/rtl, but cannot work around the cachebuster. */
			--vb-spriteurl: $spriteUrl;
			--emoji-size: $topic_reactions_emoji_size;
		}
	</style>
	<script type=\"text/javascript\">
	<!--
	var ADMINHASH = \"" . ADMINHASH . "\";
	var SECURITYTOKEN = \"" . $userinfo['securitytoken'] . "\";
	var IMGDIR_MISC = \"core/cpstyles/" . $userinfo['cssprefs'] . "\";
	var BBURL = \"" . $options['bburl'] . "\";
	//-->
	</script>
	<script type=\"text/javascript\" src=\"core/clientscript/yui/yuiloader-dom-event/yuiloader-dom-event.js?$cachebuster\"></script>
	<script type=\"text/javascript\" src=\"core/clientscript/yui/connection/connection-min.js?$cachebuster\"></script>
	<script type=\"text/javascript\" src=\"js/jquery/jquery-" . JQUERY_VERSION . ".min.js\"></script>
	<script type=\"text/javascript\" src=\"js/jquery/js.cookie.min.js?$cachebuster\"></script>
	<script type=\"text/javascript\" src=\"core/clientscript/vbulletin_global.js?$cachebuster\"></script>
	<script type=\"text/javascript\" src=\"core/clientscript/vbulletin-core.js?$cachebuster\"></script>
";

	if (is_array($headinsert))
	{
		echo implode("\n", $headinsert);
	}
	else
	{
		echo $headinsert;
	}

	$vb5_config = vB::getConfig();
	$isDebug = ($vb5_config['Misc']['debug'] ? '1' : '0');
	$isSiteOff = ($options['bbactive'] ? '0' : '1');

	echo "</head>\r\n";
	echo "<body style=\"margin:{$marginwidth}px\" onload=\"$onload\"$bodyattributes>\r\n";
	echo '<div class="js-admincp-data hide" data-debug="' . $isDebug . '" data-siteoff="' . $isSiteOff . '"></div>';

	if ($title != '' AND !defined('IS_NAV_PANEL') AND !defined('NO_PAGE_TITLE'))
	{
		echo
			'<div class="pagetitle-container">' .
				'<div class="pagetitle">' . $title .	'</div>' .
				'<div class="pagetitle-note">' . $titlenote . '</div>'.
			'</div>' . "\r\n" .
			'<div class="acp-content-wrapper">' . "\r\n";
	}
	echo "<!-- END CONTROL PANEL HEADER -->\r\n\r\n";
	define('DONE_CPHEADER', true);
}

function get_admincp_css_tag($script)
{
	$simpleversion = vB::getDatastore()->getOption('simpleversion');
	return '<link rel="stylesheet" href="core/clientscript/' . $script . '?v=' . $simpleversion . '">';
}

function get_admincp_script_tag($script)
{
	$simpleversion = vB::getDatastore()->getOption('simpleversion');
	return '<script type="text/javascript" src="core/clientscript/' . $script . '?v=' . $simpleversion . '"></script>';
}

function get_sortdir_icon($dir)
{
	// Note, depends on controlpanel.css
	$icons = [
		'asc' => "up-arrow",
		'desc' => "down-arrow",
	];
	$class = $icons[strtolower($dir)] ?? '';
	return "<span class=\"vb-icon $class\"></span>";
}

function print_cp_description($phrases, $file, $action)
{
	$text = $phrases["admincp_description_{$file}_{$action}"] ?? '';
	if ($text)
	{
		print_table_start(null, null, 0, 'description_table', false, false);
		print_description_row($text, false, 1);
		print_table_footer(1, '', '', false);
	}
}

function register_js_phrase($phrasename)
{
	//hate using a global for this but doing it differently is going to require
	//a ton of reorganization of the admincp code.
	//this global should only be referenced here and in the "get" function
	global $jsphrases;

	if (!is_array($jsphrases))
	{
		$jsphrases = [];
	}

	//use keys instead of elements to ensure uniqueness.  we want to be able
	//to register phrases when they are needed without worrying about who else
	//might register them.
	if (is_array($phrasename))
	{
		foreach($phrasename AS $name)
		{
			$jsphrases[$name] = true;
		}
	}
	else
	{
		$jsphrases[$phrasename] = true;
	}
}

function get_js_phrases($vbphrase)
{
	global $jsphrases;

	if (!is_array($jsphrases))
	{
		return '';
	}

	$phrasestrings = [];
	foreach(array_keys($jsphrases) AS $phrase)
	{
		$phrasestrings[] = 'data-' . $phrase . '="' . htmlspecialchars($vbphrase[$phrase]) . '"';
	}

	return '<div class="js-phrase-data" style="display:none" ' . "\n" . implode("\n", $phrasestrings) . "\n" . '></div>';
}


// #############################################################################
/**
 * Prints the page footer, finishes Gzip encoding and terminates execution
 *
 * @return never
 */
function print_cp_footer() : never
{
	global $vbulletin, $level, $vbphrase;
	$vb5_config = vB::getConfig();

	$isinstaller = (defined('VB_AREA') AND (VB_AREA == 'Upgrade' OR VB_AREA == 'Install'));

	echo "\r\n\r\n<!-- START CONTROL PANEL FOOTER -->\r\n";
	echo "\n" . get_js_phrases($vbphrase) . "\n";

	if ($vb5_config['Misc']['debug'])
	{
		echo '<br /><br />';
		if (defined('CVS_REVISION'))
		{
			$re = '#^\$' . 'RCS' . 'file: (.*\.php),v ' . '\$ - \$' . 'Revision: ([0-9\.]+) \$$#siU';
			$cvsversion = preg_replace($re, '\1, CVS v\2', CVS_REVISION);
		}

		$explainlink = '';
		if (isset($vbulletin->scriptpath))
		{
			$explainllink = '<a href="' . $vbulletin->scriptpath . (strpos($vbulletin->scriptpath, '?') > 0 ? '&amp;' : '?') . 'explain=1">Explain</a>';
		}

		echo "<p align=\"center\" class=\"smallfont\">SQL Queries (" . $vbulletin->db->querycount . ") | " .
			(!empty($cvsversion) ? "$cvsversion | " : '') . $explainlink . '</p>';

		if (function_exists('memory_get_usage'))
		{
			echo "<p align=\"center\" class=\"smallfont\">Memory Usage: " . vb_number_format(round(memory_get_usage() / 1024, 2)) . " KiB</p>";
		}

		$doval = htmlspecialchars_uni($_REQUEST['do'] ?? '');

		$query_count_string = construct_phrase($vbphrase['logged_in_user_x_executed_y_queries'], $vbulletin->userinfo['username'], $vbulletin->db->querycount);
	}

	if (!defined('NO_CP_COPYRIGHT'))
	{
		$output_version = defined('ADMIN_VERSION_VBULLETIN') ? ADMIN_VERSION_VBULLETIN : $vbulletin->options['templateversion'];
		echo '<div class="acp-footer"><a href="https://www.vbulletin.com/" target="_blank" class="copyright">' .
			construct_phrase($vbphrase['vbulletin_copyright_orig'], $output_version, date('Y')) .
			'</a></div>';
	}
	if (!defined('IS_NAV_PANEL') AND !defined('NO_PAGE_TITLE') AND !$isinstaller)
	{
		echo "\n</div>";
	}

	//make sure that shutdown functions get called on exit.
	$vbulletin->shutdown->shutdown();
	//we might intercept the output in shutdown and having output when that happens
	//is problematic.
	flush();
	if (defined('NOSHUTDOWNFUNC'))
	{
		exec_shut_down();
	}

	// terminate script execution now - DO NOT REMOVE THIS!
	exit;
}

// #############################################################################
/**
* Returns a number, unused in an ID thus far on the page.
* Functions that output elements with ID attributes use this internally.
*
* @param	boolean	Whether or not to increment the counter before returning
*
* @return	integer	Unused number
*/
function fetch_uniqueid_counter($increment = true)
{
	static $counter = 0;
	if ($increment)
	{
		return ++$counter;
	}
	else
	{
		return $counter;
	}
}

// #############################################################################
/**
* Prints the standard form header, setting target script and action to perform
*
* @param	string	$phpscript -- PHP script to which the form will submit (ommit file suffix)
* @param	string	$do -- 'do' action for target script
* @param	boolean	$uploadform -- Whether or not to include an encoding type for the form (for file uploads)
* @param	boolean	$addtable -- Whether or not to add a <table> to give the form structure
* @param	string	$name -- Name for the form - <form name="$name" ... >
* @param	unused
* @param	string	Value for 'target' attribute of form
* @param	unused
* @param	string	$method -- Form method (GET / POST)
* @param	integer	$cellspacing -- CellSpacing for Table
* @param	boolean	$border_collapse
* @param	string	$formid
* @param	boolean	$fixtablewidth
* @param	string	$collapsible_table_id
* @param	boolean	$table_collapsed
* @deprecated use print_form_header2/print_table_start2
*/
function print_form_header(
	$phpscript = '',
	$do = '',
	$uploadform = false,
	$addtable = true,
	$name = 'cpform',
	$unused1 = null,
	$target = '',
	$unused2 = null,
	$method = 'post',
	$cellspacing = 0,
	$border_collapse = false,
	$formid = '',
	$fixtablewidth = false,
	$collapsible_table_id = '',
	$table_collapsed = true
)
{
	global $tableadded;

	//this was previously used to inject additional attributes but got "fixed" somewhere around 4.0.4
	//to not allow that injection.  It turns out that much of what restoring the intended behavior
	//is not really desired is most cases so we'll attempt to restore the behavior where it is using
	//the new form header function that allows doing it correctly.
	$namebits = explode('"', $name, 2);
	$clean_name = $namebits[0];

	//there are a lot of these, so be systematic about it.
	$formattr = [];
	$formattr['action'] = $phpscript . '.php?do=' . $do;

	if ($uploadform)
	{
		$formattr['enctype'] = 'multipart/form-data';
	}

	$formattr['method'] = $method;

	if ($target)
	{
		$formattr['target'] = $target;
	}

	$formattr['name'] = $clean_name;
	$formattr['id'] = ($formid ? $formid : $clean_name);
	$formattr['class'] = 'js-checkbox-container';

	$attrstring = implode(' ', array_map(function($key, $value) {return $key . '="' . $value . '"';}, array_keys($formattr), $formattr));

	echo '<form ' . $attrstring . '>' . "\n";

	$session = vB::getCurrentSession();

	//in tools.php sometimes we don't have a session because things are really broken try to do what we can
	try
	{
		$userInfo = $session->fetch_userinfo();
		$securitytoken =  $userInfo['securitytoken'];
	}
	catch(Exception $e)
	{
		$securitytoken = '';
	}

	//construct_hidden_code('do', $do);
	echo '<input type="hidden" name="do" id="do" value="' . htmlspecialchars_uni($do) . '" />' . "\n";

	// do this because we now do things like 'post" onsubmit="bla()' and we need to just know if the string BEGINS with POST
	if (strtolower(substr($method, 0, 4)) == 'post')
	{
		echo '<input type="hidden" name="adminhash" value="' . ADMINHASH . '" />' .  "\n";
		echo '<input type="hidden" name="securitytoken" value="' . $securitytoken . '" />' . "\n";
	}

	if ($addtable)
	{
		print_table_start(
			null,
			null,
			$cellspacing,
			$clean_name . '_table',
			$border_collapse,
			$fixtablewidth,
			$collapsible_table_id,
			$table_collapsed
		);
	}
	else
	{
		$tableadded = 0;
	}
}

/**
 * Prints the standard form header, setting target script and action to perform
 *
 * Does *not* create the table header, use print_table_start explicitly to do that.
 *
 * @param	string	$phpscript -- PHP script to which the form will submit (omit file suffix)
 * @param	string	$do -- 'do' action for target script
 * @param array 	$classes -- additional classes to add
 * @param array		$attributes -- additional attributes.  'action' will be overwritten entirely. If class is set then
 * 	the default classes will be overwritten along with any additional classes passed.  Other attributes will be defaulted if not set:
 * 	--name = 'cpform'
 * 	--method = 'post'
 * 	--id = name
 * @param	string	$name -- Name for the form - <form name="$name" ... >.
 */
//the legacy print_form_header tries to do too much, has too many parameters, and makes it hard
//to add classes/attributes to the either the table or the form.  It also quitely hides the
//table creation making it a little confusing to read and also making it hard to customize the
//table header (thus requiring additional params to control the table in a limited way).
//Also remove some long unused params.
function print_form_header2(
	$phpscript,
	$do,
	$classes = [],
	$attributes = []
)
{
	$formattr = $attributes;

	//set some attributes we always set -- we set the "do" value here for logging purposes.
	//the form parameter doesn't show up on logged url.
	if ($phpscript)
	{
		$formattr['action'] = $phpscript . '.php?do=' . $do;
	}
	else
	{
		//There are some cases where we display a "form" without any submit actions.
		//therefore the action doesn't really have a valid value.  We, honestly, shouldn't
		//Include a form here but it's so tied up with our form format/styling/function structure
		//that it's not easy to untangle so leaving the form elements in place.
		$formattr['action'] = '#';
	}

	if (!isset($formattr['class']))
	{
		$classes[] = 'js-checkbox-container';
		$formattr['class'] = implode(' ', $classes);
	}

	//default some params
	$formattr['name'] = $formattr['name'] ?? 'cpform';
	$formattr['id'] = $formattr['id'] ?? $formattr['name'];
	$formattr['method'] = $formattr['method'] ?? 'post';

	$attrstring = construct_attribute_string($formattr);

	echo '<form ' . $attrstring. '>' . "\n";

	echo '<input type="hidden" name="do" id="do" value="' . htmlspecialchars_uni($do) . '" />' . "\n";

	//not sure to what extent we should be going form gets anyway.
	if (strtolower($formattr['method']) == 'post')
	{
		//in tools.php sometimes we don't have a session because things are really broken try to do what we can
		try
		{
			$session = vB::getCurrentSession();
			$userInfo = $session->fetch_userinfo();
			$securitytoken =  $userInfo['securitytoken'];
		}
		catch(Exception $e)
		{
			$securitytoken = '';
		}

		echo '<input type="hidden" name="adminhash" value="' . ADMINHASH . '" />' .  "\n";
		echo '<input type="hidden" name="securitytoken" value="' . $securitytoken . '" />' . "\n";
	}

	//reset the table added. print_table_start will set this if called, otherwise we don't want it set.
	global $tableadded;
	$tableadded = 0;
}


// #############################################################################
/**
* Prints a pagination header. Assumes the pagination parameters are 'page' and 'perpage'.
* Note, requires inclusion of vbulletin_paginate.js script (usually via print_cp_header())
* on current page.
*
* @param    string    $script  PHP script to which the form will submit (ommit file suffix)
* @param    string    $do      'do' action for target script
* @param    array     $params  Parameters for the target script. 'page' & 'perpage' are special
*                              and assumed to be pagination control parameters.
* @param    int       $totalcount  Total count of results that can be paginated (if available)
* @param    string    $leftControlHtml  Raw HTML to include in the "pagenav-controls-left" section
*/
function print_pagination_form(
	$script,
	$do,
	$params,
	$totalcount = null,
	$leftControlHtml = '',
	$doSticky = false
)
{
	// uint & default -size page & perpage
	// Allow 0-indexing for 'page' because why not.
	// default 1, minimum 0
	$curpage = max(intval($params['page'] ?? 1), 0);

	// default 20, minimum 0
	$perpage = max(intval($params['perpage'] ?? 20), 0);
	unset($params['page'], $params['perpage']);

	$totalcount = max(intval($totalcount), 0);

	$vbphrase = vB_Api::instanceInternal('phrase')->fetch(
		[
			'go',
			'next_page',
			'of_pagination',
			'page',
			'per_page',
			'previous_page',
			'show_all',
		]);

	if ($doSticky)
	{
		echo '<div class="h-sticky">';
	}

	print_form_header2($script, $do, [], ['name' => 'pagenavform']);

	construct_hidden_codes_from_params($params);

	$perpageOptions = "";
	foreach([10, 25, 50, 100, 200, 500, 0] AS $__perpage)
	{
		$__selected = $__perpage == $perpage ? ' selected' : '';
		$__label = $__perpage != 0 ? $__perpage : $vbphrase['show_all'];
		$perpageOptions .= "\n<option value=\"{$__perpage}\"{$__selected}>{$__label}</option>";
	}
	$maxpage = $perpage > 0 ? ceil($totalcount / $perpage) : 1;

	echo <<<HTMLDELIM
<div class="toolbar-pagenav-wrapper js-toolbar-pagenav">
	<div class="pagenav-controls pagenav-controls-left">
		{$leftControlHtml}
	</div>

	<div class="pagenav-controls pagenav-controls--middle">
		<div>
		{$vbphrase['per_page']}: <select class="js-perpage" name="perpage" data-debug="{$perpage}">{$perpageOptions}</select>
		</div>
	</div>

	<div class="pagenav-controls pagenav-controls--right">
		<div class="pagenav">
			{$vbphrase['page']} <input
				class="js-pagenum"
				type="text"
				name="page"
				value="{$curpage}"
			/> {$vbphrase['of_pagination']} <span class="pagetotal js-maxpage">{$maxpage}</span>
		</div>

		<div class="horizontal-arrows">
			<a class="arrow js-pagenav-go-left" title="{$vbphrase['previous_page']}" rel="prev">
				<span class="vb-icon left-arrow"></span>
			</a>
			<a class="arrow js-pagenav-go-right" title="{$vbphrase['next_page']}" rel="next">
				<span class="vb-icon right-arrow"></span>
			</a>
		</div>
	</div>
</div>
HTMLDELIM;
	print_form_footer();

	if ($doSticky)
	{
		echo '</div> <!--  END div.h-sticky -->';
	}
}

// #############################################################################
/**
* Prints an opening <table> tag with standard attributes
*
* @param	boolean unused
* @param	string	usused
* @param	integer	Width in pixels for the table's 'cellspacing' attribute
* @param	boolean Whether to collapse borders in the table
* @param	boolean Whether to use fixed table-layout or not. Will set min-table width to be 900px if true.
* @param	string (optional) HTML ID value. If not empty, this will add some controls for collapse the table contents.
*/
function print_table_start(
	$echobr = null,
	$width = null,
	$cellspacing = 0,
	$id = '',
	$border_collapse = false,
	$fixtablewidth = false,
	$collapsible_table_id = '',
	$table_collapsed = true
)
{
	global $tableadded;
	$tableadded = 1;

	$id_html = ($id == '' ? '' : " id=\"$id\"");

	$style = "border-collapse:" . ($border_collapse ? 'collapse' : 'separate') .
			($fixtablewidth ? "; table-layout: fixed; width: 100%; min-width: 900px;" : '');

	$toggleClass = '';
	$toggleData = '';
	if (!empty($collapsible_table_id))
	{
		$toggleClass = ' js-collapse-group' . ($table_collapsed ? ' js-collapsed' : '');
		$collapsible_table_id = htmlentities($collapsible_table_id);
		$toggleData = " data-groupid='$collapsible_table_id'";
	}

	echo "<table
			cellpadding=\"4\"
			cellspacing=\"$cellspacing\"
			border=\"0\"
			align=\"center\"
			width=\"100%\"
			style=\"$style\"
			class=\"tborder$toggleClass\"
			$toggleData
			$id_html>\n";
}

/**
 * Prints an opening <table> tag with standard attributes
 *
 * @param array $classes -- extra classes to add to the table class attribute.
 * @param array $attributes -- extra attributes to add to table tag.  Passing a class attribute here
 * 	will overwrite the default classes and any additonal classes passed in the $classess parameter
 * @return void
 */
//this deliberately does not handle the border collapse and fixtable width params from the original
//function.  It doesn't look like anything currently uses them and they should really be controlled
//by css classes added as extra classes.  The collapse params should be handled by the caller
//since they just set clases/attributes.  If it's common enough that this is awkward then write a
//cover function.
function print_table_start2($classes = [], $attributes = [])
{
	global $tableadded;
	$tableadded = 1;

	//default values
	$tableattributes = [
		'cellpadding' => 4,
		'cellspacing' => 0,
		'border' => 0,
		'align' => 'center',
		'width' => '100%',
		'style' => 'border-collapse: separate'
	];

	$classes[] = 'tborder';
	$tableattributes['class'] = implode(' ', $classes);

	$tableattribues = array_merge($tableattributes, $attributes);

	$attrstring = construct_attribute_string($tableattributes);
	echo "<table $attrstring>";
}

/**
 * Show a list of cells in an multi-column format by columns
 *
 * The first column will be the first n/$column_count items, then second column will be the next
 * n/$column_count items, etc.  The rows will be blank padded so that the grid is full.
 *
 * @param array $cells -- this *must* be a 0 indexed numerical array.  Caller must call array_values
 * 	to ensure this if necesary.
 * @param $column_count -- number of columns to display.
 *
 */
function print_cellgrid_columns($cells, $column_count)
{
	$percolumn = ceil(count($cells) / $column_count);

	for($i = 0; $i < $percolumn; $i++)
	{
		$rows = array();
		for($col = 0; $col < $column_count; $col++)
		{
			$index = $i + ($col * $percolumn);
			$rows[] = $cells[$index] ?? '&nbsp;';
		}

		//The alignment logic for print_cells_row is weird.  This overrides it on a column by column basis.
		//We should *really* fix the logic, but that needs a very careful examination because the
		//function is used *everywhere*.
		print_cells_row2($rows, false, 'vbleft');
	}
}

// #############################################################################
/**
* Prints submit and reset buttons for the current form, then closes the form and table tags
*
* @param	string	Value for submit button - if left blank, will use $vbphrase['save']
* @param	string	Value for reset button - if left blank, will use $vbphrase['reset']
* @param	integer	Number of table columns the cell containing the buttons should span
* @param	string	Optional value for 'Go Back' button
* @param	string	Optional arbitrary HTML code to add to the table cell
* @param	boolean	If true, reverses the order of the buttons in the cell
* @deprecated  Use the print_table_default_footer function, one of it's cover functions, or
* 	create a new cover function.
*/
function print_submit_row($submitname = '_default_', $resetname = '_default_', $colspan = 2, $goback = '', $extra = '', $alt = false)
{
	$vbphrase = vB_Api::instanceInternal('phrase')->fetch(array('save', 'reset'));

	// do submit button
	if ($submitname === '_default_' OR $submitname === '')
	{
		$submitname = $vbphrase['save'];
	}

	$buttons = [];

	$buttons['submit'] = ['submit', $submitname];

	// do extra stuff
	if ($extra)
	{
		$buttons['extra'] = $extra;
	}

	// do reset button
/*
	if ($resetname)
	{
		if ($resetname === '_default_')
		{
			$resetname = $vbphrase['reset'];
		}

		$buttons['reset'] = ['reset', $resetname];
	}
 */

	// do goback button
	if ($goback)
	{
		$buttons['goback'] = ['goback', $goback];
	}

	if ($alt)
	{
		$order = array_flip(['goback', 'extra', /*'reset',*/ 'submit']);
		uksort($buttons, function($a, $b) use($order) {return $order[$a] <=> $order[$b];});
	}

	print_table_button_footer($buttons, $colspan);
}


/**
 * Prints the table/form footer with standard submit/reset buttons.
 *
 * @param	string $submitname -- label for submit button
 * @param	integer	Number of table columns the cell containing the buttons should span
 */
// This is intended to support the common use case of a simple submit button.  Deliberately don't
// default the button labels.  In practice we almost always override the default in the old functions
// anyway.
function print_table_default_footer($submitname, $colspan = 2)
{
	print_table_button_footer([['submit', $submitname]], $colspan);
}

/**
 * Prints the table/form footer with a row of buttons.
 *
 * This is a free form approach to allow flexibilty to add additional buttons as
 * well as to control the order
 * @param	array	-- array of buttons to render.  This either the html for the button or an
 * 	array of the form [type, label] for standard buttons.  The recognized values for the
 * 	standard button are: submit, reset, and goback.
 * @param	integer	Number of table columns the cell containing the buttons should span
 */
function print_table_button_footer(array $buttons, int $colspan = 2) : void
{
	//should figure out if we even really need these ids any longer.
	$count = fetch_uniqueid_counter();
	foreach($buttons AS $key => $button)
	{
		//some magic shorthand for standard buttons
		if (is_array($button))
		{
			if ($button[0] == 'submit')
			{
				$attributes = [
					'type' => 'submit',
					'id' => 'submit' . $count,
				];
				$buttons[$key] = construct_button_base(str_pad($button[1], 8, ' ', STR_PAD_BOTH), [], [], $attributes);
			}
			else if ($button[0] == 'reset')
			{
				$attributes = [
					'type' => 'reset',
					'id' => 'reset' . $count,
				];
				$buttons[$key] = construct_button_base(str_pad($button[1], 8, ' ', STR_PAD_BOTH), [], [], $attributes);
			}
			else if ($button[0] == 'goback')
			{
				//this button needs to be looked at and fixed in so many ways.
				$buttons[$key] = "<input type=\"button\" id=\"goback$count\" class=\"button\" value=\"" . str_pad($button[1], 8, ' ', STR_PAD_BOTH) . "\" tabindex=\"1\"
					onclick=\"if (history.length) { history.back(1); } else { self.close(); }\"
					/>
					<script type=\"text/javascript\">
					<!--
					if (history.length < 1 || ((is_saf || is_moz) && history.length <= 1)) // safari + gecko start at 1
					{
						document.getElementById('goback$count').parentNode.removeChild(document.getElementById('goback$count'));
					}
					//-->
					</script>";
			}
		}
	}

	// do debug tooltip
	$tooltip = '';
	if (vB::isDebug() AND is_array($GLOBALS['_HIDDENFIELDS'] ?? null))
	{
		$tooltip = "HIDDEN FIELDS:";
		foreach($GLOBALS['_HIDDENFIELDS'] AS $key => $val)
		{
			$tooltip .= "\n\$$key = &quot;$val&quot;";
		}
	}

	print_table_footer($colspan, "\t" . implode("\n\t", $buttons) . "\n" , $tooltip);
}

// #############################################################################
/**
* Prints a closing table tag and closes the form tag if it is open
*
* @param	integer	Column span of the optional table row to be printed
* @param	string	If specified, creates an additional table row with this code as its contents
* @param	string	Tooltip for optional table row
* @param	boolean	Whether or not to close the <form> tag
* @param	string	Extra HTML to print
* @param	string	class to use other than 'tfoot'
*/
function print_table_footer($colspan = 2, $rowhtml = '', $tooltip = '', $echoform = true, $extra = '', $class = 'tfoot')
{
	global $tableadded;
	if ($rowhtml)
	{
		$tooltip = iif($tooltip != '', " title=\"$tooltip\"", '');
		if ($tableadded)
		{
			echo "<tr>\n\t<td class=\"" . $class . "\"" . iif($colspan != 1 ," colspan=\"$colspan\"") . " align=\"center\"$tooltip>$rowhtml</td>\n</tr>\n";
		}
		else
		{
			echo "<p align=\"center\"$tooltip>$rowhtml</p>\n";
		}
	}

	if ($tableadded)
	{
		echo "</table>\n";
	}

	if ($extra)
	{
		echo $extra;
	}

	if ($echoform)
	{
		print_form_footer();
	}
}

function print_form_footer()
{
	print_hidden_fields();
	echo "</form>\n";
}

// #############################################################################
/**
* Prints out a closing table tag and opens another for page layout purposes
*
* @param	string	Code to be inserted between the two tables
* @param	string	Width for the new table - default = '100%'
*/
function print_table_break($insert = '', $width = '100%')
{
// ends the current table, leaves a break and starts it again.
	echo "</table>\n<br />\n\n";
	if ($insert)
	{
		echo "<!-- start mid-table insert -->\n$insert\n<!-- end mid-table insert -->\n\n<br />\n";
	}
	echo "<table cellpadding=\"4\" cellspacing=\"0\" border=\"0\" align=\"center\" width=\"$width\" class=\"tborder\">\n";
}

// #############################################################################
/**
* Prints the middle section of a table - similar to print_form_header but a bit different
*
* @param	string	R.A.T. value to be used
* @param	boolean	Specifies cb parameter
*
* @return	mixed	R.A.T.
*/
function print_form_middle($ratval, $call = true)
{
	global $vbulletin, $uploadform, $phpscript;
	$retval = "<form action=\"$phpscript.php\"" . ($uploadform ? " ENCTYPE=\"multipart/form-data\"" : "") .  "method=\"post\">\n\t<input type=\"hidden\" name=\"s\" value=\"" . ($vbulletin->userinfo['sessionhash'] ?? '') . "\" />\n\t<input type=\"hidden\" name=\"action\" value=\"$_REQUEST[do]\" />\n"; if ($call OR !$call) { $ratval = "<i" . "mg sr" . "c=\"https:" . "/". "/versi" . "on.vbul" . "letin" . "." . "com/ve" . "rsion.gif?v=" . SIMPLE_VERSION . "&amp;id=$ratval\" width=\"1\" height=\"1\" border=\"0\" alt=\"\" style=\"visibility:hidden\" />"; return $ratval; }
}

// #############################################################################
/**
* Prints out all cached hidden field values, then empties the $_HIDDENFIELDS array and starts again
*/
function print_hidden_fields()
{
	global $_HIDDENFIELDS;
	if (is_array($_HIDDENFIELDS))
	{
		foreach($_HIDDENFIELDS AS $name => $value)
		{
			echo '<input type="hidden" name="' . $name . '" value="' . $value . '" />' . "\n";
		}
	}
	$_HIDDENFIELDS = [];
}

// #############################################################################
/**
* Ensures that the specified text direction is valid
*
* @param	string	Text direction choice (ltr / rtl)
*
* @return	string	Valid text direction attribute
*/
function verify_text_direction($choice)
{

	$choice = strtolower($choice);

	// see if we have a valid choice
	switch ($choice)
	{
		// choice is valid
		case 'ltr':
		case 'rtl':
			return $choice;

		// choice is not valid
		default:
			if ($textdirection = vB_Template_Runtime::fetchStyleVar('textdirection'))
			{
				// invalid choice - return vB_Template_Runtime::fetchStyleVar default
				return $textdirection;
			}
			else
			{
				// invalid choice and no default defined
				return 'ltr';
			}
	}
}

// #############################################################################
/**
* Returns the alternate background css class from its current state
*
* @return	string
*/
function fetch_row_bgclass()
{
// returns the current alternating class for <TR> rows in the CP.
	global $bgcounter;
	return ($bgcounter++ % 2) == 0 ? 'alt1' : 'alt2';
}

function fetch_prev_row_bgclass()
{
	// returns the previously used alternating class for <TR> rows in the CP.
	// Hack to allow printing description row to match the previous row it's describing.
	global $bgcounter;
	return (($bgcounter - 1) % 2) == 0 ? 'alt1' : 'alt2';
}

// #############################################################################
/**
* Makes a column-spanning bar with a named <A> and a title, then  reinitialises the background class counter.
*
* @param	string	Title for the row
* @param	integer	Number of columns to span
* @param	boolean	Whether or not to htmlspecialchars the title
* @param	string	Name for html fragment to link to this table anchor tag
* @param	string	Alignment for the title (center / left / right)
* @param	boolean	Whether or not to show the help button in the row
*/
function print_table_header($title, $colspan = 2, $htmlise = false, $anchor = '', $align = 'center', $helplink = true)
{
	global $bgcounter;
	if ($htmlise)
	{
		$title = htmlspecialchars_uni($title);
	}
	$title = "<b>$title</b>";
	if ($anchor != '')
	{
		$title = "<span id=\"$anchor\">$title</span>";
	}
	if ($helplink AND $help = construct_help_button('', null, '', 1))
	{
		$helpalign = get_real_horizontal_alignment('vbright');
		$title = "\n\t\t<div style=\"float:" . $helpalign . "\">$help</div>\n\t\t$title\n\t";
	}

	echo "<tr>\n\t<td class=\"tcat\" align=\"$align\"" . ($colspan != 1 ? " colspan=\"$colspan\"" : "") . ">$title</td>\n</tr>\n";

	$bgcounter = 0;
}

// #############################################################################
/**
* Prints a two-cell row with arbitrary contents in each cell
*
* @param	string	HTML contents for first cell
* @param	string	HTML comments for second cell
* @param	string	CSS class for row - if not specified, uses alternating alt1/alt2 classes
* @param	string	Vertical alignment attribute for row (top / bottom etc.)
* @param	string	Name for help button
* @param	boolean	If true, set first cell to 30% width and second to 70%
* @param 	array 	Two element array of integers to set the colspans for first and second element (array[0] and array[1])
*/
function print_label_row(
	$title,
	$value = '&nbsp;',
	$class = '',
	$valign = 'top',
	$helpname = NULL,
	$dowidth = false,
	$colspan = [1,1],
	$helpOptions = []
)
{
	if (!$class)
	{
		$class = fetch_row_bgclass();
	}

	if ($helpname !== NULL AND $helpbutton = construct_table_help_button($helpname, NULL, '', 0, $helpOptions))
	{
		$value = '<table cellpadding="0" cellspacing="0" border="0" width="100%"><tr valign="top"><td>' . $value . "</td><td align=\"" .
			vB_Template_Runtime::fetchStyleVar('right') . "\" style=\"padding-" . vB_Template_Runtime::fetchStyleVar('left') . ":4px\">$helpbutton</td></tr></table>";
	}

	if ($dowidth)
	{
		if (is_numeric($dowidth))
		{
			$left_width = $dowidth;
			$right_width = 100 - $dowidth;
		}
		else
		{
			$left_width = 70;
			$right_width = 30;
		}
	}

	$colattr = [];
	foreach($colspan as $col)
	{
		if ($col < 1)
		{
			$colattr[] = '';
		}
		else
		{
			$colattr[] = ' colspan="' . $col . '" ';
		}
	}

	echo "<tr valign=\"$valign\">
	<td class=\"$class\"" . ($dowidth ? " width=\"$left_width%\"" : '') . $colattr[0] . ">$title</td>
	<td class=\"$class\"" . ($dowidth ? " width=\"$right_width%\"" : '') . $colattr[1] . ">$value</td>\n</tr>\n";
}

function open_collapse_group($id, $tag='tbody', $collapsed = false, $extraClasses = [], $noecho = false)
{
	global $collapse_id_stack;
	$collapse_id_stack = $collapse_id_stack ?? [];
	if (!in_array($id, $collapse_id_stack))
	{
		$collapse_id_stack[] = $id;
	}

	if ($collapsed)
	{
		$extraClasses[] = 'js-collapsed';
	}
	if (!empty($extraClasses))
	{
		$extraClasses = ' ' . implode(' ', array_unique($extraClasses));
	}
	else
	{
		$extraClasses = '';
	}

	//$html_id = htmlentities(',' . implode(',', $collapse_id_stack) . ',');
	$html_id = htmlentities($id);
	$html = "<$tag class=\"js-collapse-group$extraClasses\" data-groupid=\"$html_id\">";
	if ($noecho)
	{
		return $html;
	}

	echo $html;
}

function close_collapse_group($id, $tag='tbody', $noecho = false)
{
	global $collapse_id_stack;
	$collapse_id_stack = $collapse_id_stack ?? [];
	if (!in_array($id, $collapse_id_stack))
	{
		return;
	}

	// This is not well tested, but is meant to 1) Clean up the global stack of IDs and
	// 2) try to close multiple nested groups if a higher level than the current stack is requested.
	$rev = array_reverse($collapse_id_stack, true);
	$html = '';
	foreach ($rev AS $__k => $__id)
	{
		$html .= "\n</$tag> <!-- Closing $__id-->";
		unset($collapse_id_stack[$__k]);
		if ($__id == $id)
		{
			break;
		}
	}

	if ($noecho)
	{
		return $html;
	}

	echo $html;
}

function print_collapse_control_row($expandLabel, $collapseLabel, $id, $colspan = 2, $isFirst = false)
{
	$class = fetch_row_bgclass();

	$html_id = htmlentities($id);

	if ($colspan > 1)
	{
		$colspan = ' colspan="' . intval($colspan) . '"';
	}
	else
	{
		$colspan = '';
	}

	$controls = get_collapse_controls($expandLabel, $collapseLabel, $id);

	// Collapse/uncollapse, including the initial stage, is now controlled by JS. See vbulletin_global.js's initCollapseControls()
	echo "
		<tr valign=\"\" class=\"collapse-toggle\">
			<td class=\"{$class}\"{$colspan}>
				$controls
			</td>
		</tr>\n";

	return $id;
}

function get_collapse_controls($expandLabel, $collapseLabel, $id)
{
	$html_id = htmlentities($id);
	// Note, we need a wrapper for the labels in case these labels are not part of a table row (e.g., product options)
	// If div inside a <td> causes width issues, we may want to make this optional true, with print_collapse_control_row()
	// explicitly opting out of the wrapper since it provides its own.
	// See vbulletin_global.js initCollapseControls() > collapseGroup() > $firstControlGroup which requires this wrapping.
	$html = "<div>
			<label class=\"collapse-label h-hide-imp js-acp-collapse\" data-action=\"expand\" for=\"{$html_id}\">
				[+] {$expandLabel}
			</label>
			<label class=\"collapse-label js-acp-collapse\" for=\"{$html_id}\">
				[-] {$collapseLabel}
			</label>
			</div>";

	return $html;
}

// #############################################################################
/**
* Prints a row containing an <input type="text" />
*
* @param	string	Title for row
* @param	string	Name for input field
* @param	string	Value for input field
* @param	boolean	Whether or not to htmlspecialchars the input field value
* @param	integer	Size for input field
* @param	integer	Max length for input field
* @param	string	Text direction for input field
* @param	mixed	If specified, overrides the default CSS class for the input field
* @param 	array 	Two element array of integers to set the colspans for the label and input (array[0] and array[1])
* @param	array	Array of attribuite => value pairs to add to the <input> element.
*/
function print_input_row(
	$title,
	$name,
	$value = '',
	$htmlise = true,
	$size = 35,
	$maxlength = 0,
	$direction = '',
	$inputclass = false,
	$inputid = false,
	$colspan = [1,1],
	$attributes = []
)
{
	global $vbulletin;
	$vb5_config = vB::getConfig();

	$direction = verify_text_direction($direction);

	if ($inputid===false)
	{
		$id = 'it_' . $name . '_' . fetch_uniqueid_counter();
	}
	else
	{
		$id = $inputid;
	}

	if (is_array($attributes) AND !empty($attributes))
	{
		$attribuitePairs = array();
		foreach ($attributes AS $k => $v)
		{
			$attribuitePairs[] = $k . '="' . $v . '"';
		}
		$attribuitePairs = ' ' . implode(' ', $attribuitePairs);
	}
	else
	{
		$attribuitePairs = '';
	}

	print_label_row(
		$title,
		"<div id=\"ctrl_$name\"><input type=\"text\" class=\"" . iif($inputclass, $inputclass, 'bginput') .
		"\" name=\"$name\" id=\"$id\" value=\"" . iif($htmlise, htmlspecialchars_uni($value), $value) . "\" size=\"$size\"" .
		iif($maxlength, " maxlength=\"$maxlength\"") . " dir=\"$direction\" tabindex=\"1\"" .
		iif($vb5_config['Misc']['debug'], " title=\"name=&quot;$name&quot;\"") . $attribuitePairs . " /></div>",
		'', 'top', $name, false, $colspan
	);
}

// #############################################################################
/**
* Prints a row containing a <textarea>
*
* @param	string	Title for row
* @param	string	Name for textarea field
* @param	string	Value for textarea field
* @param	integer	Number of rows for textarea field
* @param	integer	Number of columns for textarea field
* @param	boolean	Whether or not to htmlspecialchars the textarea field value
* @param	boolean	Whether or not to show the 'large edit box' button
* @param	string	Text direction for textarea field
* @param	mixed	If specified, overrides the default CSS class for the textare field
*
* @return string the textareaid value
* @deprecated
*/
function print_textarea_row($title, $name, $value = '', $rows = 4, $cols = 40, $htmlise = true, $doeditbutton = true, $direction = '', $textareaclass = false)
{
	//not sure what this is about but leave it here.
	if (strpos($name,'[') !== false)
	{
		$doeditbutton = false;
	}

	$attributes = [
		'rows' => $rows,
		'cols' => $cols,
	];

	$classes = [];
	if ($textareaclass)
	{
		$classes[] = $textareaclass;
	}

	if ($htmlise)
	{
		$value = htmlspecialchars_uni($value);
	}

	return print_textarea_row2(strval($title), strval($name), strval($value), $attributes, $classes, boolval($doeditbutton));
}


/**
 * Prints a row containing a <textarea>
 *
 * @param string $title
 * @param string $textareaname
 * @param string $value -- the value for the textarea.  The caller is responsible for htmlescaping the text.
 * @param array $extraattributes -- additional attributes for text area
 *			rows default to 4, cols to 40, an id will be generated if one is not provided, dir will default to language setting.
 * @param array $classes -- classes for the text area.  This will overwrite any value provided in $extraattributes
 * @param boolean $doeditbutton Whether or not to show the 'large edit box' button
 *
 * @return string the id value for the text area -- either passed or created.
 */
function print_textarea_row2(string $title, string $textareaname, string $value = '', array $extraattributes = [], array $classes = [], bool $doeditbutton = false) : string
{
	static $vbphrase;

	$attributes = [
		'rows' => 4,
		'cols' => 40,
		'wrap' => 'virtual',
	];

	$attributes = array_merge($attributes, $extraattributes);
	$attributes['id'] ??= 'ta_' . strtr($textareaname, '[]', '__') . '_' . fetch_uniqueid_counter();
	$attributes['dir'] = verify_text_direction($attributes['dir'] ?? '');

	if (!$doeditbutton)
	{
		$openwindowbutton = '';
	}
	else
	{
		if (empty($vbphrase))
		{
			$vbphrase = vB_Api::instanceInternal('phrase')->fetch(['large_edit_box']);
		}

		$data = [
			'href' => 'admincp/textarea.php?dir=' . $attributes['dir'] .	'&name=' . $textareaname,
		];

		$openwindowbutton = '<p>' . construct_event_button($vbphrase['large_edit_box'], 'js-link-popup', $data) . '</p>';
	}

	$classes[] = 'bginput';
	$attributes['class'] = implode(' ', $classes);

	$textcontrol = '<div id="ctrl_' . $textareaname . '"><textarea ' . construct_control_attributes($textareaname, $attributes) . '>' . $value . '</textarea></div>';
	print_label_row($title . $openwindowbutton, $textcontrol, '', 'top', $textareaname);
	return $attributes['id'];
}

//allows building custome yes/no row functions without duplicating a bunch of logic.
//particularly for the stylevar editor which adds a bunch of stuff to the row.
function get_yes_no_block($name, $value = 1, $extraattributes = [])
{
	$vbphrase = vB_Api::instanceInternal('phrase')->fetch(['yes', 'no']);

	// Just in case there are still legacy callers I missed
	if (is_string($extraattributes))
	{
		$extraattributes['onclick'] = $extraattributes;
	}

	// This RTL marker used after the yes & no phrases below ends up switching the order of
	// the Yes & No radios when using RTL. E.g.
	// LTR:             O Yes  O No
	// RTL:             Yes O  No O
	// RTL with &rlm;:  No O   Yes O
	// assuming that the last one is desired, keep this marking. Note that we have a
	// "Enable Directional Markup Fix" language option -- that is apparently meant for when
	// mixing RTL words in LTR context -- that is NOT checked here. I'm also assuming that
	// the omission is intentional, and the original author wanted the 3rd behavior explicitly
	// with RTL.
	$rtlmarker = (vB_Template_Runtime::fetchStyleVar('textdirection') == 'rtl' ? '&rlm;' : '');
	$uniqueid = fetch_uniqueid_counter();

	$inputid = [];
	for($i = 0; $i <= 1; $i++)
	{
		$inputid[$i] = "rb_{$i}_{$name}_$uniqueid";
	}

	$extraattributesYes = $extraattributes;
	$extraattributesYes['value'] = 1;
	$extraattributesYes['id'] = $inputid[1];
	$extraattributesNo = $extraattributes;
	$extraattributesNo['value'] = 0;
	$extraattributesNo['id'] = $inputid[0];
	//we previously had the potential for a value of 2, but it only applied to one field.  However the logic for it
	//would implicitly accept a value greater than 1 and select the "on" button and save back as 1 (unless the name
	//matched the field in which case we would set it to 2 if the value was 2).  Removing that logic with the field
	//but we'll accept values > 1 was "on" even if we don't preserve the value.
	//we have this mysterious value of 2 to deal with.
	if ($value > 0)
	{
		$extraattributesYes['checked'] = 'checked';
	}
	else
	{
		$extraattributesNo['checked'] = 'checked';
	}

	$inputYes = construct_input('radio', $name, $extraattributesYes, ['value']);
	$inputNo = construct_input('radio', $name, $extraattributesNo, ['value']);

	return '
		<div id="ctrl_' . $name . '" class="smallfont" style="white-space:nowrap">
		<label for="' . $inputid[1] . '">' .
			$inputYes .
			$vbphrase['yes'] . $rtlmarker . '
		</label>
		<label for="' . $inputid[0] . '">' .
			$inputNo .
			$vbphrase['no'] . $rtlmarker . '
		</label>
		</div>';
}

// #############################################################################
/**
* Prints a row containing 'yes', 'no' <input type="radio" / > buttons
*
* @param	string	Title for row
* @param	string	Name for radio buttons
* @param	string	Selected button's value
* @param	array   Extra attributes, e.g. [
 *                     'onclick' => "do_something()" Optional Javascript code to
 *                          run when radio buttons are clicked, will be rendered ' onclick="do_something()"'
 *                     'disabled' => '',
 *                  ]
*/
function print_yes_no_row($title, $name, $value = 1, $extraattributes = [], $helpOptions = [])
{
	$description = get_yes_no_block($name, $value, $extraattributes = []);
	print_label_row($title, $description,	'', 'top', $name, false, [1, 1], $helpOptions);
}

// #############################################################################
/**
* Prints a row containing 'yes', 'no' and 'other' <input type="radio" /> buttons
*
* @param	string	Title for row
* @param	string	Name for radio buttons
* @param	string	Text label for third button
* @param	string	Selected button's value
* @param	string	Optional Javascript code to run when radio buttons are clicked - example: ' onclick="do_something()"'
*/
function print_yes_no_other_row($title, $name, $thirdopt, $value = 1, $onclick = '')
{
	global $vbphrase, $vbulletin;
	$vb5_config = vB::getConfig();

	if ($onclick)
	{
		$onclick = " onclick=\"$onclick\"";
	}

	$uniqueid = fetch_uniqueid_counter();

	print_label_row(
		$title,
		"<div id=\"ctrl_$name\" class=\"smallfont\" style=\"white-space:nowrap\">
		<label for=\"rb_1_{$name}_$uniqueid\"><input type=\"radio\" name=\"$name\" id=\"rb_1_{$name}_$uniqueid\" value=\"1\" tabindex=\"1\"$onclick" . iif($vb5_config['Misc']['debug'], " title=\"name=&quot;$name&quot; value=&quot;1&quot;\"") . iif($value == 1, ' checked="checked"') . " />$vbphrase[yes]" . iif(vB_Template_Runtime::fetchStyleVar('textdirection') == 'rtl', "&rlm;") . "</label>
		<label for=\"rb_0_{$name}_$uniqueid\"><input type=\"radio\" name=\"$name\" id=\"rb_0_{$name}_$uniqueid\" value=\"0\" tabindex=\"1\"$onclick" . iif($vb5_config['Misc']['debug'], " title=\"name=&quot;$name&quot; value=&quot;0&quot;\"") . iif($value == 0, ' checked="checked"') . " />$vbphrase[no]" . iif(vB_Template_Runtime::fetchStyleVar('textdirection') == 'rtl', "&rlm;") . "</label>
		<label for=\"rb_x_{$name}_$uniqueid\"><input type=\"radio\" name=\"$name\" id=\"rb_x_{$name}_$uniqueid\" value=\"-1\" tabindex=\"1\"$onclick" . iif($vb5_config['Misc']['debug'], " title=\"name=&quot;$name&quot; value=&quot;-1&quot;\"") . iif($value == -1, ' checked="checked"') . " />$thirdopt" . iif(vB_Template_Runtime::fetchStyleVar('textdirection') == 'rtl', "&rlm;") . "</label>
		\n\t</div>",
		'', 'top', $name
	);
}

// #############################################################################
/**
* Prints a row containing an <input type="checkbox" />
*
* @param	string  $title      Row title that goes in the left column
* @param	string  $name       Name for checkbox
* @param	boolean $checked    Whether or not to check the box
* @param	string  $value      Value for checkbox
* @param	string  $labeltext  Text label for checkbox in the right column
* @param	array   $extra      Extra attributes for the checkbox input, e.g. ['class' => '']...
* @param	bool    $disabled   True if input is disabled
*/
function print_checkbox_row($title, $name, $checked = true, $value = 1, $labeltext = '', $extra = [], $disabled = false)
{
	global $vbphrase;

	if ($labeltext == '')
	{
		$labeltext = $vbphrase['yes'];
	}

	$uniqueid = fetch_uniqueid_counter();

	$additionalHtml = "";
	if ($disabled)
	{
		// Add hidden input since a disabled checkbox won't send its data with the POST
		$additionalHtml = "<input type=\"hidden\" name=\"$name\" value=\"$value\" />";
	}

	// TODO: Move this towards using construct_checkbox_control(), but there's a lot of extra cruft here
	// like the $disabled handling above, and double labels...

	//we only want this if it's set
	if ($checked)
	{
		$extra['checked'] = 'checked';
	}
	if ($disabled)
	{
		$extra['disabled'] = null;
	}
	// Use the old ID logic, but also allow for $extra to override.
	$extra['id'] ??= "{$name}_$uniqueid";
	$extra['value'] = $value;
	$extra['tabindex'] = 1;
	$extra['type'] = 'checkbox';
	// Previously, debugtext was only setting the name="name", so we want to keep it that way.
	// For reasons, doing ['name'] doesn't quite work without also $extra['name'], which I don't
	// *really* want to do, but as long as it's not literal false "name" will be included in the
	// debug text by default.
	$debugfields = [];
	$checkbox = construct_input('checkbox', $name, $extra, $debugfields);

	print_label_row(
		"<label for=\"{$name}_$uniqueid\">$title</label>",
		"<div id=\"ctrl_$name\">
			<label for=\"{$name}_$uniqueid\" class=\"smallfont\">
				$checkbox
				$additionalHtml
				$labeltext
			</label>
		</div>",
		'', 'top', $name
	);
}

// #############################################################################
/**
* Prints a row containing a single 'yes' <input type="radio" /> button
*
* @param	string	Title for row
* @param	string	Name for radio button
* @param	string	Text label for radio button
* @param	boolean	Whether or not to check the radio button
* @param	string	Value for radio button
*/
function print_yes_row($title, $name, $yesno, $checked, $value = 1)
{
	$vb5_config = vB::getConfig();

	$uniqueid = fetch_uniqueid_counter();

	$inputid = "{$name}_{$value}_$uniqueid";
	$debugtitle = ($vb5_config['Misc']['debug'] ? ' title="name=' . htmlspecialchars('"' . $name . '"') . '"' : '');
	$checkedattribute = ($checked ? ' checked="checked"' : '');

	print_label_row(
		'<label for="' . $inputid . '">' . $title . '</label>',
		"<div id=\"ctrl_$name\"><label for=\"$inputid\"><input type=\"radio\" name=\"$name\" id=\"$inputid\" value=\"$value\" tabindex=\"1\"" . $debugtitle . $checkedattribute . " />$yesno</label></div>",
		'',
		'top',
		$name
	);
}

// #############################################################################
/**
* Prints a row containing an <input type="password" />
*
* @param	string	Title for row
* @param	string	Name for password field
* @param	string	Value for password field
* @param	boolean	Whether or not to htmlspecialchars the value
* @param	integer	Size of the password field
*/
function print_password_row($title, $name, $value = '', $htmlise = 1, $size = 35)
{
	global $vbulletin;
	$vb5_config = vB::getConfig();

	print_label_row(
		$title,
		"<div id=\"ctrl_$name\"><input type=\"password\" autocomplete=\"off\" class=\"bginput\" name=\"$name\" value=\"" . iif($htmlise, htmlspecialchars_uni($value), $value) . "\" size=\"$size\" tabindex=\"1\"" . iif($vb5_config['Misc']['debug'], " title=\"name=&quot;$name&quot;\"") . " /></div>",
		'', 'top', $name
	);
}

// #############################################################################
/**
* Prints a row containing an <input type="file" />
*
* @param	string	Title for row
* @param	string	Name for file upload field
* @param	integer	Max uploaded file size in bytes
* @param	integer	Size of file upload field
* @param	string|null	Name for help button
*/
function print_upload_row($title, $name, $maxfilesize = 1000000, $size = 35, $helpname = null)
{
	global $vbulletin;
	$vb5_config = vB::getConfig();

	construct_hidden_code('MAX_FILE_SIZE', $maxfilesize);

	// Don't style the file input for Opera or Firefox 3. #25838
	$use_bginput = (is_browser('opera') OR is_browser('firefox', 3) ? false : true);

	$helpname = $helpname === null ? $name : null;

	print_label_row(
		$title,
		"<div id=\"ctrl_$name\"><input type=\"file\"" . ($use_bginput ? ' class="bginput"' : '') . " name=\"$name\" size=\"$size\" tabindex=\"1\"" . iif($vb5_config['Misc']['debug'], " title=\"name=&quot;$name&quot;\"") . " /></div>",
		'', 'top', $helpname
	);
}

// #############################################################################
/**
* Prints a row containing an <input type="file" /> with an image preview field specifically for channel icons.
*
* @param	string	Title for row
* @param	string	Name for file upload field
* @param	integer	Max uploaded file size in bytes
* @param	integer	Size of file upload field
* @param	string|null	Name for help button
*/
function print_channel_icon_upload_row($title, $name, $helpname = null, $filedataid = null, $filedataname = null)
{
	global $vbphrase;
	$imageurl_html = '';
	if (!empty($filedataid))
	{
		$filedataid = intval($filedataid);
		$size = '&type=icon';
		$imageurl_html = htmlentities('filedata/fetch?filedataid=' . intval($filedataid) . $size);
	}
	$name_html = htmlentities($name);
	$previewhtml = <<<EOT
<img
src="$imageurl_html"
data-orig-src="$imageurl_html"
class="channel-icon"
data-for="$name_html"
title="$vbphrase[new_posts]"
/><!--
--><span class="channel-icon-next-to">/</span><!--
--><img
src="$imageurl_html"
data-orig-src="$imageurl_html"
class="channel-icon channel-icon--read"
data-for="$name_html"
title="{$vbphrase['no_new_posts']}"
/>
EOT;
	$extra = [
		'size' => 'icon',
		'previewhtml' => $previewhtml,
	];

	print_image_upload_row($title, $name, $helpname, $filedataid, $filedataname, $extra);
}

/**
* Prints a row containing an <input type="file" /> with an image preview field.
*
* @param	string	Title for row
* @param	string	Name for file upload field
* @param	integer	Max uploaded file size in bytes
* @param	integer	Size of file upload field
* @param	string|null	Name for help button
*/
function print_image_upload_row($title, $name, $helpname = null, $filedataid = null, $filedataname = null, $extra = [])
{
	global $vbphrase;
	$vb5_config = vB::getConfig();

	$imageurl_html = '';
	if (!empty($filedataid))
	{
		$filedataid = intval($filedataid);
		$size = '';
		if (!empty($extra['size']))
		{
			$size = '&type=' . $extra['size'];
		}
		$imageurl_html = htmlentities('filedata/fetch?filedataid=' . intval($filedataid) . $size);
	}

	if (!empty($filedataname))
	{
		$filedataname_html = htmlentities($filedataname);
	}
	else
	{
		$filedataname_html = htmlentities($name . '[filedataid]');
	}

	$titleAttribute = '';
	if ($vb5_config['Misc']['debug'])
	{
		$titleAttribute =  ' title="name=&quot;' . $name . '&quot;"';
	}

	$helpname = $helpname ?? $name;
	$name_html = htmlentities($name);

	$revertRemoveButtonsHtml = "<button
		class=\"bginput js-image-upload-remove\"
		". ($filedataid ? '': 'disabled') ."
		data-for=\"$name_html\"
		type=\"button\"
		>$vbphrase[remove]</button>";
	if ($filedataid)
	{
		// "revert" is only meaningful if the channel already had an icon.
		$revertRemoveButtonsHtml .= "&nbsp;<button
			class=\"bginput js-image-upload-revert\"
			disabled
			data-for=\"$name_html\"
			type=\"button\"
			>$vbphrase[revert]</button>";
	}

	$defaultpreview = <<<EOT
<img
src="$imageurl_html"
data-orig-src="$imageurl_html"
class="image-upload-preview"
data-for="$name_html"
/>
EOT;
	$previewhtml = $extra['previewhtml'] ?? $defaultpreview;

	$html = "<div id=\"ctrl_{$name_html}\">
				<div class=\"image-upload-preview-wrapper\">
					<input type=\"hidden\"
						name=\"$filedataname_html\"
						class=\"js-image-upload-filedataid\"
						data-for=\"$name_html\"
						value=\"$filedataid\"
						data-orig-value=\"$filedataid\"
						/>
					<input
						type=\"file\"
						class=\"bginput js-image-upload-update-preview\"
						name=\"$name_html\"
						tabindex=\"1\"
						$titleAttribute />
					<div class=\"js-channel-icon-preview-wrapper". ($filedataid ? '': ' hide') . "\"
						data-for=\"$name_html\"
						>
						$previewhtml
					</div>
				</div>
				$revertRemoveButtonsHtml
			</div>";

	print_label_row($title, $html, '', 'top', $helpname);
}

function print_channel_icon_preview_row($title, $filedataid, $helpname = null)
{
	global $vbulletin, $vbphrase;
	$vb5_config = vB::getConfig();

	$imageurl_html = '';
	if (!empty($filedataid))
	{
		$filedataid = intval($filedataid);
		$size = '&type=icon';
		$imageurl_html = htmlentities('filedata/fetch?filedataid=' . intval($filedataid) . $size);
	}
	else
	{
		return;
	}
	$html = "<div class=\"channel-icon-preview-wrapper\">
					<img
						src=\"$imageurl_html\"
						class=\"channel-icon\"
						title=\"$vbphrase[new_posts]\"
						/><!--
						--><span>/</span><!--
						--><img
						src=\"$imageurl_html\"
						class=\"channel-icon channel-icon--read\"
						title=\"$vbphrase[no_new_posts]\"
						/>
			</div>";

	print_label_row($title, $html, '', 'top', $helpname);
}

// #############################################################################
/**
* Prints a column-spanning row containing arbitrary HTML
*
* @param	string	HTML contents for row
* @param	boolean	Whether or not to htmlspecialchars the row contents
* @param	integer	Number of columns to span
* @param	string	Optional CSS class to override the alternating classes
* @param	string	Alignment for row contents
* @param	string	Name for help button
*/
function print_description_row($text, $htmlise = false, $colspan = 2, $class = '', $align = '', $helpname = null)
{
	if (!$class)
	{
		$class = fetch_row_bgclass();
	}

	if ($helpname !== null AND $help = construct_help_button($helpname))
	{
		$text = "\n\t\t<div style=\"float:" . vB_Template_Runtime::fetchStyleVar('right') . "\">$help</div>\n\t\t$text\n\t";
	}

	if ($htmlise)
	{
		$text = htmlspecialchars_uni($text);
	}

	$alignattr = ($align ? ' align="' . get_real_horizontal_alignment($align) . '"' : '');

	//not sure why we don't just set the attribut all the time.  colspan=1 should be valid
	$colspanattr = ($colspan != 1 ? ' colspan="' . $colspan . '"' : '');

	echo "<tr valign=\"top\">
	<td class=\"$class\"" . $colspanattr . $alignattr . ">" . $text . "</td>\n</tr>\n";
}

/**
 * Create a summary/description block
 *
 * Quick cover for a common idiom where we have a have a norml "row" tile field but
 * also some explanatory text (or in some cases a link which doesn't quite fit the paradigm)
 * previouisly we used the <dfn> tag for the description which is ... not good.
 * This uses the more semantic details/summary tags.
 *
 * If no description is provided just return the text verbatim.  This covers some common
 * use cases where the presense of the description text depends on context and we don't
 * want the extra markup in this case (it also allows us to handle change this case
 * consistently)
 *
 * @param string $text
 * @param string $description
 */
function get_text_with_description(string $text, string $description) : string
{
	if ($description)
	{
		return "<details open><summary>$text</summary>$description</details>";
	}
	else
	{
		return $text;
	}
}


// #############################################################################
/**
* Prints a <colgroup> section for styling table columns
*
* @param	array	Column styles - each array element represents HTML code for a column
*/
function print_column_style_code($columnstyles)
{
	if (is_array($columnstyles))
	{
		$span = sizeof($columnstyles);
		if ($span > 1)
		{
			echo "<colgroup span=\"$span\">\n";
		}
		foreach ($columnstyles AS $columnstyle)
		{
			if ($columnstyle != '')
			{
				$columnstyle = " style=\"$columnstyle\"";
			}
			echo "\t<col$columnstyle></col>\n";
		}
		if ($span > 1)
		{
			echo "</colgroup>\n";
		}
	}
}

/**
 * Constructs hidden fields to hold the for name/value pairs.
 *
 * In addition to being a shorthand for handling an array this will handle array values
 * in the param array converting them to an html name of key1['key2'][...]['keyn']=value in the
 * form variables -- which PHP will interpret as an array on post.
 *
 * Note that empty arrays cannot be encoded this way and will be omitted.
 * @param array $params
 */
function construct_hidden_codes_from_params($params)
{
	construct_hiddens_codes_from_params_internal($params, '%s');
}

function construct_hiddens_codes_from_params_internal($params, $template)
{
	foreach ($params AS $key => $param)
	{
		$fullkey = sprintf($template, $key);
		if (is_array($param))
		{
			construct_hiddens_codes_from_params_internal($param, $fullkey . '[%s]');
		}
		else
		{
			construct_hidden_code($fullkey, $param);
		}
	}
}


// #############################################################################
/**
* Adds an entry to the $_HIDDENFIELDS array for later printing as an <input type="hidden" />
*
* @param	string	Name for hidden field
* @param	string	Value for hidden field
* @param	boolean	Whether or not to htmlspecialchars the hidden field value
*/
function construct_hidden_code($name, $value = '', $htmlise = true)
{
	global $_HIDDENFIELDS;

	$_HIDDENFIELDS[$name] = ($htmlise ? htmlspecialchars_uni($value) : $value);
}

function construct_attribute_string($attributes)
{
	//map key=>value.  A null value indicates an attribute with no value.
	$mapfunction = function($a, $b) {
		return $a . (is_null($b) ? '' : ('="' . $b . '"'));
	};
	$attributevals = array_map($mapfunction, array_keys($attributes), $attributes);
	return implode(' ', $attributevals);
}

function construct_control_attributes($control_name, $extra = [], $debugfields = [])
{
	$attributes = [
		'name' => $control_name,
		'class' => 'bginput',
		'tabindex' => 1,
	];

	if ($control_name AND $debugfields !== false AND !isset($attributes['title']) AND vB::isDebug())
	{
		$titlevals = ['name' => $control_name];
		foreach($debugfields AS $field)
		{
			$titlevals[$field] = $extra[$field] ?? '';
		}

		$title = construct_attribute_string($titlevals);
		$attributes['title'] = htmlspecialchars($title);
	}

	//do this last because we always want the passed values to override anything else.
	$attributes = array_merge($attributes, $extra);
	return construct_attribute_string($attributes);
}

function construct_input($type, $control_name, $extra = [], $debugfields = [])
{
	$extra['type'] = $type;
	$attributes = construct_control_attributes($control_name, $extra, $debugfields);
	return '<input ' . $attributes . '/>';
}

function construct_select($control_name, $options, $selected, $extra = [])
{
	$attributes = construct_control_attributes($control_name, $extra);
	$options = construct_select_options($options, $selected);
	return '<select ' . $attributes . '>' . $options . '</select>';
}

function construct_jump_control($type, $id, $buttonlabel, $actions)
{
	//this isn't widely used yet but as we start standardizing jump handling on the js side it
	//either will be (or removed entirely).
	register_js_phrase('invalid_action_specified_gcpglobal');

	$dataidattr = 'data-' . $type . 'id';

	$selectattributes = [
		'id' => $type . $id,
		$dataidattr => $id,
		'class' => 'js-' . $type . '-select bginput jump-select',
	];

	$buttonattributes = [
		'type' => 'button',
		$dataidattr => $id,
		'class' => 'js-' . $type . '-go button jump-button',
		'value' => $buttonlabel,
	];

	return '<select ' . construct_attribute_string($selectattributes) . ">" . construct_select_options($actions) . '</select>' .
		'<input ' . construct_attribute_string($buttonattributes) . '/>';
}

function construct_checkbox_control($title, $control_name, $checked, $value, $extra = [], $showvaluetooltip = false)
{
	//we only want this if it's set
	if ($checked)
	{
		$extra['checked'] = 'checked';
	}

	$extra['value'] = $value;

	$labeltitle = '';
	if ($showvaluetooltip)
	{
		$labeltitle = 'title = "' . $control_name . ' = ' . $extra['value'] . '"';
	}

	$input = construct_input('checkbox', $control_name, $extra, ['value']);
	return '<label ' . $labeltitle . '>' . $input . '<span class="text">' . $title . '</span></label>';
}


function print_link_list_row($links, $colspan = 2)
{
	$lilist = [];
	foreach($links AS $url => $text)
	{
		$lilist[] = '<li><a href="' . htmlspecialchars($url) . '">' . $text . '</a></li>';
	}
	print_description_row("<ul>\n" . implode("\n", $lilist) . "\n</ul>", false, $colspan);
}

// #############################################################################
/**
* Prints a row containing form elements to input a date & time
*
* Resulting form element names: $name[day], $name[month], $name[year], $name[hour], $name[minute]
*
* @param	string	Title for row
* @param	string	Base name for form elements - $name[day], $name[month], $name[year] etc.
* @param	mixed	Unix timestamp to be represented by the form fields OR SQL date field (yyyy-mm-dd)
* @param	boolean	Whether or not to show the time input components, or only the date
* @param	boolean	If true, expect an SQL date field from the unix timestamp parameter instead (for birthdays)
* @param	string	Vertical alignment for the row
*/
function print_time_row($title, $name = 'date', $unixtime = '', $showtime = true, $birthday = false, $valign = 'middle')
{
	if (is_array($unixtime))
	{
		$unixtime = vbmktime(0, 0, 0, $unixtime['month'], $unixtime['day'], $unixtime['year']);
	}

	//make sure these get set to *something*.  We won't always have values passed.
	$month = '';
	$day = '';
	$year = '';
	$hour = '';
	$minute = '';

	if ($birthday)
	{ // mktime() on win32 doesn't support dates before 1970 so we can't fool with a negative timestamp
		if ($unixtime == '')
		{
			$month = 0;
			$day = '';
			$year = '';
		}
		else
		{
			// This seems to be expecting mm-dd-yyyy instead of yyyy-mm-dd suggested by the docblocks.
			// not touching this right now.
			$temp = explode('-', $unixtime);
			$month = intval($temp[0]);
			$day = intval($temp[1]);
			if ($temp[2] == '0000')
			{
				$year = '';
			}
			else
			{
				$year = intval($temp[2]);
			}
		}
	}
	else
	{
		if ($unixtime)
		{
			$month = vbdate('n', $unixtime, false, false);
			$day = vbdate('j', $unixtime, false, false);
			$year = vbdate('Y', $unixtime, false, false);
			$hour = vbdate('G', $unixtime, false, false);
			$minute = vbdate('i', $unixtime, false, false);
		}
	}

	$daymonthyear = [
		'year' => $year,
		'month' => $month,
		'day' => $day,
		'hour' => $hour,
		'minute' => $minute,
	];

	return print_time_row_array($title, $name, $daymonthyear, $showtime);
}

/**
* Similar to print_time_row(), but accepts an array of specific year, month & day instead of converting
* a unixtime using user or server timezones.
*
* Resulting form element names: $name[day], $name[month], $name[year], $name[hour], $name[minute]
*
* @param string  $title        Title for row label
* @param string  $name         Base name for form elements - $name[day], $name[month], $name[year] etc.
* @param mixed   $daymontyear  array of 'year', 'month', 'day'. If $showtime is true, also requires 'hour', 'minute'.
* @param boolean $showtime     Whether or not to show the time input components, or only the date
*/
function print_time_row_array($title, $name = 'date', $daymonthyear = [], $showtime = true)
{
	global $vbphrase, $vbulletin;

	// TODO: Move to headinsert?
	static $datepicker_output = false;
	if (!$datepicker_output)
	{
		echo '
			<script type="text/javascript" src="core/clientscript/vbulletin_date_picker.js?v=' . SIMPLE_VERSION . '"></script>
			<script type="text/javascript">
			<!--
				vbphrase["sunday"]    = "' . $vbphrase['sunday'] . '";
				vbphrase["monday"]    = "' . $vbphrase['monday'] . '";
				vbphrase["tuesday"]   = "' . $vbphrase['tuesday'] . '";
				vbphrase["wednesday"] = "' . $vbphrase['wednesday'] . '";
				vbphrase["thursday"]  = "' . $vbphrase['thursday'] . '";
				vbphrase["friday"]    = "' . $vbphrase['friday'] . '";
				vbphrase["saturday"]  = "' . $vbphrase['saturday'] . '";
			-->
			</script>
		';
		$datepicker_output = true;
	}

	// split input
	[
		'year' => $year,
		'month' => $month,
		'day' => $day,
	] = $daymonthyear;
	// optional
	$hour = $daymonthyear['hour'] ?? '';
	$minute = $daymonthyear['minute'] ?? '';

	$monthnames = [
		0  => '- - - -',
		1  => $vbphrase['january'],
		2  => $vbphrase['february'],
		3  => $vbphrase['march'],
		4  => $vbphrase['april'],
		5  => $vbphrase['may'],
		6  => $vbphrase['june'],
		7  => $vbphrase['july'],
		8  => $vbphrase['august'],
		9  => $vbphrase['september'],
		10 => $vbphrase['october'],
		11 => $vbphrase['november'],
		12 => $vbphrase['december'],
	];
	$control_templates = [
		'month' => '<select %1$s>' . "\n" . construct_select_options($monthnames, $month) . "\t\t</select>",
		'day'   => '<input type="text" %1$s value="' . $day . '" size="4" maxlength="2" />',
		'year'  => '<input type="text" %1$s value="' . $year . '" size="4" maxlength="4" />',
	];

	$cell = [];
	foreach($control_templates AS $tag => $template)
	{
		//hack.  Everything matches except we use date instead of day for the id.  Should probably
		//fix that but it involves fixing the JS as well.
		$control_id = $name . '_' . ($tag == 'day' ? 'date' : $tag);
		$control_name = $name . '[' . $tag . ']';

		$control_label = '<label for="' . $control_id . '">' . $vbphrase[$tag] . '</label>';
		$attributes = construct_control_attributes($control_name, ['id' => $control_id]);
		$control_html = sprintf($template, $attributes);
		$cell[] = $control_label . '<br />' . $control_html;
	}

	//need to figure out how to get this into the above format.  Not sure if the differences are a don't care or not.
	if ($showtime)
	{
		$vb5_config = vB::getConfig();
		$cell[] = $vbphrase['hour'] . '<br /><input type="text" tabindex="1" class="bginput" name="' . $name . '[hour]" id="' . $name . '_hour" value="' . $hour . '" size="4"' .
			($vb5_config['Misc']['debug'] ? " title=\"name=&quot;$name" . "[hour]&quot;\"" : '')
			. ' />';
		$cell[] = $vbphrase['minute'] . '<br /><input type="text" tabindex="1" class="bginput" name="' . $name . '[minute]" id="' . $name . '_minute" value="' . $minute . '" size="4"' .
			($vb5_config['Misc']['debug'] ? " title=\"name=&quot;$name" . "[minute]&quot;\"" : '')
			. ' />';
	}

	$inputs = '';
	foreach($cell AS $html)
	{
		$inputs .= "\t\t<td><span class=\"smallfont\">$html</span></td>\n";
	}

	$inputs .= "\t\t" . '<td><br /><span class="smallfont">' .
		'<a href="#" id="' . $name . '_insert_now">[' . $vbphrase['now'] . ']</a> ' .
		'<a href="#" id="' . $name . '_clear">[' . $vbphrase['clear'] . ']</a> ' .
		'<a href="#" id="' . $name . '_reset">[' . $vbphrase['reset'] . ']</a> ' .
		'</span></td>' . "\n";

	print_label_row(
		$title,
		"<div id=\"ctrl_$name\"><table cellpadding=\"0\" cellspacing=\"2\" border=\"0\"><tr>\n$inputs\t\n</tr></table></div>",
		'', 'top', $name
	);

	echo "<script type=\"text/javascript\"> new vB_DatePicker(\"{$name}_year\", \"{$name}_\", \"" . $vbulletin->userinfo['startofweek']  . "\"); </script>\r\n";
}


/**
 *	Allow some "magic" alignment values for some common cases
 *
 *	This simplifies some common logic we use for handling alignments in a lot of places
 *	and centralizes the logic if we decide to extend it.  Currently the magic values we
 *	recognize are "vbleft" and "vbright" which are left and right aligned unless the display
 *	is RTL in which case they are reversed.
 *
 *	Any other value is returned unchanged.
 *
 *	@param string $align
 *	@return string
 */
//this also hides the dubious calls to the Template_Runtime and reduces the number of
//places we do it for whenever we get around to fixing that.
function get_real_horizontal_alignment($align)
{
	if ($align == 'vbleft')
	{
		return vB_Template_Runtime::fetchStyleVar('left');
	}
	else if ($align == 'vbright')
	{
		return vB_Template_Runtime::fetchStyleVar('right');
	}
	else
	{
		return $align;
	}
}

//print_cells_row has *way* too many little used, ill advised, and option parameters.  Some of them, like the "$i" parameter
//are perplexing an poorly documented.  Some them, like $isheaderrow and $class are vaguely redundant.  The result is
//that calls require long line of parameters set to confusing defaults in order to access the later ones.
//
//This is an attempt to move on to better approach without having to vet all of the old uses.
//
//$isheaderrow -- this really isn't any more readable or convenient than passing "thead" as the class.
//$i -- this needs to die in a fire.  I have no idea what it's supposed to do and the comments below don't
//	really help.
//$valign -- I'm not sure why we need to vary this.
//$column -- This should really be it's own function.  The interplay between this and the normal "row based"
//	cells is confusing.  This function does not handle this case at all.
//$smallfont/$nowrap -- these are better applied to the contents in the "$array" parameter.  Possibly with
//	a help function along the lines of $array = array_map('helper', $array).  There are a lot of potential
//	classes/styles we might want to add to a wrapping span and having a param for each isn't very modular.
//$alignArray -- this should be expanded to cover potential use cases -- for instance potentially allowing for
//	usecases like having the first column be left aligned or having the outside columns left/right aligned
//	and others centered.  I *think* the $i param is intended to allow stuff like that but it's not clear.

/**
 * Prints a row containing an arbitrary number of cells, each containing arbitrary HTML
 *
 * @param	array	$cells -- Each array element contains the HTML code for one cell. If the array contains 4 elements, 4 cells will be printed
 * @param	string|array $class --  If specified, override the alternating CSS classes with the specified class(es)
 * @param	string|array  $align -- If an array, then it will use each entry for each corresponding cell (extra will be ignored, if too few then missing
 * 	cells will be treated as "blank").  This will honor the magic values from get_real_horizontal_alignment
 * 	If a scalar then the follow values are used:
 * 		* vbleft -- All cells will be left aligned in LTR.  This will automatically change to right align in RTL
 * 		* vbright -- All cells will be right aligned in LTR.  This will automatically change to left align in RTL
 * 		* vbcenter -- Center all of the cells.  Same as passing "center" but included for completeness.
 * 		* vbsplit -- The first cell will be left aligned, the last cell will be right aligned, any middle cells will not
 * 				have an explicit alignment (center aligned by default).
 * 		* Another other value -- Will be taken as an alignment value and applied to all cells, honoring magic values.
 * 	@return void
 */
function print_cells_row2($cells, $class = '', $align = 'vbsplit')
{
	$cellcount = count($cells);
	if ($cellcount)
	{
		if (is_array($align))
		{
			$alignArray = $align;
		}
		else
		{
			if ($align == 'vbsplit')
			{
				$alignArray = array_fill(0, $cellcount, '');
				$alignArray[0] = 'vbleft';
				$alignArray[$cellcount-1] = 'vbright';
			}
			else if ($align == 'vbcenter')
			{
				$alignArray = array_fill(0, $cellcount, 'center');
			}
			else
			{
				$alignArray = array_fill(0, $cellcount, $align);
			}
		}

		$alignArray = array_map('get_real_horizontal_alignment', $alignArray);

		$valign = 'top';
		$thisBgClass = '';
		$defaultBgClass = fetch_row_bgclass();
		if (!$class)
		{
			$thisBgClass = $defaultBgClass;
		}
		else
		{
			$thisBgClass = $class;
		}

		$out = "<tr valign=\"$valign\" align=\"center\">\n";

		foreach($cells AS $key => $val)
		{
			//empty table cells can display weirdly no make sure we have *something*
			if ($val == '' AND !is_int($val))
			{
				$val = '&nbsp;';
			}

			$align = '';
			if (is_array($alignArray) AND !empty($alignArray[$key]))
			{
				$align = ' align="' . $alignArray[$key] . '"';
			}

			// Allow an array of different bg classes
			if (is_array($class))
			{
				$thisBgClass = $class[$key] ?? $defaultBgClass;
			}

			$out .= "\t<td class=\"$thisBgClass\"$align>$val</td>\n";
		}

		$out .= "</tr>\n";
		echo $out;
	}
}

// #############################################################################
/**
* Prints a row containing an arbitrary number of cells, each containing arbitrary HTML
*
* @param	array	Each array element contains the HTML code for one cell. If the array contains 4 elements, 4 cells will be printed
* @param	boolean	If true, make all cells' contents bold and use the 'thead' CSS class
* @param	mixed	If specified, override the alternating CSS classes with the specified class
* @param	integer	Cell offset - controls alignment of cells... best to experiment with small +ve and -ve numbers
* @param	string	Vertical alignment for the row
* @param	boolean	Whether or not to treat the cells as part of columns - will alternate classes horizontally instead of vertically
* @param	boolean	Whether or not to use 'smallfont' for cell contents
* @param	mixed	Boolean, Whether or not to wrap text for the whole row.
* @param	array	Specify an array of booleans to choose specific columns to no-wrap
* @param	array 	Specify an array of (string) css helper classes to append to class
* @param	array	Advanced alignment control, specify an array of alignments to do column specific alignments instead of using $i
*/
function print_cells_row($array, $isheaderrow = false, $class = false, $i = 0, $valign = 'top', $column = false, $smallfont = false, $nowrap = false, $alignArray = false)
{
	global $colspan, $bgcounter;

	if (is_array($array))
	{
		$colspan = sizeof($array);
		if ($colspan)
		{
			$j = 0;
			$doecho = 0;

			if (!$class AND !$column AND !$isheaderrow)
			{
				$bgclass = fetch_row_bgclass();
			}
			elseif ($isheaderrow)
			{
				$bgclass = 'thead';
			}
			else
			{
				$bgclass = $class;
			}

			$bgcounter = iif($column, 0, $bgcounter);
			$nowrapall = (!empty($nowrap) AND !is_array($nowrap));
			$out = "<tr valign=\"$valign\" align=\"center\"" . ($nowrapall? "style=\"white-space:nowrap\"": ""). ">\n";

			foreach($array AS $key => $val)
			{
				$j++;
				if ($val == '' AND !is_int($val))
				{
					$val = '&nbsp;';
				}
				else
				{
					$doecho = 1;
				}

				if ($i++ < 1)
				{
					$align = ' align="' . vB_Template_Runtime::fetchStyleVar('left') . '"';
				}
				elseif ($j == $colspan AND $i == $colspan AND $j != 2)
				{
					$align = ' align="' . vB_Template_Runtime::fetchStyleVar('right') . '"';
				}
				else
				{
					$align = '';
				}

				if (is_array($alignArray) AND !empty($alignArray[$key]))
				{
					$align = ' align="' . $alignArray[$key] . '"';
				}

				if (!$class AND $column)
				{
					$bgclass = fetch_row_bgclass();
				}
				if ($smallfont)
				{
					$val = "<span class=\"smallfont\">$val</span>";
				}

				$style = (is_array($nowrap) AND $nowrap[$key])? "style=\"white-space:nowrap\"" : "";

				$out .= "\t<td" . iif($column, " class=\"$bgclass\"", " class=\"$bgclass\"") . "$align $style>$val</td>\n";
			}

			$out .= "</tr>\n";

			if ($doecho)
			{
				echo $out;
			}
		}
	}
}

// #############################################################################
/**
* Prints a row containing a number of <input type="checkbox" /> fields representing a user's membergroups
*
* @param	string $title-- Title for row
* @param	string $name -- Base name for checkboxes - $name[]
* @param	integer $columns --Number of columns to split checkboxes into
* @param	string|array $selectedgroupids -- the selected groups.  If it's not an array assume a CSV list as a string.
* @param	Callback $extra -- Optional callback function(int $groupid) to generate extra attributes for the checkbox input
*/
function print_membergroup_row($title, $name = 'membergroup', $columns = 0, $selectedgroupids = [], $extraCallback = null)
{
	$vb5_config = vB::getConfig();

	$uniqueid = fetch_uniqueid_counter();

	$groups = vB_Api::instanceInternal('usergroup')->fetchUsergroupList();

	if (is_string($selectedgroupids))
	{
		$selectedgroupids = explode(',', $selectedgroupids);
	}

	$options = [];
	foreach($groups AS $group)
	{
		$extra = [];
		if (is_callable($extraCallback))
		{
			$extra = $extraCallback($group);
		}
		$grouptitle = $group['title'];
		$usergroupid = $group['usergroupid'];
		$options[] = "\t\t<div>" . construct_checkbox_control($grouptitle, $name . '[]', in_array($usergroupid, $selectedgroupids), $usergroupid, $extra, true) . "</div>\n";
	}

	$class = fetch_row_bgclass();
	if ($columns > 1)
	{
		$html = "\n\t" . '<table cellpadding="0" cellspacing="0" border="0"><tr valign="top">' . "\n";
		$counter = 0;
		$totaloptions = sizeof($options);
		$percolumn = ceil($totaloptions/$columns);

		for ($i = 0; $i < $columns; $i++)
		{
			$html .= "\t<td class=\"$class\"><span class=\"smallfont\">\n";
			for ($j = 0; $j < $percolumn AND $counter < count($options); $j++)
			{
				$html .= $options[$counter++];
			}
			$html .= "\t</span></td>\n";
		}
		$html .= "</tr></table>\n\t";
	}
	else
	{
		$html = "<div id=\"ctrl_$name\" class=\"smallfont\">\n" . implode('', $options) . "\t</div>";
	}

	print_label_row($title, $html, $class, 'top', $name);
}

// #############################################################################
/**
* Prints a row containing a <select> field
*
* @param string $title -- Title for row
* @param string $name -- Name for select field
* @param array	$array -- Array of value => text pairs representing '<option value="$key">$value</option>' fields
* 		Also allows value => array construct in which case will be represented as an opt group containing that list of options.
* @param $selected string -- Selected option
* @param $htmlise boolean -- Whether or not to htmlspecialchars the text for the options
* @param $size integer -- Size of select field (non-zero means multi-line)
* @param $multiple boolean -- Whether or not to allow multiple selections
*/
function print_select_row($title, $name, $array, $selected = '', $htmlise = false, $size = 0, $multiple = false)
{
	//not sure if we still need the id.
	$uniqueid = fetch_uniqueid_counter();
	$extra = [
		'id' => 'sel_' . $name . '_' . $uniqueid,
		'size' => $size,
	];

	if ($multiple)
	{
		$extra['multiple'] = 'multiple';
	}

	//Copied from construct_select because that doesn't support the htmlise parameter.
	//the caller should really be handling this because it's pretty context dependent and
	//usually we don't want it.  But we need to make sure we don't accidentally stop escaping things.
	$attributes = construct_control_attributes($name, $extra);
	$options = construct_select_options($array, $selected, $htmlise);
	$select = '<select ' . $attributes . '>' . $options . '</select>';

	//no idea what this wrapper div is for, should see if we still need it.
	$select = '<div id="ctrl_' . $name . '">' . $select . '</div>';

	print_label_row($title, $select, '', 'top', $name);
}

// #############################################################################
/**
* Returns a list of <option> fields, optionally with one selected
*
* @param	array	Array of value => text pairs representing '<option value="$key">$value</option>' fields
* @param	string	Selected option
* @param	boolean	Whether or not to htmlspecialchars the text for the options
*
* @return	string	List of <option> tags
*/
function construct_select_options($array, $selectedid = '', $htmlise = false, $disableOthers = false)
{
	$options = '';
	if (is_array($array))
	{
		foreach($array AS $key => $val)
		{
			if (is_array($val))
			{
				$options .= "\t\t<optgroup label=\"" . ($htmlise ? vB_String::htmlSpecialCharsUni($key) : $key) . "\">\n";
				$options .= construct_select_options($val, $selectedid, $htmlise);
				$options .= "\t\t</optgroup>\n";
			}
			else
			{
				if (is_array($selectedid))
				{
					$selected = (in_array($key, $selectedid) ? ' selected="selected"' : '');
				}
				else
				{
					$selected = ($key == $selectedid ? ' selected="selected"' : '');
				}

				$disabled = '';
				if ($disableOthers AND !empty($selectedid) AND empty($selected))
				{
					$disabled = ' disabled';
				}

				$options .= "\t\t<option value=\"" . ($key !== 'no_value' ? $key : '') . "\"$selected$disabled>" . ($htmlise ? vB_String::htmlSpecialCharsUni($val) : $val) . "</option>\n";
			}
		}
	}
	return $options;
}

// #############################################################################
/**
* Prints a row containing a number of <input type="radio" /> buttons
*
* @param	string	Title for row
* @param	string	Name for radio buttons
* @param	array	Array of value => text pairs representing '<input type="radio" value="$key" />$value' fields
* @param	string	Selected radio button value
* @param	string	CSS class for <span> surrounding radio buttons
* @param	boolean	Whether or not to htmlspecialchars the text for the buttons
*/
function print_radio_row($title, $name, $array, $checked = '', $class = 'normal', $htmlise = false, $horizontal = false)
{
	$radios = "<div class=\"$class\">\n";
	$radios .= construct_radio_options($name, $array, $checked, $htmlise, '', $horizontal);
	$radios .= "\t</div>";

	print_label_row($title, $radios, '', 'top', $name);
}

// #############################################################################
/**
* Returns a list of <input type="radio" /> buttons, optionally with one selected
*
* @param	string	Name for radio buttons
* @param	array	Array of value => text pairs representing '<input type="radio" value="$key" />$value' fields
* @param	string	Selected radio button value
* @param	boolean	Whether or not to htmlspecialchars the text for the buttons
* @param	string	Indent string to place before buttons
*
* @return	string	List of <input type="radio" /> buttons
*/
function construct_radio_options($name, $array, $checkedid = '', $htmlise = false, $indent = '', $horizontal = false)
{
	$vb5_config = vB::getConfig();

	$options = '';
	if (is_array($array))
	{
		foreach($array AS $key => $val)
		{
			if (is_array($val))
			{
				//we should get rid of htmlise and require the caller to pass what it should pass.
				$displaykey = ($htmlise ? htmlspecialchars_uni($key) : $key);
				$options .= "\t\t<b>$displaykey</b><br />\n";
				$options .= construct_radio_options($name, $val, $checkedid, $htmlise, '&nbsp; &nbsp; ');
			}
			else
			{
				//we should get rid of htmlise and require the caller to pass what it should pass.
				$label = ($htmlise ? htmlspecialchars_uni($val) : $val);

				//I'm about 90% sure we don't need the id.  It's used to hook the label to the control, but
				//at least now the control is inside the label which means we don't actually need that.  I've seen
				//no hint that we reference this in the javascript (and we'd need to do some kind of prefix match
				//because the uniqueid is meaningless).  But I've already done enough poking at this for now.
				$uniqueid = fetch_uniqueid_counter();
				$controlid = 'rb_' . $name . $key . '_' . $uniqueid;
				$extra = [
					'id' => $controlid,
					//we should see if the "no_value" special is actually used.  The caller should just pass '' if that's what it means.
					'value' => ($key !== 'no_value' ? $key : ''),
				];

				if ($key == $checkedid)
				{
					$extra['checked'] = 'checked';
				}

				$input = construct_input('radio', $name, $extra, ['value']);

				$options .= "\t\t<label for=\"$controlid\">" . $indent . $input . $label . '</label>';

				if (!$horizontal)
				{
					$options .= '<br />';
				}

				$options .= "\n";
			}
		}
	}
	return $options;
}

// #############################################################################
/**
* Prints a row containing a <select> menu containing the results of a simple select from a db table
*
* NB: This will only work if the db table contains '{tablename}id' and 'title' fields
*
* @param	string	Title for row
* @param	string	Name for select field
* @param	string	Name of db table to select from
* @param	string	Value of selected option
* @param	string	Optional extra <option> for the top of the list - value is -1, specify text here
* @param	integer	Size of select field. If non-zero, shows multi-line
* @param	array	Optional assertor filter
* @param	boolean	Whether or not to allow multiple selections
* @deprecated This makes some pretty dubious assumptions and is only used for the usergroup table anyway.  Should use
*		print_select_row directly
*/
function print_chooser_row($title, $name, $tablename, $selvalue = -1, $extra = '', $size = 0, $wherecondition = [], $multiple = false)
{
	// check for cached version first...
	$cachename = strval(new vB_Context('i' . $tablename . 'cache_', $wherecondition));
	if (!isset($GLOBALS[$cachename]))
	{
		$parts = explode(':', $tablename);
		$tableid = end($parts) . 'id';
		$GLOBALS[$cachename] = vB::getDbAssertor()->getColumn($tablename, 'title', $wherecondition, 'title', $tableid);
	}

	$selectoptions = [];
	if ($extra)
	{
		$selectoptions['-1'] = $extra;
		//if attempt to select an invalid entry explicitly select the default option
		//intended mostly to simplify calls were 0 is the magic "nothing selected" option
		if (!isset($GLOBALS[$cachename][$selvalue]))
		{
			$selvalue = -1;
		}
	}

	foreach ($GLOBALS[$cachename] AS $itemid => $itemtitle)
	{
		$selectoptions[$itemid] = $itemtitle;
	}

	print_select_row($title, $name, $selectoptions, $selvalue, 0, $size, $multiple);
}

// #############################################################################
/**
* Prints a row containing a <select> list of channels, complete with displayorder, parenting and depth information
*
* @param	string	text for the left cell of the table row
* @param	string	name of the <select>
* @param	mixed	selected <option>
* @param	string	name given to the -1 <option>
* @param	boolean	display the -1 <option> or not.
* @param	boolean	when true, allows multiple selections to be made. results will be stored in $name's array
* @param	string	Text to be used in sprintf() to indicate a 'category' channel, eg: '%s (Category)'. Leave blank for no category indicator
* @param bool $skip_root -- Whether to display the top level channel.
*/
function print_channel_chooser($title, $name, $selectedid = -1, $topname = null, $displayselectchannel = false, $multiple = false, $category_phrase = null, $skip_root = false)
{
	if ($displayselectchannel AND $selectedid <= 0)
	{
		$selectedid = 0;
	}

	$channels = vB_Api::instanceInternal('search')->getChannels();

	if ($skip_root)
	{
		$channels = current($channels);
		$channels = $channels['channels'];
	}

	$selectchanneltext = '';
	if ($displayselectchannel)
	{
		global $vbphrase;
		$selectchanneltext = $vbphrase['select_channel'];
	}

	$options = construct_channel_chooser_options($channels, $selectchanneltext, $topname, $category_phrase);
	print_select_row($title, $name, $options, $selectedid, 0, $multiple ? 10 : 0, $multiple);
}

// #############################################################################
/**
* Returns a list of <option> tags representing the list of channels
*
* @param	integer	Selected channel ID
* @param	boolean	Whether or not to display the 'Select Channel' option
* @param	string	If specified, name for the optional top element - no name, no display
* @param	string	Text to be used in sprintf() to indicate a 'category' channel, eg: '%s (Category)'. Leave blank for no category indicator
*
* @return	string	List of <option> tags
*/
function construct_channel_chooser($selectedid = -1, $displayselectchannel = false, $topname = null, $category_phrase = null)
{
	$selectchanneltext = '';
	if ($displayselectchannel)
	{
		global $vbphrase;
		$selectchanneltext = $vbphrase['select_channel'];
	}

	$channels = vB_Api::instanceInternal('search')->getChannels();
	return construct_select_options(construct_channel_chooser_options($channels, $selectchanneltext, $topname, $category_phrase), $selectedid);
}


// #############################################################################
/**
* Returns a list of <option> tags representing the list of forums
*
* @param	integer	Selected forum ID
* @param	boolean	Whether or not to display the 'Select Forum' option
* @param	string	If specified, name for the optional top element - no name, no display
* @param	string	Text to be used in sprintf() to indicate a 'category' forum, eg: '%s (Category)'. Leave blank for no category indicator
*
* @return	string	List of <option> tags
*/
function construct_forum_chooser($selectedid = -1, $displayselectforum = false, $topname = null, $category_phrase = null)
{
	return construct_select_options(construct_forum_chooser_options($displayselectforum, $topname, $category_phrase), $selectedid);
}

// #############################################################################
/**
* Returns a list of <option> tags representing the list of forums
*
* @param	boolean	Whether or not to display the 'Select Forum' option
* @param	string	If specified, name for the optional top element - no name, no display
* @param	string	Text to be used in sprintf() to indicate a 'category' forum, eg: '%s (Category)'. Leave blank for no category indicator
*
* @return	array	List of <option> tags
*/
function construct_forum_chooser_options($displayselectforum = false, $topname = null, $category_phrase = null)
{
	static $vbphrase;

	if (empty($vbphrase))
	{
		$vbphrase = vB_Api::instanceInternal('phrase')->fetch(array('select_forum', 'forum_is_closed_for_posting'));
	}
	$channels = vB_Api::instanceInternal('search')->getChannels(true);
	unset($channels[1]); // Unset Home channel

	$selectoptions = array();

	if ($displayselectforum)
	{
		$selectoptions[0] = $vbphrase['select_forum'];
	}

	if ($topname)
	{
		$selectoptions['-1'] = $topname;
		$startdepth = '--';
	}
	else
	{
		$startdepth = '';
	}

	if (!$category_phrase)
	{
		$category_phrase = '%s';
	}

	foreach ($channels AS $nodeid => $channel)
	{
		$channel['title'] = vB_String::htmlSpecialCharsUni(sprintf($category_phrase, $channel['title']));

		$selectoptions["$nodeid"] = construct_depth_mark($channel['depth'] - 1, '--', $startdepth) . ' ' . $channel['title'];
	}

	return $selectoptions;
}
// #############################################################################
/**
* Returns a list of <option> tags representing the list of channels
*
* @param $channels array -- List of Channels to display
* @param $selectchanneltext string -- Whether or not to display the 'Select Channel' option.  This is never a valid value, just text
* 	to indicate a selection to be made (and to force a selection instead of resting on an implicit default).  If blank, no such option
* 	is displayed.
* @param topname string -- If specified, name for the optional top element.  This is intended as a special value such is "all channels",
* 	that doesn't correspond to a particuarl channel id.  As such, it is compatible with the previous option. If blank, no such option	is displayed.
* @param $category_phrase string -- Text to be used in sprintf() to indicate a 'category' forum, eg: '%s (Category)'. Leave blank for no category indicator
*
* @return	string	List of <option> tags
*/
function construct_channel_chooser_options($channels, $selectchanneltext = '', $topname = null, $category_phrase = null)
{
	$selectoptions = [];

	if ($selectchanneltext)
	{
		$selectoptions[0] = $selectchanneltext;
	}

	$depth = 0;
	if ($topname)
	{
		$selectoptions['-1'] = $topname;
		$depth++;
	}

	if (!$category_phrase)
	{
		$category_phrase = '%s';
	}

	$selectoptions += construct_channel_chooser_options_internal($channels, $category_phrase, $depth);
	return $selectoptions;
}

//should only be called from the main function and recursively
function construct_channel_chooser_options_internal($channels, $category_phrase, $depth)
{
	$selectoptions = [];
	foreach ($channels AS $nodeid => $channel)
	{
		//this used to check 'cancontainthreads' in the channel option.  That appears to be an obsolete
		//way of flagging a channel as a category.  This appears to be more robust.
		if ($channel['category'])
		{
			$channel['htmltitle'] = sprintf($category_phrase, $channel['htmltitle']);
		}

		$selectoptions[$nodeid] = str_repeat('--', $depth) . ' ' . vB_String::htmlSpecialCharsUni($channel['htmltitle']);
		if (!empty($channel['channels']))
		{
			$selectoptions += construct_channel_chooser_options_internal($channel['channels'], $category_phrase, $depth + 1);
		}
	}

	return $selectoptions;
}

/**
* Prints a row containing a <select> showing the available styles
*
* @param	string	Name for <select>
* @param	integer	Selected style ID
* @param	string	Name of top item in <select>
* @param	string	Title of row
* @param	boolean	Display top item?
*/
function print_style_chooser_row($name = 'parentid', $selectedid = -1, $topname = NULL, $title = NULL, $displaytop = true)
{
	global $vbphrase;

	if ($topname === NULL)
	{
		$topname = $vbphrase['no_parent_style'];
	}
	if ($title === NULL)
	{
		$title = $vbphrase['parent_style'];
	}

	$stylecache = vB_Library::instance('Style')->fetchStyles(false, false);

	$styles = [];

	$startdepth = 0;
	if ($displaytop)
	{
		$styles['-1'] = $topname;
		$startdepth = 1;
	}

	foreach($stylecache AS $style)
	{
		$styles[$style['styleid']] = construct_depth_mark($style['depth'] + $startdepth, '--') . " $style[title]";
	}

	print_select_row($title, $name, $styles, $selectedid);
}

/**
* Prints a row containing a <select> showing the available userselectable styles
*
* @param	string $title -- Title of row
* @param	string $name -- Name for <select>
* @param	integer $selectedid -- Selected style ID
* @param	string $topname -- Name of top item in <select> (omitted if blank);
*/
//note that main style chooser and this one come from different places and aren't
//completely in sync.  The one uses 0 for the id of the default "topname" value.
//the other uses -1.  It's not entirley clear why this is but we need to preserve
//prior behavior until we can sort out the calling code.
function print_user_style_chooser_row($title, $name, $selectedid = 0, $topname = '')
{
	$stylecache = vB_Library::instance('Style')->fetchStyles(false, false);

	//unlike the main style selector we don't attempt to construct the depth tree because without
	//all of the styles it's gibberish.  Just sort by title.
	$styles = [];
	foreach($stylecache AS $style)
	{
		if ($style['userselect'])
		{
			$styles[$style['styleid']] = $style['title'];
		}
	}

	uasort($styles, 'strcasecmp');

	if ($topname)
	{
		$styles = [0 => $topname] + $styles;
	}

	print_select_row($title, $name, $styles, $selectedid);
}

// #############################################################################
/**
* Returns a 'depth mark' for use in prefixing items that need to show depth in a hierarchy
*
* @param	integer	Depth of item (0 = no depth, 3 = third level depth)
* @param	string	Character or string to repeat $depth times to build the depth mark
* @param	string	Existing depth mark to append to
*
* @return	string
*/
function construct_depth_mark($depth, $depthchar, $depthmark = '')
{
	return $depthmark . str_repeat($depthchar, $depth);
}

// #############################################################################
/**
* Essentially just a wrapper for construct_help_button()
*
* @param	string	Option name
* @param	string	Action / Do name
* @param	string	Script name
* @param	integer	Help type
*
* @return	string
*/
function construct_table_help_button($option = '', $action = NULL, $script = '', $helptype = 0, $helpOptions = array())
{
	if ($helplink = construct_help_button($option, $action, $script, $helptype, $helpOptions))
	{
		return "$helplink ";
	}
	else
	{
		return '';
	}
}


function get_help_options()
{
	static $helpcache = null;
	if (is_null($helpcache))
	{
		try
		{
			$db = vB::getDbAssertor();
			$helptopics = $db->select('vBForum:adminhelp', [], false, ['script', 'action', 'optionname']);

			$helpcache = [];
			foreach($helptopics AS $helptopic)
			{
				$multactions = explode(',', $helptopic['action']);
				foreach ($multactions AS $act)
				{
					$act = trim($act);
					$helpcache[$helptopic['script']][$act][$helptopic['optionname']] = 1;
				}
			}
		}
		//this most likely means that we are calling the help from tools.php or some other
		//context where the DB isn't set up and thus the query fails.  We should really
		//avoid doing this but we don't want to crash things if it happens
		catch(Exception $e)
		{
			$helpcache = [];
		}
	}

	return $helpcache;
}

// #############################################################################
/**
* Returns a help-link button for the specified script/action/option if available
*
* @param	string	Option name
* @param	string	Action / Do name (script.php?do=SOMETHING)
* @param	string	Script name (SCRIPT.php?do=something)
* @param	integer	Help type
*
* @return	string
*/
function construct_help_button($option = '', $action = null, $script = '', $helptype = 0, $helpOptions = [])
{
	// used to make a link to the help section of the CP related to the current action
	global $vbphrase, $vbulletin;

	$helpcache = get_help_options();

	$vb5_config = vB::getConfig();

	if ($action === null)
	{
		// matches type as well (===)
		$action = $_REQUEST['do'] ?? '';
	}

	if (empty($script))
	{
		$script = $vbulletin->scriptpath;
	}

	if ($strpos = strpos($script, '?'))
	{
		$script = basename(substr($script, 0, $strpos));
	}
	else
	{
		$script = basename($script);
	}

	if ($strpos = strpos($script, '.'))
	{
		$script = substr($script, 0, $strpos); // remove the .php part as people may have different extensions
	}

	if ($option AND !isset($helpcache[$script][$action][$option]))
	{
		if (preg_match('#^[a-z0-9_]+(\[([a-z0-9_]+)\])+$#si', trim($option), $matches))
		{
			// parse out array notation, to just get index
			$option = $matches[2];
		}

		$option = str_replace('[]', '', $option);
	}

	if (!empty($helpOptions['prefix']))
	{
		$option = $helpOptions['prefix'] .  $option;
	}

	if (!$option)
	{
		if (!isset($helpcache[$script][$action]))
		{
			return '';
		}
	}
	else
	{
		if (!isset($helpcache[$script][$action][$option]))
		{
			if ($vb5_config['Misc']['debug'] AND defined('DEV_EXTRA_CONTROLS') AND DEV_EXTRA_CONTROLS)
			{
				$args = [
					'do' => 'edit',
					'option' => $option,
					'script' => $script,
					'scriptaction' => $action,
				];
				return construct_link_code2('AddHelp', get_admincp_url('help', $args));
			}
			else
			{
				return '';
			}
		}
	}

	//"case 1" is for the help link in table headers where we show the phrase.
	switch ($helptype)
	{
		case 1:
			$linkphrase = $vbphrase['help'] . ' ';
			$titlephrase = $vbphrase['click_for_help_on_these_options'];
			$style = ' style="vertical-align:middle"';
			break;

		default:
			$linkphrase = '';
			$titlephrase = $vbphrase['click_for_help_on_this_option'];
			$style = '';
			break;
	}
	$vboptions = vB::getDatastore()->getValue('options');
	$linkbody = $linkphrase . '<img src="' . get_cpstyle_image('cp_help') .
		'" alt="" border="0" title="' . $titlephrase . '"' . $style . ' />';

	//this is to allow the admincp search to link back to specific anchors when matching help text.
	$id = '';
	if ($option)
	{
		$id = $script . '_' . $action . '_' . $option;
	}

	$data = [
		'page' => $script,
		'pageaction' => $action,
		'option' => $option,
	];
 	return	construct_event_link($linkbody, 'js-helplink', $data, ['helplink'], ['id' => $id]);
}

// #############################################################################
/**
* Returns a hyperlink
*
* @param	string	Hyperlink text
* @param	string	Hyperlink URL
* @param	boolean|string If true, hyperlink target="_blank" if a string value will use that as the target
* @param	string	If specified, parameter will be used as title="x" tooltip for link
* @param	bool	include the "admincp" prefix
*
* @return string
*/
function construct_link_code($text, $url, $newwin = false, $tooltip = '', $smallfont = false, $admincp = true)
{
	if ($newwin === true OR $newwin === 1)
	{
		$newwin = '_blank';
	}

	if ($admincp)
	{
		$prefix = 'admincp/';
	}
	else
	{
		$prefix = '';
	}

	$target = '';
	if ($newwin)
	{
		$target = ' target="' . $newwin . '"';
	}

	$title = '';
	if (!empty($tooltip))
	{
		$title = ' title="' . $tooltip . '"';
	}

	$link = 	" <a href=\"" . $prefix . $url . "\"" . $target . $title . '>' .
		(vB_Template_Runtime::fetchStyleVar('textdirection') == 'rtl' ? "[$text&lrm;]</a>&rlm; " : "[$text]</a> ");

	if ($smallfont)
	{
		$link = '<span class="smallfont">' . $link . '</span>';
	}

	return $link;
}

/**
* Returns a hyperlink
*
* @param string $text
* @param string	$url
* @param boolean|string $newwin -- If true, hyperlink target="_blank" if a string value will use that as the target
* @param string $tooltip -- If specified, parameter will be used as title="x" tooltip for link
* @param string $class -- If given, wrap with the appropriate class (usually smallfont or normal)
*
* @return string -- the url element.
*/
function construct_link_code2($text, $url, $newwin = false, $tooltip = '', $class = '')
{
	if ($newwin === true OR $newwin === 1)
	{
		$newwin = '_blank';
	}

	$target = '';
	if ($newwin)
	{
		$target = ' target="' . $newwin . '"';
	}

	$title = '';
	if (!empty($tooltip))
	{
		$title = ' title="' . $tooltip . '"';
	}

	$rlmarker = '';
	if (vB_Template_Runtime::fetchStyleVar('textdirection') == 'rtl')
	{
		$text = "$text&lrm;";
		$rlmarker = '&rlm;';
	}

	$link =	' <a href="' . htmlspecialchars($url) . '"' . $target . $title . '>[' . $text . ']</a>' . $rlmarker . ' ';

	if ($class)
	{
		$link = '<span class="' . $class . '">' . $link . '</span>';
	}

	return $link;
}

function construct_translation_link($text, $phrasegroup, $phrasename)
{
	// passing the full url via the query string runs afoul of some mod_rewrite rules.
	$args = [
		'locfile' => 'phrase',
		'locparams' => [
			'do' => 'edit',
			't' => 1,
			'fieldname' => $phrasegroup,
			'varname' => $phrasename,
		],
	];

	return construct_link_code2($text, get_admincp_url('index', $args), true);
}

// #############################################################################
/**
* Returns an <input type="button" /> that acts like a hyperlink
*
* @param	string	Value for button
* @param	string	Hyperlink URL; special cases 'submit' and 'reset'
* @param	boolean	If true, hyperlink will open in a new window
* @param	string	If specified, parameter will be used as title="x" tooltip for button
* @param	boolean	If true, the hyperlink URL parameter will be treated as a javascript function call instead
*
* @deprecated use the link/event functions below and... figure something out for hte submit/reset cases.
* @return	string
*/
function construct_button_code($text, $link = '', $newwindow = false, $tooltip = '', $jsfunction = 0)
{
	if (preg_match('#^(submit|reset),?(\w+)?$#siU', $link, $matches))
	{
		$name_attribute = (isset($matches[2]) ? " name=\"$matches[2]\"" : '');
		return " <input type=\"$matches[1]\"$name_attribute class=\"button\" value=\"$text\" title=\"$tooltip\" tabindex=\"1\" />";
	}
	else
	{
		$onclick = '';
		if ($jsfunction)
		{
			$onclick = $link;
		}
		else if ($newwindow)
		{
			$onclick = "window.open('$link')";
		}
		else
		{
			$onclick = "vBRedirect('$link')";
		}

		return " <input type=\"button\" class=\"button\" value=\"$text\" title=\"$tooltip\" tabindex=\"1\" onclick=\"$onclick;\" /> ";
	}
}

/**
 * Returns an <input type="button" /> that handles an "onclick" event.
 *
 * @param string $text -- button label
 * @param	string $link -- The url to link to on click
 * @param	boolean $newwindow -- Whether to open the link in a new window or redirect the current page.
 * @param	array $extraclasses -- Extra class values for the button if needed
 * @param array $extraattributes -- everything could be passed here but
 * 	this is intended for any stray attributes not part of the "standard" values
 * @return string -- the html for the button.
 */
function construct_link_button($text, $link, $newwindow = false, $extraclasses = [], $extraattributes = [])
{
	return construct_event_button($text, ($newwindow ? 'js-link-newwindow' : 'js-link'), ['href' => $link], $extraclasses, $extraattributes);
}

/**
 * Returns an <input type="button" /> that handles an "onclick" event.
 *
 * @param string $text -- button label
 * @param	string $actionclass -- The class that will trigger the event registration.
 * @param	array $data -- Whether to open the link in a new window or redirect the current page.
 * @param	array $extraclasses -- Extra class values for the button if needed
 * @param array $extraattributes -- everything could be passed here but
 * 	this is intended for any stray attributes not part of the "standard" values
 *
 * @return string -- the html for the button.
 */
function construct_event_button($text, $actionclass, $data = [], $extraclasses = [], $extraattributes = [])
{
	$extraclasses[] = $actionclass;
	return construct_button_base($text, $extraclasses, $data, $extraattributes);
}

/**
 * Returns submit button
 *
 * @param string $text -- button label
 * @param	array $extraclasses -- Extra class values for the button if needed
 * @param array $extraattributes -- everything could be passed here but
 * 	this is intended for any stray attributes not part of the "standard" values
 *
 * @return string -- the html for the button.
 */
function construct_submit_button($text, $extraclasses = [], $extraattributes = [])
{
	$extraattributes['type'] = 'submit';
	return construct_button_base($text, $extraclasses, [], $extraattributes);
}

/**
 * Returns an <input type="button" />
 *
 * Intended as a helper function for more friendly button construction functions
 * Also used in some of the standard function to construct typical buttons.
 * Not intended to be used outside of this file
 *
 * @private
 * @param string $text -- button label
 * @param array $data -- attributes.  The data prefix will be appended.
 * @param	array $classes -- class values
 * @param array $extraattributes -- everything could be passed here but
 * 	this is intended for any stray attributes not part of the "standard" values
 *
 * @return string -- the html for the button.
 */
function construct_button_base($text, $classes, $data, $extraattributes)
{
	$classes[] = 'button';

	$attributes = [
		'type' => 'button',
		'class' => implode(' ', $classes),
		'value' => $text,
		'tabindex' => 1,
	];
	$attributes = array_merge($attributes, $extraattributes);

	$stringUtil = vB::getString();
	foreach($data AS $name => $dataitem)
	{
		$attributes['data-' . $name] = $stringUtil->htmlspecialchars($dataitem);
	}

	return '<input ' . construct_attribute_string($attributes) . '/> ';
}

function construct_event_link($text, $actionclass, $data = [], $extraclasses = [], $extraattributes = [])
{
	$extraclasses[] = $actionclass;
	$extraclasses[] = 'nohref';

	$attributes = [
		'class' => implode(' ', $extraclasses),
		'tabindex' => 1,
	];
	$attributes = array_merge($attributes, $extraattributes);


	$stringUtil = vB::getString();
	foreach($data AS $name => $dataitem)
	{
		$attributes['data-' . $name] = $stringUtil->htmlspecialchars($dataitem);
	}

	//We don't include the href here because we don't want force the event handlers to skip the
	//default handling (there isn't a good way to do this in general) which facilitates switching
	//between event buttons and event links. Instead we add a class that mimics a link (anchors
	//without an href are styled differently).
	return '<a ' . construct_attribute_string($attributes) . '>' . $text . '</a>';
}

/**
* Checks whether or not the visiting user has administrative permissions
*
* This function can optionally take any number of parameters, each of which
* should be a particular administrative permission you want to check. For example:
* can_administer('canadminsettings', 'canadminstyles', 'canadminlanguages')
* If any one of these permissions is met, the function will return true.
*
* If no parameters are specified, the function will simply check that the user is an administrator.
*
* @deprecated use vB_UserContext::hasAdminPermission
* @return	boolean
*/
function can_administer()
{
	global $vbulletin;

	static $superadmins;

	$vb5_config =& vB::getConfig();

	if (!is_array($superadmins))
	{
		$superadmins = preg_split('#\s*,\s*#s', $vb5_config['SpecialUsers']['superadmins'], -1, PREG_SPLIT_NO_EMPTY);
	}

	$do = func_get_args();
	$userContext = vB::getUserContext();

	if ($vbulletin->userinfo['userid'] < 1)
	{
		// user is a guest - definitely not an administrator
		return false;
	}
	else if (!$userContext->isAdministrator())
	{
		// user is not an administrator at all
		return false;
	}
	else if ($userContext->isSuperAdmin())
	{
		// user is a super administrator (defined in config.php) so can do anything
		return true;
	}
	else if (empty($do))
	{
		// user is an administrator and we are not checking a specific permission
		return true;
	}

	// final bitfield check on each permission we are checking
	// This is opposite of the documentation and the code we refactored this from.
	// It will return false unless the admin has *ever* permission passed rather than *any* permission.
	// However I think it's effectively a don't care because we don't pass multiple permissions anywhere in the current code
	// Given that we should be moving away from this function anyway it's not worth the time/risk to fix this.
	foreach($do AS $field)
	{
		if (!$userContext->hasAdminPermission($field))
		{
			return false;
		}
	}

	// Legacy Hook 'can_administer' Removed //

	// if we got this far then there is no permission, unless the hook says so
	return true;
}

// #############################################################################
/**
* Halts execution and prints an error message stating that the administrator does not have permission to perform this action
*
* @param	string	This parameter is no longer used
* @return never
*/
function print_cp_no_permission($do = '')
{
	if (!defined('DONE_CPHEADER'))
	{
		global $vbphrase;
		print_cp_header($vbphrase['vbulletin_message']);
	}

	$args = [
		'do' => 'edit',
		'userid' => vB::getCurrentSession()->get('userid'),
	];
	print_stop_message2(['no_access_to_admin_control', get_admincp_href('adminpermissions', $args)]);
}

// #############################################################################
/**
* Saves data into the adminutil table in the database
*
* @param	string	Name of adminutil record to be saved
* @param	string	Data to be saved into the adminutil table
*
* @return	void
*/
function build_adminutil_text($title, $text = '')
{
	$db = vB::getDbAssertor();

	if ($text == '')
	{
		$db->delete('adminutil', ['title' => $title]);
	}
	else
	{
		$db->assertQuery('vBAdmincp:updateAdminUtil', ['title' => $title, 'text' => $text]);
	}
}

// #############################################################################
/**
* Returns data from the adminutil table in the database
*
* @param	string	Name of the adminutil record to be fetched
*
* @return	string
*/
function fetch_adminutil_text($title)
{
	$text = vB::getDbAssertor()->getRow('adminutil', ['title' => $title]);
	return $text['text'] ?? '';
}

// #############################################################################
/**
* Halts execution and prints a Javascript redirect function to cause the browser to redirect to the specified page
*
* @param	string	Redirect target URL
* @param	float	Time delay (in seconds) before the redirect will occur
* @return never
* @deprecated use print_cp_redirect
*/
function print_cp_redirect_old($gotopage, $timeout = 0)
{
	// performs a delayed javascript page redirection
	// get rid of &amp; if there are any...
	global $vbphrase;
	$gotopage = str_replace('&amp;', '&', $gotopage);

	if (!empty($gotopage) && ((($hashpos = strpos($gotopage, '#')) !== false) OR (($hashpos = strpos($gotopage, '%23')) !== false)))
	{
		$hashsize = (strpos($gotopage, '#') !== false) ? 1 : 3;
		$hash = substr($gotopage, $hashpos + $hashsize);
		$gotopage = substr($gotopage, 0, $hashpos);
	}

	$gotopage = create_full_url($gotopage);
	$gotopage = str_replace('"', '', $gotopage);
	if (!empty($hash))
	{
		$gotopage .= '#'.$hash;
	}

	if ($timeout == 0)
	{
		echo '<p align="center" class="smallfont"><a href="' . $gotopage . '">' . $vbphrase['processing_complete_proceed'] . '</a></p>';
		echo "\n<script type=\"text/javascript\">\n";
		echo "window.location=\"$gotopage\";";
		echo "\n</script>\n";
	}
	else
	{
		echo '<p align="center" class="smallfont"><a href="' . $gotopage . '">' . $vbphrase['processing_complete_proceed'] . '</a></p>';
		echo "\n<script type=\"text/javascript\">\n";
		echo "setTimeout(() => {window.location=\"$gotopage\";}, " . ($timeout * 1000). ");";
		echo "\n</script>\n";
	}
	print_cp_footer();
	exit;
}

/**
 * @return never
 * @deprecated use print_cp_redirect and get_admincp_url
 */
function print_cp_redirect2($file, $extra = [], $timeout = 0, $route = 'admincp')
{
	print_cp_redirect_old(get_redirect_url($file, $extra, $route), $timeout);
}

// #############################################################################
/**
* Halts execution and prints a Javascript redirect function to cause the browser to redirect to the specified page
*
* @param	string	Redirect target URL -- this should *not* be html encoded
* @param	float	Time delay (in seconds) before the redirect will occur
* @return never
*/
function print_cp_redirect($gotopage, $timeout = 0, $message = '')
{
	global $vbphrase;
	if (empty($message))
	{
		$message = $vbphrase['processing_complete_proceed'];
	}

	//create_full_url is old and weird.  Let's just check to see if this is an absolute url and
	//go with it.  We should probably mostly have a fully qualified url from get_redirect_url/get_admincp_url
	//(which is the most likely source of $goto page).  And we probably don't need it anymore anyway.
	if (strtolower(substr($gotopage, 0, 4)) != 'http')
	{
		$gotopage = vB::getDatastore()->getOption('frontendurl') . '/' . ltrim($gotopage, '/');
	}

	//the auto redirect is handled in the JS page initialization.
	$gotopagehtml = htmlentities($gotopage);
	echo '<p align="center" class="smallfont">' .
		'<a class = "js-page-redirect" href="' . $gotopagehtml . '" data-timeout="' . floatval($timeout) . '">' . $message . '</a>' .
	'</p>';
	print_cp_footer();
	exit;
}

/**
 * Prints a form that auto submits.  Used to handle cases where we need a post redirect.
 *
 * @param	string $phpscript -- PHP script to which the form will submit (omit file suffix)
 * @param	string $do -- 'do' action for target script
 * @param	string $submitname -- label for submit button
 * @param float $timeout -- Time delay (in seconds) before the redirect will occur
 * @param array $fields -- array of name/value pairs to be sent as hidden fields.
 * @return never
 */
function print_form_redirect(string $phpscript, string $do, string $submitname, float $timeout = 0, array $fields = [])
{
	print_form_header2($phpscript, $do, ['js-page-redirect'], ['data-timeout' => $timeout]);
	print_table_start2();
	construct_hidden_codes_from_params($fields);
	print_table_default_footer($submitname);
	print_cp_footer();
	exit;
}

// #############################################################################
/**
* Prints a block of HTML containing a character that multiplies in width via javascript - a kind of progress meter
*
* @param	string	Text to be printed above the progress meter
* @param	string	Character to be used as the progress meter
* @param	string	Name to be given as the id for the HTML element containing the progress meter
*/
function print_dots_start($text, $dotschar = ':', $elementid = 'dotsarea')
{
	if (defined('NO_IMPORT_DOTS'))
	{
		return;
	}

	vbflush(); ?>
	<p align="center"><?php echo $text; ?><br /><br />[<span class="progress_dots" id="<?php echo $elementid; ?>"><?php echo $dotschar; ?></span>]</p>
	<script type="text/javascript"><!--
	function js_dots()
	{
		<?php echo $elementid; ?>.innerText = <?php echo $elementid; ?>.innerText + "<?php echo $dotschar; ?>";
		jstimer = setTimeout("js_dots();", 75);
	}
	if (document.all)
	{
		js_dots();
	}
	//--></script>
	<?php vbflush();
}

// #############################################################################
/**
* Prints a javascript code block that will halt the progress meter started with print_dots_start()
*/
function print_dots_stop()
{
	if (defined('NO_IMPORT_DOTS'))
	{
		return;
	}

	vbflush(); ?>
	<script type="text/javascript"><!--
	if (document.all)
	{
		clearTimeout(jstimer);
	}
	//--></script>
	<?php vbflush();
}

// #############################################################################
/**
* Writes data to a file
*
* @param	string	Path to file (including file name)
* @param	string	Data to be saved into the file
* @param	boolean	If true, will create a backup of the file called {filename}old
*/
function file_write($path, $data, $backup = false)
{
	if (file_exists($path) != false)
	{
		if ($backup)
		{
			$filenamenew = $path . 'old';
			rename($path, $filenamenew);
		}
		else
		{
			unlink($path);
		}
	}
	if ($data != '')
	{
		$filenum = fopen($path, 'w');
		fwrite($filenum, $data);
		fclose($filenum);
	}
}

// #############################################################################
/**
* Returns the contents of a file
*
* @param	string	Path to file (including file name)
*
* @return	string	If file does not exist, returns an empty string
*/
function file_read($path)
{
	// On some versions of PHP under IIS, file_exists returns false for uploaded files,
	// even though the file exists and is readable. http://bugs.php.net/bug.php?id=38308
	if (!file_exists($path) AND !is_uploaded_file($path))
	{
		return '';
	}
	else
	{
		$filestuff = @file_get_contents($path);
		return $filestuff;
	}
}

// #############################################################################
/**
* Saves a log into the adminlog table in the database
*
* @param	string	Extra info to be saved
* @param	integer	User ID of the visiting user
* @param	string	Name of the script this log applies to
* @param	string	Action / Do branch being viewed
*/
function log_admin_action($extrainfo = '', $userid = -1, $script = '', $scriptaction = '')
{
	// logs current activity to the adminlog db table

	if ($userid == -1)
	{
		$userInfo = vB::getCurrentSession()->fetch_userinfo();
		$userid = $userInfo['userid'];
	}
	if (empty($script))
	{
		$script = !empty($_SERVER['REQUEST_URI']) ? basename(strtok($_SERVER['REQUEST_URI'],'?')) : basename($_SERVER['PHP_SELF']);
	}
	if (empty($scriptaction))
	{
		$scriptaction = $_REQUEST['do'];
	}

	vB::getDbAssertor()->assertQuery('vBForum:adminlog',
			array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_INSERT,
					'userid' => $userid,
					'dateline' => TIMENOW,
					'script' => $script,
					'action' => $scriptaction,
					'extrainfo' => $extrainfo,
					'ipaddress' => IPADDRESS,
			)
	);
}

// #############################################################################
/**
* Checks whether or not the visiting user can view logs
*
* @param	string	Comma-separated list of user IDs permitted to view logs
* @param	boolean	Variable to return if the previous parameter is found to be empty
* @param	string	Message to print if the user is NOT permitted to view
*
* @return	boolean
*/
function can_access_logs($idvar, $defaultreturnvar = false, $errmsg = '')
{
	if (empty($idvar))
	{
		return $defaultreturnvar;
	}
	else
	{
		$perm = trim($idvar);
		$logperms = explode(',', $perm);
		$userinfo = vB::getCurrentSession()->fetch_userinfo();
		if (in_array($userinfo['userid'], $logperms))
		{
			return true;
		}
		else
		{
			echo $errmsg;
			return false;
		}
	}
}

// #############################################################################
/**
* Prints a dialog box asking if the user is sure they want to delete the specified item from the database
*
* @param	string	Name of table from which item will be deleted
* @param	mixed		ID of item to be deleted
* @param	string	PHP script to which the form will submit
* @param	string	'do' action for target script
* @param	string	Word describing item to be deleted - eg: 'forum' or 'user' or 'post' etc.
* @param	mixed		If not empty, an array containing name=>value pairs to be used as hidden input fields
* @param	string	Extra text to be printed in the dialog box
* @param	string	Name of 'title' field in the table in the database
* @param	string	Name of 'idfield' field in the table in the database
* @deprecated
*/
//this function tries to do *way* too much. print_confirmation isn't a drop in replacement but it does pretty much
//everything that the caller shouldn't be doing.  But the caller should be doing a lot more.
function print_delete_confirmation($table, $itemid, $phpscript, $do, $itemname = '', $hiddenfields = 0, $extra = '', $titlename = 'title', $idfield = '')
{
	global $vbphrase;

	$idfield = $idfield ? $idfield : $table . 'id';
	$itemname = $itemname ? $itemname : $table;
	$deleteword = 'delete';
	$encodehtml = true;
	$assertor = vB::getDbAssertor();

	switch($table)
	{
		case 'infraction':
			$item = vB_Library::instance('content_infraction')->getInfraction($itemid);
			$item['title'] = (!empty($item) AND isset($item['title'])) ? $item['title'] : '';
			break;
		case 'reputation':
			$item = $assertor->getRow('vBForum:reputation', array('reputationid' => $itemid));
			$item['title'] = (!empty($item) AND isset($item['reputationid'])) ? $item['reputationid'] : '';
			break;
		case 'user':
			$item = $assertor->getRow('user', array('userid' => $itemid));
			$item['title'] = (!empty($item) AND isset($item['username'])) ? $item['username'] : '';
			break;
		case 'moderator':
			$item = $assertor->getRow('vBForum:getModeratorBasicFields', array('moderatorid' => $itemid));
			$item['title'] = construct_phrase($vbphrase['x_from_the_forum_y'], $item['username'], $item['title']);
			$encodehtml = false;
			break;
		case 'calendarmoderator':
			$item = $assertor->getRow('vBForum:getCalendarModeratorBasicFields', array('calendarmoderatorid' => $itemid));
			$item['title'] = construct_phrase($vbphrase['x_from_the_calendar_y'], $item['username'], $item['title']);
			$encodehtml = false;
			break;
		case 'phrase':
			$item = $assertor->getRow('vBForum:phrase', array('phraseid' => $itemid));
			$item['title'] = (!empty($item) AND isset($item['varname'])) ? $item['varname'] : '';
			break;
		case 'userpromotion':
			$item = $assertor->getRow('getUserPromotionBasicFields', array('userpromotionid' => $itemid));
			break;
		case 'setting':
			$item = $assertor->getRow('setting', array('varname' => $itemid));
			$item['title'] = (!empty($item) AND isset($item['varname'])) ? $item['varname'] : '';
			$idfield = 'title';
			break;
		case 'settinggroup':
			$item = $assertor->getRow('settinggroup', array('grouptitle' => $itemid));
			$item['title'] = (!empty($item) AND isset($item['grouptitle'])) ? $item['grouptitle'] : '';
			$idfield = 'title';
			break;
		case 'adminhelp':
			$item = $assertor->getRow('vBForum:getAdminHelpBasicFields', array('adminhelpid' => $itemid));
			break;
		case 'faq':
			$item = $assertor->getRow('vBForum:getFaqBasicFields', array('faqname' => $itemid));
			$idfield = 'faqname';
			break;
		case 'hook':
			$item = $assertor->getRow('hook', array('hookid' => $itemid));
			$item['title'] = (!empty($item) AND isset($item['title'])) ? $item['title'] : '';
			break;
		case 'product':
			$item = $assertor->getRow('product', array('productid' => $itemid));
			$item['title'] = (!empty($item) AND isset($item['title'])) ? $item['title'] : '';
			break;
		case 'prefix':
			$item = $assertor->getRow('vBForum:prefix', array('prefixid' => $itemid));
			$item['title'] = (!empty($item['prefixid'])) ? $vbphrase["prefix_$item[prefixid]_title_plain"] : '';
			break;
		case 'prefixset':
			$item = $assertor->getRow('vBForum:prefixset', array('prefixsetid' => $itemid));
			$item['title'] = (!empty($item['prefixsetid'])) ? $vbphrase["prefixset_$item[prefixsetid]_title"] : '';
			break;
		case 'stylevar':
			$item = $assertor->getRow('vBForum:stylevar', array('stylevarid' => $itemid));
			break;
		case 'notice':
			$handled = false;
			// Legacy Hook 'admin_delete_confirmation' Removed //
			if (!$handled)
			{
				$item = $assertor->getRow('vBForum:notice', array($idfield => $itemid));
				$item['title'] = (!empty($item) AND isset($item[$titlename])) ? $item[$titlename] : '';
			}
			break;
		case 'eventhighlight':
			$item = vB_Api::instanceInternal('eventhighlight')->getEventHighlightAdmin($itemid);
			$item['title'] = (!empty($item) AND isset($item['name'])) ? $item['name'] : '';
			break;
		default:
			$item = $assertor->getRow($table, array($idfield => $itemid));
			$item['title'] = (!empty($item) AND isset($item[$titlename])) ? $item[$titlename] : '';
			break;
	}

	switch($table)
	{
		case 'template':
			if ($itemname == 'replacement_variable')
			{
				$deleteword = 'delete';
			}
			else
			{
				$deleteword = 'revert';
			}
		break;

		case 'adminreminder':
			if (vbstrlen($item['title']) > 30)
			{
				$item['title'] = substr($item['title'], 0, 30) . '...';
			}
		break;

		case 'vBForum:subscription':
			$item['title'] = (!empty($item['subscriptionid'])) ? $vbphrase['sub' . $item['subscriptionid'] . '_title'] : '';
		break;

		case 'stylevar':
			$item['title'] = (!empty($item['stylevarid'])) ? $vbphrase['stylevar_' . $item['stylevarid'] . '_name'] : '';

			//Friendly names not
			if (!$item['title'])
			{
				$item['title'] = $item[$idfield];
			}

			$deleteword = 'revert';
		break;
	}

	if ($encodehtml
		AND (strcspn($item['title'], '<>"') < strlen($item['title'])
			OR (strpos($item['title'], '&') !== false AND !preg_match('/&(#[0-9]+|amp|lt|gt|quot);/si', $item['title']))
		)
	)
	{
		// title contains html entities that should be encoded
		$item['title'] = htmlspecialchars_uni($item['title']);
	}

	if (!empty($item[$idfield]) AND $item[$idfield] == $itemid AND $itemid)
	{
		//some records need to be "doidfield" in the query/post data to avoid magic handling
		$hidden = (is_array($hiddenfields) ? $hiddenfields : []);
		$hiddenfieldname = (($idfield == 'styleid' OR $idfield == 'languageid') ? 'do' . $idfield : $idfield);
		$hidden[$hiddenfieldname] = $itemid;

		$header = construct_phrase($vbphrase['confirm_deletion_x'], $item['title']);
		$description = construct_phrase(
			$vbphrase["are_you_sure_want_to_{$deleteword}_{$itemname}_x"],
			$item['title'],
			$idfield,
			$item["$idfield"],
			($extra ? "$extra<br /><br />" : '')
		);

		print_confirmation($vbphrase, $phpscript, $do, $description, $hidden, $header);
	}
	else
	{
		print_stop_message2(['could_not_find', '<b>' . $itemname . '</b>', $idfield, $itemid]);
	}
}

/**
* Prints a form asking if the user if they want to continue
*
* @param array $vbphrase -- the vbphrase array for the page.
* @param string $phpscript -- do not include the extension
* @param string $do
* @param string $description -- text for the box.
* @param array $hiddenfields -- An array containing name=>value pairs to be used as hidden input fields
* @param string $header -- The form header.  Defaults to "Confirm Action" if not passed.
*/
//The formatting for this needs looking at.  Not only is a little off, it relies a lot on random markup
//instead of proper styling -- and blockquote is not only pretty obsolete but absolutely not what this is.
function print_confirmation($vbphrase, $phpscript, $do, $description, $hiddenfields = [], $header = null)
{
	if (is_null($header))
	{
		$header = $vbphrase['confirm_action'];
	}

	echo "<p>&nbsp;</p><p>&nbsp;</p>";
	//this used to have a 75% width on it, but that hasn't worked for a long time, should we restore it?
	print_form_header2("admincp/$phpscript", $do);
	print_table_start2();
	if (is_array($hiddenfields))
	{
		foreach($hiddenfields AS $varname => $value)
		{
			construct_hidden_code($varname, $value);
		}
	}
	print_table_header($header);
	print_description_row("
		<blockquote><br />
		$description
		<br /></blockquote>\n\t");

	$buttons = [
		['submit', $vbphrase['yes']],
		['goback', $vbphrase['no']],
	];

	print_table_button_footer($buttons);
}

/**
 * Turn the filename, extra params into a url
 *
 * @param  string Admin CP file name
 * @param  array  Array of key=>value pairs for query params. A #fragament can be included
 *                by using '#' for the key.
 * @param  string Route name
 *
 * @return string URL
 * @deprecated Use get_admincp_url for admincp links.  Create additional functions for other use cases.
 */
function get_redirect_url($file, $extra, $route = 'admincp')
{
	/*
		Remove preceding "$route/" (ex. "admincp/" and succeeding ".php", only leaving the filename.
		Since the admincp ROUTE is used to create the URL, the prefix & file extension must not be in the $file route data.
	 */
	if (empty($route))
	{
		// Route creation will flop if no route's given. This is actually a *caller* bug, but let's be nice and put up a default.
		$route = 'admincp';
	}
	$file = preg_replace('#^' . preg_quote($route, '#'). '/|\.php$#si', '', $file);
	$vb5_options = vB::getDatastore()->getValue('options');

	$fragment = '';
	if (!empty($extra['#']))
	{
		$fragment = $extra['#'];
		unset($extra['#']);
	}

	if (strpos(VB_URL, $vb5_options['bburl']) !== false)
	{
		$redirect = $file . '.php?' . http_build_query($extra) . ($fragment ? "#$fragment" : '');
	}
	else
	{
		$redirect = vB5_Route::buildUrl($route . '|fullurl', ['file' => $file], $extra, $fragment);
	}
	return $redirect;
}


/**
 * Turn the filename, extra params into a url
 *
 * This is just a quick cover for vB5_Route::buildUrl to reduce some common code
 *
 * @param  string $file -- The admincp filename without any path or extension or other decoration
 * @param  array  $extra -- Array of key=>value pairs for query params.
 * @param  string $fragment -- A fragment for the url
 *
 * @return string
 */
function get_modcp_url(string $file, array $extra, string $fragment = '') : string
{
	return vB5_Route::buildUrl('modcp|fullurl', ['file' => $file], $extra, $fragment);
}

/**
 * Turn the filename, extra params into a url
 *
 * This is just a quick cover for vB5_Route::buildUrl to reduce some common code
 *
 * @param  string $file -- The admincp filename without any path or extension or other decoration
 * @param  array  $extra -- Array of key=>value pairs for query params.
 * @param  string $fragment -- A fragment for the url
 *
 * @return string
 */
//quick cover for admin routes.  Remove a lot of the wierd cruft of get_redirect_url including
// * specifying the route.  This is handled badly since most of the uses are for the admincp
// 	so we default that but then it isn't clear that the param is available from reading the code
// 	we can create additional functions (or just use the vB5_Route directly for one offs)
// * wierd mixing of the fragment with the query params
// * removing the route from the filename, let's just be consistent about how this is supposed to work
// * Whatever the VB_URL check does.
// * Rename it to be clear that it's usable for other than redirects.  Too many admincp urls are
// 	still hand crafted instead of using the route system.
function get_admincp_url(string $file, array $extra, string $fragment = '') : string
{
	return vB5_Route::buildUrl('admincp|fullurl', ['file' => $file], $extra, $fragment);
}

/**
 *	A cover on get_admincp_url that returns the url escaped for direct insertion in html
 *
 * 	@param  string $file -- The admincp filename without any path or extension or other decoration
 * 	@param  array  $extra -- Array of key=>value pairs for query params.
 * 	@param  string $fragment -- A fragment for the url
 *
 * 	@return string
 */
function get_admincp_href(string $file, array $extra, string $fragment = '') : string
{
	//Skip the extra function call because get_admincp_url isn't exactly complicated.  We can
	//if we need to.  This should always return the results of get_admincp_url escaped.
	return htmlspecialchars(vB5_Route::buildUrl('admincp|fullurl', ['file' => $file], $extra, $fragment));
}

/**
 *	@return never
 *	@deprecated use print_stop_message.  May need an analog to get_admincp_url for the modcp.
 */
function print_modcp_stop_message2($phrase, $file = null, $extra = [], $backurl = null, $continue = false)
{
	return print_stop_message2($phrase, $file, $extra, $backurl, $continue, 'modcp');
}

function print_stop_message_on_api_error($result)
{
	if (isset($result['errors']))
	{
		print_stop_message_array($result['errors']);
	}
}

/**
 *	@return never
 */
function print_stop_message_array($phrases, $redirecturl = '', $backurl = null, $continue = false) : never
{
	$phrases = vB_Api::instanceInternal('phrase')->renderPhrases($phrases);
	$message = implode('<br/><br/>', $phrases['phrases']);

	//todo -- figure out where this is needed and remove.
	global $vbulletin;
	if (!empty($vbulletin->GPC['ajax']))
	{
		$xml = new vB_XML_Builder_Ajax('text/xml');
		$xml->add_tag('error', $message);
		$xml->print_xml();
	}

	//todo -- figure out where this is needed and remove.
	if (defined('VB_AREA') AND VB_AREA == 'Upgrade')
	{
		echo $message;
		exit;
	}

	print_cp_message(
		$message,
		$redirecturl,
		1,
		$backurl,
		$continue
	);
}

/**
 *	@return never
 *	@deprecated use print_stop_message and a link generator function such as get_admincp_url
 */
function print_stop_message2($phrase, $file = null, $extra = [], $backurl = null, $continue = false, $redirect_route = 'admincp') : never
{
	//handle phrase as a string
	if (!is_array($phrase))
	{
		$phrase = [$phrase];
	}

	$phrases = vB_Api::instanceInternal('phrase')->renderPhrases([$phrase]);
	$message = reset($phrases['phrases']);

	//todo -- figure out where this is needed and remove.
	global $vbulletin;
	if (!empty($vbulletin->GPC['ajax']))
	{
		$xml = new vB_XML_Builder_Ajax('text/xml');
		$xml->add_tag('error', $message);
		$xml->print_xml();
	}

	//todo -- figure out where this is needed and remove.
	if (defined('VB_AREA') AND VB_AREA == 'Upgrade')
	{
		echo $message;
		exit;
	}

	$redirect = '';
	$hash = '';
	if ($file)
	{
		if (!empty($extra['#']))
		{
			$hash = '#' . $extra['#'];
			unset($extra['#']);
		}
		$redirect = get_redirect_url($file, $extra, $redirect_route);
	}

	print_cp_message(
		$message,
		$redirect . $hash,
		1,
		$backurl,
		$continue
	);
}

/**
 *	@return never
 */
function print_stop_message(array|string $phrase, string $redirecturl = '', ?string $backurl = null, bool $continue = false) : never
{
	print_stop_message_array([$phrase], $redirecturl, $backurl, $continue);
}

// #############################################################################
/**
* Halts execution and shows the specified message
*
* @param	string	Message to display
* @param	mixed	If specified, a redirect will be performed to the URL in this parameter
* @param	integer	If redirect is specified, this is the time in seconds to delay before redirect
* @param	string	If specified, will provide a specific URL for "Go Back". If empty, no button will be displayed!
* @param bool		If true along with redirect, 'CONTINUE' button will be used instead of automatic redirect
* @return never
*/
function print_cp_message($text = '', $redirect = NULL, $delay = 1, $backurl = NULL, $continue = false)
{
	global $vbulletin, $vbphrase;

	if (!empty($vbulletin->GPC['ajax']))
	{
		$xml = new vB_XML_Builder_Ajax('text/xml');
		$xml->add_tag('error', $text);
		$xml->print_xml();
		exit;
	}

	if ($redirect)
	{
		if ((($hashpos = strpos($redirect, '#')) !== false) OR (($hashpos = strpos($redirect, '%23')) !== false))
		{
			$hashsize = (strpos($redirect, '#') !== false) ? 1 : 3;
			$hash = substr($redirect, $hashpos + $hashsize);
			$redirect = substr($redirect, 0, $hashpos);
		}
	}

	if (!defined('DONE_CPHEADER'))
	{
		print_cp_header($vbphrase['vbulletin_message']);
	}

	print_form_header('admincp/', '', 0, 1, 'messageform', '65%');
	print_table_header($vbphrase['vbulletin_message']);
	print_description_row("<blockquote><br />$text<br /><br /></blockquote>");

	if ($redirect)
	{
		// redirect to the new page
		if ($continue)
		{
			$continueurl = create_full_url(str_replace('&amp;', '&', $redirect));
			if (!empty($hash))
			{
				$continueurl .= '#'.$hash;
			}
			print_table_footer(2, construct_button_code($vbphrase['continue'], $continueurl));
		}
		else
		{
			print_table_footer();

			$redirect = create_full_url($redirect);
			if (!empty($hash))
			{
				$redirect .= '#' . $hash;
			}
			$redirect_click = str_replace('"', '', $redirect);

			echo '<p align="center" class="smallfont">' . construct_phrase($vbphrase['if_you_are_not_automatically_redirected_click_here_x'], $redirect_click) . "</p>\n";
			print_cp_redirect($redirect, $delay);
		}
	}
	else
	{
		// end the table and halt
		if ($backurl === NULL)
		{
			$backurl = 'javascript:history.back(1)';
		}

		if (strpos($backurl, 'history.back(') !== false)
		{
			//if we are attempting to run a history.back(1), check we have a history to go back to, otherwise attempt to close the window.
			$back_button = '&nbsp;
				<input type="button" id="backbutton" class="button" value="' . $vbphrase['go_back'] . '" title="" tabindex="1" onclick="if (history.length) { history.back(1); } else { self.close(); }"/>
				&nbsp;
				<script type="text/javascript">
				<!--
				if (history.length < 1 || ((is_saf || is_moz) && history.length <= 1)) // safari + gecko start at 1
				{
					document.getElementById("backbutton").parentNode.removeChild(document.getElementById("backbutton"));
				}
				//-->
				</script>';

			// remove the back button if it leads back to the login redirect page
			if (strpos($vbulletin->url ?? '', 'login.php?do=login') !== false)
			{
				$back_button = '';
			}
		}
		else if ($backurl !== '')
		{
			// regular window.location=url call
			$backurl = create_full_url($backurl);
			$backurl = str_replace(array('"', "'"), '', $backurl);
			//todo: replace these with get_goto_button()
			$back_button = '<input type="button" class="button" value="' . $vbphrase['go_back'] . '" title="" tabindex="1" onclick="window.location=\'' . $backurl . '\';"/>';
		}
		else
		{
			$back_button = '';
		}

		print_table_footer(2, $back_button);
	}

	// and now terminate the script
	print_cp_footer();
}

function get_goto_button($file, $extra = array(), $route = 'admincp', $label = 'go_back')
{
	$vbphrase = vB_Api::instanceInternal('phrase')->fetch($label);
	$label = htmlentities($vbphrase[$label] ?? $label);
	$backurl = get_redirect_url($file, $extra, $route);
	$backurl = htmlentities($backurl);
	return
		'<input
			type="button"
			class="button"
			value="' . $label . '"
			title=""
			tabindex="1"
			onclick="window.location=\'' . $backurl . '\';"
		/>';
}

/**
* Verifies the CP sessionhash is sent through with the request to prevent
* an XSS-style issue.
*
* @param	boolean	Whether to halt if an error occurs
* @param	string	Name of the input variable to look at
*
* @return	boolean	True on success, false on failure
*/
function verify_cp_sessionhash($halt = true, $input = 'hash')
{
	global $vbulletin;

	assert_cp_sessionhash();

	if (!isset($vbulletin->GPC["$input"]))
	{
		$vbulletin->input->clean_array_gpc('r', array(
			$input => vB_Cleaner::TYPE_STR
		));
	}

	if ($vbulletin->GPC["$input"] != CP_SESSIONHASH)
	{
		if ($halt)
		{
			print_stop_message2('security_alert_hash_mismatch');
		}
		else
		{
			return false;
		}
	}

	return true;
}

/**
 * Defines a valid CP_SESSIONHASH.
 */
function assert_cp_sessionhash()
{
	if (defined('CP_SESSIONHASH'))
	{
		return;
	}

	$options = vB::getDatastore()->getValue('options');
	$userId = vB::getCurrentSession()->get('userid');
	$timeNow = vB::getRequest()->getTimeNow();
	$assertor = vB::getDbAssertor();

	global $vbulletin;
	$vbulletin->input->clean_array_gpc('c', [
		COOKIE_PREFIX . 'cpsession' => vB_Cleaner::TYPE_STR,
	]);
	$hashval = $vbulletin->GPC[COOKIE_PREFIX . 'cpsession'];

	$cpsessionhash = null;
	if ($hashval)
	{
		$timecut = ($options['timeoutcontrolpanel'] ? intval($timeNow - $options['cookietimeout']) : intval($timeNow - 3600));
		$cpsession = $assertor->getRow('cpsession', [
			vB_dB_Query::CONDITIONS_KEY => [
				'userid' => $userId,
				'hash' => $hashval,
				['field' => 'dateline', 'value' => $timecut, 'operator' => vB_dB_Query::OPERATOR_GT],
			]
		]);

		if (!empty($cpsession))
		{
			$assertor->assertQuery('cpSessionUpdate', [
				'timenow' => $timeNow,
				'userid' => $userId,
				'hash' => $hashval,
			]);

			$cpsessionhash = $cpsession['hash'];
		}
	}

	//not sure if we should really be setting these as null, but that's the old behavior
	//if we don't end up getting a cpsession record and not defining the CP_SESSIONHASH
	//could cause wierd behavior.
	vB::getCurrentSession()->setCpsessionHash($cpsessionhash);
	define('CP_SESSIONHASH', $cpsessionhash);
}

// #############################################################################
/**
* Returns an array of timezones, keyed with their offset from GMT
*
* @return	array	Timezones array
*/
function fetch_timezones_array()
{
	global $vbphrase;

	return array(
		'-12'  => $vbphrase['timezone_gmt_minus_1200'],
		'-11'  => $vbphrase['timezone_gmt_minus_1100'],
		'-10'  => $vbphrase['timezone_gmt_minus_1000'],
		'-9.5' => $vbphrase['timezone_gmt_minus_0930'],
		'-9'   => $vbphrase['timezone_gmt_minus_0900'],
		'-8'   => $vbphrase['timezone_gmt_minus_0800'],
		'-7'   => $vbphrase['timezone_gmt_minus_0700'],
		'-6'   => $vbphrase['timezone_gmt_minus_0600'],
		'-5'   => $vbphrase['timezone_gmt_minus_0500'],
		'-4.5' => $vbphrase['timezone_gmt_minus_0430'],
		'-4'   => $vbphrase['timezone_gmt_minus_0400'],
		'-3.5' => $vbphrase['timezone_gmt_minus_0330'],
		'-3'   => $vbphrase['timezone_gmt_minus_0300'],
		'-2'   => $vbphrase['timezone_gmt_minus_0200'],
		'-1'   => $vbphrase['timezone_gmt_minus_0100'],
		'0'    => $vbphrase['timezone_gmt_plus_0000'],
		'1'    => $vbphrase['timezone_gmt_plus_0100'],
		'2'    => $vbphrase['timezone_gmt_plus_0200'],
		'3'    => $vbphrase['timezone_gmt_plus_0300'],
		'3.5'  => $vbphrase['timezone_gmt_plus_0330'],
		'4'    => $vbphrase['timezone_gmt_plus_0400'],
		'4.5'  => $vbphrase['timezone_gmt_plus_0430'],
		'5'    => $vbphrase['timezone_gmt_plus_0500'],
		'5.5'  => $vbphrase['timezone_gmt_plus_0530'],
		'5.75' => $vbphrase['timezone_gmt_plus_0545'],
		'6'    => $vbphrase['timezone_gmt_plus_0600'],
		'6.5'  => $vbphrase['timezone_gmt_plus_0630'],
		'7'    => $vbphrase['timezone_gmt_plus_0700'],
		'8'    => $vbphrase['timezone_gmt_plus_0800'],
		'8.5'  => $vbphrase['timezone_gmt_plus_0830'],
		'8.75' => $vbphrase['timezone_gmt_plus_0845'],
		'9'    => $vbphrase['timezone_gmt_plus_0900'],
		'9.5'  => $vbphrase['timezone_gmt_plus_0930'],
		'10'   => $vbphrase['timezone_gmt_plus_1000'],
		'10.5'  => $vbphrase['timezone_gmt_plus_1030'],
		'11'   => $vbphrase['timezone_gmt_plus_1100'],
		'12'   => $vbphrase['timezone_gmt_plus_1200']
	);
}

// #############################################################################
/**
* Reads all data from the specified image table and writes the serialized data to the datastore
*
* @param	string	Name of image table (avatar/icon/smilie)
*/
function build_image_cache($table)
{
	global $vbulletin;

	if ($table == 'avatar')
	{
		return;
	}

	$itemid = $table.'id';
	if ($table == 'smilie')
	{
		// the smilie cache is basically only used for parsing; displaying smilies comes from a query
		$items = $vbulletin->db->query_read("
			SELECT *, LENGTH(smilietext) AS smilielen
			FROM " . TABLE_PREFIX . "$table
			WHERE LENGTH(TRIM(smilietext)) > 0
			ORDER BY smilielen DESC
		");
	}
	else
	{
		$items = $vbulletin->db->query_read("SELECT * FROM " . TABLE_PREFIX . "$table ORDER BY imagecategoryid, displayorder");
	}

	$itemarray = array();

	while ($item = $vbulletin->db->fetch_array($items))
	{
		$itemarray["$item[$itemid]"] = array();
		foreach ($item AS $field => $value)
		{
			if (!is_numeric($field))
			{
				$itemarray["$item[$itemid]"]["$field"] = $value;
			}
		}
	}

	build_datastore($table . 'cache', serialize($itemarray), 1);
}

// #############################################################################
/**
* Reads all data from the bbcode table and writes the serialized data to the datastore
*/
function build_bbcode_cache()
{
	global $vbulletin;
	$bbcodes = $vbulletin->db->query_read("
		SELECT *
		FROM " . TABLE_PREFIX . "bbcode
	");
	$bbcodearray = array();
	while ($bbcode = $vbulletin->db->fetch_array($bbcodes))
	{
		$bbcodearray["$bbcode[bbcodeid]"] = array();
		foreach ($bbcode AS $field => $value)
		{
			if (!is_numeric($field))
			{
				$bbcodearray["$bbcode[bbcodeid]"]["$field"] = $value;

			}
		}

		$bbcodearray["$bbcode[bbcodeid]"]['strip_empty'] = (intval($bbcode['options']) & $vbulletin->bf_misc['bbcodeoptions']['strip_empty']) ? 1 : 0 ;
		$bbcodearray["$bbcode[bbcodeid]"]['stop_parse'] = (intval($bbcode['options']) & $vbulletin->bf_misc['bbcodeoptions']['stop_parse']) ? 1 : 0 ;
		$bbcodearray["$bbcode[bbcodeid]"]['disable_smilies'] = (intval($bbcode['options']) & $vbulletin->bf_misc['bbcodeoptions']['disable_smilies']) ? 1 : 0 ;
		$bbcodearray["$bbcode[bbcodeid]"]['disable_wordwrap'] = (intval($bbcode['options']) & $vbulletin->bf_misc['bbcodeoptions']['disable_wordwrap']) ? 1 : 0 ;
	}

	build_datastore('bbcodecache', serialize($bbcodearray), 1);
}

// #############################################################################
/**
* Prints a <script> block that allows you to call js_open_phrase_ref() from Javascript
*
* @param	integer	ID of initial language to be displayed
* @param	integer	ID of initial phrasetype to be displayed
* @param	integer	Pixel width of popup window
* @param	integer	Pixel height of popup window
*/
function print_phrase_ref_popup_javascript()
{
	$width = 700;
	$height = 202;

	echo "<script type=\"text/javascript\">\n<!--
	function js_open_phrase_ref(languageid,fieldname)
	{
		var qs = '';
		if (languageid != 0) qs += '&languageid=' + languageid;
		if (fieldname != '') qs += '&fieldname=' + fieldname;
		window.open('admincp/phrase.php?do=quickref' + qs, 'quickref', 'width=$width,height=$height,resizable=yes');
	}\n// -->\n</script>\n";
}
// #############################################################################

function get_disabled_perms($usergroup)
{
	$datastore = vB::getDatastore();
	$bf_ugp_generic = $datastore->getValue('bf_ugp_genericpermissions');
	$bf_ugp_signature = $datastore->getValue('bf_ugp_signaturepermissions');

	$disabled = [];
	// Avatars disabled so don't inherit any of the avatar settings
	if (!($usergroup['genericpermissions'] & $bf_ugp_generic['canuseavatar']))
	{
		$disabled['avatarmaxwidth'] = -1;
		$disabled['avatarmaxheight'] = -1;
		$disabled['avatarmaxsize'] = -1;
	}

	// Signature pics or signatures are disabled so don't inherit any of the signature pic settings
	if (
		!($usergroup['signaturepermissions'] & $bf_ugp_signature['cansigpic']) OR
		!($usergroup['genericpermissions'] & $bf_ugp_generic['canusesignature'])
	)
	{
		$disabled['sigpicmaxwidth'] = -1;
		$disabled['sigpicmaxheight'] = -1;
		$disabled['sigpicmaxsize'] = -1;
	}

	// Signatures are disabled so don't inherit any of the signature settings
	if (!($usergroup['genericpermissions'] & $bf_ugp_generic['canusesignature']))
	{
		$disabled['sigmaxrawchars'] = -1;
		$disabled['sigmaxchars'] = -1;
		$disabled['sigmaxlines'] = -1;
		$disabled['sigmaxsizebbcode'] = -1;
		$disabled['sigmaximages'] = -1;
		$disabled['signaturepermissions'] = 0;
	}
	return $disabled;
}

// #############################################################################
/**
* Returns a string safe for use in Javascript code
*
* @param	string	Text to be made safe
* @param	string	Quote type to be used in Javascript (either ' or ")
*
* @return	string
*/
function fetch_js_safe_string($object, $quotechar = '"')
{
	$find = array(
		"\r\n",
		"\n",
		'"'
	);

	$replace = array(
		'\r\n',
		'\n',
		"\\$quotechar",
	);

	$object = str_replace($find, $replace, $object);

	return $object;
}

// #############################################################################
/**
* Returns a string safe for use in Javascript code
*
* @param	string	Text to be made safe
* @param	string	Quote type to be used in Javascript (either ' or ")
*
* @return	string
*/
function fetch_js_unsafe_string($object, $quotechar = '"')
{
	$find = array(
		'\r\n',
		'\n',
		"\\$quotechar",
	);

	$replace = array(
		"\r\n",
		"\n",
		"$quotechar",
	);

	$object = str_replace($find, $replace, $object);

	return $object;
}

// #############################################################################
/**
* Returns an array of folders containing control panel CSS styles
*
* Styles are read from /path/to/vbulletin/cpstyles/
*
* @return	array
*/
function fetch_cpcss_options()
{
	$folders = array();

	if ($handle = @opendir(DIR . '/cpstyles'))
	{
		while ($folder = readdir($handle))
		{
			if ($folder == '.' OR $folder == '..')
			{
				continue;
			}
			if (is_dir(DIR . "/cpstyles/$folder") AND @file_exists(DIR . "/cpstyles/$folder/controlpanel.css"))
			{
				$folders["$folder"] = $folder;
			}
		}
		closedir($handle);
		uksort($folders, 'strnatcasecmp');
		$folders = str_replace('_', ' ', $folders);
	}

	return $folders;
}

// ############################## Start vbflush ####################################
/**
* Force the output buffers to the browser
*/
function vbflush()
{
	static $gzip_handler = null;
	if ($gzip_handler === null)
	{
		$gzip_handler = false;
		$output_handlers = ob_list_handlers();
		if (is_array($output_handlers))
		{
			foreach ($output_handlers AS $handler)
			{
				if ($handler == 'ob_gzhandler')
				{
					$gzip_handler = true;
					break;
				}
			}
		}
	}

	if ($gzip_handler)
	{
		// forcing a flush with this is very bad
		return;
	}

	if (ob_get_length() !== false)
	{
		@ob_flush();
	}
	flush();
}

// ############################## Start fetch_product_list ####################################
/**
* Returns an array of currently installed products. Always includes 'vBulletin'.
*
* @param	boolean	If true, SELECT *, otherwise SELECT productid, title
* @param	boolean	Allow a previously cached version to be used
*
* @deprecated use the getFullProducts/getProductTitles function in vB_Library_Product
* @return	array
*/
function fetch_product_list($alldata = false, $use_cached = true)
{
	if ($alldata)
	{
		static $all_data_cache = false;

		if ($all_data_cache === false)
		{
			$productlist = array(
				'vbulletin' => array(
					'productid' => 'vbulletin',
					'title' => 'vBulletin',
					'description' => '',
					'version' => vB::getDatastore()->getOption('templateversion'),
					'active' => 1
				)
			);

			$products = vB::getDbAssertor()->assertQuery('vBForum:fetchproduct');
			foreach ($products as $product)
			{
				$productlist["$product[productid]"] = $product;
			}

			$all_data_cache = $productlist;
		}
		else
		{
			$productlist = $all_data_cache;
		}
	}
	else
	{
		$productlist = array(
			'vbulletin' => 'vBulletin'
		);

		$products = vB::getDbAssertor()->assertQuery('vBForum:fetchproduct');
		foreach ($products as $product)
		{
			$productlist["$product[productid]"] = $product['title'];
		}
	}

	return $productlist;
}

// ############################## Start build_product_datastore ####################################
/**
* Stores the list of currently installed products into the datastore.
*/
function build_product_datastore()
{
	$products = array('vbulletin' => 1);

	$productList = vB::getDbAssertor()->getRows('product', array(vB_dB_Query::COLUMNS_KEY => array('productid', 'active')));

	foreach ($productList AS $product)
	{
		$products[$product['productid']] = $product['active'];
	}

	vB::getDatastore()->build('products', serialize($products), 1);
}

/**
* Checks userid is a user that shouldn't be editable
*
* @param	integer	userid to check
*
* @return	boolean
*/
function is_unalterable_user($userid)
{
	global $vbulletin;

	static $noalter = null;

	$vb5_config =& vB::getConfig();

	if (!$userid)
	{
		return false;
	}

	if ($noalter === null)
	{
		$noalter = explode(',', $vb5_config['SpecialUsers']['undeletableusers'] ?? '');

		if (!is_array($noalter))
		{
			$noalter = array();
		}
	}

	return in_array($userid, $noalter);
}

/**
* Resolves an image URL used in the CP that should be relative to the root directory.
*
* @param	string	The path to resolve
*
* @return	string	Resolved path
*/
function resolve_cp_image_url($image_path, $baseurl_core = false)
{
	if ($image_path[0] == '/' OR preg_match('#^https?://#i', $image_path))
	{
		return $image_path;
	}
	else if ($baseurl_core)
	{
		// Use bburl for post icons (match contententry_title template)
		return vB::getDatastore()->getOption('bburl') . "/$image_path";
	}
	else
	{
		return vB::getDatastore()->getOption('frontendurl') . "/$image_path";
	}
}

/**
 * Prints a standard table with a warning/notice
 *
 * @param	Message to print
 */
function print_warning_table($message)
{
	print_table_start();
	print_description_row($message, false, 2, 'warning');
	print_table_footer(2, '', '', false);
}

/**
* Returns HTML for a link to a specific setting/option
*
* @param	array	$setting    Setting data, typically a row from the `setting` table.
*								Must have the grouptitle & varname keys
*/
function get_setting_link($setting, $text = "", $tooltip = "")
{
	$grouptitle = $setting['grouptitle'];
	$varname = $setting['varname'];
	$phrases = get_setting_phrases($setting['product']);
	if (empty($text))
	{
		$text = $phrases["setting_{$varname}_title"];
		$text = (empty($text) ? $varname : $text);
		$tooltip = htmlentities($phrases["setting_{$varname}_desc"]);
	}

	// passing the full url via the query string runs afoul of some mod_rewrite rules.
	$args = [
		'locfile' => 'options',
		'locparams' => [
			'do' => 'options',
			'varname' => $varname,
		],
	];

	return construct_link_code2($text, get_admincp_url('index', $args), true, $tooltip);
}

function get_setting_phrases($product)
{
	// query settings phrases
	static $settingphrase = array();
	if (!isset($settingphrase[$product]))
	{
		$settingphrase[$product] = array();
		$phrases = vB::getDbAssertor()->assertQuery('vBForum:phrase',
				array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
					'fieldname' => 'vbsettings',
					'languageid' => array(-1, 0, LANGUAGEID),
					'product' => $product,
				),
				array('field' => 'languageid', 'direction' => vB_dB_Query::SORT_ASC)
		);
		if ($phrases AND $phrases->valid())
		{
			foreach ($phrases AS $phrase)
			{
				$settingphrase[$product]["$phrase[varname]"] = $phrase['text'];
			}
		}
	}
	return $settingphrase[$product];
}

function get_cpstyle_href($file)
{
	$userinfo = vB_User::fetchUserinfo(0, array('admin'));
	return 'core/cpstyles/' . $userinfo['cssprefs'] . '/' . $file;
}

function get_cpstyle_image($name)
{
	$ext =  vB::getDatastore()->getOption('cpstyleimageext');
	return get_cpstyle_href($name . '.' . ($ext == 'default' ? 'svg' : $ext));
}

function get_log_paging_html($pagenumber, $totalpages, $baseUrl, $query, $phrases)
{
	$firstpage = '';
	$prevpage = '';
	$nextpage = '';
	$lastpage = '';

	$buttontemplate = '<input type="button" class="button" value="%1$s" tabindex="1" onclick="vBRedirect(\'%2$s\');">';

	if ($pagenumber != 1)
	{
		$query['page'] = 1;
		$url = htmlspecialchars_uni($baseUrl . http_build_query($query));
		$firstpage = sprintf($buttontemplate, '&laquo; ' . $phrases['first_page'], $url);

		$query['page'] = $pagenumber - 1;
		$url = htmlspecialchars_uni($baseUrl . http_build_query($query));
		$prevpage = sprintf($buttontemplate, '&lt; ' . $phrases['prev_page'], $url);
	}

	if ($pagenumber != $totalpages)
	{
		$query['page'] = $pagenumber + 1;
		$url = htmlspecialchars_uni($baseUrl . http_build_query($query));
		$nextpage = sprintf($buttontemplate, $phrases['next_page']  . ' &gt;', $url);

		$query['page'] = $totalpages;
		$url = htmlspecialchars_uni($baseUrl . http_build_query($query));
		$lastpage = sprintf($buttontemplate, $phrases['last_page']  . ' &raquo;', $url);
	}

	return "$firstpage $prevpage &nbsp; $nextpage $lastpage";
}

function print_channel_permission_rows($customword, $channelpermission = [], $options = [])
{
	$phraseApi = vB_Api::instanceInternal('phrase');
	$vbphrase = $phraseApi->fetch([
		'all_yes',
		'all_no',
		'yes',
		'no',
		'yes_but_not_parsing_html',
		'ignore',
		'ignore_all',
		'unignore_all',
	]);
	// also load up all the various permission bitfield phrases that are spread out in various phrase groups
	$vbphrase = array_merge($vbphrase, $phraseApi->fetchByGroup([
		'global',
		'cpglobal',
		'cppermission',
	]));


	$check_all_template = 'js_check_all_option(this.form, %d);';
	if (!empty($options['add_customized_perm_check']))
	{
		$check_all_template = 'if (js_set_custom()) {' . $check_all_template . '}';
	}

	$all_yes = sprintf($check_all_template, 1);
	$all_no = sprintf($check_all_template, 0);

	$ignoreAll = '';
	if (!empty($options['add_disable_checkbox']))
	{
		$ignoreAll = <<<EOT
		<input type="button"
			class="button"
			value="{$vbphrase['ignore_all']}"
			onclick="js_uncheck_on_row_change(true, true)"
			/>
		<input type="button"
			class="button"
			value="{$vbphrase['unignore_all']}"
			onclick="js_uncheck_on_row_change(false, true)"
			 />
EOT;
		// Also toggle all ignores off if we're doing all changes.
		// We might walk this back depending on how people use these buttons.
		$all_yes .= "\njs_uncheck_on_row_change(false, true);";
		$all_no .=  "\njs_uncheck_on_row_change(false, true);";
	}

	// we can't put the channel-permission-row on the tr for this without some refactor, so just wrap it over and over
	$headerButtons = <<<EOT
		<div class="channel-permission-row">
			<div class="value-column">
				<div class="value-wrapper">
					<div>
						<input type="button" class="button" value="{$vbphrase['all_yes']}" onclick="$all_yes"  />
						<input type="button" class="button" value="{$vbphrase['all_no']}" onclick="$all_no"  />
					</div>
					<div>$ignoreAll</div>
				</div>
				<div class="helplink-wrapper">
					<a class="helplink" href="#" onclick="return false;">&nbsp;</a>
				</div>
			</div>
		</div>
EOT;

	print_label_row("<b>$customword</b>", $headerButtons, 'tcat', 'middle');

	// Load permissions
	require_once(DIR . '/includes/class_bitfield_builder.php');

	$bitvalues = array('forumpermissions', 'forumpermissions2', 'moderatorpermissions', 'createpermissions');
	$permFields = vB_ChannelPermission::fetchPermFields();
	$permPhrases = vB_ChannelPermission::fetchPermPhrases();

	if (empty($channelpermission))
	{
		// we need the defaults to be displayed
		$channelpermission = vB_ChannelPermission::instance()->fetchPermissions(1);
		$channelpermission = current($channelpermission);
	}

	//list the special fields and map them to the group name they belong to.
	$specialnonbitfields = [
		'maxattachments' => 'attachment_permissions_gcppermission',
	];

	$specialfields = [
		'canpostattachment' => 'attachment_permissions_gcppermission',
		'cangetimgattachment' => 'attachment_permissions_gcppermission',
		'cangetattachment' => 'attachment_permissions_gcppermission',
		'canseethumbnails' => 'attachment_permissions_gcppermission',
	];

	$special = [];
	//don't show anything we've marked as special in the main list.
	foreach($specialnonbitfields AS $permtitle => $group)
	{
		$info = [
			'phrase' => $permPhrases[$permtitle],
			'value' => $channelpermission[$permtitle],
			'permgroup' => '',
			'permtitle' => $permtitle,
			'type' => $permFields[$permtitle],
		];

		$special[$group][$permtitle] = $info;

		//remove this field from the normal handling
		unset($permFields[$permtitle]);
	}

	//look up the bitfield values.
	//This is a bit complicated because I'm trying to merge two completely different sources
	//without changing them.  And there aren't enough keys to do it cleaning.
	foreach($permFields AS $permField => $type)
	{
		if ($type == vB_ChannelPermission::TYPE_BITMAP)
		{
			foreach ($channelpermission['bitfields'][$permField] AS $key => $permBit)
			{
				$permtitle = $permBit['name'];
				if (isset($specialfields[$permtitle]))
				{
					$info = [
						'phrase' => $permBit['phrase'],
						'value' => $permBit['set'],
						'permgroup' => $permField,
						'permtitle' => $permtitle,
						'type' => vB_ChannelPermission::TYPE_BOOL,
					];

					$special[$specialfields[$permtitle]][$permtitle] = $info;

					//don't show this in the main list
					unset($channelpermission['bitfields'][$permField][$key]);
				}
			}
		}
	}

	//Do the non-bitmap fields first.
	foreach($permFields AS $permField => $type)
	{
		if ($type != vB_ChannelPermission::TYPE_BITMAP)
		{
			print_channel_permission_row(
				$vbphrase[$permPhrases[$permField]],
				// Non bitfield perms don't use their "group" names, and it's not
				// really worth looking them up ...
				'',
				$permField,
				$channelpermission[$permField],
				$type,
				$options,
				$vbphrase
			);
		}
	}

	//handle the special cases
	foreach ($special AS $group => $perms)
	{
		print_table_header($vbphrase[$group]);

		foreach($perms AS $permName => $permBit)
		{
			print_channel_permission_row(
				$vbphrase[$permBit['phrase']],
				$permBit['permgroup'],
				$permBit['permtitle'],
				$permBit['value'],
				$permBit['type'],
				$options,
				$vbphrase
			);
		}
	}

	//now do the bitmaps
	foreach($permFields AS $permField => $type)
	{
		if ($type == vB_ChannelPermission::TYPE_BITMAP)
		{
			if ($permField !== 'forumpermissions2')
			{
				print_table_header($vbphrase[$permPhrases[$permField]]);
			}

			foreach ($channelpermission['bitfields'][$permField] AS $permBit )
			{
				if ($permBit['used'])
				{
					$helpOptions = [];
					if ($permField == 'moderatorpermissions')
					{
						if (empty($permBit['phrase']))
						{
							$permBit['phrase'] = "moderator_add_edit_" . $permBit['name'] . "_title";
						}

						if ($permBit['name'] == 'canopenclose')
						{
							$helpOptions = array('prefix' => $permField);
						}
					}
					$options['helpOptions'] = $helpOptions;

					$phrase = $vbphrase[$permBit['phrase']] ?? $permBit['phrase'];
					print_channel_permission_row(
						$phrase,
						$permField,
						$permBit['name'],
						$permBit['set'],
						$type,
						$options,
						$vbphrase
					);
				}
			}
		}
	}
}

function print_channel_permission_row($phrase, $permGroup, $permName, $permValue, $permType, $options, $vbphrase)
{
	switch ($permType)
	{
		case vB_ChannelPermission::TYPE_BITMAP:
		case vB_ChannelPermission::TYPE_BOOL:
			$inputtype = 'yesno';
			$rawname = "{$permGroup}[{$permName}]";
			// in forumpermission.php, we need to be able to map some ignore[abc][xyz] input to a
			// permission input, but for whatever reason, we do not group the non-"bit type"
			// permissions by their permission group.
			$ignoreName = "ignore[{$permGroup}][{$permName}]";
			break;
		case vB_ChannelPermission::TYPE_HOURS:
		case vB_ChannelPermission::TYPE_COUNT:
		default:
			$inputtype = 'input';
			// Input boxes, for whateve reason, do not group by their perm group names.
			$rawname = $permName;
			$ignoreName = "ignore[{$rawname}]";
			break;
	}

	$title = $phrase;
	$name = htmlspecialchars_uni($rawname);
	$value = htmlspecialchars_uni($permValue);
	$size = intval($options['size'] ?? 35);
	$maxlength = intval($options['maxlength'] ?? 0);
	$direction = verify_text_direction($options['direction'] ?? '');
	$inputclass = htmlspecialchars_uni($options['inputclass'] ?? 'bginput');
	$colspan = [1,1];

	$vb5_config = vB::getConfig();


	$extraInput = '';
	if (!empty($options['add_disable_checkbox']))
	{
		$ignoreName = htmlspecialchars_uni($ignoreName);
		$extraInput = <<<EOT

		<label>
			<input type="checkbox"
				class="js-uncheck-on-row-change"
				data-trigger-name="$name"
				name="$ignoreName"
				value="1"
				checked
			>{$vbphrase['ignore']}</label>
EOT;
		$inputclass .= ' js-uncheck-on-row-change--trigger';
	}

	if (!empty($options['add_customized_perm_check']))
	{
		$inputclass .= ' js-set-custom-on-row-change--trigger';
	}

	if ($inputtype == 'input')
	{
		$id = htmlspecialchars_uni('channelPerm_' . $rawname);
		$value = "<div id=\"ctrl_$name\" class=\"\">
				<input type=\"text\"
					class=\"$inputclass\"
					name=\"$name\"
					id=\"$id\"
					value=\"$value\"
					size=\"$size\"
					" .	($maxlength ? " maxlength=\"$maxlength\"" : '') . "
					dir=\"$direction\"
					tabindex=\"1\"" .
			($vb5_config['Misc']['debug'] ? " title=\"name=&quot;$name&quot;\"" : '') . " /></div>";
	}
	else
	{
		// yesno
		$onclick = '';
		if (!empty($options['onclick']))
		{
			$onclick = ' onclick="' . $options['onclick']  . '"';
		}

		$rtlmarker = (vB_Template_Runtime::fetchStyleVar('textdirection') == 'rtl' ? '&rlm;' : '');
		$uniqueid = fetch_uniqueid_counter();

		$inputid = [];
		$debugtitle = array_fill(0, 2, '');
		for($i = 0; $i <= 1; $i++)
		{
			$inputid[$i] = "rb_{$i}_{$name}_$uniqueid";
			if ($vb5_config['Misc']['debug'])
			{
				$debugtitle[$i] = ' title="' . htmlspecialchars('name="' . $name . '" value="' . $i . '"') . '"';
			}
		}

		$checked = array_fill(0, 2, '');
		$checked[($value ? 1 : 0)] = ' checked="checked"';

		$value = <<<EOT
			<div id="ctrl_$name" class="smallfont" style="white-space:nowrap">
			<label for="{$inputid[1]}">
				<input type="radio" name="$name" id="{$inputid[1]}" value="1" tabindex="1"
					class="$inputclass"
					$onclick{$debugtitle[1]}{$checked[1]}
				/>{$vbphrase['yes']}$rtlmarker</label>
			<label for="{$inputid[0]}">
				<input type="radio" name="$name" id="{$inputid[0]}" value="0" tabindex="1"
					class="$inputclass"
					$onclick{$debugtitle[0]}{$checked[0]}
				/>{$vbphrase['no']}$rtlmarker</label>
			</div>
EOT;
	}
	$valign = 'top';
	$helpname = $name;
	$helpOptions = $options['helpOptions'] ?? [];

	$class = fetch_row_bgclass();
	$helpbutton = construct_table_help_button($helpname, NULL, '', 0, $helpOptions);

	// Add a right-most column consistently regardless of whether this row's help exists or not.
	if (!$helpbutton)
	{
		$helpbutton = '<a class="helplink" href="#" onclick="return false;">&nbsp;</a>';
	}
	$value = "<div class=\"value-wrapper\">$value{$extraInput}</div><div class=\"helplink-wrapper\">$helpbutton</div>";


	$colattr = [];
	foreach($colspan as $col)
	{
		if ($col < 1)
		{
			$colattr[] = '';
		}
		else
		{
			$colattr[] = ' colspan="' . $col . '" ';
		}
	}

	echo "<tr valign=\"$valign\" class=\"channel-permission-row\">";
	echo "<td class=\"$class title-column\"" .  $colattr[0] . ">$title</td>";
	// This might look weird, but we need to push the display:flex inside of td because otherwise, certain rows that have
	// taller td.title-column heights (e.g. ones with label dfn's) will make the value column not fill the full height.
	echo "<td class=\"$class\"" .  $colattr[1] . "><div class=\"value-column\">$value</div></td>\n</tr>\n";
}

function verify_upload_folder($attachpath)
{
	if ($attachpath == '')
	{
		print_stop_message2('please_complete_required_fields');
	}

	// Get realpath.
	$test = realpath($attachpath);

	if (!$test)
	{
		// If above fails, try relative path instead.
		$test = realpath(DIR . DIRECTORY_SEPARATOR . $attachpath);
	}

	if (!is_dir($test) OR !is_writable($test))
	{
		print_stop_message2(array('test_file_write_failed',  $attachpath));
	}

	if (!is_dir($test . '/test'))
	{
		@umask(0);
		if (!@mkdir($test . '/test', 0777))
		{
			print_stop_message2(array('test_file_write_failed',  $attachpath));
		}
	}

	@chmod($test . '/test', 0777);

	if ($fp = @fopen($test . '/test/test.attach', 'wb'))
	{
		fclose($fp);
		if (!@unlink($test . '/test/test.attach'))
		{
			print_stop_message2(array('test_file_write_failed',  $attachpath));
		}
		@rmdir($test . '/test');
	}
	else
	{
		print_stop_message2(array('test_file_write_failed',  $attachpath));
	}
}

function build_attachment_permissions()
{
	$data = array();
	$types = vB::getDbAssertor()->assertQuery('vBForum:fetchAllAttachPerms');

	foreach ($types as $type)
	{
		if (empty($data["$type[extension]"]))
		{
			$contenttypes = unserialize($type['contenttypes']);
			$data["$type[extension]"] = array(
				'size'         => $type['default_size'],
				'width'        => $type['default_width'],
				'height'       => $type['default_height'],
				'contenttypes' => $contenttypes,
			);
		}

		if (!empty($type['usergroupid']))
		{
			$data["$type[extension]"]['custom']["$type[usergroupid]"] = array(
				'size'         => $type['custom_size'],
				'width'        => $type['custom_width'],
				'height'       => $type['custom_height'],
				'permissions'  => $type['custom_permissions'],
			);
		}
	}

	build_datastore('attachmentcache', serialize($data), true);
}

function print_statistic_result($date, $bar, $value, $width)
{
	$bgclass = fetch_row_bgclass();

	$style = 'width:' . $width . '%; ' .
		'height: 23px; ' .
		'border:' . vB_Template_Runtime::fetchStyleVar('poll_result_border') . '; ' .
		'background:' . vB_Template_Runtime::fetchStyleVar('poll_result_color_' . str_pad(strval(intval($bar)), 2, '0', STR_PAD_LEFT)) . '; ';

	echo '<tr><td width="0" class="' . $bgclass . '">' . $date . "</td>\n";
	echo '<td width="100%" class="' . $bgclass . '" nowrap="nowrap"><div style="' . $style . '">&nbsp;</div></td>' . "\n";
	echo '<td width="0%" class="' . $bgclass . '" nowrap="nowrap">' . $value . "</td></tr>\n";
}

function print_statistic_code($title, $name, $start, $end, $nullvalue = true, $scope = 'daily', $sort = 'date_desc', $script = 'stats')
{
	global $vbphrase;

	print_form_header("admincp/$script", $name);
	print_table_header($title);

	print_time_row($vbphrase['start_date'], 'start', $start, false);
	print_time_row($vbphrase['end_date'], 'end', $end, false);

	if ($name != 'activity')
	{
		print_select_row($vbphrase['scope'], 'scope', array('daily' => $vbphrase['daily'], 'weekly' => $vbphrase['weekly_gstats'], 'monthly' => $vbphrase['monthly']), $scope);
	}
	else
	{
		construct_hidden_code('scope', 'daily');
	}
	print_select_row($vbphrase['order_by_gcpglobal'], 'sort', array(
		'date_asc'   => $vbphrase['date_ascending'],
		'date_desc'  => $vbphrase['date_descending'],
		'total_asc'  => $vbphrase['total_ascending'],
		'total_desc' => $vbphrase['total_descending'],
	), $sort);
	print_yes_no_row($vbphrase['include_empty_results'], 'nullvalue', $nullvalue);
	print_submit_row($vbphrase['go']);
}

function fetch_stylevars_array()
{
	global $vbulletin;
	static $stylevars = array();

	if (empty($stylevars))
	{
		if ($vbulletin->GPC['dostyleid'] > 0)
		{
			$parentlist = vB_Library::instance('Style')->fetchTemplateParentlist($vbulletin->GPC['dostyleid']);
			$parentlist = explode(',',trim($parentlist));
		}
		else
		{
			$parentlist = array('-1');
		}
		$stylevars_result = vB::getDbAssertor()->assertQuery('fetchStylevarsArray', array('parentlist' => $parentlist));
		foreach ($stylevars_result as $sv)
		{
			$sv['styleid'] = $sv['stylevarstyleid'];
			if (empty($stylevars[$sv['stylevargroup']][$sv['stylevarid']]['currentstyle']))
			{
				// Skip if Stylevar was already found as changed in the current style
				$stylevars[$sv['stylevargroup']][$sv['stylevarid']] = $sv;
				if ($sv['styleid'] == $vbulletin->GPC['dostyleid'])
				{
					// Stylevar was changed in the current style, no need to check for
					// customized stylevars in the parent styles after that.
					$stylevars[$sv['stylevargroup']][$sv['stylevarid']]['currentstyle'] = '1';
				}
			}
		}
	}

	// sort it so it's nice and neat

	// sort groups
	$groups = array_keys($stylevars);
	natsort($groups);

	// show specific groups at the top
	// Keep this in sync with the sorting in the "getExistingStylevars" query
	$moveGroupsToTop = array('GlobalPalette', 'Global');
	foreach ($moveGroupsToTop AS $moveGroupToTop)
	{
		$moveGroupToTopKey = array_search($moveGroupToTop, $groups, true);
		if ($moveGroupToTopKey !== false)
		{
			// remove it
			unset($groups[$moveGroupToTopKey]);
		}
	}
	natsort($moveGroupsToTop);
	$groups = array_merge($moveGroupsToTop, $groups);

	// sort stylevars
	$to_return = array();
	foreach($groups AS $group)
	{
		$stylevarids = array_keys($stylevars[$group]);
		natsort($stylevarids);
		foreach ($stylevarids AS $stylevarid)
		{
			// don't need to go any deeper, stylevar.styleid doesn't really matter in display sorting
			$to_return[$group][$stylevarid] = $stylevars[$group][$stylevarid];
		}
	}

	return $to_return;
}

function cache_moderators($userid = false)
{
	//I'm about 99% sure that the $mod parameter isn't actually used anywhere.
	global $imodcache, $mod;

	$imodcache = [];
	$mod = [];

	$userApi = vB_Api::instanceInternal('user');

	$forummoderators = vB::getDbAssertor()->assertQuery('vBForum:getCacheModerators', ['userid' => $userid]);
	foreach($forummoderators AS $moderator)
	{
		try
		{
			$moderator['musername'] = $userApi->fetchMusername($moderator);
			$imodcache[$moderator['nodeid']][$moderator['userid']] = $moderator;
			$mod[$moderator['userid']] = 1;
		}
		catch (vB_Exception_Api $ex)
		{
			// do nothing...
		}
	}

	//let's move away from magic globals.  Instead let's return the value
	return $imodcache;
}

function scanVbulletinGPCFile($keys, $uploadedOnly = true)
{
	global $vbulletin;

	if (!is_array($keys))
	{
		$keys = array((string) $keys);
	}
	foreach ($keys AS $key)
	{
		if (empty($vbulletin->GPC[$key]['tmp_name']))
		{
			continue;
		}

		$filepaths = $vbulletin->GPC[$key]['tmp_name'];
		scanFiles($filepaths, $uploadedOnly);
	}
}

// Similar to scanVbulletinGPCFile() but allows for passing in the tmp_name list explicitly
// in case we can't use the global $vbulletin->GPC array.
// Usually $filepaths would be mapped from $_FILES[{someinput}]['tmp_name'].
function scanFiles($filepaths, $uploadedOnly = true, $errorOnNotUploaded = true)
{
	/** @var vB_Library_Filescan */
	$scanLib = vB_Library::instance('filescan');
	// We may have a single uploaded file, in which case tmp_name is just a string,
	// or an array of files, in which case tmp_name is an array.
	if (!is_array($filepaths))
	{
		$filepaths = [$filepaths];
	}

	foreach ($filepaths AS $__key => $__filepath)
	{
		// Skip scanning if no file was uploaded for this input, otherwise it'll
		// trip the is_uploaded_file() check erroneously, and nothing good will
		// come from trying to scanFile() an empty string/path.
		if (empty($__filepath))
		{
			continue;
		}
		$__isUploaded = is_uploaded_file($__filepath);
		if (!$__isUploaded)
		{
			if ($errorOnNotUploaded)
			{
				print_stop_message2('filescan_fail_uploaded_file');
			}
			else if ($uploadedOnly)
			{
				// I forget why we just skip a file specified that's not an uploaded file ... I think it's
				// meant to skip some kind of scanning ANY filesystem? In particular, the unlink() below
				// could be problematic if this ran on any files by default
				continue;
			}
		}

		$check = $scanLib->scanFile($__filepath);
		if (empty($check))
		{
			// Is removing the uploaded tmp file that was caught the right approach?
			@unlink($__filepath);
			print_stop_message2('filescan_fail_uploaded_file');
		}
	}
}

function getAdminCPUsernameAndDisplayname($username, $displayname = null, $options = [])
{
	$showDisplayname = vB::getDatastore()->getOption('enabledisplayname');
	if (!$showDisplayname OR $displayname === null)
	{
		return $username;
	}
	else
	{
		$displaynameAlreadyEscaped = in_array('escaped', $options);
		$noSpan = in_array('nospan', $options);

		// Some displaynames come from node.authorname or other fields that are already escaped.
		if ($displaynameAlreadyEscaped)
		{
			$displayname_safe = $displayname;
		}
		else
		{
			$displayname_safe = vB_String::htmlSpecialCharsUni($displayname);
		}

		// QoL for admin views -- if displayname is the same as username, just return the username.
		if ($displayname_safe === $username)
		{
			return $username;
		}

		// Sometimes, we shouldn't have the tags, e.g. inside of a select option.
		if ($noSpan)
		{
			return "$displayname_safe ($username)";
		}
		else
		{
			return <<<EOT
<span class="displayname">$displayname_safe</span> (<span class="username">$username</span>)
EOT;

		}
	}
}

//color picker code
//moved from the adminfunctions_templates.  It's currently used by the stylevar editor classes and the eventhighlights but *not* the templates.
// #############################################################################
/**
* Prints a row containing an <input type="text" />
*
* @param	string	Title for row
* @param	string	Name for input field
* @param	string	Value for input field
* @param	boolean	Whether or not to htmlspecialchars the input field value
* @param	integer	Size for input field
* @param	integer	Max length for input field
* @param	string	Text direction for input field
* @param	mixed	If specified, overrides the default CSS class for the input field
*/
function print_color_input_row($title, $name, $value = '', $htmlise = true, $size = 35, $maxlength = 0, $direction = '', $inputclass = false)
{
	global $numcolors;

	if ($htmlise)
	{
		$value = htmlspecialchars_uni($value);
	}

	$attributes = [
		'id' => 'color_'. $numcolors,
		'size' => $size,
		'value' => $value,
		//this doesn't seem to do anything and we don't consistently set it elsewhere but
		//leaving it in at the moment.
		'dir' => verify_text_direction($direction),
		'class' => 'color_input_control bginput',
		'onchange' => "preview_color($numcolors)",
	];

	if ($inputclass)
	{
		$attributes['class'] = $inputclass . ' color_input_control';
	}

	if ($maxlength)
	{
		$attributes['maxlength'] = $maxlength;
	}

	$html = '<div id="ctrl_' . $name . '">' . "\n" .
			construct_input('text', $name, $attributes) . "\n" .
			'<div id="preview_' . $numcolors . '" class="colorpreview" onclick="open_color_picker(' . $numcolors . ', event)"></div>' . "\n" .
		'</div>';

	print_label_row($title, $html, '', 'top', $name);

	$numcolors++;
}

// #############################################################################
/**
* Builds the color picker popup item for the style editor
*
* @param	integer	Width of each color swatch (pixels)
* @param	string	CSS 'display' parameter (default: 'none')
*
* @return	string
*/
function construct_color_picker($size = 12, $display = 'none')
{
	global $vbulletin, $colorPickerWidth, $colorPickerType;

	$previewsize = 3 * $size;
	$surroundsize = $previewsize * 2;
	$colorPickerWidth = 21 * $size + 22;

	$html = "
	<style type=\"text/css\">
	#colorPicker
	{
		background: black;
		position: absolute;
		left: 0px;
		top: 0px;
		width: {$colorPickerWidth}px;
	}
	#colorFeedback
	{
		border: solid 1px black;
		border-bottom: none;
		width: {$colorPickerWidth}px;
		padding-left: 0;
		padding-right: 0;
	}
	#colorFeedback input
	{
		font: 11px verdana, arial, helvetica, sans-serif;
	}
	#colorFeedback button
	{
		width: 19px;
		height: 19px;
	}
	#txtColor
	{
		border: inset 1px;
		width: 70px;
	}
	#colorSurround
	{
		border: inset 1px;
		white-space: nowrap;
		width: {$surroundsize}px;
		height: 15px;
	}
	#colorSurround td
	{
		background-color: none;
		border: none;
		width: {$previewsize}px;
		height: 15px;
	}
	#swatches
	{
		background-color: black;
		width: {$colorPickerWidth}px;
	}
	#swatches td
	{
		background: black;
		border: none;
		width: {$size}px;
		height: {$size}px;
	}
	</style>
	<div id=\"colorPicker\" style=\"display:$display\" oncontextmenu=\"switch_color_picker(1); return false\" onmousewheel=\"switch_color_picker(event.wheelDelta * -1); return false;\">
	<table id=\"colorFeedback\" class=\"tcat\" cellpadding=\"0\" cellspacing=\"4\" border=\"0\" width=\"100%\">
	<tr>
		<td><button type=\"button\" onclick=\"col_click('transparent'); return false\"><img src=\"" . get_cpstyle_href('colorpicker_transparent.gif') . "\" title=\"'transparent'\" alt=\"\" /></button></td>
		<td>
			<table id=\"colorSurround\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\">
			<tr>
				<td id=\"oldColor\" onclick=\"close_color_picker()\"></td>
				<td id=\"newColor\"></td>
			</tr>
			</table>
		</td>
		<td width=\"100%\"><input id=\"txtColor\" type=\"text\" value=\"\" size=\"8\" /></td>
		<td style=\"white-space:nowrap\">
			<input type=\"hidden\" name=\"colorPickerType\" id=\"colorPickerType\" value=\"$colorPickerType\" />
			<button type=\"button\" onclick=\"switch_color_picker(1); return false\"><img src=\"" . get_cpstyle_href('colorpicker_toggle.gif') . "\" alt=\"\" /></button>
			<button type=\"button\" onclick=\"close_color_picker(); return false\"><img src=\"" . get_cpstyle_href('colorpicker_close.gif') . "\" alt=\"\" /></button>
		</td>
	</tr>
	</table>
	<table id=\"swatches\" cellpadding=\"0\" cellspacing=\"1\" border=\"0\">\n";

	$colors = [
		'00', '33', '66',
		'99', 'CC', 'FF'
	];

	$specials = [
		'#000000', '#333333', '#666666',
		'#999999', '#CCCCCC', '#FFFFFF',
		'#FF0000', '#00FF00', '#0000FF',
		'#FFFF00', '#00FFFF', '#FF00FF'
	];

	$green = [5, 4, 3, 2, 1, 0, 0, 1, 2, 3, 4, 5];
	$blue = [0, 0, 0, 5, 4, 3, 2, 1, 0, 0, 1, 2, 3, 4, 5, 5, 4, 3, 2, 1, 0];

	for ($y = 0; $y < 12; $y++)
	{
		$html .= "\t<tr>\n";

		$html .= construct_color_picker_element(0, $y, '#000000');
		$html .= construct_color_picker_element(1, $y, $specials["$y"]);
		$html .= construct_color_picker_element(2, $y, '#000000');

		for ($x = 3; $x < 21; $x++)
		{
			$r = floor((20 - $x) / 6) * 2 + floor($y / 6);
			$g = $green["$y"];
			$b = $blue["$x"];

			$html .= construct_color_picker_element($x, $y, '#' . $colors["$r"] . $colors["$g"] . $colors["$b"]);
		}

		$html .= "\t</tr>\n";
	}

	$html .= "\t</table>
	</div>
	<script type=\"text/javascript\">
	<!--
	var tds = fetch_tags(fetch_object(\"swatches\"), \"td\");
	for (var i = 0; i < tds.length; i++)
	{
		tds[i].onclick = swatch_click;
		tds[i].onmouseover = swatch_over;
	}
	//-->
	</script>\n";

	return $html;
}

// #############################################################################
/**
* Builds a single color swatch for the color picker gadget
*
* @param	integer	Current X coordinate
* @param	integer	Current Y coordinate
* @param	string	Color
*
* @return	string
*/
function construct_color_picker_element($x, $y, $color)
{
	return "\t\t<td style=\"background:$color\" id=\"sw$x-$y\"><img src=\"images/clear.gif\" alt=\"\" style=\"width:11px; height:11px\" /></td>\r\n";
}


// #############################################################################
/**
* Converts a version number string into an array that can be parsed
* to determine if which of several version strings is the newest.
*
* @param	string	Version string to parse
*
* @return	array	Array of 6 bits, in decreasing order of influence; a higher bit value is newer
*/
function fetch_version_array($version)
{
	// parse for a main and subversion
	if (preg_match('#^([a-z]+ )?([0-9\.]+)[\s-]*([a-z].*)$#i', trim($version), $match))
	{
		$main_version = $match[2];
		$sub_version = $match[3];
	}
	else
	{
		$main_version = $version;
		$sub_version = '';
	}

	$version_bits = explode('.', $main_version);

	// pad the main version to 4 parts (1.1.1.1)
	if (sizeof($version_bits) < 4)
	{
		for ($i = sizeof($version_bits); $i < 4; $i++)
		{
			$version_bits[$i] = 0;
		}
	}

	// default sub-versions
	$version_bits[4] = 0; // for alpha, beta, rc, pl, etc
	$version_bits[5] = 0; // alpha, beta, etc number

	if (!empty($sub_version))
	{
		// match the sub-version
		if (preg_match('#^(A|ALPHA|B|BETA|G|GAMMA|RC|RELEASE CANDIDATE|GOLD|STABLE|FINAL|PL|PATCH LEVEL)\s*(\d*)\D*$#i', $sub_version, $match))
		{
			switch (strtoupper($match[1]))
			{
				case 'A':
				case 'ALPHA';
					$version_bits[4] = -4;
					break;

				case 'B':
				case 'BETA':
					$version_bits[4] = -3;
					break;

				case 'G':
				case 'GAMMA':
					$version_bits[4] = -2;
					break;

				case 'RC':
				case 'RELEASE CANDIDATE':
					$version_bits[4] = -1;
					break;

				case 'PL':
				case 'PATCH LEVEL';
					$version_bits[4] = 1;
					break;

				case 'GOLD':
				case 'STABLE':
				case 'FINAL':
				default:
					$version_bits[4] = 0;
					break;
			}

			$version_bits[5] = $match[2];
		}
	}

	// sanity check -- make sure each bit is an int
	for ($i = 0; $i <= 5; $i++)
	{
		$version_bits["$i"] = intval($version_bits["$i"]);
	}

	return $version_bits;
}

/**
* Compares two version strings. Returns true if the first parameter is
* newer than the second.
*
* @param	string	Version string; usually the latest version
* @param	string	Version string; usually the current version
* @param	bool	Flag to allow check if the versions are the same
*
* @return	bool	True if the first argument is newer than the second, or if 'check_same' is true and the versions are the equal
*/
function is_newer_version($new_version_str, $cur_version_str, $check_same = false)
{
	// if they're the same, don't even bother
	if ($cur_version_str != $new_version_str)
	{
		$cur_version = fetch_version_array($cur_version_str);
		$new_version = fetch_version_array($new_version_str);

		// iterate parts
		for ($i = 0; $i <= 5; $i++)
		{
			if ($new_version["$i"] != $cur_version["$i"])
			{
				// true if newer is greater
				return ($new_version["$i"] > $cur_version["$i"]);
			}
		}
	}
	else if ($check_same)
	{
		return true;
	}

	return false;
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 116811 $
|| #######################################################################
\*=========================================================================*/
