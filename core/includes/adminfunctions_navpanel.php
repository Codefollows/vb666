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

define('IS_NAV_PANEL', true);

// #################################################################
// ############## NAVBODY LINKS/OPTIONS FUNCTIONS ##################
// #################################################################


function init_nav_prefs($force = true)
{
	//this is refactored from can_administer, which is a weird place for it. It does some weird things with
	//checking both the user array and the admin record.  I'm about 98% sure that the admin record check
	//is completely unnecesary.  One change here is that were would overwrite the user array version
	//based on the the admin record (once) IIF the user is an admin but not a superadmin.  However that's
	//only place it's stored so if we have it in the user record then it's the same or possibly more
	//up to date.  We should really clean up all of the global vars in this file, but at least
	//sweeping them into this file.
	global $vbulletin, $_NAVPREFS;

	if (!isset($_NAVPREFS) OR $force)
	{
		//don't use isset, navprefs can be null and if so we don't want to run the query
		if(array_key_exists('navprefs', $vbulletin->userinfo))
		{
			$prefs = $vbulletin->userinfo['navprefs'];
		}
		else
		{
			// this will be empty for a moderator in the modcp
			$row = vB::getDbAssertor()->getRow('vBForum:administrator', [
				vB_dB_Query::COLUMNS_KEY => ['navprefs'],
				'userid' => $vbulletin->userinfo['userid'],
			]);
			$prefs = $row['navprefs'] ?? '';
		}

		$_NAVPREFS = preg_split('#,#', strval($prefs), -1, PREG_SPLIT_NO_EMPTY);
	}
}

// ###################### Start construct_nav_spacer #######################
function construct_nav_spacer($wide = false)
{
	global $_NAV;

	$_NAV .= '<div class="' . ($wide ? 'nav-spacer-wide' : 'nav-spacer') . '"></div>';
}

// ###################### Start makenavoption #######################
function construct_nav_option($title, $url, $prefix = 'admincp')
{
	global $options;
	$url_query = vB_String::parseUrl($url, PHP_URL_QUERY);
	if (!isset($options))
	{
		$options = array();
	}
	$url .= (empty($url_query) ? '?' : '&amp;');
	$options[] = "\t\t<a class=\"navlink\" href=\"$prefix/$url\">$title</a>\n";
}

function fetch_nav_text($navoption)
{
	global $vbphrase;

	if (isset($navoption['phrase']) AND isset($vbphrase["$navoption[phrase]"]))
	{
		return $vbphrase["$navoption[phrase]"];
	}
	else if (isset($navoption['text']))
	{
		return $navoption['text'];
	}
	else
	{
		return '*[' . $navoption['phrase'] . ']*';
	}
}

