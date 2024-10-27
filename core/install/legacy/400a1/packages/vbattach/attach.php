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
 *	This file is only used for by the installer 400a1 steps 89 and 90.  It and the classes it relies on are
 *	obsolete and should not be used elsewhere.
 *
 *	This is used by vB_DataManager_Attachment and vB_DataManager_AttachData
 */

// #######################################################################
// ############################# STORAGE #################################
// #######################################################################

/**
* Class for initiating proper subclass to extende attachment DM operations
*
* @package 		vBulletin
* @version		$Revision: 111182 $
* @date 		$Date: 2022-12-16 17:26:26 -0800 (Fri, 16 Dec 2022) $
*
*/
class vB_Attachment_Dm_Library
{
	/**
	* Select library
	*
	* @param	vB_Registry	Instance of the vBulletin data registry object - expected to have the database object as one of its $this->db member.
	* @param	integer			Unique id of this contenttype (forum post, blog entry, etc)
	*
	* @return	object
	*/
	public static function fetch_library(&$registry, $contenttypeid)
	{
		return false;
		//This can't return anything but false.  The path below looks for files in DIR/packages but
		//the relevant files don't exist any longer there.  They were moved to this legacy directory a *long* time ago
		//this may mean that the upgrade steps that access these files don't work correctly but that's not new
		//and are only relevant from people upgrading from vB3 so it's not worth the effort involved to untangle.
/*
		static $instance;

		if (!$instance["$contenttypeid"])
		{
			$types = vB_Types::instance();

			if (!($contenttypeid = $types->getContentTypeID($contenttypeid)))
			{
				return false;
			}

			$package = $types->getContentTypePackage($contenttypeid);
			$class = $types->getContentTypeClass($contenttypeid);

			$selectclass = "vB_Attachment_Dm_{$package}_{$class}";
			$path = DIR . '/packages/' . strtolower($package) . '/attach/' . strtolower($class) . '.php';
			if (file_exists($path))
			{
				include_once($path);
				if (class_exists($selectclass))
				{
					$instance["$contenttypeid"] = new $selectclass($registry, $contenttypeid);
					return $instance["$contenttypeid"];
				}
			}
			return false;
		}

		return $instance["$contenttypeid"];
 */
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 111182 $
|| #######################################################################
\*=========================================================================*/
