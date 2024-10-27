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
* @version	$Revision: 109629 $
* @date		$Date: 2022-06-24 16:53:29 -0700 (Fri, 24 Jun 2022) $
*/
class vB_PaidSubscriptionMethod_paypal2 extends vB_PaidSubscriptionMethod
{
	private $debug = false;
	private $sandbox = false;
	private string $paypal_url = '';


	// Used for storing the message body for logging errors.
	private $payload;
	private $additional_log = [];


	private function log($msg)
	{
		if (!$this->debug)
		{
			return;
		}

		$bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		// item 0 will be the log() call, so it has the right line but
		// we need to know the function that called log, which is one stack above.
		$info = '';
		if (!empty($bt[1]['class']))
		{
			$info = $bt[1]['class'] . "->" . $bt[1]['function'] . "() @ LINE " . $bt[0]['line'];
		}
		else
		{
			$info = $bt[0]['file'] . " @ LINE " . $bt[0]['line'];
		}
		if (!is_string($msg))
		{
			$msg = print_r($msg, true);
		}
		error_log(
			$info
			. "\n$msg"
		);
	}

	/**
	 * Constructor
	 *
	 * @param   array  `paymentapi` record associated with this payment method.
	 *
	 */
	function __construct($paymentapirecord)
	{
		parent::__construct($paymentapirecord);

		$config = vB::getConfig();
		// Note: Logging will only happen when config.Misc.debugpayments is set.
		$this->debug = !empty($config['Misc']['debugpayments']);

		// Set URL: https://developer.paypal.com/api/rest/requests/#link-apirequests
		if (!empty($this->settings['sandbox']))
		{
			$this->sandbox = true;
			$this->paypal_url = 'https://api-m.sandbox.paypal.com';
		}
		else
		{
			$this->sandbox = false;
			$this->paypal_url = 'https://api-m.paypal.com';
		}
	}



	/**
	 * Called by payment_gateway.php for logging transaction for TXN_TYPE_ERROR & TXN_TYPE_LOGONLY.
	 *
	 * @return array
	 */
	public function getRequestForLogging()
	{
		$data = [
			'JSON'          => $this->payload,
			'additional_log' => $this->additional_log,
		];
		return $data;
	}

	private function isWebhookAlreadyHandled($transactionid, $transactiontype = vB_PaidSubscriptionMethod::TXN_TYPE_PAYMENT) : bool
	{
		$assertor = vB::getDbAssertor();
		$transaction = $assertor->getRow('vBForum:paymenttransaction', [
			'transactionid' => $transactionid,
			'paymentapiid' => $this->paymentapirecord['paymentapiid'],
			'state' => $transactiontype,
		]);

		return !empty($transaction);
	}

	/**
	* Perform verification of the payment, this is called from the payment gateway
	*
	* @return	bool	Whether the payment is valid
	*/
	public function verify_payment()
	{
		// We are expecting application/JSON data, which won't be available in $_POST.
		// Note, this is set before the SDK init so the message is logged in paymenttransaction for debugging purposes even if our end fails.
		$this->payload = file_get_contents('php://input');
		$this->additional_log = [];
		$this->type = self::TXN_TYPE_LOGONLY;
		$this->paymentinfo = [];

		$auth_algo = $_SERVER['HTTP_PAYPAL_AUTH_ALGO'] ?? '';
		$cert_url = $_SERVER['HTTP_PAYPAL_CERT_URL'] ?? '';
		$transmission_id = $_SERVER['HTTP_PAYPAL_TRANSMISSION_ID'] ?? '';
		$transmission_sig = $_SERVER['HTTP_PAYPAL_TRANSMISSION_SIG'] ?? '';
		$transmission_time = $_SERVER['HTTP_PAYPAL_TRANSMISSION_TIME'] ?? '';
		$webhook_id = $this->getWebhookId();
		$payload = json_decode($this->payload, true);
		// $event_version = $payload['event_version'] ?? '';
		// $resource_version = $payload['resource_version'] ?? '';

		['body' => $response, 'headers' => $headers,] = $this->callApi(
			'/v1/notifications/verify-webhook-signature',
			[
				'auth_algo' => $auth_algo,
				'cert_url' => $cert_url,
				'transmission_id' => $transmission_id,
				'transmission_sig' => $transmission_sig,
				'transmission_time' => $transmission_time,
				'webhook_id' => $webhook_id,
				'webhook_event' => $payload,
			],
			[
				'Prefer' => 'Prefer: return=minimal',
			],
		);

		// From what I could test in the sandbox, non-paypal-subscription (i.e. non-recurring) payments do NOT
		// generate the PAYMENT.SALE.COMPLETED webhook.
		$check = $response['verification_status'] ?? '';
		if ($check != 'SUCCESS')
		{
			$this->log("PayPal webhook verification failure: " . print_r($check, true) . "\nPayload:". print_r($payload, true));
			$this->indicateRetry();
			$this->additional_log[] = "Webhook failed to validate against PayPal. Returned 400 code for retry.";
			return false;
		}
		else
		{
			$this->log("PayPal webhook verified: " . print_r($payload, true));
		}

		// Signal paypal not to resend this.
		$this->indicateOk();

		// transaction_id is needed to handle any potential duplicate webhook calls. Saved into the `paymenttransaction` record
		// alongside much of the $this->paymentinfo data.
		$this->transaction_id = $payload['resource']['id'] ?? '';


		// We now use custom_id, because refund data of non-recurring payment had seemingly NO way to hop back to the proper orderid
		// for us to map back to the vb-subscription/payment. For both paypal-subscriptions & paypal-checkouts, custom_id is set to
		// the associated `paymentinfo`.`hash` ...
		$resource = $payload['resource'];
		// Great. This field also is NOT consistent. Seems like one-time payment refund sets 'custom_id', but recurring payment refund sets
		// 'custom'
		$hash = $resource['custom_id'] ?? $resource['custom'] ?? '';
		$paymentinfo = [];
		if ($hash)
		{
			$assertor = vB::getDbAssertor();
			$paymentinfo = $assertor->getRow('vBForum:getPaymentinfo', ['hash' => $hash]);
		}
		// ... however, for subscription webhooks, the custom_id is NOT provided in the webhook data. We can probably find it
		// through fetching the original order, but we kept track of the subscriptionid so that's faster.
		$subscriptionid = $payload['resource']['billing_agreement_id'] ?? '';
		if (!$paymentinfo AND $subscriptionid)
		{
			// note, this also gets called for the first time payment.. need to dedupe.
			$paymentinfo = $this->getPaymentInfoBySubscriptionId($subscriptionid);
		}
		if (!$paymentinfo['hash'])
		{
			$this->log("Failed to find `paymentinfo` record for hash $hash (or subscriptionid $subscriptionid) while handling PayPal webhook");

			$this->type = self::TXN_TYPE_ERROR;
			$this->error_code = 'missing_data';
			return false;
		}



		$event_type = $payload['event_type'];
		$txnType = self::TXN_TYPE_LOGONLY;
		$isRefund = in_array($event_type, [
			// recurring payments. Refunded is merchant-initiated, reversed is usually a customer dispute that results in paypal's reversal
			'PAYMENT.SALE.REFUNDED',
			'PAYMENT.SALE.REVERSED',
			// one-time payments
			'PAYMENT.CAPTURE.REFUNDED',
			'PAYMENT.CAPTURE.REVERSED',
		]);
		$isPayment = ($event_type == 'PAYMENT.SALE.COMPLETED');

		if (!$isRefund AND !$isPayment)
		{
			$this->error_code = 'unknown_webhook';
			$this->additional_log[] = "Unknown event type: '{$event_type}'. Webhook was logged but no action was taken.";
			// we don't currently handle this. log only.
			return false;
		}

		$txnType = ($isRefund ? self::TXN_TYPE_CANCEL : self::TXN_TYPE_PAYMENT);
		$check = $this->isWebhookAlreadyHandled($this->transaction_id, $txnType);
		if ($check)
		{
			$this->log("Duplicate webhook skipped. Paypload: " . print_r($payload, true));
			// Payment gateway will ignore this & also not re-log this transaction, since we already have it above.
			$this->type = self::TXN_TYPE_DUPE_WEBHOOK;
			return true;
		}

		// Apparently COMPLETED vs REFUNDED Have different fields.. have not been able to test REVERSED yet, but
		// assuming it's the same as REFUNDED for now.
		$amount = $payload['resource']['amount'];
		if ($isPayment)
		{
			$receivedTotal = $amount['total'];
			$currency = $amount['currency'];
		}
		else
		{
			// Okay, actually, this just is not at all consistent. Seems like the refund fields also differ
			// depending on one-time vs subscription? Just fallback -- we don't validate this for refunds,
			// it's just for display in the transactionlogs, and the ACTUAL refunded amount can get very
			// complicated, e.g. if partial refunds or multiple pay sources, and admin should probably use
			// the paypal dashboard to view detailed information. For refunds, not reversals, the admin
			// probably DID view the detailed info since refunds are usually triggered from merchant not
			// customer (as opposed to disputes/reversals)
			$receivedTotal = $amount['value'] ?? $amount['total'] ?? '';
			$currency = $amount['currency_code'] ?? $amount['currency'] ?? '';
		}

		// Used by payment_gateway.php for building subscriptions as well as transaction logging
		$this->paymentinfo = $paymentinfo;
		$this->paymentinfo['currency'] = $currency;
		$this->paymentinfo['amount'] = $receivedTotal;


		if ($isRefund)
		{
			$this->log("PayPal Webhook refund received, processed.");
			// Historically, we don't seem to check for payment info for refunds, and just immediately
			// cancel the sub & log the transaction via payment_gateway. Not sure what to check in any case,
			// so let's just follow precedence.
			$this->type = self::TXN_TYPE_CANCEL;
			return true;
		}
		else if ($isPayment)
		{
			$expectedCost = $this->fetchExpectedCostConsideringTaxSettings(
				$paymentinfo['subscriptionid'],
				$paymentinfo['subscriptionsubid'],
				$currency
			);
			// If difference is < 1 cent, that should be close enough.
			// This is assuming we're only using currencies that have up to 2 decimals (some have 3), which is currently the case.
			$closeEnough = (abs($expectedCost - $receivedTotal) < 0.01);
			if ($closeEnough)
			{
				$this->log("PayPal Webhook payment amount received & validated. (transactionid: {$this->transaction_id }). Subscription is extended.");
				$this->type = self::TXN_TYPE_PAYMENT;
				return true;
			}
			else
			{
				$this->log("PayPal Webhook payment amount was incorrect (transactionid: {$this->transaction_id }). "
					. "Expected $expectedCost but received $receivedTotal in $currency. Subscription was not extended.");

				$this->type = self::TXN_TYPE_ERROR;
				$this->error_code = 'invalid_payment_amount';
				return false;
			}
		}
		else
		{
			// this is handled in the first check against event_type
		}

		return false;
	}

