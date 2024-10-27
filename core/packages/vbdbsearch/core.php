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
class vBDBSearch_Core extends vB_Search_Core
{
	const asymptote = 5;
	const slope = 20;

	/**************INDEXING**************************/

	/**
	 * Index a node
	 */
	public function indexText($node, $title, $text, $forceupdate = false)
	{
		$wordinfo = $this->getWordsForNode($title, $text);

		// no meaningful words, no index
		$assertor = vB::getDbAssertor();
		if (empty($wordinfo['words']))
		{
			// updating the CRC32 value so it would not be picked up by the indexer again
			$assertor->update('vBForum:node', ['CRC32' => 1], ['nodeid' => $node['nodeid']]);
			return false;
		}

		//count the number of occurance of each word
		$total_words = count($wordinfo['words']);

		$crc = sprintf('%u', crc32(implode(' ', $wordinfo['words'])));
		// check if the same content has already been indexed (update)
		// We reduce the check to checking the current CRC with the old(stored) CRC of the content
		if (!$forceupdate AND $node['CRC32'] == $crc)
		{
			// no need to index if the content hasn't changed
			return false;
		}

		// fetch previously indexed word ids
		$prev_index = $assertor->getRows('vBDBSearch:fetch_indexed_words', ['nodeid' => $node['nodeid']], false, 'wordid');

		// update the existing node with the new CRC
		$assertor->update('vBForum:node', ['CRC32' => $crc], ['nodeid' => $node['nodeid']]);

		//fetch the ids of the words
		[$existing_words, $canonical] = $this->addWords($assertor, $wordinfo['occurances']);
		$searchword_tables_unique = [];

		$total_words = count($wordinfo['words']);

		$consolidatedInfo = $this->consolidateWords($wordinfo, $existing_words);
		foreach ($consolidatedInfo AS $wordid => $worddata)
		{
			$score_params = [
				'total_words' => $total_words,
				'occurance_nr' => $worddata['occurance_nr'],
				'words' => $worddata['words'],
				'is_title' => $worddata['is_title'],
				'position' => $worddata['position'],
			];
			$score = $this->get_score($score_params);

			$wordquerydata = [
				'nodeid' => $node['nodeid'],
				'wordid' => $wordid,
				'score' => $score,
				'is_title' => $worddata['is_title'],
				'position' => $worddata['position'],
			];

			//let's check if this word has previously been indexed
			if (array_key_exists($wordid, $prev_index))
			{
				//do we need to update it?
				if (
					$prev_index[$wordid]['score'] != $score OR
					$prev_index[$wordid]['is_title'] != $worddata['is_title'] OR
					$prev_index[$wordid]['position'] != $worddata['position']
				)
				{
					$wordquerydata['suffix'] = $prev_index[$wordid]['suffix'];
					$assertor->assertQuery('vBDBSearch:updateSearchtowords', $wordquerydata);
				}
				//now that we know the previous index is accurate, we can remove it from the list of obsolete index
				unset($prev_index[$wordid]);
			}
			else // it looks like a new word that needs to be indexed
			{
				// create a relation between the word and the searchword_table
				$suffix = $this->get_table_name($canonical[$wordid]);
				$searchword_tables_unique[$suffix][] = $wordquerydata;
			}
		}

		foreach ($searchword_tables_unique AS $suffix => $worddata)
		{
			if (empty($worddata))
			{
				continue;
			}

			try
			{
				$assertor->insertMultiple('vBDBSearch:searchtowords_' . $suffix, array_keys(reset($worddata)), $worddata);
			}
			catch (Exception $e)
			{
				//just ignore the error, if this query fails it means the node is already indexed
			}
		}

		// let's clean up the entries from the previous indexing that are not used anymore
		foreach ($prev_index AS $wordid => $details)
		{
			$assertor->delete('vBDBSearch:searchtowords_' . $details['suffix'], ['wordid' => $wordid, 'nodeid' => $node['nodeid']]);
		}

		return true;
	}

	private function getWordsForNode($title, $text)
	{
		$result = [];
		$result['title'] = $this->break_words($title);
		$result['words'] = array_merge($result['title'], $this->break_words($text));

		if ($result['words'])
		{
			//count the number of occurance of each word
			$result['occurances'] = array_count_values($result['words']);
			$result['words'] = array_values(array_unique($result['words']));
		}
		else
		{
			$result['occurances'] = [];
			$result['words'] = [];
		}
		return $result;
	}