// ###################### Start makenavselect #######################
function construct_nav_group($title, $nav_file = 'vbulletin')
{
	init_nav_prefs();
	global $_NAV, $_NAVPREFS, $vbulletin, $vbphrase, $options, $groupid;
	static $localphrase = array(), $navlinks = '';

	$vb_options = vB::getDatastore()->getValue('options');

	if (VB_AREA == 'AdminCP')
	{
		if (!isset($groupid))
		{
			$groupid = array();
			$navlinks = implode(',', $_NAVPREFS);
			$localphrase = array(
				'expand_group' => $vbphrase['expand_group'],
				'collapse_group' => $vbphrase['collapse_group']
			);
		}

		if (!isset($groupid["$nav_file"]))
		{
			$groupid["$nav_file"] = 0;
		}

		if (in_array("{$nav_file}_" . $groupid["$nav_file"], $_NAVPREFS))
		{
			$dowhat = 'collapse';
			$imagefile = 'cp_' . $dowhat;
			$style = '';
			$tooltip = $localphrase['collapse_group'];
			$addtableclass = ' navtitle-open';
		}
		else
		{
			$dowhat = 'expand';
			$imagefile = 'cp_' . $dowhat . '_' . vB_Template_Runtime::fetchStyleVar('textdirection');
			$style = 'display:none';
			$tooltip = $localphrase['expand_group'];
			$addtableclass = '';
		}

		$_NAV .= "\n\t<a name=\"grp{$nav_file}_$groupid[$nav_file]\"></a>
		<table id=\"grouptable_{$nav_file}_$groupid[$nav_file]\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\" class=\"navtitle$addtableclass\" onclick=\"toggle_group('{$nav_file}_$groupid[$nav_file]'); return false;\">
		<tr>
			<td>$title</td>
			<td align=\"" . vB_Template_Runtime::fetchStyleVar('right') . "\">
				<a href=\"admincp/index.php?do=buildnavprefs&amp;nojs=" . $vbulletin->GPC['nojs'] . "&amp;prefs=$navlinks&amp;dowhat=$dowhat&amp;id={$nav_file}_$groupid[$nav_file]#grp{$nav_file}_$groupid[$nav_file]\" target=\"_self\"

					oncontextmenu=\"toggle_group('{$nav_file}_$groupid[$nav_file]'); save_group_prefs('{$nav_file}_$groupid[$nav_file]'); return false\"
				><img class=\"acp-nav-arrow\" src=\"" . get_cpstyle_image($imagefile) . "\" title=\"$tooltip\" id=\"button_{$nav_file}_$groupid[$nav_file]\" alt=\"+\" border=\"0\" /></a>
			</td>
		</tr>
		</table>";
		$_NAV .= "
		<div id=\"group_{$nav_file}_$groupid[$nav_file]\" class=\"navgroup\" style=\"$style\">\n";

		$groupid[$nav_file]++;
	}
	else
	{
		$_NAV .= "\n\t
		<div class=\"navtitle\">$title</div>
		<div class=\"navgroup\">\n";
	}

	foreach ($options AS $link)
	{
		$_NAV .= $link;
	}

	$_NAV .= "\t\t</div>\n";

	$options = array();
}