	/**
	 * Only call this or indicateRetry() once per process.
	 */
	private function indicateOk()
	{
		if (!headers_sent())
		{
			http_response_code(200);
			// Stripe (& other payment processors in general) require that you return a header status as fast as possible, before
			// running any processing that might cause a timeout on their end, otherwise they may re-send the event.
			// As such, unless we're explicitly waiting for output, we should send any headers back before we do longer internal
			// processing
			flush();
		}
	}

	/**
	 * Only call this or indicateOk() once per process.
	 */
	private function indicateRetry()
	{
		if (!headers_sent())
		{
			// PayPal does not seem to actually document what the webhook listener should return anywhere. This is based on
			// their support forum posts, it *seems* like anything not a 20x will indicate a retry.
			http_response_code(400);
			flush();
		}
	}

	private function fetchExpectedCostConsideringTaxSettings($vbsubscriptionid, $vbsubscription_subid, $currency)
	{
		$currency = strtolower($currency);
		$assertor = vB::getDbAssertor();
		$sub = $assertor->getRow('vBForum:subscription', ['subscriptionid' => $vbsubscriptionid]);
		$cost = unserialize($sub['cost'], ['allowed_classes' => false]);
		$expectedCost = $cost[$vbsubscription_subid]['cost'][$currency] ?? NULL;

		$newoptions = unserialize($sub['newoptions'], ['allowed_classes' => false]);
		$subscriptionSettings = $newoptions['api']['paypal2'];
		if ($subscriptionSettings['tax'])
		{
			$taxPercent = $subscriptionSettings['tax_percentage'];
			if (!is_numeric($taxPercent) OR $taxPercent < 0 OR $taxPercent > 100)
			{
				$taxPercent = 0;
			}

			/*
			Amount with inclusive tax looks like:
			($3, 33% tax)
				[amount] => Array
                (
                    [total] => 3.33
                    [currency] => USD
                    [details] => Array
                        (
                            [subtotal] => 3.33
                        )

                )

			With exclusive tax, looks like:
			($1, 23% tax)
			[amount] => Array
                (
                    [total] => 1.23
                    [currency] => USD
                    [details] => Array
                        (
                            [subtotal] => 1.23
                        )

                )
			*/
			// Add the additional tax that'll show up in the webhook
			if ($subscriptionSettings['tax_behavior'] != 'inclusive')
			{
				$expectedCost = $expectedCost * (1 + $taxPercent / 100);
			}
		}

		return $expectedCost;
	}

	/**
	 * For APIs that support it, call the remote endpoint to cancel automatic recurring payments when
	 * a user cancels their vBulletin recurring subscription.
	 *
	 * @param int   $userid
	 * @param int   $vbsubscriptionid
	 */
	public function cancelRemoteSubscription($userid, $vbsubscriptionid)
	{
		$assertor = vB::getDbAssertor();
		$params = [
			'paymentapiid' => $this->paymentapirecord['paymentapiid'],
			'vbsubscriptionid' => $vbsubscriptionid,
			'userid' => $userid,
			'active' => 1,
		];
		$subids = $assertor->getColumn('vBForum:paymentapi_subscription', 'paymentsubid', $params);
		$defaultParams = $params;
		foreach ($subids AS $__subid)
		{
			$__conditions = $defaultParams;
			$__conditions['paymentsubid'] = $__subid;

			['body' => $response, 'headers' => $headers,] = $this->callApi(
				'/v1/billing/subscriptions/' . $__subid . '/cancel',
				[],
				[
					'Prefer' => 'Prefer: return=minimal',
				],
			);
			// Successful response is 204 No Content status with no JSON body...
			if ($headers['http-response']['statuscode'] == 204)
			{
				$assertor->update('vBForum:paymentapi_subscription', ['active' => 0], $__conditions);
			}
			else
			{
				// ignore..
				$this->log("Failed to auto cancel Paypal Subscription {$__subid}.");
			}
		}
	}

	/**
	* Test that required settings are available, and if we can communicate with the server (if required)
	*
	* @return	bool	If the vBulletin has all the information required to accept payments
	*/
	public function test()
	{
		if (empty($this->getClientID()))
		{
			$this->log("PayPal API missing client ID");
			return false;
		}
		if (empty($this->getClientSecret()))
		{
			$this->log("PayPal API missing Secret Key");
			return false;
		}

		$response = $this->getAccessToken();
		if (empty($response['access_token']))
		{
			$this->log("PayPal API failed to fetch access_token (Are client ID & secret key valid?). Response was:" . print_r($response, true));
			return false;
		}
		$this->log("PayPal API client ID & secret KEY validated. access_token successfully fetched.");


		$webhookid = $this->getWebhookId();
		if (empty($webhookid))
		{
			$this->log("PayPal API is missing Webhook ID. Verify the client ID & secret, clear the webhook ID in payment API settings, and re-save to autogenerate the webhook ID.");
			return false;
		}
		else
		{
			['body' => $response, 'headers' => $headers,] = $this->callApi(
				'/v1/notifications/webhooks/' . $webhookid,
				[],
				[
					'Prefer' => 'Prefer: return=minimal',
				],
				'GET'
			);

			$webhookurl = $this->getOurWebhookEndpoint();
			$urlCheck = ($response['url'] ?? '') == $webhookurl;
			$webhookEventSubscriptions = $this->getWebhookEvents();
			$notfound = array_flip($webhookEventSubscriptions);
			foreach ($response['event_types'] ?? [] AS $__evt)
			{
				if (isset($notfound[$__evt['name']]))
				{
					unset($notfound[$__evt['name']]);
				}
			}
			if (!$urlCheck)
			{
				$this->log("PayPal API Webhook URL check failed. Expected: {$webhookurl} Webhook Response: " . print_r($response, true));
				return false;
			}
			if (count($notfound) > 0)
			{
				$this->log("PayPal API Webhook Events check failed. Following events were not found: " . print_r(array_keys($notfound), true) . "\nWebhook Response: " . print_r($response, true));
				return false;
			}
		}
		$this->log("PayPal API Webhook validated");


		['body' => $response, 'headers' => $headers,] = $this->callApi(
			'/v1/catalogs/products',
			[],
			[],
			'GET'
		);
		// A successful request returns the HTTP 200 OK status code and a JSON response body that lists products with details.
		if ($headers['http-response']['statuscode'] == 200)
		{
			$this->log("Sample PayPal API call successful: " . print_r($response, true));
		}
		else
		{
			$this->log("Sample PayPal API call failed: " . print_r($response, true));
			return false;
		}

		return true;
	}


	/**
	 * Called near the end of admincp/subscriptions.php's 'apiupdate', after
	 * new settings are saved into the database. Meant to allow subclasses to
	 * handle any automated API calls when activated (e.g. adding webhooks)
	 *
	 */
	public function post_update_settings()
	{
		// We can check paymentapiRecord['active'] if we want to only run some things when activated.
		$this->checkAndSetWebhooks();
	}

	private function getWebhookEvents() : array
	{
		return [
			// Subscriptions
			'PAYMENT.SALE.COMPLETED',
			'PAYMENT.SALE.REFUNDED',
			'PAYMENT.SALE.REVERSED',
			// One time payments
			// WE don't listen to PAYMENT.CAPTURE.COMPLETED, because instead we're using the
			// immediate callback through completeOrder() instead. However, refunds will generate
			// webhooks that we need to listen to & handle.
			'PAYMENT.CAPTURE.REFUNDED',
			'PAYMENT.CAPTURE.REVERSED',
		];
	}

