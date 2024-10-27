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

class vB5_Frontend_Controller_Dashboard extends vB5_Frontend_Controller
{
	protected $api;
	function __construct()
	{
		parent::__construct();
		$this->api = Api_InterfaceAbstract::instance();
	}

	public function actionUndo()
	{
		$router = vB5_ApplicationAbstract::instance()->getRouter();
		$queryParams = $router->getQueryParameters();

		if (empty($queryParams['undoid']))
		{
			$this->showErrorPage(['invalid_data']);
			return;
		}

		$currentuser = vB5_User::instance();
		$legacyLink = (empty($queryParams['hash']) AND empty($queryParams['userid']));
		$callNamed = true;
		if ($legacyLink)
		{
			$vars = [
				'undoid' => intval($queryParams['undoid']),
			];
			$result = $this->api->callApi('notification', 'undoUnsubscribe', $vars, $callNamed);
		}
		else
		{
			$vars = [
				'undoid' => intval($queryParams['undoid']),
				'hash' => $queryParams['hash'],
				'userid' => $queryParams['userid'],
			];
			$result = $this->api->callApi('notification', 'undoUnsubscribeViaHash', $vars, $callNamed);
		}

		if (!empty($result['errors']))
		{
			return $this->showErrorPage($result['errors']);
		}


		$settingslink = vB5_Route::buildUrl('settings|fullurl', ['tab' => 'notifications']);
		$undoingUserid = $queryParams['userid'] ?? $currentuser['userid'] ?? 0;
		$profilelink =  vB5_Route::buildUrl('subscription|fullurl', ['tab' => 'subscriptions', 'userid' => $undoingUserid]);

		$phrasesToRender = [
			'change_notification_settings' => ['change_notification_settings', $settingslink],
			'resubbed_to_following_nodes' => 'resubbed_to_following_nodes',
			'resubbed_success' => 'resubbed_success',
			'unsubscribe_pagetitle' => 'unsubscribe_pagetitle',
			'view_your_subs' => ['view_your_subs', $profilelink],
			//'changing_password_but_currently_logged_in_msg',
		];
		['phrases' => $phrases] = $this->api->callApi('phrase', 'renderPhrases', [$phrasesToRender]);

		if (!empty($result['subscribed_details']))
		{
			$message = $phrases['resubbed_to_following_nodes'];
			foreach ($result['subscribed'] AS ['nodeid' => $__id, 'title' => $__title])
			{
				$url = vB5_Route::buildUrl('node|fullurl', ['nodeid' => $__id]);
				$message .= "<br/>\n<a href=\"" . htmlentities($url) . "\">{$__title}</a>" ;
			}
		}
		else
		{
			$message = $phrases['resubbed_success'];
		}

		$message .= "<br/><br/>\n" . $phrases['view_your_subs'];

		$message .= "<br/><br/>\n" . $phrases['change_notification_settings'];

		$this->showMessagePage($phrases['unsubscribe_pagetitle'], $message);
	}

	private function handleOneClickUnsubscribe($queryParams)
	{
		$this->api->callApi('unsubscribe', 'unsubscribeEmail', [$queryParams['userid'] ?? 0, $queryParams['hash'] ?? '']);
		// For now, this is a blank page as RFC does not specify any response requirements, and this is not meant to
		// be user-friendly. It is expected to only be accessed via a mail client and not the human mail recipient directly.
	}

