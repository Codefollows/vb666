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
define('CVS_REVISION', '$RCSfile$ - $Revision: 115599 $');

// ################### DEFINE LOCAL SCRIPT CONSTANTS ######################

// #################### PRE-CACHE TEMPLATES AND DATA ######################
global $phrasegroups, $specialtemplates, $vbphrase;
$phrasegroups = ['tagscategories', 'maintenance'];
$specialtemplates = [];

// ########################## REQUIRE BACK-END ############################
require_once(dirname(__FILE__) . '/global.php');

ignore_user_abort(true);
vB_Utility_Functions::setPhpTimeout(0);

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!can_administer('canadmintags'))
{
	print_cp_no_permission();
}

// ############################# LOG ACTION ###############################
log_admin_action();

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

$action = ($_REQUEST['do'] ? $_REQUEST['do'] : 'modify');

//I'm not sure how much we need this, but the old branch logic checks some
//actions against REQUEST and some against POST. This should maintain
//equivilent behavior (error instead of explicit fallthrough)
$post_only_actions = [
	'taginsert',
	'tagclear',
	// allowing tagkill as GET for batch & redirect logic
	//'tagkill',
	'tagmerge',
	'tagdomerge',
];
if (in_array($action, $post_only_actions) AND empty($_POST['do']))
{
	exit;
}

$dispatch = array
(
	'taginsert' => 'taginsert',
	'tagclear' => 'tagclear',
	'tagmerge' => 'tagmerge',
	'tagdomerge' => 'tagdomerge',
	'tagdopromote' => 'tagdopromote',
	'tagkill' => 'tagkill',
	'tags' => 'displaytags', //legacy from when this was part of threads
	'modify' => 'displaytags',
);

global $stop_file, $stop_args;

$stop_file = '';
$stop_args = [];
if (array_key_exists($action, $dispatch))
{
	//this is a bit ugly, but do to some weirdness we set a path cookie on the client
	//cookie which overrides the more general cookie we set in this script.  There isn't
	//a good way for the user to *do* anything about it.  This will nuke that cookie without
	//affecting anything that we're currently using
	//We can probably remove this after a few releases.
	setcookie('vbulletin_inlinetag', '', TIMENOW - 3600);

	// these three actions need to set cookies, and will print the cp header themselves.
	if (!in_array($action, ['tagclear', 'tagdomerge', 'tagkill']))
	{
		tagcp_print_header();
	}
	tagcp_init_tag_action();
	call_user_func($dispatch[$action]);
	print_cp_footer();
}


// ########################################################################
// some utility function for the actions
function tagcp_init_tag_action()
{
	global $vbulletin, $stop_file, $stop_args;

	$vbulletin->input->clean_array_gpc('r', [
		'page' => vB_Cleaner::TYPE_UINT,
		'sort'       => vB_Cleaner::TYPE_NOHTML,
		'orphaned'   => vB_Cleaner::TYPE_BOOL,
	]);

	$stop_file = 'tag';
	$stop_args = [
		'do' => 'tags',
		'page' => $vbulletin->GPC['page'],
		'sort' => $vbulletin->GPC['sort'],
		'orphaned' => $vbulletin->GPC['orphaned'],
	];
}

function tagcp_add_hidden_fields($params)
{
	foreach($params AS $field => $value)
	{
		//we'll handle this magically so that we can pass the "stop_args" array
		//to the this function.  It's a bit of a cheat, but avoids some annoying
		//hoops for the caller to field
		if($field != 'do')
		{
			construct_hidden_code('page', $value);
		}
	}
}



function tagcp_fetch_tag_list()
{
	global $vbulletin;

	$vbulletin->input->clean_array_gpc('p', [
		'tag' => vB_Cleaner::TYPE_ARRAY_KEYS_INT
	]);

	$vbulletin->input->clean_array_gpc('c', [
		'vbulletin_inlinetag' => vB_Cleaner::TYPE_STR,
	]);

	$taglist = $vbulletin->GPC['tag'];

	if (!empty($vbulletin->GPC['vbulletin_inlinetag']))
	{
		$cookielist = explode('-', $vbulletin->GPC['vbulletin_inlinetag']);
		$cookielist = $vbulletin->cleaner->clean($cookielist, vB_Cleaner::TYPE_ARRAY_UINT);

		$taglist = array_unique(array_merge($taglist, $cookielist));
	}

	return $taglist;
}


