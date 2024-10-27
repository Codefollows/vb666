===========================
===========================
API Credentials:
===========================
===========================
For the vBulletin Integration, you must set up a "Custom App" for your Shopify store.
You will need to know your Shopify store URL, Shopify admin URL & login information.
Follow this Shopify documentation to set up your Custom App:
https://help.shopify.com/en/manual/apps/custom-apps#enable-custom-app-development-from-the-shopify-admin
For the "Create and install a custom app" step, you can use any name for yoru Custom App, it will not matter for vBulletin.
The important part is to configure the "Storefront API scopes" (NOT the Admin API) and enable the following scopes:
* unauthenticated_read_product_listings
*-   vBulletin uses this scope to pull the product catalog and stores the product id & handle mapping in order to generate the proper data for the [buy] BB Codes. Without this scope, the bbcode renders will fail, and show up as raw text instead.
* unauthenticated_write_checkouts
*-   The Shopify JS SDK, which generates the "Buy" button for the product based on the data fed in via the [buy] bbcode render, requires this scope in order to create a checkout session upon an end user clickign on the "Buy" button. Without this scope, clicking on the "Buy" button will fail to load up a checkout popup.
After finishing setting up the "Storefront API scopes" and clicking "Install app", you should have the API Key, Secret, and Storefront Access Token on the subsequent page. You can always re-access those via going back to the Shopify store admin, clicking on Settings > Apps and sales channesl > Develop Apps > and clicking on the Custom App you just created, and viewing the "API credentials" for that app.

The Shopify Store Password is only used if the store is in development, or if the store is password protected for any reason.
To view the Store's password, refer to this Shopify documentation:
https://help.shopify.com/en/manual/online-store/themes/password-page#add-your-online-store-password


===========================
===========================
How to set up the RSS Feed:
===========================
===========================

At the time of writing, Shopify only provides built-in RSS feeds for "Collections".
You can access your store's default "all" collection feed via:
{YOUR STORE URL}/collections/all.atom
e.g.
https://your-store-example.myshopify.com/collections/all.atom

If you have a custom collection and wish to use that instead, you can do so via specifying it like so:
{YOUR STORE URL}/collections/{COLLECTION NAME}.atom

Once you have the atom feed URL and you visited it to verify that it works, go to your forum's adminCP.
You may want to first set up a custom channel to post the RSS Feeds to via Channel Management > Add New Channel.
If you know what channel you want vBulletin to post the RSS Feed items to, go into the RSS Feeds > Add New RSS Feed.
* Specify anything for the "Title", e.g. "Shopify RSS Feed".
* "URL of Feed" Should be your store's .atom feed URL .
* Specify a "User Name" & "Channel" for the feeds to be posted under.
* Under "Templates", change the "Body Template" to sometihing like the following:
{feed:description}
[buy]{feed:link}[/buy]

The important part is the [buy]{feed:link}[/buy], this will generate the appropriate BBCode to show the "Buy" button on the RSS feed posts.

Known issues:
Since the "all" collection from Shopify contains ALL items, and there currently is no way to filter or sort items by date, the initial few runs of the RSS Feed Cron will post all of the old products nearly at the same time, but in reverse order.
This is because the current sorting for the feed seems to be sort by date descending (latest at the top), and since vBulletin makes the post in the order it encounters the item, it will end up posting the newest products first, then the older items subsequently.