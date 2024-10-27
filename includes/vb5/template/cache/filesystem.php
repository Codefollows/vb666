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
 *	Class to handle fetching the template filenames when stored on the filesystem.
 *	Note that this requires that the template file is the same path for both front end and backend code.
 */
class vB5_Template_Cache_Filesystem extends vB5_Template_Cache
{
	protected $textOnlyTemplates = [];

	public function isTemplateText()
	{
		return false;
	}

	protected function __construct()
	{
		$this->textOnlyTemplates = Api_InterfaceAbstract::instance()->callApi('template', 'getTextonlyDS', []);
	}

	/**
	 * Receives either a template name or an array of template names to be fetched from the API
	 * @param mixed $templateName
	 */
	protected function fetchTemplate(array|string $templateName) : void
	{
		if (!is_array($templateName))
		{
			$templateName = [$templateName];
		}

		$styleId = vB5_Template_Stylevar::instance()->getPreferredStyleId();

		$response = Api_InterfaceAbstract::instance()->callApi('template', 'getTemplateIds', [
			'template_names' => $templateName,
			'styleid' => $styleId,
		]);
		$template_path = vB5_Template_Options::instance()->get('options.template_cache_path');

		if (isset($response['ids']))
		{
			foreach ($response['ids'] AS $name => $templateid)
			{
				$file = false;
				if ($templateid)
				{
					$file_name = "template$templateid.php";

					//this matches the filename logic from template library saveTemplateToFileSystem and needs to
					//so that we come up with the same file in both cases.
					$real_path = realpath($template_path);

					if ($real_path === false)
					{
						$real_path = realpath(vB5_ApplicationAbstract::instance()->getCorePath() . '/' . $template_path);
					}

					if ($real_path === false)
					{
						$file = false;
					}
					else
					{
						$file = $real_path . "/$file_name";
					}
				}

				if ($templateid AND $file AND array_key_exists($templateid, $this->textOnlyTemplates))
				{
					$placeholder =  $this->getPlaceholder($templateid, '_to');
					$this->textOnlyReplace[$placeholder] = file_get_contents($file);
					$this->cache[$name] = ['textonly' => 1, 'placeholder' => $placeholder];
				}
				else
				{
					$this->cache[$name] = $file;
				}
			}
		}
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 115856 $
|| #######################################################################
\*=========================================================================*/
