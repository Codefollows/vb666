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

class vB5_Frontend_Controller
{
	/** vboptions **/
	protected $vboptions = [];

	function __construct()
	{
		$vboptions = vB5_Template_Options::instance()->getOptions();
		$this->vboptions = $vboptions['options'];
	}

	/**
	 * Sends the response as a JSON encoded string
	 *
	 * @param	mixed	The data (usually an array) to send
	 */
	//this probably should be protected rather than public
	public function sendAsJson($data)
	{
		//this really isn't appropriate for this function but we need to
		//track down how to move this to the caller functions.  We should
		//*not* be altering data inside the send function
		if (isset($data['template']) AND !empty($data['template']))
		{
			$data['template'] = $this->outputPage($data['template'], false);
		}

		vB5_ApplicationAbstract::instance()->sendAsJson($data);
	}

	/*
	 * Cover functions for the application class message functions
	 * They aren't strictly needed but they present a cleaner interface to the controller classes
	 * than directly accessing the vB5_ApplicationAbstract statically.
	 *
	 * We can't simply move them here because they are used internally to the application class.
	 */

	/**
	 * Show a simple and clear message page which contains no widget
	 *
	 * @param string $title Page title. HTML will be escaped.
	 * @param string $message Message to display. HTML is allowed and the caller must make sure it's valid.
	 * @param string $state The state of the site
	 */
	protected function showMessagePage($title, $message, $status = '')
	{
		vB5_ApplicationAbstract::showMsgPage($title, $message, $status);
	}

	/**
	 * Show a simple and clear message page which contains no widget
	 *
	 * Same as showMsgPage without the headers/footers.  Intended for use in popup windows.
	 *
	 * @param string $title Page title. HTML will be escaped.
	 * @param string $message Message to display. HTML is allowed and the caller must make sure it's valid.
	 * @param string $state The state of the site
	 */
	protected function showMessagePageBare($title, $message, $status = '')
	{
		vB5_ApplicationAbstract::showMsgPageBare($title, $message, $status);
	}

	/**
	 * Show an error message
	 *
	 * The main purpose of this function is to convert a standard error array to the
	 * main application error page function.
	 *
	 * @param $errors -- an error array such as gets returned from the API.  Currently only
	 * 	the first error is displayed but this may change in the future.
	 */
	protected function showErrorPage($errors)
	{
		//the base function only handles one error so we'll go with the first one
		$newErrors = [
			'message' => $errors[0],
		];

		//the show error page function doesn't handle the exception_trace
		//quite as expected so we'll fish it out of the array and reformat it if exists
/*
 		//actually the formats are fundamentially incompatible.  We should fix this but
		//it's not worth it at the moment.
		foreach($errors AS $error)
		{
			if ($error[0] == 'exception_trace')
			{
				$newErrors['trace'] = explode("\n", $error[1]);
			}
		}
*/
		vB5_ApplicationAbstract::showErrorPage($newErrors);
	}


	protected function showErrorPageBare($errors)
	{
		$template = new vB5_Template('error_page_bare');
		$template->register('error', ['message' => $errors[0]]);

		echo vB5_ApplicationAbstract::getPreheader() . $template->render();
	}

	/**
	 * Handle errors that are returned by API for use in JSON AJAX responses.
	 *
	 * @param	mixed	The result array to populate errors into. It will contain error phrase ids.
	 * @param	mixed	The returned object by the API call.
	 *
	 * @return	boolean	true errors are found, false, otherwise.
	 * @deprecated
	 */
	//This probably dates back to a time when the JS expectations for error returns were all over the map
	//(a problem not entirely fixed).  It attempts to normalize the error return but the detection/transform
	//by type is inherently ambigious so it may not be successful.  Worse the value that is returned is
	//*not* the standard error format (though it will be accepted by the standard Ajax error handler).
	//The correct approach is to fix the API to return the standard format if it isn't and then return
	//the API error return verbatim unless it's being specifically handled.
	//However we need to test the places that use this before removing it.
	protected function handleErrorsForAjax(&$result, $return)
	{
		if ($return AND !empty($return['errors']))
		{
			if (isset($return['errors'][0][1]))
			{
				// it is a phraseid with variables
				$errorList = [$return['errors'][0]];
			}
			else
			{
				$errorList = [$return['errors'][0][0]];
			}

			if (!empty($result['error']))
			{
				//merge and remove duplicate error ids
				$errorList = array_merge($errorList, $result['error']);
				$errorList = array_unique($errorList);
			}

			$result['error'] = $errorList;
			return true;
		}
		return false;
	}

