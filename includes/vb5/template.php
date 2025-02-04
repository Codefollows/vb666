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
 * This class is a simplified version of the one implemented in includes/class_core.php
 */
class vB5_Template
{
	const WIDGET_ERROR_TEMPLATE = 'widget_error';

	/**
	 * Name of the template to render
	 *
	 * @var	string
	 */
	protected $template = '';

	/**
	 * Array of registered variables.
	 * @see vB5_Template::preRegister()
	 *
	 * @var	array
	 */
	protected $registered = [];

	/**
	 * Array of global registered variables.
	 * Global registered variables are available in main templates and child templates
	 * included with {vb:template}
	 *
	 * @var array
	 */
	protected static $globalRegistered = [];
	private static bool $globalsRegistered = false;

	//this is primarily to hide the variable from the template code since local variables
	//are in scope of the template code.
	private vB5_Template_Cache $templateCache;

	/**
	 * List of templates rendered (for debugging output)
	 *
	 * @var array
	 */
	protected static $renderedTemplates = [];
	protected static $renderedTemplateNames = [];
	protected static $renderedTemplatesStack = [];

	/*
	 * jQuery URL
	 */
	protected static $jQueryUrl = '';

	/**
	 * Constructor
	 */
	public function __construct($templateName)
	{
		$this->template = $templateName;

		$this->templateCache = vB5_Template_Cache::instance();
		$this->registerDefaultGlobals();
	}

	/**
	 * Register a variable with the template.
	 * Global registered variables are available in main templates and child templates
	 * included with {vb:template}
	 *
	 * @param	string	Name of the variable to be registered
	 * @param	mixed	Value to be registered. This may be a scalar or an array.
	 * @param	bool	Whether to overwrite existing vars
	 * @return	bool	Whether the var was registered
	 */
	public function register($name, $value, $overwrite = true)
	{
		if (!$overwrite AND $this->isRegistered($name))
		{
			return false;
		}

		$this->registered[$name] = $value;

		return true;
	}

	/**
	 * Register a global variable with the template.
	 *
	 * @param	string	Name of the variable to be registered
	 * @param	mixed	Value to be registered. This may be a scalar or an array.
	 * @param	bool	Whether to overwrite existing vars
	 * @return	bool	Whether the var was registered
	 */
	public function registerGlobal($name, $value, $overwrite = true)
	{
		if (!$overwrite AND $this->isGlobalRegistered($name))
		{
			return false;
		}

		self::$globalRegistered[$name] = $value;

		return true;
	}

	/**
	 * Determines if a named variable is registered.
	 *
	 * @param	string	Name of variable to check
	 * @return	bool
	 */
	public function isRegistered($name)
	{
		return isset($this->registered[$name]);
	}

	/**
	 *	Remove any problematic values from the template
	 *	variable arrays before rendering
	 */
	//for now don't pass the values through.  These arrays are potentially large
	//and we don't want to make unnecesary copies.  The alternative is to pass by
	//reference which causes it's own headaches.  It's an internal function and the
	//relevant arrays are all class variables.
	private function cleanRegistered()
	{
		$disallowedNames = ['widgetConfig'];
		foreach ($disallowedNames AS $name)
		{
			unset($this->registered[$name]);
			unset(self::$globalRegistered[$name]);
		}
	}

	/**
	 * Determines if a named variable is global registered.
	 *
	 * @param	string	Name of variable to check
	 * @return	bool
	 */
	public function isGlobalRegistered($name)
	{
		return isset(self::$globalRegistered[$name]);
	}

	protected function registerjQuery()
	{
		if (!self::$jQueryUrl)
		{
			// create the path to jQuery depending on the version
			$customjquery_path = vB::getDatastore()->getOption('customjquery_path');
			$remotejquery = vB::getDatastore()->getOption('remotejquery');

			$protocol = '';
			if ($customjquery_path)
			{
				$path = str_replace('{version}', JQUERY_VERSION, $customjquery_path);
				if (!preg_match('#^https?://#si', $customjquery_path))
				{
					$path = '//' . $path;
				}
				self::$jQueryUrl = $path;
			}
			// Google CDN
			else if ($remotejquery == 1)
			{
				self::$jQueryUrl = 'https://ajax.googleapis.com/ajax/libs/jquery/' . JQUERY_VERSION . '/jquery.min.js';
			}
			// jQuery CDN
			else if ($remotejquery == 2)
			{
				self::$jQueryUrl = 'https://code.jquery.com/jquery-' . JQUERY_VERSION . '.min.js';
			}
			// Microsoft CDN
			else if ($remotejquery == 3)
			{
				self::$jQueryUrl = 'https://ajax.aspnetcdn.com/ajax/jquery/jquery-' . JQUERY_VERSION . '.min.js';
			}
			else
			{
				self::$jQueryUrl = 'js/jquery/jquery-' . JQUERY_VERSION . '.min.js';
			}
		}

		$this->registerGlobal('jqueryurl', self::$jQueryUrl);
		$this->registerGlobal('jqueryversion', JQUERY_VERSION);
	}

