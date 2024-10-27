<?php

class vB5_Frontend_Controller_Json extends vB5_Frontend_Controller
{
	public function __construct()
	{
		parent::__construct();

		// Taken from Api_InterfaceLight::init() -- disable notices/warnings as
		// they'll just break any JSON output.
		$config = vB5_Config::instance();
		if (!$config->report_all_ajax_errors)
		{
			vB::silentWarnings();
		}
	}

	public function actionManifest()
	{
		//the api init can redirect.  We need to make sure that happens before we echo anything
		$api = Api_InterfaceAbstract::instance();


		//This function needs to be kept in sync with the implmentation in applicationlight.php
		if (headers_sent($file, $line))
		{
			throw new Exception("Cannot send response, headers already sent. File: $file Line: $line");
		}

		// we're using json, so we need UTF-8, but the API method should've already guaranteed that.
		// Skip the conversion here. (Also one reason we're avoiding prepJson(), other being so we can
		// customize the header)
		$check = $api->callApi('site', 'getAppManifest');
		$manifestJson = $check['manifest'] ?? [];
		if (is_array($manifestJson))
		{
			$manifestJson = json_encode($manifestJson, true);
		}

		header('Content-Type: application/manifest+json;');
		echo $manifestJson;

	}
}
