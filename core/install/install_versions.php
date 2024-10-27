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
 *	@package vBInstall
 */

/*
 *	Holds the various minimum versions for vbulletin.  Versions below these will
 *	cause the installer to fail.
 *
 *	If you change these to install vBulletin on an unsupported version, vBulletin
 *	may not work properly or may not work at all.
 *
 *	This file should work under the version listed as install_php_version and later.
 */
$install_versions = [
	'install_php_version' => '7.1.0',
	'php_required' => '8.1.0',
	'mysql_required' => '5.7.9',
	'mariadb_required' => '10.4.0',
];

$install_version_error = 'The vBulletin installer requires at leaset PHP %1$s to run.  vBulletin requires at least PHP %2$s.';

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 115779 $
|| #######################################################################
\*=========================================================================*/
