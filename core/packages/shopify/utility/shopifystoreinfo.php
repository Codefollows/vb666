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

class shopify_Utility_shopifyStoreInfo
{
	use vB_Trait_NoSerialize;
	// A fake struct to easily pass some data around.
	// readonly requires PHP 8.1. Our min version may still be PHP 8.0 by the time this hits public
	public /* readonly */ string $scheme;
	public /* readonly */ string $domain;
	public /* readonly */ string $storeurlprefix;

	public function __construct(string $scheme, string $domain, string $storeurlprefix)
	{
		$this->scheme = $scheme;
		$this->domain = $domain;
		$this->storeurlprefix = $storeurlprefix;
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 19:44, Thu Apr 14th 2022
|| # CVS: $RCSfile$ - $Revision: 105377 $
|| #######################################################################
\*=========================================================================*/
