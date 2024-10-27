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
* Stack based BB code parser.
*
* @package 		vBulletin
*/

// SigPic parsing removed from this class because it never gets called for singatures and the code didn't work because of changes.
// Its only used in the signature parser that inherets from the copy in includes/class_bbcode.php.  When we consolidate these
// parsers we'll need to restore the logic in that class to the common library.
class vB5_Template_BbCode
{
	/**#@+
	 * These make up the bit field to control what "special" BB codes are found in the text.
	 */
	const BBCODE_HAS_IMG		= 1;
	const BBCODE_HAS_ATTACH		= 2;
//	const BBCODE_HAS_SIGPIC		= 4;
	const BBCODE_HAS_RELPATH	= 8;
	/**#@-*/


	protected static bool $initialized = false;

	/**
	 * A list of default tags to be parsed.
	 * Takes a specific format. See function that defines the array passed into the c'tor.
	 *
	 * @var	array
	 */
	protected static $defaultTags = [];

	/**
	 * A list of default options for most types.
	 * Use <function> to retrieve options based on content id
	 *
	 * @var	array
	 */
	protected static $defaultOptions = [];

	/**
	 * A list of custom tags to be parsed.
	 *
	 * @var	array
	 */
	protected static $customTags = [];

	/**
	 * List of smilies
	 * @var array
	 */
	protected static $smilies = [];

	protected static $wordWrap;
	protected static $bbUrl;
	protected static $viewAttachedImages;
	protected static $urlNoFollow;
	protected static $urlNoFollowWhiteList;
	protected static $vBHttpHost;
	protected static $useFileAvatar;

	protected $renderImmediate = false;

	/**
	 * A list of tags to be parsed.
	 * Takes a specific format. See function that defines the array passed into the c'tor.
	 *
	 * @var	array
	 */
	protected $tag_list = [];

	/**
	 * Used alongside the stack. Holds a reference to the node on the stack that is
	 * currently being processed. Only applicable in callback functions.
	 */
	protected $currentTag = null;

	/**
	 * Whether this parser is parsing for printable output
	 *
	 * @var	bool
	 */
	protected $printable = false;

	/**
	 * Holds various options such what type of things to parse and cachability.
	 *
	 * @var	array
	 */
	protected $options = [];

	/**
	 * Holds the cached post if caching was enabled
	 *
	 * @var	array	keys: text (string), has_images (int)
	 */
	protected $cached = [];

	// TODO: refactor this property
	/**
	 * Reference to attachment information pertaining to this post
	 * Uses nodeid as key
	 *
	 * @var	array
	 */
	protected $attachments = null;
	protected $filedatas = null;
	/**
	 * Mapping of filedataid to array of attachment nodeids (aka attachmentids) that
	 * uses that filedata.
	 * Uses filedataid as first key, then nodeid as next key. Inner value is nodeid.
	 * Ex, node 84 & 85 point to filedataid 14, node 86 points to filedataid 15
	 * array(
	 *	14 => array(84 => 84, 85 => 85),
	 *	15 => array(86),
	 * );
	 *
	 * @var	array
	 */
	protected $filedataidsToAttachmentids = null;
	// Used strictly for VBV-12051, replacing [IMG]...?filedataid=...[/IMG] with corresponding attachment image
	protected $skipAttachmentList = [];

	// This is used for translating old attachment ids
	protected $oldAttachments = [];

	/**
	 * Whether this parser unsets attachment info in $this->attachments when an inline attachment is found
	 *
	 * @var	bool
	 */
	protected $unsetattach = true;

	// TODO: remove $this->forumid
	/**
	 * Id of the forum the source string is in for permissions
	 *
	 * @var integer
	 */
	protected $forumid = 0;

	/**
	 * Id of the outer container, if applicable
	 *
	 * @var mixed
	 */
	protected $containerid = 0;

	/**
	 * Local cache of smilies for this parser. This is per object to allow WYSIWYG and
	 * non-WYSIWYG versions on the same page.
	 *
	 * @var array
	 */
	protected $smilieCache = [];

	/**
	 * Global override for space stripping
	 *
	 * @var	bool
	 */
	protected $stripSpaceAfter = true;

	/** Template for generating quote links. We need to override for cms comments" **/
	protected $quotePrintableTemplate = 'bbcode_quote_printable';

	/** Template for generating quote links. We need to override for cms comments" **/
	protected $quoteTemplate =  'bbcode_quote';

	/**Additional parameter(s) for the quote template. We need for cms comments **/
	protected $quoteVars = false;

	/**
	 * Object to provide the implementation of the table helper to use.
	 * See setTableHelper and getTableHelper.
	 *
	 * @var	vB5_Template_BbCode_Table
	 */
	protected $tableHelper = null;

	private $bbcodeHelper = null;

	/**
	 *	Display full size image attachment if an image is [attach] using without =config, otherwise display a thumbnail
	 *
	 */
	// No longer used.
	//protected $displayimage = true;	// @ VBV-5936 we're changing the default so that if settings are not specified, we're displaying the fullsize image.

	/**
	 * Tells the parser to handle a multi-page render, in which case,
	 * the [PAGE] and [PRBREAK] bbcodes are handled differently and are
	 * discarded.
	 */
	protected $multiPageRender = false;

	protected $userImagePermissions = [];

	/** Show attachment view counts in alt texts. **/
	protected $showAttachViews = true;

	// Mainly for the signature parser to disable lightboxing.
	protected $doLightbox = true;

	protected $local_smilies;
	protected $snippet_length;

	// Used in get_preview()
	protected $default_previewlen = 0;

	// TODO: combine with $this->options?
	protected $renderOptions = [
		'url_preview' => true,
		'url_truncate' => true,
	];

	protected function getBbcodeRenderOptions() : array
	{
		$renderOptions = $this->renderOptions;
		vB::getHooks()->invoke('hookGetBbcodeRenderOptions',[
			'context' => 'NORMAL_FRONTEND',
			'renderOptions' => &$renderOptions,
		]);
		return $renderOptions;
	}

	protected function initBbcodeRenderOptions(array $vboptions) : void
	{
		$this->renderOptions['url_preview'] = ($this->renderOptions['url_preview'] AND $vboptions['url_preview']);
	}

	/**
	 * Constructor. Sets up the tag list.
	 *
	 * @param	bool		Whether to append customer user tags to the tag list
	 */
	public function __construct($appendCustomTags = true)
	{
		if (!self::$initialized)
		{
			$response = Api_InterfaceAbstract::instance()->callApi('bbcode', 'initInfo');
			self::$defaultTags = $response['defaultTags'];
			self::$customTags = $response['customTags'];
			self::$defaultOptions = $response['defaultOptions'];
			self::$smilies = $response['smilies'];
			self::$wordWrap = $response['wordWrap'];
			self::$bbUrl = $response['bbUrl'];
			self::$viewAttachedImages = $response['viewAttachedImages'];
			self::$urlNoFollow = $response['urlNoFollow'];
			self::$urlNoFollowWhiteList = $response['urlNoFollowWhiteList'];
			self::$vBHttpHost = $response['vBHttpHost'];
			self::$useFileAvatar = $response['useFileAvatar'];

			self::$initialized = true;
		}

		$this->tag_list = self::$defaultTags;
		if ($appendCustomTags)
		{
			$this->tag_list = array_replace_recursive($this->tag_list, self::$customTags);
		}

		$options = vB5_Template_Options::instance();
		$this->showAttachViews = (bool) $options->get('options.attachmentviewstrack');

		// Toggle render options based on global settings... but not all
		// options are readily available, so we use the bbcode API to ferry
		// a subset of the relevant ones to us.
		$this->initBbcodeRenderOptions(self::$defaultOptions['renderoptions']);

		$this->bbcodeHelper = new vB_BbCodeHelper($this->tag_list, self::$smilies);

		// Legacy Hook 'bbcode_create' Removed //
	}

	/**
	 * Adds attachments to the class property using attachment nodeid as key.
	 * Using nodeid keeps us from overwriting separate attachments that are using the same
	 * Filedata record.
	 *
	 * @param	Array	$attachments	Attachments data array with nodeid, filedataid,
	 *									 parentid, contenttypeid etc, typically from
	 *									@see vB_Api_Node::getNodeAttachments() or
	 *									@see vB_Api_Node::getNodeAttachmentsPublicInfo()
	 * @param	Bool	$skipattachlist	Only used internally for legacy attachments. Notifies
	 *									the class to not append these attachments to the list
	 *									of non-inline attachments.
	 */
	public function setAttachments($attachments, $skipattachlist = false)
	{
		$this->userImagePermissions = [];
		$currentUserid = vB5_User::get('userid');
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
					$this->skipAttachmentList[$attachment['nodeid']] = [
						'attachmentid' => $attachment['nodeid'],
						'filedataid' => $attachment['filedataid'],
					];

				}


