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
define('CVS_REVISION', '$RCSfile$ - $Revision: 115429 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
global $phrasegroups, $specialtemplates, $vbphrase, $vbulletin;
$phrasegroups = ['cphome', 'diagnostic'];
$specialtemplates = [];

// ########################## REQUIRE BACK-END ############################
require_once(dirname(__FILE__) . '/global.php');

// ######################## CHECK ADMIN PERMISSIONS #######################
$canUseAll = (bool)  vB::getUserContext()->hasAdminPermission('canuseallmaintenance');

if (!$canUseAll AND ! vB::getUserContext()->hasAdminPermission('canadminmaintain') AND
	!(($_REQUEST['do'] == 'payments') AND vB::getUserContext()->hasAdminPermission('canadminusers')))
{
	print_cp_no_permission();
}


// ############################# LOG ACTION ###############################
log_admin_action();

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

$vb5_config =& vB::getConfig();
$vboptions = vB::getDatastore()->getValue('options');

// ###################### Start maketestresult #######################
function print_diagnostic_test_result($status, $reasons = array(), $exit = 1)
{
	// $status values = -1: indeterminate; 0: failed; 1: passed
	// $reasons a list of reasons why the test passed/failed
	// $exit values = 0: continue execution; 1: stop here
	global $vbphrase;

	print_form_header('admincp/', '');

	print_table_header($vbphrase['results']);

	if (is_array($reasons))
	{
		foreach ($reasons AS $reason)
		{
			print_description_row($reason);
		}
	}
	else if (!empty($reasons))
	{
		print_description_row($reasons);
	}

	print_table_footer();

	if ($exit == 1)
	{
		print_cp_footer();
	}
}

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'list';
}

$extraheader = [
	'<script type="text/javascript" src="core/clientscript/vbulletin_diagnostic.js?v=' . $vboptions['simpleversion'] . '"></script>',
];

print_cp_header($vbphrase['diagnostics'], '', $extraheader);
print_cp_description($vbphrase, 'diagnostic', $_REQUEST['do']);


// ###################### Start upload test #######################
if ($_POST['do'] == 'doupload')
{
	// additional checks should be added with testing on other OS's (Windows doesn't handle safe_mode the same as Linux).
	if (!$canUseAll)
	{
		print_cp_no_permission();
	}
	$vbulletin->input->clean_array_gpc('f', array(
		'attachfile' => vB_Cleaner::TYPE_FILE
	));
	scanVbulletinGPCFile('attachfile');

	print_form_header('admincp/', '');
	print_table_header($vbphrase['pertinent_php_settings']);

	$file_uploads = ini_get('file_uploads');
	print_label_row('file_uploads:', $file_uploads == 1 ? $vbphrase['on'] : $vbphrase['off']);

	print_label_row('open_basedir:', iif($open_basedir = ini_get('open_basedir'), $open_basedir, '<i>' . $vbphrase['none'] . '</i>'));
	print_label_row('upload_tmp_dir:', iif($upload_tmp_dir = ini_get('upload_tmp_dir'), $upload_tmp_dir, '<i>' . $vbphrase['none'] . '</i>'));
	require_once(DIR . '/includes/functions_file.php');
	print_label_row('upload_max_filesize:', vb_number_format(fetch_max_upload_size(), 1, true));
	print_table_footer();

	if (sizeof($_FILES) == 0)
	{
		if ($file_uploads === 0)
		{ // don't match NULL
			print_diagnostic_test_result(0, $vbphrase['file_upload_setting_off']);
		}
		else
		{
			print_diagnostic_test_result(0, $vbphrase['unknown_error']);
		}
	}

	if (empty($vbulletin->GPC['attachfile']['tmp_name']))
	{
		$errorMsg = construct_phrase(
			$vbphrase['no_file_uploaded_and_no_local_file_found_gcpglobal'],
			$vbphrase['test_cannot_continue']
		);
		if (isset($vbulletin->GPC['attachfile']['error']))
		{
			$errorMsg .= '<br />' . construct_phrase(
				$vbphrase['upload_file_failed_php_error_x_phplink'],
				intval($vbulletin->GPC['attachfile']['error'])
			);
		}
		print_diagnostic_test_result(0, $errorMsg);
	}

	// do not use file_exists here, under IIS it will return false in some cases
	if (!is_uploaded_file($vbulletin->GPC['attachfile']['tmp_name']))
	{
		print_diagnostic_test_result(0, construct_phrase($vbphrase['unable_to_find_attached_file'], $vbulletin->GPC['attachfile']['tmp_name'], $vbphrase['test_cannot_continue']));
	}

	$fp = @fopen($vbulletin->GPC['attachfile']['tmp_name'], 'rb');
	if (!empty($fp))
	{
		@fclose($fp);
		if ($vbulletin->options['safeupload'])
		{
			$safeaddntl = $vbphrase['turn_safe_mode_option_off'];
		}
		else
		{
			$safeaddntl = '';
		}
		print_diagnostic_test_result(1, $vbphrase['no_errors_occurred_opening_upload']. ' ' . $safeaddntl);
	} // we had problems opening the file as is, but we need to run the other tests before dying

	if ($vbulletin->options['safeupload'])
	{
		if ($vbulletin->options['tmppath'] == '')
		{
			print_diagnostic_test_result(0, $vbphrase['safe_mode_enabled_no_tmp_dir']);
		}
		else if (!is_dir($vbulletin->options['tmppath']))
		{
			print_diagnostic_test_result(0, construct_phrase($vbphrase['safe_mode_dir_not_dir'], $vbulletin->options['tmppath']));
		}
		else if (!is_writable($vbulletin->options['tmppath']))
		{
			print_diagnostic_test_result(0, construct_phrase($vbphrase['safe_mode_not_writeable'], $vbulletin->options['tmppath']));
		}
		$copyto = $vbulletin->options['tmppath'] . '/' . $vbulletin->session->fetch_sessionhash();
		if ($result = @move_uploaded_file($vbulletin->GPC['attachfile']['tmp_name'], $copyto))
		{
			$fp = @fopen($copyto , 'rb');
			if (!empty($fp))
			{
				@fclose($fp);
				print_diagnostic_test_result(1, $vbphrase['file_copied_to_tmp_dir_now_readable']);
			}
			else
			{
				print_diagnostic_test_result(0, $vbphrase['file_copied_to_tmp_dir_now_unreadable']);
			}
			@unlink($copyto);
		}
		else
		{
			print_diagnostic_test_result(0, construct_phrase($vbphrase['unable_to_copy_attached_file'], $copyto));
		}
	}

	if ($open_basedir)
	{
		print_diagnostic_test_result(0, construct_phrase($vbphrase['open_basedir_in_effect'], $open_basedir));
	}

	print_diagnostic_test_result(-1, $vbphrase['test_indeterminate_contact_host']);
}

