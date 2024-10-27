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
define('CVS_REVISION', '$RCSfile$ - $Revision: 110134 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
global $phrasegroups, $specialtemplates, $vbphrase, $vbulletin;
//not sure whey the "all_products" phrase is in the language phrase group but including
//that group is easier then trying to move the phrase.
$phrasegroups = ['logging', 'threadmanage', 'language'];
$specialtemplates = [];

// ########################## REQUIRE BACK-END ############################
require_once(dirname(__FILE__) . '/global.php');
require_once(DIR . '/includes/functions_log_error.php');

// ############################# LOG ACTION ###############################
if (!can_administer('canadminmodlog'))
{
	print_cp_no_permission();
}

log_admin_action();

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

$vb5_config =& vB::getConfig();
print_cp_header($vbphrase['moderator_log_gthreadmanage']);

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'choose';
}


//This will pull values out of the $rawparams array needed for the get_modlog_conditions function,
//while potentially converting some values (like "username") to the canonical values get_modlog_conditions
//expects.  We use a seperate function here because in addition to getting the conditions we need
//to propagate the parameters and it's cleanest just to get the canonical params once and pass those.
//
//$rawparams is usually the GPC array, which introduces the complexity that GPC values are *always*
//set.  Knowing which parameters were actually passed is important to deciding which parameter should
//be used when multiple parameters map to the same canonical value (in practice this generally won't happen)
//If the exists array is blank assume that all keys specified in $rawparams "exist" -- that is they
//are real value instead of defaults for params not passed.
function get_modlog_params($rawparams, $exists = [])
{
	//these are the cananonical params, copy them verbatim
	$names = [
		'enddate',
		'startdate',
		'userid',
		'modaction',
		'product',
	];

	//strip out only the values of interest form the raw params (which is going to be the GPC array in reality)
	$params = array_intersect_key($rawparams, array_flip($names));

	//these are some different ways of calculating the above.  We don't expect to see both the normal
	//param and the special value, preserve the canonical value if it's present.
	//
	//Note that if the exists array is populated and the exist flag is false that indicates that
	//the param value is defaulted and we should override it with the calculated value. If the exists
	//array is not populated we assume that the value is *not* a default.  This way if we don't have
	//defaulted values in $rawparams we don't have to construct the array of defaults/not defaults.
	//(In this case the expression collapses to !(isset($params['enddate']))
	if (isset($rawparams['daysprune']) AND !(isset($params['enddate']) AND $exists['enddate'] ?? true))
	{
		$params['enddate'] = vB::getRequest()->getTimeNow() - (86400 * $rawparams['daysprune']);
	}

	if (!empty($rawparams['username']) AND !(isset($params['userid']) AND $exists['userid'] ?? true))
	{
		$user = vB_Api::instance('user')->fetchByUsername($rawparams['username']);
		print_stop_message_on_api_error($user);

		if(!$user)
		{
			print_stop_message2('invalid_username_specified');
		}

		$params['userid'] = $user['userid'];
	}

	return $params;
}

//translate the "params" above to assertor conditions.
function get_modlog_conditions($params, $qualifyfields)
{
	$conditions = [];

	//some queries that use this do joins and need qualified fieldnames,
	//some don't and don't properly alias the table so this works. So we allow both.
	$tablename = '';
	if($qualifyfields)
	{
		$tablename = 'moderatorlog.';
	}

	if (!empty($params['enddate']))
	{
		$conditions[] = ['field' => $tablename . 'dateline', 'value' => $params['enddate'], 'operator' => vB_dB_Query::OPERATOR_LTE];
	}

	if (!empty($params['startdate']))
	{
		$conditions[] = ['field' => $tablename . 'dateline', 'value' => $params['startdate'], 'operator' => vB_dB_Query::OPERATOR_GTE];
	}

	if (!empty($params['userid']))
	{
		$conditions[$tablename . 'userid'] = $params['userid'];
	}

	if (!empty($params['modaction']))
	{
		$conditions[] = ['field' => $tablename . 'action', 'value' => $params['modaction'], 'operator' => vB_dB_Query::OPERATOR_INCLUDES];
	}

	if (!empty($params['product']))
	{
		$product = $params['product'];

		if ($product == 'vbulletin')
		{
			$product = ['', $product];
		}

		$conditions[$tablename . 'product'] = $product;
	}

	return $conditions;
}

