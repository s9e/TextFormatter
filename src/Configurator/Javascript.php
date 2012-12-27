<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\Javascript\Minifier;
use s9e\TextFormatter\Configurator\Javascript\Minifiers\ClosureCompilerService;
use s9e\TextFormatter\Configurator\Traits\Configurable;
use s9e\TextFormatter\Plugins\ConfiguratorBase;

class Javascript
{
	use Configurable;

	/**
	* @var Minifier
	*/
	protected $minifier;

	/**
	* Constructor
	*
	* @param  Configurator $configurator Configurator
	* @return void
	*/
	public function __construct(Configurator $configurator)
	{
		$this->configurator = $configurator;
	}

	/**
	* Return the cached instance of Minifier (creates one if necessary)
	*
	* @return Minifier
	*/
	public function getMinifier()
	{
		if (!isset($this->minifier))
		{
			$this->minifier = new ClosureCompilerService;
		}

		return $this->minifier;
	}

	/**
	* Get a Javascript parser
	*
	* @return string
	*/
	public function getParser()
	{
		$files = array(
			'Parser/BuiltInFilters.js',
			'Parser/Logger.js',
			'Parser/Tag.js',
			'Parser.js',
			'Parser/FilterProcessing.js',
			'Parser/OutputHandling.js',
			'Parser/PluginsHandling.js',
			'Parser/RulesHandling.js',
			'Parser/TagProcessing.js',
			'Parser/TagStack.js'
		);

		$src = '';
		foreach ($files as $filename)
		{
			$filepath = __DIR__ . '/../' . $filename;
			$src .= file_get_contents($filepath) . "\n";
		}

		file_put_contents('/tmp/z.js', $src);
	}

	/**
	* Set the cached instance of Minifier
	*
	* @param  Minifier $minifier
	* @return void
	*/
	public function setMinifier(Minifier $minifier)
	{
		$this->minifier = $minifier;
	}

	//==========================================================================
	// Internal
	//==========================================================================

	/**
	* 
	*
	* @param  ConfiguratorBase $plugin Plugin to format
	* @return string
	*/
	protected function formatPlugin(ConfiguratorBase $plugin)
	{
		$src = $plugin->getJSParser();
		$pluginConfig = $plugin->asConfig();

		if ($src === false || $pluginConfig === false)
		{
			return false;
		}

		/**
		* @var array Keys of elements that are kept in the global scope. Everything else will be
		*            moved into the plugin's parser
		*/
		$globalKeys = array(
			'quickMatch'        => 1,
			'regexp'            => 1,
			'regexpLimit'       => 1,
			'regexpLimitAction' => 1
		);

		$globalConfig = array_intersect_key($pluginConfig, $globalKeys);
		$localConfig  = array_diff_key($pluginConfig, $globalKeys);

		$src = 'function(text,matches){/** @const */var config=' . self::encode($localConfig) . ';' . $src . '}';
	}
}