// ########################################################################
// handled inserting a form
function taginsert()
{
	global $vbulletin, $stop_file, $stop_args;

	$vbulletin->input->clean_array_gpc('p', ['tagtext' => vB_Cleaner::TYPE_NOHTML]);

	$response = vB_Api::instance('Tags')->insertTags($vbulletin->GPC['tagtext']);
	print_stop_message_on_api_error($response);
	print_stop_message2('tag_saved', $stop_file, $stop_args);
}

// ########################################################################
// clear the tag selection cookie
function tagclear()
{
	tagcp_clear_taglist_cookie();

	tagcp_print_header();
	displaytags();
}

// ########################################################################

function tagmerge()
{
	global $vbulletin, $vbphrase, $stop_file, $stop_args;

	$strUtil = vB::getString();
	tagcp_init_tag_action();
	$taglist = tagcp_fetch_tag_list();
	if (!sizeof($taglist))
	{
		print_stop_message2('no_tags_selected', $stop_file, $stop_args);
	}

	$tags = vB::getDbAssertor()->getRows('vBForum:tag',
		['tagid' => $taglist],
		['field' => 'tagtext', 'direction' => vB_dB_Query::SORT_ASC]
	);

	if (!$tags)
	{
		print_stop_message2('no_tags_selected', $stop_file, $stop_args);
	}

	print_form_header('admincp/tag', 'tagdomerge');
	$columns = ['','',''];
	$counter = 0;
	foreach ($tags AS $tag)
	{
		$id = $tag['tagid'];
		$text =  $strUtil->htmlspecialchars($tag['tagtext']);
		$column = floor($counter++ / ceil(count($tags) / 3));
		$columns[$column] .= '<label for="taglist_' . $id . '">' .
			'<input type="checkbox" name="tag[' . $id . ']" id="taglist_' . $id . '" value="' . $id . '" tabindex="' . $column . '" checked="checked" /> ' . $text .
		'</label><br/>';
	}

	print_description_row($vbphrase['tag_merge_description'], false, 3, '', 'vbleft');
	print_cells_row($columns, false, false, -3);
	tagcp_add_hidden_fields($stop_args);

	print_input_row($vbphrase['new_tag'], 'tagtext', '', true, 35, 0, '', false, false, [1, 2]);
	print_submit_row($vbphrase['merge_tags'], false, 3, $vbphrase['go_back']);
}


// ########################################################################
function tagdomerge()
{
	global $vbulletin, $vbphrase, $stop_file, $stop_args;

	$tagApi = vB_Api::instance('tags');

	$taglist = tagcp_fetch_tag_list();
	if (!sizeof($taglist))
	{
		tagcp_print_header();
		print_stop_message2('no_tags_selected', $stop_file, $stop_args);
	}

	$vbulletin->input->clean_array_gpc('p', [
		'tagtext' => vB_Cleaner::TYPE_NOHTML
	]);

	$tagtext = $vbulletin->GPC['tagtext'];

	$name_changed = false;
	$tagExists = $tagApi->fetchTagByText($tagtext);
	if (empty($tagExists['tag']))
	{
		//Create tag
		$response = $tagApi->insertTags($tagtext);
		if (!empty($response['errors']))
		{
			tagcp_print_header();
			print_stop_message_array($response['errors']);
		}
	}
	else
	{
		//if the old tag and new differ only by case, then update
		if ($tagtext != $tagExists['tag']['tagtext'] AND vbstrtolower($tagtext) == vbstrtolower($tagExists['tag']['tagtext']))
		{
			$name_changed = true;
			$update = $tagApi->updateTags($tagtext);
		}
	}

	$tagExists = $tagApi->fetchTagByText($tagtext);
	if (empty($tagExists['tag']))
	{
		tagcp_print_header();
		print_stop_message2('no_changes_made', $stop_file, $stop_args);
	}
	else
	{
		$targetid = $tagExists['tag']['tagid'];
	}

	// check if source and targed are the same
	if (sizeof($taglist) == 1 AND in_array($targetid, $taglist))
	{
		if ($name_changed)
		{
			tagcp_print_header();
			print_stop_message2('tags_edited_successfully', $stop_file, $stop_args);
		}
		else
		{
			tagcp_print_header();
		 	print_stop_message2('no_changes_made', $stop_file, $stop_args);
		}
	}

	if (false !== ($selected = array_search($targetid, $taglist)))
	{
		// ensure targetid is not in taglist
		unset($taglist[$selected]);
	}

	$synonym = $tagApi->createSynonyms($taglist, $targetid);
	print_stop_message_on_api_error($synonym);

	// need to invalidate the search and tag cloud caches
	build_datastore('tagcloud', '', 1);
	build_datastore('searchcloud', '', 1);

	tagcp_clear_taglist_cookie();
	tagcp_print_header();
	print_stop_message2('tags_edited_successfully', $stop_file, $stop_args);
}

