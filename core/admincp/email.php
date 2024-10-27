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
define('CVS_REVISION', '$RCSfile$ - $Revision: 115430 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
global $phrasegroups, $specialtemplates, $vbphrase, $vbulletin;
$phrasegroups = ['user', 'cpuser', 'messaging', 'cprofilefield', 'profilefield'];
$specialtemplates = [];

// ########################## REQUIRE BACK-END ############################
require_once(dirname(__FILE__) . '/global.php');
require_once(DIR . '/includes/adminfunctions_profilefield.php');
require_once(DIR . '/includes/adminfunctions_user.php');
$assertor = vB::getDbAssertor();

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!can_administer('canadminusers'))
{
	print_cp_no_permission();
}

// ############################# LOG ACTION ###############################
log_admin_action();

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

print_cp_header($vbphrase['email_manager']);

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'start';
}

// *************************** Send a page of emails **********************
if ($_POST['do'] == 'dosendmail' OR $_POST['do'] == 'makelist')
{
	$vbulletin->input->clean_array_gpc('p', [
		'user'              => vB_Cleaner::TYPE_ARRAY,
		'profile'           => vB_Cleaner::TYPE_ARRAY,
		'serializeduser'    => vB_Cleaner::TYPE_STR,
		'serializedprofile' => vB_Cleaner::TYPE_STR,
		'septext'           => vB_Cleaner::TYPE_NOTRIM,
		'perpage'           => vB_Cleaner::TYPE_UINT,
		'startat'           => vB_Cleaner::TYPE_UINT,
		'test'              => vB_Cleaner::TYPE_BOOL,
		'from'              => vB_Cleaner::TYPE_STR,
		'subject'           => vB_Cleaner::TYPE_STR,
		'message'           => vB_Cleaner::TYPE_STR,
	]);

	$vbulletin->GPC['septext'] = nl2br(htmlspecialchars_uni($vbulletin->GPC['septext']));

	// ensure that we don't send blank emails by mistake
	if ($_POST['do'] == 'dosendmail')
	{
		if ($vbulletin->GPC['subject'] == '' OR $vbulletin->GPC['message'] == '' OR !is_valid_email($vbulletin->GPC['from']))
		{
			print_stop_message2('please_complete_required_fields');
		}
	}

	if (!empty($vbulletin->GPC['serializeduser']))
	{
		$vbulletin->GPC['user'] = @unserialize(verify_client_string($vbulletin->GPC['serializeduser']));
		$vbulletin->GPC['profile'] = @unserialize(verify_client_string($vbulletin->GPC['serializedprofile']));
	}

	$users = vB_Api::instanceInternal('user')->generateMailingList($vbulletin->GPC['user'], $vbulletin->GPC['profile']);
	if ($_POST['do'] == 'makelist')
	{
		if ($users['totalcount'] > 0)
		{
			$seenemails = [];
			foreach ($users['list'] AS $user)
			{
				if (!isset($seenemails[$user['email']]))
				{
					$seenemails[$user['email']] = 1;
					echo $user['email'] . $vbulletin->GPC['septext'];
					vbflush();
				}
			}
			unset($seenemails);
		}
		else
		{
			print_stop_message2('no_users_matched_your_query');
		}
	}
	else
	{
		if (empty($vbulletin->GPC['perpage']))
		{
			$vbulletin->GPC['perpage'] = 500;
		}

		vB_Utility_Functions::setPhpTimeout(0);

		if ($users['totalcount'] == 0)
		{
			print_stop_message2('no_users_matched_your_query');
		}
		else
		{
			$users = vB_Api::instanceInternal('user')->generateMailingList(
				$vbulletin->GPC['user'],
				$vbulletin->GPC['profile'],
				[
					'activation' => 1,
					vB_dB_Query::PARAM_LIMITPAGE => $vbulletin->GPC['startat'],
					vB_dB_Query::PARAM_LIMIT => $vbulletin->GPC['perpage']
				]
			);

			if (count($users['list']))
			{
				$hasactivateid = (strpos($vbulletin->GPC['message'], '$activateid') !== false OR strpos($vbulletin->GPC['message'], '$activatelink') !== false);

				$endcount = $vbulletin->GPC['startat'] + $vbulletin->GPC['perpage'];
				if ($endcount > $users['totalcount'])
				{
					$endcount = $users['totalcount'];
				}

				echo '<p><b>' . $vbphrase['emailing'] . '<br />' .
					construct_phrase(
						$vbphrase['showing_users_x_to_y_of_z'],
						vb_number_format($vbulletin->GPC['startat'] + 1),
						vb_number_format($endcount),
						vb_number_format($users['totalcount'])
					) .
					'</b></p>';

				$userLib = vB_Library::instance('user');
				foreach ($users['list'] AS $user)
				{
					echo "$user[userid] - $user[username] .... \n";
					vbflush();

					$userid = $user['userid'];
					$sendmessage = $vbulletin->GPC['message'];
					$sendmessage = str_replace(
						['$email', '$username', '$userid'],
						[$user['email'], $user['username'], $user['userid']],
						$vbulletin->GPC['message']
					);

					if ($hasactivateid)
					{
						if ($user['usergroupid'] == 3)
						{
							$activate = $userLib->buildActivationInfo($user['userid'], 2);
						}
						else
						{
							$activate = [
								'id' => '',
								'url' => '',
							];
						}

						$sendmessage = str_replace(['$activateid', '$activatelink'], [$activate['id'], $activate['url']], $sendmessage);
					}

					$sendmessage = str_replace(
						['$bburl', '$bbtitle'],
						[$vbulletin->options['bburl'], $vbulletin->options['bbtitle']],
						$sendmessage
					);

					if (!$vbulletin->GPC['test'])
					{
						echo $vbphrase['emailing'] . " \n";
						// Skipping vbmailWithUnsubscribe() & isUserOptedOutOfEmail() here since this should be a one-off admin email.
						// May change this in the future based on feedback however.
						// Should this allow HTML or escape as to be WYSIWYG? For now, allowing HTML as this is an adminCP script.
						vB_Mail::vbmail2($user['email'], $vbulletin->GPC['subject'], $sendmessage, false, $vbulletin->GPC['from']);
					}
					else
					{
						echo $vbphrase['test'] . " ... \n";
					}

					echo $vbphrase['okay'] . "<br />\n";
					vbflush();

				}
				$fields = [
					'test' => $vbulletin->GPC['test'],
					'serializeduser' => sign_client_string(serialize($vbulletin->GPC['user'])),
					'serializedprofile' => sign_client_string(serialize($vbulletin->GPC['profile'])),
					'from' => $vbulletin->GPC['from'],
					'subject' => $vbulletin->GPC['subject'],
					'message' => $vbulletin->GPC['message'],
					'startat' => $vbulletin->GPC['startat'] + $vbulletin->GPC['perpage'],
					'perpage' => $vbulletin->GPC['perpage'],
				];
				print_form_redirect('admincp/email', 'dosendmail', $vbphrase['next_page'], 0, $fields);
			}
			else
			{
				print_stop_message2('emails_sent_successfully', 'email');
			}
		}
	}
}

