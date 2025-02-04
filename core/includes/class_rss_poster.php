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

function getElementsByTagName(&$array, $tagname, $reinitialise = false, $depth = 0)
{
	static $output = array();

	if ($reinitialise)
	{
		$output = array();
	}

	if (is_array($array))
	{
		foreach (array_keys($array) AS $key)
		{
			if ($key === $tagname) // encountered an oddity with RDF feeds where == was evaluating to true when key was 0 and $tagname was 'item'
			{
				if (is_array($array["$key"]))
				{
					if (!empty($array["$key"][0]))
					{
						foreach (array_keys($array["$key"]) AS $item_key)
						{
							$output[] =& $array["$key"]["$item_key"];
						}
					}
					else
					{
						$output[] =& $array["$key"];
					}
				}
			}
			else if (is_array($array["$key"]) AND $depth < 30)
			{
				getElementsByTagName($array["$key"], $tagname, false, $depth + 1);
			}
		}
	}

	return $output;
}

function get_item_value($item)
{
	return (is_array($item) ? $item['value'] : $item);
}

class vB_RSS_Poster
{
	var $xml_string = null;
	var $xml_array = null;
	var $xml_object = null;
	var $template = null;
	var $feedtype = null;

	function set_xml_string($xml_string)
	{
		$this->xml_string = $xml_string;
	}

	function fetch_xml($url)
	{
		$vurl = vB::getUrlLoader();
		$vurl->setOption(vB_Utility_Url::FOLLOWLOCATION, 1);
		$vurl->setOption(vB_Utility_Url::TIMEOUT, 5);
		$vurl->setOption(vB_Utility_Url::ENCODING, 'gzip');

		// For the current purposes I don't think the & is even necessary here
		// since $vurl is an object, but leaving it in in case they want to swap
		// it out entirely, or something. Though we should probably define an
		// actual interface that vB_Utility_Url implements so that extensions
		// can implement a swappable properly.
		// We may want to add a $handled = false; 'handled' => &$handled, param
		// if we expect multiple extensions to handle the same $url and need
		// extensions to claim certain ones as already handled...
		vB::getHooks()->invoke('hookRssFetchXmlPreGet', [
			'url' => $url,
			'vurl' => &$vurl,
		]);

		$xml_string = $vurl->get($url);
		if ($xml_string === false OR empty($xml_string['body']))
		{
			$this->set_xml_string('');
		}
		else
		{
			$xml_string = $xml_string['body'];

			// There are some RSS feeds that embed (HTML) tags within the description without
			// CDATA. While this is actually invalid, try to workaround it by wrapping the
			// contents in CDATA if it contains a < and is not in CDATA already.
			// This must be done before parsing because our parser can't handle the output.
			if (preg_match_all('#(<description>)(.*)(</description>)#siU', $xml_string, $matches, PREG_SET_ORDER))
			{
				foreach ($matches AS $match)
				{
					if (strpos(strtoupper($match[2]), '<![CDATA[') === false AND strpos($match[2], '<') !== false)
					{
						// no CDATA tag, but we have an HTML tag
						$output = $match[1] . '<![CDATA[' . vB_XML_Builder::escape_cdata($match[2]) . ']]>' . $match[3];
						$xml_string = str_replace($match[0], $output, $xml_string);
					}
				}
			}

			$this->set_xml_string($xml_string);
		}
		return true;
	}

	/**
	 * Parses the RSS XML
	 * If the target encoding is not specified then the XML parser will resolve it
	 * from the current language settings.
	 * The source encoding can be overridden with $override_encoding.  This forces
	 * the RSS feed to be interpreted with that character set.  This should only be
	 * done if the RSS fails to report it's encoding or reports it incorrectly.
	 *
	 * @param	string	The target encoding of the
	 * @param	bool	Whether to ncrencode unicode
	 * @param	string	Override the source encoding
	 */
	function parse_xml($target_encoding = '', $ncrencode = false, $override_encoding = '', $escape_html = false)
	{
		$this->xml_object = new vB_XML_Parser($this->xml_string);

		// Set xml encoding
		$this->xml_object->disable_legacy_mode();
		$this->xml_object->set_target_encoding($target_encoding, $ncrencode, $escape_html);
		$this->xml_object->set_encoding($override_encoding);

		if ($parsedData = $this->xml_object->parse_xml())
		{
			$this->xml_array =& $parsedData;
			if (!empty($this->xml_array['xmlns']) AND preg_match("#^http://www.w3.org/2005/atom$#i", $this->xml_array['xmlns']))
			{
				$this->feedtype = 'atom';
			}
			else if (is_array($this->xml_array['channel']))
			{
				$this->feedtype = 'rss';
			}
			else
			{ // Rather than just continue with an unknown type, show an error
				$this->xml_array = [];
				$this->feedtype = 'unknown';
				return false;
			}
			return true;
		}
		else
		{
			$this->xml_array = [];
			$this->feedtype = '';
			return false;
		}
	}

	public function fetch_items()
	{
		switch($this->feedtype)
		{
			case 'atom':
			{
				$tagname = 'entry';
				break;
			}
			case 'rss':
			default:
			{
				$tagname = 'item';
			}
		}
		return getElementsByTagName($this->xml_array, $tagname, true);
	}

