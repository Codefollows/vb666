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
*	This file is a bucket in which functions common to both the
* install and the upgrade can be located.
*/

if (!defined('VB_AREA') AND !defined('THIS_SCRIPT'))
{
	echo 'VB_AREA or THIS_SCRIPT must be defined to continue';
	exit;
}

// Determine which mysql engines to use
// Will use InnoDB & MEMORY where appropriate, if available, otherwise MyISAM

function get_engine($db, $allow_memory)
{
	$memory = $innodb = false;
	$engines = $db->query('SHOW ENGINES');

	while ($row = $db->fetch_array($engines))
	{
		if ($allow_memory
			AND strtoupper($row['Engine']) == 'MEMORY'
			AND strtoupper($row['Support']) == 'YES')
		{
			$memory = true;
		}

		if (strtoupper($row['Engine']) == 'INNODB'
			AND (strtoupper($row['Support']) == 'YES'
			OR strtoupper($row['Support']) == 'DEFAULT'))
		{
			$innodb = true;
		}
	}

	//prefer innodb to memory type even for "memory" tables. The memory type
	//has locking issues similar to MyISAM and InnoDB will use memory caching
	//anyway for high traffic tables like session
	if ($innodb)
	{ // Otherise try Innodb
		return 'InnoDB';
	}

	if ($memory)
	{ // Return Memory if possible, and allowed
		return 'MEMORY';
	}

	return 'MyISAM'; // Otherwise default to MyISAM.
}

// Choose Engine for Session Tables, MEMORY preferred.
function get_memory_engine($db)
{
	return get_engine($db, true);
}

// Determines which mysql engine to use for high concurrency tables
// Will use InnoDB if its available, otherwise MyISAM
function get_innodb_engine($db)
{
	return get_engine($db, false);
}

