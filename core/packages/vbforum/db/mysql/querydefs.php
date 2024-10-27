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
class vBForum_dB_MYSQL_QueryDefs extends vB_dB_MYSQL_QueryDefs
{
	/**
	 * This class is called by the new vB_dB_Assertor database class
	 * It does the actual execution. See the vB_dB_Assertor class for more information
	 *
	 * $queryid can be either the id of a query from the dbqueries table, or the
	 * name of a table.
	 *
	 * if it is the name of a table , $params MUST include vB_dB_Query::TYPE_KEY of either update, insert, select, or delete.
	 *
	 * $params includes a list of parameters. Here's how it gets interpreted.
	 *
	 * If the queryid was the name of a table and type was "update", one of the params
	 * must be the primary key of the table. All the other parameters will be matched against
	 * the table field names, and appropriate fields will be updated. The return value will
	 * be false if an error is generated and true otherwise
	 *
	 * If the queryid was the name of a table and type was "delete", one of the params
	 * must be the primary key of the table. All the other parameters will be ignored
	 * The return value will be false if an error is generated and true otherwise
	 *
	 * If the queryid was the name of a table and type was "insert", all the parameters will be
	 * matched against the table field names, and appropriate fields will be set in the insert.
	 * The return value is the primary key of the inserted record.
	 *
	 * If the queryid was the name of a table and type was "select", all the parameters will be
	 * matched against the table field names, and appropriate fields will be part of the
	 * "where" clause of the select. The return value will be a vB_dB_Result object
	 * The return value is the primary key of the inserted record.
	 *
	 * If the queryid is the key of a record in the dbqueries table then each params
	 * value will be matched to the query. If there are missing parameters we will return false.
	 * If the query generates an error we return false, and otherwise we return either true,
	 * or an inserted id, or a recordset.
	 */

	/*Properties====================================================================*/

	//type-specific
	protected $db_type = 'MYSQL';

	protected static $permission_string = false;

	/**
	 * This is the definition for tables we will process through. It saves a
	 * database query to put them here.
	 */
	protected $table_data = [
		'adminhelp' => [
			'key' => 'adminhelpid',
			'structure' => [
				'action', 'adminhelpid', 'displayorder', 'optionname', 'product', 'script', 'volatile'
			],
			'forcetext' => ['action', 'optionname', 'product', 'script'],
		],
		'administrator' => [
			'key' => 'userid',
			'structure' => [
				'adminpermissions', 'cssprefs', 'dismissednews', 'languageid', 'navprefs', 'notes', 'userid'
			],
			'forcetext' => ['cssprefs', 'dismissednews', 'navprefs', 'notes'],
		],
		'adminlog' => [
			'key' => 'adminlogid',
			'structure' => [
				'action', 'adminlogid', 'dateline', 'extrainfo', 'ipaddress', 'script', 'userid'
			],
			'forcetext' => ['action', 'extrainfo', 'ipaddress', 'script'],
		],
		'apiclient_devicetoken' => [
			'key' => 'apiclientid',
			'structure' => ['apiclientid', 'devicetoken', 'userid'],
			'forcetext' => ['devicetoken'],
		],
		'attach' => [
			'key' => 'nodeid',
			'structure' => [
				'caption', 'counter', 'filedataid', 'filename', 'nodeid', 'posthash', 'reportthreadid',
				'settings', 'visible'
			],
			'forcetext' => ['caption', 'filename', 'posthash', 'settings'],
		],
		'attachmentpermission' => [
			'key' => 'attachmentpermissionid',
			'structure' => [
				'attachmentpermissionid', 'attachmentpermissions', 'extension', 'height', 'size',
				'usergroupid', 'width'
			],
			'forcetext' => ['extension'],
		],
		'attachmenttype' => [
			'key' => 'extension',
			'structure' => ['contenttypes', 'display', 'extension', 'height', 'mimetype', 'size', 'width'],
			'forcetext' => ['contenttypes', 'extension', 'mimetype'],
		],
		'autosavetext' => [
			'key' => ['nodeid', 'parentid', 'userid'],
			'structure' => ['dateline', 'nodeid', 'pagetext', 'parentid', 'userid'],
			'forcetext' => ['pagetext'],
		],
		'avatar' => [
			'key' => 'avatarid',
			'structure' => [
				'avatarid', 'avatarpath', 'displayorder', 'imagecategoryid', 'minimumposts', 'title'
			],
			'forcetext' => ['avatarpath', 'title'],
		],
		'channel' => [
			'key' => 'nodeid',
			'structure' => [
				'category', 'daysprune', 'defaultsortfield', 'defaultsortorder', 'filedataid', 'guid',
				'imageprefix', 'newcontentemail', 'nodeid', 'options', 'product', 'styleid',
				'topicexpireseconds', 'topicexpiretype'
			],
			'forcetext' => [
				'defaultsortfield', 'defaultsortorder', 'guid', 'imageprefix', 'newcontentemail', 'product',
				'topicexpiretype'
			],
		],
		'channelprefixset' => [
			'key' => ['nodeid', 'prefixsetid'],
			'structure' => ['nodeid', 'prefixsetid'],
			'forcetext' => ['prefixsetid'],
		],
		'closure' => [
			'key' => ['parent', 'child'],
			'structure' => ['child', 'depth', 'displayorder', 'parent', 'publishdate'],
		],
		'contenttype' => [
			'key' => 'contenttypeid',
			'structure' => [
				'canattach', 'canplace', 'cansearch', 'cantag', 'class', 'contenttypeid', 'isaggregator',
				'packageid'
			],
			'forcetext' => ['canattach', 'canplace', 'cansearch', 'cantag', 'isaggregator'],
		],
		'cronlog' => [
			'key' => 'cronlogid',
			'structure' => ['cronlogid', 'dateline', 'description', 'type', 'varname'],
			'forcetext' => ['description', 'varname'],
		],
		'customavatar' => [
			'key' => 'userid',
			'structure' => [
				'dateline', 'extension', 'filedata', 'filedata_thumb', 'filename', 'filesize', 'height',
				'height_thumb', 'userid', 'visible', 'width', 'width_thumb'
			],
			'forcetext' => ['extension', 'filename'],
		],
		'customprofile' => [
			'key' => 'customprofileid',
			'structure' => [
				'button_background_color', 'button_background_image', 'button_background_repeat',
				'button_border', 'button_text_color', 'content_background_color',
				'content_background_image', 'content_background_repeat', 'content_border',
				'content_link_color', 'content_text_color', 'customprofileid', 'font_family', 'fontsize',
				'headers_background_color', 'headers_background_image', 'headers_background_repeat',
				'headers_border', 'headers_link_color', 'headers_text_color', 'module_background_color',
				'module_background_image', 'module_background_repeat', 'module_border', 'module_link_color',
				'module_text_color', 'moduleinactive_background_color', 'moduleinactive_background_image',
				'moduleinactive_background_repeat', 'moduleinactive_border', 'moduleinactive_link_color',
				'moduleinactive_text_color', 'page_background_color', 'page_background_image',
				'page_background_repeat', 'page_link_color', 'themeid', 'thumbnail', 'title',
				'title_text_color', 'userid'
			],
			'forcetext' => [
				'button_background_color', 'button_background_image', 'button_background_repeat',
				'button_border', 'button_text_color', 'content_background_color',
				'content_background_image', 'content_background_repeat', 'content_border',
				'content_link_color', 'content_text_color', 'font_family', 'fontsize',
				'headers_background_color', 'headers_background_image', 'headers_background_repeat',
				'headers_border', 'headers_link_color', 'headers_text_color', 'module_background_color',
				'module_background_image', 'module_background_repeat', 'module_border', 'module_link_color',
				'module_text_color', 'moduleinactive_background_color', 'moduleinactive_background_image',
				'moduleinactive_background_repeat', 'moduleinactive_border', 'moduleinactive_link_color',
				'moduleinactive_text_color', 'page_background_color', 'page_background_image',
				'page_background_repeat', 'page_link_color', 'thumbnail', 'title', 'title_text_color'
			],
		],
		'event' => [
			'key' => 'nodeid',
			'structure' => [
				'allday', 'eventenddate', 'eventhighlightid', 'eventstartdate', 'ignoredst', 'location',
				'maplocation', 'nodeid'
			],
			'forcetext' => ['location', 'maplocation'],
		],
		'eventhighlight' => [
			'key' => 'eventhighlightid',
			'structure' => [
				'backgroundcolor', 'denybydefault', 'displayorder', 'eventhighlightid', 'textcolor'
			],
			'forcetext' => ['backgroundcolor', 'textcolor'],
		],
		'eventhighlightpermission' => [
			'key' => ['eventhighlightid', 'usergroupid'],
			'structure' => ['eventhighlightid', 'usergroupid'],
		],
		'faq' => [
			'key' => 'faqname',
			'structure' => ['displayorder', 'faqname', 'faqparent', 'product', 'volatile'],
			'forcetext' => ['faqname', 'faqparent', 'product'],
		],
		'fcmessage' => [
			'key' => 'messageid',
			'structure' => ['message_data', 'message_hash', 'messageid'],
			'forcetext' => ['message_data', 'message_hash'],
		],
		'fcmessage_offload' => [
			'key' => 'hash',
			'structure' => ['hash', 'message_data', 'recipientids', 'removeafter'],
			'forcetext' => ['hash', 'message_data', 'recipientids'],
		],
		'fcmessage_queue' => [
			'key' => ['recipient_apiclientid', 'messageid'],
			'structure' => [
				'messageid', 'recipient_apiclientid', 'retries', 'retryafter', 'retryafterheader', 'status'
			],
			'forcetext' => ['status'],
		],
		'filedata' => [
			'key' => 'filedataid',
			'structure' => [
				'dateline', 'extension', 'filedata', 'filedataid', 'filehash', 'filesize', 'height',
				'publicview', 'refcount', 'userid', 'width'
			],
			'forcetext' => ['extension', 'filehash'],
		],
		'filedataresize' => [
			'key' => ['filedataid', 'resize_type'],
			'structure' => [
				'filedataid', 'reload', 'resize_dateline', 'resize_filedata', 'resize_filesize',
				'resize_height', 'resize_type', 'resize_width'
			],
			'forcetext' => ['resize_type'],
		],
		'forumpermission' => [
			'key' => 'forumpermissionid',
			'structure' => ['forumid', 'forumpermissionid', 'forumpermissions', 'usergroupid'],
		],
		'gallery' => [
			'key' => 'nodeid',
			'structure' => ['caption', 'nodeid'],
			'forcetext' => ['caption'],
		],
		'groupintopic' => [
			'key' => ['userid', 'groupid', 'nodeid'],
			'structure' => ['groupid', 'nodeid', 'userid'],
		],
		'hvanswer' => [
			'key' => 'answerid',
			'structure' => ['answer', 'answerid', 'dateline', 'questionid'],
			'forcetext' => ['answer'],
		],
		'hvquestion' => [
			'key' => 'questionid',
			'structure' => ['dateline', 'questionid', 'regex'],
			'forcetext' => ['regex'],
		],
		'icon' => [
			'key' => 'iconid',
			'structure' => ['displayorder', 'iconid', 'iconpath', 'imagecategoryid', 'title'],
			'forcetext' => ['iconpath', 'title'],
		],
		'imagecategory' => [
			'key' => 'imagecategoryid',
			'structure' => ['displayorder', 'imagecategoryid', 'imagetype', 'title'],
			'forcetext' => ['title'],
		],
		'imagecategorypermission' => [
			'key' => '',
			'structure' => ['imagecategoryid', 'usergroupid'],
		],
		'infraction' => [
			'key' => 'nodeid',
			'structure' => [
				'action', 'actiondateline', 'actionreason', 'actionuserid', 'customreason', 'expires',
				'infractednodeid', 'infracteduserid', 'infractionlevelid', 'nodeid', 'note', 'points',
				'reputation_penalty'
			],
			'forcetext' => ['actionreason', 'customreason', 'note'],
		],
		'link' => [
			'key' => 'nodeid',
			'structure' => ['filedataid', 'meta', 'nodeid', 'url', 'url_title'],
			'forcetext' => ['meta', 'url', 'url_title'],
		],
		'loginlibrary' => [
			'key' => 'loginlibraryid',
			'structure' => ['class', 'loginlibraryid', 'productid'],
			'forcetext' => ['class', 'productid'],
		],
		'messagefolder' => [
			'key' => 'folderid',
			'structure' => ['folderid', 'oldfolderid', 'title', 'titlephrase', 'userid'],
			'forcetext' => ['title', 'titlephrase'],
		],
		'moderator' => [
			'key' => 'moderatorid',
			'structure' => ['moderatorid', 'nodeid', 'permissions', 'permissions2', 'userid'],
		],
		'node' => [
			'key' => 'nodeid',
			'structure' => [
				'CRC32', 'answer_set_by_user', 'answer_set_time', 'approved', 'authorname', 'commentperms',
				'contenttypeid', 'created', 'deletereason', 'deleteuserid', 'description', 'displayorder',
				'featured', 'groupid', 'hasanswer', 'hasphoto', 'hasvideo', 'htmltitle', 'iconid', 'inlist',
				'ipaddress', 'isanswer', 'lastauthorid', 'lastcontent', 'lastcontentauthor',
				'lastcontentid', 'lastprefixid', 'lastupdate', 'nextupdate', 'nodeid', 'nodeoptions',
				'oldcontenttypeid', 'oldid', 'open', 'parentid', 'prefixid', 'protected', 'public_preview',
				'publishdate', 'routeid', 'setfor', 'showapproved', 'showopen', 'showpublished', 'starter',
				'sticky', 'taglist', 'textcount', 'textunpubcount', 'title', 'totalcount',
				'totalunpubcount', 'unpublishdate', 'urlident', 'userid', 'viewperms', 'votes'
			],
			'forcetext' => [
				'CRC32', 'authorname', 'deletereason', 'description', 'htmltitle', 'ipaddress',
				'lastcontentauthor', 'lastprefixid', 'prefixid', 'taglist', 'title', 'urlident'
			],
		],
		'nodefield' => [
			'key' => 'nodefieldid',
			'structure' => [
				'displayorder', 'name', 'nodefieldcategoryid', 'nodefieldid', 'required', 'type'
			],
			'forcetext' => ['name', 'type'],
		],
		'nodefieldcategory' => [
			'key' => 'nodefieldcategoryid',
			'structure' => ['displayorder', 'name', 'nodefieldcategoryid'],
			'forcetext' => ['name'],
		],
		'nodefieldcategorychannel' => [
			'key' => ['nodeid', 'nodefieldcategoryid'],
			'structure' => ['nodefieldcategoryid', 'nodeid'],
		],
		'nodefieldvalue' => [
			'key' => 'nodefieldvalueid',
			'structure' => ['nodefieldid', 'nodefieldvalueid', 'nodeid', 'value'],
			'forcetext' => ['value'],
		],
		'nodehash' => [
			'key' => '',
			'structure' => ['dateline', 'dupehash', 'nodeid', 'userid'],
			'forcetext' => ['dupehash'],
		],
		'noderead' => [
			'key' => ['userid', 'nodeid'],
			'structure' => ['nodeid', 'readtime', 'userid'],
		],
		'nodeview' => [
			'key' => 'nodeid',
			'structure' => ['count', 'nodeid'],
		],
		'notice' => [
			'key' => 'noticeid',
			'structure' => [
				'active', 'dismissible', 'displayorder', 'noticeid', 'noticeoptions', 'persistent', 'title'
			],
			'forcetext' => ['title'],
		],
		'noticecriteria' => [
			'key' => ['noticeid', 'criteriaid'],
			'structure' => ['condition1', 'condition2', 'condition3', 'criteriaid', 'noticeid'],
			'forcetext' => ['condition1', 'condition2', 'condition3', 'criteriaid'],
		],
		'noticedismissed' => [
			'key' => ['noticeid', 'userid'],
			'structure' => ['noticeid', 'userid'],
		],
		'notification' => [
			'key' => 'notificationid',
			'structure' => [
				'customdata', 'lastreadtime', 'lastsenttime', 'lookupid', 'lookupid_hashed',
				'notificationid', 'recipient', 'sender', 'sentbynodeid', 'typeid'
			],
			'forcetext' => ['customdata', 'lookupid', 'lookupid_hashed'],
		],
		'notificationevent' => [
			'key' => 'eventname',
			'structure' => ['classes', 'eventname'],
			'forcetext' => ['classes', 'eventname'],
		],
		'notificationtype' => [
			'key' => 'typeid',
			'structure' => ['class', 'typeid', 'typename'],
			'forcetext' => ['class', 'typename'],
		],
		'page' => [
			'key' => 'pageid',
			'structure' => [
				'displayorder', 'guid', 'metadescription', 'moderatorid', 'pageid', 'pagetemplateid',
				'pagetype', 'parentid', 'product', 'routeid', 'title'
			],
			'forcetext' => ['guid', 'metadescription', 'pagetype', 'product', 'title'],
		],
		'paymentapi' => [
			'key' => 'paymentapiid',
			'structure' => [
				'active', 'classname', 'currency', 'paymentapiid', 'recurring', 'settings', 'subsettings',
				'title'
			],
			'forcetext' => ['classname', 'currency', 'settings', 'subsettings', 'title'],
		],
		'paymentapi_remote_catalog' => [
			'key' => ['paymentapiid', 'vbsubscriptionid', 'vbsubscription_subid', 'type', 'currency'],
			'structure' => [
				'active', 'currency', 'data', 'paymentapiid', 'remotecatalogid', 'type',
				'vbsubscription_subid', 'vbsubscriptionid'
			],
			'forcetext' => ['currency', 'data', 'remotecatalogid', 'type'],
		],
		'paymentapi_remote_invoice_map' => [
			'key' => '',
			'structure' => ['hash', 'paymentapiid', 'remotesubscriptionid'],
			'forcetext' => ['hash', 'remotesubscriptionid'],
		],
		'paymentapi_remote_orderid' => [
			'key' => ['remoteorderid', 'paymentapiid'],
			'structure' => ['hash', 'paymentapiid', 'recurring', 'remoteorderid'],
			'forcetext' => ['hash', 'remoteorderid'],
		],
		'paymentapi_subscription' => [
			'key' => ['paymentapiid', 'vbsubscriptionid', 'userid', 'paymentsubid'],
			'structure' => ['active', 'paymentapiid', 'paymentsubid', 'userid', 'vbsubscriptionid'],
			'forcetext' => ['paymentsubid'],
		],
		'paymentinfo' => [
			'key' => 'paymentinfoid',
			'structure' => [
				'completed', 'hash', 'paymentinfoid', 'subscriptionid', 'subscriptionsubid', 'userid'
			],
			'forcetext' => ['hash'],
		],
		'paymenttransaction' => [
			'key' => 'paymenttransactionid',
			'structure' => [
				'amount', 'currency', 'dateline', 'paymentapiid', 'paymentinfoid', 'paymenttransactionid',
				'request', 'reversed', 'state', 'transactionid'
			],
			'forcetext' => ['currency', 'request', 'transactionid'],
		],
		'permission' => [
			'key' => 'permissionid',
			'structure' => [
				'channeliconmaxsize', 'createpermissions', 'edit_time', 'forumpermissions',
				'forumpermissions2', 'groupid', 'maxattachments', 'maxchannels', 'maxothertags',
				'maxstartertags', 'maxtags', 'moderatorpermissions', 'nodeid', 'permissionid'
			],
		],
		'photo' => [
			'key' => 'nodeid',
			'structure' => ['caption', 'filedataid', 'height', 'nodeid', 'style', 'width'],
			'forcetext' => ['caption', 'style'],
		],
		'phrase' => [
			'key' => 'phraseid',
			'structure' => [
				'dateline', 'fieldname', 'languageid', 'phraseid', 'product', 'text', 'username', 'varname',
				'version'
			],
			'forcetext' => ['fieldname', 'product', 'text', 'username', 'varname', 'version'],
		],
		'poll' => [
			'key' => 'nodeid',
			'structure' => [
				'active', 'lastvote', 'multiple', 'nodeid', 'numberoptions', 'options', 'public', 'timeout',
				'votes'
			],
			'forcetext' => ['options'],
		],
		'polloption' => [
			'key' => 'polloptionid',
			'structure' => ['nodeid', 'polloptionid', 'title', 'voters', 'votes'],
			'forcetext' => ['title', 'voters'],
		],
		'pollvote' => [
			'key' => 'pollvoteid',
			'structure' => [
				'nodeid', 'pollid', 'polloptionid', 'pollvoteid', 'userid', 'votedate', 'voteoption'
			],
		],
		'postedithistory' => [
			'key' => 'postedithistoryid',
			'structure' => [
				'dateline', 'iconid', 'nodeid', 'original', 'pagetext', 'postedithistoryid', 'postid',
				'reason', 'title', 'userid', 'username'
			],
			'forcetext' => ['pagetext', 'reason', 'title', 'username'],
		],
		'prefix' => [
			'key' => 'prefixid',
			'structure' => ['displayorder', 'options', 'prefixid', 'prefixsetid'],
			'forcetext' => ['prefixid', 'prefixsetid'],
		],
		'prefixpermission' => [
			'key' => '',
			'structure' => ['prefixid', 'usergroupid'],
			'forcetext' => ['prefixid'],
		],
		'prefixset' => [
			'key' => 'prefixsetid',
			'structure' => ['displayorder', 'prefixsetid'],
			'forcetext' => ['prefixsetid'],
		],
		'privatemessage' => [
			'key' => 'nodeid',
			'structure' => ['about', 'aboutid', 'deleted', 'msgtype', 'nodeid'],
			'forcetext' => ['about', 'msgtype'],
		],
		'profilefield' => [
			'key' => 'profilefieldid',
			'structure' => [
				'data', 'def', 'displayorder', 'editable', 'form', 'height', 'hidden', 'html', 'maxlength',
				'memberlist', 'optional', 'perline', 'profilefieldcategoryid', 'profilefieldid', 'regex',
				'required', 'searchable', 'showonpost', 'size', 'type'
			],
			'forcetext' => ['data', 'regex', 'type'],
		],
		'profilefieldcategory' => [
			'key' => 'profilefieldcategoryid',
			'structure' => ['allowprivacy', 'displayorder', 'location', 'profilefieldcategoryid'],
			'forcetext' => ['location'],
		],
		'ranks' => [
			'key' => 'rankid',
			'structure' => [
				'active', 'display', 'grouping', 'minposts', 'priority', 'rankid', 'rankimg', 'ranklevel',
				'registrationtime', 'reputation', 'stack', 'startedtopics', 'totallikes', 'type',
				'usergroupid'
			],
			'forcetext' => ['grouping', 'rankimg'],
		],
		'redirect' => [
			'key' => 'nodeid',
			'structure' => ['nodeid', 'tonodeid'],
		],
		'report' => [
			'key' => 'nodeid',
			'structure' => ['closed', 'nodeid', 'reportnodeid'],
		],
		'reputation' => [
			'key' => 'reputationid',
			'structure' => [
				'dateline', 'nodeid', 'reason', 'reputation', 'reputationid', 'userid', 'whoadded'
			],
			'forcetext' => ['reason'],
		],
		'rssfeed' => [
			'key' => 'rssfeedid',
			'structure' => [
				'bodytemplate', 'iconid', 'itemtype', 'lastrun', 'maxresults', 'nodeid', 'options', 'port',
				'prefixid', 'rssfeedid', 'searchwords', 'title', 'titletemplate', 'topicactiondelay', 'ttl',
				'url', 'userid'
			],
			'forcetext' => [
				'bodytemplate', 'itemtype', 'prefixid', 'searchwords', 'title', 'titletemplate', 'url'
			],
		],
		'rsslog' => [
			'key' => ['rssfeedid', 'itemid', 'itemtype'],
			'structure' => [
				'contenthash', 'dateline', 'itemid', 'itemtype', 'rssfeedid', 'topicactioncomplete',
				'topicactiontime', 'uniquehash'
			],
			'forcetext' => ['contenthash', 'itemtype', 'uniquehash'],
		],
		'sentto' => [
			'key' => ['nodeid', 'userid', 'folderid'],
			'structure' => ['deleted', 'folderid', 'msgread', 'nodeid', 'userid'],
		],
		'sessionauth' => [
			'key' => ['sessionhash', 'loginlibraryid'],
			'structure' => [
				'additional_params', 'expires', 'loginlibraryid', 'sessionhash', 'token', 'token_secret'
			],
			'forcetext' => ['additional_params', 'sessionhash', 'token', 'token_secret'],
		],
		'sigpic' => [
			'key' => 'userid',
			'structure' => ['filedataid', 'userid'],
		],
		'site' => [
			'key' => 'siteid',
			'structure' => ['footernavbar', 'headernavbar', 'siteid', 'title'],
			'forcetext' => ['footernavbar', 'headernavbar', 'title'],
		],
		'smilie' => [
			'key' => 'smilieid',
			'structure' => [
				'displayorder', 'imagecategoryid', 'smilieid', 'smiliepath', 'smilietext', 'title'
			],
			'forcetext' => ['smiliepath', 'smilietext', 'title'],
		],
		'strikes' => [
			'key' => '',
			'structure' => ['ip_1', 'ip_2', 'ip_3', 'ip_4', 'strikeip', 'striketime', 'username'],
			'forcetext' => ['strikeip', 'username'],
		],
		'style' => [
			'key' => 'styleid',
			'structure' => [
				'dateline', 'displayorder', 'editorstyles', 'filedataid', 'guid', 'newstylevars',
				'parentid', 'parentlist', 'previewfiledataid', 'replacements', 'styleattributes', 'styleid',
				'templatelist', 'title', 'userselect'
			],
			'forcetext' => [
				'editorstyles', 'guid', 'newstylevars', 'parentlist', 'replacements', 'templatelist',
				'title'
			],
		],
		'stylevar' => [
			'key' => ['stylevarid', 'styleid'],
			'structure' => ['dateline', 'styleid', 'stylevarid', 'username', 'value'],
			'forcetext' => ['stylevarid', 'username'],
		],
		'stylevardfn' => [
			'key' => 'stylevarid',
			'structure' => [
				'datatype', 'failsafe', 'parentid', 'parentlist', 'product', 'styleid', 'stylevargroup',
				'stylevarid', 'uneditable', 'units', 'validation'
			],
			'forcetext' => [
				'datatype', 'parentlist', 'product', 'stylevargroup', 'stylevarid', 'units', 'validation'
			],
		],
		'subscribediscussion' => [
			'key' => 'subscribediscussionid',
			'structure' => [
				'discussionid', 'emailupdate', 'oldid', 'oldtypeid', 'subscribediscussionid', 'userid'
			],
		],
		'subscription' => [
			'key' => 'subscriptionid',
			'structure' => [
				'active', 'adminoptions', 'cost', 'displayorder', 'forums', 'membergroupids', 'newoptions',
				'nusergroupid', 'options', 'subscriptionid', 'varname'
			],
			'forcetext' => ['cost', 'forums', 'membergroupids', 'newoptions', 'varname'],
		],
		'subscriptionlog' => [
			'key' => 'subscriptionlogid',
			'structure' => [
				'expirydate', 'pusergroupid', 'regdate', 'status', 'subscriptionid', 'subscriptionlogid',
				'userid'
			],
		],
		'subscriptionpermission' => [
			'key' => 'subscriptionpermissionid',
			'structure' => ['subscriptionid', 'subscriptionpermissionid', 'usergroupid'],
		],
		'tag' => [
			'key' => 'tagid',
			'structure' => ['canonicaltagid', 'dateline', 'tagid', 'tagtext'],
			'forcetext' => ['tagtext'],
		],
		'tagnode' => [
			'key' => ['tagid', 'nodeid'],
			'structure' => ['dateline', 'nodeid', 'tagid', 'userid'],
		],
		'tagsearch' => [
			'key' => '',
			'structure' => ['dateline', 'tagid'],
		],
		'text' => [
			'key' => 'nodeid',
			'structure' => [
				'allowsmilie', 'attach', 'htmlstate', 'imageheight', 'imagewidth', 'infraction',
				'moderated', 'nodeid', 'pagetext', 'pagetextimages', 'previewimage', 'previewtext',
				'previewvideo', 'rawtext', 'showsignature'
			],
			'forcetext' => [
				'htmlstate', 'pagetext', 'pagetextimages', 'previewimage', 'previewtext', 'previewvideo',
				'rawtext'
			],
		],
		'thread_post' => [
			'key' => 'nodeid',
			'structure' => ['nodeid', 'postid', 'threadid'],
		],
		'trending' => [
			'key' => 'nodeid',
			'structure' => ['nodeid', 'weight'],
		],
		'useractivation' => [
			'key' => 'useractivationid',
			'structure' => [
				'activationid', 'dateline', 'emailchange', 'reset_attempts', 'reset_locked_since', 'type',
				'useractivationid', 'usergroupid', 'userid'
			],
			'forcetext' => ['activationid'],
		],
		'userauth' => [
			'key' => ['userid', 'loginlibraryid'],
			'structure' => [
				'additional_params', 'external_userid', 'loginlibraryid', 'token', 'token_secret', 'userid'
			],
			'forcetext' => ['additional_params', 'external_userid', 'token', 'token_secret'],
		],
		'userban' => [
			'key' => 'userid',
			'structure' => [
				'adminid', 'bandate', 'customtitle', 'displaygroupid', 'liftdate', 'reason', 'usergroupid',
				'userid', 'usertitle'
			],
			'forcetext' => ['reason', 'usertitle'],
		],
		'userfield' => [
			'key' => 'userid',
			'structure' => ['field1', 'field2', 'field3', 'field4', 'temp', 'userid'],
			'forcetext' => ['field1', 'field2', 'field3', 'field4', 'temp'],
		],
		'usergroup' => [
			'key' => 'usergroupid',
			'structure' => [
				'adminpermissions', 'albummaxpics', 'albummaxsize', 'albumpermissions', 'albumpicmaxheight',
				'albumpicmaxwidth', 'attachlimit', 'avatarmaxheight', 'avatarmaxsize', 'avatarmaxwidth',
				'canoverride', 'closetag', 'description', 'forumpermissions', 'forumpermissions2',
				'genericoptions', 'genericpermissions', 'genericpermissions2', 'groupiconmaxsize',
				'maximumsocialgroups', 'opentag', 'passwordexpires', 'passwordhistory', 'pmpermissions',
				'pmquota', 'pmsendmax', 'pmthrottlequantity', 'sigmaxchars', 'sigmaximages', 'sigmaxlines',
				'sigmaxrawchars', 'sigmaxsizebbcode', 'signaturepermissions', 'sigpicmaxheight',
				'sigpicmaxsize', 'sigpicmaxwidth', 'socialgrouppermissions', 'systemgroupid', 'title',
				'usercsspermissions', 'usergroupid', 'usertitle', 'visitormessagepermissions',
				'wolpermissions'
			],
			'forcetext' => ['closetag', 'description', 'opentag', 'title', 'usertitle'],
		],
		'userreferral' => [
			'key' => 'userreferralid',
			'structure' => ['dateline', 'referralcode', 'userid', 'userreferralid'],
			'forcetext' => ['referralcode'],
		],
		'usertextfield' => [
			'key' => 'userid',
			'structure' => [
				'buddylist', 'ignorelist', 'pmfolders', 'rank', 'signature', 'status', 'subfolders',
				'userid'
			],
			'forcetext' => [
				'buddylist', 'ignorelist', 'pmfolders', 'rank', 'signature', 'status', 'subfolders'
			],
		],
		'video' => [
			'key' => 'nodeid',
			'structure' => ['meta', 'nodeid', 'thumbnail', 'thumbnail_date', 'url', 'url_title'],
			'forcetext' => ['meta', 'thumbnail', 'url', 'url_title'],
		],
		'videoitem' => [
			'key' => 'videoitemid',
			'structure' => ['code', 'nodeid', 'provider', 'url', 'videoitemid'],
			'forcetext' => ['code', 'provider', 'url'],
		],
	];

	/*
	 * This is the definition for queries.
	 */
	protected $query_data = [
		'getParents' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' =>  "
				SELECT cl.*, node.nodeid, node.userid, node.parentid, node.routeid, node.title, node.urlident,
					node.contenttypeid, node.publishdate, node.unpublishdate, node.showpublished, node.starter,
					node.lastcontentid
				FROM {TABLE_PREFIX}closure AS cl
					INNER JOIN {TABLE_PREFIX}node AS node ON node.nodeid = cl.parent
				WHERE cl.child IN ({nodeid})
				ORDER by cl.child ASC, cl.depth ASC
			"
		],

		'getChildren' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' =>  "
				SELECT cl.*, node.nodeid, node.userid, node.parentid, node.routeid, node.title, node.urlident,
					node.contenttypeid, node.publishdate, node.unpublishdate, node.showpublished, node.starter, node.showopen, node.showapproved
				FROM {TABLE_PREFIX}closure AS cl
					INNER JOIN {TABLE_PREFIX}node AS node ON node.nodeid = cl.child
				WHERE cl.parent IN ({nodeid})
				ORDER by cl.parent ASC, cl.depth ASC
			"
		],

		'getChildrenOnly' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' =>  "
				SELECT cl.*, node.nodeid, node.userid, node.parentid, node.routeid, node.title,
					node.urlident, node.contenttypeid, node.publishdate, node.unpublishdate, node.showpublished, node.starter
				FROM {TABLE_PREFIX}closure AS cl
					INNER JOIN {TABLE_PREFIX}node AS node ON node.nodeid = cl.child
				WHERE cl.parent IN ({nodeid}) AND cl.depth > 0
				ORDER by cl.parent ASC, cl.depth ASC
			"
		],

		'getDescendants' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' =>  "SELECT cl.*, node.contenttypeid, node.publishdate, node.unpublishdate
				FROM {TABLE_PREFIX}closure AS cl
					INNER JOIN {TABLE_PREFIX}node AS node ON node.nodeid = cl.child
				WHERE cl.parent IN ({nodeid})
				ORDER by cl.child ASC, cl.depth ASC
			"
		],

		'getDescendantChannelNodeIds' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT closure.child
				FROM {TABLE_PREFIX}closure AS closure
					INNER JOIN {TABLE_PREFIX}node AS node ON node.nodeid = closure.child
				WHERE closure.parent = {parentnodeid} AND node.contenttypeid = {channelType}
			"
		],

		'getChildrenMatchingRoute' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' =>  "
				SELECT node.nodeid
				FROM {TABLE_PREFIX}closure AS closure
				INNER JOIN {TABLE_PREFIX}node AS node ON node.nodeid = closure.child
				WHERE closure.parent IN ({nodeid}) AND node.routeid IN ({routeid})
			"
		],

		'getLastPostDate' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT MAX(publishdate) AS dateline
				FROM {TABLE_PREFIX}node
				WHERE userid = {userid}
			"
		],

		'deleteMovedNodeClosureRecords' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_DELETE,
			'query_string' => "
				DELETE cl3 FROM {TABLE_PREFIX}closure AS cl
					INNER JOIN {TABLE_PREFIX}closure AS cl2 ON cl2.parent = cl.child AND cl2.depth > 0
					INNER JOIN {TABLE_PREFIX}closure AS cl3 ON cl3.child = cl2.child AND cl3.parent = cl.parent
				WHERE cl.child IN ({nodeids}) AND cl.depth > 0
			"
		],

		'insertMovedNodeClosureRecords' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_INSERT,
			'query_string' => "
				INSERT INTO {TABLE_PREFIX}closure (parent, child, depth, publishdate)
					SELECT cl2.parent, {nodeid}, cl2.depth + 1, node.publishdate
					FROM {TABLE_PREFIX}closure AS cl2 INNER JOIN
						{TABLE_PREFIX}node AS node
					WHERE cl2.child = {parentid} AND node.nodeid = {nodeid}
			"
		],

		'insertMovedNodeChildrenClosureRecords' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_INSERT,
			'query_string' => "
				INSERT INTO {TABLE_PREFIX}closure (parent, child, depth, publishdate)
					SELECT cl3.parent, cl2.child, cl2.depth + cl3.depth, cl2.publishdate
					FROM {TABLE_PREFIX}closure AS cl2 INNER JOIN
						{TABLE_PREFIX}closure AS cl3
					WHERE cl2.depth > 0 AND cl2.parent = {nodeid} AND cl3.depth > 0 AND cl3.child = {nodeid}
			"
		],

		'updateMovedNodeStarter' => array(vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_UPDATE,
			'query_string' => "
				UPDATE {TABLE_PREFIX}node
				SET starter = nodeid
				WHERE nodeid IN ({nodeids}) AND contenttypeid <> {channelTypeid}
		"),

		'updateMovedNodeChildrenStarter' => array(vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_UPDATE,
			'query_string' => "
				UPDATE {TABLE_PREFIX}node AS node INNER JOIN
					{TABLE_PREFIX}closure AS cl ON (cl.parent = node.nodeid) INNER JOIN
					{TABLE_PREFIX}node AS child ON (child.nodeid = cl.child)
				SET child.starter = node.nodeid, child.routeid = {routeid}
				WHERE node.nodeid IN ({nodeids}) AND node.contenttypeid <> {channelTypeid}
		"),

		'updateMovedNodeChildrenStarterNonChannel' => array(vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_UPDATE,
			'query_string' => "
				UPDATE {TABLE_PREFIX}node AS node INNER JOIN
					{TABLE_PREFIX}closure AS cl ON cl.parent = node.nodeid INNER JOIN
					{TABLE_PREFIX}node AS child ON child.nodeid = cl.child
				SET child.starter = {starter}, child.routeid = {routeid}
				WHERE node.nodeid IN ({nodeids})
		"),

		/*
		 *	After running this query, will need to update the counts on the tree to reflect the
		 *	changes.  See vB_Library_Node::updateSubTreePublishStatus
		 */
		'updateMovedNodeShowFields' => array(vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_UPDATE,
			'query_string' => "
				UPDATE {TABLE_PREFIX}node AS ch INNER JOIN
					{TABLE_PREFIX}closure AS cl ON (cl.child = ch.nodeid AND cl.parent = {parentid}) INNER JOIN
					{TABLE_PREFIX}closure AS cl2 ON (cl2.child = cl.child AND cl2.parent IN ({nodeids})) INNER JOIN
					{TABLE_PREFIX}node AS p ON (cl.parent = p.nodeid)
				SET ch.showopen = CASE WHEN p.open > 0 AND ch.open > 0 THEN 1 ELSE 0 END,
					ch.showapproved = CASE WHEN p.approved > 0 AND ch.approved > 0 THEN 1 ELSE 0 END
			"),

		'updateMovedNodeShowOpen' => array(vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_UPDATE,
			'query_string' => "
				UPDATE {TABLE_PREFIX}node AS filter INNER JOIN {TABLE_PREFIX}closure cl ON (filter.nodeid = cl.child
				    AND (filter.open = 0 OR filter.showopen = 0) AND cl.parent IN ({nodeids}) AND depth > 0) INNER JOIN
					{TABLE_PREFIX}closure AS cl2 ON cl2.parent = filter.nodeid INNER JOIN
					{TABLE_PREFIX}node AS target ON target.nodeid = cl2.child
					SET target.showopen = 0
		"),

		'updateMovedNodeShowApproved' => array(vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_UPDATE,
			'query_string' => "
				UPDATE {TABLE_PREFIX}node AS filter INNER JOIN
					{TABLE_PREFIX}closure AS cl ON (filter.nodeid = cl.child
				    AND (filter.approved = 0 OR filter.showapproved = 0) AND cl.parent IN ({nodeids}) AND depth > 0) INNER JOIN
					{TABLE_PREFIX}closure AS cl2 ON cl2.parent = filter.nodeid INNER JOIN
					{TABLE_PREFIX}node AS target ON target.nodeid = cl2.child
				SET target.showapproved = 0
		"),

		'selectMaxDepth' => array(vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT MAX(closure.depth) AS maxDepth FROM {TABLE_PREFIX}closure AS closure WHERE closure.parent = {rootnodeid}
		"),

		'getStarterStats' => array(vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT COUNT(*) AS count
				FROM {TABLE_PREFIX}node
				WHERE (starter=nodeid) AND publishdate > {timestamp}
		"),


		/*
		 *	This query relies on the parent node having the correct showpublish value.  It's intended to be
		 *	run in sequence from 0 to max depth
		 */
		"updatePublishedForDepth" => array(vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_UPDATE,
			'query_string' => "
				UPDATE {TABLE_PREFIX}node node INNER JOIN
					{TABLE_PREFIX}closure cl ON (node.nodeid = cl.child AND cl.parent={rootnodeid} AND cl.depth={depth}) INNER JOIN
					{TABLE_PREFIX}node parent ON (node.parentid = parent.nodeid)
				SET node.showpublished = IF((parent.showpublished AND node.publishdate > 0 AND node.publishdate <= {timenow} AND
						(IFNULL(node.unpublishdate, 0) = 0 OR IFNULL(node.unpublishdate, 0) > {timenow})), 1, 0)
		"),

		/*
		 *	Note that this will *not* update any nodes that don't have children.  The counts for nodes without
		 *	children should always be 0.  This is intended for updating a subtree based on a change to the
		 *	parent so leaf nodes should not be affected.
		 *
		 *	It also depends on the fact that nodes at a lower depth have correct counts (which can be assured
		 *	by running the query at depth n+1).
		 */
		"updateCountsForDepth" => array(vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_UPDATE,
			'query_string' => "
				UPDATE {TABLE_PREFIX}node AS target INNER JOIN
					(SELECT node.parentid AS nodeid,
						SUM(IF (node.contenttypeid NOT IN ({excluded}), 1, 0)) AS total,
						SUM(IF (node.showpublished AND node.showapproved AND node.contenttypeid NOT IN ({excluded}), 1, 0)) AS pubcount,
						SUM(node.totalcount) AS totalcount,
						SUM(node.totalunpubcount) AS totalunpubcount
					FROM  {TABLE_PREFIX}node AS node INNER JOIN
						{TABLE_PREFIX}closure AS closure ON (node.parentid = closure.child AND depth = {depth})
					WHERE closure.parent = {rootnodeid}
					GROUP BY node.parentid
					) AS sums ON (sums.nodeid = target.nodeid)
				SET target.textcount = sums.pubcount,
					target.textunpubcount = (sums.total - sums.pubcount),
					target.totalcount = sums.pubcount + sums.totalcount,
					target.totalunpubcount = (sums.total - sums.pubcount) + sums.totalunpubcount
		"),

		'truncate_cache' => array(vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_DELETE,
			'query_string' =>  "TRUNCATE TABLE {TABLE_PREFIX}cache"),

		'getContentTypes' => array(vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' =>  "
				(SELECT 'package' AS classtype, package.packageid AS typeid, package.packageid AS packageid,
						package.productid AS productid, if(package.productid = 'vbulletin', 1, product.active) AS enabled,
						package.class AS class, -1 as isaggregator, -1 AS cansearch, -1 AS canattach
				FROM {TABLE_PREFIX}package AS package
				LEFT JOIN {TABLE_PREFIX}product AS product ON product.productid = package.productid
				WHERE product.active = 1 OR package.productid = 'vbulletin'
				)
				UNION
				(SELECT 'contenttype' AS classtype, contenttypeid AS typeid, contenttype.packageid AS packageid,
					1, 1, contenttype.class AS class , contenttype.isaggregator, contenttype.cansearch, contenttype.canattach
				FROM {TABLE_PREFIX}contenttype AS contenttype
				INNER JOIN {TABLE_PREFIX}package AS package ON package.packageid = contenttype.packageid
				LEFT JOIN {TABLE_PREFIX}product AS product ON product.productid = package.productid
				WHERE product.active = 1 OR package.productid = 'vbulletin')
		"),

		'getUserDetails' => array(vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
		'query_string' =>  "
			SELECT u.*, ut.`rank`, ut.signature, av.avatarpath, NOT ISNULL(cu.userid) AS hascustomavatar, cu.dateline AS avatardateline,
				cu.width AS avwidth, cu.height AS avheight, cu.height_thumb AS avheight_thumb, cu.width_thumb AS avwidth_thumb
			FROM {TABLE_PREFIX}user AS u
			LEFT JOIN {TABLE_PREFIX}usertextfield AS ut ON (ut.userid = u.userid)
			LEFT JOIN {TABLE_PREFIX}customavatar AS cu ON (cu.userid = u.userid)
			LEFT JOIN {TABLE_PREFIX}avatar AS av ON (av.avatarid = u.avatarid)
			WHERE u.userid IN ({userid})
			"),

		'getNeedUpdate' => array(vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT node.parentid,  node.nodeid,  node.contenttypeid, node.publishdate, node.unpublishdate, node.showpublished,
					parent.showpublished AS parentpublished, node.textcount, node.textunpubcount, node.totalcount, node.totalunpubcount
				FROM {TABLE_PREFIX}node AS node INNER JOIN {TABLE_PREFIX}node AS parent	ON parent.nodeid = node.parentid
				WHERE node.nextupdate < {timenow}  AND node.nextupdate > 0
				ORDER BY node.nextupdate
				LIMIT {maxrows}
		"),

		'UpdateParentTextCount' => array(vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_UPDATE,
			'query_string' => 'UPDATE {TABLE_PREFIX}node SET
				textcount =
					CASE WHEN {textChange} > 0 OR textcount > -1 * {textChange}
						THEN textcount + ({textChange})
					ELSE 0 END,
				textunpubcount =
					CASE WHEN {textUnpubChange} > 0 OR textunpubcount > -1 * {textUnpubChange}
						THEN textunpubcount + ({textUnpubChange})
					ELSE 0 END
					WHERE nodeid IN ({nodeid})
		'),

		'UpdateAncestorCount' => array(vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_UPDATE,
			'query_string' => 'UPDATE {TABLE_PREFIX}node SET
				totalcount =
					CASE WHEN {totalChange} > 0 OR totalcount > -1 * {totalChange}
						THEN totalcount + ({totalChange})
					ELSE 0 END,
				totalunpubcount =
					CASE WHEN {totalUnpubChange} > 0 OR totalunpubcount > -1 * {totalUnpubChange}
						THEN totalunpubcount + ({totalUnpubChange})
					ELSE 0 END
					WHERE nodeid IN ({nodeid})'),

		'getChildContentTypes' => array(vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => 'SELECT DISTINCT contenttypeid FROM {TABLE_PREFIX}node AS node INNER JOIN {TABLE_PREFIX}closure AS cl ON cl.child = node.nodeid
			WHERE cl.parent IN ({nodeid})'),

		'setLastDataParentList' => array(vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_UPDATE,
			'query_string' => '
				UPDATE {TABLE_PREFIX}node AS node
					SET node.lastcontent = CASE WHEN {lastcontent} >= node.lastcontent THEN {lastcontent} ELSE node.lastcontent END,
						node.lastcontentid = CASE WHEN {lastcontent} >= node.lastcontent THEN {nodeid} ELSE node.lastcontentid END,
						node.lastcontentauthor = CASE WHEN {lastcontent} >= node.lastcontent THEN {lastcontentauthor} ELSE node.lastcontentauthor END,
						node.lastauthorid = CASE WHEN {lastcontent} >= node.lastcontent THEN {lastauthorid} ELSE node.lastauthorid END
						WHERE node.nodeid IN ({parentlist})'
		),


		/**
		 *	This has the potential to be hideously slow.  Need to figure out if we can't alter to use fixNodeLast in all cases
		 *	The current query is also incorrect in that it uses cl2.publishdate which is not currently maintained.
		 */
		'updateLastData' => array(vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_UPDATE,
			'query_string' => "UPDATE {TABLE_PREFIX}node AS node
				LEFT JOIN (
					SELECT cl2.parent, nodeid, starter, parentid, authorname, userid, n2.publishdate FROM {TABLE_PREFIX}node AS n2
					INNER JOIN {TABLE_PREFIX}closure AS cl2 USE INDEX (parent_2, publishdate) ON cl2.child = n2.nodeid
					WHERE cl2.parent = {parentid} AND cl2.depth > 0
						AND n2.inlist > 0 AND n2.publishdate <= {timenow} AND n2.showpublished > 0 AND n2.showapproved > 0
					ORDER BY cl2.publishdate DESC, cl2.child DESC LIMIT 1
					) AS latest ON (latest.parent = node.nodeid)
				SET node.lastcontent = GREATEST(node.publishdate, IFNULL(latest.publishdate, 0)),
					node.lastcontentid = COALESCE(latest.nodeid, CASE WHEN node.showapproved > 0 AND node.showpublished > 0 THEN node.nodeid ELSE 0 END),
					node.lastcontentauthor = COALESCE(latest.authorname, CASE WHEN node.showapproved > 0 AND node.showpublished > 0 THEN node.authorname ELSE '' END),
					node.lastauthorid = COALESCE(latest.userid, CASE WHEN node.showapproved > 0 AND node.showpublished > 0 THEN node.userid ELSE 0 END),
					node.lastupdate = {timenow}
				WHERE node.nodeid = {parentid}"
		),

		// the cl.depth > 0 prevents the parent-node from selecting itself as a possible
		// lastcontent candidate. This is problematic for starters & replies without
		// any replies and comments, respectively. todo: Fix this and update node validator in tests
		'getLastData' => array(vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => '
				SELECT nodeid, authorname, userid, n.publishdate
				FROM {TABLE_PREFIX}node AS n INNER JOIN
					{TABLE_PREFIX}closure AS cl ON (cl.child = n.nodeid)
				WHERE cl.parent = {parentid} AND
					cl.depth > 0 AND
					n.inlist > 0 AND
					n.publishdate <= {timenow} AND
					n.showpublished > 0 AND
					n.showapproved > 0 AND
					n.contenttypeid NOT IN ({excludeTypes})
				ORDER BY n.publishdate DESC, n.nodeid DESC LIMIT 1'
		),



		/*
		 *	Update the last content values to the this nodes values *unless* its an excluded content type
		 *	(something that doesn't count for last content for some reason).
		 *  TODO: for these queries, we currently do not check that the publishdate <= {timenow} (future publish)
		 *	It's unlikely but we should still check those for completeness
		 */
		'resetLastContentSelfForTree' => array(vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_UPDATE,
			'query_string' => "
				UPDATE {TABLE_PREFIX}node AS node
				INNER JOIN {TABLE_PREFIX}closure AS parent ON parent.child = node.nodeid
				SET node.lastcontent = CASE WHEN
						@var_usethisnodevalue := (node.contenttypeid NOT IN ({excluded}))
					THEN node.publishdate ELSE 0 END,
					node.lastcontentid = CASE WHEN @var_usethisnodevalue THEN node.nodeid ELSE 0 END,
					node.lastcontentauthor = CASE WHEN @var_usethisnodevalue THEN node.authorname ELSE '' END,
					node.lastauthorid = CASE WHEN @var_usethisnodevalue THEN node.userid ELSE 0 END,
					node.lastprefixid = CASE WHEN @var_usethisnodevalue THEN node.prefixid ELSE 0 END
				WHERE parent.parent = {rootid}"
		),

		// todo: add a n.publishdate <= {timenow} condition on the left join (future published children
		// though they should only really exist for articles, shouldn't affect parent lastcontent)
		'updateLastContentDateForTree' => array(vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_UPDATE,
			'query_string' => "
				UPDATE {TABLE_PREFIX}node AS node INNER JOIN
					(SELECT child.parent as nodeid, max(n.publishdate) as publishdate
					FROM {TABLE_PREFIX}closure AS parent INNER JOIN
						{TABLE_PREFIX}closure AS child ON (parent.child = child.parent) LEFT JOIN
						{TABLE_PREFIX}node AS n ON (
							child.child = n.nodeid AND
							n.inlist = 1 AND
							n.showpublished = 1 AND
							n.showapproved = 1 AND
							n.contenttypeid NOT IN ({excluded})
						)
					WHERE parent.parent = {rootid}
					GROUP BY child.parent) AS latest ON (node.nodeid = latest.nodeid)
					SET node.lastcontent = IFNULL(latest.publishdate, node.lastcontent)"
		),

		'updateLastContentNodeForTree' => array(vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_UPDATE,
			'query_string' => "
				UPDATE
					{TABLE_PREFIX}closure AS parent INNER JOIN
					{TABLE_PREFIX}node AS nodeparent
						ON (parent.child = nodeparent.nodeid AND nodeparent.lastcontent > 0) INNER JOIN
					{TABLE_PREFIX}closure AS child
						ON (parent.child = child.parent) INNER JOIN
					{TABLE_PREFIX}node AS nodechild
						ON (child.child = nodechild.nodeid AND
							nodeparent.lastcontent = nodechild.publishdate AND
							nodechild.inlist = 1 AND
							nodechild.showpublished = 1 AND
							nodechild.showapproved = 1 AND
							nodechild.contenttypeid NOT IN ({excluded})
						)
				SET nodeparent.lastcontentid = nodechild.nodeid,
					nodeparent.lastcontentauthor = nodechild.authorname,
					nodeparent.lastauthorid = nodechild.userid,
					nodeparent.lastprefixid = nodechild.prefixid
				WHERE parent.parent = {rootid}
				"
		),

		'updateLastContentBlankNodeForTree' => array(vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_UPDATE,
			'query_string' => "
				UPDATE {TABLE_PREFIX}closure parent INNER JOIN
						{TABLE_PREFIX}node AS nodeparent ON (parent.child = nodeparent.nodeid AND nodeparent.lastcontent = 0)
				SET nodeparent.lastcontentid = 0,
					nodeparent.lastcontentauthor = '',
					nodeparent.lastauthorid = 0,
					nodeparent.lastprefixid = 0
				WHERE parent.parent = {rootid}"
		),

		/*
		 *	Update the last content values to the this nodes values *unless* its an excluded content type
		 *	(something that doesn't count for last content for some reason).
		 */
		'updateLastContentSelf' => array(vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_UPDATE,
			'query_string' => "
				UPDATE {TABLE_PREFIX}node AS node
				SET node.lastcontent = CASE WHEN
						@var_usethisnodevalue := (node.contenttypeid NOT IN ({excluded}) AND node.inlist = 1)
					THEN node.publishdate ELSE 0 END,
					node.lastcontentid = CASE WHEN @var_usethisnodevalue THEN node.nodeid ELSE 0 END,
					node.lastcontentauthor = CASE WHEN @var_usethisnodevalue THEN node.authorname ELSE '' END,
					node.lastauthorid = CASE WHEN @var_usethisnodevalue THEN node.userid ELSE 0 END,
					node.lastprefixid = CASE WHEN @var_usethisnodevalue THEN node.prefixid ELSE 0 END
				WHERE node.nodeid = {nodeid}"
		),

		'selectMaxClosureDepth' => array(vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => '
				SELECT MAX(depth)
				FROM {TABLE_PREFIX}closure
				WHERE parent={parentid}'
			),

		'addClosure' => array (vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_INSERT,
			'query_string' => '
				INSERT INTO {TABLE_PREFIX}closure (parent, child, depth, publishdate)
				SELECT parent.parent, node.nodeid, parent.depth + 1, node.publishdate
				FROM {TABLE_PREFIX}node AS node INNER JOIN
					{TABLE_PREFIX}closure AS parent ON (parent.child = node.parentid)
				WHERE node.nodeid = {nodeid}'
		),

		'getCanonicalTags' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT t.tagtext, p.tagid AS canonicaltagid, p.tagtext AS canonicaltagtext
				FROM {TABLE_PREFIX}tag t JOIN
				{TABLE_PREFIX}tag p ON t.canonicaltagid = p.tagid
				WHERE t.tagtext IN ({tags})
			",
		],

		'addTagContent' => array(vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_INSERT,
			'query_string' => '
				INSERT INTO {TABLE_PREFIX}tagnode (nodeid, tagid, userid, dateline)
				SELECT {nodeid}, tag.tagid, {userid}, {dateline}
				FROM {TABLE_PREFIX}tag AS tag
					LEFT JOIN {TABLE_PREFIX}tagnode AS tn ON
						tn.nodeid = {nodeid} AND tn.tagid = tag.tagid AND tn.userid = {userid}
				WHERE tagtext IN ({tags}) AND tn.tagid IS NULL'
			),

		// TODO: remove if both vB_Tags::moveTagAttachments() vB_Tags::copyTagAttachments()
		// are permanently removed.
		'copyTagContent' => array(vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_INSERT,
			'query_string' => 'INSERT INTO {TABLE_PREFIX}tagnode
				(nodeid, tagid, userid, dateline)
			SELECT {nodeid}, tn.tagid, tn.userid, tn.dateline
			FROM {TABLE_PREFIX}tagnode AS tn
			LEFT JOIN {TABLE_PREFIX}tagnode AS tn2 ON tn2.nodeid = {nodeid}
			 AND tn.tagid = tn2.tagid
			WHERE tn.nodeid IN ({sourceid}) AND tn2.tagid IS NULL LIMIT {#limit}'
			),

		'mergeTagContent' => array(vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_INSERT,
			'query_string' => 'INSERT INTO {TABLE_PREFIX}tagnode
				(nodeid, tagid, userid, dateline)
			SELECT {nodeid}, tn.tagid, tn.userid, tn.dateline
			FROM {TABLE_PREFIX}tagnode AS tn
			LEFT JOIN {TABLE_PREFIX}tagnode AS tn2 ON tn2.nodeid = {nodeid}
			 AND tn2.tagid = tn.tagid
			WHERE tn.nodeid IN ({sourceid}) AND tn2.tagid IS NULL LIMIT {#limit}'
			),

		'getNodeTagList' => array(vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => '
				SELECT node.taglist
				FROM {TABLE_PREFIX}node as node
				WHERE node.nodeid = {nodeid}
			'),

		'getTagContent' => array(vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => ' SELECT tag.tagtext, IF(tagnode.tagid IS NULL, 0, 1) AS tagincontent, tagnode.userid
				FROM {TABLE_PREFIX}tag AS tag
				LEFT JOIN {TABLE_PREFIX}tagnode AS tagnode ON
				(tag.tagid = tagnode.tagid AND tagnode.nodeid = {nodeid})
				WHERE tag.tagtext IN ({tags})
			'),

		'getTagCount' => array(vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => " SELECT COUNT(*) AS count
					FROM {TABLE_PREFIX}tagnode AS tagnode
					WHERE nodeid = {nodeid}	AND userid = {userid}
			"),

		'getTags' => array(vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT tag.tagtext, tagnode.userid, tag.tagid
			FROM {TABLE_PREFIX}tag AS tag
			JOIN {TABLE_PREFIX}tagnode AS tagnode ON (tag.tagid = tagnode.tagid)
			WHERE tagnode.nodeid = {nodeid} ORDER BY tag.tagtext" ),
		'fetchproduct' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT * FROM {TABLE_PREFIX}product ORDER BY title
			"
		),

		//we duplicate the nodeid subquery because the optimizer doesn't appear to be smart enough
		//to figure out that the inner subquery only needs the row referneced in the outer update
		//and the *entire* tagnode table could be quite large.  I think it's smart enough to cache
		//the results and reuse them keeping the overhead of doing it twice to a minimum.
		'updateTagListExcludeUser' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_UPDATE,
			'query_string' => "
				UPDATE {TABLE_PREFIX}node AS node
					LEFT JOIN (
						SELECT tagnode.nodeid, group_concat(DISTINCT tag.tagtext ORDER BY tag.tagtext) AS taglist
						FROM {TABLE_PREFIX}tagnode AS tagnode
							JOIN {TABLE_PREFIX}tag AS tag ON (tagnode.tagid = tag.tagid) AND tagnode.userid != {userid}
						WHERE tagnode.nodeid IN (
							SELECT nodeid FROM {TABLE_PREFIX}tagnode AS tagnodeuser WHERE tagnodeuser.userid = {userid}
						)
						GROUP BY nodeid
					) AS lists ON (node.nodeid = lists.nodeid)
				SET node.taglist = IFNULL(lists.taglist, '')
				WHERE node.nodeid IN (
					SELECT nodeid FROM {TABLE_PREFIX}tagnode AS tagnodeuser WHERE tagnodeuser.userid = {userid}
				)
			"
		),

		'getTagNodesForUser' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT DISTINCT nodeid FROM {TABLE_PREFIX}tagnode WHERE userid = {userid}"
		),

		'getDistinctNodesCountForTagids' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT COUNT(DISTINCT nodeid) AS count
				FROM {TABLE_PREFIX}tagnode
				WHERE tagid IN ({tagids})"
		),
		'getDistinctNodesForTagids' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT DISTINCT nodeid
				FROM {TABLE_PREFIX}tagnode
				WHERE tagid IN ({tagids})
				LIMIT {perpage}"
		),

		'fetchchangedtemplates' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT tCustom.templateid, tCustom.title, tCustom.styleid,
				tCustom.username AS customuser, tCustom.dateline AS customdate, tCustom.version AS customversion,
				tCustom.mergestatus AS custommergestatus,
				tGlobal.username AS globaluser, tGlobal.dateline AS globaldate, tGlobal.version AS globalversion,
				tGlobal.product, templatemerge.savedtemplateid
				FROM {TABLE_PREFIX}template AS tCustom
				INNER JOIN {TABLE_PREFIX}template AS tGlobal ON
					(tGlobal.styleid = -1 AND tGlobal.title = tCustom.title)
				LEFT JOIN {TABLE_PREFIX}templatemerge AS templatemerge ON
					(templatemerge.templateid = tCustom.templateid)
				WHERE tCustom.styleid <> -1
					AND tCustom.templatetype = 'template' AND tCustom.mergestatus IN ('merged', 'conflicted')
				ORDER BY tCustom.title
			"
		),
		'fetchstyles2' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT styleid, title, parentlist, parentid, userselect
				FROM {TABLE_PREFIX}style
				ORDER BY parentid
			"
		),
		'fetchstylebyid' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT * FROM {TABLE_PREFIX}style WHERE styleid = {styleid}
			"
		),
		'updatestyleparent' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_UPDATE,
			'query_string' => "
				UPDATE {TABLE_PREFIX}style
				SET parentlist = {parentlist}
				WHERE styleid = {styleid}
			"
		),
		'updatestyletemplatelist' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_UPDATE,
			'query_string' => "
				UPDATE {TABLE_PREFIX}style
				SET templatelist = {templatelist}
				WHERE styleid = {styleid}
			"
		),
		'fetchprofilefields' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT profilefieldid, type, data, optional
				FROM {TABLE_PREFIX}profilefield
			"
		),
		'fetchCustomProfileFields' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT pf.profilefieldcategoryid, pfc.location, pf.*
				FROM {TABLE_PREFIX}profilefield AS pf
					LEFT JOIN {TABLE_PREFIX}profilefieldcategory AS pfc ON(pfc.profilefieldcategoryid = pf.profilefieldcategoryid)
				WHERE pf.form = 0 AND pf.hidden IN ({hidden})
				ORDER BY pfc.displayorder, pf.displayorder
			"
		),

		'fetchUserVmInfo' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT COUNT(*) AS count, MAX(publishdate) AS mostrecent
				FROM {TABLE_PREFIX}node
				WHERE setfor = {setfor}
			",
		],

		// fetch_dismissed_notice()
		'fetchdismissednotices' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT noticeid
				FROM {TABLE_PREFIX}noticedismissed AS noticedismissed
				WHERE noticedismissed.userid = {userid}
			"
		),

		// Notice API
		'dismissnotice' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_INSERT,
			'query_string' => "
				REPLACE INTO {TABLE_PREFIX}noticedismissed
					(noticeid, userid)
				VALUES
					({noticeid}, {userid})
			"
		),

		'fetchnoticecachevalues' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT notice.noticeid, notice.persistent, notice.dismissible, notice.noticeoptions,
					noticecriteria.criteriaid, noticecriteria.condition1,
					noticecriteria.condition2, noticecriteria.condition3
				FROM {TABLE_PREFIX}notice AS notice
					LEFT JOIN {TABLE_PREFIX}noticecriteria AS noticecriteria ON(noticecriteria.noticeid = notice.noticeid)
				WHERE notice.active = 1
				ORDER BY notice.displayorder, notice.title
			"
		),

		// Update filedataresize
		'replaceIntoFiledataResize' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_INSERT,
			'query_string' => "
				REPLACE INTO {TABLE_PREFIX}filedataresize
					(filedataid, resize_type, resize_filedata, resize_filesize, resize_dateline, resize_width, resize_height, reload)
				VALUES
					({filedataid}, {resize_type}, {resize_filedata}, {resize_filesize}, {resize_dateline}, {resize_width}, {resize_height}, {reload})
			"
		),

		// vBTemplate::fetch_template
		'fetchtemplate' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT template
				FROM {TABLE_PREFIX}template
				WHERE templateid = {templateid}
			"
		),

		// can_moderate()
		'supermodcheck' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT usergroupid
				FROM {TABLE_PREFIX}usergroup
				WHERE usergroupid IN ({usergroupids})
					AND (adminpermissions & {ismoderator}) != 0
				LIMIT 1
			"
		),
		'fetchusermembergroups' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT usergroupid, membergroupids
				FROM {TABLE_PREFIX}user
				WHERE userid = {userid}
			"
		),
		'fetchLegacyAttachments' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT n.oldid, n.nodeid, n.contenttypeid, n.parentid,
					fd.filedataid, fd.filehash, fd.extension, fd.filesize,
					fd.dateline, fd.publicview,	fd.refcount,
					fdr.resize_filesize, IF (fdr.resize_filesize > 0, 1, 0) AS hasthumbnail,
					fdr.resize_dateline, a.settings, a.filename
				FROM {TABLE_PREFIX}attach AS a INNER JOIN {TABLE_PREFIX}node AS n
				ON n.nodeid = a.nodeid
				INNER JOIN {TABLE_PREFIX}filedata AS fd ON fd.filedataid = a.filedataid
				LEFT JOIN {TABLE_PREFIX}filedataresize AS fdr ON (fd.filedataid = fdr.filedataid AND fdr.resize_type = 'thumb')
				WHERE n.oldid IN ({oldids}) AND n.oldcontenttypeid IN ({oldcontenttypeid})
				ORDER BY n.displayorder
			"
		),
		'fetchLegacyPostIds' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				(SELECT nodeid, oldid, starter, routeid
				FROM {TABLE_PREFIX}node
				WHERE oldid IN ({oldids}) AND oldcontenttypeid IN ({postContentTypeId}))
				UNION
				(SELECT t.nodeid, t.postid as oldid, n.starter, n.routeid
				FROM {TABLE_PREFIX}thread_post t
				INNER JOIN {TABLE_PREFIX}node n ON n.nodeid = t.nodeid
				WHERE t.postid IN ({oldids}))
			"
		),
		'fetchAttach' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT a.*
				FROM {TABLE_PREFIX}attach AS a
					INNER JOIN {TABLE_PREFIX}node AS n ON (n.nodeid = a.nodeid)
				WHERE n.nodeid IN ({nodeid})
				ORDER BY n.displayorder
			"
		),
		'fetchAttachForLoad' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT node.parentid, node.userid, attach.filename, attach.filedataid,
					filedata.extension
				FROM {TABLE_PREFIX}attach AS attach
					INNER JOIN {TABLE_PREFIX}node AS node ON (node.nodeid = attach.nodeid)
					INNER JOIN {TABLE_PREFIX}filedata AS filedata ON (filedata.filedataid = attach.filedataid)
				WHERE node.nodeid IN ({nodeid})
			"
		),
		'fetchAttach2' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT a.*, f.userid, f.extension, f.dateline, fdr.resize_dateline, f.filesize, f.filehash,
					fdr.resize_filesize, f.width, f.height, fdr.resize_width, fdr.resize_height, f.refcount,
					f.publicview
				FROM {TABLE_PREFIX}attach AS a
					INNER JOIN {TABLE_PREFIX}filedata AS f ON (a.filedataid = f.filedataid)
					LEFT JOIN {TABLE_PREFIX}filedataresize AS fdr ON (f.filedataid = fdr.filedataid AND fdr.resize_type = 'thumb')
				WHERE a.filedataid IN ({filedataid})
			"
		),
		//this is a funny looking query.  We want all of the child nodes that are either attachments or photos
		//which have slightly different data associated with them.  The previous version of the query used
		//left joins to both the attach and photo table and kept the records that had one or the other.
		//However we can rely on the fact that we will only have one or the other and that we know what the
		//content type will be to better use the index on the node table to avoid looking at every child
		//of a node to find its attachments.  This is especially important when dealing with starters
		//that have lots of replies.  The "NULL AS" mimics the left join results.  I'm not sure that's the
		//best way to do this but it best mimics the prior query to avoid having to hunt down and make sure
		//know how those records are being used (unfortunately this record format is implicitly part of the
		//public interface to the Node Library class).
		//
		//We add the display order so that we can sort client side. Add the sort here forces the query to
		//use a temp table which adds overhead.
		'fetchNodeAttachments' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				(
					SELECT
						a.filedataid,
						n.nodeid, n.parentid, n.contenttypeid,
						n.title,
						a.visible, a.counter, a.posthash, a.filename, a.reportthreadid, a.settings,
						NULL AS height, NULL AS width, NULL AS style,
						a.caption,
						n.displayorder
					FROM {TABLE_PREFIX}node AS n USE INDEX (contenttypeid_parentid)
						JOIN {TABLE_PREFIX}attach AS a ON (a.nodeid = n.nodeid)
					WHERE n.contenttypeid = {attachcontenttypeid} AND n.parentid IN ({nodeid})
				)
				UNION ALL
				(
					SELECT
						p.filedataid,
						n.nodeid, n.parentid, n.contenttypeid,
						n.title,
						NULL AS visible, NULL AS counter, NULL AS posthash, NULL AS filename, NULL AS reportthreadid, NULL AS settings,
						p.height, p.width, p.style,
						p.caption,
						n.displayorder
					FROM {TABLE_PREFIX}node AS n USE INDEX (contenttypeid_parentid)
						JOIN {TABLE_PREFIX}photo AS p ON (p.nodeid = n.nodeid)
					WHERE n.contenttypeid = {photocontenttypeid} AND n.parentid IN ({nodeid})
				)
			"
		),

		'filteredTagsCount' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT count(tn.tagid) as filteredTags
				FROM {TABLE_PREFIX}tagnode AS tn
					LEFT JOIN {TABLE_PREFIX}tagnode AS tn2 ON (tn.tagid = tn2.tagid AND tn2.nodeid = {targetid})
				WHERE tn.nodeid = {sourceid} AND tn2.tagid IS NULL"
		),

		'deleteTags' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_DELETE,
			'query_string' => "
				DELETE tn.*
				FROM {TABLE_PREFIX}tag AS tag
					LEFT JOIN {TABLE_PREFIX}tagnode AS tn ON (tag.tagid = tn.tagid)
					WHERE tn.nodeid = {nodeid} AND tag.tagtext NOT IN ({ignoredTags})"
		),

		// build_datastore()
		'insertdatastore' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_INSERT,
			'query_string' => "
				REPLACE INTO {TABLE_PREFIX}datastore
					(title, data, unserialize)
				VALUES
					({title}, {data}, {unserialize})
			"
		),

		// build_language()
		'fetchphrasetypes' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT fieldname
				FROM {TABLE_PREFIX}phrasetype
				WHERE editrows <> 0 AND
					special = 0
			",
		),
		// for building the rank datastore -- note that the ordering is very important for the
		// logic that determines a user's ranks
		// Edit: previously, the minposts DESC determined the collapse logic. This posed a problem
		// when we added other qualifiers, so added the 'priority' column which works the same way
		'fetchranks' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT `ranks`.*
				FROM `{TABLE_PREFIX}ranks` AS `ranks`
				WHERE `active` = 1
				ORDER BY `grouping` ASC, `priority` DESC
			",
		],

		// build_userlist()
		'fetchuserlists' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT user.*, userlist.type FROM {TABLE_PREFIX}userlist AS userlist
					INNER JOIN {TABLE_PREFIX}user AS user ON (user.userid = userlist.relationid)
				WHERE userlist.userid = {userid}
			",
		),

		'setStarter' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_UPDATE,
			'query_string' => "UPDATE {TABLE_PREFIX}node AS n
			INNER JOIN {TABLE_PREFIX}closure AS c ON n.nodeid = c.child AND c.parent = {nodeid}
			SET n.starter = {starter}",
		),
		// Poll api
		'poll_fetchvotes' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT COUNT(DISTINCT(userid))
				FROM {TABLE_PREFIX}pollvote as pollvote
				WHERE pollvote.nodeid = {nodeid}
			",
		),
		'getDefaultStyleVars' =>  array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT stylevardfn.stylevarid, stylevardfn.datatype, stylevar.value
				FROM {TABLE_PREFIX}stylevardfn AS stylevardfn
				LEFT JOIN {TABLE_PREFIX}stylevar AS stylevar ON (stylevardfn.stylevarid = stylevar.stylevarid AND stylevar.styleid = -1)"
		),
		'getStylesFromList' =>  array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT stylevarid, styleid, value, INSTR(CONCAT(',' , {parentlist} , ','), CONCAT(',', styleid, ',') ) AS ordercontrol
				FROM {TABLE_PREFIX}stylevar
				WHERE styleid IN ({stylelist})
				ORDER BY ordercontrol DESC"
		),
		'getPhotos' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT p.*,
					node.routeid, node.contenttypeid, node.publishdate, node.unpublishdate,
					node.userid, node.groupid, node.authorname, node.description,
					node.title, node.htmltitle, node.parentid, node.urlident, node.displayorder,
					node.created,node.lastcontent,node.lastcontentid,node.lastcontentauthor,node.lastauthorid,
					node.lastprefixid,node.textcount,node.textunpubcount,node.totalcount,node.totalunpubcount,node.ipaddress,
					node.nextupdate, node.lastupdate, node.showpublished, node.featured, node.starter, node.crc32,
					starter.title AS startertitle, starter.parentid AS channelid, starter.userid AS gallery_userid
				FROM {TABLE_PREFIX}photo AS p
				INNER JOIN {TABLE_PREFIX}node AS node ON node.nodeid = p.nodeid
				INNER JOIN {TABLE_PREFIX}node AS starter ON starter.nodeid = node.starter
					WHERE node.parentid IN ({parentid})
				"
		),
		// channel info for widgets
		'getChannelInfo' => array(vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT parent.title, parent.routeid
				FROM {TABLE_PREFIX}node AS parent INNER JOIN {TABLE_PREFIX}node AS child ON child.parentid =parent.nodeid
				WHERE child.nodeid IN ({nodeid})
			"),
		'getChannelInfoExport' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => '
				SELECT c.*, n.*, r.guid AS routeguid, parent.guid AS parentguid
				FROM {TABLE_PREFIX}channel c
				INNER JOIN {TABLE_PREFIX}node n ON c.nodeid = n.nodeid
				LEFT JOIN {TABLE_PREFIX}channel parent ON n.parentid = parent.nodeid
				INNER JOIN {TABLE_PREFIX}routenew r ON r.routeid = n.routeid
				WHERE c.product = {productid}
				ORDER BY n.parentid, n.nodeid
			'
		),
		'getChannelWidgetInfo' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => '
				SELECT c.*, n.*
				FROM {TABLE_PREFIX}channel c
				INNER JOIN {TABLE_PREFIX}node n ON c.nodeid = n.nodeid
				WHERE c.nodeid > 1
				ORDER BY n.nodeid
			'
		),

		'selectPostsForPostCount' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => '
				SELECT node.nodeid, node.parentid, node.approved, node.showapproved, node.showpublished, node.publishdate,
					node.userid, node.contenttypeid, node.deleteuserid, node.starter
				FROM 	{TABLE_PREFIX}closure AS closure JOIN
					{TABLE_PREFIX}node AS node ON (closure.child = node.nodeid)
				WHERE
					closure.parent={rootid}
				ORDER BY closure.depth ASC
			'
		),

		'incrementUserPostCount' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_UPDATE,
			'query_string' => "
				UPDATE {TABLE_PREFIX}user
				SET posts = posts + {count},
					lastpost = if((lastpost >= {lastposttime}), lastpost, {lastposttime}),
					lastpostid = if((lastpost >= {lastposttime}), lastpostid, {lastnodeid}),
					startedtopics = startedtopics + {starters}
				WHERE userid = {userid}
			",
		),

		//this used to use GREATEST(0, posts - {count}) to set the posts, however because
		//posts is an unsigned in then so is (posts - {count}) and that gives a range error
		//before we even hit the GREATEST call.  Somebody slapped an AND posts > 0 into the
		//where clause on the previous version (where count was hardcoded to 1) but that's the
		//wrong answer
		//
		//note if this happens it means we have a bug somewhere, but a incorrectly zeroed post
		//count is better than a database error in this instance.
		'decrementUserPostCount' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_UPDATE,
			'query_string' => "
				UPDATE {TABLE_PREFIX}user
				SET posts = if (posts < {count}, 0, posts - {count}),
					startedtopics = if (startedtopics < {starters}, 0, startedtopics - {starters})
				WHERE userid = {userid}
			",
		),

		// user referral count
		'getReferralsCount' => array(
				vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
				'query_string' => "SELECT count(referrerid) AS referrals
						FROM {TABLE_PREFIX}user
						WHERE referrerid = {userid}"
		),
		'getNodeFollowers' => array(
				vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
				'query_string' => "SELECT u.username, u.displayname, u.userid, sd.discussionid as nodeid
				FROM {TABLE_PREFIX}subscribediscussion AS sd
				INNER JOIN {TABLE_PREFIX}user AS u ON sd.userid = u.userid
				WHERE sd.discussionid IN ({nodeid})
				ORDER BY u.username ASC
				LIMIT {#limit_start}, {#limit}
				"
		),
		'getNodeFollowersCount' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT COUNT(*) AS nr
				FROM {TABLE_PREFIX}subscribediscussion AS sd
				INNER JOIN {TABLE_PREFIX}user AS u ON sd.userid = u.userid
				WHERE sd.discussionid = {nodeid}
			"
		),

		// get user followers
		'getFollowers' => array(
				vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
				'query_string' => "SELECT u.userid, u.username AS username, IFNULL(u.lastactivity, u.joindate) as lastactivity,
				IFNULL((SELECT userid FROM {TABLE_PREFIX}userlist AS ul2 WHERE ul2.userid = {userid} AND ul2.relationid = u.userid AND ul2.type = 'follow' AND ul2.friend = 'yes'), 0) as isFollowing,
				IFNULL((SELECT userid FROM {TABLE_PREFIX}userlist AS ul2 WHERE ul2.userid = {userid} AND ul2.relationid = u.userid AND ul2.type = 'follow' AND ul2.friend = 'pending'), 0) as isPending
				FROM {TABLE_PREFIX}user AS u
				INNER JOIN {TABLE_PREFIX}userlist AS ul ON (u.userid = ul.userid AND ul.relationid = {userid})
				WHERE ul.type = 'follow' AND ul.friend = 'yes'
				ORDER BY lastactivity DESC, username ASC
				LIMIT {#limit_start}, {#limit}
				"
		),

		// delete following from user with all his/her posts
		'deleteMemberFollowing' => array(
				vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_DELETE,
				'query_string' => "DELETE sd.*
				FROM {TABLE_PREFIX}subscribediscussion AS sd
				INNER JOIN {TABLE_PREFIX}node AS n ON (n.nodeid = sd.discussionid)
				WHERE n.userid = {memberid} AND sd.userid = {userid}
				"
		),

		// delete following from channel with all related posts
		'deleteChannelFollowing' => array(
				vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_DELETE,
				'query_string' => "DELETE sd.*
				FROM {TABLE_PREFIX}node AS n
				INNER JOIN {TABLE_PREFIX}subscribediscussion AS sd ON (n.nodeid = sd.discussionid)
				WHERE n.parentid = {channelid} AND sd.userid = {userid}
				"
		),
		// summary of unread messages
		'messageSummary' => array(
				vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT f.folderid, f.titlephrase, f.title, count(node.nodeid)-count(i.type) AS qty
			FROM {TABLE_PREFIX}messagefolder AS f
			LEFT JOIN {TABLE_PREFIX}sentto AS s ON s.folderid = f.folderid AND s.deleted = 0 AND s.msgread = 0
			LEFT JOIN {TABLE_PREFIX}node AS node ON s.nodeid = node.nodeid AND node.nodeid = node.starter
			LEFT JOIN {TABLE_PREFIX}userlist AS i ON (i.userid = f.userid AND i.relationid = node.userid AND i.type = 'ignore')
			WHERE f.userid = {userid}
			GROUP BY f.folderid
			ORDER BY f.titlephrase, f.title"
		),
		// Get the last nodeid for a PM thread. (This should be the latest reply)
		'lastNodeids' => array(
				vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT MAX(node.nodeid) AS nodeid
			FROM {TABLE_PREFIX}node AS node
			INNER JOIN {TABLE_PREFIX}sentto AS s ON s.nodeid = node.nodeid
			INNER JOIN {TABLE_PREFIX}privatemessage AS pm ON pm.nodeid = node.nodeid
			WHERE s.userid = {userid} AND s.msgread=0 AND s.deleted = 0 AND s.folderid NOT IN ({excludeFolders}) AND pm.msgtype = 'message'
			GROUP BY node.starter"
		),

		//Get the ignored user id
		'getIgnoredUserids' => array(
				vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT i.relationid AS userid FROM {TABLE_PREFIX}userlist AS i WHERE i.userid = {userid} AND i.type = 'ignore'"
		),

		//Get the preview page for messages.
		'pmPreview' => array(
				vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "(SELECT n.nodeid, n.routeid, n.publishdate, n.unpublishdate, n.userid , n.authorname, n.title, n.starter, n.lastcontent, n.lastcontentid,
			n.lastcontentauthor, n.lastauthorid, n.textcount, n.totalcount, n.ipaddress, text.rawtext, text.pagetext, text.previewtext, pm.msgtype, s.folderid, 'messages' AS titlephrase, u.username, starter.routeid, n.textcount AS responses,
			s.msgread, pm.about, pm.aboutid, n.routeid AS aboutrouteid, starter.title AS abouttitle, starter.userid AS starteruserid, starter.created AS startercreated, starter.lastcontent AS lastpublishdate, poll.votes, poll.lastvote, starter.starter AS starter_parent,
			NULL AS aboutstarterid, NULL as aboutstartertitle, NULL AS aboutstarterrouteid
			FROM {TABLE_PREFIX}privatemessage AS pm
			INNER JOIN {TABLE_PREFIX}text AS text ON text.nodeid = pm.nodeid
			INNER JOIN {TABLE_PREFIX}node AS n ON n.nodeid = pm.nodeid
			INNER JOIN {TABLE_PREFIX}node AS starter ON starter.nodeid = n.starter
			INNER JOIN {TABLE_PREFIX}sentto AS s ON s.nodeid = pm.nodeid
			INNER JOIN {TABLE_PREFIX}messagefolder AS f ON f.folderid = s.folderid
			INNER JOIN {TABLE_PREFIX}user AS u ON u.userid = n.userid
			LEFT JOIN {TABLE_PREFIX}poll AS poll ON poll.nodeid = pm.aboutid
			WHERE s.userid = {userid} AND s.msgread=0 AND s.deleted = 0 AND n.userid NOT IN ({ignoreUsers}) AND s.folderid NOT IN ({excludeFolders}) AND pm.msgtype = 'message'
			AND ifnull(f.title, '') = '' AND n.nodeid IN ({nodeids})
			ORDER BY n.created DESC
			LIMIT 5)
			UNION
			(SELECT n.nodeid, n.routeid, n.publishdate, n.unpublishdate, n.userid , n.authorname, n.title, n.starter, n.lastcontent, n.lastcontentid,
			n.lastcontentauthor, n.lastauthorid, n.textcount, n.totalcount, n.ipaddress, text.rawtext, text.pagetext, text.previewtext, pm.msgtype, s.folderid,'requests' AS titlephrase, u.username, starter.routeid, n.textcount AS responses,
			s.msgread, pm.about, pm.aboutid, n.routeid AS aboutrouteid, starter.title AS abouttitle, starter.userid AS starteruserid, starter.created AS startercreated, starter.lastcontent AS lastpublishdate, poll.votes, poll.lastvote, starter.starter AS starter_parent,
			NULL AS aboutstarterid, NULL as aboutstartertitle, NULL AS aboutstarterrouteid
			FROM {TABLE_PREFIX}privatemessage AS pm
			INNER JOIN {TABLE_PREFIX}text AS text ON text.nodeid = pm.nodeid
			INNER JOIN {TABLE_PREFIX}node AS n ON n.nodeid = pm.nodeid
			INNER JOIN {TABLE_PREFIX}node AS starter ON starter.nodeid = n.starter
			INNER JOIN {TABLE_PREFIX}sentto AS s ON s.nodeid = pm.nodeid
			INNER JOIN {TABLE_PREFIX}user AS u ON u.userid = n.userid
			LEFT JOIN {TABLE_PREFIX}poll AS poll ON poll.nodeid = pm.aboutid
			WHERE s.userid = {userid} AND s.msgread=0 AND s.deleted = 0 AND n.userid NOT IN ({ignoreUsers}) AND pm.msgtype = 'request'
			ORDER BY n.created DESC
			LIMIT 5)"
		),
		// count of undeleted messages for this user
		'getUnreadMsgCount' => array(
				vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT count(node.nodeid) AS qty
			FROM {TABLE_PREFIX}sentto AS s
			JOIN {TABLE_PREFIX}node AS node ON s.nodeid = node.nodeid AND node.nodeid = node.starter
			LEFT JOIN {TABLE_PREFIX}userlist AS i ON i.userid = s.userid AND i.relationid = node.userid AND i.type = 'ignore'
			WHERE s.userid = {userid} AND s.deleted = 0 AND s.msgread = 0 AND i.type is NULL"
		),
		// count of undeleted system (No Pms) messages for this user
		'getUnreadSystemMsgCount' => array(
				vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT count(node.nodeid) AS qty
			FROM {TABLE_PREFIX}sentto AS s
			JOIN {TABLE_PREFIX}messagefolder AS f ON f.folderid = s.folderid AND f.titlephrase IN ('requests', 'pending_posts')
			JOIN {TABLE_PREFIX}node AS node ON node.nodeid = s.nodeid AND node.nodeid = node.starter
			LEFT JOIN {TABLE_PREFIX}userlist AS i ON i.userid = s.userid AND i.relationid = node.userid AND i.type = 'ignore'
			WHERE s.userid = {userid} AND s.deleted = 0 AND s.msgread = 0 AND i.type is NULL"
		),
		//to be correct this should by group by f.folderid, f.titlephrase.  But that seems to perform slightly less well
		//in mysql and the current group by will still produce the same results in mysql (it's not standard sql)
		//it's valid so log as folderid/titlephrase is a unique combination
		'getHeaderMsgCount' => array(
				vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT count(node.nodeid) AS qty, f.titlephrase AS folder, f.folderid AS folderid
			FROM {TABLE_PREFIX}sentto AS s
			JOIN {TABLE_PREFIX}messagefolder AS f ON f.folderid = s.folderid AND f.titlephrase IN ('messages', 'requests')
			JOIN {TABLE_PREFIX}node AS node ON s.nodeid = node.nodeid AND node.nodeid = node.starter
			LEFT JOIN {TABLE_PREFIX}userlist AS i ON i.userid = s.userid AND i.relationid = node.userid AND i.type = 'ignore'
			WHERE s.userid = {userid} AND s.deleted = 0 AND s.msgread = 0 AND i.type is NULL
			GROUP BY f.folderid"
		),
		// count of open reports.
		// TODO: Change count(report.nodeid) to count(distinct(report.reportnodeid)) when implementing grouping reports via nodeid
		'getOpenReportsCount' => array(
				vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT count(report.nodeid) AS qty FROM {TABLE_PREFIX}report AS report
			WHERE  report.closed = 0"
		),
		// count of undeleted messages in this folder
		'getMsgCountInFolder' => array(
				vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT CASE WHEN s.deleted = 1 THEN 1 ELSE 0 END as deleted, n.nodeid
			FROM {TABLE_PREFIX}sentto AS s
			INNER JOIN {TABLE_PREFIX}messagefolder AS m ON m.folderid = s.folderid
			INNER JOIN {TABLE_PREFIX}node AS n ON n.nodeid = s.nodeid
			WHERE  m.folderid = {folderid}
			GROUP BY n.starter
			HAVING deleted = 0"
		),
		// count of undeleted messages in this folder with an about limit- for notifications
		'getMsgCountInFolderAbout' => array(
				vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT CASE WHEN s.deleted = 1 THEN 1 ELSE 0 END as deleted, n.nodeid
			FROM {TABLE_PREFIX}sentto AS s
			INNER JOIN {TABLE_PREFIX}messagefolder AS m ON m.folderid = s.folderid
			INNER JOIN {TABLE_PREFIX}node AS n ON n.nodeid = s.nodeid
			INNER JOIN {TABLE_PREFIX}privatemessage AS pm ON pm.nodeid = n.nodeid
			WHERE  m.folderid = {folderid} AND pm.about IN ({about})
			GROUP BY n.starter
			HAVING deleted = 0"
		),
		// Id and name of all "other" recipients of an email.
		'getPMRecipients' => array(
				vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT s.nodeid, s.userid, u.username FROM
			{TABLE_PREFIX}node AS n
			INNER JOIN {TABLE_PREFIX}sentto AS s ON s.nodeid = n.starter
			INNER JOIN {TABLE_PREFIX}user AS u ON u.userid = s.userid
			WHERE n.nodeid IN ({nodeid}) AND u.userid NOT IN ({userid})
			ORDER BY s.nodeid"
		),
		'getRecipientsForNode' => array(
				vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT s.nodeid, s.userid, folder.titlephrase AS folder
			FROM {TABLE_PREFIX}sentto AS s
			INNER JOIN {TABLE_PREFIX}messagefolder AS folder ON folder.folderid = s.folderid
			WHERE s.nodeid IN ({nodeid})"
		),
		'getPMRecipientsForMessage' => array(
				vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT s.userid, u.username, n.nodeid AS starter
			FROM {TABLE_PREFIX}node AS n
			INNER JOIN {TABLE_PREFIX}sentto AS s ON s.nodeid = n.nodeid AND n.nodeid = n.starter
			INNER JOIN {TABLE_PREFIX}user AS u ON u.userid = s.userid
			WHERE n.nodeid IN ({nodeid})
			ORDER BY n.nodeid"
		),

		'getPMRecipientsForMessageOverlay' => array(vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT s.userid, u.username, n.nodeid AS starter, NULL AS following
				FROM {TABLE_PREFIX}node AS n INNER JOIN
					{TABLE_PREFIX}sentto AS s ON s.nodeid = n.nodeid AND n.nodeid = n.starter INNER JOIN
					{TABLE_PREFIX}user AS u ON u.userid = s.userid
				WHERE n.nodeid IN ({nodeid})
				ORDER BY s.nodeid, u.username"
		),

		'getPMLastAuthor' => array(
				vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT nodeinfo.nodeid, (CASE when u1.username IS NULL THEN u.username ELSE u1.username END) AS username,
			(CASE when u1.userid IS NULL THEN u.userid ELSE u1.userid END) AS userid
			FROM
			(
				SELECT node.nodeid, MAX(cl.child) AS child
				FROM {TABLE_PREFIX}node AS node
				LEFT JOIN {TABLE_PREFIX}closure AS cl ON cl.parent = node.nodeid
				LEFT JOIN {TABLE_PREFIX}node AS child ON child.nodeid = cl.child
				WHERE node.nodeid IN ({nodeid}) AND child.userid <> {userid}
				GROUP BY node.nodeid
			) AS nodeinfo
			INNER JOIN {TABLE_PREFIX}sentto AS sentto ON (sentto.nodeid = nodeinfo.nodeid AND sentto.userid <> {userid})
			INNER JOIN {TABLE_PREFIX}user AS u ON u.userid = sentto.userid
			LEFT JOIN {TABLE_PREFIX}node AS reply ON reply.nodeid = nodeinfo.child
			INNER JOIN {TABLE_PREFIX}user AS u1 ON u1.userid = reply.userid
			GROUP BY nodeinfo.nodeid"
		),
		'getSimplifiedPMNodelist' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT sentto.nodeid
				FROM {TABLE_PREFIX}sentto AS sentto
				INNER JOIN {TABLE_PREFIX}node AS node ON node.nodeid = sentto.nodeid
				WHERE node.starter = {starter} AND sentto.userid = {userid}
				ORDER BY node.nodeid
			"
		),
		'getPrivateMessageTree' => array(
				vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT node.*, IFNULL(u.username, node.authorname) AS username, cl.depth, t.pagetext, t.rawtext, t.previewtext,
				pm.msgtype, pm.about, pm.aboutid, s.msgread
				FROM {TABLE_PREFIX}node AS node
				INNER JOIN {TABLE_PREFIX}closure AS cl ON cl.child = node.nodeid
				LEFT JOIN {TABLE_PREFIX}user AS u ON u.userid = node.userid
				INNER JOIN {TABLE_PREFIX}text AS t ON t.nodeid = node.nodeid
				INNER JOIN {TABLE_PREFIX}privatemessage AS pm ON pm.nodeid = node.nodeid
				INNER JOIN {TABLE_PREFIX}sentto AS s ON s.nodeid = node.nodeid
				WHERE cl.parent = {nodeid} AND s.userid = {userid}
				ORDER BY cl.depth, node.publishdate"
		),
		'getPrivateMessageForward' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT messagenode.nodeid AS messageid, messagenode.authorname AS messageauthor, node.*, t.rawtext, t.pagetext, u.username
				FROM {TABLE_PREFIX}node AS messagenode
					INNER JOIN {TABLE_PREFIX}node AS node ON node.nodeid = messagenode.starter
					INNER JOIN {TABLE_PREFIX}text AS t ON t.nodeid = messagenode.nodeid
					LEFT JOIN {TABLE_PREFIX}sentto AS s ON s.nodeid = messagenode.nodeid AND s.userid <> messagenode.userid
					LEFT JOIN {TABLE_PREFIX}user AS u ON u.userid = s.userid
				WHERE messagenode.nodeid IN ({nodeid})
				ORDER BY messagenode.nodeid, u.username
			"
		],

		'fetchParticipants' => array(vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT
					node.userid, node.authorname AS username, node.starter,
					CASE WHEN list.userid IS NULL then 0 WHEN list.friend = 'pending' then 2 ELSE 1 END AS following
				FROM {TABLE_PREFIX}node AS sentbynode
				INNER JOIN {TABLE_PREFIX}closure AS closure
					ON (closure.parent = sentbynode.parentid AND closure.depth > 0)
				INNER JOIN {TABLE_PREFIX}node AS node
					ON (	node.nodeid = closure.child
						AND node.starter = sentbynode.starter
						AND node.publishdate >= sentbynode.publishdate
						AND node.userid NOT IN ({exclude})
					)
				LEFT JOIN {TABLE_PREFIX}userlist AS list
					ON (list.userid = {currentuser} AND list.relationid = node.userid AND type='follow')
				WHERE sentbynode.nodeid = {nodeid}"
		),

		/*
			The idea for this query is to update the privatemessage table for all pms associated with a particular
			user (has a record in the sentto table for that user) and no active references (records in sentto for
			any user with deleted = 0).  We also should not change the deleted date for any PM that is already
			deleted.

			Use a correlated subquery instead of NOT IN because the latter would need to return the list of
			every "sentto"
		*/
		'markUserPMsDeleted' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_UPDATE,
			'query_string' => "
				UPDATE {TABLE_PREFIX}privatemessage AS privatemessage,
					{TABLE_PREFIX}sentto AS userpms
				SET privatemessage.deleted = {deletiondate}
				WHERE privatemessage.nodeid = userpms.nodeid AND
					userpms.userid = {userid} AND
					privatemessage.deleted = 0 AND
					NOT EXISTS (
						SELECT 1
						FROM {TABLE_PREFIX}sentto AS anyusers
						WHERE privatemessage.nodeid = anyusers.nodeid AND anyusers.deleted = 0
						LIMIT 1
					)
			"
		)
		,

		'fetchNotificationOthers' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT
					sentbynode.nodeid AS sentbynode,
					node.userid,
					node.authorname AS username,
					CASE WHEN list.userid IS NULL then 0 WHEN list.friend = 'pending' then 2 ELSE 1 END AS following
				FROM {TABLE_PREFIX}node AS sentbynode
				INNER JOIN {TABLE_PREFIX}closure AS closure
					ON (closure.parent = sentbynode.parentid AND closure.depth IN ({depth}))
				INNER JOIN {TABLE_PREFIX}node AS node
					ON (	node.nodeid = closure.child
						AND node.showpublished > 0
						AND node.showapproved > 0
						AND node.starter = sentbynode.starter
						AND node.publishdate >= sentbynode.publishdate
						AND node.userid NOT IN ({exclude})
					)
				LEFT JOIN {TABLE_PREFIX}userlist AS list
					ON (list.userid = {currentuser} AND list.relationid = node.userid AND type='follow')
				WHERE sentbynode.nodeid IN ({sentbynodeids})
				ORDER BY sentbynode.nodeid, node.publishdate ASC, node.nodeid ASC
			"
		],

		'fetchNodeReactors' => array(vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT
					nodevote.nodeid AS sentbynode,
					user.userid, user.username AS username,
					CASE WHEN list.userid IS NULL then 0 WHEN list.friend = 'pending' then 2 ELSE 1 END as following
				FROM {TABLE_PREFIX}nodevote AS nodevote
				LEFT JOIN {TABLE_PREFIX}user AS user
					ON (user.userid = nodevote.whovoted)
				LEFT JOIN {TABLE_PREFIX}userlist AS list
					ON (list.userid = {currentuser} AND list.relationid = nodevote.whovoted AND type='follow')
				WHERE nodevote.nodeid IN ({sentbynodeids})
					AND nodevote.whovoted NOT IN ({exclude})
					AND nodevote.votetypeid IN ({notifyTypeids})
				ORDER BY nodevote.nodeid, nodevote.dateline DESC"
		),
		'fetchPollVoters' => array(vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT
					pollvote.nodeid AS sentbynode,
					user.userid, user.username,
					CASE WHEN list.userid IS NULL then 0 WHEN list.friend = 'pending' then 2 ELSE 1 END as following
				FROM {TABLE_PREFIX}pollvote AS pollvote
				LEFT JOIN {TABLE_PREFIX}user AS user
					ON (user.userid = pollvote.userid)
				LEFT JOIN {TABLE_PREFIX}userlist AS list
					ON (list.userid = {currentuser} AND list.relationid = pollvote.userid AND type='follow')
				WHERE pollvote.nodeid IN ({sentbynodeids})
					AND pollvote.userid NOT IN ({exclude})
				ORDER BY pollvote.nodeid, pollvote.votedate DESC"
		),
		'readNotificationsSentbynodeDescendantsOfX' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_UPDATE,
			'query_string' => "
				UPDATE {TABLE_PREFIX}notification AS notification
					INNER JOIN {TABLE_PREFIX}closure AS closure
						ON notification.sentbynodeid = closure.child
				SET notification.lastreadtime = {timenow}
				WHERE
					notification.recipient = {userid}
					AND closure.parent = {parentid}
					AND notification.typeid IN ({typeids})
		"),
		// I believe  `vote_agg_helper` (`nodeid`, `votetypeid`) index *should* help with this. However currently,
		// the IMO less useful  `enforce_single` (`nodeid`, `whovoted`, `votetypeid`) is being used in tests. It
		// might just be a factor of data size...
		'updateNodeVotes' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_UPDATE,
			'query_string' => "UPDATE `{TABLE_PREFIX}node`
				SET `votes` = (SELECT COUNT(*) FROM `{TABLE_PREFIX}nodevote` WHERE `nodeid`={nodeid} AND `votetypeid` IN ({enabledvotetypeids}))
				WHERE `nodeid` = {nodeid}"
		],
		//AdminCP - FAQ Queries
		'getDistinctProduct' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT DISTINCT product FROM {TABLE_PREFIX}phrase
			WHERE varname IN ({phraseDeleteNamesSql}) AND fieldname IN ('faqtitle', 'faqtext')"
		),
		'replaceIntoFaq' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_INSERT,
			'query_string' => "REPLACE INTO {TABLE_PREFIX}faq (faqname, faqparent, displayorder, volatile, product)
			VALUES ({faqname}, {faqparent}, {displayorder}, {volatile}, {product})"
		),
		'getDistinctProductFAQ' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT DISTINCT product FROM {TABLE_PREFIX}faq AS faq
			WHERE faqname IN ({faqnames})"
		),
		'getDistinctScriptHelp' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT DISTINCT script FROM {TABLE_PREFIX}adminhelp"
		),
		//AdminCP - USERGROUP Queries
		'getUserGroupPermissions' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT usergroup.usergroupid, usergroup.title, COUNT(permission.permissionid) AS permcount
				FROM {TABLE_PREFIX}usergroup AS usergroup
					LEFT JOIN {TABLE_PREFIX}permission AS permission ON (usergroup.usergroupid = permission.groupid)
				GROUP BY usergroup.usergroupid, usergroup.title
				HAVING permcount > 0
				ORDER BY title
			"
		],
		'getUserGroupCountById' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT COUNT(*) AS usergroups FROM {TABLE_PREFIX}usergroup
			WHERE (adminpermissions & {cancontrolpanel}) AND usergroupid <> {usergroupid}"
		),
		'updateUserOptions' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_UPDATE,
			'query_string' => "UPDATE {TABLE_PREFIX}user SET options = (options & ~{bf_misc_useroptions})
			WHERE usergroupid = {usergroupid}"
		),
		'getUserGroupId' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT usergroupid FROM {TABLE_PREFIX}usergroup WHERE genericpermissions & {bf_ugp_genericpermissions}"
		),
		'getUserIdByAdministrator' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT user.userid FROM {TABLE_PREFIX}user AS user
			LEFT JOIN {TABLE_PREFIX}administrator as administrator ON (user.userid = administrator.userid)
			WHERE administrator.userid IS NULL AND user.usergroupid = {usergroupid}"
		),
		'getUserIdNotIn' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT userid FROM {TABLE_PREFIX}user WHERE usergroupid NOT IN {ausergroupids}
			AND NOT FIND_IN_SET('{ausergroupids}', membergroupids)
			AND (usergroupid = {usergroupid} OR FIND_IN_SET('{usergroupid}', membergroupids))"
		),
		'replaceIntoPrefixPermission' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_INSERT,
			'query_string' => "REPLACE INTO {TABLE_PREFIX}prefixpermission (usergroupid, prefixid)
			SELECT {newugid}, prefixid FROM {TABLE_PREFIX}prefix
			WHERE options & {bf_misc_prefixoptions}"
		),

		'getChannelPrefixset' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT channelprefixset.nodeid, channelprefixset.prefixsetid
				FROM {TABLE_PREFIX}channelprefixset AS channelprefixset
					INNER JOIN {TABLE_PREFIX}prefixset AS prefixset ON (prefixset.prefixsetid = channelprefixset.prefixsetid)
				ORDER BY prefixset.displayorder"
		),

		'getUsergroupWithTags' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT usergroupid, opentag, closetag FROM {TABLE_PREFIX}usergroup
			WHERE opentag <> '' OR closetag <> ''"
		),

		'getUsersByMemberGroups' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT userid, username, membergroupids
			FROM {TABLE_PREFIX}user
			WHERE FIND_IN_SET('{usergroupid}', membergroupids)"
		),
		'getPrimaryUsersCount' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT user.usergroupid, COUNT(user.userid) AS total
			FROM {TABLE_PREFIX}user AS user
			LEFT JOIN {TABLE_PREFIX}usergroup AS usergroup USING (usergroupid)
			WHERE usergroup.usergroupid IS NOT NULL
			GROUP BY usergroupid"
		),
		'updateUserMemberGroupsByUserId' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_UPDATE,
			'query_string' => "UPDATE {TABLE_PREFIX}user SET
			membergroupids = IF(membergroupids = '', {usergroupid}, CONCAT(membergroupids, ',{usergroupid}'))
			WHERE userid IN ({auth})"
		),
		'reactions_fetchwhovoted' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT
					`user`.`userid`,
					`user`.`username`,
					`user`.`displayname`,
					`user`.`infractiongroupid`,
					IF(`user`.`displaygroupid` = 0, `user`.`usergroupid`, `user`.`displaygroupid`) AS `displaygroupid`,
					`user`.`usertitle`,
					`user`.`customtitle`
				FROM `{TABLE_PREFIX}nodevote` AS `nodevote`
				INNER JOIN `{TABLE_PREFIX}user` AS `user` ON (`user`.`userid` = `nodevote`.`whovoted`)
				WHERE `nodevote`.`nodeid` = {nodeid} AND `nodevote`.`votetypeid` = {votetypeid}
				ORDER BY `nodevote`.`dateline` DESC
			"
		),
		'getDeletedMsgs' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT pm.nodeid
					FROM {TABLE_PREFIX}privatemessage AS pm
					WHERE pm.deleted > 0 AND pm.deleted <= {deleteLimit}
					LIMIT {#limit}"
		),
		'deleteSentMessagesForUser' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_UPDATE,
				'query_string' => "
					UPDATE {TABLE_PREFIX}sentto AS sentto, {TABLE_PREFIX}node AS node
					SET sentto.deleted = 1
					WHERE sentto.nodeid = node.nodeid AND node.userid = {userid} AND
						node.contenttypeid={contenttypeid}
				"
		),
		//AdminCP - IMAGES Queries
		'fetchUsergroupImageCategories' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT usergroup.*, imagecategoryid AS nopermission FROM {TABLE_PREFIX}usergroup AS usergroup
			LEFT JOIN {TABLE_PREFIX}imagecategorypermission AS imgperm ON
			(imgperm.usergroupid = usergroup.usergroupid AND imgperm.imagecategoryid = {imagecategoryid})
			ORDER BY title"
		),
		'fetchSmilieId' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT smilieid
			FROM {TABLE_PREFIX}smilie WHERE BINARY smilietext = {smilietext}"
		),

		'fetchAvatarsPermissions' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT imagecategory.imagecategoryid, COUNT(avatarid) AS avatars
				FROM {TABLE_PREFIX}imagecategory AS imagecategory
					LEFT JOIN {TABLE_PREFIX}avatar AS avatar ON (avatar.imagecategoryid=imagecategory.imagecategoryid)
				WHERE imagetype = 1
				GROUP BY imagecategory.imagecategoryid
				HAVING avatars > 0
			"
		],

		'fetchImagesWithoutPermissions' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT usergroupid, COUNT(*) AS count
				FROM {TABLE_PREFIX}imagecategorypermission
				WHERE imagecategoryid IN ({cats})
				GROUP BY usergroupid
				HAVING count = {catsCount}
			"
		],

		'updateSettingValues' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_UPDATE,
			'query_string' => '
				UPDATE {TABLE_PREFIX}setting SET value =
				CASE varname
					WHEN {path} THEN {imagepath}
					WHEN {url} THEN {imageurl}
					ELSE value
				END
				WHERE varname IN({path}, {url})
			'
		],

		'fetchAvatarsForUsers' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => '
				SELECT
					`user`.`userid`,
					`user`.`displayname`,
					`avatar`.`avatarid`,
					`avatar`.`avatarpath`,
					`user`.`avatarrevision`,
					`customavatar`.`dateline`,
					`customavatar`.`filename`
				FROM `{TABLE_PREFIX}user` AS `user`
					LEFT JOIN `{TABLE_PREFIX}customavatar` AS `customavatar` ON `customavatar`.`userid` = `user`.`userid`
					LEFT JOIN `{TABLE_PREFIX}avatar` AS `avatar` ON `avatar`.`avatarid` = `user`.`avatarid`
				WHERE `user`.`userid` IN ({userid})
			'
		],

		// The use of afilename, afiledata, etc are an artifact of when this use to also have sigpic data with
		// sfilename, etc. Should clean that up but requires changing the caller as well.
		'fetchAvatarInfo' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT
					`user`.`userid`,
					`user`.`avatarrevision`,
					`user`.`sigpicrevision`,
					`customavatar`.`filename` AS `afilename`,
					`customavatar`.`filedata` AS `afiledata`,
					`customavatar`.`extension` AS `aextension`,
					`customavatar`.`filedata_thumb` AS `afiledata_thumb`
				FROM `{TABLE_PREFIX}user` AS `user`
					LEFT JOIN `{TABLE_PREFIX}customavatar` AS `customavatar` ON (`user`.`userid` = `customavatar`.`userid`)
				WHERE NOT ISNULL(`customavatar`.`userid`)
				ORDER BY `user`.`userid` ASC
				LIMIT {#limit_start}, {#limit}
			"
		],

		'fetchUserIdByAvatar' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT `user`.`userid`
				FROM `{TABLE_PREFIX}user` AS `user`
					LEFT JOIN `{TABLE_PREFIX}customavatar` AS `customavatar` ON (`user`.`userid` = `customavatar`.`userid`)
				WHERE `user`.`userid` > {lastuser} AND (NOT ISNULL(`customavatar`.`userid`))
				LIMIT 1
			"
		],

		// fetchHardAutoExpireTopicsForChannel & fetchSoftAutoExpireTopicsForChannel notes:
		// These are split up so that we can leverage indices on node.publishdate & node.lastcontent separately
		// (vs. previously doing a
		//     (c.topicexpiretype = 'hard' AND (node.publishdate + c.topicexpireseconds) <= {timenow}
		//      OR
		//      c.topicexpiretype = 'soft' AND (node.lastcontent + c.topicexpireseconds) <= {timenow})
		// ).
		// Also, we enforce a lower-bound against the publishdate/lastcontent via lookback_cutoff so that we
		// avoid the worst case of going through a channel's entire node list when they first turn this on.
		// Furthermore, we call this one channel at a time (node.parentid), so that we can avoid doing math
		// per-joined-row, since each channel's topicexpireseconds can be different. And since we grab topics
		// for one channel at a time, we can completely skip a channel join here.
		// We're trading off having to do multiple queries (db roundtrips) for each channel in exchange for
		// a much simpler / more easily optimized query plan on queries that can filter on the indexed
		// publishdate and lastcontent fields.
		'fetchHardAutoExpireTopicsForChannel' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT `node`.`nodeid`
				FROM `{TABLE_PREFIX}node` AS `node`
				WHERE `node`.`parentid` = {parentid}
					AND `node`.`nodeid` = `node`.`starter`
					AND `node`.`open` = 1
					AND `node`.`publishdate` <= {cutoff}
					AND `node`.`publishdate` >= {lookback_cutoff}
				"
		],
		'fetchSoftAutoExpireTopicsForChannel' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT `node`.`nodeid`
				FROM `{TABLE_PREFIX}node` AS `node`
				WHERE `node`.`parentid` = {parentid}
					AND `node`.`nodeid` = `node`.`starter`
					AND `node`.`open` = 1
					AND `node`.`lastcontent` <= {cutoff}
					AND `node`.`lastcontent` >= {lookback_cutoff}
				"
		],

		'closeNode' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_UPDATE,
			'query_string' => "
				UPDATE {TABLE_PREFIX}closure AS c
				INNER JOIN {TABLE_PREFIX}node AS n ON n.nodeid = c.child
				SET n.showopen = 0
				WHERE c.parent = {nodeid}
			"
		],

		'openNodeInitial' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_UPDATE,
			'query_string' => "
				UPDATE `{TABLE_PREFIX}node` AS `node`
				JOIN `{TABLE_PREFIX}node` AS `parent` ON (`node`.`parentid` = `parent`.`nodeid`)
				SET `node`.`open` = 1,
					`node`.`showopen` = `parent`.`showopen`
				WHERE `node`.`nodeid` IN ({nodeids})
			",
		],

		'setShowOpenLevel' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_UPDATE,
			'query_string' => "
				UPDATE `{TABLE_PREFIX}closure` AS `closure`
					JOIN `{TABLE_PREFIX}node` AS `node` ON `node`.`nodeid` = `closure`.`child`
					JOIN `{TABLE_PREFIX}node` AS `parent` ON (`node`.`parentid` = `parent`.`nodeid`)
				SET `node`.`showopen` = IF (`node`.`open` = 1, `parent`.`showopen`, 0)
				WHERE `closure`.`parent` = {nodeid} AND `closure`.`depth` = {depth}
			",
		],

		'unapproveNode' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_UPDATE,
			'query_string' => "
				UPDATE {TABLE_PREFIX}closure AS c
				INNER JOIN {TABLE_PREFIX}node AS n ON n.nodeid = c.child
				SET n.showapproved = 0
				WHERE c.parent IN ({nodeid})
			"
		],

		// approve Node
		'approveNode' => array(
				vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_UPDATE,
				'query_string' => "
					UPDATE {TABLE_PREFIX}closure AS c INNER JOIN
						{TABLE_PREFIX}node AS n ON n.nodeid = c.child LEFT JOIN
						(
							SELECT DISTINCT child.child AS nodeid
							FROM {TABLE_PREFIX}closure AS child
							INNER JOIN  {TABLE_PREFIX}closure AS parent ON parent.child = child.child AND child.parent IN ({nodeid})
							INNER JOIN {TABLE_PREFIX}node AS chknode ON chknode.nodeid = parent.parent AND chknode.approved = 0
						) AS chk ON chk.nodeid = n.nodeid
					SET n.showapproved = 1
					WHERE c.parent IN ({nodeid}) AND chk.nodeid IS NULL AND n.approved = 1"
		),

		//AdminCP - TAG Queries
		'getTagsBySynonym' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT t.tagtext, p.tagtext as canonicaltagtext
			FROM {TABLE_PREFIX}tag t JOIN {TABLE_PREFIX}tag p ON t.canonicaltagid = p.tagid
			WHERE t.tagtext IN ({tags})"
		),
		'insertIgnoreTagContent2' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_INSERT,
			'query_string' => "INSERT IGNORE INTO {TABLE_PREFIX}tagnode (nodeid, tagid, userid, dateline)
			VALUES({id}, {tagid}, {userid}, {time})"
		),
		'getContentCounts' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT
				SUM(CASE WHEN showpublished = 1 AND showapproved = 1 AND node.parentid = {parentid} THEN 1 ELSE 0 END) AS textcount,
				SUM(CASE WHEN (showpublished = 0 OR showapproved = 0) AND node.parentid = {parentid} THEN 1 ELSE 0 END) AS textunpubcount,
				SUM(CASE WHEN showpublished = 1 AND showapproved = 1 THEN 1 ELSE 0 END) AS totalcount,
				SUM(CASE WHEN (showpublished = 0 OR showapproved = 0) THEN 1 ELSE 0 END) AS totalunpubcount
				FROM {TABLE_PREFIX}node AS node INNER JOIN {TABLE_PREFIX}closure AS cl ON cl.child = node.nodeid
				WHERE node.contenttypeid NOT IN ({excludeTypes})
				AND cl.parent = {parentid} AND node.nodeid <> {parentid}"
		),
		'getDirectContentCount' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT COUNT(*)
				FROM {TABLE_PREFIX}node AS node INNER JOIN {TABLE_PREFIX}closure AS cl ON cl.child = node.nodeid AND cl.depth = 1
				WHERE node.contenttypeid NOT IN ({excludeTypes})
				AND cl.parent = {parentid} AND node.nodeid <> {parentid}"
		),
		'fetchQuestions' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT question.questionid, question.regex, question.dateline, COUNT(*) AS answers, phrase.text, answer.answerid
			FROM {TABLE_PREFIX}hvquestion AS question
			LEFT JOIN {TABLE_PREFIX}phrase AS phrase ON (phrase.varname = CONCAT('question', question.questionid) AND phrase.fieldname = 'hvquestion' and languageid = 0)
			LEFT JOIN {TABLE_PREFIX}hvanswer AS answer ON (question.questionid = answer.questionid)
			GROUP BY question.questionid
			ORDER BY dateline"
		),
		'fetchQuestionById' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT question.questionid, question.regex, question.dateline, phrase.text
			FROM {TABLE_PREFIX}hvquestion AS question
			LEFT JOIN {TABLE_PREFIX}phrase AS phrase ON (phrase.varname = CONCAT('question', question.questionid) AND phrase.fieldname = 'hvquestion' and languageid = 0)
			LEFT JOIN {TABLE_PREFIX}hvanswer AS answer ON (question.questionid = answer.questionid)
			WHERE question.questionid = {questionid}"
		),
		'fetchQuestionByAnswer' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT question.questionid, phrase.text
			FROM {TABLE_PREFIX}hvquestion AS question
			LEFT JOIN {TABLE_PREFIX}phrase AS phrase ON (phrase.varname = CONCAT('question', question.questionid) AND phrase.fieldname = 'hvquestion' and languageid = 0)
			WHERE question.questionid = {questionid}"
		),
		'fetchQuestionByPhrase' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT questionid, phrase.text
			FROM {TABLE_PREFIX}hvquestion AS question
			LEFT JOIN {TABLE_PREFIX}phrase AS phrase ON (phrase.varname = CONCAT('question', question.questionid) AND phrase.fieldname = 'hvquestion' and languageid = 0)
			WHERE questionid = {questionid}"
		),
		'fetchAttachStatsAvarage' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT COUNT(*) AS count, SUM(filesize) AS totalsize, SUM(counter) AS downloads
			FROM {TABLE_PREFIX}attach AS a
			INNER JOIN {TABLE_PREFIX}filedata AS fd ON (a.filedataid = fd.filedataid)"
		),
		'fetchAttachStatsTotal' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT COUNT(*) AS count, SUM(filesize) AS totalsize
			FROM {TABLE_PREFIX}filedata AS fd"
		),
		'fetchAttachStatsLargestUser' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT COUNT(*) AS count, SUM(filesize) AS totalsize, user.userid, username
			FROM {TABLE_PREFIX}attach AS a
			INNER JOIN {TABLE_PREFIX}filedata AS fd ON (a.filedataid = fd.filedataid)
			LEFT JOIN {TABLE_PREFIX}user AS user ON (fd.userid = user.userid)
			GROUP BY fd.userid
			HAVING totalsize > 0
			ORDER BY totalsize DESC
			LIMIT 5"
		),
		'fetchTopAttachmentsCounter' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT a.nodeid, node.parentid, a.counter, a.filedataid, fd.dateline, a.filename, node.userid, node.authorname
			FROM {TABLE_PREFIX}attach AS a
			INNER JOIN {TABLE_PREFIX}filedata AS fd ON (a.filedataid = fd.filedataid)
			LEFT JOIN {TABLE_PREFIX}attachmenttype AS at ON (at.extension = fd.extension)
			INNER JOIN {TABLE_PREFIX}node AS node ON (node.nodeid = a.nodeid)
			LEFT JOIN {TABLE_PREFIX}user AS user ON (fd.userid = user.userid)
			ORDER BY a.counter DESC
			LIMIT 5"
		),
		'fetchTopAttachmentsSize' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT a.nodeid, node.parentid, fd.filesize, a.filedataid, fd.dateline, a.filename, node.userid, node.authorname
			FROM {TABLE_PREFIX}attach AS a
			INNER JOIN {TABLE_PREFIX}filedata AS fd ON (a.filedataid = fd.filedataid)
			LEFT JOIN {TABLE_PREFIX}attachmenttype AS at ON (at.extension = fd.extension)
			INNER JOIN {TABLE_PREFIX}node AS node ON (node.nodeid = a.nodeid)
			LEFT JOIN {TABLE_PREFIX}user AS user ON (fd.userid = user.userid)
			ORDER BY fd.filesize DESC
			LIMIT 5"
		),
		'fetchAttach' => array(
				vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
				'query_string' => "SELECT node.*, fd.filesize, a.filedataid, a.filename, a.visible, a.counter
				FROM {TABLE_PREFIX}node AS node
				INNER JOIN {TABLE_PREFIX}attach AS a ON (node.nodeid = a.nodeid)
				INNER JOIN {TABLE_PREFIX}filedata AS fd ON (a.filedataid = fd.filedataid)
				WHERE node.nodeid = {nodeid}"
		),
		'fetchAttachPerms' => array(
				vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
				'query_string' => "SELECT attachmentpermission.*
				FROM {TABLE_PREFIX}attachmentpermission AS attachmentpermission
				INNER JOIN {TABLE_PREFIX}usergroup AS usergroup ON (usergroup.usergroupid = attachmentpermission.usergroupid)
				WHERE attachmentpermissionid = {attachmentpermissionid}"
		),
		'fetchAttachPermsByExtension' => array(
				vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
				'query_string' => "SELECT atype.extension, atype.height AS default_height,
					atype.width AS default_width,
					atype.size AS default_size,
					atype.mimetype AS mimetype,
					aperm.height AS custom_height,
					aperm.width AS custom_width,
					aperm.size AS custom_size,
					aperm.attachmentpermissions AS custom_permissions, aperm.usergroupid
				FROM {TABLE_PREFIX}attachmenttype AS atype
				LEFT JOIN {TABLE_PREFIX}attachmentpermission AS aperm
				ON atype.extension = aperm.extension
				WHERE atype.extension={extension}"
		),
		'replaceAttachPerms' => array(
				vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_INSERT,
				'query_string' => "REPLACE INTO {TABLE_PREFIX}attachmentpermission
			(usergroupid, extension, attachmentpermissions, height, width, size)
			VALUES
			({usergroupid}, {extension}, {attachmentpermissions}, {height}, {width}, {size})
			"
		),
		'fetchAllAttachPerms' => array(
				vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
				'query_string' => "SELECT
					atype.extension, atype.height AS default_height, atype.width AS default_width, atype.size AS default_size, atype.contenttypes,
					aperm.height AS custom_height, aperm.width AS custom_width, aperm.size AS custom_size,
					aperm.attachmentpermissions AS custom_permissions, aperm.usergroupid
				FROM {TABLE_PREFIX}attachmenttype AS atype
				LEFT JOIN {TABLE_PREFIX}attachmentpermission AS aperm USING (extension)
				ORDER BY extension"
		),
		'fetchMinFiledataId' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT MIN(filedataid) AS min FROM {TABLE_PREFIX}filedata"
		),
		'fetchTotalAttach' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT COUNT(*) AS count FROM {TABLE_PREFIX}filedata"
		),

		'fetchCronByDate' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT cron.*
			FROM {TABLE_PREFIX}cron AS cron
			LEFT JOIN {TABLE_PREFIX}product AS product ON (cron.product = product.productid)
			WHERE cron.nextrun <= {date} AND cron.active = 1
			AND (product.productid IS NULL OR product.active = 1)
			ORDER BY cron.nextrun
			LIMIT 1"
		),

		'fetchActiveChannelContributors' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT git.userid, u.username, u.displayname, ug.systemgroupid, ug.usergroupid
			FROM {TABLE_PREFIX}groupintopic git
			INNER JOIN {TABLE_PREFIX}usergroup ug ON (git.groupid = ug.usergroupid)
			INNER JOIN {TABLE_PREFIX}user u ON (git.userid = u.userid)
			WHERE git.nodeid = {nodeid}
			ORDER BY u.username"
		),

		'fetchPendingChannelContributors' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT pm.about, s.userid as recipientid, u.username, u.displayname
			FROM {TABLE_PREFIX}privatemessage pm
			INNER JOIN {TABLE_PREFIX}sentto s ON (pm.nodeid = s.nodeid)
			INNER JOIN {TABLE_PREFIX}user u ON (s.userid = u.userid)
			INNER JOIN {TABLE_PREFIX}node n ON (pm.nodeid = n.nodeid)
			WHERE pm.aboutid = {nodeid} AND pm.msgtype = 'request'
			AND pm.about in ('owner_to', 'moderator_to', 'owner_from', 'moderator',
							 'sg_owner_to', 'sg_moderator_to', 'sg_owner_from', 'sg_moderator')
				AND s.userid != n.userid
			ORDER BY u.username"
		),

		'fetchPendingChannelRequestUser' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT pm.nodeid
			FROM {TABLE_PREFIX}privatemessage pm
			INNER JOIN {TABLE_PREFIX}sentto s ON (pm.nodeid = s.nodeid)
			INNER JOIN {TABLE_PREFIX}user u ON (s.userid = u.userid)
			INNER JOIN {TABLE_PREFIX}node n ON (pm.nodeid = n.nodeid)
			WHERE pm.aboutid = {aboutid}
				AND s.userid != n.userid
				AND s.userid = {userid}
				AND pm.about IN ({about})
			ORDER BY u.username"
		),

		'updateNodePerms' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_UPDATE,
			'query_string' => "UPDATE {TABLE_PREFIX}node AS n
			INNER JOIN {TABLE_PREFIX}closure AS cl ON cl.child = n.nodeid AND cl.parent IN({nodeid})
			SET n.viewperms = {viewperms}, n.commentperms = {commentperms}"
		),

		'groupintopicCount' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT
				COUNT(DISTINCT userid) AS members, nodeid
				FROM {TABLE_PREFIX}groupintopic
				WHERE groupid IN({groupid}) AND nodeid IN ({nodeid})
				GROUP BY nodeid
			"
		],

		'groupintopicPage' => array(vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			// GROUP_CONCAT supports sorting, but we sort the groupids in PHP
			// because we can't rely on the three blog groups being 9, 10, and 11.
			'query_string' => "
				SELECT u.userid, u.username, u.displayname,
					GROUP_CONCAT(DISTINCT g.groupid SEPARATOR ',') AS groupids
				FROM {TABLE_PREFIX}groupintopic AS g
				INNER JOIN {TABLE_PREFIX}user AS u ON (u.userid = g.userid)
				WHERE g.nodeid = {nodeid} AND g.groupid IN({groupid})
				GROUP BY u.userid, u.username
				ORDER BY u.username
				LIMIT {#limit_start}, {#limit}
			"
		),

		//AdminCP - ADMINLOG Queries
		'fetchDistinctScript' => array(
				vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
				'query_string' => "SELECT DISTINCT adminlog.script
				FROM {TABLE_PREFIX}adminlog AS adminlog
				ORDER BY script"
		),
		'fetchDistinctUsers' => array(
				vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
				'query_string' => "SELECT DISTINCT adminlog.userid, user.username
				FROM {TABLE_PREFIX}adminlog AS adminlog
				LEFT JOIN {TABLE_PREFIX}user AS user USING(userid)
				ORDER BY username"
		),
		// get nodes with attachments
		'fetchNodesWithAttachments' => array(
		vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT DISTINCT parent.nodeid FROM
				{TABLE_PREFIX}closure AS cl
				INNER JOIN {TABLE_PREFIX}node AS parent ON parent.nodeid = cl.child
				INNER JOIN {TABLE_PREFIX}node AS image ON image.parentid = parent.nodeid
				WHERE image.contenttypeid in ({contenttypeid}) AND cl.parent IN({channel})"
		),
		// get Albums in a channel
		'fetchGalleriesInChannel' => array(
		vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT DISTINCT node.nodeid FROM
				{TABLE_PREFIX}closure AS cl
				INNER JOIN {TABLE_PREFIX}node AS node ON node.nodeid = cl.child
				WHERE node.contenttypeid in ({contenttypeid}) AND cl.parent IN({channel})"
		),
		'fetchAttachInfo' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT
					fd.filedataid, fd.dateline, fdr.resize_dateline, fd.filesize, IF(fdr.resize_filesize > 0, 1, 0) AS hasthumbnail, fdr.resize_filesize, fd.userid,
					a.nodeid, a.counter, a.filename, a.settings, a.visible, a.caption,
					n.showpublished, n.parentid, n.title
				FROM {TABLE_PREFIX}attach AS a
				INNER JOIN {TABLE_PREFIX}node AS n ON n.nodeid = a.nodeid
				INNER JOIN {TABLE_PREFIX}filedata AS fd ON fd.filedataid = a.filedataid
				LEFT JOIN {TABLE_PREFIX}filedataresize AS fdr ON (fd.filedataid = fdr.filedataid AND fdr.resize_type = 'thumb')
				WHERE n.parentid IN ({parentId}) AND n.contenttypeid IN ({contenttypeid})"
		),
		// needed for print_delete_confirmation [START]
		'getModeratorBasicFields' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT moderator.moderatorid, user.username, node.title
				FROM {TABLE_PREFIX}moderator AS moderator
				INNER JOIN {TABLE_PREFIX}user AS user ON (moderator.userid = user.userid)
				INNER JOIN {TABLE_PREFIX}node AS node ON (node.nodeid = moderator.nodeid)
				WHERE moderatorid = {moderatorid}"
		),
		'getCalendarModeratorBasicFields' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT calendarmoderatorid, username, title
				FROM {TABLE_PREFIX}calendarmoderator AS calendarmoderator
				INNER JOIN {TABLE_PREFIX}user AS user ON (user.userid = calendarmoderator.userid)
				INNER JOIN {TABLE_PREFIX}calendar AS calendar ON (calendar.calendarid = calendarmoderator.calendarid)
				WHERE calendarmoderatorid = {calendarmoderatorid}"
		),
		'getAdminHelpBasicFields' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT adminhelpid, phrase.text AS title
				FROM {TABLE_PREFIX}adminhelp AS adminhelp
				LEFT JOIN {TABLE_PREFIX}phrase AS phrase ON (phrase.varname = CONCAT(adminhelp.script, IF(adminhelp.action != '', CONCAT('_', REPLACE(adminhelp.action, ',', '_')), ''), IF(adminhelp.optionname != '', CONCAT('_', adminhelp.optionname), ''), '_title') AND phrase.fieldname = 'cphelptext' AND phrase.languageid IN (-1, 0))
				WHERE adminhelpid = {adminhelpid}
			"
		),
		'getFaqBasicFields' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT faqname, IF(phrase.text IS NOT NULL, phrase.text, faq.faqname) AS title
				FROM {TABLE_PREFIX}faq AS faq
				LEFT JOIN {TABLE_PREFIX}phrase AS phrase ON (phrase.varname = faq.faqname AND phrase.fieldname = 'faqtitle' AND phrase.languageid IN(-1, 0))
				WHERE faqname = {faqname}
			"
		),
		// needed for print_delete_confirmation [END]

		// admincp - index [START]
		'getFiledataFilesizeSum' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT SUM(filesize) AS size FROM {TABLE_PREFIX}filedata
			"
		),
		'getUserFiledataFilesizeSum' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT SUM(filesize) AS size FROM {TABLE_PREFIX}filedata WHERE userid = {userid}
			"
		),
		'getChangedTemplatesCount' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT count(*) AS count
				FROM {TABLE_PREFIX}template AS tCustom
				INNER JOIN {TABLE_PREFIX}template AS tGlobal ON
					(tGlobal.styleid = -1 AND tGlobal.title = tCustom.title)
				LEFT JOIN {TABLE_PREFIX}templatemerge AS templatemerge ON
					(templatemerge.templateid = tCustom.templateid)
				WHERE tCustom.styleid <> -1
					AND tCustom.templatetype = 'template' AND tCustom.mergestatus IN ('merged', 'conflicted')
				ORDER BY tCustom.title
			"
		),
		// admincp - index [END]

		// admincp - image [START]
		'getSmilieTextCmp' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT smilieid
				FROM {TABLE_PREFIX}smilie
				WHERE BINARY smilietext = {smilietext}
			"
		),
		// admincp - image [END]

		// admincp - moderator [START]
		'getModGlobalEdit' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT user.username, user.userid,
				moderator.nodeid, moderator.permissions, moderator.permissions2, moderator.moderatorid
				FROM {TABLE_PREFIX}user AS user
				LEFT JOIN {TABLE_PREFIX}moderator AS moderator ON (moderator.userid = user.userid AND moderator.nodeid = 0)
				WHERE user.userid = {userid}
			"
		),
		'getModeratorInfoToUpdate' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT moderator.*,
				user.username, user.usergroupid, user.membergroupids
				FROM {TABLE_PREFIX}moderator AS moderator
				INNER JOIN {TABLE_PREFIX}user AS user USING(userid)
				WHERE moderator.moderatorid = {moderatorid}
			"
		),
		'getSuperGroups' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT user.*, usergroup.usergroupid
				FROM {TABLE_PREFIX}usergroup AS usergroup
				INNER JOIN {TABLE_PREFIX}user AS user ON(user.usergroupid = usergroup.usergroupid OR FIND_IN_SET(usergroup.usergroupid, user.membergroupids))
				WHERE (usergroup.adminpermissions & {ismodpermission})
				GROUP BY user.userid
				ORDER BY user.username
			"
		),
		'getModsFromNodeShowList' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT moderator.moderatorid, user.userid, user.username, user.lastactivity, node.nodeid, node.htmltitle, node.routeid
				FROM {TABLE_PREFIX}node AS node
				INNER JOIN {TABLE_PREFIX}moderator AS moderator ON (moderator.nodeid = node.nodeid)
				INNER JOIN {TABLE_PREFIX}user AS user ON (user.userid = moderator.userid)
				ORDER BY user.username, node.htmltitle
			"
		),
		'checkUserMod' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT username FROM {TABLE_PREFIX}moderator AS moderator
				LEFT JOIN {TABLE_PREFIX}user AS user USING(userid)
				WHERE moderator.userid = {userid}
			"
		),
		'getModUserInfoKillAll' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT user.*,
				IF (user.displaygroupid = 0, user.usergroupid, user.displaygroupid) AS displaygroupid
				FROM {TABLE_PREFIX}moderator AS moderator
				LEFT JOIN {TABLE_PREFIX}user AS user USING(userid)
				WHERE moderator.userid = {userid}
					AND nodeid <> -1
			"
		),
		// admincp - moderator [END]

		// admincp - notice [START]
		'doNoticeSwap' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_UPDATE,
			'query_string' => "
			UPDATE {TABLE_PREFIX}notice
			SET displayorder = CASE noticeid
				WHEN {orig_noticeid} THEN {swap_displayorder}
				WHEN {swap_noticeid} THEN {orig_displayorder}
				ELSE displayorder END
			WHERE noticeid IN({orig_noticeid}, {swap_noticeid})
			"
		),
		// admincp - notice [END]

		// admincp - permission [START]
		'getChannelPermissionsByGroup' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
			SELECT permission.*
			FROM {TABLE_PREFIX}permission AS permission
			INNER JOIN {TABLE_PREFIX}closure AS closure ON closure.parent = permission.nodeid
			WHERE permission.groupid IN ({groupid}) AND closure.child IN ({nodeid})
			ORDER BY closure.depth ASC LIMIT 1
			"
		),
		'getChannelPermissionsForAllGroups' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
			SELECT permission.*
			FROM {TABLE_PREFIX}permission AS permission
			INNER JOIN {TABLE_PREFIX}closure AS closure ON closure.parent = permission.nodeid
			WHERE closure.child IN ({nodeid})
			ORDER BY permission.groupid ASC, closure.depth ASC
			"
		),
		// admincp - permission [END]

		// admincp - rssfeed [START]
		'getUserRssFeed' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
			SELECT rssfeed.*, user.username
			FROM {TABLE_PREFIX}rssfeed AS rssfeed
			INNER JOIN {TABLE_PREFIX}user AS user ON(user.userid = rssfeed.userid)
			WHERE rssfeed.rssfeedid = {rssfeedid}
			"
		),
		'getRssFeedsDetailed' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
			SELECT rssfeed.*, user.username, channel.title AS channeltitle
			FROM {TABLE_PREFIX}rssfeed AS rssfeed
			LEFT JOIN {TABLE_PREFIX}user AS user ON(user.userid = rssfeed.userid)
			LEFT JOIN {TABLE_PREFIX}node AS channel ON(channel.nodeid = rssfeed.nodeid)
			ORDER BY rssfeed.title
			"
		),
		// admincp - rssfeed [END]

		'getDescendantAttachCount' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT cl.parent AS nodeid, count(a.nodeid) AS count
				FROM {TABLE_PREFIX}closure AS cl
				INNER JOIN {TABLE_PREFIX}attach AS a ON (a.nodeid = cl.child)
				WHERE cl.parent in ({nodeid})
				GROUP BY cl.parent
			"),
		'getDescendantPhotoCount' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT cl.parent AS nodeid, count(child.nodeid) AS count
				FROM {TABLE_PREFIX}closure AS cl
				INNER JOIN {TABLE_PREFIX}node AS child USE INDEX (nodeid, node_ctypid_userid_dispo_idx) ON (child.nodeid = cl.child)
				WHERE cl.parent in ({nodeid}) AND child.contenttypeid = {photoTypeid}
				GROUP BY cl.parent
			"),

		// admincp - stylevar [START]
		// Keep the ORDER BY clause in sync with the sorting in fetch_stylevars_array().
		'getExistingStylevars' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
			SELECT stylevardfn.*, stylevar.styleid AS stylevarstyleid, stylevar.value
			FROM {TABLE_PREFIX}stylevardfn AS stylevardfn
			LEFT JOIN {TABLE_PREFIX}stylevar AS stylevar ON(stylevardfn.stylevarid = stylevar.stylevarid)
			WHERE stylevardfn.stylevarid IN ({stylevarids})
			ORDER BY
				CASE
					WHEN stylevardfn.stylevargroup IN ('Global', 'GlobalPalette')
					THEN 1
					ELSE 2
				END,
				stylevardfn.stylevargroup,
				stylevardfn.stylevarid
			"
		),
		'getStylevarsToRevert' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
			SELECT DISTINCT s1.stylevarid
			FROM {TABLE_PREFIX}stylevar AS s1
			INNER JOIN {TABLE_PREFIX}stylevar AS s2 ON
				(s2.styleid IN ({parentlist}) AND s2.styleid <> {styleid} AND s2.stylevarid = s1.stylevarid)
			WHERE s1.styleid = {styleid}
			"
		),
		'getStylevarGroups' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
			SELECT DISTINCT (stylevargroup)
			FROM {TABLE_PREFIX}stylevardfn AS stylevardfn
			"
		),
		// admincp - stylevar [END]

		// admincp - subscriptionpermission [START]
		'getSubscriptionPermissionInfo' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
			SELECT subscriptionpermission.*
			FROM {TABLE_PREFIX}subscriptionpermission AS subscriptionpermission
			INNER JOIN {TABLE_PREFIX}usergroup AS usergroup ON (usergroup.usergroupid = subscriptionpermission.usergroupid)
			WHERE subscriptionid = {subscriptionid} AND subscriptionpermission.usergroupid = {usergroupid}
			"
		),
		// admincp - subscriptionpermission [END]

		// admincp - subscriptions [START]
		'getSubscriptionLogCount' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
			SELECT COUNT(*) as total, subscriptionid
			FROM {TABLE_PREFIX}subscriptionlog
			GROUP BY subscriptionid
			"
		),
		'getActiveSubscriptionLogCount' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
			SELECT COUNT(*) as total, subscriptionid
			FROM {TABLE_PREFIX}subscriptionlog
			WHERE status = 1
			GROUP BY subscriptionid
			"
		),
		// admincp - subscriptions [END]

		// admincp - usertools [START]
		'getAvatarLimit' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
			SELECT *
			FROM {TABLE_PREFIX}avatar
			ORDER BY title LIMIT {startat}, {perpage}
			"
		),
		'getUserPmFolders' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
			SELECT user.userid, user.username, folder.*
			FROM {TABLE_PREFIX}user AS user
			INNER JOIN {TABLE_PREFIX}messagefolder AS folder ON user.userid = folder.userid
			WHERE user.userid = {userid}
			"
		),
		'getUserPmFoldersCount' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
			SELECT COUNT(*) AS messages, folderid
			FROM {TABLE_PREFIX}sentto
			WHERE userid = {userid}
			GROUP BY folderid
			"
		),
		// admincp - usertools [END]
		'getOtherParticipants' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT parent.nodeid, count(distinct child.userid) AS qty FROM {TABLE_PREFIX}node AS parent
			INNER JOIN {TABLE_PREFIX}closure AS cl ON cl.parent = parent.nodeid AND cl.child <> parent.nodeid
			INNER JOIN {TABLE_PREFIX}node AS child ON child.nodeid = cl.child
			WHERE parent.nodeid IN ({nodeids})
			GROUP BY parent.nodeid"),

		'getParticipantsList' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT child.parentid AS parent, child.nodeid, child.userid, user.username, pm.about
				FROM {TABLE_PREFIX}privatemessage AS pm
					INNER JOIN {TABLE_PREFIX}node AS notification ON notification.nodeid = pm.nodeid
					INNER JOIN {TABLE_PREFIX}node AS last_post ON last_post.nodeid = pm.aboutid
					INNER JOIN {TABLE_PREFIX}closure AS cl ON cl.parent = last_post.parentid AND depth = 1
					INNER JOIN {TABLE_PREFIX}node AS child ON cl.child = child.nodeid
					INNER JOIN {TABLE_PREFIX}user as user ON user.userid = child.userid
				WHERE pm.aboutid IN ({nodeids}) AND
					child.nodeid <> child.starter AND child.publishdate >= notification.publishdate
				GROUP BY child.nodeid, pm.about
				ORDER BY child.nodeid DESC
			",
		],

		'getNodePendingRequest' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT pm.nodeid, pm.aboutid
				FROM {TABLE_PREFIX}privatemessage AS pm
					INNER JOIN {TABLE_PREFIX}node AS msg ON msg.nodeid = pm.nodeid
				WHERE pm.aboutid IN ({nodeid}) AND msg.userid IN ({userid}) AND pm.about IN ({request})
			",
		],

		'getExistingRequest' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
			SELECT pm.nodeid, msg.userid
			FROM {TABLE_PREFIX}privatemessage AS pm
			INNER JOIN {TABLE_PREFIX}sentto AS msg ON msg.nodeid = pm.nodeid
			WHERE pm.aboutid IN({nodeid}) AND msg.userid IN ({userid}) AND pm.about = {request}
			"
		),
		'getPendingRequestsForNodes' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT userlist.*
				FROM {TABLE_PREFIX}privatemessage AS privatemessage
					INNER JOIN {TABLE_PREFIX}sentto AS sentto ON sentto.nodeid = sentto.nodeid
					INNER JOIN {TABLE_PREFIX}userlist AS userlist ON userlist.userid = privatemessage.aboutid AND
						userlist.relationid = sentto.userid AND userlist.type = 'follow' AND userlist.friend = 'pending'
				WHERE privatemessage.nodeid IN ({nodeids}) AND privatemessage.about = {request} AND
					privatemessage.msgtype = 'request'
			"
		),
		'getFolderInfoFromId' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT folderid,
					(CASE WHEN titlephrase IS NOT NULL THEN titlephrase ELSE title END) AS title,
					(CASE WHEN titlephrase IS NOT NULL THEN 0 ELSE 1 END) AS iscustom
				FROM {TABLE_PREFIX}messagefolder
				WHERE folderid IN ({folderid})
			"
		),
		'resetStarterPmOnResponse' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_UPDATE,
			'query_string' => "
				UPDATE {TABLE_PREFIX}sentto AS sentto
					JOIN {TABLE_PREFIX}messagefolder AS messagefolder ON
						(sentto.folderid = messagefolder.folderid AND messagefolder.titlephrase IN ({folderphrases}))
				SET msgread = 0,
					deleted = 0
				WHERE sentto.nodeid IN ({nodeid}) AND sentto.userid <> {userid}
			"
		),
		'getTotalUserPhotos' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
			SELECT COUNT(*) AS total
			FROM {TABLE_PREFIX}node AS gallery
			INNER JOIN {TABLE_PREFIX}node AS photo ON photo.parentid = gallery.nodeid
			WHERE gallery.parentid = {channelid} AND photo.contenttypeid = {contenttypeid}
			AND gallery.userid = {userid}
			"
		),
		'getNumberAlbumPhotos' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
			SELECT COUNT(*) AS total
			FROM {TABLE_PREFIX}node AS gallery
			INNER JOIN {TABLE_PREFIX}node AS photo ON photo.parentid = gallery.nodeid
			WHERE gallery.nodeid = {albumid} AND photo.contenttypeid = {contenttypeid}
			"
		),
		'getNumberPosthotos' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
			SELECT COUNT(*) AS total
			FROM {TABLE_PREFIX}node AS gallery
			INNER JOIN {TABLE_PREFIX}node AS photo ON photo.parentid = gallery.nodeid
			WHERE gallery.nodeid = {nodeid} AND photo.contenttypeid = {contenttypeid}
			"
		),
		'getUserPhotosSize' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
			SELECT IFNULL(SUM(fd.filesize), 0) AS totalsize
			FROM {TABLE_PREFIX}node as gallery
			INNER JOIN {TABLE_PREFIX}node as pic ON pic.parentid = gallery.nodeid
			INNER JOIN {TABLE_PREFIX}photo as photo ON photo.nodeid = pic.nodeid
			INNER JOIN {TABLE_PREFIX}filedata as fd ON photo.filedataid = fd.filedataid
			WHERE gallery.parentid = {channelid} and pic.contenttypeid = {contenttypeid}
			AND gallery.userid = {userid}
			"
		),
		'getUserChannelsCount' => array(vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT COUNT(node.nodeid) as totalcount
			FROM {TABLE_PREFIX}node AS node
			INNER JOIN {TABLE_PREFIX}closure as cl ON cl.child = node.nodeid
			INNER JOIN {TABLE_PREFIX}channel AS ch ON ch.nodeid = node.nodeid
			WHERE cl.parent = {parent} AND node.userid = {userid} AND ch.category = 0"
		),
		'getChannelTree' => array(vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT
				node.nodeid, node.routeid, node.title, node.description,
				node.parentid, ch.category, node.displayorder, ch.filedataid
			FROM {TABLE_PREFIX}node AS node
			INNER JOIN {TABLE_PREFIX}channel AS ch ON ch.nodeid = node.nodeid
			ORDER BY node.parentid ASC, node.displayorder ASC, node.title ASC "
		),
		'updateChildsNodeoptions' => array(vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_UPDATE,
			'query_string' => "UPDATE {TABLE_PREFIX}node AS child
			INNER JOIN {TABLE_PREFIX}closure AS cl ON cl.child = child.nodeid
			RIGHT JOIN {TABLE_PREFIX}node AS father ON cl.parent = father.nodeid
			SET child.nodeoptions = father.nodeoptions
			WHERE cl.parent = {parentid} AND cl.depth > 0",
		),
		'getDataForParse' => array(vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT node.*, channel.nodeid AS channelid, channel.options, channel.guid,
				text.rawtext, text.htmlstate, text.previewtext
			FROM {TABLE_PREFIX}node AS node
			INNER JOIN {TABLE_PREFIX}node AS starter ON starter.nodeid = node.starter
			INNER JOIN {TABLE_PREFIX}channel AS channel ON channel.nodeid = starter.parentid
			INNER JOIN {TABLE_PREFIX}text AS text ON text.nodeid = node.nodeid
			WHERE node.nodeid IN ({nodeid}) "
		),
		'getRepliesAfterCutoff' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT nodeid, publishdate
				FROM {TABLE_PREFIX}node AS node
				WHERE starter = {starter} AND publishdate > {cutoff}
				ORDER BY publishdate ASC
				LIMIT 10
			",
		),
		'getNodeOptionsList' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT DISTINCT node.nodeid, node.nodeoptions,
				CASE when starter.nodeid IS NULL then -1 ELSE starter.nodeoptions END AS starternodeoptions,
				CASE when channel.nodeid IS NULL then -1 ELSE channel.nodeoptions END AS channelnodeoptions
				FROM {TABLE_PREFIX}node AS node
				LEFT JOIN {TABLE_PREFIX}node AS starter ON starter.nodeid = node.starter
				LEFT JOIN {TABLE_PREFIX}node AS channel ON channel.nodeid = starter.parentid
				WHERE node.nodeid IN ({nodeid})
			",
		),

		'getNotificationPollVoters' => array(vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT pv.userid, u.username, n.nodeid AS starter, NULL as following
				FROM {TABLE_PREFIX}node AS n INNER JOIN
					{TABLE_PREFIX}pollvote AS pv ON (pv.nodeid = n.nodeid AND n.nodeid = n.starter) INNER JOIN
					{TABLE_PREFIX}user AS u ON (u.userid = pv.userid)
				WHERE n.nodeid IN ({nodeid})
				ORDER BY pv.nodeid, u.username"
		),

		'getGitSubchannels' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
			SELECT n.nodeid, n.title
			FROM {TABLE_PREFIX}closure AS cl
			INNER JOIN {TABLE_PREFIX}channel AS ch ON ch.nodeid = cl.child
			INNER JOIN {TABLE_PREFIX}node AS n ON n.nodeid = cl.child
			INNER JOIN {TABLE_PREFIX}groupintopic AS git ON git.nodeid = n.nodeid
			WHERE cl.parent = {parentnodeId} AND git.userid = {userid}
			"
		],
		'verifySubscriberRequest' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT n.nodeid
			FROM {TABLE_PREFIX}node AS n
			INNER JOIN {TABLE_PREFIX}privatemessage AS pm ON n.nodeid = pm.nodeid
			WHERE n.userid = {userid} AND pm.aboutid IN ({nodeid}) and pm.about = ({about})"
		),
		// This query will only work if called immediately after SQL_CALC_FOUND_ROWS
		'getNodeSubscribersTotalCount' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT FOUND_ROWS() AS total"
		),
		'getNodeModerators' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT DISTINCT m.userid
			FROM {TABLE_PREFIX}moderator as m
			INNER JOIN {TABLE_PREFIX}closure as cl ON m.nodeid = cl.parent AND cl.child = {nodeid}
			WHERE m.nodeid > 0"
		),
		'getSuperModeratorsAdmins' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "SELECT DISTINCT m.userid
			FROM {TABLE_PREFIX}moderator as m
			WHERE m.nodeid <= 0 AND userid NOT IN ({userids})"
		),

		// Paid subscriptions
		'getPaymentinfo' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT paymentinfo.*, user.username, user.displayname
				FROM {TABLE_PREFIX}paymentinfo AS paymentinfo
				INNER JOIN {TABLE_PREFIX}user AS user USING (userid)
				WHERE hash = {hash}
			"
		],

		// START: api_gotonewpost
		'getNodeReplyNumber' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT COUNT(*) AS replies
				FROM {TABLE_PREFIX}node AS node
				WHERE node.parentid = {nodeid} AND showpublished = 1
				AND publishdate <= {publishdate}
			",
		),

		'getFirstUnreadReply' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT MIN(nodeid) AS nodeid, publishdate
				FROM {TABLE_PREFIX}node
				WHERE parentid = {nodeid} AND showpublished = 1
				AND publishdate > {publishdate}
				LIMIT 1
			",
		),
		// END: api_gotonewpost
		'getChannelRead' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => '
				SELECT COUNT(*) AS count
				FROM {TABLE_PREFIX}node AS child
					LEFT JOIN {TABLE_PREFIX}noderead AS parent_nr ON (parent_nr.nodeid = child.parentid AND parent_nr.userid = {userid})
					LEFT JOIN {TABLE_PREFIX}noderead AS child_nr ON (child_nr.nodeid = child.nodeid AND child_nr.userid = {userid})
				WHERE child.parentid = {channelid} AND
					child.nodeid NOT IN ({nodesmarked}) AND
					(child.contenttypeid <> {channelcontenttypeid} OR child.nodeid IN ({canview})) AND
					child.lastcontent > {cutoff} AND
					child.lastcontent > IF(parent_nr.readtime IS NULL, 0, parent_nr.readtime) AND
					child.lastcontent > IF(child_nr.readtime IS NULL, 0, child_nr.readtime)
			',
		),
		'deleteUserInfractions' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_DELETE,
			'query_string' => '
				DELETE i.*, n.*
				FROM {TABLE_PREFIX}infraction i
				INNER JOIN {TABLE_PREFIX}node AS n ON n.nodeid = i.nodeid
				WHERE i.infracteduserid = {userid}
			'
		),
		'getRouteFromChGuid' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => '
				SELECT route.* FROM {TABLE_PREFIX}routenew AS route
				INNER JOIN {TABLE_PREFIX}node AS n ON n.routeid = route.routeid
				INNER JOIN {TABLE_PREFIX}channel AS ch ON ch.nodeid = n.nodeid
				WHERE ch.guid IN ({guid})'
		),
		'checkLastData' => array(
		vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
		'query_string' => '
			SELECT parent.lastcontentid, parent.lastcontentauthor, parent.lastauthorid, chk.nodeid, chk.lastauthorid, chk.authorname
			FROM {TABLE_PREFIX}node AS parent
			INNER JOIN {TABLE_PREFIX}closure AS cl ON cl.parent = parent.nodeid AND cl.depth > 0
			INNER JOIN {TABLE_PREFIX}node AS chk ON chk.nodeid = cl.child
			WHERE parent.nodeid = {parentid} AND chk.showpublished > 0 AND chk.showapproved > 0 and chk.publishdate > parent.lastcontent
			AND chk.contenttypeid <> {channeltype}
			AND (chk.nodeid <> parent.lastcontentid OR chk.authorname <> parent.lastcontentauthor OR chk.userid <> parent.lastauthorid)'
		),

		'updateNodeview' => array(vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_INSERT,
			'query_string' => '
				INSERT INTO {TABLE_PREFIX}nodeview (nodeid, count)
				VALUES ({nodeid}, 1)
				ON DUPLICATE KEY UPDATE count = count + 1'
		),

		// Only GUID is needed ATM but extend if necessary
		'getPageInfoFromChannelId' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => '
				SELECT ch.nodeid, p.guid
				FROM {TABLE_PREFIX}channel AS ch
				INNER JOIN {TABLE_PREFIX}node AS n ON n.nodeid = ch.nodeid
				INNER JOIN {TABLE_PREFIX}page AS p ON p.routeid = n.routeid
				WHERE ch.nodeid IN ({nodeid})'
		),

		'modPostNotify' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT u.email, u.userid, u.languageid, m.nodeid,
					m.permissions & {bfPost} AS notifypost,
					m.permissions & {bfTopic} AS notifytopic
				FROM {TABLE_PREFIX}moderator AS m
				INNER JOIN {TABLE_PREFIX}user AS u
					ON u.userid = m.userid
				WHERE ((m.permissions & {bfTopic} > 0) OR (m.permissions & {bfPost} > 0))",
		),

		'getChildrenOrderedByDepth' => array(
				vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
				'query_string' =>
				"SELECT n.nodeid, n.parentid, n.textcount, n.textunpubcount,
					n.totalcount, n.totalunpubcount, n.showpublished,
					n.showapproved, n.approved,
					n.publishdate, n.unpublishdate, c.depth, n.contenttypeid,
					n.starter, n.lastupdate, n.authorname, n.userid,
					n.lastcontent, n.lastcontentid, n.lastcontentauthor, n.lastauthorid
				FROM {TABLE_PREFIX}closure AS c
				INNER JOIN {TABLE_PREFIX}node AS n ON n.nodeid = c.child
				WHERE c.parent = {nodeid}
					ORDER BY c.depth ASC"
		),

		'getApprovedAndPublishedChildren' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' =>
				"SELECT n.nodeid
				FROM {TABLE_PREFIX}node AS n
				INNER JOIN {TABLE_PREFIX}closure AS cl ON n.nodeid = cl.child
				WHERE cl.parent = {parentid} AND n.showpublished = 1 AND n.showapproved = 1
					AND n.contenttypeid NOT IN ({excluded}) AND cl.depth > 0
				LIMIT 1"
		),

		'getUnpublishedParent' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' =>
				"SELECT n.nodeid
				FROM {TABLE_PREFIX}node AS n
				INNER JOIN {TABLE_PREFIX}closure AS cl
					ON n.nodeid = cl.parent
					AND cl.child = {nodeid} AND cl.depth > 0
				WHERE n.showpublished = 0
				LIMIT 1"
		),
		'updateNotificationevent' => array(
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_INSERT,
			'query_string' => "
				INSERT INTO {TABLE_PREFIX}notificationevent
				(eventname, classes)
				VALUES ({event}, {classes})
				ON DUPLICATE KEY UPDATE classes = {classes}"
		),

		'deleteNotifications_dismissed' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_DELETE,
			'query_string' => '
				DELETE FROM {TABLE_PREFIX}notification
				WHERE lastreadtime >= lastsenttime AND lastsenttime <= {cutoff}
			'
		],

		'deleteNotifications_new' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_DELETE,
			'query_string' => '
				DELETE FROM {TABLE_PREFIX}notification
				WHERE lastreadtime < lastsenttime AND lastsenttime <= {cutoff}
			'
		],

		'getUserPostsInTopic' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			//not sure if closure.publishdate is really kept populated correctly.
			//might want node.publishdate or node.created. But not changing it now
			'query_string' => "
				SELECT closure.parent, MAX(closure.publishdate) AS lastpost, COUNT(*) AS count
				FROM {TABLE_PREFIX}closure AS closure
					JOIN {TABLE_PREFIX}node AS node ON(closure.child = node.nodeid)
				WHERE closure.parent IN({nodeids}) AND node.userid = {userid}
				GROUP BY 1
			"
		],

		'getImageCategoryPermissions' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT imagecategorypermission.imagecategoryid, imagecategorypermission.usergroupid
				FROM {TABLE_PREFIX}imagecategorypermission AS imagecategorypermission, {TABLE_PREFIX}imagecategory AS imagecategory
				WHERE imagetype = {imagetype}
					AND imagecategorypermission.imagecategoryid = imagecategory.imagecategoryid
				ORDER BY imagecategory.displayorder
			"
		],

		'getOrphanedPagetemplates' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT
					pagetemplate.pagetemplateid, pagetemplate.title, pagetemplate.guid,
					pagetemplate.product, pagetemplate.screenlayoutid
				FROM {TABLE_PREFIX}pagetemplate AS pagetemplate
				LEFT JOIN {TABLE_PREFIX}page AS page USING (pagetemplateid)
				WHERE page.pageid IS NULL
			"
		],

		'getPagetemplatesAndPageid' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT
					pagetemplate.pagetemplateid, pagetemplate.guid, page.pageid
				FROM {TABLE_PREFIX}pagetemplate AS pagetemplate
				LEFT JOIN {TABLE_PREFIX}page AS page USING (pagetemplateid)
				WHERE pagetemplate.pagetemplateid IN ({pagetemplateids})
			"
		],

		'getMultipleUsersDeviceTokensForPushNotification' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT
					token.*,
					apiclient.lastactivity
				FROM {TABLE_PREFIX}apiclient_devicetoken AS token
				INNER JOIN {TABLE_PREFIX}apiclient AS apiclient USING (apiclientid)
				WHERE token.userid IN ({userids})
			"
		],

		'getFCMessageQueue' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT *
				FROM {TABLE_PREFIX}fcmessage_queue
				WHERE status = 'ready' AND retryafter < {timenow}
				ORDER BY messageid, recipient_apiclientid, retryafter ASC
				LIMIT {limit}
			"
		],

		'lockFCMQueueItems' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_UPDATE,
			'query_string' => "
			UPDATE {TABLE_PREFIX}fcmessage_queue
			SET status = 'processing'
			WHERE recipient_apiclientid IN ({clientids}) AND messageid = {messageid}
			"
		],

		'getLastActivityAndDeviceTokens' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT
					token.*,
					apiclient.lastactivity
				FROM {TABLE_PREFIX}apiclient_devicetoken AS token
				INNER JOIN {TABLE_PREFIX}apiclient AS apiclient USING (apiclientid)
				WHERE token.apiclientid IN ({clientids})
			"
		],

		'getFCMQueueDeleteCount' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT COUNT(*) AS count
				FROM {TABLE_PREFIX}fcmessage_queue
				WHERE retryafter < {delete_cutoff}
			"
		],

		'deleteOldFCMQueue' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_DELETE,
			'query_string' => '
				DELETE FROM {TABLE_PREFIX}fcmessage_queue
				WHERE retryafter < {delete_cutoff}
			'
		],

		'updateFCMOffload' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_INSERT,
			'query_string' => "
				INSERT INTO {TABLE_PREFIX}fcmessage_offload (recipientids, message_data, hash, removeafter)
				VALUES({recipientids}, {message_data}, {hash}, {removeafter})
				ON DUPLICATE KEY UPDATE removeafter = {removeafter}
			"
		],

		'getUnusedFCMessageids' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => '
				SELECT messageid
				FROM {TABLE_PREFIX}fcmessage AS message
				LEFT JOIN {TABLE_PREFIX}fcmessage_queue AS queue USING (messageid)
				WHERE queue.messageid IS NULL
			'
		],

		'fetchMessageRecipientsNotIgnoringSender' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => '
				SELECT sentto.userid
				FROM {TABLE_PREFIX}sentto AS sentto
				LEFT JOIN {TABLE_PREFIX}userlist AS userlist
					ON (
						userlist.relationid = {senderid}
						AND userlist.userid = sentto.userid
						AND userlist.type = "ignore"
					)
				WHERE
					sentto.nodeid = {nodeid}
					AND sentto.userid <> {senderid}
					AND userlist.userid IS NULL
			'
		],

		'getVMCountAfterVMID' =>  [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => '
				SELECT COUNT(nodeid) AS vm_count
				FROM {TABLE_PREFIX}node AS node
				WHERE
					node.nodeid = node.starter AND
					node.showpublished > 0 AND
					node.inlist = 1 AND
					node.setfor = {userid} AND
					node.publishdate >= {publishdate}
			'
		],

		'getUserChannels' =>  [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => '
				SELECT node.nodeid, channel.guid
				FROM {TABLE_PREFIX}node AS node
				INNER JOIN {TABLE_PREFIX}channel AS channel
					ON channel.nodeid = node.nodeid
				INNER JOIN {TABLE_PREFIX}closure AS closure
					ON closure.child = node.nodeid
				WHERE
					node.userid = {userid} AND
					node.contenttypeid = {channeltypeid} AND
					closure.parent = {parentchannelid} AND
					channel.category = 0;
			'
		],

		'getUserAuths' =>  [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => '
				SELECT
					auth.userid,
					auth.loginlibraryid,
					auth.external_userid,
					auth.additional_params,
					auth.token,
					auth.token_secret,
					lib.productid,
					lib.class
				FROM {TABLE_PREFIX}userauth AS auth
				INNER JOIN {TABLE_PREFIX}loginlibrary AS lib
					ON auth.loginlibraryid = lib.loginlibraryid
				WHERE
					auth.userid = {userid};
			'
		],

		'addUserEditorState' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_UPDATE,
			'query_string' => "
				UPDATE {TABLE_PREFIX}user
				SET editorstate = (editorstate | {editorstatevalue})
				WHERE  userid = {userid}
			"
		],

		'removeUserEditorState' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_UPDATE,
			'query_string' => "
				UPDATE {TABLE_PREFIX}user
				SET editorstate = (editorstate & ~{editorstatevalue})
				WHERE  userid = {userid}
			"
		],

		'incrementAttachCounter' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_UPDATE,
			'query_string' => "
				UPDATE {TABLE_PREFIX}attach
				SET counter = counter + 1
				WHERE nodeid = {nodeid}
			"
		],

		'getHomePages' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => '
				SELECT p.pageid, p.routeid, p.guid, r.routeid, r.ishomeroute
				FROM {TABLE_PREFIX}page AS p
				INNER JOIN {TABLE_PREFIX}routenew AS r ON p.routeid = r.routeid
				WHERE p.guid IN({pageguids})
			'
		],

		'getModeratorNotificationOptions' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => '
				SELECT DISTINCT user.userid, user.moderatornotificationoptions, user.moderatoremailnotificationoptions
				FROM {TABLE_PREFIX}user AS user
				INNER JOIN {TABLE_PREFIX}moderator AS moderator ON (moderator.userid = user.userid)
			'
		],

		'removeTagtextFromTaglist' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_UPDATE,
			'query_string' => "
				UPDATE {TABLE_PREFIX}node AS node
				SET taglist = TRIM(BOTH ',' FROM REPLACE(CONCAT(',', taglist, ','), CONCAT(',', {tagtext}, ','), ','))
				WHERE node.nodeid IN ({nodeids})
			"
		],

		'getCustomNodeFieldsForChannel' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT `nodefieldcategory`.`name` AS `category_name`,
					`nodefieldcategory`.`displayorder` AS `category_displayorder`,
					`nodefield`.*
				FROM `{TABLE_PREFIX}nodefieldcategorychannel` AS `nodefieldcategorychannel`
					JOIN `{TABLE_PREFIX}nodefieldcategory` AS `nodefieldcategory` USING (`nodefieldcategoryid`)
					JOIN `{TABLE_PREFIX}nodefield` AS `nodefield` USING(`nodefieldcategoryid`)
				WHERE `nodefieldcategorychannel`.`nodeid` IN ({channelid})
				ORDER BY `nodefieldcategory`.`displayorder`, `nodefield`.`displayorder`
			",
		],

		'getCustomNodeFieldsForValues' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT `nodefieldcategory`.`name` AS `category_name`,
					`nodefieldcategory`.`displayorder` AS `category_displayorder`,
					`nodefield`.*
				FROM `{TABLE_PREFIX}nodefieldcategory` AS `nodefieldcategory`
					JOIN `{TABLE_PREFIX}nodefield` AS `nodefield` USING(`nodefieldcategoryid`)
				WHERE `nodefieldcategory`.`nodefieldcategoryid` IN (
					SELECT DISTINCT `nodefieldinner`.`nodefieldcategoryid`
					FROM 	`{TABLE_PREFIX}nodefield` AS `nodefieldinner`
					WHERE `nodefieldinner`.`nodefieldid` IN ({nodefieldid})
				)
				ORDER BY `nodefieldcategory`.`displayorder`, `nodefield`.`displayorder`
			",
		],

		'getCustomNodeFieldsWithValues' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT `nodefieldcategory`.`name` AS `category_name`,
					`nodefield`.`type`,
					`nodefieldvalue`.`value`,
					`nodefield`.*
				FROM `{TABLE_PREFIX}nodefieldcategory` AS `nodefieldcategory`
					JOIN `{TABLE_PREFIX}nodefield` AS `nodefield` USING(`nodefieldcategoryid`)
					JOIN `{TABLE_PREFIX}nodefieldvalue` AS `nodefieldvalue` USING(`nodefieldid`)
				WHERE `nodefieldvalue`.`nodeid` IN ({nodeid}) AND `nodefieldvalue`.`value` <> ''
				ORDER BY `nodefieldcategory`.`displayorder`, `nodefield`.`displayorder`
			",
		],

		'getCustomNodeFieldValues' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT `nodefieldvalue`.`value`, `nodefieldvalue`.`nodefieldid`
				FROM `{TABLE_PREFIX}nodefieldvalue` AS `nodefieldvalue`
					JOIN `{TABLE_PREFIX}nodefield` AS `nodefield` USING(`nodefieldid`)
				WHERE `nodefieldvalue`.`nodeid` IN ({nodeid})
				ORDER BY `nodefield`.`displayorder`
			",
		],

		'deleteCustomFieldCategoryAndChildren' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_DELETE,
			'query_string' => "
				DELETE `nodefieldcategory`, `nodefieldcategorychannel`, `nodefield`, `nodefieldvalue`
				FROM `{TABLE_PREFIX}nodefieldcategory` AS `nodefieldcategory`
					LEFT JOIN `{TABLE_PREFIX}nodefieldcategorychannel` AS `nodefieldcategorychannel` USING(`nodefieldcategoryid`)
					LEFT JOIN `{TABLE_PREFIX}nodefield` AS `nodefield` USING(`nodefieldcategoryid`)
					LEFT JOIN `{TABLE_PREFIX}nodefieldvalue` AS `nodefieldvalue` USING(`nodefieldid`)
				WHERE `nodefieldcategory`.`nodefieldcategoryid` IN ({nodefieldcategoryid})
			",
		],

		'getUserSubscriptionsAffectingNodeid' => [
			vB_dB_Query::QUERYTYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'query_string' => "
				SELECT `node`.`nodeid`,
					`node`.`contenttypeid`,
					`node`.`starter`,
					`node`.`title`,
					`sub`.`emailupdate`
				FROM `{TABLE_PREFIX}closure` AS `cl`
				INNER JOIN `{TABLE_PREFIX}subscribediscussion` AS `sub` ON `sub`.`discussionid` = `cl`.`parent`
				INNER JOIN `{TABLE_PREFIX}node` AS `node` ON `node`.`nodeid` = `cl`.`parent`
				WHERE `sub`.`userid` = {userid}
					AND `cl`.`child` = {nodeid};
			",
		],
	];

	/*
	 * Returns a list of routes & associated page data for vB_Api_Page::getURLs()
	 */
	public function getURLs($params, $db, $check_only = false)
	{
		/*
			This is implemented as a method query, as we can end up with a non-trivially different WHERE
			clause depending on the parameters. Achieving this via stored queries would require creating
			a series of nearly duplicate stored queries, one for each possible WHERE clause.
		*/
		if ($check_only)
		{
			return (!empty($params['exclude']) OR !empty($params['include']) OR !empty($params['pageid']));
		}
		else
		{
			$params = vB::getCleaner()->cleanArray($params, [
				// It is only a conditional argument, NOT used in the query
				'include' => vB_Cleaner::TYPE_ARRAY_STR,
				// It is only a conditional argument, NOT used in the query
				'exclude' => vB_Cleaner::TYPE_ARRAY_STR,
				'pageid'  => vB_Cleaner::TYPE_ARRAY_UINT,
			]);

			$excludeSystem =   in_array('system', $params['exclude']);
			$excludeCustom =   in_array('custom', $params['exclude']);
			$excludeChannels = in_array('channels', $params['exclude']);
			unset($params['exclude']);
			// If the same category is both excluded AND included, that's kind of an error, but let's just treat the excludes
			// as higher precedence and quietly pass it.
			$includeAll = in_array('all', $params['include']);
			$includeSystem =  (!$excludeSystem AND ($includeAll OR in_array('system', $params['include'])));
			$includeCustom =   (!$excludeCustom AND ($includeAll OR in_array('custom', $params['include'])));
			$includeChannels = (!$excludeChannels AND ($includeAll OR in_array('channels', $params['include'])));
			// This one is special -- meant to support "always include home" kind of queries.
			$includeHome = ($includeAll OR in_array('home', $params['include']));
			unset($params['include']);

			/*
				Exclude the weird/default Pages & class-less routes that probably shouldn't
				be modified (e.g. lostpw, create-content). Unfortunately this will also return
				"special" channels. We'll handle them separately in the API.

				Skip 'vB5_Route_Conversation', as we cannot really distinguish them from a
				Channel URL until we're able to reliably generate a sample URL from a node that
				the user has permission to view. Furthermore, the "special" channels don't have a
				known route GUID for their conversation routes, and we'll require additional logic
				to filter the "special" conversation routes out if we decide to include other
				conversation routes.

				For ex., to get the "album" routes, it'd be a multi step process where we first
				grab the channelid from the channel table via
					SELECT channelid
					FROM `{TABLE_PREFIX}channel`
					WHERE guid = '{vB_Channel::ALBUM_CHANNEL}'
				('vbulletin-4ecbdf567f3a38.99555303' hard-coded in the vbulletin-channels.xml file),
				then finding the routenew records via
					SELECT *
					FROM `{TABLE_PREFIX}routenew`
					WHERE contentid = '{channelid from above}'
						AND class IN ('vB5_Route_Channel', 'vB5_Route_Conversation', 'vB5_Route_Article')
					(contentid & class logic is taken from how vB_Library_Channel::deleteChannelPages()
					finds the associated pages for a given channel)
				*/
			/*
			// These won't be included because they don't have a routenew.class, but
			// listing here just for reference
			$excludes_NoRouteClass = [
				// ajax
				'vbulletin-4ecbdacd6a3d43.49233131',
				// page/1234 -- kinda like node/123 but apparently for pages. it works.
				'vbulletin-4ecbdacd6a4277.53325739',
				// pages
				'vbulletin-4ecbdacd6a4687.72226697',
				// page-edit
				'vbulletin-4ecbdacd6a4ec4.04956185',
				// pagetemplate-edit
				'vbulletin-4ecbdacd6a5335.81970166',
				// site-new -- ??
				'vbulletin-4ecbdacd6a5733.19365762',
				// site-save -- ??
				'vbulletin-4ecbdacd6a5b34.78659015',
				// page-save (admin::pageSave)
				'vbulletin-4ecbdacd6a5f24.20442364',
				// site-manager -- ???
				'vbulletin-4ecbdacd6a6728.48186180',
				// site-install -- ???
				'vbulletin-4ecbdacd6a6b27.74517966',
				// create-content (createcontent/<contenttype> controller route)
				'vbulletin-4ecbdacd6a7709.25161691',
			];
			*/
			// pages that we generally do not want to include in general "get pages" lists.
			// This list is ignored if specific pageids are requested via $params['pageid']
			// Some of them are because they lack a "default" URL (e.g. member-related ones that
			// only make sense in context of a specific userid), or because they're placeholders
			// or planned routes that haven't been implemented yet  (e.g. uploadmedia?)
			$excludedDefaultRoutes = [
				// register
				'vbulletin-4ecbdacd6a6f13.66635711',
				// lostpw
				'vbulletin-4ecbdacd6a6f13.66635712',
				// member (profile)
				'vbulletin-4ecbdacd6a7315.96817600',
				// editphoto -- ???
				'vbulletin-4ecbdacd6a7b06.81753708',
				// settings (user profile/account etc settings)
				'vbulletin-4ecbdacd6a9307.24480802',
				// uploadmedia -- ???
				'vbulletin-4ecbdacd6a9ee3.66723601',
				// admincp -- see modcp note below
				'vbulletin-4ecbdacd6aa7c8.79724467',
				// admincp redirect301 to index
				'vbulletin-4ecbdacd6aa7c8.89724467',
				// modcp -- this is excluded because we can't generate a 'canonical' url
				// via default, we have to handle it specially to provide the "index.php" file name
				'vbulletin-4ecbdacd6aa7c8.79724488',
				// modcp redirect301 to index
				'vbulletin-4ecbdacd6aa7c8.79734488',
				// privatemessage
				'vbulletin-4ecbdacd6aac05.50909921',
				// member/<userid>/subscriptions (subscription)
				'vbulletin-4ecbdacd6aac05.50909922',
				// album/<nodeid>
				'vbulletin-4ecbdacd6aac05.50909923',
				// member/<userid>/visitormessage (visitormessage)
				'vbulletin-4ecbdacd6aac05.50909924',
				// blogadmin
				'vbulletin-4ecbdacd6aac05.50909925',
				// new-content
				'vbulletin-4ecbdaad6aac05.50902379',
				//sgadmin
				'vbulletin-4ecbdacd6aac05.50909980',
				// activate user
				'vbulletin-4ecbdacd6aac05.50909984',
				// activate email
				'vbulletin-4ecbdacd6aac05.50909985',
				// coppa form
				'vbulletin-4ecbdacd6aac05.50909986',
				// node/<nodeid> (node route)
				'vbulletin-4ecbdacd6aac05.50909987',
				// special/css-examples
				'vbulletin-513e559445fc66.10550504',
				// special/markup-library
				'vbulletin-route-markuplibrary-92e837cb33910.016642946',
				// special/api-form
				'vbulletin-route-apiform-5605af1c66ec89.17376376',
				// reset password
				'vbulletin-route-resetpassword-569814b4a8a849.28212294',
				// pm chat
				'vbulletin-pmchat-route-chat-573cbacdc65943.65236568',
			];
			/*
			// default routes that I'm not sure if it should be included above or below...
			$unknown = [
				// advanced_search
				'vbulletin-4ecbdacd6a8335.81846640',
				// search
				'vbulletin-4ecbdacd6aa3b7.75359902',
				//sguncategorized
				'vbulletin-sgcatlistaac05.50909983',
				// articles/<nodeid>-<title>
				'vbulletin-r-cmsarticle522a1d420a59e1.65940114',
			];
			*/
			// Default pages that we want to include because e.g. they're
			// the default home pages, or used for navigation items, etc.
			// Not used atm because we're using the exclusion list above instead,
			// but documented for reference & possible future use
			/*
			$includedDefaultRoutes = [
				// the three homepage options
				'vbulletin-4ecbdacd6a4ad0.58738735',
				'vbulletin-route-homeclassic-5d5f1629cb5297.17711543',
				'vbulletin-route-homecommunity-5d6039ff5c14d0.86786683',

				// BEGIN used in header nav

				// Forums heading (note the first "homepage" route above is the default "Forums" tab's route)
				// online
				'vbulletin-4ecbdacd6a8725.49820977',
				// memberlist
				'vbulletin-4ecbdacd6a8725.49820978',
				// calendar
				'vbulletin-route-calendar-58af7c31d90530.47875165',

				// Blogs heading
				// blogs
				'vbulletin-4ecbdacd6aac05.50909926',

				// Articles heading
				// articles
				'vbulletin-r-cmshome5229f999bcb705.52472433',

				// Groups heading
				// social-groups
				'vbulletin-4ecbdac93742a5.43676037',

				// END used in header nav

				// BEGIN used in footer nav
				// help
				'vbulletin-4ecbdacd6a6f13.66635714',
				// contact-us
				'vbulletin-4ecbdacd6a6f13.66635713',
				// privacy
				'vbulletin-route-privacy-25c722b99d29ac.6b08da87',
				// admin & mod are skipped because their routes require a "file" page param
				// END used in footer nav
				// This is included just in case, terms-of-service is very similar to
				// privacy in implementation / category
				'vbulletin-route-tos-632bbd31cdee46.28098868',
			];
			*/
			$homepage = 'vbulletin-4ecbdacd6a4ad0.58738735';
			$homepageClassic = 'vbulletin-route-homeclassic-5d5f1629cb5297.17711543';
			$homepageCommunity = 'vbulletin-route-homecommunity-5d6039ff5c14d0.86786683';
			$blogs = 'vbulletin-4ecbdacd6aac05.50909926';
			$articles = 'vbulletin-r-cmshome5229f999bcb705.52472433';
			$groups = 'vbulletin-4ecbdac93742a5.43676037';


			$whereExtra = '';
			if (empty($params['pageid']))
			{
				$whereExcludes = "\n AND routenew.guid NOT IN ('" . implode("', '", $excludedDefaultRoutes) . "')";
			}
			else
			{
				// Let's not exclude ANY page when pageids are specified. This is used in adminCP
				// product delete, and is meant to list all pages with the product's modules installed on the page.
				// Some of those pages may include the default pages, if they admin added those modules to them,
				// so it makes sense to return these pages.
				$whereExcludes = '';
			}
			$whereIncludes = [];

			// Excluding both system and custom leaves nothing
			if ($excludeSystem AND $excludeCustom)
			{
				return new vB_dB_ArrayResult($db, []);
			}

			// If we have NOTHING to show, just return empty, I guess.
			if (empty($params['pageid']) AND !$includeSystem AND !$includeCustom AND !$includeChannels AND !$includeHome)
			{
				return new vB_dB_ArrayResult($db, []);
			}

			// Treat exclusions as ANDs
			if ($excludeCustom)
			{
				$whereExcludes .= "\n AND page.pagetype <> 'custom'";
			}

			if ($excludeSystem)
			{
				$whereExcludes .= "\n AND page.pagetype <> 'default'";
			}

			if ($excludeChannels)
			{
				$whereExcludes .= "\n AND routenew.class <> 'vB5_Route_Channel'";
			}

			// Treat inclusions as ORs
			// Note, for "custom" and "system", we're only tracking class = vB5_Route_page -- this is because
			// currently, channel's pages are ALWAYS created with page.pagetype = 'custom' -- see VBV-21524
			if ($includeCustom)
			{
				$whereIncludes[] = "(routenew.class = 'vB5_Route_Page' AND page.pagetype = 'custom')";
			}

			if ($includeSystem)
			{
				$whereIncludes[] = "(routenew.class = 'vB5_Route_Page' AND page.pagetype = 'default')";
				// Currently, /blogs & /social-groups are Page routes, while /homepage and /articles are
				// channel routes. I think the latter two are more useful in the system grouping than
				// channels, since they're associated with the 4 default navitem tabs, so let's shove
				// those in here & exclude them from the channels.
				$whereIncludes[] = "routenew.guid = '$homepage'";
				$whereIncludes[] = "routenew.guid = '$articles'";
			}

			if ($includeChannels)
			{
				// Exclude the "Homepage" in the "channels" grouping.
				$whereIncludes[] = "(routenew.class = 'vB5_Route_Channel' AND routenew.guid <> '$homepage' AND routenew.guid <> '$articles')";
			}

			if ($includeHome)
			{
				$whereIncludes[] = "(routenew.ishomeroute = 1)";
			}

			$whereExtra = $whereExcludes . ($whereIncludes ? "\n AND (\n\t" . implode(" OR \n\t", $whereIncludes) . "\n)" : '');

			// Used in adminCP product.php
			if (!empty($params['pageid']))
			{
				$whereExtra .= " AND page.pageid IN (" . implode(", ", $params['pageid']) . ")";
			}

			$sql = "
					SELECT 	routenew.prefix, routenew.name,
							routenew.class, routenew.product,
							routenew.guid, routenew.routeid,
							routenew.arguments, routenew.contentid,
							routenew.ishomeroute,
							page.pagetype, page.guid AS page_guid,
							page.pageid AS pageid
					FROM " . TABLE_PREFIX . "routenew AS routenew
					INNER JOIN " . TABLE_PREFIX . "page AS page
						ON page.routeid = routenew.routeid
					WHERE (routenew.class IS NOT NULL AND routenew.class != '') $whereExtra
					ORDER BY routenew.prefix ASC
			";

			return $this->getResultSet($db, $sql, __FUNCTION__);
		}
	}


	/*
	 * Gets the channel children list
	 */
	public function getChannel($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			return !empty($params['channel']);
		}
		else
		{
			$params = vB::getCleaner()->cleanArray($params, array(
				// It is cleaned later in the query, it may or may not be an array
				'channel' 						=> vB_Cleaner::TYPE_UINT,
				'contenttypeid' 				=> vB_Cleaner::TYPE_UINT,
				'no_perm_check' 				=> vB_Cleaner::TYPE_BOOL,
			));

			$where = [
				'cl.parent = ' . $params['channel'],
				'node.contenttypeid = ' . $params['contenttypeid'],
			];

			// admincp needs to display all the channels, regardless of the permissions
			if (!$params['no_perm_check'])
			{
				$channelAccess = vB::getUserContext()->getAllChannelAccess();
				$canview =  array_merge($channelAccess['canview'], $channelAccess['canalwaysview'], $channelAccess['canmoderate']);
				if(empty($canview))
				{
					return new vB_dB_ArrayResult($db, []);
				}

				$where[] = 'node.nodeid IN (' . implode(',', $canview) . ')';
			}

			//we can most likely trim the select list a great deal here, but we need to verify all of the callers.
			$sql = "
				SELECT node.*, cl.parent, cl.child, cl.depth, cl.displayorder AS clorder, channel.category, 'Channel' AS contenttypeclass
				FROM " . TABLE_PREFIX . "closure AS cl
				INNER JOIN " . TABLE_PREFIX . "node AS node ON node.nodeid = cl.child
				INNER JOIN " . TABLE_PREFIX . "channel AS channel ON node.nodeid = channel.nodeid
				WHERE " . implode("\n AND ", $where) . "
				ORDER BY node.parentid ASC, node.displayorder ASC
			";

			return $this->getResultSet($db, $sql, __FUNCTION__);
		}
	}

	/*
	 * Get filedata record
	 */
	public function getFiledataContent($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			//We need a nodeid
			if (empty($params['filedataid']))
			{
				return false;
			}
			return true;
		}
		else
		{
			$params = vB::getCleaner()->cleanArray($params, [
				'filedataid' => vB_Cleaner::TYPE_UINT,
				// escaped downstairs
				'type' => vB_Cleaner::TYPE_STR,
			]);
			$joinfields = $joinsql = '';
			if ($params['type'])
			{
				$params['type'] = vB_Api::instanceInternal('filedata')->sanitizeFiletype($params['type']);
				if ($params['type'] != vB_Api_Filedata::SIZE_FULL)
				{
					$joinfields = ", fdr.*, f.filedataid";
					$joinsql = "LEFT JOIN " . TABLE_PREFIX . "filedataresize AS fdr " .
								"ON (fdr.filedataid = f.filedataid AND " .
								"fdr.resize_type = '" . $db->escape_string($params['type']) . "')";
				}
			}
			unset($params['type']);

			$sql = "
				SELECT
					f.* {$joinfields}
				FROM " . TABLE_PREFIX . "filedata AS f
				{$joinsql}
				WHERE f.filedataid = " . $params['filedataid'];

			return $this->getResultSet($db, $sql, __FUNCTION__);
		}
	}

	public function getFiledataWithThumb($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			//We need something to query by..
			if (empty($params['filedataid']) AND empty($params['filehash']))
			{
				return false;
			}
			return true;
		}
		else
		{
			//the cleaner doesn't handle array/scalar options very well so we can't
			//really use it properly.  However we can set it up to keep to standards
			//and, more importantly, make sure that we strip out any unexpected param
			//values.  It is critical that anything that is NOCLEAN below get handled
			//before it goes to the query.
			$params = vB::getCleaner()->cleanArray($params, [
				'filedataid' => vB_Cleaner::TYPE_NOCLEAN,
				'userid' => vB_Cleaner::TYPE_NOCLEAN,
				'dateline' => vB_Cleaner::TYPE_NOCLEAN,
				'filesize' => vB_Cleaner::TYPE_NOCLEAN,
				'filehash' => vB_Cleaner::TYPE_NOCLEAN,
			]);

			$wheresql = [];
			foreach ($params AS $key => $value)
			{
				if(is_null($value))
				{
					continue;
				}

				// It seems like we're not relying on the cleaner above for the INTs
				// because each value can be singular or an array... clean this up sometime?
				if (!is_array($value))
				{
					$value = [$value];
				}

				switch ($key)
				{
					case 'filedataid':
					case 'userid':
					case 'dateline':
					case 'filesize':
						$value = array_map('intval', $value);
						$wheresql[] = "f.{$key} IN (" . implode(', ', $value) . ")";
						break;
					case 'filehash':
						foreach ($value AS $_key => $_value)
						{
							$value[$_key] = $db->escape_string($_value);
						}
						$wheresql[] = "f.{$key} IN ('" . implode("', '", $value) . "')";
						break;
				}
			}
			unset($params);

			//I don't think this can actually happen due to validating that we have a
			//filedataid parameter.  If it does, however, return a resultset instead of false
			if (!$wheresql)
			{
				return new vB_dB_ArrayResult($db, []);
			}

			$sql = "
				SELECT f.*, fdr.resize_type, fdr.resize_filesize, fdr.resize_dateline, fdr.resize_width,
					fdr.resize_height, fdr.resize_filedata, fdr.reload
				FROM " . TABLE_PREFIX . "filedata AS f
				LEFT JOIN " . TABLE_PREFIX . "filedataresize AS fdr ON (fdr.filedataid = f.filedataid AND fdr.resize_type = 'thumb')
				WHERE " . implode(" AND ", $wheresql);

			return $this->getResultSet($db, $sql, __FUNCTION__);
		}
	}

	/*
	 * Get photo record
	 */
	public function getPhotoContent($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			//We need a nodeid
			if (empty($params['nodeid']))
			{
				return false;
			}
			return true;
		}
		else
		{
			$params = vB::getCleaner()->cleanArray($params, [
				'nodeid' => vB_Cleaner::TYPE_UINT,
				// escaped downstairs
				'type' => vB_Cleaner::TYPE_STR,
			]);
			$joinfields = $joinsql = '';
			if ($params['type'])
			{
				$params['type'] = vB_Api::instanceInternal('filedata')->sanitizeFiletype($params['type']);
				if ($params['type'] != vB_Api_Filedata::SIZE_FULL)
				{
					$joinfields = ", fdr.*, f.filedataid";
					$joinsql = "LEFT JOIN " . TABLE_PREFIX . "filedataresize AS fdr " .
								"ON (fdr.filedataid = f.filedataid AND " .
								"fdr.resize_type = '" . $db->escape_string($params['type']) . "')";
				}
			}
			unset($params['type']);

			$sql = "
				SELECT
					f.*,
					p.nodeid, p.caption, p.width as displaywidth, p.height as displayheight,
					node.starter AS galleryid
					{$joinfields}
				FROM " . TABLE_PREFIX . "photo AS p
				INNER JOIN " . TABLE_PREFIX . "filedata AS f ON (f.filedataid = p.filedataid)
				INNER JOIN " . TABLE_PREFIX . "node AS node ON (node.nodeid = p.nodeid)
				{$joinsql}
				WHERE p.nodeid = " . $params['nodeid'];

			return $this->getResultSet($db, $sql, __FUNCTION__);
		}
	}

	/**
	 * Gets the Activity for the profile page.
	 *
	 * @param	array $params
	 * @param	object $db
	 * @param	bool $check_only
	 * @result bool | object -- bool if check_only is true, or db result set if not
	 */
	public function getActivity($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			//We need either a userid or a setfor
			if (empty($params['setfor']) AND empty($params['userid']))
			{
				return false;
			}
			return true;
		}
		else
		{
			$params = vB::getCleaner()->cleanArray($params, array(
				// It is cleaned later in the query, it may or may not be an array
				'exclude' => vB_Cleaner::TYPE_NOCLEAN,
				'userid' => vB_Cleaner::TYPE_UINT,
				vB_Api_Node::FILTER_SOURCE => vB_Cleaner::TYPE_STR,
				// It is cleaned later in the query, it may or may not be an array
				'contenttypeid' => vB_Cleaner::TYPE_NOCLEAN,
				'time' => vB_Cleaner::TYPE_STR,
				// It is cleaned later in the query, it may or may not be an array
				'sort' => vB_Cleaner::TYPE_NOCLEAN,
				vB_dB_Query::PARAM_LIMIT  	 => vB_Cleaner::TYPE_UINT,
				vB_dB_Query::PARAM_LIMITPAGE => vB_Cleaner::TYPE_UINT,
			));

			$nodeApi = vB_Api::instanceInternal('node');
			$VMChannel = $nodeApi->fetchVMChannel();
			$sql = "SELECT node.nodeid
			FROM " . TABLE_PREFIX . "node AS node
			INNER JOIN " . TABLE_PREFIX . "contenttype AS type ON type.contenttypeid = node.contenttypeid
			INNER JOIN " . TABLE_PREFIX . "node AS starter ON starter.nodeid = node.starter
			LEFT JOIN " . TABLE_PREFIX . "user AS postfor ON postfor.userid = node.setfor
			LEFT JOIN " . TABLE_PREFIX . "closure AS vmcheck ON vmcheck.child = node.nodeid AND vmcheck.parent=$VMChannel\n";


			if (!empty($params['exclude']))
			{
				if (!is_array($params['exclude']))
				{
					$params['exclude'] = array($params['exclude']);
				}

				$params['exclude'] = vB::getCleaner()->clean($params['exclude'], vB_Cleaner::TYPE_ARRAY_UINT);

				$sql .= "LEFT JOIN  " . TABLE_PREFIX . "closure AS cl2 ON cl2.child = node.nodeid AND cl2.parent IN (" .
					implode(',',$params['exclude']) . " )\n";
			}

			if (!empty($params['userid']))
			{
				switch ($params[vB_Api_Node::FILTER_SOURCE])
				{
					case vB_Api_Node::FILTER_SOURCEUSER:
						$sql .= "WHERE (starter.userid =" .  intval($params['userid']) . " OR starter.lastauthorid = " . intval($params['userid']) . ")
							AND (node.protected = 0 OR vmcheck.child IS NOT NULL) \n";
						break;
					case vB_Api_Node::FILTER_SOURCEVM:
						$sql .= "WHERE (starter.setfor=" . intval($params['userid']) . " OR starter.lastauthorid = " . intval($params['userid']) . ") AND vmcheck.child IS NOT NULL \n";
						break;
					default:
						$sql .= "WHERE (starter.setfor=" . intval($params['userid']). " OR starter.userid =" .  intval($params['userid']) . " OR starter.lastauthorid = " . intval($params['userid']) . ")
							AND (node.protected = 0 OR vmcheck.child IS NOT NULL) \n";
						break;
				}
			}
			else
			{
				throw new vB_Exception_Api('invalid_data');
			}
			$sql .= " AND ((node.starter = node.nodeid AND starter.totalcount = 0) OR (starter.lastcontentid = node.nodeid)) AND node.inlist > 0 AND type.class <> 'Channel'\n";

			if (!empty($params['contenttypeid']))
			{
				if (is_array($params['contenttypeid']))
				{
					$params['contenttypeid'] = vB::getCleaner()->clean($params['contenttypeid'], vB_Cleaner::TYPE_ARRAY_UINT);
					$sql .= "AND node.contenttypeid IN (" . implode(', ', $params['contenttypeid']) .") \n";
				}
				else
				{
					$params['contenttypeid'] = vB::getCleaner()->clean($params['contenttypeid'], vB_Cleaner::TYPE_UINT);
					$sql .= "AND node.contenttypeid = " . $params['contenttypeid'] . " \n";
				}
			}

			if (!empty($params['exclude']))
			{
				$sql .= " AND cl2.child IS NULL \n";
			}

			//block people on the global ignore list
			$options = vB::getDatastore()->getValue('options');
			if (trim($options['globalignore']) != '')
			{
				$blocked = preg_split('#\s+#s', $options['globalignore'], -1, PREG_SPLIT_NO_EMPTY);
				//the user can always see their own posts, so if they are in the blocked list we remove them
				$bbuserkey = array_search(vB::getCurrentSession()->get('userid') , $blocked);

				if ($bbuserkey !== false AND $bbuserkey !== null)
				{
					unset($blocked[$bbuserkey]);
				}

				//Make sure we didn't just empty the list
				if (!empty($blocked))
				{
					$sql .= " AND node.userid NOT IN (" . implode(',', $blocked) . ")";
				}
			}

			/** Date filter */
			if (!empty($params['time']))
			{
				$datenow = vB::getRequest()->getTimeNow();
				switch ($params['time'])
				{
					case vB_Api_Search::FILTER_LASTDAY:
						$timeVal = $datenow - (24 * 60 * 60);
						break;
					case vB_Api_Search::FILTER_LASTWEEK:
						$timeVal = $datenow - (7 * 24 * 60 * 60);
						break;
					case vB_Api_Search::FILTER_LASTMONTH:
						$timeVal = strtotime(date("Y-m-d H:i:s", $datenow) . " - 1 month");
						break;
					default:
						$timeVal = 0;
						break;
				}
				$sql .= " AND node.publishdate >= $timeVal";
			}

			if (isset($params['sort']))
			{
				if (is_array($params['sort']))
				{
					$params['sort'] = vB::getCleaner()->clean($params['sort'], vB_Cleaner::TYPE_ARRAY_STR);
					$sorts = array();
					foreach ($params['sort'] as $key => $value)
					{
						//we may have something like 'publishdate' => 'desc'
						if (
							($key == 'publishdate')
							OR
							($key == 'unpublishdate')
							OR
							($key == 'authorname')
							OR
							($key == 'displayorder')
							)
						{
							if (strtolower($value) == 'desc')
							{
								$key = $db->escape_string($key);
								$sorts[] = "node.$key DESC";
							}
							else
							{
								$key = $db->escape_string($key);
								$sorts[] = "node.$key ASC";
							}
						}
						else if (
							($value == 'publishdate')
							OR
							($value == 'unpublishdate')
							OR
							($value == 'authorname')
							OR
							($value == 'displayorder')
							)
						{
							$value = $db->escape_string($value);
							$sorts[] = "node.$value ASC";
						}
						else if (
							is_array($value)
							AND
							isset($value['sortby'])
							AND
							(
							($value['sortby'] == 'publishdate')
							OR
							($value['sortby'] == 'unpublishdate')
							OR
							($value['sortby'] == 'authorname')
							OR
							($value['sortby'] == 'displayorder')
							)
							)
						{
							if (
								isset($value['direction'])
								AND
								(strtolower($value['direction']) == 'desc')
								)
							{
								$sorts[] = 'node.' . $db->escape_string($value['sortby']) . " DESC";
							}
							else
							{
								$sorts[] = 'node.' . $db->escape_string($value['sortby']) . " ASC";
							}

						}

						if (!empty($sorts))
						{
							$sort = implode(', ', $sorts);
						}
					}
				}
				else if (
					($params['sort'] == 'publishdate')
					OR
					($params['sort'] == 'unpublishdate')
					OR
					($params['sort'] == 'authorname')
					OR
					($params['sort'] == 'displayorder')
					)
				{
					$params['sort'] = vB::getCleaner()->clean($params['sort'], vB_Cleaner::TYPE_STR);
					$params['sort'] = $db->escape_string($params['sort']);
					$sort = 'node.' . $params['sort'] . ' ASC';
				}
			}

			if (empty($sort))
			{
				$sql .= " ORDER BY node.publishdate DESC LIMIT ";
			}
			else
			{
				$sql .= " ORDER BY $sort LIMIT ";
			}

			if (isset($params[vB_dB_Query::PARAM_LIMIT]) AND intval($params[vB_dB_Query::PARAM_LIMIT]))
			{
				$perpage = intval($params[vB_dB_Query::PARAM_LIMIT]);
			}
			else if (isset($params[vB_dB_Query::PARAM_LIMITPAGE]))
			{
				$perpage = 20;
			}
			else
			{
				$perpage = 500;
			}

			if (isset($params[vB_dB_Query::PARAM_LIMITPAGE]) AND intval($params[vB_dB_Query::PARAM_LIMITPAGE]) AND (intval($params[vB_dB_Query::PARAM_LIMITPAGE]) > 1))
			{
				$sql .=  ($perpage * (intval($params[vB_dB_Query::PARAM_LIMITPAGE]) - 1)) . ',';
			}
			$sql .= $perpage . "\n/**" . __FUNCTION__ . (defined('THIS_SCRIPT') ? '- ' . THIS_SCRIPT : '') . "**/";

			$resultclass = 'vB_dB_' . $this->db_type . '_result';
			$config = vB::getConfig();

			if (isset($config['Misc']['debug_sql']) AND $config['Misc']['debug_sql'])
			{
				echo "sql: $sql<br />\n";
			}

			$result = new $resultclass($db, $sql);
			return $result;
		}
	}

	/**
	 * fetchNodeWithContent
	 *
	 * @param	array $params
	 * @param	object $db
	 * @param	bool $check_only
	 * @result bool | object -- bool if check_only is true, or db result set if not
	 */
	public function fetchNodeWithContent($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			//We need a nodeid
			if (empty($params['nodeid']))
			{
				return false;
			}
			return true;
		}
		else
		{
			// clean $params
			if (!isset($params['userid']))
			{
				$params['userid'] = vB::getCurrentSession()->get('userid');
			}

			if (!is_array($params['nodeid']))
			{
				$params['nodeid'] = array($params['nodeid']);
			}

			$params = vB::getCleaner()->cleanArray($params, array(
				'userid' => vB_Cleaner::TYPE_UINT,
				'nodeid' => vB_Cleaner::TYPE_ARRAY_UINT,
			));

			$sqlJoin = array();
			$sqlFields = array("node.*");
			if (!defined('VB_AREA') OR VB_AREA != 'Upgrade')
			{
				$sqlJoin[] = "LEFT JOIN " . TABLE_PREFIX . "editlog AS editlog ON (editlog.nodeid = node.nodeid)";
				$sqlFields[] = "editlog.reason AS edit_reason, editlog.userid AS edit_userid, editlog.username AS edit_username, editlog.dateline AS edit_dateline, editlog.hashistory";
			}

			if ($params['userid'])
			{
				if ($threadmarking = vB::getDatastore()->getOption('threadmarking'))
				{
					$sqlFields[] = "IF (noderead.readtime, noderead.readtime, 0) AS readtime";
					$sqlJoin[] = "LEFT JOIN " . TABLE_PREFIX . "noderead AS noderead ON (node.nodeid = noderead.nodeid AND noderead.userid = {$params['userid']})";
				}
			}
			else
			{
				$sqlFields[] = "0 AS readtime";
			}

			$ids = implode(',', $params['nodeid']);

			$sql = "SELECT " . implode(", ", $sqlFields) . "
			FROM " . TABLE_PREFIX . "node AS node
			" . implode("\n", $sqlJoin) . "
			 WHERE node.nodeid IN ({$ids})";

			$sql .= " \n/**" . __FUNCTION__ . (defined('THIS_SCRIPT') ? '- ' . THIS_SCRIPT : '') . "**/";

			$resultclass = 'vB_dB_' . $this->db_type . '_result';
			$config = vB::getConfig();

			if (isset($config['Misc']['debug_sql']) AND $config['Misc']['debug_sql'])
			{
				echo "sql: $sql<br />\n";
			}
			$result = new $resultclass($db, $sql);
			return $result;
		}
	}


	/**
	 * Gets the Media Max Count for the media tab or profile media page
	 *
	 * @param	array $params
	 * @param	object $db
	 * @param	bool $check_only
	 * @result bool | object -- bool if check_only is true, or db result set if not
	 */
	public function fetchGalleryPhotosCount($params, $db, $check_only = false)
	{
		// Keep the queries in sync with fetchGalleryPhotos()

		if ($check_only)
		{
			//We need a userid or channelid or module_filter_nodes (search_photos module)
			if (empty($params['userid']) AND empty($params['channelid']) AND empty('module_filter_nodes'))
			{
				return false;
			}
			// we need the extensions list.
			if (empty($params['extensions']))
			{
				return false;
			}
			return true;
		}
		else
		{
			// params are cleaned in cleanFetchGalleryPhotoParams()
			$unclean = $params;
			unset($params);
			$params = $this->cleanFetchGalleryPhotoParams($unclean, $db);
			$helperData = $this->fetchGalleryPhotoSql($params, $db);
			$sql = $helperData['sql'];

			// DO COUNT QUERY
			$sql = "SELECT COUNT(nodeid) AS count FROM (\n" . $sql . "\n) as dummy_alias";


			$sql .= "\n/**" . __FUNCTION__ . (defined('THIS_SCRIPT') ? '- ' . THIS_SCRIPT : '') . "**/";

			$resultclass = 'vB_dB_' . $this->db_type . '_result';
			$config = vB::getConfig();

			if (isset($config['Misc']['debug_sql']) AND $config['Misc']['debug_sql'])
			{
				echo "sql: $sql<br />\n";
			}

			$result = new $resultclass($db, $sql);
			return $result;
		}
	}

	private function cleanModuleFilterNodes($filterChannels)
	{
		// Copied from vb_Search_Criteria::add_inc_exc_channel_filter()

		// This is set by the runtime vB5_Template_Runtime::parseJSON() which is called when the search template
		// calls vb:compilesearch on the json
		$currentChannelid = $filterChannels['currentChannelid'] ?? 0;
		$cleaned = vB_Library::instance('widget')->cleanFilterNodes($filterChannels, $currentChannelid);
		/*
		$filterType = $cleaned['filterType'];
		$filterChannels = $cleaned['filterChannels'];
		$includeChildren = $cleaned['includeChildren'];
		*/
		if (empty($cleaned['filterChannels']))
		{
			return [];
		}

		return $cleaned;
	}

	private function cleanFetchGalleryPhotoParams($params, $db)
	{
		$params = vB::getCleaner()->cleanArray($params, [
			'userid'        => vB_Cleaner::TYPE_UINT,
			'channelid'     => vB_Cleaner::TYPE_UINT,
			'module_filter_nodes' => vB_Cleaner::TYPE_NOCLEAN, // this is cleaned right below.
			'dateFilter'    => vB_Cleaner::TYPE_STR,
			'date_range'    => vB_Cleaner::TYPE_UINT,
			'showFilter'    => vB_Cleaner::TYPE_STR,
			'extensions'    => vB_Cleaner::TYPE_ARRAY_STR, // ESCAPE THIS!!
			vB_dB_Query::PARAM_LIMIT     => vB_Cleaner::TYPE_UINT,
			vB_dB_Query::PARAM_LIMITPAGE => vB_Cleaner::TYPE_UINT,
			'sort'          => vB_Cleaner::TYPE_STR,
		]);

		if (isset($params['module_filter_nodes']))
		{
			$cleaned = $this->cleanModuleFilterNodes($params['module_filter_nodes']);
			if (!empty($cleaned))
			{
				// Keeping the naming the same with search queries
				$params['inc_exc_channel'] = $cleaned;
				// This can't work with the singular channelid filter, so unset that.
				unset($params['channelid']);
			}
			unset($params['module_filter_nodes']);
		}


		$temp = [];
		foreach ($params['extensions'] AS $__ext)
		{
			$temp[] = $db->escape_string(strtolower($__ext));
		}
		unset($params['extensions']);

		$params['clean_extensions_list'] = "'" . implode("', '", $temp) . "'";

		switch ($params['showFilter'])
		{
			case 'vBForum_Text':
			case 'vBForum_Gallery':
			case 'vBForum_Video':
			case 'vBForum_Link':
			case 'vBForum_Poll':
			case 'vBForum_Event':
				$params['showFilter'] = vB_Types::instance()->getContentTypeID($params['showFilter']);
				break;
			case 'show_all':
			default:
				$params['showFilter'] = '';
				break;
		}
		// We're not planning on using these directly in queries, but let's still
		// whitelist them.

		switch ($params['dateFilter'])
		{
			case 'time_today':
			case 'time_lastweek':
			case 'time_lastmonth':
				break;
			case 'time_all':
			default:
				$params['dateFilter'] = '';
				break;
		}

		if ($params['date_range'] > 0)
		{
			// This can't work together with dateFilter.
			unset($params['dateFilter']);
		}
		else
		{
			unset($params['date_range']);
		}

		switch($params['sort'])
		{
			case 'thread':
				break;
			default:
				$params['sort'] = '';
				break;
		}

		return $params;
	}

	private function fetchGalleryPhotoSql($params, $db)
	{
		$extensionsWhere = '';
		$extensionsWhere = " AND filedata.extension IN (" . $params['clean_extensions_list'] . ") \n";

		$attachType = vB_Types::instance()->getContentTypeID('vBForum_Attach');
		$photoType = vB_Types::instance()->getContentTypeID('vBForum_Photo');
		$pmType = vB_Types::instance()->getContentTypeID('vBForum_PrivateMessage');
		$albumChannel = vB_Library::instance('node')->fetchAlbumChannel();
		// image attach/thumb view permissions
		$userContext = vB::getUserContext();
		$currentUserid = intval($userContext->fetchUserId());
		[
			'imgfullchannels' => $imgfullchannels,
			'imgthumbchannels' => $imgthumbchannels,
		] = $userContext->getAllChannelAccess();
		$imgthumbchannels = array_map('intval', $imgthumbchannels);


		$sqlJoin = [];
		$sqlWhere = ['TRUE'];
		if (!empty($params['userid']))
		{
			$sqlWhere[] = "node.userid = {$params['userid']}";
		}

		$filterChannels = [];
		if (!empty($params['channelid']))
		{
			$sqlJoin[] = "INNER JOIN " . TABLE_PREFIX . "closure AS cl ON cl.child = node.nodeid AND cl.parent = {$params['channelid']}";
			// We *could* do this instead, but currently not sure what the performance implications are.
			// edit: actually, no we shouldn't, because it won't do the "depth" properly unless we fetch all of the subchannels.
			//$filterChannels = [$params['channelid']];
		}
		// Note, the cleanSQL converts module_filter_nodes to inc_exc_channel (for parity with the key used in search queries)
		else if (isset($params['inc_exc_channel']))
		{
			// copied from vbdbsearch querydef's process_inc_exc_channel_filter()
			$filterType = $params['inc_exc_channel']['filterType'];
			$filterChannels = $params['inc_exc_channel']['filterChannels'];
			$includeChildren = $params['inc_exc_channel']['includeChildren'];
			//convert into an explicit list of channels we will include.
			$filterChannels = vB_Library::instance('search')->convertToParentChannels($filterType, $filterChannels, $includeChildren);
			// IMPORTANT: We also rely on the photo_starter & attach_starter conditions below!!
		}
		else
		{
			/*
				Note, you cannot upload attachments to albums, you can only upload photos (vbforum_photo)
				TODO: can you not e.g. include an inline attachment image to a text post in the album?
			 */
			$sqlJoin['photo_only'] = "LEFT JOIN " . TABLE_PREFIX . "closure AS cl_photo " .
				"ON cl_photo.child = node.nodeid AND cl_photo.parent = $albumChannel";
			$sqlWhere['photo_only'] = 'cl_photo.child IS NULL';
			// We *could* do this instead, but currently not sure what the performance implications are.
			//$filterChannels = [$albumChannel];
		}
		$permflags = $this->getNodePermTerms(false, null, $filterChannels);

		// Critical edge case, user can't see images or thumbnails for ANY channels...
		if (empty($imgthumbchannels))
		{
			// in this case, we can only return photos iff the requested $userid is
			// the current userid . . . .
			$photoStarterWhere = "photo_starter.userid = $currentUserid AND ";
			$attachStarterWhere = "attach_starter.userid = $currentUserid AND ";

			if (!empty($filterChannels))
			{
				$channelsCSV = implode(', ', $filterChannels);
				$photoStarterWhere = "photo_starter.parentid IN ($channelsCSV) AND $photoStarterWhere";
				$attachStarterWhere = "attach_starter.parentid IN ($channelsCSV) AND $photoStarterWhere";
			}
		}
		else
		{
			if (!empty($filterChannels))
			{
				$imgthumbchannels = array_intersect($imgthumbchannels, $filterChannels);
				$channelsCSV = implode(', ', $imgthumbchannels);
				// If we're looking for specific channels, then we do not want to return the self-photos.
				$photoStarterWhere = "photo_starter.parentid IN ($channelsCSV) AND ";
				$attachStarterWhere = "attach_starter.parentid IN ($channelsCSV) AND ";
			}
			else
			{
				// $imgthumbchannels contain channels that can show full images OR thumbnails only.
				$channelsCSV = implode(', ', $imgthumbchannels);
				$photoStarterWhere = "photo_starter.parentid IN ($channelsCSV)";
				$attachStarterWhere = "attach_starter.parentid IN ($channelsCSV)";
				if (!empty($currentUserid))
				{
					$photoStarterWhere = "($photoStarterWhere OR photo_starter.userid = $currentUserid)";
					$attachStarterWhere = "($attachStarterWhere OR attach_starter.userid = $currentUserid)";
				}
				$photoStarterWhere .= ' AND ';
				$attachStarterWhere .= ' AND ' ;
			}
		}

		$timenow = vB::getRequest()->getTimeNow();
		if (!empty($params['dateFilter']))
		{
			switch ($params['dateFilter'])
			{
				case 'time_today':
					$sqlWhere[] = "node.publishdate > " . ($timenow - 86400);
					break;
				case 'time_lastweek':
					$sqlWhere[] = "node.publishdate > " . ($timenow - 7 * 86400);
					break;
				case 'time_lastmonth':
					$sqlWhere[] = "node.publishdate > " . ($timenow - 30 * 86400);
					break;
				default:
					break;
			}
		}
		else if (!empty($params['date_range']) AND is_numeric($params['date_range']))
		{
			$dateline = $timenow - $params['date_range'] * 86400;
			$sqlWhere[] = "node.publishdate > $dateline";
		}

		if (!empty($params['showFilter']) AND is_numeric($params['showFilter']))
		{
			$sqlWhere[] = "parent.contenttypeid = " . intval($params['showFilter']);
		}

		/*
			Previously returned fields:
		 node.nodeid, node.title, node.description, node.contenttypeid, node.publishdate,
		 parent.nodeid AS parentnode, parent.title AS parenttitle, parent.authorname,
		 parent.routeid, parent.userid, parent.setfor AS parentsetfor,
		 start.title AS startertitle
		*/

		/*
			Note, the joins on parent & starter are required by vB5_Frontend_Controller_Filedata::actionGallery()
			which calls this through vB_Api_Profile::getSlideshow()

			Joins on the parent are now also used with sort & filtering for vB_Api_Profile::getAlbum() &
			getSlideshow().
			Generally, getAlbum() is used to generate the thumbnail list for templates, while getSlideshow()
			is used by vBSlideshow.js to generate the lightboxed slideshow arrays. They may have, in the
			past, been different, but nowadays should return the same data and are redudant and should
			be collapsed. This is based on recent demo feedback for photos tab pagination slideshows,
			where it was requested that the slideshows show what's on the page rather than the full,
			unpaginated collection.
		 */

		$sqlPhoto = "
		SELECT
			node.nodeid,
			node.title,
			node.htmltitle,
			node.contenttypeid,
			node.publishdate,
			parent.nodeid AS parentnode,
			parent.title AS parenttitle,
			parent.authorname,
			parent.routeid,
			parent.userid,
			parent.setfor AS parentsetfor,
			parent.starter = parent.nodeid AS parent_isstarter,
			parent.created AS parent_created,
			photo_starter.title AS startertitle,
			photo_starter.userid AS gallery_userid,
			photo_starter.parentid AS channelid,
			photo.caption,
			filedata.filedataid,
			NULL AS filename,
			false AS isAttach
		FROM " . TABLE_PREFIX . "node AS node
		INNER JOIN " . TABLE_PREFIX . "node AS parent ON node.parentid = parent.nodeid
		INNER JOIN " . TABLE_PREFIX . "node AS photo_starter ON node.starter = photo_starter.nodeid
		INNER JOIN " . TABLE_PREFIX . "photo AS photo ON node.nodeid = photo.nodeid
		INNER JOIN " . TABLE_PREFIX . "filedata AS filedata ON photo.filedataid = filedata.filedataid
		" . implode("\n", $sqlJoin) . "
		" . implode("\n", $permflags['joins']) . "
		WHERE node.contenttypeid = $photoType AND
			photo_starter.contenttypeid <> $pmType AND
			$photoStarterWhere
			" . implode(' AND ', $sqlWhere) . "
			" . $permflags['where'];
		// We don't need the $extensionsWhere here since photos are assumed to be images only.


		// The photo_only bits exclude any photos that were part of "profile albums" (under the special album channel).
		// Since you can't add attachments to albums, the left join check for "not part of profile album" is
		// unnecessary for attach counts.
		unset($sqlJoin['photo_only'], $sqlWhere['photo_only']);

		$sqlAttach = "
		SELECT
			node.nodeid,
			node.title,
			node.htmltitle,
			node.contenttypeid,
			node.publishdate,
			parent.nodeid AS parentnode,
			parent.title AS parenttitle,
			parent.authorname,
			parent.routeid,
			parent.userid,
			parent.setfor AS parentsetfor,
			parent.starter = parent.nodeid AS parent_isstarter,
			parent.created AS parent_created,
			attach_starter.title AS startertitle,
			attach_starter.userid AS gallery_userid,
			attach_starter.parentid AS channelid,
			NULL AS caption,
			filedata.filedataid,
			attach.filename,
			true AS isAttach
		FROM " . TABLE_PREFIX . "node AS node
		INNER JOIN " . TABLE_PREFIX . "node AS parent ON node.parentid = parent.nodeid
		INNER JOIN " . TABLE_PREFIX . "node AS attach_starter ON node.starter = attach_starter.nodeid
		INNER JOIN " . TABLE_PREFIX . "attach AS attach ON node.nodeid = attach.nodeid
		INNER JOIN " . TABLE_PREFIX . "filedata AS filedata ON attach.filedataid = filedata.filedataid
		" . implode("\n", $sqlJoin) . "
		" . implode("\n", $permflags['joins']) . "
		WHERE node.contenttypeid = $attachType AND
			attach_starter.contenttypeid <> $pmType AND
			$attachStarterWhere
			" . implode(' AND ', $sqlWhere) . "
			" . $permflags['where'] .
			$extensionsWhere;
		// We need the extensionsWhere here since we don't know whether the attachment is an image or not.

		$sql =	$sqlPhoto
			. "\nUNION ALL\n" // No need for DISTINCT here, as nodeid's should be unique already.
			. $sqlAttach;

		return array(
			'sql' => $sql,
		);
	}

	/**
	 * Gets the Media data for the media tab or profile media page
	 *
	 * @param	array $params
	 * @param	object $db
	 * @param	bool $check_only
	 * @result bool | object -- bool if check_only is true, or db result set if not
	 */
	public function fetchGalleryPhotos($params, $db, $check_only = false)
	{
		// Keep the queries in sync with fetchProfileMedia()

		if ($check_only)
		{
			//We need a userid or channelid or module_filter_nodes (search_photos module)
			if (empty($params['userid']) AND empty($params['channelid']) AND empty('module_filter_nodes'))
			{
				return false;
			}
			// we need the extensions list.
			if (empty($params['extensions']))
			{
				return false;
			}
			return true;
		}
		else
		{
			// params are cleaned in cleanFetchGalleryPhotoParams()
			// I'm explicitly unsetting unclean params rather than cleaning by
			// reference because I think it's more regression proof this way.
			$unclean = $params;
			unset($params);
			$params = $this->cleanFetchGalleryPhotoParams($unclean, $db);
			$helperData = $this->fetchGalleryPhotoSql($params, $db);
			$sql = $helperData['sql'];

			switch($params['sort'])
			{
				case 'thread':
					$sql .= " ORDER BY parent_isstarter DESC, parent_created ASC, nodeid ASC \n";
					break;
				default:
					$sql .= " ORDER BY publishdate DESC, nodeid ASC \n";
					break;
			}

			if (isset($params[vB_dB_Query::PARAM_LIMIT]) AND intval($params[vB_dB_Query::PARAM_LIMIT]))
			{
				$perpage = intval($params[vB_dB_Query::PARAM_LIMIT]);
			}
			else
			{
				$perpage = 60;
			}

			if (isset($params[vB_dB_Query::PARAM_LIMITPAGE]) AND (intval($params[vB_dB_Query::PARAM_LIMITPAGE]) > 1))
			{
				$start=  ($perpage * (intval($params[vB_dB_Query::PARAM_LIMITPAGE]) - 1)) ;
			}
			else
			{
				$start = 0 ;
			}

			$sql .= "LIMIT $start, $perpage";

			return $this->getResultSet($db, $sql, __FUNCTION__);
		}

	}

	/** Gets the Media  for the profile page.
	 *
	 *	@param	mixed
	 *	@param	mixed 	a db pointer
	 *  @param	bool
	 *
	 *	@result	mixed
	 * **/
	public function fetchProfileMedia($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			//We need a userid or a nodeid
			if (empty($params['userId']) AND empty($params['channelId']))
			{
				return false;
			}
			return true;
		}
		else
		{
			$params = vB::getCleaner()->cleanArray($params, [
				'channelId' => vB_Cleaner::TYPE_UINT,
				'userId' => vB_Cleaner::TYPE_UINT,
				// unclean str consumed & unset below
				'type' => vB_Cleaner::TYPE_STR,
			]);
			$includeGallery = (empty($params['type']) OR ($params['type'] == 'gallery'));
			$includeVideo = (empty($params['type']) OR ($params['type'] == 'video'));
			unset($params['type']);
			$types = vB_Types::instance();
			$attachType = $types->getContentTypeID('vBForum_Attach');
			$photoType = $types->getContentTypeID('vBForum_Photo');
			$pmType = $types->getContentTypeID('vBForum_PrivateMessage');
			$phrases = vB_Api::instanceInternal('phrase')->fetch(['posted_photos', 'videos']);
			$albumChannel = vB_Library::instance('node')->fetchAlbumChannel();
			$permflags = $this->getNodePermTerms();
			$userContext = vB::getUserContext();
			$currentUserid = intval($userContext->fetchUserId());
			[
				'imgfullchannels' => $imgfullchannels,
				'imgthumbchannels' => $imgthumbchannels,
			] = $userContext->getAllChannelAccess();
			$imgthumbchannels = array_map('intval', $imgthumbchannels);

			if (empty($imgthumbchannels))
			{
				$photoStarterWhere = "photo_starter.userid = $currentUserid ";
				$attachStarterWhere = "attach_starter.userid = $currentUserid ";
			}
			else
			{
				$channelsCSV = implode(', ', $imgthumbchannels);
				$photoStarterWhere = "photo_starter.parentid IN ($channelsCSV)";
				$attachStarterWhere = "attach_starter.parentid IN ($channelsCSV)";
				if (!empty($currentUserid))
				{
					$photoStarterWhere = "($photoStarterWhere OR photo_starter.userid = $currentUserid)";
					$attachStarterWhere = "($attachStarterWhere OR attach_starter.userid = $currentUserid)";
				}
			}

			$join = $where = [];

			if (!empty($params['channelId']))
			{
				$join[] = "INNER JOIN " . TABLE_PREFIX . "closure AS channelClosure ON channelClosure.child = node.nodeid AND channelClosure.parent = " . $params['channelId'] . "\n";
			}

			if (!empty($params['userId']))
			{
				$where[] = "node.userid = " .  $params['userId'] . "\n";
			}

			$sqlJoin = implode("\n", $join);
			$sqlWhere = empty($where) ? 'TRUE' : implode(' AND ', $where);
			$sql = '';
			$concat = false;

			// "Posted Photos" pseudo gallery
			if ($includeGallery)
			{
				// Keep this block in sync with fetchGalleryPhotos() above

				$extensions = vB_Api::instanceInternal('content_attach')->getImageExtensions();
				$extensions = $extensions['extensions'];
				$temp = array();
				foreach ($extensions AS $__ext)
				{
					$temp[] = $db->escape_string(strtolower($__ext));
				}
				$extensionsWhere = " AND filedata.extension IN ('" . implode("', '", $temp) . "') \n";

				$sqlPhoto = "SELECT node.nodeid
					FROM " . TABLE_PREFIX . "node AS node
					INNER JOIN " . TABLE_PREFIX . "photo AS photo ON node.nodeid = photo.nodeid
					INNER JOIN " . TABLE_PREFIX . "node AS photo_starter ON node.starter = photo_starter.nodeid
					LEFT JOIN " . TABLE_PREFIX . "closure AS albumClosure
						ON albumClosure.child = node.nodeid AND albumClosure.parent = $albumChannel
					$sqlJoin
					" . implode("\n", $permflags['joins']) . "
					WHERE node.contenttypeid = $photoType AND $sqlWhere AND
						photo_starter.contenttypeid <> $pmType AND
						$photoStarterWhere AND
						albumClosure.child IS NULL
						" . $permflags['where'];
				// We don't need the $extensionsWhere above since photos are assumed to be images only.

				$sqlAttach = "SELECT node.nodeid
					FROM " . TABLE_PREFIX . "node AS node
					INNER JOIN " . TABLE_PREFIX . "attach AS attach ON node.nodeid = attach.nodeid
					INNER JOIN " . TABLE_PREFIX . "node AS attach_starter ON node.starter = attach_starter.nodeid
					INNER JOIN " . TABLE_PREFIX . "filedata AS filedata ON attach.filedataid = filedata.filedataid
					$sqlJoin
					" . implode("\n", $permflags['joins']) . "
					WHERE node.contenttypeid = $attachType AND $sqlWhere AND
						attach_starter.contenttypeid <> $pmType AND
						$attachStarterWhere
						" . $permflags['where'] .
						$extensionsWhere;
				// We need the extensionsWhere here since we don't know whether the attachment is an image or not.

				$sql .= "SELECT -2 AS nodeid,
						'" . $db->escape_string($phrases['posted_photos']) . "' AS title,
						'" . $db->escape_string($phrases['posted_photos']) . "' AS htmltitle,
						count(alias_posted_photos.nodeid) AS qty,
						NULL as starter,
						NULL as starterroute,
						NULL as startertitle,
						max(alias_posted_photos.nodeid) AS childnode,
						NULL as provider,
						NULL as code,
						NULL as albumid
					FROM (
						$sqlPhoto
						UNION ALL
						$sqlAttach
					) AS alias_posted_photos
					HAVING count(alias_posted_photos.nodeid) > 0\n";
				// No need for UNION DISTINCT above, as nodeid's should be unique already.

				 $concat =  true;
			}

			if ($includeVideo)
			{
				$sql .= ($concat) ? "UNION ALL\n" : '';
				$sql .= "(SELECT -1 AS nodeid, '" . $db->escape_string($phrases['videos']) . "' AS title,
				  '" . $db->escape_string($phrases['videos']) . "' AS htmltitle, count(node.nodeid) AS qty,
				  NULL as starter, NULL as starterroute, NULL as startertitle, 0 AS childnode, v.provider, v.code, node.parentid as albumid
			  	FROM " . TABLE_PREFIX . "node AS node
				$sqlJoin
			  	INNER JOIN " . TABLE_PREFIX . "videoitem AS v ON v.nodeid = node.nodeid
				  " . implode("\n", $permflags['joins']) . "
				  WHERE $sqlWhere
				  " . $permflags['where'] . "
				  HAVING COUNT(node.nodeid) > 0)\n";
				$concat =  true;
			}

			// profile albums.
			if (
				$includeGallery AND (
					isset($imgthumbchannels[$albumChannel]) OR
					!empty($params['userid']) AND $params['userid'] == $currentUserid
				)
			)
			{
				$sql .= ($concat) ? "UNION ALL\n" : '';
				$sql .= "(SELECT node.nodeid, node.title, node.htmltitle, count(child.nodeid) AS qty, node.starter,
					ns.routeid AS starterroute, ns.title AS startertitle, max(child.nodeid) AS childnode, NULL as provider, NULL as code, node.parentid as albumid
					FROM " . TABLE_PREFIX . "node AS node
					$sqlJoin
					INNER JOIN " . TABLE_PREFIX . "node AS child ON child.parentid = node.nodeid
					INNER JOIN " . TABLE_PREFIX . "node AS ns ON ns.nodeid = node.starter
				  " . implode("\n", $permflags['joins']) . "
				  WHERE node.parentid = $albumChannel AND child.showpublished > 0
					AND $sqlWhere AND child.contenttypeid  = $photoType
					" . $permflags['where'] . "
					GROUP BY node.nodeid, node.title
				ORDER BY node.publishdate) ";
			}

			$sql .= "\n/**" . __FUNCTION__ . (defined('THIS_SCRIPT') ? '- ' . THIS_SCRIPT : '') . "**/";

			$resultclass = 'vB_dB_' . $this->db_type . '_result';
			$config = vB::getConfig();

			if (isset($config['Misc']['debug_sql']) AND $config['Misc']['debug_sql'])
			{
				echo "sql: $sql<br />\n";
			}
			$result = new $resultclass($db, $sql);
			return $result;
		}
	}
	/**
	 * Gets the Media  for the profile page.
	 *
	 * @param	array $params
	 * @param	object $db
	 * @param	bool $check_only
	 * @result bool | object -- bool if check_only is true, or db result set if not
	 */
	public function fetchVideoNodes($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			//We need a userid or a nodeid
			if (empty($params['userid']) AND empty($params['nodeid']))
			{
				return false;
			}
			return true;
		}
		else
		{
			$params = vB::getCleaner()->cleanArray($params, array(
				'nodeid' => vB_Cleaner::TYPE_UINT,
				'userid' => vB_Cleaner::TYPE_UINT,
				'dateFilter' => vB_Cleaner::TYPE_STR,
				vB_dB_Query::PARAM_LIMIT => vB_Cleaner::TYPE_UINT,
				vB_dB_Query::PARAM_LIMITPAGE => vB_Cleaner::TYPE_UINT
			));

			$permflags = $this->getNodePermTerms();
			$sql = "SELECT node.nodeid
		  	FROM " . TABLE_PREFIX . "node AS node ";

			if (!empty($params['nodeid']))
			{
				$sql .= "INNER JOIN ". TABLE_PREFIX . "closure AS clLimit ON clLimit.child = node.nodeid \n";
			}
			$sql .=  implode("\n", $permflags['joins']) ;
			$sql .=  " WHERE node.contenttypeid = " .vB_Types::instance()->getContentTypeId('vBForum_Video') . "\n";

			if (!empty($params['nodeid']))
			{
				$sql .= " AND clLimit.parent = " . $params['nodeid'] . "\n";
			}

			if (!empty($params['userid']))
			{
				$sql .= " AND node.userid = " . $params['userid'] . "\n";
			}

			switch ($params['dateFilter'])
			{
				case 'time_today':
				{
					$sql .= " AND node.publishdate > " . vB::getRequest()->getTimeNow() . ' - 86400';
					break;
				}
				case 'time_lastweek':
				{
					$sql .= " AND node.publishdate > "  . vB::getRequest()->getTimeNow() . ' - (7 * 86400)';
					break;
				}
				case 'time_lastmonth':
				{
					$sql .= " AND node.publishdate > " . vB::getRequest()->getTimeNow() . ' - (30 * 86400)';
					break;
				}
			}

			$sql .=  $permflags['where'] . "
			ORDER BY node.publishdate LIMIT ";

			if (isset($params[vB_dB_Query::PARAM_LIMIT]) AND intval($params[vB_dB_Query::PARAM_LIMIT]))
			{
				$perpage = intval($params[vB_dB_Query::PARAM_LIMIT]);
			}
			else
			{
				$perpage = 10;
			}

			if (isset($params[vB_dB_Query::PARAM_LIMITPAGE]) AND intval($params[vB_dB_Query::PARAM_LIMITPAGE]) AND (intval($params[vB_dB_Query::PARAM_LIMITPAGE]) > 1))
			{
				$sql .=  ($perpage * (intval($params[vB_dB_Query::PARAM_LIMITPAGE]) - 1)) . ',';
			}

			$sql .= $perpage;

			return $this->getResultSet($db, $sql, __FUNCTION__);
		}
	}


	/**
	 * gets the count of videos for a video album page.
	 *
	 * @param	array $params
	 * @param	object $db
	 * @param	bool $check_only
	 * @result bool | object -- bool if check_only is true, or db result set if not
	 */
	public function fetchVideoCount($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			//We need a userid or a nodeid
			if (empty($params['userid']) AND empty($params['nodeid']))
			{
				return false;
			}
			return true;
		}
		else
		{
			$params = vB::getCleaner()->cleanArray($params, array(
				'nodeid' => vB_Cleaner::TYPE_UINT,
				'userid' => vB_Cleaner::TYPE_UINT,
				'dateFilter' => vB_Cleaner::TYPE_STR
			));
			$permflags = $this->getNodePermTerms();
			$sql = "SELECT count( vi.videoitemid) AS count
		  	FROM " . TABLE_PREFIX . "node AS node
		  	INNER JOIN " . TABLE_PREFIX . "videoitem AS vi ON vi.nodeid = node.nodeid \n";
			if (!empty($params['nodeid']))
			{
				$sql .= "INNER JOIN ". TABLE_PREFIX . "closure AS clLimit ON clLimit.child = node.nodeid \n";
			}
			$sql .=  implode("\n", $permflags['joins']) ;
			$wheres = array();

			if (!empty($params['nodeid']))
			{
				$wheres[] = "clLimit.parent = " . $params['nodeid'];
			}

			if (!empty($params['userid']))
			{
				$wheres[] = "node.userid = " . $params['userid'];
			}

			switch ($params['dateFilter'])
			{
				case 'time_today':
				{
					$wheres[] = "node.publishdate > " . vB::getRequest()->getTimeNow() . " - 86400";
					break;
				}
				case 'time_lastweek':
				{
					$wheres[] = "node.publishdate > " . vB::getRequest()->getTimeNow() . " - (7 * 86400)";
					break;
				}
				case 'time_lastmonth':
				{
					$wheres[] = "node.publishdate > " . vB::getRequest()->getTimeNow() . " - (30 * 86400)";
					break;
				}
			}

			$sql .=  " WHERE " . implode(" AND ", $wheres) ."\n " . $permflags['where'] ;

			return $this->getResultSet($db, $sql, __FUNCTION__);
		}
	}

	// keep this in sync with listFlattenedPrivateMessages
	public function countFlattenedPrivateMessages($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			//We need at least a userid and a folderid.
			if (empty($params['userid']) OR empty($params['folderid']))
			{
				return false;
			}
			return true;
		}
		else
		{
			$params = vB::getCleaner()->cleanArray($params, array(
				'userid' => vB_Cleaner::TYPE_UINT,
				'folderid' => vB_Cleaner::TYPE_UINT,
				'skipSenders' => vB_Cleaner::TYPE_ARRAY_INT,
				'unreadOnly' => vB_Cleaner::TYPE_BOOL,
			));

			// This is used for the global blocked users. Also can be used to skip specific users, like the current user (if we want to skip the self-replies)
			$sql_where_skipsenders = "";
			if (!empty($params['skipSenders']))
			{
				$sql_where_skipsenders = "\n\tAND node.userid NOT IN (" . implode(',', $params['skipSenders']) . ")";
			}

			// Unread only
			$sql_where_unreadonly = "";
			if (!empty($params['unreadOnly']))
			{
				$sql_where_unreadonly = "\n\tAND s.msgread = 0";
			}

			/*
			Almost all of these joins are NOT useful for this query, but I'm keeping them in the spirit of
			keeping it perfectly synced with listFlattenedPrivateMessages()... as there's a possibility
			that broken(?) records (e.g. missing text records) could cause a deviation if we just
			strip out all joins except sentto, node.
			 */
			$sql = "
				SELECT COUNT(node.nodeid) AS total
				FROM " . TABLE_PREFIX . "sentto AS s \n
				INNER JOIN " . TABLE_PREFIX . "node AS node
					ON node.nodeid = s.nodeid
				INNER JOIN " . TABLE_PREFIX . "node AS starter
					ON starter.nodeid = node.starter
				INNER JOIN " . TABLE_PREFIX . "text AS text
					ON text.nodeid = node.nodeid
				WHERE s.folderid = " . $params['folderid'] . " AND s.deleted = 0 AND s.userid = " . $params['userid'] .
				$sql_where_skipsenders .
				$sql_where_unreadonly;

			return $this->getResultSet($db, $sql, __FUNCTION__);
		}
	}

	public function listFlattenedPrivateMessages($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			//We need at least a userid and a folderid.
			if (empty($params['userid']) OR empty($params['folderid']))
			{
				return false;
			}
			return true;
		}
		else
		{
			$params = vB::getCleaner()->cleanArray($params, array(
				'userid' => vB_Cleaner::TYPE_UINT,
				'folderid' => vB_Cleaner::TYPE_UINT,
				'skipSenders' => vB_Cleaner::TYPE_ARRAY_INT,
				'sort' => vB_Cleaner::TYPE_STR,
				'sortDir' => vB_Cleaner::TYPE_STR,
				vB_dB_Query::PARAM_LIMIT => vB_Cleaner::TYPE_UINT,
				vB_dB_Query::PARAM_LIMITPAGE => vB_Cleaner::TYPE_UINT,
				// ATM only used for the "count" query above, but keeping things synced.
				// The flag is used with the count query for "forum" call's notifications_menubits, not
				// the private_messagelist call.
				'unreadOnly' => vB_Cleaner::TYPE_BOOL,
			));

			// This is used for the global blocked users. Also can be used to skip specific users, like the current user (if we want to skip the self-replies)
			$sql_where_skipsenders = "";
			if (!empty($params['skipSenders']))
			{
				$sql_where_skipsenders = "\n\tAND node.userid NOT IN (" . implode(',', $params['skipSenders']) . ")";
			}

			$sortcolumn = 'publishdate';
			switch ($params['sort'])
			{
				// TODO: sender, title sorting
				case 'date':
				default:
					$sortcolumn = 'publishdate';
					break;
			}
			unset($params['sort']); // If you need this in the sql string, do not use unescaped.

			if (isset($params['sortDir']) AND ($params['sortDir'] == "ASC"))
			{
				$sql_orderby = "\nORDER BY $sortcolumn ASC ";
			}
			else
			{
				$sql_orderby = "\nORDER BY $sortcolumn DESC ";
			}
			unset($params['sortDir']); // If you need this in the sql string, do not use unescaped.

			if (isset($params[vB_dB_Query::PARAM_LIMIT]) AND intval($params[vB_dB_Query::PARAM_LIMIT]))
			{
				$perpage = intval($params[vB_dB_Query::PARAM_LIMIT]);
			}
			else
			{
				$perpage = 20;
			}

			if (isset($params[vB_dB_Query::PARAM_LIMITPAGE]) AND (intval($params[vB_dB_Query::PARAM_LIMITPAGE]) > 1))
			{
				$start=  ($perpage * (intval($params[vB_dB_Query::PARAM_LIMITPAGE]) - 1)) ;
			}
			else
			{
				$start = 0 ;
			}

			$sql_limit = "\nLIMIT $start, $perpage ";

			// Unread only
			$sql_where_unreadonly = "";
			if (!empty($params['unreadOnly']))
			{
				$sql_where_unreadonly = "\n\tAND s.msgread = 0";
			}

			/*
				Keep this in sync with countFlattenedPrivateMessages

				Note, we no longer have a userlist.type = 'ignored' NULL check because
				we're checking the user's ignorelist outside of this query & passing it in
				via skipSenders param.
			 */
			$sql = "
				SELECT
					node.nodeid, node.starter,
					node.publishdate,
					starter.title,
					text.rawtext, text.pagetext,
					s.msgread,
					node.userid AS userid,
					node.authorname AS authorname,
					u.username AS username,
					u.displayname AS displayname
				FROM " . TABLE_PREFIX . "sentto AS s \n
				INNER JOIN " . TABLE_PREFIX . "node AS node
					ON node.nodeid = s.nodeid
				INNER JOIN " . TABLE_PREFIX . "node AS starter
					ON starter.nodeid = node.starter
				INNER JOIN " . TABLE_PREFIX . "text AS text
					ON text.nodeid = node.nodeid
				LEFT JOIN " . TABLE_PREFIX . "user AS u
					ON u.userid = node.userid
				WHERE s.folderid = " . $params['folderid'] . " AND s.deleted = 0 AND s.userid = " . $params['userid'] .
				$sql_where_skipsenders .
				$sql_where_unreadonly .
				$sql_orderby .
				$sql_limit;

			return $this->getResultSet($db, $sql, __FUNCTION__);
		}
	}

	/**
	 * Lists messages from a PM folder.
	 *
	 * @param	array $params
	 * @param	object $db
	 * @param	bool $check_only
	 * @result bool | object -- bool if check_only is true, or db result set if not
	 */
	public function listPrivateMessages($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			//We need at least a userid and a folderid.
			if (empty($params['userid']) OR empty($params['folderid']) OR !isset($params['showdeleted']))
			{
				return false;
			}
			return true;
		}
		else
		{
			$params = vB::getCleaner()->cleanArray($params, array(
				'userid' => vB_Cleaner::TYPE_UINT,
				'sortDir' => vB_Cleaner::TYPE_STR,
				'folderid' => vB_Cleaner::TYPE_UINT,
				'showdeleted' => vB_Cleaner::TYPE_UINT,
				vB_dB_Query::PARAM_LIMIT => vB_Cleaner::TYPE_UINT,
				vB_dB_Query::PARAM_LIMITPAGE => vB_Cleaner::TYPE_UINT,
			));

			//keep the tests for lastauthor the same for both columns.  They *should* always be in sync but
			//we never want to use the id for one and the name for the other
			$sql = "
				SELECT folder.folderid, folder.titlephrase, folder.title AS folder, node.nodeid, node.title,
					(CASE WHEN node.lastauthorid <> 0 THEN node.lastauthorid ELSE node.userid END) AS userid,
					(CASE WHEN node.lastauthorid <> 0 THEN node.lastcontentauthor ELSE node.authorname END) AS username,
					node.created, s.msgread, text.rawtext, text.pagetext, node.lastcontent AS publishdate,
					node.lastcontentauthor AS lastauthor, node.lastauthorid AS lastauthorid, node.textcount AS responses
				FROM " . TABLE_PREFIX . "messagefolder AS folder
					INNER JOIN " . TABLE_PREFIX . "sentto AS s ON s.folderid = folder.folderid AND s.deleted = " . $params['showdeleted'] . "\n
					INNER JOIN " . TABLE_PREFIX . "node AS node ON node.nodeid = s.nodeid AND node.nodeid = node.starter
					INNER JOIN " . TABLE_PREFIX . "text AS text ON text.nodeid = node.lastcontentid
					LEFT JOIN " . TABLE_PREFIX . "userlist AS i ON i.userid = s.userid AND i.relationid = node.userid AND i.type = 'ignore'
				WHERE s.userid = " . $params['userid'] . " AND s.folderid =  " . $params['folderid'] . " AND i.type IS NULL
			";
			$sql .= "GROUP BY node.nodeid\n";

			//block people on the global ignore list.
			if (isset($params['sortDir']) AND ($params['sortDir'] == "ASC"))
			{
				$sql .= " ORDER BY publishdate ASC ";
			}
			else
			{
				$sql .= " ORDER BY publishdate DESC ";
			}

			if (isset($params[vB_dB_Query::PARAM_LIMIT]) AND intval($params[vB_dB_Query::PARAM_LIMIT]))
			{
				$perpage = intval($params[vB_dB_Query::PARAM_LIMIT]);
			}
			else
			{
				$perpage = 20;
			}

			if (isset($params[vB_dB_Query::PARAM_LIMITPAGE]) AND intval($params[vB_dB_Query::PARAM_LIMITPAGE]) AND (intval($params[vB_dB_Query::PARAM_LIMITPAGE]) > 1))
			{
				$start=  ($perpage * (intval($params[vB_dB_Query::PARAM_LIMITPAGE]) - 1)) ;
			}
			else
			{
				$start = 0 ;
			}

			$sql .= "LIMIT $start, $perpage";

			return $this->getResultSet($db, $sql, __FUNCTION__);
		}
	}

	/**
	 * Lists messages from a PM folder.
	 *
	 * @param	array $params
	 * @param	object $db
	 * @param	bool $check_only
	 * @result bool | object -- bool if check_only is true, or db result set if not
	 */
	public function listSentMessages($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			//We need at least a userid and a folderid.
			if (empty($params['userid']) OR empty($params['folderid']) OR !isset($params['showdeleted']))
			{
				return false;
			}
			return true;
		}
		else
		{
			$params = vB::getCleaner()->cleanArray($params, array(
				'userid' => vB_Cleaner::TYPE_UINT,
				'sortDir' => vB_Cleaner::TYPE_STR,
				'folderid' => vB_Cleaner::TYPE_UINT,
				'showdeleted' => vB_Cleaner::TYPE_UINT,
				vB_dB_Query::PARAM_LIMIT => vB_Cleaner::TYPE_UINT,
				vB_dB_Query::PARAM_LIMITPAGE => vB_Cleaner::TYPE_UINT,
			));

			//keep the tests for lastauthor the same for both columns.  They *should* always be in sync but
			//we never want to use the id for one and the name for the other
			$sql = "
				SELECT folder.folderid, folder.titlephrase, folder.title AS folder, starter.nodeid, starter.title,
					(CASE WHEN starter.lastauthorid <> 0 THEN starter.lastauthorid ELSE starter.userid END) AS userid,
					(CASE WHEN starter.lastauthorid <> 0 THEN starter.lastcontentauthor ELSE starter.authorname END) AS username,
					starter.created, s.msgread, text.rawtext, text.pagetext, starter.lastcontent AS publishdate,
					starter.lastcontentauthor AS lastauthor, starter.lastauthorid AS lastauthorid, starter.textcount AS responses,
					SUM(CASE WHEN s_starter.deleted = 1 AND s_starter.userid = " . $params['userid']. " THEN 1 ELSE 0 END) AS deleted
				FROM " . TABLE_PREFIX . "messagefolder AS folder
					INNER JOIN " . TABLE_PREFIX . "sentto AS s ON s.folderid = folder.folderid\n
					INNER JOIN " . TABLE_PREFIX . "node AS node ON node.nodeid = s.nodeid
					INNER JOIN " . TABLE_PREFIX . "node AS starter ON starter.nodeid = node.starter
					INNER JOIN " . TABLE_PREFIX . "text AS text ON text.nodeid = starter.lastcontentid
					INNER JOIN " . TABLE_PREFIX . "sentto AS s_starter ON s_starter.nodeid = starter.nodeid
				WHERE s.userid = " . $params['userid'] . " AND s.folderid =  " . $params['folderid'] . "  \n
			";
			$sql .= "GROUP BY starter.nodeid\n";

			if ($params['showdeleted'])
			{
				$sql .= " HAVING deleted >= 1\n";
			}
			else
			{
				$sql .= " HAVING deleted = 0\n";
			}

			//block people on the global ignore list.
			if (isset($params['sortDir']) AND ($params['sortDir'] == "ASC"))
			{
				$sql .= " ORDER BY publishdate ASC ";
			}
			else
			{
				$sql .= " ORDER BY publishdate DESC ";
			}

			if (isset($params[vB_dB_Query::PARAM_LIMIT]) AND intval($params[vB_dB_Query::PARAM_LIMIT]))
			{
				$perpage = intval($params[vB_dB_Query::PARAM_LIMIT]);
			}
			else
			{
				$perpage = 20;
			}

			if (isset($params[vB_dB_Query::PARAM_LIMITPAGE]) AND intval($params[vB_dB_Query::PARAM_LIMITPAGE]) AND (intval($params[vB_dB_Query::PARAM_LIMITPAGE]) > 1))
			{
				$start=  ($perpage * (intval($params[vB_dB_Query::PARAM_LIMITPAGE]) - 1)) ;
			}
			else
			{
				$start = 0 ;
			}

			$sql .= "LIMIT $start, $perpage";

			return $this->getResultSet($db, $sql, __FUNCTION__);
		}
	}

	/**
	 * Lists either notifications or requests
	 *
	 * @param	array $params
	 * @param	object $db
	 * @param	bool $check_only
	 * @result bool | object -- bool if check_only is true, or db result set if not
	 */
	public function listSpecialMessages($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			//We need at least a userid and a folderid.
			if (empty($params['userid']) OR empty($params['folderid']))
			{
				return false;
			}
			return true;
		}
		else
		{
			$params = vB::getCleaner()->cleanArray($params, array(
				'userid' => vB_Cleaner::TYPE_UINT,
				'sortdir' => vB_Cleaner::TYPE_STR,
				'folderid' => vB_Cleaner::TYPE_UINT,
				vB_dB_Query::PARAM_LIMIT => vB_Cleaner::TYPE_UINT,
				vB_dB_Query::PARAM_LIMITPAGE => vB_Cleaner::TYPE_UINT,
			));

			$sql = "SELECT node.*, s.msgread, text.rawtext, text.pagetext, message.about, message.aboutid
			FROM " . TABLE_PREFIX . "sentto AS s
			INNER JOIN " . TABLE_PREFIX . "node AS node ON node.nodeid = s.nodeid
			INNER JOIN " . TABLE_PREFIX . "privatemessage AS message ON node.nodeid = message.nodeid
			INNER JOIN " . TABLE_PREFIX . "text AS text ON text.nodeid = s.nodeid
			WHERE s.userid = " . $params['userid'] . " AND s.folderid =  " . $params['folderid'] . " AND s.deleted = 0\n";

			//block people on the global ignore list.

			if (isset($params['sortdir']) AND ($params['sortdir'] == "ASC"))
			{
				$sql .= " ORDER BY node.publishdate ASC";
			}
			else
			{
				$sql .= " ORDER BY node.publishdate DESC ";
			}

			if (isset($params[vB_dB_Query::PARAM_LIMIT]) AND intval($params[vB_dB_Query::PARAM_LIMIT]))
			{
				$perpage = intval($params[vB_dB_Query::PARAM_LIMIT]);
			}
			else
			{
				$perpage = 20;
			}

			if (isset($params[vB_dB_Query::PARAM_LIMITPAGE]) AND intval($params[vB_dB_Query::PARAM_LIMITPAGE]) AND (intval($params[vB_dB_Query::PARAM_LIMITPAGE]) > 1))
			{
				$start=  ($perpage * (intval($params[vB_dB_Query::PARAM_LIMITPAGE]) - 1)) ;
			}
			else
			{
				$start = 0 ;
			}

			$sql .= "LIMIT $start, $perpage \n/**" . __FUNCTION__ . (defined('THIS_SCRIPT') ? '- ' . THIS_SCRIPT : '') . "**/";

			$resultclass = 'vB_dB_' . $this->db_type . '_result';
			$config = vB::getConfig();

			if (isset($config['Misc']['debug_sql']) AND $config['Misc']['debug_sql'])
			{
				echo "sql: $sql<br />\n";
			}
			$result = new $resultclass($db, $sql);
			return $result;
		}
	}

	/**
	 * Returns specified user's notifications count.
	 *
	 * @param	array	$params			Fields
	 *									-	'userid'		Int
	 *									-	'typeid'		Optional Integer notification typeid
	 * @param	abject	$db				DB Pointer
	 * @param	bool	$check_only
	 * @result bool | object -- bool if check_only is true, or db result set if not
	 */
	public function fetchNotificationsCount($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			return isset($params['userid']);
		}
		else
		{
			$params = vB::getCleaner()->cleanArray($params, [
				'userid' => vB_Cleaner::TYPE_UINT,
				'typeid' => vB_Cleaner::TYPE_UINT,
				'readFilter' => vB_Cleaner::TYPE_STR,
			]);

			/*
				Keep this in sync with fetchNotifications()
			 */

			$where = $this->fetchNotificationsFilter($params);
			['joins' => $permjoins, 'where' => $permwhere] = $this->getNodePermTerms();

			if($permwhere)
			{
				//The perm where "helpfully" comes with the AND attached.  Need ot finesse that.
				//Should really fix it.
				$permwhere = " AND (node.nodeid IS NULL OR (1=1 $permwhere))";
			}

			$sql = "
				SELECT COUNT(*) AS count
				FROM " . TABLE_PREFIX . "notification AS notification
				LEFT JOIN " . TABLE_PREFIX . "node AS node ON (notification.sentbynodeid = node.nodeid)
			" . implode("\n", $permjoins) . "\n";

			if($where OR $permwhere)
			{
				$sql .= 'WHERE ' . implode(' AND ', $where) . $permwhere;
			}

			return $this->getResultSet($db, $sql, __FUNCTION__);
		}
	}


	/**
	 * Returns specified user's notifications
	 *
	 * @param	array	$params			Fields
	 *									-	'userid'		Int
	 *									-	'sortDir'		Optional String sort direction. If not "ASC", sort order will be DESC.
	 *									-	'typeid'		Optional Integer notification typeid
	 *									-	vB_dB_Query::PARAM_LIMIT 	Optional integer results per page. Default is 20 per page.
	 *									-	vB_dB_Query::PARAM_LIMITPAGE 	Optional integer page # to return. Default is the first page.
	 * @param	object	$db				DB Pointer
	 * @param	bool	$check_only
	 *
	 * @result bool | object -- bool if check_only is true, or db result set if not
	 */
	public function fetchNotifications($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			return isset($params['userid']);
		}
		else
		{
			$params = vB::getCleaner()->cleanArray($params, [
				'userid' => vB_Cleaner::TYPE_UINT,
				'typeid' => vB_Cleaner::TYPE_UINT,
				'readFilter' => vB_Cleaner::TYPE_STR,
				'skipIds' => vB_Cleaner::TYPE_ARRAY_UINT, //special, only used by dismissnotifications()
				'sortDir' => vB_Cleaner::TYPE_STR,
				vB_dB_Query::PARAM_LIMIT => vB_Cleaner::TYPE_UINT,
				vB_dB_Query::PARAM_LIMITPAGE => vB_Cleaner::TYPE_UINT,
			]);

			$perpage = $params[vB_dB_Query::PARAM_LIMIT];
			$start = ($perpage * ($params[vB_dB_Query::PARAM_LIMITPAGE] - 1)) ;

			/*
				Keep this in sync with fetchNotificationCount()
			*/

			$where = $this->fetchNotificationsFilter($params);
			['joins' => $permjoins, 'where' => $permwhere] = $this->getNodePermTerms();

			if($permwhere)
			{
				//The perm where "helpfully" comes with the AND attached.  Need ot finesse that.
				//Should really fix it.
				$permwhere = " AND (node.nodeid IS NULL OR (1=1 $permwhere))";
			}
			//we already have the starter
			unset($permjoins['starter']);

			if (!empty($params['skipIds']))
			{
				// If we have skipIds, this is special. We need to fetch only ONE notification
				// that would be on the current page, that's NOT in skipIds.
				// skipIds is cleaned as TYPE_ARRAY_UINT by the cleaner above.
				$where[] = 'notification.notificationid NOT IN (' . implode(',', $params['skipIds']) . ')';

				// If we have skipIds, this is special. We need to fetch only ONE notification
				// that would be on the current page, that's NOT in skipIds.
				$perpage = 1;
			}

			/*
				Note, we grab the starter via a join onto node on sentbynodeid, because a notification
				might have a sentbynodeid but no aboutstarter (e.g., nodeaction, pollvote), but no
				notification will have an aboutstarter but no sentbynodeid (AFAIK).
			*/
			$sql = "
				SELECT notification.*,
					sender.userid AS senderid,
					sender.username AS sender_username,
					sender.displayname AS sender_displayname,
					starter.title AS aboutstartertitle,
					starter.routeid AS aboutstarterrouteid,
					starter.nodeid AS aboutstarterid,
					poll.votes, poll.lastvote
				FROM " . TABLE_PREFIX . "notification AS notification
				LEFT JOIN " . TABLE_PREFIX . "user AS sender ON (notification.sender = sender.userid)
				LEFT JOIN " . TABLE_PREFIX . "node AS node ON (notification.sentbynodeid = node.nodeid)
				LEFT JOIN " . TABLE_PREFIX . "node AS starter ON (node.starter = starter.nodeid)
				LEFT JOIN " . TABLE_PREFIX . "poll AS poll ON (notification.sentbynodeid = poll.nodeid)
			" . implode("\n", $permjoins) . "\n";

			if($where OR $permwhere)
			{
				$sql .= 'WHERE ' . implode(' AND ', $where) . $permwhere;
			}

			$dir = (strcasecmp($params['sortDir'], 'ASC') == 0) ? 'ASC' : 'DESC';
			$sql .= "
				ORDER BY notification.lastsenttime $dir
				LIMIT $start, $perpage
			";

			return $this->getResultSet($db, $sql, __FUNCTION__);
		}
	}


	//To keep the count and the query in sync this *must* be all of the filtering.
	//The query includes some additional joins to get additional data so we don't consolidate the queries,
	//but that can't filter rows
	private function fetchNotificationsFilter($params)
	{
		$where = [];

		$where[] = 'notification.recipient = ' . $params['userid'];
		if ($params['typeid'])
		{
			$where[] = 'notification.typeid = ' . $params['typeid'];
		}

		if ($params['readFilter'] == 'unread_only')
		{
			$where[] = 'notification.lastsenttime > notification.lastreadtime';
		}
		else if ($params['readFilter'] == 'read_only')
		{
			$where[] = 'notification.lastsenttime <= notification.lastreadtime';
		}

		return $where;
	}

	/**
	 * Adds a node
	 *
	 * @param	array $params
	 * @param	object $db
	 * @param	bool $check_only
	 *
	 * @return	int
	 */
	//This should be broken up and replaced with multiple stored/table queries
	//If it's used in multiple places a function should be written outside
	//of the DB code.
	public function addNode($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			return (!empty($params['contenttypeid'])
			AND !empty($params['parentid']) AND !empty($params['title']));
		}

		$cleaned = vB::getCleaner()->cleanArray($params, array(
			'parentid' => vB_Cleaner::TYPE_UINT,
		));

		$params[vB_dB_Query::TYPE_KEY] = vB_dB_Query::QUERY_INSERT;

		//We must set the protected field.
		$parent = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "node WHERE nodeid =" . $cleaned['parentid']);
		$params['protected'] = $parent['protected'];

		$nodeid =  vB_dB_Assertor::instance()->assertQuery('vBForum:node', $params);
		$config = vB::getConfig();

		if ($nodeid)
		{
			$nodeid = $nodeid[0];
			$sql = "INSERT INTO " . TABLE_PREFIX . "closure(parent, child, depth)
				VALUES($nodeid, $nodeid, 0) \n/**" . __FUNCTION__ .
			      (defined('THIS_SCRIPT') ? '- ' . THIS_SCRIPT : '') . "**/" ;

			if (isset($config['Misc']['debug_sql']) AND $config['Misc']['debug_sql'])
			{
				echo "sql: $sql <br />\n";
			}
			$db->query_write($sql);

			$sql = "INSERT INTO " . TABLE_PREFIX . "closure(parent, child, depth)
				SELECT p.parent, $nodeid, p.depth+1
			  	FROM " . TABLE_PREFIX . "closure p
			 	WHERE p.child=". $cleaned['parentid'] . "\n/**" . __FUNCTION__ .
				(defined('THIS_SCRIPT') ? '- ' . THIS_SCRIPT : '') . "**/";

			if (isset($config['Misc']['debug_sql']) AND $config['Misc']['debug_sql'])
			{
				echo "sql: $sql <br />\n";
			}

			$db->query_write($sql);

			return $nodeid;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Deletes a node
	 *
	 * @param	array $params
	 * @param	object $db
	 * @param	bool $check_only
	 * @return	int
	 */
	function deleteNode($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			return (!empty($params['nodeid']));
		}

		$params = vB::getCleaner()->cleanArray($params, [
			'nodeid' => vB_Cleaner::TYPE_UINT,
			'delete_subnodes' => vB_Cleaner::TYPE_BOOL
		]);

		//If we have any children and delete_subnodes is not set positive, we abort.
		if (empty($params['delete_subnodes']) OR !$params['delete_subnodes'])
		{
			$children = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "closure WHERE parent = " .
			$params['nodeid'] . " AND depth > 0 LIMIT 1");
			if ($children)
			{
				throw new vB_Exception_Database('cannot_delete_with_subnodes');
			}
		}

		$sql = "DELETE node, cl2 FROM " . TABLE_PREFIX . "closure AS cl
			INNER JOIN " . TABLE_PREFIX . "node AS node on node.nodeid = cl.child
			INNER JOIN " . TABLE_PREFIX . "closure AS cl2 on node.nodeid = cl2.child
			WHERE cl.parent = " . $params['nodeid'];

		return $this->executeWriteQuery($db, $sql, __FUNCTION__);
	}

	public function deleteNodes($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			return (!empty($params['nodeids']));
		}

		$params = vB::getCleaner()->cleanArray($params, array(
			'nodeids' => vB_Cleaner::TYPE_ARRAY_UINT,
			'delete_subnodes' => vB_Cleaner::TYPE_BOOL
		));

		//If we have any children and delete_subnodes is not set positive, we abort.
		if (empty($params['delete_subnodes']) OR !$params['delete_subnodes'])
		{
			$children = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "closure WHERE parent IN (" .
			implode(',',$params['nodeids']) . ") AND depth > 0 LIMIT 1");
			if ($children)
			{
				throw new vB_Exception_Database('cannot_delete_with_subnodes');
			}
		}

		$sql = "DELETE node, cl2 FROM " . TABLE_PREFIX . "closure AS cl
			INNER JOIN " . TABLE_PREFIX . "node AS node on node.nodeid = cl.child
			INNER JOIN " . TABLE_PREFIX . "closure AS cl2 on node.nodeid = cl2.child
			WHERE cl.parent IN (" . implode(',',$params['nodeids']) . ")\n/**" . __FUNCTION__ .
		(defined('THIS_SCRIPT') ? '- ' . THIS_SCRIPT : '') . "**/";
		$config = vB::getConfig();

		if (isset($config['Misc']['debug_sql']) AND $config['Misc']['debug_sql'])
		{
			echo "sql: $sql <br />\n";
		}

		$result = $db->query_write($sql);

		return $result;
	}

	/*
	 *	Uses the table data values to clone node records substituting the newnodeid (if given)
	 *	for the nodeid field.  If the newnodeid is omitted it will exclude that field entirely
	 *	and rely on the default (presumably autoincrement value).  It is up to the caller to
	 *	ensure that this is only called that way on tables with an autoincrement field.
	 */
	public function cloneNodeRecord($params, $db, $check_only = false)
	{
		//validate params
		if ($check_only)
		{
			return (!empty($params['oldnodeid']) AND !empty($params['table']));
		}

		//clean params
		$params = vB::getCleaner()->cleanArray($params, array(
			'oldnodeid' => vB_Cleaner::TYPE_UINT,
			'newnodeid' => vB_Cleaner::TYPE_UINT,
			'table' => vB_Cleaner::TYPE_STR
		));

		$table = $db->clean_identifier($params['table']);
		$newnodeid = $params['newnodeid'];
		$oldnodeid =  $params['oldnodeid'];

		//we can assume that the ids in the $nodefields table are safe.
		$nodefields = $this->table_data[$table]['structure'];
		// Unset Nodeid
		foreach ($nodefields as $k => $v)
		{
			if ($v == 'nodeid')
			{
				unset ($nodefields[$k]);
				break;
			}
		}

		//if we have a nodeid use it
		if ($newnodeid)
		{
			$sql = "INSERT INTO " . TABLE_PREFIX . "$table (nodeid, " . implode(',', $nodefields) . ")
				SELECT $newnodeid, " . implode(',', $nodefields) . " FROM " . TABLE_PREFIX . "$table
				WHERE nodeid = " . $oldnodeid;
		}

		//otherwise just leave it off
		else
		{
			$sql = "INSERT INTO " . TABLE_PREFIX . "$table (" . implode(',', $nodefields) . ")
				SELECT " . implode(',', $nodefields) . " FROM " . TABLE_PREFIX . "$table
				WHERE nodeid = " . $oldnodeid;
		}

		$db->query_write($sql);
		return $db->insert_id();
	}

	/**
	 * Returns a Content record
	 *
	 * @param	array $params
	 * @param	object $db
	 * @param	bool $check_only
	 * @result bool | object -- bool if check_only is true, or db result set if not
	 */
	public function getFullContent($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			return !empty($params['tablename']) AND !empty($params['nodeid']);
		}

		$params = vB::getCleaner()->cleanArray($params, [
			'nodeid' => vB_Cleaner::TYPE_NOCLEAN, // Cleaned later, it may or may not be an array
			'tablename' => vB_Cleaner::TYPE_NOCLEAN, // db table name, cleaned later
			vB_dB_Query::PRIORITY_QUERY => vB_Cleaner::TYPE_BOOL,
		]);

		if (is_array($params['nodeid']))
		{
			$params['nodeid'] = vB::getCleaner()->clean($params['nodeid'], vB_Cleaner::TYPE_ARRAY_UINT);
			$ids = implode(',',$params['nodeid']);
			$idArray = $params['nodeid'];
		}
		else
		{
			$params['nodeid'] = vB::getCleaner()->clean($params['nodeid'], vB_Cleaner::TYPE_UINT);
			$ids = $params['nodeid'];
			$idArray = [$ids];
		}
		$userContext = vB::getUserContext();
		$joins = '';

		if (is_array($params['tablename']))
		{
			$params['tablename'] = vB::getCleaner()->clean($params['tablename'], vB_Cleaner::TYPE_ARRAY_STR);
			$params['tablename'] = $db->clean_identifier($params['tablename']);
			$tables = $params['tablename'];
		}
		else
		{
			$params['tablename'] = vB::getCleaner()->clean($params['tablename'], vB_Cleaner::TYPE_STR);
			$params['tablename'] = $db->clean_identifier($params['tablename']);
			$tables = [$params['tablename']];
		}

		//Let's build the fields list. We'll add all the fields of the node table.
		//For the other tables, the first field gets its own name. subsequent fields
		//with the same name will be table_field.
		$selectedFields = ['node.*' => 'node.*'];
		$nodeFields = [];
		foreach ($this->table_data['node']['structure'] AS $field)
		{
			$nodeFields[$field] = 'node.' . $field;
		}

		foreach ($tables as $table)
		{
			$joins .= "
			LEFT JOIN  " . TABLE_PREFIX . "$table AS $table
			ON $table.nodeid = node.nodeid\n";
			foreach ($this->table_data[$table]['structure'] AS $field)
			{
				//nodeid is common to all these tables
				if (array_key_exists($field, $nodeFields) OR array_key_exists($field, $selectedFields))
				{
					$selectedFields[] = $table . '.' . $field . " AS $table" . '_' . $field;
				}
				else
				{
					$selectedFields[$field] = $table. '.' . $field;
				}
			}
		}


		$sql = "SELECT " . implode(',', $selectedFields) . ", icon.iconpath, ch.routeid AS channelroute, ch.title AS channeltitle, ch.nodeid AS channelid,
		 starter.routeid AS starterroute, starter.title AS startertitle, starter.authorname as starterauthorname, starter.prefixid as starterprefixid,
		 starter.userid as starteruserid, starter.lastcontentid as starterlastcontentid, starter.totalcount+1 as startertotalcount, starter.urlident AS starterurlident,
		 deleteuser.username AS deleteusername, deleteuser.displayname AS deletedisplayname, lastauthor.username AS lastauthorname, editlog.reason AS edit_reason, editlog.userid AS edit_userid, editlog.username AS edit_username,
		 editlog.dateline AS edit_dateline, editlog.hashistory, starter.nodeoptions as starternodeoptions, ch.nodeoptions as channelnodeoptions
		 FROM " . TABLE_PREFIX . "node AS node
		 $joins
		 LEFT JOIN " . TABLE_PREFIX . "editlog AS editlog ON (editlog.nodeid = node.nodeid)
		 LEFT JOIN " . TABLE_PREFIX . "node AS starter ON (starter.nodeid = node.starter)
		 LEFT JOIN " . TABLE_PREFIX . "node AS ch ON (ch.nodeid = starter.parentid)
		 LEFT JOIN " . TABLE_PREFIX . "user AS deleteuser ON (node.deleteuserid > 0 AND node.deleteuserid = deleteuser.userid)
		 LEFT JOIN " . TABLE_PREFIX . "user AS lastauthor ON (node.lastauthorid = lastauthor.userid)
		 LEFT JOIN " . TABLE_PREFIX . "icon AS icon ON (node.iconid = icon.iconid)
		" ;

		$sql .= "
		 WHERE node.nodeid IN ($ids)\n/** getFullContent" .
			(defined('THIS_SCRIPT') ? '- ' . THIS_SCRIPT : '') .
			"**/";

		$resultclass = 'vB_dB_' . $this->db_type . '_result';
		$config = vB::getConfig();

		if (isset($config['Misc']['debug_sql']) AND $config['Misc']['debug_sql'])
		{
			echo "sql: $sql<br />\n";
		}

		if (!empty($params[vB_dB_Query::PRIORITY_QUERY]))
		{
			$result = new $resultclass($db, $sql, false);
		}
		else
		{
			$result = new $resultclass($db, $sql);
		}
		return $result;
	}

	public function getContentTablesData($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			return !empty($params['tablename']) AND !empty($params['nodeid']);
		}

		$params = vB::getCleaner()->cleanArray($params, array(
			'nodeid' => vB_Cleaner::TYPE_UINT,
			'tablename' => vB_Cleaner::TYPE_NOCLEAN, // db table name, cleaned later
			vB_dB_Query::PRIORITY_QUERY => vB_Cleaner::TYPE_BOOL,
		));

		if (is_array($params['tablename']))
		{
			$params['tablename'] = vB::getCleaner()->clean($params['tablename'], vB_Cleaner::TYPE_ARRAY_STR);
			$params['tablename'] = $db->clean_identifier($params['tablename']);
			$tables = $params['tablename'];
		}
		else
		{
			$params['tablename'] = vB::getCleaner()->clean($params['tablename'], vB_Cleaner::TYPE_STR);
			$params['tablename'] = $db->clean_identifier($params['tablename']);
			$tables = array($params['tablename']);
		}

		$queryBuilder = $this->getQueryBuilder($db);

		$from = array();
		$where = array();

		foreach ($tables AS $table)
		{
			$safe_table = $queryBuilder->escapeTable($table);

			$from[] = $safe_table;
			$where[] = $safe_table . '.nodeid = ' . $params['nodeid'];

			foreach ($this->table_data[$table]['structure'] AS $field)
			{
				//nodeid is common to all these tables
				if ($field != 'nodeid')
				{
					$selectedFields[$field] = $safe_table. '.' . $field;
				}
			}
		}

		$sql = "
			SELECT " . implode(', ', $selectedFields) . "
			FROM " . implode(', ', $from) . "
			WHERE " . implode(' AND ', $where);

		$resultclass = 'vB_dB_' . $this->db_type . '_result';
		$config = vB::getConfig();

		if (isset($config['Misc']['debug_sql']) AND $config['Misc']['debug_sql'])
		{
			echo "sql: $sql<br />\n";
		}

		if (!empty($params[vB_dB_Query::PRIORITY_QUERY]))
		{
			$result = new $resultclass($db, $sql, false);
		}
		else
		{
			$result = new $resultclass($db, $sql);
		}
		return $result;
	}

	/**
	 * Returns the popular tags
	 * pass searchStr in the parameters to narrow the tags
	 *
	 * @param array $params
	 * 	searchStr string -- return only tags that contain this string
	 * @param object $db -- the database object
	 * @param bool $check_only --whether we run the query, or just validate that we can run it.
	 *
	 * @return vB_dB_Result -- The query result
	 */
	public function getPopularTags($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			return isset($params[vB_dB_Query::PARAM_LIMIT]) AND isset($params['offset']);
		}

		$params = vB::getCleaner()->cleanArray($params, [
			'searchStr' => vB_Cleaner::TYPE_STR,
			vB_dB_Query::PARAM_LIMIT => vB_Cleaner::TYPE_UINT,
			'offset' => vB_Cleaner::TYPE_UINT
		]);

		$where = '';
		if (!empty($params['searchStr']))
		{
			$where = " WHERE tag.tagtext LIKE '" . $db->escape_string_like($params['searchStr']) . "%'";
		}

		$sql = "
			SELECT tag.tagtext, tagnode.userid, tag.tagid, count(tag.tagid) AS nr
			FROM " . TABLE_PREFIX . "tag AS tag
				JOIN " . TABLE_PREFIX. "tagnode AS tagnode ON (tag.tagid = tagnode.tagid)
			$where
			GROUP BY tag.tagid
			ORDER BY nr DESC, tag.tagtext ASC
			LIMIT " .  $params[vB_dB_Query::PARAM_LIMIT] . " OFFSET " . $params['offset'];

		return $this->getResultSet($db, $sql, __FUNCTION__);
	}

	public function isModAll($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			return !empty($params['userid']);
		}

		$params['userid'] = intval($params['userid']);
		$sql = "
			SELECT nodeid, moderatorid, permissions, permissions2
			FROM " . TABLE_PREFIX . "moderator
			WHERE userid = $params[userid]" . (!$params['issupermod'] ? ' AND nodeid != -1' : '');

		$resultclass = 'vB_dB_' . $this->db_type . '_result';
		$config = vB::getConfig();

		if (isset($config['Misc']['debug_sql']) AND $config['Misc']['debug_sql'])
		{
			echo "sql: $sql<br />\n";
		}
		$result = new $resultclass($db, $sql);
		return $result;
	}


	public function fetchTemplateIdsByParentlist($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			return isset($params['parentlist']);
		}
		else
		{
			$parents = explode(',', $params['parentlist']);
			$parents = vB::getCleaner()->clean($parents, vB_Cleaner::TYPE_ARRAY_INT);
			$i = sizeof($parents);
			$querySele = '';
			$queryJoin = '';
			foreach($parents AS $setid)
			{
				if ($setid != -1 AND $i > 1)
				{
					//this looks like it should append rather than overwrite.
					$querySele = ",\nt$i.templateid AS templateid_$i, t$i.title AS title$i, t$i.styleid AS styleid_$i $querySele";
					$queryJoin = "\nLEFT JOIN " . TABLE_PREFIX . "template AS t$i ON (t1.title=t$i.title AND t$i.styleid=$setid)$queryJoin";
					$i--;
				}
			}

			$sql = "
				SELECT t1.templateid AS templateid_1, t1.title $querySele
				FROM " . TABLE_PREFIX . "template AS t1 $queryJoin
				WHERE t1.styleid IN (-1,0)
				ORDER BY t1.title
			";

			return $this->getResultSet($db, $sql, __FUNCTION__);
		}
	}

	//this should be replaced by a stored query ... once we can figure out what's going
	//on with the parentlist array_pop.
	public function fetchCustomtempsByParentlist($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			return true;
		}
		else
		{
			$parentlist = explode(',',$params['parentlist']);
			$params['parentlist'] = vB::getCleaner()->clean($parentlist,  vB_Cleaner::TYPE_ARRAY_INT);

			if (empty($params['parentlist']))
			{
				return new vB_dB_ArrayResult($db, array());
			}

			$styleids = $params['parentlist'];

			if (count($styleids) > 2)
			{
				array_pop($styleids);
			}

			$sql = "
				SELECT t1.templateid, t1.title, INSTR('," . implode(',', $params['parentlist']) . ",', CONCAT(',', t1.styleid, ',') ) AS ordercontrol, t1.styleid
				FROM " . TABLE_PREFIX . "template AS t1
				LEFT JOIN " . TABLE_PREFIX . "template AS t2 ON (t2.title=t1.title AND t2.styleid=-1)
				WHERE t1.styleid IN (" . implode(',', $styleids) . ")
				ORDER BY title, ordercontrol
			";

			$resultclass = 'vB_dB_' . $this->db_type . '_result';
			$result = new $resultclass($db, $sql);
			return $result;
		}
	}


	public function rebuildLanguage($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			return !empty($params['phrasearray']) AND !empty($params['languageid']);
		}

		$config = vB::getConfig();
		$result = null;

		$params = vB::getCleaner()->cleanArray($params, array(
			'languageid' => vB_Cleaner::TYPE_UINT,
			'phrasearray' => vB_Cleaner::TYPE_ARRAY,
		));

		foreach($params['phrasearray'] as $fieldname => $phrases)
		{
			$result = null;

			$cachefield = $db->clean_identifier("phrasegroup_$fieldname");

			ksort($phrases);
			$phrases = preg_replace('/\{([0-9]+)\}/siU', '%\\1$s', $phrases);
			$cachetext = $db->escape_string(serialize($phrases));

			$sql = "
				UPDATE " . TABLE_PREFIX . "language
				SET $cachefield = '$cachetext'
				WHERE languageid = $params[languageid]
			";
			if (isset($config['Misc']['debug_sql']) AND $config['Misc']['debug_sql'])
			{
				echo "sql: $sql<br />\n";
			}
			$result = $db->query_write($sql);
		}

		if ($result === null) // shouldn't return null
		{
			$sql = "
				UPDATE " . TABLE_PREFIX . "language
				SET title = title
				WHERE languageid = $params[languageid]
			";
			if (isset($config['Misc']['debug_sql']) AND $config['Misc']['debug_sql'])
			{
				echo "sql: $sql<br />\n";
			}
			$result = $db->query_write($sql);
		}

		return $result;
	}


	public function fetchPhrase($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			return !empty($params['fieldname']) AND !empty($params['phrasename']);
		}

		$params = vB::getCleaner()->cleanArray($params, array(
			'fieldname' => vB_Cleaner::TYPE_STR,
			'phrasename' => vB_Cleaner::TYPE_STR,
			'alllanguages' => vB_Cleaner::TYPE_BOOL,
			'languageid' => vB_Cleaner::TYPE_UINT
		));

		//make sure we have a languageid
		if (empty($params['languageid']) AND !$params['alllanguages'])
		{
			$session = vB::getCurrentSession();

			if (empty($session))
			{
				$options = vB::getDataStore()->getValue('options');
				$languageid = $options['languageid'];
			}
			else
			{
				$languageid = $session->get('languageid');
			}
		}
		else
		{
			$languageid = $params['languageid'];
		}

		$sql = "
			SELECT text, languageid, special
			FROM " . TABLE_PREFIX . "phrase AS phrase
			LEFT JOIN " . TABLE_PREFIX . "phrasetype USING (fieldname)
			WHERE phrase.fieldname = '" . $db->escape_string($params['fieldname']) . "'
			AND varname = '" . $db->escape_string($params['phrasename']) . "' "
			. iif(!$params['alllanguages'], "AND languageid IN (-1, 0, $languageid)")
		;

		$resultclass = 'vB_dB_' . $this->db_type . '_result';
		$config = vB::getConfig();

		if (isset($config['Misc']['debug_sql']) AND $config['Misc']['debug_sql'])
		{
			echo "sql: $sql<br />\n";
		}
		$result = new $resultclass($db, $sql);
		return $result;
	}

	/**
	 * Used for getFollowing follow API method
	 */
	public function getUserFollowing($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			return (!empty($params['userid']) AND !empty($params[vB_Api_Follow::FOLLOWTYPE]));
		}
		else
		{
			$cleaner = vB::getCleaner();
			$params = $cleaner->cleanArray($params, [
				'userid'                              => vB_Cleaner::TYPE_UINT,
				'contenttypeid'                       => vB_Cleaner::TYPE_NOCLEAN, // Cleaned later, it may or may not be an array
				vB_Api_Follow::FOLLOWTYPE             => vB_Cleaner::TYPE_STR,
				vB_Api_Follow::FOLLOWFILTERTYPE_SORT  => vB_Cleaner::TYPE_ARRAY_STR,
				vB_dB_Query::PARAM_LIMIT              => vB_Cleaner::TYPE_UINT,
				vB_dB_Query::PARAM_LIMITPAGE          => vB_Cleaner::TYPE_UINT
			]);

			if (!empty($params['contenttypeid']))
			{
				if (is_array($params['contenttypeid']))
				{
					$params['contenttypeid'] = $cleaner->clean($params['contenttypeid'], vB_Cleaner::TYPE_ARRAY_UINT);
				}
				else
				{
					$params['contenttypeid'] = [$cleaner->clean($params['contenttypeid'],  vB_Cleaner::TYPE_UINT)];
				}
			}
			$contenttypeid = vB_Types::instance()->getContentTypeId('vBForum_Channel');

			$sql = [];
			if (
				$params[vB_Api_Follow::FOLLOWTYPE] == vB_Api_Follow::FOLLOWTYPE_ALL
				OR
				$params[vB_Api_Follow::FOLLOWTYPE] == vB_Api_Follow::FOLLOWTYPE_ACTIVITY
			)
			{
				// noUnsubscribe is used by template subscriptions_one to detect & disable JS hook for certain channels (Blogs by default)
				// so that users cannot "unsubscribe" from their profile/subscriptions tag
				$sql[] = "
					/* Channels */
					SELECT follow.title AS title, follow.nodeid AS keyval, 'node' AS sourcetable, IF(follow.lastcontent = 0, follow.lastupdate, follow.lastcontent) AS lastactivity,
					follow.totalcount AS activity, type.class AS type,
					(follow.nodeoptions & " . vB_Api_Node::OPTION_AUTOSUBSCRIBE_ON_JOIN . ") AS noUnsubscribe
					FROM " . TABLE_PREFIX . "node AS follow
					INNER JOIN " . TABLE_PREFIX . "contenttype AS type ON type.contenttypeid = follow.contenttypeid
					INNER JOIN " . TABLE_PREFIX . "subscribediscussion AS sd ON sd.discussionid = follow.nodeid AND sd.userid = " . $params['userid'];
			}
			else
			{
				//If FOLLOWTYPE is vB_Api_Follow::FOLLOWTYPE_ACTIVITY we'll have hit the if case and won't be here.
				//Not entirely sure what's going on with that.
				if (
					$params[vB_Api_Follow::FOLLOWTYPE] == vB_Api_Follow::FOLLOWTYPE_CHANNELS
					OR
					$params[vB_Api_Follow::FOLLOWTYPE] == vB_Api_Follow::FOLLOWTYPE_ACTIVITY
				)
				{
					$sql[] = "
						/* Channels */
						SELECT follow.title AS title, follow.nodeid AS keyval, 'node' AS sourcetable, IF(follow.lastcontent = 0, follow.lastupdate, follow.lastcontent) AS lastactivity,
						follow.totalcount AS activity, 'Channel' AS type,
						(follow.nodeoptions & " . vB_Api_Node::OPTION_AUTOSUBSCRIBE_ON_JOIN . ") AS noUnsubscribe
						FROM " . TABLE_PREFIX . "node AS follow
						INNER JOIN " . TABLE_PREFIX . "subscribediscussion AS sd ON sd.discussionid = follow.nodeid AND sd.userid = " . $params['userid'] . "
						WHERE follow.contenttypeid = " . $contenttypeid;
				}

				if ($params[vB_Api_Follow::FOLLOWTYPE] == vB_Api_Follow::FOLLOWTYPE_CONTENT)
				{
					$thisSql = "
						/* Content */
						SELECT follow.title AS title, follow.nodeid AS keyval, 'node' AS sourcetable, IF(follow.lastcontent = 0, follow.lastupdate, follow.lastcontent) AS lastactivity,
						follow.totalcount AS activity, type.class AS type,
						(follow.nodeoptions & " . vB_Api_Node::OPTION_AUTOSUBSCRIBE_ON_JOIN . ") AS noUnsubscribe
						FROM " . TABLE_PREFIX . "node AS follow
						INNER JOIN " . TABLE_PREFIX . "subscribediscussion AS sd ON sd.discussionid = follow.nodeid AND sd.userid = " . $params['userid'] . "
						INNER JOIN " . TABLE_PREFIX . "contenttype AS type ON type.contenttypeid = follow.contenttypeid \n";

					if (empty($params['contenttypeid']))
					{
						$thisSql .= "WHERE follow.contenttypeid NOT IN ($contenttypeid)\n";
					}
					else
					{
						$thisSql .= "WHERE follow.contenttypeid IN (" . implode(", ", $params['contenttypeid']) . ")\n" ;
					}
					$sql[] = $thisSql;
				}
			}

			if (
				$params[vB_Api_Follow::FOLLOWTYPE] == vB_Api_Follow::FOLLOWTYPE_USERS
				OR
				$params[vB_Api_Follow::FOLLOWTYPE] == vB_Api_Follow::FOLLOWTYPE_ALL
			)
			{
				$sql[] = "
					/* Users */
					SELECT follow.displayname AS title, follow.userid AS keyval, 'user' AS sourcetable, IFNULL(follow.lastpost, follow.joindate) AS lastactivity,
					follow.posts as activity, 'Member' AS type,
					0 AS noUnsubscribe
					FROM " . TABLE_PREFIX . "user AS follow
					INNER JOIN " . TABLE_PREFIX . "userlist AS ul ON ul.relationid = follow.userid AND ul.userid = " . $params['userid'] . "
					WHERE ul.type = 'follow' AND ul.friend = 'yes'";
			}

			//we now require the sort param to be an array.  The only caller passes it that way and we should attempt to
			//have the caller adapt to the query as much as possible to keep complicated logic out of the query methods.
			if (is_array($params[vB_Api_Follow::FOLLOWFILTERTYPE_SORT]))
			{
				$sorts = [];
				$validsorts = ['title', 'keyval', 'lastactivity', 'activity'];
				foreach ($params[vB_Api_Follow::FOLLOWFILTERTYPE_SORT] AS $key => $value)
				{
					//the first case appears to be the only one the caller actually uses
					//we may have something like 'publishdate' => 'desc'
					if (in_array($key, $validsorts))
					{
						$key = $db->escape_string($key);
						if (strtolower($value) == 'desc')
						{
							$sorts[] = "$key DESC";
						}
						else
						{
							$sorts[] = "$key ASC";
						}
					}
					else if (in_array($value, $validsorts))
					{
						$value = $db->escape_string($value);
						$sorts[] = "$value ASC";
					}
				}
			}

			if (empty($sorts))
			{
				$sort = " ORDER BY title DESC LIMIT ";
			}
			else
			{
				$sort = " ORDER BY " . implode(", ", $sorts) . " LIMIT ";
			}

			if (intval($params[vB_dB_Query::PARAM_LIMIT]))
			{
				$perpage = intval($params[vB_dB_Query::PARAM_LIMIT]);
			}
			else
			{
				$perpage = 100;
			}

			$limit = '';
			if (intval($params[vB_dB_Query::PARAM_LIMITPAGE]) > 1)
			{
				$limit .= ($perpage * (intval($params[vB_dB_Query::PARAM_LIMITPAGE]) - 1)) . ',';
			}

			if(!$sql)
			{
				throw new vB_Exception_Database('invalid_data');
			}

			$sql = implode("\n UNION  ALL \n", $sql) . " \r\n" . $sort . $limit . $perpage;
			return $this->getResultSet($db, $sql, __FUNCTION__);
		}
	}

	/**
	 * Used to get the total count for getFollowing follow API method
	 */
	public function getUserFollowingCount($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			return (!empty($params['userid']) AND !empty($params[vB_Api_Follow::FOLLOWTYPE])) ? true : false;
		}
		else
		{
			$cleaner = vB::getCleaner();
			$params = $cleaner->cleanArray($params, array(
				'userid' => vB_Cleaner::TYPE_UINT,
				'contenttypeid' => vB_Cleaner::TYPE_NOCLEAN, // It is cleaned later, it may or may not be an array
				vB_Api_Follow::FOLLOWTYPE => vB_Cleaner::TYPE_STR,
			));

			if (!empty($params['contenttypeid']))
			{
				if (is_array($params['contenttypeid']))
				{
					$params['contenttypeid'] = $cleaner->clean($params['contenttypeid'], vB_Cleaner::TYPE_ARRAY_UINT);
				}
				else
				{
					$params['contenttypeid'] = $cleaner->clean($params['contenttypeid'], vB_Cleaner::TYPE_UINT);
				}
			}
			$contenttypeid = vB_Types::instance()->getContentTypeId('vBForum_Channel');

			if ($params[vB_Api_Follow::FOLLOWTYPE] == vB_Api_Follow::FOLLOWTYPE_ALL	)
			{
				$sql[] = "/* All Content */
					SELECT follow.nodeid AS keyval
					FROM " . TABLE_PREFIX . "node AS follow
					INNER JOIN " . TABLE_PREFIX . "subscribediscussion AS sd ON sd.discussionid = follow.nodeid AND sd.userid = " . intval($params['userid']) ;
			}
			else
			{
				if (
					$params[vB_Api_Follow::FOLLOWTYPE] == vB_Api_Follow::FOLLOWTYPE_CHANNELS
					OR
					$params[vB_Api_Follow::FOLLOWTYPE] == vB_Api_Follow::FOLLOWTYPE_ACTIVITY
				)
				{
					$sql[] = "
					/* Channels */
					SELECT follow.nodeid AS keyval
					FROM " . TABLE_PREFIX . "node AS follow
					INNER JOIN " . TABLE_PREFIX . "subscribediscussion AS sd ON sd.discussionid = follow.nodeid AND sd.userid = " . $params['userid'] . "
					WHERE follow.contenttypeid = " . $contenttypeid;
				}

				if (
					$params[vB_Api_Follow::FOLLOWTYPE] == vB_Api_Follow::FOLLOWTYPE_CONTENT
					OR
					$params[vB_Api_Follow::FOLLOWTYPE] == vB_Api_Follow::FOLLOWTYPE_ACTIVITY
				)
				{
					$thisSql = "/* Content */
					SELECT follow.nodeid AS keyval
					FROM " . TABLE_PREFIX . "node AS follow
					INNER JOIN " . TABLE_PREFIX . "subscribediscussion AS sd ON sd.discussionid = follow.nodeid AND sd.userid = " . $params['userid'] . "
					INNER JOIN " . TABLE_PREFIX . "contenttype AS type ON type.contenttypeid = follow.contenttypeid \n";


					if (empty($params['contenttypeid']))
					{
						$thisSql .= "WHERE follow.contenttypeid NOT IN ($contenttypeid)\n";

					}
					else
					{
						$thisSql .= "WHERE follow.contenttypeid IN (" . implode(", ", $params['contenttypeid']) . ")\n" ;
					}
					$sql[] = $thisSql;
				}
			}

			if (
				$params[vB_Api_Follow::FOLLOWTYPE] == vB_Api_Follow::FOLLOWTYPE_USERS
				OR
				$params[vB_Api_Follow::FOLLOWTYPE] == vB_Api_Follow::FOLLOWTYPE_ALL
			)
			{
				$sql[] = "
					/* Users */
					SELECT follow.userid AS keyval
					FROM " . TABLE_PREFIX . "user AS follow
					INNER JOIN " . TABLE_PREFIX . "userlist AS ul ON ul.relationid = follow.userid AND ul.userid = " . $params['userid'] ."
					WHERE ul.type = 'follow' AND ul.friend = 'yes'";
			}

			$innersql = "(" . implode(") UNION ALL (", $sql) . ")\r\n";
			$sql = "SELECT COUNT(userfollowing.keyval) AS total
			FROM
			(" . $innersql . ") AS userfollowing";
			$resultclass = 'vB_dB_' . $this->db_type . '_result';
			$config = vB::getConfig();
			if (isset($config['Misc']['debug_sql']) AND $config['Misc']['debug_sql'])
			{
				echo "sql: $sql<br />\n";
			}
			$result = new $resultclass($db, $sql);

			return $result;
		}
	}

	/**
	 * Gets the user followers
	 */
	public function getUserFollowers($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			return true;
		}
		else
		{
			$cleaner = vB::getCleaner();
			$cleaned = $cleaner->cleanArray($params, array(
				'userid' => vB_Cleaner::TYPE_UINT,
				vB_dB_Query::PARAM_LIMITPAGE => vB_Cleaner::TYPE_UINT,
				vB_dB_Query::PARAM_LIMIT => vB_Cleaner::TYPE_UINT,
			));

			if (!empty($params['contenttypeid']))
			{
				if (is_array($params['contenttypeid']))
				{
					$cleaned['contenttypeid'] = $cleaner->clean($params['contenttypeid'], vB_Cleaner::TYPE_ARRAY_INT);
				}
				else
				{
					$cleaned['contenttypeid'] = array($cleaner->clean($params['contenttypeid'],  vB_Cleaner::TYPE_ARRAY_INT));
				}
			}
			$contenttypeid = vB_Types::instance()->getContentTypeId('vBForum_Channel');
			$select = "SELECT u.userid,
					u.options,
					u.username AS username,
					u.displayname,
					u.usergroupid AS usergroupid,
					u.displaygroupid,
					u.infractiongroupid,
					u.usertitle,
					u.customtitle,
					IFNULL(u.lastactivity, u.joindate) as lastactivity,
				IFNULL((SELECT userid FROM " . TABLE_PREFIX . "userlist AS ul2 WHERE ul2.userid = " . $cleaned['userid'] . " AND ul2.relationid = u.userid AND ul2.type = 'follow' AND ul2.friend = 'yes'), 0) as isFollowing,
				IFNULL((SELECT userid FROM " . TABLE_PREFIX . "userlist AS ul2 WHERE ul2.userid = " . $cleaned['userid'] . " AND ul2.relationid = u.userid AND ul2.type = 'follow' AND ul2.friend = 'pending'), 0) as isPending\n";
			$queryFrom = "FROM " . TABLE_PREFIX . "user AS u
				INNER JOIN " . TABLE_PREFIX . "userlist AS ul ON (u.userid = ul.userid AND ul.relationid = " . $cleaned['userid'] . ")\n
			";
			$queryWhere = "WHERE ul.type = 'follow' AND ul.friend = 'yes'\n";
			$queryExtra = "";

			if (isset($params[vB_Api_Follow::FOLLOWFILTERTYPE_SORT]) AND !empty($params[vB_Api_Follow::FOLLOWFILTERTYPE_SORT]))
			{
				switch ($params[vB_Api_Follow::FOLLOWFILTERTYPE_SORT])
				{
					case vB_Api_Follow::FOLLOWFILTER_SORTMOST:
						$queryExtra .= "ORDER BY lastactivity DESC, username ASC\n";
						break;
					case vB_Api_Follow::FOLLOWFILTER_SORTLEAST:
						$queryExtra .= "ORDER BY lastactivity ASC, username ASC\n";
						break;
					default:
						$queryExtra .= "ORDER BY username ASC\n";
						break;
				}
			}

			if (isset($cleaned[vB_dB_Query::PARAM_LIMITPAGE]) AND isset($cleaned[vB_dB_Query::PARAM_LIMIT]))
			{
				$queryExtra .= "LIMIT " . ( ($cleaned[vB_dB_Query::PARAM_LIMITPAGE] - 1) * intval($cleaned[vB_dB_Query::PARAM_LIMIT]) ) .
					 ", " . intval($cleaned[vB_dB_Query::PARAM_LIMIT]) . "\n";
			}

			$sql = $select . $queryFrom . $queryWhere . $queryExtra;
			$resultclass = 'vB_dB_' . $this->db_type . '_result';
			$config = vB::getConfig();
			if (isset($config['Misc']['debug_sql']) AND $config['Misc']['debug_sql'])
			{
				echo "sql: $sql<br />\n";
			}

			$result = new $resultclass($db, $sql);

			return $result;
		}
	}



	/**
	 * Gets the user following content for the profile page- this is the settings, not the content
	 *
	 * @param	array $params
	 * @param	object $db
	 * @param	bool $check_only
	 * @result bool | object -- bool if check_only is true, or db result set if not
	 */
	public function getFollowing($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			return true;
		}
		else
		{
			$nodeApi = vB_Api::instanceInternal('node');
			$VMChannel = $nodeApi->fetchVMChannel();
			$extraFields = '';
			$cleaner = vB::getCleaner();
			$params = $cleaner->cleanArray($params, array(
				'contenttypeid' => vB_Cleaner::TYPE_NOCLEAN, // Cleaned later, may or may not be an array
				'following' => vB_Cleaner::TYPE_STR,
				'followerid' => vB_Cleaner::TYPE_UINT,
				vB_Api_Node::FILTER_TIME => vB_Cleaner::TYPE_STR,
				vB_Api_Node::FILTER_SORT => vB_Cleaner::TYPE_NOCLEAN, // Cleaned later, may or may not be an array
				vB_dB_Query::PARAM_LIMITPAGE => vB_Cleaner::TYPE_UINT,
				vB_dB_Query::PARAM_LIMIT => vB_Cleaner::TYPE_UINT
			));

			if (!empty($params['contenttypeid']))
			{
				if (is_array($params['contenttypeid']))
				{
					$params['contenttypeid'] = $cleaner->clean($params['contenttypeid'], vB_Cleaner::TYPE_ARRAY_INT);
				}
				else
				{
					$params['contenttypeid'] = array($cleaner->clean($params['contenttypeid'], vB_Cleaner::TYPE_INT));
				}
			}

			if ($params['following'] == vB_Api_Search::FILTER_FOLLOWING_CHANNEL OR $params['following'] == vB_Api_Search::FILTER_FOLLOWING_BOTH)
			{
				$extraFields .= ', IFNULL(p_sd.discussionid, 0) AS isFollowingContent, IFNULL(sd.discussionid, 0) AS isFollowingChannel, IFNULL(ul.relationid, 0) AS isFollowingMember';
			}
			else
			{
				$extraFields .= ', IFNULL(p_sd.discussionid, 0) AS isFollowingChannel, IFNULL(sd.discussionid, 0) AS isFollowingContent';
			}
			$permflags = $this->getNodePermTerms();
			unset($permflags['joins']['starter']);

			$sql = "SELECT node.*, type.class AS contenttypeclass, postfor.username AS postfor, IFNULL(ul.relationid, 0) AS isFollowingMember $extraFields
			FROM " . TABLE_PREFIX . "node AS node
				INNER JOIN " . TABLE_PREFIX . "contenttype AS type ON type.contenttypeid = node.contenttypeid
				LEFT JOIN " . TABLE_PREFIX . "user AS postfor ON postfor.userid = node.setfor
				LEFT JOIN " . TABLE_PREFIX . "closure AS vmcheck ON vmcheck.child = node.nodeid AND vmcheck.parent = $VMChannel
				LEFT JOIN " . TABLE_PREFIX . "node AS starter ON starter.nodeid = node.starter
				LEFT JOIN " . TABLE_PREFIX . "node AS ch ON ch.nodeid = starter.parentid
				" . implode("\n", $permflags['joins']) . "\n";

			switch ($params['following'])
			{
				case vB_Api_Search::FILTER_FOLLOWING_USERS:
					$sql .= " INNER JOIN " . TABLE_PREFIX . "userlist AS ul ON node.userid = ul.relationid AND ul.type = 'follow' AND ul.friend = 'yes'
					LEFT JOIN " . TABLE_PREFIX . "subscribediscussion AS sd ON node.nodeid = sd.discussionid AND sd.userid = " . $params['followerid'] . "
					LEFT JOIN " . TABLE_PREFIX . "subscribediscussion AS p_sd ON p_sd.discussionid = ch.nodeid AND p_sd.userid = " . $params['followerid'];
					break;
				case vB_Api_Search::FILTER_FOLLOWING_CONTENT:
					$sql .= " INNER JOIN " . TABLE_PREFIX . "subscribediscussion AS sd ON sd.discussionid = node.nodeid AND sd.userid = " . $params['followerid'] . "
					LEFT JOIN " . TABLE_PREFIX . "userlist AS ul ON node.userid = ul.relationid AND ul.userid = " . $params['followerid'] . "
					LEFT JOIN " . TABLE_PREFIX . "subscribediscussion AS p_sd ON p_sd.discussionid = ch.nodeid AND p_sd.userid = " . $params['followerid'];
					break;
				case vB_Api_Search::FILTER_FOLLOWING_CHANNEL:
					$sql .= " INNER JOIN " . TABLE_PREFIX . "subscribediscussion AS sd ON sd.discussionid = ch.nodeid AND sd.userid = " . $params['followerid'] . "
					LEFT JOIN " . TABLE_PREFIX . "userlist AS ul ON node.userid = ul.relationid AND ul.userid = " . $params['followerid'] . "
					LEFT JOIN " . TABLE_PREFIX . "subscribediscussion AS p_sd ON p_sd.discussionid = node.nodeid AND p_sd.userid = " . $params['followerid'];
					break;
				case vB_Api_Search::FILTER_FOLLOWING_BOTH:
					$sql .= " LEFT JOIN " . TABLE_PREFIX . "userlist AS ul ON node.userid = ul.relationid AND ul.userid = " . $params['followerid'] . " AND ul.type = 'follow' AND ul.friend = 'yes'\n
					LEFT JOIN " . TABLE_PREFIX . "subscribediscussion AS sd ON ch.nodeid = sd.discussionid AND sd.userid = " . $params['followerid'] . "
					LEFT JOIN " . TABLE_PREFIX . "subscribediscussion AS p_sd ON p_sd.discussionid = node.nodeid AND p_sd.userid = " . $params['followerid'] . "
					LEFT JOIN " . TABLE_PREFIX . "subscribediscussion AS subscription ON node.nodeid = subscription.discussionid AND subscription.userid = " . $params['followerid'];
					break;
				default:
					// just ignore
					break;
			}

			$sql .= " WHERE node.inlist > 0 AND type.class NOT IN ('Channel', 'PrivateMessage')"
				. $permflags['where'] . "\n";

			if (!empty($params['contenttypeid']))
			{
				$sql .= "AND node.contenttypeid IN (" . implode(', ', $params['contenttypeid']) .") \n";
			}

			//block people on the global ignore list.
			$options = vB::getDatastore()->getValue('options');
			if (trim($options['globalignore']) != '')
			{
				$blocked = preg_split('#\s+#s', $options['globalignore'], -1, PREG_SPLIT_NO_EMPTY);
				//the user can always see their own posts, so if they are in the blocked list we remove them
				$bbuserkey = array_search(vB::getCurrentSession()->get('userid') , $blocked);

				if ($bbuserkey !== FALSE AND $bbuserkey !== NULL)
				{
					unset($blocked[$bbuserkey]);
				}

				//Make sure we didn't just empty the list
				if (!empty($blocked))
				{
					$sql .= " AND node.userid NOT IN (" . implode(',', $blocked) . ")";
				}
			}

			/** Follow filter */
			if (!empty($params['following']))
			{
				switch ($params['following'])
				{
					case vB_Api_Search::FILTER_FOLLOWING_USERS:
						$sql .= " AND ul.userid = " . $params['followerid'];
						break;
					case vB_Api_Search::FILTER_FOLLOWING_CONTENT:
						$sql .= " AND sd.userid = " . $params['followerid'];
						break;
					case vB_Api_Search::FILTER_FOLLOWING_CHANNEL:
						$sql .= " AND sd.userid = " . $params['followerid'];
						break;
					case vB_Api_Search::FILTER_FOLLOWING_BOTH:
						$sql .= " AND (ul.userid IS NOT NULL OR sd.discussionid IS NOT NULL OR subscription.discussionid IS NOT NULL)";
						break;
					default:
						// just ignore
						break;
				}
			}

			/** Date filter */
			if (!empty($params[vB_Api_Node::FILTER_TIME]))
			{
				$datenow = vB::getRequest()->getTimeNow();
				switch ($params[vB_Api_Node::FILTER_TIME])
				{
					case vB_Api_Search::FILTER_LASTDAY:
						$timeVal = $datenow - (24 * 60 * 60);
						break;
					case vB_Api_Search::FILTER_LASTWEEK:
						$timeVal = $datenow - (7 * 24 * 60 * 60);
						break;
					case vB_Api_Search::FILTER_LASTMONTH:
						$timeVal = strtotime(date("Y-m-d H:i:s", $datenow) . " - 1 month");
						break;
					default:
						$timeVal = 0;
						break;
				}
				$sql .= " AND node.publishdate >= $timeVal";
			}

			if (isset($params[vB_Api_Node::FILTER_SORT]))
			{
				if (is_array($params[vB_Api_Node::FILTER_SORT]))
				{
					$params[vB_Api_Node::FILTER_SORT] = vB::getCleaner()->clean($params[vB_Api_Node::FILTER_SORT], vB_Cleaner::TYPE_ARRAY_STR);
					$sorts = array();
					foreach ($params[vB_Api_Node::FILTER_SORT] as $key => $value)
					{
						//we may have something like 'publishdate' => 'desc'
						if (
							($key == 'publishdate')
							OR
							($key == 'unpublishdate')
							OR
							($key == 'authorname')
							OR
							($key == 'displayorder')
							OR
							($key == 'votes')
						)
						{
							if (strtolower($value) == 'desc')
							{
								$key = $db->escape_string($key);
								$sorts[] = "node.$key DESC";
							}
							else
							{
								$key = $db->escape_string($key);
								$sorts[] = "node.$key ASC";
							}
						}
						else if (
							($value == 'publishdate')
							OR
							($value == 'unpublishdate')
							OR
							($value == 'authorname')
							OR
							($value == 'displayorder')
						)
						{
							$value = $db->escape_string($value);
							$sorts[] = "node.$value ASC";
						}
						else if (
							is_array($value)
							AND
							isset($value['sortby'])
							AND
							(
								($value['sortby'] == 'publishdate')
								OR
								($value['sortby'] == 'unpublishdate')
								OR
								($value['sortby'] == 'authorname')
								OR
								($value['sortby'] == 'displayorder')
							)
						)
						{
							if (
								isset($value['direction'])
								AND
								(strtolower($value['direction']) == 'desc')
							)
							{
								$value['sortby'] = $db->escape_string($value['sortby']);
								$sorts[] = 'node.' . $value['sortby'] . " DESC";
							}
							else
							{
								$value['sortby'] = $db->escape_string($value['sortby']);
								$sorts[] = 'node.' . $value['sortby'] . " ASC";
							}

						}

						if (!empty($sorts))
						{
							$sort = implode(', ', $sorts);
						}
					}
				}
				else if (
					($params[vB_Api_Node::FILTER_SORT] == 'publishdate')
					OR
					($params[vB_Api_Node::FILTER_SORT] == 'unpublishdate')
					OR
					($params[vB_Api_Node::FILTER_SORT] == 'authorname')
					OR
					($params[vB_Api_Node::FILTER_SORT] == 'displayorder')
				)
				{
					$params[vB_Api_Node::FILTER_SORT] = vB::getCleaner()->clean($params[vB_Api_Node::FILTER_SORT], vB_Cleaner::TYPE_STR);
					$sort = 'node.' . $params[vB_Api_Node::FILTER_SORT] . ' ASC';
				}
			}

			if (empty($sort))
			{
				$sql .= " ORDER BY node.publishdate DESC LIMIT ";
			}
			else
			{
				$sql .= " ORDER BY $sort LIMIT ";
			}

			if (isset($params[vB_dB_Query::PARAM_LIMIT]) AND intval($params[vB_dB_Query::PARAM_LIMIT]))
			{
				$perpage = intval($params[vB_dB_Query::PARAM_LIMIT]);
			}
			else if (isset($params[vB_dB_Query::PARAM_LIMITPAGE]))
			{
				$perpage = 20;
			}
			else
			{
				$perpage = 500;
			}

			if (isset($params[vB_dB_Query::PARAM_LIMITPAGE]) AND (intval($params[vB_dB_Query::PARAM_LIMITPAGE]) > 1))
			{
				$sql .=  ($perpage * (intval($params[vB_dB_Query::PARAM_LIMITPAGE]) - 1)) . ',';
			}
			$sql .= $perpage . "\n/**" . __FUNCTION__ . (defined('THIS_SCRIPT') ? '- ' . THIS_SCRIPT : '') . "**/";

			$resultclass = 'vB_dB_' . $this->db_type . '_result';
			$config = vB::getConfig();

			if (isset($config['Misc']['debug_sql']) AND $config['Misc']['debug_sql'])
			{
				echo "sql: $sql<br />\n";
			}

			$result = new $resultclass($db, $sql);
			return $result;
		}
	}

	/**
	 * Gets the content for the profile page based on the user's following settings.
	 *
	 *	@param	array $params
	 *	@param	mixed a db pointer
	 *	@param	bool $check_only
	 *
	 *	@result	mixed
	 */
	public function getFollowingContent($params, $db, $check_only = false)
	{
		$validTypes = [
			vB_Api_Follow::FOLLOWTYPE_USERS,
			vB_Api_Follow::FOLLOWTYPE_CONTENT,
			vB_Api_Follow::FOLLOWTYPE_CHANNELS,
			vB_Api_Follow::FOLLOWTYPE_ALL,
			vB_Api_Follow::FOLLOWTYPE_ACTIVITY
		];

		if ($check_only)
		{
			$parentCheck = (isset($params['parentid']) AND !is_numeric($params['parentid'])) ? false : true;
			return (
				!empty($params['followerid']) AND (intval($params['followerid']) > 0) AND
				!empty($params[vB_Api_Follow::FOLLOWTYPE]) AND in_array($params[vB_Api_Follow::FOLLOWTYPE], $validTypes) AND
				$parentCheck
			);
		}
		else
		{
			$cleaner = vB::getCleaner();
			$params = $cleaner->cleanArray($params, [
				'contenttypeid' => vB_Cleaner::TYPE_NOCLEAN, // Cleaned later, it may or may not be an array
				vB_Api_Follow::FOLLOWFILTERTYPE_SORT => vB_Cleaner::TYPE_NOCLEAN, // Cleaned later, it may or may not be an array
				vB_Api_Follow::FOLLOWTYPE => vB_Cleaner::TYPE_STR,
				vB_Api_Follow::FOLLOWFILTERTYPE_TIME => vB_Cleaner::TYPE_STR,
				'followerid' => vB_Cleaner::TYPE_UINT,
				'parentid' => vB_Cleaner::TYPE_UINT,
				vB_dB_Query::PARAM_LIMITPAGE => vB_Cleaner::TYPE_UINT,
				vB_dB_Query::PARAM_LIMIT => vB_Cleaner::TYPE_UINT,
				'pageseemore' => vB_Cleaner::TYPE_UINT,
				// cleaned later, may be an integer with special meaning or string prefix.
				'filter_prefix' => vB_Cleaner::TYPE_NOCLEAN,
			]);

			if (!empty($params['contenttypeid']))
			{
				if (is_array($params['contenttypeid']))
				{
					$params['contenttypeid'] = $cleaner->clean($params['contenttypeid'], vB_Cleaner::TYPE_ARRAY_UINT);
				}
				else
				{
					$params['contenttypeid'] = [$cleaner->clean($params['contenttypeid'],  vB_Cleaner::TYPE_UINT)];
				}
			}

			//there is *no way* we should be accessing this much of the system from a method query.
			$nodeApi = vB_Api::instanceInternal('node');
			$VMChannel = $nodeApi->fetchVMChannel();
			$channelType = vB_Types::instance()->getContentTypeid('vBForum_Channel');
			$PMType = vB_Types::instance()->getContentTypeid('vBForum_PrivateMessage');
			$permflags = $this->getNodePermTerms();

			$wheresql = [];
			$sorts = [];
			$sortfields = [];
			$outersorts = [];
			if (isset($params[vB_Api_Follow::FOLLOWFILTERTYPE_SORT]))
			{
				$validSorts = [
					'publishdate',
					'unpublishdate',
					'lastcontent',
					'authorname',
					'displayorder',
					'title',
				];

				//it's not clear *why* we have so may options for the sort parameter here.  Especially since the query is only called
				//from one place which always appears to set the sort accoring to the $field => $direction style.
				//We should probably settle on something simpler and expect the caller to conform -- method queries aren't intended to
				//be API functions where we need to be lenient in terms of what we accept and make work.
				if (is_array($params[vB_Api_Follow::FOLLOWFILTERTYPE_SORT]))
				{
					$params[vB_Api_Follow::FOLLOWFILTERTYPE_SORT] = $cleaner->clean($params[vB_Api_Follow::FOLLOWFILTERTYPE_SORT], vB_Cleaner::TYPE_ARRAY_STR);
					foreach ($params[vB_Api_Follow::FOLLOWFILTERTYPE_SORT] AS $key => $value)
					{
						//we may have something like 'publishdate' => 'desc'
						//it's not clear why we allow votes in this case and not in the others.  I'm 99% certain it is a mistake, but
						//I really don't want to change it withough verifying that and that's a deeper code dive than there is
						//time for at the moment.  This appears to be the branch that's actually used by the live code.
						if (in_array($key, $validSorts) OR ($key == 'votes'))
						{
							$field = $key;
							$direction = (strtolower($value) == 'desc' ? 'DESC' : 'ASC');
						}
						else if (in_array($value, $validSorts))
						{
							$field = $value;
							$direction = 'ASC';
						}
						else if (is_array($value)	AND in_array($value['sortby'], $validSorts))
						{
							$field = $value['sortby'];
							$direction = ((isset($value['direction']) AND (strtolower($value['direction']) == 'desc')) ? 'DESC' : 'ASC');
						}
						else
						{
							//if we didn't set the field/direction here then ignore this entry it's not valid.
							continue;
						}

						$sorts[] = "$field $direction";
						$sortfields[] = "node.$field AS sort_$field";
						$outersorts[] = "followingContent.sort_$field $direction";
					}
				}
				else if (in_array($params[vB_Api_Follow::FOLLOWFILTERTYPE_SORT], $validSorts))
				{
					$field = $params[vB_Api_Follow::FOLLOWFILTERTYPE_SORT];
					$direction = 'ASC';

					$sorts[] = "$field $direction";
					$sortfields[] = "node.$field AS sort_$field";
					$outersorts[] = "followingContent.sort_$field $direction";
				}
			}

			if (empty($sorts))
			{
				$sortfields[] = "node.publishdate AS sort_publishdate";
				$outersorts[] = "followingContent.sort_publishdate DESC";
			}

			// prefix filter. Based on activity controller & search API
			// We need this before the sql[] generation below in case we need to do our own starter join.
			if (isset($params['filter_prefix']) AND $params['filter_prefix'] !== '')
			{
				// Prefices are only on the starter, so we need to guarantee a starter join for this.
				if (empty($permflags['joins']['starter']))
				{
					$permflags['joins']['starter'] = " LEFT JOIN " .
						TABLE_PREFIX . "node AS starter ON starter.nodeid = node.starter";
				}

				$prefix = $params['filter_prefix'];
				if ($prefix == '-1')
				{
					// "no_prefix"
					$wheresql[] = "starter.prefixid = '' ";
				}
				else if ($prefix == '-2')
				{
					// "has_prefix" aka "any prefix"
					$wheresql[] = "starter.prefixid <> '' ";
				}
				else
				{
					// Specific prefix.
					$wheresql[] = "starter.prefixid = '" . $db->escape_string($prefix) .  "' ";
				}
				unset ($prefix);
			}
			unset($params['filter_prefix']);

			if (
				$params[vB_Api_Follow::FOLLOWTYPE] == vB_Api_Follow::FOLLOWTYPE_CHANNELS OR
				$params[vB_Api_Follow::FOLLOWTYPE] == vB_Api_Follow::FOLLOWTYPE_ACTIVITY OR
				$params[vB_Api_Follow::FOLLOWTYPE] == vB_Api_Follow::FOLLOWTYPE_ALL
			)
			{
				$sql[] = "
					/* Following Channel */
					SELECT node.nodeid, node.contenttypeid, node.lastcontentid, latest.contenttypeid AS lastcontenttypeid,
						postfor.username AS postfor, 0 AS isFollowingContent, sd.discussionid AS isFollowingChannel,
						0 AS isFollowingMember,
						" . implode(",", $sortfields) . "
					FROM " . TABLE_PREFIX . "node AS node
					" . ( !empty($params['parentid']) ?
					"INNER JOIN " . TABLE_PREFIX . "closure AS cl ON (cl.child = node.nodeid)" : "") . "
					LEFT JOIN " . TABLE_PREFIX . "node AS latest ON (latest.nodeid = node.lastcontentid)
					LEFT JOIN " . TABLE_PREFIX . "user AS postfor ON (postfor.userid = node.setfor)
					INNER JOIN " . TABLE_PREFIX . "subscribediscussion AS sd ON (sd.discussionid = node.parentid
						AND sd.userid = " . $params['followerid'] . "  AND node.nodeid = node.starter)
					" . (implode("\n", $permflags['joins']));
			}

			if (
				$params[vB_Api_Follow::FOLLOWTYPE] == vB_Api_Follow::FOLLOWTYPE_CONTENT OR
				$params[vB_Api_Follow::FOLLOWTYPE] == vB_Api_Follow::FOLLOWTYPE_ACTIVITY OR
				$params[vB_Api_Follow::FOLLOWTYPE] == vB_Api_Follow::FOLLOWTYPE_ALL
			)
			{
				$sql[] = "
					/* Following Content */
					SELECT node.nodeid, node.contenttypeid, node.lastcontentid, latest.contenttypeid AS lastcontenttypeid,
						postfor.username AS postfor, sd.discussionid AS isFollowingContent, 0 AS isFollowingChannel,
						0 AS isFollowingMember,
						" . implode(",", $sortfields) . "
					FROM " . TABLE_PREFIX . "node AS node
					" . ( !empty($params['parentid']) ?
					"INNER JOIN " . TABLE_PREFIX . "closure AS cl ON (cl.child = node.nodeid)" : "") . "
					LEFT JOIN " . TABLE_PREFIX . "node AS latest ON (latest.nodeid = node.lastcontentid)
					LEFT JOIN " . TABLE_PREFIX . "user AS postfor ON (postfor.userid = node.setfor)
					INNER JOIN " . TABLE_PREFIX . "subscribediscussion AS sd ON (sd.discussionid = node.nodeid
						AND sd.userid = " . $params['followerid'] . " AND node.nodeid = node.starter)
					" . (implode("\n", $permflags['joins']));
			}

			if (
				$params[vB_Api_Follow::FOLLOWTYPE] == vB_Api_Follow::FOLLOWTYPE_USERS OR
				$params[vB_Api_Follow::FOLLOWTYPE] == vB_Api_Follow::FOLLOWTYPE_ALL
			)
			{
				$sql[] = "
					/* Following Users */
					SELECT node.nodeid, node.contenttypeid, node.lastcontentid, latest.contenttypeid AS lastcontenttypeid,
						postfor.username AS postfor, 0 AS isFollowingContent, 0 AS isFollowingChannel,
						ul.relationid AS isFollowingMember,
						" . implode(",", $sortfields) . "
					FROM " . TABLE_PREFIX . "node AS node
					" . ( !empty($params['parentid']) ?
					"INNER JOIN " . TABLE_PREFIX . "closure AS cl ON (cl.child = node.nodeid)" : "") . "
					LEFT JOIN " . TABLE_PREFIX . "node AS latest ON (latest.nodeid = node.lastcontentid)
					LEFT JOIN " . TABLE_PREFIX . "user AS postfor ON (postfor.userid = node.setfor)
					INNER JOIN " . TABLE_PREFIX . "userlist AS ul ON (node.userid = ul.relationid
						AND ul.userid = " . $params['followerid'] . " AND ul.type = 'follow' AND ul.friend = 'yes'
						AND node.nodeid = node.starter)
					" . (implode("\n", $permflags['joins']));
			}

			if (
				$params[vB_Api_Follow::FOLLOWTYPE] == vB_Api_Follow::FOLLOWTYPE_CHANNELS OR
				$params[vB_Api_Follow::FOLLOWTYPE] == vB_Api_Follow::FOLLOWTYPE_ACTIVITY OR
				$params[vB_Api_Follow::FOLLOWTYPE] == vB_Api_Follow::FOLLOWTYPE_ALL
			)
			{
				$blogChannel = vB_Api::instanceInternal('blog')->getBlogChannel();
				$sql[] = "
					/* Following Blog Channel */
					SELECT node.nodeid, node.contenttypeid, node.lastcontentid, latest.contenttypeid AS lastcontenttypeid,
						postfor.username AS postfor, 0 AS isFollowingContent, git.nodeid AS isFollowingChannel,
						0 AS isFollowingMember,
						" . implode(",", $sortfields) . "
					FROM " . TABLE_PREFIX . "node AS node
					" . (!empty($params['parentid']) ?
					"INNER JOIN " . TABLE_PREFIX . "closure AS cl ON (cl.child = node.nodeid)" : "") . "
					LEFT JOIN " . TABLE_PREFIX . "node AS latest ON (latest.nodeid = node.lastcontentid)
					LEFT JOIN " . TABLE_PREFIX . "user AS postfor ON (postfor.userid = node.setfor)
					INNER JOIN " . TABLE_PREFIX . "groupintopic AS git ON (git.nodeid = node.parentid
						AND git.userid = " . $params['followerid'] . "  AND node.nodeid = node.starter)
					INNER JOIN " . TABLE_PREFIX . "closure AS blog_check ON (blog_check.child = git.nodeid AND blog_check.parent = $blogChannel)
					" . (implode("\n", $permflags['joins']));
			}

			$sortprefix = count($sql) == 1 ? 'node.' : 'sort_';

			if (empty($sorts))
			{
				$sort = " ORDER BY {$sortprefix}publishdate DESC LIMIT ";
			}
			else
			{
				array_walk($sorts, function(&$value, $key) use ($sortprefix)
				{
					$value = $sortprefix . $value;
				});
				$sort = " ORDER BY " . implode(", ", $sorts) . " LIMIT ";
			}

			$wheresql[] = "node.inlist > 0 AND node.ContentTypeid NOT IN ($channelType, $PMType)";
			if (!empty($params['parentid']))
			{
				$wheresql[] = "cl.parent = " . $params['parentid'];
			}

			if (!empty($params['contenttypeid']))
			{
				$wheresql[] = "node.contenttypeid IN (" . implode(', ', $params['contenttypeid']) .") \n";
			}

			//block people on the global ignore list.
			$options = vB::getDatastore()->getValue('options');
			if (trim($options['globalignore']) != '')
			{
				$blocked = preg_split('#\s+#s', $options['globalignore'], -1, PREG_SPLIT_NO_EMPTY);
				//the user can always see their own posts, so if they are in the blocked list we remove them
				$bbuserkey = array_search(vB::getCurrentSession()->get('userid') , $blocked);

				if ($bbuserkey !== false AND $bbuserkey !== null)
				{
					unset($blocked[$bbuserkey]);
				}

				//Make sure we didn't just empty the list
				if (!empty($blocked))
				{
					$wheresql[] = "node.userid NOT IN (" . implode(',', $blocked) . ")";
				}
			}

			/** Date filter */
			//we should *not* be doing this calculation here. The calling coud should handle the offset and pass it.
			if (!empty($params[vB_Api_Follow::FOLLOWFILTERTYPE_TIME]))
			{
				$datenow = vB::getRequest()->getTimeNow();
				switch ($params[vB_Api_Follow::FOLLOWFILTERTYPE_TIME])
				{
					case vB_Api_Follow::FOLLOWFILTER_LASTDAY:
						$timeVal = $datenow - (24 * 60 * 60);
						break;
					case vB_Api_Follow::FOLLOWFILTER_LASTWEEK:
						$timeVal = $datenow - (7 * 24 * 60 * 60);
						break;
					case vB_Api_Follow::FOLLOWFILTER_LASTMONTH:
						$timeVal = strtotime(date("Y-m-d H:i:s", $datenow) . " - 1 month");
						break;
					default:
						if (is_numeric($params[vB_Api_Follow::FOLLOWFILTERTYPE_TIME]))
						{
							$timeVal = $params[vB_Api_Follow::FOLLOWFILTERTYPE_TIME];
						}
						else
						{
							$timeVal = 0;
						}
						break;
				}
				$wheresql[] = "node.publishdate >= $timeVal";
			}

			$wheresql = implode(" AND ", $wheresql);

			if (!empty($permflags['where']))
			{
				$wheresql .= $permflags['where'];
			}

			$limit = "";
			if (isset($params[vB_dB_Query::PARAM_LIMIT]) AND intval($params[vB_dB_Query::PARAM_LIMIT]))
			{
				$perpage = intval($params[vB_dB_Query::PARAM_LIMIT]);
			}
			else
			{
				$perpage = 30;
			}

			if (!empty($params['pageseemore']) )
			{
				// needed for seemore button
				$limit .= ((($perpage - 1) * (intval($params['pageseemore']) - 1)) . ',');
			}
			else if (isset($params[vB_dB_Query::PARAM_LIMITPAGE]) AND intval($params[vB_dB_Query::PARAM_LIMITPAGE]) AND (intval($params[vB_dB_Query::PARAM_LIMITPAGE]) > 1))
			{
				$limit .= ($perpage * (intval($params[vB_dB_Query::PARAM_LIMITPAGE]) - 1)) . ',';
			}

			if ($wheresql)
			{
				foreach ($sql AS $key => $statement)
				{
					$sql[$key] .= " WHERE $wheresql";
				}
			}

			$innersql = "(" . implode(")UNION(", $sql) . ")\r\n";
			$sql = "
				SELECT followingContent.nodeid, followingContent.contenttypeid, followingContent.lastcontentid,
					followingContent.lastcontenttypeid, followingContent.postfor,
					SUM(CASE WHEN followingContent.isFollowingContent <> 0 THEN  followingContent.isFollowingContent ELSE 0 END) AS isFollowingContent,
					SUM(CASE WHEN followingContent.isFollowingChannel <> 0 THEN  followingContent.isFollowingChannel ELSE 0 END) AS isFollowingChannel,
					SUM(CASE WHEN followingContent.isFollowingMember <> 0 THEN  followingContent.isFollowingMember ELSE 0 END) AS isFollowingMember
				FROM
					(" . $innersql . ") AS followingContent
				GROUP BY followingContent.nodeid\r\n
				ORDER BY " . implode(", ", $outersorts) . "\r\n
				LIMIT " . $limit . $perpage;

			return $this->getResultSet($db, $sql, __FUNCTION__);
		}
	}

	/**
	 * Gets plain nodes
	 *
	 * @param	array $params
	 * @param	object $db
	 * @param	bool $check_only
	 * @result bool | object -- bool if check_only is true, or db result set if not
	 */
	public function getNodesToIndex($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			return isset($params['nodeid']);
		}

		$params = vB::getCleaner()->cleanArray($params, array(
			'contenttypeids' => vB_Cleaner::TYPE_ARRAY_UINT,
			'excludecontenttypeids' => vB_Cleaner::TYPE_ARRAY_UINT,
			'channelid' => vB_Cleaner::TYPE_UINT,
			'nodeid' => vB_Cleaner::TYPE_UINT,
			vB_dB_Query::PARAM_LIMIT => vB_Cleaner::TYPE_UINT,
		));

		$sql = "SELECT * " . $this->getNodesToIndexBaseQuery($params);
		$sql .= "\nORDER BY nodeid";

		if (!empty($params[vB_dB_Query::PARAM_LIMIT]))
		{
			$sql .= "\nLIMIT " . $params[vB_dB_Query::PARAM_LIMIT];
		}

		return $this->getResultSet($db, $sql, __FUNCTION__);
	}

	public function getNodesToIndexCount($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			return isset($params['nodeid']);
		}

		$params = vB::getCleaner()->cleanArray($params, array(
			'contenttypeids' => vB_Cleaner::TYPE_ARRAY_UINT,
			'excludecontenttypeids' => vB_Cleaner::TYPE_ARRAY_UINT,
			'channelid' => vB_Cleaner::TYPE_UINT,
			'nodeid' => vB_Cleaner::TYPE_UINT,
		));

		$sql = "SELECT COUNT(*) AS count" . $this->getNodesToIndexBaseQuery($params);
		return $this->getResultSet($db, $sql, __FUNCTION__);
	}

	private function getNodesToIndexBaseQuery($params)
	{
		$where = array();
		$joins = array();

		if(!empty($params['contenttypeids']))
		{
			$where[] = 'contenttypeids IN (' . implode(',', $params['contenttypeids']) . ')';
		}

		if(!empty($params['excludecontenttypeids']))
		{
			$where[] = 'contenttypeid NOT IN (' . implode(',', $params['excludecontenttypeids']) . ')';
		}

		if(!empty($params['channelid']))
		{
			//the child filter is redundant with the one on the nodeid.  However, the optimizer might not
			//be smart enough to figure that out and I want to give it the flexibily to use the closure
			//table or the node table as the driving table depending which would be better.
			$joins[] = 'JOIN ' . TABLE_PREFIX . 'closure AS closure ON node.nodeid = closure.child';
			$where[] = 'closure.parent = ' . $params['channelid'];
			$where[] = 'closure.child > ' . $params['nodeid'];
		}

		$where[] = 'node.nodeid > ' . $params['nodeid'];

		$sql = "\nFROM " . TABLE_PREFIX . "node AS node";

		if($joins)
		{
			$sql .= "\n" . implode("\n", $joins);
		}

		if ($where)
		{
			$sql .= "\nWHERE " . implode(' AND ', $where);
		}

		return $sql;
	}

	/**
	 * Gets adminhelp items with specific existing actions and options.
	 *
	 * @param	array $params
	 * @param	object $db
	 * @param	bool $check_only
	 * @result bool | object -- bool if check_only is true, or db result set if not
	 */
	public function getHelpLength($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			return (!empty($params['pagename']));
		}
		else
		{
			$params = vB::getCleaner()->cleanArray($params, array(
				'pagename' => vB_Cleaner::TYPE_STR,
				'action' => vB_Cleaner::TYPE_STR,
				'option' => vB_Cleaner::TYPE_STR,
			));

			$sql = "
				SELECT *, LENGTH(action) AS length
				FROM " . TABLE_PREFIX  . "adminhelp
				WHERE script = '" . $db->escape_string($params['pagename']) . "'
					AND (action = '' OR FIND_IN_SET('" . $db->escape_string($params['action']) . "', action))";

			if (!empty($params['option']))
			{
				$sql .= " AND optionname = '" . $db->escape_string($params['option']) . "'";
			}
			$sql .= " AND displayorder <> 0 ORDER BY displayorder";

			$resultclass = 'vB_dB_' . $this->db_type . '_result';
			$config = vB::getConfig();

			if (isset($config['Misc']['debug_sql']) AND $config['Misc']['debug_sql'])
			{
				echo "sql: $sql<br />\n";
			}

			$result = new $resultclass($db, $sql);
			return $result;
		}
	}

	/**
	 * fetchImagesSortedLimited
	 *
	 * @param	array $params
	 * @param	object $db
	 * @param	bool $check_only
	 * @result bool | object -- bool if check_only is true, or db result set if not
	 */
	public function fetchImagesSortedLimited($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			return ( !empty($params['table']) ) ? true : false;
		}
		else
		{
			$params = vB::getCleaner()->cleanArray($params, array(
				'table' => vB_Cleaner::TYPE_NOCLEAN, // Cleaned immediately after this call
				'categoryinfo' => vB_Cleaner::TYPE_ARRAY_UINT,
				'imagecategoryid' => vB_Cleaner::TYPE_UINT,
				vB_dB_Query::PARAM_LIMITSTART => vB_Cleaner::TYPE_UINT,
				vB_dB_Query::PARAM_LIMIT => vB_Cleaner::TYPE_UINT,
			));
			$params['table'] = $db->clean_identifier($params['table']);

			$sql = "SELECT";
			if ($params['categoryinfo'])
			{
				$sql .= " * FROM " . TABLE_PREFIX . $params['table'] .
					" WHERE imagecategoryid = " . $params['categoryinfo']['imagecategoryid'];
				$sql .= " ORDER BY";
				if ($params['table'] == 'avatar')
				{
					$sql .= " minimumposts,";
				}
				$sql .= " displayorder";
			}
			else
			{
				$sql .= " " . $params['table'] . ".*, imagecategory.title AS category";
				$sql .= " FROM " . TABLE_PREFIX .  $params['table'] . " AS " .  $params['table'];
				$sql .= " LEFT JOIN " . TABLE_PREFIX . "imagecategory AS imagecategory USING(imagecategoryid)";
				if ($params['imagecategoryid'])
				{
					$sql .= " WHERE " . $params['table'] . ".imagecategoryid = " . $params['imagecategoryid'];
				}
				$sql .=  " ORDER BY";
				if ($params['table'] == 'avatar')
				{
				    $sql .= ' minimumposts,';
				}
				$sql .= " imagecategory.displayorder, imagecategory.title, " . $params['table'] . ".displayorder";
			}

			if ($params[vB_dB_Query::PARAM_LIMITSTART] > 0 OR $params[vB_dB_Query::PARAM_LIMIT] > 0)
			{
				$sql .= " LIMIT " . $params[vB_dB_Query::PARAM_LIMITSTART] . ", " . $params[vB_dB_Query::PARAM_LIMIT];
			}

			$resultclass = 'vB_dB_' . $this->db_type . '_result';
			$config = vB::getConfig();

			if (isset($config['Misc']['debug_sql']) AND $config['Misc']['debug_sql'])
			{
				echo "sql: $sql<br />\n";
			}

			$result = new $resultclass($db, $sql);
			return $result;
		}
	}


	/**
	 * fetchCategoryImages
	 *
	 * @param	array $params
	 * @param	object $db
	 * @param	bool $check_only
	 * @result bool | object -- bool if check_only is true, or db result set if not
	 */
	public function fetchCategoryImages($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			return ( !empty($params['table']) AND !empty($params['itemid']) AND !empty($params['catid']) ) ? true : false;
		}
		else
		{
			$params = vB::getCleaner()->cleanArray($params, array(
				'table' => vB_Cleaner::TYPE_NOCLEAN, // Cleaned immediately after this call
				'catid' => vB_Cleaner::TYPE_UINT,
				'itemid' => vB_Cleaner::TYPE_STR,
			));
			$params['table'] = $db->clean_identifier($params['table']);
			$params['itemid'] = $db->clean_identifier($params['itemid']);

			$sql = "SELECT imagecategory.*, COUNT(" . $params['table'] .  "." . $params['itemid'] . ") AS items
			FROM " . TABLE_PREFIX . "imagecategory AS imagecategory
			LEFT JOIN " . TABLE_PREFIX . $params['table'] . " AS " . $params['table'] . " USING(imagecategoryid)
			WHERE imagetype = " . $params['catid'] . "
			GROUP BY imagecategoryid
			ORDER BY displayorder";

			$resultclass = 'vB_dB_' . $this->db_type . '_result';
			$config = vB::getConfig();

			if (isset($config['Misc']['debug_sql']) AND $config['Misc']['debug_sql'])
			{
				echo "sql: $sql<br />\n";
			}

			$result = new $resultclass($db, $sql);
			return $result;
		}
	}

	/**
	 * Optimize tables for Avatar Script
	 *
	 * @param	array $params
	 * @param	object $db
	 * @param	bool $check_only
	 * @result bool | object -- bool if check_only is true, or db result set if not
	 */
	public function optimizePictureTables($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			return true;
		}
		else
		{
			$db->hide_errors();
			$db->query_write("OPTIMIZE TABLE " . TABLE_PREFIX . "customavatar");
			$db->show_errors();
			return true;
		}
	}

	public function replaceIntoTagContent($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			return is_array($params['values']);
		}
		else
		{
			$recordLines = array();
			foreach ($params['values'] AS $record)
			{
				//all of the values should be integers, so we can just use array_map.
				$safeRecord = array_map('intval', $record);
				$recordLines[] = "($safeRecord[tagid], $safeRecord[nodeid], $safeRecord[userid], $safeRecord[dateline])";
			}

			$sql =
				'REPLACE INTO ' . TABLE_PREFIX . 'tagnode (tagid, nodeid, userid, dateline) ' .
				'VALUES ' . implode(', ', $recordLines);

			return $this->executeWriteQuery($db, $sql, __FUNCTION__);
		}
	}

	/**
	 * Fetch settings by group
	 *
	 * @param	array $params
	 * @param	object $db
	 * @param	bool $check_only
	 * @result bool | object -- bool if check_only is true, or db result set if not
	 */
	public function fetchSettingsByGroup($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			return true;
		}
		else
		{
			$params = vB::getCleaner()->cleanArray($params, [
				'debug' => vB_Cleaner::TYPE_BOOL,
			]);

			$sql = "
				SELECT setting.*, settinggroup.grouptitle, settinggroup.adminperm AS groupperm
				FROM " . TABLE_PREFIX . "settinggroup AS settinggroup
				LEFT JOIN " . TABLE_PREFIX . "setting AS setting USING(grouptitle)
			";

			if (!$params['debug'])
			{
				$sql .= "
					WHERE settinggroup.displayorder <> 0
				";
			}
			$sql .= "ORDER BY settinggroup.displayorder, setting.displayorder";

			return $this->getResultSet($db, $sql, __FUNCTION__);
		}
	}

	/**
	 * Delete settings by product
	 *
	 * @param	array $params
	 * @param	object $db
	 * @param	bool $check_only
	 * @result bool | object -- bool if check_only is true, or db result set if not
	 */
	public function deleteSettingGroupByProduct($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			return true;
		}
		else
		{
			$params = vB::getCleaner()->cleanArray($params, array(
				'product' => vB_Cleaner::TYPE_STR,
			));

			$sql = "DELETE FROM " . TABLE_PREFIX . "settinggroup WHERE volatile = 1 AND (product = '" .  $db->escape_string($params['product']) . "'";
			if ($params['product'] == 'vbulletin')
			{
				$sql .= " OR product = ''";
			}
			$sql .= ")";

			$config = vB::getConfig();

			if (isset($config['Misc']['debug_sql']) AND $config['Misc']['debug_sql'])
			{
				echo "sql: $sql<br />\n";
			}
			return $db->query_write($sql);
		}
	}

	/**
	 * Delete settings by product
	 *
	 * @param	array $params
	 * @param	object $db
	 * @param	bool $check_only
	 * @result bool | object -- bool if check_only is true, or db result set if not
	 */
	public function deleteSettingByProduct($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			return true;
		}
		else
		{
			$params = vB::getCleaner()->cleanArray($params, array(
				'product' => vB_Cleaner::TYPE_STR,
			));

			$sql = "DELETE FROM " . TABLE_PREFIX . "setting WHERE volatile = 1 AND (product = '" .  $db->escape_string($params['product']) . "'";
			if ($params['product'] == 'vbulletin')
			{
				$sql .= " OR product = ''";
			}
			$sql .= ")";

			$resultclass = 'vB_dB_' . $this->db_type . '_result';
			$config = vB::getConfig();

			if (isset($config['Misc']['debug_sql']) AND $config['Misc']['debug_sql'])
			{
				echo "sql: $sql<br />\n";
			}

			return $db->query_write($sql);
		}
	}

	public function searchAttach($params, vB_Database $db, $check_only = false)
	{
		if ($check_only)
		{
			return true;
		}
		$query = array(
				"a.nodeid <> 0"
		);

		$cleaner = vB::getCleaner();

		//do this before cleaning to avoid default value interferring
		if (!isset($params['search']['visible']))
		{
			$params['search']['visible'] = -1;
		}

		$search = $cleaner->cleanArray($params['search'], array(
			'filename' => vB_Cleaner::TYPE_STR,
			'attachedbyuser' => vB_Cleaner::TYPE_INT,
			'datelinebefore' => vB_Cleaner::TYPE_STR,
			'datelineafter' => vB_Cleaner::TYPE_STR,
			'downloadsmore' => vB_Cleaner::TYPE_INT,
			'downloadsless' => vB_Cleaner::TYPE_INT,
			'sizemore' => vB_Cleaner::TYPE_INT,
			'sizeless' => vB_Cleaner::TYPE_INT,
			'visible' => vB_Cleaner::TYPE_INT
		));

		//need to distinguish between doesn't exist and the default 0 that
		//we'll have after cleaning.
		$haslimitstart = isset($params[vB_dB_Query::PARAM_LIMITSTART]);
		$haslimit = isset($params[vB_dB_Query::PARAM_LIMIT]);

		//this will intentially clear the raw search param.  We don't want any
		//unknown values past this point.
		$params = $cleaner->cleanArray($params, array(
			'countonly' => vB_Cleaner::TYPE_BOOL,
			vB_dB_Query::PARAM_LIMITSTART => vB_Cleaner::TYPE_INT,
			vB_dB_Query::PARAM_LIMIT => vB_Cleaner::TYPE_INT,
			'sort' => vB_Cleaner::TYPE_STR,
			'direction' => vB_Cleaner::TYPE_STR
		));

		$sortColumn = 'a.filename';
		switch($params['sort'])
		{
			case 'username':
				$sortColumn = 'node.authorname';
				break;
			case 'counter':
				$sortColumn = 'a.counter';
				break;
			case 'filename':
				$sortColumn = 'a.filename';
				break;
			case 'filesize':
				$sortColumn = 'fd.filesize';
				break;
			case 'dateline':
				$sortColumn = 'fd.dateline';
				break;
			case 'state':
				$sortColumn = 'a.visible';
				break;
		}


		$sortDirection = (strtolower($params['direction']) == 'desc' ? 'DESC' : 'ASC');

		if ($search['filename'])
		{
			$query[] = "a.filename LIKE '%" . $db->escape_string_like($search['filename']) . "%' ";
		}

		if ($search['attachedbyuser'])
		{
			$query[] = "node.userid=" . $search['attachedbyuser'];
		}

		$safeBefore = $db->escape_string($search['datelinebefore']);
		$safeAfter = $db->escape_string($search['datelineafter']);

		if ($search['datelinebefore'] AND $search['datelineafter'])
		{
			$query[] = "(fd.dateline BETWEEN UNIX_TIMESTAMP('" . $safeBefore . "') AND UNIX_TIMESTAMP('" . $safeAfter . "')) ";
		}
		else if ($search['datelinebefore'])
		{
			$query[] = "fd.dateline < UNIX_TIMESTAMP('" . $safeBefore . "') ";
		}
		else if ($search['datelineafter'])
		{
			$query[] = "fd.dateline > UNIX_TIMESTAMP('" . $safeAfter . "') ";
		}

		if ($search['downloadsmore'] AND $search['downloadsless'])
		{
			$query[] = "(a.counter BETWEEN " . $search['downloadsmore'] ." AND " . $search['downloadsless'] . ") ";
		}
		else if ($search['downloadsless'])
		{
			$query[] = "a.counter < " . $search['downloadsless'];
		}
		else if ($search['downloadsmore'])
		{
			$query[] = "a.counter > " . $search['downloadsmore'];
		}

		if ($search['sizemore'] AND $search['sizeless'])
		{
			$query[] = "(fd.filesize BETWEEN " . $search['sizemore'] . " AND " . $search['sizeless'] . ") ";
		}
		else if ($search['sizeless'])
		{
			$query[] = "fd.filesize < " . $search['sizeless'];
		}
		else if ($search['sizemore'])
		{
			$query[] = "fd.filesize > " . $search['sizemore'];
		}

 		if ($search['visible'] != -1)
 		{
 			$query[] = "a.visible = " . $search['visible'];
 		}

		$tables = "FROM " . TABLE_PREFIX . "node AS node
				INNER JOIN " . TABLE_PREFIX . "attach AS a ON (node.nodeid = a.nodeid)
				INNER JOIN " . TABLE_PREFIX . "filedata AS fd ON (a.filedataid = fd.filedataid)
				LEFT JOIN " . TABLE_PREFIX . "user AS u ON (u.userid = node.userid)
		";
		$where = "WHERE " . implode(" AND ", $query);
		$limit = "";
		$order = "";
		if (!empty($params['countonly']))
		{
			$fields = "COUNT(*) AS count, SUM(fd.filesize) AS sum";
		}
		else
		{
			$fields = "node.*, fd.filesize, a.filedataid, a.filename, fd.dateline, u.username, a.counter";

			if($haslimit)
			{
				$limit = 'LIMIT ';
				if($haslimitstart)
				{
					$limit .= $params[vB_dB_Query::PARAM_LIMITSTART] . ', ';
				}

				$limit .= $params[vB_dB_Query::PARAM_LIMIT];
			}

			$order = 'ORDER BY ' . $sortColumn . ' ' . $sortDirection;
		}
		$sql = "
				SELECT $fields
				$tables
				$where
				$order
				$limit
		";
		$resultclass = 'vB_dB_' . $this->db_type . '_result';
		$config = vB::getConfig();

		if (isset($config['Misc']['debug_sql']) AND $config['Misc']['debug_sql'])
		{
			echo "sql: $sql<br />\n";
		}
		$result = new $resultclass($db, $sql);
		return $result;
	}

	public function replacePerms($params, vB_Database $db, $check_only = false)
	{
		if ($check_only)
		{
			return isset($params['extension']) AND
				isset($params['size']) AND
				isset($params['width']) AND
				isset($params['height']) AND
				isset($params['groupids']);
		}

		$params = vB::getCleaner()->cleanArray($params, [
			'extension' => vB_Cleaner::TYPE_STR,
			'size' => vB_Cleaner::TYPE_INT,
			'width' => vB_Cleaner::TYPE_INT,
			'height' => vB_Cleaner::TYPE_INT,
			//it would likely make sense to combine these two params into a
			//groupid => attachement permission map.  But there is not current use case
			//and the cleaner doesn't really handle associative arrays.
			'groupids' => vB_Cleaner::TYPE_ARRAY_UINT,
			'attachmentpermissions' => vB_Cleaner::TYPE_INT,
		]);

		$inserts = [];
		foreach($params['groupids'] AS $groupid)
		{
			$fields = [];
			$fields[] = "'" . $db->escape_string($params['extension']) . "'";
			$fields[] = $groupid;
			$fields[] = $params['size'];
			$fields[] = $params['width'];
			$fields[] = $params['height'];
			$fields[] = $params['attachmentpermissions'];

			$inserts[] = '(' . implode(',', $fields) . ')';
		}

		$sql = "
			REPLACE INTO " . TABLE_PREFIX . "attachmentpermission
				(extension, usergroupid, size, width, height, attachmentpermissions)
			VALUES
				" . implode(",\n", $inserts);

		$config = vB::getConfig();
		if (isset($config['Misc']['debug_sql']) AND $config['Misc']['debug_sql'])
		{
				echo "sql: $sql<br />\n";
		}
		$db->query_write($sql);
	}

	/**
	 * This fetch all the pending posts (posts awaiting for approval)
	 *
	 * @param	array $params
	 * @param	object $db
	 * @param	bool $check_only
	 * @result bool | object -- bool if check_only is true, or db result set if not
	 */
	public function fetchPendingPosts($params, $db, $check_only)
	{
		if ($check_only)
		{
			return
				(!empty($params['canModerate']) AND is_array($params['canModerate'])) OR
				(!empty($params['canPublish']) AND is_array($params['canPublish'])) ;
		}
		else
		{
			$params = vB::getCleaner()->cleanArray($params, [
				'canModerate' => vB_Cleaner::TYPE_ARRAY_UINT,
				'canPublish' => vB_Cleaner::TYPE_ARRAY_UINT,
				vB_dB_Query::PARAM_LIMITPAGE => vB_Cleaner::TYPE_UINT,
				vB_dB_Query::PARAM_LIMIT => vB_Cleaner::TYPE_UINT,
				'cutofftime' => vB_Cleaner::TYPE_INT,
				'type' => vB_Cleaner::TYPE_STR
			]);

			$sql = $this->fetchPendingPostsInternalQuery($db, $params);
			if (!$sql)
			{
				return new vB_dB_ArrayResult($db, []);
			}

			//and the non union parts
			$sql .= "ORDER BY publishdate DESC \nLIMIT ";

			if (isset($params[vB_dB_Query::PARAM_LIMIT]) AND intval($params[vB_dB_Query::PARAM_LIMIT]))
			{
				$perpage = intval($params[vB_dB_Query::PARAM_LIMIT]);
			}
			else
			{
				$perpage = 20;
			}

			if (
				isset($params[vB_dB_Query::PARAM_LIMITPAGE]) AND
				intval($params[vB_dB_Query::PARAM_LIMITPAGE]) AND
				(intval($params[vB_dB_Query::PARAM_LIMITPAGE]) > 1) AND
				$perpage
			)
			{
				$sql .=  " " . ($perpage * (intval($params[vB_dB_Query::PARAM_LIMITPAGE]) - 1)) . ",\n";
			}

			$sql .= " " . $perpage;

			return $this->getResultSet($db, $sql, __FUNCTION__);
		}
	}

	/*
	 *	Note that $params must be cleaned by the caller.
	 */
	private function fetchPendingPostsInternalQuery($db, $params)
	{
			$resultclass = 'vB_dB_' . $this->db_type . '_result';
			$bf_forum = vB::getDatastore()->getValue('bf_misc_forumoptions');

			//get the types we don't want to include
			$excludeTypes = array();
			$typesClass = vB_Types::instance();
			$pmId = intval($typesClass->getContentTypeID('vBForum_PrivateMessage'));
			$channelId = intval($typesClass->getContentTypeID('vBForum_Channel'));

			if ($pmId)
			{
				$excludeTypes[] = $pmId;
			}
			if ($channelId)
			{
				$excludeTypes[] = $channelId;
			}

			//Check for canpublish with nodeoptions set.  This is normally articles.
			if (!empty($params['canPublish']))
			{
				// The moderatepublish bit (4194304) corresponds to "Moderate Unpublished Starters"
				// when editing a channel in the *Channel Manager*. This option is not visible when
				// creating an article category in the Articles section of the Admin CP and as such
				// defaults to "off" in that case.
				$articleChannels = new $resultclass($db, "
					SELECT nodeid
					FROM "  . TABLE_PREFIX . "channel
					WHERE nodeid IN (" . implode(',', $params['canPublish']) . ") AND options & " . $bf_forum['moderatepublish'] . "> 0
				");

				if ($articleChannels->valid())
				{
					$checkPublish = array();
					foreach($articleChannels AS $articleChannel)
					{
						$checkPublish[] = $articleChannel['nodeid'];
					}
				}
			}

			/* Added index hint to stop mysql switching indexes
			when a large number of parent nodes is encounted */

			$base = "
				SELECT node.publishdate, node.nodeid, node.contenttypeid
				FROM " . TABLE_PREFIX . "node AS starter
			";

			$join = "
				INNER JOIN " . TABLE_PREFIX . "node AS node ON (node.starter = starter.nodeid)
			";

			$where = [];
			$union = [];

			if (!empty($params['canModerate']))
			{
				$union[] = array(
					'conditions' => "(starter.parentid IN (" . implode(',', $params['canModerate']) . ")
						AND node.approved = 0 AND node.showpublished <> 0) \n",
					'indexhint' => "\nUSE INDEX (PRIMARY)\n",
				);
			}

			if (!empty($checkPublish))
			{
				$union[] = array(
					'conditions' => "(node.nodeid = node.starter AND starter.parentid IN (" . implode(',', $checkPublish) . ") AND node.showpublished < 1)\n",
					'indexhint' => '',
				);
			}

			if (empty($union))
			{
				return false;
			}

			if (!empty($excludeTypes))
			{
				$where[] = "node.contenttypeid NOT IN (" . implode(',', $excludeTypes) . ")\n";
			}

			if (!empty($params['type']))
			{
				switch ($params['type'])
				{
					case 'vm':
						$where[] = "starter.setfor <> ''\n";
					break;
					case 'post':
						$where[] = "starter.setfor = ''\n";
					break;
				}
			}

			// set the cut-off time
			if (!empty($params['cutofftime']))
			{
				$where[] = 'node.created > ' . $params['cutofftime'] . "\n";
			}

			$sql_parts = array();
			foreach($union AS $part)
			{
				$sql_parts[] = "($base $part[indexhint] $join WHERE " . $part['conditions'] . " AND " . implode(' AND ', $where) . ')';
			}

			$sql = implode(' UNION ', $sql_parts);

			return $sql;
	}

	/**
	 * Same as fetchPendingPosts but will only return the totalcount.
	 * @param	array $params
	 * @param	object $db
	 * @param	bool $check_only
	 * @result bool | object -- bool if check_only is true, or db result set if not
	 */
	public function fetchPendingPostsCount($params, $db, $check_only)
	{
		if ($check_only)
		{
			return (!empty($params['canModerate'])  AND (is_array($params['canModerate']) OR is_string($params['canModerate']))) OR
			(!empty($params['canPublish'])  AND (is_array($params['canPublish']) OR is_string($params['canPublish']))) ;
		}
		else
		{

			if (!is_array($params['canModerate']))
			{
				$params['canModerate'] = explode(',', $params['canModerate']);
			}

			if (!is_array($params['canPublish']))
			{
				$params['canPublish'] = explode(',', $params['canPublish']);
			}

			$params = vB::getCleaner()->cleanArray($params, [
				'canModerate' => vB_Cleaner::TYPE_ARRAY_UINT,
				'canPublish' => vB_Cleaner::TYPE_ARRAY_UINT,
			]);

			$sql = $this->fetchPendingPostsInternalQuery($db, $params);
			if (!$sql)
			{
				return new vB_dB_ArrayResult($db, [['ppCount' => 0]]);
			}

			$sql = "SELECT COUNT(*) AS ppCount FROM ($sql) AS dummy";
			return $this->getResultSet($db, $sql, __FUNCTION__);
		}
	}

	//this function can be written as a table query.
	public function deleteProductTemplates($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			return (!empty($params['products']));
		}

		$products = [];
		foreach ($params['products'] AS $product)
		{
			$products[] = $db->escape_string($product);
		}

		$sql = "
			DELETE FROM " . TABLE_PREFIX . "template
			WHERE styleid = -10 AND (product = '". implode("' OR product = '", $products) . "')
		";

		return $this->executeWriteQuery($db, $sql, __FUNCTION__);
	}

	//this function can be written as a table query.
	public function updateProductTemplates($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			return (!empty($params['products']));
		}
		$products = [];
		foreach ($params['products'] AS $product)
		{
			$products[] = $db->escape_string($product);
		}
		$sql = "
			UPDATE " . TABLE_PREFIX . "template
			SET styleid = -10
			WHERE styleid = -1 AND (product = '". implode("' OR product = '", $products) . "')
		";

		return $this->executeWriteQuery($db, $sql, __FUNCTION__);
	}

	/**
	 * Get Stats ordered
	 *
	 *	@param	mixed
	 *	@param	mixed 	a db pointer
	 *	@param	bool
	 *
	 *	@result	mixed
	 */
	public function fetchStats($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			return true;
		}

		$queryBuilder = $this->getQueryBuilder($db);

		$params = vB::getCleaner()->cleanArray($params, [
			'type' => vB_Cleaner::TYPE_NOCLEAN, // Cleaned immediately after this call
			'sqlformat' => vB_Cleaner::TYPE_STR,
			'sortby' => vB_Cleaner::TYPE_STR,
			'sortdir' => vB_Cleaner::TYPE_STR,
			'start_time' => vB_Cleaner::TYPE_UINT,
			'end_time' => vB_Cleaner::TYPE_UINT,
			'nullvalue' => vB_Cleaner::TYPE_BOOL,
		]);

		$safe_field = $queryBuilder->escapeField($params['type']);
		$safe_format = "'" . $db->escape_string($params['sqlformat']) . "'";

		$sortmap = [
			'date' => 'dateline',
			'total' => 'total',
		];

		$orderby = '';
		if(isset($sortmap[$params['sortby']]))
		{
			$dir = (strcasecmp($params['sortdir'], 'DESC') == 0 ? 'DESC' : 'ASC');
			$orderby = 'ORDER BY ' . $sortmap[$params['sortby']] . ' ' . $dir;
		}

		$having = '';
		if(!$params['nullvalue'])
		{
			$having = 'HAVING total > 0';
		}

		$sql = "
			SELECT SUM(" . $safe_field . ") AS total,
				DATE_FORMAT(from_unixtime(dateline), " . $safe_format . ") AS formatted_date,
				AVG(dateline) AS dateline
			FROM " . TABLE_PREFIX . "stats
			WHERE dateline >= " . $params['start_time'] . " AND dateline <= " . $params['end_time'] . "
			GROUP BY formatted_date
			$having
			$orderby
		";
		return $this->getResultSet($db, $sql, __FUNCTION__);
	}


	/**
	 * Fetch Admin Log info
	 *
	 *	@param	mixed
	 *	@param	mixed 	a db pointer
	 *	@param	bool
	 *
	 *	@result	mixed
	 */
	public function fetchAdminLogCount($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			return true;
		}
		else
		{
			$params = vB::getCleaner()->cleanArray($params, array(
				'script' => vB_Cleaner::TYPE_STR,
				'startdate' => vB_Cleaner::TYPE_UINT,
				'enddate' => vB_Cleaner::TYPE_UINT,
				'userid' => vB_Cleaner::TYPE_UINT,
			));

			$sql = "SELECT COUNT(*) AS total FROM " . TABLE_PREFIX . "adminlog AS adminlog ";
			if ($params['userid'] OR $params['script'] OR $params['startdate'] OR $params['enddate'])
			{
				$sql .= 'WHERE 1=1 ';
				if ($params['userid'])
				{
					$sql .= " AND adminlog.userid = " . intval($params['userid']);
				}
				if ($params['script'])
				{
					$sql .= " AND adminlog.script = '" . $db->escape_string($params['script']) . "' ";
				}
				if ($params['startdate'])
				{
					$sql .= " AND adminlog.dateline >= " . $params['startdate'];
				}
				if ($params['enddate'])
				{
					$sql .= " AND adminlog.dateline <= " . $params['enddate'];
				}
			}


			$resultclass = 'vB_dB_' . $this->db_type . '_result';
			$config = vB::getConfig();

			if (isset($config['Misc']['debug_sql']) AND $config['Misc']['debug_sql'])
			{
				echo "sql: $sql<br />\n";
			}

			$result = new $resultclass($db, $sql);
			return $result;
		}
	}

	/**
	 * Fetch Admin Log info
	 *
	 * @param	array $params
	 * @param	object $db
	 * @param	bool $check_only
	 * @result bool | object -- bool if check_only is true, or db result set if not
	 */
	public function fetchAdminLog($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			return true;
		}
		else
		{
			$params = vB::getCleaner()->cleanArray($params, array(
				'script' => vB_Cleaner::TYPE_STR,
				'startdate' => vB_Cleaner::TYPE_UINT,
				'enddate' => vB_Cleaner::TYPE_UINT,
				'userid' => vB_Cleaner::TYPE_UINT,
				'orderby' => vB_Cleaner::TYPE_STR,
				vB_dB_Query::PARAM_LIMITSTART => vB_Cleaner::TYPE_UINT,
				vB_dB_Query::PARAM_LIMIT => vB_Cleaner::TYPE_UINT,
			));

			$sql = "SELECT adminlog.*, user.username FROM " . TABLE_PREFIX . "adminlog AS adminlog
				LEFT JOIN " . TABLE_PREFIX . "user AS user USING(userid) ";
			if ($params['userid'] OR $params['script'] OR $params['startdate'] OR $params['enddate'])
			{
				$sql .= 'WHERE 1=1 ';
				if ($params['userid'])
				{
					$sql .= " AND adminlog.userid = " . intval($params['userid']);
				}
				if ($params['script'])
				{
					$sql .= " AND adminlog.script = '" . $db->escape_string($params['script']) . "' ";
				}
				if ($params['startdate'])
				{
					$sql .= " AND adminlog.dateline >= " . $params['startdate'];
				}
				if ($params['enddate'])
				{
					$sql .= " AND adminlog.dateline <= " . $params['enddate'];
				}
			}
			switch ($params['orderby'])
			{
				case 'user':
					$sql .= ' ORDER BY username ASC,adminlogid DESC';
					break;
				case 'script':
					$sql .= ' ORDER BY script ASC,adminlogid DESC';
					break;
				// Date
				default:
					$sql .= ' ORDER BY adminlogid DESC';
					break;
			}
			$sql .= " LIMIT " . intval($params[vB_dB_Query::PARAM_LIMITSTART]) . ", " .  intval($params[vB_dB_Query::PARAM_LIMIT]);


			$resultclass = 'vB_dB_' . $this->db_type . '_result';
			$config = vB::getConfig();

			if (isset($config['Misc']['debug_sql']) AND $config['Misc']['debug_sql'])
			{
				echo "sql: $sql<br />\n";
			}

			$result = new $resultclass($db, $sql);
			return $result;
		}
	}

	/**
	 * Fetch Admin Log Count by Cut Date
	 *
	 * @param	array $params
	 * @param	object $db
	 * @param	bool $check_only
	 * @result bool | object -- bool if check_only is true, or db result set if not
	 */
	public function countAdminLogByDateCut($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			return ( !empty($params['datecut']) ) ? true : false;
		}
		else
		{
			$params = vB::getCleaner()->cleanArray($params, array(
				'script' => vB_Cleaner::TYPE_STR,
				'datecut' => vB_Cleaner::TYPE_UINT,
				'userid' => vB_Cleaner::TYPE_UINT,
			));

			$sql = "SELECT COUNT(*) AS total FROM " . TABLE_PREFIX . "adminlog AS adminlog WHERE dateline < " . $params['datecut'];
			if ($params['userid'])
			{
				$sql .= " AND userid = " . intval($params['userid']);
			}
			if ($params['script'] != '')
			{
				$sql .= " AND script = '" . $db->escape_string($params['script']) . "'";
			}
			$resultclass = 'vB_dB_' . $this->db_type . '_result';
			$config = vB::getConfig();

			if (isset($config['Misc']['debug_sql']) AND $config['Misc']['debug_sql'])
			{
				echo "sql: $sql<br />\n";
			}

			$result = new $resultclass($db, $sql);
			return $result;
		}
	}

	/**
	 * Delete Admin Log by Cut Date
	 *
	 * @param	array $params
	 * @param	object $db
	 * @param	bool $check_only
	 * @result bool | object -- bool if check_only is true, or db result set if not
	 */
	public function deleteAdminLogByDateCut($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			return ( !empty($params['datecut']) ) ? true : false;
		}

		$params = vB::getCleaner()->cleanArray($params, [
			'script' => vB_Cleaner::TYPE_STR,
			'datecut' => vB_Cleaner::TYPE_UINT,
			'userid' => vB_Cleaner::TYPE_UINT,
		]);

		$sql = "DELETE FROM " . TABLE_PREFIX . "adminlog WHERE dateline < " . $params['datecut'];
		if ($params['userid'])
		{
			$sql .= " AND userid = " . intval($params['userid']);
		}
		if ($params['script'] != '')
		{
			$sql .= " AND script = '" . $db->escape_string($params['script']) . "'";
		}

		return $this->executeWriteQuery($db, $sql, __FUNCTION__);
	}

	/**
	 * Get Node Tools Topics Count
	 *
	 * @param	array $params
	 * @param	object $db
	 * @param	bool $check_only
	 * @result bool | object -- bool if check_only is true, or db result set if not
	 */
	public function getNodeToolsTopicsCount($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			return isset($params['conditions']) AND isset($params['special']['specialchannelid']);
		}
		else
		{
			$params = $this->cleanNodeToolsParams($params);
			$sql = $this->getNodeToolsTopicsSql($db, 'COUNT(*) AS count', $params);
			return $this->getResultSet($db, $sql);
		}
	}

	public function getNodeToolsTopics($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			return isset($params['conditions']) AND isset($params['special']['specialchannelid']);
		}
		else
		{
			$params = $this->cleanNodeToolsParams($params);
			$sql = $this->getNodeToolsTopicsSql($db, 'node.nodeid', $params);
			return $this->getResultSet($db, $sql);
		}
	}

	private function cleanNodeToolsParams($params)
	{
		$cleaner = vB::getCleaner();
		//conditions should *only* be passed to queryBuilder
		//they aren't sufficiently cleaned for anything else
		$params = $cleaner->cleanArray($params, array(
			'conditions'	=> vB_Cleaner::TYPE_NOCLEAN,
			'channelinfo'	=> vB_Cleaner::TYPE_NOCLEAN,
			'special'	=> vB_Cleaner::TYPE_NOCLEAN,
		));

		$params['channelinfo'] = $cleaner->cleanArray($params['channelinfo'], array(
			'channelid'	=> vB_Cleaner::TYPE_INT,
			'subforums'	=> vB_Cleaner::TYPE_BOOL,
		));

		$params['special'] = $cleaner->cleanArray($params['special'], array(
			'unpublished'	=> vB_Cleaner::TYPE_STR,
			'timenow'	=> vB_Cleaner::TYPE_INT,
			'specialchannelid'	=> vB_Cleaner::TYPE_INT,
			'topicsposts'	=> vB_Cleaner::TYPE_STR,
			'ispm'	=> vB_Cleaner::TYPE_BOOL,
		));

		return $params;
	}


	private function getNodeToolsTopicsSql($db, $select, $params)
	{
		$where = array();
		$join = array();

		$conditions = $params['conditions'];
		$channelinfo = $params['channelinfo'];
		$special = $params['special'];

		//$conditions should only be passed to queryBuilder
		//they haven't been cleaned for direct query insert
		$queryBuilder = $this->getQueryBuilder($db);
		$result = $queryBuilder->conditionsToFilter($conditions);
		if($result)
		{
			$where[] = $result;
		}

		if ($channelinfo['channelid'])
		{
			$depth = '';
			if (!$channelinfo['subforums'])
			{
				$depth = 'AND closure.depth = 1';
			}

			$join[] = 'JOIN ' . TABLE_PREFIX . 'closure AS closure ' .
				'ON (node.nodeid = closure.child AND closure.parent = ' . $channelinfo['channelid'] . ' ' . $depth . ')';
		}
		else
		{
			//don't include "special" in "all channels".
			$join[] = 'LEFT JOIN ' . TABLE_PREFIX . 'closure AS special ' .
				'ON (node.nodeid = special.child AND special.parent = ' . $special['specialchannelid'] . ')';

			$where[] = 'special.parent IS NULL';
		}

		if($special['ispm'])
		{
			//this will implicitly filter out anything that isn't a PM ... but we really *shoudn't*
			//have anything else if this flag is set.
			$join[] = 'JOIN ' . TABLE_PREFIX . 'privatemessage AS privatemessage ON (node.nodeid = privatemessage.nodeid)';

			//We have a lot of weird things showhorned into PMs (many of which are probably obsolete after the
			//notification refactor moved those to a seperate system entirely).
			//We only want to deal with bog standard PMs here.
			$where[] = "privatemessage.msgtype = 'message'";
		}

		if($special['unpublished'])
		{
			//we really need to clean up treatment of publish/unpublish because this logic is insane.
			//this is going by vB_Library_Content::isPublished

			$is_published = 'node.publishdate IS NOT NULL AND node.publishdate > 0 AND node.publishdate <= ' . $special['timenow'] .
				' AND (node.unpublishdate IS NULL OR node.unpublishdate = 0 OR node.unpublishdate > ' . $special['timenow'] . ')';

			if($special['unpublished'] == 'no')
			{
				$where[] = $is_published;
			}
			else if($special['unpublished'] == 'yes')
			{
				//note that showpublished is redundant -- we should not have have showpublished set to 1 if
				//timenow is not in the publish/unpublish range.  But it has an index and should cut the search
				//space considerably if no other indexes are hit.  Note that the opposite is not true, it's entirely
				//possbile for showpublished to be 0 and have timenow fall in the publish/unpublish (if, for example
				//the node's parent is not published).

				$where[] = 'node.showpublished = 0';
				$where[] = 'NOT (' . $is_published . ')';
			}
		}

		//if we don't recognize it, assume we only want topics
		if($special['topicsposts'] == 'either')
		{
			//deliberate fallthrough
		}
		else if ($special['topicsposts'] == 'posts')
		{
			$where[] = 'node.nodeid != node.starter';
		}
		//topicsposts = 'topics'
		else
		{
			$where[] = 'node.nodeid = node.starter';
		}

		//We don't want to show attachements and simialar nodes.
		$where[] = 'node.inlist = 1';

		$sql = "SELECT $select
			FROM " . TABLE_PREFIX . "node AS node
				" . implode ("\n", $join) . "
				LEFT JOIN " . TABLE_PREFIX . "nodeview AS nodeview ON (node.nodeid = nodeview.nodeid)
		";

		if($where)
		{
			$sql .= 'WHERE ' . implode(' AND ', $where);
		}

		return $sql;
	}



	/**
	 * Get Max Posts from a thread
	 *
	 * @param	array $params
	 * @param	object $db
	 * @param	bool $check_only
	 * @result bool | object -- bool if check_only is true, or db result set if not
	 */
	public function getMaxPosts($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			return true;
		}
		else
		{
			//Original Query
			//$maxposts = $vbulletin->db->query_first("SELECT userid, username, posts FROM " . TABLE_PREFIX . "user ORDER BY posts DESC");

			//New Query
			$sql = "SELECT userid, username, posts FROM " . TABLE_PREFIX . "user ORDER BY posts DESC LIMIT 1";

			$resultclass = 'vB_dB_' . $this->db_type . '_result';
			$config = vB::getConfig();

			if (isset($config['Misc']['debug_sql']) AND $config['Misc']['debug_sql'])
			{
				echo "sql: $sql<br />\n";
			}

			$result = new $resultclass($db, $sql);
			return $result;
		}
	}

	/**
	 * Get Largest Thread
	 *
	 * @param	array $params
	 * @param	object $db
	 * @param	bool $check_only
	 * @result bool | object -- bool if check_only is true, or db result set if not
	 */
	public function getMaxThread($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			return true;
		}
		else
		{
			//New Query
			$sql = "
				SELECT *
				FROM " . TABLE_PREFIX . "node
				WHERE protected = 0 AND inlist > 0 AND
					contenttypeid != " . vB_Types::instance()->getContenttypeId('vBForum_Channel') . "
				ORDER BY totalcount DESC
				LIMIT 1
			";

			$resultclass = 'vB_dB_' . $this->db_type . '_result';
			$config = vB::getConfig();

			if (isset($config['Misc']['debug_sql']) AND $config['Misc']['debug_sql'])
			{
				echo "sql: $sql<br />\n";
			}

			$result = new $resultclass($db, $sql);
			return $result;
		}
	}

	/**
	 * Get Most Popular Thread
	 *
	 * @param	array $params
	 * @param	object $db
	 * @param	bool $check_only
	 * @result bool | object -- bool if check_only is true, or db result set if not
	 */
	public function getMostPopularThread($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			return true;
		}
		else
		{
			$sql = "SELECT * FROM " . TABLE_PREFIX . "node
				WHERE protected = 0 AND inlist > 0
				AND contenttypeid != " . vB_Types::instance()->getContenttypeId('vBForum_Channel') . "
				ORDER BY views DESC LIMIT 1
			";
			$resultclass = 'vB_dB_' . $this->db_type . '_result';
			$config = vB::getConfig();

			if (isset($config['Misc']['debug_sql']) AND $config['Misc']['debug_sql'])
			{
				echo "sql: $sql<br />\n";
			}

			$result = new $resultclass($db, $sql);
			return $result;
		}
	}

	/**
	 * Get Most Popular Forum
	 *
	 * @param	array $params
	 * @param	object $db
	 * @param	bool $check_only
	 * @result bool | object -- bool if check_only is true, or db result set if not
	 */
	public function getMostPopularForum($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			return true;
		}
		else
		{
			$sql = "
				SELECT * FROM " . TABLE_PREFIX . "node
				WHERE contenttypeid = " . vB_Types::instance()->getContenttypeId('vBForum_Channel') . "
				ORDER BY totalcount DESC LIMIT 1
			";

			$resultclass = 'vB_dB_' . $this->db_type . '_result';
			$config = vB::getConfig();

			if (isset($config['Misc']['debug_sql']) AND $config['Misc']['debug_sql'])
			{
				echo "sql: $sql<br />\n";
			}

			$result = new $resultclass($db, $sql);
			return $result;
		}
	}

	/**
	 * This is used for cache_moderators().
	 *
	 * @param	array $params
	 * @param	object $db
	 * @param	bool $check_only
	 * @result bool | object -- bool if check_only is true, or db result set if not
	 */
	public function getCacheModerators($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			return ((!$params['userid']) OR ($params['userid'] AND is_numeric($params['userid']))) ? true : false;
		}
		else
		{
			$params = vB::getCleaner()->cleanArray($params, [
				'userid' => vB_Cleaner::TYPE_UINT,
			]);

			$sql = "SELECT moderator.*, user.username, user.displayname,
				IF(user.displaygroupid = 0, user.usergroupid, user.displaygroupid) AS displaygroupid, infractiongroupid
				FROM " . TABLE_PREFIX . "moderator AS moderator
				INNER JOIN " . TABLE_PREFIX . "user AS user USING(userid)
				" . ($params['userid'] ? "WHERE moderator.userid = " . $params['userid'] : '');

			return $this->getResultSet($db, $sql, __FUNCTION__);
		}
	}

	/**
	 * This is used for get_stylevars_for_export().
	 *
	 * @param	array $params
	 * @param	object $db
	 * @param	bool $check_only
	 * @result bool | object -- bool if check_only is true, or db result set if not
	 */
	public function getStylevarsForExport($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			return (is_array($params['product']) AND is_array($params['stylelist'])) ? true : false;
		}
		else
		{
			$params = vB::getCleaner()->cleanArray($params, array(
				'stylelist'       => vB_Cleaner::TYPE_ARRAY_INT,
				'product'         => vB_Cleaner::TYPE_ARRAY_STR,
				'stylevar_groups' => vB_Cleaner::TYPE_ARRAY_STR,
			));

			// Would be awkward to do escape_string on the whole implode
			// below. Instead do it per product string here.
			foreach ($params['product'] as $key => $product)
			{
				$params['product'][$key] = $db->escape_string($product);
			}

			$sqlWhereAdditional = "";
			if (!empty($params['stylevar_groups']))
			{
				$unclean = $params['stylevar_groups'];
				unset($params['stylevar_groups']);
				foreach ($unclean as $key => $groupname)
				{
					$params['stylevar_groups'][$key] = $db->escape_string($groupname);
				}
				unset($unclean, $key, $groupname);

				$sqlWhereAdditional = "\nAND stylevardfn.stylevargroup IN ('" . implode("','", $params['stylevar_groups']) . "')";
			}

			$sql = "SELECT stylevar.*,
				INSTR('," . implode(',', $params['stylelist']) . ",', CONCAT(',', stylevar.styleid, ',') ) AS ordercontrol
				FROM " . TABLE_PREFIX . "stylevar AS stylevar
				INNER JOIN " . TABLE_PREFIX . "stylevardfn AS stylevardfn
					ON (
						stylevardfn.stylevarid = stylevar.stylevarid
						AND stylevardfn.product IN ('" . implode("','", $params['product']) . "')
					)
				WHERE stylevar.styleid IN (" . implode(',', $params['stylelist']) . ") $sqlWhereAdditional
				ORDER BY ordercontrol DESC\n
				/** getStylevarsForExport" . (defined('THIS_SCRIPT') ? '- ' . THIS_SCRIPT : '') . "**/";

			$resultclass = 'vB_dB_' . $this->db_type . '_result';
			$config = vB::getConfig();

			if (isset($config['Misc']['debug_sql']) AND $config['Misc']['debug_sql'])
			{
				echo "sql: $sql<br />\n";
			}

			$result = new $resultclass($db, $sql);
			return $result;
		}
	}

	/**
	 * This is used for get_stylevars_for_export().
	 *
	 * @param	array $params
	 * @param	object $db
	 * @param	bool $check_only
	 * @result bool | object -- bool if check_only is true, or db result set if not
	 */
	public function getStylevarsDfnForExport($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			return (is_array($params['product']) AND is_array($params['stylelist'])) ? true : false;
		}
		else
		{
			$params = vB::getCleaner()->cleanArray($params, array(
				'stylelist' => vB_Cleaner::TYPE_ARRAY_INT,
				'product' => vB_Cleaner::TYPE_ARRAY_STR,
				'stylevar_groups' => vB_Cleaner::TYPE_ARRAY_STR,
			));

			// Would be awkward to do escape_string on the whole implode
			// below. Instead do it per product string here.
			foreach ($params['product'] as $key => $product)
			{
				$params['product'][$key] = $db->escape_string($product);
			}

			$sqlWhereAdditional = "";
			if (!empty($params['stylevar_groups']))
			{
				$unclean = $params['stylevar_groups'];
				unset($params['stylevar_groups']);
				foreach ($unclean as $key => $groupname)
				{
					$params['stylevar_groups'][$key] = $db->escape_string($groupname);
				}
				unset($unclean, $key, $groupname);
				$sqlWhereAdditional = "\nAND stylevargroup IN ('" . implode("','", $params['stylevar_groups']) . "')";
			}

			$sql = "SELECT *,
				INSTR('," . implode(',', $params['stylelist']) . ",', CONCAT(',', styleid, ',') ) AS ordercontrol
				FROM " . TABLE_PREFIX . "stylevardfn
				WHERE styleid IN (" . implode(',', $params['stylelist']) . ")
				AND product IN ('" . implode("','", $params['product']) . "') $sqlWhereAdditional
				ORDER BY stylevargroup, stylevarid, ordercontrol\n
				/** getStylevarsDfnForExport" . (defined('THIS_SCRIPT') ? '- ' . THIS_SCRIPT : '') . "**/";

			$resultclass = 'vB_dB_' . $this->db_type . '_result';
			$config = vB::getConfig();

			if (isset($config['Misc']['debug_sql']) AND $config['Misc']['debug_sql'])
			{
				echo "sql: $sql<br />\n";
			}

			$result = new $resultclass($db, $sql);
			return $result;
		}
	}

	/**
	 * This is used in language manager update block.
	 *
	 * @param	array $params
	 * @param	object $db
	 * @param	bool $check_only
	 * @result bool | object -- bool if check_only is true, or db result set if not
	 */
	public function updatePhrasesFromLanguage($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			//@TODO validate each phrase record to be as expected
			return (!empty($params['phraserecords']) AND is_array($params['phraserecords']));
		}

		$params = vB::getCleaner()->cleanArray($params, [
			'phraserecords' => vB_Cleaner::TYPE_NOCLEAN,
		]);

		// Clean per record, no other params.
		$records = [];
		foreach ($params['phraserecords'] AS $phrase)
		{
			$phrase = vB::getCleaner()->cleanArray($phrase, [
				'languageid' => vB_Cleaner::TYPE_UINT,
				'dateline' => vB_Cleaner::TYPE_UINT,
				'fieldname' => vB_Cleaner::TYPE_STR,
				'varname' => vB_Cleaner::TYPE_STR,
				'newphrase' => vB_Cleaner::TYPE_STR,
				'product' => vB_Cleaner::TYPE_STR,
				'username' => vB_Cleaner::TYPE_STR,
				'version' => vB_Cleaner::TYPE_STR,
			]);

			$records[] = "(
				$phrase[languageid],
				'" . $db->escape_string($phrase['fieldname']) . "',
				'" . $db->escape_string($phrase['varname']) . "',
				'" . $db->escape_string($phrase['newphrase']) . "',
				'" . $db->escape_string($phrase['product']) . "',
				'" . $db->escape_string($phrase['username']) . "',
				" . intval($phrase['dateline']) . ",
				'" . $db->escape_string($phrase['version']) . "'
			)";
		}

		$sql = "
			REPLACE INTO " . TABLE_PREFIX . "phrase
				(languageid, fieldname, varname, text, product, username, dateline, version)
			VALUES\n
		";
		$sql .= implode(",\n\t\t\t\t", $records);

		return $this->executeWriteQuery($db, $sql, __FUNCTION__);
	}

	/**
	 * Gets mod information including mod's channel as well.
	 *
	 * 	Used in admincp moderator.
	 *
	 * @param	array $params
	 * @param	object $db
	 * @param	bool $check_only
	 * @result bool | object -- bool if check_only is true, or db result set if not
	 */
	public function getModeratorChannelInfo($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			// field checks
			if (isset($params['sortby']))
			{
				$params['sortby'] = @array_pop($params['sortby']);
				if (isset($params['sortby']['field']) AND !empty($params['sortby']['field']))
				{
					if (!$this->checkSortingFields($params['sortby']['field']))
					{
						return false;
					}
				}
			}

			return true;
		}
		else
		{
			$params = vB::getCleaner()->cleanArray($params, array(
				'nodeid' => vB_Cleaner::TYPE_UINT,
				'moderatorid' => vB_Cleaner::TYPE_UINT,
				'sortby' => vB_Cleaner::TYPE_NOCLEAN, // Handled by getSortingFields
			));

			$queryBuilder = $this->getQueryBuilder($db);
			$orderBy = $this->getSortingFields($params['sortby'], $queryBuilder);

			$sql = "
				SELECT moderator.moderatorid, user.userid, user.username, user.lastactivity, node.nodeid, node.htmltitle,
				moderator.permissions, moderator.permissions2, node.routeid
				FROM " . TABLE_PREFIX . "moderator AS moderator
				INNER JOIN " . TABLE_PREFIX . "node AS node ON (moderator.nodeid = node.nodeid)
				INNER JOIN " . TABLE_PREFIX . "user AS user ON (user.userid = moderator.userid)
				" . ((isset($params['nodeid']) AND intval($params['nodeid'])) ? "WHERE moderator.nodeid = " . $params['nodeid'] : '') . "
				" . ((isset($params['moderatorid']) AND intval($params['moderatorid'])) ? "WHERE moderator.moderatorid = " . $params['moderatorid'] : '') . "
				$orderBy
			";
			return $this->getResultSet($db, $sql, __FUNCTION__);
		}
	}

	/**
	 * 	Used in admincp css.
	 *
	 * @param	array $params
	 * @param	object $db
	 * @param	bool $check_only
	 * @result bool | object -- bool if check_only is true, or db result set if not
	 */
	public function getCronLog($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			if (!empty($params[vB_dB_Query::PARAM_LIMITSTART]) AND (intval($params[vB_dB_Query::PARAM_LIMITSTART]) < 0))
			{
				return false;
			}

			if (!empty($params[vB_dB_Query::PARAM_LIMIT]) AND (intval($params[vB_dB_Query::PARAM_LIMIT]) < 0))
			{
				return false;
			}

			return true;
		}
		else
		{
			$params = vB::getCleaner()->cleanArray($params, array(
				'varname' => vB_Cleaner::TYPE_STR,
				vB_dB_Query::PARAM_LIMITSTART => vB_Cleaner::TYPE_UINT,
				vB_dB_Query::PARAM_LIMIT => vB_Cleaner::TYPE_UINT,
				'orderby' => vB_Cleaner::TYPE_NOCLEAN, // Handled by getSortingFields
				'checkCron' => vB_Cleaner::TYPE_BOOL,
			));

			//because of cleaning this will be set even if not explicitly passed
			$orderBy = '';
			switch ($params['orderby'])
			{
				case 'action':
					$orderBy = 'cronlog.varname ASC, cronlog.dateline DESC';
					break;
				case 'cronid':
					$orderBy = 'cronlog.cronlogid DESC';
					break;
				case 'date':
				default:
					$orderBy = 'cronlog.dateline DESC';
					break;
			}

			if (isset($params['varname']))
			{
				$varname = $db->escape_string($params['varname']);
			}

			$sql = "SELECT cronlog.*
			FROM " . TABLE_PREFIX . "cronlog AS cronlog
			" . ((!empty($params['checkCron'])) ?
			"INNER JOIN " . TABLE_PREFIX . "cron AS cron ON (cronlog.varname = cron.varname)" : "") . "
			" . (!empty($varname) ? "WHERE cronlog.varname = '" . $varname . "'" : '') . "
			ORDER BY $orderBy
			LIMIT " . intval($params[vB_dB_Query::PARAM_LIMITSTART]) . ", " . intval($params[vB_dB_Query::PARAM_LIMIT]) . "
			/** getCronLog" . (defined('THIS_SCRIPT') ? '- ' . THIS_SCRIPT : '') . "**/";

			$resultclass = 'vB_dB_' . $this->db_type . '_result';
			$config = vB::getConfig();

			if (isset($config['Misc']['debug_sql']) AND $config['Misc']['debug_sql'])
			{
				echo "sql: $sql<br />\n";
			}

			$result = new $resultclass($db, $sql);
			return $result;
		}
	}

	/**
	 * Mysql implementation of order by clause.
	 *
	 * @param	array	An array of fields and sort direction.
	 * 					Should be an array containing fields to be sorted are placed in the 'field' index and
	 * 					'direction' index to specify if ASC or DESC.
	 * 					The field index should be in tablename.fieldname form to get the tablename and check against.
	 *	@param	object 	a db pointer
	 *
	 * @return	string	The order by string clause.
	 */
	protected function getSortingFields($sortFields, $queryBuilder)
	{
		$orderBy = "";
		if (!empty($sortFields) AND is_array($sortFields))
		{
			if (isset($sortFields['field']) AND is_array($sortFields['field']))
			{
				$sorts = array();
				foreach ($sortFields['field'] AS $key => $field)
				{
					$sort = $field;

					//this is wrong, but declining to fix now.  It will always strip the
					//alias off the string regardless of if it match aliasField or not.
					if (strpos('aliasField', $sort) !== -1)
					{
						$sort = explode('.', $sort);
						$sort = $sort[1];
					}

					$safe_sort = $queryBuilder->escapeField($sort);
					if (
						!empty($sortFields['direction']) AND !empty($sortFields['direction'][$key]) AND
						(strtoupper( $sortFields['direction'][$key]) == vB_dB_Query::SORT_DESC)
					)
					{
						$safe_sort .=  ' ' . vB_dB_Query::SORT_DESC;
					}
					else
					{
						$safe_sort .=  ' ' . vB_dB_Query::SORT_ASC;
					}
					$sorts[] = $safe_sort;
				}

				if (!empty($sorts))
				{
					$orderBy .= "\n ORDER BY " . implode(', ', $sorts);
				}
			}
			else if (!empty($sortFields['field']))
			{
				$safe_sort = $queryBuilder->escapeField($sortFields['field']);

				$orderBy .= "\n ORDER BY " . $safe_sort;
				if (!empty($sortFields['direction']) AND (strtoupper($sortFields['direction']) == vB_dB_Query::SORT_DESC))
				{
					$orderBy .= " " . $sortFields['direction'];
				}
			}
		}

		return $orderBy;
	}

	/**
	 * Validate the sorting fields passed.
	 *
	 * @param	array	An array of fields.
	 * 					Should be tablename.fieldname to get the tablename and check against.
	 *
	 * @return	bool	True - valid, False - not valid
	 */
	protected function checkSortingFields($sortFields)
	{
		foreach ($sortFields AS $val)
		{
			$dbField = explode('.', $val);
			$tableStructure = vB::getDbAssertor()->fetchTableStructure($dbField[0]);

			// try getting from vBForum package
			if ($dbField[0] == 'aliasField')
			{
				return true;
			}

			if (empty($tableStructure))
			{
				$tableStructure = vB::getDbAssertor()->fetchTableStructure('vBForum:' . $dbField[0]);
			}

			if (empty($tableStructure))
			{
				return false;
			}

			$tableStructure = $tableStructure['structure'];
			if (!in_array($dbField[1], $tableStructure))
			{
				return false;
			}
		}

		return true;
	}

	/**
	 *  Get the user subscription log
	 * 	Used in admincp subscription manager.
	 *
	 *	@param	mixed
	 *	@param	mixed 	a db pointer
	 *	@param	bool
	 *
	 *	@result	mixed
	 */
	public function getSubscriptionUsersLog($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			// field checks
			if (isset($params['sortby']))
			{
				$params['sortby'] = @array_pop($params['sortby']);
				if (isset($params['sortby']['field']) AND !empty($params['sortby']['field']))
				{
					if (!$this->checkSortingFields($params['sortby']['field']))
					{
						return false;
					}
				}
			}

			if (isset($params[vB_dB_Query::PARAM_LIMIT]) AND !is_numeric($params[vB_dB_Query::PARAM_LIMIT]))
			{
				return false;
			}

			if (isset($params[vB_dB_Query::PARAM_LIMITSTART]) AND !is_numeric($params[vB_dB_Query::PARAM_LIMITSTART]))
			{
				return false;
			}

			return true;
		}
		else
		{
			$queryBuilder = $this->getQueryBuilder($db);
			$where = '';
			if (!empty($params['conditions']))
			{
				$where .= "WHERE " . $queryBuilder->conditionsToFilter($params['conditions']);
			}

			$orderBy = '';
			if (!empty($params['sortby']))
			{
				$sortorder = @array_pop($params['sortby']);
				$orderBy = $this->getSortingFields($sortorder, $queryBuilder);
				unset($sortorder);
			}

			$limit = '';
			if (!empty($params[vB_dB_Query::PARAM_LIMITSTART]) AND !empty($params[vB_dB_Query::PARAM_LIMIT]))
			{
				// Although the "check only" ensures these are numeric, we never explicitly clean the $params in bulk.
				// Intvaling here before using.
				$limit = 'LIMIT ' . intval($params[vB_dB_Query::PARAM_LIMITSTART]) . ', ' . intval($params[vB_dB_Query::PARAM_LIMIT]);
			}

			if (!empty($params['count']))
			{
				$selectFields = 'COUNT(*) AS users';
			}
			else
			{
				$selectFields = 'user.userid, user.username, subscriptionlog.*';
			}

			// params is not cleaned, do not use directly.
			unset($params);

			$sql = "
				SELECT $selectFields
				FROM " . TABLE_PREFIX . "subscriptionlog AS subscriptionlog
				LEFT JOIN " . TABLE_PREFIX . "user AS user USING (userid)
				$where
				$orderBy
				$limit";

			return $this->getResultSet($db, $sql, __FUNCTION__);
		}
	}

	/**
	 *  Do the subscription log display order
	 * 	Used in admincp subscription manager.
	 *
	 *	@param	mixed
	 *	@param	mixed 	a db pointer
	 *	@param	bool
	 *
	 *	@result	mixed
	 */
	public function doSubscriptionLogOrder($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			// field checks
			if (empty($params['subscriptions']) OR empty($params['displayorder']))
			{
				return false;
			}

			return true;
		}
		else
		{
			$cleaner = vB::getCleaner();
			$params = $cleaner->cleanArray($params, array(
				'subscriptions' => vB_Cleaner::TYPE_NOCLEAN, // array of arrays. will handle this next
				'displayorder' => vB_Cleaner::TYPE_ARRAY_INT,
			));

			foreach ($params['subscriptions'] AS $subId => $sub)
			{
				$params['subscriptions'][$subId] = $cleaner->cleanArray($sub, array(
					'subscriptionid' => vB_Cleaner::TYPE_UINT,
					'displayorder' => vB_Cleaner::TYPE_INT,
				));
			}

			$casesql = '';
			$subscriptionids = '';

			foreach($params['subscriptions'] AS $sub)
			{
				if (!isset($params['displayorder']["$sub[subscriptionid]"]))
				{
					continue;
				}

				$displayorder = intval($params['displayorder']["$sub[subscriptionid]"]);
				$displayorder = ($displayorder < 0) ? 0 : $displayorder;
				if ($sub['displayorder'] != $displayorder)
				{
					$casesql .= "WHEN subscriptionid = $sub[subscriptionid] THEN $displayorder\n";
					$subscriptionids .= ",$sub[subscriptionid]";
				}
			}

			if (empty($casesql))
			{
				return false;
			}

			$sql = "
				UPDATE " . TABLE_PREFIX . "subscription
					SET displayorder =
						CASE
							$casesql
							ELSE 1
						END
					WHERE subscriptionid IN (-1$subscriptionids)
				/** doSubscriptionLogOrder" . (defined('THIS_SCRIPT') ? '- ' . THIS_SCRIPT : '') . "**/";

			$config = vB::getConfig();

			if (isset($config['Misc']['debug_sql']) AND $config['Misc']['debug_sql'])
			{
				echo "sql: $sql<br />\n";
			}

			return $db->query_write($sql);
		}
	}

	/**
	 *  Gets the pms for the users
	 *
	 *	@param	mixed
	 *	@param	mixed 	a db pointer
	 *	@param	bool
	 *
	 *	@result	mixed
	 */
	public function getUsersPms($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			if (isset($params['total']) AND !is_numeric($params['total']))
			{
				return false;
			}

			return true;
		}

		$params = vB::getCleaner()->cleanArray($params, [
			'sortby' => vB_Cleaner::TYPE_STR,
			'sortdir' => vB_Cleaner::TYPE_STR,
			'total'	=> vB_Cleaner::TYPE_UINT,
		]);

		$pmType = vB_Types::instance()->getContentTypeId('vBForum_PrivateMessage');

		$where = [];
		$folderlist = [
			vB_Library_Content_Privatemessage::TRASH_FOLDER,
			vB_Library_Content_Privatemessage::REQUEST_FOLDER,
			vB_Library_Content_Privatemessage::NOTIFICATION_FOLDER,
			vB_Library_Content_Privatemessage::PENDING_FOLDER
		];
		$where[] = 'node.contenttypeid = ' .  $pmType;
		$where[] = "(messagefolder.titlephrase NOT IN ('" . implode("', '", $folderlist) . "') OR messagefolder.title <> '')";
		$where[] = 'sentto.deleted = 0';

		$having = '';
		if ($params['total'])
		{
			$having = "HAVING total = " . $params['total'];
		}

		$sortmap = [
			'username' => 'user.username',
			'total' => 'total',
		];

		$orderby = '';
		if(isset($sortmap[$params['sortby']]))
		{
			$dir = (strcasecmp($params['sortdir'], 'DESC') == 0 ? 'DESC' : 'ASC');
			$orderby = 'ORDER BY ' . $sortmap[$params['sortby']] . ' ' . $dir;
		}

		$sql = "
			SELECT user.userid, user.username, user.lastactivity, user.email, count(sentto.nodeid) AS total
			FROM " . TABLE_PREFIX . "sentto AS sentto
				INNER JOIN " . TABLE_PREFIX . "node AS node ON (sentto.nodeid = node.nodeid)
				INNER JOIN " . TABLE_PREFIX . "user AS user ON (sentto.userid = user.userid)
				INNER JOIN " . TABLE_PREFIX . "messagefolder AS messagefolder ON (sentto.folderid = messagefolder.folderid)
			WHERE " . implode(' AND ', $where) . "
			GROUP BY sentto.userid
			$having
			$orderby
		";

		return $this->getResultSet($db, $sql, __FUNCTION__);
	}

	/**
	 *  Gets the payment transaction statistics info
	 *
	 *	@param	mixed
	 *	@param	mixed 	a db pointer
	 *	@param	bool
	 *
	 *	@result	mixed
	 */
	public function getTransactionStats($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			// field checks
			if (isset($params['sortby']))
			{
				$params['sortby'] = @array_pop($params['sortby']);
				if (isset($params['sortby']['field']) AND !empty($params['sortby']['field']))
				{
					if (!$this->checkSortingFields($params['sortby']['field']))
					{
						return false;
					}
				}
			}

			return !empty($params['sqlformat']);
		}

		$params = vB::getCleaner()->cleanArray($params, [
			'sqlformat' => vB_Cleaner::TYPE_STR,
			// Handled by getSortingFields
			'sortby' => vB_Cleaner::TYPE_NOCLEAN,
			//this should only be passed to queryBuilder
			vB_dB_Query::CONDITIONS_KEY => vB_Cleaner::TYPE_NOCLEAN,
		]);

		$queryBuilder = $this->getQueryBuilder($db);
		$where = "";
		if (!empty($params[vB_dB_Query::CONDITIONS_KEY]))
		{
			$where .= "WHERE " . $queryBuilder->conditionsToFilter($params[vB_dB_Query::CONDITIONS_KEY]);
		}

		if (!empty($params['sortby']))
		{
			$sortorder = @array_pop($params['sortby']);
		}

		$safe_format = "'" . $db->escape_string($params['sqlformat'] ) . "'";

		$orderBy = $this->getSortingFields($sortorder, $queryBuilder);
		$sql = "
			SELECT COUNT(*) AS total,
				DATE_FORMAT(from_unixtime(dateline), " . $safe_format . ") AS formatted_date,
				MAX(dateline) AS dateline
			FROM " . TABLE_PREFIX . "paymenttransaction AS paymenttransaction
			LEFT JOIN " . TABLE_PREFIX . "paymentinfo AS paymentinfo ON (paymenttransaction.paymentinfoid = paymentinfo.paymentinfoid)
			$where
			GROUP BY formatted_date
			$orderBy
		";

		return $this->getResultSet($db, $sql, __FUNCTION__);
	}

	/**
	 *  Gets the payment transaction log info
	 *
	 *	@param	mixed
	 *	@param	mixed 	a db pointer
	 *	@param	bool
	 *
	 *	@result	mixed
	 */
	public function getTransactionLog($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			// field checks
			if (isset($params['sortby']))
			{
				$params['sortby'] = @array_pop($params['sortby']);
				if (isset($params['sortby']['field']) AND !empty($params['sortby']['field']))
				{
					if (!$this->checkSortingFields($params['sortby']['field']))
					{
						return false;
					}
				}
			}

			if (isset($params[vB_dB_Query::PARAM_LIMIT]) AND !is_numeric($params[vB_dB_Query::PARAM_LIMIT]))
			{
				return false;
			}

			if (isset($params[vB_dB_Query::PARAM_LIMITSTART]) AND !is_numeric($params[vB_dB_Query::PARAM_LIMITSTART]))
			{
				return false;
			}

			return true;
		}

		$params = vB::getCleaner()->cleanArray($params, array(
			'sortby' => vB_Cleaner::TYPE_NOCLEAN,
			vB_dB_Query::CONDITIONS_KEY => vB_Cleaner::TYPE_NOCLEAN,
			vB_dB_Query::PARAM_LIMIT => vB_Cleaner::TYPE_UINT,
			vB_dB_Query::PARAM_LIMITPAGE => vB_Cleaner::TYPE_UINT
		));

		$queryBuilder = $this->getQueryBuilder($db);
		$where = "";
		if (!empty($params[vB_dB_Query::CONDITIONS_KEY]))
		{
			$where .= "WHERE " . $queryBuilder->conditionsToFilter($params[vB_dB_Query::CONDITIONS_KEY]);
		}

		if (!empty($params['sortby']))
		{
			$sortorder = @array_pop($params['sortby']);
		}

		$orderBy = $this->getSortingFields($sortorder, $queryBuilder);

		$limit = '';
		if (!empty($params[vB_dB_Query::PARAM_LIMITSTART]) AND !empty($params[vB_dB_Query::PARAM_LIMIT]))
		{
			$limit = 'LIMIT ' . $params[vB_dB_Query::PARAM_LIMITSTART] . ', ' . $params[vB_dB_Query::PARAM_LIMIT];
			$paginate = true;
		}

		$sql = "
			SELECT paymenttransaction.*,
				paymentinfo.subscriptionid, paymentinfo.userid,
				paymentapi.title,
				user.username
			FROM " . TABLE_PREFIX . "paymenttransaction AS paymenttransaction
			LEFT JOIN " . TABLE_PREFIX . "paymentinfo AS paymentinfo ON (paymenttransaction.paymentinfoid = paymentinfo.paymentinfoid)
			LEFT JOIN " . TABLE_PREFIX . "paymentapi AS paymentapi ON (paymenttransaction.paymentapiid = paymentapi.paymentapiid)
			LEFT JOIN " . TABLE_PREFIX . "user AS user ON (paymentinfo.userid = user.userid)
			$where
			$orderBy
			$limit
		";

		return $this->getResultSet($db, $sql, __FUNCTION__);
	}

	/**
	 *  Gets the payment transaction log total count
	 *
	 *	@param	mixed
	 *	@param	mixed 	a db pointer
	 *	@param	bool
	 *
	 *	@result	mixed
	 */
	public function getTransactionLogCount($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			return true;
		}

		$params = vB::getCleaner()->cleanArray($params, array(
			vB_dB_Query::CONDITIONS_KEY => vB_Cleaner::TYPE_NOCLEAN,
		));

		$where = "";
		if (!empty($params[vB_dB_Query::CONDITIONS_KEY]))
		{
			$queryBuilder = $this->getQueryBuilder($db);
			$where .= "WHERE " . $queryBuilder->conditionsToFilter($params[vB_dB_Query::CONDITIONS_KEY]);
		}

		$sql = "
			SELECT COUNT(*) AS trans
			FROM " . TABLE_PREFIX . "paymenttransaction AS paymenttransaction
			LEFT JOIN " . TABLE_PREFIX . "paymentinfo AS paymentinfo ON (paymenttransaction.paymentinfoid = paymentinfo.paymentinfoid)
			$where
		";

		return $this->getResultSet($db, $sql, __FUNCTION__);
	}

	/**
	 * Get the all socialgroups count.
	 *
	 *	@param	mixed
	 *	@param	mixed 	a db pointer
	 *	@param	bool
	 *
	 *	@result	mixed
	 */
	public function getSocialGroupsTotalCount($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			if (isset($params['userid']) AND !intval($params['userid']))
			{
				return false;
			}

			if (isset($params['my_channels']) AND !intval($params['my_channels']))
			{
				return false;
			}

			if (!isset($params['sgParentChannel']) OR !intval($params['sgParentChannel']))
			{
				return false;
			}

			if (!isset($params['depth']) OR !intval($params['depth']))
			{
				return false;
			}

			return true;
		}
		else
		{
			$params = vB::getCleaner()->cleanArray($params, array(
				'depth'           => vB_Cleaner::TYPE_UINT,
				'sgParentChannel' => vB_Cleaner::TYPE_UINT,
				'userid'          => vB_Cleaner::TYPE_UINT,
				'my_channels'     => vB_Cleaner::TYPE_UINT,
			));

			$where = "WHERE cl.depth = " . intval($params['depth']) . " AND cl.parent = " . intval($params['sgParentChannel']);
			if (!empty($params['userid']))
			{
				$where .= " AND node.userid = " . intval($params['userid']);
			}

			$gitjoin = '';
			if (!empty($params['my_channels']))
			{
				$gitjoin = "INNER JOIN " . TABLE_PREFIX . "groupintopic AS git ON (git.userid = " . intval($params['my_channels']) . " AND git.nodeid = node.nodeid)";
			}

			$permflags = $this->getNodePermTerms();
			$sql = "
				SELECT COUNT(node.nodeid) AS totalcount
				FROM " . TABLE_PREFIX . "node AS node
				INNER JOIN " . TABLE_PREFIX . "closure AS cl ON (node.nodeid = cl.child)
				$gitjoin
				" . (!empty($permflags['joins']['starter']) ? $permflags['joins']['starter'] : '') . "
				" . (!empty($permflags['joins']['blocked']) ? $permflags['joins']['blocked'] : '') . "
				$where
				" . (!empty($permflags['where']) ? $permflags['where'] : '') . "
				/** getSocialGroupsTotalCount" . (defined('THIS_SCRIPT') ? '- ' . THIS_SCRIPT : '') . "**/
			";

			$resultclass = 'vB_dB_' . $this->db_type . '_result';
			$config = vB::getConfig();

			if (isset($config['Misc']['debug_sql']) AND $config['Misc']['debug_sql'])
			{
				echo "sql: $sql<br />\n";
			}

			$result = new $resultclass($db, $sql);
			return $result;
		}
	}

	/**
	 * Get the socialgroups categories.
	 *
	 * @param	array $params
	 * @param	object $db
	 * @param	bool $check_only
	 * @result bool | object -- bool if check_only is true, or db result set if not
	 */
	public function getSocialGroupsCategories($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			return true;
		}
		else
		{
			$sgChannel = intval(vB_Api::instanceInternal('socialgroup')->getSGChannel());
			$channelContentTypeId = intval(vB_Types::instance()->getContentTypeID('vBForum_Channel'));

			//cleaner won't handle the scalar/array check gracefully.  Clean manually.
			$safe_categories = [];
			if (!empty($params['categoryId']))
			{
				$categories = (is_array($params['categoryId']) ? $params['categoryId'] : [$params['categoryId']]);
				$safe_categories = array_map('intval', $categories);
			}

			$params = vB::getCleaner()->cleanArray($params, [
				'doCount' => vB_Cleaner::TYPE_BOOL,
				'fetchCreator' => vB_Cleaner::TYPE_BOOL,
			]);

			$sqlSelect = [];
			$sqlJoins = [];
			$sqlWhere = [];
			$sqlGroupBy =[];

			$sqlSelect[] = 'n.*';
			$sqlWhere[] = "n.parentid = $sgChannel AND n.contenttypeid = $channelContentTypeId";

			if ($params['doCount'])
			{
				$sqlSelect[] = 'COUNT(c.child) AS groupcount';
				$sqlJoins[] = 'LEFT JOIN ' . TABLE_PREFIX . 'closure AS c ON n.nodeid = c.parent AND c.depth = 1';
				$sqlJoins[] = 'LEFT JOIN ' . TABLE_PREFIX . "node AS n2 ON c.child = n2.nodeid AND n2.contenttypeid = $channelContentTypeId";
				$sqlGroupBy[] = 'n.nodeid';
			}

			if ($params['fetchCreator'])
			{
				$sqlSelect[] = 'u.username';
				$sqlJoins[] = 'LEFT JOIN ' . TABLE_PREFIX . 'user u ON n.userid = u.userid';
			}

			if ($safe_categories)
			{
				$sqlWhere[] = "n.nodeid IN (" . implode(',', $safe_categories) . ")";
			}

			$sql = "
				SELECT " . implode(', ', $sqlSelect) . "
				FROM " . TABLE_PREFIX . "node AS n
					" . implode(" \n", $sqlJoins) . "
				WHERE "  . implode("\n AND ", $sqlWhere) . "
				" . (empty($sqlGroupBy) ? '' : ('GROUP BY ' . implode(', ', $sqlGroupBy))) . "
				ORDER BY n.title
			";

			return $this->getResultSet($db, $sql, __FUNCTION__);
		}
	}

	public function getTLChannelInfo($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			return !empty($params['channelid']) AND isset($params['from']) AND !empty($params['perpage']);
		}

		$types = [
			'vBForum_Text',
			'vBForum_Poll',
			'vBForum_Gallery',
			'vBForum_Video',
			'vBForum_Link',
			'vBForum_Event',
		];
		$types = array_map([vB_Types::instance(), 'getContentTypeID'], $types);

		$params = vB::getCleaner()->cleanArray($params, [
			'channelid' => vB_Cleaner::TYPE_UINT,
			'from' => vB_Cleaner::TYPE_UINT,
			'perpage' => vB_Cleaner::TYPE_UINT,
		]);

		$sql = '
			SELECT parent.* , (
				SELECT count(*)
					FROM ' . TABLE_PREFIX . 'closure AS cl2
					INNER JOIN ' . TABLE_PREFIX . 'node AS node ON cl2.child = node.nodeid
					WHERE
						cl2.parent = parent.nodeid
						AND node.contenttypeid IN ( ' . implode(',', $types) . ' )
						AND (
							node.parentid = node.starter
							OR node.nodeid = node.starter
							)
				) AS count
			FROM ' . TABLE_PREFIX . 'closure AS cl
			INNER JOIN ' . TABLE_PREFIX . 'node AS parent ON parent.nodeid = cl.child AND parent.contenttypeid =23 AND cl.depth >0
			INNER JOIN ' . TABLE_PREFIX . 'channel AS c ON c.nodeid = parent.nodeid
			WHERE cl.parent = ' . $params['channelid'] . ' AND c.category =0
			ORDER BY parent.title ASC
			LIMIT ' . $params['from'] . ' , ' . $params['perpage'];

		return $this->getResultSet($db, $sql, __FUNCTION__);
	}

	public function getTLChannelCount($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			return !empty($params['channelid']);
		}

		$params = vB::getCleaner()->cleanArray($params, [
			'channelid' => vB_Cleaner::TYPE_UINT,
		]);

		$sql = '
			SELECT count(*) AS count
			FROM ' . TABLE_PREFIX . 'channel c
				INNER JOIN ' . TABLE_PREFIX . 'node n ON c.nodeid = n.nodeid
				INNER JOIN ' . TABLE_PREFIX . 'closure cl ON cl.child = n.nodeid
			WHERE
				cl.parent = ' . $params['channelid'] . '
				AND cl.depth > 0
				AND c.category = 0
		';

		return $this->getResultSet($db, $sql, __FUNCTION__);
	}

	public function clearPictureData($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			return true; // Nothing really to check, so just return true.
		}

		$db->query_write("UPDATE " . TABLE_PREFIX . "customavatar SET filedata = '', filedata_thumb = ''");
		return true;
	}

	public function deleteChildContentTableRecords($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			return !empty($params['tables']) AND !empty($params['nodeid']);
		}

		$params = vB::getCleaner()->cleanArray($params, [
			'tables' => vB_Cleaner::TYPE_ARRAY_STR, // be sure to escape this!
			'nodeid' => vB_Cleaner::TYPE_UINT,
		]);


		$queryBuilder = $this->getQueryBuilder($db);

		$counter = 0;
		$deletions = [];
		$joins = [];
		foreach($params['tables'] AS $name => $field)
		{
			$counter++;
			$safe_table = $queryBuilder->escapeTable($name);
			$safe_field = $queryBuilder->escapeField($field);
			$alias = 'table' . $counter;

			$deletions[] = $alias;
		 	$joins[] = "LEFT JOIN $safe_table AS $alias ON (cl.child = $alias.$safe_field)";
		}

		$sql = "
			DELETE " . implode(',', $deletions) . "
			FROM " . TABLE_PREFIX . "closure AS cl
			" . implode("\n", $joins) . "
			WHERE cl.parent IN (" . $params['nodeid']. ")
		";

		return $this->executeWriteQuery($db, $sql, __FUNCTION__);
	}

	/**
	 * Rebuilds the pmtotal for the given userids
	 *
	 *	@param array			the query parameters
	 *	@param	object		the database object
	 *	@param	bool		whether we run the query, or just validate that we can run it.
	 *
	 *	@return	int
	 *
	 */
	public function buildPmTotals($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			return (!empty($params['userid']));
		}

		$userids = array_map('intval', $params['userid']);

		//ensure that nothing "leaks" that wasn't explicitly cleaned.
		unset($params);

		$pmtotalsql = "";
		$users = array();
		$sql1 = "
			SELECT sentto.userid, COUNT(DISTINCT node.nodeid) AS pmtotal
			FROM " . TABLE_PREFIX . "sentto AS sentto
				INNER JOIN " . TABLE_PREFIX . "node AS node ON (node.nodeid = sentto.nodeid AND node.nodeid = node.starter)
				INNER JOIN " . TABLE_PREFIX . "privatemessage AS pm ON (pm.nodeid = node.nodeid AND pm.msgtype = 'message')
			WHERE
				sentto.userid IN (" . implode(', ', $userids) . ") AND sentto.deleted = 0
			GROUP BY sentto.userid
			/**" . __FUNCTION__ . ' (Fetch Totals) ' . (defined('THIS_SCRIPT') ? '- ' . THIS_SCRIPT : '') . "**/\n";

		$user_without_pms = array_flip($userids);

		$results = $db->query_read($sql1);
		while ($result = $db->fetch_array($results))
		{
			unset($user_without_pms[$result['userid']]);
			$users[] = $result['userid'];
			$pmtotalsql .= "WHEN {$result['userid']} THEN {$result['pmtotal']} ";
		}

		$sql2 = '';
		if (!empty($pmtotalsql))
		{
			$sql2 = "
				UPDATE " . TABLE_PREFIX . "user
				SET pmtotal = CASE userid
					$pmtotalsql
					ELSE pmtotal END
				WHERE userid IN (" . implode(', ', $users) . ")
				/**" . __FUNCTION__ . ' (Update Totals) ' . (defined('THIS_SCRIPT') ? '- ' . THIS_SCRIPT : '') . "**/\n";

			$db->query_write($sql2);
		}

		$sql3 = '';
		if(count($user_without_pms))
		{
			$sql3 = "
				UPDATE " . TABLE_PREFIX . "user
				SET pmtotal = 0
				WHERE userid IN (" . implode(', ', array_keys($user_without_pms)) . ")
				/**" . __FUNCTION__ . ' (Reset Totals) ' . (defined('THIS_SCRIPT') ? '- ' . THIS_SCRIPT : '') . "**/\n";

			$db->query_write($sql3);
		}

		$config = vB::getConfig();
		if (isset($config['Misc']['debug_sql']) AND $config['Misc']['debug_sql'])
		{
			echo "sql: $sql1\n$sql2\n$sql3 <br />\n";
		}

		return $result;
	}

	/**
	 * Gets subscribers from a given nodeid
	 *
	 * @param	array $params
	 * @param	object $db
	 * @param	bool $check_only
	 * @result bool | object -- bool if check_only is true, or db result set if not
	 */
	public function fetchNodeSubscribers($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			if (!isset($params['nodeid']))
			{
				return false;
			}

			foreach (array('nodeid', vB_dB_Query::PARAM_LIMITPAGE, vB_dB_Query::PARAM_LIMIT) AS $param)
			{
				if (isset($params[$param]) AND (!is_numeric($params[$param]) OR ($params[$param] < 1)))
				{
					return false;
				}
			}

			if (isset($params['sort']) AND !is_array($params['sort']))
			{
				if (!is_array($params['sort']))
				{
					return false;
				}

				foreach ($params['sort'] AS $field => $direction)
				{
					if (!in_array($field, array('username', 'userid', 'lastactivity')))
					{
						return false;
					}

					if (!in_array($direction, array(vB_dB_Query::SORT_ASC, vB_dB_Query::SORT_DESC)))
					{
						return false;
					}
				}
			}

			return true;
		}
		else
		{
			/*
			 * Note that the sort cleaning below actually does nothing useful. It is there purely to maintain the
			 * standard cleaning format. Note that the code above in the checkOnly block will only allow valid
			 * parameters through, except that it only checks for a nodeid. The clean below is reguired to ensure a
			 * valid integer nodeid.
			 */
			$params = vB::getCleaner()->cleanArray($params, array(
				'nodeid' => vB_Cleaner::TYPE_UINT,
				'sort' => vB_Cleaner::TYPE_ARRAY_STR,
				vB_dB_Query::PARAM_LIMIT => vB_Cleaner::TYPE_UINT,
				vB_dB_Query::PARAM_LIMITPAGE => vB_Cleaner::TYPE_UINT
			));

			$sql = "SELECT SQL_CALC_FOUND_ROWS u.userid, u.username, u.displayname
			FROM " . TABLE_PREFIX . "subscribediscussion sd
			INNER JOIN " . TABLE_PREFIX . "user u ON sd.userid = u.userid
			WHERE sd.discussionid = " . $params['nodeid'] . "\n";

			$sorts = array();
			if (isset($params['sort']))
			{
				foreach ($params['sort'] AS $field => $direction)
				{
					$sorts[] = 'u.' . $field . ' ' . $direction;
				}

				$sql .= "ORDER BY " . implode(", ", $sorts) . "\n";
			}

			$limit = "";
			if (!empty($params[vB_dB_Query::PARAM_LIMIT]))
			{
				$perpage = $params[vB_dB_Query::PARAM_LIMIT];
			}
			else
			{
				$perpage = 20;
			}

			if (!empty($params[vB_dB_Query::PARAM_LIMITPAGE]))
			{
				$limit .=  ($perpage * ($params[vB_dB_Query::PARAM_LIMITPAGE] - 1)) . ',';
			}

			$sql .= "LIMIT " . $limit .$perpage;
			$sql .= "\n/**" . __FUNCTION__ . (defined('THIS_SCRIPT') ? '- ' . THIS_SCRIPT : '') . "**/";

			$resultclass = 'vB_dB_' . $this->db_type . '_result';
			$config = vB::getConfig();
			if (isset($config['Misc']['debug_sql']) AND $config['Misc']['debug_sql'])
			{
				echo "sql: $sql<br />\n";
			}

			$result = new $resultclass($db, $sql);
			return $result;
		}
	}

	/**
	 * Updates node totals based on supplied array
	 *
	 *	@param	mixed		the query parameters
	 * 	@param	object		the database object
	 * 	@param	bool		whether we run the query, or just validate that we can run it.
	 */
	public function updateNodeTotals($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			return (!empty($params['updates']));
		}

		$sql = '';
		$nodes = array();
		$multiple = $flag = false;
		$updates =& $params['updates'];

		if ($updates)
		{
			foreach ($updates AS $field => $values)
			{
				if ($values)
				{
					$field = vB::getCleaner()->clean($field,  vB_Cleaner::TYPE_STR);
					$field = $db->clean_identifier($field);
					$sql .= $multiple ? ",\n" : "\n";
					$sql .= " $field = CASE nodeid \n";

					foreach ($values AS $nodeid => $value)
					{
						$nodeid = vB::getCleaner()->clean($nodeid,  vB_Cleaner::TYPE_UINT);
						$value = vB::getCleaner()->clean($value,  vB_Cleaner::TYPE_INT);

						$flag = true;
						$nodes[] = $nodeid;

						if ($value > -1)
						{
							$sql .= " WHEN $nodeid THEN $field + $value \n";
						}
						else
						{
							$sql .= " WHEN $nodeid THEN (CASE WHEN $field > " . abs($value) . " THEN $field + ($value) ELSE 0 END) \n";
						}
					}

					$multiple = true;
					$sql .= " ELSE $field END";
				}
			}
		}

		if ($flag)
		{
			$nodes = implode(',', array_unique($nodes));

			$db->query_write(" UPDATE " . TABLE_PREFIX . "node SET \n $sql \n WHERE nodeid IN ($nodes)");
		}
	}

	public function searchHelp($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			if(empty($params['search']) OR empty($params['languages']) OR empty($params['fields']))
			{
				return false;
			}
			else
			{
				return true;
			}
		}
		$className = 'vB_Db_' . $this->db_type  . '_QueryBuilder';
		$queryBuilder = new $className($db, false);
		$conditions['languageid'] = $params['languages'];
		$conditions['fieldname'] = $params['fields'];
		$where = "WHERE " . $queryBuilder->conditionsToFilter($conditions);
		foreach ($params['search'] as $word)
		{
			if (strlen($word) == 1)
			{
			// searches happen anywhere within a word, so 1 letter searches are useless
				continue;
			}
			$keyword_filters[] = $queryBuilder->conditionsToFilter(array(
					array('field' => 'text', 'value' => $word, 'operator' => vB_dB_Query::OPERATOR_INCLUDES)
			));
		}
		if (!empty($keyword_filters))
		{
			$where .= ' AND ((' . implode(') OR (', $keyword_filters) . '))';
		}
		$sql = '
		SELECT fieldname, varname FROM ' . TABLE_PREFIX . 'phrase AS phrase
		' . $where . '
		/** searchHelp' . (defined('THIS_SCRIPT') ? '- ' . THIS_SCRIPT : '') . '**/';

		$resultclass = 'vB_dB_' . $this->db_type . '_result';
		$config = vB::getConfig();

		if (isset($config['Misc']['debug_sql']) AND $config['Misc']['debug_sql'])
		{
			echo "sql: $sql<br />\n";
		}

		$result = new $resultclass($db, $sql);
		return $result;
	}

	public function nodeMarkread($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			return !empty($params['nodeid']) AND !empty($params['userid']) AND !empty($params['readtime']);
		}

		if (!is_array($params['nodeid']))
		{
			$params['nodeid'] = array($params['nodeid']);
		}

		$params = vB::getCleaner()->cleanArray($params, [
			'nodeid' => vB_Cleaner::TYPE_ARRAY_UINT,
			'userid' => vB_Cleaner::TYPE_UINT,
			'readtime' => vB_Cleaner::TYPE_UNIXTIME,
		]);

		$values = array();
		foreach($params['nodeid'] AS $nodeid)
		{
			$values[] = "($nodeid, " . $params['userid'] . ", " . $params['readtime'] . ")";
		}

		$sql = "
			INSERT INTO " . TABLE_PREFIX . "noderead (nodeid, userid, readtime)
			VALUES
				" . implode(",\n", $values) . "
			ON DUPLICATE KEY UPDATE readtime = " . $params['readtime'];

		return $this->executeWriteQuery($db, $sql, __FUNCTION__);
	}


	/**
	 * Adds notifications
	 *
	 * @param	array $params
	 * @param	object $db
	 * @param	bool $check_only
	 * @return bool
	 */
	public function addNotifications($params, $db, $check_only = false)
	{
		// cleaner hint array
		$expectedFields = [
			'recipient'       => vB_Cleaner::TYPE_UINT,
			'sender'          => vB_Cleaner::TYPE_UINT,
			'lookupid'        => vB_Cleaner::TYPE_STR,
			'lookupid_hashed' => vB_Cleaner::TYPE_STR,
			'sentbynodeid'    => vB_Cleaner::TYPE_UINT,
			'customdata'      => vB_Cleaner::TYPE_STR,
			'typeid'          => vB_Cleaner::TYPE_UINT,
			'lastsenttime'    => vB_Cleaner::TYPE_UINT,
		];

		if ($check_only)
		{
			if (!is_array($params['notifications']))
			{
				return false;
			}
			foreach ($params['notifications'] AS $row)
			{
				foreach ($expectedFields AS $field => $cleanType)
				{
					// isset doesn't work for NULL values
					if (!array_key_exists($field, $row))
					{
						return false;
					}
				}
			}

			return true;
		}

		$cleaned = [];
		$nullables = [
			'sender',
			'lookupid',
			'lookupid_hashed',
			'sentbynodeid',
			'customdata',
		];

		foreach ($params['notifications'] AS $key => $row)
		{
			$clean = vB::getCleaner()->cleanArray($row, $expectedFields);
			foreach ($clean AS $field => $value)
			{
				// Because of the 'NULL' handling below, we need to pre-quote the strings.
				// It's probably safest to just assume anything not UINT should be cleaned, in case we add
				// other types of columns later.
				if ($expectedFields[$field] != vB_Cleaner::TYPE_UINT)
				{
					$clean[$field] = "'" . $db->escape_string($value) . "'";
				}
			}
			foreach ($nullables AS $field)
			{
				if (is_null($row[$field]))
				{
					// We should think of a better way to do this... but in the insert SQL string generation below,
					// this has to be string 'NULL' in order to be inserted like (1, 2, NULL, NULL, '', ...)
					$clean[$field] = 'NULL';
				}
			}
			$cleaned[] = $clean;
		}
		unset($params);

		if (empty($cleaned))
		{
			return false;
		}

		$sql = "
INSERT INTO " . TABLE_PREFIX . "notification
(recipient, sender, lookupid, lookupid_hashed, sentbynodeid, customdata, typeid, lastsenttime)
VALUES
";
		$valuesSql = [];
		foreach ($cleaned AS $row)
		{
			$valuesSql[] = "\n({$row['recipient']}, {$row['sender']}, {$row['lookupid']}, {$row['lookupid_hashed']}, "
							. "{$row['sentbynodeid']}, {$row['customdata']}, {$row['typeid']}, {$row['lastsenttime']})";
		}
		$sql .= implode(",", $valuesSql);

		$sql .= "
\nON DUPLICATE KEY UPDATE
sender = VALUES(sender),
sentbynodeid = VALUES(sentbynodeid),
customdata = VALUES(customdata),
typeid = VALUES(typeid),
lastsenttime = VALUES(lastsenttime)
";

		$this->executeWriteQuery($db, $sql, __FUNCTION__);
		return true;
	}

	public function getClosureChildrenUnbuffered($params, $db, $check_only = false)
	{
		if ($check_only)
		{
			if(empty($params['nodeids']) OR !is_array($params['nodeids']))
			{
				return false;
			}
			else
			{
				return true;
			}
		}

		$params = vB::getCleaner()->cleanArray($params, [
			'nodeids' => vB_Cleaner::TYPE_ARRAY_UINT,
		]);

		// based on query used in vB_Library_Node::fetchClosurechildren().
		$sql = "SELECT cl.parent, cl.child, cl.depth
		FROM " . TABLE_PREFIX . "closure AS cl
		WHERE cl.parent IN (" . implode(',', $params['nodeids']) . ")
		ORDER BY cl.parent ASC, cl.depth DESC";

		// This is a method query solely so we can use unbuffered queries, not because the
		// query itself requires a method or stored logic.
		return $this->getResultSetUnbuffered($db, $sql, __FUNCTION__);
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 116506 $
|| #######################################################################
\*=========================================================================*/
