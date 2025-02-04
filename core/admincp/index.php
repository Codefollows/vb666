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
define('CVS_REVISION', '$RCSfile$ - $Revision: 116812 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
global $phrasegroups, $specialtemplates, $vbphrase, $vbulletin;
$phrasegroups = array('cphome');
$specialtemplates = array('acpstats');


// ########################## REQUIRE BACK-END ############################
require_once(dirname(__FILE__) . '/global.php');

// #############################################################################
// ########################### START MAIN SCRIPT ###############################
// #############################################################################

$vb5_config = vB::getConfig();
$datastore = vB::getDatastore();
$vb_options = $datastore->getValue('options');
$assertor = vB::getDbAssertor();
$userContext = vB::getUserContext();


// ############################## Start build_acpstats_datastore ####################################
/**
* Stores a cache of various data for ACP Home Quick Stats into the datastore.
*/
function build_acpstats_datastore($specialid)
{
	$assertor = vB::getDbAssertor();
	$starttime = mktime(0, 0, 0, date('m'), date('d'), date('Y'));

	$acpstats = array();

	$data = $assertor->getRow('vBForum:getFiledataFilesizeSum');
	$acpstats['attachsize'] = $data['size'];

	$data = $assertor->getRow('getCustomAvatarFilesizeSum');
	$acpstats['avatarsize'] = $data['size'];

	$data = $assertor->getRow('user', array(
		vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_COUNT,
		vB_dB_Query::CONDITIONS_KEY => array(
			array('field' => 'joindate', 'value' => $starttime, vB_dB_Query::OPERATOR_KEY => vB_dB_Query::OPERATOR_GTE)
		)
	));
	$acpstats['newusers'] = $data['count'];

	$data = $assertor->getRow('user', array(
		vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_COUNT,
		vB_dB_Query::CONDITIONS_KEY => array(
			array('field' => 'lastactivity', 'value' => $starttime, vB_dB_Query::OPERATOR_KEY => vB_dB_Query::OPERATOR_GTE)
		)
	));
	$acpstats['userstoday'] = $data['count'];

	$data = $assertor->getRow('vBAdmincp:getIndexNewStartersCount', array('starttime' => $starttime, 'specialchannelid' => $specialid));
	$acpstats['newthreads'] = $data['count'];

	$data = $assertor->getRow('vBAdmincp:getIndexNewPostsCount', array('starttime' => $starttime, 'specialchannelid' => $specialid));
	$acpstats['newposts'] = $data['count'];

	$acpstats['indexsize'] = 0;
	$acpstats['datasize'] = 0;

	try
	{
		$tables = $assertor->getRows('vBAdmincp:getTableStatus', array());
	}
	catch (Exception $ex)
	{
		$tables = array();
	}

	if ($tables AND !isset($table['errors']))
	{
		foreach ($tables AS $table)
		{
			$acpstats['datasize'] += $table['Data_length'];
			$acpstats['indexsize'] += $table['Index_length'];
		}
	}

	if (!$acpstats['indexsize'])
	{
		$acpstats['indexsize'] = -1;
	}
	if (!$acpstats['datasize'])
	{
		$acpstats['datasize'] = -1;
	}
	$acpstats['lastupdate'] = vB::getRequest()->getTimeNow();
	build_datastore('acpstats', serialize($acpstats), 1);

	return $acpstats;
}

if (empty($_REQUEST['do']))
{
	log_admin_action();
}

// #############################################################################

$vbulletin->input->clean_array_gpc('r', array(
	'nojs'     => vB_Cleaner::TYPE_BOOL,
));

// #############################################################################
// ############################### LOG OUT OF CP ###############################
// #############################################################################

if ($_REQUEST['do'] == 'cplogout')
{
	vbsetcookie('cpsession', '', false, true, true);
	$assertor->delete('cpsession', [
		'userid' => vB::getCurrentSession()->get('userid'),
		'hash' => $vbulletin->GPC[COOKIE_PREFIX . 'cpsession']
	]);
	vbsetcookie('customerid', '', 0);
	exec_header_redirect(get_admincp_url('index', []));
}

// #############################################################################
// ################################# SAVE NOTES ################################
// #############################################################################

if ($_POST['do'] == 'notes')
{
	$vbulletin->input->clean_array_gpc('p', array('notes' => vB_Cleaner::TYPE_STR));

	$admindm =& datamanager_init('Admin', $vbulletin, vB_DataManager_Constants::ERRTYPE_CP);
	$admindm->set_existing($vbulletin->userinfo);
	$admindm->set('notes', $vbulletin->GPC['notes']);
	$admindm->save();
	unset($admindm);

	$vbulletin->userinfo['notes'] = htmlspecialchars_uni($vbulletin->GPC['notes']);
	$_REQUEST['do'] = 'home';
}

// #############################################################################
// ################################# HEADER FRAME ##############################
// #############################################################################

$versionhost = 'https://version.vbulletin.com';

if ($_REQUEST['do'] == 'head')
{
	ignore_user_abort(true);

	define('IS_NAV_PANEL', true);
	$extra = [
		'<script type= "text/javascript" src="' . $versionhost . '/version.js?v=' . $vb_options['simpleversion'] . '&amp;id=LN05842122&amp;pid=vb6"></script>',
		'<script type="text/javascript" src="core/clientscript/vbulletin_cphead.js?v=' . $vb_options['simpleversion'] . '"></script>',
	];
	print_cp_header('', '', $extra);
	?>
	<div id="acp-head-wrapper">
		<ul id="acp-top-links">
			<li id="acp-top-link-acp" class="h-left"><?php echo $vbphrase['admin_cp']; ?></li>
			<li id="acp-top-link-site" class="h-left"><a href="index.php" target="homePageFromACP"><?php echo $vbphrase['site_home_page']; ?></a></li>
			<li class="h-left divider"></li>
			<li id="acp-top-link-logout" class="h-right rightmost"><a href="admincp/index.php?do=cplogout" class='js-cplogout' target="_top"><?php echo $vbphrase['log_out']; ?></a></li>
			<li class="h-right divider"></li>
			<li id="acp-top-link-msg" class="h-right"><a href="messagecenter/index" target="_blank"><?php echo $vbphrase['messages_header']; ?></a></li>
			<li class="h-right divider"></li>
			<li id="acp-top-link-msg" class="h-right"><a href="https://www.vbulletin.com/forum/" target="_blank"><?php echo $vbphrase['site_get_support']; ?></a></li>
			<li class="h-right divider"></li>
			<li id="acp-top-link-msg" class="h-right"><a href="https://www.vbulletin.com/go/vb5bugreport" target="_blank"><?php echo $vbphrase['site_report_bug']; ?></a></li>
			<li class="h-right divider"></li>

		</ul>
		<div id="acp-logo-bar">
			<div class="logo">
				<img src="<?php echo get_cpstyle_image('cp_logo'); ?>" title="<?php echo $vbphrase['admin_control_panel']; ?>" alt="" border="0" <?php $df = print_form_middle("LN05842122"); ?> />
			</div>
			<div class="links">
				<a class="header-item" href="https://www.vbulletin.com/" target="_blank"><?php echo $vbphrase['vbulletin'] . ' ' . ADMIN_VERSION_VBULLETIN . print_form_middle('LN05842122'); echo (is_demo_mode() ? ' <b>DEMO MODE</b>' : ''); ?></a>
				<a class="header-item" href="https://members.vbulletin.com/" id="head_version_link" target="_blank">&nbsp;</a>
				<span class="header-item warning-message js-debug-warning-message <?php echo ($vb5_config['Misc']['debug'] ? '' : 'hide'); ?>"><?php echo $vbphrase['debug_mode_active']; ?></span>
				<span class="header-item warning-message js-siteoff-warning-message <?php echo ($vb_options['bbactive'] ? 'hide' : ''); ?>"><?php echo $vbphrase['site_off']; ?></span>
			</div>
			<div class="search">
				<?php print_form_header('admincp/search', 'dosearch', 1, 1, ''); ?>
					<input type="text" name="terms" />
					<input type="submit" class="button" value="<?php echo $vbphrase['search']; ?>" />
				</form>
			</div>
		</div>
	</div>
	<?php

	register_js_phrase([
		'latest_version_available_x',
		'n_a',
		'sure_you_want_to_log_out_of_cp',
	]);
	define('NO_CP_COPYRIGHT', true);
	print_cp_footer();
}

$vbulletin->input->clean_array_gpc('r', ['navprefs' => vB_Cleaner::TYPE_STR]);
$vbulletin->GPC['navprefs'] = preg_replace('#[^a-z0-9_,]#i', '', $vbulletin->GPC['navprefs']);

// #############################################################################
// ############################### SAVE NAV PREFS ##############################
// #############################################################################

if ($_REQUEST['do'] == 'navprefs')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'groups'	=> vB_Cleaner::TYPE_STR,
		'expand'	=> vB_Cleaner::TYPE_BOOL,
		'navprefs'	=> vB_Cleaner::TYPE_STR
	));

	$vbulletin->GPC['groups'] = preg_replace('#[^a-z0-9_,]#i', '', $vbulletin->GPC['groups']);

	if ($vbulletin->GPC['expand'])
	{
		$groups = explode(',', $vbulletin->GPC['groups']);

		foreach ($groups AS $group)
		{
			if (empty($group))
			{
				continue;
			}

			$vbulletin->input->clean_gpc('r', "num$group", vB_Cleaner::TYPE_UINT);

			for ($i = 0; $i < $vbulletin->GPC["num$group"]; $i++)
			{
				$vbulletin->GPC['navprefs'][] = $group . "_$i";
			}
		}

		$vbulletin->GPC['navprefs'] = implode(',', $vbulletin->GPC['navprefs']);
	}
	else
	{
		$vbulletin->GPC['navprefs'] = '';
	}

	$vbulletin->GPC['navprefs'] = preg_replace('#[^a-z0-9_,]#i', '', $vbulletin->GPC['navprefs']);

	$_REQUEST['do'] = 'savenavprefs';
}