function get_default_navbars()
{
	$headernavbar = [
		[
			'title' => 'navbar_home',
			'url' => '/',
			'route_guid' => 'vbulletin-4ecbdacd6a4ad0.58738735',
			'newWindow' => 0,
			'subnav' => [
				[
					'title' => 'navbar_newtopics',
					'url' => 'search?searchJSON=%7B%22view%22%3A%22topic%22%2C%22unread_only%22%3A1%2C%22sort%22%3A%7B%22lastcontent%22%3A%22desc%22%7D%2C%22exclude_type%22%3A%5B%22vBForum_PrivateMessage%22%5D%7D',
					'newWindow' => 0,
					'usergroups' => [2,5,6,7,9,10,11,12,13,14],
				],
				[
					'title' => 'navbar_todays_posts',
					'url' => 'search?searchJSON=%7B%22last%22%3A%7B%22from%22%3A%22lastDay%22%7D%2C%22view%22%3A%22topic%22%2C%22starter_only%22%3A+1%2C%22sort%22%3A%7B%22lastcontent%22%3A%22desc%22%7D%2C%22exclude_type%22%3A%5B%22vBForum_PrivateMessage%22%5D%7D',
					'newWindow' => 0,
					'usergroups' => [1],
				],
				[
					'title' => 'navbar_whos_online',
					'url' => 'online',
					'route_guid' => 'vbulletin-4ecbdacd6a8725.49820977',
					'newWindow' => 0,
					'usergroups' => [2,5,6,7,9,10,11,12,13,14],
				],
				[
					'title' => 'navbar_member_list',
					'url' => 'memberlist',
					'route_guid' => 'vbulletin-4ecbdacd6a8725.49820978',
					'newWindow' => 0,
					'usergroups' => 0,
				],
				[
					'title' => 'navbar_calendar',
					'url' => 'calendar',
					'route_guid' => 'vbulletin-route-calendar-58af7c31d90530.47875165',
					'newWindow' => 0,
				],
			]
		],
		[
			'title' => 'navbar_blogs',
			'url' => 'blogs',
			'route_guid' => 'vbulletin-4ecbdacd6aac05.50909926',
			'newWindow' => 0,
			'subnav' => [
				[
					'title' => 'navbar_create_a_new_blog',
					'url' => 'blogadmin/create/settings',
					'newWindow' => 0,
					'usergroups' => [2,5,6,7,9,10,11,12,13,14],
				],
				[
					'title' => 'navbar_newentries',
					'url' => 'search?searchJSON=%7B%22date%22%3A%22lastVisit%22%2C%22view%22%3A%22topic%22%2C%22unread_only%22%3A1%2C%22sort%22%3A%7B%22lastcontent%22%3A%22desc%22%7D%2C%22exclude_type%22%3A%5B%22vBForum_PrivateMessage%22%5D%2C%22channel%22%3A%5B%225%22%5D%7D',
					'newWindow' => 0,
				],
			]
		],
		[
			'title' => 'navbar_articles',
			'url' => 'articles',
			'route_guid' => 'vbulletin-r-cmshome5229f999bcb705.52472433',
			'newWindow' => 0,
		],
		[
			'title' => 'navbar_social_groups',
			'url' => 'social-groups',
			'route_guid' => 'vbulletin-4ecbdac93742a5.43676037',
			'newWindow' => 0,
			'subnav' => [
				[
					'title' => 'navbar_create_a_new_group',
					'url' => 'sgadmin/create/settings',
					'newWindow' => 0,
					'usergroups' => [2,5,6,7,9,10,11,12,13,14]
				],
			]
		],
	];

	$footernavbar = [
		[
			'title' => 'navbar_help',
			'url' => 'help',
			'route_guid' => 'vbulletin-4ecbdacd6a6f13.66635714',
			'newWindow' => 0,
			'attr' => 'rel="nofollow"',
		],
		[
			'title' => 'navbar_contact_us',
			'url' => 'contact-us',
			'route_guid' => 'vbulletin-4ecbdacd6a6f13.66635713',
			'newWindow' => 0,
			'attr' => 'rel="nofollow"',
		],
		[
			'title' => 'navbar_privacy',
			'url' => 'privacy',
			'route_guid' => 'vbulletin-route-privacy-25c722b99d29ac.6b08da87',
			'newWindow' => 0,
		],
		[
			'title' => 'navbar_terms_of_service',
			'url' => 'terms-of-service',
			'route_guid' => 'vbulletin-route-tos-632bbd31cdee46.28098868',
			'newWindow' => 0,
		],
		[
			'title' => 'navbar_admin',
			'url' => 'admincp',
			// the admin route doesn't work like other routes, so we can't associate this properly atm.
			//'route_guid' => 'vbulletin-4ecbdacd6aa7c8.79724467',
			'newWindow' => 0,
			'usergroups' => [6],
		],
		[
			'title' => 'navbar_mod',
			'url' => 'modcp/',
			//'route_guid' => 'vbulletin-4ecbdacd6aa7c8.79724488',
			'newWindow' => 0,
			'usergroups' => [6,7,5],
		],
	];

	return ['header' => $headernavbar, 'footer' => $footernavbar];
}


/**
 *	Adds a user in the install
 *	Avoids using main system components that might not work without having a user
 */
function install_add_user($userid, $username, $title, $email, $admincp_useroption, $adminpermission, $permissions, $permissions2)
{
	//refactored from class_upgrade_install.  Should look into using library classes here, but could
	//run into problems with that.

	$db = vB::getDBAssertor();

	$data = [
		'userid' => $userid,
		'username' => $username,
		'displayname' => unhtmlspecialchars($username),
		'usertitle' => $title,
		'email' => $email,
		'joindate' => TIMENOW,
		'lastvisit' => TIMENOW,
		'lastactivity' => TIMENOW,
		'usergroupid' => 6,
		'options' => $admincp_useroption,
		'showvbcode' => 2,
		'membergroupids' => '',
		'secret' => vB_Library::instance('user')->generateUserSecret(),
		'location' => 'UNKNOWN'
	];

	$db->insert('user', $data);

	$data = ['userid' => $userid];
	$db->insert('vBForum:usertextfield', $data);
	$db->insert('vBForum:userfield', $data);

	$data = [
		'userid' => $userid,
		'adminpermissions' => $adminpermission,
	];
	$db->insert('vBForum:administrator', $data);

	$data = [
		'userid' => $userid,
		'nodeid' => 0,
		'permissions' => $permissions,
		'permissions2' => $permissions2
	];
	$db->insert('vBForum:moderator', $data);
}

