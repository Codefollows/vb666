<?php
/*========================================================================*\
|| ###################################################################### ||
|| # vBulletin  - Licence Number VBC58C8600
|| # ------------------------------------------------------------------ # ||
|| # Copyright 2000-2022 MH Sub I, LLC dba vBulletin. All Rights Reserved.  # ||
|| # This file may not be redistributed in whole or significant part.   # ||
|| # ----------------- VBULLETIN IS NOT FREE SOFTWARE ----------------- # ||
|| # http://www.vbulletin.com | http://www.vbulletin.com/license.html   # ||
|| ###################################################################### ||
\*========================================================================*/

class shopify_Api_Shopify extends vB_Api
{
	protected $productid = 'shopify';

	private shopify_Utility_Shopifywrapper $shop;

	protected $disableWhiteList = [
		// This mainly came out of frontend tests, but this gets called as part of the header_head hooks.
		// Not sure atm if this failing would cause any cascading errors, but it doesn't harm anything
		// to allow-list it.
		'getClientsideAccessToken',
	];

	public function __construct()
	{
		parent::__construct();

		$this->shop = shopify_Library_Shopify::getDefaultWrapperSingleton();
	}

	public function isEnabled() : bool
	{
		return $this->shop->isConfigured();
	}

	public function getClientsideAccessToken() : array
	{
		if(!$this->isEnabled())
		{
			return [];
		}

		// Unlike the Admin API, the Storefront API is meant for frontend / client usage (e.g. javascript),
		// so its access token is NOT secret and meant to be public. Note that the storefront API has
		// restricted access to various things unlike the Admin API, & each app must declare the access
		// scopes for the different APIs it uses.
		// -- https://www.shopify.com/partners/blog/storefront-api-learning-kit
		return $this->shop->getClientsidePublicInfo();
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 19:44, Thu Apr 14th 2022
|| # CVS: $RCSfile$ - $Revision: 105377 $
|| #######################################################################
\*=========================================================================*/