if ($_REQUEST['do'] == 'buildbitfields')
{
	require_once(DIR . '/includes/class_bitfield_builder.php');
	vB_Bitfield_Builder::save();
	vB_Library::instance('usergroup')->buildDatastore();
	$userContext->rebuildGroupAccess();

	print_stop_message2('rebuilt_bitfields_successfully', 'index');
}

if (
	$_REQUEST['do'] == 'buildvideo' AND
	($userContext->hasAdminPermission('canadminstyles') OR $userContext->hasAdminPermission('canadmintemplates'))
)
{
	require_once(DIR . '/includes/functions_databuild.php');
	build_bbcode_video();
	vB_Library::instance('style')->buildAllStyles();
	print_stop_message2('rebuilt_video_bbcodes_successfully', 'index');
}

if ($_REQUEST['do'] == 'buildnavprefs')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'prefs' 	=> vB_Cleaner::TYPE_STR,
		'dowhat'	=> vB_Cleaner::TYPE_STR,
		'id'		=> vB_Cleaner::TYPE_INT
	));

	$vbulletin->GPC['prefs'] = preg_replace('#[^a-z0-9_,]#i', '', $vbulletin->GPC['prefs']);
	$_tmp = preg_split('#,#', $vbulletin->GPC['prefs'], -1, PREG_SPLIT_NO_EMPTY);
	$_navprefs = array();

	foreach ($_tmp AS $_val)
	{
		$_navprefs["$_val"] = $_val;
	}
	unset($_tmp);

	if ($vbulletin->GPC['dowhat'] == 'collapse')
	{
		// remove an item from the list
		unset($_navprefs[$vbulletin->GPC['id']]);
	}
	else
	{
		// add an item to the list
		$_navprefs[$vbulletin->GPC['id']] = $vbulletin->GPC['id'];
		ksort($_navprefs);
	}

	$vbulletin->GPC['navprefs'] = implode(',', $_navprefs);
	$_REQUEST['do'] = 'savenavprefs';
}