// ###################### Start mail test #######################
if ($_POST['do'] == 'domail')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'emailaddress' => vB_Cleaner::TYPE_STR,
	));

	print_form_header('admincp/', '');
	if ($vbulletin->options['use_smtp'])
	{
		print_table_header($vbphrase['pertinent_smtp_settings']);
		$smtp_tls = '';
		switch ($vbulletin->options['smtp_tls'])
		{
			case 'ssl':
				$smtp_tls = 'ssl://';
				break;
			case 'tls':
				$smtp_tls = 'tls://';
				break;
			default:
				$smtp_tls = '';
		}

		print_label_row('SMTP:', $smtp_tls . $vbulletin->options['smtp_host'] . ':' . (!empty($vbulletin->options['smtp_port']) ? intval($vbulletin->options['smtp_port']) : 25));
		print_label_row($vbphrase['smtp_username'], $vbulletin->options['smtp_user']);
	}
	else
	{
		print_table_header($vbphrase['pertinent_php_settings']);
		print_label_row('SMTP:', iif($SMTP = @ini_get('SMTP'), $SMTP, '<i>' . $vbphrase['none'] . '</i>'));
		print_label_row('sendmail_from:', iif($sendmail_from = @ini_get('sendmail_from'), $sendmail_from, '<i>' . $vbphrase['none'] . '</i>'));
		print_label_row('sendmail_path:', iif($sendmail_path = @ini_get('sendmail_path'), $sendmail_path, '<i>' . $vbphrase['none'] . '</i>'));
	}
	print_table_footer();

	$emailaddress = $vbulletin->GPC['emailaddress'];

	if (empty($emailaddress))
	{
		print_diagnostic_test_result(0, fetch_error('please_complete_required_fields'));
	}

	if (!is_valid_email($emailaddress))
	{
		print_diagnostic_test_result(0, $vbphrase['invalid_email_specified']);
	}


	$phraseApi = vB_Api::instanceInternal('phrase');
	$string = vB::getString();
	$bbtitle_escaped = $string->htmlspecialchars($vboptions['bbtitle']);

	$subject = ($vboptions['needfromemail'] ? $vbphrase['vbulletin_email_test_withf'] : $vbphrase['vbulletin_email_test']);
	['phrases' => $phrases] = $phraseApi->renderPhrases(['msg' => ['vbulletin_email_test_msg', $bbtitle_escaped]]);
	$message = $phrases['msg'];

	$mail = vB_Mail::fetchLibrary();
	$mail->setDebug(true);
	$mail->prepareMail($emailaddress, $subject, $message, $vboptions['webmasteremail']);

	// error handling
	$olderrordisplay = ini_set('display_errors', true);
	try
	{
		ob_start();
		$mailreturn = $mail->send(true);
		$errors = ob_get_contents();
	}
	finally
	{
		ini_set('display_errors', $olderrordisplay);
		ob_end_clean();
	}
	// end error handling

	$results = [];
	$status = 0;
	if (!$mailreturn OR $errors)
	{
		$status = 0;
		if (!$mailreturn)
		{
			$results[] = $vbphrase['mail_function_returned_error'];
		}
		if ($errors)
		{
			$results[] = $vbphrase['mail_function_errors_returned_were'].'<br /><br />' . $errors;
		}
		if (!$vbulletin->options['use_smtp'])
		{
			$results[] = $vbphrase['check_mail_server_configured_correctly'];
		}
	}
	else
	{
		$status = 1;
		$results[] = construct_phrase($vbphrase['email_sent_check_shortly'], $emailaddress);
	}

	if (empty($vboptions['webmasteremail']))
	{
		$results[] = $vbphrase['warn_webmasteremail_empty'];
	}
	print_diagnostic_test_result($status, $results);
}

