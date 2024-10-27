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

class vB5_Template_NodeText
{
	const PLACEHOLDER_PREFIX = '<!-- ##nodetext_';
	const PLACEHOLDER_SUFFIX = '## -->';

	protected static $instance;
	protected $cache = [];
	protected $pending = [];
	protected $bbCodeOptions = [];
	protected $placeHolders = [];
	protected $contentPages = [];
	protected $cacheIdToNodeid = [];

	/**
	 * Returns a reference to the singleton instance of this class
	 *
	 * @return	mixed	reference to the vB5_Template_NodeText object
	 */
	public static function instance()
	{
		if (!isset(self::$instance))
		{
			$c = __CLASS__;
			self::$instance = new $c;
		}

		return self::$instance;
	}

	/**
	 * Returns preview info for one node
	 *
	 * @param	int $nodeId
	 * @param	Api_InterfaceAbstract $api
	 * @param	string	previewtext
	 */
	public function fetchOneNodePreview($nodeId, $api = null)
	{
		if (!$api)
		{
			$api = Api_InterfaceAbstract::instance();
		}

		$perms = $this->getPermissions($api, $nodeId, 'canviewthreads');
		if ($perms === false)
		{
			return '';
		}

		$bbCodeOptions['cangetimgattachment'] = $perms['cangetimgattachment'];
		//This isn't specifically referenced but is needed to generate the placeholder/cachekey properly
		//if this isn't set then we end up with different renders sharing the same values.
		$bbCodeOptions['cangetattachment'] = $perms['cangetattachment'];

		$cache = $api->cacheInstance(0);
		// since we're replacing phrases, we need the cachekey to be languageid sensitive
		$cacheKey = $this->getCacheKey($nodeId, $bbCodeOptions, true, $perms['canview']);
		$found = $cache->read($cacheKey);

		if ($found !== false)
		{
			return $found;
		}

		[$previewText, $parsed] = $this->doParse($nodeId, $bbCodeOptions, $api, $cache);
		return $previewText;
	}

	/**
	 * Returns text info for one node
	 *
	 * @param	int $nodeId
	 * @param	Api_InterfaceAbstract $api
	 * @param	int $contentPage
	 *
	 * @return	string	text of the page
	 */
	public function fetchOneNodeText($nodeId, $api = null, $contentPage = 1)
	{
		if (!$api)
		{
			$api = Api_InterfaceAbstract::instance();
		}

		$perms = $this->getPermissions($api, $nodeId, 'canviewthreads');
		if ($perms === false)
		{
			return '';
		}

		$bbCodeOptions['cangetimgattachment'] = $perms['cangetimgattachment'];
		//This isn't specifically referenced but is needed to generate the placeholder/cachekey properly
		//if this isn't set then we end up with different renders sharing the same values.
		$bbCodeOptions['cangetattachment'] = $perms['cangetattachment'];

		$cache = $api->cacheInstance(0);
		// since we're replacing phrases, we need the cachekey to be languageid sensitive
		$cacheKey = $this->getCacheKey($nodeId, $bbCodeOptions, false, $perms['canview']);

		$found = $cache->read($cacheKey);

		if ($found == false)
		{
			[$previewText, $found] = $this->doParse($nodeId, $bbCodeOptions, $api, $cache);
		}

		if (empty($found))
		{
			return '';
		}
		else if (!is_array($found))
		{
			return $found;
		}
		$contentPage = intval($contentPage);

		if ($contentPage < 2 OR (count($found) <= $contentPage))
		{
			return $found[0]['pageText'];
		}

		//Remember the cached data uses zero-based key
		return $found[$contentPage - 1]['pageText'];
	}

	/**
	 * returns the title of a page
	 *
	 * 	@param	int		the nodeid to be returned
	 * 	@param	int		optional content page
	 *
	 *	@return	string	title of the article page
	 */
	public function fetchPageTitle($nodeId, $contentPage = 1)
	{
		$paging = $this->fetchArticlePaging($nodeId);

		if (!empty($paging[$contentPage]))
		{
			return $paging[$contentPage];
		}

		return '';
	}