if ($_REQUEST['do'] == 'savenavprefs')
{
	$admindm = new vB_DataManager_Admin(vB_DataManager_Constants::ERRTYPE_CP);
	$admindm->set_existing($vbulletin->userinfo);
	$admindm->set('navprefs', $vbulletin->GPC['navprefs']);
	$admindm->save();
	unset($admindm);

	// ensure that the new nav prefs are used on *this* page load
	require_once(DIR . '/includes/adminfunctions_navpanel.php');
	$vbulletin->userinfo['navprefs'] = $vbulletin->GPC['navprefs'];
	init_nav_prefs(true);
	$_REQUEST['do'] = 'nav';
}

// ################################ NAVIGATION FRAME #############################

if ($_REQUEST['do'] == 'nav')
{
	require_once(DIR . '/includes/adminfunctions_navpanel.php');
	print_cp_header();

	echo "\n\n" . (is_demo_mode() ? "<div align=\"center\"><b>DEMO MODE</b></div>\n\n" : '') . "<div id=\"acp-nav-wrapper\">\n";

	construct_nav_spacer(true);

	$navigation = []; // [displayorder][phrase/text] = [[group], [options][disporder][]]
	$navfiles = vB_Api::instance('product')->loadProductCpnavFiles();

	if (empty($navfiles['vbulletin']))	// cpnav_vbulletin.xml is missing
	{
		echo construct_phrase($vbphrase['could_not_open_x'], DIR . '/includes/xml/cpnav_vbulletin.xml');
		exit;
	}

	if (empty($vbulletin->products))
	{
		$vbulletin->products = vB::getProducts()->getProducts();
	}

	foreach ($navfiles AS $nav_file => $file)
	{
		$xmlobj = new vB_XML_Parser(false, $file);
		$xml = $xmlobj->parse();

		if ($xml['product'] AND empty($vbulletin->products["$xml[product]"]))
		{
			// attached to a specific product and that product isn't enabled
			continue;
		}

		$navgroups = vB_XML_Parser::getList($xml, 'navgroup');
		foreach ($navgroups AS $navgroup)
		{

			if (!empty($navgroup['debug']) AND $vb5_config['Misc']['debug'] != 1)
			{
				continue;
			}

			// do we have access to this group? permissions may be a delmited list
			if (!empty($navgroup['permissions']))
			{
				if (strpos($navgroup['permissions'], ':'))
				{
					//colon means any of these
					$groupPerms = explode(':', $navgroup['permissions']);
					$canShow = false;
					foreach ($groupPerms as $groupPerm)
					{
						if ($userContext->hasAdminPermission($groupPerm))
						{
							$canShow = true;
							continue;
						}
					}
				}
				else
				{
					//comma means *all* of these
					$canShow = true;
					$groupPerms = explode(',', $navgroup['permissions']);
					foreach ($groupPerms as $groupPerm)
					{
						if (!$userContext->hasAdminPermission($groupPerm))
						{
							$canShow = false;
							continue;
						}
					}
				}

				if (!$canShow)
				{
					continue;
				}
			}
			$group_displayorder = intval($navgroup['displayorder']);
			$group_key = fetch_nav_text($navgroup);

			if (!isset($navigation["$group_displayorder"]["$group_key"]))
			{
				$navigation["$group_displayorder"]["$group_key"] = ['options' => []];
			}
			$local_options =& $navigation["$group_displayorder"]["$group_key"]['options'];

			$navoptions = vB_XML_Parser::getList($navgroup, 'navoption');
			foreach ($navoptions AS $navoption)
			{
				if (!empty($navoption['debug']) AND ($vb5_config['Misc']['debug'] != 1))
				{
					continue;
				}
				// do we have access to this option?  permissions may be a delmited list
				if (!empty($navoption['permissions']))
				{
					if (strpos($navoption['permissions'], ':'))
					{
						//semicolon means any of these
						$optPerms = explode(':', $navoption['permissions']);
						$canShow = false;
						foreach ($optPerms as $optPerm)
						{
							if ($userContext->hasAdminPermission($optPerm))
							{
								$canShow = true;
								continue;
							}
						}
					}
					else
					{
						//comma means any of these
						$optPerms = explode(',', $navoption['permissions']);
						$canShow = true;
						foreach ($optPerms as $optPerm)
						{
							if (!$userContext->hasAdminPermission($optPerm))
							{
								$canShow = false;
								continue;
							}
						}
					}

					if (!$canShow)
					{
						continue;
					}
				}

				$navoption['link'] = str_replace(
					array(
						'{$vbulletin->config[Misc][modcpdir]}',
						'{$vbulletin->config[Misc][admincpdir]}',
					),
					array(
						$vb5_config['Misc']['modcpdir'],
						'admincp',
					),
					$navoption['link']
				);

				$navoption['text'] = fetch_nav_text($navoption);

				$local_options[intval($navoption['displayorder'])]["$navoption[text]"] = $navoption;
			}

			if (!isset($navigation["$group_displayorder"]["$group_key"]['group']) OR !empty($xml['master']))
			{
				unset($navgroup['navoption']);
				$navgroup['nav_file'] = $nav_file;
				$navgroup['text'] = $group_key;

				$navigation["$group_displayorder"]["$group_key"]['group'] = $navgroup;
			}
		}

		$xmlobj = null;
		unset($xml);
	}

	// Legacy Hook 'admin_index_navigation' Removed //

	// sort groups by display order
	ksort($navigation);
	foreach ($navigation AS $group_keys)
	{
		foreach ($group_keys AS $group_key => $navgroup_holder)
		{
			//treat a navgroup with no options as if the user doesn't
			//have permission to see the group.
			if($navgroup_holder['options'])
			{
				// sort options by display order
				ksort($navgroup_holder['options']);

				foreach ($navgroup_holder['options'] AS $navoption_holder)
				{
					foreach ($navoption_holder AS $navoption)
					{
						construct_nav_option($navoption['text'], $navoption['link']);
					}
				}

				// have all the options, so do the group
				$group = $navgroup_holder['group'];
				construct_nav_group($group['text'], $group['nav_file']);

				if (($group['hr'] ?? '') == 'true')
				{
					construct_nav_spacer();
				}
			}
		}
	}

	print_nav_panel();

	unset($navigation);

	echo "</div>\n";
	// *************************************************

	define('NO_CP_COPYRIGHT', true);
	print_cp_footer();
}

// #############################################################################
// ################################ BUILD FRAMESET #############################
// #############################################################################

