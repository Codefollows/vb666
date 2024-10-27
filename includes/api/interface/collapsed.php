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

class Api_Interface_Collapsed extends Api_InterfaceAbstract
{
	protected $initialized = false;

	public function init()
	{
		if ($this->initialized)
		{
			return true;
		}

		//initialize core
		$config = vB5_Config::instance();

		//if this is AJAX, let's avoid showing warnings (notices etc)
		//nothing good will come of it.
		if (
			!$config->report_all_ajax_errors AND
			isset($_SERVER['HTTP_X_REQUESTED_WITH']) AND
			$_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'
		)
		{
			vB::silentWarnings();
		}

		$request = new vB_Request_WebApi();
		vB::setRequest($request);

		//We normally don't allow the use of the backend classes in the front end, but the
		//rules are relaxed inside the api class and especially in the bootstrap dance of getting
		//things set up.  Right now getting at the options in the front end is nasty, but I don't
		//want the backend dealing with cookies if I can help it (among other things it makes
		//it nasty to handle callers of the backend that don't have cookies).  But we need
		//so information to determine what the cookie name is.  This is the least bad way
		//of handling things.
		$options = vB::getDatastore()->getValue('options');
		vB5_Cookie::loadConfig($options);
		$this->setPhpSessionIni($options);

		// When we reach here, there's no user information loaded. What we can do is trying to load language from cookies.
		// Shouldn't use vB5_User::getLanguageId() as it will try to load userinfo from session
		$languageid = vB5_Cookie::get('languageid', vB5_Cookie::TYPE_UINT);
		if ($languageid)
		{
			$request->setLanguageid($languageid);
		}

		$session = $this->createSession($request, $options);

		// Update lastvisit/lastactivity
		$info = $session->doLastVisitUpdate(
			vB5_Cookie::get('lastvisit', vB5_Cookie::TYPE_UINT),
			vB5_Cookie::get('lastactivity', vB5_Cookie::TYPE_UINT)
		);

		if (!empty($info))
		{
			//these cookies don't make any sense unless they are stored.
			$duration = 365;

			// for guests we need to set some cookies
			if (isset($info['lastvisit']))
			{
				vB5_Cookie::set('lastvisit', $info['lastvisit'], $duration);
			}

			if (isset($info['lastactivity']))
			{
				vB5_Cookie::set('lastactivity', $info['lastactivity'], $duration);
			}
		}

		$this->initialized = true;
	}

	public function callApi($controller, $method, array $arguments = [], $useNamedParams = false, $byTemplate = false)
	{
		try
		{
			$c = vB_Api::instance($controller);
		}
		catch (vB_Exception_Api $e)
		{
			throw new vB5_Exception_Api($controller, $method, $arguments, ['Failed to create API controller.']);
		}

		if ($useNamedParams)
		{
			$result = $c->callNamed($method, $arguments);
		}
		else
		{
			$result = call_user_func_array([&$c, $method], array_values($arguments));
		}

		// The core error handler has been rewritten and can be used here (by default)
		// The api call sets error/exception handlers appropriate to core. We need to reset.
		// But if the API is called by template ({vb:data}), we should use the core exception handler.
		// Otherwise we will have endless loop. See VBV-1682.
		if (!$byTemplate)
		{
			set_exception_handler(['vB5_ApplicationAbstract', 'handleException']);
		}
		return $result;
	}


	public static function callApiStatic($controller, $method, array $arguments = [])
	{
		if (is_callable('vB_Api_'  . $controller, $method))
		{
			return call_user_func_array(['vB_Api_'  . $controller, $method], array_values($arguments));
		}
		throw new vB5_Exception_Api($controller, $method, $arguments, 'invalid_request');
	}


	public function relay($file)
	{
		$corepath = vB5_ApplicationAbstract::instance()->getCorePath();

		$filePath = $corepath . '/' . $file;
		if ($file AND file_exists($filePath))
		{
			$core = realpath($corepath);
			$filePath = realpath($filePath);

			//we don't want to include anything that isn't in the core directory
			if(strpos($filePath, $core) === 0)
			{
				//hack because the admincp/modcp files won't return so the remaining processing in
				//index.php won't take place.  If we better integrate the admincp into the
				//frontend, we can (and should) remove this.
				vB_Shutdown::instance()->add(['vB5_Frontend_ExplainQueries', 'finish']);
				try
				{
					require_once($filePath);
				}
				catch (vB_Exception_404 $e)
				{
					throw new vB5_Exception_404($e->getMessage());
				}
				return;
			}
		}

		throw new vB5_Exception_404('invalid_page_url');
	}

	/*
	 *	Play nice and handle backend communication through the api class even though noncollapsed
	 *	mode is completely dead.  These are systems that don't really belong as part of the API, but
	 *	we really don't want to implement seperately for frontend/backend use.  By indirecting through
	 *	this class we maintain our goal of keeping the front end reasonable separate (hopefully ensuring
	 *	that backend functionality stands on its own for integration/extension purposes).
	 */
	public function cacheInstance($type)
	{
		return vB_Cache::instance($type);
	}

	public function stringInstance()
	{
		return vB::getString();
	}

	public function urlInstance()
	{
		return vB::getUrlLoader();
	}

	protected function setPhpSessionIni($options)
	{
		$secure = (stripos($options['frontendurl'], 'https:') !== false);

		//if the headers are sent then this isn't going to do anybody any good
		if(!headers_sent())
		{
			//I have no idea why these aren't the default.  It's pretty much
			//make my site more secure yes/no
			ini_set('session.use_strict_mode', true);
			ini_set('session.cookie_httponly', true);

			ini_set('session.cookie_samesite', 'Strict');
			ini_set('session.cookie_secure', $secure);

			//this is off by default, but let's make sure
			ini_set('session.session.use_trans_sid', false);

			//following current guidance on these, but let's nto make it less
			//secure if the user has configured with more.
			if(ini_get('session.sid_length') < 48)
			{
				ini_set('session.sid_length', 48);
			}

			if(ini_get('session.sid_bits_per_character ') < 5)
			{
				ini_set('session.sid_bits_per_character ', 5);
			}
		}
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 111848 $
|| #######################################################################
\*=========================================================================*/