	/**
	 * Checks if this is a POST request
	 */
	protected function verifyPostRequest()
	{
		// Require a POST request for certain controller methods
		// to avoid CSRF issues. See VBV-15018 for more details.
		if (strtoupper($_SERVER['REQUEST_METHOD']) != 'POST')
		{
			// show exception and stack trace in debug mode
			throw new Exception('This action only available via POST');
		}

		// Also verify CSRF token.
		vB5_ApplicationAbstract::checkCSRF();

	}

	/**
	 * Any final processing, and then output the page
	 */
	protected function outputPage($html, $exit = true)
	{
		$styleid = vB5_Template_Stylevar::instance()->getPreferredStyleId();

		if (!$styleid)
		{
			$styleid = $this->vboptions['styleid'];
		}

		$api = Api_InterfaceAbstract::instance();
		$fullPage = $api->callApi('template', 'processReplacementVars', array($html, $styleid));

		if (vB5_Config::instance()->debug)
		{
			$fullPage = str_replace('<!-- VB-DEBUG-PAGE-TIME-PLACEHOLDER -->', round(microtime(true) - VB_REQUEST_START_TIME, 4), $fullPage);
		}

		$api->invokeHook('hookFrontendBeforeOutput', array('styleid' => $styleid, 'pageHtml' => &$fullPage));

		if ($exit)
		{
			echo $fullPage;
			exit;
		}

		return $fullPage;
	}

	protected function parseBbCodeForPreview($rawText, $options = array())
	{
		$results = array();

		if (empty($rawText))
		{
			$results['parsedText'] = $rawText;
			return $results;
		}

		// parse bbcode in text
		try
		{
			$results['parsedText'] = vB5_Frontend_Controller_Bbcode::parseWysiwygForPreview($rawText, $options);
		}
		catch (Exception $e)
		{
			$results['error'] = 'error_parsing_bbcode_for_preview';

			if (vB5_Config::instance()->debug)
			{
				$results['error_trace'] = (string) $e;
			}
		}

		return $results;
	}


	/**
	 *	Adds attachment information so attachments can be created in one call
	 *
	 *	This will modify the $data array to add data under the keys
	 *	'attachments' for added attachments & 'removeattachments' for
	 *	attachments requested for removal.
	 *
	 * @param 	mixed	array of node data for insert
	 */
	protected function addAttachments(&$data)
	{
		if (isset($_POST['filedataids']) AND !empty($data['parentid']))
		{
			$api = Api_InterfaceAbstract::instance();
			$availableSettings =  $api->callApi('content_attach', 'getAvailableSettings', array());
			$availableSettings = (isset($availableSettings['settings'])? $availableSettings['settings'] : array());

			$data['attachments'] = array();
			/*
			 *	For inline inserts, the key is the temporary id that will be replaced by the nodeid in
			 *	vB_Library_Content_Text so maintaining the key $k is important.
			 */
			foreach ($_POST['filedataids'] AS $k => $filedataid)
			{
				$filedataid = (int) $filedataid;

				if ($filedataid < 1)
				{
					continue;
				}

				// We only use $availableSettings so we know which values to extract
				// from the $_POST variable. This is not here for cleaning,
				// which happens in the API. See the text and attach API cleanInput
				// methods.
				$settings = array();
				foreach ($availableSettings AS $settingkey)
				{
					if (!empty($_POST['setting'][$k][$settingkey]))
					{
						$settings[$settingkey] = $_POST['setting'][$k][$settingkey];
					}
				}

				$data['attachments'][$k] = array(
					'filedataid' => $filedataid,
					'filename' => (isset($_POST['filenames'][$k]) ? strval($_POST['filenames'][$k]) : ''),
					'settings' => $settings,
				);

			}
		}

		// if it's an update, we might have some attachment removals.
		// Let's also add removeattachments for an update, so the attachment limit
		// checks can take them into account.
		if (!empty($_POST['removeattachnodeids']))
		{
			// This list is used in 2 places.
			// First, it's used for permission checking purposes in vB_Api_Content_Text->checkAttachmentPermissions()
			// Later, it is used to delete attachments after the main node update in vB_Library_Content_Text->update().
			foreach ($_POST['removeattachnodeids'] AS $removeattachnodeid)
			{
				$removeattachnodeid = (int) $removeattachnodeid;
				if ($removeattachnodeid > 0)
				{
					$data['removeattachments'][$removeattachnodeid] = $removeattachnodeid;
				}
			}
		}
	}