// ###################### Start geoip test #######################
if ($_POST['do'] == 'dogeoip')
{
	// additional checks should be added with testing on other OS's (Windows doesn't handle safe_mode the same as Linux).
	if (!$canUseAll)
	{
		print_cp_no_permission();
	}

	$vbulletin->input->clean_array_gpc('p', array(
		'ipaddress' => vB_Cleaner::TYPE_STR,
	));
	$ipaddress = $vbulletin->GPC['ipaddress'];

	$options = vB::getDatastore()->getValue('options');
	print_form_header('admincp/', '');
	print_table_header($vbphrase['pertinent_php_settings']);
	print_label_row($vbphrase['geoip_provider'] . ':', $options['geoip_provider']);
	print_label_row($vbphrase['ip_address'] . ':', $ipaddress);
	print_table_footer();


	if(!$options['geoip_provider'] OR $options['geoip_provider'] == 'none')
	{
		print_diagnostic_test_result(0, $vbphrase['geoip_provider_not_configured']);
	}

	if (empty($ipaddress))
	{
		print_diagnostic_test_result(0, fetch_error('please_complete_required_fields'));
	}

	$data = array();
	$data['urlLoader'] = vB::getUrlLoader();
	$data['key'] = $options['geoip_service_key'];

	try
	{
		$class = vB::getVbClassName($options['geoip_provider'], 'Utility_Geoip', 'vB_Utility_Geoip');
		$geoip = new $class($data);
		$response = $geoip->getIpData($ipaddress);

		print_diagnostic_test_result(1, construct_phrase($vbphrase['geoip_response_x'], $response));
	}
	catch(Throwable $e)
	{
		print_diagnostic_test_result(0, (string) $e);
	}
}

// ###################### Start imagick test #######################
if ($_REQUEST['do'] == 'doimagick')
{
	// additional checks should be added with testing on other OS's (Windows doesn't handle safe_mode the same as Linux).
	if (!$canUseAll)
	{
		print_cp_no_permission();
	}

	$phraseApi = vB_Api::instanceInternal('phrase');
	$info = vB_Image_Imagick::diagnostics();

	print_form_header('admincp/', '');

	print_table_header($vbphrase['imagick_test_version_header']);
	// The version fetch may also have an error that we want to report in this section instead of errors section.
	if (is_array($info['version']))
	{
		$error = $phraseApi->renderPhrases([$info['version']]);
		$error = reset($error['phrases']);
		print_cells_row([$error]);
	}
	else
	{
		print_cells_row([$info['version']]);
	}
	print_table_footer(2, '', '', false);

	if (!empty($info['errors']))
	{
		print_table_start();
		print_table_header($vbphrase['errors']);
		$errorPhrases = $phraseApi->renderPhrases($info['errors']);
		foreach($errorPhrases['phrases'] AS $__err)
		{
			print_description_row('<pre>' . $__err . '</pre>');
		}
		print_table_footer(2, '', '', false);
	}

	if (!empty($info['pdf_thumbnail_sample']))
	{
		print_table_start();
		print_table_header($vbphrase['imagick_test_pdf_thumbnail_header']);
		print_description_row($vbphrase['imagick_test_pdf_thumbnail_desc']);
		$src = 'data:image/png;base64,' . base64_encode($info['pdf_thumbnail_sample']);
		$html = "<img src='$src' />";
		print_description_row($html);
		print_table_footer(2, '', '', false);
	}

	if (!empty($info['png']))
	{
		print_table_start();
		print_table_header($vbphrase['imagick_test_png_header']);
		print_description_row($vbphrase['imagick_test_png_desc']);
		$phraseArgs = [];
		if (!empty($info['png']['original']))
		{
			$src = 'data:image/png;base64,' . base64_encode($info['png']['original']['blob']);
			$phraseArgs[] = "<img src='$src' />";
		}
		else
		{
			$phraseArgs[] = '';
		}

		for ($i = 0; $i < 4; $i++)
		{
			if (!empty($info['png']['rotate'][$i]['blob']))
			{
				$src = 'data:image/png;base64,' . base64_encode($info['png']['rotate'][$i]['blob']);
				$phraseArgs[] = "<img src='$src' />";
			}
			else
			{
				$phraseArgs[] = '';
			}
		}

		if (!empty($info['png']['resize']['down']['blob']))
		{
			$src = 'data:image/png;base64,' . base64_encode($info['png']['resize']['down']['blob']);
			$phraseArgs[] = "<img src='$src' />";
		}
		else
		{
			$phraseArgs[] = '';
		}

		if (!empty($info['png']['resize']['up']['blob']))
		{
			$src = 'data:image/png;base64,' . base64_encode($info['png']['resize']['up']['blob']);
			$phraseArgs[] = "<img src='$src' />";
		}
		else
		{
			$phraseArgs[] = '';
		}



		if (!empty($info['png']['convert']['jpg']['blob']))
		{
			$src = 'data:image/jpeg;base64,' . base64_encode($info['png']['convert']['jpg']['blob']);
			$phraseArgs[] = "<img src='$src' />";
		}
		else
		{
			$phraseArgs[] = '';
		}

		$html = construct_phrase($vbphrase['imagick_test_png_output'], $phraseArgs);
		print_description_row($html);

		print_table_footer(2, '', '', false);
	}

	if (!empty($info['webp']))
	{
		print_table_start2();
		print_table_header($vbphrase['imagick_test_webp_header']);
		print_description_row($vbphrase['imagick_test_webp_desc']);

		if (!empty($info['webp']['convert']['frompng']))
		{
			// Avoiding sticking massive chunks of HTML into phrase per translation team's feedback from similar work (i.e. above, datestring formats).
			$src = 'data:image/jpeg;base64,' . base64_encode($info['webp']['convert']['frompng']['blob']);
			$img = "<img src='$src' />";
			$html = '<div class="diagnostics--imagick-test-png-out">
				<section>
					<figure>
						<figcaption>
							' . $vbphrase['imagick_test_webp_convertpng'] . '
						</figcaption>
					' . $img. '
					</figure>
				</section>';
			print_description_row($html);
		}
		else
		{
			$phraseArgs[] = '';
		}

		print_table_footer(2, '', '', false);
	}

	print_form_footer();
}


