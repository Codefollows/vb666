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

class shopify_Utility_Shopifywrapper
{
	use vB_Trait_NoSerialize;

	private $initialized;
	private bool $valid = true;

	private $apiVersion = '2022-07';
	private $config = [];

	public function __construct($config, $sessionStorage)
	{
		$this->config['apikey'] = $config['apikey'] ?? '';
		$this->config['apisecret'] = $config['apisecret'] ?? '';
		$this->config['adminaccesstoken'] = $config['adminaccesstoken'] ?? '';
		$this->config['storefrontaccesstoken'] = $config['storefrontaccesstoken'] ?? '';
		$this->config['store'] = $config['store'] ?? '';
		$this->config['apphostname'] = $config['apphostname'] ?? '';

		// todo: any error handling?
		$this->intializeShopifySDK($sessionStorage);
	}

	public function isConfigured() : bool
	{
		return $this->valid;
	}

	/**
	 * @return \Shopify\Clients\Rest
	 */
	/*
	private function getClient()
	{
		$client = new \Shopify\Clients\Rest($this->getStoreDomain(), $this->getAdminAPIAccessToken());
		return $client;
	}
	*/

	private function getStorefrontClient()
	{
		$storefrontClient = new \Shopify\Clients\Storefront($this->getStoreDomain(), $this->getStorefrontAPIAccessToken());

		return $storefrontClient;
	}

	private function queryProductsNextPage(\Shopify\Clients\Storefront $storefrontClient, $prevEndCursor = '', $perPage = 100)
	{
		$after = '';
		if ($prevEndCursor)
		{
			$after = ", after: \"$prevEndCursor\"";
		}

		// Note, product.status is apparently only available via the Admin API not Storefront API. I guess because
		// status is more related to the inventory management than the storefront? Not sure which field might give us
		// info about available quantity etc, if we might need that in the future.
		$graphql= <<<QUERY
{
	products (first: $perPage, sortKey: ID$after) {
		edges {
			node {
				id
				title
				handle
				updatedAt
			}
		}
		pageInfo{
			hasNextPage
			endCursor
		}
	}
}
QUERY;

		$response = $storefrontClient->query($graphql);
		$return = $response->getDecodedBody();

		return $return['data']['products'] ?? [
			'edges' => [],
			'pageInfo' => [
				'hasNextPage' => false,
				'endCursor' => '',
			],
		];
	}

	private function convertProductGidToId(string $gid) : string
	{
		// Interestingly, when fetched through the storefront API instead of admin API,
		// the ID is in the form of gid://shopify/Product/12345 isntead of just
		// 12345. And unfortunately, the extended form does not seem to work with
		// the storefront JS library...
		preg_match('@gid://shopify/[A-Z0-9_-]+/(?P<id>.*)@i', $gid, $matches);
		return $matches['id'] ?? $gid;
	}

	public function getProductsFromRemote(bool $sparse = true) : array
	{
		// For very large stores, we may want to break this up further...
		$products = [];
		$perPage = 100;
		$storeFront = $this->getStorefrontClient();
		$cursor = '';
		$hasNextPage = true;
		$perPage = 50;
		while ($hasNextPage)
		{
			$result = $this->queryProductsNextPage($storeFront, $cursor, $perPage);

			[
				'hasNextPage' => $hasNextPage,
				'endCursor' => $cursor,
			] = $result['pageInfo'];
			foreach ($result['edges'] AS $__node)
			{
				$__node['node']['id'] = $this->convertProductGidToId($__node['node']['id']);
				$products[] = $__node['node'];
			}
		}

		return $products;
	}

	/*
	// Debug function. No longer needed until we add a debug admincp page.
	public function checkStorefrontApi()
	{
		//https://github.com/Shopify/shopify-api-php/blob/main/docs/usage/storefront.md
		$response = $this->getStorefrontClient()->query(
			<<<QUERY
			{
				products (first: 10) {
					edges {
						node {
							id
							title
							descriptionHtml
						}
					}
				}
			}
			QUERY,
		);

		$return = $response->getDecodedBody();

		return $return;
	}
	*/

