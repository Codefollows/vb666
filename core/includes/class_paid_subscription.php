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

/**
* Class that provides payment verification and form generation functions
*
* @package	vBulletin
* @date		$Date: 2024-02-14 14:29:40 -0800 (Wed, 14 Feb 2024) $
*
* @abstract
*
*/
abstract class vB_PaidSubscriptionMethod
{

	/**
	 * The vBulletin Registry
	 *
	 * @var vB_Registry
	 *
	 */
	var $registry = null;

	/**
	 * Settings for this Subscription Method
	 *
	 * @var array
	 *
	 */
	protected $settings = [];

	/**
	 * Should we display the feedback from this Subscription Gateway?
	 *
	 * @var	boolean
	 *
	 */
	public $display_feedback = false;
	// TODO: This is public to allow payment_gateway.php to read it, but this should be
	// read-only (protected with a getter)

	/**
	 * An array of information regarding the payment
	 *
	 * @var array
	 *
	 */
	public $paymentinfo = [];
	// This is currently public to allow payment_gateway.php to read it, but should probably
	// be read-only like $display_feedback above.

	/**
	 * The transaction ID
	 *
	 * @var	mixed
	 *
	 */
	public $transaction_id = '';
	// This is currently public to allow payment_gateway.php to read it, but should probably
	// be read-only like $display_feedback above.

	/**
	 * The payment Type
	 *
	 * @var integer
	 *
	 */
	public $type = 0;
	// This is currently public to allow payment_gateway.php to read it, but should probably
	// be read-only like $display_feedback above.

	// Identify some $this->type constants we use throughout.
	// Have not replaced hard-coded int usages though, so this is
	// more for dev documentation atm.
	const TXN_TYPE_ERROR = 0;
	const TXN_TYPE_PAYMENT = 1;
	const TXN_TYPE_CANCEL = 2;
	// This used to be used for google checkout (now removed) to ignore
	// certain webhooks (no logging).
	const TXN_TYPE_GOOGLECHECKOUT_IGNORE = 3;
	const TXN_TYPE_LOGONLY = 4;
	const TXN_TYPE_DUPE_WEBHOOK = 5;

	/**
	 * The error String (if any)
	 *
	 * @var	string
	 *
	 */
	var $error = '';

	/**
	 * The error code (if any)
	 *
	 * @var string
	 *
	 */
	var $error_code = '';

	/**
	 * `paymentapi` record used to construct this class. Useful for fetching/linking back to
	 * relevant database records.
	 *
	 * @var array[
	 * 	'paymentapiid' int,
	 *  'classname' string,
	 *  ...
	 * ]
	 */
	protected $paymentapirecord = [];

	/**
	 * Constructor
	 *
	 * @param   array  `paymentapi` record associated with this payment method.
	 *
	 */
	function __construct($paymentapirecord)
	{
		$this->paymentapirecord = $paymentapirecord;

		if (is_string($paymentapirecord['settings']))
		{
			// need to convert this from a serialized array with types to a single value
			$this->settings = vB_PaidSubscription::construct_payment_settings($paymentapirecord['settings']);
		}
		else if (is_array($paymentapirecord['settings']))
		{
			$this->settings = $paymentapirecord['settings'];
		}
		else
		{
			// some payment processors (like the debug test one) may not have settings..
			//throw new vB_Exception_Api('invalid_data');
		}

		$this->registry = vB::get_registry();
		if (!is_object($this->registry->db))
		{
			throw new Exception('Database object is not an object');
		}
	}
	/**
	 * Perform verification of the payment, this is called from the payment gateway
	 *
	 * @return	bool	Whether the payment is valid
	 *
	 */
	abstract function verify_payment();

	/**
	* Generates HTML for the subscription form page
	*
	* @param	string		Hash used to indicate the transaction within vBulletin
	* @param	string		The cost of this payment
	* @param	string		The currency of this payment
	* @param	array		Information regarding the subscription that is being purchased
	* @param	array		Information about the user who is purchasing this subscription
	* @param	array		Array containing specific data about the cost and time for the specific subscription period
	* @param	array		Additional information: [
    *                          "context" => "usersettings"|"registration" - context of the subscription.
	*                       ]
	*
	* @return	array		Compiled form information
	*
	*/
	function generate_form_html($hash, $cost, $currency, $subinfo, $userinfo, $timeinfo, $extra = [])
	{
		$form = array();
		// Legacy Hook 'paidsub_construct_payment' Removed //
		return $form;
	}