//remove all of the records we add in install_add_user
//we need to do this if we hit an error after we create the user.
function install_delete_user($userid)
{
	$db = vB::getDBAssertor();

	$data = ['userid' => $userid];
	$db->delete('vBForum:moderator', $data);
	$db->delete('vBForum:administrator', $data);
	$db->delete('vBForum:usertextfield', $data);
	$db->delete('vBForum:userfield', $data);
	$db->delete('user', $data);
}

//make this a function so we don't need to worry about namespacing.  If something
//really needs to be global we can pull it out and pass it back in.
function getAttachmenttypeInsertQuery($db, $types = [])
{
	//It does not appear that the contenttype field these feed is used any longer.
	// Attachment types
	$contenttype_post = array(
		1 => array(	// 1 signifies vBulletin Post as the contenttype
			'n' => 0,	// Open New Window on Click
			'e' => 1, // Enabled
		)
	);

	$contenttype_album_enabled = array(
		7 => array(	// 7 signifies vBulletin SocialGroup as the contenttype
			'n' => 0,	// Open New Window on Click
			'e' => 1, // Enabled
		)
	);

	$contenttype_album_disabled = array(
		7 => array(	// 2 signifies vBulletin SocialGroup as the contenttype
			'n' => 0,	// Open New Window on Click
			'e' => 0, // Enabled
		)
	);

	$contenttype_group_enabled = array(
		8 => array(	// 8 signifies vBulletin Album as the contenttype
			'n' => 0,	// Open New Window on Click
			'e' => 1, // Enabled
		)
	);

	$contenttype_group_disabled = array(
		8 => array(	// 2 signifies vBulletin Album as the contenttype
			'n' => 0,	// Open New Window on Click
			'e' => 0, // Enabled
		)
	);

	$attachments_images = [
		'gif' => [
			'mimetype' => 'Content-type: image/gif',
		],

		'jpeg' => [
			'mimetype' => 'Content-type: image/jpeg',
		],

		'jpg' => [
			'mimetype' => 'Content-type: image/jpeg',
		],

		'jpe' => [
			'mimetype' => 'Content-type: image/jpeg',
		],

		'png' => [
			'mimetype' => 'Content-type: image/png',
		],

		'webp' => [
			'mimetype' => 'Content-type: image/webp',
		],
	];

	$attachments_files = [
		'txt' => [
			'mimetype' => 'Content-type: text/plain',
			'display' => '2',
		],

		'doc' => [
			'mimetype' => 'Content-type: application/msword',
		],

		'docx' => [
			'mimetype' => 'Content-type: application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		],

		'pdf' => [
			'mimetype' => 'Content-type: application/pdf',
		],

		'psd' => [
			'mimetype' => 'Content-type: image/vnd.adobe.photoshop',
		],

		'zip' => [
			'mimetype' => 'Content-type: application/zip',
		],

		'mp3' => [
			'mimetype' => 'Content-type: audio/mp3',
			'size' => 2000000,
		],

		'mp4' => [
			'mimetype' => 'Content-type: video/mp4',
			'size' => 4000000,
		],

		'mov' => [
			'mimetype' => 'Content-type: video/quicktime',
			'size' => 4000000,
		],
	];

	if ($types)
	{
		$flippedTypes = array_flip($types);
		$attachments_images = array_intersect_key($attachments_images, $flippedTypes);
		$attachments_files = array_intersect_key($attachments_files, $flippedTypes);
	}

	$rows = [];
	foreach ($attachments_images AS $extension => $attachment)
	{
		$contenttype = $db->escape_string(serialize(($contenttype_post + $contenttype_album_enabled + $contenttype_group_enabled)));

		$size = $attachment['size'] ?? 900000;
		$mimetype = $db->escape_string(serialize([$attachment['mimetype']]));
		$rows[] = "('" . $extension . "', '" . $mimetype . "', '" . $size . "', '1440', '900', '0', '" . $contenttype . "')";
	}

	foreach ($attachments_files AS $extension => $attachment)
	{
		$contenttype = $db->escape_string(serialize(($contenttype_post + $contenttype_album_disabled + $contenttype_group_disabled)));
		$display = $attachment['display'] ?? 0;

		$size = $attachment['size'] ?? 900000;
		$mimetype = $db->escape_string(serialize([$attachment['mimetype']]));
		$rows[] = "('" . $extension . "', '" .  $mimetype . "', '" . $size . "', '0', '0', '" . $display . "', '" . $contenttype . "')";
	}

	//conttenttypes doesn't appear to be used any more (it's referenced but ultimately just to pass around values that are ultimately
	//ignored.  I'm not clear if display ever was -- however searching for display turns up a lot of noise.
	//Neither can be set from the admincp (display isn't even refenced there).
	$query =  "INSERT INTO " . TABLE_PREFIX . "attachmenttype (extension, mimetype, size, width, height, display, contenttypes) " .
		"VALUES " . implode(",\n", $rows);

	return $query;
}

function get_default_reputationranks() : array
{
	$default = [
		// 1 ~ 6
		[
			'reputation' => 10,
			'title' => 'is on a distinguished road',
		],
		[
			'reputation' => 50,
			'title' => 'will become famous soon enough',
		],
		[
			'reputation' => 150,
			'title' => 'has a spectacular aura about',
		],

		[
			'reputation' => 250,
			'title' => 'is a jewel in the rough',
		],
		[
			'reputation' => 350,
			'title' => 'is just really nice',
		],
		[
			'reputation' => 450,
			'title' => 'is a glorious beacon of light',
		],
		// 7 ~ 11
		[
			'reputation' => 550,
			'title' => 'is a name known to all',
		],
		[
			'reputation' => 650,
			'title' => 'is a splendid one to behold',
		],
		[
			'reputation' => 1000,
			'title' => 'has much to be proud of',
		],

		[
			'reputation' => 1500,
			'title' => 'has a brilliant future',
		],
		[
			'reputation' => 2000,
			'title' => 'has a reputation beyond repute',
		],
	];
	$i = 1;
	$return = [];
	foreach ($default AS $__data)
	{
		// `grouping`,priority,minposts,startedtopics,registrationtime,reputation,totallikes,ranklevel,rankimg,usergroupid,`type`,stack,display
		$thisreturn = [];
		$thisreturn['grouping'] = 'Reputation';
		$thisreturn['priority'] = $i;
		$thisreturn['minposts'] = 0;
		$thisreturn['startedtopics'] = 0;
		$thisreturn['registrationtime'] = 0;
		$thisreturn['reputation'] = $__data['reputation'];
		$thisreturn['totallikes'] = 0;
		// Note, this is intentional. It's meant to go from 1-6 regular stars, then 1-6 solid stars
		$thisreturn['ranklevel'] = (($i - 1) % 6) + 1;
		$startype = ($i <= 6) ? 'fa-regular' : 'fa-solid';
		$thisreturn['rankimg'] = '<i class="' . $startype . ' fa-star" title="' . htmlspecialchars($__data['title']) . '"></i>';
		$thisreturn['usergroupid'] = 0;
		// 0: local (core/) filesystem image, 1: raw HTML 2: remote URL image
		$thisreturn['type'] = 1;
		$thisreturn['stack'] = 1;
		// 0: Always, 1: Displaygroup == This Group
		$thisreturn['display'] = 0;

		$return[] = $thisreturn;

		$i++;
	}

	return $return;
}

function get_default_reputationranks__values($db) : array
{
	$ranks = get_default_reputationranks();
	// $columns = [
	// 	'grouping',
	// 	'priority',
	// 	'minposts',
	// 	'startedtopics',
	// 	'registrationtime',
	// 	'reputation',
	// 	'totallikes',
	// 	'ranklevel',
	// 	'rankimg',
	// 	'usergroupid',
	// 	'type',
	// 	'stack',
	// 	'display',
	// ];
	return convert_data_array_to_insert_sql($db, $ranks);
}

function convert_data_array_to_insert_sql($db, $data) : array
{
	$sqls = [];
	$columns = [];
	$first = reset($data);
	foreach ($first AS $__column => $__unused)
	{
		$columns[$__column] = $db->clean_identifier($__column);
	}

	foreach ($data AS $__rank)
	{
		$__values = [];
		foreach ($columns AS $__key => $__cleanedcolumn)
		{
			$__values[] =  $db->escape_string($__rank[$__key]);
		}
		$sqls[] = '(\'' . implode("','", $__values) . '\')';
	}

	$sql = implode(",\n\t", $sqls);
	$columns = '`' . implode('`,`', $columns) . '`';

	return [
		'columns' => $columns,
		'values' => $sql,
	];
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 116084 $
|| #######################################################################
\*=========================================================================*/
