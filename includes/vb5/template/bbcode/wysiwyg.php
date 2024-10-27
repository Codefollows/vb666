<?php if (!defined('VB_ENTRY')) die('Access denied.');
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
* BB code parser for the WYSIWYG editor
*
* @package	vBulletin
*/
class vB5_Template_BbCode_Wysiwyg extends vB5_Template_BbCode
{
	/**
	* List of tags the WYSIWYG BB code parser should not parse.
	*
	* @var	array
	*/
	protected $unparsed_tags = [
		'thread'    => 'thread',
		'post'      => 'post',
		'quote'     => 'quote',
		'highlight' => 'highlight',
		'noparse'   => 'noparse',
		'video'     => 'video',
		'sigpic'    => 'sigpic',

		// leave these parsed, because <space><space> needs to be replaced to emulate pre tags
		//'php',
		//'code',
		//'html',
	];

	protected function getBbcodeRenderOptions() : array
	{
		$renderOptions = parent::getBbcodeRenderOptions();
		// Do not show previews in WYSIWYG (editor) mode.
		// TODO: does source mode need anything?
		$renderOptions['url_preview'] = false;
		// Disable ellipsizing/truncating URL on WYSIWYG editor, so that editing a
		// post with URL bbcode will not fail to expand to preview on save.
		$renderOptions['url_truncate'] = false;

		vB::getHooks()->invoke('hookGetBbcodeRenderOptions',[
			'context' => 'EDITOR',
			'renderOptions' => &$renderOptions,
		]);

		return $renderOptions;
	}

	public function __construct($appendCustomTags = true)
	{
		$this->setStripSpace(false);
		parent::__construct($appendCustomTags);

		if (!empty(self::$customTags['no_option']))
		{
			foreach(self::$customTags['no_option'] AS $tagname => $taginfo)
			{
				if (!isset($this->unparsed_tags[$tagname]))
				{
					$this->unparsed_tags[$tagname] = $tagname;
				}
			}
		}
		if (!empty(self::$customTags['option']))
		{
			foreach(self::$customTags['option'] AS $tagname => $taginfo)
			{
				if (!isset($this->unparsed_tags[$tagname]))
				{
					$this->unparsed_tags[$tagname] = $tagname;
				}
			}
		}

		// change all unparsable tags to use the unparsable callback
		foreach ($this->unparsed_tags AS $remove)
		{
			if (isset($this->tag_list['option']["$remove"]))
			{
				$this->tag_list['option']["$remove"]['callback'] = 'handle_wysiwyg_unparsable';
				unset($this->tag_list['option']["$remove"]['html'], $this->tag_list['option']["$remove"]['strip_space_after']);
			}
			if (isset($this->tag_list['no_option']["$remove"]))
			{
				$this->tag_list['no_option']["$remove"]['callback'] = 'handle_wysiwyg_unparsable';
				unset($this->tag_list['no_option']["$remove"]['html'], $this->tag_list['option']["$remove"]['strip_space_after']);
			}
		}

		// make the "pre" tags use the correct handler
		foreach (['code', 'php', 'html'] AS $pre_tag)
		{
			if (isset($this->tag_list['no_option']["$pre_tag"]))
			{
				$this->tag_list['no_option']["$pre_tag"]['callback'] = 'handle_preformatted_tag';
				unset($this->tag_list['no_option']["$pre_tag"]['html'], $this->tag_list['option']["$pre_tag"]['strip_space_after']);
			}
		}
	}

	/**
	* No-op so that non-inline attchments are not rendered at the end of the
	* text for the WYSIWYG editor, since we're now using the parent implementation
	* of handle_bbcode_img().
	*
	* @param	string	Text to append attachments
	* @param	array	Attachment data
	* @param	bool	Whether to show images
	* @param	array	Array of nodeid => (nodeid, filedataid) attachments that should not be included in the attachment box.
	*/
	function append_noninline_attachments($text, $attachments, $do_imgcode = false, $skiptheseattachments = [])
	{
		return $text;
	}

	/**
	* Handles a [code]/[html]/[php] tag. In WYSIYWYG parsing, keeps the tag but replaces
	* <space><space> with a non-breaking space followed by a space.
	*
	* @param	string	The code to display
	*
	* @return	string	Tag with spacing replaced
	*/
	function handle_preformatted_tag($code)
	{
		$current_tag =& $this->currentTag;
		$tag_name = (isset($current_tag['name_orig']) ? $current_tag['name_orig'] : $current_tag['name']);

		return "[$tag_name]" . $this->emulate_pre_tag($code) . "[/$tag_name]";
	}

