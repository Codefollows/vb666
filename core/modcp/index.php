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
error_reporting(error_reporting() & ~E_NOTICE);

// ##################### DEFINE IMPORTANT CONSTANTS #######################
define('CVS_REVISION', '$RCSfile$ - $Revision: 116812 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
global $phrasegroups, $specialtemplates, $vbulletin, $vbphrase;

$phrasegroups = ['cphome','cpuser', 'user'];
$specialtemplates = [];

// ########################## REQUIRE BACK-END ############################
require_once(dirname(__FILE__) . '/global.php');

// ############################# LOG ACTION ###############################
if (empty($_REQUEST['do']))
{
	log_admin_action();
}

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

// #############################################################################
// ############################### LOG OUT OF CP ###############################
// #############################################################################

if ($_REQUEST['do'] == 'cplogout')
{
	vbsetcookie('cpsession', '', false, true, true);
	$vbulletin->db->query_write("DELETE FROM " . TABLE_PREFIX . "cpsession WHERE userid = " . $vbulletin->userinfo['userid'] . " AND hash = '" . $vbulletin->db->escape_string($vbulletin->GPC[COOKIE_PREFIX . 'cpsession']) . "'");
	$sessionurl_js = vB::getCurrentSession()->get('sessionurl_js');
	if (!empty($sessionurl_js))
	{
		exec_header_redirect('index.php?' . $sessionurl_js);
	}
	else
	{
		exec_header_redirect('index.php');
	}
}

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'frames';
}

// ####################################################################
if ($_REQUEST['do'] == 'frames')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'locfile' => vB_Cleaner::TYPE_NOHTML,
		'locparams' => vB_Cleaner::TYPE_ARRAY_STR,
	));

	// passing the entire url on the query string runs afoul of some security restrictions on apache mod_rewrite.
	if ($vbulletin->GPC['locfile'])
	{
		$url = get_modcp_url($vbulletin->GPC['locfile'], $vbulletin->GPC['locparams']);
	}
	else
	{
		$url = 'index.php?do=home';
	}

	$url = htmlspecialchars($url);

	$navframe = '<frame src="index.php?' . vB::getCurrentSession()->get('sessionurl') . "do=nav" .
		"\" name=\"nav\" scrolling=\"yes\" frameborder=\"0\" marginwidth=\"0\" marginheight=\"0\" border=\"no\" />\n";
	$headframe = '<frame src="index.php?' . vB::getCurrentSession()->get('sessionurl') . "do=head\" name=\"head\" scrolling=\"no\" noresize=\"noresize\" frameborder=\"0\" marginwidth=\"10\" marginheight=\"0\" border=\"no\" />\n";
	$mainframe = '<frame src="' . $url . "\" name=\"main\" scrolling=\"yes\" frameborder=\"0\" marginwidth=\"10\" marginheight=\"10\" border=\"no\" />\n";

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="<?php echo vB_Template_Runtime::fetchStyleVar('textdirection'); ?>" lang="<?php echo vB_Template_Runtime::fetchStyleVar('languagecode'); ?>">
<head>
<script type="text/javascript">
<!-- // get out of any containing frameset
if (self.parent.frames.length != 0)
{
	document.write('<span style="font: bold 10pt verdana,sans-serif">Get me out of this frame set!</span>');
	self.parent.location.replace(document.location.href);
}
// -->
</script>
<title><?php echo $vbulletin->options['bbtitle']; ?> <?php echo $vbphrase['moderator_control_panel']; ?></title>
</head>

<?php

if (vB_Template_Runtime::fetchStyleVar('textdirection') == 'ltr')
{
// left-to-right frameset
?>
<frameset cols="195,*"  framespacing="0" border="0" frameborder="0" frameborder="no" border="0">
	<?php echo $navframe; ?>
	<frameset rows="20,*"  framespacing="0" border="0" frameborder="0" frameborder="no" border="0">
		<?php echo $headframe; ?>
		<?php echo $mainframe; ?>
	</frameset>
</frameset>
<?php
}
else
{
// right-to-left frameset
?>
<frameset cols="*,195"  framespacing="0" border="0" frameborder="0" frameborder="no" border="0">
	<frameset rows="20,*"  framespacing="0" border="0" frameborder="0" frameborder="no" border="0">
		<?php echo $headframe; ?>
		<?php echo $mainframe; ?>
	</frameset>
	<?php echo $navframe; ?>
</frameset>
<?php
}

?>

<noframes>
	<body>
		<p><?php echo $vbphrase['no_frames_support']; ?></p>
	</body>
</noframes>

</html>
<?php
}