	/**
	 * Called near the end of admincp/subscriptions.php's 'apiupdate', after
	 * new settings are saved into the database. Meant to allow subclasses to
	 * handle any automated API calls when activated (e.g. adding webhooks)
	 */
	public function post_update_settings()
	{
		// Implement on subclass as needed.
	}

	/**
	 * Called by payment_gateway.php for logging transaction for TXN_TYPE_ERROR & TXN_TYPE_LOGONLY.
	 *
	 * @return array
	 */
	public function getRequestForLogging()
	{
		//Log GET & POST data by default.
		$data = [
			'GET'           => serialize($_GET),
			'POST'          => serialize($_POST)
		];
		return $data;
	}

	/**
	 * Called by payment_gateway.php for logging paymentapi subscription information for TXN_TYPE_PAYMENT.
	 * This information will be used to automatically cancel recurring payments on the payment endpoint, for
	 * APIs that support it.
	 *
	 * @param int     $userid
	 * @param int     $vbsubscriptionid
	 * @param int     $paymentapiid
	 * @param string  $paymentsubid  subscription id relevant to the payment platform that can be used to cancel the
	 *                               remote subscription via their API. As such, format is dependent on the remote
	 *                               API in question.
	 */
	protected function recordRemoteSubscription($userid, $vbsubscriptionid, $paymentsubid)
	{
		if (empty($paymentsubid))
		{
			return;
		}
		$params = [
			'paymentapiid' => $this->paymentapirecord['paymentapiid'],
			'vbsubscriptionid' => $vbsubscriptionid,
			'userid' => $userid,
			'paymentsubid' => $paymentsubid,
		];
		$assertor = vB::getDbAssertor();
		$assertor->assertQuery('insertUpdatePaymentapiSubscription', $params);
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
		// Implement payment API specific logic in the subclass.
	}

	/**
	* Test that required settings are available, and if we can communicate with the server (if required)
	*
	* @return	bool	If the vBulletin has all the information required to accept payments
	*/
	abstract public function test();

	protected function getZeroDecimalCurrencies() : array
	{
		// These are currencies whose "minor unit" per ISO-4217 is 0 instead of 2.
		// I.e. 1 JPY is the lowest, there's no 1 japanese cent (e.g. sen is not
		// used in ISO-4217)
		$zeroDecimalCurrencies = [
			'BIF',
			'CLP',
			'DJF',
			'GNF',
			'JPY',
			'KMF',
			'KRW',
			'MGA',
			'PYG',
			'RWF',
			'UGX',
			'VND',
			'VUV',
			'XAF',
			'XOF',
			'XPF',
		];

		return $zeroDecimalCurrencies;
	}