// ###################### Start date format diag #######################
if ($_REQUEST['do'] == 'dateformat')
{
	// This diag section is mainly for helping with development & debugging

	if (!$canUseAll)
	{
		print_cp_no_permission();
	}

	$vbulletin->input->clean_array_gpc('p', [
		'time' => vB_Cleaner::TYPE_STR,
		'format' => vB_Cleaner::TYPE_STR,
		'locale' => vB_Cleaner::TYPE_STR,
	]);
	$time = $vbulletin->GPC['time'];
	if (empty($time))
	{
		$time = time();
	}
	if (!is_numeric($time))
	{
		$timestamp = strtotime($time);
	}
	else
	{
		$timestamp = intval($time);
	}
	$format = $vbulletin->GPC['format'];
	$locale = $vbulletin->GPC['locale'];
	$dateUtil = new vB_Utility_Date();
	$vboptions = vB::getDatastore()->getValue('options');
	$options = [
		// first two override names aren't just "lang_<option>override"
		// so we have to have a map...
		'dateformat' => 'lang_dateoverride',
		'timeformat' => 'lang_timeoverride',
		'registereddateformat' => 'lang_registereddateoverride',
		'calformat1' => 'lang_calformat1override',
		'calformat2' => 'lang_calformat2override',
		'eventdateformat' => 'lang_eventdateformatoverride',
		// pickerdateformat is for datepicker only
		'logdateformat' => 'lang_logdateoverride',
		// I don't think lang_decimalsep & lang_thousandsep are
		// used in date/time formatting, only number formatting
	];
	$varnames = [];
	foreach ($options AS $__opt => $__override)
	{
		$varnames[] = 'setting_' . $__opt . '_title';
		$varnames[] = 'setting_' . $__opt . '_desc';
	}
	$varnames = array_combine($varnames, $varnames);
	['phrases' => $optionPhrases] = vB_Api::instance('phrase')->renderPhrases($varnames);
	$languages = vB::getDatastore()->getValue('languagecache');
	/*
	$langSelectOptions = [];
	foreach($languages AS $__langid => $__lang)
	{
		$langSelectOptions[$__langid] = $__lang['title'];
	}
	*/

	/* Begin Output */

	// Custom CSS only used for this diag. TODO: push into adminCP css somewhere?
	echo <<<EOHTML
<style>
.h-warning {
	color: yellow;
	background-color: red;
	font-weight: bold;
}
</style>
EOHTML;
	print_form_header('admincp/diagnostic', 'dateformat');
	print_table_header($vbphrase['dateformat_diagnostics_header']);
	print_input_row($vbphrase['dateformat_timestring'], 'time', $time);

	$params = [
		$vbphrase['dateformat_locale'],
		'locale',
		$locale,
	];
	print_input_row(...$params);
	$originalLocale = setlocale(LC_TIME, 0);
	if (!empty($locale))
	{
		setlocale(LC_TIME, $locale);
	}

	print_textarea_row($vbphrase['dateformat_testformat'], 'format', $format);

	if (!empty($format))
	{
		$dateUtil = new vB_Utility_Date();
		$output = $dateUtil->checkDisallowed($format, $timestamp);
		if ($output['source'] !== $format)
		{
			print_description_row($vbphrase['dateformat_disallowed_warning']);
		}
		print_label_row($output['source_label'], $output['source_output']);
		print_label_row($output['converted_label'], $output['converted_output']);
		print_label_row($vbphrase['dateformat_vbdate_output'], vbdate($format, $timestamp));
		print_description_row($vbphrase['dateformat_vbdateout_desc']);
	}

	print_submit_row($vbphrase['submit']);



	/*	LANG OVERRIDE INFO	 */
	$headers = [
		$vbphrase['dateformat_setting'],
		$vbphrase['dateformat_global'],
		$vbphrase['dateformat_global_output'],
		$vbphrase['dateformat_vbdate_output'],
		$vbphrase['dateformat_langoverride'],
		$vbphrase['dateformat_langoverride_output'],
		$vbphrase['dateformat_vbdate_output'],
	];
	$colspan = count($headers);
	print_form_header('admincp/', '', false, false);
	$first = true;
	foreach ($languages AS $__languageid => $__lang)
	{
		$__langinfo = vB_Language::getPhraseInfo($__languageid);
		foreach ($__langinfo AS $__key => $__val)
		{
			if (strpos($__key, 'phrasegroup_') === 0)
			{
				unset($__langinfo[$__key]);
			}
		}

		print_table_start();
		print_table_header($__lang['title'], $colspan);
		print_description_row(
			construct_phrase($vbphrase['dateformat_locale_x_desc'], $__langinfo['lang_locale']),
			false,
			$colspan
		);
		print_cells_row($headers, true);
		foreach ($options AS $__opt => $__override)
		{
			$__title = $optionPhrases["setting_{$__opt}_title"];
			$__format = $vboptions[$__opt];
			$__langFormat = $__langinfo[$__override];
			print_cells_row([
					$__title,
					$__format,
					$dateUtil->autoFunc($__format, $timestamp),
					vbdate($__format, $timestamp),
					$__langFormat ? $__langFormat : '-',
					$__langFormat ? $dateUtil->autoFunc($__langFormat, $timestamp) : '-',
					$__langFormat ? vbdate($__langFormat, $timestamp) : '-',
			]);
		}
		if ($first)
		{
			print_description_row($vbphrase['dateformat_output_desc'], false, $colspan);
			print_description_row($vbphrase['dateformat_vbdateout_desc'], false, $colspan);
		}
		$first = false;
		print_table_footer($colspan, '', '', false);
	}
	echo "</form>";


	/* CURRENT OPTIONS CONVERSIONS */
	$headers = [
		$vbphrase['dateformat_setting'],
		$vbphrase['dateformat_global'],
		$vbphrase['dateformat_global_output'],
		$vbphrase['dateformat_autoconv'],
		$vbphrase['dateformat_autoconv_output'],
	];
	$colspan = count($headers);
	print_form_header('admincp/', false, false);
	print_table_header($vbphrase['dateformat_conversions'], $colspan);
	print_description_row($vbphrase['dateformat_autoconv_desc'], false, $colspan);
	print_cells_row($headers, true);
	foreach ($options AS $__opt => $__override)
	{
		$__title = $optionPhrases["setting_{$__opt}_title"];
		$__format = $vboptions[$__opt];
		$__output = $dateUtil->checkDisallowed($__format);
		print_cells_row([
				$__title,
				$__output['source'],
				$__output['source_output'],
				$__output['converted'],
				$__output['converted_output'],
		]);
	}
	print_description_row($vbphrase['dateformat_disallowed_warning'], false, $colspan);
	print_table_footer($colspan);

	/* revert any locale / timezone changes */

	setlocale(LC_TIME, $originalLocale);
}

