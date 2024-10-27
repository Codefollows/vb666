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

// ###################### Start getHelpPhraseName #######################
// return the correct short name for a help topic
function fetch_help_phrase_short_name($item, $suffix = '')
{
	$parts = [$item['script']];
	if ($item['action'])
	{
		$parts[] = str_replace(',', '_', $item['action']);
	}

	if ($item['optionname'])
	{
		$parts[] = $item['optionname'];
	}

	return implode('_', $parts) . $suffix;
}

function get_help_export_xml($product)
{
	global $vbulletin;

	$assertor = vB::getDbAssertor();

	if ($product == 'vbulletin')
	{
		$products = ['vbulletin', ''];
	}
	else
	{
		$products = [$product];
	}

	// query topics
	$helptopics = [];
	$phrase_names = [];
	$topics = $assertor->assertQuery('vBAdmincp:getAdminHelpTopics', ['products' => $products]);
	foreach ($topics AS $topic)
	{
		$topic['phrase_name'] = fetch_help_phrase_short_name($topic);
		$phrase_names[] = $vbulletin->db->escape_string($topic['phrase_name'] . '_title');
		$phrase_names[] = $vbulletin->db->escape_string($topic['phrase_name'] . '_text');

		$helptopics["$topic[script]"][] = $topic;
	}
	unset($topic);

	$phrases = [];
	$phraseResults = $assertor->assertQuery('vBAdmincp:getAdminHelpTopicPhrases', ['phraseNames' => $phrase_names]);
	foreach ($phraseResults AS $phrase)
	{
		$phrases["$phrase[varname]"] = $phrase;
	}

	$xml = new vB_XML_Builder();

	$version = str_replace('"', '\"', $vbulletin->options['templateversion']);
	$xml->add_group('helptopics', [
		'vbversion' => $version,
		'product' => $product,
		'hasphrases' => 1,
	]);

	ksort($helptopics);
	foreach($helptopics AS $script => $scripttopics)
	{
		$xml->add_group('helpscript', ['name' => $script]);
		foreach($scripttopics AS $topic)
		{
			$attr = ['disp' => $topic['displayorder']];
			if ($topic['action'])
			{
				$attr['act'] = $topic['action'];
			}
			if ($topic['optionname'])
			{
				$attr['opt'] = $topic['optionname'];
			}

			$title =& $phrases[$topic['phrase_name'] . '_title'];
			$text =& $phrases[$topic['phrase_name'] . '_text'];

			if (!empty($title) OR !empty($text))
			{
				$xml->add_group('helptopic', $attr);

				$title_attributes = [
					'date' => $title['dateline'],
					'username' => $title['username'],
					'version' => htmlspecialchars_uni($title['version'])
				];
				$xml->add_tag('title', $title['text'], $title_attributes);

				$text_attributes = [
					'date' => $text['dateline'],
					'username' => $text['username'],
					'version' => htmlspecialchars_uni($text['version'])
				];
				$xml->add_tag('text', $text['text'], $text_attributes);

				$xml->close_group();
			}
			else
			{
				$xml->add_tag('helptopic', '', $attr);
			}
		}
		$xml->close_group();
	}

	$xml->close_group();

	$doc = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\r\n\r\n";

	$doc .= $xml->output();
	$xml = null;

	return $doc;
}