	/**
	 * Convert minor unit 2 currencies to their "smallest denomination" values.
	 * This was initially implemented in the stripe class, but turns out other payment platforms also
	 * use this format. This method should generally work for most cases expecting smallest denominations/unit
	 * with ISO-4217 currency code format, but be warned that we do not check EVERY currency code per ISO-4217,
	 * only the ones using minor unit of 2, and the exceptions listed below. If support for more currencies
	 * that do not have minor unit = 2 (e.g. BHD) is required, refactor this to check exhaustively.
	 *
	 * @param float    $cost      "dollar" value, e.g. 1.20 for $1.20 USD
	 * @param string   $currency  ISO-4217 currency code, e.g. "USD". Can be either upper or lower case.
	 *
	 * @return int   Cost converted to smallest unit. E.g. 120 for $1.20 USD
	 */
	protected function convertToCents($cost, $currency)
	{
		// Stripe & Square APIs (& likely other payment APIs) only accepts "cents" values:
		// https://stripe.com/docs/currencies#zero-decimal
		// In this format, e.g. $1.25, has to be USD 125, apparently. This probably helps
		// avoid various floating point errors that can occur needlessly.
		$currency = strtoupper($currency);

		// These are currencies whose "minor unit" per ISO-4217 is 0 instead of 2.
		// I.e. 1 JPY is the lowest, there's no 1 japanese cent (e.g. sen is not
		// used in ISO-4217)
		$zeroDecimalCurrencies = $this->getZeroDecimalCurrencies();
		if (!in_array($currency, $zeroDecimalCurrencies))
		{
			// The special handlings are based around Stripe at the moment, as that's the
			// only modern payment API that seems to have the most currency coverage AND
			// allows for multiple currencies per merchant. If we implement other payment
			// APIs that handle these "special" currencies differently, please override this
			// method!

			// https://stripe.com/docs/currencies#special-cases
			if ($currency == 'HUF' OR $currency == 'TWD' OR $currency == 'UGX')
			{
				$cost = intval($cost) * 100;
			}
			else
			{
				// At the time of this writing, there are some non-cent currencies, like
				// BHD (minorunit = 3, or 1000:1) but Stripe & Square (currently the only
				// APIs that use this method) do not support those currencies.
				// vBulletin seems to only support usd,gbp,eur,aud,cad out of the box
				// for e.g. PayPal, Moneybrookers, and we've currently defaulted Stripe to also
				// only support those currencies (but hypothetically we could add more currencies
				// if customers request them). Note that we're currently bound by the 250 varchar
				// `paymentapi`.`currency` column limit (might need to refactor this..).
				// Square API is a bit special in that in order to handle its API restrction of
				// supporting only ONE currency per store, and having to match the expected currency
				// of the store's locale, we fetch expected currency from Square's API and set it
				// to Square's `paymentapi` record to ensure everything will work. As such, the
				// set currency could hypothetically be outside of any "preset" default currencies
				// mentioned. At the time of writing, Square seems to only support payments in the
				// following countries: Australia, Canada, France, Ireland, Japan, Spain,
				// United Kingdom, United States. (see https://developer.squareup.com/docs/payment-card-support-by-country
				// & https://squareup.com/help/us/en/article/4956-international-availability )
				// Of those countries, only Japan uses a minorunit=0 currency (JPY, in
				// $zeroDecimalCurrencies above), while the others are minorunit=2, so this method
				// should still work for all Square supported currencies.

				// If we begin integrating with payment APIs with wider support than the 0 or 2 minorunit
				// currencies, we should go ahead and map all of the current ISO-4217 currencies and
				// perform proper conversions here instead of just assuming it's minorunit=2 (100:1).
				// For a list, see https://en.wikipedia.org/wiki/ISO_4217#Active_codes

				$cost = $cost * 100;
			}
		}

		// unit_amount MUST be an integer, otherwise it'll throw an exception. unit_amount_decimal seems to be able to handle
		// "fractional cents" depending on currency, but we're not handling that ATM.

		return intval($cost);
	}

	/**
	 * Convert minor unit 2 currencies from their "smallest denomination" values to "dollar" values.
	 * Also see @self::convertToCents() .
	 *
	 * @param float    $cost      "cents" value, e.g. 120 for $1.20 USD
	 * @param string   $currency  ISO-4217 currency code, e.g. "USD". Can be either upper or lower case.
	 *
	 * @return int   Cost converted to normalized "dollar" value. E.g. 1.20 for API returns of "120 USD"
	 *               meaning 1 dollar 20 cents
	 */
	protected function convertFromCents($cost, $currency)
	{
		// See notes in convertToCents().
		// Revert from Stripe amount format (e.g. 10 USD for 10 cents) to vB amount format (0.10 USD for 10 cents)

		$currency = strtoupper($currency);

		$zeroDecimalCurrencies = [
			'BIF',
			'CLP',
			'DJF',
			'GNF',
			'JPY',
			'KMF',
			'KRW',
			'MGA',
			'PYG',
			'RWF',
			'UGX',
			'VND',
			'VUV',
			'XAF',
			'XOF',
			'XPF',
		];
		if (!in_array($currency, $zeroDecimalCurrencies))
		{
			$cost = $cost /  100;
		}

		return floatval($cost);
	}

	protected function fetchSubscriptionTitle($subscriptionid)
	{
		$phrasetitle = 'sub' . $subscriptionid . '_title';
		$vbphrase = vB_Api::instanceInternal('phrase')->fetch($phrasetitle);
		return $vbphrase[$phrasetitle] ?? 'Untitled Subscription';
	}

