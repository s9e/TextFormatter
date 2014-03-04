<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;

use ReflectionClass;
use RuntimeException;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\Items\Regexp as RegexpObject;
use s9e\TextFormatter\Configurator\JavaScript\Code;
use s9e\TextFormatter\Configurator\JavaScript\Dictionary;
use s9e\TextFormatter\Configurator\JavaScript\Minifier;
use s9e\TextFormatter\Configurator\JavaScript\Minifiers\Noop;
use s9e\TextFormatter\Configurator\JavaScript\RegExp;
use s9e\TextFormatter\Configurator\JavaScript\RegexpConvertor;
use s9e\TextFormatter\Configurator\RendererGenerators\XSLT;

class JavaScript
{
	/**
	* @var array Associative array of functions [name => function literal] built from and for
	*            ProgrammableCallback instances
	*/
	protected $callbacks;

	/**
	* @var array Configuration, filtered for JavaScript
	*/
	protected $config;

	/**
	* @var Configurator Configurator this instance belongs to
	*/
	protected $configurator;

	/**
	* @var array List of methods to be exported in the s9e.TextFormatter object
	*/
	public $exportMethods = [
		'disablePlugin',
		'disableTag',
		'enablePlugin',
		'enableTag',
		'getLogger',
		'parse',
		'preview',
		'setNestingLimit',
		'setParameter',
		'setTagLimit'
	];

	/**
	* @var Minifier Instance of Minifier used to minify the JavaScript parser
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
			$this->minifier = new Noop;
		}

		return $this->minifier;
	}

	/**
	* Get a JavaScript parser
	*
	* @return string
	*/
	public function getParser()
	{
		// Load the sources
		$files = [
			'Parser/utils.js',
			'Parser/BuiltInFilters.js',
			'Parser/Logger.js',
			'Parser/Tag.js',
			'Parser.js',
			'Parser/PluginsHandling.js',
			'Parser/RulesHandling.js',
			'Parser/TagProcessing.js',
			'Parser/TagStack.js'
		];

		// Append render.js if we export the preview method
		if (in_array('preview', $this->exportMethods, true))
		{
			$files[] = 'render.js';
		}

		// Get the stylesheet used for rendering
		$xsl = (new XSLT)->getXSL($this->configurator->rendering);

		// Reset this instance's callbacks
		$this->callbacks = [];

		// Store the parser's config
		$this->config = $this->configurator->asConfig();
		ConfigHelper::filterVariants($this->config, 'JS');

		// Start with the generated HINTs
		$src = $this->getHints($xsl);

		// Append the parser's source
		foreach ($files as $filename)
		{
			if ($filename === 'render.js')
			{
				// Insert the stylesheet if we include the renderer
				$src .= '/** @const */ var xsl=' . json_encode($xsl) . ";\n";
			}

			$filepath = __DIR__ . '/../' . $filename;
			$src .= file_get_contents($filepath) . "\n";
		}

		// Inject the parser config
		$config = [
			'plugins'        => $this->getPluginsConfig(),
			'registeredVars' => $this->getRegisteredVarsConfig(),
			'rootContext'    => $this->getRootContext(),
			'tagsConfig'     => $this->getTagsConfig()
		];
		$src = preg_replace_callback(
			'/(\\nvar (' . implode('|', array_keys($config)) . '))(;)/',
			function ($m) use ($config)
			{
				return $m[1] . '=' . $config[$m[2]] . $m[3];
			},
			$src
		);

		// Append the callbacks from filters and generators
		$src .= "\n" . implode("\n", $this->callbacks) . "\n";

		// Export the public API
		if (!empty($this->exportMethods))
		{
			$methods = [];
			foreach ($this->exportMethods as $method)
			{
				$methods[] = "'" . $method . "':" . $method;
			}

			$src .= "window['s9e'] = { 'TextFormatter': {" . implode(',', $methods) . "} }\n";
		}

		// Minify the source
		$src = $this->getMinifier()->get($src);

		return $src;
	}