	private function checkAndSetWebhooks()
	{
		$listenerurl = $this->getOurWebhookEndpoint();

		$webhookEventSubscriptions = $this->getWebhookEvents();
		$eventTypes = [];
		foreach ($webhookEventSubscriptions AS $__evt)
		{
			$eventTypes[] = ['name' => $__evt];
		}

		$existing = $this->getWebhookId();
		if ($existing)
		{
			['body' => $response, 'headers' => $headers,] = $this->callApi(
				'/v1/notifications/webhooks/' . $existing,
				[],
				[
					'Prefer' => 'Prefer: return=minimal',
				],
				'GET'
			);
			if (!empty($response['url']))
			{
				$urlCheck = $response['url'] == $listenerurl;
				$notfound = array_flip($webhookEventSubscriptions);
				foreach ($response['event_types'] AS $__evt)
				{
					if (isset($notfound[$__evt['name']]))
					{
						unset($notfound[$__evt['name']]);
					}
					else
					{
						// If we wanted to keep the "extra" event types in tact when we need to update,
						// we could do this. E.g. this would allow paypal merchants to bind additional
						// events if they e.g. have custom code to handle them
						//$eventTypes[] = $__evt['name'];
					}
				}
				$eventsCheck = empty($notfound);

				if ($urlCheck AND $eventsCheck)
				{
					// We have no way of verifying the secret until we receive a message. Assume it's good.
					$this->log("PayPal Webhook exists: " . print_r($existing, true));
					return true;
				}
				else
				{
					if (!$urlCheck)
					{
						$this->log("PayPal Webhook was found, but the URL did not match. Existing: {$response['url']} , Expected: $listenerurl");
					}
					if (!$eventsCheck)
					{
						$this->log("PayPal Webhook was found, but some event subscriptions are missing: " . print_r(array_keys($notfound), true));
					}

					if ($this->tryUpdateWebhook($existing, $listenerurl, $eventTypes))
					{
						// we successfully updated the existing webhook, so we don't have to delete and retry.
						return;
					}

					// If PATCH failed, need to delete the old one before trying to create a new one since paypal does not allow multiple webhooks per single URL.
					$this->tryDeleteWebhook($existing);
					$this->log("Creating a new webhook. Old webhook: " . print_r($existing, true));
				}
			}
			else
			{
				$this->log("Failed to verify existing PayPal webhook ($existing). Response was: " . print_r($response, true));
			}
		}


		// If we got here, we need to create a new webhook either because we don't have one, or because something changed but
		// we couldn't update the existing one and deleted the old one.

		// automagically create webhook & store webhook ID for validation later.
		// https://developer.paypal.com/docs/api/webhooks/v1/#webhooks_post

		['body' => $response, 'headers' => $headers,] = $this->callApi(
			'/v1/notifications/webhooks',
			[
				'url' => $listenerurl,
				'event_types' => $eventTypes,
			],
			[
				'Prefer' => 'Prefer: return=minimal',
			]
		);
		if (!empty($response['id']))
		{
			$this->log("Generated new PayPal webhook: {$response['id']}");
			$this->saveWebhookId($response['id']);
		}
		else
		{
			$this->log("Failed to generate paypal webhook. Response was: " . print_r($response, true));
			// In case the setting just got messed up somehow (e.g. hitting back and save), try to recover from a fail loop.
			if (($response['name'] ?? '') == 'WEBHOOK_URL_ALREADY_EXISTS')
			{
				$id = $this->tryFindOurWebhook($listenerurl);
				if ($id)
				{
					// Also try to update the webhook in case it's outdated.
					$this->tryUpdateWebhook($existing, $listenerurl, $eventTypes);
					$this->log("Restored an existing PayPal webhook: $id (old ID: $existing)");
					$this->saveWebhookId($id);
				}

			}
		}
	}

	private function tryUpdateWebhook(string $webhookid, string $listenerurl, array $eventTypes) : bool
	{
		// https://developer.paypal.com/docs/api/webhooks/v1/#webhooks_update
		['body' => $response, 'headers' => $headers,] = $this->callApi(
			'/v1/notifications/webhooks/' . $webhookid,
			[
				[
					'op' => 'replace',
					'path' => '/url',
					'value' => $listenerurl,
				],
				[
					'op' => 'replace',
					'path' => '/event_types',
					'value' => $eventTypes,
				],
			],
			[],
			'PATCH'
		);
		// A successful request returns the HTTP 200 OK status code and a JSON response body that shows webhook details.
		if ($headers['http-response']['statuscode'] == 200)
		{
			$this->log("Updated existing webhook: " . print_r($response, true));
			return true;
		}
		else
		{
			$this->log("Updating old webhook failed: " . print_r($response, true));
			return false;
		}
	}

	private function tryFindOurWebhook(string $listenerurl) : string
	{
		['body' => $response, 'headers' => $headers,] = $this->callApi(
			'/v1/notifications/webhooks',
			[],
			[],
			'GET'
		);
		if (!empty($response['webhooks']))
		{
			foreach ($response['webhooks'] AS ['url' => $__url, 'id' => $__id])
			{
				if ($__url == $listenerurl)
				{
					return $__id;
				}

			}
		}

		return '';
	}

	private function tryDeleteWebhook($webhookid)
	{
		['body' => $response, 'headers' => $headers,] = $this->callApi(
			'/v1/notifications/webhooks/' . $webhookid,
			[],
			[],
			'DELETE'
		);
	}

	private function saveWebhookId($id)
	{
		$assertor = vB::getDbAssertor();
		$record = $this->paymentapirecord;
		if (!empty($record))
		{
			$settings = vB_Utility_Unserialize::unserialize($record['settings']);
			$settings['webhook_id']['value'] = $id;
			$serialized = serialize($settings);
			$check = $assertor->update('vBForum:paymentapi', ['settings' => $serialized], ['paymentapiid' => $record['paymentapiid']]);
			if ($check)
			{
				$this->settings['webhook_id'] = $id;
				// we now store the paymentapi record, so we should update that too just in case.
				$this->paymentapirecord['settings'] = $serialized;
				return $check;
			}
		}
		else
		{
			$this->log("Error: Tried to save webhook ID & secret but failed to fetch the existing settings. Settings were not saved.");
		}

		return false;
	}


	/**
	 * Validate a single subscription's time & cost info against the payment API.
	 * This is for restricted platforms like Square that has restrictions on the subscription
	 * length, and supported currencies.
	 *
	 * @param array  $costsinfo  Nested array [
	 *      0 => [
	 *          int    'length'     Subscription length time-value. E.g. 2 for 2 weeks
	 *          string 'units'      Subscription length time-units (D, W, M, Y). E.g. W for 2 weeks
	 *          bool   'recurring'  True of recurring, false if one-time only.
	 *          array  'cost'       array key value pairs of currency => amount, e.g. [
	 *                'usd' => 1.25,
	 *                'gbp' => 1.04,
	 *                'aud' => 1.81,
	 *          ]
	 *      ]
	 * ]
	 *
	 * @return bool  true if successful
	 * @throws vB_Exception_Api  on validation failure
	 */
	public function validatePricingAndTime($costsArray)
	{
		// ...
	}

	/**
	 * Insert or update remote catalog items for a vBulletin subscription
	 *
	 * @param int    $vbsubscriptionid         `subscription`.`subscriptionid`
	 * @param array  $costsArray               Unserialized data stored in `subscription`.`cost` .
	 *                                         The numeric keys are "subscriptionsubid", while values
	 *                                         are of the form [
	 *                                            'recurring' => bool,
	 *                                            'length' => int,
	 *                                            'units' => string 'D'|'W'|'M'|'Y',
	 *                                            'recurring' => bool,
	 *                                            'cost' => [<string currency> => <float cost>, 'usd' => 55, 'aud' => 55...]
	 *                                         ]
	 * @param array  $subscriptionApiOptions   The "newoptions" subarray for this specific API.
	 *                                         May contain things like 'show', 'tax', 'tax_percentage' etc
	 *
	 * @throws vB_Exception_Api  on upsert failure
	 */
	public function ensureRemoteCatalogItems($vbsubscriptionid, $costsArray, $subscriptionApiOptions = [])
	{
		/*
		https://developer.paypal.com/docs/subscriptions/integrate/#link-createsubscriptionplan

		Subscriptions need a product ID, and you can create different "subscription plans" (different
		billing cycle) against the same product.

		So, we could actually use 1 product for *all* of the subscriptions. On the other extreme,
		we could have 1 product for each subscription - subid (different pricing/duration options)
		pairs.

		I think it makes the most sense to have each "subscription" to be its own product, so that
		it can be represented by the subscription title, but re-use that same product for the
		sub-subid's within each subscription, and just create the specific billing plan.
		*/

		// Not sure ATM what to do if we have currencies locally that are not supported by paypal..
		$productid = $this->ensurePaypalProduct($vbsubscriptionid);

		$currencies = $this->getPaypalCurrencies();
		$currencies = array_combine($currencies, $currencies);
		//$this->log($vbsubscriptionid);
		//$this->log($costsArray);
		foreach ($costsArray AS $__subid => $__costandtime)
		{
			// Do not catalog non-recurring subscriptions, as they'll be handled by ad hoc one time payments.
			if (!$__costandtime['recurring'])
			{
				continue;
			}


			// TODO: Test what happens with unsupported currencies...
			// If this one doesn't have our currency, just skip and assume it's intentionally meant to be handled by other payment APIs.
			foreach ($__costandtime['cost'] AS $__currency => $__cost)
			{
				$__currencyUpper = strtoupper($__currency);
				if (isset($currencies[$__currencyUpper]))
				{
					try
					{
						// make this bullshit array easier to read and transfer
						$__subscriptionEntity = $this->convertArrayToPaidsubscriptionEntity($vbsubscriptionid, $__subid, $costsArray, $__currency);
						$this->ensurePaypalSubscriptionPlan($productid, $__subscriptionEntity, $subscriptionApiOptions);
					}
					catch (Throwable $e)
					{
						$this->log($e);
						$this->log("Failed to generate PayPal Subscription Plan for subscription $vbsubscriptionid.$__subid, currency $__currency");
					}
				}
				else
				{
					$this->log("Unsupported currency $__currency skipped for PayPal API");
				}
			}
		}
	}

