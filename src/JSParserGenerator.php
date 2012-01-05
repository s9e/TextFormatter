<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter;

use DOMDocument,
    DOMXPath,
    RuntimeException;

/**
* KNOWN LIMITATIONS:
*
* - only a subset of all possible PHP callbacks have a Javascript port
* - TextFormatter.js does not puny-encode IDNs
* - TextFormatter.js does not resolve redirects
* - TextFormatter.js does not support custom filters
*/
class JSParserGenerator
{
	/**
	* List of Javascript reserved words
	*
	* @link https://developer.mozilla.org/en/JavaScript/Reference/Reserved_Words
	*
	* Also, Closure Compiler doesn't like "char"
	*/
	const RESERVED_WORDS_REGEXP =
		'#^(?:break|case|catch|continue|debugger|default|delete|do|else|finally|for|function|if|in|instanceof|new|return|switch|this|throw|try|typeof|var|void|while|with|class|enum|export|extends|import|super|implements|interface|let|package|private|protected|public|static|yield|char|float|int)$#D';

	//==========================================================================
	// Properties taken from ConfigBuilder
	//==========================================================================

	/**
	* @var ConfigBuilder
	*/
	protected $cb;

	/**
	* @var string Template source
	*/
	protected $tpl;

	/**
	* @var array Tags' config
	*/
	protected $tagsConfig;

	/**
	* @var array Filters' config
	*/
	protected $filtersConfig;

	/**
	* @var array Namespaces used by tags (namespaces with no tags do not appear here)
	*/
	protected $namespaces;

	/**
	* @var array Root context
	*/
	protected $rootContext;

	/**
	* @var string XSL used for rendering
	*/
	protected $xsl;

	/**
	* @var array Array of plugins (JS source, config, config metadata)
	*/
	protected $plugins;

	//==========================================================================
	// Per-compilation vars
	//==========================================================================

	/**
	* @var array Compilation options
	*/
	protected $options;

	/**
	* @var string Source being generated
	*/
	protected $src;

	/**
	* @param ConfigBuilder $cb
	*/
	public function __construct(ConfigBuilder $cb)
	{
		$this->cb  = $cb;
		$this->tpl = file_get_contents(__DIR__ . '/TextFormatter.js');
	}

	/**
	* Load and initialize everything that's needed for a compilation
	*
	* @param  array  $options Generator options
	*/
	protected function init(array $options)
	{
		$this->options = $options + array(
			'closureCompilerURL'   => 'http://closure-compiler.appspot.com/compile',
			'compilationLevel'     => 'none',
			'disableAPI'           => array(),
			'disableLogTypes'      => array(),
			'enableIE'             => true,
			'enableIE7'            => true,
			'enableIE9'            => true,
			'escapeScriptEndTag'   => true,
			'setOptimizationHints' => false,
			'unsafeMinification'   => false,
			'xslNamespacePrefix'   => 'xsl'
		);

		$this->src     = $this->tpl;
		$this->xsl     = $this->cb->getXSL($this->options['xslNamespacePrefix']);
		$this->plugins = $this->cb->getJSPlugins();

		$config = $this->cb->getParserConfig();

		$this->tagsConfig    = $config['tags'];
		$this->filtersConfig = $config['filters'];
		$this->namespaces    = (isset($config['namespaces'])) ? $config['namespaces'] : array();
		$this->rootContext   = $config['rootContext'];
	}

	/**
	* Generate and return the JS parser
	*
	* @param  array  $options Generator options
	* @return string
	*/
	public function get(array $options = array())
	{
		$this->init($options);

		if ($this->options['setOptimizationHints'])
		{
			$this->setOptimizationHints();
		}

		/**
		* Inject the config objects, as well as plugins
		*/
		$this->injectConfig();

		/**
		* Rename properties whose name is preserved by Google Closure Compiler by default.
		* For instance, "tag.name" will be renamed "tag._name" so that Google Closure Compiler will
		* rename it to a shorter form. This operation is **UNSAFE** as its name implies, because it
		* doesn't have any context.
		*/
		if ($this->options['unsafeMinification'])
		{
			$this->renameReservedProperties();
		}

		/**
		* Add the XSL
		*/
		$this->injectXSL();

		/**
		* Compile using Google Closure Compiler
		*/
		if ($this->options['compilationLevel'] !== 'none')
		{
			$this->compile();
		}

		/**
		* NOTE: json_encode() and Google Closure Compiler also escape them, so this block is more
		*       of a failsafe than anything
		*/
		if ($this->options['escapeScriptEndTag'])
		{
			$this->src = preg_replace('#</(script)#i', '<\\/$1', $this->src);
		}

		return $this->src;
	}

