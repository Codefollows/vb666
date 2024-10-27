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
interface shopify_Interface_ProductProvider
{
	// pass interface around in case we need to stub a dependency for testing

	public function getProducts() : array;

	public function convertUrlTextToIdAndLabel(string $productUrl) : array;

	public function convertProductHandleToId(string $productHandle) : string;

	public function convertProductIdToHandle(string $productId) : string;

	public function convertProductIdToUrl(string $productId) : string;
}


/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 103236 $
|| #######################################################################
\*=========================================================================*/