	// todo: push to shared library
	private function convertArrayToPaidsubscriptionEntity($vbsubscriptionid, $vbsubscription_subid, $timeAndCosts, $currency) : vB_Entity_Paidsubscription
	{
		/** @var vB_Library_Paidsubscription */
		$lib = vB_Library::instance('paidsubscription');

		$vbsubscriptioninfo = $lib->convertArrayToPaidsubscriptionEntity($vbsubscriptionid, $vbsubscription_subid, $timeAndCosts, $currency);

		// $actualCost = $timeAndCosts[$vbsubscription_subid]['cost'][$currency] ?? null;
		// if (is_null($actualCost))
		// {
		// 	throw new vB_Exception_Api('currency_not_supported');
		// }

		// $vbsubscriptioninfo = vB_Entity_Paidsubscription::createFromArray([
		// 	'cost' => $actualCost,
		// 	'currency' => $currency,
		// 	// time info
		// 	'recurring' => $timeAndCosts[$vbsubscription_subid]['recurring'],
		// 	'duration' => $timeAndCosts[$vbsubscription_subid]['length'],
		// 	'duration_units' => $timeAndCosts[$vbsubscription_subid]['units'],
		// 	// sub info
		// 	'subscriptionid' => $vbsubscriptionid,
		// 	'subscriptionsubid' => $vbsubscription_subid,
		// 	// NA
		// 	'userid' => 0,
		// 	'vbhash' => '',
		// ]);

		return $vbsubscriptioninfo;
	}

	private function getRemoteCatalogItem($vbsubscriptionid, $vbsubscription_subid = 0, $type = 'product', $currency = '#ALL') : array
	{
		$assertor = vB::getDbAssertor();
		$check = $assertor->getRow('vBForum:paymentapi_remote_catalog', [
			'paymentapiid' => $this->paymentapirecord['paymentapiid'],
			'vbsubscriptionid' => $vbsubscriptionid,
			'vbsubscription_subid' => $vbsubscription_subid,
			'type' => $type,
			'currency' => $currency,
		]);
		if (!empty($check['data']))
		{
			$check['data'] = json_decode($check['data'], true);
		}

		return $check ?? [];
	}

	private function formatCost($cost, $currency)
	{
		// Note, if you pass in a decimal to a currency that does not support decimals, apparently an error will occur.
		// Not sure if PayPal has simple validation to accept ".00" decimals for integer currencies...

		$zeroDecimalCurrencies = $this->getZeroDecimalCurrencies();
		if (in_array($currency, $zeroDecimalCurrencies))
		{
			$cost = intval(round($cost, 0));
		}

		return round($cost, 2);
	}

	private function ensurePaypalSubscriptionPlan(string $paypalProductid, vB_Entity_Paidsubscription $plan, array $subscriptionApiOptions) : string
	{
		// This check is removed because otherwise we'll hit (& miss) the DB redundantly in a loop when we're saving any
		// sub-subs with multiple currencies. So the caller should ensure this instead (and note this method is not labeled
		// "ensure" because we skip this check).
		$catalogType = 'plan';
		$existing = $this->getRemoteCatalogItem($plan->subscriptionid, $plan->subscriptionsubid, $catalogType, $plan->currency);

		$time_units_full = [
			'D' => 'DAY',
			'W' => 'WEEK',
			'M' => 'MONTH',
			'Y' => 'YEAR'
		];
		$interval_unit = $time_units_full[$plan->duration_units] ?? null;
		// PayPal has limits on the max duration for each time unit:
		// https://developer.paypal.com/docs/api/subscriptions/v1/#plans_create!path=billing_cycles/frequency/interval_count&t=request
		$max = [
			'DAY' => 365,
			'WEEK' => 52,
			'MONTH' => 12,
			'YEAR' => 1,
		];
		if (is_null($interval_unit))
		{
			$accepted = array_keys($time_units_full);
			$this->log("This plan's time unit is invalid: {$plan->duration_units}. Accepted: " . implode(',', $accepted));
			throw new vB_Exception_Api('payment_api_error');
		}
		if ($max[$interval_unit] < $plan->duration)
		{
			$this->log("This plan's time period ({$plan->duration} {$interval_unit} exceeds PayPal's maximum duration for the given time unit: {$max[$interval_unit]}");
			throw new vB_Exception_Api('payment_api_error');
		}


		$title = $this->fetchSubscriptionTitle($plan->subscriptionid);
		$description = "$title (" . $plan->currency . $plan->cost . " / " . $plan->duration . $plan->duration_units . ")";
		$formattedCost = $this->formatCost($plan->cost, $plan->currency);

		$pricing_scheme = [
			'fixed_price' => [
				'currency_code' => $plan->currency,
				'value' => $formattedCost,
			],
		];

		// todo: taxes are now available in $subscriptionApiOptions
		// if ($subscriptionApiOptions['tax'])
		// {
		// 	$taxPercent = $subscriptionApiOptions['tax_percentage'];
		// 	if (!is_numeric($taxPercent) OR $taxPercent < 0 OR $taxPercent > 100)
		// 	{
		// 		$taxPercent = 0;
		// 	}
		// 	$taxes = [
		// 		'inclusive' => ($subscriptionApiOptions['tax_behavior'] == 'inclusive'),
		// 		'percentage' => $taxPercent,
		// 	];
		// }


		$data = [
			'product_id' => $paypalProductid,
			'name' => $title,
			// The plan is active. You can only create subscriptions for a plan in this state.
			'status' => 'ACTIVE',
			'description' => $description,
			'billing_cycles' => [
				[
					'tenure_type' => 'REGULAR',
					// only meaningful with other billing cycles (e.g. trial first, then regular).
					// we only have 1.
					'sequence' => 1,
					// 0 for infinite.
					'total_cycles' => 0,
					'pricing_scheme' => $pricing_scheme,
					'frequency' => [
						'interval_unit' => $interval_unit,
						'interval_count' => $plan->duration,
					],
				]
			],
			// required...
			// https://developer.paypal.com/docs/api/subscriptions/v1/#plans_create!path=payment_preferences&t=request
			'payment_preferences' => [
				'auto_bill_outstanding' => true,
				'setup_fee_failure_action' => 'CANCEL',
				// Max # of payment failures before subscription is suspended. Default is 0, but may want this to be
				// more generous.. not sure if this means multiple sequential <duration>'s failures, or just
				// a single cluster of failures
				'payment_failure_threshold' => 0,
				'setup_fee' => [
					'currency_code' => $plan->currency,
					'value' => 0,
				],

			],
		];

		// Note, taxes are set during create subscription call via the plan overrides.
		// https://developer.paypal.com/docs/api/subscriptions/v1/#subscriptions_create!path=plan&t=request

		$hash = md5(json_encode($data));
		$assertor = vB::getDbAssertor();

		if (!$existing)
		{
			$this->log("Creating new paypal subscription plan");
			/*
			>>> Important: Only one currency_code is allowed per subscription plan. Make a new subscription plan to offer a subscription in another currency.
			*/
			// https://developer.paypal.com/docs/api/subscriptions/v1/#plans_create
			['body' => $response, 'headers' => $headers,] = $this->callApi(
				'/v1/billing/plans',
				$data,
				[
					'Prefer' => 'Prefer: return=minimal',
				]
			);
			if (!empty($response['id']))
			{
				$assertor->insert('vBForum:paymentapi_remote_catalog', [
					'paymentapiid' => $this->paymentapirecord['paymentapiid'],
					'vbsubscriptionid' => $plan->subscriptionid,
					'vbsubscription_subid' => $plan->subscriptionsubid,
					'type' => $catalogType,
					'currency' => $plan->currency,
					'remotecatalogid' => $response['id'],
					'data' => json_encode([
						'product_id' => $response['product_id'] ?? '',
						// cost
						'currency_code' => $plan->currency,
						'value' => $formattedCost,
						// frequency
						'interval_unit' => $interval_unit,
						'interval_count' => $plan->duration,
						// quick diff
						'hash' => $hash,
					]),
					'active' => 1,
				]);

				return $response['id'];
			}
		}
		else if ($existing['data']['hash'] != $hash)
		{
			$keyCondition = [
				'paymentapiid' => $this->paymentapirecord['paymentapiid'],
				'vbsubscriptionid' => $plan->subscriptionid,
				'vbsubscription_subid' => $plan->subscriptionsubid,
				'type' => $catalogType,
				'currency' => $plan->currency,
			];
			$runUpdate = false;
			$productidChanged = ($existing['data']['product_id'] != $paypalProductid);
			$costChanged = ($existing['data']['value'] != $formattedCost);
			$frequencyChanged = (
				$existing['data']['interval_unit'] != $interval_unit OR
				$existing['data']['interval_count'] != $plan->duration
			);
			$this->log(
				"subscriptionid {$plan->subscriptionid} subid {$plan->subscriptionsubid} currency {$plan->currency} was modified:"
				. ($productidChanged ? ("\nProductid: " . $existing['data']['product_id'] . " -> " . $paypalProductid) : "")
				. ($costChanged ? ("\Cost: " . $existing['data']['value'] . " -> " . $formattedCost) : "")
				. ($frequencyChanged ? ("\Frequency: " . $existing['data']['interval_count'] . $existing['data']['interval_unit'] . " -> " . $plan->duration . $interval_unit) : "")
			);
			if ($productidChanged OR $frequencyChanged)
			{
				$this->log("Replacing existing paypal subscription plan (productidChanged: $productidChanged, costChanged; $costChanged, frequencyChanged; $frequencyChanged)");
				// Easiest to just delete this and run the above empty condition.
				// Note, this means that if we ever have to refer back to the old paypal-planid for some reason, we won't recognize what it is.
				// Even for old/existing subscriptions under that plan, I don't *think* that will be an issue, because for individual subscriptions,
				// we keep track the paypal-subscriptionid against the `paymentinfo`.`hash`, so when we get a webhook, we should be able to find the
				// subscription.
				// HOWEVER, the pricing validation will likely fail, and if frequency changed, the vb-sub vs payments will be out of sync. That is
				// a pre-existing problem (VB6-448) for all payment APIs, not just this one.
				$assertor->delete('vBForum:paymentapi_remote_catalog', $keyCondition);
				return $this->ensurePaypalSubscriptionPlan($paypalProductid, $plan, $subscriptionApiOptions);
			}
			// For cost changes, there's a different API for it.
			else if ($costChanged)
			{
				// Note, you can also override the pricing and taxes when creating a subscription to this plan,
				// but NOT the frequency!!

				// https://developer.paypal.com/docs/api/subscriptions/v1/#plans_update-pricing-schemes
				['body' => $response, 'headers' => $headers,] = $this->callApi(
					'/v1/billing/plans/' . $existing['remotecatalogid'] . '/update-pricing-schemes',
					[
						'pricing_schemes' => [
							[
								'billing_cycle_sequence' => 1,
								'pricing_scheme' => $pricing_scheme,
							],
						],
					],
					[],
				);
				// Successful response is 204 No Content status with no JSON body...
				if ($headers['http-response']['statuscode'] == 204)
				{
					$this->log("Pricing for PayPal subscription plan {$existing['remotecatalogid']} updated successfully.");
					$runUpdate = true;
				}
				else
				{
					$this->log("Pricing for PayPal subscription plan {$existing['remotecatalogid']} failed to update:" . print_r($response, true));
				}
			}
			else
			{
				// otherwise, we're pretty limited in what we can change.. but we can patch description & name
				// https://developer.paypal.com/docs/api/subscriptions/v1/#plans_patch
				['body' => $response, 'headers' => $headers,] = $this->callApi(
					'/v1/billing/plans/' . $existing['remotecatalogid'],
					[
						// apparently we can NOT change inclusive vs exclusive, which takes us only half way there...
						// so we're going to always override this when we create a subscription under this plan, instead of
						// trying to keep track of this.
						// [
						// 	'op' => 'replace',
						// 	'path' => '/taxes/percentage',
						// 	'value' => $data['taxes']['percentage'],
						// ],
						[
							'op' => 'replace',
							'path' => '/description',
							'value' => $description,
						],
						[
							'op' => 'replace',
							'path' => '/name',
							'value' => $title,
						],
					],
					[
						'Prefer' => 'Prefer: return=minimal',
					],
					'PATCH'
				);
				// Successful response is 204 No Content status with no JSON body...
				if ($headers['http-response']['statuscode'] == 204)
				{
					$this->log("Updated PayPal plan {$existing['remotecatalogid']} successfully");
					$runUpdate = true;
				}
				else
				{
					$this->log("Failed to update PayPal plan {$existing['remotecatalogid']}: " . print_r($response, true));
				}
			}

			if ($runUpdate)
			{
				$assertor->update('vBForum:paymentapi_remote_catalog',
				[
					'data' => json_encode([
						'product_id' => $response['product_id'] ?? '',
						'taxes' => $data['taxes'] ?? [],
						// cost
						'currency_code' => $plan->currency,
						'value' => $formattedCost,
						// frequency
						'interval_unit' => $interval_unit,
						'interval_count' => $plan->duration,
						// quick diff
						'hash' => $hash,
					]),
				],
				$keyCondition
			);
			}
		}
		else
		{
			$this->log("subscriptionid {$plan->subscriptionid} subid {$plan->subscriptionsubid} currency {$plan->currency} was not changed.");
		}

		return $existing['remotecatalogid'];
	}