	/*
		Copied from vB5_Frontend_ApplicationLight::handleAjaxApiDetached()
	*/
	protected function sendAsJsonAndCloseConnection($data)
	{
		//this really isn't appropriate for this function but we need to
		//track down how to move this to the caller functions.  We should
		//*not* be altering data inside the send function
		if (isset($data['template']) AND !empty($data['template']))
		{
			$data['template'] = $this->outputPage($data['template'], false);
		}

		vB5_ApplicationAbstract::instance()->sendAsJsonAndCloseConnection($data);
	}

	/**
	 * Generates a signed message to pass to the following page, so that the
	 * message can be displayed briefly to the user (flashed).
	 *
	 * @param  string|array The phrase key|array or rendered phrase for the message to display
	 * @return string The signed value that should be passed as a query parameter
	 *                using the format flashmsg=<signed value>
	 */
	protected function encodeFlashMessage($phrase)
	{
		// For an overview of how the flashMessage system works, see:
		// vB5_Frontend_Controller::encodeFlashMessage()
		// vB5_Template::decodeFlashMessage()
		// vB_Api_User::verifyFlashMessageSignature()
		// displayFlashMessage() in global.js

		$api = Api_InterfaceAbstract::instance();
		$userinfo = $api->callApi('user', 'fetchUserinfo');

		$securitytoken = '';
		if (!empty($userinfo['securitytoken']))
		{
			$securitytoken = $userinfo['securitytoken'];
		}

		$timestamp = explode('-', $securitytoken, 2);
		$timestamp = $timestamp[0];

		//handle cases when $phrase is a rendered phrase (also indirectly handles when delimiter "-" is
		// part of the phrase) or phrase array.
		if ($phrase AND !preg_match('#^[a-z0-9_\+/=,]+$#siU', $phrase) OR is_array($phrase))
		{
			$phrase = 'base64,' . base64_encode(json_encode($phrase));
		}

		$ret = 'msg-' . $phrase . '-' . $timestamp . '-' . substr(sha1($phrase . $securitytoken), -10);

		return $ret;
	}


	// handle exceptions in controller that don't come from the API.  Centralized the type checking of the exceptions here
	// rather than copying the same logic in multiple try/catch blocks.  Currently just use the default "unexpected error"
	// but we can add different handling via "instanceof" checks
	//
	// This needs a lot of work and probaby a rewrite of how the vB5_Exception_Api class works because it does too much
	// formatting in the class and does not play nice with the standard error handling on Ajax calls.  However that's
	// beyond the current scope.
	protected function exceptionToErrorArray(Throwable $e)
	{
		$errors = [['unexpected_error', $e->getMessage()]];
		$result = ['errors' => $errors];

		$errors[] = ['exception_trace', $this->formatTrace($e)];

	//Need to figure out how to control the stacktrace from the front end.
	//		$config = vB::getConfig();
	//		if (!empty($config['Misc']['debug']))
	//		{
	//			$errors[] = ['exception_trace', $this->formatTrace($e)];
	//		}
		return $result;
	}

	private function formatTrace($e)
	{
		return '## ' . $e->getFile() . '(' . $e->getLine() . ") Exception Thrown \n" . $e->getTraceAsString();
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 111764 $
|| #######################################################################
\*=========================================================================*/
