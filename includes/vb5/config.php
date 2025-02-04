<?php

class vB5_Config
{
	private static $instance;
	private static $defaults = [
		'no_template_notices' => false,
		'debug' => false,
		'report_all_php_errors' => true,
		'report_all_ajax_errors' => false,
		'no_js_bundles' => false,
		'render_debug' => false,
	];
	private $config = [];


	public static function instance()
	{
		if (!isset(self::$instance))
		{
			$c = __CLASS__;
			self::$instance = new $c;
		}

		return self::$instance;
	}

	/**
	 * @param string $file
	 */
	public function loadConfigFile($file)
	{
		if (is_link(dirname($_SERVER["SCRIPT_FILENAME"])))
		{
			$frontendConfigPath = dirname(dirname(dirname($_SERVER["SCRIPT_FILENAME"]))) . '/' . $file;
		}
		else
		{
			$frontendConfigPath = dirname(__FILE__) . '/../../' . $file;
		}

		if (!file_exists($frontendConfigPath))
		{
			// Since we require removal of the install directory for live sites after the installation,
			// we should only treat this as "new install" if the makeconfig file exists.
			$makeConfigPath = dirname(__FILE__) . '/../../core/install/makeconfig.php';
			if (file_exists($makeConfigPath))
			{
				// Ideally, we would redirect them to the install URL, but we're not sure where we're at, and
				// without a config file and possibly no DB, we can't rely on bburl/frontendurl.
				require_once($makeConfigPath);
				exit;
			}
			// If the makeconfig file (& probably the install dir) isn't there and we don't have a config file, something is horribly wrong.
			// Let the regular handling below deal with it.
		}

		$config = [];
		require_once($frontendConfigPath);
		if (!isset($config))
		{
			//exiting here is dubious, should probably be an exception.
			die("Couldn't read config file $file");
		}

		$this->config = array_merge(self::$defaults, $config);
	}

	public function __get($name)
	{
		return $this->config[$name] ?? null;
	}
}