// ########################################################################
function tagdopromote()
{
	global $vbulletin, $vbphrase, $stop_file, $stop_args;

	$taglist = tagcp_fetch_tag_list();
	if (!sizeof($taglist))
	{
		print_stop_message2('no_tags_selected', $stop_file, $stop_args);
	}
	$promote = vB_Api::instance('Tags')->promoteTags($taglist);
	if (!empty($promote['errors']))
	{
		tagcp_print_header();
		print_stop_message_array($promote['errors']);
	}
	else
	{
		print_stop_message2('tags_edited_successfully', $stop_file, $stop_args);
	}
}

// ########################################################################

function tagkill()
{
	global $vbulletin, $vbphrase, $stop_file, $stop_args;

	$taglist = tagcp_fetch_tag_list();
	if (sizeof($taglist))
	{
		$kill = vB_Api::instance('Tags')->killTags($taglist);
		if (!empty($kill['errors']))
		{
			tagcp_print_header();
			print_stop_message_array($kill['errors'], get_admincp_url($stop_file, $stop_args));
		}
		else if (!empty($kill['remaining']))
		{
			/*
			killTags previously did not rebuild taglist for affected nodes,
			only unlinked the nodes in bulk.
			We now do a quick rebuild of taglist (just string processing
			for the node.taglist records rather than a "full" rebuild via
			tagnode & tag tables) and reindex the nodes as taglist is now
			included as keywords. This is done in batches as this may
			take some time (3.5s per 1000 nodes)
			 */
			tagcp_print_header();
			$message = construct_phrase(
				$vbphrase['processed_x_y_remaining'],
				$kill['processed'],
				$kill['remaining']
			);
			$args = ['do' => 'tagkill', 'tag' => $taglist,];
			$goto = get_redirect_url('admincp/tag.php', $args);
			print_cp_redirect($goto, 1, $message);
		}

		// need to invalidate the search and tag cloud caches
		build_datastore('tagcloud', '', 1);
		build_datastore('searchcloud', '', 1);
	}

	tagcp_clear_taglist_cookie();
	tagcp_print_header();
	print_stop_message2('tags_deleted_successfully', $stop_file, $stop_args);
}


// ########################################################################

