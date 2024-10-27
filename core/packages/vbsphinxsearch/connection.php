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

/*
 *	Sphinx uses the mysql libraries to connect to the sphinx daemon.
 *	The initial implementation piggybacked on the existing mysql classes
 *	which worked after a fashion, but starts causing problems because while
 *	the mysql libraries work, sphinx doesn't accept the same commands
 *	as mysql does so if we attempt to query mysql as part of the intialization
 *	or otherwise assume that we are connected to a mysql backend it can
 *	break sphinx.  We really need a seperate class to handle sphinx.
 *
 *	However, at this point we need to be risk adverse so we'll compromise.
 *	We'll create the class, but extend the existing class and override
 *	the bits that are currently failing.  This should not be considered
 *	a long term solution.  The goal is to remove the "extends" below,
 *	not to make this part of a real class hierarchy.
 */
class vBSphinxSearch_Connection extends vB_Database_MySQLi
{
	private $hadSyntaxError = false;
	private $suppressSyntaxErrors = false;

	protected function set_charset($charset, $link)
	{
		//do nothing.  We don't set the charset for sphinx and the implementation
		//for mysql simply doesn't work (fatal error).
	}

	protected function &execute_query($buffered, &$link)
	{
		// reset report flag(s) before each query exec.
		$this->hadSyntaxError = false;

		$queryresult = parent::execute_query($buffered, $link);

		// consume command flag(s) after each query exec
		$this->suppressSyntaxErrors = false;

		return $queryresult;
	}

	protected function halt($errortext = '')
	{
		if ($this->suppressSyntaxErrors AND strpos($this->error, 'syntax error, unexpected') !== false)
		{
			$this->hadSyntaxError = true;
			return;
		}
		else
		{
			// halt may throw an exception, which means we won't hit the bottom of execute_query()
			$this->suppressSyntaxErrors = false;

			/*
			We can't call the parent halt for the suppressed case, because the generation of the
			exception will do a lot of things including sending the email, which we want to suppress
			until the retry fails.
			Furthermore, halt() can be called only once "statically", so if we call it once, then the
			second retry fails again and tries to call it again, error messages will not display
			when they're supposed to.
			 */
			return parent::halt();

		}
	}

	public function suppressSyntaxErrorsNextQuery($supress = true)
	{
		$this->suppressSyntaxErrors = $supress;
	}

	public function lastQueryHadSyntaxError()
	{
		return $this->hadSyntaxError;
	}
}
/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 107039 $
|| #######################################################################
\*=========================================================================*/
