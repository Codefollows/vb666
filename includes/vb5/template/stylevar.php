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

class vB5_Template_Stylevar
{

	protected static $instance;
	protected $cache = [];
	protected $stylePreference = [];
	protected bool $disallowStylePicker = false;

	/**
	 *
	 * @return vB5_Template_Stylevar
	 */
	public static function instance()
	{
		if (!isset(self::$instance))
		{
			$c = __CLASS__;
			self::$instance = new $c;
		}

		return self::$instance;
	}

	protected function __construct()
	{
		$this->getStylePreference();
		$this->fetchStyleVars();
	}

	/**
	 * Returns the styleid that should be used on this request
	 */
	public function getPreferredStyleId()
	{
		return intval(reset($this->stylePreference));
	}

	/**
	 * Returns whether the preferred style is from a source that should disallow the footer style picker.
	 * For channel & scheduled styles that override user selection, we should disallow the picker, as
	 * the picker doesn't do anything in those cases.
	 *
	 * @return bool
	 */
	public function getDisallowStylePicker()
	{
		return $this->disallowStylePicker;

	}

	private function prepareChannelAndUserStyleids($defaultStyleId) : array
	{
		$forceStyleId = null;
		$cookieStyleId = null;
		$userStyleId = null;
		$routeStyleId = null;

		$arguments = [];
		try
		{
			$router = vB5_ApplicationAbstract::instance()->getRouter();
			if (!empty($router))
			{
				$arguments = $router->getArguments();

				// #1 check for a forced style in current route
				if (!empty($arguments) AND !empty($arguments['forceStyleId']) AND intval($arguments['forceStyleId']))
				{
					$forceStyleId = $arguments['forceStyleId'];
				}
			}
		}
		catch (vB5_Exception $e)
		{
			// the application instance might not be initialized yet, so just ignore this first check
		}

		// #2 check for a style cookie (style chooser in footer)
		// If style is set in querystring, the routing component will set this cookie (VBV-3322)
		$cookieStyleId = vB5_Cookie::get('userstyleid', vB5_Cookie::TYPE_UINT);

		// #3 check for user defined style
		$userStyleId = vB5_User::get('styleid');

		// TODO: if user does not have $canChangeStyle, where
		//	$vboptions = vB::getDatastore()->getValue('options');
		//  $canChangeStyle =  ($vboptions['allowchangestyles'] == 1 OR $userContext->hasAdminPermission('cancontrolpanel'));
		// we should defer to the style schedule if one exists and ignore anything set in the user select.

		// #4 check for a route style which is not forced (i.e. "Override Users' Style Choice" : No)
		// Note that this does not work currently.
		// It didn't work in vB5 because there used to be a is_int($arguments['routeStyleId']) check,
		// but our route arguments were strings so that always failed.
		// It currently does not work, because it seems that $userStyleId is ALWAYS set and override
		// this (because it's higher order/precedence in $stylePreference), even if the user style
		// is set to use "Forum Default" in the user settings.
		if (!empty($arguments) AND isset($arguments['routeStyleId']) AND is_numeric($arguments['routeStyleId'])
		)
		{
			$routeStyleId = $arguments['routeStyleId'];
		}

		return [
			'forceStyleId'  => ($forceStyleId != -1  ? $forceStyleId  : $defaultStyleId),
			'cookieStyleId' => ($cookieStyleId != -1 ? $cookieStyleId : $defaultStyleId),
			'userStyleId'   => ($userStyleId != -1   ? $userStyleId   : $defaultStyleId),
			'routeStyleId'  => ($routeStyleId != -1  ? $routeStyleId  : $defaultStyleId),
		];
	}

