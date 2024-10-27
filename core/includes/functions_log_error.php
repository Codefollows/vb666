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
* Log errors to a file
*
* @param	string	The error message to be placed within the log
* @param	string	The type of error that occured. php, database, security, etc.
*
* @return	boolean
*/
function log_vbulletin_error($errstring, $type = 'database', $extra = [])
{
	$logmaxsize = 1048576;
	//if for some reason we can't get the datastore (for example the
	//database is borked) we don't know what to do.  But having
	//the error log function throw errors isn't going to help.
	try
	{
		$options = vB::getDatastore()->getValue('options');
		$logmaxsize = $options['errorlogmaxsize'] ?? $logmaxsize;
	}
	catch(Exception $e)
	{
		// If it's a database error, the option may not be available, but we
		// still want to log this. We've moving the logfile location from
		// options to config to allow this, so let's continue.
		// Fetching config should not throw an exception
		// (though it may die() if the config file isn't found)
		if ($type != 'database')
		{
			return false;
		}
	}

	$config = vB::getConfig();
	// Smooth over the fact that options may not be present when there's a database failure.
	// If config is set, always override. Otherwise, use the option (if available), or else default to
	// the option default.
	if ($type == 'database')
	{
		$logmaxsize = $config['Database']['errorlogmaxsize'] ?? $options['errorlogmaxsize'] ?? $logmaxsize;
	}

	// do different things depending on the error log type
	switch($type)
	{
		// log PHP E_USER_ERROR, E_USER_WARNING, E_WARNING to file
		case 'php':
			if (!empty($options['errorlogphp']))
			{
				$session = vB::getCurrentSession();
				if ($session)
				{
					$username = $session->fetch_userinfo();
					$username = $username['username'];
				}
				else
				{
					$username = 'unknown';
				}

				$request = vB::getRequest();
				if($request)
				{
					$ip = $request->getIpAddress();
				}
				else
				{
					$ip = '';
				}

				$errfile = $options['errorlogphp'];
				$errstring .= "\r\nDate: " . date('l dS \o\f F Y h:i:s A') . "\r\n";
				$errstring .= "Username: $username\r\n";
				$errstring .= 'IP Address: ' . $ip . "\r\n";
			}
			break;

		// log database error to file
		case 'database':
			if (!empty($config['Database']['logfile']))
			{
				$errstring = preg_replace("#(\r\n|\r|\n)#s", "\r\n", $errstring);
				// TODO: should we smooth over if config includes the ".log" extension?
				// For now keeping the .log appending consistent with old options behavior/
				// other logfile options.
				$errfile = $config['Database']['logfile'];
			}
			break;

		// log admin panel login failure to file
		case 'security':
			if (!empty($options['errorlogsecurity']))
			{
				$request = vB::getRequest();
				if($request)
				{
					$server = $request->getVbHttpHost();
					$path = $request->getScriptPath();

					$script = "http://$server" . unhtmlspecialchars($path);
					$referrer = $request->getReferrer();
					$ip = $request->getIpAddress();
				}
				else
				{
					$script = '';
					$referrer = '';
					$ip = '';
				}

				$strikes = $extra['strikes'] ?? 'unknown';
				$errfile = $options['errorlogsecurity'];
				$username = $errstring;
				$errstring  = 'Failed admin logon in vBulletin ' . $options['templateversion'] . "\r\n\r\n";
				$errstring .= 'Date: ' . date('l dS \o\f F Y h:i:s A') . "\r\n";
				$errstring .= "Script: $script\r\n";
				$errstring .= 'Referer: ' . $referrer . "\r\n";
				$errstring .= "Username: $username\r\n";
				$errstring .= 'IP Address: ' . $ip . "\r\n";
				$errstring .= 'Strikes: ' . $strikes . "\r\n";
			}
			break;
	}

	// if no filename is specified, exit this function
	if (!isset($errfile) OR !($errfile = trim($errfile)) OR (defined('DEMO_MODE') AND DEMO_MODE == true))
	{
		return false;
	}

	// rotate the log file if filesize is greater than $options[errorlogmaxsize]
	if (
		$logmaxsize != 0 AND
		$filesize = @filesize("$errfile.log") AND
		$filesize >= $logmaxsize
	)
	{
		//don't assume that everything is working properly.
		$request = vB::getRequest();
		if($request)
		{
			$time = $request->getTimeNow();
		}
		else
		{
			$time = time();
		}

		@copy("$errfile.log", $errfile . $time . '.log');
		@unlink("$errfile.log");
	}

	// write the log into the appropriate file
	if ($fp = @fopen("$errfile.log", 'a+'))
	{
		@fwrite($fp, "$errstring\r\n=====================================================\r\n\r\n");
		@fclose($fp);
		return true;
	}
	else
	{
		return false;
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 113752 $
|| #######################################################################
\*=========================================================================*/