// ###################### Start view #######################
if ($_REQUEST['do'] == 'view')
{
	$vbulletin->input->clean_array_gpc('r', [
		'perpage'    => vB_Cleaner::TYPE_UINT,
		'pagenumber' => vB_Cleaner::TYPE_UINT,
		'userid'     => vB_Cleaner::TYPE_UINT,
		'username'   => vB_Cleaner::TYPE_STR,
		'modaction'  => vB_Cleaner::TYPE_STR,
		'orderby'    => vB_Cleaner::TYPE_NOHTML,
		'sortdir'    => vB_Cleaner::TYPE_NOHTML,
		'product'    => vB_Cleaner::TYPE_STR,
		'startdate'  => vB_Cleaner::TYPE_UNIXTIME,
		'enddate'    => vB_Cleaner::TYPE_UNIXTIME,
	]);

	function get_modlog_sort($column, $dir)
	{
		$sorts = [
			'user' => 'ASC',
			'modaction' => 'ASC',
			'date' => 'DESC',
		];

		$column = (isset($sorts[$column]) ? $column : 'date');
		return [
			$column,
			$dir ?: $sorts[$column],
		];
	}

	$assertor = vB::getDbAssertor();
	$params = get_modlog_params($vbulletin->GPC, $vbulletin->GPC_exists);
	$conditions = get_modlog_conditions($params, true);

	$perpage = $vbulletin->GPC['perpage'];
	$perpage = ($perpage > 0 ? $perpage : 15);

	$pagenumber = max($vbulletin->GPC['pagenumber'], 1);

	$counter = $assertor->getRow('fetchModlogCount', ['conds' => $conditions]);
	$totalpages = ceil($counter['total'] / $perpage);

	[$currentOrderBy, $sortDir] = get_modlog_sort($vbulletin->GPC['orderby'], $vbulletin->GPC['sortdir']);

	$logs = $assertor->assertQuery('fetchModlogs', [
		'conds' => $conditions,
		'pagenumber' => $pagenumber,
		vB_dB_Query::PARAM_LIMIT => $perpage,
		vB_dB_Query::PARAM_LIMITSTART => ($pagenumber-1) * $perpage,
		'orderby' => $currentOrderBy,
		'sortdir' => $sortDir,
	]);
	if ($logs->valid())
	{
		$baseUrl = 'admincp/modlog.php?';
		$query = [
			'do' => 'view',
			'modaction' => $params['modaction'],
			'u' => $params['userid'],
			'pp' => $perpage,
			'orderby' => $currentOrderBy,
			'sortdir' => $sortDir,
			'page' => $pagenumber,
			'startdate' => $params['startdate'],
			'enddate' => $params['enddate'],
		];


		$headings = [];
		$headings[] = $vbphrase['id'];

		//by the time we get here the query values should have valid values for
		//order by and sortdir with defaults for bad input values resolved.
		function getHeading($query, $baseUrl, $currentOrderBy, $label)
		{
			$orderby = $query['orderby'];
			if($orderby == $currentOrderBy)
			{
				$sortDir = $query['sortdir'];
				$sortmarker = get_sortdir_icon($sortDir);
				// We're already sorted by this column, clicking on the column again should flip the sort.
				$query['sortdir'] = (strtolower($sortDir) == 'asc' ? 'desc': 'asc');
			}
			else
			{
				$sortmarker = '';
				//this will translate to the default for the column on click, no need to figure out
				//what the default is right now.
				$query['sortdir'] = '';
			}

			$url = htmlspecialchars_uni($baseUrl . http_build_query($query));
			$html = '<a href="' . $url . '" class="h-relative">' . $label . $sortmarker . '</a>';
			return $html;
		}

		$query['orderby'] = 'user';
		$headings[] = getHeading($query, $baseUrl, $currentOrderBy, $vbphrase['user']);

		$query['orderby'] = 'date';
		$headings[] = getHeading($query, $baseUrl, $currentOrderBy, $vbphrase['date']);

		$query['orderby'] = 'modaction';
		$headings[] = getHeading($query, $baseUrl, $currentOrderBy, $vbphrase['action']);

		$headings[] = str_replace(' ', '&nbsp;', $vbphrase['ip_address']);
		$query['orderby'] = $vbulletin->GPC['orderby'];

		$columncount = count($headings);
		$title = construct_phrase($vbphrase['moderator_log_viewer_page_x_y_there_are_z_total_log_entries'], vb_number_format($pagenumber),
			vb_number_format($totalpages), vb_number_format($counter['total']));
		print_form_header('admincp/modlog', 'remove');
		print_description_row(construct_link_code($vbphrase['restart'], 'modlog.php'), 0, $columncount, 'thead', 'vbright');
		print_table_header($title, $columncount);

		$columnalign = 'vbleft';
		print_cells_row2($headings, 'thead nowrap', $columnalign);

		foreach ($logs AS $log)
		{
			$cell = [];
			$cell[] = $log['moderatorlogid'];
			$cell[] = "<a href=\"admincp/user.php?do=edit&u=$log[userid]\"><b>$log[username]</b></a>";
			$cell[] = '<span class="smallfont">' . vbdate($vbulletin->options['logdateformat'], $log['dateline']) . '</span>';

			if ($log['type'])
			{
				$phrase = vB_Library_Admin::GetModlogAction($log['type']);

				if (!$log['nodeid'])
				{
					// Pre vB5 logs
					if ($unserialized = @unserialize($log['action']))
					{
						array_unshift($unserialized, $vbphrase[$phrase]);
						$action = call_user_func_array('construct_phrase', $unserialized);
					}
					else
					{
						$action = construct_phrase($vbphrase[$phrase], $log['action']);
					}

					if ($log['threadtitle'])
					{
						$action .= ', \'' . $log['threadtitle'] . '\'';
					}
				}
				else
				{
					// vB5 logs
					$temp = [];
					$logdata = @unserialize($log['action']);
					$modDisplayname = getAdminCPUsernameAndDisplayname($log['username'], $log['displayname'] ?? null);
					$action = construct_phrase($vbphrase[$phrase], $modDisplayname);

					if ($logdata['userid'] AND $logdata['username'])
					{
						$displayname = getAdminCPUsernameAndDisplayname($logdata['username'], $logdata['displayname'] ?? null);
						$name = '<a href="admincp/user.php?do=edit&u=' . $logdata['userid'] . '">' . $displayname . '</a>';
						$temp[] = $vbphrase['author'] . ' = ' . $name;
						unset($logdata['userid'], $logdata['username'], $logdata['displayname']);
					}

					$logdata['nodeid'] = $log['nodeid'];
					$title = $log['nodetitle'] ? $log['nodetitle'] : $vbphrase['untitled'];
					if ($log['routeid'])
					{
						$data = [
							'nodeid' => $log['nodeid'],
							'title' => $title,
							'innerPost' => $log['nodeid'],
						];
						$titleurl = vB5_Route::buildUrl($log['routeid'] . '|fullurl', $data, [], '#post' . $log['nodeid']);
						$logdata['title'] = '<a href="' . $titleurl . '">' . $title . '</a>';
					}
					else
					{
						$logdata['title'] = $title;
					}

					if (!empty($logdata))
					{
						foreach ($logdata AS $key => $data)
						{
							$temp[] = "$key = $data";
						}

						$action .= '<br />' . implode('; ', $temp);
					}
				}
			}
			else
			{
				$action = '-';
			}

			$cell[] = $action;

			$ipaddresscell = '&nbsp;';
			if($log['ipaddress'])
			{
				$ipaddresscell = '<a href="admincp/usertools.php?do=gethost&ip=' . $log['ipaddress'] . '">' . $log['ipaddress'] . '</a>';
			}

			$cell[] = '<span class="smallfont">' . $ipaddresscell . '</span>';

			print_cells_row2($cell, '', $columnalign);
		}

		$paging = get_log_paging_html($pagenumber, $totalpages, $baseUrl, $query, $vbphrase);
		print_table_footer($columncount, $paging);
	}
	else
	{
		print_stop_message2('no_results_matched_your_query');
	}
}