	/**
	* Set the cached instance of Minifier
	*
	* Extra arguments will be passed to the minifier's constructor
	*
	* @param  string|Minifier $minifier Name of a supported minifier, or an instance of Minifier
	* @return Minifier                  The new minifier
	*/
	public function setMinifier($minifier)
	{
		if (is_string($minifier))
		{
			$className = __NAMESPACE__ . '\\JavaScript\\Minifiers\\' . $minifier;

			// Pass the extra argument to the constructor, if applicable
			$args = array_slice(func_get_args(), 1);
			if ($args)
			{
				$reflection = new ReflectionClass($className);
				$minifier   = $reflection->newInstanceArgs($args);
			}
			else
			{
				$minifier = new $className;
			}
		}

		$this->minifier = $minifier;

		return $minifier;
	}

	//==========================================================================
	// Internal
	//==========================================================================

	/**
	* Convert a bitfield to the JavaScript representationg of an array of number
	*
	* Context bitfields are stored as binary strings, but JavaScript doesn't really have binary
	* strings so instead we split up that string in 4-bytes chunk, which we represent in hex
	* notation to avoid the number overflowing to a float in 32bit PHP
	*
	* @param  string $bitfield Raw bytes
	* @return Code             JavaScript code
	*/
	static protected function convertBitfield($bitfield)
	{
		$hex = [];

		foreach (str_split($bitfield, 4) as $quad)
		{
			$v = '';
			foreach (str_split($quad, 1) as $c)
			{
				$v = sprintf('%02X', ord($c)) . $v;
			}

			$hex[] = '0x' . $v;
		}

		$code = new Code('[' . implode(',', $hex) . ']');

		return $code;
	}

	/**
	* Generate a HINT object that contains informations about the configuration
	*
	* @param  string $xsl XSL stylesheet used for rendering
	* @return string      JavaScript Code
	*/
	protected function getHints($xsl)
	{
		$hints = [
			'attributeGenerator'      => 0,
			'attributeDefaultValue'   => 0,
			'closeAncestor'           => 0,
			'closeParent'             => 0,
			'fosterParent'            => 0,
			'postProcessing'          => 1,
			'regexpLimitActionAbort'  => 0,
			'regexpLimitActionIgnore' => 0,
			'regexpLimitActionWarn'   => 0,
			'requireAncestor'         => 0
		];

		// Test for post-processing in templates. Theorically allows for false positives and
		// false negatives, but not in any realistic setting
		if (strpos($xsl, 'data-s9e-livepreview-postprocess') === false)
		{
			$hints['postProcessing'] = 0;
		}

		// Test each plugin's regexpLimitAction
		foreach ($this->config['plugins'] as $pluginConfig)
		{
			if (isset($pluginConfig['regexpLimitAction']))
			{
				$hintName = 'regexpLimitAction' . ucfirst($pluginConfig['regexpLimitAction']);
				if (isset($hints[$hintName]))
				{
					$hints[$hintName] = 1;
				}
			}
		}

		$flags = 0;
		foreach ($this->config['tags'] as $tagConfig)
		{
			// Testing which rules are in use. First we aggregate the flags set on all the tags and
			// test for the presence of other rules at the tag level
			foreach ($tagConfig['rules'] as $k => $v)
			{
				if ($k === 'flags')
				{
					$flags |= $v;
				}
				elseif (isset($hints[$k]))
				{
					// This will set HINT.closeAncestor and others
					$hints[$k] = 1;
				}
			}

			// Test the presence of an attribute generator, and an attribute's defaultValue
			if (!empty($tagConfig['attributes']))
			{
				foreach ($tagConfig['attributes'] as $attrConfig)
				{
					if (isset($attrConfig['generator']))
					{
						$hints['attributeGenerator'] = 1;
					}

					if (isset($attrConfig['defaultValue']))
					{
						$hints['attributeDefaultValue'] = 1;
					}
				}
			}
		}

		// Add the flags from the root context
		$flags |= $this->config['rootContext']['flags'];

		// Iterate over Parser::RULE_* constants and test which flags are set
		$parser = new ReflectionClass('s9e\\TextFormatter\\Parser');
		foreach ($parser->getConstants() as $constName => $constValue)
		{
			if (substr($constName, 0, 5) === 'RULE_')
			{
				// This will set HINT.RULE_AUTO_CLOSE and others
				$hints[$constName] = (bool) ($flags & $constValue);
			}
		}

		// Build the source. Note that Closure Compiler seems to require that each of HINT's
		// properties be declared as a const
		$js = "/** @const */ var HINT={};\n";
		foreach ($hints as $hintName => $hintValue)
		{
			$js .= '/** @const */ HINT.' . $hintName . '=' . self::encode($hintValue) . ";\n";
		}

		return $js;
	}