if ($_REQUEST['do'] == 'frames' OR empty($_REQUEST['do']))
{
	$vbulletin->input->clean_array_gpc('r', [
		'locfile' => vB_Cleaner::TYPE_NOHTML,
		'locparams' => vB_Cleaner::TYPE_ARRAY_STR,
	]);

	$navframe = "<frame src=\"admincp/index.php?do=nav\" name=\"nav\" scrolling=\"yes\" frameborder=\"0\" marginwidth=\"0\" marginheight=\"0\" border=\"no\" id=\"vb-acp-navframe\" />\n";
	$headframe = "<frame src=\"admincp/index.php?do=head\" name=\"head\" scrolling=\"no\" noresize=\"noresize\" frameborder=\"0\" marginwidth=\"10\" marginheight=\"0\" border=\"no\" id=\"vb-acp-headframe\" />\n";

	// passing the entire url on the query string runs afoul of some security restrictions on apache mod_rewrite.
	if ($vbulletin->GPC['locfile'])
	{
		$url = get_admincp_url($vbulletin->GPC['locfile'], $vbulletin->GPC['locparams']);
	}
	else
	{
		$url = 'admincp/index.php?do=home';
	}

	$url = htmlspecialchars($url);
	$mainframe = "<frame src=\"$url\" name=\"main\" scrolling=\"yes\" frameborder=\"0\" marginwidth=\"10\" marginheight=\"10\" border=\"no\" id=\"vb-acp-mainframe\" />\n";

	// dir="ltr" is hardcoded for the frameset so that the frames render correctly
	// in IE. The contents of each frame use the correct dir value, based on the
	// textdirection stylevar. See VBV-13610.
	?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="<?php echo vB_Template_Runtime::fetchStyleVar('languagecode'); ?>">
	<head>
	<base href="<?php echo $datastore->getOption('frontendurl')?>/" />
	<script type="text/javascript">
	<!--
	// get out of any containing frameset
	if (self.parent.frames.length != 0)
	{
		self.parent.location.replace(document.location.href);
	}
	// -->
	</script>
	<title><?php echo $vbulletin->options['bbtitle'] . ' ' . $vbphrase['admin_control_panel']; ?></title>
	</head>

	<?php

	if (vB_Template_Runtime::fetchStyleVar('textdirection') == 'ltr')
	{
	// left-to-right frameset
	?>
	<frameset rows="85,*"  framespacing="0" border="0" frameborder="0" frameborder="no" border="0">
		<?php echo $headframe; ?>
		<frameset cols="256,*"  framespacing="0" border="0" frameborder="0" frameborder="no" border="0">
			<?php echo $navframe; ?>
			<?php echo $mainframe; ?>
		</frameset>
	</frameset>
	<?php
	}
	else
	{
	// right-to-left frameset
	?>
	<frameset rows="85,*"  framespacing="0" border="0" frameborder="0" frameborder="no" border="0">
		<?php echo $headframe; ?>
		<frameset cols="*,256"  framespacing="0" border="0" frameborder="0" frameborder="no" border="0">
			<?php echo $mainframe; ?>
			<?php echo $navframe; ?>
		</frameset>
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

// ################################ MAIN FRAME #############################

if ($_REQUEST['do'] == 'home')
{

$vbulletin->input->clean_array_gpc('r', array('showallnews' => vB_Cleaner::TYPE_BOOL));

$servertime = vbdate('%B %e, %Y %r', 0, false, true);
print_cp_header($vbphrase['welcome_to_the_vbulletin_admin_control_panel'], '', '', 0, '', '', $servertime);

$news_rows = [
	'sql_strict' => '',
	'php_optimizer' => '',
	'admin_messages' => '',
	'new_version' => '',
];

// look to see if MySQL is running in strict mode
$force_sql_mode = $assertor->getForceSqlMode();
if (empty($force_sql_mode))
{
	// check to see if MySQL is running strict mode and recommend disabling it
	$strict_mode_check = $assertor->getRow('showVariablesLike', array('var' => 'sql_mode'));
	if (strpos(strtolower($strict_mode_check['Value']), 'strict_') !== false)
	{
		ob_start();
		print_table_header($vbphrase['mysql_strict_mode_warning']);
		print_description_row('<div class="smallfont">' . $vbphrase['mysql_running_strict_mode'] . '</div>');
		$news_rows['sql_strict'] = ob_get_clean();
	}
}

// look for incomplete admin messages that may have actually been independently completed
// and say they're done
$donemessages_result = $assertor->getRows('getIncompleteAdminMessages');
foreach ($donemessages_result AS $donemessage)
{
	$assertor->update('adminmessage', array('status' => 'done'), array('adminmessageid' => intval($donemessage['adminmessageid'])));
}

// let's look for any messages that we need to display to the admin
$adminmessages_result = $assertor->getRows('adminmessage', array('status' => 'undone'), 'dateline');
$dismissableCount = 0;
foreach ($adminmessages_result AS $adminmessage)
{
	if ($adminmessage['dismissable'] OR !$adminmessage['execurl'])
	{
		++$dismissableCount;
	}
}
ob_start();
foreach ($adminmessages_result AS $adminmessage)
{
	$buttons = '';

	if ($adminmessage['execurl'])
	{
		$buttons .= '<input type="submit" name="address[' . $adminmessage['adminmessageid'] .']" value="' . $vbphrase['address'] . '" class="button adminmessage-button-item" />';
	}

	if ($adminmessage['dismissable'] OR !$adminmessage['execurl'])
	{
		$buttons .= ' <input type="submit" name="dismiss[' . $adminmessage['adminmessageid'] .']" value="' . $vbphrase['dismiss_gcphome'] . '" class="button adminmessage-button-item" />';
		if ($dismissableCount > 1)
		{
			$buttons .= ' <input type="checkbox" class="js-adminmessage-dismissmultiple h-align-middle adminmessage-button-item" name="dismissmultiple[' . $adminmessage['adminmessageid'] .']" value="1" title="' . $vbphrase['select'] . '" />';
		}
	}
	else
	{
		$buttons .= ' <span class="adminmessage-button-item"></span> ';
	}

	$args = @unserialize($adminmessage['args']);
	$title = '
		<div class="h-clearfix">
			<div class="h-right">' . $buttons . '</div>
			<div class="button-line-height">' . $vbphrase['admin_attention_required'] . '</div>
		</div>
	';
	print_description_row($title, false, 2, 'thead');
	print_description_row('<div class="smallfont">' . fetch_error($adminmessage['varname'], $args) . '</div>');
}
$admin_messages = ob_get_clean();
if (!empty($admin_messages))
{
	$news_rows['admin_messages'] = $admin_messages;
}

if ($userContext->hasAdminPermission('canadmintemplates'))
{
	// before the quick stats, display the number of templates that need updating
	require_once(DIR . '/includes/adminfunctions_template.php');
	$need_updates = fetch_changed_templates_count();
	if ($need_updates)
	{
		ob_start();
		print_description_row($vbphrase['out_of_date_custom_templates_found'], false, 2, 'thead');
		print_description_row(construct_phrase(
			'<div class="smallfont">' .  $vbphrase['currently_x_customized_templates_updated'] . '</div>',
			$need_updates,
			vB::getCurrentSession()->get('sessionurl')
		));
		$news_rows['new_version'] = ob_get_clean();
	}
}

echo '<div id="admin_news"' . (empty($news_rows) ? ' style="display: none;"' : '') . '>';
if (!empty($news_rows))
{
	print_form_header('admincp/index', 'handlemessage', false, true, 'news');

	print_table_header($vbphrase['news_header_string']);

	if ($dismissableCount > 1)
	{
		$html = '
			<input type="submit" class="button js-adminmessage-dismissmultiple-submit adminmessage-button-item" value="' . $vbphrase['dismiss_selected_messages'] . '" disabled="disabled" />
			<input type="checkbox" class="js-adminmessage-dismissmultiple-toggle h-align-middle adminmessage-button-item" title="' . $vbphrase['select_all_none'] . '" />
		';
		print_description_row($html, false, 2, 'tfoot');
	}

	echo $news_rows['new_version'];
	echo $news_rows['php_optimizer'];
	echo $news_rows['sql_strict'];
	echo $news_rows['admin_messages'];

	print_table_footer();
}
else
{
	print_form_header('admincp/index', 'handlemessage', false, true, 'news');

	print_table_footer();
}
echo '</div>'; // end of <div id="admin_news">

// *******************************
// Admin Quick Stats -- Toggable via the CP
$mysqlversion = $assertor->getRow('mysqlVersion');

try
{
	$variables = $assertor->getRow('showVariablesLike', array('var' => 'max_allowed_packet'));
}
catch (Exception $ex)
{
	$variables = false;
}

if ($variables)
{
	$maxpacket = $variables['Value'];
}
else
{
	$maxpacket = $vbphrase['n_a'];
}

$sapiextra =  ' (' . PHP_SAPI . ')';
$iscgi = (PHP_SAPI == 'cgi' OR PHP_SAPI == 'cgi-fcgi');
if (preg_match('#(Apache)/([0-9\.]+)\s#siU', $_SERVER['SERVER_SOFTWARE'], $wsregs))
{
	$webserver = "$wsregs[1] v$wsregs[2]";
	if ($iscgi)
	{
		$webserver .= $sapiextra;
	}
}
else if (preg_match('#Microsoft-IIS/([0-9\.]+)#siU', $_SERVER['SERVER_SOFTWARE'], $wsregs))
{
	$webserver = "IIS v$wsregs[1]" . $sapiextra;
}
else if (preg_match('#Zeus/([0-9\.]+)#siU', $_SERVER['SERVER_SOFTWARE'], $wsregs))
{
	$webserver = "Zeus v$wsregs[1]" . $sapiextra;
}
else if (strtoupper($_SERVER['SERVER_SOFTWARE']) == 'APACHE')
{
	$webserver = 'Apache';
	if ($iscgi)
	{
		$webserver .= $sapiextra;
	}
}
else
{
	$webserver = PHP_SAPI;
}

$serverinfo = (ini_get('file_uploads') == 0 OR strtolower(ini_get('file_uploads')) == 'off') ? "<br />$vbphrase[file_uploads_disabled]" : '';
$memorylimit = ini_get('memory_limit');
if($memorylimit AND $memorylimit != '-1')
{
	$memorylimit = vb_number_format($memorylimit, 2, true);
}
else
{
	$memorylimit = $vbphrase['none'];
}

// Moderation Counts //
$guids = array (
	vB_Channel::DEFAULT_FORUM_PARENT,
	vB_Channel::DEFAULT_SOCIALGROUP_PARENT,
	vB_Channel::VISITORMESSAGE_CHANNEL,
	//this is the badly named key for the special channel
	vB_Channel::DEFAULT_CHANNEL_PARENT,
);

$roots = $assertor->getColumn('vBForum:channel', 'nodeid', array('guid' => $guids), false, 'guid');
$vmrootid = $roots[vB_Channel::VISITORMESSAGE_CHANNEL];
$specialid = $roots[vB_Channel::DEFAULT_CHANNEL_PARENT];
$rootids = array($roots[vB_Channel::DEFAULT_FORUM_PARENT], $roots[vB_Channel::DEFAULT_SOCIALGROUP_PARENT]);

// Note the returned field here is automatically called 'count'.
$waiting = $assertor->getRow('user', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_COUNT, 'usergroupid' => 4));

$phototype = vB_Types::instance()->getContentTypeId('vBForum_Photo');
$attachtype = vB_Types::instance()->getContentTypeId('vBForum_Attach');
$typeids = array($phototype, $attachtype);

$postcount = $assertor->getRow('getModeratedReplies', array('typeids' => $typeids, 'rootids' => $rootids));
$threadcount = $assertor->getRow('getModeratedTopics', array('typeids' => $typeids, 'rootids' => $rootids));
$messagecount = $assertor->getRow('getModeratedVisitorMessages', array('typeid' => $vmrootid));

$mailqueue = $assertor->getRow('vBAdmincp:getQueuedMessageCount', array());

$postmaxsize = ini_get('post_max_size');
if($postmaxsize)
{
	$postmaxsize = vb_number_format($postmaxsize, 2, true);
}
else
{
	$postmaxsize = $vbphrase['n_a'];
}

$postmaxuploadsize = ini_get('upload_max_filesize');
if($postmaxuploadsize)
{
	$postmaxuploadsize = vb_number_format($postmaxuploadsize, 2, true) ;
}
else
{
	$postmaxuploadsize = $vbphrase['n_a'];
}

//the base columns.  We'll tweak it based on which stat mode we are in.

//if we get an error fetching the view link, just omit it.  It's not important enough to make a fuss over.
$viewmoderationlink = '';
$messageInfo = vB_Api::instance('content_privatemessage')->fetchSummary();
if(!isset($messageInfo['errors']))
{
	$url = vB5_Route::buildUrl('privatemessage|fullurl', array('action' => 'pendingposts', 'folderid' => $messageInfo['folders']['pending']['folderid']));
	$viewmoderationlink = construct_link_code2($vbphrase['view'], $url, true);
}

$columns = 	array(
	'mainversions' => array(
		'server_type' => PHP_OS . $serverinfo,
		'web_server' => $webserver,
		'PHP' => PHP_VERSION,
		'php_max_post_size' => $postmaxsize,
		'php_max_upload_size' => $postmaxuploadsize,
		'php_memory_limit' => $memorylimit,
		'facebook_api_version' => vB_Library_Facebook::FBVERSION,
	),
	'secondary' => array(
		'users_awaiting_moderation_gcphome' => vb_number_format($waiting['count']) . '&nbsp;&nbsp;' .
			construct_link_code2($vbphrase['view'], 'admincp/user.php?do=moderate'),
		'threads_awaiting_moderation_gcphome' =>  vb_number_format($threadcount['count']) . '&nbsp;&nbsp;' . $viewmoderationlink,
		'posts_awaiting_moderation_gcphome' => vb_number_format($postcount['count']) . '&nbsp;&nbsp;' . $viewmoderationlink,
		'messages_awaiting_moderation' => vb_number_format($messagecount['count']),
		'queued_emails' => vb_number_format($mailqueue['queued']),
		'mysql_version_gcphome' => $mysqlversion['version'],
		'mysql_max_packet_size' => vb_number_format($maxpacket, 2, 1),
	),
	'extrastats' => array(
	),
);

print_form_header('admincp/index', 'home');
if ($vbulletin->options['adminquickstats'])
{
	$acpstats = $datastore->getValue('acpstats');
	if ($acpstats['lastupdate'] < (vB::getRequest()->getTimeNow() - 3600))
	{
		$acpstats = build_acpstats_datastore($specialid);
	}

	if ($acpstats['datasize'] == -1)
	{
		$acpstats['datasize'] = $vbphrase['n_a'];
	}

	if ($acpstats['indexsize'] == -1)
	{
		$acpstats['indexsize'] = $vbphrase['n_a'];
	}

	//we can't make this part of the base columns because the acpstats array
	//isn't going to loaded if the adminquickstats isn't on -- and we don't want
	//to load it/fill out the additional items because the main reason to hide
	//them is to avoid loading that in the first place.
	$columns['extrastats'] = array(
		'database_data_usage' => vb_number_format($acpstats['datasize'], 2, true),
		'database_index_usage' => vb_number_format($acpstats['indexsize'], 2, true),
		'attachment_usage' => vb_number_format($acpstats['attachsize'], 2, true),
		'custom_avatar_usage' => vb_number_format($acpstats['avatarsize'], 2, true),
		'unique_registered_visitors_today' => vb_number_format($acpstats['userstoday']),
		'new_users_today' => vb_number_format($acpstats['newusers']),
		'new_threads_today' => vb_number_format($acpstats['newthreads']),
		'new_posts_today' => vb_number_format($acpstats['newposts']),
	);
}
else
{
	//hide the empty extra column
	unset($columns['extrastats']);

	//we don't show these in standard mode -- I'm not sure why
	unset($columns['secondary']['messages_awaiting_moderation']);
}

//translate columns to row.  We need rows for output but the relationships between items are
//by column (or instance if we remove an item we want the column to slide up)
$rowcount = 0;
foreach($columns AS $column)
{
	$rowcount = max($rowcount, count($column));
}

$stats = array();
foreach($columns AS $column)
{
	$row = 0;
	foreach($column AS $label => $stat)
	{
		$stats[$row][] = $vbphrase[$label] ?? $label;
		$stats[$row][] = $stat;
		$row++;
	}

	//fill out any empty cells at the end of the column
	for(; $row < $rowcount; $row++)
	{
		$stats[$row][] = '&nbsp;';
		$stats[$row][] = '&nbsp;';
	}
}

print_table_header($vbphrase['welcome_to_the_vbulletin_admin_control_panel'], 2 * count($columns));

foreach($stats AS $cells)
{
	print_cells_row($cells, 0, 0, -5, 'top', 1, 1);
}
print_table_footer();
// Legacy Hook 'admin_index_main1' Removed //

// *************************************
// Administrator Notes

print_form_header('admincp/index', 'notes');
print_table_header($vbphrase['administrator_notes'], 1);
print_description_row("<textarea name=\"notes\" style=\"width: 90%\" rows=\"9\" tabindex=\"1\">" . $vbulletin->userinfo['notes'] . "</textarea>", false, 1, '', 'center');
print_submit_row($vbphrase['save'], 0, 1);

// Legacy Hook 'admin_index_main2' Removed //

// *************************************
// QUICK ADMIN LINKS

print_table_start();
print_table_header($vbphrase['quick_administrator_links']);

// ### MAX LOGGEDIN USERS ################################

$is_windows = (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN');
$loadavg = [];

//windows doesn't have the available functions to compute load average.
if(!$is_windows)
{
	if (function_exists('exec') AND $stats = @exec('uptime 2>&1') AND trim($stats) != '' AND preg_match('#: ([\d.,]+),?\s+([\d.,]+),?\s+([\d.,]+)$#', $stats, $regs))
	{
		$loadavg = array_slice($regs, 1, 3);
	}
	else if (@file_exists('/proc/loadavg') AND $stats = @file_get_contents('/proc/loadavg') AND trim($stats) != '')
	{
		$loadavg = explode(' ', $stats);
	}

	$loadavg = array_map(function($x) {return vb_number_format($x, 2);}, $loadavg);
}

//will set the max logged in values as a side effect.
$wolcounts = vB_Api::instance('wol')->fetchCounts();
if(isset($wolcounts['errors']))
{
	$woltext = fetch_error($wolcounts['errors'][0]);
}
else
{
	if (!empty($loadavg))
	{
		$wolphrase = 'users_online_x_members_y_guests';
	}
	else
	{
		$wolphrase = 'x_y_members_z_guests';
	}

	$woltext = construct_phrase($vbphrase[$wolphrase], vb_number_format($wolcounts['total']), vb_number_format($wolcounts['members']), vb_number_format($wolcounts['guests']));
}

if (!empty($loadavg))
{
	print_label_row($vbphrase['server_load_averages'], "$loadavg[0]&nbsp;&nbsp;$loadavg[1]&nbsp;&nbsp;$loadavg[2] | " . $woltext, '', 'top', NULL, false);
}
else
{
	print_label_row($vbphrase['users_online'], $woltext, '', 'top', NULL, false);
}

if ($userContext->hasAdminPermission('canadminusers'))
{
	print_label_row($vbphrase['quick_user_finder'], '
		<form action="admincp/user.php?do=find" method="post" style="display:inline">
		<input type="hidden" name="s" value="' . vB::getCurrentSession()->get('sessionhash') . '" />
		<input type="hidden" name="adminhash" value="' . ADMINHASH . '" />
		<input type="hidden" name="do" value="find" />
		<input type="text" class="bginput" name="user[username_or_email]" size="30" tabindex="1" />
		<input type="submit" value=" ' . $vbphrase['find'] . ' " class="button" tabindex="1" />
		<input type="submit" class="button" value="' . $vbphrase['exact_match'] . '" tabindex="1" name="user[exact]" />
		</form>
		', '', 'top', NULL, false
	);
}

print_label_row($vbphrase['quick_phrase_finder'], '
	<form action="admincp/phrase.php?do=dosearch" method="post" style="display:inline">
	<input type="text" class="bginput" name="searchstring" size="30" tabindex="1" />
	<input type="submit" value=" ' . $vbphrase['find'] . ' " class="button" tabindex="1" />
	<input type="hidden" name="do" value="dosearch" />
	<input type="hidden" name="languageid" value="-10" />
	<input type="hidden" name="searchwhere" value="10" />
	<input type="hidden" name="adminhash" value="' . ADMINHASH . '" />
	</form>
	', '', 'top', NULL, false
);

print_label_row($vbphrase['php_function_lookup'], '
	<form action="//www.ph' . 'p.net/manual-lookup.ph' . 'p" method="get" style="display:inline" target="_blank">
	<input type="text" class="bginput" name="function" size="30" tabindex="1" />
	<input type="submit" value=" ' . $vbphrase['find'] . ' " class="button" tabindex="1" />
	</form>
	', '', 'top', NULL, false
);
print_label_row($vbphrase['mysql_language_lookup'], '
	<form action="//www.mysql.com/search/" method="get" style="display:inline" target="_blank">
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
				'https://www.vbulletin.com/' => $vbphrase['home_page_gcpglobal'] . ' (vBulletin.com)',
				'https://members.vbulletin.com/' => $vbphrase['members_area'],
				'https://www.vbulletin.com/forum/' => $vbphrase['community_forums'],
				'https://www.vbulletin.com/docs/html/' => $vbphrase['reference_manual']
			),
			'PHP' => array(
				'http://www.ph' . 'p.net/' => $vbphrase['home_page_gcpglobal'] . ' (PHP.net)',
				'http://www.ph' . 'p.net/manual/' => $vbphrase['reference_manual'],
				'http://www.ph' . 'p.net/downloads.ph' . 'p' => $vbphrase['download_latest_version']
			),
			'MySQL' => array(
				'http://www.mysql.com/' => $vbphrase['home_page_gcpglobal'] . ' (MySQL.com)',
				'http://dev.mysql.com/doc/' => $vbphrase['reference_manual'],
				'http://www.mysql.com/downloads/' => $vbphrase['download_latest_version'],
			),
			'Apache' => array(
				'http://httpd.apache.org/' => $vbphrase['home_page_gcpglobal'] . ' (Apache.org)',
				'http://httpd.apache.org/docs/' => $vbphrase['reference_manual'],
				'http://httpd.apache.org/download.cgi' => $vbphrase['download_latest_version'],
			),
	)) . '</select>
	</form>
	', '', 'top', NULL, false
);
print_table_footer(2, '', '', false);