	protected function fetchSubscriptionTitleAndDescription($subscriptionid) : array
	{
		$title = 'sub' . $subscriptionid . '_title';
		$desc = 'sub' . $subscriptionid . '_desc';
		$vbphrase = vB_Api::instanceInternal('phrase')->fetch([$title, $desc]);
		return [
			'title' => $vbphrase[$title] ?? 'Untitled Subscription',
			'desc' => $vbphrase[$desc] ?? '',
		];
	}

	protected function fetchExpectedCost($vbsubscriptionid, $vbsubscription_subid, $currency)
	{
		$currency = strtolower($currency);
		$assertor = vB::getDbAssertor();
		$sub = $assertor->getRow('vBForum:subscription', ['subscriptionid' => $vbsubscriptionid]);
		$cost = unserialize($sub['cost'], ['allowed_classes' => false]);
		$expected = $cost[$vbsubscription_subid]['cost'][$currency] ?? NULL;

		return $expected;
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
	 * @throws vB_Exception_Api  on validation failure
	 */
	public function validatePricingAndTime($costsinfo)
	{
		// each subclass should implement this.
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
		// each subclass should implement this.
	}
}


/**
 * Class to handle Paid Subscriptions
 *
 * @package	vBulletin
 * @license http://www.vbulletin.com/licence.html
 *
 */
class vB_PaidSubscription
{
	/**
	* The vBulletin registry object
	*
	* @var	vB_Registry
	*/
	var $registry = null;

	// This seems to be an outdated thing, as I see no UI for setting this in the payment API adminCP pages and
	// no references to these in the form submitted to PayPal. We should remove it but that requires also cleaning
	// up some upgrade/install code with it.
	/**
	* The extra paypal option bitfields
	*
	* @var	_SUBSCRIPTIONS
	*/
	const _SUBSCRIPTIONOPTIONS = [
		'tax'       => 1,
		'shipping1' => 2,
		'shipping2' => 4,
	];

	/**
	* The subscription cache array, indexed by subscriptionid
	*
	* @var	subscriptioncache
	*/
	var $subscriptioncache = array();

	/**
	* Constructor
	*
	* @param	vB_Registry	Reference to registry object
	*/
	function __construct()
	{
		$this->registry = vB::get_registry();
		if (!is_object($this->registry))
		{
			throw new Exception("vB_PaidSubscription::Registry object is not an object");
		}
	}

	/**
	 * @param array $api  `paymentapi` record
	 *
	 * @return vB_PaidSubscriptionMethod subclass
	 */
	public static function fetchPaymentMethodInstance($api)
	{
		$subobj = new vB_PaidSubscription();
		if (file_exists(DIR . '/includes/paymentapi/class_' . $api['classname'] . '.php'))
		{
			require_once(DIR . '/includes/paymentapi/class_' . $api['classname'] . '.php');
			$api_class = 'vB_PaidSubscriptionMethod_' . $api['classname'];
			// we could exploit the fact that $subobj->registry is currently PUBLIC, but let's not.
			$apiobj = new $api_class($api);

			return $apiobj;
		}

		return null;
	}

	/**
	* Adds a unix timestamp and an english date together
	*
	* @param	int		Unix timestamp
	* @param	int		Number of units to add to timestamp
	* @param	string	The units of the number parameter
	*
	* @return	int		Unix timestamp
	*/
	function fetch_proper_expirydate($regdate, $length, $units)
	{
		//So I'm not sure what the deal is here but the comment below about converting to an int is wrong.
		//Adding zero will convert to a number -- either an int or a float depending. The code appears to
		//be checking to ensure the value is a int and not a float.  More recently PHP requires that a
		//string be numeric to do math on it instead of just treating it as 0.  Let's restore that behavior
		//instead of getting a fatal error.
		if(!is_numeric($length))
		{
			$length = 0;
		}

		if(!is_numeric($regdate))
		{
			$regdate = 0;
		}

		// convert the string to an integer by adding 0
		$length = $length + 0;
		$regdate = $regdate + 0;

		if (!is_int($regdate) OR !is_int($length) OR !is_string($units))
		{ // its not a valid date
			return false;
		}

		$units_full = array(
			'D' => 'day',
			'W' => 'week',
			'M' => 'month',
			'Y' => 'year'
		);
		// lets get a formatted string that strtotime will understand
		$formatted = date('d F Y H:i', $regdate);

		// if we extend for years, we need to make sure we're not going into 2038 - #23115
		if ($units == 'Y')
		{
			$start_year = date('Y', $regdate);
			if ($start_year + $length >= 2038)
			{
				// too long, return a time for the beginning of 2038
				return mktime(0, 0, 0, 1, 2, 2038);
			}
		}

		// now lets add the appropriate terms
		$time = strtotime("$formatted + $length " . $units_full["$units"]);
		return $time;
	}

