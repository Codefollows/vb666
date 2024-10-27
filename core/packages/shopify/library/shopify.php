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

class shopify_Library_Shopify extends vB_Library implements shopify_Interface_ProductProvider
{
	private bool $productsFetched = false;
	private $productsCache = [];
	private $productHandleToIdMap = [];
	private $productIdToHandleMap = [];
	const PRODUCT_CACHE_DS_KEY = 'shopify_products';

	private function ensureProducts()
	{
		if ($this->productsFetched)
		{
			return;
		}
		$this->productsFetched = true;

		$datastore = vB::getDatastore();
		$products = $datastore->getValue(static::PRODUCT_CACHE_DS_KEY);

		if (empty($products))
		{
			$this->refreshProducts();
		}
		else
		{
			$products = json_decode($products, true);
			$this->rebuildLocalProductCaches($products);
		}
	}

	private function refreshProducts()
	{
		$wrapper = static::getDefaultWrapperSingleton();
		$products = $wrapper->getProductsFromRemote();

		$datastore = vB::getDatastore();
		$datastore->build(static::PRODUCT_CACHE_DS_KEY, json_encode($products), 0);
		$this->rebuildLocalProductCaches($products);
	}

	private function rebuildLocalProductCaches($products)
	{
		$this->productsCache = $products;

		// Work around for some stores (and all dev stores) being password protected. Fetch the
		// map via API instead of trying to fetch a productid via a product page JSON access.
		$this->productHandleToIdMap = array_column($this->productsCache, 'id', 'handle');
		$this->productIdToHandleMap = array_flip($this->productHandleToIdMap);
	}

	public function getProducts() : array
	{
		$this->ensureProducts();
		return $this->productsCache;
	}

	public function convertUrlTextToIdAndLabel(string $productUrl) : array
	{
		$storeinfo = $this->getStoreUrlInfo();
		$shopifyStoreDomain = $storeinfo->domain;
		$delim = '@';
		// allow  'https://',  'http://', or schemeless '//'.
		$optional_scheme = '(?:(?:https?://)|//)?';
		// the bbcode will look like
		//     [buy]https://something.myshopify.com/products/abc-defg|Cool Product[/buy]
		//     [buy]https://something.myshopify.com/products/abc-defg[/buy]
		// Based on https://stackoverflow.com/a/36564776, should match everything up to a punctuation or newline/end of line.
		// Also note here that we're using the pipe | as a delimiter between the URL and the optional label
		$url_check = '(?P<url>' . $optional_scheme . preg_quote($shopifyStoreDomain, $delim) . '/products/(?P<product_handle>[^,\s()<>|]+)' . ')';
		$optional_label = '(?:\|(?P<label>.*))?';
		// case insensitive
		$modifiers = 'i';
		$regex = $delim . $url_check . $optional_label .  $delim . $modifiers;

		$label = $productUrl;
		$productid = '';

		if (preg_match($regex, $productUrl, $matches))
		{
			// We may have a {url}|{Label}, but if not, just use the full text as the label like normal.
			$label = !empty($matches['label']) ? $matches['label'] : $matches[0];
			$productid = $this->convertProductHandleToId($matches['product_handle']);
		}

		// todo: add struct for this?
		return ['productid' => $productid, 'label' => $label,];
	}

	public function convertProductHandleToId(string $productHandle) : string
	{
		$this->ensureProducts();
		$productHandle = $this->trimWhitespace($productHandle);
		// If we don't have this handle, try once to fetch from shopify, as this might be a
		// newly added product. Otherwise, cache it locally assuming it's an error.
		// TODO: webhooks to sync/update the product datastore.
		if (!isset($this->productHandleToIdMap[$productHandle]))
		{
			$this->refreshProducts();
			if (!isset($this->productHandleToIdMap[$productHandle]))
			{
				// skip expensive remote call for missing product for this page load.
				$this->productHandleToIdMap[$productHandle] = '';
			}
		}

		return $this->productHandleToIdMap[$productHandle];
	}


	private function trimWhitespace(string $text) : string
	{
		// It seems like there can be zero-width-spaces injected next to URLs while copy pasting in CKEditor. I'm not entirely
		// sure where this is coming from and how frequently it might happen, but it DOES happen and it wrecks the product_handle
		// lookup if we don't clean it up. However, I'm not sure what regex would be best. This assumes that the string is in
		// UTF-8, and while URLs will be, this is part of the post text...
		$trim_spaces = '@(?:[\x{200B}-\x{200D}\x{FEFF}\s])*(?P<handle>[^\x{200B}-\x{200D}\x{FEFF}\s]*)(?:[\x{200B}-\x{200D}\x{FEFF}\s])*@u';
		$string = vB::getString();
		$text = $string->toUtf8($text);
		preg_match($trim_spaces, $text, $matches);

		return $matches['handle'];
	}

	public function convertProductIdToHandle(string $productId) : string
	{
		$this->ensureProducts();
		if (!isset($this->productIdToHandleMap[$productId]))
		{
			$this->refreshProducts();
			if (!isset($this->productIdToHandleMap[$productId]))
			{
				$this->productIdToHandleMap[$productId] = '';
			}
		}

		return $this->productIdToHandleMap[$productId];
	}

	public function convertProductIdToUrl(string $productId) : string
	{
		$storeinfo = $this->getStoreUrlInfo();

		$producthandle = $this->convertProductIdToHandle($productId);
		return $storeinfo->storeurlprefix . '/products/' . $producthandle;
	}