// ####################################################################
if ($_REQUEST['do'] == 'head')
{
	define('IS_NAV_PANEL', true);
	print_cp_header();

	$forumhomelink = 'index.php';
?>
<table border="0" width="100%" height="100%">
<tr valign="middle">
	<td><a href="https://www.vbulletin.com/" target="_blank"><b><?php echo $vbphrase['moderator_control_panel']; ?></b> (vBulletin <?php echo $vbulletin->versionnumber; ?>)</a></td>
	<td style="white-space:nowrap; text-align:<?php echo vB_Template_Runtime::fetchStyleVar('right'); ?>; font-weight:bold">
			<a href="<?php echo $forumhomelink; ?>" target="_blank"><?php echo $vbphrase['forum_home_page']; ?></a>
			|
			<a href="modcp/index.php?<?php echo vB::getCurrentSession()->get('sessionurl'); ?>do=cplogout" onclick="return confirm('<?php echo $vbphrase['sure_you_want_to_log_out_of_cp']; ?>');"  target="_top"><?php echo $vbphrase['log_out']; ?></a>
</td>
</tr>
</table>
<?php
	print_cp_footer();
}

// ####################################################################
if ($_REQUEST['do'] == 'home')
{

print_cp_header($vbphrase['welcome_to_the_vbulletin_moderator_control_panel']);

print_form_header('', '');
print_table_header($vbphrase['welcome_to_the_vbulletin_moderator_control_panel']);
print_table_footer();

// *************************************
// QUICK ADMIN LINKS

//$reminders = fetch_reminders_array();

print_table_start();
print_table_header($vbphrase['quick_moderator_links']);

$datecut = TIMENOW - $vbulletin->options['cookietimeout'];
$guestsarry = $vbulletin->db->query_first("SELECT COUNT(host) AS sessions FROM " . TABLE_PREFIX . "session WHERE userid = 0 AND lastactivity > $datecut");
$membersarry = $vbulletin->db->query_read("SELECT DISTINCT userid FROM " . TABLE_PREFIX . "session WHERE userid <> 0 AND lastactivity > $datecut");
$guests = intval($guestsarry['sessions']);
$members = intval($vbulletin->db->num_rows($membersarry));

$is_windows = (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN');
$loadavg = array();

if (!$is_windows AND function_exists('exec') AND $stats = @exec('uptime 2>&1') AND trim($stats) != '' AND preg_match('#: ([\d.,]+),?\s+([\d.,]+),?\s+([\d.,]+)$#', $stats, $regs))
{
	$loadavg[0] = vb_number_format($regs[1], 2);
	$loadavg[1] = vb_number_format($regs[2], 2);
	$loadavg[2] = vb_number_format($regs[3], 2);
}
else if (!$is_windows AND @file_exists('/proc/loadavg') AND $stats = @file_get_contents('/proc/loadavg') AND trim($stats) != '')
{
	$loadavg = explode(' ', $stats);
	$loadavg[0] = vb_number_format($loadavg[0], 2);
	$loadavg[1] = vb_number_format($loadavg[1], 2);
	$loadavg[2] = vb_number_format($loadavg[2], 2);
}

if (!empty($loadavg))
{
	print_label_row($vbphrase['server_load_averages'], "$loadavg[0]&nbsp;&nbsp;$loadavg[1]&nbsp;&nbsp;$loadavg[2] | " . construct_phrase($vbphrase['users_online_x_members_y_guests'], vb_number_format($guests + $members), vb_number_format($members), vb_number_format($guests)), '', 'top', NULL, false);
}
else
{
	print_label_row($vbphrase['users_online'], construct_phrase($vbphrase['x_y_members_z_guests'], vb_number_format($guests + $members), vb_number_format($members), vb_number_format($guests)), '', 'top', NULL, false);
}

// Legacy Hook 'mod_index_main' Removed //

print_label_row($vbphrase['quick_user_finder'], '
	<form action="modcp/user.php?do=findnames" method="post" style="display:inline">
	<input type="hidden" name="s" value="' . vB::getCurrentSession()->get('sessionhash') . '" />
	<input type="hidden" name="do" value="findnames" />
	<input type="hidden" name="adminhash" value="' . ADMINHASH . '" />
	<input type="hidden" name="securitytoken" value="' . $vbulletin->userinfo['securitytoken'] . '" />
	<input type="text" class="bginput" name="findname" size="30" tabindex="1" />
	<input type="submit" class="button" value=" ' . $vbphrase['find'] . ' " tabindex="1" />
	<input type="submit" class="button" value="' . $vbphrase['exact_match'] . '" tabindex="1" name="exact" />
	</form>
	', '', 'top', NULL, false
);
print_label_row($vbphrase['php_function_lookup'], '
	<form action="http://www.ph' . 'p.net/manual-lookup.ph' . 'p" method="get" style="display:inline" target="_blank">
	<input type="text" class="bginput" name="function" size="30" tabindex="1" />
	<input type="submit" value=" ' . $vbphrase['find'] . ' " class="button" tabindex="1" />
	</form>
	', '', 'top', NULL, false
);
print_label_row($vbphrase['mysql_language_lookup'], '
	<form action="http://www.mysql.com/search/" method="get" style="display:inline" target="_blank">
	<input type="hidden" name="doc" value="1" />
	<input type="hidden" name="m" value="o" />
	<input type="text" class="bginput" name="q" size="30" tabindex="1" />
	<input type="submit" value=" ' . $vbphrase['find'] . ' " class="button" tabindex="1" />
	</form>
	', '', 'top', NULL, false
);
print_label_row($vbphrase['useful_links'], '
	<form style="display:inline">
	<select onchange="if (this.options[this.selectedIndex].value != \'\') { window.open(this.options[this.selectedIndex].value); } return false;" tabindex="1" class="bginput">
		<option value="">-- ' . $vbphrase['useful_links'] . ' --</option>' . construct_select_options(array(
			'vBulletin' => array(
				'https://www.vbulletin.com/' => $vbphrase['home_page_guser'] . ' (vBulletin.com)',
				'https://members.vbulletin.com/' => $vbphrase['members_area'],
				'https://www.vbulletin.com/forum/' => $vbphrase['community_forums'],
				'https://www.vbulletin.com/manual/' => $vbphrase['reference_manual']
			),
			'PHP' => array(
				'http://www.ph' . 'p.net/' => $vbphrase['home_page_guser'] . ' (PHP.net)',
				'http://www.ph' . 'p.net/manual/' => $vbphrase['reference_manual'],
				'http://www.ph' . 'p.net/downloads.ph' . 'p' => $vbphrase['download_latest_version']
			),
			'MySQL' => array(
				'http://www.mysql.com/' => $vbphrase['home_page_guser'] . ' (MySQL.com)',
				'http://www.mysql.com/documentation/' => $vbphrase['reference_manual'],
				'http://www.mysql.com/downloads/' => $vbphrase['download_latest_version'],
			)
	)) . '</select>
	</form>
	', '', 'top', NULL, false
);
print_table_footer(2, '', '', false);