	private function prioritizePreferredStyles($scheduledStyle, $forceStyleId, $cookieStyleId, $userStyleId, $routeStyleId, $defaultStyleId) : array
	{
		$scheduledStyleInserted = false;
		$stylePreference = [];
		$disallowStyleSelect = [];
		// There's going to be a rock paper scissors game here...
		// E.g. Say we have a schedule that overrides channel but not user, and we have a user
		// with a style selected, viewing a channel that overrides user style (forceStyleId).
		// Without the schedule, channel (forceStyleId) > user (cookie) > user (session) > channel (routeStyleId),
		// so we'd get the channel (forceStyleId). But with the schedule, the schedule trumps channel(routeStyleId),
		// and we'd see the scheduled style, but that state violates the schedule's "not overrides user" (A). If we
		// allow the user style to override, however, then the channel (forceStyleId) is not being respected (B).
		// For now, we're just going to go with (A), as that feels slightly more correct.

		if ($scheduledStyle['overridechannelcustom'] AND $forceStyleId)
		{
			// note, the keys are useful mainly for debugging to see which styleid is from what source.
			$stylePreference['scheduledStyle'] = $scheduledStyle['styleid'];
			$scheduledStyleInserted = true;
		}

		// #1 channel, overrides user
		if ($forceStyleId)
		{
			$stylePreference['forceStyleId'] = $forceStyleId;
		}

		if ($scheduledStyle['overrideusercustom'] AND !$scheduledStyleInserted)
		{
			$stylePreference['scheduledStyle'] = $scheduledStyle['styleid'];
			$scheduledStyleInserted = true;
		}

		// #2 user cookie (OR supplied from URL, see VBV-3322)
		if ($cookieStyleId)
		{
			$stylePreference['cookieStyleId'] = $cookieStyleId;
		}

		// #3 user session, currently the $routeStyleId below because it seems to ALWAYS be set even when using "forum default" style.
		if ($userStyleId)
		{
			$stylePreference['userStyleId'] = $userStyleId;
		}

		// #4 channel, NOT overrides user
		if ($routeStyleId)
		{
			$stylePreference['routeStyleId'] = $routeStyleId;
		}

		// If we have a schedule, but it doesn't override channel styles or user styles, at least insert it
		// above the site default.
		if (!$scheduledStyleInserted AND $scheduledStyle['found'])
		{
			$stylePreference['scheduledStyle'] = $scheduledStyle['styleid'];
			$scheduledStyleInserted = true;
		}

		// #5 check for the overall site default style
		if ($defaultStyleId)
		{
			$stylePreference['defaultStyleId'] = $defaultStyleId;
		}

		// Certain conditions should disallow style selection.
		if ($scheduledStyle['overrideusercustom'])
		{
			$disallowStyleSelect[$scheduledStyle['styleid']] = $scheduledStyle['styleid'];
		}

		if ($forceStyleId)
		{
			$disallowStyleSelect[$forceStyleId] = $forceStyleId;
		}

		return [
			'stylePreference' => $stylePreference,
			'disallowStyleSelect' => $disallowStyleSelect,
		];
	}