	function parse_template($template, $item, $unhtmlspecialchars = true)
	{
		if (preg_match_all('#\{(?:feed|rss):([\w:\[\]]+)\}#siU', $template, $matches))
		{
			foreach ($matches[0] AS $match_number => $field)
			{
				$replace = $this->fetch_replacement($matches[1][$match_number], $item);
				$template = str_replace($field, $replace, $template);
			}
		}

		if ($unhtmlspecialchars)
		{
			$template = unhtmlspecialchars($template);
		}

		return $template;
	}

	private function fetch_replacement($field, $item)
	{
		switch ($this->feedtype)
		{
			case 'atom':
			{
				$handled_value = null;
				// Legacy Hook 'rssposter_parse_atom' Removed //
				if ($handled_value !== null)
				{
					return $handled_value;
				}

				switch($field)
				{
					case 'link':
					{
						if (empty($item['link']))
						{
							if (!empty($item['guid']))
							{
								return $item['guid']['value'];
							}
							return '';
						}
						else if (empty($item['link'][0]))
						{
							return $item['link']['href'];
						}
						else
						{
							foreach ($item['link'] AS $link)
							{
								if ($link['rel'] == 'alternate' OR empty($link['rel']))
								{
									return $link['href'];
								}
							}
						}
					}
					break;

					case 'description':
					{
						return get_item_value($item['summary']);
					}
					break;

					case 'title':
					{
						return get_item_value($item['title']);
					}
					break;

					case 'id':
					{
						return get_item_value($item['id']);
					}
					break;

					case 'date':
					{
						$timestamp = strtotime(get_item_value($item['updated']));

						if ($timestamp > 0)
						{
							return vbdate(vB::getDatastore()->getOption('dateformat') . " " . vB::getDatastore()->getOption('timeformat'), $timestamp);
						}
						else
						{
							return get_item_value($item['updated']);
						}
					}
					break;

					case 'enclosure_link':
					{
						if (empty($item['link'][0]))
						{
							return '';
						}
						else
						{
							foreach ($item['link'] AS $link)
							{
								if ($link['rel'] == 'enclosure')
								{
									return $link['href'];
								}
							}
						}
					}
					break;

					case 'content':
					case 'content:encoded':
					{
						if (empty($item['content'][0]))
						{
							return get_item_value($item['content']);
						}
						else
						{
							$return = array();
							foreach($item['content'] AS $contents)
							{
								if (is_array($contents))
								{
									if ($contents['type'] == 'html' AND !($return['type'] == 'xhtml'))
									{
										$return = $contents;
									}
									elseif ($contents['type'] == 'text' AND !($return['type'] == 'html' OR $return['type'] == 'xhtml'))
									{
										$return = $contents;
									}
									elseif ($contents['type'] == 'xhtml')
									{
										$return = $contents;
									}
									elseif ($contents['type'] != 'xhtml' OR $contents['type'] != 'xhtml' OR $contents['type'] != 'xhtml')
									{
										$return = $contents;
									}
								}
								else
								{
									if (empty($return['type']))
									{
										$return['value'] = $contents;
									}
								}
							}

							return $return['value'];
						}
					}
					break;

					case 'author':
					{
						return get_item_value($item['author']['name']);
					}
					break;

					default:
					{
						if (is_array($item["$field"]))
						{
							if (is_string($item["$field"]['value']))
							{
								return $item["$field"]['value'];
							}
							else
							{
								return '';
							}
						}
						else
						{
							return $item["$field"];
						}
					}
				}
			}

			case 'rss':
			{
				$handled_value = null;
				// Legacy Hook 'rssposter_parse_rss' Removed //
				if ($handled_value !== null)
				{
					return $handled_value;
				}

				switch ($field)
				{
					case 'link':
					{
						if (empty($item['link']))
						{
							if (!empty($item['guid']))
							{
								return $item['guid']['value'];
							}
							return '';
						}
						else if (is_array($item['link']) AND isset($item['link']['href']))
						{
							return $item['link']['href'];
						}
						else
						{
							return get_item_value($item['link']);
						}
					}
					break;

					case 'description':
					{	// this can be handled by the default case
						return get_item_value($item['description']);
					}
					break;

					case 'title':
					{	// this can be handled by the default case
						return get_item_value($item['title']);
					}
					break;

					case 'id':
					case 'guid':
					{
						return get_item_value($item['guid']);
					}
					break;

					case 'pubDate':
					case 'date':
					{
						$timestamp = strtotime(get_item_value($item['pubDate']));

						if ($timestamp > 0)
						{
							return vbdate(vB::getDatastore()->getOption('dateformat') . " " . vB::getDatastore()->getOption('timeformat'), $timestamp);
						}
						else
						{
							return $item['pubDate'];
						}
					}
					break;

					case 'enclosure_link':
					case 'enclosure_href':
					{
						if (is_array($item['enclosure']))
						{
							return $item['enclosure']['url'];
						}
						else
						{
							return '';
						}
					}
					break;

					case 'content':
					case 'content:encoded':
					{
						return get_item_value($item['content:encoded']);
					}
					break;

					case 'author':
					case 'dc:creator':
					{
						if (isset($item['dc:creator']))
						{
							return get_item_value($item['dc:creator']);
						}
						else
						{
							return $item['author'];
						}
					}
					break;

					default:
					{
						if (is_array($item["$field"]))
						{
							if (is_string($item["$field"]['value']))
							{
								return $item["$field"]['value'];
							}
							else
							{
								return '';
							}
						}
						else
						{
							return $item["$field"];
						}
					}

				}
			}
		}
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 112105 $
|| #######################################################################
\*=========================================================================*/