	/*
	public function generateCustomerAccessToken($email, $password) : array
	{
		// https://shopify.dev/api/examples/customer-accounts#create-an-access-token
		// You can use the "Shopify GraphiQL App" to help generate these mutation queries.
		// escape quotes in graphql
		$email = str_replace('"', '\\"', $email);
		$password = str_replace('"', '\\"', $password);
		$mutation = <<<EOT
			mutation {
				customerAccessTokenCreate(input: {
				  email:"$email",
				  password:"$password"
				}) {
				  customerAccessToken {
					accessToken
					expiresAt
				  }
				  customerUserErrors {
					message
				  }
				}
			  }
EOT;

		$response = $this->getStorefrontClient()->query($mutation);
		$decoded = $response->getDecodedBody();
		if (empty($decoded['data']['customerAccessTokenCreate']))
		{
			throw new Exception('Failed to create customer access token');
		}
		$result = $decoded['data']['customerAccessTokenCreate'];

		if (!empty($result['customerUserErrors']))
		{
			throw new Exception(implode("\n", $result['customerUserErrors']));
		}

		// Note, we should have an access token here. However, it seems to only be useful for
		// doing storefront API calls (e.g. via Shopify's JS SDK) as the customer, but it's
		// unclear if it's possible to actually log in the user into the online store like
		// you can with the MULTIPASS access tokens (https://shopify.dev/api/examples/customer-accounts#customeraccesstokencreatewithmultipass)
		// I.e. useful for an actual storefront app for a headless shopify store, but not so
		// useful for doing stuff with the Online Store that we're trying to work with.

		return $result['customerAccessToken'];
	}

	public function createOrUpdateShopifyCustomer(array $userinfo) : ?array
	{
		//Shopify customer accounts do not have usernames, but email, first name & last name (last two may not be unique).
		//As such I believe emails can uniquely identify a shopify customer account.
		$email = $userinfo['email'];
		$info = $this->searchShopifyCustomer($email);

		$session = $this->makeSession();
		// https://shopify.dev/api/admin-rest/2022-07/resources/customer#get-customers
		$urlIds = []; // WTF is this?
		$params = [];
		$customer = new \Shopify\Rest\Admin2022_07\Customer($session);
		if (!empty($info['id']))
		{
			// This is required to update the customer password. Some old community threads state
			// that updating passwords via admin API is unsupported, but it still works.
			$customer->id = $info['id'];
		}
		foreach ($userinfo AS $__key => $__val)
		{
			$customer->$__key = $__val;
		}

		// metafields not working atm.
		//if (!empty($userinfo['metafields']))
		//{
		//	$customer->metafields = $userinfo['metafields'];
		//}

		$customer->save(true);

		$id = $customer->id ?? null;
		if (empty($id))
		{
			throw new Exception('Failed to create shopify customer');
		}
		// seems customer::search is cached, and usually fails to return a newly created custoemr.
		// trying ::find() instead
		$params = [];
		$result = \Shopify\Rest\Admin2022_07\Customer::find($session, $id, $params);

		return get_object_vars($result);
	}

	public function searchShopifyCustomer(string $email) : ?array
	{
		// This isn't actually a var, just a way to do a block comment while trying to block-comment-out some
		// chunks of code.
		$comment = <<<'EOCOMMENT'
Rant:
Shopify docs seem to assume the central app server installed/authenticated via OAuth path.
So it has a lot of sample code that begins with fetching a session, which is created & stored as part of
\Shopify\Auth\OAuth::callback()...
but using the Shopify-admin install method, we DON'T HAVE THIS SESSION/SESSIONID.
I'm guessing there's some kind of internal offline session that was created on Shopify's end, but hell if
we know what that looks like from our end, because again, we never went through that session create logic,
as far as I can tell. Even though the ::initialize() call requires a session storage, nothing in its stack
or more reasonably the \Shopify\Clients\Rest instance stack generates/fetches a session. In fact, documentation
using the Rest class actually begins with... fetching a session like
Shopify\Utils::loadCurrentSession($headers, $cookies, $isOnline);
where $headers and $cookies, poorly documented, is presumably the headers & cookies from when the shopify admin
is calling the app server(?)........ since currently our code is being generated from the app-server end, not
Shopify's end, we don't have these headers and cookies.
EOCOMMENT;
		//$this->getClient()->get('customer');
		$session = $this->makeSession();
		// https://shopify.dev/api/admin-rest/2022-07/resources/customer#get-customers-search
		$urlIds = []; // WTF is this?
		// https://shopify.dev/api/usage/search-syntax
		$params = [
			"query" => "email:$email ",
		];
		$result = \Shopify\Rest\Admin2022_07\Customer::search($session, $urlIds, $params);
		$customers = $result['customers'][0] ?? null;
		return $customers;
	}

	private function shopifyCustomerExists(string $email) : bool
	{
		$customer = $this->searchShopifyCustomer($email);
		return !empty($customer['id']);
	}

	public function getCustomers() : array
	{
		//$this->getClient()->get('customer');
		$session = $this->makeSession();
		// https://shopify.dev/api/admin-rest/2022-07/resources/customer#get-customers
		$urlIds = [];
		$params = [];
		$result = \Shopify\Rest\Admin2022_07\Customer::all($session, $urlIds, $params);

		return $result;
	}

	private function makeSession()
	{
		$isOnline= false;
        $sanitizedShop = \Shopify\Utils::sanitizeShopDomain($this->getStoreDomain());
        $mySessionId = "offline_{$sanitizedShop}";
		// Not sure what the repurcussions of recreating this "state" every request are...
		$state = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $session = new \Shopify\Auth\Session($mySessionId, $sanitizedShop, $isOnline, $state);
		$session->setAccessToken($this->getAdminAPIAccessToken());
        //$session->setScope($this->getShopifyAPIScopes());
		return $session;
	}
	*/

