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
* BB code parser's start state. Looking for the next tag to start.
*/
define('BB_PARSER_START', 1);

/**
* BB code parser's "this range is just text" state.
* Requires $internal_data to be set appropriately.
*/
define('BB_PARSER_TEXT', 2);

/**
* Tag has been opened. Now parsing for option and closing ].
*/
define('BB_PARSER_TAG_OPENED', 3);

/**
* Stack based BB code parser.
*
* @package 		vBulletin
*/
class vB_BbCodeParser
{
	use vB_Trait_NoSerialize;

	/**#@+
	 * These make up the bit field to control what "special" BB codes are found in the text.
	 */
	const BBCODE_HAS_IMG		= 1;
	const BBCODE_HAS_ATTACH		= 2;
	const BBCODE_HAS_SIGPIC		= 4;
	const BBCODE_HAS_RELPATH	= 8;
	/**#@-*/

	/**
	* A list of tags to be parsed.
	* Takes a specific format. See function that defines the array passed into the c'tor.
	*
	* @var	array
	*/
	protected $tag_list = [];

	/**
	* The stack that will be populated during final parsing. Used to check context.
	*
	* @var	array
	*/
	var $stack = array();

	/**
	* Holder for the output of the BB code parser while it is being built.
	*
	* @var	string
	*/
	var $parse_output = '';

	/**
	* Used alongside the stack. Holds a reference to the node on the stack that is
	* currently being processed. Only applicable in callback functions.
	*/
	var $current_tag = null;

	/**
	* Whether this parser is parsing for printable output
	*
	* @var	bool
	*/
	var $printable = false;

	/**
	* Reference to the main registry object
	*
	* @var	vB_Registry
	*/
	var $registry = null;

	/**
	* Holds various options such what type of things to parse and cachability.
	*
	* @var	array
	*/
	var $options = array();

	/**
	* Holds the cached post if caching was enabled
	*
	* @var	array	keys: text (string), has_images (int)
	*/
	var $cached = array();

	/**
	* Reference to attachment information pertaining to this post
	*
	* @var	array
	*/
	var $attachments = null;

	/**
	* Whether this parser unsets attachment info in $this->attachments when an inline attachment is found
	*
	* @var	bool
	*/
	var $unsetattach = false;

	/**
	 * Id of the forum the source string is in for permissions
	 *
	 * @var integer
	 */
	var $forumid = 0;

	/**
	 * Id of the outer container, if applicable
	 *
	 * @var mixed
	 */
	var $containerid = 0;

	/**
	* True if custom tags have been fetched
	*
	* @var	bool
	*/
	var $custom_fetched = false;

	/**
	* Local cache of smilies for this parser. This is per object to allow WYSIWYG and
	* non-WYSIWYG versions on the same page.
	*
	* @var array
	*/
	var $smilie_cache = array();

	/**
	* If we need to parse using specific user information (such as in a sig),
	* set that info in this member. This should include userid, custom image revision info,
	* and the user's permissions, at the least.
	*
	* @var	array
	*/
	var $parse_userinfo = array();

	/**
	* The number that is the maximum node when parsing for tags. count(nodes)
	*
	* @var	int
	*/
	var $node_max = 0;

	/**
	* When parsing, the number of the current node. Starts at 1. Note that this is not
	* necessary the key of the node in the array, but reflects the number of nodes handled.
	*
	* @var	int
	*/
	var $node_num = 0;

	/** Template for generating quote links. We need to override for cms comments" **/
	protected $quote_printable_template = 'bbcode_quote_printable';

	/** Template for generating quote links. We need to override for cms comments" **/
	protected $quote_template =  'bbcode_quote';

	/**Additional parameter(s) for the quote template. We need for cms comments **/
	protected $quote_vars = false;

	/**
	*	Display full size image attachment if an image is [attach] using without =config, otherwise display a thumbnail
	*
	*/
	protected $displayimage = false;

	protected $userImagePermissions = array();

	// Mainly for the signature parser to disable lightboxing.
	protected $doLightbox = true;

	protected $filedataidsToAttachmentids = [];
	protected $skipAttachmentList;
	protected $snippet_length;
	protected $default_previewlen;

	private $local_smilies;

	/**
	* Constructor. Sets up the tag list.
	*
	* @param	vB_Registry	Reference to registry object
	* @param	array		List of tags to parse
	* @param	bool		Whether to append customer user tags to the tag list
	*/
	public function __construct(&$registry, $tag_list = [], $append_custom_tags = true)
	{
		$this->registry =& $registry;
		$this->tag_list = $tag_list;

		if ($append_custom_tags)
		{
			$this->append_custom_tags();
		}

	}

	public function setAttachments($attachments, $skipattachlist = false)
	{
		/*
		This parser is missing this method which allows attach bbcode handling
		to work. However it doesn't seem to be used in a context where we'll
		be rendering attach bbcodes.
		 */
		$this->userImagePermissions = [];
		$currentUserid = vB::getUserContext()->fetchUserId();
		if (is_array($attachments) AND !empty($attachments))
		{
			foreach($attachments AS $attachment)
			{
				$this->attachments[$attachment['nodeid']] = $attachment;
				// There might be multiple attachments referencing the same filedataid. We should keep track of all filedataid
				// to attachmentid mappings in case we need them later.
				$this->filedataidsToAttachmentids[$attachment['filedataid']][$attachment['nodeid']] = $attachment['nodeid'];

				// only used for legacy attachments fetched later. If a legacy attachment came through here, it means
				// it was found in-text, and we shouldn't include in the attachments list.
				if ($skipattachlist AND $this->unsetattach)
				{
					$this->skipAttachmentList[$attachment['nodeid']] = array(
						'attachmentid' => $attachment['nodeid'],
						'filedataid' => $attachment['filedataid'],
					);

				}


				// Parser needs to know about permissions in order to process "show image|thumbnail or show anchor" decisions.
				$this->checkImagePermissions($currentUserid, $attachment['parentid']);
			}
		}
	}


	public function getAttachments()
	{
		return $this->attachments;
	}

	// Copied here for consistency with frontend parser, these aren't used atm.
	/**
	 * Called by vB5_Template_Nodetext's parse(), this function fetchs publicly available
	 * attachment data for the parent $nodeid and sets that data to the class property
	 * $this->attachments via setAttachments() above. This method of separate fetching is required
	 * as the text data used by the nodetext parser will lack certain attachment information
	 * necessary for correctly rendering [attach] bbcodes & the attachments list if the current
	 * user lacks certain permissions
	 *
	 * @param	int		$nodeid		Nodeid of the content node that's currently being rendered.
	 */
	/*
	public function getAndSetAttachments($nodeid)
	{
		// attachReplaceCallback() will only show an img tag if $this->options['do_imgcode']; is true
		$photoTypeid = vB_Types::instance()->getContentTypeId('vBForum_Photo');
		$attachments = array();
		$apiResult = vB_Api::instance('node')->getNodeAttachmentsPublicInfo($nodeid);
		if (!empty($apiResult[$nodeid]))
		{
			$rawAttach = $apiResult[$nodeid];
		}
		else
		{
			return;
		}

		foreach ($rawAttach AS $attach)
		{
			// Gallery photos should not show up in the attachment list.
			if ($attach['contenttypeid'] != $photoTypeid)
			{
				$attachments[$attach['nodeid']] = $attach;
			}
		}

		$this->setAttachments($attachments);
	}
	*/

	/*
	 * Bulk fetch filedata records & add to $this->filedatas using filedataid as key.
	 * Used by createcontent controller's parseWysiwyg action, when editor is switched from
	 * source to wysiwyg mode, for new attachments with tempids.
	 */
	/*
	public function prefetchFiledata($filedataids)
	{
		if (!empty($filedataids))
		{
			$imagehandler = vB_Image::instance();
			$filedataRecords = vB_Api::instance('filedata')->fetchFiledataByid($filedataids);
			foreach ($filedataRecords AS $record)
			{
				$record['isImage'] = $imagehandler->isImageExtension($record['extension']);
				$this->filedatas[$record['filedataid']] = $record;
			}
		}
	}
	*/

	/**
	 * Sets the engine to render immediately
	 *
	 *	@param	bool	whether to set immediate on or off
	 */
	/*
	public function setRenderImmediate($immediate = true)
	{
		$this->renderImmediate = $immediate;
	}
	*/
	// END UNUSED COPIED FUNCS FROM FRONTEND PARSER

	/**
	* Loads any user specified custom BB code tags into the $tag_list
	*/
	function append_custom_tags()
	{
		if ($this->custom_fetched == true)
		{
			return;
		}

		$this->custom_fetched = true;
		$loaded = false;
		// this code would make nice use of an interator
		if ($this->registry->bbcodecache !== null) // get bbcodes from the datastore
		{
			$has_errors = false;
			foreach($this->registry->bbcodecache AS $customtag)
			{
				// the datastore record is not valid, we have to load the values
				if (
					!is_array($customtag)
					OR !array_key_exists('twoparams', $customtag)
					OR !array_key_exists('bbcodereplacement', $customtag)
					OR !array_key_exists('strip_empty', $customtag)
					OR !array_key_exists('stop_parse', $customtag)
					OR !array_key_exists('disable_smilies', $customtag)
					OR !array_key_exists('disable_wordwrap', $customtag)
				)
				{
					$has_errors = true;
					break;
				}
				$has_option = $customtag['twoparams'] ? 'option' : 'no_option';
				$customtag['bbcodetag'] = strtolower($customtag['bbcodetag']);

				$this->tag_list["$has_option"]["$customtag[bbcodetag]"] = array(
					'html'             => $customtag['bbcodereplacement'],
					'strip_empty'      => $customtag['strip_empty'],
					'stop_parse'       => $customtag['stop_parse'],
					'disable_smilies'  => $customtag['disable_smilies'],
					'disable_wordwrap' => $customtag['disable_wordwrap'],
				);
			}
			$loaded = !$has_errors;
		}

		//it's not available in the datastore or it has failed
		if (!$loaded) // query bbcodes out of the database
		{
			$this->registry->bbcodecache = array();

			$bbcodes = vB_Library::instance('bbcode')->fetchBBCodes();
			foreach($bbcodes as $customtag)
			{
				$has_option = $customtag['twoparams'] ? 'option' : 'no_option';
				$customtag['bbcodetag'] = strtolower($customtag['bbcodetag']);
				$this->tag_list["$has_option"]["$customtag[bbcodetag]"] = array(
					'html'             => $customtag['bbcodereplacement'],
					'strip_empty'      => (intval($customtag['options']) & $this->registry->bf_misc['bbcodeoptions']['strip_empty']) ? 1 : 0 ,
					'stop_parse'       => (intval($customtag['options']) & $this->registry->bf_misc['bbcodeoptions']['stop_parse']) ? 1 : 0 ,
					'disable_smilies'  => (intval($customtag['options']) & $this->registry->bf_misc['bbcodeoptions']['disable_smilies']) ? 1 : 0 ,
					'disable_wordwrap' => (intval($customtag['options']) & $this->registry->bf_misc['bbcodeoptions']['disable_wordwrap']) ? 1 : 0
				);

				$this->registry->bbcodecache["$customtag[bbcodeid]"] = $customtag;
			}
		}
	}

	/**
	* Sets the user the BB code as parsed as. As of 3.7, this function should
	* only be called for parsing signatures (for sigpics and permissions).
	*
	* @param	array	Array of user info to parse as
	* @param	array	Array of user's permissions (may come through $userinfo already)
	*/
	function set_parse_userinfo($userinfo, $permissions = null)
	{
		$this->parse_userinfo = $userinfo;
		if ($permissions)
		{
			$this->parse_userinfo['permissions'] = $permissions;
		}
	}

	/**
	* Collect parser options and misc data and fully parse the string into an HTML version
	*
	* @param	string	Unparsed text
	* @param	int|str	ID number of the forum whose parsing options should be used or a "special" string
	* @param	bool	Whether to allow smilies in this post (if the option is allowed)
	* @param	bool	Whether to parse the text as an image count check
	* @param	string	Preparsed text ([img] tags should not be parsed)
	* @param	int		Whether the preparsed text has images
	* @param	bool	Whether the parsed post is cachable
	* @param	string	Switch for dealing with nl2br
	*
	* @return	string	Parsed text
	*/
	function parse($text, $forumid = 0, $allowsmilie = true, $isimgcheck = false, $parsedtext = '', $parsedhasimages = 3, $cachable = false, $htmlstate = null)
	{
		global $calendarinfo;

		$this->forumid = $forumid;

		$donl2br = true;

		if (empty($forumid))
		{
			$forumid = 'nonforum';
		}

		switch($forumid)
		{
			// Parse Calendar
			case 'calendar':
				$dohtml = $calendarinfo['allowhtml'];
				$dobbcode = $calendarinfo['allowbbcode'];
				$dobbimagecode = $calendarinfo['allowimgcode'];
				$dosmilies = $calendarinfo['allowsmilies'];
				break;

			// parse private message
			case 'privatemessage':
				$dohtml = false;
				$dobbcode = $this->registry->options['privallowbbcode'];
				$dobbimagecode = true;
				$dosmilies = $this->registry->options['privallowsmilies'];
				break;

			// parse signature
			case 'signature':
				if (!empty($this->parse_userinfo['permissions']))
				{
					$dohtml = ($this->parse_userinfo['permissions']['signaturepermissions'] & $this->registry->bf_ugp_signaturepermissions['allowhtml']);
					$dobbcode = ($this->parse_userinfo['permissions']['signaturepermissions'] & $this->registry->bf_ugp_signaturepermissions['canbbcode']);
					$dobbimagecode = ($this->parse_userinfo['permissions']['signaturepermissions'] & $this->registry->bf_ugp_signaturepermissions['allowimg']);
					$dosmilies = ($this->parse_userinfo['permissions']['signaturepermissions'] & $this->registry->bf_ugp_signaturepermissions['allowsmilies']);
					break;
				}
				// else fall through to nonforum

			// parse non-forum item
			case 'nonforum':
				$dohtml = $this->registry->options['allowhtml'];
				$dobbcode = $this->registry->options['allowbbcode'];
				$dobbimagecode = $this->registry->options['allowbbimagecode'];
				$dosmilies = $this->registry->options['allowsmilies'];
				break;

			// parse visitor/group/picture message
			case 'visitormessage':
			case 'groupmessage':
			case 'socialmessage':
				$dohtml = $this->registry->options['allowhtml'];
				$dobbcode = $this->registry->options['allowbbcode'];
				$dobbimagecode = true; // this tag can be disabled manually; leaving as true means old usages remain (as documented)
				$dosmilies = $this->registry->options['allowsmilies'];
				break;

			// parse forum item
			default:
				// explicit defaults to get rid of warnings. Same behavior as before.
				$dohtml = false;
				$dobbimagecode = false;
				$dosmilies = false;
				$dobbcode = false;
				if (intval($forumid))
				{
//					$forum = fetch_foruminfo($forumid);
//					$dohtml = $forum['allowhtml'];
//					$dobbimagecode = $forum['allowimages'];
//					$dosmilies = $forum['allowsmilies'];
//					$dobbcode = $forum['allowbbcode'];
				}
				// else they'll basically just default to false -- saves a query in certain circumstances
				break;
		}

		if (!$allowsmilie)
		{
			$dosmilies = false;
		}

		// Legacy Hook 'bbcode_parse_start' Removed //

		if (!empty($parsedtext) AND (!defined('VB_API') OR !VB_API))
		{
			if ($parsedhasimages)
			{
				return $this->handle_bbcode_img($parsedtext, $dobbimagecode, $parsedhasimages);
			}
			else
			{
				return $parsedtext;
			}
		}
		else
		{
			return $this->do_parse($text, $dohtml, $dosmilies, $dobbcode, $dobbimagecode, $donl2br, $cachable, $htmlstate);
		}
	}

	/**
	* Parse the string with the selected options
	*
	* @param string $text Unparsed text
	* @param bool $do_html Whether to allow HTML (true) or not (false)
	* @param bool $do_smilies Whether to parse smilies or not
	* @param bool $do_bbcode Whether to parse BB code
	* @param bool $do_imgcode Whether to parse the [img] BB code (independent of $do_bbcode)
	* @param bool $do_nl2br Whether to automatically replace new lines with HTML line breaks
	* @param bool $cachable Whether the post text is cachable
	* @param string $htmlstate Switch for dealing with nl2br
	* @param boolean $minimal Do minimal required actions to parse bbcode
	* @param bool $do_censor Whether to censor the text
	*
	* @return	string	Parsed text
	*/
	function do_parse(
		$text,
		$do_html = false,
		$do_smilies = true,
		$do_bbcode = true,
		$do_imgcode = true,
		$do_nl2br = true,
		$cachable = false,
		$htmlstate = null,
		$minimal = false,
		$do_censor = true
	)
	{
		global $html_allowed;
		if ($htmlstate)
		{
			switch ($htmlstate)
			{
				case 'on':
					$do_nl2br = false;
					break;
				case 'off':
					$do_html = false;
					break;
				case 'on_nl2br':
					$do_nl2br = true;
					break;
			}
		}

		$this->options = array(
			'do_html'    => $do_html,
			'do_smilies' => $do_smilies,
			'do_bbcode'  => $do_bbcode,
			'do_imgcode' => $do_imgcode,
			'do_nl2br'   => $do_nl2br,
			'cachable'   => $cachable
		);
		$this->cached = array('text' => '', 'has_images' => 0);

		$fulltext = $text;

		// ********************* REMOVE HTML CODES ***************************
		if (!$do_html)
		{
			$text = vB_String::htmlSpecialCharsUni($text);
		}
		$html_allowed = $do_html;

		if (!$minimal)
		{
			$text = $this->parse_whitespace_newlines($text, $do_nl2br);
		}
		// ********************* PARSE BBCODE TAGS ***************************
		if ($do_bbcode)
		{
			$text = $this->parse_bbcode($text, $do_smilies, $do_imgcode, $do_html, $do_censor);
		}
		else if ($do_smilies)
		{
			$text = $this->parse_smilies($text, $do_html);
		}

		$has_img_tag = 0;
		if (!$minimal)
		{
			// parse out nasty active scripting codes
			static $global_find = array('/(javascript):/si', '/(about):/si', '/(vbscript):/si', '/&(?![a-z0-9#]+;)/si');
			static $global_replace = array('\\1<b></b>:', '\\1<b></b>:', '\\1<b></b>:', '&amp;');
			$text = preg_replace($global_find, $global_replace, $text);

			$has_img_tag = ($do_bbcode ? max(array($this->contains_bbcode_img_tags($fulltext), $this->contains_bbcode_img_tags($text))) : 0);
		}

		// Legacy Hook 'bbcode_parse_complete_precache' Removed //

		// save the cached post
		if ($this->options['cachable'])
		{
			$this->cached['text'] = $text;
			$this->cached['has_images'] = $has_img_tag;
		}
		// do [img] tags if the item contains images
		if(($do_bbcode OR $do_imgcode) AND $has_img_tag)
		{
			$text = $this->handle_bbcode_img($text, $do_imgcode, $has_img_tag, $fulltext);
		}

		// this parser currently does not generate the attachments list.
		//$text = $this->append_noninline_attachments($text, $this->attachments, $do_imgcode, $this->skipAttachmentList);

		// Legacy Hook 'bbcode_parse_complete' Removed //

		return $text;
	}