	private function ensurePaypalProduct($vbsubscriptionid) : string
	{
		$vbsubscription_subid = 0;
		$catalogType = 'product';
		$currency = '#ALL';
		$existing = $this->getRemoteCatalogItem($vbsubscriptionid, $vbsubscription_subid, $catalogType, $currency);
		['title' => $title, 'desc' => $description,] = $this->fetchSubscriptionTitleAndDescription($vbsubscriptionid);

		$productData = [
			// id / SKU will be autogenerated if we don't specify it. No reason to make this complicated on our end.
			// if we DO want to use our own ID, we need to ensure 6-30 length requirement & uniqueness...
			// 'id' => $vbsubscriptionid . '_' . $vbsubscription_subid,

			// required
			'name' => $title,
			// also required. PHYSICAL|DIGITAL|SERVICE. For now, going with SERVICE but this might need to be configurable...
			// https://developer.paypal.com/docs/api/catalog-products/v1/#products_create!path=type&t=request
			'type' => 'SERVICE',

			// Not required
			'description' => $description,
			// 'category' => '...',
			// 'image_url' => '...',
			// 'home_url' => '...',
		];
		// If description is empty, PayPal will throw an error.
		if (empty($productData['description']))
		{
			unset($productData['description']);
		}

		// So not every attribute can be edited, for some reason. Furthermore, catalog products can NOT be
		// deleted (probably because managing existing subscriptions on a to-be-deleted product is going
		// to be complicated).
		// Although it's annoying that we can't keep the names in sync, it does not seem worthwhile to always
		// add new products and bloat the catalog whenever the subscription gets renamed in vB. So let's just
		// keep the existing product on paypal's side. Most of the details are on the "plan" end which is under
		// products anyways.
		// For the hash, only keep track of the bits we CAN edit so that we don't constantly try to update and
		// fail.
		$editables = $productData;
		// Only the following attributes/objects can be patched: description, category, image_url, home_url
		// See https://developer.paypal.com/docs/api/catalog-products/v1/#products_patch
		unset($editables['name']);
		// This is for our own records
		$hash = md5(json_encode($productData));

		$assertor = vB::getDbAssertor();
		if (empty($existing))
		{
			// https://developer.paypal.com/docs/subscriptions/integrate/#link-createproduct
			['body' => $response, 'headers' => $headers,] = $this->callApi(
				'/v1/catalogs/products',
				$productData,
				[
					'Prefer' => 'Prefer: return=minimal',
				]
			);

			if (!empty($response['id']))
			{
				$assertor->insert('vBForum:paymentapi_remote_catalog', [
					'paymentapiid' => $this->paymentapirecord['paymentapiid'],
					'vbsubscriptionid' => $vbsubscriptionid,
					'vbsubscription_subid' => $vbsubscription_subid,
					'type' => $catalogType,
					'currency' => $currency,
					'remotecatalogid' => $response['id'],
					'data' => json_encode([
						'hash' => $hash,
					]),
					'active' => 1,
				]);
				$this->log("Created recurring subscription product in PayPal Catalog for subscriptionid $vbsubscriptionid: " . $response['id']);

				return $response['id'];
			}
			else
			{
				$this->log("Failed to create recurring subscription product in PayPal Catalog. response was: " . print_r($response, true));

				return null;
			}
		}
		else if ($existing['data']['hash'] != $hash)
		{
			$this->log("Attempting to PATCH PayPal Catalog for subscriptionid $vbsubscriptionid");
			// https://developer.paypal.com/docs/subscriptions/integrate/#link-createproduct
			['body' => $response, 'headers' => $headers,] = $this->callApi(
				'/v1/catalogs/products/' . $existing['remotecatalogid'],
				[
					// apparently, we cannot rename existing products.. for now just keep the old name
					// rather than create a whole new one...
					// [
					// 	'op' => 'replace',
					// 	'path' => '/name',
					// 	'value' => $title,
					// ],
					// Not needed until we make this configurable
					// [
					// 	'op' => 'replace',
					// 	'path' => '/type',
					// 	'value' => 'SERVICE',
					// ],
					[
						// 'op' => 'add' is safer than 'replace' because if description is missing, 'replace' will throw an error,
						// but 'add' will EITHER add or replace the param.
						'op' => 'add',
						'path' => '/description',
						'value' => $description,
					],
				],
				[
					'Prefer' => 'Prefer: return=minimal',
				],
				'PATCH'
			);
			// Successful response is 204 No Content status with no JSON body...
			if ($headers['http-response']['statuscode'] == 204)
			{
				$assertor->update('vBForum:paymentapi_remote_catalog',
					[
						'data' => json_encode([
							'hash' => $hash,
						]),
					],
					[
						'paymentapiid' => $this->paymentapirecord['paymentapiid'],
						'vbsubscriptionid' => $vbsubscriptionid,
						'vbsubscription_subid' => $vbsubscription_subid,
						'type' => $catalogType,
						'currency' => $currency,
						'remotecatalogid' => $existing['remotecatalogid'],
					]
				);
			}
		}
		else
		{
			$this->log("Subscriptionid $vbsubscriptionid unchanged, remotecatalog was not modified");
		}

		return $existing['remotecatalogid'];
	}

