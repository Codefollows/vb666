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

if (!isset($GLOBALS['vbulletin']->db))
{
	exit;
}

/**
* Class that provides payment verification and form generation functions
*
* @package	vBulletin
* @date		$Date: 2024-02-14 14:29:40 -0800 (Wed, 14 Feb 2024) $
*/
class vB_PaidSubscriptionMethod_2checkout extends vB_PaidSubscriptionMethod
{
	/**
	* Display feedback via payment_gateway.php when the callback is made
	*
	* @var	bool
	*/
	var $display_feedback = true;

	/**
	* Perform verification of the payment, this is called from the payment gateway
	*
	* @return	bool	Whether the payment is valid
	*/
	function verify_payment()
	{
		// use _GET rather than GPC since we dont want the value changed
		$unclean_total = $_REQUEST['total'];

		$this->registry->input->clean_array_gpc('r', array(
			'order_number'  => vB_Cleaner::TYPE_STR,
			'key'           => vB_Cleaner::TYPE_STR,
			'cart_order_id' => vB_Cleaner::TYPE_STR,
			'total'         => vB_Cleaner::TYPE_NUM,
		));

		if (!$this->test())
		{
			$this->error = 'Payment processor not configured';
			return false;
		}

		$this->transaction_id = $this->registry->GPC['order_number'];
		$check_hash = strtoupper(md5($this->settings['secret_word'] . $this->settings['twocheckout_id'] . $this->registry->GPC['order_number'] . $unclean_total));

		if ($check_hash == $this->registry->GPC['key'])
		{
			$this->paymentinfo = vB::getDbAssertor()->getRow('vBForum:getPaymentinfo', array('hash' => $this->registry->GPC['item_number']));
			// lets check the values
			if (!empty($this->paymentinfo))
			{
				$this->paymentinfo['currency'] = 'usd';
				$this->paymentinfo['amount'] = $this->registry->GPC['total'];
				// dont need to check the amount since 2checkout dont include the currency when its sent back
				// the hash helps us get around this though
				$this->type = 1;
				return true;
			}
			else
			{
				$this->error = 'Duplicate transaction.';
			}
		}
		else
		{
			$this->error = 'Hash mismatch, please check your secret word.';
		}
		return false;
	}

	/**
	* Test that required settings are available, and if we can communicate with the server (if required)
	*
	* @return	bool	If the vBulletin has all the information required to accept payments
	*/
	function test()
	{
		return (!empty($this->settings['secret_word']) AND !empty($this->settings['twocheckout_id']));
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
	function generate_form_html($hash, $cost, $currency, $subinfo, $userinfo, $timeinfo, $extra = [])
	{
		global $vbphrase, $vbulletin, $show;

		$item = $hash;

		$form['action'] = 'https://www.2checkout.com/cgi-bin/sbuyers/cartpurchase.2c';
		$form['method'] = 'get';

		// load settings into array so the template system can access them
		$settings =& $this->settings;
		$subinfo['twocheckout_prodid'] = htmlspecialchars_uni($timeinfo['twocheckout_prodid']);

		$templater = vB_Template::create('subscription_payment_2checkout');
			$templater->register('cost', $cost);
			$templater->register('item', $item);
			$templater->register('settings', $settings);
			$templater->register('subinfo', $subinfo);
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