	/**
	 * This is copied from the blog bbcode parser. We either have a specific
	 * amount of text, or [PRBREAK][/PRBREAK].
	 *
	 * @param	array	Fixed tokens
	 * @param	integer	Length of the text before parsing (optional)
	 *
	 * @return	array	Tokens, chopped to the right length.
	 */
	public function get_preview($pagetext, $initial_length = 0, $do_html = false, $do_nl2br = true, $htmlstate = null)
	{
		if ($htmlstate)
		{
			switch ($htmlstate)
			{
				case 'on':
					$do_nl2br = false;
					break;
				case 'off':
					$do_html = false;
					break;
				case 'on_nl2br':
					$do_nl2br = true;
					break;
			}
		}

		$this->options = array(
			'do_html'    => $do_html,
			'do_smilies' => false,
			'do_bbcode'  => true,
			'do_imgcode' => false,
			'do_nl2br'   => $do_nl2br,
			'cachable'   => true
		);

		global $html_allowed;
		$html_allowed = $do_html;

		if (!$do_html)
		{
			$pagetext = htmlspecialchars_uni($pagetext);
		}
		$pagetext = $this->parse_whitespace_newlines(trim(strip_quotes($pagetext)), $do_nl2br);
		$tokens = $this->fix_tags($this->build_parse_array($pagetext));

		$counter = 0;
		$stack = array();
		$new = array();
		$over_threshold = false;

		if (strpos($pagetext, '[PRBREAK][/PRBREAK]'))
		{
			$this->snippet_length = strlen($pagetext);
		}
		else if (intval($initial_length))
		{
			$this->snippet_length = $initial_length;

		}
		else
		{
			$this->snippet_length = $this->default_previewlen;
		}

		$noparse = false;

		//strip these tags from the preview including anything they might contain
		//we keep track of each seperately, but that might be overkill (we shouldn't
		//see a case where they are nested).
		$strip_tags = array_fill_keys(array('video', 'page', 'attach', 'img2'), 0);

		foreach ($tokens AS $tokenid => $token)
		{
			if (($token['name'] == 'noparse') AND $do_html)
			{
				//can't parse this. We don't know what's inside.
				$new[] = $token;
				$noparse = ! $noparse;
			}

			//if this is a tag we are skipping, flip the "in" state based on if this is an open or close tag
			else if (!empty($token['name']) AND isset($strip_tags[$token['name']]))
			{
				$strip_tags[$token['name']] = !$token['closing'];
				continue;
			}

			//if any of our skip flags are set, skip this tag
			else if(array_sum($strip_tags) > 0)
			{
				continue;
			}

			// only count the length of text entries
			else if ($token['type'] == 'text')
			{

				if (!$noparse)
				{
					//If this has [ATTACH] or [IMG] or VIDEO then we nuke it.
					$pagetext = preg_replace('#\[ATTACH.*?\[/ATTACH\]#si', '', $token['data']);
					$pagetext = preg_replace('#\[IMG.*?\[/IMG\]#si', '', $pagetext);
					$pagetext = preg_replace('#\[video.*?\[/video\]#si', '', $pagetext);

					if ($pagetext == '')
					{
						continue;
					}
					$token['data'] = $pagetext;
				}
				$length = vbstrlen($token['data']);

				// uninterruptable means that we will always show until this tag is closed
				$uninterruptable = (isset($stack[0]) AND isset($this->uninterruptable["$stack[0]"]));

				if ((($counter + $length) < $this->snippet_length ) OR $uninterruptable OR $noparse)
				{
					// this entry doesn't push us over the threshold
					$new[] = $token;
					$counter += $length;
				}
				else
				{
					// a text entry that pushes us over the threshold
					$over_threshold = true;
					$last_char_pos = $this->snippet_length - $counter - 1; // this is the threshold char; -1 means look for a space at it
					if ($last_char_pos < 0)
					{
						$last_char_pos = 0;
					}

					if (preg_match('#\s#s', $token['data'], $match, PREG_OFFSET_CAPTURE, $last_char_pos))
					{
						$token['data'] = substr($token['data'], 0, $match[0][1]); // chop to offset of whitespace
						if (substr($token['data'], -3) == '<br')
						{
							// we cut off a <br /> code, so just take this out
							$token['data'] = substr($token['data'], 0, -3);
						}

						$new[] = $token;
					}
					else
					{
						$new[] = $token;
					}

					break;
				}
			}
			else
			{
				// not a text entry
				if ($token['type'] == 'tag')
				{
					//If we have a prbreak we are done.
					if (($token['name'] == 'prbreak') AND isset($tokens[intval($tokenid) + 1])
						AND ($tokens[intval($tokenid) + 1]['name'] == 'prbreak')
						AND ($tokens[intval($tokenid) + 1]['closing']))
					{
						$over_threshold == true;
						break;
					}
					// build a stack of open tags
					if ($token['closing'] == true)
					{
						// by now, we know the stack is sane, so just remove the first entry
						array_shift($stack);
					}
					else
					{
						array_unshift($stack, $token['name']);
					}
				}

				$new[] = $token;
			}
		}
		// since we may have cut the text, close any tags that we left open
		foreach ($stack AS $tag_name)
		{
			$new[] = array('type' => 'tag', 'name' => $tag_name, 'closing' => true);
		}

		$result = $this->parse_array($new, true, true, $do_html);
		return $result;
	}

	/**
	* Word wraps the text if enabled.
	*
	* @param	string	Text to wrap
	*
	* @return	string	Wrapped text
	*/
	protected function do_word_wrap($text)
	{
		if ($this->registry->options['wordwrap'] != 0)
		{
			$text = vB_String::fetchWordWrappedString($text, $this->registry->options['wordwrap'], '  ');
		}
		return $text;
	}

	/**
	* Parses smilie codes into their appropriate HTML image versions
	*
	* @param	string	Text with smilie codes
	* @param	bool	Whether HTML is allowed
	*
	* @return	string	Text with HTML images in place of smilies
	*/
	function parse_smilies($text, $do_html = false)
	{
		static $regex_cache;
		$org_text = $text;
		$this->local_smilies =& $this->cache_smilies($do_html);

		$cache_key = ($do_html ? 'html' : 'nohtml');

		if (!isset($regex_cache["$cache_key"]))
		{
			$regex_cache["$cache_key"] = array();
			$quoted = array();

			foreach ($this->local_smilies AS $find => $replace)
			{
				$quoted[] = preg_quote($find, '/');
				if (sizeof($quoted) > 500)
				{
					$regex_cache["$cache_key"][] = '/(?<!&amp|&quot|&lt|&gt|&copy|&#[0-9]{1}|&#[0-9]{2}|&#[0-9]{3}|&#[0-9]{4}|&#[0-9]{5})(' . implode('|', $quoted) . ')/s';
					$quoted = array();
				}
			}

			if (sizeof($quoted) > 0)
			{
				$regex_cache["$cache_key"][] = '/(?<!&amp|&quot|&lt|&gt|&copy|&#[0-9]{1}|&#[0-9]{2}|&#[0-9]{3}|&#[0-9]{4}|&#[0-9]{5})(' . implode('|', $quoted) . ')/s';
			}
		}
		$replaced_nr = 0;
		foreach ($regex_cache["$cache_key"] AS $regex)
		{
			$text = preg_replace_callback($regex, array(&$this, 'replace_smilies'), $text, -1, $replaced_nr);
			if (isset($this->imgcount) AND $replaced_nr > 0)
			{
				$this->imgcount += $replaced_nr;
			}
		}
		if (!empty($this->imgcount))
		{
			$allowedImgs = vB::getUserContext($this->userid)->getLimit('sigmaximages');
			if (($allowedImgs > 0) AND ($allowedImgs < $this->imgcount))
			{
				$this->errors['img'] = array('toomanyimages' => array($this->imgcount, $allowedImgs));
				return $org_text;
			}
		}
		return $text;
	}

	/**
	* Callback function for replacing smilies.
	*
	* @ignore
	*/
	function replace_smilies($matches)
	{
		return $this->local_smilies["$matches[0]"];
	}

	/**
	* Caches the smilies in a form ready to be executed.
	*
	* @param	bool	Whether HTML parsing is enabled
	*
	* @return	array	Reference to smilie cache (key: find text; value: replace text)
	*/
	function &cache_smilies($do_html)
	{
		$key = $do_html ? 'html' : 'no_html';
		if (isset($this->smilie_cache["$key"]))
		{
			return $this->smilie_cache["$key"];
		}

		$sc =& $this->smilie_cache["$key"];
		$sc = array();
		if ($this->registry->smiliecache !== null)
		{
			// we can get the smilies from the smiliecache datastore
			foreach ($this->registry->smiliecache AS $smilie)
			{
				if (!$do_html)
				{
					$find = htmlspecialchars_uni(trim($smilie['smilietext']));
				}
				else
				{
					$find = trim($smilie['smilietext']);
				}

				$smiliepath = $smilie['smiliepath'];

				// if you change this HTML tag, make sure you change the smilie remover in code/php/html tag handlers!
				if ($this->is_wysiwyg())
				{
					$replace = "<img src=\"$smiliepath\" border=\"0\" alt=\"\" title=\"" . htmlspecialchars_uni($smilie['title']) . "\" smilieid=\"$smilie[smilieid]\" class=\"inlineimg\" />";
				}
				else
				{
					$replace = "<img src=\"$smiliepath\" border=\"0\" alt=\"\" title=\"" . htmlspecialchars_uni($smilie['title']) . "\" class=\"inlineimg\" />";
				}

				$sc["$find"] = $replace;
			}
		}
		else
		{
			// we have to get the smilies from the database
			$this->registry->smiliecache = array();

			$smilies = vB::getDbAssertor()->getRows('fetchSmilies', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_STORED));
			foreach ($smilies as $smilie)
			{
				if (!$do_html)
				{
					$find = htmlspecialchars_uni(trim($smilie['smilietext']));
				}
				else
				{
					$find = trim($smilie['smilietext']);
				}

				$smiliepath = $smilie['smiliepath'];

				// if you change this HTML tag, make sure you change the smilie remover in code/php/html tag handlers!
				if ($this->is_wysiwyg())
				{
					$replace = "<img src=\"$smiliepath\" border=\"0\" alt=\"\" title=\"" . htmlspecialchars_uni($smilie['title']) . "\" smilieid=\"$smilie[smilieid]\" class=\"inlineimg\" />";
				}
				else
				{
					$replace = "<img src=\"$smiliepath\" border=\"0\" alt=\"\" title=\"" . htmlspecialchars_uni($smilie['title']) . "\" class=\"inlineimg\" />";
				}

				$sc["$find"] = $replace;

				$this->registry->smiliecache["$smilie[smilieid]"] = $smilie;
			}
		}

