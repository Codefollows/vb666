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

/**
* Class that provides payment verification and form generation functions
*
* @package	vBulletin
* @version	$Revision: 114976 $
* @date		$Date: 2024-02-14 14:29:40 -0800 (Wed, 14 Feb 2024) $
*/
class vB_PaidSubscriptionMethod_paypal extends vB_PaidSubscriptionMethod
{
	private function getPaymentFormUrl()
	{
		if ($this->isSandboxMode())
		{
			return 'https://www.sandbox.paypal.com/cgi-bin/webscr';
		}
		else
		{
			return 'https://www.paypal.com/cgi-bin/webscr';
		}
	}

	private function getIPNValidateUrl()
	{
		if ($this->isSandboxMode())
		{
			return 'https://ipnpb.sandbox.paypal.com/cgi-bin/webscr';
		}
		else
		{
			return 'https://ipnpb.paypal.com/cgi-bin/webscr';
		}
	}

	// Payment API settings are set AFTER construction. This is not great, but working around it for now.
	private function isSandboxMode()
	{
		return !empty($this->settings['sandbox']);
	}

	private function verifyDataWithPaypal(&$used_curl)
	{
		// The reason this uses $_POST is that white-lists may not capture all of the possible IPN properties, and per PayPal's specs we must
		// send the data back EXACTLY:
		// >> Prefix the returned message with the cmd=_notify-validate variable, but do not change the message fields, the order of the fields,
		// >> or the character encoding from the original message.
		// https://developer.paypal.com/api/nvp-soap/ipn/IPNImplementation/#specs   Step 3
		$query = [];
		$query[] = 'cmd=_notify-validate';
		foreach($_POST AS $key => $val)
		{
			$query[] = $key . '=' . urlencode ($val);
		}
		$query = implode('&', $query);

		$result = null;

		if (function_exists('curl_init') AND $ch = curl_init())
		{
			curl_setopt($ch, CURLOPT_URL, $this->getIPNValidateUrl());
			curl_setopt($ch, CURLOPT_TIMEOUT, 15);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_USERAGENT, 'vBulletin via cURL/PHP');
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));

			$result = curl_exec($ch);
			curl_close($ch);
			if ($result !== false)
			{
				$used_curl = true;
			}
		}

		// I'm not exactly sure why, but SOMETIMES the result can be right-padded with a bunch of spaces,
		// so trim before comparing.
		if (is_string($result) AND trim($result) == 'VERIFIED')
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	private function verifyMerchantEmail($postData)
	{
		// Check that the payment recipient (merchant) email from IPN matches our PayPal Email,
		// or receiver_email ("Primary email address of the payment recipient (the merchant).")
		// matches our "PayPal Primary Account Email" setting.
		// Otherwise, this payment is not for us! (e.g. form was tampered)
		return (!empty($this->settings['ppemail']) AND
			(
				strtolower($postData['business'] ?? '') == strtolower($this->settings['ppemail']) OR
				strtolower($postData['receiver_email'] ?? '') == strtolower($this->settings['primaryemail'])
			)
		);
	}

	/**
	* Perform verification of the payment, this is called from the payment gateway
	*
	* @return	bool	Whether the payment is valid
	*/
	function verify_payment()
	{
		$used_curl = false;
		$verified = $this->verifyDataWithPaypal($used_curl);
		if (!$used_curl)
		{
			$this->error_code = 'curl_failure';
			// These errors aren't displayed since $this->display_feedback is false for PayPal. These are meant more for the next
			// developer to look at this.
			$this->error = 'Something went wrong with our cURL. Requesting Re-try from PayPal via 503 response code.';
			// Send a non-200 response to get paypal to send the IPN again, so that we can try to re-submit the postback (assuming
			// the curl issue is transient)
			http_response_code(503);
			return false;
		}

		if (!$verified)
		{
			// If our post-back to PayPal failed, that likely means the IPN data is somehow invalid or got corrupted along the way (or
			// some other issue has made our payload to PayPal invalid).
			// Not a whole lot we can do here other than submit a non-200 HTTP status and get PayPal to resend us the IPN.
			$this->error_code = 'authentication_failure';
			$this->error = 'Invalid Request: Paypal rejected our IPN verification because our message did not match the original IPN message.';
			// 503 to stay consistent with old behavior?
			http_response_code(503);
			return false;
		}


		// This is after the verifyDataWithPaypal() as clean may modify the globals w/ reference vars (or so the previous code
		// seemed to imply).
		$this->registry->input->clean_array_gpc('p', [
			'item_number'    => vB_Cleaner::TYPE_STR,
			'business'       => vB_Cleaner::TYPE_STR,
			'receiver_email' => vB_Cleaner::TYPE_STR,
			'txn_type'       => vB_Cleaner::TYPE_STR,
			'payment_status' => vB_Cleaner::TYPE_STR,
			'mc_currency'    => vB_Cleaner::TYPE_STR,
			'txn_id'         => vB_Cleaner::TYPE_STR,

			'tax'            => vB_Cleaner::TYPE_NUM,
			'mc_gross'       => vB_Cleaner::TYPE_NUM,
		]);

		// IPN variables reference: https://developer.paypal.com/api/nvp-soap/ipn/IPNandPDTVariables/

		$this->transaction_id = $this->registry->GPC['txn_id'];
		$mc_gross = $this->registry->GPC['mc_gross'];
		$tax = $this->registry->GPC['tax'];
		$subscriptionHash = $this->registry->GPC['item_number'];
		$paymentStatus = $this->registry->GPC['payment_status'];
		$transactionType = $this->registry->GPC['txn_type'];
		$mc_currency = strtolower($this->registry->GPC['mc_currency']);

		// If we're here, the IPN data delivery is correct per PayPal.
		// Now check if it's actually ours.
		$correctMerchant = $this->verifyMerchantEmail($this->registry->GPC);
		if (!$correctMerchant)
		{
			// If the merchant doesn't match us, this is not a payment that we are aware of. Send back a 200 so PayPal does not
			// resend the same data (note, since VERIFY succeeded above, this is unlikely to be a data-corruption issue so
			// trying again won't help resolve this), flag it as an error but this is unlikely something on our end. It MAY
			// mean someone fiddled with the form (either on the frontend or backend) to change the payment target
			$this->error_code = 'invalid_merchant';
			$this->error = 'Invalid Request: Payment is not to our PayPal email.';
			http_response_code(200);
			// Note that the inherited $this->display_feedback is false for PayPal. We don't ever want to have any response body.
			return false;
		}

		$assertor = vB::getDbAssertor();
		$this->paymentinfo = $assertor->getRow('vBForum:getPaymentinfo', ['hash' => $subscriptionHash]);

		// lets check the values
		if (!empty($this->paymentinfo))
		{
			$this->paymentinfo['currency'] = $mc_currency;
			$this->paymentinfo['amount'] = $mc_gross;
			//its a paypal payment and we have some valid ids
			$sub = $assertor->getRow('vBForum:subscription', array('subscriptionid' => $this->paymentinfo['subscriptionid']));
			$cost = unserialize($sub['cost']);
			if ($tax > 0)
			{
				$mc_gross -= $tax;
			}

			// Check if its a payment or if its a reversal
			if (($transactionType == 'web_accept' OR $transactionType == 'subscr_payment') AND $paymentStatus == 'Completed')
			{
				$subid = $this->paymentinfo['subscriptionsubid'];
				$expected = $cost[$subid]['cost'][$mc_currency] ?? NULL;
				if (isset($expected) AND $mc_gross == floatval($expected))
				{
					$this->type = static::TXN_TYPE_PAYMENT;
				}
				else
				{
					$this->error_code = 'invalid_payment_amount';
				}
			}
			else if ($paymentStatus == 'Reversed' OR $paymentStatus == 'Refunded')
			{
				$this->type = static::TXN_TYPE_CANCEL;
			}
			else
			{
				/*
				Some notes:
				We cannot log a `paymenttarnsactionid` with a transactionid, as that is used as a flag by payment_gateway.php to see if
				the payment has been handled. However, we want to acknowledge certain non-actionable IPNs and log them for e.g. debugging,
				without flagging them as "failures".

				The way PayPal subscriptions (vB5 recurring subscriptions) currently seems to work is this:
				* When first signing up, PayPal sends an IPN with  txn_type = subscr_signup  and no payment_status.
				* Sometime before/after (in my testing, it was always after, but apparently this order is not guaranteed) the signup IPN,
				  PayPal also sends a  txn_type = subscr_payment  with  payment_status = Completed  . This is the IPN we look for to
				  trigger the actual subscription addition (see above).
				* Side note, for non-recurring (one time) subs, that triggers no  subscr_signup  (since it's not a PayPal subscription),
				  and just a single IPN with  txn_type = web_accept  and  payment_status = Completed  . This is handled above the same way.
				* When the payment is verified, payment_gateway sees that $apiobj->type == 1, with NO existing `paymenttransaction` record
				  (each payment for the same subscription has a unique transactionid, it seems), and calls build_user_subscription() which
				  handles creating a new `subscriptionlog` record if this is a new signup, or extending the expiry of an existing
				  `subscriptionlog` record for a renewal.
				* At each renewal period (end of each subscription period), PayPal handles the subscription payment, and upon success,
				  sends vBulletin a single IPN with  txn_type = subscr_payment  and  payment_status = Completed  . This is handled the same
				  as the first time payment, and as mentioned above, build_user_subscription() handles adding or extending a subscription.
				  Note here that the new payment for the EXISTING subscription apparently has a separate transactionid, which is how it
				  hooks up to the above payment_gateway.php -> build_user_subscription() logic.

				The other action that's handled is  payment_status = Refunded|Reversed  , which will trigger delete_user_subscription() to
				revert the subscription addition that was triggered from a previous  payment_status = Completed  .

				This means that if the end user e.g. stops paying for the subscription, we simply don't receive the next period's renewal
				IPN, and the subscription expires naturally. As such, there's no reason to handle  txn_status = subscr_cancel  or
				txn_status = subscr_signup  , nor should we. To be more specific, for  subscr_cancel  , we shouldn't immediately cancel their
				sub as they already paid for the current subscription, and as far as I can tell this is not for refunding the previous renewal,
				but choosing to no-longer pay for it. In that case, the next renewal IPN will not be sent, and the natural expiry will take
				place. As for  subscr_signup  , since we're already listening for the guaranteed  subscr_payment  that will come in,
				handling  subscr_signup  just means we need to have additional logic to wait and verify a matching  subscr_payment  (which
				might've actually been received BEFORE  subscr_signup  , due to lack of order guarantee), or cancel after some duration of
				time if a matching  subscr_payment  IPN was never received for some reason. In general, this is what I mean by "non-actionable",
				not that we can NOT take action for them, but that actions are either redudant, unnecessary, or adds unnecessary complexity
				to the current handling. However, these are known messages to us and not errors, so marking them as FAILURES as they were
				previously is also not correct.
				Another known one is  txn_status = subscr_eot  , which apparenly is sent on the day of the very last payment. Assuming that's
				accurate, that means we cannot cancel their subscription immediately, as they have paid for that last renewal. And after that
				last renewal expires naturally, the fact that no new payment comes in means the subscription ends as expected.
				Note that accrding to https://www.mixedwaves.com/2010/11/paypal-subscriptions-ipn-demystified/ , the EOT IPN may actually be
				sent at the end of the time, instead of on the date of the last payment, which further complicates the handling if we were to
				do anything with it.
				 */

				switch ($transactionType)
				{
					// See above notes on why these are ignored.
					case 'subscr_signup':
					case 'subscr_eot':
					case 'subscr_cancel':
					// This is if the payment failed. We ignore this because if the payment failed, we wouldn't get the subscr_payment IPN that is
					// actionable, so we have nothing to revert or cancel.
					case 'subscr_failed':
					// Modify is apparently sent when the customer changes their payment plan for example, or switches to a different subscription tier,
					// if the subscription profile supports tiers. We don't support that, so we just note it and ignore it like the rest.
					case 'subscr_modify':
						$this->type = static::TXN_TYPE_LOGONLY;
						// 200 Empty to indicate to PayPal that we got the message and they should not re-send it.
						// Return false to trigger the !verified() block in payment_gateway.php that'll record the payload in `paymenttransactions`
						http_response_code(200);
						return false;
					default:
						break;
				}

				/*
				Subscription vs Recurring note: See https://stackoverflow.com/a/9377429
				We are currently using Subscriptions not Recurring payments. As such we're ignoring any recurring_XYZ txn_type's above.

				IPN URL note:
				Our subscription_payment_paypal specifies a notify_url to which the abovementioned IPN messages will be sent. Currently
				this is set to  {vb:raw vboptions.bburl}/payment_gateway.php?method=paypal  .
				It seems that the notify_url CAN NOT be changed (except possibly by PayPal support), and this overrides the "global"
				IPN URL set under the PayPal account setting's IPN settings:
				"Active subscriptions / recurring payments will use the IPN URL which was active on the account at the time the profile
				 was created.
				 If you want to change the IPN URL to be used, the subscription / recurring payment needs to be cancelled and recreated."
				- https://www.paypal-community.com/t5/Sandbox-Environment/Is-it-possible-to-change-IPN-URL-for-existing-recurring-payment/td-p/1833552
				"If you're conducting subscriptions or any kind of recurring payment, they will use the IPN URL which was active at the
				 time the subscription or recurring payment was setup.
				 The URL cannot be changed for these types of transactions without cancelling the subscription or recurring payment and
				 setting it up again."
				- https://www.paypal-community.com/t5/Sandbox-Environment/PayPal-IPN-Sandbox-Changing-the-Notification-URL-will-not-update/td-p/1935187
				"transaction-level specification supersedes any setting in the PayPal account. It is now set in stone for anything related
				 to that transaction/recurring profile, and cannot be changed."
				- https://stackoverflow.com/a/63963213

				This means that if the forum changes their URL in any way, the IPNs for existing subscriptions will not reach vBulletin,
				unless some sort of forwarding is set up on the old payment gateway URL, or they had set up the notify_url in the
				subscription_payment_paypal template to some forwarding service like zapier: https://zapier.com/help/doc/how-use-multiple-ipns-paypal
				 */

				$this->error_code = 'unhandled_payment_status_or_type';
			}
		}
		else
		{
			$this->error_code = 'invalid_subscriptionid';
		}

		// For "regular" errors, we should not return a 503 as a non 200-response indicates to PayPal that they need to resend
		// the IPN, which will retrigger the above code:
		// >> After receiving the IPN message from PayPal, your listener returns an empty HTTP 200 response to PayPal.
		// >> Otherwise, PayPal resends the IPN message.
		// https://developer.paypal.com/api/nvp-soap/ipn/IPNImplementation/#specs   Step 2
		// Chances are, if we failed to process this the first time and it wasn't some connectivity or IPN message corruption
		// issues (for which we reply with a 503 above), the IPN is not going to somehow allow us to verify again the next time.
		http_response_code(200);
		// Note that the inherited $this->display_feedback is false for PayPal, as we want an EMPTY response.
		return ($this->type > 0);
	}

	/**
	* Test that required settings are available, and if we can communicate with the server (if required)
	*
	* @return	bool	If the vBulletin has all the information required to accept payments
	*/
	public function test()
	{
		$communication = false;
		$query = 'cmd=_notify-validate';

		if (function_exists('curl_init') AND $ch = curl_init())
		{
			curl_setopt($ch, CURLOPT_URL, $this->getIPNValidateUrl());
			curl_setopt($ch, CURLOPT_TIMEOUT, 15);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_USERAGENT, 'vBulletin via cURL/PHP');
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));

			$result = curl_exec($ch);
			curl_close($ch);
			if ($result !== false)
			{
				$communication = true;
			}
		}

		return (!empty($this->settings['ppemail']) AND $communication);
	}

	/**
	* Generates HTML for the subscription form page
	*
	* @param	string		Hash used to indicate the transaction within vBulletin
	* @param	string		The cost of this payment
	* @param	string		The currency of this payment
	* @param	array		Information regarding the subscription that is being purchased
	* @param	array		Information about the user who is purchasing this subscription
	* @param	array		Array containing specific data about the cost and time for the specific subscription period
	*
	* @return	array		Compiled form information
	*/
	public function generate_form_html($hash, $cost, $currency, $subinfo, $userinfo, $timeinfo, $extra = [])
	{
		// Currently public as construct_payment() needs access to this.
		$item = $hash;
		$currency = strtoupper($currency);

		$show['notax'] = ($subinfo['newoptions']['api']['paypal']['tax']) ? false : true;
		$show['recurring'] = !empty($timeinfo['recurring']);
		$no_shipping = '1';
		switch ($subinfo['newoptions']['api']['paypal']['shipping_address'])
		{
			case 'none':
				$no_shipping = '1';
				break;
			case 'optional':
				$no_shipping = '0';
				break;
			case 'required':
				$no_shipping = '2';
				break;
		}

		$form['action'] = $this->getPaymentFormUrl();
		$form['method'] = 'post';

		$vbphrase = vB_Api::instanceInternal('phrase')->fetch('sub' . $subinfo['subscriptionid'] . '_title');
		$subinfo['title'] = $vbphrase['sub' . $subinfo['subscriptionid'] . '_title'];

		// load settings into array so the template system can access them
		$settings =& $this->settings;

		$templater = new vB5_Template('subscription_payment_paypal');
			$templater->register('cost', $cost);
			$templater->register('currency', $currency);
			$templater->register('item', $item);
			$templater->register('no_shipping', $no_shipping);
			$templater->register('settings', $settings);
			$templater->register('subinfo', $subinfo);
			$templater->register('timeinfo', $timeinfo);
			$templater->register('userinfo', $userinfo);
			$templater->register('show', $show);
		$form['hiddenfields'] .= $templater->render();
		return $form;
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 114976 $
|| #######################################################################
\*=========================================================================*/
