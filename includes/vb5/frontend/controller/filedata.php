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

class vB5_Frontend_Controller_Filedata extends vB5_Frontend_Controller
{

	/**
	 * This methods returns the contents of a specific image
	 */
/*
	public function actionFetch()
	{
		// dev note: if you're wondering why a filedata/fetch url isn't hitting this function, it's probably because
		// it's going through vB5_Frontend_ApplicationLight's fetchImage()
	}
*/

	/**
	 * This is called on a delete- only used by the blueimp slider and doesn't do anything
	 */
	public function actionDelete()
	{
		//Note that we shouldn't actually do anything here. If the filedata record isn't
		//used it will soon be deleted.
		$contents = '';
		header('Content-Type: image/png');
		header('Accept-Ranges: bytes');
		header('Content-transfer-encoding: binary');
		header("Content-Length: " . strlen($contents) );
		header("Content-Disposition: inline; filename=\"1px.png\"");
		header('Cache-control: max-age=31536000, private');
		header('Expires: ' . gmdate("D, d M Y H:i:s", time() + 31536000) . ' GMT');
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s', time()) . ' GMT');
		die($contents);
	}

	/**
	 * gets a gallery and returns in json format for slideshow presentation.
	 */
	public function actionGallery()
	{
		// Don't need to require POST, since this is only displaying content

		//We need a nodeid
		if (!empty($_REQUEST['nodeid']))
		{
			$nodeid = $_REQUEST['nodeid'];
		}
		else if (!empty($_REQUEST['id']))
		{
			$nodeid = $_REQUEST['id'];
		}
		else
		{
			return '';
		}

		//get the raw data.
		$api = Api_InterfaceAbstract::instance();
		$config = vB5_Config::instance();
		$phraseApi = vB5_Template_Phrase::instance();
		$gallery = ['photos' => [],];
		switch (intval($nodeid))
		{
		 	//All Videos
			case 0:
			case -1:
				throw new vB_Exception_Api('invalid_request');
				break;

			//All non-Album photos and attachments
			case -2:
				if (
					(empty($_REQUEST['userid']) OR !intval($_REQUEST['userid'])) AND
					(empty($_REQUEST['channelid']) OR !intval($_REQUEST['channelid']))
				)
				{
					throw new vB_Exception_Api('invalid_request');
				}

				$galleryData = $api->callApi('profile', 'getSlideshow', [
					[
						'userid'      => intval($_REQUEST['userid'] ?? 0),
						'channelid'   => intval($_REQUEST['channelid'] ?? 0),
						'dateFilter'  => $_REQUEST['dateFilter'] ?? '',
						'searchlimit' => $_REQUEST['perpage'] ?? '',
						'startIndex'  => $_REQUEST['startIndex'] ?? '',
						// Conversation photo tab params.
						// Pagination, sort, additional filters
						'pageno' => intval($_REQUEST['pageno'] ?? null),
						'perpage' => intval($_REQUEST['perpage'] ?? null),
						'sort'        => $_REQUEST['sort'] ?? '',
						'showFilter' => $_REQUEST['showFilter'] ?? '',
					]
				]);

				if (empty($galleryData))
				{
					return [];
				}

				$photoTypeid = vB_Types::instance()->getContentTypeID('vBForum_Photo');
				$attachTypeid = vB_Types::instance()->getContentTypeID('vBForum_Attach');
				foreach($galleryData AS $photo)
				{
					$titleVm = !empty($photo['parenttitle']) ? $photo['parenttitle'] : $photo['startertitle'];
					$route = $photo['routeid'];
					if($photo['parenttitle'] == 'No Title' AND $photo['parentsetfor'] > 0)
					{
						$titleVm = $phraseApi->getPhrase('visitor_message_from_x', [$photo['authorname']]);
						$route = 'visitormessage';
					}

					$userLink = $api->callApi('route', 'getUrl', [
						'route' => 'profile|fullurl',
						'data' => [
							'userid' => $photo['userid'],
							'username' => $photo['authorname'],
						],
						'extra' => [],
					]);

					$topicLink = $api->callApi('route', 'getUrl', [
						'route' => "$route|fullurl",
						'data' => [
							'title' => $titleVm,
							'nodeid' => $photo['parentnode'],
						],
						'extra' => [],
					]);

					$title = $photo['title'] ?? '';
					$htmltitle = $photo['htmltitle'] ?? '';
					if ($photo['contenttypeid'] === $photoTypeid)
					{
						$queryVar = 'photoid';
					}
					else if ($photo['contenttypeid'] === $attachTypeid)
					{
						$queryVar = 'id';
					}


					$__photoUrl = 'filedata/fetch?' . $queryVar . '=' . intval($photo['nodeid']);
					$__thumbUrl = $__photoUrl . '&thumb=1';

					if (!$photo['showfull'])
					{
						$__photoUrl = $__thumbUrl;
						if (!$photo['showthumb'])
						{
							// we can't show this image at all, so skip it.
							continue;
						}
					}

					$gallery['photos'][] = [
						'title' => $title,
						'caption' => $photo['caption'] ?? '',
						'htmltitle' => $htmltitle,
						'url' => $__photoUrl,
						'thumb' => $__thumbUrl,
						'links' => $phraseApi->getPhrase('photos_by_x_in_y_linked', [
								$userLink,
								$photo['authorname'],
								$topicLink,
								htmlspecialchars($titleVm),
								$photo['userid'],
							]) . "<br />\n",
					];
				}
				$this->sendAsJson($gallery);
				return;

			default:
				$galleryData = $api->callApi('content_gallery', 'getContent', ['nodeid' => $nodeid]);
				if (!empty($galleryData) AND !empty($galleryData[$nodeid]['photo']))
				{
					$galleryData = $galleryData[$nodeid];
					$userLinks = [];
					$topicLink = $api->callApi('route', 'getUrl', [
						'route' => $galleryData['routeid'] . '|fullurl',
						'data' => $galleryData,
						'extra' => [],
					]);
					foreach($galleryData['photo'] AS $photo)
					{
						// Each photo *may* have a different userid, e.g. if admin edits a user's gallery.
						// Not sure if profile albums can have multiple contributors yet.
						if (empty($userLinks[$photo['userid']]))
						{
							$userLinks[$photo['userid']] = $api->callApi('route', 'getUrl', [
								'route' => 'profile|fullurl',
								'data' => [
									'userid' => $photo['userid'],
									'username' => $photo['authorname'],
								],
								'extra' => [],
							]);
						}
						$__userLink = $userLinks[$photo['userid']];

						$route = $photo['routeid'];

						$__link = $phraseApi->getPhrase(
								'photos_by_x_in_y_linked',
								[
									$__userLink,
									$photo['authorname'],
									$topicLink,
									htmlspecialchars($photo['startertitle']),
									$photo['userid'],
								]
							) . "<br />\n";

						$__photoUrl = 'filedata/fetch?photoid=' . intval($photo['nodeid']);
						$__thumbUrl = $__photoUrl . '&thumb=1';
						// If thumbs only, just show thumbnail images in the slideshow.
						if (!$photo['showfull'])
						{
							$__photoUrl = $__thumbUrl;
							// If they cannot even see thumbs, they shouldn't be able to hit
							// this code by the normal gallery UI (and if they do, ANY image
							// URL will be broken so we cannot handle it), so let's skip it.
							if (!$photo['showthumb'])
							{
								continue;
							}
						}

						$gallery['photos'][] = [
							'title' => $photo['title'],
							'caption' => $photo['caption'],
							'htmltitle' => $photo['htmltitle'],
							'url' => $__photoUrl,
							'thumb' => $__thumbUrl,
							'links' => $__link,
						];
					}
					$this->sendAsJson($gallery);
				}
				else
				{
					$this->sendAsJson(['error' => 'not_a_gallery']);
				}
		}
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 108270 $
|| #######################################################################
\*=========================================================================*/