	public function isStoreUrl(string $url) : bool
	{
		$storeinfo = $this->getStoreUrlInfo();
		$shopifyStoreDomain = $storeinfo->domain;

		$url = $this->trimWhitespace($url);
		$delim = '@';
		// allow  'https://',  'http://', or schemeless '//'.
		$optional_scheme = '(?:(?:https?://)|//)?';
		// Based on https://stackoverflow.com/a/36564776, should match everything up to a punctuation or newline/end of line.
		$regex = $delim  . '^' . $optional_scheme . preg_quote($shopifyStoreDomain, $delim) . $delim;
		return preg_match($regex, $url);
	}

	public function isStoreProductUrl(string $url) : bool
	{
		$storeinfo = $this->getStoreUrlInfo();
		$shopifyStoreDomain = $storeinfo->domain;

		$url = $this->trimWhitespace($url);
		$delim = '@';
		// allow  'https://',  'http://', or schemeless '//'.
		$optional_scheme = '(?:(?:https?://)|//)?';
		// Based on https://stackoverflow.com/a/36564776, should match everything up to a punctuation or newline/end of line.
		$regex = $delim  . '^' . $optional_scheme . preg_quote($shopifyStoreDomain, $delim) . '/products/[^,\s()<>]+(?:[^,[:punct:]\s]|/)' . $delim;
		return preg_match($regex, $url);
	}

	private static function cleanStoreUrlInternal($shopifyStoreDomain) : shopify_Utility_shopifyStoreInfo
	{
		// Split store into scheme & domain so that we can more-robustly match any forms that
		// come from user data in cleanUpBbcode() later.
		// Also, the Shopify library apparently requires protocolless domains or else they'll break.
		$preferredScheme = '';
		$delim = '@';
		// allow  'https://',  'http://', or schemeless '//'.
		$scheme_grab = '^(((?P<scheme>https?)://)|//)?';
		$url_grab = '(?P<storeurl>.*)';
		$regex = $delim . $scheme_grab . $url_grab. $delim;
		if (preg_match($regex, $shopifyStoreDomain, $matches))
		{
			$preferredScheme = $matches['scheme'];
			$shopifyStoreDomain = rtrim($matches['storeurl'], '/');
		}
		else
		{
			$shopifyStoreDomain = rtrim($shopifyStoreDomain, '/');
		}
		$preferredScheme = $preferredScheme ? $preferredScheme . '://' : /*'//'*/ 'http://';

		// Store preferred URL prefix for buy bbcode rendering later.
		$storeUrlPrefix = $preferredScheme . $shopifyStoreDomain;

		return new shopify_Utility_shopifyStoreInfo(
			$preferredScheme,
			$shopifyStoreDomain,
			$storeUrlPrefix
		);
	}

	private $config = [];
	public function getStoreUrlInfo() : shopify_Utility_shopifyStoreInfo
	{
		if (empty($this->config))
		{
			$this->config = static::getShopifyStoreConfig();
		}

		return $this->config['storeinfo'];
	}

	protected static shopify_Utility_Shopifywrapper $wrapper_instance;
	// These are some methods meant for quick prototyping while we haven't sorted out exactly how the api key/secret/accesstokens will be
	// ferried & stored.
	public static function getDefaultWrapperSingleton() : shopify_Utility_Shopifywrapper
	{
		if (!isset(static::$wrapper_instance))
		{
			$sessionStorage = new shopify_Library_SessionStorage();
			static::$wrapper_instance = new shopify_Utility_Shopifywrapper(static::getShopifyStoreConfig(), $sessionStorage);
		}

		return static::$wrapper_instance;
	}

	// This used to be a public static function as bbcodes and such used to access this directly, and this
	// used to be part of another class.
	// Those accesses have been changed to use getStoreUrlInfo() instead, so switched it to private.
	// Probably does not have to be static any longer
	private static function getShopifyStoreConfig() : array
	{
		$vboptions = vB::getDatastore()->getValue('options');
		$config = [
			'apikey' => $vboptions['shopify_apikey'] ?? '',
			'apisecret' => $vboptions['shopify_apisecret'] ?? '',
			'adminaccesstoken' => $vboptions['shopify_adminaccesstoken'] ?? '',
			'storefrontaccesstoken' => $vboptions['shopify_storefrontaccesstoken'] ?? '',
			// 'store' set below because it needs a bit of cleaning
			'apphostname' => $vboptions['frontendurl'],
		];

		$config['apphostname'] ??= vB::getDatastore()->getOption('frontendurl');

		// Set only the domain for 'store'. Shopify internal libraries will apparently always prefix the protocol to it.
		$storeinfo = static::cleanStoreUrlInternal($vboptions['shopify_store'] ?? '');
		$config['store'] = $storeinfo->domain;
		// getStoreUrlInfo() used to call this and did all the logic of cleanStoreUrlInternal() on its own before I found
		// out that the Shopify libraries also require a protocolless URL for their store domain params.
		// In order to avoid some circular logic and dupe code, I moved the old cleaning logic into cleanStoreUrlInternal(),
		// then just pass that through to getStoreUrlInfo() via this value.
		$config['storeinfo'] = $storeinfo;

		return $config;
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 19:44, Thu Apr 14th 2022
|| # CVS: $RCSfile$ - $Revision: 105377 $
|| #######################################################################
\*=========================================================================*/