		return $sc;
	}

	/**
	* Parses out specific white space before or after cetain tags and does nl2br
	*
	* @param	string	Text to process
	* @param	bool	Whether to translate newlines to <br /> tags
	*
	* @return	string	Processed text
	*/
	function parse_whitespace_newlines($text, $do_nl2br = true)
	{
		// this replacement is equivalent to removing leading whitespace via this regex:
		// '#(? >(\r\n|\n|\r)?( )+)(\[(\*\]|/?list|indent))#si'
		// however, it's performance is much better! (because the tags occur less than the whitespace)
		foreach (array('[*]', '[list', '[/list', '[indent') AS $search_string)
		{
			$start_pos = 0;
			while (($tag_pos = stripos($text, $search_string, $start_pos)) !== false)
			{
				$whitespace_pos = $tag_pos - 1;
				while ($whitespace_pos >= 0 AND $text[$whitespace_pos] == ' ')
				{
					--$whitespace_pos;
				}
				if ($whitespace_pos >= 1 AND substr($text, $whitespace_pos - 1, 2) == "\r\n")
				{
					$whitespace_pos -= 2;
				}
				else if ($whitespace_pos >= 0 AND ($text[$whitespace_pos] == "\r" OR $text[$whitespace_pos] == "\n"))
				{
					--$whitespace_pos;
				}

				$length = $tag_pos - $whitespace_pos - 1;
				if ($length > 0)
				{
					$text = substr_replace($text, '', $whitespace_pos + 1, $length);
				}

				$start_pos = $tag_pos + 1 - $length;
			}
		}
		$text = preg_replace('#(/list\]|/indent\])(?> *)#si', '$1', $text);

		if ($do_nl2br)
		{
			$text = nl2br($text);
		}

		return $text;
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
	function parse_bbcode($input_text, $do_smilies, $do_imgcode, $do_html = false, $do_censor = true)
	{
		$array = $this->fix_tags($this->build_parse_array($input_text));
		return $this->parse_array($array, $do_smilies, $do_imgcode, $do_html, $do_censor);
	}

	/**
	* Takes a raw string and builds an array of tokens for parsing.
	*
	* @param	string	Raw text input
	*
	* @return	array	List of tokens
	*/
	function build_parse_array($text)
	{
		$start_pos = 0;
		$strlen = strlen($text);
		$output = array();
		$state = BB_PARSER_START;

		while ($start_pos < $strlen)
		{
			switch ($state)
			{
				case BB_PARSER_START:
					$tag_open_pos = strpos($text, '[', $start_pos);
					if ($tag_open_pos === false)
					{
						$internal_data = array('start' => $start_pos, 'end' => $strlen);
						$state = BB_PARSER_TEXT;
					}
					else if ($tag_open_pos != $start_pos)
					{
						$internal_data = array('start' => $start_pos, 'end' => $tag_open_pos);
						$state = BB_PARSER_TEXT;
					}
					else
					{
						$start_pos = $tag_open_pos + 1;
						if ($start_pos >= $strlen)
						{
							$internal_data = array('start' => $tag_open_pos, 'end' => $strlen);
							$start_pos = $tag_open_pos;
							$state = BB_PARSER_TEXT;
						}
						else
						{
							$state = BB_PARSER_TAG_OPENED;
						}
					}
					break;

				case BB_PARSER_TEXT:
					$end = end($output);
					if ($end AND $end['type'] == 'text')
					{
						// our last element was text too, so let's join them
						$key = key($output);
						$output["$key"]['data'] .= substr($text, $internal_data['start'], $internal_data['end'] - $internal_data['start']);
					}
					else
					{
						$output[] = array('type' => 'text', 'data' => substr($text, $internal_data['start'], $internal_data['end'] - $internal_data['start']));
					}

					$start_pos = $internal_data['end'];
					$state = BB_PARSER_START;
					break;

				case BB_PARSER_TAG_OPENED:
					$tag_close_pos = strpos($text, ']', $start_pos);
					if ($tag_close_pos === false)
					{
						$internal_data = array('start' => $start_pos - 1, 'end' => $start_pos);
						$state = BB_PARSER_TEXT;
						break;
					}

					// check to see if this is a closing tag, since behavior changes
					$closing_tag = ($text[$start_pos] == '/');
					if ($closing_tag)
					{
						// we don't want the / to be saved
						++$start_pos;
					}

					// ok, we have a ], check for an option
					$tag_opt_start_pos = strpos($text, '=', $start_pos);
					if ($closing_tag OR $tag_opt_start_pos === false OR $tag_opt_start_pos > $tag_close_pos)
					{
						// no option, so the ] is the end of the tag
						// check to see if this tag name is valid
						$tag_name_orig = substr($text, $start_pos, $tag_close_pos - $start_pos);
						$tag_name = strtolower($tag_name_orig);

						// if this is a closing tag, we don't know whether we had an option
						$has_option = $closing_tag ? null : false;

						if ($this->is_valid_tag($tag_name, $has_option))
						{
							$output[] = array(
								'type' => 'tag',
								'name' => $tag_name,
								'name_orig' => $tag_name_orig,
								'option' => false,
								'closing' => $closing_tag
							);

							$start_pos = $tag_close_pos + 1;
							$state = BB_PARSER_START;
						}
						else
						{
							// this is an invalid tag, so it's just text
							$internal_data = array('start' => $start_pos - 1 - ($closing_tag ? 1 : 0), 'end' => $start_pos);
							$state = BB_PARSER_TEXT;
						}
					}
					else
					{
						// check to see if this tag name is valid
						$tag_name_orig = substr($text, $start_pos, $tag_opt_start_pos - $start_pos);
						$tag_name = strtolower($tag_name_orig);

						if (!$this->is_valid_tag($tag_name, true))
						{
							// this isn't a valid tag name, so just consider it text
							$internal_data = array('start' => $start_pos - 1, 'end' => $start_pos);
							$state = BB_PARSER_TEXT;
							break;
						}

						// we have a = before a ], so we have an option
						$delimiter = $text[$tag_opt_start_pos + 1];
						if ($delimiter == '&' AND substr($text, $tag_opt_start_pos + 2, 5) == 'quot;')
						{
							$delimiter = '&quot;';
							$delim_len = 7;
						}
						else if ($delimiter != '"' AND $delimiter != "'")
						{
							$delimiter = '';
							$delim_len = 1;
						}
						else
						{
							$delim_len = 2;
						}

						if ($delimiter != '')
						{
							$close_delim = strpos($text, "$delimiter]", $tag_opt_start_pos + $delim_len);
							if ($close_delim === false)
							{
								// assume no delimiter, and the delimiter was actually a character
								$delimiter = '';
								$delim_len = 1;
							}
							else
							{
								$tag_close_pos = $close_delim;
							}
						}

						$tag_option = substr($text, $tag_opt_start_pos + $delim_len, $tag_close_pos - ($tag_opt_start_pos + $delim_len));
						if ($this->is_valid_option($tag_name, $tag_option))
						{
							$output[] = array(
								'type' => 'tag',
								'name' => $tag_name,
								'name_orig' => $tag_name_orig,
								'option' => $tag_option,
								'delimiter' => $delimiter,
								'closing' => false
							);

							$start_pos = $tag_close_pos + $delim_len;
							$state = BB_PARSER_START;
						}
						else
						{
							// this is an invalid option, so consider it just text
							$internal_data = array('start' => $start_pos - 1, 'end' => $start_pos);
							$state = BB_PARSER_TEXT;
						}
					}
					break;
			}
		}
		return $output;
	}

	/**
	* Traverses parse array and fixes nesting and mismatched tags.
	*
	* @param	array	Parsed data array, such as one from build_parse_array
	*
	* @return	array	Parse array with specific data fixed
	*/
	function fix_tags($preparsed)
	{
		$output = array();
		$stack = array();
		$noparse = null;

		foreach ($preparsed AS $node_key => $node)
		{
			if ($node['type'] == 'text')
			{
				$output[] = $node;
			}
			else if ($node['closing'] == false)
			{
				// opening a tag
				if ($noparse !== null)
				{
					$output[] = array('type' => 'text', 'data' => '[' . $node['name_orig'] . ($node['option'] !== false ? "=$node[delimiter]$node[option]$node[delimiter]" : '') . ']');
					continue;
				}

				$output[] = $node;
				end($output);

				$node['added_list'] = array();
				$node['my_key'] = key($output);
				array_unshift($stack, $node);

				if ($node['name'] == 'noparse')
				{
					$noparse = $node_key;
				}
			}
			else
			{
				// closing tag
				if ($noparse !== null AND $node['name'] != 'noparse')
				{
					// closing a tag but we're in a noparse - treat as text
					$output[] = array('type' => 'text', 'data' => '[/' . $node['name_orig'] . ']');
				}
				else if (($key = $this->find_first_tag($node['name'], $stack)) !== false)
				{
					if ($node['name'] == 'noparse')
					{
						// we're closing a noparse tag that we opened
						if ($key != 0)
						{
							for ($i = 0; $i < $key; $i++)
							{
								$output[] = $stack["$i"];
								unset($stack["$i"]);
							}
						}

						$output[] = $node;

						unset($stack["$key"]);
						$stack = array_values($stack); // this is a tricky way to renumber the stack's keys

						$noparse = null;

						continue;
					}

					if ($key != 0)
					{
						end($output);
						$max_key = key($output);

						// we're trying to close a tag which wasn't the last one to be opened
						// this is bad nesting, so fix it by closing tags early
						for ($i = 0; $i < $key; $i++)
						{
							$output[] = array('type' => 'tag', 'name' => $stack["$i"]['name'], 'name_orig' => $stack["$i"]['name_orig'], 'closing' => true);
							$max_key++;
							$stack["$i"]['added_list'][] = $max_key;
						}
					}

					$output[] = $node;

					if ($key != 0)
					{
						$max_key++; // for the node we just added

						// ...and now reopen those tags in the same order
						for ($i = $key - 1; $i >= 0; $i--)
						{
							$output[] = $stack["$i"];
							$max_key++;
							$stack["$i"]['added_list'][] = $max_key;
						}
					}

					unset($stack["$key"]);
					$stack = array_values($stack); // this is a tricky way to renumber the stack's keys
				}
				else
				{
					// we tried to close a tag which wasn't open, to just make this text
					$output[] = array('type' => 'text', 'data' => '[/' . $node['name_orig'] . ']');
				}
			}
		}

		// These tags were never closed, so we want to display the literal BB code.
		// Rremove any nodes we might've added before, thinking this was valid,
		// and make this node become text.
		foreach ($stack AS $open)
		{
			foreach ($open['added_list'] AS $node_key)
			{
				unset($output["$node_key"]);
			}
			$output["$open[my_key]"] = array(
				'type' => 'text',
				'data' => '[' . $open['name_orig'] . (!empty($open['option']) ? '=' . $open['delimiter'] . $open['option'] . $open['delimiter'] : '') . ']'
			);
		}

		/*
		// automatically close any tags that remain open
		foreach (array_reverse($stack) AS $open)
		{
			$output[] = array('type' => 'tag', 'name' => $open['name'], 'name_orig' => $open['name_orig'], 'closing' => true);
		}
		*/

		$output = $this->fixQuoteTags($output);

		return $output;
	}

	/**
	 * @see vB5_Template_BbCode::fixQuoteTags()
	 */
	protected function fixQuoteTags($elements)
	{
		// NOTE: See extensive comments on this function in vB5_Template_BbCode
		// The only differences here are the use of vB_String instead of vB_String
		// and how vB options are accessed.

		$prevKey = null;

		foreach ($elements AS $key => $el)
		{
			if ($prevKey !== null)
			{
				$prevEl = $elements[$prevKey];

				if ($prevEl['type'] == 'tag' AND $prevEl['name'] == 'quote' AND $el['type'] == 'text')
				{
					if (!preg_match('/^.*;n?\d+$/U', $prevEl['option'], $match))
					{
						$options = vB::getDatastore()->getValue('options');
						$limit = (int) $options['maxuserlength'];
						$limit -= vB_String::vbStrlen($prevEl['option']);
						$limit += 20;

						$text = vB_String::vbChop($el['data'], $limit);

						if (preg_match('/^(.*(?<!&#[0-9]{3}|&#[0-9]{4}|&#[0-9]{5});\s*n\d+\s*)\]/U', $text, $match))
						{
							$len = strlen($match[1]);
							$elements[$prevKey]['option'] .= ']' . substr($el['data'], 0, $len);

							$len = strlen($match[0]);
							$elements[$key]['data'] = substr($el['data'], $len);
						}
					}
				}
			}

			$prevKey = $key;
		}

		return $elements;
	}

	/**
	* Takes a parse array and parses it into the final HTML.
	* Tags are assumed to be matched.
	*
	* @param array $preparsed Parse array
	* @param bool $do_smilies Whether to parse smilies
	* @param bool $do_imgcode Whether to parse img (for the video tags)
	* @param bool $do_html Whether to allow HTML (for smilies)
	* @param bool $do_censor Whether to censor the text
	*
	* @return	string	Final HTML
	*/
	function parse_array($preparsed, $do_smilies, $do_imgcode, $do_html = false, $do_censor = true)
	{
		$this->parse_output = '';
		$output =& $this->parse_output;
		$this->stack = array();
		$stack_size = 0;

		// holds options to disable certain aspects of parsing
		$parse_options = array(
			'no_parse'          => 0,
			'no_wordwrap'       => 0,
			'no_smilies'        => 0,
			'strip_space_after' => 0
		);

		$this->node_max = count($preparsed);
		$this->node_num = 0;

		foreach ($preparsed AS $node)
		{
			$this->node_num++;
			$pending_text = '';
			if ($node['type'] == 'text')
			{
				$pending_text =& $node['data'];

				// remove leading space after a tag
				if ($parse_options['strip_space_after'])
				{
					$pending_text = $this->strip_front_back_whitespace($pending_text, $parse_options['strip_space_after'], true, false);
					$parse_options['strip_space_after'] = 0;
				}

				// parse smilies
				if ($do_smilies AND !$parse_options['no_smilies'])
				{
					$pending_text = $this->parse_smilies($pending_text, $do_html);
				}

				// do word wrap
				if (!$parse_options['no_wordwrap'])
				{
					$pending_text = $this->do_word_wrap($pending_text);
				}

				if ($parse_options['no_parse'])
				{
					$pending_text = str_replace(array('[', ']'), array('&#91;', '&#93;'), $pending_text);
				}

				// run the censor
				if($do_censor)
				{
					$pending_text = vB_String::fetchCensoredText($pending_text);
				}

			}
			else if ($node['closing'] == false)
			{
				$parse_options['strip_space_after'] = 0;

				if ($parse_options['no_parse'] == 0)
				{
					// opening a tag
					// initialize data holder and push it onto the stack
					$node['data'] = '';
					array_unshift($this->stack, $node);
					++$stack_size;

					$has_option = $node['option'] !== false ? 'option' : 'no_option';
					$tag_info =& $this->tag_list["$has_option"]["$node[name]"];

					// setup tag options
					if (!empty($tag_info['stop_parse']))
					{
						$parse_options['no_parse'] = 1;
					}
					if (!empty($tag_info['disable_smilies']))
					{
						$parse_options['no_smilies']++;
					}
					if (!empty($tag_info['disable_wordwrap']))
					{
						$parse_options['no_wordwrap']++;
					}
				}
				else
				{
					$pending_text = '&#91;' . $node['name_orig'] . ($node['option'] !== false ? "=$node[delimiter]$node[option]$node[delimiter]" : '') . '&#93;';
				}
			}
			else
			{
				$parse_options['strip_space_after'] = 0;

				// closing a tag
				// look for this tag on the stack
				if (($key = $this->find_first_tag($node['name'], $this->stack)) !== false)
				{
					// found it
					$open =& $this->stack["$key"];
					$this->current_tag =& $open;

					$has_option = $open['option'] !== false ? 'option' : 'no_option';

					// check to see if this version of the tag is valid
					if (isset($this->tag_list["$has_option"]["$open[name]"]))
					{
						$tag_info =& $this->tag_list["$has_option"]["$open[name]"];

						// make sure we have data between the tags
						if ((isset($tag_info['strip_empty']) AND $tag_info['strip_empty'] == false) OR trim($open['data']) != '')
						{
							// make sure our data matches our pattern if there is one
							if (empty($tag_info['data_regex']) OR preg_match($tag_info['data_regex'], $open['data']))
							{
								if (!isset($tag_info['callback']) AND isset($tag_info['handlers']))
								{
									// Handlers replace callbacks as to allow for bbcode extensions, but
									// it's not clear atm how things using this legacy bbcode class should
									// utilize them. For now, just fallback to the existing callback methods.
									$defaultHandler = $tag_info['handlers'][0] ?? '';
									switch ($defaultHandler)
									{
										case 'vB_BbCode_Url':
											$tag_info['callback'] = 'handle_bbcode_url';
											break;
										default:
											// Newly refactored bbcode type, unknown yet how this should be supported.
											// We may want to just return the unparsed text instead, but
											// it may be a case-by-case thing.
											break;
									}
								}

								// now do the actual replacement
								if (isset($tag_info['html']))
								{
									// this is a simple HTML replacement
									// removing bad fix per Freddie.
									//$search = array("'", '=');
									//$replace = array('&#039;', '&#0061;');
									//$open['data'] = str_replace($search, $replace, $open['data']);
									//$open['option'] = str_replace($search, $replace, $open['option']);
									$pending_text = sprintf($tag_info['html'], $open['data'], $open['option']);
								}
								else if (isset($tag_info['callback']))
								{
									// call a callback function
									if ($tag_info['callback'] == 'handle_bbcode_video' AND !$do_imgcode)
									{
										$tag_info['callback'] = 'handle_bbcode_url';
										$open['option'] = '';
									}

									$pending_text = $this->{$tag_info['callback']}($open['data'], $open['option']);
								}
							}
							else
							{
								// oh, we didn't match our regex, just print the tag out raw
								$pending_text =
									'&#91;' . $open['name_orig'] .
									($open['option'] !== false ? "=$open[delimiter]$open[option]$open[delimiter]" : '') .
									'&#93;' . $open['data'] . '&#91;/' . $node['name_orig'] . '&#93;'
								;
							}
						}

						// undo effects of various tag options
						if (!empty($tag_info['strip_space_after']))
						{
							$parse_options['strip_space_after'] = $tag_info['strip_space_after'];
						}
						if (!empty($tag_info['stop_parse']))
						{
							$parse_options['no_parse'] = 0;
						}
						if (!empty($tag_info['disable_smilies']))
						{
							$parse_options['no_smilies']--;
						}
						if (!empty($tag_info['disable_wordwrap']))
						{
							$parse_options['no_wordwrap']--;
						}
					}
					else
					{
						// this tag appears to be invalid, so just print it out as text
						$pending_text = '&#91;' . $open['name_orig'] . ($open['option'] !== false ? "=$open[delimiter]$open[option]$open[delimiter]" : '') . '&#93;';
					}

					// pop the tag off the stack

					unset($this->stack["$key"]);
					--$stack_size;
					$this->stack = array_values($this->stack); // this is a tricky way to renumber the stack's keys
				}
				else
				{
					// wasn't there - we tried to close a tag which wasn't open, so just output the text
					$pending_text = '&#91;/' . $node['name_orig'] . '&#93;';
				}
			}

			if ($stack_size == 0)
			{
				$output .= $pending_text;
			}
			else
			{
				$this->stack[0]['data'] .= $pending_text;
			}
		}

		/*
		// check for tags that are stil open at the end and display them
		foreach (array_reverse($this->stack) AS $open)
		{
			$output .= '[' . $open['name_orig'];
			if ($open['option'])
			{
				$output .= '=' . $open['delimiter'] . $open['option'] . $open['delimiter'];
			}
			$output .= "]$open[data]";
			//$output .= $open['data'];
		}
		*/

		return $output;
	}

	/**
	* Checks if the specified tag exists in the list of parsable tags
	*
	* @param	string		Name of the tag
	* @param	bool/null	true = tag with option, false = tag without option, null = either
	*
	* @return	bool		Whether the tag is valid
	*/
	function is_valid_tag($tag_name, $has_option = null)
	{
		if ($tag_name === '')
		{
			// no tag name, so this definitely isn't a valid tag
			return false;
		}

		if ($tag_name[0] == '/')
		{
			$tag_name = substr($tag_name, 1);
		}

		if ($has_option === null)
		{
			return (isset($this->tag_list['no_option']["$tag_name"]) OR isset($this->tag_list['option']["$tag_name"]));
		}
		else
		{
			$option = $has_option ? 'option' : 'no_option';
			return isset($this->tag_list["$option"]["$tag_name"]);
		}
	}

	/**
	* Checks if the specified tag option is valid (matches the regex if there is one)
	*
	* @param	string		Name of the tag
	* @param	string		Value of the option
	*
	* @return	bool		Whether the option is valid
	*/
	function is_valid_option($tag_name, $tag_option)
	{
		if (empty($this->tag_list['option']["$tag_name"]['option_regex']))
		{
			return true;
		}
		return preg_match($this->tag_list['option']["$tag_name"]['option_regex'], $tag_option);
	}

	/**
	* Find the first instance of a tag in an array
	*
	* @param	string		Name of tag
	* @param	array		Array to search
	*
	* @return	int/false	Array key of first instance; false if it does not exist
	*/
	function find_first_tag($tag_name, &$stack)
	{
		foreach ($stack AS $key => $node)
		{
			if ($node['name'] == $tag_name)
			{
				return $key;
			}
		}
		return false;
	}

	/**
	* Find the last instance of a tag in an array.
	*
	* @param	string		Name of tag
	* @param	array		Array to search
	*
	* @return	int/false	Array key of first instance; false if it does not exist
	*/
	function find_last_tag($tag_name, &$stack)
	{
		foreach (array_reverse($stack, true) AS $key => $node)
		{
			if ($node['name'] == $tag_name)
			{
				return $key;
			}
		}
		return false;
	}

	/**
	 * Handles an [indent] tag.
	 *
	 * @param	string	The text to indent
	 * @param	string	Indentation level
	 *
	 * @return	string	HTML representation of the tag.
	 */
	protected function handle_bbcode_indent($text, $type = '')
	{
		$type = (int) $type;

		if ($type < 1)
		{
			$type = 1;
		}

		$indent = $type * vB_Api_Bbcode::EDITOR_INDENT;
		$user = vB::getCurrentSession()->fetch_userinfo();
		$dir = ($user['lang_options']['direction'] ? 'left' : 'right');

		return '<div style="margin-' . $dir . ':' . $indent . 'px">' . $text . '</div>';
	}

	/**
	* Allows extension of the class functionality at run time by calling an
	* external function. To use this, your tag must have a callback of
	* 'handle_external' and define an additional 'external_callback' entry.
	* Your function will receive 3 parameters:
	*	A reference to this BB code parser
	*	The value for the tag
	*	The option for the tag
	* Ensure that you accept at least the first parameter by reference!
	*
	* @param	string	Value for the tag
	* @param	string	Option for the tag (if it has one)
	*
	* @return	string	HTML representation of the tag
	*/
	function handle_external($value, $option = null)
	{
		$open = $this->current_tag;

		$has_option = $open['option'] !== false ? 'option' : 'no_option';
		$tag_info =& $this->tag_list["$has_option"]["$open[name]"];

		return $tag_info['external_callback']($this, $value, $option);
	}

	/**
	* Handles an [email] tag. Creates a link to email an address.
	*
	* @param	string	If tag has option, the displayable email name. Else, the email address.
	* @param	string	If tag has option, the email address.
	*
	* @return	string	HTML representation of the tag.
	*/
	function handle_bbcode_email($text, $link = '')
	{
		$rightlink = trim($link);
		if (empty($rightlink))
		{
			// no option -- use param
			$rightlink = trim($text);
		}
		$rightlink = str_replace(array('`', '"', "'", '['), array('&#96;', '&quot;', '&#39;', '&#91;'), $this->strip_smilies($rightlink));

		if (!trim($link) OR $text == $rightlink)
		{
			$tmp = unhtmlspecialchars($text);
			if (vbstrlen($tmp) > 55 AND $this->is_wysiwyg() == false)
			{
				$text = htmlspecialchars_uni(vbchop($tmp, 36) . '...' . substr($tmp, -14));
			}
		}

		// remove double spaces -- fixes issues with wordwrap
		$rightlink = str_replace('  ', '', $rightlink);

		// email hyperlink (mailto:)
		if (vB_String::isValidEmail($rightlink))
		{
			return "<a href=\"mailto:$rightlink\">$text</a>";
		}
		else
		{
			return $text;
		}
	}

	/**
	* Handles a [quote] tag. Displays a string in an area indicating it was quoted from someone/somewhere else.
	*
	* @param	string	The body of the quote.
	* @param	string	If tag has option, the original user to post.
	*
	* @return	string	HTML representation of the tag.
	*/
	function handle_bbcode_quote($message, $username = '')
	{
		global $vbulletin, $vbphrase, $show;

		// remove smilies from username
		$username = $this->strip_smilies($username);

		// NOTE: This regex differs from the other bbcode implementations in that
		// it doesn't account for the nXXX nodeid format. It uses (\d+) instead
		// of (n?\d+) I don't want to change it at this time because I haven't
		// researched exactly where and how this class is used.
		if (preg_match('/^(.+)(?<!&#[0-9]{3}|&#[0-9]{4}|&#[0-9]{5});\s*(\d+)\s*$/U', $username, $match))
		{
			$username = $match[1];
			$postid = $match[2];
		}
		else
		{
			$postid = 0;
		}

		$username = $this->do_word_wrap($username);

		$show['username'] = iif($username != '', true, false);
		$message = $this->strip_front_back_whitespace($message, 1);

		if ($this->options['cachable'] == false)
		{
			$show['iewidthfix'] = (is_browser('ie') AND !(is_browser('ie', 6)));
		}
		else
		{
			// this post may be cached, so we can't allow this "fix" to be included in that cache
			$show['iewidthfix'] = false;
		}

		$templater = vB_Template::create($this->printable ? $this->quote_printable_template : $this->quote_template);
			$templater->register('message', $message);
			$templater->register('postid', $postid);
			$templater->register('username', $username);
			$templater->register('quote_vars', $this->quote_vars);
		return $templater->render();
	}

	/**
	* Handles a [post] tag. Creates a link to another post.
	*
	* @param	string	If tag has option, the displayable name. Else, the postid.
	* @param	string	If tag has option, the postid.
	*
	* @return	string	HTML representation of the tag.
	*/
	function handle_bbcode_post($text, $postId)
	{
		$postId = intval($postId);

		if (empty($postId))
		{
			// no option -- use param
			$postId = intval($text);
			unset($text);
		}

		$url = vB_Api::instanceInternal('route')->fetchLegacyPostUrl($postId);

		if (!isset($text))
		{
			$text = $url;
		}
		$url = $this->escapeAttribute($url);

		// standard URL hyperlink
		return "<a href=\"$url\" target=\"_blank\">$text</a>";
	}

	/**
	* Handles a [thread] tag. Creates a link to another thread.
	*
	* @param	string	If tag has option, the displayable name. Else, the threadid.
	* @param	string	If tag has option, the threadid.
	*
	* @return	string	HTML representation of the tag.
	*/
	function handle_bbcode_thread($text, $threadId)
	{
		$threadId = intval($threadId);

		if (empty($threadId))
		{
			// no option -- use param
			$threadId = intval($text);
			unset($text);
		}

		$url = vB_Api::instanceInternal('route')->fetchLegacyThreadUrl($threadId);

		if (!isset($text))
		{
			$text = $url;
		}
		$url = $this->escapeAttribute($url);

		// standard URL hyperlink
		return "<a href=\"$url\" target=\"_blank\">$text</a>";
	}

	/**
	* Handles a [php] tag. Syntax highlights a string of PHP.
	*
	* @param	string	The code to highlight.
	*
	* @return	string	HTML representation of the tag.
	*/
	function handle_bbcode_php($code)
	{
		global $vbulletin, $vbphrase, $show;
		static $codefind1, $codereplace1, $codefind2, $codereplace2;

		$code = $this->strip_front_back_whitespace($code, 1);

		if (!is_array($codefind1))
		{
			$codefind1 = array(
				'<br>',		// <br> to nothing
				'<br />'	// <br /> to nothing
			);
			$codereplace1 = array(
				'',
				''
			);

			$codefind2 = array(
				'&gt;',		// &gt; to >
				'&lt;',		// &lt; to <
				'&quot;',	// &quot; to ",
				'&amp;',	// &amp; to &
				'&#91;',    // &#91; to [
				'&#93;',    // &#93; to ]
			);
			$codereplace2 = array(
				'>',
				'<',
				'"',
				'&',
				'[',
				']',
			);
		}

		// remove htmlspecialchars'd bits and excess spacing
		$code = rtrim(str_replace($codefind1, $codereplace1, $code));
		$blockheight = $this->fetch_block_height($code); // fetch height of block element
		$code = str_replace($codefind2, $codereplace2, $code); // finish replacements

		// do we have an opening <? tag?
		if (!preg_match('#<\?#si', $code))
		{
			// if not, replace leading newlines and stuff in a <?php tag and a closing tag at the end
			$code = "<?php BEGIN__VBULLETIN__CODE__SNIPPET $code \r\nEND__VBULLETIN__CODE__SNIPPET ?>";
			$addedtags = true;
		}
		else
		{
			$addedtags = false;
		}

		// highlight the string
		$code = highlight_string($code, true);

		// if we added tags above, now get rid of them from the resulting string
		if ($addedtags)
		{
			$search = array(
				'#&lt;\?php( |&nbsp;)BEGIN__VBULLETIN__CODE__SNIPPET( |&nbsp;)#siU',
				'#(<(span|font)[^>]*>)&lt;\?(</\\2>(<\\2[^>]*>))php( |&nbsp;)BEGIN__VBULLETIN__CODE__SNIPPET( |&nbsp;)#siU',
				'#END__VBULLETIN__CODE__SNIPPET( |&nbsp;)\?(>|&gt;)#siU'
			);
			$replace = array(
				'',
				'\\4',
				''
			);

			$code = preg_replace($search, $replace, $code);
		}

		$code = preg_replace('/&amp;#([0-9]+);/', '&#$1;', $code); // allow unicode entities back through
		$code = str_replace(array('[', ']'), array('&#91;', '&#93;'), $code);

		$templater = vB_Template::create($this->printable ? 'bbcode_php_printable' : 'bbcode_php');
			$templater->register('blockheight', $blockheight);
			$templater->register('code', $code);
		return $templater->render();
	}

	/**
	* Emulates the behavior of a pre tag in HTML. Tabs and multiple spaces
	* are replaced with spaces mixed with non-breaking spaces. Usually combined
	* with code tags. Note: this still allows the browser to wrap lines.
	*
	* @param	string	Text to convert. Should not have <br> tags!
	*
	* @param	string	Converted text
	*/
	function emulate_pre_tag($text)
	{
		$text = str_replace(
			array("\t",       '  '),
			array('        ', '&nbsp; '),
			nl2br($text)
		);

		return preg_replace('#([\r\n]) (\S)#', '$1&nbsp;$2', $text);
	}

	/**
	* Handles a [video] tag. Displays a movie.
	*
	* @param	string	The code to display
	*
	* @return	string	HTML representation of the tag.
	*/
	function handle_bbcode_video($url, $option)
	{
		global $vbulletin, $vbphrase, $show;

		$params = array();
		$options = explode(';', $option);
		$provider = strtolower($options[0]);
		$code = $options[1];

		if (!$code OR !$provider)
		{
			return '[video=' . $option . ']' . $url . '[/video]';
		}

		// atm only code is used in attributes. url is not used and provider is only used in checks.
		// but putting these in just in case.
		// not sure where $width & $height that the template optionally expects come from
		$url = $this->escapeAttribute($url);
		$provider = $this->escapeAttribute($provider);
		$code = $this->escapeAttribute($code);

		$templater = vB_Template::create('bbcode_video');
			$templater->register('url', $url);
			$templater->register('provider', $provider);
			$templater->register('code', $code);

		return $templater->render();
	}

	/**
	* Handles a [code] tag. Displays a preformatted string.
	*
	* @param	string	The code to display
	*
	* @return	string	HTML representation of the tag.
	*/
	function handle_bbcode_code($code)
	{
		global $vbulletin, $vbphrase, $show;

		// remove unnecessary line breaks and escaped quotes
		$code = str_replace(array('<br>', '<br />'), array('', ''), $code);

		$code = $this->strip_front_back_whitespace($code, 1);

		if ($this->printable)
		{
			$code = $this->emulate_pre_tag($code);
			$template = 'bbcode_code_printable';
		}
		else
		{
			$blockheight = $this->fetch_block_height($code);
			$template = 'bbcode_code';
		}

		$templater = vB_Template::create($template);
			$templater->register('blockheight', $blockheight);
			$templater->register('code', $code);
		return $templater->render();
	}

	/**
	 * Handled [h] tags - converts to <b>
	*
	* @param	string	Body of the [H]
	* @param	string	H Size (1 - 6)
	*
	* @return	string	Parsed text
	*/
	function handle_bbcode_h($text, $option)
	{
		if (preg_match('#^[1-6]$#', $option))
		{
			return "<b>{$text}</b><br /><br />";
		}
		else
		{
			return $text;
		}

		return $text;
	}


	/**
	* Handles an [html] tag. Syntax highlights a string of HTML.
	*
	* @param	string	The HTML to highlight.
	*
	* @return	string	HTML representation of the tag.
	*/
	function handle_bbcode_html($code)
	{
		global $vbulletin, $vbphrase, $show, $html_allowed;
		static $regexfind, $regexreplace;

		$code = $this->strip_front_back_whitespace($code, 1);


		if (!is_array($regexfind))
		{
			$regexfind = array(
				'#<br( /)?>#siU',				// strip <br /> codes
				'#(&amp;\w+;)#siU',				// do html entities
				'#&lt;!--(.*)--&gt;#siU',		// italicise comments
			);
			$regexreplace = array(
				'',								// strip <br /> codes
				'<b><i>\1</i></b>',				// do html entities
				'<i>&lt;!--\1--&gt;</i>',		// italicise comments
			);
		}

		// parse the code
		$code = preg_replace($regexfind, $regexreplace, $code);

		$code = preg_replace_callback('#&lt;((?>[^&"\']+?|&quot;.*&quot;|&(?!gt;)|"[^"]*"|\'[^\']*\')+)&gt;#siU', // push code through the tag handler
			array($this, 'handleBBCodeTagPregMatch1'), $code);

		if ($html_allowed)
		{
			$code = preg_replace_callback('#<((?>[^>"\']+?|"[^"]*"|\'[^\']*\')+)>#', // push code through the tag handler
				array($this, 'handleBBCodeTagPregMatch2'), $code);
		}

		if ($this->printable)
		{
			$code = $this->emulate_pre_tag($code);
			$template = 'bbcode_html_printable';
		}
		else
		{
			$blockheight = $this->fetch_block_height($code);
			$template = 'bbcode_html';
		}

		$templater = vB_Template::create($template);
			$templater->register('blockheight', $blockheight);
			$templater->register('code', $code);
		return $templater->render();
	}

	/**
	 * Callback for preg_replace_callback used in handle_bbcode_html
	 */
	protected function handleBBCodeTagPregMatch1($matches)
	{
		return $this->handle_bbcode_html_tag($matches[1]);
	}

	/**
	 * Callback for preg_replace_callback used in handle_bbcode_html
	 */
	protected function handleBBCodeTagPregMatch2($matches)
	{
		return $this->handle_bbcode_html_tag(vB_String::htmlSpecialCharsUni($matches[1]));
	}

	/**
	* Handles an individual HTML tag in a [html] tag.
	*
	* @param	string	The body of the tag.
	*
	* @return	string	Syntax highlighted, displayable HTML tag.
	*/
	function handle_bbcode_html_tag($tag)
	{
		static $bbcode_html_colors;

		if (empty($bbcode_html_colors))
		{
			$bbcode_html_colors = $this->fetch_bbcode_html_colors();
		}

		// change any embedded URLs so they don't cause any problems
		$tag = preg_replace('#\[(email|url)=&quot;(.*)&quot;\]#siU', '[$1="$2"]', $tag);

		// find if the tag has attributes
		$spacepos = strpos($tag, ' ');
		if ($spacepos != false)
		{
			// tag has attributes - get the tag name and parse the attributes
			$tagname = substr($tag, 0, $spacepos);
			$tag = preg_replace('# (\w+)=&quot;(.*)&quot;#siU', ' \1=<span style="color:' . $bbcode_html_colors['attribs'] . '">&quot;\2&quot;</span>', $tag);
		}
		else
		{
			// no attributes found
			$tagname = $tag;
		}
		// remove leading slash if there is one
		if ($tag[0] == '/')
		{
			$tagname = substr($tagname, 1);
		}
		// convert tag name to lower case
		$tagname = strtolower($tagname);

		// get highlight colour based on tag type
		switch($tagname)
		{
			// table tags
			case 'table':
			case 'tr':
			case 'td':
			case 'th':
			case 'tbody':
			case 'thead':
				$tagcolor = $bbcode_html_colors['table'];
				break;
			// form tags
			//NOTE: Supposed to be a semi colon here ?
			case 'form';
			case 'input':
			case 'select':
			case 'option':
			case 'textarea':
			case 'label':
			case 'fieldset':
			case 'legend':
				$tagcolor = $bbcode_html_colors['form'];
				break;
			// script tags
			case 'script':
				$tagcolor = $bbcode_html_colors['script'];
				break;
			// style tags
			case 'style':
				$tagcolor = $bbcode_html_colors['style'];
				break;
			// anchor tags
			case 'a':
				$tagcolor = $bbcode_html_colors['a'];
				break;
			// img tags
			case 'img':
				$tagcolor = $bbcode_html_colors['img'];
				break;
			// if (vB Conditional) tags
			case 'if':
			case 'else':
			case 'elseif':
				$tagcolor = $bbcode_html_colors['if'];
				break;
			// all other tags
			default:
				$tagcolor = $bbcode_html_colors['default'];
				break;
		}

		$tag = '<span style="color:' . $tagcolor . '">&lt;' . str_replace('\\"', '"', $tag) . '&gt;</span>';
		return $tag;
	}

	/**
	* Handles a [list] tag. Makes a bulleted or ordered list.
	*
	* @param	string	The body of the list.
	* @param	string	If tag has option, the type of list (ordered, etc).
	*
	* @return	string	HTML representation of the tag.
	*/
	function handle_bbcode_list($text, $type = '')
	{
		if ($type)
		{
			switch ($type)
			{
				case 'A':
					$listtype = 'upper-alpha';
					break;
				case 'a':
					$listtype = 'lower-alpha';
					break;
				case 'I':
					$listtype = 'upper-roman';
					break;
				case 'i':
					$listtype = 'lower-roman';
					break;
				case '1': //break missing intentionally
				default:
					$listtype = 'decimal';
					break;
			}
		}
		else
		{
			$listtype = '';
		}

		// emulates ltrim after nl2br
		$text = preg_replace('#^(\s|<br>|<br />)+#si', '', $text);

		$bullets = preg_split('#\s*\[\*\]#s', $text, -1, PREG_SPLIT_NO_EMPTY);
		if (empty($bullets))
		{
			return "\n\n";
		}

		$output = '';
		foreach ($bullets AS $bullet)
		{
			$output .= $this->handle_bbcode_list_element($bullet);
		}

		if ($listtype)
		{
			return '<ol class="' . $listtype . '">' . $output . '</ol>';
		}
		else
		{
			return "<ul>$output</ul>";
		}
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
		return "<li>$text</li>\n";
	}


	/**
	* Handles a [url] tag. Creates a link to another web page.
	*
	* @param	string	If tag has option, the displayable name. Else, the URL.
	* @param	string	If tag has option, the URL.
	* @param	bool	If this is for an image, just return the link
	*
	* @return	string	HTML representation of the tag.
	*/
	function handle_bbcode_url($text, $link, $image = false)
	{
		$rightlink = trim($link);

		if (empty($rightlink))
		{
			// no option -- use param
			$rightlink = trim($text);
		}
		$rightlink = str_replace(array('`', '"', "'", '['), array('&#96;', '&quot;', '&#39;', '&#91;'), $this->strip_smilies($rightlink));

		// remove double spaces -- fixes issues with wordwrap
		$rightlink = str_replace('  ', '', $rightlink);

		if (!preg_match('#^[a-z0-9]+(?<!about|javascript|vbscript|data):#si', $rightlink))
		{
			$rightlink = "http://$rightlink";
		}

		if (!trim($link) OR str_replace('  ', '', $text) == $rightlink)
		{
			$tmp = unhtmlspecialchars($rightlink);
			if (vbstrlen($tmp) > 55 AND $this->is_wysiwyg() == false)
			{
				$text = htmlspecialchars_uni(vbchop($tmp, 36) . '...' . substr($tmp, -14));
			}
			else
			{
				// under the 55 chars length, don't wordwrap this
				$text = str_replace('  ', '', $text);
			}
		}

		static $current_url, $current_host, $allowed, $friendlyurls = array();

		if (!isset($current_url))
		{
			$current_url = @vB_String::parseUrl($this->registry->options['bburl']);
		}
		$is_external = $this->registry->options['url_nofollow'];

		if ($this->registry->options['url_nofollow'])
		{
			if (!isset($current_host))
			{
				$current_host = preg_replace('#:(\d)+$#', '', VB_HTTP_HOST);

				$allowed = preg_split('#\s+#', $this->registry->options['url_nofollow_whitelist'], -1, PREG_SPLIT_NO_EMPTY);
				$allowed[] = preg_replace('#^www\.#i', '', $current_host);
				$allowed[] = preg_replace('#^www\.#i', '', $current_url['host']);
			}

			$target_url = preg_replace('#^([a-z0-9]+:(//)?)#', '', $rightlink);

			foreach ($allowed AS $host)
			{
				if (stripos($target_url, $host) !== false)
				{
					$is_external = false;
				}
			}
		}

		if ($image)
		{
			return array('link' => $rightlink, 'nofollow' => $is_external);
		}

		// API need to convert link to vb:action/param1=val1/param2=val2...
		if (defined('VB_API') AND VB_API === true)
		{
			$current_link = @vB_String::parseUrl($rightlink);
			if ($current_link !== false)
			{
				$current_link['host'] = strtolower($current_link['host']);
				$current_url['host'] = strtolower($current_url['host']);
				if (
					(
						$current_link['host'] == $current_url['host']
						OR 'www.' . $current_link['host'] == $current_url['host']
						OR $current_link['host'] == 'www.' . $current_url['host']
					)
					AND
					(!$current_url['path'] OR stripos($current_link['path'], $current_url['path']) !== false)
				)
				{
					// This is a vB link.
					if (
						$current_link['path'] == $current_url['path']
						OR $current_link['path'] . '/' == $current_url['path']
						OR $current_link['path'] == $current_url['path'] . '/'
					)
					{
						$rightlink = 'vb:index';
					}
					else
					{
						// Get a list of declared friendlyurl classes
						if (!$friendlyurls)
						{
							require_once(DIR . '/includes/class_friendly_url.php');
							$classes = get_declared_classes();
							foreach ($classes as $classname)
							{
								if (strpos($classname, 'vB_Friendly_Url_') !== false)
								{
									$reflect = new ReflectionClass($classname);
									$props = $reflect->getdefaultProperties();

									if ($classname == 'vB_Friendly_Url_vBCms')
									{
										$props['idvar'] = $props['ignorelist'][] = $this->registry->options['route_requestvar'];
										$props['script'] = 'content.php';
										$props['rewrite_segment'] = 'content';
									}

									if ($props['idvar'])
									{
										$friendlyurls[$classname]['idvar'] = $props['idvar'];
										$friendlyurls[$classname]['idkey'] = $props['idkey'];
										$friendlyurls[$classname]['titlekey'] = $props['titlekey'];
										$friendlyurls[$classname]['ignorelist'] = $props['ignorelist'];
										$friendlyurls[$classname]['script'] = $props['script'];
										$friendlyurls[$classname]['rewrite_segment'] = $props['rewrite_segment'];
									}
								}

								$friendlyurls['vB_Friendly_Url_vBCms']['idvar'] = $this->registry->options['route_requestvar'];
								$friendlyurls['vB_Friendly_Url_vBCms']['ignorelist'][] = $this->registry->options['route_requestvar'];
								$friendlyurls['vB_Friendly_Url_vBCms']['script'] = 'content.php';
								$friendlyurls['vB_Friendly_Url_vBCms']['rewrite_segment'] = 'content';

								$friendlyurls['vB_Friendly_Url_vBCms2']['idvar'] = $this->registry->options['route_requestvar'];
								$friendlyurls['vB_Friendly_Url_vBCms2']['ignorelist'][] = $this->registry->options['route_requestvar'];
								$friendlyurls['vB_Friendly_Url_vBCms2']['script'] = 'list.php';
								$friendlyurls['vB_Friendly_Url_vBCms2']['rewrite_segment'] = 'list';
							}
						}

						/*
						* 	FRIENDLY_URL_OFF
						*	showthread.php?t=1234&p=2
						*
						*	FRIENDLY_URL_BASIC
						*	showthread.php?1234-Thread-Title/page2&pp=2
						*
						*	FRIENDLY_URL_ADVANCED
						*	showthread.php/1234-Thread-Title/page2?pp=2
						*
						*	FRIENDLY_URL_REWRITE
						*	/threads/1234-Thread-Title/page2?pp=2
						*/

						// Try to get the script name
						// FRIENDLY_URL_OFF, FRIENDLY_URL_BASIC or FRIENDLY_URL_ADVANCED
						$scriptname = '';
						if (preg_match('#([^/]+)\.php#si', $current_link['path'], $matches))
						{
							$scriptname = $matches[1];
						}
						else
						{
							// Build a list of rewrite_segments
							foreach ($friendlyurls as $v)
							{
								$rewritesegments .= "|$v[rewrite_segment]";
							}
							$pat = '#/(' . substr($rewritesegments, 1) . ')/#si';
							if (preg_match($pat, $current_link['path'], $matches))
							{
								$uri = $matches[1];
							}
							// Decide the type of the url
							$urltype = null;
							foreach ($friendlyurls as $v)
							{
								if ($v['rewrite_segment'] == $uri)
								{
									$urltype = $v;
									break;
								}
							}

							// Convert $uri back to correct scriptname
							$scriptname = str_replace('.php', '', $urltype['script']);
						}

						if ($scriptname)
						{
							$oldrightlink = $rightlink;

							$rightlink = "vb:$scriptname";

							// Check if it's FRIENDLY_URL_BASIC or FRIENDLY_URL_ADVANCED
							if (preg_match('#(?:\?|/)(\d+).*?(?:/page(\d+)|$)#si', $oldrightlink, $matches))
							{
								// Decide the type of the url
								$urltype = null;
								foreach ($friendlyurls as $v)
								{
									if ($v['script'] == $scriptname . '.php')
									{
										$urltype = $v;
										break;
									}
								}

								if ($urltype)
								{
									$rightlink .= "/$urltype[idvar]=$matches[1]";
								}

								if ($matches[2])
								{
									$rightlink .= "/page=2";
								}
							}
							if (preg_match_all('#([a-z0-9_]+)=([a-z0-9_\+]+)#si', $current_link['query'], $matches))
							{
								foreach ($matches[0] as $match)
								{
									$rightlink .= "/$match";
								}

							}
						}
					}
				}
			}
		}

		// standard URL hyperlink
		return "<a href=\"$rightlink\" target=\"_blank\"" . ($is_external ? ' rel="nofollow"' : '') . ">$text</a>";
	}

	protected function handle_bbcode_attach($text, $option)
	{
		/*
			At the moment, this is here only to hack around the wordwrap from breaking a possibly long attach bbcode (due to a long caption in the json_encode string).
			The json_encode/decode doesn't break, but the generated *search* string for str_replace in handle_bbcode_img() does break, as that comes from the
			full text and not the *current page text* that's been through wordwrap.
		 */
		$option = strtoupper($option);
		switch ($option)
		{
			case 'JSON':
				if (!$this->options['do_html'])
				{
					$unescaped_text = html_entity_decode($text, ENT_QUOTES);
				}
				else
				{
					$unescaped_text = $text;
				}

				$data = json_decode($unescaped_text, true);

				if (empty($data))
				{
					// just let the old attachment code handle this.
					return '[ATTACH=' . $option . ']' . $text . '[/ATTACH]';
				}
				else
				{
					$data['original_text'] = $text;
					$return = $this->processAttachBBCode($data);

					return $return;
				}

				break;
			case 'CONFIG':
				$prefix = '[ATTACH=' . $option . ']';
				break;
			case '':
			default:
				$prefix = '[ATTACH]';
				break;
		}

		return $prefix . $text . '[/ATTACH]';
	}

	protected function handle_bbcode_img2($text, $option)
	{

		/*
			See notes above for handle_bbcode_attach.
			Let's also support custom settings on an external image.
		 */
		$option = strtoupper($option);
		switch ($option)
		{
			case 'JSON':
				if (!$this->options['do_html'])
				{
					$unescaped_text = html_entity_decode($text, ENT_QUOTES);
				}
				else
				{
					$unescaped_text = $text;
				}

				$data = json_decode($unescaped_text, true);

				if (empty($data))
				{
					// We don't know what to do with this
					return '[IMG2=' . $option . ']' . $text . '[/IMG2]';
				}
				else
				{
					$data['original_text'] = $text;
					$return = $this->processImg2BBCode($data);

					return $return;
				}

				break;
			default:
				$prefix = '[IMG2]';
				break;
		}

		return $prefix . $text . '[/IMG2]';
	}

	protected function isLocalUrl($url)
	{
		return vB::getUrlLoader()->isSiteUrl($url);
	}

	protected function processImg2BBCode($data)
	{
		$settings = $this->processCustomImgConfig($data);

		/*
			TODO: Do img bbcodes have any "reader" permission checks required to render image??

			Edit: In an img bbcode, we may have an external link, or a link to an image
			in another part of this forum, or even a current post's attachment just manually
			inserted via the bbcode. However, we can't know that without 1) parsing the URL,
			then 2) if it's an internal image, fetching the associated attach or photo data,
			and running permissoin checks.

			For the "current post's attachment manually inserted" case, we might want to
			do that URL parsing and checking (since its attachment data should already be
			stored in the class properties).
			I'm going to skip this for now but we may want to revisit it.

			If it's an external image, we have no business passing in our own query params here
			(and it may actually cause problems for load).
			If it's an internal image, we may want to dynamically change this based on the item's
			associated cangetimgattachment / canseethumbnails permissions, but we don't have that
			set up yet.
		 */
		$size = 'full';


		// OUTPUT LOGIC
		// src is NOT cleaned by vB_Library_Content_Text::postBbcodeParseCleanRawtext().
		// Escaped before use in attributes in getImageHtml()
		$link = $data['src'];

		$insertHtml = $this->getImageHtml($settings, $link, $size);

		return $insertHtml;
	}

	protected function processAttachBBCode($data)
	{
		$currentUserid = vB::getUserContext()->fetchUserId();

		$attachmentid = false;
		$tempid = false;
		$filedataid = false;

		if (!empty($data['data-tempid']) AND strpos($data['data-tempid'], 'temp_') === 0)
		{
			/*
			This section is strictly for the wysiwyg editor handling. If stored data has tempid
			references, then something in the save went wrong. And this parser is not well
			equipped to deal with anything frontend, so I'm just going to remove this handling.
			 */
			// this attachment hasn't been saved yet (ex. going back & forth between source mode & wysiwyg on a new content)

			// Defer to attachReplaceCallback or attachReplaceCallbackFinal
			return $data['original_text'];
		}
		else if (!empty($data['data-attachmentid']) AND is_numeric($data['data-attachmentid']))
		{
			// keep 'data-attachmentid' key in sync with text LIB's replaceAttachBbcodeTempids()
			$attachmentid = $data['data-attachmentid'];
			$filedataid = false;

			if (empty($this->attachments["$attachmentid"]))
			{
				//if we get here there was an attachment once but there is no longer.  Let's not display anything
				//because it's going to be garbage. Most likely the JSON image information we store as part of the ATTACH=Json tag
				return '';
			}

			$attachment =& $this->attachments["$attachmentid"];
			$filedataid = $attachment['filedataid'];

			// flag this for omit from append_noninline_attachments.
			if ($this->unsetattach)
			{
				$this->skipAttachmentList[$attachmentid] = array(
					'attachmentid' => $attachmentid,
					'filedataid' => $filedataid,
				);
			}

			$settings = $this->processCustomImgConfig($data);

			// todo: match nearest size
			$size = $this->getNearestImageSize($settings);

			// OUTPUT LOGIC
			$link = 'filedata/fetch?';
			if (!empty($attachment['nodeid']))
			{
				$link .= "id=$attachment[nodeid]";
			}
			else
			{
				$link .= "filedataid=$attachment[filedataid]";
			}
			if (!empty($attachment['resize_dateline']))
			{
				$link .= "&d=$attachment[resize_dateline]";
			}
			else
			{
				$link .= "&d=$attachment[dateline]";
			}

			// TODO: This doesn't look right to me. I feel like htmlSpecialCharsUni should be outside of the
			// fetchCensoredText call, but don't have time to verify this right now...
			$attachment['filename'] = vB_String::fetchCensoredText(vB_String::htmlSpecialCharsUni($attachment['filename']));
			if (empty($attachment['extension']))
			{
				$attachment['extension'] = strtolower(file_extension($attachment['filename']));
			}
			$attachment['filesize_humanreadable'] = vb_number_format($attachment['filesize'], 1, true);

			$insertHtml = $this->getImageHtml($settings, $link, $size, $attachment);

			return $insertHtml;
		}
		else
		{
			// TODO: can legacy attachments come through here...???
			/*
			// it's a legacy attachmentid, get the new id
			if (isset($this->oldAttachments[intval($matches[2])]))
			{
				// key should be nodeid, not filedataid.
				$attachmentid =  $this->oldAttachments[intval($matches[2])]['nodeid'];
				//$showOldImage = $this->oldAttachments[intval($matches[2])]['cangetattachment'];
			}
			*/
		}

		// No data match was found for the attachment, so just let attachReplaceCallback or attachReplaceCallbackFinal deal with this later.
		return $data['original_text'];
	}

	/*
		This is used when user does not have the proper cangetimgattachment | canseethumbnails channel
		permissions for an inline image.
	 */
	private function getLinkHtml($settings, $link, $size, $attachment = [])
	{
		$linktext = '';
		$alt = '';
		$title = '';
		// If we need to change putting link through htmlSpecialCharsUni(), we
		// should at least call escapeAttribute() on it.
		$link = vB_String::htmlSpecialCharsUni($link);

		if (!empty($attachment['filename']))
		{
			// attachment.filename is escaped by caller.
			$filename = $attachment['filename'];
			$linktext = $filename;
			if ($this->showAttachViews)
			{
				// todo: switch to 'image_larger_version_x_y_z' for consistency with frontend parser?
				$title = $this->getPhrase(
					'image_x_y_z',
					$filename,
					intval($attachment['counter']),
					$attachment['filesize_humanreadable']
				);
			}
			else
			{
				// todo: switch to 'image_larger_version_name_size_id' for consistency with frontend parser?
				$title = $this->getPhrase(
					'image_name_size',
					$filename,
					$attachment['filesize_humanreadable']
				);
			}
		}
		else
		{

			/*
				Mostly used in duplicate code in the legacy vB_BBCodeParser for signature pics,
				where the user may not have img bbcode perms for signatures.

				Return as a link instead, using as much info as possible.
			*/

			// alt, title & caption are cleaned TYPE_NOHTML & censored in processCustomImgConfig()

			$alt = '';
			if (!empty($settings['imgbits']['alt']))
			{
				// Double quotes should be already escaped iff settings came from processCustomImgConfig(),
				// so this escapeAttribute() is not needed most times, but see big note below.
				$alt = ' alt="' . $this->escapeAttribute($settings['imgbits']['alt']) . '"';
			}

			$title = $settings['imgbits']['title'] ?? '';

			$linktext = $link;
			if (!empty($settings['all']['caption']))
			{
				$linktext = $settings['all']['caption'];
			}
			elseif (!empty($title))
			{
				$linktext = $title;
			}
		}

		// $link is already put through htmlspecialcharsuni, but that doesn't escape singlequotes.
		// That is the default behavior for htmlspecialchars & htmlentities, and we're using double
		// quotes here explicitly. Skipping escapeAttribute().
		// alt, title & caption in settings are cleaned with TYPE_NOHTML in processCustomImgConfig()
		// and we're using double quotes so we should be ok without escapeAttribute() for "regular"
		// cases, but that's ONLY if the caller remembers to pass the settings through the process
		// function or does its own cleaning (we have at least 1 case where we generate our own
		// pseudoSetting array). IMO it doesn't really cost much to put it through escapeAttribute()
		// and since it only escapes quotes, there is no double escaping worries there, so let's just
		// put them through esacapeAttributes.
		return	"<a href=\""
					. $link
					. "\" title=\""
					. $this->escapeAttribute($title)
					. "\" $alt>$linktext</a>";
	}

	/*
		Build up the img tag, wrapping anchor & caption as specified.
	 */
	private function getImageHtml($settings, $link, $size, $attachment = [], $imgbitsExtras = [])
	{
		if (isset($attachment['extension']))
		{
			// If we have an attach record, let's check if it's an image or it needs to be a link.
			// $size is checked basically just for PDF thumbnails.
			$isImage = vB_Api::instance('content_attach')->isImage($attachment['extension'], $size);
		}
		else
		{
			// Otherwise assume the caller wants us to treat whatever this is as an image (e.g.
			// an external image URL via img|img2 bbcode)
			$isImage = true;
		}

		// Perm check if we have an attach record and enforce thumbnails only
		if (!empty($attachment['parentid']))
		{
			$currentUserid = vB::getUserContext()->fetchUserId();
			$permCheck = $this->checkImagePermissions2($currentUserid, $attachment['parentid']);
			$canViewImg = $permCheck['doImg'];
			if (!$permCheck['canFull'])
			{
				// Specify the size on the link itself so that both lightbox "fullsize" url
				// AND the inline image url will both point to thumbnails.
				// TODO: Allow $size == 'icon' case for thumbs-only-channels??
				$link .= '&type=thumb';
				$size = '';
			}
		}
		else
		{
			// Again, we don't know where this image is from (may not even be part of the forum),
			// so assume they can view it if we don't have anything to check.
			$canViewImg = true;
		}

		/*
			The only reason we do permission checks here is to make the rendered result look nicer, NOT for
			security.
			If they have no permission to see an image, any image tags will just show a broken image,
			so we show a link with the filename instead.
		*/
		$useImageTag = ($this->options['do_imgcode'] AND
			$isImage AND
			$canViewImg
		);

		if (!$useImageTag)
		{
			return $this->getLinkHtml($settings, $link, $size, $attachment);
		}


		$imgbits = $settings['imgbits'] ?? [];
		$imgbits['border'] = 0;
		$imgbits['src'] = vB_String::htmlSpecialCharsUni($link);

		if (!empty($size) AND $size != 'full')
		{
			$imgbits['src'] .= '&amp;type=' . $size;
		}

		if (empty($imgbits['alt']))
		{
			$imgbits['alt'] = '';
			if (!empty($attachment))
			{
				// attachment.filename is escaped by caller.
				if ($this->showAttachViews)
				{
					$imgbits['alt'] = $this->getPhrase(
						'image_larger_version_x_y_z',
						$attachment['filename'],
						intval($attachment['counter']),
						$attachment['filesize_humanreadable'],
						$attachment['nodeid']
					);
				}
				else
				{
					$imgbits['alt'] = $this->getPhrase(
						'image_larger_version_name_size_id',
						$attachment['filename'],
						$attachment['filesize_humanreadable'],
						$attachment['nodeid']
					);
				}
			}
		}

		// This is required for img2 plugin to recognize the image as editable
		$imgbits['classes'] = $imgbits['classes'] ?? [];
		$imgbits['classes'][] = 'bbcode-attachment';

		// These classes are replicated for the figure element in addCaption().
		// We don't want to double up the classes on BOTH figure & img (or else the image
		// plugin JS gets messier), we skip this if addCaption() will handle it.
		if (empty($settings['all']['caption']) AND isset($settings['all']['data-align']))
		{
			switch ($settings['all']['data-align'])
			{
				case 'left':
				case 'center':
				case 'right':
					$imgbits['classes'][] = 'align_' . $settings['all']['data-align'];
					break;
				default:
					// old behavior. Not sure if our css needs this for non-aligned but non-thumbnail images...
					$imgbits['classes'][] = 'thumbnail';
					break;
			}
		}

		// Extras: lightbox, if specified, always overrides, while specified classes are additive.
		$imgbits['lightbox'] = $imgbitsExtras['lightbox'] ?? $this->doLightbox($settings, $attachment);
		$imgbits['classes'] = array_merge($imgbits['classes'], $imgbitsExtras['classes'] ?? []);

		// We still add lightbox data even if this particular image won't trigger the lightbox.
		// This is to allow externally-linked images to still be pulled into the full slideshow.
		$this->addLightboxDataToImgbits($imgbits, $settings, $link, $size, $attachment);
		if (!$imgbits['lightbox'] AND $this->doLightbox)
		{
			// This is to flag the exetrnally linked image as part of the lightbox without
			// attaching the slideshow instance trigger to it (since that overrides the
			// outgoing link action)
			$imgbits['classes'][] = 'js-lightbox-participant';
		}

		$insertHtml = $this->addAnchorAndConvertToHtml($imgbits, $settings, $link, $size);
		$insertHtml = $this->addCaption($insertHtml, $settings);

		if (isset($settings['all']['data-align']) && $settings['all']['data-align'] == 'center')
		{
			$insertHtml = "<div class=\"img_align_center_wrapper\">$insertHtml</div>";
		}

		return $insertHtml;
	}

	private function doLightbox($settings, $attachment)
	{
		$doLightbox = true;
		if (!empty($attachment['parentid']))
		{
			$currentUserid = vB::getUserContext()->fetchUserId();
			$check = $this->checkImagePermissions2($currentUserid, $attachment['parentid']);
			$doLightbox = $check['doImg'];
		}

		// 0 = default, 1 = url, 2 = none
		$linkType = $settings['all']['data-linktype'] ?? $settings['link'] ?? 0;
		switch ($linkType)
		{
			case 2:
				// Allowing lightbox for "no links" for now...
				break;
			case 1:
				// Disable lightbox so we don't override the custom URL linking.
				$doLightbox = false;
				break;
			case 0:
			default:
				/*
					Default, allow lightbox.
					Not sure ATM if the already fullsized image needs a lightbox. Probably not, but
					just ignoring the size to keep the behavior consistent.
					Previous (vB4?) behavior was limit lightbox to
						'gif', 'jpg', 'jpeg', 'jpe', 'png', 'bmp'
					which might've been some kind of weird browser limitation?
					For now I'm pushing this check to whoever decided that this particular attachment
					is an image and called this function rather than having a separate extension check
					here.
				 */
				break;
		}


		return $doLightbox;
	}

	private function addLightboxDataToImgbits(&$imgbits, $settings, $link, $size, $attachment)
	{
		$thumblink = $link;
		// Only add the thumb query param if it's a local URL. otherwise external (e.g. img2)
		// images is likely to break.
		if ($this->isLocalUrl($link) AND strpos($link, '&type=thumb') === false)
		{
			$thumblink .= '&type=thumb';
		}
		$imgbits['data-fullsize-url'] = vB_String::htmlSpecialCharsUni($link);
		$imgbits['data-thumb-url'] = vB_String::htmlSpecialCharsUni($thumblink);
		// Per forum feedback, adding instructions on getting larger image, unless it seems like
		// we can't due to permissions.
		$imgbits['data-title'] = '';
		if (strpos($link, '&type=thumb') === false)
		{
			$imgbits['data-title'] = $this->getPhrase('image_click_original');
		}
		// setting caption & title escaped as part of processCustomImgConfig().
		$imgbits['data-caption'] = $settings['all']['caption'] ?? $settings['all']['title'] ?? '';
		/*
		This seems weird because the caller already escaped these fields, but we have to escape
		twice. First, we're using these as HTML attributes (<img src='...' data-title='...' ...>).
		Second, we have to make sure that when these data attributes are pulled into JS via
		$.data(), each data is safe to re-insert as raw HTML via the lightbox captioning code.
		The lightbox caption code assumes these data come from database fields which are escaped
		prior to DB insertion, and doesn't want to double escape those.
		See vBSlideshow.js's addCaption()
		 */
		// title is now using a phrase with no user data. We can't escape this as that'll break
		// the phrase placeholder replacement.
		//$imgbits['data-title'] = vB_String::htmlSpecialCharsUni($imgbits['data-title']);
		$imgbits['data-caption'] = vB_String::htmlSpecialCharsUni($imgbits['data-caption']);
	}

	protected function addCaption($insertHtml, $settings)
	{
		if (empty($settings['all']['caption']))
		{
			return $insertHtml;
		}
		else
		{
			$alignClass = '';
			if (isset($settings['all']['data-align']))
			{
				switch ($settings['all']['data-align'])
				{
					case 'left':
					case 'center':
					case 'right':
						$alignClass = ' align_' . $settings['all']['data-align'];
						break;
					default:
						break;
				}
			}

			/*
			'<figure class="{captionedClass}">' +
				template +
				'<figcaption>{captionPlaceholder}</figcaption>' +
			'</figure>
			 */
			return
				"<figure class=\"image bbcode-attachment{$alignClass}\">" .
					$insertHtml .
					"<figcaption>" . $settings['all']['caption'] . "</figcaption>" .	// no XSS here as this is put through htmlentities() in processCustomImgConfig()
				"</figure>";
		}
	}

	protected function convertImgBitsArrayToHtml($imgbits)
	{
		if ($imgbits['lightbox'] AND $this->doLightbox)
		{
			$imgbits['classes'][] = 'js-lightbox';
			$imgbits['classes'][] = 'bbcode-attachment--lightbox';
		}
		// lightbox is not a real attribute, just a helper to toggle the js-lightbox class.
		unset($imgbits['lightbox']);

		$imgbits['class'] = implode(' ', $imgbits['classes']);
		unset($imgbits['classes']);

		$imgtag = '';
		foreach ($imgbits AS $tag => $value)
		{
			$tag = vB_String::htmlSpecialCharsUni($tag);
			$value = $this->escapeAttribute($value);
			$imgtag .= "$tag=\"$value\" ";
		}
		/*
			note: be careful about adding white space before & after this. In particular, image2's plugin code's isLinkedorStandaloneImage() check
			kind of fails due to expecting <img> being the only child of <a>, and the whitespace creates a text sibling node to <img>.
		 */
		$itemprop = '';
		if(vB::getDatastore()->getOption('schemaenabled'))
		{
			$itemprop = 'itemprop="image"';
		}

		$imgtag = "<img $itemprop $imgtag/>";

		return $imgtag;
	}

	protected function addAnchorAndConvertToHtml($imgbits, $settings, $link, $size)
	{
		$hrefbits = [];
		// link: 0 = default, 1 = url, 2 = none
		if (!empty($settings['all']['data-linktype']))
		{
			if ($settings['all']['data-linktype'] == 2)
			{
				// nothing to do here..
				return $this->convertImgBitsArrayToHtml($imgbits);
			}
			else
			{
				// note, settings['all']['data-linkurl'] is currently cleaned by processCustomImgConfig
				//$settings['all']['data-linkurl'] = $settings['all']['data-linkurl'];
				// custom URL
				$linkinfo = $this->handle_bbcode_url('', $settings['all']['data-linkurl'], true);
				if ($linkinfo['link'] AND $linkinfo['link'] != 'http://')
				{
					$hrefbits['href'] = vB_String::htmlSpecialCharsUni($linkinfo['link']);

					// linktarget: 0 = self, 1 = new window
					if (!empty($settings['all']['data-linktarget']))
					{
						$hrefbits['target'] = '_blank';
					}

					// below will always occur if it's an external link.
					if ($linkinfo['nofollow'])
					{
						$hrefbits["rel"] = "nofollow";
					}
				}
			}
		}
		else
		{
			$fullsize = ($size == 'fullsize' OR $size == 'full');
			if ($fullsize)
			{
				// Do not link for a full sized image. There's no bigger image to view.
				$hrefbits = [];
			}
			else
			{
				$hrefbits['href'] = vB_String::htmlSpecialCharsUni($link);
				// todo: $hrefbits["rel"] = "nofollow"; ?
			}
		}


		// Something above might've modified imgbits, so we have to do it down here.
		$insertHtml = $this->convertImgBitsArrayToHtml($imgbits);


		if (!empty($hrefbits) AND !empty($hrefbits['href']))
		{
			if (isset($hrefbits['class']))
			{
				$hrefbits['class'] .= ' bbcode-attachment';
			}
			else
			{
				$hrefbits['class'] = 'bbcode-attachment';
			}
			$anchortag = '';
			foreach ($hrefbits AS $tag => $value)
			{
				$tag = vB_String::htmlSpecialCharsUni($tag);
				$value = $this->escapeAttribute($value);
				$anchortag .= "$tag=\"$value\" ";
			}

			$insertHtml = "<a $anchortag >" . $insertHtml . "</a>";

			return $insertHtml;
		}
		else
		{
			return $insertHtml;
		}
	}

	protected function getNearestImageSize($settings)
	{
		static $attachresizes;
		if (is_null($attachresizes))
		{
			$options = vB::getDatastore()->get_value('options');
			$attachresizes = @unserialize($options['attachresizes']);
			asort($attachresizes); // sort low to high, so we find the nearest largest image.
		}

		if (!empty($settings['all']['data-size']) AND $settings['all']['data-size']  != 'custom')
		{
			return $settings['all']['data-size'];
		}

		$copy = $attachresizes;
		if (isset($settings['all']['width']))
		{
			$width = $settings['all']['width'];
		}
		else
		{
			$width = 0;
		}

		if (isset($settings['all']['height']))
		{
			$height = $settings['all']['height'];
		}
		else
		{
			$height = 0;
		}

		// todo: What size should it be if width & height are empty? ATM not sure if they *can* be empty for image2 inserted attachments.

		foreach (array($width, $height) AS $imagelength)
		{
			foreach ($copy AS $type => $maxallowedlength)
			{
				if (empty($maxallowedlength))
				{
					// 0 == fullsize, we default to it at the end so just unset it.
					unset($copy[$type]);
					continue;
				}

				if ($maxallowedlength < $imagelength)
				{
					unset($copy[$type]);
				}
				else
				{
					break;
				}
			}
		}

		$size = 'full';

		if (!empty($copy))
		{
			reset($copy);
			$size = key($copy);
		}

		return $size;
	}

	protected function processCustomImgConfig($config_array)
	{
		if (!is_array($config_array) OR empty($config_array))
		{
			return ['all' => [], 'imgbits' => [], 'html' => ''];
		}
		/*
			If $align="config{...}", that's a json_encode'd array of custom settings, set as part of image2 plugin handler
		 */
		// todo: make this a common public arr for this & wysiwyghtmlparser
		// KEEP THIS SYNCED, GREP FOR FOLLOWING IN core/vb/wysiwyghtmlparser.php
		// GREP MARK IMAGE2 ACCEPTED CONFIG
		$accepted_config = [
			'alt'	                  => vB_Cleaner::TYPE_NOHTML,
			'title'                   => vB_Cleaner::TYPE_NOHTML,
			'data-tempid'             => vB_Cleaner::TYPE_NOHTML,
			'data-attachmentid'       => vB_Cleaner::TYPE_INT,
			'width'                   => vB_Cleaner::TYPE_NUM,
			'height'                  => vB_Cleaner::TYPE_NUM,
			'data-align'              => vB_Cleaner::TYPE_NOHTML,
			'caption'                 => vB_Cleaner::TYPE_NOHTML,
			'data-linktype'           => vB_Cleaner::TYPE_INT, // todo
			'data-linkurl'            => vB_Cleaner::TYPE_NOHTML, // todo. todo2: should this be TYPE_STR & cleaned when inserted into HTML??
			'data-linktarget'         => vB_Cleaner::TYPE_INT, // todo
			'style'                   => vB_Cleaner::TYPE_STR, //todo
			'data-size'               => vB_Cleaner::TYPE_NOHTML,
		];
		$settings = [];
		foreach ($accepted_config AS $name => $info) // $info not yet used. May be used for different types of cleaning, etc
		{
			if(isset($config_array[$name]))
			{
				//$settings[$name] = htmlentities($config_array[$name]);	// default of ENT_COMPAT is OK as we use double quotes as delimiters in caller & below
				//$settings[$name] = vB_String::htmlSpecialCharsUni($config_array[$name]); // todo: use this instead??
				$settings[$name] = $config_array[$name]; // cleaned by cleaner below. STR types must be cleaned separately!
			}
			else
			{
				// Do not set any defaults via the *cleaner*. If we don't do this, cleaner will add this to the cleaned array if it's not set in the unclean array.
				unset($accepted_config[$name]);
			}
		}


		/*
			We have to clean here instead of at save time because wysiwyg to source and back doesn't work properly since that doesn't
			go through the text api/lib.
			style is uncleaned, as it needs to be raw html, but is unset if the poster lacks the canattachmentcss permission
		 */
		$settings = vB::getCleaner()->cleanArray($settings, $accepted_config);

		// These may be part of the tooltip, or part of the title or caption of a slideshow.
		$censorList = [
			'alt',
			'title',
			'caption',
		];
		foreach ($censorList AS $_key)
		{
			if (isset($settings[$_key]))
			{
				$settings[$_key] = vB_String::fetchCensoredText($settings[$_key]);
			}
		}

		/*
			Let's do some more checks to make things play nicely.
		 */
		if (empty($settings['width']) OR !is_numeric($settings['width']))
		{
			unset($settings['width']);
		}

		if (empty($settings['height']) OR !is_numeric($settings['height']))
		{
			unset($settings['height']);
		}

		if (isset($settings['data-size']))
		{
			if ($settings['data-size'] == 'custom')
			{
				unset($settings['data-size']);
			}
			else
			{
				$settings['data-size'] = vB_Api::instanceInternal('filedata')->sanitizeFiletype($settings['data-size']);
			}
		}


		$not_part_of_img_tag = array(
			'caption' => true,
			//'data-linktype' => true, // todo
			//'data-linkurl' => true, // todo
			//'data-linktarget' => true, // todo
		);
		$imgtag = '';
		$imgbits = [];
		foreach ($settings AS $tag => $value)
		{
			if (!isset($not_part_of_img_tag[$tag]))
			{
				$tag = vB_String::htmlSpecialCharsUni($tag);
				$value = $this->escapeAttribute($value);
				$imgtag .= "$tag=\"$value\" ";
				$imgbits[$tag] = $value;
			}
		}

		return ['all' => $settings, 'imgbits' => $imgbits, 'html' => $imgtag];
	}

	/**
	* Handles an [img] tag.
	*
	* @param	string	The text to search for an image in.
	* @param	string	Whether to parse matching images into pictures or just links.
	*
	* @return	string	HTML representation of the tag.
	*/
	function handle_bbcode_img($bbcode, $do_imgcode, $has_img_code = false, $fulltext = '', $forceShowImages = false)
	{
		$sessionurl = vB::getCurrentSession()->get('sessionurl');

		$currentUser = vB::getCurrentSession()->fetch_userinfo();
		$showImages = ($currentUser['userid'] == 0 OR $currentUser['showimages'] OR $forceShowImages);

		/*
			attach & img2 bbcodes are handled via vB_Api_Bbcode::fetchTagList() but the legacy
			img bbcode has to be handled separately. We handle it here instead of in
			vB5_Template_NodeText because turning do_imgcode off instead triggers fallbacks
			to convert these to links, which we probably do not want.
		*/
		$options = vB::getDatastore()->getValue('options');
		$allowedbbcodes = $options['allowedbbcodes'];
		if (!($allowedbbcodes & vB_Api_Bbcode::ALLOW_BBCODE_IMG))
		{
			return $bbcode;
		}

		/* Do search on $fulltext, which would be the entire article, not just a page of the article which would be in $page */
		if (!$fulltext)
		{
			$fulltext = $bbcode;
		}

		// Skip checking if inside a [QUOTE] or [NOPARSE] tag.
		$find = array(
			'#\[QUOTE[^\]]*\].*\[/QUOTE\]#siU',
			'#\[NOPARSE\].*\[/NOPARSE\]#siU',
		);
		$replace = '';
		$fulltext_omit_noparse = preg_replace($find, $replace, $fulltext);
		// keep the regex roughly in sync with the one in text library's replaceAttachBbcodeTempids()
		$attachRegex = '#\[attach(?:=(?<align>[a-z]+))?\](?<type>n|temp_)?(?<id>[0-9]+)(?<tempsuffix>[0-9_]+)?\[/attach\]#i';
		if (($has_img_code & self::BBCODE_HAS_ATTACH) AND
			preg_match_all($attachRegex, $fulltext_omit_noparse, $matches)
		)
		{
			if (!empty($matches['id']))
			{
				$legacyIds = array();
				$searchForStrReplace = array();
				$replaceForStrReplace = array();
				$matchesForCallback = array();
				foreach ($matches['id'] AS $key => $id)
				{
					// full original, replace this with $replaceForStrReplace
					$searchForStrReplace[$key] =  $matches[0][$key];
					// 0 is the full match, 1 is align/config, 2 is "n{nodeid}"
					$matchesForCallback[$key] = array(
						0 => $matches[0][$key],
						1 => ((!empty($matches['align'][$key])) ? $matches['align'][$key] : ''),
						2 => $matches['type'][$key] . $id . $matches['tempsuffix'][$key],
					);

					switch ($matches['type'][$key])
					{
						case 'n':
							$nodeid = 0;
							// either a nodeid or a bugged filedataid.
							if (!empty($this->attachments[$id]))
							{
								// This is a normal one with an attachmentid
								$nodeid = $id;
								$filedataid = $this->attachments[$id]['filedataid'];
							}
							else if (!empty($this->filedataidsToAttachmentids[$id]))
							{
								/*
								 * VBV-10556  -  In addition to [IMG] bbcodes using filedataid URLs, there might be some old [ATTACH] bbcodes with
								 * n{filedataid} instead of n{attachid}.
								 * $id is a probably a filedataid, or just bogus. Since we can't tell the difference at this point, assume it's bugged not bogus.
								 * There might be multiple attachments using the filedataid, but we just have
								 * no way of knowing which this one is supposed to be. Let's just walk through them.
								 */
								$filedataid = $id;
								$nodeid = current($this->filedataidsToAttachmentids[$id]);
								if (false === next($this->filedataidsToAttachmentids[$id]))
								{
									reset($this->filedataidsToAttachmentids[$id]);
								}
							}

							if (!empty($nodeid))
							{
								$matchesForCallback[$key][2] = "n$nodeid";
								// Flag attachment for removal from array
								if ($this->unsetattach)
								{
									$this->skipAttachmentList[$nodeid] = array(
										'attachmentid' => $nodeid,
										'filedataid' => $filedataid,
									);
								}
							}

							break; // end case 'n'
						case 'temp_':
							// temporary id.
							break; // end case 'temp_'
						default:
							// most likely a legacy id.
							$legacyIds[$key] = intval($id);
							// The legacy attachments are set later, meaning it will fail the "filedataid" interpolation fixes
							// but AFAIK that's okay, because those are vB5 bugs and shouldn't be using legacy ids.
							break; // end default case
					}
				}

				// grab and set any legacy attachments.
				if (!empty($legacyIds))
				{
					$this->oldAttachments = vB_Api::instanceInternal('filedata')->fetchLegacyAttachments($legacyIds);

					if (!empty($this->oldAttachments))
					{
						$this->setAttachments($this->oldAttachments, true);
					}
				}

				// Use attachReplaceCallback() to construct the replacements.
				foreach ($matchesForCallback AS $key => $matchesArr)
				{
					$replaceForStrReplace[$key] = $this->attachReplaceCallback($matchesArr);

					if ($searchForStrReplace[$key] == $replaceForStrReplace[$key])
					{
						// No idea if PHP already optimizes this, but don't bother str_replace with the same value.
						unset( $searchForStrReplace[$key], $replaceForStrReplace[$key] );
					}
				}

				if (!empty($replaceForStrReplace))
				{
					$bbcode = str_replace($searchForStrReplace, $replaceForStrReplace, $bbcode);
				}
			}
		}

		/*
			VBV-12051 - Check for legacy [IMG] code using filedataid
			Since we know that the filedata/fetch?filedataid URL only works for publicview
			filedata (generally channel icons, forum logo, sigpics, theme icons and the like),
			let's assume that any [IMG] tag with a filedataid that's used by any *remaining*
			attachments (after above attachReplaceCallback) is an incorrect inline-inserted
			attachment image, and replace them with the proper image tags.
			This can cause some weirdness, like multiple [attach] bbcodes with the same nodeid,
			but I think this is better than the current broken behavior (where filedataid img
			will only show for the poster, and the attachment associated with the filedataid
			is listed in the  bbcode_attachment_list)
		 */
		$expectedPrefix = preg_quote('filedata/fetch?', '#');
		$regex = '#\[img\]\s*' . $expectedPrefix . '(?<querystring>[^\s]*filedataid=(?<filedataid>[0-9]+?)[^\s]*)\s*\[/img\]#iU';
		if (($has_img_code & self::BBCODE_HAS_IMG) AND
			preg_match_all($regex, $fulltext_omit_noparse, $matches)
		)
		{
			if (!empty($matches['filedataid']))
			{
				$searchForStrReplace = array();
				$replaceForStrReplace = array();
				$matchesForCallback = array();
				foreach ($matches['filedataid'] AS $key => $filedataid)
				{
					if (!empty($this->filedataidsToAttachmentids[$filedataid]))
					{
						// There might be multiple attachments using the filedataid, but we just have
						// no way of knowing which this one is supposed to be. Let's just walk through them.
						$nodeid = current($this->filedataidsToAttachmentids[$filedataid]);
						if (false === next($this->filedataidsToAttachmentids[$filedataid]))
						{
							reset($this->filedataidsToAttachmentids[$filedataid]);
						}
						$searchForStrReplace[$key] =  $matches[0][$key];

						// 0 is the full match, 1 is align/config, 2 is "n{nodeid}"
						$matchesForCallback = array(
							0 => $matches[0][$key],
							1 => '',
							2 => "n$nodeid",
						);
						// grab size if provided in query string.
						$querydata = array();
						$querystring = vB_String::unHtmlSpecialChars($matches['querystring'][$key]);
						parse_str($querystring, $querydata);
						if (!empty($querydata['type']))
						{
							$matchesForCallback['settings'] = array('size' => $querydata['type']);
						}
						elseif (!empty($querydata['thumb']))
						{
							$matchesForCallback['settings'] = array('size' => 'thumb');
						}

						$replaceForStrReplace[$key] = $this->attachReplaceCallback($matchesForCallback);
						if ($searchForStrReplace[$key] == $replaceForStrReplace[$key])
						{
							// No idea if PHP already optimizes this, but don't bother str_replace with the same value.
							unset( $searchForStrReplace[$key], $replaceForStrReplace[$key] );
						}

						if ($this->unsetattach)
						{
							$this->skipAttachmentList[$nodeid] = array(
								'attachmentid' => $nodeid,
								'filedataid' => $filedataid,
							);
						}
					}
				}

				if (!empty($replaceForStrReplace))
				{
					$bbcode = str_replace($searchForStrReplace, $replaceForStrReplace, $bbcode);
				}
			}
		}


		// Now handle everything that was left behind. These are ones we just don't know how to "fix" because we
		// couldn't find an existing, remaining attachment that we can interpolate to.
		if (($has_img_code & self::BBCODE_HAS_ATTACH) AND !empty($search))
		{
			$bbcode = preg_replace_callback($search, array($this, 'attachReplaceCallbackFinal'), $bbcode);
		}

		if ($has_img_code & self::BBCODE_HAS_IMG)
		{
			if ($do_imgcode AND $showImages)
			{
				// do [img]xxx[/img]
				$callback = function($matches) {return $this->handle_bbcode_img_match($matches[1]);};
			}
			else
			{
				$callback = function($matches) {return $this->handle_bbcode_url($matches[1], '');};
			}

			$bbcode = preg_replace_callback('#\[img\]\s*(https?://([^*\r\n]+|[a-z0-9/\\._\- !]+))\[/img\]#iU', $callback, $bbcode);
		}

		// while the handling for sigpics is this class, it's only relevant to the child class because that's the only instance
		// where the SIGPIC tag is enabled.  Should figure out how to seperate the handling but that needs to be a bigger bbcode refactor.
		if ($has_img_code & self::BBCODE_HAS_SIGPIC)
		{
			$callback = function($matches) {return $this->handle_bbcode_sigpic($matches[1]);};
			$bbcode = preg_replace_callback('#\[sigpic\](.*)\[/sigpic\]#siU', $callback, $bbcode);
		}

		if ($has_img_code & self::BBCODE_HAS_RELPATH)
		{
			$vBUrlClean = vB::getRequest()->getVbUrlClean();
			$bbcode = str_replace('[relpath][/relpath]', vB_String::htmlSpecialCharsUni($vBUrlClean), $bbcode);
		}

		return $bbcode;
	}

	protected function attachReplaceCallback($matches)
	{
		$align = $matches[1];
		$tempid = false; // used if this attachment hasn't been saved yet (ex. going back & forth between source mode & wysiwyg on a new content)
		$filedataid = false;
		$attachmentid =   false;

		// Same as before: are we looking at a legacy attachment?
		if (preg_match('#^n(\d+)$#', $matches[2], $matches2))
		{
			// if the id has 'n' as prefix, it's a nodeid
			$attachmentid = intval($matches2[1]);
		}
		else if (preg_match('#^temp_(\d+)_(\d+)_(\d+)$#', $matches[2], $matches2))
		{
			// if the id is in the form temp_##_###_###, it's a temporary id that links to hidden inputs that contain
			// the stored settings that will be saved when it becomes a new attachment @ post save.
			$tempid = $matches2[0];
			$filedataid = intval($matches2[1]);
		}
		else
		{
			// it's a legacy attachmentid, get the new id
			if (isset($this->oldAttachments[intval($matches[2])]))
			{
				// key should be nodeid, not filedataid.
				$attachmentid =  $this->oldAttachments[intval($matches[2])]['nodeid'];
			}
		}

		if (($attachmentid === false) AND ($tempid === false))
		{	// No data match was found for the attachment, so just return nothing
			return '';
		}
		else if ($attachmentid AND !empty($this->attachments[$attachmentid]))
		{	// attachment specified by [attach] tag belongs to this post
			$attachment =& $this->attachments[$attachmentid];
			$filedataid = $attachment['filedataid'];

			// TODO: This doesn't look right to me. I feel like htmlSpecialCharsUni should be outside of the
			// fetchCensoredText() call, but don't have time to verify this right now...
			$attachment['filename'] = vB_String::fetchCensoredText(vB_String::htmlSpecialCharsUni($attachment['filename']));
			if (empty($attachment['extension']))
			{
				$attachment['extension'] = strtolower(file_extension($attachment['filename']));
			}
			$attachment['filesize_humanreadable'] = vb_number_format($attachment['filesize'], 1, true);

			$settings = [];
			if (!empty($attachment['settings']) AND strtolower($align) == 'config')
			{
				$settings = unserialize($attachment['settings']);
				// TODO: REPLACE USE OF unserialize() above WITH json_decode
				// ALSO UPDATE createcontent controller's handleAttachmentUploads() to use json_encode() instead of serialize()
			}
			elseif (!empty($matches['settings']))
			{
				// Currently strictly used by VBV-12051, replacing [IMG]...?filedataid=...[/IMG] with corresponding attachment image
				// when &amp;type=[a-z]+ or &amp;thumb=1 is part of the image url, that size is passed into us as settings from handle_bbcode_img()
				// Nothing else AFAIK should be able to pass in the settings, but if we do add this as a main feature,
				// we should be sure to scrub this well (either via the regex pattern or actual cleaning) to prevent xss.
				if (isset($matches['settings']['size']))
				{
					// This cleaning is not strictly necessary since the switch-case below that uses this restricts the string to a small set, so xss is not possible.
					$size = vB_Api::instanceInternal('filedata')->sanitizeFiletype($matches['settings']['size']);
					$settings['size'] = $size;
				}
			}
			$type = $settings['size'] ?? '';


			// OUTPUT LOGIC
			$link = 'filedata/fetch?';
			if (!empty($attachment['nodeid']))
			{
				$link .= "id=$attachment[nodeid]";
			}
			else
			{
				$link .= "filedataid=$attachment[filedataid]";
			}
			if (!empty($attachment['resize_dateline']))
			{
				$link .= "&d=$attachment[resize_dateline]";
			}
			else
			{
				$link .= "&d=$attachment[dateline]";
			}



			$imgbits = [];

			$title_text = !empty($settings['title']) ? vB_String::htmlSpecialCharsUni($settings['title']) : '';
			$description_text = !empty($settings['description']) ? vB_String::htmlSpecialCharsUni($settings['description']) : '';
			$title_text = vB_String::fetchCensoredText($title_text);
			$description_text = vB_String::fetchCensoredText($description_text);
			if ($title_text)
			{
				$imgbits['title'] = $title_text;
				$imgbits['data-title'] = $title_text;	// only set this if $setting['title'] is not empty
			}
			else if ($description_text)
			{
				$imgbits['title'] = $description_text;
			}

			if ($description_text)
			{
				$imgbits['description'] = $description_text;
				$imgbits['data-description'] = $description_text;
			}

			$alt_text = !empty($settings['description']) ? vB_String::htmlSpecialCharsUni($settings['description']) : $title_text; // vB4 used to use description for alt text. vB5 seems to expect title for it, for some reason. Here's a compromise
			if ($alt_text)
			{
				$imgbits['alt'] = $alt_text;
			}

			// See VBV-14079 -- This requires the forumpermissions.canattachmentcss permission.
			// @TODO Do we want to escape this in some fashion?
			$styles = $settings['styles'] ?? false;
			if ($styles)
			{
				$imgbits['style'] = $styles;
				$imgbits['data-styles'] = vB_String::htmlSpecialCharsUni($styles);
			}
			else if (!$settings AND $align AND $align != '=CONFIG')
			{
				$imgbits['style'] = "float:$align";
			}
			// TODO: WHAT IS THIS CAPTION???
//						if ($settings['caption'])
//						{
//							$caption_tag = "<p class=\"caption $size_class\">$settings[caption]</p>";
//						}

			if (!empty($attachment['nodeid']))
			{
				// used by ckeditor.js's dialogShow handler for Image Dialogs
				$imgbits['data-attachmentid'] = $attachment['nodeid'];
			}

			// image size
			if (isset($settings['size']))
			{
				// For all the supported sizes, refer to vB_Api_Filedata::SIZE_{} class constants
				switch($settings['size'])
				{
					case 'icon':	// AFAIK vB4 didn't have this size, but vB5 does.
						$type = $settings['size'];
						break;
					case 'thumb':	// I think 'thumbnail' was used mostly in vB4. We lean towards the usage of 'thumb' instead in vB5.
					case 'thumbnail':
						$type = 'thumb';
						break;
					case 'small':	// AFAIK vB4 didn't have this size, but vB5 does.
					case 'medium':
					case 'large':
						$type = $settings['size'];
						break;
					case 'full': // I think 'fullsize' was used mostly in vB4. We lean towards the usage of 'full' instead in vB5.
					case 'fullsize':
					default: // @ VBV-5936 we're changing the default so that if settings are not specified, we're displaying the fullsize image.
						$type = 'full';
						break;
				}
			}

			$pseudoSetting = [
				'all' => [],
				'imgbits' => $imgbits,
			];
			// alignment setting
			if (isset($settings['alignment']))
			{
				$pseudoSetting['all']['data-align'] = $settings['alignment'];
			}

			// link: 0 = default, 1 = url, 2 = none
			if (!empty($settings['link']))
			{
				$pseudoSetting['all']['data-linktype'] = $settings['link'];
				if (($settings['link'] == 1) AND !empty($settings['linkurl']))
				{
					$pseudoSetting['all']['data-linkurl'] = $settings['linkurl'];

					// I think these are used for pulling old attach bbcode data into the
					// cke image2 dialog, but need to test...
					$imgbits['data-link'] = $settings['link'];
					$imgbits['data-linkurl'] = $settings['linkurl'];
					// linktarget: 0 = self, 1 = new window
					if (!empty($settings['linktarget']))
					{
						$imgbits['data-linktarget'] = $settings['linktarget'];
					}
				}
			}

			$insertHtml = $this->getImageHtml($pseudoSetting, $link, $type, $attachment);
			return $insertHtml;
		}
		else
		{
			/*
				This parser isn't meant for use with the frontend data from the editor,
				but this code is pretty straight forward so just leaving it in.
				Note that $this->filedatas is never setup for backend parsers.
			*/

			// if we have a temporaryid, then we're probably editing a post with a new attachment that doesn't have
			// a node created for the attachment yet. Let's return a basic image tag and let JS handle fixing it.
			// Skipping js-lightbox for content entry.
			if ($tempid AND $filedataid)
			{
				$filedataid = $this->escapeAttribute($filedataid);
				$tempid = $this->escapeAttribute($tempid);
				if (isset($this->filedatas[$filedataid]) AND !$this->filedatas[$filedataid]['isImage'])
				{
					return "<a class=\"bbcode-attachment\" href=\"" .
						"filedata/fetch?filedataid=$filedataid\" data-tempid=\"" . $tempid . "\" >"
						. $this->getPhrase('attachment')
						. "</a>";
				}
				else
				{
					return "<img class=\"bbcode-attachment js-need-data-att-update\" src=\"" .
						"filedata/fetch?filedataid=$filedataid\" data-tempid=\"" . $tempid . "\" />";
				}
			}
			else
			{
				// We don't know how to handle this. It'll just be replaced by a link via attachReplaceCallbackFinal later.
				return $matches[0];
			}
		}
	}

	protected function attachReplaceCallbackFinal($matches)
	{
		$attachmentid = false;

		// Same as before: are we looking at a legacy attachment?
		if (preg_match('#^n(\d+)$#', $matches[2], $matches2))
		{
			// if the id has 'n' as prefix, it's a nodeid
			$attachmentid = intval($matches2[1]);
		}

		if ($attachmentid)
		{
			// Belongs to another post so we know nothing about it ... or we are not displying images so always show a link
			return "<a href=\"filedata/fetch?filedataid=$attachmentid\">" . $this->getPhrase('attachment') . " </a>";
		}
		else
		{
			// We couldn't even get an attachmentid. It could've been a tempid but if so the default attachReplaceCallback() should've
			// handled it. I'm leaving this here just in case however.
			return $matches[0];
		}
	}

	/**
	* Handles a match of the [img] tag that will be displayed as an actual image.
	*
	* @param	string	The URL to the image.
	*
	* @return	string	HTML representation of the tag.
	*/
	function handle_bbcode_img_match($link, $fullsize = false)
	{
		$link = $this->strip_smilies(str_replace('\\"', '"', $link));
		// remove double spaces -- fixes issues with wordwrap
		$link = str_replace(array('  ', '"'), '', $link);

		$classes = 'bbcode-attachment';
		if ($this->doLightbox)
		{
			$classes .= ' bbcode-attachment--lightbox js-lightbox';
		}

		$itemprop = '';
		if(vB::getDatastore()->getOption('schemaenabled'))
		{
			$itemprop = 'itemprop="image"';
		}

		return  '<img ' . $itemprop . ' class="' . $classes . '" src="' .  $link . '" border="0" alt="" />';
	}

	/**
	* Handles the parsing of a signature picture. Most of this is handled
	* based on the $parse_userinfo member.
	*
	* @param	string	Description for the sig pic
	*
	* @return	string	HTML representation of the sig pic
	*/
	function handle_bbcode_sigpic($description)
	{
		// remove unnecessary line breaks and escaped quotes
		$description = str_replace(array('<br>', '<br />', '\\"'), array('', '', '"'), $description);

		if (empty($this->parse_userinfo['userid']) OR empty($this->parse_userinfo['sigpic']) OR (!vB::getUserContext($this->parse_userinfo['userid'])->hasPermission('signaturepermissions', 'cansigpic')))
		{
			// unknown user or no sigpic
			return '';
		}

		$sigpic_url = 'filedata/fetch?filedataid=' . $this->parse_userinfo['sigpic']['filedataid'] . '&amp;sigpic=1';

		$description = str_replace(array('\\"', '"'), '', trim($description));
		$sigpic_url = $this->escapeAttribute($sigpic_url);

		$currentUser = vB::getCurrentSession()->fetch_userinfo();
		if ($currentUser['userid'] == 0 OR $currentUser['showimages'])
		{
			return "<img src=\"$sigpic_url\" alt=\"$description\" border=\"0\" />";
		}
		else
		{
			if (!$description)
			{
				$description = $sigpic_url;
				if (vbstrlen($description) > 55 AND $this->is_wysiwyg() == false)
				{
					$description = substr($description, 0, 36) . '...' . substr($description, -14);
				}
			}
			return "<a href=\"$sigpic_url\">$description</a>";
		}
	}

	public function append_noninline_attachments($text, $attachments, $do_imgcode = false, $skiptheseattachments = array())
	{
		// STUB
		return $text;
	}

	/**
	* Handles a [size] tag
	*
	* @param	string	The text to size.
	* @param	string	The size to size to
	*
	* @return	string	HTML representation of the tag.
	*/
	function handle_bbcode_size($text, $size)
	{
		$newsize = 0;
		if (preg_match('#^[1-7]$#si', $size, $matches))
		{
			switch ($size)
			{
				case 1:
					$newsize = '8px';
					break;
				case 2:
					$newsize = '10px';
					break;
				case 3:
					$newsize = '12px';
					break;
				case 4:
					$newsize = '20px';
					break;
				case 5:
					$newsize = '28px';
					break;
				case 6:
					$newsize = '48px';
					break;
				case 7:
					$newsize = '72px';
			}

			return "<span style=\"font-size:$newsize\">$text</span>";
		}
		else if (preg_match('#^([8-9]|([1-6][0-9])|(7[0-2]))px$#si', $size, $matches))
		{
			$newsize = $size;
		}

		if ($newsize)
		{
			return "<span style=\"font-size:$newsize\">$text</span>";
		}
		else
		{
			return $text;
		}
	}

	/**
	* Parses the [table] tag and returns the necessary HTML representation.
	* TRs and TDs are parsed by this function (they are not real BB codes).
	* Classes are pushed down to inner tags (TRs and TDs) and TRs are automatically
	* valigned top.
	*
	* @param	string	Content within the table tag
	* @param	string	Optional set of parameters in an unparsed format. Parses "param: value, param: value" form.
	*
	* @return	string	HTML representation of the table and its contents.
	*/
	function parseTableTag($content, $params = '')
	{
		$helper = new vBForum_BBCodeHelper_Table($this);
		return $helper->parseTableTag($content, $params);
	}


	/**
	* Removes the specified amount of line breaks from the front and/or back
	* of the input string. Includes HTML line braeks.
	*
	* @param	string	Text to remove white space from
	* @param	int		Amount of breaks to remove
	* @param	bool	Whether to strip from the front of the string
	* @param	bool	Whether to strip from the back of the string
	*/
	function strip_front_back_whitespace($text, $max_amount = 1, $strip_front = true, $strip_back = true)
	{
		$max_amount = intval($max_amount);

		if ($strip_front)
		{
			$text = preg_replace('#^(( |\t)*((<br>|<br />)[\r\n]*)|\r\n|\n|\r){0,' . $max_amount . '}#si', '', $text);
		}

		if ($strip_back)
		{
			// The original regex to do this: #(<br>|<br />|\r\n|\n|\r){0,' . $max_amount . '}$#si
			// is slow because the regex engine searches for all breaks and fails except when it's at the end.
			// This uses ^ as an optimization by reversing the string. Note that the strings in the regex
			// have been reversed too! strrev(<br />) == >/ rb<
			$text = strrev(preg_replace('#^(((>rb<|>/ rb<)[\n\r]*)|\n\r|\n|\r){0,' . $max_amount . '}#si', '', strrev(rtrim($text))));
		}

		return $text;
	}

	/**
	* Removes translated smilies from a string.
	*
	* @param	string	Text to search
	*
	* @return	string	Text with smilie HTML returned to smilie codes
	*/
	function strip_smilies($text)
	{
		$cache =& $this->cache_smilies(false);

		// 'replace' refers to the <img> tag, so we want to remove that
		return str_replace($cache, array_keys($cache), $text);
	}

	/**
	* Determines whether a string contains an [img] tag.
	*
	* @param	string	Text to search
	*
	* @return	bool	Whether the text contains an [img] tag
	*/
	function contains_bbcode_img_tags($text)
	{
		// use a bitfield system to look for img, attach, and sigpic tags

		$hasimage = 0;
		if (stripos($text, '[/img]') !== false)
		{
			$hasimage += BBCODE_HAS_IMG;
		}

		if (stripos($text, '[/attach]') !== false)
		{
			$hasimage += BBCODE_HAS_ATTACH;
		}

		if (stripos($text, '[/sigpic]') !== false)
		{
			if (!empty($this->parse_userinfo['userid'])
				AND !empty($this->parse_userinfo['sigpic'])
				AND (vB::getUserContext($this->parse_userinfo['userid'])->hasPermission('signaturepermissions', 'cansigpic'))
			)
			{
				$hasimage += BBCODE_HAS_SIGPIC;
			}
		}

		if (stripos($text, '[/relpath]') !== false)
		{
			$hasimage += BBCODE_HAS_RELPATH;
		}

		return $hasimage;
		//return preg_match('#(\[img\]|\[/attach\])#i', $text);
		//return (stripos($text, '[img]') !== false OR stripos($text, '[/attach]') !== false) ? true : false;
		//return preg_match('#\[img\]#i', $text);
		//return iif(strpos(strtolower($bbcode), '[img') !== false, 1, 0);
	}

	/**
	* Returns the height of a block of text in pixels (assuming 16px per line).
	* Limited by your "codemaxlines" setting (if > 0).
	*
	* @param	string	Block of text to find the height of
	*
	* @return	int		Number of lines
	*/
	function fetch_block_height($code)
	{

		// establish a reasonable number for the line count in the code block
		$numlines = max(substr_count($code, "\n"), substr_count($code, "<br />")) + 1;

		// set a maximum number of lines...
		if ($numlines > $this->registry->options['codemaxlines'] AND $this->registry->options['codemaxlines'] > 0)
		{
			$numlines = $this->registry->options['codemaxlines'];
		}
		else if ($numlines < 1)
		{
			$numlines = 1;
		}

		// return height in pixels
		return ($numlines); // removed multiplier
	}

	/**
	* Fetches the colors used to highlight HTML in an [html] tag.
	*
	* @return	array	array of type (key) to color (value)
	*/
	function fetch_bbcode_html_colors()
	{
		return array(
			'attribs'	=> '#0000FF',
			'table'		=> '#008080',
			'form'		=> '#FF8000',
			'script'	=> '#800000',
			'style'		=> '#800080',
			'a'			=> '#008000',
			'img'		=> '#800080',
			'if'		=> '#FF0000',
			'default'	=> '#000080'
		);
	}

	/**
	* Returns whether this parser is a WYSIWYG parser. Useful to change
	* behavior slightly for a WYSIWYG parser without rewriting code.
	*
	* @return	bool	True if it is; false otherwise
	*/
	function is_wysiwyg()
	{
		return false;
	}

	/**
	 * Chops a set of (fixed) BB code tokens to a specified length or slightly over.
	 * It will search for the first whitespace after the snippet length.
	 *
	 * @param	array	Fixed tokens
	 * @param	integer	Length of the text before parsing (optional)
	 *
	 * @return	array	Tokens, chopped to the right length.
	 */
	function make_snippet($tokens, $initial_length = 0)
	{
		// no snippet to make, or our original text was short enough
		if ($this->snippet_length == 0 OR ($initial_length AND $initial_length < $this->snippet_length))
		{
			return $tokens;
		}

		$counter = 0;
		$stack = [];
		$new = [];
		$over_threshold = false;

		foreach ($tokens AS $tokenid => $token)
		{
			// only count the length of text entries
			if ($token['type'] == 'text')
			{
				$length = vbstrlen($token['data']);

				// uninterruptable means that we will always show until this tag is closed
				$uninterruptable = (isset($stack[0]) AND isset($this->uninterruptable["$stack[0]"]));

				if ($counter + $length < $this->snippet_length OR $uninterruptable)
				{
					// this entry doesn't push us over the threshold
					$new["$tokenid"] = $token;
					$counter += $length;
				}
				else
				{
					// a text entry that pushes us over the threshold
					$over_threshold = true;
					$last_char_pos = $this->snippet_length - $counter - 1; // this is the threshold char; -1 means look for a space at it
					if ($last_char_pos < 0)
					{
						$last_char_pos = 0;
					}

					if (preg_match('#\s#s', $token['data'], $match, PREG_OFFSET_CAPTURE, $last_char_pos))
					{
						$token['data'] = substr($token['data'], 0, $match[0][1]); // chop to offset of whitespace
						if (substr($token['data'], -3) == '<br')
						{
							// we cut off a <br /> code, so just take this out
							$token['data'] = substr($token['data'], 0, -3);
						}

						$new["$tokenid"] = $token;
					}
					else
					{
						$new["$tokenid"] = $token;
					}

					break;
				}
			}
			else
			{
				// not a text entry
				if ($token['type'] == 'tag')
				{
					// build a stack of open tags
					if ($token['closing'] == true)
					{
						// by now, we know the stack is sane, so just remove the first entry
						array_shift($stack);
					}
					else
					{
						array_unshift($stack, $token['name']);
					}
				}

				$new["$tokenid"] = $token;
			}
		}

		// since we may have cut the text, close any tags that we left open
		foreach ($stack AS $tag_name)
		{
			$new[] = array('type' => 'tag', 'name' => $tag_name, 'closing' => true);
		}

		return $new;
	}

	/** Sets the template to be used for generating quotes
	*
	* @param	string	the template name
	***/
	public function set_quote_template($template_name)
	{
		$this->quote_template = $template_name;
	}

	/** Sets the template to be used for generating quotes
	 *
	 * @param	string	the template name
	 ***/
	public function set_quote_printable_template($template_name)
	{
		$this->quote_printable_template = $template_name;
	}

	/** Sets variables to be passed to the quote template
	 *
	 * @param	string	the template name
	 ***/
	public function set_quote_vars($var_array)
	{
		$this->quote_vars = $var_array;
	}

/**
 * This is copied from the blog bbcode parser. We either have a specific
 * amount of text, or [PRBREAK][/PRBREAK].
 *
 * @param	string	text to parse
 * @param	integer	Length of the text before parsing (optional)
 * @param	boolean Flag to indicate whether do html or not
 * @param	boolean Flag to indicate whether to convert new lines to <br /> or not
 * @param	string	Defines how to handle html while parsing.
 * @param	array	Extra options for parsing.
 * 					'do_smilies' => boolean used to handle the smilies display
 *
 * @return	array	Tokens, chopped to the right length.
 */
	public function getPreview($pagetext, $initial_length = 0, $do_html = false, $do_nl2br = true, $htmlstate = null, $options = array())
	{
		if ($htmlstate)
		{
			switch ($htmlstate)
			{
				case 'on':
					$do_nl2br = false;
					break;
				case 'off':
					$do_html = false;
					break;
				case 'on_nl2br':
					$do_nl2br = true;
					break;
			}
		}

		$do_smilies = isset($options['do_smilies']) ? ((bool) $options['do_smilies']) : true;
		$this->options = array(
			'do_html'    => $do_html,
			'do_smilies' => $do_smilies,
			'do_bbcode'  => true,
			'do_imgcode' => false,
			'do_nl2br'   => $do_nl2br,
			'cachable'   => true
		);

		if (!$do_html)
		{
			$pagetext = vB_String::htmlSpecialCharsUni($pagetext);
		}

		$pagetext = $this->parse_whitespace_newlines(trim(strip_quotes($pagetext)), $do_nl2br);
		$tokens = $this->fix_tags($this->build_parse_array($pagetext));

		$counter = 0;
		$stack = array();
		$new = array();
		$over_threshold = false;

		if (!empty($options['allowPRBREAK']) AND strpos($pagetext, '[PRBREAK][/PRBREAK]'))
		{
			$this->snippet_length = strlen($pagetext);
		}
		else if (intval($initial_length))
		{
			$this->snippet_length = $initial_length;
		}
		else
		{
			if (empty($this->default_previewlen))
			{
				$this->default_previewlen = vB::getDatastore()->getOption('previewLength');

				if (empty($this->default_previewlen))
				{
					$this->default_previewlen = 200;
				}
			}
			$this->snippet_length = $this->default_previewlen;
		}

		$noparse = false;
		$video = false;
		$in_page = false;

		foreach ($tokens AS $tokenid => $token)
		{
			if (!empty($token['name']) AND ($token['name'] == 'noparse') AND $do_html)
			{
				//can't parse this. We don't know what's inside.
				$new[] = $token;
				$noparse = ! $noparse;

			}
			else if (!empty($token['name']) AND $token['name'] == 'video')
			{
				$video = !$token['closing'];
				continue;

			}
			else if (!empty($token['name']) AND $token['name'] == 'page')
			{
				$in_page = !$token['closing'];
				continue;

			}
			else if ($video OR $in_page)
			{
				continue;
			}
			// only count the length of text entries
			else if ($token['type'] == 'text')
			{
				if ($over_threshold)
				{
					continue;
				}
				if (!$noparse)
				{
					//If this has [ATTACH] or [IMG] or VIDEO then we nuke it.
					$pagetext =preg_replace('#\[ATTACH.*?\[/ATTACH\]#si', '', $token['data']);
					$pagetext = preg_replace('#\[IMG.*?\[/IMG\]#si', '', $pagetext);
					$pagetext = preg_replace('#\[video.*?\[/video\]#si', '', $pagetext);
					if ($pagetext == '')
					{
						continue;
					}

					if ($trim = stripos($pagetext, '[PRBREAK][/PRBREAK]'))
					{
						$pagetext = substr($pagetext, 0, $trim);
						$over_threshold = true;
					}
					$token['data'] = $pagetext;
				}
				$length = vB_String::vbStrlen($token['data']);

				// uninterruptable means that we will always show until this tag is closed
				$uninterruptable = (isset($stack[0]) AND isset($this->uninterruptable["$stack[0]"]));

				if ((($counter + $length) < $this->snippet_length ) OR $uninterruptable OR $noparse)
				{
					// this entry doesn't push us over the threshold
					$new[] = $token;
					$counter += $length;
				}
				else
				{
					// a text entry that pushes us over the threshold
					$over_threshold = true;
					$last_char_pos = $this->snippet_length - $counter - 1; // this is the threshold char; -1 means look for a space at it
					if ($last_char_pos < 0)
					{
						$last_char_pos = 0;
					}

					if (preg_match('#\s#s', $token['data'], $match, PREG_OFFSET_CAPTURE, $last_char_pos))
					{
						if ($do_html)
						{
							$token['data'] = strip_tags($token['data']);
						}
						$token['data'] = substr($token['data'], 0, $match[0][1]); // chop to offset of whitespace
						if (substr($token['data'], -3) == '<br')
						{
							// we cut off a <br /> code, so just take this out
							$token['data'] = substr($token['data'], 0, -3);
						}

						$new[] = $token;
					}
					else	// no white space found .. chop in the middle
					{
						if ($do_html)
						{
							$token['data'] = strip_tags($token['data']);
						}
						$token['data'] = substr($token['data'], 0, $last_char_pos);
						if (substr($token['data'], -3) == '<br')
						{
							// we cut off a <br /> code, so just take this out
							$token['data'] = substr($token['data'], 0, -3);
						}
						$new[] = $token;
					}
					break;
				}
			}
			else
			{
				// not a text entry
				if ($token['type'] == 'tag')
				{
					//If we have a prbreak we are done.
					if (($token['name'] == 'prbreak') AND isset($tokens[intval($tokenid) + 1])
						AND ($tokens[intval($tokenid) + 1]['name'] == 'prbreak')
						AND ($tokens[intval($tokenid) + 1]['closing']))
					{
						$over_threshold == true;
						break;
					}
					// build a stack of open tags
					if ($token['closing'] == true)
					{
						// by now, we know the stack is sane, so just remove the first entry
						array_shift($stack);
					}
					else
					{
						array_unshift($stack, $token['name']);
					}
				}

				$new[] = $token;
			}
		}
		// since we may have cut the text, close any tags that we left open
		foreach ($stack AS $tag_name)
		{
			$new[] = array('type' => 'tag', 'name' => $tag_name, 'closing' => true);
		}

		$result = $this->parse_array($new, $do_smilies, true, $do_html);
		return $result;
	}
/**
 * Used for any tag we ignore. At the time of this, writing that means PRBREAK and PAGE. Both are cms-only and handled outside the parser.
 *
 * @param	string	Page title
 *
 * @return	string	Output of the page header in multi page views, nothing in single page views
 */
	protected function parseDiscard($text)
	{
		return '';
	}

	/**
	* Returns true of provided $currentUserid has either cangetimageattachment or
	* canseethumbnails permission for the provided $parentid of the attachment.
	* Also stores the already checked permissions in the userImagePermissions
	* class variable.
	*
	* @param	int	$currentUserid
	* @param	int	$parentid	Parent of attachment, usually the "content" post (starter/reply)
	* @return	bool
	*/
	protected function checkImagePermissions($currentUserid, $parentid)
	{
		$check = $this->checkImagePermissions2($currentUserid, $parentid);

		return $check['doImg'];
	}

	protected function checkImagePermissions2($currentUserid, $parentid)
	{
		$thisUserContext = vB::getUserContext($currentUserid);
		/*
			The only reason we do permission checks here is to make the rendered result look nicer, NOT for
			security.
			If they have no permission to see an image, any image tags will just show a broken image,
			so we show a link with the filename instead.
		*/
		if (!isset($this->userImagePermissions[$currentUserid][$parentid]['cangetimgattachment']))
		{
			$canDownloadImages = $thisUserContext->getChannelPermission('forumpermissions2', 'cangetimgattachment', $parentid);
			$this->userImagePermissions[$currentUserid][$parentid]['cangetimgattachment'] = $canDownloadImages;
		}
		if (!isset($this->userImagePermissions[$currentUserid][$parentid]['canseethumbnails']))
		{
			// Currently there's something wrong with checking 'canseethumbnails' permission.
			// This permission is only editable via usergroup manager, and thus seems to set
			// the permission at the root level, but seems to check it at the specific channel
			// level in the user/permission context.
			$canSeeThumbs = $thisUserContext->getChannelPermission('forumpermissions', 'canseethumbnails', $parentid);
			$this->userImagePermissions[$currentUserid][$parentid]['canseethumbnails'] = $canSeeThumbs;
		}


		$hasPermission = (
			$this->userImagePermissions[$currentUserid][$parentid]['cangetimgattachment'] OR
			$this->userImagePermissions[$currentUserid][$parentid]['canseethumbnails']
		);

		return [
			'doImg' => $hasPermission,
			'canFull' => $this->userImagePermissions[$currentUserid][$parentid]['cangetimgattachment'],
			'canThumb' => $this->userImagePermissions[$currentUserid][$parentid]['canseethumbnails'],
		];
	}

	protected function getPhrase()
	{
		$phrase_array = func_get_args();
		$phraseTitle = $phrase_array[0];
		$apiReturn = vB_Api::instanceInternal('phrase')->fetch($phraseTitle);
		if (isset($apiReturn[$phraseTitle]))
		{
			/*
				The return value can actually be an array({phrasetitle} => {unparsed phrase})
			 */
			$phrase_array[0] = $apiReturn[$phraseTitle];
		}
		else
		{
			$phrase_array[0] = $apiReturn;
		}
		if ($phrase_array[0] === null OR empty($phrase_array[0]))
		{
			return '';
		}
		return @call_user_func_array('sprintf', $phrase_array);
	}

	private function escapeAttribute($text)
	{
		// THIS IS NOT MEANT TO REPLACE HTMLENTITY IN ANY CONTEXT

		// Based on some existing logic where we don't trust the values and escape it for safe usage in attributes:
		// $rightlink = str_replace(['`', '"', "'", '['], ['&#96;', '&quot;', '&#39;', '&#91;'], $this->stripSmilies($rightlink));
		// but dialing it down to just disallowing quotes as to avoid "escaping out" of the attribute quotes, with the assumption
		// that the consumer will appropriately quote/doublequote the resulting value for actual html use.
		// This is similar to our DB fieldname escape strategy.

		$search = ['"', "'",];
		$replace = ['&quot;', '&#39;',];
		$text = str_replace($search, $replace, $text);

		return $text;
	}
}

