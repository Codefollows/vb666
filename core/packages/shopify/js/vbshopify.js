/*!=======================================================================*\
|| ###################################################################### ||
|| # vBulletin 6.0.6
|| # ------------------------------------------------------------------ # ||
|| # Copyright 2000-2024 MH Sub I, LLC dba vBulletin. All Rights Reserved.  # ||
|| # This file may not be redistributed in whole or significant part.   # ||
|| # ----------------- VBULLETIN IS NOT FREE SOFTWARE ----------------- # ||
|| # http://www.vbulletin.com | http://www.vbulletin.com/license.html   # ||
|| ###################################################################### ||
\*========================================================================*/

(function() {
	function onEverythingReady() {
		/*
		console.log({
			msg: "onEverythingReady()",
			$infometa: window.jQuery('.js-vb-shopify-clientinfo'),
		});
		*/
		const $ = window.jQuery,
			$infometa = $('.js-vb-shopify-clientinfo'),
			client = ShopifyBuy.buildClient({
				domain: $infometa.data('store'),
				storefrontAccessToken: $infometa.data('access-token'),
				// language: document.documentElement.getAttribute('lang'),
			}),
			ui = ShopifyBuy.UI.init(client),
			initvar = 'vb-shopify-init';

		// Based on Shopify's buy button embed JS
		function createBuyButton(productid, node)
		{
			var $node = $(node);
			// guard against running multiple times per element, just in case.
			if ($node.data(initvar))
			{
				return;
			}
			$node.data(initvar, 1);

			// Handy references: https://shopify.github.io/buy-button-js/customization/
			// https://github.com/Shopify/buy-button-js/blob/master/src/defaults/components.js
			ui.createComponent('product', {
				id: productid,
				node: node,
				moneyFormat: '%24%7B%7Bamount%7D%7D',
				options: {
					"product": {
						// 'layout': 'horizontal' | 'modal',
						// The horizontal layout will turn vertical in narrow containers.
						// I feel horizontal fits better in most content (less wasted whitespace).
						'layout' : 'horizontal',
						// 'buttonDestination': 'checkout' | 'modal',
						// The modal looks and feels more integrated, and comes with
						// the neat "shopping cart" feature that persists through
						// different pages.
						'buttonDestination': 'modal',
						'contents' : {
							// Recommend using EITHER img OR imgWithCarousel, not both.
							// Since the modalProduct will have the carousel, let's just
							// go with the singular thumb for a cleaner look until we get
							// feedback.
							'img' : true,
							'imgWithCarousel' : false,
							'title': true,
							'price': true,
							'unitPrice': true,
							'options': true,
							'button': true,
							// This is default off, but I find it more valuable to show the
							// desc, particularly in the rss feed widget context.
							'description': true,
						},
						// See https://sdks.shopifycdn.com/buy-button/2.2.1/buybutton.css for default styling
						'styles' : {
							'product': {
								// Doing this at this level causes rss feed widgets on wide columns to
								// look bad. Doing it at the outer div level with vb css instead.
								//'max-width': '600px',
							},
							// 60% default img split for large screens makes the img a bit too big IMO.
							// Overrides...
							// Note, I don't want all these !importants here, but for whatever reason,
							// the css override doesn't actually override and seems to sit at a lower
							// precedence than the default media queries, for some reason.
							'imgWrapper' : {
								"@media (min-width: 680px)": {
									'width': '50% !important',
								}

							},
							'title' : {
								"@media (min-width: 680px)": {
									'margin-left': 'calc(50% + 25px) !important',
								}

							},
							// Note 'prices' not 'price'. The latter causes this rule to
							// be applied to both the wrapper and the price element (I guess
							// the wrapper exists since it might also show the sales/"compare"
							// prices)
							'prices' : {
								"@media (min-width: 680px)": {
									'margin-left': 'calc(50% + 25px) !important',
								}

							},
							'buttonWrapper' : {
								"@media (min-width: 680px)": {
									'margin-left': 'calc(50% + 25px) !important',
								}

							},
							'description' : {
								"@media (min-width: 680px)": {
									'margin-left': 'calc(50% + 25px) !important',
								}

							},
						},
						'order' : [
							'img',
							'imgWithCarousel',
							'title',
							'price',
							'options',
							'quantity',
							'button',
							'description',
						],
						"text": {
							"button": "Buy now"
						}
					},
					"modalProduct": {
						"contents": {
							"img": false,
							"imgWithCarousel": true,
							"button": false,
							"buttonWithQuantity": true
						},
						"text": {
						"button": "Add to cart"
						}
					},
					"option": {},
					"cart": {
						"text": {
							"total": "Subtotal",
							"button": "Checkout"
						}
					},
					"toggle": {}
				},
			});
	  	}

		function handleBuyLinks($context)
		{
			$('.js-vb-shopify-buy-link', $context).each((idx, el) => {
				let productid = el.dataset.productid || '';
				createBuyButton(productid, el)
			});
		}

		handleBuyLinks($(document));
		// also hook up ajax loaded posts
		$(document).on('vb-loadnewposts', (e, data) => handleBuyLinks(data.insertedHtml));
		$(document).on('vb-loadnode', (e, data) => handleBuyLinks(data.insertedHtml));
	}

	function ensureXYZ(checkFunc, waitingForStr, maxtries, waittime) {
		var maxtries = maxtries || 10,
			tries = maxtries,
			waittime = waittime || 100;

		return new Promise((resolve, reject) => {
			(function mywait(){
				if (checkFunc())
				{
					//console.log('found ' + waitingForStr);
					resolve();
				}
				else if (tries-- > 0)
				{
					//console.log('waiting ' + (waittime * (maxtries - tries)) + 'ms for ' + waitingForStr);
					setTimeout(mywait, waittime * (maxtries - tries));
				}
				else
				{
					reject(new Error(waitingForStr + ' not found after waiting. Cancelling'));
				}
			})();
		});
	}

	// Wait for vBulletin & jQuery
	ensureXYZ(() => window.vBulletin, 'vBulletin').then(
		() => ensureXYZ(() => window.jQuery, 'JQuery')
	).then(
		() => ensureXYZ(() => window.ShopifyBuy, 'Shopify')
	).then(
		() => onEverythingReady()
	).catch((e) => {
		console.log(e.message);
	});
})();