if (
	$_REQUEST['do'] == 'dateformat' OR
	$_REQUEST['do'] == 'displaydateformat'

)
{
	if (empty($dateUtil))
	{
		$dateUtil = new vB_Utility_Date();
	}

	/* DOCUMENTATION */
	/*
	$headers = [
		'Format',
		'Description',
		'Example',
	];
	$colspan = count($headers);
	print_form_header('admincp/', false, false);
	print_table_header('vBulletin Date & Time Format Documentation', $colspan);
	print_cells_row($headers, true);
	$rows = $dateUtil->spitDoc();
	foreach ($rows AS $__row)
	{
		if ($__row[1] == '---' AND $__row[2] = '---')
		{
			$__row[0] = "<b>$__row[0]</b>";
		}
		print_cells_row($__row);
	}
	*/
	// Above looks better, but for localization that would require a single phrase for each
	// table-data cell, which is a bit ridiculous...

	echo $vbphrase['datetime_format_description'];

	//print_table_footer($colspan);
}

// ###################### Start system information #######################
if ($_POST['do'] == 'dosysinfo')
{
	if (!$canUseAll)
	{
		print_cp_no_permission();
	}
	$vbulletin->input->clean_array_gpc('p', [
		'type' => vB_Cleaner::TYPE_STR
	]);


	$type = $vbulletin->GPC['type'];

	if($type == 'mysql_vars')
	{
		$querytext = 'SHOW VARIABLES';
		$phrase = 'mysql_variables';
	}
	else if($type == 'mysql_status')
	{
		$querytext = 'SHOW /*!50002 GLOBAL */ STATUS';
		$phrase = 'mysql_status';
	}
	else
	{
		$querytext = 'SHOW TABLE STATUS';
		$phrase = 'table_status';
	}

	//need to convert this to the assertor but need to sort out how to handle the num_fields call.
	$result = $vbulletin->db->query_read($querytext);
	$colcount = $vbulletin->db->num_fields($result);

	$collist = [];
	for ($i = 0; $i < $colcount; $i++)
	{
		$collist[] = $vbulletin->db->field_name($result, $i);
	}

	print_form_header('admincp/', '');
	print_table_header($vbphrase[$phrase], $colcount);
	print_cells_row($collist, 1);
	while ($row = $vbulletin->db->fetch_array($result))
	{
		print_cells_row($row);
	}

	print_table_footer();
}

if ($_REQUEST['do'] == 'predoversion')
{
	if (!$canUseAll)
	{
		print_cp_no_permission();
	}

	$phraseApi = vB_Api::instanceInternal('phrase');
	$hashChecker = vB::getHashchecker();
	$hashChecker->addIgnoredDir(DIR . '/install');
	$files = $hashChecker->fetchChecksumFiles();

	$files = "<ul><li>" . implode("</li>\n<li>", $files) . "</li></ul>";
	$message = construct_phrase($vbphrase['verify_following_manifests'], $files);

	print_form_header('admincp/diagnostic', 'doversion');
	print_table_header($vbphrase['suspect_file_versions']);
	print_label_row($message);

	['fatalErrors' => $errors, 'startupWarnings' => $warnings, ] = $hashChecker->getErrors();
	if (!empty($errors))
	{
		$errorPhrases = $phraseApi->renderPhrases($errors);
		$errorPhrases = $errorPhrases['phrases'];
		print_description_row(implode('<br />', $errorPhrases), false, 2, 'warning-red');
	}
	if (!empty($warnings))
	{
		$errorPhrases = $phraseApi->renderPhrases($warnings);
		$errorPhrases = $errorPhrases['phrases'];
		print_description_row(implode('<br />', $errorPhrases), false, 2, 'warning-red');
	}

	print_submit_row($vbphrase['begin_process'], false);
}