	/**
	 * Returns paging information
	 *
	 * @param	int		nodeid for which we need information
	 * @return array of int => string.
	 */
	public function fetchArticlePaging($nodeId)
	{
		$nodeId = intval($nodeId);
		$api = Api_InterfaceAbstract::instance();
		$canview =  $api->callApi('user', 'hasPermissions', [
			'group' => 'forumpermissions',
			'permission' => 'canviewthreads',
			'nodeid' => $nodeId,
		]);

		if (!$canview)
		{
			return [];
		}

		$cache = $api->cacheInstance(0);

		$cacheKey = $this->getPagingCacheKey($nodeId);
		$found = $cache->read($cacheKey);

		//We need to do the parse, which will set in cache.
		if ($found === false)
		{
			$cangetattachments =  $api->callApi('user', 'hasPermissions', [
				'group' => 'forumpermissions2',
				'permission' => 'cangetimgattachment',
				'nodeid' => $nodeId,
			]);

			$bbCodeOptions = ['cangetimgattachment' => $cangetattachments > 0];
			$this->doParse($nodeId, $bbCodeOptions, $api, $cache);
			$found = $cache->read($cacheKey);
		}

		return $found;
	}

	/**
	 * Register userids for avatar preloading
	 *
	 * @param  array  $textDataArray that comes from the getDataForParse API call
	 */
	protected function registerUseridsForAvatarPreloading($textDataArray)
	{
		$avatarUserIds = [];
		foreach ($textDataArray AS $node)
		{
			if (is_array($node['avatar_userids']))
			{
				foreach ($node['avatar_userids'] AS $avatarUserid)
				{
					$avatarUserIds[$avatarUserid] = $avatarUserid;
				}
			}
		}

		if (!empty($avatarUserIds))
		{
			Api_InterfaceAbstract::instance()->callApi('User', 'registerNeedAvatarForUsers', [$avatarUserIds]);
		}
	}

	protected function doParse($nodeId, $bbCodeOptions, &$api, &$cache)
	{
		$textDataArray =  $api->callApi('content_text', 'getDataForParse', [intval($nodeId), $bbCodeOptions]);

		// preload avatars
		$this->registerUseridsForAvatarPreloading($textDataArray);

		// Preload bbcode data
		$bbcodeDataCache = new vB_BbCodeDataCache(vB_BbCodeHelper::instance(), [$nodeId]);

		// writing to cache has been copied from parseNode() to here so that
		// the cached text has the placeholders replaced. VBV-9507
		// any changes to the node requires update
		$events = ['nodeChg_' . $nodeId];
		// need to update cache if channel changes options
		$events[] = 'nodeChg_' .  $textDataArray[$nodeId]['channelid'];
		// also need to update if phrases have been modified
		$events[] = 'vB_Language_languageCache';

		if (empty($textDataArray))
		{
			return ['', ''];
		}
		else if (!empty($textDataArray[$nodeId]['previewtext']))
		{
			$cache->write($this->getCacheKey($nodeId, $bbCodeOptions, false, false), $textDataArray[$nodeId]['previewtext'], 10080, $events);
			$cache->write($this->getCacheKey($nodeId, $bbCodeOptions, true, false), $textDataArray[$nodeId]['previewtext'], 10080, $events);
			return [$textDataArray[$nodeId]['previewtext'], $textDataArray[$nodeId]['previewtext']];
		}
		$canview = true;

		[$previewText, $parsed] = $this->parseNode($textDataArray, $nodeId, $bbCodeOptions, $bbcodeDataCache);

		// store any cachable data for next time.
		$bbcodeDataCache->saveChanges();

		$templateCache = vB5_Template_Cache::instance();

		// we need to replace the place holders before we can write to cache.
		$this->doOtherReplacePlaceholders($parsed, true);

		// write the parsed text to cache. cache for a week.
		$cache->write($this->getCacheKey($nodeId, $bbCodeOptions, false, $canview), $parsed, 10080, $events);
		$cache->write($this->getCacheKey($nodeId, $bbCodeOptions, true, $canview), $previewText, 10080, $events);

		return [$previewText, $parsed];
	}

