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

require_once(DIR . '/includes/adminfunctions_template.php');
require_once(DIR . '/includes/class_core.php');
require_once(DIR . '/includes/functions.php');

/**
* Outputs the XML templates to the file system from a specified product
*
* @param	mixed	the product string or string in an array, if null we will process all products
*
* @return	bool	true if successfully wrote the products to the file system
*/
function roll_up_templates($product = null, $base_svn_url="")
{
	require_once(DIR . '/includes/class_filesystemxml_template.php');

	// instantiate the file system helper
	$helper = new vB_FilesystemXml_Template();

	// if not given a product, we will process all products
	if (empty($product))
	{
		$product = $helper->get_all_products();
	}

	// wrap the product string in an array
	else if (!is_array($product))
	{
		$product = [$product];
	}

	if ($base_svn_url)
	{
		$helper->set_base_svn_url($base_svn_url);
	}

	// now loop through each product and roll up to master xml
	$successful = true;
	foreach ($product as $p)
	{
		// check for success of each product rollup, but keep processing on failure
		if (!$helper->rollup_product_templates($p))
		{
			$successful = false;
		}
	}

	return $successful;
}

/**
* Outputs the XML templates to the file system from a specified product
*
* @param	mixed	the product string or string in an array, if null we will process all products
*
* @return	bool	true if successfully wrote the products to the file system
*/
function xml_templates_to_files($product = null)
{
	require_once(DIR . '/includes/class_filesystemxml_template.php');

	// instantiate the file system helper
	$helper = new vB_FilesystemXml_Template();

	// if not given a product, we will process all products
	if (empty($product))
	{
		$product = $helper->get_all_products();
	}

	// wrap the product string in an array
	else if (!is_array($product))
	{
		$product = [$product];
	}

	// now loop through each product and output to filesystem
	$successful = true;
	foreach ($product as $p)
	{
		// check for success of each product, but keep processing on failure
		if (!$helper->write_product_to_files($p))
		{
			$successful = false;
		}
	}

	return $successful;
}

/**
* Writes a single template to the file system, using a helper class
*
* @param	string - template
* @param	string - the actual contents of the template
* @param	string - the product to which the template belongs
* @param	string - the version string
* @param	string - the username of last editor
* @param	string - the datestamp of last edit
* @param	string - the previous title of the template if applicable.  If oldtitle=title
* 			no action will be taken.
* @param	array  - additional attributes to be set, like "textonly"
*
* @return	bool - true if successful, false otherwise
*/
function autoexport_write_template($name, $text, $product, $version, $username, $datestamp, $oldname="", $extra = [])
{
	require_once(DIR . '/includes/class_filesystemxml_template.php');
	$helper = new vB_FilesystemXml_Template();
	return $helper->write_template_to_file($name, $text, $product, $version, $username, $datestamp, $oldname, $extra);
}

/**
* Deletes a single template to the file system, using a helper class
*
*
* @param	string - template
*/
function autoexport_delete_template($name)
{
	require_once(DIR . '/includes/class_filesystemxml_template.php');
	$helper = new vB_FilesystemXml_Template();
	return $helper->delete_template_file($name);
}

function autoexport_write_settings($product)
{
	autoexport_route_update(-1, $product, 'autoexport_write_main_settings');
}

function autoexport_write_help($product)
{
	autoexport_route_update(-1, $product, 'autoexport_write_main_help');
}


function autoexport_write_settings_and_language($languageid, $product)
{
	autoexport_route_update($languageid, $product, ['autoexport_write_main_settings', 'autoexport_write_master_language']);
}

function autoexport_write_style($styleid, $product)
{
	//autoexport_route_update($styleid, $product, 'autoexport_write_master_style');
	autoexport_route_update($styleid, $product, []);

	autoexport_write_default_style_and_themes($styleid);
}


function autoexport_write_language($languageid, $product)
{
	autoexport_route_update($languageid, $product, 'autoexport_write_master_language');
}

//doing language and style in one operation avoids writing the product file twice
//if we have a product (since any operation on a product write the entire product file).
function autoexport_write_style_and_language($styleid, $product)
{
	//autoexport_route_update($styleid, $product, ['autoexport_write_master_style', 'autoexport_write_master_language']);
	autoexport_route_update($styleid, $product, ['autoexport_write_master_language']);

	autoexport_write_default_style_and_themes($styleid);
}


//Note that there is no seperate faq import/export for vbulletin -- this is
//handled as part of the the default data and needs to be changed there if
//a master faq item is to be removed or added.  So we either do the product
//export/nothing or product export/language if there are language changes
//to track.
function autoexport_write_faq($product)
{
	autoexport_route_update(-1, $product, "autoexport_no_op");
}

