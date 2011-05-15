<?php

/**
* @package   s9e\Toolkit
* @copyright Copyright (c) 2010-2011 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\Toolkit\TextFormatter;

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
	* @var array Parser's config
	*/
	protected $parserConfig;

	/**
	* @var array JS plugins parsers
	*/
	protected $pluginParsers;

	/**
	* @var array JS plugins config
	*/
	protected $pluginsConfig;

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

		$this->parserConfig = $this->cb->getParserConfig();
		$this->src = $this->tpl;

		if ($options['removeDeadCode'])
		{
			$this->removeDeadCode();
		}

		$this->injectTagsConfig();
		$this->injectPluginParsers();
		$this->injectPluginsConfig();
		$this->injectFiltersConfig();

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
		foreach ($this->parserConfig['tags'] as $tagConfig)
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
		foreach ($this->parserConfig['tags'] as $tagConfig)
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

		foreach ($this->parserConfig['tags'] as $tagConfig)
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
			foreach ($this->parserConfig['tags'] as $tagConfig)
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
		foreach ($this->parserConfig['tags'] as $tagConfig)
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
		foreach ($this->parserConfig['tags'] as $tagConfig)
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

	protected function injectPluginParsers()
	{
		$this->generatePluginParsers();

		$this->src = str_replace(
			'pluginParsers = {/* DO NOT EDIT*/}',
			'pluginParsers = {' . implode(',', $this->pluginParsers) . '}',
			$this->src
		);
	}

	protected function injectPluginsConfig()
	{
		$this->generatePluginsConfig();

		$this->src = str_replace(
			'pluginsConfig = {/* DO NOT EDIT*/}',
			'pluginsConfig = {' . implode(',', $this->pluginsConfig) . '}',
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

	/**
	* Kind of hardcoding stuff here, will need to be cleaned up at some point
	*/
	protected function generateFiltersConfig()
	{
		$filtersConfig = $this->parserConfig['filters'];

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
		$tagsConfig = $this->parserConfig['tags'];

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

	protected function generatePluginsConfig()
	{
		$this->pluginsConfig = array();

		foreach ($this->pluginParsers as $pluginName => $parserJS)
		{
			$pluginConfig = $this->parserConfig['plugins'][$pluginName];

			/**
			* Remove useless settings
			*/
			unset(
				$pluginConfig['parserClassName'],
				$pluginConfig['parserFilepath']
			);

			/**
			* Prepare the plugin config
			*/
			$config = self::encode(
				$pluginConfig,
				array(
					'preserveKeys' => $this->cb->$pluginName->getPreservedJSProps(),
					'isGlobalRegexp' => array(
						array('regexp'),
						array('regexp', true)
					)
				)
			);

			$this->pluginsConfig[$pluginName] = json_encode($pluginName) . ':' . $config;
		}
	}

	protected function generatePluginParsers()
	{
		$this->pluginParsers = array();

		foreach ($this->parserConfig['plugins'] as $pluginName => $pluginConfig)
		{
			$js = $this->cb->$pluginName->getJSParser();

			if (!$js)
			{
				continue;
			}

			$this->pluginParsers[$pluginName] =
				'"' . $pluginName . "\":function(text,matches){/** @const */var config=pluginsConfig['" . $pluginName . "'];" . $js . '}';
		}
	}

	/**
	* Remove JS code related to whitespace trimming if not used
	*/
	protected function removeWhitespaceTrimming()
	{
		foreach ($this->parserConfig['tags'] as $tagConfig)
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

	static public function encode(array $arr, array $struct)
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
			$regexp = preg_replace('#(?<!\\)(?:\\\\\\\\)*\\.#', '[\\s\\S]', $regexp);
		}

		$modifiers = preg_replace('#[SusD]#', '', $modifiers);

		$js = 'new RegExp(' . json_encode($regexp)
		    . (($flags || $modifiers) ?  ',' . json_encode($flags . $modifiers) : '')
		    . ')';

		return $js;
	}

	static public function encodeArray(array $arr, array $struct)
	{
		$match = array();

		foreach ($struct as $name => $keypaths)
		{
			foreach ($arr as $k => $v)
			{
				$match[$name][$k] =
					(in_array(array((string) $k), $keypaths, true)
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
			if (!empty($match['preserveKey'][$k])
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