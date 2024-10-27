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

// ####################### SET PHP ENVIRONMENT ###########################
error_reporting(error_reporting() & ~E_NOTICE);

// #################### DEFINE IMPORTANT CONSTANTS #######################
define('THIS_SCRIPT', 'payment_gateway');
define('CSRF_PROTECTION', false);
define('SKIP_SESSIONCREATE', 1);
if (!defined('VB_ENTRY'))
{
	define('VB_ENTRY', 1);
}

// #################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = ['subscription'];

// get special data templates from the datastore
$specialtemplates = [];

// pre-cache templates used by all actions
$globaltemplates = [];

// pre-cache templates used by specific actions
$actiontemplates = [];

// ######################### REQUIRE BACK-END ############################
define('VB_AREA', 'Subscriptions');
define('VB_API', false);

// VB INIT //
//set_exception_handler('handleExceptionAjax');
require_once('vb/vb.php');
vB::silentWarnings(); // VBV-16618 Suppress or Resolve array_merge error in MAPI
vB::init();
$request = new vB_Request_Web();
vB::setRequest($request);

//$request->createSessionForUser(0);
vB::setCurrentSession(new vB_Session_Skip(vB::getDBAssertor(), vB::getDatastore(), vB::getConfig()));
// This used to be defined in core/includes/init.php. Used by this file & class_paid_subscriptions.php,
// so not replaced with a var yet.
if (!defined('TIMENOW'))
{
	define('TIMENOW', $request->getTimeNow());
}


require_once(DIR . '/includes/adminfunctions.php');
require_once(DIR . '/includes/class_paid_subscription.php');

$vbulletin->input->clean_array_gpc('r', [
	'method' => vB_Cleaner::TYPE_STR
]);

$config = vB::getConfig();
$debug = !empty($config['Misc']['debugpayments']);

if ($debug)
{
	error_log(
		__FILE__ . " @ LINE " . __LINE__
		. "\n" . "_REQUEST: " . print_r($_REQUEST, true)
		. "\n" . "_SERVER: " . print_r($_SERVER, true)
	);
}

$assertor = vB::getDbAssertor();