	//==========================================================================
	// Optimization hints
	//==========================================================================

	/**
	* Gather optimization hints then inject them into the parser source
	*
	* @return void
	*/
	protected function setOptimizationHints()
	{
		$hints = array(
			'attrConfig'            => $this->getAttributesConfigHints(),
			'disabledAPI'           => $this->getDisabledAPIHints(),
			'disabledLogTypes'      => array(
				'debug'   => in_array('debug',   $this->options['disableLogTypes'], true),
				'warning' => in_array('warning', $this->options['disableLogTypes'], true),
				'error'   => in_array('error',   $this->options['disableLogTypes'], true)
			),
			'enableIE'              => (bool) $this->options['enableIE'],
			'enableIE7'             => $this->options['enableIE'] && $this->options['enableIE7'],
			'enableIE9'             => $this->options['enableIE'] && $this->options['enableIE9'],
			'filterConfig'          => $this->getFiltersConfigHints(),
			'hasCompoundAttributes' => $this->hasCompoundAttributes(),
			'hasNamespacedHTML'     => $this->hasNamespacedHTML(),
			'hasNamespacedTags'     => $this->hasNamespacedTags(),
			'hasRegexpLimitAction'  => $this->getRegexpLimitActionHints(),
			'mightUseTagRequires'   => $this->mightUseTagRequires(),
			'tagConfig'             => $this->getTagConfigHints()
		);

		// Add attribute types hints
		$hints += $this->getFiltersHints();

		// Inject the hints into the source
		$this->src = preg_replace(
			'#// START OF STOCK HINTS - DO NOT EDIT.*?// END OF STOCK HINTS - DO NOT EDIT#s',
			"/**@const*/var HINT={};\n" . $this->flattenHints($hints, 'HINT'),
			$this->src
		);
	}

	/**
	* Flattens the hint array into a bunch of properties assignments
	*
	* @param  array  $hints
	* @param  string $prefix
	* @return string
	*/
	protected function flattenHints(array $hints, $prefix)
	{
		$str = '';
		foreach ($hints as $k => $v)
		{
			if (is_array($v))
			{
				$str .= '/**@const*/' . $prefix . '.' . $k . "={};\n";
				$str .= $this->flattenHints($v, $prefix . '.' . $k);
				$v = count(array_filter($v));
			}
			else
			{
				$str .= '/**@const*/' . $prefix . '.' . $k . '=' . ((int) ($v !== false)) . ";\n";
			}
		}

		return $str;
	}

	/**
	* Return an array of hints describing the presence of some keys in the tagsConfig object
	*
	* @return array
	*/
	protected function getAttributesConfigHints()
	{
		/**
		* Aggregate all the attributes from all tags
		*/
		$attrsConfig = array();
		foreach ($this->tagsConfig as $tagConfig)
		{
			if (empty($tagConfig['attrs']))
			{
				continue;
			}

			foreach ($tagConfig['attrs'] as $attrName => &$attrConf)
			{
				// Replace defaultValue=>0 with defaultValue=>1 so that it passes an empty() test
				if (isset($attrConf['defaultValue'])
				 && empty($attrConf['defaultValue']))
				{
					$attrConf['defaultValue'] = 1;
				}

				$attrsConfig[] = $attrConf;
			}
			unset($attrConf);
		}

		return $this->getDataStructureHints($attrsConfig, array(
			'defaultValue' => false,
			'isRequired'   => false,
			'postFilter'   => false,
			'preFilter'    => false
		));
	}

	/**
	* Return an array of hints used to disable parts of the API
	*
	* @return void
	*/
	protected function getDisabledAPIHints()
	{
		$hints = array(
			'parse'         => false,
			'render'        => false,
			'getLog'        => false,
			'enablePlugin'  => false,
			'disablePlugin' => false,
			'preview'       => false
		);

		foreach ($this->options['disableAPI'] as $methodName)
		{
			$hints[$methodName] = true;
		}

		return $hints;
	}