function print_nav_panel()
{
	init_nav_prefs();
	global $_NAV, $_NAVPREFS, $groupid, $vbulletin, $vbphrase;
	$options = vB::getDatastore()->getValue('options');

	$controls = "<div class=\"acp-nav-controls\"><a href=\"admincp/index.php?" . vB::getCurrentSession()->get('sessionurl') . "do=home\">$vbphrase[control_panel_home]</a></div>";

	if (VB_AREA != 'AdminCP')
	{
		echo $controls . $_NAV;
		return;
	}

	$groups = implode(',', array_keys($groupid));
	$numgroups = array();
	$navprefs = array();
	foreach ($groupid AS $nav_file => $ids)
	{
		$numgroups[] = "num$nav_file=" . intval($ids);
		$navs[] = $nav_file;
		for ($i = 0; $i < $ids; $i++)
		{
			$navprefs["$nav_file"]["$i"] = iif(in_array("{$nav_file}_{$i}", $_NAVPREFS), 1, 0);
		}
	}

	$numgroups = implode('&amp;', $numgroups);

	if ($vbulletin->GPC['nojs'])
	{
		$controls .= "<div class=\"acp-nav-controls\">
			<div>
				<a class=\"nav-left\" href=\"admincp/index.php?do=navprefs&amp;nojs=" . $vbulletin->GPC['nojs'] . "&amp;groups=$groups&amp;expand=1&amp;$numgroups\" onclick=\"expand_all_groups(1); return false;\" target=\"_self\">" . $vbphrase['expand_all'] . "</a>
				<a class=\"nav-right\" href=\"admincp/index.php?do=navprefs&amp;nojs=" . $vbulletin->GPC['nojs'] . "&amp;groups=$groups&amp;expand=0&amp;$numgroups\" onclick=\"expand_all_groups(0); return false;\" target=\"_self\">" . $vbphrase['collapse_all'] . "</a>
			</div>
		</div>";
	}
	else
	{
		$controls .= "<div class=\"acp-nav-controls\">
			<div>
				<a class=\"nav-left\" href=\"admincp/index.php?do=navprefs&amp;nojs=" . $vbulletin->GPC['nojs'] . "&amp;groups=$groups&amp;expand=1&amp;$numgroups\" onclick=\"expand_all_groups(1); return false;\" target=\"_self\">" . $vbphrase['expand_all'] . "</a>
				<a class=\"nav-right\" href=\"admincp/index.php?do=navprefs&amp;nojs=" . $vbulletin->GPC['nojs'] . "&amp;groups=$groups&amp;expand=0&amp;$numgroups\" onclick=\"expand_all_groups(0); return false;\" target=\"_self\">" . $vbphrase['collapse_all'] . "</a>
			</div>
			<div>
				<a class=\"nav-left\" href=\"#\" onclick=\"save_group_prefs(-1); return false\">$vbphrase[save_prefs]</a>
				<a class=\"nav-right\" href=\"#\" onclick=\"read_group_prefs(); return false\">$vbphrase[revert_prefs]</a>
			</div>
		</div>";
	}

	?>
	<script type="text/javascript">
	<!--
	var expanded = false;
	var autosave = <?php echo iif($vbulletin->GPC['nojs'], 'true', 'false'); ?>;
<?php
	foreach ($navprefs AS $name => $prefs)
	{
		if (sizeof($prefs) == 1)
		{
			echo "\tvar nav$name = new Array(1);\n";
			echo "\tnav{$name}[0] = $prefs[0];\n";
		}
		else
		{
			echo "\tvar nav$name = new Array(" . implode(",", $prefs) . ");\n";
		}
	}
?>
	var files = new Array('<?php echo implode("','", $navs); ?>');
	function open_close_group(group, doOpen)
	{
		var curdiv = fetch_object("group_" + group);
		var curbtn = fetch_object("button_" + group);
		var curtbl = fetch_object("grouptable_" + group);

		if (doOpen)
		{
			curdiv.style.display = "";
			curbtn.src = "<?php echo get_cpstyle_image('cp_collapse'); ?>";
			curbtn.title = "<?php echo $vbphrase['collapse_group']; ?>";
			curtbl.className = "navtitle navtitle-open";
		}
		else
		{
			curdiv.style.display = "none";
			curbtn.src = "<?php echo get_cpstyle_image('cp_expand_' . vB_Template_Runtime::fetchStyleVar('textdirection')); ?>";
			curbtn.title = "<?php echo $vbphrase['expand_group']; ?>";
			curtbl.className = "navtitle";
		}
	}

	function toggle_group(group)
	{
		var curdiv = fetch_object("group_" + group);

		if (curdiv.style.display == "none")
		{
			open_close_group(group, true);
		}
		else
		{
			open_close_group(group, false);
		}

		if (autosave)
		{
			save_group_prefs(group);
		}
	}

	function expand_all_groups(doOpen)
	{
		var navobj = null;
		for (nav_file in files)
		{
			navobj = eval('nav' + files[nav_file]);
			for (var i = 0; i < navobj.length; i++)
			{
				open_close_group(files[nav_file] + '_' + i, doOpen);
			}
		}

		if (autosave)
		{
			save_group_prefs(-1);
		}
	}

	function save_group_prefs(groupid)
	{
		var opengroups = new Array();
		var counter = 0;
		var navobj = null;

		for (nav_file in files)
		{
			navobj = eval('nav' + files[nav_file]);
			for (var i = 0; i < navobj.length; i++)
			{
				if (fetch_object("group_" + files[nav_file] + '_' + i).style.display != "none")
				{
					opengroups[counter] = files[nav_file] + '_' + i;
					counter++;
				}
			}
		}

		vBRedirect("admincp/index.php?<?php echo vB::getCurrentSession()->get('sessionurl_js'); ?>do=savenavprefs&nojs=<?php echo $vbulletin->GPC['nojs']; ?>&navprefs=" + opengroups.join(",") + "#grp" + groupid);
	}

	function read_group_prefs()
	{
		var navobj = null;
		for (nav_file in files)
		{
			navobj = eval('nav' + files[nav_file]);
			for (var i = 0; i < navobj.length; i++)
			{
				open_close_group(files[nav_file] + '_' + i, navobj[i]);
			}
		}
	}
	//-->
	</script>
	<?php

	echo $controls . $_NAV;
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 116813 $
|| #######################################################################
\*=========================================================================*/