$api = $assertor->getRow('vBForum:paymentapi', ['classname' => $vbulletin->GPC['method']]);
if (!empty($api) AND $api['active'])
{
	$subobj = new vB_PaidSubscription();
	$apiobj = vB_PaidSubscription::fetchPaymentMethodInstance($api);
	if (!is_null($apiobj))
	{
		if ($apiobj->verify_payment())
		{
			// its a valid payment now lets check transactionid
			$transaction = $assertor->getRow('vBForum:paymenttransaction', [
				'transactionid' => $apiobj->transaction_id,
				'paymentapiid' => $api['paymentapiid'],
			]);

			// Payment emails have been moved back to the end, because we don't want email
			// timeouts to cause critical subscription processing failures.

			if ($debug)
			{
				error_log(
					__FILE__ . " @ LINE " . __LINE__
					. "\n" . "Payment Verified."
					. "\n" . "apiobj->type: " . print_r($apiobj->type, true)
					. "\n" . "apiobj->transaction_id: " . print_r($apiobj->transaction_id, true)
					. "\n" . "apiobj->paymentinfo: " . print_r($apiobj->paymentinfo, true)
					. "\n" . "apiobj->display_feedback: " . print_r($apiobj->display_feedback, true)
					. "\n" . "transaction: " . print_r($transaction, true)
				);
			}


			if (empty($transaction))
			{
				// transaction hasn't been processed before
				/*insert query*/
				$trans = [
					'transactionid' => $apiobj->transaction_id,
					'paymentinfoid' => $apiobj->paymentinfo['paymentinfoid'],
					'amount'        => $apiobj->paymentinfo['amount'],
					'currency'      => $apiobj->paymentinfo['currency'],
					'state'         => $apiobj->type,
					'dateline'      => TIMENOW,
					'paymentapiid'  => $api['paymentapiid'],
				];

				if (!$apiobj->type)
				{
					$log = $apiobj->getRequestForLogging();
					$log['vb_error_code'] = $apiobj->error_code;
					$trans['request'] = serialize($log);
				}

				$assertor->insert('vBForum:paymenttransaction', $trans);

				if ($apiobj->type == vB_PaidSubscriptionMethod::TXN_TYPE_PAYMENT)
				{
					$subobj->build_user_subscription($apiobj->paymentinfo['subscriptionid'], $apiobj->paymentinfo['subscriptionsubid'], $apiobj->paymentinfo['userid']);
					if ($apiobj->display_feedback)
					{
						paymentCompleteRedirect();
					}
				}
				else if ($apiobj->type == vB_PaidSubscriptionMethod::TXN_TYPE_CANCEL)
				{
					$subobj->delete_user_subscription($apiobj->paymentinfo['subscriptionid'], $apiobj->paymentinfo['userid'], $apiobj->paymentinfo['subscriptionsubid']);
				}
			}
			else if ($apiobj->type == vB_PaidSubscriptionMethod::TXN_TYPE_CANCEL)
			{
				// Also track Refund transactions.
				$trans = [
					'transactionid' => $apiobj->transaction_id,
					'paymentinfoid' => $apiobj->paymentinfo['paymentinfoid'],
					'amount'        => $apiobj->paymentinfo['amount'],
					'currency'      => $apiobj->paymentinfo['currency'],
					'state'         => $apiobj->type,
					'dateline'      => TIMENOW,
					'paymentapiid'  => $api['paymentapiid'],
				];
				if (!$apiobj->type)
				{
					$log = $apiobj->getRequestForLogging();
					$log['vb_error_code'] = $apiobj->error_code;
					$trans['request'] = serialize($log);
				}
				$assertor->insert('vBForum:paymenttransaction', $trans);

				// transaction is a reversal / refund
				$subobj->delete_user_subscription($apiobj->paymentinfo['subscriptionid'], $apiobj->paymentinfo['userid'], $apiobj->paymentinfo['subscriptionsubid']);
			}
			else
			{
				// its most likely a re-post of a payment, if we've already dealt with it serve up a redirect
				if ($apiobj->display_feedback)
				{
					paymentCompleteRedirect();
				}
			}

			// Emails moved to end of processing, as otherwise email timeouts may completely block subscription renewals
			if (shouldDoPaymentEmails($apiobj, $transaction, $vbulletin))
			{
				handlePaymentEmails($api['title'], $apiobj);
			}
		}
		else
		{
			if ($debug)
			{
				$verstate = ($apiobj->type == $apiobj::TXN_TYPE_LOGONLY ? 'skipped' : 'failed');
				error_log(
					__FILE__ . " @ LINE " . __LINE__
					. "\n" . "Payment verification $verstate. "
					. "\n" . "apiobj->type: " . print_r($apiobj->type, true)
					. "\n" . "apiobj->error_code: " . print_r($apiobj->error_code, true)
					. "\n" . "apiobj->transaction_id: " . print_r($apiobj->transaction_id, true)
					. "\n" . "apiobj->paymentinfo: " . print_r($apiobj->paymentinfo, true)
					. "\n" . "apiobj->display_feedback: " . print_r($apiobj->display_feedback, true)
				);
			}

			// type == 3 was only used by google checkout, which has been removed. It was used to ignore & also NOT LOG the transaction.
			// type 4 means roughly "we've logged it, it's not an error but it's not something we need to act on".
			// Currently used in PayPal IPN and Stripe webhook handling
			if ($apiobj->type == $apiobj::TXN_TYPE_LOGONLY)
			{
				$log = $apiobj->getRequestForLogging();
				$trans = [
					'state'         => $apiobj::TXN_TYPE_LOGONLY,
					'dateline'      => TIMENOW,
					'paymentapiid'  => $api['paymentapiid'],
					'request'       => serialize($log),
				];
				$assertor->insert('vBForum:paymenttransaction', $trans);
				if ($apiobj->display_feedback AND !empty($apiobj->error))
				{
					showError($api['title'], $apiobj->error);
				}
			}
			else
			{
				// something went wrong, get $apiobj->error & mark it as TXN_TYPE_ERROR.
				$log = $apiobj->getRequestForLogging();
				$log['vb_error_code'] = $apiobj->error_code;
				$trans = [
					'state'         => $apiobj::TXN_TYPE_ERROR,
					'dateline'      => TIMENOW,
					'paymentapiid'  => $api['paymentapiid'],
					'request'       => serialize($log),
				];
				$assertor->insert('vBForum:paymenttransaction', $trans);
				if ($apiobj->display_feedback AND !empty($apiobj->error))
				{
					showError($api['title'], $apiobj->error);
				}
			}
		}
	}
	else
	{
		if ($debug)
		{
			error_log(
				__FILE__ . " @ LINE " . __LINE__
				. "\n" . "Payment class file (class_{$api['classname']}.php) not found"
			);
		}
	}
}
else
{
	if ($debug)
	{
		if (empty($api))
		{
			error_log(
				__FILE__ . " @ LINE " . __LINE__
				. "\n" . "Payment Ignored: Payment API {$vbulletin->GPC['method']} not found"
			);
		}
		else
		{
			error_log(
				__FILE__ . " @ LINE " . __LINE__
				. "\n" . "Payment Ignored: Payment API {$vbulletin->GPC['method']} not active"
			);
		}
	}

	exec_header_redirect(vB5_Route::buildHomeUrl('fullurl'));
}