// *************************************
// vBULLETIN CREDITS
require_once(DIR . '/includes/vbulletin_credits.php');

print_cp_footer();
}

// ####################################################################
if ($_REQUEST['do'] == 'nav')
{
	require_once(DIR . '/includes/adminfunctions_navpanel.php');
	print_cp_header();

	$vboptions = vB::getDatastore()->getValue('options');
	$userinfo = vB_User::fetchUserinfo(0, array('admin'));
	$logourl = get_cpstyle_image('cp_logo');
	echo '
	<div><img src="' . $logourl . '" alt=""  style="padding: 16px"/></div>
	<div style="width:168px; padding: 4px">';

	construct_nav_spacer();

	// *************************************************
	$canmoderate = false;
	/*
	if (can_moderate(0, 'canmoderateposts'))
	{
		$canmoderate = true;
		construct_nav_option($vbphrase['moderate_threads_gcphome'], 'moderate.php?do=posts','modcp');
		construct_nav_option($vbphrase['moderate_posts_gcphome'], 'moderate.php?do=posts#posts','modcp');
	}
	 */
	/*
	if (can_moderate(0, 'canmoderateattachments'))
	{
		$canmoderate = true;
		construct_nav_option($vbphrase['moderate_attachments_gcphome'], 'moderate.php?do=attachments','modcp');
	}
	 */
	/*
	if (can_moderate(0, 'canmoderatevisitormessages'))
	{
		$canmoderate = true;
		construct_nav_option($vbphrase['moderate_visitor_messages'], 'moderate.php?do=messages','modcp');
	}
	 */
	if ($canmoderate)
	{
		construct_nav_group($vbphrase['moderation']);
		construct_nav_spacer();
	}
	// *************************************************
	$canuser = false;
	if (can_moderate(0, 'canunbanusers') OR can_moderate(0, 'canbanusers') OR can_moderate(0, 'canviewprofile') OR can_moderate(0, 'caneditsigs') OR can_moderate(0, 'caneditavatar'))
	{
		$canuser = true;
		construct_nav_option($vbphrase['search_for_users'],'user.php?do=find','modcp');
	}
	if (can_moderate(0, 'canbanusers'))
	{
		$canuser = true;
		construct_nav_option($vbphrase['ban_user_gcphome'], 'banning.php?do=banuser','modcp');
	}

	if (can_moderate(0, 'canunbanusers') OR can_moderate(0, 'canbanusers'))
	{
		$canuser = true;
		construct_nav_option($vbphrase['view_banned_users'], 'banning.php?do=modify','modcp');
	}

	if (can_moderate(0, 'canviewips'))
	{
		$canuser = true;
		construct_nav_option($vbphrase['search_ip_addresses_gcphome'], 'user.php?do=doips','modcp');
	}
	if ($canuser)
	{
		construct_nav_group($vbphrase['users']);
		construct_nav_spacer();
	}
	// *************************************************
	/*
	$canmass = false;
	if (can_moderate(0, 'canmassmove'))
	{
		$canmass = true;
		construct_nav_option($vbphrase['move'], 'thread.php?do=move','modcp');
	}
	if (can_moderate(0, 'canmassprune'))
	{
		$canmass = true;
		construct_nav_option($vbphrase['prune'], 'thread.php?do=prune','modcp');
	}
	if ($canmass)
	{
		construct_nav_group($vbphrase['thread']);
		construct_nav_spacer();
	}
	*/
	// Legacy Hook 'mod_index_navigation' Removed //

	print_nav_panel();

	echo "</div>\n";
	// *************************************************

	define('NO_CP_COPYRIGHT', true);
	print_cp_footer();
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 116812 $
|| #######################################################################
\*=========================================================================*/