	// Landing page. This is to avoid GET request coming from email scanners etc from triggering the unsubscribe
	// without the user's intent.
	// List-Unsubscribe specs require a POST for the immediate unsubscribe, which is handled via handleOneClickUnsubscribe().
// 	private function handleManualUnsubscribe($queryParams)
// 	{

// 		$title = vB5_Template_Options::instance()->get('options.bbtitle');
// 		$string = vB::getString();
// 		$titleEscaped = $string->htmlspecialchars($title);

// 		$settingslink = vB5_Route::buildUrl('settings|fullurl', ['tab' => 'notifications']);

// 		$phrasesToRender = [
// 			'change_notification_settings' => ['change_notification_settings', $settingslink],
// 			'unsubscribe_pagetitle' => 'unsubscribe_pagetitle',
// 			'unsubscribe_description' => ['unsubscribe_description', $titleEscaped],
// 			'unsubscribe' => 'unsubscribe',
// 		];
// 		['phrases' => $phrases] = $this->api->callApi('phrase', 'renderPhrases', [$phrasesToRender]);

// 		$extra = [
// 			'hash' => $queryParams['hash'],
// 			'userid' => $queryParams['userid'],
// 		];
// 		$url = vB5_Route::buildUrl('dashboard|fullurl', ['action' => 'doManualUnsubscribe'], $extra);
// 		$string = vB::getString();
// 		$urlEscaped = $string->htmlspecialchars($url);

// 		// We may want to make this into a template instead.
// 		$message = <<<EOT
// <form action="$urlEscaped" method="POST">
// 	<div class="h-margin-bottom-l">
// 		{$phrases['unsubscribe_description']}
// 	</div>
// 	<div class="h-margin-bottom-l">
// 		{$phrases['change_notification_settings']}
// 	</div>
// 	<button type="submit" class="b-button b-button--primary">
// 		{$phrases['unsubscribe']}
// 	</button>
// </form>
// EOT;

// 		//$this->showMessagePage($phrases['unsubscribe_pagetitle'], $message);
// 		$this->showMessagePageBare($phrases['unsubscribe_pagetitle'], $message);


// 		$this->api->callApi('unsubscribe', 'validateUnsubscribeEmail', [$queryParams['userid'] ?? 0, $queryParams['hash'] ?? '']);
// 		// todo: show interstitial page for granularly unsubscribing,
// 		// or unsub all and show message about going to the user settings for better controls.
// 	}

	// Handle action after user clicks the unsubscribe button on the email link landing page
	public function actionDoManualUnsubscribe()
	{
		$router = vB5_ApplicationAbstract::instance()->getRouter();
		$queryParams = $router->getQueryParameters();
		$result = $this->api->callApi('unsubscribe', 'unsubscribeEmail', [$queryParams['userid'] ?? 0, $queryParams['hash'] ?? '']);

		$title = vB5_Template_Options::instance()->get('options.bbtitle');
		$string = vB::getString();
		$titleEscaped = $string->htmlspecialchars($title);

		$settingslink = vB5_Route::buildUrl('settings|fullurl', ['tab' => 'notifications']);
		$contactuslink = vB5_Route::buildUrl('contact-us|fullurl');

		$phrasesToRender = [
			'change_notification_settings' => ['change_notification_settings', $settingslink],
			'unsubscribe_error' => ['unsubscribe_error', $contactuslink],
			'unsubscribe_pagetitle' => 'unsubscribe_pagetitle',
			'unsubscribe_done' => ['unsubscribe_done', $titleEscaped],
		];
		['phrases' => $phrases] = $this->api->callApi('phrase', 'renderPhrases', [$phrasesToRender]);

		if(!empty($result['errors']))
		{
			// The spans are because flex containers seem to strip the spaces between texts & anchors
			// because the text blocks become anonymous flex item blocks separate from the anchors.
			// We might want to pusuh this into the error_page_bare template instead, but for now
			// taking the safer workaround for just this instance.
			$errorMsg = '<span>' . $phrases['unsubscribe_error'] . '</span>';
			// Switch out any errors with a generic error. I don't think we want to show site-specific
			// errors to someone just trying to unsubscribe, but may revisit this.
			return $this->showErrorPageBare([$errorMsg]);
		}


		$message = <<<EOT
<div class="h-margin-bottom-l">
	{$phrases['unsubscribe_done']}
</div>
<div class="h-margin-bottom-l">
	{$phrases['change_notification_settings']}
</div>
EOT;

		//$this->showMessagePage($phrases['unsubscribe_pagetitle'], $message);
		$this->showMessagePageBare($phrases['unsubscribe_pagetitle'], $message);
	}

	public function actionUnsubscribe()
	{
		$router = vB5_ApplicationAbstract::instance()->getRouter();
		$queryParams = $router->getQueryParameters();
		if ($_SERVER['REQUEST_METHOD'] == 'POST')
		{
			// "one click" unsubscribe
			return $this->handleOneClickUnsubscribe($queryParams);
		}
		// else, it's a manual click on a link

		if (!empty($queryParams['sentbynodeid']))
		{
			return $this->handleNodeSpecificUnsubscribe($queryParams);
		}

		if (!empty($queryParams['hash']))
		{
			//return $this->handleManualUnsubscribe($queryParams);
			// per new requirements, we are skipping the interstitial page and immediately
			// processing any GET requests.
			return $this->actionDoManualUnsubscribe();
		}

		// If we got here we don't know what to do with this.
		$this->showErrorPage(['invalid_data']);
		return;
	}