	/**
	* This does it's best to emulate an HTML pre tag and keep whitespace visible
	* in a standard HTML environment. Useful with code/html/php tags.
	*
	* @param	string	Code to process
	*
	* @return	string	Processed code
	*/
	function emulate_pre_tag($code)
	{
		$code = str_replace('  ', ' &nbsp;', $code);
		$code = preg_replace('#(\r\n|\n|\r|<p>)( )(?!([\r\n]}|<p>))#i', '$1&nbsp;', $code);
		return $code;
	}

	/**
	* Parses out specific white space before or after cetain tags, rematches
	* tags where necessary, and processes line breaks.
	*
	* @param	string	Text to process
	* @param	bool	Whether to translate newlines to HTML breaks (unused)
	*
	* @return	string	Processed text
	*/
	function parse_whitespace_newlines($text, $do_nl2br = true)
	{
		$whitespacefind = [
			'#(\r\n|\n|\r)?( )*(\[\*\]|\[/list|\[list|\[indent)#si',
			'#(/list\]|/indent\])( )*(\r\n|\n|\r)?#si'
		];
		$whitespacereplace = [
			'\3',
			'\1'
		];
		$text = preg_replace($whitespacefind, $whitespacereplace, $text);
		$text = nl2br($text);

		// convert tabs to four &nbsp;
		$text = str_replace("\t", '&nbsp;&nbsp;&nbsp;&nbsp;', $text);

		return $text;
	}

	/**
	 * Callback for preg_replace_callback in parse_whitespace_newline
	 */
	protected function bbcodeRematchTagsWysiwygPregMatch1($matches)
	{
		return $this->bbcode_rematch_tags_wysiwyg($matches[3], $matches[2], $matches[1]);
	}

	/**
	 * Callback for preg_replace_callback in parse_whitespace_newline
	 */
	protected function bbcodeRematchTagsWysiwygPregMatch2($matches)
	{
		return $this->bbcode_rematch_tags_wysiwyg($matches[2], $matches[1]);
	}

	/**
	 * Callback for preg_replace_callback in parse_whitespace_newline
	 */
	protected function removeWysiwygBreaksPregMatch3($matches)
	{
		return $this->remove_wysiwyg_breaks($matches[0]);
	}

	/**
	* Parse an input string with BB code to a final output string of HTML
	*
	* @param string	$input_text
	* @param bool $do_smilies Whether to parse smilies
	* @param bool $do_imgcode Whether to parse img (for the video tags)
	* @param bool $do_html Whether to allow HTML (for smilies)
	* @param bool $do_censor Whether to censor the text
	*
	* @return	string	Ouput Text (HTML)
	*/
	protected function parseBbcode($input_text, $do_smilies, $do_imgcode, $do_html = false, $do_censor = true)
	{
		$text = parent::parseBbcode($input_text, $do_smilies, $do_imgcode, $do_html, $do_censor);

		// need to display smilies in code/php/html tags as literals
		$text = preg_replace_callback('#\[(code|php|html)\](.*)\[/\\1\]#siU', [$this, 'stripSmiliesPregMatch'], $text);
		return $text;
	}

	/**
	 * Callback for preg_replace_callback in parseBbcode
	 */
	protected function stripSmiliesPregMatch($matches)
	{
		return $this->stripSmilies($matches[0], true);
	}

	/**
	* Call back to handle any tag that the WYSIWYG editor can't handle. This
	* parses the tag, but returns an unparsed version of it. The advantage of
	* this method is that any parsing directives (no parsing, no smilies, etc)
	* will still be applied to the text within.
	*
	* @param	string	Text inside the tag
	*
	* @return	string	The unparsed tag and the text within it
	*/
	//keeping this function as is for the moment in case there are compatiblity issues
	//with the name and other bbcode parsers.
	function handle_wysiwyg_unparsable($text)
	{
		return $this->handle_unparsable($text);
	}

	/**
	* Handles a single bullet of a list
	*
	* @param	string	Text of bullet
	*
	* @return	string	HTML for bullet
	*/
	function handle_bbcode_list_element($text)
	{
		$bad_tag_list = '(br|p|li|ul|ol)';

		$exploded = preg_split("#(\r\n|\n|\r)#", $text);

		$output = '';
		foreach ($exploded AS $value)
		{
			if (!preg_match('#(</' . $bad_tag_list . '>|<' . $bad_tag_list . '\s*/>)$#iU', $value))
			{
				if (trim($value) == '')
				{
					$value = '&nbsp;';
				}
				$output .= $value . "<br />\n";
			}
			else
			{
				$output .= "$value\n";
			}
		}
		$output = preg_replace('#<br />+\s*$#i', '', $output);

		return "<li>$output</li>";
	}