	/**
	* Creates user subscription
	*
	* @param	int		The id of the subscription
	* @param	int		The subid of the subscription, this indicates the length
	* @param	int		The userid the subscription is to be applied to
	* @param	int		The start timestamp of the subscription
	* @param	int		The expiry timestamp of the subscription
	* @param	boolean	Whether to perform permission checks to determin if this user can have this subscription
	*
	*/
	function build_user_subscription($subscriptionid, $subid, $userid, $regdate = 0, $expirydate = 0, $checkperms = true)
	{
		//first three variables are pretty self explanitory
		//the 4th is used to decide if the user is subscribing to the subscription for the first time or rejoining

		$vb5_config = vB::getConfig();
		$timenow = vB::getRequest()->getTimeNow();

		$subscriptionid = intval($subscriptionid);
		$subid = intval($subid);
		$userid = intval($userid);

		$this->cache_user_subscriptions();
		$sub = $this->subscriptioncache[$subscriptionid];
		$tmp = unserialize($sub['cost']);
		// It seems that delete_user_subscription() will call this with $subid = -1. Not exactly sure why, but I think it has to do with
		// rebuilding subscription groups for users with multiple subs.
		if ($subid != -1 AND is_array($tmp[$subid] ?? NULL))
		{
			$sub = array_merge($sub, $tmp[$subid]);
		}
		unset($tmp);
		$assertor = vB::getDbAssertor();
		$user = $assertor->getRow('user', ['userid' => $userid]);
		$currentsubscription = $assertor->getRow('vBForum:subscriptionlog', ['userid' => $userid, 'subscriptionid' => $subscriptionid]);

		if ($checkperms AND !empty($sub['deniedgroups']) AND !count(array_diff(fetch_membergroupids_array($user), $sub['deniedgroups'])))
		{
				return false;
		}

		// no value passed in for regdate and we have a currently active subscription
		if ($regdate <= 0 AND !empty($currentsubscription['regdate']) AND !empty($currentsubscription['status']))
		{
			$regdate = $currentsubscription['regdate'];
		}
		// no value passed and no active subscription
		else if ($regdate <= 0)
		{
			$regdate = $timenow;
		}

		$expirydate_basis = null;
		if ($expirydate <= 0 AND !empty($currentsubscription['expirydate']) AND !empty($currentsubscription['status']))
		{
			$expirydate_basis = $currentsubscription['expirydate'];
		}
		else if ($expirydate <= 0 OR $expirydate <= $regdate)
		{
			$expirydate_basis = $regdate;
		}

		if ($expirydate_basis)
		{
			// active subscription base the value on our current expirydate
			$expirydate = $this->fetch_proper_expirydate($expirydate_basis, $sub['length'], $sub['units']);
		}

		if ($user['userid'] AND $sub['subscriptionid'])
		{
			$userdm = new vB_DataManager_User($this->registry, vB_DataManager_Constants::ERRTYPE_SILENT);
			$userdm->set_existing($user);

			$noalter = explode(',', $vb5_config['SpecialUsers']['undeletableusers'] ?? '');
			if (empty($noalter[0]) OR !in_array($userid, $noalter))
			{
				//membergroupids and usergroupid
				if (!empty($sub['membergroupids']))
				{
					$membergroupids = array_merge(fetch_membergroupids_array($user, false), array_diff(fetch_membergroupids_array($sub, false), fetch_membergroupids_array($user, false)));
				}
				else
				{
					$membergroupids = fetch_membergroupids_array($user, false);
				}

				if ($sub['nusergroupid'] > 0)
				{
					$userdm->set('usergroupid', $sub['nusergroupid']);
					$userdm->set('displaygroupid', 0);

					if ($user['customtitle'] == 0)
					{
						$usergroup = $assertor->getRow('usergroup', ['usergroupid' => $sub['nusergroupid']]);
						if (!empty($usergroup['usertitle']))
						{
							$userdm->set('usertitle', $usergroup['usertitle']);
						}
					}
				}
				$userdm->set('membergroupids', implode(',', $membergroupids));
			}

			$userdm->save();
			unset($userdm);

			if (empty($currentsubscription['subscriptionlogid']))
			{
				/*insert query*/
				$assertor->insert('vBForum:subscriptionlog', [
					'subscriptionid' => $subscriptionid,
					'userid' => $userid,
					'pusergroupid' => $user['usergroupid'],
					'status' => 1,
					'regdate' => $regdate,
					'expirydate' => $expirydate,
				]);
			}
			else
			{
				$updatedata = [
					'status' => 1,
					'regdate' => $regdate,
					'expirydate' => $expirydate,
				];

				if (!$currentsubscription['status'])
				{
					$updatedata['pusergroupid'] = $user['usergroupid'];
				}

				$assertor->update('vBForum:subscriptionlog',
					$updatedata,
					[
						'userid' => $userid,
						'subscriptionid' => $subscriptionid,
					]
				);
			}

			// Legacy Hook 'paidsub_build' Removed //
		}
	}