	/**
	 *	Index a batch of nodes
	 */
	public function indexTextBatch($nodes, $titles, $texts, $forceupdate = false)
	{
		$db = vB::getDbAssertor();

		$wordinfo = [];
		foreach ($titles AS $nodeid => $tile)
		{
			$wordinfo[$nodeid] = $this->getWordsForNode($titles[$nodeid], $texts[$nodeid]);
		}

		// update the existing node with the new CRC
		$crc = [];
		foreach ($wordinfo AS $nodeid => $info)
		{
			if (!$info['words'])
			{
				//if we don't have any words update the CRC to a placeholder so
				//we don't think the node hasn't been indexed.  This *may* primary
				//refer to the obsolete searchindex script that used this as a marker
				$crc[$nodeid] = 1;
				unset($wordinfo[$nodeid]);
			}
			else
			{
				$newcrc = sprintf('%u', crc32(implode(' ', $info['words'])));
				//the crc matches so we don't need to process this node (and updating the CRC
				//is pointless because it's already the same).
				if ($nodes[$nodeid]['CRC32'] == $crc)
				{
					//if we are forcing the update then we don't want to unset wordinfo.
					//however it still doesn't make sense to update the CRC if it hasn't changed.
					if (!$forceupdate)
					{
						unset($wordinfo[$nodeid]);
					}
				}
				else
				{
					$crc[$nodeid] = $newcrc;
				}
			}
		}

		//if nothing changed this batch then don't try the update.  It will fail.
		if ($crc)
		{
			$db->assertQuery('vBDBSearch:updateNodeCrc', ['crc' => $crc]);
		}

		//if we don't have anything left to update then bail.  We might need to update
		//the crc even if we don't have anything here due to the fake crc for empty nodes.
		if (!$wordinfo)
		{
			return;
		}

		$allWords = [];
		foreach ($wordinfo AS $nodeid => $info)
		{
			//we use occurances to keep the words in the key.  This should make deduping faster.
			$allWords = array_replace($allWords, $info['occurances']);
		}

		[$existing_words, $canonical] = $this->addWords($db, $allWords);
		unset($allWords);

		$existing_index_words = [];
		$result = $db->assertQuery('vBDBSearch:fetch_indexed_words', ['nodeid' => array_keys($wordinfo)]);
		foreach ($result AS $row)
		{
			$existing_index_words[$row['nodeid']][$row['wordid']] = $row;
		}

		$searchword_tables_unique = [];
		foreach ($wordinfo AS $nodeid => $info)
		{
			$prev_index = [];
			if (isset($existing_index_words[$nodeid]))
			{
				$prev_index = $existing_index_words[$nodeid];
			}

			$total_words = count($info['words']);

			$consolidatedInfo = $this->consolidateWords($info, $existing_words);
			foreach ($consolidatedInfo AS $wordid => $worddata)
			{
				// create a relation between the word and the searchword_table
				$suffix = $this->get_table_name($canonical[$wordid]);

				$score_params = [
					'total_words' => $total_words,
					'occurance_nr' => $worddata['occurance_nr'],
					'words' => $worddata['words'],
					'is_title' => $worddata['is_title'],
					'position' => $worddata['position'],
				];
				$score = $this->get_score($score_params);

				$wordquerydata = [
					'nodeid' => $nodeid,
					'wordid' => $wordid,
					'score' => $score,
					'is_title' => $worddata['is_title'],
					'position' => $worddata['position'],
				];

				//let's check if this word has previously been indexed
				if (isset($prev_index[$wordid]))
				{
					//do we need to update it?
					if (
						$prev_index[$wordid]['score'] != $score OR
						$prev_index[$wordid]['is_title'] != $worddata['is_title'] OR
						$prev_index[$wordid]['position'] != $worddata['position']
					)
					{
						$searchword_tables_unique[$suffix][] = $wordquerydata;
					}

					//regardless of if we need to update, we found the word so we can mark
					//it off the list
					unset($prev_index[$wordid]);
				}
				else // it looks like a new word that needs to be indexed
				{
					// creating a list of indexes partitioned by the searchtowords tables
					$searchword_tables_unique[$suffix][] = $wordquerydata;
				}
			}

			// let's clean up the entries from the previous indexing that are not used anymore
			foreach ($prev_index AS $wordid => $details)
			{
				$db->delete('vBDBSearch:searchtowords_' . $details['suffix'], ['wordid' => $wordid, 'nodeid' => $nodeid]);
			}

		}

		foreach ($searchword_tables_unique AS $suffix => $values)
		{
			if (empty($values))
			{
				continue;
			}

			$db->assertQuery('vBDBSearch:insertUpdateSearchWords', [
				'suffix' => $suffix,
				'values' => $values
			]);

		}
	}