// ####################################################################

if (!function_exists('stripos'))
{
	/**
	* Case-insensitive version of strpos(). Defined if it does not exist.
	*
	* @param	string		Text to search for
	* @param	string		Text to search in
	* @param	int			Position to start search at
	*
	* @param	int|false	Position of text if found, false otherwise
	*/
	function stripos($haystack, $needle, $offset = 0)
	{
		$foundstring = stristr(substr($haystack, $offset), $needle);
		return $foundstring === false ? false : strlen($haystack) - strlen($foundstring);
	}
}

/**
* Grabs the list of default BB code tags.
*
* @param	string	Allows an optional path/URL to prepend to thread/post tags
* @param	boolean	Force all BB codes to be returned?
*
* @return	array	Array of BB code tags
*/
function fetch_tag_list($prepend_path = '', $force_all = false)
{
	return vB_Api::instanceInternal('bbcode')->fetchTagList($prepend_path, $force_all);
	// TODO: remove the following code or the whole function and replace with reference to api method

	global $vbulletin, $vbphrase;
	static $tag_list;

	if ($force_all)
	{
		$tag_list_bak = $tag_list;
		$tag_list = array();
	}

	if (empty($tag_list))
	{
		//set up some variable for later on to take into account the optional vbforum_url
		//we don't use the seo urls here because they don't play nice with the bbcode
		//processing.
		//
		//forum_path is the prefix for the forum based urls.  If provided we use it as the base.
		//if it is not an absolute url we also use the prepend_path (which is, itself, used to
		//make the url's absolute where needed).
		//forum_path_full is the same, however its always made absolute using the bburl when
		//it is not an absolute url.  This follows existing usage except for inserting the vbforum_url

		$forum_path = $prepend_path;
		$forum_path_full =  rtrim($vbulletin->options['bburl'], '/') . '/';

		$tag_list = array();

		// [QUOTE]
		$tag_list['no_option']['quote'] = array(
			'callback'          => 'handle_bbcode_quote',
			'strip_empty'       => true,
			'strip_space_after' => 2
		);

		// [QUOTE=XXX]
		$tag_list['option']['quote'] = array(
			'callback'          => 'handle_bbcode_quote',
			'strip_empty'       => true,
			'strip_space_after' => 2,
		);

		// [HIGHLIGHT]
		$tag_list['no_option']['highlight'] = array(
			'html'        => '<span class="highlight">%1$s</span>',
			'strip_empty' => true
		);

		// [NOPARSE]-- doesn't need a callback, just some flags
		$tag_list['no_option']['noparse'] = array(
			'html'            => '%1$s',
			'strip_empty'     => true,
			'stop_parse'      => true,
			'disable_smilies' => true
		);

		// [VIDEO]
		$tag_list['option']['video'] = array(
			'callback' => 'handle_bbcode_video',
			'strip_empty'     => true,
			'disable_smilies' => true,
			'stop_parse'  => true,
		);

		$tag_list['no_option']['video'] = array(
			'callback'    => 'handle_bbcode_url',
			'strip_empty' => true,
			'stop_parse'  => true,
		);

		$tag_list['no_option']['prbreak'] = array(
			'callback'    => 'parseDiscard',
			'strip_empty' => true
		);

		if (($vbulletin->options['allowedbbcodes'] & ALLOW_BBCODE_BASIC) OR $force_all)
		{
			// [B]
			$tag_list['no_option']['b'] = array(
				'html'        => '<b>%1$s</b>',
				'strip_empty' => true
			);

			// [I]
			$tag_list['no_option']['i'] = array(
				'html'        => '<i>%1$s</i>',
				'strip_empty' => true
			);

			// [U]
			$tag_list['no_option']['u'] = array(
				'html'        => '<u>%1$s</u>',
				'strip_empty' => true
			);
		}

		if (($vbulletin->options['allowedbbcodes'] & ALLOW_BBCODE_COLOR) OR $force_all)
		{
			// [COLOR=XXX]
			$tag_list['option']['color'] = array(
				'html'         => '<span style="color:%2$s">%1$s</span>',
				'option_regex' => '#^\#?\w+$#',
				'strip_empty'  => true
			);
		}

		if (($vbulletin->options['allowedbbcodes'] & ALLOW_BBCODE_SIZE) OR $force_all)
		{
			// [SIZE=XXX]
			$tag_list['option']['size'] = array(
				'callback'    => 'handle_bbcode_size',
				'strip_empty'  => true
			);
		}

		if (($vbulletin->options['allowedbbcodes'] & ALLOW_BBCODE_FONT) OR $force_all)
		{
			// [FONT=XXX]
			$tag_list['option']['font'] = array(
				'html'         => '<span style="font-family:%2$s">%1$s</span>',
				'option_regex' => '#^[^["`\':]+$#',
				'strip_empty'  => true
			);
		}

		if (($vbulletin->options['allowedbbcodes'] & ALLOW_BBCODE_ALIGN) OR $force_all)
		{
			// [LEFT]
			$tag_list['no_option']['left'] = array(
				'html'              => '<div align="left">%1$s</div>',
				'strip_empty'       => true,
				'strip_space_after' => 1
			);

			// [CENTER]
			$tag_list['no_option']['center'] = array(
				'html'              => '<div align="center">%1$s</div>',
				'strip_empty'       => true,
				'strip_space_after' => 1
			);

			// [RIGHT]
			$tag_list['no_option']['right'] = array(
				'html'              => '<div align="right">%1$s</div>',
				'strip_empty'       => true,
				'strip_space_after' => 1
			);

			// [INDENT]
			$tag_list['no_option']['indent'] = array(
				'html'              => '<blockquote>%1$s</blockquote>',
				'strip_empty'       => true,
				'strip_space_after' => 1
			);
		}

		if (($vbulletin->options['allowedbbcodes'] & ALLOW_BBCODE_LIST) OR $force_all)
		{
			// [LIST]
			$tag_list['no_option']['list'] = array(
				'callback'    => 'handle_bbcode_list',
				'strip_empty' => true
			);

			// [LIST=XXX]
			$tag_list['option']['list'] = array(
				'callback'    => 'handle_bbcode_list',
				'strip_empty' => true
			);

			// [INDENT]
			$tag_list['no_option']['indent'] = array(
				'html'              => '<blockquote>%1$s</blockquote>',
				'strip_empty'       => true,
				'strip_space_after' => 1
			);
		}

		if (($vbulletin->options['allowedbbcodes'] & ALLOW_BBCODE_URL) OR $force_all)
		{
			// [EMAIL]
			$tag_list['no_option']['email'] = array(
				'callback'    => 'handle_bbcode_email',
				'strip_empty' => true
			);

			// [EMAIL=XXX]
			$tag_list['option']['email'] = array(
				'callback'    => 'handle_bbcode_email',
				'strip_empty' => true
			);

			// [URL]
			$tag_list['no_option']['url'] = array(
				'callback'    => 'handle_bbcode_url',
				'strip_empty' => true
			);

			// [URL=XXX]
			$tag_list['option']['url'] = array(
				'callback'    => 'handle_bbcode_url',
				'strip_empty' => true
			);

			// [THREAD]
			$tag_list['no_option']['thread'] = array(
				'html'        => '<a href="' . $forum_path . 'showthread.php?' . vB::getCurrentSession()->get('sessionurl') . 't=%1$s"' .
					($vbulletin->options['friendlyurl'] ? ' rel="nofollow"' : '') . '>' . $forum_path_full . 'showthread.php?t=%1$s</a>',
				'data_regex'  => '#^\d+$#',
				'strip_empty' => true,
				'stop_parse'  => true,
			);

			// [THREAD=XXX]
			$tag_list['option']['thread'] = array(
				'html'         => '<a href="' . $forum_path . 'showthread.php?' . vB::getCurrentSession()->get('sessionurl') . 't=%2$s"' .
					($vbulletin->options['friendlyurl'] ? ' rel="nofollow"' : '') .
					' title="' . htmlspecialchars_uni($vbulletin->options['bbtitle']) . ' - ' . $vbphrase['thread'] . ' %2$s">%1$s</a>',
				'option_regex' => '#^\d+$#',
				'strip_empty'  => true,
				'stop_parse'  => true,
			);

			// [POST]
			$tag_list['no_option']['post'] = array(
				'html'        => '<a href="' . $forum_path . 'showthread.php?' . vB::getCurrentSession()->get('sessionurl') . 'p=%1$s#post%1$s"' .
					($vbulletin->options['friendlyurl'] ? ' rel="nofollow"' : '') . '>' . $forum_path_full . 'showthread.php?p=%1$s</a>',
				'data_regex'  => '#^\d+$#',
				'strip_empty' => true,
				'stop_parse'  => true,
			);

			// [POST=XXX]
			$tag_list['option']['post'] = array(
				'html'         => '<a href="' . $forum_path . 'showthread.php?' . vB::getCurrentSession()->get('sessionurl') . 'p=%2$s#post%2$s"' .
					($vbulletin->options['friendlyurl'] ? ' rel="nofollow"' : '') .
					' title="' . htmlspecialchars_uni($vbulletin->options['bbtitle']) . ' - ' . $vbphrase['post'] . ' %2$s">%1$s</a>',
				'option_regex' => '#^\d+$#',
				'strip_empty'  => true,
				'stop_parse'  => true,
			);

			if (defined('VB_API') AND VB_API === true)
			{
				$tag_list['no_option']['thread']['html'] = '<a href="vb:showthread/t=%1$s">' . $vbulletin->options['bburl'] . '/showthread.php?t=%1$s</a>';
				$tag_list['option']['thread']['html'] = '<a href="vb:showthread/t=%2$s">%1$s</a>';
				$tag_list['no_option']['post']['html'] = '<a href="vb:showthread/p=%1$s">' . $vbulletin->options['bburl'] . '/showthread.php?p=%1$s</a>';
				$tag_list['option']['post']['html'] = '<a href="vb:showthread/p=%2$s">%1$s</a>';
			}
		}

		if (($vbulletin->options['allowedbbcodes'] & ALLOW_BBCODE_PHP) OR $force_all)
		{
			// [PHP]
			$tag_list['no_option']['php'] = array(
				'callback'          => 'handle_bbcode_php',
				'strip_empty'       => true,
				'stop_parse'        => true,
				'disable_smilies'   => true,
				'disable_wordwrap'  => true,
				'strip_space_after' => 2
			);
		}

		if (($vbulletin->options['allowedbbcodes'] & ALLOW_BBCODE_CODE) OR $force_all)
		{
			//[CODE]
			$tag_list['no_option']['code'] = array(
				'callback'          => 'handle_bbcode_code',
				'strip_empty'       => true,
				'stop_parse'        => true,
				'disable_smilies'   => true,
				'disable_wordwrap'  => true,
				'strip_space_after' => 2
			);
		}

		if (($vbulletin->options['allowedbbcodes'] & ALLOW_BBCODE_HTML) OR $force_all)
		{
			// [HTML]
			$tag_list['no_option']['html'] = array(
				'callback'          => 'handle_bbcode_html',
				'strip_empty'       => true,
				'stop_parse'        => true,
				'disable_smilies'   => true,
				'disable_wordwrap'  => true,
				'strip_space_after' => 2
			);
		}

		// Legacy Hook 'bbcode_fetch_tags' Removed //
	}
	if ($force_all)
	{
		$tag_list_return = $tag_list;
		$tag_list = $tag_list_bak;
		return $tag_list_return;
	}
	else
	{
		return $tag_list;
	}


}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 116251 $
|| #######################################################################
\*=========================================================================*/