	/**
	* Removes user subscription
	*
	* @param	int		The id of the subscription
	* @param	int		The userid the subscription is to be removed from
	* @param int		The id of the sub-subscriptionid
	* @param bool		Update user.adminoptions from subscription.adminoption (keep avatars)
	*
	*/
	function delete_user_subscription($subscriptionid, $userid, $subid = -1, $adminoption = false)
	{
		$subscriptionid = intval($subscriptionid);
		$userid = intval($userid);

		$this->cache_user_subscriptions();
		$sub =& $this->subscriptioncache["$subscriptionid"];

		$assertor = vB::getDbAssertor();
		$user = $assertor->getRow('fetchUsersSubscriptions', [
			'userid' => $userid,
			'subscriptionid' => $subscriptionid,
			'adminoption' => $adminoption,
		]);
		$timenow = vB::getRequest()->getTimeNow();

		if ($user['userid'] AND $sub['subscriptionid'])
		{
			$this->cache_user_subscriptions();
			$sub =& $this->subscriptioncache[$subscriptionid];
			$tmp = unserialize($sub['cost']);
			if ($subid != -1 AND is_array($tmp[$subid]))
			{
				$sub = array_merge($sub, $tmp[$subid]);

				switch ($sub['units'])
				{
					case 'D':
						$new_expires = mktime(date('H', $user['expirydate']), date('i', $user['expirydate']), date('s', $user['expirydate']), date('n', $user['expirydate']), date('j', $user['expirydate']) - $sub['length'], date('Y', $user['expirydate']));
						break;
					case 'W':
						$new_expires = mktime(date('H', $user['expirydate']), date('i', $user['expirydate']), date('s', $user['expirydate']), date('n', $user['expirydate']), date('j', $user['expirydate']) - ($sub['length'] * 7), date('Y', $user['expirydate']));
						break;
					case 'M':
						$new_expires = mktime(date('H', $user['expirydate']), date('i', $user['expirydate']), date('s', $user['expirydate']), date('n', $user['expirydate']) - $sub['length'], date('j', $user['expirydate']), date('Y', $user['expirydate']));
						break;
					case 'Y':
						$new_expires = mktime(date('H', $user['expirydate']), date('i', $user['expirydate']), date('s', $user['expirydate']), date('n', $user['expirydate']), date('j', $user['expirydate']), date('Y', $user['expirydate']) - $sub['length']);
						break;
				}

				if ($new_expires > $timenow)
				{	// new expiration is still after today so just decremement and return
					$assertor->update('vBForum:subscriptionlog', ['expirydate' => $new_expires], ['subscriptionid' => $subscriptionid, 'userid' => $userid]);
					return;
				}
			}
			unset($tmp);

			$userdm = new vB_DataManager_User($this->registry, vB_DataManager_Constants::ERRTYPE_SILENT);
			$userdm->set_existing($user);

			if ($adminoption)
			{
				if ($user['hascustomavatar'] AND $sub['adminavatar'])
				{
					$userdm->set_bitfield('adminoptions', 'adminavatar', 1);
				}
			}

			$membergroupids = array_diff(fetch_membergroupids_array($user, false), fetch_membergroupids_array($sub, false));
			$update_userban = false;
			$userbansql = [];
			if ($sub['nusergroupid'] == $user['usergroupid'] AND $user['usergroupid'] != $user['pusergroupid'])
			{
				// check if there are other active subscriptions that set the same primary usergroup
				$subids = [0];
				foreach ($this->subscriptioncache AS $subcheck)
				{
					if ($subcheck['nusergroupid'] == $user['usergroupid'] AND $subcheck['subscriptionid'] != $subscriptionid)
					{
						$subids[] = $subcheck['subscriptionid'];
					}
				}
				if (!empty($subids))
				{
					$activesub = $assertor->getRow('vBForum:subscriptionlog', ['userid' => $userid, 'subscriptionid' => $subids], ['field' => 'expirydate', 'direction' => vB_dB_Query::SORT_DESC]);
				}
				if ($activesub)
				{
					// there is at least one active subscription with the same primary usergroup, so alter its resetgroup
					$assertor->update('vBForum:subscriptionlog', ['pusergroupid' => $user['pusergroupid']], ['subscriptionlogid' => $activesub['subscriptionlogid']]);
					// don't touch usertitle/displaygroup
					$user['pusergroupid'] = $user['usergroupid'];
					$sub['nusergroupid'] = 0;
				}
				else
				{
					$userdm->set('usergroupid', $user['pusergroupid']);
				}
			}
			else if ($user['isbanned'] AND $user['busergroupid'] == $sub['nusergroupid'])
			{
				$update_userban = true;
				$userbansql['usergroupid'] = $user['pusergroupid'];
			}
			$groups = iif(!empty($sub['membergroupids']), $sub['membergroupids'] . ',') . $sub['nusergroupid'];

			if (in_array ($user['displaygroupid'], explode(',', $groups)))
			{
				// they're displaying as one of the usergroups in the subscription
				$user['displaygroupid'] = 0;
			}
			else if ($user['isbanned'] AND in_array ($user['bandisplaygroupid'], explode(',', $groups)))
			{
				$update_userban = true;
				$userbansql['displaygroupid'] = 0;
			}

			// do their old groups still allow custom titles?
			$reset_title = false;
			if ($user['customtitle'] == 2)
			{
				$groups = empty($membergroupids) ? [] : $membergroupids;
				$groups[] = $user['pusergroupid'];
				$bf_ugp_genericpermissions = vB::getDatastore()->getValue('bf_ugp_genericpermissions');
				$usergroup = $assertor->getRow('usergroup', [
					vB_dB_Query::CONDITIONS_KEY=> [
						['field'=> 'usergroupid', 'value' => $groups, vB_dB_Query::OPERATOR_KEY => vB_dB_Query::OPERATOR_EQ],
						['field'=> 'genericpermissions', 'value' => $bf_ugp_genericpermissions['canusecustomtitle'], vB_dB_Query::OPERATOR_KEY => vB_dB_Query::OPERATOR_AND]
					]
				]);

				if (empty($usergroup['usergroupid']))
				{
					// no custom group any more lets set it back to the default
					$reset_title = true;
				}
			}

			if (($sub['nusergroupid'] > 0 AND $user['customtitle'] == 0) OR $reset_title)
			{
				// they need a default title
				$usergroup = $assertor->getRow('usergroup',['usergroupid' => $user['pusergroupid']]);
				if (empty($usergroup['usertitle']))
				{
					// should be a title based on minposts it seems then
					$usergroup = $assertor->getRow('usertitle',
						[
							vB_dB_Query::CONDITIONS_KEY => [['field'=> 'minposts', 'value' => $user['posts'], vB_dB_Query::OPERATOR_KEY => vB_dB_Query::OPERATOR_LTE]]
						],
						['field' => 'minposts', 'direction' => vB_dB_Query::SORT_DESC]
					);
				}

				if ($user['isbanned'])
				{
					$update_userban = true;
					$userbansql['customtitle'] = 0;
					$userbansql['usertitle'] = $usergroup['usertitle'];
				}
				else
				{
					$userdm->set('customtitle', 0);
					$userdm->set('usertitle', $usergroup['usertitle']);
				}
			}

			$userdm->set('membergroupids', implode(',', $membergroupids));
			$userdm->set('displaygroupid', $user['displaygroupid']);

			$userdm->save();
			unset($userdm);
			$assertor->update('vBForum:subscriptionlog',
				['status' => 0],
				['subscriptionid' => $subscriptionid, 'userid' => $userid]
			);

			if ($update_userban)
			{
				$assertor->update('userban', $userbansql, ['userid' => $user['userid']]);
			}

			$mysubs = $assertor->assertQuery('vBForum:subscriptionlog', ['status' => 1, 'userid' => $userid]);
			foreach ($mysubs as $mysub)
			{
				$this->build_user_subscription($mysub['subscriptionid'], -1, $userid, $mysub['regdate'], $mysub['expirydate']);
			}

			// Legacy Hook 'paidsub_delete' Removed //
		}
	}