// *************************** Send invite emails **********************
if ($_POST['do'] == 'doinvite')
{
	$vbulletin->input->clean_array_gpc('p', [
		'from'    => vB_Cleaner::TYPE_STR,
		'to'      => vB_Cleaner::TYPE_STR,
		'subject' => vB_Cleaner::TYPE_STR,
		'message' => vB_Cleaner::TYPE_STR,
	]);

	// verify basic fields
	if (!is_valid_email($vbulletin->GPC['from']) OR $vbulletin->GPC['to'] == '' OR $vbulletin->GPC['subject'] == '' OR $vbulletin->GPC['message'] == '')
	{
		print_stop_message2('please_complete_required_fields');
	}

	// verify the "to" email addresses
	$email_addresses = [];
	$raw_email_addresses = explode("\n", $vbulletin->GPC['to']);
	foreach ($raw_email_addresses AS $raw_email_address)
	{
		$raw_email_address = trim($raw_email_address);
		if (!empty($raw_email_address) AND is_valid_email($raw_email_address))
		{
			$email_addresses[] = $raw_email_address;
		}
	}
	$email_addresses = array_unique($email_addresses);
	if (empty($email_addresses))
	{
		print_stop_message2('please_complete_required_fields');
	}
	$email_address_count = count($email_addresses);

	// send emails
	vB_Utility_Functions::setPhpTimeout(0);
	echo '<p><b>' . construct_phrase($vbphrase['sending_invitation_email_to_x_addresses'], $email_address_count) . '</b></p>';
	foreach ($email_addresses AS $email_address)
	{
		echo $email_address . " ... \n";
		vbflush();

		// There rest of the {shortcode} substitutions are the standard phrase short codes
		// and are made as part of sending the email. This is the only one that's appropriate
		// to do here, since it's not available elsewhere.
		$sendmessage = str_replace('{email}', $email_address, $vbulletin->GPC['message']);

		echo $vbphrase['emailing'] . " \n";
		// Should this allow HTML or escape as to be WYSIWYG? For now, allowing HTML as this is an adminCP script.
		// TODO: This might need some kind of unsubscribe, but that would require that we keep a list of non-registered
		// email addresses that have opted out. Might be best to convert all the mailoption features to hinge on email
		// instead of userid (even if there's a chance of legacy forums with dupe emails), but not sure what the
		// registration-less emails in the DB would mean for GDPR
		vB_Mail::vbmail2($email_address, $vbulletin->GPC['subject'], $sendmessage, false, $vbulletin->GPC['from']);
		vbflush();

		echo $vbphrase['okay'] . "<br />\n";
		vbflush();
	}
}

