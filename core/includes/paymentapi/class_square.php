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
class vB_PaidSubscriptionMethod_square extends vB_PaidSubscriptionMethod
{
	private $initialized = NULL;
	private $init_fail_reason = '';
	private $debug = false;
	private $sandbox = false;
	private $environment = 'sandbox';
	private $defaultClientParams = [];

	// True if we should do ad hoc pricing instead of remote catalog items for non-recurring subscriptions.
	// Due to Square API limitations, recurring subscriptions will always be catalogued.
	private $onlyCatalogRecurringSubs = true;

	// Used for storing the message body for logging errors.
	private $payload;
	private $additional_log = [];

	private $payment_is_recurring = false;

	/**
	 * Unlike Stripe, Square SDK seems to be matched against a specific API version internally:
	 * https://developer.squareup.com/docs/build-basics/versioning-overview
	 * "Each Square SDK version is created for a specific API version.
	 * To use a newer Square API version, you must upgrade the SDK to work with new API versions."
	 * As such we don't have to set anything to avoid backwards imcompatible changes. This is
	 * here just as a reference for now, but may be removed in the future.
	 *
	 * @var string
	 */
	private $square_api_version = '19.1.1.20220616';

	private function initSDK()
	{
		if (!is_null($this->initialized))
		{
			return $this->initialized;
		}

		/*
		Square SDK requires curl, json & mbstring, as well as unirest-php & jsonmapper (latter two are included in
		core/libraries/square via autoload.php)
		*/
		$dependencies = [
			'curl',
			'json',
			'mbstring',
		];
		foreach ($dependencies AS $__dep)
		{
			if (!extension_loaded($__dep))
			{
				$this->init_fail_reason = 'Dependency missing: ' . $__dep;
				$this->log($this->init_fail_reason);
				$this->handleException(new Exception($this->init_fail_reason));
				$this->initialized = false;
				return false;
			}
		}

		try
		{
			require_once(DIR . '/libraries/square/autoload.php');
		}
		catch (Exception $e)
		{
			$this->init_fail_reason = 'Square SDK failed to initialize';
			$this->log($this->init_fail_reason);
			$this->log($e->getMessage());
			$this->handleException($e);
			$this->initialized = false;
			return false;
		}


		if (!empty($this->settings['sandbox']))
		{
			$this->sandbox = true;
			$this->environment = Square\Environment::SANDBOX;
		}
		else
		{
			$this->environment = Square\Environment::PRODUCTION;
		}

		// Set up the default square client instance configs.
		$this->defaultClientParams = [
			'accessToken' => $this->getAccessToken(),
			'environment' => $this->environment,
			// If we want to verride this, note that square version takes the API version format NOT the SDK version format. See SQUARE_VERSION
			// in COnfigurationDefaults.php
			//'squareVersion' => '2022-06-16';
		];

		// Let's allow custom configurations -- may be useful on specific servers.
		// Warning: These are untested.
		$config = vB::getConfig();
		if (!empty($config['Misc']['paymentapi']['square']))
		{
			$opts = $config['Misc']['paymentapi']['square'];

			/*
			Square API currently allows these overrides:
				'timeout'
				'enableRetries'
				'numberOfRetries'
				'retryInterval'
				'backOffFactor'
				'maximumRetryWaitTime'
				'retryOnTimeout'
				'httpStatusCodesToRetry'
				'httpMethodsToRetry'
				'squareVersion'
				'additionalHeaders'
				'userAgentDetail'
				'environment'
				'customUrl'
				'accessToken'
				'httpCallback'
			 */
			foreach ($opts AS $__key => $__value)
			{
				if ($__key == 'numberOfRetries' AND $__value > 0 AND !isset($opts['numberOfRetries']))
				{
					$this->defaultClientParams['enableRetries'] = true;
				}
				$this->defaultClientParams[$__key] = $__value;
			}
		}



		$this->initialized = true;
		return true;
	}


	/**
	 * Handles SDK exceptions (expose the exception if in debug mode)
	 *
	 * @param	object	The exception
	 */
	private function handleException(Throwable $e)
	{
		$config = vB::getConfig();
		if (isset($config['Misc']['debug']) AND $config['Misc']['debug'])
		{
			throw $e;
		}
	}

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