	private function handleNodeSpecificUnsubscribe($queryParams)
	{
		$currentuser = vB5_User::instance();
		$legacyLink = (empty($queryParams['hash']) AND empty($queryParams['userid']));
		$callNamed = true;
		if ($legacyLink)
		{
			$vars = [
				'sentbynodeid' => intval($queryParams['sentbynodeid']),
				'hash' => $queryParams['hash'] ?? null,
				'userid' => $queryParams['userid'] ?? null,
			];
			$result = $this->api->callApi('notification', 'unsubscribeFromNotification', $vars, $callNamed);
		}
		else
		{
			$vars = [
				'sentbynodeid' => intval($queryParams['sentbynodeid']),
				'hash' => $queryParams['hash'] ?? null,
				'userid' => $queryParams['userid'] ?? null,
			];
			$result = $this->api->callApi('notification', 'unsubscribeFromNotificationViaHash', $vars, $callNamed);
		}

		if (!empty($result['errors']))
		{
			return $this->showErrorPage($result['errors']);
		}

		$undoUrl = '';
		if (!empty($result['undoid']))
		{
			$extra = ['undoid' => $result['undoid']];
			if (empty($currentuser['userid']) AND !empty($queryParams['hash']))
			{
				$extra['hash'] = $queryParams['hash'];
				$extra['userid'] = $queryParams['userid'];
			}
			$undoUrl = vB5_Route::buildUrl('dashboard|fullurl', ['action' => 'undo'], $extra);
		}
		$settingslink = vB5_Route::buildUrl('settings|fullurl', ['tab' => 'notifications']);
		$unsubbingUserid = $queryParams['userid'] ?? $currentuser['userid'] ?? 0;
		$profilelink =  vB5_Route::buildUrl('subscription|fullurl', ['tab' => 'subscriptions', 'userid' => $unsubbingUserid]);

		$phrasesToRender = [
			'already_unsubscribed' => 'already_unsubscribed',
			'change_notification_settings' => ['change_notification_settings', $settingslink],
			'to_undo_click' => ['to_undo_click', $undoUrl],
			'unsubbed_from_following_nodes' => 'unsubbed_from_following_nodes',
			'unsubbed_success' => 'unsubbed_success',
			'unsubscribe_pagetitle' => 'unsubscribe_pagetitle',
			'view_your_subs' => ['view_your_subs', $profilelink],
		];
		// TODO: It would be nice to be able to get the target user's languageid and fetch the phrases under that language for
		// sessionless unsubscribes, but currently I'm not sure you can pull that user data out as a guest, and I'm not sure
		// if that counts as sensitive info or not. (This applies to all phrase API calls in this controller)
		['phrases' => $phrases] = $this->api->callApi('phrase', 'renderPhrases', [$phrasesToRender]);


		if (empty($result['unsubscribed_count']))
		{
			$message = $phrases['already_unsubscribed'];
		}
		else
		{
			// We may not have the node titles to display if we're not logged in.
			if (!empty($result['unsubscribed_details']))
			{
				$message = $phrases['unsubbed_from_following_nodes'];
				foreach ($result['unsubscribed_details'] AS ['nodeid' => $__id, 'title' => $__title])
				{
					$url = vB5_Route::buildUrl('node|fullurl', ['nodeid' => $__id]);
					$message .= "<br/>\n<a href=\"" . htmlentities($url) . "\">{$__title}</a>" ;
				}
			}
			else
			{
				$message = $phrases['unsubbed_success'];
			}

			if (!empty($result['undoid']))
			{
				$message .= "<br/><br/>\n" . $phrases['to_undo_click'];
			}
		}

		if ($unsubbingUserid)
		{
			$message .= "<br/><br/>\n" . $phrases['view_your_subs'];
		}

		$message .= "<br/><br/>\n" . $phrases['change_notification_settings'];

		$this->showMessagePage($phrases['unsubscribe_pagetitle'], $message);
	}

	public function index()
	{
		$this->showErrorPage(['invalid_data']);
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 111676 $
|| #######################################################################
\*=========================================================================*/
