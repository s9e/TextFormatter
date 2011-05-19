<?php

/**
* @package   s9e\Toolkit
* @copyright Copyright (c) 2010-2011 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\Toolkit\TextFormatter;

use RuntimeException;

class JSParserGenerator
{
	/**
	* @var ConfigBuilder
	*/
	protected $cb;

	/**
	* @var string Template source
	*/
	protected $tpl;

	/**
	* @var string Source being generated
	*/
	protected $src;

	/**
	* @var array Tags' config
	*/
	protected $tagsConfig;

	/**
	* List of Javascript reserved words
	*
	* @link https://developer.mozilla.org/en/JavaScript/Reference/Reserved_Words
	*
	* Also, Closure Compiler doesn't like "char"
	*/
	const RESERVED_WORDS_REGEXP =
		'#^(?:break|case|catch|continue|debugger|default|delete|do|else|finally|for|function|if|in|instanceof|new|return|switch|this|throw|try|typeof|var|void|while|with|class|enum|export|extends|import|super|implements|interface|let|package|private|protected|public|static|yield|char)$#D';

	/**
	* 
	*
	* @return void
	*/
	public function __construct(ConfigBuilder $cb)
	{
		$this->cb = $cb;

		$this->tpl = file_get_contents(__DIR__ . '/TextFormatter.js');
	}

	/**
	* 
	*
	* @return string
	*/
	public function get(array $options = array())
	{
		$options += array(
			'compilation'     => 'none',
			'disableLogTypes' => array(),
			'removeDeadCode'  => true
		);

		$this->tagsConfig = $this->cb->getTagsConfig(true);
		$this->src = $this->tpl;

		if ($options['removeDeadCode'])
		{
			$this->removeDeadCode();
		}

		$this->injectTagsConfig();
		$this->injectPlugins();
		$this->injectFiltersConfig();
		$this->injectCallbacks();

		/**
		* Turn off logging selectively
		*/
		if ($options['disableLogTypes'])
		{
			$this->src = preg_replace(
				"#\\n\\s*(?=log\\('(?:" . implode('|', $options['disableLogTypes']) . ")',)#",
				'${0}0&&',
				$this->src
			);
		}

		$this->injectXSL();

		if ($options['compilation'] !== 'none')
		{
			$this->compile($options['compilation']);
		}

		return $this->src;
	}

	/**
	* Compile/minimize the JS source
	*
	* @param string $level Level to be passed to the Google Closure Compiler service
	*/
	protected function compile($level)
	{
		$content = http_build_query(array(
			'js_code' => $this->src,
			'compilation_level' => $level,
			'output_format' => 'text',
//			'formatting' => 'pretty_print',
			'output_info' => 'compiled_code'
		));

		$this->src = file_get_contents(
			'http://closure-compiler.appspot.com/compile',
			false,
			stream_context_create(array(
				'http' => array(
					'method'  => 'POST',
					'header'  => "Connection: close\r\n"
					           . "Content-length: " . strlen($content) . "\r\n"
					           . "Content-type: application/x-www-form-urlencoded\r\n",
					'content' => $content
				)
			))
		);
	}

	/**
	* Remove JS code that is not going to be used
	*/
	protected function removeDeadCode()
	{
		$this->removeDeadRules();
		$this->removeAttributesProcessing();
		$this->removeDeadFilters();
		$this->removeWhitespaceTrimming();
		$this->removeCompoundAttritutesSplitting();
		$this->removePhaseCallbacksProcessing();
		$this->removeAttributesDefaultValueProcessing();
		$this->removeRequiredAttributesProcessing();
	}

	/**
	* Remove the code related to attributes' default value if no attribute has a default value
	*/
	protected function removeAttributesDefaultValueProcessing()
	{
		foreach ($this->tagsConfig as $tagConfig)
		{
			if (!empty($tagConfig['attrs']))
			{
				foreach ($tagConfig['attrs'] as $attrName => $attrConf)
				{
					if (isset($attrConf['defaultValue']))
					{
						return;
					}
				}
			}
		}

		$this->removeFunctions('addDefaultAttributeValuesToCurrentTag');
	}

	/**
	* Remove the code checks whether all required attributes have been filled in for a tag, if
	* there no required attribute for any tag
	*/
	protected function removeRequiredAttributesProcessing()
	{
		foreach ($this->tagsConfig as $tagConfig)
		{
			if (!empty($tagConfig['attrs']))
			{
				foreach ($tagConfig['attrs'] as $attrName => $attrConf)
				{
					if (isset($attrConf['isRequired']))
					{
						return;
					}
				}
			}
		}

		$this->removeFunctions('currentTagRequiresMissingAttribute');
	}

	/**
	* Remove the code related to preFilter/postFilter callbacks
	*/
	protected function removePhaseCallbacksProcessing()
	{
		$remove = array(
			'applyTagPreFilterCallbacks' => 1,
			'applyTagPostFilterCallbacks' => 1,
			'applyAttributePreFilterCallbacks' => 1,
			'applyAttributePostFilterCallbacks' => 1
		);

		foreach ($this->tagsConfig as $tagConfig)
		{
			if (!empty($tagConfig['preFilter']))
			{
				unset($remove['applyTagPreFilterCallbacks']);
			}

			if (!empty($tagConfig['postFilter']))
			{
				unset($remove['applyTagPostFilterCallbacks']);
			}

			if (!empty($tagConfig['attrs']))
			{
				foreach ($tagConfig['attrs'] as $attrName => $attrConf)
				{
					if (!empty($attrConf['preFilter']))
					{
						unset($remove['applyAttributePreFilterCallbacks']);
					}

					if (!empty($attrConf['postFilter']))
					{
						unset($remove['applyAttributePostFilterCallbacks']);
					}
				}
			}
		}

		if (count($remove) === 4)
		{
			$remove['applyCallback'] = 1;
		}

		if ($remove)
		{
			$this->removeFunctions(implode('|', array_keys($remove)));
		}
	}

	/**
	* Remove JS code related to rules that are not used
	*/
	protected function removeDeadRules()
	{
		$rules = array(
			'closeParent',
			'closeAscendant',
			'requireParent',
			'requireAscendant'
		);

		$remove = array();

		foreach ($rules as $rule)
		{
			foreach ($this->tagsConfig as $tagConfig)
			{
				if (!empty($tagConfig['rules'][$rule]))
				{
					continue 2;
				}
			}

			$remove[] = $rule;
		}

		if ($remove)
		{
			$this->removeFunctions(implode('|', $remove));
		}
	}

	/**
	* Remove JS code related to attributes if no attributes exist
	*/
	protected function removeAttributesProcessing()
	{
		foreach ($this->tagsConfig as $tagConfig)
		{
			if (!empty($tagConfig['attrs']))
			{
				return;
			}
		}

		$this->removeFunctions('filterAttributes|filter');
	}

	/**
	* Remove JS code related to compound attributes if none exist
	*/
	protected function removeCompoundAttritutesSplitting()
	{
		foreach ($this->tagsConfig as $tagConfig)
		{
			if (!empty($tagConfig['attrs']))
			{
				foreach ($tagConfig['attrs'] as $attrConf)
				{
					if ($attrConf['type'] === 'compound')
					{
						return;
					}
				}
			}
		}

		$this->removeFunctions('splitCompoundAttributes');
	}

	/**
	* Remove JS code related to filters that are not used
	*/
	protected function removeDeadFilters()
	{
		$keepFilters = array();

		foreach ($this->tagsConfig as $tagConfig)
		{
			if (!empty($tagConfig['attrs']))
			{
				foreach ($tagConfig['attrs'] as $attrConf)
				{
					$keepFilters[$attrConf['type']] = 1;
				}
			}
		}
		$keepFilters = array_keys($keepFilters);

		$this->src = preg_replace_callback(
			"#\n\tfunction filter\\(.*?\n\t\}#s",
			function ($functionBlock) use ($keepFilters)
			{
				return preg_replace_callback(
					"#\n\t\t\tcase .*?\n\t\t\t\treturn[^\n]+\n#s",
					function ($caseBlock) use ($keepFilters)
					{
						preg_match_all("#\n\t\t\tcase '([a-z]+)#", $caseBlock[0], $m);

						if (array_intersect($m[1], $keepFilters))
						{
							// at least one of those case can happen, we keep the whole block
							return $caseBlock[0];
						}

						// remove block
						return '';
					},
					$functionBlock[0]
				);
			},
			$this->src
		);
	}

	/**
	* Remove the content of some JS functions from the source
	*
	* @param string  $regexp Regexp used to match the function's name
	*/
	protected function removeFunctions($regexp)
	{
		$this->src = preg_replace(
			'#(\\n\\t+function\\s+(?:' . $regexp . ')\\(.*?\\)(\\n\\t+)\\{).*?(\\2\\})#s',
			'$1$3',
			$this->src
		);
	}

	protected function injectTagsConfig()
	{
		$this->src = str_replace(
			'tagsConfig = {/* DO NOT EDIT*/}',
			'tagsConfig = ' . $this->generateTagsConfig(),
			$this->src
		);
	}

	protected function injectPlugins()
	{
		$pluginParsers = array();
		$pluginsConfig = array();

		foreach ($this->cb->getJSPlugins() as $pluginName => $plugin)
		{
			$js = self::encodeConfig(
				$plugin['config'],
				$plugin['meta']
			);

			$pluginsConfig[] = json_encode($pluginName) . ':' . $js;
			$pluginParsers[] =
				json_encode($pluginName) . ':function(text,matches){/** @const */var config=pluginsConfig["' . $pluginName . '"];' . $plugin['parser'] . '}';
		}

		$this->src = str_replace(
			'pluginsConfig = {/* DO NOT EDIT*/}',
			'pluginsConfig = {' . implode(',', $pluginsConfig) . '}',
			$this->src
		);

		$this->src = str_replace(
			'pluginParsers = {/* DO NOT EDIT*/}',
			'pluginParsers = {' . implode(',', $pluginParsers) . '}',
			$this->src
		);
	}

	protected function injectPluginParsers()
	{
		$this->src = str_replace(
			'pluginParsers = {/* DO NOT EDIT*/}',
			'pluginParsers = {' . $this->generatePluginParsers() . '}',
			$this->src
		);
	}

	protected function injectPluginsConfig()
	{
		$this->src = str_replace(
			'pluginsConfig = {/* DO NOT EDIT*/}',
			'pluginsConfig = {' . $this->generatePluginsConfig() . '}',
			$this->src
		);
	}

	protected function injectFiltersConfig()
	{
		$this->src = str_replace(
			'filtersConfig = {/* DO NOT EDIT*/}',
			'filtersConfig = ' . $this->generateFiltersConfig(),
			$this->src
		);
	}

	protected function injectCallbacks()
	{
		$this->src = str_replace(
			'callbacks = {/* DO NOT EDIT*/}',
			'callbacks = ' . $this->generateCallbacks(),
			$this->src
		);
	}

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
				throw new RuntimeException("Invalid callback name '" . $funcName . "'");
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
	* Kind of hardcoding stuff here, will need to be cleaned up at some point
	*/
	protected function generateFiltersConfig()
	{
		$filtersConfig = $this->cb->getFiltersConfig();

		if (isset($filtersConfig['url']['disallowedHosts']))
		{
			// replace the unsupported lookbehind assertion with a non-capturing subpattern
			$filtersConfig['url']['disallowedHosts'] = str_replace(
				'(?<![^\\.])',
				'(?:^|\\.)',
				$filtersConfig['url']['disallowedHosts']
			);
		}

		return self::encode(
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

	protected function generateTagsConfig()
	{
		$tagsConfig = $this->tagsConfig;

		foreach ($tagsConfig as $tagName => &$tagConfig)
		{
			$this->fixTagAttributesRegexp($tagConfig);

			/**
			* Sort tags alphabetically. It can improve the compression if the source gets gzip'ed
			*/
			ksort($tagConfig['allow']);

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
		}
		unset($tagConfig);

		return self::encode(
			$tagsConfig,
			array(
				'preserveKeys' => array(
					array(true),
					array(true, 'allow', true),
					array(true, 'attrs', true)
				),
				'isRegexp' => array(
					array(true, 'attrs', true, 'regexp')
				)
			)
		);
	}

	protected function fixTagAttributesRegexp(array &$tagConfig)
	{
		if (empty($tagConfig['attrs']))
		{
			return;
		}

		foreach ($tagConfig['attrs'] as &$attrConf)
		{
			if (!isset($attrConf['regexp']))
			{
				continue;
			}

			$backslashes = '(?<!\\\\)(?<backslashes>(?:\\\\\\\\)*)';
			$nonCapturing = '(?<nonCapturing>\\?[a-zA-Z]*:)';
			$name = '(?<name>[A-Za-z_0-9]+)';
			$namedCapture = implode('|', array(
				'\\?P?<' . $name . '>',
				"\\?'" . $name . "'"
			));

			$k = 0;
			$attrConf['regexp'] = preg_replace_callback(
				'#' . $backslashes . '\\((?J:' . $nonCapturing . '|' . $namedCapture . ')#',
				function ($m) use (&$attrConf, &$k)
				{
					if ($m['nonCapturing'])
					{
						return $m[0];
					}

					$attrConf['regexpMap'][$m['name']] = ++$k;

					return $m['backslashes'] . '(';
				},
				$attrConf['regexp']
			);
		}
	}

	protected function generatePluginParsers()
	{
		$pluginParsers = array();

		foreach ($this->cb->getLoadedPlugins() as $pluginName => $plugin)
		{
			$js = $plugin->getJSParser();

			if (!$js)
			{
				continue;
			}

			$pluginParsers[] = json_encode($pluginName) . ':function(text,matches){' . $js . '}';
		}

		return implode(',', $pluginParsers);
	}

	protected function generatePluginsConfig()
	{
		$pluginsConfig = array();

		foreach ($this->cb->getLoadedPlugins() as $pluginName => $plugin)
		{
			$js = self::encodeConfig(
				$plugin->getJSConfig(),
				$plugin->getJSConfigMeta()
			);

			$pluginsConfig[] = json_encode($pluginName) . ':' . $js;
		}

		return implode(',', $pluginsConfig);
	}

	/**
	* Remove JS code related to whitespace trimming if not used
	*/
	protected function removeWhitespaceTrimming()
	{
		foreach ($this->tagsConfig as $tagConfig)
		{
			if (!empty($tagConfig['trimBefore'])
			 || !empty($tagConfig['trimAfter']))
			{
				return;
			}
		}

		$this->removeFunctions('addTrimmingInfoToTag');
	}

	protected function injectXSL()
	{
		$pos = 58 + strpos(
			$this->src,
			"xslt['importStylesheet'](new DOMParser().parseFromString('', 'text/xml'));"
		);

		$this->src = substr($this->src, 0, $pos)
		           . addcslashes($this->cb->getXSL(), "'\\\r\n")
		           . substr($this->src, $pos);
	}

	static public function encodeConfig(array $pluginConfig, array $struct)
	{
		unset(
			$pluginConfig['parserClassName'],
			$pluginConfig['parserFilepath']
		);

		// mark the plugin's regexp(s) as global regexps
		if (!empty($pluginConfig['regexp']))
		{
			$keypath = (is_array($pluginConfig['regexp']))
			         ? array('regexp', true)
			         : array('regexp');

			$struct = array_merge_recursive($struct, array(
				'isGlobalRegexp' => array(
					$keypath
				)
			));
		}

		return self::encode($pluginConfig, $struct);
	}

	static public function encode(array $arr, array $struct = array())
	{
		/**
		* Replace booleans with 1/0
		*/
		array_walk_recursive($arr, function(&$v)
		{
			if (is_bool($v))
			{
				$v = (int) $v;
			}
		});

		return self::encodeArray($arr, $struct);
	}

	static public function convertRegexp($regexp, $flags = '')
	{
		$pos = strrpos($regexp, $regexp[0]);

		$modifiers = substr($regexp, $pos + 1);
		$regexp    = substr($regexp, 1, $pos - 1);

		if (strpos($modifiers, 's') !== false)
		{
			/**
			* Uses the "s" modifier, which doesn't exist in Javascript RegExp and has
			* to be replaced with the character class [\s\S]
			*/
			$regexp = preg_replace('#(?<!\\\\)((?:\\\\\\\\)*)\\.#', '$1[\\s\\S]', $regexp);
		}

		/**
		* Replace \pL with \w because Javascript doesn't support Unicode properties. Other Unicode
		* properties are currently unsupported.
		*/
		$regexp = str_replace('\\pL', '\\w', $regexp);

		$modifiers = preg_replace('#[SusD]#', '', $modifiers);

		$js = 'new RegExp(' . json_encode($regexp)
		    . (($flags || $modifiers) ?  ',' . json_encode($flags . $modifiers) : '')
		    . ')';

		return $js;
	}

	static public function encodeArray(array $arr, array $struct = array())
	{
		$match = array();

		foreach ($struct as $name => $keypaths)
		{
			foreach ($arr as $k => $v)
			{
				$match[$name][$k] =
					(in_array(array($k), $keypaths, true)
					|| in_array(array(true), $keypaths, true));
			}
		}

		foreach ($arr as $k => &$v)
		{
			if (!empty($match['isRegexp'][$k]))
			{
				$v = self::convertRegexp($v);
			}
			elseif (!empty($match['isGlobalRegexp'][$k]))
			{
				$v = self::convertRegexp($v, 'g');
			}
			elseif (is_array($v))
			{
				$v = self::encodeArray(
					$v,
					self::filterKeyPaths($struct, $k)
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

	static protected function filterKeyPaths(array $struct, $key)
	{
		$ret = array();
		foreach ($struct as $name => $keypaths)
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