	// Handle the case where we have mutliple different "words" that are the same for the DB collation.  It's a little
	// complicated because we have no ability to validate that in PHP since we don't know what the collation is nor do we
	// have access to libraries that manage the collation rules.  So we'll use the wordid to match equivilant words and
	// keep a list of all of the unique words that map to that id.  For the most part this is more of hypothetical than
	// real problem since much of the "score" data is no longer used to calculate the score and we don't use the actual
	// word text for much else.
	private function consolidateWords($info, $existing_words)
	{
		$newinfo = [];
		foreach ($info['words'] AS $index => $word)
		{
			if (!isset($existing_words[$word]))
			{
				continue;
			}
			else
			{
				$wordid = $existing_words[$word];
			}

			if (isset($newinfo[$wordid]))
			{
				// add the occurances to the other words, add to wordlist, set is_title to 1 if *any* variant is in the title.
				$newinfo[$wordid]['occurance_nr'] += $info['occurances'][$word];
				$newinfo[$wordid]['words'][] = $word;
				$newinfo[$wordid]['is_title'] = (($newinfo[$wordid]['is_title'] OR in_array($word, $info['title'])) ? 1 : 0);
			}
			else
			{
				// Position is 1 based.  It's also the first occurance of the word in the post	(this is used for
				// various scoring calculations).  We'll preserve the lowest index if we have multiple matching words.
				$newinfo[$wordid] = [
					'occurance_nr' => $info['occurances'][$word],
					'words' => [$word],
					'position' => $index + 1,
					'is_title' => (in_array($word, $info['title']) ? 1 : 0),
				];
			}
		}

		return $newinfo;
	}

	public function reIndexAll()
	{
		return false;
	}

	public function emptyIndex()
	{
		parent::emptyIndex();
		$assertor = vB::getDbAssertor();
		$assertor->assertQuery('truncateTable', ['table' => 'words']);

		$names = $this->get_table_name_suffixes();
		foreach ($names as $name)
		{
			$assertor->assertQuery('truncateTable', ['table' => 'searchtowords_' . $name]);
		}
	}

	/**
	 *	Updates the trending post data based
	 *
	 *	@param int $lookback -- the time, in hours, that we consider threads and replies for trending
	 *	@param int $minimum -- the minimum time, in hours, that we will take as the thread lifetime.
	 *		Threads created more recently than this will be consider to have been created minimum hours
	 *		ago for purposes of scoring
	 */
	public function indexTrending($lookback, $minimum)
	{
		$lookback = (int) $lookback;
		$minimum = (int) $minimum;

		$db = vB::getDbAssertor();

		$channelApi = vB_Api::instance('content_channel');
		$channels = $channelApi->fetchTopLevelChannelIds();

		$channelTypeId = vB_Types::instance()->getContentTypeID('vBForum_Channel');

		$timenow = vB::getRequest()->getTimenow();
		$timecut = $timenow - ($lookback * 3600);

		$db->assertQuery('vBDBSearch:addNewTrendingTopics', [
			'timecut' => $timecut,
			'excludetypes' => $channelTypeId,
			'specialchannelid' => $channels['special'],
		]);

		$db->assertQuery('vBDBSearch:deleteOldTrendingTopics', [
			'timecut' => $timecut,
		]);

		//see comment for the query on how this works.
		$db->assertQuery('vBDBSearch:setTrendingWeights', [
			'timenow' => $timenow,
			'timecut' => $timecut,
			'excludetypes' => $channelTypeId,
			'minlifetime' => $minimum,
		]);
	}

	/**************DELETING**************************/

	public function delete($nodeid, $node = false)
	{
		$data = [
			vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_DELETE,
			'nodeid' => $nodeid,
		];
		$assertor = vB::getDbAssertor();
		$names = $this->get_table_name_suffixes();
		foreach ($names as $name)
		{
			$assertor->assertQuery('vBDBSearch:searchtowords_' . $name, $data);
		}

		//delete any trending information for this node.
		$assertor->delete('vBForum:trending', ['nodeid' => $nodeid]);
	}