	/**
	* Return an array of hints describing the presence of some keys in the tagsConfig object
	*
	* @return array
	*/
	protected function getTagConfigHints()
	{
		return $this->getDataStructureHints($this->tagsConfig, array(
			'attrs'         => false,
			'isTransparent' => false,
			'ltrimContent'  => false,
			'postFilter'    => false,
			'preFilter'     => false,
			'rtrimContent'  => false,
			'rules'         => array(
				'closeAncestor'   => false,
				'closeParent'     => false,
				'reopenChild'     => false,
				'requireAncestor' => false,
				'requireParent'   => false
			),
			'trimAfter'     => false,
			'trimBefore'    => false,
		));
	}

	/**
	* Toogle hints based on given config array
	*
	* @param  array $config Config array
	* @param  array $hints  2D array of booleans
	* @return array
	*/
	protected function getDataStructureHints(array $config, array $hints)
	{
		$struct = array();

		foreach ($config as $entry)
		{
			$struct = array_merge_recursive($struct, array_filter($entry));
		}

		foreach ($hints as $hintName => &$hintValue)
		{
			if (empty($struct[$hintName]))
			{
				// This config does not have this option set, or it's false/empty
				continue;
			}

			if (is_array($hintValue))
			{
				foreach ($hintValue as $k => &$v)
				{
					if (!empty($struct[$hintName][$k]))
					{
						$v = true;
					}
				}
			}
			else
			{
				$hintValue = true;
			}
		}
		unset($hintValue);

		return $hints;
	}