function displaytags()
{
	global $vbphrase, $stop_args;
	$assertor = vB::getDbAssertor();
	$datastore = vB::getDatastore();

	$page = $stop_args['page'];
	$sort = $stop_args['sort'];
	$orphaned = $stop_args['orphaned'];

	if ($page < 1)
	{
		$page = 1;
	}

	$synonyms_in_list = ($sort == 'alphaall');

	$column_count = 3;
	$max_per_column = 15;
	$perpage = $column_count * $max_per_column;

	$query = [];
	$query['synonyms_in_list'] = $synonyms_in_list;
	$query['orphaned_only'] = $orphaned;

	$tag_counts = $assertor->getRow('vBAdmincp:getTagsForAdminCount', $query);
	$tag_count  = $tag_counts['count'];

	$start = ($page - 1) * $perpage;
	if ($start >= $tag_count)
	{
		$start = max(0, $tag_count - $perpage);
	}

	$query[vB_dB_Query::PARAM_LIMIT] = $perpage;
	$query[vB_dB_Query::PARAM_LIMITSTART] = $start;
	$query['sort'] = $sort;

	//the form name is used by the "inline mod" logic we use to handle/process tags in the js.
	print_form_header2('admincp/tag', 'modify', [], ['name' => 'tagsform']);
	print_table_start2();
	print_table_header($vbphrase['tag_list'], $column_count);

	$tags = $assertor->assertQuery('vBAdmincp:getTagsForAdmin', $query);
	if ($tags AND $tags->valid())
	{
		$columns = [];
		$counter = 0;

		// build page navigation
		$pagenav = tagcp_build_page_nav($stop_args, ceil($tag_count / $perpage));

		$args = $stop_args;
		//we want to reset pagingation when we change the sort or orphan status
		unset($args['page']);

		$orphan_status = [
			0 => 'all_tags',
			1 => 'unused_tags',
		];

		$orphan_links = tagcp_build_sortfilter_links($vbphrase, $orphan_status, $args, 'orphaned');

		$sorts = [
			'' => 'display_alphabetically',
			'dateline' => 'display_newest',
			'alphaall' => 'display_alphabetically_all',
		];
		$sort_links = tagcp_build_sortfilter_links($vbphrase, $sorts, $args, 'sort');

		$spacer = '&nbsp;&nbsp;';

		$sort_links = implode($spacer, $sort_links);
		$orphan_links = implode($spacer, $orphan_links);

		$mastercb = construct_checkbox_control('', '', false, 0, ['class' => 'js-checkbox-master']);
		print_description_row(
			'<div class="h-left">' . $mastercb . $sort_links .  str_repeat($spacer, 6) . $orphan_links . '</div>' . $pagenav,
			false, $column_count, 'thead', 'vbright'
		);

		// build columns
		foreach ($tags AS $tag)
		{
			$columnid = floor($counter++ / $max_per_column);
			$columns[$columnid][] = tagcp_format_tag_entry($tag, $synonyms_in_list);
		}

		// make column values printable
		$cells = [];
		for ($i = 0; $i < $column_count; $i++)
		{
			if (isset($columns[$i]))
			{
				$cells[] = implode("\n", $columns[$i]);
			}
			else
			{
				$cells[] = '&nbsp;';
			}
		}

		print_column_style_code([
			'width: 33%',
			'width: 33%',
			'width: 34%'
		]);
		print_cells_row2($cells, '', 'vbleft');
		tagcp_add_hidden_fields($stop_args);
		?>
		<tr>
			<td colspan="<?php echo $column_count; ?>" align="center" class="tfoot">
				<div class='js-tag-phrase-data hide' data-gox='<?php echo $vbphrase['go_x']; ?>'></div>
				<select id="select_tags" name="do">
					<option value="tagmerge" id="select_tags_merge"><?php echo $vbphrase['merge_selected_synonym']; ?></option>
					<option value="tagdopromote" id="select_tags_delete"><?php echo $vbphrase['promote_synonyms_selected']; ?></option>
					<option value="tagkill" id="select_tags_delete"><?php echo $vbphrase['delete_selected']; ?></option>
					<optgroup label="____________________">
						<option value="tagclear"><?php echo $vbphrase['deselect_all_tags']; ?></option>
					</optgroup>
				</select>
				<input type="submit" value="<?php echo $vbphrase['go']; ?>" id="tag_inlinego" class="button" />
			</td>
		</tr>
<?php
	}
	else
	{
		print_description_row($vbphrase['no_tags_defined'], false, 3, '', 'center');
	}

	print_table_footer();

	tagcp_add_hidden_fields($stop_args);

	print_form_header('admincp/tag', 'taginsert');
	print_input_row($vbphrase['add_tag'], 'tagtext');
	print_submit_row();
}

function format_tag_list_item($tagid, $text, $autocheck)
{
	$extra = ['id' => 'taglist_' . $tagid];
	if($autocheck)
	{
		$extra['class'] = 'js-checkbox-child';
	}

	return construct_checkbox_control($text, 'tag[' . $tagid . ']', false, 1, $extra);
}