	public function deleteBulk($nodeids)
	{
		if (empty($nodeids))
		{
			return;
		}

		$data = [
			vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_DELETE,
			'nodeid' => $nodeids,
		];
		$assertor = vB::getDbAssertor();
		$names = $this->get_table_name_suffixes();
		foreach ($names as $name)
		{
			$assertor->assertQuery('vBDBSearch:searchtowords_' . $name, $data);
		}
	}


	/**************SEARCHING**************************/

	public function getResults(vB_Search_Criteria $criteria)
	{
		$results = $this->getTwoPassResults($criteria);
		if (is_array($results))
		{
			$nodeids = [];
			foreach ($results AS $nodeid)
			{
				$nodeids[$nodeid] = $nodeid;
			}

			return $nodeids;
		}

		$cacheKey = $results;
		$assertor = vB::getDbAssertor();

		// Check criteria before it *might* get modified by the method query.
		$equalsFilters = $criteria->get_equals_filters();
		$results = $assertor->assertQuery(
			'vBDBSearch:getSearchResults',
			[
				vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_METHOD,
				'criteria' => $criteria,
				'cacheKey' => $cacheKey,
			]
		);

		/*
			Ensure array is keyed by nodeid, like above.
			However, I'm not sure if getResults() actually needs to key the return array via
			nodeid. It *may* be needed as a way to easily de-dupe nodeids, but it seems like
			not every single return path bothered to do so, and I'm not sure if that was oversight
			or intended.
			For now, going with the more common cases of return nodeid arrays being keyed by nodeid.
		 */
		$nodeids = [];
		if ($results AND $results->valid())
		{
			foreach ($results as $result)
			{
				$nodeids[$result['nodeid']] = $result['nodeid'];
			}
			// Free memory held by result class. If buffered, this could be large.
			// Also importantly, the destruct will call the free_result() function to
			// release the database result object as soon as we no longer need it.
			unset($results);

			// Note, $cacheKey will be false if there are any post processors.
			if ($cacheKey)
			{
				// VBV-14803, allow new replies to immediately invalidate the two-pass cache.
				// This works because "channelid" in such a case is actually the topic starter, which
				// gets a node change event called upon it when a reply is added to it.
				if (!empty($equalsFilters['channelid']) AND is_numeric($equalsFilters['channelid']))
				{
					vB_Search_Core::saveSecondPassResults($nodeids, $cacheKey, $equalsFilters['channelid']);
				}
				else
				{
					vB_Search_Core::saveSecondPassResults($nodeids, $cacheKey);
				}
			}
		}

		$post_processors = $criteria->get_post_processors();
		foreach ($post_processors as $post_processor)
		{
			// nothing else to process
			if (empty($nodeids))
			{
				break;
			}
			// create new $resultset based on those node ids
			$resultset = $assertor->assertQuery(
				'vBDBSearch:' . $post_processor,
				[
					'nodeids' => $nodeids,
					'criteria' => $criteria
				]
			);

			$nodeids = [];
			foreach ($resultset as $result)
			{
				$nodeids[$result['nodeid']] = $result['nodeid'];
			}
			// Same deal as above, we're going to use unbuffered queries for postprocessors, so free the result object ASAP.
			unset($resultset);
		}

		return $nodeids;
	}