if ($_POST['do'] == 'doversion')
{
	if (!$canUseAll)
	{
		print_cp_no_permission();
	}

	$phraseApi = vB_Api::instanceInternal('phrase');
	$hashChecker = vB::getHashchecker();
	$hashChecker->addIgnoredDir(DIR . '/install');
	$check = $hashChecker->verifyFiles();

	if (!$check['success'])
	{
		print_stop_message_array($check['fatalErrors']);
	}
	else
	{
		print_form_header('admincp/diagnostic', 'doversion');
		print_table_header($vbphrase['suspect_file_versions']);

		// Show which manifests were used.
		if (!empty($check['checksumManifests']))
		{
			$files = implode(', ', $check['checksumManifests']);
			$message = construct_phrase($vbphrase['following_manifests_used'], $files);
			print_label_row($message);
		}

		// Show any startup warnings (like md5 file writable, etc)
		if (!empty($check['startupWarnings']))
		{
			$errorPhrases = $phraseApi->renderPhrases($check['startupWarnings']);
			$errorPhrases = $errorPhrases['phrases'];
			print_description_row(implode('<br />', $errorPhrases), false, 2, 'warning-red');
		}

		// Output problematic directories
		if (empty($check['errors']))
		{
			/*
				Important note, this just signifies that of the files explicitly specified
				in the checksum manifest file(s) we did not find any mismatched content,
				however there may be skipped/ignored/unexpected files that we could not
				validate.
			 */
			print_label_row($vbphrase['no_failed_checksum']);
		}
		else
		{
			foreach ($check['errors'] AS $directory => $filesToErrors)
			{
				if (isset($check['fileCounts'][$directory]))
				{
					$file_count = $check['fileCounts'][$directory];
					$message = "<div style=\"float:" . vB_Template_Runtime::fetchStyleVar('right') . "\">"
								. construct_phrase($vbphrase['scanned_x_files'], $file_count)
								. "</div>.$directory";
				}
				else
				{
					/*
						I don't think we currently have any errors that can occur that doesn't have a
						corresponding fileCounts element.
						We used to have a few possible cases where we didn't keep track of certain
						folders that only contained subfolders, or if a directory wasn't recognized,
						the directory could've been missing from the fileCounts list, but we've changed
						a number of things to try to make sure every known (even if empty) directory has
						an entry in the checksum file, and that even empty directories get a fileCount row
						in the hashchecker return.
						However, I'm going to keep this here just in case I missed any, or in case we
						change things in the future.
					 */
					$message = ".$directory";
				}
				print_description_row($message, 0, 2, 'thead');

				foreach ($filesToErrors AS $file => $errors)
				{
					$errorPhrases = $phraseApi->renderPhrases($errors);
					$errorPhrases = $errorPhrases['phrases'];
					print_label_row($file, implode('<br />', $errorPhrases));
				}

				unset($check['errors'][$directory], $check['fileCounts'][$directory]);
			}
		}

		// Adding a convenient repeat button in the "break" before skipped dirs/files listing
		print_submit_row($vbphrase['repeat_process'], false);

		function print_collapsed_header($headerphrasedata, $count, $vbphrase, $forceStartOpenOrClosed = null)
		{
			$startCollapsed = !($forceStartOpenOrClosed ?? ($count <= 20));
			$expandPhrase = construct_phrase($vbphrase['expand_x_directories'], $count);

			// print_submit_row() also closes the form, so we have to re-open it here.
			$collapseId = uniqid('acp-collapse-');

			print_form_header2('admincp/diagnostic', 'doversion');
			print_table_start(null, null, 0, 'cpform_table', false, false, $collapseId, $startCollapsed);

			if (!is_array($headerphrasedata))
			{
				$headerphrase = $vbphrase[$headerphrasedata];
			}
			else
			{
				$headerphrase = array_unshift($headerphrasedata);
				$headerphrase = construct_phrase($vbphrase[$headerphrase], ...$headerphrasedata);
			}
			echo "<tbody class=\"no-collapse\">";
			print_table_header($headerphrase);
			echo "</tbody>";

			print_collapse_control_row($expandPhrase, $vbphrase['collapse_all'], $collapseId, 2, true);

			return $collapseId;
		}

		// Flag skipped folders & skipped files for manual review.
		if (!empty($check['skippedDirs']))
		{
			/*
				skipped dirs look like
				[
					'core' => ['cpstyles', 'install', ],
					'core/includes' => ['datastore', ],
					...
				]
			 */
			$count = array_sum(array_map('count', $check['skippedDirs']));
			// print_submit_row() also closes the form, so we have to re-open it here.
			$collapseId = print_collapsed_header('following_directories_skipped', $count, $vbphrase, false);
			$expandPhrase = construct_phrase($vbphrase['expand_x_directories'], $count);
			open_collapse_group($collapseId);

			$i = 0;
			$showCollapseEvery = 30;
			// Output clean directories
			foreach ($check['skippedDirs'] AS $__parent => $__children)
			{
				foreach ($__children AS $__dir)
				{
					$message = '.' . $__parent . DIRECTORY_SEPARATOR . $__dir;
					print_description_row($message, 0, 2, 'thead');
					if (++$i % $showCollapseEvery == 0)
					{
						print_collapse_control_row($expandPhrase, $vbphrase['collapse_all'], $collapseId);
					}
				}
			}

			// Show final collapse button
			if ($i % $showCollapseEvery != 0 AND $count > 5)
			{
				print_collapse_control_row($expandPhrase, $vbphrase['collapse_all'], $collapseId);
			}
			close_collapse_group($collapseId);
			// Note, skipped dirs & files don't contribute to fileCounts, so they're not accounted here.

			print_submit_row($vbphrase['repeat_process'], false);
		}
		if (!empty($check['skippedFiles']))
		{
			/*
				skippedFiles look like
				[
					'/' => ['.htaccess', 'config.php', 'config.php.bkp', ... ],
					'/core/includes' => ['config.php', ... ],
					...
				]
			 */
			//var_dump($check['skippedFiles']);
			// due to test settings, the following directories or files were not scanned.
			// Please manually review them.
			$dirCount = count($check['skippedFiles']);
			// print_submit_row() also closes the form, so we have to re-open it here.
			$outerCollapseId = print_collapsed_header('following_files_skipped', $dirCount, $vbphrase, false);
			$expandPhrase = construct_phrase($vbphrase['expand_x_directories'], $dirCount);
			open_collapse_group($outerCollapseId);

			$i = 0;
			$showCollapseEvery = 30;
			// Output clean directories
			foreach ($check['skippedFiles'] AS $__dir => $__files)
			{
				$__innerCollapseID = uniqid("acp-collapse-inner-");
				open_collapse_group($__innerCollapseID);

				$__expandLabel = construct_phrase($vbphrase['expand_dir_x_y_files'], $__dir, count($__files));
				$__collapseLabel = construct_phrase($vbphrase['collapse_x_y_files'], $__dir, count($__files));
				print_collapse_control_row($__expandLabel, $__collapseLabel, $__innerCollapseID, 1, true);

				// This is really only for the root directory item that shows up as "/", so we don't end up
				// with like ".//UNSCANNED_FILE" instead of "./UNSCANNED_FILE"
				$__dir = rtrim($__dir, DIRECTORY_SEPARATOR);
				foreach ($__files AS $__files)
				{
					$__row = '.' . $__dir . DIRECTORY_SEPARATOR . $__files;
					print_description_row($__row, 0, 2);

					if (++$i % $showCollapseEvery == 0)
					{
						print_collapse_control_row($expandPhrase, $vbphrase['collapse_all'], $outerCollapseId);
					}
				}
				close_collapse_group($__innerCollapseID);
			}

			// Show final collapse button
			if ($i % $showCollapseEvery != 0 AND $count > 5)
			{
				print_collapse_control_row($expandPhrase, $vbphrase['collapse_all'], $outerCollapseId);
			}
			close_collapse_group($outerCollapseId);
			// Note, skipped dirs & files don't contribute to fileCounts, so they're not accounted here.

			print_submit_row($vbphrase['repeat_process'], false);
		}


		if (!empty($check['fileCounts']))
		{
			// # of directories
			$count = count($check['fileCounts']);
			$collapseId = print_collapsed_header('following_directories_clean', $count, $vbphrase);
			$expandPhrase = construct_phrase($vbphrase['expand_x_directories'], $count);
			open_collapse_group($collapseId);

			$i = 0;
			$showCollapseEvery = 30;
			// Output clean directories
			foreach ($check['fileCounts'] AS $directory => $file_count)
			{
				$message = "<div style=\"float:" . vB_Template_Runtime::fetchStyleVar('right') . "\">"
							. construct_phrase($vbphrase['scanned_x_files'], $file_count)
							. "</div>.$directory";
				print_description_row($message, 0, 2, 'thead');

				if (++$i % $showCollapseEvery == 0)
				{
					print_collapse_control_row($expandPhrase, $vbphrase['collapse_all'], $collapseId);
				}
			}

			if ($i % $showCollapseEvery != 0)
			{
				print_collapse_control_row($expandPhrase, $vbphrase['collapse_all'], $collapseId);
			}
			close_collapse_group($collapseId);

			print_submit_row($vbphrase['repeat_process'], false);
		}
	}
}
else if ($_REQUEST['do'] == 'doversion')
{
	// If we're here then this page was probably visited by a GET.
	// While the filescan doesn't do any writes or state changes, so GET *should*
	// be OK, let's not change that for now, but at least print a submit button
	// so they don't have to go back to the main diagnostics page to resubmit a scan.

	if (!$canUseAll)
	{
		print_cp_no_permission();
	}
	print_form_header('admincp/diagnostic', 'doversion');
	print_table_header($vbphrase['suspect_file_versions']);
	print_description_row(construct_phrase($vbphrase['file_versions_explained'], $vbulletin->options['templateversion']));
	print_submit_row($vbphrase['submit'], 0);
}

