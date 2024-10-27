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

//@TODO -- Remove this controller. The two methods here should be template
// helper functions of some sort. They aren't controller methods.

class vB5_Frontend_Controller_Permission extends vB5_Frontend_Controller
{
	/**
	 * Compare two arrays, and merge any non-zero values of the second into the first. Must be string => integer members
	 *
	 * @param	array of $string => integer values
	 * @param array of $string => integer values
	 * @return array of $string => integer values
	 */
	public function actionMergePerms($currPerms = [], $addPerms = [])
	{
		// this is called from the templates via {vb:action}
		// but since the method name starts with 'action', it *is*
		// accessible externally via <site>/permission/merge-perms,
		// however, since it's not reading any user input from the
		// superglobals, it won't do anything.

		if (is_array($currPerms) AND is_array($addPerms))
		{
			foreach ($addPerms AS $permName => $permValue)
			{
				if (is_string($permName) AND (is_numeric($permValue) OR is_bool($permValue)) AND empty($currPerms[$permName]) AND ($permValue > 0))
				{
					$currPerms[$permName] = $permValue;
				}
			}
		}

		return $currPerms;
	}

	/**
	 * Decide if the inlinemod menu should be shown
	 *
	 *	@param		array
	 *	@param		array
	 *	@param		array
	 *	@return		bool
	 */
	public function showInlinemodMenu($conversation = [], $modPerms = [], $options = [])
	{
		// this is called from the templates via {vb:action}
		// since the method name doesn't start with 'action'
		// it's not even accessible externally. Which leads
		// to the question of why it's even a controller method....

		// It was already decided not to show the inlinemod menu
		if (isset($options['showInlineMod']) AND !$options['showInlineMod'])
		{
			return false;
		}

		if (is_array($conversation) AND !empty($conversation))
		{
			if (
				!empty($conversation['permissions']) AND
				(
					!empty($conversation['permissions']['canmoderate']) OR
					!empty($conversation['moderatorperms']['canmoderateposts']) OR
					!empty($conversation['moderatorperms']['candeleteposts']) OR
					!empty($conversation['moderatorperms']['caneditposts']) OR
					!empty($conversation['moderatorperms']['canopenclose']) OR
					!empty($conversation['moderatorperms']['canmassmove']) OR
					!empty($conversation['moderatorperms']['canremoveposts']) OR
					!empty($conversation['moderatorperms']['cansetfeatured']) OR
					!empty($conversation['moderatorperms']['canmanagethreads']) OR
					!empty($conversation['moderatorperms']['canharddeleteposts']) OR
					!empty($conversation['moderatorperms']['cansetanswer'])
				)
			)
			{
				return true;
			}
		}

		// This is from the inlinemod_nemu
		$view = $options['view'] ?? '';

		if (is_array($modPerms) AND !empty($modPerms))
		{
			if (
				!empty($modPerms['canmove']) OR
				!empty($modPerms['canopenclose']) OR
				(!empty($modPerms['candeleteposts']) AND $view == 'thread') OR
				!empty($modPerms['canmoderateposts']) OR
				!empty($modPerms['caneditposts']) OR
				!empty($modPerms['candeletethread']) OR
				!empty($modPerms['cansetfeatured']) OR
				!empty($modPerms['canmoderateattachments']) OR
				!empty($modPerms['canmassmove']) OR
				!empty($modPerms['canremoveposts']) OR
				!empty($modPerms['canundeleteposts']) OR
				!empty($modPerms['canharddeleteposts'])
			)
			{
				return true;
			}
		}

		return false;
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 114279 $
|| #######################################################################
\*=========================================================================*/