	/**
	 * Globally registers the default variables that are globally available
	 * to all templates.
	 */
	protected function registerDefaultGlobals()
	{
		if (!self::$globalsRegistered)
		{
			$this->registerGlobal('admincpdir', 'admincp');

			$this->registerjQuery();

			$user = vB5_User::instance();
			$this->registerGlobal('user', $user, true);

			$this->registerGlobal('config', $this->getConfigForTemplates(), true);

			$vboptions = vB5_Template_Options::instance()->getOptions()['options'];
			$this->registerGlobal('vboptions', $vboptions, true);

			$baseurl = $vboptions['frontendurl'];
			$this->registerGlobal('baseurl', $baseurl, true);

			$baseurl_core = $vboptions['bburl'];
			$this->registerGlobal('baseurl_core', $baseurl_core, true);

			$baseurl_data = vB5_String::parseUrl($baseurl);
			$this->registerGlobal('baseurl_data', $baseurl_data, true);

			$baseurl_path = $baseurl_data['path'] ?? '/';
			$baseurl_path = $baseurl_path . (substr($baseurl_path, -1) != '/' ? '/' : ''); //same as cookie path
			$this->registerGlobal('baseurl_path', $baseurl_path, true);

			$baseurl_cdn = $vboptions['cdnurl'];
			if (!$baseurl_cdn)
			{
				//if we haven't set a cdn url, then let's default to the actual site urls.
				$baseurl_cdn = './';
			}

			$baseurl_cdn .= ($baseurl_cdn[-1] != '/' ? '/' : '');
			$baseurl_corecdn = $baseurl_cdn . 'core/';

			$this->registerGlobal('baseurl_cdn', $baseurl_cdn, true);
			$this->registerGlobal('baseurl_corecdn', $baseurl_corecdn, true);

			$vbproducts = vB::getDatastore()->getValue('products');
			$this->registerGlobal('vbproducts', $vbproducts, true);

			$templateStylevar = vB5_Template_Stylevar::instance();
			$preferred_styleid = $templateStylevar->getPreferredStyleId();
			if (!$preferred_styleid)
			{
				$preferred_styleid = $vboptions['styleid'];
			}
			$this->registerGlobal('preferred_styleid', $preferred_styleid, true);
			$disallow = $templateStylevar->getDisallowStylePicker();
			$this->registerGlobal('disallow_style_picker', $disallow, true);

			$preferred_languageid  = vB5_User::getLanguageId() > 0 ? vB5_User::getLanguageId() : $vboptions['languageid'];
			$this->registerGlobal('preferred_languageid', $preferred_languageid, true);

			$timenow = time();
			$this->registerGlobal('timenow', $timenow, true);

			$this->registerGlobal('simpleversion', SIMPLE_VERSION, true);

			$flash_message = $this->decodeFlashMessage();
			$this->registerGlobal('flash_message', $flash_message, true);

			self::$globalsRegistered = true;
		}
	}

	// We don't want to just dump the entire content of the config file to the template
	// layer.  Nothing in the front end config file is that sensitive, but the back end config
	// absolutely has some stuff (and at some point we really should unify the two).
	//
	// This makes sure that we are explicit on exactly what we expose to the templates.
	private function getConfigForTemplates()
	{
		$config = vB5_Config::instance();
		return [
			'cookie_prefix' => $config->cookie_prefix,
			'debug' => $config->debug,
		];
	}