	/**
	* Get the JavaScript representation of the plugins
	*
	* @return Code JavaScript code
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

			// Not needed in JavaScript
			unset($pluginConfig['className']);

			// Ensure that quickMatch is UTF-8 if present
			if (isset($pluginConfig['quickMatch']))
			{
				// Well-formed UTF-8 sequences
				$valid = [
					'[[:ascii:]]',
					// [1100 0000-1101 1111] [1000 0000-1011 1111]
					'[\\xC0-\\xDF][\\x80-\\xBF]',
					// [1110 0000-1110 1111] [1000 0000-1011 1111]{2}
					'[\\xE0-\\xEF][\\x80-\\xBF]{2}',
					// [1111 0000-1111 0111] [1000 0000-1011 1111]{3}
					'[\\xF0-\\xF7][\\x80-\\xBF]{3}'
				];

				$regexp = '#(?>' . implode('|', $valid) . ')+#';

				// Keep only the first valid sequence of UTF-8, or unset quickMatch if none is found
				if (preg_match($regexp, $pluginConfig['quickMatch'], $m))
				{
					$pluginConfig['quickMatch'] = $m[0];
				}
				else
				{
					unset($pluginConfig['quickMatch']);
				}
			}

			/**
			* @var array Keys of elements that are kept in the global scope. Everything else will be
			*            moved into the plugin's parser
			*/
			$globalKeys = [
				'parser'            => 1,
				'quickMatch'        => 1,
				'regexp'            => 1,
				'regexpLimit'       => 1,
				'regexpLimitAction' => 1
			];

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
	* Generate a JavaScript representation of the registered vars
	*
	* @return Code JavaScript source code
	*/
	protected function getRegisteredVarsConfig()
	{
		$registeredVars = $this->config['registeredVars'];

		// Remove cacheDir from the registered vars. Not only it is useless in JavaScript, it could
		// leak some informations about the server
		unset($registeredVars['cacheDir']);

		return new Code(self::encode(new Dictionary($registeredVars)));
	}

	/**
	* Generate a JavaScript representation of the root context
	*
	* @return Code JavaScript source code
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
	* Generate a JavaScript representation of the tags' config
	*
	* @return Code JavaScript source code
	*/
	protected function getTagsConfig()
	{
		// Replace callback arrays with JavaScript code
		$this->replaceCallbacks();

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
	* Encode a PHP value into an equivalent JavaScript representation
	*
	* @param  mixed  $value Original value
	* @return string        JavaScript representation
	*/
	public static function encode($value)
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

		if ($value instanceof RegexpObject)
		{
			$value = $value->toJS();
		}

		if ($value instanceof RegExp
		 || $value instanceof Code)
		{
			// Rely on RegExp::__toString() and Code::__toString()
			return (string) $value;
		}

		if (!is_array($value) && !($value instanceof Dictionary))
		{
			throw new RuntimeException('Cannot encode non-scalar value');
		}

		if ($value instanceof Dictionary)
		{
			// For some reason, ArrayObject will omit elements whose key is an empty string or a
			// NULL byte, so we'll use its array copy instead
			$value = $value->getArrayCopy();
			$preserveKeys = true;
		}
		else
		{
			$preserveKeys = false;
		}

		$isArray = (!$preserveKeys && array_keys($value) === range(0, count($value) - 1));

		$src = ($isArray) ? '[' : '{';
		$sep = '';

		foreach ($value as $k => $v)
		{
			$src .= $sep;

			if (!$isArray)
			{
				$src .= (($preserveKeys || !self::isLegalProp($k)) ? json_encode($k) : $k) . ':';
			}

			$src .= self::encode($v);
			$sep = ',';
		}

		// Close that structure
		$src .= ($isArray) ? ']' : '}';

		return $src;
	}