function autoexport_write_faq_and_language($languageid, $product)
{
	autoexport_route_update($languageid, $product, 'autoexport_write_master_language');
}


/*
 *	Internal use for cover functions above
 */

function autoexport_route_update($id, $product, $vb_func)
{
	if ($id == -1)
	{
//		$timer = vB_Timer::get('timer');
//		$timer->start();

		if (is_array($product))
		{
			//makes sure that if we are passed a list of products
			//that we only process each product a single time.
			//More is unnecesary and possibly counterproductive.
			//We will actually rely on the fact that we'll only
			//process a product once to avoid having to check if
			//products are the same before adding them to the list.
			$products = array_unique($product);
		}
		else
		{
			$products = [$product];
		}

		foreach ($products AS $product)
		{
			if ($product == "vbulletin")
			{
				if (!is_array($vb_func))
				{
					$vb_func();
				}
				else
				{
					//allow multiple callbacks for operations that
					//touch multiple files.
					foreach ($vb_func as $func)
					{
						$func();
					}
				}
			}
			//this doesn't do anything at present but leaving in in case we want to export products in the future
			else if (in_array($product, []))
			{
				autoexport_write_product($product);
			}
		}
	}
}

/*
 * Dummy function for when the vb callback shouldn't actually do anything.
 */
function autoexport_no_op()
{
}

function autoexport_write_main_settings()
{
	require_once(DIR . '/includes/adminfunctions_options.php');
	$xml = get_settings_export_xml('vbulletin');
	autoexport_write_file(DIR . '/install/vbulletin-settings.xml', $xml);
}

function autoexport_write_main_help()
{
	require_once(DIR . '/includes/adminfunctions_options.php');
	$xml = get_help_export_xml('vbulletin');
	autoexport_write_file(DIR . '/install/vbulletin-adminhelp.xml', $xml);
}

function autoexport_write_master_language()
{
	require_once(DIR . '/includes/adminfunctions_language.php');
	$xml = get_language_export_xml(-1, 'vbulletin', 0, 0);
	autoexport_write_file(DIR . '/install/vbulletin-language.xml', $xml);
}

function autoexport_write_master_style()
{
	require_once(DIR . '/includes/adminfunctions.php');
	require_once(DIR . '/includes/adminfunctions_template.php');
	$full_product_info = fetch_product_list(true);
	$xml = get_style_export_xml(-1, 'vbulletin', $full_product_info['vbulletin']['version'], '', 2);
	autoexport_write_file(DIR . '/install/vbulletin-style.xml', $xml);

	//we don't want the templates in the xml on the filesystem
	require_once(DIR . '/includes/class_filesystemxml_template.php');
	$helper = new vB_FilesystemXml_Template();
	$helper->remove_product_templates('vbulletin');
}