	/**
	 * breaks up the text into words
	 * @param string $text
	 * @return array
	 */
	private function break_words($text)
	{
		if (!$text)
		{
			return [];
		}

		$text = strip_tags($text);
		$text = strip_bbcode($text, true, false, false, true);

		//we should consider handling all text via the "multibyte" process
		//ie convert to utf-8 if it isn't and then convert back.  It will provide more consistent
		//results and avoid having to maintain duplicate code.
		$is_mb = preg_match('/[^\x00-\x7F]/', $text);
		if (!$is_mb)
		{
			$is_mb = (strlen($text) != vB_String::vbStrlen($text));
		}

		//null will use the site default value
		$charset = null;
		$string = vB::getString();
		if ($is_mb)
		{
			//do the processing as utf-8... tryinng to handle in the native charset is going to too weird.
			$text = $string->toUtf8($text);
			$charset = 'utf8';
			if (preg_match('/&#([0-9]+);|[^\x00-\x7F]/siU', $text) AND function_exists('mb_decode_numericentity'))
			{
				$text = mb_decode_numericentity($text, [0x0, 0x2FFFF, 0, 0xFFFF], 'UTF-8');
			}

			//unicode punctuation: currently smart quotes both single and double
			$unicodepunc = hex2bin('E28098E28099E2809CE2809D');
			$pattern = '/[\s,.!?@#$%^&*\(\)\\/<>"\';:\[\]\{\}\+|' . $unicodepunc . ']/u';
		}
		else
		{
			$pattern = '/[^a-z0-9_\-]+/i';
		}

		$words = preg_split($pattern, $string->strtolower($text, $charset), -1, PREG_SPLIT_NO_EMPTY);
		/*
			split words including dashes separately such that for "word1-word2", the words "word1-word2", "word1" and "word2"
			are all indexed.
		 */
		$dashWordIndices = [];
		foreach ($words AS $index => $word)
		{
			//trim any - characters from the front and back of the word
			//if we converted the text to utf-8, then covert the words back to the default charset.
			if ($is_mb)
			{
				$word = preg_replace("/(^-+)|(-+$)/u", '', $word);
				$words[$index] = $string->toDefault($word, 'utf-8');
			}
			else
			{
				$words[$index] = trim($word, '-');
			}

			if ($string->strpos($words[$index], '-') !== false)
			{
				$dashWordIndices[] = $index;
			}
		}

		if (!empty($dashWordIndices))
		{
			$useMbSplit = ($is_mb AND function_exists('mb_split'));
			foreach ($dashWordIndices AS $__wordInd)
			{
				$moreWords = [];
				if ($useMbSplit)
				{
					// Note, apparently mb_split() does *not* use /delimiters/ for the pattern
					// unlike the preg_* functions. So '/-+/' will NOT work.
					// The docs don't really specify, but figured out the issue thanks to this
					// helpful comment:
					// http://php.net/manual/en/function.mb-split.php#103470
					$moreWords = mb_split('-+', $words[$__wordInd]);
				}
				else
				{
					// Not using explode to avoid inserting empty words in cases like abc---def
					//$moreWords = explode('-', $words[$__wordInd]);
					$moreWords = preg_split('/-+/', $words[$__wordInd]);
				}
				// Splitting *then* combining it back like this removes instances of multiple dashes... (e.g. word1--word2 -> word1, word1-word2, word2)
				//$moreWords = static::getWordCombinationsInOrder($moreWords);
				$words = array_merge($words, $moreWords);
			}
		}

		$validator = new vB_Search_KeywordValidator();

		//we do not want to dedup the list *yet*.  The frequency with which words appear is
		//relevant to the search and that will be handled by the caller via array_count_values
		foreach ($words AS $index => $word)
		{
			// Skip certain words
			// 1) Is an empty string
			// 2) multiple consecutive hypens.  We don't want to index whole words like 'aaaa----bbbb' or '----' chances are these are not things
			//		people will actually search on.  We may need to tweak it based on feedback but for now we'll
			//		treat anything except a single dash as a word breaker
			// 3) fails our word criteria for instance too short or too long or on the blocked words list
			if (
				!$word OR
				$string->strpos($word, '--') !== false OR
				!$validator->isIndexable($word)
			)
			{
				unset($words[$index]);
			}
		}

		return $words;
	}

	protected static function getWordCombinationsInOrder($words)
	{
		/*
			Given a, b, c, d ... char_n

                          0    1    2   3     n-1
                         /    /    /    /    /     depth    concat & inserts
                        a    b    c    d ... n       0      (1: a)
                         \  / \  / \  /
                          ab   bc   cd   ...         1      (2: ab, b)
                           \   /\   /
                            abc  bcd     ...         2      (3: abc, bc, c)
                              \  /
                              abcd       ...         3      (4: abcd, bcd, cd, d) so each depth requires i + 1 inserts
                                  ...
                                    \     /
                                    abcd...n            n-1 (n: abcd...n, bcd...n, cd...n, ...n, n)
			Each depth requires i+1 concatenations & inserts, and we have n-1 depth, so roughly a O(n^2) operation
			which isn't great (but at least we don't have to generate all permutations of words, yay?)...

			For memory, we only have to keep track of the "last inserted row" (diagonal in the direction of top label,
			e.g. [abc, bc, c] for depth 2 inserts when creating depth 3 inserts). In addition to the accumulation
			and the original word list we're walking through, that's ~ 3 [n] that we have at the end of the loop.
			Not great either, but no nested arrays.
		 */
		$superSet = [];
		$diagonal = [];
		$i = 0;
		foreach ($words AS $__word)
		{
			foreach ($diagonal AS $__dInd => $__combinedWord)
			{
				$diagonal[$__dInd] .= "-" . $__word;
				$superSet[] = $diagonal[$__dInd];
			}
			$diagonal[] = $__word;
			$superSet[] = $__word;
		}

		return $superSet;
	}

