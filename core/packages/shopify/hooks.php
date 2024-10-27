<?php
class shopify_Hooks
{
	// extend vB_Api_Tag::fetchTagList()
	public static function hookExtendBbcodeTagList($params)
	{
		if(!vB_Api::instance('shopify:Shopify')->isEnabled())
		{
			return;
		}

		// TODO: this callback may not actually work, as 2nd param is NOT a static function
		// and first param is not an instance...
		// the instancing may occur as part of the bbcode "bucket", so not sure how to set that up
		// ahead of time....


		// [buy=shopify]
		$bbcodeInfo = [
			'handlers' => [],
			'stop_parse' => true,
			'disable_smilies' => true,
			'strip_empty' => true,
			'disable_urlconversion' => true,
		];

		$params['tag_list']['option']['buy'] ??= $bbcodeInfo;
		$params['tag_list']['no_option']['buy'] ??= $bbcodeInfo;
		$params['tag_list']['option']['buy']['handlers'][] = 'shopify_bbcode_buy';
		$params['tag_list']['no_option']['buy']['handlers'][] = 'shopify_bbcode_buy';
	}

	public static function hookConvertUrlToBbcodeCallback($params)
	{
		if(!vB_Api::instance('shopify:Shopify')->isEnabled())
		{
			return;
		}

		$shopifyLibrary = vB_Library::instance('shopify:Shopify');
		// e.g. replace with custom bbcode instead of url bbcode.
		// Don't forget to check 'handled'
		$matches = $params['matches'];
		if (!$params['handled'] AND $shopifyLibrary->isStoreProductUrl($matches[0]))
		{
			// don't forget to set 'handled' to true
			$params['handled'] = true;
			$params['replace'] = "[buy=shopify]$matches[0][/buy]";
		}
	}

	public static function hookGetBbcodeRenderOptions($params)
	{
		if(!vB_Api::instance('shopify:Shopify')->isEnabled())
		{
			return;
		}

		// Readonly
		// String 'EDITOR'|'NORMAL_FRONTEND'. EDITOR means it's for ckeditor. NORMAL_FRONTEND means for regular browser view.
		// Sometimes, you do not want complex HTML in the editor view.
		switch ($params['context'])
		{
			case 'NORMAL_FRONTEND':
				$params['renderOptions']['render_shopify'] = true;
				break;

			case 'LIBRARY':
			case 'EDITOR':
			default:
				$params['renderOptions']['render_shopify'] = false;
				break;
		}
	}

	public static function hookRssFetchXmlPreGet($params)
	{
		if(!vB_Api::instance('shopify:Shopify')->isEnabled())
		{
			return;
		}

		// work around for Shopify dev-stores being password protected.
		// Submit password, grab cookie, add cookie to actual content-get request.

		$url = $params['url'];
		$vurl = $params['vurl'];

		/** @var shopify_Library_Shopify */
		$shopifyLibrary = vB_Library::instance('shopify:Shopify');
		if (!$shopifyLibrary->isStoreUrl($url))
		{
			return;
		}

		$password = vB::getDatastore()->getOption('shopify_store_password') ?? '';
		if (empty($password))
		{
			return;
		}

		$storeinfo = $shopifyLibrary->getStoreUrlInfo();

		$passwordurl = $storeinfo->storeurlprefix . '/password';
		$useragent = 'vBulletin via cURL/PHP';
		$vurl2 = vB::getUrlLoader();
		$vurl2->setOption(vB_Utility_Url::FOLLOWLOCATION, 1);
		$vurl2->setOption(vB_Utility_Url::TIMEOUT, 5);
		$vurl2->setOption(vB_Utility_Url::USERAGENT, $useragent);
		$check = $vurl2->get($passwordurl);

		$domdoc = new DOMDocument();
		@$domdoc->loadHTML($check['body']);
		$xpath = new DomXPath($domdoc);
		$query = $xpath->query('//input[@name="authenticity_token"]/@value');
		$authtokens = [];
		foreach ($query AS $__node)
		{
			$authtokens[] = $__node->nodeValue;
		}
		$authtoken = $authtokens[0] ?? '';

		// going through curl directly because vburl currently doesn't seem to return cookie headers
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $passwordurl);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
			'authenticity_token' => $authtoken,
			'password' => $password,
		]));
		$check2 = curl_exec($ch);
		preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $check2, $matches);
		$cookies = array();
		foreach($matches[1] as $item) {
			parse_str($item, $cookie);
			$cookies = array_merge($cookies, $cookie);
		}

		$auth = $cookies['storefront_digest'] ?? '';

		$vurl->setOption(vB_Utility_Url::USERAGENT, $useragent);
		$vurl->setOption($vurl::COOKIE, "storefront_digest=$auth;");
	}

	public static function hookRssFeedMapItem($params)
	{
		if(!vB_Api::instance('shopify:Shopify')->isEnabled())
		{
			return;
		}

		/** @var shopify_Library_Shopify */
		$shopifyLibrary = vB_Library::instance('shopify:Shopify');
		if (!$shopifyLibrary->isStoreUrl($params['url']))
		{
			return;
		}

		if ($params['type'] != 'atom')
		{
			return;
		}

		['productid' => $productid, ] = $shopifyLibrary->convertUrlTextToIdAndLabel($params['out_item']['url']);
		if ($productid)
		{
			$productidSafe = htmlentities($productid);
			$buy_button = <<<EOT
<div class="js-vb-shopify-buy-link vb-shopify-rssfeed-item" data-productid="$productidSafe"></div>
EOT;
			/*
			$html_description = $params['out_item']['description'];
			$params['out_item']['description'] = <<<EOT
<div class="shopify-rssfeed-item">
	<div class="shopify-rssfeed-description">$html_description</div>
	<div class="shopify-rssfeed-buybutton">$buy_button</div>
</div>
EOT;
			*/
			$params['out_item']['description'] = $buy_button;
		}
	}
}
