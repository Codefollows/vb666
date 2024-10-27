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
 * @package vBulletin
 */

/**
 * @package vBulletin
 */
class shopify_BbCode_Buy extends vB_BbCode
{
	public const TYPE = 'Buy';

	private $preferredScheme = '';
	private $shopifyStoreDomain = '';
	private $storeUrlPrefix = '';

	private shopify_Interface_ProductProvider $productProvider;
	public function __construct($record, $modified = false)
	{
		parent::__construct($record, $modified);

		/** @var shopify_Library_Shopify */
		$library = vB_Library::instance('shopify:Shopify');
		$this->setProductProvider($library);

		$storeinfo = $library->getStoreUrlInfo();
		$this->setStoreInfo($storeinfo);

		$this->updateProductID();
	}

	private function setProductProvider(shopify_Interface_ProductProvider $productProvider)
	{
		// consolidate any provider init logic into a single function so we can easily swap it in testing if needed
		// Did not want to noise the constructor signature with provider param in case we need to change the parent
		// constructor signature later
		$this->productProvider = $productProvider;
	}

	private function setStoreInfo(shopify_Utility_shopifyStoreInfo $storeinfo)
	{
		// See setProductProvider above. Similar idea.
		$this->preferredScheme = $storeinfo->scheme;
		$this->shopifyStoreDomain = $storeinfo->domain;
		$this->storeUrlPrefix = $storeinfo->storeurlprefix;
	}

	private function updateProductID()
	{
		// this is downstream of constructor because it depends on productProvider, which isn't available statically ATM.
		$bbcode_data = $this->record['data']['bbcode_data'] ?? '';
		[
			'productid' => $this->record['data']['productid'],
			'label' => $this->record['data']['label'],
		] = $this->productProvider->convertUrlTextToIdAndLabel($bbcode_data);
	}


	public static function generateFreshFromDataAndOption(vB_BbCodeHelper $bbCodeHelper, string $data, ?string $option) : static
	{
		$record = static::getDefaultRecord();
		// set hash
		$record['hash'] = static::getHash($bbCodeHelper, $data, $option);

		// constructor will handle populating the data, because that depends on instance properties (productProvider)
		$record['data'] = [
			'bbcode_data' => $data,
		];
		$record['expires'] = 0;
		$modified = true;
		return new static($bbCodeHelper, $record, $modified);
	}

	protected function updateDataAndexpires() : void
	{
		// Default, cache for 1 day. TODO: figure out if there's a better dynamic cache time.
		$this->record['expires'] = time() + 86400;

		$this->updateProductID();

		// Mark for update
		$this->modified = true;
	}

	// [bbcode:option]data[/bbcode]
	public static function getHash(vB_BbCodeHelper $bbCodeHelper, string $data, ?string $option) : string
	{
		$unique = [
			'data' => $data,
		];
		return md5(json_encode($unique));
	}

	public function canHandleBbCode($data, $option) : bool
	{
		// this function is to allow for multiple products to handle the same
		// bbcode with different options or data, e.g. different shop handlers
		// that can handle [buy] bbcodes w/ different store links, or different
		// video handlers that each handle a specific video provider.


		// check if data / option is for this shopify domain, return true, i.e.
		// $checkOption: [buy=shopify]...[/buy]
		// $checkData: [buy]{SPECIFIED_SHOPIFY_STORE_URL}...[/buy]
		$checkOption = (stripos($option, 'shopify') !== false);
		$checkData = (strpos($data, $this->shopifyStoreDomain) !== false);

		return ($checkOption OR $checkData);
	}

	private $doRenderShopify = false;
	public function setRenderOptionsAndContext(array $renderOptions, array $renderContext) : void
	{
		$this->doRenderShopify =  $renderOptions['render_shopify'] ?? false;
	}


	// [bbcode:option]data[/bbcode]
	public function renderBbCode(string $data, ?string $option) : string
	{
		// e.g. editor view won't have doRenderShopify.
		// productid is set during intial construction via $this->updateProductID().
		// This was originally in case we wanted to store this in the DB, but currently
		// is not being stored.
		if (!$this->doRenderShopify OR empty($this->record['data']['productid']))
		{
			// [buy=option], or [buy].
			$option = $option ? '=' . $option : '';
			return "[buy{$option}]{$data}[/buy]";
		}
		// If we never plan on using the bbcode_data table, we can simplify this function by
		// getting rid of the empty record.data.productid check above, and doing
		// ['productid' => $productid, 'label' => $label, ] = $this->productProvider->convertUrlTextToIdAndLabel($data);

		//Decided against using the buy;PRODUCTID because that was tricky when editing.
		//Just alwasy refetch the productid from data always.

		// We could check $provider == 'shopify', but currently there isn't a need to over updating canHandleBbCode() to just
		// skip cases we don't want to handle.
		//$provider = $option;
		$productid = $this->record['data']['productid'];
		$html = '';
		$label = $this->record['data']['label'] ?? $data;

		$productidSafe = htmlentities($productid);
		//$url = $this->productProvider->convertProductIdToUrl($productid);
		//$urlSafe = htmlentities($url);
		/*
		$html = <<<EOT
<a href="$urlSafe" class="js-vb-shopify-buy-link" data-productid="$productidSafe">$data</a>
EOT;
		*/
		// Started off with a div to work around link auto parsing (need some way to register this as a no-parse).
		// May want to go back to anchors to handle cases when the JS fails to load, but not sure if that'll cause its
		// own problems yet with CKE link button handling.
		$html = <<<EOT
<div class="js-vb-shopify-buy-link vb-shopify-bbcode-buy-item" data-productid="$productidSafe">$label</div>
EOT;

		return $html;
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 107965 $
|| #######################################################################
\*=========================================================================*/