function autoexport_write_default_style_and_themes($styleid)
{
	require_once(DIR . '/includes/adminfunctions.php');
	require_once(DIR . '/includes/adminfunctions_template.php');
	$full_product_info = fetch_product_list(true);
	$vbversion = $full_product_info['vbulletin']['version'];

	$map = [
		'default'                                                      => '/install/vbulletin-style.xml',
		'vbulletin-theme-legacy-668e390263dc4ebd89f967b785bc47da'      => '/install/vbulletin-style-five.xml',
		'vbulletin-theme-blackred-995ca62b4419380feb2b00a9cc5f3cc1'    => '/install/themes/vbulletin-theme-blackred.xml',
		'vbulletin-theme-546431b47d6f68c536e065d9c8ce3bbb'             => '/install/themes/vbulletin-theme-blue-green.xml',
		'vbulletin-theme-blueyellow-d12431a2f52176c59aa6f8d782f5ed82'  => '/install/themes/vbulletin-theme-blueyellow.xml',
		'vbulletin-theme-b292dd3d02298b147cd95b315a948e03'             => '/install/themes/vbulletin-theme-cool-blue.xml',
		'vbulletin-theme-dark-4d400525e238fc2620fbe6d5a3d9b345'        => '/install/themes/vbulletin-theme-dark.xml',
		'vbulletin-theme-denim-56882bc2c5245ea5dbcd8c105031c501'       => '/install/themes/vbulletin-theme-denim.xml',
		'vbulletin-theme-gradient-526179b8d4fd222b51cb78244ca249ba'    => '/install/themes/vbulletin-theme-gradient.xml',
		'vbulletin-theme-greystripes-9f2f39b326b0a5a2d84505cf66815632' => '/install/themes/vbulletin-theme-greystripes.xml',
		'vbulletin-theme-grunge-a44efa11de30a3e3d03e1a69691da99b'      => '/install/themes/vbulletin-theme-grunge.xml',
		'vbulletin-theme-halloween-b74519e13d9119a76439216dfd24bf9f'   => '/install/themes/vbulletin-theme-halloween.xml',
		'vbulletin-theme-lightblue-5508d4a29a8349ca818c6e0397981aba'   => '/install/themes/vbulletin-theme-lightblue.xml',
		'vbulletin-theme-oldschool-6d078a144e4de14edcd4e0d154e35080'   => '/install/themes/vbulletin-theme-oldschool.xml',
		'vbulletin-theme-cf5977271d5582b91abdf68dede732d0'             => '/install/themes/vbulletin-theme-orange-purple.xml',
		'vbulletin-theme-f0p32f1c5fe8ifb202357f4n69930akc'             => '/install/themes/vbulletin-theme-pink.xml',
		'vbulletin-theme-3r2c44802dfd26326cde63f825b927d1'             => '/install/themes/vbulletin-theme-red.xml',
		'vbulletin-theme-stripes-60b450ad1f9202d1f51e151aa3c15b76'     => '/install/themes/vbulletin-theme-stripes.xml',
		'vbulletin-theme-winter-55f554bf33204c348b3a5cf68913fd80'      => '/install/themes/vbulletin-theme-winter.xml',
		'vbulletin-theme-wood-2f5b0a19e4ba08699ca2575ef3c56c14'        => '/install/themes/vbulletin-theme-wood.xml',
	];

	if ($styleid == -1)
	{
		$guid = 'default';
		// export as master. This is probably not correct, because we explicitly
		// REMOVE the template groups from the XML after the fact, but leaving this
		// as before for now for styleid = -1
		$mode = 2;
	}
	else
	{
		// new vB dependency here...
		$assertor = vB::getDbAssertor();
		$style = $assertor->getRow('style', [
			vB_dB_Query::CONDITIONS_KEY => ['styleid' => $styleid,],
			vB_dB_Query::COLUMNS_KEY => ['styleid', 'guid', 'parentid', 'parentlist'],
		]);
		$guid = $style['guid'] ?? null;
		// 2: export as master
		// 1: Customizations in this & parent
		// 0: customizations in THIS STYLE ONLY
		$mode = 0;
	}

	if ($guid AND isset($map[$guid]))
	{
		$file =  $map[$guid];
		$xml = get_style_export_xml($styleid, 'vbulletin', $vbversion, '', $mode);
		autoexport_write_file(DIR . $file, $xml);

		if ($file == '/install/vbulletin-style.xml' OR $file == '/install/vbulletin-style-five.xml')
		{
			//we don't want the templates in the xml on the filesystem
			require_once(DIR . '/includes/class_filesystemxml_template.php');
			$helper = new vB_FilesystemXml_Template();
			$helper->remove_templates_from_xml(DIR . $file);
		}

		//
		// hook to decode style would go here
		//

	}

	// To update/recreate the map:
	// $files = [
	// 	DIR . '/install/vbulletin-style.xml',
	// 	DIR . '/install/vbulletin-style-five.xml',
	// ];
	// $themesFolderContents = glob(DIR . '/install/themes/vbulletin-theme-*.xml');
	// foreach ($themesFolderContents AS $__file)
	// {
	// 	$files[] = $__file;
	// }
	// $map = [];
	// foreach ($files AS $__file)
	// {
	// 	if (str_ends_with($__file, '/install/vbulletin-style.xml'))
	// 	{
	// 		$__styleid = -1;
	// 	}
	// 	$doc = new DOMDocument();
	// 	$doc->load($__file);
	// 	$guid = null;
	// 	$guidTags = $doc->getElementsByTagName('guid');
	// 	if ($guidTags->length > 0)
	// 	{
	// 		$guid = $guidTags->item(0)->nodeValue;
	// 	}
	// 	$__rel = str_replace(DIRECTORY_SEPARATOR, '/', str_replace(realpath(DIR), '', realpath($__file)));
	// 	$map[$guid] = $__rel;
	// }
	// error_log(
	// 	. "\n" . "map: " . var_export($map, true)
	// );
}

function autoexport_write_product($product)
{
	// This function basically doesn't work and gets called in some dubious instances.
	// We only define the default vbulletin product and it's not clear what actually
	// uses that.  We don't presently store product templates on as separate files, which
	// this clearly contemplates.  All in all we need to reconsider how we autoexport products.
	// Not removing because we might actually want to.
	require_once(DIR . '/includes/adminfunctions_product.php');
	$xml = get_product_export_xml($product);

	//we don't want the templates in the xml on the filesystem
	require_once(DIR . '/includes/class_filesystemxml_template.php');
	$helper = new vB_FilesystemXml_Template();
	$helper->write_product_xml($product, $xml);
	$helper->remove_product_templates($product);
}

function autoexport_write_file($file, $xml)
{
	file_put_contents($file, $xml);
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 115785 $
|| #######################################################################
\*=========================================================================*/