	private function doOtherReplacePlaceholders(&$parsed, $doTemplatesInReverse = false)
	{
		$templateCache = vB5_Template_Cache::instance();
		$phraseCache = vB5_Template_Phrase::instance();
		$urlCache = vB5_Template_Url::instance();

		// If this is an article with page breaks, parsed will be an array of
		// [0 => ['title' => ..., 'pageText' => ...,]] instead of a flat string.
		// This apparently used to get through str_replace() transparently but
		// also without any replacements in php 7.4, but in php 8 we end up with
		// a list of string literals like [0 => 'Array', 1 => 'Array'] which is
		// NOt what we want. This list causes errors with other functions
		// which expect the 'pageText' key to exist in the cached nodetext
		if (is_array($parsed))
		{
			$titles = array_column($parsed, 'title');
			// I hate this, but keeping the old strict order of operations
			// in place just in case there were some weird dependencies between
			// the three placeholder replacements that required it.
			if ($doTemplatesInReverse)
			{
				$templateCache->setRenderTemplatesInReverseOrder(true);
			}
			$templateCache->replacePlaceholders($titles);
			if ($doTemplatesInReverse)
			{
				$templateCache->setRenderTemplatesInReverseOrder(false);
			}

			$phraseCache->replacePlaceholders($titles);
			$urlCache->replacePlaceholders($titles);

			$pageTexts = array_column($parsed, 'pageText');
			if ($doTemplatesInReverse)
			{
				$templateCache->setRenderTemplatesInReverseOrder(true);
			}
			$templateCache->replacePlaceholders($pageTexts);
			if ($doTemplatesInReverse)
			{
				$templateCache->setRenderTemplatesInReverseOrder(false);
			}

			$phraseCache->replacePlaceholders($pageTexts);
			$urlCache->replacePlaceholders($pageTexts);

			// There might be a more efficient way to do the above...
			// Also couldn't seem to find the inverse of array_column()
			// so doing it by hand here...
			$parsed = [];
			foreach ($titles AS $__k => $__title)
			{
				$parsed[$__k] = [
					'title' => $__title,
					// we should have the same keys between titles & pageTexts
					// unless something got really screwed up above
					'pageText' => $pageTexts[$__k],
				];
			}
		}
		else
		{
			if ($doTemplatesInReverse)
			{
				$templateCache->setRenderTemplatesInReverseOrder(true);
			}
			$templateCache->replacePlaceholders($parsed);
			if ($doTemplatesInReverse)
			{
				$templateCache->setRenderTemplatesInReverseOrder(false);
			}

			$phraseCache->replacePlaceholders($parsed);
			$urlCache->replacePlaceholders($parsed);
		}
	}

	/**
	 * Registers location for node text, to be filled with the parsed text later.
	 *
	 *	@param	int		$nodeId
	 * 	@param	array $bbCodeOptions
	 * 	@param	int		$contentPage -- for articles only
	 *
	 *	@return	string	the placeholder text
	 */
	public function register($nodeId, $bbCodeOptions = [], $contentPage = 1)
	{
		$perms = $this->getPermissions(Api_InterfaceAbstract::instance(), $nodeId, 'public_preview');
		if ($perms === false)
		{
			return '';
		}

		$bbCodeOptions['cangetimgattachment'] = $perms['cangetimgattachment'];
		//This isn't specifically referenced but is needed to generate the placeholder/cachekey properly
		//if this isn't set then we end up with different renders sharing the same values.
		$bbCodeOptions['cangetattachment'] = $perms['cangetattachment'];

		$placeHolder = $this->getPlaceholder($nodeId, $bbCodeOptions, $contentPage);
		$this->pending[$placeHolder] = $nodeId;
		$this->contentPages[$placeHolder] = $contentPage;
		$this->bbCodeOptions[$placeHolder] = $bbCodeOptions;

		$cacheKey = $this->getCacheKey($nodeId, $bbCodeOptions, false, $perms['canview']);
		$this->placeHolders[$cacheKey] = $placeHolder;
		$this->cacheIdToNodeid[$cacheKey] = $nodeId;

		return $placeHolder;
	}

	private function getPermissions(Api_InterfaceAbstract $api, int $nodeId, string $checkproperty) : array|false
	{
		$result = [];
		$result['canview'] = $api->callApi('user', 'hasPermissions', ['forumpermissions', 'canviewthreads', $nodeId]);
		if (!$result['canview'])
		{
			$node = $api->callApi('node', 'getNode', [$nodeId]);
			if (empty($node[$checkproperty]))
			{
				return false;
			}
		}

		$result['cangetattachment'] = $api->callApi('user', 'hasPermissions', ['forumpermissions', 'cangetattachment', $nodeId]);
		$result['cangetimgattachment'] = $api->callApi('user', 'hasPermissions', ['forumpermissions2', 'cangetimgattachment', $nodeId]);
		return $result;
	}

