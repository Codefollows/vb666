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

class vB5_Route_Newcontent extends vB5_Route
{
	public static function exportArguments($arguments)
	{
		self::pageIdtoGuid($arguments);
		return $arguments;
	}

	public static function importArguments($arguments)
	{
		self::pageGuidToId($arguments);
		return $arguments;
	}

	public static function importContentId($arguments)
	{
		return $arguments['pageid'];
	}

	/**
	 * Sets the breadcrumbs for the route
	 */
	protected function setBreadcrumbs()
	{
		// Not checking if this is homeroute -- new-content page is special and I don't think anyone would
		// want this as their homepage, nor would we want to really support that.
		// However, we probably need to check if its associated channel is a home page and may want to
		// handle that portion of the breadcrumbs properly.

		$this->breadcrumbs = [];

		$phrase = 'create_new_topic';

		if (isset($this->arguments['nodeid']) && $this->arguments['nodeid'])
		{
			$onlyAddTopParent = false;

			$channelInfo = vB_Api::instanceInternal('Content_Channel')->fetchChannelById(intval($this->arguments['nodeid']));
			if ($channelInfo)
			{
				switch($channelInfo['channeltype'])
				{
					case 'blog':
						$phrase = 'create_new_blog_entry';
						break;
					case 'group':
						$phrase = 'create_new_topic';
						break;
					case 'article':
						$phrase = 'create_new_article';
						// when creating an article, the breadcrumb should
						// always be home > articles > create article
						// since you can choose the category when creating the article
						$onlyAddTopParent = true;
						break;
					default:
						break;
				}
			}

			$this->addParentNodeBreadcrumbs($this->arguments['nodeid'], $onlyAddTopParent);
		}

		$this->breadcrumbs[] = array(
			'phrase' => $phrase,
		);
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 111805 $
|| #######################################################################
\*=========================================================================*/