	/**
	* Automatically inserts a closing tag before a line break and reopens it after.
	* Also wraps the text in the tag. Workaround for IE WYSIWYG issue.
	*
	* @param	string	Text to search through
	* @param	string	Tag to close and reopen (can't include the option)
	* @param	string	Raw text that opens the tag (this needs to include the option if there is one)
	*
	* @return	string	Processed text
	*/
	function bbcode_rematch_tags_wysiwyg($innertext, $tagname, $tagopen_raw = '')
	{
		// This function replaces line breaks with [/tag]\n[tag].
		// It is intended to be used on text inside [tag] to fix an IE WYSIWYG issue.

		$tagopen_raw = str_replace('\"', '"', $tagopen_raw);
		if (!$tagopen_raw)
		{
			$tagopen_raw = $tagname;
		}

		$innertext = str_replace('\"', '"', $innertext);
		return "[$tagopen_raw]" . preg_replace('#(\r\n|\n|\r)#', "[/$tagname]\n[$tagopen_raw]", $innertext) . "[/$tagname]";
	}

	/**
	* Removes IE's WYSIWYG breaks from within a list.
	*
	* @param	string	Text to remove breaks from. Should start with [list] and end with [/list]
	*
	* @return	string	Text with breaks removed
	*/
	function remove_wysiwyg_breaks($fulltext)
	{
		$fulltext = str_replace('\"', '"', $fulltext);
		preg_match('#^(\[list(=(&quot;|"|\'|)(.*)\\3)?\])(.*?)(\[/list(=\\3\\4\\3)?\])$#siU', $fulltext, $matches);
		$prepend = $matches[1];
		$innertext = $matches[5];

		$find = ["</p>\n<p>", '<br />', '<br>'];
		$replace = ["\n", "\n", "\n"];
		$innertext = str_replace($find, $replace, $innertext);

		return $prepend . $innertext . '[/list]';
	}

	public function getTableHelper()
	{
		if (!isset($this->tableHelper))
		{
			$this->tableHelper = new vB5_Template_BbCode_Tablewysiwyg($this);
		}

		return $this->tableHelper;
	}

	protected function handle_bbcode_node($text, $nodeId)
	{
		//add some tags to allow wysiwyg parser to convert this back to a bbcode we don't need them in other
		//contexts (at least at the moment) so let's no pollute the markup with them.

		//a bit of a hack and very dependant on knowing the parent return but avoids repeating a bunch of logic.
		$return = parent::handle_bbcode_node($text, $nodeId);

		$nodeId = intval($nodeId);

		//if we don't have a nodeId then the text is really the nodeId and we should treat the text as blank
		$noTextAttr = '';
		if(!$nodeId OR !$text)
		{
			$noTextAttr = 'data-notext="1" ';
		}

		if(!$nodeId)
		{
			$nodeId = intval($text);
		}

		$link = str_replace('<a', '<a ' . $noTextAttr . 'data-nodeid="' . $nodeId . '"', $return);
		return $link;
	}

	/**
	 * Displays the [USER] bbcode
	 *
	 * @param	string	Username
	 * @parma	int		User ID
	 *
	 * @return	string	Rendered USER bbcode.
	 */
	protected function handle_bbcode_user($username = '', $userid = '')
	{
		$userid = (int) $userid;
		$vboptions = vB::getDatastore()->getValue('options');
		// todo: update this for displaynames once user autosuggest work is done

		// this implementation is used when rendering a post for the editor (when editing a post)

		// keep this markup in sync with the other 2 implementations of handle_bbcode_user()
		// and with autocompleteSelect() in ckeditor.js
		if ($vboptions['userbbcodeavatar'])
		{
			$avatar = Api_InterfaceAbstract::instance()->callApi('User', 'fetchAvatar', [$userid, true]);
			$avatarUrl = (!$avatar['isfullurl'] ? $vboptions['bburl'] . '/' : '')  . $avatar['avatarpath'];

			return '<a href="#" style="background-image:url(\'' . $avatarUrl . '\');" class="b-bbcode-user b-bbcode-user--has-avatar js-bbcode-user" data-userid="' . $userid . '">' . $username . '</a>';
		}
		else
		{
			return '<a href="#" class="b-bbcode-user js-bbcode-user" data-userid="' . $userid . '">' . $username . '</a>';
		}
	}

	protected function handle_bbcode_hashtag($text, $id)
	{
		//add some tags to allow wysiwyg parser to convert this back to a bbcode we don't need them in other
		//contexts (at least at the moment) so let's no pollute the markup with them.

		//a bit of a hack and very dependant on knowing the parent return but avoids repeating a bunch of logic.
		$return = parent::handle_bbcode_hashtag($text, $id);

		//we need specifically to mark the channel hashtags seperate from the tag hashtags.
		if($id[0] == 'c')
		{
			$attr = 'data-nodeid="' . substr($id, 1) . '"';
			$return = str_replace('<a', '<a ' . $attr, $return);
		}

		return $return;
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 114640 $
|| #######################################################################
\*=========================================================================*/
