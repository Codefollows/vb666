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

class vB5_Template_Cache
{
	const PLACEHOLDER_PREFIX = '<!-- ##template_';
	const PLACEHOLDER_SUFIX = '## -->';

	protected static $instance;
	protected $cache = [];
	protected $renderTemplatesInReverseOrder = false;

	protected $preloadHashKey = '';
	protected $preloadTemplates = [];
	protected $textOnlyReplace = [];

	/**
	 *
	 * @var array Stores the template info for direct descendants of the parent template.
	 */
	protected $pending = [];

	public static function instance()
	{
		if (!isset(self::$instance))
		{
			$c = __CLASS__;

			if (vB5_Template_Options::instance()->get('options.cache_templates_as_files'))
			{
				$c .= '_Filesystem';
			}
			self::$instance = new $c;
		}

		return self::$instance;
	}

	public function isTemplateText()
	{
		return true;
	}

	/**
	 * Stores template info for deferred fetching & rendering, and returns a placeholder
	 * @param string $templateName
	 * @param array $args
	 * @return string
	 */
	public function register($templateName, $args)
	{
		$pos = isset($this->pending[$templateName]) ? count($this->pending[$templateName]) : 0;
		$placeholder = $this->getPlaceholder($templateName, $pos);

		$this->pending[$templateName][$placeholder] = $args;

		return $placeholder;
	}

	/**
	 * Register variables in template
	 * @param vB5_Template $templater
	 * @param array $templateArgs
	 */
	protected function registerTemplateVariables($templater, $templateArgs)
	{
		// this is for allowing indexed access to variables
		$templater->register('arg', array_values($templateArgs));

		// also registered named variables
		foreach ($templateArgs as $key=>$value)
		{
			if (is_string($key))
			{
				$templater->register($key, $value);
			}
		}
	}

	/**
	 * Instructs the class to render the templates in reverse order or not
	 *
	 * @param	bool
	 */
	public function setRenderTemplatesInReverseOrder($value)
	{
		$this->renderTemplatesInReverseOrder = (bool) $value;
	}

	/**
	 * Replaces all template placeholders in $content with the rendered templates
	 * @param string|array $content
	 */
	public function replacePlaceholders(&$content)
	{
		//avoid warnings for null.  Note that this could be an array of strings (which str_replace is happy with)
		//in which case we need to preserve that behavior.
		$content = $content ?? '';

		// This function procceses subtemplates by level
		$missing = array_diff(array_keys($this->pending), array_keys($this->cache));
		if (!empty($missing))
		{
			$this->fetchTemplate($missing);
		}

		// move pending templates to a new variable, so they are not re-processed by subtemplates
		$levelPending = & $this->pending;
		unset($this->pending);
		$this->pending = [];

		// This line is important. In BBCode parser, the templates of inner BBCode are registered firstly
		// So they should be replaced later than the outer BBCode templates. See VBV-4834.
		if ($this->renderTemplatesInReverseOrder)
		{
			$levelPending = array_reverse($levelPending);
		}

		foreach ($levelPending as $templateName => $templates)
		{
			foreach ($templates as $placeholder => $templateArgs)
			{
				$templater = new vB5_Template($templateName);
				$this->registerTemplateVariables($templater, $templateArgs);

				try
				{
					$replace = $templater->render(false);
				}
				catch (vB5_Exception_Api $e)
				{
					$e->prependTemplate($templateName);

					if (isset($templateArgs['isWidget']) AND $templateArgs['isWidget'])
					{
						$errorTemplate = new vB5_Template(vB5_Template::WIDGET_ERROR_TEMPLATE);

						// we want to make the registered variables available to error template
						$this->registerTemplateVariables($errorTemplate, $templateArgs);

						$errorlist = $e->getErrors();
						//shouldn't happen but let's make sure we have an error message
						if (!$errorlist)
						{
							$errorlist = ['widget_error'];
						}

						$errorTemplate->register('errorlist', $errorlist);
						if (vB5_Config::instance()->debug)
						{
							$errorTemplate->register('template', $e->getTemplate());
							$errorTemplate->register('controller', $e->getController());
							$errorTemplate->register('method', $e->getMethod());
							$errorTemplate->register('arguments', print_r($e->getArguments(), true));
						}

						$replace = $errorTemplate->render(false);
					}
					else
					{
						throw $e;
					}
				}

				$content = str_replace($placeholder, $replace, $content);
				unset($templater);
			}
		}
	}

	public function getTemplate($templateId)
	{
		//it's odd here that we don't check the cache first in this case.  However I'm
		//not sure this ever gets called with an array and it's cached internally to the API
		if (is_array($templateId))
		{
			return $this->fetchTemplate($templateId);
		}

		if (!isset($this->cache[$templateId]))
		{
			$this->fetchTemplate($templateId);
		}

		if (isset($this->cache[$templateId]))
		{
			return $this->cache[$templateId];
		}

		//This should never happen under current code.  The fetch will return a blank result for invalid template
		//names.  We should, probably, fix this to handle the that defaulting so the fetch doesn't have to.
		throw new Exception('Non-existent template requested: ' . htmlspecialchars($templateId));
	}

	protected function getPlaceholder($templateName, $pos)
	{
		return self::PLACEHOLDER_PREFIX . $templateName . '_' . $pos . self::PLACEHOLDER_SUFIX;
	}

	/**
	 * Receives either a template name or an array of template names to be fetched from the API
	 * @param string|array $templateName
	 */
	protected function fetchTemplate(string|array $templateName) : void
	{
		$arguments = [$templateName];
		if ($styleId = vB5_Template_Stylevar::instance()->getPreferredStyleId())
		{
			$arguments[] = $styleId;
		}

		//these cases are different enough that we need to separate them.  Should possibly be different functions
		if (is_array($templateName))
		{
			$response = Api_InterfaceAbstract::instance()->callApi('template', 'fetchBulk', $arguments);
			if (isset($response['errors']))
			{
				//not sure what we should do here but passing the error array along as a success isn't a good idea.
				return;
			}

			foreach ($response AS $id => $code)
			{
				$this->setTemplateCache($id, $code);
			}
		}
		else
		{
			$response = Api_InterfaceAbstract::instance()->callApi('template', 'fetch', $arguments);

			// fetch will return false if the template doesn't exist.  Should probably be an error.
			if ($response === false)
			{
				return;
			}

			if (isset($response['errors']))
			{
				//not sure what we should do here but passing the error array along as a success isn't a good idea.
				return;
			}

			$this->setTemplateCache($templateName, $response);
		}
	}

	private function setTemplateCache(string $templateName, array $code) : void
	{
		// We treat full and limited the same way here.
		if ($code['compiletype'] == 'textonly')
		{
			// We use a placeholder for the text
			$placeholder =  $this->getPlaceholder($templateName, '_to');
			$this->cache[$templateName] = "\$final_rendered = \"" . $placeholder . "\";";
			$this->textOnlyReplace[$placeholder] = $code['template'];
		}
		else
		{
			// in this layer we need to use vB5_Template_Runtime instead of vB_Template_Runtime
			$this->cache[$templateName] = str_replace('vB_Template_Runtime', 'vB5_Template_Runtime', $code['template']);
		}
	}

	public function replaceTextOnly(&$finalRendered)
	{
		foreach ($this->textOnlyReplace AS $placeholder => $template)
		{
			$finalRendered = str_replace($placeholder, $template, $finalRendered);
		}
	}

}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 115856 $
|| #######################################################################
\*=========================================================================*/