	private function intializeShopifySDK(Shopify\Auth\SessionStorage $sessionStorage)
	{
		if (!is_null($this->initialized))
		{
			return $this->initialized;
		}

		try
		{
			$isEmbeddedApp = false;
			// this controls whether client will use the secretkey or store-specific accesstoken ...
			// and for some reason that I haven't figured out yet, the accesstoken DOES NOT WORK for the
			// admin API, and neither works for the storefront API...
			$isPrivateApp = false; // true;
			$userAgentPrefix = 'via vBulletin';
			// https://github.com/Shopify/shopify-api-php/blob/main/docs/getting_started.md#set-up-the-library
			require_once(DIR . '/packages/shopify/vendor/autoload.php');
			\Shopify\Context::initialize(
				$this->getAppApiKey(),
				$this->getAppApiSecret(),
				$this->getShopifyAPIScopes(),
				$this->getAppHostname(),
				$sessionStorage,
				$this->apiVersion,
				$isEmbeddedApp,
				$isPrivateApp,
				$userAgentPrefix
			);
		}
		catch (Throwable $e)
		{
			//we don't want misconfigurations or other errors in the product to bring down the entire site.
			$this->valid = false;
		}

		//even if initialization failed, we don't want to try again.
		$this->initialized = true;
	}

	private function getAppApiKey() : string
	{
		return $this->config['apikey'];
	}

	private function getAppApiSecret() : string
	{
		return $this->config['apisecret'];
	}

	/*
	private function getAdminAPIAccessToken() : string
	{
		// Documentation for "public" apps use OAuth to generate an access token... this is if the merchant
		// is installing the app from the shopify app store, it would presumably pop up a access request in
		// the shopify admin, and upon admin's granting the request, the app would receive & permanently store
		// the access token...

		// It sounds like the api secret is a 'static' (shared) secret between Shopify and the app, NOT the store, and
		// the access token is the actual token to grant access to a specific store to this app...
		// https://community.shopify.com/c/shopify-apis-and-sdks/what-is-the-difference-between-api-key-secret-key-and-refresh/td-p/170004
		// For the OAuth process, see https://github.com/Shopify/shopify-api-php/blob/main/docs/usage/oauth.md and
		// https://www.shopify.com/partners/blog/17056443-how-to-generate-a-shopify-api-token
		// For now, we're assuming the access token is being generated in Shopify admin like:
		// https://help.shopify.com/en/manual/apps/custom-apps#create-and-install-a-custom-app

		return $this->config['adminaccesstoken'];
	}
	*/

	private function getStorefrontAPIAccessToken() : string
	{
		// Different APIs apparently have separate access tokens. Notably, Storefront API is meant for client facing
		// logic. In simpelr terms, this access code will be revealed publicly (e.g. for javascript SDK usage) and
		// has very limited access vs Admin API.
		//https://shopify.dev/api/examples/storefront-api#step-2-generate-a-storefront-api-access-token
		return $this->config['storefrontaccesstoken'];
	}

	public function getClientsidePublicInfo() : array
	{
		// Just a public wrapper for some data that's meant to be exposed clientside, but
		// making this aspect more explicit via the name.
		return [
			// storefront access token required for Shopify's JS SDK
			'access_token' => $this->getStorefrontAPIAccessToken(),
			// already should be a public piece of info (literally the store URL), but also required for
			// Shopify's JS SDK
			'store' => $this->getStoreDomain(),
		];
	}

	private function getStoreDomain() : string
	{
		return $this->config['store'];
	}

	private function getAppHostname() : string
	{
		// .... it seems like this is supposed to be the app URL, but need to find specific documentation about this...
		// it's also not clear how this is used (can possibly used for OAuth redirect path?)

		return $this->config['apphostname'];
	}

	private function getShopifyAPIScopes() : array
	{
		// https://shopify.dev/api/usage/access-scopes
		// probably needs to match the scopes set for the custom app added in shopify admin..
		// You can find this in https://{YOURSTORESUBDOMAIN}.myshopify.com/admin/settings/apps/development/{YOURAPPID}/configuration
		// or Shopify admin > Settings (very bottom left) > "Apps and sales channels" in left column > Develop Apps > {The custom app you created}
		// > Configuration > Review configuration, then on that page, look for the access scopes granted, e.g. "Admin API access scopes"
		return [
			// Admin API access scopes
			/*
			'write_customers',
			'read_customers',
			'read_product_listings',
			'read_products',
			*/
			// I'm not entirely sure WHICH access scopes we need to define during ::initialize()... Admin API or Storefront API or both...
			// Currently, we'll only use the Storefront client, so let's only define those.
			// Storefront access scopes:
			// required for https://shopify.dev/api/storefront/2022-07/mutations/customeraccesstokencreate
			//'unauthenticated_write_customers ',
			// Pull product data for our internal map
			'unauthenticated_read_product_listings',

		];
	}

	/*
	private function getShopifyStorefrontAPIAccessScopes()
	{
		return [
			// required for https://shopify.dev/api/storefront/2022-07/mutations/customeraccesstokencreate
			'unauthenticated_write_customers ',
			// I believe this is required to pull product data.
			'unauthenticated_read_product_listings',
		];
	}
	*/
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 19:44, Thu Apr 14th 2022
|| # CVS: $RCSfile$ - $Revision: 105377 $
|| #######################################################################
\*=========================================================================*/