function shouldDoPaymentEmails($apiobj, $transaction, $vbulletin)
{
	if (
		(
			$apiobj->type == vB_PaidSubscriptionMethod::TXN_TYPE_CANCEL OR
			(empty($transaction) AND $apiobj->type == vB_PaidSubscriptionMethod::TXN_TYPE_PAYMENT)
		) AND $vbulletin->options['paymentemail']
	)
	{
		return true;
	}

	return false;
}

function handlePaymentEmails($apititle, $apiobj)
{
	$string = vB::getString();
	$datastore = vB::getDatastore();
	$bbtitle = $datastore->getOption('bbtitle');
	$bbtitle_escaped = $string->htmlspecialchars($bbtitle);
	$phraseApi = vB_Api::instanceInternal('phrase');

	$paymentemail = $datastore->getOption('paymentemail');
	// While there's nothing in the setting description or adminhelp that indicates that
	// this option can take multiple emails, this was copied from the legacy code which
	// expected a space delimited list of emails.
	$emails = explode(' ', $paymentemail);
	if (empty($emails))
	{
		// just a short circuit check to skip unnecessary processing if we don't have any recipients
		// for this notice email.
		return;
	}

	$username = $apiobj->paymentinfo['username'];
	$userid = $apiobj->paymentinfo['userid'];
	$userLabel = vB_User::getEmailUserLabel($apiobj->paymentinfo);
	$subPhraseTitle = 'sub' . $apiobj->paymentinfo['subscriptionid'] . '_title';
	['phrases' => $phrases] = $phraseApi->renderPhrases(['subTitle' => $subPhraseTitle]);
	// These *may* need to be escaped, e.g. if subscription or payment title contained special characters like < > meant to be displayed as WYSIWYG
	$subscription = $phrases['subTitle'];
	// amount and currency are usually set in PHP by each paymentapi class, except for the test api.
	$amount = vb_number_format($apiobj->paymentinfo['amount'] ?? 0, 2) . ' ' . strtoupper($apiobj->paymentinfo['currency'] ?? '');
	$processor = $apititle;
	$transactionid = $apiobj->transaction_id;

	// This needs to be escaped once we start using anchors
	$memberlink = vB5_Route::buildUrl('profile|bburl', ['userid' => $userid, 'username' => $apiobj->paymentinfo['username']]);

	if ($apiobj->type == vB_PaidSubscriptionMethod::TXN_TYPE_CANCEL)
	{
		$maildata = vB_Api::instanceInternal('phrase')->fetchEmailPhrases(
			'payment_reversed',
			[
				$username,
				$bbtitle_escaped,
				$memberlink,
				$subscription,
				$amount,
				$processor,
				$transactionid,
				$userLabel,
			],
			[$bbtitle]
		);
	}
	else
	{
		$maildata = vB_Api::instanceInternal('phrase')->fetchEmailPhrases(
			'payment_received',
			[
				$username,
				$bbtitle_escaped,
				$memberlink,
				$subscription,
				$amount,
				$processor,
				$transactionid,
				$userLabel,
			],
			[$bbtitle]
		);
	}

	foreach($emails AS $toemail)
	{
		if (trim($toemail))
		{
			// For now, skipping vbmailWithUnsubscribe() & isUserOptedOutOfEmail() as we don't have
			// a specific email option for payment status emails (so if we "opt out", we won't be able
			// to opt back in..). We may want to add a specific option for this and revisit.
			vB_Mail::vbmail2($toemail, $maildata['subject'], $maildata['message'], true);
		}
	}
}