// Legacy Hook 'admin_index_main3' Removed //

// *************************************
// vBULLETIN CREDITS
require_once(DIR . '/includes/vbulletin_credits.php');

register_js_phrase([
	'latest_version_available_x',
	'you_are_running_vbulletin_version_x',
	'download_vbulletin_x_from_members_area',
	'there_is_a_newer_vbulletin_version',
]);

?>

<script type="text/javascript">
<!--
var dismiss_string = "<?php echo $vbphrase['dismiss_gcphome']; ?>";
var vbulletin_news_string = "<?php echo $vbphrase['vbulletin_news_x']; ?>";
var news_header_string = "<?php echo $vbphrase['news_header_string']; ?>";
var show_all_news_string = "<?php echo $vbphrase['show_all_news']; ?>";
var view_string = "<?php echo $vbphrase['view']; ?>...";

var show_all_news_link = "admincp/index.php?do=home&showallnews=1";
var dismissed_news = "<?php echo ($vbulletin->GPC['showallnews'] ? '' : $vbulletin->userinfo['dismissednews']); ?>";
var stylevar_left = "<?php echo vB_Template_Runtime::fetchStyleVar('left'); ?>";
var stylevar_right = "<?php echo vB_Template_Runtime::fetchStyleVar('right'); ?>";
var done_table = <?php echo (empty($news_rows) ? 'false' : 'true'); ?>;
var current_version = "<?php echo ADMIN_VERSION_VBULLETIN; ?>";
var local_extension = '.php';
//-->
</script>
<script type="text/javascript" src="<?php echo $versionhost; ?>/versioncheck.js?v=<?php echo SIMPLE_VERSION; ?>"></script>
<script type="text/javascript" src="<?php echo $versionhost; ?>/version.js?v=<?php echo SIMPLE_VERSION; ?>&amp;id=LN05842122&amp;pid=vb6"></script>
<script type="text/javascript" src="core/clientscript/vbulletin_cphome_scripts.js?v=<?php echo SIMPLE_VERSION; ?>"></script>
<?php