	/**
	* Test whether a string can be used as a property name, unquoted
	*
	* @link http://es5.github.io/#A.1
	*
	* @param  string $name Property's name
	* @return bool
	*/
	public static function isLegalProp($name)
	{
		/**
		* @link https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Reserved_Words
		* @link http://www.crockford.com/javascript/survey.html
		*/
		$reserved = ['abstract', 'boolean', 'break', 'byte', 'case', 'catch', 'char', 'class', 'const', 'continue', 'debugger', 'default', 'delete', 'do', 'double', 'else', 'enum', 'export', 'extends', 'false', 'final', 'finally', 'float', 'for', 'function', 'goto', 'if', 'implements', 'import', 'in', 'instanceof', 'int', 'interface', 'let', 'long', 'native', 'new', 'null', 'package', 'private', 'protected', 'public', 'return', 'short', 'static', 'super', 'switch', 'synchronized', 'this', 'throw', 'throws', 'transient', 'true', 'try', 'typeof', 'var', 'void', 'volatile', 'while', 'with'];

		if (in_array($name, $reserved, true))
		{
			return false;
		}

		return (bool) preg_match('#^[$_\\pL][$_\\pL\\pNl]+$#Du', $name);
	}

	/**
	* Replace the callbacks in the config with their JavaScript representation
	*
	* @return void
	*/
	protected function replaceCallbacks()
	{
		foreach ($this->config['tags'] as &$tagConfig)
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
	* Convert a callback array into JavaScript code
	*
	* Will create entries in $this->callbacks
	*
	* @param  string $callbackType   Type of callback: either "attributeFilter",
	*                                "attributeGenerator" or "tagFilter"
	* @param  array  $callbackConfig Callback's config
	* @return Code                   The name of the function representing this callback
	*/
	protected function convertCallback($callbackType, array $callbackConfig)
	{
		$callback = $callbackConfig['callback'];
		$params   = (isset($callbackConfig['params'])) ? $callbackConfig['params'] : [];

		// Prepare the code for this callback. If we don't have a JavaScript implementation of this
		// filter, we make it return FALSE unconditionally
		$jsCallback = '(function(){return false;})';

		if (isset($callbackConfig['js']))
		{
			// Use the JavaScript source code that was set in the callback. Put it in parentheses to
			// ensure we can use it in our "return" statement without worrying about empty lines or
			// comments at the beginning
			$jsCallback = '(' . $callbackConfig['js'] . ')';
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
		}

		// List of arguments (and their type) for each type of callbacks. MUST be kept in sync with
		// the invocations in Parser.js
		$arguments = [
			'attributeFilter' => [
				'attrValue' => '*',
				'attrName'  => '!string'
			],
			'attributeGenerator' => [
				'attrName'  => '!string'
			],
			'tagFilter' => [
				'tag'       => '!Tag',
				'tagConfig' => '!Object'
			]
		];

		// Generate the function that will call the callback with the right signature. The function
		// name is a hash of its content so we start with the first parenthesis after the function
		// name in the function definition, which will prepend once we know what it is
		$js = '(' . implode(',', array_keys($arguments[$callbackType])) . '){'
		    . 'return ' . $jsCallback . '(';

		// Add this callback's params
		$sep = '';
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
				// it's not one of the global vars "logger", "openTags" and "registeredVars" then we
				// assume that it's a variable registered in registeredVars
				if (!isset($arguments[$callbackType][$k])
				 && $k !== 'logger'
				 && $k !== 'openTags'
				 && $k !== 'registeredVars')
				{
					$k = 'registeredVars[' . json_encode($k) . ']';
				}

				$js .= $k;
			}
		}

		// Close the list of arguments and the function body
		$js .= ');}';

		// Prepare the function's header
		$header = "/**\n";
		foreach ($arguments[$callbackType] as $paramName => $paramType)
		{
			$header .= '* @param {' . $paramType . '} ' . $paramName . "\n";
		}
		$header .= "*/\n";

		// Compute the function's name
		$funcName = sprintf('c%08X', crc32($js));

		// Prepend the function header and the fill the missing part of the function definition
		$js = $header . 'function ' . $funcName . $js;

		// Save the callback
		$this->callbacks[$funcName] = $js;

		return new Code($funcName);
	}
}