// ###################### Start prune log #######################
if ($_REQUEST['do'] == 'prunelog' AND can_access_logs($vb5_config['SpecialUsers']['canpruneadminlog'], 0, '<p>' . $vbphrase['control_panel_log_pruning_permission_restricted'] . '</p>'))
{
	$vbulletin->input->clean_array_gpc('r', [
		'daysprune' => vB_Cleaner::TYPE_UINT,
		'userid'    => vB_Cleaner::TYPE_UINT,
		'username' => vB_Cleaner::TYPE_STR,
		'modaction' => vB_Cleaner::TYPE_STR,
		'product'   => vB_Cleaner::TYPE_STR,
	]);

	$params = get_modlog_params($vbulletin->GPC, $vbulletin->GPC_exists);
	$conditions = get_modlog_conditions($params, true);
	$logs = vB::getDbAssertor()->getRow('fetchModlogCount', ['conds' => $conditions]);
	if ($logs['total'])
	{
		print_form_header('admincp/modlog', 'doprunelog');
		construct_hidden_code('enddate', $params['enddate']);
		construct_hidden_code('modaction', $params['modaction']);
		construct_hidden_code('userid', $params['userid']);
		construct_hidden_code('product', $params['product']);
		print_table_header($vbphrase['prune_moderator_log']);
		print_description_row(construct_phrase($vbphrase['are_you_sure_you_want_to_prune_x_log_entries_from_moderator_log'], vb_number_format($logs['total'])));
		print_submit_row($vbphrase['yes'], 0, 0, $vbphrase['no']);
	}
	else
	{
		print_stop_message2('no_logs_matched_your_query');
	}

}