				/*
					So I would rather not have the frontend bbcode parser care or know about permissions. However,
					there's just no way to get around checking permissions in order to decide on whether to
					display image attachments as image tags (displayed broken since the image fetch fails), or
					as anchors with filename as the link text value.
					Alternatively, we should have the API etc just build up all the "render" options based on permissions,
					but at this point, this is the simplest way IMO to take care of the "show image or show anchor" issue.
				 */
				// The permissions are stored in memory in just 1 location for easier maintenance/call.
				$this->checkImagePermissions($currentUserid, $attachment['parentid']);
			}
		}
	}

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
	public function getAndSetAttachments($nodeid)
	{
		// attachReplaceCallback() will only show an img tag if $this->options['do_imgcode']; is true
		$photoTypeid = vB_Types::instance()->getContentTypeId('vBForum_Photo');
		$attachments = array();
		$apiResult = Api_InterfaceAbstract::instance()->callApi('node', 'getNodeAttachmentsPublicInfo', array($nodeid));
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

	/*
	 * Bulk fetch filedata records & add to $this->filedatas using filedataid as key.
	 * Used by createcontent controller's parseWysiwyg action, when editor is switched from
	 * source to wysiwyg mode, for new attachments with tempids.
	 */
	public function prefetchFiledata($filedataids)
	{
		if (!empty($filedataids))
		{
			$imagehandler = vB_Image::instance();
			$filedataRecords = Api_InterfaceAbstract::instance()->callApi('filedata', 'fetchFiledataByid', array($filedataids));
			foreach ($filedataRecords AS $record)
			{
				$record['isImage'] = $imagehandler->isImageExtension($record['extension']);
				$this->filedatas[$record['filedataid']] = $record;
			}
		}
	}

	/**
	 * Sets the engine to render immediately
	 *
	 *	@param	bool	whether to set immediate on or off
	 */
	public function setRenderImmediate($immediate = true)
	{
		$this->renderImmediate = $immediate;
	}

	/**
	 * Sets the parser to handle a multi-page render, in which case,
	 * the [PAGE] and [PRBREAK] bbcodes are handled differently and are
	 * discarded.
	 */
	public function setMultiPageRender($multiPage)
	{
		$this->multiPageRender = (bool) $multiPage;
	}

	/**
	 * Sets whether this parser is parsing for printable output
	 * @var	bool
	 */
	public function setPrintable($bool)
	{
		$this->printable = $bool;
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
	public function parse(
		$text,
		$forumid = 0,
		$allowsmilie = true,
		$isimgcheck = false,
		$parsedtext = '',
		$parsedhasimages = 3,
		$cachable = false,
		$htmlstate = null
	)
	{
		$this->forumid = $forumid;

		$donl2br = true;

		if (empty($forumid))
		{
			$forumid = 'nonforum';
		}

		switch($forumid)
		{
			case 'calendar':
			case 'privatemessage':
			case 'usernote':
			case 'visitormessage':
			case 'groupmessage':
			case 'socialmessage':
				$dohtml = self::$defaultOptions[$forumid]['dohtml'];
				$dobbcode = self::$defaultOptions[$forumid]['dobbcode'];
				$dobbimagecode = self::$defaultOptions[$forumid]['dobbimagecode'];
				$dosmilies = self::$defaultOptions[$forumid]['dosmilies'];
				break;

			// parse non-forum item
			case 'nonforum':
				$dohtml = self::$defaultOptions['nonforum']['dohtml'];
				$dobbcode = self::$defaultOptions['nonforum']['dobbcode'];
				$dobbimagecode = self::$defaultOptions['nonforum']['dobbimagecode'];
				$dosmilies = self::$defaultOptions['nonforum']['dosmilies'];
				break;

			// parse forum item
			default:
				if (intval($forumid))
				{
					$forum = fetch_foruminfo($forumid);
					$dohtml = $forum['allowhtml'];
					$dobbimagecode = $forum['allowimages'];
					$dosmilies = $forum['allowsmilies'];
					$dobbcode = $forum['allowbbcode'];
				}
				// else they'll basically just default to false -- saves a query in certain circumstances
				break;
		}
		// need to make sure public preview images also works...

		if (!$allowsmilie)
		{
			$dosmilies = false;
		}

		// Legacy Hook 'bbcode_parse_start' Removed //

		if (!empty($parsedtext))
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
			return $this->doParse($text, $dohtml, $dosmilies, $dobbcode, $dobbimagecode, $donl2br, $cachable, $htmlstate);
		}
	}

	/** @var ?vB_BbCodeDataCache */
	protected $bbcodeDataCache;
	protected $nodeid;

	public function setDataCache(?vB_BbCodeDataCache $cache)
	{
		$this->bbcodeDataCache = $cache;
	}

	public function setNodeid(int $nodeid)
	{
		$this->nodeid = $nodeid;
	}

	/**
	 * Parse the string with the selected options
	 *
	 * @param string  $text Unparsed text
	 * @param bool    $do_html Whether to allow HTML (true) or not (false)
	 * @param bool    $do_smilies Whether to parse smilies or not
	 * @param bool    $do_bbcode Whether to parse BB code
	 * @param bool    $do_imgcode Whether to parse the [img] BB code (independent of $do_bbcode)
	 * @param bool    $do_nl2br Whether to automatically replace new lines with HTML line breaks
	 * @param bool    $cachable Whether the post text is cachable
	 * @param string  $htmlstate Switch for dealing with nl2br
	 * @param boolean $minimal Do minimal required actions to parse bbcode
	 * @param string  $fulltext Full rawtext, ignoring pagebreaks.
	 * @param bool    $do_censor Whether to censor the text
	 *
	 * @return	string	Parsed text
	 */
	public function doParse(
		$text,
		$do_html = false,
		$do_smilies = true,
		$do_bbcode = true ,
		$do_imgcode = true,
		$do_nl2br = true,
		$cachable = false,
		$htmlstate = null,
		$minimal = false,
		$fulltext = '',
		$do_censor = true
	)
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
			'do_smilies' => $do_smilies,
			'do_bbcode'  => $do_bbcode,
			'do_imgcode' => $do_imgcode,
			'do_nl2br'   => $do_nl2br,
			'cachable'   => $cachable
		);
		$this->cached = array('text' => '', 'has_images' => 0);

		if (empty($fulltext))
		{
			// AFAIK these two should be different only for articles with multiple pages.
			$fulltext = $text;
		}

		// ********************* REMOVE HTML CODES ***************************
		if (!$do_html)
		{
			$text = vB5_String::htmlSpecialCharsUni($text);
		}

		if (!$minimal)
		{
			$text = $this->parseWhitespaceNewlines($text, $do_nl2br);
		}

		// ********************* PARSE BBCODE TAGS ***************************
		if ($do_bbcode)
		{
			$text = $this->parseBbcode($text, $do_smilies, $do_imgcode, $do_html, $do_censor);
		}
		else if ($do_smilies)
		{
			$text = $this->parseSmilies($text, $do_html);
		}

		// This also checks for [/attach], which might actually be a non-image attachment. Parsing non-image attachments
		// is handled in attachReplaceCallback(), so this works out.
		$has_img_tag = 0;
		if (!$minimal)
		{
			// parse out nasty active scripting codes
			static $global_find = array('/(javascript):/si', '/(about):/si', '/(vbscript):/si', '/&(?![a-z0-9#]+;)/si');
			static $global_replace = array('\\1<b></b>:', '\\1<b></b>:', '\\1<b></b>:', '&amp;');
			$text = preg_replace($global_find, $global_replace, $text);

			// Since we always use the $fulltext to look for IMG & ATTACH codes to replace with image tags, and sometimes
			// the local filedata URL used in an IMG code might have a query param that we care about (ex &type=icon) whose
			// ampersand & would be replaced with &amp; Let's just make sure that the found text will match the one actually
			// being replaced in $text as closely as possible.
			// For details, see handle_bbcode_img(), $searchForStrReplace
			$fulltext = preg_replace('/&(?![a-z0-9#]+;)/si', '&amp;', $fulltext);
			$has_img_tag = ($do_bbcode ? $this->containsBbcodeImgTags($fulltext) : 0);
		}

		// save the cached post
		if ($this->options['cachable'])
		{
			$this->cached['text'] = $text;
			$this->cached['has_images'] = $has_img_tag;
		}

		// do [img] tags if the item contains images
		if(($do_bbcode OR $do_imgcode) AND $has_img_tag)	// WHY IS THIS $do_bbcode OR $do_imgcode???
		{
			$text = $this->handle_bbcode_img($text, $do_imgcode, $has_img_tag, $fulltext);
		}

		// We're expanding previews in place but only for urls on its own lines (not inline)
		//$text = $this->append_link_previews($text, $this->bbcodeDataCache ?? null);

		/*
		TODO: lightbox images in the list, and force thumb for canseethumbnails only channels?
		 */

		$text = $this->append_noninline_attachments($text, $this->attachments, $do_imgcode, $this->skipAttachmentList);

		// Legacy Hook 'bbcode_parse_complete' Removed //
		return $text;
	}

	// Removing this for now, as we're no longer appending the list of link previews at the end of post.
	/*
	private function append_link_previews(string $text, ?vB_BbCodeDataCache $bbcodeDataCache)
	{
		if (is_null($bbcodeDataCache))
		{
			// workaround for vB5_Frontend_Controller_Bbcode::verifyImgCheck() during vB5_Frontend_Controller_CreateContent::createNewNode() hitting this without
			// a bbcode data cache set.
			return $text;
		}
		$append = $bbcodeDataCache->fetchAppends($this->nodeid, 'url');
		if (!empty($append))
		{
			$text .= '<div style="margin: 10px; border: solid black 1px; background-color: grey;">' . $append . '</div>';
		}
		return $text;
	}
	 */

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
	 * Handles the [PAGE] bbcode
	 *
	 * @param	string	The text
	 *
	 * @return	string
	 */
	protected function parsePageBbcode($text)
	{
		if ($this->multiPageRender)
		{
			return '';
		}
		else
		{
			return '<h3 class="wysiwyg_pagebreak">' . $text . '</h3>';
		}
	}

	/**
	 * Handles the [PRBREAK] bbcode
	 *
	 * @param	string	The text
	 *
	 * @return	string
	 */
	protected function parsePrbreakBbcode($text)
	{
		if ($this->multiPageRender)
		{
			return '';
		}
		else
		{
			return '<hr class="previewbreak" />' . $text;
		}
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
	public function get_preview($pagetext, $initial_length = 0, $do_html = false, $do_nl2br = true, $htmlstate = null, $options = array())
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
			$pagetext = vB5_String::htmlSpecialCharsUni($pagetext);
		}

		$pagetext = $this->parseWhitespaceNewlines(trim(strip_quotes($pagetext)), $do_nl2br);
		$tokens = $this->buildParseArrayAndFixTags($pagetext);

		$counter = 0;
		$stack = array();
		$new = array();
		$over_threshold = false;

		if ($options['allowPRBREAK'] AND strpos($pagetext, '[PRBREAK][/PRBREAK]'))
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
				$options =  Api_InterfaceAbstract::instance()->callApiStatic('options', 'fetchStatic', array('previewLength'));
				$this->default_previewlen = $options['previewLength'];

				if (empty($this->default_previewlen))
				{
					$this->default_previewlen = 200;
				}
			}
			$this->snippet_length = $this->default_previewlen;
		}

		$noparse = false;

		//strip these tags from the preview including anything they might contain
		//we keep track of each seperately, but that might be overkill (we shouldn't
		//see a case where they are nested).
		$strip_tags = array_fill_keys(array('video', 'page', 'attach', 'img2'), 0);

		foreach ($tokens AS $tokenid => $token)
		{
			if (!empty($token['name']) AND ($token['name'] == 'noparse') AND $do_html)
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
				$length = vB5_String::vbStrlen($token['data']);

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
						// Note, URLs in bbcodes are frequently chopped off improperly around here.
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

		// This is a bandaid to a bigger problemm, but since the token data that's truncated above might actually
		// include things like invalid URLs (truncated URLs) and such, we do NOT want to save any vB_BbCode instances
		// that might be generated during this parse to bbcode_data. Again, this is because the data might be totally
		// bogus, and preview vs fullview should NOT have different bbcode instance states (rather, the preview should
		// just utilize a subset of the instances)
		$bbcodeDataCache = $this->bbcodeDataCache;
		$this->setDataCache(null);
		// Disable URL preview in post previews... it was never meant for post previews, and due to the preview
		// logic just kinda chopping off the URL at the preview length, it's not even the valid URL.
		$previousRenderOptions = $this->renderOptions;
		$this->renderOptions['url_preview'] = false;
		// Not used yet, but in post-preview mode, url bbcodes should be plaintext'ed, AND ellipsized when truncated.
		// We may actually skip the bbcode altogether in the above block and swap with a text block instead of pushing
		// the task down to vB_BbCode_Url...
		//$this->renderOptions['url_plaintext'] = true;

		try
		{
			$result = $this->parseArray($new, $do_smilies, true, $do_html);
		}
		finally
		{
			// Restore things back regardless of what happened in parseArray().
			// There might still be rare edge cases where something downstream of the
			// vB_BbCodes leak an exception... while we're not catching that
			// let's still put things back the way they were...
			$this->setDataCache($bbcodeDataCache);
			$this->renderOptions = $previousRenderOptions;
		}

		return $result;
	}

	/**
	 * Word wraps the text if enabled.
	 *
	 * @param	string	Text to wrap
	 *
	 * @return	string	Wrapped text
	 */
	protected function doWordWrap($text)
	{
		if (self::$wordWrap != 0)
		{
			$text = vB5_String::fetchWordWrappedString($text, self::$wordWrap, '  ');
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
	protected function parseSmilies($text, $do_html = false)
	{
		static $regex_cache;

		// this class property is used just for the callback function below
		$this->local_smilies = $this->cacheSmilies($do_html);

		$cache_key = ($do_html ? 'html' : 'nohtml');

		if (!isset($regex_cache[$cache_key]))
		{
			$regex_cache[$cache_key] = [];
			$quoted = [];

			foreach ($this->local_smilies AS $find => $replace)
			{
				$quoted[] = preg_quote($find, '/');
				if (sizeof($quoted) > 500)
				{
					$regex_cache[$cache_key][] = '/(?<!&amp|&quot|&lt|&gt|&copy|&#[0-9]{1}|&#[0-9]{2}|&#[0-9]{3}|&#[0-9]{4}|&#[0-9]{5})(' . implode('|', $quoted) . ')/s';
					$quoted = [];
				}
			}

			if (sizeof($quoted) > 0)
			{
				$regex_cache[$cache_key][] = '/(?<!&amp|&quot|&lt|&gt|&copy|&#[0-9]{1}|&#[0-9]{2}|&#[0-9]{3}|&#[0-9]{4}|&#[0-9]{5})(' . implode('|', $quoted) . ')/s';
			}
		}

		foreach ($regex_cache[$cache_key] AS $regex)
		{
			$text = preg_replace_callback($regex, [$this, 'replaceSmiliesPregMatch'], $text);
		}

		return $text;
	}

	/**
	 * Callback function for replacing smilies.
	 *
	 * @ignore
	 */
	protected function replaceSmiliesPregMatch($matches)
	{
		return $this->local_smilies[$matches[0]];
	}

	/**
	 * Caches the smilies in a form ready to be executed.
	 *
	 * @param	bool	Whether HTML parsing is enabled
	 *
	 * @return	array	Smilie cache (key: find text; value: replace text)
	 */
	protected function cacheSmilies($do_html)
	{
		return $this->bbcodeHelper->cacheSmilies($do_html);
	}

	/**
	 * Parses out specific white space before or after cetain tags and does nl2br
	 *
	 * @param	string	Text to process
	 * @param	bool	Whether to translate newlines to <br /> tags
	 *
	 * @return	string	Processed text
	 */
	protected function parseWhitespaceNewlines($text, $do_nl2br = true)
	{
		return $this->bbcodeHelper->parseWhitespaceNewlines($text, $do_nl2br);
	}

	/**
	 * Parse an input string with BB code to a final output string of HTML
	 *
	 * @param string $input_text   Input Text (BB code)
	 * @param bool   $do_smilies   Whether to parse smilies
	 * @param bool   $do_imgcode   Whether to parse img (for the video tags)
	 * @param bool   $do_html      Whether to allow HTML (for smilies)
	 * @param bool   $do_censor    Whether to censor the text
	 *
	 * @return	string	Ouput Text (HTML)
	 */
	protected function parseBbcode($input_text, $do_smilies, $do_imgcode, $do_html = false, $do_censor = true)
	{
		$array = $this->buildParseArrayAndFixTags($input_text);
		return $this->parseArray($array, $do_smilies, $do_imgcode, $do_html, $do_censor);
	}

	private function buildParseArrayAndFixTags($text)
	{
		return $this->bbcodeHelper->buildParseArrayAndFixTags($text);
	}

	/**
	 * Override each tag's default strip_space_after setting ..
	 * We don't want to strip spaces when parsing bbcode for the editor
	 *
	 * @param	bool
	 */
	function setStripSpace($value)
	{
		$this->stripSpaceAfter = $value;
	}

	/**
	 * Takes a parse array and parses it into the final HTML.
	 * Tags are assumed to be matched.
	 *
	 * @param array $preparsed   Parse array
	 * @param bool  $do_smilies  Whether to parse smilies
	 * @param bool  $do_imgcode  Whether to parse img (for the video tags)
	 * @param bool  $do_html     Whether to allow HTML (for smilies)
	 * @param bool  $do_censor   Whether to censor the text
	 *
	 * @return	string	Final HTML
	 */
	protected function parseArray($preparsed, $do_smilies, $do_imgcode, $do_html = false, $do_censor = true)
	{
		$output = '';
		$frontendurl = vB5_Template_Options::instance()->get('options.frontendurl');

		$stack = [];
		$stack_size = 0;

		// holds options to disable certain aspects of parsing
		$parse_options = [
			'no_parse'          => 0,
			'no_wordwrap'       => 0,
			'no_smilies'        => 0,
			'strip_space_after' => 0
		];

		// we can't move this to the helper class yet as all of the callbacks are still in "this" class

		// Numerically indexed preparsed array allows us to quickly walk back or forward once
		// from the current bbcode stack in order to pull the "context" or "neighbor" information.
		// This is just in case the parsed array changes keys aren't numerical
		$keys = array_keys($preparsed);
		// For url bbcode, walk back from the bbcode opening tag in $preparsed (via the numeric $keys),
		// check if it has no preceding node OR its previous node ends in a <br /> (or <br> or any other
		// weird permutation of break tag), and walk forward from the bbcode closing tag in $preparsed
		// to check if it has no following node or its next node begins with a <br /> etc (in both cases,
		// ignore any leading or trailing whitespaces), and in that case, show the URL preview.
		// Note that this check is done in vB_BbCodeHelper::tagIsOnOwnLine()



		// flag to fix whitespace after each PRBREAK is rendered
		$fixPrbreak = false;


		foreach ($keys AS $__numindex => $__parsedkey)
		{
			$node = $preparsed[$__parsedkey];

			$pending_text = '';
			if ($node['type'] == 'text')
			{
				$pending_text =& $node['data'];

				// remove leading space after a tag
				if ($parse_options['strip_space_after'])
				{
					$pending_text = $this->stripFrontBackWhitespace($pending_text, $parse_options['strip_space_after'], true, false);
					$parse_options['strip_space_after'] = 0;
				}

				// parse smilies
				if ($do_smilies AND !$parse_options['no_smilies'])
				{
					$pending_text = $this->parseSmilies($pending_text, $do_html);
				}

				// do word wrap
				if (!$parse_options['no_wordwrap'])
				{
					$pending_text = $this->doWordWrap($pending_text);
				}

				// fix whitespace after PRBREAK
				if ($fixPrbreak)
				{
					// if a PRBREAK is followed by at least one line break then
					// we need to add an additional line break so that it matches
					// the WYSIWYG editor, which displays with an additional line
					// break due to displaying the PRBREAK as an <hr> element
					// we only do this when rendering the node for display in the
					// thread, not when displaying in the editor (multiPageRender)
					// see VBV-12316.
					if ($this->multiPageRender AND preg_match('#^<br[^>]*>#si', $pending_text))
					{
						$pending_text = '<br />' . $pending_text;
					}
					$fixPrbreak = false;
				}

				if ($parse_options['no_parse'])
				{
					$pending_text = str_replace(array('[', ']'), array('&#91;', '&#93;'), $pending_text);
				}

				if($do_censor)
				{
					$pending_text = vB5_String::fetchCensoredText($pending_text);
				}
			}
			else if ($node['closing'] == false)
			{
				$parse_options['strip_space_after'] = 0;
				$fixPrbreak = false;

				if ($parse_options['no_parse'] == 0)
				{
					// opening a tag
					// initialize data holder and push it onto the stack
					$node['data'] = '';
					// Keep track of the numeric index because otherwise, finding the "previous node"
					// from something in $stack is tricky. However, this modifies the array meaning
					// doubling memory against $preparsed due to array copies
					// This might double memory usage, but we already have some amount of array writes
					// (e.g. the pending text appends against $stack[0] & $pending_text) that causes
					// array copy-on-write against some of the elements.
					$node['numindex'] = $__numindex;

					//todo: there might be a better way to do this than having to rebuild the array each time
					// due to array_unshift...
					array_unshift($stack, $node);
					++$stack_size;
					$has_option = $node['option'] !== false ? 'option' : 'no_option';
					$tag_info =& $this->tag_list[$has_option][$node['name']];

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
				$fixPrbreak = false;

				// closing a tag
				// look for this tag on the stack
				if (($key = $this->findFirstTag($node['name'], $stack)) !== false)
				{
					// found it
					$open =& $stack[$key];
					$__openTagPosition = $open['numindex'];
					$__closeTagPosition = $__numindex;

					$this->currentTag =& $open;

					$has_option = $open['option'] !== false ? 'option' : 'no_option';

					// check to see if this version of the tag is valid
					if (isset($this->tag_list[$has_option][$open['name']]))
					{
						$tag_info =& $this->tag_list[$has_option][$open['name']];

						// make sure we have data between the tags
						if ((isset($tag_info['strip_empty']) AND $tag_info['strip_empty'] == false) OR trim($open['data']) != '')
						{
							// make sure our data matches our pattern if there is one
							if (empty($tag_info['data_regex']) OR preg_match($tag_info['data_regex'], $open['data']))
							{
								// now do the actual replacement
								if (isset($tag_info['html']))
								{
									// this is a simple HTML replacement
									// removing bad fix per Freddie.
									//$search = array("'", '=');
									//$replace = array('&#039;', '&#0061;');
									//$open['data'] = str_replace($search, $replace, $open['data']);
									//$open['option'] = str_replace($search, $replace, $open['option']);
									$pending_text = sprintf($tag_info['html'], $open['data'], $open['option'], $frontendurl);
								}
								else if (isset($tag_info['callback']) OR !empty($tag_info['handlers']))
								{
									if (isset($tag_info['callback']))
									{
										// call a callback function
										if ($tag_info['callback'] == 'handle_bbcode_video' AND !$do_imgcode)
										{
											// tag_info is assigned by reference. Do we really mean to change this value?
											$tag_info['callback'] = 'handle_bbcode_url';
											$open['option'] = '';
										}

										// fix whitespace after PRBREAK
										if ($tag_info['callback'] == 'parsePrbreakBbcode')
										{
											$fixPrbreak = true;
										}
									}

									// Get surrounding context.

									// there's probably a more sane way to do this... but can't think of one right now
									// without converting preparsed to some kind of tree so the sibling node relation
									// is trivial.
									// Basically, find the numeric index for the "open" tag so that we can find the
									// previous sibling for the entire tag node. Next sibling is trivial as it's just
									// $__numindex + 1.
									//$__openTagPosition = $open['numindex'];
									$__tagIsOnOwnLine = $this->bbcodeHelper->tagIsOnOwnLine($__openTagPosition, $__closeTagPosition, $keys, $preparsed);
									$context = [
										'tagIsOnOwnLine' => $__tagIsOnOwnLine,
									];

									if (isset($tag_info['callback']))
									{
										$pending_text = $this->{$tag_info['callback']}($open['data'], $open['option'], $context);
									}
									else if (!empty($tag_info['handlers']))
									{
										$thisUserContext = vB::getUserContext();
										$renderOptions = $this->getBbcodeRenderOptions();

										foreach ($tag_info['handlers'] AS $__class)
										{
											if (isset($this->bbcodeDataCache) AND !empty($this->nodeid))
											{
												/** @var vB_Interface_BbCode */
												$bbcode = $this->bbcodeDataCache->getBbcodeInstance($this->bbcodeHelper, $this->nodeid, $__class, $open['data'], $open['option']);
											}
											else
											{
												// TODO: edit & preview mode hit this. We may want Edit to use the existing cache...
												// get canonical classname
												$bbcodeClass = vB_BbCode::determineClassname($__class);
												if (empty($bbcodeClass))
												{
													// failed to find the relevant bbcode class, ignore this.
													continue;
												}
												/** @var vB_Interface_BbCode */
												$bbcode = $bbcodeClass::generateFreshFromDataAndOption($this->bbcodeHelper, $open['data'], $open['option']);
											}


											/*
											This canHandleBbCode() check is for the future when we may have multiple packages to handle the same "class"
											of bbcodes with different options.
											e.g. [video=youtube;...] is handled by one package, [video=tiktok;...] is handled by another, or
											[buy=shopify;...] by one package, [buy=amazon;...] by another.
											This is only possible by allowing 'handlers' to stack, due to the outer bbcode info array being
											grouped by "option" & "no_option" currently.
											*/
											if ($bbcode->canHandleBbCode($open['data'], $open['option']))
											{
												$bbcode->setRenderOptionsAndContext($renderOptions, $context);
												$bbcode->setUserContext($thisUserContext);
												// Seems like renderBbCode() doesn't even need the data & option passed in again, since we
												// could hypothetically set them during construction...
												//$pending_text = $bbcode->renderBbCode($open['data'], $open['option'], $thisUserContext);
												$pending_text = $bbcode->renderBbCode($open['data'], $open['option']);
												break;
											}
										}

										//this is a sheet anchor in case the above class(es) are either not found (should never happen but once did)
										//or the canHandleCheck fails for all of them.  In that case preserve the tag as text but parse the contents.
										if($pending_text ===  '')
										{
											$pending_text = $this->handle_unparsable($open['data']);
										}
									}

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
						if (!empty($tag_info['strip_space_after']) AND ($this->stripSpaceAfter OR !empty($tag_info['ignore_global_strip_space_after'])))
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

					unset($stack[$key]);
					--$stack_size;
					// it seems like we have to renumber the stack keys to ensure that '0' is always the "newest"
					// item for appending the data / pending_text below... but surely there's a better way to do this.
					$stack = array_values($stack); // this is a tricky way to renumber the stack's keys
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
				// we're probably doing array copies here due to the array modificiation... is there a better way?
				$stack[0]['data'] .= $pending_text;
			}
		}

		return $output;
	}

	/**
	 * Find the first instance of a tag in an array
	 *
	 * @param	string		Name of tag
	 * @param	array		Array to search
	 *
	 * @return	int/false	Array key of first instance; false if it does not exist
	 */
	protected function findFirstTag($tagName, &$stack)
	{
		foreach ($stack AS $key => $node)
		{
			if ($node['name'] == $tagName)
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
	protected function findLastTag($tag_name, &$stack)
	{
		/*
		foreach (array_reverse($stack, true) AS $key => $node)
		{
			if ($node['name'] == $tag_name)
			{
				return $key;
			}
		}
		return false;
		*/
		// Trying to avoid doing array_reverse over and over.
		// This will change the $stack's internal pointer..
		// https://stackoverflow.com/a/25769831
		for (end($stack); ($key = key($stack))!==null; prev($stack)){
			$node = current($stack);
			// ...
			if ($node['name'] == $tag_name)
			{
				return $key;
			}
		}

		return false;
	}

	// The handle functions haven't been renamed since they must have the same name as in core (see vB_Api_Bbcode::fetchTagList).

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
		$open = $this->currentTag;

		$has_option = $open['option'] !== false ? 'option' : 'no_option';
		$tag_info =& $this->tag_list["$has_option"]["$open[name]"];

		return $tag_info['external_callback']($this, $value, $option);
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
		$language = vB5_User::get('lang_options');
		$dir = ($language['direction'] ? 'left' : 'right');

		return '<div style="margin-' . $dir . ':' . $indent . 'px">' . $text . '</div>';
	}

	/**
	 * Handles an [email] tag. Creates a link to email an address.
	 *
	 * @param	string	If tag has option, the displayable email name. Else, the email address.
	 * @param	string	If tag has option, the email address.
	 *
	 * @return	string	HTML representation of the tag.
	 */
	protected function handle_bbcode_email($text, $link = '')
	{
		$rightlink = trim($link);
		if (empty($rightlink))
		{
			// no option -- use param
			$rightlink = trim($text);
		}
		$rightlink = str_replace(array('`', '"', "'", '['), array('&#96;', '&quot;', '&#39;', '&#91;'), $this->stripSmilies($rightlink));

		if (!trim($link) OR $text == $rightlink)
		{
			$tmp = vB5_String::unHtmlSpecialChars($text);
			if (vB5_String::vbStrlen($tmp) > 55)
			{
				$text = vB5_String::htmlSpecialCharsUni(vbchop($tmp, 36) . '...' . substr($tmp, -14));
			}
		}

		// remove double spaces -- fixes issues with wordwrap
		$rightlink = str_replace('  ', '', $rightlink);

		// email hyperlink (mailto:)
		if (vB5_String::isValidEmail($rightlink))
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
		// remove smilies from username
		$username = $this->stripSmilies($username);

		if (preg_match('/^(.+)(?<!&#[0-9]{3}|&#[0-9]{4}|&#[0-9]{5});\s*(n?\d+)\s*$/U', $username, $match))
		{
			$username = $match[1];
			$postid = $match[2];
		}
		else
		{
			$postid = 0;
		}

		$username = $this->doWordWrap($username);

		$show['username'] = iif($username != '', true, false);
		$message = $this->stripFrontBackWhitespace($message, 1);

		$template = $this->printable ? $this->quotePrintableTemplate : $this->quoteTemplate;
		$vars = array(
			'message'    => $message,
			'postid'     => $postid,
			'username'   => $username,
			'quote_vars' => $this->quoteVars,
			'show'       => $show,
		);

		if ($this->renderImmediate)
		{
			return vB5_Template::staticRender($template, $vars, false);
		}
		else
		{
			return vB5_Template_Runtime::includeTemplate($template, $vars);
		}
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

		$url = Api_InterfaceAbstract::instance()->callApi('route', 'fetchLegacyPostUrl', array($postId));

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

		$url = Api_InterfaceAbstract::instance()->callApi('route', 'fetchLegacyThreadUrl', array($threadId));

		if (!isset($text))
		{
			$text = $url;
		}
		$url = $this->escapeAttribute($url);

		// standard URL hyperlink
		return "<a href=\"$url\" target=\"_blank\">$text</a>";
	}

	/**
	 * Handles a [node] tag. Creates a link to a node.
	 *
	 * @param	string	If tag has option, the displayable name. Else, the threadid.
	 * @param	string	If tag has option, the threadid.
	 *
	 * @return	string	HTML representation of the tag.
	 */
	protected function handle_bbcode_node($text, $nodeId)
	{
		//we don't look up the node title here or use the direct node route information because there are permission
		//issues.  We cache the rendered post independant of user so we need to render things the same way for
		//every user.  Otherwise if a user causes a post to render with a node tag to a node they can't access then
		//everybody would see the link missing until the next user who could see it manages to render it.
		//Even if we decided to allow see titles in this context the underlying functions won't allow it (and if we
		//changed that it wouldn't be limited to this context).
		$nodeId = intval($nodeId);
		if (!$nodeId)
		{
			// no option -- use param
			$nodeId = intval($text);
			$text = '';
		}

		// fetch URL
		$nodeInfo = array('nodeid' => $nodeId);
		$url = vB5_Template_Runtime::buildUrl('node', $nodeInfo);
		vB5_Template_Url::instance()->replacePlaceholders($url);

		if (!$text)
		{
			$text = $url;
		}

		// standard URL hyperlink
		$link = '<a href="' . $url . '" target="_blank">' . $text . '</a>';

		return $link;
	}

	/**
	 * Handles a [USER] tag. Creates a link to the user profile
	 *
	 * @param	string	The username
	 * @param	string	The userid
	 *
	 * @return	string	HTML representation of the tag.
	 */
	protected function handle_bbcode_user($username = '', $userid = '')
	{
		$userid = (int) $userid;

		// the api methods handles guest displaynames
		['info' => $namecardInfo] = Api_InterfaceAbstract::instance()->callApi('User', 'getNamecardInfo', [$userid]);
		// User mention isn't quite ready for displayname yet.
		// todo: update this for displaynames once user autosuggest work is done
		// todo: Also update samples in css_b_bbcode_user template
		//$displayname_safe =  $namecardInfo['displayname_safe'];
		$displayname_safe = $namecardInfo['username'];
		$url = $namecardInfo['profileurl'];
		// Decided against prerendering the namecard, because the parsed post gets cached & user-relations & other userinfo
		// may update in the meanwhile, which is annoying.
		// Let's prefer ajax loading for this.

		// this implementation is used when rendering a post to display the thread

		// keep this markup in sync with the other 2 implementations of handle_bbcode_user()
		// and with autocompleteSelect() in ckeditor.js
		if ($url)
		{
			$vboptions = vB5_Template_Options::instance()->getOptions();
			$vboptions = $vboptions['options'];

			if ($vboptions['userbbcodeavatar'])
			{
				$avatar = $namecardInfo['avatar'];
				// todo: should this be cdn url?
				$avatarUrl = (!$avatar['isfullurl'] ? $vboptions['bburl'] . '/' : '')  . $avatar['avatarpath'];
				return "<a href=\"$url\" style=\"background-image:url('$avatarUrl');\" class=\"b-bbcode-user b-bbcode-user--has-avatar js-bbcode-user\" data-userid=\"$userid\" data-vbnamecard=\"$userid\">$displayname_safe</a>";
			}
			else
			{
				return "<a href=\"$url\" class=\"b-bbcode-user js-bbcode-user\" data-userid=\"$userid\" data-vbnamecard=\"$userid\">$displayname_safe</a>";
			}
		}
		else
		{
			return "<span class=\"b-bbcode-user js-bbcode-user\">$displayname_safe</span>";
		}
	}

	protected function handle_bbcode_hashtag($tagtext, $tagid)
	{
		$stringutil = Api_InterfaceAbstract::instance()->stringInstance();
		if($tagid[0] == 'c')
		{
			$nodeid = $stringutil->substr($tagid, 1);
			$url = vB5_Template_Runtime::buildUrl('node', ['nodeid' => $nodeid]);
			if (!$tagtext)
			{
				$tagtext = $url;
			}
		}
		else
		{
			//need to figure out a better way to handle routes in the bbcode parser.
			$url = vB5_Template_Runtime::buildUrl('search', [], ['searchJSON' => '{"tag":"' . $tagtext . '"}']);
		}

		vB5_Template_Url::instance()->replacePlaceholders($url);
		$safeurl = $stringutil->htmlspecialchars($url);
		return '<a href="' . $safeurl . '" class="b-bbcode b-bbcode__hashtag">' . $tagtext . '</a>';
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
		static $codefind1, $codereplace1, $codefind2, $codereplace2;

		$code = $this->stripFrontBackWhitespace($code, 1);

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
		$blockheight = $this->fetchBlockHeight($code); // fetch height of block element
		$code = str_replace($codefind2, $codereplace2, $code); // finish replacements

		// do we have an opening <? tag?
		if (!preg_match('#<\?#si', $code))
		{
			// if not, replace leading newlines and stuff in a <?php tag and a closing tag at the end
			$code = "<?php BEGIN__VBULLETIN__CODE__SNIPPET $code \r\nEND__VBULLETIN__CODE__SNIPPET ?" . ">";
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

		$template = $this->printable ? 'bbcode_php_printable' : 'bbcode_php';
		$vars = array(
			'blockheight' => $blockheight,
			'code' => $code,
		);

		if ($this->renderImmediate)
		{
			return vB5_Template::staticRender($template, $vars, false);
		}
		else
		{
			return vB5_Template_Runtime::includeTemplate($template, $vars);
		}
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
	protected function emulatePreTag($text)
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
	protected function handle_bbcode_video($url, $option)
	{
		$options = explode(';', $option);
		$provider = strtolower($options[0]);
		$code = $options[1];

		if (!$code OR !$provider)
		{
			return '[video=' . $option . ']' . $url . '[/video]';
		}

		$vars = [
			'url'      => $this->escapeAttribute($url),
			'provider' => $this->escapeAttribute($provider),
			'code'     => $this->escapeAttribute($code),
		];

		if ($this->renderImmediate)
		{
			return vB5_Template::staticRender('video_frame', $vars, false);
		}
		else
		{
			return vB5_Template_Runtime::includeTemplate('video_frame', $vars);
		}
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
		// remove unnecessary line breaks and escaped quotes
		$code = str_replace(array('<br>', '<br />'), array('', ''), $code);

		$code = $this->stripFrontBackWhitespace($code, 1);

		if ($this->printable)
		{
			$code = $this->emulatePreTag($code);
			$template = 'bbcode_code_printable';
		}
		else
		{
			$blockheight = $this->fetchBlockHeight($code);
			$template = 'bbcode_code';
		}

		$vars = array(
			'blockheight' => $blockheight,
			'code' => $code,
		);

		if ($this->renderImmediate)
		{
			return vB5_Template::staticRender($template, $vars, false);
		}
		else
		{
			return vB5_Template_Runtime::includeTemplate($template, $vars);
		}
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
		static $regexfind, $regexreplace;

		$code = $this->stripFrontBackWhitespace($code, 1);


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
			array($this, 'handleBbcodeHtmlTagPregMatchNoEscape'), $code);

		if ($this->options['do_html'])
		{
			$code = preg_replace_callback('#<((?>[^>"\']+?|"[^"]*"|\'[^\']*\')+)>#', // push code through the tag handler
				array($this, 'handleBbcodeHtmlTagPregMatch'), $code);
		}

		if ($this->printable)
		{
			$code = $this->emulatePreTag($code);
			$template = 'bbcode_html_printable';
		}
		else
		{
			$blockheight = $this->fetchBlockHeight($code);
			$template = 'bbcode_html';
		}

		$vars = array(
			'blockheight' => $blockheight,
			'code' => $code,
		);

		if ($this->renderImmediate)
		{
			return vB5_Template::staticRender($template, $vars, false);
		}
		else
		{
			return vB5_Template_Runtime::includeTemplate($template, $vars);
		}
	}

	/**
	 * Callback for preg_replace_callback, used by handle_bbcode_html
	 *
	 * @param	array	matches
	 *
	 * @return	string	the transformed value
	 */
	protected function handleBbcodeHtmlTagPregMatch($matches)
	{
		return $this->handle_bbcode_html_tag(vB5_String::htmlSpecialCharsUni($matches[1]));
	}

	/**
	 * Callback for preg_replace_callback, used by handle_bbcode_html
	 *
	 * @param	array	matches
	 *
	 * @return	string	the transformed value
	 */
	protected function handleBbcodeHtmlTagPregMatchNoEscape($matches)
	{
		return $this->handle_bbcode_html_tag($matches[1]);
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
			$bbcode_html_colors = $this->fetchBbcodeHtmlColors();
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

	/*
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
	* Call back to handle any tag that the WYSIWYG editor can't handle. This
	* parses the tag, but returns an unparsed version of it. The advantage of
	* this method is that any parsing directives (no parsing, no smilies, etc)
	* will still be applied to the text within.
	*
	* @param	string	Text inside the tag
	*
	* @return	string	The unparsed tag and the text within it
	*/
	protected function handle_unparsable($text)
	{
		$tag_name = ($this->currentTag['name_orig'] ?? $this->currentTag['name']);
		return '[' . $tag_name .
			($this->currentTag['option'] !== false ?
				('=' . $this->currentTag['delimiter'] . $this->currentTag['option'] . $this->currentTag['delimiter']) :
				''
			) . ']' . $text . '[/' . $tag_name . ']';
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
	 * @param	array	bbcode node context in the entire parsed list/tree
	 *
	 * @return	string	HTML representation of the tag.
	 */
	function handle_bbcode_url($text, $link, $context = [])
	{
		/*
		1) we need to be able to bulk fetch get all current page's nodes' bbcode data to avoid
		  unnecessary db hits.
		  At the very minimum each node's bbcode data should be bulk fetched.
		2) We should be able to assign the fetched data to each bbcode "handle" invocation.
		This means we need the parse array/stack to be able to map to the bbcode data records somehow
		*/
		$thisUserContext = vB::getUserContext();
		$renderOptions = $this->getBbcodeRenderOptions();

		if (isset($this->bbcodeDataCache) AND !empty($this->nodeid))
		{
			/** @var vB_BbCode_Url */
			$bbcode = $this->bbcodeDataCache->getBbcodeInstance($this->bbcodeHelper, $this->nodeid, 'url', $text, $link);
		}
		else
		{
			// TODO: edit & preview mode hit this. We may want Edit to use the existing cache...
			/** @var vB_BbCode_Url */
			$bbcode = vB_BbCode_Url::generateFreshFromDataAndOption($this->bbcodeHelper, $text, $link);
		}

		$bbcode->setRenderOptionsAndContext($renderOptions, $context);
		$bbcode->setUserContext($thisUserContext);
		$html = $bbcode->renderBbCode($text, $link);

		return $html;
	}

	// This code is mostly a duplicate of some parts of the URL bbcode code. It was split out so that
	// we could refactor the URL bbcode without affecting image bbcodes, but we should figure out a way
	// to collapse duplicate logic again.
	private function generateLinkForImage($text, $link)
	{
		$rightlink = trim($link);

		if (empty($rightlink))
		{
			// no option -- use param
			$rightlink = trim($text);
		}
		$rightlink = str_replace(array('`', '"', "'", '['), array('&#96;', '&quot;', '&#39;', '&#91;'), $this->stripSmilies($rightlink));

		// remove double spaces -- fixes issues with wordwrap
		$rightlink = str_replace('  ', '', $rightlink);

		if (!preg_match('#^[a-z0-9]+(?<!about|javascript|vbscript|data):#si', $rightlink))
		{
			// todo: prefer https, or is http safer still?
			$rightlink = "http://$rightlink";
		}

		if (!trim($link) OR str_replace('  ', '', $text) == $rightlink)
		{
			$tmp = vB5_String::unHtmlSpecialChars($rightlink);
			if (vB5_String::vbStrlen($tmp) > 55)
			{
				$text = vB5_String::htmlSpecialCharsUni(vB5_String::vbChop($tmp, 36) . '...' . substr($tmp, -14));
			}
			else
			{
				// under the 55 chars length, don't wordwrap this
				$text = str_replace('  ', '', $text);
			}
		}

		static $current_url, $current_host, $allowed = [];
		if (!isset($current_url))
		{
			$current_url = @vB5_String::parseUrl(self::$bbUrl);
		}
		$is_external = self::$urlNoFollow;

		if (self::$urlNoFollow)
		{
			if (!isset($current_host))
			{
				$current_host = preg_replace('#:(\d)+$#', '', self::$vBHttpHost);

				$allowed = preg_split('#\s+#', self::$urlNoFollowWhiteList, -1, PREG_SPLIT_NO_EMPTY);
				$allowed[] = preg_replace('#^www\.#i', '', $current_host);
				$allowed[] = preg_replace('#^www\.#i', '', $current_url['host']);
			}

			$target_url = preg_replace('#^([a-z0-9]+:(//)?)#', '', $rightlink);

			foreach ($allowed AS $host)
			{
				if (vB5_String::stripos($target_url, $host) !== false)
				{
					$is_external = false;
				}
			}
		}

		return ['link' => $rightlink, 'nofollow' => $is_external];
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
		return Api_InterfaceAbstract::instance()->urlInstance()->isSiteUrl($url);
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
		$currentUserid = vB5_User::get('userid');

		$attachmentid = false;
		$tempid = false;
		$filedataid = false;

		if (!empty($data['data-tempid']) AND strpos($data['data-tempid'], 'temp_') === 0)
		{
			// this attachment hasn't been saved yet (ex. going back & forth between source mode & wysiwyg on a new content)
			if (preg_match('#^temp_(\d+)_(\d+)_(\d+)$#', $data['data-tempid'], $matches))
			{
				// if the id is in the form temp_##_###_###, it's a temporary id that links to hidden inputs that contain
				// the stored settings that will be saved when it becomes a new attachment @ post save.
				$tempid = $this->escapeAttribute($matches[0]);
				$filedataid = intval($matches[1]);

				if (isset($this->filedatas[$filedataid]) AND !$this->filedatas[$filedataid]['isImage'])
				{
					// non image. Return as <a >
					$insertHtml = "<a class=\"bbcode-attachment\" href=\"" .
						"filedata/fetch?filedataid=$filedataid\" data-tempid=\"" . $tempid . "\" >"
						. vB5_Template_Phrase::instance()->register(array('attachment'))
						. "</a>";
					return $insertHtml;
				}
				else
				{
					// image. Return as <img >
					$settings = $this->processCustomImgConfig($data);
					$size = $this->getNearestImageSize($settings);

					// also replicated for the figure element in addCaption
					// Note, we don't want to double up the classes on BOTH figure & img (or else the plugin JS gets messier),
					// so we check to see if this will be added via caption later.
					$alignClass = '';
					if (empty($settings['all']['caption']) AND isset($settings['all']['data-align']))
					{
						switch ($settings['all']['data-align'])
						{
							case 'left':
							case 'center':
							case 'right':
								$alignClass = ' align_' . $settings['all']['data-align'];
								break;
							default:
								$alignClass = ' thumbnail';	// old behavior. Not sure if our css needs this for non-aligned but non-thumbnail images...
								break;
						}
					}
					// todo $alignClass is not used. Check missing code?


					// We're in a content entry state of some kind, with tempids.
					$imgbitsExtra = [];
					$imgbitsExtra['classes'] = ['js-need-data-att-update'];
					$link = "filedata/fetch?filedataid=$filedataid";
					// Disable lightbox for content entry as that might screw up the image2 dialog.
					$imgbitsExtra['lightbox'] = false;
					$insertHtml = $this->getImageHtml($settings, $link, $size, [], $imgbitsExtra);

					return $insertHtml;
				}
			}
		}
		else if (!empty($data['data-attachmentid']) AND is_numeric($data['data-attachmentid']))
		{
			// keep 'data-attachmentid' key in sync with text LIB's replaceAttachBbcodeTempids()
			$attachmentid = $data['data-attachmentid'];
			$filedataid = false;

			if (empty($this->attachments["$attachmentid"]))
			{
				/*
					This hack is here meant to allow QUOTE bbcode to allow rendering of attachments that's under another post, but it may add unintended
					"features," like allowing people to use [ATTACH] bbcodes with an attachmentid from another post and allowing it to render.
					Which is probably OK, as the image fetch calls are supposed to do their own checks.
					If this attach BBCode is referencing an attachment that's not been set by the caller, let's *try* to fetch the image and render it.
				 */
				$apiResult = Api_InterfaceAbstract::instance()->callApi('node', 'getAttachmentPublicInfo', array($attachmentid));
				if (!empty($apiResult[$attachmentid]))
				{
					// Skipping $this->setAttachments() and setting it directly, as this is not the current content node's attachment.
					$attachment = $apiResult[$attachmentid];
				}
				else
				{
					//if we get here there was an attachment once but there is no longer.  Let's not display anything
					//because it's going to be garbage. Most likely the JSON image information we store as part of the ATTACH=Json tag
					return '';
				}
			}
			else
			{
				$attachment =& $this->attachments["$attachmentid"];
			}

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
			// fetchCensoredText() call, but don't have time to verify this right now...
			$attachment['filename'] = vB5_String::fetchCensoredText(vB5_String::htmlSpecialCharsUni($attachment['filename']));
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
		$link = vB5_String::htmlSpecialCharsUni($link);

		if (!empty($attachment['filename']))
		{
			// attachment.filename is escaped by caller.
			$filename = $attachment['filename'];
			$linktext = $filename;
			if ($this->showAttachViews)
			{
				// todo: switch to 'image_x_y_z' for no larger version?
				$title = vB5_Template_Phrase::instance()->register([
					'image_larger_version_x_y_z',
					$filename,
					intval($attachment['counter']),
					$attachment['filesize_humanreadable'],
					$attachment['nodeid']
				]);
			}
			else
			{
				// todo: switch to 'image_name_size' for no larger version?
				$title = vB5_Template_Phrase::instance()->register([
					'image_larger_version_name_size_id',
					$filename,
					$attachment['filesize_humanreadable'],
					$attachment['nodeid']
				]);
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
			$params = [$attachment['extension'], $size];
			$isImage = Api_InterfaceAbstract::instance()->callApi('content_attach', 'isImage', $params);
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
			$currentUserid = vB5_User::get('userid');
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
		$imgbits['src'] = vB5_String::htmlSpecialCharsUni($link);

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
					$imgbits['alt'] = vB5_Template_Phrase::instance()->register([
						'image_larger_version_x_y_z',
						$attachment['filename'],
						intval($attachment['counter']),
						$attachment['filesize_humanreadable'],
						$attachment['nodeid']
					]);
				}
				else
				{
					$imgbits['alt'] = vB5_Template_Phrase::instance()->register([
						'image_larger_version_name_size_id',
						$attachment['filename'],
						$attachment['filesize_humanreadable'],
						$attachment['nodeid']
					]);
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
			$currentUserid = vB5_User::get('userid');
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
		$imgbits['data-fullsize-url'] = vB5_String::htmlSpecialCharsUni($link);
		$imgbits['data-thumb-url'] = vB5_String::htmlSpecialCharsUni($thumblink);
		// Per forum feedback, adding instructions on getting larger image, unless it seems like
		// we can't due to permissions.
		$imgbits['data-title'] = '';
		if (strpos($link, '&type=thumb') === false)
		{
			$imgbits['data-title'] = vB5_Template_Phrase::instance()->register(['image_click_original']);
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
		//$imgbits['data-title'] = vB5_String::htmlSpecialCharsUni($imgbits['data-title']);
		$imgbits['data-caption'] = vB5_String::htmlSpecialCharsUni($imgbits['data-caption']);
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
			$tag = vB5_String::htmlSpecialCharsUni($tag);
			$value = $this->escapeAttribute($value);
			$imgtag .= "$tag=\"$value\" ";
		}

		$itemprop = '';
		if(vB5_Template_Options::instance()->get('options.schemaenabled'))
		{
			$itemprop = 'itemprop="image"';
		}

		/*
			note: be careful about adding white space before & after this. In particular, image2's plugin code's isLinkedorStandaloneImage() check
			kind of fails due to expecting <img> being the only child of <a>, and the whitespace creates a text sibling node to <img>.
		 */
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
				$linkinfo = $this->generateLinkForImage('', $settings['all']['data-linkurl']);
				if ($linkinfo['link'] AND $linkinfo['link'] != 'http://')
				{
					$hrefbits['href'] = vB5_String::htmlSpecialCharsUni($linkinfo['link']);

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
				$hrefbits['href'] = vB5_String::htmlSpecialCharsUni($link);
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
				$tag = vB5_String::htmlSpecialCharsUni($tag);
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
		if (!empty($settings['all']['data-size']) AND $settings['all']['data-size']  != 'custom')
		{
			return $settings['all']['data-size'];
		}

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

		$api = Api_InterfaceAbstract::instance();
		$apiResult = $api->callApi('filedata', 'fetchBestFitGTE', array(max($width, $height)));
		return $apiResult['type'] ?? 'full';
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
				//$settings[$name] = vB5_String::htmlSpecialCharsUni($config_array[$name]); // todo: use this instead??
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
				$settings[$_key] = vB5_String::fetchCensoredText($settings[$_key]);
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
				$settings['data-size'] = Api_InterfaceAbstract::instance()->callApi('filedata', 'sanitizeFiletype', array($settings['data-size']));
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
				$tag = vB5_String::htmlSpecialCharsUni($tag);
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
		$showImages = (vB5_User::get('userid') == 0 OR vB5_User::get('showimages') OR $forceShowImages);

		/*
			attach & img2 bbcodes are handled via vB_Api_Bbcode::fetchTagList() but the legacy
			img bbcode has to be handled separately. We handle it here instead of in
			vB5_Template_NodeText because turning do_imgcode off instead triggers fallbacks
			to convert these to links, which we probably do not want.
		*/
		$allowedbbcodes = vB5_Template_Options::instance()->get('options.allowedbbcodes');
		// See vB_Api_Bbcode constants for bit values
		if (!($allowedbbcodes & 1024))
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

		// Prevent inline attachments from showing up in the attachments list
		//$this->skipAttachmentList = array(); // we set this in processAttachBbcode as well, so we can't blank this here.

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
					$this->oldAttachments = Api_InterfaceAbstract::instance()->callApi('filedata', 'fetchLegacyAttachments', array($legacyIds));

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
		/*
		$baseURL = vB5_Template_Options::instance()->get('options.frontendurl');
		$expectedPrefix = preg_quote($baseURL . '/filedata/fetch?filedataid=', '#');
		$regex = '#\[img\]\s*' . $expectedPrefix . '(?<filedataid>[0-9]+?)(?<extra>[&\#][^*\r\n]*|[a-z0-9/\\._\- !]*)\[/img\]#iU';
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
						$querystring = vB5_String::unHtmlSpecialChars($matches['querystring'][$key]);
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
				$callback = function($matches) {return $this->handleBbcodeImgMatch($matches[1]);};
			}
			else
			{
				$callback = function($matches) {return $this->handle_bbcode_url($matches[1], '');};
			}

			$bbcode = preg_replace_callback('#\[img\]\s*(https?://([^*\r\n]+|[a-z0-9/\\._\- !]+))\[/img\]#iU', $callback, $bbcode);
		}

		if ($has_img_code & self::BBCODE_HAS_RELPATH)
		{
			$bbcode = str_replace('[relpath][/relpath]', vB5_String::htmlSpecialCharsUni(vB5_Request::get('vBUrlClean')), $bbcode);
		}

		return $bbcode;
	}

	/**
	 * Callback for preg_replace_callback in handle_bbcode_img
	 */
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
			$attachment['filename'] = vB5_String::fetchCensoredText(vB5_String::htmlSpecialCharsUni($attachment['filename']));
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
					$size = Api_InterfaceAbstract::instance()->callApi('filedata', 'sanitizeFiletype', array($matches['settings']['size']));
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

			$title_text = !empty($settings['title']) ? vB5_String::htmlSpecialCharsUni($settings['title']) : '';
			$description_text = !empty($settings['description']) ? vB5_String::htmlSpecialCharsUni($settings['description']) : '';
			$title_text = vB5_String::fetchCensoredText($title_text);
			$description_text = vB5_String::fetchCensoredText($description_text);
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

			$alt_text = !empty($settings['description']) ? vB5_String::htmlSpecialCharsUni($settings['description']) : $title_text; // vB4 used to use description for alt text. vB5 seems to expect title for it, for some reason. Here's a compromise
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
				$imgbits['data-styles'] = vB5_String::htmlSpecialCharsUni($styles);
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
						. vB5_Template_Phrase::instance()->register(array('attachment'))
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
			return "<a href=\"filedata/fetch?filedataid=$attachmentid\">" . vB5_Template_Phrase::instance()->register(array('attachment')) . " </a>";
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
	function handleBbcodeImgMatch($link, $fullsize = false)
	{
		$link = $this->stripSmilies(str_replace('\\"', '"', $link));

		// remove double spaces -- fixes issues with wordwrap
		$link = str_replace(array('  ', '"'), '', $link);
		// skipping $this->escapeAttribute()

		$classes = 'bbcode-attachment';
		if ($this->doLightbox)
		{
			$classes .= ' bbcode-attachment--lightbox js-lightbox';
		}

		$itemprop = '';
		if(vB5_Template_Options::instance()->get('options.schemaenabled'))
		{
			$itemprop = 'itemprop="image"';
		}

		// todo, change class to bbcode-image? It's kind of misleading to use bbcode-attachment when this is for
		// [IMG] bbcodes, not [ATTACH] bbcodes. There might be css changes etc that might be required, and this isn't
		// causing problems at the moment, so leaving this alone for now.
		// .js-lightbox : Enable Lightbox, but we have no title or caption info for a simple img bbcode.
		return  '<img ' . $itemprop . ' class="' . $classes . '" src="' .  $link . '" border="0" alt="" />';
	}

	/**
	 * Returns true of provided $currentUserid has either cangetimgattachment or
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

	/**
	 * Appends the non-inline attachment UI to the passed $text
	 *
	 * @param	string	Text to append attachments
	 * @param	array	Attachment data
	 * @param	bool	Whether to show images
	 * @param	array	Array of nodeid => (nodeid, filedataid) attachments that should not be included in the attachment box.
	 */
	public function append_noninline_attachments($text, $attachments, $do_imgcode = false, $skiptheseattachments = array())
	{
		if (!empty($attachments))
		{
			$currentUserid = vB5_User::get('userid');
			$imagehandler = vB_Image::instance();
			$imgAttachments = [];
			$nonImgAttachments = [];
			$attach_url = 'filedata/fetch?id=';
			/*
				self::$viewAttachedImages
				0: No
				1: Thumbnails
				2: Fullsize only if one attach
				3: Fullsize
			 */
			$imageSize = self::$viewAttachedImages;
			$imgTagAllowed = ($imageSize > 0);

			foreach ($attachments AS $nodeid => &$attachment)
			{
				if (isset($skiptheseattachments[$nodeid]))
				{
					continue;
				}

				$permCheck = $this->checkImagePermissions2($currentUserid, $attachment['parentid']);
				$canViewImg = $permCheck['doImg'];
				$attach['thumbonly'] = false;
				// Special override for 'canseethumbnails'
				if (!$permCheck['canFull'] AND $imageSize > 0)
				{
					// They can only view thumbnails, because they only have 'canseethumbnails'
					$imageSize = 1;
					$attach['thumbonly'] = true;
				}

				$attachment['isImage'] = ($canViewImg AND $imagehandler->isImageExtension($attachment['extension']));
				$attachment['useImgTag'] = ($attachment['isImage'] AND $imgTagAllowed);
				$attachment['filesize'] = (!empty($attachment['filesize'])) ?
					vb_number_format($attachment['filesize'] , 1, true) : 0;

				/*
					Censor filename for usage in alt texts. Escaped via vb:var in template usage,
					NOT SAFE to use in raw HTML.
				 */
				$attachment['filename'] = vB5_String::fetchCensoredText($attachment['filename']);

				// We'll include this item in the slideshow as long as it's an image that this user can view,
				// even if it doesn't render as a thumbnail/image in the attachment list.
				// However note that we will NOT try to lightbox attachment links when the current user
				// lacks image view permissions altogether by grace of checking $canViewImg.
				if ($attachment['isImage'])
				{
					$attachment['doLightbox'] = $this->doLightbox;
					$link = $attach_url . $nodeid;
					// attachments are always going to be local URL that support the thumb param.
					$thumblink = $link . '&type=thumb';
					// Unrelated to the list display settings (viewAttachedImages), if this
					// channel restricts user to thumbnails only, slideshow images must also
					// be thumbnails (or else they'll be blank due to failed fetch)
					if (!$permCheck['canFull'])
					{
						$link = $thumblink;
					}
					// URLs escaped during template insert.
					$attachment['data-fullsize-url'] = $link;
					$attachment['data-thumb-url'] = $thumblink;
					/*
						I don't think there's UI to set titles for non-inline attachments,
						and certainly none for setting captions for such. Per demo feedback
						we do not want to use filename as the last slideshow caption fallback,
						so we have no slideshow caption.
						If this changes, see the note above in addLightboxDataToImgbits() about
						the double escaping requirement.
						Edit:
						If we use phrases for these, we have to double-escape the pre-phrase
						data, otherwise the phrase PLACEholders will get escaped & fail to be
						replaced properly.
						Edit 2:
						Currently 'image_click_original' phrase is hard-coded for title via
						the tempalte.
					 */
					$attachment['data-title'] = '';
					$attachment['data-caption'] = '';
				}

				if ($attachment['useImgTag'])
				{
					$attachment['urlsuffix'] = "&type=thumb";
					if ($imageSize > 1)
					{
						$attachment['urlsuffix'] = '';
						if ($imageSize === 2)
						{
							/*
							// Do not display rest of the images.
							$imageSize = 0;
							$imgTagAllowed = 0;
							*/
							// Display the rest of the images as THUMBNAILS
							$imageSize = 1;
						}
					}
					$imgAttachments[] = $attachment;
				}
				else
				{
					$nonImgAttachments[] = $attachment;
				}
			}

			// Show image attachments first, then nonImage
			$attachments = array_merge($imgAttachments, $nonImgAttachments);

			$vars = [
				'attachments' => $attachments,
				'attachurl' => $attach_url,
			];

			if ($this->renderImmediate)
			{
				$text .= vB5_Template::staticRender('bbcode_attachment_list', $vars, false);
			}
			else
			{
				$text .= vB5_Template_Runtime::includeTemplate('bbcode_attachment_list', $vars);
			}
		}

		return $text;
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
	public function stripFrontBackWhitespace($text, $max_amount = 1, $strip_front = true, $strip_back = true)
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
	protected function stripSmilies($text)
	{
		return $this->bbcodeHelper->stripSmilies($text);
	}

	/**
	 * Determines whether a string contains an [img] tag.
	 *
	 * @param	string	Text to search
	 *
	 * @return	bool	Whether the text contains an [img] tag
	 */
	protected function containsBbcodeImgTags($text)
	{
		// use a bitfield system to look for img, attach, and sigpic tags

		$hasimage = 0;
		if (vB5_String::stripos($text, '[/img]') !== false)
		{
			$hasimage += self::BBCODE_HAS_IMG;
		}

		if (vB5_String::stripos($text, '[/attach]') !== false)
		{
			$hasimage += self::BBCODE_HAS_ATTACH;
		}

		if (vB5_String::stripos($text, '[/relpath]') !== false)
		{
			$hasimage += self::BBCODE_HAS_RELPATH;
		}

		return $hasimage;
	}

	/**
	 * Returns the height of a block of text in pixels (assuming 16px per line).
	 * Limited by your "codemaxlines" setting (if > 0).
	 *
	 * @param	string	Block of text to find the height of
	 *
	 * @return	int		Number of lines
	 */
	protected function fetchBlockHeight($code)
	{
		$options = vB5_Template_Options::instance();
		$codeMaxLines = $options->get('options.codemaxlines');
		// establish a reasonable number for the line count in the code block
		$numlines = max(substr_count($code, "\n"), substr_count($code, "<br />")) + 1;

		// set a maximum number of lines...
		if ($numlines > $codeMaxLines AND $codeMaxLines > 0)
		{
			$numlines = $codeMaxLines;
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
	protected function fetchBbcodeHtmlColors()
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
	 * Sets the template to be used for generating quotes
	 *
	 * @param	string	the template name
	 */
	public function setQuoteTemplate($templateName)
	{
		$this->quoteTemplate = $templateName;
	}

	/**
	 * Sets the template to be used for generating quotes
	 *
	 * @param	string	the template name
	 */
	public function setQuotePrintableTemplate($template_name)
	{
		$this->quotePrintableTemplate = $template_name;
	}

	/**
	 * Sets variables to be passed to the quote template
	 *
	 * @param	string	the template name
	 */
	public function setQuoteVars($var_array)
	{
		$this->quoteVars = $var_array;
	}

	/**
	 * Fetches the table helper in use. It also acts as a lazy initializer.
	 * If no table helper has been explicitly set, it will instantiate
	 * the class's default.
	 *
	 * @return	vBForum_BBCodeHelper_Table	Table helper object
	 */
	public function getTableHelper()
	{
		if (!isset($this->tableHelper))
		{
			$this->tableHelper = new vB5_Template_BbCode_Table($this);
		}

		return $this->tableHelper;
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
	protected function parseTableTag($content, $params = '')
	{
		$helper = $this->getTableHelper();
		return $helper->parseTableTag($content, $params);
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

	// Unshared functions
	// This section is meant for functions specific to the frontend and not shared with vB_Library_Bbcode

	/**
	 * Chops a set of (fixed) BB code tokens to a specified length or slightly over.
	 * It will search for the first whitespace after the snippet length.
	 *
	 * @param	array	Fixed tokens
	 * @param	integer	Length of the text before parsing (optional)
	 *
	 * @return	array	Tokens, chopped to the right length.
	 */
	/*
	function make_snippet($tokens, $initial_length = 0)
	{
		// no snippet to make, or our original text was short enough
		if ($this->snippet_length == 0 OR ($initial_length AND $initial_length < $this->snippet_length))
		{
			return $tokens;
		}

		$counter = 0;
		$stack = array();
		$new = array();
		$over_threshold = false;

		foreach ($tokens AS $tokenid => $token)
		{
			// only count the length of text entries
			if ($token['type'] == 'text')
			{
				$length = vB5_String::vbStrlen($token['data']);

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
	 */
}

// ####################################################################

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 116251 $
|| #######################################################################
\*=========================================================================*/