// *************************** Invite users form **********************
if ($_REQUEST['do'] == 'invite')
{
	$vboptions = vB::getDatastore()->getValue('options');

	print_form_header('admincp/email', 'doinvite');
	print_table_header($vbphrase['invite_members_to_forum']);

	print_input_row($vbphrase['from_gmessaging'], 'from', $vboptions['webmasteremail']);
	print_textarea_row($vbphrase['to_invite_email'], 'to', '', 10, 50);
	print_input_row($vbphrase['subject'], 'subject');
	$default_message = construct_phrase($vbphrase['default_invite_email_content_at_x_y'], $vboptions['bbtitle'], $vboptions['frontendurl']);
	print_textarea_row($vbphrase['message_invite_email'], 'message', $default_message, 10, 50);

	print_submit_row($vbphrase['send']);
}

// *************************** Main email form **********************
if ($_REQUEST['do'] == 'start' OR $_REQUEST['do'] == 'genlist')
{
	if ($_REQUEST['do'] == 'start')
	{
		print_form_header('admincp/email', 'dosendmail');
		print_table_header($vbphrase['email_manager']);
		print_yes_no_row($vbphrase['test_email_only'], 'test', 0);
		print_input_row($vbphrase['email_to_send_at_once_gcpuser'], 'perpage', 500);
		print_input_row($vbphrase['from_gmessaging'], 'from', $vbulletin->options['webmasteremail']);
		print_input_row($vbphrase['subject'], 'subject');
		print_textarea_row($vbphrase['message_email'], 'message', '', 10, 50);
		$text = $vbphrase['send'];

	}
	else
	{
		print_form_header('admincp/email', 'makelist');
		print_table_header($vbphrase['generate_mailing_list']);
		print_textarea_row($vbphrase['text_to_separate_addresses_by'], 'septext', ' ');
		$text = $vbphrase['go'];
	}

	print_table_break();
	print_table_header($vbphrase['search_criteria']);
	print_user_search_rows(true);

	print_table_break();
	print_submit_row($text);
}

print_cp_footer();

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 115430 $
|| #######################################################################
\*=========================================================================*/