	/**
	 * Determines if there is a flash message passed from the previous
	 * page, and if so, checks for validity and returns it.
	 *
	 * @return string The phrase for the flash message
	 */
	private function decodeFlashMessage()
	{
		// For an overview of how the flashMessage system works, see:
		// vB5_Frontend_Controller::encodeFlashMessage()
		// vB5_Template::decodeFlashMessage()
		// vB_Api_User::verifyFlashMessageSignature()
		// displayFlashMessage() in global.js

		// check if we have a valid, signed message passed from the previous
		// page, and if so return it so it can be shown briefly on the page.

		$message = '';

		if (!empty($_REQUEST['flashmsg']))
		{
			$encoded = (string) $_REQUEST['flashmsg'];
			$parts = explode('-', $encoded);

			if (count($parts) === 4 AND $parts[0] === 'msg')
			{
				$phrase = $parts[1];
				// normal phrase keys will have alphanumeric and "_". $phrase may also be a
				// base64 encoded (indicated by the prefix "base64,") fully rendered phrase
				// or phrase array. base64 may have alphanumeric and "+", "/", "=".
				// Phrase array handling is done automagically via vb:phrase in the header
				// & bare_header templates.
				if (preg_match('#^[a-z0-9_\+/=,]+$#siU', $phrase))
				{
					$api = Api_InterfaceAbstract::instance();
					array_shift($parts);
					$result = $api->callApi('user', 'verifyFlashMessageSignature', $parts);
					if ($result['success'])
					{
						if (substr($phrase, 0, 7) === 'base64,')
						{
							$phrase = json_decode(base64_decode(substr($phrase, 7)), true);
						}
						$message = $phrase;
					}
				}
			}
		}

		return $message;
	}

	/**
	 * Renders the output after preperation.
	 * @see vB5_Template::render()
	 *
	 * @param boolean	Whether to suppress the HTML comment surrounding option (for JS, etc)
	 * @param boolean	true if we are rendering for a call to /ajax/render/ and we want CSS <link>s separate
	 *
	 * @return string
	 */
	public function render($isParentTemplate = true, $isAjaxTemplateRender = false)
	{
		//if this is the top level template let's make sure we don't have anything being
		//set that we don't want passed to the templates (to avoid overwriting values that
		//we generate internally and expect to come from the API).
		//
		//Ideally we would do this on register but we use the register function to pass things
		//from parent to child that we *do* need to preserve and we don't have the "top level"
		//information until render is called.
		if ($isParentTemplate)
		{
			$this->cleanRegistered();
		}

		// NOTE: Keep the debug template render in renderDelayed() in sync
		// with this render() function

		// save info for debug output
		self::handlePreRenderDebugInfo($this->template, $isParentTemplate);
		vB5_Template_Runtime::startTemplate($this->template);

		$config = vB5_Config::instance();
		// remove this once we can remove notices from template code
		// allow developers to turn notices off for templates -- to avoid having them turn off notices entirely
		if ($config->no_template_notices)
		{
			$oldReporting = error_reporting(error_reporting() & ~(E_NOTICE | E_STRICT | E_DEPRECATED));
		}

		if ($config->render_debug)
		{
			set_exception_handler(null);
			set_error_handler(function ($errno, $errmsg, $errfile, $errline)
			{
				//This will ignore any error reporting masks (the handler is called regardless
				//and it's up to the implementation to check) but given the context that's
				//more of a feature than a bug.
				echo("Error ($errno): $errmsg in $errfile line $errline. <br />");
			});

			// Show which template is being rendered.
			echo 'Template: ' . $this->template . '<br />';
		}

		$templateCache = vB5_Template_Cache::instance();
		$templateCode = $templateCache->getTemplate($this->template);

		$final_rendered = $this->runTemplateCode();

		if ($config->render_debug)
		{
			restore_error_handler();
			restore_exception_handler();
		}

		if ($config->no_template_notices)
		{
			error_reporting($oldReporting);
		}

		// always replace placeholder for templates, as they are process by levels
		$templateCache->replacePlaceholders($final_rendered);
		if ($isParentTemplate)
		{
			// we only replace phrases/urls/nodetext, insert javascript and stylesheets at the parent template
			$this->renderDelayed($final_rendered, $isAjaxTemplateRender);
		}
		vB5_Template_Runtime::endTemplate();

		// save info for debug output
		self::handlePostRenderDebugInfo();

		// add template name to HTML source for debugging
		self::addDebugTemplateName($final_rendered, $this->template);

		return $final_rendered;
	}

	//Keep this scope as narrow as possible.  Any variables exposed here are exposed to the template.
	private function runTemplateCode()
	{
		extract(self::$globalRegistered, EXTR_SKIP | EXTR_REFS);
		extract($this->registered, EXTR_OVERWRITE | EXTR_REFS);

		$templateCode = $this->templateCache->getTemplate($this->template);

		$final_rendered = '';
		if (is_array($templateCode) AND !empty($templateCode['textonly']))
		{
			$final_rendered = $templateCode['placeholder'];
		}
		else if ($this->templateCache->isTemplateText())
		{
			eval($templateCode);
		}
		else
		{
			if ($templateCode !== false)
			{
				include($templateCode);
			}
		}

		return $final_rendered;
	}