	/**
	 * Registers preview for node text, to be filled with the parsed text later.
	 *
	 *	@param	int		nodeid
	 * 	@param	mixed	optianal bbcode options
	 *
	 *	@return	string	the placeholder text
	 */
	public function registerPreview($nodeId, $bbCodeOptions = [])
	{
		$perms = $this->getPermissions(Api_InterfaceAbstract::instance(), $nodeId, 'public_preview');
		if ($perms === false)
		{
			return '';
		}

		$bbCodeOptions['cangetimgattachment'] = $perms['cangetimgattachment'];
		//This isn't specifically referenced but is needed to generate the placeholder/cachekey properly
		//if this isn't set then we end up with different renders sharing the same values.
		$bbCodeOptions['cangetattachment'] = $perms['cangetattachment'];

		$placeHolder = $this->getPlaceholderPre($nodeId, $bbCodeOptions);
		$this->pending[$placeHolder] = $nodeId;
		$this->bbCodeOptions[$placeHolder] = $bbCodeOptions;

		$cacheKey = $this->getCacheKey($nodeId, $bbCodeOptions, true, $perms['canview']);
		$this->placeHolders[$cacheKey] = $placeHolder;
		$this->cacheIdToNodeid[$cacheKey] = $nodeId;

		return $placeHolder;
	}

	/**
	 * Resets the array of items pending
	 */
	public function resetPending()
	{
		$this->pending = [];
	}

	/**
	 * This is the main function, called by the page renderer. It replaces all the placeholders with the parsed content.
	 *
	 * @param  string The page content. This currently will have all the placeholders
	 *
	 * @return string Page content with all the placeholders replaced with the parsed text.
	 */
	public function replacePlaceholders(&$content)
	{
		$this->fetchNodeText();

		foreach ($this->cache AS $placeHolder => $replace)
		{
			if (is_array($replace))
			{
				if (
					!empty($this->contentPages[$placeHolder]) AND
					intval($this->contentPages[$placeHolder])	AND
					intval($this->contentPages[$placeHolder]) AND
					(intval($this->contentPages[$placeHolder]) <= count($replace)))
				{
					$contentPage = intval($this->contentPages[$placeHolder]);
				}
				else
				{
					$contentPage = 1;
				}
				//Remember the array of page information starts at zero, not one.
				$content = str_replace($placeHolder, $replace[$contentPage - 1]['pageText'], $content);

			}
			else
			{
				$content = str_replace($placeHolder, $replace, $content);
			}
		}
	}

	protected function getPlaceholder($nodeId, $bbCodeOptions, $contentPage = 1)
	{
		if (empty($bbCodeOptions))
		{
			$result = self::PLACEHOLDER_PREFIX . $nodeId . '|' . $contentPage . self::PLACEHOLDER_SUFFIX;
		}
		else
		{
			ksort($bbCodeOptions);
			$result = self::PLACEHOLDER_PREFIX . $nodeId . '|' . $contentPage . ':'  . serialize($bbCodeOptions) . self::PLACEHOLDER_SUFFIX;
		}

		return $result;
	}

	protected function getPlaceholderPre($nodeId, $bbCodeOptions)
	{
		if (empty($bbCodeOptions))
		{
			return self::PLACEHOLDER_PREFIX . '_pre_' . $nodeId . self::PLACEHOLDER_SUFFIX;
		}
		ksort($bbCodeOptions);

		return self::PLACEHOLDER_PREFIX  . '_pre_'. $nodeId. ':'  . serialize($bbCodeOptions) . self::PLACEHOLDER_SUFFIX;
	}

	/**
	 * Returns the cache key to be used by vB_Cache
	 * @param type $nodeId
	 * @return string
	 */
	private function getCacheKey($nodeId, $bbCodeOptions, $preview, $canview)
	{
		$styleId = vB5_Template_Stylevar::instance()->getPreferredStyleId();
		$languageId = vB5_User::getLanguageId();

		$cacheKey = "vbNodeText". ($preview ? "_pre_" : '') . "{$nodeId}_{$styleId}_{$languageId}";

		if (vB5_Template_Options::instance()->get('options.cachebbcodebyusergroup'))
		{
			$groupkey = vB5_User::getGroupKey();
			$cacheKey .= '_' . $groupkey;
		}

		if (!$canview)
		{
			$cacheKey .= '_pvo)';
		}
		else if (!empty($bbCodeOptions))
		{
			ksort($bbCodeOptions);
			$cacheKey .= ':' . md5(json_encode($bbCodeOptions));
		}

		return strtolower($cacheKey);
	}

