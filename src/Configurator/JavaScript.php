<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
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
	* @var array Associative array of [hint name => 1 or 0]
	*/
	protected $hints;

	/**
	* @var Minifier Instance of Minifier used to minify the JavaScript parser
	*/
	protected $minifier;

	/**
	* @var string Stylesheet used for rendering
	*/
	protected $xsl;

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
	* @param  array  $config Config array returned by the configurator
	* @return string         JavaScript parser
	*/
	public function getParser(array $config = null)
	{
		// Store the parser's config
		$this->config = (isset($config)) ? $config : $this->configurator->asConfig();
		ConfigHelper::filterVariants($this->config, 'JS');

		// Get parser's source
		$src = $this->getSource();

		// Inject the parser config
		$this->injectConfig($src);

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

		// Wrap the source in a function to protect the global scope
		$src = '(function(){' . $src . '})()';

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
			if (!empty($args))
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
	* Generate a HINT object that contains informations about the configuration
	*
	* @return string JavaScript Code
	*/
	protected function getHints()
	{
		$this->hints = [];
		$this->setRenderingHints();
		$this->setRulesHints();
		$this->setTagsHints();

		// Build the source. Note that Closure Compiler seems to require that each of HINT's
		// properties be declared as a const
		ksort($this->hints);
		$js = "/** @const */ var HINT={};\n";
		foreach ($this->hints as $hintName => $hintValue)
		{
			$js .= '/** @const */ HINT.' . $hintName . '=' . self::encode($hintValue) . ";\n";
		}

		return $js;
	}

	/**
	* Get the JavaScript representation of the plugins
	*
	* @return string JavaScript code
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
				'parser'      => 1,
				'quickMatch'  => 1,
				'regexp'      => 1,
				'regexpLimit' => 1
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

			$globalConfig['parser'] = new Code(
				'/**
				* @param {!string} text
				* @param {!Array.<Array>} matches
				*/
				function(text, matches)
				{
					/** @const */
					var config=' . self::encode($localConfig) . ';
					' . $globalConfig['parser'] . '
				}'
			);

			$plugins[$pluginName] = $globalConfig;
		}

		return self::encode($plugins);
	}

	/**
	* Generate a JavaScript representation of the registered vars
	*
	* @return string JavaScript source code
	*/
	protected function getRegisteredVarsConfig()
	{
		$registeredVars = $this->config['registeredVars'];

		// Remove cacheDir from the registered vars. Not only it is useless in JavaScript, it could
		// leak some informations about the server
		unset($registeredVars['cacheDir']);

		return self::encode(new Dictionary($registeredVars));
	}

	/**
	* Generate a JavaScript representation of the root context
	*
	* @return string JavaScript source code
	*/
	protected function getRootContext()
	{
		return self::encode($this->config['rootContext']);
	}

	/**
	* Return the parser's source
	*
	* @return string
	*/
	protected function getSource()
	{
		$files = [
			'Parser/utils.js',
			'Parser/BuiltInFilters.js',
			// If getLogger() is not exported we use a dummy Logger that can be optimized away
			'Parser/' . (in_array('getLogger', $this->exportMethods) ? '' : 'Null') . 'Logger.js',
			'Parser/Tag.js',
			'Parser.js'
		];

		// Append render.js if we export the preview method
		if (in_array('preview', $this->exportMethods, true))
		{
			$files[] = 'render.js';
		}

		// Get the stylesheet used for rendering
		$this->xsl = (new XSLT)->getXSL($this->configurator->rendering);

		// Start with the generated HINTs
		$src = $this->getHints();

		foreach ($files as $filename)
		{
			if ($filename === 'render.js')
			{
				// Insert the stylesheet if we include the renderer
				$src .= '/** @const */ var xsl=' . json_encode($this->xsl) . ";\n";
			}

			$filepath = __DIR__ . '/../' . $filename;
			$src .= file_get_contents($filepath) . "\n";
		}

		return $src;
	}

	/**
	* Generate a JavaScript representation of the tags' config
	*
	* @return string JavaScript source code
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

			$tags[$tagName] = $tagConfig;
		}

		return self::encode($tags);
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
				// Represent true/false as !0/!1
				return ($value) ? '!0' : '!1';
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
	* Inject the parser config into given source
	*
	* @param  string &$src Parser's source, by reference
	* @return void
	*/
	protected function injectConfig(&$src)
	{
		// Reset this instance's callbacks
		$this->callbacks = [];

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
		foreach ($this->config['tags'] as $tagName => $tagConfig)
		{
			$this->config['tags'][$tagName] = $this->replaceCallbacksInTagConfig($tagConfig);
		}
	}

	/**
	* Replace callbacks in given attribute config array
	*
	* @param  array $config Original config
	* @return array         Modified config
	*/
	protected function replaceCallbacksInAttributeConfig(array $config)
	{
		if (isset($config['filterChain']))
		{
			foreach ($config['filterChain'] as $i => $filter)
			{
				$config['filterChain'][$i] = $this->convertCallback('attributeFilter', $filter);
			}
		}

		if (isset($config['generator']))
		{
			$config['generator'] = $this->convertCallback('attributeGenerator', $config['generator']);
		}

		return $config;
	}

	/**
	* Replace callbacks in given tag config array
	*
	* @param  array $config Original config
	* @return array         Modified config
	*/
	protected function replaceCallbacksInTagConfig(array $config)
	{
		if (isset($config['filterChain']))
		{
			foreach ($config['filterChain'] as $i => $filter)
			{
				$config['filterChain'][$i] = $this->convertCallback('tagFilter', $filter);
			}
		}

		if (isset($config['attributes']))
		{
			foreach ($config['attributes'] as $attrName => $attrConfig)
			{
				$config['attributes'][$attrName] = $this->replaceCallbacksInAttributeConfig($attrConfig);
			}
		}

		return $config;
	}

	/**
	* Build the list of arguments used in a callback invocation
	*
	* @param  array  $params    Callback parameters
	* @param  array  $localVars Known vars from the calling scope
	* @return string            JavaScript code
	*/
	protected function buildCallbackArguments(array $params, array $localVars)
	{
		// Remove 'parser' as a parameter, since there's no such thing in JavaScript
		unset($params['parser']);

		// Add global vars to the list of vars in scope
		$localVars += ['logger' => 1, 'openTags' => 1, 'registeredVars' => 1];

		$args = [];
		foreach ($params as $k => $v)
		{
			if (isset($v))
			{
				// Param by value
				$args[] = self::encode($v);
			}
			elseif (isset($localVars[$k]))
			{
				// Param by name that matches a local var
				$args[] = $k;
			}
			else
			{
				$args[] = 'registeredVars[' . json_encode($k) . ']';
			}
		}

		return implode(',', $args);
	}

	/**
	* Convert a callback array into JavaScript code
	*
	* Will create entries in $this->callbacks
	*
	* @param  string $type   Either one of: "attributeFilter", "attributeGenerator" or "tagFilter"
	* @param  array  $config Callback's config
	* @return Code           The name of the function representing this callback
	*/
	protected function convertCallback($type, array $config)
	{
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

		// Prepare the function's header
		$header = "/**\n";
		foreach ($arguments[$type] as $paramName => $paramType)
		{
			$header .= '* @param {' . $paramType . '} ' . $paramName . "\n";
		}
		$header .= "*/\n";

		// Generate the function that will call the callback with the right signature. The function
		// name is a hash of its content so we start with the first parenthesis after the function
		// name in the function definition, which will prepend once we know what it is
		$params   = (isset($config['params'])) ? $config['params'] : [];
		$callback = $this->getJavaScriptCallback($config);

		$js = '(' . implode(',', array_keys($arguments[$type])) . '){return ' . $callback . '(' . $this->buildCallbackArguments($params, $arguments[$type]) . ');}';

		// Compute the function's name
		$funcName = sprintf('c%08X', crc32($js));

		// Prepend the function header and the fill the missing part of the function definition
		$js = $header . 'function ' . $funcName . $js . "\n";

		// Save the callback
		$this->callbacks[$funcName] = $js;

		return new Code($funcName);
	}

	/**
	* Get the JavaScript callback that corresponds to given config
	*
	* @param  array  $callbackConfig
	* @return string
	*/
	protected function getJavaScriptCallback(array $callbackConfig)
	{
		if (isset($callbackConfig['js']))
		{
			// Use the JavaScript source code that was set in the callback. Put it in parentheses to
			// ensure we can use it in our "return" statement without worrying about empty lines or
			// comments at the beginning
			return '(' . $callbackConfig['js'] . ')';
		}

		$callback = $callbackConfig['callback'];
		if (is_string($callback))
		{
			if (substr($callback, 0, 41) === 's9e\\TextFormatter\\Parser\\BuiltInFilters::')
			{
				// BuiltInFilters::filterNumber => BuiltInFilters.filterNumber
				return 'BuiltInFilters.' . substr($callback, 41);
			}
			elseif (substr($callback, 0, 26) === 's9e\\TextFormatter\\Parser::')
			{
				// Parser::filterAttributes => filterAttributes
				return substr($callback, 26);
			}
		}

		// If there's no JS callback available, return FALSE unconditionally
		return 'returnFalse';
	}

	/**
	* Set hints related to rules
	*
	* @return void
	*/
	protected function setRulesHints()
	{
		$this->hints['closeAncestor']   = 0;
		$this->hints['closeParent']     = 0;
		$this->hints['fosterParent']    = 0;
		$this->hints['requireAncestor'] = 0;

		$flags = 0;
		foreach ($this->config['tags'] as $tagConfig)
		{
			// Test which rules are in use
			foreach (array_intersect_key($tagConfig['rules'], $this->hints) as $k => $v)
			{
				$this->hints[$k] = 1;
			}
			$flags |= $tagConfig['rules']['flags'];
		}
		$flags |= $this->config['rootContext']['flags'];

		// Iterate over Parser::RULE_* constants and test which flags are set
		$parser = new ReflectionClass('s9e\\TextFormatter\\Parser');
		foreach ($parser->getConstants() as $constName => $constValue)
		{
			if (substr($constName, 0, 5) === 'RULE_')
			{
				// This will set HINT.RULE_AUTO_CLOSE and others
				$this->hints[$constName] = ($flags & $constValue) ? 1 : 0;
			}
		}
	}

	/**
	* Set hints based on given tag's attributes config
	*
	* @param  array $tagConfig
	* @return void
	*/
	protected function setTagAttributesHints(array $tagConfig)
	{
		if (empty($tagConfig['attributes']))
		{
			return;
		}

		foreach ($tagConfig['attributes'] as $attrConfig)
		{
			$this->hints['attributeGenerator']    |= isset($attrConfig['generator']);
			$this->hints['attributeDefaultValue'] |= isset($attrConfig['defaultValue']);
		}
	}

	/**
	* Set hints related to tags config
	*
	* @return void
	*/
	protected function setTagsHints()
	{
		$this->hints['attributeGenerator']    = 0;
		$this->hints['attributeDefaultValue'] = 0;
		$this->hints['namespaces']            = 0;
		foreach ($this->config['tags'] as $tagName => $tagConfig)
		{
			$this->hints['namespaces'] |= (strpos($tagName, ':') !== false);
			$this->setTagAttributesHints($tagConfig);
		}
	}

	/**
	* Set hints related to rendering
	*
	* @return void
	*/
	protected function setRenderingHints()
	{
		// Test for post-processing in templates. Theorically allows for false positives and
		// false negatives, but not in any realistic setting
		$this->hints['postProcessing'] = (int) (strpos($this->xsl, 'data-s9e-livepreview-postprocess') !== false);
	}
}