if ($_GET['do'] == 'payments')
{
	/**
	 * Note that this block cannot be accessed directly from this page.  It's called from the
	 * Paid Subscriptions -> Test Communication page
	 */

	require_once(DIR . '/includes/class_paid_subscription.php');
	$subobj = new vB_PaidSubscription();

	print_form_header('admincp/subscriptions');
	print_table_header($vbphrase['payment_api_tests'], 2);
	print_cells_row(array($vbphrase['title'], $vbphrase['pass']), 1, 'tcat', 1);
	$apis = $vbulletin->db->query_read("
		SELECT * FROM " . TABLE_PREFIX . "paymentapi WHERE active = 1
	");

	$yesImage = get_cpstyle_href('cp_tick_yes.gif');
	$noImage = get_cpstyle_href('cp_tick_no.gif');

	while ($api = $vbulletin->db->fetch_array($apis))
	{
		$cells = array();
		$cells[] = $api['title'];

		$obj = vB_PaidSubscription::fetchPaymentMethodInstance($api);
		if (!is_null($obj))
		{
			if ($obj->test())
			{
				$cells[] = "<img src=\"$yesImage\" alt=\"\" />";
			}
			else
			{
				$cells[] = "<img src=\"$noImage\" alt=\"\" />";
			}
		}
		print_cells_row($cells, 0, '', 1);
	}

	print_table_footer(2);
}

if ($_REQUEST['do'] == 'server_modules')
{
	if (!$canUseAll)
	{
		print_cp_no_permission();
	}
	print_form_header('admincp/', '');

	print_form_header('admincp/', '');
	print_table_header('mod_security');

	//javascript will attempt to load an image with an ajax character in the url to check for a block.
	register_js_phrase('yes');
	print_label_row($vbphrase['mod_security_ajax_issue'], "<span id=\"mod_security_test_result\">$vbphrase[no]</span>");
	print_diagnostic_test_result(-1, $vbphrase['mod_security_problem_desc'], 0);
	print_table_footer();
}

if ($_POST['do'] == 'ssl')
{
	if (!$canUseAll)
	{
		print_cp_no_permission();
	}
	print_form_header('admincp/', '');
	print_table_header($vbphrase['tls_ssl']);

	$ssl_available = false;
	if (function_exists('curl_init') AND ($ch = curl_init()) !== false)
	{
		$curlinfo = curl_version();
		if (!empty($curlinfo['ssl_version']))
		{
			// passed
			$ssl_available = true;
		}
		curl_close($ch);
	}

	if (function_exists('openssl_open'))
	{
		// passed
		$ssl_available = true;
	}

	print_label_row($vbphrase['ssl_available'], ($ssl_available ? $vbphrase['yes'] : $vbphrase['no']));
	print_diagnostic_test_result(0, $vbphrase['ssl_unavailable_desc'], 0);

	print_table_footer();
}

// ###################### Start options list #######################
if ($_REQUEST['do'] == 'list')
{
	if ($canUseAll)
	{
		print_form_header('admincp/diagnostic', 'doupload', 1);
		print_table_header($vbphrase['upload']);
		print_description_row($vbphrase['upload_test_desc']);
		print_upload_row($vbphrase['filename_gcpglobal'], 'attachfile');
		print_submit_row($vbphrase['upload'],NULL);
	}

	print_form_header('admincp/diagnostic', 'domail');
	print_table_header($vbphrase['email']);
	print_description_row($vbphrase['email_test_explained']);
	if (empty($vboptions['webmasteremail']))
	{
		print_description_row($vbphrase['warn_webmasteremail_empty']);
	}
	print_input_row($vbphrase['email'], 'emailaddress');
	print_submit_row($vbphrase['send']);

	if ($canUseAll)
	{
		print_form_header('admincp/diagnostic', 'predoversion');
		print_table_header($vbphrase['suspect_file_versions']);
		print_description_row(construct_phrase($vbphrase['file_versions_explained'], $vbulletin->options['templateversion']));
		print_submit_row($vbphrase['submit'], 0);

		print_form_header('admincp/diagnostic', 'server_modules');
		print_table_header($vbphrase['problematic_server_modules']);
		print_description_row($vbphrase['problematic_server_modules_explained']);
		print_submit_row($vbphrase['submit'], 0);

		print_form_header('admincp/diagnostic', 'ssl');
		print_table_header($vbphrase['tls_ssl']);
		print_description_row($vbphrase['facebook_connect_ssl_req_explained']);
		print_submit_row($vbphrase['submit'], 0);

		print_form_header('admincp/diagnostic', 'dosysinfo');
		print_table_header($vbphrase['system_information']);
		print_description_row($vbphrase['server_information_desc']);
		$selectopts = array(
			'mysql_vars' => $vbphrase['mysql_variables'],
			'mysql_status' => $vbphrase['mysql_status'],
			'table_status' => $vbphrase['table_status']
		);
		print_select_row($vbphrase['view'], 'type', $selectopts);
		print_submit_row($vbphrase['submit']);

		print_form_header('admincp/diagnostic', 'dogeoip');
		print_table_header($vbphrase['testgeoip']);
		print_description_row($vbphrase['geoip_test_explained']);
		print_input_row($vbphrase['ip_address'], 'ipaddress', vB::getRequest()->getIpAddress());
		print_submit_row($vbphrase['send']);


		print_form_header('admincp/diagnostic', 'doimagick');
		print_table_header($vbphrase['testimagick']);
		print_description_row($vbphrase['imagick_test_explained']);
		print_submit_row($vbphrase['submit']);


		print_form_header('admincp/diagnostic', 'dateformat');
		print_table_header($vbphrase['dateformat_diagnostics_header']);
		print_description_row($vbphrase['dateformat_diagnostics_desc']);
		print_submit_row($vbphrase['submit']);


		print_form_header2('admincp/fcm', 'testkey');
		print_table_start();
		print_table_header($vbphrase['firebasecloudmessaging']);
		print_description_row($vbphrase['fcm_diagnostics_desc']);
		print_table_default_footer($vbphrase['go']);
	}
}

print_cp_footer();

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 115429 $
|| #######################################################################
\*=========================================================================*/
