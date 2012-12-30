<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;

use RuntimeException;
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
	/**
	* @var array Associative array of functions [name => function literal] built from and for
	*            ProgrammableCallback instances
	*/
	protected $callbacks;

	/**
	* @var Configurator Configurator this instance belongs to
	*/
	protected $configurator;

	/**
	* @var Minifier Instance of Minifier used to minify the Javascript parser
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
		// Reset this instance's callbacks
		$this->callbacks = array();

		// Grab the parser's config
		$config = $this->configurator->asConfig();

		// Replace callback arrays with Javascript code
		self::replaceCallbacks($config);

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

		// TODO: to tagsConfig (do filters?), do plugins

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
	* @return void
	*/
	protected function getTagsConfig()
	{
	}

	/**
	* Encode a PHP array an equivalent Javascript representation
	*
	* @param  array|ArrayObject $array Original array
	* @return string                   Javascript representation
	*/
	protected static function encode($array)
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

	/**
	* 
	*
	* @return array
	*/
	protected function replaceCallbacks(array $config)
	{
		foreach ($config['tags'] as $tagName => &$tagConfig)
		{
			if (isset($tagConfig['filterChain']))
			{
				foreach ($tagConfig['filterChain'] as &$filter)
				{
					$filter = $this->convertCallback('tagFilter', $filter);
				}
				unset($filter);
			}

			if (isset($tagConfig['attributes']))
			{
				foreach ($tagConfig['attributes'] as &$attrConfig)
				{
					if (isset($attrConfig['filterChain']))
					{
						foreach ($attrConfig['filterChain'] as &$filter)
						{
							$filter = $this->convertCallback('attributeFilter', $filter);
						}
						unset($filter);
					}

					if (isset($attrConfig['generator']))
					{
						$attrConfig['generator'] = $this->convertCallback(
							'attributeGenerator',
							$attrConfig['generator']
						);
					}
				}
				unset($attrConfig);
			}
		}
	}

	/**
	* Convert a callback array into Javascript code
	*
	* NOTE: custom callbacks will create entries in $this->callbacks
	*
	* @param  string $callbackType   Type of callback: either "attributeFilter",
	*                                "attributeGenerator" or "tagFilter"
	* @param  array  $callbackConfig Callback's config
	* @return Code                   Instance of Code
	*/
	protected function convertCallback($callbackType, array $callbackConfig)
	{
		// List of arguments for each type of callbacks. MUST be kept in sync with the invokations
		// in FilterProcessing.js
		$arguments = array(
			'attributeFilter'    => array('attrValue', 'attrName'),
			'attributeGenerator' => array('attrName'),
			'tagFilter'          => array('tag', 'tagConfig')
		);

		$callback   = $callbackConfig['callback'];
		$params     = $callbackConfig['params'];
		$jsCallback = null;

		if (isset($callbackConfig['js']))
		{
			// Use the Javascript source code that was set in the callback
			$jsCode     = $callbackConfig['js'];
			$jsCallback = sprintf('c%08X', crc32($jsCode));

			// Record this custom callback to be injected in the source
			$this->callbacks[$jsCallback] = $jsCode;
		}
		elseif (is_string($callback))
		{
			if (substr($callback, 0, 41) === 's9e\\TextFormatter\\Parser\\BuiltInFilters::')
			{
				// BuiltInFilters::filterNumber => BuiltInFilters.filterNumber
				$jsCallback = 'BuiltInFilters.' . substr($callback, 41);
			}
			elseif (substr($callback, 0, 26) === 's9e\\TextFormatter\\Parser::')
			{
				// Parser::filterAttributes => filterAttributes
				$jsCallback = substr($callback, 26);
			}
			elseif (preg_match('#^[-a-z_0-9]+$#Di', $callback))
			{
				// If the callback looks like the name of a PHP function, see if we have a
				// Javascript implementation available for it
				$filepath = __DIR__ . '/Javascript/functions/' . $callback . '.js';

				if (file_exists($filepath))
				{
					$jsCode     = file_get_contents($filepath);
					$jsCallback = sprintf('c%08X', crc32($jsCode));

					// Record the content of that file to be injected in the source
					$this->callbacks[$jsCallback] = $jsCode;
				}
			}
		}

		// If we don't have a Javascript implementation of this filter, we make it return FALSE
		// unconditionally
		if (!isset($jsCallback))
		{
			$jsCode     = 'function(){return false;}';
			$jsCallback = sprintf('c%08X', crc32($jsCode));
			$params     = array();

			$this->callbacks[$jsCallback] = $jsCode;
		}

		// Build the code needed to execute this callback
		$js  = $jsCallback . '(';
		$sep = '';

		// Add this callback's params
		foreach ($params as $k => $v)
		{
			$js .= $sep;
			$sep = ',';

			if (isset($v))
			{
				// Param by value
				$js .= json_encode($v);
			}
			else
			{
				// Param by name -- if it's not one of the local vars passed to the callback, and
				// it's not one of the global vars "logger" and "registeredVars" then we assume that
				// it's a variables registered in registeredVars
				if (!in_array($k, $arguments[$callbackType], true)
				 && $k !== 'logger'
				 && $k !== 'registeredVars')
				{
					 $k = 'registeredVars[' . json_encode($k) . ']';
				}

				$js .= $k;
			}
		}

		// Close the list of arguments
		$js .= ')';

		// Wrap the code inside of a function definition using this callback's type's arguments list
		$js = 'function(' . implode(',', $arguments[$callbackType]) . '){return ' . $js . ';}';

		// Return the Javascript source code as an instance of Code
		return new Code($js);
	}
}