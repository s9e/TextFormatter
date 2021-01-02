<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;

use ReflectionClass;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\JavaScript\CallbackGenerator;
use s9e\TextFormatter\Configurator\JavaScript\Code;
use s9e\TextFormatter\Configurator\JavaScript\ConfigOptimizer;
use s9e\TextFormatter\Configurator\JavaScript\Dictionary;
use s9e\TextFormatter\Configurator\JavaScript\Encoder;
use s9e\TextFormatter\Configurator\JavaScript\HintGenerator;
use s9e\TextFormatter\Configurator\JavaScript\Minifier;
use s9e\TextFormatter\Configurator\JavaScript\Minifiers\Noop;
use s9e\TextFormatter\Configurator\JavaScript\RegexpConvertor;
use s9e\TextFormatter\Configurator\JavaScript\StylesheetCompressor;
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
	* @var ConfigOptimizer
	*/
	protected $configOptimizer;

	/**
	* @var Configurator Configurator this instance belongs to
	*/
	protected $configurator;

	/**
	* @var Encoder
	*/
	public $encoder;

	/**
	* @var array List of methods and properties to be exported in the s9e.TextFormatter object
	*/
	public $exports = [
		'disablePlugin',
		'disableTag',
		'enablePlugin',
		'enableTag',
		'getLogger',
		'parse',
		'preview',
		'registeredVars',
		'setNestingLimit',
		'setParameter',
		'setTagLimit'
	];

	/**
	* @var HintGenerator
	*/
	protected $hintGenerator;

	/**
	* @var Minifier Instance of Minifier used to minify the JavaScript parser
	*/
	protected $minifier;

	/**
	* @var StylesheetCompressor
	*/
	protected $stylesheetCompressor;

	/**
	* @var string Stylesheet used for rendering
	*/
	protected $xsl;

	/**
	* Constructor
	*
	* @param  Configurator $configurator Configurator
	*/
	public function __construct(Configurator $configurator)
	{
		$this->encoder              = new Encoder;
		$this->callbackGenerator    = new CallbackGenerator;
		$this->configOptimizer      = new ConfigOptimizer($this->encoder);
		$this->configurator         = $configurator;
		$this->hintGenerator        = new HintGenerator;
		$this->stylesheetCompressor = new StylesheetCompressor;
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
		$this->configOptimizer->reset();

		// Get the stylesheet used for rendering
		$xslt      = new XSLT;
		$xslt->normalizer->remove('RemoveLivePreviewAttributes');
		$this->xsl = $xslt->getXSL($this->configurator->rendering);

		// Prepare the parser's config
		$this->config = (isset($config)) ? $config : $this->configurator->asConfig();
		$this->config = ConfigHelper::filterConfig($this->config, 'JS');
		$this->config = $this->callbackGenerator->replaceCallbacks($this->config);

		// Get the parser's source and inject its config
		$src = $this->getHints() . $this->injectConfig($this->getSource());

		// Export the public API
		$src .= "if (!window['s9e']) window['s9e'] = {};\n" . $this->getExports();

		// Minify the source
		$src = $this->getMinifier()->get($src);

		// Wrap the source in a function to protect the global scope
		$src = '(function(){' . $src . '})();';

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
	* Encode a PHP value into an equivalent JavaScript representation
	*
	* @param  mixed  $value Original value
	* @return string        JavaScript representation
	*/
	protected function encode($value)
	{
		return $this->encoder->encode($value);
	}

	/**
	* Generate and return the public API
	*
	* @return string JavaScript Code
	*/
	protected function getExports()
	{
		if (empty($this->exports))
		{
			return '';
		}

		$exports = [];
		foreach ($this->exports as $export)
		{
			$exports[] = "'" . $export . "':" . $export;
		}
		sort($exports);

		return "window['s9e']['TextFormatter'] = {" . implode(',', $exports) . '};';
	}

	/**
	* Generate a HINT object that contains informations about the configuration
	*
	* @return string JavaScript Code
	*/
	protected function getHints()
	{
		$this->hintGenerator->setConfig($this->config);
		$this->hintGenerator->setPlugins($this->configurator->plugins);
		$this->hintGenerator->setXSL($this->xsl);

		return $this->hintGenerator->getHints();
	}

	/**
	* Return the plugins' config
	*
	* @return Dictionary
	*/
	protected function getPluginsConfig()
	{
		$plugins = new Dictionary;

		foreach ($this->config['plugins'] as $pluginName => $pluginConfig)
		{
			if (!isset($pluginConfig['js']))
			{
				// Skip this plugin
				continue;
			}
			$js = $pluginConfig['js'];
			unset($pluginConfig['js']);

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
				'quickMatch'  => 1,
				'regexp'      => 1,
				'regexpLimit' => 1
			];

			$globalConfig = array_intersect_key($pluginConfig, $globalKeys);
			$localConfig  = array_diff_key($pluginConfig, $globalKeys);

			if (isset($globalConfig['regexp']) && !($globalConfig['regexp'] instanceof Code))
			{
				$globalConfig['regexp'] = new Code(RegexpConvertor::toJS($globalConfig['regexp'], true));
			}

			$globalConfig['parser'] = new Code(
				'/**
				* @param {string}          text
				* @param {!Array.<!Array>} matches
				*/
				function(text, matches)
				{
					/** @const */
					var config=' . $this->encode($localConfig) . ';
					' . $js . '
				}'
			);

			$plugins[$pluginName] = $globalConfig;
		}

		return $plugins;
	}

	/**
	* Return the registeredVars config
	*
	* @return Dictionary
	*/
	protected function getRegisteredVarsConfig()
	{
		$registeredVars = $this->config['registeredVars'];

		// Remove cacheDir from the registered vars. Not only it is useless in JavaScript, it could
		// leak some informations about the server
		unset($registeredVars['cacheDir']);

		return new Dictionary($registeredVars);
	}

	/**
	* Return the root context config
	*
	* @return array
	*/
	protected function getRootContext()
	{
		return $this->config['rootContext'];
	}

	/**
	* Return the parser's source
	*
	* @return string
	*/
	protected function getSource()
	{
		$rootDir = __DIR__ . '/..';
		$src     = '';

		// If getLogger() is not exported we use a dummy Logger that can be optimized away
		$logger = (in_array('getLogger', $this->exports)) ? 'Logger.js' : 'NullLogger.js';

		// Prepare the list of files
		$files   = glob($rootDir . '/Parser/AttributeFilters/*.js');
		$files[] = $rootDir . '/Parser/utils.js';
		$files[] = $rootDir . '/Parser/FilterProcessing.js';
		$files[] = $rootDir . '/Parser/' . $logger;
		$files[] = $rootDir . '/Parser/Tag.js';
		$files[] = $rootDir . '/Parser.js';

		// Append render.js if we export the preview method
		if (in_array('preview', $this->exports, true))
		{
			$files[] = $rootDir . '/render.js';
			$src .= '/** @const */ var xsl=' . $this->getStylesheet() . ";\n";
		}

		$src .= implode("\n", array_map('file_get_contents', $files));

		return $src;
	}

	/**
	* Return the JavaScript representation of the stylesheet
	*
	* @return string
	*/
	protected function getStylesheet()
	{
		return $this->stylesheetCompressor->encode($this->xsl);
	}

	/**
	* Return the tags' config
	*
	* @return Dictionary
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

		return $tags;
	}

	/**
	* Inject the parser config into given source
	*
	* @param  string $src Parser's source
	* @return string      Modified source
	*/
	protected function injectConfig($src)
	{
		$config = array_map(
			[$this, 'encode'],
			$this->configOptimizer->optimize(
				[
					'plugins'        => $this->getPluginsConfig(),
					'registeredVars' => $this->getRegisteredVarsConfig(),
					'rootContext'    => $this->getRootContext(),
					'tagsConfig'     => $this->getTagsConfig()
				]
			)
		);

		$src = preg_replace_callback(
			'/(\\nvar (' . implode('|', array_keys($config)) . '))(;)/',
			function ($m) use ($config)
			{
				return $m[1] . '=' . $config[$m[2]] . $m[3];
			},
			$src
		);

		// Prepend the deduplicated objects
		$src = $this->configOptimizer->getVarDeclarations() . $src;

		return $src;
	}
}