	private function getPaypalCurrencies() : array
	{
		// https://developer.paypal.com/docs/reports/reference/paypal-supported-currencies/
		return [
			'AUD',
			'BRL',
			'CAD',
			'CNY',
			'CZK',
			'DKK',
			'EUR',
			'HKD',
			'HUF',
			'ILS',
			'JPY',
			'MYR',
			'MXN',
			'TWD',
			'NZD',
			'NOK',
			'PHP',
			'PLN',
			'GBP',
			'SGD',
			'SEK',
			'CHF',
			'THB',
			'USD',
		];
	}

	// private function handleSquareApiError($api_response)
	// {
	// 	if (!$api_response->isSuccess())
	// 	{
	// 		$errors = $api_response->getErrors();
	// 		$error = reset($errors);
	// 		$this->log($error->getDetail());
	// 		throw new Exception($error->getCode());
	// 	}
	// }

	private function getClientID() : string
	{
		return $this->settings['client_id'] ?? '';
	}

	private function getClientSecret() : string
	{
		return $this->settings['secret_key'] ?? '';
	}

	// todo: delete webhook_url?
	private function getWebhookId()
	{
		return $this->settings['webhook_id'] ?? '';
	}

	private function getAccessToken() : array
	{
		// This v1 is correct. This endpoint (& a few others) only has a v1 at the time of writing.
		$posttarget = $this->paypal_url . '/v1/oauth2/token';

		//https://developer.paypal.com/api/rest/#link-curl
		$httpHeaders = [
			'Content-Type: application/x-www-form-urlencoded',
		];
		$postdata = [
			'grant_type' => 'client_credentials',
		];

		// Appaerntly, CURLOPT_USERPWD will base64 encode for us, so don't encode here.
		$challenge = $this->getClientID() . ':' . $this->getClientSecret();

		$vurl = vB::getUrlLoader();
		$vurl->setOption(vB_Utility_Url::HTTPHEADER, $httpHeaders);
		$vurl->setOption(vB_Utility_Url::USERPWD, $challenge);
		$vurl->setOption(vB_Utility_Url::TIMEOUT, 5);
		//note that sending using an array instead of a string automatically uses form/multipart
		//not sure how strict paypal is, but going with the documented form-urlencoded instead.
		$result = $vurl->post($posttarget, http_build_query($postdata));

		if ($result['body'])
		{
			/*
			$response should usually have
				scope
				access_token
				token_type
				app_id
				expires_in
				nonce
			*/
			$response = json_decode($result['body'], true);
			//$this->log($response);
			return $response;
		}

		return [
			'access_token' => '',
		];
	}

	private function callApi(string $apiEndpoint, array $data, array $headerParams = [], string $requestType = 'POST', string $accessToken = '') : array
	{
		$posttarget = $this->paypal_url . $apiEndpoint;

		if (!$accessToken)
		{
			['access_token' => $accessToken,] = $this->getAccessToken();
		}

		//https://developer.paypal.com/api/rest/#link-curl
		$httpHeaders = [
			'Content-Type' => 'Content-Type: application/json',
			'Authorization' => 'Authorization: Bearer ' . $accessToken,
		];
		if ($headerParams)
		{
			$httpHeaders = array_values(array_merge($httpHeaders, $headerParams));
		}

		if (!empty($data))
		{
			$postdata = json_encode($data);
		}
		else
		{
			// Sending a json-encoded empty array with contenttype application/json gets rejected by paypal with: MALFORMED_REQUEST_JSON.
			$postdata = [];
			// "If value[CURLOPT_POSTFIELDS] is an array, the Content-Type header will be set to multipart/form-data."
			// As such, that'll will cause the above 'Content-Type' header to be ignored!!
		}

		$vurl = vB::getUrlLoader();
		$vurl->setOption(vB_Utility_Url::HEADER, 1);
		$vurl->setOption(vB_Utility_Url::HTTPHEADER, $httpHeaders);
		$vurl->setOption(vB_Utility_Url::TIMEOUT, 5);
		if ($requestType == 'POST')
		{
			$result = $vurl->post($posttarget, $postdata);
		}
		else if ($requestType == 'GET')
		{
			// Extremely rudimentary... probably needs to check of ? already exists in URL and append with & instead, but not needed ATM
			$postdata = [];
			$posttarget .= '?' . http_build_query($data);
			$result = $vurl->get($posttarget);
		}
		else
		{
			// e.g. PATCH request.
			if ($postdata)
			{
				$result = $vurl->customRequest($posttarget, $requestType, $postdata);
			}
			else
			{
				$result = $vurl->customRequest($posttarget, $requestType);
			}
		}

		if ($result['body'])
		{
			$result['body'] = json_decode($result['body'], true);
		}

		$this->log([
			'httpHeaders' => $httpHeaders,
			'requestType' => $requestType,
			'postdata' => $postdata,
			'posttarget' => $posttarget,
			'result' => $result,
		]);

		return $result;
	}

	// private function getSubscriptionCostInfoPhrased()
	// {
	// 	// Copied from vB_Api_Paidsubscription::fetchAll(), todo: need to refactor..
	// 	$phraseApi = vB_Api::instanceInternal('phrase');

	// 	$vbphrase = $phraseApi->fetch([
	// 		'day',
	// 		'week',
	// 		'month',
	// 		'year',
	// 		'days',
	// 		'weeks',
	// 		'months',
	// 		'years',
	// 		'length_x_units_y_recurring_z',
	// 		'recurring'
	// 	]);

	// 	$lengths = [
	// 		'D' => $vbphrase['day'],
	// 		'W' => $vbphrase['week'],
	// 		'M' => $vbphrase['month'],
	// 		'Y' => $vbphrase['year'],
	// 		// plural stuff below
	// 		'Ds' => $vbphrase['days'],
	// 		'Ws' => $vbphrase['weeks'],
	// 		'Ms' => $vbphrase['months'],
	// 		'Ys' => $vbphrase['years']
	// 	];

	// 	if ($currentsub['length'] == 1)
	// 	{
	// 		$currentsub['units'] = $lengths["{$currentsub['units']}"];
	// 	}
	// 	else
	// 	{
	// 		$currentsub['units'] = $lengths[$currentsub['units'] . 's'];
	// 	}


	// 	$phrases = $phraseApi->renderPhrases(array('length' => array(
	// 		'length_x_units_y_recurring_z',
	// 		$currentsub['length'],
	// 		$currentsub['units'],
	// 		($currentsub['recurring'] ? " ($vbphrase[recurring])" : '')
	// 	)));

	// }

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

		['access_token' => $accessToken] = $this->getAccessToken();
		if (!$accessToken)
		{
			throw new vB_Exception_Api('payment_api_error');
		}

		$recurring = $timeinfo['recurring'];
		$currencyUpper = strtoupper($currency);

		$escapedToken = htmlentities($accessToken);
		$escapedHash = htmlentities($hash);
		$escapedCost = htmlentities($cost);
		$escapedCurrency = htmlentities($currencyUpper);
		$escapedRecurring = $recurring ? 1 : 0;
		$paypalSubscriptionid = '';
		if ($recurring)
		{
			$paypalSubscriptionid = $this->createSubscription($hash, $cost, $currency, $subinfo, $userinfo, $timeinfo);
		}
		$escapedId = uniqid('pp2_');
		$escapedContext = htmlentities($extra['context'] ?? 'usersettings');

		// Since we need to be able to load different versions (currency, recurring vs onetime) of the JS within a single page load if the
		// user clicks "cancel" then selects another pricing, all possible JS permutations are specified in fetchPaypalJsArray(), and set via
		// the paymentapi_data template.
		// Re-selecting the same pricing should work similarly, but not have to reload the JS that's already loaded.
		// might need to filter the currency codes: https://developer.paypal.com/api/rest/reference/currency-codes/
		// $paypalJS = "https://www.paypal.com/sdk/js?client-id=" . $this->getClientID() . "&components=buttons&currency=" . $currencyUpper;
		// if ($recurring)
		// {
		// 	// https://developer.paypal.com/docs/subscriptions/integrate/
		// 	$paypalJS .= '&vault=true&intent=subscription';
		// 	$paypalSubscriptionid = $this->createSubscription($hash, $cost, $currency, $subinfo, $userinfo, $timeinfo);
		// }
		// if ($this->sandbox)
		// {
		// 	$paypalJS .= "&debug=true";
		// }
		// $escapedPaypalJSUrl = htmlentities($paypalJS);
		$escapedPaypalSubscriptionid = htmlentities($paypalSubscriptionid);

		// TODO: Show the cost, currency & recurring info to user...

