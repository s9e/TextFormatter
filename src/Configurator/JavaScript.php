<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;

use ReflectionClass;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\JavaScript\CallbackGenerator;
use s9e\TextFormatter\Configurator\JavaScript\Code;
use s9e\TextFormatter\Configurator\JavaScript\Dictionary;
use s9e\TextFormatter\Configurator\JavaScript\Encoder;
use s9e\TextFormatter\Configurator\JavaScript\Minifier;
use s9e\TextFormatter\Configurator\JavaScript\Minifiers\Noop;
use s9e\TextFormatter\Configurator\JavaScript\RegexpConvertor;
use s9e\TextFormatter\Configurator\RendererGenerators\XSLT;

class JavaScript
{
	/**
	* @var CallbackGenerator
	*/
	protected $callbackGenerator;

	/**
	* @var array Configuration, filtered for JavaScript
	*/
	protected $config;

	/**
	* @var Configurator Configurator this instance belongs to
	*/
	protected $configurator;

	/**
	* @var Encoder
	*/
	public $encoder;

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
		$this->callbackGenerator = new CallbackGenerator;
		$this->configurator      = $configurator;
		$this->encoder           = new Encoder;
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

		// Replace callback arrays with JavaScript code
		$this->config = $this->callbackGenerator->replaceCallbacks($this->config);

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
			$js .= '/** @const */ HINT.' . $hintName . '=' . $this->encode($hintValue) . ";\n";
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

			if (isset($globalConfig['regexp']) && !($globalConfig['regexp'] instanceof Code))
			{
				$globalConfig['regexp'] = RegexpConvertor::toJS($globalConfig['regexp'], true);
			}

			$globalConfig['parser'] = new Code(
				'/**
				* @param {!string} text
				* @param {!Array.<Array>} matches
				*/
				function(text, matches)
				{
					/** @const */
					var config=' . $this->encode($localConfig) . ';
					' . $globalConfig['parser'] . '
				}'
			);

			$plugins[$pluginName] = $globalConfig;
		}

		return $this->encode($plugins);
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

		return $this->encode(new Dictionary($registeredVars));
	}

	/**
	* Generate a JavaScript representation of the root context
	*
	* @return string JavaScript source code
	*/
	protected function getRootContext()
	{
		return $this->encode($this->config['rootContext']);
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

		return $this->encode($tags);
	}

	/**
	* Encode a PHP value into an equivalent JavaScript representation
	*
	* @param  mixed  $value Original value
	* @return string        JavaScript representation
	*/
	public function encode($value)
	{
		return $this->encoder->encode($value);
	}

	/**
	* Inject the parser config into given source
	*
	* @param  string &$src Parser's source, by reference
	* @return void
	*/
	protected function injectConfig(&$src)
	{
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

		// Append the functions from filters and generators
		$src .= "\n" . implode("\n", $this->callbackGenerator->getFunctions()) . "\n";
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