	/**
	 * Handles storing the debug info about templates that have been rendered. Called PRE-render.
	 *
	 * @param string Template name
	 * @param bool   Is this a parent template
	 */
	protected static function handlePreRenderDebugInfo($templateName, $isParentTemplate)
	{
		self::$renderedTemplateNames[] = $templateName;

		// debug info for the templates that have been used
		if (vB5_Config::instance()->debug)
		{
			self::$renderedTemplates[] = [
				'templateName'     => $templateName,
				'isParentTemplate' => (bool) $isParentTemplate,
				'indent'           => str_repeat('|----', count(self::$renderedTemplatesStack)),
			];
			self::$renderedTemplatesStack[] = $templateName;
		}
	}

	/**
	 * Handles storing the debug info about templates that have been rendered. Called POST-render.
	 */
	protected static function handlePostRenderDebugInfo()
	{
		// debug info for the templates that have been used
		if (vB5_Config::instance()->debug)
		{
			array_pop(self::$renderedTemplatesStack);
		}
	}

	/**
	 * Adds the template name as an HTML comment for debugging purposes
	 *
	 * @param  string (reference) The rendered HTML
	 * @param  string The template name.
	 *
	 * @return string The rendered HTML with an HTML comment before/after, indicating the template name
	 */
	public static function addDebugTemplateName(&$final_rendered, $templateName)
	{
		// add template name to HTML source for debugging
		if (!empty(self::$globalRegistered['vboptions']['addtemplatename']) AND self::$globalRegistered['vboptions']['addtemplatename'])
		{
			$final_rendered = "<!-- BEGIN: $templateName -->$final_rendered<!-- END: $templateName -->";
		}
	}

	/**
	 * Handle any delayed rendering. Currently delayed urls and node texts.
	*
	* @param	string
	 * @param	boolean	true if we are rendering for a call to /ajax/render/ and we want CSS <link>s separate
	*
	* @return	string
	 */
	protected function renderDelayed(&$final_rendered_orig, $isAjaxTemplateRender = false)
	{
		$javascript = vB5_Template_Javascript::instance();
		$javascript->insertJs($final_rendered_orig);
		$javascript->resetPending();

		$link = vB5_Template_Headlink::instance();
		$link->insertLinks($final_rendered_orig);
		$link->resetPending();

		$phrase = vB5_Template_Phrase::instance();
		$phrase->replacePlaceholders($final_rendered_orig);
		$phrase->resetPending();

		// we do not reset pending urls, since they may be required by nodetext
		vB5_Template_Url::instance()->replacePlaceholders($final_rendered_orig);

		$nodeText = vB5_Template_NodeText::instance();
		$nodeText->replacePlaceholders($final_rendered_orig);
		$nodeText->resetPending();
		$templateCache = vB5_Template_Cache::instance();
		$templateCache->replaceTextOnly($final_rendered_orig);

		// insert stylesheets after phrases and node text, since both could
		// contain CSS block classes that insertCSS will autoload
		$stylesheet = vB5_Template_Stylesheet::instance();
		$stylesheet->insertCss($final_rendered_orig, $isAjaxTemplateRender);
		$stylesheet->resetPending();

		//We should keep the debug info for truly last.
		if (vB5_Frontend_Controller_Bbcode::needDebug())
		{
			$config = vB5_Config::instance();

			if (!$config->debug)
			{
				return $final_rendered_orig;
			}

			$debugtemplate = new self('debug_info');

			$renderedTemplates = [
				'count' => count(self::$renderedTemplates),
				'countUnique' => count(array_unique(self::$renderedTemplateNames)),
				'templates' => self::$renderedTemplates,
				'styleid' => vB5_Template_Stylevar::instance()->getPreferredStyleId(),
			];

			// This follows the logic that was in the template but there may be a better way to do this.
			// I think that the explain link isn't guarded because the debug block doesn't show the on the explain page.
			$urlChar = (empty($_SERVER['QUERY_STRING']) ? '?': '&');
			$querylisturl = (!strpos($_SERVER['REQUEST_URI'], 'querylist=1') ? $_SERVER['REQUEST_URI'] . $urlChar . 'querylist=1' : '');
			$explainurl = $_SERVER['REQUEST_URI'] . $urlChar . 'explain=1';

			$debugtemplate->register('renderedTemplates', $renderedTemplates, true);
			$debugtemplate->register('cssDebugLog', vB5_Template_Stylesheet::getDebugLog(), true);
			$debugtemplate->register('jsDebugLog', vB5_Template_Javascript::instance()->getDebugLog(), true);
			$debugtemplate->register('includedFileInfo', $this->getIncludedFileInfo(), true);
			$debugtemplate->register('autoloadInfo', $this->getAutoloadInfo(), true);
			$debugtemplate->register('querylisturl', $querylisturl);
			$debugtemplate->register('explainurl', $explainurl);
			$final_rendered = $debugtemplate->render(false);

			$phrase->replacePlaceholders($final_rendered);
			$phrase->resetPending();

			// <!-- VB-DEBUG-PAGE-TIME-PLACEHOLDER --> is replaced in the controller's outputPage method
			//$final_rendered = str_replace('<!-- VB-DEBUG-PAGE-TIME-PLACEHOLDER -->', round(microtime(true) - VB_REQUEST_START_TIME, 4), $final_rendered);

			$final_rendered_orig = str_replace('<!-DebugInfo-->', $final_rendered, $final_rendered_orig);
		}
	}