	/**
	 * gets the suffixes that need to be appended to the searchtowords table names
	 * @return array
	 */
	public static function get_table_name_suffixes()
	{
		$suffixes = range('a', 'z');
		$suffixes[] = 'other';
		return $suffixes;
	}

	/**
	 * add multiple words into the words table
	 * @param array  -- [$existing, $canonlical] where existing is actualword => wordid, and canonical is
	 * 	wordid => value in word table.  This allows us to navigate cases where two (or more) words differ but are the same
	 * 	under the selected collation.  In most cases we want to use the actual word but in others we want to make
	 * 	sure we handle them the same so we'll use actual or canonical depending.  $existing must use the word as the key
	 * 	becasue the wordid is no guarenteed to be unique.
	 */
	private function addWords($db, $words_flipped)
	{
		if (empty($words_flipped))
		{
			return [];
		}

		// this isn't strictly speaking necesary, but INSERT IGNORE will in most cases increment the auto increment key for each item in the list
		// regardless of whether it's already there (ON DUPLICATE KEY isn't better).  There is going to be *alot* of overlap for words and
		// that can eat up key space pretty rapidly if reindexing happens a lot.  This shouldn't slow us down much (we'll pull all
		// of the words either way -- this just makes it two queries instead of one and we'll still be batching in the Batch case).
		//
		// See if all the words are in the words table. Note, we have to use the fetchExistingWords query, otherwise
		// if we have the "word" may not exactly match the $words_flipped, which is a problem in PHP.
		// E.g. if we have noël indexed, and collation treats it same as noel, and search for noel via directly quering
		// against the `words` table, we'll get noël back (which is correct per collation) but PHP will fail to match
		// it against noel and the searchtowords_XYZ record will not be created in indexText()

		$existing = [];
		$canonical = [];
		$result = $db->assertQuery('vBDBSearch:fetchExistingWords', ['words' => array_keys($words_flipped)]);
		foreach ($result AS $row)
		{
			$word = $row['word'];
			$wordid = $row['wordid'];

			$existing[$word] = $wordid;
			$canonical[$wordid] = $row['canonicalword'];
			unset($words_flipped[$word]);
		}

		//all the words exist, nothing left to do.
		if (!$words_flipped)
		{
			return [$existing, $canonical];
		}

		$remaining_words = array_keys($words_flipped);
		unset($words_flipped);

		$db->assertQuery('vBDBSearch:insertWords', [
			'words' => $remaining_words,
		]);

		//get the missing(just added) wordids
		$result = $db->assertQuery('vBDBSearch:fetchExistingWords', ['words' => $remaining_words]);
		foreach ($result AS $row)
		{
			$word = $row['word'];
			$wordid = $row['wordid'];

			$existing[$word] = $wordid;
			$canonical[$wordid] = $row['canonicalword'];
		}

		return [$existing, $canonical];
	}

	/**
	 * finds which searchtowords table a word belongs to
	 * @var string $word
	 * @return array
	 */
	public static function get_table_name($word)
	{
		if (empty($word))return false;
		$firstchar = $word[0];
		$suffixes = self::get_table_name_suffixes();
		// do we have a valid character?
		if (($index = array_search($firstchar, $suffixes)) !== false)
		{
			return $suffixes[$index];
		}
		// all numbers and non-valid characters are stored in the 'searchtowords_other' table
		return 'other';
	}

	/**
	 *
	 * The function for the word weight uses an asymptotic function
	 * @param array $word_info contains the information about the word
	 */
	protected function get_score($word_info)
	{
		$score = self::asymptote - ((self::asymptote-1) * exp(-1 * ($word_info['occurance_nr']-1)/self::slope));
		return round($score * 10000);

		//		$score = ceil($word_info['occurance_nr'] * 100 / $word_info['total_words']);
		//		//if the word is in the title, add more weight to it
		//		$score = min(100, $score + (empty($word_info['is_title'])?0:50));
		//		return $score;
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 116495 $
|| #######################################################################
\*=========================================================================*/