	protected function fetchNodeText()
	{
		// This function is the main function that's called for frontend parsing, hit downstream from vB5_Template::renderDelayed()
		// which calls nodetext::replacePlaceholders()
		// But other functions may be called for stuff like articles, previews, etc pending investigation.

		if (!empty($this->placeHolders))
		{
			// first try with cache
			$api = Api_InterfaceAbstract::instance();
			$cache = $api->cacheInstance(0);
			$found = $cache->read(array_keys($this->placeHolders));

			// We have at least two levels of caching that we need to consider
			// 1) is this parsedtest cache here, and
			// 2) is the cache lifetime for bbcode data (e.g. cached page info for bbcode url)
			if (!empty($found))
			{
				foreach ($found AS $cacheKey => $parsedText)
				{
					if (($parsedText !== false) AND !empty($this->cacheIdToNodeid[$cacheKey]))
					{
						$nodeId = $this->cacheIdToNodeid[$cacheKey];
						$placeHolder = $this->placeHolders[$cacheKey];
						$this->cache[$placeHolder] = $parsedText;
						unset($this->placeHolders[$cacheKey]);
						unset($this->pending[$placeHolder]);
					}
				}
			}

			if (!empty($this->pending))
			{
				// we still have to parse some nodes, fetch data for them
				$textDataArray = Api_InterfaceAbstract::instance()->callApi('content_text', 'getDataForParse', [$this->pending]);

				// preload avatars
				$this->registerUseridsForAvatarPreloading($textDataArray);

				// Preload bbcode data
				$bbcodeDataCache = new vB_BbCodeDataCache(vB_BbCodeHelper::instance(), $this->pending);

				$templateCache = vB5_Template_Cache::instance();
				$phraseCache = vB5_Template_Phrase::instance();
				$urlCache = vB5_Template_Url::instance();

				// In BBCode parser, the templates of inner BBCode are registered first,
				// so they should be replaced after the outer BBCode templates. See VBV-4834.

				//Also- if we have a preview we're likely to need the full text, and vice versa. So if either is requested
				// let's parse both.
				$templateCache->setRenderTemplatesInReverseOrder(true);

				foreach ($this->placeHolders AS $cacheKey => $placeHolder)
				{
					$nodeId = $this->pending[$placeHolder] ?? 0;

					if ($nodeId AND !empty($textDataArray[$nodeId]))
					{
						//If we got previewtext in textDataArray, we are done.
						if (isset($textDataArray[$nodeId]['preview_only']))
						{
							$previewText = $parsed = $textDataArray[$nodeId]['previewtext'];
							$canview = false;
						}
						else
						{
							$canview = true;
							[$previewText, $parsed] = $this->parseNode($textDataArray, $nodeId, $this->bbCodeOptions[$placeHolder], $bbcodeDataCache);

							// It's safe to do it here cause we already are in delayed rendering.
							$this->doOtherReplacePlaceholders($parsed);
							// also need to replace phrase & url placeholders for preview text
							$phraseCache->replacePlaceholders($previewText);
							$urlCache->replacePlaceholders($previewText);
							$canview = true;
						}

						// writing to cache has been moved from parseNode() to here so that
						// the cached text has the placeholders replaced. (VBV-9507)
						// any changes to the node requires update
						$events = ['nodeChg_' . $nodeId];
						// need to update cache if channel changes options
						$events[] = 'nodeChg_' .  $textDataArray[$nodeId]['channelid'];
						// also need to update if phrases have been modified
						$events[] = 'vB_Language_languageCache';

						// write the parsed text values to cache. cache for a week.
						$cache->write($this->getCacheKey($nodeId, $this->bbCodeOptions[$placeHolder], false, $canview), $parsed, 10080, $events);
						$cache->write($this->getCacheKey($nodeId, $this->bbCodeOptions[$placeHolder], true, $canview), $previewText, 10080, $events);

						if ($parsed !== false)
						{
							if (stripos($placeHolder, '_pre_') === false)
							{
								$this->cache[$placeHolder] = $parsed;
							}
							else
							{
								$this->cache[$placeHolder] = $previewText;
							}
						}
					}
				}

				// store any cachable data for next time.
				$bbcodeDataCache->saveChanges();

				$templateCache->setRenderTemplatesInReverseOrder(false);
			}
		}
	}