		$form = [];
		// docs: https://braintree.github.io/braintree-web-drop-in/docs/current/module-braintree-web-drop-in.html
		// TODO: do we want to push this to a sub template? see class_2checkout for example...
		$form['hiddenfields'] = <<<EOHTML
<!-- PayPal buttons container as well as data needed for paypal2.init-->
<div id="$escapedId" class="js-paypal2-btn-container"
	data-currency="$escapedCurrency"
	data-recurring="$escapedRecurring"
	data-context="$escapedContext"
	data-subscriptionid="$escapedPaypalSubscriptionid"
	></div>

<!-- Data needed for paypal2.init, specifically for prepareOrder() -->
<input type="hidden" name="hash" value="$escapedHash" />
<input type="hidden" name="paymentapiclass" value="paypal2" />
<input type="hidden" name="cost" value="$escapedCost" />
<input type="hidden" name="currency" value="$currency" />
EOHTML;

		$form['action'] = '#';
		$form['method'] = 'post';

		return $form;
	}

	private function createSubscription($hash, $cost, $currency, $subinfo, $userinfo, $timeinfo) : string
	{
		// This isn't exactly documented as far as I could find, but this mirrors how the "createOrder" flow works --
		// we create the final whatever-id required and return that in the JS init. This is the only way to
		// "safely" override the plan information, e.g. cost & taxes (but unfortunately not frequency)


		// This is a bit stupid, but for the life of me I don't think the "subsubid" is passed to generate_form_html()...
		// and if it is, I don't know where the hell it is because the params are confusingly nested arrays
		$assertor = vB::getDbAssertor();
		$paymentinfo = $assertor->getRow('vBForum:paymentinfo', ['hash' => $hash]);
		// this shouldn't normally happen, because this method gets called shortly after above record is generated...
		// but things below will break without some requisite info.
		if (empty($paymentinfo))
		{
			$this->log("Failed to find `paymentinfo` record for hash = \"$hash\"");
			throw new vB_Exception_Api('payment_api_error');
		}
		$currencyUpper = strtoupper($currency);

		$check = $this->getRemoteCatalogItem($paymentinfo['subscriptionid'], $paymentinfo['subscriptionsubid'], 'plan', $currencyUpper);
		if (empty($check['remotecatalogid']))
		{
			$this->log("Missing remote catalog item for Sub {$paymentinfo['subscriptionid']}.{$paymentinfo['subscriptionsubid']} with currency $currencyUpper");
			throw new vB_Exception_Api('payment_api_error');
		}
		$planid = $check['remotecatalogid'];

		// Note, prices can be overridden, but that should be reflected in the plan updates in ensurePaypalSubscriptionPlan()
		// so let's not worry about it here.
		// $pricing_scheme = [
		// 	'fixed_price' => [
		// 		'currency_code' => $currencyUpper,
		// 		'value' => $this->formatCost($cost, $currencyUpper),
		// 	],
		// ];

		$frontendurl = vB::getDatastore()->getOption('frontendurl');
		// This is "required" in order to remove the shipping info, but it's unknow how the paypal JS SDK will use this, because
		// it's a pop-up initiated from an embedded iframe and it doesn't actually navigate away...
		// Just to be safe let's set it to return to the same page.
		$return_url = $frontendurl . '/settings/subscriptions';
		$cancel_url = $frontendurl . '/settings/subscriptions';

		// The taxes, however, needs to be handled here, because we didn't save that data in the plan
		/*
		show = bool
		tax = bool
		tax_percentage = 1.23
		tax_behavior = inclusive | exclusive
		*/
		$data = [
			'plan_id' => $planid,
			'quantity' => 1,
			// This is needed to reconcile refunds later in verify_payment
			'custom_id' => $hash,
			'application_context' => [
				'shipping_preference' => 'NO_SHIPPING',
				'user_action' => 'SUBSCRIBE_NOW',
				// These are marked as "required", but some testing indicates that they may not actually be...
				// it's unknown why this is required or how it'll be used since the JS SDK does a popup & if you
				// "cancel", you're just closing the pop-up, and we don't really want the user to move away from
				// where they were before (where the paypal buttons are rendered) in case they want to try again,
				// but just to be safe, setting them.
				// Edit: looks like, at least for the ORDER API, these are used for "3DS" approval, but in the
				// experience_context for each payment_source instead of application_context...
				'return_url' => $return_url,
				'cancel_url' => $cancel_url,
			],
			// 'plan' => [
			// 	// 'billing_cycles' => [
			// 	// 	[
			// 	// 		'sequence' => 1,
			// 	// 		// 0 for infinite.
			// 	// 		'total_cycles' => 0,
			// 	// 		'pricing_scheme' => $pricing_scheme,
			// 	// 		// Note that you apparently can NOT override the frequency.
			// 	// 	]
			// 	// ],
			// ],
		];

		$subscriptionSettings = $subinfo['newoptions']['api']['paypal2'];
		if ($subscriptionSettings['tax'])
		{
			$taxPercent = $subscriptionSettings['tax_percentage'];
			if (!is_numeric($taxPercent) OR $taxPercent < 0 OR $taxPercent > 100)
			{
				$taxPercent = 0;
			}
			$data['plan'] ??= [];
			$data['plan']['taxes'] = [
				'inclusive' => ($subscriptionSettings['tax_behavior'] == 'inclusive'),
				'percentage' => $taxPercent,
			];
		}

		['body' => $response, 'headers' => $headers,] = $this->callApi(
			'/v1/billing/subscriptions',
			$data,
			[
				'Prefer' => 'Prefer: return=minimal',
			]
		);

		if ($response['id'])
		{
			// It doesn't seem like paypal allows us to set much meta info at all, so we have to keep track of this mapping...
			// Hypothetically we could set something to "custom_id" but it's uncertain if that data is sent back in the webhooks..
			// Note, this is important as this is the only way we can map the paypal webhook to `paymentinfo`:
			// webhook.resource.billing_id (== subscriptionid) = `paymentapi_remote_invoice_map`.`remotesubscriptionid`
			$this->trackSubscriptionId($hash, $response['id']);
			return $response['id'];
		}

		return '';
	}

	public function prepareOrder(vB_Entity_Paidsubscription $vbsub, array $subscriptionSettings)
	{
		$cost = $this->formatCost($vbsub->cost, $vbsub->currency);
		if ($subscriptionSettings['tax'])
		{
			$taxPercent = $subscriptionSettings['tax_percentage'];
			if (!is_numeric($taxPercent) OR $taxPercent < 0 OR $taxPercent > 100)
			{
				$taxPercent = 0;
			}
			$inclusive = ($subscriptionSettings['tax_behavior'] == 'inclusive');
			$taxFraction = $taxPercent / 100;
			if ($inclusive)
			{
				$tax = $cost / (1 + $taxFraction) * $taxFraction;
				$tax = $this->formatCost($tax, $vbsub->currency);
				$subtotal = $cost - $tax;
			}
			else
			{
				$tax = $cost * $taxFraction;
				$tax = $this->formatCost($tax, $vbsub->currency);
				$subtotal = $cost;
				$cost = $subtotal + $tax;
			}
			$unit_amount = [
				'currency_code' => $vbsub->currency,
				'value' => $cost,
				'breakdown' => [
					'item_total' => [
						'currency_code' => $vbsub->currency,
						'value' => $subtotal,
					],
					'tax_total' => [
						'currency_code' => $vbsub->currency,
						'value' => $tax,
					],
				],
			];
		}
		else
		{
			$unit_amount = [
				"currency_code" => $vbsub->currency,
				"value" => $cost,
			];
		}


		['body' => $response, ] = $this->callApi(
			'/v2/checkout/orders',
			[
				'purchase_units' => [
					[
						// This is needed to reconcile refunds later in verify_payment
						'custom_id' => $vbsub->vbhash,
						'amount' => $unit_amount,
						// 'items' => [
						// 	[
						// 		'name' => $subscriptionTitle,
						// 		'unit_amount' => $unit_amount,
						// 		//'sku' => '...',
						// 	]
						// ],
					]
				],
				'intent' => 'CAPTURE',
				// 'application_context' => [
				// 	'shipping_preference' => 'NO_SHIPPING',
				// ],

			],
			[
				'Prefer' => 'Prefer: return=minimal',
			]
		);

		if (empty($response['id']))
		{
			$this->log($response);
		}
		else
		{
			$this->trackOrderId($vbsub->vbhash, $response['id'], $vbsub->recurring);
		}



		return $response;
	}

	// These are duplicated from class_square. Left apart for now in case we have paypal2 specific logic, but we may want to consolidate
	// into the parent class.
	private function trackOrderId($vbhash, $orderid, $recurring) : void
	{
		// Paypal's v1 API seemed to have metadata (https://developer.paypal.com/docs/api/orders/v1/#definition-metadata)
		// v2 does NOT seem to have it, and furthermore it's unclear what could be stored in metadata and what will be
		// returned back.

		// We track the orderid so we can map back to the vb-subscription when we receive
		// an invoice or payment webhook later, because it seems like the only custom metadata
		// we can set is a string custom_id, & reference_id, requiring us to store more detailed
		// data on our own end.
		$assertor = vB::getDbAssertor();
		$assertor->insertIgnore('vBForum:paymentapi_remote_orderid', [
			'paymentapiid' => $this->paymentapirecord['paymentapiid'],
			'hash' => $vbhash,
			'remoteorderid' => $orderid,
			'recurring' => $recurring,
		]);
	}

	private function trackSubscriptionId($vbhash, $subscription_id) : void
	{
		$params = [
			'hash' => $vbhash,
			'paymentapiid' => $this->paymentapirecord['paymentapiid'],
			'remotesubscriptionid' => $subscription_id,
		];
		$this->log($params);
		$assertor = vB::getDbAssertor();
		$assertor->insert('vBForum:paymentapi_remote_invoice_map', $params);
	}

	private function getPaymentInfoBySubscriptionId($subscription_id) : array
	{
		$assertor = vB::getDbAssertor();
		$paymentinfo = $assertor->getRow('getPaymentinfoByRemoteSubscriptionId', [
			'paymentapiid' => $this->paymentapirecord['paymentapiid'],
			'subscription_id' => $subscription_id,
		]);

		return $paymentinfo;
	}

	private function getPaymentInfoByOrderId($order_id) : array
	{
		$assertor = vB::getDbAssertor();
		$paymentinfo = $assertor->getRow('getPaymentinfoByRemoteOrderID', [
			'paymentapiid' => $this->paymentapirecord['paymentapiid'],
			'orderid' => $order_id,
		]);

		if (empty($paymentinfo))
		{
			$this->log("Failed to fetch getPaymentinfoByRemoteOrderID query for `paymentapiid` = \"{$this->paymentapirecord['paymentapiid']}\ and `orderid` = \"$order_id\"");
		}

		return $paymentinfo;
	}

	private function getTotalPayAmount($paypalApiResponse) : array
	{
		// So this handles edge cases where we have multiple payments in different currencies
		// and refunds at the same time, but in reality that extreme case is untested / unknown.
		// This is just following their API specs, but it's not known if customers can split up
		// payments like this and how exactly we should handle it if they use multiple currencies...
		$amountsPerCurrency = [];
		foreach ($paypalApiResponse['purchase_units'] ?? [] AS $__unit)
		{
			foreach ($__unit['payments']['captures'] ?? [] AS $__capture)
			{
				if ($__capture['status'] == 'COMPLETED')
				{
					$__cur = $__capture['amount']['currency_code'];
					$__val = $__capture['amount']['value'];
					$amountsPerCurrency[$__cur] ??= 0;
					$amountsPerCurrency[$__cur] += $__val;
				}
			}

			foreach ($__unit['payments']['refunds'] ?? [] AS $__refund)
			{
				if ($__refund['status'] == 'COMPLETED')
				{
					$__cur = $__refund['amount']['currency_code'];
					$__val = $__refund['amount']['value'];
					$amountsPerCurrency[$__cur] ??= 0;
					$amountsPerCurrency[$__cur] -= $__val;
				}
			}
		}

		return $amountsPerCurrency;
	}

	public function completeSubscription(string $subscriptionid, string $orderid)
	{
		// Note, the below works, but we have a duplication + race condition problem because
		// the first payment also generates a webhook that looks just like any subsequent recurring payment.
		// So far, their API documentation for the webhook is pretty sparse and does not provide detailed
		// information about each API type. Looking at the webhook payload from the sandbox however, it
		// does NOT seem like the payload contains any information about which payment "sequence" it is.
		// Even when pulling the specific transaction associated with that webhook, it does not have information
		// to help us figure out which payment # it is. (e.g. /v1/payments/sale/:webhook.resource.id ,
		// /v2/payments/captures/:webhook.resource.id)
		// One possible way to try to figure out the pay sequence is to get the subscription transactions
		// (/v1/billing/subscriptions/:webhook.resource.billing_agreement_id/transactions), map the newly
		// received transaction, and count it, but that could have potential performance implications as
		// the subscription goes on (since we'd be pulling more and more transactions), and it's not reliable
		// since paypal may force some cutoff (they do in other APIs) requiring us to paginate multiple times
		// to do a full "count" (we could cache pulled data locally but that would add complexity).
		// Another way to "guess" at if a webhook is the first payment or not is to pull the subscription
		// info (/v1/billing/subscriptions/:webhook.resource.billing_agreement_id) and check
		// cycle_execution.0.cycle_completed, but that kind of heuristic is not going to be reliable and
		// require a lot of complexity since webhooks do not have any guarantee of coming in order, and
		// you can also potentially send dupe webhooks via the dashboard, etc.

		// Edit: New record on sandbox is a 28 minute delay between payment and the associated webhook.
		// A near 30m delay may not be something we want to live with, ESPECIALLY when a forum user is
		// paying for the first time to start up a subscription -- usually when a customer pays for subs,
		// they want it to start RIGHT NOW not 30 minutes later.
		// Hopefully this is just a sandbox issue and live webhooks are much faster. If we get reports
		// in the wild of otherwise however, we may want to re-think this.

		$paymentinfo = $this->getPaymentInfoBySubscriptionId($subscriptionid);
		if (!$paymentinfo['hash'])
		{
			$this->log("Failed to find `paymentapi_remote_invoice_map` record for subscriptionid $subscriptionid (orderid: $orderid)");
			throw new vB_Exception_Api('payment_api_error');
		}
		['body' => $response, ] = $this->callApi(
			'/v1/billing/subscriptions/' . $subscriptionid,
			[],
			[
				'Prefer' => 'Prefer: return=minimal',
			],
			'GET'
		);

		// This is needed for auto-cancelling the sub later.
		$this->recordRemoteSubscription(
			$paymentinfo['userid'],
			$paymentinfo['subscriptionid'],
			$subscriptionid,
		);

		// I think until Paypal adds more data to the webhooks, the most reliable method will be to ignore this
		// event, and just wait for the webhook. While that means a potential several~dozens minutes delay
		// (if sandbox performance is anything to go by), I just don't think the additional complexity required
		// to handle this AND the "first" webhook is worthwhile.

		return $response;


		// $status = $response['status'] ?? '';
		// if ($status == 'APPROVED' OR $status == 'ACTIVE')
		// {
		// 	// This isn't really needed, because the webhook data maps to the subscriptionid, which was already tracked when we first set up the
		// 	// payment form.
		// 	//$this->trackOrderId($paymentinfo['hash'], $orderid, 1);

		// 	// TODO: I think paypal will also send a webhook for this. do we need to / how do we de-dupe THIS activation vs the webhook handling?

		// 	// TODO: test that these properties are correct if we want to support immediate enable & have a fix for the dupe sub issue per top of method.
		// 	$amount = $response['billing_info']['last_payment']['amount'];
		// 	$currency = $amount['currency_code'];
		// 	$value = $amount['value'];

		// 	// This replicates the payment_gateway.php action
		// 	$trans = [
		// 		'transactionid' => $orderid,
		// 		'paymentinfoid' => $paymentinfo['paymentinfoid'],
		// 		'amount'        => $value,
		// 		'currency'      => $currency,
		// 		'state'         => self::TXN_TYPE_PAYMENT,
		// 		'dateline'      => vB::getRequest()->getTimeNow(),
		// 		'paymentapiid'  => $this->paymentapirecord['paymentapiid'],
		// 	];
		// 	$log = [
		// 		'note' => 'initial payment for recurring subscription (NOT webhook)',
		// 		'subscriptionid' => $subscriptionid,
		// 	];
		// 	$trans['request'] = serialize($log);

		// 	vB::getDbAssertor()->insert('vBForum:paymenttransaction', $trans);
		// 	$subobj = new vB_PaidSubscription();
		// 	$subobj->build_user_subscription($paymentinfo['subscriptionid'], $paymentinfo['subscriptionsubid'], $paymentinfo['userid']);

		// 	// TODO: Need to duplicate the email action that payment_gateway.php performs
		// }


		// return $response;
	}

	public function completeOrder(string $orderid)
	{
		$paymentinfo = $this->getPaymentInfoByOrderId($orderid);

		['body' => $response, ] = $this->callApi(
			'/v2/checkout/orders/' . $orderid . '/capture',
			[],
			[
				'Prefer' => 'Prefer: return=minimal',
			]
		);

		//$this->log($response);

		$status = $response['status'] ?? '';
		if ($status == 'COMPLETED')
		{
			$amounts = $this->getTotalPayAmount($response);
			if (count($amounts) > 1)
			{
				$this->log("Multiple payment currencies detected.. only recording the first in `paymenttransaction` record: " . print_r($amounts, true));
			}
			$currency = array_key_first($amounts) ?? 'Unknown';
			$value = $amounts[$currency] ?? 0;
			$trans = [
				'transactionid' => $orderid,
				'paymentinfoid' => $paymentinfo['paymentinfoid'],
				'amount'        => $value,
				'currency'      => $currency,
				'state'         => self::TXN_TYPE_PAYMENT,
				'dateline'      => vB::getRequest()->getTimeNow(),
				'paymentapiid'  => $this->paymentapirecord['paymentapiid'],
			];

			vB::getDbAssertor()->insert('vBForum:paymenttransaction', $trans);
			$subobj = new vB_PaidSubscription();
			$subobj->build_user_subscription($paymentinfo['subscriptionid'], $paymentinfo['subscriptionsubid'], $paymentinfo['userid']);
		}

		return $response;
	}


	private function getOurWebhookEndpoint()
	{
		// Allow a redirector for the webhook, e.g. ngrok, zapier.
		$config = vB::getConfig();
		if (!empty($config['Misc']['paymentapi']['paypal2']['webhookurl']))
		{
			return $config['Misc']['paymentapi']['paypal2']['webhookurl'];
		}

		$frontendurl = vB::getDatastore()->getOption('frontendurl');
		$listenerurl = $frontendurl . '/core/payment_gateway.php?method=paypal2';
		return $listenerurl;
	}

}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 109629 $
|| #######################################################################
\*=========================================================================*/
