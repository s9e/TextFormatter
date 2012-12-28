<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\Javascript\Code;
use s9e\TextFormatter\Configurator\Javascript\Dictionary;
use s9e\TextFormatter\Configurator\Javascript\Minifier;
use s9e\TextFormatter\Configurator\Javascript\Minifiers\ClosureCompilerService;
use s9e\TextFormatter\Configurator\Javascript\RegExp;
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
	* Encode a PHP array an equivalent Javascript representation
	*
	* @param  array|ArrayObject $array Original array
	* @return string                   Javascript representation
	*/
	protected function encode($array)
	{
		$preserveKeys = ($v instanceof Dictionary);
		$isArray = (!$preserveKeys && array_keys($array) === range(0, count($array) - 1));

		$src = ($isArray) ? '[' : '{';
		foreach ($array as $k => $v)
		{
			if (!$isArray)
			{
				$src .= (($preserveKeys) ? json_encode($k) : $k) . ':';
			}

			if (is_bool($v))
			{
				// Represent true/false as 1/0
				$src .= (string) (int) $v;
			}
			elseif ($v instanceof RegExp)
			{
				// Rely on RegExp::__toString()
				$src .= $v;
			}
			elseif ($v instanceof Code)
			{
				// Rely on Code::__toString()
				$src .= $v;
			}
			elseif (is_array($v) || $v instanceof ArrayObject)
			{
				$src .= self::encode($v);
			}
			elseif (is_scalar($v))
			{
				$src .= json_encode($v);
			}
			else
			{
				throw new RuntimeException('Cannot encode non-scalar value');
			}

			$src .= ',';
		}

		// Remove the last comma and close that structure
		$src  = substr($src, 0, -1);
		$src .= ($isArray) ? ']' : '}';

		return $src;
	}

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