	/**
	 * gets the key used for storing page information
	 *
	 *	@param	int		the nodeid
	 *	@return	string  the cache key string
	 */
	protected function getPagingCacheKey($nodeid)
	{
		return 'vB_ArtPaging_' . $nodeid . '_' . vB5_User::getLanguageId();
	}

	/**
	 * @param $textDataArray
	 * @param $nodeId
	 * @param $bbcodeOptions
	 * @return array
	 */
	protected function parseNode($textDataArray, $nodeId, $bbcodeOptions, vB_BbCodeDataCache $bbcodeDataCache)
	{
		$textData = $textDataArray[$nodeId];
		$skipBbCodeParsing = $textData['disable_bbcode']; // if disable_bbcode is set (static pages), just use the rawtext

		$parser = new vB5_Template_BbCode();
		$parser->setRenderImmediate(true);
		$parser->setMultiPageRender($textData['channeltype'] == 'article');

		if (isset($textData['attachments']))
		{
			$parser->setAttachments($textData['attachments']);
		}
		if (isset($textData['attachments']) AND empty($textData['attachments']))
		{
			$parser->getAndSetAttachments($nodeId);
		}
		//make sure we have values for all the necessary options
		foreach (['allowimages', 'allowimagebbcode', 'allowbbcode', 'allowsmilies'] AS $option)
		{
			if (!empty($bbcodeOptions) AND isset($bbcodeOptions[$option]))
			{
				$textData['bbcodeoptions'][$option] = $bbcodeOptions[$option];
			}
			else if (!isset($textData['bbcodeoptions'][$option]))
			{
				$textData['bbcodeoptions'][$option] = false;
			}
		}

		// Set pass along some data required for bbcode data caching
		$parser->setDataCache($bbcodeDataCache);
		$parser->setNodeid($nodeId);


		/*
			bbcodeOptions['allowhtml'] comes from channel.options & 256 (bf_misc_forumoptions.allowhtml),
			except for public_preview > 0 articles that the user can't view... (see function vB_Api_Content_Text->getDataForParse() & queryef vBForum:getDataForParse)
			so we should actually be ignoring that, and using htmlstate only.
			Unfortunately, we can't just ignore it in the parser's doParse() function, because there is at least 1 other thing that seems to use allowhtml: announcements. I'm placing
			the change here instead of the parser in order to minimize risk.
			Alternatively, we could just make sure that every single channel is created with allowhtml set, but that'd also mean we're keeping this option, and adding
			an upgrade step to fix all old channels that may have been created with allowhtml unset.
		*/
		$textData['bbcodeoptions']['allowhtml'] = in_array($textData['htmlstate'], ['on', 'on_nl2br']);

		$allowimages = false;
		if (!empty($bbcodeOptions) AND !empty($bbcodeOptions['allowimages']))
		{
			$allowimages = $bbcodeOptions['allowimages'];
		}
		else if (!empty($bbcodeOptions['cangetimgattachment']))
		{
			$allowimages = $bbcodeOptions['cangetimgattachment'];
		}
		else if (!empty($textData['bbcodeoptions']['allowimages']))
		{
			$allowimages = $textData['bbcodeoptions']['allowimages'];
		}
		else if (!empty($textData['bbcodeoptions']['allowimagecode']))
		{
			$allowimages = $textData['bbcodeoptions']['allowimagecode'];
		}


		if ($textData['channeltype'] == 'article')
		{
			$paging = [];
			if (!$skipBbCodeParsing)
			{
				//If it's paginated we parse it here.
				$matches = [];
				$check = preg_match_all('#\[page\].*\[\/page\]#siU', $textData['rawtext'], $matches, PREG_OFFSET_CAPTURE);
				$start = 0;
				$title = $textData['title'];
				$parsed = [];

				// If [page] is at the beginning of the text, use it for the first page title
				// instead of using the article title for the first one.
				$hasFirstPageTitle = (bool) preg_match('#^\s*\[PAGE\]#siU', $textData['rawtext']);

				if (!empty($matches[0]))
				{
					foreach ($matches[0] AS $match)
					{
						if ($hasFirstPageTitle)
						{
							$hasFirstPageTitle = false;
							$start = strlen($match[0]) + $match[1];
							$title = vB_String::stripBbcode($match[0]);
							continue;
						}

						$rawtext = substr($textData['rawtext'], $start, $match[1] - $start);
						$currentText = $parser->doParse(
							$rawtext,
							$textData['bbcodeoptions']['allowhtml'],
							$textData['bbcodeoptions']['allowsmilies'],
							$textData['bbcodeoptions']['allowbbcode'],
							$allowimages,
							true, // do_nl2br
							false, // cachable
							$textData['htmlstate'],
							false, // minimal
							$textData['rawtext'],	// fulltext
							true // do_censor
						);
						$parsed[] = ['title' => $title, 'pageText' => $currentText];
						$start = strlen($match[0]) + $match[1];
						$title = vB_String::stripBbcode($match[0]);
					}

					if (!empty($start) AND ($start < strlen($textData['rawtext'])))
					{
						$rawtext = substr($textData['rawtext'], $start);
						$currentText = $parser->doParse(
							$rawtext,
							$textData['bbcodeoptions']['allowhtml'],
							$textData['bbcodeoptions']['allowsmilies'],
							$textData['bbcodeoptions']['allowbbcode'],
							$allowimages,
							true, // do_nl2br
							false, // cachable
							$textData['htmlstate'],
							false, // minimal
							$textData['rawtext'],	// fulltext
							true // do_censor
						);
						$parsed[] = ['title' => $title, 'pageText' => $currentText];
					}
				}

				$pageNo = 1;
				$phrases = vB5_Template_Phrase::instance();
				foreach ($parsed as $page)
				{
					if (empty($page['title']))
					{
						$page['title'] = $phrases->getPhrase('page_x', $pageNo);
					}
					$paging[$pageNo] = $page['title'];
					$pageNo++;
				}
			}
			else
			{
				$parsed = $textData['rawtext'];
				$matches[0] = 1; // skip re-parsing below.
			}
			Api_InterfaceAbstract::instance()->cacheInstance(0)->write($this->getPagingCacheKey($nodeId), $paging, 1440, 'nodeChg_' . $nodeId);
		}

		if (empty($matches[0]))
		{
			// Get full text
			$parsed = $parser->doParse(
				$textData['rawtext'],
				// todo: Remove this. We should be using htmlstate, not an outdated forum option that we're planning to remove.
				$textData['bbcodeoptions']['allowhtml'],
				$textData['bbcodeoptions']['allowsmilies'],
				$textData['bbcodeoptions']['allowbbcode'],
				$allowimages,
				true, // do_nl2br
				false, // cachable
				$textData['htmlstate'],
				false, // minimal
				$textData['rawtext'],	// fulltext
				true // do_censor
			);
		}

		// if textData has previewLength set, we always want to use it (articles)
		if (isset($textData['previewLength']))
		{
			$previewLength = $textData['previewLength'];
		}
		else
		{
			$previewLength = vB5_Template_Options::instance()->get('options.previewLength');
		}

		if ($skipBbCodeParsing)
		{
			// static pages from vb4 should always have text.previewtext set, taken from cms_nodeconfig.value where name = 'previewtext'
			// As such, we should always set the previewtext for static pages created in vB5.
			$previewText = $textData['previewtext'];
		}
		else
		{
			$previewText = $parser->get_preview(
				$textData['rawtext'],
				$previewLength,
				$textData['bbcodeoptions']['allowhtml'],
				true,
				$textData['htmlstate'],
				[
					'do_smilies' => $textData['bbcodeoptions']['allowsmilies'],
					'allowPRBREAK' => (!empty($textData['disableBBCodes']['prbreak'])),
				]
			);
		}

		if (is_array($parsed))
		{
			// for multi-paged articles, $parsed is an array, let's check the length
			// of the first page of that article for purposes of appending the ellipsis
			$parsedLength = strlen($parsed[0]['pageText']);
		}
		else
		{
			$parsedLength = strlen($parsed);
		}

		// Append ellipsis if preview text is shorter than parsed full text.
		// One special case to note is if previewText has 0 length. This could happen if the previewText is
		// entirely composed of bbcodes that are stripped via parsing
		// If we want special behavior, we should check for that case here and not append the ellipsis
		if ($parsedLength > strlen($previewText))
		{
			$previewText .= '...';
		}

		return [$previewText, $parsed];
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 114264 $
|| #######################################################################
\*=========================================================================*/