	/**
	* Caches the subscriptions from the database into an array
	*/
	public function cache_user_subscriptions()
	{
		if (empty($this->subscriptioncache))
		{
			$permissions = vB::getDbAssertor()->assertQuery('vBForum:subscriptionpermission');
			$permcache = array();
			foreach ($permissions as $perm)
			{
				$permcache["$perm[subscriptionid]"]["$perm[usergroupid]"] = $perm['usergroupid'];
			}

			$subscriptions = vB::getDbAssertor()->assertQuery('vBForum:subscription', array(), array('field' => 'displayorder', 'direction' => vB_dB_Query::SORT_ASC));
			//$subscriptions = $this->registry->db->query_read_slave("SELECT * FROM " . TABLE_PREFIX . "subscription ORDER BY displayorder");
			foreach ($subscriptions as $subscription)
			{
				$subscription = array_merge($subscription, convert_bits_to_array($subscription['adminoptions'], $this->registry->bf_misc_adminoptions));
				if (!empty($permcache["$subscription[subscriptionid]"]))
				{
					$subscription['deniedgroups'] = 	$permcache["$subscription[subscriptionid]"];
				}
				$this->subscriptioncache["$subscription[subscriptionid]"] = $subscription;
			}
			unset($permcache);
		}
	}

	/**
	* Constructs the payment form
	*
	* @param	string	A 32 character hash corresponding to the entry in the paymentinfo table
	* @param	array	Array containing the `paymentapi` API information for the form to be constructed for
	* @param	array	Array containing specific data about the cost and time for the specific subscription period
	* @param	string	The currency of the cost
	* @param	array	Array containing the entry from the subscription table
	* @param	array	Array containing the userinfo of the user purchasing the subscription
	* @param	array	Additional information: [
    *                       "context" => "usersettings"|"registration" - context of the subscription.
	*                   ]
	*
	* @return	array|bool	The array containing the form data or false on error
	*/
	public function construct_payment($hash, $methodinfo, $timeinfo, $currency, $subinfo, $userinfo, $extra)
	{
		$obj = static::fetchPaymentMethodInstance($methodinfo);
		if (is_null($obj))
		{
			// maybe throw an error about the lack of a class?
			return false;
		}

		return $obj->generate_form_html($hash, $timeinfo['cost']["$currency"], $currency, $subinfo, $userinfo, $timeinfo, $extra);
	}

	/**
	* Prepares the API settings array
	*
	* @param	string	Serialized string
	*
	* @return	array	Array containing the settings after being converted to the correct index format
	*/
	public static function construct_payment_settings($serialized_settings)
	{
		$methodsettings = unserialize($serialized_settings);
		$settings = [];
		// could probably do with finding a nicer solution to the following
		$settings['_SUBSCRIPTIONOPTIONS'] = static::_SUBSCRIPTIONOPTIONS;
		if (is_array($methodsettings))
		{
			foreach ($methodsettings AS $key => $info)
			{
				$settings[$key] = $info['value'];
			}
		}
		return $settings;
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 114976 $
|| #######################################################################
\*=========================================================================*/
