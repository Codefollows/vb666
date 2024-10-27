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

class vB5_Frontend_Controller_Admin extends vB5_Frontend_Controller
{
	/**
	 * Get layouts and modules for the sitebuilder layout UI.
	 *
	 *               'screenlayoutlist' - rendered screenlayouts to insert in DOM
	 *               'widgets' - array of widgets with rendered widget admin template
	 *               'css_links' - any css links needed to display rendered templates
	 */
	public function actionGetLayoutsForSitebuilder()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		$pagetemplateid = intval($_REQUEST['pagetemplateid'] ?? 0);
		$channelid = intval($_REQUEST['channelid'] ?? 0);

		$css_links = [];
		// If user has canusesitebuilder permission, it will return all modules, ignoring
		// the instance usergroup view checks, so the admin can edit the modules even if they
		// accidentally locked themselves out of that module.
		$admincheck = true;
		$widgets = Api_InterfaceAbstract::instance()->callApi('widget', 'fetchHierarchicalWidgetInstancesByPageTemplateId', [
			'pagetemplateid' => $pagetemplateid,
			'channelid' =>
			$channelid,
			'admincheck' => $admincheck
		]);

		foreach ($widgets AS $sectionnumber => $sectionwidgets)
		{
			// this adds the 'rendered_template' to each widget (including
			// any subModules), and populates the $css_links array
			// if there are any CSS links.
			$this->addRenderedWidgetAdminTemplates($widgets[$sectionnumber], $css_links);
		}

		$screenlayouttemplate = vB5_Template::staticRenderAjax('screenlayout_screenlayoutlist');

		$layouts =  [
			'screenlayoutlist' => $screenlayouttemplate['template'],
			'widgets' => $widgets,
			'css_links' => $css_links,
		];

		$this->sendAsJson($layouts);
	}


	/**
	 * Renders the admin template for each widget and adds it to the passed widget array.
	 * Handles recursive 'subModules' as well.
	 *
	 * @param	array	Reference to an array of widgets (this array is modified)
	 * @param	array	Reference to an array of css links (this array is modified)
	 */
	private function addRenderedWidgetAdminTemplates(array &$widgets, array &$css_links)
	{
		foreach ($widgets AS $key => $widget)
		{
			// add template & css links
			$template = !empty($widget['admintemplate']) ? $widget['admintemplate'] : 'widget_admin_default';
			$rendered = vB5_Template::staticRenderAjax($template, array('widget' => $widget));
			$widgets[$key]['rendered_template'] = $rendered['template'];
			$css_links = array_merge($css_links, $rendered['css_links']);

			// handle any sub modules
			if (!empty($widget['subModules']) AND is_array($widget['subModules']))
			{
				$this->addRenderedWidgetAdminTemplates($widgets[$key]['subModules'], $css_links);
			}
		}
	}

	public function actionSavepage()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		$input = $_POST['input'];
		$url = $_POST['url'];

		//parse_url doesn't work on relative urls and I don't want to assume that
		//we have an absolute url.  We probably don't have a query string, but bad assumptions
		//about the url are what got us into this problem to begin with.
		$parts = explode('?', $url, 2);
		$url = $parts[0];

		$query = '';
		if (sizeof($parts) == 2)
		{
			$query = $parts[1];
		}

		if (preg_match('#^http#', $url))
		{
			$base = vB5_Template_Options::instance()->get('options.frontendurl');
			if (preg_match('#^' . preg_quote($base, '#') . '#', $url))
			{
				$url = substr($url, strlen($base)+1);
			}
		}

		//if we are hitting the index page directly then we should treat it like the site root
		if($url == 'index.php')
		{
			$url = '';
		}

		$api = Api_InterfaceAbstract::instance();
		$route = $api->callApi('route', 'getRoute', ['pathInfo' => $url, 'queryString' => $query]);

		//if we have a redirect try to find the real route -- this should only need to handle one layer
		//and if that also gets a redirect things are broken somehow.
		if (!empty($route['redirect']))
		{
			$route = $api->callApi('route', 'getRoute', ['pathInfo' => ltrim($route['redirect'], '/'), 'queryString' => $query]);
		}

		$result = $api->callApi('page', 'pageSave', [$input]);
		if (empty($result['errors']))
		{
			$page = $api->callApi('page', 'fetchPageById', ['pageid' => $result['pageid'], 'routeData' => $route['arguments']]);

			//the route classes are, unfortunately, inconsistant about returning a leading slash (they shouldn't) and that
			//will break the JS code if we do here.  So force it not to.
			$result['url'] = ltrim($page['url'], '/');
		}

		$this->sendAsJson($result);
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 113928 $
|| #######################################################################
\*=========================================================================*/