print_cp_footer();

}

// ################################ SHOW PHP INFO #############################

if ($_REQUEST['do'] == 'phpinfo')
{
	if (is_demo_mode())
	{
		print_cp_message('This function is disabled within demo mode');
	}
	else
	{
		if (!$userContext->hasAdminPermission('canuseallmaintenance'))
		{
			print_cp_no_permission();
		}

		phpinfo();
		exit;
	}
}

// ################################ HANDLE ADMIN MESSAGES #############################
if ($_POST['do'] == 'handlemessage')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'address'         => vB_Cleaner::TYPE_ARRAY_KEYS_INT,
		'dismiss'         => vB_Cleaner::TYPE_ARRAY_KEYS_INT,
		'dismissmultiple' => vB_Cleaner::TYPE_ARRAY_KEYS_INT,
		'acpnews'         => vB_Cleaner::TYPE_ARRAY_KEYS_INT
	));

	print_cp_header($vbphrase['welcome_to_the_vbulletin_admin_control_panel']);

	if ($vbulletin->GPC['address'])
	{
		// chosen to address the issue -- redirect to the appropriate page
		$adminmessageid = intval($vbulletin->GPC['address'][0]);
		$adminmessage = vB::getDbAssertor()->getRow('adminmessage', array('adminmessageid' => $adminmessageid));

		if (!empty($adminmessage))
		{
			// set the issue as addressed
			vB::getDbAssertor()->update(
					'adminmessage',
					array('status' => 'done', 'statususerid' => $vbulletin->userinfo['userid']),
					array('adminmessageid' => $adminmessageid)
			);
		}

		if (!empty($adminmessage) AND !empty($adminmessage['execurl']))
		{
			if ($adminmessage['method'] == 'get')
			{
				// get redirect -- can use the url basically as is
				if (!strpos($adminmessage['execurl'], '?'))
				{
					$adminmessage['execurl'] .= '?';
				}
				$args = array();

				$execurl =  vB_String::parseUrl($adminmessage['execurl'] . vB::getCurrentSession()->get('sessionurl_js'));
				$pathinfo = pathinfo($execurl['path']);
				$file = $pathinfo['basename'];

				//as part of VBV-14906 we need to start with 'admincp'
				if (substr($file, 0, 1) == '/')
				{
					$file = 'admincp' . $file;
				}
				else
				{
					$file = 'admincp/' . $file;
				}

				parse_str($execurl['query'], $args);
				print_cp_redirect2($file, $args);
			}
			else
			{
				// post redirect -- need to seperate into <file>?<querystring> first
				if (preg_match('#^(.+)\?(.*)$#siU', $adminmessage['execurl'], $match))
				{
					$script = $match[1];
					$arguments = explode('&', $match[2]);
				}
				else
				{
					$script = $adminmessage['execurl'];
					$arguments = array();
				}

				//as part of VBV-14906 we need to start with 'admincp'
				if (substr($script, 0, 1) == '/')
				{
					$script = 'admincp' . $script;
				}
				else
				{
					$script = 'admincp/' . $script;
				}

				echo '
					<form action="' . htmlspecialchars($script) . '" method="post" id="postform">
				';

				foreach ($arguments AS $argument)
				{
					// now take each element in the query string into <name>=<value>
					// and stuff it into hidden form elements
					if (preg_match('#^(.*)=(.*)$#siU', $argument, $match))
					{
						$name = $match[1];
						$value = $match[2];
					}
					else
					{
						$name = $argument;
						$value = '';
					}
					echo '
						<input type="hidden" name="' . htmlspecialchars(urldecode($name)) . '" value="' . htmlspecialchars(urldecode($value)) . '" />
					';
				}

				// Also add admin hash & security token.
				echo '
						<input type="hidden" name="s" value="' . vB::getCurrentSession()->get('sessionhash') . '" />
						<input type="hidden" name="adminhash" value="' . ADMINHASH . '" />
				';

				// and submit the form automatically
				echo '
					</form>
					<script type="text/javascript">
					<!--
					fetch_object(\'postform\').submit();
					// -->
					</script>
				';
			}

			print_cp_footer();
		}
	}
	else if ($vbulletin->GPC['dismiss'])
	{
		$adminmessageid = intval($vbulletin->GPC['dismiss'][0]);

		vB::getDbAssertor()->update('adminmessage', array('status' => 'dismissed'), array('adminmessageid' => $adminmessageid));
	}
	else if ($vbulletin->GPC['dismissmultiple'])
	{
		$adminmessageids = array();
		foreach ($vbulletin->GPC['dismissmultiple'] AS $one)
		{
			// excessive paranoia
			$adminmessageids[] = (int) $one;
		}

		vB::getDbAssertor()->update('adminmessage', array('status' => 'dismissed'), array('adminmessageid' => $adminmessageids));
	}
	else if ($vbulletin->GPC['acpnews'])
	{
		$items = preg_split('#\s*,\s*#s', $vbulletin->userinfo['dismissednews'], -1, PREG_SPLIT_NO_EMPTY);
		$items[] = intval($vbulletin->GPC['acpnews'][0]);
		$vbulletin->userinfo['dismissednews'] = implode(',', array_unique($items));

		$admindata = new vB_DataManager_Admin(vB_DataManager_Constants::ERRTYPE_CP);
		$getperms = $assertor->getRow('vBForum:administrator', array('userid' => $vbulletin->userinfo['userid']));
		if ($getperms)
		{
			$admindata->set_existing($vbulletin->userinfo);
		}
		else
		{
			$admindata->set('userid', $vbulletin->userinfo['userid']);
		}

		$admindata->set('dismissednews', $vbulletin->userinfo['dismissednews']);
		$admindata->save();
	}
	$args = array();
	print_cp_redirect2('admincp/index', array('do' => 'home'), 2, 'admincp');
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 116812 $
|| #######################################################################
\*=========================================================================*/
