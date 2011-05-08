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
	* 
	*
	* @return void
	*/
	public function __construct(ConfigBuilder $cb)
	{
		$this->cb = $cb;

		$this->tpl = file_get_contents(__DIR__ . '/Parser.js');
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

		$this->removeFunctions('processCurrentTagAttributes');
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
		$this->src = str_replace(
			'pluginParsers = {/* DO NOT EDIT*/}',
			'pluginParsers = ' . $this->generatePluginParsers(),
			$this->src
		);
	}

	protected function generateTagsConfig()
	{
		$tagsConfig = '';
		$prepend = '{';

		$replace = array();

		foreach ($this->parserConfig['tags'] as $tagName => $tagConfig)
		{
			/**
			* Replace true/false with 1/0
			*/
			$tagConfig['allow'] = array_map('intval', $tagConfig['allow']);

			/**
			* Sort tags alphabetically. It can improve the compression if the source gets gzip'ed
			*/
			ksort($tagConfig['allow']);

			/**
			* We replace the "allow" object with a token that we will later replace with the
			* original value in order to preserve quotes around tag names
			*/
			$json = json_encode($tagConfig['allow']);
			$md5  = md5($json);
			$replace[$md5] = $json;
			$tagConfig['allow'] = $md5;

			$tagsConfig .= $prepend . '"' . $tagName . '":';
			$tagsConfig .= preg_replace(
				'#(?<=[\\{,])"([a-z]+)"(?=[:\\}])#i',
				'$1',
				json_encode($tagConfig, JSON_HEX_QUOT)
			);

			$prepend = ',';
		}
		$tagsConfig .= '}';

		$tagsConfig = preg_replace_callback(
			'#([\'"])(' . implode('|', array_keys($replace)) . ')\1#',
			function ($m) use ($replace)
			{
				return $replace[$m[2]];
			},
			$tagsConfig
		);

		return $tagsConfig;
	}

	protected function generatePluginParsers()
	{
		$js = '{';

		foreach ($this->parserConfig['plugins'] as $pluginName => $pluginConf)
		{
			$parserJS = $this->cb->$pluginName->getJSParser();

			if (!$parserJS)
			{
				continue;
			}

			unset($pluginConf['parserClassName'], $pluginConf['parserFilepath']);

			$config = json_encode($pluginConf, JSON_HEX_QUOT);

			$preserveProps = $this->cb->$pluginName->getPreservedJSProps();
			$regexp = '#"'
			        . (($preserveProps) ? '(?!' . implode('|', $preserveProps) . ')' : '')
			        . '([A-Za-z_][A-Za-z_0-9]*)"(?=:)#';

			$config = 'var config=' . preg_replace($regexp, '$1', $config) . ';';

			$js .= '"' . $pluginName . '":function(text,matches){' . $config . $parserJS . '},';
		}

		$js = substr($js, 0, -1) . '}';

		return $js;
	}
}