<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;

use ArrayObject;
use RuntimeException;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\Javascript\Code;
use s9e\TextFormatter\Configurator\Javascript\Dictionary;
use s9e\TextFormatter\Configurator\Javascript\Minifier;
use s9e\TextFormatter\Configurator\Javascript\Minifiers\ClosureCompilerService;
use s9e\TextFormatter\Configurator\Javascript\RegExp;
use s9e\TextFormatter\Configurator\Javascript\RegexpConvertor;
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
	* @var array Configuration, filtered for Javascript
	*/
	protected $config;

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
		// Load the sources
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

		$this->config = $this->configurator->asConfig();
		ConfigHelper::filterVariants($this->config, 'Javascript');

		// Reset this instance's callbacks
		$this->callbacks = array();

		// Inject the parser config
		$config = array(
			'plugins'        => $this->getPluginsConfig(),
			'registeredVars' => $this->getRegisteredVarsConfig(),
			'rootContext'    => $this->getRootContext(),
			'tagsConfig'     => $this->getTagsConfig()
		);
		$src = preg_replace_callback(
			'/(\\nvar (' . implode('|', array_keys($config)) . '))(;)/',
			function ($m) use ($config)
			{
				return $m[1] . '=' . $config[$m[2]] . $m[3];
			},
			$src
		);

		// Append the callbacks from filters and generators
		foreach ($this->callbacks as $name => $code)
		{
			$src .= "var $name=$code;\n";
		}

//		$src = $this->getMinifier()->minify($src);
		file_put_contents('/tmp/z.js', $src);

		return $src;
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
	* Convert a bitfield to the Javascript representationg of an array of number
	*
	* Context bitfields are stored as binary strings, but Javascript doesn't really have binary
	* strings so instead we split up that string in 4-bytes chunk, which we represent in hex
	* notation to avoid the number overflowing to a float in 32bit PHP
	*
	* @param  string $bitfield Raw bytes
	* @return Code             Javascript code
	*/
	static protected function convertBitfield($bitfield)
	{
		$hex = array();

		foreach (str_split($bitfield, 4) as $quad)
		{
			$v = '';
			foreach (str_split($quad, 1) as $n => $c)
			{
				$v = sprintf('%02X', ord($c)) . $v;
			}

			$hex[] = '0x' . $v;
		}

		$code = new Code('[' . implode(',', $hex) . ']');

		return $code;
	}

	/**
	* Get the Javascript representation of the plugins
	*
	* @return Code Javascript code
	*/
	protected function getPluginsConfig()
	{
		$plugins = new Dictionary;

		foreach ($this->config['plugins'] as $pluginName => $pluginConfig)
		{
			if (!isset($pluginConfig['parser']))
			{
				// Skip this plugin
				continue;
			}

			/**
			* @var array Keys of elements that are kept in the global scope. Everything else will be
			*            moved into the plugin's parser
			*/
			$globalKeys = array(
				'parser'            => 1,
				'quickMatch'        => 1,
				'regexp'            => 1,
				'regexpLimit'       => 1,
				'regexpLimitAction' => 1
			);

			$globalConfig = array_intersect_key($pluginConfig, $globalKeys);
			$localConfig  = array_diff_key($pluginConfig, $globalKeys);

			if (isset($globalConfig['regexp'])
			 && !($globalConfig['regexp'] instanceof RegExp))
			{
				$regexp = RegexpConvertor::toJS($globalConfig['regexp']);
				$regexp->flags .= 'g';

				$globalConfig['regexp'] = $regexp;
			}

			$globalConfig['parser'] = new Code('function(text,matches){/** @const */var config=' . self::encode($localConfig) . ';' . $globalConfig['parser'] . '}');

			$plugins[$pluginName] = $globalConfig;
		}

		// Create an instance of Code that represents the plugins array
		$code = new Code(self::encode($plugins));

		return $code;
	}

	/**
	* Generate a Javascript representation of the registered vars
	*
	* @return Code Javascript source code
	*/
	protected function getRegisteredVarsConfig()
	{
		if (empty($this->config['registeredVars']))
		{
			return new Code('{}');
		}

		$code = new Code(self::encode(new Dictionary($this->config['registeredVars'])));

		return $code;
	}

	/**
	* Generate a Javascript representation of the root context
	*
	* @return Code Javascript source code
	*/
	protected function getRootContext()
	{
		$rootContext = $this->config['rootContext'];

		$rootContext['allowedChildren']
				= self::convertBitfield($rootContext['allowedChildren']);
		$rootContext['allowedDescendants']
				= self::convertBitfield($rootContext['allowedDescendants']);

		$code = new Code(self::encode($rootContext));

		return $code;
	}

	/**
	* Generate a Javascript representation of the tags' config
	*
	* @return Code Javascript source code
	*/
	protected function getTagsConfig()
	{
		// Replace callback arrays with Javascript code
		self::replaceCallbacks($this->config);

		// Prepare a Dictionary that will preserve tags' names
		$tags = new Dictionary;
		foreach ($this->config['tags'] as $tagName => $tagConfig)
		{
			if (isset($tagConfig['attributes']))
			{
				// Make the attributes array a Dictionary, to preserve the attributes' names
				$tagConfig['attributes'] = new Dictionary($tagConfig['attributes']);
			}

			$tagConfig['allowedChildren']
				= self::convertBitfield($tagConfig['allowedChildren']);
			$tagConfig['allowedDescendants']
				= self::convertBitfield($tagConfig['allowedDescendants']);

			$tags[$tagName] = $tagConfig;
		}

		// Create an instance of Code that represents the tags array
		$code = new Code(self::encode($tags));

		return $code;
	}

	/**
	* Encode a PHP value into an equivalent Javascript representation
	*
	* @param  mixed  $value Original value
	* @return string        Javascript representation
	*/
	protected static function encode($value)
	{
		if (is_scalar($value))
		{
			if (is_bool($value))
			{
				// Represent true/false as 1/0
				$value = (int) $value;
			}

			return json_encode($value);
		}

		if ($value instanceof RegExp
		 || $value instanceof Code)
		{
			// Rely on RegExp::__toString() and Code::__toString()
			return (string) $value;
		}

		if (!is_array($value) && !($value instanceof ArrayObject))
		{
			throw new RuntimeException('Cannot encode non-scalar value');
		}

		$preserveKeys = ($value instanceof Dictionary);
		$isArray = (!$preserveKeys && array_keys($value) === range(0, count($value) - 1));

		$src = ($isArray) ? '[' : '{';
		$sep = '';

		foreach ($value as $k => $v)
		{
			$src .= $sep;

			if (!$isArray)
			{
				$src .= (($preserveKeys) ? json_encode($k) : $k) . ':';
			}

			$src .= self::encode($v);
			$sep = ',';
		}

		// Close that structure
		$src .= ($isArray) ? ']' : '}';

		return $src;
	}

	/**
	* 
	*
	* @return array
	*/
	protected function replaceCallbacks(array &$config)
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
				$js .= self::encode($v);
			}
			else
			{
				// Param by name -- if it's not one of the local vars passed to the callback, and
				// it's not one of the global vars "logger" and "registeredVars" then we assume that
				// it's a variable registered in registeredVars
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