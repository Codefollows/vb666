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

class vB5_Frontend_Controller_Relay extends vB5_Frontend_Controller
{
	public function admincp($file)
	{
		if ($file)
		{
			//the sizeof is due to the fact that there is code in the download that can
			//alter the php extension for nonstandard environments.  This will work with
			//any string that might replace php
			if (substr($file, -1 * strlen('.php')) != '.php')
			{
				$file = "$file.php";
			}
		}
		else
		{
			$file = "index.php";
		}

		$api = Api_InterfaceAbstract::instance();
		$api->relay('admincp/' . $file);
	}

	public function modcp($file)
	{
		if ($file)
		{
			//the sizeof is due to the fact that there is code in the download that can
			//alter the php extension for nonstandard environments.  This will work with
			//any string that might replace php
			if (substr($file, -1 * strlen('.php')) != '.php')
			{
				$file = "$file.php";
			}
		}
		else
		{
			$file = "index.php";
		}

		$api = Api_InterfaceAbstract::instance();
		$api->relay('modcp/' . $file);
	}

	public function legacy($file)
	{
		//this duplicates some checks in the relay function, but there
		//really isn't a good way to avoid that while ensuring that $file
		//is a direct child of core.  We don't want to allow arbitrary file
		//inclusion via the relay, just some select areas
		//
		//we only want to allow files "1 deep" via the legacy route
		$corepath = vB5_ApplicationAbstract::instance()->getCorePath();

		$filePath = $corepath . '/' . $file;
		if ($file AND file_exists($filePath))
		{
			$core = realpath($corepath);
			$filePath = realpath($filePath);

			if($core AND dirname($filePath) === $core)
			{
				$api = Api_InterfaceAbstract::instance();
				$api->relay($file);
				return;
			}
		}

		throw new vB5_Exception_404("invalid_page_url");
	}

	public function action404()
	{
		throw new vB5_Exception_404("invalid_page_url");
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 111777 $
|| #######################################################################
\*=========================================================================*/