	/**
	 * Gets the styles to be used ordered by preference
	 */
	protected function getStylePreference()
	{
		$defaultStyleId = vB5_Template_Options::instance()->get('options.styleid');
		$result = Api_InterfaceAbstract::instance()->callApi('style', 'getScheduledStyle');
		// fallback just in case
		$scheduledStyle = $result['currentstyle'] ?? [
			'found' => false,
			'styleid' => $defaultStyleId,
			'overridechannelcustom' => false,
			'overrideusercustom' => false,
			'expires' => 0,
		];
		if ($scheduledStyle['styleid'] == -1)
		{
			$scheduledStyle['styleid'] = $defaultStyleId;
		}

		[
			'forceStyleId' => $forceStyleId,
			'cookieStyleId' => $cookieStyleId,
			'userStyleId' => $userStyleId,
			'routeStyleId' => $routeStyleId,
		] = $this->prepareChannelAndUserStyleids($defaultStyleId);

		[
			'stylePreference' => $stylePreference,
			'disallowStyleSelect' => $disallowStyleSelect,

		] = $this->prioritizePreferredStyles($scheduledStyle, $forceStyleId, $cookieStyleId, $userStyleId, $routeStyleId, $defaultStyleId);
		$styleid = Api_InterfaceAbstract::instance()->callApi('style', 'getValidStyleFromPreference', [$stylePreference]);

		if (isset($disallowStyleSelect[$styleid]))
		{
			$this->disallowStylePicker = true;
		}

		//if we run into an error, let's make sure we have a valid style.
		if (!is_numeric($styleid))
		{
			$styleid = $defaultStyleId;
		}

		//we may not have a session yet -- especially if we are trying to render
		//an error that happens very early in the bootstrap.
		$session = vB::getCurrentSession();
		if($session)
		{
			// todo: vB_Library_User::fetchUserinfo(), which I think would be called as part of vB5_User::get('styleid') above,
			// gets styleid from the session. It seems a bit chicken or egg to be potentially getting and setting the styleid
			// from the session like this... not sure what's going on here or if this is a problem or not.
			$session->set('styleid', $styleid);
		}

		//we need to pass the "preference" array to some API functions.  We should probably fix that, but it's a
		//bit complicated by the fact that we still need to validate the style when we pass it in.
		//however we should chuck the styles we already rejected.
		$this->stylePreference = [$styleid];
	}

	public function get($name)
	{
		$path = explode('.', $name);

		$var = $this->cache;
		foreach ($path AS $t)
		{
			if (isset($var[$t]))
			{
				$var = $var[$t];
			}
			else
			{
				return NULL;
			}
		}

		return $var;
	}

	protected function fetchStyleVars()
	{
		// PLEASE keep this function in sync with fetch_stylevars() in functions.php
		// in terms of setting fake/pseudo stylevars

		$res = Api_InterfaceAbstract::instance()->callApi('style', 'fetchStyleVars', [$this->stylePreference]); // api method returns unserealized stylevars

		if (empty($res) OR !empty($res['errors']))
		{
			return;
		}

		$pseudo = [];
		$user = vB5_User::instance();

		$ltr = (is_null($user['lang_options']) OR !empty($user['lang_options']['direction']));

		if ($ltr)
		{
			// if user has a LTR language selected
			$pseudo['textdirection'] = 'ltr';
			$pseudo['left'] = 'left';
			$pseudo['right'] = 'right';
			$pseudo['pos'] = '';
			$pseudo['neg'] = '-';
		}
		else
		{
			// if user has a RTL language selected
			$pseudo['textdirection'] = 'rtl';
			$pseudo['left'] = 'right';
			$pseudo['right'] = 'left';
			$pseudo['pos'] = '-';
			$pseudo['neg'] = '';
		}


		if (!empty($user['lang_options']['dirmark']))
		{
			$pseudo['dirmark'] = ($ltr ? '&lrm;' : '&rlm;');
		}
		else
		{
			$pseudo['dirmark'] = '';
		}

		// get the 'lang' attribute for <html> tags
		$pseudo['languagecode'] = $user['lang_code'];

		// get the 'charset' attribute
		$pseudo['charset'] = $user['lang_charset'];

		// add 'styleid' of the current style for the sprite.php call to load the SVG icon sprite
		$pseudo['styleid'] = $this->getPreferredStyleId();

		// add 'cssdate' for the cachebreaker for the sprite.php call to load the SVG icon sprite
		$options = vB5_Template_Options::instance();
		$cssdate = intval($options->get('miscoptions.cssdate'));
		if (!$cssdate)
		{
			$cssdate = time(); // fallback so we get the latest css
		}
		$pseudo['cssdate'] = $cssdate;


		foreach ($res AS $key => $value)
		{
			$this->cache[$key] = $value;
		}

		foreach($pseudo AS $key => $value)
		{
			$this->cache[$key] = ['datatype' => 'string', 'string' => $value];
		}
	}


}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 114059 $
|| #######################################################################
\*=========================================================================*/