// ###################### Start xml_import_helptopics #######################
// import XML help topics - call this function like this:
function xml_import_help_topics($xml = false)
{
	global $vbulletin;

	$vbphrase = vB_Api::instanceInternal('phrase')->renderPhrasesNoShortcode([
		'please_wait' => 'please_wait',
		'importing_admin_help' => 'importing_admin_help',
	]);
	$vbphrase = $vbphrase['phrases'];
	print_dots_start('<b>' . $vbphrase['importing_admin_help'] . "</b>, $vbphrase[please_wait]", ':', 'dspan');

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

	if (!$arr['helpscript'])
	{
		print_dots_stop();
		print_stop_message2('invalid_file_specified');
	}

	$product = (empty($arr['product']) ? 'vbulletin' : $arr['product']);
	$has_phrases = (!empty($arr['hasphrases']));
	$arr = $arr['helpscript'];

	if ($product == 'vbulletin')
	{
		$product_sql = "product IN ('vbulletin', '')";
	}
	else
	{
		$product_sql = "product = '" . $vbulletin->db->escape_string($product) . "'";
	}

	$vbulletin->db->query_write("
		DELETE FROM " . TABLE_PREFIX . "adminhelp
		WHERE $product_sql
			 AND volatile = 1
	");
	if ($has_phrases)
	{
		$vbulletin->db->query_write("
			DELETE FROM " . TABLE_PREFIX . "phrase
			WHERE $product_sql
				AND fieldname = 'cphelptext'
				AND languageid = -1
		");
	}

	// Deal with single entry
	if (!is_array($arr[0]))
	{
		$arr = array($arr);
	}


	foreach($arr AS $helpscript)
	{
		$help_sql = array();
		$phrase_sql = array();
		$help_sql_len = 0;
		$phrase_sql_len = 0;

		// Deal with single entry
		if (!isset($helpscript['helptopic'][0]) OR !is_array($helpscript['helptopic'][0]))
		{
			$helpscript['helptopic'] = [$helpscript['helptopic']];
		}

		foreach ($helpscript['helptopic'] AS $topic)
		{
			$topic['opt'] = $topic['opt'] ?? '';
			$topic['act'] = $topic['act'] ?? '';

			$help_sql[] = "
				('" . $vbulletin->db->escape_string($helpscript['name']) . "',
				'" . $vbulletin->db->escape_string($topic['act']) . "',
				'" . $vbulletin->db->escape_string($topic['opt']) . "',
				" . intval($topic['disp']) . ",
				1,
				'" . $vbulletin->db->escape_string($product) . "')
			";
			$help_sql_len += strlen(end($help_sql));

			if ($has_phrases)
			{
				$phrase_name = fetch_help_phrase_short_name(array(
					'script' => $helpscript['name'],
					'action' => $topic['act'],
					'optionname' => $topic['opt']
				));

				if (isset($topic['text']['value']))
				{
					$phrase_sql[] = "
						(-1,
						'cphelptext',
						'{$phrase_name}_text',
						'" . $vbulletin->db->escape_string($topic['text']['value']) . "',
						'" . $vbulletin->db->escape_string($product) . "',
						'" . $vbulletin->db->escape_string($topic['text']['username']) . "',
						" . intval($topic['text']['date']) . ",
						'" . $vbulletin->db->escape_string($topic['text']['version']) . "')
					";

					$phrase_sql_len += strlen(end($phrase_sql));

				}

				if (isset($topic['title']['value']))
				{
					$phrase_sql[] = "
						(-1,
						'cphelptext',
						'{$phrase_name}_title',
						'" . $vbulletin->db->escape_string($topic['title']['value']) . "',
						'" . $vbulletin->db->escape_string($product) . "',
						'" . $vbulletin->db->escape_string($topic['title']['username']) . "',
						" . intval($topic['title']['date']) . ",
						'" . $vbulletin->db->escape_string($topic['title']['version']) . "')
					";
					$phrase_sql_len += strlen(end($phrase_sql));
				}
			}

			if ($phrase_sql_len > 102400)
			{
				// insert max of 100k of phrases at a time
				/*insert query*/
				$vbulletin->db->query_write("
					REPLACE INTO " . TABLE_PREFIX . "phrase
						(languageid, fieldname, varname, text, product, username, dateline, version)
					VALUES
						" . implode(",\n", $phrase_sql)
				);

				$phrase_sql = array();
				$phrase_sql_len = 0;
			}

			if ($help_sql_len > 102400)
			{
				// insert max of 100k of phrases at a time
				/*insert query*/
				$vbulletin->db->query_write("
					REPLACE INTO " . TABLE_PREFIX . "adminhelp
						(script, action, optionname, displayorder, volatile, product)
					VALUES
						" . implode(",\n\t", $help_sql)
				);

				$help_sql = array();
				$help_sql_len = 0;
			}
		}

		if ($help_sql)
		{
			/*insert query*/
			$vbulletin->db->query_write("
				REPLACE INTO " . TABLE_PREFIX . "adminhelp
					(script, action, optionname, displayorder, volatile, product)
				VALUES
					" . implode(",\n\t", $help_sql)
			);
		}

		if ($phrase_sql)
		{
			/*insert query*/
				$vbulletin->db->query_write("
					REPLACE INTO " . TABLE_PREFIX . "phrase
						(languageid, fieldname, varname, text, product, username, dateline, version)
					VALUES
						" . implode(",\n", $phrase_sql)
				);
		}
	}

	// stop the 'dots' counter feedback
	print_dots_stop();

	require_once(DIR . '/includes/adminfunctions_language.php');
	build_language();
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 115374 $
|| #######################################################################
\*=========================================================================*/