// ###################### Start do prune log #######################
if ($_POST['do'] == 'doprunelog' AND can_access_logs($vb5_config['SpecialUsers']['canpruneadminlog'], 0, '<p>' . $vbphrase['control_panel_log_pruning_permission_restricted'] . '</p>'))
{
	$vbulletin->input->clean_array_gpc('p', [
		'enddate'   => vB_Cleaner::TYPE_UINT,
		'modaction' => vB_Cleaner::TYPE_STR,
		'userid'    => vB_Cleaner::TYPE_UINT,
		'product'   => vB_Cleaner::TYPE_STR,
	]);

	$params = get_modlog_params($vbulletin->GPC, $vbulletin->GPC_exists);
	$conditions = get_modlog_conditions($params, false);
	vB::getDbAssertor()->delete('moderatorlog', $conditions);

	print_stop_message2('pruned_moderator_log_successfully', 'modlog', ['do'=>'choose']);
}

// ###################### Start modify #######################
if ($_REQUEST['do'] == 'choose')
{
	print_form_header('admincp/modlog', 'view');
	print_table_header($vbphrase['moderator_log_viewer']);
	print_input_row($vbphrase['log_entries_to_show_per_page'], 'perpage', 15);
	print_input_row($vbphrase['show_only_entries_generated_by'], 'username');
	print_time_row($vbphrase['start_date'], 'startdate', 0, 0);
	print_time_row($vbphrase['end_date'], 'enddate', 0, 0);
	if (count($products = fetch_product_list()) > 1)
	{
		print_select_row($vbphrase['product'], 'product', ['' => $vbphrase['all_products']] + $products);
	}

	$orderby = [
		'date' => $vbphrase['date'],
		'user' => $vbphrase['username'],
		'modaction' => $vbphrase['action'],
	];

	print_select_row($vbphrase['order_by_gcpglobal'], 'orderby', $orderby, 'date');
	print_submit_row($vbphrase['view'], 0);

	if (can_access_logs($vb5_config['SpecialUsers']['canpruneadminlog'], 0, ''))
	{
		print_form_header('admincp/modlog', 'prunelog');
		print_table_header($vbphrase['prune_moderator_log']);
		print_input_row($vbphrase['show_only_entries_generated_by'], 'username');
		if (count($products) > 1)
		{
			print_select_row($vbphrase['product'], 'product', ['' => $vbphrase['all_products']] + $products);
		}
		print_input_row($vbphrase['remove_entries_older_than_days'], 'daysprune', 30);
		print_submit_row($vbphrase['prune_log_entries'], 0);
	}

}

print_cp_footer();

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 110134 $
|| #######################################################################
\*=========================================================================*/