	/**
	 * Returns the included file information used by renderDelayed for the debug_info template
	 *
	 * @return array Array containing 'files' and 'count'.
	 */
	protected function getIncludedFileInfo()
	{
		$dir = rtrim(str_replace('\\', '/', DIR), '/');
		if (substr($dir, -5) == '/core')
		{
			$dir = substr($dir, 0, -5);
		}

		$len = strlen($dir);

		$includedFiles = get_included_files();
		foreach ($includedFiles AS $k => $v)
		{
			$v = str_replace('\\', '/', $v);
			if (strpos($v, $dir) === 0)
			{
				$includedFiles[$k] = '.' . substr($v, $len);
			}
		}

		return [
			'files' => $includedFiles,
			'count' => count($includedFiles),
		];
	}

	/**
	 * Returns debug autoload info
	 *
	 * @return array Array of debug info containing 'classes' and 'count'
	 */
	public static function getAutoloadInfo()
	{
		$info = [
			vB5_Autoloader::getAutoloadInfo(),
			vB::getAutoloadInfo(),
		];

		$autoloadInfo = [];

		foreach ($info AS $loaderInfo)
		{
			foreach ($loaderInfo AS $class => $classInfo)
			{
				if (!isset($autoloadInfo[$class]))
				{
					$autoloadInfo[$class] = $classInfo;
				}
				else
				{
					// keep the one that actually loaded the class
					if (!empty($classInfo['loaded']) AND !empty($classInfo['filename']))
					{
						$autoloadInfo[$class] = $classInfo;
					}
				}
			}
		}

		$dir = rtrim(str_replace('\\', '/', DIR), '/');
		if (substr($dir, -5) == '/core')
		{
			$dir = substr($dir, 0, -5);
		}
		$len = strlen($dir);

		foreach ($autoloadInfo AS $k => $v)
		{
			if (!empty($v['filename']))
			{
				$v['filename'] = str_replace('\\', '/', $v['filename']);
				if (strpos($v['filename'], $dir) === 0)
				{
					$autoloadInfo[$k]['filename'] = '.' . substr($v['filename'], $len);
				}
			}
		}

		return [
			'classes' => $autoloadInfo,
			'count' => count($autoloadInfo),
		];
	}

	public static function getRenderedTemplates()
	{
		return self::$renderedTemplateNames;
	}

	/**
	 * Returns a string containing the rendered template
	 * @see vB5_Frontend_Controller_Ajax::actionRender
	 * @see vB5_Frontend_Controller_Page::renderTemplate
	 * @param string $templateName
	 * @param array $data
	 * @param bool $isParentTemplate
	 * @param bool $isAjaxTemplateRender - true if we are rendering for a call to /ajax/render/ and we want CSS <link>s separate
	 * @return string
	 */
	public static function staticRender($templateName, $data = [], $isParentTemplate = true, $isAjaxTemplateRender = false)
	{
		if (empty($templateName))
		{
			return null;
		}

		$templater = new vB5_Template($templateName);

		foreach ($data AS $varname => $value)
		{
			$templater->register($varname, $value);
		}

		vB5_Autoloader::register(vB5_ApplicationAbstract::instance()->getCorePath());

		$result = $templater->render($isParentTemplate, $isAjaxTemplateRender);
		return $result;
	}

	/**
	 * Returns a string containing the rendered template
	 * @see vB5_Frontend_Controller_Ajax::actionRender
	 * @param string $templateName
	 * @param array $data
	 * @return string
	 */
	public static function staticRenderAjax($templateName, $data = [])
	{
		$rendered = self::staticRender($templateName, $data, true, true);

		$css = vB5_Template_Stylesheet::instance()->getAjaxCssLinks();

		return [
			'template' => $rendered,
			'css_links' => $css,
		];
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 116130 $
|| #######################################################################
\*=========================================================================*/