		$this->initSDK();
	}



	/**
	 * Only call this or indicateRetry() once per process.
	 */
	private function indicateOk()
	{
		if (!headers_sent())
		{
			http_response_code(200);
			// Payment processors (Stripe, PayPal, Square) require that you return a header status as fast as possible, before
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
			// Payment processors (Stripe, PayPal, Square) will resend us the event if we send a non 200 status. As such, this
			// should only be used when we want the payment platform to RESEND the event due to malformed data, etc, NOT for
			// something we should "internally" reject.
			http_response_code(400);
			flush();
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

	private function getSignatureFromHeader()
	{
		// Expected in 'x-square-signature';
		return $_SERVER['HTTP_X_SQUARE_SIGNATURE'] ?? '';
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
		$signature = $this->getSignatureFromHeader();
		$this->additional_log = [];

		if (!$this->initialized)
		{
			$this->indicateRetry();
			$this->error_code = 'square_sdk_failure';
			return false;
		}


		$signatureCheck = $this->verifyWebhookMessage($this->payload, $signature);
		if (!$signatureCheck)
		{
			$this->indicateRetry();
			$this->error_code = 'invalid_signature';
			$this->type = self::TXN_TYPE_ERROR;
			return false;
		}

		// See https://developer.squareup.com/docs/webhooks/step2subscribe#format-of-an-event-notification
		// for data format
		$payload = json_decode($this->payload, false);
		if (!isset($payload->data))
		{
			$this->indicateRetry();
			$this->error_code = 'empty_webhook';
			//$this->log("Payload:" . print_r($payload, true));
			//$this->log("signature:" . print_r($signature, true));
			$this->type = self::TXN_TYPE_ERROR;
			return false;
		}

		$this->indicateOk();
		$data = $payload->data;
		switch ($payload->type)
		{
			// Note, it seems like when invoice payment for a subscription is made, it will invoke both invoice.payment_made
			// AND payment.updated events: https://developer.squareup.com/docs/invoices-api/overview#pay-an-invoice
			// However, it's nontrivial to try to track the subscription_id from a payment.updated webhook, but the
			// invoice provides us with a subscription_id, which is why we have to listen to both.
			// Also unknown at this point whether the FIRST payment for a recurring subscription will invoke invoice.payment_made
			// or just the payment.updated
			// NOTE: invoice.payment_made is untested due to current sandbox limitations!!!
			case 'invoice.payment_made':
				$invoice = $data->object->invoice;
				// If this is related to a subscription, it *should* have a subscription_id. If it's not a recurring sub,
				// we shouldn't be getting an invoice, as far as I'm aware.
				// Unfortunately, there's no way to check this in sandbox as apparently the checkout api does NOT create
				// subscriptions in sandbox -- https://developer.squareup.com/forums/t/subscription-not-being-created-using-checkout-api/6399/2
				// Per dev discussion, we'll have to leave this as is, and come back to it later.
				// https://developer.squareup.com/reference/square/invoices-api/webhooks/invoice.payment_made
				$subscription_id = $invoice->subscription_id ?? null;
				if (empty($subscription_id))
				{
					$this->error_code = 'invalid_subscription_id';
					$this->type = self::TXN_TYPE_ERROR;
					return false;
				}

				$order_id = $invoice->order_id;
				// Only the FIRST payment will have a matching order_id. This is also critical as it's the only time for us to
				// store the various mappings for subscription_id to our vbulletin subscriptions
				if ($this->getSetPaymentInfoByOrderId($order_id))
				{
					$this->recordRemoteSubscription(
						$this->paymentinfo['userid'],
						$this->paymentinfo['subscriptionid'],
						$subscription_id,
					);
					$this->recordInvoiceMap($this->paymentinfo['hash'], $subscription_id);
				}

				// If we don't have a subscription_id => vb-hash mapping, we have no way to continue. This might happen
				// if, due to network issues or something else, we never successfully captured the very FIRST invoice.payment_made
				// webhook.
				if (!$this->getSetPaymentInfoBySubscriptionId($subscription_id))
				{
					$this->error_code = 'invalid_subscription_id';
					$this->type = self::TXN_TYPE_ERROR;
					return false;
				}

				[
					'amount' => $amount_cents,
					'currency' => $currency,
				] = $this->fetchAmountInfoInCents($order_id);

				// These are used by payment_gateway.php's transaction logging...
				$this->paymentinfo['currency'] = $currency;
				$this->paymentinfo['amount'] = $this->convertFromCents($amount_cents, $currency);

				$expected = $this->fetchExpectedCost(
					$this->paymentinfo['subscriptionid'],
					$this->paymentinfo['subscriptionsubid'],
					$currency
				);
				if (isset($expected) AND
					$amount_cents == $this->convertToCents($expected, $currency)
				)
				{
					$this->type = self::TXN_TYPE_PAYMENT;
					return true;
				}
				else
				{
					$this->type = self::TXN_TYPE_ERROR;
					$this->error_code = 'invalid_payment_amount';
					return false;
				}
				break;
			// https://developer.squareup.com/reference/square/payments-api/webhooks/payment.updated
			case 'payment.updated':
				$payment = $data->object->payment;
				$this->transaction_id = $payment->id;
				if ($payment->status == 'COMPLETED')
				{
					$order_id = $payment->order_id;
					$knownOrderId = $this->getSetPaymentInfoByOrderId($order_id);
					// If we don't know the order_id, OR we had stored this order_id with the is_recurring flag, ignore this webhook as it's supposed to be
					// handled by the invoice webhooks.
					if (!$knownOrderId OR $this->payment_is_recurring)
					{
						// Each new payment for a subscription apparently generates a completely new order, so order_id won't map to anything for those
						// payments. We will be handling subscriptions in the future via the invoice webhooks, NOT payment webhooks, so let's ignore these and
						// not flag it as an error. We can only do the "is recurring" check on the very first payment, because the order_id associated with
						// that payment is the only one we're aware of (since the other orders are created automatically from Square's end).
						$this->type = self::TXN_TYPE_LOGONLY;
						$this->additional_log[] = "Orderid $order_id was not found. This may happen normally if this payment is associated with a recurring subscription.";
						return false;
					}

					[
						'amount' => $amount_cents,
						'currency' => $currency,
					] = $this->fetchAmountInfoInCents($order_id);

					// These are used by payment_gateway.php's transaction logging...
					$this->paymentinfo['currency'] = $currency;
					$this->paymentinfo['amount'] = $this->convertFromCents($amount_cents, $currency);

					$expected = $this->fetchExpectedCost(
						$this->paymentinfo['subscriptionid'],
						$this->paymentinfo['subscriptionsubid'],
						$currency
					);
					if (isset($expected) AND
						$amount_cents == $this->convertToCents($expected, $currency)
					)
					{
						// ATM it doesn't seem like there's a nontrivial way to fetch the subscriptionid
						// from a square payment. As such, we currently listen to both payment.updated and
						// invoice.payment_made, and use the former for "provisioning" and latter for
						// "remote subscription_id tracking". To handle race conditions, the latter also
						// checks and cancels the remote sub if the user e.g. immediately cancelled the
						// vb subscription after the first payment, etc.
						/*
						$this->recordRemoteSubscription(
							$this->paymentinfo['userid'],
							$this->paymentinfo['subscriptionid'],
							SUBSCRIPTION_ID,
						);
						*/
						// TODO: if needed, we could check $this->payment_is_recurring to see if this payment is
						// associated with a recurring subscription or is a one-time payment, and move recurring
						// subs to be handled solely by invoice.payment_made. The reason I haven't done this already
						// is because the invoice data is different from payment data (e.g. the amount & currency are
						// not standard between payments & invoices), and since we cannot generate subscriptions in
						// sandbox via the checkouts API, I can't reliably test that aspect. The reason we may want to
						// move it off to invoice.payment_made is that that way, we can be certain that the first time
						// we provision a recurring subscription on our end, we have the subscription_id stored to avoid
						// the race condition described in the invoice.payment_made handling above.

						$this->type = self::TXN_TYPE_PAYMENT;
						return true;
					}
					else
					{
						$this->type = self::TXN_TYPE_ERROR;
						$this->error_code = 'invalid_payment_amount';
						return false;
					}
				}
				else
				{
					$this->type = self::TXN_TYPE_LOGONLY;
					return false;
				}
				break;
			// https://developer.squareup.com/reference/square/webhooks/refund.updated
			case 'refund.updated':
				$refund = $data->object->refund;
				if ($refund->status == 'COMPLETED')
				{
					// There's no way to test refunds via the sandbox Dashboard (dashboard is the standard
					// way to issue refunds for a transaction). You CAN test via the sandbox API.
					// I don't know if this is an effect of the API vs Dashboard, or always the case,
					// but the refund also generates a completely new order, because why wouldn't it?
					// So rather than using the $refund->order_id which we won't recognize at all, we need to
					// fetch the old order_id associated with the particular payment.
					$payment_id = $refund->payment_id;
					$order_id = $this->fetchOrderIdFromPaymentId($payment_id);
					if (!$this->getSetPaymentInfoByOrderId($order_id))
					{
						// Still need more clarity on this, but there's a possibility that this refund is associated with
						// a recurring subscription payment (whose refund should also generate an invoice.refunded, AFAIK)
						// In that case, just log it and not error out, and wait for the invoice.refunded to handle it.
						$this->type = self::TXN_TYPE_LOGONLY;
						$this->additional_log[] = "Orderid $order_id was not found. This may happen normally if this payment is associated with a recurring subscription.";
						return false;
					}
					else
					{
						// TODO: should we verify the amount against the expected for this pricing bracket?
						// ATM none of the other payment methods do, and there's no such thing as a "partial refund" of a subscription
						// so I'm not sure what the desired action should be, if it wasn't the full refund amount...
						$this->type = self::TXN_TYPE_CANCEL;
						return true;
					}
				}
				else
				{
					$this->type = self::TXN_TYPE_LOGONLY;
					return false;
				}
				break;
			// above is for merchant-initiated refunds. This is for customer-initiated refunds (disputes).
			case 'dispute.state.updated':
				// https://developer.squareup.com/reference/square/objects/DisputeStateUpdatedWebhookData says
				// that the dispute object is under object->object instead of object->dispute like other webhook objects..
				// Per Square dev team & testing, documentation is incorrect and it is under object->dispute as consistent with
				// other webhook objects.
				$dispute = $data->object->dispute;
				// Seller accepts the dispute or lost the challenge, and refund was issued.
				if ($dispute->state == 'LOST' OR $dispute->state == 'ACCEPTED')
				{
					// Note, this handler currently does NOT support recurring subscriptions. Recurring subs
					// may have an "unknown" (to us) payment & order (see the difference between invoice.payment_made
					// vs payment.updated handling)
					// TODO: IMPLEMENT SUBSCRIPTION HANDLING WHEN WE CAN TEST RECURRING CHARGES WITH SQUARE SANDBOX
					$order_id = $this->fetchOrderIdFromPaymentId($dispute->disputed_payment->payment_id);
					if (!$this->getSetPaymentInfoByOrderId($order_id))
					{
						$this->type = self::TXN_TYPE_LOGONLY;
						$this->additional_log[] = "Orderid $order_id was not found. This may happen normally if this payment is associated with a recurring subscription.";
						return false;
					}
					else
					{
						$this->type = self::TXN_TYPE_CANCEL;
						return true;
					}
				}
				else
				{
					// Dispute may be on-going (being processed by the bank, or waiting for evidence for challenge),
					// or seller won and no refund was issued.
					$this->type = self::TXN_TYPE_LOGONLY;
					return false;
				}

				break;
			// Note, this is untested due to sandbox limitations!!! Also may require refactoring when we have updated data from Square dev team.
			case 'invoice.refunded':
				$invoice = $data->object->invoice;
				$subscription_id = $invoice->subscription_id ?? null;
				if (!$this->getSetPaymentInfoBySubscriptionId($subscription_id))
				{
					$this->error_code = 'invalid_subscription_id';
					$this->type = self::TXN_TYPE_ERROR;
					return false;
				}
				else
				{
					$this->type = self::TXN_TYPE_CANCEL;
					return true;
				}
				break;
			default:
				$this->log("Received unhandled Square webhook. Payload: " . print_r($payload, true));
				// If it's not something we explicitly handle, let's just log it (not a failure) and return.
				$this->additional_log[] = "'{$payload->type}' is not a webhook type we subscribe to. Webhook is logged but no action was taken.";
				$this->type = self::TXN_TYPE_LOGONLY;
				return false;
				break;
		}
	}

	private function fetchOrderIdFromPaymentId($payment_id)
	{
		$client = $this->getSquareClient();
		$api_response = $client->getPaymentsApi()->getPayment($payment_id);
		if ($api_response->isSuccess())
		{
			/**
			 * @var \Square\Models\Payment
			 */
			$payment = $api_response->getResult()->getPayment();
			return $payment->getOrderId();
		}
		else
		{
			return '';
		}
	}

	/**
	 * Fetch the payment amount (in smallest denomination) that matches the vbulletin subscription amount
	 * depending the tax behavior
	 *
	 * @param  string  $order_id
	 *
	 * @return array[
	 * 	'amount'    => int,
	 *  'currency'  => string,
	 *  'tax'       => int,
	 *  'inclusive' => bool,
	 * ]
	 */
	private function fetchAmountInfoInCents($order_id)
	{
		// The payment object has NO information about taxes whatsoever. We must fetch these
		// from the order object associated with the payment.

		$currency = 'usd';
		$amount = 0;
		$tax = 0;
		$taxIsInclusive = true;

		$client = $this->getSquareClient();
		$api_response = $client->getOrdersApi()->retrieveOrder($order_id);
		if ($api_response->isSuccess())
		{
			/**
			 * @var \Square\Models\Order
			 */
			$order = $api_response->getResult()->getOrder();
			// While hypothetically, each money-esque object might have a different currency,
			// Square currently only supports a single currency per merchant anyways, so not going
			// to worry about it.
			$currency = strtolower($order->getTotalMoney()->getCurrency());
			// I'm not sure what happens if somehow they are able to set one set of taxes as inclusive
			// and another set of taxes as additive... for now, if ANY tax is set as additive, treat the
			// whole thing as additive.
			// Note, we currently only support a single tax, due to the "automatic tax calculation
			// based on merchant & customer locations" feature not yet being in the API.
			$taxes = $order->getTaxes();
			foreach ($taxes AS $__tax)
			{
				$taxIsInclusive = ($taxIsInclusive AND ($__tax->getType() == \Square\Models\TaxInclusionType::INCLUSIVE));
			}
			// We *could* sum up $__tax->getAppliedMoney()->getAmount();, but ATM i don't see a reason for it.
			$tax = $order->getTotalTaxMoney()->getAmount();
			// Square doesn't seem to provide a clean "pre-tax total" values for the whole order.
			// From observations, it seems that the sum of line_items' gross_sales_money will equal to
			// the vb subscription price regardless of inclusive or additive, while
			// $order->getTotalMoney()->getAmount() differs depending on inclusive or additive tax with
			// no flags telling us which it is. So we have to manually check the gross_sales_moneys.
			// Note that this was only ever tested with a single line item per order (we currently do
			// not support multiple items per order).
			$line_items = $order->getLineItems();
			foreach ($line_items AS $__item)
			{
				$__info = $__item->getGrossSalesMoney();
				$amount += $__info->getAmount();
			}
		}

		return [
			'amount' => $amount,
			'currency' => $currency,
			'tax' => $tax,
			'inclusive' => $taxIsInclusive,
		];
	}

	private function recordInvoiceMap($vbhash, $subscription_id)
	{
		$params = [
			'hash' => $vbhash,
			'paymentapiid' => $this->paymentapirecord['paymentapiid'],
			'remotesubscriptionid' => $subscription_id,
		];
		$assertor = vB::getDbAssertor();
		$assertor->insert('vBForum:paymentapi_remote_invoice_map', $params);
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
		$params = [
			'paymentapiid' => $this->paymentapirecord['paymentapiid'],
			'vbsubscriptionid' => $vbsubscriptionid,
			'userid' => $userid,
			'active' => 1,
		];
		$assertor = vB::getDbAssertor();
		try
		{
			$squareSubsApi = $this->getSquareClient()->getSubscriptionsApi();

			$subids = $assertor->getColumn('vBForum:paymentapi_subscription', 'paymentsubid', $params);
			$defaultParams = $params;
			foreach ($subids AS $__subid)
			{
				$__conditions = $defaultParams;
				$__conditions['paymentsubid'] = $__subid;
				$__response = $squareSubsApi->cancelSubscription($__subid);
				// Log only, but move on as we may need to cancel multiple subs.
				try
				{
					$this->handleSquareApiError($__response);
					$assertor->update('vBForum:paymentapi_subscription', ['active' => 0], $__conditions);
				}
				catch (Throwable $e)
				{
					// ignore
				}
			}

			return true;
		}
		catch (Throwable $e)
		{
			$this->log($e);
			//$this->handleException($e);
			// fail quietly as to not block any other paymentAPI attempts...
			return false;
		}
	}

	private function getSetPaymentInfoBySubscriptionId($subscription_id)
	{
		if (empty($subscription_id))
		{
			return false;
		}

		$assertor = vB::getDbAssertor();
		$this->paymentinfo = $assertor->getRow('getPaymentinfoByRemoteSubscriptionId', [
			'paymentapiid' => $this->paymentapirecord['paymentapiid'],
			'subscription_id' => $subscription_id,
		]);

		// lets check the values
		if (!empty($this->paymentinfo))
		{
			// If we have a matching subscription_id stored, this is always assumed to be for a recurring payment.
			$this->payment_is_recurring = true;
			return true;
		}
		else
		{
			return false;
		}
	}

	private function getSetPaymentInfoByOrderId($order_id)
	{
		$assertor = vB::getDbAssertor();
		$this->paymentinfo = $assertor->getRow('getPaymentinfoByRemoteOrderID', [
			'paymentapiid' => $this->paymentapirecord['paymentapiid'],
			'orderid' => $order_id,
		]);

		// Shift off the `paymentapi_remote_orderid`.`recurring` which we track on our end
		// from the standard $paymentinfo
		$this->payment_is_recurring = !empty($this->paymentinfo['recurring']);
		unset($this->paymentinfo['recurring']);

		// lets check the values
		if (!empty($this->paymentinfo))
		{
			return true;
		}
		else
		{
			//$this->error_code = 'invalid_order_id';
			// let's log this instead of flagging as an error, see caller.
			return false;
		}
	}

	/**
	 * Verify Webhook Message signature from Stripe, and return the Event object from Stripe
	 *
	 * @param string $payload      JSON (undecoded) post data
	 * @param string $signature    HTTP_STRIPE_SIGNATURE header
	 *
	 * @return Stripe/Event | false  Stripe event on message verification success, false on failure.
	 */
	private function verifyWebhookMessage($payload, $receivedSignature)
	{
		// based on https://github.com/square/connect-api-examples/blob/master/connect-examples/v1/php/webhooks.php

		$webhookUrl = $this->getWebhookURL();
		$webhookSignatureKey = $this->getWebhookSecret();

  		# Combine your webhook notification URL and the JSON body of the incoming request into a single string
		# Generate the HMAC-SHA1 signature of the string, signed with your webhook signature key
		$expectedSignature = base64_encode(hash_hmac('sha1', $webhookUrl . $payload, $webhookSignatureKey, true));

		# Hash the signatures a second time (to protect against timing attacks)
		# and compare them
		return (sha1($expectedSignature) === sha1($receivedSignature));
	}

	private function getAccessToken()
	{
		// "Access token"
		return $this->settings['access_token'] ?? '';
	}

	private function getWebhookURL()
	{
		// We're allowing URL as a setting in case they use an intermediary forwarding service.
		// We need to know what they set up the webhook URL as on Square's dashboard, as that
		// URL and the webhook secret are used for signing.
		return $this->settings['webhook_url'] ?? $this->getDefaultWebhookEndpoint();
	}

	private function getWebhookSecret()
	{
		// "Webhook Signature Key"
		return $this->settings['webhook_signaure_key'] ?? '';
	}

	private function getSupportsRecurring()
	{
		return $this->paymentapirecord['recurring'] ?? false;
	}

	/**
	 * @return \Square\SquareClient
	 */
	private function getSquareClient()
	{
		return new \Square\SquareClient($this->defaultClientParams);
	}

	/**
	* Test that required settings are available, and if we can communicate with the server (if required)
	*
	* @return	bool	If the vBulletin has all the information required to accept payments
	*/
	public function test()
	{
		if (!$this->initialized)
		{
			$this->log("Stripe test failed because Stripe SDK failed to initialize. See previously logged errors.");
			return false;
		}

		$this->log('SDK initialized');

		if (
			empty($this->getAccessToken()) OR
			empty($this->getWebhookSecret())
		)
		{
			$this->log("Square test failed because at least one required setting (Access Token or Webhook Signature Key) was missing. \n" .
				"Check the Payment API manager for Square to view these settings."
			);
			return false;
		}

		// Note that square webhooks REQUIRE HTTPS !!!!
		// Square API requires TLS 1.2+, 1.3 recommended.
		// https://developer.squareup.com/docs/build-basics/general-considerations/tls-and-https
		// TODO: Find out if square offers test code for tls check like stripe did and add that here.

		try
		{
			$merchants = $this->getSquareClient()->getMerchantsApi()->listMerchants();

			if (!($merchants instanceof Square\Http\ApiResponse))
			{
				$this->log("Unexpected return from Square. Expected Square\\ApiResponse, but got: ". print_r($merchants, true));
				return false;
			}
			//$this->log("Square Merchants List: " . print_r($merchants, true));

			if (!$merchants->isSuccess())
			{
				$this->log("Encountered error while listing Square merchants: ". print_r($merchants->getErrors(), true));
				return false;
			}
		}
		catch (Throwable $e)
		{
			$this->log($e);
			$this->handleException($e);
			return false;
		}

		$currency = $this->fetchSquareMerchantCurrency();
		if ($currency !== $this->paymentapirecord['currency'])
		{
			$this->log("Currency mismatch. Square account currency is <$currency>, but paymentapi record has <{$this->paymentapirecord['currency']}>.\n"
				. "Try re-saving the paymentapi record.");
			return false;
		}


		return true;
	}

	// square does not allow programmatically setting up webhooks...
	// We need some way to expose the "Expected" URL & events somewhere in adminCP to help users
	// set up their square webhooks correctly (e.g. so signature check passes etc). Curerntly this
	// is embeded in the description phrase (setting_square_webhook_url_desc) but ideally it would
	// call this method to populate it with the EXACT value our code is expecting, as the signature
	// verification will fail if the dashboard-stored webhook URL & our expected URL differ.
	// Or even MORE ideally, we would be able to set this up via their API if they add a webhooks API
	// in the future.
	private function getDefaultWebhookEndpoint()
	{
		$frontendurl = vB::getDatastore()->getOption('frontendurl');
		$listenerurl = $frontendurl . '/core/payment_gateway.php?method=square';
		return $listenerurl;
	}

	// Again, square currently does not allow programmatically setting up the webhooks via API (this is
	// allegedly on their list of requested features, but no ETA...)
	// This is documented here for dev purposes, but also documented in the setting_square_webhook_url_desc
	// phrase.
	private function getWebhookEvents()
	{
		return [
			'invoice.payment_made',
			'payment.updated',
			'refund.updated',
			'invoice.refunded',
		];
	}

	/**
	 * Called near the end of admincp/subscriptions.php's 'apiupdate', after
	 * new settings are saved into the database. Meant to allow subclasses to
	 * handle any automated API calls when activated (e.g. adding webhooks)
	 *
	 * @param array $paymentapiRecord Updated `paymentapi` record.
	 */
	public function post_update_settings()
	{
		// Square API does not have an endpoint for programmatically updating or fetching webhooks.
		// We can check paymentapiRecord['active'] if we want to only run some things when activated.
		//$this->checkAndSetWebhooks();

		/*
		We need to fetch the merchant's account, grab its currency, and update our payment APi's currency setting.
		This is because Square API currently does NOT support different currencies, and the currency provided
		MUST MATCH the currency of the country that the account is set to... I don't know why they even allow us
		to set the currency in the API calls. Maybe they plan to allow it in the future or something??
		 */
		// fetch first merchant and grab its currency.
		// currently each account can only be set to 1 country. Not sure if this will change in the future, so
		// we may have to update this logic.

		if (!empty($this->paymentapirecord['paymentapiid']))
		{
			$currency = $this->fetchSquareMerchantCurrency();
			// do not save a empty value, as that may cause the paymentapi settings save form to get permanently stuck since
			// you cannot leave it blank, but without debug mode the admin cannot manually set this value.
			if ($currency AND $currency !== $this->paymentapirecord['currency'])
			{
				$this->log("Updating Square paymentapi currency from {$this->paymentapirecord['currency']} to $currency");
				$assertor = vB::getDbAssertor();
				$assertor->update('vBForum:paymentapi', ['currency' => $currency], ['paymentapiid' => $this->paymentapirecord['paymentapiid']]);
				$this->paymentapirecord['currency'] = $currency;
			}
		}
	}

	private function fetchSquareMerchantLocationID()
	{
		$client = $this->getSquareClient();
		$apiresult = $client->getMerchantsApi()->listMerchants();
		if ($apiresult->isSuccess())
		{
			$result = $apiresult->getResult();
			$merchants = $result->getMerchant();
			$firstMerchant = reset($merchants);
			return $firstMerchant->getMainLocationId();
		}
		else
		{
			$this->handleSquareApiError($apiresult);
		}
	}

	/**
	 * Get currency associated with the Square Account's country
	 *
	 * @return string  currency code in lower case, like 'usd' or 'aud'
	 */
	private function fetchSquareMerchantCurrency()
	{
		try
		{
			$client = $this->getSquareClient();
			$apiresult = $client->getMerchantsApi()->listMerchants();
			if ($apiresult->isSuccess())
			{
				$result = $apiresult->getResult();
				$merchants = $result->getMerchant();
				$firstMerchant = reset($merchants);
				return strtolower($firstMerchant->getCurrency());
			}
			else
			{
				$this->log($apiresult->getErrors());
				return '';
			}
		}
		catch (Throwable $e)
		{
			$this->log($e);
			$this->handleException($e);
			return '';
		}
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
		// Putting these toggles in in case we change our minds about how strict/loose we want to be
		// with the validation.
		$onlyCheckSupportedCurrencies = true;

		$hasSupportedCurrency = false;
		$currency = $this->fetchSquareMerchantCurrency();
		foreach ($costsArray AS $__subid => $__costandtime)
		{
			//$this->log($currency);
			//$this->log($__costandtime);
			//$this->log($__cost[$currency] ?? null);
			$__cost = $__costandtime['cost'];
			if (isset($__cost[$currency]) OR !$onlyCheckSupportedCurrencies)
			{
				$hasSupportedCurrency = true;
				// Only recurring vb-subscriptions "need" to be converted into Square "subscription" objects -- one time payments
				// can probably be ad hoc payments without a catalog item. Let's just worry about the recurring ones for now.
				if ($this->doesRequireRemoteCatalog($__costandtime))
				{
					// Below may throw exceptions
					$__cadence = $this->convertToSquareCadence($__costandtime);
				}
			}
		}

		if (empty($hasSupportedCurrency))
		{
			throw new vB_Exception_Api('no_supported_currency_square');
		}
	}

	private function doesRequireRemoteCatalog($costandtime)
	{
		$isRecurring = $costandtime['recurring'];
		if ($isRecurring AND !$this->getSupportsRecurring())
		{
			return false;
		}

		if ($isRecurring OR !$this->onlyCatalogRecurringSubs)
		{
			return true;
		}

		return false;
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
		$currency = $this->fetchSquareMerchantCurrency();

		//$this->log($vbsubscriptionid);
		//$this->log($costsArray);
		foreach ($costsArray AS $__subid => $__costandtime)
		{
			// If this one doesn't have our currency, just skip and assume it's intentionally meant to be handled by other payment APIs.
			// Currently this isn't allowed and shouldn't happen, but just future proofing / making it robust so we don't try to create
			// something that'll just fail at square's end.
			if (!isset($__costandtime['cost'][$currency]))
			{
				continue;
			}
			// Also, do not catalog non-recurring subscriptions, as they'll be handled by ad hoc one time payments.
			// Not cataloging is more flexible as that allows usage of non square-standard subscription lengths. Note that
			// this this->onlyCatalogRecurringSubs check is paired in the validatePricingAndTime(), so we'll begin validating
			// non-recurring sub lengths as well if we flip over to begin cataloging them.
			if (!$this->doesRequireRemoteCatalog($__costandtime))
			{
				continue;
			}

			$__cost = $__costandtime['cost'][$currency];
			$this->ensureSquareCatalogItem($vbsubscriptionid, $__subid, $__cost, $currency, $__costandtime);
		}
	}

	/**
	 * @return Square Subscription Plan ID,
	 */
	private function ensureSquareCatalogItem($vbsubscriptionid, $vbsubscription_subid, $cost, $currency, $timeinfo)
	{
		$assertor = vB::getDbAssertor();
		$check = $assertor->getRow('vBForum:paymentapi_remote_catalog', [
			'paymentapiid' => $this->paymentapirecord['paymentapiid'],
			'vbsubscriptionid' => $vbsubscriptionid,
			'vbsubscription_subid' => $vbsubscription_subid,
		]);
		$this->log($check);

		$title = $this->fetchSubscriptionTitle($vbsubscriptionid);
		$cadence = $this->convertToSquareCadence($timeinfo);
		if (empty($check))
		{
			['catalogid' => $subcriptionPlanid, 'phaseuid' => $phaseuid, 'catalogversion' => $catalogversion] = $this->upsertSquareSubscriptionPlan(
				$vbsubscriptionid,
				$vbsubscription_subid,
				$cost,
				$currency,
				$cadence,
				$title
			);
			$assertor->insert('vBForum:paymentapi_remote_catalog', [
				'paymentapiid' => $this->paymentapirecord['paymentapiid'],
				'vbsubscriptionid' => $vbsubscriptionid,
				'vbsubscription_subid' => $vbsubscription_subid,
				'remotecatalogid' => $subcriptionPlanid,
				'data' => json_encode([
					'phaseuid' => $phaseuid,
					'title' => $title,
					'cost' => $cost,
					'currency' => $currency,
					'cadence' => $cadence,
					'catalogversion' => $catalogversion,
				]),
				'active' => 1,
			]);

			return $subcriptionPlanid;
		}
		else
		{
			/*
			Be aware that after creating a subscription plan, you cannot add, remove, or reorder phases.
			To make any of these changes, you must create a new subscription plan.
			You have the option to disable a subscription plan, in which case you are not able to create new subscriptions for that plan.
			-- https://developer.squareup.com/docs/subscriptions-api/setup-plan#set-up-a-subscription-plan

			Edit:
				You also cannot edit the cost (Error: "On existing plan ..., recurring price money amount cannot be updated.")
				or edit an existing phase's time (Error: "The attribute type SQ_SUBSCRIPTION_CADENCE on object type SUBSCRIPTION_PHASE must not be modified.")
				so it seems like changing the title is just about the only thing we're allowed to do...

				In cases of the amount of money, we can override the money AT purchase time. So if it's not important, we can do that.

				so in other cases, let's disable the existing subscription

			 */
			// todo: do we need to *update* it?
			$data = json_decode($check['data'], true);
			$catalogid = $check['remotecatalogid'];
			$phaseuid = $data['phaseuid'];
			$catalogversion = $data['catalogversion'] ?? null;

			if (
				$data['title'] == $title AND
				$data['cost'] == $cost AND
				$data['currency'] == $currency AND
				$data['cadence'] == $cadence
			)
			{
				// nothing to change.
				return $catalogid;
			}
			// If only the title changed, we can update that.
			else if (
				$data['title'] != $title AND
				$data['cost'] == $cost AND
				$data['currency'] == $currency AND
				$data['cadence'] == $cadence
			)
			{
				['catalogid' => $subcriptionPlanid, 'phaseuid' => $phaseuid, 'catalogversion' => $catalogversion] = $this->upsertSquareSubscriptionPlan(
					$vbsubscriptionid,
					$vbsubscription_subid,
					$cost,
					$currency,
					$cadence,
					$title,
					$catalogid,
					$phaseuid,
					$catalogversion
				);
				$assertor->update('vBForum:paymentapi_remote_catalog', [
						'remotecatalogid' => $subcriptionPlanid,
						'data' => json_encode([
							'phaseuid' => $phaseuid,
							'title' => $title,
							'cost' => $cost,
							'currency' => $currency,
							'cadence' => $cadence,
							'catalogversion' => $catalogversion,
						]),
						'active' => 1,
					],
					[
						'paymentapiid' => $this->paymentapirecord['paymentapiid'],
						'vbsubscriptionid' => $vbsubscriptionid,
						'vbsubscription_subid' => $vbsubscription_subid,
					]
				);
				return $subcriptionPlanid;
			}
			// if something else like pricing or cadence changed, we have to disable the old, insert a new one,
			// and update our records.
			else
			{
				// Okay, apparently you also cannot edit the amount of money ("On existing plan ..., recurring price money amount cannot be updated.")
				// So we have to just disable the old plan and create a new plan.
				// Note that disabling does NOT cancel any subscription instances under those plans, but just prevents future NEW subscriptions for that plan.
				// So existing customers will still try to bill this.
				try
				{
					$this->disableSquarePlan($catalogid);
					// TODO: DO we need to fetch & cancel every subscription under this cancelled plan?
				}
				catch (Throwable $e)
				{
					// Let's just ignore any potential disable errors for now, and just go ahead with creating the new one.
					// If the disable failed for some reason, it'll still show up as active in the dashboard but does not .
				}

				// TODO: should we record the "legacy" plan somewhere?


				['catalogid' => $subcriptionPlanid, 'phaseuid' => $phaseuid,] = $this->upsertSquareSubscriptionPlan(
					$vbsubscriptionid,
					$vbsubscription_subid,
					$cost,
					$currency,
					$cadence,
					$title
				);
				// Update with new planid.
				$assertor->update('vBForum:paymentapi_remote_catalog', [
						'remotecatalogid' => $subcriptionPlanid,
						'data' => json_encode([
							'phaseuid' => $phaseuid,
							'title' => $title,
							'cost' => $cost,
							'currency' => $currency,
							'cadence' => $cadence,
							'catalogversion' => $catalogversion,
						]),
						'active' => 1,
					],
					[
						'paymentapiid' => $this->paymentapirecord['paymentapiid'],
						'vbsubscriptionid' => $vbsubscriptionid,
						'vbsubscription_subid' => $vbsubscription_subid,
					]
				);
				return $subcriptionPlanid;
			}
		}
	}

	private function handleSquareApiError($api_response)
	{
		if (!$api_response->isSuccess())
		{
			$errors = $api_response->getErrors();
			$error = reset($errors);
			$this->log($error->getDetail());
			throw new Exception($error->getCode());
		}
	}

	private function disableSquarePlan($catalogid)
	{

		// https://developer.squareup.com/docs/subscriptions-api/setup-plan#rename-a-subscription-plan
		// Square API is extremely cumbersome in that you have to re-set all the data up for an existing
		// plan, and if you get any of it wrong, it'll fail. You can't just say ->retrievePlan()->disable()

		$client = $this->getSquareClient();
		$api_response = $client->getCatalogApi()->retrieveCatalogObject($catalogid);
		$this->handleSquareApiError($api_response);

		$catalog = $api_response->getResult()->getObject();
		$catalogversion = $catalog->getVersion();

		$subscription_plan_data = $catalog->getSubscriptionPlanData();
		// Suffix title with " (archived)"
		$title = $subscription_plan_data->getName();
		$subscription_plan_data->setName($title . ' (archived)');

		$object = new \Square\Models\CatalogObject('SUBSCRIPTION_PLAN', $catalogid);
		$object->setSubscriptionPlanData($subscription_plan_data);

		$object->setUpdatedAt(date(DATE_RFC3339));
		// This is required, or else throws Object version does not match latest database version.
		$object->setVersion($catalogversion);
		// According to their docs, they currently don't support deleting subscription plans.
		// Not sure why setting deleted = false is explicitly required though.
		$object->setIsDeleted(false);
		// This bit is the actual "disable" flag, according to their docs:
		$object->setPresentAtAllLocations(false);

		$idempotencyKey = uniqid(time());
		$body = new \Square\Models\UpsertCatalogObjectRequest($idempotencyKey, $object);
		$api_response = $client->getCatalogApi()->upsertCatalogObject($body);

		if ($api_response->isSuccess())
		{
			$result = $api_response->getResult();
			$this->log($result);
			return true;
		}
		else
		{
			$this->handleSquareApiError($api_response);
		}
	}

	/**
	 * Convert vB subscription length to square cadence
	 *
	 * @param array $timeinfo  Timeinfo data from vb subscription that have the keys
	 *                         'length', 'units', 'recurring'.
	 *
	 * @return string one of the following valid Square cadence strings:
	 *                'DAILY'
	 *                'WEEKLY'
	 *                'EVERY_TWO_WEEKS'
	 *                'THIRTY_DAYS'
	 *                'SIXTY_DAYS'
	 *                'NINETY_DAYS'
	 *                'MONTHLY'
	 *                'EVERY_TWO_MONTHS'
	 *                'QUARTERLY'
	 *                'EVERY_FOUR_MONTHS'
	 *                'EVERY_SIX_MONTHS'
	 *                'ANNUAL'
	 *                'EVERY_TWO_YEARS'
	 * @throws vB_Exception_Api('invalid_square_cadence') if $timeinfo has no valid
	 *                mapping to a Square cadence.
	 */
	private function convertToSquareCadence($timeinfo)
	{
		$length = $timeinfo['length'];
		$units = $timeinfo['units'];
		/*
		Another frustrating factor...
		Square subscription "cadences" (intervals) are restricted to:
			DAILY
			WEEKLY
			EVERY_TWO_WEEKS
			THIRTY_DAYS
			SIXTY_DAYS
			NINETY_DAYS
			MONTHLY
			EVERY_TWO_MONTHS
			QUARTERLY -- Once every three months
			EVERY_FOUR_MONTHS
			EVERY_SIX_MONTHS
			ANNUAL
			EVERY_TWO_YEARS
		EXACTLY. So no E.g. every 3 days, or 40 days, etc.
		vBulletin allows arbitrary days, so this is problematic.
		There's a misleading "period" parameter, but that's the expiration/termination, NOT the interval specifier.

		This function is used to convert a vb subscription length to a valid square cadence, and throw exceptions
		if a valid mapping was not found. This is done when saving the individual subscription, where we validate
		the <pricing, subcription length> pairs.
		*/
		$mapping = [
			'D' => [
				1 => 'DAILY',
				7 => 'WEEKLY',
				14 => 'EVERY_TWO_WEEKS',
				30 => 'THIRTY_DAYS',
				60 => 'SIXTY_DAYS',
				90 => 'NINETY_DAYS',
				365 => 'ANNUAL',
				730 => 'EVERY_TWO_YEARS',
			],
			'W' => [
				1 => 'WEEKLY',
				2 => 'EVERY_TWO_WEEKS',
			],
			'M' => [
				1 => 'MONTHLY',
				2 => 'EVERY_TWO_MONTHS',
				3 => 'QUARTERLY',
				4 => 'EVERY_FOUR_MONTHS',
				6 => 'EVERY_SIX_MONTHS',
				12 => 'ANNUAL',
				24 => 'EVERY_TWO_YEARS',
			],
			'Y' => [
				1 => 'ANNUAL',
				2 => 'EVERY_TWO_YEARS',
			],
		];
		if (isset($mapping[$units][$length]))
		{
			return $mapping[$units][$length];
		}
		else
		{
			throw new vB_Exception_Api('invalid_square_cadence_x_y', [$length, $units]);
		}
	}

	private function getSquareMoneyObject($cost, $currency)
	{
		$price_money = new \Square\Models\Money();
		$amountSmallestDenomination = $this->convertToCents($cost, $currency);
		$price_money->setAmount($amountSmallestDenomination);
		$price_money->setCurrency(strtoupper($currency));

		return $price_money;
	}


	/**
	 *
	 * @return array [
	 *    string 'catalogid'  Square Subscription Plan ID,
	 *    string 'phaseuid'   Square Subscription Phase UID,
	 * ]
	 */
	private function upsertSquareSubscriptionPlan(
		$vbsubscriptionid,
		$vbsubscription_subid,
		$cost,
		$currency,
		$cadence,
		$subscriptiontitle,
		$catalogid = '#plan',
		$phaseuid = null,
		$catalogversion = null
	)
	{
		/*
		$recurring_price_money = new \Square\Models\Money();
		$amountSmallestDenomination = $this->convertToCents($cost, $currency);
		$recurring_price_money->setAmount($amountSmallestDenomination);
		$recurring_price_money->setCurrency(strtoupper($currency));
		*/
		$recurring_price_money = $this->getSquareMoneyObject($cost, $currency);


		$subscription_phase = new \Square\Models\SubscriptionPhase($cadence);
		$subscription_phase->setRecurringPriceMoney($recurring_price_money);
		// If it's an update, set the phase uid
		// We are NOT allowed to add, remove or reorder phases...
		// Update: not only that, but we apparently also cannot EDIT an existing phase either!
		// "The attribute type SQ_SUBSCRIPTION_CADENCE on object type SUBSCRIPTION_PHASE must not be modified."
		// But we even if we're not changing phase data (because we CAN'T), we still have to set every little bit
		// for every update, which is annoying.
		if ($catalogid !== '#plan' AND !is_null($phaseuid))
		{
			//https://developer.squareup.com/docs/subscriptions-api/setup-plan#rename-a-subscription-plan
			$subscription_phase->setUid($phaseuid);
			// This is required, or else throws Invalid object `....`: ordinals must be present when updating an existing plan's phases.
			$subscription_phase->setOrdinal(0);
		}

		$phases = [$subscription_phase];
		$subscription_plan_data = new \Square\Models\CatalogSubscriptionPlan($subscriptiontitle, $phases);

		$object = new \Square\Models\CatalogObject('SUBSCRIPTION_PLAN', $catalogid);
		$object->setSubscriptionPlanData($subscription_plan_data);


		if ($catalogid != '#plan' AND is_null($catalogversion))
		{
			['catalogversion' => $catalogversion ] = $this->fetchSquareSubplanPhaseAndVersion($catalogid);
			$this->log("Catalogversion:" . $catalogversion);
		}
		if ($catalogid !== '#plan' AND !is_null($catalogversion))
		{
			$object->setUpdatedAt(date(DATE_RFC3339));
			// This is required, or else throws Object version does not match latest database version.
			$object->setVersion($catalogversion);
		}

		// We might want a time component to this too, but these are effectively what makes this request "unique",
		// such that we won't mind if we get the exact same data back if we happen to call this twice.
		/*
		$uniqueData = [
			// insert or update?
			$catalogid,
			// vb primary key components
			$this->paymentapirecord['paymentapiid'],
			$vbsubscriptionid,
			$vbsubscription_subid,
			// price info
			$cost,
			$currency,
			// timeinfo
			$cadence,
			// misc
			$subscriptiontitle,
		];
		$this->log("idempotency data" . print_r($uniqueData, true));
		*/
		// Above causes too many issues with "IDEMPOTENCY_KEY_REUSED" when we're making multiple edits in the adminCP
		// e.g. if admin changes back and forth between prices or sub titles
		//$idempotencyKey = md5(json_encode($uniqueData));
		$idempotencyKey = uniqid(time());

		$body = new \Square\Models\UpsertCatalogObjectRequest($idempotencyKey, $object);

		$client = $this->getSquareClient();
		$api_response = $client->getCatalogApi()->upsertCatalogObject($body);

		if ($api_response->isSuccess())
		{
			$result = $api_response->getResult();
			$this->log($result);
			$catalog = $result->getCatalogObject();
			$phases = $catalog->getSubscriptionPlanData()->getPhases();
			$phase = reset($phases);
			return [
				'catalogid' => $catalog->getId(),
				'phaseuid' => $phase->getUid(),
				'catalogversion' => $catalog->getVersion(),
			];
		}
		else
		{
			$this->handleSquareApiError($api_response);
		}
	}

	private function fetchSquareSubplanPhaseAndVersion($catalogid)
	{
		$client = $this->getSquareClient();
		$api_response = $client->getCatalogApi()->retrieveCatalogObject($catalogid);

		if ($api_response->isSuccess())
		{
			$result = $api_response->getResult();
			$this->log($result);
			$catalog = $result->getObject();
			$phases = $catalog->getSubscriptionPlanData()->getPhases();
			$phase = reset($phases);
			return [
				'catalogversion' => $catalog->getVersion(),
				'phaseuid' => $phase->getUid(),
			];
		}
		else
		{
			$this->handleSquareApiError($api_response);
		}

	}

	/**
	 *
	 * @return string|bool  URL or false on failure.
	 */
	private function generateSquarePaymentLink($hash, $taxOptions, $cost, $currency, $subinfo, $userinfo, $timeinfo)
	{
		// ATM cataloging is moved to subscription save in adminCP. This simplifies some things.
		$doJustInTimeCataloging = false;
		if (!$this->initialized)
		{
			return false;
		}

		$assertor = vB::getDbAssertor();

		$isRecurring = !empty($timeinfo['recurring']);
		if ($isRecurring AND !$this->getSupportsRecurring())
		{
			throw new vB_Exception_Api('payment_api_error');
		}
		$subcriptionPlanid = null;
		if ($isRecurring OR !$this->onlyCatalogRecurringSubs)
		{
			// We're doing another query because the data we receive does not reliably have subscriptionid & subscriptionsubid from the paymentinfo table,
			// although all the params are BASED on them, and changing the call stack to provide it would affect all other payment APIs, which is too risky.
			$check = $assertor->getRow('fetchRemoteCatalogByHash', ['hash' => $hash, 'paymentapiid' => $this->paymentapirecord['paymentapiid']]);
			if (empty($check))
			{
				// this means the paymentinfo record is missing. Error out.
				$this->log("Missing `paymentinfo` record for hash $hash");
				throw new vB_Exception_Api('invalid_data');
			}

			if (empty($check['remotecatalogid']))
			{
				if (!$doJustInTimeCataloging)
				{
					$this->log("Missing required `paymentapi_remote_catalog` record for recurring subscriptionid & subid associated with hash $hash");
					// TODO: Different error -- also warn admin to fix their data??
					throw new vB_Exception_Api('invalid_data');
				}

				// need to create one.
				$subcriptionPlanid = $this->ensureSquareCatalogItem($check['subscriptionid'], $check['subscriptionidsubid'], $cost, $currency, $timeinfo);
			}
			else
			{
				$subcriptionPlanid = $check['remotecatalogid'];
			}
		}


		// We're assuming that $subcriptionPlanid check & $isRecurring check are equivalent, but
		// that's only true as long as $this->onlyCatalogRecurringSubs is true. But it really doesn't
		// matter as long as we're not trying to generate a "quick pay" (ad hoc) link for a recurring
		// subscription (as opposed to generating a catalog payment link for a non-recurring, which is fine).
		// If that's the case, we'll have caught it in the above blocks and exceptioned out.


		// References:
		// 1. https://developer.squareup.com/reference/square/checkout-api/create-payment-link
		// 2. https://developer.squareup.com/docs/checkout-api/subscription-plan-checkout
		// 3. https://github.com/square/square-php-sdk/blob/master/doc/apis/subscriptions.md#create-subscription
		// 4. https://developer.squareup.com/docs/checkout-api/quick-pay-checkout
		// The only difference between an ad-hoc & subscription plan paymentlink generation is that
		// we set the subscriptionplanid in CheckoutOptions & set the CheckoutOptions into the
		// createPaymentLink body.

		// Note that the $price_money works as a "price override" if it does not match the values in the subscription plan
		// according to reference #2.
		$price_money = $this->getSquareMoneyObject($cost, $currency);
		$locationid = $this->fetchSquareMerchantLocationID();


		// Note, late during development, there was confusion from Square support about whether quick pay worked with subscriptions
		// or not, and metadata is in beta & was not quite reliable, so I've opted to keep tracking order_id instead of using metadata.
		// However, the metadata SETTING code is still extant for future usage.
		// Note: Subscription payments (other than the first time payment, presumably) will generate a new order each time. As such,
		// we listen to the first invoice webhook which *should* have an orderid that matches the below, and store a mapping of
		// subscription_id to hash so that we can map subsequent invoices back to which vb-order it was. See verify_payment().

		// In order to use the metadata, it seems we must go through Order.
		// Note, keys must be < 60chars [a-zA-Z9-9_-], values < 255 chars.
		// max 10 entries per metadata field. Metadata set by one application
		// may NOT BE read by another application, so beware if using multiple
		// applicationid's.
		$metadata = [
			'url' => vB::getDatastore()->getOption('frontendurl'),
			'hash' => $hash,
			'userid' => $userinfo['userid'],
			'subscriptionid' => $subinfo['subscriptionid'],
		];
		// https://developer.squareup.com/docs/checkout-api/square-order-checkout
		// https://developer.squareup.com/reference/square/objects/Order
		$order_line_item = new \Square\Models\OrderLineItem('1');
		$order_line_item->setName($subinfo['title']);
		$order_line_item->setMetadata(array_merge($metadata, ['data_src' => 'OrderLineItem']));
		$order_line_item->setBasePriceMoney($price_money);
		$line_items = [$order_line_item, ];
		$order = new \Square\Models\Order($locationid);
		$order->setLineItems($line_items);
		$order->setMetadata(array_merge($metadata, ['data_src' => 'Order']));

		if ($taxOptions['tax'] AND !empty($taxOptions['tax_percentage']))
		{
			// Some references...
			// https://squareup.com/help/us/en/article/5061-create-and-manage-your-tax-settings
		 	// https://developer.squareup.com/docs/orders-api/apply-taxes-and-discounts
			// https://developer.squareup.com/forums/t/checkout-api-does-not-show-taxes-in-reports/2798/4
			$taxes = new \Square\Models\OrderLineItemTax();
			$taxes->setScope(\Square\Models\OrderLineItemTaxScope::ORDER);
			if ($taxOptions['tax_behavior'] == 'inclusive')
			{
				$taxes->setType(\Square\Models\TaxInclusionType::INCLUSIVE);
			}
			else
			{
				$taxes->setType(\Square\Models\TaxInclusionType::ADDITIVE);
			}
			$taxes->setPercentage($taxOptions['tax_percentage']);
			$taxes->setName($taxOptions['tax_name']);
			// Per Square dev team this must be set as well as the name above, but it can be some arbitrary
			// value as long as each tax has a unique one per array.
			// But also: "Uids cannot be empty and must contain only alphanumeric characters, dots, underscores, and hyphens."
			// We could alternatively set the taxuid to some version of the tax_name, but that seems overkill since it's not
			// used for anything and has no "meaning" as far as I can tell per correspondence with Square dev team.
			//$taxuid = preg_replace('#[^A-Za-z0-9\._-]#', '_', $taxOptions['tax_name'])
			$taxes->setUid('vBulletin_Custom_Tax_Override');

			$order->setTaxes([$taxes]);
			// Alternatively, we might be able to set autopay via OrderPricingOptions::setAutoApplyTaxes,
			// but store-wide taxes does not seem to work in Sandbox.
		}
		$this->log($taxOptions);
		/*
		$pricing_options = new \Square\Models\OrderPricingOptions();
		$pricing_options->setAutoApplyTaxes(true);
		$order->setPricingOptions($pricing_options);
		*/

		$body = new \Square\Models\CreatePaymentLinkRequest();
		$body->setIdempotencyKey(uniqid());
		$body->setOrder($order);

		// Edit: Quick Pay does not seem to support taxes. Shifting back to Order override (now that we know
		// it DOES support subscriptions for the future as well.)
		/*
		$quick_pay = new \Square\Models\QuickPay(
			$subinfo['title'],
			$price_money,
			$locationid
		);

		$body = new \Square\Models\CreatePaymentLinkRequest();
		$body->setIdempotencyKey(uniqid());
		$body->setQuickPay($quick_pay);
		*/

		$checkout_options = new \Square\Models\CheckoutOptions();
		$frontendurl = vB::getDatastore()->getOption('frontendurl');
		$checkout_options->setRedirectUrl($frontendurl . '/settings/subscriptions');

		$recurring = 0;
		if ($subcriptionPlanid)
		{
			$checkout_options->setSubscriptionPlanId($subcriptionPlanid);
			$recurring = 1;
		}

		$body->setCheckoutOptions($checkout_options);


		$client = $this->getSquareClient();
		$api_response = $client->getCheckoutApi()->createPaymentLink($body);
		$this->handleSquareApiError($api_response);
		$payment_link = $api_response->getResult()->getPaymentLink();
		$order_id = $payment_link->getOrderId();
		// keep track of our order id so we can handle the webhook later
		$this->trackOrderId($hash, $order_id, $recurring);

		return $payment_link->getUrl();
	}

	private function trackOrderId($vbhash, $square_order_id, $recurring)
	{
		// We track the orderid so we can map back to the vb-subscription when we receive
		// an invoice or payment webhook later, because metadata is not reliable yet (&
		// unsupported for subscriptions).
		$assertor = vB::getDbAssertor();
		$assertor->insertIgnore('vBForum:paymentapi_remote_orderid', [
			'paymentapiid' => $this->paymentapirecord['paymentapiid'],
			'hash' => $vbhash,
			'remoteorderid' => $square_order_id,
			'recurring' => $recurring,
		]);
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

		$defaultOptions = [
			'tax' => 0,
			//'tax_uid' => '',
			'tax_name' => '',
			'tax_percentage' => 0,
			'tax_behavior' => 'inclusive',
		];
		$options = $subinfo['newoptions']['api']['square'] ?? [];
		$options = array_merge($defaultOptions, $options);

		// load sub title for product...
		$subinfo['title'] = $this->fetchSubscriptionTitle($subinfo['subscriptionid']);

		try
		{
			$url = $this->generateSquarePaymentLink($hash, $options, $cost, $currency, $subinfo, $userinfo, $timeinfo);
		}
		catch (Throwable $e)
		{
			// Show generic payment error exception to regular user trying to subscribe
			// rather than Square specific one.
			throw new vB_Exception_Api('payment_api_error');
		}
		if (empty($url))
		{
			throw new vB_Exception_Api('payment_api_error');
		}

		//  redirect to URL...
		$form['action'] = $url;
		// This is a GET not POST, intentionally. Trying to POST to the checkout URL will hit a 403.
		$form['method'] = 'get';

		return $form;
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 109629 $
|| #######################################################################
\*=========================================================================*/
