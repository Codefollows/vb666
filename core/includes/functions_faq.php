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

// ###################### Start makeAdminFaqRow #######################
function print_faq_admin_row($faq, $prefix = '')
{
	global $ifaqcache, $vbphrase;

	$firstcolumntext = $prefix . '<b></b>';
	if(isset($ifaqcache[$faq['faqname']]) AND is_array($ifaqcache[$faq['faqname']]))
	{
		$firstcolumntext .= '<a href="admincp/faq.php?faq=' . urlencode($faq['faqname']) .
			"\" title=\"$vbphrase[show_child_faq_entries]\">$faq[title]</a>";
	}
	else
	{
		$firstcolumntext .= $faq['title'];
	}
	$firstcolumntext .= '<b></b>';

	$cell = [
		$firstcolumntext,
		// second column
		"<input type=\"text\" class=\"bginput\" size=\"4\" name=\"order[$faq[faqname]]\" title=\"$vbphrase[display_order]\" tabindex=\"1\" value=\"$faq[displayorder]\" />",
		// third column
		construct_link_code($vbphrase['edit'], 'faq.php?do=edit&amp;faq=' . urlencode($faq['faqname'])) .
		construct_link_code($vbphrase['add_child_faq_item'], 'faq.php?do=add&amp;faq=' . urlencode($faq['faqname'])) .
		construct_link_code($vbphrase['delete'], 'faq.php?do=delete&amp;faq=' . urlencode($faq['faqname'])),
	];
	print_cells_row($cell);
}

// ###################### Start getFaqParents #######################
// get parent titles function for navbar
function fetch_faq_parents($faqname)
{
	global $ifaqcache, $faqcache, $parents;
	static $i = 0;

	if(isset($faqcache[$faqname]))
	{
		$faq = $faqcache[$faqname];
		if (is_array($ifaqcache[$faq['faqparent']]))
		{
			$key = ($i++ ? 'faq.php?faq=' . $faq['faqname'] : '');
			$parents[$key] = $faq['title'];
			fetch_faq_parents($faq['faqparent']);
		}
	}
}

// ###################### Start getifaqcache #######################
function cache_ordered_faq($gettext = false, $disableproducts = false, $languageid = null)
{
	global $vbulletin, $faqcache, $ifaqcache;
	$assertor = vB::getDbAssertor();

	if ($languageid === null)
	{
		$languageid = LANGUAGEID;
	}

	// ordering arrays
	$displayorder = [];
	$languageorder = [];

	// data cache arrays
	$faqcache = [];
	$ifaqcache = [];
	$phrasecache = [];

	$fieldname = ($gettext) ? ['faqtitle', 'faqtext'] : 'faqtitle';
	$phrases = $assertor->assertQuery('vBForum:phrase',
		[
			'fieldname' => $fieldname,
			'languageid' => [-1, 0, $languageid]
		]
	);

	foreach($phrases AS $phrase)
	{
		$languageorder[$phrase['languageid']][] = $phrase;
	}

	ksort($languageorder);

	foreach($languageorder AS $phrases)
	{
		foreach($phrases AS $phrase)
		{
			$phrasecache[$phrase['varname']] = $phrase['text'];
		}
	}
	unset($languageorder);

	$conditions = [];
	if ($disableproducts)
	{
		$activeproducts = ['', 'vbulletin'];
		foreach ($vbulletin->products AS $product => $active)
		{
			if ($active)
			{
				$activeproducts[] = $product;
			}
		}
		$conditions['product'] = $activeproducts;
	}

	$faqs = $assertor->assertQuery('vBForum:faq', $conditions);
	foreach($faqs AS $faq)
	{
		$faq['title'] = $phrasecache["$faq[faqname]_gfaqtitle"];
		if ($gettext)
		{
			$faq['text'] = $phrasecache["$faq[faqname]_gfaqtext"];
		}
		$faqcache[$faq['faqname']] = $faq;
		$displayorder[$faq['displayorder']][] =& $faqcache[$faq['faqname']];
	}

	ksort($displayorder);

	$ifaqcache = ['faqroot' => []];

	foreach($displayorder AS $faqs)
	{
		foreach($faqs AS $faq)
		{
			$ifaqcache[$faq['faqparent']][$faq['faqname']] =& $faqcache[$faq['faqname']];
		}
	}
}

// ###################### Start getFaqParentOptions #######################
function fetch_faq_parent_options(&$parentoptions, $thisitem = '', $parentname = 'faqroot', $depth = 1)
{
	global $ifaqcache;
	foreach($ifaqcache["$parentname"] AS $faq)
	{
		if ($faq['faqname'] != $thisitem)
		{
			$parentoptions["$faq[faqname]"] = str_repeat('--', $depth) . ' ' . $faq['title'];
			if (isset($ifaqcache[$faq['faqname']]) AND is_array($ifaqcache[$faq['faqname']]))
			{
				fetch_faq_parent_options($parentoptions, $thisitem, $faq['faqname'], $depth + 1);
			}
		}
	}

	return $parentoptions;
}

// ###################### Start getFaqDeleteList #######################
function fetch_faq_delete_list($parentname, $deletelist = [])
{
	global $ifaqcache;

	if (!is_array($ifaqcache))
	{
		cache_ordered_faq();
	}

	$deletelist[] = $parentname;

	//if it's set it should be an array
	if (isset($ifaqcache[$parentname]) AND is_array($ifaqcache[$parentname]))
	{
		foreach($ifaqcache[$parentname] AS $faq)
		{
			$deletelist = array_merge($deletelist, fetch_faq_delete_list($faq['faqname']));
		}
	}

	return $deletelist;
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 112676 $
|| #######################################################################
\*=========================================================================*/