function tagcp_build_sortfilter_links($phrases, $source, $args, $field)
{
	$current = $args[$field];
	$links = [];
	foreach($source AS $key => $phrase)
	{
		if ($key == $current)
		{
			$links[] = '<b>' . $phrases[$phrase] . '</b>';
		}
		else
		{
			$args[$field] = $key;
			$url = 'admincp/tag.php?' . http_build_query($args);
			$links[] = '<a href="' . htmlspecialchars($url) . '">' . $phrases[$phrase] . '</a>';
		}
	}

	return $links;
}

function tagcp_print_header()
{
	$datastore = vB::getDatastore();
	$jsversion =  $datastore->getOption('simpleversion');

	global $vbphrase;

	//it's possible that not fall of this will be relevant for every action, but let's just include it
	//until it causes problems.  It's simpler that way and the js files get cached anyway.
	$extraheader[] = '<script type="text/javascript" src="core/clientscript/vbulletin_inlinemod.js?v=' . $jsversion .'"></script>';
	$extraheader[] = '<script type="text/javascript" src="core/clientscript/vbulletin_tags.js?v=' . $jsversion .'"></script>';

	print_cp_header($vbphrase['tag_manager'], '', implode("\n", $extraheader));
}

function tagcp_clear_taglist_cookie()
{
	setcookie('vbulletin_inlinetag', '', TIMENOW - 3600, '/');
}

function tagcp_build_page_nav($page_args, $total_pages)
{
	global $vbphrase;

	$page = $page_args['page'];
	$args = $page_args;

	if ($total_pages > 1)
	{
		$pagenav = '<strong>' . $vbphrase['go_to_page'] . '</strong>';
		for ($thispage = 1; $thispage <= $total_pages; $thispage++)
		{
			if ($page == $thispage)
			{
				$pagenav .= " <strong>[$thispage]</strong> ";
			}
			else
			{
				$args['page'] = $thispage;
				$url = 'admincp/tag.php?' . http_build_query($args);

				$pagenav .= ' <a href="' . htmlspecialchars($url) . '" class="normal">' . $thispage . '</a> ';
			}
		}

	}
	else
	{
		$pagenav = '';
	}
	return $pagenav;
}

function tagcp_format_tag_entry($tag, $synonyms_in_list)
{
	$strUtil = vB::getString();
	if (!$synonyms_in_list)
	{
		$label = $strUtil->htmlspecialchars($tag['tagtext']);
		$synonyms = vB_Api::instance('Tags')->getTagSynonyms($tag['tagid']);
		$synonym_list = '';
		if (empty($synonyms['errors']) AND count($synonyms['tags']))
		{
			$synonym_list = '<span class="cbsubgroup-trigger h-align-middle">' .
				'<img class="js-synlist-collapseclose" src="' .  get_cpstyle_href('collapse_generic_collapsed.gif')  . '" />'.
				'<img class="js-synlist-collapseopen hide" src="' .  get_cpstyle_href('collapse_generic.gif')  . '" />'.
				'</span>';

			$synonym_list .= '<ul class="cbsubgroup hide">';
			foreach ($synonyms['tags'] AS $tagid => $tagtext)
			{
				$synonym_list .= '<li>' . format_tag_list_item($tagid,  $strUtil->htmlspecialchars($tagtext), false) . '</li>';
			}
			$synonym_list .= '</ul>';
		}
	}
	else
	{
		if($tag['canonicaltagid'])
		{
			$canonical = vB_Api::instance('Tags')->getTags($tag['canonicaltagid']);
			$canonical = $canonical['tags'][$tag['canonicaltagid']];

			$label = '<i>' .  $strUtil->htmlspecialchars($tag['tagtext']) . '</i> (' .  $strUtil->htmlspecialchars($canonical['tagtext']) . ')';
		}
		else
		{
			$label =  $strUtil->htmlspecialchars($tag['tagtext']);
		}


		$synonym_list = '';
	}

	$tag_item_text = format_tag_list_item($tag['tagid'], $label, true);

	return '<div id="tag' . $tag['tagid'] . '" class="js-synlist-container alt1 h-left h-clear-left">' . "\n" .
		$tag_item_text . "\n" . $synonym_list . "\n" .
	'</div>';
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 115599 $
|| #######################################################################
\*=========================================================================*/