function paymentCompleteRedirect()
{
	showRedirect('payment_complete', vB5_Route::buildUrl('settings|fullurl', ['tab' => 'subscriptions']));
}

function bootstrapFrontend()
{
	//boot the front end code.  This isn't ideal -- for one thing it violates the dusty and unused notion that
	//the core directory can be relocated from it's default location -- but the backend template engine
	//is creaky and can't handle rendering the header block.
	//
	//We really should relocate the callback to a frontend route/controller and get rid of this entire file
	//but that would ential a tremendous amount of risk for some difficult to test code
	$rootdir = realpath(__DIR__ . '/../');
	require_once($rootdir . '/includes/vb5/autoloader.php');
	vB5_Autoloader::register($rootdir);
	vB5_Frontend_Application::init($rootdir);

	//this also runs some init code that we probably want to do before we get to far in.
	Api_InterfaceAbstract::instance();
}

//this function is here because it's some shim code to make this file work that
//really shouldn't be used elsewhere.
function showRedirect($phrase, $url)
{
	bootstrapFrontend();

	$preheader = vB5_ApplicationAbstract::getPreheader();
	$phrase = vB_Api::instanceInternal('phrase')->renderPhrases(['redirect' => $phrase]);
	$message = $phrase['phrases']['redirect'];

	//Copied from the standard_redirect function.  Much of this is old, old code to avoid xss problems.
	//Some of it due to the fact that, unlike now the url could be sourced from user data.  Not
	//sure why the standard html escape is inadequate (or if it even is) but I don't want to change
	//and risk weird bugs/security problems.
	static
		$str_find     = ['"',      '<',    '>'],
		$str_replace  = ['&quot;', '&lt;', '&gt;'];

	$url = str_replace(chr(0), '', $url);
	$url = str_replace($str_find, $str_replace, $url);
	$js_url = addslashes_js($url, '"'); // " has been replaced by &quot;

	$url = preg_replace(
		['/&#0*59;?/', '/&#x0*3B;?/i', '#;#'],
		'%3B',
		$url
	);
	$url = preg_replace('#&amp%3B#i', '&amp;', $url);

	//postvars isn't used here (and actually anywhere in the current code)
	//but it's in the template and until it's removed we should set it.
	$page = [];
	$templater = new vB5_Template('STANDARD_REDIRECT');
		$templater->registerGlobal('page', $page);
		$templater->register('errormessage', $message);
		$templater->register('formfile', $url);
		$templater->register('js_url', $js_url);
		$templater->register('postvars', '');
		$templater->register('url', $url);
	$text = $templater->render();
	print_output($preheader . $text);
	// Do not exit, because we may have to send emails afterwards.
}

function showError($title, $message)
{
	bootstrapFrontend();
	vB5_ApplicationAbstract::showMsgPage($title, $message);
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 116483 $
|| #######################################################################
\*=========================================================================*/
