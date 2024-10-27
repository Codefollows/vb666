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
 * Options for final upgrade step 15 where themes are imported
 *
 * Parameters:
 *	bool	'overwrite'		Default true. Set it to false to skip importing any existing themes.
 */
$upgrade_options['theme_import'] = [
	'overwrite' => true,
];


/*
 * Options for final upgrade step 20 which attempts to three-way-merge the old default,
 * new default, & custom templates.
 *
 * Parameters:
 *	bool	'skip_themes'	Default true.	Skip trying to merge theme templates with the default templates.
 *	int		'time_limit'	Default 4, mininum 1. Seconds allowed to elapse before breaking the merge
 *							process and moving onto the next iteration of this step. Note that
 *							if the very last merge takes a long time, the step might go past this limit.
 *							If that is causing the step to time out prematurely, try setting the
 *							'batch_size' below.
 *	int		'batch_size'	Default 100, mininum 1. Number of templates we should attemp to merge per
 *							iteration. Try setting this to a small value if the 'time_limit' above does not help
 *							resolve timeout issues.
 */
$upgrade_options['template_merge'] = [
	'skip_themes' => true,
	'time_limit' => 4,
	'batch_size' => 100,
];


/*
 *	The optimal batch size for various upgrade steps can depend considerably on the environment where
 *	the site is hosted.  Larger batch sizes reduce the number of round trips to the server, but also
 *	increases the amount of time each step takesto process which can lead to timeouts.  This allows
 *	customizing the batch sizes	in various ways to adapt the upgrade to your environment.
 */
$batch_options = [
	//this will increase or decrease the default batch sizes.  A value of 0.5 will halve
	//all batch sizes, a value of 2 would double them.
	'masterslider' => 1.0,

	//These are the standard batch sizes.  Changing these values will change the value
	//for all steps that use that particular bucket.
	'sizes' => [
		'tiny' => 200,
		'xxxsmall' => 500,
		'xxsmall' => 1000,
		'xsmall' => 2000,
		'small' => 5000,
		'medium' => 10000,
		'large' => 20000,
		'xlarge' => 40000,
	],

	//This will set a batch size for an indiviudal step. This will override the value
	//it would normally take from the "sizes" array but will still be affected by the
	//masterslider setting.
	//
	//The key for this array is "shortversion:step_function" for instance "564a2:step_1"
	//for step 1 of the 5.6.4 alpha 2 upgrade.
	'steps' => [
		'602a1:step_1' => 1,
	]
];
/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 114143 $
|| #######################################################################
\*=========================================================================*/