	/**
	* Return whether any compound attributes exist
	*
	* @return bool
	*/
	protected function hasCompoundAttributes()
	{
		foreach ($this->tagsConfig as $tagConfig)
		{
			if (!empty($tagConfig['attrs']))
			{
				foreach ($tagConfig['attrs'] as $attrConf)
				{
					if ($attrConf['type'] === 'compound')
					{
						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	* Return whether there is any namespace tags in the config
	*
	* @return bool
	*/
	protected function hasNamespacedTags()
	{
		foreach ($this->tagsConfig as $tagName => $tagConfig)
		{
			if (strpos($tagName, ':') !== false)
			{
				return true;
			}
		}

		return false;
	}

	/**
	* Return whether there is any namespaced elements or attributes in the template
	*
	* @return bool
	*/
	protected function hasNamespacedHTML()
	{
		$xsl = new DOMDocument;
		$xsl->loadXML($this->xsl);

		$xpath = new DOMXPath($xsl);

		return $xpath->evaluate('boolean(//*[namespace-uri() != "" and namespace-uri() != "http://www.w3.org/1999/XSL/Transform"] | //@*[namespace-uri() != "" and namespace-uri() != "http://www.w3.org/1999/XSL/Transform"])');
	}

	/**
	* Return for each meaningful value of regexpLimitAction whether it is in use in any plugin
	*
	* @return array
	*/
	protected function getRegexpLimitActionHints()
	{
		$hints = array(
			'abort'  => false,
			'ignore' => false,
			'warn'   => false
		);

		foreach ($this->plugins as $plugin)
		{
			if (!isset($plugin['config']['regexp'], $plugin['config']['regexpLimitAction']))
			{
				continue;
			}

			switch ($plugin['config']['regexpLimitAction'])
			{
				case 'abort':
					$hints['abort'] = true;
					break;

				case 'ignore':
					$hints['ignore'] = true;
					break;

				default:
					$hints['warn'] = true;
			}
		}

		return $hints;
	}

	/**
	* Return a list of filters in use
	*
	* @return array
	*/
	protected function getFiltersHints()
	{
		$types = array(
			'color'      => false,
			'email'      => false,
			'float'      => false,
			'id'         => false,
			'identifier' => false,
			'int'        => false,
			'integer'    => false,
			'number'     => false,
			'range'      => false,
			'regexp'     => false,
			'simpletext' => false,
			'text'       => false,
			'uint'       => false,
			'url'        => false
		);

		foreach ($this->tagsConfig as $tagConfig)
		{
			if (!empty($tagConfig['attrs']))
			{
				foreach ($tagConfig['attrs'] as $attrConf)
				{
					$types[$attrConf['type']] = true;
				}
			}
		}

		$hints = array();

		foreach ($types as $type => $bool)
		{
			$hints['has' . ucfirst($type) . 'Attribute'] = $bool;
		}

		return $hints;
	}

	/**
	* Return a options used in filters
	*
	* @return array
	*/
	protected function getFiltersConfigHints()
	{
		$hints = array(
			'email'  => array(
				'forceUrlencode'  => false
			),
			'regexp' => array(
				'replaceWith'     => false
			),
			'url'    => array(
				'disallowedHosts' => false
			)
		);

		foreach ($this->tagsConfig as $tagConfig)
		{
			if (!empty($tagConfig['attrs']))
			{
				foreach ($tagConfig['attrs'] as $attrConf)
				{
					foreach ($attrConf as $k => $v)
					{
						if (!isset($hints[$attrConf['type']][$k])
						 || $hints[$attrConf['type']][$k] === false)
						{
							$hints[$attrConf['type']][$k] = $v;
						}
					}
				}
			}
		}

		foreach ($this->filtersConfig as $attrType => $filterConfig)
		{
			foreach ($filterConfig as $k => $v)
			{
				if (!isset($hints[$attrType][$k])
				 || $hints[$attrType][$k] === false)
				{
					$hints[$attrType][$k] = $v;
				}
			}
		}

		foreach ($hints as $attrType => &$attrConf)
		{
			unset($attrConf['defaultValue']);
			unset($attrConf['isRequired']);
			unset($attrConf['type']);

			if (empty($attrConf))
			{
				$attrConf = true;
			}
			else
			{
				foreach ($attrConf as &$value)
				{
					// false stays false, anything else (e.g. 0 or "") becomes true
					$value = (bool) ($value !== false);
				}
				unset($value);
			}
		}
		unset($attrConf);

		return array_filter($hints, 'is_array');
	}

	/**
	* Test whether any plugin may create tags with the "requires" option set
	*
	* @return bool
	*/
	protected function mightUseTagRequires()
	{
		foreach ($this->plugins as $plugin)
		{
			// Covers tag.requires
			if (preg_match('#\\.\\s*requires#', $plugin['parser']))
			{
				return true;
			}

			// Covers tag['requires'], tag[ "requires" ] plus a few theorical false-positives
			if (preg_match('#\\[\\s*([\'"])requires\\1\\s*\\]#', $plugin['parser']))
			{
				return true;
			}

			// Covers tag = { requires: ... }
			if (preg_match('#\\W([\'"]?)requires\\1\\s*:#', $plugin['parser']))
			{
				return true;
			}
		}

		return false;
	}

	//==========================================================================
	// Source generation/manipulation
	//==========================================================================

	/**
	* Generate all the required config objects and inject their source into the parser's source
	*/
	protected function injectConfig()
	{
		$configs = array(
			'callbacks'            => $this->generateCallbacks(),
			'filtersConfig'        => $this->generateFiltersConfig(),
			'pluginsConfig'        => $this->generatePluginsConfig(),
			'registeredNamespaces' => $this->generateNamespaces(),
			'rootContext'          => $this->generateRootContext(),
			'tagsConfig'           => $this->generateTagsConfig()
		);

		foreach ($configs as $k => $v)
		{
			$this->src = str_replace(
				$k . ' = {/* DO NOT EDIT */}',
				$k . '=' . $v,
				$this->src
			);
		}
	}

	/**
	* Load and generate the array of functions required by preFilter and postFilter callbacks
	*
	* @return string Javascript representation of an object
	*/
	protected function generateCallbacks()
	{
		$usedCallbacks = array();

		foreach ($this->tagsConfig as $tagConfig)
		{
			if (!empty($tagConfig['preFilter']))
			{
				foreach ($tagConfig['preFilter'] as $callbackConf)
				{
					$usedCallbacks[] = $callbackConf['callback'];
				}
			}

			if (!empty($tagConfig['postFilter']))
			{
				foreach ($tagConfig['postFilter'] as $callbackConf)
				{
					$usedCallbacks[] = $callbackConf['callback'];
				}
			}

			if (!empty($tagConfig['attrs']))
			{
				foreach ($tagConfig['attrs'] as $attrName => $attrConf)
				{
					if (!empty($attrConf['preFilter']))
					{
						foreach ($attrConf['preFilter'] as $callbackConf)
						{
							$usedCallbacks[] = $callbackConf['callback'];
						}
					}

					if (!empty($attrConf['postFilter']))
					{
						foreach ($attrConf['postFilter'] as $callbackConf)
						{
							$usedCallbacks[] = $callbackConf['callback'];
						}
					}
				}
			}
		}

		$jsCallbacks = array();

		foreach (array_unique($usedCallbacks) as $funcName)
		{
			if (!preg_match('#^[a-z_0-9]+$#Di', $funcName))
			{
				/**
				* This cannot actually happen because callbacks are validated in ConfigBuilder.
				* HOWEVER, if there was a way to get around this validation, this method could be
				* used to get the content of any file in the filesystem, so we're still validating
				* the callback name here as a failsafe.
				*/
				// @codeCoverageIgnoreStart
				throw new RuntimeException("Invalid callback name '" . $funcName . "'");
				// @codeCoverageIgnoreEnd
			}

			$filepath = __DIR__ . '/jsFunctions/' . $funcName . '.js';

			if (file_exists($filepath))
			{
				$jsCallbacks[] = json_encode($funcName) . ':' . file_get_contents($filepath);
			}
		}

		return '{' . implode(',', $jsCallbacks) . '}';
	}

	/**
	* Generate the filters config
	*
	* @return string Javascript representation of an object
	*/
	protected function generateFiltersConfig()
	{
		$filtersConfig = $this->filtersConfig;

		if (isset($filtersConfig['url']['disallowedHosts']))
		{
			// replace the unsupported lookbehind assertion with a non-capturing subpattern
			$filtersConfig['url']['disallowedHosts'] = str_replace(
				'(?<![^\\.])',
				'(?:^|\\.)',
				$filtersConfig['url']['disallowedHosts']
			);
		}

		return $this->encode(
			$filtersConfig,
			array(
				'preserveKeys' => array(
					array(true)
				),
				'isRegexp' => array(
					array('url', 'allowedSchemes'),
					array('url', 'disallowedHosts')
				)
			)
		);
	}

	/**
	* Generate the list of namespaces in use
	*
	* @return string Javascript representation of an object
	*/
	protected function generateNamespaces()
	{
		return json_encode($this->namespaces, JSON_FORCE_OBJECT);
	}

	/**
	* Generate root context
	*
	* @return string Javascript representation of an object
	*/
	protected function generateRootContext()
	{
		return '{'
		 . 'allowedChildren:' . self::convertBitfield($this->rootContext['allowedChildren']) . ','
		 . 'allowedDescendants:' . self::convertBitfield($this->rootContext['allowedDescendants'])
		 . '}';
	}

	/**
	* Generate the plugins config
	*
	* @return string Javascript representation of an object
	*/
	protected function generatePluginsConfig()
	{
		/**
		* Those keys will be kept in "pluginsConfig", the rest will be moved into the method's body
		* as local var "config"
		*/
		$globalKeys = array(
			'regexp' => 1,
			'regexpLimit' => 1,
			'regexpLimitAction' => 1
		);

		$plugins = array();

		foreach ($this->plugins as $pluginName => $plugin)
		{
			$localConfig  = array_diff_key($plugin['config'], $globalKeys);
			$globalConfig = array_intersect_key($plugin['config'], $globalKeys);

			$globalConfig['parser']
				= 'function(text,matches)'
				. '{'
				. '/** @const */'
				. 'var config=' . $this->encodePluginConfig($localConfig, $plugin['meta']) . ';'
				. $plugin['parser']
				. '}';

			$plugin['meta']['isRawJS'][] = array('parser');

			$plugins[] = json_encode($pluginName) . ':' . $this->encodePluginConfig(
				$globalConfig,
				$plugin['meta']
			);
		}

		return '{' . implode(',', $plugins) . '}';
	}

	/**
	* Generate the tags config
	*
	* @return string Javascript representation of an object
	*/
	protected function generateTagsConfig()
	{
		$tagsConfig = $this->tagsConfig;

		$rm = $this->cb->getRegexpMaster();

		foreach ($tagsConfig as $tagName => &$tagConfig)
		{
			if (!empty($tagConfig['rules']))
			{
				foreach ($tagConfig['rules'] as $rule => &$tagNames)
				{
					/**
					* The PHP parser uses the keys, but the JS parser uses an Array instead
					*/
					$tagNames = array_keys($tagNames);
				}
				unset($tagNames);
			}

			if (!empty($tagConfig['attrs']))
			{
				/**
				* Prepare a regexpMap for compound attributes
				*/
				foreach ($tagConfig['attrs'] as $attrName => &$attrConf)
				{
					if ($attrConf['type'] === 'compound')
					{
						$rm->pcreToJs($attrConf['regexp'], $attrConf['regexpMap']);
					}
				}
				unset($attrConf);
			}

			/**
			* Convert the context bitfields
			*/
			foreach (array('allowedChildren', 'allowedDescendants') as $k)
			{
				$tagConfig[$k] = self::convertBitfield($tagConfig[$k]);
			}
		}
		unset($tagConfig);

		return $this->encode(
			$tagsConfig,
			array(
				'preserveKeys' => array(
					// preserve tag names
					array(true),
					// preserve attribute names
					array(true, 'attrs', true)
				),
				'isRawJS'  => array(
					array(true, 'allowedChildren'),
					array(true, 'allowedDescendants')
				),
				'isRegexp' => array(
					// some attribute types use a regexp
					array(true, 'attrs', true, 'regexp')
				)
			)
		);
	}

	//==========================================================================
	// Misc
	//==========================================================================

	/**
	* Compile/minimize the JS source
	*/
	protected function injectXSL()
	{
		$xsl = new DOMDocument;
		$xsl->loadXML($this->xsl);

		/**
		* Remove the "/m" template, which is only used when rendering multiple texts
		*/
		$xpath = new DOMXPath($xsl);
		$nodes = $xpath->query(
			'//*[@match="/m"][local-name()="template"][namespace-uri()="http://www.w3.org/1999/XSL/Transform"]'
		);

		foreach ($nodes as $node)
		{
			$node->parentNode->removeChild($node);
		}

		$this->src = str_replace(
			'/* XSL WILL BE INSERTED HERE */',
			json_encode($xsl->saveXML($xsl->documentElement)),
			$this->src
		);
	}

	/**
	* Compile/minimize the JS source
	*/
	protected function compile()
	{
		$content = http_build_query(array(
			'output_format'     => 'json',
			'output_info'       => 'compiled_code',
			'compilation_level' => $this->options['compilationLevel'],
			'js_code'           => $this->src
		));

		// Got to add dupe variables by hand
		$content .= '&output_info=errors';

		$response = json_decode(file_get_contents(
			$this->options['closureCompilerURL'],
			false,
			stream_context_create(array(
				'http' => array(
					'method'  => 'POST',
					'header'  => "Connection: close\r\n"
					           . "Content-length: " . strlen($content) . "\r\n"
					           . "Content-type: application/x-www-form-urlencoded",
					'content' => $content
				)
			))
		), true);

		if (!$response || !isset($response['compiledCode']))
		{
			throw new RuntimeException('An error occured while contacting Google Closure Compiler');
		}

		if (isset($response['errors']))
		{
			throw new RuntimeException("An error has been returned Google Closure Compiler: '" . $response['errors'][0]['error'] . "'");
		}

		$this->src = $response['compiledCode'];
	}

	/**
	* Rename properties whose name Closure Compiler preserves
	*/
	protected function renameReservedProperties()
	{
		$rename = array(
			'attrName',
			'defaultValue',
			'id',
			'name',
			'rules',
			'tagName',
			'type'
		);

		// tag.name
		$this->src = preg_replace(
			'#(?<=\\.)(?=(?:' . implode('|', $rename) . ')(?=\\W))#',
			'_',
			$this->src
		);

		// name:
		$this->src = preg_replace(
			'#(?<=\\W)(?=(?:' . implode('|', $rename) . ')(?=\\s*:))#',
			'_',
			$this->src
		);
	}

	//==========================================================================
	// Tools that deal with the Javascript representation of PHP structures
	//==========================================================================

	/**
	* Convert a bitfield to the Javascript representationg of an array of number
	*
	* Context bitfields are stored as binary strings, but Javascript doesn't bitfieldto the Javascript representation of an array of numbers
	* expressed in hexadecimal to work around some potential bugs with 32bit PHP
	*/
	static protected function convertBitfield($bitfield)
	{
		$hex = array();

		foreach (str_split($bitfield, 4) as $quad)
		{
			$v = 0;
			foreach (str_split($quad, 1) as $n => $c)
			{
				$v += ord($c) << ($n * 8);
			}

			$hex[] = '0x' . dechex($v);
		}

		return '[' . implode(',', $hex) . ']';
	}

	/**
	* Encode a plugin's config into Javascript
	*
	* @param  array  $pluginConfig Plugin's config
	* @param  array  $meta         Metadata associated with the config
	* @return string               Javascript representation of config
	*/
	protected function encodePluginConfig(array $pluginConfig, array $meta)
	{
		// We don't need those in the JS parser
		unset($pluginConfig['parserClassName']);
		unset($pluginConfig['parserFilepath']);

		// mark the plugin's regexp(s) as global regexps
		if (!empty($pluginConfig['regexp']))
		{
			$keypath = (is_array($pluginConfig['regexp']))
			         ? array('regexp', true)
			         : array('regexp');

			$meta = array_merge_recursive($meta, array(
				'isGlobalRegexp' => array(
					$keypath
				)
			));
		}

		return $this->encode($pluginConfig, $meta);
	}

	/**
	* Encode an array to Javascript
	*
	* Replaces booleans with 0s and 1s
	*
	* @param  array  $arr
	* @param  array  $meta Metadata associated with the array
	* @return string               Javascript representation of config
	*/
	protected function encode(array $arr, array $meta = array())
	{
		/**
		* Replace booleans with 1/0
		*/
		array_walk_recursive($arr, function (&$v)
		{
			if (is_bool($v))
			{
				$v = (int) $v;
			}
		});

		return $this->encodeArray($arr, $meta);
	}

	/**
	* Encode an array to Javascript
	*
	* @param  array  $arr
	* @param  array  $meta Metadata associated with the array
	* @return string               Javascript representation of config
	*/
	protected function encodeArray(array $arr, array $meta = array())
	{
		$rm = $this->cb->getRegexpMaster();

		$match = array();

		foreach ($meta as $name => $keypaths)
		{
			foreach ($arr as $k => $v)
			{
				$match[$name][$k] = (in_array(array($k), $keypaths, true) || in_array(array(true), $keypaths, true));
			}
		}

		foreach ($arr as $k => &$v)
		{
			if (!empty($match['isRegexp'][$k]))
			{
				$v = $rm->pcreToJs($v);
			}
			elseif (!empty($match['isGlobalRegexp'][$k]))
			{
				$v = $rm->pcreToJs($v) . 'g';
			}
			elseif (!empty($match['isRawJS'][$k]))
			{
				// do nothing
			}
			elseif (is_array($v))
			{
				$v = $this->encodeArray(
					$v,
					self::filterKeyPaths($meta, $k)
				);
			}
			else
			{
				$v = json_encode($v);
			}
		}
		unset($v);

		if (array_keys($arr) === range(0, count($arr) - 1))
		{
			return '[' . implode(',', $arr) . ']';
		}

		$ret = array();
		foreach ($arr as $k => $v)
		{
			if (!empty($match['preserveKeys'][$k])
			 || preg_match(self::RESERVED_WORDS_REGEXP, $k)
			 || !preg_match('#^[a-z_0-9]+$#Di', $k))
			{
				$k = json_encode($k);
			}

			$ret[] = "$k:" . $v;
		}

		return '{' . implode(',', $ret) . '}';
	}

	/**
	* Filter an array of keypath, removing paths that don't match current key and removing the first
	* key of the remaining paths
	*
	* @param  array  $meta Array of keypaths, sorted by metadata type
	* @param  string $key  Current key
	* @return array        Filtered metadata
	*/
	protected function filterKeyPaths(array $meta, $key)
	{
		$ret = array();
		foreach ($meta as $name => $keypaths)
		{
			foreach ($keypaths as $keypath)
			{
				if ($keypath[0] === $key
				 || $keypath[0] === true)
				{
					$ret[$name][] = array_slice($keypath, 1);
				}
			}
		}

		return array_map('array_filter', $ret);
	}
}